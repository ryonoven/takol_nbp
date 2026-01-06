<?php
namespace App\Models;

use CodeIgniter\Model;

class M_strukturkomite extends Model
{
    protected $table = 'strukturkomite';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'kodebpr', 'periode_id', 'anggotakomite', 'nikkomite', 'keahlian', 'fullname', 'jbtaudit', 'jbtpantauresiko', 'jbtremunerasi', 'jbtmanrisk', 'jbtlain', 'independen', 'tindakstrukturkomite', 'is_approved', 'approved_by', 'approved_at', 'fullname', 'accdekom', 'accdekom_by', 'accdekom_at', 'date', 'accdekom2', 'accdekom2_by', 'accdekom3', 'accdekom3_by', 'accdekom4', 'accdekom4_by', 'accdekom5', 'accdekom5_by', 'accdir2', 'accdir2_by'];
    protected $useTimestamps = false;

    public function __construct()
    {
        parent::__construct();
        $this->builder = $this->db->table($this->table);
    }

    public function checkIncrement()
    {
        if (empty($this->getAllData())) {
            $this->db->query('ALTER TABLE strukturkomite AUTO_INCREMENT = 1');
        }
    }

    public function setIncrement($value)
    {
        $value = (int) $value;
        $sql = 'ALTER TABLE strukturkomite AUTO_INCREMENT = ?';
        $query = $this->db->query($sql, $value);
        return $query;
    }

    public function getAllData()
    {
        return $this->builder->get()->getResultArray();
    }

    public function tambahstrukturkomite($data)
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
    public function ubahketerangan($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }

    public function getDataByKodebprAndPeriode($kodebpr, $periodeId)
    {
        return $this->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->findAll();
    }

    public function getDataPenjelasByKodebprAndPeriode($kodebpr, $periodeId)
    {
        return $this->db->table('strukturkomite')
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
            ->select('strukturkomite.*, users.fullname')
            ->join('users', 'users.id = strukturkomite.user_id', 'left')
            ->where('strukturkomite.id', $Id)
            ->where('strukturkomite.kodebpr', $kodebpr)
            ->orderBy('strukturkomite.created_at', 'ASC')
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
        return $this->db->table('strukturkomite')
            ->where('id', $Id)
            ->orderBy('date', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function editbasedkodedanperiode($data, $kodebpr, $periodeId, $id)
    {
        return $this->db->table('strukturkomite')
            ->where('id', $id)  // Menambahkan kondisi berdasarkan ID
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->update($data);
    }

    public function ubahBerdasarkanFaktorId($data, $strukturkomite, $kodebpr, $periodeId)
    {
        // Pastikan update hanya terjadi untuk faktor4id, kodebpr, dan periode_id yang sesuai
        $this->builder->where('subkategori', $strukturkomite)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId);
        return $this->builder->update($data);
    }

}