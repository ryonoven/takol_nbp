<?php
namespace App\Models;

use CodeIgniter\Model;

class M_tksrentabilitas extends Model
{
    protected $table = 'tksrentabilitas';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id',
        'kodebpr',
        'periode_id',
        'fullname',
        'labarugibelumpajak',
        'ratarataaset',
        'penilaianroa',
        'peringkatroa',
        'rasioroa',
        'lababelumpajakbefore',
        'ratarataasetbefore',
        'penilaianroabefore',
        'peringkatroabefore',
        'rasioroabefore',
        'bebanoperasional',
        'pendapatanoperasional',
        'rasiobopo',
        'peringkatbopo',
        'penilaianbopo',
        'bebanoperasionalbefore',
        'pendapatanoperasionalbefore',
        'rasiobopobefore',
        'peringkatbopobefore',
        'penilaianbopobefore',
        'bebanbunga',
        'pendapatanbunga',
        'ratarataasetproduktif',
        'rasionim',
        'peringkatnim',
        'penilaiannim',
        'bebanbungabefore',
        'pendapatanbungabersihbefore',
        'ratarataasetprodbefore',
        'rasionimbefore',
        'peringkatnimbefore',
        'penilaiannimbefore',
        'pendapatanbungabersih',
        'rataratarentabilitas',
        'penilaianrentabilitas',
        'peringkatrentabilitas',
        'rataratarentabilitasbefore',
        'penilaianrentabilitasbefore',
        'peringkatrentabilitasbefore',
        'nilaifaktor',
        'nilaifaktorbefore',
        'deskripsirentabilitas',
        'analisarentabilitas',
        'created_at',
        'updated_at',
        'pendapatanbungabefore',
        'is_approved',
        'approved_by',
        'approved_at'
    ];
    protected $useTimestamps = false;
    protected $returnType = 'array';

    public function __construct()
    {
        parent::__construct();
    }

    public function getByKodebprAndPeriode($kodebpr, $periodeId)
    {
        return $this->where([
            'kodebpr' => $kodebpr,
            'periode_id' => $periodeId
        ])->first();
    }

    public function saveData($data, $kodebpr, $periodeId)
    {
        $existing = $this->getByKodebprAndPeriode($kodebpr, $periodeId);

        if ($existing) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            return $this->update($existing['id'], $data);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            return $this->insert($data);
        }
    }

    public function hitungRataRataRentabilitas($kodebpr, $periodeId)
    {
        $data = $this->getByKodebprAndPeriode($kodebpr, $periodeId);

        if (!$data)
            return false;

        $peringkatROA = $data['peringkatroa'] ?? 0;
        $peringkatNIM = $data['peringkatnim'] ?? 0;

        if ($peringkatROA > 0 && $peringkatNIM > 0) {
            $rataRata = ($peringkatROA + $peringkatNIM) / 2;
            $peringkatRentabilitas = round($rataRata);

            $nilaiFaktor = $peringkatRentabilitas * 0.15;

            return $this->update($data['id'], [
                'rataratarentabilitas' => $rataRata,
                'penilaianrentabilitas' => $peringkatRentabilitas,
                'peringkatrentabilitas' => $peringkatRentabilitas,
                'nilaifaktor' => $nilaiFaktor,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }

        return false;
    }

    public function hitungRataRataRentabilitasBefore($kodebpr, $periodeId)
    {
        $data = $this->getByKodebprAndPeriode($kodebpr, $periodeId);

        if (!$data)
            return false;

        $peringkatROABefore = $data['peringkatroabefore'] ?? 0;
        $peringkatNIMBefore = $data['peringkatnimbefore'] ?? 0;

        if ($peringkatROABefore > 0 && $peringkatNIMBefore > 0) {
            $rataRataBefore = ($peringkatROABefore + $peringkatNIMBefore) / 2;
            $peringkatRentabilitasBefore = round($rataRataBefore);

            $nilaiFaktorBefore = $peringkatRentabilitasBefore * 0.15;

            return $this->update($data['id'], [
                'rataratarentabilitasbefore' => $rataRataBefore,
                'penilaianrentabilitasbefore' => $peringkatRentabilitasBefore,
                'peringkatrentabilitasbefore' => $peringkatRentabilitasBefore,
                'nilaifaktorbefore' => $nilaiFaktorBefore,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }

        return false;
    }

    public function getKomentarByFaktorIdAndKodebpr($faktorId, $kodebpr)
    {
        return $this->db->table($this->table)
            ->select('tksrentabilitas.*, users.fullname')
            ->join('users', 'users.id = tksrentabilitas.user_id', 'left')
            ->where([
                'tksrentabilitas.faktor1id' => $faktorId,
                'tksrentabilitas.kodebpr' => $kodebpr
            ])
            ->orderBy('tksrentabilitas.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function insertBatchSync(array $rows): bool
    {
        if (empty($rows)) return true;
 
        $result = $this->db->table($this->table)->insertBatch($rows);
 
        return $result !== false;
    }
}