<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Laporan Transparansi 12 — Rasio Gaji Tertinggi dan Gaji Terendah (E0600).
 * Satu baris per BPR + periode. User mengisi 6 nominal gaji; 5 rasio selalu dihitung di server.
 * Approval: Komisaris Utama (accdekom) lalu Direktur Utama (is_approved).
 */
class M_rasiogaji extends Model
{
    protected $table = 'rasiogaji';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;

    public const FORM_CODE = 'E0600';
    public const SUBKATEGORI = 'Rasiogaji';
    public const MAX_TINDAK = 4000;
    public const MAX_DIGITS = 15;

    /** Nominal gaji 1 tahun yang diisi user: kolom => label */
    public const AMOUNTS = [
        'pegawaitinggi' => 'Gaji pegawai tertinggi',
        'pegawairendah' => 'Gaji pegawai terendah',
        'dirtinggi' => 'Gaji anggota Direksi tertinggi',
        'dirrendah' => 'Gaji anggota Direksi terendah',
        'dekomtinggi' => 'Gaji anggota Dewan Komisaris tertinggi',
        'dekomrendah' => 'Gaji anggota Dewan Komisaris terendah',
    ];

    /** Pasangan tertinggi >= terendah. */
    public const PAIRS = [['pegawaitinggi', 'pegawairendah'], ['dirtinggi', 'dirrendah'], ['dekomtinggi', 'dekomrendah']];

    /** Rasio: kode OJK => [no, judul, kolom rasio, kolom a, kolom b] */
    public const RATIOS = [
        '070100000000' => ['1', 'Gaji pegawai tertinggi : terendah', 'pegawaitinggirendah', 'pegawaitinggi', 'pegawairendah'],
        '070200000000' => ['2', 'Gaji anggota Direksi tertinggi : terendah', 'direksitinggirendah', 'dirtinggi', 'dirrendah'],
        '070300000000' => ['3', 'Gaji anggota Dewan Komisaris tertinggi : terendah', 'dekomtinggirendah', 'dekomtinggi', 'dekomrendah'],
        '070400000000' => ['4', 'Gaji Direksi tertinggi : Dewan Komisaris tertinggi', 'dirdekomtinggirendah', 'dirtinggi', 'dekomtinggi'],
        '070500000000' => ['5', 'Gaji Direksi tertinggi : pegawai tertinggi', 'dirpegawaitinggirendah', 'dirtinggi', 'pegawaitinggi'],
    ];

    /** Kolom NOT NULL tanpa default (aman untuk MySQL strict mode). */
    private const INSERT_DEFAULTS = [
        'is_approved' => 0, 'approved_by' => 0, 'accdekom' => 0, 'accdekom_by' => 0,
        'accdekom2' => 0, 'accdekom2_by' => 0, 'accdekom3' => 0, 'accdekom3_by' => 0,
        'accdekom4' => 0, 'accdekom4_by' => 0, 'accdekom5' => 0, 'accdekom5_by' => 0,
    ];

    public function __construct(...$args)
    {
        $this->allowedFields = array_merge(
            array_keys(self::AMOUNTS),
            array_column(array_values(self::RATIOS), 2),
            array_keys(self::INSERT_DEFAULTS),
            ['kodebpr', 'periode_id', 'user_id', 'fullname', 'keterangan', 'approved_at', 'accdekom_at']
        );
        parent::__construct(...$args);
    }

    /** Rasio a : b dibulatkan 2 desimal (null bila b = 0). */
    public static function computeRatios(array $v): array
    {
        $out = [];
        foreach (self::RATIOS as [, , $col, $a, $b]) {
            $out[$col] = ((int) ($v[$b] ?? 0)) > 0 ? round((int) $v[$a] / (int) $v[$b], 2) : null;
        }
        return $out;
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

    /** Data tahun sebelumnya (periode terbaru di tahun - 1). */
    public function getPrevYear(string $kodebpr, int $tahun): ?array
    {
        return $this->db->table($this->table . ' r')
            ->select('r.*')
            ->join('periode_tahunan t', 't.id = r.periode_id')
            ->where('r.kodebpr', $kodebpr)->where('t.tahun', $tahun - 1)
            ->orderBy('r.periode_id', 'DESC')->orderBy('r.id', 'DESC')
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
