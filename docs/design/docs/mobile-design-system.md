# Mobile Design System

Tokens, status vocabularies, and the binding copy glossary for RukunMuda.

Related: `mobile-ux.md` (principles, IA, journeys), `mobile-components.md`
(components), `mobile-screens.md` (screen specs).

The machine-readable source is `../rn/theme.ts` and `../rn/type.ts`. **Those
files win over prose.** Nothing in this document introduces a value that is not
in them, except where a new token is listed under "Additions in this phase".

## Rules that govern the whole system

1. **Radius 0 everywhere** — including avatars, inputs, images, sheets, dialogs,
   tags, and progress bars. There is no rounded corner in the product.
2. **Structure, not decoration.** Hierarchy comes from type weight, flush-left
   alignment, and rules: 1px `divider` between rows, 2px `rule` between sections.
   No shadow inside a screen; shadows exist only on sheets, dialogs, toasts.
3. **Signals stack.** No status is carried by color alone. Every status = color +
   sign or icon + word. A grayscale screenshot must read identically.
4. **Red is brand, not danger.** `accent` marks the primary action and the poster
   blocks. Expense uses `accent700` (`#ae1800`), which is a distinct, darker ink —
   and never without the `−` sign and the word "Keluar".
5. **One primary action per screen.** Everything else is secondary, ghost, or in
   a bottom sheet.
6. **Type weights are 400 / 600 / 800 only.** No italic, no 300, no 700. Every
   title is 800.

## Color

Full objects in `../rn/theme.ts` (`light` and `dark`). Dark mode is a token
inversion; no layout changes anywhere.

| Token | Light | Role |
| --- | --- | --- |
| `bg` | `#f3f2f2` | Screen ground |
| `surface` | `#ffffff` | Cards, rows, sheets |
| `surfaceAlt` | `#eae9e9` | Inset blocks, table headers, disabled fills |
| `text` | `#201e1d` | Ink — titles, body, balance, income |
| `textMuted` | `#6b6867` | Meta, section headers, secondary lines |
| `textFaint` | `#8b8887` | Timestamps, helper text, placeholders |
| `divider` | `#c9c6c5` | 1px row rules, input borders |
| `rule` | `#8d8988` | 2px section rules, header/tab-bar edges |
| `accent` | `#ec3013` | Primary action, poster blocks, needs-action marker |
| `accent200` | `#ffe0d9` | Accent tint fills (tag backgrounds, warning bands) |
| `accent700` | `#ae1800` | Expense figures, accent-colored body text, error titles |
| `accent800` | `#7c1405` | Pressed accent |
| `neutral300` | `#d7d3d3` | Outline tag borders, unfilled progress segments |
| `skeleton` | `#e3e0e0` | Loading blocks |
| `income` / `balance` | `#201e1d` | Money in, balance — ink, never green |
| `expense` | `#ae1800` | Money out |
| `onAccent` / `onInk` | `#f3f2f2` | Text on accent or ink fills |

### Additions in this phase

Only two, both aliases of existing values — no new hues:

| Token | Value | Why it was needed |
| --- | --- | --- |
| `surfaceAlt` used as **inactive/closed** fill | `#eae9e9` | Closed voting, returned inventory, archived documents, and cancelled events all need a "no longer live" fill. Reusing `surfaceAlt` avoids inventing a gray. |
| `neutral300` used as **pending** outline | `#d7d3d3` | Awaiting-review states (report under review, dues awaiting confirmation) use an outline tag rather than a fill, so pending never reads as done. |

No feature earned a new color. Requests for "a green for paid" and "a yellow for
pending" were refused: paid is `text` + "Sudah bayar", pending is an outline tag
+ "Menunggu konfirmasi". See rule 3 and 4.

## Type

Full scale in `../rn/type.ts`.

| Style | Size / line | Weight | Used for |
| --- | --- | --- | --- |
| `display` | 40 / 42 | 800 | Splash wordmark, poster blocks |
| `h1` | 30 / 33 | 800 | Screen titles on detail screens |
| `h2` | 26 / 30 | 800 | Report titles, step headings |
| `title` | 17 / 22 | 800 | Card titles, list item titles, sheet titles |
| `section` | 11 / 14 | 800, uppercase, +1.0 | `SectionHeader`, field labels |
| `body` | 15 / 23 | 400 | Paragraphs, descriptions |
| `bodySm` | 13 / 19 | 400 | Meta lines, helper text |
| `caption` | 11 / 15 | 600, +0.7 | Tags, badges, timestamps |
| `numHero` | 44 / 46 | 800, tabular | Balance on Home, Kas, Transparansi |
| `numLg` | 20 / 24 | 800, tabular | Section totals, sponsorship amounts |
| `numRow` | 15 / 20 | 800, tabular | Money inside list rows |

`fontVariant: ['tabular-nums']` is mandatory on every money value, including
inside lists and tables. Money never renders in `body`.

## Spacing and layout

Base 4: `xs 4 · sm 8 · md 12 · lg 16 · xl 24 · xxl 32 · xxxl 48`.

| Measure | Value |
| --- | --- |
| Screen horizontal padding | 16 |
| Content width at 390 device | 358 |
| Header height | 56 + safe area, 2px `rule` bottom edge |
| Tab bar height | 56 + safe area, 2px `rule` top edge |
| List row min height | 64 |
| Button height | 48 (44 inside a dialog) |
| Minimum touch target | 48 × 48 including `hitSlop` 12 |
| Section gap | 24 above a `SectionHeader`, 32 above a 2px `rule` |

