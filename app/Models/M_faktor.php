<?php
namespace App\Models;

use CodeIgniter\Model;

class M_faktor extends Model
{
    protected $table = 'faktor';
    protected $primaryKey = 'id';
    protected $allowedFields = ['sub_category', 'sph'];
    protected $useTimestamps = false;

    public function __construct()
    {
        parent::__construct();
        $this->db = db_connect();
        $this->builder = $this->db->table($this->table);
    }

    public function checkIncrement()
    {
        if (empty($this->getAllData())) {
            $this->db->query('ALTER TABLE faktor AUTO_INCREMENT = 1');
        }
    }

    public function setIncrement($value)
    {
        $value = (int) $value;
        $sql = 'ALTER TABLE faktor AUTO_INCREMENT = ?';
        return $this->db->query($sql, [$value]);
    }

    public function getAllData()
    {
        return $this->builder->get()->getResultArray();
    }

    public function getAllDataWithNilai($kodebpr, $periodeId)
    {
        return $this->db->table($this->table)
            ->select('faktor.*, nilaifaktor.nilai, nilaifaktor.keterangan, nilaifaktor.is_approved, 
                      nilaifaktor.approved_at, nilaifaktor.approved_by, nilaifaktor.accdekom, 
                      nilaifaktor.accdekom_by, nilaifaktor.accdekom_at, nilaifaktor.accdekom2,
                      nilaifaktor.accdekom2_by, nilaifaktor.accdir2, nilaifaktor.accdir2_by,
                      nilaifaktor.positifstruktur, nilaifaktor.negatifstruktur,
                      nilaifaktor.positifproses, nilaifaktor.negatifproses,
                      nilaifaktor.positifhasil, nilaifaktor.negatifhasil,
                      nilaifaktor.periode_id, nilaifaktor.kodebpr, nilaifaktor.nfaktor')
            ->join('nilaifaktor', 'faktor.id = nilaifaktor.faktor1id AND nilaifaktor.kodebpr = ' . $this->db->escape($kodebpr) . ' AND nilaifaktor.periode_id = ' . $this->db->escape($periodeId), 'left')
            ->orderBy('faktor.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getDataByCategory($category, $kodebpr, $periodeId)
    {
        return $this->db->table($this->table)
            ->select('faktor.*, nilaifaktor.nilai, nilaifaktor.keterangan, nilaifaktor.is_approved')
            ->join('nilaifaktor', 'faktor.id = nilaifaktor.faktor1id AND nilaifaktor.kodebpr = ' . $this->db->escape($kodebpr) . ' AND nilaifaktor.periode_id = ' . $this->db->escape($periodeId), 'left')
            ->where('faktor.sph', $category)
            ->orderBy('faktor.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function setNullKolom($id)
    {
        return $this->builder->update(
            ['nilai' => null, 'keterangan' => null],
            ['id' => $id]
        );
    }

    public function getDataByKodebprAndPeriode($kodebpr, $periodeId)
    {
        return $this->where([
            'kodebpr' => $kodebpr,
            'periode_id' => $periodeId
        ])->findAll();
    }

}