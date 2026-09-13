#!/usr/bin/env bash
# Restore the newest dump into a throwaway database and check that the
# financial records actually came back.
#
#   infra/scripts/verify-restore.sh [path/to/dump]
#
# An untested backup is a hope, not a backup. Run this after the first
# backup, and on a schedule after that — a dump that restores empty fails
# silently otherwise, and you find out on the day it matters.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
# shellcheck disable=SC1091
set -a; . "$ROOT/infra/.env.prod"; set +a

COMPOSE="docker compose -f $ROOT/infra/docker-compose.prod.yml --env-file $ROOT/infra/.env.prod"
DIR="${BACKUP_DIR:-/var/backups/rukunmuda}"
DUMP="${1:-$(ls -t "$DIR"/rukunmuda-*.dump 2>/dev/null | head -1)}"
SCRATCH="rukunmuda_restore_check"

[ -n "$DUMP" ] && [ -f "$DUMP" ] || { echo "No dump found in $DIR" >&2; exit 1; }
echo "==> Verifying $DUMP"

cleanup() {
    $COMPOSE exec -T postgres psql -U "$DB_USERNAME" -d postgres \
        -c "DROP DATABASE IF EXISTS $SCRATCH;" >/dev/null 2>&1 || true
}
trap cleanup EXIT

cleanup
$COMPOSE exec -T postgres psql -U "$DB_USERNAME" -d postgres -c "CREATE DATABASE $SCRATCH;" >/dev/null
$COMPOSE exec -T postgres pg_restore -U "$DB_USERNAME" -d "$SCRATCH" --no-owner < "$DUMP" >/dev/null

query() {
    $COMPOSE exec -T postgres psql -U "$DB_USERNAME" -d "$SCRATCH" -tAc "$1" | tr -d '[:space:]'
}

FAILED=0
# Row counts prove the restore carried data, not just schema. These four
# tables are the ones whose loss would be unrecoverable by re-entry.
for table in organizations organization_memberships financial_transactions financial_reports; do
    COUNT="$(query "SELECT count(*) FROM $table;")"
    echo "    $table: $COUNT rows"
    if [ "$COUNT" = "0" ]; then
        echo "    WARNING: $table restored empty" >&2
        FAILED=1
    fi
done

# The balance must recompute from the restored rows — a dump that
# restores rows but loses the APPROVED/PENDING distinction would
# reconcile to the wrong number while still looking healthy.
# TRANSFER is excluded deliberately: it moves money between accounts of
# the same organization and nets to zero org-wide (see
# FinancialAccount::balance(), which nets it per account).
BALANCE="$(query "SELECT COALESCE(SUM(CASE WHEN transaction_type = 'INCOME' THEN amount ELSE -amount END), 0) FROM financial_transactions WHERE status = 'APPROVED' AND transaction_type <> 'TRANSFER';")"
echo "    net approved balance (IDR, transfers excluded): $BALANCE"

if [ "$FAILED" = "1" ]; then
    echo "==> RESTORE VERIFICATION FAILED" >&2
    exit 1
fi
echo "==> Restore verified"
