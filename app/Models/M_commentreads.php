<?php
namespace App\Models;

use CodeIgniter\Model;

class M_commentreads extends Model
{
    protected $table = 'comment_reads';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'comment_id',
        'user_id',
        'is_read',
        'read_at',
        'created_at'
    ];

    protected bool $allowEmptyInserts = false;
    protected $useTimestamps = false;

    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;

    /**
     * Mark a specific comment as read by a specific user
     */
    public function markAsRead(int $commentId, int $userId)
    {
        $existingRead = $this->where('comment_id', $commentId)
            ->where('user_id', $userId)
            ->first();

        if (empty($existingRead)) {
            return $this->insert([
                'comment_id' => $commentId,
                'user_id' => $userId,
                'is_read' => 1,
                'read_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        return $this->update($existingRead['id'], [
            'is_read' => 1,
            'read_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * ✅ UPDATED: Count unread comments untuk user berdasarkan faktor
     * Sekarang support parameter $faktorId opsional
     */
    public function countUnreadComments($kodebpr, $userId, $periodeId, $faktorId = null)
    {
        $builder = $this->db->table('faktor_comments')
            ->select('faktor_comments.id')
            ->where('faktor_comments.kodebpr', $kodebpr)
            ->where('faktor_comments.periode_id', $periodeId)
            ->where('faktor_comments.user_id !=', $userId); // Tidak hitung komentar sendiri

        // ✅ TAMBAHAN: Filter berdasarkan faktor_id jika diberikan
        if ($faktorId !== null) {
            $builder->where('faktor_comments.faktor1id', $faktorId);
        }

        $builder->join(
            'comment_reads',
            'comment_reads.comment_id = faktor_comments.id AND comment_reads.user_id = ' . $userId,
            'left'
        )
            ->where('comment_reads.id IS NULL'); // Hanya yang belum dibaca

        return $builder->countAllResults();
    }

    /**
     * ✅ DEPRECATED: Method lama (tetap ada untuk backward compatibility)
     * Gunakan countUnreadComments() untuk consistency
     */
    public function countUnreadCommentsForUserByFactor($faktorId, $kodebpr, $userId, $periodeId): int
    {
        return $this->countUnreadComments($kodebpr, $userId, $periodeId, $faktorId);
    }

    /**
     * ✅ BARU: Mark all comments as read untuk periode dan faktor tertentu
     */
    public function markAllAsReadForPeriode($kodebpr, $userId, $periodeId, $faktorId = null)
    {
        $builder = $this->db->table('faktor_comments')
            ->select('id')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('user_id !=', $userId); // Tidak mark komentar sendiri

        // ✅ TAMBAHAN: Filter berdasarkan faktor_id jika diberikan
        if ($faktorId !== null) {
            $builder->where('faktor1id', $faktorId);
        }

        $comments = $builder->get()->getResultArray();

        $marked = 0;
        foreach ($comments as $comment) {
            // Cek apakah sudah pernah di-mark
            $exists = $this->where('comment_id', $comment['id'])
                ->where('user_id', $userId)
                ->first();

            if (!$exists) {
                $this->insert([
                    'comment_id' => $comment['id'],
                    'user_id' => $userId,
                    'is_read' => 1,
                    'read_at' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                $marked++;
            } else if ($exists['is_read'] == 0) {
                // Update jika exists tapi belum di-mark as read
                $this->update($exists['id'], [
                    'is_read' => 1,
                    'read_at' => date('Y-m-d H:i:s')
                ]);
                $marked++;
            }
        }

        return $marked;
    }

    /**
     * ✅ BARU: Check if a comment has been read by a user
     */
    public function isCommentRead(int $commentId, int $userId): bool
    {
        $result = $this->where('comment_id', $commentId)
            ->where('user_id', $userId)
            ->where('is_read', 1)
            ->first();

        return !empty($result);
    }

    /**
     * ✅ BARU: Get list komentar yang sudah dibaca
     */
    public function getReadComments($userId, $kodebpr, $periodeId, $faktorId = null)
    {
        $builder = $this->db->table($this->table . ' as r');
        $builder->select('r.*, c.faktor1id, c.komentar, c.created_at as comment_created_at')
            ->join('faktor_comments as c', 'c.id = r.comment_id')
            ->where('r.user_id', $userId)
            ->where('c.kodebpr', $kodebpr)
            ->where('c.periode_id', $periodeId);

        if ($faktorId !== null) {
            $builder->where('c.faktor1id', $faktorId);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * ✅ BARU: Get unread comments detail
     */
    public function getUnreadComments($userId, $kodebpr, $periodeId, $faktorId = null)
    {
        $builder = $this->db->table('faktor_comments as c');
        $builder->select('c.*, u.fullname')
            ->join('users as u', 'u.id = c.user_id', 'left')
            ->where('c.kodebpr', $kodebpr)
            ->where('c.periode_id', $periodeId)
            ->where('c.user_id !=', $userId);

        if ($faktorId !== null) {
            $builder->where('c.faktor1id', $faktorId);
        }

        $builder->where('c.id NOT IN (
            SELECT comment_id 
            FROM comment_reads 
            WHERE user_id = ' . $this->db->escape($userId) . '
            AND is_read = 1
        )', null, false);

        return $builder->get()->getResultArray();
    }

    /**
     * ✅ BARU: Batch mark multiple comments as read
     */
    public function batchMarkAsRead(array $commentIds, int $userId)
    {
        if (empty($commentIds)) {
            return 0;
        }

        $data = [];
        foreach ($commentIds as $commentId) {
            // Check if already exists
            $exists = $this->where('comment_id', $commentId)
                ->where('user_id', $userId)
                ->first();

            if (!$exists) {
                $data[] = [
                    'comment_id' => $commentId,
                    'user_id' => $userId,
                    'is_read' => 1,
                    'read_at' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s')
                ];
            }
        }

        if (!empty($data)) {
            return $this->insertBatch($data);
        }

        return 0;
    }
}