# Komunalka API

REST API for managing utility services: meters, readings, billing, addresses, and user accounts. Migrated from .NET to Laravel.

## Tech Stack

- **PHP 8.4** / Laravel 12 / FrankenPHP (Octane)
- **PostgreSQL 17** / Redis 7.2
- **JWT Authentication** with OAuth (Google, GitHub)
- **Pest 4** (361 tests) / PHPStan level 5 / Laravel Pint

## Requirements

- Docker & Docker Compose

## Local Setup

1. Clone the repository:

```bash
git clone <repo-url> && cd komunalka-laravel-api
```

2. Copy environment file and configure it:

```bash
cp .env.example .env
```

Edit `.env` — set database credentials, Redis password, JWT secret, and OAuth keys as needed. The defaults work with Docker Compose services out of the box.

3. Start all services:

```bash
docker compose up -d
```

This starts: **app** (FrankenPHP), **db** (PostgreSQL), **db-test** (test database), **redis**, **workers** (queue), **schedule** (cron), **websockets** (Reverb), **mailpit** (email testing).

4. Run initial setup inside the container:

```bash
docker compose exec app composer setup
```

This will: install dependencies, generate app key, run migrations, install npm packages, and build frontend assets.

5. Generate JWT secret:

```bash
docker compose exec app php artisan jwt:secret
```

The API is now available at **https://localhost**.

## Services

| Service | URL | Purpose |
|---------|-----|---------|
| API | https://localhost | Main application |
| Mailpit | http://localhost:8025 | Email testing dashboard |
| WebSockets | ws://localhost:8080 | Real-time events (Reverb) |
| PostgreSQL | localhost:5432 | Database |
| Redis | localhost:6379 | Cache & queues |

## Local HTTPS

HTTPS works out of the box via Caddy's `tls internal` directive, which generates a self-signed certificate from Caddy's local CA. HTTP requests to `http://localhost` are automatically redirected to HTTPS.

The `.env` file includes `OCTANE_HTTPS=true` so Laravel generates correct `https://` URLs.

To trust the local CA certificate on macOS (removes browser warnings):

```bash
docker compose cp app:/data/caddy/pki/authorities/local/root.crt ./caddy-root.crt
sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain ./caddy-root.crt
rm ./caddy-root.crt
```

To verify HTTPS is working:

```bash
curl -k https://localhost/health
```

## API Modules

All endpoints are versioned under `/api/v1/`.

| Module | Endpoints | Description |
|--------|-----------|-------------|
| **Auth** | 22 | Registration, login, JWT refresh/revoke, OAuth (Google, GitHub) |
| **Address** | 11 | Address CRUD, types, regions, primary toggle |
| **Meter** | 21 | Meters, batch readings, photo uploads, legacy endpoints |
| **Billing** | 7 | Service providers, tariffs, cost calculation |
| **Export** | 2 | CSV/PDF export of meter readings |
| **Shared** | 3 | Currencies, utility types |
| **Health** | 2 | `/health`, `/health/ready` |

## Running Tests

```bash
docker compose exec app php artisan test --compact --parallel
```

Filter by group:

```bash
docker compose exec app php artisan test --compact --filter=EndToEnd
docker compose exec app php artisan test --compact --filter=ApiCompatibility
docker compose exec app php artisan test --compact --filter=Performance
```

## Code Quality

```bash
# Code style
docker compose exec app vendor/bin/pint --dirty --format agent

# Static analysis
docker compose exec app vendor/bin/phpstan analyse --memory-limit=512M
```

## Project Structure

```
app/                    Base controller, repository contracts
Modules/
  Address/              Address CRUD, types, regions
  Auth/                 JWT auth, OAuth, user management
  Billing/              Service providers, tariffs
  Export/               CSV/PDF export
  Meter/                Meters, readings, photos
  Shared/               Currencies, utility types
tests/
  Feature/
    EndToEnd/           Cross-module integration flows
    ApiCompatibility/   Response format verification
    EndpointCoverage/   Edge cases, security
    Performance/        N+1 prevention, queue tests
```

Each module follows the structure: `Actions/`, `DTOs/`, `Http/Controllers/`, `Http/Requests/`, `Http/Resources/`, `Models/`, `Repositories/`, `routes/`, `database/`, `tests/`.
