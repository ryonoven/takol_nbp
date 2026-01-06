<?php
namespace App\Models;

use CodeIgniter\Model;

class M_reputasiinheren extends Model
{
    protected $table = 'reputasiinheren';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id',
        'kodebpr',
        'periode_id',
        'faktor1id',
        'rasiokredit',
        'penilaiankredit',
        'penjelasanpenilaian',
        'keterangan',
        'fullname',
        'created_at',
        'is_approved',
        'approved_by',
        'approved_at',
        'accdir2',
        'accdir2_by',
        'accdekom',
        'accdekom_by',
        'accdekom_at',
        'read_at',
        'comment_id'
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
        ])->findAll();
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
        return $this->db->table('profilrisikocomments')
            ->select('profilrisikocomments.*, users.fullname')
            ->join('users', 'users.id = profilrisikocomments.user_id', 'left')
            ->where('profilrisikocomments.subkategori', $subkategori)
            ->where('profilrisikocomments.faktor1id', $faktorId)
            ->where('profilrisikocomments.kodebpr', $kodebpr)
            ->where('profilrisikocomments.periode_id', $periodeId)
            ->orderBy('profilrisikocomments.created_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getKomentarByFaktorIdAndKodebpr($faktorId, $kodebpr)
    {
        return $this->db->table($this->table)
            ->select('reputasiinheren.*, users.fullname')
            ->join('users', 'users.id = reputasiinheren.user_id', 'left')
            ->where([
                'reputasiinheren.faktor1id' => $faktorId,
                'reputasiinheren.kodebpr' => $kodebpr
            ])
            ->orderBy('reputasiinheren.created_at', 'ASC')
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
        $this->setIncrement($lastData[0]['id']);
    }

    public function hitungRataRata($faktorId, $kodebpr, $periodeId)
    {
        if ($faktorId == 148) {
            $result = $this->select('AVG(penilaiankredit) as rata_rata, COUNT(*) as total')
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->whereIn('faktor1id', [138, 141, 142, 144, 147])
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
            'faktor1id' => 148,
            'kodebpr' => $kodebpr,
            'periode_id' => $periodeId
        ])->first();

        if ($keterangan === null) {
            if ($existing && !empty($existing['keterangan'])) {
                $keterangan = $existing['keterangan'];
            } else {
                $keterangan = 'Hasil Penilaian Tingkat Risiko Reputasi Inheren';
            }
        }

        $data = [
            'faktor1id' => 148,
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
        return $this->db->query('ALTER TABLE reputasiinheren AUTO_INCREMENT = 1');
    }

    public function setIncrement($value)
    {
        $value = (int) $value;
        return $this->db->query('ALTER TABLE reputasiinheren AUTO_INCREMENT = ?', [$value]);
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
            ->where('faktor1id <=', 147)
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
}