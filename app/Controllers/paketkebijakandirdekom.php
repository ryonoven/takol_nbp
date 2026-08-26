<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_paketkebijakandirdekom;
use App\Models\M_infobpr;
use App\Models\M_user;
use App\Models\M_periodetahunan;
use App\Models\M_transparansicomments;
use App\Models\M_transparansicommentsread;
use App\Models\M_penjelastindak;
use Myth\Auth\Config\Services as AuthServices;

class Paketkebijakandirdekom extends Controller
{
    protected $auth;
    protected $session;
    protected $userKodebpr;
    protected $userId;

    protected $paketkebijakandirdekomModel;
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
    private const SUBKAT = 'Paketkebijakandirdekom';

    private const FIELDS = [
        'penerimagajidir',
        'nominalgajidir',
        'penerimagajidekom',
        'nominalgajidekom',
        'terimatunjangandir',
        'nominaltunjangandir',
        'terimatunjangandekom',
        'nominaltunjangandekom',
        'terimatantiemdir',
        'nominaltantiemdir',
        'terimatantiemdekom',
        'nominaltantiemdekom',
        'terimashmdir',
        'nominalshmdir',
        'terimashmdekom',
        'nominalshmdekom',
        'terimaremunlaindir',
        'nominalremunlaindir',
        'terimaremunlaindekom',
        'nominalremunlaindekom',
        'totalremundir',
        'totalremundekom',
        'terimarumahdir',
        'nominalrumahdir',
        'terimarumahdekom',
        'nominalrumahdekom',
        'terimatransportdir',
        'nominaltransportdir',
        'terimatransportdekom',
        'nominaltransportdekom',
        'terimaasuransidir',
        'nominalasuransidir',
        'terimaasuransidekom',
        'nominalasuransidekom',
        'terimafasilitasdir',
        'nominalfasilitasdir',
        'terimafasilitasdekom',
        'nominalfasilitasdekom',
        'totalfasdir',
        'totalfasdekom',
        'totaldir',
        'totaldekom',
    ];

