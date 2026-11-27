<?php

namespace App\Controllers;

use App\Libraries\TksFaktorController;
use App\Models\M_showfaktor;
use App\Models\M_tksfaktor;
use App\Models\M_tkstatakelola;

/**
 * TKS — Faktor 2: Tata Kelola (bobot 30%).
 * Sumber: Laporan Penilaian Sendiri Tata Kelola (showfaktor.nilaikomposit + kesimpulan);
 * peringkat periode sebelumnya diambil dari baris showfaktor periode sebelumnya.
 */
class Tkstatakelola extends TksFaktorController
{
    public const PAGE = 'Tkstatakelola';
    public const NO = 2;
    public const JUDUL = 'Penilaian Faktor Tata Kelola';
    public const ICON = 'fa-building';
    public const SOURCE_NAME = 'Laporan Tata Kelola';
    public const SOURCE_URL = 'ShowFaktor';

    protected function newModel(): M_tksfaktor
    {
        return new M_tkstatakelola();
    }

    protected function sourceRows(array $periodeIds): array
    {
        $out = [];
        foreach ((new M_showfaktor())->select('periode_id, nilaikomposit, kesimpulan')
            ->where('kodebpr', $this->userKodebpr)->whereIn('periode_id', $periodeIds)->findAll() as $r) {
            $out[(int) $r['periode_id']] = ['pk' => $r['nilaikomposit'], 'pk_before' => 0, 'kesimpulan' => $r['kesimpulan']];
        }
        return $out;
    }
}
