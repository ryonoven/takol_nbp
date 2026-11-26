<?php

namespace App\Controllers;

use App\Models\M_kerjasamabpr;
use App\Models\M_infobpr;
use App\Models\M_periodetahunan;
use App\Models\M_user;
use CodeIgniter\Controller;
use Dompdf\Dompdf;
use Dompdf\Options;
use Myth\Auth\Config\Services as AuthServices;

class Kerjasamabpr extends Controller
{
    protected $kerjasamaModel;
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
    private const VIEW = 'Laporantahunan/kerjasamabpr';
    private const VIEW_PDF = 'Laporantahunan/pdf_kerjasamabpr';
    private const PERIODE_REDIRECT = '/periodetahunan';

    public function __construct()
    {
        $this->kerjasamaModel = new M_kerjasamabpr();
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

    private function canApprove(): bool
    {
        return $this->userInGroupAdmin || $this->userInGroupDireksi;
    }

    private function isPost(): bool
    {
        return strtolower((string) $this->request->getMethod()) === 'post';
    }

    private function json(int $status, array $payload)
    {
        $payload['csrf'] = ['name' => csrf_token(), 'hash' => csrf_hash()];
        return $this->response->setStatusCode($status)->setJSON($payload);
    }

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
        $rows = $this->kerjasamaModel->getByPeriode((int) $periode['id'], $periode['kodebpr']);
        $was = in_array(1, array_map(static fn($r) => (int) $r['is_approved'], $rows), true);
        if ($was) {
            $this->kerjasamaModel->setApproval((int) $periode['id'], $periode['kodebpr'], false, $this->userId);
        }
        return $was;
    }

    // halaman

