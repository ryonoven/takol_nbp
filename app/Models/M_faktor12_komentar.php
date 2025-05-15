<?php

namespace App\Models;

use CodeIgniter\Model;

class Faktor12KomentarModel extends Model
{
    protected $table = 'faktor12_komentar';
    protected $primaryKey = 'id';
    protected $allowedFields = ['faktor_id', 'komentar', 'created_by'];
    protected $useTimestamps = true;
}