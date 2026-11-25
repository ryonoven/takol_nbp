<?php

namespace App\Models;

/**
 * Laporan Transparansi 9 — Hubungan Keuangan Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR (E0401).
 * Satu baris per orang. Mendukung "Tidak Ada Data". Tabel tanpa kolom accdekom2..5 / accdir2.
 */
class M_keuangandirdekompshm extends M_anggotatransparansi
{
    protected $table = 'keuangandirdekompshm';

    public const SUBKATEGORI = 'Keuangandirdekompshm';
    public const FORM_CODE = 'E0401';
    public const D01_CODE = '031010000000';
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
    public const HAS_EXTRA_ACC = false;
    public const NODATA = true;

    public static function fields(): array
    {
        $hub = static fn(string $col, string $label, string $short) => [
            'col' => $col, 'label' => $label, 'short' => $short, 'type' => 'text', 'max' => 50, 'default' => 'Tidak ada',
        ];
        return [
            'nama' => ['col' => 'nama', 'label' => 'Nama', 'type' => 'text', 'max' => 150, 'col_md' => 7],
            'nik' => ['col' => 'nik', 'label' => 'NIK', 'type' => 'nik', 'max' => 16, 'col_md' => 5],
            'jabatan' => ['col' => 'jabatan', 'label' => 'Jabatan', 'type' => 'select', 'options' => self::JABATAN],
            'hubdir' => $hub('hubdir', 'Hubungan Keuangan dengan Anggota Direksi Lain', 'Dengan Direksi lain'),
            'hubdekom' => $hub('hubdekom', 'Hubungan Keuangan dengan Anggota Dewan Komisaris Lain', 'Dengan Dewan Komisaris lain'),
            'hubpshm' => $hub('hubpshm', 'Hubungan Keuangan dengan Pemegang Saham Lain', 'Dengan Pemegang Saham lain'),
        ];
    }

    /** Baris "Tidak Ada Data" (sama dengan versi lama). */
    protected static function nodataValues(): array
    {
        return ['nama' => '', 'nik' => '', 'jabatan' => 'Direksi', 'hubdir' => '', 'hubdekom' => '', 'hubpshm' => ''];
    }

    /** D01|031010000000|nik|hubdir|hubdekom|hubpshm (format lama). */
    public function txtLine(array $row, callable $clean): string
    {
        return 'D01|' . self::D01_CODE . '|' . $clean($row['nik']) . '|' . $clean($row['hubdir'])
            . '|' . $clean($row['hubdekom']) . '|' . $clean($row['hubpshm']);
    }

    /** Tidak ada data: baris default (format lama). */
    public function txtEmptyLine(array $ctx = []): ?string
    {
        return 'D01|' . self::D01_CODE . '|0| | | ';
    }
}
