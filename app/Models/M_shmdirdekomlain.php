<?php
namespace App\Models;

use CodeIgniter\Model;

class M_shmdirdekomlain extends Model
{
    protected $table = 'shmdirdekomlain';

    public function __construct()
    {
        $this->db = db_connect();
        $this->builder = $this->db->table($this->table);
    }  
    
    public function checkIncrement() {
        if(empty($this->getAllData())) {
            $this->db->query('ALTER TABLE shmdirdekomlain AUTO_INCREMENT = 1');
        }
    }

    public function setIncrement($value) {
        $value = (int)$value;
        $sql = 'ALTER TABLE shmdirdekomlain AUTO_INCREMENT = ?';
        $query = $this->db->query($sql, $value);
        return $query;
    }

    public function getAllData()
    {
        return $this->builder->get()->getResultArray();
    }

    public function tambahsahamdir($data)
    {
        return $this->builder->insert($data);
    }
    public function tambahsahamdekom($data)
    {
        return $this->builder->insert($data);
    }

    public function hapus($id)
    {
        // Menghapus data siswa berdasar    an ID yang diberikan
        // Menggunakan metode delete() pada tabel "" dengan kondisi ID = $id
        $lastData = $this->builder->orderBy('id', 'DESC')->limit(1)->get()->getResultArray();
        $this->builder->delete(['id' => $id]);
        $this->setIncrement($lastData[0]['id']);
    }

    public function ubahdir($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }

    public function ubahdekom($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }

    public function ubahketerangan($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }

}