<?php

namespace App\Models;

use CodeIgniter\Model;

class LaporanPdfSafModel extends Model
{
    protected $table = 'laporan_pdfsaf';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'kodebpr',
        'periode_id',
        'ringkasan_umum',
        'kota_ttd',
        'tanggal_laporan',
        'nama_pembuat',
        'jabatan_pembuat',
        'ttd_path',
        'status',
        'created_by',
        'user_role',
        'accdir',
        'accdir_by',
        'accdir_at',
        'accdir2',
        'accdir2_by',
        'accdir2_at',
    ];

    public const T_PROGRAM = 'laporan_pdfsaf_program';
    public const T_BPR = 'infobpr';
    public const C_NAMABPR = 'namabpr';
    public const T_PERIODE = 'periode_saf';
    public const T_SAFST = 'penerapansafst';
    public const PILAR = [
        1 => 'Pencegahan',
        2 => 'Deteksi',
        3 => 'Investigasi, Pelaporan, dan Sanksi',
        4 => 'Pemantauan, Evaluasi, dan Tindak Lanjut',
        5 => 'Rencana Tindak Lanjut Penguatan SAF',
    ];

    /** level => [kolom flag, kolom by, kolom at] */
    public const LEVEL_COLS = [
        'dekom'   => ['accdir', 'accdir_by', 'accdir_at'],
        'direksi' => ['accdir2', 'accdir2_by', 'accdir2_at'],
    ];

    private const TTL = 300;

    private function key(string $kodebpr, int $periodeId, string $s = 'lap'): string
    {
        return "pdfsaf_{$s}_" . md5($kodebpr) . "_{$periodeId}";
    }

    public function forget(string $kodebpr, int $periodeId): void
    {
        cache()->delete($this->key($kodebpr, $periodeId));
    }

    public function siapkan(string $kodebpr, int $periodeId, ?int $userId): bool
    {
        $ok = $this->db->table(self::T_PERIODE)
            ->where('id', $periodeId)->where('kodebpr', $kodebpr)
            ->whereIn('jenispelaporan', ['SAFTS', 'SAFTA', 'SAFTI3', 'SAFTI6'])
            ->countAllResults();
        if ($ok === 0) {
            return false;
        }
        $this->ignore(true)->insert([
            'kodebpr' => $kodebpr,
            'periode_id' => $periodeId,
            'status' => 0,
            'created_by' => $userId,
        ]);

        return true;
    }

    public function getHeader(string $kodebpr, int $periodeId): ?array
    {
        return $this->db->table($this->table . ' l')
            ->select('l.*, b.' . self::C_NAMABPR . ' AS namabpr, b.alamat, b.nomor, b.email, b.webbpr, b.logo, p.semester, p.tahun')
            ->join(self::T_BPR . ' b', 'b.kodebpr = l.kodebpr', 'left')
            ->join(self::T_PERIODE . ' p', 'p.id = l.periode_id', 'left')
            ->where('l.kodebpr', $kodebpr)->where('l.periode_id', $periodeId)
            ->get()->getRowArray();
    }

    public function getLaporan(string $kodebpr, int $periodeId): ?array
    {
        $key = $this->key($kodebpr, $periodeId);
        if (($c = cache($key)) !== null) {
            return $c;
        }
        $h = $this->getHeader($kodebpr, $periodeId);
        if (!$h) {
            return null;
        }
        $d = ['h' => $h, 'tree' => $this->getTree($kodebpr, $periodeId)];
        cache()->save($key, $d, self::TTL);

        return $d;
    }

    private function getTree(string $kodebpr, int $periodeId): array
    {
        $rows = $this->db->table(self::T_PROGRAM)
            ->select('id, pilar_id, parent_id, urutan, judul, uraian, gaya')
            ->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
            ->orderBy('urutan')->get()->getResultArray();

        $anak = [];
        foreach ($rows as $r) {
            if ($r['parent_id'] !== null) {
                $anak[$r['parent_id']][] = $r;
            }
        }
        $tree = [];
        foreach ($rows as $r) {
            if ($r['parent_id'] === null) {
                $r['anak'] = $anak[$r['id']] ?? [];
                $tree[(int) $r['pilar_id']][] = $r;
            }
        }

        return $tree;
    }

    public static function semNum($s): string
    {
        $s = strtolower(trim((string) $s));

        return $s === 'ganjil' ? '1' : ($s === 'genap' ? '2' : $s);
    }

    public static function Semnumto($s): string
    {
        $s = strtolower(trim((string) $s));
        return $s === 'ganjil' ? '1' : ($s === 'genap' ? '2' : $s);
    }

    public function kejadianFraud(string $kodebpr, int $periodeId): ?array
    {
        $key = $this->key($kodebpr, $periodeId, 'frd');
        if (($c = cache($key)) !== null) {
            return $c;
        }
        try {
            $row = $this->db->table(self::T_SAFST)
                ->select('COALESCE(SUM(is_null_record = 0), 0) AS n, COALESCE(SUM(is_null_record = 1), 0) AS z')
                ->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->get()->getRowArray();
        } catch (\Throwable $e) {
            log_message('error', 'kejadianFraud: ' . $e->getMessage());

            return null;
        }
        if (!$row) {
            return null;
        }
        $r = [
            'st' => (int) $row['n'],
            'st_null' => (int) $row['z'] > 0,
            'inc' => 0,
            'inc_null' => false,
        ];
        cache()->save($key, $r, 120);

        return $r;
    }

    public function simpanPenuh(string $kodebpr, int $periodeId, array $head, array $pilar): bool
    {
        $db = $this->db;
        $lap = $this->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();
        if (!$lap) {
            return false;
        }
        $base = ['laporan_pdfsaf_id' => $lap['id'], 'kodebpr' => $kodebpr, 'periode_id' => $periodeId];

        $db->transStart();
        $this->update($lap['id'], $head);
        $db->table(self::T_PROGRAM)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->delete();

        $flat = $anak = [];
        foreach ($pilar as $pid => $items) {
            foreach ($items as $i => $it) {
                $row = $base + [
                    'pilar_id' => $pid,
                    'parent_id' => null,
                    'urutan' => $i + 1,
                    'judul' => $it['judul'],
                    'uraian' => $it['uraian'],
                    'gaya' => $it['gaya'],
                ];
                if (!$it['anak']) {
                    $flat[] = $row;
                    continue;
                }
                $db->table(self::T_PROGRAM)->insert($row);
                $parentId = $db->insertID();
                foreach ($it['anak'] as $j => $a) {
                    $anak[] = $base + [
                        'pilar_id' => $pid,
                        'parent_id' => $parentId,
                        'urutan' => $j + 1,
                        'judul' => $a['judul'],
                        'uraian' => $a['uraian'],
                        'gaya' => 'paragraf',
                    ];
                }
            }
        }
        if ($flat) {
            $db->table(self::T_PROGRAM)->insertBatch($flat);
        }
        if ($anak) {
            $db->table(self::T_PROGRAM)->insertBatch($anak);
        }
        $db->transComplete();
        $this->forget($kodebpr, $periodeId);

        return $db->transStatus();
    }

    public function setStatus(string $kodebpr, int $periodeId, int $status): void
    {
        $this->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->update(null, ['status' => $status]);
        $this->forget($kodebpr, $periodeId);
    }

    public function simpanItem(string $kodebpr, int $periodeId, int $pilar, ?int $id, ?int $parentId, array $row): ?int
    {
        $lap = $this->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();
        if (!$lap) {
            return null;
        }
        $db = $this->db;
        $scope = static function ($b) use ($kodebpr, $periodeId) {
            return $b->where('kodebpr', $kodebpr)->where('periode_id', $periodeId);
        };

        if ($parentId !== null) {
            $induk = $scope($db->table(self::T_PROGRAM))->where('id', $parentId)->where('parent_id', null)->get()->getRowArray();
            if (!$induk) {
                return null;
            }
            $pilar = (int) $induk['pilar_id'];
        }

        $data = [
            'judul' => $row['judul'],
            'uraian' => $row['uraian'],
            'gaya' => $row['gaya'],
            'urutan' => $row['urutan'],
        ];

        if ($id) {
            $ada = $scope($db->table(self::T_PROGRAM))->where('id', $id)->countAllResults();
            if (!$ada) {
                return null;
            }
            $scope($db->table(self::T_PROGRAM))->where('id', $id)->update($data);
            $this->forget($kodebpr, $periodeId);

            return $id;
        }

        $db->table(self::T_PROGRAM)->insert($data + [
            'laporan_pdfsaf_id' => $lap['id'],
            'kodebpr' => $kodebpr,
            'periode_id' => $periodeId,
            'pilar_id' => $pilar,
            'parent_id' => $parentId,
        ]);
        $this->forget($kodebpr, $periodeId);

        return (int) $db->insertID();
    }

    public function hapusItem(string $kodebpr, int $periodeId, int $id): bool
    {
        $db = $this->db;
        $db->table(self::T_PROGRAM)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->where('parent_id', $id)->delete();
        $db->table(self::T_PROGRAM)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->where('id', $id)->delete();
        $this->forget($kodebpr, $periodeId);

        return true;
    }

    public function resetApproval(string $kodebpr, int $periodeId, ?int $userRole): bool
    {
        $row = $this->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();
        if (!$row) {
            return false;
        }
        $berubah = (int) $row['accdir'] === 1 || (int) $row['accdir2'] === 1 || (int) $row['status'] !== 0;
        $data = [
            'status' => 0,
            'accdir' => 0, 'accdir_by' => null, 'accdir_at' => null,
            'accdir2' => 0, 'accdir2_by' => null, 'accdir2_at' => null,
        ];
        if (empty($row['user_role']) && $userRole) {
            $data['user_role'] = $userRole;
            $berubah = true;
        }
        $this->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->update(null, $data);
        $this->forget($kodebpr, $periodeId);

        return $berubah;
    }

    public function setApprovalLevel(string $kodebpr, int $periodeId, string $level, bool $approve, ?int $userId): ?int
    {
        if (!isset(self::LEVEL_COLS[$level])) {
            return null;
        }
        [$flag, $by, $at] = self::LEVEL_COLS[$level];

        $this->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->update(null, [
            $flag => $approve ? 1 : 0,
            $by => $approve ? $userId : null,
            $at => $approve ? date('Y-m-d H:i:s') : null,
        ]);

        $row = $this->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();
        if (!$row) {
            return null;
        }

        $semua = true;
        foreach (M_penerapansafst::requiredLevels($row['user_role'] ?? null) as $lv) {
            if ((int) ($row[self::LEVEL_COLS[$lv][0]] ?? 0) !== 1) {
                $semua = false;
            }
        }
        $status = $semua ? 2 : 0;

        $this->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->update(null, ['status' => $status]);
        $this->forget($kodebpr, $periodeId);

        return $status;
    }
}