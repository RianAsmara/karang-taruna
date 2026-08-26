# Mobile Screens

Every screen in the RukunMuda app. Screens 01–11 were specified in the first
pass; 12–39 are new in this phase.

Each new screen carries: purpose · user goal · entry · exit · layout (top to
bottom) · components · primary action · secondary actions · behavior · states ·
edge cases. Components are only those defined in `mobile-components.md`; tokens
only those in `mobile-design-system.md`.

Route names are the navigator names in `../rn/navigation.ts`.

## Index

| # | Screen | Route | Tab / parent |
| --- | --- | --- | --- |
| 01 | Splash | `Splash` | — |
| 02 | Login | `Login` | — |
| 03 | Home | `Home` | Home |
| 04 | Kegiatan | `Kegiatan` | Kegiatan |
| 05 | Event Detail | `EventDetail` | Home + Kegiatan |
| 06 | Kas | `Kas` | Kas |
| 07 | Transparansi | `Transparansi` | Kas |
| 08 | Report Detail | `LaporanDetail` | Kas |
| 09 | Notifikasi | `Notifikasi` | Notifikasi |
| 10 | Profil & Organisasi | `Profil` | modal |
| 11 | Transparansi — dark | — | token variant |
| 12 | Anggota | `Anggota` | Profil |
| 13 | Anggota — Detail | `AnggotaDetail` | Anggota |
| 14 | Peran & Izin | `Peran` | Profil |
| 15 | Undang Anggota | `UndangAnggota` | Anggota |
| 16 | Iuran | `Iuran` | Kas |
| 17 | Iuran Saya | `IuranSaya` | Profil / Home |
| 18 | Inventaris | `Inventaris` | Profil |
| 19 | Inventaris — Detail | `InventarisDetail` | Inventaris |
| 20 | Tambah / Ubah Barang | `BarangForm` | Inventaris |
| 21 | Dokumen | `Dokumen` | Profil |
| 22 | Dokumen — Detail | `DokumenDetail` | Dokumen |
| 23 | Sponsor | `Sponsor` | Kas |
| 24 | Sponsor — Detail | `SponsorDetail` | Sponsor |
| 25 | Tambah / Ubah Sponsor | `SponsorForm` | Sponsor |
| 26 | Voting | `Voting` | Notifikasi / Home |
| 27 | Voting — Detail | `VotingDetail` | Voting |
| 28 | Voting — Hasil | `VotingHasil` | Voting |
| 29 | Buat Kegiatan | `BuatKegiatan` | Kegiatan |
| 30 | Susun Laporan | `SusunLaporan` | Kas |
| 31 | Periksa Laporan | `PeriksaLaporan` | Notifikasi / Kas |
| 32 | Pencarian | `Pencarian` | contextual |
| 33 | Buat Organisasi | `BuatOrganisasi` | onboarding stack |
| — | Sheets (not routes) | see § Bottom sheets | |

## 01–11 · Existing screens

Unchanged. Per-screen references with screenshots, exact tokens and exact copy
are in `../screens/01-…` through `../screens/11-…`; block-level tables are in
section 06 of `../RukunMuda RN Handoff.dc.html`.

Three additions were made to existing screens because the new features demanded
an entry point. Nothing else changed.

| Screen | Addition | Reason |
| --- | --- | --- |
| 03 Home | A `Card` "Voting sedang berjalan" appears above "Kegiatan berikutnya" **only while a vote is open**, showing the question, the closing time, and a `Tag` "Belum memilih" / "Sudah memilih". | Voting is time-bound; it must not need hunting for. Disappears when closed. |
| 03 Home | For a chair or treasurer during setup, a `Card` "Lengkapi profil organisasi — 3 dari 5" with a `Progress` bar, dismissible, gone permanently once complete. | Progressive setup after onboarding (§ 33). |
| 06 Kas | Two rows added to the existing organization list block: "Iuran" (→ 16) and "Sponsor" (→ 23). | Both are cash concerns; they belong in Kas, not behind the avatar. |
| 10 Profil | The existing menu rows "Anggota & peran", "Inventaris", "Dokumen" now route to 12 / 18 / 21 instead of the `BelumTersedia` stub. | Features now exist. |

---

## 12 · Anggota

**Purpose** Answer "Siapa saja anggota organisasi?" for everyone.
**User goal** Find a person and see who holds which role.
**Entry** Profil → "Anggota & peran"; event detail committee row → "Lihat semua".
**Exit** Back to Profil; 13 Anggota — Detail; 15 Undang Anggota.

**Layout** Header (← · "Anggota" · search glyph) → count line "42 anggota · 6 pengurus" in `bodySm` `textMuted` → `FilterChip` row (Semua · Pengurus · Anggota · Baru) → `SectionHeader` "Pengurus" → `MemberItem` rows → 2px `rule` → `SectionHeader` "Anggota" → `MemberItem` rows grouped alphabetically → for chair/secretary, a bottom action bar with primary "Undang anggota".

**Components** `MemberItem`, `FilterChip`, `SectionHeader`, `Button`, `Skeleton`, `EmptyState`, `ErrorState`, `PermissionNote`.

**Primary action** Undang anggota (chair, secretary only).
**Secondary** Search (→ 32 scoped to members), filter chips, tap a member.

**Behavior** Pengurus are listed first, always, so the role question is answered without filtering. Chips filter in place, no navigation. Dues status appears in the trailing slot **only** for treasurer and chair — an ordinary member does not see other members' dues.

