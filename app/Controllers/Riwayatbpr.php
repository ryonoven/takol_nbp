<?php

namespace App\Controllers;

use App\Models\M_infobpr;
use App\Models\M_periodetahunan;
use App\Models\M_riwayatbpr;
use App\Models\M_user;
use CodeIgniter\Controller;
use Myth\Auth\Config\Services as AuthServices;
use DateTime;

class Riwayatbpr extends Controller
{
    protected $riwayatModel;
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
    private const VIEW = 'Laporantahunan/riwayatbpr';
    private const PERIODE_REDIRECT = '/periodetahunan';

    public function __construct()
    {
        $this->riwayatModel = new M_riwayatbpr();
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

    private function canEditGroup(): bool
    {
        return $this->userInGroupPE || $this->userInGroupAdmin;
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

    private static function isLocked(?array $row): bool
    {
        return $row && (int) $row['is_approved'] === 1;
    }

    public function index()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }

        $periode = $this->resolvePeriode();
        $row = $periode ? $this->riwayatModel->getByPeriode((int) $periode['id'], $periode['kodebpr']) : null;

        $data = [
            'judul' => 'Riwayat BPR',
            'periode' => $periode,
            'row' => $row ?? [],
            'max' => M_riwayatbpr::MAX,
            'opini' => M_riwayatbpr::OPINI,
            'hasApproval' => self::isLocked($row),
            'canEdit' => $periode && $this->canEditGroup(),
            'namabpr' => $periode ? ($this->infobprModel->getBprByKode($periode['kodebpr'])['namabpr'] ?? 'Nama BPR') : '',
            'backUrl' => base_url(ltrim(self::PERIODE_REDIRECT, '/')),
            'canApproveDireksi' => (bool) ($row && ($this->userInGroupAdmin || $this->userInGroupDireksi)),
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

        $row = $this->riwayatModel->getByPeriode((int) $periode['id'], $periode['kodebpr']);

        $data = [];
        $errors = [];
        $post = $this->request->getPost();

        foreach (M_riwayatbpr::MAX as $field => $max) {
            $v = trim((string) ($post[$field] ?? ''));
            if ($v === '') {
                $errors[$field] = 'Wajib diisi.';
            } elseif (mb_strlen($v) > $max) {
                $errors[$field] = "Maksimal {$max} karakter.";
            }
            $data[$field] = $v;
        }

        foreach (M_riwayatbpr::DATE_FIELDS as $field) {
            $v = trim((string) ($post[$field] ?? ''));
            $d = DateTime::createFromFormat('!Y-m-d', $v);
            if ($v === '') {
                $errors[$field] = 'Pilih tanggal.';
            } elseif (!$d || $d->format('Y-m-d') !== $v) {
                $errors[$field] = 'Format tanggal tidak valid.';
            }
            $data[$field] = $v;
        }

        $opini = (string) ($post['opinipublik'] ?? '');
        if (!array_key_exists($opini, M_riwayatbpr::OPINI)) {
            $errors['opinipublik'] = 'Pilih opini akuntan publik.';
        }
        $data['opinipublik'] = $opini;

        if ($errors) {
            return $this->json(422, ['ok' => false, 'message' => 'Periksa kembali isian yang ditandai.', 'errors' => $errors]);
        }

        // Pola modul lain: mengubah isi me-reset status approval.
        $changed = !$row;
        if ($row) {
            foreach ($data as $k => $val) {
                if ((string) ($row[$k] ?? '') !== (string) $val) {
                    $changed = true;
                    break;
                }
            }
        }
        $wasReset = $changed && self::isLocked($row);
        if ($changed) {
            $data += [
                'is_approved' => 0,
                'approved_by' => null,
                'approved_at' => null,
            ];
        }

        $data['user_id'] = $this->userId;
        $data['kodebpr'] = $periode['kodebpr'];
        $data['periode_id'] = (int) $periode['id'];

        try {
            $ok = $this->riwayatModel->saveRow($row ? (int) $row['id'] : null, $data);
        } catch (\Throwable $e) {
            log_message('error', 'Riwayatbpr::save ' . $e->getMessage());
            $ok = false;
        }

        return $ok
            ? $this->json(200, ['ok' => true, 'message' => $wasReset ? 'Data berhasil disimpan. Status approval direset.' : 'Data berhasil disimpan.'])
            : $this->json(500, ['ok' => false, 'message' => 'Data gagal disimpan.']);
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

        // approve: hanya direksi. batal: direksi (yang meng-approve) atau admin.
        if ($act === 'approve' && !$this->userInGroupDireksi && !$this->userInGroupAdmin) {
            return $this->json(403, ['ok' => false, 'message' => 'Approval hanya dapat dilakukan oleh direksi.']);
        }
        if ($act === 'cancel' && !$this->userInGroupDireksi && !$this->userInGroupAdmin) {
            return $this->json(403, ['ok' => false, 'message' => 'Anda tidak memiliki akses approval.']);
        }

        $periode = $this->resolvePeriode();
        $row = $periode ? $this->riwayatModel->getByPeriode((int) $periode['id'], $periode['kodebpr']) : null;
        if (!$row) {
            return $this->json(422, ['ok' => false, 'message' => 'Data belum diisi, tidak dapat di-approve.']);
        }

        // Hanya approval Direktur Utama (is_approved / approved_by / approved_at).
        $flag = 'is_approved';
        $by = 'approved_by';

        if ($act === 'approve' && (int) $row[$flag] === 1) {
            return $this->json(409, ['ok' => false, 'message' => 'Sudah di-approve sebelumnya.']);
        }
        if ($act === 'cancel') {
            if ((int) $row[$flag] !== 1) {
                return $this->json(409, ['ok' => false, 'message' => 'Belum di-approve.']);
            }
            if (!$this->userInGroupAdmin && (int) $row[$by] !== $this->userId) {
                return $this->json(403, ['ok' => false, 'message' => 'Hanya yang meng-approve yang dapat membatalkan.']);
            }
        }

        $ok = $this->riwayatModel->setApproval((int) $row['id'], 'dirut', $act === 'approve', $this->userId);

        return $ok
            ? $this->json(200, ['ok' => true, 'message' => $act === 'approve' ? 'Berhasil di-approve.' : 'Approval dibatalkan.'])
            : $this->json(500, ['ok' => false, 'message' => 'Gagal memproses approval.']);
    }

    // ------------------------------------------------------------------ export TXT

    private static function txtClean($text): string
    {
        $text = str_replace(["\r", "\n", "\t", '|'], ' ', (string) $text);
        $text = str_replace(["\xE2\x80\x9C", "\xE2\x80\x9D"], '"', $text);
        $text = str_replace(["\xE2\x80\x98", "\xE2\x80\x99"], "'", $text);
        $text = str_replace('%', ' persen ', $text);
        $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    /** Tanggal -> YYYY-MM-DD; kosong bila tidak valid. */
    private static function txtDate($value): string
    {
        $ts = !empty($value) ? strtotime((string) $value) : false;
        return $ts ? date('Y-m-d', $ts) : '';
    }

    /**
     * Export TXT LTBPRK Riwayat Pendirian BPR.
     *   H01|kodejenis|sandibpr|YYYY-12-31|LTBPRK|<FORM_CODE>|0|
     *   D01|<kolom sesuai M_riwayatbpr::TXT_FIELDS>
     *   F01|keterangan
     * Dipanggil juga langsung oleh Showtahunan::exportAllToZip, karena itu memakai service('response').
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

        $row = $this->riwayatModel->getByPeriode((int) $periode['id'], $periode['kodebpr']);
        if (!$row) {
            return redirect()->to(site_url('riwayatbpr'))->with('error', 'Data belum diisi, tidak dapat diekspor.');
        }

        $bpr = $this->infobprModel->getBprByKode($periode['kodebpr']) ?? [];
        $sandibpr = $bpr['sandibpr'] ?? '';
        $kodejenis = $bpr['kodejenis'] ?? '';
        $tahun = $periode['tahun'] ?? date('Y');
        $form = M_riwayatbpr::FORM_CODE;

        $cols = ['D01'];
        foreach (M_riwayatbpr::TXT_FIELDS as $f) {
            $cols[] = in_array($f, M_riwayatbpr::DATE_FIELDS, true) ? self::txtDate($row[$f] ?? null) : self::txtClean($row[$f] ?? '');
        }

        $output = "H01|{$kodejenis}|{$sandibpr}|{$tahun}-12-31|LTBPRK|{$form}|0|\r\n";
        $output .= implode('|', $cols) . "\r\n";
        $ket = self::txtClean($row['keterangan'] ?? '');
        if ($ket !== '') {
            $output .= "F01|{$ket}\r\n";
        }

        $filename = "LTBPRK-{$form}-{$periode['jenispelaporan']}-A-{$tahun}1231-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }
}