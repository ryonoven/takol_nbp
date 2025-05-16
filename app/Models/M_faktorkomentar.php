<?php
namespace App\Models;

use CodeIgniter\Model;

class M_faktorkomentar extends Model
{
    protected $table = 'faktor_comments'; // Nama tabel untuk menyimpan komentar
    protected $primaryKey = 'id'; // Kolom primary key
    protected $allowedFields = ['faktor1id', 'user_id', 'komentar', 'created_at']; // Kolom yang boleh diinsert/update
    protected $useTimestamps = false; // Jika tidak menggunakan timestamp otomatis

    // Fungsi untuk menambahkan komentar
    public function insertKomentar($data)
    {
        return $this->insert($data); // Menyimpan data komentar
    }

    // Fungsi untuk mengambil semua komentar
    public function getAllData()
    {
        return $this->findAll(); // Mengambil semua data komentar
    }
}