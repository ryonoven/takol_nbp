<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_kesimpulanaudit;
use App\Models\M_user;
use App\Models\M_infobpr;
use App\Models\M_periodesemesteran;
use App\Models\M_semesterancomments;
use App\Models\M_semesterancommentsread;
use Myth\Auth\Config\Services as AuthServices;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Reader\Xls as XlsReader;

class Kesimpulanaudit extends Controller
{
    protected $db;
    protected $auth;
    protected $session;

    protected $kesimpulanauditModel;
    protected $userModel;
    protected $infobprModel;
    protected $periodeModel;

    protected $userKodebpr;
    protected $userId;

    protected $userInGroupPE;
    protected $userInGroupAdmin;
    protected $userInGroupDekom;
    protected $userInGroupDekom2;
    protected $userInGroupDireksi;
    protected $userInGroupDireksi2;
    protected $userInGroupAuditor;

    protected $komentarModel;
    protected $komentarReadModel;

    const KATEGORI = 'DIRYMF';
    const SUBKATEGORI = 'KESIMPULANAUDIT';
    private const IMPORT_MAX_ROWS = 200;
    private const IMPORT_MAX_SIZE = 3 * 1024 * 1024; // 3MB

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');

        $this->db = \Config\Database::connect();
        $this->session = service('session');
        $this->auth = service('authentication');
        $this->kesimpulanauditModel = new M_kesimpulanaudit();
        $this->userModel = new M_user();
        $this->infobprModel = new M_infobpr();
        $this->periodeModel = new M_periodesemesteran();
        $this->komentarModel = new M_semesterancomments();
        $this->komentarReadModel = new M_semesterancommentsread();

        helper('url');

