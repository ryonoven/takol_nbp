<?php
namespace App\Models;

use CodeIgniter\Model;

class M_fraudinternal extends Model
{
    protected $table = 'fraudinternal';

    public function __construct()
    {
        $this->db = db_connect();
        $this->builder = $this->db->table($this->table);
    }  
    
    public function checkIncrement() {
        if(empty($this->getAllData())) {
            $this->db->query('ALTER TABLE fraudinternal AUTO_INCREMENT = 1');
        }
    }

    public function setIncrement($value) {
        $value = (int)$value;
        $sql = 'ALTER TABLE fraudinternal AUTO_INCREMENT = ?';
        $query = $this->db->query($sql, $value);
        return $query;
    }

    public function getAllData()
    {
        return $this->builder->get()->getResultArray();
    }

    public function tambahfrauddir($data)
    {
        return $this->builder->insert($data);
    }

    public function tambahfrauddekom($data)
    {
        return $this->builder->insert($data);
    }

    public function tambahfraudkartap($data)
    {
        return $this->builder->insert($data);
    }

    public function tambahfraudkontrak($data)
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

    public function ubah($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }
    public function ubahfrauddir($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }

    public function ubahfrauddekom($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }

    public function ubahfraudkartap($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }
    public function ubahfraudkontrak($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }

    public function ubahketerangan($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }

}