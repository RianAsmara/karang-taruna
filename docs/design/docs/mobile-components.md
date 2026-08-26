# Mobile Components

Every component in the RukunMuda app. Screens may only use components defined
here; components may only use tokens defined in `mobile-design-system.md`.

Prop tables for the twenty original components also exist in section 05 of
`../RukunMuda RN Handoff.dc.html` with pixel-level specification. This file is
the canonical list, adds the components the new features required, and records
what was deliberately **not** created.

Conventions: `radius 0` everywhere · `hitSlop 12` on every pressable · 1px
`divider` between rows, 2px `rule` between sections · no shadow except on
`BottomSheet`, `Dialog`, `Toast`.

## Core (existing — unchanged)

| Component | Props | Notes |
| --- | --- | --- |
| `Button` | `variant: 'primary'\|'secondary'\|'ghost'`, `label`, `onPress`, `disabled`, `loading`, `block` | Height 48 (44 in dialog). Label 15/800, **flush left** when block. One primary per screen. |
| `Tag` | `tone: 'solid'\|'tint'\|'outline'\|'accent'`, `label`, `icon?` | 10–11px/800 uppercase. Status labels come from the closed vocabularies. |
| `Avatar` | `size: 24\|28\|32\|48\|56`, `initials`, `tone?` | Square. Never a circle. |
| `SectionHeader` | `title`, `action?`, `onAction?` | `type.section`, `textMuted`. Action is a ghost text link on the right. |
| `Card` | `children`, `onPress?`, `inverted?` | `surface` fill, no shadow. `inverted` = ink fill for the one emphasized card per screen. |
| `ListItem` | `title`, `subtitle?`, `leading?`, `trailing?`, `onPress?` | Min height 64. The base for most new list rows below. |
| `TransactionItem` | `kind:'in'\|'out'`, `title`, `meta`, `amount`, `status?` | Sign + label + color. |
| `EventItem` | `date`, `title`, `time`, `place`, `progress?`, `people?`, `state?` | Left date block 48 wide, 2px right border. |
| `TaskItem` | `title`, `meta`, `done`, `priority?`, `onToggle` | 20×20 square checkbox. |
| `BalanceDisplay` | `amount`, `caption`, `breakdown?`, `income?`, `expense?` | `numHero`, tabular. |
| `ReportSummary` | `opening`, `income`, `expense`, `closing`, `status` | 2px ink frame. |
| `Progress` | `variant:'segmented'\|'bar'`, `value`, `total`, `label` | Height 10 (6 in compact cards). |
| `BottomSheet` | `visible`, `title`, `onClose`, `children` | Backdrop `rgba(32,30,29,0.45)`, `shadow.lg`. |
| `Dialog` | `visible`, `title`, `body`, `confirmLabel`, `onConfirm`, `onCancel` | **Destructive or irreversible actions only.** |
| `Toast` | `message`, `actionLabel?`, `onAction?` | Ink fill, 74 from bottom. `Urungkan` for 6s where undoable. |
| `Segmented` | `options` (max 3), `value`, `onChange` | Active = ink fill. |
| `Tabs` | `items`, `value`, `onChange` | Flush left, 2px container underline. |
| `EmptyState` | `title`, `body`, `actionLabel?`, `onAction?` | Flush left, 28×28 2px square mark. |
| `Skeleton` | `width`, `height` | `skeleton` block, opacity blink 1.2s. |
| `ErrorState` | `title`, `body`, `onRetry`, `staleAt?` | 4px left `accent` border. Keeps stale data on screen. |

## New in this phase

Twelve components. Each is here because no existing component could carry it —
the reuse decision is recorded in the last column.

