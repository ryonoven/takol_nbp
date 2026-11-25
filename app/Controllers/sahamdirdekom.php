<?php

namespace App\Controllers;

use App\Libraries\TransparansiAnggotaController;
use App\Models\M_anggotatransparansi;
use App\Models\M_sahamdirdekom;

/**
 * Laporan Transparansi 6 — Kepemilikan Saham Anggota Direksi dan Dewan Komisaris pada BPR.
 * Seluruh logika ada di TransparansiAnggotaController; komentar memakai widget bersama templates/komentar.
 * Halaman ini tidak memiliki export TXT (lihat M_showtransparansi: export = null).
 */
class Sahamdirdekom extends TransparansiAnggotaController
{
    protected function newModel(): M_anggotatransparansi
    {
        return new M_sahamdirdekom();
    }

    protected function config(): array
    {
        return [
            'page' => 'Sahamdirdekom',
            'judul' => 'Kepemilikan Saham Anggota Direksi dan Dewan Komisaris pada BPR',
            'entity' => 'Pemilik Saham',
            'listTitle' => 'Kepemilikan Saham',
            'icon' => 'fa-chart-pie',
            'tindak' => ['Tindak Lanjut Rekomendasi Kepemilikan Saham Anggota Direksi dan Dewan Komisaris', null],
            'exportMethod' => null,
            'nodataNote' => 'Bila tidak ada anggota Direksi atau Dewan Komisaris yang memiliki saham BPR, klik "Tidak Ada Data" agar halaman ini dapat diteruskan untuk persetujuan.',
        ];
    }
}
