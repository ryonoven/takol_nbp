<?php
namespace App\Models;

use CodeIgniter\Model;

class M_nilaifaktor extends Model
{
    protected $table = 'nilaifaktor';
    protected $primaryKey = 'id';
    protected $allowedFields = ['faktor1id', 'nilai', 'keterangan', 'user_id', 'fullname', 'bpr_id', 'created_at', 'periode', 'is_approved', 'approved_by', 'approved_at'];
    protected $useTimestamps = false;


    public function __construct()
    {
        parent::__construct(); // Call parent constructor for Model initialization
        $this->builder = $this->db->table($this->table); // Get builder for this table
    }
    public function insertNilai($data)
    {
        return $this->insert($data);
    }

    public function getAllData()
    {
        // return $this->findAll();
        return $this->builder->get()->getResultArray();
    }
    public function tambahNilai($data)
    {
        return $this->builder->insert($data);
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


    public function setIncrement($value)
    {
        $value = (int) $value;
        $sql = 'ALTER TABLE nilaifaktor AUTO_INCREMENT = ?';
        $query = $this->db->query($sql, $value);
        return $query;
    }

    public function hapus($faktor1id)
    {
        // Menghapus data berdasarkan faktor1id
        $deleteResult = $this->builder->delete(['faktor1id' => $faktor1id]);

        // Periksa apakah penghapusan berhasil
        if ($deleteResult) {
            return true; // Berhasil
        } else {
            return false; // Gagal
        }
    }

    public function ubahBerdasarkanFaktor1Id($data, $faktor1id)
    {
        // Update data berdasarkan faktor1id
        $this->builder->where('faktor1id', $faktor1id);
        $updateResult = $this->builder->update($data);  // Melakukan update data

        return $updateResult;
    }



    public function ubah($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }

}