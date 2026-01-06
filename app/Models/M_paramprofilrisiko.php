<?php
namespace App\Models;

use CodeIgniter\Model;

class M_paramprofilrisiko extends Model
{
    protected $table = 'parampenilaian_profilrisiko';
    protected $primaryKey = 'id';
    protected $allowedFields = ['risiko', 'pilar', 'parameterpenilaian', 'inheren_kpmr'];
    protected $useTimestamps = false;

    public function __construct()
    {
        $this->db = db_connect();
        $this->builder = $this->db->table($this->table);
    }

    public function checkIncrement()
    {
        if (empty($this->getAllData())) {
            $this->db->query('ALTER TABLE parampenilaian_profilrisiko AUTO_INCREMENT = 1');
        }
    }

    public function setIncrement($value)
    {
        $value = (int) $value;
        $sql = 'ALTER TABLE parampenilaian_profilrisiko AUTO_INCREMENT = ?';
        $query = $this->db->query($sql, $value);
        return $query;
    }

    public function getAllData()
    {
        return $this->builder->get()->getResultArray();
    }    

}