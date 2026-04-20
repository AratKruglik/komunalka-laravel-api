# Migrations & Queue Jobs

## Migration Conventions

- **Naming**: `create_meters_table`, `add_slug_to_posts_table`, `drop_legacy_field_from_users_table`
- Every migration must implement `down()` for reversibility
- Never modify existing migration files — always create a new one
- Column modifications must include all existing attributes (Laravel requires full column re-declaration on `change()`)

> For migration template: activate skill `laravel-actions-patterns`.

## Queue Jobs via Laravel Actions

Use `AsJob` trait from `lorisleiva/laravel-actions` — **no separate Job class needed**.

Key properties on every job class:
- `public int $tries = 3`
- `public array $backoff = [30, 60, 120]` — exponential backoff in seconds
- `public int $timeout = 120`
- Constructor accepts IDs, not model instances — keeps payload small

## Idempotency

Jobs must produce the same result when run multiple times. Use `updateOrCreate` / `firstOrCreate` in `handle()`.

## Unique Jobs

Implement `ShouldBeUnique` + `uniqueId()` to prevent duplicate jobs for the same resource.

Dispatch: `ProcessMeterReading::dispatch($meter)` or with delay: `::dispatch($meter)->delay(now()->addMinutes(5))`.

> For AsJob code examples (class anatomy, dispatch patterns, ShouldBeUnique): activate skill `laravel-actions-patterns`.
