# Handoff: RukunMuda — Mobile Product Design

## Overview

RukunMuda is a community management app for Indonesian youth organizations
(Karang Taruna, Pemuda Kampung, Pemuda RT/RW). It helps them run activities,
committees, tasks, members, attendance, community cash, dues, budgets, and
financial reports.

Product promise: *"Urus kegiatan dan kas kampung dengan rapi, transparan, dan mudah."*

Core principle: **transparency**. Every member sees the same financial numbers
as the treasurer; roles gate what you can *change*, never what you can *see*.

This package contains the complete design specification for the first
implementation pass: 10 core screens + 1 dark-mode screen.

## About the Design Files

The three `.dc.html` files in this bundle are **design references authored in
HTML** — prototypes that show intended look, layout, and behavior. They are
**not production code to copy**.

The task is to **recreate these designs in React Native** using the target
project's own patterns and libraries. `RukunMuda RN Handoff.dc.html` is the
binding implementation spec; the other two files explain the reasoning and let
you see the intended result at 1:1.

Do not port the HTML, the DC runtime, or the design-system CSS into the app.

## Fidelity

**High fidelity.** Colors, typography, spacing, copy, and interaction states
are final and specified numerically. Recreate the UI faithfully. Where a value
is not written down, ask — do not invent it.

## Files in this bundle

| File | Role |
| --- | --- |
| `RukunMuda RN Handoff.dc.html` | **Start here.** The implementation spec: tokens, type scale, navigator tree, component prop tables, per-screen specs, copy rules, accessibility acceptance criteria, build order, and a "don't" list. |
| `RukunMuda Core Screens.dc.html` | Live interactive prototype at 390×844. All 11 screens run off shared state — tapping a task updates progress on three screens. Use it to check layout and behavior. |
| `RukunMuda Foundations.dc.html` | The reasoning: UX principles, information architecture, navigation model, four user journeys, visual direction, and rendered component specimens at real content width (358px). |

Open the two HTML files in a browser to view them. `support.js` and the
`_ds/` folder are the rendering runtime and the visual style reference used to
author the docs — they are **not** app dependencies.

## How to use this with Claude Code

Point the agent at this folder and let it read in this order:

1. this README
2. `screens/README.md` then each `screens/NN-*.md` — one reference per screen, with its screenshot, exact tokens and exact copy
3. `rn/` — starter files to drop into the app: `theme.ts`, `type.ts`, `format.ts`, `navigation.ts`
4. `RukunMuda RN Handoff.dc.html` — component prop tables (section 05) and accessibility criteria (section 09)

Suggested prompt:

> Read design_handoff_rukunmuda/README.md, then the per-screen references in
> design_handoff_rukunmuda/screens/. Copy the files in design_handoff_rukunmuda/rn/
> into the app as the token layer, build the shared components from section 05 of
> the RN Handoff doc, then implement the screens in the order given in
> screens/README.md.

## Folder contents

| Path | What it is |
| --- | --- |
| `docs/mobile-ux.md` | Principles, IA, navigation, all user journeys, authorization behavior, search rationale, sharing, notifications, open decisions. |
| `docs/mobile-design-system.md` | Tokens, status vocabularies, roles, the binding copy glossary. |
| `docs/mobile-components.md` | Every component, its props, and what was deliberately not created. |
| `docs/mobile-screens.md` | All 33 screens and 10 sheets, each with layout, states, and edge cases. |
| `screens/NN-*.md` | **Per-screen implementation references.** Route, block order, tokens, copy, behavior, screenshot. |
| `shots/NN-screen.png` | Screenshots of each screen at 390×844, rendered from the prototype. |
| `rn/theme.ts` | Light and dark token objects. Drop in as-is. |
| `rn/type.ts` | The type scale. Archivo 400/600/800 only. |
| `rn/format.ts` | Money, date and screen-reader formatting. One TODO: id-ID number-to-words. |
| `rn/navigation.ts` | Navigator tree, tab config, tab-bar spec as comments. |
| `RukunMuda RN Handoff.dc.html` | Full spec: tokens, component prop tables, per-screen tables, copy glossary, a11y criteria, build order, don't-list. |
| `RukunMuda Core Screens.dc.html` | Interactive prototype. Open in a browser to check behavior. |
| `RukunMuda Foundations.dc.html` | The reasoning: principles, IA, flows, component specimens. |

