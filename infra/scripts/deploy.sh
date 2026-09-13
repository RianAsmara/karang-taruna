#!/usr/bin/env bash
# Deploy the current checkout. Run from anywhere on the VPS:
#   infra/scripts/deploy.sh
#
# Not zero-downtime — a few seconds of 502 while containers swap. That is
# the right trade for a single-VPS deployment serving community
# organizations; buying zero-downtime here means a load balancer and two
# app hosts, which is not the constraint this project has.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
COMPOSE="docker compose -f $ROOT/infra/docker-compose.prod.yml --env-file $ROOT/infra/.env.prod"

cd "$ROOT"

echo "==> Backing up the database before migrating"
# A migration is the one deploy step that cannot be undone by redeploying
# the previous image. Take the dump first, every time.
"$ROOT/infra/scripts/pg-backup.sh"

echo "==> Building"
$COMPOSE build

echo "==> Starting (migrations run in the web container's entrypoint)"
$COMPOSE up -d

echo "==> Restarting workers onto the new image"
# Without this the queue worker keeps executing the previous release's
# job classes until it happens to recycle.
$COMPOSE restart queue scheduler

echo "==> Waiting for the app to answer"
"$ROOT/infra/scripts/healthcheck.sh"

echo "==> Deployed"
$COMPOSE ps
