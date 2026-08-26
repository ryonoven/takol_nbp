<?php
namespace App\Models;

use CodeIgniter\Model;

class M_semesterancommentsread extends Model
{
    protected $table = 'semesterancommentsread';
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

    public function countUnreadComments($kodebpr, $userId, $periodeId, $kategori = null, $subkategori = null)
    {
        $builder = $this->db->table('semesteran_comments')
            ->select('semesteran_comments.id')
            ->where('semesteran_comments.kodebpr', $kodebpr)
            ->where('semesteran_comments.periode_id', $periodeId)
            ->where('semesteran_comments.user_id !=', $userId); // Tidak hitung komentar sendiri

        if ($kategori !== null) {
            $builder->where('semesteran_comments.kategori', $kategori);
        }

        if ($subkategori !== null) {
            $builder->where('semesteran_comments.subkategori', $subkategori);
        }

        $builder->join(
            'semesterancommentsread',
            'semesterancommentsread.comment_id = semesteran_comments.id AND semesterancommentsread.user_id = ' . $userId,
            'left'
        )
            ->where('semesterancommentsread.id IS NULL');

        return $builder->countAllResults();
    }

    public function markAllAsReadForPeriode($kodebpr, $userId, $periodeId, $kategori = null, $subkategori = null)
    {
        $builder = $this->db->table('semesteran_comments')
            ->select('id')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('user_id !=', $userId);

        if ($kategori !== null) {
            $builder->where('kategori', $kategori);
        }

        if ($subkategori !== null) {
            $builder->where('subkategori', $subkategori);
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