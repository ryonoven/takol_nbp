<?php

namespace App\Controllers;

use App\Models\M_bidusahathn;
use App\Models\M_infobpr;
use App\Models\M_periodetahunan;
use App\Models\M_user;
use CodeIgniter\Controller;
use Dompdf\Dompdf;
use Dompdf\Options;
use Myth\Auth\Config\Services as AuthServices;

class Bidusahathn extends Controller
{
    protected $bidusahaModel;
    protected $periodeModel;
    protected $infobprModel;
    protected $userModel;
    protected $auth;
    protected $userId;
    protected $userKodebpr;

    protected $userInGroupPE;
    protected $userInGroupAdmin;
    protected $userInGroupDireksi;

    private const SESSION_PERIODE = 'active_periode';
    private const VIEW = 'Laporantahunan/bidusahathn';
    private const VIEW_PDF = 'Laporantahunan/pdf_bidusahathn';
    private const PERIODE_REDIRECT = '/periodetahunan';

    public function __construct()
    {
        $this->bidusahaModel = new M_bidusahathn();
        $this->periodeModel = new M_periodetahunan();
        $this->infobprModel = new M_infobpr();
        $this->userModel = new M_user();
        $this->auth = service('authentication');

        $this->userId = 0;
        $this->userKodebpr = null;
        $this->userInGroupPE = $this->userInGroupAdmin = $this->userInGroupDireksi = false;

        if ($this->auth->check()) {
            $this->userId = (int) $this->auth->id();
            $this->userKodebpr = $this->userModel->find($this->userId)['kodebpr'] ?? null;

            $authorize = AuthServices::authorization();
            $this->userInGroupPE = $authorize->inGroup('pe', $this->userId);
            $this->userInGroupAdmin = $authorize->inGroup('admin', $this->userId);
            $this->userInGroupDireksi = $authorize->inGroup('direksi', $this->userId);
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

    /** Periode tahunan aktif; 'kodebpr' dipaksa milik user agar semua query terisolasi per BPR. */
    private function resolvePeriode(): ?array
    {
        $periodeId = (int) session(self::SESSION_PERIODE);
        if (!$this->userKodebpr || $periodeId <= 0) {
            return null;
        }
        $periode = $this->periodeModel->find($periodeId);
        if (!$periode) {
            return null;
        }
        $periode['kodebpr'] = $this->userKodebpr;
        return $periode;
    }

    private function canEdit(): bool
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

    /** Validasi umum request POST + hak akses. Mengembalikan [periode, errorResponse]. */
    private function guardPost(bool $needEdit): array
    {
        if (!$this->isPost()) {
            return [null, $this->json(405, ['ok' => false, 'message' => 'Metode tidak diizinkan.'])];
        }
        if (!$this->auth->check()) {
            return [null, $this->json(401, ['ok' => false, 'message' => 'Sesi berakhir. Silakan login kembali.'])];
        }
        if ($needEdit && !$this->canEdit()) {
            return [null, $this->json(403, ['ok' => false, 'message' => 'Anda tidak memiliki akses untuk mengisi halaman ini.'])];
        }
        $periode = $this->resolvePeriode();
        if (!$periode) {
            return [null, $this->json(422, ['ok' => false, 'message' => 'Periode tahunan aktif tidak ditemukan.'])];
        }
        return [$periode, null];
    }

    /** Setiap perubahan data me-reset approval seluruh periode. Mengembalikan true bila sebelumnya sudah approved. */
    private function resetApproval(array $periode): bool
    {
        $rows = $this->bidusahaModel->getByPeriode((int) $periode['id'], $periode['kodebpr']);
        $was = in_array(1, array_map(static fn($r) => (int) $r['is_approved'], $rows), true);
        if ($was) {
            $this->bidusahaModel->setApproval((int) $periode['id'], $periode['kodebpr'], false, $this->userId);
        }
        return $was;
    }

    // ------------------------------------------------------------------ halaman

    public function index()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }

        $periode = $this->resolvePeriode();
        $rows = $periode ? $this->bidusahaModel->getByPeriode((int) $periode['id'], $periode['kodebpr']) : [];

        $data = [
            'judul' => 'Bidang Usaha',
            'periode' => $periode,
            'rows' => $rows,
            'approval' => M_bidusahathn::approvalOf($rows),
            'keterangan' => M_bidusahathn::keteranganOf($rows),
            'maxKeterangan' => M_bidusahathn::MAX_KETERANGAN,
            'max' => M_bidusahathn::MAX,
            'required' => M_bidusahathn::REQUIRED,
            'kategori' => M_bidusahathn::KATEGORI,
            'jenis' => M_bidusahathn::JENIS,
            'canEdit' => $periode && $this->canEdit(),
            'canApprove' => $rows && $this->canApprove(),
            'namabpr' => $periode ? ($this->infobprModel->getBprByKode($periode['kodebpr'])['namabpr'] ?? 'Nama BPR') : '',
        ];

        return view('templates/v_header', $data)
            . view('templates/v_sidebar')
            . view('templates/v_topbar')
            . view(self::VIEW, $data)
            . view('templates/v_footer');
    }

