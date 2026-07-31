# FluidWeb

FluidWeb is an internal platform built as a server-rendered Symfony Web App with FrankenPHP, PostgreSQL and Mercure.

## Structure

```text
app/       Symfony Web App and API
docker/    Runtime configuration
```

The application uses Twig, Webpack Encore, SCSS, Stimulus and Symfony UX Turbo. Browser assets are built locally by the separate Node.js assets service.

The server-rendered web interface uses Symfony form authentication and sessions. The former Angular JSON authentication endpoints have been removed. `/api/v1` remains available for future integrations, but integration authentication is not implemented yet; it will use API keys or another dedicated mechanism, not user passwords or the web login endpoint.

## Development

Start the development profile:

```bash
docker compose --profile dev up --build --wait -d
```

Open `http://localhost`. FrankenPHP serves static assets directly and sends application routes to Symfony.

Useful commands:

```bash
docker compose --profile dev ps
docker compose --profile dev logs -f app-dev
docker compose --profile dev exec app-dev php bin/console
docker compose --profile dev down --remove-orphans
```

PostgreSQL is available only inside the Docker network.

## Production image

Build and start the local production profile:

```bash
cp .env.example .env
docker compose --profile prod up --build --wait -d
```

The `app_prod` Docker target contains the complete Symfony application, its production dependencies and Webpack Encore output compiled into `public/build/`. Node.js and `node_modules` are not included in the final image. Production secrets must be supplied through `.env` or the installer.

## Quality checks

GitHub Actions runs only when started manually. The workflow validates Composer, the reproducible frontend build, YAML, Twig, the service container, PHPStan, PHP-CS-Fixer, Doctrine migrations, PHPUnit, application routes and the production image.
