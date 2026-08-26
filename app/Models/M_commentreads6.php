<?php
namespace App\Models;

use CodeIgniter\Model;

class M_commentreads6 extends Model
{
    protected $table = 'comment_reads6';
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

    public function countUnreadComments($kodebpr, $userId, $periodeId, $faktorId = null)
    {
        $builder = $this->db->table('faktor6_comments')
            ->select('faktor6_comments.id')
            ->where('faktor6_comments.kodebpr', $kodebpr)
            ->where('faktor6_comments.periode_id', $periodeId)
            ->where('faktor6_comments.user_id !=', $userId);

        if ($faktorId !== null) {
            $builder->where('faktor6_comments.faktor6id', $faktorId);
        }

        $builder->join(
            'comment_reads6',
            'comment_reads6.comment_id = faktor6_comments.id AND comment_reads6.user_id = ' . $userId,
            'left'
        )
            ->where('comment_reads6.id IS NULL');

        return $builder->countAllResults();
    }

    public function countUnreadCommentsForUserByFactor($faktorId, $kodebpr, $userId, $periodeId): int
    {
        return $this->countUnreadComments($kodebpr, $userId, $periodeId, $faktorId);
    }

    public function markAllAsReadForPeriode($kodebpr, $userId, $periodeId, $faktorId = null)
    {
        $builder = $this->db->table('faktor6_comments')
            ->select('id')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('user_id !=', $userId);

        if ($faktorId !== null) {
            $builder->where('faktor6id', $faktorId);
        }

        $comments = $builder->get()->getResultArray();

        $marked = 0;
        foreach ($comments as $comment) {
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
                $this->update($exists['id'], [
                    'is_read' => 1,
                    'read_at' => date('Y-m-d H:i:s')
                ]);
                $marked++;
            }
        }

        return $marked;
    }

    public function isCommentRead(int $commentId, int $userId): bool
    {
        $result = $this->where('comment_id', $commentId)
            ->where('user_id', $userId)
            ->where('is_read', 1)
            ->first();

        return !empty($result);
    }

    public function getReadComments($userId, $kodebpr, $periodeId, $faktorId = null)
    {
        $builder = $this->db->table($this->table . ' as r');
        $builder->select('r.*, c.faktor6id, c.komentar, c.created_at as comment_created_at')
            ->join('faktor6_comments as c', 'c.id = r.comment_id')
            ->where('r.user_id', $userId)
            ->where('c.kodebpr', $kodebpr)
            ->where('c.periode_id', $periodeId);

        if ($faktorId !== null) {
            $builder->where('c.faktor6id', $faktorId);
        }

        return $builder->get()->getResultArray();
    }

    public function getUnreadComments($userId, $kodebpr, $periodeId, $faktorId = null)
    {
        $builder = $this->db->table('faktor6_comments as c');
        $builder->select('c.*, u.fullname')
            ->join('users as u', 'u.id = c.user_id', 'left')
            ->where('c.kodebpr', $kodebpr)
            ->where('c.periode_id', $periodeId)
            ->where('c.user_id !=', $userId);

        if ($faktorId !== null) {
            $builder->where('c.faktor6id', $faktorId);
        }

        $builder->where('c.id NOT IN (
            SELECT comment_id 
            FROM comment_reads6 
            WHERE user_id = ' . $this->db->escape($userId) . '
            AND is_read = 1
        )', null, false);

        return $builder->get()->getResultArray();
    }

    public function batchMarkAsRead(array $commentIds, int $userId)
    {
        if (empty($commentIds)) {
            return 0;
        }

        $data = [];
        foreach ($commentIds as $commentId) {
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