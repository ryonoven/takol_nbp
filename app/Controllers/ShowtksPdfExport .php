<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RedirectResponse;
use App\Libraries\LaporanTKSPdfGenerator;
use App\Models\M_periodesemesteran;
use App\Models\M_showtks;
use App\Models\M_infobpr;
use App\Models\M_user;
use App\Models\M_tksprofilrisiko;
use App\Models\M_tkstatakelola;
use App\Models\M_tksrentabilitas;
use App\Models\M_tkspermodalan;

class ShowtksPdfExport extends Controller
{
    protected $auth;
    protected $session;
    protected $userKodebpr;
    protected $userId;
    protected $periodeId;

    protected $periodeModel;
    protected $showtksModel;
    protected $infobprModel;
    protected $userModel;
    protected $tksprofilrisikoModel;
    protected $tkstatakelolaModel;
    protected $tksrentabilitasModel;
    protected $tkspermodalanModel;

    public function __construct()
    {
        $this->periodeModel         = new M_periodesemesteran();
        $this->showtksModel         = new M_showtks();
        $this->infobprModel         = new M_infobpr();
        $this->userModel            = new M_user();
        $this->tksprofilrisikoModel = new M_tksprofilrisiko();
        $this->tkstatakelolaModel   = new M_tkstatakelola();
        $this->tksrentabilitasModel = new M_tksrentabilitas();
        $this->tkspermodalanModel   = new M_tkspermodalan();

        $this->session = service('session');
        $this->auth    = service('authentication');

        $this->initializeUser();
    }

    private function initializeUser()
    {
        if ($this->auth->isLoggedIn()) {
            $this->userId = $this->auth->id();
            $user = $this->userModel->find($this->userId);
            $this->userKodebpr = $user['kodebpr'] ?? null;
        } else {
            $this->userKodebpr = null;
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
            return redirect()->to('/Periodetks')
                ->with('error', 'Silakan pilih periode aktif terlebih dahulu.');
        }
        return null;
    }

    /**
     * Generate PDF TKS dan tampilkan langsung di browser (inline).
     * Route contoh: $routes->get('Showtks/pdf', 'ShowtksPdfExport::generate');
     */
    public function generate()
    {
        if ($redirect = $this->checkAuth()) return $redirect;
        if ($redirect = $this->checkPeriode()) return $redirect;

        if (!$this->userKodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid.');
        }

        $kodebpr   = $this->userKodebpr;
        $periodeId = $this->periodeId;

        $periodeDetail = $this->periodeModel->find($periodeId) ?: [];
        $bprData       = $this->infobprModel->getBprByKode($kodebpr) ?: [];
        $showtks       = $this->showtksModel->getByKodebprAndPeriode($kodebpr, $periodeId) ?: [];

        $profilRisiko = $this->tksprofilrisikoModel->getByKodebprAndPeriode($kodebpr, $periodeId) ?: [];
        $tataKelola   = $this->tkstatakelolaModel->getByKodebprAndPeriode($kodebpr, $periodeId) ?: [];
        $rentabilitas = $this->tksrentabilitasModel->getByKodebprAndPeriode($kodebpr, $periodeId) ?: [];
        $permodalan   = $this->tkspermodalanModel->getByKodebprAndPeriode($kodebpr, $periodeId) ?: [];

        $periode  = $this->buildPeriodeData($periodeDetail);
        $tks      = $this->buildTksData($showtks, $profilRisiko, $tataKelola, $rentabilitas, $permodalan);
        $analisis = $this->buildAnalisisData($showtks);
        $profil   = $this->buildProfilData($showtks);

        $bpr = [
            'namabpr' => $bprData['namabpr'] ?? '',
            'alamat'  => $bprData['alamat'] ?? '',
            'nomor'   => $bprData['nomor'] ?? '',
            'webbpr'  => $bprData['webbpr'] ?? '',
            'email'   => $bprData['email'] ?? '',
            'logo'    => $bprData['logo'] ?? '',
        ];

        try {
            $generator = new LaporanTKSPdfGenerator();
            $generator->setData([
                'bpr'      => $bpr,
                'periode'  => $periode,
                'tks'      => $tks,
                'analisis' => $analisis,
                'profil'   => $profil,
            ]);

            $pdfContent = $generator->generate();
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal membuat PDF: ' . $e->getMessage());
        }

        $namaBpr = preg_replace('/[^A-Za-z0-9\-]/', '_', $bpr['namabpr'] ?: 'BPR');
        $tahun   = $periodeDetail['tahun'] ?? date('Y');
        $bulan   = $periode['bulan'] ?? '';
        $filename = "Laporan-TKS-{$namaBpr}-{$bulan}-{$tahun}.pdf";

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->setBody($pdfContent);
    }