        if ($this->auth->check()) {
            $this->userId = $this->auth->id();
            $user = $this->userModel->select('kodebpr, fullname')->find($this->userId);
            $this->userKodebpr = $user['kodebpr'] ?? null;

            $authorize = AuthServices::authorization();
            $this->userInGroupPE = $authorize->inGroup('pe', $this->userId);
            $this->userInGroupAdmin = $authorize->inGroup('admin', $this->userId);
            $this->userInGroupDekom = $authorize->inGroup('dekom', $this->userId);
            $this->userInGroupDekom2 = $authorize->inGroup('dekom2', $this->userId);
            $this->userInGroupDireksi = $authorize->inGroup('direksi', $this->userId);
            $this->userInGroupDireksi2 = $authorize->inGroup('direksi2', $this->userId);
            $this->userInGroupAuditor = $authorize->inGroup('auditor', $this->userId);
        }
    }

    private function requireAjax()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)
                ->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }
        return null;
    }

    private function requireAuth()
    {
        if (!$this->auth->check()) {
            return $this->response->setStatusCode(401)
                ->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }
        return null;
    }

    private function getKodebprPeriode(): array
    {
        return [$this->userKodebpr, (int) session('active_periode')];
    }

    private function jsonOk(string $message, array $extra = [])
    {
        return $this->response->setJSON(
            array_merge(['status' => 'success', 'message' => $message], $extra)
        );
    }

    private function jsonErr(string $message, int $code = 200)
    {
        return $this->response->setStatusCode($code)
            ->setJSON(['status' => 'error', 'message' => $message]);
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

        $user = $this->userModel->select('fullname')->find($this->userId);
        $periodeDetail = $this->periodeModel->select('semester, tahun')->find($periodeId);
        $bprData = $this->infobprModel->getBprByKode($kodebpr);

        $data = [
            'judul' => 'Kesimpulan Audit',
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
            'userInGroupDireksi' => $this->userInGroupDireksi,
            'userInGroupDireksi2' => $this->userInGroupDireksi2,
            'userInGroupAuditor' => $this->userInGroupAuditor
        ];

        return view('templates/v_header', $data)
            . view('templates/v_sidebar')
            . view('templates/v_topbar')
            . view('Laporansemester/Laporanaudit/kesimpulanaudit', $data)
            . view('templates/v_footer');
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

            $allRows = $this->kesimpulanauditModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);

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
            log_message('error', '[Kesimpulanaudit::getData] ' . $e->getMessage());
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

            $kesimpulan = trim($this->request->getPost('kesimpulan') ?? '');
            $pernyataan = trim($this->request->getPost('pernyataan') ?? '');

            if ($kesimpulan === '' || $pernyataan === '') {
                return $this->jsonErr('Kesimpulan Audit dan Pernyataan Auditor wajib diisi');
            }

            $periodeDetail = $this->periodeModel->select('tahun, semester')->find($periodeId);
            $user = $this->userModel->select('fullname')->find($this->userId);
            if ($this->kesimpulanauditModel->hasNullRecord($kodebpr, $periodeId)) {
                return $this->jsonErr('Batalkan status "Tidak Ada Data" terlebih dahulu sebelum menambah data');
            }

            $data = [
                'user_id' => $this->userId,
                'kodebpr' => $kodebpr,
                'periode_id' => $periodeId,
                'tahun' => $periodeDetail['tahun'] ?? date('Y'),
                'semester' => $periodeDetail['semester'] ?? '1',
                'kesimpulan' => $kesimpulan,
                'pernyataan' => $pernyataan,
                'fullname' => $user['fullname'] ?? null,
                'accdir2' => 0,
            ];

            if ($this->kesimpulanauditModel->tambah($data)) {
                return $this->jsonOk('Data berhasil ditambahkan');
            }

            return $this->jsonErr('Gagal menambahkan data');

        } catch (\Exception $e) {
            log_message('error', '[Kesimpulanaudit::tambah] ' . $e->getMessage());
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

            $existing = $this->kesimpulanauditModel
                ->select('id')
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->first();

            if (!$existing) {
                return $this->jsonErr('Data tidak ditemukan atau tidak memiliki akses');
            }

            $kesimpulan = trim($this->request->getPost('kesimpulan') ?? '');
            $pernyataan = trim($this->request->getPost('pernyataan') ?? '');

            if ($kesimpulan === '' || $pernyataan === '') {
                return $this->jsonErr('Kesimpulan Audit dan Pernyataan Auditor wajib diisi');
            }

            if (
                $this->kesimpulanauditModel->ubah(
                    ['kesimpulan' => $kesimpulan, 'pernyataan' => $pernyataan, 'accdir2' => 0],
                    $id,
                    $kodebpr,
                    $periodeId
                )
            ) {
                return $this->jsonOk('Data berhasil diubah');
            }

            return $this->jsonErr('Gagal mengubah data');

        } catch (\Exception $e) {
            log_message('error', '[Kesimpulanaudit::ubah] ' . $e->getMessage());
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

            $existing = $this->kesimpulanauditModel
                ->select('id')
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->first();

            if (!$existing) {
                return $this->jsonErr('Data tidak ditemukan atau tidak memiliki akses');
            }

            if ($this->kesimpulanauditModel->hapusSpesifik($id, $kodebpr, $periodeId)) {
                return $this->jsonOk('Data berhasil dihapus');
            }

            return $this->jsonErr('Gagal menghapus data');

        } catch (\Exception $e) {
            log_message('error', '[Kesimpulanaudit::hapus] ' . $e->getMessage());
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

            $keterangan = $this->kesimpulanauditModel->getApprovalStatus($kodebpr, $periodeId);

            return $this->jsonOk('OK', $keterangan);

        } catch (\Exception $e) {
            log_message('error', '[Kesimpulanaudit::getApprovalStatus] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function approveKomRow()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        if (!$this->userInGroupAdmin && !$this->userInGroupDireksi) {
            return $this->jsonErr('Tidak memiliki akses', 403);
        }

        try {
            $id = (int) $this->request->getPost('id');
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            $existing = $this->kesimpulanauditModel
                ->select('id')
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->first();

            if (!$existing)
                return $this->jsonErr('Data tidak ditemukan');

            if ($this->kesimpulanauditModel->approveKomRow($id, $kodebpr, $periodeId, $this->userId)) {
                return $this->jsonOk('Data berhasil disetujui (Direktur Utama)');
            }

            return $this->jsonErr('Gagal menyimpan approval');

        } catch (\Exception $e) {
            log_message('error', '[Kesimpulanaudit::approveKomRow] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function unapproveKomRow()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        if (!$this->userInGroupAdmin && !$this->userInGroupDireksi) {
            return $this->jsonErr('Tidak memiliki akses', 403);
        }

        try {
            $id = (int) $this->request->getPost('id');
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            $existing = $this->kesimpulanauditModel
                ->select('id')
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->first();

            if (!$existing)
                return $this->jsonErr('Data tidak ditemukan');

            if ($this->kesimpulanauditModel->unapproveKomRow($id, $kodebpr, $periodeId, $this->userId)) {
                return $this->jsonOk('Approval Direktur Utama');
            }

            return $this->jsonErr('Gagal membatalkan approval');

        } catch (\Exception $e) {
            log_message('error', '[Kesimpulanaudit::unapproveKomRow] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function approveSemuaKom()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        if (!$this->userInGroupAdmin && !$this->userInGroupDireksi) {
            return $this->jsonErr('Tidak memiliki akses', 403);
        }

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            if (!$kodebpr || !$periodeId)
                return $this->jsonErr('Data tidak valid');

            if ($this->kesimpulanauditModel->approveSemuaKom($kodebpr, $periodeId, $this->userId)) {
                return $this->jsonOk('Semua data berhasil disetujui oleh Direktur Utama');
            }

            return $this->jsonErr('Tidak ada data yang diupdate');

        } catch (\Exception $e) {
            log_message('error', '[Kesimpulanaudit::approveSemuaKom] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function unapproveSemuaKom()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        if (!$this->userInGroupAdmin && !$this->userInGroupDireksi) {
            return $this->jsonErr('Tidak memiliki akses', 403);
        }

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            if (!$kodebpr || !$periodeId)
                return $this->jsonErr('Data tidak valid');

            if ($this->kesimpulanauditModel->unapproveSemuaKom($kodebpr, $periodeId, $this->userId)) {
                return $this->jsonOk('Semua approval Direktur Utama');
            }

            return $this->jsonErr('Tidak ada data yang diupdate');

        } catch (\Exception $e) {
            log_message('error', '[Kesimpulanaudit::unapproveSemuaKom] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function setNodataAjax()
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

            if ($this->kesimpulanauditModel->hasRealData($kodebpr, $periodeId)) {
                return $this->jsonErr('Tidak bisa ditandai kosong — masih ada data yang tersimpan. Hapus data terlebih dahulu.');
            }

            if ($this->kesimpulanauditModel->hasNullRecord($kodebpr, $periodeId)) {
                return $this->jsonOk('Periode ini sudah ditandai tidak ada data');
            }

            $periodeDetail = $this->periodeModel->find($periodeId);
            $user = $this->userModel->find($this->userId);

            $ok = $this->kesimpulanauditModel->insertNullRecord([
                'user_id' => $this->userId,
                'kodebpr' => $kodebpr,
                'periode_id' => $periodeId,
                'tahun' => $periodeDetail['tahun'] ?? date('Y'),
                'semester' => $periodeDetail['semester'] ?? '1',
                'kesimpulan' => null,
                'pernyataan' => null,
                'fullname' => $user['fullname'] ?? null,
                'accdir2' => 0,
                'is_null_record' => 1,
            ]);

            if (!$ok)
                return $this->jsonErr('Gagal menyimpan status');

            return $this->jsonOk('Periode berhasil ditandai tidak ada data Kesimpulan Audit');

        } catch (\Exception $e) {
            log_message('error', '[Kesimpulanaudit::setNodataAjax] ' . $e->getMessage());
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

            if ($this->kesimpulanauditModel->removeNullRecord($kodebpr, $periodeId)) {
                return $this->jsonOk('Status "Tidak Ada Data" berhasil dibatalkan');
            }
            return $this->jsonErr('Gagal membatalkan status');

        } catch (\Exception $e) {
            log_message('error', '[Kesimpulanaudit::batalNodataAjax] ' . $e->getMessage());
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

        $teks = trim($this->request->getPost('komentar') ?? '');

        if ($teks === '') {
            return $this->jsonErr('Komentar tidak boleh kosong');
        }
        if (strlen($teks) < 3)
            return $this->jsonErr('Komentar minimal 3 karakter');
        if (strlen($teks) > 1000)
            return $this->jsonErr('Komentar maksimal 1000 karakter');

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            $result = $this->komentarModel->insertKomentar([
                'user_id' => $this->userId,
                'fullname' => $this->request->getPost('fullname'),
                'kategori' => self::KATEGORI,
                'subkategori' => self::SUBKATEGORI,
                'komentar' => $teks,
                'kodebpr' => $kodebpr,
                'periode_id' => $periodeId,
            ]);

            if ($result) {
                return $this->jsonOk('Komentar berhasil ditambahkan');
            }

            throw new \Exception('Gagal menyimpan komentar');

        } catch (\Exception $e) {
            log_message('error', '[Kesimpulanaudit::tambahKomentar] ' . $e->getMessage());
            return $this->jsonErr('Gagal menambahkan komentar');
        }
    }

    public function getUnreadCounts()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)
                ->setJSON(['status' => 'error', 'message' => 'Forbidden']);
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
            return $this->response->setStatusCode(403)
                ->setJSON(['status' => 'error', 'message' => 'Forbidden']);
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
            'marked_count' => $marked,
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

            $current = $this->periodeModel->select('tahun, semester')->find($periodeId);
            if (empty($current)) {
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
                ->where('tahun', $prevTahun)
                ->whereIn('semester', $prevSemVariants)
                ->first();

            if (!$prevPeriode) {
                return $this->response->setJSON([
                    'status' => 'not_found',
                    'message' => 'Tidak ada periode sebelumnya yang ditemukan',
                ]);
            }

            $prevRows = $this->kesimpulanauditModel
                ->getDataByKodebprAndPeriode($kodebpr, (int) $prevPeriode['id']);

            if (empty($prevRows)) {
                return $this->response->setJSON([
                    'status' => 'not_found',
                    'message' => 'Periode sebelumnya ada tetapi belum ada data Kesimpulan Audit',
                ]);
            }

            $existingKeys = $this->kesimpulanauditModel
                ->getKesimpulanKeysByPeriode($kodebpr, $periodeId);

            $semLabel = function (int $n): string {
                return $n === 1 ? 'Semester 1 (Ganjil)' : 'Semester 2 (Genap)';
            };

            return $this->response->setJSON([
                'status' => 'found',
                'current_label' => $semLabel($semNum) . ' ' . $tahun,
                'prev_label' => $semLabel($prevSemNum) . ' ' . $prevTahun,
                'prev_periode_id' => (int) $prevPeriode['id'],
                'rows' => $prevRows,
                'existing_keys' => $existingKeys,
            ]);

        } catch (\Exception $e) {
            log_message('error', '[Kesimpulanaudit::checkPrevPeriode] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function syncFromPrevPeriode()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        if (!$this->userInGroupPE && !$this->userInGroupAdmin && $this->userInGroupAuditor) {
            return $this->jsonErr('Tidak memiliki akses untuk sinkronisasi data', 403);
        }

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            if (!$kodebpr || !$periodeId) {
                return $this->jsonErr('Data tidak valid');
            }

            $current = $this->periodeModel->select('tahun, semester')->find($periodeId);
            if (empty($current)) {
                return $this->jsonErr('Periode aktif tidak ditemukan');
            }

            if ($this->kesimpulanauditModel->hasNullRecord($kodebpr, $periodeId)) {
                return $this->jsonErr('Batalkan status "Tidak Ada Data" terlebih dahulu sebelum sinkronisasi');
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
                ->where('tahun', $prevTahun)
                ->whereIn('semester', $prevSemVariants)
                ->first();

            if (!$prevPeriode) {
                return $this->jsonErr('Periode sebelumnya tidak ditemukan');
            }

            $prevRows = $this->kesimpulanauditModel
                ->getDataByKodebprAndPeriode($kodebpr, (int) $prevPeriode['id']);

            if (empty($prevRows)) {
                return $this->jsonErr('Tidak ada data Kesimpulan Audit pada periode sebelumnya');
            }

            $existingKeys = $this->kesimpulanauditModel->getKesimpulanKeysByPeriode($kodebpr, $periodeId);
            $existingKeySet = array_flip($existingKeys);

            $user = $this->userModel->select('fullname')->find($this->userId);
            $insertBatch = [];

            foreach ($prevRows as $row) {
                $compositeKey = mb_substr($row['kesimpulan'] ?? '', 0, 100)
                    . '||'
                    . mb_substr($row['pernyataan'] ?? '', 0, 100);

                if (isset($existingKeySet[$compositeKey])) {
                    continue;
                }

                $insertBatch[] = [
                    'user_id' => $this->userId,
                    'kodebpr' => $kodebpr,
                    'periode_id' => $periodeId,
                    'tahun' => $current['tahun'],
                    'semester' => $current['semester'],
                    'kesimpulan' => $row['kesimpulan'],
                    'pernyataan' => $row['pernyataan'],
                    'fullname' => $user['fullname'] ?? null,
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
            $this->kesimpulanauditModel->insertBatchSync($insertBatch);
            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return $this->jsonErr('Transaksi gagal, silakan coba kembali');
            }

            return $this->jsonOk(
                count($insertBatch) . ' data berhasil disinkronisasi dari periode sebelumnya'
            );

        } catch (\Exception $e) {
            log_message('error', '[Kesimpulanaudit::syncFromPrevPeriode] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function exporttxtkesimpulanaudit()
    {
        if (!$this->auth->check()) {
            return redirect()->to(session('redirect_url') ?? '/login');
        }

        [$kodebpr, $periodeId] = $this->getKodebprPeriode();

        $periodeDetail = $this->periodeModel
            ->select('tahun, semester, modalinti, kategori, jenispelaporan, nomorsurat, jeniskoreksi')
            ->find($periodeId);

        $dataRows = $this->kesimpulanauditModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        $dataInfobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        $sandibpr = $dataInfobpr[0]['sandibpr'] ?? '';
        $kodejenis = $dataInfobpr[0]['kodejenis'] ?? '';
        $tahun = $periodeDetail['tahun'] ?? date('Y');
        $semester = $periodeDetail['semester'] ?? '';

        $modalinti = $periodeDetail['modalinti'];
        $kategori = $periodeDetail['kategori'];
        $nomorsurat = $periodeDetail['nomorsurat'];
        $jeniskoreksi = $periodeDetail['jeniskoreksi'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];

        $tanggalLaporan = (strtolower($semester) === 'ganjil' || $semester === '1')
            ? "{$tahun}-06-30"
            : "{$tahun}-12-31";

        static $re = null;
        if ($re === null)
            $re = '/[^\x20-\x7E]/';
        $sanitize = static function (string $text) use ($re): string {
            $text = str_replace(["\r\n", "\r", "\n", '|', '%'], [' ', ' ', ' ', ' ', ' persen '], $text);
            $text = preg_replace($re, ' ', $text);
            return trim(preg_replace('/\s+/', ' ', $text));
        };

        $output = "H01|{$kodejenis}|{$sandibpr}|{$tanggalLaporan}|TKSBPRKS|3002|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";

        foreach ($dataRows as $row) {
            $k = $sanitize((string) ($row['kesimpulan'] ?? ''));
            $p = $sanitize((string) ($row['pernyataan'] ?? ''));
            if ($k === '' && $p === '')
                continue;
            $output .= "D01|A2100|{$k}|{$p}\r\n";
        }

        $tanggalFile = str_replace('-', '', $tanggalLaporan);
        $filename = "TKSBPRKS-3002-{$jenispelaporan}-S-{$tanggalFile}-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Cache-Control', 'no-store')
            ->setBody($output);
    }

    public function previewImportExcel()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        if (!$this->userInGroupPE && !$this->userInGroupAdmin && !$this->userInGroupAuditor) {
            return $this->jsonErr('Tidak memiliki akses', 403);
        }

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();
            if (!$kodebpr || !$periodeId) {
                return $this->jsonErr('Kode BPR atau periode tidak valid');
            }

            if ($this->kesimpulanauditModel->hasNullRecord($kodebpr, $periodeId)) {
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

            $preview = [];

            for ($rowIdx = 2; $rowIdx <= $highestRow; $rowIdx++) {
                $kesimpulan = trim((string) ($sheet->getCell('A' . $rowIdx)->getValue() ?? ''));
                $pernyataan = trim((string) ($sheet->getCell('B' . $rowIdx)->getValue() ?? ''));

                if ($kesimpulan === '' && $pernyataan === '') {
                    continue; // baris kosong, skip diam-diam
                }

                $rowErr = [];
                if ($kesimpulan === '')
                    $rowErr[] = 'Kesimpulan Audit kosong';
                if ($pernyataan === '')
                    $rowErr[] = 'Pernyataan Auditor kosong';

                $preview[] = [
                    'line' => $rowIdx,
                    'kesimpulan' => $kesimpulan,
                    'pernyataan' => $pernyataan,
                    'row_status' => empty($rowErr) ? 'ok' : 'error',
                    'errors' => $rowErr,
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
            log_message('error', '[Kesimpulanaudit::previewImportExcel] ' . $e->getMessage());
            return $this->jsonErr('Gagal membaca file Excel. Pastikan format sesuai template.', 500);
        }
    }

    public function confirmImportExcel()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        if (!$this->userInGroupPE && !$this->userInGroupAdmin && !$this->userInGroupAuditor) {
            return $this->jsonErr('Tidak memiliki akses', 403);
        }

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();
            if (!$kodebpr || !$periodeId) {
                return $this->jsonErr('Data tidak valid');
            }

            if ($this->kesimpulanauditModel->hasNullRecord($kodebpr, $periodeId)) {
                return $this->jsonErr('Batalkan status "Tidak Ada Data" terlebih dahulu sebelum impor');
            }

            $rows = json_decode($this->request->getPost('rows') ?? '[]', true);
            if (!is_array($rows) || empty($rows)) {
                return $this->jsonErr('Tidak ada data valid untuk disimpan');
            }
            if (count($rows) > self::IMPORT_MAX_ROWS) {
                return $this->jsonErr('Jumlah baris melebihi batas maksimal');
            }

            $periodeDetail = $this->periodeModel->select('tahun, semester')->find($periodeId);
            $user = $this->userModel->select('fullname')->find($this->userId);

            $insertBatch = [];
            foreach ($rows as $r) {
                $kesimpulan = trim((string) ($r['kesimpulan'] ?? ''));
                $pernyataan = trim((string) ($r['pernyataan'] ?? ''));

                if ($kesimpulan === '' || $pernyataan === '') {
                    continue;
                }

                $insertBatch[] = [
                    'user_id' => $this->userId,
                    'kodebpr' => $kodebpr,
                    'periode_id' => $periodeId,
                    'tahun' => $periodeDetail['tahun'] ?? date('Y'),
                    'semester' => $periodeDetail['semester'] ?? '1',
                    'kesimpulan' => $kesimpulan,
                    'pernyataan' => $pernyataan,
                    'fullname' => $user['fullname'] ?? null,
                    'accdir2' => 0,
                ];
            }

            if (empty($insertBatch)) {
                return $this->jsonErr('Tidak ada data baru yang dapat disimpan (semua kosong)');
            }

            $this->db->transStart();
            $this->kesimpulanauditModel->insertBatchSync($insertBatch);
            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return $this->jsonErr('Transaksi gagal, silakan coba kembali');
            }

            return $this->jsonOk(count($insertBatch) . ' data berhasil diimpor dari Excel');

        } catch (\Exception $e) {
            log_message('error', '[Kesimpulanaudit::confirmImportExcel] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }
}