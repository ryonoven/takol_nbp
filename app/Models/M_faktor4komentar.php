<?php
namespace App\Models;

use CodeIgniter\Model;

class M_faktor4komentar extends Model
{
    protected $table = 'faktor4_comments';
    protected $primaryKey = 'id';
    protected $allowedFields = ['faktor4id', 'user_id', 'fullname', 'periode_id', 'komentar', 'kodebpr', 'created_at', 'is_read'];
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
        $this->db->query('ALTER TABLE faktor4_comments AUTO_INCREMENT = 1');
    }

    public function getKomentarByFaktorId($faktor4Id, $kodebpr, $periodeId = null)
    {
        $builder = $this->builder
            ->select('faktor4_comments.*, users.fullname')
            ->join('users', 'users.id = faktor4_comments.user_id', 'left')
            ->where('faktor4_comments.faktor4id', $faktor4Id)
            ->where('faktor4_comments.kodebpr', $kodebpr);

        // Tambahkan filter periode_id jika tersedia
        if ($periodeId !== null) {
            $builder->where('faktor4_comments.periode_id', $periodeId);
        }

        return $builder->orderBy('faktor4_comments.created_at', 'ASC')
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
            ->select('faktor4id, COUNT(*) as jumlah')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('created_at >', $lastVisit)
            ->groupBy('faktor4id')
            ->get()
            ->getResultArray();
    }
}