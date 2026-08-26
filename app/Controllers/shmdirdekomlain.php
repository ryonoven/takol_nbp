<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_shmdirdekomlain;
use App\Models\M_infobpr;
use App\Models\M_user;
use App\Models\M_periodetahunan;
use App\Models\M_transparansicomments;
use App\Models\M_transparansicommentsread;
use App\Models\M_penjelastindak;
use Myth\Auth\Config\Services as AuthServices;

class Shmdirdekomlain extends Controller
{
    protected $auth;
    protected $session;
    protected $userKodebpr;
    protected $userId;

    protected $shmdirdekomlainModel;
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
    private const SUBKAT = 'Shmdirdekomlain';

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

    private function getShmdirdekomlainModel(): M_shmdirdekomlain
    {
        return $this->shmdirdekomlainModel ??= new M_shmdirdekomlain();
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
        return $this->getShmdirdekomlainModel()
            ->select('id, nama, nik, jabatan, jenisperusahaan, perusahaan,
                  persensaham, kodebank, is_null_record,
                  accdekom, accdekom_at, is_approved, approved_at')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->orderBy('jabatan', 'ASC')
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
            'judul' => '8. Kepemilikan Saham Anggota Direksi dan Dewan Komisaris pada Perusahaan Lain',
            'shmdirdekomlain' => $rows,
            'userInGroupPE' => $permissions['pe'] ?? false,
            'userInGroupAdmin' => $permissions['admin'] ?? false,
            'userInGroupDekom' => $permissions['dekom'] ?? false,
            'userInGroupDekom2' => $permissions['dekom2'] ?? false,
            'userInGroupDekom3' => $permissions['dekom3'] ?? false,
            'userInGroupDekom4' => $permissions['dekom4'] ?? false,
            'userInGroupDekom5' => $permissions['dekom5'] ?? false,
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
        echo view('Laporantahunan/Transparansi/shmdirdekomlain', $data);
        echo view('templates/v_footer');
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

            $existing = $this->getShmdirdekomlainModel()
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->countAllResults();

            if ($existing > 0)
                return $this->jsonErr('Data sudah ada, tidak bisa ditandai sebagai kosong');

            $ok = $this->getShmdirdekomlainModel()->insert([
                'nama' => '',
                'nik' => '',
                'jabatan' => 'Direksi',
                'jenisperusahaan' => 'Perusahaan Lain',
                'perusahaan' => '',
                'persensaham' => 0,
                'kodebank' => '',
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
            log_message('error', '[Shmdirdekomlain::setNodataAjax] ' . $e->getMessage());
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

            $ok = $this->getShmdirdekomlainModel()
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
            log_message('error', '[Shmdirdekomlain::batalNodataAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
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
            $jenisperusahaan = trim($this->request->getPost('jenisperusahaan') ?? '');
            $perusahaan = trim($this->request->getPost('perusahaan') ?? '');
            $persensaham = trim($this->request->getPost('persensaham') ?? '');
            $kodebank = trim($this->request->getPost('kodebank') ?? '');

            if (!$nama || !$nik || !$jabatan || !$jenisperusahaan || !$perusahaan || $persensaham === '') {
                return $this->jsonErr('Semua field wajib diisi');
            }
            if (!in_array($jabatan, ['Direksi', 'Dekom'], true)) {
                return $this->jsonErr('Jabatan tidak valid');
            }
            if (!is_numeric($persensaham) || (float) $persensaham < 0 || (float) $persensaham > 100) {
                return $this->jsonErr('Persen saham harus angka antara 0–100');
            }

            $ok = $this->getShmdirdekomlainModel()->insert([
                'nama' => $nama,
                'nik' => $nik,
                'jabatan' => $jabatan,
                'jenisperusahaan' => $jenisperusahaan,
                'perusahaan' => $perusahaan,
                'persensaham' => (float) $persensaham,
                'kodebank' => $kodebank,
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
            log_message('error', '[Shmdirdekomlain::tambahAjax] ' . $e->getMessage());
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

            $existing = $this->getShmdirdekomlainModel()
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();
            if (!$existing)
                return $this->jsonErr('Data tidak ditemukan atau tidak memiliki akses');

            $nama = trim($this->request->getPost('nama') ?? '');
            $nik = trim($this->request->getPost('nik') ?? '');
            $jabatan = trim($this->request->getPost('jabatan') ?? '');
            $jenisperusahaan = trim($this->request->getPost('jenisperusahaan') ?? '');
            $perusahaan = trim($this->request->getPost('perusahaan') ?? '');
            $persensaham = trim($this->request->getPost('persensaham') ?? '');
            $kodebank = trim($this->request->getPost('kodebank') ?? '');

            if (!$nama || !$nik || !$jabatan || !$jenisperusahaan || !$perusahaan || $persensaham === '') {
                return $this->jsonErr('Semua field wajib diisi');
            }
            if (!is_numeric($persensaham))
                return $this->jsonErr('Persentase saham harus berupa angka');

            $ok = $this->getShmdirdekomlainModel()->editbasedkodedanperiode(
                [
                    'nama' => $nama,
                    'nik' => $nik,
                    'jabatan' => $jabatan,
                    'jenisperusahaan' => $jenisperusahaan,
                    'perusahaan' => $perusahaan,
                    'persensaham' => (float) $persensaham,
                    'kodebank' => $kodebank,
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
            log_message('error', '[Shmdirdekomlain::ubahAjax] ' . $e->getMessage());
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

            $existing = $this->getShmdirdekomlainModel()
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();
            if (!$existing)
                return $this->jsonErr('Data tidak ditemukan atau tidak memiliki akses');

            $ok = $this->getShmdirdekomlainModel()
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->delete();
            if (!$ok)
                return $this->jsonErr('Gagal menghapus data');

            return $this->jsonOk('Data berhasil dihapus', ['data' => $this->getFreshRows($kodebpr, $periodeId)]);

        } catch (\Exception $e) {
            log_message('error', '[Shmdirdekomlain::hapusAjax] ' . $e->getMessage());
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

        service('cache')->delete('unread_' . self::SUBKAT . "_{$kodebpr}_{$periodeId}_{$userId}");

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
                ? $this->jsonErr('Penjelasan wajib diisi')
                : redirect()->back()->with('error', 'Penjelasan wajib diisi');
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
            return $this->jsonOk('Penjelasan berhasil disimpan', ['penjelastindak' => $newData]);
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
                ? $this->jsonErr('Penjelasan tidak boleh kosong')
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
            return $this->jsonOk('Penjelasan berhasil diubah', ['penjelastindak' => $newData]);
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

            return $this->jsonOk('Penjelasan berhasil dihapus');

        } catch (\Exception $e) {
            log_message('error', '[Shmdirdekomlain::setNullKolomTindakAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
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
            $this->getShmdirdekomlainModel()
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
            $this->getShmdirdekomlainModel()
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
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        if (!$kodebpr || !$periodeId) {
            session()->setFlashdata('err', 'Kode BPR atau Periode ID tidak valid');
            return redirect()->back();
        }

        try {
            $this->getShmdirdekomlainModel()
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->update(null, [
                    'is_approved' => 1,
                    'approved_by' => $this->userId,
                    'approved_at' => date('Y-m-d H:i:s'),
                ]);
            session()->setFlashdata('message', 'Persetujuan Direktur Utama berhasil diberikan.');
        } catch (\Exception $e) {
            log_message('error', '[approveSemuaDirut] ' . $e->getMessage());
            session()->setFlashdata('err', 'Terjadi kesalahan sistem');
        }

        return redirect()->back();
    }

    public function unapproveSemuaDirut()
    {
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        if (!$kodebpr || !$periodeId) {
            session()->setFlashdata('err', 'Kode BPR atau Periode ID tidak valid');
            return redirect()->back();
        }

        try {
            $this->getShmdirdekomlainModel()
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->update(null, [
                    'is_approved' => 0,
                    'accdekom' => 0,
                    'approved_by' => $this->userId,
                    'approved_at' => date('Y-m-d H:i:s'),
                ]);
            session()->setFlashdata('err', 'Persetujuan Direktur Utama dibatalkan.');
        } catch (\Exception $e) {
            log_message('error', '[unapproveSemuaDirut] ' . $e->getMessage());
            session()->setFlashdata('err', 'Terjadi kesalahan sistem');
        }

        return redirect()->back();
    }

    public function exporttxtshmdirdekomlain()
    {
        if ($r = $this->checkAuthentication())
            return $r;

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        $periodeDetail = $this->getPeriodeModel()->getPeriodeDetail($periodeId);
        $exportDate = $periodeDetail['tahun'] ?? date('Y');
        $jenispelaporan = $periodeDetail['jenispelaporan'];

        $data = $this->getShmdirdekomlainModel()->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        $data_infobpr = $this->getInfobprModel()->getDataByKodebpr($kodebpr);
        $data_penjelastindak = $this->getPenjelastindakModel()
            ->where('subkategori', self::SUBKAT)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->findAll();

        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'] ?? '';
            $kodejenis = $infobpr['kodejenis'] ?? '';
        }

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
            $text = preg_replace('/\s+/', ' ', $text);
            return trim($text);
        };

        $isEmpty = fn($v) => $v === null || $v === '';

        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}-12-31|LTBPRK|E0303|0|\r\n";

        $hasRealData = false;

        foreach ($data as $row) {
            if ((int) ($row['is_null_record'] ?? 0) === 1) {
                continue;
            }

            $nik = $sanitize($row['nik'] ?? '');
            $nama = $sanitize($row['nama'] ?? '');
            $perusahaan = $sanitize($row['perusahaan'] ?? '');
            $persensaham = $sanitize($row['persensaham'] ?? '');

            if ($nik === '' && $nama === '' && $perusahaan === '' && $persensaham === '') {
                continue;
            }

            $hasRealData = true;

            $output .= sprintf(
                "D01|022010000000|%s|%s|%s|%s|%s\r\n",
                $nik,
                $nama,
                $sanitize($row['kodebank'] ?? ''),
                $perusahaan,
                $persensaham
            );
        }

        if (!$hasRealData) {
            $output .= "D01|022010000000|0| |$sandibpr| |1.00\r\n";
        }

        foreach ($data_penjelastindak as $penjelas) {
            if ($penjelas['tindaklanjut'] !== null && trim($penjelas['tindaklanjut']) !== '') {
                $output .= "F01|" . $sanitize($penjelas['tindaklanjut']) . "\r\n";
            }
        }

        $filename = "LTBPRK-E0303-{$jenispelaporan}-A-{$exportDate}1231-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }
}
