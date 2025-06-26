<?php
namespace App\Models;

use CodeIgniter\Model;

class M_nilaifaktor3 extends Model
{
    protected $table = 'nilaifaktor3';
    protected $primaryKey = 'id';
    protected $allowedFields = ['faktor3id', 'nilai', 'keterangan', 'user_id', 'fullname', 'kodebpr', 'created_at', 'periode_id', 'nfaktor3', 'penjelasfaktor', 'is_approved', 'approved_by', 'approved_at', 'accdekom', 'accdekom_by', 'accdekom_at', 'accdekom2', 'accdekom2_by', 'accdir2', 'accdir2_by'];
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
    public function tambahNilai($data, $faktor3Id, $kodebpr)
    {
        return $this->builder->insert($data);
    }

    public function getDataByKodebprAndPeriode($kodebpr, $periodeId)
    {
        return $this->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->findAll();
    }

    // Di dalam App\Models\M_nilaifaktor3

    public function getDataByKodebpr($kodebpr)
    {
        return $this->builder()
            ->where('kodebpr', $kodebpr)
            ->get()
            ->getResultArray();
    }

    public function getKomentarByFaktorIdAndKodebpr($faktor3Id, $kodebpr)
    {
        return $this->db->table($this->table)
            ->select('nilaifaktor3.*, users.fullname')
            ->join('users', 'users.id = nilaifaktor3.user_id', 'left')
            ->where('nilaifaktor3.faktor3id', $faktor3Id)
            ->where('nilaifaktor3.kodebpr', $kodebpr)
            ->orderBy('nilaifaktor3.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function resetAutoIncrement()
    {
        $this->db->query('ALTER TABLE nilaifaktor3 AUTO_INCREMENT = 1');
    }

    public function getKomentarByFaktorId($faktor3Id)
    {
        return $this->db->table($this->table)
            ->select('nilaifaktor3.*, users.fullname')
            ->join('users', 'users.id = nilaifaktor3.user_id', 'left')
            ->where('nilaifaktor3.faktor3id', $faktor3Id) // INI KUNCI FILTERING
            ->orderBy('nilaifaktor3.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }


    public function setIncrement($value)
    {
        $value = (int) $value;
        $sql = 'ALTER TABLE nilaifaktor3 AUTO_INCREMENT = ?';
        $query = $this->db->query($sql, $value);
        return $query;
    }

    public function hapus($faktor3Id)
    {
        // Menghapus data berdasarkan faktor3id
        $deleteResult = $this->builder->delete(['faktor3id' => $faktor3Id]);

        // Periksa apakah penghapusan berhasil
        if ($deleteResult) {
            return true; // Berhasil
        } else {
            return false; // Gagal
        }
    }

    public function approveFaktorByKodebprAndPeriode($kodebpr, $periodeId)
    {
        // Update nilai is_approved menjadi 1 untuk faktor3id = 27, berdasarkan kodebpr dan periode_id
        return $this->builder
            ->where('faktor3id', 27)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->set(['is_approved' => 1]) // Mengatur is_approved menjadi 1
            ->update();
    }


    public function ubahBerdasarkanFaktorId($data, $faktor3Id, $kodebpr, $periodeId)
    {
        // Pastikan update hanya terjadi untuk faktor4id, kodebpr, dan periode_id yang sesuai
        $this->builder->where('faktor3id', $faktor3Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId);
        return $this->builder->update($data);
    }

    public function hitungRataRata($faktor3Id, $kodebpr)
    {

        $periodeId = session('active_periode');
        // Ambil semua nilai untuk faktor3id dari 1 sampai 26
        $query = $this->builder()
            ->select('nilai')
            ->where('faktor3id >=', 1)
            ->where('faktor3id <=', 26)
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

    public function insertOrUpdateRataRata($rataRata, $faktor3Id, $kodebpr)
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

        $rataRata = $this->hitungRataRata($faktor3Id, $kodebpr);

        // Check if the record already exists for faktor3id = 27
        $existing = $this->where('faktor3id', 27)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $currentDateTime = date('Y-m-d H:i:s');

        // Prepare the data to be inserted or updated
        $data = [
            'faktor3id' => 27,
            'nfaktor3' => $rataRata,
            'penjelasfaktor' => $penjelasfaktor, // Store explanation in penjelasfaktor column
            'fullname' => $fullname,
            'user_id' => $userId,
            'kodebpr' => $kodebpr,
            'created_at' => $currentDateTime,
            // 'is_approved' => $associatedNilai['is_approved'] ?? 0,
            // 'approved_by' => $userId,
            // 'approved_at' => $currentDateTime,
            'periode_id' => $periodeId, // Use the current date for the period
            'keterangan' => 'Nilai rata-rata faktor 1-26'
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
                    "a. Dewan Komisaris memenuhi seluruh persyaratan yang harus dipenuhi selama menjabat sesuai dengan ketentuan sehingga pelaksanaan tugas dan tanggung jawab, termasuk pengambilan keputusan berjalan dengan sangat baik serta hasil kinerja Dewan Komisaris dapat dipertanggungjawabkan sepenuhnya kepada pemegang saham melalui RUPS.\n" .
                    "b. Dewan Komisaris telah memiliki dan menginikan secara berkala pedoman dan tata tertib kerja anggota Dewan Komisaris sehingga pelaksanaan tugas dan pengambilan keputusan rapat Dewan Komisaris terlaksana dengan memperhatikan pedoman dan tata tertib kerja.\n" .
                    "c. Dewan Komisaris memiliki kemauan dan kemampuan, serta upaya untuk membudayakan pembelajaran secara berkala dan berkelanjutan, sehingga terdapat peningkatan pengetahuan, keahlian, dan kemampuan.\n".
                    "d. Dewan Komisaris telah memiliki dan menginikan secara berkala kebijakan remunerasi dan nominasi sehingga pelaksanaan tugas terlaksana dengan memperhatikan kebijakan remunerasi dan nominasi.";
            case 2:
                return "Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
                    "a. Dewan Komisaris memenuhi seluruh persyaratan yang harus dipenuhi selama menjabat sesuai dengan ketentuan sehingga pelaksanaan tugas dan tanggung jawab, termasuk pengambilan keputusan berjalan dengan baik serta hasil kinerja Dewan Komisaris dapat dipertanggungjawabkan kepada pemegang saham melalui RUPS.\n" .
                    "b. Dewan Komisaris telah memiliki dan menginikan pedoman dan tata tertib kerja anggota Dewan Komisaris sehingga pelaksanaan tugas dan pengambilan keputusan rapat Dewan Komisaris terlaksana dengan memperhatikan pedoman dan tata tertib kerja.\n" .
                    "c. Dewan Komisaris memiliki kemauan dan kemampuan, serta upaya untuk membudayakan pembelajaran secara berkala, sehingga terdapat peningkatan pengetahuan, keahlian, dan kemampuan.\n".
                    "d. Dewan Komisaris telah memiliki dan menginikan kebijakan remunerasi dan nominasi sehingga pelaksanaan tugas terlaksana dengan memperhatikan kebijakan remunerasi dan nominasi.";
            case 3:
                return "Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan cukup memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang cukup baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
                    "a. Dewan Komisaris memenuhi seluruh persyaratan yang harus dipenuhi selama menjabat sesuai dengan ketentuan sehingga pelaksanaan tugas dan tanggung jawab, termasuk pengambilan keputusan berjalan dengan cukup baik serta hasil kinerja Dewan Komisaris dapat dipertanggungjawabkan kepada pemegang saham melalui RUPS.\n" .
                    "b. Dewan Komisaris telah memiliki pedoman dan tata tertib kerja anggota Dewan Komisaris sehingga pelaksanaan tugas dan pengambilan keputusan rapat Dewan Komisaris terlaksana dengan memperhatikan pedoman dan tata tertib kerja.\n" .
                    "c. Dewan Komisaris memiliki kemauan dan kemampuan, serta upaya untuk membudayakan pembelajaran, sehingga terdapat peningkatan pengetahuan, keahlian, dan kemampuan.\n".
                    "d. Dewan Komisaris telah memiliki kebijakan remunerasi dan nominasi sehingga pelaksanaan tugas terlaksana dengan memperhatikan kebijakan remunerasi dan nominasi.";
            case 4:
                return "Belum sepenuhnya terpenuhi struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan kurang memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang kurang baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
                    "a. Dewan Komisaris memenuhi sebagian persyaratan yang harus dipenuhi selama menjabat sesuai dengan ketentuan sehingga pelaksanaan tugas dan tanggung jawab, termasuk pengambilan keputusan berjalan dengan kurang baik serta hasil kinerja Dewan Komisaris tidak sepenuhnya dapat dipertanggungjawabkan kepada pemegang saham melalui RUPS.\n" .
                    "b. Dewan Komisaris telah memiliki pedoman dan tata tertib kerja anggota Dewan Komisaris namun ruang lingkup belum sesuai dengan ketentuan sehingga pelaksanaan tugas dan pengambilan keputusan rapat Dewan Komisaris tidak terlaksana dengan baik.\n" .
                    "c. Dewan Komisaris kurang memiliki kemauan dan kemampuan, serta upaya untuk membudayakan pembelajaran secara berkala, sehingga tidak terdapat peningkatan pengetahuan, keahlian, dan kemampuan.\n".
                    "d. Dewan Komisaris telah memiliki kebijakan remunerasi dan nominasi namun ruang lingkup belum sesuai dengan ketentuan sehingga pelaksanaan tugas tidak terlaksana dengan baik.";
            case 5:
                return "Tidak terpenuhi struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan tidak memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang tidak baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
                    "a. Dewan Komisaris tidak memenuhi seluruh persyaratan yang harus dipenuhi selama menjabat sesuai dengan ketentuan sehingga pelaksanaan tugas dan tanggung jawab, termasuk pengambilan keputusan tidak berjalan dengan baik dan hasil kinerja Dewan Komisaris tidak dapat dipertanggungjawabkan kepada pemegang saham melalui RUPS.\n" .
                    "b. Dewan Komisaris tidak memenuhi seluruh persyaratan yang harus dipenuhi selama menjabat sesuai dengan ketentuan sehingga pelaksanaan tugas dan tanggung jawab, termasuk pengambilan keputusan tidak berjalan dengan baik dan hasil kinerja Dewan Komisaris tidak dapat dipertanggungjawabkan kepada pemegang saham melalui RUPS. Dewan Komisaris tidak memiliki pedoman dan tata tertib kerja anggota Dewan Komisaris sehingga pelaksanaan tugas dan pengambilan keputusan rapat Dewan Komisaris tidak dapat terlaksana dengan baik.\n" .
                    "c. Dewan Komisaris tidak memiliki kemauan dan kemampuan, serta upaya untuk membudayakan pembelajaran secara berkala, sehingga tidak terdapat peningkatan pengetahuan, keahlian, dan kemampuan.\n".
                    "d. Dewan Komisaris tidak memiliki kebijakan remunerasi dan nominasi sehingga pelaksanaan tugas tidak dapat terlaksana dengan baik.";
            default:
                return "Nilai tidak valid.";
        }
    }


    public function ubah($data, $faktor3Id, $kodebpr, $periodeId)
    {
        return $this->builder
            ->where('faktor3id', $faktor3Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->update($data);
    }

    public function ubahkesimpulan($data, $faktor3Id, $kodebpr, $periodeId)
    {
        return $this->builder
            ->where('faktor3id', $faktor3Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->update($data);
    }



}