**States** Loading: 6 skeleton rows. Empty: only possible pre-onboarding — "Belum ada anggota lain." + "Undang anggota". Error: `ErrorState` over cached list. Permission: an ordinary member sees the list and roles but no invite button and no dues column; no `PermissionNote` needed because nothing is missing that they would expect.

**Edge cases** A member with four roles shows two tags + "+2", full list on detail. Members who left show a "Keluar" outline tag for 30 days, then drop off. Self is pinned to the top of their group with "(Anda)".

---

## 13 · Anggota — Detail

**Purpose** One person's identity, roles, responsibilities, and — for authorized viewers — dues.
**User goal** "Who is this, what do they do, how do I reach them?"
**Entry** 12 Anggota; committee list on 05 Event Detail; a notification about an assignment.
**Exit** Back; 05 Event Detail; 14 Peran & Izin (chair only); WhatsApp (external).

**Layout** Header (← · name) → identity block (`Avatar` 56 · name 22/800 · joined "Anggota sejak Mar 2024" · role `Tag`s) → 2px `rule` → contact row ("+62 812 ···· 4410" + ghost "Chat WhatsApp"; visible to pengurus, and to all members only if the person allowed it) → `SectionHeader` "Tanggung jawab" → `ListItem` rows for each active event responsibility ("Panitia konsumsi · Kerja Bakti Agustus") → `SectionHeader` "Iuran" (treasurer/chair only) → last 3 periods with status `Tag`s + ghost "Lihat semua" → `SectionHeader` "Aktivitas" → last 5 actions in `ListItem` form, meta timestamps → chair only: bottom bar with secondary "Ubah peran".

**Components** `Avatar`, `Tag`, `ListItem`, `SectionHeader`, `Button`, `PermissionNote`, `EmptyState`.

**Primary action** None by default. Chair: "Ubah peran" (secondary — no destructive action is primary).
**Secondary** Chat WhatsApp, Lihat semua iuran, Keluarkan dari organisasi (chair, in a sheet, `Dialog` confirm).

**Behavior** Role changes open a sheet (§ Bottom sheets → *Ubah peran*), never an inline picker. Removing a member requires a `Dialog` naming the person.

**States** Empty responsibilities: "Belum ada tanggung jawab aktif." Permission: a member viewing another member sees identity, roles, responsibilities — the dues section is absent, with no note (they never had reason to expect it). A treasurer viewing dues sees the section; a member viewing **their own** detail is redirected to 17 Iuran Saya for dues.

**Edge cases** Members with no phone number on file: contact row absent. Chair viewing themselves cannot remove their own chair role — the sheet shows "Peran ketua hanya bisa dipindahkan, bukan dihapus." with the transfer option.

---

## 14 · Peran & Izin

**Purpose** Answer "Siapa memegang peran apa?" and let the chair change it.
**User goal** Understand and adjust who can do what — in plain language.
**Entry** Profil → "Anggota & peran" → "Peran"; 13 Anggota — Detail → "Ubah peran".
**Exit** Back; 13 Anggota — Detail.

**Layout** Header (← · "Peran") → intro paragraph 13 `textMuted`: "Peran menentukan siapa yang bisa mengubah data. Semua anggota tetap bisa melihat seluruh laporan kas." → 2px `rule` → `RoleRow` per role in fixed order (Ketua · Bendahara · Sekretaris · Anggota) each with its plain-language description and holder avatars → 2px `rule` → `SectionHeader` "Panitia kegiatan" → explanatory paragraph + `ListItem` per active event linking to its committee.

**Components** `RoleRow`, `SectionHeader`, `ListItem`, `BottomSheet`, `Dialog`, `PermissionNote`.

**Role descriptions (verbatim)**
- Ketua — "Menyetujui dan menerbitkan laporan kas, mengatur anggota dan peran."
- Bendahara — "Mencatat uang masuk dan keluar, mengelola iuran, menyusun laporan."
- Sekretaris — "Mengelola dokumen, notulen, dan pengumuman."
- Anggota — "Melihat seluruh kas, laporan, dan kegiatan. Ikut voting. Mengerjakan tugas yang diberikan."

**Primary action** None. Tapping a `RoleRow` opens the assignment sheet (chair only).
**Behavior** No permission matrix, no checkboxes, no capability toggles. Roles are fixed bundles; only their holders change. Assigning treasurer to a second person is allowed; assigning chair asks to transfer.

**States** Permission: a member sees this screen fully — it is transparency, not administration — with no assignment affordance and a single `PermissionNote`: "Hanya ketua yang bisa mengubah peran."

**Edge cases** A role with no holder shows "Belum ada" + a `PermissionNote` for members, and an "Tetapkan" affordance for the chair. An organization can never have zero chairs — the transfer sheet enforces it.

---

## 15 · Undang Anggota

**Purpose** Get people into the organization.
**User goal** Invite by number or by shareable code with minimum typing.
**Entry** 12 Anggota; 33 Buat Organisasi step 4.
**Exit** Back with a toast; WhatsApp share sheet.

**Layout** Header (← · "Undang anggota") → `Segmented` (Bagikan kode · Nomor WhatsApp) → **Bagikan kode:** code block in 30/800 tabular with a 2px frame, "Berlaku 7 hari", ghost "Salin kode", primary "Bagikan ke WhatsApp" → **Nomor:** `FormSection` "Nomor WhatsApp" with add-another row (max 10 per batch), role `Segmented` (Anggota · Pengurus), primary "Kirim undangan".

