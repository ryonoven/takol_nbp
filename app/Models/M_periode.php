<?php
namespace App\Models;

use CodeIgniter\Model;

class M_periode extends Model
{
    protected $table = 'periode_pelaporan';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'kodebpr', 'tahun', 'semester'];
    protected $useTimestamps = true;

    public function getPeriodeByUser($userId)
    {
        return $this->where('user_id', $userId)->findAll();
    }

    public function getPeriodeByBpr($kodebpr)
    {
        return $this->where('kodebpr', $kodebpr)->findAll();
    }

    // Di App\Models\M_periode.php
    public function getPeriodeDetail($periodeId)
    {
        return $this->where('id', $periodeId)->first();
    }
}