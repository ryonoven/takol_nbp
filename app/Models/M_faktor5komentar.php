<?php
namespace App\Models;

use CodeIgniter\Model;

class M_faktor5komentar extends Model
{
    protected $table = 'faktor5_comments';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'faktor5id',
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

    public function getKomentarByPeriode($kodebpr, $periodeId, $faktor5Id = null)
    {
        $builder = $this->db->table($this->table)
            ->select('faktor5_comments.*, users.fullname, users.email')
            ->join('users', 'users.id = faktor5_comments.user_id', 'left')
            ->where('faktor5_comments.kodebpr', $kodebpr)
            ->where('faktor5_comments.periode_id', $periodeId);

        if ($faktor5Id !== null) {
            $builder->where('faktor5_comments.faktor5id', $faktor5Id);
        }

        return $builder
            ->orderBy('faktor5_comments.created_at', 'ASC') 
            ->get()
            ->getResultArray();
    }

    public function getKomentarByFaktorId($faktor5Id, $kodebpr, $periodeId = null)
    {
        return $this->getKomentarByPeriode($kodebpr, $periodeId, $faktor5Id);
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

    public function getCount($kodebpr, $periodeId, $faktor5Id = null)
    {
        $builder = $this->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId);

        if ($faktor5Id !== null) {
            $builder->where('faktor5id', $faktor5Id);
        }

        return $builder->countAllResults();
    }

    public function getLatestKomentar($kodebpr, $periodeId, $faktor5Id, $limit = 5)
    {
        return $this->db->table($this->table)
            ->select('faktor5_comments.*, users.fullname')
            ->join('users', 'users.id = faktor5_comments.user_id', 'left')
            ->where('faktor5_comments.kodebpr', $kodebpr)
            ->where('faktor5_comments.periode_id', $periodeId)
            ->where('faktor5_comments.faktor5id', $faktor5Id)
            ->orderBy('faktor5_comments.created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    public function getFaktorList($kodebpr, $periodeId)
    {
        return $this->db->table($this->table)
            ->select('faktor5id, COUNT(*) as total')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->groupBy('faktor5id')
            ->orderBy('faktor5id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function hasKomentar($kodebpr, $periodeId, $faktor5Id)
    {
        return $this->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor5id', $faktor5Id)
            ->countAllResults() > 0;
    }

    public function getStatistikByFaktor($kodebpr, $periodeId)
    {
        return $this->db->table($this->table)
            ->select('faktor5id, COUNT(*) as total, MAX(created_at) as latest_comment')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->groupBy('faktor5id')
            ->orderBy('faktor5id', 'ASC')
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

    public function getUnreadCountByFaktor($faktor5Id, $kodebpr, $periodeId, $userId)
    {
        return $this->db->table($this->table)
            ->where('faktor5id', $faktor5Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('user_id !=', $userId)
            ->where('is_read', 0)
            ->countAllResults();
    }

    public function resetAutoIncrement()
    {
        $this->db->query('ALTER TABLE faktor5_comments AUTO_INCREMENT = 1');
    }

    public function deleteKomentar($id)
    {
        return $this->delete($id);
    }

    public function deleteByPeriode($kodebpr, $periodeId, $faktor5Id = null)
    {
        $builder = $this->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId);

        if ($faktor5Id !== null) {
            $builder->where('faktor5id', $faktor5Id);
        }

        return $builder->delete();
    }
}