**Components** `Segmented`, `FormSection`, `Button`, `SharePreview`, `Toast`, `PermissionNote`.

**Primary action** Bagikan ke WhatsApp / Kirim undangan.
**Behavior** Code sharing goes through `SharePreview` so the sender reads the message first. Invited people appear in 12 Anggota with an outline "Diundang" tag until they join.

**States** Success toast "Undangan terkirim ke 3 nomor." Error per row, inline under the field. Permission: members reaching this route (deep link) get a full-screen `PermissionNote` and a back button.

**Edge cases** A number already in the organization is rejected inline: "Nomor ini sudah jadi anggota." Regenerating the code invalidates the old one — `Dialog` confirm.

---

## 16 · Iuran

**Purpose** The dues ledger for the whole organization. **This app records payments; it does not accept them.**
**User goal** Treasurer: see who has paid this month and record payments. Member: see the collection rate.
**Entry** Kas → "Iuran"; notification "Iuran bulan ini belum dicatat".
**Exit** Back to Kas; 13 Anggota — Detail; record-payment sheet.

**Layout** Header (← · "Iuran") → period `Segmented` or month stepper "‹ Agustus 2026 ›" → summary block: "Terkumpul" `numHero` + "Rp1.400.000 dari Rp2.100.000" + `Progress` bar "28 dari 42 anggota" → inset `surfaceAlt` note, 13/400: "Aplikasi mencatat pembayaran yang sudah diterima. Pembayaran tetap dilakukan langsung ke bendahara." → 2px `rule` → `FilterChip` row (Semua · Belum bayar · Sudah bayar) defaulting to **Belum bayar** for the treasurer → `MemberItem` rows with dues `Tag` trailing → treasurer: bottom bar primary "Catat pembayaran".

**Components** `BalanceDisplay`, `Progress`, `FilterChip`, `MemberItem`, `Tag`, `BottomSheet`, `Button`, `PermissionNote`, `EmptyState`.

**Primary action** Catat pembayaran (treasurer) → sheet.
**Secondary** Change period, filter, "Ingatkan yang belum bayar" (ghost → `SharePreview` with a group reminder message), tap a member.

**Behavior** Recording a payment happens in the sheet: member picker, amount prefilled with the period rate, method `Segmented` (Tunai · Transfer), date, optional note. On save: member flips to "Sudah bayar", a matching income transaction is created in Kas, toast with "Urungkan" for 6s. Confirming a member-submitted payment ("Menunggu konfirmasi") is the same sheet, prefilled.

**States** Loading: skeleton summary + 4 rows. Empty: "Belum ada iuran yang diatur." + treasurer action "Atur iuran bulanan". Permission: a member sees the summary, the note, and the collection list with statuses — but no record button, and one `PermissionNote`: "Hanya bendahara yang bisa mencatat pembayaran iuran." Offline: recording queues with a "Menunggu kirim" tag.

**Edge cases** Partial payments show the amount paid plus an outline "Sebagian" tag and stay in "Belum bayar" filters. A member exempt for a period shows "Dibebaskan" and is excluded from the denominator. Changing the monthly rate never rewrites closed periods.

---

## 17 · Iuran Saya

**Purpose** Answer "Iuran saya sudah bayar belum?"
**User goal** Check status, see history, tell the treasurer they have paid.
**Entry** Home dues card; Profil → "Iuran saya".
**Exit** Back; `SharePreview`.

**Layout** Header (← · "Iuran saya") → status block: period "Agustus 2026", amount `numHero`, status `Tag` large, one-line meaning ("Belum dicatat oleh bendahara.") → inset note: "Bayar langsung ke bendahara. Aplikasi hanya mencatat." → primary "Beri tahu bendahara" (opens `SharePreview` with "Saya sudah bayar iuran Agustus…") → 2px `rule` → `SectionHeader` "Riwayat" → `ListItem` per period with amount, date recorded, and who recorded it.

**Components** `Tag`, `Button`, `ListItem`, `SectionHeader`, `SharePreview`, `EmptyState`.

**Primary action** Beri tahu bendahara — only when status is "Belum bayar". When paid, there is no primary action; the block simply reads "Sudah bayar · dicatat 12 Agu oleh Rini".
**Behavior** Notifying sets status to "Menunggu konfirmasi" and notifies the treasurer. It never marks itself paid — only a treasurer's record does.

**States** Empty history: "Belum ada catatan iuran." No permission variant: every member sees their own.

**Edge cases** Multiple unpaid periods: the block shows the oldest unpaid with "3 periode belum dibayar · Rp150.000" and the history carries the rest.

---

## 18 · Inventaris

**Purpose** Answer "Barang apa yang kita punya dan di mana?"
**User goal** Check what is available before planning an activity; borrow something.
**Entry** Profil → "Inventaris"; 05 Event Detail → equipment row.
**Exit** Back; 19 detail; 20 form.

**Layout** Header (← · "Inventaris" · search glyph) → count line "24 jenis barang · 6 dipinjam" → `FilterChip` row (Semua · Tersedia · Dipinjam · Perlu perbaikan) → `SectionHeader` per category (Sound system · Kursi & meja · Tenda · Olahraga · Lain-lain) → `InventoryItem` rows → pengurus: bottom bar primary "Tambah barang".

