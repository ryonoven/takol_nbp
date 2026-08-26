<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_periodeprofilresiko;
use App\Models\M_periodesemesteran;
use App\Models\M_showprofilresiko;
use App\Models\M_showtks;
use App\Models\M_infobpr;
use App\Models\M_user;
use App\Models\M_tksprofilrisiko;
use App\Models\M_tkscomments;
use App\Models\M_tkscommentsread;
use Myth\Auth\Config\Services as AuthServices;

class Tksprofilrisiko extends Controller
{
    protected $auth;
    protected $session;
    protected $userKodebpr;
    protected $userId;
    protected $periodeId;

    // Models
    protected $periodeModel;
    protected $periodetksModel;
    protected $showprofilresikoModel;
    protected $showtksModel;
    protected $userModel;
    protected $infobprModel;
    protected $tksprofilrisikoModel;
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
        $this->tksprofilrisikoModel = new M_tksprofilrisiko();
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

        $periodeDetail = $this->periodetksModel->find($this->periodeId);
        $bprData = $this->infobprModel->getBprByKode($this->userKodebpr);

        $periodeProfilRisikoList = $this->periodeModel->where([
            'kodebpr' => $this->userKodebpr,
            'semester' => $periodeDetail['semester'],
            'tahun' => $periodeDetail['tahun']
        ])->findAll();

        $showprofilresiko = null;
        if (!empty($periodeProfilRisikoList)) {
            $periodeProfilRisiko = $periodeProfilRisikoList[0];
            $showprofilresiko = $this->showprofilresikoModel->where([
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $periodeProfilRisiko['id']
            ])->first();
        }

        $tksprofilrisiko = $this->tksprofilrisikoModel->where([
            'kodebpr' => $this->userKodebpr,
            'periode_id' => $this->periodeId
        ])->first();

        $showtks = $this->showtksModel->getByKodebprAndPeriode($this->userKodebpr, $this->periodeId);
        $syncStatus = $this->calculateSyncStatus($tksprofilrisiko);

        $peringkatLabels = [
            '1' => 'Sangat Rendah',
            '2' => 'Rendah',
            '3' => 'Sedang',
            '4' => 'Tinggi',
            '5' => 'Sangat Tinggi'
        ];

        // Check approval status
        $isApproved = false;
        $approvedAt = null;
        if ($tksprofilrisiko && isset($tksprofilrisiko['is_approved']) && $tksprofilrisiko['is_approved'] == 1) {
            $isApproved = true;
            $approvedAt = $tksprofilrisiko['approved_at'] ?? null;
        }

        $canApprove = false;
        if ($tksprofilrisiko && !empty($tksprofilrisiko['nilaiperingkat'])) {
            $canApprove = true;
        }

        $data = [
            'judul' => 'Penilaian Faktor Profil Risiko',
            'bprData' => $bprData,
            'periodeDetail' => $periodeDetail,
            'showprofilresiko' => $showprofilresiko,
            'tksprofilrisiko' => $tksprofilrisiko,
            'showtks' => $showtks,
            'syncStatus' => $syncStatus,
            'peringkatLabels' => $peringkatLabels,
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
            . view('Laporansemester/Tks/tksprofilrisiko', $data)
            . view('templates/v_footer');
    }

