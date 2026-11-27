<?php

namespace App\Controllers;

use App\Libraries\TksIndikatorController;
use App\Models\M_mistks;
use App\Models\M_tksindikator;
use App\Models\M_tksrentabilitas;

/** TKS — Faktor 3: Rentabilitas (bobot 15%): ROA, BOPO, NIM. */
class Tksrentabilitas extends TksIndikatorController
{
    public const PAGE = 'Tksrentabilitas';
    public const NO = 3;
    public const JUDUL = 'Penilaian Faktor Rentabilitas';

    protected function newModel(): M_tksindikator
    {
        return new M_tksrentabilitas();
    }

    protected function misData(string $kodebpr, string $periodeCurrent, string $periodePrev): array
    {
        return (new M_mistks())->getMisTksDataCached($kodebpr, $periodeCurrent, $periodePrev);
    }
}
