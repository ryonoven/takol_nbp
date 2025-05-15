<?php
namespace App\Models;

use CodeIgniter\Model;

class M_Faktor extends Model
{
    protected $table = 'faktor';

    public function __construct()
    {
        $this->db = db_connect();
        $this->builder = $this->db->table($this->table);
    }  

    public function checkIncrement(){
        if (empty($this->getAllData())) {
            $this->db->query('ALTER TABLE faktor AUTO_INCREMENT = 1');
        }
    }

    public function setIncrement($value)
    {
        $value = (int)$value;
        $sql = 'ALTER TABLE faktor AUTO_INCREMENT = ?';
        $query = $this->db->query($sql, $value);
        return $query;
    }

    public function getAllData()
    {
        return $this->builder->get()->getResultArray();
    }

    // Function menambah komentar dewan komisaris
    public function tambahKomentar($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }

    // Mengubah data berdasarkan ID yang diberikan
    public function ubah($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }

    // Function menambah data
    // public function tambahF($data)
    // {
    //     return $this->builder->insert($data);
    // }

    // Menghapus data sop berdasarkan ID yang diberikan
    // Menggunakan metode delete() pada tabel "" dengan kondisi ID = $id
    // public function hapus($id)
    // {    
    //     $lastData = $this->builder->orderBy('id', 'DESC')->limit(1)->get()->getResultArray();
    //     $this->builder->delete(['id' => $id]);
    //     $this->setIncrement($lastData[0]['id']);
    // }

}