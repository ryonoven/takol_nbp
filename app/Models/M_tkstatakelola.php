<?php

namespace App\Models;

/** TKS — Faktor 2: Tata Kelola (bobot 30%). Sumber sinkron: Laporan Penilaian Sendiri Tata Kelola (showfaktor). */
class M_tkstatakelola extends M_tksfaktor
{
    protected $table = 'tkstatakelola';
    protected $allowedFields = [
        'user_id', 'kodebpr', 'periode_id',
        'ratarata', 'nilaiperingkat', 'peringkat', 'nilaifaktor',
        'nilaiperingkatbefore', 'peringkatbefore', 'nilaifaktorbefore',
        'deskripsi', 'analisis',
        'is_approved', 'approved_by', 'approved_at', 'accdir2', 'accdir2_by',
        'synced_from_tk', 'sync_date', 'created_at', 'updated_at',
    ];

    public const BOBOT = 0.30;
    public const LABELS = [1 => 'Sangat Baik', 2 => 'Baik', 3 => 'Cukup', 4 => 'Kurang Baik', 5 => 'Buruk'];
    public const SYNC_FLAG = 'synced_from_tk';
}
