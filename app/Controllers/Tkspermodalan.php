<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_periodeprofilresiko;
use App\Models\M_periodesemesteran;
use App\Models\M_showprofilresiko;
use App\Models\M_showtks;
use App\Models\M_infobpr;
use App\Models\M_user;
use App\Models\M_tkspermodalan;
use App\Models\M_tkscomments;
use App\Models\M_tkscommentsread;
use Myth\Auth\Config\Services as AuthServices;

class Tkspermodalan extends Controller
{
    protected $auth;
    protected $session;
    protected $userKodebpr;
    protected $userId;
    protected $periodeId;

    protected $periodeModel;
    protected $periodetksModel;
    protected $showprofilresikoModel;
    protected $showtksModel;
    protected $userModel;
    protected $infobprModel;
    protected $tkspermodalanModel;
    protected $tksCommentsModel;
    protected $tksCommentsReadModel;
    protected $userGroups = [];

    private const NUMERIC_FIELDS = [
        'modalinti',
        'modalpelengkap',
        'atmr',
        'modalintiapb',
        'totalasetbermasalah',
        'modalintibefore',
        'modalpelengkapbefore',
        'atmrbefore',
        'modalintiapbbefore',
        'totalasetbermasalahbefore',
    ];

    public function __construct()
    {
        $this->infobprModel = new M_infobpr();
        $this->periodeModel = new M_periodeprofilresiko();
        $this->periodetksModel = new M_periodesemesteran();
        $this->showprofilresikoModel = new M_showprofilresiko();
        $this->showtksModel = new M_showtks();
        $this->userModel = new M_user();
        $this->tkspermodalanModel = new M_tkspermodalan();
        $this->tksCommentsModel = new M_tkscomments();
        $this->tksCommentsReadModel = new M_tkscommentsread();

        $this->session = service('session');
        $this->auth = service('authentication');

        helper('text');
        $this->initializeUser();
    }

    private function initializeUser(): void
    {
        if ($this->auth->isLoggedIn()) {
            $this->userId = $this->auth->id();
            $user = $this->userModel->find($this->userId);
            $this->userKodebpr = $user['kodebpr'] ?? null;

            $authorize = AuthServices::authorization();
            foreach (['pe', 'admin', 'dekom', 'direksi'] as $group) {
                $this->userGroups[$group] = $authorize->inGroup($group, $this->userId);
            }
        } else {
            $this->userKodebpr = null;
            $this->userGroups = array_fill_keys(['pe', 'admin', 'dekom', 'direksi'], false);
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

    private function getExistingRecord(): ?array
    {
        return $this->tkspermodalanModel->getByKodebprAndPeriode($this->userKodebpr, $this->periodeId);
    }

    private function jsonError(string $msg): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->response->setJSON(['success' => false, 'message' => $msg]);
    }

    private function getPeringkatKPMM(float $rasio): int
    {
        if ($rasio >= 15)
            return 1;
        if ($rasio >= 13)
            return 2;
        if ($rasio >= 8)
            return 3;
        if ($rasio >= 6)
            return 4;
        return 5;
    }

    private function getPenilaianKPMM(int $peringkat): string
    {
        return ['Sangat Rendah', 'Rendah', 'Sedang', 'Tinggi', 'Sangat Tinggi'][$peringkat - 1] ?? 'Belum dinilai';
    }

    private function getPeringkatMIAPB(float $rasio): int
    {
        if ($rasio >= 200)
            return 1;
        if ($rasio >= 180)
            return 2;
        if ($rasio >= 150)
            return 3;
        if ($rasio >= 120)
            return 4;
        return 5;
    }

    private function getPenilaianMIAPB(int $peringkat): string
    {
        return $this->getPenilaianKPMM($peringkat);
    }

    private function getPreviousPeriode(array $periodeDetail): ?array
    {
        $semester = (int) ($periodeDetail['semester'] ?? 0);
        $tahun = (int) ($periodeDetail['tahun'] ?? 0);

        if ($semester === 1) {
            $prevSemester = 2;
            $prevTahun = $tahun - 1;
        } else {
            $prevSemester = 1;
            $prevTahun = $tahun;
        }

        return $this->periodetksModel
            ->where(['semester' => $prevSemester, 'tahun' => $prevTahun])
            ->first();
    }

    public function index()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;
        if ($redirect = $this->checkPeriode())
            return $redirect;

        if (!$this->userKodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid.');
        }

        $periodeDetail = $this->periodetksModel->find($this->periodeId);
        $bprData = $this->infobprModel->getBprByKode($this->userKodebpr);
        $showtks = $this->showtksModel->getByKodebprAndPeriode($this->userKodebpr, $this->periodeId);
        $tkspermodalan = $this->getExistingRecord() ?? [];

        $isApproved = !empty($tkspermodalan['is_approved']);
        $approvedAt = $tkspermodalan['approved_at'] ?? null;
        $canApprove = !empty($tkspermodalan['peringkatpermodalan']);

        $unreadCount = $this->tksCommentsReadModel->countUnreadComments(
            $this->userKodebpr,
            $this->userId,
            $this->periodeId,
            'PERMODALAN'
        );

        $data = [
            'judul' => 'Penilaian Faktor Permodalan',
            'bprData' => $bprData,
            'periodeDetail' => $periodeDetail,
            'showtks' => $showtks,
            'tkspermodalan' => $tkspermodalan,
            'userInGroupAdmin' => $this->userGroups['admin'],
            'userInGroupPE' => $this->userGroups['pe'],
            'userInGroupDekom' => $this->userGroups['dekom'],
            'userInGroupDireksi' => $this->userGroups['direksi'],
            'isApproved' => $isApproved,
            'approvedAt' => $approvedAt,
            'canApprove' => $canApprove,
            'unreadCount' => $unreadCount,
            'fullname' => $this->userModel->find($this->userId)['fullname'] ?? '',
        ];

        return view('templates/v_header', $data)
            . view('templates/v_sidebar')
            . view('templates/v_topbar')
            . view('Laporansemester/Tks/tkspermodalan', $data)
            . view('templates/v_footer');
    }

