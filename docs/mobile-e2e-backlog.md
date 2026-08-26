# Mobile E2E Backlog

Captured while scoping "test the mobile app end-to-end against the real
Laravel backend" (2026-08-25). Originally the mobile app (`mobile/`) was
100% static mock data (`mobile/src/data/mock.ts`) with zero network
calls anywhere. As of 2026-08-25, all 11 designed screens are wired to
the real `/api/v1` backend. This document now records what was wired,
how, and the honest gaps/simplifications that remain — not a
"not started yet" gap list anymore.

## 1. Login flow mismatch — RESOLVED (temporary deviation, as decided)

The design's Login screen (`screens/02-Login.md`) is WhatsApp number +
6-digit OTP, no password. There's still no WhatsApp/OTP endpoint in
Laravel.

Implemented as decided: a new `LoginEmailScreen.tsx` (email+password)
is mounted at `/login` and calls `POST /api/v1/auth/login` for real.
The original design-verified OTP screen (`LoginScreen.tsx`) is left
untouched in the codebase, just unreferenced. Session persists via
`expo-secure-store`; the Splash screen (`SplashScreen.tsx`) now checks
it and `<Redirect>`s straight to Home if already signed in.

## 2. Screens/blocks with no backing endpoint — RESOLVED, wired

- Home "Pengumuman" → `useAnnouncements()` (`GET /announcements`).
- Home "Perlu tindakan" / Kas "Iuran {bulan}" / Profil "iuran saya" →
  `useDues()` (`GET /finance/dues`).
- Home "Tugas saya" / Profil "tugas aktif" → `useMyTasks()`
  (`GET /my/tasks`).
- Notifikasi (screen 09) → reshaped and wired, see below.
- Profil "kegiatan diikuti" → **RESOLVED (2026-08-26)**. `OrganizationController::current()`'s
  `membership` block now carries `participatedEventsCount`: count of
  the caller's `EventParticipant` rows with status `REGISTERED` whose
  event `status` is `COMPLETED` — i.e. events actually attended, not
  just signed up for. `ProfilScreen.tsx`'s third stats cell (previously
  missing outright, not a "–" placeholder) now renders it. Covered by
  `tests/Feature/Api/OrganizationTest.php`.

**Notifikasi was reshaped, not just wired.** The real API is a flat,
chronological `{type, data, readAt}` feed (`NotificationResource`), not
the mock's "pending action list + voting progress card" shape. Decided
(not asked, per "go ahead") to keep the two-section layout but redefine
it honestly: "Perlu tindakan" = unread notifications (each row's copy
derived from `type`+`data`, mirroring the web Inertia notifications
page's `describe()` logic), "Kabar lain" = read ones. The "Sedang
berjalan" voting card was dropped entirely — voting doesn't exist
(Phase 7). "Tandai dibaca" now calls `POST /notifications/{id}/read`
for every unread item. The tab bar's unread badge
(`(app)/_layout.tsx`) is wired to the same query.

## 3. Screens with a real endpoint but missing fields — mostly RESOLVED

- Kegiatan list counts → `EventResource.committeeCount/participantCount`. Wired.
- Event Detail committee/panitia → `EventResource.committees`. Wired.
- Report Detail income/expense bars → `categoryBreakdown` meta. Wired.
- **Event Detail "Anggaran" tab — reframed, not "budget vs. used".**
  There is no "planned budget ceiling" field anywhere in the backend
  (Event has no budget column). Rather than fabricate one, the tab now
  shows real totals: sum of the event's own INCOME/EXPENSE transactions
  (`GET /finance/transactions?event_id=`), net amount, and the
  transaction list — honest data, different framing than the original
  mock's "total/used/remaining" language.
- **Kas wallet split** ("Tunai Rp.. · Rekening BRI Rp..") — the
  Tunai/Rekening naming convention doesn't exist server-side (still
  true). Wired generically instead: joins the real `/finance/accounts`
  list as `"{account.name} {balance}" · ...` for however many accounts
  actually exist, rather than assuming two specific named wallets.

## 4. Client-side infrastructure — DONE

