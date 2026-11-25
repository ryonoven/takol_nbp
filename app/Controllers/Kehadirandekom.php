<?php

namespace App\Controllers;

use App\Libraries\TransparansiAnggotaController;
use App\Models\M_anggotatransparansi;
use App\Models\M_kehadirandekom;

/**
 * Laporan Transparansi 14 — Kehadiran Anggota Dewan Komisaris (E0702).
 * Seluruh logika ada di TransparansiAnggotaController; komentar memakai widget bersama templates/komentar.
 */
class Kehadirandekom extends TransparansiAnggotaController
{
    protected function newModel(): M_anggotatransparansi
    {
        return new M_kehadirandekom();
    }

    protected function config(): array
    {
        return [
            'page' => 'Kehadirandekom',
            'judul' => 'Kehadiran Anggota Dewan Komisaris',
            'entity' => 'Kehadiran Dewan Komisaris',
            'listTitle' => 'Kehadiran Anggota Dewan Komisaris',
            'icon' => 'fa-user-check',
            'tindak' => ['Penjelasan Lebih Lanjut Kehadiran Anggota Dewan Komisaris', null],
            'exportMethod' => 'exporttxtkehadirandekom',
        ];
    }

    /** Nama method dipertahankan: dipanggil ZIP Showtransparansi / Showtahunan. */
    public function exporttxtkehadirandekom()
    {
        return $this->exportTxt();
    }
}
