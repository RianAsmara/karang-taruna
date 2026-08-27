# Security

## Tenant isolation

- Tenant boundary is `Organization`. The active organization for a
  request is resolved server-side from the authenticated user's
  `OrganizationMembership` records plus route/session context — never
  accepted as a raw `organization_id` from the client.
- A global Eloquent scope applied to every tenant-owned model constrains
  queries to the resolved organization, so a missing `where` clause in a
  controller can't leak cross-tenant data.
- Every Policy method re-checks that the acting user has an active
  `OrganizationMembership` in the target record's organization before
  evaluating role-based ability — defense in depth against the scope
  being bypassed (e.g. `withoutGlobalScope` misuse, raw queries).

## Authentication

- **Web**: session-based, via the auth system shipped with the official
  Laravel React Starter Kit (Fortify under the hood) — registration,
  login, logout, password reset, email verification, profile/settings.
  Not replaced or reimplemented.
- **Mobile/API**: Laravel Sanctum personal access tokens (Phase 5,
  `docs/api.md`). `POST /api/v1/auth/login` issues a token named after
  the client's `device_name`; `POST /api/v1/auth/logout` revokes only
  the token used for that request, leaving other devices signed in. No
  custom JWT, no custom auth protocol. A device/session listing endpoint
  (to let a user see and revoke *other* devices' tokens) is not built yet
  — not required by anything shipped through Phase 5.
- Passwords: Laravel's default hashing (bcrypt/argon2 per config) —
  never stored or logged in plaintext.

## Authorization

- Laravel **Policies** for every authorization decision, using named
  abilities (`viewFinancialReport`, `publishFinancialReport`,
  `approveTransaction`, `manageMembers`, `manageEvent`,
  `manageOrganization`, etc.) — not inline `$user->role === 'admin'`
  checks scattered through the codebase.
- Current roles: `KETUA`, `BENDAHARA`, `SEKRETARIS`, `ANGGOTA` (see
  `domain-model.md` for the migration from the original six-role set, and
  the hierarchy — the chair inherits every other role's abilities).
  Policies map abilities to roles per organization membership, not
  globally per user.
- Frontend hides UI affordances a user can't use, but every mutating
  action is re-authorized server-side regardless of what the client
  sends — the frontend check is UX only.

## Financial safety (non-negotiable invariants)

- No floating-point money — integer IDR everywhere (`database.md`).
- Published `FinancialReport` records are immutable; corrections go
  through `FinancialReportRevision`, never an in-place update.
- Every multi-record financial operation (approve transaction, publish
  report, process a member payment) runs inside `DB::transaction()` — no
  partially-committed financial state.
- `require_transaction_approval` (per-organization) gates whether a
  `BENDAHARA`-created transaction needs a separate `APPROVED` step before
  it counts toward any published report. Only `APPROVED` transactions
  feed report totals.
- Duplicate prevention is enforced at the DB level, not just application
  logic: unique constraint on `vote_responses (vote_id, membership_id)`,
  unique constraint on `attendances (attendance_session_id,
  membership_id)`.

## Financial audit trail

Tracked via Laravel Events/Listeners into `AuditLog` (actor, action,
model, model ID, previous/new values where applicable, timestamp,
organization):
- transaction creation, modification, approval, rejection
- report publication, archival, revision

Kept intentionally simple — no event-sourcing, no separate audit service
— an `AuditLog` table populated by listeners is sufficient for this
domain's scale, revisited only if a real need for more emerges.

## File uploads (financial evidence, documents)

- Laravel Filesystem (`Storage::disk(...)`), backed by local disk in dev
  and S3-compatible/MinIO in staging/production — no raw PHP filesystem
  calls.
- Validated: MIME type, extension, file size, and authorization (only
  users with access to the owning record can upload/view).
- Private files (receipts, invoices, LPJ documents not yet published) are
  never served from a predictable public URL — access goes through an
  authorized download endpoint or a temporary signed URL.

## Web security baseline

- CSRF protection for all web (Inertia) form submissions — Laravel
  default, not disabled.
- Rate limiting on auth endpoints and any public-facing endpoint (public
  transparency pages, report share links) via Laravel's rate limiter.
- Server-side validation via Form Requests is authoritative; any
  client-side validation is UX-only and never trusted.
- Output escaping via React/Blade defaults — no raw HTML injection from
  user-controlled content without explicit sanitization.

## What's explicitly never exposed

- Member personal data, private donor information, internal audit notes,
  or other sensitive metadata on any `PUBLIC`-visibility surface
  (public transparency pages, shared report links) — see
  `transparency.md` for the visibility model.
- Secrets, tokens, or credentials in logs. Structured logs carry request
  ID, user ID, organization ID, route, action, duration — never
  passwords, tokens, or unnecessary PII.
