<?php
namespace App\Models;

use CodeIgniter\Model;

class M_nilaifaktor10 extends Model
{
    protected $table = 'nilaifaktor10';
    protected $primaryKey = 'id';
    protected $allowedFields = ['faktor10id', 'nilai', 'keterangan', 'user_id', 'fullname', 'kodebpr', 'created_at', 'periode_id', 'nfaktor10', 'penjelasfaktor', 'is_approved', 'approved_by', 'approved_at', 'accdekom', 'accdekom_by', 'accdekom_at', 'accdekom2', 'accdekom2_by', 'accdir2', 'accdir2_by'];
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
    public function tambahNilai($data, $faktor10Id, $kodebpr)
    {
        return $this->builder->insert($data);
    }

    public function getDataByKodebprAndPeriode($kodebpr, $periodeId)
    {
        return $this->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->findAll();
    }

    // Di dalam App\Models\M_nilaifaktor10

    public function getDataByKodebpr($kodebpr)
    {
        return $this->builder()
            ->where('kodebpr', $kodebpr)
            ->get()
            ->getResultArray();
    }

    public function getKomentarByFaktorIdAndKodebpr($faktor10Id, $kodebpr)
    {
        return $this->db->table($this->table)
            ->select('nilaifaktor10.*, users.fullname')
            ->join('users', 'users.id = nilaifaktor10.user_id', 'left')
            ->where('nilaifaktor10.faktor10id', $faktor10Id)
            ->where('nilaifaktor10.kodebpr', $kodebpr)
            ->orderBy('nilaifaktor10.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function resetAutoIncrement()
    {
        $this->db->query('ALTER TABLE nilaifaktor10 AUTO_INCREMENT = 1');
    }

    public function getKomentarByFaktorId($faktor10Id)
    {
        return $this->db->table($this->table)
            ->select('nilaifaktor10.*, users.fullname')
            ->join('users', 'users.id = nilaifaktor10.user_id', 'left')
            ->where('nilaifaktor10.faktor10id', $faktor10Id) // INI KUNCI FILTERING
            ->orderBy('nilaifaktor10.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }


    public function setIncrement($value)
    {
        $value = (int) $value;
        $sql = 'ALTER TABLE nilaifaktor10 AUTO_INCREMENT = ?';
        $query = $this->db->query($sql, $value);
        return $query;
    }

    public function hapus($faktor10Id)
    {
        // Menghapus data berdasarkan faktor10id
        $deleteResult = $this->builder->delete(['faktor10id' => $faktor10Id]);

        // Periksa apakah penghapusan berhasil
        if ($deleteResult) {
            return true; // Berhasil
        } else {
            return false; // Gagal
        }
    }

    public function approveFaktorByKodebprAndPeriode($kodebpr, $periodeId)
    {
        // Update nilai is_approved menjadi 1 untuk faktor10id = 6, berdasarkan kodebpr dan periode_id
        return $this->builder
            ->where('faktor10id', 6)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->set(['is_approved' => 1]) // Mengatur is_approved menjadi 1
            ->update();
    }


    public function ubahBerdasarkanFaktorId($data, $faktor10Id, $kodebpr, $periodeId)
    {
        // Pastikan update hanya terjadi untuk faktor10id, kodebpr, dan periode_id yang sesuai
        $this->builder->where('faktor10id', $faktor10Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId);
        return $this->builder->update($data);
    }

    public function hitungRataRata($faktor10Id, $kodebpr)
    {

        $periodeId = session('active_periode');
        // Ambil semua nilai untuk faktor5id dari 1 sampai 5
        $query = $this->builder()
            ->select('nilai')
            ->where('faktor10id >=', 1)
            ->where('faktor10id <=', 5)
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

    public function insertOrUpdateRataRata($rataRata, $faktor10Id, $kodebpr)
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

        $rataRata = $this->hitungRataRata($faktor10Id, $kodebpr);

        // Check if the record already exists for faktor10id = 6
        $existing = $this->where('faktor10id', 6)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $currentDateTime = date('Y-m-d H:i:s');

        // Prepare the data to be inserted or updated
        $data = [
            'faktor10id' => 6,
            'nfaktor10' => $rataRata,
            'penjelasfaktor' => $penjelasfaktor, // Store explanation in penjelasfaktor column
            'fullname' => $fullname,
            'user_id' => $userId,
            'kodebpr' => $kodebpr,
            'created_at' => $currentDateTime,
            // 'is_approved' => $associatedNilai['is_approved'] ?? 0,
            // 'approved_by' => $userId,
            // 'approved_at' => $currentDateTime,
            'periode_id' => $periodeId, // Use the current date for the period
            'keterangan' => 'Nilai rata-rata faktor 1-5'
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
                    "a. BPR telah memiliki, mengevaluasi, dan menginikan secara berkala kebijakan, sistem dan prosedur tertulis terkait BMPK dengan ruang lingkup sangat memadai, serta melaksanakan kebijakan, sistem dan prosedur, termasuk sosialisasi kebijakan BMPK secara berkala kepada seluruh sumber daya manusia BPR.\n" .
                    "b. Proses pemberian kredit oleh BPR kepada pihak terkait dan/atau pemberian kredit besar telah memenuhi Peraturan Otoritas Jasa Keuangan mengenai BMPK dan memperhatikan prinsip kehati-hatian maupun peraturan perundang-undangan, termasuk melakukan pemantauan terhadap seluruh proses pemberian kredit secara berkala sehingga tidak terdapat pelanggaran dan pelampauan BMPK.\n" .
                    "c. Laporan pemberian kredit oleh BPR kepada pihak terkait dan/atau pemberian kredit yang melanggar dan/atau melampaui BMPK telah disampaikan secara berkala kepada Otoritas Jasa Keuangan secara lengkap, akurat, kini, utuh, dan tepat waktu sesuai ketentuan Otoritas Jasa Keuangan.";
            case 2:
                return "Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
                    "a. BPR telah memiliki, mengevaluasi, dan menginikan kebijakan, sistem dan prosedur tertulis terkait BMPK dengan ruang lingkup memadai, serta melaksanakan kebijakan, sistem dan prosedur, termasuk sosialisasi kebijakan BMPK kepada seluruh sumber daya manusia BPR.\n" .
                    "b. Proses pemberian kredit oleh BPR kepada pihak terkait dan/atau pemberian kredit besar sebagian besar telah memenuhi Peraturan Otoritas Jasa Keuangan mengenai BMPK dan memperhatikan prinsip kehati hatian maupun peraturan perundang-undangan, termasuk melakukan pemantauan terhadap proses pemberian kredit sehingga penyelesaian pelanggaran dan/atau pelampauan BMPK dilakukan dengan segera.\n" .
                    "c. Laporan pemberian kredit oleh BPR kepada pihak terkait dan/atau pemberian kredit yang melanggar dan/atau melampaui BMPK telah disampaikan secara berkala kepada Otoritas Jasa Keuangan secara lengkap, akurat, kini, utuh, dan tepat waktu sesuai ketentuan Otoritas Jasa Keuangan.";
            case 3:
                return "Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan cukup memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang cukup baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
                    "a. BPR telah memiliki dan mengevaluasi kebijakan, sistem dan prosedur tertulis terkait BMPK dengan ruang lingkup cukup memadai, serta melaksanakan kebijakan, sistem dan prosedur, termasuk sosialisasi kebijakan BMPK kepada seluruh sumber daya manusia BPR.\n" .
                    "b. Proses pemberian kredit oleh BPR kepada pihak terkait dan/atau pemberian kredit besar telah memenuhi sebagian Peraturan Otoritas Jasa Keuangan mengenai BMPK dan memperhatikan prinsip kehati-hatian maupun peraturan perundang-undangan, termasuk melakukan pemantauan terhadap proses pemberian kredit sehingga penyelesaian pelanggaran dan/atau pelampauan BMPK dilakukan dengan baik.\n" .
                    "c. Laporan pemberian kredit oleh BPR kepada pihak terkait dan/atau pemberian kredit yang melanggar dan/atau melampaui BMPK telah disampaikan kepada Otoritas Jasa Keuangan secara lengkap, akurat, kini, utuh, dan tepat waktu sesuai ketentuan Otoritas Jasa Keuangan.";
            case 4:
                return "Belum sepenuhnya terpenuhi struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan kurang memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang kurang baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
                    "a. BPR telah memiliki kebijakan, sistem dan prosedur tertulis terkait BMPK namun ruang lingkup kurang memadai, sehingga pelaksanaan penyelesaian pelanggaran dan/atau pelampauan BMPK tidak terlaksana dengan baik.\n" .
                    "b. Proses pemberian kredit oleh BPR kepada pihak terkait dan/atau pemberian kredit besar hanya memenuhi sebagian kecil Peraturan Otoritas Jasa Keuangan mengenai BMPK dan memperhatikan prinsip kehati hatian maupun peraturan perundang-undangan, termasuk tidak sepenuhnya melakukan pemantauan terhadap proses pemberian kredit sehingga penyelesaian pelanggaran dan/atau pelampauan BMPK tidak dilakukan dengan baik.\n" .
                    "c. Laporan pemberian kredit oleh BPR kepada pihak terkait dan/atau pemberian kredit yang melanggar dan/atau melampaui BMPK tidak sepenuhnya disampaikan kepada Otoritas Jasa Keuangan secara lengkap, akurat, kini, utuh, dan tepat waktu sesuai ketentuan Otoritas Jasa Keuangan.";
            case 5:
                return "Tidak terpenuhi struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan tidak memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang tidak baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
                    "a. BPR tidak memiliki kebijakan, sistem dan prosedur tertulis terkait BMPK namun ruang lingkup, sehingga pelaksanaan penyelesaian pelanggaran dan/atau pelampauan BMPK tidak terlaksana dengan baik.\n" .
                    "b. Proses pemberian kredit oleh BPR kepada pihak terkait dan/atau pemberian kredit besar tidak memenuhi Peraturan Otoritas Jasa Keuangan mengenai BMPK dan memperhatikan prinsip kehati-hatian maupun peraturan perundang-undangan, termasuk tidak melakukan pemantauan terhadap proses pemberian kredit sehingga penyelesaian pelanggaran dan/atau pelampauan BMPK tidak dilakukan dengan baik.\n" .
                    "c. Laporan pemberian kredit oleh BPR kepada pihak terkait dan/atau pemberian kredit yang melanggar dan/atau melampaui BMPK tidak disampaikan kepada Otoritas Jasa Keuangan secara lengkap, akurat, kini, utuh, dan tepat waktu sesuai ketentuan Otoritas Jasa Keuangan.";
            default:
                return "Nilai tidak valid.";
        }
    }

    public function ubah($data, $faktor10Id, $kodebpr, $periodeId)
    {
        return $this->builder
            ->where('faktor10id', $faktor10Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->update($data);
    }

    public function ubahkesimpulan($data, $faktor10Id, $kodebpr, $periodeId)
    {
        return $this->builder
            ->where('faktor10id', $faktor10Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->update($data);
    }

}