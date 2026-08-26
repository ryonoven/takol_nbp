<?php
namespace App\Models;

use CodeIgniter\Model;

class M_nilaifaktor12 extends Model
{
    protected $table = 'nilaifaktor12';
    protected $primaryKey = 'id';
    protected $allowedFields = ['faktor12id', 'nilai', 'keterangan', 'user_id', 'fullname', 'kodebpr', 'created_at', 'periode_id', 'nfaktor12', 'penjelasfaktor', 'is_approved', 'approved_by', 'approved_at', 'accdekom', 'accdekom_by', 'accdekom_at', 'accdekom2', 'accdekom2_by', 'accdir2', 'accdir2_by', 'positifstruktur','negatifstruktur','positifproses','negatifproses','positifhasil','negatifhasil'];
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
            ->where('nilaifaktor12.faktor12id', $faktor12Id)
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
        $deleteResult = $this->builder->delete(['faktor12id' => $faktor12Id]);

        if ($deleteResult) {
            return true;
        } else {
            return false;
        }
    }

    public function approveFaktorByKodebprAndPeriode($kodebpr, $periodeId)
    {
        return $this->builder
            ->where('faktor12id', 8)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->set(['is_approved' => 1])
            ->update();
    }


    public function ubahBerdasarkanFaktorId($data, $faktor12Id, $kodebpr, $periodeId)
    {
        $this->builder->where('faktor12id', $faktor12Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId);
        return $this->builder->update($data);
    }

    public function hitungRataRata($faktor12Id, $kodebpr, $periodeId = NULL)
    {

        $periodeId = session('active_periode');
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

            $rataRata = $totalNilai / $count;

            $desimal = $rataRata - floor($rataRata);
            if ($desimal >= 0.5) {
                return ceil($rataRata);
            } else {
                return floor($rataRata);
            }
        } else {
            return 0;
        }
    }

    public function insertOrUpdateRataRata($rataRata, $faktor12Id, $kodebpr, $periodeId = NULL)
    {
        $userId = service('authentication')->id();
        $userModel = new M_user();
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

        $data = [
            'faktor12id' => 8,
            'nfaktor12' => $rataRata,
            'penjelasfaktor' => $penjelasfaktor,
            'fullname' => $fullname,
            'user_id' => $userId,
            'kodebpr' => $kodebpr,
            'created_at' => $currentDateTime,
            'periode_id' => $periodeId,
            'keterangan' => 'Nilai rata-rata faktor 1-7'
        ];

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