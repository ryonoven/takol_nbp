<?php

namespace App\Models;

/** Laporan Transparansi 3 — Pelaksanaan Tugas dan Tanggung Jawab Anggota Dewan Komisaris (E0202). */
class M_tgjwbdekom extends M_anggotatransparansi
{
    protected $table = 'tgjwbdekom';

    public const SUBKATEGORI = 'Tgjwbdekom';
    public const FORM_CODE = 'E0202';
    public const D01_CODE = '012010000000';
    public const JABATAN = [
        '0100' => 'Komisaris Utama',
        '0101' => 'Komisaris Utama dan Independen',
        '0200' => 'Komisaris',
        '0201' => 'Komisaris dan Independen',
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
        return self::personFields('dekom', 'Nama Dewan Komisaris', 'tugastgjwbdekom', self::JABATAN);
    }
}
