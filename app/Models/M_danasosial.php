<?php

namespace App\Models;

/**
 * Laporan Transparansi 18 — Pemberian Dana untuk Kegiatan Sosial dan Kegiatan Politik (E1100).
 * Satu baris per pemberian dana. Mendukung "Tidak Ada Data". Tabel: accdekom2..5 tanpa accdir2;
 * kolom komut/dirut NOT NULL (tidak dipakai, diisi '').
 */
class M_danasosial extends M_anggotatransparansi
{
    protected $table = 'danasosial';

    public const SUBKATEGORI = 'Danasosial';
    public const FORM_CODE = 'E1100';
    public const D01_CODE = '110100000000';
    public const JENIS = ['01' => 'Kegiatan Sosial', '02' => 'Kegiatan Politik'];

    public const UNIQUE = null;
    public const TITLE_FIELD = 'penerima';
    public const TAG_FIELD = 'jenis';
    public const SUB_FIELD = null;
    public const EXTRA_LINES = [['Tanggal pelaksanaan: %s', ['tanggal']]];
    public const BODY_FIELDS = ['penjelasan'];
    public const CHIP_FIELDS = ['jumlah'];
    public const UTAMA = ['01'];
    public const ORDER_COL = 'tanggalpelaksanaan';

    public const HAS_NAMES = false;
    public const HAS_EXTRA_ACC = false;   // accdekom2..5 + komut/dirut diisi lewat insertDefaults() (tabel tanpa accdir2)
    public const NODATA = true;

    public static function fields(): array
    {
        return [
            'tanggal' => ['col' => 'tanggalpelaksanaan', 'label' => 'Tanggal Pelaksanaan', 'type' => 'date', 'col_md' => 6],
            'jenis' => ['col' => 'jeniskegiatan', 'label' => 'Jenis Kegiatan', 'type' => 'select', 'options' => self::JENIS, 'col_md' => 6],
            'penerima' => ['col' => 'penerimadana', 'label' => 'Penerima Dana', 'type' => 'text', 'max' => 50],
            'penjelasan' => ['col' => 'penjelasankegiatan', 'label' => 'Penjelasan Kegiatan', 'type' => 'text', 'max' => 50],
            'jumlah' => ['col' => 'jumlah', 'label' => 'Total Dana (Rp)', 'short' => 'Dana', 'type' => 'rupiah', 'min' => 1, 'col_md' => 6],
        ];
    }

    protected static function insertDefaults(): array
    {
        return ['is_approved' => 0, 'approved_by' => 0, 'accdekom' => 0, 'accdekom_by' => 0,
            'accdekom2' => 0, 'accdekom2_by' => 0, 'accdekom3' => 0, 'accdekom3_by' => 0,
            'accdekom4' => 0, 'accdekom4_by' => 0, 'accdekom5' => 0, 'accdekom5_by' => 0,
            'komut' => '', 'dirut' => ''];
    }

    /** Baris "Tidak Ada Data": tanggal = hari ditandai, jenis 00 (sama dengan versi lama). */
    protected static function nodataValues(): array
    {
        return ['tanggalpelaksanaan' => date('Y-m-d'), 'jeniskegiatan' => '00', 'penerimadana' => '', 'penjelasankegiatan' => '', 'jumlah' => 0];
    }

    /** Sinkronisasi: tanggal pelaksanaan digeser +1 tahun (silakan disesuaikan setelahnya). */
    public static function syncRow(array $row): array
    {
        $ts = !empty($row['tanggalpelaksanaan']) ? strtotime($row['tanggalpelaksanaan'] . ' +1 year') : false;
        if ($ts) {
            $row['tanggalpelaksanaan'] = date('Y-m-d', $ts);
        }
        return $row;
    }

    private static function ymd($v): string
    {
        $ts = !empty($v) ? strtotime((string) $v) : false;
        return $ts ? date('Ymd', $ts) : '';
    }

    /** D01|110100000000|YYYYMMDD|jenis|penerima|penjelasan|jumlah (format lama). */
    public function txtLine(array $row, callable $clean): string
    {
        return 'D01|' . self::D01_CODE . '|' . self::ymd($row['tanggalpelaksanaan']) . '|' . $clean($row['jeniskegiatan'])
            . '|' . $clean($row['penerimadana']) . '|' . $clean($row['penjelasankegiatan']) . '|' . $clean($row['jumlah']);
    }

    /** "Tidak Ada Data": D01|110100000000|<tanggal ditandai>|00|||0 (format lama); belum diisi sama sekali: tanpa baris. */
    public function txtEmptyLine(array $ctx = []): ?string
    {
        $n = $ctx['nodata'] ?? null;
        return $n ? 'D01|' . self::D01_CODE . '|' . self::ymd($n['tanggalpelaksanaan'] ?? '') . '|00|||0' : null;
    }
}
