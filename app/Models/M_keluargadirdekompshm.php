<?php

namespace App\Models;

/**
 * Laporan Transparansi 10 — Hubungan Keluarga Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR (E0402).
 * Satu baris per orang. Mendukung "Tidak Ada Data".
 * Tabel: punya accdekom2..5 (+_by) tetapi tanpa accdir2; kolom keterangan TEXT NOT NULL tanpa default.
 */
class M_keluargadirdekompshm extends M_anggotatransparansi
{
    protected $table = 'keluargadirdekompshm';

    public const SUBKATEGORI = 'Keluargadirdekompshm';
    public const FORM_CODE = 'E0402';
    public const D01_CODE = '032010000000';
    public const JABATAN = ['Direksi' => 'Direksi', 'Dekom' => 'Dewan Komisaris', 'PSaham' => 'Pemegang Saham'];

    public const UNIQUE = 'nik';
    public const UNIQUE_MSG = 'NIK sudah terdaftar pada periode ini.';
    public const TITLE_FIELD = 'nama';
    public const TAG_FIELD = 'jabatan';
    public const SUB_FIELD = 'nik';
    public const SUB_FORMAT = 'NIK %s';
    public const BODY_FIELDS = ['hubdir', 'hubdekom', 'hubpshm'];
    public const UTAMA = ['Direksi'];
    public const ORDER_COL = 'jabatan';

    public const HAS_NAMES = false;
    public const HAS_EXTRA_ACC = false;   // accdekom2..5 diisi lewat insertDefaults() (tabel tanpa accdir2)
    public const NODATA = true;

    public static function fields(): array
    {
        $hub = static fn(string $col, string $label, string $short) => [
            'col' => $col, 'label' => $label, 'short' => $short, 'type' => 'text', 'max' => 50, 'default' => 'Tidak ada',
        ];
        return [
            'nama' => ['col' => 'nama', 'label' => 'Nama', 'type' => 'text', 'max' => 100, 'col_md' => 7],
            'nik' => ['col' => 'nik', 'label' => 'NIK', 'type' => 'nik', 'max' => 16, 'col_md' => 5],
            'jabatan' => ['col' => 'jabatan', 'label' => 'Jabatan', 'type' => 'select', 'options' => self::JABATAN],
            'hubdir' => $hub('hubkeldir', 'Hubungan Keluarga dengan Anggota Direksi Lain', 'Dengan Direksi lain'),
            'hubdekom' => $hub('hubkeldekom', 'Hubungan Keluarga dengan Anggota Dewan Komisaris Lain', 'Dengan Dewan Komisaris lain'),
            'hubpshm' => $hub('hubkelpshm', 'Hubungan Keluarga dengan Pemegang Saham Lain', 'Dengan Pemegang Saham lain'),
        ];
    }

    /** Kolom NOT NULL tanpa default di tabel ini (aman untuk MySQL strict mode). */
    protected static function insertDefaults(): array
    {
        return ['is_approved' => 0, 'approved_by' => 0, 'accdekom' => 0, 'accdekom_by' => 0,
            'accdekom2' => 0, 'accdekom2_by' => 0, 'accdekom3' => 0, 'accdekom3_by' => 0,
            'accdekom4' => 0, 'accdekom4_by' => 0, 'accdekom5' => 0, 'accdekom5_by' => 0,
            'keterangan' => ''];
    }

    /** Baris "Tidak Ada Data" (sama dengan versi lama). */
    protected static function nodataValues(): array
    {
        return ['nama' => '', 'nik' => '', 'jabatan' => 'Direksi', 'hubkeldir' => '', 'hubkeldekom' => '', 'hubkelpshm' => ''];
    }

    /** D01|032010000000|nik|hubkeldir|hubkeldekom|hubkelpshm (format lama). */
    public function txtLine(array $row, callable $clean): string
    {
        return 'D01|' . self::D01_CODE . '|' . $clean($row['nik']) . '|' . $clean($row['hubkeldir'])
            . '|' . $clean($row['hubkeldekom']) . '|' . $clean($row['hubkelpshm']);
    }

    /** Tidak ada data: baris default (format lama). */
    public function txtEmptyLine(array $ctx = []): ?string
    {
        return 'D01|' . self::D01_CODE . '|0| | | ';
    }
}
