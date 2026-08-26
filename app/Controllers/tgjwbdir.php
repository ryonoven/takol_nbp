<?php
namespace App\Controllers;
use CodeIgniter\Controller;
use App\Models\M_tgjwbdir;
use App\Models\M_infobpr;
use App\Models\M_user;
use App\Models\M_periodetahunan;
use App\Models\M_transparansicomments;
use App\Models\M_transparansicommentsread;
use App\Models\M_penjelastindak;
use Myth\Auth\Config\Services as AuthServices;

class Tgjwbdir extends Controller
{
    protected $auth;
    protected $tgjwbdirModel;
    protected $userModel;
    protected $komentarModel;
    protected $infobprModel;
    protected $periodeModel;
    protected $session;
    protected $commentReadsModel;
    protected $penjelastindakModel;
    protected $userId;
    protected $userKodebpr;
    protected $userFullname;

    protected $userInGroupPE;
    protected $userInGroupAdmin;
    protected $userInGroupDekom;
    protected $userInGroupDekom2;
    protected $userInGroupDekom3;
    protected $userInGroupDekom4;
    protected $userInGroupDekom5;
    protected $userInGroupDireksi;
    protected $userInGroupDireksi2;
    private const SUBKATEGORI = 'Tgjwbdir';

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');

        $this->tgjwbdirModel = new M_tgjwbdir();
        $this->periodeModel = new M_periodetahunan();
        $this->userModel = new M_user();
        $this->komentarModel = new M_transparansicomments();
        $this->commentReadsModel = new M_transparansicommentsread();
        $this->infobprModel = new M_infobpr();
        $this->penjelastindakModel = new M_penjelastindak();
        $this->session = service('session');
        $this->auth = service('authentication');

        $userId = $this->auth->id();
        $user = cache()->remember("user_data_{$userId}", 300, function () use ($userId) {
            return $this->userModel
                ->select('kodebpr, fullname')
                ->find($userId);
        });

        $this->userId = $userId;
        $this->userKodebpr = $user['kodebpr'] ?? null;
        $this->userFullname = $user['fullname'] ?? 'Unknown';

        $authorize = AuthServices::authorization();

        $groups = cache()->remember("user_groups_{$userId}", 300, function () use ($authorize, $userId) {
            return [
                'pe' => $authorize->inGroup('pe', $userId),
                'admin' => $authorize->inGroup('admin', $userId),
                'dekom' => $authorize->inGroup('dekom', $userId),
                'dekom2' => $authorize->inGroup('dekom2', $userId),
                'dekom3' => $authorize->inGroup('dekom3', $userId),
                'dekom4' => $authorize->inGroup('dekom4', $userId),
                'dekom5' => $authorize->inGroup('dekom5', $userId),
                'direksi' => $authorize->inGroup('direksi', $userId),
                'direksi2' => $authorize->inGroup('direksi2', $userId),
            ];
        });

