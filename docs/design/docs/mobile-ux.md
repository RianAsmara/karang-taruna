# Mobile UX

Product, principles, information architecture, navigation, and the core user
journeys for the RukunMuda mobile app.

Related: `mobile-design-system.md` (tokens), `mobile-components.md` (components),
`mobile-screens.md` (screen specs), `transparency.md` (the domain rules behind
the transparency feature).

## Product

RukunMuda is a community management app for Indonesian youth organizations —
Karang Taruna, Pemuda Kampung, Pemuda RT/RW.

Promise: *Urus kegiatan dan kas kampung dengan rapi, transparan, dan mudah.*

It should feel like a modern community product: friendly, youthful, trustworthy,
simple, transparent, practical. It must not feel like government bureaucracy
software, accounting software, an ERP, a banking app, or a generic SaaS dashboard.

## Users

Young people and community organizers in Indonesia, many not technically
sophisticated. That forces clarity, discoverability, plain language, obvious
actions, low cognitive load, and fast access to the few things that matter.

Roles: **Anggota**, **Panitia**, **Sekretaris**, **Bendahara**, **Ketua**. Their
definitions and tag tones are in `mobile-design-system.md` § Roles; what each can
change is in § Roles and authorization below.

## The nine questions

Members repeatedly ask their committee these. The app exists to answer them
without anyone having to ask:

1. Kas sekarang berapa?
2. Uang digunakan untuk apa?
3. Ada kegiatan apa minggu ini?
4. Iuran saya sudah bayar belum?
5. Saya punya tugas apa?
6. Siapa yang bertanggung jawab?
7. Bagaimana hasil kegiatan kemarin?
8. Mana laporan kasnya?
9. Bagaimana membagikan laporan ke grup WhatsApp?

## Principles

Every design decision traces to one of these. A screen violating two is wrong.

### 1. Jawab sebelum ditanya
The nine questions are answered without asking an administrator. The cash
balance is on Home, not three taps into a menu. Personal dues status is visible
on Home and Profil.

### 2. Transparansi itu default, bukan fitur
Every member sees the same numbers as the treasurer. Roles gate the ability to
*change*, never the ability to *see*. There is no admin-only financial screen.

### 3. Bahasa kampung, bukan bahasa kantor
"Uang masuk", not "Kredit". "Belum bayar", not "Outstanding". No accounting
abbreviations. The glossary in `mobile-design-system.md` is binding — engineers
must not re-translate labels.

### 4. Satu layar, satu pekerjaan
Home summarizes. Kegiatan manages. Kas explains. No screen tries to do all
three. One primary action per screen; everything else drops to a bottom sheet.

### 5. Sinyal bertumpuk
Status is never conveyed by color alone — always color **plus** sign **plus**
text label. Income carries `+` and the word "Masuk"; expense carries `−` and
"Keluar". A grayscale screenshot must still convey every status; that test is
part of definition of done.

## Information architecture

Four bottom tabs. Everything about settings, membership, and the organization
enters through the header avatar, not a tab.

| Tab | Answers | Contains |
| --- | --- | --- |
| **Home** | What is happening in my organization? | Cash summary, next event, my tasks, pending actions, announcements, recent activity |
| **Kegiatan** | What are we working on? | Activity list, activity detail (committee, tasks, attendance, budget, documentation), archive |
| **Kas** | How much do we have, and where did it go? | Balance, month summary, transactions, dues, wallets, **Transparansi**, monthly reports, sponsors |
| **Notifikasi** | What needs me right now? | Needs action, announcements, running votes |

Behind the avatar: Profil saya · Iuran saya · Tugas saya · Ganti organisasi ·
Anggota & peran · Inventaris · Dokumen · Voting · Pengaturan organisasi ·
Bantuan · Keluar.

**Iuran** and **Sponsor** live inside Kas, not behind the avatar — both are cash
concerns, and a treasurer looking for either is already in Kas.

**Why those aren't tabs.** Inventory, documents, sponsors and members get opened
a few times a month, not daily. Putting them in the tab bar would penalize four
daily jobs for the sake of four monthly ones. Voting appears as a Home card and
a Notifikasi item while it is running, then disappears.

## Navigation model

| Pattern | Rule |
| --- | --- |
| Bottom tabs | 4 fixed slots, always visible, height 56 + safe area. Active tab: label at weight 800 plus a 2px accent line above the icon. Badges on Notifikasi only. |
| Header | Left: organization name (tap to switch org). Right: square 32px avatar. Always 56 tall, no shadow, closed by a 2px rule. |
| Depth | Max 3 levels from a tab (Kas → Transparansi → Laporan Agustus). Deeper than that, use a bottom sheet. |
| Bottom sheet | Quick actions and choices — add transaction, filter, change task status. Never for content that needs a shareable link. |
| Dialog | Destructive confirmation only: delete a transaction, reject an approval, leave the organization. |
| Primary action | No floating FAB. The primary action is a full-width block button below the content or in a bottom action bar. |

