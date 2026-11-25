<?php

namespace App\Controllers;

use App\Libraries\TransparansiAnggotaController;
use App\Models\M_anggotatransparansi;
use App\Models\M_rapat;

/**
 * Laporan Transparansi 13 — Pelaksanaan Rapat dalam 1 (satu) tahun (E0701).
 * Seluruh logika ada di TransparansiAnggotaController; komentar memakai widget bersama templates/komentar.
 */
class Rapat extends TransparansiAnggotaController
{
    protected function newModel(): M_anggotatransparansi
    {
        return new M_rapat();
    }

    protected function config(): array
    {
        return [
            'page' => 'Rapat',
            'judul' => 'Pelaksanaan Rapat dalam 1 (satu) tahun',
            'entity' => 'Rapat',
            'listTitle' => 'Daftar Rapat',
            'icon' => 'fa-calendar-check',
            'tindak' => ['Penjelasan Lebih Lanjut Pelaksanaan Rapat', null],
            'exportMethod' => 'exporttxtrapat',
        ];
    }

    /** Nama method dipertahankan: dipanggil ZIP Showtransparansi / Showtahunan. */
    public function exporttxtrapat()
    {
        return $this->exportTxt();
    }
}
