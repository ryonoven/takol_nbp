<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Basis model halaman daftar laporan semesteran (mis. bagian Laporan Audit Intern).
 * Banyak baris per BPR + periode; persetujuan Direktur Utama per baris (accdir2);
 * status "Tidak ada data" = satu baris dengan is_null_record = 1.
 */
abstract class M_semesterlist extends Model
{
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;

    /**
     * Field isian: name => [
     *   'label', 'type' => textarea|text|date|select, 'max' => panjang maks, 'required' => bool,
     *   'options' => [value => label] (select), 'col' => kelas grid, 'short' => label singkat (chip)
     * ]
     */
    public const FIELDS = [];

    /** [tanggal awal, tanggal akhir] — validasi akhir >= awal. Kosongkan bila tidak ada. */
    public const DATE_RANGE = [];

    /** Urutan kolom Excel impor (A, B, C, ...). */
    public const IMPORT_COLS = [];

    /** Maksimal baris per impor Excel. */
    public const IMPORT_MAX_ROWS = 200;

    /**
     * Tampilan kartu (gaya halaman Transparansi). Kosong = kartu daftar biasa.
     * ['title' => field judul, 'tag' => field label di bawah judul, 'sub' => [field baris kecil], 'chips' => [field],
     *  'body' => [field teks panjang], 'utama' => [nilai tag ditonjolkan], 'icon' => ikon avatar (kosong = inisial judul)]
     */
    public const CARD = [];

    /** true = persetujuan per baris + semua; false = hanya persetujuan seluruh data sekaligus. */
    public const ROW_APPROVAL = true;

    /** Tabel punya kolom is_null_record (fitur "Tidak ada data"). */
    public const HAS_NODATA = true;

    /** Field kunci duplikat untuk sinkron periode sebelumnya (kosong = seluruh field). */
    public const DUP_KEY = [];

    /**
     * Kolom accdir2_at boleh NULL? Seluruh tabel laporan semesteran saat ini NOT NULL DEFAULT CURRENT_TIMESTAMP,
     * jadi pembatalan persetujuan / perubahan data mengisi waktu kejadian (bukan NULL → 0000-00-00 / error strict).
     */
    public const ACC_AT_NULLABLE = false;

    /** Nilai accdir2_at saat persetujuan dibatalkan / data diubah. */
    public static function accAtReset(): ?string
    {
        return static::ACC_AT_NULLABLE ? null : date('Y-m-d H:i:s');
    }

    public function rows(string $kodebpr, int $periodeId): array
    {
        return $this->where(['kodebpr' => $kodebpr, 'periode_id' => $periodeId])->orderBy('id', 'ASC')->findAll();
    }

    public function findOwned(int $id, string $kodebpr, int $periodeId): ?array
    {
        return $this->where(['id' => $id, 'kodebpr' => $kodebpr, 'periode_id' => $periodeId])->first();
    }

    /** Validasi + normalisasi satu baris input. @return array{0: array, 1: array} [data, errors] */
    public static function validateRow(array $in): array
    {
        $data = [];
        $err = [];
        foreach (static::FIELDS as $name => $f) {
            $v = trim(str_replace("\r\n", "\n", (string) ($in[$name] ?? '')));
            $req = $f['required'] ?? true;
            // Field bersyarat: hanya berlaku bila field lain bernilai tertentu ('show_if' => [field, [nilai...]])
            if (!empty($f['show_if'])) {
                [$dep, $vals] = $f['show_if'];
                if (!in_array(trim((string) ($in[$dep] ?? '')), array_map('strval', $vals), true)) {
                    $data[$name] = $f['type'] === 'date' || $f['type'] === 'number' ? null : '';
                    continue;
                }
            }
            switch ($f['type']) {
                case 'number':
                    if ($v !== '') {
                        $v = str_replace('.', '', $v); // "1.200" → 1200
                        if (!preg_match('/^-?\d+$/', $v)) {
                            $err[$name] = 'Harus berupa bilangan bulat.';
                        } elseif (isset($f['min']) && (int) $v < $f['min']) {
                            $err[$name] = 'Minimal ' . $f['min'] . '.';
                        } elseif (isset($f['maxv']) && (int) $v > $f['maxv']) {
                            $err[$name] = 'Maksimal ' . number_format($f['maxv'], 0, ',', '.') . '.';
                        }
                    }
                    break;
                case 'date':
                    if ($v !== '') {
                        $d = \DateTime::createFromFormat('!Y-m-d', $v);
                        if (!$d || $d->format('Y-m-d') !== $v) {
                            $err[$name] = 'Format tanggal tidak valid.';
                        }
                    }
                    break;
                case 'select':
                    if ($v !== '' && !isset($f['options'][$v])) {
                        $err[$name] = 'Pilihan tidak valid.';
                    }
                    break;
                default:
                    if (!empty($f['digits']) && $v !== '' && !ctype_digit($v)) {
                        $err[$name] = 'Hanya boleh berisi angka.';
                    } elseif (!empty($f['max']) && mb_strlen($v) > $f['max']) {
                        $err[$name] = 'Maksimal ' . number_format($f['max'], 0, ',', '.') . ' karakter.';
                    }
            }
            if ($req && $v === '' && !isset($err[$name])) {
                $err[$name] = 'Wajib diisi.';
            }
            // Tanggal/angka kosong = NULL; teks/pilihan kosong = '' (aman untuk kolom NOT NULL)
            $data[$name] = $v === '' ? (in_array($f['type'], ['date', 'number'], true) ? null : '')
                : ($f['type'] === 'number' && !isset($err[$name]) ? (int) $v : $v);
        }
        if (static::DATE_RANGE) {
            [$a, $b] = static::DATE_RANGE;
            if (!isset($err[$a], $err[$b]) && $data[$a] && $data[$b] && $data[$b] < $data[$a]) {
                $err[$b] = 'Tidak boleh lebih awal dari ' . strtolower(static::FIELDS[$a][0]) . '.';
            }
        }
        return [$data, $err];
    }

    /** Kunci duplikat (untuk sinkron periode sebelumnya): seluruh isi field. */
    public static function rowKey(array $row): string
    {
        $k = [];
        foreach (static::DUP_KEY ?: array_keys(static::FIELDS) as $f) {
            $k[] = trim(str_replace("\r\n", "\n", (string) ($row[$f] ?? '')));
        }
        return md5(implode("\x1f", $k));
    }

    /** Nilai field untuk baris "Tidak ada data" (kolom NOT NULL dapat diisi di turunan). */
    public static function nullValues(array $periode): array
    {
        return array_fill_keys(array_keys(static::FIELDS), null);
    }

    /** Nilai awal kolom NOT NULL tanpa default (MySQL strict). */
    public static function insertDefaults(): array
    {
        return ['fullname' => '', 'accdir2' => 0, 'accdir2_by' => 0, 'accdir2_at' => static::accAtReset()]
            + (static::HAS_NODATA ? ['is_null_record' => 0] : []);
    }
}
