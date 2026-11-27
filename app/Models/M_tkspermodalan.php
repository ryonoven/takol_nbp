<?php

namespace App\Models;

/** TKS — Faktor 4: Permodalan (bobot 30%): KPMM, MIAPB. */
class M_tkspermodalan extends M_tksindikator
{
    protected $table = 'tkspermodalan';
    protected $allowedFields = [
        'user_id', 'kodebpr', 'periode_id',
        'modalinti', 'modalpelengkap', 'atmr', 'rasiokpmm', 'penilaiankpmm', 'peringkatkpmm',
        'modalintibefore', 'modalpelengkapbefore', 'atmrbefore', 'rasiokpmmbefore', 'penilaiankpmmbefore', 'peringkatkpmmbefore',
        'modalintiapb', 'totalasetbermasalah', 'rasiomiapb', 'penilaianmiapb', 'peringkatmiapb',
        'modalintiapbbefore', 'totalasetbermasalahbefore', 'rasiomiapbbefore', 'penilaianmiapbbefore', 'peringkatmiapbbefore',
        'rataratapermodalan', 'penilaianpermodalan', 'peringkatpermodalan', 'nilaifaktor',
        'rataratapermodalanbefore', 'penilaianpermodalanbefore', 'peringkatpermodalanbefore', 'nilaifaktorbefore',
        'deskripsipermodalan', 'analisispermodalan',
        'is_approved', 'approved_by', 'approved_at', 'accdir2', 'accdir2_by', 'created_at', 'updated_at',
    ];

    public const BOBOT = 0.30;
    /** Label sesuai data yang sudah tersimpan (penilaiankpmm / penilaianmiapb). */
    public const LABELS = [1 => 'Sangat Rendah', 2 => 'Rendah', 3 => 'Sedang', 4 => 'Tinggi', 5 => 'Sangat Tinggi'];

    public const INDIKATOR = [
        'kpmm' => [
            'title' => 'Kewajiban Penyediaan Modal Minimum (KPMM)',
            'formula' => '(Modal inti + Modal pelengkap) ÷ ATMR × 100%',
            'inputs' => [
                ['Modal inti', 'modalinti', 'modalintibefore'],
                ['Modal pelengkap', 'modalpelengkap', 'modalpelengkapbefore'],
                ['Aset Tertimbang Menurut Risiko (ATMR)', 'atmr', 'atmrbefore'],
            ],
            'calc' => 'sum', 'den' => 'positive', 'dir' => 'high', 'limits' => [15, 13, 8, 6],
            'ratio' => ['rasiokpmm', 'rasiokpmmbefore'],
            'rank' => ['peringkatkpmm', 'peringkatkpmmbefore'],
            'label' => ['penilaiankpmm', 'penilaiankpmmbefore'],
        ],
        'miapb' => [
            'title' => 'Modal Inti terhadap Aset Produktif Bermasalah (MIAPB)',
            'formula' => 'Modal inti ÷ Total aset produktif bermasalah (netto) × 100%',
            'inputs' => [
                ['Modal inti', 'modalintiapb', 'modalintiapbbefore'],
                ['Total aset produktif bermasalah (netto)', 'totalasetbermasalah', 'totalasetbermasalahbefore'],
            ],
            'calc' => 'ratio', 'den' => 'positive', 'dir' => 'high', 'limits' => [200, 180, 150, 120],
            'ratio' => ['rasiomiapb', 'rasiomiapbbefore'],
            'rank' => ['peringkatmiapb', 'peringkatmiapbbefore'],
            'label' => ['penilaianmiapb', 'penilaianmiapbbefore'],
        ],
    ];

    public const FAKTOR_COLS = [
        ['rataratapermodalan', 'penilaianpermodalan', 'peringkatpermodalan', 'nilaifaktor'],
        ['rataratapermodalanbefore', 'penilaianpermodalanbefore', 'peringkatpermodalanbefore', 'nilaifaktorbefore'],
    ];

    public const TEXT_COLS = ['deskripsipermodalan', 'analisispermodalan'];

    /** is_approved & accdir2 = ENUM('1','0') → kirim sebagai string. */
    protected const EXTRA_DEFAULTS = ['is_approved' => '0', 'accdir2' => '0', 'accdir2_by' => 0];

    /** Kolom "Modal inti" MIAPB mengikuti Modal inti KPMM bila belum diisi. */
    public const COPY_IF_EMPTY = ['modalinti' => 'modalintiapb', 'modalintibefore' => 'modalintiapbbefore'];
}
