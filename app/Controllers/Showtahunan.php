<?php

namespace App\Controllers;

use App\Models\M_periodetahunan;
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

class Showtahunan extends Controller
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

        $this->periodeModel = new M_periodetahunan();
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
            return redirect()->to('/periodetahunan')
                ->with('error', 'Silakan pilih periode aktif terlebih dahulu.');
        }

        return null;
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

    public function index()
    {
        if (!$this->auth->check()) {
            session()->set('redirect_url', current_url());
            return redirect()->to('/login');
        }

        if (!session('active_periode')) {
            return redirect()->to('/periodetahunan')->with('error', 'Silakan pilih periode aktif terlebih dahulu.');
        }

        $periodeId = session('active_periode');
        $kodebpr = $this->userKodebpr;

        $showfaktor = $this->showfaktorModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $id = $showfaktor['id'] ?? null;

        $allShowFaktor = $this->showfaktorModel->findAll();

        log_message('debug', 'ShowFaktor: User ID: ' . $this->auth->id() . ', Kode BPR: ' . $kodebpr . ', Periode ID: ' . $periodeId);

        if (!$kodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid.');
        }

        $periodeDetail = $this->periodeModel->find($periodeId);
        $bprData = $this->infobprModel->getBprByKode($kodebpr);

        $factors = [
            [
                'name' => 'Kata Pengantar',
                'link' => base_url(''),
                'nfaktor' => $this->nilaiModel->where('faktor1id', 12)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['nfaktor'] ?? 'N/A',
                'accdekom' => $this->nilaiModel->where('faktor1id', 12)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['accdekom'] ?? null,
                'is_approved' => $this->nilaiModel->where('faktor1id', 12)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['is_approved'] ?? null,
            ],
            [
                'name' => 'Data Kepengurusan',
                'link' => base_url(''),
                'nfaktor' => $this->nilaiModel->where('faktor1id', 12)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['nfaktor'] ?? 'N/A',
                'accdekom' => $this->nilaiModel->where('faktor1id', 12)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['accdekom'] ?? null,
                'is_approved' => $this->nilaiModel->where('faktor1id', 12)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['is_approved'] ?? null,
            ],
            [
                'name' => 'Laporan Kepemilikan',
                'link' => base_url(''),
                'nfaktor2' => $this->nilai2Model->where('faktor2id', 29)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['nfaktor2'] ?? 'N/A',
                'accdekom' => $this->nilai2Model->where('faktor2id', 29)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['accdekom'] ?? null,
                'is_approved' => $this->nilai2Model->where('faktor2id', 29)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['is_approved'] ?? null,
            ],
            [
                'name' => 'Perkembangan Usaha BPR',
                'link' => base_url(''),
                'nfaktor2' => $this->nilai2Model->where('faktor2id', 29)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['nfaktor2'] ?? 'N/A',
                'accdekom' => $this->nilai2Model->where('faktor2id', 29)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['accdekom'] ?? null,
                'is_approved' => $this->nilai2Model->where('faktor2id', 29)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['is_approved'] ?? null,
            ],
            [
                'name' => 'Strategi dan Kebijakan Manajemen',
                'link' => base_url(''),
                'nfaktor2' => $this->nilai2Model->where('faktor2id', 29)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['nfaktor2'] ?? 'N/A',
                'accdekom' => $this->nilai2Model->where('faktor2id', 29)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['accdekom'] ?? null,
                'is_approved' => $this->nilai2Model->where('faktor2id', 29)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['is_approved'] ?? null,
            ],
            [
                'name' => 'Laporan Manajemen',
                'link' => base_url(''),
                'nfaktor2' => $this->nilai2Model->where('faktor2id', 29)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['nfaktor2'] ?? 'N/A',
                'accdekom' => $this->nilai2Model->where('faktor2id', 29)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['accdekom'] ?? null,
                'is_approved' => $this->nilai2Model->where('faktor2id', 29)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['is_approved'] ?? null,
            ],
            [
                'name' => 'Pengembangan Sumber Daya Manusia',
                'link' => base_url(''),
                'nfaktor2' => $this->nilai2Model->where('faktor2id', 29)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['nfaktor2'] ?? 'N/A',
                'accdekom' => $this->nilai2Model->where('faktor2id', 29)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['accdekom'] ?? null,
                'is_approved' => $this->nilai2Model->where('faktor2id', 29)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['is_approved'] ?? null,
            ],
            [
                'name' => 'Laporan Keuangan Tahunan',
                'link' => base_url(''),
                'nfaktor2' => $this->nilai2Model->where('faktor2id', 29)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['nfaktor2'] ?? 'N/A',
                'accdekom' => $this->nilai2Model->where('faktor2id', 29)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['accdekom'] ?? null,
                'is_approved' => $this->nilai2Model->where('faktor2id', 29)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['is_approved'] ?? null,
            ],
            [
                'name' => 'Laporan dan Opini Akuntan Publik',
                'link' => base_url(''),
                'nfaktor2' => $this->nilai2Model->where('faktor2id', 29)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['nfaktor2'] ?? 'N/A',
                'accdekom' => $this->nilai2Model->where('faktor2id', 29)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['accdekom'] ?? null,
                'is_approved' => $this->nilai2Model->where('faktor2id', 29)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['is_approved'] ?? null,
            ],
            [
                'name' => 'Surat Pernyataan Kebenaran Laporan Keuangan Tahunan',
                'link' => base_url(''),
                'nfaktor2' => $this->nilai2Model->where('faktor2id', 29)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['nfaktor2'] ?? 'N/A',
                'accdekom' => $this->nilai2Model->where('faktor2id', 29)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['accdekom'] ?? null,
                'is_approved' => $this->nilai2Model->where('faktor2id', 29)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['is_approved'] ?? null,
            ],
            [
                'name' => 'Laporan Transparansi Tahunan',
                'link' => base_url('Showtransparansi'),
                'nfaktor3' => $this->nilai3Model->where('faktor3id', 27)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['nfaktor3'] ?? 'N/A',
                'accdekom' => $this->nilai3Model->where('faktor3id', 27)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['accdekom'] ?? null,
                'is_approved' => $this->nilai3Model->where('faktor3id', 27)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['is_approved'] ?? null,
            ]
        ];

        $totalNilai = 0;
        $validCount = 0;
        $validFactors = 12;

        foreach ($factors as $factor) {
            if (isset($factor['nfaktor4']) && is_numeric($factor['nfaktor4']) && $factor['nfaktor4'] != 'N/A' && $factor['nfaktor4'] != 0 && $factor['nfaktor4'] !== null) {
                $totalNilai += $factor['nfaktor4'];
                $validCount++;
            }

            if (isset($factor['nfaktor']) && is_numeric($factor['nfaktor']) && $factor['nfaktor'] != 'N/A') {
                $totalNilai += $factor['nfaktor'];
                $validCount++;
            } elseif (isset($factor['nfaktor2']) && is_numeric($factor['nfaktor2']) && $factor['nfaktor2'] != 'N/A') {
                $totalNilai += $factor['nfaktor2'];
                $validCount++;
            } else if (isset($factor['nfaktor3']) && is_numeric($factor['nfaktor3']) && $factor['nfaktor3'] != 'N/A') {
                $totalNilai += $factor['nfaktor3'];
                $validCount++;
            } else if (isset($factor['nfaktor5']) && is_numeric($factor['nfaktor5']) && $factor['nfaktor5'] != 'N/A') {
                $totalNilai += $factor['nfaktor5'];
                $validCount++;
            } else if (isset($factor['nfaktor6']) && is_numeric($factor['nfaktor6']) && $factor['nfaktor6'] != 'N/A') {
                $totalNilai += $factor['nfaktor6'];
                $validCount++;
            } else if (isset($factor['nfaktor7']) && is_numeric($factor['nfaktor7']) && $factor['nfaktor7'] != 'N/A') {
                $totalNilai += $factor['nfaktor7'];
                $validCount++;
            } else if (isset($factor['nfaktor8']) && is_numeric($factor['nfaktor8']) && $factor['nfaktor8'] != 'N/A') {
                $totalNilai += $factor['nfaktor8'];
                $validCount++;
            } else if (isset($factor['nfaktor9']) && is_numeric($factor['nfaktor9']) && $factor['nfaktor9'] != 'N/A') {
                $totalNilai += $factor['nfaktor9'];
                $validCount++;
            } else if (isset($factor['nfaktor10']) && is_numeric($factor['nfaktor10']) && $factor['nfaktor10'] != 'N/A') {
                $totalNilai += $factor['nfaktor10'];
                $validCount++;
            } else if (isset($factor['nfaktor11']) && is_numeric($factor['nfaktor11']) && $factor['nfaktor11'] != 'N/A') {
                $totalNilai += $factor['nfaktor11'];
                $validCount++;
            } else if (isset($factor['nfaktor12']) && is_numeric($factor['nfaktor12']) && $factor['nfaktor12'] != 'N/A') {
                $totalNilai += $factor['nfaktor12'];
                $validCount++;
            }
        }

        $nilaikomposit = $validCount > 0 ? $totalNilai / ($validFactors - ($validFactors === 12 && $validCount < 12 ? 1 : 0)) : 0;
        $nilaikomposit = ($nilaikomposit - floor($nilaikomposit)) >= 0.5 ? ceil($nilaikomposit) : floor($nilaikomposit);
        $peringkatkomposit = $this->hitungPeringkatKomposit($nilaikomposit);
        $colorClass = $this->getColorClassForPeringkat($peringkatkomposit);

        foreach ($factors as $index => $factor) {
            $factor['accdekom_status'] = isset($factor['accdekom']) && $factor['accdekom'] == 1 ? 'Telah disetujui oleh Komisaris Utama' : 'Belum disetujui oleh Komisaris Utama';
            $factor['direksi_status'] = isset($factor['is_approved']) && $factor['is_approved'] == 1 ? 'Telah disetujui oleh Direktur Utama' : 'Belum disetujui oleh Direktur Utama';

            $factors[$index] = $factor;
        }

        $data = [
            'judul' => 'Pelaporan Tahunan',
            'namabpr' => $bprData['namabpr'] ?? 'Nama BPR Tidak Ditemukan',
            'periode_tahun' => $periodeDetail['tahun'] ?? 'N/A',
            'factors' => $factors,
            'nilaikomposit' => $nilaikomposit,
            'peringkatkomposit' => $peringkatkomposit,
            'colorClass' => $colorClass,
            'showfaktor' => $showfaktor,
            'allShowFaktor' => $allShowFaktor
        ];

        $showFaktorModel = new M_showfaktor();
        $showFaktorModel->simpanShowFaktor([
            'kodebpr' => $kodebpr,
            'periode_id' => $periodeId,
            'nilaikomposit' => $nilaikomposit,
            'peringkatkomposit' => $peringkatkomposit,
            'tahun' => $periodeDetail['tahun'] ?? 'N/A',
        ]);

        return view('templates/v_header', $data)
            . view('templates/v_sidebar')
            . view('templates/v_topbar')
            . view('Laporantahunan/showtahunan', $data)
            . view('templates/v_footer');
    }

    public function exportAllToZip()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');
        $periodeDetail = $this->periodeModel->find($periodeId);
        $modalinti = $periodeDetail['modalinti'];
        $kategori = $periodeDetail['kategori'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];

        if (!$kodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid.');
        }

        $showfaktor = $this->showfaktorModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $bprData = $this->infobprModel->getBprByKode($kodebpr);
        $namaBpr = preg_replace('/[^A-Za-z0-9\s]/', '', $bprData['namabpr'] ?? 'BPR');
        $namaBpr = trim(preg_replace('/\s+/', ' ', $namaBpr));

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

        $zipFileName = 'NBPSIMPEL - Laporan Tahunan - ' . $namaBpr . ' (' . $zipDate . ').zip';
        $zipFilePath = WRITEPATH . 'uploads/' . $zipFileName;

        if (!is_dir(WRITEPATH . 'uploads')) {
            mkdir(WRITEPATH . 'uploads', 0775, true);
        }

        $zip = new \ZipArchive();

        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
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
            ['ShowFaktor', 'exporttxtshowfaktor'],
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

        foreach ($exports as [$controller, $method]) {
            $this->addTxtToZip($controller, $method, $zip);
        }

        try {
            $pdfController = new \App\Controllers\Pdfself();
            $pdfContent = $pdfController->generatePdfToBuffer();

            $bprData = $this->infobprModel->getBprByKode($kodebpr);
            $namaBpr = preg_replace('/[^A-Za-z0-9\s]/', '', $bprData['namabpr'] ?? 'BPR');
            $namaBpr = trim(preg_replace('/\s+/', ' ', $namaBpr));
            $dataInfobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

            $periodeData = (new M_periodetahunan())->find(session('active_periode'));
            $tahun = $periodeDetail['tahun'] ?? date('Y');
            $sandibpr = '';
            $kodejenis = '';
            if (!empty($dataInfobpr)) {
                $sandibpr = $dataInfobpr[0]['sandibpr'] ?? '';
                $kodejenis = $dataInfobpr[0]['kodejenis'] ?? '';
            }

            if (strtolower($tahun) === 'ganjil' || $tahun === '1') {
                $tanggalLaporan = "{$tahun}-01-31";
            } else {
                $tanggalLaporan = "{$tahun}-06-30";
            }

            $tanggalFile = str_replace('-', '', $tanggalLaporan);
            $pdfFilename = "TKSBPRKS-1000-{$jenispelaporan}-S-{$tanggalFile}-{$sandibpr}-01.pdf";

            $zip->addFromString($pdfFilename, $pdfContent);

        } catch (\Throwable $e) {
            log_message('error', '[exportAllToZip] Gagal generate PDF: ' . $e->getMessage());
        }

        try {
            $profilRisikoCtrl = new \App\Controllers\Showprofilresiko();
            $pdfBytes = $profilRisikoCtrl->generateLaporanProfilRisikoPdfBytes();

            if (!empty($pdfBytes)) {
                $periodeDetailZip = $this->periodeModel->find($periodeId);                
                $thn = $periodeDetailZip['tahun'] ?? '';
                $pdfFileName = "TKSBPRKS-0001-{$jenispelaporan}-S-{$tanggalFile}-{$sandibpr}-01.pdf";
                $zip->addFromString($pdfFileName, $pdfBytes);
            }
        } catch (\Throwable $e) {
            log_message('error', '[exportAllToZip] Gagal generate Laporan Profil Risiko PDF: ' . $e->getMessage());
        }

        try {
            $lembarController = new \App\Controllers\Showprofilresiko();
            $lembarContent = $lembarController->generateLembarPernyataanPdfBytes();

            $bprData = $this->infobprModel->getBprByKode($kodebpr);
            $namaBpr = preg_replace('/[^A-Za-z0-9\s]/', '', $bprData['namabpr'] ?? 'BPR');
            $namaBpr = trim(preg_replace('/\s+/', ' ', $namaBpr));
            $dataInfobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

            $tahun = $periodeDetail['tahun'] ?? date('Y');
            $sandibpr = '';
            $kodejenis = '';
            if (!empty($dataInfobpr)) {
                $sandibpr = $dataInfobpr[0]['sandibpr'] ?? '';
                $kodejenis = $dataInfobpr[0]['kodejenis'] ?? '';
            }

            if (strtolower($tahun) === 'ganjil' || $tahun === '1') {
                $tanggalLaporan = "{$tahun}-01-31";
            } else {
                $tanggalLaporan = "{$tahun}-06-30";
            }

            $tanggalFile = str_replace('-', '', $tanggalLaporan);
            $lembarFilename = "TKSBPRKS-1013-{$jenispelaporan}-S-{$tanggalFile}-{$sandibpr}-01.pdf";

            $zip->addFromString($lembarFilename, $lembarContent);

        } catch (\Throwable $e) {
            log_message('error', '[exportAllToZip] Gagal generate Lembar Pernyataan PDF: ' . $e->getMessage());
        }

        try {
            $kodebpr = $this->userKodebpr;
            $periodeId = session('active_periode');

            $showdirymfModel = new M_showdirymf();
            $showdirymfRecord = $showdirymfModel
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->first();

            $pdfDirymfPath = WRITEPATH . 'uploads/pdf_dirymf/';

            if (
                !empty($showdirymfRecord['pdfdir_filename']) &&
                file_exists($pdfDirymfPath . $showdirymfRecord['pdfdir_filename'])
            ) {
                $pdfContent = file_get_contents($pdfDirymfPath . $showdirymfRecord['pdfdir_filename']);

                if (substr($pdfContent, 0, 4) === '%PDF') {
                    $dataInfobpr = $this->infobprModel->getDataByKodebpr($kodebpr);
                    $sandibpr = '';
                    $jenispelaporan = $periodeDetail['jenispelaporan'] ?? '';
                    if (!empty($dataInfobpr)) {
                        $sandibpr = $dataInfobpr[0]['sandibpr'] ?? '';
                    }

                    $tahun = $periodeDetail['tahun'] ?? date('Y');

                    $tanggalFile = str_replace('-', '', $tanggalLaporan);
                    $pdfFilename = "TKSBPRKS-2006-{$jenispelaporan}-S-{$tanggalFile}-{$sandibpr}-01.pdf";

                    $zip->addFromString($pdfFilename, $pdfContent);
                    log_message('info', '[exportAllToZip] PDF Dirymf ditambahkan: ' . $pdfFilename);
                } else {
                    log_message('warning', '[exportAllToZip] File PDF Dirymf bukan PDF valid, dilewati.');
                }
            } else {
                log_message('info', '[exportAllToZip] Tidak ada PDF Dirymf yang diunggah, dilewati.');
            }
        } catch (\Throwable $e) {
            log_message('error', '[exportAllToZip] Gagal menambahkan PDF Dirymf ke ZIP: ' . $e->getMessage());
        }

        try {
            $showauditModel = new M_showaudit();
            $showauditRecord = $showauditModel
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->first();

            $pdfAuditPath = WRITEPATH . 'uploads/pdf_auditinternal/';

            if (
                !empty($showauditRecord['pdfaudit_filename']) &&
                file_exists($pdfAuditPath . $showauditRecord['pdfaudit_filename'])
            ) {
                $pdfContent = file_get_contents($pdfAuditPath . $showauditRecord['pdfaudit_filename']);

                if (substr($pdfContent, 0, 4) === '%PDF') {
                    $dataInfobpr = $this->infobprModel->getDataByKodebpr($kodebpr);
                    $sandibpr = $dataInfobpr[0]['sandibpr'] ?? '';
                    $jenispelaporan = $periodeDetail['jenispelaporan'] ?? '';
                    $tahun = $periodeDetail['tahun'] ?? date('Y');

                    $tanggalFile = str_replace('-', '', $tanggalLaporan);
                    $pdfFilename = "TKSBPRKS-3005-{$jenispelaporan}-S-{$tanggalFile}-{$sandibpr}-01.pdf";

                    $zip->addFromString($pdfFilename, $pdfContent);
                    log_message('info', '[exportAllToZip] PDF Audit Internal ditambahkan: ' . $pdfFilename);
                } else {
                    log_message('warning', '[exportAllToZip] File PDF Audit bukan PDF valid, dilewati.');
                }
            } else {
                log_message('info', '[exportAllToZip] Tidak ada PDF Audit yang diunggah, dilewati.');
            }
        } catch (\Throwable $e) {
            log_message('error', '[exportAllToZip] Gagal menambahkan PDF Audit ke ZIP: ' . $e->getMessage());
        }

        $zip->close();

        if (!file_exists($zipFilePath)) {
            return redirect()->back()->with('error', 'File ZIP gagal dibuat.');
        }

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
            'negatifhasil' => $this->request->getPost('negatifhasil')
        ];
        $this->showfaktorModel->update($id, $data);
        return redirect()->to(base_url('ShowFaktor'))->with('message', 'Data berhasil diupdate!');
    }

    public function updatettd()
    {
        $id = $this->request->getPost('id');
        $data = [
            'dirut' => $this->request->getPost('dirut'),
            'komut' => $this->request->getPost('komut'),
            'tanggal' => $this->request->getPost('tanggal'),
            'lokasi' => $this->request->getPost('lokasi')
        ];
        $this->showfaktorModel->update($id, $data);
        return redirect()->to(base_url('ShowFaktor'))->with('message', 'Data berhasil diupdate!');
    }

    private function hitungPeringkatKomposit($nilaikomposit)
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

    private function getColorClassForPeringkat($peringkatkomposit)
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

    private function getColorClassForPeringkat2($peringkatkomposit)
    {
        switch ($peringkatkomposit){
            case 'Sangat Baik':
                return 'text-success';
            case 'Baik':
                return 'text-info';
            case 'Cukup' :
                return 'text-warning';
            case 'Kurang Baik':
                return 'text-danger';
            case "Buruk":
                return 'text-dark';
            default:
                return 'text-muted';
        }
    }

}