    /** Tambah (id kosong) atau ubah satu baris. */
    public function save()
    {
        [$periode, $err] = $this->guardPost(true);
        if ($err) {
            return $err;
        }

        $post = $this->request->getPost();
        $id = (int) ($post['id'] ?? 0) ?: null;

        $row = null;
        if ($id) {
            $row = $this->bidusahaModel->findOwned($id, (int) $periode['id'], $periode['kodebpr']);
            if (!$row) {
                return $this->json(404, ['ok' => false, 'message' => 'Data tidak ditemukan.']);
            }
        }

        $errors = [];
        $data = [
            'kategoriusaha' => (string) ($post['kategoriusaha'] ?? ''),
            'jenisproduk' => (string) ($post['jenisproduk'] ?? ''),
        ];
        if (!isset(M_bidusahathn::KATEGORI[$data['kategoriusaha']])) {
            $errors['kategoriusaha'] = 'Pilih kategori usaha.';
        }
        if (!isset(M_bidusahathn::JENIS[$data['jenisproduk']])) {
            $errors['jenisproduk'] = 'Pilih jenis produk.';
        }

        foreach (M_bidusahathn::MAX as $field => $max) {
            $v = trim((string) ($post[$field] ?? ''));
            if ($v === '' && in_array($field, M_bidusahathn::REQUIRED, true)) {
                $errors[$field] = 'Wajib diisi.';
            } elseif (mb_strlen($v) > $max) {
                $errors[$field] = "Maksimal {$max} karakter.";
            }
            $data[$field] = $v;
        }

        if ($errors) {
            return $this->json(422, ['ok' => false, 'message' => 'Periksa kembali isian yang ditandai.', 'errors' => $errors]);
        }

        // Tidak ada perubahan -> tidak perlu simpan / reset approval.
        if ($row && !array_diff_assoc(array_map('strval', $data), array_map('strval', array_intersect_key($row, $data)))) {
            return $this->json(200, ['ok' => true, 'message' => 'Tidak ada perubahan.']);
        }

        $data['user_id'] = $this->userId;
        $data['kodebpr'] = $periode['kodebpr'];
        $data['periode_id'] = (int) $periode['id'];
        if (!$id) {
            // Baris baru ikut keterangan (footer) periode yang sudah ada.
            $data['keterangan'] = M_bidusahathn::keteranganOf(
                $this->bidusahaModel->getByPeriode((int) $periode['id'], $periode['kodebpr'])
            );
        }

        try {
            $wasReset = $this->resetApproval($periode);
            $ok = $this->bidusahaModel->saveItem($id, $data);
        } catch (\Throwable $e) {
            log_message('error', 'Bidusahathn::save ' . $e->getMessage());
            $ok = false;
        }

        return $ok
            ? $this->json(200, ['ok' => true, 'message' => 'Data berhasil disimpan.' . ($wasReset ? ' Status approval direset.' : '')])
            : $this->json(500, ['ok' => false, 'message' => 'Data gagal disimpan.']);
    }

