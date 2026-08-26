<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_periodesemesteran;
use App\Models\M_showtks;
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
use App\Models\M_tksprofilrisiko;
use App\Models\M_tkstatakelola;
use App\Models\M_tksrentabilitas;
use App\Models\M_tkspermodalan;
use App\Models\M_showprofilresiko;
use App\Libraries\LaporanTKSPdfGenerator;
use Myth\Auth\Config\Services as AuthServices;

class Showtks extends Controller
{
    protected $auth;
    protected $session;
    protected $userKodebpr;
    protected $userId;
    protected $periodeId;
    protected $periodeModel;
    protected $showprofilresikoModel;
    protected $showtksModel;
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
    protected $tksprofilrisikoModel;
    protected $tkstatakelolaModel;
    protected $tksrentabilitasModel;
    protected $tkspermodalanModel;
    protected $userModel;
    protected $infobprModel;
    protected $userGroups = [];
    private $riskModelsConfig = [];
    private $queryCache = [];


    public function __construct()
    {
        $this->infobprModel = new M_infobpr();
        $this->periodeModel = new M_periodesemesteran();
        $this->showprofilresikoModel = new M_showprofilresiko();
        $this->showtksModel = new M_showtks();
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
        $this->tksprofilrisikoModel = new M_tksprofilrisiko();
        $this->tkstatakelolaModel = new M_tkstatakelola();
        $this->tksrentabilitasModel = new M_tksrentabilitas();
        $this->tkspermodalanModel = new M_tkspermodalan();
        $this->userModel = new M_user();

        $this->session = service('session');
        $this->auth = service('authentication');

        helper('text');

        $this->initializeUser();
        $this->initializeRiskModels();
    }

    private function cacheGet(string $key)
    {
        return $this->queryCache[$key] ?? null;
    }

    private function cacheSet(string $key, $value)
    {
        $this->queryCache[$key] = $value;
        return $value;
    }

