<?php

namespace App\Models;

/**
 * Laporan Transparansi 7 — Kepemilikan Saham Anggota Direksi, Dewan Komisaris, dan Pemegang Saham
 * pada Kelompok Usaha BPR (E0302). Satu baris per kepemilikan. Mendukung "Tidak Ada Data".
 */
class M_shmusahadirdekom extends M_anggotatransparansi
{
    protected $table = 'shmusahadirdekom';

    public const SUBKATEGORI = 'Shmusahadirdekom';
    public const FORM_CODE = 'E0302';
    public const D01_CODE = '042010000000';
    public const JABATAN = ['Direksi' => 'Direksi', 'Dekom' => 'Dewan Komisaris', 'Psaham' => 'Pemegang Saham'];
    public const JENIS_ID = ['NIK' => 'NIK', 'NIB' => 'NIB'];

    public const UNIQUE = null;   // satu orang boleh memiliki saham di beberapa perusahaan kelompok usaha
    public const TITLE_FIELD = 'nama';
    public const TAG_FIELD = 'jabatan';
    public const SUB_FIELD = ['jenisid', 'nik'];
    public const SUB_FORMAT = '%s %s';
    public const BODY_FIELDS = ['usaha'];
    public const CHIP_FIELDS = ['persen', 'persenlalu'];
    public const UTAMA = ['Direksi'];
    public const ORDER_COL = 'jabatan';

    public const HAS_NAMES = false;
    public const NODATA = true;

    public static function fields(): array
    {
        return [
            'nama' => ['col' => 'nama', 'label' => 'Nama', 'type' => 'text', 'max' => 100, 'col_md' => 7],
            'jabatan' => ['col' => 'jabatan', 'label' => 'Jabatan', 'type' => 'select', 'options' => self::JABATAN, 'col_md' => 5],
            'jenisid' => ['col' => 'jenisid', 'label' => 'Jenis Identitas', 'type' => 'select', 'options' => self::JENIS_ID, 'default' => 'NIK', 'col_md' => 4],
            'nik' => ['col' => 'nik', 'label' => 'Nomor Identitas', 'type' => 'idnum', 'typeField' => 'jenisid', 'lengths' => ['NIK' => 16, 'NIB' => 13], 'col_md' => 8],
            'usaha' => ['col' => 'usaha', 'label' => 'Nama Perusahaan Kelompok Usaha BPR', 'type' => 'text', 'max' => 150],
            'persen' => ['col' => 'persensaham', 'label' => 'Persentase Saham (%)', 'short' => 'Saham', 'unit' => '%', 'type' => 'percent', 'col_md' => 6],
            'persenlalu' => ['col' => 'persensahamlalu', 'label' => 'Persentase Saham Tahun Lalu (%)', 'short' => 'Tahun lalu', 'unit' => '%', 'type' => 'percent', 'col_md' => 6],
        ];
    }

    /** Kolom accdekom2_by ... accdir2_by di tabel ini bertipe DATETIME (punya default) -> jangan diisi 0. */
    protected static function insertDefaults(): array
    {
        return ['is_approved' => 0, 'approved_by' => 0, 'accdekom' => 0, 'accdekom_by' => 0,
            'accdekom2' => 0, 'accdekom3' => 0, 'accdekom4' => 0, 'accdekom5' => 0, 'accdir2' => 0];
    }

    /** Baris "Tidak Ada Data" (sama dengan versi lama). */
    protected static function nodataValues(): array
    {
        return ['nama' => '', 'jabatan' => 'Direksi', 'jenisid' => 'NIK', 'nik' => '', 'usaha' => null, 'persensaham' => null, 'persensahamlalu' => null];
    }

    /** D01|042010000000|nomor identitas|usaha|persen|persen tahun lalu (format lama). */
    public function txtLine(array $row, callable $clean): string
    {
        return 'D01|' . self::D01_CODE . '|' . $clean($row['nik']) . '|' . $clean($row['usaha'])
            . '|' . $clean($row['persensaham']) . '|' . $clean($row['persensahamlalu']);
    }
}
