<?php

namespace App\Models;

/**
 * Laporan Transparansi 8 — Kepemilikan Saham Anggota Direksi dan Dewan Komisaris pada Perusahaan Lain (E0303).
 * Satu baris per kepemilikan. Mendukung "Tidak Ada Data". Tabel ini tidak memiliki kolom fullname.
 * Catatan DB: persensaham harus DOUBLE(5,2) (0 – 100,00); versi lama DOUBLE(3,2) hanya menampung s.d. 9,99.
 */
class M_shmdirdekomlain extends M_anggotatransparansi
{
    protected $table = 'shmdirdekomlain';

    public const SUBKATEGORI = 'Shmdirdekomlain';
    public const FORM_CODE = 'E0303';
    public const D01_CODE = '022010000000';
    public const JABATAN = ['Direksi' => 'Direksi', 'Dekom' => 'Dewan Komisaris'];
    public const JENIS = ['Perusahaan Lain' => 'Perusahaan Lain', 'Bank Lain' => 'Bank Lain'];

    public const UNIQUE = null;   // satu orang boleh memiliki saham di beberapa perusahaan
    public const TITLE_FIELD = 'nama';
    public const TAG_FIELD = 'jabatan';
    public const SUB_FIELD = 'nik';
    public const SUB_FORMAT = 'NIK %s';
    public const BODY_FIELDS = ['perusahaan'];
    public const CHIP_FIELDS = ['jenis', 'kodebank', 'persen'];
    public const CHIP_SKIP = [''];
    public const UTAMA = ['Direksi'];
    public const ORDER_COL = 'jabatan';

    public const HAS_NAMES = false;
    public const HAS_FULLNAME = false;
    public const NODATA = true;

    public static function fields(): array
    {
        return [
            'nama' => ['col' => 'nama', 'label' => 'Nama', 'type' => 'text', 'max' => 100, 'col_md' => 7],
            'nik' => ['col' => 'nik', 'label' => 'NIK', 'type' => 'nik', 'max' => 16, 'col_md' => 5],
            'jabatan' => ['col' => 'jabatan', 'label' => 'Jabatan', 'type' => 'select', 'options' => self::JABATAN, 'col_md' => 6],
            'jenis' => ['col' => 'jenisperusahaan', 'label' => 'Jenis Perusahaan', 'short' => 'Jenis', 'type' => 'select', 'options' => self::JENIS, 'default' => 'Perusahaan Lain', 'col_md' => 6],
            'kodebank' => ['col' => 'kodebank', 'label' => 'Sandi / Kode Bank', 'short' => 'Kode bank', 'type' => 'text', 'max' => 25, 'col_md' => 6, 'showIf' => ['jenis', 'Bank Lain']],
            'perusahaan' => ['col' => 'perusahaan', 'label' => 'Nama Perusahaan / Bank', 'type' => 'text', 'max' => 200],
            'persen' => ['col' => 'persensaham', 'label' => 'Persentase Kepemilikan (%)', 'short' => 'Saham', 'unit' => '%', 'type' => 'percent', 'col_md' => 6],
        ];
    }

    /** Baris "Tidak Ada Data" (sama dengan versi lama). */
    protected static function nodataValues(): array
    {
        return ['nama' => '', 'nik' => '', 'jabatan' => 'Direksi', 'jenisperusahaan' => 'Perusahaan Lain', 'kodebank' => '', 'perusahaan' => '', 'persensaham' => 0];
    }

    /** D01|022010000000|nik|nama|kodebank|perusahaan|persen (format lama). */
    public function txtLine(array $row, callable $clean): string
    {
        return 'D01|' . self::D01_CODE . '|' . $clean($row['nik']) . '|' . $clean($row['nama']) . '|' . $clean($row['kodebank'])
            . '|' . $clean($row['perusahaan']) . '|' . $clean($row['persensaham']);
    }

    /** Tidak ada kepemilikan: baris default berisi sandi BPR sendiri (format lama). */
    public function txtEmptyLine(array $ctx = []): ?string
    {
        return 'D01|' . self::D01_CODE . '|0| |' . ($ctx['sandibpr'] ?? '') . '| |1.00';
    }
}
