<?php

namespace App\Models;

/**
 * Laporan Transparansi 14 — Kehadiran Anggota Dewan Komisaris (E0702).
 * Satu baris per anggota Dewan Komisaris. Tabel: accdekom2..5 tanpa accdir2, tanpa komut/dirut,
 * kolom keterangan TEXT NOT NULL tanpa default.
 */
class M_kehadirandekom extends M_anggotatransparansi
{
    protected $table = 'kehadirandekom';

    public const SUBKATEGORI = 'Kehadirandekom';
    public const FORM_CODE = 'E0702';
    public const D01_CODE = '082010000000';

    public const UNIQUE = 'nik';
    public const UNIQUE_MSG = 'NIK sudah terdaftar pada periode ini.';
    public const TITLE_FIELD = 'nama';
    public const TAG_FIELD = null;
    public const SUB_FIELD = 'nik';
    public const SUB_FORMAT = 'NIK %s';
    public const BODY_FIELDS = [];
    public const CHIP_FIELDS = ['fisik', 'online', 'persen'];
    public const ORDER_COL = 'id';

    public const HAS_NAMES = false;
    public const HAS_EXTRA_ACC = false;   // accdekom2..5 diisi lewat insertDefaults() (tabel tanpa accdir2)

    public static function fields(): array
    {
        return [
            'nama' => ['col' => 'dekom', 'label' => 'Nama Anggota Dewan Komisaris', 'type' => 'text', 'max' => 50, 'col_md' => 7],
            'nik' => ['col' => 'nik', 'label' => 'NIK', 'type' => 'nik', 'max' => 16, 'col_md' => 5],
            'fisik' => ['col' => 'hadirfisik', 'label' => 'Frekuensi Kehadiran Fisik', 'short' => 'Fisik', 'unit' => ' kali', 'type' => 'number', 'max' => 999, 'col_md' => 4],
            'online' => ['col' => 'hadironline', 'label' => 'Frekuensi Kehadiran Online', 'short' => 'Online', 'unit' => ' kali', 'type' => 'number', 'max' => 999, 'col_md' => 4],
            'persen' => ['col' => 'persen', 'label' => 'Persentase Kehadiran (%)', 'short' => 'Kehadiran', 'unit' => '%', 'type' => 'percent', 'col_md' => 4],
        ];
    }

    protected static function insertDefaults(): array
    {
        return ['is_approved' => 0, 'approved_by' => 0, 'accdekom' => 0, 'accdekom_by' => 0,
            'accdekom2' => 0, 'accdekom2_by' => 0, 'accdekom3' => 0, 'accdekom3_by' => 0,
            'accdekom4' => 0, 'accdekom4_by' => 0, 'accdekom5' => 0, 'accdekom5_by' => 0,
            'keterangan' => ''];
    }

    /** Sinkronisasi: nama & NIK disalin, angka kehadiran dikosongkan (diisi ulang untuk tahun berjalan). */
    public static function syncRow(array $row): array
    {
        return ['hadirfisik' => '0', 'hadironline' => '0', 'persen' => 0] + $row;
    }

    /** D01|082010000000|nik|nama|fisik|online|persen (format lama). */
    public function txtLine(array $row, callable $clean): string
    {
        return 'D01|' . self::D01_CODE . '|' . $clean($row['nik']) . '|' . $clean($row['dekom']) . '|' . $clean($row['hadirfisik'])
            . '|' . $clean($row['hadironline']) . '|' . $clean($row['persen']);
    }
}
