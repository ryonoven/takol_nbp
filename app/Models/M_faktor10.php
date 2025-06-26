<?php
namespace App\Models;

use CodeIgniter\Model;

class M_Faktor10 extends Model
{
    protected $table = 'faktor10';
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
            $this->db->query('ALTER TABLE faktor10 AUTO_INCREMENT = 1');
        }
    }

    public function setIncrement($value)
    {
        $value = (int) $value;
        $sql = 'ALTER TABLE faktor10 AUTO_INCREMENT = ?';
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
        $this->db->query('ALTER TABLE faktor10_comments AUTO_INCREMENT = 1');
    }

    // Menyaring data berdasarkan user_id, kodebpr, dan periode
    public function getDataByUserAndPeriod($userId, $kodebpr, $periode)
    {
        return $this->nilai10Model->where('user_id', $userId)
            ->where('kodebpr', $kodebpr)
            ->where('periode', $periode)
            ->findAll();
    }

    public function getKomentarByFaktor($faktor10Id)
    {
        return $this->db->table('faktor10_comments')
            ->where('faktor10id', $faktor10Id)
            ->orderBy('date', 'DESC')
            ->get()
            ->getResultArray();
    }


    public function getNilaiByFaktor($faktor10Id)
    {
        return $this->db->table('nilaifaktor10')
            ->where('faktor10id', $faktor10Id)
            ->orderBy('date', 'DESC')
            ->get()
            ->getResultArray();
    }

    // Di dalam model M_faktor.php
    public function getAllDataWithApprovalStatus()
    {
        // Mengambil data dari tabel faktor10 dan nilaifaktor10 dengan join
        return $this->db->table('nilaifaktor10')
            ->select('faktor10.id, faktor10.sub_category, nilaifaktor10.nilai, nilaifaktor10.keterangan, nilaifaktor10.is_approved, nilaifaktor10.approved_at')
            ->join('nilaifaktor10', 'faktor10.id = nilaifaktor10.faktor10id', 'left')
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