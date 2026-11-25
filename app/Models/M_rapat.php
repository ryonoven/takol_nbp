<?php

namespace App\Models;

/**
 * Laporan Transparansi 13 — Pelaksanaan Rapat dalam 1 (satu) tahun (E0701).
 * Satu baris per rapat. Tabel: accdekom2..5 (+_by) tanpa accdir2, tanpa kolom komut/dirut.
 */
class M_rapat extends M_anggotatransparansi
{
    protected $table = 'rapat';

    public const SUBKATEGORI = 'Rapat';
    public const FORM_CODE = 'E0701';
    public const D01_CODE = '081010000000';

    public const UNIQUE = null;   // beberapa rapat bisa pada tanggal yang sama
    public const TITLE_FIELD = 'tanggal';
    public const TAG_FIELD = null;
    public const SUB_FIELD = 'peserta';
    public const SUB_FORMAT = '%s peserta';
    public const BODY_FIELDS = ['topik'];
    public const ORDER_COL = 'tanggalrapat';

    public const HAS_NAMES = false;
    public const HAS_EXTRA_ACC = false;   // accdekom2..5 diisi lewat insertDefaults() (tabel tanpa accdir2)

    public static function fields(): array
    {
        return [
            'tanggal' => ['col' => 'tanggalrapat', 'label' => 'Tanggal Pelaksanaan Rapat', 'type' => 'date', 'col_md' => 6],
            'peserta' => ['col' => 'jumlahpeserta', 'label' => 'Jumlah Peserta Rapat', 'type' => 'number', 'min' => 1, 'max' => 9999, 'col_md' => 6],
            'topik' => ['col' => 'topikrapat', 'label' => 'Topik Rapat', 'type' => 'textarea', 'max' => 4000],
        ];
    }

    protected static function insertDefaults(): array
    {
        return ['is_approved' => 0, 'approved_by' => 0, 'accdekom' => 0, 'accdekom_by' => 0,
            'accdekom2' => 0, 'accdekom2_by' => 0, 'accdekom3' => 0, 'accdekom3_by' => 0,
            'accdekom4' => 0, 'accdekom4_by' => 0, 'accdekom5' => 0, 'accdekom5_by' => 0];
    }

    /** Sinkronisasi: tanggal rapat tahun lalu digeser +1 tahun (silakan disesuaikan setelahnya). */
    public static function syncRow(array $row): array
    {
        $ts = !empty($row['tanggalrapat']) ? strtotime($row['tanggalrapat'] . ' +1 year') : false;
        if ($ts) {
            $row['tanggalrapat'] = date('Y-m-d', $ts);
        }
        return $row;
    }

    /** D01|081010000000|YYYYMMDD|jumlah peserta|topik (format lama). */
    public function txtLine(array $row, callable $clean): string
    {
        $ts = !empty($row['tanggalrapat']) ? strtotime($row['tanggalrapat']) : false;
        return 'D01|' . self::D01_CODE . '|' . ($ts ? date('Ymd', $ts) : '') . '|' . $clean($row['jumlahpeserta']) . '|' . $clean($row['topikrapat']);
    }
}
