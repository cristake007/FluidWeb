# syntax=docker/dockerfile:1

FROM dunglas/frankenphp:1-php8.5 AS frankenphp_base

SHELL ["/bin/bash", "-euxo", "pipefail", "-c"]

WORKDIR /app

RUN <<-EOF
	apt-get update
	apt-get install -y --no-install-recommends \
		file \
		git
	install-php-extensions \
		@composer \
		apcu \
		intl \
		opcache \
		pdo_pgsql \
		zip
	rm -rf /var/lib/apt/lists/*
EOF

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PHP_INI_SCAN_DIR=":$PHP_INI_DIR/app.conf.d"

COPY --link docker/php/common.ini $PHP_INI_DIR/app.conf.d/10-app.ini
COPY --link --chmod=755 docker/frankenphp/docker-entrypoint.sh /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]

HEALTHCHECK --start-period=60s CMD php -r 'exit(false === @file_get_contents("http://localhost:2019/metrics", context: stream_context_create(["http" => ["timeout" => 5]])) ? 1 : 0);'

FROM frankenphp_base AS app_dev

ENV APP_ENV=dev
ENV XDEBUG_MODE=off

RUN <<-EOF
	mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
	install-php-extensions xdebug
	git config --system --add safe.directory /app
EOF

COPY --link docker/php/dev.ini $PHP_INI_DIR/app.conf.d/20-app.dev.ini
COPY --link docker/frankenphp/Caddyfile.dev /etc/frankenphp/Caddyfile

CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile", "--watch"]

FROM frankenphp_base AS app_prod_builder

ENV APP_ENV=prod

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY --link docker/php/prod.ini $PHP_INI_DIR/app.conf.d/20-app.prod.ini
COPY --link app/composer.* app/symfony.* ./
RUN composer install --no-cache --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress

COPY --link --exclude=var app/ ./

RUN <<-EOF
	mkdir -p var/cache var/log var/share
	composer dump-autoload --classmap-authoritative --no-dev
	composer dump-env prod
	composer run-script --no-dev post-install-cmd
	php bin/console asset-map:compile
	chmod +x bin/console
	chmod -R g=u var
	sync
EOF

RUN <<-'EOF'
	apt-get update
	apt-get install -y --no-install-recommends libtree
	mkdir -p /tmp/libs
	BINARIES=(frankenphp php file)
	for target in $(printf '%s\n' "${BINARIES[@]}" | xargs -I{} which {}) \
		$(find "$(php -r 'echo ini_get("extension_dir");')" -maxdepth 2 -name "*.so"); do
		libtree -pv "$target" 2>/dev/null | grep -oP '(?:── )\K/\S+(?= \[)' | while IFS= read -r lib; do
			[ -f "$lib" ] && cp -n "$lib" /tmp/libs/
		done
	done
	rm -rf /var/lib/apt/lists/*
EOF

FROM debian:13-slim AS app_prod

SHELL ["/bin/bash", "-euxo", "pipefail", "-c"]

ENV APP_ENV=prod
ENV PHP_INI_SCAN_DIR=":/usr/local/etc/php/app.conf.d"
ENV OPENSSL_CONF=/etc/ssl/openssl.cnf
ENV XDG_CONFIG_HOME=/config
ENV XDG_DATA_HOME=/data
ENV SSL_CERT_FILE=/etc/ssl/certs/ca-certificates.crt

COPY --from=app_prod_builder /usr/local/bin/frankenphp /usr/local/bin/frankenphp
COPY --from=app_prod_builder /usr/local/bin/php /usr/local/bin/php
COPY --from=app_prod_builder /usr/local/bin/docker-php-entrypoint /usr/local/bin/docker-php-entrypoint
COPY --from=app_prod_builder /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --from=app_prod_builder /tmp/libs /usr/lib
COPY --from=app_prod_builder /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d
COPY --from=app_prod_builder /usr/local/etc/php/php.ini /usr/local/etc/php/php.ini
COPY --from=app_prod_builder /usr/local/etc/php/app.conf.d /usr/local/etc/php/app.conf.d
COPY --from=app_prod_builder /etc/ssl/certs/ca-certificates.crt /etc/ssl/certs/ca-certificates.crt
COPY --from=app_prod_builder /etc/ssl/openssl.cnf /etc/ssl/openssl.cnf
COPY --from=app_prod_builder /usr/bin/file /usr/bin/file
COPY --from=app_prod_builder /usr/lib/file/magic.mgc /usr/lib/file/magic.mgc

COPY --link --exclude=var --from=app_prod_builder /app /app
COPY --chown=www-data:0 --from=app_prod_builder /app/var /app/var

COPY --link docker/frankenphp/Caddyfile.prod /etc/frankenphp/Caddyfile
COPY --link --chmod=755 docker/frankenphp/docker-entrypoint.sh /usr/local/bin/docker-entrypoint

RUN <<-EOF
	mkdir -p /data/caddy /config/caddy
	chown -R www-data:www-data /data /config
	chmod g=u /app/var
	find / -perm /6000 -type f -exec chmod a-s {} + 2>/dev/null || true
EOF

USER www-data
WORKDIR /app

ENTRYPOINT ["docker-entrypoint"]

HEALTHCHECK --start-period=60s CMD php -r 'exit(false === @file_get_contents("http://localhost:2019/metrics", context: stream_context_create(["http" => ["timeout" => 5]])) ? 1 : 0);'

CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
