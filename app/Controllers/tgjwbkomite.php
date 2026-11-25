<?php

namespace App\Controllers;

use App\Libraries\TransparansiAnggotaController;
use App\Models\M_anggotatransparansi;
use App\Models\M_tgjwbkomite;

/**
 * Laporan Transparansi 4 — Tugas, Tanggung Jawab, Program Kerja, dan Realisasi Program Kerja Komite (E0203).
 * Seluruh logika ada di TransparansiAnggotaController; komentar memakai widget bersama templates/komentar.
 */
class Tgjwbkomite extends TransparansiAnggotaController
{
    protected function newModel(): M_anggotatransparansi
    {
        return new M_tgjwbkomite();
    }

    protected function config(): array
    {
        return [
            'page' => 'Tgjwbkomite',
            'judul' => 'Tugas, Tanggung Jawab, Program Kerja, dan Realisasi Program Kerja Komite',
            'entity' => 'Komite',
            'listTitle' => 'Daftar Komite',
            'icon' => 'fa-users',
            'tindak' => ['Tindak Lanjut Rekomendasi Program Kerja dan Realisasi Program Kerja Komite', null],
            'exportMethod' => 'exporttxttgjwbkomite',
            'nodataNote' => 'Bila BPR tidak memiliki komite, klik "Tidak Ada Data" agar halaman ini dapat diteruskan untuk persetujuan.',
        ];
    }

    /** Nama method dipertahankan: dipanggil ZIP Showtransparansi / Showtahunan. */
    public function exporttxttgjwbkomite()
    {
        return $this->exportTxt();
    }
}
