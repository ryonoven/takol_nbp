<?php

namespace App\Controllers;

use App\Models\M_infobpr;
use App\Models\M_periodetahunan;
use App\Models\M_suratpernyataandireksi;
use App\Models\M_user;
use CodeIgniter\Controller;
use Dompdf\Dompdf;
use Dompdf\Options;
use Myth\Auth\Config\Services as AuthServices;

class Suratpernyataandireksi extends Controller
{
    protected $suratModel;
    protected $periodeModel;
    protected $infobprModel;
    protected $userModel;
    protected $auth;
    protected $userId;
    protected $userKodebpr;

    protected $userInGroupPE;
    protected $userInGroupAdmin;
    protected $userInGroupDireksi;

    private $_periodeCache = [];

    private const SESSION_PERIODE = 'active_periode';
    private const VIEW = 'Laporantahunan/suratpernyataandireksi';
    private const VIEW_PDF = 'Laporantahunan/pdf_suratpernyataandireksi';
    private const PERIODE_REDIRECT = '/periodetahunan';
    /** true = PDF hanya bisa dibuka setelah disetujui Direktur Utama. */
    private const PDF_REQUIRE_APPROVAL = false;
    /** Kode form LTBPRK untuk nama file PDF. */
    private const PDF_FORM = 'D0000';

    public function __construct()
    {
        $this->suratModel = new M_suratpernyataandireksi();
        $this->periodeModel = new M_periodetahunan();
        $this->infobprModel = new M_infobpr();
        $this->userModel = new M_user();
        $this->auth = service('authentication');

        if ($this->auth->check()) {
            $this->userId = (int) $this->auth->id();
            $user = $this->userModel->find($this->userId);
            $this->userKodebpr = $user['kodebpr'] ?? null;

            $authorize = AuthServices::authorization();
            $this->userInGroupPE = $authorize->inGroup('pe', $this->userId);
            $this->userInGroupAdmin = $authorize->inGroup('admin', $this->userId);
            $this->userInGroupDireksi = $authorize->inGroup('direksi', $this->userId);
        } else {
            $this->userId = 0;
            $this->userKodebpr = null;
            $this->userInGroupPE = $this->userInGroupAdmin = $this->userInGroupDireksi = false;
        }
    }

    // ------------------------------------------------------------------ helpers

