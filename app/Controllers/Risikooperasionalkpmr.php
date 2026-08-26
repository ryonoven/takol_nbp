<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_paramprofilrisiko;
use App\Models\M_user;
use App\Models\M_profilrisikocomments;
use App\Models\M_risikooperasionalkpmr;
use App\Models\M_infobpr;
use App\Models\M_periodesemesteran;
use App\Models\M_profilrisikocommentsread;
use App\Models\M_showprofilresiko;
use App\Models\M_showtks;
use App\Models\M_periodetks;
use Myth\Auth\Config\Services as AuthServices;

class Risikooperasionalkpmr extends Controller
{
    protected $db;
    protected $auth;
    protected $paramprofilrisikoModel;
    protected $userModel;
    protected $komentarModel;
    protected $nilaiModel;
    protected $infobprModel;
    protected $periodeModel;
    protected $commentReadsModel;
    protected $session;
    protected $userKodebpr;
    protected $userId;
    protected $periodeId;
    protected $showtksModel;
    protected $showprofilresikoModel;
    protected $periodetksModel;
    protected $userGroups = [];

    private static ?array $_penilaianConfigCache = null;

    private const CATEGORY_MAPPING = [
        51 => [52, 53, 54, 55, 56, 57],
        58 => [59, 60, 61],
        62 => [63, 64, 65, 66],
        67 => [68, 69],
    ];

    private const KODE_MAP = [
        51 => '2310',
        52 => '2311',
        53 => '2312',
        54 => '2313',
        55 => '2314',
        56 => '2315',
        57 => '2316',
        58 => '2320',
        59 => '2321',
        60 => '2322',
        61 => '2323',
        62 => '2330',
        63 => '2331',
        64 => '2332',
        65 => '2333',
        66 => '2334',
        67 => '2340',
        68 => '2341',
        69 => '2342',
        70 => '2350',
    ];

    private const PENJELASAN_MAP = [
        '1' => 'Tingkat Risiko Operasional KPMR: Sangat Rendah',
        '2' => 'Tingkat Risiko Operasional KPMR: Rendah',
        '3' => 'Tingkat Risiko Operasional KPMR: Sedang',
        '4' => 'Tingkat Risiko Operasional KPMR: Tinggi',
        '5' => 'Tingkat Risiko Operasional KPMR: Sangat Tinggi',
    ];

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');

        $this->db = \Config\Database::connect();
        $this->paramprofilrisikoModel = new M_paramprofilrisiko();
        $this->periodeModel = new M_periodesemesteran();
        $this->userModel = new M_user();
        $this->komentarModel = new M_profilrisikocomments();
        $this->nilaiModel = new M_risikooperasionalkpmr();
        $this->infobprModel = new M_infobpr();
        $this->commentReadsModel = new M_profilrisikocommentsread();
        $this->showprofilresikoModel = new M_showprofilresiko();
        $this->showtksModel = new M_showtks();
        $this->periodetksModel = new M_periodetks();

        helper('url');
        $this->session = service('session');
        $this->auth = service('authentication');
        $this->userId = $this->auth->id();

        if ($this->userId) {
            $user = $this->userModel->find($this->userId);
            $this->userKodebpr = $user['kodebpr'] ?? null;
        }

