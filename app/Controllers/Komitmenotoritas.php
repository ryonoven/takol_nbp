<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_komitmenotoritas;
use App\Models\M_user;
use App\Models\M_infobpr;
use App\Models\M_periodesemesteran;
use App\Models\M_semesterancomments;
use App\Models\M_semesterancommentsread;
use Myth\Auth\Config\Services as AuthServices;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Reader\Xls as XlsReader;

class Komitmenotoritas extends Controller
{
    protected $db;
    protected $auth;
    protected $session;

    protected $komitmenModel;
    protected $userModel;
    protected $infobprModel;
    protected $periodeModel;
    protected $userKodebpr;
    protected $userId;

    protected $userInGroupPE;
    protected $userInGroupAdmin;
    protected $userInGroupDekom;
    protected $userInGroupDekom2;
    protected $userInGroupDekom3;
    protected $userInGroupDekom4;
    protected $userInGroupDekom5;
    protected $userInGroupDireksi;
    protected $userInGroupDireksi2;

    protected $komentarModel;
    protected $komentarReadModel;

    const KATEGORI = 'DIRYMF';
    const SUBKATEGORI = 'KOMITMENOTORITAS';

    private const IMPORT_MAX_ROWS = 200;
    private const IMPORT_MAX_SIZE = 3 * 1024 * 1024; // 3MB

    private const STATUS_MAP = [
        'selesai' => '01',
        'sudah dipenuhi' => '01',
        'proses' => '02',
        'dalam proses pemenuhan' => '02',
        'belum ditindaklanjuti' => '03',
        'belum dipenuhi' => '03',
    ];

    private function resolveCode(string $raw, array $map, array $validCodes): ?string
    {
        $raw = trim($raw);
        if ($raw === '')
            return null;
        if (in_array($raw, $validCodes, true))
            return $raw;
        return $map[strtolower($raw)] ?? null;
    }

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');

        $this->db = \Config\Database::connect();
        $this->session = service('session');
        $this->auth = service('authentication');

        $this->komitmenModel = new M_komitmenotoritas();
        $this->userModel = new M_user();
        $this->infobprModel = new M_infobpr();
        $this->periodeModel = new M_periodesemesteran();
        $this->komentarModel = new M_semesterancomments();
        $this->komentarReadModel = new M_semesterancommentsread();

        helper('url');

        if ($this->auth->check()) {
            $this->userId = $this->auth->id();
            $user = $this->userModel->find($this->userId);
            $this->userKodebpr = $user['kodebpr'] ?? null;

            $authorize = AuthServices::authorization();

            $this->userInGroupPE = $authorize->inGroup('pe', $this->userId);
            $this->userInGroupAdmin = $authorize->inGroup('admin', $this->userId);
            $this->userInGroupDekom = $authorize->inGroup('dekom', $this->userId);
            $this->userInGroupDekom2 = $authorize->inGroup('dekom2', $this->userId);
            $this->userInGroupDekom3 = $authorize->inGroup('dekom3', $this->userId);
            $this->userInGroupDekom4 = $authorize->inGroup('dekom4', $this->userId);
            $this->userInGroupDekom5 = $authorize->inGroup('dekom5', $this->userId);
            $this->userInGroupDireksi = $authorize->inGroup('direksi', $this->userId);
            $this->userInGroupDireksi2 = $authorize->inGroup('direksi2', $this->userId);
        }
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

    private function getKodebprPeriode(): array
    {
        return [$this->userKodebpr, (int) session('active_periode')];
    }

    private function jsonOk(string $message, array $extra = [])
    {
        return $this->response->setJSON(array_merge(['status' => 'success', 'message' => $message], $extra));
    }

    private function jsonErr(string $message, int $code = 200)
    {
        return $this->response->setStatusCode($code)->setJSON(['status' => 'error', 'message' => $message]);
    }

