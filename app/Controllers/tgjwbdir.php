<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_tgjwbdir;
use App\Models\M_infobpr;
use App\Models\M_user;
use App\Models\M_periodetransparansi;
use App\Models\M_transparansicomments;
use App\Models\M_transparansicommentsread;
use App\Models\M_penjelastindak;
use Myth\Auth\Config\Services as AuthServices;

class tgjwbdir extends Controller
{
    protected $auth;
    protected $tgjwbdirModel;
    protected $userModel;
    protected $komentarModel;
    protected $infobprModel;
    protected $periodeModel;
    protected $session;
    protected $userKodebpr;
    protected $commentReadsModel;
    protected $penjelastindakModel;

    protected $userInGroupPE;
    protected $userInGroupAdmin;
    protected $userInGroupDekom;
    protected $userInGroupDekom2;
    protected $userInGroupDekom3;
    protected $userInGroupDekom4;
    protected $userInGroupDekom5;
    protected $userInGroupDireksi;
    protected $userInGroupDireksi2;

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->tgjwbdirModel = new M_tgjwbdir();
        $this->periodeModel = new M_periodetransparansi();
        $this->userModel = new M_user();
        $this->komentarModel = new M_transparansicomments();
        $this->commentReadsModel = new M_transparansicommentsread();
        $this->infobprModel = new M_infobpr();
        $this->penjelastindakModel = new M_penjelastindak();
        $this->session = service('session');
        $this->auth = service('authentication');

        $userId = $this->auth->id();
        $user = $this->userModel->find($userId);
        $this->userKodebpr = $user['kodebpr'] ?? null;

        $auth = AuthServices::authentication();
        $authorize = AuthServices::authorization();

