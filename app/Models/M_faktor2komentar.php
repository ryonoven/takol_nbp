<?php
namespace App\Models;

use CodeIgniter\Model;

class M_faktor2komentar extends Model
{
    protected $table = 'faktor2_comments';
    protected $primaryKey = 'id';
    protected $allowedFields = ['faktor2id', 'user_id', 'fullname', 'periode_id', 'komentar', 'kodebpr', 'created_at', 'is_read'];
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
        $this->db->query('ALTER TABLE faktor2_comments AUTO_INCREMENT = 1');
    }

    public function getKomentarByFaktorId($faktor2Id, $kodebpr, $periodeId = null)
    {
        $builder = $this->builder
            ->select('faktor2_comments.*, users.fullname')
            ->join('users', 'users.id = faktor2_comments.user_id', 'left')
            ->where('faktor2_comments.faktor2id', $faktor2Id)
            ->where('faktor2_comments.kodebpr', $kodebpr);

        // Tambahkan filter periode_id jika tersedia
        if ($periodeId !== null) {
            $builder->where('faktor2_comments.periode_id', $periodeId);
        }

        return $builder->orderBy('faktor2_comments.created_at', 'ASC')
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

    // public function countNewComments($kodebpr, $periodeId, $lastVisit)
    // {
    //     return $this->builder
    //         ->select('faktor2id, COUNT(*) as jumlah')
    //         ->where('kodebpr', $kodebpr)
    //         ->where('periode_id', $periodeId)
    //         ->where('created_at >', $lastVisit)
    //         ->groupBy('faktor2id')
    //         ->get()
    //         ->getResultArray();
    // }
}