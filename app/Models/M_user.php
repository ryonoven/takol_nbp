<?php
namespace App\Models;

use CodeIgniter\Model;

class M_user extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $allowedFields = ['email', 'username', 'fullname', 'user_image']; // tambahkan sesuai kolom tabel
}