## Motion

`sheetIn 240 · sheetOut 180 · fade 160 · toast 2600 · toastUndo 6000` ms.
Opacity and translate only — no scale, no spring, no bounce. Everything respects
`AccessibilityInfo.isReduceMotionEnabled()`, in which case transitions become
instant and skeletons stop blinking.

## Status vocabularies

Each domain has a **closed** set of statuses. Engineers must not add values;
designers must not rename them. Every status renders as a `Tag` with the tone
below plus its literal Indonesian label.

### Money direction
| Meaning | Sign | Label | Color |
| --- | --- | --- | --- |
| Income | `+` | Masuk | `income` |
| Expense | `−` | Keluar | `expense` |

### Event (`Kegiatan`)
| Status | Label | Tag tone |
| --- | --- | --- |
| Draft | Draf | outline |
| Published | Terbit | tint |
| Ongoing | Berjalan | accent |
| Completed | Selesai | solid |
| Cancelled | Dibatalkan | outline on `surfaceAlt` |

### Financial report (`Laporan`)
| Status | Label | Tag tone |
| --- | --- | --- |
| Draft | Draf | outline |
| Submitted for review | Diperiksa | outline |
| Approved, not yet public | Disetujui | tint |
| Published | Terbit | solid |

### Dues (`Iuran`)
| Status | Label | Tag tone |
| --- | --- | --- |
| Recorded as paid | Sudah bayar | solid |
| Awaiting treasurer confirmation | Menunggu konfirmasi | outline |
| Not recorded | Belum bayar | tint (accent) |
| Exempt this period | Dibebaskan | outline on `surfaceAlt` |

### Inventory (`Inventaris`)
Availability: **Tersedia** (solid) · **Dipinjam** (tint) · **Tidak tersedia**
(outline on `surfaceAlt`).
Condition: **Baik** · **Perlu perbaikan** · **Rusak** — condition always renders
as plain text with its label, never as a colored dot.

### Sponsor
Type: **Uang** · **Barang** · **Jasa**.
Status: **Diajukan** (outline) · **Setuju** (tint) · **Diterima** (solid) ·
**Batal** (outline on `surfaceAlt`).

### Voting
| Status | Label | Tag tone |
| --- | --- | --- |
| Open, not yet voted | Belum memilih | accent |
| Open, vote recorded | Sudah memilih | solid |
| Closed | Ditutup | outline on `surfaceAlt` |

Anonymity is **never** a tag. It is a full sentence on the voting detail screen:
"Pilihan Anda tidak akan terlihat siapa pun." or "Pilihan Anda terlihat oleh
pengurus." See `mobile-screens.md` § Voting.

### Document (`Dokumen`)
Categories: **Proposal · Notulen · Laporan · Surat · Dokumen organisasi**.
No status; documents are either present or archived (**Diarsipkan**, outline on
`surfaceAlt`).

## Roles

Five roles. `Anggota` is the baseline everyone holds; the other four are added on
top. "Pengurus" is the collective word shown in the UI for anyone holding a
management role — the app never says "admin", "role", "permission", or "RBAC".

| Role | UI label | Tag tone | Scope |
| --- | --- | --- | --- |
| Member | Anggota | outline | Permanent |
| Chair | Ketua | accent | Permanent, one per org |
| Treasurer | Bendahara | solid | Permanent, one or two |
| Secretary | Sekretaris | solid | Permanent |
| Event committee | Panitia | tint | **Per event**, temporary |

`Panitia` is an event assignment, not an organization role: it appears on the
event, expires with it, and is shown as "Panitia · Kerja Bakti Agustus".

Permission mapping is in `mobile-ux.md` § Roles and authorization. It is stated
there once; no other document restates it.

## Copy glossary — binding

Engineers must not re-translate. Where a string is listed here, use it verbatim.

| Use this | Never this |
| --- | --- |
| Uang masuk / Uang keluar | Debit / Kredit, Pemasukan / Pengeluaran in rows |
| Saldo kas | Balance, Cash on hand |
| Belum bayar | Tunggakan, Outstanding, Overdue |
| Sudah bayar | Lunas, Paid |
| Menunggu konfirmasi bendahara | Pending approval |
| Kegiatan | Event, Acara |
| Panitia | Committee, PIC team |
| Pengurus | Admin, Pengelola |
| Anggota | User, Member |
| Barang | Aset, Inventory item |
| Pinjam / Kembalikan | Checkout / Check-in |
| Dokumen | File, Berkas |
| Voting · Pilihan · Suara | Poll, Ballot, Option |
| Bagikan ke WhatsApp | Share, Export |
| Catat | Input, Submit, Record data |
| Simpan sebagai draf | Save draft |
| Terbitkan | Publish, Go live |
| Belum ada … | No data, Empty |
| Hanya bendahara yang bisa mencatat transaksi. | Access denied, Unauthorized, No permission |

Tone: plain, short, second person ("Anda"), no exclamation marks, no emoji.
Numbers use `id-ID` grouping via `formatRupiah()` in `../rn/format.ts`.

## The grayscale test

Definition of done for every screen: take a screenshot, desaturate it, and check
that direction of money, status of every item, active tab, active filter, and
which action is primary are all still readable. If anything becomes ambiguous,
the screen is wrong — add a sign, a label, or a rule, not a color.