        $this->periodeId = session('active_periode');
        $this->initializeUserGroups();
    }

    private function initializeUserGroups(): void
    {
        if (!$this->userId)
            return;

        $authorize = AuthServices::authorization();
        $groups = ['pe', 'admin', 'dekom', 'dekom2', 'dekom3', 'dekom4', 'dekom5', 'direksi', 'direksi2'];
        foreach ($groups as $group) {
            $this->userGroups[$group] = $authorize->inGroup($group, $this->userId);
        }
    }

    private function checkAuth()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }
        return null;
    }

    private function validatePeriode()
    {
        if (!$this->periodeId) {
            return redirect()->to('/periodeprofilresiko');
        }
        return null;
    }

    public function index()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;
        if ($redirect = $this->validatePeriode())
            return $redirect;

        if (!$this->userKodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
        }

        $periodeDetail = $this->periodeModel->find($this->periodeId);
        $bprData = $this->infobprModel->where('kodebpr', $this->userKodebpr)->first();

        $nilaiRows = $this->nilaiModel
            ->whereIn('faktor1id', [70, 71])
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->findAll();

        $nilai13 = null;
        $nilai14 = null;
        foreach ($nilaiRows as $row) {
            if ($row['faktor1id'] == 70)
                $nilai13 = $row;
            if ($row['faktor1id'] == 71)
                $nilai14 = $row;
        }

        $approvalData = $this->db->table('risikooperasional_kpmr')
            ->select('
                COUNT(CASE WHEN penilaiankredit IS NOT NULL THEN 1 END) AS filled_count,
                SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END)        AS approved_count
            ')
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->whereIn('faktor1id', range(51, 70))
            ->get()->getRowArray();

        $allFilled = ((int) ($approvalData['filled_count'] ?? 0) === 20);
        $allApproved = ($allFilled && (int) ($approvalData['approved_count'] ?? 0) === 20);
        $canApprove = $allFilled;

        $user = $this->userModel->find($this->userId);

        $data = [
            'judul' => 'Penilaian Risiko Operasional KPMR',
            'userId' => $this->userId,
            'userInGroupPE' => $this->userGroups['pe'],
            'userInGroupAdmin' => $this->userGroups['admin'],
            'userInGroupDekom' => $this->userGroups['dekom'],
            'userInGroupDekom2' => $this->userGroups['dekom2'],
            'userInGroupDekom3' => $this->userGroups['dekom3'],
            'userInGroupDekom4' => $this->userGroups['dekom4'],
            'userInGroupDekom5' => $this->userGroups['dekom5'],
            'userInGroupDireksi' => $this->userGroups['direksi'],
            'userInGroupDireksi2' => $this->userGroups['direksi2'],
            'fullname' => $user['fullname'] ?? 'Unknown',
            'canApprove' => $canApprove,
            'allApproved' => $allApproved,
            'kodebpr' => $this->userKodebpr,
            'periodeId' => $this->periodeId,
            'periodeDetail' => $periodeDetail,
            'bprData' => $bprData,
            'nilai13' => $nilai13,
            'nilai14' => $nilai14,
            'penilaianConfig' => $this->getPenilaianConfig(),
            'loadDataViaAjax' => true,
        ];

        return view('templates/v_header', $data)
            . view('templates/v_sidebar')
            . view('templates/v_topbar')
            . view('Laporansemester/Profilrisiko/opskpmr', $data)
            . view('templates/v_footer');
    }

    public function getFactorsData()
    {
        if (!$this->request->isAJAX())
            return $this->response->setStatusCode(403);
        $nilaiRows = $this->db->table('risikooperasional_kpmr AS r')
            ->select('r.*, u.fullname')
            ->join('users AS u', 'u.id = r.user_id', 'left')
            ->where('r.kodebpr', $this->userKodebpr)
            ->where('r.periode_id', $this->periodeId)
            ->whereIn('r.faktor1id', range(51, 69))
            ->get()->getResultArray();

        $nilaiLookup = [];
        foreach ($nilaiRows as $row) {
            $nilaiLookup[$row['faktor1id']] = $row;
        }

        $structure = $this->buildStructure($nilaiLookup);

        return $this->response->setJSON([
            'status' => 'success',
            'data' => $structure,
        ]);
    }

    public function getAllUnreadCounts()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }
        if (!$this->userKodebpr || !$this->userId || !$this->periodeId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Missing data']);
        }

        $faktorIds = range(51, 70);

        $hasComments = $this->db->table('profilrisiko_comments')
            ->select('faktor1id')
            ->where('subkategori', 'OPERASIONALKPMR')
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->whereIn('faktor1id', $faktorIds)
            ->groupBy('faktor1id')
            ->get()->getResultArray();

        if (empty($hasComments)) {
            return $this->response->setJSON(['status' => 'success', 'counts' => []]);
        }

        $activeFaktorIds = array_column($hasComments, 'faktor1id');
        $counts = [];

        foreach ($activeFaktorIds as $faktorId) {
            $count = $this->commentReadsModel->countUnreadCommentsForUserByFactor(
                $faktorId,
                'OPERASIONALKPMR',
                $this->userKodebpr,
                $this->userId,
                $this->periodeId
            );
            if ($count > 0) {
                $counts[$faktorId] = $count;
            }
        }

        return $this->response->setJSON(['status' => 'success', 'counts' => $counts]);
    }

    public function markUserCommentsAsRead()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $faktorId = $this->request->getPost('faktor_id');
        if (!$faktorId || !$this->userKodebpr || !$this->userId || !$this->periodeId) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Missing data.']);
        }

        $marked = $this->commentReadsModel->markAllAsReadForFactor(
            $faktorId,
            'OPERASIONALKPMR',
            $this->userKodebpr,
            $this->userId,
            $this->periodeId
        );

        return $this->response->setJSON(['status' => 'success', 'message' => 'Comments marked as read', 'marked_count' => $marked]);
    }

    public function getKomentarByFaktorId($faktorId)
    {
        if (!$this->request->isAJAX() || !is_numeric($faktorId)) {
            return $this->response->setStatusCode(404)->setBody('Not Found');
        }

        $komentarList = $this->komentarModel->getKomentarByFaktorId(
            'OPERASIONALKPMR',
            $faktorId,
            $this->userKodebpr,
            $this->periodeId
        );

        return $this->response->setJSON($komentarList);
    }

    public function ubah()
    {
        if (!$this->request->isAJAX())
            return redirect()->back()->with('error', 'Invalid request');

        $faktor1id = (int) $this->request->getPost('faktor1id');
        if (!$faktor1id || !$this->userKodebpr || !$this->periodeId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak valid']);
        }

        $data = [
            'penilaiankredit' => $this->request->getPost('penilaiankredit'),
            'penjelasanpenilaian' => $this->request->getPost('penjelasanpenilaian'),
            'keterangan' => $this->request->getPost('keterangan'),
            'is_approved' => 0,
            'accdir2' => 0,
            'user_id' => $this->userId,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        try {
            $this->db->table('risikooperasional_kpmr')
                ->where('faktor1id', $faktor1id)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update($data);

            $this->recalculateAfterChildChange($faktor1id);
            $this->resetApprovalForFaktor70();

            $nilai13 = $this->getNilai70();
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Data berhasil diubah',
                'peringkat13' => $nilai13['penilaiankredit'] ?? null,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error ubah: ' . $e->getMessage());
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menyimpan data: ' . $e->getMessage()]);
        }
    }

    public function ubahkesimpulan()
    {
        if (!$this->request->isAJAX())
            return redirect()->back()->with('error', 'Invalid request');

        $faktor1id = (int) $this->request->getPost('faktor1id');
        $keterangan = $this->request->getPost('keterangan');

        if (!$faktor1id || !$this->userKodebpr || !$this->periodeId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak valid']);
        }

        $existing = $this->nilaiModel
            ->where('faktor1id', $faktor1id)
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->first();

        if (!$existing) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak ditemukan']);
        }

        try {
            $this->db->table('risikooperasional_kpmr')
                ->where('faktor1id', $faktor1id)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update([
                    'keterangan' => $keterangan,
                    'penilaiankredit' => $existing['penilaiankredit'],
                    'user_id' => $this->userId,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'is_approved' => 0,
                ]);

            $this->resetApprovalForFaktor70();

            return $this->response->setJSON(['status' => 'success', 'message' => 'Keterangan kesimpulan berhasil diubah']);
        } catch (\Exception $e) {
            log_message('error', 'Error ubah kesimpulan: ' . $e->getMessage());
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal mengubah keterangan: ' . $e->getMessage()]);
        }
    }

    public function tambahNilai()
    {
        if (!$this->request->isAJAX())
            return redirect()->back()->with('error', 'Invalid request');

        $validation = $this->validate(['penilaiankredit' => 'required', 'keterangan' => 'required']);
        if (!$validation) {
            return $this->response->setJSON(['status' => 'error', 'message' => $this->validator->listErrors()]);
        }

        $faktorId = (int) $this->request->getPost('faktor_id');
        if (!$faktorId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'ID tidak ditemukan']);
        }

        $data = [
            'faktor1id' => $faktorId,
            'penilaiankredit' => $this->request->getPost('penilaiankredit'),
            'penjelasanpenilaian' => $this->request->getPost('penjelasanpenilaian'),
            'keterangan' => $this->request->getPost('keterangan'),
            'user_id' => $this->userId,
            'kodebpr' => $this->userKodebpr,
            'periode_id' => $this->periodeId,
            'is_approved' => 0,
            'accdir2' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        try {
            $this->db->table('risikooperasional_kpmr')->insert($data);
            $this->recalculateAfterChildChange($faktorId);
            $this->resetApprovalForFaktor70();

            $nilai13 = $this->getNilai70();
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Nilai berhasil ditambahkan',
                'peringkat13' => $nilai13['penilaiankredit'] ?? null,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error tambahNilai: ' . $e->getMessage());
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menambahkan nilai: ' . $e->getMessage()]);
        }
    }

    public function tambahKomentar()
    {
        $isAjax = $this->request->isAJAX();
        if (!$isAjax && !isset($_POST['tambahKomentar'])) {
            return redirect()->to(base_url('Risikooperasionalkpmr'));
        }

        if (!$this->validate(['komentar' => 'required'])) {
            return $isAjax
                ? $this->response->setJSON(['status' => 'error', 'message' => 'Komentar tidak boleh kosong'])
                : redirect()->back()->with('err', $this->validator->listErrors());
        }

        $faktorId = $this->request->getPost('faktor_id');
        $komentar = $this->request->getPost('komentar');

        if (!$faktorId) {
            return $isAjax
                ? $this->response->setJSON(['status' => 'error', 'message' => 'ID tidak ditemukan'])
                : redirect()->back()->with('err', 'ID tidak ditemukan');
        }

        try {
            $result = $this->komentarModel->insert([
                'subkategori' => 'OPERASIONALKPMR',
                'faktor1id' => $faktorId,
                'komentar' => $komentar,
                'user_id' => $this->userId,
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            if (!$result)
                throw new \Exception('Gagal menyimpan komentar');

            if ($isAjax) {
                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Komentar berhasil ditambahkan',
                    'comments' => $this->komentarModel->getKomentarByFaktorId('OPERASIONALKPMR', $faktorId, $this->userKodebpr, $this->periodeId),
                ]);
            }
            return redirect()->to(base_url('Risikooperasional') . '?modal_komentar=' . $faktorId)->with('message', 'Komentar berhasil ditambahkan');

        } catch (\Exception $e) {
            log_message('error', 'Error tambahKomentar: ' . $e->getMessage());
            return $isAjax
                ? $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menambahkan komentar: ' . $e->getMessage()])
                : redirect()->back()->with('err', 'Gagal menambahkan komentar');
        }
    }

    public function ubahPeringkat13Manual()
    {
        if (!$this->request->isAJAX())
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid request']);

        $penilaiankredit = $this->request->getPost('penilaiankredit');
        if (!in_array($penilaiankredit, ['1', '2', '3', '4', '5'], true)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Pilih peringkat yang valid']);
        }

        try {
            $this->nilaiModel
                ->where('faktor1id', 70)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->set([
                    'penilaiankredit' => $penilaiankredit,
                    'penjelasanpenilaian' => self::PENJELASAN_MAP[$penilaiankredit],
                    'is_manual' => 1,
                    'updated_at' => date('Y-m-d H:i:s'),
                ])->update();

            return $this->response->setJSON(['status' => 'success', 'peringkat' => $penilaiankredit, 'is_manual' => 1]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function resetPeringkat13Auto()
    {
        if (!$this->request->isAJAX())
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid request']);

        try {
            $rataRata = $this->nilaiModel->hitungRataRata(70, $this->userKodebpr, $this->periodeId);
            $rataRata = ($rataRata - floor($rataRata) >= 0.5) ? (int) ceil($rataRata) : (int) floor($rataRata);

            $this->nilaiModel
                ->where('faktor1id', 70)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->set([
                    'penilaiankredit' => $rataRata,
                    'penjelasanpenilaian' => self::PENJELASAN_MAP[$rataRata] ?? 'N/A',
                    'is_manual' => 0,
                    'updated_at' => date('Y-m-d H:i:s'),
                ])->update();

            return $this->response->setJSON(['status' => 'success', 'peringkat' => $rataRata, 'is_manual' => false]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function approveSemua()
    {
        if (!$this->userKodebpr || !$this->periodeId) {
            return $this->_approveResponse(false, 'Kode BPR atau Periode ID tidak valid');
        }

        $requiredFaktor = range(51, 70);
        $checkData = $this->nilaiModel
            ->select('COUNT(CASE WHEN penilaiankredit IS NOT NULL THEN 1 END) AS filled_count')
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->whereIn('faktor1id', $requiredFaktor)
            ->first();

        if ((int) ($checkData['filled_count'] ?? 0) < count($requiredFaktor)) {
            return $this->_approveResponse(false, 'Semua faktor harus diisi terlebih dahulu');
        }

        try {
            $this->db->table('risikooperasional_kpmr')
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->whereIn('faktor1id', $requiredFaktor)
                ->update(['is_approved' => 1, 'approved_by' => $this->userId, 'approved_at' => date('Y-m-d H:i:s')]);

            return $this->_approveResponse(true, 'Semua data berhasil disetujui.');
        } catch (\Exception $e) {
            log_message('error', 'Error approveSemua: ' . $e->getMessage());
            return $this->_approveResponse(false, 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    public function unapproveSemua()
    {
        if (!$this->userKodebpr || !$this->periodeId) {
            return $this->_approveResponse(false, 'Kode BPR atau Periode ID tidak valid');
        }

        try {
            $this->db->table('risikooperasional_kpmr')
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->whereIn('faktor1id', range(51, 70))
                ->update(['is_approved' => 0, 'approved_by' => $this->userId, 'approved_at' => date('Y-m-d H:i:s')]);

            return $this->_approveResponse(true, 'Semua data berhasil dibatalkan persetujuannya.');
        } catch (\Exception $e) {
            log_message('error', 'Error unapproveSemua: ' . $e->getMessage());
            return $this->_approveResponse(false, 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    private function _approveResponse(bool $success, string $message)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['status' => $success ? 'success' : 'error', 'message' => $message]);
        }
        return $success
            ? redirect()->back()->with('message', $message)
            : redirect()->back()->with('err', $message);
    }

    public function accdir2()
    {
        $faktor1id = $this->request->getPost('faktor1id');
        if (!$faktor1id)
            return redirect()->back()->with('error', 'Data tidak lengkap');

        $this->db->table('risikooperasional_kpmr')
            ->where('faktor1id', $faktor1id)
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->update(['accdir2' => 1, 'accdir2_by' => $this->userId, 'approved_at' => date('Y-m-d H:i:s')]);

        return redirect()->back()->with('message', 'Data berhasil disetujui');
    }

    public function unapprovedir2()
    {
        if ($this->request->getMethod() !== 'post')
            return $this->response->setStatusCode(405, 'Method Not Allowed');

        $faktor1id = $this->request->getPost('faktor1id');
        $kodebpr = $this->request->getPost('kodebpr');
        $periodeId = $this->request->getPost('periode_id');

        if (!$faktor1id || !$kodebpr || !$periodeId)
            return redirect()->to('/Risikooperasionalkpmr')->with('error', 'Data tidak lengkap');

        $nilaiFaktor = $this->nilaiModel
            ->where('faktor1id', $faktor1id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if (!$nilaiFaktor)
            return redirect()->to('/Risikooperasionalkpmr')->with('error', 'Data tidak ditemukan');

        if ($this->nilaiModel->update($nilaiFaktor['id'], ['accdir2' => 0, 'accdir2_by' => $this->userId, 'approved_at' => date('Y-m-d H:i:s')])) {
            return redirect()->to('/Risikooperasionalkpmr')->with('message', 'Persetujuan dibatalkan');
        }
        return redirect()->to('/Risikooperasionalkpmr')->with('error', 'Gagal memperbarui data');
    }

    public function simpanNilai14()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;

        if (!$this->validate(['penilaiankredit' => 'required|in_list[1,2,3,4,5]'])) {
            return redirect()->back()->with('err', 'Pilih tingkat risiko terlebih dahulu');
        }
        if (!$this->userKodebpr || !$this->periodeId)
            return redirect()->back()->with('error', 'Data user atau periode tidak valid');

        $penilaiankredit = $this->request->getPost('penilaiankredit');
        $user = $this->userModel->find($this->userId);
        $penjelasanpenilaian = self::PENJELASAN_MAP[$penilaiankredit] ?? 'N/A';

        try {
            $existing = $this->nilaiModel
                ->where('faktor1id', 71)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->first();

            $rowData = [
                'penilaiankredit' => $penilaiankredit,
                'penjelasanpenilaian' => $penjelasanpenilaian,
                'keterangan' => 'Tingkat Risiko Operasional KPMR Posisi Sebelumnya',
                'user_id' => $this->userId,
                'fullname' => $user['fullname'] ?? 'Unknown',
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($existing) {
                $result = $this->nilaiModel->where('faktor1id', 71)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)->set($rowData)->update();
            } else {
                $rowData += ['faktor1id' => 71, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId, 'is_approved' => 0, 'accdir2' => 0, 'created_at' => date('Y-m-d H:i:s')];
                $result = $this->nilaiModel->insert($rowData);
            }

            if (!$result)
                throw new \Exception('Gagal menyimpan data');
            return redirect()->to(base_url('Risikooperasionalkpmr'))->with('message', 'Data Tingkat Risiko Operasional KPMR berhasil ' . ($existing ? 'diupdate' : 'disimpan'));
        } catch (\Exception $e) {
            log_message('error', 'Error simpanNilai14: ' . $e->getMessage());
            return redirect()->back()->with('err', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function simpanKesimpulan13()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;

        if (!$this->validate(['keterangan' => 'required'])) {
            return redirect()->back()->with('err', 'Keterangan kesimpulan harus diisi');
        }
        if (!$this->userKodebpr || !$this->periodeId)
            return redirect()->back()->with('error', 'Data user atau periode tidak valid');

        $keterangan = $this->request->getPost('keterangan');
        $user = $this->userModel->find($this->userId);

        try {
            $existing = $this->nilaiModel->where('faktor1id', 70)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)->first();

            if ($existing) {
                $this->nilaiModel->where('faktor1id', 70)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
                    ->set(['keterangan' => $keterangan, 'user_id' => $this->userId, 'fullname' => $user['fullname'] ?? 'Unknown', 'updated_at' => date('Y-m-d H:i:s')])->update();
                return redirect()->to(base_url('Risikooperasionalkpmr'))->with('message', 'Kesimpulan berhasil diupdate');
            }

            $rataRata = $this->nilaiModel->hitungRataRata(70, $this->userKodebpr, $this->periodeId);
            $rataRata = ($rataRata - floor($rataRata) >= 0.5) ? (int) ceil($rataRata) : (int) floor($rataRata);

            $this->nilaiModel->insert([
                'faktor1id' => 70,
                'penilaiankredit' => $rataRata,
                'penjelasanpenilaian' => self::PENJELASAN_MAP[$rataRata] ?? 'N/A',
                'keterangan' => $keterangan,
                'fullname' => $user['fullname'] ?? 'Unknown',
                'user_id' => $this->userId,
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId,
                'is_approved' => 0,
                'accdir2' => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            return redirect()->to(base_url('Risikooperasionalkpmr'))->with('message', 'Kesimpulan berhasil disimpan');
        } catch (\Exception $e) {
            log_message('error', 'Error simpan kesimpulan13: ' . $e->getMessage());
            return redirect()->back()->with('err', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function setNullKolom($id)
    {
        if ($redirect = $this->checkAuth())
            return $redirect;
        $success = $this->nilaiModel->setNullKolom($id);
        session()->setFlashdata($success ? 'message' : 'err', $success ? 'Data berhasil dihapus' : 'Data gagal dihapus');
        return redirect()->to(base_url('Risikooperasionalkpmr'));
    }

    public function approve($idNilai = null)
    {
        if ($idNilai === null)
            return redirect()->back()->with('err', 'ID tidak valid');
        $this->db->table('risikooperasional_kpmr')->where('id', $idNilai)
            ->update(['is_approved' => 1, 'approved_by' => $this->userId, 'approved_at' => date('Y-m-d H:i:s')]);
        return redirect()->back()->with('message', 'Data berhasil disetujui');
    }

    public function unapprove($idNilai)
    {
        if (!is_numeric($idNilai) || $idNilai <= 0)
            return redirect()->back()->with('err', 'ID tidak valid.');
        $nilaiFaktor = $this->nilaiModel->find($idNilai);
        if (!$nilaiFaktor)
            return redirect()->back()->with('err', 'Data tidak ditemukan.');
        $this->nilaiModel->update($idNilai, ['is_approved' => 0, 'approved_by' => $this->userId, 'approved_at' => date('Y-m-d H:i:s')]);
        return redirect()->back()->with('message', 'Data approval dibatalkan.');
    }

    public function exporttxtrisikooperasionalkpmr()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;

        $periodeDetail = $this->periodeModel->find($this->periodeId);
        $exportDate = $this->buildExportDate($periodeDetail);
        $titleDate = str_replace('-', '', $exportDate);

        $data_risikooperasionalkpmr = $this->nilaiModel->getDataByKodebprAndPeriode($this->userKodebpr, $this->periodeId);
        $infobpr = $this->infobprModel->getDataByKodebpr($this->userKodebpr)[0] ?? [];

        $sandibpr = $infobpr['sandibpr'] ?? '';
        $kodejenis = $infobpr['kodejenis'] ?? '';
        $modalinti = $periodeDetail['modalinti'];
        $nomorsurat = $periodeDetail['nomorsurat'] ?? '';
        $jeniskoreksi = $periodeDetail['jeniskoreksi'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];
        $kategori = $periodeDetail['kategori'];

        usort($data_risikooperasionalkpmr, fn($a, $b) => ($a['faktor1id'] ?? 0) <=> ($b['faktor1id'] ?? 0));

        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0202|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";

        foreach ($data_risikooperasionalkpmr as $row) {
            $fid = $row['faktor1id'] ?? null;
            if (!isset(self::KODE_MAP[$fid]))
                continue;
            $output .= 'D01|' . self::KODE_MAP[$fid] . '|' . self::sanitizeTxt($row['penilaiankredit']) . '|' . self::sanitizeTxt($row['keterangan']) . "\r\n";
        }

        $filename = "TKSBPRKS-0202-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";
        return service('response')
            ->setHeader('Content-Type', 'text/plain')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }

    public function exporttxtrisikooperasionalkpmrtks()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;

        $periodeTKS = $this->periodetksModel->find($this->periodeId);
        if (!$periodeTKS)
            return redirect()->back()->with('error', 'Periode TKS tidak ditemukan');

        $periodeProfilRisikoId = $this->getMatchingProfilRisikoPeriodeId($this->periodeId);
        if (!$periodeProfilRisikoId)
            return redirect()->back()->with('error', 'Tidak ada periode Profil Risiko yang cocok');

        $exportDate = $this->buildExportDate($periodeTKS);
        $titleDate = str_replace('-', '', $exportDate);

        $data = $this->nilaiModel->getDataByKodebprAndPeriode($this->userKodebpr, $periodeProfilRisikoId);
        $infobpr = $this->infobprModel->getDataByKodebpr($this->userKodebpr)[0] ?? [];

        $sandibpr = $infobpr['sandibpr'] ?? '';
        $kodejenis = $infobpr['kodejenis'] ?? '';
        $modalinti = $periodeTKS['modalinti'] ?? '';
        $jenispelaporan = $periodeTKS['jenispelaporan'] ?? '';
        $kategori = $periodeTKS['kategori'] ?? '';

        usort($data, fn($a, $b) => ($a['faktor1id'] ?? 0) <=> ($b['faktor1id'] ?? 0));

        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0202|{$modalinti}|{$kategori}||\r\n";
        foreach ($data as $row) {
            $fid = $row['faktor1id'] ?? null;
            if (!isset(self::KODE_MAP[$fid]))
                continue;
            $output .= 'D01|' . self::KODE_MAP[$fid] . '|' . self::sanitizeTxt($row['penilaiankredit']) . '|' . self::sanitizeTxt($row['keterangan']) . "\r\n";
        }

        $filename = "TKSBPRKS-0202-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";
        return service('response')
            ->setHeader('Content-Type', 'text/plain')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }
    private function recalculateAfterChildChange(int $changedFaktorId): void
    {
        foreach (self::CATEGORY_MAPPING as $categoryId => $childrenIds) {
            if (!in_array($changedFaktorId, $childrenIds, true))
                continue;

            $childRows = $this->db->table('risikooperasional_kpmr')
                ->select('penilaiankredit')
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->whereIn('faktor1id', $childrenIds)
                ->where('penilaiankredit IS NOT NULL', null, false)
                ->get()->getResultArray();

            if (empty($childRows))
                return;

            $values = array_column($childRows, 'penilaiankredit');
            $average = array_sum($values) / count($values);
            $rounded = (int) round($average);

            $config = $this->getPenilaianConfig();
            $autoKeterangan = $config[$categoryId]['descriptions'][$rounded] ?? '';
            $user = $this->userModel->find($this->userId);

            $existing = $this->nilaiModel->where('faktor1id', $categoryId)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)->first();

            $catData = [
                'faktor1id' => $categoryId,
                'penilaiankredit' => $rounded,
                'penjelasanpenilaian' => $autoKeterangan,
                'keterangan' => $autoKeterangan,
                'user_id' => $this->userId,
                'fullname' => $user['fullname'] ?? 'Unknown',
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId,
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($existing) {
                $this->nilaiModel->update($existing['id'], $catData);
            } else {
                $catData += ['created_at' => date('Y-m-d H:i:s'), 'is_approved' => 0, 'accdir2' => 0];
                $this->nilaiModel->insert($catData);
            }
            break;
        }

        $rataRata = $this->nilaiModel->hitungRataRata(70, $this->userKodebpr, $this->periodeId);
        $this->nilaiModel->insertOrUpdateRataRata($rataRata, 70, $this->userKodebpr, $this->periodeId);
    }

    private function resetApprovalForFaktor70(): void
    {
        $this->db->table('risikooperasional_kpmr')
            ->where('faktor1id', 70)
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->update(['accdir2' => 0, 'is_approved' => 0]);
    }

    private function getNilai70(): ?array
    {
        return $this->nilaiModel->where(['faktor1id' => 70, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first();
    }

    private function buildStructure(array $nilaiLookup): array
    {
        $raw = [
            [
                'id' => 51,
                'title' => 'Pilar Pengawasan Direksi dan Dewan Komisaris',
                'type' => 'category',
                'faktor_id' => 51,
                'children' => [
                    ['id' => 52, 'title' => 'Apakah Dewan Komisaris telah melakukan persetujuan terhadap kebijakan manajemen risiko operasional <br> yang disusun oleh Direksi dan melakukan evaluasi secara berkala?', 'faktor_id' => 52],
                    ['id' => 53, 'title' => 'Apakah Dewan Komisaris telah melakukan evaluasi terhadap pertanggungjawaban Direksi atas pelaksanaan <br> kebijakan manajemen risiko operasional secara berkala dan memastikan tindak lanjut hasil evaluasi dimaksud?', 'faktor_id' => 53],
                    ['id' => 54, 'title' => 'Apakah Direksi telah menyusun kebijakan manajemen risiko operasional, melaksanakan secara konsisten, <br> dan melakukan pengkinian secara berkala?', 'faktor_id' => 54],
                    ['id' => 55, 'title' => 'Apakah Direksi telah memiliki kemampuan untuk mengambil tindakan yang diperlukan dalam rangka mitigasi <br> risiko operasional, dan melakukan komunikasi kebijakan manajemen risiko operasional terhadap seluruh jenjang <br> organisasi BPR?', 'faktor_id' => 55],
                    ['id' => 56, 'title' => 'Apakah BPR telah memiliki kecukupan organisasi yang menangani fungsi operasional dan fungsi manajemen <br> risiko operasional?', 'faktor_id' => 56],
                    ['id' => 57, 'title' => 'Apakah Direksi telah menerapkan kebijakan pengelolaan SDM dalam rangka penerapan Manajemen Risiko <br> operasional?', 'faktor_id' => 57],
                ],
            ],
            [
                'id' => 58,
                'title' => 'Pilar Kecukupan Kebijakan, Prosedur, dan Limit',
                'type' => 'category',
                'faktor_id' => 58,
                'children' => [
                    ['id' => 59, 'title' => 'Apakah BPR telah memiliki kebijakan manajemen risiko operasional yang memadai dan disusun dengan <br> mempertimbangkan visi, misi, skala usaha dan kompleksitas bisnis, serta kecukupan SDM?', 'faktor_id' => 59],
                    ['id' => 60, 'title' => 'Apakah BPR: <br>● Memiliki prosedur manajemen risiko operasional dan penetapan limit risiko operasional yang ditetapkan oleh Direksi; <br>● Melaksanakan prosedur manajemen risiko operasional dan penetapan limit risiko operasional secara konsisten untuk seluruh aktivitas; dan <br>● Melakukan evaluasi dan pengkinian terhadap prosedur manajemen risiko operasional dan penetapan limit risiko operasional secara berkala?', 'faktor_id' => 60],
                    ['id' => 61, 'title' => 'Apakah BPR telah memiliki kebijakan dan prosedur penerbitan produk dan/atau pelaksanaan aktivitas <br> baru yang mencakup identifikasi dan mitigasi risiko operasional sesuai dengan ketentuan?', 'faktor_id' => 61],
                ],
            ],
            [
                'id' => 62,
                'title' => 'Kecukupan Proses dan Sistem Manajemen Risiko',
                'type' => 'category',
                'faktor_id' => 62,
                'children' => [
                    ['id' => 63, 'title' => 'Apakah BPR telah melaksanakan proses manajemen risiko operasional yang melekat pada kegiatan <br> usaha BPR?', 'faktor_id' => 63],
                    ['id' => 64, 'title' => 'Apakah BPR telah memiliki sistem informasi manajemen risiko yang mendukung Direksi dalam <br> pengambilan keputusan terkait risiko operasional serta telah dilaporkan kepada Direksi secara berkala?', 'faktor_id' => 64],
                    ['id' => 65, 'title' => 'Apakah BPR telah memiliki kebijakan dan prosedur penyelenggaraan TI?', 'faktor_id' => 65],
                    ['id' => 66, 'title' => 'Apakah BPR telah melakukan langkah mitigasi risiko terkait kejadian eksternal?', 'faktor_id' => 66],
                ],
            ],
            [
                'id' => 67,
                'title' => 'Sistem Pengendalian Internal yang Menyeluruh',
                'type' => 'category',
                'faktor_id' => 67,
                'children' => [
                    ['id' => 68, 'title' => 'Apakah SKAI atau PEAI telah melaksanakan audit secara berkala terhadap penerapan <br> manajemen risiko operasional, menyampaikan laporan hasil audit intern, dan memastikan <br> tindaklanjut atas temuan pemeriksaan?', 'faktor_id' => 68],
                    ['id' => 69, 'title' => 'Apakah sistem pengendalian intern terhadap risiko operasional telah dilaksanakan oleh <br> seluruh jenjang organisasi BPR?', 'faktor_id' => 69],
                ],
            ],
        ];

        foreach ($raw as &$item) {
            $item['nilai'] = $nilaiLookup[$item['faktor_id']] ?? null;
            if (!empty($item['children'])) {
                foreach ($item['children'] as &$child) {
                    $child['nilai'] = $nilaiLookup[$child['faktor_id']] ?? null;
                }
            }
        }
        return $raw;
    }

    private function buildExportDate(array $periodeDetail): string
    {
        $tahun = $periodeDetail['tahun'] ?? date('Y');
        $semester = $periodeDetail['semester'] ?? '';
        return (strtolower($semester) === 'ganjil' || $semester === '1')
            ? "{$tahun}-06-30"
            : "{$tahun}-12-31";
    }

    private static function sanitizeTxt(string $text = ''): string
    {
        $text = str_replace(["\r", "\n"], ' ', $text);
        $text = str_replace(['"', '"', '%', '|'], ['"', '"', "'", "'", ' persen ', ' '], $text);
        $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    private function getMatchingProfilRisikoPeriodeId($periodeTKSId): ?int
    {
        $periodeTKS = $this->periodetksModel->find($periodeTKSId);
        if (!$periodeTKS)
            return null;

        $match = $this->periodeModel->where([
            'semester' => $periodeTKS['semester'],
            'tahun' => $periodeTKS['tahun'],
            'kodebpr' => $this->userKodebpr,
        ])->first();

        return $match ? (int) $match['id'] : null;
    }

    private function formatDateForDisplay(string $date): string
    {
        if (empty($date) || $date === '0000-00-00')
            return '';
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date))
            return $date;
        $dateObj = \DateTime::createFromFormat('Y-m-d', $date);
        return $dateObj ? $dateObj->format('d/m/Y') : '';
    }

    private function getPenilaianConfig(): array
    {
        if (self::$_penilaianConfigCache !== null)
            return self::$_penilaianConfigCache;

        $catatan = 'Peniliaian Risiko Operasional Kualitas Penerapan Manajemen Risiko (KPMR)';

        self::$_penilaianConfigCache = [
            51 => ['descriptions' => [1 => 'Parameter Pengawasan Direksi dan Komisaris berada pada tingkat risiko yang Sangat Rendah', 2 => 'Parameter Pengawasan Direksi dan Komisaris berada pada tingkat risiko yang Rendah', 3 => 'Parameter Pengawasan Direksi dan Komisaris berada pada tingkat risiko yang Sedang', 4 => 'Parameter Pengawasan Direksi dan Komisaris berada pada tingkat risiko yang Tinggi', 5 => 'Parameter Pengawasan Direksi dan Komisaris berada pada tingkat risiko yang Sangat Tinggi'], 'catatan' => $catatan],
            52 => [
                'descriptions' => [
                    1 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko operasional <br>• Dewan Komisaris telah melakukan evaluasi terhadap kebijakan Manajemen Risiko operasional <br>• Evaluasi dilakukan oleh Dewan Komisaris secara berkala paling sedikit satu kali dalam satu tahun atau sewaktu-waktu dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan; dan <br>• Evaluasi yang dilakukan relevan dengan kebutuhan penyesuaian kebijakan Manajemen Risiko operasional.',
                    2 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko operasional; <br>• Dewan Komisaris telah melakukan evaluasi terhadap kebijakan Manajemen Risiko operasional; dan <br>• Evaluasi dilakukan oleh Dewan Komisaris secara berkala paling sedikit satu kali dalam satu tahun atau sewaktu-waktu dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan',
                    3 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko operasional; <br>• Dewan Komisaris telah melakukan evaluasi terhadap kebijakan Manajemen Risiko operasional; dan <br>• Evaluasi tidak dilakukan oleh Dewan Komisaris secara berkala paling sedikit satu kali dalam satu tahun atau sewaktu-waktu dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan',
                    4 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko operasional; dan <br>• Dewan Komisaris tidak melakukan evaluasi terhadap kebijakan Manajemen Risiko operasional.',
                    5 => '• Dewan Komisaris tidak memberikan persetujuan terhadap kebijakan Manajemen Risiko operasional; dan <br>• Dewan Komisaris tidak melakukan evaluasi terhadap kebijakan Manajemen Risiko operasional.',
                ],
                'catatan' => $catatan
            ],
            53 => [
                'descriptions' => [
                    1 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko operasional oleh Direksi; <br>• Evaluasi oleh Dewan Komisaris dilakukan secara berkala setiap semester atau lebih; <br>• Dewan Komisaris telah memastikan tindak lanjut hasil evaluasi dalam setiap periode laporan; dan <br>• Evaluasi yang diberikan relevan dengan pelaksanaan kebijakan Manajemen Risiko operasional.',
                    2 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko operasional oleh Direksi; <br>• Evaluasi dilakukan secara berkala setiap semester; <br>• Dewan Komisaris telah memastikan tindak lanjut hasil evaluasi dalam setiap periode laporan.',
                    3 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko operasional oleh Direksi; <br>• Evaluasi dilakukan secara berkala setiap semester; dan <br>• Dewan Komisaris tidak memastikan tindak lanjut hasil evaluasi dalam setiap periode laporan.',
                    4 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko operasional oleh Direksi; <br>• Evaluasi oleh Dewan Komisaris tidak dilakukan secara berkala; dan <br>• Dewan Komisaris tidak memastikan tindak lanjut hasil evaluasi.',
                    5 => 'Dewan Komisaris tidak melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko operasional oleh Direksi.',
                ],
                'catatan' => $catatan
            ],
            54 => [
                'descriptions' => [
                    1 => '• Direksi telah menyusun kebijakan Manajemen Risiko operasional; <br>• Menjalankan kegiatan usaha berdasarkan kebijakan yang telah ditetapkan; <br>• Direksi melakukan pengkinian secara berkala; dan <br>• Kebijakan yang dijalankan terbukti memitigasi terjadinya Risiko operasional.',
                    2 => '• Direksi telah menyusun kebijakan Manajemen Risiko operasional; <br>• Menjalankan kegiatan usaha berdasarkan kebijakan yang telah ditetapkan; dan <br>• Direksi melakukan pengkinian secara berkala.',
                    3 => '• Direksi telah menyusun kebijakan Manajemen Risiko operasional; <br>• Menjalankan kegiatan usaha berdasarkan kebijakan yang telah ditetapkan; dan <br>• Direksi tidak melakukan pengkinian secara berkala.',
                    4 => '• Direksi telah menyusun kebijakan Manajemen Risiko operasional; <br>• Tidak menjalankan kegiatan usaha berdasarkan kebijakan yang telah ditetapkan; dan <br>• Direksi tidak melakukan pengkinian.',
                    5 => 'Direksi tidak menyusun kebijakan Manajemen Risiko operasional.',
                ],
                'catatan' => $catatan
            ],
            55 => [
                'descriptions' => [
                    1 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko; <br>• Direksi mengomunikasikan kebijakan Manajemen Risiko operasional; dan <br>• Seluruh jenjang organisasi BPR mampu memahami kebijakan yang diterapkan.',
                    2 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko; <br>• Direksi mengomunikasikan kebijakan Manajemen Risiko operasional; dan <br>• Tidak seluruh jenjang organisasi BPR mampu memahami kebijakan namun tidak menimbulkan dampak yang signifikan.',
                    3 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko; <br>• Direksi mengomunikasikan kebijakan Manajemen Risiko operasional; dan <br>• Tidak seluruh jenjang organisasi BPR mampu memahami kebijakan dan menimbulkan dampak yang signifikan.',
                    4 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko; <br>• Direksi tidak mengomunikasikan kebijakan; dan <br>• Seluruh jenjang organisasi BPR tidak mampu memahami kebijakan yang diterapkan.',
                    5 => '• Direksi tidak mengambil tindakan yang diperlukan; <br>• Direksi tidak mengomunikasikan kebijakan; dan <br>• Seluruh jenjang organisasi BPR tidak mampu memahami kebijakan yang diterapkan.',
                ],
                'catatan' => $catatan
            ],
            56 => [
                'descriptions' => [
                    1 => '• Memiliki unit kerja yang menangani fungsi operasional secara lengkap dan tidak terdapat rangkap jabatan. <br>• Unit kerja telah melaksanakan tugas dan wewenangnya sesuai pedoman; dan <br>• Memiliki SKMR atau PEMR dan mampu melaksanakan fungsinya untuk memitigasi Risiko operasional.',
                    2 => '• Memiliki unit kerja yang menangani fungsi operasional namun tidak lengkap dan tidak terdapat rangkap jabatan. <br>• Unit kerja telah melaksanakan tugas dan wewenangnya sesuai pedoman; dan <br>• Memiliki SKMR atau PEMR dan mampu melaksanakan fungsinya untuk memitigasi Risiko operasional.',
                    3 => '• Memiliki unit kerja yang menangani fungsi operasional namun tidak lengkap dan terdapat rangkap jabatan namun tidak menyebabkan tidak terlaksananya tata kelola yang baik. <br>• Unit kerja telah melaksanakan tugasnya sesuai pedoman; dan <br>• Memiliki SKMR atau PEMR namun tidak mampu melaksanakan fungsinya.',
                    4 => '• Memiliki unit kerja yang menangani fungsi operasional namun tidak lengkap dan terdapat rangkap jabatan yang dapat menyebabkan tidak terlaksananya tata kelola yang baik. <br>• Unit kerja telah melaksanakan tugasnya sesuai pedoman; dan <br>• Memiliki SKMR atau PEMR namun tidak mampu melaksanakan fungsinya.',
                    5 => '• Memiliki unit kerja yang menangani fungsi operasional namun tidak lengkap dan terdapat rangkap jabatan yang dapat menyebabkan tidak terlaksananya tata kelola yang baik. <br>• Unit kerja tidak melaksanakan tugasnya sesuai pedoman; dan <br>• Memiliki SKMR atau PEMR namun tidak mampu melaksanakan fungsinya.',
                ],
                'catatan' => $catatan
            ],
            57 => [
                'descriptions' => [
                    1 => '• Terdapat kesesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan; <br>• Terdapat upaya peningkatan kompetensi SDM secara konsisten; dan <br>• Tingkat pemenuhan standar kinerja SDM sesuai dengan tugas dan tanggung jawab.',
                    2 => '• Terdapat ketidaksesuaian kualifikasi SDM namun tidak memberikan dampak yang signifikan; <br>• Terdapat upaya peningkatan kompetensi SDM secara konsisten; dan <br>• Tingkat pemenuhan standar kinerja SDM sesuai dengan tugas dan tanggung jawab.',
                    3 => '• Terdapat ketidaksesuaian kualifikasi SDM namun tidak memberikan dampak yang signifikan; <br>• Terdapat upaya peningkatan kompetensi SDM namun tidak secara konsisten; dan <br>• Tingkat pemenuhan standar kinerja SDM sesuai dengan tugas dan tanggung jawab.',
                    4 => '• Terdapat ketidaksesuaian kualifikasi SDM dan memberikan dampak yang signifikan; <br>• Terdapat upaya peningkatan kompetensi SDM namun tidak secara konsisten; dan <br>• Tingkat pemenuhan standar kinerja SDM tidak sesuai namun tidak memberikan dampak yang signifikan.',
                    5 => '• Terdapat ketidaksesuaian kualifikasi SDM dan memberikan dampak yang signifikan; <br>• Tidak terdapat upaya peningkatan kompetensi SDM; dan <br>• Tingkat pemenuhan standar kinerja SDM tidak sesuai dan memberikan dampak yang signifikan.',
                ],
                'catatan' => $catatan
            ],
            58 => ['descriptions' => [1 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Sangat Rendah', 2 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Rendah', 3 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Sedang', 4 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Tinggi', 5 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Sangat Tinggi'], 'catatan' => $catatan],
            59 => [
                'descriptions' => [
                    1 => '• Telah memiliki kebijakan Manajemen Risiko operasional; <br>• Terdapat kesesuaian antara substansi kebijakan dengan ketentuan Manajemen Risiko BPR; dan <br>• Terdapat keselarasan antara kebijakan dengan visi, misi, skala usaha, dan kompleksitas bisnis.',
                    2 => '• Telah memiliki kebijakan Manajemen Risiko operasional; <br>• Terdapat ketidaksesuaian yang tidak signifikan antara substansi kebijakan dengan ketentuan; dan <br>• Terdapat keselarasan antara kebijakan dengan visi, misi, skala usaha, dan kompleksitas bisnis.',
                    3 => '• Telah memiliki kebijakan Manajemen Risiko operasional; <br>• Terdapat ketidaksesuaian yang tidak signifikan antara substansi kebijakan dengan ketentuan; dan <br>• Terdapat ketidakselarasan antara kebijakan dengan visi, misi, skala usaha, dan kompleksitas bisnis namun tidak menimbulkan dampak yang signifikan.',
                    4 => '• Telah memiliki kebijakan Manajemen Risiko operasional; <br>• Terdapat ketidaksesuaian yang signifikan antara substansi kebijakan dengan ketentuan; dan <br>• Terdapat ketidakselarasan antara kebijakan dengan visi, misi, skala usaha, dan kompleksitas bisnis dan menimbulkan dampak yang signifikan.',
                    5 => 'Tidak memiliki kebijakan Manajemen Risiko operasional.',
                ],
                'catatan' => $catatan
            ],
            60 => [
                'descriptions' => [
                    1 => '• Memiliki prosedur dan limit yang ditetapkan Direksi; <br>• Melaksanakan prosedur secara konsisten; dan <br>• Melakukan evaluasi dan pengkinian dalam hal terdapat perubahan bisnis yang signifikan.',
                    2 => '• Memiliki prosedur dan limit yang ditetapkan Direksi; <br>• Melaksanakan prosedur secara konsisten; dan <br>• Tidak melakukan evaluasi dan pengkinian, namun tidak menimbulkan dampak yang signifikan.',
                    3 => '• Memiliki prosedur dan limit yang ditetapkan Direksi; <br>• Melaksanakan prosedur secara konsisten; dan <br>• Tidak melakukan evaluasi dan pengkinian, dan menimbulkan dampak yang signifikan.',
                    4 => '• Memiliki prosedur dan limit yang ditetapkan Direksi; <br>• Tidak melaksanakan prosedur secara konsisten; dan <br>• Tidak melakukan evaluasi dan pengkinian.',
                    5 => 'Tidak memiliki prosedur Manajemen Risiko operasional dan penetapan limit.',
                ],
                'catatan' => $catatan
            ],
            61 => [
                'descriptions' => [
                    1 => '• Memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru; <br>• Menerapkan kebijakan dan prosedur dalam hal terdapat penerbitan produk atau pelaksanaan aktivitas baru; dan <br>• Terdapat kesesuaian antara kebijakan dan prosedur dengan ketentuan.',
                    2 => '• Memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru; <br>• Menerapkan kebijakan dan prosedur; dan <br>• Terdapat ketidaksesuaian yang tidak signifikan antara kebijakan dan prosedur dengan ketentuan.',
                    3 => '• Memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru; <br>• Menerapkan kebijakan dan prosedur; dan <br>• Terdapat ketidaksesuaian yang signifikan antara kebijakan dan prosedur dengan ketentuan.',
                    4 => '• Memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru; <br>• Tidak menerapkan kebijakan dan prosedur; dan <br>• Terdapat ketidaksesuaian yang signifikan antara kebijakan dan prosedur dengan ketentuan.',
                    5 => 'Tidak memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru.',
                ],
                'catatan' => $catatan
            ],
            62 => ['descriptions' => [1 => 'Parameter Kecukupan Proses dan Sistem Manajemen Risiko berada pada tingkat risiko yang Sangat Rendah', 2 => 'Parameter Kecukupan Proses dan Sistem Manajemen Risiko berada pada tingkat risiko yang Rendah', 3 => 'Parameter Kecukupan Proses dan Sistem Manajemen Risiko berada pada tingkat risiko yang Sedang', 4 => 'Parameter Kecukupan Proses dan Sistem Manajemen Risiko berada pada tingkat risiko yang Tinggi', 5 => 'Parameter Kecukupan Proses dan Sistem Manajemen Risiko berada pada tingkat risiko yang Sangat Tinggi'], 'catatan' => $catatan],
            63 => [
                'descriptions' => [
                    1 => '• Telah melaksanakan proses Manajemen Risiko operasional meliputi identifikasi, pengukuran, pemantauan, dan pengendalian; <br>• Penerapan Manajemen Risiko operasional dilakukan dengan sangat memadai; dan <br>• Penerapan dilakukan secara konsisten.',
                    2 => '• Telah melaksanakan proses Manajemen Risiko operasional; <br>• Penerapan dilakukan dengan memadai; dan <br>• Penerapan dilakukan cukup konsisten.',
                    3 => '• Telah melaksanakan proses Manajemen Risiko operasional; <br>• Penerapan dilakukan dengan memadai; dan <br>• Penerapan tidak dilakukan secara konsisten namun tidak menimbulkan dampak yang signifikan.',
                    4 => '• Telah melaksanakan proses Manajemen Risiko operasional namun tidak secara keseluruhan; <br>• Penerapan tidak memadai; dan <br>• Penerapan tidak dilakukan secara konsisten sehingga menimbulkan dampak yang signifikan.',
                    5 => 'Tidak melaksanakan proses Manajemen Risiko operasional.',
                ],
                'catatan' => $catatan
            ],
            64 => [
                'descriptions' => [
                    1 => '• Telah memiliki sistem informasi Manajemen Risiko yang mencerminkan Risiko operasional; <br>• Data pada sistem informasi lengkap, akurat, kini, dan utuh; <br>• Mendukung Direksi dalam pengambilan keputusan; dan <br>• Sangat mendukung SKMR atau PEMR dalam pembuatan laporan kepada Direksi setiap semester.',
                    2 => '• Telah memiliki sistem informasi Manajemen Risiko; <br>• Data cukup lengkap, akurat, kini, dan utuh; <br>• Mendukung Direksi dalam pengambilan keputusan; dan <br>• Cukup mendukung SKMR atau PEMR dalam pembuatan laporan.',
                    3 => '• Telah memiliki sistem informasi Manajemen Risiko; <br>• Data kurang lengkap, akurat, kini, dan utuh; <br>• Tidak sepenuhnya mendukung Direksi; dan <br>• Kurang mendukung SKMR atau PEMR dalam pembuatan laporan.',
                    4 => '• Telah memiliki sistem informasi Manajemen Risiko; <br>• Data tidak lengkap, akurat, kini, dan utuh; <br>• Tidak sepenuhnya mendukung Direksi; dan <br>• Tidak mendukung SKMR atau PEMR dalam pembuatan laporan.',
                    5 => 'Tidak memiliki sistem informasi Manajemen Risiko yang mencerminkan Risiko operasional.',
                ],
                'catatan' => $catatan
            ],
            65 => [
                'descriptions' => [
                    1 => '• Telah memiliki kebijakan dan prosedur penyelenggaraan TI yang telah ditetapkan oleh Direksi; <br>• Telah menjalankan kegiatan operasional sesuai kebijakan; dan <br>• Telah memiliki aspek pengamanan TI sesuai ketentuan OJK.',
                    2 => '• Telah memiliki kebijakan dan prosedur penyelenggaraan TI; <br>• Telah menjalankan kegiatan operasional namun tidak sepenuhnya sesuai kebijakan dan tidak menimbulkan dampak signifikan; dan <br>• Telah memiliki aspek pengamanan TI sesuai ketentuan OJK.',
                    3 => '• Telah memiliki kebijakan dan prosedur penyelenggaraan TI; <br>• Telah menjalankan kegiatan operasional namun tidak sepenuhnya sesuai kebijakan dan menimbulkan dampak signifikan; dan <br>• Telah memiliki aspek pengamanan TI sesuai ketentuan OJK.',
                    4 => '• Telah memiliki kebijakan dan prosedur penyelenggaraan TI; <br>• Telah menjalankan kegiatan operasional namun tidak sepenuhnya sesuai kebijakan dan menimbulkan dampak signifikan; dan <br>• Aspek pengamanan TI tidak sesuai ketentuan OJK.',
                    5 => 'Tidak memiliki kebijakan dan prosedur penyelenggaraan TI yang telah ditetapkan oleh Direksi.',
                ],
                'catatan' => $catatan
            ],
            66 => [
                'descriptions' => [
                    1 => '• Telah memiliki rekam cadang, sumber listrik cadangan, jaringan komunikasi alternatif, dan pusat pemulihan bencana sesuai ketentuan OJK; <br>• Telah memiliki rencana pemulihan bencana; dan <br>• Telah melakukan uji coba terhadap rencana pemulihan bencana sesuai ketentuan OJK.',
                    2 => '• Telah memiliki rekam cadang, sumber listrik cadangan, dan pusat pemulihan bencana sesuai ketentuan OJK; <br>• Telah memiliki rencana pemulihan bencana; dan <br>• Telah melakukan uji coba namun tidak sepenuhnya sesuai ketentuan OJK.',
                    3 => '• Telah memiliki rekam cadang, sumber listrik cadangan, dan pusat pemulihan bencana sesuai ketentuan OJK; <br>• Telah memiliki rencana pemulihan bencana namun tidak sepenuhnya sesuai ketentuan dan tidak menimbulkan dampak signifikan; dan <br>• Telah melakukan uji coba namun tidak sepenuhnya sesuai ketentuan OJK.',
                    4 => '• Telah memiliki rekam cadang, sumber listrik cadangan, dan pusat pemulihan bencana sesuai ketentuan OJK; <br>• Telah memiliki rencana pemulihan bencana namun tidak sepenuhnya sesuai ketentuan dan menimbulkan dampak signifikan; dan <br>• Telah melakukan uji coba namun tidak sepenuhnya sesuai ketentuan OJK.',
                    5 => '• Tidak memiliki rekam cadang, sumber listrik cadangan, dan pusat pemulihan bencana sesuai ketentuan OJK; <br>• Rencana pemulihan bencana tidak sepenuhnya sesuai ketentuan dan menimbulkan dampak signifikan; dan <br>• Uji coba tidak sepenuhnya sesuai ketentuan OJK.',
                ],
                'catatan' => $catatan
            ],
            67 => ['descriptions' => [1 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Sangat Rendah', 2 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Rendah', 3 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Sedang', 4 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Tinggi', 5 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Sangat Tinggi'], 'catatan' => $catatan],
            68 => [
                'descriptions' => [
                    1 => '• SKAI atau PEAI telah melaksanakan audit intern terhadap penerapan Manajemen Risiko operasional, memberikan rekomendasi, dan melaporkan hasil audit intern kepada Direktur Utama; <br>• Audit intern mencakup pelaksanaan kebijakan dan prosedur; dan <br>• Hasil temuan audit intern yang dijadikan rekomendasi telah ditindaklanjuti.',
                    2 => '• SKAI atau PEAI telah melaksanakan audit intern; <br>• Audit intern mencakup pelaksanaan kebijakan dan prosedur; dan <br>• Hasil temuan tidak sepenuhnya ditindaklanjuti namun tidak menimbulkan dampak yang signifikan.',
                    3 => '• SKAI atau PEAI telah melaksanakan audit intern; <br>• Audit intern mencakup pelaksanaan kebijakan dan prosedur; dan <br>• Hasil temuan tidak sepenuhnya ditindaklanjuti dan menimbulkan dampak yang signifikan.',
                    4 => '• SKAI atau PEAI telah melaksanakan audit intern; <br>• Audit intern tidak sesuai cakupan pelaksanaan kebijakan dan prosedur; dan <br>• Hasil temuan tidak ditindaklanjuti.',
                    5 => 'SKAI atau PEAI tidak melaksanakan audit intern terhadap penerapan Manajemen Risiko operasional.',
                ],
                'catatan' => $catatan
            ],
            69 => [
                'descriptions' => [
                    1 => '• Seluruh jenjang organisasi BPR telah melaksanakan fungsi pengendalian intern dengan memerhatikan kebijakan, prosedur, serta penetapan limit; <br>• Terdapat kejelasan wewenang dan tanggung jawab; <br>• SKMR atau PEMR terpisah dari unit operasional; dan <br>• SKAI atau PEAI terpisah dari unit operasional.',
                    2 => '• Seluruh jenjang organisasi BPR telah melaksanakan fungsi pengendalian intern namun tidak sepenuhnya memerhatikan kebijakan dan tidak berdampak signifikan; <br>• Terdapat kejelasan wewenang dan tanggung jawab; <br>• SKMR atau PEMR terpisah; dan <br>• SKAI atau PEAI terpisah.',
                    3 => '• Seluruh jenjang organisasi BPR telah melaksanakan fungsi pengendalian intern namun tidak sepenuhnya memerhatikan kebijakan dan berdampak signifikan; <br>• Terdapat kejelasan wewenang dan tanggung jawab; <br>• SKMR atau PEMR terpisah; dan <br>• SKAI atau PEAI terpisah.',
                    4 => '• Tidak seluruh jenjang organisasi BPR melaksanakan fungsi pengendalian intern dan berdampak sangat signifikan; <br>• Tidak terdapat kejelasan wewenang dan tanggung jawab; <br>• SKMR atau PEMR terpisah; dan <br>• SKAI atau PEAI terpisah.',
                    5 => '• Seluruh jenjang organisasi BPR tidak melaksanakan fungsi pengendalian intern; <br>• Tidak terdapat kejelasan wewenang dan tanggung jawab; <br>• SKMR atau PEMR tidak terpisah dari unit operasional; dan <br>• SKAI atau PEAI tidak terpisah dari unit operasional.',
                ],
                'catatan' => $catatan
            ],
        ];

        return self::$_penilaianConfigCache;
    }

    public function getNilaiByFaktorId($faktorId)
    {
        if (!$this->request->isAJAX() || !is_numeric($faktorId))
            return $this->response->setStatusCode(404)->setBody('Not Found');
        return $this->response->setJSON($this->nilaiModel->getNilaiByFaktorId($faktorId));
    }

    public function cekKomentarBaru()
    {
        $subkategori = 'OPERASIONALKPMR';
        $kodebpr = $this->request->getGet('kodebpr');
        $lastVisit = $this->request->getGet('last_visit');
        $results = $this->komentarModel->select('faktor1id, COUNT(*) as jumlah')->where('subkategori', $subkategori)->where('kodebpr', $kodebpr)->where('periode_id', $this->periodeId)->where('created_at >', $lastVisit)->groupBy('faktor1id')->findAll();
        return $this->response->setJSON($results);
    }

    public function recalculateCategoryAverage()
    {
        if (!$this->request->isAJAX())
            return $this->response->setStatusCode(403);
        $faktorId = (int) $this->request->getPost('faktor_id');
        $this->recalculateAfterChildChange($faktorId);
        return $this->response->setJSON(['status' => 'success', 'message' => 'Kategori berhasil di-recalculate']);
    }

    public function getUnreadCommentCountForFactor()
    {
        if (!$this->request->isAJAX())
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        $faktorId = $this->request->getGet('faktor_id');
        if (!$faktorId || !$this->userKodebpr || !$this->userId || !$this->periodeId)
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Missing data.']);
        $count = $this->commentReadsModel->countUnreadCommentsForUserByFactor($faktorId, 'OPERASIONALKPMR', $this->userKodebpr, $this->userId, $this->periodeId);
        return $this->response->setJSON(['unread_count' => $count]);
    }

    public function ubahketerangan()
    {
        if (!$this->request->isAJAX())
            return redirect()->back()->with('error', 'Invalid request');
        $faktor1id = (int) $this->request->getPost('faktor1id');
        $keterangan = $this->request->getPost('keterangan');
        if (!$faktor1id || !$this->userKodebpr || !$this->periodeId)
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak valid']);
        try {
            $this->db->table('risikooperasional_kpmr')->where('faktor1id', $faktor1id)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)->update(['keterangan' => $keterangan, 'user_id' => $this->userId, 'updated_at' => date('Y-m-d H:i:s'), 'is_approved' => 0, 'accdir2' => 0]);
            return $this->response->setJSON(['status' => 'success', 'message' => 'Keterangan berhasil diubah']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal: ' . $e->getMessage()]);
        }
    }

    public function simpanNilai13()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;
        $faktorId = 70;
        $keterangan = $this->request->getPost('keterangan');
        $this->nilaiModel->insertOrUpdateRataRata(0, $faktorId, $this->userKodebpr, $this->periodeId, $keterangan);
        return redirect()->back()->with('message', 'Kesimpulan berhasil disimpan atau diperbarui.');
    }

    public function saveKomentar()
    {
        $this->komentarModel->insert(['faktor1id' => $this->request->getPost('faktor_id'), 'kodebpr' => $this->request->getPost('kodebpr'), 'komentar' => $this->request->getPost('komentar'), 'is_read' => 0, 'created_at' => date('Y-m-d H:i:s'), 'user_id' => $this->userId]);
        return $this->response->setJSON(['status' => 'comment_saved']);
    }

    public function getNilai14()
    {
        if (!$this->userKodebpr || !$this->periodeId)
            return null;
        return $this->nilaiModel->where('faktor1id', 71)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)->first();
    }

    public function checkPrevPeriode()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error']);
        }
        if (!$this->userKodebpr || !$this->periodeId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak valid']);
        }

        $currentPeriode = $this->periodeModel->find($this->periodeId);
        if (!$currentPeriode) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Periode tidak ditemukan']);
        }

        $prevPeriode = $this->resolvePrevPeriode($currentPeriode);
        if (!$prevPeriode) {
            return $this->response->setJSON([
                'status' => 'no_prev',
                'message' => 'Tidak ada periode sebelumnya yang terdaftar',
            ]);
        }

        $currentCount = (int) $this->db->table('risikooperasional_kpmr')
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->whereIn('faktor1id', range(51, 69))
            ->where('penilaiankredit IS NOT NULL')
            ->countAllResults();

        $prevCount = (int) $this->db->table('risikooperasional_kpmr')
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $prevPeriode['id'])
            ->whereIn('faktor1id', range(51, 69))
            ->where('penilaiankredit IS NOT NULL')
            ->countAllResults();

        if ($prevCount === 0) {
            return $this->response->setJSON([
                'status' => 'no_data',
                'message' => "Tidak ada data pada periode Semester {$prevPeriode['semester']} Tahun {$prevPeriode['tahun']}",
                'prev_info' => [
                    'semester' => $prevPeriode['semester'],
                    'tahun' => $prevPeriode['tahun'],
                ],
            ]);
        }

        return $this->response->setJSON([
            'status' => 'available',
            'prev_info' => [
                'semester' => $prevPeriode['semester'],
                'tahun' => $prevPeriode['tahun'],
                'faktor_count' => $prevCount,
            ],
            'current_has_data' => $currentCount > 0,
            'message' => "Data periode Semester {$prevPeriode['semester']} Tahun {$prevPeriode['tahun']} tersedia ({$prevCount} faktor)",
        ]);
    }

    public function syncFromPrevPeriode()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error']);
        }
        if (!$this->userKodebpr || !$this->periodeId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak valid']);
        }

        $forceOverwrite = (int) $this->request->getPost('force_overwrite') === 1;

        $currentPeriode = $this->periodeModel->find($this->periodeId);
        if (!$currentPeriode) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Periode tidak ditemukan']);
        }

        $prevPeriode = $this->resolvePrevPeriode($currentPeriode);
        if (!$prevPeriode) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Periode sebelumnya tidak ditemukan']);
        }

        $syncFaktorIds = range(51, 69);

        $prevRows = $this->db->table('risikooperasional_kpmr')
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $prevPeriode['id'])
            ->whereIn('faktor1id', $syncFaktorIds)
            ->get()->getResultArray();

        if (empty($prevRows)) {
            return $this->response->setJSON([
                'status' => 'no_data',
                'message' => "Tidak ada data pada periode Semester {$prevPeriode['semester']} Tahun {$prevPeriode['tahun']}",
            ]);
        }

        $existingRows = $this->db->table('risikooperasional_kpmr')
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->whereIn('faktor1id', $syncFaktorIds)
            ->get()->getResultArray();

        $existingLookup = [];
        foreach ($existingRows as $row) {
            $existingLookup[(int) $row['faktor1id']] = $row;
        }

        $user = $this->userModel->find($this->userId);
        $now = date('Y-m-d H:i:s');

        $copyFields = ['penilaiankredit', 'penjelasanpenilaian', 'keterangan'];

        $toInsert = [];
        $toUpdate = [];
        $syncedFaktors = [];
        $skippedFaktors = [];

        foreach ($prevRows as $prev) {
            $fid = (int) $prev['faktor1id'];
            if (empty($prev['penilaiankredit'])) {
                continue;
            }

            $rowData = [];
            foreach ($copyFields as $field) {
                $rowData[$field] = $prev[$field] ?? null;
            }
            $rowData['user_id'] = $this->userId;
            $rowData['fullname'] = $user['fullname'] ?? 'Unknown';
            $rowData['kodebpr'] = $this->userKodebpr;
            $rowData['periode_id'] = $this->periodeId;
            $rowData['faktor1id'] = $fid;
            $rowData['is_approved'] = 0;
            $rowData['accdir2'] = 0;
            $rowData['is_manual'] = 0;

            if (isset($existingLookup[$fid])) {
                $existing = $existingLookup[$fid];
                if (!$forceOverwrite && !empty($existing['penilaiankredit'])) {
                    $skippedFaktors[] = $fid;
                    continue;
                }
                $toUpdate[] = ['id' => $existing['id'], 'data' => array_merge($rowData, ['updated_at' => $now])];
            } else {
                $toInsert[] = array_merge($rowData, ['created_at' => $now]);
            }
            $syncedFaktors[] = $fid;
        }

        if (empty($syncedFaktors)) {
            return $this->response->setJSON([
                'status' => 'no_change',
                'message' => empty($skippedFaktors)
                    ? 'Tidak ada data yang bisa disinkronisasi'
                    : 'Semua data sudah terisi. Centang "Timpa data" jika ingin mengganti.',
                'skipped' => count($skippedFaktors),
            ]);
        }

        $this->db->transStart();

        try {
            if (!empty($toInsert)) {
                $this->db->table('risikooperasional_kpmr')->insertBatch($toInsert);
            }

            foreach ($toUpdate as $upd) {
                $this->db->table('risikooperasional_kpmr')
                    ->where('id', $upd['id'])
                    ->update($upd['data']);
            }

            foreach (self::CATEGORY_MAPPING as $catId => $childIds) {
                if (array_intersect($syncedFaktors, $childIds)) {
                    $this->recalculateAfterChildChange(reset($childIds));
                }
            }

            $categoryFaktors = array_keys(self::CATEGORY_MAPPING);
            if (array_intersect($syncedFaktors, $categoryFaktors)) {
                $rataRata = $this->nilaiModel->hitungRataRata(70, $this->userKodebpr, $this->periodeId);
                $this->nilaiModel->insertOrUpdateRataRata($rataRata, 70, $this->userKodebpr, $this->periodeId);
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                throw new \Exception('Transaksi database gagal');
            }

            $msg = sprintf(
                '%d faktor berhasil disinkronisasi dari Semester %s Tahun %s',
                count($syncedFaktors),
                $prevPeriode['semester'],
                $prevPeriode['tahun']
            );
            if (!empty($skippedFaktors)) {
                $msg .= sprintf('. %d faktor dilewati (sudah terisi)', count($skippedFaktors));
            }

            return $this->response->setJSON([
                'status' => 'success',
                'message' => $msg,
                'synced_count' => count($syncedFaktors),
                'skipped_count' => count($skippedFaktors),
                'prev_periode' => [
                    'semester' => $prevPeriode['semester'],
                    'tahun' => $prevPeriode['tahun'],
                ],
            ]);

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'syncFromPrevPeriode [OperasionalKPMR] error: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Gagal sinkronisasi: ' . $e->getMessage(),
            ]);
        }
    }

    private function resolvePrevPeriode(array $currentPeriode): ?array
    {
        $semester = (int) $currentPeriode['semester'];
        $tahun = (int) $currentPeriode['tahun'];

        [$prevSemester, $prevTahun] = $semester === 1
            ? [2, $tahun - 1]
            : [1, $tahun];

        return $this->periodeModel
            ->where('semester', $prevSemester)
            ->where('tahun', $prevTahun)
            ->first() ?: null;
    }
}