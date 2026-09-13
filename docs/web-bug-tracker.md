# Web Bug Tracker

For findings from testing the Inertia/React web frontend in a browser.
Add a row under **Open** for each new bug — copy the template row, fill
it in, delete the placeholder text. Move a row to **Resolved** once
it's fixed (don't delete it — keep the history).

Known gaps that aren't bugs (pages/features that were never built) are
tracked in `docs/web-backlog.md` — check there before logging something
that's actually missing rather than broken.

## Open

| ID | Date | Page | Description | Severity | Steps to reproduce | Notes |
| --- | --- | --- | --- | --- | --- | --- |
| B-001 | | | | | | |

**Severity guide**: `blocker` (can't use the page at all) · `major`
(wrong data, broken interaction, crash) · `minor` (visual/layout
mismatch, doesn't affect function) · `cosmetic` (typo, alignment nit).

## Resolved

| ID | Date | Page | Description | Fixed in | Notes |
| --- | --- | --- | --- | --- | --- |
| W-001 | 13 Sep 2026 | all authenticated pages | Every flashed message was silently discarded. Five places called `->with('error'\|'success')` — attendance check-in ("Kehadiran Anda tercatat."), QR check-in, theme save, the invite-failure redirect, and the no-organization redirect — but `flash` was never shared by `HandleInertiaRequests` and no page rendered it. A member could check in to an event and see literally no confirmation; opening an expired invite link bounced them to the dashboard with no explanation. | `HandleInertiaRequests::share()` now exposes `flash.success`/`flash.error`; new `components/flash-messages.tsx` renders it once in `app-sidebar-layout`, using icon + wording as well as colour (status is never colour alone) with an `alert`/`status` role and a dismiss control. 3 Pest tests pin the plumbing. | Found by auditing the invite feature's failure branch. |
| W-002 | 13 Sep 2026 | Register | Registration ignored the intended URL and hard-redirected to the dashboard, so the commonest invite path — a person with no account taps the join link, is bounced to login, taps "Daftar", registers — landed them on the dashboard having **never joined the organization**. The primary onboarding flow silently failed for exactly the people it exists to onboard. | `RegisteredUserController` now uses `redirect()->intended(...)`, matching what `AuthenticatedSessionController` already did. Regression test added to `OrganizationInviteTest`. | Login was already correct; only registration was not. |