    /** Simpan Keterangan (footer F01) untuk seluruh baris periode. */
    public function savefooter()
    {
        [$periode, $err] = $this->guardPost(true);
        if ($err) {
            return $err;
        }

        $rows = $this->bidusahaModel->getByPeriode((int) $periode['id'], $periode['kodebpr']);
        if (!$rows) {
            return $this->json(422, ['ok' => false, 'message' => 'Tambahkan minimal satu produk terlebih dahulu.']);
        }

        $ket = trim((string) $this->request->getPost('keterangan'));
        if (mb_strlen($ket) > M_bidusahathn::MAX_KETERANGAN) {
            return $this->json(422, ['ok' => false, 'message' => 'Periksa kembali isian.', 'errors' => ['keterangan' => 'Maksimal ' . M_bidusahathn::MAX_KETERANGAN . ' karakter.']]);
        }
        if ($ket === M_bidusahathn::keteranganOf($rows)) {
            return $this->json(200, ['ok' => true, 'message' => 'Tidak ada perubahan.']);
        }

        $wasReset = $this->resetApproval($periode);
        $ok = $this->bidusahaModel->setKeterangan((int) $periode['id'], $periode['kodebpr'], $ket);

        return $ok
            ? $this->json(200, ['ok' => true, 'message' => 'Keterangan disimpan.' . ($wasReset ? ' Status approval direset.' : '')])
            : $this->json(500, ['ok' => false, 'message' => 'Keterangan gagal disimpan.']);
    }

    public function delete()
    {
        [$periode, $err] = $this->guardPost(true);
        if ($err) {
            return $err;
        }

        $id = (int) $this->request->getPost('id');
        if (!$id || !$this->bidusahaModel->findOwned($id, (int) $periode['id'], $periode['kodebpr'])) {
            return $this->json(404, ['ok' => false, 'message' => 'Data tidak ditemukan.']);
        }

        $wasReset = $this->resetApproval($periode);
        $ok = (bool) $this->bidusahaModel->delete($id);

        return $ok
            ? $this->json(200, ['ok' => true, 'message' => 'Data dihapus.' . ($wasReset ? ' Status approval direset.' : '')])
            : $this->json(500, ['ok' => false, 'message' => 'Data gagal dihapus.']);
    }

    // ------------------------------------------------------------------ approval

    public function approve()
    {
        [$periode, $err] = $this->guardPost(false);
        if ($err) {
            return $err;
        }
        if (!$this->canApprove()) {
            return $this->json(403, ['ok' => false, 'message' => 'Anda tidak memiliki akses untuk approval ini.']);
        }

        $act = (string) $this->request->getPost('act');   // approve | cancel
        if (!in_array($act, ['approve', 'cancel'], true)) {
            return $this->json(422, ['ok' => false, 'message' => 'Permintaan tidak valid.']);
        }

        $rows = $this->bidusahaModel->getByPeriode((int) $periode['id'], $periode['kodebpr']);
        if (!$rows) {
            return $this->json(422, ['ok' => false, 'message' => 'Data belum diisi, tidak dapat di-approve.']);
        }
        $approval = M_bidusahathn::approvalOf($rows);

        if ($act === 'approve' && $approval['approved']) {
            return $this->json(409, ['ok' => false, 'message' => 'Sudah di-approve sebelumnya.']);
        }
        if ($act === 'cancel') {
            if (!$approval['approved']) {
                return $this->json(409, ['ok' => false, 'message' => 'Belum di-approve.']);
            }
            if (!$this->userInGroupAdmin && $approval['by'] !== $this->userId) {
                return $this->json(403, ['ok' => false, 'message' => 'Hanya yang meng-approve yang dapat membatalkan.']);
            }
        }

        $ok = $this->bidusahaModel->setApproval((int) $periode['id'], $periode['kodebpr'], $act === 'approve', $this->userId);

        return $ok
            ? $this->json(200, ['ok' => true, 'message' => $act === 'approve' ? 'Berhasil di-approve.' : 'Approval dibatalkan.'])
            : $this->json(500, ['ok' => false, 'message' => 'Gagal memproses approval.']);
    }

    // ------------------------------------------------------------------ TXT (APOLO)

