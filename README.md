# RukunMuda

Platform manajemen komunitas untuk organisasi pemuda Indonesia (Karang
Taruna, Pemuda Kampung, Pemuda RT/RW, dan komunitas sejenis) — mengelola
anggota, kegiatan, kas, iuran, transparansi keuangan, kehadiran, voting,
inventaris, sponsor, dokumen, dan pengumuman.

Prinsip produk: **Transparan · Kolaboratif · Sederhana · Akuntabel**.

## Status

**Phase 3 — Finance: complete.** (Phases 1–2 — Laravel foundation,
members/events — also complete.) Financial accounts, categories,
transactions with a full DRAFT→PENDING→APPROVED/REJECTED approval
workflow, member dues tied to the ledger, event budgets, and an audit
trail are all live. See `docs/roadmap.md` for what's done and
`CLAUDE.md` for the full phase-by-phase plan and working rules.

## Structure

```
rukunmuda/
├── laravel/            # Laravel 13 app — web (Inertia) + REST API (Phase 5+)
├── mobile/              # React Native + Expo client (Phase 6, not started)
├── docs/                # architecture, domain model, database, api, security,
│                        #   transparency, roadmap, decisions
├── docker-compose.yml   # postgres (5433), redis (6380), minio, mailpit
├── infra/               # deployment/infra config (added when needed)
└── CLAUDE.md            # governing instructions for this project
```

`mobile/` and `infra/` don't exist yet — those start in Phase 6 and
Phase 8 respectively.

## Local development

```bash
cd rukunmuda
docker compose up -d          # postgres, redis, minio, mailpit
cd laravel
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
composer run dev              # serve + queue:listen + pail + vite, concurrently
```

Seeded accounts (password `password`): `owner@rukunmuda.test`,
`admin@rukunmuda.test`, `bendahara@rukunmuda.test`,
`panitia@rukunmuda.test`, `anggota@rukunmuda.test`,
`warga@rukunmuda.test` — one per `OrganizationRole`, all members of the
same seeded organization, which also gets one sample event (with
committee members and tasks), one published announcement, two kas
accounts with a realistic transaction history (including one still
pending approval), monthly dues for every member (one already paid),
and a matching audit trail.

Postgres and Redis are on non-default host ports (`5433` / `6380`) to
avoid clashing with other projects on this machine — see
`docs/decisions.md` ADR-0008.

## Docs

- [`docs/architecture.md`](docs/architecture.md) — stack, layering, why one Laravel app
- [`docs/domain-model.md`](docs/domain-model.md) — models and relationships
- [`docs/database.md`](docs/database.md) — schema, identifier strategy, constraints
- [`docs/api.md`](docs/api.md) — `/api/v1` shape (Phase 5)
- [`docs/security.md`](docs/security.md) — tenancy, authN/authZ, financial safety
- [`docs/transparency.md`](docs/transparency.md) — visibility model, publish/revision flow, sharing
- [`docs/roadmap.md`](docs/roadmap.md) — phase plan and non-goals
- [`docs/decisions.md`](docs/decisions.md) — ADR log

## Stack

Laravel 13.26.1 (PHP 8.4.24) · React 19.2 + TypeScript + Inertia 3.3.1 +
shadcn/ui + Tailwind v4 · PostgreSQL 16 · Redis (via predis) · Pest ·
Pint · Larastan · Docker Compose (postgres, redis, minio, mailpit).
Sanctum lands in Phase 5 (API).

Later: React Native + Expo (`mobile/`, Phase 6 only).
