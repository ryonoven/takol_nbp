<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_ketentuanbpr;
use App\Models\M_user;
use App\Models\M_infobpr;
use App\Models\M_periodesemesteran;
use App\Models\M_semesterancomments;
use App\Models\M_semesterancommentsread;
use Myth\Auth\Config\Services as AuthServices;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Reader\Xls as XlsReader;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class Ketentuanbpr extends Controller
{
    protected $db;
    protected $auth;
    protected $session;

    protected $ketentuanbprModel;
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
    const SUBKATEGORI = 'KETENTUANBPR';
    private const IMPORT_MAX_ROWS = 400;
    private const IMPORT_MAX_SIZE = 3 * 1024 * 1024;

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');

        $this->db = \Config\Database::connect();
        $this->session = service('session');
        $this->auth = service('authentication');

        $this->ketentuanbprModel = new M_ketentuanbpr();
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
            'judul' => 'Ketentuan Internal Yang Dimiliki BPR',
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
        echo view('Laporansemester/Dirymf/ketentuanbpr', $data);
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

            $allRows = $this->ketentuanbprModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);

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
            log_message('error', '[Ketentuanbpr::getData] ' . $e->getMessage());
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

            if ($this->ketentuanbprModel->hasNullRecord($kodebpr, $periodeId)) {
                return $this->jsonErr('Batalkan status "Tidak Ada Data" terlebih dahulu sebelum menambah data');
            }

            $judulketentuan = trim($this->request->getPost('judulketentuan'));
            $nomorketentuan = trim($this->request->getPost('nomorketentuan'));
            $tanggalberlaku = trim($this->request->getPost('tanggalberlaku'));
            $keterangan = trim($this->request->getPost('keterangan'));

            if (empty($judulketentuan) || empty($nomorketentuan) || empty($tanggalberlaku) || empty($keterangan)) {
                return $this->jsonErr('Judul Ketentuan, Nomor Ketentuan, Tanggal Pemberlakuan, dan Keterangan wajib diisi');
            }

            $periodeDetail = $this->periodeModel->find($periodeId);
            $user = $this->userModel->find($this->userId);

            $data = [
                'user_id' => $this->userId,
                'kodebpr' => $kodebpr,
                'periode_id' => $periodeId,
                'tahun' => $periodeDetail['tahun'] ?? date('Y'),
                'semester' => $periodeDetail['semester'] ?? '1',
                'judulketentuan' => $judulketentuan,
                'nomorketentuan' => $nomorketentuan,
                'tanggalberlaku' => $tanggalberlaku,
                'keterangan' => $keterangan,
                'fullname' => $user['fullname'] ?? null,
                'accdir2' => 0
            ];

            if ($this->ketentuanbprModel->tambah($data)) {
                return $this->jsonOk('Data berhasil ditambahkan');
            }

            return $this->jsonErr('Gagal menambahkan data');

        } catch (\Exception $e) {
            log_message('error', '[Ketentuanbpr::tambah] ' . $e->getMessage());
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

            $existing = $this->ketentuanbprModel
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->first();

            if (!$existing) {
                return $this->jsonErr('Data tidak ditemukan atau tidak memiliki akses');
            }

            $judulketentuan = trim($this->request->getPost('judulketentuan'));
            $nomorketentuan = trim($this->request->getPost('nomorketentuan'));
            $tanggalberlaku = trim($this->request->getPost('tanggalberlaku'));
            $keterangan = trim($this->request->getPost('keterangan'));

            if (empty($judulketentuan) || empty($nomorketentuan) || empty($tanggalberlaku) || empty($keterangan)) {
                return $this->jsonErr('Judul Ketentuan, Nomor Ketentuan, Tanggal Pemberlakuan, dan Keterangan wajib diisi');
            }

            $data = [
                'judulketentuan' => $judulketentuan,
                'nomorketentuan' => $nomorketentuan,
                'tanggalberlaku' => $tanggalberlaku,
                'keterangan' => $keterangan,
                'accdir2' => 0
            ];

            if ($this->ketentuanbprModel->ubah($data, $id, $kodebpr, $periodeId)) {
                return $this->jsonOk('Data berhasil diubah');
            }

            return $this->jsonErr('Gagal mengubah data');

        } catch (\Exception $e) {
            log_message('error', '[Ketentuanbpr::ubah] ' . $e->getMessage());
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

            $existing = $this->ketentuanbprModel
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->first();

            if (!$existing) {
                return $this->jsonErr('Data tidak ditemukan atau tidak memiliki akses');
            }

            if ($this->ketentuanbprModel->hapusSpesifik($id, $kodebpr, $periodeId)) {
                return $this->jsonOk('Data berhasil dihapus');
            }

            return $this->jsonErr('Gagal menghapus data');

        } catch (\Exception $e) {
            log_message('error', '[Ketentuanbpr::hapus] ' . $e->getMessage());
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

            $keterangan = $this->ketentuanbprModel->getApprovalStatus($kodebpr, $periodeId);

            return $this->jsonOk('OK', $keterangan);

        } catch (\Exception $e) {
            log_message('error', '[Ketentuanbpr::getApprovalStatus] ' . $e->getMessage());
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

            $existing = $this->ketentuanbprModel
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->first();

            if (!$existing)
                return $this->jsonErr('Data tidak ditemukan');

            if ($this->ketentuanbprModel->approveKomRow($id, $kodebpr, $periodeId, $this->userId)) {
                return $this->jsonOk('Data berhasil disetujui (Direktur yang membawahkan fungsi kepatuhan)');
            }

            return $this->jsonErr('Gagal menyimpan approval');

        } catch (\Exception $e) {
            log_message('error', '[Ketentuanbpr::approveKomRow] ' . $e->getMessage());
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

            $existing = $this->ketentuanbprModel
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->first();

            if (!$existing)
                return $this->jsonErr('Data tidak ditemukan');

            if ($this->ketentuanbprModel->unapproveKomRow($id, $kodebpr, $periodeId, $this->userId)) {
                return $this->jsonOk('Approval Direktur Yang Membawahi Fungsi Kepatuhan dibatalkan');
            }

            return $this->jsonErr('Gagal membatalkan approval');

        } catch (\Exception $e) {
            log_message('error', '[Ketentuanbpr::unapproveKomRow] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function approveDirRow()
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

            $existing = $this->ketentuanbprModel
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->first();

            if (!$existing)
                return $this->jsonErr('Data tidak ditemukan');

            if ((int) $existing['accdir2'] !== 1) {
                return $this->jsonErr('Direktur Yang Membawahi Fungsi Kepatuhan harus menyetujui data ini terlebih dahulu');
            }

            if ($this->ketentuanbprModel->approveDirRow($id, $kodebpr, $periodeId, $this->userId)) {
                return $this->jsonOk('Data berhasil disetujui (Direktur Utama)');
            }

            return $this->jsonErr('Gagal menyimpan approval');

        } catch (\Exception $e) {
            log_message('error', '[Ketentuanbpr::approveDirRow] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function unapproveDirRow()
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

            $existing = $this->ketentuanbprModel
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->first();

            if (!$existing)
                return $this->jsonErr('Data tidak ditemukan');

            if ($this->ketentuanbprModel->unapproveDirRow($id, $kodebpr, $periodeId, $this->userId)) {
                return $this->jsonOk('Approval Direktur dibatalkan');
            }

            return $this->jsonErr('Gagal membatalkan approval');

        } catch (\Exception $e) {
            log_message('error', '[Ketentuanbpr::unapproveDirRow] ' . $e->getMessage());
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

            if ($this->ketentuanbprModel->approveSemuaKom($kodebpr, $periodeId, $this->userId)) {
                return $this->jsonOk('Semua data berhasil disetujui oleh Direktur yang membawahkan fungsi kepatuhan');
            }

            return $this->jsonErr('Tidak ada data yang diupdate');

        } catch (\Exception $e) {
            log_message('error', '[Ketentuanbpr::approveSemuaKom] ' . $e->getMessage());
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

            if ($this->ketentuanbprModel->unapproveSemuaKom($kodebpr, $periodeId, $this->userId)) {
                return $this->jsonOk('Semua approval Direktur Yang Membawahi Fungsi Kepatuhan berhasil dibatalkan');
            }

            return $this->jsonErr('Tidak ada data yang diupdate');

        } catch (\Exception $e) {
            log_message('error', '[Ketentuanbpr::unapproveSemuaKom] ' . $e->getMessage());
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

            if ($this->ketentuanbprModel->hasRealData($kodebpr, $periodeId)) {
                return $this->jsonErr('Tidak bisa ditandai kosong — masih ada data yang tersimpan. Hapus data terlebih dahulu.');
            }

            if ($this->ketentuanbprModel->hasNullRecord($kodebpr, $periodeId)) {
                return $this->jsonOk('Periode ini sudah ditandai tidak ada data');
            }

            $periodeDetail = $this->periodeModel->find($periodeId);
            $user = $this->userModel->find($this->userId);

            $ok = $this->ketentuanbprModel->insertNullRecord([
                'user_id' => $this->userId,
                'kodebpr' => $kodebpr,
                'periode_id' => $periodeId,
                'tahun' => $periodeDetail['tahun'] ?? date('Y'),
                'semester' => $periodeDetail['semester'] ?? '1',
                'judulketentuan' => null,
                'nomorketentuan' => null,
                'tanggalberlaku' => null,
                'keterangan' => null,
                'fullname' => $user['fullname'] ?? null,
                'accdir2' => 0,
                'is_null_record' => 1,
            ]);

            if (!$ok)
                return $this->jsonErr('Gagal menyimpan status');

            return $this->jsonOk('Periode berhasil ditandai tidak ada data Ketentuan Internal Yang Dimiliki BPR');

        } catch (\Exception $e) {
            log_message('error', '[Ketentuanbpr::setNodataAjax] ' . $e->getMessage());
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

            if ($this->ketentuanbprModel->removeNullRecord($kodebpr, $periodeId)) {
                return $this->jsonOk('Status "Tidak Ada Data" berhasil dibatalkan');
            }
            return $this->jsonErr('Gagal membatalkan status');

        } catch (\Exception $e) {
            log_message('error', '[Ketentuanbpr::batalNodataAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function approveSemuaDir()
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

            $keterangan = $this->ketentuanbprModel->getApprovalStatus($kodebpr, $periodeId);
            if (!$keterangan['dekom_approved']) {
                return $this->jsonErr('Direktur Yang Membawahi Fungsi Kepatuhan harus menyetujui seluruh data terlebih dahulu');
            }

            if ($this->ketentuanbprModel->approveSemuaDir($kodebpr, $periodeId, $this->userId)) {
                return $this->jsonOk('Semua data berhasil disetujui oleh Direktur Utama');
            }

            return $this->jsonErr('Tidak ada data yang diupdate');

        } catch (\Exception $e) {
            log_message('error', '[Ketentuanbpr::approveSemuaDir] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function unapproveSemuaDir()
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

            if ($this->ketentuanbprModel->unapproveSemuaDir($kodebpr, $periodeId, $this->userId)) {
                return $this->jsonOk('Semua approval Direktur berhasil dibatalkan');
            }

            return $this->jsonErr('Tidak ada data yang diupdate');

        } catch (\Exception $e) {
            log_message('error', '[Ketentuanbpr::unapproveSemuaDir] ' . $e->getMessage());
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
            log_message('error', '[Ketentuanbpr::tambahKomentar] ' . $e->getMessage());
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

            // 1. Baca periode aktif
            $current = $this->periodeModel->find($periodeId);
            if (!$current) {
                return $this->jsonErr('Periode aktif tidak ditemukan');
            }

            $tahun = (int) $current['tahun'];
            $semRaw = strtolower(trim($current['semester']));
            $semNum = ($semRaw === 'ganjil' || $semRaw === '1') ? 1 : 2;

            // 2. Hitung semester & tahun sebelumnya
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

            // 3. Cari periode sebelumnya 
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

            // 4. Ambil data ketentuan dari periode sebelumnya
            $prevRows = $this->ketentuanbprModel
                ->getDataByKodebprAndPeriode($kodebpr, (int) $prevPeriode['id']);

            if (empty($prevRows)) {
                return $this->response->setJSON([
                    'status' => 'not_found',
                    'message' => 'Periode sebelumnya ada tetapi belum ada data Ketentuan Internal',
                ]);
            }

            // 5. Nomor ketentuan yang sudah ada di periode aktif
            $existingNomor = $this->ketentuanbprModel
                ->getNomorByPeriode($kodebpr, $periodeId);

            $semLabel = fn(int $n): string =>
                $n === 1 ? 'Semester 1 (Ganjil)' : 'Semester 2 (Genap)';

            return $this->response->setJSON([
                'status' => 'found',
                'current_label' => $semLabel($semNum) . ' ' . $tahun,
                'prev_label' => $semLabel($prevSemNum) . ' ' . $prevTahun,
                'prev_periode_id' => (int) $prevPeriode['id'],
                'rows' => $prevRows,
                'existing_nomors' => $existingNomor,
            ]);

        } catch (\Exception $e) {
            log_message('error', '[Ketentuanbpr::checkPrevPeriode] ' . $e->getMessage());
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

            if ($this->ketentuanbprModel->hasNullRecord($kodebpr, $periodeId)) {
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

            $prevRows = $this->ketentuanbprModel
                ->getDataByKodebprAndPeriode($kodebpr, (int) $prevPeriode['id']);

            if (empty($prevRows)) {
                return $this->jsonErr('Tidak ada data Ketentuan Internal pada periode sebelumnya');
            }

            $existingNomor = $this->ketentuanbprModel->getNomorByPeriode($kodebpr, $periodeId);
            $existingNomorSet = array_flip($existingNomor);

            $user = $this->userModel->find($this->userId);
            $insertBatch = [];

            foreach ($prevRows as $row) {
                if (isset($existingNomorSet[$row['nomorketentuan']])) {
                    continue;
                }

                $insertBatch[] = [
                    'user_id' => $this->userId,
                    'kodebpr' => $kodebpr,
                    'periode_id' => $periodeId,
                    'tahun' => $current['tahun'],
                    'semester' => $current['semester'],
                    'judulketentuan' => $row['judulketentuan'],
                    'nomorketentuan' => $row['nomorketentuan'],
                    'tanggalberlaku' => $row['tanggalberlaku'],
                    'keterangan' => $row['keterangan'],
                    'fullname' => $user['fullname'] ?? null,

                    'accdir2' => 0,
                    // 'accdir2_by'     => null,
                    // 'accdir2_at'     => null,
                ];
            }

            if (empty($insertBatch)) {
                return $this->jsonErr(
                    'Semua data dari periode sebelumnya sudah ada di periode aktif. Tidak ada data baru.'
                );
            }

            $this->db->transStart();
            $this->ketentuanbprModel->insertBatchSync($insertBatch);
            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return $this->jsonErr('Transaksi gagal, silakan coba kembali');
            }

            return $this->jsonOk(
                count($insertBatch) . ' data berhasil disinkronisasi dari periode sebelumnya'
            );

        } catch (\Exception $e) {
            log_message('error', '[Ketentuanbpr::syncFromPrevPeriode] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function exporttxtketentuanbpr()
    {
        if (!$this->auth->check()) {
            return redirect()->to(session('redirect_url') ?? '/login');
        }

        [$kodebpr, $periodeId] = $this->getKodebprPeriode();
        $periodeDetail = $this->periodeModel->find($periodeId);
        $dataKetentuanbpr = $this->ketentuanbprModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
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

        $output = "H01|{$kodejenis}|{$sandibpr}|{$tanggalLaporan}|TKSBPRKS|2001|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";

        foreach ($dataKetentuanbpr as $row) {
            $judulketentuan = $sanitize($row['judulketentuan'] ?? '');
            $nomorketentuan = $sanitize($row['nomorketentuan'] ?? '');
            $tanggalberlaku = '';
            $keterangan = $sanitize($row['keterangan'] ?? '');
            if (!empty($row['tanggalberlaku'])) {
                $tanggalberlaku = str_replace('-', '', $sanitize($row['tanggalberlaku']));
            }

            if (empty($judulketentuan) && empty($nomorketentuan))
                continue;

            $output .= "D01|K1400|{$judulketentuan}|{$nomorketentuan}|{$tanggalberlaku}|{$keterangan}\r\n";
        }

        $tanggalFile = str_replace('-', '', $tanggalLaporan);
        $filename = "TKSBPRKS-2001-{$jenispelaporan}-S-{$tanggalFile}-{$sandibpr}-01.txt";

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

            if ($this->ketentuanbprModel->hasNullRecord($kodebpr, $periodeId)) {
                return $this->jsonErr('Batalkan status "Tidak Ada Data" terlebih dahulu sebelum impor');
            }

            // Validasi ringan dulu sebelum menyentuh PhpSpreadsheet sama sekali
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

            // Baru buat reader — mode ringan, tanpa style/formatting
            $reader = $ext === 'xlsx' ? new XlsxReader() : new XlsReader();
            $reader->setReadDataOnly(true);

            $spreadsheet = $reader->load($file->getTempName());
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestDataRow();

            if (($highestRow - 1) > self::IMPORT_MAX_ROWS) {
                unset($spreadsheet, $sheet);
                return $this->jsonErr('Maksimal ' . self::IMPORT_MAX_ROWS . ' baris per impor. File Anda memiliki ' . ($highestRow - 1) . ' baris.');
            }

            // Ambil daftar nomor existing SEKALI SAJA (index kodebpr+periode_id sudah ada)
            $existingNomor = $this->ketentuanbprModel->getNomorByPeriode($kodebpr, $periodeId);
            $existingNomorSet = array_flip($existingNomor);
            $seenInFile = [];

            $preview = [];

            // Baca baris satu per satu, mulai dari baris 2 (skip header), berhenti di highestRow
            for ($rowIdx = 2; $rowIdx <= $highestRow; $rowIdx++) {
                $judul = trim((string) ($sheet->getCell(Coordinate::stringFromColumnIndex(1) . $rowIdx)->getValue() ?? ''));
                $nomor = trim((string) ($sheet->getCell(Coordinate::stringFromColumnIndex(2) . $rowIdx)->getValue() ?? ''));
                $tglRaw = $sheet->getCell(Coordinate::stringFromColumnIndex(3) . $rowIdx)->getValue();
                $ket = trim((string) ($sheet->getCell(Coordinate::stringFromColumnIndex(4) . $rowIdx)->getValue() ?? ''));

                if ($judul === '' && $nomor === '' && $ket === '') {
                    continue;
                }

                $rowErr = [];
                if ($judul === '')
                    $rowErr[] = 'Judul kosong';
                if ($nomor === '')
                    $rowErr[] = 'Nomor kosong';
                if ($ket === '')
                    $rowErr[] = 'Keterangan kosong';

                $tanggal = null;
                if ($tglRaw !== null && $tglRaw !== '') {
                    if (is_numeric($tglRaw)) {
                        try {
                            $tanggal = ExcelDate::excelToDateTimeObject($tglRaw)->format('Y-m-d');
                        } catch (\Exception $e) {
                            $rowErr[] = 'Format tanggal tidak valid';
                        }
                    } else {
                        $ts = strtotime(str_replace('/', '-', trim((string) $tglRaw)));
                        $tanggal = $ts ? date('Y-m-d', $ts) : null;
                        if (!$tanggal)
                            $rowErr[] = 'Format tanggal tidak valid';
                    }
                } else {
                    $rowErr[] = 'Tanggal kosong';
                }

                $placeholders = ['-', '—', 'n/a', 'na', ''];
                $isPlaceholder = in_array(strtolower($nomor), $placeholders, true);

                $isDupDb = !$isPlaceholder && isset($existingNomorSet[$nomor]);
                $isDupFile = !$isPlaceholder && isset($seenInFile[$nomor]);
                if ($nomor !== '' && !$isPlaceholder) {
                    $seenInFile[$nomor] = true;
                }

                $status = 'ok';
                if (!empty($rowErr))
                    $status = 'error';
                elseif ($isDupDb || $isDupFile)
                    $status = 'skip';

                $preview[] = [
                    'line' => $rowIdx,
                    'judulketentuan' => $judul,
                    'nomorketentuan' => $nomor,
                    'tanggalberlaku' => $tanggal,
                    'keterangan' => $ket,
                    'status' => $status,
                    'errors' => $rowErr,
                    'dup_reason' => $isDupDb ? 'Sudah ada di periode ini' : ($isDupFile ? 'Duplikat dalam file' : null),
                ];
            }

            // Lepas memori PhpSpreadsheet secepat mungkin
            unset($sheet, $spreadsheet, $reader);

            if (empty($preview)) {
                return $this->jsonErr('Tidak ada baris data yang terbaca dari file');
            }

            $okCount = count(array_filter($preview, static fn($p) => $p['status'] === 'ok'));

            return $this->jsonOk('OK', [
                'preview' => $preview,
                'ok_count' => $okCount,
                'total' => count($preview),
            ]);

        } catch (\Exception $e) {
            log_message('error', '[Ketentuanbpr::previewImportExcel] ' . $e->getMessage());
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

            if ($this->ketentuanbprModel->hasNullRecord($kodebpr, $periodeId)) {
                return $this->jsonErr('Batalkan status "Tidak Ada Data" terlebih dahulu sebelum impor');
            }

            $rows = json_decode($this->request->getPost('rows') ?? '[]', true);
            if (!is_array($rows) || empty($rows)) {
                return $this->jsonErr('Tidak ada data valid untuk disimpan');
            }
            if (count($rows) > self::IMPORT_MAX_ROWS) {
                return $this->jsonErr('Jumlah baris melebihi batas maksimal');
            }

            $placeholders = ['-', '—', 'n/a', 'na', ''];

            $existingNomor = $this->ketentuanbprModel->getNomorByPeriode($kodebpr, $periodeId);
            $existingNomorSet = array_flip($existingNomor);

            $insertBatch = [];
            $seenInFile = [];

            foreach ($rows as $r) {
                $judul = trim((string) ($r['judulketentuan'] ?? ''));
                $nomor = trim((string) ($r['nomorketentuan'] ?? ''));
                $tgl = $r['tanggalberlaku'] ?? null;
                $ket = trim((string) ($r['keterangan'] ?? ''));

                if ($judul === '' || $nomor === '' || $ket === '' || !$tgl)
                    continue;

                $isPlaceholder = in_array(strtolower($nomor), $placeholders, true);

                if (!$isPlaceholder) {
                    if (isset($existingNomorSet[$nomor]) || isset($seenInFile[$nomor]))
                        continue;
                    $seenInFile[$nomor] = true;
                }

                $insertBatch[] = [
                    'user_id' => $this->userId,
                    'kodebpr' => $kodebpr,
                    'periode_id' => $periodeId,
                    'tahun' => $periodeDetail['tahun'] ?? date('Y'),
                    'semester' => $periodeDetail['semester'] ?? '1',
                    'judulketentuan' => $judul,
                    'nomorketentuan' => $nomor,
                    'tanggalberlaku' => $tgl,
                    'keterangan' => $ket,
                    'fullname' => $user['fullname'] ?? null,
                    'accdir2' => 0,
                ];
            }

            if (empty($insertBatch)) {
                return $this->jsonErr('Tidak ada data baru yang dapat disimpan (semua duplikat/kosong)');
            }

            $this->db->transStart();
            $this->ketentuanbprModel->insertBatchSync($insertBatch);
            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return $this->jsonErr('Transaksi gagal, silakan coba kembali');
            }

            return $this->jsonOk(count($insertBatch) . ' data berhasil diimpor dari Excel');

        } catch (\Exception $e) {
            log_message('error', '[Ketentuanbpr::confirmImportExcel] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }
}