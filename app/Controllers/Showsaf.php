<?php

namespace App\Controllers;

use App\Models\LaporanPdfSafModel;
use App\Models\M_infobpr;
use App\Models\M_komentar;
use App\Models\M_penerapansafst;
use App\Models\M_periodesaf;
use App\Models\M_user;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Ringkasan Pelaporan Strategi Anti Fraud (SAF) + export ZIP (TXT penerapan SAF + PDF).
 * Halaman ini hanya membaca (tidak ada INSERT saat dibuka); status laporan PDF memakai cache model.
 */
class Showsaf extends Controller
{
    protected M_periodesaf $periodeModel;
    protected M_infobpr $infobprModel;
    protected M_penerapansafst $penerapanModel;
    protected LaporanPdfSafModel $laporanModel;
    protected $auth;
    protected ?string $userKodebpr = null;

    /** Jenis pelaporan yang memiliki Laporan Deskripsi Fraud (kartu II). */
    private const JENIS_LAPORAN_PDF = ['SAFTA', 'SAFTS', 'SAFTI3', 'SAFTI6'];
    /** Jenis pelaporan form 01A (selain itu 01B). */
    private const JENIS_01A = ['SAFTA', 'SAFTS'];

    private const LEVEL_LABEL = ['dekom' => 'Dewan Komisaris', 'direksi' => 'Direktur Utama'];

    public function __construct()
    {
        $this->periodeModel = new M_periodesaf();
        $this->infobprModel = new M_infobpr();
        $this->penerapanModel = new M_penerapansafst();
        $this->laporanModel = new LaporanPdfSafModel();
        $this->auth = service('authentication');

        if ($this->auth->check()) {
            $this->userKodebpr = (new M_user())->find($this->auth->id())['kodebpr'] ?? null;
        }
    }

    // ------------------------------------------------------------------ helpers

    private function checkAuth(): ?RedirectResponse
    {
        if (!$this->auth->check()) {
            session()->set('redirect_url', current_url());
            return redirect()->to('/login');
        }
        if (!session('active_periode')) {
            return redirect()->to('/periodesaf')->with('error', 'Silakan pilih periode aktif terlebih dahulu.');
        }
        if (!$this->userKodebpr) {
            return redirect()->to('/periodesaf')->with('error', 'User tidak memiliki kode BPR yang valid.');
        }
        return null;
    }

    /** Periode SAF aktif milik BPR user (null bila bukan miliknya / tidak ada). */
    private function periode(): ?array
    {
        return $this->periodeModel->where('id', (int) session('active_periode'))->where('kodebpr', $this->userKodebpr)->first();
    }

    /**
     * Status kedua bagian laporan.
     * @return array{sections: list<array>, canExport: bool}
     */
    private function status(array $periode): array
    {
        $kodebpr = $this->userKodebpr;
        $periodeId = (int) $periode['id'];

        // I. Penerapan SAF (tabel penerapansafst; level wajib tergantung peran penginput)
        $ap = $this->penerapanModel->getApprovalStatus($kodebpr, $periodeId);
        $levels1 = [];
        foreach (self::LEVEL_LABEL as $lv => $label) {
            if (!empty($ap[$lv]['required'])) {
                $levels1[] = ['label' => $label, 'ok' => !empty($ap[$lv]['approved'])];
            }
        }
        $sections = [[
            'key' => 'Penerapansafst',
            'name' => 'Penerapan SAF yang mencakup informasi kejadian fraud dan informasi pelaku fraud',
            'link' => base_url('Penerapansafst'),
            'icon' => 'fa-shield-alt',
            'hasData' => !empty($ap['has_rows']),
            'levels' => $levels1,
            'done' => !empty($ap['dekom_approved']),
        ]];

        // II. Laporan Deskripsi Fraud / Modus Operandi (cache 5 menit di model; tanpa INSERT)
        if (in_array($periode['jenispelaporan'] ?? '', self::JENIS_LAPORAN_PDF, true)) {
            $d = $this->laporanModel->getLaporan($kodebpr, $periodeId);
            $h = $d['h'] ?? null;
            $levels2 = [];
            if ($h) {
                foreach (M_penerapansafst::requiredLevels($h['user_role'] ?? null) as $lv) {
                    [$flag] = LaporanPdfSafModel::LEVEL_COLS[$lv];
                    $levels2[] = ['label' => self::LEVEL_LABEL[$lv] ?? $lv, 'ok' => (int) ($h[$flag] ?? 0) === 1];
                }
            }
            $sections[] = [
                'key' => 'Laporanpdfsaf',
                'name' => 'Laporan Deskripsi Fraud / Modus Operandi (Pencegahan, Deteksi, Investigasi, Pemantauan & Rencana Tindak Lanjut)',
                'link' => base_url('Laporanpdfsaf'),
                'icon' => 'fa-file-alt',
                'hasData' => $h !== null,
                'levels' => $levels2,
                'done' => $h !== null && (int) $h['status'] === 2,
            ];
        }

        return ['sections' => $sections, 'canExport' => !array_filter($sections, static fn($s) => !$s['done'])];
    }

