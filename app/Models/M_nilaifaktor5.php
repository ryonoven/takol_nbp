<?php
namespace App\Models;

use CodeIgniter\Model;

class M_nilaifaktor5 extends Model
{
    protected $table = 'nilaifaktor5';
    protected $primaryKey = 'id';
    protected $allowedFields = ['faktor5id', 'nilai', 'keterangan', 'user_id', 'fullname', 'kodebpr', 'created_at', 'periode_id', 'nfaktor5', 'penjelasfaktor', 'is_approved', 'approved_by', 'approved_at', 'accdekom', 'accdekom_by', 'accdekom_at', 'accdekom2', 'accdekom2_by', 'accdir2', 'accdir2_by'];
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
    public function tambahNilai($data, $faktor5Id, $kodebpr)
    {
        return $this->builder->insert($data);
    }

    public function getDataByKodebprAndPeriode($kodebpr, $periodeId)
    {
        return $this->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->findAll();
    }

    // Di dalam App\Models\M_nilaifaktor5

    public function getDataByKodebpr($kodebpr)
    {
        return $this->builder()
            ->where('kodebpr', $kodebpr)
            ->get()
            ->getResultArray();
    }

    public function getKomentarByFaktorIdAndKodebpr($faktor5Id, $kodebpr)
    {
        return $this->db->table($this->table)
            ->select('nilaifaktor5.*, users.fullname')
            ->join('users', 'users.id = nilaifaktor5.user_id', 'left')
            ->where('nilaifaktor5.faktor5id', $faktor5Id)
            ->where('nilaifaktor5.kodebpr', $kodebpr)
            ->orderBy('nilaifaktor5.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function resetAutoIncrement()
    {
        $this->db->query('ALTER TABLE nilaifaktor5 AUTO_INCREMENT = 1');
    }

    public function getKomentarByFaktorId($faktor5Id)
    {
        return $this->db->table($this->table)
            ->select('nilaifaktor5.*, users.fullname')
            ->join('users', 'users.id = nilaifaktor5.user_id', 'left')
            ->where('nilaifaktor5.faktor5id', $faktor5Id) // INI KUNCI FILTERING
            ->orderBy('nilaifaktor5.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }


    public function setIncrement($value)
    {
        $value = (int) $value;
        $sql = 'ALTER TABLE nilaifaktor5 AUTO_INCREMENT = ?';
        $query = $this->db->query($sql, $value);
        return $query;
    }

    public function hapus($faktor5Id)
    {
        // Menghapus data berdasarkan faktor5id
        $deleteResult = $this->builder->delete(['faktor5id' => $faktor5Id]);

        // Periksa apakah penghapusan berhasil
        if ($deleteResult) {
            return true; // Berhasil
        } else {
            return false; // Gagal
        }
    }

    public function approveFaktorByKodebprAndPeriode($kodebpr, $periodeId)
    {
        // Update nilai is_approved menjadi 1 untuk faktor5id = 6, berdasarkan kodebpr dan periode_id
        return $this->builder
            ->where('faktor5id', 6)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->set(['is_approved' => 1]) // Mengatur is_approved menjadi 1
            ->update();
    }


    public function ubahBerdasarkanFaktorId($data, $faktor5Id, $kodebpr, $periodeId)
    {
        // Pastikan update hanya terjadi untuk faktor5id, kodebpr, dan periode_id yang sesuai
        $this->builder->where('faktor5id', $faktor5Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId);
        return $this->builder->update($data);
    }

    public function hitungRataRata($faktor5Id, $kodebpr)
    {

        $periodeId = session('active_periode');
        // Ambil semua nilai untuk faktor5id dari 1 sampai 5
        $query = $this->builder()
            ->select('nilai')
            ->where('faktor5id >=', 1)
            ->where('faktor5id <=', 5)
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

    public function insertOrUpdateRataRata($rataRata, $faktor5Id, $kodebpr)
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

        $rataRata = $this->hitungRataRata($faktor5Id, $kodebpr);

        // Check if the record already exists for faktor5id = 6
        $existing = $this->where('faktor5id', 6)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $currentDateTime = date('Y-m-d H:i:s');

        // Prepare the data to be inserted or updated
        $data = [
            'faktor5id' => 6,
            'nfaktor5' => $rataRata,
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
                    "a. BPR telah memiliki dan menginikan secara berkala kebijakan benturan kepentingan dengan ruang lingkup sangat memadai.\n" .
                    "b. Tidak terdapat transaksi yang memiliki benturan kepentingan.\n" .
                    "c. Pelaksanaan tugas, fungsi, dan wewenang Direksi, Dewan Komisaris, Pejabat Eksekutif, dan pegawai BPR terkait dengan penanganan benturan kepentingan dilakukan secara sangat baik.";
            case 2:
                return "Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
                    "a. BPR telah memiliki dan menginikan kebijakan benturan kepentingan dengan ruang lingkup memadai, serta berhasil menangani benturan kepentingan dengan baik sesuai dengan kebijakan.\n" .
                    "b. Tidak terdapat transaksi yang memiliki benturan kepentingan dan apabila terdapat benturan kepentingan ditangani dengan baik serta tidak menimbulkan kerugian atau mengurangi keuntungan BPR, diungkapkan seluruhnya dalam setiap keputusan, dan telah terdokumentasi dengan sangat baik.\n" .
                    "c. Pelaksanaan tugas, fungsi, dan wewenang Direksi, Dewan Komisaris, Pejabat Eksekutif, dan pegawai BPR terkait dengan penanganan benturan kepentingan dilakukan secara baik.";
            case 3:
                return "Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan cukup memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang cukup baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
                    "a. BPR telah memiliki kebijakan benturan kepentingan dengan ruang lingkup cukup memadai, serta penanganan benturan kepentingan dilakukan dengan cukup baik sesuai dengan kebijakan.\n" .
                    "b. Terdapat benturan kepentingan yang belum sepenuhnya ditangani dan menimbulkan kerugian atau mengurangi keuntungan BPR, diungkapkan seluruhnya dalam setiap keputusan, dan telah terdokumentasi dengan baik.\n" .
                    "c. Pelaksanaan tugas, fungsi, dan wewenang Direksi, Dewan Komisaris, Pejabat Eksekutif, dan pegawai BPR terkait dengan penanganan benturan kepentingan dilakukan secara cukup baik.";
            case 4:
                return "Belum sepenuhnya terpenuhi struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan kurang memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang kurang baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
                    "a. BPR telah memiliki kebijakan benturan kepentingan dengan ruang lingkup kurang memadai, sehingga penanganan benturan kepentingan kurang berhasil.\n" .
                    "b. Terdapat benturan kepentingan yang belum sepenuhnya ditangani dan menimbulkan kerugian atau mengurangi keuntungan BPR, diungkapkan sebagian dalam setiap keputusan, dan terdokumentasi dengan kurang baik.\n" .
                    "c. Pelaksanaan tugas, fungsi, dan wewenang Direksi, Dewan Komisaris, Pejabat Eksekutif, dan pegawai BPR terkait dengan penanganan benturan kepentingan dilakukan secara kurang baik.";
            case 5:
                return "Tidak terpenuhi struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan tidak memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang tidak baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
                    "a. BPR tidak memiliki kebijakan benturan kepentingan, sehingga penanganan benturan kepentingan tidak berhasil.\n" .
                    "b. Seluruh benturan kepentingan tidak ditangani dan menimbulkan kerugian atau mengurangi keuntungan BPR, tidak diungkapkan dalam setiap keputusan, dan tidak terdokumentasi.\n" .
                    "c. Pelaksanaan tugas, fungsi, dan wewenang Direksi, Dewan Komisaris, Pejabat Eksekutif, dan pegawai BPR terkait dengan penanganan benturan kepentingan dilakukan secara tidak baik.";
            default:
                return "Nilai tidak valid.";
        }
    }


    public function ubah($data, $faktor5Id, $kodebpr, $periodeId)
    {
        return $this->builder
            ->where('faktor5id', $faktor5Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->update($data);
    }

    public function ubahkesimpulan($data, $faktor5Id, $kodebpr, $periodeId)
    {
        return $this->builder
            ->where('faktor5id', $faktor5Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->update($data);
    }



}