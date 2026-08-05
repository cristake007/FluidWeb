# syntax=docker/dockerfile:1.11
FROM rust:1.97.1-slim-trixie AS build
WORKDIR /app
RUN apt-get update && apt-get install -y --no-install-recommends ca-certificates curl pkg-config libssl-dev nodejs npm && rm -rf /var/lib/apt/lists/*
RUN rustup target add wasm32-unknown-unknown && OPENSSL_NO_VENDOR=1 cargo install cargo-leptos --version 0.3.6 --locked
COPY package.json ./
RUN npm install
COPY . .
RUN npm run css:build && cargo leptos build --release

FROM debian:trixie-slim AS runtime
RUN apt-get update && apt-get install -y --no-install-recommends ca-certificates && rm -rf /var/lib/apt/lists/*
RUN useradd --system --uid 10001 --create-home fluidweb
WORKDIR /app
COPY --from=build /app/target/release/fluidweb /usr/local/bin/fluidweb
COPY --from=build /app/target/site /app/site
USER fluidweb
ENV LEPTOS_SITE_ROOT=/app/site LEPTOS_SITE_ADDR=0.0.0.0:3000 RUST_LOG=fluidweb=info,tower_http=info
EXPOSE 3000
ENTRYPOINT ["fluidweb"]
