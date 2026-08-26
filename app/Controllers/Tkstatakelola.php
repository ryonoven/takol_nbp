<?php

namespace App\Controllers;
use CodeIgniter\Controller;
use App\Models\M_periode;
use App\Models\M_periodesemesteran;
use App\Models\M_showfaktor;
use App\Models\M_showtks;
use App\Models\M_infobpr;
use App\Models\M_user;
use App\Models\M_tkstatakelola;
use App\Models\M_tkscomments;
use App\Models\M_tkscommentsread;
use Myth\Auth\Config\Services as AuthServices;

class Tkstatakelola extends Controller
{
    protected $auth;
    protected $session;
    protected $userKodebpr;
    protected $userId;
    protected $periodeId;

    protected $periodeModel;
    protected $periodetksModel;
    protected $showfaktorModel;
    protected $showtksModel;
    protected $userModel;
    protected $infobprModel;
    protected $tkstatakelolaModel;
    protected $tksCommentsModel;
    protected $tksCommentsReadModel;

    protected $userGroups = [];

    public function __construct()
    {
        $this->infobprModel = new M_infobpr();
        $this->periodeModel = new M_periode();
        $this->periodetksModel = new M_periodesemesteran();
        $this->showfaktorModel = new M_showfaktor();
        $this->showtksModel = new M_showtks();
        $this->userModel = new M_user();
        $this->tkstatakelolaModel = new M_tkstatakelola();
        $this->tksCommentsModel = new M_tkscomments();
        $this->tksCommentsReadModel = new M_tkscommentsread();

        $this->session = service('session');
        $this->auth = service('authentication');

        helper('text');

        $this->initializeUser();
    }

