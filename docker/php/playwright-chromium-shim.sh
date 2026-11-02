#!/bin/sh
# Playwright downloads a glibc chrome-headless-shell that cannot run on Alpine (musl),
# so its executable is replaced with a launcher for the apk Chromium. This runs at
# container start because node_modules, and with it the Playwright revision, only
# exists on the mounted source volume.
set -e

PLAYWRIGHT=/var/www/node_modules/.bin/playwright
[ -x "$PLAYWRIGHT" ] || exit 0

plan=$("$PLAYWRIGHT" install --dry-run chromium-headless-shell)
dir=$(printf '%s\n' "$plan" | awk '/Install location:/ { print $3; exit }')
archive=$(printf '%s\n' "$plan" | awk '/Download url:/ { print $3; exit }')
binary="$dir/$(basename "$archive" .zip)/chrome-headless-shell"

mkdir -p "$(dirname "$binary")"
printf '#!/bin/sh\nexec /usr/lib/chromium/chromium-launcher.sh --headless=new --no-sandbox "$@"\n' > "$binary"
chmod +x "$binary"
touch "$dir/INSTALLATION_COMPLETE" "$dir/DEPENDENCIES_VALIDATED"
