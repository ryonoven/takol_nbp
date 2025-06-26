<?php
namespace App\Models;

use CodeIgniter\Model;

class M_faktor8komentar extends Model
{
    protected $table = 'faktor8_comments';
    protected $primaryKey = 'id';
    protected $allowedFields = ['faktor8id', 'user_id', 'fullname', 'periode_id', 'komentar', 'kodebpr', 'created_at', 'is_read'];
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
        $this->db->query('ALTER TABLE faktor8_comments AUTO_INCREMENT = 1');
    }

    public function getKomentarByFaktorId($faktor8Id, $kodebpr, $periodeId = null)
    {
        $builder = $this->builder
            ->select('faktor8_comments.*, users.fullname')
            ->join('users', 'users.id = faktor8_comments.user_id', 'left')
            ->where('faktor8_comments.faktor8id', $faktor8Id)
            ->where('faktor8_comments.kodebpr', $kodebpr);

        // Tambahkan filter periode_id jika tersedia
        if ($periodeId !== null) {
            $builder->where('faktor8_comments.periode_id', $periodeId);
        }

        return $builder->orderBy('faktor8_comments.created_at', 'ASC')
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
            ->select('faktor8id, COUNT(*) as jumlah')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('created_at >', $lastVisit)
            ->groupBy('faktor8id')
            ->get()
            ->getResultArray();
    }
}