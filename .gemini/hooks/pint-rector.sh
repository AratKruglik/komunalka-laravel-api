#!/usr/bin/env bash
set -u

PAYLOAD="$(cat || true)"

emit_skip() {
    printf '{"decision":"allow","reason":"%s"}\n' "$1"
    exit 0
}

if ! command -v docker >/dev/null 2>&1; then
    emit_skip "docker unavailable"
fi

if ! docker compose ps --services --filter status=running 2>/dev/null | grep -qx app; then
    emit_skip "app container not running"
fi

PATH_LINE=""
if command -v python3 >/dev/null 2>&1; then
    PATH_LINE="$(printf '%s' "$PAYLOAD" | python3 -c 'import json,sys
try:
    data=json.load(sys.stdin)
except Exception:
    sys.exit(0)
for key in ("tool_input","toolInput","parameters","input"):
    v=data.get(key)
    if isinstance(v,dict):
        for k in ("path","file_path","filePath","absolute_path","target","new_path"):
            val=v.get(k)
            if isinstance(val,str) and val:
                print(val); sys.exit(0)
' 2>/dev/null || true)"
fi

if [ -z "$PATH_LINE" ] || [[ "$PATH_LINE" != *.php ]]; then
    emit_skip "non-php edit or path unavailable"
fi

{
    docker compose exec -T app composer run pint:fix >/dev/null 2>&1
    docker compose exec -T app composer run rector:fix >/dev/null 2>&1
} &

printf '{"decision":"allow","reason":"pint+rector dispatched async"}\n'
exit 0
