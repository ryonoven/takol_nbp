<?php
namespace App\Models;

use CodeIgniter\Model;

class M_faktor12komentar extends Model
{
    protected $table = 'faktor12_comments';
    protected $primaryKey = 'id';
    protected $allowedFields = ['faktor12id', 'user_id', 'fullname', 'periode_id', 'komentar', 'kodebpr', 'created_at', 'is_read'];
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
        $this->db->query('ALTER TABLE faktor12_comments AUTO_INCREMENT = 1');
    }

    public function getKomentarByFaktorId($faktor12Id, $kodebpr, $periodeId = null)
    {
        $builder = $this->builder
            ->select('faktor12_comments.*, users.fullname')
            ->join('users', 'users.id = faktor12_comments.user_id', 'left')
            ->where('faktor12_comments.faktor12id', $faktor12Id)
            ->where('faktor12_comments.kodebpr', $kodebpr);

        // Tambahkan filter periode_id jika tersedia
        if ($periodeId !== null) {
            $builder->where('faktor12_comments.periode_id', $periodeId);
        }

        return $builder->orderBy('faktor12_comments.created_at', 'ASC')
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
            ->select('faktor12id, COUNT(*) as jumlah')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('created_at >', $lastVisit)
            ->groupBy('faktor12id')
            ->get()
            ->getResultArray();
    }
}