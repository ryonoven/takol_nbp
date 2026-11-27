<?php

namespace App\Models;

use CodeIgniter\Model;

class M_penjelasanumum extends Model
{
    protected $table = 'penjelasanumum';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'namabpr', 'alamat', 'nomor', 'penjelasan', 'peringkatkomposit', 'penjelasankomposit', 'keterangan',
        'is_approved', 'approved_by', 'approved_at', 'dirut',
        'accdekom', 'accdekom_by', 'accdekom_at', 'komut',
        'user_id', 'fullname', 'kodebpr', 'periode_id',
        'accdekom2', 'accdekom2_by', 'accdekom3', 'accdekom3_by', 'accdekom4', 'accdekom4_by',
        'accdekom5', 'accdekom5_by', 'accdir2', 'accdir2_by',
    ];

    public const FORM_CODE = 'E0100';

    public const MAX = [
        'namabpr' => 250,
        'alamat' => 500,
        'nomor' => 25,
        'penjelasan' => 2000,
        'peringkatkomposit' => 1,
        'penjelasankomposit' => 2000,
    ];

    public const PERINGKAT = [
        '1' => '1 — Sangat Baik',
        '2' => '2 — Baik',
        '3' => '3 — Cukup Baik',
        '4' => '4 — Kurang Baik',
        '5' => '5 — Tidak Baik',
    ];

    private const INSERT_DEFAULTS = [
        'keterangan' => '',
        'is_approved' => 0, 'approved_by' => 0, 'dirut' => '',
        'accdekom' => 0, 'accdekom_by' => 0, 'komut' => '',
        'accdekom2' => 0, 'accdekom2_by' => 0, 'accdekom3' => 0, 'accdekom3_by' => 0,
        'accdekom4' => 0, 'accdekom4_by' => 0, 'accdekom5' => 0, 'accdekom5_by' => 0,
        'accdir2' => 0, 'accdir2_by' => 0,
    ];

    public function getAllData(): array
    {
        return $this->db->table($this->table)->get()->getResultArray();
    }

    public function getDataByKodebprAndPeriode($kodebpr, $periodeId): array
    {
        return $this->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->findAll();
    }

    /** Baris periode aktif (terbaru bila ada duplikat lama). */
    public function getByPeriode(int $periodeId, string $kodebpr): ?array
    {
        return $this->where('periode_id', $periodeId)->where('kodebpr', $kodebpr)
            ->orderBy('id', 'DESC')->first();
    }

    /** Data Penjelasan Umum tahun sebelumnya (periode apa pun di tahun tsb, yang terbaru). */
    public function getPrevYear(string $kodebpr, int $tahun): ?array
    {
        return $this->db->table($this->table . ' p')
            ->select('p.namabpr, p.alamat, p.nomor, p.penjelasan, p.peringkatkomposit, p.penjelasankomposit')
            ->join('periode_tahunan t', 't.id = p.periode_id')
            ->where('p.kodebpr', $kodebpr)
            ->where('t.tahun', $tahun - 1)
            ->orderBy('p.id', 'DESC')
            ->get(1)->getRowArray();
    }

    public function saveRow(?int $id, array $data): bool
    {
        if ($id) {
            return (bool) $this->update($id, $data);
        }
        return (bool) $this->insert($data + self::INSERT_DEFAULTS);
    }

    public static function resetApprovalData(): array
    {
        return ['accdekom' => 0, 'accdekom_by' => 0, 'is_approved' => 0, 'approved_by' => 0];
    }

    public function setApproval(int $id, string $type, bool $approve, int $userId, string $fullname): bool
    {
        $now = date('Y-m-d H:i:s');
        if ($type === 'komut') {
            $data = $approve
                ? ['accdekom' => 1, 'accdekom_by' => $userId, 'accdekom_at' => $now, 'komut' => $fullname]
                : ['accdekom' => 0, 'accdekom_by' => 0, 'is_approved' => 0, 'approved_by' => 0];
        } else {
            $data = $approve
                ? ['is_approved' => 1, 'approved_by' => $userId, 'approved_at' => $now, 'dirut' => $fullname]
                : ['is_approved' => 0, 'approved_by' => 0];
        }
        return (bool) $this->update($id, $data);
    }
}