| Component | Props | Specification | Why not reuse |
| --- | --- | --- | --- |
| `MemberItem` | `initials`, `name`, `roles: string[]`, `meta?`, `duesStatus?`, `onPress?` | `ListItem` geometry: `Avatar` 32 leading, name 15/800, meta 13 `textMuted`, up to two `Tag`s on the second line (overflow becomes "+2"), optional dues `Tag` trailing. Min height 64, 1px `divider`. | `ListItem` can't hold two tags plus a trailing status without the trailing slot overflowing at 358px. |
| `RoleRow` | `role`, `description`, `holders: Member[]`, `onPress?`, `editable` | Role name 15/800 + `Tag` in the same tone as the role, description 13 `textMuted`, holder avatars 24 in a row with gap 4. Used only on `Peran & Izin`. | Needs an avatar stack and a plain-language capability description; not a list row. |
| `InventoryItem` | `name`, `category`, `available`, `total`, `condition`, `status`, `onPress?` | `ListItem` + trailing two-line block: `numRow` "3/8" over an availability `Tag`. Condition renders in the meta line as text. | Quantity fraction plus two statuses exceeds the trailing slot. |
| `DocumentItem` | `title`, `category`, `uploader`, `date`, `sizeLabel`, `kind`, `onPress?` | Leading 28×28 1px `divider` square holding a 2–4 letter type mark (PDF, DOC, JPG) at 9/800 — no file-type icons, no colors per type. Meta: "Notulen · Rini · 12 Agu · 240 KB". | The type mark is a documented pattern, not a generic leading slot. |
| `SponsorItem` | `name`, `type`, `amount?`, `status`, `eventName?`, `onPress?` | Name 15/800, meta "Kerja Bakti Agustus · Barang", trailing `numLg` amount (or "—" for in-kind) over status `Tag`. | Same trailing-stack reason as inventory. |
| `VoteOption` | `label`, `description?`, `selected`, `disabled`, `result?`, `onPress` | 20×20 square selector (filled ink + ✓ when selected), label 15/800, optional description 13. When `result` is present the row gains an ink bar (`Progress` variant `bar`, height 6) plus "18 suara · 45%" in `numRow`. Min height 56. | `TaskItem` is a completion toggle with different semantics and no result state. |
| `FilterChip` | `label`, `active`, `count?`, `onPress` | Height 32, padding 6×12, 1px `divider` border; active = ink fill + `onInk` text. In a horizontally scrolling row with gap 8, screen padding 16. | `Tag` is non-interactive and 10px; chips are touch targets. |
| `FilterSheet` | `sections: FilterSection[]`, `value`, `onApply`, `onReset` | `BottomSheet` with `SectionHeader` per facet, options as `FilterChip` rows or a `Segmented`, a 1px `divider` between facets, and a fixed footer: ghost "Reset" left, primary "Terapkan" right. Applied count returns to the trigger button as "Filter · 2". | A composition of `BottomSheet` + chips, defined once so every domain filters identically. |
| `StepIndicator` | `steps: string[]`, `current` | Row of 2px bars, gap 4, filled `text` for done/current and `neutral300` ahead; above it "Langkah 2 dari 3" in `caption` and the step name in `title`. | The only progress that is navigational, not quantitative. |
| `FormSection` | `title`, `hint?`, `children` | `SectionHeader` + fields stacked with gap 12, closed by a 2px `rule`. Field labels are `type.section`; inputs 48 high, 1px `divider` border, radius 0. | Groups fields consistently across the four new forms. |
| `PermissionNote` | `body`, `roleHint`, `actionLabel?`, `onAction?` | Inset `surfaceAlt` block, padding 12–16, no border. Body 13 `text`: a full sentence naming who can act — "Hanya bendahara yang bisa mencatat transaksi." Optional ghost action "Hubungi bendahara". **Never** a disabled button, never an alert, never a lock icon. | Replaces the anti-pattern of disabled controls; see `mobile-ux.md` § Roles and authorization. |
| `SharePreview` | `lines: string[]`, `linkLabel`, `onShare`, `onCopy` | `BottomSheet` showing the exact outgoing message in a `surfaceAlt` block, 13/400, line breaks preserved, left `rule` 2px. Footer: primary "Bagikan ke WhatsApp", ghost "Salin teks". The preview is the literal string that will be sent — no approximation. | Trust requirement: the user must read what leaves the app before it leaves. |

`SearchField` is **not** a new component: it is `FormSection`'s input at height
48 with a leading 20px search glyph, a trailing "Hapus" ghost when non-empty, and
`autoFocus` on the search screen. Documented here so it is not reinvented.

## Deliberately not created

| Asked for | Decision |
| --- | --- |
| `RoleBadge` | Use `Tag` with the role's tone from `mobile-design-system.md` § Roles. A second badge component would drift. |
| `MemberCard` | `MemberItem` in a list; the detail screen uses an identity block already specified on `10 Profil & Organisasi`. |
| `StatusBadge` per domain | One `Tag`, six closed vocabularies. |
| `FAB` | The product has no floating action button. Primary actions are block buttons or bottom action bars. |
| `Accordion` | Progressive disclosure uses steps (`StepIndicator`) or a sheet, never in-place expansion of form sections. |
| `Calendar picker` | Date entry is a native platform picker opened from a `FormSection` row; no custom calendar. |
| `Chart` components beyond bars | Reports use ink bars only, as already specified on `08 Report Detail`. |

## State treatments

Every screen must define all eight. Defaults, so screens only note deviations:

| State | Treatment |
| --- | --- |
| Loading | Per-block `Skeleton` matching the real block's geometry. Never a full-screen spinner. Lists render 3 skeleton rows. |
| Empty | `EmptyState`, flush left, with a first-run action where the user can create the thing. Copy names the thing: "Belum ada barang." |
| Error | `ErrorState`, 4px left `accent`. Cached data stays visible above it with a "Data per 12 Agu 19:04" band. |
| Success | `Toast`, 2.6s. Undoable actions get "Urungkan" for 6s. No success dialogs. |
| Disabled | Only for a control that is temporarily unusable for a **non-permission** reason (form incomplete, request in flight): 45% opacity, no press feedback. Permission never produces a disabled control. |
| Permission denied | `PermissionNote`. The action is absent; the sentence explains who can do it. |
| Confirmation | Inline via `Toast` for reversible actions. `Dialog` only when irreversible. |
| Destructive confirmation | `Dialog` with the object named in the title ("Hapus transaksi Rp250.000?"), body stating the consequence, confirm label naming the verb ("Hapus"), cancel "Batal". Confirm is `primary`; there is no red-only signal. |
| Offline | Last cached view stays on screen with the stale band. Write actions queue and show "Akan dikirim saat online" in the toast; queued items carry an outline "Menunggu kirim" `Tag`. |
