<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_rapat;
use App\Models\M_infobpr;
use App\Models\M_user;
use App\Models\M_periodetahunan;
use App\Models\M_transparansicomments;
use App\Models\M_transparansicommentsread;
use App\Models\M_penjelastindak;
use Myth\Auth\Config\Services as AuthServices;

class Rapat extends Controller
{
    protected $auth;
    protected $session;
    protected $userKodebpr;
    protected $userId;

    protected $rapatModel;
    protected $userModel;
    protected $infobprModel;
    protected $periodeModel;
    protected $komentarModel;
    protected $commentReadsModel;
    protected $penjelastindakModel;

    private $userPermissions = null;
    private $userData = null;

    private const CACHE_TTL = 30;
    private const POLL_MIN_MS = 10000;
    private const SUBKAT = 'Rapat';

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

    private function getRapatModel(): M_rapat
    {
        return $this->rapatModel ??= new M_rapat();
    }
    private function getUserModel(): M_user
    {
        return $this->userModel ??= new M_user();
    }
    private function getPeriodeModel(): M_periodetahunan
    {
        return $this->periodeModel ??= new M_periodetahunan();
    }
    private function getKomentarModel(): M_transparansicomments
    {
        return $this->komentarModel ??= new M_transparansicomments();
    }
    private function getCommentReadsModel(): M_transparansicommentsread
    {
        return $this->commentReadsModel ??= new M_transparansicommentsread();
    }
    private function getPenjelastindakModel(): M_penjelastindak
    {
        return $this->penjelastindakModel ??= new M_penjelastindak();
    }
    private function getInfobprModel(): M_infobpr
    {
        return $this->infobprModel ??= new M_infobpr();
    }

    private function loadUserData(): void
    {
        if ($this->userData === null && $this->userId) {
            $this->userData = $this->getUserModel()->find($this->userId);
            $this->userKodebpr = $this->userData['kodebpr'] ?? null;
        }
    }

    private function getUserPermissions(): array
    {
        if ($this->userPermissions === null && $this->userId) {
            $authorize = AuthServices::authorization();
            $uid = $this->userId;
            foreach (['pe', 'admin', 'dekom', 'dekom2', 'dekom3', 'dekom4', 'dekom5', 'direksi', 'direksi2'] as $g) {
                $this->userPermissions[$g] = $authorize->inGroup($g, $uid);
            }
        }
        return $this->userPermissions ?? [];
    }

    private function checkAuthentication()
    {
        if (!$this->auth->check()) {
            $url = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($url);
        }
        return null;
    }

