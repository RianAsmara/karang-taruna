# Theme Builder (web) — spec

A web admin tool where an organization uploads its logo and sets **one** primary
color. It emits a theme the mobile app consumes at boot. Everything else in the
design system stays fixed.

Related: `mobile-design-system.md` (tokens, rules), `../rn/theme.ts` (source of
truth for token names and defaults).

## What it is not

- Not a design-system editor. Neutrals, type, spacing, radius, shadows, and
  motion are **not** exposed. No new tokens, no second accent, no font picker.
- Not a light/dark switcher. Dark mode is derived, never authored.
- Not a layout tool. Nothing about screens changes.

## Scope

One theme per organization. Fields:

| Field      | Type         | Required | Notes                                                         |
| ---------- | ------------ | -------- | ------------------------------------------------------------- |
| `logo`     | image upload | yes      | PNG/SVG, ≤2 MB, min 512×512, transparent background preferred |
| `logoMono` | derived      | —        | Auto-generated single-ink version for headers and the splash  |
| `primary`  | hex          | yes      | The only authored color. Becomes `accent`.                    |
| `name`     | text         | yes      | Organization display name (wordmark fallback if no logo)      |

The whole builder is three steps on one page: **Upload → Color → Preview**. No
wizard chrome, no save-per-step. One primary button, `Terapkan tema`.

## Which tokens the builder can touch

Six of the nineteen color tokens move. One is authored, five are derived from it.
The other thirteen are fixed in the app binary and cannot be changed by any
theme, ever.

| Token        | Builder     | How                                                              |
| ------------ | ----------- | ---------------------------------------------------------------- |
| `accent`     | **changes** | `= primary`, the one authored value                              |
| `accent200`  | **changes** | derived tint                                                     |
| `accent700`  | **changes** | derived deep step                                                |
| `accent800`  | **changes** | derived pressed step                                             |
| `onAccent`   | **changes** | derived: `bg` or `text`, whichever passes on `accent`            |
| `onInk`      | **changes** | only if `text` inversion demands it; in practice stays `#f3f2f2` |
| `bg`         | fixed       | the ground is the system, not the brand                          |
| `surface`    | fixed       |                                                                  |
| `surfaceAlt` | fixed       | also carries inactive/closed states                              |
| `text`       | fixed       |                                                                  |
| `textMuted`  | fixed       |                                                                  |
| `textFaint`  | fixed       |                                                                  |
| `divider`    | fixed       | 1px rules are structure                                          |
| `rule`       | fixed       | 2px rules are structure                                          |
| `neutral300` | fixed       |                                                                  |
| `skeleton`   | fixed       |                                                                  |
| `income`     | fixed       | `= text`. Money in is ink, never a hue                           |
| `balance`    | fixed       | `= text`                                                         |
| `expense`    | fixed       | `#ae1800` light / `#ff9783` dark — see below                     |

### Why `expense` is not derived

An earlier draft set `expense = accent700`. Wrong: an organization with a green
or blue brand would print money-out in green. `expense` is a fixed deep red ink,
independent of the brand, always paired with `−` and "Keluar".

One consequence: if `primary` is itself a deep red, `accent700` and the fixed
`expense` land near-identical. The builder does not block that — the sign and the
word carry the meaning (rule 3) — but the Kas preview must be checked.

## Color derivation

The user picks `primary`. Everything accent-family is computed in OKLCH from it —
never authored, never stored by hand. Neutrals are untouched: `bg`, `surface`,
`surfaceAlt`, `text`, `textMuted`, `textFaint`, `divider`, `rule`, `neutral300`,
`skeleton` keep the values in `../rn/theme.ts` exactly. No hue-tinted grays.

Let the input be `(L, C, H)` in OKLCH.

| Output token | Rule                                         | Reference (input `#ec3013`) |
| ------------ | -------------------------------------------- | --------------------------- |
| `accent`     | input, clamped to sRGB gamut                 | `#ec3013`                   |
| `accent200`  | `L 0.92`, `C min(C, 0.06)`, `H`              | `#ffe0d9`                   |
| `accent700`  | `L × 0.78`, `C × 0.81`, `H`                  | `#ae1800`                   |
| `accent800`  | `L × 0.61`, `C × 0.65`, `H`                  | `#7c1405`                   |
| `onAccent`   | `bg` if `accent` is dark enough, else `text` | `#f3f2f2`                   |

`income`, `balance`, and `expense` are not derived. See the table above.

Dark mode is derived from the same input, not authored:

| Token       | Rule                   | Reference |
| ----------- | ---------------------- | --------- |
| `accent`    | `L + 0.09`, `C × 0.88` | `#ff563c` |
| `accent200` | `= light accent800`    | `#7c1405` |
| `accent700` | `L 0.78`, `C × 0.55`   | `#ff9783` |
| `accent800` | `L 0.87`, `C × 0.38`   | `#ffc4b8` |
| `onAccent`  | dark `bg`              | `#1a1918` |

