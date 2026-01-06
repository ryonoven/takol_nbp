<?php
namespace App\Models;

use CodeIgniter\Model;

class M_masalahhukum extends Model
{
    protected $table = 'masalahhukum';
    protected $primaryKey = 'id';
    protected $allowedFields = ['kodebpr', 'periode_id', 'user_id', 'hukumperdataselesai', 'hukumpidanaselesai', 'totalselesai', 'hukumperdataproses', 'hukumpidanaproses', 'totalproses', 'fullname', 'is_approved', 'approved_by', 'approved_at', 'accdekom', 'accdekom_by', 'accdekom_at'];
    protected $useTimestamps = false;

    public function __construct()
    {
        parent::__construct();
        $this->builder = $this->db->table($this->table);
    }

    public function checkIncrement()
    {
        if (empty($this->getAllData())) {
            $this->db->query('ALTER TABLE masalahhukum AUTO_INCREMENT = 1');
        }
    }

    public function setIncrement($value)
    {
        $value = (int) $value;
        $sql = 'ALTER TABLE masalahhukum AUTO_INCREMENT = ?';
        $query = $this->db->query($sql, $value);
        return $query;
    }

    public function getAllData()
    {
        return $this->builder->get()->getResultArray();
    }

    public function tambah($data)
    {
        return $this->builder->insert($data);
    }

    public function getDataByKodebprAndPeriode($kodebpr, $periodeId)
    {
        return $this->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->findAll();
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
            ->select('masalahhukum.*, users.fullname')
            ->join('users', 'users.id = masalahhukum.user_id', 'left')
            ->where('masalahhukum.id', $Id)
            ->where('masalahhukum.kodebpr', $kodebpr)
            ->orderBy('masalahhukum.created_at', 'ASC')
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

    public function getKomentarByFaktorId($Id)
    {
        return $this->db->table($this->table)
            ->select('masalahhukum.*, users.fullname')
            ->join('users', 'users.id = masalahhukum.user_id', 'left')
            ->where('masalahhukum.faktor1id', $Id) // INI KUNCI FILTERING
            ->orderBy('masalahhukum.created_at', 'ASC')
            ->get()
            ->getResultArray();
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
    public function setNullKolom($id)
    {
        return $this->builder->update(
            ['nilai' => null, 'keterangan' => null],
            ['id' => $id]
        );
    }

}