**Components** `InventoryItem`, `FilterChip`, `SectionHeader`, `Button`, `Skeleton`, `EmptyState`, `FilterSheet` (via the search screen), `PermissionNote`.

**Primary action** Tambah barang (chair, secretary).
**Secondary** Search, filter, tap an item.

**Behavior** Availability is the first thing the row communicates: "3/8 Tersedia". Categories are a fixed list; adding a category is not a user action in this phase.

**States** Empty: "Belum ada barang." + "Tambah barang" for pengurus; for members "Belum ada barang yang dicatat." with no action. Loading: 5 skeleton rows.

**Edge cases** Items with quantity 1 read "Tersedia" / "Dipinjam" with no fraction. Items marked "Rusak" are excluded from available counts and shown last in their category.

---

## 19 · Inventaris — Detail

**Purpose** One item: how many, what condition, who has it, what happened to it.
**User goal** Borrow it, return it, or find out who is responsible.
**Entry** 18 Inventaris; a notification about an overdue return.
**Exit** Back; borrow / return sheet; 13 Anggota — Detail.

**Layout** Header (← · item name) → block: name 26/800, category, `Tag` availability → two-column ruled block "Jumlah 8 · Tersedia 3" → `SectionHeader` "Kondisi" → condition text + last-checked date + note → `SectionHeader` "Penanggung jawab" → `MemberItem` (single) → `SectionHeader` "Sedang dipinjam" → `ListItem` per active loan (borrower, quantity, due date; overdue gets an accent left border 4px and "Terlambat 3 hari") → `SectionHeader` "Riwayat" → last 10 loan/return entries → bottom bar: primary "Pinjam" (all members) or "Kembalikan" when the viewer holds a loan.

**Components** `Tag`, `MemberItem`, `ListItem`, `SectionHeader`, `Button`, `BottomSheet`, `Dialog`, `EmptyState`, `PermissionNote`.

**Primary action** Pinjam / Kembalikan.
**Secondary** Ubah barang (pengurus), Catat kerusakan (pengurus, sheet).

**Behavior** Borrowing is a sheet: quantity stepper (capped at available), return date, purpose, optional event link. Returning asks for quantity and condition; if condition drops, a note becomes required. Deleting an item with active loans is blocked — `Dialog` explains why.

**States** Empty loans: "Tidak sedang dipinjam." Permission: any member may borrow; only pengurus may edit the item or record damage — `PermissionNote`: "Hanya pengurus yang bisa mengubah data barang."

**Edge cases** Requesting more than available: the stepper stops and shows "Hanya 3 tersedia." Item borrowed for a cancelled event: loan stays open, the event link shows "Dibatalkan".

---

## 20 · Tambah / Ubah Barang

**Purpose** Record a community asset.
**Layout** Header (← · "Tambah barang") → `FormSection` "Barang" (nama, kategori picker, jumlah stepper) → `FormSection` "Kondisi" (condition `Segmented`, catatan) → `FormSection` "Penanggung jawab" (member picker) → optional photo slot 4:3, grayscale → bottom bar primary "Simpan".
**Components** `FormSection`, `Segmented`, `Button`, `Toast`, `Dialog`.
**Behavior** Single screen, not stepped — five fields do not need steps. Editing reuses the same screen with "Simpan perubahan" and a ghost "Hapus barang" at the bottom (`Dialog` confirm).
**States** Disabled save until nama + jumlah are present. Success toast "Barang ditambahkan." Offline: queues.
**Edge cases** Reducing quantity below the number currently on loan is refused inline.

---

## 21 · Dokumen

**Purpose** One place for the organization's paperwork.
**User goal** Find a document and share or download it.
**Entry** Profil → "Dokumen"; 05 Event Detail documentation; 08 Report Detail → "Lihat dokumen".
**Exit** Back; 22 detail; upload sheet.

**Layout** Header (← · "Dokumen" · search glyph) → `FilterChip` row (Semua · Proposal · Notulen · Laporan · Surat · Organisasi) → `SectionHeader` grouping by month → `DocumentItem` rows → pengurus: bottom bar primary "Unggah dokumen".

**Components** `DocumentItem`, `FilterChip`, `SectionHeader`, `Button`, `BottomSheet`, `Skeleton`, `EmptyState`, `ErrorState`.

**Primary action** Unggah dokumen (chair, secretary, treasurer for financial documents).
**Secondary** Search (→ 32 scoped to documents), category chips.

**Behavior** Newest first inside each month group. Published financial reports appear here automatically as read-only entries linking to 08 Report Detail — they are not separate uploads.

**States** Loading 4 skeleton rows. Empty per filter: "Belum ada notulen." Error keeps the cached list.

**Edge cases** A document larger than the mobile preview limit shows "Terlalu besar untuk dilihat di aplikasi" and offers download only.

---

## 22 · Dokumen — Detail

