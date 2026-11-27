<?php

namespace App\Models;

/** Laporan Direktur YMF Kepatuhan — 3. Pelaksanaan Sosialisasi dan Pelatihan Berkelanjutan (form K1500). */
class M_sosialisasipelatihan extends M_semesterlist
{
    protected $table = 'sosialisasipelatihan';
    protected $allowedFields = [
        'user_id', 'kodebpr', 'periode_id', 'tahun', 'semester',
        'topik', 'tanggal', 'pihakpelaksana', 'namapihak', 'kategoripeserta', 'keteranganpeserta', 'jumlahpeserta', 'uraian',
        'accdir2', 'accdir2_by', 'accdir2_at', 'is_null_record',
    ];

    public const PIHAK = ['01' => 'Internal BPR', '02' => 'Eksternal BPR', '03' => 'Kolaborasi dengan Lembaga Lain'];
    public const KATEGORI = ['01' => 'Seluruh Pegawai', '02' => 'Direksi dan/atau Dewan Komisaris', '03' => 'Satuan Kerja atau Pejabat Eksekutif', '04' => 'Komite'];

    public const FIELDS = [
        'topik' => ['Topik sosialisasi/pelatihan', 'type' => 'text', 'max' => 255, 'col' => 'col-12', 'short' => 'Topik'],
        'tanggal' => ['Tanggal pelaksanaan', 'type' => 'date', 'col' => 'col-md-6', 'short' => 'Tanggal'],
        'jumlahpeserta' => ['Jumlah peserta', 'type' => 'number', 'min' => 1, 'maxv' => 9999, 'col' => 'col-md-6', 'short' => 'Peserta', 'unit' => ' orang'],
        'pihakpelaksana' => ['Pihak pelaksana', 'type' => 'select', 'options' => self::PIHAK, 'col' => 'col-md-6', 'short' => 'Pelaksana',
            'aliases' => ['internal bpr/bprs' => '01', 'internal bprs' => '01', 'eksternal bpr/bprs' => '02', 'eksternal bprs' => '02']],
        'namapihak' => ['Nama pihak pelaksana', 'type' => 'text', 'max' => 255, 'col' => 'col-md-6', 'short' => 'Penyelenggara'],
        'kategoripeserta' => ['Kategori peserta', 'type' => 'select', 'options' => self::KATEGORI, 'col' => 'col-md-6', 'short' => 'Kategori',
            'aliases' => ['direksi, dewan komisaris, dan/atau dps' => '02', 'direksi, dewan komisaris, dan atau dps' => '02']],
        // Hanya untuk kategori Satuan Kerja/Pejabat Eksekutif (03) atau Komite (04) — sesuai aturan form lama
        'keteranganpeserta' => ['Keterangan peserta (nama satuan kerja/komite)', 'type' => 'text', 'max' => 1000, 'col' => 'col-md-6', 'short' => 'Unit peserta',
            'show_if' => ['kategoripeserta', ['03', '04']]],
        'uraian' => ['Uraian kegiatan', 'type' => 'textarea', 'max' => 4000, 'required' => false, 'col' => 'col-12', 'rows' => 4],
    ];
    /** Template: A topik · B tanggal · C pihak pelaksana · D nama pihak · E kategori · F jumlah · G keterangan peserta · H uraian. */
    public const IMPORT_COLS = ['topik', 'tanggal', 'pihakpelaksana', 'namapihak', 'kategoripeserta', 'jumlahpeserta', 'keteranganpeserta', 'uraian'];

    /** Kartu kegiatan (gaya Tugas & Tanggung Jawab Direksi). */
    public const CARD = [
        'title' => 'topik',
        'tag' => 'pihakpelaksana',
        'sub' => ['tanggal', 'namapihak'],
        'chips' => ['kategoripeserta', 'keteranganpeserta', 'jumlahpeserta'],
        'body' => ['uraian'],
        'icon' => 'fa-chalkboard-teacher',
    ];
    public const ROW_APPROVAL = false;
    /** Sinkron periode sebelumnya: kegiatan dengan topik + tanggal yang sama dilewati (sama seperti sebelumnya). */
    public const DUP_KEY = ['topik', 'tanggal'];

    /** Baris "Tidak ada data": seluruh field kosong (kolom nullable). */
    public static function insertDefaults(): array
    {
        return array_diff_key(parent::insertDefaults(), ['fullname' => 1]); // tabel tanpa kolom fullname
    }

    /** Dipakai LaporanPdfGcg. */
    public function getDataByKodebprAndPeriode(string $kodebpr, int $periodeId): array
    {
        return $this->rows($kodebpr, $periodeId);
    }
}
