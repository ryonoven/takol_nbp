<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_rasiogaji;
use App\Models\M_infobpr;
use App\Models\M_user;
use App\Models\M_periodetransparansi;
use App\Models\M_transparansicomments;
use App\Models\M_transparansicommentsread;
use App\Models\M_penjelastindak;
use Myth\Auth\Config\Services as AuthServices;

class rasiogaji extends Controller
{
    protected $auth;
    protected $session;
    protected $userKodebpr;
    protected $userId;

    protected $rasiogajiModel;
    protected $userModel;
    protected $infobprModel;
    protected $periodeModel;
    protected $komentarModel;
    protected $commentReadsModel;
    protected $penjelastindakModel;

    private $userPermissions = null;
    private $userData = null;

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->session = service('session');
        $this->auth = service('authentication');

        if ($this->auth->check()) {
            $this->userId = $this->auth->id();
            $this->loadUserData();
        }
    }
    private function getRasiogajiModel()
    {
        if (!$this->rasiogajiModel) {
            $this->rasiogajiModel = new M_rasiogaji();
        }
        return $this->rasiogajiModel;
    }

    private function getUserModel()
    {
        if (!$this->userModel) {
            $this->userModel = new M_user();
        }
        return $this->userModel;
    }

    private function getPeriodeModel()
    {
        if (!$this->periodeModel) {
            $this->periodeModel = new M_periodetransparansi();
        }
        return $this->periodeModel;
    }

    private function getKomentarModel()
    {
        if (!$this->komentarModel) {
            $this->komentarModel = new M_transparansicomments();
        }
        return $this->komentarModel;
    }

    private function getCommentReadsModel()
    {
        if (!$this->commentReadsModel) {
            $this->commentReadsModel = new M_transparansicommentsread();
        }
        return $this->commentReadsModel;
    }

    private function getPenjelastindakModel()
    {
        if (!$this->penjelastindakModel) {
            $this->penjelastindakModel = new M_penjelastindak();
        }
        return $this->penjelastindakModel;
    }

    private function getInfobprModel()
    {
        if (!$this->infobprModel) {
            $this->infobprModel = new M_infobpr();
        }
        return $this->infobprModel;
    }

    private function loadUserData()
    {
        if ($this->userData === null && $this->userId) {
            $this->userData = $this->getUserModel()->find($this->userId);
            $this->userKodebpr = $this->userData['kodebpr'] ?? null;
        }
    }

    private function getUserPermissions()
    {
        if ($this->userPermissions === null && $this->userId) {
            $authorize = AuthServices::authorization();

            $this->userPermissions = [
                'pe' => $authorize->inGroup('pe', $this->userId),
                'admin' => $authorize->inGroup('admin', $this->userId),
                'dekom' => $authorize->inGroup('dekom', $this->userId),
                'dekom2' => $authorize->inGroup('dekom2', $this->userId),
                'dekom3' => $authorize->inGroup('dekom3', $this->userId),
                'dekom4' => $authorize->inGroup('dekom4', $this->userId),
                'dekom5' => $authorize->inGroup('dekom5', $this->userId),
                'direksi' => $authorize->inGroup('direksi', $this->userId),
                'direksi2' => $authorize->inGroup('direksi2', $this->userId),
            ];
        }
        return $this->userPermissions;
    }

    private function checkAuthentication()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }
        return null;
    }

    private function getIndexData($periodeId, $kodebpr)
    {
        $subkategori = 'Rasiogaji';

        $rasiogajiData = $this->getRasiogajiModel()
            ->select('*, accdekom, accdekom_by, accdekom_at, is_approved, approved_by, approved_at')
            ->where('periode_id', $periodeId)
            ->where('kodebpr', $kodebpr)
            ->limit(10)
            ->findAll();

        $komentarList = $this->getKomentarModel()
            ->where('subkategori', $subkategori)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->findAll();

        $penjelastindak = $this->getPenjelastindakModel()
            ->getDataPenjelasByKodebprAndPeriode($subkategori, $kodebpr, $periodeId);

        return [
            'rasiogaji' => $rasiogajiData,
            'komentarList' => $komentarList,
            'penjelastindak' => $penjelastindak
        ];
    }

    public function index()
    {
        $authCheck = $this->checkAuthentication();
        if ($authCheck)
            return $authCheck;

        if (!session('active_periode')) {
            return redirect()->to('/Periodetransparansi');
        }

        $periodeId = session('active_periode');
        $kodebpr = $this->userKodebpr;

        if (!$kodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
        }

        $indexData = $this->getIndexData($periodeId, $kodebpr);

        $periodeDetail = $this->getPeriodeModel()->getPeriodeDetail($periodeId);
        $bprData = $this->getInfobprModel()->getBprByKode($kodebpr);

        $permissions = $this->getUserPermissions();

        $accdekomData = $this->rasiogajiModel
            ->select('accdekom, accdekom_by, accdekom_at')
            ->where('periode_id', $periodeId)
            ->where('kodebpr', $kodebpr)
            ->findAll();

        $accdirutData = $this->rasiogajiModel
            ->select('is_approved, approved_by, approved_at')
            ->where('periode_id', $periodeId)
            ->where('kodebpr', $kodebpr)
            ->findAll();

        $lastVisit = session('last_visit_komentar') ?? date('Y-m-d H:i:s', strtotime('-1 day'));
        session()->set('last_visit_komentar', date('Y-m-d H:i:s'));

        $canApprove = true;

        // Ambil semua data dengan kondisi yang sesuai
        $accdekomValues = $this->rasiogajiModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->findAll();  // Mengambil semua data yang sesuai

        // Loop melalui setiap data
        foreach ($accdekomValues as $accdekomValue) {
            if ($accdekomValue['accdekom'] != 1) {
                // Jika ada data yang accdekom tidak 1, set canApprove ke false
                $canApprove = false;
                break;  // Tidak perlu melanjutkan jika sudah ditemukan yang tidak valid
            }
        }

        $data = [
            'judul' => '12. Rasio Gaji Tertinggi dan Gaji Terendah',
            'rasiogaji' => $indexData['rasiogaji'],
            'userInGroupPE' => $permissions['pe'],
            'userInGroupAdmin' => $permissions['admin'],
            'userInGroupDekom' => $permissions['dekom'],
            'userInGroupDekom2' => $permissions['dekom2'],
            'userInGroupDekom3' => $permissions['dekom3'],
            'userInGroupDekom4' => $permissions['dekom4'],
            'userInGroupDekom5' => $permissions['dekom5'],
            'userInGroupDireksi' => $permissions['direksi'],
            'userInGroupDireksi2' => $permissions['direksi2'],
            'fullname' => $this->userData['fullname'] ?? 'Unknown',
            'kodebpr' => $kodebpr,
            'komentarModel' => $this->getKomentarModel(),
            'commentReadsModel' => $this->getCommentReadsModel(),
            'lastVisit' => $lastVisit,
            'periodeId' => $periodeId,
            'periodeDetail' => $periodeDetail,
            'bprData' => $bprData,
            'accdekomData' => $accdekomData,
            'accdirutData' => $accdirutData,
            'periodetransparansi' => $this->getPeriodeModel()->find($periodeId),
            'penjelastindak' => $indexData['penjelastindak'],
            'canApprove' => $canApprove
        ];

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('rasiogaji/index', $data);
        echo view('templates/v_footer');
    }

    public function tambahpenjelasAjax()
    {
        if (!$this->auth->check()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        if ($this->request->getMethod() === 'post') {
            // Validate the incoming data
            $validation = \Config\Services::validation();
            $val = $this->validate([
                'pegawaitinggi' => 'required',
                'pegawairendah' => 'required',
                'dirtinggi' => 'required',
                'dirrendah' => 'required',
                'dekomtinggi' => 'required',
                'dekomrendah' => 'required',
                'pegawaitinggirendah' => 'required',
                'direksitinggirendah' => 'required',
                'dekomtinggirendah' => 'required',
                'dirdekomtinggirendah' => 'required',
                'dirpegawaitinggirendah' => 'required'
            ]);

            if (!$val) {
                return $this->response->setJSON(['status' => 'error', 'message' => $validation->listErrors()]);
            } else {
                // Prepare the data to be inserted
                $userId = $this->auth->id();
                $kodebpr = $this->userKodebpr;
                $periodeId = session('active_periode');

                // PERBAIKAN: Gunakan method getter untuk model
                $existingData = $this->getRasiogajiModel()->where(['kodebpr' => $kodebpr, 'periode_id' => $periodeId])->first();

                $data = [
                    'pegawaitinggi' => $this->request->getPost('pegawaitinggi'),
                    'pegawairendah' => $this->request->getPost('pegawairendah'),
                    'dirtinggi' => $this->request->getPost('dirtinggi'),
                    'dirrendah' => $this->request->getPost('dirrendah'),
                    'dekomtinggi' => $this->request->getPost('dekomtinggi'),
                    'dekomrendah' => $this->request->getPost('dekomrendah'),
                    'pegawaitinggirendah' => $this->request->getPost('pegawaitinggirendah'),
                    'direksitinggirendah' => $this->request->getPost('direksitinggirendah'),
                    'dekomtinggirendah' => $this->request->getPost('dekomtinggirendah'),
                    'dirdekomtinggirendah' => $this->request->getPost('dirdekomtinggirendah'),
                    'dirpegawaitinggirendah' => $this->request->getPost('dirpegawaitinggirendah'),
                    'periode_id' => $periodeId,
                    'user_id' => $userId,
                    'kodebpr' => $kodebpr,
                    'fullname' => $this->getUserModel()->find($userId)['fullname'],
                    'accdekom' => 0,
                    'is_approved' => 0,
                ];

                if ($existingData) {
                    // Data sudah ada, lakukan UPDATE
                    $data['id'] = $existingData['id']; // Pastikan ID disertakan

                    $result = $this->getRasiogajiModel()->save($data);

                    if ($result) {
                        return $this->response->setJSON([
                            'status' => 'success',
                            'message' => 'Data berhasil diperbarui'
                        ]);
                    } else {
                        return $this->response->setJSON([
                            'status' => 'error',
                            'message' => 'Gagal memperbarui data'
                        ]);
                    }
                } else {
                    // Data belum ada, lakukan INSERT
                    unset($data['id']); // Pastikan tidak ada ID untuk insert

                    $result = $this->getRasiogajiModel()->insert($data);

                    if ($result) {
                        return $this->response->setJSON([
                            'status' => 'success',
                            'message' => 'Data berhasil ditambahkan'
                        ]);
                    } else {
                        return $this->response->setJSON([
                            'status' => 'error',
                            'message' => 'Gagal menambahkan data'
                        ]);
                    }
                }
            }
        }

        return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Invalid request']);
    }

    public function getUnreadCommentCountForFactor()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $Id = $this->request->getGet('id');
        $kodebpr = $this->userKodebpr;
        $userId = $this->userId;
        $periodeId = session('active_periode');

        if (!$Id || !$kodebpr || !$userId || !$periodeId) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Missing data.']);
        }

        $count = $this->getCommentReadsModel()->countUnreadCommentsForUserByFactor($Id, $kodebpr, $userId, $periodeId);

        return $this->response->setJSON(['unread_count' => $count]);
    }

    public function cekKomentarBaru()
    {
        $subkategori = 'Rasiogaji';
        $kodebpr = $this->request->getGet('kodebpr');
        $lastVisit = $this->request->getGet('last_visit');
        $periodeId = session('active_periode');

        $results = $this->komentarModel
            ->select('id, COUNT(*) as jumlah')
            ->where('subkategori', $subkategori)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('created_at >', $lastVisit)
            ->groupBy('id')
            ->findAll();

        return $this->response->setJSON($results);
    }

    public function getKomentarByFaktorId()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(404)->setBody('Not Found');
        }
        $subkategori = 'Rasiogaji';
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        $komentarList = $this->getKomentarModel()->getKomentarByFaktorId($subkategori, $kodebpr, $periodeId);

        return $this->response->setJSON($komentarList);
    }

    public function Tambahkomentar()
    {
        $authCheck = $this->checkAuthentication();
        if ($authCheck)
            return $authCheck;

        if (!isset($_POST['TambahKomentar'])) {
            return redirect()->to(base_url('Rasiogaji'));
        }

        if (!$this->userKodebpr) {
            session()->setFlashdata('error', 'User tidak memiliki kode BPR yang valid');
            return redirect()->back();
        }

        $val = $this->validate([
            'komentar' => [
                'label' => 'Komentar',
                'rules' => 'required',
                'errors' => ['required' => '{field} tidak boleh kosong.']
            ],
        ]);

        if (!$val) {
            session()->setFlashdata('err', \Config\Services::validation()->listErrors());
            return redirect()->back();
        }

        $data = [
            'id' => $this->request->getPost('id'),
            'subkategori' => 'Rasiogaji',
            'komentar' => $this->request->getPost('komentar'),
            'fullname' => $this->request->getPost('fullname'),
            'user_id' => $this->userId,
            'kodebpr' => $this->userKodebpr,
            'periode_id' => session('active_periode'),
            'created_at' => date('Y-m-d H:i:s')
        ];

        $this->getKomentarModel()->insertKomentar($data);
        session()->setFlashdata('message', 'Komentar berhasil ditambahkan');
        return redirect()->to(base_url('Rasiogaji') . '?modal_komentar=' . $this->request->getPost('id'));
    }

    public function markUserCommentsAsRead()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $Id = $this->request->getPost('id');
        $kodebpr = $this->userKodebpr; // Get from property
        $userId = user_id();
        $periodeId = session('active_periode');

        if (!$Id || !$kodebpr || !$userId || !$periodeId) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Missing data.']);
        }

        // Get all comment IDs for this factor, kodebpr, periode, and not by the current user
        $commentsToMark = $this->getKomentarModel()->select('id')
            ->where('subkategori', $Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('user_id !=', $userId) // Mark comments from others as read
            ->findAll();

        if (!empty($commentsToMark)) {
            foreach ($commentsToMark as $comment) {
                $this->getCommentReadsModel()->markAsRead($comment['id'], $userId);
            }
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Comments marked as read for this user.']);
    }

    public function saveKomentar()
    {
        $data = [
            'id' => $this->request->getPost('id'),
            'kodebpr' => $this->request->getPost('kodebpr'),
            'komentar' => $this->request->getPost('komentar'),
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'user_id' => session()->get('user_id')
        ];

        $this->komentarModel->insert($data);
        return $this->response->setJSON(['status' => 'comment_saved']);
    }

    public function tambahketerangan()
    {
        $authCheck = $this->checkAuthentication();
        if ($authCheck)
            return $authCheck;

        if (!isset($_POST['tambahketerangan'])) {
            return redirect()->to(base_url('Rasiogaji'));
        }

        $val = $this->validate([
            'tindaklanjut' => [
                'label' => 'Penjelasan lebih lanjut',
                'rules' => 'required',
                'errors' => ['required' => '{field} tidak boleh kosong.']
            ]
        ]);

        if (!$val) {
            session()->setFlashdata('err', \Config\Services::validation()->listErrors());
            return redirect()->back()->withInput();
        }

        $periodeId = session('active_periode');
        $kodebpr = $this->userKodebpr;

        if (!$kodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
        }

        $penjelastindak = [
            'subkategori' => 'Rasiogaji',
            'tindaklanjut' => $this->request->getPost('tindaklanjut'),
            'kodebpr' => $kodebpr,
            'periode_id' => $periodeId,
            'fullname' => $this->userData['fullname'] ?? null,
            'user_id' => $this->userId,
        ];

        $this->getPenjelastindakModel()->tambahpenjelastindak($penjelastindak);
        session()->setFlashdata('message', 'Data berhasil diubah');

        return redirect()->to(base_url('Rasiogaji'));
    }

    public function editketerangan()
    {
        $id = $this->request->getPost('id');
        $subkategori = 'Rasiogaji';
        $kodebpr = $this->userKodebpr;

        if (!$kodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
        }

        $periodeId = session('active_periode');
        if (!$periodeId) {
            return redirect()->back()->with('error', 'Periode tidak valid');
        }

        $tindaklanjut = $this->request->getPost('tindaklanjut');
        if (empty($tindaklanjut)) {
            return redirect()->back()->with('error', 'Tindak Lanjut atau Penjelasan tidak boleh kosong');
        }

        $data = [
            'tindaklanjut' => $tindaklanjut,
            'user_id' => $this->userId,
            'kodebpr' => $kodebpr,
        ];

        if ($this->getPenjelastindakModel()->editberdasarkankodedanperiode($data, $subkategori, $kodebpr, $periodeId)) {
            session()->setFlashdata('message', 'Data berhasil diubah');
        } else {
            session()->setFlashdata('err', 'Gagal mengubah data');
        }

        return redirect()->to(base_url('Rasiogaji'));
    }

    public function ubahketerangan()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
        }

        if (isset($_POST['ubahketerangan'])) {
            $val = $this->validate([
                'keterangan' => [
                    'label' => 'Keterangan',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Keterangan',
                    'rasiogaji' => $this->rasiogajiModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('rasiogaji/index', $data);
                echo view('templates/v_footer');

            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'keterangan' => $this->request->getPost('keterangan')
                ];

                // Update data
                $success = $this->rasiogajiModel->ubah($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Tindak Lanjut Direksi berhasil diubah ');
                    return redirect()->to(base_url('rasiogaji'));
                }
            }
        } else {
            return redirect()->to(base_url('rasiogaji'));
        }
    }

    public function hapus($id)
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        $this->rasiogajiModel = new M_rasiogaji();

        $this->rasiogajiModel->builder()
            ->where('id', $id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->delete();

        session()->setFlashdata('message', 'Data berhasil dihapus');
        return redirect()->to(base_url('Rasiogaji'));
    }

    private function updateApprovalStatus($id, $isApproved, $successMessage, $errorMessage)
    {
        if (!is_numeric($id) || $id <= 0) {
            session()->setFlashdata('err', 'ID tidak valid.');
            return redirect()->back();
        }

        $data = $this->getRasiogajiModel()->find($id);
        if (!$data) {
            session()->setFlashdata('err', 'Data tidak ditemukan.');
            return redirect()->back();
        }

        $dataUpdate = [
            'id' => $id,
            'is_approved' => $isApproved,
            'approved_by' => $this->userId,
            'approved_at' => $isApproved ? date('Y-m-d H:i:s') : null,
        ];

        if ($this->getRasiogajiModel()->save($dataUpdate)) {
            session()->setFlashdata('message', $successMessage);
        } else {
            session()->setFlashdata('err', $errorMessage);
        }

        return redirect()->back();
    }

    public function approve($idrasiogaji)
    {
        return $this->updateApprovalStatus(
            $idrasiogaji,
            1,
            'Data berhasil disetujui.',
            'Terjadi kesalahan saat melakukan approval.'
        );
    }

    public function unapprove($idrasiogaji)
    {
        return $this->updateApprovalStatus(
            $idrasiogaji,
            0,
            'Approval dibatalkan.',
            'Terjadi kesalahan saat membatalkan approval.'
        );
    }

    private function bulkUpdateApproval($isApproved, $field, $successMessage, $isError = false)
    {
        date_default_timezone_set('Asia/Jakarta');
        $userId = $this->userId;
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        if (!$kodebpr || !$periodeId) {
            session()->setFlashdata('err', 'Kode BPR atau Periode ID tidak valid');
            return redirect()->back();
        }

        $count = $this->getRasiogajiModel()
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->countAllResults();

        if ($count === 0) {
            session()->setFlashdata('err', 'Tidak ada data yang bisa diupdate untuk periode ini');
            return redirect()->back();
        }

        $currentTimestamp = date('Y-m-d H:i:s');

        $dataUpdate = [
            $field => $isApproved,
            'approved_by' => $isApproved ? $this->userId : null,
            $field . '_at' => $isApproved ? $currentTimestamp : null,
        ];

        // Tambahkan update untuk approved_at dan accdekom_at jika field utama disetujui
        if ($isApproved) {
            if ($field === 'is_approved') {
                $dataUpdate['approved_at'] = $currentTimestamp;
            } elseif ($field === 'accdekom') {
                $dataUpdate['accdekom_at'] = $currentTimestamp;
            }
        } else {
            // Jika dibatalkan, set timestamp menjadi null
            if ($field === 'is_approved') {
                $dataUpdate['approved_at'] = null;
            } elseif ($field === 'accdekom') {
                $dataUpdate['accdekom_at'] = null;
            }
        }

        try {
            $updated = $this->getRasiogajiModel()
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->set($dataUpdate)
                ->update();

            if (!$updated) {
                session()->setFlashdata('err', 'Gagal mengupdate data approval');
                return redirect()->back();
            }

            if ($isError) {
                session()->setFlashdata('err', $successMessage);
            } else {
                session()->setFlashdata('message', $successMessage);
            }

            return redirect()->back();

        } catch (\Exception $e) {
            log_message('error', 'Error in bulk approval: ' . $e->getMessage());
            session()->setFlashdata('err', 'Terjadi kesalahan sistem: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function approveSemua()
    {
        return $this->bulkUpdateApproval(1, 'is_approved', 'Semua data berhasil disetujui.');
    }

    public function unapproveSemua()
    {
        return $this->bulkUpdateApproval(0, 'is_approved', 'Semua approval dibatalkan.', true);
    }

    // Method untuk update approval komisaris saja (tanpa dependency)
    private function updateKomisarisApproval($isApproved)
    {
        $field = 'accdekom';
        $successMessage = $isApproved ? 'Persetujuan komisaris utama berhasil diberikan.' : 'Persetujuan komisaris utama dibatalkan.';

        return $this->bulkUpdateApproval($isApproved, $field, $successMessage, !$isApproved);
    }

    // Method untuk update approval direktur saja (tanpa dependency)
    private function updateDirekturApproval($isApproved)
    {
        $field = 'is_approved';
        $successMessage = $isApproved ? 'Persetujuan direktur utama berhasil diberikan.' : 'Persetujuan direktur utama dibatalkan.';

        return $this->bulkUpdateApproval($isApproved, $field, $successMessage, !$isApproved);
    }

    // Public methods dengan dependency yang benar
    public function approveSemuaKom()
    {
        return $this->updateKomisarisApproval(1);
    }

    public function unapproveSemuaKom()
    {
        // Ketika komisaris dibatalkan, direktur juga harus dibatalkan
        $this->updateDirekturApproval(0);  // Batalkan direktur dulu
        return $this->updateKomisarisApproval(0);  // Lalu batalkan komisaris
    }

    public function approveSemuaDirut()
    {
        return $this->updateDirekturApproval(1);
    }

    public function unapproveSemuaDirut()
    {
        // Ketika direktur dibatalkan, hanya direktur saja yang dibatalkan
        // TIDAK perlu membatalkan komisaris
        return $this->updateDirekturApproval(0);
    }

    // ATAU jika Anda ingin hierarchy yang ketat:
// Dimana pembatalan komisaris akan membatalkan direktur juga

    public function unapproveSemuaKomWithHierarchy()
    {
        try {
            // 1. Batalkan direktur terlebih dahulu
            $this->updateDirekturApproval(0);

            // 2. Baru batalkan komisaris
            $result = $this->updateKomisarisApproval(0);

            // 3. Set pesan gabungan
            session()->setFlashdata('message', 'Persetujuan komisaris dan direktur utama telah dibatalkan.');

            return redirect()->back();

        } catch (\Exception $e) {
            log_message('error', 'Error in unapprove hierarchy: ' . $e->getMessage());
            session()->setFlashdata('err', 'Terjadi kesalahan saat membatalkan persetujuan.');
            return redirect()->back();
        }
    }

    private function updateApprovalStatusKom($id, $isApproved, $successMessage, $errorMessage)
    {
        date_default_timezone_set('Asia/Jakarta');
        if (!is_numeric($id) || $id <= 0) {
            session()->setFlashdata('err', 'ID tidak valid.');
            return redirect()->back();
        }

        $data = $this->getRasiogajiModel()->find($id);
        if (!$data) {
            session()->setFlashdata('err', 'Data tidak ditemukan.');
            return redirect()->back();
        }

        $dataUpdate = [
            'id' => $id,
            'accdekom' => $isApproved,
            'accdekom_by' => $isApproved ? $this->userId : null,
            'accdekom_at' => $isApproved ? date('Y-m-d H:i:s') : null,
        ];

        if ($this->getRasiogajiModel()->save($dataUpdate)) {
            session()->setFlashdata('message', $successMessage);
        } else {
            session()->setFlashdata('err', $errorMessage);
        }

        return redirect()->back();
    }

    public function setNullKolomTindak($id)
    {
        $authCheck = $this->checkAuthentication();
        if ($authCheck)
            return $authCheck;

        $result = $this->getRasiogajiModel()->setNullKolomTindak($id);

        if ($result) {
            session()->setFlashdata('message', 'Data berhasil dihapus');
        } else {
            session()->setFlashdata('err', 'Data gagal dihapus');
        }

        return redirect()->to(base_url('Rasiogaji'));
    }


    public function exporttxtrasiogaji()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $this->rasiogajiModel = model('M_rasiogaji');
        $this->infobprModel = model('M_infobpr');
        $this->penjelastindakModel = model('M_penjelastindak');

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');
        $subkategori = "Rasiogaji";

        $data_rasiogaji = $this->rasiogajiModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);

        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        $data_penjelastindak = $this->penjelastindakModel->where('subkategori', $subkategori)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->findAll();

        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'];
            $kodejenis = $infobpr['kodejenis'];
        }

        $isEmpty = function ($value) {
            return empty($value) || is_null($value) || $value === '' || $value === '0';
        };

        $periodeDetail = $this->getPeriodeModel()->getPeriodeDetail($periodeId);
        $exportDate = $periodeDetail['tahun'] ?? date('Y');

        $output = "";
        $output .= "H01|" . $kodejenis . "|" . $sandibpr . "|" . $exportDate . "-12-31|LTBPRK|E0600|0|\n";

        foreach ($data_rasiogaji as $row) {
            $hasValidData = false;
            $pegawaitinggirendah = isset($row['pegawaitinggirendah']) && !$isEmpty($row['pegawaitinggirendah']) ? $row['pegawaitinggirendah'] : '';
            if ($pegawaitinggirendah !== '') {
                $hasValidData = true;
            }

            // Hanya generate jika ada data valid
            if ($hasValidData) {
                $output .= "D01|" . "070100000000" . "|" . $pegawaitinggirendah . "\r\n";
            }
        }

        foreach ($data_rasiogaji as $row) {
            // Hitung rasio pegawai
            foreach ($data_rasiogaji as $row) {
                $hasValidData = false;
                $direksitinggirendah = isset($row['direksitinggirendah']) && !$isEmpty($row['direksitinggirendah']) ? $row['direksitinggirendah'] : '';

                if ($direksitinggirendah !== '') {
                    $hasValidData = true;
                }

                // Hanya generate jika ada data valid
                if ($hasValidData) {
                    $output .= "D01|" . "070200000000" . "|" . $direksitinggirendah . "\r\n";
                }
            }
        }

        foreach ($data_rasiogaji as $row) {
            // Hitung rasio pegawai
            foreach ($data_rasiogaji as $row) {
                $hasValidData = false;
                $dekomtinggirendah = isset($row['dekomtinggirendah']) && !$isEmpty($row['dekomtinggirendah']) ? $row['dekomtinggirendah'] : '';

                if ($dekomtinggirendah !== '') {
                    $hasValidData = true;
                }

                // Hanya generate jika ada data valid
                if ($hasValidData) {
                    $output .= "D01|" . "070300000000" . "|" . $dekomtinggirendah . "\r\n";
                }
            }
        }

        foreach ($data_rasiogaji as $row) {
            // Hitung rasio pegawai
            foreach ($data_rasiogaji as $row) {
                $hasValidData = false;
                $dirdekomtinggirendah = isset($row['dirdekomtinggirendah']) && !$isEmpty($row['dirdekomtinggirendah']) ? $row['dirdekomtinggirendah'] : '';

                if ($dirdekomtinggirendah !== '') {
                    $hasValidData = true;
                }

                // Hanya generate jika ada data valid
                if ($hasValidData) {
                    $output .= "D01|" . "070400000000" . "|" . $dirdekomtinggirendah . "\r\n";
                }
            }
        }

        foreach ($data_rasiogaji as $row) {
            // Hitung rasio pegawai
            foreach ($data_rasiogaji as $row) {
                $hasValidData = false;
                $dirpegawaitinggirendah = isset($row['dirpegawaitinggirendah']) && !$isEmpty($row['dirpegawaitinggirendah']) ? $row['dirpegawaitinggirendah'] : '';

                if ($dirpegawaitinggirendah !== '') {
                    $hasValidData = true;
                }

                // Hanya generate jika ada data valid
                if ($hasValidData) {
                    $output .= "D01|" . "070500000000" . "|" . $dirpegawaitinggirendah . "\r\n";
                }
            }
        }


        foreach ($data_penjelastindak as $penjelas) {
            if (!empty($penjelas['tindaklanjut']) && $penjelas['tindaklanjut'] !== null) {
                $tindaklanjut = str_replace(array("\r", "\n"), ' ', $penjelas['tindaklanjut']);
                $output .= "F01|" . $tindaklanjut . "\r\n";
            }
        }

        $filename = "LTBPRK-E0600-R-A-" . $exportDate . "1231-" . $sandibpr . "-01.txt";

        $response = service('response');
        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        return $response->setBody($output);
    }

}
