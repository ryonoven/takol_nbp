<?php

namespace App\Models;

/** Laporan Audit Intern — III. Kesimpulan Audit dan Pernyataan Auditor. */
class M_kesimpulanaudit extends M_semesterlist
{
    protected $table = 'kesimpulanaudit';
    protected $allowedFields = [
        'user_id', 'kodebpr', 'periode_id', 'tahun', 'semester',
        'kesimpulan', 'pernyataan',
        'fullname', 'accdir2', 'accdir2_by', 'accdir2_at', 'is_null_record',
    ];

    public const FIELDS = [
        'kesimpulan' => ['Kesimpulan audit', 'type' => 'textarea', 'max' => 2000, 'col' => 'col-12', 'rows' => 6],
        'pernyataan' => ['Pernyataan auditor', 'type' => 'textarea', 'max' => 2000, 'col' => 'col-12', 'rows' => 6],
    ];
    public const IMPORT_COLS = ['kesimpulan', 'pernyataan'];

    /** Dipakai LaporanPdfAudit. */
    public function getDataByKodebprAndPeriode(string $kodebpr, int $periodeId): array
    {
        return $this->rows($kodebpr, $periodeId);
    }
}
