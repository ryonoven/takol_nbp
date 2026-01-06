<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_periodeprofilresiko;
use App\Models\M_showprofilresiko;
use App\Models\M_infobpr;
use App\Models\M_user;
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
use Myth\Auth\Config\Services as AuthServices;

class Showprofilresiko extends Controller
{
    protected $auth;
    protected $session;
    protected $userKodebpr;
    protected $userId;
    protected $periodeId;

    // Models
    protected $periodeModel;
    protected $showprofilresikoModel;
    protected $kreditinherenModel;
    protected $kreditkpmrModel;
    protected $operasionalinherenModel;
    protected $operasionalkpmrModel;

    protected $kepatuhaninherenModel;
    protected $kepatuhankpmrModel;
    protected $likuiditasinherenModel;
    protected $likuiditaskpmrModel;

    protected $reputasiinherenModel;
    protected $reputasikpmrModel;
    protected $stratejikinherenModel;
    protected $stratejikkpmrModel;


    protected $userModel;
    protected $infobprModel;

    protected $userGroups = [];

    private $riskModelsConfig = [];

    public function __construct()
    {
        // Initialize core models
        $this->infobprModel = new M_infobpr();
        $this->periodeModel = new M_periodeprofilresiko();
        $this->showprofilresikoModel = new M_showprofilresiko();
        $this->kreditinherenModel = new M_nilairisikokredit();
        $this->kreditkpmrModel = new M_risikokreditkpmr();
        $this->operasionalinherenModel = new M_risikooperasional();
        $this->operasionalkpmrModel = new M_risikooperasionalkpmr();

        $this->kepatuhaninherenModel = new M_risikokepatuhan();
        $this->kepatuhankpmrModel = new M_kepatuhankpmr();
        $this->likuiditasinherenModel = new M_likuiditasinheren();
        $this->likuiditaskpmrModel = new M_likuiditaskpmr();

        $this->reputasiinherenModel = new M_reputasiinheren();
        $this->reputasikpmrModel = new M_reputasikpmr();
        $this->stratejikinherenModel = new M_stratejikinheren();
        $this->stratejikkpmrModel = new M_stratejikkpmr();

        $this->userModel = new M_user();

        $this->session = service('session');
        $this->auth = service('authentication');

        helper('text');

        // Initialize user data
        $this->initializeUser();

        // Initialize risk models config
        $this->initializeRiskModels();
    }

    private function initializeUser()
    {
        if ($this->auth->isLoggedIn()) {
            $this->userId = $this->auth->id();
            $user = $this->userModel->find($this->userId);
            $this->userKodebpr = $user['kodebpr'] ?? null;

            // Cache user groups
            $authorize = AuthServices::authorization();
            $groups = ['pe', 'admin', 'dekom', 'direksi'];
            foreach ($groups as $group) {
                $this->userGroups[$group] = $authorize->inGroup($group, $this->userId);
            }
        } else {
            $this->userKodebpr = null;
            $this->userGroups = array_fill_keys(['pe', 'admin', 'dekom', 'direksi'], false);
        }

        $this->periodeId = session('active_periode');
    }

    /**
     * Initialize risk models configuration
     */
    private function initializeRiskModels()
    {
        $this->riskModelsConfig = [
            'kredit' => [
                'name' => 'Risiko Kredit',
                'link' => 'Risikokredit',
                'inheren' => ['model' => new M_nilairisikokredit(), 'faktor' => 13],
                'kpmr' => ['model' => new M_risikokreditkpmr(), 'faktor' => 33],
                'beluminheren' => ['model' => new M_nilairisikokredit(), 'faktor' => 14],
                'belumkpmr' => ['model' => new M_risikokreditkpmr(), 'faktor' => 34],
                'result_key' => 'nkredit',
                'result_belumkey' => 'nbelumkredit',
                'kesimpulan_key' => 'kesimpulan1'
            ],
            'operasional' => [
                'name' => 'Risiko Operasional',
                'link' => 'Risikooperasional',
                'inheren' => ['model' => new M_risikooperasional(), 'faktor' => 48],
                'kpmr' => ['model' => new M_risikooperasionalkpmr(), 'faktor' => 70],
                'beluminheren' => ['model' => new M_risikooperasional(), 'faktor' => 49],
                'belumkpmr' => ['model' => new M_risikooperasionalkpmr(), 'faktor' => 71],
                'result_key' => 'noperasional',
                'result_belumkey' => 'nbelumoperasional',
                'kesimpulan_key' => 'kesimpulan2'
            ],
            'kepatuhan' => [
                'name' => 'Risiko Kepatuhan',
                'link' => 'Risikokepatuhan',
                'inheren' => ['model' => new M_risikokepatuhan(), 'faktor' => 81],
                'kpmr' => ['model' => new M_kepatuhankpmr(), 'faktor' => 102],
                'beluminheren' => ['model' => new M_risikokepatuhan(), 'faktor' => 82],
                'belumkpmr' => ['model' => new M_kepatuhankpmr(), 'faktor' => 103],
                'result_key' => 'nkepatuhan',
                'result_belumkey' => 'nbelumkepatuhan',
                'kesimpulan_key' => 'kesimpulan3'
            ],
            'likuiditas' => [
                'name' => 'Risiko Likuiditas',
                'link' => 'Likuiditasinheren',
                'inheren' => ['model' => new M_likuiditasinheren(), 'faktor' => 115],
                'kpmr' => ['model' => new M_likuiditaskpmr(), 'faktor' => 135],
                'beluminheren' => ['model' => new M_likuiditasinheren(), 'faktor' => 116],
                'belumkpmr' => ['model' => new M_likuiditaskpmr(), 'faktor' => 136],
                'result_key' => 'nlikuiditas',
                'result_belumkey' => 'nbelumlikuiditas',
                'kesimpulan_key' => 'kesimpulan4'
            ],
            'reputasi' => [
                'name' => 'Risiko Reputasi',
                'link' => 'Reputasiinheren',
                'inheren' => ['model' => new M_reputasiinheren(), 'faktor' => 148],
                'kpmr' => ['model' => new M_reputasikpmr(), 'faktor' => 168],
                'beluminheren' => ['model' => new M_reputasiinheren(), 'faktor' => 149],
                'belumkpmr' => ['model' => new M_reputasikpmr(), 'faktor' => 169],
                'result_key' => 'nreputasi',
                'result_belumkey' => 'nbelumreputasi',
                'kesimpulan_key' => 'kesimpulan5'
            ],
            'stratejik' => [
                'name' => 'Risiko Stratejik',
                'link' => 'Stratejikinheren',
                'inheren' => ['model' => new M_stratejikinheren(), 'faktor' => 179],
                'kpmr' => ['model' => new M_stratejikkpmr(), 'faktor' => 199],
                'beluminheren' => ['model' => new M_stratejikinheren(), 'faktor' => 180],
                'belumkpmr' => ['model' => new M_stratejikkpmr(), 'faktor' => 200],
                'result_key' => 'nstratejik',
                'result_belumkey' => 'nbelumstratejik',
                'kesimpulan_key' => 'kesimpulan6'
            ],
        ];
    }

    private function checkAuth()
    {
        if (!$this->auth->check()) {
            session()->set('redirect_url', current_url());
            return redirect()->to('/login');
        }
        return null;
    }

