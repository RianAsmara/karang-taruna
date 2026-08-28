# RukunMuda

Platform manajemen komunitas untuk organisasi pemuda Indonesia (Karang
Taruna, Pemuda Kampung, Pemuda RT/RW, dan komunitas sejenis) — mengelola
anggota, kegiatan, kas, iuran, transparansi keuangan, kehadiran, voting,
inventaris, sponsor, dokumen, dan pengumuman.

Prinsip produk: **Transparan · Kolaboratif · Sederhana · Akuntabel**.

## Status

**Laravel foundation, finance, transparency, and the API (Phases 1–5)
are complete.** Phase 6 (mobile) is well underway — most of the
member-facing design screens are built and wired to the live API,
following the mobile design's own build order. `docs/next-up.md` is the
live, current source of truth for exactly what's done and what's next;
`docs/roadmap.md` and `CLAUDE.md` have the full phase-by-phase plan and
working rules.

## Structure

```
rukunmuda/
├── laravel/            # Laravel 13 app — web (Inertia) + REST API
├── mobile/              # React Native + Expo client (Phase 6)
├── docs/                # architecture, domain model, database, api, security,
│                        #   transparency, roadmap, decisions, next-up
├── docker-compose.yml   # postgres (5433), redis (6380), minio, mailpit
├── infra/               # deployment/infra config (added when needed)
└── CLAUDE.md            # governing instructions for this project
```

`infra/` doesn't exist yet — that starts in Phase 8.

## Local development

### Backend

```bash
cd rukunmuda
docker compose up -d          # postgres, redis, minio, mailpit
cd laravel
cp .env.example .env && php artisan key:generate   # first time only
php artisan migrate --seed                          # first time only
composer run dev              # serve + queue:listen + pail + vite, concurrently
```

`composer run dev` binds `serve` to `127.0.0.1` only — fine for the web
app and for testing mobile over USB (see below), but unreachable from a
physical device over WiFi. For that, run serve on its own instead:
`php artisan serve --host=0.0.0.0 --port=8000` (add `queue:listen`/
`pail`/`npm run dev` in separate terminals if you want them).

MinIO buckets are private by default — fine for Documents/Uploads
(served through an authorized Laravel endpoint), but the Theme
Builder's logo files are meant to be public brand assets loaded
directly by URL (web preview, mobile). One-time per environment, after
`docker compose up -d`:

```bash
docker exec rukunmuda-minio-1 mc alias set local http://localhost:9000 rukunmuda rukunmuda-secret
docker exec rukunmuda-minio-1 mc anonymous set download local/rukunmuda/theme-logos
```

Without this, logo images 403 and show as broken images in the browser/app.

Seeded accounts (password `password`): `owner@rukunmuda.test`,
`admin@rukunmuda.test`, `bendahara@rukunmuda.test`,
`panitia@rukunmuda.test`, `anggota@rukunmuda.test`,
`warga@rukunmuda.test` — one per `OrganizationRole`, all members of the
same seeded organization, which also gets one sample event (with
committee members and tasks), one published announcement, two kas
accounts with a realistic transaction history (including one still
pending approval), monthly dues for every member (one already paid), a
matching audit trail, and one published PUBLIC report — with public
transparency enabled, so `/org/{slug}/transparency` has something to
show without logging in.

`php artisan db:seed` (and therefore `migrate:fresh --seed`) also runs
`MultiOrganizationSeeder` on top of the account above — 3 more
organizations (`org1`/`org2`/`org3`), each with 14+ members across all
four roles and a full spread of domain data (events, finance at every
report-review stage, dues, inventory, a sponsor, a document, a vote).
Useful for testing tenant isolation, RBAC, and the superadmin cross-org
view against more than one lonely organization. Log in as
`ketua@org1.test`, `bendahara@org2.test`, `anggota3@org3.test`, etc.
(same `password` password). Also seeds a plain
`superadmin@rukunmuda.test` user — grant is CLI-only by design and
needs an interactive confirm, so it's **not** granted automatically;
run this yourself after every `migrate:fresh --seed`:

```bash
php artisan superadmin:grant superadmin@rukunmuda.test
```

To seed only the multi-org fixture on its own (e.g. on top of a DB you
don't want to fully reset), run
`php artisan db:seed --class=MultiOrganizationSeeder` directly — see
its docblock (`laravel/database/seeders/MultiOrganizationSeeder.php`)
for the exact per-organization data shape.

Postgres and Redis are on non-default host ports (`5433` / `6380`) to
avoid clashing with other projects on this machine — see
`docs/decisions.md` ADR-0008.

### Mobile

```bash
cd rukunmuda/mobile
npm install                   # first time
```

Set `EXPO_PUBLIC_API_URL` in `mobile/.env.local`. Two ways to point it
at the backend, both work — pick based on how the device connects:

- **USB (the usual way)** — Expo already tunnels the JS bundle over USB
  (`adb reverse tcp:8081 tcp:8081`, set up automatically). Do the same
  for the API and skip the network/firewall question entirely:
  ```
  adb reverse tcp:8000 tcp:8000
  ```
  ```
  EXPO_PUBLIC_API_URL=http://127.0.0.1:8000/api/v1
  ```
- **WiFi** — point at the dev machine's LAN IP instead, and make sure
  `serve` is bound to `0.0.0.0` (see above):
  ```
  EXPO_PUBLIC_API_URL=http://<your-LAN-IP>:8000/api/v1
  ```
  (Android emulator only: omit the env var and it defaults to `10.0.2.2`
  automatically.)

Then:

```bash
npx expo run:android          # first run, or after adding a native dependency
npx expo start --dev-client   # every run after that — JS only, no rebuild
```

If a fetch ever behaves like nothing loaded (empty-looking screens, one
spot showing a raw `NaN`/error) with the network otherwise fine, suspect
a stale login on the device before the network — log out and back in.

## Docs

- [`docs/architecture.md`](docs/architecture.md) — stack, layering, why one Laravel app
- [`docs/domain-model.md`](docs/domain-model.md) — models and relationships
- [`docs/database.md`](docs/database.md) — schema, identifier strategy, constraints
- [`docs/api.md`](docs/api.md) — `/api/v1` shape (Phase 5)
- [`docs/security.md`](docs/security.md) — tenancy, authN/authZ, financial safety
- [`docs/transparency.md`](docs/transparency.md) — visibility model, publish/revision flow, sharing
- [`docs/roadmap.md`](docs/roadmap.md) — phase plan and non-goals
- [`docs/decisions.md`](docs/decisions.md) — ADR log
- [`docs/deployment.md`](docs/deployment.md) — process model, infra, environment, backups, go-live checklist
- [`docs/next-up.md`](docs/next-up.md) — live, prioritized "what's next" across backend/web/mobile

## Stack

**Backend/web**: Laravel 13.26.1 (PHP 8.4.24) · React 19.2 + TypeScript +
Inertia 3.3.1 + shadcn/ui + Tailwind v4 · PostgreSQL 16 · Redis (via
predis) · Sanctum (REST API) · Pest · Pint · Larastan · Docker Compose
(postgres, redis, minio, mailpit).

**Mobile**: React Native + Expo (SDK 57) · TypeScript · Expo Router ·
TanStack Query · Expo SecureStore · `expo-document-picker`/
`expo-file-system`/`expo-sharing` for uploads and downloads.
