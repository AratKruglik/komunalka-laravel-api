#!/bin/sh
set -e

if [ "$PROCESS" = "app" ]; then
    if [ ! -e /var/www/public/storage ]; then
        php artisan storage:link || true
    fi

    if [ -x /usr/local/bin/playwright-chromium-shim.sh ]; then
        /usr/local/bin/playwright-chromium-shim.sh || echo "playwright-chromium-shim failed; browser tests may not start" >&2
    fi
fi

exec "$@"
