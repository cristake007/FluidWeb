# FluidWeb — Rust rewrite

Full-stack Rust foundation for the FluidWeb internal operations platform.

## Stack

- Leptos 0.8 with SSR and hydration
- Rust/UI source-owned components and Tailwind CSS 4
- Axum 0.8
- async-graphql 7
- SQLx 0.9 and PostgreSQL 18
- Argon2 password hashing and database-backed session cookies

## Development

```bash
cp .env.example .env
docker compose -f compose.dev.yaml up --build
```

Open `http://SERVER_IP:3000`. GraphiQL is available at `/graphql` during this foundation stage.

## Create the first administrator

```bash
docker compose -f compose.dev.yaml exec app cargo run --features ssr -- create-admin admin@example.com Cristian Popa 'replace-this-password'
```

## Production

```bash
cp .env.example .env
# Replace all example secrets.
docker compose -f compose.prod.yaml up -d --build
```

The production app binds to `127.0.0.1:3000` for a host reverse proxy. PostgreSQL is private to the Compose network.

## Current scope

This branch establishes the complete runtime architecture and the first authentication vertical slice. The remaining FluidWeb business modules must be migrated incrementally rather than mechanically translated from PHP.
