<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_periodeprofilresiko;
use App\Models\M_periodetks;
use App\Models\M_showprofilresiko;
use App\Models\M_showtks;
use App\Models\M_infobpr;
use App\Models\M_user;
use Myth\Auth\Config\Services as AuthServices;

class Tksprofilresiko extends Controller
{
    protected $auth;
    protected $session;
    protected $userKodebpr;
    protected $userId;
    protected $periodeId;

    // Models
    protected $periodeModel;
    protected $showprofilresikoModel;

    protected $userModel;
    protected $infobprModel;

    protected $userGroups = [];

    public function __construct()
    {
        $this->infobprModel = new M_infobpr();
        $this->periodeModel = new M_periodeprofilresiko();
        $this->periodetksModel = new M_periodetks();
        $this->showprofilresikoModel = new M_showprofilresiko();
        $this->showtksModel = new M_showtks();
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
            'judul' => 'Penilaian Faktor Profil Risiko',
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
            . view('tksprofilrisiko/index', $data)
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
}