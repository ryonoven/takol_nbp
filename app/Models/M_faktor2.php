<?php
namespace App\Models;

use CodeIgniter\Model;

class M_faktor2 extends Model
{
    protected $table = 'faktor2';
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
            $this->db->query('ALTER TABLE faktor2 AUTO_INCREMENT = 1');
        }
    }

    public function setIncrement($value)
    {
        $value = (int) $value;
        $sql = 'ALTER TABLE faktor2 AUTO_INCREMENT = ?';
        return $this->db->query($sql, [$value]);
    }

    public function getAllData()
    {
        return $this->builder->get()->getResultArray();
    }

    public function getAllDataWithNilai($kodebpr, $periodeId)
    {
        return $this->db->table($this->table)
            ->select('faktor2.*, nilaifaktor2.nilai, nilaifaktor2.keterangan, nilaifaktor2.is_approved, 
                      nilaifaktor2.approved_at, nilaifaktor2.approved_by, nilaifaktor2.accdekom, 
                      nilaifaktor2.accdekom_by, nilaifaktor2.accdekom_at, nilaifaktor2.accdekom2,
                      nilaifaktor2.accdekom2_by, nilaifaktor2.accdir2, nilaifaktor2.accdir2_by,
                      nilaifaktor2.positifstruktur, nilaifaktor2.negatifstruktur,
                      nilaifaktor2.positifproses, nilaifaktor2.negatifproses,
                      nilaifaktor2.positifhasil, nilaifaktor2.negatifhasil,
                      nilaifaktor2.periode_id, nilaifaktor2.kodebpr, nilaifaktor2.nfaktor')
            ->join('nilaifaktor2', 'faktor2.id = nilaifaktor2.faktor1id AND nilaifaktor2.kodebpr = ' . $this->db->escape($kodebpr) . ' AND nilaifaktor2.periode_id = ' . $this->db->escape($periodeId), 'left')
            ->orderBy('faktor2.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getDataByCategory($category, $kodebpr, $periodeId)
    {
        return $this->db->table($this->table)
            ->select('faktor2.*, nilaifaktor2.nilai, nilaifaktor2.keterangan, nilaifaktor2.is_approved')
            ->join('nilaifaktor2', 'faktor2.id = nilaifaktor2.faktor1id AND nilaifaktor2.kodebpr = ' . $this->db->escape($kodebpr) . ' AND nilaifaktor2.periode_id = ' . $this->db->escape($periodeId), 'left')
            ->where('faktor2.sph', $category)
            ->orderBy('faktor2.id', 'ASC')
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