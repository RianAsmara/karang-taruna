# Database

PostgreSQL 16 (confirmed installed locally: 16.15). Local dev via Docker
Compose (`postgres`, `redis`, `minio`, optionally `mailpit`), created in
Phase 1.

## Identifier strategy

**ULIDs** for all primary keys (Laravel 13's `HasUlids` trait), applied
consistently across every model — not mixed with auto-increment ints.

Rationale:
- Report and transaction IDs are exposed in shareable URLs
  (`/reports/{report}`) and QR codes — ULIDs avoid leaking sequential
  counts (e.g. "how many transactions has this org logged") the way
  auto-increment IDs would.
- Sortable by creation time unlike UUIDv4, which matters for
  transaction/report listings.
- No coordination needed to generate client-side later (e.g. offline
  mobile writes), unlike auto-increment.

Every table has `id` (ULID PK), `created_at`, `updated_at`. Soft deletes
(`deleted_at`) are added only where the business requires recovering
deleted records — not by default. Likely candidates: `Event`,
`InventoryItem`. Financial tables (`FinancialTransaction`,
`FinancialReport`) are explicitly **not** soft-deleted — corrections go
through the approval/revision flow instead (§ Financial integrity),
never a delete.

## Money

Integer **rupiah** (no floating point, no minor-unit subdivision — IDR
has no commonly-used fractional unit in this domain). `amount` columns
are `bigint`, always positive; sign/direction comes from
`transaction_type`, not from the stored number. All aggregation
(balances, report totals) is computed in PHP/SQL integer arithmetic, never
floats.

## Core tables (Phase 1–4 scope)

```
organizations
  id, name, slug, require_transaction_approval (bool),
  public_transparency_enabled (bool, default false), settings (jsonb),
  created_at, updated_at

organization_memberships
  id, organization_id fk, user_id fk, role (enum), created_at, updated_at
  unique(organization_id, user_id)

events
  id, organization_id fk, title, description, location,
  start_at, end_at, pic_membership_id fk -> organization_memberships nullable,
  status (enum), lifecycle_stage (enum), created_by fk -> users,
  created_at, updated_at
  index(organization_id, status)
  index(organization_id, start_at)

event_committees
  id, event_id fk, membership_id fk -> organization_memberships,
  role_title nullable, created_at, updated_at
  unique(event_id, membership_id)

event_tasks
  id, event_id fk, title, description,
  assignee_membership_id fk -> organization_memberships nullable,
  status (enum), priority (enum), due_date, created_by fk -> users,
  created_at, updated_at
  index(event_id, status)

event_participants
  id, event_id fk, membership_id fk, status, created_at, updated_at
  unique(event_id, membership_id)

announcements
  id, organization_id fk, title, body, published_at nullable,
  created_by fk -> users, created_at, updated_at
  index(organization_id, published_at)

financial_accounts
  id, organization_id fk, name, created_at, updated_at
  unique(organization_id, name)

financial_categories
  id, organization_id fk, name, transaction_type (enum: INCOME/EXPENSE
  — never TRANSFER, which needs no category), created_at, updated_at
  unique(organization_id, name, transaction_type)

financial_transactions
  id, organization_id fk, financial_account_id fk,
  related_account_id fk -> financial_accounts nullable (TRANSFER
  destination only), category_id fk -> financial_categories nullable
  (required for INCOME/EXPENSE, absent for TRANSFER — enforced in the
  Form Request, not the DB), event_id fk nullable,
  amount (bigint, check amount > 0 — pgsql only, see decisions.md
  ADR-0011), transaction_type (enum: INCOME/EXPENSE/TRANSFER),
  status (enum: DRAFT/PENDING/APPROVED/REJECTED, default DRAFT),
  description, transaction_date, created_by fk -> users,
  reviewed_by fk -> users nullable, reviewed_at nullable,
  created_at, updated_at
  index(organization_id, financial_account_id, status)
  index(organization_id, transaction_date)
  index(organization_id, event_id)

member_dues
  id, organization_id fk, membership_id fk, period (date, first-of-month
  for monthly dues), amount_due (bigint, check > 0 — pgsql only),
  type (enum: MONTHLY/EVENT/SPECIAL/DONATION), created_at, updated_at
  unique(organization_id, membership_id, period, type)

member_payments
  id, member_due_id fk, financial_transaction_id fk -> financial_transactions
  nullable, amount (bigint, check > 0 — pgsql only), paid_at,
  created_at, updated_at

audit_logs
  id, organization_id fk, actor_id fk -> users nullable, action (string,
  free-form e.g. "transaction.approved" — not an enum; grows across
  domains over time), model_type, model_id, previous_values (jsonb
  nullable), new_values (jsonb nullable), created_at (no updated_at —
  append-only)
  index(organization_id, model_type, model_id)
  index(organization_id, created_at)

financial_reports
  id, organization_id fk, period_start, period_end, title,
  status (enum: DRAFT/PUBLISHED/ARCHIVED, default DRAFT),
  visibility (enum: PRIVATE/MEMBERS/PUBLIC, default MEMBERS),
  report_type (enum: MONTHLY/EVENT/ANNUAL/CASH_FLOW/MEMBER_DUES — a
  label only for now, all types compute identically; see decisions.md),
  opening_balance (bigint), total_income (bigint), total_expense (bigint),
  closing_balance (bigint) — all four always computed by
  FinancialReport::calculateFigures(), never hand-typed,
  published_at nullable, published_by fk -> users nullable,
  created_by fk -> users, created_at, updated_at
  index(organization_id, status)
  index(organization_id, period_start)

financial_report_revisions
  id, financial_report_id fk, revision_number (int),
  snapshot (jsonb), created_by fk -> users, created_at (no updated_at —
  append-only)
  unique(financial_report_id, revision_number)

report_share_logs
  id, financial_report_id fk, channel (enum: WHATSAPP/WEB/PDF),
  shared_by fk -> users nullable (an anonymous guest can share too),
  shared_at (no created_at/updated_at — this is the only timestamp)
  index(financial_report_id, shared_at)

attendance_sessions
  id, event_id fk, opened_at, closed_at, created_at, updated_at

attendances
  id, attendance_session_id fk, membership_id fk, status, recorded_at
  unique(attendance_session_id, membership_id)

votes
  id, organization_id fk, event_id fk nullable, question,
  is_anonymous (bool), start_at, end_at, created_by fk -> users,
  created_at, updated_at

vote_options
  id, vote_id fk, label, sort_order

vote_responses
  id, vote_id fk, vote_option_id fk, membership_id fk, created_at
  unique(vote_id, membership_id)

inventory_items
  id, organization_id fk, name, category, quantity (int), condition,
  location, created_at, updated_at

inventory_loans
  id, inventory_item_id fk, borrower_membership_id fk,
  status (enum: BORROWED/RETURNED/OVERDUE), borrowed_at, due_at,
  returned_at nullable

sponsors
  id, organization_id fk, name, contact, created_at, updated_at

sponsor_contributions
  id, sponsor_id fk, event_id fk, package, expected_amount (bigint),
  actual_amount (bigint) nullable, agreement_notes, created_at, updated_at

documents
  id, organization_id fk, event_id fk nullable, type (enum), title,
  file_path, created_by fk -> users, created_at, updated_at

announcements
  id, organization_id fk, title, body, published_at, created_by fk -> users,
  created_at, updated_at

audit_logs
  id, organization_id fk, actor_id fk -> users, action, model_type,
  model_id, previous_values (jsonb) nullable, new_values (jsonb) nullable,
  created_at
```

## Constraints & invariants enforced at the DB level

- `financial_transactions.amount`, `member_dues.amount_due`,
  `member_payments.amount`, `financial_accounts`-derived balances: `CHECK
  (amount > 0)`.
- `organization_memberships`: `UNIQUE (organization_id, user_id)`.
- `vote_responses`: `UNIQUE (vote_id, membership_id)` — one vote per
  eligible member.
- `attendances`: `UNIQUE (attendance_session_id, membership_id)` — no
  duplicate check-ins.
- `financial_report_revisions`: `UNIQUE (financial_report_id,
  revision_number)`, monotonically increasing.
- `membership_exit_requests`: a **partial** unique index on
  `membership_id WHERE status = 'PENDING'` — at most one undecided
  request per membership, so a double-tapped submit can't queue two
  decisions for the chair, while a member who was rejected once can
  still ask again later. A plain unique index would have forbidden the
  second request outright, which is the wrong rule.
- All tenant-owned tables: `organization_id` is `NOT NULL` with a foreign
  key to `organizations`, and every query path goes through a global
  Eloquent scope keyed off the resolved active organization (see
  `security.md`) rather than a client-supplied value.

## Indexing approach

Indexes are added for actual access patterns identified per phase, not
speculatively:
- Every tenant-owned table: index on `organization_id` (usually composite
  with the next most common filter, e.g. `status` or `transaction_date`,
  as listed above).
- Foreign keys are indexed by default via Laravel's `foreignId()`.
- No indexes are added ahead of a known query in Phase 2–4; revisited in
  Phase 8 (production hardening) against real query patterns.