    public function autoSave()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;

        try {
            $field = $this->request->getPost('field');
            $value = $this->request->getPost('value');

            if (empty($field)) {
                return $this->jsonError('Field tidak boleh kosong');
            }

            if (!$this->periodeId || !$this->userKodebpr) {
                return $this->jsonError('Periode atau Kodebpr tidak valid');
            }

            if (in_array($field, self::NUMERIC_FIELDS)) {
                $value = (float) str_replace(',', '', (string) $value);
            }

            if (in_array($field, ['deskripsipermodalan', 'analisispermodalan']) && mb_strlen((string)$value) > 2000) {
                $value = mb_substr((string)$value, 0, 2000);
            }

            $existing = $this->getExistingRecord();
            $showtks = $this->showtksModel->getByKodebprAndPeriode($this->userKodebpr, $this->periodeId);

            $data = [$field => $value];

            // KPMM (current)
            if (in_array($field, ['modalinti', 'modalpelengkap', 'atmr'])) {
                $mi = (float) (($field === 'modalinti') ? $value : ($existing['modalinti'] ?? $showtks['modal_inti'] ?? 0));
                $mp = (float) (($field === 'modalpelengkap') ? $value : ($existing['modalpelengkap'] ?? 0));
                $atmr = (float) (($field === 'atmr') ? $value : ($existing['atmr'] ?? 0));

                if ($atmr > 0) {
                    $rasio = (($mi + $mp) / $atmr) * 100;
                    $pr = $this->getPeringkatKPMM($rasio);
                    $data['rasiokpmm'] = $rasio;
                    $data['peringkatkpmm'] = $pr;
                    $data['penilaiankpmm'] = $this->getPenilaianKPMM($pr);
                }

                if ($field === 'modalinti' && empty($existing['modalintiapb'])) {
                    $data['modalintiapb'] = $value;
                }
            }

            // MIAPB (current)
            if (in_array($field, ['modalintiapb', 'totalasetbermasalah'])) {
                $mia = (float) (($field === 'modalintiapb') ? $value : ($existing['modalintiapb'] ?? $existing['modalinti'] ?? $showtks['modal_inti'] ?? 0));
                $tab = (float) (($field === 'totalasetbermasalah') ? $value : ($existing['totalasetbermasalah'] ?? 0));

                if ($tab > 0) {
                    $rasio = ($mia / $tab) * 100;
                    $pr = $this->getPeringkatMIAPB($rasio);
                    $data['rasiomiapb'] = $rasio;
                    $data['peringkatmiapb'] = $pr;
                    $data['penilaianmiapb'] = $this->getPenilaianMIAPB($pr);
                }
            }

            // KPMM (before)
            if (in_array($field, ['modalintibefore', 'modalpelengkapbefore', 'atmrbefore'])) {
                $mi = (float) (($field === 'modalintibefore') ? $value : ($existing['modalintibefore'] ?? 0));
                $mp = (float) (($field === 'modalpelengkapbefore') ? $value : ($existing['modalpelengkapbefore'] ?? 0));
                $atmr = (float) (($field === 'atmrbefore') ? $value : ($existing['atmrbefore'] ?? 0));

                if ($atmr > 0) {
                    $rasio = (($mi + $mp) / $atmr) * 100;
                    $pr = $this->getPeringkatKPMM($rasio);
                    $data['rasiokpmmbefore'] = $rasio;
                    $data['peringkatkpmmbefore'] = $pr;
                    $data['penilaiankpmmbefore'] = $this->getPenilaianKPMM($pr);
                }

                if ($field === 'modalintibefore' && empty($existing['modalintiapbbefore'])) {
                    $data['modalintiapbbefore'] = $value;
                }
            }

            // MIAPB (before)
            if (in_array($field, ['modalintiapbbefore', 'totalasetbermasalahbefore'])) {
                $mia = (float) (($field === 'modalintiapbbefore') ? $value : ($existing['modalintiapbbefore'] ?? $existing['modalintibefore'] ?? 0));
                $tab = (float) (($field === 'totalasetbermasalahbefore') ? $value : ($existing['totalasetbermasalahbefore'] ?? 0));

                if ($tab > 0) {
                    $rasio = ($mia / $tab) * 100;
                    $pr = $this->getPeringkatMIAPB($rasio);
                    $data['rasiomiapbbefore'] = $rasio;
                    $data['peringkatmiapbbefore'] = $pr;
                    $data['penilaianmiapbbefore'] = $this->getPenilaianMIAPB($pr);
                }
            }

            // Persist
            $now = date('Y-m-d H:i:s');

            if ($existing) {
                $data['updated_at'] = $now;
                $this->tkspermodalanModel->update($existing['id'], $data);
                $recordId = $existing['id'];
            } else {
                if (!isset($data['modalinti']) && $showtks) {
                    $data['modalinti'] = (float) ($showtks['modal_inti'] ?? 0);
                }
                if (!isset($data['modalintiapb']) && isset($data['modalinti'])) {
                    $data['modalintiapb'] = $data['modalinti'];
                }

                $data = array_merge($data, [
                    'user_id' => $this->userId,
                    'kodebpr' => $this->userKodebpr,
                    'periode_id' => $this->periodeId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $recordId = $this->tkspermodalanModel->insert($data);
            }

            // Recalculate aggregates
            $this->tkspermodalanModel->hitungRataRataPermodalan($this->userKodebpr, $this->periodeId);
            $this->tkspermodalanModel->hitungRataRataPermodalanBefore($this->userKodebpr, $this->periodeId);

            $updated = $this->tkspermodalanModel->find($recordId);

            if (!$updated) {
                return $this->jsonError('Data tidak ditemukan setelah disimpan');
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Data berhasil disimpan',
                'data' => $updated,
                'is_approved' => 0,
                'csrf_hash' => csrf_hash(),
            ]);

        } catch (\Throwable $e) {
            log_message('error', 'AutoSave Permodalan: ' . $e->getMessage());
            return $this->jsonError('Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // Approval

    public function approve()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;

        $existing = $this->getExistingRecord();
        if (!$existing)
            return redirect()->back()->with('error', 'Data tidak ditemukan.');
        if (empty($existing['peringkatpermodalan'])) {
            return redirect()->back()->with('error', 'Data belum lengkap. Lengkapi data terlebih dahulu.');
        }

        $this->tkspermodalanModel->update($existing['id'], [
            'is_approved' => 1,
            'approved_by' => $this->userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->back()->with('message', 'Data berhasil disetujui oleh Direktur Utama.');
    }

    public function unapprove()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;

        $existing = $this->getExistingRecord();
        if (!$existing)
            return redirect()->back()->with('error', 'Data tidak ditemukan.');

        $this->tkspermodalanModel->update($existing['id'], [
            'is_approved' => 0,
            'approved_by' => $this->userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->back()->with('message', 'Persetujuan berhasil dibatalkan.');
    }

    public function checkSyncStatus()
    {
        if ($this->request->getMethod() !== 'get') {
            return $this->response->setStatusCode(404)->setBody('Not Found');
        }
        if (!$this->auth->check() || !$this->periodeId || !$this->userKodebpr) {
            return $this->response->setJSON(['available' => false]);
        }

        $periodeDetail = $this->periodetksModel->find($this->periodeId);
        if (!$periodeDetail) {
            return $this->response->setJSON(['available' => false]);
        }

        $prevPeriode = $this->getPreviousPeriode($periodeDetail);
        if (!$prevPeriode) {
            return $this->response->setJSON(['available' => false]);
        }

        $exists = $this->tkspermodalanModel
            ->select('id')
            ->where(['kodebpr' => $this->userKodebpr, 'periode_id' => $prevPeriode['id']])
            ->first();

        return $this->response->setJSON([
            'available' => (bool) $exists,
            'periode_label' => 'Semester ' . $prevPeriode['semester'] . ' Tahun ' . $prevPeriode['tahun'],
        ]);
    }

    public function syncFromPrevious()
    {
        if ($this->request->getMethod() !== 'post') {
            return $this->jsonError('Invalid request method');
        }
        if (!$this->auth->check() || !$this->periodeId || !$this->userKodebpr) {
            return $this->jsonError('Periode atau Kodebpr tidak valid');
        }

        try {
            $periodeDetail = $this->periodetksModel->find($this->periodeId);
            if (!$periodeDetail) {
                return $this->jsonError('Periode tidak ditemukan');
            }

            $prevPeriode = $this->getPreviousPeriode($periodeDetail);
            if (!$prevPeriode) {
                return $this->jsonError('Tidak ada data pada periode sebelumnya');
            }

            $prevData = $this->tkspermodalanModel->getByKodebprAndPeriode($this->userKodebpr, $prevPeriode['id']);
            if (!$prevData) {
                return $this->jsonError('Tidak ada data pada periode sebelumnya');
            }

            $map = [
                'modalinti' => 'modalintibefore',
                'modalpelengkap' => 'modalpelengkapbefore',
                'atmr' => 'atmrbefore',
                'rasiokpmm' => 'rasiokpmmbefore',
                'peringkatkpmm' => 'peringkatkpmmbefore',
                'penilaiankpmm' => 'penilaiankpmmbefore',
                'modalintiapb' => 'modalintiapbbefore',
                'totalasetbermasalah' => 'totalasetbermasalahbefore',
                'rasiomiapb' => 'rasiomiapbbefore',
                'peringkatmiapb' => 'peringkatmiapbbefore',
                'penilaianmiapb' => 'penilaianmiapbbefore',
                'rataratapermodalan' => 'rataratapermodalanbefore',
                'penilaianpermodalan' => 'penilaianpermodalanbefore',
                'peringkatpermodalan' => 'peringkatpermodalanbefore',
                'nilaifaktor' => 'nilaifaktorbefore',
            ];

            $syncData = [];
            foreach ($map as $src => $dest) {
                if (isset($prevData[$src])) {
                    $syncData[$dest] = $prevData[$src];
                }
            }

            if (empty($syncData)) {
                return $this->jsonError('Tidak ada data pada periode sebelumnya');
            }

            $existing = $this->getExistingRecord();
            $now = date('Y-m-d H:i:s');

            if ($existing) {
                $syncData['updated_at'] = $now;
                $this->tkspermodalanModel->update($existing['id'], $syncData);
                $recordId = $existing['id'];
            } else {
                $syncData = array_merge($syncData, [
                    'user_id' => $this->userId,
                    'kodebpr' => $this->userKodebpr,
                    'periode_id' => $this->periodeId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $recordId = $this->tkspermodalanModel->insert($syncData);
            }

            $updated = $this->tkspermodalanModel->find($recordId);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Data berhasil disinkronkan dari periode sebelumnya',
                'data' => $updated,
                'csrf_hash' => csrf_hash(),
            ]);

        } catch (\Throwable $e) {
            log_message('error', 'Sync Permodalan: ' . $e->getMessage());
            return $this->jsonError('Terjadi kesalahan saat sinkronisasi');
        }
    }

    public function getKomentar()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(404)->setBody('Not Found');
        }

        return $this->response->setJSON(
            $this->tksCommentsModel->getKomentarByPeriode($this->userKodebpr, $this->periodeId, 'PERMODALAN')
        );
    }

    public function tambahKomentar()
    {
        $isAjax = $this->request->isAJAX();

        if (!$this->validate(['komentar' => 'required'])) {
            return $isAjax
                ? $this->response->setJSON(['status' => 'error', 'message' => 'Komentar tidak boleh kosong'])
                : redirect()->back()->with('error', 'Komentar tidak boleh kosong');
        }

        try {
            $result = $this->tksCommentsModel->insert([
                'komentar' => $this->request->getPost('komentar'),
                'fullname' => $this->request->getPost('fullname'),
                'kategori' => 'PERMODALAN',
                'user_id' => $this->userId,
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            if (!$result)
                throw new \RuntimeException('Gagal menyimpan komentar');

            if ($isAjax) {
                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Komentar berhasil ditambahkan',
                    'comments' => $this->tksCommentsModel->getKomentarByPeriode($this->userKodebpr, $this->periodeId, 'PERMODALAN'),
                ]);
            }

            return redirect()->back()->with('message', 'Komentar berhasil ditambahkan');

        } catch (\Throwable $e) {
            log_message('error', 'tambahKomentar Permodalan: ' . $e->getMessage());
            return $isAjax
                ? $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()])
                : redirect()->back()->with('error', 'Gagal menambahkan komentar');
        }
    }

    public function markCommentsAsRead()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $marked = $this->tksCommentsReadModel->markAllAsReadForPeriode(
            $this->userKodebpr,
            $this->userId,
            $this->periodeId,
            'PERMODALAN'
        );

        return $this->response->setJSON([
            'status' => 'success',
            'marked_count' => $marked,
            'csrf_hash' => csrf_hash(),
        ]);
    }

