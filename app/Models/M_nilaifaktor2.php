<?php
namespace App\Models;

use CodeIgniter\Model;

class M_nilaifaktor2 extends Model
{
    protected $table = 'nilaifaktor2';
    protected $primaryKey = 'id';
    protected $allowedFields = ['faktor2id', 'nilai', 'keterangan', 'user_id', 'fullname', 'kodebpr', 'created_at', 'periode_id', 'nfaktor2', 'penjelasfaktor', 'is_approved', 'approved_by', 'approved_at', 'accdekom', 'accdekom_by', 'accdekom_at', 'accdekom2', 'accdekom2_by', 'accdir2', 'accdir2_by', 'positifstruktur', 'negatifstruktur', 'positifproses', 'negatifproses', 'positifhasil', 'negatifhasil',];
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
        return $this->db->table('faktor2 as f')
            ->select('f.*, n.nilai, n.keterangan, n.id as nilai_id, n.accdekom, n.accdekom2, n.accdir2, n.is_approved, n.positifstruktur, n.negatifstruktur, n.positifproses, n.negatifproses, n.positifhasil, n.negatifhasil')
            ->join('nilaifaktor2 as n', 'n.faktor2id = f.id AND n.kodebpr = ' . $this->db->escape($kodebpr) . ' AND n.periode_id = ' . $this->db->escape($periodeId), 'left')
            ->where('f.id <=', 28)
            ->orderBy('f.id', 'ASC')
            ->get()
            ->getResultArray();
    }
    public function tambahNilai($data, $faktor2Id, $kodebpr)
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

    public function getKomentarByFaktorIdAndKodebpr($faktor2Id, $kodebpr)
    {
        return $this->db->table($this->table)
            ->select('nilaifaktor2.*, users.fullname')
            ->join('users', 'users.id = nilaifaktor2.user_id', 'left')
            ->where('nilaifaktor2.faktor2id', $faktor2Id)
            ->where('nilaifaktor2.kodebpr', $kodebpr)
            ->orderBy('nilaifaktor2.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function resetAutoIncrement()
    {
        $this->db->query('ALTER TABLE nilaifaktor2 AUTO_INCREMENT = 1');
    }

    public function getKomentarByFaktorId($faktor2Id)
    {
        return $this->db->table($this->table)
            ->select('nilaifaktor2.*, users.fullname')
            ->join('users', 'users.id = nilaifaktor2.user_id', 'left')
            ->where('nilaifaktor2.faktor2id', $faktor2Id)
            ->orderBy('nilaifaktor2.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }


    public function setIncrement($value)
    {
        $value = (int) $value;
        $sql = 'ALTER TABLE nilaifaktor2 AUTO_INCREMENT = ?';
        $query = $this->db->query($sql, $value);
        return $query;
    }

    public function approveFaktorByKodebprAndPeriode($kodebpr, $periodeId)
    {
        return $this->builder
            ->where('faktor2id', 29)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->set(['is_approved' => 1])
            ->update();
    }


    public function ubahBerdasarkanFaktorId($data, $faktor2Id, $kodebpr, $periodeId)
    {
        return $this->builder()
            ->where('faktor2id', $faktor2Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->update($data);
    }

    public function hitungRataRata($faktor2Id, $kodebpr, $periodeId = null)
    {
        if ($periodeId === null) {
            $periodeId = session('active_periode');
        }

        $cacheKey = "{$faktor2Id}_{$kodebpr}_{$periodeId}";
        if (isset(self::$rataRataCache[$cacheKey])) {
            return self::$rataRataCache[$cacheKey];
        }

        $result = $this->db->table($this->table)
            ->selectAvg('nilai', 'avg_nilai')
            ->where('faktor2id >=', 1)
            ->where('faktor2id <=', 28)
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

    public function insertOrUpdateRataRata($rataRata, $faktor2Id, $kodebpr, $periodeId = null)
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
            'faktor2id' => 29,
            'nfaktor2' => $rataRata,
            'penjelasfaktor' => $penjelasfaktor,
            'fullname' => $fullname,
            'user_id' => $userId,
            'kodebpr' => $kodebpr,
            'created_at' => $currentDateTime,
            'periode_id' => $periodeId,
            'keterangan' => 'Nilai rata-rata faktor 1-11'
        ];

        $existing = $this->where('faktor2id', 29)
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
            1 => "Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan sangat memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang sangat baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n" .
            "a. Direksi memenuhi seluruh persyaratan yang harus dipenuhi selama menjabat sesuai dengan ketentuan sehingga tugas dan tanggung jawab terlaksana dengan itikad baik, penuh tanggung jawab, kehati-hatian, dan independen, serta hasil kinerja Direksi dapat dipertanggungjawabkan sepenuhnya kepada pemegang saham melalui RUPS.\n" .
            "b. Direksi telah melakukan pemenuhan sumber daya manusia dan struktur organisasi, termasuk membentuk satuan kerja atau mengangkat Pejabat Eksekutif dengan kuantitas dan kualitas sesuai dengan ketentuan dengan mempertimbangkan kompleksitas kegiatan usaha dalam rangka mendukung pelaksanaan tugas dan fungsi Direksi sehingga penyelenggaraan kegiatan usaha pada seluruh jenjang organisasi telah sepenuhnya menerapkan prinsip tata kelola.\n" .
            "c. Direksi telah memiliki dan menginikan secara berkala pedoman dan tata tertib kerja anggota Direksi sehingga pelaksanaan tugas dan pengambilan keputusan rapat Direksi yang bersifat strategis terlaksana dengan memperhatikan pedoman dan tata tertib kerja." .
            "d. Direksi memiliki kemauan dan kemampuan, serta upaya untuk membudayakan pembelajaran secara berkala dan berkelanjutan sehingga terdapat peningkatan pengetahuan, keahlian, dan kemampuan.\n" .
            "e. Direksi sesuai dengan tugas dan tanggung jawab melakukan tindak lanjut seluruh temuan audit atau pemeriksaan, dan rekomendasi dari satuan kerja atau pejabat yang bertanggung jawab terhadap pelaksanaan audit intern, auditor ekstern, dan hasil pengawasan Dewan Komisaris, Otoritas Jasa Keuangan, dan/atau otoritas lain sehingga tidak terdapat temuan serupa dan/atau temuan berulang.",
            2 => "Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n" .
            "a. Direksi memenuhi seluruh persyaratan yang harus dipenuhi selama menjabat sesuai dengan ketentuan sehingga tugas dan tanggung jawab terlaksana dengan baik namun terdapat kelemahan dalam tugas dan tanggung jawab yang tidak signifikan dan dapat diperbaiki dengan segera serta hasil kinerja Direksi dapat dipertanggungjawabkan kepada pemegang saham melalui RUPS.\n" .
            "b. Direksi telah melakukan pemenuhan sumber daya manusia dan struktur organisasi, termasuk membentuk satuan kerja atau mengangkat Pejabat Eksekutif dengan kuantitas dan kualitas sesuai dengan ketentuan dalam rangka mendukung pelaksanaan tugas dan fungsi Direksi sehingga penyelenggaraan kegiatan usaha pada seluruh jenjang organisasi telah menerapkan prinsip tata kelola dengan baik.\n" .
            "c. Direksi telah memiliki dan menginikan secara berkala pedoman dan tata tertib kerja anggota Direksi sehingga pelaksanaan tugas dan pengambilan keputusan rapat Direksi yang bersifat strategis terlaksana dengan memperhatikan pedoman dan tata tertib kerja.\n" .
            "d. Direksi memiliki kemauan dan kemampuan, serta upaya untuk membudayakan pembelajaran secara berkala sehingga terdapat peningkatan pengetahuan, keahlian, dan kemampuan.\n" .
            "e. Direksi sesuai dengan tugas dan tanggung jawab telah melakukan tindak lanjut seluruh temuan audit atau pemeriksaan, dan rekomendasi dari satuan kerja atau pejabat yang bertanggung jawab terhadap pelaksanaan audit intern, auditor ekstern, dan hasil pengawasan Dewan Komisaris, Otoritas Jasa Keuangan, dan/atau otoritas lain namun terdapat temuan yang bersifat administratif.",
            3 => "Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan cukup memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang cukup baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n" .
            "a. Direksi memenuhi seluruh persyaratan yang harus dipenuhi selama menjabat sesuai dengan ketentuan sehingga tugas dan tanggung jawab terlaksana dengan cukup baik namun terdapat kelemahan dalam tugas dan tanggung jawab dan dapat diperbaiki serta hasil kinerja Direksi dapat dipertanggungjawabkan kepada pemegang saham melalui RUPS\n" .
            "b. Direksi telah melakukan pemenuhan sumber daya manusia dan struktur organisasi, termasuk membentuk satuan kerja atau mengangkat Pejabat Eksekutif dengan kuantitas dan kualitas sesuai dengan ketentuan dalam rangka mendukung pelaksanaan tugas dan fungsi Direksi sehingga penyelenggaraan kegiatan usaha pada seluruh jenjang organisasi telah menerapkan prinsip tata kelola dengan cukup baik.\n" .
            "c. Direksi telah memiliki pedoman dan tata tertib kerja anggota Direksi sehingga pelaksanaan tugas dan pengambilan keputusan rapat Direksi yang bersifat strategis terlaksana dengan memperhatikan pedoman dan tata tertib kerja.\n" .
            "d. Direksi memiliki kemauan dan kemampuan, serta upaya untuk membudayakan pembelajaran sehingga terdapat peningkatan pengetahuan, keahlian, dan kemampuan.\n" .
            "e. Direksi sesuai dengan tugas dan tanggung jawab telah melakukan tindak lanjut seluruh temuan audit atau pemeriksaan, dan rekomendasi dari satuan kerja atau pejabat yang bertanggung jawab terhadap pelaksanaan audit intern, auditor ekstern, dan hasil pengawasan Dewan Komisaris, Otoritas Jasa Keuangan, dan/atau otoritas lain namun terdapat temuan berulang yang bersifat administratif.",
            4 => "Belum sepenuhnya terpenuhi struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan kurang memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang kurang baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n" .
            "a. Direksi memenuhi sebagian persyaratan yang harus dipenuhi selama menjabat sesuai dengan ketentuan sehingga pelaksanaan tugas dan tanggung jawab kurang berjalan dengan baik dan hasil kinerja Direksi tidak sepenuhnya dapat dipertanggungjawabkan kepada pemegang saham melalui RUPS.\n" .
            "b. Direksi tidak melakukan pemenuhan sumber daya manusia dan struktur organisasi, termasuk pembentukan satuan kerja atau pengangkatan Pejabat Eksekutif dengan kuantitas dan kualitas yang tidak sesuai dengan ketentuan sehingga kurang mendukung pelaksanaan tugas dan fungsi Direksi sehingga penyelenggaraan kegiatan usaha pada seluruh jenjang organisasi tidak sepenuhnya menerapkan prinsip tata kelola.\n" .
            "c. Direksi telah memiliki pedoman dan tata tertib kerja anggota Direksi namun ruang lingkup belum sesuai dengan ketentuan sehingga pelaksanaan tugas dan pengambilan keputusan rapat Direksi yang bersifat strategis tidak terlaksana dengan baik.\n" .
            "d. Direksi kurang memiliki kemauan dan kemampuan, serta upaya untuk membudayakan pembelajaran secara berkelanjutan sehingga tidak terdapat peningkatan pengetahuan, keahlian, dan kemampuan.\n" .
            "e. Direksi telah melakukan tindak lanjut terhadap sebagian temuan audit atau pemeriksaan, dan rekomendasi dari satuan kerja atau pejabat yang bertanggung jawab terhadap pelaksanaan audit intern, auditor ekstern, dan hasil pengawasan Dewan Komisaris, Otoritas Jasa Keuangan, dan/atau otoritas lain sehingga terdapat temuan dan/atau temuan berulang yang bersifat substantif.",
            5 => "Tidak terpenuhi struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan tidak memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang tidak baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:\n" .
            "a. Direksi tidak memenuhi seluruh persyaratan yang harus dipenuhi selama menjabat sesuai dengan ketentuan sehingga pelaksanaan tugas dan tanggung jawab tidak berjalan dengan baik dan hasil kinerja Direksi tidak dapat dipertanggungjawabkan kepada pemegang saham melalui RUPS.\n" .
            "b. Direksi tidak melakukan pemenuhan sumber daya manusia dan struktur organisasi, termasuk tidak membentuk satuan kerja atau mengangkat Pejabat Eksekutif sesuai dengan ketentuan dalam rangka mendukung pelaksanaan tugas dan fungsi Direksi sehingga prinsip tata kelola tidak dapat diterapkan dalam penyelenggaraan kegiatan usaha pada seluruh jenjang organisasi.\n" .
            "c. Direksi tidak memiliki pedoman dan tata tertib kerja anggota Direksi sehingga pelaksanaan tugas dan pengambilan keputusan rapat Direksi yang bersifat strategis tidak dapat terlaksana dengan baik.\n" .
            "d. Direksi tidak memiliki pedoman dan tata tertib kerja anggota Direksi sehingga pelaksanaan tugas dan pengambilan keputusan rapat Direksi yang bersifat strategis tidak dapat terlaksana dengan baik. Direksi tidak memiliki kemauan dan kemampuan, serta upaya untuk membudayakan pembelajaran secara berkelanjutan sehingga tidak terdapat peningkatan pengetahuan, keahlian, dan kemampuan.\n" .
            "e. Direksi tidak melakukan tindak lanjut seluruh temuan audit atau pemeriksaan, dan rekomendasi dari satuan kerja atau pejabat yang bertanggung jawab terhadap pelaksanaan audit intern, auditor ekstern, dan hasil pengawasan Dewan Komisaris, Otoritas Jasa Keuangan, dan/atau otoritas lain sehingga terdapat temuan dan/atau temuan berulang yang bersifat substantif.",

            default => "Nilai tidak valid (nilai harus antara 1-5)."
        };
        self::$penjelasanCache[$rataRata] = $penjelasan;
        return $penjelasan;
    }

    public function ubah($data, $faktor2Id, $kodebpr, $periodeId)
    {
        return $this->builder
            ->where('faktor2id', $faktor2Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->update($data);
    }

    public function ubahkesimpulan($data, $faktor2Id, $kodebpr, $periodeId)
    {
        return $this->builder
            ->where('faktor2id', $faktor2Id)
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