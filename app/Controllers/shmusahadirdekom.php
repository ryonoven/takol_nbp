<?php

namespace App\Controllers;

use App\Libraries\TransparansiAnggotaController;
use App\Models\M_anggotatransparansi;
use App\Models\M_shmusahadirdekom;

/**
 * Laporan Transparansi 7 — Kepemilikan Saham Anggota Direksi, Dewan Komisaris, dan Pemegang Saham
 * pada Kelompok Usaha BPR (E0302).
 * Seluruh logika ada di TransparansiAnggotaController; komentar memakai widget bersama templates/komentar.
 */
class Shmusahadirdekom extends TransparansiAnggotaController
{
    protected function newModel(): M_anggotatransparansi
    {
        return new M_shmusahadirdekom();
    }

    protected function config(): array
    {
        return [
            'page' => 'Shmusahadirdekom',
            'judul' => 'Kepemilikan Saham Anggota Direksi, Dewan Komisaris, dan Pemegang Saham pada Kelompok Usaha BPR',
            'entity' => 'Kepemilikan Saham',
            'listTitle' => 'Kepemilikan Saham pada Kelompok Usaha',
            'icon' => 'fa-building',
            'tindak' => ['Penjelasan Lebih Lanjut Kepemilikan Saham pada Kelompok Usaha BPR', null],
            'exportMethod' => 'exporttxtshmusahadirdekom',
            'nodataNote' => 'Bila tidak ada kepemilikan saham pada kelompok usaha BPR, klik "Tidak Ada Data" agar halaman ini dapat diteruskan untuk persetujuan.',
        ];
    }

    /** Nama method dipertahankan: dipanggil ZIP Showtransparansi / Showtahunan. */
    public function exporttxtshmusahadirdekom()
    {
        return $this->exportTxt();
    }
}