    /** Jalankan method export controller lain (Response baru) dan kembalikan isi body, atau null. */
    private function runExport(string $controller, string $method, array $args = []): ?string
    {
        try {
            $fqcn = 'App\\Controllers\\' . $controller;
            $c = new $fqcn();
            $c->initController(service('request'), \Config\Services::response(null, false), service('logger'));
            ob_start();
            try {
                $r = $c->$method(...$args);
            } finally {
                ob_end_clean();
            }
            if ($r instanceof RedirectResponse || !$r instanceof ResponseInterface) {
                return null;
            }
            $body = (string) $r->getBody();
            return $body !== '' ? $body : null;
        } catch (\Throwable $e) {
            log_message('error', "[Showsaf::exportZip] {$controller}::{$method}: " . $e->getMessage());
            return null;
        }
    }

    // ------------------------------------------------------------------ halaman

    public function index()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }
        $periode = $this->periode();
        if (!$periode) {
            return redirect()->to('/periodesaf')->with('error', 'Periode SAF tidak valid. Silakan pilih periode kembali.');
        }

        ['sections' => $sections, 'canExport' => $canExport] = $this->status($periode);
        $periodeId = (int) $periode['id'];

        $data = [
            'judul' => 'Pelaporan Strategi Anti Fraud (SAF)',
            'namabpr' => $this->infobprModel->getBprByKode($this->userKodebpr)['namabpr'] ?? null,
            'semester' => LaporanPdfSafModel::semNum($periode['semester'] ?? ''),
            'tahun' => $periode['tahun'] ?? '',
            'jenis' => $periode['jenispelaporan'] ?? '',
            'periodeId' => $periodeId,
            'sections' => $sections,
            'canExport' => $canExport,
            'hasLaporanPdf' => count($sections) > 1,
            'unreadComments' => (new M_komentar())->unreadCounts($this->userKodebpr, $periodeId, (int) $this->auth->id(), M_komentar::GROUP_SAF),
        ];

        return view('templates/v_header', $data)
            . view('templates/v_sidebar')
            . view('templates/v_topbar')
            . view('Laporansaf/showsaf', $data)
            . view('templates/v_footer');
    }

    public function exportZip()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }
        $periode = $this->periode();
        if (!$periode) {
            return redirect()->to('/periodesaf')->with('error', 'Periode SAF tidak valid.');
        }
        if (!$this->status($periode)['canExport']) {
            return redirect()->to(base_url('Showsaf'))->with('error', 'ZIP belum dapat dibuat: masih ada bagian yang belum disetujui.');
        }

        @set_time_limit(300);

        $kodebpr = $this->userKodebpr;
        $periodeId = (int) $periode['id'];
        $jenis = $periode['jenispelaporan'] ?? 'SAFTS';
        $kodejenis = $periode['kodejenis'] ?? '';
        $kodeform = $periode['kodeform'] ?? '';
        $is01A = in_array($jenis, self::JENIS_01A, true);
        $tahun = $periode['tahun'] ?? date('Y');
        $tanggalFile = LaporanPdfSafModel::semNum($periode['semester'] ?? '') === '1' ? "{$tahun}0630" : "{$tahun}1231";
        $bpr = $this->infobprModel->getBprByKode($kodebpr) ?? [];
        $sandibpr = $bpr['sandibpr'] ?? '';
        $namaBpr = trim(preg_replace('/\s+/', ' ', preg_replace('/[^A-Za-z0-9\s]/', '', $bpr['namabpr'] ?? 'BPR')));
        $name = static fn(string $form, string $ext) => "{$jenis}-{$form}-{$kodejenis}-{$kodeform}-{$tanggalFile}-{$sandibpr}-01.{$ext}";

        // Pastikan header laporan PDF ada (dibuat di sini, bukan setiap halaman dibuka)
        $this->laporanModel->siapkan($kodebpr, $periodeId, (int) $this->auth->id() ?: null);

        $files = [
            $name($is01A ? '01A' : '01B', 'txt') => $this->runExport('Penerapansafst', 'exporttxtpenerapansafst'),
            $name($is01A ? '02A' : '02B', 'pdf') => $this->runExport('Laporanpdfsaf', 'pdfFraud', [$periodeId]),
        ];
        if ($is01A) {
            $files[$name('00A', 'pdf')] = $this->runExport('Laporanpdfsaf', 'pdf', [$periodeId]);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'saf');
        $zip = new \ZipArchive();
        if ($zip->open($tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return redirect()->to(base_url('Showsaf'))->with('error', 'Gagal membuat file ZIP.');
        }
        foreach ($files as $fileName => $content) {
            if ($content !== null) {
                $zip->addFromString($fileName, $content);
            } else {
                log_message('error', "[Showsaf::exportZip] {$fileName}: kosong / gagal dibuat");
            }
        }
        $count = $zip->numFiles;
        $zip->close();

        $body = $count ? (string) file_get_contents($tmp) : '';
        @unlink($tmp);
        if ($body === '') {
            return redirect()->to(base_url('Showsaf'))->with('error', 'File ZIP gagal dibuat.');
        }

        $zipName = 'NBPSIMPEL - Laporan SAF - ' . $namaBpr . ' - Semester ' . LaporanPdfSafModel::semNum($periode['semester'] ?? '') . ' ' . $tahun . '.zip';
        return $this->response
            ->setHeader('Content-Type', 'application/zip')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $zipName . '"')
            ->setHeader('Content-Length', (string) strlen($body))
            ->setBody($body);
    }
}