    public function index()
    {
        if (!$this->auth->check()) {
            return redirect()->to(session('redirect_url') ?? '/login');
        }

        if (!session('active_periode')) {
            return redirect()->to('/Periodesemesteran');
        }

        [$kodebpr, $periodeId] = $this->getKodebprPeriode();

        if (!$kodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
        }

        $user = $this->userModel->find($this->userId);
        $periodeDetail = $this->periodeModel->find($periodeId);
        $bprData = $this->infobprModel->getBprByKode($kodebpr);

        $data = [
            'judul' => 'Pelaksanaan Pemenuhan Komitmen Terhadap Otoritas',
            'userId' => $this->userId,
            'fullname' => $user['fullname'] ?? 'Unknown',
            'kodebpr' => $kodebpr,
            'periodeId' => $periodeId,
            'periodeDetail' => $periodeDetail,
            'bprData' => $bprData,
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
        echo view('Laporansemester/Dirymf/komitmenotoritas', $data);
        echo view('templates/v_footer');
    }

    public function getData()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            if (!$kodebpr || !$periodeId) {
                return $this->jsonErr('Kode BPR atau periode tidak valid');
            }

            $allRows = $this->komitmenModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);

            $nullRow = null;
            $dataRows = [];
            foreach ($allRows as $r) {
                if (!empty($r['is_null_record'])) {
                    $nullRow = $r;
                    continue;
                }
                $dataRows[] = $r;
            }

