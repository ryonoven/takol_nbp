<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Dasar model halaman Laporan Transparansi berbentuk daftar (Direksi, Dewan Komisaris, Komite, ...).
 * Banyak baris per BPR + periode. Approval berlaku untuk seluruh baris periode:
 * Komisaris Utama (accdekom) lalu Direktur Utama (is_approved).
 * Tindak lanjut (F01/F02) disimpan di tabel penjelastindak dengan subkategori = static::SUBKATEGORI.
 *
 * Subclass mengisi $table, konstanta di bawah, dan fields(): key => [
 *   'col'      kolom tabel
 *   'label'    label form
 *   'type'     text | nik | idnum | select | number | percent | date | rupiah | textarea
 *   'section'  judul kelompok di dialog sebelum field ini (opsional)
 *   'min'      (number) nilai minimum (default 0)
 *   'typeField', 'lengths'  (idnum) field select jenis identitas + panjang digit per jenis, mis. ['NIK' => 16, 'NIB' => 13]
 *   'unit'     satuan untuk chip kartu (opsional, mis. '%')
 *   'showIf'   [field, nilai] — field hanya tampil & wajib bila field lain bernilai tsb (selain itu disimpan '')
 *   'max'      panjang maks (text/textarea) atau nilai maks (number)
 *   'options'  (select) kode => label
 *   'col_md'   lebar kolom form (bootstrap, default 12)
 *   'default'  nilai awal saat tambah data (opsional)
 *   'short'    label singkat untuk chip kartu (opsional)
 * ]
 */
abstract class M_anggotatransparansi extends Model
{
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;

    public const SUBKATEGORI = '';
    public const FORM_CODE = '';
    public const D01_CODE = '';
    /** Definisi field (lihat keterangan kelas). */
    abstract public static function fields(): array;

    /** Field unik per periode (null = tanpa cek) + nilai yang dikecualikan dari cek. */
    public const UNIQUE = null;
    public const UNIQUE_EXCEPT = [];
    public const UNIQUE_MSG = 'Data ini sudah terdaftar pada periode ini.';

    /** Tampilan kartu: judul, badge (field select), keterangan kecil (SUB_FIELD: satu field atau array, mengisi %s berurutan di SUB_FORMAT), isi (field textarea). */
    public const TITLE_FIELD = '';
    public const TAG_FIELD = '';
    public const SUB_FIELD = null;
    public const SUB_FORMAT = '%s';
    public const BODY_FIELDS = [];
    /** Nilai TAG_FIELD yang ditandai "utama" (warna badge berbeda). */
    public const UTAMA = [];
    /** Baris keterangan tambahan di kartu: [[format sprintf %s, [field...]], ...] */
    public const EXTRA_LINES = [];
    /** Tulis "F01|" kosong di TXT bila penjelasan belum diisi (format lama beberapa halaman). */
    public const TXT_EMPTY_F01 = false;
    /** Field select yang ditampilkan sebagai chip di kartu (pakai 'short' sebagai label), kecuali nilai CHIP_SKIP. */
    public const CHIP_FIELDS = [];
    public const CHIP_SKIP = [];
    /** Kolom urutan tampilan / export. */
    public const ORDER_COL = 'id';

    /** Tabel punya kolom nama penyetuju (komut, dirut). */
    public const HAS_NAMES = true;
    /** Tabel punya kolom approval tambahan accdekom2..5 / accdir2 (+ _by). */
    public const HAS_EXTRA_ACC = true;
    /** Tabel punya kolom fullname (nama pengisi). */
    public const HAS_FULLNAME = true;
    /** Mendukung status "Tidak Ada Data" (kolom is_null_record). */
    public const NODATA = false;

    public const MAX_TINDAK = 4000;

    /** Kolom NOT NULL tanpa default: wajib saat insert agar aman di MySQL strict mode. */
    private const INSERT_DEFAULTS = [
        'is_approved' => 0, 'approved_by' => 0,
        'accdekom' => 0, 'accdekom_by' => 0,
        'accdekom2' => 0, 'accdekom2_by' => 0, 'accdekom3' => 0, 'accdekom3_by' => 0,
        'accdekom4' => 0, 'accdekom4_by' => 0, 'accdekom5' => 0, 'accdekom5_by' => 0,
        'accdir2' => 0, 'accdir2_by' => 0,
    ];

    public function __construct(...$args)
    {
        $this->allowedFields = array_merge(
            array_column(static::fields(), 'col'),
            ['user_id', 'kodebpr', 'periode_id',
                'is_approved', 'approved_by', 'approved_at', 'accdekom', 'accdekom_by', 'accdekom_at'],
            static::HAS_EXTRA_ACC ? ['accdekom2', 'accdekom2_by', 'accdekom3', 'accdekom3_by', 'accdekom4', 'accdekom4_by',
                'accdekom5', 'accdekom5_by', 'accdir2', 'accdir2_by'] : [],
            static::HAS_NAMES ? ['komut', 'dirut'] : [],
            static::HAS_FULLNAME ? ['fullname'] : [],
            static::NODATA ? ['is_null_record'] : [],
            array_keys(static::insertDefaults())   // kolom default tambahan (mis. keterangan) ikut diizinkan
        );
        $this->allowedFields = array_values(array_unique($this->allowedFields));
        parent::__construct(...$args);
    }

