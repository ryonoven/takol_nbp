<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_strukturkomite;
use App\Models\M_infobpr;
use App\Models\M_user;
use App\Models\M_periodetahunan;
use App\Models\M_transparansicomments;
use App\Models\M_transparansicommentsread;
use App\Models\M_penjelastindak;
use Myth\Auth\Config\Services as AuthServices;

class strukturkomite extends Controller
{
    protected $auth;
    protected $session;
    protected $userKodebpr;
    protected $userId;

    protected $strukturkomiteModel;
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
    private const SUBKAT = 'Strukturkomite';

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

    private function getStrukturkomiteModel(): M_strukturkomite
    {
        return $this->strukturkomiteModel ??= new M_strukturkomite();
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
        return $this->getStrukturkomiteModel()
            ->select('id, anggotakomite, nikkomite, keahlian,
                  jbtaudit, jbtpantauresiko, jbtremunerasi, jbtmanrisk, jbtlain,
                  independen, accdekom, accdekom_by, accdekom_at,
                  is_approved, approved_by, approved_at, is_null_record')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->orderBy('id', 'ASC')
            ->findAll();
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
        if (!$this->request->isAJAX())
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        return null;
    }

    private function requireAuth()
    {
        if (!$this->auth->check())
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        return null;
    }

    private function validateNik(string $nik): bool
    {
        return strlen($nik) === 16 && ctype_digit($nik);
    }

    private function checkIdempotency(string $key): bool
    {
        $cache = service('cache');
        $cacheKey = 'idem_' . md5($key);
        if ($cache->get($cacheKey))
            return false;
        $cache->save($cacheKey, 1, 10);
        return true;
    }

    public function index()
    {
        if ($r = $this->checkAuthentication())
            return $r;
        if (!session('active_periode'))
            return redirect()->to('/Periodetransparansi');

        $periodeId = (int) session('active_periode');
        $kodebpr = $this->userKodebpr;

        if (!$kodebpr)
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');

        $rows = $this->getFreshRows($kodebpr, $periodeId);
        $approval = $this->deriveApprovalBundle($rows);
        $perms = $this->getUserPermissions();

        $accdekomData = array_map(fn($r) => ['accdekom' => $r['accdekom'], 'accdekom_by' => $r['accdekom_by'], 'accdekom_at' => $r['accdekom_at']], $rows);
        $accdirutData = array_map(fn($r) => ['is_approved' => $r['is_approved'], 'approved_by' => $r['approved_by'], 'approved_at' => $r['approved_at']], $rows);

        $data = [
            'judul' => '5. Struktur, Keanggotaan, Keahlian, dan Independensi Anggota Komite',
            'strukturkomite' => $rows,
            'userId' => $this->userId,
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
            'periodeId' => $periodeId,
            'periodeDetail' => $this->getPeriodeModel()->getPeriodeDetail($periodeId),
            'bprData' => $this->getInfobprModel()->getBprByKode($kodebpr),
            'accdekomData' => $accdekomData,
            'accdirutData' => $accdirutData,
            'penjelastindak' => $this->getPenjelastindakModel()
                ->getDataPenjelasByKodebprAndPeriode(self::SUBKAT, $kodebpr, $periodeId),
            'canApprove' => $approval['allDekom'],
            'pollIntervalMs' => self::POLL_MIN_MS,
        ];

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('Laporantahunan/Transparansi/strukturkomite', $data);
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

            if (!$this->checkIdempotency("tambah_{$kodebpr}_{$periodeId}_{$this->userId}"))
                return $this->jsonErr('Permintaan duplikat — tunggu sebentar');

            $anggotakomite = trim($this->request->getPost('anggotakomite') ?? '');
            $nikkomite = trim($this->request->getPost('nikkomite') ?? '');
            $keahlian = trim($this->request->getPost('keahlian') ?? '');
            $jbtaudit = trim($this->request->getPost('jbtaudit') ?? '');
            $jbtpantauresiko = trim($this->request->getPost('jbtpantauresiko') ?? '');
            $jbtremunerasi = trim($this->request->getPost('jbtremunerasi') ?? '');
            $jbtmanrisk = trim($this->request->getPost('jbtmanrisk') ?? '');
            $jbtlain = trim($this->request->getPost('jbtlain') ?? '');
            $independen = trim($this->request->getPost('independen') ?? '');

            if (!$anggotakomite || !$nikkomite || !$keahlian)
                return $this->jsonErr('Nama Anggota Komite, NIK, dan Keahlian wajib diisi');
            if (!$this->validateNik($nikkomite))
                return $this->jsonErr('NIK harus tepat 16 digit angka');

            $this->getStrukturkomiteModel()->checkIncrement();
            $ok = $this->getStrukturkomiteModel()->insert([
                'anggotakomite' => $anggotakomite,
                'nikkomite' => $nikkomite,
                'keahlian' => $keahlian,
                'jbtaudit' => $jbtaudit,
                'jbtpantauresiko' => $jbtpantauresiko,
                'jbtremunerasi' => $jbtremunerasi,
                'jbtmanrisk' => $jbtmanrisk,
                'jbtlain' => $jbtlain,
                'independen' => $independen,
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
            log_message('error', '[strukturkomite::tambahAjax] ' . $e->getMessage());
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

            $existing = $this->getStrukturkomiteModel()
                ->select('id')->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();
            if (!$existing)
                return $this->jsonErr('Data tidak ditemukan atau tidak memiliki akses');

            $anggotakomite = trim($this->request->getPost('anggotakomite') ?? '');
            $nikkomite = trim($this->request->getPost('nikkomite') ?? '');
            $keahlian = trim($this->request->getPost('keahlian') ?? '');
            $jbtaudit = trim($this->request->getPost('jbtaudit') ?? '');
            $jbtpantauresiko = trim($this->request->getPost('jbtpantauresiko') ?? '');
            $jbtremunerasi = trim($this->request->getPost('jbtremunerasi') ?? '');
            $jbtmanrisk = trim($this->request->getPost('jbtmanrisk') ?? '');
            $jbtlain = trim($this->request->getPost('jbtlain') ?? '');
            $independen = trim($this->request->getPost('independen') ?? '');

            if (
                !$anggotakomite || !$nikkomite || !$keahlian || !$jbtaudit || !$jbtpantauresiko
                || !$jbtremunerasi || !$jbtmanrisk || !$jbtlain || !$independen
            )
                return $this->jsonErr('Semua field wajib diisi');
            if (!$this->validateNik($nikkomite))
                return $this->jsonErr('NIK harus tepat 16 digit angka');

            $ok = $this->getStrukturkomiteModel()->editbasedkodedanperiode(
                [
                    'anggotakomite' => $anggotakomite,
                    'nikkomite' => $nikkomite,
                    'keahlian' => $keahlian,
                    'jbtaudit' => $jbtaudit,
                    'jbtpantauresiko' => $jbtpantauresiko,
                    'jbtremunerasi' => $jbtremunerasi,
                    'jbtmanrisk' => $jbtmanrisk,
                    'jbtlain' => $jbtlain,
                    'independen' => $independen,
                    'user_id' => $this->userId,
                    'kodebpr' => $kodebpr,
                    'accdekom' => 0,
                    'is_approved' => 0,
                ],
                $kodebpr,
                $periodeId,
                $id
            );

            if (!$ok)
                return $this->jsonErr('Gagal mengubah data');

            return $this->jsonOk('Data berhasil diubah', ['data' => $this->getFreshRows($kodebpr, $periodeId)]);

        } catch (\Throwable $e) {
            log_message('error', '[strukturkomite::ubahAjax] ' . $e->getMessage());
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

            $existing = $this->getStrukturkomiteModel()
                ->select('id')->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();
            if (!$existing)
                return $this->jsonErr('Data tidak ditemukan atau tidak memiliki akses');

            $ok = $this->getStrukturkomiteModel()
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->delete();
            if (!$ok)
                return $this->jsonErr('Gagal menghapus data');

            return $this->jsonOk('Data berhasil dihapus', ['data' => $this->getFreshRows($kodebpr, $periodeId)]);

        } catch (\Throwable $e) {
            log_message('error', '[strukturkomite::hapusAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function getKomentarByFaktorId()
    {
        if (!$this->request->isAJAX())
            return $this->response->setStatusCode(404)->setBody('Not Found');

        $list = $this->getKomentarModel()->getKomentarByFaktorId(self::SUBKAT, $this->userKodebpr, session('active_periode'));
        $unread = count(array_filter($list, fn($k) => (int) $k['user_id'] !== $this->userId));

        return $this->response->setJSON(['comments' => $list, 'unread_count' => $unread]);
    }

    public function getUnreadCommentCountForFactor()
    {
        if (!$this->request->isAJAX())
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);

        $kodebpr = $this->userKodebpr;
        $userId = $this->userId;
        $periodeId = session('active_periode');

        if (!$kodebpr || !$userId || !$periodeId)
            return $this->response->setStatusCode(400)->setJSON(['unread_count' => 0]);

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
                'kodebpr' => $this->userKodebpr,
                'periode_id' => session('active_periode'),
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            service('cache')->deleteMatching('unread_' . self::SUBKAT . "_{$this->userKodebpr}_*");

            return $this->jsonOk('Komentar berhasil dikirim');
        }

        if (!isset($_POST['TambahKomentar']))
            return redirect()->to(base_url(self::SUBKAT));

        $val = $this->validate(['komentar' => ['label' => 'Komentar', 'rules' => 'required|min_length[3]|max_length[1000]']]);
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
            'kodebpr' => $this->userKodebpr,
            'periode_id' => session('active_periode'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        session()->setFlashdata('message', 'Komentar berhasil ditambahkan');
        return redirect()->to(base_url(self::SUBKAT));
    }

    public function markUserCommentsAsRead()
    {
        if (!$this->request->isAJAX())
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);

        $kodebpr = $this->userKodebpr;
        $userId = $this->userId;
        $periodeId = session('active_periode');

        if (!$kodebpr || !$userId || !$periodeId)
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Missing data.']);

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

    public function tambahKeteranganAjax()
    {
        if ($r = $this->checkAuthentication())
            return $r;

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        if (!$kodebpr || !$periodeId) {
            return $this->request->isAJAX()
                ? $this->jsonErr('Kode BPR atau periode tidak valid')
                : redirect()->back()->with('error', 'Tidak valid');
        }

        $tindaklanjut = trim($this->request->getPost('tindaklanjut') ?? '');
        if (empty($tindaklanjut)) {
            return $this->request->isAJAX()
                ? $this->jsonErr('Tindak Lanjut wajib diisi')
                : redirect()->back()->with('error', 'Tindak Lanjut wajib diisi');
        }

        $existing = $this->getPenjelastindakModel()
            ->getDataPenjelasByKodebprAndPeriode(self::SUBKAT, $kodebpr, $periodeId);
        if (!empty($existing)) {
            return $this->request->isAJAX()
                ? $this->jsonErr('Data sudah ada. Gunakan Edit untuk mengubah.')
                : redirect()->back()->with('error', 'Data sudah ada.');
        }

        $this->getPenjelastindakModel()->tambahpenjelastindak([
            'subkategori' => self::SUBKAT,
            'tindaklanjut' => $tindaklanjut,
            'kodebpr' => $kodebpr,
            'periode_id' => $periodeId,
            'fullname' => $this->userData['fullname'] ?? null,
            'user_id' => $this->userId,
        ]);

        if ($this->request->isAJAX()) {
            $fresh = $this->getPenjelastindakModel()
                ->getDataPenjelasByKodebprAndPeriode(self::SUBKAT, $kodebpr, $periodeId);
            return $this->jsonOk('Tindak lanjut berhasil disimpan', ['penjelastindak' => $fresh]);
        }

        session()->setFlashdata('message', 'Tindak lanjut berhasil ditambahkan');
        return redirect()->to(base_url(self::SUBKAT));
    }

    public function editKeteranganAjax()
    {
        if ($r = $this->checkAuthentication())
            return $r;

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        if (!$kodebpr || !$periodeId) {
            return $this->request->isAJAX()
                ? $this->jsonErr('Kode BPR atau periode tidak valid')
                : redirect()->back()->with('error', 'Tidak valid');
        }

        $tindaklanjut = trim($this->request->getPost('tindaklanjut') ?? '');
        if (empty($tindaklanjut)) {
            return $this->request->isAJAX()
                ? $this->jsonErr('Tindak Lanjut tidak boleh kosong')
                : redirect()->back()->with('error', 'Tidak boleh kosong');
        }

        $ok = $this->getPenjelastindakModel()->editberdasarkankodedanperiode(
            ['tindaklanjut' => $tindaklanjut, 'user_id' => $this->userId, 'kodebpr' => $kodebpr],
            self::SUBKAT,
            $kodebpr,
            $periodeId
        );

        if ($this->request->isAJAX()) {
            if (!$ok)
                return $this->jsonErr('Gagal mengubah data');
            $fresh = $this->getPenjelastindakModel()
                ->getDataPenjelasByKodebprAndPeriode(self::SUBKAT, $kodebpr, $periodeId);
            return $this->jsonOk('Data berhasil diubah', ['penjelastindak' => $fresh]);
        }

        session()->setFlashdata($ok ? 'message' : 'err', $ok ? 'Data berhasil diubah' : 'Gagal mengubah data');
        return redirect()->to(base_url(self::SUBKAT));
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

            $existing = $this->getPenjelastindakModel()->where('id', $id)->where('kodebpr', $kodebpr)->first();
            if (!$existing)
                return $this->jsonErr('Data tidak ditemukan atau tidak memiliki akses');

            $ok = $this->getPenjelastindakModel()->where('id', $id)->where('kodebpr', $kodebpr)->delete();
            if (!$ok)
                return $this->jsonErr('Gagal menghapus data');

            return $this->jsonOk('Tindak lanjut berhasil dihapus');

        } catch (\Throwable $e) {
            log_message('error', '[strukturkomite::setNullKolomTindakAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
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
            $this->getStrukturkomiteModel()
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->update(null, array_merge($fields, [
                    'approved_by' => $this->userId,
                    'approved_at' => date('Y-m-d H:i:s'),
                ]));
            session()->setFlashdata($flashType, $msg);
        } catch (\Throwable $e) {
            log_message('error', '[strukturkomite::bulkApprove] ' . $e->getMessage());
            session()->setFlashdata('err', 'Terjadi kesalahan sistem');
        }

        return redirect()->back();
    }

    public function approveSemuaKom()
    {
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        if (!$kodebpr || !$periodeId) {
            session()->setFlashdata('err', 'Kode BPR atau Periode ID tidak valid');
            return redirect()->back();
        }

        try {
            $this->getStrukturkomiteModel()
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->update(null, [
                    'accdekom' => 1,
                    'accdekom_by' => $this->userId,
                    'accdekom_at' => date('Y-m-d H:i:s'),
                ]);
            session()->setFlashdata('message', 'Persetujuan Komisaris Utama berhasil diberikan.');
        } catch (\Throwable $e) {
            log_message('error', '[strukturkomite::approveSemuaKom] ' . $e->getMessage());
            session()->setFlashdata('err', 'Terjadi kesalahan sistem');
        }

        return redirect()->back();
    }

    public function unapproveSemuaKom()
    {
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        if (!$kodebpr || !$periodeId) {
            session()->setFlashdata('err', 'Kode BPR atau Periode ID tidak valid');
            return redirect()->back();
        }

        try {
            $this->getStrukturkomiteModel()
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->update(null, [
                    'accdekom' => 0,
                    'is_approved' => 0,
                    'accdekom_by' => $this->userId,
                    'accdekom_at' => date('Y-m-d H:i:s'),
                ]);
            session()->setFlashdata('err', 'Persetujuan Komisaris Utama dibatalkan.');
        } catch (\Throwable $e) {
            log_message('error', '[strukturkomite::unapproveSemuaKom] ' . $e->getMessage());
            session()->setFlashdata('err', 'Terjadi kesalahan sistem');
        }

        return redirect()->back();
    }

    public function approveSemuaDirut()
    {
        return $this->bulkApprove(['is_approved' => 1], 'message', 'Persetujuan Direktur Utama berhasil diberikan.');
    }

    public function unapproveSemuaDirut()
    {
        return $this->bulkApprove(['is_approved' => 0, 'accdekom' => 0], 'err', 'Persetujuan Direktur Utama dibatalkan.');
    }

    public function setNodataAjax()
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

            $existing = $this->getStrukturkomiteModel()
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->countAllResults();

            if ($existing > 0)
                return $this->jsonErr('Data sudah ada, tidak bisa ditandai sebagai kosong');

            $ok = $this->getStrukturkomiteModel()->insert([
                'anggotakomite' => '',
                'nikkomite' => '',
                'keahlian' => '',
                'jbtaudit' => '00',
                'jbtpantauresiko' => '00',
                'jbtremunerasi' => '00',
                'jbtmanrisk' => '00',
                'jbtlain' => '00',
                'independen' => '0',
                'is_null_record' => 1,
                'periode_id' => $periodeId,
                'user_id' => $this->userId,
                'kodebpr' => $kodebpr,
                'fullname' => $this->userData['fullname'] ?? null,
                'accdekom' => 0,
                'is_approved' => 0,
            ]);

            if (!$ok)
                return $this->jsonErr('Gagal menyimpan data');

            return $this->jsonOk('Data berhasil ditandai sebagai tidak ada', [
                'data' => $this->getFreshRows($kodebpr, $periodeId),
            ]);

        } catch (\Throwable $e) {
            log_message('error', '[strukturkomite::setNodataAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function batalNodataAjax()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;

        try {
            $kodebpr = $this->userKodebpr;
            $periodeId = (int) session('active_periode');

            if (!$kodebpr || !$periodeId)
                return $this->jsonErr('Data tidak valid');

            $ok = $this->getStrukturkomiteModel()
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->where('is_null_record', 1)
                ->delete();

            if (!$ok)
                return $this->jsonErr('Gagal membatalkan status');

            return $this->jsonOk('Status berhasil dibatalkan', [
                'data' => $this->getFreshRows($kodebpr, $periodeId),
            ]);

        } catch (\Throwable $e) {
            log_message('error', '[strukturkomite::batalNodataAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function exporttxtstrukturkomite()
    {
        if ($r = $this->checkAuthentication())
            return $r;

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        $periodeDetail = $this->getPeriodeModel()->find($periodeId);
        $exportDate = $periodeDetail['tahun'] ?? date('Y');
        $jenispelaporan = $periodeDetail['jenispelaporan'];

        $data_strukturkomite = $this->getStrukturkomiteModel()->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        $data_infobpr = $this->getInfobprModel()->getDataByKodebpr($kodebpr);
        $data_penjelastindak = $this->getPenjelastindakModel()
            ->where('subkategori', self::SUBKAT)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->findAll();

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

        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}-12-31|LTBPRK|E0204|0|\r\n";

        $hasRealData = false;

        foreach ($data_strukturkomite as $row) {
            if ((int)($row['is_null_record'] ?? 0) === 1) {
                continue;
            }
        
            $anggota = trim((string) ($row['anggotakomite'] ?? ''));
            $nik     = trim((string) ($row['nikkomite'] ?? ''));
        
            if ($anggota === '' && $nik === '') {
                continue;
            }
        
            $hasRealData = true;
        
            $output .= sprintf(
                "D01|013201000000|%s|%s|%s|%s|%s|%s|%s|%s|%s\r\n",
                $sanitize($anggota),
                $sanitize($nik),
                $sanitize($row['keahlian'] ?? ''),
                $sanitize($row['jbtaudit'] ?? ''),
                $sanitize($row['jbtpantauresiko'] ?? ''),
                $sanitize($row['jbtremunerasi'] ?? ''),
                $sanitize($row['jbtmanrisk'] ?? ''),
                $sanitize($row['jbtlain'] ?? ''),
                $sanitize($row['independen'] ?? '')
            );
        }
        
        if (!$hasRealData) {
            $output .= "D01|013201000000| |0| |00|00|00|00|00|2\r\n";
        }

        foreach ($data_penjelastindak as $p) {
            if (!empty($p['tindaklanjut']))
                $output .= 'F01|' . $sanitize($p['tindaklanjut']) . "\r\n";
        }

        $filename = "LTBPRK-E0204-{$jenispelaporan}-A-{$exportDate}1231-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }
}