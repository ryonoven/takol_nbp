<?php
namespace App\Models;

use CodeIgniter\Model;

class M_transparansicommentsread extends Model
{
    protected $table = 'transparansi_reads';
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

    /**
     * Mark a specific comment as read by a specific user.
     * Inserts a record if it doesn't exist.
     */
    public function markAsRead(int $commentId, int $userId)
    {
        // Check if the record already exists to prevent duplicates
        $existingRead = $this->where('comment_id', $commentId)
            ->where('user_id', $userId)
            ->first();

        if (empty($existingRead)) {
            // Insert new read record
            return $this->insert([
                'comment_id' => $commentId,
                'user_id' => $userId,
                'read_at' => date('Y-m-d H:i:s')
            ]);
        }
        // If it already exists, just return the existing ID (or true for success)
        return $existingRead['id'];
    }

    /**
     * Get the count of comments related to a factor that a specific user has NOT read.
     *
     * @param int $Id
     * @param string $kodebpr
     * @param int $userId
     * @param int $periodeId
     * @return int
     */
    public function countUnreadCommentsForUserByFactor($subkategori, $kodebpr, $userId, $periodeId): int
    {
        // Get all comment IDs for the given factor, kodebpr, and periode
        $commentsInFactor = $this->db->table('transparansi_comments')
            ->select('id')
            ->where('subkategori', $subkategori)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('user_id !=', $userId) // We only care about comments from *other* users
            ->get()
            ->getResultArray();

        if (empty($commentsInFactor)) {
            return 0;
        }

        $commentIds = array_column($commentsInFactor, 'id');

        // Count how many of these comments are NOT in the comment_reads table for this user
        $readCommentIds = $this->db->table('transparansi_reads')
            ->select('comment_id')
            ->whereIn('comment_id', $commentIds)
            ->where('user_id', $userId)
            ->get()
            ->getResultArray();

        $readCommentIds = array_column($readCommentIds, 'comment_id');

        // Total comments by others - comments by others read by this user
        return count($commentIds) - count($readCommentIds);
    }
}   