<?php
namespace App\Models;

use CodeIgniter\Model;

class M_semesterancomments extends Model
{
    protected $table = 'semesteran_comments';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'user_id',
        'fullname',
        'kategori',
        'subkategori',
        'komentar',
        'kodebpr',
        'periode_id',
        'updated_at'
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

    public function getKomentarByPeriode($kodebpr, $periodeId, $kategori = null, $subkategori = null)
    {
        $builder = $this->db->table($this->table)
            ->select('semesteran_comments.*, users.fullname')
            ->join('users', 'users.id = semesteran_comments.user_id', 'left')
            ->where('semesteran_comments.kodebpr', $kodebpr)
            ->where('semesteran_comments.periode_id', $periodeId);

        if ($kategori !== null) {
            $builder->where('semesteran_comments.kategori', $kategori);
        }

        if ($subkategori !== null) {
            $builder->where('semesteran_comments.subkategori', $subkategori);
        }

        return $builder
            ->orderBy('semesteran_comments.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getCount($kodebpr, $periodeId, $kategori = null, $subkategori = null)
    {
        $builder = $this->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId);

        if ($kategori !== null) {
            $builder->where('kategori', $kategori);
        }

        if ($subkategori !== null) {
            $builder->where('subkategori', $subkategori);
        }

        return $builder->countAllResults();
    }

    public function getLatestKomentar($kodebpr, $periodeId, $kategori, $subkategori = null, $limit = 5)
    {
        $builder = $this->db->table($this->table)
            ->select('semesteran_comments.*, users.fullname')
            ->join('users', 'users.id = semesteran_comments.user_id', 'left')
            ->where('semesteran_comments.kodebpr', $kodebpr)
            ->where('semesteran_comments.periode_id', $periodeId)
            ->where('semesteran_comments.kategori', $kategori);

        if ($subkategori !== null) {
            $builder->where('semesteran_comments.subkategori', $subkategori);
        }

        return $builder
            ->orderBy('semesteran_comments.created_at', 'DESC')
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

    public function getSubkategoriList($kodebpr, $periodeId, $kategori = null)
    {
        $builder = $this->db->table($this->table)
            ->select('subkategori, COUNT(*) as total')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId);

        if ($kategori !== null) {
            $builder->where('kategori', $kategori);
        }

        return $builder
            ->groupBy('subkategori')
            ->orderBy('subkategori', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function deleteKomentar($id)
    {
        return $this->delete($id);
    }

    public function deleteByPeriode($kodebpr, $periodeId, $kategori = null, $subkategori = null)
    {
        $builder = $this->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId);

        if ($kategori !== null) {
            $builder->where('kategori', $kategori);
        }

        if ($subkategori !== null) {
            $builder->where('subkategori', $subkategori);
        }

        return $builder->delete();
    }

    public function deleteByKategori($kodebpr, $kategori, $subkategori = null)
    {
        $builder = $this->where('kodebpr', $kodebpr)
            ->where('kategori', $kategori);

        if ($subkategori !== null) {
            $builder->where('subkategori', $subkategori);
        }

        return $builder->delete();
    }

    public function hasKomentar($kodebpr, $periodeId, $kategori, $subkategori = null)
    {
        $builder = $this->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('kategori', $kategori);

        if ($subkategori !== null) {
            $builder->where('subkategori', $subkategori);
        }

        return $builder->countAllResults() > 0;
    }

    public function getStatistikByKategori($kodebpr, $periodeId)
    {
        return $this->db->table($this->table)
            ->select('kategori, subkategori, COUNT(*) as total, MAX(created_at) as latest_comment')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->groupBy('kategori, subkategori')
            ->orderBy('kategori', 'ASC')
            ->get()
            ->getResultArray();
    }
}