    private function calculateSyncStatus($tksData)
    {
        $status = [
            'is_synced' => false,
            'sync_date' => null,
            'synced_from' => null,
            'can_sync' => false,
            'periode_match' => false,
            'ntotalrisk' => null,
            'nbelumtotalrisk' => null,
            'kesimpulan' => null,
            'current_semester' => null,
            'current_tahun' => null,
            'before_semester' => null,
            'before_tahun' => null,
            'before_available' => false,
            'profil_risiko_periode_id' => null,
            'before_periode_id' => null,
        ];

        $periodeTKS = $this->periodetksModel->find($this->periodeId);
        if (!$periodeTKS) {
            log_message('error', 'Periode tidak ditemukan: ' . $this->periodeId);
            return $status;
        }

        $curSemester = $periodeTKS['semester'];
        $curTahun = $periodeTKS['tahun'];
        $prev = $this->getPreviousPeriode($curSemester, $curTahun);

        $status['current_semester'] = $curSemester;
        $status['current_tahun'] = $curTahun;
        $status['before_semester'] = $prev['semester'];
        $status['before_tahun'] = $prev['tahun'];

        // Cari ID periode sebelumnya di tabel periodesemesteran (periode aktif sudah = $this->periodeId)
        $beforePeriodeRow = $this->periodetksModel->where([
            'kodebpr' => $this->userKodebpr,
            'semester' => $prev['semester'],
            'tahun' => $prev['tahun']
        ])->first();
        $status['profil_risiko_periode_id'] = $this->periodeId;

        $periodeIds = [$this->periodeId];
        if ($beforePeriodeRow) {
            $status['before_periode_id'] = $beforePeriodeRow['id'];
            $periodeIds[] = $beforePeriodeRow['id'];
        }

        // 1 query ambil showprofilresiko utk periode aktif + sebelumnya sekaligus
        $showRows = $this->showprofilresikoModel
            ->select('periode_id, ntotalrisk, nbelumtotalrisk, kesimpulan')
            ->where('kodebpr', $this->userKodebpr)
            ->whereIn('periode_id', $periodeIds)
            ->findAll();

        $curShow = $beforeShow = null;
        foreach ($showRows as $r) {
            if ($r['periode_id'] == $this->periodeId) {
                $curShow = $r;
            } elseif ($beforePeriodeRow && $r['periode_id'] == $beforePeriodeRow['id']) {
                $beforeShow = $r;
            }
        }

        if (!$curShow || empty($curShow['ntotalrisk'])) {
            log_message('info', 'Data Profil Risiko periode aktif kosong');
            return $status;
        }

        $status['can_sync'] = true;
        $status['periode_match'] = true;
        $status['ntotalrisk'] = $curShow['ntotalrisk'];
        $status['kesimpulan'] = $curShow['kesimpulan'] ?? '';

        // Prioritas: pakai nbelumtotalrisk dari row periode aktif kalau ada,
        // fallback ke ntotalrisk row periode sebelumnya jika kosong
        if (!empty($curShow['nbelumtotalrisk'])) {
            $status['before_available'] = true;
            $status['nbelumtotalrisk'] = $curShow['nbelumtotalrisk'];
        } elseif ($beforeShow && !empty($beforeShow['ntotalrisk'])) {
            $status['before_available'] = true;
            $status['nbelumtotalrisk'] = $beforeShow['ntotalrisk'];
        }

        if ($tksData && isset($tksData['synced_from_pr']) && $tksData['synced_from_pr'] == 1) {
            $status['is_synced'] = true;
            $status['sync_date'] = $tksData['sync_date'] ?? $tksData['updated_at'];
            $status['synced_from'] = 'profil_risiko';
        }

        return $status;
    }

    public function syncWithProfilRisiko()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;

