<?php

namespace App\Models;

/** Laporan Audit Intern — IV. Rekomendasi Auditor, Tanggapan Auditee, dan Tindak Lanjut. */
class M_rekomendasiaudit extends M_semesterlist
{
    protected $table = 'rekomendasiaudit';
    protected $allowedFields = [
        'user_id', 'kodebpr', 'periode_id', 'tahun', 'semester',
        'penyimpangan', 'usulan', 'tindaklanjut', 'uraian', 'bataswaktu',
        'fullname', 'accdir2', 'accdir2_by', 'accdir2_at', 'is_null_record',
    ];

    public const FIELDS = [
        'bataswaktu' => ['Batas waktu penyelesaian tindak lanjut/komitmen', 'type' => 'date', 'col' => 'col-md-6', 'short' => 'Batas waktu'],
        'penyimpangan' => ['Penyimpangan', 'type' => 'textarea', 'max' => 2000, 'col' => 'col-12', 'rows' => 4],
        'usulan' => ['Usulan langkah perbaikan', 'type' => 'textarea', 'max' => 2000, 'col' => 'col-12', 'rows' => 4],
        'tindaklanjut' => ['Tindak lanjut/komitmen auditee', 'type' => 'textarea', 'max' => 2000, 'col' => 'col-md-6', 'rows' => 4],
        'uraian' => ['Uraian komitmen auditee', 'type' => 'textarea', 'max' => 2000, 'col' => 'col-md-6', 'rows' => 4],
    ];
    /** Urutan kolom template: A penyimpangan · B usulan · C tindak lanjut · D uraian · E batas waktu. */
    public const IMPORT_COLS = ['penyimpangan', 'usulan', 'tindaklanjut', 'uraian', 'bataswaktu'];

    /** bataswaktu NOT NULL tanpa default → baris "Tidak ada data" diisi tanggal akhir semester. */
    public static function nullValues(array $periode): array
    {
        $tahun = (int) ($periode['tahun'] ?? date('Y'));
        $end = (int) ($periode['semester'] ?? 1) === 1 ? "{$tahun}-06-30" : "{$tahun}-12-31";
        return ['bataswaktu' => $end] + parent::nullValues($periode);
    }

    /** Dipakai LaporanPdfAudit. */
    public function getDataByKodebprAndPeriode(string $kodebpr, int $periodeId): array
    {
        return $this->rows($kodebpr, $periodeId);
    }
}
