<?php

namespace App\Controllers;

use App\Models\M_periodesemesteran;
use CodeIgniter\Controller;
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
use App\Models\M_nilaifaktor;
use App\Models\M_nilaifaktor2;
use App\Models\M_nilaifaktor3;
use App\Models\M_nilaifaktor4;
use App\Models\M_nilaifaktor5;
use App\Models\M_nilaifaktor6;
use App\Models\M_nilaifaktor7;
use App\Models\M_nilaifaktor8;
use App\Models\M_nilaifaktor9;
use App\Models\M_nilaifaktor10;
use App\Models\M_nilaifaktor11;
use App\Models\M_nilaifaktor12;
use App\Models\M_formasisdm;
use App\Models\M_ketentuanbpr;
use App\Models\M_sosialisasipelatihan;
use App\Models\M_budayakepatuhan;
use App\Models\M_penyimpangankepatuhan;
use App\Models\M_komitmenotoritas;
use App\Models\M_lingkupaudit;
use App\Models\M_temuanaudit;
use App\Models\M_kesimpulanaudit;
use App\Models\M_rekomendasiaudit;
use App\Models\M_pemantauanaudit;
use App\Models\M_tksprofilrisiko;
use App\Models\M_tkstatakelola;
use App\Models\M_tksrentabilitas;
use App\Models\M_tkspermodalan;
use App\Models\M_infobpr;
use App\Models\M_user;
use App\Models\M_showprofilresiko;
use App\Models\M_showfaktor;
use App\Models\M_showdirymf;
use App\Models\M_showaudit;
use App\Models\M_showtks;
use Myth\Auth\Config\Services as AuthServices;

class Showsemesteran extends Controller
{
    protected $nilaiModel;
    protected $nilai2Model;
    protected $nilai3Model;
    protected $nilai4Model;
    protected $nilai5Model;
    protected $nilai6Model;
    protected $nilai7Model;
    protected $nilai8Model;
    protected $nilai9Model;
    protected $nilai10Model;
    protected $nilai11Model;
    protected $nilai12Model;

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

    protected $formasisdmModel;
    protected $ketentuanbprModel;
    protected $budayakepatuhanModel;
    protected $penyimpanganModel;
    protected $komitmenModel;
    protected $sosialisasipelatihanModel;

    protected $lingkupauditModel;
    protected $temuanauditModel;
    protected $kesimpulanauditModel;
    protected $rekomendasiauditModel;
    protected $pemantauanauditModel;

    protected $tkstatakelolaModel;
    protected $tksprofilrisikoModel;
    protected $tksrentabilitasModel;
    protected $tkspermodalanModel;

    protected $showprofilrisikoModel;
    protected $showfaktorModel;
    protected $showdirymfModel;
    protected $showauditModel;
    protected $showtksModel;
    protected $infobprModel;
    protected $periodeModel;
    protected $userModel;
    protected $session;
    protected $auth;
    protected $userKodebpr;

    protected $userInGroupPE;
    protected $userInGroupAdmin;
    protected $userInGroupDekom;
    protected $userInGroupDireksi;

    private const CACHE_PREFIX = 'showsemesteran_';
    private const CACHE_TTL = 120;

