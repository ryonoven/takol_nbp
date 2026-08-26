<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_pemantauanaudit;
use App\Models\M_user;
use App\Models\M_infobpr;
use App\Models\M_periodesemesteran;
use App\Models\M_semesterancomments;
use App\Models\M_semesterancommentsread;
use Myth\Auth\Config\Services as AuthServices;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Reader\Xls as XlsReader;

class Pemantauanaudit extends Controller
{
    protected $db;
    protected $auth;
    protected $session;

    protected $pemantauanauditModel;
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
    const SUBKATEGORI = 'PEMANTAUANAUDIT';

    const STATUS_OPTIONS = [
        '01' => 'Selesai',
        '02' => 'Proses',
        '03' => 'Belum Ditindaklanjuti',
    ];

    private const IMPORT_MAX_ROWS = 200;
    private const IMPORT_MAX_SIZE = 3 * 1024 * 1024;

    private function resolveStatusCode(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') return null;

        // Already a valid code
        if (array_key_exists($raw, self::STATUS_OPTIONS)) {
            return $raw;
        }

        // Match against label text, case-insensitive
        $rawLower = mb_strtolower($raw);
        foreach (self::STATUS_OPTIONS as $code => $label) {
            if (mb_strtolower($label) === $rawLower) {
                return $code;
            }
        }

        return null;
    }

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');

        $this->db = \Config\Database::connect();
        $this->session = service('session');
        $this->auth = service('authentication');

        $this->pemantauanauditModel = new M_pemantauanaudit();
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

            $groupPropMap = [
                'pe' => 'userInGroupPE',
                'admin' => 'userInGroupAdmin',
                'dekom' => 'userInGroupDekom',
                'dekom2' => 'userInGroupDekom2',
                'dekom3' => 'userInGroupDekom3',
                'dekom4' => 'userInGroupDekom4',
                'dekom5' => 'userInGroupDekom5',
                'direksi' => 'userInGroupDireksi',
                'direksi2' => 'userInGroupDireksi2',
                'auditor' => 'userInGroupAuditor',
            ];

            foreach ($groupPropMap as $g => $prop) {
                $this->$prop = $authorize->inGroup($g, $this->userId);
            }
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

    private function guard(string ...$groups)
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        if (!empty($groups)) {
            foreach ($groups as $g) {
                $prop = 'userInGroup' . ucfirst($g);
                if ($this->$prop ?? false)
                    return null;
            }
            return $this->jsonErr('Tidak memiliki akses', 403);
        }

