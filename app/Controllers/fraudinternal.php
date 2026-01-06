<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_fraudinternal;
use App\Models\M_infobpr;
use App\Models\M_user;
use App\Models\M_periodetransparansi;
use App\Models\M_transparansicomments;
use App\Models\M_transparansicommentsread;
use App\Models\M_penjelastindak;
use Myth\Auth\Config\Services as AuthServices;

class fraudinternal extends Controller
{
    protected $auth;
    protected $session;
    protected $userKodebpr;
    protected $userId;

    protected $fraudinternalModel;
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

    private function getFraudinternalModel()
    {
        if (!$this->fraudinternalModel) {
            $this->fraudinternalModel = new M_fraudinternal();
        }
        return $this->fraudinternalModel;
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
        $subkategori = 'Fraudinternal';

        $fraudinternalData = $this->getFraudinternalModel()
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
            'fraudinternal' => $fraudinternalData,
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

        $accdekomData = $this->fraudinternalModel
            ->select('accdekom, accdekom_by, accdekom_at')
            ->where('periode_id', $periodeId)
            ->where('kodebpr', $kodebpr)
            ->findAll();

        $accdirutData = $this->fraudinternalModel
            ->select('is_approved, approved_by, approved_at')
            ->where('periode_id', $periodeId)
            ->where('kodebpr', $kodebpr)
            ->findAll();

        $lastVisit = session('last_visit_komentar') ?? date('Y-m-d H:i:s', strtotime('-1 day'));
        session()->set('last_visit_komentar', date('Y-m-d H:i:s'));

        $canApprove = true;

        // Ambil semua data dengan kondisi yang sesuai
        $accdekomValues = $this->fraudinternalModel
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
            'judul' => '15. Jumlah Penyimpangan Internal (Internal Fraud)',
            'fraudinternal' => $indexData['fraudinternal'],
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
        echo view('fraudinternal/index', $data);
        echo view('templates/v_footer');
    }

