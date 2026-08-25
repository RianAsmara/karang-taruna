# Roadmap

Phases as fixed by the product brief. Each phase's "definition of done"
(migration, model, validation, authorization, business logic, UI, error/
loading states, tests, lint, static analysis, docs) applies throughout —
not just at the end.

## Phase 0 — Architecture (this phase)

Inspect repo, confirm no existing Laravel app, confirm local tooling
(done — see `architecture.md`). Produce this `docs/` set. No production
code. **Status: complete, awaiting approval to start Phase 1.**

## Phase 1 — Laravel foundation

**Status: complete.**

- Scaffolded via `composer create-project laravel/react-starter-kit`,
  then manually upgraded to Laravel 13.26.1 / Inertia 3.3.1 / React 19.2
  / Tailwind v4 (the published starter kit still pins Laravel 12/Inertia
  2 — see `decisions.md` ADR-0007).
- PostgreSQL 16 + Redis wired up via Docker Compose
  (`rukunmuda/docker-compose.yml`: postgres, redis, minio, mailpit),
  ports remapped to 5433/6380 to avoid clashing with sibling projects on
  this machine (ADR-0008). MinIO bucket created and verified with a
  read/write smoke test; Redis verified as the cache/session driver.
- `Organization`, `User`, `OrganizationMembership` on ULID primary keys
  (ADR-0002), `OrganizationRole` enum, `OrganizationPolicy` /
  `OrganizationMembershipPolicy` with real (not stubbed) ability logic,
  `CreateOrganizationAction` for the create-org + owner-membership
  transaction.
- Dashboard shows the user's organization + role, or a create-organization
  form when they have none — verified both states in-browser.
- `APP_LOCALE=id`; every visible auth/settings/dashboard string
  translated to Indonesian; verified in-browser (register → dashboard →
  create organization → settings).
- Pest (replacing the stock PHPUnit-only setup) + Larastan wired in,
  matching the master prompt's explicit tooling requirement. 33 tests
  passing, Pint clean, Larastan clean, `tsc --noEmit` clean, ESLint
  clean.
- No mobile work.

## Phase 2 — Members and events

**Status: complete.**

- `Event`, `EventCommittee`, `EventTask`, `EventParticipant`,
  `Announcement` — ULID-keyed, full migrations/models/factories.
- Tenant resolution formalized: `ResolveCurrentOrganization` middleware
  (alias `current-org`) binds the acting user's `Organization` /
  `OrganizationMembership` into the container from their membership, never
  from client input; controllers type-hint `Organization $organization`
  to receive it. `User::roleIn()` / `membershipIn()` centralize the
  role/membership lookup previously duplicated per-policy (ADR-0009).
- Real authorization throughout: `EventPolicy`, `EventTaskPolicy`,
  `EventParticipantPolicy`, `AnnouncementPolicy`, `OrganizationMembershipPolicy`
  (member management). Event manage-rights (`Event::isManagedBy()`) are
  OWNER/ADMIN or the event's PIC; task-status updates are additionally
  open to the task's own assignee; a member can always cancel their own
  event registration.
- `CreateEventAction` (creates the event and adds its creator to the
  committee in one transaction) and `AddMemberAction` (adds an existing
  registered user to the organization by email, or fails with a
  validation error — no invitation/email system yet, out of scope for
  this phase).
- Full Inertia UI: members list + add/role-change/remove, events
  list/create/edit/show (with inline committee/task/participant
  management), announcements list/create/edit/show. Sidebar nav gained
  Kegiatan, Anggota, Pengumuman. All UI text in Indonesian.
- 56 Pest tests (30 new this phase) covering authorization boundaries —
  tenant isolation, role gates, PIC/assignee/self-service exceptions —
  not just happy paths. Pint clean, Larastan clean, `tsc --noEmit` clean,
  ESLint clean, verified against real PostgreSQL.
- Deferred, not forgotten: task comments/attachments/activity history
  (section 14 of the brief) — needs a `Comment`/audit-log foundation
  that doesn't exist yet; revisit alongside Phase 3's audit trail work.
  Member invitations by email (for not-yet-registered users) are also
  deferred — `AddMemberAction` currently requires the user to already
  have an account.

## Phase 3 — Finance

**Status: complete.**

