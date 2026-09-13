# Next Up

A single prioritized "what to pick up next" list, pulled from
`backend-backlog.md`, `web-backlog.md`, `mobile-e2e-backlog.md`, and the
three `*-bug-tracker.md` files as of 2026-08-26. Those five docs remain
the source of truth for detail and rationale — this file just orders
their still-open items into one queue so a session doesn't have to
cross-reference five files to decide what's next. Update it whenever an
item here gets resolved or a new gap is logged in one of the source
docs, the same way `mobile-e2e-backlog.md` was updated when
"kegiatan diikuti" closed.

No open bugs exist right now — `backend-bug-tracker.md`,
`web-bug-tracker.md`, and `mobile-bug-tracker.md` each have an empty
**Open** table (placeholder row only). Everything below is backlog
(missing feature), not a defect.

**As of 2026-09-13**: see the dated entry below for the five items just
closed. Still deferred: Undang Anggota (invite architecture), mobile
chair-side exit approval (needs a design decision), and Phase 8's
infrastructure remainder. `docs/under-construction.md` is now the live
list of unfinished surfaces.

**As of 2026-08-28: nothing is queued.** Every item that was open (web
backlog, Attendance, mobile signup/org-switching/Buat Organisasi,
Activity points, vote creation, Phase 8's actionable subset) closed
this session — see the dated entries below for what each one actually
shipped. What's left is genuinely deferred, not forgotten: Undang
Anggota (needs an invite/auth architecture decision — the same one
that also blocks a full Buat Organisasi flow) and Phase 8's
infrastructure-only remainder (OpenTelemetry, backup automation,
caching — the last deliberately not added, see its own entry for
why). The next session should start by asking the user what they want
next, not by assuming this file still has a queue.

## 2026-09-13 — Five reported bugs closed: date pickers, keyboard handling, org-creation rule, leave-with-approval, stub audit

From a device-testing session. Two were UI defects, two were business
rule changes needing product decisions, one was an audit.

- **Date pickers (mobile)** — `BuatKegiatan` (tanggal, jam mulai, jam
  selesai) and `InventarisDetail` (tanggal kembali) were free-text
  `YYYY-MM-DD`/`HH:mm` fields with a numeric keyboard. New `DateField`
  wraps `@react-native-community/datetimepicker` (the OS dialog, not a
  custom calendar) and keeps the same wire format, so only the input
  mechanism changed. Web was already using native `type="date"`/
  `datetime-local` across 17 fields — no change needed there.
  **Needs `npx expo run:android`** (native module).
- **Keyboard covering the UI (mobile)** — there was *no* keyboard
  handling anywhere in the app. `BottomSheet` (where it was first hit,
  on Pinjam barang) now lifts with the keyboard and scrolls internally,
  capped at 90% height; a new `KeyboardAwareScroll` replaced the plain
  outer `ScrollView` on six form screens. `softwareKeyboardLayoutMode:
  resize` made explicit in `app.json` since the iOS/Android split in
  that component depends on it.
- **Org creation restricted (ADR-0019)** — taken literally, "only Ketua
  may create" dead-ends signup, since a new user holds no role. Split:
  first org open to anyone, second is KETUA-only. Policy-enforced (both
  surfaces route through the shared Form Request), page route gated,
  `canCreate` returned by both org-list endpoints so neither client
  re-derives it.
- **Leave-with-approval (ADR-0020)** — the flow didn't exist at all; the
  mobile dialog's `onConfirm` just closed itself. Built
  `MembershipExitRequest` end-to-end: request/approve/reject, chair-only
  decisions, partial unique index for one pending request per
  membership, notifications both directions, approval soft-deleting the
  membership into the existing 30-day "Keluar" window. Chair cannot
  request without transferring the role first. **Mobile has no
  chair-side approval UI** — outside the 11 specified screens, needs a
  design decision.
- **Under-construction audit** — new `docs/under-construction.md`: one
  reachable stub route (Pengaturan) and three stub sheets, each with
  what actually blocks it. Web has none. Pruned eight dead
  `belumTersediaCopy` entries for screens that shipped long ago.

455 backend tests (up from 439), Pint/PHPStan level 7 clean; tsc/ESLint/
Vitest/`vite build` clean on web; tsc/`expo lint` clean on mobile.
**Not device-verified** — the mobile changes need a native rebuild, and
this device can't be driven over adb.

## 2026-08-26 — Backend Phase 7 built (API-only), unblocking the mobile build order

While scoping mobile screens 12–33 against the existing API, found the
backend didn't have what several screens needed — see ADR-0017 and the
domain-model.md sections for Inventory/Documents/Sponsors/Voting for the
full rationale. Closed in one session:

- **Role model migrated** (ADR-0017): `OrganizationRole` went from six
  values (`OWNER`/`ADMIN`/`TREASURER`/`COMMITTEE`/`MEMBER`/`RESIDENT`) to
  the mobile design's four (`KETUA`/`BENDAHARA`/`SEKRETARIS`/`ANGGOTA`).
  This was a prerequisite for everything else in Peran & Izin.
- **Member roles/removal, dues payment recording, organization
  creation** — these already existed as web business logic
  (`UpdateMemberRoleRequest`, `RecordDuePaymentAction`,
  `CreateOrganizationAction`); just needed API routes/controllers wired
  to the same Actions/Policies.
- **Inventory, Documents, Sponsors, Voting** — net-new domains, built
  API-only (migrations, models, policies, Form Requests, Actions,
  controllers, Resources, routes, Pest tests — 237 tests total, Pint/
  PHPStan clean, verified against real Postgres). No Inertia web pages
  yet for any of these four — a deliberate scope call to prioritize the
  mobile build; adding web pages later is purely additive since the
  business logic already lives in Actions/Policies, not controllers.
  *(Superseded: all four now have web pages under
  `laravel/resources/js/pages/`.)*
  Voting has no creation endpoint — `mobile-ux.md` leaves "who may
  create a vote" as an explicitly open product decision. *(Superseded
  2026-08-28: resolved to pengurus; `CreateVoteAction` and a
  `votes/create` page exist.)*

**Still not built** *(as of 2026-08-26 — all four have since shipped;
see the 2026-08-28 entries below)*: Attendance (no models at all),
Activity points, web pages for the four new domains, and the
vote-creation flow.

> **Reading this file**: entries are append-only and dated. A statement
> here describes what was true on its own date, **not** current state —
> this block is the worked example, since every item it lists was built
> two days later. Check the filesystem before repeating any "not built"
> claim from a dated entry. See `.claude/skills/release-readiness`.

## 2026-08-26 (later) — Mobile Step 1 built: Anggota, Anggota Detail, Peran & Izin

