<?php

namespace App\Models;

/** Laporan Direktur YMF Kepatuhan — 2. Ketentuan Internal yang Dimiliki BPR (form K1400). */
class M_ketentuanbpr extends M_semesterlist
{
    protected $table = 'ketentuanbpr';
    protected $allowedFields = [
        'user_id', 'kodebpr', 'periode_id', 'tahun', 'semester',
        'judulketentuan', 'nomorketentuan', 'tanggalberlaku', 'keterangan',
        'fullname', 'accdir2', 'accdir2_by', 'accdir2_at', 'is_null_record',
    ];

    public const FIELDS = [
        'judulketentuan' => ['Judul ketentuan', 'type' => 'textarea', 'max' => 2000, 'col' => 'col-12', 'rows' => 2, 'short' => 'Judul'],
        'nomorketentuan' => ['Nomor ketentuan internal', 'type' => 'text', 'max' => 1000, 'col' => 'col-md-7', 'short' => 'Nomor'],
        'tanggalberlaku' => ['Tanggal pemberlakuan', 'type' => 'date', 'col' => 'col-md-5', 'short' => 'Berlaku'],
        'keterangan' => ['Keterangan', 'type' => 'textarea', 'max' => 2000, 'col' => 'col-12', 'rows' => 4],
    ];
    /** Template: A judul · B nomor · C tanggal pemberlakuan · D keterangan. */
    public const IMPORT_COLS = ['judulketentuan', 'nomorketentuan', 'tanggalberlaku', 'keterangan'];
    public const IMPORT_MAX_ROWS = 400;

    /** Kartu dokumen (gaya Tugas & Tanggung Jawab Direksi). */
    public const CARD = [
        'title' => 'judulketentuan',
        'tag' => 'nomorketentuan',
        'sub' => ['tanggalberlaku'],
        'body' => ['keterangan'],
        'icon' => 'fa-file-contract',
    ];
    /** Persetujuan Direktur Utama untuk seluruh ketentuan sekaligus. */
    public const ROW_APPROVAL = false;
    /** Sinkron periode sebelumnya: ketentuan dengan nomor yang sama dilewati. */
    public const DUP_KEY = ['nomorketentuan'];

    /** Dipakai LaporanPdfGcg. */
    public function getDataByKodebprAndPeriode(string $kodebpr, int $periodeId): array
    {
        return $this->rows($kodebpr, $periodeId);
    }
}
