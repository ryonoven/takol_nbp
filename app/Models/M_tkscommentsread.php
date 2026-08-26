<?php
namespace App\Models;

use CodeIgniter\Model;

class M_tkscommentsread extends Model
{
    protected $table = 'tks_comments_read';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = ['comment_id', 'user_id', 'read_at'];

    protected bool $allowEmptyInserts = false;

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function markAsRead(int $commentId, int $userId)
    {
        $existingRead = $this->where('comment_id', $commentId)
            ->where('user_id', $userId)
            ->first();

        if (empty($existingRead)) {
            return $this->insert([
                'comment_id' => $commentId,
                'user_id' => $userId,
                'read_at' => date('Y-m-d H:i:s')
            ]);
        }

        return $existingRead['id'];
    }

    public function countUnreadComments($kodebpr, $userId, $periodeId, $kategori = null)
    {
        $builder = $this->db->table('tks_comments')
            ->select('tks_comments.id')
            ->where('tks_comments.kodebpr', $kodebpr)
            ->where('tks_comments.periode_id', $periodeId)
            ->where('tks_comments.user_id !=', $userId);

        if ($kategori !== null) {
            $builder->where('tks_comments.kategori', $kategori);
        }

        $builder->join(
            'tks_comments_read',
            'tks_comments_read.comment_id = tks_comments.id AND tks_comments_read.user_id = ' . $userId,
            'left'
        )
            ->where('tks_comments_read.id IS NULL');

        return $builder->countAllResults();
    }

    public function markAllAsReadForPeriode($kodebpr, $userId, $periodeId, $kategori = null)
    {
        $builder = $this->db->table('tks_comments')
            ->select('id')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('user_id !=', $userId);

        if ($kategori !== null) {
            $builder->where('kategori', $kategori);
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
            ->first();

        return !empty($result);
    }
}