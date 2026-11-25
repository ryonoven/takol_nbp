<?php

namespace App\Models;

/**
 * Laporan Transparansi 5 — Struktur, Keanggotaan, Keahlian, dan Independensi Anggota Komite (E0204).
 * Satu baris per anggota komite. Mendukung "Tidak Ada Data" (is_null_record = 1).
 */
class M_strukturkomite extends M_anggotatransparansi
{
    protected $table = 'strukturkomite';

    public const SUBKATEGORI = 'Strukturkomite';
    public const FORM_CODE = 'E0204';
    public const D01_CODE = '013201000000';

    public const JABATAN = ['00' => '00. Tidak menjabat', '01' => '01. Ketua', '02' => '02. Anggota'];
    public const INDEPENDEN = ['1' => 'Pihak independen', '2' => 'Bukan pihak independen'];
    /** key field => [kolom, label, label singkat chip] */
    private const KOMITE = [
        'audit' => ['jbtaudit', 'Jabatan di Komite Audit', 'Audit'],
        'pantau' => ['jbtpantauresiko', 'Jabatan di Komite Pemantau Risiko', 'Pemantau Risiko'],
        'remunerasi' => ['jbtremunerasi', 'Jabatan di Komite Remunerasi & Nominasi', 'Remunerasi & Nominasi'],
        'manrisk' => ['jbtmanrisk', 'Jabatan di Komite Manajemen Risiko', 'Manajemen Risiko'],
        'lain' => ['jbtlain', 'Jabatan di Komite Lainnya', 'Lainnya'],
    ];

    public const UNIQUE = 'nik';
    public const UNIQUE_MSG = 'NIK sudah terdaftar pada periode ini.';
    public const TITLE_FIELD = 'nama';
    public const TAG_FIELD = 'independen';
    public const SUB_FIELD = 'nik';
    public const SUB_FORMAT = 'NIK %s';
    public const BODY_FIELDS = ['keahlian'];
    public const CHIP_FIELDS = ['audit', 'pantau', 'remunerasi', 'manrisk', 'lain'];
    public const CHIP_SKIP = ['00'];
    public const UTAMA = ['1'];
    public const ORDER_COL = 'id';

    public const HAS_NAMES = false;
    public const NODATA = true;

    public static function fields(): array
    {
        $f = [
            'nama' => ['col' => 'anggotakomite', 'label' => 'Nama Anggota Komite', 'type' => 'text', 'max' => 250, 'col_md' => 7],
            'nik' => ['col' => 'nikkomite', 'label' => 'NIK', 'type' => 'nik', 'max' => 16, 'col_md' => 5],
            'keahlian' => ['col' => 'keahlian', 'label' => 'Keahlian', 'type' => 'textarea', 'max' => 4000],
        ];
        foreach (self::KOMITE as $k => [$col, $label, $short]) {
            $f[$k] = ['col' => $col, 'label' => $label, 'short' => $short, 'type' => 'select', 'options' => self::JABATAN, 'default' => '00', 'col_md' => 6];
        }
        $f['independen'] = ['col' => 'independen', 'label' => 'Independensi', 'type' => 'select', 'options' => self::INDEPENDEN, 'col_md' => 6];
        return $f;
    }

    /** Anggota komite harus menjabat minimal di satu komite. */
    public static function validateRow(array $data): array
    {
        foreach (self::KOMITE as [$col]) {
            if (($data[$col] ?? '00') !== '00') {
                return [];
            }
        }
        return ['audit' => 'Pilih jabatan minimal di satu komite.'];
    }

    /** Kolom tabel NOT NULL: baris "Tidak Ada Data" diisi nilai kosong (sama dengan versi lama). */
    protected static function nodataValues(): array
    {
        $v = ['anggotakomite' => '', 'nikkomite' => '', 'keahlian' => '', 'independen' => '0'];
        foreach (self::KOMITE as [$col]) {
            $v[$col] = '00';
        }
        return $v;
    }

    /** D01|013201000000|nama|nik|keahlian|audit|pantau|remunerasi|manrisk|lain|independen (format lama). */
    public function txtLine(array $row, callable $clean): string
    {
        $cols = ['anggotakomite', 'nikkomite', 'keahlian', 'jbtaudit', 'jbtpantauresiko', 'jbtremunerasi', 'jbtmanrisk', 'jbtlain', 'independen'];
        return 'D01|' . self::D01_CODE . '|' . implode('|', array_map(static fn($c) => $clean($row[$c] ?? ''), $cols));
    }

    /** Tidak ada anggota komite: baris default (format lama). */
    public function txtEmptyLine(array $ctx = []): ?string
    {
        return 'D01|' . self::D01_CODE . '| |0| |00|00|00|00|00|2';
    }
}