## Roles and authorization

This is the single authoritative permission table in the documentation set. Every
role sees **everything**; roles differ only in what they can change.

| Role | Can change |
| --- | --- |
| Anggota | Own tasks and attendance · own dues notification · own vote |
| Panitia (per event) | + That event's tasks, attendance, documentation · borrow inventory for it |
| Sekretaris | + Documents · announcements · invite members |
| Bendahara | + Transactions · dues records · sponsors · compose and submit reports |
| Ketua | + Approve and publish reports · assign roles · remove members · organization settings |

### How unauthorized actions behave

Three treatments, chosen by whether the user would expect the action to exist:

1. **Absent, unexplained** — when the action was never plausibly theirs. An
   ordinary member does not see "Tambah sponsor" and needs no explanation for its
   absence. Most cases land here.
2. **Absent, explained** — when a member could reasonably expect to act, or when
   the absence would otherwise read as a bug. A `PermissionNote` states in one
   plain sentence who can act: "Hanya bendahara yang bisa mencatat pembayaran
   iuran." Used on Iuran, Peran & Izin, Susun Laporan, and on any screen reached
   by deep link without rights.
3. **Present, with the reason as the label** — when the action is theirs but
   blocked by workflow, not role: "Menunggu persetujuan ketua".

Never used: disabled buttons for permission reasons, lock icons, alert dialogs on
tap, and the words *admin*, *role*, *permission*, *akses ditolak*. A member never
sees an empty screen because of their role — only a missing button.

Every permission-sensitive screen names its treatment in `mobile-screens.md`
under **States → Permission**.

## Core journeys

### A · Anggota checks the cash — highest frequency, target under 10 seconds
Open app → Home → balance readable in the top block → tap balance → Kas → see
this month's in/out → scroll recent transactions.

### B · Bendahara shares the monthly report to WhatsApp — the signature flow
Kas → Transparansi → review the August summary → Terbitkan laporan → sheet shows
the message text and link → Bagikan ke WhatsApp → report status flips to Terbit.

### C · Panitia completes an activity task
Notifikasi "Anda ditugaskan sebagai PIC konsumsi" → activity detail → Tugas tab →
mark done → prep progress rises 80% → 90% → toast, and the chair is notified.

### D · Anggota pays monthly dues
Home "Iuran Agustus belum dibayar" → Iuran saya → the member pays the treasurer
directly, then taps "Beri tahu bendahara" → status becomes "Menunggu konfirmasi
bendahara" until the treasurer records it. The app records payments; it does not
accept them.

### E · Ketua onboards a new organization
01 Splash → "Buat organisasi baru" → 4 steps (nama & jenis → lokasi → undang
anggota → selesai) → Home, where a "Lengkapi profil organisasi — 2 dari 5" card
carries the deferred setup (logo, iuran rate, bendahara, sekretaris, inventaris).
No feature must be configured before the app is usable.

### F · Panitia creates an activity
04 Kegiatan → "+ Buat kegiatan baru" → step 1 judul & kategori → step 2 tanggal,
jam, lokasi → step 3 panitia & anggaran → "Terbitkan kegiatan" → event detail,
where a "Lengkapi kegiatan" card offers tasks, image, and inventory. Only judul
and tanggal are required; a draft autosaves at every step.

### G · Bendahara records a dues payment
Kas → Iuran → the list already filtered to "Belum bayar" → "Catat pembayaran" →
sheet (member, amount prefilled, tunai/transfer, date) → member flips to "Sudah
bayar", a matching income transaction appears in Kas, toast with "Urungkan".

### H · Anggota votes
Notifikasi "Voting baru tersedia." → 27 Voting — Detail → read the disclosure
block (anonymity · editability · closing time) → select an option → "Kirim
pilihan" → confirmation dialog naming the choice → toast. Nothing is submitted by
tapping an option.

### I · Report reaches the members
Bendahara: Transparansi → Susun laporan → "Kirim untuk diperiksa" (**Draf** →
**Diperiksa**). Ketua: notification → Periksa laporan → "Setujui"
(**Disetujui**) or "Minta perbaikan" with a required reason (back to **Draf**).
Ketua: Transparansi → "Terbitkan" → `SharePreview` → WhatsApp (**Terbit**).
Members see published reports only, and see them in full.

### J · Panitia borrows equipment
Profil → Inventaris → filter "Tersedia" → item detail → "Pinjam" sheet (quantity,
return date, purpose, optional event) → the loan shows on the item and on the
borrower's profile; overdue loans notify the responsible person, not the whole
organization.

### K · Sekretaris files the meeting minutes
Profil → Dokumen → "Unggah dokumen" → file, kategori Notulen, judul, optional
event link → document appears at the top of the current month → "Bagikan" via the
native share sheet.

