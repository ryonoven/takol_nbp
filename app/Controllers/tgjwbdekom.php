<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_tgjwbdekom;
use App\Models\M_infobpr;
use App\Models\M_user;
use App\Models\M_periodetahunan;
use App\Models\M_transparansicomments;
use App\Models\M_transparansicommentsread;
use App\Models\M_penjelastindak;
use Myth\Auth\Config\Services as AuthServices;

class Tgjwbdekom extends Controller
{
    protected $auth;
    protected $tgjwbdekomModel;
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

    private const SUBKATEGORI = 'Tgjwbdekom';
    private const ALLOWED_JABATAN = ['0100', '0101', '0200', '0201'];
    private const CACHE_TTL_USER = 300;
    private const CACHE_TTL_UNREAD = 30;
    private const CACHE_TTL_KOMEN = 60;

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');

        $this->tgjwbdekomModel = new M_tgjwbdekom();
        $this->periodeModel = new M_periodetahunan();
        $this->userModel = new M_user();
        $this->infobprModel = new M_infobpr();
        $this->komentarModel = new M_transparansicomments();
        $this->commentReadsModel = new M_transparansicommentsread();
        $this->penjelastindakModel = new M_penjelastindak();
        $this->session = service('session');
        $this->auth = service('authentication');

        $userId = $this->auth->id();

        $userData = cache()->remember("user_data_{$userId}", self::CACHE_TTL_USER, function () use ($userId) {
            return $this->userModel->select('kodebpr, fullname')->find($userId);
        });

        $this->userId = $userId;
        $this->userKodebpr = $userData['kodebpr'] ?? null;
        $this->userFullname = $userData['fullname'] ?? 'Unknown';

        $authorize = AuthServices::authorization();

