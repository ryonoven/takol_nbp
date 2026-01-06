<?php
namespace App\Models;

use CodeIgniter\Model;

class M_profilrisikocomments extends Model
{
    protected $table = 'profilrisiko_comments';
    protected $primaryKey = 'id';
    protected $allowedFields = ['subkategori', 'faktor1id', 'user_id', 'fullname', 'komentar', 'created_at', 'kodebpr', 'periode_id'];
    protected $useTimestamps = false;

    public function __construct()
    {
        parent::__construct();
        $this->builder = $this->db->table($this->table);
    }

    public function insertKomentar($data, $faktorId)
    {
        return $this->insert($data);
    }

    public function getAllData()
    {
        return $this->findAll();
    }

    public function resetAutoIncrement()
    {
        $this->db->query('ALTER TABLE profilrisiko_comments AUTO_INCREMENT = 1');
    }

    public function getKomentarByFaktorId($subkategori, $faktorId ,$kodebpr, $periodeId = null)
    {
        $builder = $this->builder
            ->select('profilrisiko_comments.*, users.fullname')
            ->join('users', 'users.id = profilrisiko_comments.user_id', 'left')
            // ->where('profilrisiko_comments.id', $Id)
            ->where('profilrisiko_comments.faktor1id', $faktorId)
            ->where('profilrisiko_comments.subkategori', $subkategori)
            ->where('profilrisiko_comments.kodebpr', $kodebpr);

        if ($periodeId !== null) {
            $builder->where('profilrisiko_comments.periode_id', $periodeId);
        }

        return $builder->orderBy('profilrisiko_comments.created_at', 'ASC')
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

    public function getKomentarBySubkategori($subkategori, $kodebpr, $faktor1id, $periodeId)
    {
        $comments = $this->db->table('profilrisiko_comments')
            ->select('id, komentar, faktor1id, fullname, created_at')
            ->where('subkategori', $subkategori)
            ->where('faktor1id', $faktor1id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->get()
            ->getResultArray();

        return $this->response->setJSON($comments); 
    }

}