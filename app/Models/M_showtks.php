<?php

namespace App\Models;

use CodeIgniter\Model;

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
        'updated_at',
        'created_at',

        // Profil Risiko
        'nprofilrisiko',
        'nprofilrisikobefore',
        'nfaktorprofilrisiko',
        'nfaktorprofilrisikobefore',
        'kesimpulan2',

        // Tata Kelola
        'ntatakelola',
        'ntatakelolabefore',
        'nfaktortakol',
        'nfaktortakolbefore',
        'kesimpulan3',

        // Rentabilitas
        'nrentabilitas',
        'nrentabilitasbefore',
        'nfaktorrentabilitas',
        'nfaktorrentabilitasbefore',
        'kesimpulan4',

        // Permodalan
        'npermodalan',
        'npermodalanbefore',
        'nfaktorpermodalan',
        'nfaktorpermodalanbefore',
        'kesimpulan5',

        //Komposit
        'nkomposittks',
        'nkomposittksbefore',
        'peringkatkomposittks',
        'peringkatkomposittksbefore'
    ];

    protected $useTimestamps = true;

    protected $afterInsert = ['triggerKompositRecalculation'];
    protected $afterUpdate = ['triggerKompositRecalculation'];

    protected function triggerKompositRecalculation(array $data)
    {
        // Trigger recalculation if TKS factor fields are updated
        $tksFields = [
            'nfaktorprofilrisiko',
            'nfaktortakol',
            'nfaktorrentabilitas',
            'nfaktorpermodalan',
            'nfaktorprofilrisikobefore',
            'nfaktortakolbefore',
            'nfaktorrentabilitasbefore',
            'nfaktorpermodalanbefore'
        ];

        $needsRecalculation = false;

        if (isset($data['data'])) {
            foreach ($tksFields as $field) {
                if (array_key_exists($field, $data['data'])) {
                    $needsRecalculation = true;
                    break;
                }
            }
        }

        if ($needsRecalculation && isset($data['data']['kodebpr']) && isset($data['data']['periode_id'])) {
            $this->recalculateKomposit($data['data']['kodebpr'], $data['data']['periode_id']);
        }

        return $data;
    }

    private function recalculateKomposit($kodebpr, $periodeId)
    {
        try {
            $existing = $this->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->first();

            if (!$existing) {
                return;
            }

            // Calculate current
            $sum = 0;
            $count = 0;
            $fields = ['nfaktorprofilrisiko', 'nfaktortakol', 'nfaktorrentabilitas', 'nfaktorpermodalan'];

            foreach ($fields as $field) {
                if (isset($existing[$field]) && is_numeric($existing[$field])) {
                    $sum += $existing[$field];
                    $count++;
                }
            }

            $nkomposittks = $count > 0 ? $sum : null;

            // Calculate before
            $sumBefore = 0;
            $countBefore = 0;
            $fieldsBefore = ['nfaktorprofilrisikobefore', 'nfaktortakolbefore', 'nfaktorrentabilitasbefore', 'nfaktorpermodalanbefore'];

            foreach ($fieldsBefore as $field) {
                if (isset($existing[$field]) && is_numeric($existing[$field])) {
                    $sumBefore += $existing[$field];
                    $countBefore++;
                }
            }

            $nkomposittksbefore = $countBefore > 0 ? $sumBefore : null;

            // Update
            $updateData = [
                'nkomposittks' => $nkomposittks,
                'nkomposittksbefore' => $nkomposittksbefore,
                'peringkatkomposittks' => $this->getPeringkatKomposit($nkomposittks),
                'peringkatkomposittksbefore' => $this->getPeringkatKomposit($nkomposittksbefore),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $this->update($existing['id'], $updateData);

            log_message('info', "Komposit recalculated for kodebpr: {$kodebpr}, periode: {$periodeId}");

        } catch (\Exception $e) {
            log_message('error', 'Error in recalculateKomposit: ' . $e->getMessage());
        }
    }

    private function getPeringkatKomposit($nilaiKomposit)
    {
        if ($nilaiKomposit === null || !is_numeric($nilaiKomposit)) {
            return null;
        }

        // Mapping berdasarkan ketentuan
        if ($nilaiKomposit >= 1.00 && $nilaiKomposit <= 1.50) {
            return 1;  // ✅ Return angka, bukan string
        } elseif ($nilaiKomposit >= 1.51 && $nilaiKomposit <= 2.60) {
            return 2;
        } elseif ($nilaiKomposit >= 2.61 && $nilaiKomposit <= 3.40) {
            return 3;
        } elseif ($nilaiKomposit >= 3.41 && $nilaiKomposit <= 4.20) {
            return 4;
        } elseif ($nilaiKomposit >= 4.21 && $nilaiKomposit <= 5.00) {
            return 5;
        }

        return null;
    }

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

    public function getPeriodeByUser($userId)
    {
        return $this->where('user_id', $userId)->findAll();
    }

    public function getPeriodeByBpr($kodebpr)
    {
        return $this->where('kodebpr', $kodebpr)->findAll();
    }

    public function getPeriodeDetail($periodeId)
    {
        return $this->where('id', $periodeId)->first();
    }

    public function getModalintiDetail($periodeId)
    {
        return $this->where('modalinti', $periodeId)->first();
    }

    public function getTotalasetDetail($periodeId)
    {
        return $this->where('totalaset', $periodeId)->first();
    }
}
