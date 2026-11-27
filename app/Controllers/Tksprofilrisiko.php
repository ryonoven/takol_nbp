<?php

namespace App\Controllers;

use App\Libraries\TksFaktorController;
use App\Models\M_showprofilresiko;
use App\Models\M_tksfaktor;
use App\Models\M_tksprofilrisiko;

/** TKS — Faktor 1: Profil Risiko (bobot 25%). Sumber: Laporan Profil Risiko (showprofilresiko). */
class Tksprofilrisiko extends TksFaktorController
{
    public const PAGE = 'Tksprofilrisiko';
    public const NO = 1;
    public const JUDUL = 'Penilaian Faktor Profil Risiko';
    public const ICON = 'fa-shield-alt';
    public const SOURCE_NAME = 'Laporan Profil Risiko';
    public const SOURCE_URL = 'Showprofilresiko';

    protected function newModel(): M_tksfaktor
    {
        return new M_tksprofilrisiko();
    }

    protected function sourceRows(array $periodeIds): array
    {
        $out = [];
        foreach ((new M_showprofilresiko())->select('periode_id, ntotalrisk, nbelumtotalrisk, kesimpulan')
            ->where('kodebpr', $this->userKodebpr)->whereIn('periode_id', $periodeIds)->findAll() as $r) {
            $out[(int) $r['periode_id']] = ['pk' => $r['ntotalrisk'], 'pk_before' => $r['nbelumtotalrisk'], 'kesimpulan' => $r['kesimpulan']];
        }
        return $out;
    }
}
