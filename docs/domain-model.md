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

**OrganizationInvite** (added 13 Sep 2026, ADR-0021)
- A shareable join link: `organization_id`, unique 40-char `token`,
  `created_by`, `expires_at` (7 days by default), nullable `max_uses`,
  `uses`, nullable `revoked_at`.
- `isActive()` is the single gate: not revoked, not expired, and under its use
  limit. Expiry and revocation are what make "holding the link is
  authorization" safe.
- Always grants `ANGGOTA`, never more — otherwise a forwarded WhatsApp message
  becomes privilege escalation.
- Acceptance reuses `AddMemberAction::forUser()`, so the 30-day soft-delete
  restore logic lives in one place rather than being duplicated.

**MembershipExitRequest** (added 13 Sep 2026, ADR-0020)
- A member's request to leave, awaiting the chair's decision — leaving is
  never unilateral. `organization_id`, `membership_id`, optional `reason`,
  `status` (`PENDING`/`APPROVED`/`REJECTED`), `decided_by`, `decided_at`,
  optional `decision_note`.
- The membership stays fully active while `PENDING`. Approval soft-deletes
  it, reusing the same 30-day "Keluar" retention described under
  `OrganizationMembership` below; rejection changes nothing and the member
  may ask again.
- A partial unique index (`WHERE status = 'PENDING'`) allows at most one
  undecided request per membership — a double-tapped submit can't queue two
  decisions, while a rejected member can still re-request.
- `KETUA` cannot request: an organization is never left chairless, so the
  chair hands the role over first (`TransferChairAction`) — the same rule
  that already blocks removing a chair.

**OrganizationMembership**
- Joins `User` ↔ `Organization` with a `role`.
- Roles: `KETUA`, `BENDAHARA`, `SEKRETARIS`, `ANGGOTA` — migrated from the
  original six-role set (`OWNER`/`ADMIN`/`TREASURER`/`COMMITTEE`/`MEMBER`/
  `RESIDENT`, see `decisions.md`) to match the mobile design's
  authoritative role table (`mobile-ux.md` § Roles and authorization).
  `KETUA` is the sole chair (one per organization, transfer-only —
  never granted through the general member/role endpoints). Roles are
  hierarchical, not independent bundles: `User::isChairOf()` is `KETUA`
  only, `isTreasurerOf()` is `KETUA`/`BENDAHARA`, `isSecretaryOf()` is
  `KETUA`/`SEKRETARIS` — the chair inherits every other role's abilities.
  "Panitia" (event committee) is **not** a stored org-level role; it's
  derived per-event from `EventCommittee`, matching mobile-design-system.md's
  "per event, temporary" scope.
