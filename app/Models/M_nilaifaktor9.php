<?php
namespace App\Models;

use CodeIgniter\Model;

class M_nilaifaktor9 extends Model
{
    protected $table = 'nilaifaktor9';
    protected $primaryKey = 'id';
    protected $allowedFields = ['faktor9id', 'nilai', 'keterangan', 'user_id', 'fullname', 'kodebpr', 'created_at', 'periode_id', 'nfaktor9', 'penjelasfaktor', 'is_approved', 'approved_by', 'approved_at', 'accdekom', 'accdekom_by', 'accdekom_at', 'accdekom2', 'accdekom2_by', 'accdir2', 'accdir2_by', 'positifstruktur', 'negatifstruktur', 'positifproses', 'negatifproses', 'positifhasil', 'negatifhasil'];
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
        return $this->db->table('faktor9 as f')
            ->select('f.*, n.nilai, n.keterangan, n.id as nilai_id, n.accdekom, n.accdekom2, n.accdir2, n.is_approved, n.positifstruktur, n.negatifstruktur, n.positifproses, n.negatifproses, n.positifhasil, n.negatifhasil')
            ->join('nilaifaktor9 as n', 'n.faktor9id = f.id AND n.kodebpr = ' . $this->db->escape($kodebpr) . ' AND n.periode_id = ' . $this->db->escape($periodeId), 'left')
            ->where('f.id <=', 17)
            ->orderBy('f.id', 'ASC')
            ->get()
            ->getResultArray();
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
            ->where('nilaifaktor9.faktor9id', $faktor9Id)
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

    public function approveFaktorByKodebprAndPeriode($kodebpr, $periodeId)
    {
        return $this->builder
            ->where('faktor9id', 18)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->set(['is_approved' => 1])
            ->update();
    }


