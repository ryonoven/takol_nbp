<?php

namespace App\Controllers;

use App\Models\M_infobpr;
use App\Models\M_periodetahunan;
use App\Models\M_perkembanganpasar;
use App\Models\M_user;
use CodeIgniter\Controller;
use Dompdf\Dompdf;
use Dompdf\Options;
use Myth\Auth\Config\Services as AuthServices;

class Perkembanganpasar extends Controller
{
    protected $pasarModel;
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
    private const VIEW = 'Laporantahunan/perkembanganpasar';
    private const VIEW_PDF = 'Laporantahunan/pdf_perkembanganpasar';
    private const PERIODE_REDIRECT = '/periodetahunan';
    /** true = PDF hanya bisa dibuka setelah disetujui Direktur Utama. */
    private const PDF_REQUIRE_APPROVAL = false;
    /** Kode form LTBPRK untuk nama file PDF. */
    private const PDF_FORM = 'A0504'; // TODO: konfirmasi kode form

    public function __construct()
    {
        $this->pasarModel = new M_perkembanganpasar();
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

    private static function aman(string $s): string
    {
        return preg_replace('/[^A-Za-z0-9_-]/', '', $s);
    }

    /** Folder PDF per BPR (di luar public, diakses lewat action pdf). */
    private static function pdfDir(string $kodebpr): string
    {
        return WRITEPATH . M_perkembanganpasar::PDF_DIR . self::aman($kodebpr) . '/';
    }

    /** Path lengkap PDF upload milik row, atau null bila tidak ada / nama tidak valid. */
    private static function pdfPath(?array $row): ?string
    {
        $name = (string) ($row['perkembanganpdf'] ?? '');
        if (!$row || $name === '' || !preg_match('/^[A-Za-z0-9_-]+\.pdf$/', $name)) {
            return null;
        }
        $path = self::pdfDir((string) $row['kodebpr']) . $name;
        return is_file($path) ? $path : null;
    }

    /** Gambar lokal -> data URI untuk Dompdf (isRemoteEnabled = false). */
    private static function dataUri(?string $file): ?string
    {
        if (!$file || !is_file($file)) {
            return null;
        }
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mime = $ext === 'png' ? 'image/png' : ($ext === 'gif' ? 'image/gif' : 'image/jpeg');
        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($file));
    }

    // ------------------------------------------------------------------ halaman

