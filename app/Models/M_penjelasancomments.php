<?php
namespace App\Models;

use CodeIgniter\Model;

class M_penjelasancomments extends Model
{
    protected $table = 'penjelasan_comments';
    protected $primaryKey = 'id';
    protected $allowedFields = ['id', 'user_id', 'fullname', 'komentar', 'created_at','kodebpr', 'periode_id'];
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
        $this->db->query('ALTER TABLE penjelasan_comments AUTO_INCREMENT = 1');
    }

    public function getKomentarByFaktorId($Id, $kodebpr, $periodeId = null)
    {
        $builder = $this->builder
            ->select('penjelasan_comments.*, users.fullname')
            ->join('users', 'users.id = penjelasan_comments.user_id', 'left')
            ->where('penjelasan_comments.id', $Id)
            ->where('penjelasan_comments.kodebpr', $kodebpr);

        // Tambahkan filter periode_id jika tersedia
        if ($periodeId !== null) {
            $builder->where('penjelasan_comments.periode_id', $periodeId);
        }

        return $builder->orderBy('penjelasan_comments.created_at', 'ASC')
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
}