    public function index()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }

        $periode = $this->resolvePeriode();
        $rows = $periode ? $this->kerjasamaModel->getByPeriode((int) $periode['id'], $periode['kodebpr']) : [];

        $data = [
            'judul' => 'Kerja Sama BPR dengan Bank atau Lembaga Lain',
            'periode' => $periode,
            'rows' => $rows,
            'approval' => M_kerjasamabpr::approvalOf($rows),
            'keterangan' => M_kerjasamabpr::keteranganOf($rows),
            'maxKeterangan' => M_kerjasamabpr::MAX_KETERANGAN,
            'max' => M_kerjasamabpr::MAX,
            'required' => M_kerjasamabpr::REQUIRED,
            'reflembaga' => M_kerjasamabpr::REFLEMBAGA,
            'refBank' => M_kerjasamabpr::REF_BANK,
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

    /** Tambah atau ubah satu baris. */
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
            $row = $this->kerjasamaModel->findOwned($id, (int) $periode['id'], $periode['kodebpr']);
            if (!$row) {
                return $this->json(404, ['ok' => false, 'message' => 'Data tidak ditemukan.']);
            }
        }

        $errors = [];
        $data = ['reflembaga' => (string) ($post['reflembaga'] ?? '')];
        if (!isset(M_kerjasamabpr::REFLEMBAGA[$data['reflembaga']])) {
            $errors['reflembaga'] = 'Pilih referensi lembaga.';
        }
        $isBank = $data['reflembaga'] === M_kerjasamabpr::REF_BANK;
        $isLain = $data['reflembaga'] === M_kerjasamabpr::REF_LAINNYA;

        foreach (M_kerjasamabpr::MAX as $field => $max) {
            $v = trim((string) ($post[$field] ?? ''));
            // Kondisional: Sandi Bank hanya untuk Bank, NPWP hanya untuk Lembaga Lain (yang lain dikosongkan).
            if (($field === 'sandibank' && !$isBank) || ($field === 'npwp' && !$isLain)) {
                $data[$field] = '';
                continue;
            }
            $required = in_array($field, M_kerjasamabpr::REQUIRED, true) || $field === 'sandibank' || $field === 'npwp';
            if ($v === '' && $required) {
                $errors[$field] = 'Wajib diisi.';
            } elseif (mb_strlen($v) > $max) {
                $errors[$field] = "Maksimal {$max} karakter.";
            } elseif ($field === 'sandibank' && !ctype_digit($v)) {
                $errors[$field] = 'Sandi bank hanya berisi angka.';
            } elseif ($field === 'npwp' && !preg_match('/^[0-9.\-]+$/', $v)) {
                $errors[$field] = 'NPWP hanya berisi angka, titik, dan tanda hubung.';
            }
            $data[$field] = $v;
        }

        $tgl = trim((string) ($post['tglkerjasama'] ?? ''));
        $dt = \DateTime::createFromFormat('!Y-m-d', $tgl);
        if ($tgl === '') {
            $errors['tglkerjasama'] = 'Wajib diisi.';
        } elseif (!$dt || $dt->format('Y-m-d') !== $tgl) {
            $errors['tglkerjasama'] = 'Tanggal tidak valid.';
        }
        $data['tglkerjasama'] = $tgl;

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
            $data['keterangan'] = M_kerjasamabpr::keteranganOf(
                $this->kerjasamaModel->getByPeriode((int) $periode['id'], $periode['kodebpr'])
            );
        }

        try {
            $wasReset = $this->resetApproval($periode);
            $ok = $this->kerjasamaModel->saveItem($id, $data);
        } catch (\Throwable $e) {
            log_message('error', 'Kerjasamabpr::save ' . $e->getMessage());
            $ok = false;
        }

        return $ok
            ? $this->json(200, ['ok' => true, 'message' => 'Data berhasil disimpan.' . ($wasReset ? ' Status approval direset.' : '')])
            : $this->json(500, ['ok' => false, 'message' => 'Data gagal disimpan.']);
    }

    /** Simpan Keterangan untuk seluruh baris periode. */
    public function savefooter()
    {
        [$periode, $err] = $this->guardPost(true);
        if ($err) {
            return $err;
        }

        $rows = $this->kerjasamaModel->getByPeriode((int) $periode['id'], $periode['kodebpr']);
        if (!$rows) {
            return $this->json(422, ['ok' => false, 'message' => 'Tambahkan minimal satu data kerja sama terlebih dahulu.']);
        }

        $ket = trim((string) $this->request->getPost('keterangan'));
        if (mb_strlen($ket) > M_kerjasamabpr::MAX_KETERANGAN) {
            return $this->json(422, ['ok' => false, 'message' => 'Periksa kembali isian.', 'errors' => ['keterangan' => 'Maksimal ' . M_kerjasamabpr::MAX_KETERANGAN . ' karakter.']]);
        }
        if ($ket === M_kerjasamabpr::keteranganOf($rows)) {
            return $this->json(200, ['ok' => true, 'message' => 'Tidak ada perubahan.']);
        }

        $wasReset = $this->resetApproval($periode);
        $ok = $this->kerjasamaModel->setKeterangan((int) $periode['id'], $periode['kodebpr'], $ket);

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
        if (!$id || !$this->kerjasamaModel->findOwned($id, (int) $periode['id'], $periode['kodebpr'])) {
            return $this->json(404, ['ok' => false, 'message' => 'Data tidak ditemukan.']);
        }

        $wasReset = $this->resetApproval($periode);
        $ok = (bool) $this->kerjasamaModel->delete($id);

        return $ok
            ? $this->json(200, ['ok' => true, 'message' => 'Data dihapus.' . ($wasReset ? ' Status approval direset.' : '')])
            : $this->json(500, ['ok' => false, 'message' => 'Data gagal dihapus.']);
    }

    // approval

    public function approve()
    {
        [$periode, $err] = $this->guardPost(false);
        if ($err) {
            return $err;
        }
        if (!$this->canApprove()) {
            return $this->json(403, ['ok' => false, 'message' => 'Anda tidak memiliki akses untuk approval ini.']);
        }

        $act = (string) $this->request->getPost('act');
        if (!in_array($act, ['approve', 'cancel'], true)) {
            return $this->json(422, ['ok' => false, 'message' => 'Permintaan tidak valid.']);
        }

        $rows = $this->kerjasamaModel->getByPeriode((int) $periode['id'], $periode['kodebpr']);
        if (!$rows) {
            return $this->json(422, ['ok' => false, 'message' => 'Data belum diisi, tidak dapat di-approve.']);
        }
        $approval = M_kerjasamabpr::approvalOf($rows);

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

        $ok = $this->kerjasamaModel->setApproval((int) $periode['id'], $periode['kodebpr'], $act === 'approve', $this->userId);

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
     * Export TXT Form A0506 Kerja Sama BPR dengan Bank atau Lembaga Lain (LTBPRK).
     *   H01|kodejenis|sandibpr|YYYY-12-31|LTBPRK|A0506|0|
     *   D01|05002|reflembaga|nama|sandibank|npwp|jenis|uraian|YYYYMMDD   (per baris)
     *   F01|keterangan                                                  (bila diisi)
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

        $rows = $this->kerjasamaModel->getByPeriode((int) $periode['id'], $periode['kodebpr']);
        $bpr = $this->infobprModel->getBprByKode($periode['kodebpr']) ?? [];
        $sandibpr = $bpr['sandibpr'] ?? '';
        $kodejenis = $bpr['kodejenis'] ?? '';
        $tahun = $periode['tahun'] ?? date('Y');
        $form = M_kerjasamabpr::FORM_CODE;

        $output = "H01|{$kodejenis}|{$sandibpr}|{$tahun}-12-31|LTBPRK|{$form}|0|\r\n";
        foreach ($rows as $r) {
            $output .= implode('|', [
                'D01',
                M_kerjasamabpr::KODE_KOMPONEN,
                $r['reflembaga'],
                self::txtClean($r['namabank']),
                self::txtClean($r['sandibank']),
                self::txtClean($r['npwp']),
                self::txtClean($r['jeniskerjasama']),
                self::txtClean($r['uraiankerjasama']),
                str_replace('-', '', (string) $r['tglkerjasama']),
            ]) . "\r\n";
        }
        $ket = self::txtClean(M_kerjasamabpr::keteranganOf($rows));
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
        $rows = $periode ? $this->kerjasamaModel->getByPeriode((int) $periode['id'], $periode['kodebpr']) : [];
        if (!$rows) {
            return redirect()->to(site_url('kerjasamabpr'))->with('error', 'Data belum diisi, PDF belum tersedia.');
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
            'reflembaga' => M_kerjasamabpr::REFLEMBAGA,
            'keterangan' => M_kerjasamabpr::keteranganOf($rows),
        ]));
        $pdf->setPaper('A4', 'landscape');
        $pdf->render();

        $filename = 'LTBPRK-' . M_kerjasamabpr::FORM_CODE . '-' . preg_replace('/[^A-Za-z0-9]/', '', (string) ($periode['jenispelaporan'] ?? ''))
            . '-A-' . (int) $periode['tahun'] . '1231-' . preg_replace('/[^A-Za-z0-9]/', '', (string) ($bpr['sandibpr'] ?? '')) . '-01.pdf';

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->setHeader('Cache-Control', 'private, max-age=0, must-revalidate')
            ->setBody($pdf->output());
    }
}
