<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_formasisdm;
use App\Models\M_user;
use App\Models\M_infobpr;
use App\Models\M_periodesemesteran;
use App\Models\M_semesterancomments;
use App\Models\M_semesterancommentsread;
use Myth\Auth\Config\Services as AuthServices;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Reader\Xls as XlsReader;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class Formasisdm extends Controller
{
    protected $db;
    protected $auth;
    protected $session;

    protected $formasisdmModel;
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
    const SUBKATEGORI = 'FORMASISDM';
    private const IMPORT_MAX_ROWS = 200;
    private const IMPORT_MAX_SIZE = 3 * 1024 * 1024;

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');

        $this->db = \Config\Database::connect();
        $this->session = service('session');
        $this->auth = service('authentication');

        $this->formasisdmModel = new M_formasisdm();
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
            'judul' => 'Formasi Sumber Daya Manusia',
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
        echo view('Laporansemester/Dirymf/formasisdm', $data);
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

            $rows = $this->formasisdmModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);

            return $this->jsonOk('OK', ['data' => $rows]);

        } catch (\Exception $e) {
            log_message('error', '[Formasisdm::getData] ' . $e->getMessage());
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

            $nik = trim($this->request->getPost('nik'));
            $nama = trim($this->request->getPost('nama'));
            $jabatan = trim($this->request->getPost('jabatan'));

            if (empty($nik) || empty($nama) || empty($jabatan)) {
                return $this->jsonErr('NIK, Nama, dan Jabatan wajib diisi');
            }

            $periodeDetail = $this->periodeModel->find($periodeId);
            $user = $this->userModel->find($this->userId);

            $data = [
                'user_id' => $this->userId,
                'kodebpr' => $kodebpr,
                'periode_id' => $periodeId,
                'tahun' => $periodeDetail['tahun'] ?? date('Y'),
                'semester' => $periodeDetail['semester'] ?? '1',
                'nik' => $nik,
                'nama' => $nama,
                'jabatan' => $jabatan,
                'status' => $this->request->getPost('status'),
                'tingkatpendidikan' => $this->request->getPost('tingkatpendidikan'),
                'masajabatan' => trim($this->request->getPost('masajabatan')),
                'fullname' => $user['fullname'] ?? null,
                'accdir2' => 0,
            ];

            if ($this->formasisdmModel->tambah($data)) {
                return $this->jsonOk('Data berhasil ditambahkan');
            }

            return $this->jsonErr('Gagal menambahkan data');

        } catch (\Exception $e) {
            log_message('error', '[Formasisdm::tambah] ' . $e->getMessage());
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

            $existing = $this->formasisdmModel
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->first();

            if (!$existing) {
                return $this->jsonErr('Data tidak ditemukan atau tidak memiliki akses');
            }

            $nik = trim($this->request->getPost('nik'));
            $nama = trim($this->request->getPost('nama'));
            $jabatan = trim($this->request->getPost('jabatan'));

            if (empty($nik) || empty($nama) || empty($jabatan)) {
                return $this->jsonErr('NIK, Nama, dan Jabatan wajib diisi');
            }

            $data = [
                'nik' => $nik,
                'nama' => $nama,
                'jabatan' => $jabatan,
                'status' => $this->request->getPost('status'),
                'tingkatpendidikan' => $this->request->getPost('tingkatpendidikan'),
                'masajabatan' => trim($this->request->getPost('masajabatan')),
                'accdir2' => 0,
            ];

            if ($this->formasisdmModel->ubah($data, $id, $kodebpr, $periodeId)) {
                return $this->jsonOk('Data berhasil diubah');
            }

            return $this->jsonErr('Gagal mengubah data');

        } catch (\Exception $e) {
            log_message('error', '[Formasisdm::ubah] ' . $e->getMessage());
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

            $existing = $this->formasisdmModel
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->first();

            if (!$existing) {
                return $this->jsonErr('Data tidak ditemukan atau tidak memiliki akses');
            }

            if ($this->formasisdmModel->hapusSpesifik($id, $kodebpr, $periodeId)) {
                return $this->jsonOk('Data berhasil dihapus');
            }

            return $this->jsonErr('Gagal menghapus data');

        } catch (\Exception $e) {
            log_message('error', '[Formasisdm::hapus] ' . $e->getMessage());
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

            $status = $this->formasisdmModel->getApprovalStatus($kodebpr, $periodeId);

            return $this->jsonOk('OK', $status);

        } catch (\Exception $e) {
            log_message('error', '[Formasisdm::getApprovalStatus] ' . $e->getMessage());
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

            $existing = $this->formasisdmModel
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->first();

            if (!$existing)
                return $this->jsonErr('Data tidak ditemukan');

            if ($this->formasisdmModel->approveKomRow($id, $kodebpr, $periodeId, $this->userId)) {
                return $this->jsonOk('Data berhasil disetujui (Direktur yang membawahkan fungsi kepatuhan)');
            }

            return $this->jsonErr('Gagal menyimpan approval');

        } catch (\Exception $e) {
            log_message('error', '[Formasisdm::approveKomRow] ' . $e->getMessage());
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

            $existing = $this->formasisdmModel
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->first();

            if (!$existing)
                return $this->jsonErr('Data tidak ditemukan');

            if ($this->formasisdmModel->unapproveKomRow($id, $kodebpr, $periodeId, $this->userId)) {
                return $this->jsonOk('Approval Direktur Yang Membawahi Fungsi Kepatuhan dibatalkan');
            }

            return $this->jsonErr('Gagal membatalkan approval');

        } catch (\Exception $e) {
            log_message('error', '[Formasisdm::unapproveKomRow] ' . $e->getMessage());
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

            if ($this->formasisdmModel->approveSemuaKom($kodebpr, $periodeId, $this->userId)) {
                return $this->jsonOk('Semua data berhasil disetujui oleh Direktur yang membawahkan fungsi kepatuhan');
            }

            return $this->jsonErr('Tidak ada data yang diupdate');

        } catch (\Exception $e) {
            log_message('error', '[Formasisdm::approveSemuaKom] ' . $e->getMessage());
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

            if ($this->formasisdmModel->unapproveSemuaKom($kodebpr, $periodeId, $this->userId)) {
                return $this->jsonOk('Semua approval Direktur Yang Membawahi Fungsi Kepatuhan berhasil dibatalkan');
            }

            return $this->jsonErr('Tidak ada data yang diupdate');

        } catch (\Exception $e) {
            log_message('error', '[Formasisdm::unapproveSemuaKom] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function approveSemuaDir()
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

            $status = $this->formasisdmModel->getApprovalStatus($kodebpr, $periodeId);
            if (!$status['dekom_approved']) {
                return $this->jsonErr('Direktur Yang Membawahi Fungsi Kepatuhan harus menyetujui seluruh data terlebih dahulu');
            }

            if ($this->formasisdmModel->approveSemuaDir($kodebpr, $periodeId, $this->userId)) {
                return $this->jsonOk('Semua data berhasil disetujui oleh Direktur Utama');
            }

            return $this->jsonErr('Tidak ada data yang diupdate');

        } catch (\Exception $e) {
            log_message('error', '[Formasisdm::approveSemuaDir] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function unapproveSemuaDir()
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

            if ($this->formasisdmModel->unapproveSemuaDir($kodebpr, $periodeId, $this->userId)) {
                return $this->jsonOk('Semua approval Direktur berhasil dibatalkan');
            }

            return $this->jsonErr('Tidak ada data yang diupdate');

        } catch (\Exception $e) {
            log_message('error', '[Formasisdm::unapproveSemuaDir] ' . $e->getMessage());
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
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Komentar tidak boleh kosong'
            ]);
        }

        $teks = trim($this->request->getPost('komentar'));
        $fullname = $this->request->getPost('fullname');

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
                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Komentar berhasil ditambahkan'
                ]);
            }

            throw new \Exception('Gagal menyimpan komentar');

        } catch (\Exception $e) {
            log_message('error', '[Formasisdm::tambahKomentar] ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Gagal menambahkan komentar'
            ]);
        }
    }

    public function getUnreadCounts()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => 'error',
                'message' => 'Forbidden'
            ]);
        }

        [$kodebpr, $periodeId] = $this->getKodebprPeriode();

        $count = $this->komentarReadModel->countUnreadComments(
            $kodebpr,
            $this->userId,
            $periodeId,
            self::KATEGORI,
            self::SUBKATEGORI
        );

        return $this->response->setJSON([
            'status' => 'success',
            'count' => (int) $count
        ]);
    }

    public function markKomentarAsRead()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => 'error',
                'message' => 'Forbidden'
            ]);
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

            $prevRows = $this->formasisdmModel
                ->getDataByKodebprAndPeriode($kodebpr, (int) $prevPeriode['id']);

            if (empty($prevRows)) {
                return $this->response->setJSON([
                    'status' => 'not_found',
                    'message' => 'Periode sebelumnya ada tetapi belum ada data SDM',
                ]);
            }

            $existingNiks = $this->formasisdmModel
                ->getNikByPeriode($kodebpr, $periodeId);

            $semLabel = fn(int $n): string =>
                $n === 1 ? 'Semester 1 (Ganjil)' : 'Semester 2 (Genap)';

            return $this->response->setJSON([
                'status' => 'found',
                'current_label' => $semLabel($semNum) . ' ' . $tahun,
                'prev_label' => $semLabel($prevSemNum) . ' ' . $prevTahun,
                'prev_periode_id' => (int) $prevPeriode['id'],
                'rows' => $prevRows,
                'existing_niks' => $existingNiks,
            ]);

        } catch (\Exception $e) {
            log_message('error', '[Formasisdm::checkPrevPeriode] ' . $e->getMessage());
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

            $prevRows = $this->formasisdmModel
                ->getDataByKodebprAndPeriode($kodebpr, (int) $prevPeriode['id']);

            if (empty($prevRows)) {
                return $this->jsonErr('Tidak ada data SDM pada periode sebelumnya');
            }

            $existingNiks = $this->formasisdmModel->getNikByPeriode($kodebpr, $periodeId);
            $existingNikSet = array_flip($existingNiks);

            $user = $this->userModel->find($this->userId);
            $insertBatch = [];

            foreach ($prevRows as $row) {
                if (isset($existingNikSet[$row['nik']])) {
                    continue;
                }

                $insertBatch[] = [
                    'user_id' => $this->userId,
                    'kodebpr' => $kodebpr,
                    'periode_id' => $periodeId,
                    'tahun' => $current['tahun'],
                    'semester' => $current['semester'],
                    'nik' => $row['nik'],
                    'nama' => $row['nama'],
                    'jabatan' => $row['jabatan'],
                    'status' => $row['status'],
                    'tingkatpendidikan' => $row['tingkatpendidikan'],
                    'masajabatan' => $row['masajabatan'],
                    'fullname' => $user['fullname'] ?? null,
                    'accdir2' => 0,
                    'accdir2_by' => null,
                    'accdir2_at' => null,
                ];
            }

            if (empty($insertBatch)) {
                return $this->jsonErr(
                    'Semua data dari periode sebelumnya sudah ada di periode aktif. Tidak ada data baru.'
                );
            }

            $this->db->transStart();
            $this->formasisdmModel->insertBatchSync($insertBatch);
            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return $this->jsonErr('Transaksi gagal, silakan coba kembali');
            }

            return $this->jsonOk(
                count($insertBatch) . ' data berhasil disinkronisasi dari periode sebelumnya'
            );

        } catch (\Exception $e) {
            log_message('error', '[Formasisdm::syncFromPrevPeriode] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function exporttxtformasisdm()
    {
        if (!$this->auth->check()) {
            return redirect()->to(session('redirect_url') ?? '/login');
        }

        [$kodebpr, $periodeId] = $this->getKodebprPeriode();
        $periodeDetail = $this->periodeModel->find($periodeId);
        $dataFormasisdm = $this->formasisdmModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
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

        $output = "H01|{$kodejenis}|{$sandibpr}|{$tanggalLaporan}|TKSBPRKS|2000|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";

        foreach ($dataFormasisdm as $row) {
            $nik = $sanitize($row['nik'] ?? '');
            $nama = $sanitize($row['nama'] ?? '');
            $jabatan = $sanitize($row['jabatan'] ?? '');
            $status = $sanitize($row['status'] ?? '');
            $tingkatpendidikan = $sanitize($row['tingkatpendidikan'] ?? '');
            $masajabatan = '';
            if (!empty($row['masajabatan'])) {
                $masajabatan = str_replace('-', '', $sanitize($row['masajabatan']));
            }

            if (empty($nik) && empty($nama))
                continue;

            $output .= "D01|K1300|{$nik}|{$nama}|{$jabatan}|{$status}|{$tingkatpendidikan}|{$masajabatan}\r\n";
        }

        $tanggalFile = str_replace('-', '', $tanggalLaporan);
        $filename = "TKSBPRKS-2000-{$jenispelaporan}-S-{$tanggalFile}-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }

    private const JABATAN_MAP = [
        'direktur utama' => '110',
        'direktur yang membawahkan fungsi kepatuhan' => '120',
        'direktur' => '130',
        'komisaris utama' => '210',
        'komisaris' => '220',
        'komisaris independen' => '230',
        'komite audit' => '310',
        'komite pemantau resiko' => '320',
        'komite pemantau risiko' => '320',
        'komite remunerasi dan nominasi' => '330',
        'komite manajemen resiko' => '340',
        'komite manajemen risiko' => '340',
        'komite lainnya' => '350',
        'pejabat eksekutif kepatuhan' => '410',
        'pejabat eksekutif manajemen resiko' => '420',
        'pejabat eksekutif manajemen risiko' => '420',
        'pejabat eksekutif audit intern' => '430',
        'pejabat eksekutif apu ppt' => '440',
        'pejabat eksekutif lainnya' => '450',
    ];

    private const STATUS_MAP = [
        'pengangkatan baru' => '01',
        'pengangkatan kembali' => '02',
        'perubahan jabatan' => '03',
        'tidak berubah' => '04',
    ];

    private const PENDIDIKAN_MAP = [
        's3' => '01',
        's2' => '02',
        's1' => '03',
        'd3' => '04',
        'slta' => '05',
        'lainnya' => '99',
    ];

    private function resolveCode(string $raw, array $map, array $validCodes): ?string
    {
        $raw = trim($raw);
        if ($raw === '')
            return null;

        // Kalau sudah berupa kode valid, langsung pakai
        if (in_array($raw, $validCodes, true))
            return $raw;

        $key = strtolower($raw);
        return $map[$key] ?? null;
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

            $existingNik = $this->formasisdmModel->getNikByPeriode($kodebpr, $periodeId);
            $existingNikSet = array_flip($existingNik);
            $seenInFile = [];

            $preview = [];

            for ($rowIdx = 2; $rowIdx <= $highestRow; $rowIdx++) {
                $nama = trim((string) ($sheet->getCell('A' . $rowIdx)->getValue() ?? ''));
                $nikRaw = $sheet->getCell('B' . $rowIdx)->getValue();
                $jabRaw = trim((string) ($sheet->getCell('C' . $rowIdx)->getValue() ?? ''));
                $statRaw = trim((string) ($sheet->getCell('D' . $rowIdx)->getValue() ?? ''));
                $pendRaw = trim((string) ($sheet->getCell('E' . $rowIdx)->getValue() ?? ''));
                $tglRaw = $sheet->getCell('F' . $rowIdx)->getValue();

                // NIK bisa terbaca sebagai angka/float dari Excel, normalisasi ke string tanpa desimal
                $nik = $nikRaw === null ? '' : trim(preg_replace('/\.0+$/', '', (string) $nikRaw));

                if ($nama === '' && $nik === '' && $jabRaw === '') {
                    continue;
                }

                $rowErr = [];
                if ($nama === '')
                    $rowErr[] = 'Nama kosong';
                if ($nik === '')
                    $rowErr[] = 'NIK kosong';

                $jabatanCode = $this->resolveCode($jabRaw, self::JABATAN_MAP, array_values(self::JABATAN_MAP));
                if ($jabRaw === '')
                    $rowErr[] = 'Jabatan kosong';
                elseif ($jabatanCode === null)
                    $rowErr[] = 'Jabatan "' . $jabRaw . '" tidak dikenali';

                $statusCode = $statRaw === '' ? null : $this->resolveCode($statRaw, self::STATUS_MAP, array_values(self::STATUS_MAP));
                if ($statRaw !== '' && $statusCode === null)
                    $rowErr[] = 'Status "' . $statRaw . '" tidak dikenali';

                $pendCode = $pendRaw === '' ? null : $this->resolveCode($pendRaw, self::PENDIDIKAN_MAP, array_values(self::PENDIDIKAN_MAP));
                if ($pendRaw !== '' && $pendCode === null)
                    $rowErr[] = 'Pendidikan "' . $pendRaw . '" tidak dikenali';

                $tanggal = null;
                if ($tglRaw !== null && $tglRaw !== '') {
                    if (is_numeric($tglRaw)) {
                        // Cell Excel bertipe Date asli -> serial number, tidak tergantung format tampilan
                        try {
                            $tanggal = ExcelDate::excelToDateTimeObject($tglRaw)->format('Y-m-d');
                        } catch (\Exception $e) {
                            $rowErr[] = 'Format tanggal tidak valid';
                        }
                    } else {
                        // Cell berupa teks -> asumsikan input dd/mm/yyyy (atau dd-mm-yyyy)
                        $raw = trim((string) $tglRaw);
                        $raw = str_replace('-', '/', $raw);

                        $dt = \DateTime::createFromFormat('d/m/Y', $raw);
                        if (!$dt) {
                            $dt = \DateTime::createFromFormat('d/m/y', $raw); // fallback tahun 2 digit
                        }

                        $errors = \DateTime::getLastErrors();
                        $valid = $dt && (!$errors || ($errors['warning_count'] === 0 && $errors['error_count'] === 0));

                        if ($valid) {
                            $tanggal = $dt->format('Y-m-d');
                        } else {
                            $rowErr[] = 'Format tanggal tidak valid (gunakan dd/mm/yyyy), nilai: "' . $raw . '"';
                        }
                    }
                }

                $isDupDb = $nik !== '' && isset($existingNikSet[$nik]);
                $isDupFile = $nik !== '' && isset($seenInFile[$nik]);
                if ($nik !== '')
                    $seenInFile[$nik] = true;

                $status = 'ok';
                if (!empty($rowErr))
                    $status = 'error';
                elseif ($isDupDb || $isDupFile)
                    $status = 'skip';

                $preview[] = [
                    'line' => $rowIdx,
                    'nik' => $nik,
                    'nama' => $nama,
                    'jabatan' => $jabatanCode,
                    'jabatan_label' => $jabRaw,
                    'status' => $statusCode,
                    'status_label' => $statRaw,
                    'tingkatpendidikan' => $pendCode,
                    'pendidikan_label' => $pendRaw,
                    'masajabatan' => $tanggal,
                    'row_status' => $status,
                    'errors' => $rowErr,
                    'dup_reason' => $isDupDb ? 'NIK sudah ada di periode ini' : ($isDupFile ? 'Duplikat NIK dalam file' : null),
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
            log_message('error', '[Formasisdm::previewImportExcel] ' . $e->getMessage());
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

            $rows = json_decode($this->request->getPost('rows') ?? '[]', true);
            if (!is_array($rows) || empty($rows)) {
                return $this->jsonErr('Tidak ada data valid untuk disimpan');
            }
            if (count($rows) > self::IMPORT_MAX_ROWS) {
                return $this->jsonErr('Jumlah baris melebihi batas maksimal');
            }

            $existingNik = $this->formasisdmModel->getNikByPeriode($kodebpr, $periodeId);
            $existingNikSet = array_flip($existingNik);

            $periodeDetail = $this->periodeModel->find($periodeId);
            $user = $this->userModel->find($this->userId);

            $insertBatch = [];
            $seenInFile = [];

            foreach ($rows as $r) {
                $nik = trim((string) ($r['nik'] ?? ''));
                $nama = trim((string) ($r['nama'] ?? ''));
                $jabatan = trim((string) ($r['jabatan'] ?? ''));

                if ($nik === '' || $nama === '' || $jabatan === '')
                    continue;
                if (isset($existingNikSet[$nik]) || isset($seenInFile[$nik]))
                    continue;
                $seenInFile[$nik] = true;

                $insertBatch[] = [
                    'user_id' => $this->userId,
                    'kodebpr' => $kodebpr,
                    'periode_id' => $periodeId,
                    'tahun' => $periodeDetail['tahun'] ?? date('Y'),
                    'semester' => $periodeDetail['semester'] ?? '1',
                    'nik' => $nik,
                    'nama' => $nama,
                    'jabatan' => $jabatan,
                    'status' => $r['status'] ?? null,
                    'tingkatpendidikan' => $r['tingkatpendidikan'] ?? null,
                    'masajabatan' => $r['masajabatan'] ?? null,
                    'fullname' => $user['fullname'] ?? null,
                    'accdir2' => 0,
                ];
            }

            if (empty($insertBatch)) {
                return $this->jsonErr('Tidak ada data baru yang dapat disimpan (semua duplikat/kosong)');
            }

            $this->db->transStart();
            $this->formasisdmModel->insertBatchSync($insertBatch);
            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return $this->jsonErr('Transaksi gagal, silakan coba kembali');
            }

            return $this->jsonOk(count($insertBatch) . ' data berhasil diimpor dari Excel');

        } catch (\Exception $e) {
            log_message('error', '[Formasisdm::confirmImportExcel] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }
}