<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_masalahhukum;
use App\Models\M_infobpr;
use App\Models\M_user;
use App\Models\M_periodetahunan;
use App\Models\M_transparansicomments;
use App\Models\M_transparansicommentsread;
use App\Models\M_penjelastindak;
use Myth\Auth\Config\Services as AuthServices;

class masalahhukum extends Controller
{
    protected $auth;
    protected $session;
    protected $userKodebpr;
    protected $userId;

    protected $masalahhukumModel;
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
    private const SUBKAT = 'Masalahhukum';

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

    private function getMasalahhukumModel(): M_masalahhukum
    {
        return $this->masalahhukumModel ??= new M_masalahhukum();
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

    /* INDEX */

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

        $rows = $this->getMasalahhukumModel()
            ->select('*, accdekom, accdekom_by, accdekom_at, is_approved, approved_by, approved_at')
            ->where('periode_id', $periodeId)
            ->where('kodebpr', $kodebpr)
            ->limit(1)
            ->findAll();

        $approval = $this->deriveApprovalBundle($rows);
        $perms = $this->getUserPermissions();

        $penjelastindak = $this->getPenjelastindakModel()
            ->getDataPenjelasByKodebprAndPeriode(self::SUBKAT, $kodebpr, $periodeId);

        $accdekomData = array_map(fn($r) => [
            'accdekom' => $r['accdekom'],
            'accdekom_by' => $r['accdekom_by'],
            'accdekom_at' => $r['accdekom_at'],
        ], $rows);

        $accdirutData = array_map(fn($r) => [
            'is_approved' => $r['is_approved'],
            'approved_by' => $r['approved_by'],
            'approved_at' => $r['approved_at'],
        ], $rows);

        $data = [
            'judul' => '16. Permasalahan Hukum yang Dihadapi',
            'masalahhukum' => $rows,
            'userId' => $this->userId,
            'userInGroupPE' => $perms['pe'] ?? false,
            'userInGroupAdmin' => $perms['admin'] ?? false,
            'userInGroupDekom' => $perms['dekom'] ?? false,
            'userInGroupDekom2' => $perms['dekom2'] ?? false,
            'userInGroupDireksi' => $perms['direksi'] ?? false,
            'userInGroupDireksi2' => $perms['direksi2'] ?? false,
            'fullname' => $this->userData['fullname'] ?? 'Unknown',
            'kodebpr' => $kodebpr,
            'periodeId' => $periodeId,
            'periodeDetail' => $this->getPeriodeModel()->getPeriodeDetail($periodeId),
            'bprData' => $this->getInfobprModel()->getBprByKode($kodebpr),
            'accdekomData' => $accdekomData,
            'accdirutData' => $accdirutData,
            'penjelastindak' => $penjelastindak,
            'canApprove' => $approval['allDekom'],
            'pollIntervalMs' => self::POLL_MIN_MS,
            'isNullRecord' => (int) ($rows[0]['is_null_record'] ?? 0),
        ];

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('Laporantahunan/Transparansi/masalahhukum', $data);
        echo view('templates/v_footer');
    }

    /* SAVE */

    public function tambahpenjelasAjax()
    {
        if ($e = $this->requireAuth())
            return $e;
        if ($this->request->getMethod() !== 'post')
            return $this->jsonErr('Invalid request', 400);

        $fields = ['hukumperdataselesai', 'hukumpidanaselesai', 'hukumperdataproses', 'hukumpidanaproses', 'totalselesai', 'totalproses'];
        foreach ($fields as $f) {
            if ($this->request->getPost($f) === null)
                return $this->jsonErr('Field ' . $f . ' tidak boleh kosong');
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');
        if (!$kodebpr || !$periodeId)
            return $this->jsonErr('Kode BPR atau periode tidak valid');

        $existing = $this->getMasalahhukumModel()
            ->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();

        $data = [];
        foreach ($fields as $f)
            $data[$f] = (int) ($this->request->getPost($f) ?? 0);
        $data['periode_id'] = $periodeId;
        $data['user_id'] = $this->userId;
        $data['kodebpr'] = $kodebpr;
        $data['fullname'] = $this->userData['fullname'] ?? null;
        $data['accdekom'] = 0;
        $data['is_approved'] = 0;
        $data['is_null_record'] = 0;

        if ($existing) {
            $data['id'] = $existing['id'];
            $ok = $this->getMasalahhukumModel()->save($data);
            return $ok ? $this->jsonOk('Data berhasil diperbarui') : $this->jsonErr('Gagal memperbarui data');
        }

        unset($data['id']);
        $ok = $this->getMasalahhukumModel()->insert($data);
        return $ok ? $this->jsonOk('Data berhasil ditambahkan') : $this->jsonErr('Gagal menambahkan data');
    }

    /* KOMENTAR */

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

        if (!isset($_POST['TambahKomentar']))
            return redirect()->to(base_url(self::SUBKAT));

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

        service('cache')->delete('unread_' . self::SUBKAT . "_{$kodebpr}_{$periodeId}_{$userId}");
        return $this->response->setJSON(['status' => 'success', 'csrf_hash' => csrf_hash()]);
    }

