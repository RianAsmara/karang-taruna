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

## 1. Web dashboard is still a stub (largest open gap)

`resources/js/pages/dashboard.tsx` only shows org name/role and the
new-org onboarding form. Master prompt §39 wants the real thing: saldo
kas, event terdekat, task saya, iuran, transaksi terbaru, activity,
transparency summary — essentially the web equivalent of the mobile
Home screen (design screen 03), and everything it needs already has a
web/API backing (accounts, dues, my-tasks, transparency all exist).
Highest-visibility gap since it's the first thing every member sees
after login. See `web-backlog.md` §1.

## 2. Device/session management UI (web)

The API side is done (`AuthController::sessions()`/`destroySession()`,
`PersonalAccessTokenResource` — `backend-backlog.md` §9) but there's no
web settings page to list/revoke a user's other logged-in devices
(e.g. the mobile app). Small, self-contained, mirrors an existing
settings page pattern (`Settings\PasswordUpdateTest` /
`ProfileUpdateTest`). See `web-backlog.md` §8.

## 3. Attendance domain — entirely unbuilt, spans all three surfaces

No `Attendance`/`AttendanceSession` migration, model, policy, or
controller exists anywhere (master prompt §31, domain model §12).
`docs/roadmap.md`'s own Phase 6 text lists "attendance" as a mobile
deliverable, which overstates current reality — worth a one-line fix
there once this is scheduled, not urgent on its own.

Before building this: **the mobile design only specifies 11 screens,
and CLAUDE.md is explicit that anything beyond those needs to be asked
about first** — Event Detail's "Konfirmasi Kehadiran" button currently
opens an honest "sedang disiapkan" stub rather than a fake confirmation
(`mobile-e2e-backlog.md` §8), so this is backend + web UI + a **new**
mobile screen/flow, not just an API to wire. Largest single item on
this list; ask before starting the mobile-design half of it.

## 4. Search and advanced filters (web)

No search anywhere (events/transactions/members lists), no date-range
or multi-field filtering beyond the simple scoping already built. Not
a design-fidelity miss — the mobile design explicitly puts search out
of scope too — just a genuine gap on both surfaces. Lower priority than
#1–3: nothing is unusable without it, just less convenient at scale.
See `web-backlog.md` §2–3.

## 5. Phase 7 features — next major phase per the roadmap

Voting, Inventory, Sponsors, Document generation, Activity points: no
models/migrations/controllers/pages for any of these on any surface.
Expected — this is simply "the next phase," not a surprise gap.
Matches `backend-backlog.md` §5, `web-backlog.md` §5,
`docs/roadmap.md`'s own plan. Sequence after #1–3 since those are
smaller and touch features members already depend on daily (dashboard,
attendance) rather than net-new domains.

## 6. Phase 8 hardening — not started

Performance/query optimization, caching, security hardening beyond
what's in place, deeper observability (structured logging exists, no
OpenTelemetry), backups, CI/CD, deployment docs. Matches
`docs/roadmap.md`'s own Phase 8 scope. Correctly last — hardening a
system that's still growing its feature set is premature.

## Already closed, not re-listed here

Financial evidence uploads, database notifications + the one Scheduler
job, PDF report generation (web button + API `pdfUrl`), CORS config,
API login rate limiting, Sanctum device sessions (API side), and the
full mobile E2E wiring (all 11 screens, including "kegiatan diikuti")
— see `backend-backlog.md` and `mobile-e2e-backlog.md` for what each
one actually shipped.
