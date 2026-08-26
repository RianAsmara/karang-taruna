# Web Backlog

Gaps in the Inertia/React web frontend (`laravel/resources/js/`) found
during the same audit as `backend-backlog.md` (2026-08-25). Some of
these are the web-side symptom of a backend gap listed there — noted
where relevant so the two docs don't drift apart.

## 1. `/dashboard` is a stub, not a real dashboard

`resources/js/pages/dashboard.tsx` only shows the organization name,
role, and a link to the public landing page (plus the new-organization
onboarding form for users without one — that part *is* fully built).
Master prompt §39 specifies a real dashboard: saldo kas, event
terdekat, task saya, iuran, transaksi terbaru, activity, transparency
summary — essentially the web equivalent of what the mobile Home
screen (design doc screen 03) shows. None of that lives on `/dashboard`
today; a signed-in member has to navigate to Kas/Kegiatan/Transparansi/
Iuran separately to see any of it.

## 2. No search UI

No search exists anywhere on web — not on events, transactions, or
members lists. (Matches the mobile design's own "out of scope: search"
note, so this isn't a design-fidelity miss, just an open gap on both
surfaces.)

## 3. No advanced filters

Lists (transactions, events) only have the simple filters already
built (event_id scoping, status via treasurer/member view). No
date-range, category, or multi-field filtering UI.

## 4. Attendance — no UI (backend doesn't exist either)

See `backend-backlog.md` §4. No attendance pages exist because there's
no backend to back them.

## 5. Phase 7 feature pages — not started

Voting, Inventory, Sponsors, Document management — no pages under
`resources/js/pages/` for any of these, matching the backend gap in
`backend-backlog.md` §5.

## 6. No PDF export UI — RESOLVED (2026-08-25)

`reports/show.tsx` now has an "Unduh PDF" link (`route('reports.pdf', report.id)`),
alongside the existing WhatsApp/copy-link actions — see
`backend-backlog.md` §10.

## 7. No notification center — RESOLVED (2026-08-25)

Added `resources/js/pages/notifications/index.tsx` plus a bell icon
with an unread-count badge in `app-sidebar-header.tsx`
(`route('notifications.index')`) — see `backend-backlog.md` §2.

## 8. No device/session management UI

Follows from `backend-backlog.md` §9 — since the API has no endpoint
for listing/revoking other devices' tokens, there's naturally no web
page for it either. (Web sessions themselves are Laravel's standard
Breeze/Fortify-style session auth, unaffected by this — this is
specifically about a user managing their *other* logged-in devices,
e.g. the mobile app.)

## What's already solid (no action needed)

New-organization onboarding, member management, event CRUD + committee
management, financial accounts/categories/transactions with the full
approval flow, member dues, financial reports with publish/revision,
the transparency dashboard, and the public org landing/transparency
pages are all built and were the subject of the Phase 1–4 test suites
(111 Pest tests) — not re-litigated here.
