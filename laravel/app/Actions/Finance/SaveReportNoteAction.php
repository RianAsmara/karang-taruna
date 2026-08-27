<?php

namespace App\Actions\Finance;

use App\Models\FinancialReport;

class SaveReportNoteAction
{
    /**
     * Persist the treasurer's own note on a DRAFT report without
     * changing its status — the ghost "Simpan draf" action on screen 30,
     * distinct from "Kirim untuk diperiksa" which also submits it.
     */
    public function handle(FinancialReport $report, ?string $note): FinancialReport
    {
        $report->update(['treasurer_note' => $note]);

        return $report;
    }
}
