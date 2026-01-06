<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_shmusahadirdekom;
use App\Models\M_infobpr;
use App\Models\M_user;
use App\Models\M_periodetransparansi;
use App\Models\M_transparansicomments;
use App\Models\M_transparansicommentsread;
use App\Models\M_penjelastindak;
use Myth\Auth\Config\Services as AuthServices;

class shmusahadirdekom extends Controller
{
    protected $auth;
    protected $session;
    protected $userKodebpr;
    protected $userId;

    protected $shmusahadirdekomModel;
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

    private function getSahamdirdekomModel()
    {
        if (!$this->shmusahadirdekomModel) {
            $this->shmusahadirdekomModel = new M_shmusahadirdekom();
        }
        return $this->shmusahadirdekomModel;
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

    // Load and cache user data
    private function loadUserData()
    {
        if ($this->userData === null && $this->userId) {
            $this->userData = $this->getUserModel()->find($this->userId);
            $this->userKodebpr = $this->userData['kodebpr'] ?? null;
        }
    }

    // Load and cache user permissions
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

    // Centralized authentication check
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
        $subkategori = 'Shmusahadirdekom';

        // Single optimized query to get all sahamdirdekom data with approval info
        $shmusahadirdekomData = $this->getSahamdirdekomModel()
            ->select('*, accdekom, accdekom_by, accdekom_at, is_approved, approved_by, approved_at')
            ->where('periode_id', $periodeId)
            ->where('kodebpr', $kodebpr)
            ->limit(10)
            ->findAll();

        // Get comments with a single query
        $komentarList = $this->getKomentarModel()
            ->where('subkategori', $subkategori)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->findAll();

        // Get penjelastindak data
        $penjelastindak = $this->getPenjelastindakModel()
            ->getDataPenjelasByKodebprAndPeriode($subkategori, $kodebpr, $periodeId);

        return [
            'shmusahadirdekom' => $shmusahadirdekomData,
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

        $accdekomData = $this->shmusahadirdekomModel
            ->select('accdekom, accdekom_by, accdekom_at')
            ->where('periode_id', $periodeId)
            ->where('kodebpr', $kodebpr)
            ->findAll();

        $accdirutData = $this->shmusahadirdekomModel
            ->select('is_approved, approved_by, approved_at')
            ->where('periode_id', $periodeId)
            ->where('kodebpr', $kodebpr)
            ->findAll();

        // Prepare data for view session management
        $lastVisit = session('last_visit_komentar') ?? date('Y-m-d H:i:s', strtotime('-1 day'));
        session()->set('last_visit_komentar', date('Y-m-d H:i:s'));

        $canApprove = true;

        // Ambil semua data dengan kondisi yang sesuai
        $accdekomValues = $this->shmusahadirdekomModel
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
            'judul' => '7. Kepemilikan Saham Anggota Direksi, Dewan Komisaris, dan Pemegang Saham pada Kelompok Usaha BPR',
            'shmusahadirdekom' => $indexData['shmusahadirdekom'],
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
        echo view('shmusahadirdekom/index', $data);
        echo view('templates/v_footer');
    }

    private function validateSahamDir($data)
    {
        return $this->validate([
            'nama' => [
                'label' => 'Nama Direksi',
                'rules' => 'required',
                'errors' => ['required' => '{field} tidak boleh kosong.']
            ],
            'nik' => [
                'label' => 'NIK',
                'rules' => 'required|numeric|max_length[16]',
                'errors' => [
                    'required' => '{field} tidak boleh kosong.',
                    'numeric' => '{field} harus berupa angka.',
                    'min_length' => '{field} harus memiliki panjang minimal 16 karakter.',
                    'max_length' => '{field} harus memiliki panjang maksimal 16 karakter.'
                ]
            ],
            'usaha' => [
                'label' => 'Nama Kelompok Usaha BPR',
                'rules' => 'required',
                'errors' => ['required' => '{field} tidak boleh kosong.']
            ],
            'persensaham' => [
                'label' => 'Persentase Kepemilikan (%)',
                'rules' => 'required',
                'errors' => ['required' => '{field} tidak boleh kosong.']
            ],
            'persensahamlalu' => [
                'label' => 'Persentase Kepemilikan (%) Tahun Sebelumnya',
                'rules' => 'required',
                'errors' => ['required' => '{field} tidak boleh kosong.']
            ]
        ]);
    }

    private function validateSahamDekom($data)
    {
        return $this->validate([
            'nama' => [
                'label' => 'Nama Dewan Komisaris',
                'rules' => 'required',
                'errors' => ['required' => '{field} tidak boleh kosong.']
            ],
            'nik' => [
                'label' => 'NIK',
                'rules' => 'required|numeric|max_length[16]',
                'errors' => [
                    'required' => '{field} tidak boleh kosong.',
                    'numeric' => '{field} harus berupa angka.',
                    'min_length' => '{field} harus memiliki panjang minimal 16 karakter.',
                    'max_length' => '{field} harus memiliki panjang maksimal 16 karakter.'
                ]
            ],
            'persensaham' => [
                'label' => 'Persentase Kepemilikan (%)',
                'rules' => 'required',
                'errors' => ['required' => '{field} tidak boleh kosong.']
            ],
            'persensahamlalu' => [
                'label' => 'Persentase Kepemilikan (%) Tahun Sebelumnya',
                'rules' => 'required',
                'errors' => ['required' => '{field} tidak boleh kosong.']
            ]
        ]);
    }

    private function validateSahamPshm($data)
    {
        return $this->validate([
            'nama' => [
                'label' => 'Nama Pemegang Saham',
                'rules' => 'required',
                'errors' => ['required' => '{field} tidak boleh kosong.']
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
            'persensaham' => [
                'label' => 'Persentase Kepemilikan (%)',
                'rules' => 'required',
                'errors' => ['required' => '{field} tidak boleh kosong.']
            ],
            'persensahamlalu' => [
                'label' => 'Persentase Kepemilikan (%) Tahun Sebelumnya',
                'rules' => 'required',
                'errors' => ['required' => '{field} tidak boleh kosong.']
            ]
        ]);
    }

    // Common method for preparing insert data
    private function prepareInsertData($specificData)
    {
        return array_merge($specificData, [
            'periode_id' => session('active_periode'),
            'user_id' => $this->userId,
            'kodebpr' => $this->userKodebpr,
            'fullname' => $this->userData['fullname'] ?? null,
            'accdekom' => 0,
            'is_approved' => 0,
        ]);
    }

    public function tambahsahamdir()
    {
        $authCheck = $this->checkAuthentication();
        if ($authCheck)
            return $authCheck;

        if (!isset($_POST['tambahsahamdir'])) {
            return redirect()->to(base_url('Shmusahadirdekom'));
        }

        if (!$this->validateSahamDir($_POST)) {
            session()->setFlashdata('err', \Config\Services::validation()->listErrors());
            return redirect()->back();
        }

        if (!$this->userKodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
        }

        $specificData = [
            'nama' => $this->request->getPost('nama'),
            'jabatan' => 'Direksi',
            'nik' => $this->request->getPost('nik'),
            'usaha' => $this->request->getPost('usaha'),
            'persensaham' => $this->request->getPost('persensaham'),
            'persensahamlalu' => $this->request->getPost('persensahamlalu'),
        ];

        $data = $this->prepareInsertData($specificData);

        if ($this->getSahamdirdekomModel()->tambah($data)) {
            session()->setFlashdata('message', 'Kepemilikan Saham pada badan usaha BPR berhasil ditambahkan');
        } else {
            session()->setFlashdata('err', 'Gagal menambahkan data');
        }

        return redirect()->to(base_url('Shmusahadirdekom'));
    }

    public function tambahsahamdekom()
    {
        $authCheck = $this->checkAuthentication();
        if ($authCheck)
            return $authCheck;

        if (!isset($_POST['tambahsahamdekom'])) {
            return redirect()->to(base_url('Shmusahadirdekom'));
        }

        if (!$this->validateSahamDekom($_POST)) {
            session()->setFlashdata('err', \Config\Services::validation()->listErrors());
            return redirect()->back();
        }

        if (!$this->userKodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
        }

        $specificData = [
            'nama' => $this->request->getPost('nama'),
            'nik' => $this->request->getPost('nik'),
            'usaha' => $this->request->getPost('usaha'),
            'persensaham' => $this->request->getPost('persensaham'),
            'persensahamlalu' => $this->request->getPost('persensahamlalu'),
            'jabatan' => 'Dekom',
        ];

        $data = $this->prepareInsertData($specificData);

        if ($this->getSahamdirdekomModel()->tambah($data)) {
            session()->setFlashdata('message', 'Kepemilikan Saham pada badan usaha BPR berhasil ditambahkan');
        } else {
            session()->setFlashdata('err', 'Gagal menambahkan data');
        }

        return redirect()->to(base_url('Shmusahadirdekom'));
    }

    public function tambahsahampshm()
    {
        $authCheck = $this->checkAuthentication();
        if ($authCheck)
            return $authCheck;

        if (!isset($_POST['tambahsahampshm'])) {
            return redirect()->to(base_url('Shmusahadirdekom'));
        }

        if (!$this->validateSahamPshm($_POST)) {
            session()->setFlashdata('err', \Config\Services::validation()->listErrors());
            return redirect()->back();
        }

        if (!$this->userKodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
        }

        $specificData = [
            'nama' => $this->request->getPost('nama'),
            'nik' => $this->request->getPost('nik'),
            'usaha' => $this->request->getPost('usaha'),
            'persensaham' => $this->request->getPost('persensaham'),
            'persensahamlalu' => $this->request->getPost('persensahamlalu'),
            'jabatan' => 'Psaham',
        ];

        $data = $this->prepareInsertData($specificData);

        if ($this->getSahamdirdekomModel()->tambah($data)) {
            session()->setFlashdata('message', 'Kepemilikan Saham pada badan usaha BPR berhasil ditambahkan');
        } else {
            session()->setFlashdata('err', 'Gagal menambahkan data');
        }

        return redirect()->to(base_url('Shmusahadirdekom'));
    }

    public function Tambahkomentar()
    {
        $authCheck = $this->checkAuthentication();
        if ($authCheck)
            return $authCheck;

        if (!isset($_POST['TambahKomentar'])) {
            return redirect()->to(base_url('Shmusahadirdekom'));
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
            'subkategori' => 'Shmusahadirdekom',
            'komentar' => $this->request->getPost('komentar'),
            'fullname' => $this->request->getPost('fullname'),
            'user_id' => $this->userId,
            'kodebpr' => $this->userKodebpr,
            'periode_id' => session('active_periode'),
            'created_at' => date('Y-m-d H:i:s')
        ];

        $this->getKomentarModel()->insertKomentar($data);
        session()->setFlashdata('message', 'Komentar berhasil ditambahkan');
        return redirect()->to(base_url('Shmusahadirdekom') . '?modal_komentar=' . $this->request->getPost('id'));
    }

    public function markUserCommentsAsRead()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $Id = $this->request->getPost('id');
        $kodebpr = $this->userKodebpr;
        $userId = $this->userId;
        $periodeId = session('active_periode');

        if (!$Id || !$kodebpr || !$userId || !$periodeId) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Missing data.']);
        }

        $commentsToMark = $this->getKomentarModel()->select('id')
            ->where('subkategori', $Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('user_id !=', $userId)
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
            'is_read' => 0, // <--- Ensure this is set to 0 for new comments
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
            return redirect()->to(base_url('Sahamdirdekom'));
        }

        $val = $this->validate([
            'tindaklanjut' => [
                'label' => 'Tindak Lanjut Tugas dan Tanggung Jawab Anggota Dewan Komisaris',
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
            'subkategori' => 'Shmusahadirdekom',
            'tindaklanjut' => $this->request->getPost('tindaklanjut'),
            'kodebpr' => $kodebpr,
            'periode_id' => $periodeId,
            'fullname' => $this->userData['fullname'] ?? null,
            'user_id' => $this->userId,
        ];

        $this->getPenjelastindakModel()->tambahpenjelastindak($penjelastindak);
        session()->setFlashdata('message', 'Data berhasil diubah');

        return redirect()->to(base_url('Shmusahadirdekom'));
    }

    public function editketerangan()
    {
        $id = $this->request->getPost('id');
        $subkategori = 'Shmusahadirdekom';
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

        return redirect()->to(base_url('Shmusahadirdekom'));
    }

    public function hapus($id)
    {
        $authCheck = $this->checkAuthentication();
        if ($authCheck)
            return $authCheck;

        $this->getSahamdirdekomModel()->hapus($id);
        session()->setFlashdata('message', 'Data berhasil dihapus');

        return redirect()->to(base_url('Shmusahadirdekom'));
    }

    private function updateData($id, $data, $errorMessage)
    {
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        if (!$kodebpr || !$periodeId) {
            return redirect()->back()->with('error', 'Kode BPR atau Periode tidak valid');
        }

        $data['user_id'] = $this->userId;
        $data['kodebpr'] = $kodebpr;

        if ($this->getSahamdirdekomModel()->editbasedkodedanperiode($data, $kodebpr, $periodeId, $id)) {
            session()->setFlashdata('message', 'Data berhasil diubah');
        } else {
            session()->setFlashdata('err', $errorMessage);
        }

        return redirect()->to(base_url('Shmusahadirdekom'));
    }

    public function ubahdir()
    {
        $id = $this->request->getPost('id');
        $nama = $this->request->getPost('nama');
        $nik = $this->request->getPost('nik');
        $usaha = $this->request->getPost('usaha');
        $persensaham = $this->request->getPost('persensaham');
        $persensahamlalu = $this->request->getPost('persensahamlalu');

        if (empty($nama) || empty($nik) || empty($usaha) || empty($persensaham) || empty($persensahamlalu)) {
            return redirect()->back()->with('error', 'Semua field harus diisi');
        }

        $data = [
            'nik' => $nik,
            'nama' => $nama,
            'usaha' => $usaha,
            'persensaham' => $persensaham,
            'persensahamlalu' => $persensahamlalu,
            'accdekom' => 0,
            'is_approved' => 0
        ];

        return $this->updateData($id, $data, 'Gagal mengubah data direksi');
    }

    public function ubahdekom()
    {
        $id = $this->request->getPost('id');
        $dekom = $this->request->getPost('dekom');
        $nikdekom = $this->request->getPost('nikdekom');
        $usahadekom = $this->request->getPost('usahadekom');
        $persenshmdekom = $this->request->getPost('persenshmdekom');
        $persenshmdekomlalu = $this->request->getPost('persenshmdekomlalu');

        if (empty($dekom) || empty($nikdekom) || empty($usahadekom) || empty($persenshmdekom) || empty($persenshmdekomlalu)) {
            return redirect()->back()->with('error', 'Semua field harus diisi');
        }

        $data = [
            'dekom' => $dekom,
            'nikdekom' => $nikdekom,
            'usahadekom' => $usahadekom,
            'persenshmdekom' => $persenshmdekom,
            'persenshmdekomlalu' => $persenshmdekomlalu,
            'accdekom' => 0,
            'is_approved' => 0
        ];

        return $this->updateData($id, $data, 'Gagal mengubah data dewan komisaris');
    }

    public function ubahpshm()
    {
        $id = $this->request->getPost('id');
        $pshm = $this->request->getPost('pshm');
        $nikpshm = $this->request->getPost('nikpshm');
        $usahapshm = $this->request->getPost('usahapshm');
        $persenpshm = $this->request->getPost('persenpshm');
        $persenpshmlalu = $this->request->getPost('persenpshmlalu');

        if (empty($pshm) || empty($nikpshm) || empty($usahapshm) || empty($persenpshm) || empty($persenpshmlalu)) {
            return redirect()->back()->with('error', 'Semua field harus diisi');
        }

        $data = [
            'pshm' => $pshm,
            'nikpshm' => $nikpshm,
            'usahapshm' => $usahapshm,
            'persenpshm' => $persenpshm,
            'persenpshmlalu' => $persenpshmlalu,
            'accdekom' => 0,
            'is_approved' => 0
        ];

        return $this->updateData($id, $data, 'Gagal mengubah data dewan komisaris');
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
        $subkategori = 'Shmusahadirdekom';
        $kodebpr = $this->request->getGet('kodebpr');
        $lastVisit = $this->request->getGet('last_visit');
        $periodeId = session('active_periode');

        $results = $this->getKomentarModel()
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

        $subkategori = 'Shmusahadirdekom';
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        $komentarList = $this->getKomentarModel()->getKomentarByFaktorId($subkategori, $kodebpr, $periodeId);

        return $this->response->setJSON($komentarList);
    }

    private function updateApprovalStatus($id, $isApproved, $successMessage, $errorMessage)
    {
        if (!is_numeric($id) || $id <= 0) {
            session()->setFlashdata('err', 'ID tidak valid.');
            return redirect()->back();
        }

        $data = $this->getSahamdirdekomModel()->find($id);
        if (!$data) {
            session()->setFlashdata('err', 'Data tidak ditemukan.');
            return redirect()->back();
        }

        $dataUpdate = [
            'id' => $id,
            'is_approved' => $isApproved,
            'approved_by' => $this->userId,
            'approved_at' => $isApproved ? date('Y-m-d H:i:s') : null, // Set null jika unapprove
        ];

        if ($this->getSahamdirdekomModel()->save($dataUpdate)) {
            session()->setFlashdata('message', $successMessage);
        } else {
            session()->setFlashdata('err', $errorMessage);
        }

        return redirect()->back();
    }

    public function approve($idshmusahadirdekom)
    {
        return $this->updateApprovalStatus(
            $idshmusahadirdekom,
            1,
            'Data berhasil disetujui.',
            'Terjadi kesalahan saat melakukan approval.'
        );
    }

    public function unapprove($idshmusahadirdekom)
    {
        return $this->updateApprovalStatus(
            $idshmusahadirdekom,
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

        $count = $this->getSahamdirdekomModel()
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
            $field . '_by' => $isApproved ? $userId : null,
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
            $updated = $this->getSahamdirdekomModel()
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

    // Approval umum
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

    public function setNullKolomTindak($id)
    {
        $authCheck = $this->checkAuthentication();
        if ($authCheck)
            return $authCheck;

        $result = $this->getPenjelastindakModel()->setNullKolomTindak($id);

        if ($result) {
            session()->setFlashdata('message', 'Data berhasil dihapus');
        } else {
            session()->setFlashdata('err', 'Data gagal dihapus');
        }

        return redirect()->to(base_url('Shmusahadirdekom'));
    }

    public function exporttxtshmusahadirdekom()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $this->shmusahadirdekomModel = model('M_shmusahadirdekom');
        $this->infobprModel = model('M_infobpr');
        $this->penjelastindakModel = model('M_penjelastindak');

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');
        $subkategori = "Shmusahadirdekom";

        // $periodeDetail = $this->periodeModel->find($periodeId);
        $periodeDetail = $this->getPeriodeModel()->getPeriodeDetail($periodeId);
        $exportDate = $periodeDetail['tahun'] ?? date('Y');

        $data_shmushdirdekom = $this->shmusahadirdekomModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
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

        // Helper function untuk cek apakah nilai kosong
        $isEmpty = function ($value) {
            return empty($value) || is_null($value) || $value === '' || $value === '0';
        };

        $output = "";
        $output .= "H01|" . $kodejenis . "|" . $sandibpr . "|" . $exportDate . "-12-31|LTBPRK|E0302|0|" . "\r\n";

        // Generate data untuk Direksi - hanya jika ada data yang tidak kosong
        foreach ($data_shmushdirdekom as $row) {
            if (isset($row['jabatan']) && $row['jabatan'] === 'Direksi') {
                $hasValidData = false;
                $nik = isset($row['nik']) && !$isEmpty($row['nik']) ? $row['nik'] : '';
                $usaha = isset($row['usaha']) && !$isEmpty($row['usaha']) ? $row['usaha'] : '';
                $persensaham = isset($row['persensaham']) && !$isEmpty($row['persensaham']) ? $row['persensaham'] : '';
                $persensahamlalu = isset($row['persensahamlalu']) && !$isEmpty($row['persensahamlalu']) ? $row['persensahamlalu'] : '';

                // Cek apakah ada minimal satu field yang tidak kosong
                if ($nik !== '' || $usaha !== '' || $persensaham !== '' || $persensahamlalu !== '') {
                    $hasValidData = true;
                }

                // Hanya generate jika ada data valid
                if ($hasValidData) {
                    $output .= "D01|" . "042010000000" . "|" . $nik . "|" . $usaha . "|" . $persensaham . "|" . $persensahamlalu . "\r\n";
                }
            }
        }

        // Generate data untuk Dewan Komisaris - hanya jika ada data yang tidak kosong
        foreach ($data_shmushdirdekom as $row) {
            if (isset($row['jabatan']) && $row['jabatan'] === 'Dekom') {
                $hasValidData = false;
                $nik = isset($row['nik']) && !$isEmpty($row['nik']) ? $row['nik'] : '';
                $usaha = isset($row['usaha']) && !$isEmpty($row['usaha']) ? $row['usaha'] : '';
                $persensaham = isset($row['persensaham']) && !$isEmpty($row['persensaham']) ? $row['persensaham'] : '';
                $persensahamlalu = isset($row['persensahamlalu']) && !$isEmpty($row['persensahamlalu']) ? $row['persensahamlalu'] : '';

                // Cek apakah ada minimal satu field yang tidak kosong
                if ($nik !== '' || $usaha !== '' || $persensaham !== '' || $persensahamlalu !== '') {
                    $hasValidData = true;
                }

                // Hanya generate jika ada data valid
                if ($hasValidData) {
                    $output .= "D01|" . "042010000000" . "|" . $nik . "|" . $usaha . "|" . $persensaham . "|" . $persensahamlalu . "\r\n";
                }
            }
        }

        // Generate data untuk PSHM - hanya jika ada data yang tidak kosong
        foreach ($data_shmushdirdekom as $row) {
            if (isset($row['jabatan']) && $row['jabatan'] === 'Psaham') {
                $hasValidData = false;
                $nik = isset($row['nik']) && !$isEmpty($row['nik']) ? $row['nik'] : '';
                $usaha = isset($row['usaha']) && !$isEmpty($row['usaha']) ? $row['usaha'] : '';
                $persensaham = isset($row['persensaham']) && !$isEmpty($row['persensaham']) ? $row['persensaham'] : '';
                $persensahamlalu = isset($row['persensahamlalu']) && !$isEmpty($row['persensahamlalu']) ? $row['persensahamlalu'] : '';
                $persensahamlalu = isset($row['persensahamlalu']) && !$isEmpty($row['persensahamlalu']) ? $row['persensahamlalu'] : '';

                // Cek apakah ada minimal satu field yang tidak kosong
                if ($nik !== '' || $usaha !== '' || $persensaham !== '' || $persensahamlalu !== '') {
                    $hasValidData = true;
                }

                // Hanya generate jika ada data valid
                if ($hasValidData) {
                    $output .= "D01|" . "042010000000" . "|" . $nik . "|" . $usaha . "|" . $persensaham . "|" . $persensahamlalu . "\r\n";
                }
            }
        }

        // Generate data penjelasan tindak lanjut - hanya jika tidak kosong
        foreach ($data_penjelastindak as $penjelas) {
            $tindaklanjut = str_replace(array("\r", "\n"), ' ', $penjelas['tindaklanjut']);
            if (
                !empty($penjelas['tindaklanjut']) &&
                $penjelas['tindaklanjut'] !== null &&
                trim($penjelas['tindaklanjut']) !== ''
            ) {
                $output .= "F01|" . $tindaklanjut . "\r\n";
            }
        }

        $filename = "LTBPRK-E0302-R-A-" . $exportDate . "1231-" . $sandibpr . "-01.txt";

        $response = service('response');
        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        return $response->setBody($output);
    }

}