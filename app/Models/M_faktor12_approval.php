<?php

namespace App\Models;

use CodeIgniter\Model;

class Faktor12ApprovalModel extends Model
{
    protected $table = 'faktor12_approval';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'faktor_id',
        'nilai',
        'keterangan',
        'is_approved',
        'approved_by',
        'approved_at'
    ];
    protected $useTimestamps = true;
}