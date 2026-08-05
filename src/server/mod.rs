pub mod auth;
pub mod db;
pub mod graphql;

use async_graphql::{EmptySubscription, Schema};
use axum::extract::FromRef;
use leptos::config::LeptosOptions;
use sqlx::PgPool;

pub type AppSchema = Schema<graphql::QueryRoot, graphql::MutationRoot, EmptySubscription>;

#[derive(Clone)]
pub struct AppState {
    pub leptos_options: LeptosOptions,
    pub pool: PgPool,
    pub schema: AppSchema,
    pub cookie_secure: bool,
}

impl FromRef<AppState> for LeptosOptions {
    fn from_ref(state: &AppState) -> Self { state.leptos_options.clone() }
}
