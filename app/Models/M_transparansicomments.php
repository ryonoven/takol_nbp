<?php
namespace App\Models;

use CodeIgniter\Model;

class M_transparansicomments extends Model
{
    protected $table = 'transparansi_comments';
    protected $primaryKey = 'id';
    protected $allowedFields = ['subkategori', 'user_id', 'fullname', 'komentar', 'created_at', 'kodebpr', 'periode_id', 'is_read'];
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
        $this->db->query('ALTER TABLE transparansi_comments AUTO_INCREMENT = 1');
    }

    public function getKomentarByFaktorId($subkategori, $kodebpr, $periodeId = null)
    {
        $builder = $this->builder
            ->select('transparansi_comments.*, users.fullname')
            ->join('users', 'users.id = transparansi_comments.user_id', 'left')
            // ->where('transparansi_comments.id', $Id)
            ->where('transparansi_comments.subkategori', $subkategori)
            ->where('transparansi_comments.kodebpr', $kodebpr);

        if ($periodeId !== null) {
            $builder->where('transparansi_comments.periode_id', $periodeId);
        }

        return $builder->orderBy('transparansi_comments.created_at', 'ASC')
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

    public function getKomentarBySubkategori($subkategori, $kodebpr, $periodeId)
    {
        $comments = $this->db->table('transparansi_comments')
            ->select('id, komentar, fullname, created_at')
            ->where('subkategori', $subkategori)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->get()
            ->getResultArray();

        return $this->response->setJSON($comments); 
    }

}