    private function getFreshRows(string $kodebpr, int $periodeId): array
    {
        $rows = $this->getRapatModel()
            ->select('id, tanggalrapat, jumlahpeserta, topikrapat, accdekom, accdekom_at, is_approved, approved_at')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->orderBy('tanggalrapat', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        return array_map(function ($r) {
            $r['tanggalrapat'] = isset($r['tanggalrapat'])
                ? date('Y-m-d', strtotime($r['tanggalrapat']))
                : '';
            return $r;
        }, $rows);
    }

    private function deriveApprovalBundle(array $rows): array
    {
        $allDekom = !empty($rows) && array_reduce($rows, fn($c, $r) => $c && (int) $r['accdekom'] === 1, true);
        $allDirut = !empty($rows) && array_reduce($rows, fn($c, $r) => $c && (int) $r['is_approved'] === 1, true);
        $lastDekom = $lastDirut = null;
        foreach ($rows as $r) {
            if (!empty($r['accdekom_at']))
                $lastDekom = $r['accdekom_at'];
            if (!empty($r['approved_at']))
                $lastDirut = $r['approved_at'];
        }
        return compact('allDekom', 'allDirut', 'lastDekom', 'lastDirut');
    }

    private function jsonOk(string $msg, array $extra = [])
    {
        return $this->response->setJSON(array_merge(
            ['status' => 'success', 'message' => $msg, 'csrf_hash' => csrf_hash(), 'ts' => time()],
            $extra
        ));
    }

    private function jsonErr(string $msg, int $code = 200)
    {
        return $this->response->setStatusCode($code)->setJSON([
            'status' => 'error',
            'message' => $msg,
            'csrf_hash' => csrf_hash(),
        ]);
    }

    private function requireAjax()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }
        return null;
    }

    private function requireAuth()
    {
        if (!$this->auth->check()) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }
        return null;
    }

    private function getIndexData(int $periodeId, string $kodebpr): array
    {
        $rows = $this->getFreshRows($kodebpr, $periodeId);

        $komentarList = $this->getKomentarModel()
            ->where('subkategori', self::SUBKAT)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->findAll();

        $penjelastindak = $this->getPenjelastindakModel()
            ->getDataPenjelasByKodebprAndPeriode(self::SUBKAT, $kodebpr, $periodeId);

        return compact('rows', 'komentarList', 'penjelastindak');
    }

    public function index()
    {
        if ($r = $this->checkAuthentication())
            return $r;
        if (!session('active_periode'))
            return redirect()->to('/Periodetransparansi');

        $periodeId = (int) session('active_periode');
        $kodebpr = $this->userKodebpr;

        if (!$kodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
        }

        $indexData = $this->getIndexData($periodeId, $kodebpr);
        $rows = $indexData['rows'];
        $approval = $this->deriveApprovalBundle($rows);
        $perms = $this->getUserPermissions();

        $accdekomData = array_map(fn($r) => [
            'accdekom' => $r['accdekom'],
            'accdekom_at' => $r['accdekom_at'],
        ], $rows);

        $accdirutData = array_map(fn($r) => [
            'is_approved' => $r['is_approved'],
            'approved_at' => $r['approved_at'],
        ], $rows);

        $lastVisit = session('last_visit_komentar') ?? date('Y-m-d H:i:s', strtotime('-1 day'));
        session()->set('last_visit_komentar', date('Y-m-d H:i:s'));

        $data = [
            'judul' => '13. Pelaksanaan Rapat dalam 1 (satu) tahun',
            'rapat' => $rows,
            'userId' => $this->userId,
            'komentarList' => $indexData['komentarList'],
            'userInGroupPE' => $perms['pe'] ?? false,
            'userInGroupAdmin' => $perms['admin'] ?? false,
            'userInGroupDekom' => $perms['dekom'] ?? false,
            'userInGroupDekom2' => $perms['dekom2'] ?? false,
            'userInGroupDekom3' => $perms['dekom3'] ?? false,
            'userInGroupDekom4' => $perms['dekom4'] ?? false,
            'userInGroupDekom5' => $perms['dekom5'] ?? false,
            'userInGroupDireksi' => $perms['direksi'] ?? false,
            'userInGroupDireksi2' => $perms['direksi2'] ?? false,
            'fullname' => $this->userData['fullname'] ?? 'Unknown',
            'kodebpr' => $kodebpr,
            'komentarModel' => $this->getKomentarModel(),
            'commentReadsModel' => $this->getCommentReadsModel(),
            'lastVisit' => $lastVisit,
            'periodeId' => $periodeId,
            'periodeDetail' => $this->getPeriodeModel()->getPeriodeDetail($periodeId),
            'bprData' => $this->getInfobprModel()->getBprByKode($kodebpr),
            'accdekomData' => $accdekomData,
            'accdirutData' => $accdirutData,
            'penjelastindak' => $indexData['penjelastindak'],
            'canApprove' => $approval['allDekom'],
            'pollIntervalMs' => self::POLL_MIN_MS,
        ];

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('Laporantahunan/Transparansi/rapat', $data);
        echo view('templates/v_footer');
    }

    public function tambahAjax()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;

        try {
            $kodebpr = $this->userKodebpr;
            $periodeId = (int) session('active_periode');

            if (!$kodebpr || !$periodeId)
                return $this->jsonErr('Kode BPR atau periode tidak valid');

            $tanggalrapat = trim($this->request->getPost('tanggalrapat') ?? '');
            $jumlahpeserta = trim($this->request->getPost('jumlahpeserta') ?? '');
            $topikrapat = trim($this->request->getPost('topikrapat') ?? '');

            if (!$tanggalrapat || !$jumlahpeserta || !$topikrapat) {
                return $this->jsonErr('Semua field wajib diisi');
            }
            if (!strtotime($tanggalrapat)) {
                return $this->jsonErr('Format tanggal tidak valid');
            }
            if (!is_numeric($jumlahpeserta) || (int) $jumlahpeserta < 1) {
                return $this->jsonErr('Jumlah peserta harus berupa angka positif');
            }

            $ok = $this->getRapatModel()->insert([
                'tanggalrapat' => $tanggalrapat,
                'jumlahpeserta' => (int) $jumlahpeserta,
                'topikrapat' => $topikrapat,
                'periode_id' => $periodeId,
                'user_id' => $this->userId,
                'kodebpr' => $kodebpr,
                'fullname' => $this->userData['fullname'] ?? null,
                'accdekom' => 0,
                'is_approved' => 0,
            ]);

            if (!$ok)
                return $this->jsonErr('Gagal menyimpan data');

            return $this->jsonOk('Data berhasil ditambahkan', ['data' => $this->getFreshRows($kodebpr, $periodeId)]);

        } catch (\Throwable $e) {
            log_message('error', '[Rapat::tambahAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function ubahAjax()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;

        try {
            $id = (int) $this->request->getPost('id');
            $kodebpr = $this->userKodebpr;
            $periodeId = (int) session('active_periode');

            if (!$id || !$kodebpr || !$periodeId)
                return $this->jsonErr('Data tidak valid');

            $existing = $this->getRapatModel()
                ->select('id')
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->first();

            if (!$existing)
                return $this->jsonErr('Data tidak ditemukan atau tidak memiliki akses');

            $tanggalrapat = trim($this->request->getPost('tanggalrapat') ?? '');
            $jumlahpeserta = trim($this->request->getPost('jumlahpeserta') ?? '');
            $topikrapat = trim($this->request->getPost('topikrapat') ?? '');

            if (!$tanggalrapat || !$jumlahpeserta || !$topikrapat) {
                return $this->jsonErr('Semua field wajib diisi');
            }
            if (!is_numeric($jumlahpeserta) || (int) $jumlahpeserta < 1) {
                return $this->jsonErr('Jumlah peserta harus berupa angka positif');
            }

            $ok = $this->getRapatModel()->editbasedkodedanperiode([
                'tanggalrapat' => $tanggalrapat,
                'jumlahpeserta' => (int) $jumlahpeserta,
                'topikrapat' => $topikrapat,
                'accdekom' => 0,
                'is_approved' => 0,
            ], $kodebpr, $periodeId, $id);

            if (!$ok)
                return $this->jsonErr('Gagal mengubah data');

            return $this->jsonOk('Data berhasil diubah', ['data' => $this->getFreshRows($kodebpr, $periodeId)]);

        } catch (\Throwable $e) {
            log_message('error', '[Rapat::ubahAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function hapusAjax()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;

        try {
            $id = (int) $this->request->getPost('id');
            $kodebpr = $this->userKodebpr;
            $periodeId = (int) session('active_periode');

            if (!$id || !$kodebpr || !$periodeId)
                return $this->jsonErr('Data tidak valid');

            $existing = $this->getRapatModel()
                ->select('id')
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->first();

            if (!$existing)
                return $this->jsonErr('Data tidak ditemukan atau tidak memiliki akses');

            $ok = $this->getRapatModel()
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->delete();

            if (!$ok)
                return $this->jsonErr('Gagal menghapus data');

            return $this->jsonOk('Data berhasil dihapus', ['data' => $this->getFreshRows($kodebpr, $periodeId)]);

        } catch (\Throwable $e) {
            log_message('error', '[Rapat::hapusAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function getKomentarByFaktorId()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(404)->setBody('Not Found');
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');
        $userId = $this->userId;

        $list = $this->getKomentarModel()->getKomentarByFaktorId(self::SUBKAT, $kodebpr, $periodeId);
        $unread = count(array_filter($list, fn($k) => (int) $k['user_id'] !== $userId));

        return $this->response->setJSON(['comments' => $list, 'unread_count' => $unread]);
    }

    public function getUnreadCommentCountForFactor()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $kodebpr = $this->userKodebpr;
        $userId = $this->userId;
        $periodeId = session('active_periode');

        if (!$kodebpr || !$userId || !$periodeId) {
            return $this->response->setStatusCode(400)->setJSON(['unread_count' => 0]);
        }

        $cache = service('cache');
        $cacheKey = 'unread_' . self::SUBKAT . "_{$kodebpr}_{$periodeId}_{$userId}";

        $count = $cache->remember($cacheKey, self::CACHE_TTL, function () use ($userId, $kodebpr, $periodeId) {
            return $this->getCommentReadsModel()
                ->countUnreadCommentsForUserByFactor(self::SUBKAT, $kodebpr, $userId, $periodeId);
        });

        return $this->response->setJSON(['unread_count' => (int) $count, 'next_poll_ms' => self::POLL_MIN_MS]);
    }

    public function Tambahkomentar()
    {
        if ($r = $this->checkAuthentication())
            return $r;

        $kodebpr = $this->userKodebpr;
        if (!$kodebpr) {
            return $this->request->isAJAX()
                ? $this->jsonErr('User tidak memiliki kode BPR yang valid')
                : redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
        }

        if ($this->request->isAJAX()) {
            $komentar = trim($this->request->getPost('komentar') ?? '');
            if (strlen($komentar) < 3)
                return $this->jsonErr('Komentar minimal 3 karakter');
            if (strlen($komentar) > 1000)
                return $this->jsonErr('Komentar maksimal 1000 karakter');

            $cache = service('cache');
            $rlKey = 'cmtrl_' . $this->userId;
            $rlCnt = (int) ($cache->get($rlKey) ?? 0);
            if ($rlCnt >= 10)
                return $this->jsonErr('Terlalu banyak komentar — tunggu sebentar');
            $cache->save($rlKey, $rlCnt + 1, 60);

            $this->getKomentarModel()->insertKomentar([
                'id' => self::SUBKAT,
                'subkategori' => self::SUBKAT,
                'komentar' => $komentar,
                'fullname' => $this->userData['fullname'] ?? '',
                'user_id' => $this->userId,
                'kodebpr' => $kodebpr,
                'periode_id' => session('active_periode'),
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            service('cache')->deleteMatching('unread_' . self::SUBKAT . "_{$kodebpr}_*");

            return $this->jsonOk('Komentar berhasil dikirim');
        }

        if (!isset($_POST['TambahKomentar'])) {
            return redirect()->to(base_url(self::SUBKAT));
        }

        $val = $this->validate([
            'komentar' => ['label' => 'Komentar', 'rules' => 'required|min_length[3]|max_length[1000]'],
        ]);
        if (!$val) {
            session()->setFlashdata('err', service('validation')->listErrors());
            return redirect()->back();
        }

        $this->getKomentarModel()->insertKomentar([
            'id' => self::SUBKAT,
            'subkategori' => self::SUBKAT,
            'komentar' => $this->request->getPost('komentar'),
            'fullname' => $this->request->getPost('fullname'),
            'user_id' => $this->userId,
            'kodebpr' => $kodebpr,
            'periode_id' => session('active_periode'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        session()->setFlashdata('message', 'Komentar berhasil ditambahkan');
        return redirect()->to(base_url(self::SUBKAT));
    }

    public function markUserCommentsAsRead()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $kodebpr = $this->userKodebpr;
        $userId = $this->userId;
        $periodeId = session('active_periode');

        if (!$kodebpr || !$userId || !$periodeId) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Missing data.']);
        }

        $ids = $this->getKomentarModel()
            ->select('id')
            ->where('subkategori', self::SUBKAT)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('user_id !=', $userId)
            ->findColumn('id');

        if (!empty($ids)) {
            foreach ($ids as $cid)
                $this->getCommentReadsModel()->markAsRead($cid, $userId);
        }

        service('cache')->delete("unread_" . self::SUBKAT . "_{$kodebpr}_{$periodeId}_{$userId}");

        return $this->response->setJSON(['status' => 'success', 'csrf_hash' => csrf_hash()]);
    }

    public function tambahketerangan()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;

        try {
            $kodebpr = $this->userKodebpr;
            $periodeId = session('active_periode');
            $tindaklanjut = trim($this->request->getPost('tindaklanjut') ?? '');

            if (!$kodebpr || !$periodeId)
                return $this->jsonErr('Kode BPR atau periode tidak valid');
            if (empty($tindaklanjut))
                return $this->jsonErr('Penjelasan tidak boleh kosong');

            $existing = $this->getPenjelastindakModel()
                ->getDataPenjelasByKodebprAndPeriode(self::SUBKAT, $kodebpr, $periodeId);
            if (!empty($existing))
                return $this->jsonErr('Data sudah ada. Gunakan Edit untuk mengubah.');

            $this->getPenjelastindakModel()->tambahpenjelastindak([
                'subkategori' => self::SUBKAT,
                'tindaklanjut' => $tindaklanjut,
                'kodebpr' => $kodebpr,
                'periode_id' => $periodeId,
                'fullname' => $this->userData['fullname'] ?? null,
                'user_id' => $this->userId,
            ]);

            $fresh = $this->getPenjelastindakModel()
                ->getDataPenjelasByKodebprAndPeriode(self::SUBKAT, $kodebpr, $periodeId);

            return $this->jsonOk('Penjelasan berhasil disimpan', ['penjelastindak' => $fresh]);

        } catch (\Throwable $e) {
            log_message('error', '[Rapat::tambahketerangan] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function editketerangan()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;

        try {
            $kodebpr = $this->userKodebpr;
            $periodeId = session('active_periode');
            $tindaklanjut = trim($this->request->getPost('tindaklanjut') ?? '');

            if (!$kodebpr || !$periodeId)
                return $this->jsonErr('Kode BPR atau periode tidak valid');
            if (empty($tindaklanjut))
                return $this->jsonErr('Penjelasan tidak boleh kosong');

            $ok = $this->getPenjelastindakModel()->editberdasarkankodedanperiode([
                'tindaklanjut' => $tindaklanjut,
                'user_id' => $this->userId,
                'kodebpr' => $kodebpr,
            ], self::SUBKAT, $kodebpr, $periodeId);

            if (!$ok)
                return $this->jsonErr('Gagal mengubah data');

            $fresh = $this->getPenjelastindakModel()
                ->getDataPenjelasByKodebprAndPeriode(self::SUBKAT, $kodebpr, $periodeId);

            return $this->jsonOk('Penjelasan berhasil diubah', ['penjelastindak' => $fresh]);

        } catch (\Throwable $e) {
            log_message('error', '[Rapat::editketerangan] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function setNullKolomTindakAjax()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;

        try {
            $id = (int) $this->request->getPost('id');
            $kodebpr = $this->userKodebpr;
            if (!$id)
                return $this->jsonErr('ID tidak valid');

            $existing = $this->getPenjelastindakModel()
                ->where('id', $id)->where('kodebpr', $kodebpr)->first();
            if (!$existing)
                return $this->jsonErr('Data tidak ditemukan atau tidak memiliki akses');

            $ok = $this->getPenjelastindakModel()
                ->where('id', $id)->where('kodebpr', $kodebpr)->delete();
            if (!$ok)
                return $this->jsonErr('Gagal menghapus data');

            return $this->jsonOk('Penjelasan berhasil dihapus');

        } catch (\Throwable $e) {
            log_message('error', '[Rapat::setNullKolomTindakAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    private function updateKomisarisApproval(int $val)
    {
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        if (!$kodebpr || !$periodeId) {
            session()->setFlashdata('err', 'Kode BPR atau Periode ID tidak valid');
            return redirect()->back();
        }

        $fields = ['accdekom' => $val, 'accdekom_by' => $this->userId, 'accdekom_at' => $val ? date('Y-m-d H:i:s') : null];
        if (!$val)
            $fields['is_approved'] = 0;

        try {
            $this->getRapatModel()
                ->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->update(null, $fields);
            session()->setFlashdata(
                $val ? 'message' : 'err',
                $val ? 'Persetujuan Komisaris Utama berhasil diberikan.' : 'Persetujuan Komisaris Utama dibatalkan.'
            );
        } catch (\Throwable $e) {
            log_message('error', '[Rapat::updateKomisarisApproval] ' . $e->getMessage());
            session()->setFlashdata('err', 'Terjadi kesalahan sistem');
        }

        return redirect()->back();
    }

    private function bulkApprove(array $fields, string $flashType, string $msg)
    {
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        if (!$kodebpr || !$periodeId) {
            session()->setFlashdata('err', 'Kode BPR atau Periode ID tidak valid');
            return redirect()->back();
        }

        try {
            $this->getRapatModel()
                ->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->update(null, array_merge($fields, [
                    'approved_by' => $this->userId,
                    'approved_at' => date('Y-m-d H:i:s'),
                ]));
            session()->setFlashdata($flashType, $msg);
        } catch (\Throwable $e) {
            log_message('error', '[Rapat::bulkApprove] ' . $e->getMessage());
            session()->setFlashdata('err', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    public function approveSemuaKom()
    {
        return $this->updateKomisarisApproval(1);
    }
    public function unapproveSemuaKom()
    {
        return $this->updateKomisarisApproval(0);
    }

    public function approveSemuaDirut()
    {
        return $this->bulkApprove(['is_approved' => 1], 'message', 'Persetujuan Direktur Utama berhasil diberikan.');
    }

    public function unapproveSemuaDirut()
    {
        return $this->bulkApprove(['is_approved' => 0, 'accdekom' => 0], 'err', 'Persetujuan Direktur Utama dibatalkan.');
    }

    public function exporttxtrapat()
    {
        if ($r = $this->checkAuthentication())
            return $r;

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');
        $periodeDetail = $this->getPeriodeModel()->getPeriodeDetail($periodeId);
        $exportDate = $periodeDetail['tahun'] ?? date('Y');
        $jenispelaporan = $periodeDetail['jenispelaporan'];

        $data_rows = $this->getRapatModel()->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        $data_infobpr = $this->getInfobprModel()->getDataByKodebpr($kodebpr);
        $data_penjelas = $this->getPenjelastindakModel()
            ->where('subkategori', self::SUBKAT)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
            ->findAll();

        $sandibpr = $data_infobpr[0]['sandibpr'] ?? '';
        $kodejenis = $data_infobpr[0]['kodejenis'] ?? '';

        $sanitize = function ($text): string {
            if ($text === null)
                return '';
            $text = (string) $text;
            $text = str_replace(["\r", "\n", "\t"], ' ', $text);
            $text = str_replace('|', ' ', $text);
            $text = str_replace(["\xE2\x80\x9C", "\xE2\x80\x9D"], '"', $text);
            $text = str_replace(["\xE2\x80\x98", "\xE2\x80\x99"], "'", $text);
            $text = str_replace('%', ' persen ', $text);
            $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
            return trim(preg_replace('/\s+/', ' ', $text));
        };

        $isEmpty = fn($v) => $v === null || $v === '';
        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}-12-31|LTBPRK|E0701|0|\r\n";

        foreach ($data_rows as $row) {
            $tanggalrapat = '';
            if (!$isEmpty($row['tanggalrapat'] ?? '')) {
                $ts = strtotime($row['tanggalrapat']);
                $tanggalrapat = $ts ? date('Ymd', $ts) : '';
            }
            $jumlahpeserta = $sanitize($row['jumlahpeserta'] ?? '');
            $topikrapat = $sanitize($row['topikrapat'] ?? '');
            if (!$isEmpty($tanggalrapat) || !$isEmpty($jumlahpeserta) || !$isEmpty($topikrapat)) {
                $output .= "D01|081010000000|{$tanggalrapat}|{$jumlahpeserta}|{$topikrapat}\r\n";
            }
        }

        foreach ($data_penjelas as $penjelas) {
            if (!empty($penjelas['tindaklanjut'])) {
                $output .= 'F01|' . $sanitize($penjelas['tindaklanjut']) . "\r\n";
            }
        }

        $filename = "LTBPRK-E0701-{$jenispelaporan}-A-{$exportDate}1231-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }
}