<?php
namespace App\Models;

use CodeIgniter\Model;

class M_profilrisikocommentsread extends Model
{
    protected $table = 'profilrisiko_reads';
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

    // Validation
    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;

    // Callbacks
    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];

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

    public function countUnreadCommentsForUserByFactor(
        $faktorId,
        $subkategori,
        $kodebpr,
        $userId,
        $periodeId
    ): int {
        $commentsInFactor = $this->db->table('profilrisiko_comments')
            ->select('id')
            ->where('faktor1id', $faktorId)
            ->where('subkategori', $subkategori)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('user_id !=', $userId)
            ->get()
            ->getResultArray();

        if (empty($commentsInFactor)) {
            return 0;
        }

        $commentIds = array_column($commentsInFactor, 'id');

        $readCommentIds = $this->db->table('profilrisiko_reads')
            ->select('comment_id')
            ->whereIn('comment_id', $commentIds)
            ->where('user_id', $userId)
            ->get()
            ->getResultArray();

        $readCommentIds = array_column($readCommentIds, 'comment_id');

        return count($commentIds) - count($readCommentIds);
    }

    public function markUserCommentsAsRead()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error']);
        }

        $faktorId = $this->request->getPost('faktor_id');
        $subkategori = $this->request->getPost('subkategori');

        if (
            !$faktorId ||
            !$subkategori ||
            !$this->userKodebpr ||
            !$this->userId ||
            !$this->periodeId
        ) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error']);
        }

        $marked = $this->commentReadsModel->markAllAsReadForFactor(
            $faktorId,
            $subkategori,
            $this->userKodebpr,
            $this->userId,
            $this->periodeId
        );

        return $this->response->setJSON([
            'status' => 'success',
            'marked_count' => $marked
        ]);
    }

    public function getAllUnreadCountsBatch(
        string $subkategori,
        string $kodebpr,
        int $userId,
        $periodeId,
        array $faktorIds
    ): array {
        // Ambil semua komentar dari orang lain untuk semua faktor sekaligus
        $comments = $this->db->table('profilrisiko_comments')
            ->select('id, faktor1id')
            ->where('subkategori', $subkategori)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('user_id !=', $userId)
            ->whereIn('faktor1id', $faktorIds)
            ->get()
            ->getResultArray();

        if (empty($comments)) {
            return [];
        }

        // Ambil semua comment_id yang sudah dibaca user ini
        $allCommentIds = array_column($comments, 'id');

        $readIds = $this->db->table('profilrisiko_reads')
            ->select('comment_id')
            ->whereIn('comment_id', $allCommentIds)
            ->where('user_id', $userId)
            ->get()
            ->getResultArray();

        $readIds = array_column($readIds, 'comment_id');

        // Hitung unread per faktor
        $counts = [];
        foreach ($comments as $c) {
            $fid = (int) $c['faktor1id'];
            if (!isset($counts[$fid])) {
                $counts[$fid] = 0;
            }
            if (!in_array($c['id'], $readIds)) {
                $counts[$fid]++;
            }
        }

        return $counts;
    }

    public function markAllAsReadForFactor(
        $faktorId,
        string $subkategori,
        string $kodebpr,
        int $userId,
        $periodeId
    ): int {
        // Ambil semua komentar dari orang lain di faktor ini
        $comments = $this->db->table('profilrisiko_comments')
            ->select('id')
            ->where('faktor1id', $faktorId)
            ->where('subkategori', $subkategori)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('user_id !=', $userId)
            ->get()
            ->getResultArray();

        if (empty($comments)) {
            return 0;
        }

        $marked = 0;
        foreach ($comments as $comment) {
            $already = $this->db->table('profilrisiko_reads')
                ->where('comment_id', $comment['id'])
                ->where('user_id', $userId)
                ->get()
                ->getRowArray();

            if (!$already) {
                $this->db->table('profilrisiko_reads')->insert([
                    'comment_id' => $comment['id'],
                    'user_id' => $userId,
                    'read_at' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $marked++;
            }
        }

        return $marked;
    }
}