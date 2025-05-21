<?php
namespace App\Models;

use CodeIgniter\Model;

class M_faktorkomentar extends Model
{
    protected $table = 'faktor_comments';
    protected $primaryKey = 'id';
    protected $allowedFields = ['faktor1id', 'user_id', 'fullname', 'komentar', 'created_at'];
    protected $useTimestamps = false;

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
        $this->db->query('ALTER TABLE faktor_comments AUTO_INCREMENT = 1');
    }

    public function getKomentarByFaktorId($faktorId)
    {
        return $this->db->table($this->table)
            ->select('faktor_comments.*, users.fullname')
            ->join('users', 'users.id = faktor_comments.user_id', 'left')
            ->where('faktor_comments.faktor1id', $faktorId) // INI KUNCI FILTERING
            ->orderBy('faktor_comments.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }
}