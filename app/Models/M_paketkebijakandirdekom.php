<?php
namespace App\Models;

use CodeIgniter\Model;

class M_paketkebijakandirdekom extends Model
{
    protected $table = 'paketkebijakandirdekom';

    public function __construct()
    {
        $this->db = db_connect();
        $this->builder = $this->db->table($this->table);
    }  
    
    public function checkIncrement() {
        if(empty($this->getAllData())) {
            $this->db->query('ALTER TABLE paketkebijakandirdekom AUTO_INCREMENT = 1');
        }
    }

    public function setIncrement($value) {
        $value = (int)$value;
        $sql = 'ALTER TABLE paketkebijakandirdekom AUTO_INCREMENT = ?';
        $query = $this->db->query($sql, $value);
        return $query;
    }

    public function getAllData()
    {
        return $this->builder->get()->getResultArray();
    }

    public function tambahgaji($data)
    {
        return $this->builder->insert($data);
    }

    public function tambahtunjangan($data)
    {
        return $this->builder->insert($data);
    }

    public function tambahtantiem($data)
    {
        return $this->builder->insert($data);
    }

    public function tambahsaham($data)
    {
        return $this->builder->insert($data);
    }

    public function tambahremun($data)
    {
        return $this->builder->insert($data);
    }

    public function tambahrumah($data)
    {
        return $this->builder->insert($data);
    }

    public function tambahtransport($data)
    {
        return $this->builder->insert($data);
    }

    public function tambahasuransi($data)
    {
        return $this->builder->insert($data);
    }

    public function tambahfasilitas($data)
    {
        return $this->builder->insert($data);
    }

    public function hapus($id)
    {
        $lastData = $this->builder->orderBy('id', 'DESC')->limit(1)->get()->getResultArray();
        $this->builder->delete(['id' => $id]);
        $this->setIncrement($lastData[0]['id']);
    }

    public function ubah($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }
    public function ubahgaji($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }

    public function ubahtunjangan($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }

    public function ubahtantiem($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }
    public function ubahsaham($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }

    public function ubahremun($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }

    public function ubahrumah($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }

    public function ubahtransport($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }

    public function ubahasuransi($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }

    public function ubahfasilitas($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }

    public function ubahketerangan($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }

}