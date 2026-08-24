# Domain Model

This is the initial model set from the master prompt, grouped by bounded
area. Exact fields/relationships will be refined per-phase (Phase 2:
members/events, Phase 3: finance, Phase 4: transparency, Phase 7: voting/
inventory/sponsors) — this document fixes the shape everything else is
built against.

## Identity & tenancy

**User**
- Global identity (email, password, profile). A user can belong to
  multiple organizations.

**Organization**
- Represents a Karang Taruna / Pemuda Kampung / RT/RW youth group /
  community org. The tenant boundary for everything else.
- `require_transaction_approval` (bool) — gates the finance approval flow.

**OrganizationMembership**
- Joins `User` ↔ `Organization` with a `role`.
- Roles: `OWNER`, `ADMIN`, `TREASURER`, `COMMITTEE`, `MEMBER`, `RESIDENT`.
- A user's abilities within an organization derive from this role via
  Policies — never checked inline as a string comparison.

## Events

**Event**
- `title`, `description`, `location`, `start_at`, `end_at`, PIC
  (person in charge — an `OrganizationMembership`/`User`), status.
- Status: `DRAFT → PLANNED → ONGOING → COMPLETED → CANCELLED`.
- Lifecycle (process, not just status): `IDEA → APPROVAL → PREPARATION →
  EVENT → CLOSING → REPORT`.
- Has: committee, participants, tasks, budget, financial transactions,
  attendance, documents/photos, sponsors.

**EventCommittee** — members assigned an organizing role for one event
(`membership_id`, optional free-text `role_title` e.g. "Ketua Panitia").

**EventTask**
- `title`, `description`, `assignee`, `status`, `priority`, `due_date`.
- Status: `TODO`, `IN_PROGRESS`, `BLOCKED`, `DONE`.
- Priority: `LOW`, `MEDIUM`, `HIGH`.
- Comments, attachments, and activity history are deferred — see
  `roadmap.md` Phase 2 notes; not built yet.

**EventParticipant** — registration/RSVP record for an event.
Status: `REGISTERED`, `CANCELLED`.

## Finance

**FinancialAccount** — a "kas" (Kas Pemuda, Kas Olahraga, Kas Sosial, Kas
Event, ...). Balance is always derived from transactions, never stored as
an editable field.

**FinancialCategory** — categorization for transactions (e.g. konsumsi,
sewa venue, sponsor).

**FinancialTransaction**
- `financial_account_id`, `related_account_id` (nullable, TRANSFER
  destination only), `category_id` (nullable — required for
  INCOME/EXPENSE, absent for TRANSFER), `amount` (integer rupiah —
  see `database.md`), `transaction_type`
  (`INCOME`/`EXPENSE`/`TRANSFER`), `description`, `transaction_date`,
  `event_id` (nullable), `created_by`, `reviewed_by`/`reviewed_at`
  (nullable), `status`.
- Status: `DRAFT → PENDING → APPROVED`/`REJECTED`.
  `SubmitTransactionAction` sends DRAFT to PENDING when the
  organization has `require_transaction_approval` enabled, straight to
  APPROVED (no reviewer) otherwise. Approve/reject requires OWNER/ADMIN
  and is never the preparer themselves. APPROVED/REJECTED are locked —
  no further edits or deletes.
- `FinancialAccount::balance()` is always derived from APPROVED
  transactions only (income in, expense out, transfers moved between
  the two accounts) — never stored as an editable field.

**MemberDue** / **MemberPayment**
- Types: `MONTHLY`, `EVENT`, `SPECIAL`, `DONATION`. Per-member,
  per-period `amount_due`, with `amountPaid()`/`amountOutstanding()`/
  `isPaid()` derived from `MemberPayment` rows, never stored.
- Recording a payment (`RecordDuePaymentAction`) creates the
  `MemberPayment` *and* a mirrored INCOME `FinancialTransaction` in one
  DB transaction — the dues ledger and the kas ledger never drift apart.
  Overpayment beyond the outstanding balance is rejected.
- `GenerateMonthlyDuesAction` bulk-creates a MONTHLY due per member for
  a period; safe to re-run (skips members who already have one).