- `FinancialAccount`, `FinancialCategory`, `FinancialTransaction`,
  `MemberDue`, `MemberPayment`, `AuditLog` — ULID-keyed. `amount` columns
  carry a `CHECK (amount > 0)` constraint on PostgreSQL (ADR-0011 covers
  why it's guarded to `pgsql` only, not SQLite/tests).
- Transaction state machine, exactly as specified: `DRAFT → PENDING →
  APPROVED/REJECTED`. `SubmitTransactionAction` routes DRAFT to PENDING
  when the organization requires approval, straight to APPROVED
  (no reviewer) otherwise. `ApproveFinancialTransactionAction` /
  `RejectFinancialTransactionAction` require OWNER/ADMIN — deliberately
  excluding TREASURER, who prepares entries — and block the preparer
  from reviewing their own transaction. APPROVED/REJECTED are locked:
  `FinancialTransactionPolicy::update()` refuses once
  `status->isFinal()`, matching the brief's "never silently modify
  historical financial records."
- `User::isTreasurerOf()` / `isOrganizerOf()` added as the two
  role-group checks finance (and now event/member/announcement) policies
  share — see ADR-0012.
- Money: integer rupiah throughout, as committed in ADR-0003. Balances
  are always derived (`FinancialAccount::balance()` sums APPROVED
  transactions only — income in, expense out, transfers moved between
  the two accounts), never stored as an editable column.
- Financial visibility follows the transparency default: any member sees
  APPROVED transactions; DRAFT/PENDING/REJECTED are visible only to
  OWNER/ADMIN/TREASURER. Same split on `MemberDue` (a member sees their
  own; the treasury team sees everyone's).
- Member dues: `GenerateMonthlyDuesAction` bulk-creates a MONTHLY due per
  member for a period (idempotent — safe to re-run). Manual dues support
  EVENT/SPECIAL/DONATION types. `RecordDuePaymentAction` creates the
  `MemberPayment` *and* a mirrored INCOME `FinancialTransaction` in one
  DB transaction, rejects overpayment beyond what's outstanding, and
  goes through the same approval pipeline as any other transaction.
- Event budget: no separate model — `EventController::show()` computes
  actual (APPROVED) vs planned (DRAFT+PENDING) income/expense/variance
  directly from `FinancialTransaction` rows scoped to the event, exactly
  the "planned, actual, variance" the brief asks for in §19. Planned
  figures are treasury-only (internal, not-yet-final numbers); actual
  figures are visible to any member.
- Audit trail: `FinancialTransactionObserver` (attached via
  `#[ObservedBy]`) writes an `AuditLog` row on every create/update,
  labeling the action from the status transition
  (`transaction.created/submitted/approved/auto_approved/rejected/updated`)
  with before/after snapshots. Deliberately simple — an Observer, not a
  bespoke event-sourcing system — per the brief's own "do not create an
  unnecessarily complex audit system before it is needed."
- Full Inertia UI: Kas (accounts + balances), Kategori, Riwayat Transaksi
  (list/create/edit/show with submit/approve/reject actions), Iuran
  (dues list, generate-monthly, manual due, record-payment). Sidebar
  gained Kas and Iuran nav items. Rupiah formatting via a new
  `formatRupiah()` helper (`Intl.NumberFormat('id-ID', {currency:
  'IDR'})`).
- 83 Pest tests total (27 new this phase): the full transaction state
  machine, self-approval blocking, TREASURER-cannot-approve, tenant
  isolation, visibility splits, balance math including transfers,
  overpayment rejection, and audit-log content. Pint clean, Larastan
  clean, `tsc --noEmit` clean, ESLint clean — all verified against real
  PostgreSQL, plus a live browser walkthrough (create → submit →
  approve, dues list, event budget summary).
- One real bug caught by tests before shipping: `reviewed_by`/
  `reviewed_at` were missing from `FinancialTransaction::$fillable`, so
  `update()` was silently dropping the reviewer on approve/reject
  (Laravel mass-assignment protection working as intended, just against
  an incomplete list) — see ADR-0012 postscript for how it was caught.

## Ad hoc — Public organization landing page

Built out of phase order, on explicit request, between Phase 3 and
Phase 4: `GET /org/{organization:slug}` (`OrganizationLandingController`,
`organizations/landing.tsx`) — a public, unauthenticated page per
organization showing its name, member count, upcoming
PLANNED/ONGOING events, and published announcements. Linked from the
dashboard's organization card ("Lihat halaman publik").

Deliberately carries **no financial data** — that's Phase 4's "Public
Transparency" territory (§30 of the brief), which needs
`FinancialReport.visibility` and the publish/revision flow that don't
exist yet. Only hand-picked safe fields are selected in the controller
(never the full `Organization` model), verified by a test asserting the
response is missing `organization.id`,
`organization.require_transaction_approval`, and `organization.settings`.
5 Pest tests (unauth access, event/announcement filtering, field
scoping, 404 on unknown slug); Pint/Larastan/tsc/ESLint clean.

When Phase 4 lands, its `/org/{organization}/transparency` route is a
sibling to this one, not a replacement — this page stays the general
public profile, transparency is the financial-specific view.

## Phase 4 — Transparency

**Status: complete.**

- `FinancialReport`, `FinancialReportRevision`, `ReportShareLog` —
  ULID-keyed. `organizations.public_transparency_enabled` (boolean,
  default false) added for §30's opt-in public page.
- `FinancialReport::calculateFigures()` is the one place opening/income/
  expense/closing balance get computed, from APPROVED transactions only,
  TRANSFER excluded (it nets to zero at the organization level). Shared
  by `GenerateFinancialReportAction` (creates DRAFT),
  `PublishFinancialReportAction` (recomputes fresh at publish time —
  transactions approved after the draft was made are picked up), and
  `RevisePublishedReportAction` (§23: correcting history never means
  editing it — the current state is snapshotted to
  `FinancialReportRevision` first, revision numbers increment, then the
  report's own numbers move forward). Administrators never type a
  balance by hand, anywhere.
- Same separation of duties as Phase 3's transactions: TREASURER
  generates the draft, only OWNER/ADMIN publish/archive/revise
  (`FinancialReportPolicy`). DRAFT reports are treasury-only regardless
  of their `visibility` field, which only takes effect once
  PUBLISHED/ARCHIVED.
- Visibility (`PRIVATE`/`MEMBERS`/`PUBLIC`) drives one canonical,
  shareable URL — `GET /reports/{report}` — used both by org members and
  by public visitors; it is deliberately outside the `auth` middleware
  group and does its own visibility check per request (`ReportController`),
  since the same route must serve both audiences correctly.
- QR codes via `endroid/qr-code` (`GET /reports/{report}/qr`, PNG,
  points at the canonical URL) and a WhatsApp click-to-chat share button
  built client-side from the brief's exact §26 message format — both
  gated by the same visibility check as the page itself. Every share
  (WhatsApp, copy-link, or an anonymous guest's) writes a
  `ReportShareLog` row; distribution metadata only, never authoritative.
- `TransparencyController` (`/transparansi`, authenticated, any member)
  is the §21 dashboard — balance, this month's income/expense/surplus,
  recent approved transactions, published reports — plus the
  OWNER-only toggle for the optional public page.
  `PublicTransparencyController` (`/org/{slug}/transparency`, no auth)
  is the §30 opt-in page: current balance and PUBLIC+PUBLISHED reports
  only, 404s outright when the organization hasn't enabled it.
- Nav gained "Transparansi" (between Kas and Iuran, matching §39's
  order). Sidebar and public pages both link to it appropriately.
- `FinancialReportObserver` (same `#[ObservedBy]` pattern as
  transactions) logs `report.created/published/archived/revised` to
  `AuditLog` — added after cross-checking `security.md` against what was
  actually built and finding report publish/archive/revision wasn't
  wired to the audit trail yet, even though §24 explicitly requires it
  and the doc already promised it. Fixed before moving on, with its own
  test.
- 23 new Pest tests (111 total): the full report state machine
  (generate → publish → archive, and separately → revise), recomputation
  behavior, all three visibility levels crossed with guest/member/
  organizer/tenant-outsider viewers, share logging (authenticated and
  anonymous), QR access control, and the public transparency toggle.
  Pint/Larastan/tsc/ESLint clean, verified against real PostgreSQL, plus
  a live browser walkthrough (public report page → copy-link share
  logged live → owner's transparency dashboard).
- One test flake found and fixed in the process: `FinancialAccountFactory`
  picked from only 4 flavor names, and `financial_accounts` has a
  `unique(organization_id, name)` constraint — a test creating two
  accounts for one org would occasionally collide. Fixed by suffixing
  the factory's default name with `fake()->unique()->numberBetween(...)`;
  confirmed fixed by rerunning the full suite three times clean.
- Simplification made deliberately, not by oversight:
  `FinancialReport.report_type` (MONTHLY/EVENT/ANNUAL/CASH_FLOW/
  MEMBER_DUES) is a label only for now — every type computes the same
  way (org-wide transactions within `period_start`/`period_end`). An
  EVENT report scoped to one event's own transactions, or a MEMBER_DUES
  report scoped to dues data specifically, would need type-specific
  computation paths; deferred until a real need for that distinction
  shows up, rather than building five computation paths speculatively.

## Phase 5 — API

**Status: complete.** (Attendance is not built yet — Phase 7 — so it has
no API surface either; the note below about it was aspirational and is
corrected here.)

- `laravel/sanctum` installed via `php artisan install:api` (scaffolds
  `routes/api.php`, migrates `personal_access_tokens`). `User` gained
  `Laravel\Sanctum\HasApiTokens`.
- 22 endpoints under `/api/v1`, in `App\Http\Controllers\Api\V1\*`:
  auth (login/logout), `organizations/current`, members, events,
  event tasks, finance accounts (read-only), finance transactions,
  finance reports (index/publish/archive), the mixed-audience report
  page (show/share/qr), and transparency — matching the endpoint list
  `api.md` sketched back in Phase 0, with one deliberate correction (see
  below).
- **Zero duplicated business logic**, per the brief's explicit
  constraint — every API controller calls the exact same Actions,
  Policies, and Form Requests as its web counterpart. Three pieces of
  logic that lived only inside web controllers were promoted onto models
  so both sides share one implementation, rather than the API
  reimplementing them: `FinancialReport::isPubliclyViewable()`,
  `Organization::transparencySummary()` (the "Transparansi" figures —
  balance, month income/expense, recent transactions, published
  reports), and a small shared `App\Support\ReportQrCode` for the PNG
  generation both `ReportController`s call. This is a strict improvement
  over Phase 4's web-only versions, not just API scaffolding.
- **Tenant/org resolution reused as-is**: API routes needing "the
  current organization" sit behind the same `current-org` middleware
  alias as the web `routes/organization.php` group, and API controllers
  type-hint `Organization $organization` / `OrganizationMembership
  $membership` exactly like their web equivalents — same container-
  binding mechanism (ADR-0009), no new tenancy code path. The one change:
  `ResolveCurrentOrganization` now branches on `$request->expectsJson()`
  — a JSON client with no membership gets a `422` body instead of a
  redirect to `/dashboard`, which would be meaningless for a mobile app.
- **Auth is a separate flow from the web login, correctly** — not a
  duplication of it. `Api\V1\AuthController::login` follows Sanctum's
  official "mobile API tokens" pattern (validate credentials directly,
  issue a token via `$user->createToken($device_name)`), because the web
  `LoginRequest::authenticate()` is inherently session-based
  (`Auth::attempt` + `session()->regenerate()`) and a token exchange is a
  fundamentally different response contract, not the same rule expressed
  twice.
- **Deliberate spec correction**: `api.md`'s Phase 0 sketch listed
  `GET /api/v1/finance/reports/{report}/share` as read-only, and that's
  what got built — it returns `{ shareUrl, qrUrl }` for a mobile client
  to build its own WhatsApp share action from, with **no side effect**.
  Logging a share event stays POST-only and web-only
  (`ReportController@share`, `report_share_logs`) — a `GET` endpoint
  should never write. Worth naming since it's a case where the earlier
  sketch was followed literally rather than "fixed" to match the web
  behavior; if mobile share-analytics become a real requirement later,
  that's a new `POST /api/v1/.../share` endpoint, not a change to this
  one.
- **Real bug found only by testing against Postgres, not SQLite**:
  Sanctum's published `personal_access_tokens` migration uses
  `$table->morphs('tokenable')`, which is a `bigint` column — but every
  `tokenable` model in this app (`User`) has a ULID primary key (ADR-0002).
  SQLite (the Pest suite's driver) doesn't enforce column typing strictly,
  so `php artisan test` passed cleanly with this bug present; the very
  first live `curl` login against real Postgres failed with `invalid
  input syntax for type bigint`. Fixed by changing it to
  `$table->ulidMorphs('tokenable')` before this ever reached staging.
  Reinforces why the phase workflow always ends with a live check against
  real Postgres, not just a green test suite (see ADR-0016).
- 30 new Pest tests (141 total, `tests/Feature/Api/`): token issuance and
  revocation, tenant isolation and role-based authorization mirrored from
  the web test suites for every resource, the DRAFT-default-on-create
  behavior, and the same three-tier guest/member/organizer visibility
  matrix as `FinancialReportTest` for the mixed-audience report
  endpoints. Pint/Larastan clean; live-verified end-to-end against real
  PostgreSQL (login → protected endpoints → guest report/QR access) via
  `php artisan serve` + `curl`, which is what caught the migration bug
  above. No frontend files changed this phase, so `tsc`/ESLint/build
  were not re-run.

## Phase 6 — Mobile

Only started once Phase 5's API contracts are stable. React Native +
Expo: auth, dashboard, events, tasks, transparency, attendance,
notifications. Pure API client — no direct DB access, no independent
business rules.

## Phase 7 — Additional features

Voting, inventory, sponsors, document generation, activity points.

## Phase 8 — Production hardening

Performance/query optimization, caching where justified by measurement,
security hardening, observability, backups, CI/CD, deployment docs.

## Explicit non-goals for now

- No mobile implementation before Phase 6.
- No WhatsApp Business API automation (MVP is click-to-chat/share only;
  Business API integration, if ever pursued, is a distribution-channel
  enhancement, never a source-of-truth change).
- No repositories-for-every-model, generic CRUD services, DTOs
  everywhere, CQRS, event sourcing, or microservices — see
  `decisions.md`.
