<?php
namespace App\Models;

use CodeIgniter\Model;

class M_penyimpangankepatuhan extends Model
{
    protected $table = 'penyimpangankepatuhan';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id',
        'kodebpr',
        'periode_id',
        'tahun',
        'semester',
        'topik',
        'tanggal',
        'pelanggaran',
        'tanggaltemuan',
        'uraian',
        'upaya',
        'statuspenyelesaian',
        'uraian',
        'accdir2',
        'accdir2_by',
        'accdir2_at',
        'is_null_record'
    ];
    protected $useTimestamps = false;

    public function getDataByKodebprAndPeriode(string $kodebpr, int $periodeId): array
    {
        return $this->db->table($this->table)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();
    }

    public function tambah(array $data)
    {
        return $this->insert($data);
    }

    public function ubah(array $data, int $id, string $kodebpr, int $periodeId): bool
    {
        return $this->db->table($this->table)
            ->where('id', $id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->update($data);
    }

    public function hapusSpesifik(int $id, string $kodebpr, int $periodeId): bool
    {
        $deleted = $this->db->table($this->table)
            ->where('id', $id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->delete();

        if ($deleted) {
            $this->resequenceIds();
        }

        return (bool) $deleted;
    }

    private function resequenceIds(): void
    {
        $rows = $this->db->table($this->table)
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        if (empty($rows)) {
            $this->db->query('ALTER TABLE `' . $this->table . '` AUTO_INCREMENT = 1');
            return;
        }

        $offset = count($rows) + 10000;
        foreach ($rows as $i => $row) {
            $this->db->table($this->table)
                ->where('id', $row['id'])
                ->update(['id' => $offset + $i + 1]);
        }

        $tempRows = $this->db->table($this->table)
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        foreach ($tempRows as $i => $row) {
            $this->db->table($this->table)
                ->where('id', $row['id'])
                ->update(['id' => $i + 1]);
        }

        // Reset auto increment
        $maxRow = $this->db->table($this->table)->selectMax('id')->get()->getRow();
        $next = $maxRow ? ((int) $maxRow->id + 1) : 1;
        $this->db->query('ALTER TABLE `' . $this->table . '` AUTO_INCREMENT = ' . $next);
    }

    public function approveKomRow(int $id, string $kodebpr, int $periodeId, int $userId): bool
    {
        return $this->db->table($this->table)
            ->where('id', $id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->update([
                'accdir2' => 1,
                'accdir2_by' => $userId,
                'accdir2_at' => date('Y-m-d H:i:s'),
            ]);
    }

    public function unapproveKomRow(int $id, string $kodebpr, int $periodeId, int $userId): bool
    {
        return $this->db->table($this->table)
            ->where('id', $id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->update([
                'accdir2' => 0,
                'accdir2_by' => $userId,
                'accdir2_at' => date('Y-m-d H:i:s'),
            ]);
    }

    public function approveSemuaKom(string $kodebpr, int $periodeId, int $userId): bool
    {
        return $this->db->table($this->table)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->update([
                'accdir2' => 1,
                'accdir2_by' => $userId,
                'accdir2_at' => date('Y-m-d H:i:s'),
            ]);
    }

    public function unapproveSemuaKom(string $kodebpr, int $periodeId, int $userId): bool
    {
        return $this->db->table($this->table)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->update([
                'accdir2' => 0,
                'accdir2_by' => $userId,
                'accdir2_at' => date('Y-m-d H:i:s'),
            ]);
    }

    public function getApprovalStatus(string $kodebpr, int $periodeId): array
    {
        $rows = $this->db->table($this->table)
            ->select('accdir2, accdir2_at')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->get()->getResultArray();

        if (empty($rows)) {
            return [
                'dekom_approved' => false,
                'dekom_approved_at' => null,
            ];
        }

        $dekomOk = true;
        $dekomAt = null;

        foreach ($rows as $r) {
            if ((int) $r['accdir2'] !== 1)
                $dekomOk = false;
            if ($r['accdir2_at'])
                $dekomAt = $r['accdir2_at'];
        }

        return [
            'dekom_approved' => $dekomOk,
            'dekom_approved_at' => $dekomAt,
        ];
    }

    public function getPelanggaranTanggalByPeriode(string $kodebpr, int $periodeId): array
    {
        $rows = $this->db->table($this->table)
            ->select('pelanggaran, tanggaltemuan')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->get()->getResultArray();

        return array_map(function ($r) {
            return $r['pelanggaran'] . '||' . $r['tanggaltemuan'];
        }, $rows);
    }

    public function insertBatchSync(array $rows): bool
    {
        if (empty($rows))
            return true;

        $result = $this->db->table($this->table)->insertBatch($rows);

        return $result !== false;
    }

    public function hasNullRecord(string $kodebpr, int $periodeId): ?array
    {
        return $this->db->table($this->table)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('is_null_record', 1)
            ->get()->getRowArray();
    }

    public function hasRealData(string $kodebpr, int $periodeId): bool
    {
        return $this->db->table($this->table)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('is_null_record', 0)
            ->countAllResults() > 0;
    }

    public function insertNullRecord(array $data): bool
    {
        return (bool) $this->insert($data);
    }

    public function removeNullRecord(string $kodebpr, int $periodeId): bool
    {
        return (bool) $this->db->table($this->table)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('is_null_record', 1)
            ->delete();
    }
}