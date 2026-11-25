<?php

namespace App\Controllers;

use App\Libraries\TransparansiAnggotaController;
use App\Models\M_anggotatransparansi;
use App\Models\M_transaksikepentingan;

/**
 * Laporan Transparansi 17 — Transaksi yang Mengandung Benturan Kepentingan (E1000).
 * Seluruh logika ada di TransparansiAnggotaController; komentar memakai widget bersama templates/komentar.
 */
class Transaksikepentingan extends TransparansiAnggotaController
{
    protected function newModel(): M_anggotatransparansi
    {
        return new M_transaksikepentingan();
    }

    protected function config(): array
    {
        return [
            'page' => 'Transaksikepentingan',
            'judul' => 'Transaksi yang Mengandung Benturan Kepentingan',
            'entity' => 'Transaksi',
            'listTitle' => 'Transaksi Benturan Kepentingan',
            'icon' => 'fa-exchange-alt',
            'tindak' => ['Penjelasan Lebih Lanjut', null],
            'exportMethod' => 'exporttxttransaksikepentingan',
            'nodataNote' => 'Bila tidak ada transaksi yang mengandung benturan kepentingan, klik "Tidak Ada Data" agar halaman ini dapat diteruskan untuk persetujuan.',
        ];
    }

    /** Nama method dipertahankan: dipanggil ZIP Showtransparansi / Showtahunan. */
    public function exporttxttransaksikepentingan()
    {
        return $this->exportTxt();
    }
}
