# API

REST API under `/api/v1`, authenticated via Laravel Sanctum, built in
Phase 5 (after the web/domain logic is stable) for the future mobile
client and any external integrations. Reuses the same Actions/Policies/
Models as the web app — no duplicated business logic in API controllers.

## Conventions

- **Versioning**: `/api/v1` only. No versioning complexity beyond this
  until there's an actual breaking-change need; backward compatibility is
  maintained once mobile ships (§ Phase 5 note in `roadmap.md`).
- **Responses**: Laravel **API Resources** only — no Eloquent model is
  ever returned directly from a controller.
- **Requests**: Form Request classes for validation, same as web.
- **AuthZ**: Policies, same as web — a mobile client gets no more access
  than the equivalent web session.
- **Pagination/filtering/sorting**: consistent query-param conventions
  (`page`, `per_page`, `filter[...]`, `sort`), applied uniformly across
  list endpoints once implemented in Phase 5.
- **Errors**: consistent JSON error shape (status, message, field errors
  for 422s), matching Laravel's default validation exception rendering
  for JSON requests.
- **Tenant resolution**: identical to web — the active organization comes
  from the authenticated user's membership/context, never from a client-
  supplied `organization_id` in the request body or query string.

## Planned endpoints (shape fixed now, implemented in Phase 5)

```
Auth
  POST   /api/v1/auth/login
  POST   /api/v1/auth/logout

Organization
  GET    /api/v1/organizations/current

Members
  GET    /api/v1/members
  POST   /api/v1/members
  GET    /api/v1/members/{member}

Events
  GET    /api/v1/events
  POST   /api/v1/events
  GET    /api/v1/events/{event}
  PATCH  /api/v1/events/{event}

Tasks
  GET    /api/v1/events/{event}/tasks
  POST   /api/v1/events/{event}/tasks

Finance
  GET    /api/v1/finance/accounts
  GET    /api/v1/finance/transactions
  POST   /api/v1/finance/transactions

Reports
  GET    /api/v1/finance/reports
  GET    /api/v1/finance/reports/{report}
  POST   /api/v1/finance/reports/{report}/publish
  POST   /api/v1/finance/reports/{report}/archive

Transparency
  GET    /api/v1/transparency

Sharing
  GET    /api/v1/finance/reports/{report}/share
  GET    /api/v1/finance/reports/{report}/qr
```

Attendance, voting, and inventory endpoints follow the same shape and are
added alongside their respective feature phases (Phase 4 for attendance
inside the API layer's scope, Phase 7 for voting/inventory), specified in
detail when Phase 5 starts.

## Mobile auth flow (Sanctum)

- Login issues a personal access token scoped to the user (device/session
  identifiable for revocation).
- Logout revokes the current token.
- Token/device management endpoint(s) to list and revoke active sessions,
  added in Phase 5 — exact shape TBD against Sanctum 13's current API.

No custom JWT implementation — Sanctum only, per the master prompt's
explicit constraint.