        $groups = cache()->remember("user_groups_{$userId}", self::CACHE_TTL_USER, function () use ($authorize, $userId) {
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

    private function requireLogin(): ?object
    {
        if (!$this->auth->check()) {
            return redirect()->to(session('redirect_url') ?? '/login');
        }
        return null;
    }

    private function requireAjax(): ?object
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)
                ->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }
        return null;
    }

    private function requireAuth(): ?object
    {
        if (!$this->auth->check()) {
            return $this->response->setStatusCode(401)
                ->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }
        return null;
    }

    private function jsonOk(string $message, array $extra = []): object
    {
        return $this->response->setJSON(
            array_merge(['status' => 'success', 'message' => $message], $extra)
        );
    }

    private function jsonErr(string $message, int $code = 200): object
    {
        return $this->response->setStatusCode($code)
            ->setJSON(['status' => 'error', 'message' => $message]);
    }

    private function getFreshRows(string $kodebpr, int $periodeId): array
    {
        return $this->tgjwbdekomModel
            ->select('id, nik, dekom, jabatan, tugastgjwbdekom, accdekom, is_approved')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    private function isValidJabatan(string $jabatan): bool
    {
        return in_array($jabatan, self::ALLOWED_JABATAN, true);
    }

    private function isValidNik(string $nik): bool
    {
        return ctype_digit($nik) && strlen($nik) === 16;
    }

    private function bulkUpdate(array $data, string $message, bool $isError = false): object
    {
        $kodebpr = $this->userKodebpr;
        $periodeId = (int) session('active_periode');

        if (!$kodebpr || !$periodeId) {
            session()->setFlashdata('err', 'Kode BPR atau Periode ID tidak valid');
            return redirect()->back();
        }

        $count = $this->tgjwbdekomModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->countAllResults();

        if ($count === 0) {
            session()->setFlashdata('err', 'Tidak ada data untuk diupdate');
            return redirect()->back();
        }

        try {
            $this->tgjwbdekomModel
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->update(null, $data);

            $isError
                ? session()->setFlashdata('err', $message)
                : session()->setFlashdata('message', $message);

        } catch (\Exception $e) {
            log_message('error', '[Tgjwbdekom::bulkUpdate] ' . $e->getMessage());
            session()->setFlashdata('err', 'Terjadi kesalahan sistem');
        }

        return redirect()->back();
    }

    private function resetApprovalBulk(string $kodebpr, int $periodeId): void
    {
        $this->tgjwbdekomModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->update(null, ['accdekom' => 0, 'is_approved' => 0]);
    }

    private function komentarCacheKey(string $kodebpr, $periodeId): string
    {
        return 'komentar_' . self::SUBKATEGORI . "_{$kodebpr}_{$periodeId}";
    }

    private function invalidateKomentarCache(string $kodebpr, $periodeId): void
    {
        cache()->delete($this->komentarCacheKey($kodebpr, $periodeId));
    }

    private function sanitize(?string $text): string
    {
        if ($text === null)
            return '';
        $text = str_replace(["\r", "\n", "\t", '|'], ' ', $text);
        $text = str_replace(["\xE2\x80\x9C", "\xE2\x80\x9D"], '"', $text);
        $text = str_replace(["\xE2\x80\x98", "\xE2\x80\x99"], "'", $text);
        $text = str_replace('%', ' persen ', $text);
        $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    public function index()
    {
        if ($r = $this->requireLogin())
            return $r;

        if (!session('active_periode')) {
            return redirect()->to('/Periodetransparansi');
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = (int) session('active_periode');

        if (!$kodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
        }

        $tgjwbdekomAll = $this->tgjwbdekomModel
            ->select('id, nik, dekom, jabatan, tugastgjwbdekom, accdekom, accdekom_by, accdekom_at, komut, is_approved, approved_by, approved_at, dirut')
            ->where('periode_id', $periodeId)
            ->where('kodebpr', $kodebpr)
            ->orderBy('id', 'ASC')
            ->findAll();

        $totalRows = count($tgjwbdekomAll);
        $allDekomApproved = $totalRows > 0 && array_sum(array_column($tgjwbdekomAll, 'accdekom')) === $totalRows;
        $allDirutApproved = $totalRows > 0 && array_sum(array_column($tgjwbdekomAll, 'is_approved')) === $totalRows;
        $canApprove = $allDekomApproved;

        $lastDekomAt = $allDekomApproved && $totalRows > 0
            ? $tgjwbdekomAll[$totalRows - 1]['accdekom_at'] : null;
        $lastDirutAt = $allDirutApproved && $totalRows > 0
            ? $tgjwbdekomAll[0]['approved_at'] : null;

        $komentarList = cache()->remember(
            $this->komentarCacheKey($kodebpr, $periodeId),
            self::CACHE_TTL_KOMEN,
            fn() => $this->komentarModel
                ->where('subkategori', self::SUBKATEGORI)
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->findAll()
        );

        $periodeDetail = $this->periodeModel->getPeriodeDetail($periodeId);
        $bprData = $this->infobprModel->getBprByKode($kodebpr);
        $penjelastindak = $this->penjelastindakModel
            ->getDataPenjelasByKodebprAndPeriode(self::SUBKATEGORI, $kodebpr, $periodeId);

        $lastVisit = session('last_visit_komentar') ?? date('Y-m-d H:i:s', strtotime('-1 day'));
        session()->set('last_visit_komentar', date('Y-m-d H:i:s'));

        $tgjwbdekomData = array_map(fn($r) => [
            'id' => (int) $r['id'],
            'nik' => $r['nik'] ?? '',
            'dekom' => $r['dekom'] ?? '',
            'jabatan' => $r['jabatan'] ?? '',
            'tugastgjwbdekom' => $r['tugastgjwbdekom'] ?? '',
            'accdekom' => (int) ($r['accdekom'] ?? 0),
            'is_approved' => (int) ($r['is_approved'] ?? 0),
        ], $tgjwbdekomAll);

        $data = [
            'judul' => '3. Pelaksanaan Tugas dan Tanggung Jawab Anggota Dewan Komisaris',
            'tgjwbdekom' => $tgjwbdekomData,
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
            'canApprove' => $canApprove,
            'allDekomApproved' => $allDekomApproved,
            'allDirutApproved' => $allDirutApproved,
            'lastDekomAt' => $lastDekomAt,
            'lastDirutAt' => $lastDirutAt,
            'userInGroupPE' => $this->userInGroupPE,
            'userInGroupAdmin' => $this->userInGroupAdmin,
            'userInGroupDekom' => $this->userInGroupDekom,
            'userInGroupDekom2' => $this->userInGroupDekom2,
            'userInGroupDekom3' => $this->userInGroupDekom3,
            'userInGroupDekom4' => $this->userInGroupDekom4,
            'userInGroupDekom5' => $this->userInGroupDekom5,
            'userInGroupDireksi' => $this->userInGroupDireksi,
            'userInGroupDireksi2' => $this->userInGroupDireksi2,
        ];

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('Laporantahunan/Transparansi/tgjwbdekom', $data);
        echo view('templates/v_footer');
    }

    public function tambahAjax()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        $kodebpr = $this->userKodebpr;
        $periodeId = (int) session('active_periode');

        if (!$kodebpr || !$periodeId) {
            return $this->jsonErr('Kode BPR atau periode tidak valid');
        }

        $dekom = trim($this->request->getPost('dekom') ?? '');
        $nik = trim($this->request->getPost('nik') ?? '');
        $jabatan = trim($this->request->getPost('jabatan') ?? '');
        $tugas = trim($this->request->getPost('tugastgjwbdekom') ?? '');

        if (!$dekom || !$nik || !$jabatan || !$tugas) {
            return $this->jsonErr('Nama Dewan Komisaris, NIK, Jabatan, dan Tugas wajib diisi');
        }
        if (!$this->isValidNik($nik))
            return $this->jsonErr('NIK harus berupa 16 digit angka');
        if (!$this->isValidJabatan($jabatan))
            return $this->jsonErr('Jabatan tidak valid');

        try {
            $this->tgjwbdekomModel->checkIncrement();

            $inserted = $this->tgjwbdekomModel->insert([
                'nik' => $nik,
                'dekom' => $dekom,
                'jabatan' => $jabatan,
                'tugastgjwbdekom' => $tugas,
                'periode_id' => $periodeId,
                'user_id' => $this->userId,
                'kodebpr' => $kodebpr,
                'fullname' => $this->userFullname,
                'accdekom' => 0,
                'is_approved' => 0,
            ]);

            if (!$inserted)
                return $this->jsonErr('Gagal menyimpan data');
            $this->resetApprovalBulk($kodebpr, $periodeId);

            return $this->jsonOk('Data berhasil ditambahkan', [
                'data' => $this->getFreshRows($kodebpr, $periodeId),
            ]);

        } catch (\Exception $e) {
            log_message('error', '[Tgjwbdekom::tambahAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function ubahAjax()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        $id = (int) $this->request->getPost('id');
        $kodebpr = $this->userKodebpr;
        $periodeId = (int) session('active_periode');

        if (!$id || !$kodebpr || !$periodeId) {
            return $this->jsonErr('Data tidak valid');
        }

        $existing = $this->tgjwbdekomModel
            ->where('id', $id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if (!$existing) {
            return $this->jsonErr('Data tidak ditemukan atau tidak memiliki akses');
        }

        $dekom = trim($this->request->getPost('dekom') ?? '');
        $nik = trim($this->request->getPost('nik') ?? '');
        $jabatan = trim($this->request->getPost('jabatan') ?? '');
        $tugas = trim($this->request->getPost('tugastgjwbdekom') ?? '');

        if (!$dekom || !$nik || !$jabatan || !$tugas) {
            return $this->jsonErr('Nama Dewan Komisaris, NIK, Jabatan, dan Tugas wajib diisi');
        }
        if (!$this->isValidNik($nik))
            return $this->jsonErr('NIK harus berupa 16 digit angka');
        if (!$this->isValidJabatan($jabatan))
            return $this->jsonErr('Jabatan tidak valid');

        try {
            $updated = $this->tgjwbdekomModel
                ->where('id', $id)
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->set([
                    'nik' => $nik,
                    'dekom' => $dekom,
                    'jabatan' => $jabatan,
                    'tugastgjwbdekom' => $tugas,
                    'user_id' => $this->userId,
                    'accdekom' => 0,
                    'is_approved' => 0,
                ])
                ->update();

            if (!$updated)
                return $this->jsonErr('Gagal mengubah data');

            $this->resetApprovalBulk($kodebpr, $periodeId);

            return $this->jsonOk('Data berhasil diubah', [
                'data' => $this->getFreshRows($kodebpr, $periodeId),
            ]);

        } catch (\Exception $e) {
            log_message('error', '[Tgjwbdekom::ubahAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function hapusAjax()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        $id = (int) $this->request->getPost('id');
        $kodebpr = $this->userKodebpr;
        $periodeId = (int) session('active_periode');

        if (!$id || !$kodebpr || !$periodeId) {
            return $this->jsonErr('Data tidak valid');
        }

        $existing = $this->tgjwbdekomModel
            ->where('id', $id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if (!$existing) {
            return $this->jsonErr('Data tidak ditemukan atau tidak memiliki akses');
        }

        try {
            $deleted = $this->tgjwbdekomModel
                ->where('id', $id)
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->delete();

            if (!$deleted)
                return $this->jsonErr('Gagal menghapus data');

            return $this->jsonOk('Data berhasil dihapus', [
                'data' => $this->getFreshRows($kodebpr, $periodeId),
            ]);

        } catch (\Exception $e) {
            log_message('error', '[Tgjwbdekom::hapusAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
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
        ], 'Approval dibatalkan.', true);
    }

    public function tambahKeteranganAjax()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        $kodebpr = $this->userKodebpr;
        $periodeId = (int) session('active_periode');

        if (!$kodebpr || !$periodeId) {
            return $this->jsonErr('Kode BPR atau periode tidak valid');
        }

        $tindaklanjut = trim($this->request->getPost('tindaklanjut') ?? '');
        $penjelasanlanjut = trim($this->request->getPost('penjelasanlanjut') ?? '');

        if (empty($tindaklanjut)) {
            return $this->jsonErr('Rekomendasi/Tindak Lanjut wajib diisi');
        }

        try {
            $inserted = $this->penjelastindakModel->tambahpenjelastindak([
                'subkategori' => self::SUBKATEGORI,
                'tindaklanjut' => $tindaklanjut,
                'penjelasanlanjut' => $penjelasanlanjut ?: null,
                'kodebpr' => $kodebpr,
                'periode_id' => $periodeId,
                'fullname' => $this->userFullname,
                'user_id' => $this->userId,
            ]);

            if (!$inserted)
                return $this->jsonErr('Gagal menyimpan data');

            return $this->jsonOk('Tindak lanjut berhasil disimpan');

        } catch (\Exception $e) {
            log_message('error', '[Tgjwbdekom::tambahKeteranganAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function editKeteranganAjax()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        $kodebpr = $this->userKodebpr;
        $periodeId = (int) session('active_periode');

        if (!$kodebpr || !$periodeId) {
            return $this->jsonErr('Data tidak valid');
        }

        $tindaklanjut = trim($this->request->getPost('tindaklanjut') ?? '');
        $penjelasanlanjut = trim($this->request->getPost('penjelasanlanjut') ?? '');

        if (empty($tindaklanjut)) {
            return $this->jsonErr('Rekomendasi/Tindak Lanjut tidak boleh kosong');
        }

        try {
            $data = [
                'user_id' => $this->userId,
                'kodebpr' => $kodebpr,
                'tindaklanjut' => $tindaklanjut,
            ];
            if ($penjelasanlanjut !== '') {
                $data['penjelasanlanjut'] = $penjelasanlanjut;
            }

            $updated = $this->penjelastindakModel
                ->editberdasarkankodedanperiode($data, self::SUBKATEGORI, $kodebpr, $periodeId);

            if (!$updated)
                return $this->jsonErr('Gagal mengubah data');

            return $this->jsonOk('Tindak lanjut berhasil diubah');

        } catch (\Exception $e) {
            log_message('error', '[Tgjwbdekom::editKeteranganAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function setNullKolomTindakAjax()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        $id = (int) $this->request->getPost('id');
        if (!$id)
            return $this->jsonErr('ID tidak valid');

        try {
            if (!$this->penjelastindakModel->setNullKolomTindak($id)) {
                return $this->jsonErr('Gagal menghapus data');
            }
            return $this->jsonOk('Rekomendasi/Tindak lanjut berhasil dihapus');
        } catch (\Exception $e) {
            log_message('error', '[Tgjwbdekom::setNullKolomTindakAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function setNullKolomPenjelaslanjutAjax()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        $id = (int) $this->request->getPost('id');
        if (!$id)
            return $this->jsonErr('ID tidak valid');

        try {
            if (!$this->penjelastindakModel->setNullKolomPenjelaslanjut($id)) {
                return $this->jsonErr('Gagal menghapus data');
            }
            return $this->jsonOk('Penjelasan lanjut berhasil dihapus');
        } catch (\Exception $e) {
            log_message('error', '[Tgjwbdekom::setNullKolomPenjelaslanjutAjax] ' . $e->getMessage());
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

        $list = $this->komentarModel->getKomentarByFaktorId(self::SUBKATEGORI, $kodebpr, $periodeId);
        return $this->response->setJSON($list);
    }

    public function Tambahkomentar()
    {
        if ($r = $this->requireLogin())
            return $r;

        if (!isset($_POST['TambahKomentar'])) {
            return redirect()->to(base_url('Tgjwbdekom'));
        }

        $kodebpr = $this->userKodebpr;
        if (!$kodebpr) {
            session()->setFlashdata('error', 'User tidak memiliki kode BPR yang valid');
            return redirect()->back();
        }

        if (!$this->validate(['komentar' => ['label' => 'Komentar', 'rules' => 'required']])) {
            session()->setFlashdata('err', \Config\Services::validation()->listErrors());
            return redirect()->back();
        }

        $periodeId = session('active_periode');

        $this->komentarModel->insertKomentar([
            'id' => $this->request->getPost('id'),
            'subkategori' => self::SUBKATEGORI,
            'komentar' => $this->request->getPost('komentar'),
            'fullname' => $this->request->getPost('fullname'),
            'user_id' => $this->userId,
            'kodebpr' => $kodebpr,
            'periode_id' => $periodeId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->invalidateKomentarCache($kodebpr, $periodeId);
        session()->setFlashdata('message', 'Komentar berhasil ditambahkan');
        return redirect()->to(base_url('Tgjwbdekom') . '?modal_komentar=' . $this->request->getPost('id'));
    }

    public function getUnreadCommentCountForFactor()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)
                ->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $id = $this->request->getGet('id');
        $kodebpr = $this->userKodebpr;
        $userId = $this->userId;
        $periodeId = session('active_periode');

        if (!$id || !$kodebpr || !$userId || !$periodeId) {
            return $this->response->setStatusCode(400)
                ->setJSON(['status' => 'error', 'message' => 'Missing data.']);
        }

        $cacheKey = "unread_{$id}_{$kodebpr}_{$userId}_{$periodeId}";
        $count = cache()->remember($cacheKey, self::CACHE_TTL_UNREAD, function () use ($id, $kodebpr, $userId, $periodeId) {
            return $this->commentReadsModel
                ->countUnreadCommentsForUserByFactor($id, $kodebpr, $userId, $periodeId);
        });

        return $this->response->setJSON(['unread_count' => (int) $count]);
    }

    public function markUserCommentsAsRead()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)
                ->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $id = $this->request->getPost('id');
        $kodebpr = $this->userKodebpr;
        $userId = $this->userId;
        $periodeId = session('active_periode');

        if (!$id || !$kodebpr || !$userId || !$periodeId) {
            return $this->response->setStatusCode(400)
                ->setJSON(['status' => 'error', 'message' => 'Missing data.']);
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

    public function exporttxttgjwbdekom()
    {
        if ($r = $this->requireLogin())
            return $r;

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        $periodeDetail = $this->periodeModel->find($periodeId);
        $exportDate = $periodeDetail['tahun'] ?? date('Y');
        $jenispelaporan = $periodeDetail['jenispelaporan'];

        $data_dekom = $this->tgjwbdekomModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);
        $data_penjelas = $this->penjelastindakModel
            ->where('subkategori', self::SUBKATEGORI)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->findAll();

        $sandibpr = $data_infobpr[0]['sandibpr'] ?? '';
        $kodejenis = $data_infobpr[0]['kodejenis'] ?? '';

        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}-12-31|LTBPRK|E0202|0|\r\n";

        foreach ($data_dekom as $row) {
            $nik = $this->sanitize($row['nik'] ?? '');
            $tugas = $this->sanitize($row['tugastgjwbdekom'] ?? '');
            $output .= "D01|012010000000|{$nik}|{$tugas}\r\n";
        }

        foreach ($data_penjelas as $p) {
            if (!empty($p['tindaklanjut'])) {
                $output .= 'F01|' . $this->sanitize($p['tindaklanjut']) . "\r\n";
            }
        }
        foreach ($data_penjelas as $p) {
            if (!empty($p['penjelasanlanjut'])) {
                $output .= 'F02|' . $this->sanitize($p['penjelasanlanjut']) . "\r\n";
            }
        }

        $filename = "LTBPRK-E0202-{$jenispelaporan}-A-{$exportDate}1231-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }
}