**Purpose** Read, share, or download one document.
**Layout** Header (← · title, truncated · share glyph) → title 22/800 → meta block (kategori `Tag`, "Diunggah Rini · 12 Agu 2026 · 240 KB") → preview area: PDF/image rendered inline at content width, grayscale for photos; other types show the type mark 64×64 and "Tidak bisa dilihat di aplikasi" → `SectionHeader` "Terkait" → linked event or report `ListItem` → bottom bar primary "Bagikan" · secondary "Unduh".
**Components** `Tag`, `ListItem`, `Button`, `SharePreview`, `Dialog`, `PermissionNote`.
**Behavior** "Bagikan" uses the native share sheet with the file; a document link shared to WhatsApp goes through `SharePreview` first when it carries a link rather than the file itself.
**States** Loading: skeleton preview block. Error: "Dokumen gagal dimuat." + retry. Permission: uploader / pengurus see ghost "Hapus dokumen" (`Dialog`); members do not, and no note is shown.
**Edge cases** A deleted document reached via an old notification shows a full-screen `EmptyState` "Dokumen sudah dihapus."

---

## 23 · Sponsor

**Purpose** Track who supports the organization's activities.
**User goal** See committed support before budgeting an event.
**Entry** Kas → "Sponsor"; 05 Event Detail → budget tab.
**Exit** Back; 24 detail; 25 form.

**Layout** Header (← · "Sponsor") → summary line "Total dukungan tahun ini · Rp8.200.000" in `numLg` → `FilterChip` row (Semua · Diajukan · Setuju · Diterima) → `SectionHeader` per event, newest first → `SponsorItem` rows → treasurer/chair: bottom bar primary "Tambah sponsor".

**Components** `SponsorItem`, `FilterChip`, `SectionHeader`, `Button`, `EmptyState`, `PermissionNote`.

**Behavior** Grouping by event is deliberate: sponsors matter in the context of the activity they fund. An unassociated sponsor lands in a "Tanpa kegiatan" group at the bottom. Marking a cash sponsorship "Diterima" offers to create the matching income transaction — one tap, not automatic.

**States** Empty: "Belum ada sponsor." + action for authorized users. Permission: members see the list and amounts (transparency) but not contact details or the add button.

**Edge cases** In-kind (Barang/Jasa) sponsorships have no amount: the trailing slot shows "—" and the detail carries a description instead. Sponsorship totals exclude "Diajukan" and "Batal".

---

## 24 · Sponsor — Detail

**Purpose** Everything about one sponsorship.
**Layout** Header (← · sponsor name) → name 26/800 + type & status `Tag`s → amount `numHero` (or the in-kind description in 17/800) → two-column ruled block "Kegiatan · Kerja Bakti Agustus" / "Dicatat · 4 Agu" → `SectionHeader` "Kontak" (pengurus only: person, phone, ghost "Chat WhatsApp") → `SectionHeader` "Catatan" → free text → `SectionHeader` "Riwayat dukungan" → `ListItem` per past sponsorship from the same sponsor → bottom bar: primary "Tandai diterima" when status is Setuju, otherwise secondary "Ubah".
**Components** `Tag`, `ListItem`, `SectionHeader`, `Button`, `Dialog`, `PermissionNote`.
**Behavior** Status advances one step at a time (Diajukan → Setuju → Diterima); "Batal" is available from any state with a `Dialog` confirm. Marking "Diterima" opens the transaction sheet prefilled.
**States** Permission: members see amount, type, status, event; contact and status actions are absent with `PermissionNote` "Hanya bendahara dan ketua yang mengelola sponsor."
**Edge cases** A sponsorship whose event was cancelled keeps its record and shows the event tag "Dibatalkan".

---

## 25 · Tambah / Ubah Sponsor

**Purpose** Record a sponsorship.
**Layout** Header (← · "Tambah sponsor") → `FormSection` "Sponsor" (nama, jenis `Segmented`: Uang / Barang / Jasa) → conditional: amount field for Uang, description for Barang/Jasa → `FormSection` "Kegiatan" (event picker, optional) → `FormSection` "Kontak" (nama, nomor WhatsApp — optional) → `FormSection` "Catatan" → bottom bar primary "Simpan".
**Components** `FormSection`, `Segmented`, `Button`, `Toast`.
**Behavior** Progressive disclosure by type: choosing Barang hides the amount field entirely rather than disabling it. New sponsorships start at "Diajukan".
**Edge cases** Duplicate sponsor name prompts "Sponsor ini sudah ada — tambahkan sebagai dukungan baru?" with a link to the existing record.

---

## 26 · Voting

**Purpose** List votes: what is open, what has closed.
**User goal** Vote before it closes; look up an old result.
**Entry** Home voting card; Notifikasi; Profil → "Voting".
**Exit** Back; 27 detail; 28 result.

**Layout** Header (← · "Voting") → `SectionHeader` "Sedang berjalan" → `Card` per open vote: question 17/800, "Tutup 20 Agu 20:00" (or "Tutup dalam 4 jam" under 24h), participation "31 dari 42 sudah memilih", status `Tag` → 2px `rule` → `SectionHeader` "Sudah ditutup" → `ListItem` per closed vote with the winning option as subtitle and a "Ditutup" tag.

**Components** `Card`, `ListItem`, `Tag`, `SectionHeader`, `EmptyState`, `Skeleton`.

**Primary action** None on the list. Tapping an open vote goes to 27; a closed vote to 28.
**Behavior** Open votes always sort first, soonest-closing at the top. There is no create-vote flow in this phase — see § Open decisions in `mobile-ux.md`.

**States** Empty: "Tidak ada voting yang berjalan." with closed history below if any; fully empty: "Belum ada voting."

**Edge cases** A vote closing while the list is open updates in place and moves to the closed group on next refresh, not mid-scroll.

---

## 27 · Voting — Detail

