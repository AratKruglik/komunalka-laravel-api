# Komunalka Laravel API

REST API для управління комунальними послугами. Побудований на PHP 8.4, Laravel 12, PostgreSQL. Всі команди МАЮТЬ виконуватись через `docker compose exec app`.

## Repository structure

- `app/` — Models, Controllers, Providers, Form Requests, Jobs, Policies
- `bootstrap/` — `app.php` (middleware, exceptions, routing), `providers.php`
- `config/` — Configuration files
- `database/` — Migrations, factories, seeders
- `routes/` — `web.php`, `console.php`, API route files
- `tests/` — Pest 4 tests (`Feature/`, `Unit/`)
- `resources/` — Views, CSS, JS
- `public/` — Web entry point, static assets

## Tech stack

- **Runtime:** PHP 8.4 (FrankenPHP/Octane)
- **Database:** PostgreSQL 17
- **Caching/Queues:** Redis
- **Testing:** Pest 4, PHPUnit 12
- **Code style:** Laravel Pint
- **Dev tools:** Docker Compose, Boost (MCP)

## Setup

1. Start containers: `docker compose up -d`
2. Run setup inside container: `docker compose exec app composer setup`

- Never use `env()` outside config files — always use `config('key')`

## Development

```bash
docker compose up -d
```

Starts server (FrankenPHP), queue worker, scheduler, and reverb. 
- API: `http://localhost`
- Mailpit: `http://localhost:8025`

If you see `ViteException: Unable to locate file in Vite manifest` — run `docker compose exec app npm run build`.

## Testing

Activate `pest-testing` skill every time you work with tests.

```bash
docker compose exec app php artisan test --compact
docker compose exec app php artisan test --compact --filter=testName
```

- Create tests: `docker compose exec app php artisan make:test --pest {name}` (feature) or `--pest --unit` (unit). Most tests should be feature tests.
- Use model factories with custom states. Follow existing `$this->faker` vs `fake()` conventions.
- Do NOT delete tests without approval.

## Code style

### PHP

- Always use curly braces for control structures, even single-line
- Explicit return types and parameter type hints on all methods
- Constructor property promotion: `public function __construct(public GitHub $github) { }`
- No empty zero-parameter constructors (unless private)
- Enum keys in TitleCase
- PHPDoc blocks over inline comments; array shape type definitions where appropriate

### Formatting

```bash
docker compose exec app vendor/bin/pint --dirty --format agent
```

Run before finalizing changes.

## Architecture

### Laravel 12 structure

- Middleware, exceptions, routing configured in `bootstrap/app.php`
- Service providers in `bootstrap/providers.php`
- Console commands auto-discovered from `app/Console/Commands/`
- No `app/Http/Kernel.php` or `app/Console/Kernel.php`

### Scaffolding

- Use `docker compose exec app php artisan make:*` commands with `--no-interaction` flag
- Generic PHP classes: `docker compose exec app php artisan make:class`

### Database & Eloquent

- Prefer `Model::query()` over `DB::`; use eager loading to prevent N+1
- Relationship methods with return type hints; query builder for complex operations
- Column modifications must include all existing attributes
- Eager loading supports native `limit()`: `$query->latest()->limit(10)`
- Model casts via `casts()` method (not `$casts` property) — follow existing conventions
- New models: create factories and seeders too

### Controllers & validation

- Form Request classes for validation (not inline). Include rules and custom error messages.
- Check sibling Form Requests for array vs string rule convention.

### API

- Eloquent API Resources with API versioning (unless existing routes differ)
- Named routes with `route()` for URL generation

### Auth & queues

- Built-in Laravel auth features (gates, policies, Sanctum)
- `ShouldQueue` interface for time-consuming jobs

## Security

- Never hardcode secrets. Use `.env` (gitignored) and `config()` to access values.
- Do not log passwords, tokens, or secrets.

## Git workflow

- Branch: `feature/<short-kebab>` or `fix/<short-kebab>`
- Commit messages: imperative mood, e.g. `fix: handle null customer id`
- Before PR: tests and Pint must pass
- Do not create documentation files unless explicitly requested

## Do not

- Create new root-level directories without approval
- Change dependencies (`composer.json`, `package.json`) without approval
- Modify `bootstrap/app.php` routing config without understanding current setup
- Create verification scripts when tests cover the functionality

## MCP Tools (Laravel Boost)

This project uses Laravel Boost as an MCP server. Configuration is in `.mcp.json`. Available tools (they run inside `app` service):

- `search-docs` — search version-specific Laravel/package docs before code changes. Use broad, topic-based queries without package names.
- `database-schema` — inspect table structure before writing migrations or models
- `database-query` — read-only database queries
- `tinker` — execute PHP to debug or query Eloquent directly
- `list-artisan-commands` — verify artisan command parameters
- `get-absolute-url` — generate correct project URLs
- `browser-logs` — read recent browser errors/exceptions

## Behaviour

- Be concise — focus on what matters
- Follow existing code conventions; check sibling files before creating/editing
- Descriptive names: `isRegisteredForDiscounts`, not `discount()`
- Reuse existing components before creating new ones
- Do not create verification scripts when tests prove functionality works
