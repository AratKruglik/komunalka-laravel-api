# Docker Environment Commands

**All commands MUST run inside the Docker container.**

## PHP / Artisan

```bash
docker compose exec app php artisan make:action Domain/ActionName
docker compose exec app php artisan make:request Domain/RequestName
docker compose exec app php artisan make:model ModelName -m
docker compose exec app php artisan make:migration create_table_name_table
docker compose exec app php artisan migrate
docker compose exec app php artisan migrate:rollback
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan ide-helper:generate
```

## Code Quality

```bash
docker compose exec app ./vendor/bin/pint --dirty
docker compose exec app ./vendor/bin/pint
docker compose exec app ./vendor/bin/phpstan analyse --memory-limit=512M
docker compose exec app ./vendor/bin/rector process --dry-run
docker compose exec app ./vendor/bin/rector process
```

## Testing

```bash
docker compose exec app php artisan test --compact
docker compose exec app php artisan test --compact --parallel
docker compose exec app php artisan test --coverage
docker compose exec app php artisan test --mutate --covered-only --parallel --min=100
docker compose exec app php artisan test --compact --filter=testName
```

## Composer

```bash
docker compose exec app composer install
docker compose exec app composer require vendor/package
docker compose exec app composer run dev
docker compose exec app composer run pint:fix
docker compose exec app composer run rector:fix
```

## Frontend (pnpm, inside container)

```bash
docker compose exec app pnpm install
docker compose exec app pnpm run dev
docker compose exec app pnpm run build
docker compose exec app npx tsc --noEmit
```

> **NEVER run commands outside Docker** — all dependencies exist only in the container.
> **NEVER create Controllers** — this project uses the Laravel Actions pattern.
