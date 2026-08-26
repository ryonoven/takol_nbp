<?php
namespace App\Models;

use CodeIgniter\Model;

class M_transparansicommentsread extends Model
{
    protected $table          = 'transparansi_reads';
    protected $primaryKey     = 'id';
    protected $useAutoIncrement = true;
    protected $returnType     = 'array';
    protected $allowedFields  = ['comment_id', 'user_id', 'read_at'];
    protected $useTimestamps  = false;

    public function countUnreadComments(
        string $kodebpr,
        int    $userId,
        int    $periodeId,
        string $kategori,
        string $subkategori
    ): int {
        $readIds = $this->db->table('transparansi_reads')
            ->select('comment_id')
            ->where('user_id', $userId)
            ->get()
            ->getResultArray();

        $readIds = array_column($readIds, 'comment_id');

        $builder = $this->db->table('transparansi_comments')
            ->where('kodebpr',     $kodebpr)
            ->where('periode_id',  $periodeId)
            ->where('subkategori', $subkategori)
            ->where('user_id !=',  $userId);

        if (!empty($readIds)) {
            $builder->whereNotIn('id', $readIds);
        }

        return (int) $builder->countAllResults();
    }

    public function markAllAsReadForPeriode(
        string $kodebpr,
        int    $userId,
        int    $periodeId,
        string $kategori,
        string $subkategori
    ): int {
        $readIds = $this->db->table('transparansi_reads')
            ->select('comment_id')
            ->where('user_id', $userId)
            ->get()
            ->getResultArray();

        $readIds = array_column($readIds, 'comment_id');

        $builder = $this->db->table('transparansi_comments')
            ->select('id')
            ->where('kodebpr',     $kodebpr)
            ->where('periode_id',  $periodeId)
            ->where('subkategori', $subkategori)
            ->where('user_id !=',  $userId);

        if (!empty($readIds)) {
            $builder->whereNotIn('id', $readIds);
        }

        $rows = $builder->get()->getResultArray();

        if (empty($rows)) {
            return 0;
        }

        $now     = date('Y-m-d H:i:s');
        $inserts = array_map(fn($r) => [
            'comment_id' => (int) $r['id'],
            'user_id'    => $userId,
            'read_at'    => $now,
        ], $rows);

        $this->db->table('transparansi_reads')->insertBatch($inserts);
        return count($inserts);
    }

    public function markAsRead(int $commentId, int $userId): int|bool
    {
        $existing = $this->where('comment_id', $commentId)
            ->where('user_id', $userId)
            ->first();

        if (!empty($existing)) {
            return (int) $existing['id'];
        }

        return $this->insert([
            'comment_id' => $commentId,
            'user_id'    => $userId,
            'read_at'    => date('Y-m-d H:i:s'),
        ]);
    }

    public function countUnreadCommentsForUserByFactor(
        string $subkategori,
        string $kodebpr,
        int    $userId,
        int    $periodeId
    ): int {
        return $this->countUnreadComments($kodebpr, $userId, $periodeId, '', $subkategori);
    }

    public function markAllAsReadForFactor(
        string $subkategori,
        string $kodebpr,
        int    $userId,
        int    $periodeId
    ): int {
        return $this->markAllAsReadForPeriode($kodebpr, $userId, $periodeId, '', $subkategori);
    }
}