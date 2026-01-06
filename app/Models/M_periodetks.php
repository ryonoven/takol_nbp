<?php
namespace App\Models;

use CodeIgniter\Model;

class M_periodetks extends Model
{
    protected $table = 'periodetks';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'kodebpr', 'tahun', 'semester', 'jenispelaporan', 'modalinti', 'totalaset'];
    protected $useTimestamps = true;

    public function getPeriodeByUser($userId)
    {
        return $this->where('user_id', $userId)->findAll();
    }

    public function getPeriodeByBpr($kodebpr)
    {
        return $this->where('kodebpr', $kodebpr)->findAll();
    }

    public function getPeriodeDetail($periodeId)
    {
        return $this->where('id', $periodeId)->first();
    }

    public function getModalintiDetail($periodeId)
    {
        return $this->where('modalinti', $periodeId)->first();
    }

    public function getTotalasetDetail($periodeId)
    {
        return $this->where('totalaset', $periodeId)->first();
    }
}