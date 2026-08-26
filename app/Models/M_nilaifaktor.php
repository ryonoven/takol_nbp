<?php
namespace App\Models;

use CodeIgniter\Model;

class M_nilaifaktor extends Model
{
    protected $table = 'nilaifaktor';
    protected $primaryKey = 'id';
    protected $allowedFields = [
    'faktor1id', 'nilai', 'keterangan', 'user_id', 'fullname', 'kodebpr', 
    'created_at', 'periode_id', 'nfaktor', 'penjelasfaktor', 
    'is_approved', 'approved_by', 'approved_at', 
    'accdekom', 'accdekom_by', 'accdekom_at', 
    'accdekom2', 'accdekom2_by', 
    'accdir2', 'accdir2_by',
    'positifstruktur', 'negatifstruktur',
    'positifproses', 'negatifproses',
    'positifhasil', 'negatifhasil',
];
    protected $useTimestamps = false;
    private static $rataRataCache = [];
    private static $penjelasanCache = [];


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
        return $this->builder->get()->getResultArray();
    }

    public function getAllDataWithNilai($kodebpr, $periodeId)
    {
        return $this->db->table('faktor as f')
            ->select('f.*, n.nilai, n.keterangan, n.id as nilai_id, n.accdekom, n.accdekom2, n.accdir2, n.is_approved, n.positifstruktur, n.negatifstruktur, n.positifproses, n.negatifproses, n.positifhasil, n.negatifhasil')
            ->join('nilaifaktor as n', 'n.faktor1id = f.id AND n.kodebpr = ' . $this->db->escape($kodebpr) . ' AND n.periode_id = ' . $this->db->escape($periodeId), 'left')
            ->where('f.id <=', 11)
            ->orderBy('f.id', 'ASC')
            ->get()
            ->getResultArray();
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
            ->where('nilaifaktor.faktor1id', $faktorId)
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
        $deleteResult = $this->builder->delete(['faktor1id' => $faktorId]);

        if ($deleteResult) {
            return true;
        } else {
            return false;
        }
    }

    public function approveFaktorByKodebprAndPeriode($kodebpr, $periodeId)
    {

        return $this->builder
            ->where('faktor1id', 12)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->set(['is_approved' => 1])
            ->update();
    }


    public function ubahBerdasarkanFaktorId($data, $faktorId, $kodebpr, $periodeId)
    {
        return $this->builder()
            ->where('faktor1id', $faktorId)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->update($data);
    }

    public function hitungRataRata($faktorId, $kodebpr, $periodeId = null)
    {
        if ($periodeId === null) {
            $periodeId = session('active_periode');
        }

        $cacheKey = "{$faktorId}_{$kodebpr}_{$periodeId}";
        if (isset(self::$rataRataCache[$cacheKey])) {
            return self::$rataRataCache[$cacheKey];
        }

        $result = $this->db->table($this->table)
            ->selectAvg('nilai', 'avg_nilai')
            ->where('faktor1id >=', 1)
            ->where('faktor1id <=', 11)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->get()
            ->getRowArray();

        $avgNilai = $result['avg_nilai'] ?? 0;

        if ($avgNilai > 0) {
            $rataRata = ($avgNilai - floor($avgNilai) >= 0.5) ? ceil($avgNilai) : floor($avgNilai);
        } else {
            $rataRata = 0;
        }

        self::$rataRataCache[$cacheKey] = (int) $rataRata;

        return (int) $rataRata;
    }

    public function insertOrUpdateRataRata($rataRata, $faktorId, $kodebpr, $periodeId = null)
    {
        if ($periodeId === null) {
            $periodeId = session('active_periode');
        }

        $userId = service('authentication')->id();
        $userModel = new M_user();
        $user = $userModel->find($userId);
        $fullname = $user['fullname'] ?? 'Unknown';

        $penjelasfaktor = $this->getPenjelasanNilai($rataRata);
        $currentDateTime = date('Y-m-d H:i:s');

        $data = [
            'faktor1id' => 12,
            'nfaktor' => $rataRata,
            'penjelasfaktor' => $penjelasfaktor,
            'fullname' => $fullname,
            'user_id' => $userId,
            'kodebpr' => $kodebpr,
            'created_at' => $currentDateTime,
            'periode_id' => $periodeId,
            'keterangan' => 'Nilai rata-rata faktor 1-11'
        ];

        $existing = $this->where('faktor1id', 12)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if ($existing) {
            return $this->update($existing['id'], $data);
        } else {
            return $this->insert($data);
        }
    }

    public function getPenjelasanNilai($rataRata)
    {
        $rataRata = (int) $rataRata;

        if (isset(self::$penjelasanCache[$rataRata])) {
            return self::$penjelasanCache[$rataRata];
        }

        $penjelasan = match ($rataRata) {
            1 => "Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai ketentuan, proses pelaksanaan tata kelola dilakukan dengan sangat memadai, dan ditunjukkan dengan hasil pelaksanaan tata kelola yang sangat baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
            "a. Struktur pemegang saham memenuhi seluruh ketentuan dan pelaksanaan tata kelola sangat memadai sehingga tidak terdapat benturan kepentingan, intervensi, mengambil keuntungan pribadi atau kepentingan golongan tertentu, dan/atau keputusan pengangkatan, penggantian, atau pemberhentian anggota Direksi dan/atau Dewan Komisaris sesuai dengan ketentuan peraturan perundang-undangan.\n" .
            "b. Seluruh pengambilan kebijakan aksi korporasi melalui RUPS sejalan dengan anggaran dasar, ketentuan peraturan perundang-undangan, dan rencana strategis sehingga perencanaan pengembangan BPR terealisasikan sepenuhnya yang tercermin pada pemenuhan ketentuan permodalan, kinerja keuangan, dan/atau perkembangan kegiatan usaha BPR.\n" .
            "c. Kebijakan penggunaan laba dan pembagian dividen telah dievaluasi secara berkala sehingga seluruh pelaksanaan penggunaan laba dan pembagian dividen telah sesuai dengan kebijakan yang ditetapkan.",

            2 => "Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
            "a. Struktur pemegang saham memenuhi seluruh ketentuan dan pelaksanaan tata kelola memadai sehingga benturan kepentingan dapat diselesaikan, intervensi yang timbul tidak signifikan, tidak mengambil keuntungan pribadi atau kepentingan golongan tertentu, dan/atau keputusan pengangkatan, penggantian, atau pemberhentian anggota Direksi dan/atau Dewan Komisaris sesuai dengan ketentuan peraturan perundang-undangan.\n" .
            "b. Sebagian besar pengambilan kebijakan aksi korporasi melalui RUPS sejalan dengan anggaran dasar, ketentuan peraturan perundang-undangan, dan rencana strategis sehingga perencanaan pengembangan BPR sebagian besar terealisasikan yang tercermin pada pemenuhan ketentuan permodalan, kinerja keuangan, dan/atau perkembangan kegiatan usaha BPR.\n" .
            "c. Kebijakan penggunaan laba dan pembagian dividen telah dievaluasi sehingga sebagian besar pelaksanaan penggunaan laba dan pembagian dividen telah sesuai dengan kebijakan yang ditetapkan.",

            3 => "Belum sepenuhnya terpenuhi struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan kurang memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang kurang baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
            "a. Struktur pemegang saham memenuhi seluruh ketentuan dan pelaksanaan tata kelola sangat memadai sehingga tidak terdapat benturan kepentingan, intervensi, mengambil keuntungan pribadi atau kepentingan golongan tertentu, dan/atau keputusan pengangkatan, penggantian, atau pemberhentian anggota Direksi dan/atau Dewan Komisaris sesuai dengan ketentuan peraturan perundang-undangan.\n" .
            "b. Seluruh pengambilan kebijakan aksi korporasi melalui RUPS sejalan dengan anggaran dasar, ketentuan peraturan perundang-undangan, dan rencana strategis sehingga perencanaan pengembangan BPR terealisasikan sepenuhnya yang tercermin pada pemenuhan ketentuan permodalan, kinerja keuangan, dan/atau perkembangan kegiatan usaha BPR.\n" .
            "c. Kebijakan penggunaan laba dan pembagian dividen telah dievaluasi secara berkala sehingga seluruh pelaksanaan penggunaan laba dan pembagian dividen telah sesuai dengan kebijakan yang ditetapkan.",

            4 => "Belum sepenuhnya terpenuhi struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan kurang memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang kurang baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
            "a. Struktur pemegang saham memenuhi sebagian ketentuan dan pelaksanaan tata kelola kurang memadai sehingga benturan kepentingan kurang dapat diselesaikan, intervensi yang timbul cukup signifikan, mengambil keuntungan pribadi atau kepentingan golongan tertentu, dan/atau keputusan pengangkatan, penggantian, atau pemberhentian anggota Direksi dan/atau Dewan Komisaris kurang sesuai dengan ketentuan peraturan perundang-undangan.\n" .
            "b. Sebagian kecil pengambilan kebijakan aksi korporasi melalui RUPS sejalan dengan anggaran dasar, ketentuan peraturan perundang-undangan, dan rencana strategis sehingga perencanaan pengembangan BPR sebagian kecil terealisasikan yang tercermin pada pemenuhan ketentuan permodalan, kinerja keuangan, dan/atau perkembangan kegiatan usaha BPR.\n" .
            "c. Sebagian kebijakan penggunaan laba dan pembagian dividen telah dievaluasi sehingga sebagian kecil pelaksanaan penggunaan laba dan pembagian dividen telah sesuai dengan kebijakan yang ditetapkan.",

            5 => "Tidak terpenuhi struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan tidak memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang tidak baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
            "a. Struktur pemegang saham tidak memenuhi ketentuan dan pelaksanaan tata kelola tidak memadai sehingga benturan kepentingan tidak dapat diselesaikan, intervensi yang timbul signifikan, mengambil keuntungan pribadi atau kepentingan golongan tertentu, dan/atau keputusan pengangkatan, penggantian, atau pemberhentian anggota Direksi dan/atau Dewan Komisaris tidak sesuai dengan ketentuan peraturan perundang-undangan.\n" .
            "b. Pengambilan kebijakan aksi korporasi tidak melalui RUPS dan tidak sejalan dengan anggaran dasar, ketentuan peraturan perundang-undangan, dan rencana strategis sehingga perencanaan pengembangan BPR tidak terealisasikan yang tercermin pada pemenuhan ketentuan permodalan, kinerja keuangan, dan/atau perkembangan kegiatan usaha BPR.\n" .
            "c. Kebijakan penggunaan laba dan pembagian dividen tidak dievaluasi sehingga pelaksanaan penggunaan laba dan pembagian dividen tidak sesuai dengan kebijakan yang ditetapkan.",

            default => "Nilai tidak valid (nilai harus antara 1-5)."
        };

        self::$penjelasanCache[$rataRata] = $penjelasan;
        return $penjelasan;
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

    public function clearCache()
    {
        self::$rataRataCache = [];
        self::$penjelasanCache = [];
    }

}