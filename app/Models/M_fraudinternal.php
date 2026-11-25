<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Laporan Transparansi 15 — Jumlah Penyimpangan Internal (Internal Fraud) (E0800).
 * Satu baris per BPR + periode: matriks 8 isian × 4 kelompok pelaku.
 * Mendukung "Tidak Ada Data": baris dengan is_null_record = 1 dan seluruh nilai 0 (tetap perlu approval).
 * Approval: Komisaris Utama (accdekom) lalu Direktur Utama (is_approved).
 */
class M_fraudinternal extends Model
{
    protected $table = 'fraudinternal';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;

    public const FORM_CODE = 'E0800';
    public const SUBKATEGORI = 'Fraudinternal';
    public const MAX_TINDAK = 4000;
    public const MAX_VALUE = 9999;

    /** Kelompok pelaku: suffix kolom => label */
    public const GROUPS = ['dir' => 'Anggota Direksi', 'dekom' => 'Anggota Dewan Komisaris', 'kartap' => 'Pegawai Tetap', 'kontrak' => 'Pegawai Tidak Tetap'];

    /** Baris isian: prefix kolom (tanpa suffix kelompok) => [no, label] */
    public const ROWS = [
        'fraudtahunlaporan' => ['1', 'Total fraud — tahun laporan'],
        'fraudtahunsebelum' => ['1', 'Total fraud — tahun sebelumnya'],
        'selesaitahunlaporan' => ['2', 'Telah diselesaikan — tahun laporan'],
        'prosestahunlaporan' => ['3', 'Dalam proses penyelesaian — tahun laporan'],
        'prosestahunsebelum' => ['3', 'Dalam proses penyelesaian — tahun sebelumnya'],
        'belumtahunlaporan' => ['4', 'Belum diupayakan penyelesaiannya — tahun laporan'],
        'belumtahunsebelum' => ['4', 'Belum diupayakan penyelesaiannya — tahun sebelumnya'],
        'hukumtahunlaporan' => ['5', 'Ditindaklanjuti melalui proses hukum — tahun laporan'],
    ];

    /** Baris TXT: kode => [prefix tahun sebelumnya|null, prefix tahun laporan] (urutan kolom: per kelompok sebelum, laporan). */
    public const TXT_LINES = [
        '910' => ['fraudtahunsebelum', 'fraudtahunlaporan'],
        '911' => [null, 'selesaitahunlaporan'],
        '912' => ['prosestahunsebelum', 'prosestahunlaporan'],
        '913' => ['belumtahunsebelum', 'belumtahunlaporan'],
        '920' => [null, 'hukumtahunlaporan'],
    ];

    /** Kolom NOT NULL tanpa default (aman untuk MySQL strict mode). */
    private const INSERT_DEFAULTS = [
        'dirut' => '', 'komut' => '',
        'is_approved' => 0, 'approved_by' => 0, 'accdekom' => 0, 'accdekom_by' => 0,
        'accdekom2' => 0, 'accdekom2_by' => 0, 'accdekom3' => 0, 'accdekom3_by' => 0,
        'accdekom4' => 0, 'accdekom4_by' => 0, 'accdekom5' => 0, 'accdekom5_by' => 0,
    ];

    public function __construct(...$args)
    {
        $this->allowedFields = array_merge(self::valueColumns(), array_keys(self::INSERT_DEFAULTS),
            ['kodebpr', 'periode_id', 'user_id', 'fullname', 'is_null_record', 'approved_at', 'accdekom_at']);
        parent::__construct(...$args);
    }

    /** Semua kolom isian (8 × 4). */
    public static function valueColumns(): array
    {
        $cols = [];
        foreach (array_keys(self::ROWS) as $p) {
            foreach (array_keys(self::GROUPS) as $g) {
                $cols[] = $p . $g;
            }
        }
        return $cols;
    }

    /** Dipakai Pdfgcg. */
    public function getAllData(): array
    {
        return $this->db->table($this->table)->get()->getResultArray();
    }

    public function getDataByKodebprAndPeriode($kodebpr, $periodeId): array
    {
        return $this->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->findAll();
    }

    public function getByPeriode(int $periodeId, string $kodebpr): ?array
    {
        return $this->where('periode_id', $periodeId)->where('kodebpr', $kodebpr)->orderBy('id', 'DESC')->first();
    }

    public function getPrevYear(string $kodebpr, int $tahun): ?array
    {
        return $this->db->table($this->table . ' f')
            ->select('f.*')
            ->join('periode_tahunan t', 't.id = f.periode_id')
            ->where('f.kodebpr', $kodebpr)->where('t.tahun', $tahun - 1)
            ->orderBy('f.periode_id', 'DESC')->orderBy('f.id', 'DESC')
            ->get(1)->getRowArray();
    }

    public function saveRow(?int $id, array $data): bool
    {
        return $id ? (bool) $this->update($id, $data) : (bool) $this->insert($data + self::INSERT_DEFAULTS);
    }

    public static function zeroValues(): array
    {
        return array_fill_keys(self::valueColumns(), '0');
    }

    public static function resetApprovalData(): array
    {
        return ['accdekom' => 0, 'accdekom_by' => 0, 'is_approved' => 0, 'approved_by' => 0];
    }

    /** Membatalkan Komisaris Utama juga membatalkan Direktur Utama. */
    public function setApproval(int $id, string $type, bool $approve, int $userId): bool
    {
        $now = date('Y-m-d H:i:s');
        if ($type === 'komut') {
            $data = $approve ? ['accdekom' => 1, 'accdekom_by' => $userId, 'accdekom_at' => $now]
                : ['accdekom' => 0, 'accdekom_by' => 0, 'is_approved' => 0, 'approved_by' => 0];
        } else {
            $data = $approve ? ['is_approved' => 1, 'approved_by' => $userId, 'approved_at' => $now]
                : ['is_approved' => 0, 'approved_by' => 0];
        }
        return (bool) $this->update($id, $data);
    }

    // ------------------------------------------------------------------ penjelasan lebih lanjut (penjelastindak)

    public function getTindak(int $periodeId, string $kodebpr): ?array
    {
        return $this->db->table('penjelastindak')->select('id, tindaklanjut')
            ->where('subkategori', self::SUBKATEGORI)->where('periode_id', $periodeId)->where('kodebpr', $kodebpr)
            ->orderBy('id', 'ASC')->get(1)->getRowArray();
    }

    public function saveTindak(?int $id, array $data): bool
    {
        $t = $this->db->table('penjelastindak');
        return $id ? (bool) $t->where('id', $id)->update($data)
            : (bool) $t->insert($data + ['subkategori' => self::SUBKATEGORI]);
    }
}
