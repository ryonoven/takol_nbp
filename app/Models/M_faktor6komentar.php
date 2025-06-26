<?php
namespace App\Models;

use CodeIgniter\Model;

class M_faktor6komentar extends Model
{
    protected $table = 'faktor6_comments';
    protected $primaryKey = 'id';
    protected $allowedFields = ['faktor6id', 'user_id', 'fullname', 'periode_id', 'komentar', 'kodebpr', 'created_at', 'is_read'];
    protected $useTimestamps = false;

    public function __construct()
    {
        parent::__construct();
        $this->builder = $this->db->table($this->table);
    }

    public function insertKomentar($data)
    {
        return $this->insert($data);
    }

    public function getAllData()
    {
        return $this->findAll();
    }

    public function resetAutoIncrement()
    {
        $this->db->query('ALTER TABLE faktor6_comments AUTO_INCREMENT = 1');
    }

    public function getKomentarByFaktorId($faktor6Id, $kodebpr, $periodeId = null)
    {
        $builder = $this->builder
            ->select('faktor6_comments.*, users.fullname')
            ->join('users', 'users.id = faktor6_comments.user_id', 'left')
            ->where('faktor6_comments.faktor6id', $faktor6Id)
            ->where('faktor6_comments.kodebpr', $kodebpr);

        // Tambahkan filter periode_id jika tersedia
        if ($periodeId !== null) {
            $builder->where('faktor6_comments.periode_id', $periodeId);
        }

        return $builder->orderBy('faktor6_comments.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getDataByKodebprAndPeriode($kodebpr, $periodeId)
    {
        return $this->builder
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->get()
            ->getResultArray();
    }

    public function countNewComments($kodebpr, $periodeId, $lastVisit)
    {
        return $this->builder
            ->select('faktor6id, COUNT(*) as jumlah')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('created_at >', $lastVisit)
            ->groupBy('faktor6id')
            ->get()
            ->getResultArray();
    }
}