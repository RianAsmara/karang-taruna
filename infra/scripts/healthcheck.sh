#!/usr/bin/env bash
# Poll the app's health endpoint until it answers, or fail loudly.
# Laravel ships /up; it returns 200 only once the framework boots, so it
# catches a bad APP_KEY or an unreachable database, not just a live port.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
# shellcheck disable=SC1091
set -a; . "$ROOT/infra/.env.prod"; set +a

URL="${APP_URL:-https://localhost}/up"
DEADLINE=$((SECONDS + 90))

until curl -fsS --max-time 5 "$URL" >/dev/null 2>&1; do
    if (( SECONDS > DEADLINE )); then
        echo "FAILED: $URL did not answer within 90s" >&2
        echo "Check: docker compose -f infra/docker-compose.prod.yml logs --tail=50 app caddy" >&2
        exit 1
    fi
    sleep 3
done

echo "OK: $URL is answering"