    private function cacheInvalidate(string $key)
    {
        unset($this->queryCache[$key]);
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

    public function sanitizeText(string $text): string
    {
        $text = str_replace(["\r", "\n"], ' ', $text);
        $text = str_replace(
            ["\u{201C}", "\u{201D}", "\u{2018}", "\u{2019}", '%', '|'],
            ['"', '"', "'", "'", ' persen ', ' '],
            $text
        );
        $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    private function initializeRiskModels()
    {
        $this->riskModelsConfig = [
            'tksprofilrisiko' => [
                'name' => 'Faktor Profil Resiko',
                'link' => 'Tksprofilrisiko',
                'inheren' => ['model' => $this->kreditinherenModel, 'faktor' => 13],
                'kpmr' => ['model' => $this->kreditkpmrModel, 'faktor' => 33],
                'beluminheren' => ['model' => $this->kreditinherenModel, 'faktor' => 14],
                'belumkpmr' => ['model' => $this->kreditkpmrModel, 'faktor' => 34],
                'result_key' => 'nkredit',
                'result_belumkey' => 'nbelumkredit',
                'kesimpulan_key' => 'kesimpulan1',
            ],
            'tatakelola' => [
                'name' => 'Faktor Tata Kelola',
                'link' => 'Tkstatakelola',
                'inheren' => ['model' => $this->operasionalinherenModel, 'faktor' => 48],
                'kpmr' => ['model' => $this->operasionalkpmrModel, 'faktor' => 70],
                'beluminheren' => ['model' => $this->operasionalinherenModel, 'faktor' => 49],
                'belumkpmr' => ['model' => $this->operasionalkpmrModel, 'faktor' => 71],
                'result_key' => 'noperasional',
                'result_belumkey' => 'nbelumoperasional',
                'kesimpulan_key' => 'kesimpulan2',
            ],
            'rentabilitas' => [
                'name' => 'Faktor Rentabilitas',
                'link' => 'Tksrentabilitas',
                'inheren' => ['model' => $this->kepatuhaninherenModel, 'faktor' => 81],
                'kpmr' => ['model' => $this->kepatuhankpmrModel, 'faktor' => 102],
                'beluminheren' => ['model' => $this->kepatuhaninherenModel, 'faktor' => 82],
                'belumkpmr' => ['model' => $this->kepatuhankpmrModel, 'faktor' => 103],
                'result_key' => 'nkepatuhan',
                'result_belumkey' => 'nbelumkepatuhan',
                'kesimpulan_key' => 'kesimpulan3',
            ],
            'likuiditas' => [
                'name' => 'Faktor Permodalan',
                'link' => 'Tkspermodalan',
                'inheren' => ['model' => $this->likuiditasinherenModel, 'faktor' => 115],
                'kpmr' => ['model' => $this->likuiditaskpmrModel, 'faktor' => 135],
                'beluminheren' => ['model' => $this->likuiditasinherenModel, 'faktor' => 116],
                'belumkpmr' => ['model' => $this->likuiditaskpmrModel, 'faktor' => 136],
                'result_key' => 'nlikuiditas',
                'result_belumkey' => 'nbelumlikuiditas',
                'kesimpulan_key' => 'kesimpulan4',
            ],
        ];
    }

    private function getFaktorTKSData(string $kodebpr, $periodeId): array
    {
        $profilRisiko = $this->cachedTksProfilRisiko($kodebpr, $periodeId);
        $tataKelola = $this->cachedTksTataKelola($kodebpr, $periodeId);
        $rentabilitas = $this->cachedTksRentabilitas($kodebpr, $periodeId);
        $permodalan = $this->cachedTksPermodalan($kodebpr, $periodeId);

        return [
            'profil_risiko' => [
                'nama_faktor' => 'Faktor Profil Risiko',
                'url' => base_url('Tksprofilrisiko'),
                'nilai_peringkat' => $profilRisiko['nilaiperingkat'] ?? null,
                'nilai_peringkat_before' => $profilRisiko['nilaiperingkatbefore'] ?? null,
                'peringkat' => $profilRisiko['peringkat'] ?? 'N/A',
                'peringkat_label' => $this->getPeringkatLabel($profilRisiko['nilaiperingkat'] ?? null),
                'nilai_faktor' => $profilRisiko['nilaifaktor'] ?? null,
                'nilai_faktor_before' => $profilRisiko['nilaifaktorbefore'] ?? null,
                'analisis' => $profilRisiko['analisis'] ?? '',
                'is_approved' => $profilRisiko['is_approved'] ?? 0,
            ],
            'tata_kelola' => [
                'nama_faktor' => 'Faktor Tata Kelola',
                'url' => base_url('Tkstatakelola'),
                'nilai_peringkat' => $tataKelola['nilaiperingkat'] ?? null,
                'nilai_peringkat_before' => $tataKelola['nilaiperingkatbefore'] ?? null,
                'peringkat' => $tataKelola['peringkat'] ?? 'N/A',
                'peringkat_label' => $this->getPeringkatLabel($tataKelola['nilaiperingkat'] ?? null),
                'nilai_faktor' => $tataKelola['nilaifaktor'] ?? null,
                'nilai_faktor_before' => $tataKelola['nilaifaktorbefore'] ?? null,
                'analisis' => $tataKelola['analisis'] ?? '',
                'is_approved' => $tataKelola['is_approved'] ?? 0,
            ],
            'rentabilitas' => [
                'nama_faktor' => 'Faktor Rentabilitas',
                'url' => base_url('Tksrentabilitas'),
                'nilai_peringkat' => $rentabilitas['peringkatrentabilitas'] ?? null,
                'nilai_peringkat_before' => $rentabilitas['peringkatrentabilitasbefore'] ?? null,
                'peringkat' => $rentabilitas['peringkat'] ?? 'N/A',
                'peringkat_label' => $this->getPeringkatLabel($rentabilitas['peringkatrentabilitas'] ?? null),
                'nilai_faktor' => $rentabilitas['nilaifaktor'] ?? null,
                'nilai_faktor_before' => $rentabilitas['nilaifaktorbefore'] ?? null,
                'analisis' => $rentabilitas['analisarentabilitas'] ?? '',
                'is_approved' => $rentabilitas['is_approved'] ?? 0,
            ],
            'permodalan' => [
                'nama_faktor' => 'Faktor Permodalan',
                'url' => base_url('Tkspermodalan'),
                'nilai_peringkat' => $permodalan['penilaianpermodalan'] ?? null,
                'nilai_peringkat_before' => $permodalan['penilaianpermodalanbefore'] ?? null,
                'peringkat' => $permodalan['peringkat'] ?? 'N/A',
                'peringkat_label' => $this->getPeringkatLabel($permodalan['penilaianpermodalan'] ?? null),
                'nilai_faktor' => $permodalan['nilaifaktor'] ?? null,
                'nilai_faktor_before' => $permodalan['nilaifaktorbefore'] ?? null,
                'analisis' => $permodalan['analisispermodalan'] ?? '',
                'is_approved' => $permodalan['is_approved'] ?? 0,
            ],
        ];
    }

    private function cachedTksProfilRisiko(string $kodebpr, $periodeId)
    {
        $key = "tks_profilrisiko_{$kodebpr}_{$periodeId}";
        if (($v = $this->cacheGet($key)) !== null)
            return $v;
        return $this->cacheSet($key, $this->tksprofilrisikoModel->getByKodebprAndPeriode($kodebpr, $periodeId) ?: []);
    }

    private function cachedTksTataKelola(string $kodebpr, $periodeId)
    {
        $key = "tks_tatakelola_{$kodebpr}_{$periodeId}";
        if (($v = $this->cacheGet($key)) !== null)
            return $v;
        return $this->cacheSet($key, $this->tkstatakelolaModel->getByKodebprAndPeriode($kodebpr, $periodeId) ?: []);
    }

    private function cachedTksRentabilitas(string $kodebpr, $periodeId)
    {
        $key = "tks_rentabilitas_{$kodebpr}_{$periodeId}";
        if (($v = $this->cacheGet($key)) !== null)
            return $v;
        return $this->cacheSet($key, $this->tksrentabilitasModel->getByKodebprAndPeriode($kodebpr, $periodeId) ?: []);
    }

    private function cachedTksPermodalan(string $kodebpr, $periodeId)
    {
        $key = "tks_permodalan_{$kodebpr}_{$periodeId}";
        if (($v = $this->cacheGet($key)) !== null)
            return $v;
        return $this->cacheSet($key, $this->tkspermodalanModel->getByKodebprAndPeriode($kodebpr, $periodeId) ?: []);
    }

    private function cachedShowtks(string $kodebpr, $periodeId)
    {
        $key = "showtks_{$kodebpr}_{$periodeId}";
        if (($v = $this->cacheGet($key)) !== null)
            return $v;
        return $this->cacheSet($key, $this->showtksModel->getByKodebprAndPeriode($kodebpr, $periodeId) ?: []);
    }

    private function cachedPeriodeDetail($periodeId)
    {
        $key = "periode_{$periodeId}";
        if (($v = $this->cacheGet($key)) !== null)
            return $v;
        return $this->cacheSet($key, $this->periodeModel->find($periodeId) ?: []);
    }

    private function getPeringkatLabel($nilai): string
    {
        if ($nilai === null || !is_numeric($nilai))
            return 'N/A';

        return [
            1 => 'Sangat Rendah (1)',
            2 => 'Rendah (2)',
            3 => 'Sedang (3)',
            4 => 'Tinggi (4)',
            5 => 'Sangat Tinggi (5)',
        ][$nilai] ?? 'N/A';
    }

    private function checkAuth()
    {
        if (!$this->auth->check()) {
            session()->set('redirect_url', current_url());
            return redirect()->to('/login');
        }
        return null;
    }

    private function checkAuths()
    {
        return $this->checkAuth();
    }

    private function checkPeriode()
    {
        if (!$this->periodeId) {
            return redirect()->to('/Periodetks')
                ->with('error', 'Silakan pilih periode aktif terlebih dahulu.');
        }
        return null;
    }

    private function getTksData(string $kodebpr, $periodeId): array
    {
        $tksData = [];

        $profilRisiko = $this->cachedTksProfilRisiko($kodebpr, $periodeId);
        if ($profilRisiko) {
            $tksData['nprofilrisiko'] = $profilRisiko['nilaiperingkat'] ?? null;
            $tksData['nprofilrisikobefore'] = $profilRisiko['nilaiperingkatbefore'] ?? null;
            $tksData['nfaktorprofilrisiko'] = $profilRisiko['nilaifaktor'] ?? null;
            $tksData['nfaktorprofilrisikobefore'] = $profilRisiko['nilaifaktorbefore'] ?? null;
            $tksData['kesimpulan2'] = $profilRisiko['analisis'] ?? null;
        }

        $tataKelola = $this->cachedTksTataKelola($kodebpr, $periodeId);
        if ($tataKelola) {
            $tksData['ntatakelola'] = $tataKelola['nilaiperingkat'] ?? null;
            $tksData['ntatakelolabefore'] = $tataKelola['nilaiperingkatbefore'] ?? null;
            $tksData['nfaktortakol'] = $tataKelola['nilaifaktor'] ?? null;
            $tksData['nfaktortakolbefore'] = $tataKelola['nilaifaktorbefore'] ?? null;
            $tksData['kesimpulan3'] = $tataKelola['analisis'] ?? null;
        }

        $rentabilitas = $this->cachedTksRentabilitas($kodebpr, $periodeId);
        if ($rentabilitas) {
            $tksData['nrentabilitas'] = $rentabilitas['peringkatrentabilitas'] ?? null;
            $tksData['nrentabilitasbefore'] = $rentabilitas['peringkatrentabilitasbefore'] ?? null;
            $tksData['nfaktorrentabilitas'] = $rentabilitas['nilaifaktor'] ?? null;
            $tksData['nfaktorrentabilitasbefore'] = $rentabilitas['nilaifaktorbefore'] ?? null;
            $tksData['kesimpulan4'] = $rentabilitas['analisarentabilitas'] ?? null;
        }

        $permodalan = $this->cachedTksPermodalan($kodebpr, $periodeId);
        if ($permodalan) {
            $tksData['npermodalan'] = $permodalan['penilaianpermodalan'] ?? null;
            $tksData['npermodalanbefore'] = $permodalan['penilaianpermodalanbefore'] ?? null;
            $tksData['nfaktorpermodalan'] = $permodalan['nilaifaktor'] ?? null;
            $tksData['nfaktorpermodalanbefore'] = $permodalan['nilaifaktorbefore'] ?? null;
            $tksData['kesimpulan5'] = $permodalan['analisispermodalan'] ?? null;
        }

        return $tksData;
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

        $periodeDetail = $this->cachedPeriodeDetail($this->periodeId);
        $bprData = $this->infobprModel->getBprByKode($this->userKodebpr);
        $namaBpr = preg_replace('/[^A-Za-z0-9\-]/', '_', $bprData['namabpr']);

        $tksData = $this->getTksData($this->userKodebpr, $this->periodeId);
        $kompositData = $this->calculateKompositTKS($tksData);
        $faktorTKSData = $this->getFaktorTKSData($this->userKodebpr, $this->periodeId);

        $riskCalculations = $this->calculateAllRiskValues($this->userKodebpr, $this->periodeId);
        $riskbelumCalculations = $this->calculatebelumAllRiskValues($this->userKodebpr, $this->periodeId);

        $dataToSave = array_merge(
            [
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId,
                'tahun' => $periodeDetail['tahun'] ?? null,
            ],
            $this->extractCalculationResults($riskCalculations),
            $this->extractCalculationbelumResults($riskbelumCalculations),
            $tksData,
            $kompositData
        );

        $this->showtksModel->simpanProfilRisiko($dataToSave);
        $this->cacheInvalidate("showtks_{$this->userKodebpr}_{$this->periodeId}");

        $riskFactors = $this->getRiskFactorsForDisplay($this->userKodebpr, $this->periodeId, $riskCalculations);
        $risbelumkFactors = $this->getbelumRiskFactorsForDisplay($this->userKodebpr, $this->periodeId, $riskbelumCalculations);

        $showtks = $this->cachedShowtks($this->userKodebpr, $this->periodeId);
        if ($showtks && !empty($showtks['tanggal'])) {
            $showtks['tanggal_display'] = $this->formatDateForDisplay($showtks['tanggal']);
        } else {
            $showtks['tanggal_display'] = '';
        }

        $data = [
            'judul' => 'Laporan Tingkat Kesehatan Bank (TKS)',
            'bprData' => $bprData,
            'periodeDetail' => $periodeDetail,
            'showtks' => $showtks,
            'namabpr' => $namaBpr,
            'factors' => $riskFactors,
            'belumfactors' => $risbelumkFactors,
            'riskCalculations' => $riskCalculations,
            'riskbelumCalculations' => $riskbelumCalculations,
            'transparan' => [],
            'nilaiData' => [],
            'allShowTks' => [],
            'kategori' => $periodeDetail['kategori'] ?? null,
            'kompositData' => $kompositData,
            'faktorTKSData' => $faktorTKSData,
        ];

        return view('templates/v_header', $data)
            . view('templates/v_sidebar')
            . view('templates/v_topbar')
            . view('Laporansemester/Tks/showtks', $data)
            . view('templates/v_footer');
    }

    private function calculateKompositTKS(array $tksData): array
    {
        $fields = ['nfaktorprofilrisiko', 'nfaktortakol', 'nfaktorrentabilitas', 'nfaktorpermodalan'];
        $sum = 0;
        $count = 0;
        foreach ($fields as $f) {
            if (isset($tksData[$f]) && is_numeric($tksData[$f])) {
                $sum += $tksData[$f];
                $count++;
            }
        }
        $nkomposittks = $count > 0 ? $sum : null;
        $peringkatkomposittks = $this->getPeringkatKomposit($nkomposittks) ?? 0;

        $fieldsBefore = ['nfaktorprofilrisikobefore', 'nfaktortakolbefore', 'nfaktorrentabilitasbefore', 'nfaktorpermodalanbefore'];
        $sumB = 0;
        $countB = 0;
        foreach ($fieldsBefore as $f) {
            if (isset($tksData[$f]) && is_numeric($tksData[$f])) {
                $sumB += $tksData[$f];
                $countB++;
            }
        }
        $nkomposittksbefore = $countB > 0 ? $sumB : null;
        $peringkatkomposittksbefore = $this->getPeringkatKomposit($nkomposittksbefore) ?? 0;

        return compact('nkomposittks', 'nkomposittksbefore', 'peringkatkomposittks', 'peringkatkomposittksbefore');
    }

    private function getPeringkatKomposit($nilai)
    {
        if ($nilai === null || !is_numeric($nilai))
            return null;
        if ($nilai >= 1.00 && $nilai <= 1.50)
            return 1;
        if ($nilai >= 1.51 && $nilai <= 2.60)
            return 2;
        if ($nilai >= 2.61 && $nilai <= 3.40)
            return 3;
        if ($nilai >= 3.41 && $nilai <= 4.20)
            return 4;
        if ($nilai >= 4.21 && $nilai <= 5.00)
            return 5;
        return null;
    }

    private function extractCalculationResults(array $calc): array
    {
        return [
            'nkredit' => $calc['nkredit'],
            'kesimpulan1' => $calc['kesimpulan1'],
            'noperasional' => $calc['noperasional'],
            'kesimpulan2' => $calc['kesimpulan2'],
            'nkepatuhan' => $calc['nkepatuhan'],
            'kesimpulan3' => $calc['kesimpulan3'],
            'nlikuiditas' => $calc['nlikuiditas'],
            'kesimpulan4' => $calc['kesimpulan4'],
            'nreputasi' => $calc['nreputasi'],
            'kesimpulan5' => $calc['kesimpulan5'],
            'nstratejik' => $calc['nstratejik'],
            'kesimpulan6' => $calc['kesimpulan6'],
            'ntotalrisk' => $calc['ntotalrisk'],
        ];
    }

    private function extractCalculationbelumResults(array $calc): array
    {
        return [
            'nbelumkredit' => $calc['nbelumkredit'],
            'kesimpulan1' => $calc['kesimpulan1'],
            'nbelumoperasional' => $calc['nbelumoperasional'],
            'kesimpulan2' => $calc['kesimpulan2'],
            'nbelumkepatuhan' => $calc['nbelumkepatuhan'],
            'kesimpulan3' => $calc['kesimpulan3'],
            'nbelumlikuiditas' => $calc['nbelumlikuiditas'],
            'kesimpulan4' => $calc['kesimpulan4'],
            'nbelumreputasi' => $calc['nbelumreputasi'],
            'kesimpulan5' => $calc['kesimpulan5'],
            'nbelumstratejik' => $calc['nbelumstratejik'],
            'kesimpulan6' => $calc['kesimpulan6'],
            'nbelumtotalrisk' => $calc['nbelumtotalrisk'],
        ];
    }

    private function getRiskMatrix(int $inheren, int $kpmr): array
    {
        static $matrix = [
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

        return $matrix["{$inheren}-{$kpmr}"] ?? ['nilai' => null, 'kesimpulan' => ''];
    }

    private function getNilaiRisiko($model, int $faktorId, string $kodebpr, $periodeId)
    {
        $key = get_class($model) . "_{$faktorId}_{$kodebpr}_{$periodeId}";
        if (($v = $this->cacheGet($key)) !== null)
            return $v;

        $data = $model->where('faktor1id', $faktorId)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        return $this->cacheSet($key, $data['penilaiankredit'] ?? null);
    }

    private function calculateAllRiskValues(string $kodebpr, $periodeId, bool $force = false): array
    {
        $results = array_fill_keys(
            [
                'nkredit',
                'kesimpulan1',
                'noperasional',
                'kesimpulan2',
                'nkepatuhan',
                'kesimpulan3',
                'nlikuiditas',
                'kesimpulan4',
                'nreputasi',
                'kesimpulan5',
                'nstratejik',
                'kesimpulan6',
                'ntotalrisk'
            ],
            null
        );

        foreach ($this->riskModelsConfig as $config) {
            $nilaiInheren = $this->getNilaiRisiko($config['inheren']['model'], $config['inheren']['faktor'], $kodebpr, $periodeId);
            $nilaiKpmr = $this->getNilaiRisiko($config['kpmr']['model'], $config['kpmr']['faktor'], $kodebpr, $periodeId);

            if (is_numeric($nilaiInheren) && is_numeric($nilaiKpmr)) {
                $mr = $this->getRiskMatrix((int) $nilaiInheren, (int) $nilaiKpmr);
                $results[$config['result_key']] = $mr['nilai'];
                $results[$config['kesimpulan_key']] = $mr['kesimpulan'];
            }
        }

        $existing = $this->cachedShowtks($kodebpr, $periodeId);
        $riskKeys = ['nkredit', 'noperasional', 'nkepatuhan', 'nlikuiditas', 'nreputasi', 'nstratejik'];
        $changed = false;
        foreach ($riskKeys as $k) {
            if (!isset($existing[$k]) || $existing[$k] != $results[$k]) {
                $changed = true;
                break;
            }
        }

        $results['ntotalrisk'] = ($changed || $force)
            ? $this->calculateTotalRisk($results)
            : ($existing['ntotalrisk'] ?? null);

        return $results;
    }

    private function calculatebelumAllRiskValues(string $kodebpr, $periodeId): array
    {
        $results = array_fill_keys(
            [
                'nbelumkredit',
                'kesimpulan1',
                'nbelumoperasional',
                'kesimpulan2',
                'nbelumkepatuhan',
                'kesimpulan3',
                'nbelumlikuiditas',
                'kesimpulan4',
                'nbelumreputasi',
                'kesimpulan5',
                'nbelumstratejik',
                'kesimpulan6',
                'nbelumtotalrisk'
            ],
            null
        );

        foreach ($this->riskModelsConfig as $config) {
            $nilaiInheren = $this->getNilaiRisiko($config['beluminheren']['model'], $config['beluminheren']['faktor'], $kodebpr, $periodeId);
            $nilaiKpmr = $this->getNilaiRisiko($config['belumkpmr']['model'], $config['belumkpmr']['faktor'], $kodebpr, $periodeId);

            if (is_numeric($nilaiInheren) && is_numeric($nilaiKpmr)) {
                $mr = $this->getRiskMatrix((int) $nilaiInheren, (int) $nilaiKpmr);
                $results[$config['result_belumkey']] = $mr['nilai'];
                $results[$config['kesimpulan_key']] = $mr['kesimpulan'];
            }
        }

        $results['nbelumtotalrisk'] = $this->calculatebelumTotalRisk($results);
        return $results;
    }

    private function formatDateForDisplay(string $date): string
    {
        if (empty($date) || $date === '0000-00-00')
            return '';
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date))
            return $date;
        $obj = \DateTime::createFromFormat('Y-m-d', $date);
        return $obj ? $obj->format('d/m/Y') : '';
    }

    private function convertDateFormat(?string $date): ?string
    {
        if (empty($date))
            return null;
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date))
            return $date;
        $obj = \DateTime::createFromFormat('d/m/Y', $date)
            ?: \DateTime::createFromFormat('Y-m-d', $date);
        return $obj ? $obj->format('Y-m-d') : null;
    }

    private function calculateTotalRisk(array $results)
    {
        $periodeDetail = $this->cachedPeriodeDetail($this->periodeId);
        $isKategoriA = strtoupper($periodeDetail['kategori'] ?? '') === 'A';

        $keys = ['nkredit', 'noperasional', 'nkepatuhan', 'nlikuiditas'];
        if ($isKategoriA) {
            $keys[] = 'nreputasi';
            $keys[] = 'nstratejik';
        }

        $sum = 0;
        $count = 0;
        foreach ($keys as $k) {
            if (is_numeric($results[$k] ?? null)) {
                $sum += $results[$k];
                $count++;
            }
        }

        if ($count === 0)
            return null;
        $avg = $sum / $count;
        return ($avg - floor($avg) >= 0.5) ? ceil($avg) : floor($avg);
    }

    private function calculatebelumTotalRisk(array $results)
    {
        $periodeDetail = $this->cachedPeriodeDetail($this->periodeId);
        $isKategoriA = strtoupper($periodeDetail['kategori'] ?? '') === 'A';

        $keys = ['nbelumkredit', 'nbelumoperasional', 'nbelumkepatuhan', 'nbelumlikuiditas'];
        if ($isKategoriA) {
            $keys[] = 'nbelumreputasi';
            $keys[] = 'nbelumstratejik';
        }

        $sum = 0;
        $count = 0;
        foreach ($keys as $k) {
            if (is_numeric($results[$k] ?? null)) {
                $sum += $results[$k];
                $count++;
            }
        }

        if ($count === 0)
            return null;
        $avg = $sum / $count;
        return ($avg - floor($avg) >= 0.5) ? ceil($avg) : floor($avg);
    }

    private function getRiskFactorsForDisplay(string $kodebpr, $periodeId, array $riskCalculations): array
    {
        $factors = [];
        foreach ($this->riskModelsConfig as $key => $config) {
            // Re-use cached getNilaiRisiko results (model already queried above)
            $inherenVal = $this->getNilaiRisiko($config['inheren']['model'], $config['inheren']['faktor'], $kodebpr, $periodeId);
            $kpmrVal = $this->getNilaiRisiko($config['kpmr']['model'], $config['kpmr']['faktor'], $kodebpr, $periodeId);

            // Fetch full row only for metadata fields not already cached
            $inherenKey = get_class($config['inheren']['model']) . "_row_{$config['inheren']['faktor']}_{$kodebpr}_{$periodeId}";
            if (($inherenData = $this->cacheGet($inherenKey)) === null) {
                $inherenData = $config['inheren']['model']
                    ->where('faktor1id', $config['inheren']['faktor'])
                    ->where('kodebpr', $kodebpr)
                    ->where('periode_id', $periodeId)
                    ->first();
                $this->cacheSet($inherenKey, $inherenData);
            }

            $factors[] = [
                'name' => $config['name'],
                'link' => base_url($config['link']),
                'nfaktor' => $inherenVal ?? 'N/A',
                'nfaktor_kpmr' => $kpmrVal ?? 'N/A',
                'nkredit' => $riskCalculations[$config['result_key']] ?? 'N/A',
                'kesimpulan' => $riskCalculations[$config['kesimpulan_key']] ?? '',
                'accdekom' => $inherenData['accdekom'] ?? null,
                'is_approved' => $inherenData['is_approved'] ?? null,
                'risk_type' => $key,
            ];
        }
        return $factors;
    }

    private function getbelumRiskFactorsForDisplay(string $kodebpr, $periodeId, array $riskbelumCalculations): array
    {
        $factors = [];
        foreach ($this->riskModelsConfig as $key => $config) {
            $inherenVal = $this->getNilaiRisiko($config['beluminheren']['model'], $config['beluminheren']['faktor'], $kodebpr, $periodeId);
            $kpmrVal = $this->getNilaiRisiko($config['belumkpmr']['model'], $config['belumkpmr']['faktor'], $kodebpr, $periodeId);

            $inherenKey = get_class($config['beluminheren']['model']) . "_row_{$config['beluminheren']['faktor']}_{$kodebpr}_{$periodeId}";
            if (($inherenData = $this->cacheGet($inherenKey)) === null) {
                $inherenData = $config['beluminheren']['model']
                    ->where('faktor1id', $config['beluminheren']['faktor'])
                    ->where('kodebpr', $kodebpr)
                    ->where('periode_id', $periodeId)
                    ->first();
                $this->cacheSet($inherenKey, $inherenData);
            }

            $factors[] = [
                'name' => $config['name'],
                'link' => base_url($config['link']),
                'nbelumfaktor' => $inherenVal ?? 'N/A',
                'nbelumfaktor_kpmr' => $kpmrVal ?? 'N/A',
                'nbelumtingkatrisk' => $riskbelumCalculations[$config['result_belumkey']] ?? 'N/A',
                'kesimpulan' => $riskbelumCalculations[$config['kesimpulan_key']] ?? '',
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

        if (empty($id))
            return redirect()->back()->with('err', 'ID tidak ditemukan');
        if (!is_numeric($ntotalrisk) || $ntotalrisk < 1 || $ntotalrisk > 5) {
            return redirect()->back()->with('err', 'Nilai total risiko harus antara 1-5');
        }

        try {
            $this->showtksModel->update($id, ['ntotalrisk' => (int) $ntotalrisk, 'updated_at' => date('Y-m-d H:i:s')]);
            return redirect()->to(base_url('Showtks'))->with('message', 'Nilai Total Risiko berhasil diupdate menjadi ' . $ntotalrisk);
        } catch (\Exception $e) {
            return redirect()->back()->with('err', 'Gagal update data: ' . $e->getMessage());
        }
    }

    public function resetNtotalrisk()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;
        if ($redirect = $this->checkPeriode())
            return $redirect;

        $id = $this->request->getPost('id');
        if (empty($id))
            return redirect()->back()->with('err', 'ID tidak ditemukan');

        try {
            $existingData = $this->showtksModel->find($id);
            if (!$existingData)
                return redirect()->back()->with('err', 'Data tidak ditemukan');

            $riskCalculations = $this->calculateAllRiskValues($existingData['kodebpr'], $existingData['periode_id'], true);
            $this->showtksModel->update($id, ['ntotalrisk' => $riskCalculations['ntotalrisk'], 'updated_at' => date('Y-m-d H:i:s')]);

            return redirect()->to(base_url('Showtks'))
                ->with('message', 'Nilai Total Risiko berhasil direset ke perhitungan otomatis: ' . $riskCalculations['ntotalrisk']);
        } catch (\Exception $e) {
            return redirect()->back()->with('err', 'Gagal reset data: ' . $e->getMessage());
        }
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
            'negatifhasil' => $this->request->getPost('negatifhasil'),
        ];
        $this->showtksModel->update($id, $data);
        return redirect()->to(base_url('Showtks'))->with('message', 'Data berhasil diupdate!');
    }

    public function updatettd()
    {
        $id = $this->request->getPost('id');
        $data = [
            'dirut' => $this->request->getPost('dirut'),
            'dirkep' => $this->request->getPost('dirkep'),
            'tanggal' => $this->request->getPost('tanggal'),
            'lokasi' => $this->request->getPost('lokasi'),
        ];
        $this->showtksModel->update($id, $data);
        return redirect()->to(base_url('Showtks'))->with('message', 'Data berhasil diupdate!');
    }

    public function updatecover()
    {
        $id = $this->request->getPost('id');
        if (empty($id))
            return redirect()->to(base_url('Showtks'))->with('error', 'ID tidak ditemukan!');
        $this->showtksModel->update($id, ['cover' => $this->request->getPost('cover')]);
        return redirect()->to(base_url('Showtks'))->with('message', 'Data berhasil diupdate!');
    }


    public function ajaxUpdateTtd()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        $id = $this->request->getPost('id');
        if (empty($id))
            return $this->response->setJSON(['success' => false, 'message' => 'ID tidak ditemukan']);

        try {
            $data = [
                'dirut' => $this->request->getPost('dirut'),
                'dirkep' => $this->request->getPost('dirkep'),
                'pe' => $this->request->getPost('pe'),
                'tanggal' => $this->convertDateFormat($this->request->getPost('tanggal')),
                'lokasi' => $this->request->getPost('lokasi'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $this->showtksModel->update($id, $data);
            return $this->response->setJSON(['success' => true, 'message' => 'Semua data berhasil disimpan']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => 'Gagal menyimpan data: ' . $e->getMessage()]);
        }
    }

    public function ajaxSaveKesimpulan()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request method']);
        }

        $id = $this->request->getPost('id');
        $kesimpulan = $this->request->getPost('kesimpulan');

        if (empty($id))
            return $this->response->setJSON(['success' => false, 'message' => 'ID tidak ditemukan']);
        if ($kesimpulan === null)
            return $this->response->setJSON(['success' => false, 'message' => 'Data kesimpulan tidak valid']);

        try {
            $existing = $this->showtksModel->find($id);
            if (!$existing)
                return $this->response->setJSON(['success' => false, 'message' => 'Data tidak ditemukan dengan ID: ' . $id]);

            $this->showtksModel->update($id, ['kesimpulan' => $kesimpulan, 'updated_at' => date('Y-m-d H:i:s')]);
            return $this->response->setJSON(['success' => true, 'message' => 'Kesimpulan berhasil disimpan']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
        }
    }

    public function ajaxAutoSaveField()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        $id = $this->request->getPost('id');
        $field = $this->request->getPost('field');
        $value = $this->request->getPost('value');

        if (empty($id) || empty($field)) {
            return $this->response->setJSON(['success' => false, 'message' => 'ID atau field tidak valid']);
        }

        $allowedFields = ['dirut', 'dirkep', 'pe', 'tanggal', 'lokasi'];
        if (!in_array($field, $allowedFields, true)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Field tidak diizinkan: ' . $field]);
        }

        try {
            if (!$this->showtksModel->find($id)) {
                return $this->response->setJSON(['success' => false, 'message' => 'Data tidak ditemukan']);
            }

            if ($field === 'tanggal' && !empty($value)) {
                $value = $this->convertDateFormat($value);
            }

            $this->showtksModel->update($id, [$field => $value, 'updated_at' => date('Y-m-d H:i:s')]);
            return $this->response->setJSON(['success' => true, 'message' => 'Data berhasil disimpan']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    public function LaporanPDFTKS()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;
        if ($redirect = $this->checkPeriode())
            return $redirect;

        if (!$this->userKodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid.');
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = $this->periodeId;

        $bprData = $this->infobprModel->getBprByKode($kodebpr);
        $periodeDetail = $this->cachedPeriodeDetail($periodeId);
        $showtks = $this->cachedShowtks($kodebpr, $periodeId);

        $tksData = $this->getTksData($kodebpr, $periodeId);
        $kompositData = $this->calculateKompositTKS($tksData);
        $faktorTKSData = $this->getFaktorTKSData($kodebpr, $periodeId);

        $rentabilitas = $this->cachedTksRentabilitas($kodebpr, $periodeId);
        $permodalan = $this->cachedTksPermodalan($kodebpr, $periodeId);

        // Tentukan bulan posisi laporan & posisi sebelumnya berdasarkan semester
        $semester = (string) ($periodeDetail['semester'] ?? '1');
        $tahun = $periodeDetail['tahun'] ?? date('Y');
        $isGanjil = ($semester === '1' || strtolower($semester) === 'ganjil');

        $bulanCurrent = $isGanjil ? 'Juni' : 'Desember';
        if ($isGanjil) {
            $bulanSebelumnya = 'Desember';
            $tahunSebelumnya = $tahun - 1;
        } else {
            $bulanSebelumnya = 'Juni';
            $tahunSebelumnya = $tahun;
        }

        $pdfData = [
            'bpr' => [
                'namabpr' => $bprData['namabpr'] ?? '',
                'alamat' => $bprData['alamat'] ?? '',
                'nomor' => $bprData['nomor'] ?? '',
                'webbpr' => $bprData['webbpr'] ?? '',
                'email' => $bprData['email'] ?? '',
                'logo' => $this->resolveLogoForPdf($bprData['logo'] ?? ''),
            ],
            'periode' => [
                'bulan' => $bulanCurrent,
                'tahun' => $tahun,
                'periodeSebelumBulan' => $bulanSebelumnya,
                'periodeSebelumTahun' => $tahunSebelumnya,
            ],
            'tks' => [
                'profilrisiko' => [
                    'peringkatCurrent' => $faktorTKSData['profil_risiko']['nilai_peringkat'] ?? '',
                    'nilaifaktorCurrent' => $faktorTKSData['profil_risiko']['nilai_faktor'] ?? '',
                    'peringkatPrevious' => $faktorTKSData['profil_risiko']['nilai_peringkat_before'] ?? '',
                    'nilaifaktorPrevious' => $faktorTKSData['profil_risiko']['nilai_faktor_before'] ?? '',
                ],
                'tatakelola' => [
                    'peringkatCurrent' => $faktorTKSData['tata_kelola']['nilai_peringkat'] ?? '',
                    'nilaifaktorCurrent' => $faktorTKSData['tata_kelola']['nilai_faktor'] ?? '',
                    'peringkatPrevious' => $faktorTKSData['tata_kelola']['nilai_peringkat_before'] ?? '',
                    'nilaifaktorPrevious' => $faktorTKSData['tata_kelola']['nilai_faktor_before'] ?? '',
                ],
                'rentabilitas' => [
                    'peringkatCurrent' => $faktorTKSData['rentabilitas']['nilai_peringkat'] ?? '',
                    'nilaifaktorCurrent' => $faktorTKSData['rentabilitas']['nilai_faktor'] ?? '',
                    'peringkatPrevious' => $faktorTKSData['rentabilitas']['nilai_peringkat_before'] ?? '',
                    'nilaifaktorPrevious' => $faktorTKSData['rentabilitas']['nilai_faktor_before'] ?? '',
                    'roa' => [
                        'rasioCurrent' => $rentabilitas['rasioroa'] ?? '',
                        'peringkatCurrent' => $rentabilitas['peringkatroa'] ?? '',
                        'rasioPrevious' => $rentabilitas['rasioroabefore'] ?? '',
                        'peringkatPrevious' => $rentabilitas['peringkatroabefore'] ?? '',
                    ],
                    'bopo' => [
                        'rasioCurrent' => $rentabilitas['rasiobopo'] ?? '',
                        'peringkatCurrent' => $rentabilitas['peringkatbopo'] ?? '',
                        'rasioPrevious' => $rentabilitas['rasiobopobefore'] ?? '',
                        'peringkatPrevious' => $rentabilitas['peringkatbopobefore'] ?? '',
                    ],
                    'nim' => [
                        'rasioCurrent' => $rentabilitas['rasionim'] ?? '',
                        'peringkatCurrent' => $rentabilitas['peringkatnim'] ?? '',
                        'rasioPrevious' => $rentabilitas['rasionimbefore'] ?? '',
                        'peringkatPrevious' => $rentabilitas['peringkatnimbefore'] ?? '',
                    ],
                ],
                'permodalan' => [
                    'peringkatCurrent' => $faktorTKSData['permodalan']['nilai_peringkat'] ?? '',
                    'nilaifaktorCurrent' => $faktorTKSData['permodalan']['nilai_faktor'] ?? '',
                    'peringkatPrevious' => $faktorTKSData['permodalan']['nilai_peringkat_before'] ?? '',
                    'nilaifaktorPrevious' => $faktorTKSData['permodalan']['nilai_faktor_before'] ?? '',
                    'kpmm' => [
                        'rasioCurrent' => $permodalan['rasiokpmm'] ?? '',
                        'peringkatCurrent' => $permodalan['peringkatkpmm'] ?? '',
                        'rasioPrevious' => $permodalan['rasiokpmmbefore'] ?? '',
                        'peringkatPrevious' => $permodalan['peringkatkpmmbefore'] ?? '',
                    ],
                    'miapb' => [
                        'rasioCurrent' => $permodalan['rasiomiapb'] ?? '',
                        'peringkatCurrent' => $permodalan['peringkatmiapb'] ?? '',
                        'rasioPrevious' => $permodalan['rasiomiapbbefore'] ?? '',
                        'peringkatPrevious' => $permodalan['peringkatmiapbbefore'] ?? '',
                    ],
                ],
                'komposit' => [
                    'nilaiCurrent' => $kompositData['nkomposittks'] ?? '',
                    'nilaiPrevious' => $kompositData['nkomposittksbefore'] ?? '',
                    'peringkatCurrent' => $kompositData['peringkatkomposittks'] ?? '',
                    'peringkatPrevious' => $kompositData['peringkatkomposittksbefore'] ?? '',
                ],
            ],
            'analisis' => [
                'kesimpulan' => $showtks['kesimpulan'] ?? '',
                'profilrisiko' => $faktorTKSData['profil_risiko']['analisis'] ?? '',
                'tatakelola' => $faktorTKSData['tata_kelola']['analisis'] ?? '',
                'rentabilitas' => $faktorTKSData['rentabilitas']['analisis'] ?? '',
                'permodalan' => $faktorTKSData['permodalan']['analisis'] ?? '',
            ],
            'profil' => [
                'tanggal' => $showtks['tanggal'] ?? date('Y-m-d'),
                'lokasi' => $showtks['lokasi'] ?? '',
                'dirkep' => $showtks['dirkep'] ?? '',
                'pe' => $showtks['pe'] ?? '',
            ],
        ];

        try {
            $generator = new LaporanTKSPdfGenerator();
            $pdfOutput = $generator->setData($pdfData)->generate();

            $namaBpr = preg_replace('/[^A-Za-z0-9\-]/', '_', $bprData['namabpr'] ?? 'BPR');
            $filename = "TKS_{$namaBpr}_{$tahun}_{$semester}.pdf";

            return service('response')
                ->setHeader('Content-Type', 'application/pdf')
                ->setHeader('Content-Disposition', "inline; filename=\"{$filename}\"")
                ->setBody($pdfOutput);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membuat PDF: ' . $e->getMessage());
        }
    }

    private function resolveLogoForPdf(?string $logoFilename): string
    {
        if (empty($logoFilename)) {
            return '';
        }

        $path = FCPATH . 'asset/img/' . $logoFilename;

        if (!is_file($path)) {
            return '';
        }

        $imageData = file_get_contents($path);
        if ($imageData === false) {
            return '';
        }

        $mime = mime_content_type($path) ?: 'image/png';
        return 'data:' . $mime . ';base64,' . base64_encode($imageData);
    }

    public function exporttxtlapprofrisk()
    {
        if (!$this->auth->check()) {
            return redirect()->to(session('redirect_url') ?? '/login');
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        $profilTks = $this->cachedShowtks($kodebpr, $periodeId);
        $profilResiko = $this->showprofilresikoModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();
        $periodeDetail = $this->cachedPeriodeDetail($periodeId);

        $modalinti = $periodeDetail['modalinti'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];
        $kategori = $periodeDetail['kategori'];
        $tanggal = new \DateTime($profilTks['tanggal']);
        $titleDate = $tanggal->format('Ymd');
        $exportDate = $tanggal->format('Y-m-d');

        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);
        $sandibpr = $data_infobpr[0]['sandibpr'] ?? '';
        $kodejenis = $data_infobpr[0]['kodejenis'] ?? '';

        // Prefetch all needed rows in one block
        $kreditInheren = $this->kreditinherenModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->where('faktor1id', 13)->first();
        $kreditKpmr = $this->kreditkpmrModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->where('faktor1id', 33)->first();
        $kreditbelumInheren = $this->kreditinherenModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->where('faktor1id', 14)->first();
        $kreditbelumKpmr = $this->kreditkpmrModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->where('faktor1id', 34)->first();

        $operasionalInheren = $this->operasionalinherenModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->where('faktor1id', 48)->first();
        $operasionalKpmr = $this->operasionalkpmrModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->where('faktor1id', 70)->first();
        $opBelumInheren = $this->operasionalinherenModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->where('faktor1id', 49)->first();
        $opBelumKpmr = $this->operasionalkpmrModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->where('faktor1id', 71)->first();

        $kepatuhanInheren = $this->kepatuhaninherenModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->where('faktor1id', 81)->first();
        $kepatuhanKpmr = $this->kepatuhankpmrModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->where('faktor1id', 102)->first();
        $kepBelumInheren = $this->kepatuhaninherenModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->where('faktor1id', 82)->first();
        $kepBelumKpmr = $this->kepatuhankpmrModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->where('faktor1id', 103)->first();

        $likuiditasInheren = $this->likuiditasinherenModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->where('faktor1id', 115)->first();
        $likuiditasKpmr = $this->likuiditaskpmrModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->where('faktor1id', 135)->first();
        $likBelumInheren = $this->likuiditasinherenModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->where('faktor1id', 116)->first();
        $likBelumKpmr = $this->likuiditaskpmrModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->where('faktor1id', 136)->first();

        $reputasiInheren = $this->reputasiinherenModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->where('faktor1id', 148)->first();
        $reputasiKpmr = $this->reputasikpmrModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->where('faktor1id', 168)->first();
        $repBelumInheren = $this->reputasiinherenModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->where('faktor1id', 149)->first();
        $repBelumKpmr = $this->reputasikpmrModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->where('faktor1id', 169)->first();

        $stratejikInheren = $this->stratejikinherenModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->where('faktor1id', 179)->first();
        $stratejikKpmr = $this->stratejikkpmrModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->where('faktor1id', 199)->first();
        $strBelumInheren = $this->stratejikinherenModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->where('faktor1id', 180)->first();
        $strBelumKpmr = $this->stratejikkpmrModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->where('faktor1id', 200)->first();

        $s = fn($v) => $v !== null ? str_replace(["\r", "\n"], ' ', $v) : '0';

        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0000|{$modalinti}|{$kategori}||\r\n";
        $output .= "D01|0001|{$s($kreditInheren['penilaiankredit'] ?? null)}|{$s($kreditKpmr['penilaiankredit'] ?? null)}|{$s($profilResiko['nkredit'] ?? null)}|{$s($kreditbelumInheren['penilaiankredit'] ?? null)}|{$s($kreditbelumKpmr['penilaiankredit'] ?? null)}|{$s($profilResiko['nbelumkredit'] ?? null)}\r\n";
        $output .= "D01|0002|{$s($operasionalInheren['penilaiankredit'] ?? null)}|{$s($operasionalKpmr['penilaiankredit'] ?? null)}|{$s($profilTks['noperasional'] ?? null)}|{$s($opBelumInheren['penilaiankredit'] ?? null)}|{$s($opBelumKpmr['penilaiankredit'] ?? null)}|{$s($profilTks['nbelumoperasional'] ?? null)}\r\n";
        $output .= "D01|0003|{$s($kepatuhanInheren['penilaiankredit'] ?? null)}|{$s($kepatuhanKpmr['penilaiankredit'] ?? null)}|{$s($profilTks['nkepatuhan'] ?? null)}|{$s($kepBelumInheren['penilaiankredit'] ?? null)}|{$s($kepBelumKpmr['penilaiankredit'] ?? null)}|{$s($profilTks['nbelumkepatuhan'] ?? null)}\r\n";
        $output .= "D01|0004|{$s($likuiditasInheren['penilaiankredit'] ?? null)}|{$s($likuiditasKpmr['penilaiankredit'] ?? null)}|{$s($profilTks['nlikuiditas'] ?? null)}|{$s($likBelumInheren['penilaiankredit'] ?? null)}|{$s($likBelumKpmr['penilaiankredit'] ?? null)}|{$s($profilTks['nbelumlikuiditas'] ?? null)}\r\n";
        $output .= "D01|0005|{$s($reputasiInheren['penilaiankredit'] ?? null)}|{$s($reputasiKpmr['penilaiankredit'] ?? null)}|{$s($profilTks['nreputasi'] ?? null)}|{$s($repBelumInheren['penilaiankredit'] ?? null)}|{$s($repBelumKpmr['penilaiankredit'] ?? null)}|{$s($profilTks['nbelumreputasi'] ?? null)}\r\n";
        $output .= "D01|0006|{$s($stratejikInheren['penilaiankredit'] ?? null)}|{$s($stratejikKpmr['penilaiankredit'] ?? null)}|{$s($profilTks['nstratejik'] ?? null)}|{$s($strBelumInheren['penilaiankredit'] ?? null)}|{$s($strBelumKpmr['penilaiankredit'] ?? null)}|{$s($profilTks['nbelumstratejik'] ?? null)}\r\n";
        $output .= "D01|0000|||{$s($profilTks['ntotalrisk'] ?? null)}|||{$s($profilTks['nbelumtotalrisk'] ?? null)}\r\n";
        $output .= "F01|{$this->sanitizeText($profilTks['kesimpulan'] ?? '')}\r\n";

        $filename = "TKSBPRKS-0000-R-S-{$titleDate}-{$sandibpr}-01.txt";
        return service('response')
            ->setHeader('Content-Type', 'text/plain')
            ->setHeader('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->setBody($output);
    }

    public function exporttxtriskinheren()
    {
        if (!$this->auth->check())
            return redirect()->to(session('redirect_url') ?? '/login');

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        $profilTks = $this->cachedShowtks($kodebpr, $periodeId);
        $profilResiko = $this->showprofilresikoModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();
        $periodeDetail = $this->cachedPeriodeDetail($periodeId);
        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        $modalinti = $periodeDetail['modalinti'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];
        $kategori = $periodeDetail['kategori'];
        $tanggal = new \DateTime($profilTks['tanggal']);
        $titleDate = $tanggal->format('Ymd');
        $exportDate = $tanggal->format('Y-m-d');
        $sandibpr = $data_infobpr[0]['sandibpr'] ?? '';
        $kodejenis = $data_infobpr[0]['kodejenis'] ?? '';

        $kreditInheren = $this->kreditinherenModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->where('faktor1id', 13)->first();
        $kreditKpmr = $this->kreditkpmrModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->where('faktor1id', 33)->first();

        $s = fn($v) => $v !== null ? str_replace(["\r", "\n"], ' ', $v) : '';

        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0100|{$modalinti}|{$kategori}||\r\n";
        $output .= "D01|1100|{$s($profilResiko['nkredit'] ?? null)}|{$s($profilResiko['kesimpulan1'] ?? null)}\r\n";
        $output .= "D01|1200|{$s($kreditInheren['penilaiankredit'] ?? null)}|{$s($kreditInheren['keterangan'] ?? null)}\r\n";
        $output .= "D01|1300|{$s($kreditKpmr['penilaiankredit'] ?? null)}|{$s($kreditKpmr['keterangan'] ?? null)}\r\n";

        $filename = "TKSBPRKS-0100-R-S-{$titleDate}-{$sandibpr}-01.txt";
        return service('response')
            ->setHeader('Content-Type', 'text/plain')
            ->setHeader('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->setBody($output);
    }

    public function exporttxtoperasional()
    {
        if (!$this->auth->check())
            return redirect()->to(session('redirect_url') ?? '/login');
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');
        $profilTks = $this->cachedShowtks($kodebpr, $periodeId);
        $profilResiko = $this->showprofilresikoModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();
        $periodeDetail = $this->cachedPeriodeDetail($periodeId);
        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);
        $modalinti = $periodeDetail['modalinti'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];
        $kategori = $periodeDetail['kategori'];
        $tanggal = new \DateTime($profilTks['tanggal']);
        $titleDate = $tanggal->format('Ymd');
        $exportDate = $tanggal->format('Y-m-d');
        $sandibpr = $data_infobpr[0]['sandibpr'] ?? '';
        $kodejenis = $data_infobpr[0]['kodejenis'] ?? '';
        $opInheren = $this->operasionalinherenModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->where('faktor1id', 48)->first();
        $opKpmr = $this->operasionalkpmrModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->where('faktor1id', 70)->first();
        $s = fn($v) => $v !== null ? str_replace(["\r", "\n"], ' ', $v) : '';
        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0200|{$modalinti}|{$kategori}||0\r\n";
        $output .= "D01|2100|{$s($profilResiko['noperasional'] ?? null)}|{$s($profilResiko['kesimpulan2'] ?? null)}\r\n";
        $output .= "D01|2200|{$s($opInheren['penilaiankredit'] ?? null)}|{$s($opInheren['keterangan'] ?? null)}\r\n";
        $output .= "D01|2300|{$s($opKpmr['penilaiankredit'] ?? null)}|{$s($opKpmr['keterangan'] ?? null)}\r\n";
        $filename = "TKSBPRKS-0200-R-S-{$titleDate}-{$sandibpr}-01.txt";
        return service('response')->setHeader('Content-Type', 'text/plain')->setHeader('Content-Disposition', "attachment; filename=\"{$filename}\"")->setBody($output);
    }

    public function exporttxtkepatuhan()
    {
        if (!$this->auth->check())
            return redirect()->to(session('redirect_url') ?? '/login');
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');
        $profilTks = $this->cachedShowtks($kodebpr, $periodeId);
        $profilResiko = $this->showprofilresikoModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();
        $periodeDetail = $this->cachedPeriodeDetail($periodeId);
        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);
        $modalinti = $periodeDetail['modalinti'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];
        $kategori = $periodeDetail['kategori'];
        $tanggal = new \DateTime($profilTks['tanggal']);
        $titleDate = $tanggal->format('Ymd');
        $exportDate = $tanggal->format('Y-m-d');
        $sandibpr = $data_infobpr[0]['sandibpr'] ?? '';
        $kodejenis = $data_infobpr[0]['kodejenis'] ?? '';
        $kepInheren = $this->kepatuhaninherenModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->where('faktor1id', 81)->first();
        $kepKpmr = $this->kepatuhankpmrModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->where('faktor1id', 102)->first();
        $s = fn($v) => $v !== null ? str_replace(["\r", "\n"], ' ', $v) : '';
        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0300|{$modalinti}|{$kategori}||0\r\n";
        $output .= "D01|3100|{$s($profilResiko['nkepatuhan'] ?? null)}|{$s($profilResiko['kesimpulan3'] ?? null)}\r\n";
        $output .= "D01|3200|{$s($kepInheren['penilaiankredit'] ?? null)}|{$s($kepInheren['keterangan'] ?? null)}\r\n";
        $output .= "D01|3300|{$s($kepKpmr['penilaiankredit'] ?? null)}|{$s($kepKpmr['keterangan'] ?? null)}\r\n";
        $filename = "TKSBPRKS-0300-R-S-{$titleDate}-{$sandibpr}-01.txt";
        return service('response')->setHeader('Content-Type', 'text/plain')->setHeader('Content-Disposition', "attachment; filename=\"{$filename}\"")->setBody($output);
    }

    public function exporttxtlikuiditas()
    {
        if (!$this->auth->check())
            return redirect()->to(session('redirect_url') ?? '/login');
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');
        $profilTks = $this->cachedShowtks($kodebpr, $periodeId);
        $profilResiko = $this->showprofilresikoModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();
        $periodeDetail = $this->cachedPeriodeDetail($periodeId);
        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);
        $modalinti = $periodeDetail['modalinti'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];
        $kategori = $periodeDetail['kategori'];
        $tanggal = new \DateTime($profilTks['tanggal']);
        $titleDate = $tanggal->format('Ymd');
        $exportDate = $tanggal->format('Y-m-d');
        $sandibpr = $data_infobpr[0]['sandibpr'] ?? '';
        $kodejenis = $data_infobpr[0]['kodejenis'] ?? '';
        $likInheren = $this->likuiditasinherenModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->where('faktor1id', 115)->first();
        $likKpmr = $this->likuiditaskpmrModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->where('faktor1id', 135)->first();
        $s = fn($v) => $v !== null ? str_replace(["\r", "\n"], ' ', $v) : '';
        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0400|{$modalinti}|{$kategori}||0\r\n";
        $output .= "D01|4100|{$s($profilResiko['nlikuiditas'] ?? null)}|{$s($profilResiko['kesimpulan4'] ?? null)}\r\n";
        $output .= "D01|4200|{$s($likInheren['penilaiankredit'] ?? null)}|{$s($likInheren['keterangan'] ?? null)}\r\n";
        $output .= "D01|4300|{$s($likKpmr['penilaiankredit'] ?? null)}|{$s($likKpmr['keterangan'] ?? null)}\r\n";
        $filename = "TKSBPRKS-0400-R-S-{$titleDate}-{$sandibpr}-01.txt";
        return service('response')->setHeader('Content-Type', 'text/plain')->setHeader('Content-Disposition', "attachment; filename=\"{$filename}\"")->setBody($output);
    }

    public function exporttxtreputasi()
    {
        if (!$this->auth->check())
            return redirect()->to(session('redirect_url') ?? '/login');
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');
        $profilTks = $this->cachedShowtks($kodebpr, $periodeId);
        $profilResiko = $this->showprofilresikoModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();
        $periodeDetail = $this->cachedPeriodeDetail($periodeId);
        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);
        $modalinti = $periodeDetail['modalinti'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];
        $kategori = $periodeDetail['kategori'];
        $tanggal = new \DateTime($profilTks['tanggal']);
        $titleDate = $tanggal->format('Ymd');
        $exportDate = $tanggal->format('Y-m-d');
        $sandibpr = $data_infobpr[0]['sandibpr'] ?? '';
        $kodejenis = $data_infobpr[0]['kodejenis'] ?? '';
        $repInheren = $this->reputasiinherenModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->where('faktor1id', 148)->first();
        $repKpmr = $this->reputasikpmrModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->where('faktor1id', 168)->first();
        $s = fn($v) => $v !== null ? str_replace(["\r", "\n"], ' ', $v) : '0';
        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0500|{$modalinti}|{$kategori}||0\r\n";
        $output .= "D01|5100|{$s($profilResiko['nreputasi'] ?? null)}|{$s($profilResiko['kesimpulan5'] ?? null)}\r\n";
        $output .= "D01|5200|{$s($repInheren['penilaiankredit'] ?? null)}|{$s($repInheren['keterangan'] ?? null)}\r\n";
        $output .= "D01|5300|{$s($repKpmr['penilaiankredit'] ?? null)}|{$s($repKpmr['keterangan'] ?? null)}\r\n";
        $filename = "TKSBPRKS-0500-R-S-{$titleDate}-{$sandibpr}-01.txt";
        return service('response')->setHeader('Content-Type', 'text/plain')->setHeader('Content-Disposition', "attachment; filename=\"{$filename}\"")->setBody($output);
    }

    public function exporttxtstratejik()
    {
        if (!$this->auth->check())
            return redirect()->to(session('redirect_url') ?? '/login');
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');
        $profilTks = $this->cachedShowtks($kodebpr, $periodeId);
        $profilResiko = $this->showprofilresikoModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();
        $periodeDetail = $this->cachedPeriodeDetail($periodeId);
        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);
        $modalinti = $periodeDetail['modalinti'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];
        $kategori = $periodeDetail['kategori'];
        $tanggal = new \DateTime($profilTks['tanggal']);
        $titleDate = $tanggal->format('Ymd');
        $exportDate = $tanggal->format('Y-m-d');
        $sandibpr = $data_infobpr[0]['sandibpr'] ?? '';
        $kodejenis = $data_infobpr[0]['kodejenis'] ?? '';
        $strInheren = $this->stratejikinherenModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->where('faktor1id', 179)->first();
        $strKpmr = $this->stratejikkpmrModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->where('faktor1id', 199)->first();
        $s = fn($v) => $v !== null ? str_replace(["\r", "\n"], ' ', $v) : '0';
        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0600|{$modalinti}|{$kategori}||0\r\n";
        $output .= "D01|6100|{$s($profilResiko['nstratejik'] ?? null)}|{$s($profilResiko['kesimpulan6'] ?? null)}\r\n";
        $output .= "D01|6200|{$s($strInheren['penilaiankredit'] ?? null)}|{$s($strInheren['keterangan'] ?? null)}\r\n";
        $output .= "D01|6300|{$s($strKpmr['penilaiankredit'] ?? null)}|{$s($strKpmr['keterangan'] ?? null)}\r\n";
        $filename = "TKSBPRKS-0600-R-S-{$titleDate}-{$sandibpr}-01.txt";
        return service('response')->setHeader('Content-Type', 'text/plain')->setHeader('Content-Disposition', "attachment; filename=\"{$filename}\"")->setBody($output);
    }

    public function exporttxttks()
    {
        if (!$this->auth->check())
            return redirect()->to(session('redirect_url') ?? '/login');

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        $profilTks = $this->cachedShowtks($kodebpr, $periodeId);
        $periodeDetail = $this->cachedPeriodeDetail($periodeId);
        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        $tksrentabilitas = $this->tksrentabilitasModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();
        $tkspermodalan = $this->tkspermodalanModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();

        $modalinti = $periodeDetail['modalinti'];
        $nomorsurat = $periodeDetail['nomorsurat'];
        $jeniskoreksi = $periodeDetail['jeniskoreksi'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];
        $kategori = $periodeDetail['kategori'];
        $tahun = $periodeDetail['tahun'] ?? date('Y');
        $semester = $periodeDetail['semester'] ?? '';
        $exportDate = (strtolower($semester) === 'ganjil' || $semester === '1') ? "{$tahun}-06-30" : "{$tahun}-12-31";
        $titleDate = str_replace('-', '', $exportDate);
        $sandibpr = $data_infobpr[0]['sandibpr'] ?? '';
        $kodejenis = $data_infobpr[0]['kodejenis'] ?? '';

        $t = fn($v) => $this->sanitizeText($v ?? ' ');

        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0700|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";
        $output .= "D01|7100||{$t($profilTks['nprofilrisiko'])}|25.00|{$t($profilTks['nfaktorprofilrisiko'])}||{$t($profilTks['nprofilrisikobefore'])}|25.00|{$t($profilTks['nfaktorprofilrisikobefore'])}\r\n";
        $output .= "D01|7200||{$t($profilTks['ntatakelola'])}|30.00|{$t($profilTks['nfaktortakol'])}||{$t($profilTks['ntatakelolabefore'])}|30.00|{$t($profilTks['nfaktortakolbefore'])}\r\n";
        $output .= "D01|7300||{$t($profilTks['nrentabilitas'])}|15.00|{$t($profilTks['nfaktorrentabilitas'])}||{$t($profilTks['nrentabilitasbefore'])}|15.00|{$t($profilTks['nfaktorrentabilitasbefore'])}\r\n";
        $output .= "D01|7310|{$t($tksrentabilitas['rasioroa'])}|{$t($tksrentabilitas['peringkatroa'])}|||{$t($tksrentabilitas['rasioroabefore'])}|{$t($tksrentabilitas['peringkatroabefore'])}||\r\n";
        $output .= "D01|7311|{$t($tksrentabilitas['rasiobopo'])}|{$t($tksrentabilitas['peringkatbopo'])}|||{$t($tksrentabilitas['rasiobopobefore'])}|{$t($tksrentabilitas['peringkatbopobefore'])}||\r\n";
        $output .= "D01|7312|{$t($tksrentabilitas['rasionim'])}|{$t($tksrentabilitas['peringkatnim'])}|||{$t($tksrentabilitas['rasionimbefore'])}|{$t($tksrentabilitas['peringkatnimbefore'])}||\r\n";
        $output .= "D01|7400||{$t($profilTks['npermodalan'])}|30.00|{$t($profilTks['nfaktorpermodalan'])}||{$t($profilTks['npermodalanbefore'])}|30.00|{$t($profilTks['nfaktorpermodalanbefore'])}\r\n";
        $output .= "D01|7410|{$t($tkspermodalan['rasiokpmm'])}|{$t($tkspermodalan['peringkatkpmm'])}|||{$t($tkspermodalan['rasiokpmmbefore'])}|{$t($tkspermodalan['peringkatkpmmbefore'])}||\r\n";
        $output .= "D01|7411|{$t($tkspermodalan['rasiomiapb'])}|{$t($tkspermodalan['peringkatmiapb'])}|||{$t($tkspermodalan['rasiomiapbbefore'])}|{$t($tkspermodalan['peringkatmiapbbefore'])}||\r\n";
        $output .= "D01|7500||||{$t($profilTks['nkomposittks'])}||||{$t($profilTks['nkomposittksbefore'])}\r\n";
        $output .= "D01|7600||||{$t($profilTks['peringkatkomposittks'])}||||{$t($profilTks['peringkatkomposittksbefore'])}\r\n";
        $output .= "F01|{$t($profilTks['kesimpulan'])}\r\n";
        $output .= "F02|{$t($profilTks['kesimpulan2'])}\r\n";
        $output .= "F03|{$t($profilTks['kesimpulan3'])}\r\n";
        $output .= "F04|{$t($profilTks['kesimpulan4'])}\r\n";
        $output .= "F05|{$t($profilTks['kesimpulan5'])}\r\n";

        $filename = "TKSBPRKS-0700-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";
        return service('response')
            ->setHeader('Content-Type', 'text/plain')
            ->setHeader('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->setBody($output);
    }
}