    private function checkPeriode()
    {
        if (!$this->periodeId) {
            return redirect()->to('/Periodeprofilresiko')
                ->with('error', 'Silakan pilih periode aktif terlebih dahulu.');
        }
        return null;
    }

    public function index()
    {
        // Quick validation
        if ($redirect = $this->checkAuth())
            return $redirect;
        if ($redirect = $this->checkPeriode())
            return $redirect;

        if (!$this->userKodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid.');
        }

        // Get data
        $periodeDetail = $this->periodeModel->find($this->periodeId);
        $bprData = $this->infobprModel->getBprByKode($this->userKodebpr);
        $namaBpr = preg_replace('/[^A-Za-z0-9\-]/', '_', $bprData['namabpr']);

        $riskCalculations = $this->calculateAllRiskValues($this->userKodebpr, $this->periodeId);
        $riskbelumCalculations = $this->calculatebelumAllRiskValues($this->userKodebpr, $this->periodeId);

        $dataToSave = array_merge(
            [
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId,
                'tahun' => $periodeDetail['tahun'] ?? null,
            ],
            $this->extractCalculationResults($riskCalculations),
            $this->extractCalculationbelumResults($riskbelumCalculations)
        );


        // Save to database

        $this->showprofilresikoModel->simpanProfilRisiko($dataToSave);

        // Get risk factors for display
        $riskFactors = $this->getRiskFactorsForDisplay($this->userKodebpr, $this->periodeId, $riskCalculations);
        $risbelumkFactors = $this->getbelumRiskFactorsForDisplay($this->userKodebpr, $this->periodeId, $riskbelumCalculations);

        // Get saved data
        $showprofilresiko = $this->showprofilresikoModel->getByKodebprAndPeriode($this->userKodebpr, $this->periodeId);

        // Prepare view data
        $data = [
            'judul' => 'Laporan Profil Risiko',
            'bprData' => $bprData,
            'periodeDetail' => $periodeDetail,
            'showprofilresiko' => $showprofilresiko,
            'namabpr' => $namaBpr,
            'factors' => $riskFactors,
            'belumfactors' => $risbelumkFactors,
            'riskCalculations' => $riskCalculations,
            'riskbelumCalculations' => $riskbelumCalculations,
            'transparan' => [],
            'nilaiData' => [],
            'allShowProfilresiko' => [],
            'kategori' => $periodeDetail['kategori'] ?? null,
        ];

        return view('templates/v_header', $data)
            . view('templates/v_sidebar')
            . view('templates/v_topbar')
            . view('Showprofilresiko/index', $data)
            . view('templates/v_footer');
    }

    private function extractCalculationResults($calculations)
    {
        return [
            'nkredit' => $calculations['nkredit'],
            'kesimpulan1' => $calculations['kesimpulan1'],
            'noperasional' => $calculations['noperasional'],
            'kesimpulan2' => $calculations['kesimpulan2'],
            'nkepatuhan' => $calculations['nkepatuhan'],
            'kesimpulan3' => $calculations['kesimpulan3'],
            'nlikuiditas' => $calculations['nlikuiditas'],
            'kesimpulan4' => $calculations['kesimpulan4'],
            'nreputasi' => $calculations['nreputasi'],
            'kesimpulan5' => $calculations['kesimpulan5'],
            'nstratejik' => $calculations['nstratejik'],
            'kesimpulan6' => $calculations['kesimpulan6'],
            'ntotalrisk' => $calculations['ntotalrisk'],
        ];
    }

    private function extractCalculationbelumResults($belumcalculations)
    {
        return [
            'nbelumkredit' => $belumcalculations['nbelumkredit'],
            'kesimpulan1' => $belumcalculations['kesimpulan1'],
            'nbelumoperasional' => $belumcalculations['nbelumoperasional'],
            'kesimpulan2' => $belumcalculations['kesimpulan2'],
            'nbelumkepatuhan' => $belumcalculations['nbelumkepatuhan'],
            'kesimpulan3' => $belumcalculations['kesimpulan3'],
            'nbelumlikuiditas' => $belumcalculations['nbelumlikuiditas'],
            'kesimpulan4' => $belumcalculations['kesimpulan4'],
            'nbelumreputasi' => $belumcalculations['nbelumreputasi'],
            'kesimpulan5' => $belumcalculations['kesimpulan5'],
            'nbelumstratejik' => $belumcalculations['nbelumstratejik'],
            'kesimpulan6' => $belumcalculations['kesimpulan6'],
            // 'nbelumtotalrisk' => $belumcalculations['nbelumtotalrisk'],
            'nbelumtotalrisk' => $belumcalculations['nbelumtotalrisk'] ?? '',
        ];
    }

    /**
     * Get risk matrix for calculation
     */
    private function getRiskMatrix($inheren, $kpmr)
    {
        $matrix = [
            '1-1' => ['nilai' => 1, 'kesimpulan' => 'Tingkat Risiko Inheren pada tingkat Sangat Rendah dan Tingkat Kualitas Penerapan Manajemen Risiko Sangat Memadai'],
            '1-2' => ['nilai' => 1, 'kesimpulan' => 'Tingkat Risiko Inheren pada tingkat Sangat Rendah dan Tingkat Kualitas Penerapan Manajemen Risiko Memadai'],
            '1-3' => ['nilai' => 1, 'kesimpulan' => 'Tingkat Risiko Inheren pada tingkat Sangat Rendah dan Tingkat Kualitas Penerapan Manajemen Risiko Cukup Memadai'],
            '1-4' => ['nilai' => 1, 'kesimpulan' => 'Tingkat Risiko Inheren pada tingkat Sangat Rendah dan Tingkat Kualitas Penerapan Manajemen Risiko Kurang Memadai'],
            '1-5' => ['nilai' => 1, 'kesimpulan' => 'Kaji ulang terbatas, rencana tindak dilaporkan semesteran'],
            '2-1' => ['nilai' => 1, 'kesimpulan' => 'Tingkat Risiko Inheren pada tingkat Sangat Rendah dan Tingkat Kualitas Penerapan Manajemen Risiko Sangat Memadai'],
            '2-2' => ['nilai' => 2, 'kesimpulan' => 'Tingkat Risiko Inheren pada tingkat Sangat Rendah dan Tingkat Kualitas Penerapan Manajemen Risiko Memadai'],
            '2-3' => ['nilai' => 2, 'kesimpulan' => 'Tingkat Risiko Inheren pada tingkat Sangat Rendah dan Tingkat Kualitas Penerapan Manajemen Risiko Cukup Memadai'],
            '2-4' => ['nilai' => 2, 'kesimpulan' => 'Kaji ulang terbatas, rencana tindak dilaporkan semesteran'],
            '2-5' => ['nilai' => 2, 'kesimpulan' => 'Kaji ulang terbatas, rencana tindak dilaporkan triwulanan'],
            '3-1' => ['nilai' => 2, 'kesimpulan' => 'Tingkat Risiko Inheren pada tingkat Sedang dan Tingkat Kualitas Penerapan Manajemen Risiko Sangat Memadai'],
            '3-2' => ['nilai' => 2, 'kesimpulan' => 'Tingkat Risiko Inheren pada tingkat Sedang dan Tingkat Kualitas Penerapan Manajemen Risiko Memadai'],
            '3-3' => ['nilai' => 3, 'kesimpulan' => 'Kaji ulang terbatas, rencana tindak dilaporkan semesteran'],
            '3-4' => ['nilai' => 3, 'kesimpulan' => 'Kaji ulang terbatas, rencana tindak dilaporkan triwulanan'],
            '3-5' => ['nilai' => 3, 'kesimpulan' => 'Kaji ulang menyeluruh, rencana tindak dilaporkan triwulanan'],
            '4-1' => ['nilai' => 2, 'kesimpulan' => 'Tingkat Risiko Inheren pada tingkat Tinggidan Tingkat Kualitas Penerapan Manajemen Risiko Sangat Memadai'],
            '4-2' => ['nilai' => 3, 'kesimpulan' => 'Kaji ulang terbatas, rencana tindak dilaporkan triwulanan'],
            '4-3' => ['nilai' => 4, 'kesimpulan' => 'Kaji ulang terbatas, rencana tindak dilaporkan triwulanan'],
            '4-4' => ['nilai' => 4, 'kesimpulan' => 'Kaji ulang menyeluruh, rencana tindak dilaporkan triwulanan'],
            '4-5' => ['nilai' => 4, 'kesimpulan' => 'Kaji ulang menyeluruh, rencana tindak dilaporkan bulanan'],
            '5-1' => ['nilai' => 3, 'kesimpulan' => 'Kaji ulang terbatas, rencana tindak dilaporkan triwulanan'],
            '5-2' => ['nilai' => 3, 'kesimpulan' => 'Kaji ulang terbatas, rencana tindak dilaporkan bulanan'],
            '5-3' => ['nilai' => 4, 'kesimpulan' => 'Kaji ulang terbatas, rencana tindak dilaporkan bulanan'],
            '5-4' => ['nilai' => 5, 'kesimpulan' => 'Kaji ulang menyeluruh, rencana tindak dilaporkan bulanan'],
            '5-5' => ['nilai' => 5, 'kesimpulan' => 'Pengawasan melekat, membutuhkan pemantauan secara lebih mendalam'],
        ];

        return $matrix[$inheren . '-' . $kpmr] ?? ['nilai' => null, 'kesimpulan' => ''];
    }

