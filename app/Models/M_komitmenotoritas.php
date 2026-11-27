<?php

namespace App\Models;

/** Laporan Direktur YMF Kepatuhan — 6. Pelaksanaan Pemenuhan Komitmen terhadap Otoritas (form K1800). */
class M_komitmenotoritas extends M_semesterlist
{
    protected $table = 'komitmenotoritas';
    protected $allowedFields = [
        'user_id', 'kodebpr', 'periode_id', 'tahun', 'semester',
        'uraian', 'pemberikomitmen', 'pelaksanaan', 'pelaksanakomitmen', 'statuskomitmen',
        'accdir2', 'accdir2_by', 'accdir2_at', 'is_null_record',
    ];

    public const STATUS = ['01' => 'Selesai', '02' => 'Proses', '03' => 'Belum Ditindaklanjuti'];

    public const FIELDS = [
        'uraian' => ['Uraian komitmen', 'type' => 'textarea', 'max' => 2000, 'col' => 'col-12', 'rows' => 3, 'short' => 'Uraian komitmen'],
        'pemberikomitmen' => ['Pihak pemberi komitmen', 'type' => 'text', 'max' => 2000, 'required' => false, 'col' => 'col-md-6', 'short' => 'Pemberi'],
        'pelaksanakomitmen' => ['Pihak pelaksana komitmen', 'type' => 'text', 'max' => 2000, 'required' => false, 'col' => 'col-md-6', 'short' => 'Pelaksana'],
        'pelaksanaan' => ['Pelaksanaan komitmen', 'type' => 'textarea', 'max' => 2000, 'required' => false, 'col' => 'col-12', 'rows' => 3, 'short' => 'Pelaksanaan'],
        'statuskomitmen' => ['Status pelaksanaan komitmen', 'type' => 'select', 'options' => self::STATUS, 'required' => false, 'col' => 'col-md-6', 'short' => 'Status',
            'aliases' => ['sudah dipenuhi' => '01', 'dalam proses pemenuhan' => '02', 'belum dipenuhi' => '03', 'belum' => '03']],
    ];
    /** Template: A uraian · B pemberi komitmen · C pelaksanaan · D pelaksana komitmen · E status (kode atau teks). */
    public const IMPORT_COLS = ['uraian', 'pemberikomitmen', 'pelaksanaan', 'pelaksanakomitmen', 'statuskomitmen'];

    /** Kartu komitmen bernomor (gaya Tugas & Tanggung Jawab Direksi). */
    public const CARD = [
        'heading' => 'Komitmen',
        'tag' => 'statuskomitmen',
        'sub' => ['pemberikomitmen', 'pelaksanakomitmen'],
        'body' => ['uraian', 'pelaksanaan'],
        'utama' => ['01'],
        'icon' => 'fa-handshake',
    ];
    public const ROW_APPROVAL = false;
    /** Sinkron periode sebelumnya / impor: uraian + pemberi komitmen yang sama dilewati (sama seperti sebelumnya). */
    public const DUP_KEY = ['uraian', 'pemberikomitmen'];

    /** Dipakai LaporanPdfGcg. */
    public function getDataByKodebprAndPeriode(string $kodebpr, int $periodeId): array
    {
        return $this->rows($kodebpr, $periodeId);
    }
}