    private function initializeUser()
    {
        if ($this->auth->isLoggedIn()) {
            $this->userId = $this->auth->id();
            $user = $this->userModel->find($this->userId);
            $this->userKodebpr = $user['kodebpr'] ?? null;

            $authorize = AuthServices::authorization();
            $groups = ['pe', 'admin', 'dekom', 'direksi'];
            foreach ($groups as $group) {
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

    public function index()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;
        if ($redirect = $this->checkPeriode())
            return $redirect;

        if (!$this->userKodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid.');
        }

        $unreadCount = 0;
        if ($this->userId) {
            $unreadCount = $this->tksCommentsReadModel->countUnreadComments(
                $this->userKodebpr,
                $this->userId,
                $this->periodeId
            );
        }

        $user = $this->userModel->find($this->userId);
        $periodeDetail = $this->periodeModel->find($this->periodeId);
        $bprData = $this->infobprModel->getBprByKode($this->userKodebpr);

        $showfaktor = $this->showfaktorModel->getByKodebprAndPeriode($this->userKodebpr, $this->periodeId);
        $tkstatakelola = $this->tkstatakelolaModel->getByKodebprAndPeriode($this->userKodebpr, $this->periodeId);

        $syncData = $this->getSyncData($periodeDetail);
        $syncStatus = $this->calculateSyncStatus($tkstatakelola, $periodeDetail, $syncData);

        $isApproved = false;
        $approvedAt = null;
        if ($tkstatakelola && isset($tkstatakelola['is_approved']) && $tkstatakelola['is_approved'] == 1) {
            $isApproved = true;
            $approvedAt = $tkstatakelola['approved_at'] ?? null;
        }

        $canApprove = false;
        if ($tkstatakelola && !empty($tkstatakelola['nilaiperingkat'])) {
            $canApprove = true;
        }

        $data = [
            'judul' => 'Penilaian Faktor Tata Kelola',
            'bprData' => $bprData,
            'periodeDetail' => $periodeDetail,
            'showfaktor' => $showfaktor,
            'tkstatakelola' => $tkstatakelola,
            'syncStatus' => $syncStatus,
            'userInGroupAdmin' => $this->userGroups['admin'],
            'userInGroupPE' => $this->userGroups['pe'],
            'userInGroupDekom' => $this->userGroups['dekom'],
            'userInGroupDireksi' => $this->userGroups['direksi'],
            'isApproved' => $isApproved,
            'approvedAt' => $approvedAt,
            'canApprove' => $canApprove,
            'unreadCount' => $unreadCount,
            'fullname' => $user['fullname'] ?? 'Unknown',
        ];

        return view('templates/v_header', $data)
            . view('templates/v_sidebar')
            . view('templates/v_topbar')
            . view('Laporansemester/Tks/tkstatakelola', $data)
            . view('templates/v_footer');
    }

    private function calculateSyncStatus($tkstatakelola, $periodeDetail, $syncData)
    {
        $status = [
            'is_synced' => false,
            'sync_date' => null,
            'semester' => $periodeDetail['semester'] ?? 'N/A',
            'tahun' => $periodeDetail['tahun'] ?? 'N/A',
            'prev_semester' => $syncData['prevPeriode']['semester'] ?? null,
            'prev_tahun' => $syncData['prevPeriode']['tahun'] ?? null,
            'nilaikomposit' => null,
            'kesimpulan' => null,
            'nilaikomposit_before' => null,
            'current_available' => false,
            'before_available' => false,
            'can_sync' => false,
        ];

        $current = $syncData['currentShowfaktor'];
        $before = $syncData['prevShowfaktor'];

        if ($current && !empty($current['nilaikomposit'])) {
            $status['current_available'] = true;
            $status['nilaikomposit'] = $current['nilaikomposit'];
            $status['kesimpulan'] = $current['kesimpulan'] ?? '';
        }

        if ($before && !empty($before['nilaikomposit'])) {
            $status['before_available'] = true;
            $status['nilaikomposit_before'] = $before['nilaikomposit'];
        }

        // Sync hanya bisa jalan kalau minimal data periode aktif sudah ada
        $status['can_sync'] = $status['current_available'];

        if ($tkstatakelola && isset($tkstatakelola['synced_from_tk']) && $tkstatakelola['synced_from_tk'] == 1) {
            $status['is_synced'] = true;
            $status['sync_date'] = $tkstatakelola['sync_date'] ?? date('Y-m-d H:i:s');
        }

        return $status;
    }

    public function syncWithTatakelola()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;
        if ($redirect = $this->checkPeriode())
            return $redirect;

        try {
            $periodeDetail = $this->periodeModel->find($this->periodeId);
            $syncData = $this->getSyncData($periodeDetail);
            $syncStatus = $this->calculateSyncStatus(null, $periodeDetail, $syncData);

            if (!$syncStatus['can_sync']) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Nilai komposit Laporan Tata Kelola periode aktif (Semester '
                        . $syncStatus['semester'] . ' Tahun ' . $syncStatus['tahun']
                        . ') belum tersedia. Silakan lengkapi keseluruhan Laporan Tata Kelola terlebih dahulu.'
                ]);
            }

            $peringkatLabels = [
                '1' => 'Sangat Baik',
                '2' => 'Baik',
                '3' => 'Cukup',
                '4' => 'Kurang Baik',
                '5' => 'Buruk'
            ];

            $nilaikomposit = $syncStatus['nilaikomposit'];
            $peringkatLabel = ($nilaikomposit !== null)
                ? ($peringkatLabels[(string) $nilaikomposit] ?? 'Tidak Valid')
                : 'Tidak Valid';
            $nilaifaktor = $nilaikomposit * 0.30;

            $nilaikompositBefore = $syncStatus['before_available'] ? $syncStatus['nilaikomposit_before'] : null;
            $peringkatBeforeLabel = $nilaikompositBefore ? ($peringkatLabels[$nilaikompositBefore] ?? 'Tidak Valid') : null;
            $nilaifaktorBefore = $nilaikompositBefore ? $nilaikompositBefore * 0.30 : null;

            $syncPayload = [
                'nilaiperingkat' => $nilaikomposit,
                'peringkat' => $peringkatLabel,
                'nilaifaktor' => $nilaifaktor,
                'nilaiperingkatbefore' => $nilaikompositBefore,
                'peringkatbefore' => $peringkatBeforeLabel,
                'nilaifaktorbefore' => $nilaifaktorBefore,
                'deskripsi' => $syncStatus['kesimpulan'] ?? '',
                'analisis' => $syncStatus['kesimpulan'] ?? '',
                'updated_at' => date('Y-m-d H:i:s'),
                'synced_from_tk' => 1,
                'sync_date' => date('Y-m-d H:i:s'),
                'user_id' => $this->userId,
                'is_approved' => 0,
            ];

            $result = $this->tkstatakelolaModel->updateTatakelolaData(
                $this->userKodebpr,
                $this->periodeId,
                $syncPayload
            );

            if ($result) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Data berhasil disinkronkan.',
                    'sync_date' => date('d/m/Y H:i:s'),
                    'nilaiperingkat' => $syncPayload['nilaiperingkat'],
                    'peringkat' => $syncPayload['peringkat'],
                    'nilaifaktor' => number_format($nilaifaktor, 2),
                    'nilaiperingkatbefore' => $syncPayload['nilaiperingkatbefore'],
                    'peringkatbefore' => $syncPayload['peringkatbefore'],
                    'nilaifaktorbefore' => $nilaifaktorBefore !== null ? number_format($nilaifaktorBefore, 2) : null,
                    'before_available' => $syncStatus['before_available'],
                    'semester' => $syncStatus['semester'],
                    'tahun' => $syncStatus['tahun'],
                    'prev_semester' => $syncStatus['prev_semester'],
                    'prev_tahun' => $syncStatus['prev_tahun'],
                ]);
            }

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal menyimpan data sinkronisasi.'
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Sync error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ]);
        }
    }

    public function unsyncWithTatakelola()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;

        try {
            $existing = $this->tkstatakelolaModel->getByKodebprAndPeriode($this->userKodebpr, $this->periodeId);

            if ($existing) {
                $updateData = [
                    'synced_from_tk' => 0,
                    'sync_date' => null,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'is_approved' => 0,
                ];

                $this->tkstatakelolaModel->update($existing['id'], $updateData);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Sinkronisasi berhasil dibatalkan. Anda dapat mengisi data secara manual.'
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ]);
        }
    }

    public function simpanPeringkatSebelumnya()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;

        $nilaiperingkatbefore = $this->request->getPost('nilaiperingkatbefore');

        if (empty($nilaiperingkatbefore)) {
            return redirect()->back()->with('error', 'Nilai Peringkat Periode Sebelumnya harus diisi.');
        }

        if (!in_array($nilaiperingkatbefore, ['1', '2', '3', '4', '5'])) {
            return redirect()->back()->with('error', 'Nilai Peringkat harus antara 1-5.');
        }

        try {
            $result = $this->tkstatakelolaModel->simpanPeringkatSebelumnya(
                $this->userKodebpr,
                $this->periodeId,
                $nilaiperingkatbefore,
                $this->userId
            );

            if ($result) {
                $nilaifaktorbefore = $nilaiperingkatbefore * 0.30;
                return redirect()->back()->with(
                    'message',
                    'Peringkat Periode Sebelumnya berhasil disimpan. Nilai Faktor Before (30%): ' . number_format($nilaifaktorbefore, 2)
                );
            } else {
                return redirect()->back()->with('error', 'Gagal menyimpan data.');
            }

        } catch (\Exception $e) {
            log_message('error', 'Error saving peringkat sebelumnya: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
        }
    }

    public function simpanDeskripsi()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;

        $deskripsi = $this->request->getPost('deskripsi');

        if (empty($deskripsi)) {
            return redirect()->back()->with('error', 'Deskripsi tidak boleh kosong.');
        }

        try {
            $existing = $this->tkstatakelolaModel->where([
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId
            ])->first();

            $data = [
                'deskripsi' => $deskripsi,
                'updated_at' => date('Y-m-d H:i:s'),
                'user_id' => $this->userId,
                'is_approved' => 0,
            ];

            if ($existing) {
                $this->tkstatakelolaModel->update($existing['id'], $data);
            } else {
                $data['kodebpr'] = $this->userKodebpr;
                $data['periode_id'] = $this->periodeId;
                $data['created_at'] = date('Y-m-d H:i:s');
                $this->tkstatakelolaModel->insert($data);
            }

            return redirect()->back()->with('message', 'Deskripsi berhasil disimpan.');

        } catch (\Exception $e) {
            log_message('error', 'Error saving deskripsi: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan deskripsi.');
        }
    }

    public function simpanAnalisa()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;

        $analisis = $this->request->getPost('analisis');

        if (empty($analisis)) {
            return redirect()->back()->with('error', 'Analisa tidak boleh kosong.');
        }

        try {
            $data = [
                'analisis' => $analisis,
                'updated_at' => date('Y-m-d H:i:s'),
                'user_id' => $this->userId,
                'is_approved' => 0,
            ];

            $this->tkstatakelolaModel->updateTatakelolaData($this->userKodebpr, $this->periodeId, $data);

            return redirect()->back()->with('message', 'Analisa berhasil disimpan.');

        } catch (\Exception $e) {
            log_message('error', 'Error saving analisa: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan analisa.');
        }
    }

    public function simpanManual()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;

        $nilaiperingkat = $this->request->getPost('nilaiperingkat');
        $analisis = $this->request->getPost('analisis');

        if (empty($nilaiperingkat) || empty($analisis)) {
            return redirect()->back()->with('error', 'Peringkat Faktor dan Analisis harus diisi.');
        }

        try {
            $data = [
                'nilaiperingkat' => $nilaiperingkat,
                'analisis' => $analisis,
                'updated_at' => date('Y-m-d H:i:s'),
                'user_id' => $this->userId,
                'synced_from_tk' => 0,
                'is_approved' => 0,
            ];

            $this->tkstatakelolaModel->updateTatakelolaData($this->userKodebpr, $this->periodeId, $data);

            return redirect()->back()->with('message', 'Data berhasil disimpan secara manual.');

        } catch (\Exception $e) {
            log_message('error', 'Error saving manual data: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan data.');
        }
    }

    public function simpanPeringkatManual()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;

        $nilaiperingkat = $this->request->getPost('nilaiperingkat');
        $deskripsi = $this->request->getPost('deskripsi');

        if (empty($nilaiperingkat)) {
            return redirect()->back()->with('error', 'Nilai Peringkat Faktor harus diisi.');
        }

        try {
            if (empty($deskripsi)) {
                $peringkatLabels = [
                    '1' => 'Sangat Baik',
                    '2' => 'Baik',
                    '3' => 'Cukup',
                    '4' => 'Kurang Baik',
                    '5' => 'Buruk'
                ];
                $deskripsi = $peringkatLabels[$nilaiperingkat] ?? 'Tidak Valid';
                $peringkat = $deskripsi;
            } else {
                $peringkat = $deskripsi;
            }

            $nilaifaktor = $nilaiperingkat * 0.30;

            $data = [
                'nilaiperingkat' => $nilaiperingkat,
                'peringkat' => $peringkat,
                'deskripsi' => $deskripsi,
                'nilaifaktor' => $nilaifaktor,
                'updated_at' => date('Y-m-d H:i:s'),
                'user_id' => $this->userId,
                'synced_from_tk' => 0,
                'is_approved' => 0,
            ];

            $this->tkstatakelolaModel->updateTatakelolaData($this->userKodebpr, $this->periodeId, $data);

            return redirect()->back()->with(
                'message',
                'Peringkat Faktor berhasil disimpan. Nilai Faktor (30%): ' . number_format($nilaifaktor, 2)
            );

        } catch (\Exception $e) {
            log_message('error', 'Error saving peringkat manual: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan peringkat.');
        }
    }

    public function approve()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;

        try {
            $existing = $this->tkstatakelolaModel->where([
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId
            ])->first();

            if (!$existing) {
                return redirect()->back()->with('error', 'Data tidak ditemukan.');
            }

            if (empty($existing['nilaiperingkat'])) {
                return redirect()->back()->with('error', 'Data belum lengkap. Lengkapi nilai peringkat terlebih dahulu.');
            }

            $this->tkstatakelolaModel->update($existing['id'], [
                'is_approved' => 1,
                'approved_by' => $this->userId,
                'approved_at' => date('Y-m-d H:i:s')
            ]);

            return redirect()->back()->with('message', 'Data berhasil disetujui oleh Direktur Utama.');

        } catch (\Exception $e) {
            log_message('error', 'Error approving: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyetujui data.');
        }
    }

    public function unapprove()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;

        try {
            $existing = $this->tkstatakelolaModel->where([
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId
            ])->first();

            if (!$existing) {
                return redirect()->back()->with('error', 'Data tidak ditemukan.');
            }

            $this->tkstatakelolaModel->update($existing['id'], [
                'is_approved' => 0,
                'approved_by' => $this->userId,
                'approved_at' => date('Y-m-d H:i:s')
            ]);

            return redirect()->back()->with('message', 'Persetujuan berhasil dibatalkan.');

        } catch (\Exception $e) {
            log_message('error', 'Error unapproving: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat membatalkan persetujuan.');
        }
    }

    public function getKomentar()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(404)->setBody('Not Found');
        }

        $komentarList = $this->tksCommentsModel->getKomentarByPeriode(
            $this->userKodebpr,
            $this->periodeId,
            'TATAKELOLA'
        );

        return $this->response->setJSON($komentarList);
    }

    public function tambahKomentar()
    {
        $isAjax = $this->request->isAJAX();

        $validation = $this->validate(['komentar' => 'required']);

        if (!$validation) {
            if ($isAjax) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Komentar tidak boleh kosong'
                ]);
            }
            return redirect()->back()->with('error', 'Komentar tidak boleh kosong');
        }

        $komentar = $this->request->getPost('komentar');
        $fullname = $this->request->getPost('fullname');

        try {
            $data = [
                'komentar' => $komentar,
                'fullname' => $fullname,
                'kategori' => 'TATAKELOLA',
                'user_id' => $this->userId,
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $result = $this->tksCommentsModel->insert($data);

            if ($result) {
                if ($isAjax) {
                    $komentarList = $this->tksCommentsModel->getKomentarByPeriode(
                        $this->userKodebpr,
                        $this->periodeId
                    );

                    return $this->response->setJSON([
                        'status' => 'success',
                        'message' => 'Komentar berhasil ditambahkan',
                        'comments' => $komentarList
                    ]);
                }

                return redirect()->back()->with('message', 'Komentar berhasil ditambahkan');
            } else {
                throw new \Exception('Gagal menyimpan komentar');
            }

        } catch (\Exception $e) {
            log_message('error', 'Error tambahKomentar: ' . $e->getMessage());

            if ($isAjax) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Gagal menambahkan komentar: ' . $e->getMessage()
                ]);
            }

            return redirect()->back()->with('error', 'Gagal menambahkan komentar');
        }
    }

    public function markCommentsAsRead()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => 'error',
                'message' => 'Forbidden'
            ]);
        }

        $marked = $this->tksCommentsReadModel->markAllAsReadForPeriode(
            $this->userKodebpr,
            $this->userId,
            $this->periodeId,
            'TATAKELOLA'
        );

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Comments marked as read',
            'marked_count' => $marked
        ]);
    }

    public function getUnreadCount()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => 'error',
                'message' => 'Forbidden'
            ]);
        }

        $count = $this->tksCommentsReadModel->countUnreadComments(
            $this->userKodebpr,
            $this->userId,
            $this->periodeId,
            'TATAKELOLA'
        );

        return $this->response->setJSON([
            'status' => 'success',
            'count' => $count
        ]);
    }

    private function getPreviousPeriode($periodeDetail)
    {
        if (!$periodeDetail)
            return null;

        $currentSemester = (int) $periodeDetail['semester'];
        $currentTahun = (int) $periodeDetail['tahun'];

        if ($currentSemester == 1) {
            return ['semester' => 2, 'tahun' => $currentTahun - 1];
        }
        return ['semester' => 1, 'tahun' => $currentTahun];
    }

    private function getSyncData($periodeDetail)
    {
        $result = [
            'prevPeriode' => null,
            'currentShowfaktor' => null,
            'prevShowfaktor' => null,
        ];

        if (!$periodeDetail)
            return $result;

        $prevPeriode = $this->getPreviousPeriode($periodeDetail);
        $result['prevPeriode'] = $prevPeriode;

        // Cari row periodesemesteran utk periode sebelumnya (WAJIB scoped ke BPR user aktif)
        $prevPeriodeRow = null;
        if ($prevPeriode) {
            $prevPeriodeRow = $this->periodetksModel->where([
                'kodebpr' => $this->userKodebpr,
                'semester' => $prevPeriode['semester'],
                'tahun' => $prevPeriode['tahun']
            ])->first();
        }

        $periodeIds = [$this->periodeId];
        if ($prevPeriodeRow) {
            $periodeIds[] = $prevPeriodeRow['id'];
        }

        $rows = $this->showfaktorModel
            ->select('id, kodebpr, periode_id, nilaikomposit, peringkatkomposit, kesimpulan')
            ->where('kodebpr', $this->userKodebpr)
            ->whereIn('periode_id', $periodeIds)
            ->findAll();

        foreach ($rows as $r) {
            if ($r['periode_id'] == $this->periodeId) {
                $result['currentShowfaktor'] = $r;
            } elseif ($prevPeriodeRow && $r['periode_id'] == $prevPeriodeRow['id']) {
                $result['prevShowfaktor'] = $r;
            }
        }

        return $result;
    }
}