    public function tambahfraudajax()
    {

        if (!$this->auth->check()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        if ($this->request->getMethod() === 'post') {
            // Validate the incoming data
            $validation = \Config\Services::validation();
            $val = $this->validate([
                'fraudtahunlaporandir' => 'required',
                'fraudtahunsebelumdir' => 'required',
                'selesaitahunlaporandir' => 'required',
                'prosestahunlaporandir' => 'required',
                'prosestahunsebelumdir' => 'required',
                'belumtahunlaporandir' => 'required',
                'belumtahunsebelumdir' => 'required',
                'hukumtahunlaporandir' => 'required',

                'fraudtahunlaporandekom' => 'required',
                'fraudtahunsebelumdekom' => 'required',
                'selesaitahunlaporandekom' => 'required',
                'prosestahunlaporandekom' => 'required',
                'prosestahunsebelumdekom' => 'required',
                'belumtahunlaporandekom' => 'required',
                'belumtahunsebelumdekom' => 'required',
                'hukumtahunlaporandekom' => 'required',

                'fraudtahunlaporankartap' => 'required',
                'fraudtahunsebelumkartap' => 'required',
                'selesaitahunlaporankartap' => 'required',
                'prosestahunlaporankartap' => 'required',
                'prosestahunsebelumkartap' => 'required',
                'belumtahunlaporankartap' => 'required',
                'belumtahunsebelumkartap' => 'required',
                'hukumtahunlaporankartap' => 'required',

                'fraudtahunlaporankontrak' => 'required',
                'fraudtahunsebelumkontrak' => 'required',
                'selesaitahunlaporankontrak' => 'required',
                'prosestahunlaporankontrak' => 'required',
                'prosestahunsebelumkontrak' => 'required',
                'belumtahunlaporankontrak' => 'required',
                'belumtahunsebelumkontrak' => 'required',
                'hukumtahunlaporankontrak' => 'required'
            ]);

            if (!$val) {
                return $this->response->setJSON(['status' => 'error', 'message' => $validation->listErrors()]);
            } else {
                // Prepare the data to be inserted
                $userId = $this->auth->id();
                $kodebpr = $this->userKodebpr;
                $periodeId = session('active_periode');

                // PERBAIKAN: Gunakan method getter untuk model
                $existingData = $this->getFraudinternalModel()->where(['kodebpr' => $kodebpr, 'periode_id' => $periodeId])->first();

                $data = [
                    'fraudtahunlaporandir' => $this->request->getPost('fraudtahunlaporandir'),
                    'fraudtahunsebelumdir' => $this->request->getPost('fraudtahunsebelumdir'),
                    'selesaitahunlaporandir' => $this->request->getPost('selesaitahunlaporandir'),
                    'prosestahunlaporandir' => $this->request->getPost('prosestahunlaporandir'),
                    'prosestahunsebelumdir' => $this->request->getPost('prosestahunsebelumdir'),
                    'belumtahunlaporandir' => $this->request->getPost('belumtahunlaporandir'),
                    'belumtahunsebelumdir' => $this->request->getPost('belumtahunsebelumdir'),
                    'hukumtahunlaporandir' => $this->request->getPost('hukumtahunlaporandir'),
                    'fraudtahunlaporandekom' => $this->request->getPost('fraudtahunlaporandekom'),
                    'fraudtahunsebelumdekom' => $this->request->getPost('fraudtahunsebelumdekom'),
                    'selesaitahunlaporandekom' => $this->request->getPost('selesaitahunlaporandekom'),

                    'prosestahunlaporandekom' => $this->request->getPost('prosestahunlaporandekom'),
                    'prosestahunsebelumdekom' => $this->request->getPost('prosestahunsebelumdekom'),
                    'belumtahunlaporandekom' => $this->request->getPost('belumtahunlaporandekom'),
                    'belumtahunsebelumdekom' => $this->request->getPost('belumtahunsebelumdekom'),
                    'hukumtahunlaporandekom' => $this->request->getPost('hukumtahunlaporandekom'),
                    'fraudtahunlaporankartap' => $this->request->getPost('fraudtahunlaporankartap'),
                    'fraudtahunsebelumkartap' => $this->request->getPost('fraudtahunsebelumkartap'),
                    'selesaitahunlaporankartap' => $this->request->getPost('selesaitahunlaporankartap'),
                    'prosestahunlaporankartap' => $this->request->getPost('prosestahunlaporankartap'),
                    'prosestahunsebelumkartap' => $this->request->getPost('prosestahunsebelumkartap'),
                    'belumtahunlaporankartap' => $this->request->getPost('belumtahunlaporankartap'),

                    'belumtahunsebelumkartap' => $this->request->getPost('belumtahunsebelumkartap'),
                    'hukumtahunlaporankartap' => $this->request->getPost('hukumtahunlaporankartap'),
                    'fraudtahunlaporankontrak' => $this->request->getPost('fraudtahunlaporankontrak'),
                    'fraudtahunsebelumkontrak' => $this->request->getPost('fraudtahunsebelumkontrak'),
                    'selesaitahunlaporankontrak' => $this->request->getPost('selesaitahunlaporankontrak'),
                    'prosestahunlaporankontrak' => $this->request->getPost('prosestahunlaporankontrak'),
                    'prosestahunsebelumkontrak' => $this->request->getPost('prosestahunsebelumkontrak'),
                    'belumtahunlaporankontrak' => $this->request->getPost('belumtahunlaporankontrak'),
                    'belumtahunsebelumkontrak' => $this->request->getPost('belumtahunsebelumkontrak'),
                    'hukumtahunlaporankontrak' => $this->request->getPost('hukumtahunlaporankontrak'),

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

                    $result = $this->getFraudinternalModel()->save($data);

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
                    unset($data['id']);

                    $result = $this->getFraudinternalModel()->insert($data);

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
        $subkategori = 'Fraudinternal';
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
        $subkategori = 'Fraudinternal';
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
            return redirect()->to(base_url('Fraudinternal'));
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
            'subkategori' => 'Fraudinternal',
            'komentar' => $this->request->getPost('komentar'),
            'fullname' => $this->request->getPost('fullname'),
            'user_id' => $this->userId,
            'kodebpr' => $this->userKodebpr,
            'periode_id' => session('active_periode'),
            'created_at' => date('Y-m-d H:i:s')
        ];

        $this->getKomentarModel()->insertKomentar($data);
        session()->setFlashdata('message', 'Komentar berhasil ditambahkan');
        return redirect()->to(base_url('Fraudinternal') . '?modal_komentar=' . $this->request->getPost('id'));
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
            return redirect()->to(base_url('Fraudinternal'));
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
            'subkategori' => 'Fraudinternal',
            'tindaklanjut' => $this->request->getPost('tindaklanjut'),
            'kodebpr' => $kodebpr,
            'periode_id' => $periodeId,
            'fullname' => $this->userData['fullname'] ?? null,
            'user_id' => $this->userId,
        ];

        $this->getPenjelastindakModel()->tambahpenjelastindak($penjelastindak);
        session()->setFlashdata('message', 'Data berhasil diubah');

        return redirect()->to(base_url('Fraudinternal'));
    }

    public function editketerangan()
    {
        $id = $this->request->getPost('id');
        $subkategori = 'Fraudinternal';
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

        return redirect()->to(base_url('Fraudinternal'));
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
                    'fraudinternal' => $this->fraudinternalModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('fraudinternal/index', $data);
                echo view('templates/v_footer');

            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'keterangan' => $this->request->getPost('keterangan')
                ];

                // Update data
                $success = $this->fraudinternalModel->ubah($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Tindak Lanjut Direksi berhasil diubah ');
                    return redirect()->to(base_url('fraudinternal'));
                }
            }
        } else {
            return redirect()->to(base_url('Fraudinternal'));
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

        $this->fraudinternalModel = new M_fraudinternal();

        $this->fraudinternalModel->builder()
            ->where('id', $id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->delete();

        session()->setFlashdata('message', 'Data berhasil dihapus');
        return redirect()->to(base_url('Fraudinternal'));
    }

    private function updateApprovalStatus($id, $isApproved, $successMessage, $errorMessage)
    {
        if (!is_numeric($id) || $id <= 0) {
            session()->setFlashdata('err', 'ID tidak valid.');
            return redirect()->back();
        }

        $data = $this->getFraudinternalModel()->find($id);
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

        if ($this->getFraudinternalModel()->save($dataUpdate)) {
            session()->setFlashdata('message', $successMessage);
        } else {
            session()->setFlashdata('err', $errorMessage);
        }

        return redirect()->back();
    }

    public function approve($idfraudinternal)
    {
        return $this->updateApprovalStatus(
            $idfraudinternal,
            1,
            'Data berhasil disetujui.',
            'Terjadi kesalahan saat melakukan approval.'
        );
    }

    public function unapprove($idfraudinternal)
    {
        return $this->updateApprovalStatus(
            $idfraudinternal,
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

        $count = $this->getFraudinternalModel()
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

        if ($isApproved) {
            if ($field === 'is_approved') {
                $dataUpdate['approved_at'] = $currentTimestamp;
            } elseif ($field === 'accdekom') {
                $dataUpdate['accdekom_at'] = $currentTimestamp;
            }
        } else {
            if ($field === 'is_approved') {
                $dataUpdate['approved_at'] = null;
            } elseif ($field === 'accdekom') {
                $dataUpdate['accdekom_at'] = null;
            }
        }

        try {
            $updated = $this->getFraudinternalModel()
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

        $data = $this->getFraudinternalModel()->find($id);
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

        if ($this->getFraudinternalModel()->save($dataUpdate)) {
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

        $result = $this->getFraudinternalModel()->setNullKolomTindak($id);

        if ($result) {
            session()->setFlashdata('message', 'Data berhasil dihapus');
        } else {
            session()->setFlashdata('err', 'Data gagal dihapus');
        }

        return redirect()->to(base_url('Fraudinternal'));
    }

    public function exporttxtfraudinternal()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $this->fraudinternalModel = model('M_fraudinternal');
        $this->infobprModel = model('M_infobpr');
        $this->penjelastindakModel = model('M_penjelastindak');

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');
        $subkategori = "Fraudinternal";

        $periodeDetail = $this->getPeriodeModel()->getPeriodeDetail($periodeId);
        $exportDate = $periodeDetail['tahun'] ?? date('Y');

        $data_fraudinternal = $this->fraudinternalModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);

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

        $output = "";
        $output .= "H01|" . $kodejenis . "|" . $sandibpr . "|" . $exportDate . "-12-31|LTBPRK|E0800|0|" . "\r\n";

        foreach ($data_fraudinternal as $row) {
            $hasValidData = false;
            $fraudtahunsebelumdir = isset($row['fraudtahunsebelumdir']) && !$isEmpty($row['fraudtahunsebelumdir']) ? $row['fraudtahunsebelumdir'] : '';
            $fraudtahunlaporandir = isset($row['fraudtahunlaporandir']) && !$isEmpty($row['fraudtahunlaporandir']) ? $row['fraudtahunlaporandir'] : '';
            $fraudtahunsebelumdekom = isset($row['fraudtahunsebelumdekom']) && !$isEmpty($row['fraudtahunsebelumdekom']) ? $row['fraudtahunsebelumdekom'] : '';
            $fraudtahunlaporandekom = isset($row['fraudtahunlaporandekom']) && !$isEmpty($row['fraudtahunlaporandekom']) ? $row['fraudtahunlaporandekom'] : '';
            $fraudtahunsebelumkartap = isset($row['fraudtahunsebelumkartap']) && !$isEmpty($row['fraudtahunsebelumkartap']) ? $row['fraudtahunsebelumkartap'] : '';
            $fraudtahunlaporankartap = isset($row['fraudtahunlaporankartap']) && !$isEmpty($row['fraudtahunlaporankartap']) ? $row['fraudtahunlaporankartap'] : '';
            $fraudtahunsebelumkontrak = isset($row['fraudtahunsebelumkontrak']) && !$isEmpty($row['fraudtahunsebelumkontrak']) ? $row['fraudtahunsebelumkontrak'] : '';
            $fraudtahunlaporankontrak = isset($row['fraudtahunlaporankontrak']) && !$isEmpty($row['fraudtahunlaporankontrak']) ? $row['fraudtahunlaporankontrak'] : '';

            if ($fraudtahunsebelumdir !== '' || $fraudtahunlaporandir !== '' || $fraudtahunsebelumdekom !== '' || $fraudtahunlaporandekom !== '' || $fraudtahunsebelumkartap !== '' || $fraudtahunlaporankartap !== '' || $fraudtahunsebelumkontrak !== '' || $fraudtahunlaporankontrak !== '') {
                $hasValidData = true;
            }

            // Hanya generate jika ada data valid
            if ($hasValidData) {
                $output .= "D01|" . "910" . "|" . $fraudtahunsebelumdir . "|" . $fraudtahunlaporandir . "|" . $fraudtahunsebelumdekom . "|" . $fraudtahunlaporandekom . "|" . $fraudtahunsebelumkartap . "|" . $fraudtahunlaporankartap . "|" . $fraudtahunsebelumkontrak . "|" . $fraudtahunlaporankontrak . "\r\n";
            }
        }

        foreach ($data_fraudinternal as $row) {
            $hasValidData = false;
            $selesaitahunlaporandir = isset($row['selesaitahunlaporandir']) && !$isEmpty($row['selesaitahunlaporandir']) ? $row['selesaitahunlaporandir'] : '';
            $selesaitahunlaporandekom = isset($row['selesaitahunlaporandekom']) && !$isEmpty($row['selesaitahunlaporandekom']) ? $row['selesaitahunlaporandekom'] : '';
            $selesaitahunlaporankartap = isset($row['selesaitahunlaporankartap']) && !$isEmpty($row['selesaitahunlaporankartap']) ? $row['selesaitahunlaporankartap'] : '';
            $selesaitahunlaporankontrak = isset($row['selesaitahunlaporankontrak']) && !$isEmpty($row['selesaitahunlaporankontrak']) ? $row['selesaitahunlaporankontrak'] : '';

            if ($selesaitahunlaporandir !== '' || $selesaitahunlaporandekom !== '' || $selesaitahunlaporankartap !== '' || $selesaitahunlaporankontrak) {
                $hasValidData = true;
            }

            // Hanya generate jika ada data valid
            if ($hasValidData) {
                $output .= "D01|" . "911" . "|" . "|" . $selesaitahunlaporandekom . "|" . "|" . $selesaitahunlaporandekom . "|" . "|" . $selesaitahunlaporankartap . "|" . "|" . $selesaitahunlaporankontrak . "\r\n";
            }
        }

        foreach ($data_fraudinternal as $row) {
            $hasValidData = false;
            $prosestahunsebelumdir = isset($row['prosestahunsebelumdir']) && !$isEmpty($row['prosestahunsebelumdir']) ? $row['prosestahunsebelumdir'] : '';
            $prosestahunlaporandir = isset($row['prosestahunlaporandir']) && !$isEmpty($row['prosestahunlaporandir']) ? $row['prosestahunlaporandir'] : '';
            $prosestahunsebelumdekom = isset($row['prosestahunsebelumdekom']) && !$isEmpty($row['prosestahunsebelumdekom']) ? $row['prosestahunsebelumdekom'] : '';
            $prosestahunlaporandekom = isset($row['prosestahunlaporandekom']) && !$isEmpty($row['prosestahunlaporandekom']) ? $row['prosestahunlaporandekom'] : '';
            $prosestahunsebelumkartap = isset($row['prosestahunsebelumkartap']) && !$isEmpty($row['prosestahunsebelumkartap']) ? $row['prosestahunsebelumkartap'] : '';
            $prosestahunlaporankartap = isset($row['prosestahunlaporankartap']) && !$isEmpty($row['prosestahunlaporankartap']) ? $row['prosestahunlaporankartap'] : '';
            $prosestahunsebelumkontrak = isset($row['prosestahunsebelumkontrak']) && !$isEmpty($row['prosestahunsebelumkontrak']) ? $row['prosestahunsebelumkontrak'] : '';
            $prosestahunlaporankontrak = isset($row['prosestahunlaporankontrak']) && !$isEmpty($row['prosestahunlaporankontrak']) ? $row['prosestahunlaporankontrak'] : '';

            if ($prosestahunsebelumdir !== '' || $prosestahunlaporandir !== '' || $prosestahunsebelumdekom !== '' || $prosestahunlaporandekom !== '' || $prosestahunsebelumkartap !== '' || $prosestahunlaporankartap !== '' || $prosestahunsebelumkontrak !== '' || $prosestahunlaporankontrak !== '') {
                $hasValidData = true;
            }

            // Hanya generate jika ada data valid
            if ($hasValidData) {
                $output .= "D01|" . "912" . "|" . $prosestahunsebelumdir . "|" . $prosestahunlaporandir . "|" . $prosestahunsebelumdekom . "|" . $prosestahunlaporandekom . "|" . $prosestahunsebelumkartap . "|" . $prosestahunlaporankartap . "|" . $prosestahunsebelumkontrak . "|" . $prosestahunlaporankontrak . "\r\n";
            }
        }

        // Generate D01 - Unique non-empty belum values (913)
        foreach ($data_fraudinternal as $row) {
            $hasValidData = false;
            $belumtahunsebelumdir = isset($row['belumtahunsebelumdir']) && !$isEmpty($row['belumtahunsebelumdir']) ? $row['belumtahunsebelumdir'] : '';
            $belumtahunlaporandir = isset($row['belumtahunlaporandir']) && !$isEmpty($row['belumtahunlaporandir']) ? $row['belumtahunlaporandir'] : '';
            $belumtahunsebelumdekom = isset($row['belumtahunsebelumdekom']) && !$isEmpty($row['belumtahunsebelumdekom']) ? $row['belumtahunsebelumdekom'] : '';
            $belumtahunlaporandekom = isset($row['belumtahunlaporandekom']) && !$isEmpty($row['belumtahunlaporandekom']) ? $row['belumtahunlaporandekom'] : '';
            $belumtahunsebelumkartap = isset($row['belumtahunsebelumkartap']) && !$isEmpty($row['belumtahunsebelumkartap']) ? $row['belumtahunsebelumkartap'] : '';
            $belumtahunlaporankartap = isset($row['belumtahunlaporankartap']) && !$isEmpty($row['belumtahunlaporankartap']) ? $row['belumtahunlaporankartap'] : '';
            $belumtahunsebelumkontrak = isset($row['belumtahunsebelumkontrak']) && !$isEmpty($row['belumtahunsebelumkontrak']) ? $row['belumtahunsebelumkontrak'] : '';
            $belumtahunlaporankontrak = isset($row['belumtahunlaporankontrak']) && !$isEmpty($row['belumtahunlaporankontrak']) ? $row['belumtahunlaporankontrak'] : '';

            if ($belumtahunsebelumdir !== '' || $belumtahunlaporandir !== '' || $belumtahunsebelumdekom !== '' || $belumtahunlaporandekom !== '' || $belumtahunsebelumkartap !== '' || $belumtahunlaporankartap !== '' || $belumtahunsebelumkontrak !== '' || $belumtahunlaporankontrak !== '') {
                $hasValidData = true;
            }

            // Hanya generate jika ada data valid
            if ($hasValidData) {
                $output .= "D01|" . "913" . "|" . $belumtahunsebelumdir . "|" . $belumtahunlaporandir . "|" . $belumtahunsebelumdekom . "|" . $belumtahunlaporandekom . "|" . $belumtahunsebelumkartap . "|" . $belumtahunlaporankartap . "|" . $belumtahunsebelumkontrak . "|" . $belumtahunlaporankontrak . "\r\n";
            }
        }

        foreach ($data_fraudinternal as $row) {
            $hasValidData = false;
            $hukumtahunlaporandir = isset($row['hukumtahunlaporandir']) && !$isEmpty($row['hukumtahunlaporandir']) ? $row['hukumtahunlaporandir'] : '';
            $hukumtahunlaporandekom = isset($row['hukumtahunlaporandekom']) && !$isEmpty($row['hukumtahunlaporandekom']) ? $row['hukumtahunlaporandekom'] : '';
            $hukumtahunlaporankartap = isset($row['hukumtahunlaporankartap']) && !$isEmpty($row['hukumtahunlaporankartap']) ? $row['hukumtahunlaporankartap'] : '';
            $hukumtahunlaporankontrak = isset($row['hukumtahunlaporankontrak']) && !$isEmpty($row['hukumtahunlaporankontrak']) ? $row['hukumtahunlaporankontrak'] : '';

            if ($hukumtahunlaporandir !== '' || $hukumtahunlaporandekom !== '' || $hukumtahunlaporankartap !== '' || $hukumtahunlaporankontrak) {
                $hasValidData = true;
            }

            // Hanya generate jika ada data valid
            if ($hasValidData) {
                $output .= "D01|" . "920" . "|" . "|" . $hukumtahunlaporandir . "|" . "|" . $hukumtahunlaporandekom . "|" . "|" . $hukumtahunlaporankartap . "|" . "|" . $hukumtahunlaporankontrak . "\r\n";
            }
        }


        foreach ($data_penjelastindak as $penjelas) {
            if (!empty($penjelas['tindaklanjut']) && $penjelas['tindaklanjut'] !== null) {
                $tindaklanjut = str_replace(array("\r", "\n"), ' ', $penjelas['tindaklanjut']);
                $output .= "F01|" . $tindaklanjut . "\r\n";
            }
        }


        $filename = "LTBPRK-E0800-R-A-" . $exportDate . "1231-" . $sandibpr . "-01.txt";

        $response = service('response');
        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        return $response->setBody($output);
    }

    public function exportAllToZip()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $zip = new \ZipArchive();
        $zipFileName = 'APOLO-NBP-LAPORANGCG-' . date('Y-m-d') . '.zip';
        $zipFilePath = WRITEPATH . 'uploads/' . $zipFileName;

        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
            $this->addTxtToZip('penjelasanumum', 'exporttxtpenjelasanumum', $zip);
            $this->addTxtToZip('tgjwbdir', 'exporttxttgjwbdir', $zip);
            $this->addTxtToZip('tgjwbdekom', 'exporttxttgjwbdekom', $zip);
            $this->addTxtToZip('tgjwbkomite', 'exporttxttgjwbkomite', $zip);
            $this->addTxtToZip('strukturkomite', 'exporttxtstrukturkomite', $zip);
            $this->addTxtToZip('sahamdirdekom', 'exporttxtsahamdirdekom', $zip);
            $this->addTxtToZip('shmusahadirdekom', 'exporttxtshmusahadirdekom', $zip);
            $this->addTxtToZip('shmdirdekomlain', 'exporttxtshmdirdekomlain', $zip);
            $this->addTxtToZip('keuangandirdekompshm', 'exporttxtkeuangandirdekompshm', $zip);
            $this->addTxtToZip('keluargadirdekompshm', 'exporttxtkeluargadirdekompshm', $zip);
            $this->addTxtToZip('remunlaindirdekom', 'exporttxtremunlaindirdekom', $zip);
            $this->addTxtToZip('rasiogaji', 'exporttxtrasiogaji', $zip);
            $this->addTxtToZip('rapat', 'exporttxtrapat', $zip);
            $this->addTxtToZip('kehadirandekom', 'exporttxtkehadirandekom', $zip);
            $this->addTxtToZip('fraudinternal', 'exporttxtfraudinternal', $zip);
            $this->addTxtToZip('masalahhukum', 'exporttxtmasalahhukum', $zip);
            $this->addTxtToZip('transaksikepentingan', 'exporttxttransaksikepentingan', $zip);
            $this->addTxtToZip('danasosial', 'exporttxtdanasosial', $zip);

            $zip->close();

            $this->response->setHeader('Content-Type', 'application/zip');
            $this->response->setHeader('Content-Disposition', 'attachment; filename="' . $zipFileName . '"');
            $this->response->setHeader('Content-Length', filesize($zipFilePath));

            readfile($zipFilePath);

            unlink($zipFilePath);
        } else {
            echo 'Gagal membuat file ZIP';
        }
    }

    private function addTxtToZip(string $controllerName, string $methodName, \ZipArchive &$zip)
    {
        $controllerClassName = 'App\Controllers\\' . ucfirst($controllerName);
        if (class_exists($controllerClassName)) {
            $controllerInstance = new $controllerClassName();
            if (method_exists($controllerInstance, $methodName)) {
                $response = $controllerInstance->$methodName();
                if ($response instanceof \CodeIgniter\HTTP\Response) {
                    $txtContent = $response->getBody();

                    $contentDisposition = $response->getHeaderLine('Content-Disposition');
                    $fileNameInZip = $controllerName . '.txt';

                    if (preg_match('/filename="([^"]+)"/', $contentDisposition, $matches)) {
                        $fileNameInZip = $matches[1];
                    }

                    $zip->addFromString($fileNameInZip, $txtContent);
                } else {
                    log_message('warning', "Method '$methodName' di controller '$controllerName' tidak mengembalikan objek Response yang valid.");
                }
            } else {
                log_message('warning', "Method '$methodName' tidak ditemukan di controller '$controllerName'");
            }
        } else {
            log_message('warning', "Controller '$controllerName' tidak ditemukan");
        }
    }

}


