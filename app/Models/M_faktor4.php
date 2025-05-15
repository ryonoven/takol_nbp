<?php
namespace App\Models;

use CodeIgniter\Model;

class M_Faktor4 extends Model
{
    protected $table = 'faktor4';

    public function __construct()
    {
        $this->db = db_connect();
        $this->builder = $this->db->table($this->table);
    }

    public function checkIncrement()
    {
        if (empty($this->getAllData())) {
            $this->db->query('ALTER TABLE faktor4 AUTO_INCREMENT = 1');
        }
    }

    public function setIncrement($value)
    {
        $value = (int) $value;
        $sql = 'ALTER TABLE faktor4 AUTO_INCREMENT = ?';
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

    public function setNullKolom($id)
    {
        return $this->builder->update(
            ['nilai' => null, 'keterangan' => null],
            ['id' => $id]
        );
    }

    public function ubah4($data, $id)
    {
        // Menghapus data berdasarkan ID yang diberikan
        // Menggunakan metode update() pada tabel "" dengan kondisi ID = $id
        return $this->builder->update($data, ['id' => $id]);
    }

}