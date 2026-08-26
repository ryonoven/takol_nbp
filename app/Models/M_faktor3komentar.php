<?php
namespace App\Models;

use CodeIgniter\Model;

class M_faktor3komentar extends Model
{
    protected $table = 'faktor3_comments';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'faktor3id',
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

    public function getKomentarByPeriode($kodebpr, $periodeId, $faktor3Id = null)
    {
        $builder = $this->db->table($this->table)
            ->select('faktor3_comments.*, users.fullname, users.email')
            ->join('users', 'users.id = faktor3_comments.user_id', 'left')
            ->where('faktor3_comments.kodebpr', $kodebpr)
            ->where('faktor3_comments.periode_id', $periodeId);

        if ($faktor3Id !== null) {
            $builder->where('faktor3_comments.faktor3id', $faktor3Id);
        }

        return $builder
            ->orderBy('faktor3_comments.created_at', 'ASC') 
            ->get()
            ->getResultArray();
    }

    public function getKomentarByFaktorId($faktor3Id, $kodebpr, $periodeId = null)
    {
        return $this->getKomentarByPeriode($kodebpr, $periodeId, $faktor3Id);
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

    public function getCount($kodebpr, $periodeId, $faktor3Id = null)
    {
        $builder = $this->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId);

        if ($faktor3Id !== null) {
            $builder->where('faktor3id', $faktor3Id);
        }

        return $builder->countAllResults();
    }

    public function getLatestKomentar($kodebpr, $periodeId, $faktor3Id, $limit = 5)
    {
        return $this->db->table($this->table)
            ->select('faktor3_comments.*, users.fullname')
            ->join('users', 'users.id = faktor3_comments.user_id', 'left')
            ->where('faktor3_comments.kodebpr', $kodebpr)
            ->where('faktor3_comments.periode_id', $periodeId)
            ->where('faktor3_comments.faktor3id', $faktor3Id)
            ->orderBy('faktor3_comments.created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    public function getFaktorList($kodebpr, $periodeId)
    {
        return $this->db->table($this->table)
            ->select('faktor3id, COUNT(*) as total')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->groupBy('faktor3id')
            ->orderBy('faktor3id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function hasKomentar($kodebpr, $periodeId, $faktor3Id)
    {
        return $this->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor3id', $faktor3Id)
            ->countAllResults() > 0;
    }

    public function getStatistikByFaktor($kodebpr, $periodeId)
    {
        return $this->db->table($this->table)
            ->select('faktor3id, COUNT(*) as total, MAX(created_at) as latest_comment')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->groupBy('faktor3id')
            ->orderBy('faktor3id', 'ASC')
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

    public function getUnreadCountByFaktor($faktor3Id, $kodebpr, $periodeId, $userId)
    {
        return $this->db->table($this->table)
            ->where('faktor3id', $faktor3Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('user_id !=', $userId)
            ->where('is_read', 0)
            ->countAllResults();
    }

    public function resetAutoIncrement()
    {
        $this->db->query('ALTER TABLE faktor3_comments AUTO_INCREMENT = 1');
    }

    public function deleteKomentar($id)
    {
        return $this->delete($id);
    }

    public function deleteByPeriode($kodebpr, $periodeId, $faktor3Id = null)
    {
        $builder = $this->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId);

        if ($faktor3Id !== null) {
            $builder->where('faktor3id', $faktor3Id);
        }

        return $builder->delete();
    }
}