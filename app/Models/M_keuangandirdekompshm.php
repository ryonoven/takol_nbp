<?php
namespace App\Models;

use CodeIgniter\Model;

class M_keuangandirdekompshm extends Model
{
    protected $table = 'keuangandirdekompshm';

    public function __construct()
    {
        $this->db = db_connect();
        $this->builder = $this->db->table($this->table);
    }  
    
    public function checkIncrement() {
        if(empty($this->getAllData())) {
            $this->db->query('ALTER TABLE keuangandirdekompshm AUTO_INCREMENT = 1');
        }
    }

    public function setIncrement($value) {
        $value = (int)$value;
        $sql = 'ALTER TABLE keuangandirdekompshm AUTO_INCREMENT = ?';
        $query = $this->db->query($sql, $value);
        return $query;
    }

    public function getAllData()
    {
        return $this->builder->get()->getResultArray();
    }

    public function tambahuangdir($data)
    {
        return $this->builder->insert($data);
    }
    public function tambahuangdekom($data)
    {
        return $this->builder->insert($data);
    }

    public function tambahuangpshm($data)
    {
        return $this->builder->insert($data);
    }

    public function hapus($id)
    {
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

    public function ubahpshm($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }

    public function ubahketerangan($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }

}