    public function index()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }

        $periode = $this->resolvePeriode();
        $row = $periode ? $this->pasarModel->getByPeriode((int) $periode['id'], $periode['kodebpr']) : null;

        $data = [
            'judul' => 'Perkembangan dan Target Pasar',
            'periode' => $periode,
            'row' => $row ?? [],
            'max' => M_perkembanganpasar::MAX,
            'required' => M_perkembanganpasar::REQUIRED,
            'pdfMaxKb' => M_perkembanganpasar::PDF_MAX_KB,
            'hasPdf' => (bool) self::pdfPath($row),
            'hasApproval' => self::isApproved($row),
            'canEdit' => $periode && $this->canEditGroup(),
            'namabpr' => $periode ? ($this->infobprModel->getBprByKode($periode['kodebpr'])['namabpr'] ?? 'Nama BPR') : '',
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

        $row = $this->pasarModel->getByPeriode((int) $periode['id'], $periode['kodebpr']);
        $oldPdfPath = self::pdfPath($row);

        $data = [];
        $errors = [];
        $post = $this->request->getPost();

        // ---- PDF Laporan Perkembangan (opsional)
        $file = $this->request->getFile('perkembanganpdf');
        $hasUpload = $file && $file->getError() !== UPLOAD_ERR_NO_FILE;
        $removePdf = !$hasUpload && $oldPdfPath && (string) ($post['hapus_pdf'] ?? '') === '1';
        if ($hasUpload) {
            $maxKb = M_perkembanganpasar::PDF_MAX_KB;
            if (!$file->isValid() || $file->hasMoved()) {
                $errors['perkembanganpdf'] = 'Upload gagal: ' . $file->getErrorString();
            } elseif (strtolower($file->getClientExtension()) !== 'pdf' || $file->getMimeType() !== 'application/pdf') {
                $errors['perkembanganpdf'] = 'File harus berformat PDF.';
            } elseif ($file->getSize() > $maxKb * 1024) {
                $errors['perkembanganpdf'] = 'Ukuran file maksimal ' . ($maxKb / 1024) . ' MB.';
            } elseif (file_get_contents($file->getTempName(), false, null, 0, 5) !== '%PDF-') {
                $errors['perkembanganpdf'] = 'File bukan PDF yang valid.';
            }
        }
        // Setelah disimpan, apakah masih ada PDF upload? Jika tidak, teks wajib (PDF akan di-generate).
        $willHavePdf = $hasUpload || ($oldPdfPath && !$removePdf);

        // ---- teks
        foreach (M_perkembanganpasar::MAX as $field => $max) {
            $v = trim((string) ($post[$field] ?? ''));
            if ($v === '' && !$willHavePdf && in_array($field, M_perkembanganpasar::REQUIRED, true)) {
                $errors[$field] = 'Wajib diisi (atau unggah PDF Laporan Perkembangan).';
            } elseif (mb_strlen($v) > $max) {
                $errors[$field] = "Maksimal {$max} karakter.";
            }
            $data[$field] = $v;
        }

        if ($errors) {
            return $this->json(422, ['ok' => false, 'message' => 'Periksa kembali isian yang ditandai.', 'errors' => $errors]);
        }

        // Mengubah isi (teks / PDF) me-reset status approval.
        $changed = !$row || $hasUpload || $removePdf;
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
            $data += M_perkembanganpasar::resetApprovalData();
        }

        // ---- simpan PDF baru (nama pendek, kolom varchar(50))
        $dir = self::pdfDir($periode['kodebpr']);
        $newPdf = null;
        if ($hasUpload) {
            if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                return $this->json(500, ['ok' => false, 'message' => 'Folder upload tidak dapat dibuat.']);
            }
            $newPdf = 'pp' . (int) $periode['id'] . '_' . bin2hex(random_bytes(5)) . '.pdf';
            try {
                $file->move($dir, $newPdf);
            } catch (\Throwable $e) {
                log_message('error', 'Perkembanganpasar::save upload ' . $e->getMessage());
                return $this->json(500, ['ok' => false, 'message' => 'File PDF gagal disimpan.']);
            }
            $data['perkembanganpdf'] = $newPdf;
        } elseif ($removePdf) {
            $data['perkembanganpdf'] = '';
        }

        $data['user_id'] = $this->userId;
        $data['kodebpr'] = $periode['kodebpr'];
        $data['periode_id'] = (int) $periode['id'];

        try {
            $ok = $this->pasarModel->saveRow($row ? (int) $row['id'] : null, $data);
        } catch (\Throwable $e) {
            log_message('error', 'Perkembanganpasar::save ' . $e->getMessage());
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

    // ------------------------------------------------------------------ PDF

    /**
     * Lihat PDF:
     *  - ada PDF Laporan Perkembangan yang diunggah -> tampilkan file tersebut apa adanya;
     *  - tidak ada -> generate dari isian teks (Dompdf), seperti Laporanpdfsaf.
     */
    public function pdf()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }

        $periode = $this->resolvePeriode();
        $row = $periode ? $this->pasarModel->getByPeriode((int) $periode['id'], $periode['kodebpr']) : null;

        if (!$row) {
            return redirect()->to(site_url('perkembanganpasar'))->with('error', 'Data belum diisi, PDF belum tersedia.');
        }
        if (self::PDF_REQUIRE_APPROVAL && !self::isApproved($row)) {
            return redirect()->to(site_url('perkembanganpasar'))
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
                'tahun' => $periode['tahun'],
                'logo' => \App\Libraries\PdfLogo::fromBpr($bpr),
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

    // ------------------------------------------------------------------ approval

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
        $row = $periode ? $this->pasarModel->getByPeriode((int) $periode['id'], $periode['kodebpr']) : null;
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

        $ok = $this->pasarModel->setApproval((int) $row['id'], $act === 'approve', $this->userId);

        return $ok
            ? $this->json(200, ['ok' => true, 'message' => $act === 'approve' ? 'Berhasil di-approve.' : 'Approval dibatalkan.'])
            : $this->json(500, ['ok' => false, 'message' => 'Gagal memproses approval.']);
    }
}
