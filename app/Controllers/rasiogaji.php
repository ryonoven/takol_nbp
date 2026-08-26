<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_rasiogaji;
use App\Models\M_infobpr;
use App\Models\M_user;
use App\Models\M_periodetahunan;
use App\Models\M_transparansicomments;
use App\Models\M_transparansicommentsread;
use App\Models\M_penjelastindak;
use Myth\Auth\Config\Services as AuthServices;

class rasiogaji extends Controller
{
    protected $auth;
    protected $session;
    protected $userKodebpr;
    protected $userId;

    protected $rasiogajiModel;
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
    private const SUBKAT = 'Rasiogaji';

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

    private function getRasiogajiModel(): M_rasiogaji
    {
        return $this->rasiogajiModel ??= new M_rasiogaji();
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

    private function jsonOk(string $msg, array $extra = [])
    {
        return $this->response->setJSON(
            array_merge(['status' => 'success', 'message' => $msg, 'ts' => time()], $extra)
        );
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

    private function getFreshRow(string $kodebpr, int $periodeId): ?array
    {
        return $this->getRasiogajiModel()
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();
    }

    private function deriveApprovalBundle(string $kodebpr, int $periodeId): array
    {
        $rows = $this->getRasiogajiModel()
            ->select('accdekom, accdekom_at, is_approved, approved_at')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->findAll();

        $allDekom = !empty($rows) && array_reduce($rows, fn($c, $r) => $c && (int) $r['accdekom'] === 1, true);
        $allDirut = !empty($rows) && array_reduce($rows, fn($c, $r) => $c && (int) $r['is_approved'] === 1, true);

        return [
            'rows' => $rows,
            'allDekom' => $allDekom,
            'allDirut' => $allDirut,
        ];
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

        $permissions = $this->getUserPermissions();
        $approval = $this->deriveApprovalBundle($kodebpr, $periodeId);
        $periodeDetail = $this->getPeriodeModel()->getPeriodeDetail($periodeId);
        $bprData = $this->getInfobprModel()->getBprByKode($kodebpr);
        $penjelastindak = $this->getPenjelastindakModel()
            ->getDataPenjelasByKodebprAndPeriode(self::SUBKAT, $kodebpr, $periodeId);

        $rasiogajiData = $this->getRasiogajiModel()
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->limit(1)
            ->findAll();

        $data = [
            'judul' => '12. Rasio Gaji Tertinggi dan Gaji Terendah',
            'rasiogaji' => $rasiogajiData,
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
            'userId' => $this->userId,
            'kodebpr' => $kodebpr,
            'periodeId' => $periodeId,
            'periodeDetail' => $periodeDetail,
            'bprData' => $bprData,
            'accdekomData' => $approval['rows'],
            'accdirutData' => $approval['rows'],
            'penjelastindak' => $penjelastindak,
            'canApprove' => $approval['allDekom'],
            'commentReadsModel' => $this->getCommentReadsModel(),
            'pollIntervalMs' => self::POLL_MIN_MS,
        ];

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('Laporantahunan/Transparansi/rasiogaji', $data);
        echo view('templates/v_footer');
    }

    public function tambahpenjelasAjax()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;

        $permissions = $this->getUserPermissions();
        if (!($permissions['pe'] ?? false) && !($permissions['admin'] ?? false)) {
            return $this->jsonErr('Anda tidak memiliki izin untuk mengubah data ini', 403);
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = (int) session('active_periode');

        if (!$kodebpr || !$periodeId) {
            return $this->jsonErr('Kode BPR atau periode tidak valid');
        }

        $fields = [
            'pegawaitinggi',
            'pegawairendah',
            'dirtinggi',
            'dirrendah',
            'dekomtinggi',
            'dekomrendah',
            'pegawaitinggirendah',
            'direksitinggirendah',
            'dekomtinggirendah',
            'dirdekomtinggirendah',
            'dirpegawaitinggirendah',
        ];

        $saveData = [];
        foreach ($fields as $f) {
            $saveData[$f] = $this->request->getPost($f) ?? '';
        }

        $saveData['periode_id'] = $periodeId;
        $saveData['user_id'] = $this->userId;
        $saveData['kodebpr'] = $kodebpr;
        $saveData['fullname'] = $this->userData['fullname'] ?? null;

        try {
            $existing = $this->getFreshRow($kodebpr, $periodeId);

            if ($existing) {
                $saveData['id'] = $existing['id'];
                $saveData['accdekom'] = $existing['accdekom'];
                $saveData['is_approved'] = $existing['is_approved'];
                $ok = $this->getRasiogajiModel()->save($saveData);
                return $ok
                    ? $this->jsonOk('Data berhasil diperbarui')
                    : $this->jsonErr('Gagal memperbarui data');
            }

            $saveData['accdekom'] = 0;
            $saveData['is_approved'] = 0;
            unset($saveData['id']);
            $ok = $this->getRasiogajiModel()->insert($saveData);
            return $ok
                ? $this->jsonOk('Data berhasil ditambahkan')
                : $this->jsonErr('Gagal menambahkan data');

        } catch (\Throwable $e) {
            log_message('error', '[rasiogaji::tambahpenjelasAjax] ' . $e->getMessage());
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

        if (!isset($_POST['TambahKomentar'])) {
            return redirect()->to(base_url(self::SUBKAT));
        }

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
        if ($e = $this->requireAjax())
            return $e;

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
            foreach ($ids as $cid) {
                $this->getCommentReadsModel()->markAsRead($cid, $userId);
            }
        }

        service('cache')->delete('unread_' . self::SUBKAT . "_{$kodebpr}_{$periodeId}_{$userId}");
        return $this->response->setJSON(['status' => 'success']);
    }

    public function getUnreadCommentCountForFactor()
    {
        if ($e = $this->requireAjax())
            return $e;

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

    public function tambahketerangan()
    {
        if ($r = $this->checkAuthentication())
            return $r;

        $periodeId = (int) session('active_periode');
        $kodebpr = $this->userKodebpr;
        $isAjax = $this->request->isAJAX();

        if (!$kodebpr || !$periodeId) {
            return $isAjax
                ? $this->jsonErr('Kode BPR atau periode tidak valid')
                : redirect()->back()->with('error', 'Kode BPR atau periode tidak valid');
        }

        $tindaklanjut = trim($this->request->getPost('tindaklanjut') ?? '');
        if (empty($tindaklanjut)) {
            return $isAjax
                ? $this->jsonErr('Penjelasan tidak boleh kosong')
                : redirect()->back()->with('error', 'Penjelasan tidak boleh kosong');
        }

        $existing = $this->getPenjelastindakModel()
            ->getDataPenjelasByKodebprAndPeriode(self::SUBKAT, $kodebpr, $periodeId);
        if (!empty($existing)) {
            return $isAjax
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

        if ($isAjax) {
            $newData = $this->getPenjelastindakModel()
                ->getDataPenjelasByKodebprAndPeriode(self::SUBKAT, $kodebpr, $periodeId);
            return $this->jsonOk('Penjelasan berhasil disimpan', ['penjelastindak' => $newData]);
        }

        session()->setFlashdata('message', 'Penjelasan berhasil disimpan');
        return redirect()->to(base_url(self::SUBKAT));
    }

    public function editketerangan()
    {
        if ($r = $this->checkAuthentication())
            return $r;

        $kodebpr = $this->userKodebpr;
        $periodeId = (int) session('active_periode');
        $isAjax = $this->request->isAJAX();

        if (!$kodebpr || !$periodeId) {
            return $isAjax
                ? $this->jsonErr('Kode BPR atau periode tidak valid')
                : redirect()->back()->with('error', 'Tidak valid');
        }

        $tindaklanjut = trim($this->request->getPost('tindaklanjut') ?? '');
        if (empty($tindaklanjut)) {
            return $isAjax
                ? $this->jsonErr('Penjelasan tidak boleh kosong')
                : redirect()->back()->with('error', 'Tidak boleh kosong');
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
            return $ok
                ? $this->jsonOk('Penjelasan berhasil dihapus')
                : $this->jsonErr('Gagal menghapus data');

        } catch (\Exception $e) {
            log_message('error', '[rasiogaji::setNullKolomTindakAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    private function bulkApprove(array $fields, bool $isError, string $msg)
    {
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        if (!$kodebpr || !$periodeId) {
            session()->setFlashdata('err', 'Kode BPR atau Periode ID tidak valid');
            return redirect()->back();
        }

        try {
            $this->getRasiogajiModel()
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->set(array_merge($fields, ['approved_by' => $this->userId]))
                ->update();

            session()->setFlashdata($isError ? 'err' : 'message', $msg);
        } catch (\Exception $e) {
            log_message('error', '[rasiogaji::bulkApprove] ' . $e->getMessage());
            session()->setFlashdata('err', 'Terjadi kesalahan sistem');
        }

        return redirect()->back();
    }

    public function approveSemuaKom()
    {
        return $this->bulkApprove(
            ['accdekom' => 1, 'accdekom_at' => date('Y-m-d H:i:s')],
            false,
            'Persetujuan Komisaris Utama berhasil diberikan.'
        );
    }

    public function unapproveSemuaKom()
    {
        return $this->bulkApprove(
            ['accdekom' => 0, 'is_approved' => 0, 'accdekom_at' => date('Y-m-d H:i:s')],
            true,
            'Persetujuan Komisaris Utama dibatalkan.'
        );
    }

    public function approveSemuaDirut()
    {
        return $this->bulkApprove(
            ['is_approved' => 1, 'approved_at' => date('Y-m-d H:i:s')],
            false,
            'Persetujuan Direktur Utama berhasil diberikan.'
        );
    }

    public function unapproveSemuaDirut()
    {
        return $this->bulkApprove(
            ['is_approved' => 0, 'approved_at' => null],
            true,
            'Persetujuan Direktur Utama dibatalkan.'
        );
    }

    public function exporttxtrasiogaji()
    {
        if ($r = $this->checkAuthentication())
            return $r;

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        $data_rasiogaji = $this->getRasiogajiModel()->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        $data_infobpr = $this->getInfobprModel()->getDataByKodebpr($kodebpr);
        $data_penjelastindak = $this->getPenjelastindakModel()
            ->where('subkategori', self::SUBKAT)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->findAll();

        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $sandibpr = $data_infobpr[0]['sandibpr'] ?? '';
            $kodejenis = $data_infobpr[0]['kodejenis'] ?? '';
        }

        $sanitize = function (string $text): string {
            $text = str_replace(["\r", "\n", "\t", '|'], [' ', ' ', ' ', ' '], $text);
            $text = str_replace(["\xE2\x80\x9C", "\xE2\x80\x9D"], '"', $text);
            $text = str_replace(["\xE2\x80\x98", "\xE2\x80\x99"], "'", $text);
            $text = str_replace('%', ' persen ', $text);
            $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
            return trim(preg_replace('/\s+/', ' ', $text));
        };

        $periodeDetail = $this->getPeriodeModel()->getPeriodeDetail($periodeId);
        $exportDate = $periodeDetail['tahun'] ?? date('Y');
        $jenispelaporan = $periodeDetail['jenispelaporan'];

        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}-12-31|LTBPRK|E0600|0|\r\n";

        $lineMap = [
            '070100000000' => 'pegawaitinggirendah',
            '070200000000' => 'direksitinggirendah',
            '070300000000' => 'dekomtinggirendah',
            '070400000000' => 'dirdekomtinggirendah',
            '070500000000' => 'dirpegawaitinggirendah',
        ];

        foreach ($data_rasiogaji as $row) {
            foreach ($lineMap as $code => $col) {
                $val = $sanitize((string) ($row[$col] ?? ''));
                if ($val !== '') {
                    $output .= "D01|{$code}|{$val}\r\n";
                }
            }
        }

        foreach ($data_penjelastindak as $p) {
            if (!empty(trim((string) ($p['tindaklanjut'] ?? '')))) {
                $output .= "F01|" . $sanitize((string) $p['tindaklanjut']) . "\r\n";
            }
        }

        $filename = "LTBPRK-E0600-{$jenispelaporan}-A-{$exportDate}1231-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }
}