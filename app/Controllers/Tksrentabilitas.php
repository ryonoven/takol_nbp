<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_periodeprofilresiko;
use App\Models\M_periodesemesteran;
use App\Models\M_showprofilresiko;
use App\Models\M_showtks;
use App\Models\M_infobpr;
use App\Models\M_user;
use App\Models\M_tksrentabilitas;
use App\Models\M_tkscomments;
use App\Models\M_tkscommentsread;
use Myth\Auth\Config\Services as AuthServices;

class Tksrentabilitas extends Controller
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
    protected $tksrentabilitasModel;
    protected $tksCommentsModel;
    protected $tksCommentsReadModel;
    protected $userGroups = [];

    public function __construct()
    {
        $this->infobprModel = new M_infobpr();
        $this->periodeModel = new M_periodeprofilresiko();
        $this->periodetksModel = new M_periodesemesteran();
        $this->showprofilresikoModel = new M_showprofilresiko();
        $this->showtksModel = new M_showtks();
        $this->userModel = new M_user();
        $this->tksrentabilitasModel = new M_tksrentabilitas();
        $this->tksCommentsModel = new M_tkscomments();
        $this->tksCommentsReadModel = new M_tkscommentsread();

        $this->session = service('session');
        $this->auth = service('authentication');

        helper('text');

        $this->initializeUser();
    }

    // Private helpers
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
        if (!$this->userKodebpr || !$this->periodeId) {
            return null;
        }

        return $this->tksrentabilitasModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', (int) $this->periodeId)
            ->first();
    }

    private function checkAccess(bool ...$groups): bool
    {
        foreach ($groups as $g) {
            if ($g)
                return true;
        }
        return false;
    }

    private function getPeringkatROA(float $r): int
    {
        if ($r >= 2)
            return 1;
        if ($r >= 1.5)
            return 2;
        if ($r >= 1)
            return 3;
        if ($r >= 0.5)
            return 4;
        return 5;
    }

    private function getPenilaianROA(float $r): string
    {
        if ($r >= 2)
            return 'Sangat Baik';
        if ($r >= 1.5)
            return 'Baik';
        if ($r >= 1)
            return 'Cukup';
        if ($r >= 0.5)
            return 'Kurang Baik';
        return 'Buruk';
    }

    private function getPeringkatBOPO(float $r): int
    {
        if ($r <= 85)
            return 1;
        if ($r <= 90)
            return 2;
        if ($r <= 95)
            return 3;
        if ($r <= 100)
            return 4;
        return 5;
    }

    private function getPenilaianBOPO(float $r): string
    {
        if ($r <= 85)
            return 'Sangat Baik';
        if ($r <= 90)
            return 'Baik';
        if ($r <= 95)
            return 'Cukup';
        if ($r <= 100)
            return 'Kurang Baik';
        return 'Buruk';
    }

    private function getPeringkatNIM(float $r): int
    {
        if ($r >= 10)
            return 1;
        if ($r >= 8)
            return 2;
        if ($r >= 6)
            return 3;
        if ($r >= 4)
            return 4;
        return 5;
    }

    private function getPenilaianNIM(float $r): string
    {
        if ($r >= 10)
            return 'Sangat Baik';
        if ($r >= 8)
            return 'Baik';
        if ($r >= 6)
            return 'Cukup';
        if ($r >= 4)
            return 'Kurang Baik';
        return 'Buruk';
    }

    // Recalculate aggregate fields
    private function hitungRataRataRentabilitas(int $id): void
    {
        $data = $this->tksrentabilitasModel->find($id);
        if (!$data)
            return;

        $p1 = (int) ($data['peringkatroa'] ?? 0);
        $p2 = (int) ($data['peringkatbopo'] ?? 0);
        $p3 = (int) ($data['peringkatnim'] ?? 0);

        if ($p1 > 0 && $p2 > 0 && $p3 > 0) {
            $rr = ($p1 + $p2 + $p3) / 3;
            $pr = (int) round($rr);
            $this->tksrentabilitasModel->update($id, [
                'rataratarentabilitas' => $rr,
                'penilaianrentabilitas' => $pr,
                'peringkatrentabilitas' => $pr,
                'nilaifaktor' => $pr * 0.15,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function hitungRataRataRentabilitasBefore(int $id): void
    {
        $data = $this->tksrentabilitasModel->find($id);
        if (!$data)
            return;

        $p1 = (int) ($data['peringkatroabefore'] ?? 0);
        $p2 = (int) ($data['peringkatbopobefore'] ?? 0);
        $p3 = (int) ($data['peringkatnimbefore'] ?? 0);

        if ($p1 > 0 && $p2 > 0 && $p3 > 0) {
            $rr = ($p1 + $p2 + $p3) / 3;
            $pr = (int) round($rr);
            $this->tksrentabilitasModel->update($id, [
                'rataratarentabilitasbefore' => $rr,
                'penilaianrentabilitasbefore' => $pr,
                'peringkatrentabilitasbefore' => $pr,
                'nilaifaktorbefore' => $pr * 0.15,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function parseNumeric($value): string
    {
        if ($value === null || $value === '')
            return '';
        $clean = str_replace('.', '', (string) $value);
        $clean = str_replace(',', '.', $clean);
        return is_numeric($clean) ? $clean : (string) $value;
    }

    // Public actions
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
        $showprofilresiko = $this->showprofilresikoModel->getByKodebprAndPeriode($this->userKodebpr, $this->periodeId);
        $showtks = $this->showtksModel->getByKodebprAndPeriode($this->userKodebpr, $this->periodeId);
        $tksrentabilitas = $this->tksrentabilitasModel->getByKodebprAndPeriode($this->userKodebpr, $this->periodeId) ?? [];

        $isApproved = !empty($tksrentabilitas['is_approved']);
        $approvedAt = $tksrentabilitas['approved_at'] ?? null;
        $canApprove = !empty($tksrentabilitas['peringkatrentabilitas']);
        $syncStatus = $this->calculateSyncStatus($showprofilresiko, $showtks);

        $unreadCount = $this->tksCommentsReadModel->countUnreadComments(
            $this->userKodebpr,
            $this->userId,
            $this->periodeId,
            'RENTABILITAS'
        );

        $data = [
            'judul' => 'Penilaian Faktor Rentabilitas',
            'bprData' => $bprData,
            'periodeDetail' => $periodeDetail,
            'showprofilresiko' => $showprofilresiko,
            'showtks' => $showtks,
            'tksrentabilitas' => $tksrentabilitas,
            'syncStatus' => $syncStatus,
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
            . view('Laporansemester/Tks/tksrentabilitas', $data)
            . view('templates/v_footer');
    }

    // autoSave
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

            if (in_array($field, ['deskripsirentabilitas', 'analisarentabilitas']) && mb_strlen((string) $value) > 2000) {
                $value = mb_substr((string) $value, 0, 2000);
            }

            $numeric = $this->parseNumeric($value);

            $existing = $this->tksrentabilitasModel
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', (int) $this->periodeId)
                ->first();

            $data = [$field => $value];

            // ROA (current)
            if (in_array($field, ['labarugibelumpajak', 'ratarataaset'])) {
                $laba = $field === 'labarugibelumpajak'
                    ? $numeric
                    : $this->parseNumeric($existing['labarugibelumpajak'] ?? 0);
                $aset = $field === 'ratarataaset'
                    ? $numeric
                    : $this->parseNumeric($existing['ratarataaset'] ?? 0);
                if ($laba !== '' && $aset !== '' && (float) $aset != 0) {
                    $roa = ((float) $laba / (float) $aset) * 100;
                    $data['rasioroa'] = $roa;
                    $data['peringkatroa'] = $this->getPeringkatROA($roa);
                    $data['penilaianroa'] = $this->getPenilaianROA($roa);
                }
            }

            // BOPO (current)
            if (in_array($field, ['bebanoperasional', 'pendapatanoperasional'])) {
                $beban = $field === 'bebanoperasional'
                    ? $numeric
                    : $this->parseNumeric($existing['bebanoperasional'] ?? 0);
                $pendapatan = $field === 'pendapatanoperasional'
                    ? $numeric
                    : $this->parseNumeric($existing['pendapatanoperasional'] ?? 0);
                if ($beban !== '' && $pendapatan !== '' && (float) $pendapatan != 0) {
                    $bopo = ((float) $beban / (float) $pendapatan) * 100;
                    $data['rasiobopo'] = $bopo;
                    $data['peringkatbopo'] = $this->getPeringkatBOPO($bopo);
                    $data['penilaianbopo'] = $this->getPenilaianBOPO($bopo);
                }
            }

            // NIM (current)
            if (in_array($field, ['pendapatanbunga', 'bebanbunga', 'ratarataasetproduktif'])) {
                $pb = $field === 'pendapatanbunga'
                    ? $numeric
                    : $this->parseNumeric($existing['pendapatanbunga'] ?? 0);
                $bb = $field === 'bebanbunga'
                    ? $numeric
                    : $this->parseNumeric($existing['bebanbunga'] ?? 0);
                $ap = $field === 'ratarataasetproduktif'
                    ? $numeric
                    : $this->parseNumeric($existing['ratarataasetproduktif'] ?? 0);

                if ($pb !== '' && $bb !== '') {
                    $bersih = (float) $pb - (float) $bb;
                    $data['pendapatanbungabersih'] = $bersih;
                    if ($ap !== '' && (float) $ap != 0) {
                        $nim = ($bersih / (float) $ap) * 100;
                        $data['rasionim'] = $nim;
                        $data['peringkatnim'] = $this->getPeringkatNIM($nim);
                        $data['penilaiannim'] = $this->getPenilaianNIM($nim);
                    }
                }
            }

            // ROA (before)
            if (in_array($field, ['lababelumpajakbefore', 'ratarataasetbefore'])) {
                $laba = $field === 'lababelumpajakbefore'
                    ? $numeric
                    : $this->parseNumeric($existing['lababelumpajakbefore'] ?? 0);
                $aset = $field === 'ratarataasetbefore'
                    ? $numeric
                    : $this->parseNumeric($existing['ratarataasetbefore'] ?? 0);
                if ($laba !== '' && $aset !== '' && (float) $aset != 0) {
                    $roa = ((float) $laba / (float) $aset) * 100;
                    $data['rasioroabefore'] = $roa;
                    $data['peringkatroabefore'] = $this->getPeringkatROA($roa);
                    $data['penilaianroabefore'] = $this->getPenilaianROA($roa);
                }
            }

            // BOPO (before)
            if (in_array($field, ['bebanoperasionalbefore', 'pendapatanoperasionalbefore'])) {
                $beban = $field === 'bebanoperasionalbefore'
                    ? $numeric
                    : $this->parseNumeric($existing['bebanoperasionalbefore'] ?? 0);
                $pendapatan = $field === 'pendapatanoperasionalbefore'
                    ? $numeric
                    : $this->parseNumeric($existing['pendapatanoperasionalbefore'] ?? 0);
                if ($beban !== '' && $pendapatan !== '' && (float) $pendapatan != 0) {
                    $bopo = ((float) $beban / (float) $pendapatan) * 100;
                    $data['rasiobopobefore'] = $bopo;
                    $data['peringkatbopobefore'] = $this->getPeringkatBOPO($bopo);
                    $data['penilaianbopobefore'] = $this->getPenilaianBOPO($bopo);
                }
            }

            // NIM (before)
            if (in_array($field, ['pendapatanbungabefore', 'bebanbungabefore', 'ratarataasetprodbefore'])) {
                $pb = $field === 'pendapatanbungabefore'
                    ? $numeric
                    : $this->parseNumeric($existing['pendapatanbungabefore'] ?? 0);
                $bb = $field === 'bebanbungabefore'
                    ? $numeric
                    : $this->parseNumeric($existing['bebanbungabefore'] ?? 0);
                $ap = $field === 'ratarataasetprodbefore'
                    ? $numeric
                    : $this->parseNumeric($existing['ratarataasetprodbefore'] ?? 0);

                if ($pb !== '' && $bb !== '') {
                    $bersih = (float) $pb - (float) $bb;
                    $data['pendapatanbungabersihbefore'] = $bersih;
                    if ($ap !== '' && (float) $ap != 0) {
                        $nim = ($bersih / (float) $ap) * 100;
                        $data['rasionimbefore'] = $nim;
                        $data['peringkatnimbefore'] = $this->getPeringkatNIM($nim);
                        $data['penilaiannimbefore'] = $this->getPenilaianNIM($nim);
                    }
                }
            }

            $now = date('Y-m-d H:i:s');

            if ($existing) {
                $data['updated_at'] = $now;
                $this->tksrentabilitasModel->update($existing['id'], $data);
                $recordId = $existing['id'];
            } else {
                $data = array_merge($data, [
                    'user_id' => $this->userId,
                    'kodebpr' => $this->userKodebpr,
                    'periode_id' => (int) $this->periodeId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $recordId = $this->tksrentabilitasModel->insert($data);
            }

            if (!$recordId) {
                return $this->jsonError('Gagal menyimpan data ke database');
            }

            // Recalculate aggregate ranks
            $this->hitungRataRataRentabilitas((int) $recordId);
            $this->hitungRataRataRentabilitasBefore((int) $recordId);

            $updated = $this->tksrentabilitasModel->find((int) $recordId);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Data berhasil disimpan',
                'data' => $updated,
                'is_approved' => 0,
                'csrf_hash' => csrf_hash(),
            ]);

        } catch (\Throwable $e) {
            log_message('error', '[Tksrentabilitas::autoSave] ' . $e->getMessage() . ' | trace: ' . $e->getTraceAsString());
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
        if (empty($existing['penilaianrentabilitas'])) {
            return redirect()->back()->with('error', 'Data belum lengkap. Lengkapi data terlebih dahulu.');
        }

        $this->tksrentabilitasModel->update($existing['id'], [
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

        $this->tksrentabilitasModel->update($existing['id'], [
            'is_approved' => 0,
            'approved_by' => $this->userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->back()->with('message', 'Persetujuan berhasil dibatalkan.');
    }

    public function syncWithSipro()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;

        try {
            $showtks = $this->showtksModel->getByKodebprAndPeriode($this->userKodebpr, $this->periodeId);
            if (!$showtks) {
                return $this->response->setJSON(['success' => false, 'message' => 'Data TKS tidak ditemukan.']);
            }

            $now = date('Y-m-d H:i:s');
            $syncData = [
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId,
                'modal_inti' => $showtks['modal_inti'] ?? 0,
                'total_aset' => $showtks['total_aset'] ?? 0,
                'updated_at' => $now,
                'synced_from_tks' => 1,
                'sync_date' => $now,
                'is_approved' => 0,
            ];

            $existing = $this->showprofilresikoModel->getByKodebprAndPeriode($this->userKodebpr, $this->periodeId);
            if ($existing) {
                $this->showprofilresikoModel->update($existing['id'], $syncData);
            } else {
                $syncData['created_at'] = $now;
                $this->showprofilresikoModel->insert($syncData);
            }

            return $this->response->setJSON(['success' => true, 'message' => 'Data berhasil disinkronkan.', 'sync_date' => date('d/m/Y H:i:s')]);

        } catch (\Throwable $e) {
            log_message('error', 'Sync error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Kesalahan sinkronisasi: ' . $e->getMessage()]);
        }
    }

    public function unsyncWithSipro()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;

        try {
            $existing = $this->showprofilresikoModel->getByKodebprAndPeriode($this->userKodebpr, $this->periodeId);
            if ($existing) {
                $this->showprofilresikoModel->update($existing['id'], [
                    'synced_from_tks' => 0,
                    'sync_date' => null,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'is_approved' => 0,
                ]);
            }
            return $this->response->setJSON(['success' => true, 'message' => 'Sinkronisasi berhasil dibatalkan.']);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // Comments
    public function getKomentar()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(404)->setBody('Not Found');
        }

        return $this->response->setJSON(
            $this->tksCommentsModel->getKomentarByPeriode($this->userKodebpr, $this->periodeId, 'RENTABILITAS')
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
                'kategori' => 'RENTABILITAS',
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
                    'comments' => $this->tksCommentsModel->getKomentarByPeriode($this->userKodebpr, $this->periodeId, 'RENTABILITAS'),
                ]);
            }

            return redirect()->back()->with('message', 'Komentar berhasil ditambahkan');

        } catch (\Throwable $e) {
            log_message('error', 'tambahKomentar: ' . $e->getMessage());
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
            'RENTABILITAS'
        );

        return $this->response->setJSON(['status' => 'success', 'marked_count' => $marked, 'csrf_hash' => csrf_hash()]);
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
                'RENTABILITAS'
            ),
        ]);
    }

    // ─── Sync from previous periode ────────────────────────────────────────

    public function checkPrevPeriode()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;

        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        try {
            if (!$this->userKodebpr || !$this->periodeId) {
                return $this->response->setJSON(['status' => 'not_found', 'message' => 'Data tidak valid']);
            }

            $current = $this->periodetksModel->find($this->periodeId);
            if (!$current) {
                return $this->response->setJSON(['status' => 'not_found', 'message' => 'Periode aktif tidak ditemukan']);
            }

            $tahun = (int) $current['tahun'];
            $semRaw = strtolower(trim($current['semester']));
            $semNum = ($semRaw === 'ganjil' || $semRaw === '1') ? 1 : 2;

            if ($semNum === 1) {
                $prevSemNum = 2;
                $prevTahun = $tahun - 1;
            } else {
                $prevSemNum = 1;
                $prevTahun = $tahun;
            }

            $prevSemVariants = $prevSemNum === 1
                ? ['1', 'ganjil', 'Ganjil', 'GANJIL']
                : ['2', 'genap', 'Genap', 'GENAP'];

            $prevPeriode = $this->periodetksModel
                ->where('kodebpr', $this->userKodebpr)
                ->where('tahun', $prevTahun)
                ->whereIn('semester', $prevSemVariants)
                ->first();

            if (!$prevPeriode) {
                return $this->response->setJSON(['status' => 'not_found', 'message' => 'Tidak ada periode sebelumnya yang ditemukan']);
            }

            $prevData = $this->tksrentabilitasModel
                ->getByKodebprAndPeriode($this->userKodebpr, (int) $prevPeriode['id']);

            if (!$prevData) {
                return $this->response->setJSON(['status' => 'not_found', 'message' => 'Periode sebelumnya ada tetapi belum ada data Rentabilitas']);
            }

            $currentData = $this->getExistingRecord();
            $existingCount = $currentData ? 1 : 0;

            $semLabel = fn(int $n): string => $n === 1 ? 'Semester 1 (Ganjil)' : 'Semester 2 (Genap)';

            return $this->response->setJSON([
                'status' => 'found',
                'current_label' => $semLabel($semNum) . ' ' . $tahun,
                'prev_label' => $semLabel($prevSemNum) . ' ' . $prevTahun,
                'prev_periode_id' => (int) $prevPeriode['id'],
                'prev_data' => $prevData,
                'existing_count' => $existingCount,
            ]);

        } catch (\Throwable $e) {
            log_message('error', '[Tksrentabilitas::checkPrevPeriode] ' . $e->getMessage());
            return $this->response->setJSON(['status' => 'error', 'message' => 'Terjadi kesalahan sistem']);
        }
    }

    public function syncFromPrevPeriode()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;

        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        if (!$this->checkAccess($this->userGroups['pe'], $this->userGroups['admin'])) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Tidak memiliki akses untuk sinkronisasi data']);
        }

        try {
            if (!$this->userKodebpr || !$this->periodeId) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak valid']);
            }

            // Block sync if current period already has data
            $existingRecord = $this->getExistingRecord();
            if ($existingRecord) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Periode aktif sudah memiliki data Rentabilitas. Sinkronisasi dibatalkan untuk menghindari penimpaan data.',
                ]);
            }

            $current = $this->periodetksModel->find($this->periodeId);
            if (!$current) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Periode aktif tidak ditemukan']);
            }

            $tahun = (int) $current['tahun'];
            $semRaw = strtolower(trim($current['semester']));
            $semNum = ($semRaw === 'ganjil' || $semRaw === '1') ? 1 : 2;

            if ($semNum === 1) {
                $prevSemNum = 2;
                $prevTahun = $tahun - 1;
            } else {
                $prevSemNum = 1;
                $prevTahun = $tahun;
            }

            $prevSemVariants = $prevSemNum === 1
                ? ['1', 'ganjil', 'Ganjil', 'GANJIL']
                : ['2', 'genap', 'Genap', 'GENAP'];

            $prevPeriode = $this->periodetksModel
                ->where('kodebpr', $this->userKodebpr)
                ->where('tahun', $prevTahun)
                ->whereIn('semester', $prevSemVariants)
                ->first();

            if (!$prevPeriode) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Periode sebelumnya tidak ditemukan']);
            }

            $prevData = $this->tksrentabilitasModel
                ->getByKodebprAndPeriode($this->userKodebpr, (int) $prevPeriode['id']);

            if (!$prevData) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Tidak ada data Rentabilitas pada periode sebelumnya']);
            }

            $user = $this->userModel->find($this->userId);
            $now = date('Y-m-d H:i:s');

            $insertData = [
                'user_id' => $this->userId,
                'kodebpr' => $this->userKodebpr,
                'periode_id' => (int) $this->periodeId,
                'fullname' => $user['fullname'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
                'is_approved' => 0,

                // ROA before
                'lababelumpajakbefore' => $prevData['labarugibelumpajak'] ?? null,
                'ratarataasetbefore' => $prevData['ratarataaset'] ?? null,
                'rasioroabefore' => $prevData['rasioroa'] ?? null,
                'peringkatroabefore' => $prevData['peringkatroa'] ?? null,
                'penilaianroabefore' => $prevData['penilaianroa'] ?? null,

                // BOPO before
                'bebanoperasionalbefore' => $prevData['bebanoperasional'] ?? null,
                'pendapatanoperasionalbefore' => $prevData['pendapatanoperasional'] ?? null,
                'rasiobopobefore' => $prevData['rasiobopo'] ?? null,
                'peringkatbopobefore' => $prevData['peringkatbopo'] ?? null,
                'penilaianbopobefore' => $prevData['penilaianbopo'] ?? null,

                // NIM before
                'pendapatanbungabefore' => $prevData['pendapatanbunga'] ?? null,
                'bebanbungabefore' => $prevData['bebanbunga'] ?? null,
                'ratarataasetprodbefore' => $prevData['ratarataasetproduktif'] ?? null,
                'pendapatanbungabersihbefore' => $prevData['pendapatanbungabersih'] ?? null,
                'rasionimbefore' => $prevData['rasionim'] ?? null,
                'peringkatnimbefore' => $prevData['peringkatnim'] ?? null,
                'penilaiannimbefore' => $prevData['penilaiannim'] ?? null,

                // Aggregate before
                'rataratarentabilitasbefore' => $prevData['rataratarentabilitas'] ?? null,
                'penilaianrentabilitasbefore' => $prevData['penilaianrentabilitas'] ?? null,
                'peringkatrentabilitasbefore' => $prevData['peringkatrentabilitas'] ?? null,
                'nilaifaktorbefore' => $prevData['nilaifaktor'] ?? null,
            ];

            $newId = $this->tksrentabilitasModel->insert($insertData);

            if (!$newId) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menyimpan data sinkronisasi']);
            }

            $this->hitungRataRataRentabilitasBefore((int) $newId);

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Data periode sebelumnya berhasil disinkronisasi sebagai referensi periode aktif',
                'csrf_hash' => csrf_hash(),
            ]);

        } catch (\Throwable $e) {
            log_message('error', '[Tksrentabilitas::syncFromPrevPeriode] ' . $e->getMessage());
            return $this->response->setJSON(['status' => 'error', 'message' => 'Terjadi kesalahan sistem']);
        }
    }

    // Private utilities

    private function jsonError(string $msg): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->response->setJSON(['success' => false, 'message' => $msg]);
    }

    private function calculateSyncStatus(?array $profilRisiko, ?array $tks): array
    {
        $status = [
            'is_synced' => false,
            'sync_date' => null,
            'differences' => [],
            'modal_inti_pr' => 0,
            'total_aset_pr' => 0,
            'modal_inti_tks' => 0,
            'total_aset_tks' => 0,
        ];

        if (!$profilRisiko || !$tks)
            return $status;

        $status['modal_inti_pr'] = $profilRisiko['modal_inti'] ?? 0;
        $status['total_aset_pr'] = $profilRisiko['total_aset'] ?? 0;
        $status['modal_inti_tks'] = $tks['modal_inti'] ?? 0;
        $status['total_aset_tks'] = $tks['total_aset'] ?? 0;

        $modalMatch = abs($status['modal_inti_pr'] - $status['modal_inti_tks']) < 1000;
        $asetMatch = abs($status['total_aset_pr'] - $status['total_aset_tks']) < 1000;

        $status['is_synced'] = $modalMatch && $asetMatch;

        if ($status['is_synced']) {
            $status['sync_date'] = $profilRisiko['updated_at'] ?? date('Y-m-d H:i:s');
        } else {
            if (!$modalMatch)
                $status['differences'][] = ['field' => 'Modal Inti', 'pr_value' => $status['modal_inti_pr'], 'tks_value' => $status['modal_inti_tks'], 'diff' => $status['modal_inti_pr'] - $status['modal_inti_tks']];
            if (!$asetMatch)
                $status['differences'][] = ['field' => 'Total Aset', 'pr_value' => $status['total_aset_pr'], 'tks_value' => $status['total_aset_tks'], 'diff' => $status['total_aset_pr'] - $status['total_aset_tks']];
        }

        return $status;
    }

    private function semesterToBulanMisTks(string $semester): string
    {
        $semRaw = strtolower(trim($semester));
        $semNum = ($semRaw === 'ganjil' || $semRaw === '1') ? '1' : '2';
        return $semNum === '1' ? '06' : '12';
    }

    private function buildPeriodeMisTks(int $tahun, string $semester): string
    {
        return $tahun . $this->semesterToBulanMisTks($semester);
    }

    private function getTahunSemesterSebelumnya(int $tahun, string $semester): array
    {
        $semRaw = strtolower(trim($semester));
        $semNum = ($semRaw === 'ganjil' || $semRaw === '1') ? 1 : 2;

        return $semNum === 1
            ? [$tahun - 1, '2']
            : [$tahun, '1'];
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
            $data = $misTksModel->getMisTksDataCached($this->userKodebpr, $periodeCurrent, $periodePrev);

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
            log_message('error', '[Tksrentabilitas::checkMisTksData] ' . $e->getMessage());
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

        if (!$this->checkAccess($this->userGroups['pe'], $this->userGroups['admin'])) {
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
            $misTksData = $misTksModel->getMisTksDataCached($this->userKodebpr, $periodeCurrent, $periodePrev);

            if (array_filter($misTksData, fn($v) => $v !== null) === []) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Tidak ada data pada mis_tks untuk ditarik']);
            }

            $data = array_filter($misTksData, fn($v) => $v !== null);

            // Hitung ulang ROA
            if (isset($data['labarugibelumpajak'], $data['ratarataaset']) && (float) $data['ratarataaset'] != 0) {
                $roa = ((float) $data['labarugibelumpajak'] / (float) $data['ratarataaset']) * 100;
                $data['rasioroa'] = $roa;
                $data['peringkatroa'] = $this->getPeringkatROA($roa);
                $data['penilaianroa'] = $this->getPenilaianROA($roa);
            }
            if (isset($data['lababelumpajakbefore'], $data['ratarataasetbefore']) && (float) $data['ratarataasetbefore'] != 0) {
                $roaBefore = ((float) $data['lababelumpajakbefore'] / (float) $data['ratarataasetbefore']) * 100;
                $data['rasioroabefore'] = $roaBefore;
                $data['peringkatroabefore'] = $this->getPeringkatROA($roaBefore);
                $data['penilaianroabefore'] = $this->getPenilaianROA($roaBefore);
            }

            // Hitung ulang BOPO
            if (isset($data['bebanoperasional'], $data['pendapatanoperasional']) && (float) $data['pendapatanoperasional'] != 0) {
                $bopo = ((float) $data['bebanoperasional'] / (float) $data['pendapatanoperasional']) * 100;
                $data['rasiobopo'] = $bopo;
                $data['peringkatbopo'] = $this->getPeringkatBOPO($bopo);
                $data['penilaianbopo'] = $this->getPenilaianBOPO($bopo);
            }
            if (isset($data['bebanoperasionalbefore'], $data['pendapatanoperasionalbefore']) && (float) $data['pendapatanoperasionalbefore'] != 0) {
                $bopoBefore = ((float) $data['bebanoperasionalbefore'] / (float) $data['pendapatanoperasionalbefore']) * 100;
                $data['rasiobopobefore'] = $bopoBefore;
                $data['peringkatbopobefore'] = $this->getPeringkatBOPO($bopoBefore);
                $data['penilaianbopobefore'] = $this->getPenilaianBOPO($bopoBefore);
            }

            // Hitung ulang NIM
            if (isset($data['pendapatanbunga'], $data['bebanbunga'])) {
                $bersih = (float) $data['pendapatanbunga'] - (float) $data['bebanbunga'];
                $data['pendapatanbungabersih'] = $bersih;
                if (isset($data['ratarataasetproduktif']) && (float) $data['ratarataasetproduktif'] != 0) {
                    $nim = ($bersih / (float) $data['ratarataasetproduktif']) * 100;
                    $data['rasionim'] = $nim;
                    $data['peringkatnim'] = $this->getPeringkatNIM($nim);
                    $data['penilaiannim'] = $this->getPenilaianNIM($nim);
                }
            }
            if (isset($data['pendapatanbungabefore'], $data['bebanbungabefore'])) {
                $bersihBefore = (float) $data['pendapatanbungabefore'] - (float) $data['bebanbungabefore'];
                $data['pendapatanbungabersihbefore'] = $bersihBefore;
                if (isset($data['ratarataasetprodbefore']) && (float) $data['ratarataasetprodbefore'] != 0) {
                    $nimBefore = ($bersihBefore / (float) $data['ratarataasetprodbefore']) * 100;
                    $data['rasionimbefore'] = $nimBefore;
                    $data['peringkatnimbefore'] = $this->getPeringkatNIM($nimBefore);
                    $data['penilaiannimbefore'] = $this->getPenilaianNIM($nimBefore);
                }
            }

            $now = date('Y-m-d H:i:s');
            $existing = $this->getExistingRecord();

            if ($existing) {
                $data['updated_at'] = $now;
                $this->tksrentabilitasModel->update($existing['id'], $data);
                $recordId = $existing['id'];
            } else {
                $data = array_merge($data, [
                    'user_id' => $this->userId,
                    'kodebpr' => $this->userKodebpr,
                    'periode_id' => (int) $this->periodeId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $recordId = $this->tksrentabilitasModel->insert($data);
            }

            if (!$recordId) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menyimpan data hasil tarik']);
            }

            $this->hitungRataRataRentabilitas((int) $recordId);
            $this->hitungRataRataRentabilitasBefore((int) $recordId);

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Data berhasil ditarik dari mis_tks',
                'data' => $this->tksrentabilitasModel->find((int) $recordId),
                'csrf_hash' => csrf_hash(),
            ]);

        } catch (\Throwable $e) {
            log_message('error', '[Tksrentabilitas::tarikDataMisTks] ' . $e->getMessage());
            return $this->response->setJSON(['status' => 'error', 'message' => 'Terjadi kesalahan sistem']);
        }
    }

    private function resolvePeriodeMisTks(): array
    {
        $current = $this->periodetksModel->find($this->periodeId);
        if (!$current)
            return [null, null];

        $tahun = (int) $current['tahun'];
        $semester = (string) $current['semester'];

        $periodeCurrent = $this->buildPeriodeMisTks($tahun, $semester);
        [$prevTahun, $prevSemester] = $this->getTahunSemesterSebelumnya($tahun, $semester);
        $periodePrev = $this->buildPeriodeMisTks($prevTahun, $prevSemester);

        return [$periodeCurrent, $periodePrev];
    }
}