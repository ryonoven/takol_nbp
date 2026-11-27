<?php

namespace App\Models;

/** Laporan Audit Intern — II. Temuan Audit. */
class M_temuanaudit extends M_semesterlist
{
    protected $table = 'temuanaudit';
    protected $allowedFields = [
        'user_id', 'kodebpr', 'periode_id', 'tahun', 'semester',
        'tanggal', 'fakta', 'penyebab', 'dampak',
        'fullname', 'accdir2', 'accdir2_by', 'accdir2_at', 'is_null_record',
    ];

    public const FIELDS = [
        'tanggal' => ['Tanggal kejadian', 'type' => 'date', 'col' => 'col-md-6', 'short' => 'Tanggal'],
        'fakta' => ['Fakta yang terjadi', 'type' => 'textarea', 'max' => 2000, 'col' => 'col-12', 'rows' => 4],
        'penyebab' => ['Penyebab penyimpangan', 'type' => 'textarea', 'max' => 1000, 'col' => 'col-md-6', 'rows' => 4],
        'dampak' => ['Dampak penyimpangan', 'type' => 'textarea', 'max' => 1000, 'col' => 'col-md-6', 'rows' => 4],
    ];
    public const IMPORT_COLS = ['tanggal', 'fakta', 'penyebab', 'dampak'];

    /** Dipakai LaporanPdfAudit. */
    public function getDataByKodebprAndPeriode(string $kodebpr, int $periodeId): array
    {
        return $this->rows($kodebpr, $periodeId);
    }
}