    public function __construct()
    {
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

        $this->nilaiModel = new M_nilaifaktor();
        $this->nilai2Model = new M_nilaifaktor2();
        $this->nilai3Model = new M_nilaifaktor3();
        $this->nilai4Model = new M_nilaifaktor4();
        $this->nilai5Model = new M_nilaifaktor5();
        $this->nilai6Model = new M_nilaifaktor6();
        $this->nilai7Model = new M_nilaifaktor7();
        $this->nilai8Model = new M_nilaifaktor8();
        $this->nilai9Model = new M_nilaifaktor9();
        $this->nilai10Model = new M_nilaifaktor10();
        $this->nilai11Model = new M_nilaifaktor11();
        $this->nilai12Model = new M_nilaifaktor12();

        $this->formasisdmModel = new M_formasisdm();
        $this->ketentuanbprModel = new M_ketentuanbpr();
        $this->sosialisasipelatihanModel = new M_sosialisasipelatihan();
        $this->budayakepatuhanModel = new M_budayakepatuhan();
        $this->penyimpanganModel = new M_penyimpangankepatuhan();
        $this->komitmenModel = new M_komitmenotoritas();

        $this->lingkupauditModel = new M_lingkupaudit();
        $this->temuanauditModel = new M_temuanaudit();
        $this->kesimpulanauditModel = new M_kesimpulanaudit();
        $this->rekomendasiauditModel = new M_rekomendasiaudit();
        $this->pemantauanauditModel = new M_pemantauanaudit();

        $this->tksprofilrisikoModel = new M_tksprofilrisiko();
        $this->tkstatakelolaModel = new M_tkstatakelola();
        $this->tksrentabilitasModel = new M_tksrentabilitas();
        $this->tkspermodalanModel = new M_tkspermodalan();

        $this->periodeModel = new M_periodesemesteran();
        $this->userModel = new M_user();
        $this->infobprModel = new M_infobpr();
        $this->showprofilrisikoModel = new M_showprofilresiko();
        $this->showfaktorModel = new M_showfaktor();
        $this->showdirymfModel = new M_showdirymf();
        $this->showauditModel = new M_showaudit();
        $this->showtksModel = new M_showtks();
        $this->session = service('session');
        $this->auth = service('authentication');

        helper('text');

        if ($this->auth->isLoggedIn()) {
            $userId = $this->auth->id();
            $user = $this->userModel->find($userId);
            $this->userKodebpr = $user['kodebpr'] ?? null;

            $authorize = AuthServices::authorization();
            $this->userInGroupPE = $authorize->inGroup('pe', $userId);
            $this->userInGroupAdmin = $authorize->inGroup('admin', $userId);
            $this->userInGroupDekom = $authorize->inGroup('dekom', $userId);
            $this->userInGroupDireksi = $authorize->inGroup('direksi', $userId);
        } else {
            $this->userKodebpr = null;
            $this->userInGroupPE = false;
            $this->userInGroupAdmin = false;
            $this->userInGroupDekom = false;
            $this->userInGroupDireksi = false;
        }
    }