    /**
     * Calculate all risk values with matrix conditions
     */
    private function calculateAllRiskValues($kodebpr, $periodeId, $force = false)
    {
        $results = [
            'nkredit' => null,
            'kesimpulan1' => null,
            'noperasional' => null,
            'kesimpulan2' => null,
            'nkepatuhan' => null,
            'kesimpulan3' => null,
            'nlikuiditas' => null,
            'kesimpulan4' => null,
            'nreputasi' => null,
            'kesimpulan5' => null,
            'nstratejik' => null,
            'kesimpulan6' => null,
            'ntotalrisk' => null,
        ];

        // Hitung masing-masing risiko
        foreach ($this->riskModelsConfig as $config) {
            $nilaiInheren = $this->getNilaiRisiko(
                $config['inheren']['model'],
                $config['inheren']['faktor'],
                $kodebpr,
                $periodeId
            );

            $nilaiKpmr = $this->getNilaiRisiko(
                $config['kpmr']['model'],
                $config['kpmr']['faktor'],
                $kodebpr,
                $periodeId
            );

            // Hitung menggunakan matrix
            if (is_numeric($nilaiInheren) && is_numeric($nilaiKpmr)) {
                $matrixResult = $this->getRiskMatrix($nilaiInheren, $nilaiKpmr);
                $results[$config['result_key']] = $matrixResult['nilai'];
                $results[$config['kesimpulan_key']] = $matrixResult['kesimpulan'];
            }
        }

        // Ambil data lama dari database untuk perbandingan
        $existing = $this->showprofilresikoModel->getByKodebprAndPeriode($kodebpr, $periodeId);

        // Cek perubahan pada nilai risiko utama
        $riskKeys = ['nkredit', 'noperasional', 'nkepatuhan', 'nlikuiditas', 'nreputasi', 'nstratejik'];
        $changed = false;

        foreach ($riskKeys as $key) {
            if (!isset($existing[$key]) || $existing[$key] != $results[$key]) {
                $changed = true;
                break;
            }
        }

        // 🔥 Jalankan hitung total risk jika ada perubahan ATAU mode paksa aktif
        if ($changed || $force) {
            $results['ntotalrisk'] = $this->calculateTotalRisk($results);
        } else {
            // Gunakan nilai lama agar tidak overwrite
            $results['ntotalrisk'] = $existing['ntotalrisk'] ?? null;
        }

        return $results;
    }

    private function calculatebelumAllRiskValues($kodebpr, $periodeId)
    {
        $resultsbelum = [
            'nbelumkredit' => '',
            'kesimpulan1' => '',
            'nbelumoperasional' => '',
            'kesimpulan2' => '',
            'nbelumkepatuhan' => '',
            'kesimpulan3' => '',
            'nbelumlikuiditas' => '',
            'kesimpulan4' => '',
            'nbelumreputasi' => '',
            'kesimpulan5' => '',
            'nbelumstratejik' => '',
            'kesimpulan6' => '',
            'nbelumtotalrisk' => '',
        ];

        foreach ($this->riskModelsConfig as $config) {
            $nilaiInheren = $this->getNilaiRisiko(
                $config['beluminheren']['model'], // Gunakan beluminheren
                $config['beluminheren']['faktor'],
                $kodebpr,
                $periodeId
            );

            $nilaiKpmr = $this->getNilaiRisiko(
                $config['belumkpmr']['model'], // Gunakan belumkpmr
                $config['belumkpmr']['faktor'],
                $kodebpr,
                $periodeId
            );

            // Calculate using matrix if both values exist
            if (is_numeric($nilaiInheren) && is_numeric($nilaiKpmr)) {
                $matrixResult = $this->getRiskMatrix($nilaiInheren, $nilaiKpmr);
                $resultsbelum[$config['result_belumkey']] = $matrixResult['nilai'];
                $resultsbelum[$config['kesimpulan_key']] = $matrixResult['kesimpulan'];
            }
        }

        // Calculate nbelumtotalrisk
        $resultsbelum['nbelumtotalrisk'] = $this->calculatebelumTotalRisk($resultsbelum);

        return $resultsbelum;
    }

    private function getNilaiRisiko($model, $faktorId, $kodebpr, $periodeId)
    {
        $data = $model->where('faktor1id', $faktorId)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        return $data['penilaiankredit'] ?? null;
    }

    private function calculateTotalRisk($results)
    {
        // Get periode detail untuk kategori
        $periodeDetail = $this->periodeModel->find($this->periodeId);
        $kategori = $periodeDetail['kategori'] ?? null;
        $isKategoriB = strtoupper($kategori) === 'B';

        // Base risks yang selalu dihitung
        $riskKeys = ['nkredit', 'noperasional', 'nkepatuhan', 'nlikuiditas'];

        // Tambahkan reputasi dan stratejik hanya jika kategori B
        if ($isKategoriB) {
            $riskKeys[] = 'nreputasi';
            $riskKeys[] = 'nstratejik';
        }

        $sum = 0;
        $count = 0;

        foreach ($riskKeys as $risk) {
            if (is_numeric($results[$risk]) && $results[$risk] !== null) {
                $sum += $results[$risk];
                $count++;
            }
        }

        if ($count > 0) {
            $average = $sum / $count;
            return ($average - floor($average) >= 0.5) ? ceil($average) : floor($average);
        }

        return null;
    }

