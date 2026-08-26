<?php
namespace App\Models;

use CodeIgniter\Model;

class M_faktorkomentar extends Model
{
    protected $table = 'faktor_comments';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'faktor1id',
        'user_id',
        'fullname',
        'periode_id',
        'komentar',
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

    public function getKomentarByPeriode($kodebpr, $periodeId, $faktorId = null)
    {
        $builder = $this->db->table($this->table)
            ->select('faktor_comments.*, users.fullname, users.email')
            ->join('users', 'users.id = faktor_comments.user_id', 'left')
            ->where('faktor_comments.kodebpr', $kodebpr)
            ->where('faktor_comments.periode_id', $periodeId);

        if ($faktorId !== null) {
            $builder->where('faktor_comments.faktor1id', $faktorId);
        }

        return $builder
            ->orderBy('faktor_comments.created_at', 'ASC') 
            ->get()
            ->getResultArray();
    }

    public function getKomentarByFaktorId($faktorId, $kodebpr, $periodeId = null)
    {
        return $this->getKomentarByPeriode($kodebpr, $periodeId, $faktorId);
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

    public function getCount($kodebpr, $periodeId, $faktorId = null)
    {
        $builder = $this->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId);

        if ($faktorId !== null) {
            $builder->where('faktor1id', $faktorId);
        }

        return $builder->countAllResults();
    }

    public function getLatestKomentar($kodebpr, $periodeId, $faktorId, $limit = 5)
    {
        return $this->db->table($this->table)
            ->select('faktor_comments.*, users.fullname')
            ->join('users', 'users.id = faktor_comments.user_id', 'left')
            ->where('faktor_comments.kodebpr', $kodebpr)
            ->where('faktor_comments.periode_id', $periodeId)
            ->where('faktor_comments.faktor1id', $faktorId)
            ->orderBy('faktor_comments.created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    public function getFaktorList($kodebpr, $periodeId)
    {
        return $this->db->table($this->table)
            ->select('faktor1id, COUNT(*) as total')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->groupBy('faktor1id')
            ->orderBy('faktor1id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function hasKomentar($kodebpr, $periodeId, $faktorId)
    {
        return $this->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', $faktorId)
            ->countAllResults() > 0;
    }

    public function getStatistikByFaktor($kodebpr, $periodeId)
    {
        return $this->db->table($this->table)
            ->select('faktor1id, COUNT(*) as total, MAX(created_at) as latest_comment')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->groupBy('faktor1id')
            ->orderBy('faktor1id', 'ASC')
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

    public function getUnreadCountByFaktor($faktorId, $kodebpr, $periodeId, $userId)
    {
        return $this->db->table($this->table)
            ->where('faktor1id', $faktorId)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('user_id !=', $userId)
            ->where('is_read', 0)
            ->countAllResults();
    }

    public function resetAutoIncrement()
    {
        $this->db->query('ALTER TABLE faktor_comments AUTO_INCREMENT = 1');
    }

    public function deleteKomentar($id)
    {
        return $this->delete($id);
    }

    public function deleteByPeriode($kodebpr, $periodeId, $faktorId = null)
    {
        $builder = $this->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId);

        if ($faktorId !== null) {
            $builder->where('faktor1id', $faktorId);
        }

        return $builder->delete();
    }
}