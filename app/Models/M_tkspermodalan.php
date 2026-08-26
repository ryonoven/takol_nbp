<?php
namespace App\Models;

use CodeIgniter\Model;

class M_tkspermodalan extends Model
{
    protected $table = 'tkspermodalan';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id',
        'kodebpr',
        'periode_id',
        // Data Periode Saat Ini
        'modalinti',
        'modalpelengkap',
        'atmr',
        'rasiokpmm',
        'peringkatkpmm',
        'penilaiankpmm',
        'modalintiapb',
        'totalasetbermasalah',
        'rasiomiapb',
        'peringkatmiapb',
        'penilaianmiapb',
        'rataratapermodalan',
        'penilaianpermodalan',
        'peringkatpermodalan',
        // Data Periode Sebelumnya
        'modalintibefore',
        'modalpelengkapbefore',
        'atmrbefore',
        'rasiokpmmbefore',
        'peringkatkpmmbefore',
        'penilaiankpmmbefore',
        'modalintiapbbefore',
        'totalasetbermasalahbefore',
        'rasiomiapbbefore',
        'peringkatmiapbbefore',
        'penilaianmiapbbefore',
        'rataratapermodalanbefore',
        'penilaianpermodalanbefore',
        'peringkatpermodalanbefore',
        'nilaifaktor',
        'nilaifaktorbefore',
        // Deskripsi & Analisis
        'deskripsipermodalan',
        'analisispermodalan',
        'created_at',
        'updated_at',
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

    public function hitungRataRataPermodalan($kodebpr, $periodeId)
    {
        $data = $this->getByKodebprAndPeriode($kodebpr, $periodeId);

        if (!$data)
            return false;

        $peringkatKPMM = $data['peringkatkpmm'] ?? 0;
        $peringkatMIAPB = $data['peringkatmiapb'] ?? 0;

        if ($peringkatKPMM > 0 && $peringkatMIAPB > 0) {
            $rataRata = ($peringkatKPMM + $peringkatMIAPB) / 2;
            $peringkatPermodalan = round($rataRata);
            $nilaiFaktor = $peringkatPermodalan * 0.30;

            // Simpan dengan immediate commit
            $result = $this->update($data['id'], [
                'rataratapermodalan' => $rataRata,
                'penilaianpermodalan' => $peringkatPermodalan,
                'peringkatpermodalan' => $peringkatPermodalan,
                'nilaifaktor' => $nilaiFaktor,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            // Force write to database
            if ($this->db) {
                $this->db->close();
                $this->db->initialize();
            }

            return $result;
        }

        return false;
    }

    public function hitungRataRataPermodalanBefore($kodebpr, $periodeId)
    {
        $data = $this->getByKodebprAndPeriode($kodebpr, $periodeId);

        if (!$data)
            return false;

        $peringkatKPMMBefore = $data['peringkatkpmmbefore'] ?? 0;
        $peringkatMIAPBBefore = $data['peringkatmiapbbefore'] ?? 0;

        if ($peringkatKPMMBefore > 0 && $peringkatMIAPBBefore > 0) {
            $rataRataBefore = ($peringkatKPMMBefore + $peringkatMIAPBBefore) / 2;
            $peringkatPermodalanBefore = round($rataRataBefore);
            $nilaiFaktorBefore = $peringkatPermodalanBefore * 0.30;

            // Simpan dengan immediate commit
            $result = $this->update($data['id'], [
                'rataratapermodalanbefore' => $rataRataBefore,
                'penilaianpermodalanbefore' => $peringkatPermodalanBefore,
                'peringkatpermodalanbefore' => $peringkatPermodalanBefore,
                'nilaifaktorbefore' => $nilaiFaktorBefore,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            // Force write to database
            if ($this->db) {
                $this->db->close();
                $this->db->initialize();
            }

            return $result;
        }

        return false;
    }

    public function getKomentarByFaktorIdAndKodebpr($faktorId, $kodebpr)
    {
        return $this->db->table($this->table)
            ->select('tkspermodalan.*, users.fullname')
            ->join('users', 'users.id = tkspermodalan.user_id', 'left')
            ->where([
                'tkspermodalan.faktor1id' => $faktorId,
                'tkspermodalan.kodebpr' => $kodebpr
            ])
            ->orderBy('tkspermodalan.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }
}