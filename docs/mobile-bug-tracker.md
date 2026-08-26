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

| ID    | Date        | Screen                 | Description                               | Severity | Steps to reproduce           | Notes |
| ----- | ----------- | ---------------------- | ----------------------------------------- | -------- | ---------------------------- | ----- |
| B-003 | | | | | | |

**Severity guide**: `blocker` (can't use the screen at all) · `major`
(wrong data, broken interaction, crash) · `minor` (visual/spacing
mismatch, doesn't affect function) · `cosmetic` (typo, alignment nit).

## Resolved

| ID    | Date        | Screen                  | Description                               | Fixed in                                                                                                                                     | Notes |
| ----- | ----------- | ------------------------ | ----------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------- | ----- |
| B-001 | 25 Aug 2026 | all screens after login | Custom header overlapped the phone notch/status bar — none of the custom headers accounted for `insets.top` (same class of bug the tab bar already handled correctly for `insets.bottom`). | `ScreenHeader.tsx` (covers Kegiatan/Kas/Event Detail/Transparansi/Report Detail/Profil/BelumTersedia), plus `HomeScreen.tsx`, `NotifikasiScreen.tsx`, `LoginScreen.tsx` — each now adds `useSafeAreaInsets().top` to its header's height/padding. | |
| B-002 | 25 Aug 2026 | Login (OTP step) | OTP boxes required manually tapping each box before typing the next digit instead of auto-advancing. | `LoginScreen.tsx` — added `otpRefs` + auto-`.focus()` on the next box after each digit, backspace-to-previous-box, and `autoFocus` on the first box. Verified: typing all 6 digits with zero clicks between boxes now advances through every box and completes login. | |
