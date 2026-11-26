<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Manajemen user untuk menu admin (daftar, detail, peran, password, blokir).
 * Peran memakai tabel Myth Auth (auth_groups / auth_groups_users); blokir memakai users.status = 'banned'
 * (dicek Myth Auth saat login).
 */
class M_adminuser extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    /** Peran yang wajib tepat satu per BPR => label. */
    public const UNIQUE_ROLES = ['dekom' => 'Komisaris Utama', 'direksi' => 'Direktur Utama'];

    /** Semua user + nama BPR + peran (tanpa duplikat) + login terakhir. */
    public function listUsers(): array
    {
        $rows = $this->db->query(
            "SELECT u.id, u.username, u.email, u.fullname, u.kodebpr, u.status, u.created_at,
                    b.namabpr,
                    GROUP_CONCAT(DISTINCT g.name ORDER BY g.id SEPARATOR ',') AS roles,
                    ll.last_login
               FROM users u
               LEFT JOIN infobpr b ON b.kodebpr = u.kodebpr
               LEFT JOIN auth_groups_users gu ON gu.user_id = u.id
               LEFT JOIN auth_groups g ON g.id = gu.group_id
               LEFT JOIN (SELECT user_id, MAX(date) AS last_login FROM auth_logins WHERE success = 1 GROUP BY user_id) ll
                      ON ll.user_id = u.id
              WHERE u.deleted_at IS NULL
              GROUP BY u.id
              ORDER BY u.kodebpr, u.username"
        )->getResultArray();
        foreach ($rows as &$r) {
            $r['roles'] = $r['roles'] ? explode(',', $r['roles']) : [];
            $r['banned'] = $r['status'] === 'banned';
        }
        return $rows;
    }

    public function groups(): array
    {
        return $this->db->table('auth_groups')->select('id, name, description')->orderBy('id')->get()->getResultArray();
    }

    public function bprs(): array
    {
        return $this->db->table('infobpr')->select('kodebpr, namabpr')->orderBy('kodebpr')->get()->getResultArray();
    }

    public function findUser(int $id): ?array
    {
        return $this->db->table('users')
            ->select('id, username, email, fullname, kodebpr, status, status_message, created_at, updated_at')
            ->where('id', $id)->where('deleted_at', null)->get()->getRowArray();
    }

    public function userGroupIds(int $id): array
    {
        return array_map('intval', array_column(
            $this->db->table('auth_groups_users')->select('group_id')->distinct()->where('user_id', $id)->get()->getResultArray(),
            'group_id'
        ));
    }

    public function loginHistory(int $id, int $limit = 10): array
    {
        return $this->db->table('auth_logins')->select('ip_address, date, success')
            ->where('user_id', $id)->orderBy('id', 'DESC')->get($limit)->getResultArray();
    }

    public function isTaken(string $field, string $value, int $exceptId): bool
    {
        return $this->db->table('users')->where($field, $value)->where('id !=', $exceptId)->countAllResults() > 0;
    }

    public function updateProfile(int $id, array $data): bool
    {
        return (bool) $this->db->table('users')->where('id', $id)->update($data + ['updated_at' => date('Y-m-d H:i:s')]);
    }

    /** Ganti seluruh peran user (sekaligus menghapus baris duplikat lama). */
    public function setGroups(int $id, array $groupIds): void
    {
        $t = $this->db->table('auth_groups_users');
        $t->where('user_id', $id)->delete();
        foreach (array_unique($groupIds) as $g) {
            $this->db->table('auth_groups_users')->insert(['group_id' => (int) $g, 'user_id' => $id]);
        }
    }

    public function setPasswordHash(int $id, string $hash): bool
    {
        return (bool) $this->db->table('users')->where('id', $id)
            ->update(['password_hash' => $hash, 'reset_hash' => null, 'reset_expires' => null, 'force_pass_reset' => 0, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    public function setBanned(int $id, bool $banned, string $reason = ''): bool
    {
        return (bool) $this->db->table('users')->where('id', $id)
            ->update(['status' => $banned ? 'banned' : null, 'status_message' => $banned ? $reason : null, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Peringatan konfigurasi peran per BPR (hanya user aktif):
     * BPR tanpa / lebih dari satu Komisaris Utama atau Direktur Utama, dan BPR tanpa PE.
     */
    public function roleWarnings(array $users): array
    {
        $per = [];
        foreach ($users as $u) {
            if ($u['banned'] || $u['kodebpr'] === '') {
                continue;
            }
            $k = $u['kodebpr'];
            $per[$k] ??= ['nama' => $u['namabpr'], 'dekom' => [], 'direksi' => [], 'pe' => 0];
            foreach (array_keys(self::UNIQUE_ROLES) as $r) {
                if (in_array($r, $u['roles'], true)) {
                    $per[$k][$r][] = $u['username'];
                }
            }
            $per[$k]['pe'] += in_array('pe', $u['roles'], true) ? 1 : 0;
        }
        $out = [];
        foreach ($per as $kode => $p) {
            $msgs = [];
            foreach (self::UNIQUE_ROLES as $r => $label) {
                $n = count($p[$r]);
                if ($n === 0) {
                    $msgs[] = "belum ada {$label}";
                } elseif ($n > 1) {
                    $msgs[] = "{$n} {$label} (" . implode(', ', $p[$r]) . ')';
                }
            }
            if ($p['pe'] === 0) {
                $msgs[] = 'belum ada Pejabat Eksekutif (PE)';
            }
            if ($msgs) {
                $out[] = ['kodebpr' => $kode, 'nama' => $p['nama'], 'msgs' => $msgs];
            }
        }
        return $out;
    }

    /** Jumlah pasangan user–peran yang tercatat ganda di auth_groups_users. */
    public function duplicateRoleRows(): int
    {
        return (int) ($this->db->query(
            'SELECT COALESCE(SUM(n - 1), 0) AS d FROM (SELECT COUNT(*) n FROM auth_groups_users GROUP BY user_id, group_id HAVING n > 1) x'
        )->getRowArray()['d'] ?? 0);
    }

    /** Hapus baris peran ganda (menyisakan satu per user–peran). */
    public function cleanDuplicateRoles(): int
    {
        $before = $this->duplicateRoleRows();
        if ($before === 0) {
            return 0;
        }
        // Hanya pasangan yang ganda yang dihapus lalu disisipkan sekali (DELETE bersifat transaksional; TRUNCATE tidak).
        $pairs = $this->db->query('SELECT user_id, group_id FROM auth_groups_users GROUP BY user_id, group_id HAVING COUNT(*) > 1')->getResultArray();
        $this->db->transStart();
        foreach ($pairs as $p) {
            $this->db->table('auth_groups_users')->where('user_id', $p['user_id'])->where('group_id', $p['group_id'])->delete();
            $this->db->table('auth_groups_users')->insert(['user_id' => $p['user_id'], 'group_id' => $p['group_id']]);
        }
        $this->db->transComplete();
        return $this->db->transStatus() ? $before : 0;
    }
}
