# Architecture

## Overview

RukunMuda is a single Laravel 13 application serving both the web app (via
Inertia) and a versioned REST API (for the future React Native mobile
client). There is no separate backend service — Laravel is the only source
of business logic and the only thing that talks to PostgreSQL.

```
rukunmuda/
├── laravel/            # Laravel 13 app — web (Inertia) + REST API
│   ├── app/
│   ├── resources/js/   # React 19 + TypeScript (Inertia pages/components)
│   ├── resources/views/
│   ├── routes/web.php
│   ├── routes/api.php
│   └── ...
├── mobile/              # React Native + Expo — NOT built until Phase 6
├── docs/                # this directory
├── infra/               # deployment/infra config (created when needed)
└── docker-compose.yml   # postgres, redis, minio, mailpit (added Phase 1)
```

`mobile/` and `infra/` are placeholders in the repo layout — they are not
created until Phase 6 and Phase 8 respectively, per the roadmap.

## Why one Laravel app, not web/API split

- One set of Eloquent models, Policies, Form Requests, and Actions serves
  both Inertia controllers and API controllers. Splitting them risks the
  API and web app drifting on business rules (a documented anti-goal in
  the master prompt: mobile/web must never implement independent business
  rules).
- Sanctum supports both the Inertia session-based SPA flow (web) and
  token-based auth (mobile) from a single auth stack, so there's no
  technical reason for two services.
- Simpler ops: one deploy, one migration history, one queue.

## Stack (confirmed available on this machine)

| Layer | Choice | Version observed locally |
|---|---|---|
| Language | PHP | 8.4.24 |
| Framework | Laravel | 13.x (target `^13.17`, matching sibling project) |
| Package manager | Composer | 2.10.2 |
| Frontend runtime | Node | 24.14.1 |
| Web UI | React + TypeScript, Inertia 3 | React `^19.2`, `@inertiajs/react ^3.0` |
| Component kit | shadcn/ui (Radix primitives) + Tailwind v4 | `tailwindcss ^4.0` |
| Build | Vite | `^8.0` (via `laravel-vite-plugin`) |
| Database | PostgreSQL | 16.15 |
| Cache/Queue broker | Redis | 8.10.1 |
| Auth (web) | Laravel Fortify (session/SPA) | via starter kit |
| Auth (API/mobile) | Laravel Sanctum (tokens) | added Phase 1/5 |
| Object storage | S3-compatible / MinIO (local) | via `league/flysystem-aws-s3-v3` |
| Testing | Pest | `^4.7` |
| Static analysis | Larastan/PHPStan | `^3.9` |
| Formatting | Laravel Pint | `^1.27` |
| Local orchestration | Docker Compose (postgres, redis, minio, mailpit) | Docker 29.7.2 / Compose v5.5.0 confirmed |

These versions mirror `usaharumahan-marketplace/` (another Laravel React
Starter Kit app in this same workspace), used here as a known-good
baseline rather than guessed. See `decisions.md`.

## Application layers

Standard Laravel layering — no repository/service abstraction layer is
introduced by default:

- **Controllers** — thin. Receive request, authorize, call an
  Action/Service or Eloquent directly, return an Inertia response or API
  Resource.
- **Models** — Eloquent, relationships, scopes, casts, enums. Business
  rules that naturally belong to a single model live here (e.g.
  `FinancialReport::calculateClosingBalance()`).
- **Actions** — introduced only for multi-step business operations that
  don't belong to one model (`CreateEventAction`,
  `PublishFinancialReportAction`, `ApproveFinancialTransactionAction`).
  Not created to wrap a single Eloquent call.
- **Policies** — all authorization. No inline `if ($user->role === ...)`
  checks scattered through controllers/views.
- **Form Requests** — all non-trivial validation.
- **Events/Listeners** — side effects (audit logging, notifications).
- **Jobs** — async work (report generation, PDF generation, notification
  delivery) via the queue.

## Multi-tenancy

Tenant boundary is `Organization`. Every organization-owned table carries
`organization_id`. The active organization is resolved server-side from
the authenticated user's `OrganizationMembership` plus request context
(e.g. current route or a session-selected org) — never trusted from a
client-supplied `organization_id`. Enforced via a global scope on
tenant-owned models plus policy checks, detailed in `security.md`.

## Mobile integration (future)

`mobile/` (React Native + Expo) will be a pure client of
`/api/v1/*`, authenticated via Sanctum tokens, built only after the API
contracts are stable (Phase 6). It will not access PostgreSQL directly
and will not implement independent business rules.
