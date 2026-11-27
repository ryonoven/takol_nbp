<?php

namespace App\Models;

/** Laporan Direktur YMF Kepatuhan — 5. Penyimpangan terhadap Kepatuhan Peraturan Perundang-undangan (form K1700). */
class M_penyimpangankepatuhan extends M_semesterlist
{
    protected $table = 'penyimpangankepatuhan';
    protected $allowedFields = [
        'user_id', 'kodebpr', 'periode_id', 'tahun', 'semester',
        'pelanggaran', 'tanggaltemuan', 'uraian', 'upaya', 'statuspenyelesaian',
        'accdir2', 'accdir2_by', 'accdir2_at', 'is_null_record',
    ];

    public const STATUS = ['01' => 'Sudah Diselesaikan', '02' => 'Dalam Proses Penyelesaian', '03' => 'Belum Diselesaikan'];

    public const FIELDS = [
        'pelanggaran' => ['Peraturan yang dilanggar', 'type' => 'textarea', 'max' => 2000, 'col' => 'col-12', 'rows' => 2, 'short' => 'Peraturan'],
        'tanggaltemuan' => ['Tanggal temuan', 'type' => 'date', 'col' => 'col-md-6', 'short' => 'Ditemukan'],
        'statuspenyelesaian' => ['Status penyelesaian', 'type' => 'select', 'options' => self::STATUS, 'required' => false, 'col' => 'col-md-6', 'short' => 'Status',
            'aliases' => ['selesai' => '01', 'sudah selesai' => '01', 'proses' => '02', 'dalam proses' => '02', 'belum' => '03', 'belum selesai' => '03']],
        'uraian' => ['Uraian penyimpangan', 'type' => 'textarea', 'max' => 2000, 'required' => false, 'col' => 'col-12', 'rows' => 3, 'short' => 'Uraian'],
        'upaya' => ['Upaya yang telah dilakukan untuk mencegah penyimpangan', 'type' => 'textarea', 'max' => 2000, 'required' => false, 'col' => 'col-12', 'rows' => 3, 'short' => 'Upaya pencegahan'],
    ];
    /** Template: A peraturan dilanggar · B tanggal temuan · C uraian · D upaya · E status (kode atau teks). */
    public const IMPORT_COLS = ['pelanggaran', 'tanggaltemuan', 'uraian', 'upaya', 'statuspenyelesaian'];

    /** Kartu penyimpangan (gaya Tugas & Tanggung Jawab Direksi). */
    public const CARD = [
        'title' => 'pelanggaran',
        'tag' => 'statuspenyelesaian',
        'sub' => ['tanggaltemuan'],
        'body' => ['uraian', 'upaya'],
        'utama' => ['01'],
        'icon' => 'fa-exclamation-triangle',
    ];
    public const ROW_APPROVAL = false;
    /** Sinkron periode sebelumnya / impor: peraturan + tanggal temuan yang sama dilewati (sama seperti sebelumnya). */
    public const DUP_KEY = ['pelanggaran', 'tanggaltemuan'];

    /** Dipakai LaporanPdfGcg. */
    public function getDataByKodebprAndPeriode(string $kodebpr, int $periodeId): array
    {
        return $this->rows($kodebpr, $periodeId);
    }
}
