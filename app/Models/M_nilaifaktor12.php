<?php
namespace App\Models;

use CodeIgniter\Model;

class M_nilaifaktor12 extends Model
{
    protected $table = 'nilaifaktor12';
    protected $primaryKey = 'id';
    protected $allowedFields = ['faktor12id', 'nilai', 'keterangan', 'user_id', 'fullname', 'kodebpr', 'created_at', 'periode_id', 'nfaktor12', 'penjelasfaktor', 'is_approved', 'approved_by', 'approved_at', 'accdekom', 'accdekom_by', 'accdekom_at', 'accdekom2', 'accdekom2_by', 'accdir2', 'accdir2_by'];
    protected $useTimestamps = false;

    public function __construct()
    {
        parent::__construct();
        $this->builder = $this->db->table($this->table);
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
    public function tambahNilai($data, $faktor12Id, $kodebpr)
    {
        return $this->builder->insert($data);
    }

    public function getDataByKodebprAndPeriode($kodebpr, $periodeId)
    {
        return $this->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->findAll();
    }

    // Di dalam App\Models\M_nilaifaktor12

    public function getDataByKodebpr($kodebpr)
    {
        return $this->builder()
            ->where('kodebpr', $kodebpr)
            ->get()
            ->getResultArray();
    }

    public function getKomentarByFaktorIdAndKodebpr($faktor12Id, $kodebpr)
    {
        return $this->db->table($this->table)
            ->select('nilaifaktor12.*, users.fullname')
            ->join('users', 'users.id = nilaifaktor12.user_id', 'left')
            ->where('nilaifaktor12.faktor12id', $faktor12Id)
            ->where('nilaifaktor12.kodebpr', $kodebpr)
            ->orderBy('nilaifaktor12.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function resetAutoIncrement()
    {
        $this->db->query('ALTER TABLE nilaifaktor12 AUTO_INCREMENT = 1');
    }

    public function getKomentarByFaktorId($faktor12Id)
    {
        return $this->db->table($this->table)
            ->select('nilaifaktor12.*, users.fullname')
            ->join('users', 'users.id = nilaifaktor12.user_id', 'left')
            ->where('nilaifaktor12.faktor12id', $faktor12Id) // INI KUNCI FILTERING
            ->orderBy('nilaifaktor12.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }


    public function setIncrement($value)
    {
        $value = (int) $value;
        $sql = 'ALTER TABLE nilaifaktor12 AUTO_INCREMENT = ?';
        $query = $this->db->query($sql, $value);
        return $query;
    }

    public function hapus($faktor12Id)
    {
        // Menghapus data berdasarkan faktor12id
        $deleteResult = $this->builder->delete(['faktor12id' => $faktor12Id]);

        // Periksa apakah penghapusan berhasil
        if ($deleteResult) {
            return true; // Berhasil
        } else {
            return false; // Gagal
        }
    }

    public function approveFaktorByKodebprAndPeriode($kodebpr, $periodeId)
    {
        // Update nilai is_approved menjadi 1 untuk faktor12id = 8, berdasarkan kodebpr dan periode_id
        return $this->builder
            ->where('faktor12id', 8)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->set(['is_approved' => 1]) // Mengatur is_approved menjadi 1
            ->update();
    }


    public function ubahBerdasarkanFaktorId($data, $faktor12Id, $kodebpr, $periodeId)
    {
        // Pastikan update hanya terjadi untuk faktor12id, kodebpr, dan periode_id yang sesuai
        $this->builder->where('faktor12id', $faktor12Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId);
        return $this->builder->update($data);
    }

    public function hitungRataRata($faktor12Id, $kodebpr)
    {

        $periodeId = session('active_periode');
        // Ambil semua nilai untuk faktor12id dari 1 sampai 12
        $query = $this->builder()
            ->select('nilai')
            ->where('faktor12id >=', 1)
            ->where('faktor12id <=', 7)
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

    public function insertOrUpdateRataRata($rataRata, $faktor12Id, $kodebpr)
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

        $rataRata = $this->hitungRataRata($faktor12Id, $kodebpr);

        // Check if the record already exists for faktor12id = 8
        $existing = $this->where('faktor12id', 8)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $currentDateTime = date('Y-m-d H:i:s');

        // Prepare the data to be inserted or updated
        $data = [
            'faktor12id' => 8,
            'nfaktor12' => $rataRata,
            'penjelasfaktor' => $penjelasfaktor, // Store explanation in penjelasfaktor column
            'fullname' => $fullname,
            'user_id' => $userId,
            'kodebpr' => $kodebpr,
            'created_at' => $currentDateTime,
            // 'is_approved' => $associatedNilai['is_approved'] ?? 0,
            // 'approved_by' => $userId,
            // 'approved_at' => $currentDateTime,
            'periode_id' => $periodeId, // Use the current date for the period
            'keterangan' => 'Nilai rata-rata faktor 1-12'
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
                    "a. Rencana bisnis BPR telah disusun secara realistis, komprehensif, dan terukur (achievable) oleh Direksi dan disetujui oleh Dewan Komisaris sesuai dengan visi dan misi BPR, serta menggambarkan rencana strategis jangka panjang dan rencana bisnis tahunan dan direalisasikan sesuai dengan perencanaan sehingga indikator kinerja keuangan dan nonkeuangan dalam rencana bisnis tercapai melebihi target yang ditetapkan, termasuk penyampaian laporan rencana bisnis secara lengkap, akurat, kini, utuh, dan tepat waktu.\n" .
                    "b. Rencana bisnis BPR yang telah disusun didukung oleh pemegang saham yang ditunjukkan dengan pemenuhan seluruh komitmen dalam rangka memperkuat permodalan dan infrastruktur.";
            case 2:
                return "Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
                    "a. Rencana bisnis BPR telah disusun secara realistis, komprehensif, dan terukur (achievable) oleh Direksi dan disetujui oleh Dewan Komisaris sesuai dengan visi dan misi BPR, serta menggambarkan rencana strategis jangka panjang dan rencana bisnis tahunan dan direalisasikan sesuai dengan perencanaan sehingga indikator kinerja keuangan dan nonkeuangan dalam rencana bisnis tercapai sesuai target yang ditetapkan, termasuk penyampaian laporan rencana bisnis secara lengkap, akurat, kini, utuh, dan tepat waktu.\n" .
                    "b. Rencana bisnis BPR yang telah disusun didukung oleh pemegang saham yang ditunjukkan dengan pemenuhan sebagian besar komitmen dalam rangka memperkuat permodalan dan infrastruktur.";
            case 3:
                return "Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan cukup memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang cukup baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
                    "a. Rencana bisnis BPR telah disusun secara realistis, komprehensif, dan terukur (achievable) oleh Direksi dan disetujui oleh Dewan Komisaris sesuai dengan visi dan misi BPR, serta menggambarkan rencana strategis jangka panjang dan rencana bisnis tahunan dan sebagian besar direalisasikan sesuai dengan perencanaan sehingga indikator kinerja keuangan dan nonkeuangan dalam rencana bisnis tercapai sebagian sesuai target yang ditetapkan, termasuk penyampaian laporan rencana bisnis secara lengkap, akurat, kini, utuh, dan tepat waktu.\n" .
                    "b. Rencana bisnis BPR yang telah disusun didukung oleh pemegang saham namun pemenuhan komitmen dalam rangka memperkuat permodalan dan infrastruktur hanya dilakukan sebagian.";
            case 4:
                return "Belum sepenuhnya terpenuhi struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan kurang memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang kurang baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
                    "a. Rencana bisnis BPR belum sepenuhnya disusun secara realistis, komprehensif, dan terukur (achievable) oleh Direksi dan disetujui oleh Dewan Komisaris, serta kurang menggambarkan rencana strategis jangka panjang dan rencana bisnis tahunan dan direalisasikan kurang sesuai dengan perencanaan sehingga indikator kinerja keuangan dan nonkeuangan dalam rencana bisnis tidak tercapai target yang ditetapkan, termasuk laporan rencana bisnis tidak sepenuhnya disampaikan secara lengkap, akurat, kini, utuh dan tepat waktu.\n" .
                    "b. Rencana bisnis BPR yang telah disusun belum sepenuhnya didukung oleh pemegang saham yang ditunjukkan dengan pemenuhan sebagian kecil komitmen dalam rangka memperkuat permodalan dan infrastruktur.";
            case 5:
                return "Tidak terpenuhi struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan tidak memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang tidak baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
                    "a. Rencana bisnis BPR tidak disusun secara realistis, komprehensif, dan terukur (achievable) oleh Direksi dan disetujui oleh Dewan Komisaris, serta tidak menggambarkan rencana strategis jangka panjang dan rencana bisnis tahunan dan tidak direalisasikan sesuai dengan perencanaan sehingga indikator kinerja keuangan dan nonkeuangan dalam rencana bisnis tidak tercapai target yang ditetapkan, termasuk penyampaian laporan rencana bisnis secara tidak lengkap, tidak akurat, tidak kini, tidak utuh, dan melebihi batas waktu.\n" .
                    "b. Rencana bisnis BPR yang telah disusun tidak didukung oleh pemegang saham yang ditunjukkan dengan tidak terdapat pemenuhan komitmen dalam rangka memperkuat permodalan dan infrastruktur.";
            default:
                return "Nilai tidak valid.";
        }
    }

    public function ubah($data, $faktor12Id, $kodebpr, $periodeId)
    {
        return $this->builder
            ->where('faktor12id', $faktor12Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->update($data);
    }

    public function ubahkesimpulan($data, $faktor12Id, $kodebpr, $periodeId)
    {
        return $this->builder
            ->where('faktor12id', $faktor12Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->update($data);
    }

}