## Search and filtering

**There is no global search.** Search exists in five lists, scoped to that list:
Anggota, Kegiatan, Transaksi, Dokumen, Inventaris. Those are the only lists that
grow past what scrolling handles, and each has a clear "I know what I am looking
for" case. A global entry point would require a cross-type result taxonomy and an
empty state for six domains at once — cost without a matching job.

Sponsor, Voting, Notifikasi, and Tugas are short and time-ordered; they get
filters instead. Home has neither: it is a summary, not a corpus.

Filtering is one pattern everywhere: a horizontal `FilterChip` row for the one or
two facets that matter most in that list, and a `FilterSheet` for the rest,
reached from a "Filter" button that carries the applied count. Facet lists are
fixed per domain in `mobile-screens.md` § Bottom sheets. Filters and search
compose: entering search keeps the list's filters, shown as removable chips.

## Dues are recorded, not collected

The product records payments the treasurer has already received in cash or by
transfer. There is no payment gateway, no bank integration, and no in-app
transfer. This is stated in the UI, not just in the docs: both 16 Iuran and 17
Iuran Saya carry a fixed note — "Aplikasi mencatat pembayaran yang sudah
diterima. Pembayaran tetap dilakukan langsung ke bendahara."

A member can only *notify* ("Beri tahu bendahara"), which sets **Menunggu
konfirmasi**. Only a treasurer's record sets **Sudah bayar**. That asymmetry is
what keeps the ledger trustworthy, and it is why the member-side flow has no
failure state to design: nothing about money depends on it.

## WhatsApp sharing

WhatsApp is where these organizations already live, so sharing is a first-class
flow — but the app never tries to be a messenger. Every share goes through
`SharePreview`: the user reads the exact outgoing text, then hands it to the
native share sheet.

Shared: financial reports, event announcements, event details, dues reminders,
voting results, invitation codes. Not shared: raw transaction lists, member
contact details, individual dues status.

The report message is fixed and must not be rewritten:

```
RukunMuda

Laporan Kas Pemuda
Agustus 2026

Saldo akhir:
Rp8.450.000

Pemasukan:
Rp2.350.000

Pengeluaran:
Rp1.850.000

Lihat laporan lengkap:
[tautan laporan]
```

Short, verifiable, and ending in a link so no number travels without its source.
Event and reminder messages follow the same shape: what it is, the two or three
numbers or facts that matter, then the link.

## Notifications

Three groups only, unchanged: **Perlu tindakan** (4px accent left border) ·
**Sedang berjalan** · **Kabar lain**. New features add types, not groups.

| Trigger | Audience | Group |
| --- | --- | --- |
| "Voting baru tersedia. Ditutup 20 Agu 20:00." | Eligible members | Perlu tindakan |
| "Anda ditugaskan sebagai panitia konsumsi." | Assignee | Perlu tindakan |
| "Laporan Agustus siap disetujui." | Ketua | Perlu tindakan |
| "Iuran Agustus belum dicatat." | Member, once per period | Perlu tindakan |
| "Ada pembayaran iuran menunggu konfirmasi." | Bendahara, batched daily | Perlu tindakan |
| "Sound system belum dikembalikan — terlambat 3 hari." | Borrower + responsible person | Perlu tindakan |
| "Laporan Agustus dikembalikan untuk diperbaiki." | Bendahara | Perlu tindakan |
| "Kerja Bakti Agustus besok, 07:00." | Committee, then all members | Sedang berjalan |
| "Voting ditutup. Hasil sudah bisa dilihat." | Voters | Kabar lain |
| "Laporan Agustus sudah terbit." | All members | Kabar lain |
| "Notulen rapat 12 Agustus diunggah." | All members | Kabar lain |

Noise rules: one dues reminder per period per member, never a second. Treasurer
and chair notifications about pending items batch to one per day. Event reminders
fire at most twice (day before, morning of). Nothing notifies the whole
organization about one person's action.

## Open product decisions

Settled in this phase: dues are **manual recording only**; a report is published
by the **ketua** after the bendahara submits it, with the step skipped when one
person holds both roles; unauthorized actions are **absent, sometimes explained**,
never disabled.

Still needing a human decision:

- **Who may create a vote, and with what options?** The voting *experience* is
  designed; the creation flow is not, because it depends on whether votes are
  chair-only, whether they can be anonymous by choice, and whether multi-select
  is needed at all.
- **Dues rates.** Flat per member per month is assumed. Per-household or
  per-category rates would change 16 Iuran's summary and the exemption model.
- **How long cached financial data stays valid** before the stale band appears.
- **Whether members may see each other's phone numbers** by default, or only
  after opting in. The design assumes opt-in.
- **Data retention** for members who leave: the design keeps their historical
  transactions and dues records and shows a "Keluar" tag for 30 days.