        return null;
    }

    /* Helpers */
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

    /* Index */
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
            'judul' => 'Hasil Pemantauan Komitmen Auditee',
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
            'userInGroupAuditor' => $this->userInGroupAuditor
        ];

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('Laporansemester/Laporanaudit/pemantauanaudit', $data);
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

            $allRows = $this->pemantauanauditModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);

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
            log_message('error', '[PemantauanauditModel::getData] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function tambah()
    {
        if ($err = $this->guard())
            return $err;

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            if (!$kodebpr || !$periodeId) {
                return $this->jsonErr('Kode BPR atau Periode tidak valid');
            }

            if ($this->pemantauanauditModel->hasNullRecord($kodebpr, $periodeId)) {
                return $this->jsonErr('Batalkan status "Tidak Ada Data" terlebih dahulu sebelum menambah data');
            }

            $realisasi = trim($this->request->getPost('realisasi'));
            $hasil = trim($this->request->getPost('hasil'));
            $status = trim($this->request->getPost('status'));

            if (empty($realisasi) || empty($hasil) || empty($status)) {
                return $this->jsonErr('Semua field wajib diisi');
            }

            if (!array_key_exists($status, self::STATUS_OPTIONS)) {
                return $this->jsonErr('Nilai status tidak valid');
            }

            $periodeDetail = $this->periodeModel->find($periodeId);
            $user = $this->userModel->find($this->userId);

            $data = [
                'user_id' => $this->userId,
                'kodebpr' => $kodebpr,
                'periode_id' => $periodeId,
                'tahun' => $periodeDetail['tahun'] ?? date('Y'),
                'semester' => $periodeDetail['semester'] ?? '1',
                'realisasi' => $realisasi,
                'hasil' => $hasil,
                'status' => $status,
                'fullname' => $user['fullname'] ?? null,
                'accdir2' => 0,
            ];

            if ($this->pemantauanauditModel->tambah($data)) {
                return $this->jsonOk('Data berhasil ditambahkan');
            }

            return $this->jsonErr('Gagal menambahkan data');
        } catch (\Exception $e) {
            log_message('error', '[Pemantauanaudit::tambah] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function ubah()
    {
        if ($err = $this->guard())
            return $err;

        try {
            $id = (int) $this->request->getPost('id');
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            if (!$id || !$kodebpr || !$periodeId) {
                return $this->jsonErr('Data tidak valid');
            }

            $existing = $this->pemantauanauditModel
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->first();

            if (!$existing) {
                return $this->jsonErr('Data tidak ditemukan atau tidak memiliki akses');
            }

            $realisasi = trim($this->request->getPost('realisasi'));
            $hasil = trim($this->request->getPost('hasil'));
            $status = trim($this->request->getPost('status'));

            if (empty($realisasi) || empty($hasil) || empty($status)) {
                return $this->jsonErr('Semua field wajib diisi');
            }

            if (!array_key_exists($status, self::STATUS_OPTIONS)) {
                return $this->jsonErr('Nilai status tidak valid');
            }

            $data = [
                'realisasi' => $realisasi,
                'hasil' => $hasil,
                'status' => $status,
                'accdir2' => 0,
            ];

            if ($this->pemantauanauditModel->ubah($data, $id, $kodebpr, $periodeId)) {
                return $this->jsonOk('Data berhasil diubah');
            }

            return $this->jsonErr('Gagal mengubah data');
        } catch (\Exception $e) {
            log_message('error', '[Pemantauanaudit::ubah] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function hapus()
    {
        if ($err = $this->guard())
            return $err;

        try {
            $id = (int) $this->request->getPost('id');
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            if (!$id || !$kodebpr || !$periodeId) {
                return $this->jsonErr('Data tidak valid');
            }

            $existing = $this->pemantauanauditModel
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->first();

            if (!$existing) {
                return $this->jsonErr('Data tidak ditemukan atau tidak memiliki akses');
            }

            if ($this->pemantauanauditModel->hapusSpesifik($id, $kodebpr, $periodeId)) {
                return $this->jsonOk('Data berhasil dihapus');
            }

            return $this->jsonErr('Gagal menghapus data');
        } catch (\Exception $e) {
            log_message('error', '[Pemantauanaudit::hapus] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function getApprovalStatus()
    {
        if ($err = $this->guard())
            return $err;

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            if (!$kodebpr || !$periodeId) {
                return $this->jsonErr('Data tidak lengkap');
            }

            $keterangan = $this->pemantauanauditModel->getApprovalStatus($kodebpr, $periodeId);

            return $this->jsonOk('OK', $keterangan);
        } catch (\Exception $e) {
            log_message('error', '[Pemantauanaudit::getApprovalStatus] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function approveKomRow()
    {
        if ($err = $this->guard())
            return $err;

        try {
            $id = (int) $this->request->getPost('id');
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            $existing = $this->pemantauanauditModel
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->first();

            if (!$existing)
                return $this->jsonErr('Data tidak ditemukan');

            if ($this->pemantauanauditModel->approveKomRow($id, $kodebpr, $periodeId, $this->userId)) {
                return $this->jsonOk('Data berhasil disetujui (Direktur Utama)');
            }

            return $this->jsonErr('Gagal menyimpan approval');
        } catch (\Exception $e) {
            log_message('error', '[Pemantauanaudit::approveKomRow] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function unapproveKomRow()
    {
        if ($err = $this->guard())
            return $err;

        try {
            $id = (int) $this->request->getPost('id');
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            $existing = $this->pemantauanauditModel
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->first();

            if (!$existing)
                return $this->jsonErr('Data tidak ditemukan');

            if ($this->pemantauanauditModel->unapproveKomRow($id, $kodebpr, $periodeId, $this->userId)) {
                return $this->jsonOk('Approval Direktur Utama dibatalkan');
            }

            return $this->jsonErr('Gagal membatalkan approval');
        } catch (\Exception $e) {
            log_message('error', '[Pemantauanaudit::unapproveKomRow] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function approveSemuaKom()
    {
        if ($err = $this->guard())
            return $err;

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            if (!$kodebpr || !$periodeId)
                return $this->jsonErr('Data tidak valid');

            if ($this->pemantauanauditModel->approveSemuaKom($kodebpr, $periodeId, $this->userId)) {
                return $this->jsonOk('Semua data berhasil disetujui oleh Direktur Utama');
            }

            return $this->jsonErr('Tidak ada data yang diupdate');
        } catch (\Exception $e) {
            log_message('error', '[Pemantauanaudit::approveSemuaKom] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function unapproveSemuaKom()
    {
        if ($err = $this->guard())
            return $err;

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            if (!$kodebpr || !$periodeId)
                return $this->jsonErr('Data tidak valid');

            if ($this->pemantauanauditModel->unapproveSemuaKom($kodebpr, $periodeId, $this->userId)) {
                return $this->jsonOk('Semua approval Direktur Utama berhasil dibatalkan');
            }

            return $this->jsonErr('Tidak ada data yang diupdate');
        } catch (\Exception $e) {
            log_message('error', '[Pemantauanaudit::unapproveSemuaKom] ' . $e->getMessage());
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

            if ($this->pemantauanauditModel->hasRealData($kodebpr, $periodeId)) {
                return $this->jsonErr('Tidak bisa ditandai kosong — masih ada data yang tersimpan. Hapus data terlebih dahulu.');
            }

            if ($this->pemantauanauditModel->hasNullRecord($kodebpr, $periodeId)) {
                return $this->jsonOk('Periode ini sudah ditandai tidak ada data');
            }

            $periodeDetail = $this->periodeModel->find($periodeId);
            $user = $this->userModel->find($this->userId);

            $ok = $this->pemantauanauditModel->insertNullRecord([
                'user_id' => $this->userId,
                'kodebpr' => $kodebpr,
                'periode_id' => $periodeId,
                'tahun' => $periodeDetail['tahun'] ?? date('Y'),
                'semester' => $periodeDetail['semester'] ?? '1',
                'realisasi' => null,
                'hasil' => null,
                'status' => null,
                'fullname' => $user['fullname'] ?? null,
                'accdir2' => 0,
                'is_null_record' => 1,
            ]);

            if (!$ok)
                return $this->jsonErr('Gagal menyimpan status');

            return $this->jsonOk('Periode berhasil ditandai tidak ada data Hasil Pemantauan Komitmen Auditee');

        } catch (\Exception $e) {
            log_message('error', '[Pemantauanaudit::setNodataAjax] ' . $e->getMessage());
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

            if ($this->pemantauanauditModel->removeNullRecord($kodebpr, $periodeId)) {
                return $this->jsonOk('Status "Tidak Ada Data" berhasil dibatalkan');
            }
            return $this->jsonErr('Gagal membatalkan status');

        } catch (\Exception $e) {
            log_message('error', '[Pemantauanaudit::batalNodataAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    /* Komentar */
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
                return $this->response->setJSON(['status' => 'success', 'message' => 'Komentar berhasil ditambahkan']);
            }

            throw new \Exception('Gagal menyimpan komentar');
        } catch (\Exception $e) {
            log_message('error', '[Pemantauanaudit::tambahKomentar] ' . $e->getMessage());
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menambahkan komentar']);
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
        if ($err = $this->guard())
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

            [$prevSemNum, $prevTahun] = $semNum === 1 ? [2, $tahun - 1] : [1, $tahun];

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

            $prevRows = $this->pemantauanauditModel
                ->getDataByKodebprAndPeriode($kodebpr, (int) $prevPeriode['id']);

            if (empty($prevRows)) {
                return $this->response->setJSON([
                    'status' => 'not_found',
                    'message' => 'Periode sebelumnya ada tetapi belum ada data Hasil Pemantauan Komitmen Auditee',
                ]);
            }

            $existingKeys = $this->pemantauanauditModel
                ->getPemantauanKeysByPeriode($kodebpr, $periodeId);

            $semLabel = fn(int $n): string => $n === 1
                ? 'Semester 1 (Ganjil)' : 'Semester 2 (Genap)';

            return $this->response->setJSON([
                'status' => 'found',
                'current_label' => $semLabel($semNum) . ' ' . $tahun,
                'prev_label' => $semLabel($prevSemNum) . ' ' . $prevTahun,
                'prev_periode_id' => (int) $prevPeriode['id'],
                'rows' => $prevRows,
                'existing_keys' => $existingKeys,
            ]);

        } catch (\Exception $e) {
            log_message('error', '[Pemantauanaudit::checkPrevPeriode] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function syncFromPrevPeriode()
    {
        if ($err = $this->guard())
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

            if ($this->pemantauanauditModel->hasNullRecord($kodebpr, $periodeId)) {
                return $this->jsonErr('Batalkan status "Tidak Ada Data" terlebih dahulu sebelum sinkronisasi');
            }

            $tahun = (int) $current['tahun'];
            $semRaw = strtolower(trim($current['semester']));
            $semNum = ($semRaw === 'ganjil' || $semRaw === '1') ? 1 : 2;

            [$prevSemNum, $prevTahun] = $semNum === 1 ? [2, $tahun - 1] : [1, $tahun];

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

            $prevRows = $this->pemantauanauditModel
                ->getDataByKodebprAndPeriode($kodebpr, (int) $prevPeriode['id']);

            if (empty($prevRows)) {
                return $this->jsonErr('Tidak ada data Hasil Pemantauan Komitmen Auditee pada periode sebelumnya');
            }

            $existingKeys = $this->pemantauanauditModel->getPemantauanKeysByPeriode($kodebpr, $periodeId);
            $existingKeySet = array_flip($existingKeys);

            $user = $this->userModel->select('fullname')->find($this->userId);
            $insertBatch = [];

            foreach ($prevRows as $row) {
                $compositeKey = mb_substr($row['realisasi'] ?? '', 0, 100)
                    . '||'
                    . mb_substr($row['hasil'] ?? '', 0, 100);

                if (isset($existingKeySet[$compositeKey])) {
                    continue;
                }

                $insertBatch[] = [
                    'user_id' => $this->userId,
                    'kodebpr' => $kodebpr,
                    'periode_id' => $periodeId,
                    'tahun' => $current['tahun'],
                    'semester' => $current['semester'],
                    'realisasi' => $row['realisasi'],
                    'hasil' => $row['hasil'],
                    'status' => $row['status'],
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
            $this->pemantauanauditModel->insertBatchSync($insertBatch);
            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return $this->jsonErr('Transaksi gagal, silakan coba kembali');
            }

            return $this->jsonOk(
                count($insertBatch) . ' data berhasil disinkronisasi dari periode sebelumnya'
            );

        } catch (\Exception $e) {
            log_message('error', '[Pemantauanaudit::syncFromPrevPeriode] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function exporttxtpemantauanaudit()
    {
        if (!$this->auth->check()) {
            return redirect()->to(session('redirect_url') ?? '/login');
        }

        [$kodebpr, $periodeId] = $this->getKodebprPeriode();

        $periodeDetail = $this->periodeModel
            ->select('tahun, semester, modalinti, kategori, jenispelaporan, nomorsurat, jeniskoreksi')
            ->find($periodeId);

        $dataRows = $this->pemantauanauditModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
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

        $output = "H01|{$kodejenis}|{$sandibpr}|{$tanggalLaporan}|TKSBPRKS|3004|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";

        foreach ($dataRows as $row) {
            $realisasi = $sanitize((string) ($row['realisasi'] ?? ''));
            $hasil = $sanitize((string) ($row['hasil'] ?? ''));
            $status = $sanitize((string) ($row['status'] ?? ''));
            if ($realisasi === '' && $hasil === '' && $status === '')
                continue;
            $output .= "D01|A2300|{$realisasi}|{$hasil}|{$status}\r\n";
        }

        $tanggalFile = str_replace('-', '', $tanggalLaporan);
        $filename = "TKSBPRKS-3004-{$jenispelaporan}-S-{$tanggalFile}-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Cache-Control', 'no-store')
            ->setBody($output);
    }

    public function previewImportExcel()
    {
        if ($err = $this->guard())
            return $err;

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();
            if (!$kodebpr || !$periodeId) {
                return $this->jsonErr('Kode BPR atau periode tidak valid');
            }

            if ($this->pemantauanauditModel->hasNullRecord($kodebpr, $periodeId)) {
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
                $realisasi = trim((string) ($sheet->getCell('A' . $rowIdx)->getValue() ?? ''));
                $hasil     = trim((string) ($sheet->getCell('B' . $rowIdx)->getValue() ?? ''));
                $statusRaw = trim((string) ($sheet->getCell('C' . $rowIdx)->getValue() ?? ''));

                if ($realisasi === '' && $hasil === '' && $statusRaw === '') {
                    continue; // baris kosong, skip diam-diam
                }

                $statusCode = $statusRaw !== '' ? $this->resolveStatusCode($statusRaw) : null;

                $rowErr = [];
                if ($realisasi === '') $rowErr[] = 'Realisasi Komitmen Auditee kosong';
                if ($hasil === '')     $rowErr[] = 'Hasil Pemantauan Komitmen kosong';
                if ($statusRaw === '') {
                    $rowErr[] = 'Status Realisasi Komitmen Auditee kosong';
                } elseif (!$statusCode) {
                    $rowErr[] = 'Status "' . $statusRaw . '" tidak dikenali (gunakan: ' . implode(', ', self::STATUS_OPTIONS) . ')';
                }

                $preview[] = [
                    'line'       => $rowIdx,
                    'realisasi'  => $realisasi,
                    'hasil'      => $hasil,
                    'status'     => $statusCode,
                    'status_raw' => $statusRaw,
                    'row_status' => empty($rowErr) ? 'ok' : 'error',
                    'errors'     => $rowErr,
                ];
            }

            unset($sheet, $spreadsheet, $reader);

            if (empty($preview)) {
                return $this->jsonErr('Tidak ada baris data yang terbaca dari file');
            }

            $okCount = count(array_filter($preview, static fn($p) => $p['row_status'] === 'ok'));

            return $this->jsonOk('OK', [
                'preview'  => $preview,
                'ok_count' => $okCount,
                'total'    => count($preview),
                'status_options' => self::STATUS_OPTIONS,
            ]);

        } catch (\Exception $e) {
            log_message('error', '[Pemantauanaudit::previewImportExcel] ' . $e->getMessage());
            return $this->jsonErr('Gagal membaca file Excel. Pastikan format sesuai template.', 500);
        }
    }

    public function confirmImportExcel()
    {
        if ($err = $this->guard())
            return $err;

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();
            if (!$kodebpr || !$periodeId) {
                return $this->jsonErr('Data tidak valid');
            }

            if ($this->pemantauanauditModel->hasNullRecord($kodebpr, $periodeId)) {
                return $this->jsonErr('Batalkan status "Tidak Ada Data" terlebih dahulu sebelum impor');
            }

            $rows = json_decode($this->request->getPost('rows') ?? '[]', true);
            if (!is_array($rows) || empty($rows)) {
                return $this->jsonErr('Tidak ada data valid untuk disimpan');
            }
            if (count($rows) > self::IMPORT_MAX_ROWS) {
                return $this->jsonErr('Jumlah baris melebihi batas maksimal');
            }

            $periodeDetail = $this->periodeModel->find($periodeId);
            $user = $this->userModel->find($this->userId);

            $insertBatch = [];
            foreach ($rows as $r) {
                $realisasi = trim((string) ($r['realisasi'] ?? ''));
                $hasil     = trim((string) ($r['hasil']     ?? ''));
                $status    = trim((string) ($r['status']    ?? ''));

                if ($realisasi === '' || $hasil === '' || !array_key_exists($status, self::STATUS_OPTIONS)) {
                    continue;
                }

                $insertBatch[] = [
                    'user_id'    => $this->userId,
                    'kodebpr'    => $kodebpr,
                    'periode_id' => $periodeId,
                    'tahun'      => $periodeDetail['tahun'] ?? date('Y'),
                    'semester'   => $periodeDetail['semester'] ?? '1',
                    'realisasi'  => $realisasi,
                    'hasil'      => $hasil,
                    'status'     => $status,
                    'fullname'   => $user['fullname'] ?? null,
                    'accdir2'    => 0,
                ];
            }

            if (empty($insertBatch)) {
                return $this->jsonErr('Tidak ada data baru yang dapat disimpan (semua kosong atau status tidak valid)');
            }

            $this->db->transStart();
            $this->pemantauanauditModel->insertBatchSync($insertBatch);
            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return $this->jsonErr('Transaksi gagal, silakan coba kembali');
            }

            return $this->jsonOk(count($insertBatch) . ' data berhasil diimpor dari Excel');

        } catch (\Exception $e) {
            log_message('error', '[Pemantauanaudit::confirmImportExcel] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }
}