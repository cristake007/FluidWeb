#[cfg(feature = "ssr")]
#[tokio::main]
async fn main() -> anyhow::Result<()> {
    use async_graphql::{EmptySubscription, Schema, http::GraphiQLSource};
    use async_graphql_axum::{GraphQLRequest, GraphQLResponse};
    use axum::{Router, extract::State, http::{HeaderMap, StatusCode}, response::{Html, IntoResponse}, routing::get};
    use leptos::prelude::*;
    use leptos_axum::{generate_route_list, LeptosRoutes};
    use tower_http::{compression::CompressionLayer, trace::TraceLayer};
    use tracing_subscriber::EnvFilter;

    use fluidweb::{app::{App, shell}, server::{AppState, auth, db, graphql::{self, MutationRoot, QueryRoot, RequestAuth}}};

    tracing_subscriber::fmt().with_env_filter(EnvFilter::try_from_default_env().unwrap_or_else(|_| "fluidweb=info,tower_http=info".into())).init();
    let pool = db::connect().await?;
    db::migrate(&pool).await?;

    let args: Vec<String> = std::env::args().collect();
    if args.get(1).map(String::as_str) == Some("create-admin") {
        if args.len() != 6 { anyhow::bail!("usage: fluidweb create-admin <email> <first-name> <last-name> <password>"); }
        graphql::create_admin(&pool, &args[2], &args[3], &args[4], &args[5]).await?;
        println!("Administrator account created or updated.");
        return Ok(());
    }

    let conf = get_configuration(None)?;
    let addr = conf.leptos_options.site_addr;
    let leptos_options = conf.leptos_options;
    let schema = Schema::build(QueryRoot, MutationRoot, EmptySubscription).data(pool.clone()).limit_depth(12).limit_complexity(200).finish();
    let state = AppState { leptos_options: leptos_options.clone(), pool, schema, cookie_secure: std::env::var("COOKIE_SECURE").map(|v| v == "true").unwrap_or(false) };
    let routes = generate_route_list(App);

    async fn graphql_handler(State(state): State<AppState>, headers: HeaderMap, request: GraphQLRequest) -> GraphQLResponse {
        let token = auth::cookie_value(&headers, auth::SESSION_COOKIE);
        let user = if let Some(token) = token {
            sqlx::query_as::<_, graphql::User>("SELECT u.id, u.email, u.first_name, u.last_name, u.roles, u.active, u.created_at FROM users u JOIN sessions s ON s.user_id = u.id WHERE s.token_hash = $1 AND s.expires_at > now() AND u.active = true")
                .bind(auth::token_hash(&token)).fetch_optional(&state.pool).await.ok().flatten()
        } else { None };
        state.schema.execute(request.into_inner().data(RequestAuth { user, cookie_secure: state.cookie_secure })).await.into()
    }

    async fn graphiql() -> Html<String> { Html(GraphiQLSource::build().endpoint("/graphql").finish()) }
    async fn health() -> impl IntoResponse { (StatusCode::OK, "ok") }

    let app = Router::new()
        .route("/health", get(health))
        .route("/graphql", get(graphiql).post(graphql_handler))
        .leptos_routes(&state, routes, { let options = leptos_options.clone(); move || shell(options.clone()) })
        .fallback(leptos_axum::file_and_error_handler(shell))
        .layer(CompressionLayer::new())
        .layer(TraceLayer::new_for_http())
        .with_state(state);

    tracing::info!(%addr, "FluidWeb listening");
    let listener = tokio::net::TcpListener::bind(addr).await?;
    axum::serve(listener, app).await?;
    Ok(())
}

#[cfg(not(feature = "ssr"))]
pub fn main() {}
