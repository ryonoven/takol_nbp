<?php
namespace App\Models;

use CodeIgniter\Model;

class M_nilaifaktor7 extends Model
{
    protected $table = 'nilaifaktor7';
    protected $primaryKey = 'id';
    protected $allowedFields = ['faktor7id', 'nilai', 'keterangan', 'user_id', 'fullname', 'kodebpr', 'created_at', 'periode_id', 'nfaktor7', 'penjelasfaktor', 'is_approved', 'approved_by', 'approved_at', 'accdekom', 'accdekom_by', 'accdekom_at','accdekom2', 'accdekom2_by', 'accdir2', 'accdir2_by'];
    protected $useTimestamps = false;


    public function __construct()
    {
        parent::__construct(); // Call parent constructor for Model initialization
        $this->builder = $this->db->table($this->table); // Get builder for this table
    }
    public function insertNilai($data)
    {
        return $this->insert($data);
    }

    public function getAllData()
    {
        // return $this->findAll();
        return $this->builder->get()->getResultArray();
    }
    public function tambahNilai($data, $faktor7Id, $kodebpr)
    {
        return $this->builder->insert($data);
    }

    public function getDataByKodebprAndPeriode($kodebpr, $periodeId)
    {
        return $this->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->findAll();
    }

    // Di dalam App\Models\M_nilaifaktor7

    public function getDataByKodebpr($kodebpr)
    {
        return $this->builder()
            ->where('kodebpr', $kodebpr)
            ->get()
            ->getResultArray();
    }

