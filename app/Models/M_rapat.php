<?php
namespace App\Models;

use CodeIgniter\Model;

class M_rapat extends Model
{
    protected $table = 'rapat';
    protected $primaryKey = 'id';
    protected $allowedFields = ['kodebpr', 'periode_id', 'user_id', 'tangalrapat', 'jumlahpeserta', 'topikrapat', 'fullname', 'is_approved', 'approved_by', 'approved_at','accdekom', 'accdekom_by', 'accdekom_at'];
    protected $useTimestamps = false;

    public function __construct()
    {
        parent::__construct();
        $this->builder = $this->db->table($this->table);
    }  

    public function getAllData()
    {
        return $this->builder->get()->getResultArray();
    }

    public function setIncrement($value) {
        $value = (int)$value;
        $sql = 'ALTER TABLE rapat AUTO_INCREMENT = ?';
        $query = $this->db->query($sql, $value);
        return $query;
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

    public function getDataPenjelasByKodebprAndPeriode($kodebpr, $periodeId)
    {
        return $this->db->table('rapat')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->get()
            ->getResultArray();
    }

    public function getKomentarByFaktorIdAndKodebpr($Id, $kodebpr)
    {
        return $this->db->table($this->table)
            ->select('rapat.*, users.fullname')
            ->join('users', 'users.id = rapat.user_id', 'left')
            ->where('rapat.id', $Id)
            ->where('rapat.kodebpr', $kodebpr)
            ->orderBy('rapat.created_at', 'ASC')
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
        return $this->db->table('shmdirdekomlain')
            ->where('id', $Id)
            ->orderBy('date', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function hapus($id)
    {
        $lastData = $this->builder->orderBy('id', 'DESC')->limit(1)->get()->getResultArray();
        $this->builder->delete(['id' => $id]);
        $this->setIncrement($lastData[0]['id']);
    }

    public function ubahBerdasarkanFaktorId($data, $rapat, $kodebpr, $periodeId)
    {
        $this->builder->where('subkategori', $rapat)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId);
        return $this->builder->update($data);
    }

    public function ubah($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }

    public function editbasedkodedanperiode($data, $kodebpr, $periodeId, $id)
    {
        return $this->db->table('rapat')
            ->where('id', $id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->update($data);
    }

    public function setNullKolom($id)
    {
        return $this->builder->update(
            ['nilai' => null, 'keterangan' => null],
            ['id' => $id]
        );
    }

}