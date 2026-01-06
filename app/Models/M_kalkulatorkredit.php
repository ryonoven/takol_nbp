<?php
namespace App\Models;

use CodeIgniter\Model;

class M_kalkulatorkredit extends Model
{
    protected $table = 'kalkulator_kredit';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id',
        'kodebpr',
        'periode_id',
        'aba',
        'kydbank',
        'kydpihak3',
        'kydgross',
        'totalaset',
        'total25debitur',
        'perdagangan',
        'jasa',
        'konsumsirumah',
        'sektorekonomi',
        'asetproduktif',
        'rasioasetproduktif',
        'rasiokreditdiberikan',
        'rasio25debitur',
        'rasioekonomi',
        'abanpl',
        'kydnpl3',
        'kydnpl4',
        'kydnpl5',
        'kreditdpk2',
        'kreditrestruktur1',
        'kreditbermasalah',
        'asetproduktifbermasalah',
        'kydkoleknpl',
        'rasioasetproduktifbermasalah',
        'rasiokreditbermasalah',
        'rasiokreditkualitasrendah',
        'created_at',
        'updated_at'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $returnType = 'array';

    private $userCache = [];

    public function __construct()
    {
        parent::__construct();
        $this->builder = $this->db->table($this->table);
    }

    public function getAllData()
    {
        return $this->findAll();
    }

    public function resetAutoIncrement()
    {
        return $this->db->query('ALTER TABLE kalkulator_kredit AUTO_INCREMENT = 1');
    }

    public function getDataByKodebprAndPeriode($kodebpr, $periodeId)
    {
        return $this->builder
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->get()
            ->getResultArray();
    }

    public function insertKalkulatorKredit($data)
    {
        $kydgross = $data['kydbank'] + $data['kydpihak3'];
        $asetproduktif = $data['aba'] + $kydgross;
        $rasioasetproduktif = $data['totalaset'] > 0 ? ($asetproduktif / $data['totalaset']) * 100 : 0;
        $rasiokreditdiberikan = $asetproduktif > 0 ? ($kydgross / $asetproduktif) * 100 : 0;
        $rasio25debitur = $kydgross > 0 ? ($data['total25debitur'] / $kydgross) * 100 : 0;
        $sektorekonomi = $data['perdagangan'] + $data['jasa'] + $data['konsumsirumah'];
        $rasioekonomi = $kydgross > 0 ? ($sektorekonomi / $kydgross) * 100 : 0;

        $insertData = array_merge($data, [
            'kydgross' => $kydgross,
            'asetproduktif' => $asetproduktif,
            'rasioasetproduktif' => $rasioasetproduktif,
            'rasiokreditdiberikan' => $rasiokreditdiberikan,
            'rasio25debitur' => $rasio25debitur,
            'sektorekonomi' => $sektorekonomi,
            'rasioekonomi' => $rasioekonomi,
        ]);

        return $this->insert($insertData);
    }

    public function updateKalkulatorKredit($id, $data)
    {
        $kydgross = $data['kydbank'] + $data['kydpihak3'];
        $asetproduktif = $data['aba'] + $kydgross;
        $rasioasetproduktif = $data['totalaset'] > 0 ? ($asetproduktif / $data['totalaset']) * 100 : 0;
        $rasiokreditdiberikan = $asetproduktif > 0 ? ($kydgross / $asetproduktif) * 100 : 0;
        $rasio25debitur = $kydgross > 0 ? ($data['total25debitur'] / $kydgross) * 100 : 0;
        $sektorekonomi = $data['perdagangan'] + $data['jasa'] + $data['konsumsirumah'];
        $rasioekonomi = $kydgross > 0 ? ($sektorekonomi / $kydgross) * 100 : 0;

        $updateData = array_merge($data, [
            'kydgross' => $kydgross,
            'asetproduktif' => $asetproduktif,
            'rasioasetproduktif' => $rasioasetproduktif,
            'rasiokreditdiberikan' => $rasiokreditdiberikan,
            'rasio25debitur' => $rasio25debitur,
            'sektorekonomi' => $sektorekonomi,
            'rasioekonomi' => $rasioekonomi,
        ]);

        return $this->update($id, $updateData);
    }

    public function cekDataExists($kodebpr, $periodeId)
    {
        return $this->where([
            'kodebpr' => $kodebpr,
            'periode_id' => $periodeId
        ])->first();
    }

    public function insertNilai($data)
    {
        return $this->insert($data);
    }

    public function ubahBerdasarkanFaktorId($data, $faktorId, $kodebpr, $periodeId)
    {
        return $this->where([
            'faktor1id' => $faktorId,
            'kodebpr' => $kodebpr,
            'periode_id' => $periodeId
        ])->set($data)->update();
    }

    public function ubah($data, $faktorId, $kodebpr, $periodeId)
    {
        return $this->ubahBerdasarkanFaktorId($data, $faktorId, $kodebpr, $periodeId);
    }

    public function setNullKolom($faktorId)
    {
        return $this->builder->update(
            ['penilaiankredit' => null, 'keterangan' => null],
            ['faktor1id' => $faktorId]
        );
    }

    public function setIncrement($value)
    {
        $value = (int) $value;
        return $this->db->query('ALTER TABLE kalkulator_kredit AUTO_INCREMENT = ?', [$value]);
    }

    public function bulkInsert($dataArray)
    {
        if (empty($dataArray)) {
            return false;
        }

        return $this->insertBatch($dataArray);
    }

    public function clearCache()
    {
        $this->userCache = [];
    }
}