`expense` in dark mode stays the fixed `#ff9783`.

### Guards (block save, don't warn-and-allow)

1. `accent` vs light `bg` ≥ 3:1 **and** dark `accent` vs dark `bg` ≥ 3:1 —
   chrome and large type must hold.
2. `onAccent` vs `accent` ≥ 4.5:1 — button labels are body-size.
3. `accent700` vs `surface` ≥ 4.5:1 in both modes — it carries accent body text
   and error titles.
4. Chroma floor: `C ≥ 0.05`. A near-gray primary makes the accent invisible
   against neutrals; reject it.

When a guard fails, the picker shows the failing check in words plus the nearest
passing color as a one-tap fix ("Gunakan #1E5B3B"). Never a disabled save button
with no explanation — see `mobile-ux.md`, permission and error patterns.

## Logo handling

- Accept PNG and SVG. Reject JPEG with a reason (no transparency).
- Trim transparent padding, then fit to a square canvas with 8% breathing room.
- Emit: `mark@1x/2x/3x` (96 px base), `icon-1024`, and `mono` (all opaque pixels
  flattened to `text`, used on the splash and in the header).
- **Radius 0.** The logo is never masked into a rounded square or circle,
  including the in-app avatar slot. The Android adaptive-icon export is the only
  place the platform rounds it, and that is the platform's doing.
- Show the logo on `bg`, on `surface`, and on `accent` in the preview. If it
  fails on `accent`, say so and use `mono` there.

## AI color suggestion

Optional but expected. On upload, extract candidates and present them; the user
still confirms.

- Quantize the logo's opaque pixels in OKLCH, drop near-white/near-black and
  low-chroma clusters, sort by `chroma × coverage`.
- Offer up to 4 swatches with hex labels. Each shows a pass/fail badge against
  the four guards; failing candidates are shown with their nearest passing
  neighbor, not hidden.
- Label it a suggestion (`Warna dari logo`). It pre-selects nothing — the user
  taps to apply.
- If extraction finds nothing usable, fall through to the manual picker with a
  plain line: `Tidak ada warna yang cukup kuat di logo. Pilih manual.`

## Preview

Live, on real screens — not swatch chips. Reuse the built mobile components in a
phone frame at 390×844, radius 0, and offer three:

1. **Home** — header with logo, balance card, primary button.
2. **Kas** — income/expense rows (proves `expense` is legible and signed).
3. **Poster block** — the full-bleed accent statement (proves `onAccent`).

A light/dark toggle and a **grayscale toggle**. The grayscale test is a
first-class control here: if a preview stops being readable in grayscale, the
theme is wrong, and the builder should say which screen failed.

## Output contract

Stored per organization, served to mobile:

```
GET /api/v1/organizations/:id/theme
```

```json
{
  "version": 1,
  "updatedAt": "2026-08-27T00:00:00Z",
  "name": "Kampung Al-Abror",
  "primary": "#1E5B3B",
  "logo": {
    "mark": "…/mark@3x.png",
    "icon": "…/icon-1024.png",
    "mono": "…/mono.png"
  },
  "color": {
    "light": {
      "accent": "…",
      "accent200": "…",
      "accent700": "…",
      "accent800": "…",
      "onAccent": "…",
      "onInk": "…"
    },
    "dark": {
      "accent": "…",
      "accent200": "…",
      "accent700": "…",
      "accent800": "…",
      "onAccent": "…",
      "onInk": "…"
    }
  }
}
```

Rules:

- The response carries **only** the six changeable tokens and logo URLs. A key
  outside that set is ignored, not applied. Neutrals, finance colors, type,
  spacing, radius, shadow, motion are never transmitted — they live in the app
  binary. This keeps the design system unbreakable from the server.
- Mobile merges the response over `light`/`dark` from `../rn/theme.ts`. Unknown
  keys are ignored. A malformed or missing theme falls back to the default red
  silently, and the app logs it — never a blocked boot, never a half-themed UI.
- Cache the theme and the logo files on device; apply from cache at launch, then
  revalidate. A theme change mid-session applies on next launch, not live.
- Mobile does no color math. If a derived token is absent, use the default.

## Permissions and audit

Only `ketua` can open the builder and apply a theme; `bendahara` and `anggota`
do not see the entry point at all (absent, not disabled). Every apply writes an
audit entry with the actor, the previous `primary`, and the new one, and the last
five themes are restorable.

## States

Loading, empty (no theme set → default red with `Tema belum diatur`), upload
error (size/format/dimension, each with its own line), guard failure, saving,
success toast, and offline (builder is read-only, apply is queued and stated as
queued).
