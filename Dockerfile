# syntax=docker/dockerfile:1.11
FROM rust:1.97.1-slim-trixie AS build

ARG TARGETARCH
ARG CARGO_LEPTOS_VERSION=0.3.6

WORKDIR /app

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        gzip \
        libssl-dev \
        nodejs \
        npm \
        pkg-config \
    && rm -rf /var/lib/apt/lists/*

RUN set -eux; \
    rustup target add wasm32-unknown-unknown; \
    case "${TARGETARCH}" in \
        amd64) \
            archive="cargo-leptos-x86_64-unknown-linux-gnu.tar.gz"; \
            checksum="fbd1013f9543db0cde37dbf7bf1661d3d92b499d6337165bd625da279c82e022" \
            ;; \
        arm64) \
            archive="cargo-leptos-aarch64-unknown-linux-gnu.tar.gz"; \
            checksum="43de7e1b6adc00dfc14504a101e1a9da066e28187b1bbe30d93bdb9632720eb4" \
            ;; \
        *) \
            echo "Unsupported Docker architecture: ${TARGETARCH}" >&2; \
            exit 1 \
            ;; \
    esac; \
    curl --fail --location --silent --show-error \
        "https://github.com/leptos-rs/cargo-leptos/releases/download/v${CARGO_LEPTOS_VERSION}/${archive}" \
        --output /tmp/cargo-leptos.tar.gz; \
    echo "${checksum}  /tmp/cargo-leptos.tar.gz" | sha256sum --check --strict; \
    mkdir /tmp/cargo-leptos; \
    tar --extract --gzip --file /tmp/cargo-leptos.tar.gz \
        --directory /tmp/cargo-leptos \
        --strip-components=1 \
        "${archive%.tar.gz}/cargo-leptos"; \
    install -m 0755 /tmp/cargo-leptos/cargo-leptos /usr/local/cargo/bin/cargo-leptos; \
    cargo-leptos --version; \
    rm -rf /tmp/cargo-leptos /tmp/cargo-leptos.tar.gz

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