    private static function txtClean($text): string
    {
        $text = str_replace(["\r", "\n", "\t", '|'], ' ', (string) $text);
        $text = str_replace(["\xE2\x80\x9C", "\xE2\x80\x9D"], '"', $text);
        $text = str_replace(["\xE2\x80\x98", "\xE2\x80\x99"], "'", $text);
        $text = str_replace('%', ' persen ', $text);
        $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    /**
     * Export TXT Form A05.02 Bidang Usaha (LTBPRK).
     *   H01|kodejenis|sandibpr|YYYY-12-31|LTBPRK|A0502|0|
     *   D01|05001|kategori|jenis|namaproduk|uraian      (per baris)
     *   F01|keterangan                                  (bila diisi)
     * Juga dipanggil dari Showtahunan::exportAllToZip (instansiasi langsung),
     * karena itu memakai service('response'), bukan $this->response.
     */
    public function exporttxt()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }

        $periode = $this->resolvePeriode();
        if (!$periode) {
            return redirect()->to(self::PERIODE_REDIRECT)->with('error', 'Periode tahunan aktif tidak ditemukan.');
        }

        $rows = $this->bidusahaModel->getByPeriode((int) $periode['id'], $periode['kodebpr']);
        $bpr = $this->infobprModel->getBprByKode($periode['kodebpr']) ?? [];
        $sandibpr = $bpr['sandibpr'] ?? '';
        $kodejenis = $bpr['kodejenis'] ?? '';
        $tahun = $periode['tahun'] ?? date('Y');
        $form = M_bidusahathn::FORM_CODE;

        $output = "H01|{$kodejenis}|{$sandibpr}|{$tahun}-12-31|LTBPRK|{$form}|0|\r\n";
        foreach ($rows as $r) {
            $output .= implode('|', [
                'D01',
                M_bidusahathn::KODE_KOMPONEN,
                $r['kategoriusaha'],
                $r['jenisproduk'],
                self::txtClean($r['namaproduk']),
                self::txtClean($r['uraian']),
            ]) . "\r\n";
        }
        $ket = self::txtClean(M_bidusahathn::keteranganOf($rows));
        if ($ket !== '') {
            $output .= "F01|{$ket}\r\n";
        }

        $filename = "LTBPRK-{$form}-{$periode['jenispelaporan']}-A-{$tahun}1231-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }

    // ------------------------------------------------------------------ PDF

    public function pdf()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }

        $periode = $this->resolvePeriode();
        $rows = $periode ? $this->bidusahaModel->getByPeriode((int) $periode['id'], $periode['kodebpr']) : [];
        if (!$rows) {
            return redirect()->to(site_url('bidusahathn'))->with('error', 'Data belum diisi, PDF belum tersedia.');
        }

        $bpr = $this->infobprModel->getBprByKode($periode['kodebpr']) ?? [];

        $logo = \App\Libraries\PdfLogo::fromBpr($bpr);

        $o = new Options();
        $o->set('isRemoteEnabled', false);
        $o->set('isPhpEnabled', false);
        $o->set('defaultFont', 'Helvetica');
        $o->set('tempDir', WRITEPATH . 'cache');
        $o->set('fontCache', WRITEPATH . 'cache');

        $pdf = new Dompdf($o);
        $pdf->loadHtml(view(self::VIEW_PDF, [
            'rows' => $rows,
            'bpr' => $bpr,
            'tahun' => $periode['tahun'],
            'logo' => $logo,
            'kategori' => M_bidusahathn::KATEGORI,
            'jenis' => M_bidusahathn::JENIS,
            'keterangan' => M_bidusahathn::keteranganOf($rows),
        ]));
        $pdf->setPaper('A4', 'landscape');
        $pdf->render();

        $filename = 'LTBPRK-' . M_bidusahathn::FORM_CODE . '-' . preg_replace('/[^A-Za-z0-9]/', '', (string) ($periode['jenispelaporan'] ?? ''))
            . '-A-' . (int) $periode['tahun'] . '1231-' . preg_replace('/[^A-Za-z0-9]/', '', (string) ($bpr['sandibpr'] ?? '')) . '-01.pdf';

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->setHeader('Cache-Control', 'private, max-age=0, must-revalidate')
            ->setBody($pdf->output());
    }
}
