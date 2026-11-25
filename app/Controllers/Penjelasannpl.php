<?php

namespace App\Controllers;

use App\Models\M_infobpr;
use App\Models\M_periodetahunan;
use App\Models\M_penjelasannpl;
use App\Models\M_user;
use CodeIgniter\Controller;
use Myth\Auth\Config\Services as AuthServices;

class Penjelasannpl extends Controller
{
    protected $nplModel;
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
    private const VIEW = 'Laporantahunan/penjelasannpl';
    private const PERIODE_REDIRECT = '/periodetahunan';
    private const TXT_REQUIRE_APPROVAL = true;

    public function __construct()
    {
        $this->nplModel = new M_penjelasannpl();
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

    /** Pengisian hanya PE. */
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
        $row = $periode ? $this->nplModel->getByPeriode((int) $periode['id'], $periode['kodebpr']) : null;

        $data = [
            'judul' => 'Penjelasan NPL',
            'periode' => $periode,
            'row' => $row ?? [],
            'max' => M_penjelasannpl::MAX,
            'hasApproval' => self::isLocked($row),
            'canEdit' => $periode && $this->canEditGroup(),
            'namabpr' => $periode ? ($this->infobprModel->getBprByKode($periode['kodebpr'])['namabpr'] ?? 'Nama BPR') : '',
            'backUrl' => base_url(ltrim(self::PERIODE_REDIRECT, '/')),
            'canApproveDireksi' => (bool) ($row && $this->canApprove()),
            'canExportTxtnpl' => (bool) ($row && (!self::TXT_REQUIRE_APPROVAL || (int) $row['is_approved'] === 1)),
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

        $row = $this->nplModel->getByPeriode((int) $periode['id'], $periode['kodebpr']);

        $data = [];
        $errors = [];
        $post = $this->request->getPost();

        // ---- angka NPL (terima koma atau titik sebagai desimal)
        foreach (M_penjelasannpl::NUMERIC_FIELDS as $field => $label) {
            $raw = str_replace(',', '.', trim((string) ($post[$field] ?? '')));
            if ($raw === '') {
                $errors[$field] = 'Wajib diisi.';
            } elseif (!preg_match('/^\d+(\.\d+)?$/', $raw)) {
                $errors[$field] = 'Harus berupa angka (contoh: 3,25).';
            } elseif ((float) $raw < M_penjelasannpl::NPL_MIN || (float) $raw > M_penjelasannpl::NPL_MAX) {
                $errors[$field] = 'Nilai harus antara ' . M_penjelasannpl::NPL_MIN . ' dan ' . M_penjelasannpl::NPL_MAX . '.';
            }
            $data[$field] = round((float) $raw, 2);
        }

        // ---- teks
        foreach (M_penjelasannpl::MAX as $field => $max) {
            $v = trim((string) ($post[$field] ?? ''));
            if ($v === '') {
                $errors[$field] = 'Wajib diisi.';
            } elseif (mb_strlen($v) > $max) {
                $errors[$field] = "Maksimal {$max} karakter.";
            }
            $data[$field] = $v;
        }

        if ($errors) {
            return $this->json(422, ['ok' => false, 'message' => 'Periksa kembali isian yang ditandai.', 'errors' => $errors]);
        }

        // Mengubah isi me-reset status approval.
        $changed = !$row;
        if ($row) {
            foreach ($data as $k => $val) {
                $old = $row[$k] ?? '';
                $same = isset(M_penjelasannpl::NUMERIC_FIELDS[$k])
                    ? round((float) $old, 2) === round((float) $val, 2)
                    : (string) $old === (string) $val;
                if (!$same) {
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
            $ok = $this->nplModel->saveRow($row ? (int) $row['id'] : null, $data);
        } catch (\Throwable $e) {
            log_message('error', 'Penjelasannpl::save ' . $e->getMessage());
            $ok = false;
        }

        return $ok
            ? $this->json(200, ['ok' => true, 'message' => $wasReset ? 'Data berhasil disimpan. Status approval direset.' : 'Data berhasil disimpan.'])
            : $this->json(500, ['ok' => false, 'message' => 'Data gagal disimpan.']);
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
        $row = $periode ? $this->nplModel->getByPeriode((int) $periode['id'], $periode['kodebpr']) : null;
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

        $ok = $this->nplModel->setApproval((int) $row['id'], 'dirut', $act === 'approve', $this->userId);

        return $ok
            ? $this->json(200, ['ok' => true, 'message' => $act === 'approve' ? 'Berhasil di-approve.' : 'Approval dibatalkan.'])
            : $this->json(500, ['ok' => false, 'message' => 'Gagal memproses approval.']);
    }

    private static function txtClean($s): string
    {
        return trim(preg_replace('/[\r\n\t|]+/', ' ', (string) $s));
    }

    private static function txtNum($n): string
    {
        return number_format((float) $n, 2, '.', '');
    }

    public function exporttxtnpl()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }

        $periode = $this->resolvePeriode();
        $row = $periode ? $this->nplModel->getByPeriode((int) $periode['id'], $periode['kodebpr']) : null;

        if (!$row) {
            return redirect()->to(site_url('penjelasannpl'))
                ->with('error', 'Data belum diisi, tidak dapat diekspor.');
        }
        if (self::TXT_REQUIRE_APPROVAL && (int) $row['is_approved'] !== 1) {
            return redirect()->to(site_url('penjelasannpl'))
                ->with('error', 'Export TXT hanya dapat dilakukan setelah disetujui Direktur Utama.');
        }

        // Format LTBPRK (sama dengan laporan tahunan lainnya):
        //   H01|kodejenis|sandibpr|YYYY-12-31|LTBPRK|A0304|0|
        //   D01|nplgross|nplnetto|uraian
        //   F01|keterangan
        $bpr = $this->infobprModel->getBprByKode($periode['kodebpr']) ?? [];
        $sandibpr = $bpr['sandibpr'] ?? '';
        $kodejenis = $bpr['kodejenis'] ?? '';
        $tahun = $periode['tahun'] ?? date('Y');
        $form = M_penjelasannpl::FORM_CODE;

        $content = "H01|{$kodejenis}|{$sandibpr}|{$tahun}-12-31|LTBPRK|{$form}|0|\r\n";
        $content .= implode('|', [
            'D01',
            self::txtNum($row['nplgross']),
            self::txtNum($row['nplnetto']),
            self::txtClean($row['uraian']),
        ]) . "\r\n";
        $ket = self::txtClean($row['keterangan']);
        if ($ket !== '') {
            $content .= "F01|{$ket}\r\n";
        }

        $filename = "LTBPRK-{$form}-{$periode['jenispelaporan']}-A-{$tahun}1231-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($content);
    }
}