    public function setNodataAjax()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;

        $perms = $this->getUserPermissions();
        if (!($perms['pe'] ?? false) && !($perms['admin'] ?? false))
            return $this->jsonErr('Tidak memiliki akses', 403);

        try {
            $kodebpr = $this->userKodebpr;
            $periodeId = (int) session('active_periode');

            if (!$kodebpr || !$periodeId)
                return $this->jsonErr('Kode BPR atau periode tidak valid');

            $existing = $this->getMasalahhukumModel()
                ->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();

            if ($existing) {
                $ok = $this->getMasalahhukumModel()
                    ->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                    ->update(null, ['is_null_record' => 1, 'accdekom' => 0, 'is_approved' => 0]);
            } else {
                $ok = $this->getMasalahhukumModel()->insert([
                    'hukumperdataselesai' => 0,
                    'hukumpidanaselesai' => 0,
                    'hukumperdataproses' => 0,
                    'hukumpidanaproses' => 0,
                    'totalselesai' => 0,
                    'totalproses' => 0,
                    'is_null_record' => 1,
                    'periode_id' => $periodeId,
                    'user_id' => $this->userId,
                    'kodebpr' => $kodebpr,
                    'fullname' => $this->userData['fullname'] ?? null,
                    'accdekom' => 0,
                    'is_approved' => 0,
                ]);
            }

            if (!$ok)
                return $this->jsonErr('Gagal menyimpan data');

            return $this->jsonOk('Data berhasil ditandai sebagai tidak ada');

        } catch (\Throwable $e) {
            log_message('error', '[masalahhukum::setNodataAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function batalNodataAjax()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;

        $perms = $this->getUserPermissions();
        if (!($perms['pe'] ?? false) && !($perms['admin'] ?? false))
            return $this->jsonErr('Tidak memiliki akses', 403);

        try {
            $kodebpr = $this->userKodebpr;
            $periodeId = (int) session('active_periode');

            if (!$kodebpr || !$periodeId)
                return $this->jsonErr('Data tidak valid');

            $ok = $this->getMasalahhukumModel()
                ->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->update(null, ['is_null_record' => 0]);

            return $ok ? $this->jsonOk('Status berhasil dibatalkan') : $this->jsonErr('Gagal membatalkan status');

        } catch (\Throwable $e) {
            log_message('error', '[masalahhukum::batalNodataAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function tambahketerangan()
    {
        if ($r = $this->checkAuthentication())
            return $r;

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');
        $isAjax = $this->request->isAJAX();

        if (!$kodebpr) {
            return $isAjax ? $this->jsonErr('Kode BPR tidak valid') : redirect()->back()->with('error', 'Kode BPR tidak valid');
        }

        $tindaklanjut = trim($this->request->getPost('tindaklanjut') ?? '');
        if (empty($tindaklanjut)) {
            return $isAjax ? $this->jsonErr('Penjelasan tidak boleh kosong') : redirect()->back()->with('error', 'Penjelasan tidak boleh kosong');
        }

        $existing = $this->getPenjelastindakModel()
            ->getDataPenjelasByKodebprAndPeriode(self::SUBKAT, $kodebpr, $periodeId);
        if (!empty($existing)) {
            return $isAjax ? $this->jsonErr('Data sudah ada. Gunakan Edit untuk mengubah.') : redirect()->back()->with('error', 'Data sudah ada.');
        }

        $this->getPenjelastindakModel()->tambahpenjelastindak([
            'subkategori' => self::SUBKAT,
            'tindaklanjut' => $tindaklanjut,
            'kodebpr' => $kodebpr,
            'periode_id' => $periodeId,
            'fullname' => $this->userData['fullname'] ?? null,
            'user_id' => $this->userId,
        ]);

        if ($isAjax) {
            $fresh = $this->getPenjelastindakModel()
                ->getDataPenjelasByKodebprAndPeriode(self::SUBKAT, $kodebpr, $periodeId);
            return $this->jsonOk('Penjelasan berhasil disimpan', ['penjelastindak' => $fresh]);
        }

        session()->setFlashdata('message', 'Penjelasan berhasil disimpan');
        return redirect()->to(base_url(self::SUBKAT));
    }

    public function editketerangan()
    {
        if ($r = $this->checkAuthentication())
            return $r;

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');
        $isAjax = $this->request->isAJAX();

        if (!$kodebpr) {
            return $isAjax ? $this->jsonErr('Kode BPR tidak valid') : redirect()->back()->with('error', 'Kode BPR tidak valid');
        }
        if (!$periodeId) {
            return $isAjax ? $this->jsonErr('Periode tidak valid') : redirect()->back()->with('error', 'Periode tidak valid');
        }

        $tindaklanjut = trim($this->request->getPost('tindaklanjut') ?? '');
        if (empty($tindaklanjut)) {
            return $isAjax ? $this->jsonErr('Penjelasan tidak boleh kosong') : redirect()->back()->with('error', 'Penjelasan tidak boleh kosong');
        }

        $ok = $this->getPenjelastindakModel()->editberdasarkankodedanperiode(
            ['tindaklanjut' => $tindaklanjut, 'user_id' => $this->userId, 'kodebpr' => $kodebpr],
            self::SUBKAT,
            $kodebpr,
            $periodeId
        );

        if ($isAjax) {
            if (!$ok)
                return $this->jsonErr('Gagal mengubah data');
            $fresh = $this->getPenjelastindakModel()
                ->getDataPenjelasByKodebprAndPeriode(self::SUBKAT, $kodebpr, $periodeId);
            return $this->jsonOk('Penjelasan berhasil diubah', ['penjelastindak' => $fresh]);
        }

        session()->setFlashdata($ok ? 'message' : 'err', $ok ? 'Data berhasil diubah' : 'Gagal mengubah data');
        return redirect()->to(base_url(self::SUBKAT));
    }

    public function setNullKolomTindak($id = null)
    {
        if ($r = $this->checkAuthentication())
            return $r;

        $id = $id ?? (int) $this->request->getPost('id');
        $kodebpr = $this->userKodebpr;
        $isAjax = $this->request->isAJAX();

        if (!$id) {
            return $isAjax ? $this->jsonErr('ID tidak valid') : redirect()->back()->with('error', 'ID tidak valid');
        }

        $existing = $this->getPenjelastindakModel()
            ->where('id', $id)->where('kodebpr', $kodebpr)->first();
        if (!$existing) {
            return $isAjax ? $this->jsonErr('Data tidak ditemukan atau tidak memiliki akses') : redirect()->back()->with('error', 'Data tidak ditemukan');
        }

        $ok = $this->getPenjelastindakModel()
            ->where('id', $id)->where('kodebpr', $kodebpr)->delete();

        if ($isAjax) {
            return $ok ? $this->jsonOk('Penjelasan berhasil dihapus') : $this->jsonErr('Gagal menghapus penjelasan');
        }

        session()->setFlashdata($ok ? 'message' : 'err', $ok ? 'Data berhasil dihapus' : 'Data gagal dihapus');
        return redirect()->to(base_url(self::SUBKAT));
    }

    /* APPROVAL */
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
            $this->getMasalahhukumModel()
                ->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->update(null, $fields);
            session()->setFlashdata(
                $val ? 'message' : 'err',
                $val ? 'Persetujuan Komisaris Utama berhasil diberikan.' : 'Persetujuan Komisaris Utama dibatalkan.'
            );
        } catch (\Throwable $e) {
            log_message('error', '[masalahhukum::updateKomisarisApproval] ' . $e->getMessage());
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
            $this->getMasalahhukumModel()
                ->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->update(null, array_merge($fields, [
                    'approved_by' => $this->userId,
                    'approved_at' => date('Y-m-d H:i:s'),
                ]));
            session()->setFlashdata($flashType, $msg);
        } catch (\Throwable $e) {
            log_message('error', '[masalahhukum::bulkApprove] ' . $e->getMessage());
            session()->setFlashdata('err', 'Terjadi kesalahan sistem');
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

    /* EXPORT */
    public function exporttxtmasalahhukum()
    {
        if ($r = $this->checkAuthentication())
            return $r;

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        $periodeDetail = $this->getPeriodeModel()->getPeriodeDetail($periodeId);
        $exportDate = $periodeDetail['tahun'] ?? date('Y');
        $jenispelaporan = $periodeDetail['jenispelaporan'];

        $data = $this->getMasalahhukumModel()->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        $data_infobpr = $this->getInfobprModel()->getDataByKodebpr($kodebpr);
        $data_penjelas = $this->getPenjelastindakModel()
            ->where('subkategori', self::SUBKAT)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
            ->findAll();

        $sandibpr = $data_infobpr[0]['sandibpr'] ?? '';
        $kodejenis = $data_infobpr[0]['kodejenis'] ?? '';

        $sanitize = function ($text): string {
            if ($text === null)
                return '';
            $text = str_replace(["\r", "\n", "\t"], ' ', (string) $text);
            $text = str_replace('|', ' ', $text);
            $text = str_replace(["\xE2\x80\x9C", "\xE2\x80\x9D"], '"', $text);
            $text = str_replace(["\xE2\x80\x98", "\xE2\x80\x99"], "'", $text);
            $text = str_replace('%', ' persen ', $text);
            $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
            return trim(preg_replace('/\s+/', ' ', $text));
        };

        $isEmpty = fn($v) => $v === null || $v === '';
        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}-12-31|LTBPRK|E0900|0|\r\n";

        $hasRealData = false;

        foreach ($data as $row) {
            if ((int) ($row['is_null_record'] ?? 0) === 1) {
                continue;
            }

            $ps = $sanitize($row['hukumperdataselesai'] ?? '');
            $pi = $sanitize($row['hukumpidanaselesai'] ?? '');
            $pp = $sanitize($row['hukumperdataproses'] ?? '');
            $ip = $sanitize($row['hukumpidanaproses'] ?? '');

            if (!$isEmpty($ps) || !$isEmpty($pi))
                $output .= "D01|1001|{$ps}|{$pi}\r\n";
            if (!$isEmpty($pp) || !$isEmpty($ip))
                $output .= "D01|1002|{$pp}|{$ip}\r\n";

            $pTotal = (int) ($row['hukumperdataselesai'] ?? 0) + (int) ($row['hukumperdataproses'] ?? 0);
            $iTotal = (int) ($row['hukumpidanaselesai'] ?? 0) + (int) ($row['hukumpidanaproses'] ?? 0);
            if ($pTotal || $iTotal) {
                $output .= "D01|1003|{$pTotal}|{$iTotal}\r\n";
                $hasRealData = true;
            }
        }

        if (!$hasRealData) {
            $output .= "D01|1001|0|0\r\n";
            $output .= "D01|1002|0|0\r\n";
            $output .= "D01|1003|0|0\r\n";
        }

        $hasF01 = false;

        foreach ($data_penjelas as $p) {
            if (!empty($p['tindaklanjut'])) {
                $output .= 'F01|' . $sanitize($p['tindaklanjut']) . "\r\n";
                $hasF01 = true;
            }
        }

        if (!$hasF01) {
            $output .= "F01|\r\n";
        }

        $filename = "LTBPRK-E0900-{$jenispelaporan}-A-{$exportDate}1231-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }
}