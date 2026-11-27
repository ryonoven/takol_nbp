<?php

namespace App\Models;

/** TKS — Faktor 3: Rentabilitas (bobot 15%): ROA, BOPO, NIM. */
class M_tksrentabilitas extends M_tksindikator
{
    protected $table = 'tksrentabilitas';
    protected $allowedFields = [
        'user_id', 'kodebpr', 'periode_id', 'fullname',
        'labarugibelumpajak', 'ratarataaset', 'rasioroa', 'peringkatroa', 'penilaianroa',
        'lababelumpajakbefore', 'ratarataasetbefore', 'rasioroabefore', 'peringkatroabefore', 'penilaianroabefore',
        'bebanoperasional', 'pendapatanoperasional', 'rasiobopo', 'peringkatbopo', 'penilaianbopo',
        'bebanoperasionalbefore', 'pendapatanoperasionalbefore', 'rasiobopobefore', 'peringkatbopobefore', 'penilaianbopobefore',
        'pendapatanbunga', 'bebanbunga', 'ratarataasetproduktif', 'pendapatanbungabersih', 'rasionim', 'peringkatnim', 'penilaiannim',
        'pendapatanbungabefore', 'bebanbungabefore', 'ratarataasetprodbefore', 'pendapatanbungabersihbefore', 'rasionimbefore', 'peringkatnimbefore', 'penilaiannimbefore',
        'rataratarentabilitas', 'penilaianrentabilitas', 'peringkatrentabilitas', 'nilaifaktor',
        'rataratarentabilitasbefore', 'penilaianrentabilitasbefore', 'peringkatrentabilitasbefore', 'nilaifaktorbefore',
        'deskripsirentabilitas', 'analisarentabilitas',
        'is_approved', 'approved_by', 'approved_at', 'created_at', 'updated_at',
    ];

    public const BOBOT = 0.15;
    public const LABELS = [1 => 'Sangat Baik', 2 => 'Baik', 3 => 'Cukup', 4 => 'Kurang Baik', 5 => 'Buruk'];

    public const INDIKATOR = [
        'roa' => [
            'title' => 'Return on Asset (ROA)',
            'formula' => 'Laba sebelum pajak ÷ Rata-rata aset × 100%',
            'inputs' => [
                ['Laba/(rugi) sebelum pajak (disetahunkan)', 'labarugibelumpajak', 'lababelumpajakbefore'],
                ['Rata-rata aset', 'ratarataaset', 'ratarataasetbefore'],
            ],
            'calc' => 'ratio', 'den' => 'nonzero', 'dir' => 'high', 'limits' => [2, 1.5, 1, 0.5],
            'ratio' => ['rasioroa', 'rasioroabefore'],
            'rank' => ['peringkatroa', 'peringkatroabefore'],
            'label' => ['penilaianroa', 'penilaianroabefore'],
        ],
        'bopo' => [
            'title' => 'BOPO',
            'formula' => 'Beban operasional ÷ Pendapatan operasional × 100%',
            'inputs' => [
                ['Beban operasional (disetahunkan)', 'bebanoperasional', 'bebanoperasionalbefore'],
                ['Pendapatan operasional (disetahunkan)', 'pendapatanoperasional', 'pendapatanoperasionalbefore'],
            ],
            'calc' => 'ratio', 'den' => 'nonzero', 'dir' => 'low', 'limits' => [85, 90, 95, 100],
            'ratio' => ['rasiobopo', 'rasiobopobefore'],
            'rank' => ['peringkatbopo', 'peringkatbopobefore'],
            'label' => ['penilaianbopo', 'penilaianbopobefore'],
        ],
        'nim' => [
            'title' => 'Net Interest Margin (NIM)',
            'formula' => '(Pendapatan bunga − Beban bunga) ÷ Rata-rata aset produktif × 100%',
            'inputs' => [
                ['Pendapatan bunga (disetahunkan)', 'pendapatanbunga', 'pendapatanbungabefore'],
                ['Beban bunga (disetahunkan)', 'bebanbunga', 'bebanbungabefore'],
                ['Rata-rata aset produktif', 'ratarataasetproduktif', 'ratarataasetprodbefore'],
            ],
            'calc' => 'net', 'den' => 'nonzero', 'dir' => 'high', 'limits' => [10, 8, 6, 4],
            'ratio' => ['rasionim', 'rasionimbefore'],
            'rank' => ['peringkatnim', 'peringkatnimbefore'],
            'label' => ['penilaiannim', 'penilaiannimbefore'],
            'net' => ['pendapatanbungabersih', 'pendapatanbungabersihbefore'],
        ],
    ];

    public const FAKTOR_COLS = [
        ['rataratarentabilitas', 'penilaianrentabilitas', 'peringkatrentabilitas', 'nilaifaktor'],
        ['rataratarentabilitasbefore', 'penilaianrentabilitasbefore', 'peringkatrentabilitasbefore', 'nilaifaktorbefore'],
    ];

    public const TEXT_COLS = ['deskripsirentabilitas', 'analisarentabilitas'];

    protected const EXTRA_DEFAULTS = ['fullname' => '', 'is_approved' => 0];
}