    public function ubahBerdasarkanFaktorId($data, $faktor9Id, $kodebpr, $periodeId)
    {
        return $this->builder()
            ->where('faktor9id', $faktor9Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->update($data);
    }

    public function hitungRataRata($faktor9Id, $kodebpr, $periodeId = null)
    {
        if ($periodeId === null) {
            $periodeId = session('active_periode');
        }

        $cacheKey = "{$faktor9Id}_{$kodebpr}_{$periodeId}";
        if (isset(self::$rataRataCache[$cacheKey])) {
            return self::$rataRataCache[$cacheKey];
        }

        $result = $this->db->table($this->table)
            ->selectAvg('nilai', 'avg_nilai')
            ->where('faktor9id >=', 1)
            ->where('faktor9id <=', 17)
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

    public function insertOrUpdateRataRata($rataRata, $faktor9Id, $kodebpr, $periodeId = null)
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
            'faktor9id' => 18,
            'nfaktor9' => $rataRata,
            'penjelasfaktor' => $penjelasfaktor,
            'fullname' => $fullname,
            'user_id' => $userId,
            'kodebpr' => $kodebpr,
            'created_at' => $currentDateTime,
            'periode_id' => $periodeId,
            'keterangan' => 'Nilai rata-rata faktor 1-18'
        ];

        $existing = $this->where('faktor9id', 18)
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
            1 => "Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan sangat memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang sangat baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
            "a. Dewan Komisaris memenuhi seluruh persyaratan yang harus dipenuhi selama menjabat sesuai dengan ketentuan sehingga pelaksanaan tugas dan tanggung jawab, termasuk pengambilan keputusan berjalan dengan sangat baik serta hasil kinerja Dewan Komisaris dapat dipertanggungjawabkan sepenuhnya kepada pemegang saham melalui RUPS.\n" .
            "b. Dewan Komisaris telah memiliki dan menginikan secara berkala pedoman dan tata tertib kerja anggota Dewan Komisaris sehingga pelaksanaan tugas dan pengambilan keputusan rapat Dewan Komisaris terlaksana dengan memperhatikan pedoman dan tata tertib kerja.\n" .
            "c. Dewan Komisaris memiliki kemauan dan kemampuan, serta upaya untuk membudayakan pembelajaran secara berkala dan berkelanjutan, sehingga terdapat peningkatan pengetahuan, keahlian, dan kemampuan.\n" .
            "d. Dewan Komisaris telah memiliki dan menginikan secara berkala kebijakan remunerasi dan nominasi sehingga pelaksanaan tugas terlaksana dengan memperhatikan kebijakan remunerasi dan nominasi.",
            2 => "Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
            "a. Dewan Komisaris memenuhi seluruh persyaratan yang harus dipenuhi selama menjabat sesuai dengan ketentuan sehingga pelaksanaan tugas dan tanggung jawab, termasuk pengambilan keputusan berjalan dengan baik serta hasil kinerja Dewan Komisaris dapat dipertanggungjawabkan kepada pemegang saham melalui RUPS.\n" .
            "b. Dewan Komisaris telah memiliki dan menginikan pedoman dan tata tertib kerja anggota Dewan Komisaris sehingga pelaksanaan tugas dan pengambilan keputusan rapat Dewan Komisaris terlaksana dengan memperhatikan pedoman dan tata tertib kerja.\n" .
            "c. Dewan Komisaris memiliki kemauan dan kemampuan, serta upaya untuk membudayakan pembelajaran secara berkala, sehingga terdapat peningkatan pengetahuan, keahlian, dan kemampuan.\n" .
            "d. Dewan Komisaris telah memiliki dan menginikan kebijakan remunerasi dan nominasi sehingga pelaksanaan tugas terlaksana dengan memperhatikan kebijakan remunerasi dan nominasi.",
            3 => "Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan cukup memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang cukup baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
            "a. Dewan Komisaris memenuhi seluruh persyaratan yang harus dipenuhi selama menjabat sesuai dengan ketentuan sehingga pelaksanaan tugas dan tanggung jawab, termasuk pengambilan keputusan berjalan dengan cukup baik serta hasil kinerja Dewan Komisaris dapat dipertanggungjawabkan kepada pemegang saham melalui RUPS.\n" .
            "b. Dewan Komisaris telah memiliki pedoman dan tata tertib kerja anggota Dewan Komisaris sehingga pelaksanaan tugas dan pengambilan keputusan rapat Dewan Komisaris terlaksana dengan memperhatikan pedoman dan tata tertib kerja.\n" .
            "c. Dewan Komisaris memiliki kemauan dan kemampuan, serta upaya untuk membudayakan pembelajaran, sehingga terdapat peningkatan pengetahuan, keahlian, dan kemampuan.\n" .
            "d. Dewan Komisaris telah memiliki kebijakan remunerasi dan nominasi sehingga pelaksanaan tugas terlaksana dengan memperhatikan kebijakan remunerasi dan nominasi.",
            4 => "Belum sepenuhnya terpenuhi struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan kurang memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang kurang baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
            "a. Dewan Komisaris memenuhi sebagian persyaratan yang harus dipenuhi selama menjabat sesuai dengan ketentuan sehingga pelaksanaan tugas dan tanggung jawab, termasuk pengambilan keputusan berjalan dengan kurang baik serta hasil kinerja Dewan Komisaris tidak sepenuhnya dapat dipertanggungjawabkan kepada pemegang saham melalui RUPS.\n" .
            "b. Dewan Komisaris telah memiliki pedoman dan tata tertib kerja anggota Dewan Komisaris namun ruang lingkup belum sesuai dengan ketentuan sehingga pelaksanaan tugas dan pengambilan keputusan rapat Dewan Komisaris tidak terlaksana dengan baik.\n" .
            "c. Dewan Komisaris kurang memiliki kemauan dan kemampuan, serta upaya untuk membudayakan pembelajaran secara berkala, sehingga tidak terdapat peningkatan pengetahuan, keahlian, dan kemampuan.\n" .
            "d. Dewan Komisaris telah memiliki kebijakan remunerasi dan nominasi namun ruang lingkup belum sesuai dengan ketentuan sehingga pelaksanaan tugas tidak terlaksana dengan baik.",
            5 => "Tidak terpenuhi struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan tidak memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang tidak baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n\n" .
            "a. Dewan Komisaris tidak memenuhi seluruh persyaratan yang harus dipenuhi selama menjabat sesuai dengan ketentuan sehingga pelaksanaan tugas dan tanggung jawab, termasuk pengambilan keputusan tidak berjalan dengan baik dan hasil kinerja Dewan Komisaris tidak dapat dipertanggungjawabkan kepada pemegang saham melalui RUPS.\n" .
            "b. Dewan Komisaris tidak memenuhi seluruh persyaratan yang harus dipenuhi selama menjabat sesuai dengan ketentuan sehingga pelaksanaan tugas dan tanggung jawab, termasuk pengambilan keputusan tidak berjalan dengan baik dan hasil kinerja Dewan Komisaris tidak dapat dipertanggungjawabkan kepada pemegang saham melalui RUPS. Dewan Komisaris tidak memiliki pedoman dan tata tertib kerja anggota Dewan Komisaris sehingga pelaksanaan tugas dan pengambilan keputusan rapat Dewan Komisaris tidak dapat terlaksana dengan baik.\n" .
            "c. Dewan Komisaris tidak memiliki kemauan dan kemampuan, serta upaya untuk membudayakan pembelajaran secara berkala, sehingga tidak terdapat peningkatan pengetahuan, keahlian, dan kemampuan.\n" .
            "d. Dewan Komisaris tidak memiliki kebijakan remunerasi dan nominasi sehingga pelaksanaan tugas tidak dapat terlaksana dengan baik.",

            default => "Nilai tidak valid (nilai harus antara 1-5)."
        };
        self::$penjelasanCache[$rataRata] = $penjelasan;
        return $penjelasan;
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

    public function clearCache()
    {
        self::$rataRataCache = [];
        self::$penjelasanCache = [];
    }

}