<?php

namespace App\Models;

/** Laporan Direktur YMF Kepatuhan — 4. Penerapan Budaya Kepatuhan dan Fungsi Kepatuhan (form K1600). */
class M_budayakepatuhan extends M_semesterlist
{
    protected $table = 'budayakepatuhan';
    protected $allowedFields = [
        'user_id', 'kodebpr', 'periode_id', 'tahun', 'semester',
        'pengawasandekom', 'tindaklanjut', 'keterangan',
        'accdir2', 'accdir2_by', 'accdir2_at', 'is_null_record',
    ];

    public const FIELDS = [
        'pengawasandekom' => ['Pengawasan Dewan Komisaris', 'type' => 'textarea', 'max' => 2000, 'col' => 'col-12', 'rows' => 4, 'short' => 'Pengawasan Dewan Komisaris'],
        'tindaklanjut' => ['Tindak lanjut', 'type' => 'textarea', 'max' => 2000, 'required' => false, 'col' => 'col-12', 'rows' => 4, 'short' => 'Tindak lanjut'],
        'keterangan' => ['Keterangan', 'type' => 'textarea', 'max' => 2000, 'required' => false, 'col' => 'col-12', 'rows' => 3, 'short' => 'Keterangan'],
    ];
    /** Template: A pengawasan dewan komisaris · B tindak lanjut · C keterangan. */
    public const IMPORT_COLS = ['pengawasandekom', 'tindaklanjut', 'keterangan'];

    /** Kartu bernomor dengan tiga blok teks (gaya Tugas & Tanggung Jawab Direksi). */
    public const CARD = [
        'heading' => 'Pengawasan',
        'body' => ['pengawasandekom', 'tindaklanjut', 'keterangan'],
        'icon' => 'fa-user-shield',
    ];
    public const ROW_APPROVAL = false;
    /** Sinkron periode sebelumnya / impor: arahan pengawasan yang sama dilewati. */
    public const DUP_KEY = ['pengawasandekom'];

    /** Dipakai LaporanPdfGcg. */
    public function getDataByKodebprAndPeriode(string $kodebpr, int $periodeId): array
    {
        return $this->rows($kodebpr, $periodeId);
    }
}
