<?php
namespace App\Models;

use CodeIgniter\Model;

class M_kalkulatorlikuiditas extends Model
{
    protected $table = 'kalkulator_likuiditas';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id',
        'kodebpr',
        'periode_id',
        'asetlikuid',
        'totalaset',
        'kas',
        'girobanklain',
        'tabunganbanklain',
        'kewajibanlancar',
        'kewajibansegera',
        'tabungandpk',
        'depositodpk',
        'tabunganabp',
        'depositoabp',
        'pinjamanditerima',
        'kreditkyd',
        'totaldpk',
        'penabung25deposan',
        'totalpendanaan',
        'transaksibpr',
        'pendanaannoninti',
        'dpkdiataslps',
        'pinjamananmungkinditarik',
        'rasioasetlikuidtotalaset',
        'rasioasetlikuidkewajiban',
        'rasiokreditterhadapdpk',
        'rasio25deposan',
        'rasiononinti',
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
        return $this->db->query('ALTER TABLE kalkulator_likuiditas AUTO_INCREMENT = 1');
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
        $asetlikuid = $data['kas'] + $data['girobanklain'] + ['tabunganbanklain'];
        $kewajibanlancar = $data['kewajibansegera'] + $data['tabungandpk'] + $data['depositodpk'] + $data['tabunganabp'] + $data['depositoabp'] + $data['pinjamanditerima'];
        $totaldpk = $data['tabungandpk'] + $data['depositodpk'];
        $transaksibpr = $data['tabunganabp'] + $data['depositoabp'];
        $pendanaannoninti = $data['dpkdiataslps'] + $data['transaksibpr'] + ['pinjamananmungkinditarik'];
        $rasioasetlikuidtotalaset = $data['totalaset'] > 0 ? ($asetlikuid / $data['totalaset']) * 100 : 0;
        $rasioasetlikuidkewajiban = $kewajibanlancar > 0 ? ($asetlikuid / $kewajibanlancar) * 100 : 0;
        $rasiokreditterhadapdpk = $data['kreditkyd'] > 0 ? ($totaldpk / $data['kreditkyd']) * 100 : 0;
        $rasio25deposan = $totaldpk > 0 ? ($data['penabung25deposan'] / $totaldpk) * 100 : 0;
        $rasiononinti = $data['totalpendanaan'] > 0 ? ($pendanaannoninti / $data['totalpendanaan']) * 100 : 0;


        $insertData = array_merge($data, [
            'asetlikuid' => $asetlikuid,
            'kewajibanlancar' => $kewajibanlancar,
            'totaldpk' => $totaldpk,
            'transaksibpr' => $transaksibpr,
            'pendanaannoninti' => $pendanaannoninti,
            'rasioasetlikuidtotalaset' => $rasioasetlikuidtotalaset,
            'rasioasetlikuidkewajiban' => $rasioasetlikuidkewajiban,
            'rasiokreditterhadapdpk' => $rasiokreditterhadapdpk,
            'rasio25deposan' => $rasio25deposan,
            'rasiononinti' => $rasiononinti,
        ]);

        return $this->insert($insertData);
    }

    public function updateKalkulatorKredit($id, $data)
    {
        $asetlikuid = $data['kas'] + $data['girobanklain'] + ['tabunganbanklain'];
        $kewajibanlancar = $data['kewajibansegera'] + $data['tabungandpk'] + $data['depositodpk'] + $data['tabunganabp'] + $data['depositoabp'] + $data['pinjamanditerima'];
        $totaldpk = $data['tabungandpk'] + $data['depositodpk'];
        $transaksibpr = $data['tabunganabp'] + $data['depositoabp'];
        $pendanaannoninti = $data['dpkdiataslps'] + $data['transaksibpr'] + ['pinjamananmungkinditarik'];
        $rasioasetlikuidtotalaset = $data['totalaset'] > 0 ? ($asetlikuid / $data['totalaset']) * 100 : 0;
        $rasioasetlikuidkewajiban = $kewajibanlancar > 0 ? ($asetlikuid / $kewajibanlancar) * 100 : 0;
        $rasiokreditterhadapdpk = $data['kreditkyd'] > 0 ? ($totaldpk / $data['kreditkyd']) * 100 : 0;
        $rasio25deposan = $totaldpk > 0 ? ($data['penabung25deposan'] / $totaldpk) * 100 : 0;
        $rasiononinti = $data['totalpendanaan'] > 0 ? ($pendanaannoninti / $data['totalpendanaan']) * 100 : 0;


        $updateData = array_merge($data, [
            'asetlikuid' => $asetlikuid,
            'kewajibanlancar' => $kewajibanlancar,
            'totaldpk' => $totaldpk,
            'transaksibpr' => $transaksibpr,
            'pendanaannoninti' => $pendanaannoninti,
            'rasioasetlikuidtotalaset' => $rasioasetlikuidtotalaset,
            'rasioasetlikuidkewajiban' => $rasioasetlikuidkewajiban,
            'rasiokreditterhadapdpk' => $rasiokreditterhadapdpk,
            'rasio25deposan' => $rasio25deposan,
            'rasiononinti' => $rasiononinti,
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
        return $this->db->query('ALTER TABLE kalkulator_likuiditas AUTO_INCREMENT = ?', [$value]);
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