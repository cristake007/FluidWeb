use async_graphql::{Context, Error, Object, SimpleObject};
use chrono::{DateTime, Utc};
use serde::{Deserialize, Serialize};
use sqlx::{FromRow, PgPool};
use uuid::Uuid;

use super::auth::{SESSION_COOKIE, hash_password, new_session_token, token_hash, verify_password};

#[derive(Clone, Debug, FromRow, Serialize, Deserialize, SimpleObject)]
#[graphql(rename_fields = "camelCase")]
pub struct User {
    pub id: Uuid,
    pub email: String,
    pub first_name: String,
    pub last_name: String,
    pub roles: Vec<String>,
    pub active: bool,
    pub created_at: DateTime<Utc>,
}

#[derive(SimpleObject)]
#[graphql(rename_fields = "camelCase")]
pub struct AuthPayload {
    pub user: User,
}

#[derive(Clone)]
pub struct RequestAuth {
    pub user: Option<User>,
    pub cookie_secure: bool,
}

pub struct QueryRoot;

#[Object]
impl QueryRoot {
    async fn health(&self) -> &'static str {
        "ok"
    }

    async fn me(&self, ctx: &Context<'_>) -> async_graphql::Result<User> {
        ctx.data::<RequestAuth>()?
            .user
            .clone()
            .ok_or_else(|| Error::new("Unauthenticated"))
    }

    async fn users(&self, ctx: &Context<'_>) -> async_graphql::Result<Vec<User>> {
        let auth = ctx.data::<RequestAuth>()?;
        let current = auth
            .user
            .as_ref()
            .ok_or_else(|| Error::new("Unauthenticated"))?;

        if !current.roles.iter().any(|role| role == "ROLE_ADMIN") {
            return Err(Error::new("Forbidden"));
        }

        let pool = ctx.data::<PgPool>()?;
        Ok(sqlx::query_as::<_, User>("SELECT id, email, first_name, last_name, roles, active, created_at FROM users ORDER BY email")
            .fetch_all(pool)
            .await?)
    }
}

pub struct MutationRoot;

#[Object]
impl MutationRoot {
    async fn login(
        &self,
        ctx: &Context<'_>,
        email: String,
        password: String,
    ) -> async_graphql::Result<AuthPayload> {
        let pool = ctx.data::<PgPool>()?;
        let row = sqlx::query_as::<_, LoginRow>("SELECT id, email, first_name, last_name, roles, active, created_at, password_hash FROM users WHERE lower(email) = lower($1)")
            .bind(email.trim())
            .fetch_optional(pool)
            .await?
            .ok_or_else(|| Error::new("Invalid credentials"))?;

        if !row.active || !verify_password(&password, &row.password_hash) {
            return Err(Error::new("Invalid credentials"));
        }

        let token = new_session_token();
        sqlx::query("INSERT INTO sessions (token_hash, user_id, expires_at) VALUES ($1, $2, now() + interval '12 hours')")
            .bind(token_hash(&token))
            .bind(row.id)
            .execute(pool)
            .await?;

        let secure = ctx.data::<RequestAuth>()?.cookie_secure;
        let secure_attribute = if secure { "; Secure" } else { "" };
        ctx.insert_http_header(
            "Set-Cookie",
            format!("{SESSION_COOKIE}={token}; Path=/; HttpOnly; SameSite=Lax; Max-Age=43200{secure_attribute}"),
        );

        Ok(AuthPayload {
            user: row.into_user(),
        })
    }

    async fn logout(&self, ctx: &Context<'_>) -> async_graphql::Result<bool> {
        ctx.insert_http_header(
            "Set-Cookie",
            format!("{SESSION_COOKIE}=; Path=/; HttpOnly; SameSite=Lax; Max-Age=0"),
        );
        Ok(true)
    }
}

#[derive(FromRow)]
struct LoginRow {
    id: Uuid,
    email: String,
    first_name: String,
    last_name: String,
    roles: Vec<String>,
    active: bool,
    created_at: DateTime<Utc>,
    password_hash: String,
}

impl LoginRow {
    fn into_user(self) -> User {
        User {
            id: self.id,
            email: self.email,
            first_name: self.first_name,
            last_name: self.last_name,
            roles: self.roles,
            active: self.active,
            created_at: self.created_at,
        }
    }
}

pub async fn create_admin(
    pool: &PgPool,
    email: &str,
    first_name: &str,
    last_name: &str,
    password: &str,
) -> anyhow::Result<()> {
    let password_hash = hash_password(password)?;
    sqlx::query("INSERT INTO users (email, first_name, last_name, roles, active, password_hash) VALUES ($1, $2, $3, ARRAY['ROLE_ADMIN']::text[], true, $4) ON CONFLICT (email) DO UPDATE SET first_name = EXCLUDED.first_name, last_name = EXCLUDED.last_name, roles = EXCLUDED.roles, active = true, password_hash = EXCLUDED.password_hash")
        .bind(email.trim())
        .bind(first_name.trim())
        .bind(last_name.trim())
        .bind(password_hash)
        .execute(pool)
        .await?;

    Ok(())
}
