# Architecture Patterns

## Business Logic

- **Laravel Actions** (`lorisleiva/laravel-actions`) — all business logic in Action classes
- **Service Layer**: implemented via Action classes (no separate service classes)
- **Repository Pattern**: not used — rely on Eloquent models directly
- **Modules**: `nwidart/laravel-modules` — domain modules in `Modules/{Name}/` (Shared, Auth, Address, Billing, Meter, Export)

## Frontend

- **Inertia.js v3** with **React 19** (TypeScript) — SPA via server-driven routing
- **Tailwind CSS v4** + **Tailwind Variants** — component styling with `tv()` variant API
- Features organized by modules (Auth, Shared, Address, Meter, Billing, Export)
- Shared props (auth, flash) accessed via `usePage<T>()`, never re-passed

## Database

- Every DB structure change → new migration
- Every DB data change → update seeder + factory
- Prefer Eloquent models over raw queries (`DB::` facade)
- Prefer Eloquent relationships over manual joins
- Prefer Eloquent eager loading over lazy loading (N+1 prevention)
- Prefer Eloquent pagination, scopes, soft deletes over raw alternatives

## API

- Eloquent API Resources with JSON:API format (see recent migration commits)
- Named routes + `route()` helper everywhere (Ziggy on frontend)

## Performance

- **Laravel Octane** with **FrankenPHP** — high-performance application server
- **Redis** — caching, sessions, queue management
- **PostgreSQL 17** with proper indexing
- **Reverb** — real-time websockets (already in stack)

## Development Tools

- **Telescope** — debugging assistant (enabled in testing)
- **Log Viewer** — web-based log viewing
- **IDE Helpers** — auto-generated (`php artisan ide-helper:generate`)
- **Xdebug** — available in Docker development environment
- **Laravel Boost** — MCP tools for doc search, schema, routes, tinker
