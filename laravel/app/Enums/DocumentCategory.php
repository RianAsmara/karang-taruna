<?php

namespace App\Enums;

enum DocumentCategory: string
{
    case Proposal = 'PROPOSAL';
    case Notulen = 'NOTULEN';
    case Laporan = 'LAPORAN';
    case Surat = 'SURAT';
    case Organisasi = 'ORGANISASI';

    public function label(): string
    {
        return match ($this) {
            self::Proposal => 'Proposal',
            self::Notulen => 'Notulen',
            self::Laporan => 'Laporan',
            self::Surat => 'Surat',
            self::Organisasi => 'Dokumen organisasi',
        };
    }
}
