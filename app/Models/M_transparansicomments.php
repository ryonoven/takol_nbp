<?php
namespace App\Models;

use CodeIgniter\Model;

class M_transparansicomments extends Model
{
    protected $table = 'transparansi_comments';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'subkategori',
        'user_id',
        'fullname',
        'komentar',
        'created_at',
        'kodebpr',
        'periode_id',
        'is_read',
    ];
    protected $useTimestamps = false;

    public function insertKomentar(array $data): bool|int
    {
        return $this->insert($data);
    }

    public function getKomentarByPeriode(
        string $kodebpr,
        int $periodeId,
        string $kategori,
        string $subkategori
    ): array {
        return $this->db->table('transparansi_comments AS c')
            ->select('c.*, u.fullname')
            ->join('users AS u', 'u.id = c.user_id', 'left')
            ->where('c.kodebpr', $kodebpr)
            ->where('c.periode_id', $periodeId)
            ->where('c.subkategori', $subkategori)
            ->orderBy('c.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getKomentarByFaktorId(
        string $subkategori,
        string $kodebpr,
        ?int $periodeId = null
    ): array {
        $builder = $this->db->table('transparansi_comments AS c')
            ->select('c.*, u.fullname')
            ->join('users AS u', 'u.id = c.user_id', 'left')
            ->where('c.subkategori', $subkategori)
            ->where('c.kodebpr', $kodebpr);

        if ($periodeId !== null) {
            $builder->where('c.periode_id', $periodeId);
        }

        return $builder->orderBy('c.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getAllData(): array
    {
        return $this->findAll();
    }

    public function getDataByKodebprAndPeriode(string $kodebpr, int $periodeId): array
    {
        return $this->db->table($this->table)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->get()
            ->getResultArray();
    }

    public function resetAutoIncrement(): void
    {
        $this->db->query('ALTER TABLE transparansi_comments AUTO_INCREMENT = 1');
    }
}