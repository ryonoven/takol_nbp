<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_fraudinternal;
use App\Models\M_infobpr;
use App\Models\M_user;
use App\Models\M_periodetahunan;
use App\Models\M_transparansicomments;
use App\Models\M_transparansicommentsread;
use App\Models\M_penjelastindak;
use Myth\Auth\Config\Services as AuthServices;

class Fraudinternal extends Controller
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

    private const CACHE_TTL = 30;
    private const POLL_MIN_MS = 10000;
    private const SUBKAT = 'Fraudinternal';

    private const FIELDS = [
        'fraudtahunlaporandir',
        'fraudtahunsebelumdir',
        'selesaitahunlaporandir',
        'prosestahunlaporandir',
        'prosestahunsebelumdir',
        'belumtahunlaporandir',
        'belumtahunsebelumdir',
        'hukumtahunlaporandir',
        'fraudtahunlaporandekom',
        'fraudtahunsebelumdekom',
        'selesaitahunlaporandekom',
        'prosestahunlaporandekom',
        'prosestahunsebelumdekom',
        'belumtahunlaporandekom',
        'belumtahunsebelumdekom',
        'hukumtahunlaporandekom',
        'fraudtahunlaporankartap',
        'fraudtahunsebelumkartap',
        'selesaitahunlaporankartap',
        'prosestahunlaporankartap',
        'prosestahunsebelumkartap',
        'belumtahunlaporankartap',
        'belumtahunsebelumkartap',
        'hukumtahunlaporankartap',
        'fraudtahunlaporankontrak',
        'fraudtahunsebelumkontrak',
        'selesaitahunlaporankontrak',
        'prosestahunlaporankontrak',
        'prosestahunsebelumkontrak',
        'belumtahunlaporankontrak',
        'belumtahunsebelumkontrak',
        'hukumtahunlaporankontrak',
    ];

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

    private function getFraudinternalModel(): M_fraudinternal
    {
        return $this->fraudinternalModel ??= new M_fraudinternal();
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

    private function requireEditor()
    {
        $p = $this->getUserPermissions();
        if (!($p['pe'] ?? false) && !($p['admin'] ?? false)) {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses untuk mengubah data ini',
            ]);
        }
        return null;
    }

    private function deriveApprovalBundle(array $rows): array
    {
        $allDekom = !empty($rows) && array_reduce($rows, fn($c, $r) => $c && (int) $r['accdekom'] === 1, true);
        $allDirut = !empty($rows) && array_reduce($rows, fn($c, $r) => $c && (int) $r['is_approved'] === 1, true);
        return compact('allDekom', 'allDirut');
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

        $perms = $this->getUserPermissions();

        $fraudRow = $this->getFraudinternalModel()
            ->where('periode_id', $periodeId)->where('kodebpr', $kodebpr)->first();

        $rows = $fraudRow ? [$fraudRow] : [];
        $approval = $this->deriveApprovalBundle($rows);
        $accdekomData = array_map(fn($r) => ['accdekom' => $r['accdekom'], 'accdekom_at' => $r['accdekom_at'] ?? null], $rows);
        $accdirutData = array_map(fn($r) => ['is_approved' => $r['is_approved'], 'approved_at' => $r['approved_at'] ?? null], $rows);

        $data = [
            'judul' => '15. Jumlah Penyimpangan Internal (Internal Fraud)',
            'fraudinternal' => $rows,
            'fraudRow' => $fraudRow,
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
            'canEdit' => (($perms['pe'] ?? false) || ($perms['admin'] ?? false)),
        ];

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('Laporantahunan/Transparansi/fraudinternal', $data);
        echo view('templates/v_footer');
    }

    public function tambahfraudajax()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;
        if ($e = $this->requireEditor())
            return $e;

        try {
            $kodebpr = $this->userKodebpr;
            $periodeId = (int) session('active_periode');

            if (!$kodebpr || !$periodeId)
                return $this->jsonErr('Kode BPR atau periode tidak valid');

            $data = [];
            foreach (self::FIELDS as $f) {
                $data[$f] = (int) ($this->request->getPost($f) ?? 0);
            }
            $data['periode_id'] = $periodeId;
            $data['user_id'] = $this->userId;
            $data['kodebpr'] = $kodebpr;
            $data['fullname'] = $this->userData['fullname'] ?? null;
            $data['accdekom'] = 0;
            $data['is_approved'] = 0;
            $data['is_null_record'] = 0;

            $existing = $this->getFraudinternalModel()
                ->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();

            if ($existing) {
                $data['id'] = $existing['id'];
                $ok = $this->getFraudinternalModel()->save($data);
                $msg = 'Data berhasil diperbarui';
            } else {
                unset($data['id']);
                $ok = $this->getFraudinternalModel()->insert($data);
                $msg = 'Data berhasil ditambahkan';
            }

            if (!$ok)
                return $this->jsonErr('Gagal menyimpan data');

            return $this->jsonOk($msg);

        } catch (\Exception $e) {
            log_message('error', '[Fraudinternal::tambahfraudajax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function getKomentarByFaktorId()
    {
        if (!$this->request->isAJAX())
            return $this->response->setStatusCode(404)->setBody('Not Found');

        $list = $this->getKomentarModel()
            ->getKomentarByFaktorId(self::SUBKAT, $this->userKodebpr, session('active_periode'));
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

            if (empty($tindaklanjut))
                return $this->jsonErr('Penjelasan tidak boleh kosong');
            if (!$kodebpr || !$periodeId)
                return $this->jsonErr('Kode BPR atau periode tidak valid');

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

        } catch (\Exception $e) {
            log_message('error', '[Fraudinternal::tambahketerangan] ' . $e->getMessage());
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

            if (!$kodebpr)
                return $this->jsonErr('User tidak memiliki kode BPR yang valid');
            if (!$periodeId)
                return $this->jsonErr('Periode tidak valid');
            if (empty($tindaklanjut))
                return $this->jsonErr('Penjelasan tidak boleh kosong');

            $ok = $this->getPenjelastindakModel()->editberdasarkankodedanperiode(
                ['tindaklanjut' => $tindaklanjut, 'user_id' => $this->userId, 'kodebpr' => $kodebpr],
                self::SUBKAT,
                $kodebpr,
                $periodeId
            );

            if (!$ok)
                return $this->jsonErr('Gagal mengubah data');

            $fresh = $this->getPenjelastindakModel()
                ->getDataPenjelasByKodebprAndPeriode(self::SUBKAT, $kodebpr, $periodeId);
            return $this->jsonOk('Penjelasan berhasil diubah', ['penjelastindak' => $fresh]);

        } catch (\Exception $e) {
            log_message('error', '[Fraudinternal::editketerangan] ' . $e->getMessage());
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

            $existing = $this->getPenjelastindakModel()->where('id', $id)->where('kodebpr', $kodebpr)->first();
            if (!$existing)
                return $this->jsonErr('Data tidak ditemukan atau tidak memiliki akses');

            $ok = $this->getPenjelastindakModel()->where('id', $id)->where('kodebpr', $kodebpr)->delete();
            if (!$ok)
                return $this->jsonErr('Gagal menghapus data');

            return $this->jsonOk('Penjelasan berhasil dihapus');

        } catch (\Exception $e) {
            log_message('error', '[Fraudinternal::setNullKolomTindakAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    // ── approval ───────────────────────────────────────────────────────────────

    private function doApprovalUpdate(int $val, string $field): void
    {
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');
        if (!$kodebpr || !$periodeId)
            return;

        $ts = $val ? date('Y-m-d H:i:s') : null;
        $update = [
            $field => $val,
            $field . '_by' => $val ? $this->userId : null,
            $field . '_at' => $ts,
        ];
        if ($field === 'is_approved')
            $update['approved_at'] = $ts;
        if ($field === 'accdekom')
            $update['accdekom_at'] = $ts;

        try {
            $this->getFraudinternalModel()
                ->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->update(null, $update);
        } catch (\Exception $e) {
            log_message('error', '[Fraudinternal::doApprovalUpdate] ' . $e->getMessage());
        }
    }

    /** AJAX endpoint used by autosave to silently reset both approvals before save */
    public function unapproveSemuaKomAjax()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;

        $this->doApprovalUpdate(0, 'accdekom');
        $this->doApprovalUpdate(0, 'is_approved');

        return $this->jsonOk('Approval direset');
    }

    public function approveSemuaKom()
    {
        $this->doApprovalUpdate(1, 'accdekom');
        session()->setFlashdata('message', 'Persetujuan Komisaris Utama berhasil diberikan.');
        return redirect()->back();
    }

    public function unapproveSemuaKom()
    {
        $this->doApprovalUpdate(0, 'accdekom');
        $this->doApprovalUpdate(0, 'is_approved');
        session()->setFlashdata('err', 'Persetujuan Komisaris Utama dibatalkan.');
        return redirect()->back();
    }

    public function approveSemuaDirut()
    {
        $this->doApprovalUpdate(1, 'is_approved');
        session()->setFlashdata('message', 'Persetujuan Direktur Utama berhasil diberikan.');
        return redirect()->back();
    }

    public function unapproveSemuaDirut()
    {
        $this->doApprovalUpdate(0, 'is_approved');
        $this->doApprovalUpdate(0, 'accdekom');
        session()->setFlashdata('err', 'Persetujuan Direktur Utama dibatalkan.');
        return redirect()->back();
    }

    public function setNodataAjax()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;
        if ($e = $this->requireEditor())
            return $e;

        try {
            $kodebpr = $this->userKodebpr;
            $periodeId = (int) session('active_periode');

            if (!$kodebpr || !$periodeId)
                return $this->jsonErr('Kode BPR atau periode tidak valid');

            $existing = $this->getFraudinternalModel()
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->first();

            if ($existing) {
                // Update existing row dengan is_null_record = 1
                $ok = $this->getFraudinternalModel()
                    ->where('kodebpr', $kodebpr)
                    ->where('periode_id', $periodeId)
                    ->update(null, ['is_null_record' => 1, 'accdekom' => 0, 'is_approved' => 0]);
            } else {
                // Insert baru dengan semua field = 0 dan is_null_record = 1
                $data = array_fill_keys(self::FIELDS, 0);
                $data['is_null_record'] = 1;
                $data['periode_id'] = $periodeId;
                $data['user_id'] = $this->userId;
                $data['kodebpr'] = $kodebpr;
                $data['fullname'] = $this->userData['fullname'] ?? null;
                $data['accdekom'] = 0;
                $data['is_approved'] = 0;
                $ok = $this->getFraudinternalModel()->insert($data);
            }

            if (!$ok)
                return $this->jsonErr('Gagal menyimpan data');

            return $this->jsonOk('Data berhasil ditandai sebagai tidak ada');

        } catch (\Throwable $e) {
            log_message('error', '[Fraudinternal::setNodataAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function batalNodataAjax()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;
        if ($e = $this->requireEditor())
            return $e;

        try {
            $kodebpr = $this->userKodebpr;
            $periodeId = (int) session('active_periode');

            if (!$kodebpr || !$periodeId)
                return $this->jsonErr('Data tidak valid');

            $ok = $this->getFraudinternalModel()
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->update(null, ['is_null_record' => 0]);

            if (!$ok)
                return $this->jsonErr('Gagal membatalkan status');

            return $this->jsonOk('Status berhasil dibatalkan');

        } catch (\Throwable $e) {
            log_message('error', '[Fraudinternal::batalNodataAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function exporttxtfraudinternal()
    {
        if ($r = $this->checkAuthentication())
            return $r;

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        $periodeDetail = $this->getPeriodeModel()->getPeriodeDetail($periodeId);
        $exportDate = $periodeDetail['tahun'] ?? date('Y');
        $jenispelaporan = $periodeDetail['jenispelaporan'];

        $data = $this->getFraudinternalModel()->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        $data_infobpr = $this->getInfobprModel()->getDataByKodebpr($kodebpr);
        $data_penjelas = $this->getPenjelastindakModel()
            ->where('subkategori', self::SUBKAT)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
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

        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}-12-31|LTBPRK|E0800|0|\r\n";

        $codes = ['910', '911', '912', '913', '920'];
        $hasData = false;

        foreach ($data as $row) {
            $hasData = true;

            $v = fn($k) => $sanitize($row[$k] ?? '');

            $lines = [
                '910' => [$v('fraudtahunsebelumdir'), $v('fraudtahunlaporandir'), $v('fraudtahunsebelumdekom'), $v('fraudtahunlaporandekom'), $v('fraudtahunsebelumkartap'), $v('fraudtahunlaporankartap'), $v('fraudtahunsebelumkontrak'), $v('fraudtahunlaporankontrak')],
                '911' => ['', $v('selesaitahunlaporandir'), '', $v('selesaitahunlaporandekom'), '', $v('selesaitahunlaporankartap'), '', $v('selesaitahunlaporankontrak')],
                '912' => [$v('prosestahunsebelumdir'), $v('prosestahunlaporandir'), $v('prosestahunsebelumdekom'), $v('prosestahunlaporandekom'), $v('prosestahunsebelumkartap'), $v('prosestahunlaporankartap'), $v('prosestahunsebelumkontrak'), $v('prosestahunlaporankontrak')],
                '913' => [$v('belumtahunsebelumdir'), $v('belumtahunlaporandir'), $v('belumtahunsebelumdekom'), $v('belumtahunlaporandekom'), $v('belumtahunsebelumkartap'), $v('belumtahunlaporankartap'), $v('belumtahunsebelumkontrak'), $v('belumtahunlaporankontrak')],
                '920' => ['', $v('hukumtahunlaporandir'), '', $v('hukumtahunlaporandekom'), '', $v('hukumtahunlaporankartap'), '', $v('hukumtahunlaporankontrak')],
            ];

            foreach ($lines as $code => $vals) {
                $output .= "D01|{$code}|" . implode('|', $vals) . "\r\n";
            }
        }

        if (!$hasData) {
            foreach ($codes as $code) {
                $output .= "D01|{$code}||||||||\r\n";
            }
        }

        foreach ($data_penjelas as $p) {
            if (!empty($p['tindaklanjut'])) {
                $output .= "F01|" . $sanitize($p['tindaklanjut']) . "\r\n";
            }
        }

        $filename = "LTBPRK-E0800-{$jenispelaporan}-A-{$exportDate}1231-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }
}