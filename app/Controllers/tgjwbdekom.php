<?php

namespace App\Controllers;

use App\Libraries\TransparansiAnggotaController;
use App\Models\M_anggotatransparansi;
use App\Models\M_tgjwbdekom;

/**
 * Laporan Transparansi 3 — Pelaksanaan Tugas dan Tanggung Jawab Anggota Dewan Komisaris (E0202).
 * Seluruh logika ada di TransparansiAnggotaController; komentar memakai widget bersama templates/komentar.
 */
class Tgjwbdekom extends TransparansiAnggotaController
{
    protected function newModel(): M_anggotatransparansi
    {
        return new M_tgjwbdekom();
    }

    protected function config(): array
    {
        return [
            'page' => 'Tgjwbdekom',
            'judul' => 'Pelaksanaan Tugas dan Tanggung Jawab Anggota Dewan Komisaris',
            'entity' => 'Dewan Komisaris',
            'listTitle' => 'Anggota Dewan Komisaris',
            'icon' => 'fa-user-shield',
            'tindak' => ['Rekomendasi Dewan Komisaris kepada Direksi BPR', 'Penjelasan Lebih Lanjut'],
            'exportMethod' => 'exporttxttgjwbdekom',
        ];
    }

    /** Nama method dipertahankan: dipanggil ZIP Showtransparansi / Showtahunan. */
    public function exporttxttgjwbdekom()
    {
        return $this->exportTxt();
    }
}