Where a screenshot and a spec table disagree, **the table wins**.

## Screens

Ten core screens plus one dark-mode variant, listed below, were designed and
prototyped in phase one; full layout tables are in section 06 of the RN Handoff
doc. Phase two added 22 more screens and 10 sheets \u2014 those are specified in
`docs/mobile-screens.md` and are **not** in the prototype.

1. **Splash** — full-bleed accent poster, wordmark, promise, two entry buttons
2. **Login** — WhatsApp number + 6-digit code, no password
3. **Home** — balance first, then pending actions, then upcoming event, my tasks, announcement, recent activity
4. **Kegiatan** — activities grouped by time, each card carrying prep progress
5. **Event Detail** — what/when/where/who, then tabs: Ringkasan / Tugas / Anggaran
6. **Kas** — balance, month summary, transactions, dues, route to transparency
7. **Transparansi** — the signature screen: red poster block, opening/income/expense/closing, report publication status, "Bagikan ke WhatsApp"
8. **Report Detail** — overview, income by source, expense by category, all records with evidence
9. **Notifikasi** — three groups only: needs action, in progress, other news
10. **Profil & Organisasi** — identity, stats, organization menus (everything non-daily lives here)
11. **Transparansi — dark mode** — token inversion only, identical layout

## Interactions & Behavior

- Bottom tabs: 4 fixed slots (Home, Kegiatan, Kas, Notifikasi). Never a fifth.
- Max 3 levels deep from a tab; deeper interactions use bottom sheets.
- Bottom sheets for quick actions and forms; dialogs only for destructive confirms.
- No floating FAB — the primary action is a full-width block button in a bottom action bar.
- One primary (accent-filled) button per screen.
- Transitions ≤ 240ms, opacity and translate only; respect reduce-motion.
- Every data-changing action raises a toast; undoable ones offer "Urungkan" for 6s.
- Loading is per-block skeletons, never a full-screen spinner.
- Network errors keep cached data on screen with a "data as of…" band plus retry.

## State Management

Framework choice is the implementing team's. The prototype's state shape shows
the minimum needed: current screen, active event tab, open sheet, toast message,
task done flags, dues paid flag, selected period range. Real app state adds
auth session, org membership, role, and the financial/event data itself.

## Design Tokens

All tokens, with light and dark objects, are in section 02 of the RN Handoff doc
(`theme.ts`). The type scale is section 03 (`type.ts`). Summary:

- Ground `#f3f2f2`, surface `#ffffff`, ink `#201e1d`, accent `#ec3013`
- Income = ink `#201e1d`; expense = `#ae1800`; balance = ink, never red
- **Radius 0 everywhere**, including avatars, inputs, sheets, dialogs
- Spacing base 4: 4 / 8 / 12 / 16 / 24 / 32 / 48
- Archivo at 400 / 600 / 800 only — no italic, no 300, no 700
- All money uses `fontVariant: ['tabular-nums']` and a shared `formatRupiah()`
- Shadows only on sheets, dialogs, toasts — never on in-screen cards

## Accessibility

Binding acceptance criteria are in section 09 of the RN Handoff doc. The two
that most often get skipped:

- **Grayscale test is part of definition of done.** A grayscale screenshot must
  still convey every status — sign, tag, and label carry meaning, not color.
- Touch targets ≥ 48×48 including hitSlop; text scaling tested at 200%, with the
  balance income/expense row switching to a vertical stack above 130%.

## Copy

UI copy is Indonesian and **must not be re-translated or rewritten**. The
glossary (section 08) is binding: "Uang masuk / Uang keluar", not "Debit /
Kredit"; "Belum bayar", not "Tunggakan". The WhatsApp share message template is
specified character for character.

## Assets

No production assets are included. Still needed from the client:

- Organization logo/mark
- Activity photography for the documentation gallery
- Licensed Archivo font files for app bundling
- Lucide icons via `lucide-react-native`

## Out of scope

The second design phase closed the previously open areas — members & roles,
inventory, documents, sponsors, voting, event creation, search, filters,
onboarding, dues, and report publishing are all specified in `docs/`.

Still undesigned, ask first: the **vote creation** flow, dues rate models beyond
flat monthly, and organization-level settings beyond what onboarding sets. Open
product decisions are listed at the end of `docs/mobile-ux.md`.
