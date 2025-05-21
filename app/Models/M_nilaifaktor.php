<?php
namespace App\Models;

use CodeIgniter\Model;

class M_nilaifaktor extends Model
{
    protected $table = 'nilaifaktor';
    protected $primaryKey = 'id';
    protected $allowedFields = ['faktor1id', 'nilai', 'keterangan', 'user_id' , 'fullname' , 'bpr_id' , 'created_at', 'periode' ,'is_approved', 'approved_by', 'approved_at'];
    protected $useTimestamps = false;

    public function insertNilai($data)
    {
        return $this->insert($data);
    }

    public function getAllData()
    {
        return $this->findAll();
    }

    public function resetAutoIncrement()
    {
        $this->db->query('ALTER TABLE nilaifaktor AUTO_INCREMENT = 1');
    }

    public function getKomentarByFaktorId($faktorId)
    {
        return $this->db->table($this->table)
            ->select('nilaifaktor.*, users.fullname')
            ->join('users', 'users.id = nilaifaktor.user_id', 'left')
            ->where('nilaifaktor.faktor1id', $faktorId) // INI KUNCI FILTERING
            ->orderBy('nilaifaktor.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }
}