        $this->userInGroupPE = $groups['pe'];
        $this->userInGroupAdmin = $groups['admin'];
        $this->userInGroupDekom = $groups['dekom'];
        $this->userInGroupDekom2 = $groups['dekom2'];
        $this->userInGroupDekom3 = $groups['dekom3'];
        $this->userInGroupDekom4 = $groups['dekom4'];
        $this->userInGroupDekom5 = $groups['dekom5'];
        $this->userInGroupDireksi = $groups['direksi'];
        $this->userInGroupDireksi2 = $groups['direksi2'];
    }

    public function index()
    {
        if (!$this->auth->check()) {
            return redirect()->to(session('redirect_url') ?? '/login');
        }
        if (!session('active_periode')) {
            return redirect()->to('/Periodetransparansi');
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        if (!$kodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
        }

        $tgjwbdirAll = $this->tgjwbdirModel
            ->select('id, nik, direksi, jabatan, tugastgjwbdir, accdekom, accdekom_by, accdekom_at, komut, is_approved, approved_by, approved_at, dirut')
            ->where('periode_id', $periodeId)
            ->where('kodebpr', $kodebpr)
            ->orderBy('id', 'ASC')
            ->findAll();

        $totalRows = count($tgjwbdirAll);
        $allDekomApproved = $totalRows > 0
            && array_sum(array_column($tgjwbdirAll, 'accdekom')) === $totalRows;
        $allDirutApproved = $totalRows > 0
            && array_sum(array_column($tgjwbdirAll, 'is_approved')) === $totalRows;

        $canApprove = $allDekomApproved;
        $lastDekomAt = $allDekomApproved && $totalRows > 0
            ? $tgjwbdirAll[$totalRows - 1]['accdekom_at'] : null;
        $lastDirutAt = $allDirutApproved && $totalRows > 0
            ? $tgjwbdirAll[0]['approved_at'] : null;
        $komutName = $allDekomApproved && $totalRows > 0
            ? $tgjwbdirAll[$totalRows - 1]['komut'] : null;
        $dirutName = $allDirutApproved && $totalRows > 0
            ? $tgjwbdirAll[0]['dirut'] : null;
        $tgjwbdirData = array_slice($tgjwbdirAll, 0, 10);

        $komentarList = cache()->remember(
            $this->komentarCacheKey($kodebpr, $periodeId),
            60,
            function () use ($kodebpr, $periodeId) {
                return $this->komentarModel
                    ->where('subkategori', self::SUBKATEGORI)
                    ->where('kodebpr', $kodebpr)
                    ->where('periode_id', $periodeId)
                    ->findAll();
            }
        );

        $periodeDetail = $this->periodeModel->getPeriodeDetail($periodeId);

        $bprData = $this->infobprModel->getBprByKode($kodebpr);
        $penjelastindak = $this->penjelastindakModel
            ->getDataPenjelasByKodebprAndPeriode(self::SUBKATEGORI, $kodebpr, $periodeId);

        $lastVisit = session('last_visit_komentar') ?? date('Y-m-d H:i:s', strtotime('-1 day'));
        session()->set('last_visit_komentar', date('Y-m-d H:i:s'));

        $data = [
            'judul' => '2. Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi',
            'tgjwbdir' => $tgjwbdirData,
            'useDropify' => false,
            'userId' => $this->userId,
            'fullname' => $this->userFullname,
            'kodebpr' => $kodebpr,
            'periodeId' => $periodeId,
            'periodeDetail' => $periodeDetail,
            'periodetransparansi' => $periodeDetail,
            'bprData' => $bprData,
            'penjelastindak' => $penjelastindak,
            'komentarList' => $komentarList,
            'lastVisit' => $lastVisit,
            'userInGroupPE' => $this->userInGroupPE,
            'userInGroupAdmin' => $this->userInGroupAdmin,
            'userInGroupDekom' => $this->userInGroupDekom,
            'userInGroupDekom2' => $this->userInGroupDekom2,
            'userInGroupDekom3' => $this->userInGroupDekom3,
            'userInGroupDekom4' => $this->userInGroupDekom4,
            'userInGroupDekom5' => $this->userInGroupDekom5,
            'userInGroupDireksi' => $this->userInGroupDireksi,
            'userInGroupDireksi2' => $this->userInGroupDireksi2,
            'canApprove' => $canApprove,
            'allDekomApproved' => $allDekomApproved,
            'allDirutApproved' => $allDirutApproved,
            'lastDekomAt' => $lastDekomAt,
            'lastDirutAt' => $lastDirutAt,
            'komutName' => $komutName,
            'dirutName' => $dirutName,
        ];

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('Laporantahunan/Transparansi/tgjwbdir', $data);
        echo view('templates/v_footer');
    }

    public function tambahtgjwbdir()
    {
        if (!$this->auth->check()) {
            return redirect()->to(session('redirect_url') ?? '/login');
        }
        if (!isset($_POST['tambahtgjwbdir'])) {
            return redirect()->to(base_url('Tgjwbdir'));
        }

        $val = $this->validate([
            'direksi' => ['label' => 'Nama Direksi', 'rules' => 'required'],
            'jabatan' => ['label' => 'Jabatan', 'rules' => 'required|in_list[0100,0101,0200,0201]'],
            'nik' => ['label' => 'NIK', 'rules' => 'required|numeric|min_length[16]|max_length[16]'],
            'tugastgjwbdir' => ['label' => 'Pelaksanaan Tugas', 'rules' => 'required'],
        ]);

        if (!$val) {
            session()->setFlashdata('err', \Config\Services::validation()->listErrors());
            return redirect()->back();
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        if (!$kodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
        }

        $data = [
            'nik' => $this->request->getPost('nik'),
            'direksi' => $this->request->getPost('direksi'),
            'jabatan' => $this->request->getPost('jabatan'),
            'tugastgjwbdir' => $this->request->getPost('tugastgjwbdir'),
            'periode_id' => $periodeId,
            'user_id' => $this->userId,
            'kodebpr' => $kodebpr,
            'fullname' => $this->userFullname,
            'accdekom' => 0,
            'is_approved' => 0,
        ];

        if ($this->tgjwbdirModel->tambahtgjwbdir($data)) {
            $this->resetApprovalBulk($kodebpr, $periodeId);
            session()->setFlashdata('message', 'Data berhasil ditambahkan. Status approval telah direset.');
            return redirect()->to(base_url('Tgjwbdir'));
        }

        return redirect()->back();
    }

    public function ubah()
    {
        $id = $this->request->getPost('id');
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        if (!$kodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
        }

        $direksi = $this->request->getPost('direksi');
        $jabatan = $this->request->getPost('jabatan');
        $nik = $this->request->getPost('nik');
        $tugastgjwbdir = $this->request->getPost('tugastgjwbdir');

        if (empty($direksi) || empty($nik) || empty($tugastgjwbdir) || empty($jabatan)) {
            return redirect()->back()->with('error', 'Data tidak boleh kosong');
        }

        $data = [
            'nik' => $nik,
            'direksi' => $direksi,
            'jabatan' => $jabatan,
            'tugastgjwbdir' => $tugastgjwbdir,
            'user_id' => $this->userId,
            'kodebpr' => $kodebpr,
            'accdekom' => 0,
            'is_approved' => 0,
        ];

        if ($this->tgjwbdirModel->editbasedkodedanperiode($data, $kodebpr, $periodeId, $id)) {
            $this->resetApprovalBulk($kodebpr, $periodeId);
            session()->setFlashdata('message', 'Data berhasil diubah. Status approval telah direset.');
        } else {
            session()->setFlashdata('err', 'Gagal mengubah data');
        }

        return redirect()->to(base_url('Tgjwbdir'));
    }

    public function hapus($id, $kodebpr = null, $periodeId = null)
    {
        if (!$this->auth->check()) {
            return redirect()->to(session('redirect_url') ?? '/login');
        }

        $kodebpr = $kodebpr ?? $this->userKodebpr;
        $periodeId = $periodeId ?? session('active_periode');

        if (!$id || !$kodebpr || !$periodeId) {
            session()->setFlashdata('err', 'Data tidak valid');
            return redirect()->to(base_url('Tgjwbdir'));
        }

        $existing = $this->tgjwbdirModel
            ->where('id', $id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if (!$existing) {
            session()->setFlashdata('err', 'Data tidak ditemukan');
            return redirect()->to(base_url('Tgjwbdir'));
        }

        $this->tgjwbdirModel
            ->where('id', $id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->delete();

        session()->setFlashdata('message', 'Data berhasil dihapus');
        return redirect()->to(base_url('Tgjwbdir'));
    }

    public function hapusAjax()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }
        if (!$this->auth->check()) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $id = (int) $this->request->getPost('id');
        $kodebpr = $this->userKodebpr;
        $periodeId = (int) session('active_periode');

        if (!$id || !$kodebpr || !$periodeId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak valid']);
        }

        $existing = $this->tgjwbdirModel
            ->where('id', $id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if (!$existing) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak ditemukan']);
        }

        try {
            $deleted = $this->tgjwbdirModel
                ->where('id', $id)
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->delete();

            if (!$deleted) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menghapus data']);
            }

            $rows = $this->tgjwbdirModel
                ->select('id, nik, direksi, jabatan, tugastgjwbdir, accdekom, is_approved')
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->orderBy('id', 'ASC')
                ->findAll();

            $total = count($rows);
            $canApprove = $total > 0
                && array_sum(array_column($rows, 'accdekom')) === $total;

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Data berhasil dihapus',
                'data' => $rows,
                'canApprove' => $canApprove,
            ]);

        } catch (\Exception $e) {
            log_message('error', '[Tgjwbdir::hapusAjax] ' . $e->getMessage());
            return $this->response->setJSON(['status' => 'error', 'message' => 'Terjadi kesalahan sistem']);
        }
    }

    public function getDataAjax()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }
        if (!$this->auth->check()) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = (int) session('active_periode');

        if (!$kodebpr || !$periodeId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Kode BPR atau periode tidak valid']);
        }

        try {
            $rows = $this->tgjwbdirModel
                ->select('id, nik, direksi, jabatan, tugastgjwbdir, accdekom, is_approved')
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->orderBy('id', 'ASC')
                ->findAll();

            return $this->response->setJSON(['status' => 'success', 'data' => $rows]);

        } catch (\Exception $e) {
            log_message('error', '[Tgjwbdir::getDataAjax] ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => 'Terjadi kesalahan sistem']);
        }
    }

    public function getUnreadCommentCountForFactor()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $id = $this->request->getGet('id');
        $kodebpr = $this->userKodebpr;
        $userId = $this->userId;
        $periodeId = session('active_periode');

        if (!$id || !$kodebpr || !$userId || !$periodeId) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Missing data.']);
        }

        $cacheKey = "unread_{$id}_{$kodebpr}_{$userId}_{$periodeId}";
        $count = cache()->remember($cacheKey, 30, function () use ($id, $kodebpr, $userId, $periodeId) {
            return $this->commentReadsModel
                ->countUnreadCommentsForUserByFactor($id, $kodebpr, $userId, $periodeId);
        });

        return $this->response->setJSON(['unread_count' => $count]);
    }

    public function cekKomentarBaru()
    {
        $kodebpr = $this->request->getGet('kodebpr');
        $lastVisit = $this->request->getGet('last_visit');
        $periodeId = session('active_periode');

        $results = $this->komentarModel
            ->select('id, COUNT(*) as jumlah')
            ->where('subkategori', self::SUBKATEGORI)
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

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        $komentarList = $this->komentarModel
            ->getKomentarByFaktorId(self::SUBKATEGORI, $kodebpr, $periodeId);

        return $this->response->setJSON($komentarList);
    }

    public function Tambahkomentar()
    {
        if (!$this->auth->check()) {
            return redirect()->to('/login');
        }
        if (!isset($_POST['TambahKomentar'])) {
            return redirect()->to(base_url('Tgjwbdir'));
        }

        $kodebpr = $this->userKodebpr;
        if (!$kodebpr) {
            session()->setFlashdata('error', 'User tidak memiliki kode BPR yang valid');
            return redirect()->back();
        }

        $val = $this->validate([
            'komentar' => ['label' => 'Komentar', 'rules' => 'required'],
        ]);

        if (!$val) {
            session()->setFlashdata('err', \Config\Services::validation()->listErrors());
            return redirect()->back();
        }

        $periodeId = session('active_periode');

        $data = [
            'id' => $this->request->getPost('id'),
            'subkategori' => self::SUBKATEGORI,
            'komentar' => $this->request->getPost('komentar'),
            'fullname' => $this->request->getPost('fullname'),
            'user_id' => $this->userId,
            'kodebpr' => $kodebpr,
            'periode_id' => $periodeId,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $this->komentarModel->insertKomentar($data);
        $this->invalidateKomentarCache($kodebpr, $periodeId);

        session()->setFlashdata('message', 'Komentar berhasil ditambahkan');
        return redirect()->to(base_url('Tgjwbdir') . '?modal_komentar=' . $this->request->getPost('id'));
    }

    public function markUserCommentsAsRead()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $id = $this->request->getPost('id');
        $kodebpr = $this->userKodebpr;
        $userId = $this->userId;
        $periodeId = session('active_periode');

        if (!$id || !$kodebpr || !$userId || !$periodeId) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Missing data.']);
        }

        $commentsToMark = $this->komentarModel
            ->select('id')
            ->where('subkategori', $id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('user_id !=', $userId)
            ->findAll();

        if (!empty($commentsToMark)) {
            $now = date('Y-m-d H:i:s');
            $batchData = array_map(fn($c) => [
                'comment_id' => $c['id'],
                'user_id' => $userId,
                'read_at' => $now,
            ], $commentsToMark);

            $this->commentReadsModel->insertBatch($batchData);
        }

        cache()->delete("unread_{$id}_{$kodebpr}_{$userId}_{$periodeId}");

        return $this->response->setJSON(['status' => 'success']);
    }

    public function tambahketerangan()
    {
        if (!$this->auth->check()) {
            return redirect()->to(session('redirect_url') ?? '/login');
        }
        if (!isset($_POST['tambahketerangan'])) {
            return redirect()->to(base_url('Tgjwbdir'));
        }

        $val = $this->validate([
            'tindaklanjut' => ['label' => 'Tindak Lanjut', 'rules' => 'required'],
        ]);

        if (!$val) {
            session()->setFlashdata('err', \Config\Services::validation()->listErrors());
            return redirect()->back()->withInput();
        }

        $kodebpr = $this->userKodebpr;
        if (!$kodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
        }

        $penjelastindak = [
            'subkategori' => self::SUBKATEGORI,
            'tindaklanjut' => $this->request->getPost('tindaklanjut'),
            'penjelasanlanjut' => $this->request->getPost('penjelasanlanjut'),
            'kodebpr' => $kodebpr,
            'periode_id' => session('active_periode'),
            'fullname' => $this->userFullname,
        ];

        $this->penjelastindakModel->tambahpenjelastindak($penjelastindak);
        session()->setFlashdata('message', 'Data berhasil ditambahkan');

        return redirect()->to(base_url('Tgjwbdir'));
    }

    public function editketerangan()
    {
        $id = $this->request->getPost('id');
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        if (!$kodebpr || !$periodeId) {
            return redirect()->back()->with('error', 'Data tidak valid');
        }

        $tindaklanjut = $this->request->getPost('tindaklanjut');
        $penjelasanlanjut = $this->request->getPost('penjelasanlanjut');

        if (empty($tindaklanjut) || empty($penjelasanlanjut)) {
            return redirect()->back()->with('error', 'Tindak Lanjut atau Penjelasan tidak boleh kosong');
        }

        $data = [
            'tindaklanjut' => $tindaklanjut,
            'penjelasanlanjut' => $penjelasanlanjut,
            'user_id' => $this->userId,
            'kodebpr' => $kodebpr,
        ];

        if ($this->penjelastindakModel->editberdasarkankodedanperiode($data, self::SUBKATEGORI, $kodebpr, $periodeId)) {
            session()->setFlashdata('message', 'Data berhasil diubah');
        } else {
            session()->setFlashdata('err', 'Gagal mengubah data');
        }

        return redirect()->to(base_url('Tgjwbdir'));
    }

    public function approve($idTgjwbdir)
    {
        if (!is_numeric($idTgjwbdir) || $idTgjwbdir <= 0) {
            session()->setFlashdata('err', 'ID tidak valid.');
            return redirect()->back();
        }

        $tgjwbdir = $this->tgjwbdirModel->find($idTgjwbdir);
        if (!$tgjwbdir) {
            session()->setFlashdata('err', 'Data tidak ditemukan.');
            return redirect()->back();
        }

        $this->tgjwbdirModel->save([
            'id' => $idTgjwbdir,
            'is_approved' => 1,
            'approved_by' => $this->userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ]);

        session()->setFlashdata('message', 'Data berhasil disetujui.');
        return redirect()->back();
    }

    public function unapprove($idTgjwbdir)
    {
        if (!is_numeric($idTgjwbdir) || $idTgjwbdir <= 0) {
            session()->setFlashdata('err', 'ID tidak valid.');
            return redirect()->back();
        }

        $this->tgjwbdirModel->save([
            'id' => $idTgjwbdir,
            'is_approved' => 0,
            'approved_by' => $this->userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ]);

        session()->setFlashdata('err', 'Approval dibatalkan.');
        return redirect()->back();
    }

    private function bulkUpdate(array $data, string $message, bool $isError = false): \CodeIgniter\HTTP\RedirectResponse
    {
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        if (!$kodebpr || !$periodeId) {
            session()->setFlashdata('err', 'Kode BPR atau Periode ID tidak valid');
            return redirect()->back();
        }

        $count = $this->tgjwbdirModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->countAllResults();

        if ($count === 0) {
            session()->setFlashdata('err', 'Tidak ada data untuk diupdate');
            return redirect()->back();
        }

        try {
            $updated = $this->tgjwbdirModel
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->update(null, $data);

            if (!$updated) {
                session()->setFlashdata('err', 'Gagal mengupdate data');
                return redirect()->back();
            }

            $isError
                ? session()->setFlashdata('err', $message)
                : session()->setFlashdata('message', $message);

        } catch (\Exception $e) {
            log_message('error', 'Tgjwbdir bulkUpdate: ' . $e->getMessage());
            session()->setFlashdata('err', 'Terjadi kesalahan sistem');
        }

        return redirect()->back();
    }

    public function approveSemuaKom()
    {
        return $this->bulkUpdate([
            'accdekom' => 1,
            'accdekom_by' => $this->userId,
            'accdekom_at' => date('Y-m-d H:i:s'),
            'komut' => $this->userFullname,
        ], 'Data berhasil disetujui.');
    }

    public function unapproveSemuaKom()
    {
        return $this->bulkUpdate([
            'accdekom' => 0,
            'is_approved' => 0,
            'accdekom_by' => $this->userId,
            'accdekom_at' => date('Y-m-d H:i:s'),
            'komut' => $this->userFullname,
        ], 'Approval dibatalkan.', true);
    }

    public function approveSemuaDirut()
    {
        return $this->bulkUpdate([
            'is_approved' => 1,
            'approved_by' => $this->userId,
            'approved_at' => date('Y-m-d H:i:s'),
            'dirut' => $this->userFullname,
        ], 'Data berhasil disetujui.');
    }

    public function unapproveSemuaDirut()
    {
        return $this->bulkUpdate([
            'is_approved' => 0,
            'accdekom' => 0,
            'approved_by' => $this->userId,
            'approved_at' => date('Y-m-d H:i:s'),
            'dirut' => $this->userFullname,
        ],  'Approval dibatalkan.', true);
    }

    public function setNullKolomTindak($id)
    {
        if (!$this->auth->check()) {
            return redirect()->to(session('redirect_url') ?? '/login');
        }

        $this->penjelastindakModel->setNullKolomTindak($id)
            ? session()->setFlashdata('message', 'Data berhasil dihapus')
            : session()->setFlashdata('err', 'Data gagal dihapus');

        return redirect()->to(base_url('Tgjwbdir'));
    }

    public function setNullKolomPenjelaslanjut($id)
    {
        if (!$this->auth->check()) {
            return redirect()->to(session('redirect_url') ?? '/login');
        }

        $this->penjelastindakModel->setNullKolomPenjelaslanjut($id)
            ? session()->setFlashdata('message', 'Data berhasil dihapus')
            : session()->setFlashdata('err', 'Data gagal dihapus');

        return redirect()->to(base_url('Tgjwbdir'));
    }

    public function exporttxttgjwbdir()
    {
        if (!$this->auth->check()) {
            return redirect()->to(session('redirect_url') ?? '/login');
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        $periodeDetail = $this->periodeModel->find($periodeId);
        $exportDate = $periodeDetail['tahun'] ?? date('Y');
        $jenispelaporan = $periodeDetail['jenispelaporan'];

        $data_tgjwbdir = $this->tgjwbdirModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);
        $data_penjelastindak = $this->penjelastindakModel
            ->where('subkategori', self::SUBKATEGORI)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->findAll();

        $sandibpr = $data_infobpr[0]['sandibpr'] ?? '';
        $kodejenis = $data_infobpr[0]['kodejenis'] ?? '';

        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}-12-31|LTBPRK|E0201|0|\r\n";

        foreach ($data_tgjwbdir as $row) {
            $nik = $this->sanitize($row['nik'] ?? '');
            $tugas = $this->sanitize($row['tugastgjwbdir'] ?? '');
            $output .= "D01|011000000000|{$nik}|{$tugas}\r\n";
        }

        foreach ($data_penjelastindak as $penjelas) {
            if (!empty($penjelas['tindaklanjut'])) {
                $output .= 'F01|' . $this->sanitize($penjelas['tindaklanjut']) . "\r\n";
            }
        }
        foreach ($data_penjelastindak as $penjelas) {
            if (!empty($penjelas['penjelasanlanjut'])) {
                $output .= 'F02|' . $this->sanitize($penjelas['penjelasanlanjut']) . "\r\n";
            }
        }

        $filename = "LTBPRK-E0201-{$jenispelaporan}-A-{$exportDate}1231-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }

    private function invalidateKomentarCache(string $kodebpr, $periodeId): void
    {
        cache()->delete($this->komentarCacheKey($kodebpr, $periodeId));
    }

    private function komentarCacheKey(string $kodebpr, $periodeId): string
    {
        return 'komentar_' . self::SUBKATEGORI . "_{$kodebpr}_{$periodeId}";
    }

    private function resetApprovalBulk(string $kodebpr, $periodeId): void
    {
        $this->tgjwbdirModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->update(null, [
                'accdekom' => 0,
                'is_approved' => 0,
            ]);
    }

    private function sanitize(?string $text): string
    {
        if ($text === null)
            return '';
        $text = str_replace(["\r", "\n", "\t", '|'], [' ', ' ', ' ', ' '], $text);
        $text = str_replace(["\xE2\x80\x9C", "\xE2\x80\x9D"], '"', $text);
        $text = str_replace(["\xE2\x80\x98", "\xE2\x80\x99"], "'", $text);
        $text = str_replace('%', ' persen ', $text);
        $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
        return trim(preg_replace('/\s+/', ' ', $text));
    }
}