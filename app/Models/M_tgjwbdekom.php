<?php
namespace App\Models;

use CodeIgniter\Model;

class M_tgjwbdekom extends Model
{
    protected $table = 'tgjwbdekom';
    protected $primaryKey = 'id';
    protected $allowedFields = ['nik', 'dekom', 'tugastgjwbdekom', 'user_id', 'fullname', 'is_approved', 'approved_by', 'approved_at', 'dirut', 'accdekom', 'accdekom_by', 'accdekom_at', 'komut', 'periode_id', 'kodebpr'];
    protected $useTimestamps = false;

    public function __construct()
    {
        parent::__construct();
        $this->builder = $this->db->table($this->table);
    }

    public function checkIncrement()
    {
        if (empty($this->getAllData())) {
            $this->db->query('ALTER TABLE tgjwbdekom AUTO_INCREMENT = 1');
        }
    }

    public function setIncrement($value)
    {
        $value = (int) $value;
        $sql = 'ALTER TABLE tgjwbdekom AUTO_INCREMENT = ?';
        $query = $this->db->query($sql, $value);
        return $query;
    }

    public function getAllData()
    {
        return $this->builder->get()->getResultArray();
    }

    public function tambahtgjwbdekom($data)
    {
        return $this->builder->insert($data);
    }

    public function hapus($id, $kodebpr, $periode)
    {
        // Mengambil data terakhir berdasarkan id, kodebpr, dan periode
        $lastData = $this->builder->where('id', $id)
            ->where('kodebpr', $kodebpr)
            ->where('periode', $periode)
            ->orderBy('id', 'DESC')
            ->limit(1)
            ->get()
            ->getResultArray();

        // Jika data ditemukan, lakukan penghapusan
        if (!empty($lastData)) {
            // Hapus data
            $this->builder->delete([
                'id' => $id,
                'kodebpr' => $kodebpr,
                'periode' => $periode
            ]);

            // Set increment if necessary (optional based on your use case)
            $this->setIncrement($lastData[0]['id']);

            return true;  // Return true if deletion was successful
        }

        return false;  // Return false if data was not found or deletion failed
    }

    public function ubah($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }
    public function tambahketerangan($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }

    public function getDataByKodebprAndPeriode($kodebpr, $periodeId)
    {
        return $this->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->findAll();
    }

    public function getDireksiByKodebpr($kodebpr)
    {
        return $this->builder()
            ->select('dekom')
            ->where('kodebpr', $kodebpr)
            ->get()
            ->getResultArray();
    }

    public function getKomentarByFaktorIdAndKodebpr($Id, $kodebpr)
    {
        return $this->db->table($this->table)
            ->select('tgjwbdekom.*, users.fullname')
            ->join('users', 'users.id = tgjwbdekom.user_id', 'left')
            ->where('tgjwbdekom.id', $Id)
            ->where('tgjwbdekom.kodebpr', $kodebpr)
            ->orderBy('tgjwbdekom.created_at', 'ASC')
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
        return $this->db->table('tgjwbdekom_comments')
            ->where('id', $Id)
            ->orderBy('date', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getNilaiByFaktor($Id)
    {
        return $this->db->table('tgjwbdekom')
            ->where('id', $Id)
            ->orderBy('date', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function editbasedkodedanperiode($data, $kodebpr, $periodeId, $id)
    {
        return $this->db->table('tgjwbdekom')
            ->where('id', $id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->update($data);
    }

    public function ubahBerdasarkanFaktorId($data, $Tgjwbdekom, $kodebpr, $periodeId)
    {
        // Pastikan update hanya terjadi untuk faktor4id, kodebpr, dan periode_id yang sesuai
        $this->builder->where('subkategori', $Tgjwbdekom)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId);
        return $this->builder->update($data);
    }



}