    /** FIELDS standar daftar orang (Direksi / Dewan Komisaris). */
    protected static function personFields(string $nameCol, string $nameLabel, string $tugasCol, array $jabatan): array
    {
        return [
            'nama' => ['col' => $nameCol, 'label' => $nameLabel, 'type' => 'text', 'max' => 255, 'col_md' => 7],
            'nik' => ['col' => 'nik', 'label' => 'NIK', 'type' => 'nik', 'max' => 16, 'col_md' => 5],
            'jabatan' => ['col' => 'jabatan', 'label' => 'Jabatan', 'type' => 'select', 'options' => $jabatan],
            'tugas' => ['col' => $tugasCol, 'label' => 'Pelaksanaan Tugas dan Tanggung Jawab', 'type' => 'textarea', 'max' => 4000],
        ];
    }

    public static function col(string $key): string
    {
        return static::fields()[$key]['col'];
    }

    /** Dipakai Pdfgcg. */
    public function getAllData(): array
    {
        return $this->db->table($this->table)->get()->getResultArray();
    }

    public function getDataByKodebprAndPeriode($kodebpr, $periodeId): array
    {
        return $this->listByPeriode((int) $periodeId, (string) $kodebpr);
    }

    // ------------------------------------------------------------------ baris

    private function scoped(int $periodeId, string $kodebpr)
    {
        return $this->where('periode_id', $periodeId)->where('kodebpr', $kodebpr);
    }

    /** Baris data (tanpa penanda "Tidak Ada Data"). */
    public function listByPeriode(int $periodeId, string $kodebpr): array
    {
        $b = $this->scoped($periodeId, $kodebpr);
        if (static::NODATA) {
            $this->notNodata($b);
        }
        return $b->orderBy(static::ORDER_COL, 'ASC')->orderBy('id', 'ASC')->findAll();
    }

    /** Filter baris data biasa (is_null_record 0 atau NULL). */
    private function notNodata($b): void
    {
        $b->groupStart()->where('is_null_record', 0)->orWhere('is_null_record', null)->groupEnd();
    }

    /** Seluruh baris periode termasuk penanda "Tidak Ada Data" (dasar status approval). */
    public function listAll(int $periodeId, string $kodebpr): array
    {
        return $this->scoped($periodeId, $kodebpr)->orderBy('id', 'ASC')->findAll();
    }

    public function hasNodata(int $periodeId, string $kodebpr): bool
    {
        return static::NODATA && $this->scoped($periodeId, $kodebpr)->where('is_null_record', 1)->countAllResults() > 0;
    }

    public function findInPeriode(int $id, int $periodeId, string $kodebpr): ?array
    {
        $b = $this->scoped($periodeId, $kodebpr)->where('id', $id);
        if (static::NODATA) {
            $this->notNodata($b);
        }
        return $b->first();
    }

    /** true bila nilai field unik sudah dipakai baris lain di periode ini. */
    public function duplicate(string $value, int $periodeId, string $kodebpr, ?int $exceptId = null): bool
    {
        if (static::UNIQUE === null || in_array($value, static::UNIQUE_EXCEPT, true)) {
            return false;
        }
        $b = $this->scoped($periodeId, $kodebpr)->where(static::col(static::UNIQUE), $value);
        if ($exceptId) {
            $b->where('id !=', $exceptId);
        }
        return $b->countAllResults() > 0;
    }

    /** Nilai default kolom approval saat insert (override bila tipe kolom berbeda). */
    protected static function insertDefaults(): array
    {
        $d = static::HAS_EXTRA_ACC ? self::INSERT_DEFAULTS
            : array_intersect_key(self::INSERT_DEFAULTS, array_flip(['is_approved', 'approved_by', 'accdekom', 'accdekom_by']));
        return $d + (static::HAS_NAMES ? ['komut' => '', 'dirut' => ''] : []);
    }

    public function insertRow(array $data): bool
    {
        return (bool) $this->insert($data + static::insertDefaults());
    }

    public function setNodata(int $periodeId, string $kodebpr, int $userId, string $fullname): bool
    {
        return $this->insertRow(static::nodataValues()
            + ['is_null_record' => 1, 'kodebpr' => $kodebpr, 'periode_id' => $periodeId, 'user_id' => $userId, 'fullname' => $fullname]);
    }

    /** Ubah data baris tahun lalu sebelum disalin saat sinkronisasi (mis. geser tanggal +1 tahun). */
    public static function syncRow(array $row): array
    {
        return $row;
    }

    /** Nilai kolom field untuk baris "Tidak Ada Data" (default NULL; override bila kolom NOT NULL). */
    protected static function nodataValues(): array
    {
        return array_fill_keys(array_column(static::fields(), 'col'), null);
    }

