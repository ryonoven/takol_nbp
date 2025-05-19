<?php
namespace App\Models;

use CodeIgniter\Model;

class M_faktorkomentar extends Model
{
    protected $table = 'faktor_comments'; // Nama tabel untuk menyimpan komentar
    protected $primaryKey = 'id'; // Kolom primary key
    protected $allowedFields = ['faktor1id', 'user_id', 'fullname', 'komentar', 'created_at']; // Kolom yang boleh diinsert/update
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

    public function resetAutoIncrement()
    {
        $this->db->query('ALTER TABLE faktor_comments AUTO_INCREMENT = 1');
    }

    public function getKomentarByFaktor($faktorId)
    {
        return $this->db->table('faktor_comments')
            ->where('faktor1id', $faktorId)  // Filter by faktor1id
            ->orderBy('created_at', 'DESC')  // Optional: Order by creation date (newest first)
            ->get()
            ->getResultArray();
    }


}