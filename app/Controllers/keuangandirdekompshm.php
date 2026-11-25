<?php

namespace App\Controllers;

use App\Libraries\TransparansiAnggotaController;
use App\Models\M_anggotatransparansi;
use App\Models\M_keuangandirdekompshm;

/**
 * Laporan Transparansi 9 — Hubungan Keuangan Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR (E0401).
 * Seluruh logika ada di TransparansiAnggotaController; komentar memakai widget bersama templates/komentar.
 */
class Keuangandirdekompshm extends TransparansiAnggotaController
{
    protected function newModel(): M_anggotatransparansi
    {
        return new M_keuangandirdekompshm();
    }

    protected function config(): array
    {
        return [
            'page' => 'Keuangandirdekompshm',
            'judul' => 'Hubungan Keuangan Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR',
            'entity' => 'Hubungan Keuangan',
            'listTitle' => 'Hubungan Keuangan',
            'icon' => 'fa-hand-holding-usd',
            'tindak' => ['Penjelasan Lebih Lanjut Hubungan Keuangan', null],
            'exportMethod' => 'exporttxtkeuangandirdekompshm',
            'nodataNote' => 'Bila tidak ada hubungan keuangan yang perlu dilaporkan, klik "Tidak Ada Data" agar halaman ini dapat diteruskan untuk persetujuan.',
        ];
    }

    /** Nama method dipertahankan: dipanggil ZIP Showtransparansi / Showtahunan. */
    public function exporttxtkeuangandirdekompshm()
    {
        return $this->exportTxt();
    }
}
