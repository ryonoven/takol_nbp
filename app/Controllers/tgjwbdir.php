<?php

namespace App\Controllers;

use App\Libraries\TransparansiAnggotaController;
use App\Models\M_anggotatransparansi;
use App\Models\M_tgjwbdir;

/**
 * Laporan Transparansi 2 — Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi (E0201).
 * Seluruh logika ada di TransparansiAnggotaController; komentar memakai widget bersama templates/komentar.
 */
class Tgjwbdir extends TransparansiAnggotaController
{
    protected function newModel(): M_anggotatransparansi
    {
        return new M_tgjwbdir();
    }

    protected function config(): array
    {
        return [
            'page' => 'Tgjwbdir',
            'judul' => 'Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi',
            'entity' => 'Direksi',
            'listTitle' => 'Anggota Direksi',
            'icon' => 'fa-user-tie',
            'tindak' => ['Tindak Lanjut Rekomendasi Direksi BPR', 'Tindak Lanjut Rekomendasi Dewan Komisaris'],
            'exportMethod' => 'exporttxttgjwbdir',
        ];
    }

    /** Nama method dipertahankan: dipanggil ZIP Showtransparansi / Showtahunan. */
    public function exporttxttgjwbdir()
    {
        return $this->exportTxt();
    }
}
