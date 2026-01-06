<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_tgjwbkomite;
use App\Models\M_infobpr;
use App\Models\M_user;
use App\Models\M_periodetransparansi;
use App\Models\M_transparansicomments;
use App\Models\M_transparansicommentsread;
use App\Models\M_penjelastindak;
use Myth\Auth\Config\Services as AuthServices;

class tgjwbkomite extends Controller
{
    protected $auth;
    protected $tgjwbkomiteModel;
    protected $userModel;
    protected $komentarModel;
    protected $infobprModel;
    protected $session;
    protected $userKodebpr;
    protected $periodeModel;
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
        $this->tgjwbkomiteModel = new M_tgjwbkomite();
        $this->periodeModel = new M_periodetransparansi();
        $this->userModel = new M_user();
        $this->komentarModel = new M_transparansicomments();
        $this->commentReadsModel = new M_transparansicommentsread();
        $this->penjelastindakModel = new M_penjelastindak();
        $this->infobprModel = new M_infobpr();
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
        $subkategori = 'Tgjwbkomite';
        $bprData = $this->infobprModel->getBprByKode($this->userKodebpr);

        $tgjwbkomiteData = $this->tgjwbkomiteModel
            ->where('periode_id', $periodeId)
            ->where('kodebpr', $kodebpr)
            ->findAll();  // This should work if the model is properly set up

        $data['periodetransparansi'] = $this->periodeModel->find($periodeId);

        // Mengambil data accdekom, accdekom_by, accdekom_at
        $accdekomData = $this->tgjwbkomiteModel
            ->select('accdekom, accdekom_by, accdekom_at')
            ->where('periode_id', $periodeId)
            ->where('kodebpr', $kodebpr)
            ->findAll();