    private const CURRENCY_FIELDS = [
        'nominalgajidir',
        'nominalgajidekom',
        'nominaltunjangandir',
        'nominaltunjangandekom',
        'nominaltantiemdir',
        'nominaltantiemdekom',
        'nominalshmdir',
        'nominalshmdekom',
        'nominalremunlaindir',
        'nominalremunlaindekom',
        'nominalrumahdir',
        'nominalrumahdekom',
        'nominaltransportdir',
        'nominaltransportdekom',
        'nominalasuransidir',
        'nominalasuransidekom',
        'nominalfasilitasdir',
        'nominalfasilitasdekom',
        'totalremundir',
        'totalremundekom',
        'totalfasdir',
        'totalfasdekom',
        'totaldir',
        'totaldekom',
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

    private function getPaketModel(): M_paketkebijakandirdekom
    {
        return $this->paketkebijakandirdekomModel ??= new M_paketkebijakandirdekom();
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

        $paketRow = $this->getPaketModel()
            ->where('periode_id', $periodeId)
            ->where('kodebpr', $kodebpr)
            ->first();

        $rows = $paketRow ? [$paketRow] : [];
        $approval = $this->deriveApprovalBundle($rows);
        $accdekomData = array_map(fn($r) => ['accdekom' => $r['accdekom'], 'accdekom_at' => $r['accdekom_at'] ?? null], $rows);
        $accdirutData = array_map(fn($r) => ['is_approved' => $r['is_approved'], 'approved_at' => $r['approved_at'] ?? null], $rows);

        $data = [
            'judul' => '11. Paket/Kebijakan Remunerasi dan Fasilitas Lain bagi Direksi dan Dewan Komisaris',
            'paketkebijakandirdekom' => $rows,
            'paketRow' => $paketRow,
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
        echo view('Laporantahunan/Transparansi/paketkebijakandirdekom', $data);
        echo view('templates/v_footer');
    }

    public function tambahpenjelasAjax()
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
                $val = $this->request->getPost($f) ?? '';
                if (in_array($f, self::CURRENCY_FIELDS, true)) {
                    $val = preg_replace('/[^0-9]/', '', $val);
                }
                $data[$f] = $val;
            }

            $data['periode_id'] = $periodeId;
            $data['user_id'] = $this->userId;
            $data['kodebpr'] = $kodebpr;
            $data['fullname'] = $this->userData['fullname'] ?? null;
            $data['accdekom'] = 0;
            $data['is_approved'] = 0;

            $existing = $this->getPaketModel()
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->first();

            if ($existing) {
                $data['id'] = $existing['id'];
                $ok = $this->getPaketModel()->save($data);
                $msg = 'Data berhasil diperbarui';
            } else {
                unset($data['id']);
                $ok = $this->getPaketModel()->insert($data);
                $msg = 'Data berhasil ditambahkan';
            }

            if (!$ok)
                return $this->jsonErr('Gagal menyimpan data');

            return $this->jsonOk($msg);

        } catch (\Exception $e) {
            log_message('error', '[Paketkebijakandirdekom::tambahpenjelasAjax] ' . $e->getMessage());
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
            $fresh = $this->getPenjelastindakModel()->getDataPenjelasByKodebprAndPeriode(self::SUBKAT, $kodebpr, $periodeId);
            return $this->jsonOk('Penjelasan berhasil diubah', ['penjelastindak' => $fresh]);
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

            return $this->jsonOk('Penjelasan berhasil dihapus');

        } catch (\Exception $e) {
            log_message('error', '[Paketkebijakandirdekom::setNullKolomTindakAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    private function bulkKomApprove(int $val, string $msg, string $flashType)
    {
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        if (!$kodebpr || !$periodeId) {
            session()->setFlashdata('err', 'Kode BPR atau Periode ID tidak valid');
            return redirect()->back();
        }

        try {
            $update = ['accdekom' => $val, 'accdekom_by' => $this->userId, 'accdekom_at' => date('Y-m-d H:i:s')];
            if ($val === 0)
                $update['is_approved'] = 0;

            $this->getPaketModel()
                ->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->update(null, $update);

            session()->setFlashdata($flashType, $msg);
        } catch (\Exception $e) {
            log_message('error', '[Paketkebijakandirdekom::bulkKomApprove] ' . $e->getMessage());
            session()->setFlashdata('err', 'Terjadi kesalahan sistem');
        }

        return redirect()->back();
    }

    private function bulkDirutApprove(int $val, string $msg, string $flashType)
    {
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        if (!$kodebpr || !$periodeId) {
            session()->setFlashdata('err', 'Kode BPR atau Periode ID tidak valid');
            return redirect()->back();
        }

        try {
            $update = ['is_approved' => $val, 'approved_by' => $this->userId, 'approved_at' => date('Y-m-d H:i:s')];
            if ($val === 0)
                $update['accdekom'] = 0;

            $this->getPaketModel()
                ->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->update(null, $update);

            session()->setFlashdata($flashType, $msg);
        } catch (\Exception $e) {
            log_message('error', '[Paketkebijakandirdekom::bulkDirutApprove] ' . $e->getMessage());
            session()->setFlashdata('err', 'Terjadi kesalahan sistem');
        }

        return redirect()->back();
    }

    public function approveSemuaKom()
    {
        return $this->bulkKomApprove(1, 'Persetujuan Komisaris Utama berhasil diberikan.', 'message');
    }
    public function unapproveSemuaKom()
    {
        return $this->bulkKomApprove(0, 'Persetujuan Komisaris Utama dibatalkan.', 'err');
    }
    public function approveSemuaDirut()
    {
        return $this->bulkDirutApprove(1, 'Persetujuan Direktur Utama berhasil diberikan.', 'message');
    }
    public function unapproveSemuaDirut()
    {
        return $this->bulkDirutApprove(0, 'Persetujuan Direktur Utama dibatalkan.', 'err');
    }

    public function exporttxtpaketkebijakandirdekom()
    {
        if ($r = $this->checkAuthentication())
            return $r;

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        $periodeDetail = $this->getPeriodeModel()->getPeriodeDetail($periodeId);
        $exportDate = $periodeDetail['tahun'] ?? date('Y');
        $jenispelaporan = $periodeDetail['jenispelaporan'];

        $data_paket = $this->getPaketModel()->getDataByKodebprAndPeriode($kodebpr, $periodeId);
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

        $isEmpty = fn($v) => $v === null || $v === '';

        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}-12-31|LTBPRK|E0500|0|\r\n";

        $order = [
            '611',
            '612',
            '613',
            '614',
            '615',
            '616',
            '621',
            '622',
            '623',
            '624',
            '625',
            '630'
        ];

        $lineMap = [
            '611' => ['penerimagajidir', 'nominalgajidir', 'penerimagajidekom', 'nominalgajidekom'],
            '612' => ['terimatunjangandir', 'nominaltunjangandir', 'terimatunjangandekom', 'nominaltunjangandekom'],
            '613' => ['terimatantiemdir', 'nominaltantiemdir', 'terimatantiemdekom', 'nominaltantiemdekom'],
            '614' => ['terimashmdir', 'nominalshmdir', 'terimashmdekom', 'nominalshmdekom'],
            '615' => ['terimaremunlaindir', 'nominalremunlaindir', 'terimaremunlaindekom', 'nominalremunlaindekom'],
            '621' => ['terimarumahdir', 'nominalrumahdir', 'terimarumahdekom', 'nominalrumahdekom'],
            '622' => ['terimatransportdir', 'nominaltransportdir', 'terimatransportdekom', 'nominaltransportdekom'],
            '623' => ['terimaasuransidir', 'nominalasuransidir', 'terimaasuransidekom', 'nominalasuransidekom'],
            '624' => ['terimafasilitasdir', 'nominalfasilitasdir', 'terimafasilitasdekom', 'nominalfasilitasdekom'],
        ];

        $totalLines = [
            '616' => ['totalremundir', 'totalremundekom'],
            '625' => ['totalfasdir', 'totalfasdekom'],
            '630' => ['totaldir', 'totaldekom'],
        ];

        foreach ($data_paket as $row) {
            foreach ($order as $code) {
                if (isset($lineMap[$code])) {
                    $cols = $lineMap[$code];
                    $v = array_map(fn($c) => $sanitize($row[$c] ?? ''), $cols);

                    if (array_filter($v, fn($x) => !$isEmpty($x))) {
                        $output .= "D01|{$code}|{$v[0]}|{$v[1]}|{$v[2]}|{$v[3]}\r\n";
                    }
                }
                if (isset($totalLines[$code])) {
                    $v0 = $sanitize($row[$totalLines[$code][0]] ?? '');
                    $v1 = $sanitize($row[$totalLines[$code][1]] ?? '');

                    if (!$isEmpty($v0) || !$isEmpty($v1)) {
                        $output .= "D01|{$code}||{$v0}||{$v1}\r\n";
                    }
                }
            }
        }

        $hasF01 = false;

        foreach ($data_penjelas as $penjelas) {
            if ($penjelas['tindaklanjut'] !== null && trim($penjelas['tindaklanjut']) !== '') {
                $output .= "F01|" . $sanitize($penjelas['tindaklanjut']) . "\r\n";
                $hasF01 = true;
            }
        }

        if (!$hasF01) {
            $output .= "F01|\r\n";
        }

        $filename = "LTBPRK-E0500-{$jenispelaporan}-A-{$exportDate}1231-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }
}