    // Ganti method calculatebelumTotalRisk dengan ini:
    private function calculatebelumTotalRisk($resultsbelum)
    {
        // Get periode detail untuk kategori
        $periodeDetail = $this->periodeModel->find($this->periodeId);
        $kategori = $periodeDetail['kategori'] ?? null;
        $isKategoriB = strtoupper($kategori) === 'B';

        // Base risks yang selalu dihitung
        $riskbelumKeys = ['nbelumkredit', 'nbelumoperasional', 'nbelumkepatuhan', 'nbelumlikuiditas'];

        // Tambahkan reputasi dan stratejik hanya jika kategori B
        if ($isKategoriB) {
            $riskbelumKeys[] = 'nbelumreputasi';
            $riskbelumKeys[] = 'nbelumstratejik';
        }

        $sum = 0;
        $count = 0;

        foreach ($riskbelumKeys as $riskbelum) {
            if (is_numeric($resultsbelum[$riskbelum]) && $resultsbelum[$riskbelum] !== null) {
                $sum += $resultsbelum[$riskbelum];
                $count++;
            }
        }

        if ($count > 0) {
            $average = $sum / $count;
            return ($average - floor($average) >= 0.5) ? ceil($average) : floor($average);
        }

        return null;
    }

    private function getRiskFactorsForDisplay($kodebpr, $periodeId, $riskCalculations)
    {
        $factors = [];

        foreach ($this->riskModelsConfig as $key => $config) {
            $inherenData = $config['inheren']['model']
                ->where('faktor1id', $config['inheren']['faktor'])
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->first();

            // Get KPMR data
            $kpmrData = $config['kpmr']['model']
                ->where('faktor1id', $config['kpmr']['faktor'])
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->first();

            $factors[] = [
                'name' => $config['name'],
                'link' => base_url($config['link']),
                'nfaktor' => $inherenData['penilaiankredit'] ?? 'N/A',
                'nfaktor_kpmr' => $kpmrData['penilaiankredit'] ?? 'N/A',
                'nkredit' => $riskCalculations[$config['result_key']] ?? 'N/A', // Gunakan dynamic key
                'kesimpulan' => $riskCalculations[$config['kesimpulan_key']] ?? '',
                'accdekom' => $inherenData['accdekom'] ?? null,
                'is_approved' => $inherenData['is_approved'] ?? null,
                'risk_type' => $key, // Untuk identifier jenis risiko
            ];
        }

        return $factors;
    }

    public function updateNtotalrisk()
    {
        // Validasi auth dan periode
        if ($redirect = $this->checkAuth())
            return $redirect;
        if ($redirect = $this->checkPeriode())
            return $redirect;

        // Validasi request method
        if ($this->request->getMethod() !== 'post') {
            return redirect()->back()->with('err', 'Method tidak valid');
        }

        // Get POST data
        $id = $this->request->getPost('id');
        $ntotalrisk = $this->request->getPost('ntotalrisk');

        // Validasi input
        if (empty($id)) {
            return redirect()->back()->with('err', 'ID tidak ditemukan');
        }

        if (!is_numeric($ntotalrisk) || $ntotalrisk < 1 || $ntotalrisk > 5) {
            return redirect()->back()->with('err', 'Nilai total risiko harus antara 1-5');
        }

        // Update data
        try {
            $data = [
                'ntotalrisk' => (int) $ntotalrisk,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $this->showprofilresikoModel->update($id, $data);

            return redirect()->to(base_url('Showprofilresiko'))
                ->with('message', 'Nilai Total Risiko berhasil diupdate menjadi ' . $ntotalrisk);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('err', 'Gagal update data: ' . $e->getMessage());
        }
    }

    /**
     * Reset ntotalrisk to calculated value
     */
    public function resetNtotalrisk()
    {
        // Validasi auth dan periode
        if ($redirect = $this->checkAuth())
            return $redirect;
        if ($redirect = $this->checkPeriode())
            return $redirect;

        $id = $this->request->getPost('id');

        if (empty($id)) {
            return redirect()->back()->with('err', 'ID tidak ditemukan');
        }

        try {
            // Get existing data
            $existingData = $this->showprofilresikoModel->find($id);

            if (!$existingData) {
                return redirect()->back()->with('err', 'Data tidak ditemukan');
            }

            // Recalculate ntotalrisk
            $riskCalculations = $this->calculateAllRiskValues(
                $existingData['kodebpr'],
                $existingData['periode_id'],
                true
            );

            $data = [
                'ntotalrisk' => $riskCalculations['ntotalrisk'],
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $this->showprofilresikoModel->update($id, $data);

            return redirect()->to(base_url('Showprofilresiko'))
                ->with('message', 'Nilai Total Risiko berhasil direset ke perhitungan otomatis: ' . $riskCalculations['ntotalrisk']);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('err', 'Gagal reset data: ' . $e->getMessage());
        }
    }

    private function getbelumRiskFactorsForDisplay($kodebpr, $periodeId, $riskbelumCalculations)
    {
        $factors = [];

        foreach ($this->riskModelsConfig as $key => $config) {
            $inherenData = $config['beluminheren']['model']
                ->where('faktor1id', $config['beluminheren']['faktor'])
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->first();

            // Get KPMR data
            $kpmrData = $config['belumkpmr']['model']
                ->where('faktor1id', $config['belumkpmr']['faktor'])
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->first();

            $factors[] = [
                'name' => $config['name'],
                'link' => base_url($config['link']),
                'nbelumfaktor' => $inherenData['penilaiankredit'] ?? 'N/A',
                'nbelumfaktor_kpmr' => $kpmrData['penilaiankredit'] ?? 'N/A',
                'nbelumtingkatrisk' => $riskbelumCalculations[$config['result_belumkey']] ?? 'N/A', // Gunakan dynamic key
                'kesimpulan' => $riskbelumCalculations[$config['kesimpulan_key']] ?? '',
                'accdekom' => $inherenData['accdekom'] ?? null,
                'is_approved' => $inherenData['is_approved'] ?? null,
                'risk_type' => $key,
            ];
        }

        return $factors;
    }

    public function update()
    {
        $id = $this->request->getPost('id');
        $data = [
            'kesimpulan' => $this->request->getPost('kesimpulan'),
            'positifstruktur' => $this->request->getPost('positifstruktur'),
            'positifproses' => $this->request->getPost('positifproses'),
            'positifhasil' => $this->request->getPost('positifhasil'),
            'negatifstruktur' => $this->request->getPost('negatifstruktur'),
            'negatifproses' => $this->request->getPost('negatifproses'),
            'negatifhasil' => $this->request->getPost('negatifhasil')
        ];

        $this->showprofilresikoModel->update($id, $data);
        return redirect()->to(base_url('Showprofilresiko'))->with('message', 'Data berhasil diupdate!');
    }

    public function updatettd()
    {
        $id = $this->request->getPost('id');
        $data = [
            'dirut' => $this->request->getPost('dirut'),
            'dirkep' => $this->request->getPost('dirkep'),
            'pe' => $this->request->getPost('pe'),
            'tanggal' => $this->request->getPost('tanggal'),
            'lokasi' => $this->request->getPost('lokasi')
        ];

        $this->showprofilresikoModel->update($id, $data);
        return redirect()->to(base_url('Showprofilresiko'))->with('message', 'Data berhasil diupdate!');
    }

    public function updatecover()
    {
        $id = $this->request->getPost('id');

        if (empty($id)) {
            return redirect()->to(base_url('Showprofilresiko'))->with('error', 'ID tidak ditemukan!');
        }

        $data = ['cover' => $this->request->getPost('cover')];
        $this->showprofilresikoModel->update($id, $data);

        return redirect()->to(base_url('Showprofilresiko'))->with('message', 'Data berhasil diupdate!');
    }

    public function exporttxtlapprofrisk()
    {
        // Authentication check
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        // Get parameters
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        // Get periode detail
        $periodeDetail = $this->periodeModel->find($this->periodeId);
        $modalinti = $periodeDetail['modalinti'];
        $totalaset = $periodeDetail['totalaset'];
        $kantorcabang = $periodeDetail['kantorcabang'];
        $atmdebit = $periodeDetail['atmdebit'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];
        $kategori = $periodeDetail['kategori'];
        $exportDate = date('Y-m-d');
        $titleDate = date('Ymd');

        // Fetch data
        $data_showprofresiko = $this->showprofilresikoModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        // BPR Info
        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'] ?? '';
            $kodejenis = $infobpr['kodejenis'] ?? '';
        }

        $profilResiko = $this->showprofilresikoModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $output = "";

        // Header
        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|PRBPRKS|0000|{$modalinti}|{$totalaset}|{$kantorcabang}|{$atmdebit}|{$kategori}||\r\n";

        usort($data_showprofresiko, function ($a, $b) {
            return ($a['faktor1id'] ?? 0) <=> ($b['faktor1id'] ?? 0);
        });

        $kreditInheren = $this->kreditinherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 13)
            ->first();

        $kreditKpmr = $this->kreditkpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 33)
            ->first();

