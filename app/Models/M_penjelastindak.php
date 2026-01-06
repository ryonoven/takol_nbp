<?php
namespace App\Models;

use CodeIgniter\Model;

class M_penjelastindak extends Model
{
    protected $table = 'penjelastindak';
    protected $primaryKey = 'id';
    protected $allowedFields = ['subkategori', 'tindaklanjut', 'penjelasanlanjut', 'kodebpr', 'periode_id', 'fullname', 'user_id'];
    protected $useTimestamps = false;

    public function __construct()
    {
        parent::__construct();
        $this->builder = $this->db->table($this->table);
    }

    // Function to check and reset the AUTO_INCREMENT value of the table if no data exists
    public function checkIncrement()
    {
        if (empty($this->getAllData())) {
            $this->db->query('ALTER TABLE penjelastindak AUTO_INCREMENT = 1');
        }
    }

    // Function to set the AUTO_INCREMENT value to a specific value
    public function setIncrement($value)
    {
        $value = (int) $value;
        $sql = 'ALTER TABLE penjelastindak AUTO_INCREMENT = ?';
        $query = $this->db->query($sql, $value);
        return $query;
    }

    // Get all data from penjelastindak table
    public function getAllData()
    {
        return $this->builder->get()->getResultArray();
    }

    // Insert new data into penjelastindak table
    public function tambahpenjelastindak($data)
    {
        return $this->builder->insert($data);
    }

    // Delete data based on the provided ID and reset the auto increment value
    public function hapus($id)
    {
        // Get the last inserted ID
        $lastData = $this->builder->orderBy('id', 'DESC')->limit(1)->get()->getResultArray();

        // Delete data by ID
        $this->builder->delete(['id' => $id]);

        // Reset the auto increment value to the last inserted ID
        $this->setIncrement($lastData[0]['id']);
    }

    // Update data in penjelastindak table based on ID
    public function ubah($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }

    // Function to get data by 'kodebpr' and 'periode_id'
    public function getDataByKodebprAndPeriode($subkategori, $kodebpr, $periodeId)
    {
        return $this->where('subkategori', $subkategori)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->findAll();
    }

    // Function to get comments related to a factor (based on ID)
    public function getKomentarByFaktor($Id)
    {
        return $this->db->table('penjelastindak_comments')
            ->where('id', $Id)
            ->orderBy('date', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function editberdasarkankodedanperiode($data, $subkategori, $kodebpr, $periodeId)
    {
        // Pastikan update hanya terjadi untuk faktor4id, kodebpr, dan periode_id yang sesuai
        $this->builder->where('subkategori', $subkategori)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId);
        return $this->builder->update($data);
    }

    public function getDataPenjelasByKodebprAndPeriode($subkategori, $kodebpr, $periodeId)
    {
        return $this->db->table('penjelastindak')
            ->where('subkategori', $subkategori)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->get()
            ->getResultArray();
    }

    public function setNullKolomTindak($id)
    {
        return $this->builder->update(
            ['tindaklanjut' => null],
            ['id' => $id]
        );
    }

    public function setNullKolomPenjelaslanjut($id)
    {
        return $this->builder->update(
            ['penjelasanlanjut' => null],
            ['id' => $id]
        );
    }
}
