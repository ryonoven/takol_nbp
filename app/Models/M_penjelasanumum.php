<?php
namespace App\Models;

use CodeIgniter\Model;

class M_penjelasanumum extends Model
{
    protected $table = 'penjelasanumum';
    protected $primaryKey = 'id';
    protected $allowedFields = ['namabpr', 'alamat', 'nomor', 'penjelasan', 'peringkatkomposit', 'penjelasankomposit', 'keterangan', 'is_approved', 'approved_by', 'approved_at', 'user_id', 'fullname', 'kodebpr', 'periode_id', 'accdekom', 'accdekom_by', 'accdekom_at', 'dirut', 'komut'];
    protected $useTimestamps = false;

    public function __construct()
    {
        parent::__construct();
        $this->builder = $this->db->table($this->table);
    }

    public function checkIncrement()
    {
        if (empty($this->getAllData())) {
            $this->db->query('ALTER TABLE penjelasanumum AUTO_INCREMENT = 1');
        }
    }

    public function insertNilai($data)
    {
        return $this->insert($data);
    }

    public function setIncrement($value)
    {
        $value = (int) $value;
        $sql = 'ALTER TABLE penjelasanumum AUTO_INCREMENT = ?';
        $query = $this->db->query($sql, $value);
        return $query;
    }

    public function getAllData()
    {
        return $this->builder->get()->getResultArray();
    }

    public function tambahNilai($data)
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
            ->select('penjelasanumum.*, users.fullname')
            ->join('users', 'users.id = penjelasanumum.user_id', 'left')
            ->where('penjelasanumum.id', $Id)
            ->where('penjelasanumum.kodebpr', $kodebpr)
            ->orderBy('penjelasanumum.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }

    // Menyaring data berdasarkan user_id, kodebpr, dan periode
    public function getDataByUserAndPeriod($userId, $kodebpr, $periode)
    {
        return $this->penjelasanModel->where('user_id', $userId)
            ->where('kodebpr', $kodebpr)
            ->where('periode', $periode)
            ->findAll();
    }

    public function getKomentarByFaktorId($Id)
    {
        return $this->db->table($this->table)
            ->select('penjelasanumum.*, users.fullname')
            ->join('users', 'users.id = penjelasanumum.user_id', 'left')
            ->where('penjelasanumum.faktor1id', $Id) // INI KUNCI FILTERING
            ->orderBy('penjelasanumum.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function tambahpenjelas($data)
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

    public function getKomentarByFaktor($Id)
    {
        return $this->db->table('penjelasan_comments')
            ->where('id', $Id)
            ->orderBy('date', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getNilaiByFaktor($Id)
    {
        return $this->db->table('penjelasanumum')
            ->where('id', $Id)
            ->orderBy('date', 'DESC')
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



}   