use anyhow::Context;
use sqlx::{PgPool, postgres::PgPoolOptions};

pub async fn connect() -> anyhow::Result<PgPool> {
    let url = std::env::var("DATABASE_URL").context("DATABASE_URL is required")?;
    PgPoolOptions::new()
        .max_connections(10)
        .connect(&url)
        .await
        .context("could not connect to PostgreSQL")
}

pub async fn migrate(pool: &PgPool) -> anyhow::Result<()> {
    sqlx::migrate!("./migrations").run(pool).await?;
    Ok(())
}
