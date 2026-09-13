# API

REST API under `/api/v1`, authenticated via Laravel Sanctum, built in
Phase 5 for the future mobile client and any external integrations.
Reuses the same Actions/Policies/Models as the web app — no duplicated
business logic in API controllers (see ADR-0014, ADR-0015).

**Status: complete for everything built through Phase 4** (auth,
organization, members, events, event tasks, finance accounts/
transactions/reports, transparency, report sharing/QR), plus **Inventory**,
**Documents**, **Sponsors**, and **Voting** (Phase 7, API-only — see
below; voting has no creation endpoint by design). Attendance has no API
surface yet because it doesn't exist yet.

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
  POST   /api/v1/auth/logout                          auth:sanctum (revokes only the current token — other devices stay signed in)
  POST   /api/v1/auth/email/verification-notification auth:sanctum, throttle 6/min — re-sends the verification link
  GET    /api/v1/auth/sessions                         auth:sanctum (the caller's own active Sanctum tokens/devices; isCurrent flags the one in use)
  DELETE /api/v1/auth/sessions/{tokenId}                auth:sanctum (revoke one of the caller's own other devices)

Organization
  POST   /api/v1/organizations                        auth:sanctum (outside current-org — a new user has no org yet;
                                                        first org open to anyone, a second is KETUA-only — ADR-0019)
  GET    /api/v1/organizations/mine                   auth:sanctum (also returns canCreate — see ADR-0019)
  POST   /api/v1/organizations/switch                 auth:sanctum (membership verified server-side)
  GET    /api/v1/organizations/current                auth:sanctum + current-org

Invites (shareable join links — ADR-0021)
  GET    /api/v1/organizations/invites                auth:sanctum + current-org (KETUA only; active links)
  POST   /api/v1/organizations/invites                 "        (KETUA only; {max_uses?: int} — returns a ready-to-share url)
  DELETE /api/v1/organizations/invites/{invite}        "        (KETUA only; revoke)
  (Accepting is the WEB route GET /join/{token} — a newcomer has no org, and
   the link must open in a browser for someone with no account or app.)

Membership exit (leaving is a request the chair decides — ADR-0020)
  GET    /api/v1/membership/exit-requests             auth:sanctum + current-org (KETUA only; the pending queue)
  GET    /api/v1/membership/exit-requests/mine         "        (the caller's own latest request + canRequest)
  POST   /api/v1/membership/exit-requests              "        (KETUA refused — hand the chair over first)
  POST   /api/v1/membership/exit-requests/{id}/decide  "        (KETUA only; {approve: bool, note?: string})

Members
  GET    /api/v1/members                               "
  POST   /api/v1/members                                "        (KETUA excluded from assignable roles — see ADR-0017)
  GET    /api/v1/members/{member}                       "
  PATCH  /api/v1/members/{member}                       "        (role change; KETUA excluded, and a KETUA's own role can't be changed)
  POST   /api/v1/members/{member}/transfer-chair         "        (KETUA only; hands the chair to {member}, outgoing chair drops to ANGGOTA)
  DELETE /api/v1/members/{member}                       "        (soft-deleted; a KETUA's own membership can't be removed)
  GET    /api/v1/members/{member}/responsibilities        "        (active event committee assignments only)
  GET    /api/v1/members/{member}/activity                "        (last 5 AuditLog entries — finance-only coverage today)

Profile
  PATCH  /api/v1/profile/phone-visibility             auth:sanctum (outside current-org — user-level, not org-scoped)

Notifications
  GET    /api/v1/notifications                        auth:sanctum (outside current-org — user-level; the caller's own, latest 50)
  POST   /api/v1/notifications/{notification}/read     auth:sanctum (marks one as read; 404s if it isn't the caller's own)

Events
  GET    /api/v1/events                                  "
  POST   /api/v1/events                                   "        (create: KETUA/BENDAHARA/SEKRETARIS — any "pengurus", not chair-only; always created DRAFT)
  GET    /api/v1/events/{event}                           "
  PATCH  /api/v1/events/{event}                           "        (status/lifecycle_stage required every call; a past start_at forces PLANNED -> COMPLETED server-side)

  Both POST and PATCH additionally accept:
    category           nullable enum (KERJA_BAKTI|OLAHRAGA|SOSIAL|LAINNYA)
    sponsor_id          nullable, must belong to the org
    budget_amount        nullable int; upserts one linked DRAFT expense FinancialTransaction
                          (account/category auto-selected, no picker) rather than duplicating it
                          on repeated saves — requires the org to already have a FinancialAccount
    committees          nullable array of {membership_id, role_title?}; PATCH replaces the
                          roster wholesale, so a caller must re-send everyone who should stay
                          (the creator is always kept on create even if omitted)
  Publishing (status transitions away from DRAFT) notifies every committee member except the
  actor (EventCommitteeAssigned); changing start_at/location on an already-published event
  notifies them again (EventRescheduled). Draft-only edits notify no one.

Tasks
  GET    /api/v1/events/{event}/tasks                     "
  POST   /api/v1/events/{event}/tasks                     "
  PATCH  /api/v1/events/{event}/tasks/{task}/status        "        (status only — no general task edit or delete via API yet)
  GET    /api/v1/my/tasks                                 "        (every task assigned to the caller, across every event in the current org — powers "Tugas saya")

Finance
  GET    /api/v1/finance/accounts        (read-only — no create/update/delete via API yet)
  GET    /api/v1/finance/transactions
  POST   /api/v1/finance/transactions    (always created as DRAFT — same as web)
  GET    /api/v1/finance/categories      (read-only — no create/update/delete via API yet)
  GET    /api/v1/finance/dues
  POST   /api/v1/finance/dues/generate-monthly   (treasury team only)
  POST   /api/v1/finance/dues/{due}/payments   (treasury team only; creates a linked income transaction; accepts method: TUNAI|TRANSFER and an optional note)
  POST   /api/v1/finance/dues/{due}/notify   (the member themselves, own due only — "Beri tahu bendahara"; never marks the due paid)

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

Inventory (Phase 7 — API only, no web page yet)
  GET    /api/v1/inventory
  POST   /api/v1/inventory                              (pengurus: KETUA/BENDAHARA/SEKRETARIS)
  GET    /api/v1/inventory/{inventoryItem}                (includes the item's latest 10 loans, active + history)
  PATCH  /api/v1/inventory/{inventoryItem}               (pengurus; quantity can't drop below what's on loan)
  DELETE /api/v1/inventory/{inventoryItem}                (pengurus; blocked while any loan is BORROWED)
  POST   /api/v1/inventory/{inventoryItem}/loans          (any member — borrow)
  POST   /api/v1/inventory/loans/{loan}/return             (the borrower, or pengurus)

Documents (Phase 7 — API only, no web page yet)
  GET    /api/v1/documents
  POST   /api/v1/documents                              (KETUA/SEKRETARIS; BENDAHARA only for category=LAPORAN)
  GET    /api/v1/documents/{document}
  GET    /api/v1/documents/{document}/download
  DELETE /api/v1/documents/{document}                     (the uploader, or any pengurus)

Uploads (general-purpose file store, backed by MinIO/S3)
  POST   /api/v1/uploads                                (any member; jpg/jpeg/png/pdf, max 10MB)
  GET    /api/v1/uploads/{upload}                         (streamed inline — preview, not a forced download)

Sponsors (Phase 7 — API only, no web page yet)
  GET    /api/v1/sponsors                                (contact details only for KETUA/BENDAHARA)
  POST   /api/v1/sponsors                                 (KETUA/BENDAHARA; finds-or-creates the Sponsor by name)
  GET    /api/v1/sponsors/{sponsor}                        (route param binds a SponsorContribution)
  PATCH  /api/v1/sponsors/{sponsor}/status                 (one step at a time; BATAL from any state; optionally records a linked income transaction when marking DITERIMA on a cash sponsorship)

Voting (Phase 7 — API only, no web page, and no creation endpoint by design)
  GET    /api/v1/votes                                    (open first, soonest-closing at the top, then closed)
  GET    /api/v1/votes/{vote}
  POST   /api/v1/votes/{vote}/responses                    (eligible members only, while open; resubmission replaces the prior response when the vote is editable)
  GET    /api/v1/votes/{vote}/results                      (per-option counts/percent; percent suppressed under 3 voters on an anonymous vote; the voter breakdown never exists for an anonymous vote, for any viewer)

Superadmin (platform-level, read-only, cross-organization — see ADR-0018; deliberately outside current-org)
  GET    /api/v1/superadmin/organizations                  ('superadmin' middleware — see EnsureSuperadmin; every org, with memberCount)
  GET    /api/v1/superadmin/organizations/{organization}    (full detail: members, financial reports incl. DRAFT/PRIVATE, transparency summary; every call writes an AuditLog row)
```

**Vote creation has no endpoint on purpose** — mobile-ux.md § Open product
decisions leaves "who may create a vote, and with what options" as a
still-open human decision. Votes exist today only via factories/tinker
until that's resolved.

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
   On success: `201` with `{token, user: {id, name, email, emailVerified}}`.
   The token is a Sanctum personal access token, named after `device_name`
   (so a user's active sessions are identifiable/revocable per device
   later). On failure: `422` with a validation error on `email`.
2. Every subsequent request sends `Authorization: Bearer {token}`.
   **A token from an unverified account opens nothing** (ADR-0022): every
   gated endpoint answers `403` with
   `{message, code: 'email_unverified'}`. `user.emailVerified` is returned
   on both register and login precisely so the client can show a "check
   your email" state up front rather than discovering the 403 one request
   later. `POST /api/v1/auth/email/verification-notification` re-sends the
   link and, like logout and the session endpoints, sits outside the gate
   — the user who needs it is the one who cannot pass it.
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

`tests/Feature/Api/SuperadminTest.php` (7 tests) + `tests/Feature/
SuperadminCommandTest.php` (4 tests): non-superadmin/unauthenticated
403/401, full org detail visible regardless of membership or report
visibility, AuditLog written on every view, writes still blocked (no
`current-org` context exists for a superadmin), `is_superadmin` immune
to mass assignment, and the grant/revoke Artisan commands.