- Soft-deleted (`SoftDeletes`), added 2026-08-26 — a justified exception
  to the project's default of no soft deletes (CLAUDE.md): mobile
  screen 12's edge case shows a departed member with a "Keluar" tag for
  30 days before dropping off, and their historical transactions/dues
  must stay intact (mobile-ux.md § Open product decisions: "the design
  keeps their historical transactions and dues records"). `roleIn()`/
  `membershipIn()` on `User` never see a departed member — Eloquent's
  default global scope excludes trashed rows, so every existing Policy
  check kept working unmodified. Only `MemberController::index`
  deliberately reaches past that scope (`withTrashed()`, filtered to
  `deleted_at >= now()->subDays(30)`) to surface the tag; the DB-level
  `unique(organization_id, user_id)` constraint doesn't know about soft
  deletes, so `AddMemberAction` restores a trashed row instead of
  inserting when re-adding someone who left within the window.
- The chair role moves only via `TransferChairAction`
  (`POST /members/{member}/transfer-chair`, chair-only) — the outgoing
  chair drops to `ANGGOTA`. The docs don't specify a landing-role choice
  for the outgoing chair, so this is a documented default, not an
  invented UI flow.
- `User.phone` / `User.show_phone_to_members` (added 2026-08-26): opt-in
  contact visibility per mobile-ux.md's explicitly-flagged open
  decision. Pengurus always see a member's phone regardless of the
  opt-in flag (`User::phoneVisibleTo()`); an ordinary member sees
  another ordinary member's phone only if they opted in. Updated via
  `PATCH /api/v1/profile/phone-visibility` (user-level, not
  organization-scoped) — deliberately narrow, not a general profile-edit
  endpoint.
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
  APPROVED (no reviewer) otherwise. Approve/reject requires the chair
  (`isChairOf()`) and is never the preparer themselves. APPROVED/REJECTED are locked —
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
- `notified_at` (added 2026-08-26) — set only via
  `NotifyDuePaymentAction` (`POST /finance/dues/{due}/notify`, the
  member's own due only), when they tap "Beri tahu bendahara"
  (mobile-screens.md § 17). **Never** marks the due paid by itself —
  `isAwaitingConfirmation()` is `notified_at !== null && amountPaid() ===
  0`, a state distinct from `isPartiallyPaid()` (a treasurer already
  recorded *something*, just not the full amount). Only a treasurer's
  own `RecordDuePaymentAction` call ever sets `isPaid()` true — this
  asymmetry (mobile-ux.md § Dues are recorded, not collected) is what
  keeps the ledger trustworthy. Notifying sends
  `MemberDuePaymentNotified` to the treasury team synchronously (not the
  doc's "batched daily" — that needs its own aggregation job, not built).
- `is_exempt` (added 2026-08-26) — drives the "Dibebaskan" display
  state; no screen currently offers an affordance to *set* it (not
  specified anywhere in the design docs), so it exists for correctness
  of the display path once one does, not as a finished feature.
- `MemberPayment.method` (`MemberPaymentMethod`: `TUNAI`/`TRANSFER`),
  `.note`, `.recorded_by` (added 2026-08-26) — needed for both the
  "Catat pembayaran iuran" sheet's fields and Iuran Saya's "Riwayat"
  (amount, date recorded, who recorded it). `MemberDue::latestPayment()`
  summarizes a period by its most recent installment when several exist
  (e.g. a partial then a final payment) rather than listing each one.

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
  a DRAFT is always treasury-only (`isTreasurerOf()`: chair/treasurer)
  regardless of what `visibility` is set to.
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

## Voting — built (Phase 7, API-only; no creation endpoint)

**Vote** — `organization_id`, `question`, `description`, `anonymous`
(bool), `editable` (bool — can the member change their choice before
close), `max_selections` (int, default 1 — multi-select support),
`eligible_scope` (`VoteEligibleScope`: `ALL`/`PENGURUS` —
mobile-screens.md §27's "Voting ini hanya untuk pengurus." permission
note implies some votes restrict eligibility), `event_id` (nullable),
`start_at`/`end_at`. `isOpen()` is derived from `now` against those two
timestamps — there's no separate stored status.

**VoteOption** — `vote_id`, `label`, `position`.

**VoteResponse** — `vote_id`, `vote_option_id`, `membership_id`,
`unique(vote_id, membership_id, vote_option_id)`.
- `membership_id` **is always stored, even for anonymous votes** — an
  editable anonymous vote needs it to find and replace a member's prior
  selection when they change their mind before close. Anonymity
  ("Daftar pemilih tidak disimpan") is enforced entirely at the read
  layer: no API response ever joins a response back to an identity for
  an anonymous vote, for any viewer, including the chair
  (`VotePolicy::viewBreakdown()`, `VoteResultResource`) — see ADR-0017's
  sibling decision below for why this is a read-layer guarantee rather
  than a storage-layer one.
- Changing a vote (editable only) deletes the member's prior response
  rows and inserts new ones (`SubmitVoteResponseAction`) — not a diff.
- Results suppress percentages (count-only) when an anonymous vote has
  fewer than 3 voters, to avoid narrowing a ballot down to a
  near-identifiable individual (screen 28 edge case).
- **Not built**: the vote-creation flow. `mobile-ux.md` § Open product
  decisions leaves "who may create a vote, and with what options" as a
  still-open human decision — votes exist today only via
  factories/tinker.

## Inventory — built (Phase 7, API-only)

**InventoryItem** — `organization_id`, `name`, `category` (`InventoryCategory`:
`SOUND_SYSTEM`/`KURSI_MEJA`/`TENDA`/`OLAHRAGA`/`LAIN_LAIN`), `quantity`,
`condition` (`InventoryCondition`: `BAIK`/`PERLU_PERBAIKAN`/`RUSAK`),
`location`, `notes`, `last_checked_at`, `responsible_membership_id`
(nullable), `created_by`.
- `availableQuantity()` = `quantity − borrowedQuantity()`, forced to `0`
  whenever `condition` is `RUSAK` (screen 18 edge case — a damaged item's
  whole quantity is excluded, not just a per-unit count the schema
  doesn't track).
- Reducing `quantity` below what's currently on loan is rejected at the
  request-validation layer, not silently allowed to go negative.
- Deleting an item with any `BORROWED` loan is rejected (422), not a
  Policy-level 403 — it's a business-state block, not a permission one.

**InventoryLoan** — `organization_id`, `inventory_item_id`,
`borrower_membership_id`, `event_id` (nullable), `quantity`, `status`
(`InventoryLoanStatus`: `BORROWED`/`RETURNED` only — see below),
`purpose`, `borrowed_at`, `due_date`, `returned_at`,
`returned_quantity`/`returned_condition`/`return_note` (all nullable
until returned).
- `OVERDUE` is **not** a stored status — the master prompt's three-state
  list (§33) is derived, not persisted: `isOverdue()` is
  `status === Borrowed && due_date->isPast()`. Storing it would need a
  scheduled job to flip it at exactly the right moment for no behavioral
  benefit over computing it on read.
- Returning with a worse condition than the item's current recorded
  condition requires a note (enforced in `ReturnInventoryLoanRequest`,
  not the database), and updates the item's own `condition` — but only
  if the returned condition is *worse* than the item's current one, so
  one unit's damaged return can't be silently overwritten by another
  unit's later, unrelated healthy return.
- "Pengurus" (create/update/delete the item) is any role except
  `ANGGOTA` (`User::isPengurusOf()`) — borrowing/returning is open to any
  member; only the identity of who may *return* a specific loan is
  gated (the borrower, or a pengurus on their behalf).

## Sponsors — built (Phase 7, API-only)

**Sponsor** — `organization_id`, `name`, `contact_name`, `contact_phone`,
`notes` — the entity/contact, reusable across contributions.

**SponsorContribution** — `organization_id`, `sponsor_id`, `event_id`
(nullable), `type` (`SponsorType`: `UANG`/`BARANG`/`JASA`), `status`
(`SponsorContributionStatus`: `DIAJUKAN`→`SETUJU`→`DITERIMA`, with
`BATAL` reachable from any non-terminal state — enforced server-side in
`UpdateSponsorContributionStatusRequest`, not just the UI), `amount`
(nullable — only for `UANG`), `description` (nullable — the in-kind
equivalent of `amount`), `financial_transaction_id` (nullable).
- One `Sponsor` can have many `SponsorContribution`s (screen 24's
  "Riwayat dukungan") — creating a contribution finds-or-creates the
  Sponsor by name within the organization
  (`RecordSponsorContributionAction`); the "this sponsor already exists"
  prompt (screen 25) is a soft mobile-side confirmation, not a backend
  rule, so a matching name is never refused.
- Marking `DITERIMA` on a cash sponsorship optionally records the
  matching income transaction, created as `DRAFT` and run through the
  normal `SubmitTransactionAction` approval workflow — never hard-set to
  `APPROVED`, so a sponsor's cash can't bypass
  `require_transaction_approval` just because it originated here.
- Contact details (`contact_name`/`contact_phone`/`notes`) are only
  visible to `isTreasurerOf()` (KETUA/BENDAHARA) — amount/type/status/
  event are transparency, visible to every member.

## Uploads — general-purpose file store (2026-08-26)

**Upload** — `organization_id`, `disk`, `path`, `original_name`,
`mime_type`, `size_bytes`, `uploaded_by`. Accepts `jpg`/`jpeg`/`png`/`pdf`
only, max 10MB. Deliberately generic — a single `POST /uploads` +
`GET /uploads/{upload}` pair any caller can use to store a file and get
back a stable, authorized retrieve/preview URL, without inventing a new
attachment table per feature. Distinct from
`FinancialTransactionAttachment` and `Document`, which stay
domain-specific (their own FKs and authorization rules); this exists for
callers — event photos, sponsor evidence, and similar — that don't need
a bespoke table.
- Backed by the app's default disk, which is MinIO/S3 in every
  environment (`FILESYSTEM_DISK=s3` in `.env`, pointed at the `minio`
  service in `docker-compose.yml`) — verified end-to-end against the
  running container: upload lands in the bucket, retrieval returns the
  identical bytes.
- Retrieval streams the file with `Content-Disposition: inline` (via
  Laravel's `Storage::response()`), not `download()`'s forced
  `attachment` — the point is preview, not save-to-disk. **Never trusts
  the stored `mime_type` blindly when serving it back** (caught by
  automated security review, fixed same session): the response
  Content-Type is checked against a hardcoded allowlist
  (`image/jpeg`/`image/png`/`application/pdf`) — anything else falls
  back to `application/octet-stream` + forced `attachment`, and
  `X-Content-Type-Options: nosniff` is always sent. Upload-time
  validation uses both `mimes` (extension→MIME mapping) and
  `mimetypes` (the file's actual sniffed content type) so a renamed file
  can't spoof one of the four accepted types either.
- No public URL is ever exposed — same rule as every other private file
  in the app (master prompt §25): the only way to a file's bytes is the
  authorized `GET /uploads/{upload}` endpoint.

## Superadmin — platform-level, read-only (2026-08-26)

**`User.is_superadmin`** — boolean, default `false`, not org-scoped and
not a value in `OrganizationRole` (which is deliberately per-membership;
see ADR-0017). Not in `$fillable` — the only way to set it is
`User::forceFill()`, used solely by two Artisan commands
(`superadmin:grant {email}` / `superadmin:revoke {email}`, both requiring
interactive confirmation). No HTTP endpoint can grant or revoke it.

A superadmin has no `OrganizationMembership` anywhere by definition, so
none of the existing per-org routes' `current-org` resolution can ever
apply to them — this is a wholly separate, read-only surface rather than
an extension of the RBAC used elsewhere in the app (see ADR-0018 for the
full rationale). `EnsureSuperadmin` middleware gates
`/api/v1/superadmin/*`; `Api\V1\Superadmin\OrganizationController`
(`index`/`show`) is the only implementation so far, covering
Organizations, Members, and Financial Reports (including `DRAFT`/
`PRIVATE` reports — full visibility is the point) plus the transparency
summary. Every `show` call writes an `AuditLog` row
(`superadmin.viewed_organization`) since this bypasses tenant isolation
on purpose. Documents, Sponsors, Votes, and Inventory are not yet
covered — extend the same controller (or a sibling) if a real need
arises.

A web (Inertia) panel exists at `/superadmin/organizations` (list) and
`/superadmin/organizations/{id}` (detail) — `Superadmin\OrganizationController`
in the web namespace, same `ViewOrganizationOverviewAction` as the API
controller so both surfaces stay behaviorally identical. No mobile
surface — deliberately out of scope, see ADR-0018.

## Documents & announcements

**Document** — built (Phase 7, API-only): `organization_id`, `title`,
`category` (`DocumentCategory`: `PROPOSAL`/`NOTULEN`/`LAPORAN`/`SURAT`/
`ORGANISASI`), `disk`/`path`/`original_name`/`mime_type`/`size_bytes`
(same private-storage shape as `FinancialTransactionAttachment`),
`event_id` (nullable), `uploaded_by`, `archived_at` (nullable). One
model, not a separate `Document`/`EventDocument` pair — mobile-screens.md
§21/22 describe a single "Dokumen" list with an optional link to an
event, not two domains. **Not yet built**: published `FinancialReport`s
are supposed to surface in this same list automatically as read-only
entries (§21, "they are not separate uploads") — the API doesn't merge
that in yet; deferred until the actual Dokumen mobile screen is built.
- Upload is `KETUA`/`SEKRETARIS`, or `BENDAHARA` restricted to
  `category=LAPORAN` only (screen 21's exact wording).
- Delete is the uploader, or any pengurus.

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
