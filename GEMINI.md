# Komunalka Laravel API

REST API для управління комунальними послугами. Побудований на PHP 8.4, Laravel 12, PostgreSQL. Всі команди МАЮТЬ виконуватись через `docker compose exec app`.

## Workflow Orchestration

### 1. Plan Node Default
- Enter plan mode for ANY non-trivial task (3+ steps or architectural decisions)
- If something goes sideways, STOP and re-plan immediately – don't keep pushing
- Use plan mode for verification steps, not just building
- Write detailed specs upfront to reduce ambiguity

### 2. Subagent Strategy
- Use subagents liberally to keep main context window clean
- Offload research, exploration, and parallel analysis to subagents
- For complex problems, throw more compute at it via subagents
- One tack per subagent for focused execution

### 3. Self-Improvement Loop
- After ANY correction from the user: update `docs/tasks-docs/lessons.md` with the pattern
- Write rules for yourself that prevent the same mistake
- Ruthlessly iterate on these lessons until mistake rate drops
- Review lessons at session start for relevant project

### 4. Verification Before Done
- Never mark a task complete without proving it works
- Diff behavior between main and your changes when relevant
- Ask yourself: "Would a staff engineer approve this?"
- Run tests, check logs, demonstrate correctness

### 5. Demand Elegance (Balanced)
- For non-trivial changes: pause and ask "is there a more elegant way?"
- If a fix feels hacky: "Knowing everything I know now, implement the elegant solution"
- Skip this for simple, obvious fixes – don't over-engineer
- Challenge your own work before presenting it

### 6. Autonomous Bug Fixing
- When given a bug report: just fix it. Don't ask for hand-holding
- Point at logs, errors, failing tests – then resolve them
- Zero context switching required from the user
- Go fix failing CI tests without being told how

## Task Management

1. **Plan First**: Write plan to `docs/tasks-docs/todo.md` with checkable items
2. **Verify Plan**: Check in before starting implementation
3. **Track Progress**: Mark items complete as you go
4. **Explain Changes**: High-level summary at each step
5. **Document Results**: Add review section to `docs/tasks-docs/todo.md`
6. **Capture Lessons**: Update `docs/tasks-docs/lessons.md` after corrections

## Core Principles

- **Simplicity First**: Make every change as simple as possible. Impact minimal code.
- **No Laziness**: Find root causes. No temporary fixes. Senior developer standards.
- **Minimal Impact**: Changes should only touch what's necessary. Avoid introducing bugs.

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