        try {
            $syncStatus = $this->calculateSyncStatus(null);

            if (!$syncStatus['periode_match']) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Periode Profil Risiko tidak ditemukan.'
                ]);
            }

            if (!$syncStatus['can_sync']) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Nilai Total Risiko pada Profil Risiko periode ini belum terisi. Silakan lengkapi keseluruhan Laporan Profil Risiko terlebih dahulu.'
                ]);
            }

            $peringkatLabel = $this->tksprofilrisikoModel->getPeringkatLabel($syncStatus['ntotalrisk']);
            $nilaiFaktor = $this->tksprofilrisikoModel->calculateNilaiFaktor($syncStatus['ntotalrisk']);
            $nilaiFaktorBefore = $syncStatus['before_available']
                ? $this->tksprofilrisikoModel->calculateNilaiFaktor($syncStatus['nbelumtotalrisk'])
                : null;

            $syncData = [
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId,
                'nilaiperingkat' => $syncStatus['ntotalrisk'],
                'nilaifaktor' => $nilaiFaktor,
                'nilaiperingkatbefore' => $syncStatus['before_available'] ? $syncStatus['nbelumtotalrisk'] : null,
                'nilaifaktorbefore' => $nilaiFaktorBefore,
                'peringkat' => $peringkatLabel,
                'ratarata' => $syncStatus['ntotalrisk'],
                'analisis' => $syncStatus['kesimpulan'] ?? '',
                'user_id' => $this->userId,
                'updated_at' => date('Y-m-d H:i:s'),
                'synced_from_pr' => 1,
                'sync_date' => date('Y-m-d H:i:s'),
                'profil_risiko_periode_id' => $syncStatus['profil_risiko_periode_id'],
                'is_approved' => 0,
            ];

            $existing = $this->tksprofilrisikoModel->where([
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId
            ])->first();

            if ($existing) {
                if (empty($syncData['deskripsi']) && !empty($existing['deskripsi'])) {
                    $syncData['deskripsi'] = $existing['deskripsi'];
                }
                $this->tksprofilrisikoModel->update($existing['id'], $syncData);
            } else {
                $syncData['created_at'] = date('Y-m-d H:i:s');
                $this->tksprofilrisikoModel->insert($syncData);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Data berhasil disinkronkan dengan Laporan Profil Risiko.',
                'sync_date' => date('d/m/Y H:i:s'),
                'data' => [
                    'nilaiperingkat' => $syncStatus['ntotalrisk'],
                    'nilaifaktor' => $nilaiFaktor,
                    'nilaiperingkatbefore' => $syncStatus['before_available'] ? $syncStatus['nbelumtotalrisk'] : null,
                    'nilaifaktorbefore' => $nilaiFaktorBefore,
                    'peringkat_label' => $peringkatLabel,
                    'analisis' => $syncStatus['kesimpulan'] ?? '',
                    'semester' => $syncStatus['current_semester'],
                    'tahun' => $syncStatus['current_tahun'],
                    'before_semester' => $syncStatus['before_semester'],
                    'before_tahun' => $syncStatus['before_tahun'],
                    'before_available' => $syncStatus['before_available'],
                ]
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Sync error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Terjadi kesalahan saat sinkronisasi: ' . $e->getMessage()
            ]);
        }
    }

    public function unsyncWithProfilRisiko()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;

        try {
            $existing = $this->tksprofilrisikoModel->where([
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId
            ])->first();

            if ($existing) {
                $this->tksprofilrisikoModel->update($existing['id'], [
                    'synced_from_pr' => 0,
                    'sync_date' => null,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'is_approved' => 0,
                ]);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Sinkronisasi berhasil dibatalkan. Anda dapat mengedit data secara manual.'
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ]);
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
            $existing = $this->tksprofilrisikoModel->where([
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
                $this->tksprofilrisikoModel->update($existing['id'], $data);
            } else {
                $data['kodebpr'] = $this->userKodebpr;
                $data['periode_id'] = $this->periodeId;
                $data['created_at'] = date('Y-m-d H:i:s');
                $this->tksprofilrisikoModel->insert($data);
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
            $existing = $this->tksprofilrisikoModel->where([
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId
            ])->first();

            $data = [
                'analisis' => $analisis,
                'updated_at' => date('Y-m-d H:i:s'),
                'user_id' => $this->userId,
                'is_approved' => 0,
            ];

            if ($existing) {
                $this->tksprofilrisikoModel->update($existing['id'], $data);
            } else {
                $data['kodebpr'] = $this->userKodebpr;
                $data['periode_id'] = $this->periodeId;
                $data['created_at'] = date('Y-m-d H:i:s');
                $this->tksprofilrisikoModel->insert($data);
            }

            return redirect()->back()->with('message', 'Analisa berhasil disimpan.');

        } catch (\Exception $e) {
            log_message('error', 'Error saving analisa: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan analisa.');
        }
    }

    public function simpanPeringkatManual()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;

        $nilaiperingkat = $this->request->getPost('nilaiperingkat');
        $nilaiperingkatbefore = $this->request->getPost('nilaiperingkatbefore');

        if (empty($nilaiperingkat)) {
            return redirect()->back()->with('error', 'Nilai peringkat tidak boleh kosong.');
        }

        try {
            $peringkatLabel = $this->tksprofilrisikoModel->getPeringkatLabel($nilaiperingkat);
            $nilaiFaktor = $this->tksprofilrisikoModel->calculateNilaiFaktor($nilaiperingkat);

            $nilaiFaktorBefore = null;
            if (!empty($nilaiperingkatbefore)) {
                $nilaiFaktorBefore = $this->tksprofilrisikoModel->calculateNilaiFaktor($nilaiperingkatbefore);
            }

            $existing = $this->tksprofilrisikoModel->where([
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId
            ])->first();

            $data = [
                'nilaiperingkat' => $nilaiperingkat,
                'nilaifaktor' => $nilaiFaktor,
                'nilaiperingkatbefore' => $nilaiperingkatbefore,
                'nilaifaktorbefore' => $nilaiFaktorBefore,
                'peringkat' => $peringkatLabel,
                'ratarata' => $nilaiperingkat,
                'updated_at' => date('Y-m-d H:i:s'),
                'user_id' => $this->userId,
                'synced_from_pr' => 0,
                'is_approved' => 0,
            ];

            if ($existing) {
                $this->tksprofilrisikoModel->update($existing['id'], $data);
            } else {
                $data['kodebpr'] = $this->userKodebpr;
                $data['periode_id'] = $this->periodeId;
                $data['created_at'] = date('Y-m-d H:i:s');
                $this->tksprofilrisikoModel->insert($data);
            }

            return redirect()->back()->with('message', 'Peringkat berhasil disimpan.');

        } catch (\Exception $e) {
            log_message('error', 'Error saving peringkat: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan peringkat.');
        }
    }

    public function simpanPeringkatBeforeManual()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;

        $nilaiperingkatbefore = $this->request->getPost('nilaiperingkatbefore_manual');

        if (empty($nilaiperingkatbefore)) {
            return redirect()->back()->with('error', 'Nilai peringkat periode sebelumnya tidak boleh kosong.');
        }

        try {
            $existing = $this->tksprofilrisikoModel->where([
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId
            ])->first();

            if (!$existing) {
                return redirect()->back()->with('error', 'Data belum ada. Silakan sinkronisasi atau isi peringkat terlebih dahulu.');
            }

            $nilaiFaktorBefore = $this->tksprofilrisikoModel->calculateNilaiFaktor($nilaiperingkatbefore);

            $this->tksprofilrisikoModel->update($existing['id'], [
                'nilaiperingkatbefore' => $nilaiperingkatbefore,
                'nilaifaktorbefore' => $nilaiFaktorBefore,
                'updated_at' => date('Y-m-d H:i:s'),
                'user_id' => $this->userId,
                'is_approved' => 0, // reset approval karena data berubah
            ]);

            return redirect()->back()->with('message', 'Nilai peringkat periode sebelumnya berhasil disimpan secara manual.');

        } catch (\Exception $e) {
            log_message('error', 'Error saving peringkat before manual: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan nilai peringkat periode sebelumnya.');
        }
    }

    public function approve()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;

        try {
            $existing = $this->tksprofilrisikoModel->where([
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId
            ])->first();

            if (!$existing) {
                return redirect()->back()->with('error', 'Data tidak ditemukan.');
            }

            if (empty($existing['nilaiperingkat'])) {
                return redirect()->back()->with('error', 'Data belum lengkap. Lengkapi nilai peringkat terlebih dahulu.');
            }

            $this->tksprofilrisikoModel->update($existing['id'], [
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
            $existing = $this->tksprofilrisikoModel->where([
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId
            ])->first();

            if (!$existing) {
                return redirect()->back()->with('error', 'Data tidak ditemukan.');
            }

            $this->tksprofilrisikoModel->update($existing['id'], [
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
            'PROFILRISIKO'
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
                'kategori' => 'PROFILRISIKO',
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
            'PROFILRISIKO'
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
            'PROFILRISIKO'
        );

        return $this->response->setJSON([
            'status' => 'success',
            'count' => $count
        ]);
    }

    private function getPreviousPeriode($semester, $tahun)
    {
        if ((int) $semester === 2) {
            return ['semester' => 1, 'tahun' => $tahun];
        }
        return ['semester' => 2, 'tahun' => $tahun - 1];
    }

}