# Deployment

What it actually takes to run RukunMuda outside local development —
process model, required infrastructure, environment, backups, and a
go-live checklist. Local dev setup lives in the root `README.md`; this
doc is the production/staging complement to it, not a repeat of it.

## Process model

One Laravel app (web + API) serves both surfaces — there's no separate
backend service to deploy. Four long-running things, not one:

1. **PHP-FPM / web server** — serves web (Inertia) and `/api/v1/*`.
   `composer run dev`'s bundled `serve` is local-only; production needs
   a real web server (nginx/Apache) in front of PHP-FPM, not
   `artisan serve`.
2. **Queue worker** (`php artisan queue:work --tries=3`) — every
   notification, PDF/report generation, and other async job in
   `app/Jobs`/`app/Notifications` goes through the `redis` queue
   connection (`QUEUE_CONNECTION=redis`). Without a worker running,
   queued work silently piles up and nothing gets delivered — there's
   no synchronous fallback in production config.
3. **Scheduler** (`php artisan schedule:run` once a minute, via cron or
   a process supervisor) — drives `routes/console.php`'s
   `dues:remind-unpaid` (weekly) and any future scheduled command.
   Missing this doesn't error visibly; reminders just never go out.
4. **Vite build** (`npm run build`) — a one-time build step per deploy,
   not a running process. `npm run dev`'s HMR server is local-only.

Use a process supervisor (systemd, Supervisor, or your platform's
equivalent) for #2 and #3 so they restart on crash and on deploy — a
worker that silently died is indistinguishable from "nothing queued"
until someone asks why reminders stopped.

## Infrastructure

Same three services as local dev (`docker-compose.yml`), any managed
or self-hosted equivalent works in production:

- **PostgreSQL 16** — the database. `DB_CONNECTION=pgsql`.
- **Redis** — sessions, cache, and the queue driver
  (`SESSION_DRIVER`/`CACHE_STORE`/`QUEUE_CONNECTION=redis`). Losing
  Redis in production loses active sessions and queued jobs, not just
  a cache — size and back this up/monitor it accordingly, don't treat
  it as disposable the way a pure cache would be.
- **S3-compatible object storage** — MinIO locally,
  S3/DigitalOcean Spaces/Backblaze B2/etc. in production
  (`FILESYSTEM_DISK=s3` + the `AWS_*` variables). Holds financial
  evidence, documents, uploads, and theme logos.
- **SMTP** — Mailpit locally; any real SMTP provider (or Laravel's
  other supported mail drivers) in production for
  `dues:remind-unpaid`, report/vote/attendance notifications, and
  email verification.

## Environment — production-specific settings

Copy `laravel/.env.example`, then change these from their local
defaults (`.env.example`'s own comments flag most of this, called out
here so it isn't missed at go-live):

| Variable | Local default | Production |
| --- | --- | --- |
| `APP_ENV` | `local` | `production` |
| `APP_DEBUG` | `true` | `false` — leaking stack traces to end users is a real information disclosure, not just a style choice |
| `APP_URL` | `http://localhost:8000` | the real HTTPS origin — used to build every absolute URL this app generates: report share links, QR codes (`ReportQrCode`, attendance QR), notification links |
| `CORS_ALLOWED_ORIGINS` | `*` (open) | the exact web + mobile origins — `*` is fine for local-only development, never for a production API a real mobile client authenticates against |
| `SESSION_SECURE_COOKIE` | unset | `true`, once served over HTTPS (which production should always be) |
| `BCRYPT_ROUNDS` | `12` | fine as-is; don't lower it |
| `LOG_LEVEL` | `debug` | `warning` or `error` — `debug` in production is noisy and can leak more detail than intended into logs |
| `DB_*`, `REDIS_*`, `AWS_*`, `MAIL_*` | local container ports/creds | your real managed services |

`APP_KEY` must be generated once (`php artisan key:generate`) and then
**kept stable** — rotating it invalidates every existing session,
Sanctum token, and anything encrypted at rest.

## Deploy steps (each release)

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force      # --force: skip the "are you sure" prompt in non-interactive CI/CD
php artisan config:cache
php artisan route:cache
php artisan view:cache
# restart PHP-FPM, the queue worker, and anything supervising the scheduler
```

`config:cache`/`route:cache` are safe here because production
`.env` values don't change between requests — don't run them in local
development, where `.env` is edited frequently (a stale config cache
silently ignores new `.env` values until the next `config:cache`).

## CI

`.github/workflows/ci.yml` runs on every push to `main` and every pull
request: Pint, PHPStan (level 7), the full Pest suite (against
SQLite, matching `phpunit.xml`), a separate job that runs every
migration against a real throwaway Postgres container (catches
Postgres-specific schema issues SQLite's own quirks would hide),
ESLint, `tsc --noEmit`, the Vitest suite, and a production frontend
build. All of it must pass before merging — this is the same bar
every change in this repo has been held to throughout development
(Pint/PHPStan/Pest/tsc/ESLint/`vite build`, verified after each
feature area, not just at the end), now enforced automatically rather
than by discipline alone. CI does **not** deploy anything on its own —
wire a deploy step to a hosting platform's own pipeline once one is
chosen; this repo doesn't assume one.

## Backups

Two things actually need backing up — everything else (Redis
sessions/cache/queue, the Vite build) is disposable/regeneratable.

**PostgreSQL** — the single source of truth for every domain in this
app (finance, members, events, reports — see `docs/security.md` and
the master prompt's "source of truth" section). A daily
`pg_dump`, retained on a rolling window, is the minimum:

```bash
pg_dump -Fc -h $DB_HOST -U $DB_USERNAME -d $DB_DATABASE > "rukunmuda-$(date +%F).dump"
```

Restore with `pg_restore -d $DB_DATABASE rukunmuda-2026-08-28.dump`.
Prefer your Postgres host's own managed point-in-time-recovery/
snapshot feature if one is available — a nightly dump alone means up
to 24 hours of potential data loss (financial transactions,
attendance, votes) in the worst case.

**Object storage** — financial evidence, documents, and uploaded logos
(`FinancialTransactionAttachment`, `Document`, `Upload`,
`OrganizationTheme`'s logo files) live only in S3-compatible storage,
not the database. Most S3-compatible providers offer built-in
versioning/replication; if self-hosting MinIO, mirror the bucket
elsewhere on a schedule:

```bash
mc mirror --overwrite local/rukunmuda s3-backup/rukunmuda
```

Losing this bucket with no backup means every uploaded receipt,
transfer proof, and document is gone — treat it with the same care as
the database, not as an afterthought.

## Go-live checklist

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, real `APP_URL` (HTTPS)
- [ ] `CORS_ALLOWED_ORIGINS` set to the real web/mobile origins, not `*`
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] Real Postgres/Redis/S3 credentials, not the local `docker-compose.yml` defaults
- [ ] `APP_KEY` generated and stored somewhere durable (not regenerated on redeploy)
- [ ] Queue worker running under a supervisor, not a one-off terminal
- [ ] Scheduler (`schedule:run`) wired to cron/a supervisor
- [ ] Daily Postgres backup configured and **restore tested at least once** — an untested backup is a hope, not a backup
- [ ] Object storage bucket has versioning or a mirror/backup target
- [ ] `mc anonymous set download` applied to the theme-logo prefix in production's bucket too (see README's local-dev note) — otherwise every org's uploaded logo 403s
- [ ] CI green on the commit being deployed
