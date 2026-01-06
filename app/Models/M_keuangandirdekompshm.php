<?php
namespace App\Models;

use CodeIgniter\Model;

class M_keuangandirdekompshm extends Model
{
    protected $table = 'keuangandirdekompshm';
    protected $primaryKey = 'id';
    protected $allowedFields = ['nik', 'nama', 'jabatan', 'hubdir', 'hubdekom', 'hubpshm', 'is_approved', 'approved_by', 'approved_at', 'fullname', 'accdekom', 'accdekom_by', 'accdekom_at', 'kodebpr', 'periode_id', 'user_id'];
    protected $useTimestamps = false;

    public function __construct()
    {
        parent::__construct();
        $this->builder = $this->db->table($this->table);
    }

    public function checkIncrement()
    {
        if (empty($this->getAllData())) {
            $this->db->query('ALTER TABLE keuangandirdekompshm AUTO_INCREMENT = 1');
        }
    }

    public function getAllData()
    {
        return $this->builder->get()->getResultArray();
    }

    public function setIncrement($value)
    {
        $value = (int) $value;
        $sql = 'ALTER TABLE keuangandirdekompshm AUTO_INCREMENT = ?';
        $query = $this->db->query($sql, $value);
        return $query;
    }

    public function tambah($data)
    {
        return $this->builder->insert($data);
    }

    public function hapus($id)
    {
        $lastData = $this->builder->orderBy('id', 'DESC')->limit(1)->get()->getResultArray();
        $this->builder->delete(['id' => $id]);
        $this->setIncrement($lastData[0]['id']);
    }

    public function ubah($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }

    public function getDataByKodebprAndPeriode($kodebpr, $periodeId)
    {
        return $this->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->findAll();
    }
    // Dalam PenjelasantindakModel.php
    public function getDataPenjelasByKodebprAndPeriode($kodebpr, $periodeId)
    {
        return $this->db->table('keuangandirdekompshm')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->get()
            ->getResultArray();
    }

    public function getDataByKodebpr($kodebpr)
    {
        return $this->builder()
            ->where('kodebpr', $kodebpr)
            ->get()
            ->getResultArray();
    }

    public function getKomentarByFaktorIdAndKodebpr($Id, $kodebpr)
    {
        return $this->db->table($this->table)
            ->select('keuangandirdekompshm.*, users.fullname')
            ->join('users', 'users.id = keuangandirdekompshm.user_id', 'left')
            ->where('keuangandirdekompshm.id', $Id)
            ->where('keuangandirdekompshm.kodebpr', $kodebpr)
            ->orderBy('keuangandirdekompshm.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getDataByUserAndPeriod($userId, $kodebpr, $periode)
    {
        return $this->builder
            ->where('user_id', $userId)
            ->where('kodebpr', $kodebpr)
            ->where('periode', $periode)
            ->findAll();
    }

    public function getKomentarByFaktor($Id)
    {
        return $this->db->table('transparansi_comments')
            ->where('id', $Id)
            ->orderBy('date', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getNilaiByFaktor($Id)
    {
        return $this->db->table('keuangandirdekompshm')
            ->where('id', $Id)
            ->orderBy('date', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function editbasedkodedanperiode($data, $kodebpr, $periodeId, $id)
    {
        return $this->db->table('keuangandirdekompshm')
            ->where('id', $id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->update($data);
    }

    public function ubahBerdasarkanFaktorId($data, $keuangandirdekompshm, $kodebpr, $periodeId)
    {        
        $this->builder->where('subkategori', $keuangandirdekompshm)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId);
        return $this->builder->update($data);
    }

}