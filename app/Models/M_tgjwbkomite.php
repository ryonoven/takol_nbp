<?php

namespace App\Models;

/**
 * Laporan Transparansi 4 — Tugas, Tanggung Jawab, Program Kerja, dan Realisasi Program Kerja Komite (E0203).
 * Satu baris per komite. Mendukung "Tidak Ada Data" (is_null_record = 1) bila BPR tidak memiliki komite.
 */
class M_tgjwbkomite extends M_anggotatransparansi
{
    protected $table = 'tgjwbkomite';

    public const SUBKATEGORI = 'Tgjwbkomite';
    public const FORM_CODE = 'E0203';
    public const D01_CODE = '013301000000';
    public const KOMITE = [
        '01' => 'Komite Audit',
        '02' => 'Komite Pemantau Risiko',
        '03' => 'Komite Remunerasi dan Nominasi',
        '04' => 'Komite Manajemen Risiko',
        '05' => 'Komite Lainnya',
    ];

    /** Satu baris per jenis komite, kecuali "Komite Lainnya" yang boleh lebih dari satu. */
    public const UNIQUE = 'komite';
    public const UNIQUE_EXCEPT = ['05'];
    public const UNIQUE_MSG = 'Komite ini sudah diisi pada periode ini.';
    public const TITLE_FIELD = 'komite';
    public const TAG_FIELD = null;
    public const SUB_FIELD = 'jumlahrapat';
    public const SUB_FORMAT = '%s kali rapat dalam setahun';
    public const BODY_FIELDS = ['tugas', 'proker', 'hasil'];
    public const ORDER_COL = 'komite';

    public const HAS_NAMES = false;
    public const NODATA = true;

    public static function fields(): array
    {
        return [
            'komite' => ['col' => 'komite', 'label' => 'Komite', 'type' => 'select', 'options' => self::KOMITE, 'col_md' => 8],
            'jumlahrapat' => ['col' => 'jumlahrapat', 'label' => 'Jumlah Rapat', 'type' => 'number', 'max' => 999, 'col_md' => 4],
            'tugas' => ['col' => 'tugastgjwbkomite', 'label' => 'Tugas dan Tanggung Jawab', 'type' => 'textarea', 'max' => 4000],
            'proker' => ['col' => 'prokerkomite', 'label' => 'Program Kerja Komite', 'type' => 'textarea', 'max' => 4000],
            'hasil' => ['col' => 'hasilprokerkomite', 'label' => 'Realisasi Program Kerja Komite', 'type' => 'textarea', 'max' => 4000],
        ];
    }

    /** D01|013301000000|komite|tugas|proker|realisasi|jumlahrapat (format lama). */
    public function txtLine(array $row, callable $clean): string
    {
        return 'D01|' . self::D01_CODE . '|' . $clean($row['komite']) . '|' . $clean($row['tugastgjwbkomite'])
            . '|' . $clean($row['prokerkomite']) . '|' . $clean($row['hasilprokerkomite']) . '|' . $clean($row['jumlahrapat']);
    }

    /** Tidak ada komite: baris default "05" (format lama). */
    public function txtEmptyLine(array $ctx = []): ?string
    {
        return 'D01|' . self::D01_CODE . '|05| | | |0';
    }
}
