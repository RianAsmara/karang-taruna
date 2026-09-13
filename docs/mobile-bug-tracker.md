# Mobile Bug Tracker

For findings from manual testing on a real device. Add a row under
**Open** for each new bug — copy the template row, fill it in, delete
the placeholder text. Move a row to **Resolved** once it's fixed
(don't delete it — keep the history).

Design-fidelity/content gaps already known from the build itself are
listed separately in `docs/mobile-e2e-backlog.md` §6 — check there
before logging something that might already be tracked (e.g. the
invented Event Detail task titles, the glyph-rendering note, the
Modal/web-frame breakout).

## Open

| ID    | Date | Screen     | Description                                                               | Severity | Steps to reproduce | Notes |
| ----- | ---- | ---------- | ------------------------------------------------------------------------- | -------- | ------------------ | ----- |
|       |      |            |                                                                           |          |                    |       |

**Severity guide**: `blocker` (can't use the screen at all) · `major`
(wrong data, broken interaction, crash) · `minor` (visual/spacing
mismatch, doesn't affect function) · `cosmetic` (typo, alignment nit).

## Resolved

| ID    | Date        | Screen                  | Description                                                                                                                                                                                | Fixed in                                                                                                                                                                                                                                                              | Notes |
| ----- | ----------- | ----------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----- |
| B-001 | 25 Aug 2026 | all screens after login | Custom header overlapped the phone notch/status bar — none of the custom headers accounted for `insets.top` (same class of bug the tab bar already handled correctly for `insets.bottom`). | `ScreenHeader.tsx` (covers Kegiatan/Kas/Event Detail/Transparansi/Report Detail/Profil/BelumTersedia), plus `HomeScreen.tsx`, `NotifikasiScreen.tsx`, `LoginScreen.tsx` — each now adds `useSafeAreaInsets().top` to its header's height/padding.                     |       |
| B-004 | 13 Sep 2026 | all screens | Every screen hung on its loading skeleton forever, with nothing in logcat and no error state, whenever the API host was routable but not answering (dev server bound to loopback, WiFi client isolation, a VPN reshuffling routes). RN `fetch()` has no default timeout and `apiFetch`/`apiUpload` added none, so the promise never settled — the query never reached `isError`, so the `ErrorState` each screen already had could never render. Cost two debugging sessions. | `src/lib/api.ts` — `apiFetch` now aborts after 15s and `apiUpload` after 120s (an upload over a weak connection is legitimately slow), both converting the bare `AbortError` into the same `ApiError` shape callers already handle, with actionable Indonesian copy. Found by a subagent baseline run while building `.claude/skills`. | Not device-verified. `tsc`/`expo lint` clean. |
| B-003 | 13 Sep 2026 | Homescreen | `TypeError: Cannot read property 'recentTransactions' of undefined` — Home crashed whenever the transparency query failed. `HomeScreen` guarded only `isPending`, so a failed query fell through to `const summary = transparency.data!` (undefined) and threw on the first property read. Same incident family as the RpNaN report: a stale token / dead LAN path made every request fail. | `4822519` added the missing `isError` + `ErrorState` branch, which closed the reported crash. This session removed what caused it: `transparency.data!` is now narrowed by a real `!transparency.data` check folded into that guard (`apiFetch` resolves `undefined` on 204 and `null` on an unparseable body, so `!isPending && !isError` never guaranteed data), and the contradictory `summary?.` chains it left behind are gone — `summary?.recentTransactions.length - 1` silently produced `NaN` rather than guarding anything. `tsc` now proves the narrowing. | Not reproduced live: the reported build predates the `isError` fix, and the device could not be driven to Home this session (no `INJECT_EVENTS`). Root cause established from the code at `c758dc6`, the commit that logged the row. |
| B-002 | 25 Aug 2026 | Login (OTP step)        | OTP boxes required manually tapping each box before typing the next digit instead of auto-advancing.                                                                                       | `LoginScreen.tsx` — added `otpRefs` + auto-`.focus()` on the next box after each digit, backspace-to-previous-box, and `autoFocus` on the first box. Verified: typing all 6 digits with zero clicks between boxes now advances through every box and completes login. |       |
