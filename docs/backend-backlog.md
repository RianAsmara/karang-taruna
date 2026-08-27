# Backend Backlog

Gaps in the Laravel domain/API layer (`laravel/app/`, `routes/api.php`,
`routes/web.php`'s controllers) found while auditing the codebase for
mobile E2E readiness (2026-08-25). Distinct from `mobile-e2e-backlog.md`
(the mobile app's own gaps) and `web-backlog.md` (the Inertia frontend's
gaps) — this one is about what Laravel itself doesn't do yet.

## 1. Financial evidence / attachment upload — RESOLVED (2026-08-25)

Master prompt §25 requires receipt/invoice/transfer-proof uploads on
transactions, validated (MIME/extension/size/authorization) and stored
via Laravel Filesystem, with MinIO provisioned in `docker-compose.yml`
specifically for this.

Implemented: `financial_transaction_attachments` table + model, a
`manageEvidence` Policy ability (treasurer-only, not gated by
`status->isFinal()` — evidence can be attached to an already-approved
transaction), `StoreFinancialTransactionAttachmentRequest`
(mimes:jpg,jpeg,png,webp,pdf, max 5MB), API + web store/destroy/download
endpoints, and a web UI on `finance/transactions/show.tsx` (upload form
+ attachment list + delete). `FinancialTransactionResource` exposes
`hasEvidence` (list views, via `withCount`) and full `attachments`
(detail view) — backs the mobile Report Detail "bukti terlampir" /
"tanpa bukti" distinction. Covered by
`tests/Feature/FinancialTransactionAttachmentTest.php` and
`tests/Feature/Api/FinancialTransactionAttachmentTest.php`.

## 2. No async infrastructure — RESOLVED for existing features (2026-08-25)

`app/Jobs/` and `app/Listeners/` still don't exist — not needed yet,
nothing queues expensive work today. `app/Notifications/` now exists
(database channel only, per master prompt §36's own "database
notifications" MVP scope): `TransactionSubmittedForReview`,
`TransactionReviewed`, `FinancialReportPublished`, `MemberDueReminder`.
Dispatched synchronously from the existing Finance actions
(`SubmitTransactionAction`, `Approve/RejectFinancialTransactionAction`,
`PublishFinancialReportAction`) — deliberately not queued, consistent
with master prompt §37 ("do not queue operations that must immediately
return authoritative financial results"). Readable via
`GET /api/v1/notifications` + `POST .../read` and the web
`/notifications` page (bell icon with an unread-count badge in the
sidebar header, wired via a new `unreadNotificationsCount` shared
Inertia prop). Email/push/WhatsApp channels remain future work, not
needed for MVP.

## 3. No Scheduler tasks — RESOLVED for existing features (2026-08-25)

`routes/console.php` now also schedules `dues:remind-unpaid`
(`App\Console\Commands\SendUnpaidDueReminders`, weekly on Mondays
08:00) — notifies members with a `MemberDue` whose period has arrived
and is still outstanding via `MemberDueReminder`. Report/task
reminders beyond this are not part of the "existing features" scope
this pass covered.

## 4. Attendance is entirely unbuilt

`AttendanceSession`/`Attendance` models from the domain model (§12) were
never created — no migration, model, policy, or controller in any
phase. `docs/roadmap.md`'s own Phase 6 description lists "attendance"
as a mobile feature, but there's no backend to support it yet. This
needs resolving before Phase 6 mobile work can build a real Attendance
screen (not part of the 11 already-designed screens, so no immediate
blocker — but worth fixing the roadmap text vs. reality mismatch).

## 5. Phase 7 features — RESOLVED for API (2026-08-26); web pages and Activity points still open

Voting, Inventory, Sponsors, Documents now have full migrations/models/
enums/policies/Form Requests/Actions/API controllers/Resources/routes,
found necessary while scoping the mobile Anggota/Peran & Izin/Iuran/
Inventaris/Dokumen/Sponsor/Voting screens against the API. 237 tests
total (up from 199), Pint/PHPStan clean, verified against real Postgres
via `migrate:fresh --seed`. Full detail in `docs/domain-model.md`'s
Inventory/Documents/Sponsors/Voting sections.

Also resolved as a prerequisite: the **role model migration**
(`OrganizationRole` from six values to the mobile design's four —
`KETUA`/`BENDAHARA`/`SEKRETARIS`/`ANGGOTA` — see ADR-0017), and wiring
the API to business logic that already existed for the web (member
role-change/removal, dues-payment recording, organization creation).

**Still open**:
- No Inertia web pages for any of the four new domains — a deliberate
  scope call (user chose "API-only for now" when asked) to prioritize
  the mobile build. Matches `web-backlog.md` §5, updated accordingly.
- **Activity points** — no models/migrations at all yet, unlike the
  other four.
- **Vote creation** — no endpoint by design; `mobile-ux.md` § Open
  product decisions leaves "who may create a vote, and with what
  options" as a still-open human decision, not something to invent.
  Votes exist today only via factories/tinker.
- `EventPolicy::create` narrowed from OWNER/ADMIN to KETUA-only as a
  mechanical side effect of the role migration removing ADMIN —
  mobile-screens.md § 29 describes event creation as available to
  "pengurus" (any management role) more broadly, so this may need
  widening to `isSecretaryOf()`-or-broader when Buat Kegiatan (mobile
  step 4) is actually built.

## 6. API gaps found while scoping mobile E2E wiring — RESOLVED (2026-08-25)

Full detail in `mobile-e2e-backlog.md` §2–3; summarized here from the
backend's side, since building these is backend work regardless of
which client ends up calling them:

- ~~No Announcements API~~ — `Api\V1\AnnouncementController` added
  (index + show, read-only).
- ~~No Member Dues API~~ — `Api\V1\MemberDueController` added (index;
  treasurer sees all, member sees own).
- ~~No cross-event "my tasks" query~~ — `Api\V1\MyTasksController`
  added (`GET my/tasks`).
- ~~`EventResource` never exposes `committees`~~ — now exposes
  `committees`, `committeeCount`, `participantCount`.
- ~~No server-side category breakdown~~ — `FinancialReport::
  categoryBreakdown()` added, exposed via `ReportController::show()`'s
  `->additional()`.
- No notifications concept anywhere — still open, tracked under §2/§3
  below (deliberately scoped to existing features only).

## 7. API login has no rate limiting — RESOLVED (2026-08-25)

`Api\V1\AuthController::login` now rate-limits via
`Api\Auth\LoginRequest::ensureIsNotRateLimited()`, mirroring the web
`LoginRequest` pattern exactly (keyed by email + IP).

## 8. No published CORS config — RESOLVED (2026-08-25)

`config/cors.php` published via `php artisan config:publish cors`;
`allowed_origins` is now environment-driven
(`env('CORS_ALLOWED_ORIGINS', '*')`, documented in `.env.example`)
instead of the framework default.

## 9. No Sanctum device/session management — RESOLVED (2026-08-25)

`Api\V1\AuthController` gained `sessions()` (list) and
`destroySession()` (revoke) methods, backed by
`PersonalAccessTokenResource`. A user can list and revoke their other
active devices' tokens; cannot revoke another user's session.

## 10. No PDF generation — RESOLVED (2026-08-25)

The Transparansi/Report Detail mobile screens (and the master prompt's
own "PDF: DISTRIBUTION ARTIFACT" framing, §75) assume a PDF export
exists.

Implemented via `barryvdh/laravel-dompdf`: `ReportController::pdf()`
(web, `GET /reports/{report}/pdf`) renders `resources/views/reports/pdf.blade.php`
from the report's own stored figures (opening/closing balance,
income/expense, category breakdown via the existing
`categoryBreakdown()`) — generated on demand, never a stand-in source
of truth per §75. Same view/authorization as `show()`/`qr()`
(PUBLIC+PUBLISHED reports downloadable while logged out; everything
else Policy-gated). A "Unduh PDF" button was added next to the
existing WhatsApp/copy-link actions on `reports/show.tsx`. The API's
`ReportController::show()`/`share()` now also expose a `pdfUrl` in
their response `meta`, alongside the existing `shareUrl`/`qrUrl` —
consistent with how those already hand a mobile client a URL rather
than raw bytes; the mobile "PDF sedang disiapkan." stub itself is
mobile-side wiring, tracked in `mobile-e2e-backlog.md`, not touched
here. Covered by `tests/Feature/ReportPdfTest.php`.

## 11. Phase 8 hardening — not started

Performance/query optimization, caching, security hardening beyond
what's already in place, observability (structured logging exists;
no OpenTelemetry), backups, CI/CD, deployment docs. Matches the
roadmap's own Phase 8 scope; listed for completeness.

## Explicitly not backlog (intentional, per existing ADRs)

- WhatsApp Business API automation — deliberately deferred
  (`docs/roadmap.md`'s non-goals; click-to-chat/share is the MVP and
  intended long-term distribution mechanism per the master prompt).
- `FinancialReportType` computing identically regardless of type
  (MONTHLY/EVENT/ANNUAL/etc.) — documented as an intentional
  simplification in the Phase 4 roadmap entry, not a gap.
