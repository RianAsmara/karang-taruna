# API

REST API under `/api/v1`, authenticated via Laravel Sanctum, built in
Phase 5 for the future mobile client and any external integrations.
Reuses the same Actions/Policies/Models as the web app — no duplicated
business logic in API controllers (see ADR-0014, ADR-0015).

**Status: complete for everything built through Phase 4** (auth,
organization, members, events, event tasks, finance accounts/
transactions/reports, transparency, report sharing/QR). Attendance,
voting, and inventory have no API surface yet because they don't exist
yet — they're Phase 7.

## Conventions

- **Versioning**: `/api/v1` only.
- **Responses**: Laravel **API Resources** only — no Eloquent model is
  ever returned directly from a controller. List endpoints return
  `{"data": [...]}`; single-resource endpoints return `{"data": {...}}`,
  occasionally with a sibling top-level key for viewer-specific metadata
  (e.g. `organizations/current` returns `{"data": {...org}, "membership":
  {...}}`).
- **Requests**: the exact same Form Request classes as the equivalent web
  controller (e.g. `Api\V1\EventController::store` validates with
  `App\Http\Requests\Event\StoreEventRequest`, not a copy).
- **AuthZ**: the exact same Policies as web — a mobile client gets no
  more access than the equivalent web session. `$this->authorize(...)` in
  every API controller, same as web.
- **Tenant resolution**: identical mechanism to web (ADR-0009) — routes
  needing "the current organization" sit behind `auth:sanctum` then
  `current-org` middleware, and controllers type-hint `Organization
  $organization` / `OrganizationMembership $membership` to receive the
  container-bound instance. Never resolved from a client-supplied
  `organization_id`.
- **Errors**: Laravel's default JSON error rendering — `422` with a
  `message` + `errors` object for validation failures, `403` for policy
  denials, `404` for missing/not-visible records, `401` for missing/
  invalid auth. `current-org` returns a `422 {"message": "..."}` when the
  authenticated user has no organization membership (a web request in
  the same situation gets redirected to `/dashboard` instead — see
  ADR-0014).

## Endpoints

```
Auth
  POST   /api/v1/auth/login                          (public)
  POST   /api/v1/auth/logout                          auth:sanctum

Organization
  GET    /api/v1/organizations/current                auth:sanctum + current-org

Members
  GET    /api/v1/members                               "
  POST   /api/v1/members                                "
  GET    /api/v1/members/{member}                       "

Events
  GET    /api/v1/events                                  "
  POST   /api/v1/events                                   "
  GET    /api/v1/events/{event}                           "
  PATCH  /api/v1/events/{event}                           "

Tasks
  GET    /api/v1/events/{event}/tasks                     "
  POST   /api/v1/events/{event}/tasks                     "

Finance
  GET    /api/v1/finance/accounts        (read-only — no create/update/delete via API yet)
  GET    /api/v1/finance/transactions
  POST   /api/v1/finance/transactions    (always created as DRAFT — same as web)

Reports
  GET    /api/v1/finance/reports
  POST   /api/v1/finance/reports/{report}/publish
  POST   /api/v1/finance/reports/{report}/archive

Mixed audience — no auth:sanctum, same rule as web's /reports/{report} (ADR-0013)
  GET    /api/v1/finance/reports/{report}
  GET    /api/v1/finance/reports/{report}/share    (read-only — see note below)
  GET    /api/v1/finance/reports/{report}/qr       (PNG)

Transparency
  GET    /api/v1/transparency                          auth:sanctum + current-org
```

**A note on `GET .../reports/{report}/share`**: it returns `{shareUrl,
qrUrl}` — the pieces a mobile client needs to build its own WhatsApp
share action — and has **no side effect**. It does not write a
`ReportShareLog` row. Logging a share event stays a web-only, `POST`
concern (`ReportController@share` on the web routes) — a `GET` request
should never mutate state. If mobile share analytics become a real
requirement, that's a new endpoint, not a change to this one.

## Mobile auth flow (Sanctum)

Follows Sanctum's official "issuing mobile API tokens" pattern — a
distinct, stateless flow from the web session login, not a duplicate of
it (ADR-0014):

1. `POST /api/v1/auth/login` with `{email, password, device_name}`.
   On success: `201` with `{token, user: {id, name, email}}`. The token
   is a Sanctum personal access token, named after `device_name` (so a
   user's active sessions are identifiable/revocable per device later).
   On failure: `422` with a validation error on `email`.
2. Every subsequent request sends `Authorization: Bearer {token}`.
3. `POST /api/v1/auth/logout` (authenticated) revokes the *current*
   token only (`$request->user()->currentAccessToken()->delete()`) — a
   user's other logged-in devices stay signed in.

No custom JWT implementation — Sanctum only, per the master prompt's
explicit constraint.

## Tests

`tests/Feature/Api/` (30 tests): `AuthTest`, `OrganizationTest`,
`MemberTest`, `EventTest`, `EventTaskTest`, `FinanceTest`,
`FinancialReportTest`, `TransparencyTest`. Coverage mirrors the
corresponding web feature tests — role-based authorization, tenant
isolation, and (for reports) the full guest/member/organizer visibility
matrix — since an API client must be held to the exact same rules as a
web session.
