<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_lingkupaudit;
use App\Models\M_user;
use App\Models\M_infobpr;
use App\Models\M_periodesemesteran;
use App\Models\M_semesterancomments;
use App\Models\M_semesterancommentsread;
use Myth\Auth\Config\Services as AuthServices;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Reader\Xls as XlsReader;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class Lingkupaudit extends Controller
{
    protected $db;
    protected $auth;
    protected $session;

    protected $lingkupauditModel;
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
    protected $userInGroupAuditor;

    protected $komentarModel;
    protected $komentarReadModel;

    const KATEGORI = 'DIRYMF';
    const SUBKATEGORI = 'LINGKUPAUDIT';

    private const IMPORT_MAX_ROWS = 200;
    private const IMPORT_MAX_SIZE = 3 * 1024 * 1024; // 3MB

    private $_periodeCache = [];

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');

        $this->db = \Config\Database::connect();
        $this->session = service('session');
        $this->auth = service('authentication');
        $this->lingkupauditModel = new M_lingkupaudit();
        $this->userModel = new M_user();
        $this->infobprModel = new M_infobpr();
        $this->periodeModel = new M_periodesemesteran();
        $this->komentarModel = new M_semesterancomments();
        $this->komentarReadModel = new M_semesterancommentsread();

        helper('url');

        if ($this->auth->check()) {
            $this->userId = $this->auth->id();
            $user = $this->userModel->select('kodebpr,fullname')->find($this->userId);
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

    private function getPeriodeDetail(int $periodeId): array
    {
        if (!isset($this->_periodeCache[$periodeId])) {
            $this->_periodeCache[$periodeId] = $this->periodeModel->find($periodeId) ?? [];
        }
        return $this->_periodeCache[$periodeId];
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

    private function checkAccess(bool ...$groups): bool
    {
        foreach ($groups as $g) {
            if ($g)
                return true;
        }
        return false;
    }

    // Pages
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
        $periodeDetail = $this->getPeriodeDetail($periodeId);
        $bprData = $this->infobprModel->getBprByKode($kodebpr);

        $data = [
            'judul' => 'Ruang Lingkup dan Waktu Pelaksanaan Audit',
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
            'userInGroupAuditor' => $this->userInGroupAuditor,
        ];

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('Laporansemester/Laporanaudit/lingkupaudit', $data);
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

            $allRows = $this->lingkupauditModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);

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
            log_message('error', '[LingkupauditModel::getData] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function tambah()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();
            if (!$kodebpr || !$periodeId)
                return $this->jsonErr('Kode BPR atau Periode tidak valid');

            if ($this->lingkupauditModel->hasNullRecord($kodebpr, $periodeId)) {
                return $this->jsonErr('Batalkan status "Tidak Ada Data" terlebih dahulu sebelum menambah data');
            }

            $ruanglingkup = trim($this->request->getPost('ruanglingkup'));
            $tanggalmulai = trim($this->request->getPost('tanggalmulai'));
            $tanggalberakhir = trim($this->request->getPost('tanggalberakhir'));

            if (empty($ruanglingkup) || empty($tanggalmulai) || empty($tanggalberakhir)) {
                return $this->jsonErr('Ruang Lingkup, Tanggal Mulai, dan Tanggal Akhir wajib diisi');
            }

            $periodeDetail = $this->getPeriodeDetail($periodeId);
            $user = $this->userModel->select('fullname')->find($this->userId);

            $insertData = [
                'user_id' => $this->userId,
                'kodebpr' => $kodebpr,
                'periode_id' => $periodeId,
                'tahun' => $periodeDetail['tahun'] ?? date('Y'),
                'semester' => $periodeDetail['semester'] ?? '1',
                'ruanglingkup' => $ruanglingkup,
                'tanggalmulai' => $tanggalmulai,
                'tanggalberakhir' => $tanggalberakhir,
                'fullname' => $user['fullname'] ?? null,
                'accdir2' => 0,
            ];

            if ($this->lingkupauditModel->tambah($insertData)) {
                return $this->jsonOk('Data berhasil ditambahkan');
            }
            return $this->jsonErr('Gagal menambahkan data');
        } catch (\Exception $e) {
            log_message('error', '[Lingkupaudit::tambah] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function ubah()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;

        try {
            $id = (int) $this->request->getPost('id');
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();
            if (!$id || !$kodebpr || !$periodeId)
                return $this->jsonErr('Data tidak valid');

            $existing = $this->lingkupauditModel
                ->select('id')
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->first();
            if (!$existing)
                return $this->jsonErr('Data tidak ditemukan atau tidak memiliki akses');

            $ruanglingkup = trim($this->request->getPost('ruanglingkup'));
            $tanggalmulai = trim($this->request->getPost('tanggalmulai'));
            $tanggalberakhir = trim($this->request->getPost('tanggalberakhir'));

            if (empty($ruanglingkup) || empty($tanggalmulai) || empty($tanggalberakhir)) {
                return $this->jsonErr('Ruang Lingkup, Tanggal Mulai, dan Tanggal Akhir wajib diisi');
            }

            $updateData = [
                'ruanglingkup' => $ruanglingkup,
                'tanggalmulai' => $tanggalmulai,
                'tanggalberakhir' => $tanggalberakhir,
                'accdir2' => 0,
            ];

            if ($this->lingkupauditModel->ubah($updateData, $id, $kodebpr, $periodeId)) {
                return $this->jsonOk('Data berhasil diubah');
            }
            return $this->jsonErr('Gagal mengubah data');
        } catch (\Exception $e) {
            log_message('error', '[Lingkupaudit::ubah] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function hapus()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;

        try {
            $id = (int) $this->request->getPost('id');
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();
            if (!$id || !$kodebpr || !$periodeId)
                return $this->jsonErr('Data tidak valid');

            $existing = $this->lingkupauditModel
                ->select('id')
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->first();
            if (!$existing)
                return $this->jsonErr('Data tidak ditemukan atau tidak memiliki akses');

            if ($this->lingkupauditModel->hapusSpesifik($id, $kodebpr, $periodeId)) {
                return $this->jsonOk('Data berhasil dihapus');
            }
            return $this->jsonErr('Gagal menghapus data');
        } catch (\Exception $e) {
            log_message('error', '[Lingkupaudit::hapus] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function getApprovalStatus()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();
            if (!$kodebpr || !$periodeId)
                return $this->jsonErr('Data tidak lengkap');

            $keterangan = $this->lingkupauditModel->getApprovalStatus($kodebpr, $periodeId);
            $this->response->setHeader('Cache-Control', 'private, max-age=10');
            return $this->jsonOk('OK', $keterangan);
        } catch (\Exception $e) {
            log_message('error', '[Lingkupaudit::getApprovalStatus] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function approveKomRow()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;
        if (!$this->checkAccess($this->userInGroupAdmin, $this->userInGroupDireksi, $this->userInGroupDireksi2)) {
            return $this->jsonErr('Tidak memiliki akses', 403);
        }

        try {
            $id = (int) $this->request->getPost('id');
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            $existing = $this->lingkupauditModel->select('id')
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();
            if (!$existing)
                return $this->jsonErr('Data tidak ditemukan');

            if ($this->lingkupauditModel->approveKomRow($id, $kodebpr, $periodeId, $this->userId)) {
                return $this->jsonOk('Data berhasil disetujui (Direktur Utama)');
            }
            return $this->jsonErr('Gagal menyimpan approval');
        } catch (\Exception $e) {
            log_message('error', '[Lingkupaudit::approveKomRow] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function unapproveKomRow()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;
        if (!$this->checkAccess($this->userInGroupAdmin, $this->userInGroupDireksi, $this->userInGroupDireksi2)) {
            return $this->jsonErr('Tidak memiliki akses', 403);
        }

        try {
            $id = (int) $this->request->getPost('id');
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            $existing = $this->lingkupauditModel->select('id')
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();
            if (!$existing)
                return $this->jsonErr('Data tidak ditemukan');

            if ($this->lingkupauditModel->unapproveKomRow($id, $kodebpr, $periodeId, $this->userId)) {
                return $this->jsonOk('Approval Direktur Utama');
            }
            return $this->jsonErr('Gagal membatalkan approval');
        } catch (\Exception $e) {
            log_message('error', '[Lingkupaudit::unapproveKomRow] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function approveDirRow()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;
        if (!$this->checkAccess($this->userInGroupAdmin, $this->userInGroupDireksi, $this->userInGroupDireksi2)) {
            return $this->jsonErr('Tidak memiliki akses', 403);
        }

        try {
            $id = (int) $this->request->getPost('id');
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            $existing = $this->lingkupauditModel->select('id,accdir2')
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();
            if (!$existing)
                return $this->jsonErr('Data tidak ditemukan');
            if ((int) $existing['accdir2'] !== 1) {
                return $this->jsonErr('Direktur Utama');
            }

            if ($this->lingkupauditModel->approveDirRow($id, $kodebpr, $periodeId, $this->userId)) {
                return $this->jsonOk('Data berhasil disetujui (Direktur Utama)');
            }
            return $this->jsonErr('Gagal menyimpan approval');
        } catch (\Exception $e) {
            log_message('error', '[Lingkupaudit::approveDirRow] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function unapproveDirRow()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;
        if (!$this->checkAccess($this->userInGroupAdmin, $this->userInGroupDireksi, $this->userInGroupDireksi2)) {
            return $this->jsonErr('Tidak memiliki akses', 403);
        }

        try {
            $id = (int) $this->request->getPost('id');
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            $existing = $this->lingkupauditModel->select('id')
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();
            if (!$existing)
                return $this->jsonErr('Data tidak ditemukan');

            if ($this->lingkupauditModel->unapproveDirRow($id, $kodebpr, $periodeId, $this->userId)) {
                return $this->jsonOk('Approval Direktur dibatalkan');
            }
            return $this->jsonErr('Gagal membatalkan approval');
        } catch (\Exception $e) {
            log_message('error', '[Lingkupaudit::unapproveDirRow] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function approveSemuaKom()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;
        if (!$this->checkAccess($this->userInGroupAdmin, $this->userInGroupDireksi, $this->userInGroupDireksi2)) {
            return $this->jsonErr('Tidak memiliki akses', 403);
        }

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();
            if (!$kodebpr || !$periodeId)
                return $this->jsonErr('Data tidak valid');

            if ($this->lingkupauditModel->approveSemuaKom($kodebpr, $periodeId, $this->userId)) {
                return $this->jsonOk('Semua data berhasil disetujui oleh Direktur Utama');
            }
            return $this->jsonErr('Tidak ada data yang diupdate');
        } catch (\Exception $e) {
            log_message('error', '[Lingkupaudit::approveSemuaKom] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function unapproveSemuaKom()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;
        if (!$this->checkAccess($this->userInGroupAdmin, $this->userInGroupDireksi, $this->userInGroupDireksi2)) {
            return $this->jsonErr('Tidak memiliki akses', 403);
        }

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();
            if (!$kodebpr || !$periodeId)
                return $this->jsonErr('Data tidak valid');

            if ($this->lingkupauditModel->unapproveSemuaKom($kodebpr, $periodeId, $this->userId)) {
                return $this->jsonOk('Semua approval Direktur Utama');
            }
            return $this->jsonErr('Tidak ada data yang diupdate');
        } catch (\Exception $e) {
            log_message('error', '[Lingkupaudit::unapproveSemuaKom] ' . $e->getMessage());
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

            if ($this->lingkupauditModel->hasRealData($kodebpr, $periodeId)) {
                return $this->jsonErr('Tidak bisa ditandai kosong — masih ada data yang tersimpan. Hapus data terlebih dahulu.');
            }

            if ($this->lingkupauditModel->hasNullRecord($kodebpr, $periodeId)) {
                return $this->jsonOk('Periode ini sudah ditandai tidak ada data');
            }

            $periodeDetail = $this->periodeModel->find($periodeId);
            $user = $this->userModel->find($this->userId);

            $ok = $this->lingkupauditModel->insertNullRecord([
                'user_id' => $this->userId,
                'kodebpr' => $kodebpr,
                'periode_id' => $periodeId,
                'tahun' => $periodeDetail['tahun'] ?? date('Y'),
                'semester' => $periodeDetail['semester'] ?? '1',
                'ruanglingkup' => null,
                'tanggalmulai' => null,
                'tanggalberakhir' => null,
                'fullname' => $user['fullname'] ?? null,
                'accdir2' => 0,
                'is_null_record' => 1,
            ]);

            if (!$ok)
                return $this->jsonErr('Gagal menyimpan status');

            return $this->jsonOk('Periode berhasil ditandai tidak ada data Ruang Lingkup dan Waktu Pelaksanaan Audit');

        } catch (\Exception $e) {
            log_message('error', '[Lingkupaudit::setNodataAjax] ' . $e->getMessage());
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

            if ($this->lingkupauditModel->removeNullRecord($kodebpr, $periodeId)) {
                return $this->jsonOk('Status "Tidak Ada Data" berhasil dibatalkan');
            }
            return $this->jsonErr('Gagal membatalkan status');

        } catch (\Exception $e) {
            log_message('error', '[Lingkupaudit::batalNodataAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function approveSemuaDir()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;
        if (!$this->checkAccess($this->userInGroupAdmin, $this->userInGroupDireksi)) {
            return $this->jsonErr('Tidak memiliki akses', 403);
        }

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();
            if (!$kodebpr || !$periodeId)
                return $this->jsonErr('Data tidak valid');

            $keterangan = $this->lingkupauditModel->getApprovalStatus($kodebpr, $periodeId);
            if (!$keterangan['dekom_approved']) {
                return $this->jsonErr('Direktur Utama');
            }

            if ($this->lingkupauditModel->approveSemuaDir($kodebpr, $periodeId, $this->userId)) {
                return $this->jsonOk('Semua data berhasil disetujui oleh Direktur Utama');
            }
            return $this->jsonErr('Tidak ada data yang diupdate');
        } catch (\Exception $e) {
            log_message('error', '[Lingkupaudit::approveSemuaDir] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function unapproveSemuaDir()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;
        if (!$this->checkAccess($this->userInGroupAdmin, $this->userInGroupDireksi)) {
            return $this->jsonErr('Tidak memiliki akses', 403);
        }

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();
            if (!$kodebpr || !$periodeId)
                return $this->jsonErr('Data tidak valid');

            if ($this->lingkupauditModel->unapproveSemuaDir($kodebpr, $periodeId, $this->userId)) {
                return $this->jsonOk('Semua approval Direktur berhasil dibatalkan');
            }
            return $this->jsonErr('Tidak ada data yang diupdate');
        } catch (\Exception $e) {
            log_message('error', '[Lingkupaudit::unapproveSemuaDir] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    // Komentar
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
        $fullname = $this->request->getPost('fullname');

        if (empty($teks)) {
            return $this->jsonErr('Komentar tidak boleh kosong');
        }
        if (strlen($teks) < 3) {
            return $this->jsonErr('Komentar minimal 3 karakter');
        }
        if (strlen($teks) > 1000) {
            return $this->jsonErr('Komentar maksimal 1000 karakter');
        }

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
                return $this->jsonOk('Komentar berhasil ditambahkan');
            }
            throw new \Exception('Gagal menyimpan komentar');
        } catch (\Exception $e) {
            log_message('error', '[Lingkupaudit::tambahKomentar] ' . $e->getMessage());
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

        $this->response->setHeader('Cache-Control', 'private, max-age=15');

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
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            if (!$kodebpr || !$periodeId) {
                return $this->jsonErr('Data tidak valid');
            }

            $current = $this->getPeriodeDetail($periodeId);
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

            $prevRows = $this->lingkupauditModel
                ->getDataByKodebprAndPeriode($kodebpr, (int) $prevPeriode['id']);

            if (empty($prevRows)) {
                return $this->response->setJSON([
                    'status' => 'not_found',
                    'message' => 'Periode sebelumnya ada tetapi belum ada data Ruang Lingkup Audit',
                ]);
            }

            // Composite key "tanggalmulai||tanggalberakhir" yang sudah ada
            $existingKeys = $this->lingkupauditModel
                ->getTanggalByPeriode($kodebpr, $periodeId);

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
            log_message('error', '[Lingkupaudit::checkPrevPeriode] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function syncFromPrevPeriode()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;

        if (!$this->checkAccess($this->userInGroupPE, $this->userInGroupAdmin, $this->userInGroupAuditor)) {
            return $this->jsonErr('Tidak memiliki akses untuk sinkronisasi data', 403);
        }

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            if (!$kodebpr || !$periodeId) {
                return $this->jsonErr('Data tidak valid');
            }

            $current = $this->getPeriodeDetail($periodeId);
            if (empty($current)) {
                return $this->jsonErr('Periode aktif tidak ditemukan');
            }

            if ($this->lingkupauditModel->hasNullRecord($kodebpr, $periodeId)) {
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
                ->where('kodebpr', $kodebpr)
                ->where('tahun', $prevTahun)
                ->whereIn('semester', $prevSemVariants)
                ->first();

            if (!$prevPeriode) {
                return $this->jsonErr('Periode sebelumnya tidak ditemukan');
            }

            $prevRows = $this->lingkupauditModel
                ->getDataByKodebprAndPeriode($kodebpr, (int) $prevPeriode['id']);

            if (empty($prevRows)) {
                return $this->jsonErr('Tidak ada data Ruang Lingkup Audit pada periode sebelumnya');
            }

            $existingKeys = $this->lingkupauditModel->getTanggalByPeriode($kodebpr, $periodeId);
            $existingKeySet = array_flip($existingKeys);

            $user = $this->userModel->select('fullname')->find($this->userId);
            $insertBatch = [];

            foreach ($prevRows as $row) {
                $compositeKey = $row['tanggalmulai'] . '||' . $row['tanggalberakhir'];

                if (isset($existingKeySet[$compositeKey])) {
                    continue;
                }

                $insertBatch[] = [
                    'user_id' => $this->userId,
                    'kodebpr' => $kodebpr,
                    'periode_id' => $periodeId,
                    'tahun' => $current['tahun'],
                    'semester' => $current['semester'],
                    'ruanglingkup' => $row['ruanglingkup'],
                    'tanggalmulai' => $row['tanggalmulai'],
                    'tanggalberakhir' => $row['tanggalberakhir'],
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
            $this->lingkupauditModel->insertBatchSync($insertBatch);
            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return $this->jsonErr('Transaksi gagal, silakan coba kembali');
            }

            return $this->jsonOk(
                count($insertBatch) . ' data berhasil disinkronisasi dari periode sebelumnya'
            );

        } catch (\Exception $e) {
            log_message('error', '[Lingkupaudit::syncFromPrevPeriode] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function exporttxtlingkupaudit()
    {
        if (!$this->auth->check()) {
            return redirect()->to(session('redirect_url') ?? '/login');
        }

        [$kodebpr, $periodeId] = $this->getKodebprPeriode();
        $periodeDetail = $this->getPeriodeDetail($periodeId);
        $dataLingkupaudit = $this->lingkupauditModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        $dataInfobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        $modalinti = $periodeDetail['modalinti'];
        $kategori = $periodeDetail['kategori'];
        $nomorsurat = $periodeDetail['nomorsurat'];
        $jeniskoreksi = $periodeDetail['jeniskoreksi'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];

        $sandibpr = $dataInfobpr[0]['sandibpr'] ?? '';
        $kodejenis = $dataInfobpr[0]['kodejenis'] ?? '';

        $tahun = $periodeDetail['tahun'] ?? date('Y');
        $semester = $periodeDetail['semester'] ?? '';

        $tanggalLaporan = (strtolower($semester) === 'ganjil' || $semester === '1')
            ? "{$tahun}-06-30"
            : "{$tahun}-12-31";

        $sanitize = static function (string $text): string {
            $text = str_replace(["\r", "\n"], ' ', $text);
            $text = str_replace(['|', '%'], [' ', ' persen '], $text);
            $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
            return trim(preg_replace('/\s+/', ' ', $text));
        };

        $output = "H01|{$kodejenis}|{$sandibpr}|{$tanggalLaporan}|TKSBPRKS|3000|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";

        foreach ($dataLingkupaudit as $row) {
            $ruanglingkup = $sanitize($row['ruanglingkup'] ?? '');
            $tanggalmulai = !empty($row['tanggalmulai']) ? str_replace('-', '', $sanitize($row['tanggalmulai'])) : '';
            $tanggalberakhir = !empty($row['tanggalberakhir']) ? str_replace('-', '', $sanitize($row['tanggalberakhir'])) : '';

            if (empty($ruanglingkup) && empty($tanggalmulai))
                continue;

            $output .= "D01|A1900|{$ruanglingkup}|{$tanggalmulai}|{$tanggalberakhir}\r\n";
        }

        $tanggalFile = str_replace('-', '', $tanggalLaporan);
        $filename = "TKSBPRKS-3000-{$jenispelaporan}-S-{$tanggalFile}-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }

    private function parseExcelDate($cell): ?string
    {
        $value = $cell->getValue();
        if ($value === null || $value === '')
            return null;

        if (is_numeric($value)) {
            try {
                $dateObj = ExcelDate::excelToDateTimeObject($value);
                return $dateObj->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        $value = trim((string) $value);
        if ($value === '')
            return null;

        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'];
        foreach ($formats as $fmt) {
            $d = \DateTime::createFromFormat($fmt, $value);
            if ($d && $d->format($fmt) === $value) {
                return $d->format('Y-m-d');
            }
        }

        $ts = strtotime($value);
        return $ts !== false ? date('Y-m-d', $ts) : null;
    }

    public function previewImportExcel()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;

        if (!$this->checkAccess($this->userInGroupPE, $this->userInGroupAdmin, $this->userInGroupAuditor)) {
            return $this->jsonErr('Tidak memiliki akses', 403);
        }

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();
            if (!$kodebpr || !$periodeId) {
                return $this->jsonErr('Kode BPR atau periode tidak valid');
            }

            if ($this->lingkupauditModel->hasNullRecord($kodebpr, $periodeId)) {
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
                $tanggalmulaiRaw = $sheet->getCell('A' . $rowIdx)->getValue();
                $tanggalberakhirRaw = $sheet->getCell('B' . $rowIdx)->getValue();
                $ruanglingkup = trim((string) ($sheet->getCell('C' . $rowIdx)->getValue() ?? ''));

                if ($tanggalmulaiRaw === null && $tanggalberakhirRaw === null && $ruanglingkup === '') {
                    continue; // baris kosong, skip diam-diam
                }

                $tanggalmulai = $this->parseExcelDate($sheet->getCell('A' . $rowIdx));
                $tanggalberakhir = $this->parseExcelDate($sheet->getCell('B' . $rowIdx));

                $rowErr = [];
                if (empty($ruanglingkup))
                    $rowErr[] = 'Ruang Lingkup Audit kosong';
                if (!$tanggalmulai)
                    $rowErr[] = 'Tanggal Mulai tidak valid/kosong';
                if (!$tanggalberakhir)
                    $rowErr[] = 'Tanggal Akhir tidak valid/kosong';
                if ($tanggalmulai && $tanggalberakhir && $tanggalberakhir < $tanggalmulai) {
                    $rowErr[] = 'Tanggal Akhir lebih awal dari Tanggal Mulai';
                }

                $preview[] = [
                    'line' => $rowIdx,
                    'ruanglingkup' => $ruanglingkup,
                    'tanggalmulai' => $tanggalmulai,
                    'tanggalberakhir' => $tanggalberakhir,
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
            log_message('error', '[Lingkupaudit::previewImportExcel] ' . $e->getMessage());
            return $this->jsonErr('Gagal membaca file Excel. Pastikan format sesuai template.', 500);
        }
    }

    public function confirmImportExcel()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;

        if (!$this->checkAccess($this->userInGroupPE, $this->userInGroupAdmin, $this->userInGroupAuditor)) {
            return $this->jsonErr('Tidak memiliki akses', 403);
        }

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();
            if (!$kodebpr || !$periodeId) {
                return $this->jsonErr('Data tidak valid');
            }

            if ($this->lingkupauditModel->hasNullRecord($kodebpr, $periodeId)) {
                return $this->jsonErr('Batalkan status "Tidak Ada Data" terlebih dahulu sebelum impor');
            }

            $rows = json_decode($this->request->getPost('rows') ?? '[]', true);
            if (!is_array($rows) || empty($rows)) {
                return $this->jsonErr('Tidak ada data valid untuk disimpan');
            }
            if (count($rows) > self::IMPORT_MAX_ROWS) {
                return $this->jsonErr('Jumlah baris melebihi batas maksimal');
            }

            $periodeDetail = $this->getPeriodeDetail($periodeId);
            $user = $this->userModel->select('fullname')->find($this->userId);

            $insertBatch = [];
            foreach ($rows as $r) {
                $ruanglingkup = trim((string) ($r['ruanglingkup'] ?? ''));
                $tanggalmulai = trim((string) ($r['tanggalmulai'] ?? ''));
                $tanggalberakhir = trim((string) ($r['tanggalberakhir'] ?? ''));

                if ($ruanglingkup === '' || $tanggalmulai === '' || $tanggalberakhir === '') {
                    continue;
                }

                $insertBatch[] = [
                    'user_id' => $this->userId,
                    'kodebpr' => $kodebpr,
                    'periode_id' => $periodeId,
                    'tahun' => $periodeDetail['tahun'] ?? date('Y'),
                    'semester' => $periodeDetail['semester'] ?? '1',
                    'ruanglingkup' => $ruanglingkup,
                    'tanggalmulai' => $tanggalmulai,
                    'tanggalberakhir' => $tanggalberakhir,
                    'fullname' => $user['fullname'] ?? null,
                    'accdir2' => 0,
                ];
            }

            if (empty($insertBatch)) {
                return $this->jsonErr('Tidak ada data baru yang dapat disimpan (semua kosong)');
            }

            $this->db->transStart();
            $this->lingkupauditModel->insertBatchSync($insertBatch);
            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return $this->jsonErr('Transaksi gagal, silakan coba kembali');
            }

            return $this->jsonOk(count($insertBatch) . ' data berhasil diimpor dari Excel');

        } catch (\Exception $e) {
            log_message('error', '[Lingkupaudit::confirmImportExcel] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }
}