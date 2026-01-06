<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Models\M_nilairisikokredit;
use App\Models\M_risikokreditkpmr;
use App\Models\M_risikooperasional;
use App\Models\M_risikooperasionalkpmr;
use App\Models\M_risikokepatuhan;
use App\Models\M_kepatuhankpmr;
use App\Models\M_likuiditasinheren;
use App\Models\M_likuiditaskpmr;
use App\Models\M_reputasiinheren;
use App\Models\M_reputasikpmr;
use App\Models\M_stratejikinheren;
use App\Models\M_stratejikkpmr;

class M_showtks extends Model
{
    protected $table = 'showtks';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'kodebpr',
        'periode_id',
        'tahun',
        'dirut',
        'dirkep',
        'pe',
        'tanggal',
        'lokasi',
        'kesimpulan',
        'pdf1_filename',
        'pdf2_filename',
        'cover',
        'updated_at'
    ];

    protected $useTimestamps = false;
    protected $returnType = 'array';

    public function getDataByKodebprAndPeriode($kodebpr, $periodeId)
    {
        return $this->where([
            'kodebpr' => $kodebpr,
            'periode_id' => $periodeId
        ])->findAll();
    }

    public function ubah($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }

    public function ubahttd($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }

    public function updatecover($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }

    public function hitungRataRata($kodebpr, $periodeId)
    {
        $allnfaktor = [
            'nfaktor' => new M_nilairisikokredit(),
            'nkpmr' => new M_risikokreditkpmr(),
            // 'nfaktor2' => new M_risikokreditkpmr(),
            'nfaktor3' => new M_risikooperasional(),
            'nfaktor4' => new M_risikooperasionalkpmr(),
            'nfaktor5' => new M_risikokepatuhan(),
            'nfaktor6' => new M_kepatuhankpmr(),
            'nfaktor7' => new M_likuiditasinheren(),
            'nfaktor8' => new M_likuiditaskpmr(),
            'nfaktor9' => new M_reputasiinheren(),
            'nfaktor10' => new M_reputasikpmr(),
            'nfaktor11' => new M_stratejikinheren(),
            'nfaktor12' => new M_stratejikkpmr()
        ];


        $totalNilai = 0;
        $countValid = 0;

        foreach ($allnfaktor as $field => $model) {
            $nilai = $model->select($field)
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->orderBy('created_at', 'DESC')
                ->first();

            if ($nilai && is_numeric($nilai[$field])) {
                $totalNilai += $nilai[$field];
                $countValid++;
            }
        }

        return $countValid > 0 ? $totalNilai / $countValid : 0;
    }

    public function hitungNilaiKomposit($kodebpr, $periodeId)
    {
        $nilaikomposit = $this->hitungRataRata($kodebpr, $periodeId);

        return ($nilaikomposit - floor($nilaikomposit)) >= 0.5 ? ceil($nilaikomposit) : floor($nilaikomposit);
    }

    public function simpanShowTks($data)
    {
        $existingData = $this->where('kodebpr', $data['kodebpr'])
            ->where('periode_id', $data['periode_id'])
            ->first();

        if ($existingData) {
            $this->update($existingData['id'], $data);
        } else {
            $this->save($data);
        }
    }

    public function simpanProfilRisiko($data)
    {
        $existing = $this->where('kodebpr', $data['kodebpr'])
            ->where('periode_id', $data['periode_id'])
            ->first();

        if ($existing) {
            return $this->update($existing['id'], $data);
        } else {
            return $this->insert($data);
        }
    }

    public function hitungPeringkatKomposit($nilaikomposit)
    {
        if ($nilaikomposit == 1) {
            return 'Sangat Baik';
        } elseif ($nilaikomposit == 2) {
            return 'Baik';
        } elseif ($nilaikomposit == 3) {
            return 'Cukup';
        } elseif ($nilaikomposit == 4) {
            return 'Kurang Baik';
        } elseif ($nilaikomposit == 5) {
            return 'Buruk';
        } else {
            return 'Nilai komposit tidak valid';
        }
    }

    public function getColorClassForPeringkat($peringkatkomposit)
    {
        switch ($peringkatkomposit) {
            case 'Sangat Baik':
                return 'text-success';
            case 'Baik':
                return 'text-info';
            case 'Cukup':
                return 'text-warning';
            case 'Kurang Baik':
                return 'text-danger';
            case 'Buruk':
                return 'text-dark';
            default:
                return 'text-muted';
        }
    }

    public function getByKodebprAndPeriode($kodebpr, $periodeId)
    {
        return $this->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();
    }
}
