<?php

namespace App\Models;

use CodeIgniter\Model;

class Faktor12DataModel extends Model
{
    protected $table = 'faktor12_data';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'masuktxt', 'flagdetail', 'penggunaan', 'plusmin', 'number', 'sph', 'category', 'sub_category'
    ];
    protected $useTimestamps = true;
}