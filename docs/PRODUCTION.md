# Production Deployment

## Prerequisites

- Docker and Docker Compose installed
- `.env` configured from `.env.production.example`
- `APP_KEY` generated
- `JWT_SECRET` generated
- Redis password set
- Database credentials configured

## Build

```bash
docker build -f docker/php/Dockerfile -t komunalka-api:latest .
```

Image uses multi-stage build: builder compiles extensions and installs dependencies, runtime contains only what's needed. OPcache enabled with `validate_timestamps=0`.

## Deploy

```bash
cp .env.production.example .env
# Edit .env with actual values

docker compose -f compose.prod.yml up -d

# Run migrations
docker compose -f compose.prod.yml exec app php artisan migrate --force
```

## Zero-downtime redeploy

```bash
docker compose -f compose.prod.yml build app
docker compose -f compose.prod.yml up -d --no-deps app
```

FrankenPHP receives `SIGINT` for graceful shutdown (`stop_signal: SIGINT` in compose). 30-second grace period allows in-flight requests to complete.

## Key production settings

| Setting | Value | Why |
|---------|-------|-----|
| `CACHE_STORE` | `redis` | Database too slow for Octane |
| `QUEUE_CONNECTION` | `redis` | Database too slow for Octane |
| `LOG_CHANNEL` | `stderr` | Container logs via `docker logs` |
| `APP_DEBUG` | `false` | Debug mode leaks memory in Octane |
| `OCTANE_MAX_REQUESTS` | `500` | Worker restarts after 500 requests to prevent memory leaks |
| `SESSION_SECURE_COOKIE` | `true` | HTTPS only |

## Octane configuration

`config/octane.php` has these listeners enabled:

- **FlushUploadedFiles** — cleans temp files after each request (required for spatie/laravel-medialibrary)
- **CollectGarbage** — forces GC when memory exceeds 50MB threshold

## Monitoring

```bash
# Application logs
docker compose -f compose.prod.yml logs -f app

# All service logs
docker compose -f compose.prod.yml logs -f

# Resource usage
docker stats

# Octane status
docker compose -f compose.prod.yml exec app php artisan octane:status

# OPcache stats
docker compose -f compose.prod.yml exec app php -r "print_r(opcache_get_status(false));"
```

## Storage

Media files (meter photos, avatars) stored in Docker volume `ka_storage_data_prod` mounted at `/var/www/storage/app`. Volume persists across container rebuilds.

To backup:
```bash
docker run --rm -v ka_storage_data_prod:/data -v $(pwd):/backup alpine tar czf /backup/storage-backup.tar.gz -C /data .
```

## Troubleshooting

### Container exits with code 137
OOM killed. Check memory limits in `compose.prod.yml` and PHP `memory_limit` (256M in production Dockerfile). Monitor with `docker stats`.

### Graceful shutdown takes too long
FrankenPHP has known SIGTERM issues. We use `SIGINT` instead (`stop_signal: SIGINT`). If still slow, check `stop_grace_period` (30s default).

### Stale OPcache after deploy
OPcache has `validate_timestamps=0` — code changes require container restart. Rebuild and restart the container.

### Memory growing between requests
Enabled `CollectGarbage` listener and `--max-requests=500`. If memory still grows, check for singleton services storing request data. Test with `--workers=1 --max-requests=2`.
