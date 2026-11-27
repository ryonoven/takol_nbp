<?php

namespace App\Models;

/** TKS — Faktor 1: Profil Risiko (bobot 25%). Sumber sinkron: Laporan Profil Risiko. */
class M_tksprofilrisiko extends M_tksfaktor
{
    protected $table = 'tksprofilrisiko';
    protected $allowedFields = [
        'user_id', 'fullname', 'kodebpr', 'periode_id',
        'ratarata', 'nilaiperingkat', 'peringkat', 'nilaifaktor',
        'rataratabefore', 'nilaiperingkatbefore', 'peringkatbefore', 'nilaifaktorbefore',
        'deskripsi', 'analisis',
        'is_approved', 'approved_by', 'approved_at', 'accdir2', 'accdir2_by',
        'synced_from_pr', 'sync_date', 'created_at', 'updated_at',
    ];

    public const BOBOT = 0.25;
    public const LABELS = [1 => 'Sangat Rendah', 2 => 'Rendah', 3 => 'Sedang', 4 => 'Tinggi', 5 => 'Sangat Tinggi'];
    public const SYNC_FLAG = 'synced_from_pr';
}
