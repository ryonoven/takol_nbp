<?php
namespace App\Models;

use CodeIgniter\Model;

class M_user extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $allowedFields = ['email', 'username', 'fullname', 'user_image']; // tambahkan sesuai kolom tabel


    public function getUserWithBpr($userId)
    {
        return $this->db->table('users')
            ->select('users.*, infobpr.kodebpr, infobpr.namabpr')
            ->join('infobpr', 'users.kodebpr = infobpr.id', 'left')
            ->where('users.id', $userId)
            ->get()
            ->getRowArray();
    }


}
