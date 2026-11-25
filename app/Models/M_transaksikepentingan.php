<?php

namespace App\Models;

/**
 * Laporan Transparansi 17 — Transaksi yang Mengandung Benturan Kepentingan (E1000).
 * Satu baris per transaksi. Mendukung "Tidak Ada Data". Tabel: accdekom2..5 tanpa accdir2, tanpa komut/dirut.
 */
class M_transaksikepentingan extends M_anggotatransparansi
{
    protected $table = 'transaksikepentingan';

    public const SUBKATEGORI = 'Transaksikepentingan';
    public const FORM_CODE = 'E1000';
    public const D01_CODE = '110100000000';

    public const UNIQUE = null;
    public const TITLE_FIELD = 'nama';
    public const TAG_FIELD = null;
    public const SUB_FIELD = ['jabatan', 'nik'];
    public const SUB_FORMAT = '%s · NIK %s';
    public const EXTRA_LINES = [['Pengambil keputusan: %s (%s) · NIK %s', ['pengambil', 'jbtpengambil', 'nikpengambil']]];
    public const BODY_FIELDS = ['keterangan'];
    public const CHIP_FIELDS = ['jenis', 'nilai'];
    public const ORDER_COL = 'id';
    public const TXT_EMPTY_F01 = true;

    public const HAS_NAMES = false;
    public const HAS_EXTRA_ACC = false;   // accdekom2..5 diisi lewat insertDefaults() (tabel tanpa accdir2)
    public const NODATA = true;

    public static function fields(): array
    {
        return [
            'nama' => ['col' => 'namapihakbenturan', 'label' => 'Nama', 'type' => 'text', 'max' => 50, 'col_md' => 7,
                'section' => 'Pihak yang Memiliki Benturan Kepentingan'],
            'jabatan' => ['col' => 'jbtbenturan', 'label' => 'Jabatan', 'type' => 'text', 'max' => 50, 'col_md' => 5],
            'nik' => ['col' => 'nikbenturan', 'label' => 'NIK', 'type' => 'nik', 'max' => 16, 'col_md' => 6],
            'pengambil' => ['col' => 'pengambilkeputusan', 'label' => 'Nama', 'type' => 'text', 'max' => 50, 'col_md' => 7,
                'section' => 'Pengambil Keputusan'],
            'jbtpengambil' => ['col' => 'jbtpengambilkeputusan', 'label' => 'Jabatan', 'type' => 'text', 'max' => 50, 'col_md' => 5],
            'nikpengambil' => ['col' => 'nikpengambilkeputusan', 'label' => 'NIK', 'type' => 'nik', 'max' => 16, 'col_md' => 6],
            'jenis' => ['col' => 'jenistransaksi', 'label' => 'Jenis Transaksi', 'short' => 'Jenis', 'type' => 'text', 'max' => 100, 'col_md' => 7,
                'section' => 'Detail Transaksi'],
            'nilai' => ['col' => 'nilaitransaksi', 'label' => 'Nilai Transaksi (Rp)', 'short' => 'Nilai', 'type' => 'rupiah', 'min' => 1, 'col_md' => 5],
            'keterangan' => ['col' => 'tindakbenturan', 'label' => 'Keterangan', 'type' => 'textarea', 'max' => 500],
        ];
    }

    protected static function insertDefaults(): array
    {
        return ['is_approved' => 0, 'approved_by' => 0, 'accdekom' => 0, 'accdekom_by' => 0,
            'accdekom2' => 0, 'accdekom2_by' => 0, 'accdekom3' => 0, 'accdekom3_by' => 0,
            'accdekom4' => 0, 'accdekom4_by' => 0, 'accdekom5' => 0, 'accdekom5_by' => 0];
    }

    /** Baris "Tidak Ada Data" (sama dengan versi lama). */
    protected static function nodataValues(): array
    {
        return ['namapihakbenturan' => '', 'jbtbenturan' => '', 'nikbenturan' => '', 'pengambilkeputusan' => '',
            'jbtpengambilkeputusan' => '', 'nikpengambilkeputusan' => '', 'jenistransaksi' => '', 'nilaitransaksi' => 0, 'tindakbenturan' => ''];
    }

    /** D01|110100000000|nama|jabatan|nik|pengambil|jabatan|nik|jenis|nilai|keterangan (format lama). */
    public function txtLine(array $row, callable $clean): string
    {
        $cols = ['namapihakbenturan', 'jbtbenturan', 'nikbenturan', 'pengambilkeputusan', 'jbtpengambilkeputusan',
            'nikpengambilkeputusan', 'jenistransaksi', 'nilaitransaksi', 'tindakbenturan'];
        return 'D01|' . self::D01_CODE . '|' . implode('|', array_map(static fn($c) => $clean($row[$c] ?? ''), $cols));
    }

    /** Tidak ada transaksi: baris default (format lama). */
    public function txtEmptyLine(array $ctx = []): ?string
    {
        return 'D01|' . self::D01_CODE . '| | |0| | |0| |0| ';
    }
}
