<?php
namespace App\Models;

use CodeIgniter\Model;

class M_penerapansafst extends Model
{
    protected $table = 'penerapansafst';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id',
        'user_role',
        'kodebpr',
        'periode_id',
        'kejadianfraud',
        'idkejadian',
        'jenisfraud',
        'ketjenisfraud',
        'aktivitasfraud',
        'descfraud',
        'lokfraud',
        'ketlokfraud',
        'divisifraud',
        'pihakrugi',
        'wterjadiawal',
        'wterjadiakhir',
        'wfrauddiketahui',
        'rugiljkriil',
        'rugiljkpot',
        'rugiljkrec',
        'rugikonsumenriil',
        'rugikonsumenpot',
        'rugikonsumenrec',
        'rugilainriil',
        'rugilainpot',
        'rugilainrec',
        'rugipotensitotal',
        'kelemahanfraud',
        'ketkelemahanfraud',
        'tindakpenanganan',
        'kettindakpenanganan',
        'tindakperbaikan',
        'kettindakperbaikan',
        'tindaklanjutljk',
        'kettindaklanjutljk',
        'targetpelaksanaan',
        'realpelaksanaan',
        'pelakufraud',
        'namapelaku',
        'jenisidentitas',
        'noidentitas',
        'jeniskelamin',
        'alamatidentitas',
        'alamatdomisili',
        'tempatlahir',
        'tanggallahir',
        'statuspelaku',
        'jabatanterjadi',
        'ketjabatanterjadi',
        'jabatandiketahui',
        'ketjabatanketahui',
        'keteranganpelaku',
        'pengenaansanksi',
        'statuspenanganan',
        'accdir',
        'accdir_by',
        'accdir_at',
        'accdir2',
        'accdir2_by',
        'accdir2_at',
        'is_null_record',
    ];
    protected $useTimestamps = false;

    private const APPROVAL_COLS = [
        'dekom'   => ['accdir', 'accdir_by', 'accdir_at'],
        'direksi' => ['accdir2', 'accdir2_by', 'accdir2_at'],
    ];

    public const APPROVAL_RULES = [
        1  => ['dekom', 'direksi'],
        2  => ['dekom'],
        3  => ['direksi'],
        4  => ['dekom', 'direksi'],
        6  => ['dekom'],
        7  => ['dekom'],
        8  => ['dekom'],
        9  => ['dekom'],
        10 => ['direksi'],
    ];

    public const APPROVAL_DEFAULT = ['dekom', 'direksi'];

    public static function requiredLevels($userRole): array
    {
        if ($userRole === null || $userRole === '') {
            return self::APPROVAL_DEFAULT;
        }
        return self::APPROVAL_RULES[(int) $userRole] ?? self::APPROVAL_DEFAULT;
    }

    public static function rolesRequiring(string $level): array
    {
        $ids = [];
        foreach (self::APPROVAL_RULES as $role => $levels) {
            if (in_array($level, $levels, true)) {
                $ids[] = $role;
            }
        }
        return $ids;
    }

    public function getDataByKodebprAndPeriode(string $kodebpr, int $periodeId): array
    {
        return $this->db->table($this->table)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();
    }

    public function tambah(array $data)
    {
        return $this->insert($data);
    }

    public function ubah(array $data, int $id, string $kodebpr, int $periodeId): bool
    {
        return $this->db->table($this->table)
            ->where('id', $id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->update($data);
    }

    public function hapusSpesifik(int $id, string $kodebpr, int $periodeId): bool
    {
        $deleted = $this->db->table($this->table)
            ->where('id', $id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->delete();

        if ($deleted) {
            $this->resequenceIds();
        }

        return (bool) $deleted;
    }

    private function resequenceIds(): void
    {
        $rows = $this->db->table($this->table)
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        if (empty($rows)) {
            $this->db->query('ALTER TABLE `' . $this->table . '` AUTO_INCREMENT = 1');
            return;
        }

        $offset = count($rows) + 10000;
        foreach ($rows as $i => $row) {
            $this->db->table($this->table)
                ->where('id', $row['id'])
                ->update(['id' => $offset + $i + 1]);
        }

        $tempRows = $this->db->table($this->table)
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        foreach ($tempRows as $i => $row) {
            $this->db->table($this->table)
                ->where('id', $row['id'])
                ->update(['id' => $i + 1]);
        }

        $maxRow = $this->db->table($this->table)->selectMax('id')->get()->getRow();
        $next = $maxRow ? ((int) $maxRow->id + 1) : 1;
        $this->db->query('ALTER TABLE `' . $this->table . '` AUTO_INCREMENT = ' . $next);
    }

    // APPROVAL
    public function approveRow(int $id, string $kodebpr, int $periodeId, int $userId, string $level): bool
    {
        return $this->setApprovalRow($id, $kodebpr, $periodeId, $userId, $level, 1);
    }

    public function unapproveRow(int $id, string $kodebpr, int $periodeId, int $userId, string $level): bool
    {
        return $this->setApprovalRow($id, $kodebpr, $periodeId, $userId, $level, 0);
    }

    private function setApprovalRow(int $id, string $kodebpr, int $periodeId, int $userId, string $level, int $val): bool
    {
        if (!isset(self::APPROVAL_COLS[$level])) {
            return false;
        }
        [$col, $by, $at] = self::APPROVAL_COLS[$level];

        return $this->db->table($this->table)
            ->where('id', $id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->update([
                $col => $val,
                $by  => $userId,
                $at  => date('Y-m-d H:i:s'),
            ]);
    }

    public function setApprovalAll(string $kodebpr, int $periodeId, int $userId, string $level, bool $approve): int
    {
        if (!isset(self::APPROVAL_COLS[$level])) {
            return 0;
        }
        [$col, $by, $at] = self::APPROVAL_COLS[$level];

        $b = $this->db->table($this->table)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->groupStart()
            ->whereIn('user_role', self::rolesRequiring($level));

        if (in_array($level, self::APPROVAL_DEFAULT, true)) {
            $b->orWhere('user_role IS NULL');
        }
        $b->groupEnd();

        $b->update([
            $col => $approve ? 1 : 0,
            $by  => $userId,
            $at  => date('Y-m-d H:i:s'),
        ]);

        return $this->db->affectedRows();
    }

    public function getApprovalStatus(string $kodebpr, int $periodeId): array
    {
        $rows = $this->db->table($this->table)
            ->select('user_role, accdir, accdir_at, accdir2, accdir2_at')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->get()->getResultArray();

        $out = ['has_rows' => !empty($rows)];
        $allOk = !empty($rows);
        $lastAt = null;

        foreach (self::APPROVAL_COLS as $level => [$col, , $atCol]) {
            $required = false;
            $ok = true;
            $at = null;

            foreach ($rows as $r) {
                if (!in_array($level, self::requiredLevels($r['user_role']), true)) {
                    continue;
                }
                $required = true;
                if ((int) $r[$col] !== 1) {
                    $ok = false;
                }
                if (!empty($r[$atCol]) && ($at === null || $r[$atCol] > $at)) {
                    $at = $r[$atCol];
                }
            }

            $approved = $required && $ok;
            if ($required && !$ok) {
                $allOk = false;
            }
            if ($at !== null && ($lastAt === null || $at > $lastAt)) {
                $lastAt = $at;
            }

            $out[$level] = [
                'required'    => $required,
                'approved'    => $approved,
                'approved_at' => $approved ? $at : null,
            ];
        }

        $out['dekom_approved'] = $allOk;
        $out['dekom_approved_at'] = $lastAt;

        return $out;
    }

    public function getIdKejadianByPeriode(string $kodebpr, int $periodeId): array
    {
        $rows = $this->db->table($this->table)
            ->select('idkejadian')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('is_null_record', 0)
            ->get()->getResultArray();

        return array_filter(array_column($rows, 'idkejadian'));
    }

    public function insertBatchSync(array $rows): bool
    {
        if (empty($rows))
            return true;

        $result = $this->db->table($this->table)->insertBatch($rows);

        return $result !== false;
    }

    public function hasNullRecord(string $kodebpr, int $periodeId): ?array
    {
        return $this->db->table($this->table)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('is_null_record', 1)
            ->get()->getRowArray();
    }

    public function hasRealData(string $kodebpr, int $periodeId): bool
    {
        return $this->db->table($this->table)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('is_null_record', 0)
            ->countAllResults() > 0;
    }

    public function insertNullRecord(array $data): bool
    {
        return (bool) $this->insert($data);
    }

    public function removeNullRecord(string $kodebpr, int $periodeId): bool
    {
        return (bool) $this->db->table($this->table)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('is_null_record', 1)
            ->delete();
    }
}