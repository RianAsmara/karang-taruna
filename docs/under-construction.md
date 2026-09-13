# Under-construction surfaces

Every place in the running app that tells the user something "sedang
disiapkan", plus what each one actually needs before it can be built.

**The rule this file exists to enforce**: research before implementing.
A stub is not automatically a to-do — some are deliberate (the work is
blocked on a decision, or the feature is intentionally out of scope for
a surface). Read the entry before starting, and update it when a stub
ships or a new one is added.

Audited 13 Sep 2026. Web (Inertia) has **no** under-construction pages —
every route renders real data. Everything below is mobile.

## Reachable stubs

| Where | Surface | Copy shown | Status |
| --- | --- | --- | --- |
| Profil → "Pengaturan & notifikasi" | `ProfilScreen.tsx:151` → `belum-tersedia/pengaturan` | "Pengaturan akun dan notifikasi sedang disiapkan." | **Not scoped.** No spec exists. |
| Kas → "Catat transaksi" | `KasScreen.tsx:168` (`InfoSheet`) | "Formulir pencatatan transaksi sedang disiapkan." | **Blocked on a product decision.** |
| Anggota Detail → "Iuran" | `AnggotaDetailScreen.tsx:274` (`InfoSheet`) | "Halaman iuran lengkap sedang disiapkan." | **Partially redundant.** |

### Pengaturan & notifikasi

The only remaining `belum-tersedia` route. There is no screen spec for
it in `docs/design/screens/` — it is not one of the 11 specified
screens, so building it needs a design decision first, not just code.
What it would plausibly hold (notification preferences per channel,
account settings) partly duplicates web's `settings/` pages, which do
exist. **Before building**: decide whether mobile gets its own settings
surface at all, or whether this row should link out to the web app.

### Catat transaksi (Kas)

The backend is ready — `POST /api/v1/finance/transactions` exists, with
`FinancialTransactionPolicy`, approval workflow, and evidence upload.
The gap is deliberate: `mobile-ux.md` scopes mobile's Kas as a
*transparency* surface (read the balance, read the history), with
recording left to web where the treasurer has a keyboard and the
receipt files. **Before building**: confirm that scoping still holds.
If it does, this stub is correct behavior and should stay.

### Undang anggota — RESOLVED 13 Sep 2026

Built. The invite architecture was settled as a shareable, expiring,
revocable join link (ADR-0021): the chair generates one, shares it to the
WhatsApp group, and opening it joins immediately as `ANGGOTA`. Web has
generate/copy/revoke on the member list; mobile's stub sheet is now a real
`InviteSheet` that generates and opens the OS share sheet. Accepting is a
web route by design — the invitee may have neither an account nor the app.

**Still open**: the full 4-step Buat Organisasi flow
(`mobile-screens.md` §33) needs schema this app does not have (type,
location) — the invite step it also waited on is no longer a blocker.

### Iuran (Anggota Detail)

Least blocked of the four. `iuran-saya.tsx` already exists and shows the
viewer's own dues; this stub is about viewing *another* member's dues
from their detail screen. `GET /api/v1/finance/dues` already returns
per-membership rows, and web's dues page renders exactly this.
**Before building**: decide the privacy question — whether an ordinary
member may see another member's payment history, or only pengurus can.
That answer is a policy change, not a UI one.

## Not stubs, despite similar copy

- `AnggotaDetailScreen.tsx:269` — "Tidak bisa membuka WhatsApp di
  perangkat ini." Real error handling for a missing WhatsApp install,
  not unfinished work.
- `AnggotaDetailScreen.tsx:253` — "Peran ketua hanya bisa dipindahkan."
  An explanation of a deliberate rule (chair transfer), not a stub.
- `BelumTersedia.tsx` / `InfoSheet.tsx` — the two components that render
  these messages. Keep them; they are the honest way to show an
  unfinished destination.

## Recently retired

`belumTersediaCopy` (`mobile/src/data/mock.ts`) held entries for
`anggota-peran`, `iuran-tarif`, `inventaris`, `dokumen`, `sponsor`,
`iuran-saya`, `kegiatan-baru` and `organisasi-baru` long after those
screens shipped. Removed 13 Sep 2026. **If you add a stub, add it here
too; when it ships, delete both.**
