<?php

namespace App\Controllers;

use App\Libraries\TransparansiAnggotaController;
use App\Models\M_anggotatransparansi;
use App\Models\M_danasosial;

/**
 * Laporan Transparansi 18 — Pemberian Dana untuk Kegiatan Sosial dan Kegiatan Politik (E1100).
 * Seluruh logika ada di TransparansiAnggotaController; komentar memakai widget bersama templates/komentar.
 */
class Danasosial extends TransparansiAnggotaController
{
    protected function newModel(): M_anggotatransparansi
    {
        return new M_danasosial();
    }

    protected function config(): array
    {
        return [
            'page' => 'Danasosial',
            'judul' => 'Pemberian Dana untuk Kegiatan Sosial dan Kegiatan Politik',
            'entity' => 'Pemberian Dana',
            'listTitle' => 'Pemberian Dana',
            'icon' => 'fa-hand-holding-heart',
            'tindak' => ['Penjelasan Lebih Lanjut', null],
            'exportMethod' => 'exporttxtdanasosial',
            'nodataNote' => 'Bila tidak ada pemberian dana untuk kegiatan sosial maupun politik, klik "Tidak Ada Data" agar halaman ini dapat diteruskan untuk persetujuan.',
        ];
    }

    /** Nama method dipertahankan: dipanggil ZIP Showtransparansi / Showtahunan. */
    public function exporttxtdanasosial()
    {
        return $this->exportTxt();
    }
}
