<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Laporan Transparansi 16 — Permasalahan Hukum yang Dihadapi (E0900).
 * Satu baris per BPR + periode: jumlah perkara perdata & pidana (selesai / dalam proses).
 * Total selalu dihitung di server. Mendukung "Tidak Ada Data" (is_null_record = 1, semua nilai 0).
 * Approval: Komisaris Utama (accdekom) lalu Direktur Utama (is_approved).
 */
class M_masalahhukum extends Model
{
    protected $table = 'masalahhukum';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;

    public const FORM_CODE = 'E0900';
    public const SUBKATEGORI = 'Masalahhukum';
    public const MAX_TINDAK = 4000;
    public const MAX_VALUE = 9999;

    /** Baris: status => [kode TXT, label, kolom perdata, kolom pidana, kolom total] */
    public const ROWS = [
        'selesai' => ['1001', 'Telah selesai (berkekuatan hukum tetap)', 'hukumperdataselesai', 'hukumpidanaselesai', 'totalselesai'],
        'proses' => ['1002', 'Dalam proses penyelesaian', 'hukumperdataproses', 'hukumpidanaproses', 'totalproses'],
    ];

    /** Kolom NOT NULL tanpa default (aman untuk MySQL strict mode). */
    private const INSERT_DEFAULTS = [
        'is_approved' => 0, 'approved_by' => 0, 'accdekom' => 0, 'accdekom_by' => 0,
        'accdekom2' => 0, 'accdekom2_by' => 0, 'accdekom3' => 0, 'accdekom3_by' => 0,
        'accdekom4' => 0, 'accdekom4_by' => 0, 'accdekom5' => 0, 'accdekom5_by' => 0,
    ];

    public function __construct(...$args)
    {
        $this->allowedFields = array_merge(self::valueColumns(), ['totalselesai', 'totalproses'], array_keys(self::INSERT_DEFAULTS),
            ['kodebpr', 'periode_id', 'user_id', 'fullname', 'is_null_record', 'approved_at', 'accdekom_at']);
        parent::__construct(...$args);
    }

    /** 4 kolom isian. */
    public static function valueColumns(): array
    {
        $c = [];
        foreach (self::ROWS as [, , $perdata, $pidana]) {
            array_push($c, $perdata, $pidana);
        }
        return $c;
    }

    /** Total per status (selesai / proses). */
    public static function computeTotals(array $v): array
    {
        $out = [];
        foreach (self::ROWS as [, , $perdata, $pidana, $total]) {
            $out[$total] = (int) ($v[$perdata] ?? 0) + (int) ($v[$pidana] ?? 0);
        }
        return $out;
    }

    public static function zeroValues(): array
    {
        return array_fill_keys(self::valueColumns(), 0);
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
        return $this->db->table($this->table . ' m')
            ->select('m.*')
            ->join('periode_tahunan t', 't.id = m.periode_id')
            ->where('m.kodebpr', $kodebpr)->where('t.tahun', $tahun - 1)
            ->orderBy('m.periode_id', 'DESC')->orderBy('m.id', 'DESC')
            ->get(1)->getRowArray();
    }

    public function saveRow(?int $id, array $data): bool
    {
        return $id ? (bool) $this->update($id, $data) : (bool) $this->insert($data + self::INSERT_DEFAULTS);
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
