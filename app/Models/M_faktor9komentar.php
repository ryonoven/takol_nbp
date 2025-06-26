<?php
namespace App\Models;

use CodeIgniter\Model;

class M_faktor9komentar extends Model
{
    protected $table = 'faktor9_comments';
    protected $primaryKey = 'id';
    protected $allowedFields = ['faktor9id', 'user_id', 'fullname', 'periode_id', 'komentar', 'kodebpr', 'created_at', 'is_read'];
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
        $this->db->query('ALTER TABLE faktor9_comments AUTO_INCREMENT = 1');
    }

    public function getKomentarByFaktorId($faktor9Id, $kodebpr, $periodeId = null)
    {
        $builder = $this->builder
            ->select('faktor9_comments.*, users.fullname')
            ->join('users', 'users.id = faktor9_comments.user_id', 'left')
            ->where('faktor9_comments.faktor9id', $faktor9Id)
            ->where('faktor9_comments.kodebpr', $kodebpr);

        // Tambahkan filter periode_id jika tersedia
        if ($periodeId !== null) {
            $builder->where('faktor9_comments.periode_id', $periodeId);
        }

        return $builder->orderBy('faktor9_comments.created_at', 'ASC')
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
            ->select('faktor9id, COUNT(*) as jumlah')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('created_at >', $lastVisit)
            ->groupBy('faktor9id')
            ->get()
            ->getResultArray();
    }
}