<?php
namespace App\Models;

use CodeIgniter\Model;

class M_tkscomments extends Model
{
    protected $table = 'tks_comments';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'user_id',
        'fullname',
        'kategori',
        'komentar',
        'kodebpr',
        'periode_id'
    ];

    protected bool $allowEmptyInserts = false;

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;

    public function __construct()
    {
        parent::__construct();
    }

    public function insertKomentar($data)
    {
        return $this->insert($data);
    }

    public function getKomentarByPeriode($kodebpr, $periodeId, $kategori = null)
    {
        $builder = $this->db->table($this->table)
            ->select('tks_comments.*, users.fullname')
            ->join('users', 'users.id = tks_comments.user_id', 'left')
            ->where('tks_comments.kodebpr', $kodebpr)
            ->where('tks_comments.periode_id', $periodeId);

        if ($kategori !== null) {
            $builder->where('tks_comments.kategori', $kategori);
        }

        return $builder
            ->orderBy('tks_comments.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getCount($kodebpr, $periodeId, $kategori = null)
    {
        $builder = $this->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId);

        if ($kategori !== null) {
            $builder->where('kategori', $kategori);
        }

        return $builder->countAllResults();
    }

    public function getLatestKomentar($kodebpr, $periodeId, $kategori, $limit = 5)
    {
        return $this->db->table($this->table)
            ->select('tks_comments.*, users.fullname')
            ->join('users', 'users.id = tks_comments.user_id', 'left')
            ->where('tks_comments.kodebpr', $kodebpr)
            ->where('tks_comments.periode_id', $periodeId)
            ->where('tks_comments.kategori', $kategori)
            ->orderBy('tks_comments.created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    public function getKategoriList($kodebpr, $periodeId)
    {
        return $this->db->table($this->table)
            ->select('kategori, COUNT(*) as total')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->groupBy('kategori')
            ->orderBy('kategori', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function deleteKomentar($id)
    {
        return $this->delete($id);
    }

    public function deleteByPeriode($kodebpr, $periodeId, $kategori = null)
    {
        $builder = $this->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId);

        if ($kategori !== null) {
            $builder->where('kategori', $kategori);
        }

        return $builder->delete();
    }

    public function deleteByKategori($kodebpr, $kategori)
    {
        return $this->where('kodebpr', $kodebpr)
            ->where('kategori', $kategori)
            ->delete();
    }

    public function hasKomentar($kodebpr, $periodeId, $kategori)
    {
        return $this->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('kategori', $kategori)
            ->countAllResults() > 0;
    }

    public function getStatistikByKategori($kodebpr, $periodeId)
    {
        return $this->db->table($this->table)
            ->select('kategori, COUNT(*) as total, MAX(created_at) as latest_comment')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->groupBy('kategori')
            ->orderBy('kategori', 'ASC')
            ->get()
            ->getResultArray();
    }
}