    /**
     * Validasi tambahan antar-field (setelah validasi per field lolos).
     * @param array $data kolom => nilai
     * @return array key field => pesan
     */
    public static function validateRow(array $data): array
    {
        return [];
    }

    public function clearNodata(int $periodeId, string $kodebpr): bool
    {
        return (bool) $this->db->table($this->table)
            ->where('periode_id', $periodeId)->where('kodebpr', $kodebpr)->where('is_null_record', 1)->delete();
    }

    // ------------------------------------------------------------------ approval

    /** Status approval gabungan seluruh baris periode (pakai listAll). */
    public static function approvalSummary(array $rows): array
    {
        $n = count($rows);
        $komut = $n > 0 && count(array_filter($rows, static fn($r) => (int) $r['accdekom'] === 1)) === $n;
        $dirut = $n > 0 && count(array_filter($rows, static fn($r) => (int) $r['is_approved'] === 1)) === $n;
        $first = $rows[0] ?? [];
        return [
            'komut' => $komut, 'komut_at' => $komut ? $first['accdekom_at'] : null, 'komut_by' => (int) ($first['accdekom_by'] ?? 0), 'komut_nama' => (string) ($first['komut'] ?? ''),
            'dirut' => $dirut, 'dirut_at' => $dirut ? $first['approved_at'] : null, 'dirut_by' => (int) ($first['approved_by'] ?? 0), 'dirut_nama' => (string) ($first['dirut'] ?? ''),
            'any' => $n > 0 && (array_sum(array_column($rows, 'accdekom')) > 0 || array_sum(array_column($rows, 'is_approved')) > 0),
        ];
    }

    public function resetApproval(int $periodeId, string $kodebpr): void
    {
        $this->db->table($this->table)->where('periode_id', $periodeId)->where('kodebpr', $kodebpr)
            ->update(['accdekom' => 0, 'accdekom_by' => 0, 'is_approved' => 0, 'approved_by' => 0]);
    }

    /** Set / batalkan approval seluruh baris. Membatalkan Komisaris Utama juga membatalkan Direktur Utama. */
    public function setApproval(int $periodeId, string $kodebpr, string $type, bool $approve, int $userId, string $fullname): bool
    {
        $now = date('Y-m-d H:i:s');
        if ($type === 'komut') {
            $data = $approve
                ? ['accdekom' => 1, 'accdekom_by' => $userId, 'accdekom_at' => $now] + (static::HAS_NAMES ? ['komut' => $fullname] : [])
                : ['accdekom' => 0, 'accdekom_by' => 0, 'is_approved' => 0, 'approved_by' => 0];
        } else {
            $data = $approve
                ? ['is_approved' => 1, 'approved_by' => $userId, 'approved_at' => $now] + (static::HAS_NAMES ? ['dirut' => $fullname] : [])
                : ['is_approved' => 0, 'approved_by' => 0];
        }
        return (bool) $this->db->table($this->table)->where('periode_id', $periodeId)->where('kodebpr', $kodebpr)->update($data);
    }

    /** Periode tahun sebelumnya (tahun - 1) terbaru yang memiliki data (atau "Tidak Ada Data"); null bila tidak ada. */
    public function prevYearPeriodeId(string $kodebpr, int $tahun): ?int
    {
        $r = $this->db->table($this->table . ' d')
            ->select('d.periode_id')
            ->join('periode_tahunan t', 't.id = d.periode_id')
            ->where('d.kodebpr', $kodebpr)->where('t.tahun', $tahun - 1)
            ->orderBy('d.periode_id', 'DESC')
            ->get(1)->getRowArray();
        return $r ? (int) $r['periode_id'] : null;
    }

    // ------------------------------------------------------------------ export TXT

    /** Baris D01 untuk satu data; $clean = fungsi pembersih teks. */
    public function txtLine(array $row, callable $clean): string
    {
        return 'D01|' . static::D01_CODE . '|' . $clean($row['nik'] ?? '') . '|' . $clean($row[static::col('tugas')] ?? '');
    }

    /** Baris D01 bila tidak ada data sama sekali (null = tidak ditulis). $ctx: ['sandibpr', 'kodejenis', 'tahun', 'nodata' => baris "Tidak Ada Data"|null]. */
    public function txtEmptyLine(array $ctx = []): ?string
    {
        return null;
    }

    // ------------------------------------------------------------------ tindak lanjut (penjelastindak)

    public function getTindak(int $periodeId, string $kodebpr): ?array
    {
        return $this->db->table('penjelastindak')
            ->select('id, tindaklanjut, penjelasanlanjut')
            ->where('subkategori', static::SUBKATEGORI)->where('periode_id', $periodeId)->where('kodebpr', $kodebpr)
            ->orderBy('id', 'ASC')->get(1)->getRowArray();
    }

    public function saveTindak(?int $id, array $data): bool
    {
        $t = $this->db->table('penjelastindak');
        return $id
            ? (bool) $t->where('id', $id)->update($data)
            : (bool) $t->insert($data + ['subkategori' => static::SUBKATEGORI]);
    }
}
