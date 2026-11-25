<?php

namespace App\Models;

/** Laporan Transparansi 2 — Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi (E0201). */
class M_tgjwbdir extends M_anggotatransparansi
{
    protected $table = 'tgjwbdir';

    public const SUBKATEGORI = 'Tgjwbdir';
    public const FORM_CODE = 'E0201';
    public const D01_CODE = '011000000000';
    public const JABATAN = [
        '0100' => 'Direktur Utama',
        '0101' => 'Direktur Utama yang menjalankan fungsi kepatuhan',
        '0200' => 'Direktur',
        '0201' => 'Direktur yang menjalankan fungsi kepatuhan',
    ];

    public const UNIQUE = 'nik';
    public const UNIQUE_MSG = 'NIK sudah terdaftar pada periode ini.';
    public const TITLE_FIELD = 'nama';
    public const TAG_FIELD = 'jabatan';
    public const SUB_FIELD = 'nik';
    public const SUB_FORMAT = 'NIK %s';
    public const BODY_FIELDS = ['tugas'];
    public const UTAMA = ['0100', '0101'];
    public const ORDER_COL = 'jabatan';

    public static function fields(): array
    {
        return self::personFields('direksi', 'Nama Direksi', 'tugastgjwbdir', self::JABATAN);
    }
}
