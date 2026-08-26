<?php
namespace App\Models;

use CodeIgniter\Model;

class M_periodesemesteran extends Model
{
    protected $table = 'periode_semesteran';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id',
        'kodebpr',
        'tahun',
        'semester',
        'jenispelaporan',
        'modalinti',
        'totalaset',
        'kantorcabang',
        'atmdebit',
        'kategori',
        'nomorsurat',
        'jeniskoreksi'
    ];
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

    public function getPreviousPeriode($kodebpr, $currentPeriodeId)
    {
        $current = $this->find($currentPeriodeId);
        if (!$current)
            return null;

        $currentSemester = (int) $current['semester'];
        $currentTahun = (int) $current['tahun'];

        if ($currentSemester == 1) {
            $prevSemester = 2;
            $prevTahun = $currentTahun - 1;
        } else {
            $prevSemester = 1;
            $prevTahun = $currentTahun;
        }

        return $this->where('kodebpr', $kodebpr)
            ->where('semester', $prevSemester)
            ->where('tahun', $prevTahun)
            ->first();
    }
}