            return $this->jsonOk('OK', [
                'data' => $dataRows,
                'is_null_record' => $nullRow !== null,
            ]);

        } catch (\Exception $e) {
            log_message('error', '[Komitmenotoritas::getData] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function tambah()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            if (!$kodebpr || !$periodeId) {
                return $this->jsonErr('Kode BPR atau Periode tidak valid');
            }

            if ($this->komitmenModel->hasNullRecord($kodebpr, $periodeId)) {
                return $this->jsonErr('Batalkan status "Tidak Ada Data" terlebih dahulu sebelum menambah data');
            }

            $uraian = trim($this->request->getPost('uraian'));
            $pemberikomitmen = trim($this->request->getPost('pemberikomitmen'));
            $pelaksanaan = trim($this->request->getPost('pelaksanaan'));
            $pelaksanakomitmen = trim($this->request->getPost('pelaksanakomitmen'));
            $statuskomitmen = trim($this->request->getPost('statuskomitmen'));

            if (empty($uraian)) {
                return $this->jsonErr('Uraian Komitmen wajib diisi');
            }

            $periodeDetail = $this->periodeModel->find($periodeId);

            $data = [
                'user_id' => $this->userId,
                'kodebpr' => $kodebpr,
                'periode_id' => $periodeId,
                'tahun' => $periodeDetail['tahun'] ?? date('Y'),
                'semester' => $periodeDetail['semester'] ?? '1',
                'uraian' => $uraian,
                'pemberikomitmen' => $pemberikomitmen,
                'pelaksanaan' => $pelaksanaan,
                'pelaksanakomitmen' => $pelaksanakomitmen,
                'statuskomitmen' => $statuskomitmen,
                'accdir2' => 0,
            ];

            if ($this->komitmenModel->tambah($data)) {
                return $this->jsonOk('Data berhasil ditambahkan');
            }

            return $this->jsonErr('Gagal menambahkan data');

        } catch (\Exception $e) {
            log_message('error', '[Komitmenotoritas::tambah] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function ubah()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        try {
            $id = (int) $this->request->getPost('id');
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            if (!$id || !$kodebpr || !$periodeId) {
                return $this->jsonErr('Data tidak valid');
            }

            $existing = $this->komitmenModel
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->first();

            if (!$existing) {
                return $this->jsonErr('Data tidak ditemukan atau tidak memiliki akses');
            }

            $uraian = trim($this->request->getPost('uraian'));
            $pemberikomitmen = trim($this->request->getPost('pemberikomitmen'));
            $pelaksanaan = trim($this->request->getPost('pelaksanaan'));
            $pelaksanakomitmen = trim($this->request->getPost('pelaksanakomitmen'));
            $statuskomitmen = trim($this->request->getPost('statuskomitmen'));

            if (empty($uraian)) {
                return $this->jsonErr('Uraian Komitmen wajib diisi');
            }

            $data = [
                'uraian' => $uraian,
                'pemberikomitmen' => $pemberikomitmen,
                'pelaksanaan' => $pelaksanaan,
                'pelaksanakomitmen' => $pelaksanakomitmen,
                'statuskomitmen' => $statuskomitmen,
                'accdir2' => 0,
            ];

            if ($this->komitmenModel->ubah($data, $id, $kodebpr, $periodeId)) {
                return $this->jsonOk('Data berhasil diubah');
            }

            return $this->jsonErr('Gagal mengubah data');

        } catch (\Exception $e) {
            log_message('error', '[Komitmenotoritas::ubah] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function hapus()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        try {
            $id = (int) $this->request->getPost('id');
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            if (!$id || !$kodebpr || !$periodeId) {
                return $this->jsonErr('Data tidak valid');
            }

            $existing = $this->komitmenModel
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->first();

            if (!$existing) {
                return $this->jsonErr('Data tidak ditemukan atau tidak memiliki akses');
            }

            if ($this->komitmenModel->hapusSpesifik($id, $kodebpr, $periodeId)) {
                return $this->jsonOk('Data berhasil dihapus');
            }

            return $this->jsonErr('Gagal menghapus data');

        } catch (\Exception $e) {
            log_message('error', '[Komitmenotoritas::hapus] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function getApprovalStatus()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            if (!$kodebpr || !$periodeId) {
                return $this->jsonErr('Data tidak lengkap');
            }

            $status = $this->komitmenModel->getApprovalStatus($kodebpr, $periodeId);

            return $this->jsonOk('OK', $status);

        } catch (\Exception $e) {
            log_message('error', '[Komitmenotoritas::getApprovalStatus] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function approveKomRow()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        if (!$this->userInGroupAdmin && !$this->userInGroupDireksi && !$this->userInGroupDireksi2 && !$this->userInGroupDekom) {
            return $this->jsonErr('Tidak memiliki akses', 403);
        }

        try {
            $id = (int) $this->request->getPost('id');
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            $existing = $this->komitmenModel
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->first();

            if (!$existing)
                return $this->jsonErr('Data tidak ditemukan');

            if ($this->komitmenModel->approveKomRow($id, $kodebpr, $periodeId, $this->userId)) {
                return $this->jsonOk('Data berhasil disetujui');
            }

            return $this->jsonErr('Gagal menyimpan approval');

        } catch (\Exception $e) {
            log_message('error', '[Komitmenotoritas::approveKomRow] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function unapproveKomRow()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        if (!$this->userInGroupAdmin && !$this->userInGroupDireksi && !$this->userInGroupDireksi2 && !$this->userInGroupDekom) {
            return $this->jsonErr('Tidak memiliki akses', 403);
        }

        try {
            $id = (int) $this->request->getPost('id');
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            $existing = $this->komitmenModel
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->first();

            if (!$existing)
                return $this->jsonErr('Data tidak ditemukan');

            if ($this->komitmenModel->unapproveKomRow($id, $kodebpr, $periodeId, $this->userId)) {
                return $this->jsonOk('Approval berhasil dibatalkan');
            }

            return $this->jsonErr('Gagal membatalkan approval');

        } catch (\Exception $e) {
            log_message('error', '[Komitmenotoritas::unapproveKomRow] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function approveSemuaKom()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        if (!$this->userInGroupAdmin && !$this->userInGroupDireksi && !$this->userInGroupDireksi2 && !$this->userInGroupDekom) {
            return $this->jsonErr('Tidak memiliki akses', 403);
        }

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            if (!$kodebpr || !$periodeId)
                return $this->jsonErr('Data tidak valid');

            if ($this->komitmenModel->approveSemuaKom($kodebpr, $periodeId, $this->userId)) {
                return $this->jsonOk('Semua data berhasil disetujui');
            }

            return $this->jsonErr('Tidak ada data yang diupdate');

        } catch (\Exception $e) {
            log_message('error', '[Komitmenotoritas::approveSemuaKom] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function unapproveSemuaKom()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        if (!$this->userInGroupAdmin && !$this->userInGroupDireksi && !$this->userInGroupDireksi2 && !$this->userInGroupDekom) {
            return $this->jsonErr('Tidak memiliki akses', 403);
        }

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            if (!$kodebpr || !$periodeId)
                return $this->jsonErr('Data tidak valid');

            if ($this->komitmenModel->unapproveSemuaKom($kodebpr, $periodeId, $this->userId)) {
                return $this->jsonOk('Semua approval berhasil dibatalkan');
            }

            return $this->jsonErr('Tidak ada data yang diupdate');

        } catch (\Exception $e) {
            log_message('error', '[Komitmenotoritas::unapproveSemuaKom] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function setNodataAjax()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        if (!$this->userInGroupAdmin && !$this->userInGroupPE) {
            return $this->jsonErr('Tidak memiliki akses', 403);
        }

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();
            if (!$kodebpr || !$periodeId) {
                return $this->jsonErr('Kode BPR atau periode tidak valid');
            }

            if ($this->komitmenModel->hasRealData($kodebpr, $periodeId)) {
                return $this->jsonErr('Tidak bisa ditandai kosong — masih ada data yang tersimpan. Hapus data terlebih dahulu.');
            }

            if ($this->komitmenModel->hasNullRecord($kodebpr, $periodeId)) {
                return $this->jsonOk('Periode ini sudah ditandai tidak ada data');
            }

            $periodeDetail = $this->periodeModel->find($periodeId);

            $ok = $this->komitmenModel->insertNullRecord([
                'user_id' => $this->userId,
                'kodebpr' => $kodebpr,
                'periode_id' => $periodeId,
                'tahun' => $periodeDetail['tahun'] ?? date('Y'),
                'semester' => $periodeDetail['semester'] ?? '1',
                'uraian' => null,
                'pemberikomitmen' => null,
                'pelaksanaan' => null,
                'pelaksanakomitmen' => null,
                'statuskomitmen' => null,
                'accdir2' => 0,
                'is_null_record' => 1,
            ]);

            if (!$ok)
                return $this->jsonErr('Gagal menyimpan status');

            return $this->jsonOk('Periode berhasil ditandai tidak ada data Pelaksanaan Pemenuhan Komitmen Terhadap Otoritas');

        } catch (\Exception $e) {
            log_message('error', '[Komitmenotoritas::setNodataAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function batalNodataAjax()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        if (!$this->userInGroupPE && !$this->userInGroupAdmin) {
            return $this->jsonErr('Tidak memiliki akses', 403);
        }

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();
            if (!$kodebpr || !$periodeId) {
                return $this->jsonErr('Data tidak valid');
            }

            if ($this->komitmenModel->removeNullRecord($kodebpr, $periodeId)) {
                return $this->jsonOk('Status "Tidak Ada Data" berhasil dibatalkan');
            }
            return $this->jsonErr('Gagal membatalkan status');

        } catch (\Exception $e) {
            log_message('error', '[Komitmenotoritas::batalNodataAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function getKomentar()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(404)->setBody('Not Found');
        }

        [$kodebpr, $periodeId] = $this->getKodebprPeriode();

        $list = $this->komentarModel->getKomentarByPeriode(
            $kodebpr,
            $periodeId,
            self::KATEGORI,
            self::SUBKATEGORI
        );

        return $this->response->setJSON($list);
    }

    public function tambahKomentar()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(404)->setBody('Not Found');
        }

        $validation = $this->validate(['komentar' => 'required']);

        if (!$validation) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Komentar tidak boleh kosong']);
        }

        $teks = trim($this->request->getPost('komentar'));
        $fullname = $this->request->getPost('fullname');

        if (strlen($teks) < 3)
            return $this->jsonErr('Komentar minimal 3 karakter');
        if (strlen($teks) > 1000)
            return $this->jsonErr('Komentar maksimal 1000 karakter');

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            $result = $this->komentarModel->insertKomentar([
                'user_id' => $this->userId,
                'fullname' => $fullname,
                'kategori' => self::KATEGORI,
                'subkategori' => self::SUBKATEGORI,
                'komentar' => $teks,
                'kodebpr' => $kodebpr,
                'periode_id' => $periodeId,
            ]);

            if ($result) {
                return $this->response->setJSON(['status' => 'success', 'message' => 'Komentar berhasil ditambahkan']);
            }

            throw new \Exception('Gagal menyimpan komentar');

        } catch (\Exception $e) {
            log_message('error', '[Komitmenotoritas::tambahKomentar] ' . $e->getMessage());
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menambahkan komentar']);
        }
    }

    public function getUnreadCounts()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        [$kodebpr, $periodeId] = $this->getKodebprPeriode();

        $count = $this->komentarReadModel->countUnreadComments(
            $kodebpr,
            $this->userId,
            $periodeId,
            self::KATEGORI,
            self::SUBKATEGORI
        );

        return $this->response->setJSON(['status' => 'success', 'count' => (int) $count]);
    }

    public function markKomentarAsRead()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        [$kodebpr, $periodeId] = $this->getKodebprPeriode();

        $marked = $this->komentarReadModel->markAllAsReadForPeriode(
            $kodebpr,
            $this->userId,
            $periodeId,
            self::KATEGORI,
            self::SUBKATEGORI
        );

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Comments marked as read',
            'marked_count' => $marked
        ]);
    }

    public function checkPrevPeriode()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            if (!$kodebpr || !$periodeId) {
                return $this->jsonErr('Data tidak valid');
            }

            $current = $this->periodeModel->find($periodeId);
            if (!$current) {
                return $this->jsonErr('Periode aktif tidak ditemukan');
            }

            $tahun = (int) $current['tahun'];
            $semRaw = strtolower(trim($current['semester']));
            $semNum = ($semRaw === 'ganjil' || $semRaw === '1') ? 1 : 2;

            if ($semNum === 1) {
                $prevSemNum = 2;
                $prevTahun = $tahun - 1;
            } else {
                $prevSemNum = 1;
                $prevTahun = $tahun;
            }

            $prevSemVariants = $prevSemNum === 1
                ? ['1', 'ganjil', 'Ganjil', 'GANJIL']
                : ['2', 'genap', 'Genap', 'GENAP'];

            $prevPeriode = $this->periodeModel
                ->where('kodebpr', $kodebpr)
                ->where('tahun', $prevTahun)
                ->whereIn('semester', $prevSemVariants)
                ->first();

            if (!$prevPeriode) {
                return $this->response->setJSON([
                    'status' => 'not_found',
                    'message' => 'Tidak ada periode sebelumnya yang ditemukan',
                ]);
            }

            $prevRows = $this->komitmenModel
                ->getDataByKodebprAndPeriode($kodebpr, (int) $prevPeriode['id']);

            if (empty($prevRows)) {
                return $this->response->setJSON([
                    'status' => 'not_found',
                    'message' => 'Periode sebelumnya ada tetapi belum ada data Pelaksanaan Pemenuhan Komitmen Terhadap Otoritas',
                ]);
            }

            // Composite key "uraian||pemberikomitmen" yang sudah ada
            $existingKeys = $this->komitmenModel
                ->getUraianPemberiByPeriode($kodebpr, $periodeId);

            $semLabel = fn(int $n): string =>
                $n === 1 ? 'Semester 1 (Ganjil)' : 'Semester 2 (Genap)';

            return $this->response->setJSON([
                'status' => 'found',
                'current_label' => $semLabel($semNum) . ' ' . $tahun,
                'prev_label' => $semLabel($prevSemNum) . ' ' . $prevTahun,
                'prev_periode_id' => (int) $prevPeriode['id'],
                'rows' => $prevRows,
                'existing_keys' => $existingKeys,
            ]);

        } catch (\Exception $e) {
            log_message('error', '[Komitmenotoritas::checkPrevPeriode] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function syncFromPrevPeriode()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        if (!$this->userInGroupPE && !$this->userInGroupAdmin) {
            return $this->jsonErr('Tidak memiliki akses untuk sinkronisasi data', 403);
        }

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            if ($this->komitmenModel->hasNullRecord($kodebpr, $periodeId)) {
                return $this->jsonErr('Batalkan status "Tidak Ada Data" terlebih dahulu sebelum sinkronisasi');
            }

            if (!$kodebpr || !$periodeId) {
                return $this->jsonErr('Data tidak valid');
            }

            $current = $this->periodeModel->find($periodeId);
            if (!$current) {
                return $this->jsonErr('Periode aktif tidak ditemukan');
            }

            $tahun = (int) $current['tahun'];
            $semRaw = strtolower(trim($current['semester']));
            $semNum = ($semRaw === 'ganjil' || $semRaw === '1') ? 1 : 2;

            if ($semNum === 1) {
                $prevSemNum = 2;
                $prevTahun = $tahun - 1;
            } else {
                $prevSemNum = 1;
                $prevTahun = $tahun;
            }

            $prevSemVariants = $prevSemNum === 1
                ? ['1', 'ganjil', 'Ganjil', 'GANJIL']
                : ['2', 'genap', 'Genap', 'GENAP'];

            $prevPeriode = $this->periodeModel
                ->where('kodebpr', $kodebpr)
                ->where('tahun', $prevTahun)
                ->whereIn('semester', $prevSemVariants)
                ->first();

            if (!$prevPeriode) {
                return $this->jsonErr('Periode sebelumnya tidak ditemukan');
            }

            $prevRows = $this->komitmenModel
                ->getDataByKodebprAndPeriode($kodebpr, (int) $prevPeriode['id']);

            if (empty($prevRows)) {
                return $this->jsonErr('Tidak ada data Pelaksanaan Pemenuhan Komitmen Terhadap Otoritas pada periode sebelumnya');
            }

            // Composite key set — O(1) lookup
            $existingKeys = $this->komitmenModel->getUraianPemberiByPeriode($kodebpr, $periodeId);
            $existingKeySet = array_flip($existingKeys);

            $user = $this->userModel->find($this->userId);
            $insertBatch = [];

            foreach ($prevRows as $row) {
                $compositeKey = $row['uraian'] . '||' . $row['pemberikomitmen'];

                if (isset($existingKeySet[$compositeKey])) {
                    continue;
                }

                $insertBatch[] = [
                    'user_id' => $this->userId,
                    'kodebpr' => $kodebpr,
                    'periode_id' => $periodeId,
                    'tahun' => $current['tahun'],
                    'semester' => $current['semester'],
                    'uraian' => $row['uraian'],
                    'pemberikomitmen' => $row['pemberikomitmen'],
                    'pelaksanaan' => $row['pelaksanaan'],
                    'pelaksanakomitmen' => $row['pelaksanakomitmen'],
                    'statuskomitmen' => $row['statuskomitmen'],
                    'accdir2' => 0,
                    'accdir2_by' => 0,
                    'accdir2_at' => '0000-00-00 00:00:00',
                ];
            }

            if (empty($insertBatch)) {
                return $this->jsonErr(
                    'Semua data dari periode sebelumnya sudah ada di periode aktif. Tidak ada data baru.'
                );
            }

            $this->db->transStart();
            $this->komitmenModel->insertBatchSync($insertBatch);
            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return $this->jsonErr('Transaksi gagal, silakan coba kembali');
            }

            return $this->jsonOk(
                count($insertBatch) . ' data berhasil disinkronisasi dari periode sebelumnya'
            );

        } catch (\Exception $e) {
            log_message('error', '[Komitmenotoritas::syncFromPrevPeriode] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function exporttxtkomitmenotoritas()
    {
        if (!$this->auth->check()) {
            return redirect()->to(session('redirect_url') ?? '/login');
        }

        [$kodebpr, $periodeId] = $this->getKodebprPeriode();
        $periodeDetail = $this->periodeModel->find($periodeId);
        $dataRows = $this->komitmenModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        $dataInfobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        $modalinti = $periodeDetail['modalinti'];
        $kategori = $periodeDetail['kategori'];
        $nomorsurat = $periodeDetail['nomorsurat'];
        $jeniskoreksi = $periodeDetail['jeniskoreksi'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];

        $sandibpr = '';
        $kodejenis = '';
        if (!empty($dataInfobpr)) {
            $sandibpr = $dataInfobpr[0]['sandibpr'] ?? '';
            $kodejenis = $dataInfobpr[0]['kodejenis'] ?? '';
        }

        $tahun = $periodeDetail['tahun'] ?? date('Y');
        $semester = $periodeDetail['semester'] ?? '';

        if (strtolower($semester) === 'ganjil' || $semester === '1') {
            $tanggalLaporan = "{$tahun}-06-30";
        } else {
            $tanggalLaporan = "{$tahun}-12-31";
        }

        $sanitize = function ($text) {
            $text = str_replace(["\r", "\n"], ' ', $text);
            $text = str_replace(['|', '%'], [' ', ' persen '], $text);
            $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
            return trim(preg_replace('/\s+/', ' ', $text));
        };

        $output = "H01|{$kodejenis}|{$sandibpr}|{$tanggalLaporan}|TKSBPRKS|2005|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";

        foreach ($dataRows as $row) {
            $pemberikomitmen = $sanitize((string) ($row['pemberikomitmen'] ?? ''));
            $pelaksanaan = $sanitize((string) ($row['pelaksanaan'] ?? ''));
            $pelaksanakomitmen = $sanitize((string) ($row['pelaksanakomitmen'] ?? ''));

            if ($pemberikomitmen === '' && $pelaksanaan === '' && $pelaksanakomitmen === '') {
                continue;
            }

            $uraian = $sanitize((string) ($row['uraian'] ?? ''));
            $statuskomitmen = $sanitize((string) ($row['statuskomitmen'] ?? ''));

            $output .= "D01|K1800|{$uraian}|{$pemberikomitmen}|{$pelaksanaan}|{$pelaksanakomitmen}|{$statuskomitmen}\r\n";
        }

        $tanggalFile = str_replace('-', '', $tanggalLaporan);
        $filename = "TKSBPRKS-2005-{$jenispelaporan}-S-{$tanggalFile}-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }

    public function previewImportExcel()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        if (!$this->userInGroupPE && !$this->userInGroupAdmin) {
            return $this->jsonErr('Tidak memiliki akses', 403);
        }

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();
            if (!$kodebpr || !$periodeId) {
                return $this->jsonErr('Kode BPR atau periode tidak valid');
            }

            if ($this->komitmenModel->hasNullRecord($kodebpr, $periodeId)) {
                return $this->jsonErr('Batalkan status "Tidak Ada Data" terlebih dahulu sebelum impor');
            }

            $file = $this->request->getFile('file');
            if (!$file || !$file->isValid() || $file->hasMoved()) {
                return $this->jsonErr('File tidak valid atau gagal diupload');
            }

            $ext = strtolower($file->getClientExtension());
            if (!in_array($ext, ['xlsx', 'xls'], true)) {
                return $this->jsonErr('Format file harus .xlsx atau .xls');
            }

            if ($file->getSize() > self::IMPORT_MAX_SIZE) {
                return $this->jsonErr('Ukuran file maksimal 3MB');
            }

            $reader = $ext === 'xlsx' ? new XlsxReader() : new XlsReader();
            $reader->setReadDataOnly(true);

            $spreadsheet = $reader->load($file->getTempName());
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestDataRow();

            if (($highestRow - 1) > self::IMPORT_MAX_ROWS) {
                unset($spreadsheet, $sheet);
                return $this->jsonErr('Maksimal ' . self::IMPORT_MAX_ROWS . ' baris per impor. File Anda memiliki ' . ($highestRow - 1) . ' baris.');
            }

            $existingKeys = $this->komitmenModel->getUraianPemberiByPeriode($kodebpr, $periodeId);
            $existingKeySet = array_flip($existingKeys);
            $seenInFile = [];

            $preview = [];

            for ($rowIdx = 2; $rowIdx <= $highestRow; $rowIdx++) {
                $uraian = trim((string) ($sheet->getCell('A' . $rowIdx)->getValue() ?? ''));
                $pemberikomitmen = trim((string) ($sheet->getCell('B' . $rowIdx)->getValue() ?? ''));
                $pelaksanaan = trim((string) ($sheet->getCell('C' . $rowIdx)->getValue() ?? ''));
                $pelaksanakomitmen = trim((string) ($sheet->getCell('D' . $rowIdx)->getValue() ?? ''));
                $statusRaw = trim((string) ($sheet->getCell('E' . $rowIdx)->getValue() ?? ''));

                if ($uraian === '' && $pemberikomitmen === '' && $pelaksanakomitmen === '') {
                    continue; // baris kosong, skip diam-diam
                }

                $rowErr = [];
                if ($uraian === '')
                    $rowErr[] = 'Uraian Komitmen kosong';

                $statusCode = $statusRaw === '' ? null : $this->resolveCode($statusRaw, self::STATUS_MAP, ['01', '02', '03']);
                if ($statusRaw !== '' && $statusCode === null)
                    $rowErr[] = 'Status "' . $statusRaw . '" tidak dikenali';

                $compositeKey = $uraian . '||' . $pemberikomitmen;
                $isDupDb = isset($existingKeySet[$compositeKey]);
                $isDupFile = isset($seenInFile[$compositeKey]);
                if ($uraian !== '')
                    $seenInFile[$compositeKey] = true;

                $status = 'ok';
                if (!empty($rowErr))
                    $status = 'error';
                elseif ($isDupDb || $isDupFile)
                    $status = 'skip';

                $preview[] = [
                    'line' => $rowIdx,
                    'uraian' => $uraian,
                    'pemberikomitmen' => $pemberikomitmen,
                    'pelaksanaan' => $pelaksanaan,
                    'pelaksanakomitmen' => $pelaksanakomitmen,
                    'statuskomitmen' => $statusCode,
                    'status_label' => $statusRaw,
                    'row_status' => $status,
                    'errors' => $rowErr,
                    'dup_reason' => $isDupDb ? 'Uraian+Pemberi Komitmen sudah ada di periode ini' : ($isDupFile ? 'Duplikat dalam file' : null),
                ];
            }

            unset($sheet, $spreadsheet, $reader);

            if (empty($preview)) {
                return $this->jsonErr('Tidak ada baris data yang terbaca dari file');
            }

            $okCount = count(array_filter($preview, static fn($p) => $p['row_status'] === 'ok'));

            return $this->jsonOk('OK', [
                'preview' => $preview,
                'ok_count' => $okCount,
                'total' => count($preview),
            ]);

        } catch (\Exception $e) {
            log_message('error', '[Komitmenotoritas::previewImportExcel] ' . $e->getMessage());
            return $this->jsonErr('Gagal membaca file Excel. Pastikan format sesuai template.', 500);
        }
    }

    public function confirmImportExcel()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        if (!$this->userInGroupPE && !$this->userInGroupAdmin) {
            return $this->jsonErr('Tidak memiliki akses', 403);
        }

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();
            if (!$kodebpr || !$periodeId) {
                return $this->jsonErr('Data tidak valid');
            }

            if ($this->komitmenModel->hasNullRecord($kodebpr, $periodeId)) {
                return $this->jsonErr('Batalkan status "Tidak Ada Data" terlebih dahulu sebelum impor');
            }

            $rows = json_decode($this->request->getPost('rows') ?? '[]', true);
            if (!is_array($rows) || empty($rows)) {
                return $this->jsonErr('Tidak ada data valid untuk disimpan');
            }
            if (count($rows) > self::IMPORT_MAX_ROWS) {
                return $this->jsonErr('Jumlah baris melebihi batas maksimal');
            }

            $existingKeys = $this->komitmenModel->getUraianPemberiByPeriode($kodebpr, $periodeId);
            $existingKeySet = array_flip($existingKeys);

            $periodeDetail = $this->periodeModel->find($periodeId);

            $insertBatch = [];
            $seenInFile = [];

            foreach ($rows as $r) {
                $uraian = trim((string) ($r['uraian'] ?? ''));
                $pemberi = trim((string) ($r['pemberikomitmen'] ?? ''));

                if ($uraian === '')
                    continue;

                $compositeKey = $uraian . '||' . $pemberi;
                if (isset($existingKeySet[$compositeKey]) || isset($seenInFile[$compositeKey]))
                    continue;
                $seenInFile[$compositeKey] = true;

                $insertBatch[] = [
                    'user_id' => $this->userId,
                    'kodebpr' => $kodebpr,
                    'periode_id' => $periodeId,
                    'tahun' => $periodeDetail['tahun'] ?? date('Y'),
                    'semester' => $periodeDetail['semester'] ?? '1',
                    'uraian' => $uraian,
                    'pemberikomitmen' => $pemberi,
                    'pelaksanaan' => trim((string) ($r['pelaksanaan'] ?? '')),
                    'pelaksanakomitmen' => trim((string) ($r['pelaksanakomitmen'] ?? '')),
                    'statuskomitmen' => $r['statuskomitmen'] ?? null,
                    'accdir2' => 0,
                ];
            }

            if (empty($insertBatch)) {
                return $this->jsonErr('Tidak ada data baru yang dapat disimpan (semua duplikat/kosong)');
            }

            $this->db->transStart();
            $this->komitmenModel->insertBatchSync($insertBatch);
            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return $this->jsonErr('Transaksi gagal, silakan coba kembali');
            }

            return $this->jsonOk(count($insertBatch) . ' data berhasil diimpor dari Excel');

        } catch (\Exception $e) {
            log_message('error', '[Komitmenotoritas::confirmImportExcel] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }
}