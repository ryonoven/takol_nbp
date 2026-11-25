<?php

namespace App\Controllers;

use App\Libraries\TransparansiAnggotaController;
use App\Models\M_anggotatransparansi;
use App\Models\M_strukturkomite;

/**
 * Laporan Transparansi 5 — Struktur, Keanggotaan, Keahlian, dan Independensi Anggota Komite (E0204).
 * Seluruh logika ada di TransparansiAnggotaController; komentar memakai widget bersama templates/komentar.
 */
class Strukturkomite extends TransparansiAnggotaController
{
    protected function newModel(): M_anggotatransparansi
    {
        return new M_strukturkomite();
    }

    protected function config(): array
    {
        return [
            'page' => 'Strukturkomite',
            'judul' => 'Struktur, Keanggotaan, Keahlian, dan Independensi Anggota Komite',
            'entity' => 'Anggota Komite',
            'listTitle' => 'Anggota Komite',
            'icon' => 'fa-sitemap',
            'tindak' => ['Tindak Lanjut Rekomendasi Struktur, Keanggotaan, Keahlian, dan Independensi Anggota Komite', null],
            'exportMethod' => 'exporttxtstrukturkomite',
            'nodataNote' => 'Bila BPR tidak memiliki anggota komite, klik "Tidak Ada Data" agar halaman ini dapat diteruskan untuk persetujuan.',
        ];
    }

    /** Nama method dipertahankan: dipanggil ZIP Showtransparansi / Showtahunan. */
    public function exporttxtstrukturkomite()
    {
        return $this->exportTxt();
    }
}
