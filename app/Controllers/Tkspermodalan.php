<?php

namespace App\Controllers;

use App\Libraries\TksIndikatorController;
use App\Models\M_mistks;
use App\Models\M_tksindikator;
use App\Models\M_tkspermodalan;

/** TKS — Faktor 4: Permodalan (bobot 30%): KPMM, MIAPB. */
class Tkspermodalan extends TksIndikatorController
{
    public const PAGE = 'Tkspermodalan';
    public const NO = 4;
    public const JUDUL = 'Penilaian Faktor Permodalan';

    protected function newModel(): M_tksindikator
    {
        return new M_tkspermodalan();
    }

    protected function misData(string $kodebpr, string $periodeCurrent, string $periodePrev): array
    {
        return (new M_mistks())->getPermodalanDataCached($kodebpr, $periodeCurrent, $periodePrev);
    }
}