        $kreditbelumInheren = $this->kreditinherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 14)
            ->first();

        $kreditbelumKpmr = $this->kreditkpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 34)
            ->first();

        $inherenkredit = isset($kreditInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $kreditInheren['penilaiankredit']) : '';
        $kpmrkredit = isset($kreditKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $kreditKpmr['penilaiankredit']) : '';
        $inherenbelumkredit = isset($kreditbelumInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $kreditbelumInheren['penilaiankredit']) : '';
        $kpmrbelumkredit = isset($kreditbelumKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $kreditbelumKpmr['penilaiankredit']) : '';
        $nkredit = isset($profilResiko['nkredit']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nkredit']) : '';
        $nbelumkredit = isset($profilResiko['nbelumkredit']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nbelumkredit']) : '';

        $output .= "D01|0001|{$inherenkredit}|{$kpmrkredit}|{$nkredit}|{$inherenbelumkredit}|{$kpmrbelumkredit}|{$nbelumkredit}\r\n";

        // Risiko Operasional
        $operasionalInheren = $this->operasionalinherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 48)
            ->first();

        $operasionalKpmr = $this->operasionalkpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 70)
            ->first();

        $operasionalbelumInheren = $this->operasionalinherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 49)
            ->first();

        $operasionalbelumKpmr = $this->operasionalkpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 71)
            ->first();

        $inherenOperasional = isset($operasionalInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $operasionalInheren['penilaiankredit']) : '';
        $kpmrOperasional = isset($operasionalKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $operasionalKpmr['penilaiankredit']) : '';
        $inherenbelumOperasional = isset($operasionalbelumInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $operasionalbelumInheren['penilaiankredit']) : '';
        $kpmrbelumOperasional = isset($operasionalbelumKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $operasionalbelumKpmr['penilaiankredit']) : '';
        $nOperasional = isset($profilResiko['noperasional']) ? str_replace(["\r", "\n"], ' ', $profilResiko['noperasional']) : '';
        $nbelumOperasional = isset($profilResiko['nbelumoperasional']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nbelumoperasional']) : '';

        $output .= "D01|0002|{$inherenOperasional}|{$kpmrOperasional}|{$nOperasional}|{$inherenbelumOperasional}|{$kpmrbelumOperasional}|{$nbelumOperasional}\r\n";

        // Risiko Kepatuhan
        $kepatuhanInheren = $this->kepatuhaninherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 81)
            ->first();

        $kepatuhanKpmr = $this->kepatuhankpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 102)
            ->first();

        $kepatuhanbelumInheren = $this->kepatuhaninherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 82)
            ->first();

        $kepatuhanbelumKpmr = $this->kepatuhankpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 103)
            ->first();

        $inherenKepatuhan = isset($kepatuhanInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $kepatuhanInheren['penilaiankredit']) : '';
        $kpmrKepatuhan = isset($kepatuhanKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $kepatuhanKpmr['penilaiankredit']) : '';
        $inherenbelumKepatuhan = isset($kepatuhanbelumInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $kepatuhanbelumInheren['penilaiankredit']) : '';
        $kpmrbelumKepatuhan = isset($kepatuhanbelumKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $kepatuhanbelumKpmr['penilaiankredit']) : '';
        $nKepatuhan = isset($profilResiko['nkepatuhan']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nkepatuhan']) : '';
        $nbelumKepatuhan = isset($profilResiko['nbelumkepatuhan']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nbelumkepatuhan']) : '';

        $output .= "D01|0003|{$inherenKepatuhan}|{$kpmrKepatuhan}|{$nKepatuhan}|{$inherenbelumKepatuhan}|{$kpmrbelumKepatuhan}|{$nbelumKepatuhan}\r\n";


        // Risiko Likuiditas
        $likuiditasInheren = $this->likuiditasinherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 115)
            ->first();

        $likuiditasKpmr = $this->likuiditaskpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 135)
            ->first();

        $likuiditasbelumInheren = $this->likuiditasinherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 116)
            ->first();

        $likuiditasbelumKpmr = $this->likuiditaskpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 136)
            ->first();

        $inherenLikuiditas = isset($likuiditasInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $likuiditasInheren['penilaiankredit']) : '';
        $kpmrLikuiditas = isset($likuiditasKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $likuiditasKpmr['penilaiankredit']) : '';
        $inherenLikuiditas = isset($likuiditasbelumInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $likuiditasbelumInheren['penilaiankredit']) : '';
        $kpmrLikuiditas = isset($likuiditasbelumKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $likuiditasbelumKpmr['penilaiankredit']) : '';
        $nLikuiditas = isset($profilResiko['nlikuiditas']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nlikuiditas']) : '';
        $nbelumLikuiditas = isset($profilResiko['nbelumlikuiditas']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nbelumlikuiditas']) : '';

        $output .= "D01|0004|{$inherenLikuiditas}|{$kpmrLikuiditas}|{$nLikuiditas}|{$inherenLikuiditas}|{$kpmrLikuiditas}|{$nbelumLikuiditas}\r\n";


        // Risiko Reputasi
        $reputasiInheren = $this->reputasiinherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 148)
            ->first();

        $reputasiKpmr = $this->reputasikpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 168)
            ->first();

        $reputasibelumInheren = $this->reputasiinherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 149)
            ->first();

        $reputasibelumKpmr = $this->reputasikpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 169)
            ->first();

        $inherenReputasi = isset($reputasiInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $reputasiInheren['penilaiankredit']) : '';
        $kpmrReputasi = isset($reputasiKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $reputasiKpmr['penilaiankredit']) : '';
        $inherenbelumReputasi = isset($reputasibelumInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $reputasibelumInheren['penilaiankredit']) : '';
        $kpmrbelumReputasi = isset($reputasibelumKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $reputasibelumKpmr['penilaiankredit']) : '';
        $nReputasi = isset($profilResiko['nreputasi']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nreputasi']) : '';
        $nbelumReputasi = isset($profilResiko['nbelumreputasi']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nbelumreputasi']) : '';

        $output .= "D01|0005|{$inherenReputasi}|{$kpmrReputasi}|{$nReputasi}|{$inherenbelumReputasi}|{$kpmrbelumReputasi}|{$nbelumReputasi}\r\n";

        // Risiko Stratejik
        $stratejikInheren = $this->stratejikinherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 179)
            ->first();

        $stratejikKpmr = $this->stratejikkpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 199)
            ->first();

        $stratejikbelumInheren = $this->stratejikinherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 180)
            ->first();

        $stratejikbelumKpmr = $this->stratejikkpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 200)
            ->first();

        $inherenStratejik = isset($stratejikInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $stratejikInheren['penilaiankredit']) : '';
        $kpmrStratejik = isset($stratejikKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $stratejikKpmr['penilaiankredit']) : '';
        $inherenbelumStratejik = isset($stratejikbelumInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $stratejikbelumInheren['penilaiankredit']) : '';
        $kpmrbelumStratejik = isset($stratejikbelumKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $stratejikbelumKpmr['penilaiankredit']) : '';
        $nStratejik = isset($profilResiko['nstratejik']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nstratejik']) : '';
        $nbelumStratejik = isset($profilResiko['nbelumstratejik']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nbelumstratejik']) : '';
        $nTotalrisk = isset($profilResiko['ntotalrisk']) ? str_replace(["\r", "\n"], ' ', $profilResiko['ntotalrisk']) : '';
        $nbelumTotalrisk = isset($profilResiko['nbelumtotalrisk']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nbelumtotalrisk']) : '';
        $output .= "D01|0006|{$inherenStratejik}|{$kpmrStratejik}|{$nStratejik}|{$inherenbelumStratejik}|{$kpmrbelumStratejik}|{$nbelumStratejik}\r\n";
        $output .= "D01|0000|||{$nStratejik}|||{$nbelumStratejik}\r\n";

        // Filename
        $filename = "PRBPRKS-0000-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        // Response
        $response = service('response');
        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response->setBody($output);
    }

    public function exporttxtriskinheren()
    {
        // Authentication check
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        // Get parameters
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        // Get periode detail
        $periodeDetail = $this->periodeModel->find($this->periodeId);
        $modalinti = $periodeDetail['modalinti'];
        $totalaset = $periodeDetail['totalaset'];
        $kantorcabang = $periodeDetail['kantorcabang'];
        $atmdebit = $periodeDetail['atmdebit'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];
        $kategori = $periodeDetail['kategori'];
        $exportDate = date('Y-m-d');
        $titleDate = date('Ymd');

        // Fetch data
        $data_showprofresiko = $this->showprofilresikoModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        // BPR Info
        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'] ?? '';
            $kodejenis = $infobpr['kodejenis'] ?? '';
        }

        $profilResiko = $this->showprofilresikoModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $output = "";

        // Header
        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|PRBPRKS|0100|{$modalinti}|{$totalaset}|{$kantorcabang}|{$atmdebit}|{$kategori}||\r\n";

        usort($data_showprofresiko, function ($a, $b) {
            return ($a['faktor1id'] ?? 0) <=> ($b['faktor1id'] ?? 0);
        });

        $kreditInheren = $this->kreditinherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 13)
            ->first();

        $kreditKpmr = $this->kreditkpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 33)
            ->first();

        $inherenkredit = isset($kreditInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $kreditInheren['penilaiankredit']) : '';
        $keterangankredit = isset($kreditInheren['keterangan']) ? str_replace(["\r", "\n"], ' ', $kreditInheren['keterangan']) : '';
        $kpmrkredit = isset($kreditKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $kreditKpmr['penilaiankredit']) : '';
        $keterangankpmr = isset($kreditKpmr['keterangan']) ? str_replace(["\r", "\n"], ' ', $kreditKpmr['keterangan']) : '';
        $nkredit = isset($profilResiko['nkredit']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nkredit']) : '';
        $kesimpulan1 = isset($profilResiko['kesimpulan1']) ? str_replace(["\r", "\n"], ' ', $profilResiko['kesimpulan1']) : '';

        $output .= "D01|1100|{$nkredit}|{$kesimpulan1}\r\n";
        $output .= "D01|1200|{$inherenkredit}|{$keterangankredit}\r\n";
        $output .= "D01|1300|{$kpmrkredit}|{$keterangankpmr}\r\n";

        // Filename
        $filename = "PRBPRKS-0100-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        // Response
        $response = service('response');
        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response->setBody($output);
    }

    public function exporttxtoperasional()
    {
        // Authentication check
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        // Get parameters
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        // Get periode detail
        $periodeDetail = $this->periodeModel->find($this->periodeId);
        $modalinti = $periodeDetail['modalinti'];
        $totalaset = $periodeDetail['totalaset'];
        $kantorcabang = $periodeDetail['kantorcabang'];
        $atmdebit = $periodeDetail['atmdebit'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];
        $kategori = $periodeDetail['kategori'];
        $exportDate = date('Y-m-d');
        $titleDate = date('Ymd');

        // Fetch data
        $data_showprofresiko = $this->showprofilresikoModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        // BPR Info
        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'] ?? '';
            $kodejenis = $infobpr['kodejenis'] ?? '';
        }

        $profilResiko = $this->showprofilresikoModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $output = "";

        // Header
        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|PRBPRKS|0200|{$modalinti}|{$totalaset}|{$kantorcabang}|{$atmdebit}|{$kategori}||\r\n";

        usort($data_showprofresiko, function ($a, $b) {
            return ($a['faktor1id'] ?? 0) <=> ($b['faktor1id'] ?? 0);
        });

        $operasionalInheren = $this->operasionalinherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 48)
            ->first();

        $operasionalKpmr = $this->operasionalkpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 70)
            ->first();

        $inherenoperasional = isset($operasionalInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $operasionalInheren['penilaiankredit']) : '';
        $keterangankredit = isset($operasionalInheren['keterangan']) ? str_replace(["\r", "\n"], ' ', $operasionalInheren['keterangan']) : '';
        $kpmroperasional = isset($operasionalKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $operasionalKpmr['penilaiankredit']) : '';
        $keterangankpmr = isset($operasionalKpmr['keterangan']) ? str_replace(["\r", "\n"], ' ', $operasionalKpmr['keterangan']) : '';
        $noperasional = isset($profilResiko['noperasional']) ? str_replace(["\r", "\n"], ' ', $profilResiko['noperasional']) : '';
        $kesimpulan2 = isset($profilResiko['kesimpulan2']) ? str_replace(["\r", "\n"], ' ', $profilResiko['kesimpulan2']) : '';

        $output .= "D01|2100|{$noperasional}|{$kesimpulan2}\r\n";
        $output .= "D01|2200|{$inherenoperasional}|{$keterangankredit}\r\n";
        $output .= "D01|2300|{$kpmroperasional}|{$keterangankpmr}\r\n";

        // Filename
        $filename = "PRBPRKS-0200-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        // Response
        $response = service('response');
        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response->setBody($output);
    }

    public function exporttxtkepatuhan()
    {
        // Authentication check
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        // Get parameters
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        // Get periode detail
        $periodeDetail = $this->periodeModel->find($this->periodeId);
        $modalinti = $periodeDetail['modalinti'];
        $totalaset = $periodeDetail['totalaset'];
        $kantorcabang = $periodeDetail['kantorcabang'];
        $atmdebit = $periodeDetail['atmdebit'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];
        $kategori = $periodeDetail['kategori'];
        $exportDate = date('Y-m-d');
        $titleDate = date('Ymd');

        // Fetch data
        $data_showprofresiko = $this->showprofilresikoModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        // BPR Info
        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'] ?? '';
            $kodejenis = $infobpr['kodejenis'] ?? '';
        }

        $profilResiko = $this->showprofilresikoModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $output = "";

        // Header
        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|PRBPRKS|0300|{$modalinti}|{$totalaset}|{$kantorcabang}|{$atmdebit}|{$kategori}||\r\n";

        usort($data_showprofresiko, function ($a, $b) {
            return ($a['faktor1id'] ?? 0) <=> ($b['faktor1id'] ?? 0);
        });

        $kepatuhanInheren = $this->kepatuhaninherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 81)
            ->first();

        $kepatuhanKpmr = $this->kepatuhankpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 102)
            ->first();

        $inherenkepatuhan = isset($kepatuhanInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $kepatuhanInheren['penilaiankredit']) : '';
        $keterangankpmr = isset($operasionalInheren['keterangan']) ? str_replace(["\r", "\n"], ' ', $kepatuhanInheren['keterangan']) : '';
        $kpmrkepatuhan = isset($kepatuhanKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $kepatuhanKpmr['penilaiankredit']) : '';
        $keterangankpmr = isset($kepatuhanKpmr['keterangan']) ? str_replace(["\r", "\n"], ' ', $kepatuhanKpmr['keterangan']) : '';
        $nkepatuhan = isset($profilResiko['nkepatuhan']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nkepatuhan']) : '';
        $kesimpulan3 = isset($profilResiko['kesimpulan3']) ? str_replace(["\r", "\n"], ' ', $profilResiko['kesimpulan3']) : '';

        $output .= "D01|3100|{$nkepatuhan}|{$kesimpulan3}\r\n";
        $output .= "D01|3200|{$inherenkepatuhan}|{$keterangankpmr}\r\n";
        $output .= "D01|3300|{$kpmrkepatuhan}|{$keterangankpmr}\r\n";

        // Filename
        $filename = "PRBPRKS-0300-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        // Response
        $response = service('response');
        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response->setBody($output);
    }

    public function exporttxtlikuiditas()
    {
        // Authentication check
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        // Get parameters
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        // Get periode detail
        $periodeDetail = $this->periodeModel->find($this->periodeId);
        $modalinti = $periodeDetail['modalinti'];
        $totalaset = $periodeDetail['totalaset'];
        $kantorcabang = $periodeDetail['kantorcabang'];
        $atmdebit = $periodeDetail['atmdebit'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];
        $kategori = $periodeDetail['kategori'];
        $exportDate = date('Y-m-d');
        $titleDate = date('Ymd');

        // Fetch data
        $data_showprofresiko = $this->showprofilresikoModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        // BPR Info
        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'] ?? '';
            $kodejenis = $infobpr['kodejenis'] ?? '';
        }

        $profilResiko = $this->showprofilresikoModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $output = "";

        // Header
        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|PRBPRKS|0400|{$modalinti}|{$totalaset}|{$kantorcabang}|{$atmdebit}|{$kategori}||\r\n";

        usort($data_showprofresiko, function ($a, $b) {
            return ($a['faktor1id'] ?? 0) <=> ($b['faktor1id'] ?? 0);
        });

        $likuiditasInheren = $this->likuiditasinherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 114)
            ->first();

        $likuiditasKpmr = $this->likuiditaskpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 135)
            ->first();

        $inherenlikuiditas = isset($likuiditasInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $likuiditasInheren['penilaiankredit']) : '';
        $keterangankpmr = isset($operasionalInheren['keterangan']) ? str_replace(["\r", "\n"], ' ', $likuiditasInheren['keterangan']) : '';
        $kpmrlikuiditas = isset($likuiditasKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $likuiditasKpmr['penilaiankredit']) : '';
        $keterangankpmr = isset($likuiditasKpmr['keterangan']) ? str_replace(["\r", "\n"], ' ', $likuiditasKpmr['keterangan']) : '';
        $nlikuiditas = isset($profilResiko['nlikuiditas']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nlikuiditas']) : '';
        $kesimpulan4 = isset($profilResiko['kesimpulan4']) ? str_replace(["\r", "\n"], ' ', $profilResiko['kesimpulan4']) : '';

        $output .= "D01|4100|{$nlikuiditas}|{$kesimpulan4}\r\n";
        $output .= "D01|4200|{$inherenlikuiditas}|{$keterangankpmr}\r\n";
        $output .= "D01|4300|{$kpmrlikuiditas}|{$keterangankpmr}\r\n";

        // Filename
        $filename = "PRBPRKS-0400-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        // Response
        $response = service('response');
        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response->setBody($output);
    }

    public function exporttxtreputasi()
    {
        // Authentication check
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        // Get parameters
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        // Get periode detail
        $periodeDetail = $this->periodeModel->find($this->periodeId);
        $modalinti = $periodeDetail['modalinti'];
        $totalaset = $periodeDetail['totalaset'];
        $kantorcabang = $periodeDetail['kantorcabang'];
        $atmdebit = $periodeDetail['atmdebit'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];
        $kategori = $periodeDetail['kategori'];
        $exportDate = date('Y-m-d');
        $titleDate = date('Ymd');

        // Fetch data
        $data_showprofresiko = $this->showprofilresikoModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        // BPR Info
        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'] ?? '';
            $kodejenis = $infobpr['kodejenis'] ?? '';
        }

        $profilResiko = $this->showprofilresikoModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $output = "";

        // Header
        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|PRBPRKS|0500|{$modalinti}|{$totalaset}|{$kantorcabang}|{$atmdebit}|{$kategori}||\r\n";

        usort($data_showprofresiko, function ($a, $b) {
            return ($a['faktor1id'] ?? 0) <=> ($b['faktor1id'] ?? 0);
        });

        $reputasiInheren = $this->reputasiinherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 148)
            ->first();

        $reputasiKpmr = $this->reputasikpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 168)
            ->first();

        $inherenreputasi = isset($reputasiInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $reputasiInheren['penilaiankredit']) : '';
        $keterangankpmr = isset($operasionalInheren['keterangan']) ? str_replace(["\r", "\n"], ' ', $reputasiInheren['keterangan']) : '';
        $kpmrreputasi = isset($reputasiKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $reputasiKpmr['penilaiankredit']) : '';
        $keterangankpmr = isset($reputasiKpmr['keterangan']) ? str_replace(["\r", "\n"], ' ', $reputasiKpmr['keterangan']) : '';
        $nreputasi = isset($profilResiko['nreputasi']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nreputasi']) : '';
        $kesimpulan5 = isset($profilResiko['kesimpulan5']) ? str_replace(["\r", "\n"], ' ', $profilResiko['kesimpulan5']) : '';

        $output .= "D01|5100|{$nreputasi}|{$kesimpulan5}\r\n";
        $output .= "D01|5200|{$inherenreputasi}|{$keterangankpmr}\r\n";
        $output .= "D01|5300|{$kpmrreputasi}|{$keterangankpmr}\r\n";

        // Filename
        $filename = "PRBPRKS-0500-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        // Response
        $response = service('response');
        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response->setBody($output);
    }

    public function exporttxtstratejik()
    {
        // Authentication check
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        // Get parameters
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        // Get periode detail
        $periodeDetail = $this->periodeModel->find($this->periodeId);
        $modalinti = $periodeDetail['modalinti'];
        $totalaset = $periodeDetail['totalaset'];
        $kantorcabang = $periodeDetail['kantorcabang'];
        $atmdebit = $periodeDetail['atmdebit'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];
        $kategori = $periodeDetail['kategori'];
        $exportDate = date('Y-m-d');
        $titleDate = date('Ymd');

        // Fetch data
        $data_showprofresiko = $this->showprofilresikoModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        // BPR Info
        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'] ?? '';
            $kodejenis = $infobpr['kodejenis'] ?? '';
        }

        $profilResiko = $this->showprofilresikoModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $output = "";

        // Header
        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|PRBPRKS|0600|{$modalinti}|{$totalaset}|{$kantorcabang}|{$atmdebit}|{$kategori}||\r\n";

        usort($data_showprofresiko, function ($a, $b) {
            return ($a['faktor1id'] ?? 0) <=> ($b['faktor1id'] ?? 0);
        });

        $stratejikInheren = $this->stratejikinherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 179)
            ->first();

        $stratejikKpmr = $this->stratejikkpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 199)
            ->first();

        $inherenstratejik = isset($stratejikInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $stratejikInheren['penilaiankredit']) : '';
        $keterangankpmr = isset($operasionalInheren['keterangan']) ? str_replace(["\r", "\n"], ' ', $stratejikInheren['keterangan']) : '';
        $kpmrstratejik = isset($stratejikKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $stratejikKpmr['penilaiankredit']) : '';
        $keterangankpmr = isset($stratejikKpmr['keterangan']) ? str_replace(["\r", "\n"], ' ', $stratejikKpmr['keterangan']) : '';
        $nstratejik = isset($profilResiko['nstratejik']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nstratejik']) : '';
        $kesimpulan6 = isset($profilResiko['kesimpulan6']) ? str_replace(["\r", "\n"], ' ', $profilResiko['kesimpulan6']) : '';

        $output .= "D01|6100|{$nstratejik}|{$kesimpulan6}\r\n";
        $output .= "D01|6200|{$inherenstratejik}|{$keterangankpmr}\r\n";
        $output .= "D01|6300|{$kpmrstratejik}|{$keterangankpmr}\r\n";

        // Filename
        $filename = "PRBPRKS-0600-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        // Response
        $response = service('response');
        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response->setBody($output);
    }

    public function exportAllToZip()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;

        $bprData = $this->infobprModel->getBprByKode($this->userKodebpr);
        $namaBpr = preg_replace('/[^A-Za-z0-9\-]/', '', $bprData['namabpr']);

        $zipFileName = 'APOLO-NBPSIMPEL-LAPORANPROFILRISIKO-' . $namaBpr . "-" . date('Y-m-d') . '.zip';
        $zipFilePath = WRITEPATH . 'uploads/' . $zipFileName;

        $zip = new \ZipArchive();

        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
            return redirect()->back()->with('error', 'Gagal membuat file ZIP');
        }

        // Add files to ZIP
        $exports = [
            ['Showprofilresiko', 'exporttxtlapprofrisk'],
            ['Showprofilresiko', 'exporttxtriskinheren'],
            ['Risikokredit', 'exporttxtrisikokredit'],
            ['Risikokreditkpmr', 'exporttxtrisikokreditkpmr'],
            ['Showprofilresiko', 'exporttxtoperasional'],
            ['Risikooperasional', 'exporttxtrisikooperasional'],
            ['Risikooperasionalkpmr', 'exporttxtrisikooperasionalkpmr'],
            ['Showprofilresiko', 'exporttxtkepatuhan'],
            ['Risikokepatuhan', 'exporttxtrisikokepatuhan'],
            ['Kepatuhankpmr', 'exporttxtrisikokepatuhankpmr'],
            ['Showprofilresiko', 'exporttxtlikuiditas'],
            ['Likuiditasinheren', 'exporttxtrisikolikuiditas'],
            ['Likuiditaskpmr', 'exporttxtrisikolikuiditaskpmr'],
            ['Showprofilresiko', 'exporttxtreputasi'],
            ['Reputasiinheren', 'exporttxtrisikoreputasiinheren'],
            ['Reputasikpmr', 'exporttxtrisikoreputasikpmr'],
            ['Stratejikinheren', 'exporttxtrisikostratejikinheren'],
            ['Showprofilresiko', 'exporttxtstratejik'],
            ['Stratejikinheren', 'exporttxtrisikostratejikkpmr'],
        ];

        foreach ($exports as [$controller, $method]) {
            $this->addTxtToZip($controller, $method, $zip);
        }

        $zip->close();

        // Send file
        return $this->response
            ->setHeader('Content-Type', 'application/zip')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $zipFileName . '"')
            ->setHeader('Content-Length', filesize($zipFilePath))
            ->setBody(file_get_contents($zipFilePath))
            ->send();
    }

    private function addTxtToZip(string $controllerName, string $methodName, \ZipArchive &$zip)
    {
        $controllerClassName = 'App\Controllers\\' . ucfirst($controllerName);

        if (!class_exists($controllerClassName)) {
            log_message('warning', "Controller '$controllerName' tidak ditemukan");
            return;
        }

        $controllerInstance = new $controllerClassName();

        if (!method_exists($controllerInstance, $methodName)) {
            log_message('warning', "Method '$methodName' tidak ditemukan di controller '$controllerName'");
            return;
        }

        $response = $controllerInstance->$methodName();

        if (!$response instanceof \CodeIgniter\HTTP\Response) {
            log_message('warning', "Method '$methodName' di controller '$controllerName' tidak mengembalikan Response yang valid");
            return;
        }

        $txtContent = $response->getBody();
        $fileNameInZip = $controllerName . '.txt';

        // Get filename from content disposition if exists
        $contentDisposition = $response->getHeaderLine('Content-Disposition');
        if (preg_match('/filename="([^"]+)"/', $contentDisposition, $matches)) {
            $fileNameInZip = $matches[1];
        }

        $zip->addFromString($fileNameInZip, $txtContent);
    }
}