<?php
namespace App\Models;

use CodeIgniter\Model;

class M_profilrisikocomments extends Model
{
    protected $table = 'profilrisiko_comments';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'subkategori',
        'faktor1id',
        'user_id',
        'fullname',
        'komentar',
        'created_at',
        'kodebpr',
        'periode_id',
    ];
    protected $useTimestamps = false;
    protected $returnType = 'array';

    public function insertKomentar(array $data): bool
    {
        return (bool) $this->insert($data);
    }

    public function getAllData(): array
    {
        return $this->findAll();
    }

    public function getKomentarByFaktorId(
        string $subkategori,
        $faktorId,
        string $kodebpr,
        $periodeId = null
    ): array {
        $builder = $this->db->table('profilrisiko_comments')
            ->select('profilrisiko_comments.*, users.fullname')
            ->join('users', 'users.id = profilrisiko_comments.user_id', 'left')
            ->where('profilrisiko_comments.subkategori', $subkategori)
            ->where('profilrisiko_comments.faktor1id', $faktorId)
            ->where('profilrisiko_comments.kodebpr', $kodebpr);

        if ($periodeId !== null) {
            $builder->where('profilrisiko_comments.periode_id', $periodeId);
        }

        return $builder
            ->orderBy('profilrisiko_comments.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getDataByKodebprAndPeriode(string $kodebpr, $periodeId): array
    {
        return $this->db->table('profilrisiko_comments')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->get()
            ->getResultArray();
    }

    public function getKomentarBySubkategori(string $subkategori, string $kodebpr, $periodeId): array
    {
        return $this->db->table('profilrisiko_comments')
            ->select('id, komentar, fullname, created_at')
            ->where('subkategori', $subkategori)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->orderBy('created_at', 'ASC')
            ->get()
            ->getResultArray();
    }
}