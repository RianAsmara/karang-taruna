# Transparency

Transparency is a first-class feature, not an afterthought bolted onto
finance. The product goal: a member should never need to ask "Kas
sekarang berapa?" — the answer is always available in the app.

## Visibility model

`FinancialReport.visibility` (default `MEMBERS`):

- **PRIVATE** — authorized administrators only (e.g. draft reports,
  reports for orgs that haven't opted into member-level visibility).
- **MEMBERS** — any user with an active `OrganizationMembership` in the
  org can view. Requires authentication.
- **PUBLIC** — accessible without login, when explicitly enabled per
  report/organization. Never the default.

Enforced via Policy (`viewFinancialReport`), checked identically on the
web report page and the `/api/v1/finance/reports/{report}` endpoint.

## Transparency dashboard

A dedicated **Transparansi** nav item (see `roadmap.md` Phase 4) showing,
per organization:
- current balance (derived from approved transactions, never hand-typed)
- total income / total expense for the current period
- net cash flow / surplus
- recent transactions
- links to published monthly and event reports

Only `APPROVED` transactions (see `security.md` § Financial safety) ever
contribute to these numbers.

## Financial reports — publish/revision lifecycle

1. Report is generated from actual transaction data (opening balance +
   approved income − approved expense = closing balance). Administrators
   never manually type the final balance.
2. `DRAFT` → reviewable/editable internally.
3. `PUBLISHED` → becomes an official organizational record. Immutable
   from this point.
4. If a correction is needed after publication, a new
   `FinancialReportRevision` (with `revision_number`, full `snapshot`,
   `created_by`) is created. The published report's historical state is
   retained — never silently overwritten.
5. `ARCHIVED` — retired but retrievable.

## Shareable report URLs

Published reports live at a stable `/reports/{report}` URL.
- `MEMBERS` visibility: requires authenticated org membership.
- `PUBLIC` visibility: accessible without login.

The page shows: organization, reporting period, opening balance, income,
expense, closing balance, summary, publication timestamp, revision
number, status.

## QR codes

Published reports can generate a QR code pointing at the report's
official URL — intended for physical use (community notice boards:
"Scan untuk melihat laporan kas"). The QR always resolves to the Laravel
app; it is a link to the source of truth, never a substitute for it.

## WhatsApp sharing

WhatsApp is a **distribution channel**, not a source of truth. MVP scope
is WhatsApp click-to-chat/share with a pre-filled message summarizing the
report and linking back to `/reports/{report}`:

```
📊 LAPORAN KAS PEMUDA
Periode: Agustus 2026

Saldo awal: Rp7.950.000
Pemasukan: +Rp2.350.000
Pengeluaran: -Rp1.850.000
Saldo akhir: Rp8.450.000

Detail: https://app.rukunmuda.id/reports/abc123

Laporan ini dibuat otomatis oleh RukunMuda.
```

The user manually picks the WhatsApp group/contact to send to. No
automated WhatsApp group delivery, no unofficial WhatsApp automation —
explicitly out of scope now and later (WhatsApp Business API integration
is a possible future notification channel, tracked separately in
`roadmap.md`, and even then remains a distribution channel, not a source
of truth).

## Share log

`ReportShareLog` records `report_id`, `channel`
(`WHATSAPP`/`WEB`/`PDF`), `shared_by`, `shared_at`. This is distribution
metadata for understanding reach — explicitly not treated as a financial
record and never gates report correctness.

## Public organization transparency page

Optional, per-organization: `/org/{organization}/transparency` —
current balance, published monthly/event reports, income/expense
summaries. Never exposes member personal data, private donor
information, internal audit notes, or other sensitive metadata, even
though the page itself requires no login.

## Source-of-truth rule

Laravel's database is the only source of truth. Web (React/Inertia) and
mobile (React Native) are clients. WhatsApp is a distribution channel.
PDF is a distribution artifact. A QR code is a link to the source of
truth. None of these are ever treated as authoritative.
