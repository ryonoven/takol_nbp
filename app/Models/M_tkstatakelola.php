<?php
namespace App\Models;

use CodeIgniter\Model;

class M_tkstatakelola extends Model
{
    protected $table = 'tkstatakelola';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id',
        'kodebpr',
        'periode_id',
        'ratarata',
        'nilaiperingkat',
        'peringkat',
        'nilaifaktor',
        'nilaiperingkatbefore',
        'peringkatbefore',
        'nilaifaktorbefore',
        'deskripsi',
        'analisis',
        'fullname',
        'created_at',
        'updated_at',
        'is_approved',
        'approved_by',
        'approved_at',
        'accdir2',
        'accdir2_by',
        'accdekom',
        'accdekom_by',
        'accdekom_at',
        'read_at',
        'comment_id',
        'synced_from_tk',
        'sync_date'
    ];
    protected $useTimestamps = false;
    protected $returnType = 'array';
    private $userCache = [];

    public function __construct()
    {
        parent::__construct();
        $this->builder = $this->db->table($this->table);
    }

    public function getAllData()
    {
        return $this->findAll();
    }

    public function getDataByKodebprAndPeriode($kodebpr, $periodeId)
    {
        return $this->where([
            'kodebpr' => $kodebpr,
            'periode_id' => $periodeId
        ])->first();
    }

    public function getDataByKodebpr($kodebpr)
    {
        return $this->where('kodebpr', $kodebpr)->findAll();
    }

    public function insertNilai($data)
    {
        return $this->insert($data);
    }

    public function tambahNilai($data, $faktorId, $kodebpr)
    {
        return $this->insert($data);
    }

    public function getKomentarByFaktorId($subkategori, $faktorId, $kodebpr, $periodeId)
    {
        return $this->db->table('tkstatakelolacomments')
            ->select('tkstatakelolacomments.*, users.fullname')
            ->join('users', 'users.id = tkstatakelolacomments.user_id', 'left')
            ->where('tkstatakelolacomments.subkategori', $subkategori)
            ->where('tkstatakelolacomments.faktor1id', $faktorId)
            ->where('tkstatakelolacomments.kodebpr', $kodebpr)
            ->where('tkstatakelolacomments.periode_id', $periodeId)
            ->orderBy('tkstatakelolacomments.created_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getKomentarByFaktorIdAndKodebpr($faktorId, $kodebpr)
    {
        return $this->db->table($this->table)
            ->select('tkstatakelola.*, users.fullname')
            ->join('users', 'users.id = tkstatakelola.user_id', 'left')
            ->where([
                'tkstatakelola.faktor1id' => $faktorId,
                'tkstatakelola.kodebpr' => $kodebpr
            ])
            ->orderBy('tkstatakelola.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function ubahBerdasarkanFaktorId($data, $faktorId, $kodebpr, $periodeId)
    {
        return $this->where([
            'faktor1id' => $faktorId,
            'kodebpr' => $kodebpr,
            'periode_id' => $periodeId
        ])->set($data)->update();
    }

    public function ubah($data, $faktorId, $kodebpr, $periodeId)
    {
        return $this->ubahBerdasarkanFaktorId($data, $faktorId, $kodebpr, $periodeId);
    }

    public function ubahkesimpulan($data, $faktorId, $kodebpr, $periodeId)
    {
        return $this->ubah($data, $faktorId, $kodebpr, $periodeId);
    }

    public function getByFaktor($faktor1id, $periodeId, $kodebpr)
    {
        return $this->where('faktor1id', $faktor1id)
            ->where('periode_id', $periodeId)
            ->where('kodebpr', $kodebpr)
            ->first();
    }

    public function hapus($id)
    {
        $lastData = $this->builder->orderBy('id', 'DESC')->limit(1)->get()->getResultArray();
        $this->builder->delete(['id' => $id]);
        if (!empty($lastData)) {
            $this->setIncrement($lastData[0]['id']);
        }
    }

    public function hitungRataRatas($faktorId, $kodebpr, $periodeId)
    {
        $result = $this->select('AVG(penilaiankredit) as rata_rata, COUNT(*) as total')
            ->where('faktor1id >=', 1)
            ->where('faktor1id <=', 12)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if (!$result || $result['total'] == 0) {
            return 0;
        }

        $rataRata = (float) $result['rata_rata'];

        return ($rataRata - floor($rataRata) >= 0.5) ? ceil($rataRata) : floor($rataRata);
    }

    public function hitungRataRata($faktorId, $kodebpr, $periodeId)
    {
        if ($faktorId == 13) {
            $result = $this->select('AVG(penilaiankredit) as rata_rata, COUNT(*) as total')
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->whereIn('faktor1id', [1, 6, 10, 11, 12])
                ->where('penilaiankredit IS NOT NULL')
                ->first();

            if (!$result || $result['total'] == 0) {
                return 0;
            }

            $rataRata = (float) $result['rata_rata'];

            return ($rataRata - floor($rataRata) >= 0.5) ? ceil($rataRata) : floor($rataRata);
        }

        return 0;
    }

    public function setNullKolom($faktorId)
    {
        return $this->builder->update(
            ['penilaiankredit' => null, 'keterangan' => null],
            ['faktor1id' => $faktorId]
        );
    }

    private function getCachedUser($userId)
    {
        if (!isset($this->userCache[$userId])) {
            $userModel = new \App\Models\M_user();
            $this->userCache[$userId] = $userModel->find($userId);
        }
        return $this->userCache[$userId];
    }

    public function insertOrUpdateRataRata($rataRata, $faktorId, $kodebpr, $periodeId, $keterangan = null)
    {
        $userId = service('authentication')->id();
        $user = $this->getCachedUser($userId);

        if (!$user) {
            log_message('error', 'User not found for insertOrUpdateRataRata: ' . $userId);
            return false;
        }

        $fullname = $user['fullname'] ?? 'Unknown';
        $kodebpr = $kodebpr ?? $user['kodebpr'] ?? 'default_kodebpr';

        $rataRata = $this->hitungRataRata($faktorId, $kodebpr, $periodeId);

        $existing = $this->where([
            'faktor1id' => 13,
            'kodebpr' => $kodebpr,
            'periode_id' => $periodeId
        ])->first();

        if ($keterangan === null) {
            if ($existing && !empty($existing['keterangan'])) {
                $keterangan = $existing['keterangan'];
            } else {
                $keterangan = 'Hasil Penilaian Tingkat Risiko Kredit Inheren';
            }
        }

        $data = [
            'faktor1id' => 13,
            'penilaiankredit' => $rataRata,
            'fullname' => $fullname,
            'user_id' => $userId,
            'kodebpr' => $kodebpr,
            'periode_id' => $periodeId,
            'updated_at' => date('Y-m-d H:i:s'),
            'keterangan' => $keterangan,
        ];

        if ($existing) {
            return $this->update($existing['id'], $data);
        }

        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->insert($data);
    }

    public function batchUpdateApproval($kodebpr, $periodeId, $updateData)
    {
        return $this->where([
            'kodebpr' => $kodebpr,
            'periode_id' => $periodeId
        ])->set($updateData)->update();
    }

    public function getByMultipleFaktors($faktorIds, $kodebpr, $periodeId)
    {
        return $this->whereIn('faktor1id', $faktorIds)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->findAll();
    }

    public function checkAllApproved($faktorIds, $kodebpr, $periodeId)
    {
        $result = $this->select('COUNT(*) as total, SUM(is_approved) as approved_count')
            ->whereIn('faktor1id', $faktorIds)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if (!$result || $result['total'] == 0) {
            return false;
        }

        return $result['total'] == $result['approved_count'];
    }

    public function resetAutoIncrement()
    {
        return $this->db->query('ALTER TABLE tkstatakelola AUTO_INCREMENT = 1');
    }

    public function setIncrement($value)
    {
        $value = (int) $value;
        return $this->db->query('ALTER TABLE tkstatakelola AUTO_INCREMENT = ?', [$value]);
    }

    public function getStatistics($kodebpr, $periodeId)
    {
        $result = $this->select('
                COUNT(*) as total_records,
                SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN accdir2 = 1 THEN 1 ELSE 0 END) as accdir2_count,
                AVG(penilaiankredit) as avg_penilaian
            ')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id <=', 12)
            ->first();

        return $result ?? [
            'total_records' => 0,
            'approved_count' => 0,
            'accdir2_count' => 0,
            'avg_penilaian' => 0
        ];
    }

    public function bulkInsert($dataArray)
    {
        if (empty($dataArray)) {
            return false;
        }

        return $this->insertBatch($dataArray);
    }

    public function clearCache()
    {
        $this->userCache = [];
    }

    public function getByKodebprAndPeriode($kodebpr, $periodeId)
    {
        return $this->where([
            'kodebpr' => $kodebpr,
            'periode_id' => $periodeId
        ])->first();
    }

    public function isSynced($kodebpr, $periodeId)
    {
        $data = $this->getByKodebprAndPeriode($kodebpr, $periodeId);
        return $data && isset($data['synced_from_tk']) && $data['synced_from_tk'] == 1;
    }

    public function updateTatakelolaData($kodebpr, $periodeId, $data)
    {
        $existing = $this->getByKodebprAndPeriode($kodebpr, $periodeId);

        if ($existing) {
            return $this->update($existing['id'], $data);
        } else {
            $data['kodebpr'] = $kodebpr;
            $data['periode_id'] = $periodeId;
            $data['created_at'] = date('Y-m-d H:i:s');
            return $this->insert($data);
        }
    }

    public function syncWithTatakelola()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;

        try {
            // Get showfaktor data (tata kelola)
            $showfaktor = $this->showfaktorModel->getByKodebprAndPeriode($this->userKodebpr, $this->periodeId);

            if (!$showfaktor) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Data Tata Kelola tidak ditemukan untuk periode ini.'
                ]);
            }

            // Get periode detail
            $periodeDetail = $this->periodeModel->find($this->periodeId);

            // Determine peringkat labels
            $peringkatLabels = [
                '1' => 'Sangat Baik',
                '2' => 'Baik',
                '3' => 'Cukup',
                '4' => 'Kurang Baik',
                '5' => 'Buruk'
            ];

            $nilaikomposit = $showfaktor['nilaikomposit'] ?? 0;
            $peringkatLabel = $peringkatLabels[$nilaikomposit] ?? 'Tidak Valid';

            // Prepare data for sync - LENGKAP dengan semua field
            $syncData = [
                'nilaiperingkat' => $nilaikomposit,
                'peringkat' => $peringkatLabel,  // TAMBAHKAN INI
                'deskripsi' => $peringkatLabel,  // TAMBAHKAN INI
                'analisis' => $showfaktor['kesimpulan'] ?? '',
                'updated_at' => date('Y-m-d H:i:s'),
                'synced_from_tk' => 1,
                'sync_date' => date('Y-m-d H:i:s'),
                'user_id' => $this->userId
            ];

            // Update or insert tkstatakelola
            $result = $this->tkstatakelolaModel->updateTatakelolaData($this->userKodebpr, $this->periodeId, $syncData);

            if ($result) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Data berhasil disinkronkan dengan Laporan Tata Kelola.',
                    'sync_date' => date('d/m/Y H:i:s'),
                    'nilaiperingkat' => $syncData['nilaiperingkat'],
                    'peringkat' => $syncData['peringkat'],
                    'deskripsi' => $syncData['deskripsi']
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Gagal menyimpan data sinkronisasi.'
                ]);
            }

        } catch (\Exception $e) {
            log_message('error', 'Sync error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Terjadi kesalahan saat sinkronisasi: ' . $e->getMessage()
            ]);
        }
    }

    public function simpanPeringkatSebelumnya($kodebpr, $periodeId, $nilaiperingkatbefore, $userId)
    {
        // Generate peringkat label
        $peringkatLabels = [
            '1' => 'Sangat Baik',
            '2' => 'Baik',
            '3' => 'Cukup',
            '4' => 'Kurang Baik',
            '5' => 'Buruk'
        ];

        $peringkatbefore = $peringkatLabels[$nilaiperingkatbefore] ?? 'Tidak Valid';

        // ✅ HITUNG nilaifaktorbefore (nilaiperingkatbefore x 30%)
        $nilaifaktorbefore = $nilaiperingkatbefore * 0.30;

        $data = [
            'nilaiperingkatbefore' => $nilaiperingkatbefore,
            'peringkatbefore' => $peringkatbefore,
            'nilaifaktorbefore' => $nilaifaktorbefore,  // ✅ TAMBAHKAN INI
            'updated_at' => date('Y-m-d H:i:s'),
            'user_id' => $userId
        ];

        // Check if record exists
        $existing = $this->getByKodebprAndPeriode($kodebpr, $periodeId);

        if ($existing) {
            return $this->update($existing['id'], $data);
        } else {
            $data['kodebpr'] = $kodebpr;
            $data['periode_id'] = $periodeId;
            $data['created_at'] = date('Y-m-d H:i:s');
            return $this->insert($data);
        }
    }

    public function simpanPeringkatDenganNilaiFaktor($kodebpr, $periodeId, $nilaiperingkat, $deskripsi, $userId)
    {
        // Generate peringkat label jika deskripsi kosong
        $peringkatLabels = [
            '1' => 'Sangat Baik',
            '2' => 'Baik',
            '3' => 'Cukup',
            '4' => 'Kurang Baik',
            '5' => 'Buruk'
        ];

        if (empty($deskripsi)) {
            $deskripsi = $peringkatLabels[$nilaiperingkat] ?? 'Tidak Valid';
        }

        $peringkat = $peringkatLabels[$nilaiperingkat] ?? 'Tidak Valid';

        // ✅ HITUNG nilaifaktor (nilaiperingkat x 30%)
        $nilaifaktor = $nilaiperingkat * 0.30;

        $data = [
            'nilaiperingkat' => $nilaiperingkat,
            'peringkat' => $peringkat,
            'deskripsi' => $deskripsi,
            'nilaifaktor' => $nilaifaktor,  // ✅ TAMBAHKAN INI
            'updated_at' => date('Y-m-d H:i:s'),
            'user_id' => $userId,
            'synced_from_tk' => 0
        ];

        return $this->updateTatakelolaData($kodebpr, $periodeId, $data);
    }

}