    public function getUnreadCount()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'count' => $this->tksCommentsReadModel->countUnreadComments(
                $this->userKodebpr,
                $this->userId,
                $this->periodeId,
                'PERMODALAN'
            ),
        ]);
    }

    private function semesterToBulanMisTks($semester): string
    {
        $semNum = ((string) $semester === '1') ? '1' : '2';
        return $semNum === '1' ? '06' : '12';
    }

    private function buildPeriodeMisTks(int $tahun, $semester): string
    {
        return $tahun . $this->semesterToBulanMisTks($semester);
    }

    private function getTahunSemesterSebelumnya(int $tahun, $semester): array
    {
        $semNum = ((string) $semester === '1') ? 1 : 2;

        return $semNum === 1
            ? [$tahun - 1, '2']
            : [$tahun, '1'];
    }

    private function resolvePeriodeMisTks(): array
    {
        $current = $this->periodetksModel->find($this->periodeId);
        if (!$current)
            return [null, null];

        $tahun = (int) $current['tahun'];
        $semester = $current['semester'];

        $periodeCurrent = $this->buildPeriodeMisTks($tahun, $semester);
        [$prevTahun, $prevSemester] = $this->getTahunSemesterSebelumnya($tahun, $semester);
        $periodePrev = $this->buildPeriodeMisTks($prevTahun, $prevSemester);

        return [$periodeCurrent, $periodePrev];
    }

    public function checkMisTksData()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;

        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        try {
            if (!$this->userKodebpr || !$this->periodeId) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak valid']);
            }

            [$periodeCurrent, $periodePrev] = $this->resolvePeriodeMisTks();
            if (!$periodeCurrent) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Periode aktif tidak ditemukan']);
            }

            $misTksModel = new \App\Models\M_mistks();
            $data = $misTksModel->getPermodalanDataCached($this->userKodebpr, $periodeCurrent, $periodePrev);

            if (array_filter($data, fn($v) => $v !== null) === []) {
                return $this->response->setJSON([
                    'status' => 'not_found',
                    'message' => "Tidak ditemukan data di mis_tks untuk BPR {$this->userKodebpr} ({$periodeCurrent}/{$periodePrev})",
                ]);
            }

            return $this->response->setJSON([
                'status' => 'found',
                'periode_current' => $periodeCurrent,
                'periode_prev' => $periodePrev,
                'data' => $data,
            ]);

        } catch (\Throwable $e) {
            log_message('error', '[Tkspermodalan::checkMisTksData] ' . $e->getMessage());
            return $this->response->setJSON(['status' => 'error', 'message' => 'Terjadi kesalahan sistem']);
        }
    }

    public function tarikDataMisTks()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;

        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        if (!($this->userGroups['pe'] || $this->userGroups['admin'])) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Tidak memiliki akses untuk menarik data']);
        }

        try {
            if (!$this->userKodebpr || !$this->periodeId) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak valid']);
            }

            [$periodeCurrent, $periodePrev] = $this->resolvePeriodeMisTks();
            if (!$periodeCurrent) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Periode aktif tidak ditemukan']);
            }

            $misTksModel = new \App\Models\M_mistks();
            $misTksData = $misTksModel->getPermodalanDataCached($this->userKodebpr, $periodeCurrent, $periodePrev);

            if (array_filter($misTksData, fn($v) => $v !== null) === []) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Tidak ada data pada mis_tks untuk ditarik']);
            }

            $data = array_filter($misTksData, fn($v) => $v !== null);

            // Hitung ulang KPMM 
            if (isset($data['modalinti'], $data['modalpelengkap'], $data['atmr']) && (float) $data['atmr'] > 0) {
                $rasio = (((float) $data['modalinti'] + (float) $data['modalpelengkap']) / (float) $data['atmr']) * 100;
                $pr = $this->getPeringkatKPMM($rasio);
                $data['rasiokpmm'] = $rasio;
                $data['peringkatkpmm'] = $pr;
                $data['penilaiankpmm'] = $this->getPenilaianKPMM($pr);
            }

            // Hitung ulang KPMM
            if (isset($data['modalintibefore'], $data['modalpelengkapbefore'], $data['atmrbefore']) && (float) $data['atmrbefore'] > 0) {
                $rasioB = (((float) $data['modalintibefore'] + (float) $data['modalpelengkapbefore']) / (float) $data['atmrbefore']) * 100;
                $prB = $this->getPeringkatKPMM($rasioB);
                $data['rasiokpmmbefore'] = $rasioB;
                $data['peringkatkpmmbefore'] = $prB;
                $data['penilaiankpmmbefore'] = $this->getPenilaianKPMM($prB);
            }

            // Hitung ulang MIAPB
            if (isset($data['modalintiapb'], $data['totalasetbermasalah']) && (float) $data['totalasetbermasalah'] > 0) {
                $rasio = ((float) $data['modalintiapb'] / (float) $data['totalasetbermasalah']) * 100;
                $pr = $this->getPeringkatMIAPB($rasio);
                $data['rasiomiapb'] = $rasio;
                $data['peringkatmiapb'] = $pr;
                $data['penilaianmiapb'] = $this->getPenilaianMIAPB($pr);
            }

            // Hitung ulang MIAPB
            if (isset($data['modalintiapbbefore'], $data['totalasetbermasalahbefore']) && (float) $data['totalasetbermasalahbefore'] > 0) {
                $rasioB = ((float) $data['modalintiapbbefore'] / (float) $data['totalasetbermasalahbefore']) * 100;
                $prB = $this->getPeringkatMIAPB($rasioB);
                $data['rasiomiapbbefore'] = $rasioB;
                $data['peringkatmiapbbefore'] = $prB;
                $data['penilaianmiapbbefore'] = $this->getPenilaianMIAPB($prB);
            }

            $now = date('Y-m-d H:i:s');
            $existing = $this->getExistingRecord();

            if ($existing) {
                $data['updated_at'] = $now;
                $this->tkspermodalanModel->update($existing['id'], $data);
                $recordId = $existing['id'];
            } else {
                $data = array_merge($data, [
                    'user_id' => $this->userId,
                    'kodebpr' => $this->userKodebpr,
                    'periode_id' => $this->periodeId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $recordId = $this->tkspermodalanModel->insert($data);
            }

            if (!$recordId) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menyimpan data hasil tarik']);
            }

            $this->tkspermodalanModel->hitungRataRataPermodalan($this->userKodebpr, $this->periodeId);
            $this->tkspermodalanModel->hitungRataRataPermodalanBefore($this->userKodebpr, $this->periodeId);

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Data berhasil ditarik dari mis_tks',
                'data' => $this->tkspermodalanModel->find($recordId),
                'csrf_hash' => csrf_hash(),
            ]);

        } catch (\Throwable $e) {
            log_message('error', '[Tkspermodalan::tarikDataMisTks] ' . $e->getMessage());
            return $this->response->setJSON(['status' => 'error', 'message' => 'Terjadi kesalahan sistem']);
        }
    }
}