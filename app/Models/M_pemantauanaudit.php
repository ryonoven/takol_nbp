<?php

namespace App\Models;

/** Laporan Audit Intern — V. Hasil Pemantauan Komitmen Auditee. */
class M_pemantauanaudit extends M_semesterlist
{
    protected $table = 'pemantauanaudit';
    protected $allowedFields = [
        'user_id', 'kodebpr', 'periode_id', 'tahun', 'semester',
        'realisasi', 'hasil', 'status',
        'fullname', 'accdir2', 'accdir2_by', 'accdir2_at', 'is_null_record',
    ];

    public const STATUS_OPTIONS = ['01' => 'Selesai', '02' => 'Proses', '03' => 'Belum Ditindaklanjuti'];

    public const FIELDS = [
        'status' => ['Status realisasi komitmen auditee', 'type' => 'select', 'options' => self::STATUS_OPTIONS, 'col' => 'col-md-6', 'short' => 'Status'],
        'realisasi' => ['Realisasi komitmen auditee', 'type' => 'textarea', 'max' => 2000, 'col' => 'col-12', 'rows' => 5],
        'hasil' => ['Hasil pemantauan komitmen', 'type' => 'textarea', 'max' => 2000, 'col' => 'col-12', 'rows' => 5],
    ];
    /** Urutan kolom template: A realisasi · B hasil · C status (kode atau teks). */
    public const IMPORT_COLS = ['realisasi', 'hasil', 'status'];

    /** Dipakai LaporanPdfAudit. */
    public function getDataByKodebprAndPeriode(string $kodebpr, int $periodeId): array
    {
        return $this->rows($kodebpr, $periodeId);
    }
}
