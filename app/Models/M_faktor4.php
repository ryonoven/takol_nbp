<?php
namespace App\Models;

use CodeIgniter\Model;

class M_Faktor4 extends Model
{
    protected $table = 'faktor4';
    protected $primaryKey = 'id'; // Ganti dengan primary key tabel Anda
    protected $allowedFields = ['is_approved', 'approved_by', 'approved_at'];
    protected $useTimestamps = false;
    public function __construct()
    {
        $this->db = db_connect();
        $this->builder = $this->db->table($this->table);
    }

    public function checkIncrement()
    {
        if (empty($this->getAllData())) {
            $this->db->query('ALTER TABLE faktor4 AUTO_INCREMENT = 1');
        }
    }

    public function setIncrement($value)
    {
        $value = (int) $value;
        $sql = 'ALTER TABLE faktor4 AUTO_INCREMENT = ?';
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

    public function tambahKomentar($data)
    {
        return $this->builder->insert($data);
    }

    // Mengubah data berdasarkan ID yang diberikan
    public function ubah($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }

    public function resetAutoIncrement()
    {
        $this->db->query('ALTER TABLE faktor4_comments AUTO_INCREMENT = 1');
    }

    // Menyaring data berdasarkan user_id, kodebpr, dan periode
    public function getDataByUserAndPeriod($userId, $kodebpr, $periode)
    {
        return $this->nilai4Model->where('user_id', $userId)
            ->where('kodebpr', $kodebpr)
            ->where('periode', $periode)
            ->findAll();
    }

    public function getKomentarByFaktor($faktor4Id)
    {
        return $this->db->table('faktor4_comments')
            ->where('faktor4id', $faktor4Id)
            ->orderBy('date', 'DESC')
            ->get()
            ->getResultArray();
    }


    public function getNilaiByFaktor($faktor4Id)
    {
        return $this->db->table('nilaifaktor4')
            ->where('faktor4id', $faktor4Id)
            ->orderBy('date', 'DESC')
            ->get()
            ->getResultArray();
    }

    // Di dalam model M_faktor.php
    public function getAllDataWithApprovalStatus()
    {
        // Mengambil data dari tabel faktor4 dan nilaifaktor4 dengan join
        return $this->db->table('nilaifaktor4')
            ->select('faktor4.id, faktor4.sub_category, nilaifaktor4.nilai, nilaifaktor4.keterangan, nilaifaktor4.is_approved, nilaifaktor4.approved_at')
            ->join('nilaifaktor4', 'faktor4.id = nilaifaktor4.faktor4id', 'left')
            ->get()->getResultArray();
    }


    public function setNullKolom($id)
    {
        return $this->builder->update(
            ['nilai' => null, 'keterangan' => null],
            ['id' => $id]
        );
    }

    // Function menambah data
    // public function tambahF($data)
    // {
    //     return $this->builder->insert($data);
    // }

    // Menghapus data sop berdasarkan ID yang diberikan
    // Menggunakan metode delete() pada tabel "" dengan kondisi ID = $id
    // public function hapus($id)
    // {    
    //     $lastData = $this->builder->orderBy('id', 'DESC')->limit(1)->get()->getResultArray();
    //     $this->builder->delete(['id' => $id]);
    //     $this->setIncrement($lastData[0]['id']);
    // }

}