    /**
     * Sama seperti generate(), tapi memaksa file untuk diunduh (attachment).
     * Route contoh: $routes->get('Showtks/pdf/download', 'ShowtksPdfExport::download');
     */
    public function download()
    {
        $response = $this->generate();

        if ($response instanceof RedirectResponse) {
            return $response;
        }

        $disposition = $response->getHeaderLine('Content-Disposition');
        $response->setHeader('Content-Disposition', str_replace('inline;', 'attachment;', $disposition));

        return $response;
    }

    /**
     * Susun data periode (bulan/tahun berjalan + periode sebelumnya)
     * berdasarkan pola semester Juni/Desember yang sudah dipakai di Showtks.
     */
    private function buildPeriodeData(array $periodeDetail): array
    {
        $bulan = $periodeDetail['bulan'] ?? $this->semesterToBulan($periodeDetail['semester'] ?? null);
        $tahun = $periodeDetail['tahun'] ?? date('Y');

        if (strtolower((string) $bulan) === 'juni') {
            $prevBulan = 'Desember';
            $prevTahun = $tahun - 1;
        } else {
            $prevBulan = 'Juni';
            $prevTahun = $tahun;
        }

        return [
            'bulan'               => $bulan,
            'tahun'               => $tahun,
            'periodeSebelumBulan' => $prevBulan,
            'periodeSebelumTahun' => $prevTahun,
        ];
    }

    private function semesterToBulan($semester)
    {
        if ($semester === null) {
            return '';
        }
        $s = strtolower((string) $semester);
        return ($s === '1' || $s === 'ganjil') ? 'Juni' : 'Desember';
    }

