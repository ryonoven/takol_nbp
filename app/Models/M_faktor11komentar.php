<?php
namespace App\Models;

use CodeIgniter\Model;

class M_faktor11komentar extends Model
{
    protected $table = 'faktor11_comments';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'faktor11id',
        'user_id',
        'fullname',
        'periode_id',
        'komentar',
        'is_read',
        'kodebpr',
        'created_at',
        'is_read'
    ];

    protected bool $allowEmptyInserts = false;
    protected $useTimestamps = false;

    public function __construct()
    {
        parent::__construct();
        $this->builder = $this->db->table($this->table);
    }

    public function insertKomentar($data)
    {
        $data = array_merge([
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ], $data);

        return $this->insert($data);
    }

    public function getKomentarByPeriode($kodebpr, $periodeId, $faktor11Id = null)
    {
        $builder = $this->db->table($this->table)
            ->select('faktor11_comments.*, users.fullname, users.email')
            ->join('users', 'users.id = faktor11_comments.user_id', 'left')
            ->where('faktor11_comments.kodebpr', $kodebpr)
            ->where('faktor11_comments.periode_id', $periodeId);

        if ($faktor11Id !== null) {
            $builder->where('faktor11_comments.faktor11id', $faktor11Id);
        }

        return $builder
            ->orderBy('faktor11_comments.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getKomentarByFaktorId($faktor11Id, $kodebpr, $periodeId = null)
    {
        return $this->getKomentarByPeriode($kodebpr, $periodeId, $faktor11Id);
    }

    public function getAllData()
    {
        return $this->findAll();
    }

    public function getDataByKodebprAndPeriode($kodebpr, $periodeId)
    {
        return $this->db->table($this->table)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getCount($kodebpr, $periodeId, $faktor11Id = null)
    {
        $builder = $this->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId);

        if ($faktor11Id !== null) {
            $builder->where('faktor11id', $faktor11Id);
        }

        return $builder->countAllResults();
    }

    public function getLatestKomentar($kodebpr, $periodeId, $faktor11Id, $limit = 5)
    {
        return $this->db->table($this->table)
            ->select('faktor11_comments.*, users.fullname')
            ->join('users', 'users.id = faktor11_comments.user_id', 'left')
            ->where('faktor11_comments.kodebpr', $kodebpr)
            ->where('faktor11_comments.periode_id', $periodeId)
            ->where('faktor11_comments.faktor11id', $faktor11Id)
            ->orderBy('faktor11_comments.created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    public function getFaktorList($kodebpr, $periodeId)
    {
        return $this->db->table($this->table)
            ->select('faktor11id, COUNT(*) as total')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->groupBy('faktor11id')
            ->orderBy('faktor11id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function hasKomentar($kodebpr, $periodeId, $faktor11Id)
    {
        return $this->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor11id', $faktor11Id)
            ->countAllResults() > 0;
    }

    public function getStatistikByFaktor($kodebpr, $periodeId)
    {
        return $this->db->table($this->table)
            ->select('faktor11id, COUNT(*) as total, MAX(created_at) as latest_comment')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->groupBy('faktor11id')
            ->orderBy('faktor11id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function markMultipleAsRead(array $commentIds, int $userId)
    {
        return $this->db->table($this->table)
            ->whereIn('id', $commentIds)
            ->where('user_id !=', $userId)
            ->update(['is_read' => 1]);
    }

    public function getUnreadCountByFaktor($faktor11Id, $kodebpr, $periodeId, $userId)
    {
        return $this->db->table($this->table)
            ->where('faktor11id', $faktor11Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('user_id !=', $userId)
            ->where('is_read', 0)
            ->countAllResults();
    }

    public function resetAutoIncrement()
    {
        $this->db->query('ALTER TABLE faktor11_comments AUTO_INCREMENT = 1');
    }

    public function deleteKomentar($id)
    {
        return $this->delete($id);
    }

    public function deleteByPeriode($kodebpr, $periodeId, $faktor11Id = null)
    {
        $builder = $this->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId);

        if ($faktor11Id !== null) {
            $builder->where('faktor11id', $faktor11Id);
        }

        return $builder->delete();
    }
}