<?php
namespace App\Models;

use CodeIgniter\Model;

class M_infobpr extends Model
{
    protected $table = 'infobpr';
    protected $primaryKey = 'id';
    protected $allowedFields = ['logo', 'namabpr', 'kodebpr', 'alamat', 'nomor', 'sandibpr', 'jenis', 'kodejenis', 'kategori', 'email', 'webbpr', 'peringkatkomposit', 'penjelasankomposit'];

    public function getAllData()
    {
        return $this->findAll();
    }

    public function getBprByKode($kodebpr)
    {
        return $this->where('kodebpr', $kodebpr)->first();
    }

    public function tambahinfo($data)
    {
        return $this->insert($data);
    }

    public function ubah($data, $id)
    {
        return $this->update($id, $data);
    }

    public function hapus($id)
    {
        return $this->delete($id);
    }

    public function getKodeBprByUserId($userId)
    {
        return $this->where('user_id', $userId)->first(); // Mengambil data berdasarkan user_id
    }
}