Scoping screens 12–14 against the API surfaced more backend gaps, closed
first: `User.phone` + opt-in `show_phone_to_members` (user chose
"add phone field, opt-in visibility" when asked — matches
mobile-ux.md's own flagged-open decision), `TransferChairAction`
(`POST /members/{id}/transfer-chair`), soft-delete + 30-day "Keluar"
retention on `OrganizationMembership` (the codebase's first soft
delete — justified per CLAUDE.md), `GET /members/{id}/responsibilities`
and `GET /members/{id}/activity`, `PATCH /profile/phone-visibility`.
247 tests passing (up from 237), Pint/PHPStan clean, verified against
real Postgres.

Then built `AnggotaScreen`, `AnggotaDetailScreen`, `PeranScreen` in
`mobile/src/screens/`, routed at `/anggota`, `/anggota/[id]`,
`/anggota/peran`. `ProfilScreen`'s "Anggota & peran" row now routes
there instead of the `belum-tersedia` stub. `tsc --noEmit` and
`expo lint` both clean — **not visually tested** (no Metro/simulator
run this session); a real device pass is still owed before calling
Step 1 fully done.

**Deferred**: Undang Anggota (screen 15) — user chose to defer it;
needs a real invite/auth architecture decision (phone-based invite +
join code vs. the app's current email-only auth) beyond one session's
scope. Its button currently opens an honest stub.

## 2026-08-26 (evening) — Ran the app on a physical device; Mobile Step 2 built; general Upload API added

Ran the mobile app end-to-end on a connected physical Android device
(`npx expo run:android`) per the user's request. First build hit a
transient Gradle build-cache race (filesystem hiccup under heavy
parallel compilation, not a code bug); retry succeeded in 11s. Verified
via logcat + the Laravel request log: login, Home, Kegiatan/event
detail, Kas, Notifikasi, Profil→Anggota (Step 1's new screens) all work
with zero crashes and zero server errors. `mobile-bug-tracker.md`'s
open B-003 entry didn't reproduce — `HomeScreen.tsx` already guards
every access with optional chaining; looks like a stale, already-fixed
row rather than a live bug.

Built Step 2 — `IuranScreen` (16) and `IuranSayaScreen` (17) + the
"Catat pembayaran iuran" sheet — after closing more backend gaps found
while scoping: `POST /finance/dues/generate-monthly` (API-exposed the
existing web action), `POST /finance/dues/{due}/notify` +
`notified_at` on `MemberDue` (a real "Menunggu konfirmasi" state,
distinct from a partial payment), `is_exempt` on `MemberDue` (data-only,
no UI to set it yet), `method`/`note`/`recorded_by` on `MemberPayment`,
and `GET /finance/categories` (was missing entirely). Also fixed a
vocab bug from Step 1 — "Sebagian" (partial payment) and "Menunggu
konfirmasi" (member notified, nothing recorded) had been conflated into
one status; added a `partial` status and a shared `resolveDuesStatus()`
helper, and fixed the two Step 1 screens using the old heuristic.
260 backend tests passing (up from 247), Pint/PHPStan clean, verified
against real Postgres *and* the live server via curl (full
notify→record-payment flow confirmed correct).

Also built, at the user's explicit request and separate from the
screen build order: a general-purpose **Upload API**
(`POST /api/v1/uploads`, `GET /api/v1/uploads/{upload}`) backed by
MinIO — verified end-to-end against the real running MinIO container
(not just mocked in tests): a file uploaded through the API lands in
the bucket, and retrieval returns byte-identical content, streamed
inline (previewable) rather than force-downloaded.

Resuming the mobile build order next: Pencarian (Step 3) → Buat
Kegiatan (Step 4) → Dokumen/Inventaris/Sponsor (Step 5) → Susun/Periksa
Laporan (Step 6) → Buat Organisasi (Step 7) → Voting (Step 8).

## 2026-08-26 (night) — Mobile Step 3 built; a security review caught a real Upload API gap

A background automated security review flagged that
`UploadController::show()` trusted the stored `mime_type` column when
setting the response `Content-Type` for inline preview — a stored-XSS
vector if that value were ever wrong. Fixed: the response type is now
checked against a hardcoded allowlist (jpeg/png/pdf), anything else
falls back to `application/octet-stream` + forced `attachment`,
`X-Content-Type-Options: nosniff` is always sent, and upload-time
validation now also checks the file's real sniffed MIME type
(`mimetypes`) alongside the extension mapping (`mimes`). Confirmed
`Document`/`FinancialTransactionAttachment` were never exposed to this
(they always force `attachment`, never `inline`). 261 tests passing.

Built Step 3 — `PencarianScreen` (32), wired to Anggota and Kegiatan
first per the build order. Needed zero backend changes: pure
client-side filtering over already-fetched member/event lists, since
neither endpoint paginates yet. Recent searches persist via
`expo-secure-store` (already linked — no new native dependency).

**The physical Android device disconnected mid-session** (adb lost it
entirely, `lsusb` shows nothing — a cable/connection issue, unrelated
to anything built this session), partway through Step 3 — so Step 3 is
type/lint-verified only, not re-confirmed live on-device the way Steps
1–2 were. Metro was left running standalone in the background so the
device can reconnect automatically once replugged.

## 2026-08-26 (later still) — Platform superadmin added (read-only, cross-organization)

User asked whether the app supports RBAC and requested a superadmin
role able to "control everything"; scoped down via AskUserQuestion to
platform-level, view-only across all organizations, for support/
moderation — see ADR-0018 and domain-model.md's Superadmin section for
the full design. `User.is_superadmin` (not `$fillable`; only settable
via `superadmin:grant`/`superadmin:revoke` Artisan commands, both
requiring confirmation), a new `EnsureSuperadmin` middleware, and a
separate `/api/v1/superadmin/*` route surface (outside `current-org`,
since a superadmin has no membership to resolve one from) covering
`GET organizations` (list) and `GET organizations/{id}` (full detail —
members, financial reports including `DRAFT`/`PRIVATE`, transparency
summary). Every detail view writes an `AuditLog` row. No write
endpoints exist; a superadmin still can't mutate any organization's
data. 272 tests passing (up from 260), Pint/PHPStan clean, verified
against real Postgres and live via curl (including a caught-and-fixed
JSON response-wrapping bug — nested Resource collections don't
auto-wrap in `{"data": [...]}` unless returned directly).

**Not yet covered by superadmin view**: Documents, Sponsors, Votes,
Inventory (same pattern extends easily later).

## 2026-08-26 (later still, part 2) — Superadmin web panel added; mobile deliberately out

Follow-up: user asked whether mobile/web should support RBAC too.
Answer scoped per-surface: web got a small Inertia panel
(`/superadmin/organizations`, `/superadmin/organizations/{id}`) since
superadmin is an ops/support tool; mobile did not, since it's out of
scope for the 11 member-facing design screens (CLAUDE.md: nothing
beyond those without asking first). Refactored the existing API
controller to share a new `ViewOrganizationOverviewAction` with the web
controller, so the fetch+audit-log invariant can't drift between the
two surfaces. The web detail page deliberately doesn't link report
titles to `/reports/{id}` — that page's `FinancialReportPolicy::view`
check would 403 a superadmin (no membership) for exactly the DRAFT/
PRIVATE reports this panel exists to show; changing that policy was
treated as out of scope. Sidebar shows a "Superadmin" link only when
`auth.user.is_superadmin`. 278 tests passing (up from 272), Pint/
PHPStan/tsc/eslint/prettier all clean, verified against real Postgres
and live in a browser (login → sidebar link → org list → org detail,
DRAFT/PRIVATE report rendering confirmed).

## 2026-08-27 — Mobile Step 4 built: Buat Kegiatan

Continued the mobile build order. Backend gap-check for the 3-step event
creation wizard (screen 29) surfaced a real authorization bug and several
missing fields, closed first: `EventPolicy::create` was `isChairOf()`
only — the design spec says "pengurus and the chair" may create events,
matching `isPengurusOf()` (already used elsewhere for this exact
"any organizer" gate); fixed. Added `category` (new `EventCategory`
enum), `sponsor_id` (simple FK — no amount/type picker in this screen,
unlike a full `SponsorContribution`), and budget tracking: `budget_amount`
upserts one linked DRAFT expense `FinancialTransaction` via a new
`budget_financial_transaction_id` FK on `events`, so repeated wizard
autosaves update the same planned-expense row instead of duplicating it
(account/category auto-selected — same simplification as the Iuran
sheet's account/category, and validated to require an existing
`FinancialAccount` first). `committees` can now be set on create/update
(a PATCH replaces the roster wholesale — the mobile client always
re-includes the actor's own membership so an edit that doesn't revisit
step 3 can't silently drop the organizer). Two new queued notifications:
`EventCommitteeAssigned` (fires once, on the DRAFT -> non-DRAFT
transition) and `EventRescheduled` (fires when start_at/location changes
on an already-published event) — draft-only edits notify no one. A past
`start_at` being published is forced server-side straight to `COMPLETED`
rather than `PLANNED`, mirroring the wizard's own confirmation dialog.
New `UpdateEventAction` (the web/API controllers' plain `$event->update()`
now goes through it, same as `CreateEventAction`). 12 new tests
(`EventWizardTest.php`), 291 backend tests passing total, Pint/PHPStan
clean, verified against real Postgres.

Built `BuatKegiatanScreen` (3 steps, `StepIndicator`) and wired
`KegiatanScreen`'s "+ Buat kegiatan baru" to it (previously an honest
stub). `tsc --noEmit` and `expo lint` both clean — **not visually tested
on a device this session** (no physical device connected; same
limitation Step 3 ended on). Deliberate simplifications, not blocking:
no native date/time picker is installed (same constraint noted for the
Iuran sheet — adding one needs a native rebuild), so step 2 collects
tanggal/jam as plain text fields instead of the spec's native picker;
the inventory-picker sheet ("Butuh barang inventaris?") and the optional
sponsor-link field are both omitted from this pass — the backend supports
`sponsor_id` already, just no picker UI yet; the Home screen has no
quick-action row for pengurus yet (screen 29's second entry point),
untouched this session; and the wizard does not implement true
per-field autosave — "Simpan draf" is an explicit action on every step
rather than firing automatically on step change/backgrounding, since the
backend's `start_at` NOT NULL constraint makes a literal step-1-only
draft impossible anyway.

## 2026-08-27 (later) — Mobile Step 5 built: Dokumen, Inventaris, Sponsor

Backend gap-check found one real gap: `InventoryItemController::show()` /
`InventoryItemResource` never exposed an item's loans, so the detail
screen's "Sedang dipinjam"/"Riwayat" sections had nothing to render —
added `loans` (latest 10, eager-loaded) to the resource. Everything else
in Inventory/Documents/Sponsors (built 2026-08-26 as API-only) held up
against the screen specs as-is — migrations, policies, Actions,
controllers were already correct, including business rules like "an
item with an active loan can't be deleted", "returning with a dropped
condition requires a note", and the sponsor status state machine
(Diajukan→Setuju→Diterima→Batal, cash Diterima optionally recording a
matching income transaction). 292 backend tests (up from 291), Pint/
PHPStan clean.

Built all 8 screens: `InventarisScreen`/`InventarisDetailScreen`/
`BarangFormScreen` (18-20), `DokumenScreen`/`DokumenDetailScreen`
(21-22), `SponsorScreen`/`SponsorDetailScreen`/`SponsorFormScreen`
(23-25), each as its own top-level route stack (`/inventaris`,
`/dokumen`, `/sponsor`), wired from Profil (Inventaris, Dokumen rows)
and Kas (new Sponsor row, matching the spec's "Iuran + Sponsor" pair).
`tsc --noEmit` and `expo lint` clean.

A follow-up design-doc audit (`mobile-ux.md`, `mobile-design-system.md`)
caught one real IA violation: `mobile-ux.md`'s "behind the avatar" list
(Profil's menu) doesn't include Sponsor at all — "Iuran and Sponsor live
inside Kas, not behind the avatar" — but `ProfilScreen` already had a
pre-existing Sponsor row (a stub from before this domain was built).
Removed it; Sponsor is reachable only from Kas now, matching the doc.
No color/radius/weight/money-formatting violations found across the new
screens (`radius: 0`, no hex literals, 400/600/800 weights only, every
money value through `formatRupiah`/`fontVariant: tabular-nums`).
Two smaller, non-blocking notes from the same audit: `InventoryItemPolicy`
(backend) allows any pengurus (incl. treasurer) to add/edit barang, but
`mobile-screens.md`'s screen-level spec says chair/secretary only —
mobile followed the narrower screen spec, which is correct for what
mobile *shows*, but the backend stays broader than the UI ever exercises
(not fixed, just noted); `DocumentCategory`'s closed vocab includes an
"Diarsipkan" (archived) status but there's no archive endpoint anywhere
in the API, so `DocumentItem`/`DokumenDetailScreen` never render it —
harmless today since the state is unreachable, but worth building if an
archive action is ever added.

**Document upload needed a real decision**: no file-picker library was
installed, and unlike the recurring "no date-picker" gap, there's no
text-input workaround for picking a file. Asked the user; chose to
install `expo-document-picker` + `expo-file-system` + `expo-sharing`
and do a native rebuild (`npx expo run:android`) — succeeded, and the
app is installed with all three linked. Backend upload/download was
verified end-to-end via curl against the real running server (a
multipart POST followed by a GET download came back byte-identical to
the source file) using the exact request shapes the mobile client sends
(`apiUpload` for multipart, `authorizedDownloadUrl` for the
Authorization-header download `expo-file-system`'s `File.createDownloadTask`
needs). **The physical device disconnected again before any of the 8
new screens could be visually verified** — same recurring issue as
Step 3's ending. The rebuild is done and won't need repeating; only a
reconnect + visual pass is owed.

**Deliberate simplifications, not blocking**: Document preview is
metadata + a type-mark box only, no real inline PDF/image rendering (no
`react-native-webview` or equivalent installed, and adding one for a
single screen's "nice to have" wasn't judged worth another rebuild
decision — ask first if this comes up again). "Bagikan" and "Unduh" on
Dokumen — Detail both resolve to the same download-then-open-native-
share-sheet action, since RN can't write to Downloads without Storage
Access Framework permissions; this is the practical equivalent on
Android (the share sheet's own "Save to Files" target). Sponsor —
Detail's "Ubah" secondary action (per the design spec) became a
status-advance flow instead of a generic field-editor, because the
backend only ever had a `PATCH .../status` endpoint, never a general
update — editing name/type/amount/contact after creation isn't
supported by the API and wasn't added, since nothing in the spec
actually required editing those fields once set. Inventaris — Detail's
borrow sheet's optional inventory item photo and the "Butuh barang
inventaris?" picker sheet on Buat Kegiatan (a cross-reference from Step
4) were both left out — no image picker wired to inventory yet, though
`expo-document-picker` could now cover it if asked for.

## 2026-08-27 (later still) — Mobile Step 6 built: Susun Laporan, Periksa Laporan

The report review workflow (`mobile-screens.md` § 30-31) needed real
backend work — `FinancialReportStatus` only had DRAFT/PUBLISHED/ARCHIVED,
with a chair able to publish a DRAFT directly. Added DIPERIKSA
(submitted for review) and DISETUJUI (approved, not yet public) as two
new intermediate states, additively — the existing DRAFT→PUBLISHED
direct-publish path (chair only) still works unchanged, so nothing broke
(all 8 pre-existing report tests still pass as-is). New actions:
`SubmitReportForReviewAction` (DRAFT→DIPERIKSA, notifies every chair),
`ApproveFinancialReportAction` (DIPERIKSA→DISETUJUI, chair only, never
the submitter — mirrors transaction-approval separation of duties),
`RequestReportRevisionAction` (DIPERIKSA→DRAFT with a required reason,
shown at the top of Susun Laporan next visit), `SaveReportNoteAction`
(persists the treasurer's note without changing status — the ghost
"Simpan draf" action), and `RevertDriftedReportAction` — a DISETUJUI
report whose transactions changed since approval (new approval, edit,
rejection) is caught on next view and reverted to DRAFT with a
system-authored reason, so approval is never silently stale. The "chair
composing their own report" shortcut from the spec ("approval step is
skipped... becomes 'Setujui & terbitkan'") needed no new code — a chair
already has direct DRAFT→PUBLISHED rights, so the mobile client just
calls the existing publish action instead of submit when the actor is
chair. 303 backend tests (up from 292), Pint/PHPStan clean, full
submit→approve→publish cycle verified end-to-end against the live
server and real Postgres.

Built `SusunLaporanScreen` (find-or-create this month's draft, incomplete-
transactions list, treasurer's note, submit/publish) and
`PeriksaLaporanScreen` (chair's approve/request-revision), wired from a
new "Kelola laporan" section on Transparansi (visible to treasurer/
chair) and from the two new notification types in `NotifikasiScreen`.
`tsc`/`expo lint` clean.

**A live device session surfaced two real bugs, unrelated to this
step's own code, found and fixed along the way**: (1) `HomeScreen` never
checked `transparency.isError`/`events.isError` — on any fetch failure
it silently rendered `RpNaN` instead of an error state (every other
screen in the app already guards this; Home was the one exception).
Added the same `ErrorState` + retry pattern used everywhere else. (2)
The `composer dev` Laravel server was bound to `127.0.0.1` only
(`php artisan serve` with no `--host` flag), unreachable from any
physical device — restarted bound to `0.0.0.0`. Root cause of *this
session's* on-device testing being blocked, though, was neither of
those: the device's stored auth token for its logged-in user had gone
stale (confirmed via a temporary debug log showing 401 Unauthenticated
on every request — most screens masked this behind `?? []`/`?? 0`
fallbacks, which is what made Home's unguarded NaN the only visible
symptom). Fixing this needs an interactive login on-device, which
wasn't possible this session — the connected device rejects `adb shell
input tap` (`SecurityException: … requires INJECT_EVENTS permission`),
so no UI interaction was possible, only screenshots/logcat. **Steps 4-6
mobile screens are still only type/lint-verified, not visually
confirmed on-device** — same standing gap as every step since Step 3,
now with a clear next action: log in fresh on the device (Warga or any
seeded user), then a real visual pass is unblocked. Laravel now runs
bound to `0.0.0.0`; `mobile/.env.local` still points at the LAN IP
(reverted after testing `127.0.0.1` via `adb reverse` — both work, LAN
IP was kept for WiFi-only testing consistency with prior sessions).

## 2026-08-27 (later still) — Susun Laporan dual-role bug, guard-ordering bug, and a dead LAN-IP hang

Live-verifying the Step 6 chair shortcut ("Setujui & terbitkan" instead
of "Kirim untuk diperiksa") found `isDualRoleShortcut = canPublish &&
!canSubmit` could never be true: a DRAFT report's `canPublish` is only
ever true for the chair, and a chair is always also a treasurer in this
4-role model, so `canSubmit` is true for them too — the two are never
mutually exclusive. Fixed to `isDualRoleShortcut = canPublish` alone.

Re-verifying that fix on a cold app launch surfaced a second, unrelated
bug: `SusunLaporanScreen`'s `isTreasurer` check read
`organization.data?.membership.role` *before* any
`organization.isPending` guard, so on a fresh launch (role still
`undefined`) a genuine treasurer/chair briefly saw "Hanya bendahara yang
menyusun laporan kas." instead of the editor. Same pattern found and
fixed in five more screens — `PeriksaLaporanScreen`,
`SponsorDetailScreen`, `IuranScreen`, `PeranScreen`,
`BuatKegiatanScreen` (see the mobile-role-guard-ordering-bug memory for
the full list and the screens deliberately left alone because they only
gate an inline button, not the whole screen).

While chasing what looked like a third bug (the fix "not taking"), the
real cause turned out to be infrastructure: `mobile/.env.local`'s LAN-IP
API URL (`192.168.51.113`) had gone silently unreachable mid-session —
every query hung forever with zero errors anywhere (RN's `fetch` has no
default timeout). Confirmed via comparing `127.0.0.1:8000/up` (instant)
against the LAN IP (hung) in the device's own browser — independent of
the app entirely. Switched `.env.local` to
`EXPO_PUBLIC_API_URL=http://127.0.0.1:8000/api/v1` (the USB-tunnel path
this device's workflow already uses per the README) and restarted Metro
with `--clear`. All three fixes verified live on-device afterward:
Susun Laporan renders correctly on cold launch, no permission-note
flash, and "Setujui & terbitkan" shows correctly for the chair.
`tsc`/`expo lint` clean throughout.

## 2026-08-27 (later still) — Org chart added to Peran

User asked for a new "org chart based on org hierarchy" section on the
Anggota/Peran screens — not in `mobile-screens.md`'s spec for either
screen, so scoped it with the user first: placed on Peran (screen 14,
the roles screen) rather than Anggota, showing names/avatars per tier
rather than just role/count boxes. New `OrgChart` component
(`mobile/src/components/OrgChart.tsx`) draws a 3-tier tree — Ketua,
then Bendahara/Sekretaris side by side, then Anggota as a single
tappable node — using only plain `View` borders for the branch
connectors (flex-based, no absolute positioning or measurement, so it's
resilient to any screen width). The role model is flat (§ `docs/decisions.md`
ADR-0017), so this is always exactly three tiers, never a deeper
reporting chart. Bendahara/Sekretaris each render as a fixed column
even when vacant ("Belum ada") since only KETUA is enforced unique —
Bendahara/Sekretaris can have 0, 1, or several holders, and the chart
handles all three (solo node, vacant placeholder, or an avatar-group
node showing "N orang"). Tapping the Anggota node jumps to `/anggota`
pre-filtered to the Anggota tab — required adding optional
`useLocalSearchParams<{ filter }>()` support to `AnggotaScreen` (it
previously only accepted a `filter` param when forwarding *to*
Pencarian, never when receiving one itself). Verified live on-device in
both light and dark theme; the tree-connector geometry lines up exactly
with each node's center in both. `tsc`/`expo lint` clean.

## 2026-08-27 (later still) — MultiOrganizationSeeder for tenant isolation / RBAC / superadmin testing

User asked for a seeder for real cross-org testing: multiple
organizations, >10 members each, verifying superadmin sees everything,
no cross-org data leakage, and correct RBAC on web/mobile. Added
`database/seeders/MultiOrganizationSeeder.php` — NOT wired into the
default `DatabaseSeeder` (that stays the fast single-org fixture most
testing this session already depends on); run explicitly with
`php artisan db:seed --class=MultiOrganizationSeeder`. Creates 3 orgs
(14/15/16 members respectively — 11+ Anggota each either way "anggota"
is read), each independently seeded across every domain: events
(committee/tasks/participants), finance (accounts, categories, an
approved transaction, one left PENDING to exercise the approval queue,
monthly dues with some paid), a financial report — deliberately at a
*different* review-workflow stage per org (org1 PUBLISHED, org2
DIPERIKSA, org3 DISETUJUI) so all three states have live data,
announcements, inventory, a sponsor + contribution, a document, and a
vote with responses. Logins are predictable per org
(`ketua@org1.test`, `bendahara@org2.test`, `anggota3@org3.test`, …,
password `password`) specifically so the user can manually flip between
orgs and check isolation by hand. Also seeds a plain
`superadmin@rukunmuda.test` user, deliberately NOT auto-granted —
`superadmin:grant` requires an interactive confirm by design (see
[[superadmin-feature]]), so the README instructs running it separately
after seeding.

**Verified all three of the user's stated expectations against the
real running server, not just the DB**: (1) superadmin sees all 4 orgs
(the 3 new + the pre-existing single-org fixture) with correct member
counts via `GET /api/v1/superadmin/organizations`; (2) a real bearer
token for org2's ketua correctly gets 403 when requesting org1's report
by ID directly (`ReportController::authorizeView`'s tenant check —
the exact code path fixed earlier this session — held up under a real
cross-org attempt); (3) org2's ketua's own `/organizations/current`,
`/members` (15, all `@org2.test`), and `/finance/reports` calls
returned only org2's data. 304 backend tests still pass, Pint/PHPStan
clean on the new file.

## 2026-08-27 (later still) — Show/hide password on mobile's email login

Mirrored the web's `PasswordInput` show/hide toggle (added earlier this
session to `login.tsx`/`register.tsx`) onto mobile's
`LoginEmailScreen.tsx` — the only password field in the mobile app
(the design-verified `LoginScreen.tsx` OTP flow has no password step).
A `showPassword` boolean flips `secureTextEntry`, with a "Tampilkan"/
"Sembunyikan" text toggle (same Indonesian wording as the web
component's `aria-label`) inside the input box — no icon library in
mobile, so text instead of the web's eye/eye-off icon, consistent with
this app's existing icon-free convention (plain glyphs like "⌕", "▾"
elsewhere). `tsc`/`expo lint` clean; rendered correctly on-device,
though the actual tap-to-reveal couldn't be exercised since this
device rejects `adb shell input tap` (see
[[reference-mobile-device-testing]]).

## 2026-08-27 (later still) — Theme Builder: superadmin explicitly excluded

Follow-up after the Theme Builder build below: user asked to hide the
"Tema" nav entry from superadmin and confirm theming can never cross
organizations. The nav-hide alone wouldn't have been a real boundary
(CLAUDE.md: never rely on frontend hiding for security), so added
`OrganizationPolicy::manageTheme` — ketua-only **and never a
superadmin**, even one who independently also holds a real ketua
membership somewhere (superadmin is meant to stay strictly read-only
per ADR-0018; a separate ability rather than changing
`manageOrganization` directly, since that one is shared with
Transparency's toggle and org update/delete and changing it there would
have widened scope beyond what was asked). Used in place of
`manageOrganization` in `ThemeController`, `AttachOrganizationLogoRequest`,
and `UpdateOrganizationThemeRequest` — covers web and API together since
both share the same Form Requests. Sidebar's "Tema" entry now also
checks `!auth.user.is_superadmin`. Cross-org isolation itself needed no
code change — `current-org` middleware already resolves every route's
organization from the acting user's own membership, never a client-
supplied id, so there was never a code path to target another
org's theme — but added an explicit test proving it (a ketua of org A
opening the builder always sees org A's own empty state, never org B's,
even when org B has a theme already applied). 336 backend tests (up
from 332), Pint/PHPStan/`tsc`/ESLint clean, `vite build` succeeds.

## 2026-08-27 (later still) — RukunMuda Theme Builder built (web admin, API, mobile consumption)

Built the full feature specced in `docs/design/docs/theme-builder.md`: a
ketua-only web page (Upload → Color → Preview, one page, no wizard) that
lets an organization upload a logo and pick one primary brand color;
everything else (tints, pressed states, on-color text, dark-mode ramp)
is derived, never authored. Palette-suggestion ("AI color") extraction
was built and tested but is paused per the user's "hold AI feature"
mid-session — the code (`PaletteExtractor`) stays in place and wired,
just not a priority to extend further right now.

**Color engine, built and verified twice** — `App\Support\Theme\ThemeColorEngine`
(PHP, authoritative — the only thing that decides whether a `PUT`
persists) and `resources/js/lib/theme/derive.ts` (TypeScript, client-side
live preview/guard feedback only). Both implement the exact OKLCH
derivation tables from the spec and are pinned to the same reference-hex
unit test. **Two real ambiguities found via a rigorous OKLCH
verification pass (Node script, all 11 reference hexes round-tripped to
0 delta) and resolved with the user before implementing** — both
documented in `ThemeColorEngine`'s docblock and worth remembering:
1. `accent200`'s stated cap (`C min(C, 0.06)`) doesn't reproduce the
   legacy `#ffe0d9` within 1/255 (real gap ~5/255 — the reference hex
   predates this general formula). Implemented literally per spec;
   the unit test documents the wider tolerance for this one token only.
2. **Guard 2 (`onAccent` vs `accent` ≥ 4.5:1) is light-mode only** —
   found while testing the spec's own worked-example green (`#1E5B3B`):
   a literal both-modes reading made that exact color fail in dark mode,
   which can't be right. The spec states "and dark"/"in both modes"
   explicitly for guards 1 and 3 but never for guard 2, and dark mode's
   `onAccent` is unconditionally `dark bg` (no bg-or-text branch to guard
   there at all) — so light-only is the correct literal reading, not a
   workaround. `onAccent`'s bg-vs-text branch is also not a live
   contrast check but an OKLCH-lightness heuristic on `accent` itself
   (threshold tuned to the one known-good reference point), since a
   literal contrast check can't reproduce the reference either. The
   shipped default `#ec3013` deliberately still fails guard 2 as a *new*
   submission — confirmed intentional with the user — it's a
   grandfathered static fallback that never re-runs through this engine.

**Logo pipeline** (`LogoProcessor`, pure GD — no new PHP dependency):
trim transparent padding, pad to square with 8% breathing room, emit
mark@1x/2x/3x/icon-1024 (full bleed, no padding)/mono (flat ink-color
recolor preserving alpha, verified via unit test). **SVG is rasterized
client-side (Canvas), never server-side** — no Imagick/librsvg is
installed in this environment; the browser rasterizes to a 1024×1024 PNG
before upload, and the server-side pipeline only ever handles PNG. The
original SVG (or PNG) is still stored separately for provenance.

**Data model**: new `organization_themes` table (one row per org, unique
FK), no new audit table — reuses the existing `AuditLog` with action
`organization.theme_applied`; "last five restorable" is just the last 5
such rows, and "restore" is the normal apply flow with a historical hex
pre-filled (new guard check, new audit row — no special revert path).

**API deviates from the spec's literal `GET/PUT /api/v1/organizations/:id/theme`
on purpose**: implemented as `.../organizations/current/theme` (no
client-suppliable org ID), matching every other org-scoped route in this
app and CLAUDE.md §9. The web builder submits through its own web route
(`PATCH organisasi/tema`, matching `TransparencyController`'s pattern),
not the JSON API directly — both call the same
`ApplyOrganizationThemeAction` so the guard-check-and-audit invariant
can't fork between entry points.

**A real cross-guard bug caught by `withToken()` vs `Sanctum::actingAs()`
in testing** (not a production bug — a test-isolation artifact): mixing
`actingAs()` (session) and `withToken()` (bearer) auth *within the same
test method* made a bendahara's real-token request wrongly succeed,
because Sanctum's guard prefers an existing session over the bearer
token when both are present. Fixed by giving setup helpers their own
non-HTTP path (direct Action calls) instead of an authenticated HTTP
call, so a later `withToken()` call in the same test never inherits
stale session state. Worth remembering for any future test mixing both
auth modes in one method.

**PHPStan note**: `casts(): array` (method-style) on a model isn't
reliably picked up by this project's Larastan for return-type inference
— matches this codebase's own existing convention (see `Document.php`,
`FinancialTransaction.php` etc.) of adding an explicit `@property`
class-level docblock for any cast-typed column a consumer reads back.
Added to `OrganizationTheme` and `AuditLog`.

**Mobile consumption** (`mobile/src/theme/remoteTheme.ts` +
`ThemeProvider.tsx`): fetches once per boot, **only when already signed
in** (revalidating before login would 401 and wrongly trip the reusable
session-expired popup for a session that never existed — caught before
shipping). Cached theme applies immediately at next launch; a freshly
fetched theme only updates the *cache* for next time, never this
session's live colors, matching the spec exactly. No custom file-caching
was built for the logo images — React Native's `<Image>` component
already caches remote URIs on disk, which satisfies "cache the logo
files on device" without new plumbing. **Verified fully end-to-end live
on-device**: attached a green logo/theme to org1 via the real API,
confirmed the app rendered the default red on the launch that fetched
it, then rendered the full green-derived theme (buttons, tags, tab bar,
org logo swatch) on the *next* launch — with `expense` correctly staying
its fixed reddish tone throughout, never picking up the brand color.

**Also found and fixed mid-verification**: the device's `adb reverse
tcp:8000` tunnel had silently dropped again (same class of issue as
[[reference-mobile-device-testing]]'s dead-LAN-IP entry, different
port) — the app's `[theme] Failed to revalidate...` console warning
(exactly the designed silent-fallback behavior) was the tell. Also
needed a NEW tunnel, `adb reverse tcp:9000 tcp:9000`, for the device to
reach the MinIO-served logo images at all — not previously needed since
no feature served MinIO-hosted images to mobile before this one.

Backend: 332 tests passing (up from 304), Pint/PHPStan clean
(project-wide, level 7). Frontend: added Vitest as a new dev dependency
(none existed; the spec requires a `derive.ts` unit test) — 8 tests
passing, mirroring the Pest suite exactly. `tsc`/`eslint`/`prettier`
clean, `vite build` succeeds. Mobile: `tsc`/`expo lint` clean.

Full architecture/decisions in the approved plan file:
`~/.claude/plans/tingly-herding-avalanche.md`.

## 2026-08-27 (later still) — Web backlog closed: dashboard, sessions, search, Phase 7 pages

User asked to "finish web" this session with no bugs or wrong business
logic. Scoped via AskUserQuestion to four of the five open web items —
Attendance (#3 in the old numbering below) stayed explicitly out, since
it needs a *new* mobile screen too and CLAUDE.md requires asking first
before building beyond the 11 specified mobile screens.

**Dashboard** — `DashboardController` replaces the bare route closure:
saldo kas + recent transactions (`Organization::transparencySummary()`),
next planned/ongoing event, the viewer's incomplete tasks (mirrors the
API's `MyTasksController` query), their current-period unpaid due (skips
exempt/paid), and the most recently published announcement. The
existing "no organization yet" onboarding branch is untouched.

**Device/session management** — new `Settings\SessionController`
(`GET/DELETE settings/sessions`), a 4th settings nav entry ("Perangkat").
Reuses the same `tokens()` relation and `PersonalAccessTokenResource`
the API already used; confirmed `isCurrent` is always `false` from a
web (session-auth) request, so the page frames itself as managing
*other* (mobile) sessions, not the current browser tab.

**Search** — optional `?search=` on `EventController`/`MemberController`/
`FinancialTransactionController@index` (title / name+email / description
`LIKE`, composes with the transactions page's existing `event_id`
filter), a shared debounced `SearchInput` component. Date-range/
multi-field filtering stayed out of scope, matching the mobile design's
own choice to skip it.

**Phase 7 web pages** — Voting (read-only: list, detail, respond,
results — no creation page, matching the API), Inventory (full CRUD +
borrow/return, blocked-delete-while-loaned enforced), Sponsors (full
CRUD + the Diajukan→Setuju→Diterima/Batal state machine, contact details
gated to treasurer/chair same as the API), Documents (create/list/
delete only, secretary-or-treasurer-for-Laporan create rule) — all four
reuse the exact same Actions/Policies/Form Requests the API already
used, per CLAUDE.md's "don't duplicate business logic between web and
API" rule. Sidebar gained Voting/Inventaris/Sponsor/Dokumen entries.

60 new Pest feature tests across Dashboard/Sessions/Search/Voting/
Inventory/Sponsors/Documents, all passing on first run after each
area's implementation; 396 backend tests total (up from 336). Pint/
PHPStan (level 7)/`tsc`/ESLint/`vite build` all clean, re-verified
after each area rather than batched at the end.

**Still open, not touched this session** (see the renumbered list
below): Attendance, Activity points, the vote-creation flow, and Phase
8 hardening.

## 2026-08-27 (later still) — Mobile Step 8 built: Voting; Step 7 deferred

Continued the mobile build order. **Step 8 (Voting, screens 26-28)**
needed zero backend changes — the API (built 2026-08-26) already covers
list/detail/respond/results exactly as the screens need, and unlike
vote *creation* (still an open product decision), the read-only +
respond-only scope of these three screens was never blocked on it.
Built `VotingScreen` (26, open-then-closed grouping — backend already
sorts this), `VotingDetailScreen` (27, disclosure block, checkbox/radio
options via the existing `VoteOption` component, confirm dialog, the
"vote closes between selecting and confirming" edge case routing to
results with the spec's exact message), `VotingResultScreen` (28,
winner block, result-mode `VoteOption` rows, anonymity restatement,
pengurus-only breakdown, "Bagikan hasil"). Routed as `voting/index`,
`voting/[id]/index`, `voting/[id]/hasil` (nested dynamic segment,
mirroring the existing top-level-stack-per-domain pattern), wired into
Profil's "Organisasi saya" section per `mobile-ux.md`'s exact
menu order. `tsc`/`expo lint` clean — a real lint catch and fix along
the way: syncing `selected` from server data via `useEffect` +
`setState` tripped `react-hooks/set-state-in-effect`; fixed to the
render-time "adjust state" pattern, keyed on the vote's `id` so a
background refetch mid-edit can't silently wipe an in-progress
selection. **Not device-verified this session** (no physical device
connected). One deliberate simplification: the closed-votes list
doesn't show each vote's winning option as its subtitle (screen 26's
literal spec) — that needs a separate `results` call per vote with no
batch endpoint to avoid N+1 fetches, so the list shows only a "Ditutup"
tag instead; and the "Urungkan" (undo) toast action on submit (screen
27) wasn't wired — there's no API capability to revert to "no vote at
all" once submitted (only replace), so only a plain confirmation toast
shows, matching the same not-yet-wired state `SharePreview`'s
`onShare`/`onCopy` already have elsewhere in this app.

**Step 7 (Buat Organisasi) — scoped and then deferred.** Backend
gap-check: `POST /api/v1/organizations` already exists and reuses
`CreateOrganizationAction`, but only accepts `name` — screen 33's
4-step spec (name+type, kelurahan/kota, an invite step reusing the
already-deferred screen 15, then confirmation) needs schema this app
doesn't have. Asked the user, who chose the minimal scope (name only,
no new columns). But scoping the screen itself surfaced a deeper block
that made building it pointless right now: **neither of the spec's two
entry points actually work.** Splash's "Buat organisasi baru" is for a
signed-out user, and mobile has no signup/register screen at all (login
only, against an existing account) — that's a whole separate feature,
not part of this step. Profil's "org switcher" entry point doesn't
exist either — mobile has no switch-organization UI anywhere, and
`useCurrentOrganization()` assumes exactly one org per user throughout
the app, so even a signed-in user creating a second org would have no
way to actually reach or use it afterward. Asked again; user chose to
defer Step 7 entirely, same treatment as Undang Anggota — nothing built,
both entry-point buttons stay pointed at their existing honest stubs.

## 2026-08-28 — Theme now actually shows up outside the Theme Builder page

Follow-up to a user question: uploading a logo/color previously only
showed up inside the Theme Builder's own preview swatches — nowhere
else in the running app on either surface. Closed both gaps:
**Web** — `HandleInertiaRequests` now shares `organizationTheme` (via
`OrganizationThemeResource`, same shape as the builder page already
used) on every authenticated page, not just the builder's own route. A
new `OrganizationThemeStyle` component injects the org's `accent`/
`onAccent` tokens as `--primary`/`--sidebar-primary` CSS custom
properties (mapped deliberately, not 1:1 — shadcn's own `--accent`
token is a differently-purposed neutral hover tint, not "the brand
color"; `--primary` is what Button/Badge's default variant and the
sidebar's active item actually render), mounted once in
`app-sidebar-layout.tsx` so it covers the whole authenticated app.
Sidebar's logo box now shows the org's uploaded mark when a theme is
set, falling back to the stock SVG otherwise. **Mobile** —
`HomeScreen`'s header mark was already deriving the right *color*
(`theme.color.accent`, merged in by `ThemeProvider`) but never rendered
the actual logo *image* despite `ThemeProvider` already exposing
`logo.mark` on its context — a comment saying "see Home's header" had
never been finished. Now renders a real `<Image>` when a theme is set.
3 new Pest tests (shared-prop presence, cross-org isolation), 399
backend tests total. Pint/PHPStan/tsc/ESLint/`vite build` clean on web;
tsc/`expo lint` clean on mobile.

## 2026-08-28 (later) — Attendance domain built end-to-end (API, web, mobile)

User asked to run autonomously and finish the remaining backlog across
all three surfaces. Attendance was the largest item — `Attendance`/
`AttendanceSession` (master prompt §31, domain model §12), plus the
mobile Event Detail "Konfirmasi kehadiran saya" button screen 05
already speced but stubbed since the domain never existed
(`mobile-e2e-backlog.md` §8, "sedang disiapkan").

**Design**: one `AttendanceSession` per event (not a manual open/close
toggle — eligibility is just "event status is Ongoing", tied to the
lifecycle field organizers already manage), `Attendance` rows unique on
(session, membership) to prevent duplicates. **Manual** = the existing
spec'd self-check-in button, unchanged from the design. **QR** reuses
this codebase's own established pattern (`ReportQrCode`, `endroid/qr-
code`, already a dependency) rather than adding a camera/scanner
library: the QR encodes a plain web URL (`/attendance/{token}`), so any
phone's stock camera can check a member in via a real Inertia page —
no new native module, no rebuild, works even without the mobile app
installed. `AttendancePolicy::checkIn` (any member, event Ongoing only)
mirrors `VotePolicy`'s view-vs-respond split so an ineligible viewer
still sees *why*, not a blank 403; `::manage` (attendance list, QR)
reuses `Event::isManagedBy` (chair or the event's own PIC) rather than
a blanket pengurus check, matching how editing the event itself is
already scoped.

**Backend**: `CheckInAttendanceAction` (find-or-create the session,
reject a duplicate with a friendly message, record the row). Web:
attendance section on the event page (status tag + confirm button + a
manager-only attendance-list link and QR link), a new `events/{event}/
attendance` list page, and the public-ish `/attendance/{token}` scan-
landing page. API: mirrors the same three actions for mobile, plus an
`attendance` block folded into the existing `EventResource` (my status,
count, canCheckIn) so Event Detail doesn't need a second fetch.
**Mobile**: wired the real thing into the already-spec'd screen 05 —
the "✓ HADIR"/"BELUM HADIR" tag now reflects real status, the action
bar button calls the real endpoint instead of opening the "sedang
disiapkan" stub. No mobile UI added beyond what screen 05 already
specified — QR viewing stays web-only (an organizer's laptop/tablet at
the venue), matching how report QR codes already work in this app.

17 new Pest tests (10 web, 7 API), 416 backend tests total (up from
399). Pint/PHPStan (level 7)/tsc/ESLint/`vite build` clean on web;
tsc/`expo lint` clean on mobile — not device-verified (no physical
device connected this session).

## 2026-08-28 (later still) — Mobile signup, org-switching, and Buat Organisasi built; both blockers resolved

Continued running autonomously. Step 7 was deferred earlier this
session on two real blockers — no mobile signup, no org-switching
anywhere. Both closed, unblocking the create-organization screen.

**Real multi-org switching, not just a UI stub** — `currentMembership()`
previously always returned a user's *first* membership; there was no
way to actually select among several. Added `users.active_organization_id`
(nullable, not `$fillable` — only `SwitchOrganizationAction` ever sets
it, always after verifying real membership first, same "never trust a
client-supplied org id at face value" rule as every other org-scoped
write in this app). Falls back to the first membership when unset or
stale (e.g. the active org was left) — zero behavior change for a
single-org user, still the common case. Shared by web and API: `POST
organizations/switch`, `GET organizations/mine` (or `/mine` API-side).

**Mobile signup** — `POST /api/v1/auth/register` mirrors
`Auth\RegisteredUserController`'s web rules exactly (same validation,
same `Registered` event) but issues a Sanctum token instead of starting
a session. New `RegisterScreen`, linked from both Splash's "Buat
organisasi baru" (previously an honest stub) and a new "Belum punya
akun? Daftar" link on the login screen.

**Buat Organisasi (Step 7)** — built to the minimal scope already
agreed: name only, matching `POST /organizations` as-is; the 4-step
type/location/invite/confirm flow in `mobile-screens.md` §33 stays
out of reach (still needs schema this app doesn't have, and the invite
step is still blocked on the same deferred architecture as Undang
Anggota). Reachable from both spec'd entry points now that their real
blockers are gone: Splash → Register → auto-routed here by a new
no-org gate in `(app)/_layout.tsx` (a fresh registration has zero
memberships; every tab assumed one, so this redirects once, centrally,
rather than each of the 11 screens needing its own no-org branch); and
Profil/Home's "Ganti organisasi" now opens a real `OrgSwitcherSheet`
(org list, current one tagged, a "+ Buat organisasi baru" link) instead
of the "coming later" `InfoSheet` it opened before.

**Small web parity gap closed alongside**: an already-org'd web user had
no way to create a *second* org either (the dashboard's create-form only
ever showed for a user with none). Extracted it into a reusable
`CreateOrganizationForm`, added `/organizations/create` plus a matching
link in a new account-menu org switcher. Creating an org (either
surface) now also sets it as the user's active org — landing back on an
old org right after asking to create a new one would've been a strange
result.

7 new Pest tests (org switching), 5 more (register + API switch/list),
2 more (create-a-second-org), 427 backend tests total (up from 416).
Pint/PHPStan/tsc/ESLint/`vite build` clean on web; tsc/`expo lint` clean
on mobile — not device-verified.

## 2026-08-28 (later still) — Vote creation and Activity points built

Closed the last two items of the "finish all" backlog before Phase 8.

**Vote creation** — was an explicitly *open* product decision
(`mobile-ux.md` § Open decisions: "who may create a vote?"), not a
build gap to fill in silently. Resolved it to pengurus, matching every
other "who creates organizational content" gate already in this exact
app (Events, Sponsors, Inventory) — the one precedent this product
already has for this question. `VotePolicy::create`, `CreateVoteAction`
(question, description, anonymous/editable, max selections, eligible
scope, optional event link, options — one transaction). **Web only** —
mobile's Voting stays exactly as speced ("no creation flow in this
phase"); the API route comment was updated to say so explicitly rather
than read like an unfinished gap. `votes/create` page, "Buat voting"
button on the list (pengurus only).

**Activity points** — domain-model.md's only guidance was one line:
"participation scoring, derived from event/task/attendance activity."
Kept deliberately simple, not the elaborate points-shop-and-badges
system "gamification" often implies: one new `ActivityLog` table
(append-only, `(source, source_id)` unique so a task toggled DONE ->
TODO -> DONE, or a retried check-in, can never double-award), two
triggers (checking into an event: 5 points; completing an assigned
task: 10 points — round, simple, adjustable constants on
`ActivityPointSource`, not buried magic numbers), a total computed
live from the log rather than a separately-maintained balance column
that could drift. New `UpdateEventTaskStatusAction` extracted so the
award-on-completion logic lives in exactly one place shared by web and
API, instead of duplicating it in both task controllers. Points show up
everywhere a member is already listed: a "Poin" column on web's
member list, `meta` text on mobile's Anggota list rows and detail
screen — no new screens invented for this on either surface.

23 new Pest tests (16 vote creation, 4 activity-points triggers, 3
points-display), 438 backend tests total (up from 427). Pint/PHPStan/
tsc/ESLint/`vite build` clean on web; tsc/`expo lint` clean on mobile.

## Phase 8 hardening — not started

## 2026-08-28 (later still) — Phase 8's concrete, code-level items closed

Last item of the "finish all" pass. Phase 8 is intentionally
open-ended (`docs/roadmap.md`'s own text: "hardening a system that's
still growing its feature set is premature") — closed the actionable,
verifiable-in-this-session subset, left the rest as genuinely
infrastructure work rather than inventing something to point at.

**CI** — `.github/workflows/ci.yml` didn't exist at all (master
prompt §61 specs it exactly: Pint, Pest, PHPStan, frontend lint,
`tsc`, frontend build, fail on any of it). Three jobs: backend
(composer install, frontend build first — Pest hits real Inertia
pages, which need the Vite manifest, the exact gotcha this session hit
repeatedly locally — then Pint/PHPStan/Pest), a separate job that runs
every migration against a real throwaway Postgres container (SQLite's
own quirks can hide real Postgres-specific schema issues — this
mirrors how migrations were manually verified against real Postgres
throughout this session, now automatic), and frontend (ESLint, `tsc`,
Vitest, `vite build`). Not pushed/triggered — creating the workflow
file doesn't require it.

**Security — closed a real gap, not just documented one**:
`docs/security.md` already *claimed* "rate limiting on auth endpoints
and any public-facing endpoint," but registration (web and the new
API endpoint), password reset, and the *entire* `/api/v1/*` surface
had none — Laravel 11+ dropped the old `RouteServiceProvider`
convention that used to wire `throttle:api` in by default, and this
app never replaced it. Added: registration throttled 5/min per IP
(web via route `throttle:5,1`, API via a `RegisterRequest` method
mirroring `LoginRequest`'s existing pattern), password reset routes
throttled the same way, a general `api` limiter (60/min per user or
IP) applied to the whole API surface, and a `public-pages` limiter
(60/min per IP) on the public transparency/report-share/QR/PDF
routes. All of it verified both by test (a dedicated throttle test,
mirroring the existing login one) and by direct `route:list -vv`
inspection showing the middleware actually attached — and made inert
specifically during `php artisan test` (checked via
`runningUnitTests()`) so unrelated tests across different files can't
flakily trip a shared IP-keyed bucket, without weakening the
dedicated, still-fully-tested auth throttles. `docs/security.md`
updated to describe what's actually true now, not what it assumed
before.

**Deployment docs** — `docs/deployment.md` didn't exist (master
prompt §58 lists it explicitly). Process model (four things actually
need to run in production: web server, queue worker, scheduler,
one-time Vite build — this app has exactly one scheduled command,
`dues:remind-unpaid`, easy to miss if the scheduler isn't wired up),
required infra (same three services as `docker-compose.yml`, any
managed equivalent), a table of every env var that must change from
its local default before going live (`APP_DEBUG`, `CORS_ALLOWED_ORIGINS`
— already flagged as a TODO in `.env.example`'s own comment —
`SESSION_SECURE_COOKIE`, etc.), concrete backup commands (`pg_dump`
for Postgres, `mc mirror` for the S3-compatible bucket — both are the
sole source of truth for their data, everything else is
disposable/regeneratable), and a go-live checklist.

**Deliberately not done, with reasoning**: caching (CLAUDE.md's own
explicit caution — "never cache mutable financial values without a
clear invalidation strategy... financial correctness is more
important than cache performance" — and this app's current scale
doesn't show a real need yet); OpenTelemetry/deeper observability and
backup *automation* (both are real infrastructure work needing an
actual target platform to wire up, not something to fake in code).

3 new Pest tests (registration throttling), 439 backend tests total
(up from 438). Pint/PHPStan/tsc/ESLint/`vite build` clean.

## Already closed, not re-listed here

Financial evidence uploads, database notifications + the one Scheduler
job, PDF report generation (web button + API `pdfUrl`), CORS config,
API login rate limiting, Sanctum device sessions (API side), and the
full mobile E2E wiring (all 11 screens, including "kegiatan diikuti")
— see `backend-backlog.md` and `mobile-e2e-backlog.md` for what each
one actually shipped.
