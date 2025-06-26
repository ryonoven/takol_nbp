<?php
namespace App\Models;

use CodeIgniter\Model;

class M_nilaifaktor9 extends Model
{
    protected $table = 'nilaifaktor9';
    protected $primaryKey = 'id';
    protected $allowedFields = ['faktor9id', 'nilai', 'keterangan', 'user_id', 'fullname', 'kodebpr', 'created_at', 'periode_id', 'nfaktor9', 'penjelasfaktor', 'is_approved', 'approved_by', 'approved_at', 'accdekom', 'accdekom_by', 'accdekom_at','accdekom2', 'accdekom2_by', 'accdir2', 'accdir2_by'];
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
    public function tambahNilai($data, $faktor9Id, $kodebpr)
    {
        return $this->builder->insert($data);
    }

    public function getDataByKodebprAndPeriode($kodebpr, $periodeId)
    {
        return $this->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->findAll();
    }

    // Di dalam App\Models\M_nilaifaktor9

    public function getDataByKodebpr($kodebpr)
    {
        return $this->builder()
            ->where('kodebpr', $kodebpr)
            ->get()
            ->getResultArray();
    }

    public function getKomentarByFaktorIdAndKodebpr($faktor9Id, $kodebpr)
    {
        return $this->db->table($this->table)
            ->select('nilaifaktor9.*, users.fullname')
            ->join('users', 'users.id = nilaifaktor9.user_id', 'left')
            ->where('nilaifaktor9.faktor9id', $faktor9Id)
            ->where('nilaifaktor9.kodebpr', $kodebpr)
            ->orderBy('nilaifaktor9.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function resetAutoIncrement()
    {
        $this->db->query('ALTER TABLE nilaifaktor9 AUTO_INCREMENT = 1');
    }

    public function getKomentarByFaktorId($faktor9Id)
    {
        return $this->db->table($this->table)
            ->select('nilaifaktor9.*, users.fullname')
            ->join('users', 'users.id = nilaifaktor9.user_id', 'left')
            ->where('nilaifaktor9.faktor9id', $faktor9Id) // INI KUNCI FILTERING
            ->orderBy('nilaifaktor9.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }


    public function setIncrement($value)
    {
        $value = (int) $value;
        $sql = 'ALTER TABLE nilaifaktor9 AUTO_INCREMENT = ?';
        $query = $this->db->query($sql, $value);
        return $query;
    }

    public function hapus($faktor9Id)
    {
        // Menghapus data berdasarkan faktor9id
        $deleteResult = $this->builder->delete(['faktor9id' => $faktor9Id]);

        // Periksa apakah penghapusan berhasil
        if ($deleteResult) {
            return true; // Berhasil
        } else {
            return false; // Gagal
        }
    }

    public function approveFaktorByKodebprAndPeriode($kodebpr, $periodeId)
    {
        // Update nilai is_approved menjadi 1 untuk faktor9id = 18, berdasarkan kodebpr dan periode_id
        return $this->builder
            ->where('faktor9id', 18)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->set(['is_approved' => 1]) // Mengatur is_approved menjadi 1
            ->update();
    }


    public function ubahBerdasarkanFaktorId($data, $faktor9Id, $kodebpr, $periodeId)
    {
        // Pastikan update hanya terjadi untuk faktor9id, kodebpr, dan periode_id yang sesuai
        $this->builder->where('faktor9id', $faktor9Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId);
        return $this->builder->update($data);
    }

    public function hitungRataRata($faktor9Id, $kodebpr)
    {

        $periodeId = session('active_periode');
        // Ambil semua nilai untuk faktor17id dari 1 sampai 17
        $query = $this->builder()
            ->select('nilai')
            ->where('faktor9id >=', 1)
            ->where('faktor9id <=', 17)
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

    public function insertOrUpdateRataRata($rataRata, $faktor9Id, $kodebpr)
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

        $rataRata = $this->hitungRataRata($faktor9Id, $kodebpr);

        // Check if the record already exists for faktor9id = 18
        $existing = $this->where('faktor9id', 18)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $currentDateTime = date('Y-m-d H:i:s');

        // Prepare the data to be inserted or updated
        $data = [
            'faktor9id' => 18,
            'nfaktor9' => $rataRata,
            'penjelasfaktor' => $penjelasfaktor, // Store explanation in penjelasfaktor column
            'fullname' => $fullname,
            'user_id' => $userId,
            'kodebpr' => $kodebpr,
            'created_at' => $currentDateTime,
            // 'is_approved' => $associatedNilai['is_approved'] ?? 0,
            // 'approved_by' => $userId,
            // 'approved_at' => $currentDateTime,
            'periode_id' => $periodeId, // Use the current date for the period
            'keterangan' => 'Nilai rata-rata faktor 1-17'
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
                    "a. BPR memenuhi seluruh persyaratan terkait dengan komite, satuan kerja, dan/atau Pejabat Eksekutif yang bertanggung jawab terhadap penerapan fungsi manajemen risiko, termasuk fungsi anti fraud program anti pencucian uang dan pencegahan pendanaan terorisme sebagaimana diatur dalam Peraturan Otoritas Jasa Keuangan dan ketentuan peraturan perundang-undangan serta penerapan fungsi manajemen risiko dilakukan dengan baik sehingga: (1) peringkat risiko sangat rendah; (2) tidak terdapat fraud; dan/atau (3) peringkat program anti pencucian uang dan pencegahan pendanaan terorisme sangat rendah.\n" .
                    "b. BPR telah memiliki dan menginikan secara berkala pedoman manajemen risiko, prosedur manajemen risiko, penetapan limit risiko, serta kebijakan prosedur secara tertulis mengenai pengelolaan risiko yang melekat pada produk dan aktivitas baru dengan ruang lingkup sangat memadai, dan penerapan manajemen risiko memperhatikan pedoman dan kebijakan tersebut.\n" .
                    "c. Seluruh pelaksanaan tugas dan fungsi Direksi dan Dewan Komisaris terhadap penerapan manajemen risiko dilakukan sesuai dengan ketentuan peraturan perundang-undangan maupun pedoman, termasuk mengembangkan budaya manajemen risiko pada seluruh jenjang organisasi dan peningkatan kompetensi sumber daya manusia.";
            case 2:
                return "Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
                    "a. BPR memenuhi seluruh persyaratan terkait dengan komite, satuan kerja, dan/atau Pejabat Eksekutif yang bertanggung jawab terhadap penerapan fungsi manajemen risiko, termasuk fungsi anti fraud program anti pencucian uang dan pencegahan pendanaan terorisme sebagaimana diatur dalam Peraturan Otoritas Jasa Keuangan dan ketentuan peraturan perundang-undangan serta penerapan fungsi manajemen risiko dilakukan dengan baik sehingga: (1) peringkat risiko rendah; (2) tidak terdapat fraud; dan/atau; (3) peringkat program anti pencucian uang dan pencegahan pendanaan terorisme rendah.\n" .
                    "b. BPR telah memiliki dan menginikan pedoman manajemen risiko, prosedur manajemen risiko, penetapan limit risiko, serta kebijakan prosedur secara tertulis mengenai pengelolaan risiko yang melekat pada produk dan aktivitas baru dengan ruang lingkup memadai, dan penerapan manajemen risiko memperhatikan pedoman dan kebijakan tersebut\n" .
                    "c. Sebagian besar pelaksanaan tugas dan fungsi Direksi dan Dewan Komisaris terhadap penerapan manajemen risiko dilakukan sesuai dengan ketentuan peraturan perundang-undangan maupun pedoman, termasuk mengembangkan budaya manajemen risiko pada sebagian besar jenjang organisasi dan peningkatan kompetensi sumber daya manusia.";
            case 3:
                return "Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan cukup memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang cukup baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
                    "a. BPR memenuhi seluruh persyaratan terkait dengan komite, satuan kerja, dan/atau Pejabat Eksekutif yang bertanggung jawab terhadap penerapan fungsi manajemen risiko, termasuk fungsi anti fraud program anti pencucian uang dan pencegahan pendanaan terorisme sebagaimana diatur dalam Peraturan Otoritas Jasa Keuangan dan ketentuan peraturan perundang-undangan serta penerapan fungsi manajemen risiko dilakukan dengan cukup baik sehingga: (1) peringkat risiko sedang; (2) tidak terdapat fraud; dan/atau; (3) peringkat program anti pencucian uang dan pencegahan pendanaan terorisme rendah.\n" .
                    "b. BPR telah memiliki pedoman manajemen risiko, prosedur manajemen risiko, penetapan limit risiko, serta kebijakan prosedur secara tertulis mengenai pengelolaan risiko yang melekat pada produk dan aktivitas baru dengan ruang lingkup cukup memadai, dan penerapan manajemen risiko memperhatikan pedoman dan kebijakan tersebut.\n" .
                    "c. Sebagian pelaksanaan tugas dan fungsi Direksi dan Dewan Komisaris terhadap penerapan manajemen risiko dilakukan sesuai dengan ketentuan peraturan perundang-undangan maupun pedoman, termasuk mengembangkan budaya manajemen risiko pada sebagian jenjang organisasi dan peningkatan kompetensi sumber daya manusia.";
            case 4:
                return "Belum sepenuhnya terpenuhi struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan kurang memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang kurang baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
                    "a. BPR memenuhi sebagian persyaratan terkait dengan komite, satuan kerja, dan/atau Pejabat Eksekutif yang bertanggung jawab terhadap penerapan fungsi manajemen risiko, termasuk fungsi anti fraud program anti pencucian uang dan pencegahan pendanaan terorisme sebagaimana diatur dalam Peraturan Otoritas Jasa Keuangan dan ketentuan peraturan perundang-undangan serta penerapan fungsi manajemen risiko dilakukan dengan kurang baik sehingga: (1) peringkat risiko tinggi; (2) terdapat fraud; dan/atau (3) peringkat program anti pencucian uang dan pencegahan pendanaan terorisme tinggi.\n" .
                    "b. BPR telah memiliki pedoman manajemen risiko, prosedur manajemen risiko, penetapan limit risiko, serta kebijakan prosedur secara tertulis mengenai pengelolaan risiko yang melekat pada produk dan aktivitas baru dengan ruang lingkup kurangmemadai, dan penerapan manajemen risiko kurang memperhatikan pedoman dan kebijakan tersebut. \n" .
                    "c. Sebagian kecil pelaksanaan tugas dan fungsi Direksi dan Dewan Komisaris terhadap penerapan manajemen risiko dilakukan sesuai dengan ketentuan peraturan perundang-undangan maupun pedoman, termasuk mengembangkan budaya manajemen risiko pada sebagian kecil jenjang organisasi dan peningkatan kompetensi sumber daya manusia.";
            case 5:
                return "Tidak terpenuhi struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan tidak memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang tidak baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
                    "a. BPR tidak memenuhi persyaratan terkait dengan komite, satuan kerja, dan/atau Pejabat Eksekutif yang bertanggung jawab terhadap penerapan fungsi manajemen risiko, termasuk fungsi anti fraud program anti pencucian uang dan pencegahan pendanaan terorisme sebagaimana diatur dalam Peraturan Otoritas Jasa Keuangan dan ketentuan peraturan perundang-undangan serta penerapan fungsi manajemen risiko dilakukan dengan tidak baik sehingga: (1) peringkat risiko sangat tinggi; (2) terdapat fraud; dan/atau; (3) peringkat program anti pencucian uang dan pencegahan pendanaan terorisme sangat tinggi.\n" .
                    "b. BPR tidak memiliki pedoman manajemen risiko, prosedur manajemen risiko, penetapan limit risiko, serta kebijakan prosedur secara tertulis mengenai pengelolaan risiko yang melekat pada produk dan aktivitas baru sehingga penerapan manajemen risiko tidak memperhatikan pedoman dan kebijakan.\n" .
                    "c. Seluruh pelaksanaan tugas dan fungsi Direksi dan Dewan Komisaris terhadap penerapan manajemen risiko tidak dilakukan sesuai dengan ketentuan peraturan perundang-undangan maupun pedoman, termasuk tidak mengembangkan budaya manajemen risiko pada seluruh jenjang organisasi dan peningkatan kompetensi sumber daya manusia.";
            default:
                return "Nilai tidak valid.";
        }
    }


    public function ubah($data, $faktor9Id, $kodebpr, $periodeId)
    {
        return $this->builder
            ->where('faktor9id', $faktor9Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->update($data);
    }

    public function ubahkesimpulan($data, $faktor9Id, $kodebpr, $periodeId)
    {
        return $this->builder
            ->where('faktor9id', $faktor9Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->update($data);
    }



}