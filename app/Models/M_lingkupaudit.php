<?php

namespace App\Models;

/** Laporan Audit Intern — I. Ruang Lingkup dan Waktu Pelaksanaan Audit. */
class M_lingkupaudit extends M_semesterlist
{
    protected $table = 'lingkupaudit';
    protected $allowedFields = [
        'user_id', 'kodebpr', 'periode_id', 'tahun', 'semester',
        'ruanglingkup', 'tanggalmulai', 'tanggalberakhir',
        'fullname', 'accdir2', 'accdir2_by', 'accdir2_at', 'is_null_record',
    ];

    public const FIELDS = [
        'tanggalmulai' => ['Tanggal mulai', 'type' => 'date', 'col' => 'col-md-6', 'short' => 'Mulai'],
        'tanggalberakhir' => ['Tanggal berakhir', 'type' => 'date', 'col' => 'col-md-6', 'short' => 'Berakhir'],
        'ruanglingkup' => ['Ruang lingkup audit', 'type' => 'textarea', 'max' => 2000, 'col' => 'col-12', 'rows' => 7],
    ];
    public const DATE_RANGE = ['tanggalmulai', 'tanggalberakhir'];
    public const IMPORT_COLS = ['tanggalmulai', 'tanggalberakhir', 'ruanglingkup'];

    /** Dipakai LaporanPdfAudit. */
    public function getDataByKodebprAndPeriode(string $kodebpr, int $periodeId): array
    {
        return $this->rows($kodebpr, $periodeId);
    }
}