        $accdirutData = $this->tgjwbkomiteModel
            ->select('is_approved, approved_by, approved_at')
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
            // Handle if user does not have a valid kodebpr
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
        }

        // Prepare data for the view
        $lastVisit = session('last_visit_komentar') ?? date('Y-m-d H:i:s', strtotime('-1 day'));
        session()->set('last_visit_komentar', date('Y-m-d H:i:s'));

        $penjelastindak = $this->penjelastindakModel->getDataPenjelasByKodebprAndPeriode($subkategori, $kodebpr, $periodeId);

        // Cek komisaris telah approves
        $canApprove = true;

        // Ambil semua data dengan kondisi yang sesuai
        $accdekomValues = $this->tgjwbkomiteModel
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
            'judul' => '4. Tugas, Tanggung Jawab, Program Kerja, dan Realisasi Program Kerja Komite',
            'tgjwbkomite' => $tgjwbkomiteData,
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
        echo view('tgjwbkomite/index', $data);
        echo view('templates/v_footer');
    }

    public function tambahtgjwbkomite()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
        }

        if (isset($_POST['tambahtgjwbkomite'])) {
            $val = $this->validate([
                'komite' => [
                    'label' => 'Komite',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'tugastgjwbkomite' => [
                    'label' => 'Penjelasan Tugas dan Tanggung Jawab:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'jumlahrapat' => [
                    'label' => 'Jumlah Rapat:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'prokerkomite' => [
                    'label' => 'Program Kerja Komite:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hasilprokerkomite' => [
                    'label' => 'Realisasi Program Kerja Komite:',
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
                    'komite' => $this->request->getPost('komite'),
                    'jumlahrapat' => $this->request->getPost('jumlahrapat'),
                    'tugastgjwbkomite' => $this->request->getPost('tugastgjwbkomite'),
                    'prokerkomite' => $this->request->getPost('prokerkomite'),
                    'hasilprokerkomite' => $this->request->getPost('hasilprokerkomite'),
                    'tindakkomite' => $this->request->getPost('tindakkomite'),
                    'periode_id' => $periodeId,
                    'user_id' => $userId,
                    'kodebpr' => $kodebpr,
                    'fullname' => $fullname,
                    'accdekom' => 0,
                    'is_approved' => 0,
                ];

                // Insert data
                $this->tgjwbkomiteModel->checkIncrement();
                $success = $this->tgjwbkomiteModel->tambahtgjwbkomite($data);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil ditambahkan ');
                    return redirect()->to(base_url('Tgjwbkomite'));
                }
            }
        } else {
            return redirect()->to(base_url('Tgjwbkomite'));
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
        $komite = $this->request->getPost('komite');
        $tugastgjwbkomite = $this->request->getPost('tugastgjwbkomite');
        $jumlahrapat = $this->request->getPost('jumlahrapat');
        $prokerkomite = $this->request->getPost('prokerkomite');
        $hasilprokerkomite = $this->request->getPost('hasilprokerkomite');

        if (empty($komite) || empty($tugastgjwbkomite) || empty($jumlahrapat) || empty($prokerkomite) || empty($hasilprokerkomite)) {
            return redirect()->back()->with('error', 'Tindak Lanjut atau Penjelasan tidak boleh kosong');
        }

        // Prepare data for update
        $data = [
            'komite' => $komite,
            'tugastgjwbkomite' => $tugastgjwbkomite,
            'jumlahrapat' => $jumlahrapat,
            'prokerkomite' => $prokerkomite,
            'hasilprokerkomite' => $hasilprokerkomite,
            'user_id' => $userId,
            'kodebpr' => $kodebpr,
            'accdekom' => 0,
            'is_approved' => 0
        ];

        // Attempt to update the record
        if ($this->tgjwbkomiteModel->editbasedkodedanperiode($data, $kodebpr, $periodeId, $id)) {
            session()->setFlashdata('message', 'Data berhasil diubah');
        } else {
            session()->setFlashdata('err', 'Gagal mengubah data');
        }
        return redirect()->to(base_url('Tgjwbkomite'));
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
        $subkategori = 'Tgjwbkomite';
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
        $subkategori = 'Tgjwbkomite';
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
                'subkategori' => 'Tgjwbkomite',
                'komentar' => $this->request->getPost('komentar'),
                'fullname' => $this->request->getPost('fullname'),
                'user_id' => $userId,
                'kodebpr' => $kodebpr,
                'periode_id' => session('active_periode'), // Pastikan ini diisi
                'created_at' => date('Y-m-d H:i:s')
            ];

            $this->komentarModel->insertKomentar($data);
            session()->setFlashdata('message', 'Komentar berhasil ditambahkan');
            return redirect()->to(base_url('Tgjwbkomite') . '?modal_komentar=' . $this->request->getPost('id'));
        }

        return redirect()->to(base_url('Tgjwbkomite'));
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
                $existingData = $this->tgjwbkomiteModel->where([
                    'kodebpr' => $kodebpr,
                    'periode_id' => $periodeId
                ])->first();

                // Data to save in penjelastindak table
                $penjelastindak = [
                    'subkategori' => 'Tgjwbkomite',
                    'tindaklanjut' => $this->request->getPost('tindaklanjut'),
                    'kodebpr' => $kodebpr,
                    'periode_id' => $periodeId,
                    'fullname' => $fullname
                ];

                $this->penjelastindakModel->tambahpenjelastindak($penjelastindak);
                session()->setFlashdata('message', 'Data berhasil diubah');

                return redirect()->to(base_url('Tgjwbkomite'));
            }
        } else {
            return redirect()->to(base_url('Tgjwbkomite'));
        }
    }

    public function editketerangan()
    {
        $id = $this->request->getPost('id');
        $userId = service('authentication')->id();
        $userModel = new M_user();
        $subkategori = 'Tgjwbkomite';
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

        if (empty($tindaklanjut)) {
            return redirect()->back()->with('error', 'Tindak Lanjut atau Penjelasan tidak boleh kosong');
        }

        // Prepare data for update
        $data = [
            'tindaklanjut' => $tindaklanjut,
            'user_id' => $userId,
            'kodebpr' => $kodebpr,
        ];

        // Attempt to update the record
        if ($this->penjelastindakModel->editberdasarkankodedanperiode($data, $subkategori, $kodebpr, $periodeId)) {
            session()->setFlashdata('message', 'Data berhasil diubah');
        } else {
            session()->setFlashdata('err', 'Gagal mengubah data');
        }

        return redirect()->to(base_url('Tgjwbkomite'));
    }

    public function hapus($id)
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()->to($redirectURL);
        }

        // Memanggil fungsi hapus pada model dan menyimpan hasilnya dalam variabel $success
        $this->tgjwbkomiteModel->hapus($id);
        session()->setFlashdata('message', 'Data berhasil dihapus');

        return redirect()->to(base_url('Tgjwbkomite'));

    }

    public function approve($idTgjwbkomite)
    {
        if (!is_numeric($idTgjwbkomite) || $idTgjwbkomite <= 0) {
            session()->setFlashdata('err', 'ID Pelaksanaan Tugas, Tanggung Jawab, Program Kerja, dan Realisasi Program Kerja Komite tidak valid.');
            return redirect()->back();
        }

        $tgjwbkomite = $this->tgjwbkomiteModel->find($idTgjwbkomite);
        if (!$tgjwbkomite) {
            session()->setFlashdata('err', 'Data Pelaksanaan Tugas, Tanggung Jawab, Program Kerja, dan Realisasi Program Kerja Komite dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idTgjwbkomite,
            'is_approved' => 1,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->tgjwbkomiteModel->save($dataUpdate)) {
            session()->setFlashdata('message', 'Pelaksanaan Tugas, Tanggung Jawab, Program Kerja, dan Realisasi Program Kerja Komite berhasil disetujui.');
            return redirect()->back();
        } else {
            session()->setFlashdata('err', 'Terjadi kesalahan saat melakukan approval.');
            return redirect()->back();
        }
    }

    public function unapprove($idTgjwbkomite)
    {
        if (!is_numeric($idTgjwbkomite) || $idTgjwbkomite <= 0) {
            session()->setFlashdata('err', 'ID Pelaksanaan Tugas, Tanggung Jawab, Program Kerja, dan Realisasi Program Kerja Komite tidak valid.');
            return redirect()->back();
        }

        $tgjwbkomite = $this->tgjwbkomiteModel->find($idTgjwbkomite);
        if (!$tgjwbkomite) {
            session()->setFlashdata('err', 'Data Pelaksanaan Tugas, Tanggung Jawab, Program Kerja, dan Realisasi Program Kerja Komite dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idTgjwbkomite,
            'is_approved' => 0,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->tgjwbkomiteModel->save($dataUpdate)) {
            session()->setFlashdata('err', 'Approval Pelaksanaan Tugas, Tanggung Jawab, Program Kerja, dan Realisasi Program Kerja Komite dibatalkan.');
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

        $this->tgjwbkomiteModel->builder()->update($dataUpdate);

        session()->setFlashdata('message', 'Semua Pelaksanaan Tugas, Tanggung Jawab, Program Kerja, dan Realisasi Program Kerja Komite berhasil disetujui.');
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

        $this->tgjwbkomiteModel->builder()->update($dataUpdate);

        session()->setFlashdata('err', 'Semua approval Pelaksanaan Tugas, Tanggung Jawab, Program Kerja, dan Realisasi Program Kerja Komite dibatalkan.');
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
        $existingData = $this->tgjwbkomiteModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        // Jika data kosong, lakukan insert
        if (!$existingData) {
            $dataInsert = [
                'kodebpr' => $kodebpr,
                'periode_id' => $periodeId,
                'accdekom' => 1,
                'accdekom_by' => $userId,
                'accdekom_at' => date('Y-m-d H:i:s'),
            ];

            try {
                $inserted = $this->tgjwbkomiteModel->insert($dataInsert);

                if (!$inserted) {
                    session()->setFlashdata('err', 'Gagal menyetujui data');
                    return redirect()->back();
                }

                session()->setFlashdata('err', 'Approval dibatalkan.');
                return redirect()->back();

            } catch (\Exception $e) {
                log_message('error', 'Error in Approval Dekom: ' . $e->getMessage());
                session()->setFlashdata('err', 'Terjadi kesalahan sistem: ' . $e->getMessage());
                return redirect()->back();
            }
        } else {
            // Jika data ada, lakukan update
            $dataUpdate = [
                'accdekom' => 1,
                'accdekom_by' => $userId,
                'accdekom_at' => date('Y-m-d H:i:s'),
            ];

            try {
                $updated = $this->tgjwbkomiteModel
                    ->where('kodebpr', $kodebpr)
                    ->where('periode_id', $periodeId)
                    ->update(null, $dataUpdate);

                if (!$updated) {
                    session()->setFlashdata('err', 'Gagal mengupdate data approval');
                    return redirect()->back();
                }

                session()->setFlashdata('message', 'Data berhasil disetujui.');
                return redirect()->back();

            } catch (\Exception $e) {
                log_message('error', 'Error in approveSemuaKom (update): ' . $e->getMessage());
                session()->setFlashdata('err', 'Terjadi kesalahan sistem: ' . $e->getMessage());
                return redirect()->back();
            }
        }
    }

    public function unapproveSemuaKom()
    {
        $userId = service('authentication')->id();
        $user = $this->userModel->find($userId);
        $komut = $user['fullname'] ?? 'Unknown';
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        // Validasi data penting
        if (!$kodebpr || !$periodeId) {
            session()->setFlashdata('err', 'Kode BPR atau Periode ID tidak valid');
            return redirect()->back();
        }

        // Cek dulu apakah ada data yang akan diupdate
        $count = $this->tgjwbkomiteModel
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
            'is_approved' => 0,
            'accdekom_by' => $userId,
            'accdekom_at' => date('Y-m-d H:i:s'),
        ];

        try {
            $updated = $this->tgjwbkomiteModel
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->update(null, $dataUpdate);

            if (!$updated) {
                session()->setFlashdata('err', 'Gagal mengupdate data approval');
                return redirect()->back();
            }

            session()->setFlashdata('err', 'Approval dibatalkan.');
            return redirect()->back();

        } catch (\Exception $e) {
            log_message('error', 'Error in approveSemuaKom: ' . $e->getMessage());
            session()->setFlashdata('err', 'Terjadi kesalahan sistem: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function approveSemuaDirut()
    {
        date_default_timezone_set('Asia/Jakarta');
        $userId = service('authentication')->id();
        $user = $this->userModel->find($userId);
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        // Validasi data penting
        if (!$kodebpr || !$periodeId) {
            session()->setFlashdata('err', 'Kode BPR atau Periode ID tidak valid');
            return redirect()->back();
        }

        // Cek dulu apakah ada data yang akan diupdate
        $count = $this->tgjwbkomiteModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->countAllResults();

        if ($count === 0) {
            session()->setFlashdata('err', 'Tidak ada data yang bisa diupdate untuk periode ini');
            return redirect()->back();
        }

        // Data untuk diupdate
        $dataUpdate = [
            'is_approved' => 1,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        try {
            $updated = $this->tgjwbkomiteModel
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->update(null, $dataUpdate);

            if (!$updated) {
                session()->setFlashdata('err', 'Gagal mengupdate data approval');
                return redirect()->back();
            }

            session()->setFlashdata('message', 'Data disetujui.');
            return redirect()->back();

        } catch (\Exception $e) {
            log_message('error', 'Error in approveSemuaKom: ' . $e->getMessage());
            session()->setFlashdata('err', 'Terjadi kesalahan sistem: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function unapproveSemuaDirut()
    {
        date_default_timezone_set('Asia/Jakarta');
        $userId = service('authentication')->id();
        $user = $this->userModel->find($userId);
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        if (!$kodebpr || !$periodeId) {
            session()->setFlashdata('err', 'Kode BPR atau Periode ID tidak valid');
            return redirect()->back();
        }

        $count = $this->tgjwbkomiteModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->countAllResults();

        if ($count === 0) {
            session()->setFlashdata('err', 'Tidak ada data yang bisa diupdate untuk periode ini');
            return redirect()->back();
        }

        $dataUpdate = [
            'is_approved' => 0,
            'accdekom' => 0,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        try {
            $updated = $this->tgjwbkomiteModel
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->update(null, $dataUpdate);

            if (!$updated) {
                session()->setFlashdata('err', 'Gagal mengupdate data approval');
                return redirect()->back();
            }

            session()->setFlashdata('message', 'Approval dibatalkan.');
            return redirect()->back();

        } catch (\Exception $e) {
            log_message('error', 'Error in Approval: ' . $e->getMessage());
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

        return redirect()->to(base_url('Tgjwbkomite'));
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

        return redirect()->to(base_url('Tgjwbkomite'));
    }

    public function exporttxttgjwbkomite()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        // Get parameters from internal sources
        $kodebpr = $this->userKodebpr; // Get user kodebpr
        $periodeId = session('active_periode'); // Get active period ID
        $subkategori = "Tgjwbkomite";

        // Get the current date in YYYY-MM-DD format for the header
        $periodeDetail = $this->periodeModel->find($periodeId);
        $exportDate = $periodeDetail['tahun'] ?? date('Y');

        $data_tgjwbkomite = $this->tgjwbkomiteModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        // Fetch tindaklanjut and penjelasanlanjut data
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

        $output = "";
        $output .= "H01|" . $kodejenis . "|" . $sandibpr . "|" . $exportDate . "-12-31" . "|LTBPRK|E0203|0|\r\n";
        foreach ($data_tgjwbkomite as $row) {
            $output .= "D01|" . "013301000000" . "|" . (isset($row['komite']) ? $row['komite'] : '') . "|" . (isset($row['tugastgjwbkomite']) ? $row['tugastgjwbkomite'] : '') . "|" . (isset($row['prokerkomite']) ? $row['prokerkomite'] : '') . "|" . (isset($row['hasilprokerkomite']) ? $row['hasilprokerkomite'] : '') . "|" . (isset($row['jumlahrapat']) ? $row['jumlahrapat'] : '') . "\r\n";
        }

        foreach ($data_penjelastindak as $penjelas) {
            if (!empty($penjelas['tindaklanjut']) && $penjelas['tindaklanjut'] !== null) {
                $output .= "F01|" . $penjelas['tindaklanjut'] . "\r\n";
            }
        }

        foreach ($data_penjelastindak as $penjelas) {
            if (!empty($penjelas['penjelasanlanjut']) && $penjelas['penjelasanlanjut'] !== null) {
                $output .= "F02|" . $penjelas['penjelasanlanjut'] . "\r\n";
            }
        }

        $filename = "LTBPRK-E0203-R-A-" . $exportDate . "1231-" . $sandibpr . "-01.txt";

        // Set the response headers for file download
        $response = service('response');
        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

        // Return the file content as the body of the response
        return $response->setBody($output);
    }

}


