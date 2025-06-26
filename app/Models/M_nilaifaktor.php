<?php
namespace App\Models;

use CodeIgniter\Model;

class M_nilaifaktor extends Model
{
    protected $table = 'nilaifaktor';
    protected $primaryKey = 'id';
    protected $allowedFields = ['faktor1id', 'nilai', 'keterangan', 'user_id', 'fullname', 'kodebpr', 'created_at', 'periode_id', 'nfaktor', 'penjelasfaktor', 'is_approved', 'approved_by', 'approved_at', 'accdekom', 'accdekom_by', 'accdekom_at', 'accdekom2', 'accdekom2_by', 'accdir2', 'accdir2_by'];
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
    public function tambahNilai($data, $faktorId, $kodebpr)
    {
        return $this->builder->insert($data);
    }

    public function getDataByKodebprAndPeriode($kodebpr, $periodeId)
    {
        return $this->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->findAll();
    }

    // Di dalam App\Models\M_nilaifaktor

    public function getDataByKodebpr($kodebpr)
    {
        return $this->builder()
            ->where('kodebpr', $kodebpr)
            ->get()
            ->getResultArray();
    }

    public function getKomentarByFaktorIdAndKodebpr($faktorId, $kodebpr)
    {
        return $this->db->table($this->table)
            ->select('nilaifaktor.*, users.fullname')
            ->join('users', 'users.id = nilaifaktor.user_id', 'left')
            ->where('nilaifaktor.faktor1id', $faktorId)
            ->where('nilaifaktor.kodebpr', $kodebpr)
            ->orderBy('nilaifaktor.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function resetAutoIncrement()
    {
        $this->db->query('ALTER TABLE nilaifaktor AUTO_INCREMENT = 1');
    }

    public function getKomentarByFaktorId($faktorId)
    {
        return $this->db->table($this->table)
            ->select('nilaifaktor.*, users.fullname')
            ->join('users', 'users.id = nilaifaktor.user_id', 'left')
            ->where('nilaifaktor.faktor1id', $faktorId) // INI KUNCI FILTERING
            ->orderBy('nilaifaktor.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }


    public function setIncrement($value)
    {
        $value = (int) $value;
        $sql = 'ALTER TABLE nilaifaktor AUTO_INCREMENT = ?';
        $query = $this->db->query($sql, $value);
        return $query;
    }

    public function hapus($faktorId)
    {
        // Menghapus data berdasarkan faktor1id
        $deleteResult = $this->builder->delete(['faktor1id' => $faktorId]);

        // Periksa apakah penghapusan berhasil
        if ($deleteResult) {
            return true; // Berhasil
        } else {
            return false; // Gagal
        }
    }

    public function approveFaktorByKodebprAndPeriode($kodebpr, $periodeId)
    {
        // Update nilai is_approved menjadi 1 untuk faktor1id = 12, berdasarkan kodebpr dan periode_id
        return $this->builder
            ->where('faktor1id', 12)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->set(['is_approved' => 1]) // Mengatur is_approved menjadi 1
            ->update();
    }


    public function ubahBerdasarkanFaktorId($data, $faktorId, $kodebpr, $periodeId)
    {
        // Pastikan update hanya terjadi untuk faktor4id, kodebpr, dan periode_id yang sesuai
        $this->builder->where('faktor1id', $faktorId)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId);
        return $this->builder->update($data);
    }

    public function hitungRataRata($faktorId, $kodebpr)
    {

        $periodeId = session('active_periode');
        // Ambil semua nilai untuk faktor1id dari 1 sampai 11
        $query = $this->builder()
            ->select('nilai')
            ->where('faktor1id >=', 1)
            ->where('faktor1id <=', 11)
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

    public function insertOrUpdateRataRata($rataRata, $faktorId, $kodebpr)
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

        $rataRata = $this->hitungRataRata($faktorId, $kodebpr);

        // Check if the record already exists for faktor1id = 12
        $existing = $this->where('faktor1id', 12)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $currentDateTime = date('Y-m-d H:i:s');

        // Prepare the data to be inserted or updated
        $data = [
            'faktor1id' => 12,
            'nfaktor' => $rataRata,
            'penjelasfaktor' => $penjelasfaktor, // Store explanation in penjelasfaktor column
            'fullname' => $fullname,
            'user_id' => $userId,
            'kodebpr' => $kodebpr,
            'created_at' => $currentDateTime,
            // 'is_approved' => $associatedNilai['is_approved'] ?? 0,
            // 'approved_by' => $userId,
            // 'approved_at' => $currentDateTime,
            // 'accdekom_at' => $currentDateTime,
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
                return "Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai ketentuan, proses pelaksanaan tata kelola dilakukan dengan sangat memadai, dan ditunjukkan dengan hasil pelaksanaan tata kelola yang sangat baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
                    "a. Struktur pemegang saham memenuhi seluruh ketentuan dan pelaksanaan tata kelola sangat memadai sehingga tidak terdapat benturan kepentingan, intervensi, mengambil keuntungan pribadi atau kepentingan golongan tertentu, dan/atau keputusan pengangkatan, penggantian, atau pemberhentian anggota Direksi dan/atau Dewan Komisaris sesuai dengan ketentuan peraturan perundang-undangan.\n" .
                    "b. Seluruh pengambilan kebijakan aksi korporasi melalui RUPS sejalan dengan anggaran dasar, ketentuan peraturan perundang-undangan, dan rencana strategis sehingga perencanaan pengembangan BPR terealisasikan sepenuhnya yang tercermin pada pemenuhan ketentuan permodalan, kinerja keuangan, dan/atau perkembangan kegiatan usaha BPR.\n" .
                    "c. Kebijakan penggunaan laba dan pembagian dividen telah dievaluasi secara berkala sehingga seluruh pelaksanaan penggunaan laba dan pembagian dividen telah sesuai dengan kebijakan yang ditetapkan.";
            case 2:
                return "Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
                    "a. Struktur pemegang saham memenuhi seluruh ketentuan dan pelaksanaan tata kelola memadai sehingga benturan kepentingan dapat diselesaikan, intervensi yang timbul tidak signifikan, tidak mengambil keuntungan pribadi atau kepentingan golongan tertentu, dan/atau keputusan pengangkatan, penggantian, atau pemberhentian anggota Direksi dan/atau Dewan Komisaris sesuai dengan ketentuan peraturan perundang-undangan.\n" .
                    "b. Sebagian besar pengambilan kebijakan aksi korporasi melalui RUPS sejalan dengan anggaran dasar, ketentuan peraturan perundang-undangan, dan rencana strategis sehingga perencanaan pengembangan BPR sebagian besar terealisasikan yang tercermin pada pemenuhan ketentuan permodalan, kinerja keuangan, dan/atau perkembangan kegiatan usaha BPR.\n" .
                    "c. Kebijakan penggunaan laba dan pembagian dividen telah dievaluasi sehingga sebagian besar pelaksanaan penggunaan laba dan pembagian dividen telah sesuai dengan kebijakan yang ditetapkan.";
            case 3:
                return "Belum sepenuhnya terpenuhi struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan kurang memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang kurang baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
                    "a. Struktur pemegang saham memenuhi seluruh ketentuan dan pelaksanaan tata kelola sangat memadai sehingga tidak terdapat benturan kepentingan, intervensi, mengambil keuntungan pribadi atau kepentingan golongan tertentu, dan/atau keputusan pengangkatan, penggantian, atau pemberhentian anggota Direksi dan/atau Dewan Komisaris sesuai dengan ketentuan peraturan perundang-undangan.\n" .
                    "b. Seluruh pengambilan kebijakan aksi korporasi melalui RUPS sejalan dengan anggaran dasar, ketentuan peraturan perundang-undangan, dan rencana strategis sehingga perencanaan pengembangan BPR terealisasikan sepenuhnya yang tercermin pada pemenuhan ketentuan permodalan, kinerja keuangan, dan/atau perkembangan kegiatan usaha BPR.\n" .
                    "c. Kebijakan penggunaan laba dan pembagian dividen telah dievaluasi secara berkala sehingga seluruh pelaksanaan penggunaan laba dan pembagian dividen telah sesuai dengan kebijakan yang ditetapkan.";
            case 4:
                return "Belum sepenuhnya terpenuhi struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan kurang memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang kurang baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
                    "a. Struktur pemegang saham memenuhi sebagian ketentuan dan pelaksanaan tata kelola kurang memadai sehingga benturan kepentingan kurang dapat diselesaikan, intervensi yang timbul cukup signifikan, mengambil keuntungan pribadi atau kepentingan golongan tertentu, dan/atau keputusan pengangkatan, penggantian, atau pemberhentian anggota Direksi dan/atau Dewan Komisaris kurang sesuai dengan ketentuan peraturan perundang-undangan.\n" .
                    "b. Sebagian kecil pengambilan kebijakan aksi korporasi melalui RUPS sejalan dengan anggaran dasar, ketentuan peraturan perundang-undangan, dan rencana strategis sehingga perencanaan pengembangan BPR sebagian kecil terealisasikan yang tercermin pada pemenuhan ketentuan permodalan, kinerja keuangan, dan/atau perkembangan kegiatan usaha BPR.\n" .
                    "c. Sebagian kebijakan penggunaan laba dan pembagian dividen telah dievaluasi sehingga sebagian kecil pelaksanaan penggunaan laba dan pembagian dividen telah sesuai dengan kebijakan yang ditetapkan.";
            case 5:
                return "Tidak terpenuhi struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan tidak memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang tidak baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
                    "a. Struktur pemegang saham tidak memenuhi ketentuan dan pelaksanaan tata kelola tidak memadai sehingga benturan kepentingan tidak dapat diselesaikan, intervensi yang timbul signifikan, mengambil keuntungan pribadi atau kepentingan golongan tertentu, dan/atau keputusan pengangkatan, penggantian, atau pemberhentian anggota Direksi dan/atau Dewan Komisaris tidak sesuai dengan ketentuan peraturan perundang-undangan.\n" .
                    "b. Pengambilan kebijakan aksi korporasi tidak melalui RUPS dan tidak sejalan dengan anggaran dasar, ketentuan peraturan perundang-undangan, dan rencana strategis sehingga perencanaan pengembangan BPR tidak terealisasikan yang tercermin pada pemenuhan ketentuan permodalan, kinerja keuangan, dan/atau perkembangan kegiatan usaha BPR.\n" .
                    "c. Kebijakan penggunaan laba dan pembagian dividen tidak dievaluasi sehingga pelaksanaan penggunaan laba dan pembagian dividen tidak sesuai dengan kebijakan yang ditetapkan.";
            default:
                return "Nilai tidak valid.";
        }
    }


    public function ubah($data, $faktorId, $kodebpr, $periodeId)
    {
        return $this->builder
            ->where('faktor1id', $faktorId)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->update($data);
    }

    public function ubahkesimpulan($data, $faktorId, $kodebpr, $periodeId)
    {
        return $this->builder
            ->where('faktor1id', $faktorId)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->update($data);
    }

}