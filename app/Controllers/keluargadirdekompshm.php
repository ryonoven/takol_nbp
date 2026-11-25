<?php

namespace App\Controllers;

use App\Libraries\TransparansiAnggotaController;
use App\Models\M_anggotatransparansi;
use App\Models\M_keluargadirdekompshm;

/**
 * Laporan Transparansi 10 — Hubungan Keluarga Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR (E0402).
 * Seluruh logika ada di TransparansiAnggotaController; komentar memakai widget bersama templates/komentar.
 */
class Keluargadirdekompshm extends TransparansiAnggotaController
{
    protected function newModel(): M_anggotatransparansi
    {
        return new M_keluargadirdekompshm();
    }

    protected function config(): array
    {
        return [
            'page' => 'Keluargadirdekompshm',
            'judul' => 'Hubungan Keluarga Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR',
            'entity' => 'Hubungan Keluarga',
            'listTitle' => 'Hubungan Keluarga',
            'icon' => 'fa-people-arrows',
            'tindak' => ['Penjelasan Lebih Lanjut Hubungan Keluarga', null],
            'exportMethod' => 'exporttxtkeluargadirdekompshm',
            'nodataNote' => 'Bila tidak ada hubungan keluarga yang perlu dilaporkan, klik "Tidak Ada Data" agar halaman ini dapat diteruskan untuk persetujuan.',
        ];
    }

    /** Nama method dipertahankan: dipanggil ZIP Showtransparansi / Showtahunan. */
    public function exporttxtkeluargadirdekompshm()
    {
        return $this->exportTxt();
    }
}
