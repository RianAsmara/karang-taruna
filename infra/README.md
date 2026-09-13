# Deploying RukunMuda to a VPS

Single-VPS deployment with Docker Compose. Everything here is production
only — the repository-root `docker-compose.yml` is local development and
must not be used on a server (it exposes Postgres, Redis and MinIO on the
host with throwaway credentials).

`docs/deployment.md` explains *why* each piece exists. This file is the
sequence you actually run.

## What you need first

- A VPS with 2 vCPU / 4 GB RAM and Docker installed. Choose a Jakarta
  region if one is available — these users are in Indonesia and latency
  is felt on every page.
- A domain with an **A record already pointing at the VPS**. Caddy
  requests a certificate on first boot; if DNS has not propagated, that
  request fails and Let's Encrypt rate-limits retries.
- An SMTP provider. Email is not optional here: verification links,
  password resets and dues reminders all go through it, and a user who
  never receives the verification email cannot use the app at all.

## First deploy

```bash
git clone <repo> /opt/rukunmuda && cd /opt/rukunmuda

cp infra/.env.prod.example infra/.env.prod
docker run --rm -v "$PWD/laravel:/app" -w /app php:8.4-cli php artisan key:generate --show
# paste the output into APP_KEY, then fill in every remaining blank
chmod 600 infra/.env.prod

docker compose -f infra/docker-compose.prod.yml --env-file infra/.env.prod up -d --build
```

The web container runs migrations and warms the config/route/view caches
on boot, so there is no separate migrate step.

Create the object storage bucket and make the theme-logo prefix publicly
readable — without this every organization's uploaded logo 403s:

```bash
docker compose -f infra/docker-compose.prod.yml --env-file infra/.env.prod \
  exec minio mc alias set local http://localhost:9000 "$AWS_ACCESS_KEY_ID" "$AWS_SECRET_ACCESS_KEY"
# then: mc mb local/rukunmuda && mc anonymous set download local/rukunmuda/themes
```

Verify:

```bash
infra/scripts/healthcheck.sh
```

## Every deploy after that

```bash
cd /opt/rukunmuda && git pull && infra/scripts/deploy.sh
```

`deploy.sh` dumps the database first, rebuilds, restarts the workers onto
the new image, and waits for `/up` to answer before reporting success.

## Backups — do this on day one

```bash
crontab -e
15 2 * * * /opt/rukunmuda/infra/scripts/pg-backup.sh >> /var/log/rukunmuda-backup.log 2>&1
```

Then prove it works, once, by hand:

```bash
infra/scripts/verify-restore.sh
```

It restores the newest dump into a throwaway database and checks that
organizations, memberships, transactions and reports all came back with
rows, then prints the net approved balance. **An untested backup is a
hope, not a backup** — and a dump that restores empty fails silently
until the day you need it.

Keep a copy off this machine. A backup on the same VPS does not survive
the failure mode most likely to destroy it.

## Error reporting

Create a Sentry project, put its DSN in `SENTRY_LARAVEL_DSN`, redeploy.
PII reporting is hard-disabled in `config/sentry.php` — events carry the
stack trace and nothing about who triggered it.

Without this a production failure is invisible until a member complains,
which for a volunteer treasurer usually means never.

## Checking on things

```bash
COMPOSE="docker compose -f infra/docker-compose.prod.yml --env-file infra/.env.prod"

$COMPOSE ps                       # is anything restarting in a loop?
$COMPOSE logs -f --tail=100 app
$COMPOSE logs --tail=50 queue     # a dead worker looks exactly like "nothing queued"
$COMPOSE exec app php artisan queue:failed
```

## Restoring from a backup

```bash
COMPOSE="docker compose -f infra/docker-compose.prod.yml --env-file infra/.env.prod"
$COMPOSE stop app queue scheduler
$COMPOSE exec -T postgres psql -U rukunmuda -d postgres -c \
  'DROP DATABASE rukunmuda; CREATE DATABASE rukunmuda;'
$COMPOSE exec -T postgres pg_restore -U rukunmuda -d rukunmuda --no-owner \
  < /var/backups/rukunmuda/rukunmuda-YYYY-MM-DD-HHMM.dump
$COMPOSE start app queue scheduler
```

## Moving to managed services

Nothing in the app depends on these containers specifically. To use a
managed Postgres, S3 or Redis, delete that service from
`docker-compose.prod.yml` and point the matching `.env.prod` variables at
the provider. Managed Postgres with point-in-time recovery is a real
upgrade over a nightly dump — up to 24 hours of financial records is a
lot to re-enter by hand.