**Purpose** Cast a vote, with no ambiguity about what happens.
**User goal** Choose safely and know whether the choice is visible and changeable.
**Entry** 26 Voting; Home card; notification "Voting baru tersedia."
**Exit** Back with a toast; 28 Hasil after closing.

**Layout** Header (← · "Voting") → question 26/800 → description 15/400 → **the disclosure block**: inset `surfaceAlt`, 2px left `rule`, three lines in 13/400 — anonymity ("Pilihan Anda tidak akan terlihat siapa pun." / "Pilihan Anda terlihat oleh pengurus."), editability ("Bisa diubah sampai voting ditutup." / "Tidak bisa diubah setelah dikirim."), closing time ("Ditutup 20 Agustus 2026, 20:00") → 2px `rule` → `SectionHeader` "Pilihan" → `VoteOption` rows → bottom bar primary "Kirim pilihan", disabled until an option is selected.

**Components** `VoteOption`, `Button`, `Dialog`, `Toast`, `Tag`, `PermissionNote`, `EmptyState`.

**Primary action** Kirim pilihan.
**Secondary** Ubah pilihan (when editable and already voted), Lihat hasil (when results are live or the vote is closed).

**Behavior — accident prevention, in order**
1. Selecting an option changes nothing but the selector; nothing is submitted on tap.
2. "Kirim pilihan" opens a `Dialog`: title "Kirim pilihan Anda?", body the chosen option's label plus the editability sentence, confirm "Kirim", cancel "Batal".
3. On confirm: `Toast` "Pilihan terkirim." — with "Urungkan" for 6s **only** when the vote is editable; when it is not editable, no undo is offered and the dialog said so.
4. After submitting, options lock into a read-only state with the chosen row filled and a "Pilihan Anda" tag; the bottom bar becomes secondary "Ubah pilihan" or disappears.

**States** Loading: skeleton question + 3 option rows. Closed while open on screen: the bar is replaced by "Voting sudah ditutup." and a link to 28. Error on submit: the choice stays selected, `ErrorState` inline above the bar, retry. Offline: submission queues with "Menunggu kirim"; the vote is not counted until sent, and the UI says so rather than claiming success. Permission: a non-member viewer or a member excluded from the eligible group sees the question and a `PermissionNote`: "Voting ini hanya untuk pengurus."

**Edge cases** Multi-select votes state "Pilih maksimal 2" above the options and the dialog lists all chosen. A vote that closes between selection and confirmation fails the submit with "Voting sudah ditutup sebelum pilihan terkirim." and routes to 28.

---

## 28 · Voting — Hasil

**Purpose** Show the outcome credibly.
**Layout** Header (← · "Hasil voting") → question 26/800 → status line "Ditutup 20 Agu 20:00 · 38 dari 42 memilih" → winning option block: label 22/800 + "18 suara · 47%" → 2px `rule` → `VoteOption` rows in `result` mode, sorted by count, ink bars → anonymity restatement in 13 `textFaint` ("Voting ini anonim. Daftar pemilih tidak disimpan." / "Pengurus dapat melihat siapa memilih apa.") → for identifiable votes and pengurus only: `SectionHeader` "Siapa memilih apa" → `MemberItem` rows grouped by option → bottom bar ghost "Bagikan hasil".
**Components** `VoteOption`, `MemberItem`, `SectionHeader`, `Button`, `SharePreview`, `Tag`.
**Behavior** Ties show both options at the top with "Seri" instead of a single winner. Sharing goes through `SharePreview`.
**States** Permission: for an anonymous vote, the voter breakdown section does not exist for anyone, including the chair — and the screen says so.
**Edge cases** Fewer than 3 voters on an anonymous vote: percentages are suppressed and replaced by counts only, with "Terlalu sedikit suara untuk ditampilkan sebagai persen." to protect anonymity.

---

## 29 · Buat Kegiatan

**Purpose** Create an activity without it feeling like paperwork.
**User goal** Get an event on the calendar in under a minute; add the rest later.
**Entry** 04 Kegiatan → "+ Buat kegiatan baru"; Home quick action for pengurus.
**Exit** 05 Event Detail on publish; 04 Kegiatan on draft save.

**Three steps, not one long form.** `StepIndicator` at the top of every step; back arrow returns a step, not out of the flow.

**Step 1 — Kegiatan** (`FormSection` "Kegiatan"): judul (required, autofocus), deskripsi (optional, 3 lines), kategori `Segmented` (Kerja bakti · Olahraga · Sosial · Lain-lain). Bottom bar primary "Lanjut", ghost "Simpan draf".

**Step 2 — Waktu & tempat**: tanggal (native picker), jam mulai & selesai, lokasi text field, optional "Butuh barang inventaris?" row → opens the inventory picker sheet. Primary "Lanjut".

**Step 3 — Panitia & anggaran**: panitia member multi-picker (avatars appear as chosen), each with an optional job label; anggaran amount field with helper "Saldo kas saat ini Rp8.450.000"; optional sponsor link. Primary "Terbitkan kegiatan", ghost "Simpan sebagai draf".

**After creation, not during:** tasks, event image, attendance, documentation. Event detail shows an "Lengkapi kegiatan" card listing what is still missing, so the creation flow never has to ask for it.

**Components** `StepIndicator`, `FormSection`, `Segmented`, `Button`, `BottomSheet`, `Avatar`, `Toast`, `Dialog`, `PermissionNote`.

