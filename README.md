# FluidWeb

FluidWeb is an internal platform built with Symfony, Angular, FrankenPHP, PostgreSQL and Mercure.

## Structure

```text
backend/   Symfony API
frontend/  Angular application
docker/    Runtime configuration
```

## Development

Start the development profile:

```bash
docker compose --profile dev up --build --wait -d
```

Open `http://localhost`. FrankenPHP serves the public endpoint, sends `/api/*` to Symfony and proxies the remaining routes to Angular.

Useful commands:

```bash
docker compose --profile dev ps
docker compose --profile dev logs -f app-dev frontend-dev
docker compose --profile dev exec app-dev php bin/console
docker compose --profile dev down --remove-orphans
```

PostgreSQL and the Angular development server are available only inside the Docker network.

## Production image

Build and start the local production profile:

```bash
cp .env.example .env
docker compose --profile prod up --build --wait -d
```

The `app_prod` Docker target contains Symfony, its production dependencies and the compiled Angular application. Production secrets must be supplied through `.env` or the installer.

## Quality checks

GitHub Actions runs only when started manually. The workflow validates Composer, Symfony configuration, PHPStan, PHP-CS-Fixer, PHPUnit, Angular formatting, ESLint, build and tests.
