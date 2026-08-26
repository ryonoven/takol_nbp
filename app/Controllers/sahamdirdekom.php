<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_sahamdirdekom;
use App\Models\M_infobpr;
use App\Models\M_user;
use App\Models\M_periodetahunan;
use App\Models\M_transparansicomments;
use App\Models\M_transparansicommentsread;
use App\Models\M_penjelastindak;
use Myth\Auth\Config\Services as AuthServices;

class sahamdirdekom extends Controller
{
    protected $auth;
    protected $session;
    protected $userKodebpr;
    protected $userId;

    protected $sahamdirdekomModel;
    protected $userModel;
    protected $infobprModel;
    protected $periodeModel;
    protected $komentarModel;
    protected $commentReadsModel;
    protected $penjelastindakModel;

    private $userPermissions = null;
    private $userData = null;

    private const CACHE_TTL = 30;
    private const POLL_MIN_MS = 8000;
    private const SUBKAT = 'Sahamdirdekom';

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

    private function getSahamdirdekomModel(): M_sahamdirdekom
    {
        return $this->sahamdirdekomModel ??= new M_sahamdirdekom();
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
            $groups = ['pe', 'admin', 'dekom', 'dekom2', 'dekom3', 'dekom4', 'dekom5', 'direksi', 'direksi2'];
            foreach ($groups as $g) {
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
        return $this->getSahamdirdekomModel()
            ->select('id, nama, nik, jabatan, persensaham, is_null_record,
                  accdekom, accdekom_at, is_approved, approved_at')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    private function deriveApprovalBundle(array $rows): array
    {
        $allDekom = !empty($rows) && array_reduce($rows, fn($c, $r) => $c && (int) $r['accdekom'] === 1, true);
        $allDirut = !empty($rows) && array_reduce($rows, fn($c, $r) => $c && (int) $r['is_approved'] === 1, true);

        $lastDekom = null;
        $lastDirut = null;
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
        return $this->response->setJSON(array_merge(['status' => 'success', 'message' => $msg, 'ts' => time()], $extra));
    }

    private function jsonErr(string $msg, int $code = 200)
    {
        return $this->response->setStatusCode($code)->setJSON(['status' => 'error', 'message' => $msg]);
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
        $permissions = $this->getUserPermissions();
        $periodeDetail = $this->getPeriodeModel()->getPeriodeDetail($periodeId);
        $bprData = $this->getInfobprModel()->getBprByKode($kodebpr);
        $penjelastindak = $this->getPenjelastindakModel()->getDataPenjelasByKodebprAndPeriode(self::SUBKAT, $kodebpr, $periodeId);

        $accdekomData = array_map(fn($r) => ['accdekom' => $r['accdekom'], 'accdekom_at' => $r['accdekom_at'] ?? null], $rows);
        $accdirutData = array_map(fn($r) => ['is_approved' => $r['is_approved'], 'approved_at' => $r['approved_at'] ?? null], $rows);

        $data = [
            'judul' => '6. Kepemilikan Saham Anggota Direksi dan Dewan Komisaris pada BPR',
            'sahamdirdekom' => $rows,
            'userInGroupPE' => $permissions['pe'] ?? false,
            'userInGroupAdmin' => $permissions['admin'] ?? false,
            'userInGroupDekom' => $permissions['dekom'] ?? false,
            'userInGroupDekom2' => $permissions['dekom2'] ?? false,
            'userInGroupDireksi' => $permissions['direksi'] ?? false,
            'userInGroupDireksi2' => $permissions['direksi2'] ?? false,
            'fullname' => $this->userData['fullname'] ?? 'Unknown',
            'kodebpr' => $kodebpr,
            'periodeId' => $periodeId,
            'periodeDetail' => $periodeDetail,
            'bprData' => $bprData,
            'accdekomData' => $accdekomData,
            'accdirutData' => $accdirutData,
            'penjelastindak' => $penjelastindak,
            'canApprove' => $approval['allDekom'],
            'pollIntervalMs' => self::POLL_MIN_MS,
        ];

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('Laporantahunan/Transparansi/sahamdirdekom', $data);
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

            if (!$this->checkIdempotency("tambah_{$kodebpr}_{$periodeId}_{$this->userId}")) {
                return $this->jsonErr('Permintaan duplikat — tunggu sebentar');
            }

            $nama = trim($this->request->getPost('nama') ?? '');
            $nik = trim($this->request->getPost('nik') ?? '');
            $jabatan = trim($this->request->getPost('jabatan') ?? '');
            $persensaham = trim($this->request->getPost('persensaham') ?? '');

            if (!$nama || !$nik || !$jabatan || $persensaham === '') {
                return $this->jsonErr('Nama, NIK, Jabatan, dan Persen Saham wajib diisi');
            }
            if (!in_array($jabatan, ['Direksi', 'Dekom'], true)) {
                return $this->jsonErr('Jabatan tidak valid');
            }
            if (!is_numeric($persensaham) || (float) $persensaham < 0 || (float) $persensaham > 100) {
                return $this->jsonErr('Persen saham harus angka antara 0–100');
            }

            $this->getSahamdirdekomModel()->checkIncrement();
            $ok = $this->getSahamdirdekomModel()->insert([
                'nama' => $nama,
                'nik' => $nik,
                'jabatan' => $jabatan,
                'persensaham' => (float) $persensaham,
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

        } catch (\Exception $e) {
            log_message('error', '[sahamdirdekom::tambahAjax] ' . $e->getMessage());
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

            $existing = $this->getSahamdirdekomModel()
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();
            if (!$existing)
                return $this->jsonErr('Data tidak ditemukan atau tidak memiliki akses');

            $nama = trim($this->request->getPost('nama') ?? '');
            $nik = trim($this->request->getPost('nik') ?? '');
            $jabatan = trim($this->request->getPost('jabatan') ?? '');
            $persensaham = trim($this->request->getPost('persensaham') ?? '');

            if (!$nama || !$nik)
                return $this->jsonErr('Semua field harus diisi');
            if (!is_numeric($persensaham))
                return $this->jsonErr('Persen saham harus berupa angka');

            $ok = $this->getSahamdirdekomModel()
                ->editbasedkodedanperiode(
                    [
                        'nama' => $nama,
                        'nik' => $nik,
                        'jabatan' => $jabatan,
                        'persensaham' => (float) $persensaham,
                        'accdekom' => 0,
                        'is_approved' => 0
                    ],
                    $kodebpr,
                    $periodeId,
                    $id
                );

            if (!$ok)
                return $this->jsonErr('Gagal mengubah data');

            return $this->jsonOk('Data berhasil diubah', ['data' => $this->getFreshRows($kodebpr, $periodeId)]);

        } catch (\Throwable $e) {
            log_message('error', '[sahamdirdekom::ubahAjax] ' . $e->getMessage());
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

            $existing = $this->getSahamdirdekomModel()
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();
            if (!$existing)
                return $this->jsonErr('Data tidak ditemukan atau tidak memiliki akses');

            $ok = $this->getSahamdirdekomModel()
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->delete();
            if (!$ok)
                return $this->jsonErr('Gagal menghapus data');

            return $this->jsonOk('Data berhasil dihapus', ['data' => $this->getFreshRows($kodebpr, $periodeId)]);

        } catch (\Exception $e) {
            log_message('error', '[sahamdirdekom::hapusAjax] ' . $e->getMessage());
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

        $unread = count(array_filter($list, fn($k) => (int) ($k['user_id']) !== $userId));

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

        $val = $this->validate(['komentar' => ['label' => 'Komentar', 'rules' => 'required']]);
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

        return $this->response->setJSON(['status' => 'success']);
    }

    public function tambahketerangan()
    {
        if ($r = $this->checkAuthentication())
            return $r;

        $periodeId = session('active_periode');
        $kodebpr = $this->userKodebpr;

        if (!$kodebpr || !$periodeId) {
            return $this->request->isAJAX()
                ? $this->jsonErr('Kode BPR atau periode tidak valid')
                : redirect()->back()->with('error', 'Kode BPR atau periode tidak valid');
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
            $newData = $this->getPenjelastindakModel()
                ->getDataPenjelasByKodebprAndPeriode(self::SUBKAT, $kodebpr, $periodeId);
            return $this->jsonOk('Tindak lanjut berhasil disimpan', ['penjelastindak' => $newData]);
        }

        session()->setFlashdata('message', 'Data berhasil ditambahkan');
        return redirect()->to(base_url(self::SUBKAT));
    }

    public function editketerangan()
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
            $newData = $this->getPenjelastindakModel()
                ->getDataPenjelasByKodebprAndPeriode(self::SUBKAT, $kodebpr, $periodeId);
            return $this->jsonOk('Tindak lanjut berhasil diubah', ['penjelastindak' => $newData]);
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

            $existing = $this->getPenjelastindakModel()
                ->where('id', $id)->where('kodebpr', $kodebpr)->first();
            if (!$existing)
                return $this->jsonErr('Data tidak ditemukan atau tidak memiliki akses');

            $ok = $this->getPenjelastindakModel()->where('id', $id)->where('kodebpr', $kodebpr)->delete();
            if (!$ok)
                return $this->jsonErr('Gagal menghapus data');

            return $this->jsonOk('Tindak lanjut berhasil dihapus');

        } catch (\Exception $e) {
            log_message('error', '[sahamdirdekom::setNullKolomTindakAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
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

            $existing = $this->getSahamdirdekomModel()
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->countAllResults();

            if ($existing > 0)
                return $this->jsonErr('Data sudah ada, tidak bisa ditandai sebagai kosong');

            $ok = $this->getSahamdirdekomModel()->insert([
                'nama' => '',
                'nik' => '',
                'jabatan' => 'Direksi',
                'persensaham' => 0,
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
            log_message('error', '[sahamdirdekom::setNodataAjax] ' . $e->getMessage());
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

            $ok = $this->getSahamdirdekomModel()
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
            log_message('error', '[sahamdirdekom::batalNodataAjax] ' . $e->getMessage());
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
            $this->getSahamdirdekomModel()
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->update(null, array_merge($fields, [
                    'approved_by' => $this->userId,
                    'approved_at' => date('Y-m-d H:i:s'),
                ]));

            session()->setFlashdata($flashType, $msg);
        } catch (\Exception $e) {
            log_message('error', '[bulkApprove] ' . $e->getMessage());
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
            $this->getSahamdirdekomModel()
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->update(null, [
                    'accdekom' => 1,
                    'accdekom_by' => $this->userId,
                    'accdekom_at' => date('Y-m-d H:i:s'),
                ]);
            session()->setFlashdata('message', 'Persetujuan Komisaris Utama berhasil diberikan.');
        } catch (\Exception $e) {
            log_message('error', '[approveSemuaKom] ' . $e->getMessage());
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
            $this->getSahamdirdekomModel()
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->update(null, [
                    'accdekom' => 0,
                    'is_approved' => 0,
                    'accdekom_by' => $this->userId,
                    'accdekom_at' => date('Y-m-d H:i:s'),
                ]);
            session()->setFlashdata('err', 'Persetujuan Komisaris Utama dibatalkan.');
        } catch (\Exception $e) {
            log_message('error', '[unapproveSemuaKom] ' . $e->getMessage());
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
}