**Primary action** Step 1–2: Lanjut. Step 3: Terbitkan kegiatan.
**Secondary** Simpan draf on every step; back.

**Behavior** Only **judul** and **tanggal** are required to publish. Everything else can be empty and filled from the event detail later. Drafts autosave on every step change and on backgrounding; leaving via the header back on step 1 with content offers a `Dialog`: "Simpan sebagai draf?" / "Buang". Publishing notifies committee members and posts to the activity list. A published event can still be edited; changing date or place notifies the committee.

**States** Loading: none (local form). Disabled: "Lanjut" until judul is present; "Terbitkan" until tanggal is present, with the reason under the button in 13 `textFaint` — never a silent disabled button. Error on publish: the form stays filled, `ErrorState` above the bar. Offline: draft saves locally, publish queues with "Akan diterbitkan saat online". Permission: only pengurus and the chair reach this; members entering by deep link get a full-screen `PermissionNote`: "Hanya pengurus yang bisa membuat kegiatan."

**Edge cases** A date in the past prompts "Tanggal sudah lewat — tetap buat?" and, if confirmed, the event is created directly as "Selesai". Event lifecycle after publication: **Terbit** → **Berjalan** (auto, on the day) → **Selesai** (auto, day after, or manual) → **Dibatalkan** (manual, `Dialog`, notifies committee, keeps the record and its budget).

---

## 30 · Susun Laporan

**Purpose** Let the treasurer turn a month of transactions into a report worth publishing.
**User goal** Check the month, add a note, send it for approval.
**Entry** Kas → Transparansi → "Susun laporan"; notification "Laporan Agustus siap disusun".
**Exit** Back to Transparansi; 31 Periksa Laporan for the chair.

**Layout** Header (← · "Susun laporan") → period line "Agustus 2026" 22/800 + status `Tag` "Draf" → `ReportSummary` (opening · masuk · keluar · closing, computed, read-only) → inset note "Angka diambil langsung dari transaksi. Untuk mengubah angka, ubah transaksinya." → `SectionHeader` "Transaksi yang perlu dilengkapi" → `TransactionItem` rows missing category or evidence, with a count and a ghost "Lengkapi" per row → `SectionHeader` "Catatan bendahara" → text area, 4 lines → bottom bar primary "Kirim untuk diperiksa", ghost "Simpan draf".

**Components** `ReportSummary`, `TransactionItem`, `SectionHeader`, `FormSection`, `Tag`, `Button`, `Dialog`, `Toast`, `PermissionNote`.

**Primary action** Kirim untuk diperiksa → `Dialog` "Kirim laporan Agustus ke ketua?" → status becomes **Diperiksa**, chair notified.
**Behavior** Numbers are never editable here — the report is a view of transactions, which is what makes it trustworthy. Incomplete transactions do not block submission but are counted in the dialog body: "2 transaksi belum punya bukti."

**States** Empty month (no transactions): "Belum ada transaksi bulan ini." and submission is unavailable with the reason stated. Permission: chair and members reaching this route see `PermissionNote` "Hanya bendahara yang menyusun laporan kas."

---

## 31 · Periksa Laporan

**Purpose** The chair's approval step.
**User goal** Read the month, approve it, or send it back with a reason.
**Entry** Notification "Laporan Agustus siap disetujui"; Kas → Transparansi.
**Exit** 08 Report Detail after approval; back with a toast on rejection.

**Layout** Header (← · "Periksa laporan") → period + status `Tag` "Diperiksa" → "Disusun oleh Rini · 1 Sep" → `ReportSummary` → `SectionHeader` "Catatan bendahara" → note text → `SectionHeader` "Perlu diperhatikan" → flagged rows (missing evidence, unusually large expenses) as `TransactionItem` with a 4px accent left border → ghost "Lihat semua transaksi" (→ 08) → bottom bar: primary "Setujui", secondary "Minta perbaikan".

**Components** `ReportSummary`, `TransactionItem`, `Tag`, `Button`, `BottomSheet`, `Dialog`, `Toast`, `PermissionNote`.

**Behavior** "Setujui" → `Dialog` → status **Disetujui**, treasurer notified, and the chair is offered "Terbitkan sekarang" in the toast. "Minta perbaikan" opens a sheet requiring a reason (free text, required) → status returns to **Draf** with the reason shown at the top of 30. Publishing (from Disetujui) is a separate deliberate act on 07 Transparansi, ending in `SharePreview`.

**States** Permission: treasurer viewing sees the same screen read-only with `PermissionNote` "Menunggu persetujuan ketua." Members do not see this screen; they see the published report only.

**Edge cases** A transaction changed after approval flips the report to **Draf** with "Ada transaksi yang berubah setelah disetujui" — approval is never silently stale. If the chair is also the treasurer (small organizations), the approval step is skipped and 30's primary action becomes "Setujui & terbitkan", with the report recording "Disusun dan disetujui oleh Rini".

---

## 32 · Pencarian

**Purpose** Find one specific thing in a long list.
**User goal** Type a few letters, get the item, leave.

**Search exists in exactly five places, and nowhere else:** Anggota (12), Kegiatan (04), Transaksi (06), Dokumen (21), Inventaris (18). Each opens this screen **scoped to that domain** — the scope is fixed and shown in the field's placeholder. There is no global search: nothing in the product requires searching across domains at once, and a global entry point would demand a result-type taxonomy that would cost more than it returns. Sponsor, Voting, and Notifikasi lists are short and time-ordered; filters serve them better. Rationale is recorded in `mobile-ux.md` § Search and filtering.