        $this->userInGroupPE = $authorize->inGroup('pe', $this->auth->id());
        $this->userInGroupAdmin = $authorize->inGroup('admin', $this->auth->id());
        $this->userInGroupDekom = $authorize->inGroup('dekom', $this->auth->id());
        $this->userInGroupDekom2 = $authorize->inGroup('dekom2', $this->auth->id());
        $this->userInGroupDekom3 = $authorize->inGroup('dekom3', $this->auth->id());
        $this->userInGroupDekom4 = $authorize->inGroup('dekom4', $this->auth->id());
        $this->userInGroupDekom5 = $authorize->inGroup('dekom5', $this->auth->id());
        $this->userInGroupDireksi = $authorize->inGroup('direksi', $this->auth->id());
        $this->userInGroupDireksi2 = $authorize->inGroup('direksi2', $this->auth->id());
    }

    public function index()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        if (!session('active_periode')) {
            return redirect()->to('/Periodetransparansi');
        }

        $user = $this->userModel->find(user_id());
        $periodeId = session('active_periode');
        $periodeDetail = $this->periodeModel->getPeriodeDetail($periodeId);
        $kodebpr = $this->userKodebpr;
        $subkategori = 'Tgjwbdir';
        $bprData = $this->infobprModel->getBprByKode($this->userKodebpr);

        $tgjwbdirData = $this->tgjwbdirModel
            ->where('periode_id', $periodeId)
            ->where('kodebpr', $kodebpr)
            ->limit(10)
            ->findAll();

        $data['periodetransparansi'] = $this->periodeModel->find($periodeId);

        // Mengambil data accdekom, accdekom_by, accdekom_at
        $accdekomData = $this->tgjwbdirModel
            ->select('accdekom, accdekom_by, accdekom_at, komut')
            ->where('periode_id', $periodeId)
            ->where('kodebpr', $kodebpr)
            ->findAll();

        $accdirutData = $this->tgjwbdirModel
            ->select('is_approved, approved_by, approved_at, dirut')
            ->where('periode_id', $periodeId)
            ->where('kodebpr', $kodebpr)
            ->findAll();

        $komentarList = $this->komentarModel
            ->where('subkategori', @$subkategori)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->findAll();

        $fullname = $user['fullname'] ?? 'Unknown';
        $kodebpr = $user['kodebpr'] ?? null;

        if (!$kodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
        }

        $lastVisit = session('last_visit_komentar') ?? date('Y-m-d H:i:s', strtotime('-1 day'));
        session()->set('last_visit_komentar', date('Y-m-d H:i:s'));

        $penjelastindak = $this->penjelastindakModel->getDataPenjelasByKodebprAndPeriode($subkategori, $kodebpr, $periodeId);

        $canApprove = true;

        // Ambil semua data dengan kondisi yang sesuai
        $accdekomValues = $this->tgjwbdirModel
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
            'judul' => '2. Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi',
            'tgjwbdir' => $tgjwbdirData,
            'userId' => user_id(),
            'komentarList' => $komentarList,
            'userInGroupPE' => $this->userInGroupPE,
            'userInGroupAdmin' => $this->userInGroupAdmin,
            'userInGroupDekom' => $this->userInGroupDekom,
            'userInGroupDekom2' => $this->userInGroupDekom2,
            'userInGroupDekom3' => $this->userInGroupDekom3,
            'userInGroupDekom4' => $this->userInGroupDekom4,
            'userInGroupDekom5' => $this->userInGroupDekom5,
            'userInGroupDireksi' => $this->userInGroupDireksi,
            'userInGroupDireksi2' => $this->userInGroupDireksi2,
            'fullname' => $fullname,
            'kodebpr' => $this->userKodebpr,
            'komentarModel' => $this->komentarModel,
            'commentReadsModel' => $this->commentReadsModel,
            'lastVisit' => $lastVisit,
            'periodeId' => $periodeId,
            'periodeDetail' => $periodeDetail,
            'bprData' => $bprData,
            'accdekomData' => $accdekomData,
            'accdirutData' => $accdirutData,
            'penjelastindak' => $penjelastindak,
            'canApprove' => $canApprove,

        ];

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('tgjwbdir/index', $data);
        echo view('templates/v_footer');
    }

    public function tambahtgjwbdir()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        if (isset($_POST['tambahtgjwbdir'])) {
            $val = $this->validate([
                'direksi' => [
                    'label' => 'Nama Direksi',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nik' => [
                    'label' => 'NIK',
                    'rules' => 'required|numeric|min_length[16]|max_length[16]',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.',
                        'numeric' => '{field} harus berupa angka.',
                        'min_length' => '{field} harus memiliki panjang minimal 16 karakter.',
                        'max_length' => '{field} harus memiliki panjang maksimal 16 karakter.'
                    ]
                ],
                'tugastgjwbdir' => [
                    'label' => 'Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                return redirect()->back();
            } else {
                $periodeId = session('active_periode');
                $userId = $this->auth->id();
                $user = $this->userModel->find($userId);
                $kodebpr = $user['kodebpr'] ?? null;
                $fullname = $user['fullname'] ?? null;

                if (!$kodebpr) {
                    return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
                }

                $data = [
                    'nik' => $this->request->getPost('nik'),
                    'direksi' => $this->request->getPost('direksi'),
                    'tugastgjwbdir' => $this->request->getPost('tugastgjwbdir'),
                    // 'tindakdir' => $this->request->getPost('tindakdir'),
                    'periode_id' => $periodeId,
                    'user_id' => $userId,
                    'kodebpr' => $kodebpr,
                    'fullname' => $fullname,
                    'accdekom' => 0,
                    'is_approved' => 0,
                ];

                // Insert data
                // $this->tgjwbdirModel->checkIncrement();
                $success = $this->tgjwbdirModel->tambahtgjwbdir($data);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil ditambahkan ');
                    return redirect()->to(base_url('Tgjwbdir'));
                }
            }
        } else {
            return redirect()->to(base_url('Tgjwbdir'));
        }
    }

    public function ubah()
    {
        $id = $this->request->getPost('id');
        $userId = service('authentication')->id();
        $userModel = new M_user();
        $user = $userModel->find($userId);
        $kodebpr = $user['kodebpr'] ?? null;

        if (!$kodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
        }

        $periodeId = session('active_periode');
        if (!$periodeId) {
            return redirect()->back()->with('error', 'Periode tidak valid');
        }

        // Get form data
        $direksi = $this->request->getPost('direksi');
        $nik = $this->request->getPost('nik');
        $tugastgjwbdir = $this->request->getPost('tugastgjwbdir');

        if (empty($direksi) || empty($nik) || empty($tugastgjwbdir)) {
            return redirect()->back()->with('error', 'Tindak Lanjut atau Penjelasan tidak boleh kosong');
        }

        // Prepare data for update
        $data = [
            'nik' => $nik,
            'direksi' => $direksi,
            'tugastgjwbdir' => $tugastgjwbdir,
            'user_id' => $userId,
            'kodebpr' => $kodebpr,
            'accdekom' => 0,
            'is_approved' => 0
        ];

        // Attempt to update the record
        if ($this->tgjwbdirModel->editbasedkodedanperiode($data, $kodebpr, $periodeId, $id)) {
            session()->setFlashdata('message', 'Data berhasil diubah');
        } else {
            session()->setFlashdata('err', 'Gagal mengubah data');
        }
        return redirect()->to(base_url('Tgjwbdir'));
    }

    public function getUnreadCommentCountForFactor()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $Id = $this->request->getGet('id');
        $kodebpr = $this->userKodebpr;
        $userId = user_id();
        $periodeId = session('active_periode');

        if (!$Id || !$kodebpr || !$userId || !$periodeId) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Missing data.']);
        }

        $count = $this->commentReadsModel->countUnreadCommentsForUserByFactor($Id, $kodebpr, $userId, $periodeId);

        return $this->response->setJSON(['unread_count' => $count]);
    }

    public function cekKomentarBaru()
    {
        $subkategori = 'Tgjwbdir';
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
        $subkategori = 'Tgjwbdir';
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        $komentarList = $this->komentarModel->getKomentarByFaktorId($subkategori, $kodebpr, $periodeId);

        return $this->response->setJSON($komentarList);
    }

    public function Tambahkomentar()
    {
        if (!$this->auth->check()) {
            return redirect()->to('/login');
        }

        if (isset($_POST['TambahKomentar'])) {
            $userId = service('authentication')->id();
            $user = $this->userModel->find($userId);
            $kodebpr = $user['kodebpr'] ?? null;

            if (!$kodebpr) {
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
                'subkategori' => 'Tgjwbdir',
                'komentar' => $this->request->getPost('komentar'),
                'fullname' => $this->request->getPost('fullname'),
                'user_id' => $userId,
                'kodebpr' => $kodebpr,
                'periode_id' => session('active_periode'), // Pastikan ini diisi
                'created_at' => date('Y-m-d H:i:s')
            ];

            $this->komentarModel->insertKomentar($data);
            session()->setFlashdata('message', 'Komentar berhasil ditambahkan');
            return redirect()->to(base_url('Tgjwbdir') . '?modal_komentar=' . $this->request->getPost('id'));
        }

        return redirect()->to(base_url('Tgjwbdir'));
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
        $commentsToMark = $this->komentarModel->select('id')
            ->where('subkategori', $Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('user_id !=', $userId) // Mark comments from others as read
            ->findAll();

        if (!empty($commentsToMark)) {
            foreach ($commentsToMark as $comment) {
                $this->commentReadsModel->markAsRead($comment['id'], $userId);
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
            'is_read' => 0, // <--- Ensure this is set to 0 for new comments
            'created_at' => date('Y-m-d H:i:s'),
            'user_id' => session()->get('user_id')
        ];

        $this->komentarModel->insert($data);
        return $this->response->setJSON(['status' => 'comment_saved']);
    }

    public function tambahketerangan()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        if (isset($_POST['tambahketerangan'])) {
            $val = $this->validate([
                'tindaklanjut' => [
                    'label' => 'Tindak Lanjut Tugas dan Tanggung Jawab Anggota Direksi',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                return redirect()->back()->withInput();
            } else {
                $periodeId = session('active_periode');
                $userId = $this->auth->id();
                $userModel = new M_user();
                $user = $userModel->find($userId);
                $kodebpr = $user['kodebpr'] ?? null;
                $fullname = $user['fullname'] ?? null;

                if (!$kodebpr) {
                    return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
                }

                // Check if data exists for the given kodebpr and periode_id
                $existingData = $this->tgjwbdirModel->where([
                    'kodebpr' => $kodebpr,
                    'periode_id' => $periodeId
                ])->first();

                $penjelastindak = [
                    'subkategori' => 'Tgjwbdir',
                    'tindaklanjut' => $this->request->getPost('tindaklanjut'),
                    'penjelasanlanjut' => $this->request->getPost('penjelasanlanjut'),
                    'kodebpr' => $kodebpr,
                    'periode_id' => $periodeId,
                    'fullname' => $fullname
                ];

                // Insert data into penjelastindak table
                $this->penjelastindakModel->tambahpenjelastindak($penjelastindak);
                session()->setFlashdata('message', 'Data berhasil diubah');

                return redirect()->to(base_url('Tgjwbdir'));
            }
        } else {
            return redirect()->to(base_url('Tgjwbdir'));
        }
    }

    public function editketerangan()
    {
        $id = $this->request->getPost('id');
        $userId = service('authentication')->id();
        $userModel = new M_user();
        $subkategori = 'Tgjwbdir';
        $user = $userModel->find($userId);
        $kodebpr = $user['kodebpr'] ?? null;

        if (!$kodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
        }

        $periodeId = session('active_periode');
        if (!$periodeId) {
            return redirect()->back()->with('error', 'Periode tidak valid');
        }

        // Get form data
        $tindaklanjut = $this->request->getPost('tindaklanjut');
        $penjelasanlanjut = $this->request->getPost('penjelasanlanjut');

        if (empty($tindaklanjut) || empty($penjelasanlanjut)) {
            return redirect()->back()->with('error', 'Tindak Lanjut atau Penjelasan tidak boleh kosong');
        }

        // Prepare data for update
        $data = [
            'tindaklanjut' => $tindaklanjut,
            'penjelasanlanjut' => $penjelasanlanjut,
            'user_id' => $userId,
            'kodebpr' => $kodebpr,
        ];

        // Attempt to update the record
        if ($this->penjelastindakModel->editberdasarkankodedanperiode($data, $subkategori, $kodebpr, $periodeId)) {
            session()->setFlashdata('message', 'Data berhasil diubah');
        } else {
            session()->setFlashdata('err', 'Gagal mengubah data');
        }

        return redirect()->to(base_url('Tgjwbdir'));
    }

    public function hapus($id, $kodebpr, $periode)
    {
        // Check if the user is authenticated
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()->to($redirectURL);
        }

        // Call the hapus function in the model and store the result in $success
        $success = $this->tgjwbdirModel->hapus($id, $kodebpr, $periode);
        if ($success) {
            session()->setFlashdata('message', 'Data berhasil dihapus');
        } else {
            session()->setFlashdata('message', 'Data gagal dihapus');
        }


        // Redirect back to the Tgjwbdir page
        return redirect()->to(base_url('Tgjwbdir'));
    }



    public function approve($idTgjwbdir)
    {
        if (!is_numeric($idTgjwbdir) || $idTgjwbdir <= 0) {
            session()->setFlashdata('err', 'ID Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi tidak valid.');
            return redirect()->back();
        }

        $tgjwbdir = $this->tgjwbdirModel->find($idTgjwbdir);
        if (!$tgjwbdir) {
            session()->setFlashdata('err', 'Data Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idTgjwbdir,
            'is_approved' => 1,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->tgjwbdirModel->save($dataUpdate)) {
            session()->setFlashdata('message', 'Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi berhasil disetujui.');
            return redirect()->back();
        } else {
            session()->setFlashdata('err', 'Terjadi kesalahan saat melakukan approval.');
            return redirect()->back();
        }
    }

    public function unapprove($idTgjwbdir)
    {
        if (!is_numeric($idTgjwbdir) || $idTgjwbdir <= 0) {
            session()->setFlashdata('err', 'ID Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi tidak valid.');
            return redirect()->back();
        }

        $tgjwbdir = $this->tgjwbdirModel->find($idTgjwbdir);
        if (!$tgjwbdir) {
            session()->setFlashdata('err', 'Data Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idTgjwbdir,
            'is_approved' => 0,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->tgjwbdirModel->save($dataUpdate)) {
            session()->setFlashdata('err', 'Approval Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi dibatalkan.');
            return redirect()->back();
        } else {
            session()->setFlashdata('err', 'Terjadi kesalahan saat membatalkan approval.');
            return redirect()->back();
        }
    }

    public function approveSemua()
    {
        date_default_timezone_set('Asia/Jakarta');
        $userId = service('authentication')->id();
        $dataUpdate = [
            'is_approved' => 1,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        $this->tgjwbdirModel->builder()->update($dataUpdate);

        session()->setFlashdata('message', 'Semua Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi berhasil disetujui.');
        return redirect()->back();
    }

    public function unapproveSemua()
    {

        date_default_timezone_set('Asia/Jakarta');
        $userId = service('authentication')->id();
        $dataUpdate = [
            'is_approved' => 2,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        $this->tgjwbdirModel->builder()->update($dataUpdate);

        session()->setFlashdata('err', 'Semua approval Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi dibatalkan.');
        return redirect()->back();
    }

    public function approveSemuaKom()
    {
        $userId = service('authentication')->id();  // Mendapatkan ID pengguna
        $user = $this->userModel->find($userId);    // Ambil data pengguna berdasarkan ID
        $komut = $user['fullname'] ?? 'Unknown';    // Ambil fullname pengguna, jika tidak ada, tampilkan 'Unknown'
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        // Validasi data penting
        if (!$kodebpr || !$periodeId) {
            session()->setFlashdata('err', 'Kode BPR atau Periode ID tidak valid');
            return redirect()->back();
        }

        // Cek dulu apakah ada data yang akan diupdate
        $count = $this->tgjwbdirModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->countAllResults();

        if ($count === 0) {
            session()->setFlashdata('err', 'Tidak ada data yang bisa diupdate untuk periode ini');
            return redirect()->back();
        }

        // Data untuk diupdate
        $dataUpdate = [
            'accdekom' => 1,                // Status disetujui
            'accdekom_by' => $userId,       // Menyimpan siapa yang memberikan approval
            'accdekom_at' => date('Y-m-d H:i:s'),  // Waktu persetujuan
            'komut' => $komut,              // Menyimpan nama yang memberikan persetujuan
        ];

        try {
            // Lakukan update pada tabel penjelasanumum berdasarkan kodebpr dan periode_id
            $updated = $this->tgjwbdirModel
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->update(null, $dataUpdate);  // Parameter pertama null untuk update semua yang sesuai kriteria

            if (!$updated) {
                session()->setFlashdata('err', 'Gagal mengupdate data approval');
                return redirect()->back();
            }

            session()->setFlashdata('message', 'Data berhasil disetujui.');
            return redirect()->back();

        } catch (\Exception $e) {
            // Menangani kesalahan dan mencatat log error
            log_message('error', 'Error in approveSemuaKom: ' . $e->getMessage());
            session()->setFlashdata('err', 'Terjadi kesalahan sistem: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function unapproveSemuaKom()
    {
        $userId = service('authentication')->id();  // Mendapatkan ID pengguna
        $user = $this->userModel->find($userId);    // Ambil data pengguna berdasarkan ID
        $komut = $user['fullname'] ?? 'Unknown';    // Ambil fullname pengguna, jika tidak ada, tampilkan 'Unknown'
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        // Validasi data penting
        if (!$kodebpr || !$periodeId) {
            session()->setFlashdata('err', 'Kode BPR atau Periode ID tidak valid');
            return redirect()->back();
        }

        // Cek dulu apakah ada data yang akan diupdate
        $count = $this->tgjwbdirModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->countAllResults();

        if ($count === 0) {
            session()->setFlashdata('err', 'Tidak ada data yang bisa diupdate untuk periode ini');
            return redirect()->back();
        }

        // Data untuk diupdate
        $dataUpdate = [
            'accdekom' => 0,
            'is_approved' => 0,           // Status disetujui
            'accdekom_by' => $userId,       // Menyimpan siapa yang memberikan approval
            'accdekom_at' => date('Y-m-d H:i:s'),  // Waktu persetujuan
            'komut' => $komut,              // Menyimpan nama yang memberikan persetujuan
        ];

        try {
            // Lakukan update pada tabel penjelasanumum berdasarkan kodebpr dan periode_id
            $updated = $this->tgjwbdirModel
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->update(null, $dataUpdate);  // Parameter pertama null untuk update semua yang sesuai kriteria

            if (!$updated) {
                session()->setFlashdata('err', 'Gagal mengupdate data approval');
                return redirect()->back();
            }

            session()->setFlashdata('err', 'Approval dibatalkan.');
            return redirect()->back();

        } catch (\Exception $e) {
            // Menangani kesalahan dan mencatat log error
            log_message('error', 'Error in approveSemuaKom: ' . $e->getMessage());
            session()->setFlashdata('err', 'Terjadi kesalahan sistem: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function approveSemuaDirut()
    {
        date_default_timezone_set('Asia/Jakarta');
        $userId = service('authentication')->id();  // Mendapatkan ID pengguna
        $user = $this->userModel->find($userId);    // Ambil data pengguna berdasarkan ID
        $dirut = $user['fullname'] ?? 'Unknown';    // Ambil fullname pengguna, jika tidak ada, tampilkan 'Unknown'
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        // Validasi data penting
        if (!$kodebpr || !$periodeId) {
            session()->setFlashdata('err', 'Kode BPR atau Periode ID tidak valid');
            return redirect()->back();
        }

        // Cek dulu apakah ada data yang akan diupdate
        $count = $this->tgjwbdirModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->countAllResults();

        if ($count === 0) {
            session()->setFlashdata('err', 'Tidak ada data yang bisa diupdate untuk periode ini');
            return redirect()->back();
        }

        // Data untuk diupdate
        $dataUpdate = [
            'is_approved' => 1,                // Status disetujui
            'approved_by' => $userId,       // Menyimpan siapa yang memberikan approval
            'approved_at' => date('Y-m-d H:i:s'),  // Waktu persetujuan
            'dirut' => $dirut,              // Menyimpan nama yang memberikan persetujuan
        ];

        try {
            // Lakukan update pada tabel penjelasanumum berdasarkan kodebpr dan periode_id
            $updated = $this->tgjwbdirModel
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->update(null, $dataUpdate);  // Parameter pertama null untuk update semua yang sesuai kriteria

            if (!$updated) {
                session()->setFlashdata('err', 'Gagal mengupdate data approval');
                return redirect()->back();
            }

            session()->setFlashdata('message', 'Data berhasil disetujui.');
            return redirect()->back();

        } catch (\Exception $e) {
            // Menangani kesalahan dan mencatat log error
            log_message('error', 'Error in approveSemuaKom: ' . $e->getMessage());
            session()->setFlashdata('err', 'Terjadi kesalahan sistem: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function unapproveSemuaDirut()
    {
        date_default_timezone_set('Asia/Jakarta');
        $userId = service('authentication')->id();  // Mendapatkan ID pengguna
        $user = $this->userModel->find($userId);    // Ambil data pengguna berdasarkan ID
        $dirut = $user['fullname'] ?? 'Unknown';    // Ambil fullname pengguna, jika tidak ada, tampilkan 'Unknown'
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        // Validasi data penting
        if (!$kodebpr || !$periodeId) {
            session()->setFlashdata('err', 'Kode BPR atau Periode ID tidak valid');
            return redirect()->back();
        }

        // Cek dulu apakah ada data yang akan diupdate
        $count = $this->tgjwbdirModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->countAllResults();

        if ($count === 0) {
            session()->setFlashdata('err', 'Tidak ada data yang bisa diupdate untuk periode ini');
            return redirect()->back();
        }

        // Data untuk diupdate
        $dataUpdate = [
            'is_approved' => 0,
            'accdekom' => 0,                // Status disetujui
            'approved_by' => $userId,       // Menyimpan siapa yang memberikan approval
            'approved_at' => date('Y-m-d H:i:s'),  // Waktu persetujuan
            'dirut' => $dirut,              // Menyimpan nama yang memberikan persetujuan
        ];

        try {
            // Lakukan update pada tabel penjelasanumum berdasarkan kodebpr dan periode_id
            $updated = $this->tgjwbdirModel
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->update(null, $dataUpdate);  // Parameter pertama null untuk update semua yang sesuai kriteria

            if (!$updated) {
                session()->setFlashdata('err', 'Gagal mengupdate data approval');
                return redirect()->back();
            }

            session()->setFlashdata('err', 'Approval dibatalkan.');
            return redirect()->back();

        } catch (\Exception $e) {
            // Menangani kesalahan dan mencatat log error
            log_message('error', 'Error in approveSemuaKom: ' . $e->getMessage());
            session()->setFlashdata('err', 'Terjadi kesalahan sistem: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function setNullKolomTindak($id)
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $tindak = $this->penjelastindakModel->setNullKolomTindak($id);

        if ($tindak) {
            session()->setFlashdata('message', 'Data berhasil dihapus');
        } else {
            session()->setFlashdata('err', 'Data gagal dihapus');
        }

        return redirect()->to(base_url('Tgjwbdir'));
    }

    public function setNullKolomPenjelaslanjut($id)
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $penjelas = $this->penjelastindakModel->setNullKolomPenjelaslanjut($id);

        if ($penjelas) {
            session()->setFlashdata('message', 'Data berhasil dihapus');
        } else {
            session()->setFlashdata('err', 'Data gagal dihapus');
        }

        return redirect()->to(base_url('Tgjwbdir'));
    }

    public function exporttxttgjwbdir()
    {
        // Authentication check
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        // Get parameters from internal sources
        $kodebpr = $this->userKodebpr; // Get user kodebpr
        $periodeId = session('active_periode'); // Get active period ID
        $subkategori = "Tgjwbdir";

        // Get the current date in YYYY-MM-DD format for the header
        $periodeDetail = $this->periodeModel->find($periodeId);
        $exportDate = $periodeDetail['tahun'] ?? date('Y');

        // Fetch data for the selected kodebpr and periode
        $data_tgjwbdir = $this->tgjwbdirModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        // Fetch tindaklanjut and penjelasanlanjut data
        $data_penjelastindak = $this->penjelastindakModel->where('subkategori', $subkategori)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->findAll();

        // Initialize variables for output
        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'] ?? '';
            $kodejenis = $infobpr['kodejenis'] ?? '';
        }

        $output = "";

        // Add header row to the output
        $output .= "H01|" . $kodejenis . "|" . $sandibpr . "|" . $exportDate . "-12-31" . "|LTBPRK|E0201|0|\r\n";

        // Add data rows from tgjwbdir
        foreach ($data_tgjwbdir as $row) {
            $tugastgjwbdir = str_replace(array("\r", "\n"), ' ', $row['tugastgjwbdir']);
            $output .= "D01|" . "011000000000" . "|" . (isset($row['nik']) ? $row['nik'] : '') . "|" . $tugastgjwbdir . "\r\n";
        }

        // Add data rows from penjelastindak (tindaklanjut and penjelasanlanjut)
        foreach ($data_penjelastindak as $penjelas) {
            $tindaklanjut = str_replace(array("\r", "\n"), ' ', $penjelas['tindaklanjut']);
            if (!empty($penjelas['tindaklanjut']) && $penjelas['tindaklanjut'] !== null) {
                $output .= "F01|" . $tindaklanjut . "\r\n";
            }
        }

        // Only add F02 rows if penjelasanlanjut is not empty or null
        foreach ($data_penjelastindak as $penjelas) {
            $penjelasanlanjut = str_replace(array("\r", "\n"), ' ', $penjelas['penjelasanlanjut']);
            if (!empty($penjelas['penjelasanlanjut']) && $penjelas['penjelasanlanjut'] !== null) {
                $output .= "F02|" . $penjelasanlanjut . "\r\n";
            }
        }

        // Get the current date for the filename (e.g., 20250708)
        
        $filename = "LTBPRK-E0201-R-A-" . $exportDate . "1231" . "-" . $sandibpr . "-01.txt";

        // Set the response headers for file download
        $response = service('response');
        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

        // Return the file content as the body of the response
        return $response->setBody($output);
    }


}


