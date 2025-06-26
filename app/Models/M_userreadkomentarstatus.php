<?php namespace App\Models;

use CodeIgniter\Model;

class M_userreadkomentarstatus extends Model
{
    protected $table      = 'user_read_komentar_status';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType     = 'array'; // Atau 'object'
    protected $useSoftDeletes = false;

    protected $allowedFields = ['user_id', 'faktor1id', 'kodebpr','periode_id', 'last_read_at'];

    // Kita akan mengelola timestamps secara manual untuk last_read_at
    protected $useTimestamps = false; 

    protected $validationRules    = [];
    protected $validationMessages = [];
    protected $skipValidation     = false;
}