    private function checkAuth(): ?\CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->auth->check()) {
            session()->set('redirect_url', current_url());
            return redirect()->to('/login');
        }
        if (!session(self::SESSION_PERIODE)) {
            return redirect()->to(self::PERIODE_REDIRECT)
                ->with('error', 'Silakan pilih periode tahunan aktif terlebih dahulu.');
        }
        if (!$this->userKodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid.');
        }
        return null;
    }

    /** kodebpr dari user, periode dari session. */
    private function getKodebprPeriode(): array
    {
        return [$this->userKodebpr, (int) session(self::SESSION_PERIODE)];
    }

    private function getPeriodeDetail(int $periodeId): array
    {
        if (!isset($this->_periodeCache[$periodeId])) {
            $this->_periodeCache[$periodeId] = $this->periodeModel->find($periodeId) ?? [];
        }
        return $this->_periodeCache[$periodeId];
    }

    /** Periode tahunan aktif; 'kodebpr' dipaksa milik user agar semua query terisolasi per BPR. */
    private function resolvePeriode(): ?array
    {
        [$kodebpr, $periodeId] = $this->getKodebprPeriode();
        if (!$kodebpr || $periodeId <= 0) {
            return null;
        }
        $periode = $this->getPeriodeDetail($periodeId);
        if (!$periode) {
            return null;
        }
        $periode['kodebpr'] = $kodebpr;
        return $periode;
    }

    /** Pengisian hanya PE / admin. */
    private function canEditGroup(): bool
    {
        return $this->userInGroupPE || $this->userInGroupAdmin;
    }

    /** Approval hanya Direktur Utama (grup direksi) / admin. */
    private function canApprove(): bool
    {
        return $this->userInGroupAdmin || $this->userInGroupDireksi;
    }

    private function isPost(): bool
    {
        // Hostinger menghapus X-Requested-With: jangan pakai isAJAX()
        return strtolower((string) $this->request->getMethod()) === 'post';
    }

    private function json(int $status, array $payload)
    {
        $payload['csrf'] = ['name' => csrf_token(), 'hash' => csrf_hash()];
        return $this->response->setStatusCode($status)->setJSON($payload);
    }

    private static function isApproved(?array $row): bool
    {
        return $row && (int) $row['is_approved'] === 1;
    }

    /** Logo BPR (public/asset/img) -> data URI untuk Dompdf (isRemoteEnabled = false). */
    private static function logoUri(array $bpr): ?string
    {
        $file = !empty($bpr['logo']) ? FCPATH . 'asset/img/' . basename($bpr['logo']) : null;
        if (!$file || !is_file($file)) {
            return null;
        }
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mime = $ext === 'png' ? 'image/png' : ($ext === 'gif' ? 'image/gif' : 'image/jpeg');
        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($file));
    }

    private static function aman(string $s): string
    {
        return preg_replace('/[^A-Za-z0-9_-]/', '', $s);
    }

    /** Folder PDF per BPR (di luar public, diakses lewat action pdf). */
    private static function pdfDir(string $kodebpr): string
    {
        return WRITEPATH . M_suratpernyataandireksi::PDF_DIR . self::aman($kodebpr) . '/';
    }

    /** Path lengkap PDF upload milik row, atau null bila tidak ada / nama tidak valid. */
    private static function pdfPath(?array $row): ?string
    {
        $name = (string) ($row['suratpdf'] ?? '');
        if (!$row || $name === '' || !preg_match('/^[A-Za-z0-9_-]+\.pdf$/', $name)) {
            return null;
        }
        $path = self::pdfDir((string) $row['kodebpr']) . $name;
        return is_file($path) ? $path : null;
    }

    // ------------------------------------------------------------------ halaman

    public function index()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }

        $periode = $this->resolvePeriode();
        $row = $periode ? $this->suratModel->getByPeriode((int) $periode['id'], $periode['kodebpr']) : null;

        $bpr = $periode ? ($this->infobprModel->getBprByKode($periode['kodebpr']) ?? []) : [];
        $namabpr = $bpr['namabpr'] ?? 'Nama BPR';

        // Belum ada data: satu blok direksi kosong, Alamat Kantor disarankan dari Info BPR.
        $direksi = $row ? $this->suratModel->getDireksi((int) $row['id']) : [];
        if (!$direksi) {
            $direksi = [array_fill_keys(array_keys(M_suratpernyataandireksi::DIREKSI_FIELDS), '')];
            $direksi[0]['alamatkantor'] = (string) ($bpr['alamat'] ?? '');
        }

        $data = [
            'judul' => 'Surat Pernyataan Direksi',
            'periode' => $periode,
            'row' => $row ?? [],
            'direksi' => $direksi,
            'direksiFields' => M_suratpernyataandireksi::DIREKSI_FIELDS,
            'minDireksi' => M_suratpernyataandireksi::MIN_DIREKSI,
            'maxDireksi' => M_suratpernyataandireksi::MAX_DIREKSI,
            'maxKota' => M_suratpernyataandireksi::MAX_KOTA,
            'pernyataan' => $periode ? M_suratpernyataandireksi::pernyataan($namabpr, $periode['tahun']) : [],
            'alamatBpr' => $bpr['alamat'] ?? '',   // isian awal Alamat Kantor untuk direksi baru
            'pdfMaxKb' => M_suratpernyataandireksi::PDF_MAX_KB,
            'hasPdf' => (bool) self::pdfPath($row),
            'hasApproval' => self::isApproved($row),
            'canEdit' => $periode && $this->canEditGroup(),
            'namabpr' => $periode ? $namabpr : '',
            'backUrl' => base_url(ltrim(self::PERIODE_REDIRECT, '/')),
            'canApproveDireksi' => (bool) ($row && $this->canApprove()),
            'canViewPdf' => (bool) ($row && (!self::PDF_REQUIRE_APPROVAL || self::isApproved($row))),
        ];

        return view('templates/v_header', $data)
            . view('templates/v_sidebar')
            . view('templates/v_topbar')
            . view(self::VIEW, $data)
            . view('templates/v_footer');
    }

    public function save()
    {
        if (!$this->isPost()) {
            return $this->json(405, ['ok' => false, 'message' => 'Metode tidak diizinkan.']);
        }
        if (!$this->auth->check()) {
            return $this->json(401, ['ok' => false, 'message' => 'Sesi berakhir. Silakan login kembali.']);
        }
        if (!$this->canEditGroup()) {
            return $this->json(403, ['ok' => false, 'message' => 'Anda tidak memiliki akses untuk mengisi halaman ini.']);
        }

        $periode = $this->resolvePeriode();
        if (!$periode) {
            return $this->json(422, ['ok' => false, 'message' => 'Periode tahunan aktif tidak ditemukan.']);
        }

        $row = $this->suratModel->getByPeriode((int) $periode['id'], $periode['kodebpr']);
        $oldPdfPath = self::pdfPath($row);

        $data = [];
        $errors = [];
        $post = $this->request->getPost();

        // Scan surat bertanda tangan + meterai (opsional)
        $file = $this->request->getFile('suratpdf');
        $hasUpload = $file && $file->getError() !== UPLOAD_ERR_NO_FILE;
        $removePdf = !$hasUpload && $oldPdfPath && (string) ($post['hapus_pdf'] ?? '') === '1';
        if ($hasUpload) {
            $maxKb = M_suratpernyataandireksi::PDF_MAX_KB;
            if (!$file->isValid() || $file->hasMoved()) {
                $errors['suratpdf'] = 'Upload gagal: ' . $file->getErrorString();
            } elseif (strtolower($file->getClientExtension()) !== 'pdf' || $file->getMimeType() !== 'application/pdf') {
                $errors['suratpdf'] = 'File harus berformat PDF.';
            } elseif ($file->getSize() > $maxKb * 1024) {
                $errors['suratpdf'] = 'Ukuran file maksimal ' . ($maxKb / 1024) . ' MB.';
            } elseif (file_get_contents($file->getTempName(), false, null, 0, 5) !== '%PDF-') {
                $errors['suratpdf'] = 'File bukan PDF yang valid.';
            }
        }
        // Setelah disimpan, apakah masih ada PDF upload? Jika tidak, teks wajib (PDF akan di-generate).
        $willHavePdf = $hasUpload || ($oldPdfPath && !$removePdf);

        // Kota
        $data['kota'] = trim((string) ($post['kota'] ?? ''));
        if (mb_strlen($data['kota']) > M_suratpernyataandireksi::MAX_KOTA) {
            $errors['kota'] = 'Maksimal ' . M_suratpernyataandireksi::MAX_KOTA . ' karakter.';
        } elseif ($data['kota'] === '' && !$willHavePdf) {
            $errors['kota'] = 'Wajib diisi.';
        }

        $fields = M_suratpernyataandireksi::DIREKSI_FIELDS;
        $input = [];
        foreach (array_keys($fields) as $f) {
            $input[$f] = (array) ($post['d_' . $f] ?? []);
        }
        $direksi = [];
        $count = max(array_map('count', $input));
        for ($i = 0; $i < $count; $i++) {
            $d = [];
            foreach ($fields as $f => [, $max]) {
                $d[$f] = trim((string) ($input[$f][$i] ?? ''));
            }
            if (implode('', $d) === '') {
                continue;
            }
            foreach ($fields as $f => [, $max]) {
                $key = "d_{$f}.{$i}";
                if (mb_strlen($d[$f]) > $max) {
                    $errors[$key] = "Maksimal {$max} karakter.";
                } elseif ($d[$f] === '' && !$willHavePdf) {
                    $errors[$key] = 'Wajib diisi.';
                } elseif ($f === 'telepon' && $d[$f] !== '' && !preg_match('/^[0-9+()\-\s.]+$/', $d[$f])) {
                    $errors[$key] = 'Nomor telepon tidak valid.';
                }
            }
            $direksi[] = $d;
        }
        if (count($direksi) > M_suratpernyataandireksi::MAX_DIREKSI) {
            $errors['direksi'] = 'Maksimal ' . M_suratpernyataandireksi::MAX_DIREKSI . ' direksi.';
        } elseif (count($direksi) < M_suratpernyataandireksi::MIN_DIREKSI && !$willHavePdf) {
            $errors['direksi'] = 'Isi minimal ' . M_suratpernyataandireksi::MIN_DIREKSI . ' direksi.';
        }

        $tgl = trim((string) ($post['tanggalsurat'] ?? ''));
        $dt = $tgl === '' ? null : \DateTime::createFromFormat('!Y-m-d', $tgl);
        if ($tgl === '' && !$willHavePdf) {
            $errors['tanggalsurat'] = 'Wajib diisi.';
        } elseif ($tgl !== '' && (!$dt || $dt->format('Y-m-d') !== $tgl)) {
            $errors['tanggalsurat'] = 'Tanggal tidak valid.';
        }
        $data['tanggalsurat'] = $tgl === '' ? null : $tgl;

        if ($errors) {
            return $this->json(422, ['ok' => false, 'message' => 'Periksa kembali isian yang ditandai.', 'errors' => $errors]);
        }

        // Mengubah isi (header / daftar direksi / PDF) me-reset status approval.
        $changed = !$row || $hasUpload || $removePdf
            || $this->suratModel->getDireksi((int) $row['id']) !== $direksi;
        if ($row && !$changed) {
            foreach ($data as $k => $val) {
                if ((string) ($row[$k] ?? '') !== (string) $val) {
                    $changed = true;
                    break;
                }
            }
        }
        $wasReset = $changed && self::isApproved($row);
        if ($changed) {
            $data += M_suratpernyataandireksi::resetApprovalData();
        }

        // Simpan PDF baru (nama pendek, kolom varchar(50))
        $dir = self::pdfDir($periode['kodebpr']);
        $newPdf = null;
        if ($hasUpload) {
            if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                return $this->json(500, ['ok' => false, 'message' => 'Folder upload tidak dapat dibuat.']);
            }
            $newPdf = 'sp' . (int) $periode['id'] . '_' . bin2hex(random_bytes(5)) . '.pdf';
            try {
                $file->move($dir, $newPdf);
            } catch (\Throwable $e) {
                log_message('error', 'Suratpernyataandireksi::save upload ' . $e->getMessage());
                return $this->json(500, ['ok' => false, 'message' => 'File PDF gagal disimpan.']);
            }
            $data['suratpdf'] = $newPdf;
        } elseif ($removePdf) {
            $data['suratpdf'] = '';
        }

        $data['user_id'] = $this->userId;
        $data['kodebpr'] = $periode['kodebpr'];
        $data['periode_id'] = (int) $periode['id'];

        try {
            $ok = $this->suratModel->saveSurat($row ? (int) $row['id'] : null, $data, $direksi);
        } catch (\Throwable $e) {
            log_message('error', 'Suratpernyataandireksi::save ' . $e->getMessage());
            $ok = false;
        }

        if (!$ok) {
            if ($newPdf) {
                @unlink($dir . $newPdf);
            }
            return $this->json(500, ['ok' => false, 'message' => 'Data gagal disimpan.']);
        }

        // PDF lama dihapus setelah data baru tersimpan (diganti / dihapus).
        if (($newPdf || $removePdf) && $oldPdfPath) {
            @unlink($oldPdfPath);
        }

        return $this->json(200, ['ok' => true, 'message' => $wasReset ? 'Data berhasil disimpan. Status approval direset.' : 'Data berhasil disimpan.']);
    }

    public function pdf()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }

        $periode = $this->resolvePeriode();
        $row = $periode ? $this->suratModel->getByPeriode((int) $periode['id'], $periode['kodebpr']) : null;

        if (!$row) {
            return redirect()->to(site_url('suratpernyataandireksi'))->with('error', 'Data belum diisi, PDF belum tersedia.');
        }
        if (self::PDF_REQUIRE_APPROVAL && !self::isApproved($row)) {
            return redirect()->to(site_url('suratpernyataandireksi'))
                ->with('error', 'PDF hanya dapat dilihat setelah disetujui Direktur Utama.');
        }

        $bpr = $this->infobprModel->getBprByKode($periode['kodebpr']) ?? [];
        $filename = 'LTBPRK-' . self::PDF_FORM . '-' . preg_replace('/[^A-Za-z0-9]/', '', (string) ($periode['jenispelaporan'] ?? ''))
            . '-A-' . (int) $periode['tahun'] . '1231-' . preg_replace('/[^A-Za-z0-9]/', '', (string) ($bpr['sandibpr'] ?? '')) . '-01.pdf';

        $uploaded = self::pdfPath($row);
        if ($uploaded) {
            $body = file_get_contents($uploaded);
        } else {

            $o = new Options();
            $o->set('isRemoteEnabled', false);
            $o->set('isPhpEnabled', false);
            $o->set('defaultFont', 'Helvetica');
            $o->set('tempDir', WRITEPATH . 'cache');
            $o->set('fontCache', WRITEPATH . 'cache');

            $pdf = new Dompdf($o);
            $pdf->loadHtml(view(self::VIEW_PDF, [
                'row' => $row,
                'bpr' => $bpr,
                'logo' => \App\Libraries\PdfLogo::fromBpr($bpr),
                'tahun' => $periode['tahun'],
                'direksi' => $this->suratModel->getDireksi((int) $row['id']),
                'direksiFields' => M_suratpernyataandireksi::DIREKSI_FIELDS,
                'pernyataan' => M_suratpernyataandireksi::pernyataan((string) ($bpr['namabpr'] ?? ''), $periode['tahun']),
            ]));
            $pdf->setPaper('A4');
            $pdf->render();
            $body = $pdf->output();
        }

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->setHeader('Cache-Control', 'private, max-age=0, must-revalidate')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody($body);
    }

    public function approve()
    {
        if (!$this->isPost()) {
            return $this->json(405, ['ok' => false, 'message' => 'Metode tidak diizinkan.']);
        }
        if (!$this->auth->check()) {
            return $this->json(401, ['ok' => false, 'message' => 'Sesi berakhir. Silakan login kembali.']);
        }

        $act = (string) $this->request->getPost('act');    // approve | cancel
        if (!in_array($act, ['approve', 'cancel'], true)) {
            return $this->json(422, ['ok' => false, 'message' => 'Permintaan tidak valid.']);
        }
        if (!$this->canApprove()) {
            return $this->json(403, ['ok' => false, 'message' => 'Anda tidak memiliki akses untuk approval ini.']);
        }

        $periode = $this->resolvePeriode();
        $row = $periode ? $this->suratModel->getByPeriode((int) $periode['id'], $periode['kodebpr']) : null;
        if (!$row) {
            return $this->json(422, ['ok' => false, 'message' => 'Data belum diisi, tidak dapat di-approve.']);
        }

        if ($act === 'approve' && self::isApproved($row)) {
            return $this->json(409, ['ok' => false, 'message' => 'Sudah di-approve sebelumnya.']);
        }
        if ($act === 'cancel') {
            if (!self::isApproved($row)) {
                return $this->json(409, ['ok' => false, 'message' => 'Belum di-approve.']);
            }
            if (!$this->userInGroupAdmin && (int) $row['approved_by'] !== $this->userId) {
                return $this->json(403, ['ok' => false, 'message' => 'Hanya yang meng-approve yang dapat membatalkan.']);
            }
        }

        $ok = $this->suratModel->setApproval((int) $row['id'], $act === 'approve', $this->userId);

        return $ok
            ? $this->json(200, ['ok' => true, 'message' => $act === 'approve' ? 'Berhasil di-approve.' : 'Approval dibatalkan.'])
            : $this->json(500, ['ok' => false, 'message' => 'Gagal memproses approval.']);
    }
}
