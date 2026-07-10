#!/bin/sh
set -e

if [ "$PROCESS" = "app" ]; then
    if [ ! -e /var/www/public/storage ]; then
        php artisan storage:link || true
    fi
fi

exec "$@"