    public function getKomentarByFaktorIdAndKodebpr($faktor7Id, $kodebpr)
    {
        return $this->db->table($this->table)
            ->select('nilaifaktor7.*, users.fullname')
            ->join('users', 'users.id = nilaifaktor7.user_id', 'left')
            ->where('nilaifaktor7.faktor7id', $faktor7Id)
            ->where('nilaifaktor7.kodebpr', $kodebpr)
            ->orderBy('nilaifaktor7.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function resetAutoIncrement()
    {
        $this->db->query('ALTER TABLE nilaifaktor7 AUTO_INCREMENT = 1');
    }

    public function getKomentarByFaktorId($faktor7Id)
    {
        return $this->db->table($this->table)
            ->select('nilaifaktor7.*, users.fullname')
            ->join('users', 'users.id = nilaifaktor7.user_id', 'left')
            ->where('nilaifaktor7.faktor7id', $faktor7Id) // INI KUNCI FILTERING
            ->orderBy('nilaifaktor7.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }


    public function setIncrement($value)
    {
        $value = (int) $value;
        $sql = 'ALTER TABLE nilaifaktor7 AUTO_INCREMENT = ?';
        $query = $this->db->query($sql, $value);
        return $query;
    }

    public function hapus($faktor7Id)
    {
        // Menghapus data berdasarkan faktor7id
        $deleteResult = $this->builder->delete(['faktor7id' => $faktor7Id]);

        // Periksa apakah penghapusan berhasil
        if ($deleteResult) {
            return true; // Berhasil
        } else {
            return false; // Gagal
        }
    }

    public function approveFaktorByKodebprAndPeriode($kodebpr, $periodeId)
    {
        // Update nilai is_approved menjadi 1 untuk faktor7id = 12, berdasarkan kodebpr dan periode_id
        return $this->builder
            ->where('faktor7id', 12)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->set(['is_approved' => 1]) // Mengatur is_approved menjadi 1
            ->update();
    }


    public function ubahBerdasarkanFaktorId($data, $faktor7Id, $kodebpr, $periodeId)
    {
        // Pastikan update hanya terjadi untuk faktor7id, kodebpr, dan periode_id yang sesuai
        $this->builder->where('faktor7id', $faktor7Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId);
        return $this->builder->update($data);
    }

    public function hitungRataRata($faktor7Id, $kodebpr)
    {

        $periodeId = session('active_periode');
        // Ambil semua nilai untuk faktor11id dari 1 sampai 11
        $query = $this->builder()
            ->select('nilai')
            ->where('faktor7id >=', 1)
            ->where('faktor7id <=', 11)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->get();

        $results = $query->getResultArray();

        if (count($results) > 0) {
            $totalNilai = 0;
            $count = 0;

            foreach ($results as $row) {
                $totalNilai += $row['nilai'];
                $count++;
            }

            // Hitung rata-rata
            $rataRata = $totalNilai / $count;

            // Pembulatan: >= .5 ke atas, < .5 ke bawah
            $desimal = $rataRata - floor($rataRata);
            if ($desimal >= 0.5) {
                return ceil($rataRata);
            } else {
                return floor($rataRata);
            }
        } else {
            return 0; // Jika tidak ada data
        }
    }

    public function insertOrUpdateRataRata($rataRata, $faktor7Id, $kodebpr)
    {
        // Get the authenticated user ID and fullname
        $userId = service('authentication')->id();
        $userModel = new \App\Models\M_user();
        $user = $userModel->find($userId);
        $fullname = $user['fullname'] ?? 'Unknown';

        $periodeId = session('active_periode');

        // Sementara, nanti diganti
        $userId = $userId ?? 0;  // If userId is not found, set to 1
        $kodebpr = $user['kodebpr'] ?? 'default_kodebpr';  // 

        // Get the penjelasan (explanation) for rataRata (based on its value)
        $penjelasfaktor = $this->getPenjelasanNilai($rataRata);

        $rataRata = $this->hitungRataRata($faktor7Id, $kodebpr);

        // Check if the record already exists for faktor7id = 12
        $existing = $this->where('faktor7id', 12)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $currentDateTime = date('Y-m-d H:i:s');

        // Prepare the data to be inserted or updated
        $data = [
            'faktor7id' => 12,
            'nfaktor7' => $rataRata,
            'penjelasfaktor' => $penjelasfaktor, // Store explanation in penjelasfaktor column
            'fullname' => $fullname,
            'user_id' => $userId,
            'kodebpr' => $kodebpr,
            'created_at' => $currentDateTime,
            // 'is_approved' => $associatedNilai['is_approved'] ?? 0,
            // 'approved_by' => $userId,
            // 'approved_at' => $currentDateTime,
            'periode_id' => $periodeId, // Use the current date for the period
            'keterangan' => 'Nilai rata-rata faktor 1-11'
        ];

        // If the record exists, update it. Otherwise, insert a new record.
        if ($existing) {
            return $this->update($existing['id'], $data);
        } else {
            return $this->insert($data);
        }
    }

    public function getPenjelasanNilai($rataRata)
    {
        switch ($rataRata) {
            case 1:
                return "Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan sangat memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang sangat baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
                    "a. Satuan kerja audit intern atau Pejabat Eksekutif yang melaksanakan fungsi audit intern memenuhi seluruh persyaratan yang harus dipenuhi selama menjabat sesuai dengan ketentuan sehingga pelaksanaan tugas dan tanggung jawab berjalan dengan sangat baik serta hasil kinerja satuan kerja audit intern atau Pejabat Eksekutif dapat dipertanggungjawabkan sepenuhnya kepada direktur utama dan penyampaian laporan dilakukan secara lengkap, akurat, kini, utuh, dan tepat waktu.\n" .
                    "b. Satuan kerja audit intern atau Pejabat Eksekutif yang melaksanakan fungsi audit intern telah memiliki dan menginikan secara berkala pedoman dan tata tertib kerja sehingga pelaksanaan tugas terlaksana dengan memperhatikan pedoman dan tata tertib kerja.";
            case 2:
                return "Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
                    "a. Satuan kerja audit intern atau Pejabat Eksekutif yang melaksanakan fungsi audit intern memenuhi seluruh persyaratan yang harus dipenuhi selama menjabat sesuai dengan ketentuan sehingga pelaksanaan tugas dan tanggung jawab berjalan dengan baik serta hasil kinerja satuan kerja audit intern atau Pejabat Eksekutif dapat dipertanggungjawabkan kepada direktur utama dan penyampaian laporan dilakukan secara lengkap, akurat, kini, utuh, dan tepat waktu.\n" .
                    "b. Satuan kerja audit intern atau Pejabat Eksekutif yang melaksanakan fungsi audit intern telah memiliki dan menginikan pedoman dan tata tertib kerja sehingga pelaksanaan tugas terlaksana dengan memperhatikan pedoman dan tata tertib kerja.";                    
            case 3:
                return "Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan <b>Cukup memadai</b>, dan ditunjukkan dengan hasil penerapan tata kelola yang <b>Cukup baik</b>. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
                    "a. Struktur pemegang saham <b>Memenuhi Seluruh Ketentuan</b> dan pelaksanaan tata kelola <b>Cukup Memadai</b> sehingga benturan kepentingan dapat diselesaikan, intervensi yang timbul tidak signifikan, tidak mengambil keuntungan pribadi atau kepentingan golongan tertentu, dan/atau keputusan pengangkatan, penggantian, atau pemberhentian anggota Direksi dan/atau Dewan Komisaris sesuai dengan ketentuan peraturan perundang-undangan.\n" .
                    "b. Satuan kerja audit intern atau Pejabat Eksekutif yang melaksanakan fungsi audit intern telah memiliki pedoman dan tata tertib kerja sehingga pelaksanaan tugas terlaksana dengan memperhatikan pedoman dan tata tertib kerja.";
            case 4:
                return "Belum sepenuhnya terpenuhi struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan kurang memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang kurang baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
                    "a. Satuan kerja audit intern atau Pejabat Eksekutif yang melaksanakan fungsi audit intern memenuhi sebagian persyaratan yang harus dipenuhi selama menjabat sesuai dengan ketentuan sehingga pelaksanaan tugas dan tanggung jawab berjalan dengan kurang baik serta hasil kinerja satuan kerja audit intern atau Pejabat Eksekutif tidak dapat dipertanggungjawabkan sepenuhnya kepada direktur utama dan penyampaian laporan dilakukan secara kurang lengkap, kurang akurat, tidak kini, tidak utuh, dan melebihi batas waktu.\n" .
                    "b. Satuan kerja audit intern atau Pejabat Eksekutif yang melaksanakan fungsi audit intern telah memiliki pedoman dan tata tertib kerja namun ruang lingkup belum sesuai dengan ketentuan sehingga pelaksanaan tugas tidak terlaksana dengan baik.";                    
            case 5:
                return "Tidak terpenuhi struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan tidak memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang tidak baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
                    "a. Satuan kerja audit intern atau Pejabat Eksekutif yang melaksanakan fungsi audit intern tidak memenuhi persyaratan yang harus dipenuhi selama menjabat sesuai dengan ketentuan sehingga pelaksanaan tugas dan tanggung jawab tidak berjalan dengan baik serta hasil kinerja satuan kerja audit intern atau Pejabat Eksekutif tidak dapat dipertanggungjawabkan kepada direktur utama dan penyampaian laporan dilakukan secara tidak lengkap, tidak akurat, tidak kini, tidak utuh, dan melebihi batas waktu.\n" .
                    "b. Satuan kerja audit intern atau Pejabat Eksekutif yang melaksanakan fungsi audit intern tidak memiliki pedoman dan tata tertib kerja sehingga pelaksanaan tugas tidak dapat terlaksana dengan baik.";
            default:
                return "Nilai tidak valid.";
        }
    }


    public function ubah($data, $faktor7Id, $kodebpr, $periodeId)
    {
        return $this->builder
            ->where('faktor7id', $faktor7Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->update($data);
    }

    public function ubahkesimpulan($data, $faktor7Id, $kodebpr, $periodeId)
    {
        return $this->builder
            ->where('faktor7id', $faktor7Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->update($data);
    }



}