<?php
namespace App\Models;

use CodeIgniter\Model;

class M_faktor12komentar extends Model
{
    protected $table = 'faktor12_comments';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'faktor12id',
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

    public function getKomentarByPeriode($kodebpr, $periodeId, $faktor12Id = null)
    {
        $builder = $this->db->table($this->table)
            ->select('faktor12_comments.*, users.fullname, users.email')
            ->join('users', 'users.id = faktor12_comments.user_id', 'left')
            ->where('faktor12_comments.kodebpr', $kodebpr)
            ->where('faktor12_comments.periode_id', $periodeId);

        if ($faktor12Id !== null) {
            $builder->where('faktor12_comments.faktor12id', $faktor12Id);
        }

        return $builder
            ->orderBy('faktor12_comments.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getKomentarByFaktorId($faktor12Id, $kodebpr, $periodeId = null)
    {
        return $this->getKomentarByPeriode($kodebpr, $periodeId, $faktor12Id);
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

    public function getCount($kodebpr, $periodeId, $faktor12Id = null)
    {
        $builder = $this->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId);

        if ($faktor12Id !== null) {
            $builder->where('faktor12id', $faktor12Id);
        }

        return $builder->countAllResults();
    }

    public function getLatestKomentar($kodebpr, $periodeId, $faktor12Id, $limit = 5)
    {
        return $this->db->table($this->table)
            ->select('faktor12_comments.*, users.fullname')
            ->join('users', 'users.id = faktor12_comments.user_id', 'left')
            ->where('faktor12_comments.kodebpr', $kodebpr)
            ->where('faktor12_comments.periode_id', $periodeId)
            ->where('faktor12_comments.faktor12id', $faktor12Id)
            ->orderBy('faktor12_comments.created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    public function getFaktorList($kodebpr, $periodeId)
    {
        return $this->db->table($this->table)
            ->select('faktor12id, COUNT(*) as total')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->groupBy('faktor12id')
            ->orderBy('faktor12id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function hasKomentar($kodebpr, $periodeId, $faktor12Id)
    {
        return $this->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor12id', $faktor12Id)
            ->countAllResults() > 0;
    }

    public function getStatistikByFaktor($kodebpr, $periodeId)
    {
        return $this->db->table($this->table)
            ->select('faktor12id, COUNT(*) as total, MAX(created_at) as latest_comment')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->groupBy('faktor12id')
            ->orderBy('faktor12id', 'ASC')
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

    public function getUnreadCountByFaktor($faktor12Id, $kodebpr, $periodeId, $userId)
    {
        return $this->db->table($this->table)
            ->where('faktor12id', $faktor12Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('user_id !=', $userId)
            ->where('is_read', 0)
            ->countAllResults();
    }

    public function resetAutoIncrement()
    {
        $this->db->query('ALTER TABLE faktor12_comments AUTO_INCREMENT = 1');
    }

    public function deleteKomentar($id)
    {
        return $this->delete($id);
    }

    public function deleteByPeriode($kodebpr, $periodeId, $faktor12Id = null)
    {
        $builder = $this->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId);

        if ($faktor12Id !== null) {
            $builder->where('faktor12id', $faktor12Id);
        }

        return $builder->delete();
    }
}