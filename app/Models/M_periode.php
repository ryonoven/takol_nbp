<?php
namespace App\Models;

use CodeIgniter\Model;

class M_periode extends Model
{
    protected $table = 'periode_semesteran';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'kodebpr', 'tahun', 'semester', 'modalinti', 'totalaset', 'jenispelaporan', 'kategori'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation rules
    protected $validationRules = [
        'user_id' => 'required|integer',
        'kodebpr' => 'required',
        'tahun' => 'required|integer',
        'semester' => 'required|in_list[1,2]',
        'modalinti' => 'permit_empty|decimal',
        'totalaset' => 'permit_empty|decimal',
        'jenispelaporan' => 'required|in_list[R,K]',
        'kategori' => 'permit_empty|in_list[A,B]'
    ];

    protected $validationMessages = [
        'user_id' => [
            'required' => 'User ID harus diisi',
            'integer' => 'User ID harus berupa angka'
        ],
        'kodebpr' => [
            'required' => 'Kode BPR harus diisi'
        ],
        'tahun' => [
            'required' => 'Tahun harus diisi',
            'integer' => 'Tahun harus berupa angka'
        ],
        'semester' => [
            'required' => 'Semester harus diisi',
            'in_list' => 'Semester harus 1 atau 2'
        ],
        'jenispelaporan' => [
            'required' => 'Jenis pelaporan harus diisi',
            'in_list' => 'Jenis pelaporan tidak valid'
        ]
    ];

    public function getPeriodeByUser($userId)
    {
        return $this->where('user_id', $userId)
            ->orderBy('tahun', 'DESC')
            ->orderBy('semester', 'DESC')
            ->findAll();
    }

    public function getPeriodeByBpr($kodebpr)
    {
        return $this->where('kodebpr', $kodebpr)
            ->orderBy('tahun', 'DESC')
            ->orderBy('semester', 'DESC')
            ->findAll();
    }

    public function getPeriodeDetail($periodeId)
    {
        return $this->where('id', $periodeId)->first();
    }

    // Check if periode exists
    public function isPeriodeExists($kodebpr, $tahun, $semester)
    {
        return $this->where([
            'kodebpr' => $kodebpr,
            'tahun' => $tahun,
            'semester' => $semester
        ])->first() !== null;
    }
}