    private function checkAuth(): ?\CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->auth->check()) {
            session()->set('redirect_url', current_url());
            return redirect()->to('/login');
        }
        if (!session('active_periode')) {
            return redirect()->to('/periodesemesteran')
                ->with('error', 'Silakan pilih periode aktif terlebih dahulu.');
        }
        return null;
    }

    private function fetchApprovals(string $kodebpr, int $periodeId): array
    {
        $periodeDetail = $this->periodeModel->find($periodeId);
        $kategori = strtoupper($periodeDetail['kategori'] ?? 'A');

        $approvalKredit = $this->kreditinherenModel->where('faktor1id', 13)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['is_approved'] ?? 0;
        $approvalOperasional = $this->operasionalinherenModel->where('faktor1id', 48)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['is_approved'] ?? 0;
        $approvalKepatuhan = $this->kepatuhaninherenModel->where('faktor1id', 81)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['is_approved'] ?? 0;
        $approvalLikuiditas = $this->likuiditasinherenModel->where('faktor1id', 115)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['is_approved'] ?? 0;

        if ($kategori === 'A') {
            $approvalReputasi = $this->reputasiinherenModel->where('faktor1id', 148)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['is_approved'] ?? 0;
            $approvalStratejik = $this->stratejikinherenModel->where('faktor1id', 179)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['is_approved'] ?? 0;

            $allRisikoApproved = $approvalKredit && $approvalOperasional && $approvalKepatuhan
                && $approvalLikuiditas && $approvalReputasi && $approvalStratejik;
        } else {
            // Kategori B: hanya 4 risiko wajib (Reputasi & Stratejik tidak dinilai)
            $allRisikoApproved = $approvalKredit && $approvalOperasional && $approvalKepatuhan
                && $approvalLikuiditas;
        }

        // GCG
        $nilaiRows = [
            $this->nilaiModel->where('faktor1id', 12)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first(),
            $this->nilai2Model->where('faktor2id', 29)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first(),
            $this->nilai3Model->where('faktor3id', 27)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first(),
            $this->nilai4Model->where('faktor4id', 12)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first(),
            $this->nilai5Model->where('faktor5id', 6)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first(),
            $this->nilai6Model->where('faktor6id', 10)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first(),
            $this->nilai7Model->where('faktor7id', 12)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first(),
            $this->nilai8Model->where('faktor8id', 6)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first(),
            $this->nilai9Model->where('faktor9id', 18)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first(),
            $this->nilai10Model->where('faktor10id', 6)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first(),
            $this->nilai11Model->where('faktor11id', 13)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first(),
            $this->nilai12Model->where('faktor12id', 8)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first(),
        ];

        $allGcgApproved = true;
        foreach ($nilaiRows as $row) {
            if (($row['is_approved'] ?? 0) != 1) {
                $allGcgApproved = false;
                break;
            }
        }

        // Dirymf
        $dirymfFormasisdm = (new M_formasisdm())->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();
        $dirymfKetentuan = (new M_ketentuanbpr())->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();
        $dirymfSosialisasi = (new M_sosialisasipelatihan())->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();
        $dirymfBudaya = (new M_budayakepatuhan())->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();
        $dirymfPenyimpangan = (new M_penyimpangankepatuhan())->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();
        $dirymfKomitmen = (new M_komitmenotoritas())->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();

        $allDirymfApproved =
            ($dirymfFormasisdm['accdir2'] ?? 0) == 1 &&
            ($dirymfKetentuan['accdir2'] ?? 0) == 1 &&
            ($dirymfSosialisasi['accdir2'] ?? 0) == 1 &&
            ($dirymfBudaya['accdir2'] ?? 0) == 1 &&
            ($dirymfPenyimpangan['accdir2'] ?? 0) == 1 &&
            ($dirymfKomitmen['accdir2'] ?? 0) == 1;

        $auditLingkup = (new M_lingkupaudit())->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();
        $auditTemuan = (new M_temuanaudit())->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();
        $auditKesimpulan = (new M_kesimpulanaudit())->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();
        $auditRekomendasi = (new M_rekomendasiaudit())->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();
        $auditPemantauan = (new M_pemantauanaudit())->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();

        $allAuditApproved =
            ($auditLingkup['accdir2'] ?? 0) == 1 &&
            ($auditTemuan['accdir2'] ?? 0) == 1 &&
            ($auditKesimpulan['accdir2'] ?? 0) == 1 &&
            ($auditRekomendasi['accdir2'] ?? 0) == 1 &&
            ($auditPemantauan['accdir2'] ?? 0) == 1;

        // TKS
        $tksProfilRisikoRow = $this->tksprofilrisikoModel->getByKodebprAndPeriode($kodebpr, $periodeId);
        $tksTataKelolaRow = $this->tkstatakelolaModel->getByKodebprAndPeriode($kodebpr, $periodeId);
        $tksRentabilitasRow = $this->tksrentabilitasModel->getByKodebprAndPeriode($kodebpr, $periodeId);
        $tksPermodalanRow = $this->tkspermodalanModel->getByKodebprAndPeriode($kodebpr, $periodeId);

        $allTksApproved =
            ($tksProfilRisikoRow['is_approved'] ?? 0) == 1 &&
            ($tksTataKelolaRow['is_approved'] ?? 0) == 1 &&
            ($tksRentabilitasRow['is_approved'] ?? 0) == 1 &&
            ($tksPermodalanRow['is_approved'] ?? 0) == 1;

        return [
            'allRisikoApproved' => $allRisikoApproved,
            'allGcgApproved' => $allGcgApproved,
            'allDirymfApproved' => $allDirymfApproved,
            'allAuditApproved' => $allAuditApproved,
            'allTksApproved' => $allTksApproved,
            'nilaiRows' => $nilaiRows,
        ];
    }

    public function index()
    {
        if (!$this->auth->check()) {
            session()->set('redirect_url', current_url());
            return redirect()->to('/login');
        }

        if (!session('active_periode')) {
            return redirect()->to('/periodesemesteran')->with('error', 'Silakan pilih periode aktif terlebih dahulu.');
        }

        $periodeId = (int) session('active_periode');
        $kodebpr = $this->userKodebpr;

        if (!$kodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid.');
        }

        $cache = \Config\Services::cache();
        $cacheKey = self::CACHE_PREFIX . md5($kodebpr . '_' . $periodeId . '_index');
        $cached = $cache->get($cacheKey);

        if ($cached !== null) {
            $showfaktor = $this->showfaktorModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();
            $allShowFaktor = $this->showfaktorModel->findAll();
            $cached['showfaktor'] = $showfaktor;
            $cached['allShowFaktor'] = $allShowFaktor;

            return view('templates/v_header', $cached)
                . view('templates/v_sidebar')
                . view('templates/v_topbar')
                . view('Laporansemester/showsemesteran', $cached)
                . view('templates/v_footer');
        }

        $approvals = $this->fetchApprovals($kodebpr, $periodeId);
        $nilaiRows = $approvals['nilaiRows'];
        $periodeDetail = $this->periodeModel->find($periodeId);
        $bprData = $this->infobprModel->getBprByKode($kodebpr);
        $showfaktor = $this->showfaktorModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();
        $allShowFaktor = $this->showfaktorModel->findAll();

        $factors = [
            [
                'name' => 'Laporan Profil Risiko',
                'link' => base_url('Showprofilresiko') . '?modal_nilai=12',
                'nfaktor' => $nilaiRows[0]['nfaktor'] ?? 'N/A',
                'accdekom' => $nilaiRows[0]['accdekom'] ?? null,
                'is_approved' => $approvals['allRisikoApproved'] ? 1 : 0,
            ],
            [
                'name' => 'Self Assessment Tata Kelola (GCG)',
                'link' => base_url('ShowFaktor') . '?modal_nilai=29',
                'nfaktor2' => $nilaiRows[1]['nfaktor2'] ?? 'N/A',
                'accdekom' => $nilaiRows[1]['accdekom'] ?? null,
                'is_approved' => $approvals['allGcgApproved'] ? 1 : 0,
            ],
            [
                'name' => 'Laporan Pokok Pelaksanaan Tugas Anggota Direksi yang Membawahkan Fungsi Kepatuhan',
                'link' => base_url('Showdirymf'),
                'nfaktor3' => 'N/A',
                'accdekom' => null,
                'is_approved' => $approvals['allDirymfApproved'] ? 1 : 0,
            ],
            [
                'name' => 'Laporan Pelaksanaan dan Pokok Hasil Audit Intern',
                'link' => base_url('Showaudit') . '?modal_nilai=12',
                'nfaktor4' => 'N/A',
                'accdekom' => null,
                'is_approved' => $approvals['allAuditApproved'] ? 1 : 0,
            ],
            [
                'name' => 'Laporan Tingkat Kesehatan Bank (TKS)',
                'link' => base_url('Showtks') . '?modal_nilai=6',
                'nfaktor5' => 'N/A',
                'accdekom' => null,
                'is_approved' => $approvals['allTksApproved'] ? 1 : 0,
            ],
        ];

        $totalNilai = 0;
        $validCount = 0;
        $validFactors = 12;
        $nfaktorKeys = [
            'nfaktor',
            'nfaktor2',
            'nfaktor3',
            'nfaktor4',
            'nfaktor5',
            'nfaktor6',
            'nfaktor7',
            'nfaktor8',
            'nfaktor9',
            'nfaktor10',
            'nfaktor11',
            'nfaktor12'
        ];

        foreach ($factors as $factor) {
            foreach ($nfaktorKeys as $key) {
                if (isset($factor[$key]) && is_numeric($factor[$key]) && $factor[$key] != 'N/A' && $factor[$key] != 0) {
                    $totalNilai += $factor[$key];
                    $validCount++;
                    break;
                }
            }
        }

        $divisor = ($validFactors === 12 && $validCount < 12) ? ($validFactors - 1) : $validFactors;
        $nilaikomposit = $validCount > 0 ? $totalNilai / $divisor : 0;
        $nilaikomposit = ($nilaikomposit - floor($nilaikomposit)) >= 0.5 ? ceil($nilaikomposit) : floor($nilaikomposit);

        $peringkatkomposit = $this->hitungPeringkatKomposit($nilaikomposit);
        $colorClass = $this->getColorClassForPeringkat($peringkatkomposit);

        foreach ($factors as $index => $factor) {
            $factor['accdekom_status'] = isset($factor['accdekom']) && $factor['accdekom'] == 1
                ? 'Telah disetujui oleh Komisaris Utama'
                : 'Belum disetujui oleh Komisaris Utama';
            $factor['direksi_status'] = isset($factor['is_approved']) && $factor['is_approved'] == 1
                ? 'Telah disetujui oleh Direktur Utama'
                : 'Belum disetujui oleh Direktur Utama';
            $factors[$index] = $factor;
        }

        (new M_showfaktor())->simpanShowFaktor([
            'kodebpr' => $kodebpr,
            'periode_id' => $periodeId,
            'nilaikomposit' => $nilaikomposit,
            'peringkatkomposit' => $peringkatkomposit,
            'semester' => $periodeDetail['semester'] ?? 'N/A',
            'tahun' => $periodeDetail['tahun'] ?? 'N/A',
        ]);

        $data = [
            'judul' => 'Pelaporan Semesteran',
            'namabpr' => $bprData['namabpr'] ?? 'Nama BPR Tidak Ditemukan',
            'periode_semester' => $periodeDetail['semester'] ?? 'N/A',
            'periode_tahun' => $periodeDetail['tahun'] ?? 'N/A',
            'factors' => $factors,
            'nilaikomposit' => $nilaikomposit,
            'peringkatkomposit' => $peringkatkomposit,
            'colorClass' => $colorClass,
            'showfaktor' => $showfaktor,
            'allShowFaktor' => $allShowFaktor,
        ];

        $cacheable = $data;
        unset($cacheable['showfaktor'], $cacheable['allShowFaktor']);
        $cache->save($cacheKey, $cacheable, self::CACHE_TTL);

        return view('templates/v_header', $data)
            . view('templates/v_sidebar')
            . view('templates/v_topbar')
            . view('Laporansemester/showsemesteran', $data)
            . view('templates/v_footer');
    }

    public function startExport()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        if (!$kodebpr) {
            return $this->response->setJSON(['error' => 'Kode BPR tidak valid.'])->setStatusCode(400);
        }

        $jobKey = 'zip_job_' . md5($kodebpr . '_' . $periodeId);
        $cache = \Config\Services::cache();

        if ($cache->get($jobKey . '_lock')) {
            $progress = $cache->get($jobKey . '_progress') ?? [];
            return $this->response->setJSON([
                'token' => $jobKey,
                'progress' => $progress,
                'message' => 'Job sudah berjalan.',
            ]);
        }

        $cache->save($jobKey . '_lock', true, 600);
        $cache->save($jobKey . '_progress', [
            'step' => 0,
            'total' => 100,
            'label' => 'Memulai proses...',
            'done' => false,
            'error' => null,
            'zipname' => null,
        ], 600);

        return $this->response->setJSON(['token' => $jobKey, 'started' => true]);
    }

    public function zipProgress(string $token = '')
    {
        if (!$this->auth->check()) {
            return $this->response->setJSON(['error' => 'Unauthorized'])->setStatusCode(401);
        }

        $cache = \Config\Services::cache();
        $progress = $cache->get($token . '_progress');

        if ($progress === null) {
            return $this->response->setJSON(['error' => 'Token tidak valid atau sudah kedaluwarsa.'])->setStatusCode(404);
        }

        return $this->response->setJSON($progress);
    }

    public function downloadZip(string $token = '')
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }

        $cache = \Config\Services::cache();
        $progress = $cache->get($token . '_progress');

        if (!$progress || empty($progress['zipname'])) {
            return redirect()->back()->with('error', 'File ZIP tidak ditemukan atau sudah kedaluwarsa.');
        }

        $zipFilePath = WRITEPATH . 'uploads/' . $progress['zipname'];
        if (!file_exists($zipFilePath)) {
            return redirect()->back()->with('error', 'File ZIP sudah dihapus atau tidak ditemukan.');
        }

        $fileContent = file_get_contents($zipFilePath);
        @unlink($zipFilePath);

        $cache->delete($token . '_progress');
        $cache->delete($token . '_lock');

        return $this->response
            ->setHeader('Content-Type', 'application/zip')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $progress['zipname'] . '"')
            ->setHeader('Content-Length', strlen($fileContent))
            ->setBody($fileContent);
    }

    private function addTxtToZip(string $controllerName, string $methodName, \ZipArchive $zip): void
    {
        try {
            $fqcn = 'App\\Controllers\\' . $controllerName;
            if (!class_exists($fqcn)) {
                log_message('error', "exportAllToZip: class {$fqcn} tidak ditemukan.");
                return;
            }
            $instance = new $fqcn();
            if (!method_exists($instance, $methodName)) {
                log_message('error', "exportAllToZip: method {$methodName} tidak ditemukan di {$fqcn}.");
                return;
            }

            ob_start();
            $result = $instance->$methodName();
            $buffered = ob_get_clean();

            $content = '';
            $fileName = strtolower($controllerName) . '_' . strtolower($methodName) . '.txt';

            if ($result instanceof \CodeIgniter\HTTP\Response) {
                $content = $result->getBody();
                $disposition = $result->getHeaderLine('Content-Disposition');
                if (!empty($disposition) && preg_match('/filename=["\']?([^"\';\s]+)["\']?/i', $disposition, $m)) {
                    $fileName = $m[1];
                }
            } elseif (!empty($buffered)) {
                $content = $buffered;
            }

            if (!empty($content)) {
                $zip->addFromString($fileName, $content);
            }
        } catch (\Throwable $e) {
            log_message('error', "exportAllToZip [{$controllerName}::{$methodName}]: " . $e->getMessage());
        }
    }

    public function exportAllToZip()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');
        $periodeDetail = $this->periodeModel->find($periodeId);
        $jenispelaporan = $periodeDetail['jenispelaporan'];

        if (!$kodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid.');
        }

        $jobKey = 'zip_job_' . md5($kodebpr . '_' . $periodeId);
        $cache = \Config\Services::cache();

        $updateProgress = function (int $step, string $label, bool $done = false, ?string $error = null, ?string $zipname = null) use ($cache, $jobKey) {
            $cache->save($jobKey . '_progress', [
                'step' => $step,
                'total' => 100,
                'label' => $label,
                'done' => $done,
                'error' => $error,
                'zipname' => $zipname,
            ], 600);
        };

        $updateProgress(2, 'Menyiapkan data BPR...');

        $showfaktor = $this->showfaktorModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $bprData = $this->infobprModel->getBprByKode($kodebpr);
        $namaBpr = trim(preg_replace('/\s+/', ' ', preg_replace('/[^A-Za-z0-9\s]/', '', $bprData['namabpr'] ?? 'BPR')));

        $zipDate = date('d-m-Y');
        if (!empty($showfaktor['tanggal'])) {
            try {
                $fmt = str_contains($showfaktor['tanggal'], '/') ? 'Y/m/d' : 'Y-m-d';
                $laporanDate = \DateTime::createFromFormat($fmt, $showfaktor['tanggal']);
                if ($laporanDate) {
                    $zipDate = $laporanDate->format('d-m-Y');
                }
            } catch (\Throwable $e) {
            }
        }

        $zipFileName = 'NBPSIMPEL - Laporan Semester - ' . $namaBpr . ' (' . $zipDate . ').zip';
        $zipFilePath = WRITEPATH . 'uploads/' . $zipFileName;

        if (!is_dir(WRITEPATH . 'uploads')) {
            mkdir(WRITEPATH . 'uploads', 0775, true);
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            $updateProgress(0, 'Gagal membuat file ZIP.', true, 'Gagal membuat file ZIP. Periksa permission direktori writable.');
            return redirect()->back()->with('error', 'Gagal membuat file ZIP. Periksa permission direktori writable.');
        }

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
            ['Faktor', 'exporttxtfaktor'],
            ['Faktor2', 'exporttxtfaktor2'],
            ['Faktor3', 'exporttxtfaktor3'],
            ['Faktor4', 'exporttxtfaktor4'],
            ['Faktor5', 'exporttxtfaktor5'],
            ['Faktor6', 'exporttxtfaktor6'],
            ['Faktor7', 'exporttxtfaktor7'],
            ['Faktor8', 'exporttxtfaktor8'],
            ['Faktor9', 'exporttxtfaktor9'],
            ['Faktor10', 'exporttxtfaktor10'],
            ['Faktor11', 'exporttxtfaktor11'],
            ['Faktor12', 'exporttxtfaktor12'],
            ['ShowFaktor', 'exporttxtshowfaktor'],
            ['Formasisdm', 'exporttxtformasisdm'],
            ['Ketentuanbpr', 'exporttxtketentuanbpr'],
            ['Sosialisasipelatihan', 'exporttxtsosialisasipelatihan'],
            ['Budayakepatuhan', 'exporttxtbudayakepatuhan'],
            ['Penyimpangankepatuhan', 'exporttxtpenyimpangankepatuhan'],
            ['Komitmenotoritas', 'exporttxtkomitmenotoritas'],
            ['Lingkupaudit', 'exporttxtlingkupaudit'],
            ['Temuanaudit', 'exporttxttemuanaudit'],
            ['Kesimpulanaudit', 'exporttxtkesimpulanaudit'],
            ['Rekomendasiaudit', 'exporttxtrekomendasiaudit'],
            ['Pemantauanaudit', 'exporttxtpemantauanaudit'],
            ['Showtks', 'exporttxttks'],
        ];

        $totalExports = count($exports);
        $progressStart = 5;
        $progressEnd = 60;

        foreach ($exports as $i => [$controller, $method]) {
            $pct = (int) ($progressStart + (($i + 1) / $totalExports) * ($progressEnd - $progressStart));
            $label = 'Mengemas data: ' . $controller . '/' . $method . ' (' . ($i + 1) . '/' . $totalExports . ')';
            $updateProgress($pct, $label);
            $this->addTxtToZip($controller, $method, $zip);
        }

        $updateProgress(62, 'Membuat PDF Self Assessment TKS...');
        // try {
        //     $pdfController = new \App\Controllers\Pdfself();
        //     $pdfContent    = $pdfController->generatePdfToBuffer();

        //     $dataInfobpr    = $this->infobprModel->getDataByKodebpr($kodebpr);
        //     $sandibpr       = $dataInfobpr[0]['sandibpr'] ?? '';
        //     $tahun          = $periodeDetail['tahun'] ?? date('Y');
        //     $semester       = $periodeDetail['semester'] ?? '';
        //     $tanggalLaporan = (strtolower($semester) === 'ganjil' || $semester === '1')
        //         ? "{$tahun}-06-30" : "{$tahun}-12-31";
        //     $tanggalFile    = str_replace('-', '', $tanggalLaporan);
        //     $pdfFilename    = "TKSBPRKS-1000-{$jenispelaporan}-S-{$tanggalFile}-{$sandibpr}-01.pdf";
        //     $zip->addFromString($pdfFilename, $pdfContent);
        // } catch (\Throwable $e) {
        //     log_message('error', '[exportAllToZip] Gagal generate PDF: ' . $e->getMessage());
        // }

        $updateProgress(72, 'Membuat PDF Laporan Profil Risiko...');
        try {
            $pdfGenerator = new \App\Libraries\LaporanProfilRisikoPdf();
            $pdfBytes = $pdfGenerator->generate($kodebpr, $periodeId);
            if (!empty($pdfBytes)) {
                $dataInfobpr = $this->infobprModel->getDataByKodebpr($kodebpr);
                $sandibpr = $dataInfobpr[0]['sandibpr'] ?? '';
                $tahun = $periodeDetail['tahun'] ?? date('Y');
                $semester = $periodeDetail['semester'] ?? '';
                $tanggalLaporan = (strtolower($semester) === 'ganjil' || $semester === '1')
                    ? "{$tahun}-06-30" : "{$tahun}-12-31";
                $tanggalFile = str_replace('-', '', $tanggalLaporan);
                $pdfFileName = "TKSBPRKS-0001-{$jenispelaporan}-S-{$tanggalFile}-{$sandibpr}-01.pdf";
                $zip->addFromString($pdfFileName, $pdfBytes);
            }
        } catch (\Throwable $e) {
            log_message('error', '[exportAllToZip] Gagal generate Laporan Profil Risiko PDF: ' . $e->getMessage());
        }

        $updateProgress(80, 'Menambahkan PDF Lembar Pengesahan...');
        try {
            $showfaktorRecord = $this->showfaktorModel
                ->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();
            $pdfLembarPersetujuanPath = WRITEPATH . 'uploads/';
            if (!empty($showfaktorRecord['pdf3_filename']) && file_exists($pdfLembarPersetujuanPath . $showfaktorRecord['pdf3_filename'])) {
                $pdfContent = file_get_contents($pdfLembarPersetujuanPath . $showfaktorRecord['pdf3_filename']);
                if (substr($pdfContent, 0, 4) === '%PDF') {
                    $dataInfobpr = $this->infobprModel->getDataByKodebpr($kodebpr);
                    $sandibpr = $dataInfobpr[0]['sandibpr'] ?? '';
                    $tahun = $periodeDetail['tahun'] ?? date('Y');
                    $semester = $periodeDetail['semester'] ?? '';
                    $tanggalLaporan = (strtolower($semester) === 'ganjil' || $semester === '1')
                        ? "{$tahun}-06-30" : "{$tahun}-12-31";
                    $tanggalFile = str_replace('-', '', $tanggalLaporan);
                    $pdfFilename = "TKSBPRKS-1013-{$jenispelaporan}-S-{$tanggalFile}-{$sandibpr}-01.pdf";
                    $zip->addFromString($pdfFilename, $pdfContent);
                }
            }
        } catch (\Throwable $e) {
            log_message('error', '[exportAllToZip] Gagal menambahkan PDF Lembar Pengesahan (1013) ke ZIP: ' . $e->getMessage());
        }

        $updateProgress(88, 'Menambahkan PDF Dirymf...');
        $this->addPdfDirymfToZip($zip, $kodebpr, $periodeId, $periodeDetail, $jenispelaporan, '2006');

        $updateProgress(93, 'Menambahkan PDF Audit Internal...');
        try {
            $showauditRecord = (new M_showaudit())
                ->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();
            $pdfAuditPath = WRITEPATH . 'uploads/pdf_auditinternal/';
            if (!empty($showauditRecord['pdfaudit_filename']) && file_exists($pdfAuditPath . $showauditRecord['pdfaudit_filename'])) {
                $pdfContent = file_get_contents($pdfAuditPath . $showauditRecord['pdfaudit_filename']);
                if (substr($pdfContent, 0, 4) === '%PDF') {
                    $dataInfobpr = $this->infobprModel->getDataByKodebpr($kodebpr);
                    $sandibpr = $dataInfobpr[0]['sandibpr'] ?? '';
                    $tahun = $periodeDetail['tahun'] ?? date('Y');
                    $semester = $periodeDetail['semester'] ?? '';
                    $tanggalLaporan = (strtolower($semester) === 'ganjil' || $semester === '1') ? "{$tahun}-06-30" : "{$tahun}-12-31";
                    $tanggalFile = str_replace('-', '', $tanggalLaporan);
                    $pdfFilename = "TKSBPRKS-3005-{$jenispelaporan}-S-{$tanggalFile}-{$sandibpr}-01.pdf";
                    $zip->addFromString($pdfFilename, $pdfContent);
                }
            }
        } catch (\Throwable $e) {
            log_message('error', '[exportAllToZip] Gagal menambahkan PDF Audit ke ZIP: ' . $e->getMessage());
        }

        $updateProgress(97, 'Menutup arsip ZIP...');
        $zip->close();

        if (!file_exists($zipFilePath)) {
            $updateProgress(0, 'File ZIP gagal dibuat.', true, 'File ZIP gagal dibuat.');
            return redirect()->back()->with('error', 'File ZIP gagal dibuat.');
        }

        $updateProgress(100, 'Selesai! File ZIP siap diunduh.', true, null, $zipFileName);
        $cache->delete($jobKey . '_lock');

        $fileContent = file_get_contents($zipFilePath);
        @unlink($zipFilePath);

        return $this->response
            ->setHeader('Content-Type', 'application/zip')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $zipFileName . '"')
            ->setHeader('Content-Length', strlen($fileContent))
            ->setBody($fileContent);
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
        $this->showfaktorModel->update($id, $data);

        $periodeId = session('active_periode');
        $kodebpr = $this->userKodebpr;
        \Config\Services::cache()->delete(self::CACHE_PREFIX . md5($kodebpr . '_' . $periodeId . '_index'));

        return redirect()->to(base_url('ShowFaktor'))->with('message', 'Data berhasil diupdate!');
    }

    public function updatettd()
    {
        $id = $this->request->getPost('id');
        $data = [
            'dirut' => $this->request->getPost('dirut'),
            'komut' => $this->request->getPost('komut'),
            'tanggal' => $this->request->getPost('tanggal'),
            'lokasi' => $this->request->getPost('lokasi'),
        ];
        $this->showfaktorModel->update($id, $data);

        $periodeId = session('active_periode');
        $kodebpr = $this->userKodebpr;
        \Config\Services::cache()->delete(self::CACHE_PREFIX . md5($kodebpr . '_' . $periodeId . '_index'));

        return redirect()->to(base_url('ShowFaktor'))->with('message', 'Data berhasil diupdate!');
    }

    private function hitungPeringkatKomposit($nilaikomposit): string
    {
        return match ((int) $nilaikomposit) {
            1 => 'Sangat Baik',
            2 => 'Baik',
            3 => 'Cukup',
            4 => 'Kurang Baik',
            5 => 'Buruk',
            default => 'Nilai komposit tidak valid',
        };
    }

    private function getColorClassForPeringkat(string $peringkatkomposit): string
    {
        return match ($peringkatkomposit) {
            'Sangat Baik' => 'text-success',
            'Baik' => 'text-info',
            'Cukup' => 'text-warning',
            'Kurang Baik' => 'text-danger',
            'Buruk' => 'text-dark',
            default => 'text-muted',
        };
    }

    private function addPdfDirymfToZip(
        \ZipArchive $zip,
        string $kodebpr,
        int $periodeId,
        array $periodeDetail,
        string $jenispelaporan,
        string $kodeDokumen
    ): void {
        try {
            $showdirymfRecord = (new M_showdirymf())
                ->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();
            $pdfDirymfPath = WRITEPATH . 'uploads/pdf_dirymf/';

            if (empty($showdirymfRecord['pdfdir_filename']) || !file_exists($pdfDirymfPath . $showdirymfRecord['pdfdir_filename'])) {
                return;
            }

            $pdfContent = file_get_contents($pdfDirymfPath . $showdirymfRecord['pdfdir_filename']);
            if (substr($pdfContent, 0, 4) !== '%PDF') {
                return;
            }

            $dataInfobpr = $this->infobprModel->getDataByKodebpr($kodebpr);
            $sandibpr = $dataInfobpr[0]['sandibpr'] ?? '';
            $tahun = $periodeDetail['tahun'] ?? date('Y');
            $semester = $periodeDetail['semester'] ?? '';
            $tanggalLaporan = (strtolower($semester) === 'ganjil' || $semester === '1')
                ? "{$tahun}-06-30" : "{$tahun}-12-31";
            $tanggalFile = str_replace('-', '', $tanggalLaporan);

            $pdfFilename = "TKSBPRKS-{$kodeDokumen}-{$jenispelaporan}-S-{$tanggalFile}-{$sandibpr}-01.pdf";
            $zip->addFromString($pdfFilename, $pdfContent);
        } catch (\Throwable $e) {
            log_message('error', "[exportAllToZip] Gagal menambahkan PDF Dirymf ({$kodeDokumen}) ke ZIP: " . $e->getMessage());
        }
    }
}