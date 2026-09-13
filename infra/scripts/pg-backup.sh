#!/usr/bin/env bash
# Daily PostgreSQL dump. Wire into the VPS's crontab:
#   15 2 * * * /path/to/rukunmuda/infra/scripts/pg-backup.sh >> /var/log/rukunmuda-backup.log 2>&1
#
# This is the source of truth for every financial record in the app. A
# nightly dump alone still risks up to 24h of loss — if the Postgres host
# offers point-in-time recovery, prefer it and keep this as the offsite copy.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
# shellcheck disable=SC1091
set -a; . "$ROOT/infra/.env.prod"; set +a

COMPOSE="docker compose -f $ROOT/infra/docker-compose.prod.yml --env-file $ROOT/infra/.env.prod"
DIR="${BACKUP_DIR:-/var/backups/rukunmuda}"
RETAIN="${BACKUP_RETAIN_DAYS:-14}"
STAMP="$(date +%F-%H%M)"
FILE="$DIR/rukunmuda-$STAMP.dump"

mkdir -p "$DIR"

# -Fc (custom format) so pg_restore can do a selective or parallel restore;
# a plain SQL dump cannot.
$COMPOSE exec -T postgres \
    pg_dump -Fc -U "$DB_USERNAME" -d "$DB_DATABASE" > "$FILE"

# A dump that exists but is truncated is the failure mode that matters —
# it looks like a backup right up to the moment you need it.
if ! pg_restore --list "$FILE" >/dev/null 2>&1; then
    echo "FAILED: $FILE is not a readable dump" >&2
    rm -f "$FILE"
    exit 1
fi

SIZE="$(du -h "$FILE" | cut -f1)"
echo "$(date -Is) backed up $FILE ($SIZE)"

# Object storage holds the receipts and documents the database only
# references. Losing it loses every uploaded proof of payment.
if command -v mc >/dev/null 2>&1 && [ -n "${BACKUP_S3_TARGET:-}" ]; then
    mc mirror --overwrite --remove "local/${AWS_BUCKET}" "$BACKUP_S3_TARGET"
    echo "$(date -Is) mirrored bucket ${AWS_BUCKET} to $BACKUP_S3_TARGET"
else
    echo "$(date -Is) WARNING: object storage not mirrored (set BACKUP_S3_TARGET and configure mc)" >&2
fi

find "$DIR" -name 'rukunmuda-*.dump' -mtime "+$RETAIN" -delete
