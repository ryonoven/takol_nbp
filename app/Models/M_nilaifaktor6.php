<?php
namespace App\Models;

use CodeIgniter\Model;

class M_nilaifaktor6 extends Model
{
    protected $table = 'nilaifaktor6';
    protected $primaryKey = 'id';
    protected $allowedFields = ['faktor6id', 'nilai', 'keterangan', 'user_id', 'fullname', 'kodebpr', 'created_at', 'periode_id', 'nfaktor6', 'penjelasfaktor', 'is_approved', 'approved_by', 'approved_at', 'accdekom', 'accdekom_by', 'accdekom_at','accdekom2', 'accdekom2_by', 'accdir2', 'accdir2_by'];
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
    public function tambahNilai($data, $faktor6Id, $kodebpr)
    {
        return $this->builder->insert($data);
    }

    public function getDataByKodebprAndPeriode($kodebpr, $periodeId)
    {
        return $this->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->findAll();
    }

    // Di dalam App\Models\M_nilaifaktor6

    public function getDataByKodebpr($kodebpr)
    {
        return $this->builder()
            ->where('kodebpr', $kodebpr)
            ->get()
            ->getResultArray();
    }

    public function getKomentarByFaktorIdAndKodebpr($faktor6Id, $kodebpr)
    {
        return $this->db->table($this->table)
            ->select('nilaifaktor6.*, users.fullname')
            ->join('users', 'users.id = nilaifaktor6.user_id', 'left')
            ->where('nilaifaktor6.faktor6id', $faktor6Id)
            ->where('nilaifaktor6.kodebpr', $kodebpr)
            ->orderBy('nilaifaktor6.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function resetAutoIncrement()
    {
        $this->db->query('ALTER TABLE nilaifaktor6 AUTO_INCREMENT = 1');
    }

    public function getKomentarByFaktorId($faktor6Id)
    {
        return $this->db->table($this->table)
            ->select('nilaifaktor6.*, users.fullname')
            ->join('users', 'users.id = nilaifaktor6.user_id', 'left')
            ->where('nilaifaktor6.faktor6id', $faktor6Id) // INI KUNCI FILTERING
            ->orderBy('nilaifaktor6.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }


    public function setIncrement($value)
    {
        $value = (int) $value;
        $sql = 'ALTER TABLE nilaifaktor6 AUTO_INCREMENT = ?';
        $query = $this->db->query($sql, $value);
        return $query;
    }

    public function hapus($faktor6Id)
    {
        // Menghapus data berdasarkan faktor6id
        $deleteResult = $this->builder->delete(['faktor6id' => $faktor6Id]);

        // Periksa apakah penghapusan berhasil
        if ($deleteResult) {
            return true; // Berhasil
        } else {
            return false; // Gagal
        }
    }

    public function approveFaktorByKodebprAndPeriode($kodebpr, $periodeId)
    {
        // Update nilai is_approved menjadi 1 untuk faktor6id = 10, berdasarkan kodebpr dan periode_id
        return $this->builder
            ->where('faktor6id', 10)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->set(['is_approved' => 1]) // Mengatur is_approved menjadi 1
            ->update();
    }


    public function ubahBerdasarkanFaktorId($data, $faktor6Id, $kodebpr, $periodeId)
    {
        // Pastikan update hanya terjadi untuk faktor6id, kodebpr, dan periode_id yang sesuai
        $this->builder->where('faktor6id', $faktor6Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId);
        return $this->builder->update($data);
    }

    public function hitungRataRata($faktor6Id, $kodebpr)
    {

        $periodeId = session('active_periode');
        // Ambil semua nilai untuk faktor9id dari 1 sampai 9
        $query = $this->builder()
            ->select('nilai')
            ->where('faktor6id >=', 1)
            ->where('faktor6id <=', 9)
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

            // Pembulatan: >= .6 ke atas, < .6 ke bawah
            $desimal = $rataRata - floor($rataRata);
            if ($desimal >= 0.6) {
                return ceil($rataRata);
            } else {
                return floor($rataRata);
            }
        } else {
            return 0; // Jika tidak ada data
        }
    }

    public function insertOrUpdateRataRata($rataRata, $faktor6Id, $kodebpr)
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

        $rataRata = $this->hitungRataRata($faktor6Id, $kodebpr);

        // Check if the record already exists for faktor6id = 10
        $existing = $this->where('faktor6id', 10)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $currentDateTime = date('Y-m-d H:i:s');

        // Prepare the data to be inserted or updated
        $data = [
            'faktor6id' => 10,
            'nfaktor6' => $rataRata,
            'penjelasfaktor' => $penjelasfaktor, // Store explanation in penjelasfaktor column
            'fullname' => $fullname,
            'user_id' => $userId,
            'kodebpr' => $kodebpr,
            'created_at' => $currentDateTime,
            // 'is_approved' => $associatedNilai['is_approved'] ?? 0,
            // 'approved_by' => $userId,
            // 'approved_at' => $currentDateTime,
            'periode_id' => $periodeId, // Use the current date for the period
            'keterangan' => 'Nilai rata-rata faktor 1-9'
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
                    "a. Anggota Direksi yang membawahkan fungsi kepatuhan memenuhi seluruh persyaratan yang harus dipenuhi selama menjabat sesuai dengan ketentuan sehingga pelaksanaan tugas dan tanggung jawab berjalan dengan sangat baik serta hasil kinerja anggota Direksi yang membawahkan fungsi kepatuhan dapat dipertanggungjawabkan sepenuhnya kepada direktur utama atau Dewan Komisaris (bagi Direksi yang membawahkan fungsi kepatuhan adalah direktur utama) dan tidak terdapat pelanggaran yang signifikan atau berhasil menurunkan tingkat pelanggaran signifikan.\n" .
                    "b. Anggota Direksi yang membawahkan fungsi kepatuhan telah membentuk satuan kerja atau mengangkat Pejabat Eksekutif dengan memperhatikan kompleksitas kegiatan usaha dalam rangka mendukung pelaksanaan tugas dan fungsi anggota Direksi yang membawahkan fungsi kepatuhan sehingga prinsip tata kelola diterapkan secara efektif sesuai dengan ketentuan peraturan perundang-undangan maupun pedoman internal dan tata tertib kerja.\n" .
                    "c. Satuan kerja kepatuhan dan Pejabat Eksekutif yang membawahkan fungsi kepatuhan telah memiliki  dan/atau menginikan secara berkala pedoman dan tata tertib kerja sehingga pelaksanaan tugas terlaksana dengan memperhatikan pedoman dan tata tertib kerja.";
            case 2:
                return "Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
                    "a. Anggota Direksi yang membawahkan fungsi kepatuhan memenuhi seluruh persyaratan yang harus dipenuhi selama menjabat sesuai dengan ketentuan sehingga pelaksanaan tugas dan tanggung jawab berjalan dengan baik serta hasil kinerja anggota Direksi yang membawahkan fungsi kepatuhan dapat dipertanggungjawabkan kepada direktur utama atau Dewan Komisaris (bagi Direksi yang membawahkan fungsi kepatuhan adalah direktur utama) dan berhasil menurunkan tingkat pelanggaran.\n" .
                    "b. Anggota Direksi yang membawahkan fungsi kepatuhan telah membentuk satuan kerja atau mengangkat Pejabat Eksekutif dalam rangka mendukung pelaksanaan tugas dan fungsi anggota Direksi yang membawahkan fungsi kepatuhan sehingga prinsip tata kelola diterapkan sesuai dengan ketentuan peraturan perundang-undangan maupun pedoman internal dan tata tertib kerja.\n" .
                    "c. Satuan kerja kepatuhan dan Pejabat Eksekutif yang membawahkan fungsi kepatuhan telah memiliki dan/atau menginikan pedoman dan tata tertib kerja sehingga pelaksanaan tugas terlaksana dengan memperhatikan pedoman dan tata tertib kerja.";
            case 3:
                return "Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan cukup memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang cukup baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
                    "a. Anggota Direksi yang membawahkan fungsi kepatuhan memenuhi seluruh persyaratan yang harus dipenuhi selama menjabat sesuai dengan ketentuan sehingga pelaksanaan tugas dan tanggung jawab berjalan dengan cukup baik serta hasil kinerja anggota Direksi yang membawahkan fungsi kepatuhan dapat dipertanggungjawabkan kepada direktur utama atau Dewan Komisaris (bagi Direksi yang membawahkan fungsi kepatuhan adalah direktur utama) dan cukup berhasil menurunkan tingkat pelanggaran.\n" .
                    "b. Anggota Direksi yang membawahkan fungsi kepatuhan telah membentuk satuan kerja atau mengangkat Pejabat Eksekutif namun belum dapat mendukung sepenuhnya pelaksanaan tugas dan fungsi Direksi yang membawahkan fungsi kepatuhan sehingga penerapan prinsip tata kelola belum sepenuhnya sesuai dengan ketentuan peraturan perundang-undangan maupun pedoman internal dan tata tertib kerja.\n" .
                    "c. Satuan kerja kepatuhan dan Pejabat Eksekutif yang membawahkan fungsi kepatuhan telah memiliki pedoman dan tata tertib kerja sehingga pelaksanaan tugas terlaksana dengan memperhatikan pedoman dan tata tertib kerja.";
            case 4:
                return "Belum sepenuhnya terpenuhi struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan kurang memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang kurang baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
                    "a. Anggota Direksi yang membawahkan fungsi kepatuhan memenuhi sebagian persyaratan yang harus dipenuhi selama menjabat sesuai dengan ketentuan sehingga pelaksanaan tugas dan tanggung jawab berjalan dengan kurang baik serta hasil kinerja anggota Direksi yang membawahkan fungsi kepatuhan tidak dapat dipertanggungjawabkan sepenuhnya kepada direktur utama atau Dewan Komisaris (bagi Direksi yang membawahkan fungsi kepatuhan adalah direktur utama) dan kurang berhasil menurunkan tingkat pelanggaran.\n" .
                    "b. Anggota Direksi yang membawahkan fungsi kepatuhan telah membentuk satuan kerja atau mengangkat Pejabat Eksekutif namun tidak sesuai dengan ketentuan sehingga kurang mendukung pelaksanaan tugas dan fungsi anggota Direksi yang membawahkan fungsi kepatuhan dan penerapan prinsip tata kelola belum sesuai dengan ketentuan peraturan perundang-undangan maupun pedoman internal dan tata tertib kerja.\n" .
                    "c. Satuan kerja kepatuhan dan Pejabat Eksekutif yang membawahkan fungsi kepatuhan telah memiliki pedoman dan tata tertib kerja namun ruang lingkup belum sesuai dengan ketentuan sehingga pelaksanaan tugas tidak terlaksana dengan baik.";
            case 5:
                return "Tidak terpenuhi struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan tidak memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang tidak baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
                    "a. Anggota Direksi yang membawahkan fungsi kepatuhan tidak memenuhi seluruh persyaratan yang harus dipenuhi selama menjabat sesuai dengan ketentuan sehingga pelaksanaan tugas dan tanggung jawab tidak berjalan dengan baik serta hasil kinerja anggota Direksi yang membawahkan fungsi kepatuhan tidak dapat dipertanggungjawabkan kepada direktur utama atau Dewan Komisaris (bagi Direksi yang membawahkan fungsi kepatuhan adalah direktur utama) dan tidak berhasil menurunkan tingkat pelanggaran.\n" .
                    "b. Anggota Direksi yang membawahkan fungsi kepatuhan tidak membentuk satuan kerja atau mengangkat Pejabat Eksekutif sesuai dengan ketentuan dalam rangka mendukung pelaksanaan tugas dan fungsi anggota Direksi yang membawahkan fungsi kepatuhan sehingga prinsip tata kelola tidak dapat diterapkan sesuai dengan ketentuan peraturan perundang-undangan maupun pedoman internal dan tata tertib kerja.\n" .
                    "c. Satuan kerja kepatuhan dan Pejabat Eksekutif yang membawahkan fungsi kepatuhan tidak memiliki pedoman dan tata tertib kerja sehingga pelaksanaan tugas tidak dapat terlaksana dengan baik.";
            default:
                return "Nilai tidak valid.";
        }
    }


    public function ubah($data, $faktor6Id, $kodebpr, $periodeId)
    {
        return $this->builder
            ->where('faktor6id', $faktor6Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->update($data);
    }

    public function ubahkesimpulan($data, $faktor6Id, $kodebpr, $periodeId)
    {
        return $this->builder
            ->where('faktor6id', $faktor6Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->update($data);
    }



}