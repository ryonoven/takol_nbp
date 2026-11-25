<?php

namespace App\Controllers;

use App\Libraries\TransparansiAnggotaController;
use App\Models\M_anggotatransparansi;
use App\Models\M_shmdirdekomlain;

class Shmdirdekomlain extends TransparansiAnggotaController
{
    protected function newModel(): M_anggotatransparansi
    {
        return new M_shmdirdekomlain();
    }

    protected function config(): array
    {
        return [
            'page' => 'Shmdirdekomlain',
            'judul' => 'Kepemilikan Saham Anggota Direksi dan Dewan Komisaris pada Perusahaan Lain',
            'entity' => 'Kepemilikan Saham',
            'listTitle' => 'Kepemilikan Saham pada Perusahaan Lain',
            'icon' => 'fa-briefcase',
            'tindak' => ['Penjelasan Lebih Lanjut Kepemilikan Saham pada Perusahaan Lain', null],
            'exportMethod' => 'exporttxtshmdirdekomlain',
            'nodataNote' => 'Bila tidak ada kepemilikan saham pada perusahaan lain, klik "Tidak Ada Data" agar halaman ini dapat diteruskan untuk persetujuan.',
        ];
    }

    /** Nama method dipertahankan: dipanggil ZIP Showtransparansi / Showtahunan. */
    public function exporttxtshmdirdekomlain()
    {
        return $this->exportTxt();
    }
}