**Entry** The search glyph in a list header.
**Exit** Back to the list; the chosen item's detail.

**Layout** Header: back arrow + `SearchField` filling the row (autofocus, placeholder "Cari anggota") → when empty: `SectionHeader` "Pencarian terakhir" → up to 5 `ListItem` rows with a ghost "Hapus" in the header action → when typing: results as the domain's own row component (`MemberItem`, `EventItem`, `TransactionItem`, `DocumentItem`, `InventoryItem`) with the matched substring at weight 800 → result count line "7 hasil".

**Components** `SearchField` (per `mobile-components.md`), the domain row components, `SectionHeader`, `FilterChip`, `Skeleton`, `EmptyState`, `ErrorState`.

**Behavior** Debounce 250ms, minimum 2 characters. Local-first: cached data is searched instantly, server results merge in. Filters from the parent list stay applied and appear as `FilterChip`s below the field, removable. Recent searches are per domain, capped at 5, stored locally.

**States** Loading: 3 skeleton rows, never a spinner over the field. Empty query: recent searches. No results: `EmptyState` "Tidak ada hasil untuk "kursi lipat"." + ghost "Hapus pencarian", plus a filter hint when filters are narrowing results: "2 filter aktif — coba hapus filter." Error: `ErrorState` with retry, keeping local matches visible.

**Edge cases** Search never returns items the viewer cannot see (dues of others, sponsor contacts) — scoping happens before the query, not by hiding results after.

---

## 33 · Buat Organisasi

**Purpose** Get a new organization usable in under two minutes.
**User goal** Create the group, get people in, start using it.
**Entry** 01 Splash → "Buat organisasi baru"; 10 Profil → org switcher → "Buat organisasi baru".
**Exit** 03 Home with the setup card showing.

**Four steps only. Everything else is deferred to progressive setup.**

| Step | Fields | Required |
| --- | --- | --- |
| 1 · Organisasi | Nama organisasi, jenis `Segmented` (Karang Taruna · Pemuda Kampung · RT/RW · Lain-lain) | Nama |
| 2 · Lokasi | Kelurahan/desa, kota — one field each, no map | Kota |
| 3 · Undang anggota | Shareable code block + optional numbers, reusing 15 | none |
| 4 · Selesai | Confirmation: name, type, location, invite count, primary "Mulai pakai RukunMuda" | — |

`StepIndicator` throughout; every step except 1 has a ghost "Lewati" where its fields are optional.

**Deferred to the Home setup card, never asked here:** logo, monthly dues rate, treasurer and secretary assignment, inventory, documents. The card reads "Lengkapi profil organisasi — 2 dari 5" and links to each remaining item; it disappears when complete or when dismissed twice.

**Components** `StepIndicator`, `FormSection`, `Segmented`, `Button`, `Card`, `Progress`, `Toast`, `EmptyState`.

**Behavior** The creator becomes **Ketua** automatically, with no role screen during onboarding. First launch after finishing shows Home with real empty states — every list says what it is for and offers its first action, so the app is legible with zero data.

**States** Error on create: form retained, retry. Offline: creation is blocked with "Butuh koneksi untuk membuat organisasi." — the only place in the app where an action requires connectivity.

**Edge cases** A name already used in the same city warns but does not block. Leaving mid-flow keeps a local draft and resumes at the last step.

---

## Bottom sheets

Sheets are components, not routes. Each has a title, a single primary action, and closes on backdrop tap.

| Sheet | Opened from | Contents |
| --- | --- | --- |
| Catat transaksi | 06 Kas | Direction `Segmented`, amount, category, date, note, evidence photo |
| Catat pembayaran iuran | 16 Iuran | Member picker, amount (prefilled), method, date, note |
| Ubah peran | 13, 14 | Role list with current holder, single-select, `Dialog` on chair transfer |
| Pinjam barang | 19 | Quantity stepper, return date, purpose, optional event |
| Kembalikan barang | 19 | Quantity, condition `Segmented`, required note if condition dropped |
| Unggah dokumen | 21 | File picker, kategori, judul, optional event link |
| Tambah tugas | 05 Event Detail | Task title, assignee picker, due date, priority |
| Filter | 04, 06, 16, 18, 21, 23 | `FilterSheet` with that domain's facets |
| Bagikan | 07, 08, 15, 17, 21, 28, event detail | `SharePreview` with the literal outgoing message |
| Minta perbaikan | 31 | Required reason text, primary "Kirim" |

**Filter facets per domain** — the complete set; no domain gets more:

| Domain | Facets |
| --- | --- |
| Kegiatan | Status (Draf/Terbit/Berjalan/Selesai/Dibatalkan) · Periode (bulan ini / 3 bulan / tahun ini / semua) · Kategori · Panitia (saya / semua) |
| Transaksi | Arah (Masuk/Keluar) · Periode · Kategori · Jumlah (di atas Rp500.000) |
| Iuran | Status · Periode |
| Dokumen | Kategori · Periode · Diunggah oleh |
| Inventaris | Ketersediaan · Kondisi · Kategori |
| Sponsor | Status · Jenis · Kegiatan |

## Screen count

11 existing + 22 new screens + 10 sheets. Nothing in the app is more than three
levels from a tab.
