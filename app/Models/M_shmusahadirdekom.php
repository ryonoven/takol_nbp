<?php
namespace App\Models;

use CodeIgniter\Model;

class M_shmusahadirdekom extends Model
{
    protected $table = 'shmusahadirdekom';

    public function __construct()
    {
        $this->db = db_connect();
        $this->builder = $this->db->table($this->table);
    }

    public function checkIncrement()
    {
        if (empty($this->getAllData())) {
            $this->db->query('ALTER TABLE shmusahadirdekom AUTO_INCREMENT = 1');
        }
    }

    public function setIncrement($value)
    {
        $value = (int) $value;
        $sql = 'ALTER TABLE shmusahadirdekom AUTO_INCREMENT = ?';
        $query = $this->db->query($sql, $value);
        return $query;
    }

    public function getAllData($filter_columns = [])
    {
        $builder = $this->builder;

        if (!empty($filter_columns)) {
            foreach ($filter_columns as $column) {
                $builder->where($column . ' IS NOT NULL', null, false);
                $builder->where($column . ' !=', '');
            }
        }

        return $builder->get()->getResultArray();
    }

    public function tambahsahamdir($data)
    {
        return $this->builder->insert($data);
    }
    public function tambahsahamdekom($data)
    {
        return $this->builder->insert($data);
    }

    public function tambahsahampshm($data)
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