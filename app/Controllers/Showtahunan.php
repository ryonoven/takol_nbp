<?php

namespace App\Controllers;

use App\Models\M_infobpr;
use App\Models\M_periodetahunan;
use App\Models\M_showtahunan;
use App\Models\M_showtransparansi;
use App\Models\M_user;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class Showtahunan extends Controller
{
    protected $showtahunanModel;
    protected $periodeModel;
    protected $infobprModel;
    protected $auth;
    protected $userKodebpr;

    private const EXPORTS = [
        'Riwayatbpr'             => ['exporttxt'],
        'Penjelasannpl'          => ['exporttxtnpl'],
        'Usahasignifikan'        => ['pdf'],
        'Kebijaksanaanmanathn'   => ['pdf'],
        'Bidusahathn'            => ['exporttxt'],
        'Titahunan'              => ['pdf'],
        'Perkembanganpasar'      => ['pdf'],
        'Kerjasamabpr'           => ['exporttxt'],
        'Kegiatanpengembangan'   => ['exporttxt'],
        'Laporanakuntan'         => ['pdf'],
        'Suratpernyataandireksi' => ['pdf'],         // LTBPRK D0000
        'Dokumenpendukung'       => ['exportpdf'],   // PDF gabungan dokumen pendukung, LTBPRK F0000
    ];

    public function __construct()
    {
        $this->showtahunanModel = new M_showtahunan();
        $this->periodeModel = new M_periodetahunan();
        $this->infobprModel = new M_infobpr();
        $this->auth = service('authentication');

        $this->userKodebpr = null;
        if ($this->auth->check()) {
            $user = (new M_user())->find($this->auth->id());
            $this->userKodebpr = $user['kodebpr'] ?? null;
        }
    }

    private function checkAuth(): ?RedirectResponse
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

    /**
     * Syarat export ZIP: seluruh bagian laporan tahunan (termasuk Transparansi) sudah disetujui Direktur Utama.
     * Mengembalikan [sections, canGenerate, warnings].
     */
    private function exportStatus(string $kodebpr, int $periodeId): array
    {
        $sections = $this->showtahunanModel->getSections($kodebpr, $periodeId);
        $pending = array_column(array_filter($sections, static fn($s) => $s['table'] && !$s['approved']), 'name');

        $warnings = $pending ? ['Persetujuan Direktur Utama belum lengkap: ' . implode(', ', $pending) . '.'] : [];
        return [$sections, !$pending, $warnings];
    }

    /**
     * Jalankan method export (TXT/PDF) controller lain lalu masukkan hasilnya ke ZIP.
     * Controller di-init dengan Response baru agar $this->response tersedia dan tidak saling menimpa.
     */
    private function addExport(\ZipArchive $zip, string $controller, string $method): void
    {
        $fqcn = 'App\\Controllers\\' . $controller;
        if (!class_exists($fqcn) || !method_exists($fqcn, $method)) {
            log_message('warning', "Showtahunan ZIP: {$fqcn}::{$method} tidak ditemukan.");
            return;
        }

        $flashBefore = array_keys(session()->getFlashdata() ?? []);
        try {
            $c = new $fqcn();
            $c->initController(service('request'), Services::response(null, false), service('logger'));

            ob_start();
            try {
                $result = $c->$method();
            } finally {
                $buffered = (string) ob_get_clean();
            }

            if ($result instanceof RedirectResponse) {
                // Export menolak (data belum ada / belum disetujui): jangan bocorkan flash message-nya.
                session()->remove(array_diff(array_keys(session()->getFlashdata() ?? []), $flashBefore));
                log_message('warning', "Showtahunan ZIP: {$controller}::{$method} dilewati (redirect).");
                return;
            }

            $content = $buffered;
            $fileName = '';
            if ($result instanceof ResponseInterface) {
                $content = (string) $result->getBody();
                if (preg_match('/filename=["\']?([^"\';]+)["\']?/i', $result->getHeaderLine('Content-Disposition'), $m)) {
                    $fileName = basename(trim($m[1]));
                }
            }
            if ($content === '') {
                return;
            }
            if ($fileName === '') {
                $ext = strncmp($content, '%PDF', 4) === 0 ? 'pdf' : 'txt';
                $fileName = strtolower($controller) . '.' . $ext;
            }
            if ($zip->locateName($fileName) !== false) {
                $fileName = strtolower($controller) . '_' . $fileName;
            }
            $zip->addFromString($fileName, $content);
        } catch (\Throwable $e) {
            log_message('error', "Showtahunan ZIP [{$controller}::{$method}]: " . $e->getMessage());
        }
    }

    public function index()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }

        $periodeId = (int) session('active_periode');
        [$sections, $canGenerate, $warnings] = $this->exportStatus($this->userKodebpr, $periodeId);

        $data = [
            'judul' => 'Pelaporan Tahunan',
            'namabpr' => $this->infobprModel->getBprByKode($this->userKodebpr)['namabpr'] ?? null,
            'periode_tahun' => $this->periodeModel->find($periodeId)['tahun'] ?? 'N/A',
            'sections' => $sections,
            'canGenerate' => $canGenerate,
            'warnings' => $warnings,
            // Komentar belum dibaca per kartu (satu query untuk semua halaman)
            'unreadComments' => (new \App\Models\M_komentar())->unreadCounts(
                $this->userKodebpr, $periodeId, (int) $this->auth->id(), \App\Models\M_komentar::GROUP_TAHUNAN
            ),
        ];

        return view('templates/v_header', $data)
            . view('templates/v_sidebar')
            . view('templates/v_topbar')
            . view('Laporantahunan/showtahunan', $data)
            . view('templates/v_footer');
    }

    /** ZIP berisi TXT & PDF seluruh bagian laporan tahunan + seluruh TXT Laporan Transparansi. */
    public function exportAllToZip()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = (int) session('active_periode');

        [, $canGenerate] = $this->exportStatus($kodebpr, $periodeId);
        if (!$canGenerate) {
            return redirect()->to(base_url('Showtahunan'))
                ->with('err', 'ZIP belum dapat dibuat: masih ada bagian yang belum disetujui Direktur Utama.');
        }

        // Render ±9 PDF (Dompdf) + ±20 TXT bisa melewati batas default 30 detik.
        @set_time_limit(300);
        ignore_user_abort(true);

        $tahun = (string) ($this->periodeModel->find($periodeId)['tahun'] ?? date('Y'));
        $bpr = $this->infobprModel->getBprByKode($kodebpr) ?? [];
        $namaBpr = trim(preg_replace('/\s+/', ' ', preg_replace('/[^A-Za-z0-9\s]/', '', $bpr['namabpr'] ?? 'BPR')));
        $zipFileName = 'NBPSIMPEL - Laporan Tahunan - ' . $namaBpr . ' - ' . $tahun . '-12-31.zip';
        $zipFilePath = tempnam(sys_get_temp_dir(), 'thn');

        $zip = new \ZipArchive();
        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return redirect()->to(base_url('Showtahunan'))->with('err', 'Gagal membuat file ZIP.');
        }

        // 1. Bagian laporan tahunan: Riwayat BPR s.d. Surat Pernyataan Direksi
        foreach (self::EXPORTS as $controller => $methods) {
            foreach ($methods as $method) {
                $this->addExport($zip, $controller, $method);
            }
        }

        // 2. Laporan Transparansi: Penjelasan Umum s.d. Pemberian Dana Kegiatan Sosial & Politik.
        //    Bagian opsional tanpa data dilewati (sama dengan aturan halaman Laporan Transparansi).
        foreach ((new M_showtransparansi())->getSections($kodebpr, $periodeId) as $s) {
            if ($s['export'] && $s['hasData']) {
                $this->addExport($zip, $s['controller'], $s['export']);
            }
        }

        $count = $zip->numFiles;
        $zip->close();

        $content = $count > 0 ? (string) file_get_contents($zipFilePath) : '';
        @unlink($zipFilePath);
        if ($content === '') {
            return redirect()->to(base_url('Showtahunan'))->with('err', 'Tidak ada file yang dapat dimasukkan ke ZIP.');
        }

        return $this->response
            ->setHeader('Content-Type', 'application/zip')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $zipFileName . '"')
            ->setHeader('Content-Length', (string) strlen($content))
            ->setBody($content);
    }
}