    /**
     * Susun struktur $tks sesuai kebutuhan LaporanTKSPdfGenerator::buildTKSTable().
     */
    private function buildTksData(array $showtks, array $profilRisiko, array $tataKelola, array $rentabilitas, array $permodalan): array
    {
        return [
            'profilrisiko' => [
                'peringkatCurrent'    => $profilRisiko['nilaiperingkat'] ?? '',
                'peringkatPrevious'   => $profilRisiko['nilaiperingkatbefore'] ?? '',
                'nilaifaktorCurrent'  => $profilRisiko['nilaifaktor'] ?? '',
                'nilaifaktorPrevious' => $profilRisiko['nilaifaktorbefore'] ?? '',
            ],
            'tatakelola' => [
                'peringkatCurrent'    => $tataKelola['nilaiperingkat'] ?? '',
                'peringkatPrevious'   => $tataKelola['nilaiperingkatbefore'] ?? '',
                'nilaifaktorCurrent'  => $tataKelola['nilaifaktor'] ?? '',
                'nilaifaktorPrevious' => $tataKelola['nilaifaktorbefore'] ?? '',
            ],
            'rentabilitas' => [
                'peringkatCurrent'    => $rentabilitas['peringkatrentabilitas'] ?? '',
                'peringkatPrevious'   => $rentabilitas['peringkatrentabilitasbefore'] ?? '',
                'nilaifaktorCurrent'  => $rentabilitas['nilaifaktor'] ?? '',
                'nilaifaktorPrevious' => $rentabilitas['nilaifaktorbefore'] ?? '',
                'roa' => [
                    'rasioCurrent'      => $rentabilitas['rasioroa'] ?? '',
                    'peringkatCurrent'  => $rentabilitas['peringkatroa'] ?? '',
                    'rasioPrevious'     => $rentabilitas['rasioroabefore'] ?? '',
                    'peringkatPrevious' => $rentabilitas['peringkatroabefore'] ?? '',
                ],
                'bopo' => [
                    'rasioCurrent'      => $rentabilitas['rasiobopo'] ?? '',
                    'peringkatCurrent'  => $rentabilitas['peringkatbopo'] ?? '',
                    'rasioPrevious'     => $rentabilitas['rasiobopobefore'] ?? '',
                    'peringkatPrevious' => $rentabilitas['peringkatbopobefore'] ?? '',
                ],
                'nim' => [
                    'rasioCurrent'      => $rentabilitas['rasionim'] ?? '',
                    'peringkatCurrent'  => $rentabilitas['peringkatnim'] ?? '',
                    'rasioPrevious'     => $rentabilitas['rasionimbefore'] ?? '',
                    'peringkatPrevious' => $rentabilitas['peringkatnimbefore'] ?? '',
                ],
            ],
            'permodalan' => [
                'peringkatCurrent'    => $permodalan['penilaianpermodalan'] ?? '',
                'peringkatPrevious'   => $permodalan['penilaianpermodalanbefore'] ?? '',
                'nilaifaktorCurrent'  => $permodalan['nilaifaktor'] ?? '',
                'nilaifaktorPrevious' => $permodalan['nilaifaktorbefore'] ?? '',
                'kpmm' => [
                    'rasioCurrent'      => $permodalan['rasiokpmm'] ?? '',
                    'peringkatCurrent'  => $permodalan['peringkatkpmm'] ?? '',
                    'rasioPrevious'     => $permodalan['rasiokpmmbefore'] ?? '',
                    'peringkatPrevious' => $permodalan['peringkatkpmmbefore'] ?? '',
                ],
                'miapb' => [
                    'rasioCurrent'      => $permodalan['rasiomiapb'] ?? '',
                    'peringkatCurrent'  => $permodalan['peringkatmiapb'] ?? '',
                    'rasioPrevious'     => $permodalan['rasiomiapbbefore'] ?? '',
                    'peringkatPrevious' => $permodalan['peringkatmiapbbefore'] ?? '',
                ],
            ],
            'komposit' => [
                'nilaiCurrent'      => $showtks['nkomposittks'] ?? '',
                'nilaiPrevious'     => $showtks['nkomposittksbefore'] ?? '',
                'peringkatCurrent'  => $showtks['peringkatkomposittks'] ?? '',
                'peringkatPrevious' => $showtks['peringkatkomposittksbefore'] ?? '',
            ],
        ];
    }

    /**
     * Susun struktur $analisis sesuai kebutuhan LaporanTKSPdfGenerator::buildAnalysis().
     */
    private function buildAnalisisData(array $showtks): array
    {
        return [
            'kesimpulan'   => $showtks['kesimpulan'] ?? '',
            'profilrisiko' => $showtks['kesimpulan2'] ?? '',
            'tatakelola'   => $showtks['kesimpulan3'] ?? '',
            'rentabilitas' => $showtks['kesimpulan4'] ?? '',
            'permodalan'   => $showtks['kesimpulan5'] ?? '',
        ];
    }

    /**
     * Susun struktur $profil sesuai kebutuhan LaporanTKSPdfGenerator::buildSignature().
     */
    private function buildProfilData(array $showtks): array
    {
        return [
            'tanggal' => $showtks['tanggal'] ?? date('Y-m-d'),
            'lokasi'  => $showtks['lokasi'] ?? '-',
            'dirkep'  => $showtks['dirkep'] ?? '',
            'pe'      => $showtks['pe'] ?? '',
        ];
    }
}