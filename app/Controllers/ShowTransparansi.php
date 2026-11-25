<?php

namespace App\Controllers;

use App\Models\M_infobpr;
use App\Models\M_periodetahunan;
use App\Models\M_showtransparansi;
use App\Models\M_user;
use CodeIgniter\Controller;

/**
 * Ringkasan Laporan Transparansi Pelaksanaan Tata Kelola + export ZIP seluruh TXT.
 */
class ShowTransparansi extends Controller
{
    protected $transparansiModel;
    protected $periodeModel;
    protected $infobprModel;
    protected $auth;
    protected $userKodebpr;

    private const VIEW = 'Laporantahunan/Transparansi/showtransparansi';

    public function __construct()
    {
        $this->transparansiModel = new M_showtransparansi();
        $this->periodeModel = new M_periodetahunan();
        $this->infobprModel = new M_infobpr();
        $this->auth = service('authentication');

        $this->userKodebpr = null;
        if ($this->auth->check()) {
            $user = (new M_user())->find($this->auth->id());
            $this->userKodebpr = $user['kodebpr'] ?? null;
        }
    }

    private function checkAuth(): ?\CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->auth->check()) {
            session()->set('redirect_url', current_url());
            return redirect()->to('/login');
        }
        if (!session('active_periode')) {
            return redirect()->to('/periodetahunan')->with('error', 'Silakan pilih periode aktif terlebih dahulu.');
        }
        if (!$this->userKodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid.');
        }
        return null;
    }

    public function index()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }

        $periodeId = (int) session('active_periode');
        $sections = $this->transparansiModel->getSections($this->userKodebpr, $periodeId);
        $pending = array_values(array_filter($sections, static fn($s) => !$s['done']));

        $data = [
            'judul' => 'Laporan Transparansi Tata Kelola',
            'namabpr' => $this->infobprModel->getBprByKode($this->userKodebpr)['namabpr'] ?? null,
            'tahun' => $this->periodeModel->find($periodeId)['tahun'] ?? '',
            'sections' => $sections,
            'pending' => $pending,
            'doneCount' => count($sections) - count($pending),
            'canExport' => !$pending,
        ];

        return view('templates/v_header', $data)
            . view('templates/v_sidebar')
            . view('templates/v_topbar')
            . view(self::VIEW, $data)
            . view('templates/v_footer');
    }

    /** ZIP berisi TXT seluruh bagian yang memiliki export. Hanya bila semua bagian wajib sudah disetujui. */
    public function exportAllToZip()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }

        $periodeId = (int) session('active_periode');
        $sections = $this->transparansiModel->getSections($this->userKodebpr, $periodeId);
        if (array_filter($sections, static fn($s) => !$s['done'])) {
            return redirect()->to(base_url('ShowTransparansi'))
                ->with('err', 'ZIP belum dapat dibuat: masih ada bagian yang belum disetujui Direktur Utama.');
        }

        $tahun = (string) ($this->periodeModel->find($periodeId)['tahun'] ?? date('Y'));
        $zipFileName = 'NBPSIMPEL - Laporan Transparansi Tahunan - ' . $tahun . '-12-31.zip';
        $zipFilePath = tempnam(sys_get_temp_dir(), 'trans');

        $zip = new \ZipArchive();
        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return redirect()->to(base_url('ShowTransparansi'))->with('err', 'Gagal membuat file ZIP.');
        }
        foreach ($sections as $s) {
            if ($s['export']) {
                $this->addTxtToZip($s['controller'], $s['export'], $zip);
            }
        }
        $zip->close();

        $content = (string) file_get_contents($zipFilePath);
        @unlink($zipFilePath);

        return $this->response
            ->setHeader('Content-Type', 'application/zip')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $zipFileName . '"')
            ->setHeader('Content-Length', (string) strlen($content))
            ->setBody($content);
    }

    /** Jalankan method export TXT controller lain lalu masukkan hasilnya ke ZIP. */
    private function addTxtToZip(string $controller, string $method, \ZipArchive $zip): void
    {
        $fqcn = 'App\\Controllers\\' . $controller;
        if (!class_exists($fqcn) || !method_exists($fqcn, $method)) {
            log_message('warning', "ShowTransparansi ZIP: {$fqcn}::{$method} tidak ditemukan.");
            return;
        }

        try {
            ob_start();
            $result = (new $fqcn())->$method();
            $buffered = ob_get_clean();

            $fileName = strtolower($controller) . '.txt';
            $content = $buffered;
            if ($result instanceof \CodeIgniter\HTTP\Response) {
                $content = $result->getBody();
                if (preg_match('/filename=["\']?([^"\';]+)["\']?/i', $result->getHeaderLine('Content-Disposition'), $m)) {
                    $fileName = trim($m[1]);
                }
            }
            if ((string) $content !== '') {
                $zip->addFromString($fileName, (string) $content);
            }
        } catch (\Throwable $e) {
            log_message('error', "ShowTransparansi ZIP [{$controller}::{$method}]: " . $e->getMessage());
        }
    }
}