- `@tanstack/react-query` + `expo-secure-store` installed.
- `src/lib/api.ts` — env-aware base URL (`EXPO_PUBLIC_API_URL`, falls
  back to `localhost:8000` on iOS/web or `10.0.2.2:8000` on the Android
  emulator; a physical device needs `mobile/.env.local` set to the dev
  machine's LAN IP — `.env.example` documents this), bearer-token
  fetch wrapper, `ApiError` with Laravel-style `errors` parsing.
- `src/store/useAuth.ts` (Zustand) — SecureStore-backed session,
  `bootstrap()`/`login()`/`logout()` against the real endpoints.
- `src/lib/queries.ts` — all react-query hooks + response types for
  every endpoint the 11 screens use.
- `QueryClientProvider` wired into the root layout; auth bootstrapped
  before first render so Splash's redirect check is never stale.
- Every wired screen uses the existing `Skeleton`/`ErrorState`/
  `EmptyState` components for real loading/empty/error states — no
  screen silently shows blank or stale content on failure.

## 5. Backend additions made to support wiring (not scope creep — API mirrors of existing web features)

- `Api\V1\EventTaskController::updateStatus` +
  `PATCH /api/v1/events/{event}/tasks/{task}/status` — the web already
  had this (`EventTaskController::updateStatus` + Policy), the API
  mirror was simply missing. Lets the Tugas tab's checkbox actually
  toggle task status instead of being read-only. Tested
  (`tests/Feature/Api/EventTaskTest.php`).
- `MemberDueResource` gained `userId` (the member's raw User id,
  alongside the existing `memberName`) — needed to reliably find "my
  own" due among a treasurer's full-org dues list; `memberName` alone
  isn't a safe join key.
- `Api\V1\ReportController`'s `show()`/`share()` `meta` already carried
  `shareUrl`/`qrUrl`; `pdfUrl` was added earlier in the backend-backlog
  session and mobile now uses it directly (Transparansi's and Report
  Detail's "PDF" buttons open it via `Linking.openURL`).

## 6. Bug found and fixed during live verification

**Not caught by the Pest suite** (which runs on SQLite): the
`notifications` table migration used stock `$table->morphs('notifiable')`,
which creates `notifiable_id` as `bigint`. Every notifiable model here
(User) uses ULID string ids, so real PostgreSQL rejected every
`/notifications` query (`invalid input syntax for type bigint`) while
SQLite's dynamic typing silently let it through in tests. Fixed to
`$table->ulidMorphs('notifiable')`, migration rolled back and re-run.
Logged in `backend-bug-tracker.md` (B-001) with the general lesson: a
strict-typing schema bug can pass the full suite and still break on
Postgres — a live smoke-test against the real DB is what caught this.

A second bug from the same live pass: `EventDetailScreen`'s "tugas
saya" count initially compared `task.assignee.id` (an
`OrganizationMembership` id — `EventTaskResource.assignee` wraps
`EventTask::assignee(): BelongsTo(OrganizationMembership::class)`)
against the authenticated `User.id` — different id spaces, always
false. Fixed by fetching the current user's own membership id from
`useCurrentOrganization()`'s `membership.id` (added to the query's
response type) and comparing against that instead.

## 7. Carried over from the design-fidelity build report — status after wiring

- **Event Detail's task list placeholder titles** ("Booking wasit",
  "Siapkan sound system") — resolved naturally; the Tugas tab now
  renders whatever tasks actually exist in the database, not the mock's
  fixed four.
- **Report Detail's "Semua catatan" count** — resolved; now the real
  filtered transaction count for the report's period, not the mock's
  fixed 3.
- **Event Detail's PERSIAPAN percentage** — resolved; computed live
  from real task done-count / total.
- **Font/glyph rendering** ("◐", "☀", "⌕") and **BottomSheet/Dialog
  full-width-on-web** — unchanged, still only verifiable on-device (see
  `mobile-bug-tracker.md`).

## 8. Still genuinely open (not wired, honestly disclosed rather than faked)

- Attendance confirmation on Event Detail — `Attendance`/
  `AttendanceSession` domain was never built (explicitly deferred, see
  `backend-backlog.md` §4). The button now opens an honest "sedang
  disiapkan" stub instead of faking a toast confirmation like the mock
  did.
- "Iuran Saya" (`/iuran-saya` route) — was never one of the 11 designed
  screens; still a `BelumTersedia` stub by design, not touched.
- Voting — Phase 7, untouched everywhere (Notifikasi's voting card
  removed rather than left dangling).
