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

        $this->initializeUser();

        $this->initializeRiskModels();
    }

    private function initializeUser()
    {
        if ($this->auth->isLoggedIn()) {
            $this->userId = $this->auth->id();
            $user = $this->userModel->find($this->userId);
            $this->userKodebpr = $user['kodebpr'] ?? null;

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



        $this->showprofilresikoModel->simpanProfilRisiko($dataToSave);

        $riskFactors = $this->getRiskFactorsForDisplay($this->userKodebpr, $this->periodeId, $riskCalculations);
        $risbelumkFactors = $this->getbelumRiskFactorsForDisplay($this->userKodebpr, $this->periodeId, $riskbelumCalculations);
        $showprofilresiko = $this->showprofilresikoModel->getByKodebprAndPeriode($this->userKodebpr, $this->periodeId);

        if ($showprofilresiko && !empty($showprofilresiko['tanggal'])) {
            $showprofilresiko['tanggal_display'] = $this->formatDateForDisplay($showprofilresiko['tanggal']);
        } else {
            $showprofilresiko['tanggal_display'] = '';
        }

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
            . view('showprofilresiko/index', $data)
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
            'nbelumtotalrisk' => $belumcalculations['nbelumtotalrisk'],
        ];
    }

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

            if (is_numeric($nilaiInheren) && is_numeric($nilaiKpmr)) {
                $matrixResult = $this->getRiskMatrix($nilaiInheren, $nilaiKpmr);
                $results[$config['result_key']] = $matrixResult['nilai'];
                $results[$config['kesimpulan_key']] = $matrixResult['kesimpulan'];
            }
        }

        $existing = $this->showprofilresikoModel->getByKodebprAndPeriode($kodebpr, $periodeId);

        $riskKeys = ['nkredit', 'noperasional', 'nkepatuhan', 'nlikuiditas', 'nreputasi', 'nstratejik'];
        $changed = false;

        foreach ($riskKeys as $key) {
            if (!isset($existing[$key]) || $existing[$key] != $results[$key]) {
                $changed = true;
                break;
            }
        }

        if ($changed || $force) {
            $results['ntotalrisk'] = $this->calculateTotalRisk($results);
        } else {
            $results['ntotalrisk'] = $existing['ntotalrisk'] ?? null;
        }

        return $results;
    }

    private function calculatebelumAllRiskValues($kodebpr, $periodeId)
    {
        $resultsbelum = [
            'nbelumkredit' => null,
            'kesimpulan1' => null,
            'nbelumoperasional' => null,
            'kesimpulan2' => null,
            'nbelumkepatuhan' => null,
            'kesimpulan3' => null,
            'nbelumlikuiditas' => null,
            'kesimpulan4' => null,
            'nbelumreputasi' => null,
            'kesimpulan5' => null,
            'nbelumstratejik' => null,
            'kesimpulan6' => null,
            'nbelumtotalrisk' => null,
        ];

        foreach ($this->riskModelsConfig as $config) {
            $nilaiInheren = $this->getNilaiRisiko(
                $config['beluminheren']['model'],
                $config['beluminheren']['faktor'],
                $kodebpr,
                $periodeId
            );

            $nilaiKpmr = $this->getNilaiRisiko(
                $config['belumkpmr']['model'],
                $config['belumkpmr']['faktor'],
                $kodebpr,
                $periodeId
            );

            if (is_numeric($nilaiInheren) && is_numeric($nilaiKpmr)) {
                $matrixResult = $this->getRiskMatrix($nilaiInheren, $nilaiKpmr);
                $resultsbelum[$config['result_belumkey']] = $matrixResult['nilai'];
                $resultsbelum[$config['kesimpulan_key']] = $matrixResult['kesimpulan'];
            }
        }

        $resultsbelum['nbelumtotalrisk'] = $this->calculatebelumTotalRisk($resultsbelum);

        return $resultsbelum;
    }

    private function formatDateForDisplay($date)
    {
        if (empty($date) || $date === '0000-00-00') {
            return '';
        }

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
            return $date;
        }

        $dateObj = \DateTime::createFromFormat('Y-m-d', $date);
        return $dateObj ? $dateObj->format('d/m/Y') : '';
    }

    private function convertDateFormat($date)
    {
        if (empty($date)) {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }

        $dateObj = \DateTime::createFromFormat('d/m/Y', $date);

        if ($dateObj === false) {
            $dateObj = \DateTime::createFromFormat('Y-m-d', $date);
        }

        return $dateObj ? $dateObj->format('Y-m-d') : null;
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
        $periodeDetail = $this->periodeModel->find($this->periodeId);
        $kategori = $periodeDetail['kategori'] ?? null;
        $isKategoriA = strtoupper($kategori) === 'A';

        $riskKeys = ['nkredit', 'noperasional', 'nkepatuhan', 'nlikuiditas'];

        if ($isKategoriA) {
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

    private function calculatebelumTotalRisk($resultsbelum)
    {
        $periodeDetail = $this->periodeModel->find($this->periodeId);
        $kategori = $periodeDetail['kategori'] ?? null;
        $isKategoriA = strtoupper($kategori) === 'A';

        $riskbelumKeys = ['nbelumkredit', 'nbelumoperasional', 'nbelumkepatuhan', 'nbelumlikuiditas'];

        if ($isKategoriA) {
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
                'risk_type' => $key,
            ];
        }

        return $factors;
    }

    public function updateNtotalrisk()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;
        if ($redirect = $this->checkPeriode())
            return $redirect;

        if ($this->request->getMethod() !== 'post') {
            return redirect()->back()->with('err', 'Method tidak valid');
        }

        $id = $this->request->getPost('id');
        $ntotalrisk = $this->request->getPost('ntotalrisk');

        if (empty($id)) {
            return redirect()->back()->with('err', 'ID tidak ditemukan');
        }

        if (!is_numeric($ntotalrisk) || $ntotalrisk < 1 || $ntotalrisk > 5) {
            return redirect()->back()->with('err', 'Nilai total risiko harus antara 1-5');
        }

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

    public function resetNtotalrisk()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;
        if ($redirect = $this->checkPeriode())
            return $redirect;

        $id = $this->request->getPost('id');

        if (empty($id)) {
            return redirect()->back()->with('err', 'ID tidak ditemukan');
        }

        try {
            $existingData = $this->showprofilresikoModel->find($id);

            if (!$existingData) {
                return redirect()->back()->with('err', 'Data tidak ditemukan');
            }

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
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        // Get parameters
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        // Get periode detail
        $profilResiko = $this->showprofilresikoModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $output = "";
        $periodeDetail = $this->periodeModel->find($this->periodeId);
        $modalinti = $periodeDetail['modalinti'];
        $totalaset = $periodeDetail['totalaset'];
        $kantorcabang = $periodeDetail['kantorcabang'];
        $atmdebit = $periodeDetail['atmdebit'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];
        $kategori = $periodeDetail['kategori'];
        $tanggal = new \DateTime($profilResiko['tanggal']);

        $titleDate = $tanggal->format('Ymd');
        $exportDate = $tanggal->format('Y-m-d');
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

        $inherenkredit = isset($kreditInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $kreditInheren['penilaiankredit']) : '0';
        $kpmrkredit = isset($kreditKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $kreditKpmr['penilaiankredit']) : '0';
        $inherenbelumkredit = isset($kreditbelumInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $kreditbelumInheren['penilaiankredit']) : '0';
        $kpmrbelumkredit = isset($kreditbelumKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $kreditbelumKpmr['penilaiankredit']) : '0';
        $nkredit = isset($profilResiko['nkredit']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nkredit']) : '0';
        $nbelumkredit = isset($profilResiko['nbelumkredit']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nbelumkredit']) : '0';

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

        $inherenOperasional = isset($operasionalInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $operasionalInheren['penilaiankredit']) : '0';
        $kpmrOperasional = isset($operasionalKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $operasionalKpmr['penilaiankredit']) : '0';
        $inherenbelumOperasional = isset($operasionalbelumInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $operasionalbelumInheren['penilaiankredit']) : '0';
        $kpmrbelumOperasional = isset($operasionalbelumKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $operasionalbelumKpmr['penilaiankredit']) : '0';
        $nOperasional = isset($profilResiko['noperasional']) ? str_replace(["\r", "\n"], ' ', $profilResiko['noperasional']) : '0';
        $nbelumOperasional = isset($profilResiko['nbelumoperasional']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nbelumoperasional']) : '0';

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

        $inherenKepatuhan = isset($kepatuhanInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $kepatuhanInheren['penilaiankredit']) : '0';
        $kpmrKepatuhan = isset($kepatuhanKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $kepatuhanKpmr['penilaiankredit']) : '0';
        $inherenbelumKepatuhan = isset($kepatuhanbelumInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $kepatuhanbelumInheren['penilaiankredit']) : '0';
        $kpmrbelumKepatuhan = isset($kepatuhanbelumKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $kepatuhanbelumKpmr['penilaiankredit']) : '0';
        $nKepatuhan = isset($profilResiko['nkepatuhan']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nkepatuhan']) : '0';
        $nbelumKepatuhan = isset($profilResiko['nbelumkepatuhan']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nbelumkepatuhan']) : '0';

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

        $inherenLikuiditas = isset($likuiditasInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $likuiditasInheren['penilaiankredit']) : '0';
        $kpmrLikuiditas = isset($likuiditasKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $likuiditasKpmr['penilaiankredit']) : '0';
        $inherenLikuiditas = isset($likuiditasbelumInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $likuiditasbelumInheren['penilaiankredit']) : '0';
        $kpmrLikuiditas = isset($likuiditasbelumKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $likuiditasbelumKpmr['penilaiankredit']) : '0';
        $nLikuiditas = isset($profilResiko['nlikuiditas']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nlikuiditas']) : '0';
        $nbelumLikuiditas = isset($profilResiko['nbelumlikuiditas']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nbelumlikuiditas']) : '0';

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

        $inherenReputasi = isset($reputasiInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $reputasiInheren['penilaiankredit']) : '0';
        $kpmrReputasi = isset($reputasiKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $reputasiKpmr['penilaiankredit']) : '0';
        $inherenbelumReputasi = isset($reputasibelumInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $reputasibelumInheren['penilaiankredit']) : '0';
        $kpmrbelumReputasi = isset($reputasibelumKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $reputasibelumKpmr['penilaiankredit']) : '0';
        $nReputasi = isset($profilResiko['nreputasi']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nreputasi']) : '0';
        $nbelumReputasi = isset($profilResiko['nbelumreputasi']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nbelumreputasi']) : '0';

        $output .= "D01|0005|{$inherenReputasi}|{$kpmrReputasi}|{$nReputasi}|{$inherenbelumReputasi}|{$kpmrbelumReputasi}|{$nbelumReputasi}\r\n";

        // $output .= "D01|0005|0|0|0|0|0|0\r\n";

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

        $operasionalbelumKpmr = $this->operasionalkpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 71)
            ->first();

        $inherenStratejik = isset($stratejikInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $stratejikInheren['penilaiankredit']) : '0';
        $kpmrStratejik = isset($stratejikKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $stratejikKpmr['penilaiankredit']) : '0';
        $inherenbelumStratejik = isset($stratejikbelumInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $stratejikbelumInheren['penilaiankredit']) : '0';
        $kpmrbelumStratejik = isset($stratejikbelumKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $stratejikbelumKpmr['penilaiankredit']) : '0';
        $nStratejik = isset($profilResiko['nstratejik']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nstratejik']) : '0';
        $nbelumStratejik = isset($profilResiko['nbelumstratejik']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nbelumstratejik']) : '0';
        $nTotalrisk = isset($profilResiko['ntotalrisk']) ? str_replace(["\r", "\n"], ' ', $profilResiko['ntotalrisk']) : '0';
        $nbelumTotalrisk = isset($profilResiko['nbelumtotalrisk']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nbelumtotalrisk']) : '0';
        // $kesimpulan = isset($profilResiko['kesimpulan']) ? str_replace(["\r", "\n"], ' ', $profilResiko['kesimpulan']) : ' ';

        function sanitizeText($text)
        {
            // ganti enter
            $text = str_replace(["\r", "\n"], ' ', $text);

            // ganti karakter tipografi ke ASCII
            $text = str_replace(
                ['“', '”', '‘', '’', '%', '|'],
                ['"', '"', "'", "'", ' persen ', ' '],
                $text
            );

            // hilangkan karakter non-ASCII
            $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);

            // rapikan spasi
            return trim(preg_replace('/\s+/', ' ', $text));
        }

        $kesimpulan = isset($profilResiko['kesimpulan']) ? sanitizeText($profilResiko['kesimpulan']) : ' ';

        $kpmrbelumOperasional = isset($operasionalbelumKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $operasionalbelumKpmr['penilaiankredit']) : '0';

        $output .= "D01|0006|{$inherenStratejik}|{$kpmrStratejik}|{$nStratejik}|{$inherenbelumStratejik}|{$kpmrbelumStratejik}|{$nbelumStratejik}\r\n";

        $output .= "D01|0000|||{$nTotalrisk}|||{$nbelumTotalrisk}\r\n";
        $output .= "F01|{$kesimpulan}\r\n";

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
        $profilResiko = $this->showprofilresikoModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $output = "";
        $periodeDetail = $this->periodeModel->find($this->periodeId);
        $modalinti = $periodeDetail['modalinti'];
        $totalaset = $periodeDetail['totalaset'];
        $kantorcabang = $periodeDetail['kantorcabang'];
        $atmdebit = $periodeDetail['atmdebit'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];
        $kategori = $periodeDetail['kategori'];
        $tanggal = new \DateTime($profilResiko['tanggal']);

        $titleDate = $tanggal->format('Ymd');
        $exportDate = $tanggal->format('Y-m-d');

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
        $profilResiko = $this->showprofilresikoModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $output = "";
        $periodeDetail = $this->periodeModel->find($this->periodeId);
        $modalinti = $periodeDetail['modalinti'];
        $totalaset = $periodeDetail['totalaset'];
        $kantorcabang = $periodeDetail['kantorcabang'];
        $atmdebit = $periodeDetail['atmdebit'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];
        $kategori = $periodeDetail['kategori'];
        $tanggal = new \DateTime($profilResiko['tanggal']);

        $titleDate = $tanggal->format('Ymd');
        $exportDate = $tanggal->format('Y-m-d');
        // $exportDate = date('Y-m-d');
        // $titleDate = date('Ymd');

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
        $profilResiko = $this->showprofilresikoModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $output = "";
        $periodeDetail = $this->periodeModel->find($this->periodeId);
        $modalinti = $periodeDetail['modalinti'];
        $totalaset = $periodeDetail['totalaset'];
        $kantorcabang = $periodeDetail['kantorcabang'];
        $atmdebit = $periodeDetail['atmdebit'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];
        $kategori = $periodeDetail['kategori'];
        $tanggal = new \DateTime($profilResiko['tanggal']);

        $titleDate = $tanggal->format('Ymd');
        $exportDate = $tanggal->format('Y-m-d');

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
        $profilResiko = $this->showprofilresikoModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $output = "";
        $periodeDetail = $this->periodeModel->find($this->periodeId);
        $modalinti = $periodeDetail['modalinti'];
        $totalaset = $periodeDetail['totalaset'];
        $kantorcabang = $periodeDetail['kantorcabang'];
        $atmdebit = $periodeDetail['atmdebit'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];
        $kategori = $periodeDetail['kategori'];
        $tanggal = new \DateTime($profilResiko['tanggal']);

        $titleDate = $tanggal->format('Ymd');
        $exportDate = $tanggal->format('Y-m-d');

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
            ->where('faktor1id', 115)
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

    // public function exporttxtreputasi()
    // {
    //     // Authentication check
    //     if (!$this->auth->check()) {
    //         $redirectURL = session('redirect_url') ?? '/login';
    //         unset($_SESSION['redirect_url']);
    //         return redirect()->to($redirectURL);
    //     }

    //     // Get parameters
    //     $kodebpr = $this->userKodebpr;
    //     $periodeId = session('active_periode');

    //     // Get periode detail
    //     $profilResiko = $this->showprofilresikoModel
    //         ->where('kodebpr', $kodebpr)
    //         ->where('periode_id', $periodeId)
    //         ->first();

    //     $output = "";
    //     $periodeDetail = $this->periodeModel->find($this->periodeId);
    //     $modalinti = $periodeDetail['modalinti'];
    //     $totalaset = $periodeDetail['totalaset'];
    //     $kantorcabang = $periodeDetail['kantorcabang'];
    //     $atmdebit = $periodeDetail['atmdebit'];
    //     $jenispelaporan = $periodeDetail['jenispelaporan'];
    //     $kategori = $periodeDetail['kategori'];
    //     $tanggal = new \DateTime($profilResiko['tanggal']);

    //     $titleDate = $tanggal->format('Ymd');
    //     $exportDate = $tanggal->format('Y-m-d');

    //     // Fetch data
    //     $data_showprofresiko = $this->showprofilresikoModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
    //     $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

    //     // BPR Info
    //     $sandibpr = '';
    //     $kodejenis = '';
    //     if (!empty($data_infobpr)) {
    //         $infobpr = $data_infobpr[0];
    //         $sandibpr = $infobpr['sandibpr'] ?? '';
    //         $kodejenis = $infobpr['kodejenis'] ?? '';
    //     }

    //     $profilResiko = $this->showprofilresikoModel
    //         ->where('kodebpr', $kodebpr)
    //         ->where('periode_id', $periodeId)
    //         ->first();

    //     $output = "";

    //     // Header
    //     $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|PRBPRKS|0500|{$modalinti}|{$totalaset}|{$kantorcabang}|{$atmdebit}|{$kategori}||\r\n";

    //     usort($data_showprofresiko, function ($a, $b) {
    //         return ($a['faktor1id'] ?? 0) <=> ($b['faktor1id'] ?? 0);
    //     });

    //     $reputasiInheren = $this->reputasiinherenModel
    //         ->where('kodebpr', $kodebpr)
    //         ->where('periode_id', $periodeId)
    //         ->where('faktor1id', 148)
    //         ->first();

    //     $reputasiKpmr = $this->reputasikpmrModel
    //         ->where('kodebpr', $kodebpr)
    //         ->where('periode_id', $periodeId)
    //         ->where('faktor1id', 168)
    //         ->first();

    //     $inherenreputasi = isset($reputasiInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $reputasiInheren['penilaiankredit']) : '0';
    //     $keterangankpmr = isset($operasionalInheren['keterangan']) ? str_replace(["\r", "\n"], ' ', $reputasiInheren['keterangan']) : '0';
    //     $kpmrreputasi = isset($reputasiKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $reputasiKpmr['penilaiankredit']) : '0';
    //     $keterangankpmr = isset($reputasiKpmr['keterangan']) ? str_replace(["\r", "\n"], ' ', $reputasiKpmr['keterangan']) : '0';
    //     $nreputasi = isset($profilResiko['nreputasi']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nreputasi']) : '0';
    //     $kesimpulan5 = isset($profilResiko['kesimpulan5']) ? str_replace(["\r", "\n"], ' ', $profilResiko['kesimpulan5']) : '';

    //     $output .= "D01|5100|{$nreputasi}|{$kesimpulan5}\r\n";
    //     $output .= "D01|5200|{$inherenreputasi}|{$keterangankpmr}\r\n";
    //     $output .= "D01|5300|{$kpmrreputasi}|{$keterangankpmr}\r\n";

    //     // Filename
    //     $filename = "PRBPRKS-0500-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

    //     // Response
    //     $response = service('response');
    //     $response->setHeader('Content-Type', 'text/plain');
    //     $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

    //     return $response->setBody($output);
    // }

    public function exporttxtreputasi()
    {
        // Authentication check
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        $profilResiko = $this->showprofilresikoModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $periodeDetail = $this->periodeModel->find($this->periodeId);

        $modalinti = $periodeDetail['modalinti'];
        $totalaset = $periodeDetail['totalaset'];
        $kantorcabang = $periodeDetail['kantorcabang'];
        $atmdebit = $periodeDetail['atmdebit'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];
        $kategori = $periodeDetail['kategori'];

        $tanggal = new \DateTime($profilResiko['tanggal']);
        $titleDate = $tanggal->format('Ymd');
        $exportDate = $tanggal->format('Y-m-d');

        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'] ?? '';
            $kodejenis = $infobpr['kodejenis'] ?? '';
        }

        // ===============================
        // Sanitasi teks (samakan pola)
        // ===============================
        function sanitizeTxtReputasi($text)
        {
            $text = str_replace(["\r", "\n"], ' ', $text);
            $text = str_replace(
                ['“', '”', '‘', '’', '%', '|'],
                ['"', '"', "'", "'", ' persen ', ' '],
                $text
            );
            $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
            return trim(preg_replace('/\s+/', ' ', $text));
        }

        $output = "";

        // ===============================
        // Header
        // ===============================
        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|PRBPRKS|0500|{$modalinti}|{$totalaset}|{$kantorcabang}|{$atmdebit}|{$kategori}||\r\n";

        // ===============================
        // Ambil data reputasi
        // ===============================
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

        // ===============================
        // Sanitasi data
        // ===============================
        $nreputasi = sanitizeTxtReputasi($profilResiko['nreputasi'] ?? '');
        $kesimpulan5 = sanitizeTxtReputasi($profilResiko['kesimpulan5'] ?? '');

        $inherenreputasi = sanitizeTxtReputasi($reputasiInheren['penilaiankredit'] ?? '');
        $ketInheren = sanitizeTxtReputasi($reputasiInheren['keterangan'] ?? '');

        $kpmrreputasi = sanitizeTxtReputasi($reputasiKpmr['penilaiankredit'] ?? '');
        $ketKpmr = sanitizeTxtReputasi($reputasiKpmr['keterangan'] ?? '');

        // ===============================
        // D01 hanya jika ADA DATA
        // ===============================
        if ($nreputasi !== '' || $kesimpulan5 !== '') {
            $output .= "D01|5100|{$nreputasi}|{$kesimpulan5}\r\n";
        }

        if ($inherenreputasi !== '' || $ketInheren !== '') {
            $output .= "D01|5200|{$inherenreputasi}|{$ketInheren}\r\n";
        }

        if ($kpmrreputasi !== '' || $ketKpmr !== '') {
            $output .= "D01|5300|{$kpmrreputasi}|{$ketKpmr}\r\n";
        }

        // ===============================
        // Output response
        // ===============================
        $filename = "PRBPRKS-0500-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        $response = service('response');
        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response->setBody($output);
    }

    // public function exporttxtstratejik()
    // {
    //     // Authentication check
    //     if (!$this->auth->check()) {
    //         $redirectURL = session('redirect_url') ?? '/login';
    //         unset($_SESSION['redirect_url']);
    //         return redirect()->to($redirectURL);
    //     }

    //     // Get parameters
    //     $kodebpr = $this->userKodebpr;
    //     $periodeId = session('active_periode');

    //     // Get periode detail
    //     $profilResiko = $this->showprofilresikoModel
    //         ->where('kodebpr', $kodebpr)
    //         ->where('periode_id', $periodeId)
    //         ->first();

    //     $output = "";
    //     $periodeDetail = $this->periodeModel->find($this->periodeId);
    //     $modalinti = $periodeDetail['modalinti'];
    //     $totalaset = $periodeDetail['totalaset'];
    //     $kantorcabang = $periodeDetail['kantorcabang'];
    //     $atmdebit = $periodeDetail['atmdebit'];
    //     $jenispelaporan = $periodeDetail['jenispelaporan'];
    //     $kategori = $periodeDetail['kategori'];
    //     $tanggal = new \DateTime($profilResiko['tanggal']);

    //     $titleDate = $tanggal->format('Ymd');
    //     $exportDate = $tanggal->format('Y-m-d');

    //     // Fetch data
    //     $data_showprofresiko = $this->showprofilresikoModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
    //     $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

    //     // BPR Info
    //     $sandibpr = '';
    //     $kodejenis = '';
    //     if (!empty($data_infobpr)) {
    //         $infobpr = $data_infobpr[0];
    //         $sandibpr = $infobpr['sandibpr'] ?? '';
    //         $kodejenis = $infobpr['kodejenis'] ?? '';
    //     }

    //     $profilResiko = $this->showprofilresikoModel
    //         ->where('kodebpr', $kodebpr)
    //         ->where('periode_id', $periodeId)
    //         ->first();

    //     $output = "";

    //     // Header
    //     $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|PRBPRKS|0600|{$modalinti}|{$totalaset}|{$kantorcabang}|{$atmdebit}|{$kategori}||\r\n";

    //     usort($data_showprofresiko, function ($a, $b) {
    //         return ($a['faktor1id'] ?? 0) <=> ($b['faktor1id'] ?? 0);
    //     });

    //     $stratejikInheren = $this->stratejikinherenModel
    //         ->where('kodebpr', $kodebpr)
    //         ->where('periode_id', $periodeId)
    //         ->where('faktor1id', 179)
    //         ->first();

    //     $stratejikKpmr = $this->stratejikkpmrModel
    //         ->where('kodebpr', $kodebpr)
    //         ->where('periode_id', $periodeId)
    //         ->where('faktor1id', 199)
    //         ->first();

    //     $inherenstratejik = isset($stratejikInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $stratejikInheren['penilaiankredit']) : '0';
    //     $keterangankpmr = isset($operasionalInheren['keterangan']) ? str_replace(["\r", "\n"], ' ', $stratejikInheren['keterangan']) : '0';
    //     $kpmrstratejik = isset($stratejikKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $stratejikKpmr['penilaiankredit']) : '0';
    //     $keterangankpmr = isset($stratejikKpmr['keterangan']) ? str_replace(["\r", "\n"], ' ', $stratejikKpmr['keterangan']) : '0';
    //     $nstratejik = isset($profilResiko['nstratejik']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nstratejik']) : '0';
    //     $kesimpulan6 = isset($profilResiko['kesimpulan6']) ? str_replace(["\r", "\n"], ' ', $profilResiko['kesimpulan6']) : '';

    //     $output .= "D01|6100|{$nstratejik}|{$kesimpulan6}\r\n";
    //     $output .= "D01|6200|{$inherenstratejik}|{$keterangankpmr}\r\n";
    //     $output .= "D01|6300|{$kpmrstratejik}|{$keterangankpmr}\r\n";

    //     // Filename
    //     $filename = "PRBPRKS-0600-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

    //     // Response
    //     $response = service('response');
    //     $response->setHeader('Content-Type', 'text/plain');
    //     $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

    //     return $response->setBody($output);
    // }

    public function exporttxtstratejik()
    {
        // Authentication check
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        $profilResiko = $this->showprofilresikoModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $periodeDetail = $this->periodeModel->find($this->periodeId);

        $modalinti = $periodeDetail['modalinti'];
        $totalaset = $periodeDetail['totalaset'];
        $kantorcabang = $periodeDetail['kantorcabang'];
        $atmdebit = $periodeDetail['atmdebit'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];
        $kategori = $periodeDetail['kategori'];

        $tanggal = new \DateTime($profilResiko['tanggal']);
        $titleDate = $tanggal->format('Ymd');
        $exportDate = $tanggal->format('Y-m-d');

        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'] ?? '';
            $kodejenis = $infobpr['kodejenis'] ?? '';
        }

        // ===============================
        // Sanitasi teks (konsisten)
        // ===============================
        function sanitizeTxtStratejik($text)
        {
            $text = str_replace(["\r", "\n"], ' ', $text);
            $text = str_replace(
                ['“', '”', '‘', '’', '%', '|'],
                ['"', '"', "'", "'", ' persen ', ' '],
                $text
            );
            $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
            return trim(preg_replace('/\s+/', ' ', $text));
        }

        $output = "";

        // ===============================
        // Header
        // ===============================
        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|PRBPRKS|0600|{$modalinti}|{$totalaset}|{$kantorcabang}|{$atmdebit}|{$kategori}||\r\n";

        // ===============================
        // Ambil data stratejik
        // ===============================
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

        // ===============================
        // Sanitasi data
        // ===============================
        $nstratejik = sanitizeTxtStratejik($profilResiko['nstratejik'] ?? '');
        $kesimpulan6 = sanitizeTxtStratejik($profilResiko['kesimpulan6'] ?? '');

        $inherenstratejik = sanitizeTxtStratejik($stratejikInheren['penilaiankredit'] ?? '');
        $ketInheren = sanitizeTxtStratejik($stratejikInheren['keterangan'] ?? '');

        $kpmrstratejik = sanitizeTxtStratejik($stratejikKpmr['penilaiankredit'] ?? '');
        $ketKpmr = sanitizeTxtStratejik($stratejikKpmr['keterangan'] ?? '');

        // ===============================
        // D01 hanya jika ADA DATA
        // ===============================
        if ($nstratejik !== '' || $kesimpulan6 !== '') {
            $output .= "D01|6100|{$nstratejik}|{$kesimpulan6}\r\n";
        }

        if ($inherenstratejik !== '' || $ketInheren !== '') {
            $output .= "D01|6200|{$inherenstratejik}|{$ketInheren}\r\n";
        }

        if ($kpmrstratejik !== '' || $ketKpmr !== '') {
            $output .= "D01|6300|{$kpmrstratejik}|{$ketKpmr}\r\n";
        }

        // ===============================
        // Output response
        // ===============================
        $filename = "PRBPRKS-0600-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        $response = service('response');
        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response->setBody($output);
    }

    public function exportAllPDFToZipView()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;
        if ($redirect = $this->checkPeriode())
            return $redirect;

        $data = [
            'baseUrl' => base_url()
        ];

        return view('showprofilresiko/export_all_pdf_zip', $data);
    }

    public function exportAllToZip()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;

        $profilResiko = $this->showprofilresikoModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', session('active_periode'))
            ->first();

        $bprData = $this->infobprModel->getBprByKode($this->userKodebpr);
        $namaBpr = preg_replace('/[^A-Za-z0-9\s]/', '', $bprData['namabpr']);
        $namaBpr = preg_replace('/\s+/', ' ', $namaBpr);
        $namaBpr = trim($namaBpr);
        $laporanDate = new \DateTime($profilResiko['tanggal']);
        $zipDate = $laporanDate->format('d-m-Y');


        $zipFileName = 'LAPORAN PROFIL RISIKO NBPSIMPEL - ' . $namaBpr . " (" . $zipDate . ")" . '.zip';
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
            ['Showprofilresiko', 'exporttxtstratejik'],
            ['Stratejikinheren', 'exporttxtrisikostratejikinheren'],
            ['Stratejikkpmr', 'exporttxtrisikostratejikkpmr'],
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

    public function exportAllPDFToZipv2()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;
        if ($redirect = $this->checkPeriode())
            return $redirect;

        if (!$this->userKodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid.');
        }

        // Set unlimited execution time
        set_time_limit(600); // 10 menit
        ini_set('memory_limit', '1024M'); // 1GB

        try {
            $periodeDetail = $this->periodeModel->find($this->periodeId);
            $bprData = $this->infobprModel->getBprByKode($this->userKodebpr);

            $semester = $periodeDetail['semester'] ?? '';
            $tahun = $periodeDetail['tahun'] ?? '';
            $namaBpr = preg_replace('/[^A-Za-z0-9\-]/', '_', $bprData['namabpr']);

            // Create ZIP filename
            $zipFileName = "Profil_Risiko_PDF_{$namaBpr}_Semester_{$semester}_{$tahun}_" . date('YmdHis') . '.zip';
            $zipFilePath = WRITEPATH . 'uploads/' . $zipFileName;

            // Ensure directory exists
            if (!is_dir(WRITEPATH . 'uploads/')) {
                mkdir(WRITEPATH . 'uploads/', 0777, true);
            }

            $zip = new \ZipArchive();

            if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
                return redirect()->back()->with('error', 'Gagal membuat file ZIP');
            }

            // List of PDF generators - HTML yang akan di-convert jadi PDF di client side
            $pdfExports = [
                [
                    'type' => 'laporan',
                    'filename' => "00_Laporan_Profil_Risiko_Semester_{$semester}_{$tahun}.html",
                    'title' => 'Laporan Profil Risiko'
                ],
                [
                    'type' => 'kredit',
                    'filename' => "01_Kertas_Kerja_Risiko_Kredit_Semester_{$semester}_{$tahun}.html",
                    'title' => 'Kertas Kerja Risiko Kredit'
                ],
                [
                    'type' => 'operasional',
                    'filename' => "02_Kertas_Kerja_Risiko_Operasional_Semester_{$semester}_{$tahun}.html",
                    'title' => 'Kertas Kerja Risiko Operasional'
                ],
                [
                    'type' => 'kepatuhan',
                    'filename' => "03_Kertas_Kerja_Risiko_Kepatuhan_Semester_{$semester}_{$tahun}.html",
                    'title' => 'Kertas Kerja Risiko Kepatuhan'
                ],
                [
                    'type' => 'likuiditas',
                    'filename' => "04_Kertas_Kerja_Risiko_Likuiditas_Semester_{$semester}_{$tahun}.html",
                    'title' => 'Kertas Kerja Risiko Likuiditas'
                ]
            ];

            // Add Reputasi & Stratejik jika kategori A
            $kategori = $periodeDetail['kategori'] ?? '';
            if (strtoupper($kategori) === 'A') {
                $pdfExports[] = [
                    'type' => 'reputasi',
                    'filename' => "05_Kertas_Kerja_Risiko_Reputasi_Semester_{$semester}_{$tahun}.html",
                    'title' => 'Kertas Kerja Risiko Reputasi'
                ];
                $pdfExports[] = [
                    'type' => 'stratejik',
                    'filename' => "06_Kertas_Kerja_Risiko_Stratejik_Semester_{$semester}_{$tahun}.html",
                    'title' => 'Kertas Kerja Risiko Stratejik'
                ];
            }

            // Add Lembar Pernyataan
            $pdfExports[] = [
                'type' => 'lembar',
                'filename' => "07_Lembar_Pernyataan_Semester_{$semester}_{$tahun}.html",
                'title' => 'Lembar Pernyataan'
            ];

            // Generate HTML files for each PDF
            foreach ($pdfExports as $export) {
                $htmlContent = $this->generatePDFGeneratorHTML($export['type'], $export['title']);

                if ($htmlContent) {
                    $zip->addFromString($export['filename'], $htmlContent);
                }
            }

            $zip->close();

            // Clean up old files (optional - hapus file ZIP yang lebih dari 1 hari)
            $this->cleanupOldZipFiles();

            // Send file
            return $this->response
                ->setHeader('Content-Type', 'application/zip')
                ->setHeader('Content-Disposition', 'attachment; filename="' . $zipFileName . '"')
                ->setHeader('Content-Length', filesize($zipFilePath))
                ->setBody(file_get_contents($zipFilePath));

        } catch (\Exception $e) {
            log_message('error', 'Error exportAllPDFToZipv2: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal membuat ZIP: ' . $e->getMessage());
        }
    }

    public function ajaxUpdateTtd()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid request method'
            ]);
        }

        if (!$this->auth->check()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Unauthorized access'
            ]);
        }

        if (!$this->periodeId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Periode belum dipilih'
            ]);
        }

        try {
            $id = $this->request->getPost('id');

            if (empty($id)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'ID tidak ditemukan'
                ]);
            }

            // Convert tanggal format
            $tanggal = $this->request->getPost('tanggal');
            $tanggalConverted = $this->convertDateFormat($tanggal);

            $data = [
                'dirut' => $this->request->getPost('dirut'),
                'dirkep' => $this->request->getPost('dirkep'),
                'pe' => $this->request->getPost('pe'),
                'tanggal' => $tanggalConverted,  // Use converted date
                'lokasi' => $this->request->getPost('lokasi'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Validasi data required
            $requiredFields = ['dirut', 'dirkep', 'pe', 'tanggal', 'lokasi'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => "Field {$field} harus diisi"
                    ]);
                }
            }

            // Validasi format tanggal
            if ($tanggalConverted === null) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Format tanggal tidak valid. Gunakan format dd/mm/yyyy'
                ]);
            }

            $this->showprofilresikoModel->update($id, $data);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Data berhasil disimpan',
                'data' => [
                    'dirut' => $data['dirut'],
                    'dirkep' => $data['dirkep'],
                    'pe' => $data['pe'],
                    'tanggal' => $tanggal, // Return original format for display
                    'tanggal_db' => $tanggalConverted, // For debugging
                    'lokasi' => $data['lokasi']
                ]
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ]);
        }
    }

    public function ajaxAutoSaveField()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid request method'
            ]);
        }

        if (!$this->auth->check()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Unauthorized access'
            ]);
        }

        try {
            $id = $this->request->getPost('id');
            $field = $this->request->getPost('field');
            $value = $this->request->getPost('value');

            if (empty($id) || empty($field)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Parameter tidak lengkap'
                ]);
            }

            // Validasi field yang diizinkan
            $allowedFields = ['dirut', 'dirkep', 'pe', 'tanggal', 'lokasi'];
            if (!in_array($field, $allowedFields)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Field tidak valid'
                ]);
            }

            // Convert date format if field is tanggal
            if ($field === 'tanggal') {
                $value = $this->convertDateFormat($value);

                if ($value === null) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => 'Format tanggal tidak valid. Gunakan format dd/mm/yyyy'
                    ]);
                }
            }

            $data = [
                $field => $value,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $this->showprofilresikoModel->update($id, $data);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Data berhasil disimpan',
                'field' => $field,
                'value' => $value
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ]);
        }
    }

    public function ajaxSaveKesimpulan()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid request method'
            ]);
        }

        if (!$this->auth->check()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Unauthorized access'
            ]);
        }

        try {
            $id = $this->request->getPost('id');
            $kesimpulan = $this->request->getPost('kesimpulan');

            if (empty($id)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'ID tidak ditemukan'
                ]);
            }

            $data = [
                'kesimpulan' => $kesimpulan,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $this->showprofilresikoModel->update($id, $data);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Kesimpulan berhasil disimpan',
                'data' => [
                    'kesimpulan' => $kesimpulan
                ]
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal menyimpan kesimpulan: ' . $e->getMessage()
            ]);
        }
    }

    /// PDF
    public function viewLembarPernyataan()
    {
        // Validasi auth dan periode
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
        $showprofilresiko = $this->showprofilresikoModel->getByKodebprAndPeriode($this->userKodebpr, $this->periodeId);

        // Validasi data
        if (!$showprofilresiko) {
            return redirect()->back()->with('error', 'Data profil risiko belum tersedia.');
        }

        // Prepare data untuk view
        $data = [
            'bpr' => $bprData,
            'periode' => $periodeDetail,
            'profil' => $showprofilresiko
        ];

        // Return view
        return view('showprofilresiko/lembar_pernyataan_pdf', $data);
    }

    public function exportLembarPernyataanJSON()
    {
        // Validasi auth dan periode
        if ($redirect = $this->checkAuth())
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        if ($redirect = $this->checkPeriode())
            return $this->response->setJSON(['status' => 'error', 'message' => 'Periode tidak dipilih']);

        if (!$this->userKodebpr) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Kode BPR tidak valid']);
        }

        // Get data
        $periodeDetail = $this->periodeModel->find($this->periodeId);
        $bprData = $this->infobprModel->getBprByKode($this->userKodebpr);
        $showprofilresiko = $this->showprofilresikoModel->getByKodebprAndPeriode($this->userKodebpr, $this->periodeId);

        // Validasi data
        if (!$showprofilresiko) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Data profil risiko belum tersedia'
            ]);
        }

        // Format tanggal
        $tanggal = '';
        if (!empty($showprofilresiko['tanggal'])) {
            $dateObj = \DateTime::createFromFormat('Y-m-d', $showprofilresiko['tanggal']);
            if ($dateObj) {
                // Format: 31 Juli 2025
                $bulan = [
                    '01' => 'Januari',
                    '02' => 'Februari',
                    '03' => 'Maret',
                    '04' => 'April',
                    '05' => 'Mei',
                    '06' => 'Juni',
                    '07' => 'Juli',
                    '08' => 'Agustus',
                    '09' => 'September',
                    '10' => 'Oktober',
                    '11' => 'November',
                    '12' => 'Desember'
                ];
                $tanggal = $dateObj->format('d') . ' ' . $bulan[$dateObj->format('m')] . ' ' . $dateObj->format('Y');
            }
        }

        // Prepare logo (convert to base64 if exists)
        $logoBase64 = '';
        if (!empty($bprData['logo'])) {
            $logoPath = FCPATH . 'asset/img/' . $bprData['logo'];
            if (file_exists($logoPath)) {
                $imageData = file_get_contents($logoPath);
                $imageType = mime_content_type($logoPath);
                $logoBase64 = 'data:' . $imageType . ';base64,' . base64_encode($imageData);
            }
        }

        // Return JSON
        return $this->response->setJSON([
            'status' => 'success',
            'data' => [
                'bpr' => [
                    'logo' => $logoBase64,
                    'namabpr' => $bprData['namabpr'] ?? '',
                    'alamat' => $bprData['alamat'] ?? '',
                    'nomor' => $bprData['nomor'] ?? '',
                    'webbpr' => $bprData['webbpr'] ?? '',
                    'email' => $bprData['email'] ?? ''
                ],
                'periode' => [
                    'semester' => $periodeDetail['semester'] ?? '',
                    'tahun' => $periodeDetail['tahun'] ?? ''
                ],
                'profil' => [
                    'lokasi' => $showprofilresiko['lokasi'] ?? '',
                    'tanggal' => $tanggal,
                    'pe' => $showprofilresiko['pe'] ?? '',
                    'dirut' => $showprofilresiko['dirut'] ?? '',
                    'dirkep' => $showprofilresiko['dirkep'] ?? ''
                ]
            ]
        ]);
    }

    public function viewLaporanProfilRisiko()
    {
        // Validasi auth dan periode
        if ($redirect = $this->checkAuth())
            return $redirect;
        if ($redirect = $this->checkPeriode())
            return $redirect;

        if (!$this->userKodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid.');
        }

        // Return view
        return view('Showprofilresiko/laporan_profil_risiko_pdf');
    }

    public function exportLaporanProfilRisikoJSON()
    {
        // Validasi auth dan periode
        if ($redirect = $this->checkAuth())
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        if ($redirect = $this->checkPeriode())
            return $this->response->setJSON(['status' => 'error', 'message' => 'Periode tidak dipilih']);

        if (!$this->userKodebpr) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Kode BPR tidak valid']);
        }

        // Get data
        $periodeDetail = $this->periodeModel->find($this->periodeId);
        $bprData = $this->infobprModel->getBprByKode($this->userKodebpr);
        $showprofilresiko = $this->showprofilresikoModel->getByKodebprAndPeriode($this->userKodebpr, $this->periodeId);

        // Validasi data
        if (!$showprofilresiko) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Data profil risiko belum tersedia'
            ]);
        }

        // Prepare logo
        $logoBase64 = '';
        if (!empty($bprData['logo'])) {
            $logoPath = FCPATH . 'asset/img/' . $bprData['logo'];
            if (file_exists($logoPath)) {
                $imageData = file_get_contents($logoPath);
                $imageType = mime_content_type($logoPath);
                $logoBase64 = 'data:' . $imageType . ';base64,' . base64_encode($imageData);
            }
        }

        // Get periode sebelumnya
        $periodeSebelum = $this->getPeriodeSebelumnya($periodeDetail['semester'], $periodeDetail['tahun']);

        // Ambil data risiko inheren dan KPMR untuk periode current
        $kreditInheren = $this->kreditinherenModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 13)
            ->first();

        $kreditKpmr = $this->kreditkpmrModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 33)
            ->first();

        $operasionalInheren = $this->operasionalinherenModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 48)
            ->first();

        $operasionalKpmr = $this->operasionalkpmrModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 70)
            ->first();

        $kepatuhanInheren = $this->kepatuhaninherenModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 81)
            ->first();

        $kepatuhanKpmr = $this->kepatuhankpmrModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 102)
            ->first();

        $likuiditasInheren = $this->likuiditasinherenModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 115)
            ->first();

        $likuiditasKpmr = $this->likuiditaskpmrModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 135)
            ->first();

        // Data risiko reputasi (jika ada)
        $reputasiInheren = $this->reputasiinherenModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 148)
            ->first();

        $reputasiKpmr = $this->reputasikpmrModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 168)
            ->first();

        // Data risiko stratejik (jika ada)
        $stratejikInheren = $this->stratejikinherenModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 179)
            ->first();

        $stratejikKpmr = $this->stratejikkpmrModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 199)
            ->first();

        // Ambil data periode sebelumnya (belum)
        $kreditInherenBelum = $this->kreditinherenModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 14)
            ->first();

        $kreditKpmrBelum = $this->kreditkpmrModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 34)
            ->first();

        $operasionalInherenBelum = $this->operasionalinherenModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 49)
            ->first();

        $operasionalKpmrBelum = $this->operasionalkpmrModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 71)
            ->first();

        $kepatuhanInherenBelum = $this->kepatuhaninherenModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 82)
            ->first();

        $kepatuhanKpmrBelum = $this->kepatuhankpmrModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 103)
            ->first();

        $likuiditasInherenBelum = $this->likuiditasinherenModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 116)
            ->first();

        $likuiditasKpmrBelum = $this->likuiditaskpmrModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 136)
            ->first();

        $reputasiInherenBelum = $this->reputasiinherenModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 149)
            ->first();

        $reputasiKpmrBelum = $this->reputasikpmrModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 169)
            ->first();

        $stratejikInherenBelum = $this->stratejikinherenModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 180)
            ->first();

        $stratejikKpmrBelum = $this->stratejikkpmrModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 200)
            ->first();

        $tanggal = '';
        if (!empty($showprofilresiko['tanggal'])) {
            $dateObj = \DateTime::createFromFormat('Y-m-d', $showprofilresiko['tanggal']);
            if ($dateObj) {
                // Format: 31 Juli 2025
                $bulan = [
                    '01' => 'Januari',
                    '02' => 'Februari',
                    '03' => 'Maret',
                    '04' => 'April',
                    '05' => 'Mei',
                    '06' => 'Juni',
                    '07' => 'Juli',
                    '08' => 'Agustus',
                    '09' => 'September',
                    '10' => 'Oktober',
                    '11' => 'November',
                    '12' => 'Desember'
                ];
                $tanggal = $dateObj->format('d') . ' ' . $bulan[$dateObj->format('m')] . ' ' . $dateObj->format('Y');
            }
        }

        // Return JSON
        return $this->response->setJSON([
            'status' => 'success',
            'data' => [
                'bpr' => [
                    'logo' => $logoBase64,
                    'namabpr' => $bprData['namabpr'] ?? '',
                    'alamat' => $bprData['alamat'] ?? '',
                    'nomor' => $bprData['nomor'] ?? '',
                    'webbpr' => $bprData['webbpr'] ?? '',
                    'email' => $bprData['email'] ?? ''
                ],
                'periode' => [
                    'semester' => $periodeDetail['semester'] ?? '',
                    'tahun' => $periodeDetail['tahun'] ?? '',
                    'modalinti' => $periodeDetail['modalinti'] ?? '',
                    'totalaset' => $periodeDetail['totalaset'] ?? '',
                    'kantorcabang' => $periodeDetail['kantorcabang'] ?? '',
                    'atmdebit' => $periodeDetail['atmdebit'] ?? '',
                    'periodeSebelumSemester' => $periodeSebelum['semester'],
                    'periodeSebelumTahun' => $periodeSebelum['tahun']
                ],
                'kesimpulan' => $showprofilresiko['kesimpulan'] ?? '',
                'profil' => [
                    'lokasi' => $showprofilresiko['lokasi'] ?? '',
                    'tanggal' => $tanggal,
                    'pe' => $showprofilresiko['pe'] ?? '',
                    'dirut' => $showprofilresiko['dirut'] ?? '',
                    'dirkep' => $showprofilresiko['dirkep'] ?? ''
                ],
                'risiko' => [
                    'kredit' => [
                        'inherenCurrent' => $kreditInheren['penilaiankredit'] ?? null,
                        'kpmrCurrent' => $kreditKpmr['penilaiankredit'] ?? null,
                        'current' => $showprofilresiko['nkredit'] ?? null,
                        'inherenPrevious' => $kreditInherenBelum['penilaiankredit'] ?? null,
                        'kpmrPrevious' => $kreditKpmrBelum['penilaiankredit'] ?? null,
                        'previous' => $showprofilresiko['nbelumkredit'] ?? null
                    ],
                    'operasional' => [
                        'inherenCurrent' => $operasionalInheren['penilaiankredit'] ?? null,
                        'kpmrCurrent' => $operasionalKpmr['penilaiankredit'] ?? null,
                        'current' => $showprofilresiko['noperasional'] ?? null,
                        'inherenPrevious' => $operasionalInherenBelum['penilaiankredit'] ?? null,
                        'kpmrPrevious' => $operasionalKpmrBelum['penilaiankredit'] ?? null,
                        'previous' => $showprofilresiko['nbelumoperasional'] ?? null
                    ],
                    'kepatuhan' => [
                        'inherenCurrent' => $kepatuhanInheren['penilaiankredit'] ?? null,
                        'kpmrCurrent' => $kepatuhanKpmr['penilaiankredit'] ?? null,
                        'current' => $showprofilresiko['nkepatuhan'] ?? null,
                        'inherenPrevious' => $kepatuhanInherenBelum['penilaiankredit'] ?? null,
                        'kpmrPrevious' => $kepatuhanKpmrBelum['penilaiankredit'] ?? null,
                        'previous' => $showprofilresiko['nbelumkepatuhan'] ?? null
                    ],
                    'likuiditas' => [
                        'inherenCurrent' => $likuiditasInheren['penilaiankredit'] ?? null,
                        'kpmrCurrent' => $likuiditasKpmr['penilaiankredit'] ?? null,
                        'current' => $showprofilresiko['nlikuiditas'] ?? null,
                        'inherenPrevious' => $likuiditasInherenBelum['penilaiankredit'] ?? null,
                        'kpmrPrevious' => $likuiditasKpmrBelum['penilaiankredit'] ?? null,
                        'previous' => $showprofilresiko['nbelumlikuiditas'] ?? null
                    ],
                    'reputasi' => [
                        'inherenCurrent' => $reputasiInheren['penilaiankredit'] ?? null,
                        'kpmrCurrent' => $reputasiKpmr['penilaiankredit'] ?? null,
                        'current' => $showprofilresiko['nreputasi'] ?? null,
                        'inherenPrevious' => $reputasiInherenBelum['penilaiankredit'] ?? null,
                        'kpmrPrevious' => $reputasiKpmrBelum['penilaiankredit'] ?? null,
                        'previous' => $showprofilresiko['nbelumreputasi'] ?? null
                    ],
                    'stratejik' => [
                        'inherenCurrent' => $stratejikInheren['penilaiankredit'] ?? null,
                        'kpmrCurrent' => $stratejikKpmr['penilaiankredit'] ?? null,
                        'current' => $showprofilresiko['nstratejik'] ?? null,
                        'inherenPrevious' => $stratejikInherenBelum['penilaiankredit'] ?? null,
                        'kpmrPrevious' => $stratejikKpmrBelum['penilaiankredit'] ?? null,
                        'previous' => $showprofilresiko['nbelumstratejik'] ?? null
                    ],
                    'peringkat' => [
                        'current' => $showprofilresiko['ntotalrisk'] ?? null,
                        'previous' => $showprofilresiko['nbelumtotalrisk'] ?? null
                    ]
                ],
                'analisis' => [
                    'intro' => "Hasil dari analisis Profil Risiko semester {$periodeDetail['semester']} tahun {$periodeDetail['tahun']} sebagai berikut:",
                    'kredit' => $this->generateAnalisisText('Kredit', $showprofilresiko, $kreditInheren, $kreditKpmr),
                    'operasional' => $this->generateAnalisisText('Operasional', $showprofilresiko, $operasionalInheren, $operasionalKpmr),
                    'kepatuhan' => $this->generateAnalisisText('Kepatuhan', $showprofilresiko, $kepatuhanInheren, $kepatuhanKpmr),
                    'likuiditas' => $this->generateAnalisisText('Likuiditas', $showprofilresiko, $likuiditasInheren, $likuiditasKpmr),
                    'reputasi' => ($reputasiInheren && $reputasiKpmr) ? $this->generateAnalisisText('Reputasi', $showprofilresiko, $reputasiInheren, $reputasiKpmr) : null,
                    'stratejik' => ($stratejikInheren && $stratejikKpmr) ? $this->generateAnalisisText('Stratejik', $showprofilresiko, $stratejikInheren, $stratejikKpmr) : null
                ]
            ]
        ]);
    }

    private function getPeriodeSebelumnya($semester, $tahun)
    {
        if ($semester == 'I') {
            return ['semester' => 'II', 'tahun' => $tahun - 1];
        } else {
            return ['semester' => 'I', 'tahun' => $tahun];
        }
    }

    private function generateAnalisisText($jenis, $profil, $inheren, $kpmr)
    {
        $nilaiInheren = $inheren['penilaiankredit'] ?? 0;
        $nilaiKpmr = $kpmr['penilaiankredit'] ?? 0;

        $textInheren = $this->getNilaiText($nilaiInheren);
        $textKpmr = $this->getKpmrText($nilaiKpmr);

        return "Risiko {$jenis} : Risiko Inheren peringkat {$nilaiInheren} ({$textInheren}), Risiko KPMR peringkat {$nilaiKpmr} ({$textKpmr})";
    }

    private function getNilaiText($nilai)
    {
        switch ($nilai) {
            case 1:
                return 'Sangat Rendah';
            case 2:
                return 'Rendah';
            case 3:
                return 'Sedang';
            case 4:
                return 'Tinggi';
            case 5:
                return 'Sangat Tinggi';
            default:
                return 'Tidak ada data';
        }
    }

    private function getKpmrText($nilai)
    {
        switch ($nilai) {
            case 1:
                return 'Sangat Rendah';
            case 2:
                return 'Rendah';
            case 3:
                return 'Menengah';
            case 4:
                return 'Tinggi';
            case 5:
                return 'Sangat Tinggi';
            default:
                return 'Tidak ada data';
        }
    }

    public function getRiskKeterangan($riskType, $faktorId)
    {
        // Validasi auth
        if (!$this->auth->check()) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Unauthorized'
            ]);
        }

        if (!$this->periodeId) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Periode tidak dipilih'
            ]);
        }

        try {
            $model = null;
            $isKpmr = false;

            // Mapping faktorId ke model yang sesuai
            switch ($riskType) {
                case 'kredit':
                    if ($faktorId == 13 || $faktorId == 14) {
                        $model = $this->kreditinherenModel;
                    } else if ($faktorId == 33 || $faktorId == 34) {
                        $model = $this->kreditkpmrModel;
                        $isKpmr = true;
                    }
                    break;

                case 'operasional':
                    if ($faktorId == 48 || $faktorId == 49) {
                        $model = $this->operasionalinherenModel;
                    } else if ($faktorId == 70 || $faktorId == 71) {
                        $model = $this->operasionalkpmrModel;
                        $isKpmr = true;
                    }
                    break;

                case 'kepatuhan':
                    if ($faktorId == 81 || $faktorId == 82) {
                        $model = $this->kepatuhaninherenModel;
                    } else if ($faktorId == 102 || $faktorId == 103) {
                        $model = $this->kepatuhankpmrModel;
                        $isKpmr = true;
                    }
                    break;

                case 'likuiditas':
                    if ($faktorId == 115 || $faktorId == 116) {
                        $model = $this->likuiditasinherenModel;
                    } else if ($faktorId == 135 || $faktorId == 136) {
                        $model = $this->likuiditaskpmrModel;
                        $isKpmr = true;
                    }
                    break;

                case 'reputasi':
                    if ($faktorId == 148 || $faktorId == 149) {
                        $model = $this->reputasiinherenModel;
                    } else if ($faktorId == 168 || $faktorId == 169) {
                        $model = $this->reputasikpmrModel;
                        $isKpmr = true;
                    }
                    break;

                case 'stratejik':
                    if ($faktorId == 179 || $faktorId == 180) {
                        $model = $this->stratejikinherenModel;
                    } else if ($faktorId == 199 || $faktorId == 200) {
                        $model = $this->stratejikkpmrModel;
                        $isKpmr = true;
                    }
                    break;

                default:
                    return $this->response->setJSON([
                        'status' => 'error',
                        'message' => 'Risk type tidak valid'
                    ]);
            }

            if (!$model) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Model tidak ditemukan untuk faktor ID: ' . $faktorId
                ]);
            }

            // Ambil range faktor berdasarkan jenis risiko
            $ranges = $this->getFaktorRanges($riskType, $isKpmr);

            // Query data dengan whereIn yang benar
            $data = $model
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->whereIn('faktor1id', $ranges) // Langsung gunakan array
                ->orderBy('faktor1id', 'ASC')
                ->findAll();

            // Format data untuk response
            $formattedData = [];
            foreach ($data as $item) {
                // Skip faktor utama (parent)
                if ($item['faktor1id'] == $faktorId) {
                    continue;
                }

                $formattedData[] = [
                    'faktor1id' => $item['faktor1id'],
                    'keterangan' => $item['keterangan'] ?? '',
                    'penilaian' => $item['penilaiankredit'] ?? null
                ];
            }

            return $this->response->setJSON([
                'status' => 'success',
                'data' => $formattedData
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Helper function untuk mendapatkan range faktor ID
     * berdasarkan jenis risiko dan tipe (inheren/kpmr)
     */
    private function getFaktorRanges($riskType, $isKpmr)
    {
        $ranges = [];

        switch ($riskType) {
            case 'kredit':
                if ($isKpmr) {
                    // KPMR Kredit: faktor 15-32 (current) atau 35-52 (previous)
                    $ranges = range(15, 32);
                } else {
                    // Inheren Kredit: faktor 1-12 (current) atau 14-26 (previous)
                    $ranges = range(1, 12);
                }
                break;

            case 'operasional':
                if ($isKpmr) {
                    // KPMR Operasional: faktor 50-69 (current) atau 71-90 (previous)
                    $ranges = range(50, 69);
                } else {
                    // Inheren Operasional: faktor 35-47 (current) atau 49-61 (previous)
                    $ranges = range(35, 47);
                }
                break;

            case 'kepatuhan':
                if ($isKpmr) {
                    // KPMR Kepatuhan: faktor 83-101 (current) atau 103-121 (previous)
                    $ranges = range(83, 101);
                } else {
                    // Inheren Kepatuhan: faktor 62-80 (current) atau 82-100 (previous)
                    $ranges = range(62, 80);
                }
                break;

            case 'likuiditas':
                if ($isKpmr) {
                    // KPMR Likuiditas: faktor 118-134 (current) atau 136-153 (previous)
                    $ranges = range(118, 134);
                } else {
                    // Inheren Likuiditas: faktor 104-114 (current) atau 116-126 (previous)
                    $ranges = range(105, 114);
                }
                break;

            case 'reputasi':
                if ($isKpmr) {
                    // KPMR Reputasi: faktor 150-167 (current) atau 169-186 (previous)
                    $ranges = range(150, 167);
                } else {
                    // Inheren Reputasi: faktor 137-147 (current) atau 149-159 (previous)
                    $ranges = range(137, 147);
                }
                break;

            case 'stratejik':
                if ($isKpmr) {
                    // KPMR Stratejik: faktor 181-198 (current) atau 200-217 (previous)
                    $ranges = range(181, 198);
                } else {
                    // Inheren Stratejik: faktor 170-178 (current) atau 180-188 (previous)
                    $ranges = range(170, 178);
                }
                break;
        }

        return $ranges;
    }

    public function exportPDFGabunganKreditJSON()
    {
        // Validasi auth dan periode
        if (!$this->auth->check()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if (!$this->periodeId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Periode tidak dipilih']);
        }

        if (!$this->userKodebpr) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Kode BPR tidak valid']);
        }

        try {
            // Get periode and BPR data
            $periodeDetail = $this->periodeModel->find($this->periodeId);
            $bprData = $this->infobprModel->getBprByKode($this->userKodebpr);

            // Prepare logo
            $logoBase64 = '';
            if (!empty($bprData['logo'])) {
                $logoPath = FCPATH . 'asset/img/' . $bprData['logo'];
                if (file_exists($logoPath)) {
                    $imageData = file_get_contents($logoPath);
                    $imageType = mime_content_type($logoPath);
                    $logoBase64 = 'data:' . $imageType . ';base64,' . base64_encode($imageData);
                }
            }

            // Get Inheren data with all children
            $dataInheren = $this->getKreditInherenWithChildren($this->userKodebpr, $this->periodeId);

            // Get KPMR data with all children
            $dataKPMR = $this->getKreditKPMRWithChildren($this->userKodebpr, $this->periodeId);

            return $this->response->setJSON([
                'status' => 'success',
                'data' => [
                    'bpr' => [
                        'logo' => $logoBase64,
                        'namabpr' => $bprData['namabpr'] ?? '',
                        'alamat' => $bprData['alamat'] ?? '',
                        'nomor' => $bprData['nomor'] ?? '',
                        'webbpr' => $bprData['webbpr'] ?? '',
                        'email' => $bprData['email'] ?? ''
                    ],
                    'periode' => [
                        'semester' => $periodeDetail['semester'] ?? '',
                        'tahun' => $periodeDetail['tahun'] ?? ''
                    ],
                    'inheren' => [
                        'bpr' => [
                            'namabpr' => $bprData['namabpr'] ?? ''
                        ],
                        'periode' => [
                            'semester' => $periodeDetail['semester'] ?? '',
                            'tahun' => $periodeDetail['tahun'] ?? ''
                        ],
                        'nilai' => $dataInheren['nilai'],
                        'nilai13' => $dataInheren['nilai13'],
                        'nilai14' => $dataInheren['nilai14']
                    ],
                    'kpmr' => [
                        'bpr' => [
                            'namabpr' => $bprData['namabpr'] ?? ''
                        ],
                        'periode' => [
                            'semester' => $periodeDetail['semester'] ?? '',
                            'tahun' => $periodeDetail['tahun'] ?? ''
                        ],
                        'nilai' => $dataKPMR['nilai'],
                        'nilai33' => $dataKPMR['nilai33'],
                        'nilai34' => $dataKPMR['nilai34']
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error exportPDFGabunganKreditJSON: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    private function getKreditInherenWithChildren($kodebpr, $periodeId)
    {
        $result = [
            'nilai' => [
                'komposisi' => ['kategori' => null, 'children' => []],
                'kualitas' => ['kategori' => null, 'children' => []],
                'strategi' => null,
                'eksternal' => null,
                'lainnya' => null
            ],
            'nilai13' => null,
            'nilai14' => null
        ];

        // Get all data (faktor 1-116)
        $allData = $this->kreditinherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->whereIn('faktor1id', range(1, 14))
            ->orderBy('faktor1id', 'ASC')
            ->findAll();

        foreach ($allData as $item) {
            $faktorId = $item['faktor1id'];

            // komposisi (1-5)
            if ($faktorId >= 1 && $faktorId <= 5) {
                if ($faktorId == 1) {
                    $result['nilai']['komposisi']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['komposisi']['children'][] = $item;
                }
            }
            // Kualitas (6-9)
            else if ($faktorId >= 6 && $faktorId <= 9) {
                if ($faktorId == 6) {
                    $result['nilai']['kualitas']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['kualitas']['children'][] = $item;
                }
            }

            // Penilaian Strategi Penyediaan Dana
            else if ($faktorId == 10) {
                $result['nilai']['strategi'] = $item;
            }

            // Faktor Eksternal
            else if ($faktorId == 11) {
                $result['nilai']['eksternal'] = $item;
            }

            // Lainnya
            else if ($faktorId == 12) {
                $result['nilai']['lainnya'] = $item;
            }

            // Penilaian Risiko Current
            else if ($faktorId == 13) {
                $result['nilai13'] = $item;
            }
            // Penilaian Risiko Previous
            else if ($faktorId == 14) {
                $result['nilai14'] = $item;
            }
        }

        return $result;
    }

    private function getKreditKPMRWithChildren($kodebpr, $periodeId)
    {
        $result = [
            'nilai' => [
                'pengawasan' => ['kategori' => null, 'children' => []],
                'kebijakan' => ['kategori' => null, 'children' => []],
                'proses' => ['kategori' => null, 'children' => []],
                'pengendalian' => ['kategori' => null, 'children' => []]
            ],
            'nilai33' => null,
            'nilai34' => null
        ];

        // Get all data (faktor 16-34)
        $allData = $this->kreditkpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->whereIn('faktor1id', range(16, 34))
            ->orderBy('faktor1id', 'ASC')
            ->findAll();

        foreach ($allData as $item) {
            $faktorId = $item['faktor1id'];

            // Pengawasan (16-22)
            if ($faktorId >= 16 && $faktorId <= 22) {
                if ($faktorId == 16) {
                    $result['nilai']['pengawasan']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['pengawasan']['children'][] = $item;
                }
            }
            // Kebijakan (23-26)
            else if ($faktorId >= 23 && $faktorId <= 26) {
                if ($faktorId == 23) {
                    $result['nilai']['kebijakan']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['kebijakan']['children'][] = $item;
                }
            }
            // Proses (27-29)
            else if ($faktorId >= 27 && $faktorId <= 29) {
                if ($faktorId == 27) {
                    $result['nilai']['proses']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['proses']['children'][] = $item;
                }
            }
            // Pengendalian (30-32)
            else if ($faktorId >= 30 && $faktorId <= 32) {
                if ($faktorId == 30) {
                    $result['nilai']['pengendalian']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['pengendalian']['children'][] = $item;
                }
            }
            // Penilaian Risiko KPMR Current
            else if ($faktorId == 33) {
                $result['nilai33'] = $item;
            }
            // Penilaian Risiko KPMR Previous
            else if ($faktorId == 34) {
                $result['nilai34'] = $item;
            }
        }

        return $result;
    }

    public function exportPDFGabunganOperasionalJSON()
    {
        // Validasi auth dan periode
        if (!$this->auth->check()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if (!$this->periodeId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Periode tidak dipilih']);
        }

        if (!$this->userKodebpr) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Kode BPR tidak valid']);
        }

        try {
            // Get periode and BPR data
            $periodeDetail = $this->periodeModel->find($this->periodeId);
            $bprData = $this->infobprModel->getBprByKode($this->userKodebpr);

            // Prepare logo
            $logoBase64 = '';
            if (!empty($bprData['logo'])) {
                $logoPath = FCPATH . 'asset/img/' . $bprData['logo'];
                if (file_exists($logoPath)) {
                    $imageData = file_get_contents($logoPath);
                    $imageType = mime_content_type($logoPath);
                    $logoBase64 = 'data:' . $imageType . ';base64,' . base64_encode($imageData);
                }
            }

            // Get Inheren data with all children
            $dataInheren = $this->getOperasionalInherenWithChildren($this->userKodebpr, $this->periodeId);

            // Get KPMR data with all children
            $dataKPMR = $this->getOperasionalKPMRWithChildren($this->userKodebpr, $this->periodeId);

            return $this->response->setJSON([
                'status' => 'success',
                'data' => [
                    'bpr' => [
                        'logo' => $logoBase64,
                        'namabpr' => $bprData['namabpr'] ?? '',
                        'alamat' => $bprData['alamat'] ?? '',
                        'nomor' => $bprData['nomor'] ?? '',
                        'webbpr' => $bprData['webbpr'] ?? '',
                        'email' => $bprData['email'] ?? ''
                    ],
                    'periode' => [
                        'semester' => $periodeDetail['semester'] ?? '',
                        'tahun' => $periodeDetail['tahun'] ?? ''
                    ],
                    'inheren' => [
                        'bpr' => [
                            'namabpr' => $bprData['namabpr'] ?? ''
                        ],
                        'periode' => [
                            'semester' => $periodeDetail['semester'] ?? '',
                            'tahun' => $periodeDetail['tahun'] ?? ''
                        ],
                        'nilai' => $dataInheren['nilai'],
                        'nilai13' => $dataInheren['nilai13'],
                        'nilai14' => $dataInheren['nilai14']
                    ],
                    'kpmr' => [
                        'bpr' => [
                            'namabpr' => $bprData['namabpr'] ?? ''
                        ],
                        'periode' => [
                            'semester' => $periodeDetail['semester'] ?? '',
                            'tahun' => $periodeDetail['tahun'] ?? ''
                        ],
                        'nilai' => $dataKPMR['nilai'],
                        'nilai33' => $dataKPMR['nilai33'],
                        'nilai34' => $dataKPMR['nilai34']
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error exportPDFGabunganOperasionalJSON: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    private function getOperasionalInherenWithChildren($kodebpr, $periodeId)
    {
        $result = [
            'nilai' => [
                'kompleksitas' => ['kategori' => null, 'children' => []],
                'sdm' => ['kategori' => null, 'children' => []],
                'ti' => null,
                'fraud' => null,
                'eksternal' => null,
                'lainnya' => null
            ],
            'nilai13' => null,
            'nilai14' => null
        ];

        // Get all data
        $allData = $this->operasionalinherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->whereIn('faktor1id', range(35, 49))
            ->orderBy('faktor1id', 'ASC')
            ->findAll();

        foreach ($allData as $item) {
            $faktorId = $item['faktor1id'];

            // Kompleksitas (36-40)
            if ($faktorId >= 36 && $faktorId <= 40) {
                if ($faktorId == 36) {
                    $result['nilai']['kompleksitas']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['kompleksitas']['children'][] = $item;
                }
            }
            // SDM (41-43)
            else if ($faktorId >= 41 && $faktorId <= 43) {
                if ($faktorId == 41) {
                    $result['nilai']['sdm']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['sdm']['children'][] = $item;
                }
            }
            // Single factors
            else if ($faktorId == 44) {
                $result['nilai']['ti'] = $item;
            } else if ($faktorId == 45) {
                $result['nilai']['fraud'] = $item;
            } else if ($faktorId == 46) {
                $result['nilai']['eksternal'] = $item;
            } else if ($faktorId == 47) {
                $result['nilai']['lainnya'] = $item;
            } else if ($faktorId == 48) {
                $result['nilai13'] = $item;
            } else if ($faktorId == 49) {
                $result['nilai14'] = $item;
            }
        }

        return $result;
    }

    private function getOperasionalKPMRWithChildren($kodebpr, $periodeId)
    {
        $result = [
            'nilai' => [
                'pengawasan' => ['kategori' => null, 'children' => []],
                'kebijakan' => ['kategori' => null, 'children' => []],
                'proses' => ['kategori' => null, 'children' => []],
                'pengendalian' => ['kategori' => null, 'children' => []]
            ],
            'nilai33' => null,
            'nilai34' => null
        ];

        // Get all data
        $allData = $this->operasionalkpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->whereIn('faktor1id', range(50, 71))
            ->orderBy('faktor1id', 'ASC')
            ->findAll();

        foreach ($allData as $item) {
            $faktorId = $item['faktor1id'];

            // Pengawasan (51-57)
            if ($faktorId >= 51 && $faktorId <= 57) {
                if ($faktorId == 51) {
                    $result['nilai']['pengawasan']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['pengawasan']['children'][] = $item;
                }
            }
            // Kebijakan (58-61)
            else if ($faktorId >= 58 && $faktorId <= 61) {
                if ($faktorId == 58) {
                    $result['nilai']['kebijakan']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['kebijakan']['children'][] = $item;
                }
            }
            // Proses (62-66)
            else if ($faktorId >= 62 && $faktorId <= 66) {
                if ($faktorId == 62) {
                    $result['nilai']['proses']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['proses']['children'][] = $item;
                }
            }
            // Pengendalian (67-69)
            else if ($faktorId >= 67 && $faktorId <= 69) {
                if ($faktorId == 67) {
                    $result['nilai']['pengendalian']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['pengendalian']['children'][] = $item;
                }
            } else if ($faktorId == 70) {
                $result['nilai33'] = $item;
            } else if ($faktorId == 71) {
                $result['nilai34'] = $item;
            }
        }

        return $result;
    }

    public function exportPDFGabunganKepatuhanJSON()
    {
        // Validasi auth dan periode
        if (!$this->auth->check()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if (!$this->periodeId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Periode tidak dipilih']);
        }

        if (!$this->userKodebpr) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Kode BPR tidak valid']);
        }

        try {
            // Get periode and BPR data
            $periodeDetail = $this->periodeModel->find($this->periodeId);
            $bprData = $this->infobprModel->getBprByKode($this->userKodebpr);

            // Prepare logo
            $logoBase64 = '';
            if (!empty($bprData['logo'])) {
                $logoPath = FCPATH . 'asset/img/' . $bprData['logo'];
                if (file_exists($logoPath)) {
                    $imageData = file_get_contents($logoPath);
                    $imageType = mime_content_type($logoPath);
                    $logoBase64 = 'data:' . $imageType . ';base64,' . base64_encode($imageData);
                }
            }

            // Get Inheren data with all children
            $dataInheren = $this->getKepatuhanInherenWithChildren($this->userKodebpr, $this->periodeId);

            // Get KPMR data with all children
            $dataKPMR = $this->getKepatuhanKPMRWithChildren($this->userKodebpr, $this->periodeId);

            return $this->response->setJSON([
                'status' => 'success',
                'data' => [
                    'bpr' => [
                        'logo' => $logoBase64,
                        'namabpr' => $bprData['namabpr'] ?? '',
                        'alamat' => $bprData['alamat'] ?? '',
                        'nomor' => $bprData['nomor'] ?? '',
                        'webbpr' => $bprData['webbpr'] ?? '',
                        'email' => $bprData['email'] ?? ''
                    ],
                    'periode' => [
                        'semester' => $periodeDetail['semester'] ?? '',
                        'tahun' => $periodeDetail['tahun'] ?? ''
                    ],
                    'inheren' => [
                        'bpr' => [
                            'namabpr' => $bprData['namabpr'] ?? ''
                        ],
                        'periode' => [
                            'semester' => $periodeDetail['semester'] ?? '',
                            'tahun' => $periodeDetail['tahun'] ?? ''
                        ],
                        'nilai' => $dataInheren['nilai'],
                        'nilai81' => $dataInheren['nilai81'],
                        'nilai82' => $dataInheren['nilai82']
                    ],
                    'kpmr' => [
                        'bpr' => [
                            'namabpr' => $bprData['namabpr'] ?? ''
                        ],
                        'periode' => [
                            'semester' => $periodeDetail['semester'] ?? '',
                            'tahun' => $periodeDetail['tahun'] ?? ''
                        ],
                        'nilai' => $dataKPMR['nilai'],
                        'nilai102' => $dataKPMR['nilai102'],
                        'nilai103' => $dataKPMR['nilai103']
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error exportPDFGabunganKepatuhanJSON: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    private function getKepatuhanInherenWithChildren($kodebpr, $periodeId)
    {
        $result = [
            'nilai' => [
                'pelanggaran' => ['kategori' => null, 'children' => []],
                'hukum' => ['kategori' => null, 'children' => []],
                'lainnya' => null
            ],
            'nilai81' => null,
            'nilai82' => null
        ];

        // Get all data
        $allData = $this->kepatuhaninherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->whereIn('faktor1id', range(73, 82))
            ->orderBy('faktor1id', 'ASC')
            ->findAll();

        foreach ($allData as $item) {
            $faktorId = $item['faktor1id'];

            // Pelanggaran (36-40)
            if ($faktorId >= 73 && $faktorId <= 75) {
                if ($faktorId == 73) {
                    $result['nilai']['pelanggaran']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['pelanggaran']['children'][] = $item;
                }
            }
            // Hukum (41-43)
            else if ($faktorId >= 76 && $faktorId <= 79) {
                if ($faktorId == 76) {
                    $result['nilai']['hukum']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['hukum']['children'][] = $item;
                }
            }
            // Single factors
            else if ($faktorId == 80) {
                $result['nilai']['lainnya'] = $item;
            } else if ($faktorId == 81) {
                $result['nilai81'] = $item;
            } else if ($faktorId == 82) {
                $result['nilai82'] = $item;
            }
        }

        return $result;
    }

    private function getKepatuhanKPMRWithChildren($kodebpr, $periodeId)
    {
        $result = [
            'nilai' => [
                'pengawasan' => ['kategori' => null, 'children' => []],
                'kebijakan' => ['kategori' => null, 'children' => []],
                'proses' => ['kategori' => null, 'children' => []],
                'pengendalian' => ['kategori' => null, 'children' => []]
            ],
            'nilai102' => null,
            'nilai103' => null
        ];

        // Get all data
        $allData = $this->operasionalkpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->whereIn('faktor1id', range(83, 103))
            ->orderBy('faktor1id', 'ASC')
            ->findAll();

        foreach ($allData as $item) {
            $faktorId = $item['faktor1id'];

            // Pengawasan (51-57)
            if ($faktorId >= 84 && $faktorId <= 91) {
                if ($faktorId == 84) {
                    $result['nilai']['pengawasan']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['pengawasan']['children'][] = $item;
                }
            }
            // Kebijakan (58-61)
            else if ($faktorId >= 92 && $faktorId <= 95) {
                if ($faktorId == 92) {
                    $result['nilai']['kebijakan']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['kebijakan']['children'][] = $item;
                }
            }
            // Proses (62-66)
            else if ($faktorId >= 96 && $faktorId <= 98) {
                if ($faktorId == 96) {
                    $result['nilai']['proses']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['proses']['children'][] = $item;
                }
            }
            // Pengendalian (67-69)
            else if ($faktorId >= 99 && $faktorId <= 101) {
                if ($faktorId == 99) {
                    $result['nilai']['pengendalian']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['pengendalian']['children'][] = $item;
                }
            } else if ($faktorId == 102) {
                $result['nilai102'] = $item;
            } else if ($faktorId == 103) {
                $result['nilai103'] = $item;
            }
        }

        return $result;
    }

    public function exportPDFGabunganLikuiditasJSON()
    {
        // Validasi auth dan periode
        if (!$this->auth->check()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if (!$this->periodeId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Periode tidak dipilih']);
        }

        if (!$this->userKodebpr) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Kode BPR tidak valid']);
        }

        try {
            // Get periode and BPR data
            $periodeDetail = $this->periodeModel->find($this->periodeId);
            $bprData = $this->infobprModel->getBprByKode($this->userKodebpr);

            // Prepare logo
            $logoBase64 = '';
            if (!empty($bprData['logo'])) {
                $logoPath = FCPATH . 'asset/img/' . $bprData['logo'];
                if (file_exists($logoPath)) {
                    $imageData = file_get_contents($logoPath);
                    $imageType = mime_content_type($logoPath);
                    $logoBase64 = 'data:' . $imageType . ';base64,' . base64_encode($imageData);
                }
            }

            // Get Inheren data with all children
            $dataInheren = $this->getLikuiditasInherenWithChildren($this->userKodebpr, $this->periodeId);

            // Get KPMR data with all children
            $dataKPMR = $this->getLikuiditasKPMRWithChildren($this->userKodebpr, $this->periodeId);

            return $this->response->setJSON([
                'status' => 'success',
                'data' => [
                    'bpr' => [
                        'logo' => $logoBase64,
                        'namabpr' => $bprData['namabpr'] ?? '',
                        'alamat' => $bprData['alamat'] ?? '',
                        'nomor' => $bprData['nomor'] ?? '',
                        'webbpr' => $bprData['webbpr'] ?? '',
                        'email' => $bprData['email'] ?? ''
                    ],
                    'periode' => [
                        'semester' => $periodeDetail['semester'] ?? '',
                        'tahun' => $periodeDetail['tahun'] ?? ''
                    ],
                    'inheren' => [
                        'bpr' => [
                            'namabpr' => $bprData['namabpr'] ?? ''
                        ],
                        'periode' => [
                            'semester' => $periodeDetail['semester'] ?? '',
                            'tahun' => $periodeDetail['tahun'] ?? ''
                        ],
                        'nilai' => $dataInheren['nilai'],
                        'nilai115' => $dataInheren['nilai115'],
                        'nilai116' => $dataInheren['nilai116']
                    ],
                    'kpmr' => [
                        'bpr' => [
                            'namabpr' => $bprData['namabpr'] ?? ''
                        ],
                        'periode' => [
                            'semester' => $periodeDetail['semester'] ?? '',
                            'tahun' => $periodeDetail['tahun'] ?? ''
                        ],
                        'nilai' => $dataKPMR['nilai'],
                        'nilai135' => $dataKPMR['nilai135'],
                        'nilai136' => $dataKPMR['nilai136']
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error exportPDFGabunganLikuiditasJSON: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    private function getLikuiditasInherenWithChildren($kodebpr, $periodeId)
    {
        $result = [
            'nilai' => [
                'konsentrasi' => ['kategori' => null, 'children' => []],
                'kerentanan' => ['kategori' => null, 'children' => []],
                'lainnya' => null
            ],
            'nilai115' => null,
            'nilai116' => null
        ];

        // Get all data (faktor 105-116)
        $allData = $this->likuiditasinherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->whereIn('faktor1id', range(105, 116))
            ->orderBy('faktor1id', 'ASC')
            ->findAll();

        foreach ($allData as $item) {
            $faktorId = $item['faktor1id'];

            // Komposisi dan konsentrasi (105-110)
            if ($faktorId >= 105 && $faktorId <= 110) {
                if ($faktorId == 105) {
                    $result['nilai']['konsentrasi']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['konsentrasi']['children'][] = $item;
                }
            }
            // Kerentanan (111-113)
            else if ($faktorId >= 111 && $faktorId <= 113) {
                if ($faktorId == 111) {
                    $result['nilai']['kerentanan']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['kerentanan']['children'][] = $item;
                }
            }
            // Lainnya
            else if ($faktorId == 114) {
                $result['nilai']['lainnya'] = $item;
            }
            // Penilaian Risiko Current
            else if ($faktorId == 115) {
                $result['nilai115'] = $item;
            }
            // Penilaian Risiko Previous
            else if ($faktorId == 116) {
                $result['nilai116'] = $item;
            }
        }

        return $result;
    }

    private function getLikuiditasKPMRWithChildren($kodebpr, $periodeId)
    {
        $result = [
            'nilai' => [
                'pengawasan' => ['kategori' => null, 'children' => []],
                'kebijakan' => ['kategori' => null, 'children' => []],
                'proses' => ['kategori' => null, 'children' => []],
                'pengendalian' => ['kategori' => null, 'children' => []]
            ],
            'nilai135' => null,
            'nilai136' => null
        ];

        // Get all data (faktor 118-136)
        $allData = $this->likuiditaskpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->whereIn('faktor1id', range(118, 136))
            ->orderBy('faktor1id', 'ASC')
            ->findAll();

        foreach ($allData as $item) {
            $faktorId = $item['faktor1id'];

            // Pengawasan (118-125)
            if ($faktorId >= 118 && $faktorId <= 125) {
                if ($faktorId == 118) {
                    $result['nilai']['pengawasan']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['pengawasan']['children'][] = $item;
                }
            }
            // Kebijakan (126-129)
            else if ($faktorId >= 126 && $faktorId <= 129) {
                if ($faktorId == 126) {
                    $result['nilai']['kebijakan']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['kebijakan']['children'][] = $item;
                }
            }
            // Proses (130-132)
            else if ($faktorId >= 130 && $faktorId <= 132) {
                if ($faktorId == 130) {
                    $result['nilai']['proses']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['proses']['children'][] = $item;
                }
            }
            // Pengendalian (133-134)
            else if ($faktorId >= 133 && $faktorId <= 134) {
                if ($faktorId == 133) {
                    $result['nilai']['pengendalian']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['pengendalian']['children'][] = $item;
                }
            }
            // Penilaian Risiko KPMR Current
            else if ($faktorId == 135) {
                $result['nilai135'] = $item;
            }
            // Penilaian Risiko KPMR Previous
            else if ($faktorId == 136) {
                $result['nilai136'] = $item;
            }
        }

        return $result;
    }

}