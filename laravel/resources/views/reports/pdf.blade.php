<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $report->title }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; color: #1a1a1a; }
        h1 { font-size: 18px; margin-bottom: 2px; }
        .muted { color: #666; }
        .meta { margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        td, th { padding: 4px 0; text-align: left; }
        .amount { text-align: right; font-variant-numeric: tabular-nums; }
        .summary td { border-top: 1px solid #ddd; padding-top: 8px; }
        .section-title { font-size: 13px; font-weight: bold; margin-top: 16px; margin-bottom: 6px; }
        .footer { margin-top: 24px; font-size: 10px; color: #999; }
    </style>
</head>
<body>
    <h1>{{ $report->title }}</h1>
    <p class="muted">{{ $report->organization->name }} &middot; {{ $report->report_type->label() }}</p>

    <div class="meta">
        <p>Periode: {{ $report->period_start->translatedFormat('d F Y') }} &ndash; {{ $report->period_end->translatedFormat('d F Y') }}</p>
        <p>Status: {{ $report->status->label() }}</p>
        @if ($report->published_at)
            <p>Dipublikasikan: {{ $report->published_at->translatedFormat('d F Y H:i') }} oleh {{ $report->publisher?->name }}</p>
        @endif
    </div>

    <table class="summary">
        <tr>
            <td>Saldo awal</td>
            <td class="amount">{{ \App\Support\Money::formatRupiah($report->opening_balance) }}</td>
        </tr>
        <tr>
            <td>Pemasukan</td>
            <td class="amount">+{{ \App\Support\Money::formatRupiah($report->total_income) }}</td>
        </tr>
        <tr>
            <td>Pengeluaran</td>
            <td class="amount">-{{ \App\Support\Money::formatRupiah($report->total_expense) }}</td>
        </tr>
        <tr>
            <td><strong>Saldo akhir</strong></td>
            <td class="amount"><strong>{{ \App\Support\Money::formatRupiah($report->closing_balance) }}</strong></td>
        </tr>
    </table>

    @if (count($breakdown['income']) > 0)
        <p class="section-title">Rincian Pemasukan</p>
        <table>
            @foreach ($breakdown['income'] as $item)
                <tr>
                    <td>{{ $item['label'] }}</td>
                    <td class="amount">{{ \App\Support\Money::formatRupiah($item['amount']) }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if (count($breakdown['expense']) > 0)
        <p class="section-title">Rincian Pengeluaran</p>
        <table>
            @foreach ($breakdown['expense'] as $item)
                <tr>
                    <td>{{ $item['label'] }}</td>
                    <td class="amount">{{ \App\Support\Money::formatRupiah($item['amount']) }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <p class="footer">
        Laporan ini dibuat otomatis oleh RukunMuda.
        @if ($revisionCount > 0)
            Revisi ke-{{ $revisionCount }}.
        @endif
    </p>
</body>
</html>
