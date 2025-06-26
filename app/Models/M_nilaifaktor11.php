<?php
namespace App\Models;

use CodeIgniter\Model;

class M_nilaifaktor11 extends Model
{
    protected $table = 'nilaifaktor11';
    protected $primaryKey = 'id';
    protected $allowedFields = ['faktor11id', 'nilai', 'keterangan', 'user_id', 'fullname', 'kodebpr', 'created_at', 'periode_id', 'nfaktor11', 'penjelasfaktor', 'is_approved', 'approved_by', 'approved_at', 'accdekom', 'accdekom_by', 'accdekom_at', 'accdekom2', 'accdekom2_by', 'accdir2', 'accdir2_by'];
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
    public function tambahNilai($data, $faktor11Id, $kodebpr)
    {
        return $this->builder->insert($data);
    }

    public function getDataByKodebprAndPeriode($kodebpr, $periodeId)
    {
        return $this->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->findAll();
    }

    // Di dalam App\Models\M_nilaifaktor11

    public function getDataByKodebpr($kodebpr)
    {
        return $this->builder()
            ->where('kodebpr', $kodebpr)
            ->get()
            ->getResultArray();
    }

    public function getKomentarByFaktorIdAndKodebpr($faktor11Id, $kodebpr)
    {
        return $this->db->table($this->table)
            ->select('nilaifaktor11.*, users.fullname')
            ->join('users', 'users.id = nilaifaktor11.user_id', 'left')
            ->where('nilaifaktor11.faktor11id', $faktor11Id)
            ->where('nilaifaktor11.kodebpr', $kodebpr)
            ->orderBy('nilaifaktor11.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function resetAutoIncrement()
    {
        $this->db->query('ALTER TABLE nilaifaktor11 AUTO_INCREMENT = 1');
    }

    public function getKomentarByFaktorId($faktor11Id)
    {
        return $this->db->table($this->table)
            ->select('nilaifaktor11.*, users.fullname')
            ->join('users', 'users.id = nilaifaktor11.user_id', 'left')
            ->where('nilaifaktor11.faktor11id', $faktor11Id) // INI KUNCI FILTERING
            ->orderBy('nilaifaktor11.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }


    public function setIncrement($value)
    {
        $value = (int) $value;
        $sql = 'ALTER TABLE nilaifaktor11 AUTO_INCREMENT = ?';
        $query = $this->db->query($sql, $value);
        return $query;
    }

    public function hapus($faktor11Id)
    {
        // Menghapus data berdasarkan faktor11id
        $deleteResult = $this->builder->delete(['faktor11id' => $faktor11Id]);

        // Periksa apakah penghapusan berhasil
        if ($deleteResult) {
            return true; // Berhasil
        } else {
            return false; // Gagal
        }
    }

    public function approveFaktorByKodebprAndPeriode($kodebpr, $periodeId)
    {
        // Update nilai is_approved menjadi 1 untuk faktor11id = 13, berdasarkan kodebpr dan periode_id
        return $this->builder
            ->where('faktor11id', 13)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->set(['is_approved' => 1]) // Mengatur is_approved menjadi 1
            ->update();
    }


    public function ubahBerdasarkanFaktorId($data, $faktor11Id, $kodebpr, $periodeId)
    {
        // Pastikan update hanya terjadi untuk faktor11id, kodebpr, dan periode_id yang sesuai
        $this->builder->where('faktor11id', $faktor11Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId);
        return $this->builder->update($data);
    }

    public function hitungRataRata($faktor11Id, $kodebpr)
    {

        $periodeId = session('active_periode');
        // Ambil semua nilai untuk faktor11id dari 1 sampai 12
        $query = $this->builder()
            ->select('nilai')
            ->where('faktor11id >=', 1)
            ->where('faktor11id <=', 12)
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

    public function insertOrUpdateRataRata($rataRata, $faktor11Id, $kodebpr)
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

        $rataRata = $this->hitungRataRata($faktor11Id, $kodebpr);

        // Check if the record already exists for faktor11id = 13
        $existing = $this->where('faktor11id', 13)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $currentDateTime = date('Y-m-d H:i:s');

        // Prepare the data to be inserted or updated
        $data = [
            'faktor11id' => 13,
            'nfaktor11' => $rataRata,
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
                    "a. BPR memiliki sistem pelaporan keuangan dan nonkeuangan yang didukung oleh sistem informasi manajemen yang sangat memadai sesuai ketentuan termasuk sumber daya manusia yang kompeten sehingga penyusunan laporan dilakukan secara lengkap, akurat, kini, utuh, dan tepat waktu.\n" .
                    "b. BPR memiliki pelaporan internal yang didukung oleh sistem informasi manajemen dan meningkatkan kualitas proses pengambilan keputusan oleh Direksi dan kualitas proses pengawasan oleh Dewan Komisaris, serta tidak terdapat penyalahgunaan dan pemanfaatan dalam rangka rekayasa keuangan dan/atau rekayasa hukum.\n" .
                    "c. BPR telah memiliki dan menginikan secara berkala kebijakan dan prosedur terkait integritas pelaporan dan sistem teknologi informasi dengan ruang lingkup sangat memadai, sehingga penyampaian pelaporan dilakukan sesuai dengan kebijakan dan prosedur.\n" .
                    "d. BPR melaksanakan transparansi informasi mengenai produk, layanan dan/atau penggunaan data nasabah BPR dengan berpedoman pada persyaratan dan tata cara sesuai ketentuan Otoritas Jasa Keuangan sehingga tidak terdapat laporan pengaduan dari nasabah.";
            case 2:
                return "Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
                    "a. BPR memiliki sistem pelaporan keuangan dan nonkeuangan yang didukung oleh sistem informasi manajemen yang memadai sesuai ketentuan termasuk sumber daya manusia yang kompeten sehingga penyusunan laporan dilakukan secara lengkap, akurat, kini, utuh, dan tepat waktu.\n" .
                    "b. BPR memiliki pelaporan internal yang didukung oleh sistem informasi manajemen dan dapat meningkatkan kualitas proses pengambilan keputusan oleh Direksi dan kualitas proses pengawasan oleh Dewan Komisaris, serta tidak terdapat penyalahgunaan dan pemanfaatan dalam rangka rekayasa keuangan dan/atau rekayasa hukum.\n" .
                    "c. BPR telah memiliki dan menginikan kebijakan dan prosedur terkait integritas pelaporan dan sistem teknologi informasi dengan ruang lingkup memadai, sehingga penyampaian pelaporan dilakukan sesuai dengan kebijakan dan prosedur.\n" .
                    "d. BPR melaksanakan transparansi informasi mengenai produk, layanan dan/atau penggunaan data nasabah BPR dengan berpedoman pada persyaratan dan tata cara sesuai ketentuan Otoritas Jasa Keuangan meskipun terdapat laporan pengaduan dari nasabah yang tidak bersifat signifikan dan dapat ditindaklanjuti segera.";
            case 3:
                return "Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan cukup memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang cukup baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
                    "a. BPR memiliki sistem pelaporan keuangan dan nonkeuangan yang didukung oleh sistem informasi manajemen yang cukup memadai sesuai ketentuan termasuk sumber daya manusia yang kompeten sehingga penyusunan laporan dilakukan secara lengkap, akurat, kini, utuh, dan tepat waktu.\n" .
                    "b. BPR belum sepenuhnya memiliki pelaporan internal yang didukung oleh sistem informasi manajemen dan belum dapat meningkatkan kualitas proses pengambilan keputusan oleh Direksi dan kualitas proses pengawasan oleh Dewan Komisaris, walaupun tidak terdapat penyalahgunaan dan pemanfaatan dalam rangka rekayasa keuangan dan/atau rekayasa hukum.\n" .
                    "c. BPR telah memiliki kebijakan dan prosedur terkait integritas pelaporan dan sistem teknologi informasi dengan ruang lingkup cukup memadai, sehingga penyampaian pelaporan dilakukan cukup sesuai dengan kebijakan dan prosedur.\n" .
                    "d. BPR melaksanakan transparansi informasi mengenai produk, layanan dan/atau penggunaan data nasabah BPR dengan berpedoman pada persyaratan dan tata cara sesuai ketentuan Otoritas Jasa Keuangan meskipun terdapat laporan pengaduan dari nasabah yang bersifat cukup signifikan dan dapat ditindaklanjuti.";
            case 4:
                return "Belum sepenuhnya terpenuhi struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan kurang memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang kurang baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
                    "a. BPR memiliki sistem pelaporan keuangan dan nonkeuangan yang didukung oleh sistem informasi manajemen yang kurang memadai sesuai ketentuan termasuk sumber daya manusia yang kompeten sehingga penyusunan laporan tidak sepenuhnya dilakukan secara lengkap, akurat, kini, utuh dan tepat waktu.\n" .
                    "b. BPR belum sepenuhnya memiliki pelaporan internal yang didukung oleh sistem informasi manajemen dan belum dapat meningkatkan kualitas proses pengambilan keputusan oleh Direksi dan kualitas proses pengawasan oleh Dewan Komisaris, serta terdapat penyalahgunaan dan pemanfaatan dalam rangka rekayasa keuangan dan/atau rekayasa hukum.\n" .
                    "c. BPR telah memiliki kebijakan dan prosedur terkait integritas pelaporan dan sistem teknologi informasi dengan ruang lingkup kurang memadai, sehingga penyampaian pelaporan dilakukan kurang sesuai dengan kebijakan dan prosedur.\n" .
                    "d. BPR belum sepenuhnya melaksanakan transparansi informasi mengenai produk, layanan dan/atau penggunaan data nasabah BPR dengan berpedoman pada persyaratan dan tata cara sesuai ketentuan Otoritas Jasa Keuangan sehingga terdapat laporan pengaduan dari nasabah yang bersifat signifikan dan tidak ditindaklanjuti segera.";
            case 5:
                return "Tidak terpenuhi struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan tidak memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang tidak baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
                    "a. BPR tidak memiliki sistem pelaporan keuangan dan nonkeuangan yang didukung oleh sistem informasi manajemen sesuai ketentuan termasuk sumber daya manusia yang tidak kompeten sehingga penyusunan laporan dilakukan secara tidak lengkap, tidak akurat, tidak kini, tidak utuh, dan disampaikan melebihi batas waktu.\n" .
                    "b. BPR tidak memiliki pelaporan internal yang didukung oleh sistem informasi manajemen sehingga tidak dapat meningkatkan kualitas proses pengambilan keputusan oleh Direksi dan kualitas proses pengawasan oleh Dewan Komisaris, serta terdapat penyalahgunaan dan pemanfaatan dalam rangka rekayasa keuangan dan/atau rekayasa hukum.\n" .
                    "c. BPR tidak memiliki kebijakan dan prosedur terkait integritas pelaporan dan sistem teknologi informasi, sehingga penyampaian pelaporan tidak dilakukan sesuai dengan kebijakan dan prosedur.\n" .
                    "d. BPR tidak melaksanakan transparansi informasi mengenai produk, layanan dan/atau penggunaan data nasabah BPR dengan berpedoman pada persyaratan dan tata cara sesuai ketentuan Otoritas Jasa Keuangan sehingga terdapat laporan pengaduan dari nasabah dan tidak dapat ditindaklanjuti.";
            default:
                return "Nilai tidak valid.";
        }
    }

    public function ubah($data, $faktor11Id, $kodebpr, $periodeId)
    {
        return $this->builder
            ->where('faktor11id', $faktor11Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->update($data);
    }

    public function ubahkesimpulan($data, $faktor11Id, $kodebpr, $periodeId)
    {
        return $this->builder
            ->where('faktor11id', $faktor11Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->update($data);
    }

}