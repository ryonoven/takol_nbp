<?php

namespace App\Models;

/**
 * Laporan Transparansi 6 — Kepemilikan Saham Anggota Direksi dan Dewan Komisaris pada BPR.
 * Satu baris per pemilik saham. Mendukung "Tidak Ada Data" (is_null_record = 1). Tidak memiliki export TXT.
 */
class M_sahamdirdekom extends M_anggotatransparansi
{
    protected $table = 'sahamdirdekom';

    public const SUBKATEGORI = 'Sahamdirdekom';
    public const FORM_CODE = '';
    public const JABATAN = ['Direksi' => 'Direksi', 'Dekom' => 'Dewan Komisaris'];

    public const UNIQUE = 'nik';
    public const UNIQUE_MSG = 'NIK sudah terdaftar pada periode ini.';
    public const TITLE_FIELD = 'nama';
    public const TAG_FIELD = 'jabatan';
    public const SUB_FIELD = ['nik', 'persen'];
    public const SUB_FORMAT = 'NIK %s · Kepemilikan saham %s%';
    public const BODY_FIELDS = [];
    public const UTAMA = ['Direksi'];
    public const ORDER_COL = 'jabatan';

    public const HAS_NAMES = false;
    public const NODATA = true;

    public static function fields(): array
    {
        return [
            'nama' => ['col' => 'nama', 'label' => 'Nama', 'type' => 'text', 'max' => 255, 'col_md' => 7],
            'nik' => ['col' => 'nik', 'label' => 'NIK', 'type' => 'nik', 'max' => 16, 'col_md' => 5],
            'jabatan' => ['col' => 'jabatan', 'label' => 'Jabatan', 'type' => 'select', 'options' => self::JABATAN, 'col_md' => 7],
            'persen' => ['col' => 'persensaham', 'label' => 'Persentase Saham (%)', 'type' => 'percent', 'col_md' => 5],
        ];
    }

    /** Kolom nama / jabatan NOT NULL: baris "Tidak Ada Data" diisi nilai kosong (sama dengan versi lama). */
    protected static function nodataValues(): array
    {
        return ['nama' => '', 'nik' => '', 'jabatan' => 'Direksi', 'persensaham' => '0'];
    }
}
