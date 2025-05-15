<?php
namespace App\Models;

use CodeIgniter\Model;

class M_Bisnis extends Model
{
    protected $table = 'form_bisnis';

    public function __construct()
    {
        $this->db = db_connect();
        $this->builder = $this->db->table($this->table);
    }  
    
    public function checkIncrement() {
        if(empty($this->getAllData())) {
            $this->db->query('ALTER TABLE form_bisnis AUTO_INCREMENT = 1');
        }
    }

    public function setIncrement($value) {  
        $value = (int)$value;
        $sql = 'ALTER TABLE form_bisnis AUTO_INCREMENT = ?';
        $query = $this->db->query($sql, $value);
        return $query;
    }

    public function getAllData()
    {
        return $this->builder->get()->getResultArray();
    }

    public function tambah($data)
    {
        return $this->builder->insert($data);
    }

    public function hapus($id)
    {
        // Menghapus data siswa berdasar    an ID yang diberikan
        // Menggunakan metode delete() pada tabel "siswa" dengan kondisi ID = $id
        $lastData = $this->builder->orderBy('id', 'DESC')->limit(1)->get()->getResultArray();
        $this->builder->delete(['id' => $id]);
        $this->setIncrement($lastData[0]['id']);
    }

    public function ubah($data, $id)
    {
        // Menghapus data siswa berdasarkan ID yang diberikan
        // Menggunakan metode update() pada tabel "siswa" dengan kondisi ID = $id
        return $this->builder->update($data, ['id' => $id]);
    }

}