**AuditLog**
- `organization_id`, `actor_id` (nullable), `action` (free-form string,
  e.g. `transaction.approved` — not an enum, since actions will grow
  across domains over time), `model_type`, `model_id`,
  `previous_values`/`new_values` (jsonb), `created_at` only (append-only,
  no `updated_at`).
- Populated via `FinancialTransactionObserver` (a Model Observer, not a
  bespoke event-sourcing system) on every `FinancialTransaction`
  create/update. Extend to other models the same way if a future phase
  needs it — don't build a generic audit framework ahead of need.

## Transparency & reporting

**FinancialReport**
- `organization_id`, `period_start`, `period_end`, `title`, `status`
  (`DRAFT`/`PUBLISHED`/`ARCHIVED`), `visibility`
  (`PRIVATE`/`MEMBERS`/`PUBLIC`, default `MEMBERS`), `opening_balance`,
  `total_income`, `total_expense`, `closing_balance`, `created_by`,
  `published_at`, `published_by`.
- Types: `MONTHLY`, `EVENT`, `ANNUAL`, `CASH_FLOW`, `MEMBER_DUES` — a
  label only today; every type computes identically (org-wide approved
  transactions within the period). Type-specific scoping (e.g. an EVENT
  report limited to one event) is deferred until actually needed.
- `visibility` only governs access once a report is PUBLISHED/ARCHIVED —
  a DRAFT is always treasury-only (OWNER/ADMIN/TREASURER) regardless of
  what `visibility` is set to.
- Always generated from actual transaction data
  (`FinancialReport::calculateFigures()`) — balances are never
  hand-typed, whether creating the draft, publishing, or revising. See
  `transparency.md` for the publish/revision model.

**FinancialReportRevision**
- `financial_report_id`, `revision_number`, `snapshot`, `created_by`.
- Published reports are immutable; corrections create a new revision
  rather than mutating history.

**ReportShareLog**
- `report_id`, `channel` (`WHATSAPP`/`WEB`/`PDF`), `shared_by`,
  `shared_at`. Distribution metadata only — not a financial record.

## Attendance

**AttendanceSession** — belongs to an `Event`.

**Attendance** — `member`, `event`, `timestamp`, `status`. Duplicate
attendance for the same member+session is prevented at the DB level.

## Voting

**Vote** — question, `start_at`, `end_at`, anonymous/non-anonymous flag,
eligible-voter scope, belongs to an `Organization` (optionally an
`Event`).

**VoteOption** — e.g. `YES` / `NO` / `ABSTAIN`, or custom options.

**VoteResponse** — one per eligible member per vote, enforced by a DB
uniqueness constraint (`unique(vote_id, user_id)`).

## Inventory

**InventoryItem** — `name`, `category`, `quantity`, `condition`,
`location` (e.g. Tenda, Sound System, Kursi, Meja, Proyektor).

**InventoryLoan** — status `BORROWED`/`RETURNED`/`OVERDUE`, links an item
to a borrower and optionally an event.

## Sponsors

**Sponsor** — org/individual, contact info.

**SponsorContribution** — per-event: package, expected contribution,
actual contribution, agreement reference, notes.

## Documents & announcements

**Document** / **EventDocument** — proposal, RAB (rencana anggaran
biaya), attendance report, financial report, LPJ
(laporan pertanggungjawaban). Generated from structured application data,
not treated as a source of truth in themselves.

**Announcement** — organization-scoped broadcast content.

## Engagement & audit

**ActivityPoint** / **ActivityLog** — participation scoring, derived from
event/task/attendance activity.

**AuditLog** — actor, action, model, model ID, previous/new values where
appropriate, timestamp, organization. Populated via Events/Listeners
around the operations enumerated in `security.md` §Financial Audit Trail.

## Key relationships (Eloquent)

```
Organization  hasMany  OrganizationMembership
Organization  hasMany  Event
Organization  hasMany  FinancialAccount
Organization  hasMany  FinancialReport
User          hasMany  OrganizationMembership
Event         hasMany  EventTask
Event         hasMany  EventParticipant
Event         hasMany  FinancialTransaction (nullable event_id)
FinancialAccount  hasMany  FinancialTransaction
FinancialReport   hasMany  FinancialReportRevision
Vote          hasMany  VoteOption
Vote          hasMany  VoteResponse
```
