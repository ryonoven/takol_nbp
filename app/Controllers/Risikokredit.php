<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_paramprofilrisiko;
use App\Models\M_user;
use App\Models\M_profilrisikocomments;
use App\Models\M_nilairisikokredit;
use App\Models\M_infobpr;
use App\Models\M_periodesemesteran;
use App\Models\M_profilrisikocommentsread;
use App\Models\M_kalkulatorkredit;
use App\Models\M_showprofilresiko;
use App\Models\M_showtks;
use App\Models\M_periodetks;
use Myth\Auth\Config\Services as AuthServices;

class Risikokredit extends Controller
{
    protected $db;
    protected $auth;
    protected $paramprofilrisikoModel;
    protected $showprofilresikoModel;
    protected $kalkulatorModel;
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
    protected $periodetksModel;
    protected $periodesemesteranModel;
    protected $userGroups = [];

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');

        $this->db = \Config\Database::connect();

        $this->paramprofilrisikoModel = new M_paramprofilrisiko();
        $this->periodeModel = new M_periodesemesteran();
        $this->userModel = new M_user();
        $this->komentarModel = new M_profilrisikocomments();
        $this->showprofilresikoModel = new M_showprofilresiko();
        $this->nilaiModel = new M_nilairisikokredit();
        $this->infobprModel = new M_infobpr();
        $this->commentReadsModel = new M_profilrisikocommentsread();
        $this->kalkulatorModel = new M_kalkulatorkredit();
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
        foreach (['pe', 'admin', 'dekom', 'dekom2', 'dekom3', 'dekom4', 'dekom5', 'direksi', 'direksi2'] as $g) {
            $this->userGroups[$g] = $authorize->inGroup($g, $this->userId);
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
        if (!$this->periodeId)
            return redirect()->to('/periodeprofilresiko');
        return null;
    }

    private function checkPeriode()
    {
        if (!$this->periodeId) {
            return redirect()->to('/Periodeprofilresiko')
                ->with('error', 'Silakan pilih periode aktif terlebih dahulu.');
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

        $nilai13 = $this->nilaiModel->where(['faktor1id' => 13, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first();
        $nilai14 = $this->nilaiModel->where(['faktor1id' => 14, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first();

        // TAMBAHKAN BLOK INI
        $nilaiData = $this->db->table('risikokredit as r')
            ->select('r.*, u.fullname')
            ->join('users as u', 'u.id = r.user_id', 'left')
            ->where('r.kodebpr', $this->userKodebpr)
            ->where('r.periode_id', $this->periodeId)
            ->whereIn('r.faktor1id', range(1, 13))
            ->get()->getResultArray();

        $nilaiLookup = [];
        foreach ($nilaiData as $n) {
            $nilaiLookup[(int) $n['faktor1id']] = $n;
        }

        $unreadCounts = $this->commentReadsModel->getAllUnreadCountsBatch(
            'KREDITINHEREN',
            $this->userKodebpr,
            $this->userId,
            $this->periodeId,
            range(1, 13)
        );

        $showtks = $this->showtksModel->getByKodebprAndPeriode($this->userKodebpr, $this->periodeId);
        if ($showtks && !empty($showtks['tanggal'])) {
            $showtks['tanggal_display'] = $this->formatDateForDisplay($showtks['tanggal']);
        } else {
            $showtks['tanggal_display'] = '';
        }

        $requiredFaktor = array_diff(array_merge(range(1, 11), [13]), [12]);
        $totalRequired = count($requiredFaktor);

        $approvalData = $this->nilaiModel
            ->select('COUNT(CASE WHEN penilaiankredit IS NOT NULL THEN 1 END) AS filled_count, COUNT(CASE WHEN is_approved = 1 THEN 1 END) AS approved_count')
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->whereIn('faktor1id', $requiredFaktor)
            ->first();

        $allFilled = ((int) $approvalData['filled_count'] === $totalRequired);
        $allApproved = ($allFilled && (int) $approvalData['approved_count'] === $totalRequired);
        $canApprove = $allFilled;

        $kalkulatorData = $this->kalkulatorModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->first() ?? $this->defaultKalkulatorData();

        $user = $this->userModel->find($this->userId);

        $data = [
            'judul' => 'Penilaian Risiko Kredit Inheren',
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
            'penilaianConfig' => $this->getPenilaianConfig(),
            'nilai13' => $nilai13,
            'nilai14' => $nilai14,
            'kalkulatorData' => $kalkulatorData,
            'loadDataViaAjax' => true,
            'showtks' => $showtks,
            'nilaiLookup' => $nilaiLookup,
            'counts' => $unreadCounts,

        ];

        return view('templates/v_header', $data)
            . view('templates/v_sidebar')
            . view('templates/v_topbar')
            . view('Laporansemester/Profilrisiko/kreditinheren', $data)
            . view('templates/v_footer');
    }

    public function getFactorsData()
    {
        if (!$this->request->isAJAX())
            return $this->response->setStatusCode(403);

        $nilaiData = $this->db->table('risikokredit as r')
            ->select('r.*, u.fullname')
            ->join('users as u', 'u.id = r.user_id', 'left')
            ->where('r.kodebpr', $this->userKodebpr)
            ->where('r.periode_id', $this->periodeId)
            ->whereIn('r.faktor1id', range(1, 13))
            ->get()->getResultArray();

        $nilaiLookup = [];
        foreach ($nilaiData as $n) {
            $nilaiLookup[(int) $n['faktor1id']] = $n;
        }

        $structure = $this->buildFactorStructure($nilaiLookup);

        $etag = '"' . md5(json_encode(array_column($nilaiData, 'updated_at'))) . '"';
        $ifNoneMatch = $this->request->getHeaderLine('If-None-Match');

        if ($ifNoneMatch === $etag) {
            return $this->response
                ->setStatusCode(304)
                ->setHeader('ETag', $etag)
                ->setHeader('Cache-Control', 'no-cache');
        }

        return $this->response
            ->setHeader('ETag', $etag)
            ->setHeader('Cache-Control', 'no-cache')
            ->setJSON(['status' => 'success', 'data' => $structure]);
    }

    private function buildFactorStructure(array $nilaiLookup): array
    {
        $structure = [
            [
                'id' => 1,
                'title' => 'Pilar Komposisi Portofolio Aset dan Tingkat Konsentrasi Kredit',
                'type' => 'category',
                'faktor_id' => 1,
                'children' => [
                    ['id' => 2, 'title' => 'Parameter rasio aset produktif terhadap total aset', 'faktor_id' => 2],
                    ['id' => 3, 'title' => 'Parameter rasio kredit yang diberikan terhadap total aset produktif', 'faktor_id' => 3],
                    ['id' => 4, 'title' => 'Parameter rasio 25 debitur terbesar terhadap total kredit yang diberikan', 'faktor_id' => 4],
                    ['id' => 5, 'title' => 'Parameter rasio kredit per sektor ekonomi terhadap total kredit yang diberikan', 'faktor_id' => 5],
                ],
            ],
            [
                'id' => 6,
                'title' => 'Pilar Kualitas Aset',
                'type' => 'category',
                'faktor_id' => 6,
                'children' => [
                    ['id' => 7, 'title' => 'Parameter rasio aset produktif bermasalah terhadap total aset produktif', 'faktor_id' => 7],
                    ['id' => 8, 'title' => 'Parameter kredit bermasalah neto terhadap total kredit yang diberikan', 'faktor_id' => 8],
                    ['id' => 9, 'title' => 'Parameter kredit kualitas rendah terhadap total kredit yang diberikan', 'faktor_id' => 9],
                ],
            ],
            ['id' => 10, 'title' => 'Pilar Strategi Penyediaan Dana', 'type' => 'single', 'faktor_id' => 10],
            ['id' => 11, 'title' => 'Pilar Faktor Eksternal', 'type' => 'single', 'faktor_id' => 11],
            ['id' => 12, 'title' => 'Faktor Lainnya', 'type' => 'single', 'faktor_id' => 12],
        ];

        foreach ($structure as &$item) {
            $item['nilai'] = $nilaiLookup[$item['faktor_id']] ?? null;
            if (isset($item['children'])) {
                foreach ($item['children'] as &$child) {
                    $child['nilai'] = $nilaiLookup[$child['faktor_id']] ?? null;
                }
                unset($child);
            }
        }
        unset($item);

        return $structure;
    }

    public function getAllUnreadCounts()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }
        if (!$this->userKodebpr || !$this->userId || !$this->periodeId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Missing data']);
        }

        $unreadCounts = $this->commentReadsModel->getAllUnreadCountsBatch(
            'KREDITINHEREN',
            $this->userKodebpr,
            $this->userId,
            $this->periodeId,
            range(1, 13)
        );

        return $this->response
            ->setHeader('Cache-Control', 'private, max-age=30')
            ->setHeader('Vary', 'Cookie')
            ->setJSON(['status' => 'success', 'counts' => $unreadCounts]);
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
            'KREDITINHEREN',
            $this->userKodebpr,
            $this->userId,
            $this->periodeId
        );

        return $this->response->setJSON(['status' => 'success', 'message' => 'Comments marked as read', 'marked_count' => $marked]);
    }

    public function getUnreadCommentCountForFactor()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $faktorId = $this->request->getGet('faktor_id');
        if (!$faktorId || !$this->userKodebpr || !$this->userId || !$this->periodeId) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Missing data.']);
        }

        $count = $this->commentReadsModel->countUnreadCommentsForUserByFactor(
            $faktorId,
            'KREDITINHEREN',
            $this->userKodebpr,
            $this->userId,
            $this->periodeId
        );

        return $this->response->setJSON(['unread_count' => $count]);
    }

    public function getKomentarByFaktorId($faktorId)
    {
        if (!$this->request->isAJAX() || !is_numeric($faktorId)) {
            return $this->response->setStatusCode(404)->setBody('Not Found');
        }

        $list = $this->komentarModel->getKomentarByFaktorId(
            'KREDITINHEREN',
            $faktorId,
            $this->userKodebpr,
            $this->periodeId
        );

        return $this->response->setJSON($list);
    }

    public function tambahKomentar()
    {
        $isAjax = $this->request->isAJAX();

        if (!$isAjax && !isset($_POST['tambahKomentar'])) {
            return redirect()->to(base_url('Risikokredit'));
        }

        if (!$this->validate(['komentar' => 'required'])) {
            if ($isAjax)
                return $this->response->setJSON(['status' => 'error', 'message' => 'Komentar tidak boleh kosong']);
            return redirect()->back()->with('err', $this->validator->listErrors());
        }

        $faktorId = $this->request->getPost('faktor_id');
        $komentar = $this->request->getPost('komentar');

        if (!$faktorId) {
            if ($isAjax)
                return $this->response->setJSON(['status' => 'error', 'message' => 'ID tidak ditemukan']);
            return redirect()->back()->with('err', 'ID tidak ditemukan');
        }

        try {
            $result = $this->komentarModel->insert([
                'subkategori' => 'KREDITINHEREN',
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
                    'comments' => $this->komentarModel->getKomentarByFaktorId(
                        'KREDITINHEREN',
                        $faktorId,
                        $this->userKodebpr,
                        $this->periodeId
                    ),
                ]);
            }

            return redirect()->to(base_url('Risikokredit') . '?modal_komentar=' . $faktorId)
                ->with('message', 'Komentar berhasil ditambahkan');

        } catch (\Exception $e) {
            log_message('error', 'Error tambahKomentar: ' . $e->getMessage());
            if ($isAjax)
                return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menambahkan komentar']);
            return redirect()->back()->with('err', 'Gagal menambahkan komentar');
        }
    }

    public function cekKomentarBaru()
    {
        $subkategori = 'KREDITINHEREN';
        $kodebpr = $this->request->getGet('kodebpr');
        $lastVisit = $this->request->getGet('last_visit');

        $results = $this->komentarModel
            ->select('faktor1id, COUNT(*) as jumlah')
            ->where('subkategori', $subkategori)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('created_at >', $lastVisit)
            ->groupBy('faktor1id')
            ->findAll();

        return $this->response->setJSON($results);
    }

    public function saveKomentar()
    {
        $data = [
            'faktor1id' => $this->request->getPost('faktor_id'),
            'kodebpr' => $this->request->getPost('kodebpr'),
            'komentar' => $this->request->getPost('komentar'),
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'user_id' => $this->userId,
        ];

        $this->komentarModel->insert($data);
        return $this->response->setJSON(['status' => 'comment_saved']);
    }

    public function ubah()
    {
        if (!$this->request->isAJAX())
            return redirect()->back()->with('error', 'Invalid request');

        $faktor1id = $this->request->getPost('faktor1id');
        if (!$faktor1id || !$this->userKodebpr || !$this->periodeId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak valid']);
        }

        $existing = $this->nilaiModel
            ->where('faktor1id', $faktor1id)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
            ->first();

        $data = [
            'penilaiankredit' => $this->request->getPost('penilaiankredit'),
            'penjelasanpenilaian' => $this->request->getPost('penjelasanpenilaian'),
            'keterangan' => $this->request->getPost('keterangan'),
            'is_approved' => 0,
            'accdir2' => 0,
            'user_id' => $this->userId,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($existing && !empty($existing['rasiokredit']))
            $data['rasiokredit'] = $existing['rasiokredit'];

        try {
            $this->db->table('risikokredit')
                ->where('faktor1id', $faktor1id)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
                ->update($data);

            $this->handleCategoryAndGlobalRecalc((int) $faktor1id);

            $nilai13 = $this->nilaiModel->where(['faktor1id' => 13, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first();

            return $this->response->setJSON(['status' => 'success', 'message' => 'Data berhasil diubah', 'peringkat13' => $nilai13['penilaiankredit'] ?? null]);

        } catch (\Exception $e) {
            log_message('error', 'Error ubah: ' . $e->getMessage());
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menyimpan data: ' . $e->getMessage()]);
        }
    }

    public function ubahkesimpulan()
    {
        if (!$this->request->isAJAX())
            return redirect()->back()->with('error', 'Invalid request');

        $faktor1id = $this->request->getPost('faktor1id');
        $keterangan = $this->request->getPost('keterangan');

        if (!$faktor1id || !$this->userKodebpr || !$this->periodeId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak valid']);
        }

        $existing = $this->nilaiModel
            ->where('faktor1id', $faktor1id)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
            ->first();

        if (!$existing)
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak ditemukan']);

        try {
            $this->db->table('risikokredit')
                ->where('faktor1id', $faktor1id)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
                ->update([
                    'keterangan' => $keterangan,
                    'penilaiankredit' => $existing['penilaiankredit'],
                    'user_id' => $this->userId,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'is_approved' => 0,
                ]);

            $this->db->table('risikokredit')
                ->where('faktor1id', 13)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
                ->update(['accdir2' => 0, 'is_approved' => 0]);

            return $this->response->setJSON(['status' => 'success', 'message' => 'Keterangan kesimpulan berhasil diubah']);

        } catch (\Exception $e) {
            log_message('error', 'Error ubahkesimpulan: ' . $e->getMessage());
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function ubahketerangan()
    {
        if (!$this->request->isAJAX())
            return redirect()->back()->with('error', 'Invalid request');

        $faktor1id = $this->request->getPost('faktor1id');
        $keterangan = $this->request->getPost('keterangan');

        if (!$faktor1id || !$this->userKodebpr || !$this->periodeId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak valid']);
        }

        $existing = $this->nilaiModel
            ->where('faktor1id', $faktor1id)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
            ->first();

        if (!$existing)
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak ditemukan']);

        $data = [
            'keterangan' => $keterangan,
            'penilaiankredit' => $existing['penilaiankredit'],
            'penjelasanpenilaian' => $existing['penjelasanpenilaian'],
            'user_id' => $this->userId,
            'updated_at' => date('Y-m-d H:i:s'),
            'is_approved' => 0,
            'accdir2' => 0,
        ];
        if (!empty($existing['rasiokredit']))
            $data['rasiokredit'] = $existing['rasiokredit'];

        try {
            $this->db->table('risikokredit')
                ->where('faktor1id', $faktor1id)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
                ->update($data);

            return $this->response->setJSON(['status' => 'success', 'message' => 'Keterangan berhasil diubah']);

        } catch (\Exception $e) {
            log_message('error', 'Error ubahketerangan: ' . $e->getMessage());
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function tambahNilai()
    {
        if (!$this->request->isAJAX())
            return redirect()->back()->with('error', 'Invalid request');

        if (!$this->validate(['penilaiankredit' => 'required', 'keterangan' => 'required'])) {
            return $this->response->setJSON(['status' => 'error', 'message' => $this->validator->listErrors()]);
        }

        $faktorId = $this->request->getPost('faktor_id');
        if (!$faktorId)
            return $this->response->setJSON(['status' => 'error', 'message' => 'ID tidak ditemukan']);

        $user = $this->userModel->find($this->userId);
        $existing = $this->nilaiModel
            ->where('faktor1id', $faktorId)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
            ->first();

        $data = [
            'faktor1id' => $faktorId,
            'penilaiankredit' => $this->request->getPost('penilaiankredit'),
            'penjelasanpenilaian' => $this->request->getPost('penjelasanpenilaian'),
            'keterangan' => $this->request->getPost('keterangan'),
            'user_id' => $this->userId,
            'fullname' => $user['fullname'] ?? 'Unknown',
            'kodebpr' => $this->userKodebpr,
            'periode_id' => $this->periodeId,
            'is_approved' => 0,
            'accdir2' => 0,
        ];

        try {
            if ($existing) {
                if (!empty($existing['rasiokredit']))
                    $data['rasiokredit'] = $existing['rasiokredit'];
                $data['updated_at'] = date('Y-m-d H:i:s');
                $this->db->table('risikokredit')
                    ->where('faktor1id', $faktorId)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
                    ->update($data);
            } else {
                $data['created_at'] = date('Y-m-d H:i:s');
                $this->db->table('risikokredit')->insert($data);
            }

            $this->handleCategoryAndGlobalRecalc((int) $faktorId);

            $nilai13 = $this->nilaiModel->where(['faktor1id' => 13, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first();

            return $this->response->setJSON(['status' => 'success', 'message' => 'Nilai berhasil ditambahkan', 'peringkat13' => $nilai13['penilaiankredit'] ?? null]);

        } catch (\Exception $e) {
            log_message('error', 'Error tambahNilai: ' . $e->getMessage());
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menambahkan nilai: ' . $e->getMessage()]);
        }
    }

    public function tambahNilairasio()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;
        if (!isset($_POST['tambahNilairasio']))
            return redirect()->to(base_url('Risikokredit'));

        if (!$this->validate(['rasiokredit' => 'required', 'penilaiankredit' => 'required', 'keterangan' => 'required'])) {
            return redirect()->back()->with('err', $this->validator->listErrors());
        }
        if (!$this->userKodebpr)
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');

        $faktorId = $this->request->getPost('faktor_id');
        $user = $this->userModel->find($this->userId);

        $data = [
            'faktor1id' => $faktorId,
            'rasiokredit' => $this->request->getPost('rasiokredit'),
            'penilaiankredit' => $this->request->getPost('penilaiankredit'),
            'keterangan' => $this->request->getPost('keterangan'),
            'fullname' => $user['fullname'] ?? 'Unknown',
            'user_id' => $this->userId,
            'kodebpr' => $this->userKodebpr,
            'periode_id' => $this->periodeId,
            'is_approved' => 0,
            'accdir2' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $this->nilaiModel->insertNilai($data);

        $rataRata = $this->nilaiModel->hitungRataRata($faktorId, $this->userKodebpr, $this->periodeId);
        $rataRata = ($rataRata - floor($rataRata) >= 0.5) ? ceil($rataRata) : floor($rataRata);
        $this->nilaiModel->insertOrUpdateRataRata($rataRata, 1, $this->userKodebpr, $this->periodeId);

        return redirect()->to(base_url('Risikokredit') . '?modal_nilai=' . $faktorId)->with('message', 'Nilai berhasil ditambahkan');
    }

    public function ubahPeringkat13Manual()
    {
        if (!$this->request->isAJAX())
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid request']);

        $penilaiankredit = $this->request->getPost('penilaiankredit');
        if (!in_array($penilaiankredit, ['1', '2', '3', '4', '5'])) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Pilih peringkat yang valid']);
        }

        $labels = ['1' => 'Sangat Rendah', '2' => 'Rendah', '3' => 'Sedang', '4' => 'Tinggi', '5' => 'Sangat Tinggi'];

        try {
            $this->nilaiModel
                ->where('faktor1id', 13)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
                ->set([
                    'penilaiankredit' => $penilaiankredit,
                    'penjelasanpenilaian' => 'Tingkat Risiko Kredit Inheren: ' . $labels[$penilaiankredit],
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
            $rataRata = $this->nilaiModel->hitungRataRata(13, $this->userKodebpr, $this->periodeId);
            $rataRata = ($rataRata - floor($rataRata) >= 0.5) ? ceil($rataRata) : floor($rataRata);

            $labels = ['1' => 'Sangat Rendah', '2' => 'Rendah', '3' => 'Sedang', '4' => 'Tinggi', '5' => 'Sangat Tinggi'];

            $this->nilaiModel
                ->where('faktor1id', 13)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
                ->set([
                    'penilaiankredit' => $rataRata,
                    'penjelasanpenilaian' => 'Tingkat Risiko Kredit Inheren: ' . ($labels[$rataRata] ?? ''),
                    'is_manual' => 0,
                    'updated_at' => date('Y-m-d H:i:s'),
                ])->update();

            return $this->response->setJSON(['status' => 'success', 'peringkat' => $rataRata, 'is_manual' => false]);

        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function simpanNilai13()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;

        $faktorId = 13;
        $rataRata = 0;
        $keterangan = $this->request->getPost('keterangan');

        $this->nilaiModel->insertOrUpdateRataRata($rataRata, $faktorId, $this->userKodebpr, $this->periodeId, $keterangan);

        return redirect()->back()->with('message', 'Kesimpulan berhasil disimpan atau diperbarui.');
    }

    public function simpanKesimpulan13()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;

        if (!$this->validate(['keterangan' => 'required'])) {
            return redirect()->back()->with('err', 'Keterangan kesimpulan harus diisi');
        }
        if (!$this->userKodebpr || !$this->periodeId) {
            return redirect()->back()->with('error', 'Data user atau periode tidak valid');
        }

        $faktorId = 13;
        $keterangan = $this->request->getPost('keterangan');
        $user = $this->userModel->find($this->userId);

        try {
            $existing = $this->nilaiModel
                ->where('faktor1id', $faktorId)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
                ->first();

            if ($existing) {
                $this->nilaiModel
                    ->where('faktor1id', $faktorId)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
                    ->set(['keterangan' => $keterangan, 'user_id' => $this->userId, 'fullname' => $user['fullname'] ?? 'Unknown', 'updated_at' => date('Y-m-d H:i:s')])
                    ->update();
                return redirect()->to(base_url('Risikokredit'))->with('message', 'Kesimpulan berhasil diupdate');
            }

            $rataRata = $this->nilaiModel->hitungRataRata($faktorId, $this->userKodebpr, $this->periodeId);
            $rataRata = ($rataRata - floor($rataRata) >= 0.5) ? ceil($rataRata) : floor($rataRata);
            $labels = ['1' => 'Sangat Rendah', '2' => 'Rendah', '3' => 'Sedang', '4' => 'Tinggi', '5' => 'Sangat Tinggi'];

            $this->nilaiModel->insert([
                'faktor1id' => $faktorId,
                'penilaiankredit' => $rataRata,
                'penjelasanpenilaian' => 'Tingkat Risiko Kredit Inheren: ' . ($labels[$rataRata] ?? 'N/A'),
                'keterangan' => $keterangan,
                'fullname' => $user['fullname'] ?? 'Unknown',
                'user_id' => $this->userId,
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId,
                'is_approved' => 0,
                'accdir2' => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            return redirect()->to(base_url('Risikokredit'))->with('message', 'Kesimpulan berhasil disimpan');

        } catch (\Exception $e) {
            log_message('error', 'Error simpanKesimpulan13: ' . $e->getMessage());
            return redirect()->back()->with('err', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function simpanNilai14()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;

        if (!$this->validate(['penilaiankredit' => 'required|in_list[1,2,3,4,5]'])) {
            return redirect()->back()->with('err', 'Pilih tingkat risiko terlebih dahulu');
        }
        if (!$this->userKodebpr || !$this->periodeId) {
            return redirect()->back()->with('error', 'Data user atau periode tidak valid');
        }

        $penilaiankredit = $this->request->getPost('penilaiankredit');
        $user = $this->userModel->find($this->userId);
        $labels = ['1' => 'Sangat Rendah', '2' => 'Rendah', '3' => 'Sedang', '4' => 'Tinggi', '5' => 'Sangat Tinggi'];
        $penjelasan = 'Tingkat Risiko Kredit Inheren: ' . ($labels[$penilaiankredit] ?? '');

        try {
            $existing = $this->nilaiModel
                ->where('faktor1id', 14)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
                ->first();

            $base = [
                'penilaiankredit' => $penilaiankredit,
                'penjelasanpenilaian' => $penjelasan,
                'keterangan' => 'Tingkat Risiko Kredit Inheren Posisi Sebelumnya',
                'user_id' => $this->userId,
                'fullname' => $user['fullname'] ?? 'Unknown',
            ];

            if ($existing) {
                $this->nilaiModel
                    ->where('faktor1id', 14)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
                    ->set(array_merge($base, ['updated_at' => date('Y-m-d H:i:s')]))->update();
            } else {
                $this->nilaiModel->insert(array_merge($base, [
                    'faktor1id' => 14,
                    'kodebpr' => $this->userKodebpr,
                    'periode_id' => $this->periodeId,
                    'is_approved' => 0,
                    'accdir2' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                ]));
            }

            return redirect()->to(base_url('Risikokredit'))->with('message', 'Data berhasil disimpan');

        } catch (\Exception $e) {
            log_message('error', 'Error simpanNilai14: ' . $e->getMessage());
            return redirect()->back()->with('err', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function approve($idNilai = null)
    {
        if ($idNilai === null)
            return redirect()->back()->with('err', 'ID Faktor tidak valid.');

        $this->db->table('risikokredit')
            ->where('id', $idNilai)
            ->update(['is_approved' => 1, 'approved_by' => $this->userId, 'approved_at' => date('Y-m-d H:i:s')]);

        return redirect()->back()->with('message', 'Data berhasil disetujui');
    }

    public function unapprove($idNilai)
    {
        if (!is_numeric($idNilai) || $idNilai <= 0)
            return redirect()->back()->with('err', 'ID Faktor tidak valid.');

        $nilaiFaktor = $this->nilaiModel->find($idNilai);
        if (!$nilaiFaktor)
            return redirect()->back()->with('err', 'Data tidak ditemukan.');

        if ($this->nilaiModel->update($idNilai, ['is_approved' => 0, 'approved_by' => $this->userId, 'approved_at' => date('Y-m-d H:i:s')])) {
            return redirect()->back()->with('message', 'Data approval dibatalkan.');
        }
        return redirect()->back()->with('err', 'Terjadi kesalahan saat membatalkan approval.');
    }

    public function approveSemua()
    {
        $isAjax = $this->request->isAJAX();

        if (!$this->userKodebpr || !$this->periodeId) {
            if ($isAjax)
                return $this->response->setJSON(['status' => 'error', 'message' => 'Kode BPR atau Periode ID tidak valid']);
            return redirect()->back()->with('err', 'Kode BPR atau Periode ID tidak valid');
        }

        $requiredFaktor = array_diff(array_merge(range(1, 11), [13]), [12]);
        $totalRequired = count($requiredFaktor);

        $checkData = $this->nilaiModel
            ->select('COUNT(CASE WHEN penilaiankredit IS NOT NULL THEN 1 END) AS filled_count')
            ->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
            ->whereIn('faktor1id', $requiredFaktor)->first();

        if ((int) $checkData['filled_count'] < $totalRequired) {
            if ($isAjax)
                return $this->response->setJSON(['status' => 'error', 'message' => 'Semua faktor wajib harus diisi terlebih dahulu']);
            return redirect()->back()->with('err', 'Semua faktor wajib harus diisi terlebih dahulu');
        }

        try {
            $this->db->table('risikokredit')
                ->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
                ->whereIn('faktor1id', $requiredFaktor)
                ->update(['is_approved' => 1, 'approved_by' => $this->userId, 'approved_at' => date('Y-m-d H:i:s')]);

            if ($isAjax)
                return $this->response->setJSON(['status' => 'success', 'message' => 'Semua data berhasil disetujui.']);
            return redirect()->back()->with('message', 'Semua data berhasil disetujui.');

        } catch (\Exception $e) {
            log_message('error', 'Error approveSemua: ' . $e->getMessage());
            if ($isAjax)
                return $this->response->setJSON(['status' => 'error', 'message' => 'Terjadi kesalahan sistem']);
            return redirect()->back()->with('err', 'Terjadi kesalahan sistem');
        }
    }

    public function unapproveSemua()
    {
        $isAjax = $this->request->isAJAX();

        if (!$this->userKodebpr || !$this->periodeId) {
            if ($isAjax)
                return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak valid']);
            return redirect()->back()->with('err', 'Data tidak valid');
        }

        try {
            $this->db->table('risikokredit')
                ->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
                ->whereIn('faktor1id', range(1, 13))
                ->update(['is_approved' => 0, 'approved_by' => $this->userId, 'approved_at' => date('Y-m-d H:i:s')]);

            if ($isAjax)
                return $this->response->setJSON(['status' => 'success', 'message' => 'Semua approval berhasil dibatalkan.']);
            return redirect()->back()->with('message', 'Semua data berhasil dibatalkan persetujuannya.');

        } catch (\Exception $e) {
            log_message('error', 'Error unapproveSemua: ' . $e->getMessage());
            if ($isAjax)
                return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
            return redirect()->back()->with('err', $e->getMessage());
        }
    }

    public function accdir2()
    {
        $faktor1id = $this->request->getPost('faktor1id');
        if (!$faktor1id)
            return redirect()->back()->with('error', 'Data tidak lengkap');

        $this->db->table('risikokredit')
            ->where('faktor1id', $faktor1id)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
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
            return redirect()->to('/Risikokredit')->with('error', 'Data tidak lengkap');

        $nilaiFaktor = $this->nilaiModel
            ->where('faktor1id', $faktor1id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
            ->first();

        if (!$nilaiFaktor)
            return redirect()->to('/Risikokredit')->with('error', 'Data tidak ditemukan');

        if ($this->nilaiModel->update($nilaiFaktor['id'], ['accdir2' => 0, 'accdir2_by' => $this->userId, 'approved_at' => date('Y-m-d H:i:s')])) {
            return redirect()->to('/Risikokredit')->with('message', 'Persetujuan dibatalkan');
        }

        return redirect()->to('/Risikokredit')->with('error', 'Gagal memperbarui data');
    }

    public function simpanKalkulator()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;
        if (!isset($_POST['simpanKalkulator']))
            return redirect()->to(base_url('Risikokredit'));
        if (!$this->userKodebpr)
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
        if (!$this->periodeId)
            return redirect()->back()->with('error', 'Periode tidak valid');

        if (
            !$this->validate([
                'aba' => 'required|decimal',
                'kydbank' => 'required|decimal',
                'kydpihak3' => 'required|decimal',
                'totalaset' => 'required|decimal',
                'total25debitur' => 'required|decimal',
                'perdagangan' => 'required|decimal',
                'jasa' => 'required|decimal',
                'konsumsirumah' => 'required|decimal',
            ])
        ) {
            return redirect()->back()->with('err', $this->validator->listErrors());
        }

        $aba = $this->request->getPost('aba');
        $kydbank = $this->request->getPost('kydbank');
        $kydpihak3 = $this->request->getPost('kydpihak3');
        $totalaset = $this->request->getPost('totalaset');
        $total25 = $this->request->getPost('total25debitur');
        $perdagangan = $this->request->getPost('perdagangan');
        $jasa = $this->request->getPost('jasa');
        $konsumsirumah = $this->request->getPost('konsumsirumah');

        $kydgross = $kydbank + $kydpihak3;
        $asetproduktif = $aba + $kydgross;
        $rasioasetproduktif = $totalaset > 0 ? ($asetproduktif / $totalaset) * 100 : 0;
        $rasiokreditdib = $asetproduktif > 0 ? ($kydgross / $asetproduktif) * 100 : 0;
        $rasio25debitur = $kydgross > 0 ? ($total25 / $kydgross) * 100 : 0;
        $sektorekonomi = $perdagangan + $jasa + $konsumsirumah;
        $rasioekonomi = $kydgross > 0 ? ($sektorekonomi / $kydgross) * 100 : 0;

        $user = $this->userModel->find($this->userId);

        $data = [
            'user_id' => $this->userId,
            'fullname' => $user['fullname'] ?? 'Unknown',
            'kodebpr' => $this->userKodebpr,
            'periode_id' => $this->periodeId,
            'aba' => $aba,
            'kydbank' => $kydbank,
            'kydpihak3' => $kydpihak3,
            'kydgross' => $kydgross,
            'totalaset' => $totalaset,
            'total25debitur' => $total25,
            'perdagangan' => $perdagangan,
            'jasa' => $jasa,
            'konsumsirumah' => $konsumsirumah,
            'sektorekonomi' => $sektorekonomi,
            'asetproduktif' => $asetproduktif,
            'rasioasetproduktif' => $rasioasetproduktif,
            'rasiokreditdiberikan' => $rasiokreditdib,
            'rasio25debitur' => $rasio25debitur,
            'rasioekonomi' => $rasioekonomi,
        ];

        try {
            $existing = $this->kalkulatorModel->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)->first();

            if ($existing) {
                $data['updated_at'] = date('Y-m-d H:i:s');
                $this->kalkulatorModel->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)->set($data)->update();
                return redirect()->to(base_url('Risikokredit'))->with('message', 'Data kalkulator berhasil diupdate');
            }

            $data['created_at'] = date('Y-m-d H:i:s');
            $this->kalkulatorModel->insert($data);
            return redirect()->to(base_url('Risikokredit'))->with('message', 'Data kalkulator berhasil disimpan');

        } catch (\Exception $e) {
            log_message('error', 'Error simpanKalkulator: ' . $e->getMessage());
            return redirect()->back()->with('err', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function insertRasioToKertasKerja()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;
        if (!$this->request->isAJAX())
            return redirect()->to(base_url('Risikokredit'));
        if (!$this->userKodebpr || !$this->periodeId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak valid']);
        }

        $aba = $this->request->getPost('aba');
        $kydbank = $this->request->getPost('kydbank');
        $kydpihak3 = $this->request->getPost('kydpihak3');
        $totalaset = $this->request->getPost('totalaset');
        $total25 = $this->request->getPost('total25debitur');
        $perdagangan = $this->request->getPost('perdagangan');
        $jasa = $this->request->getPost('jasa');
        $konsumsi = $this->request->getPost('konsumsirumah');

        $rasioAP = $this->request->getPost('rasioasetproduktif');
        $rasioKD = $this->request->getPost('rasiokreditdiberikan');
        $rasio25 = $this->request->getPost('rasio25debitur');
        $rasioE = $this->request->getPost('rasioekonomi');

        if (!$rasioAP && !$rasioKD && !$rasio25 && !$rasioE) {
            return $this->response->setJSON(['success' => false, 'message' => 'Tidak ada data rasio. Silakan hitung terlebih dahulu.']);
        }

        $kydgross = ($kydbank ?? 0) + ($kydpihak3 ?? 0);
        $asetproduktif = ($aba ?? 0) + $kydgross;
        $sektorekonomi = ($perdagangan ?? 0) + ($jasa ?? 0) + ($konsumsi ?? 0);
        $user = $this->userModel->find($this->userId);

        try {
            $existingKal = $this->kalkulatorModel->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)->first();

            $kalData = [
                'user_id' => $this->userId,
                'fullname' => $user['fullname'] ?? 'Unknown',
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId,
                'aba' => $aba ?? 0,
                'kydbank' => $kydbank ?? 0,
                'kydpihak3' => $kydpihak3 ?? 0,
                'totalaset' => $totalaset ?? 0,
                'total25debitur' => $total25 ?? 0,
                'perdagangan' => $perdagangan ?? 0,
                'jasa' => $jasa ?? 0,
                'konsumsirumah' => $konsumsi ?? 0,
                'kydgross' => $kydgross,
                'asetproduktif' => $asetproduktif,
                'sektorekonomi' => $sektorekonomi,
                'rasioasetproduktif' => $rasioAP ?? 0,
                'rasiokreditdiberikan' => $rasioKD ?? 0,
                'rasio25debitur' => $rasio25 ?? 0,
                'rasioekonomi' => $rasioE ?? 0,
            ];

            if ($existingKal) {
                $kalData['updated_at'] = date('Y-m-d H:i:s');
                $this->kalkulatorModel->update($existingKal['id'], $kalData);
            } else {
                $kalData['created_at'] = date('Y-m-d H:i:s');
                $this->kalkulatorModel->insert($kalData);
            }

            $rasioMapping = [2 => $rasioAP, 3 => $rasioKD, 4 => $rasio25, 5 => $rasioE];
            [$successCount, $processed] = $this->upsertRasioRows($rasioMapping, $user);

            if ($successCount > 0) {
                $this->calculateAndSaveCategoryAverage(1, [2, 3, 4, 5], $this->userKodebpr, $this->periodeId);
                $rataRata = $this->nilaiModel->hitungRataRata(13, $this->userKodebpr, $this->periodeId);
                $this->nilaiModel->insertOrUpdateRataRata($rataRata, 13, $this->userKodebpr, $this->periodeId);
            }

            return $this->response->setJSON(['success' => true, 'message' => "Berhasil memasukkan {$successCount} rasio ke kertas kerja", 'count' => $successCount, 'processed_faktors' => $processed]);

        } catch (\Exception $e) {
            log_message('error', 'Error insertRasioToKertasKerja: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function simpanKalkulatorKualitasAset()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;
        if (!isset($_POST['simpanKalkulatorKualitasAset']))
            return redirect()->to(base_url('Risikokredit'));
        if (!$this->userKodebpr)
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
        if (!$this->periodeId)
            return redirect()->back()->with('error', 'Periode tidak valid');

        if (
            !$this->validate([
                'abanpl' => 'required|decimal',
                'kydnpl3' => 'required|decimal',
                'kydnpl4' => 'required|decimal',
                'kydnpl5' => 'required|decimal',
                'kreditdpk2' => 'required|decimal',
                'kreditbermasalah' => 'required|decimal',
                'kreditrestruktur1' => 'required|decimal',
            ])
        ) {
            return redirect()->back()->with('err', $this->validator->listErrors());
        }

        $kalData1 = $this->kalkulatorModel->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)->first();
        if (!$kalData1 || empty($kalData1['asetproduktif']) || empty($kalData1['kydgross'])) {
            return redirect()->back()->with('error', 'Silakan isi Kalkulator Komposisi Portofolio Aset terlebih dahulu');
        }

        $asetproduktif = $kalData1['asetproduktif'];
        $kydgross = $kalData1['kydgross'];
        $abanpl = $this->request->getPost('abanpl');
        $kydnpl3 = $this->request->getPost('kydnpl3');
        $kydnpl4 = $this->request->getPost('kydnpl4');
        $kydnpl5 = $this->request->getPost('kydnpl5');
        $kreditdpk2 = $this->request->getPost('kreditdpk2');
        $kreditbermasalah = $this->request->getPost('kreditbermasalah');
        $kreditrestruktur = $this->request->getPost('kreditrestruktur1');

        $kydkoleknpl = $kydnpl3 + $kydnpl4 + $kydnpl5;
        $asetproduktifbermasalah = $abanpl + $kydkoleknpl;
        $kreditkualitasrendah = $kreditdpk2 + $kreditbermasalah + $kreditrestruktur;

        $rasioAPB = $asetproduktif > 0 ? ($asetproduktifbermasalah / $asetproduktif) * 100 : 0;
        $rasioKB = $kydgross > 0 ? ($kreditbermasalah / $kydgross) * 100 : 0;
        $rasioKKR = $kydgross > 0 ? ($kreditkualitasrendah / $kydgross) * 100 : 0;

        $data = [
            'abanpl' => $abanpl,
            'kydnpl3' => $kydnpl3,
            'kydnpl4' => $kydnpl4,
            'kydnpl5' => $kydnpl5,
            'kreditdpk2' => $kreditdpk2,
            'kreditrestruktur1' => $kreditrestruktur,
            'asetproduktifbermasalah' => $asetproduktifbermasalah,
            'kydkoleknpl' => $kydkoleknpl,
            'kreditbermasalah' => $kreditbermasalah,
            'rasioasetproduktifbermasalah' => $rasioAPB,
            'rasiokreditbermasalah' => $rasioKB,
            'rasiokreditkualitasrendah' => $rasioKKR,
        ];

        try {
            $existing = $this->kalkulatorModel->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)->first();
            if (!$existing)
                return redirect()->back()->with('error', 'Data kalkulator pertama belum ada.');

            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->kalkulatorModel->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)->set($data)->update();
            return redirect()->to(base_url('Risikokredit'))->with('message', 'Data kalkulator kualitas aset berhasil diupdate');

        } catch (\Exception $e) {
            log_message('error', 'Error simpanKalkulatorKualitasAset: ' . $e->getMessage());
            return redirect()->back()->with('err', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function insertRasioKualitasAsetToKertasKerja()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;
        if (!$this->userKodebpr || !$this->periodeId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak valid']);
        }

        $abanpl = $this->request->getPost('abanpl');
        $kydnpl3 = $this->request->getPost('kydnpl3');
        $kydnpl4 = $this->request->getPost('kydnpl4');
        $kydnpl5 = $this->request->getPost('kydnpl5');
        $kreditdpk2 = $this->request->getPost('kreditdpk2');
        $kreditbmsalah = $this->request->getPost('kreditbermasalah');
        $kreditrstr = $this->request->getPost('kreditrestruktur1');

        $rasioAPB = $this->request->getPost('rasioasetproduktifbermasalah');
        $rasioKB = $this->request->getPost('rasiokreditbermasalah');
        $rasioKKR = $this->request->getPost('rasiokreditkualitasrendah');

        if (!$rasioAPB && !$rasioKB && !$rasioKKR) {
            return $this->response->setJSON(['success' => false, 'message' => 'Tidak ada data rasio. Silakan hitung terlebih dahulu.']);
        }

        $user = $this->userModel->find($this->userId);

        try {
            $existingKal = $this->kalkulatorModel->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)->first();
            if (!$existingKal) {
                return $this->response->setJSON(['success' => false, 'message' => 'Isi Kalkulator Komposisi Portofolio Aset terlebih dahulu.']);
            }

            $kydkolek = ($kydnpl3 ?? 0) + ($kydnpl4 ?? 0) + ($kydnpl5 ?? 0);
            $asetprodukBmsalah = ($abanpl ?? 0) + $kydkolek;

            $this->kalkulatorModel->update($existingKal['id'], [
                'abanpl' => $abanpl ?? 0,
                'kydnpl3' => $kydnpl3 ?? 0,
                'kydnpl4' => $kydnpl4 ?? 0,
                'kydnpl5' => $kydnpl5 ?? 0,
                'kreditdpk2' => $kreditdpk2 ?? 0,
                'kreditbermasalah' => $kreditbmsalah ?? 0,
                'kreditrestruktur1' => $kreditrstr ?? 0,
                'kydkoleknpl' => $kydkolek,
                'asetproduktifbermasalah' => $asetprodukBmsalah,
                'rasioasetproduktifbermasalah' => $rasioAPB ?? 0,
                'rasiokreditbermasalah' => $rasioKB ?? 0,
                'rasiokreditkualitasrendah' => $rasioKKR ?? 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $rasioMapping = [7 => $rasioAPB, 8 => $rasioKB, 9 => $rasioKKR];
            [$successCount, $processed] = $this->upsertRasioRows($rasioMapping, $user);

            if ($successCount > 0) {
                $this->calculateAndSaveCategoryAverage(6, [7, 8, 9], $this->userKodebpr, $this->periodeId);
                $rataRata = $this->nilaiModel->hitungRataRata(13, $this->userKodebpr, $this->periodeId);
                $this->nilaiModel->insertOrUpdateRataRata($rataRata, 13, $this->userKodebpr, $this->periodeId);
            }

            return $this->response->setJSON(['success' => true, 'message' => "Berhasil memasukkan {$successCount} rasio kualitas aset", 'count' => $successCount, 'processed_faktors' => $processed]);

        } catch (\Exception $e) {
            log_message('error', 'Error insertRasioKualitasAsetToKertasKerja: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function recalculateCategoryAverage()
    {
        if (!$this->request->isAJAX())
            return $this->response->setStatusCode(403);

        $faktorId = $this->request->getPost('faktor_id');
        $categoryMapping = [1 => [2, 3, 4, 5], 6 => [7, 8, 9]];

        foreach ($categoryMapping as $categoryId => $childrenIds) {
            if (in_array($faktorId, $childrenIds)) {
                $this->calculateAndSaveCategoryAverage($categoryId, $childrenIds, $this->userKodebpr, $this->periodeId);

                $rataRata = $this->nilaiModel->hitungRataRata(13, $this->userKodebpr, $this->periodeId);
                $this->nilaiModel->insertOrUpdateRataRata($rataRata, 13, $this->userKodebpr, $this->periodeId);
                return $this->response->setJSON(['status' => 'success', 'message' => 'Kategori berhasil di-recalculate']);
            }
        }

        return $this->response->setJSON(['status' => 'error', 'message' => 'Kategori tidak ditemukan']);
    }

    public function exporttxtrisikokredit()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');
        $periodeDetail = $this->periodeModel->find($this->periodeId);

        $modalinti = $periodeDetail['modalinti'];
        $kategori = $periodeDetail['kategori'];
        $nomorsurat = $periodeDetail['nomorsurat'];
        $jeniskoreksi = $periodeDetail['jeniskoreksi'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];

        $tahun = $periodeDetail['tahun'] ?? date('Y');
        $semester = $periodeDetail['semester'] ?? '';

        $exportDate = (strtolower($semester) === 'ganjil' || $semester === '1')
            ? "{$tahun}-06-30"
            : "{$tahun}-12-31";

        $titleDate = str_replace('-', '', $exportDate);

        $sanitizeTxt = function ($text) {
            $text = str_replace(["\r", "\n"], ' ', $text);
            $text = str_replace(['"', '"', '%', '|'], ['"', '"', "'", "'", ' persen ', ' '], $text);
            $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
            return trim(preg_replace('/\s+/', ' ', $text));
        };

        $data_risikokredit = $this->nilaiModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'] ?? '';
            $kodejenis = $infobpr['kodejenis'] ?? '';
        }

        $kodeMap = [
            1 => '1210',
            2 => '1211',
            3 => '1212',
            4 => '1213',
            5 => '1214',
            6 => '1220',
            7 => '1221',
            8 => '1222',
            9 => '1223',
            10 => '1230',
            11 => '1240',
            12 => '1299',
            13 => '1292',
        ];

        $indexedData = [];
        foreach ($data_risikokredit as $row) {
            if (isset($row['faktor1id']))
                $indexedData[$row['faktor1id']] = $row;
        }

        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0101|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";

        foreach ($kodeMap as $faktorId => $kode) {
            if (isset($indexedData[$faktorId])) {
                $row = $indexedData[$faktorId];
                $rasio = $sanitizeTxt($row['rasiokredit'] ?? '');
                $pk = $sanitizeTxt($row['penilaiankredit'] ?? '');
                $ket = $sanitizeTxt($row['keterangan'] ?? '-');
            } else {
                $rasio = '';
                $pk = '';
                $ket = '';
            }
            $output .= "D01|{$kode}|{$rasio}|{$pk}|{$ket}\r\n";
        }

        $filename = "TKSBPRKS-0101-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }

    public function exportPDF()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;
        if (!$this->userKodebpr || !$this->periodeId)
            return redirect()->back()->with('error', 'Data tidak valid');

        $periodeDetail = $this->periodeModel->find($this->periodeId);
        $bprData = $this->infobprModel->where('kodebpr', $this->userKodebpr)->first();

        $nilaiData = $this->db->table('risikokredit as r')
            ->select('r.*, u.fullname')
            ->join('users as u', 'u.id = r.user_id', 'left')
            ->where('r.kodebpr', $this->userKodebpr)->where('r.periode_id', $this->periodeId)
            ->whereIn('r.faktor1id', range(1, 13))->orderBy('r.faktor1id', 'ASC')
            ->get()->getResultArray();

        $kalkulatorData = $this->kalkulatorModel->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)->first();
        $nilai13 = $this->nilaiModel->where(['faktor1id' => 13, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first();
        $nilai14 = $this->nilaiModel->where(['faktor1id' => 14, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first();

        return $this->response->setJSON([
            'status' => 'success',
            'data' => [
                'periode' => $periodeDetail,
                'bpr' => $bprData,
                'nilai' => $this->strukturDataNilai($nilaiData),
                'kalkulator' => $kalkulatorData,
                'nilai13' => $nilai13,
                'nilai14' => $nilai14,
                'kesimpulan' => $nilai13,
            ],
        ]);
    }

    public function viewPDFPreview()
    {
        return view('Risikokredit/export_pdf');
    }

    public function exportPDFGabungan()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;
        if (!$this->userKodebpr || !$this->periodeId)
            return redirect()->back()->with('error', 'Data tidak valid');

        return $this->response->setJSON([
            'status' => 'success',
            'data' => [
                'inheren' => $this->getDataInheren(),
                'kpmr' => $this->getDataKPMR(),
                'kepatuhan' => $this->getDataKepatuhanInheren(),
                'kepatuhankpmr' => $this->getDataKepatuhanKPMR(),
                'likuiditas' => $this->getDataLikuiditas(),
                'likuidiaskpmr' => $this->getDataLikuiditasKPMR(),
                'periode' => $this->periodeModel->find($this->periodeId),
                'bpr' => $this->infobprModel->where('kodebpr', $this->userKodebpr)->first(),
            ],
        ]);
    }

    public function exportPDFGabunganOperasional()
    {
        if (!$this->auth->check())
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');
        if (!$kodebpr || !$periodeId)
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak lengkap']);

        try {
            $periodeDetail = $this->periodeModel->find($periodeId);
            $bprData = $this->infobprModel->where('kodebpr', $kodebpr)->first();

            $dataInheren = $this->db->table('risikooperasional')
                ->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->whereIn('faktor1id', range(36, 49))->orderBy('faktor1id', 'ASC')
                ->get()->getResultArray();

            $dataKPMR = $this->db->table('risikooperasional_kpmr')
                ->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->whereIn('faktor1id', range(51, 71))->orderBy('faktor1id', 'ASC')
                ->get()->getResultArray();

            return $this->response->setJSON([
                'status' => 'success',
                'data' => [
                    'periode' => ['semester' => $periodeDetail['semester'] ?? '', 'tahun' => $periodeDetail['tahun'] ?? ''],
                    'bpr' => ['namabpr' => $bprData['namabpr'] ?? '', 'kodebpr' => $kodebpr],
                    'inheren' => $this->buildOperasionalInherenStructure($dataInheren),
                    'kpmr' => $this->buildOperasionalKPMRStructure($dataKPMR),
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error exportPDFGabunganOperasional: ' . $e->getMessage());
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal mengambil data: ' . $e->getMessage()]);
        }
    }

    public function exportPDFGabunganKepatuhan()
    {
        if (!$this->auth->check())
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');
        if (!$kodebpr || !$periodeId)
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak lengkap']);

        try {
            $periodeDetail = $this->periodeModel->find($periodeId);
            $bprData = $this->infobprModel->where('kodebpr', $kodebpr)->first();

            $dataInheren = $this->db->table('risikokepatuhan')
                ->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->whereIn('faktor1id', range(73, 82))->orderBy('faktor1id', 'ASC')
                ->get()->getResultArray();

            $dataKPMR = $this->db->table('risikokepatuhan_kpmr')
                ->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->whereIn('faktor1id', range(84, 103))->orderBy('faktor1id', 'ASC')
                ->get()->getResultArray();

            return $this->response->setJSON([
                'status' => 'success',
                'data' => [
                    'periode' => ['semester' => $periodeDetail['semester'] ?? '', 'tahun' => $periodeDetail['tahun'] ?? ''],
                    'bpr' => ['namabpr' => $bprData['namabpr'] ?? '', 'kodebpr' => $kodebpr],
                    'inheren' => $this->buildKepatuhanInherenStructure($dataInheren),
                    'kpmr' => $this->buildKepatuhanKPMRStructure($dataKPMR),
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error exportPDFGabunganKepatuhan: ' . $e->getMessage());
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal mengambil data: ' . $e->getMessage()]);
        }
    }

    public function exportPDFGabunganLikuiditas()
    {
        if (!$this->auth->check())
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');
        if (!$kodebpr || !$periodeId)
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak lengkap']);

        try {
            $periodeDetail = $this->periodeModel->find($periodeId);
            $bprData = $this->infobprModel->where('kodebpr', $kodebpr)->first();

            $dataInheren = $this->db->table('likuiditasinheren')
                ->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->whereIn('faktor1id', range(105, 116))->orderBy('faktor1id', 'ASC')
                ->get()->getResultArray();

            $dataKPMR = $this->db->table('likuiditaskpmr')
                ->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->whereIn('faktor1id', range(118, 136))->orderBy('faktor1id', 'ASC')
                ->get()->getResultArray();

            return $this->response->setJSON([
                'status' => 'success',
                'data' => [
                    'periode' => ['semester' => $periodeDetail['semester'] ?? '', 'tahun' => $periodeDetail['tahun'] ?? ''],
                    'bpr' => ['namabpr' => $bprData['namabpr'] ?? '', 'kodebpr' => $kodebpr],
                    'inheren' => $this->buildLikuiditasInherenStructure($dataInheren),
                    'kpmr' => $this->buildLikuiditasKPMRStructure($dataKPMR),
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error exportPDFGabunganLikuiditas: ' . $e->getMessage());
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal mengambil data: ' . $e->getMessage()]);
        }
    }

    public function exportPDFGabunganReputasi()
    {
        if (!$this->auth->check()) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Unauthorized'
            ]);
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        if (!$kodebpr || !$periodeId) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Data tidak lengkap'
            ]);
        }

        try {
            $periodeDetail = $this->periodeModel->find($periodeId);
            $bprData = $this->infobprModel->where('kodebpr', $kodebpr)->first();

            $dataInheren = $this->db->table('reputasiinheren')
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->whereIn('faktor1id', range(138, 149))
                ->orderBy('faktor1id', 'ASC')
                ->get()
                ->getResultArray();

            $dataKPMR = $this->db->table('reputasikpmr')
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->whereIn('faktor1id', range(151, 169))
                ->orderBy('faktor1id', 'ASC')
                ->get()
                ->getResultArray();

            $inheren = $this->buildReputasiInherenStructure($dataInheren);
            $kpmr = $this->buildReputasiKPMRStructure($dataKPMR);

            $responseData = [
                'status' => 'success',
                'data' => [
                    'periode' => [
                        'semester' => $periodeDetail['semester'] ?? '',
                        'tahun' => $periodeDetail['tahun'] ?? ''
                    ],
                    'bpr' => [
                        'namabpr' => $bprData['namabpr'] ?? '',
                        'kodebpr' => $kodebpr
                    ],
                    'inheren' => $inheren,
                    'kpmr' => $kpmr
                ]
            ];

            return $this->response->setJSON($responseData);

        } catch (\Exception $e) {
            log_message('error', 'Error exportPDFGabunganReputasi: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ]);
        }
    }

    public function exportPDFGabunganStratejik()
    {
        if (!$this->auth->check()) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Unauthorized'
            ]);
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        if (!$kodebpr || !$periodeId) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Data tidak lengkap'
            ]);
        }

        try {
            $periodeDetail = $this->periodeModel->find($periodeId);
            $bprData = $this->infobprModel->where('kodebpr', $kodebpr)->first();

            $dataInheren = $this->db->table('stratejikinheren')
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->whereIn('faktor1id', range(171, 180))
                ->orderBy('faktor1id', 'ASC')
                ->get()
                ->getResultArray();

            $dataKPMR = $this->db->table('stratejikkpmr')
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->whereIn('faktor1id', range(182, 200))
                ->orderBy('faktor1id', 'ASC')
                ->get()
                ->getResultArray();

            $inheren = $this->buildStratejikInherenStructure($dataInheren);
            $kpmr = $this->buildStratejikKPMRStructure($dataKPMR);

            $responseData = [
                'status' => 'success',
                'data' => [
                    'periode' => [
                        'semester' => $periodeDetail['semester'] ?? '',
                        'tahun' => $periodeDetail['tahun'] ?? ''
                    ],
                    'bpr' => [
                        'namabpr' => $bprData['namabpr'] ?? '',
                        'kodebpr' => $kodebpr
                    ],
                    'inheren' => $inheren,
                    'kpmr' => $kpmr
                ]
            ];

            return $this->response->setJSON($responseData);

        } catch (\Exception $e) {
            log_message('error', 'Error exportPDFGabunganStratejik: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ]);
        }
    }


    public function viewExportPDFOperasional()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }
        return view('Risikokredit/export_pdf_operasional_view', ['judul' => 'Export PDF Risiko Operasional']);
    }

    public function exportAllPDFToZipView()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;
        if ($redirect = $this->checkPeriode())
            return $redirect;
        return view('risikokredit/export_pdf', ['baseUrl' => base_url()]);
    }

    // PRIVATE HELPERS
    private function handleCategoryAndGlobalRecalc(int $faktorId): void
    {
        $categoryMapping = [1 => [2, 3, 4, 5], 6 => [7, 8, 9]];

        foreach ($categoryMapping as $categoryId => $childrenIds) {
            if (in_array($faktorId, $childrenIds)) {
                $this->calculateAndSaveCategoryAverage($categoryId, $childrenIds, $this->userKodebpr, $this->periodeId);
                break;
            }
        }

        if (in_array($faktorId, [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12])) {
            $rataRata = $this->nilaiModel->hitungRataRata(13, $this->userKodebpr, $this->periodeId);
            $this->nilaiModel->insertOrUpdateRataRata($rataRata, 13, $this->userKodebpr, $this->periodeId);
        }

        $this->db->table('risikokredit')
            ->where('faktor1id', 13)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
            ->update(['accdir2' => 0, 'is_approved' => 0]);
    }

    private function upsertRasioRows(array $rasioMapping, array $user): array
    {
        $successCount = 0;
        $processed = [];

        foreach ($rasioMapping as $fid => $val) {
            if ($val === null || $val === '')
                continue;

            $val = (float) $val;
            $existing = $this->nilaiModel
                ->where('faktor1id', $fid)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
                ->first();

            if ($existing) {
                $this->nilaiModel
                    ->where('faktor1id', $fid)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
                    ->set(['rasiokredit' => $val, 'user_id' => $this->userId, 'fullname' => $user['fullname'] ?? 'Unknown', 'updated_at' => date('Y-m-d H:i:s')])
                    ->update();
            } else {
                $this->nilaiModel->insert([
                    'faktor1id' => $fid,
                    'rasiokredit' => $val,
                    'fullname' => $user['fullname'] ?? 'Unknown',
                    'user_id' => $this->userId,
                    'kodebpr' => $this->userKodebpr,
                    'periode_id' => $this->periodeId,
                    'is_approved' => 0,
                    'accdir2' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
            $successCount++;
            $processed[] = $fid;
        }

        return [$successCount, $processed];
    }

    private function calculateAndSaveCategoryAverage($categoryFaktorId, $childrenFaktorIds, $kodebpr, $periodeId)
    {
        $childRows = $this->nilaiModel
            ->whereIn('faktor1id', $childrenFaktorIds)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
            ->findAll();

        $childValues = array_filter(array_column($childRows, 'penilaiankredit'));
        if (empty($childValues))
            return null;

        $roundedAverage = (int) round(array_sum($childValues) / count($childValues));
        $user = $this->userModel->find($this->userId);
        $penilaianConfig = $this->getPenilaianConfig();
        $autoKeterangan = $penilaianConfig[$categoryFaktorId]['descriptions'][$roundedAverage] ?? '';

        $existingCategory = $this->nilaiModel
            ->where('faktor1id', $categoryFaktorId)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
            ->first();

        $dataCategory = [
            'faktor1id' => $categoryFaktorId,
            'penilaiankredit' => $roundedAverage,
            'penjelasanpenilaian' => $autoKeterangan,
            'keterangan' => $autoKeterangan,
            'user_id' => $this->userId,
            'fullname' => $user['fullname'] ?? 'Unknown',
            'kodebpr' => $kodebpr,
            'periode_id' => $periodeId,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($existingCategory) {
            $this->nilaiModel->update($existingCategory['id'], $dataCategory);
        } else {
            $dataCategory['created_at'] = date('Y-m-d H:i:s');
            $dataCategory['is_approved'] = 0;
            $dataCategory['accdir2'] = 0;
            $this->nilaiModel->insert($dataCategory);
        }

        return $roundedAverage;
    }

    private function defaultKalkulatorData(): array
    {
        return [
            'aba' => '',
            'kydbank' => '',
            'kydpihak3' => '',
            'totalaset' => '',
            'total25debitur' => '',
            'perdagangan' => '',
            'jasa' => '',
            'konsumsirumah' => '',
            'kydgross' => 0,
            'asetproduktif' => 0,
            'rasioasetproduktif' => 0,
            'rasiokreditdiberikan' => 0,
            'rasio25debitur' => 0,
            'sektorekonomi' => 0,
            'rasioekonomi' => 0,
            'abanpl' => '',
            'kydnpl3' => '',
            'kydnpl4' => '',
            'kydnpl5' => '',
            'kreditdpk2' => '',
            'kreditbermasalah' => '',
            'kreditrestruktur1' => '',
            'kydkoleknpl' => 0,
            'asetproduktifbermasalah' => 0,
            'rasioasetproduktifbermasalah' => 0,
            'rasiokreditbermasalah' => 0,
            'rasiokreditkualitasrendah' => 0,
        ];
    }

    private function formatDateForDisplay($date): string
    {
        if (empty($date) || $date === '0000-00-00')
            return '';
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date))
            return $date;
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d ? $d->format('d/m/Y') : '';
    }

    private function strukturDataNilai($nilaiData): array
    {
        $struktur = [
            'komposisi' => ['kategori' => null, 'children' => []],
            'kualitas' => ['kategori' => null, 'children' => []],
            'strategi' => null,
            'eksternal' => null,
            'lainnya' => null,
            'rata_rata' => null,
        ];
        foreach ($nilaiData as $nilai) {
            $fid = $nilai['faktor1id'];
            if ($fid == 1)
                $struktur['komposisi']['kategori'] = $nilai;
            elseif (in_array($fid, [2, 3, 4, 5]))
                $struktur['komposisi']['children'][] = $nilai;
            elseif ($fid == 6)
                $struktur['kualitas']['kategori'] = $nilai;
            elseif (in_array($fid, [7, 8, 9]))
                $struktur['kualitas']['children'][] = $nilai;
            elseif ($fid == 10)
                $struktur['strategi'] = $nilai;
            elseif ($fid == 11)
                $struktur['eksternal'] = $nilai;
                    elseif ($fid == 12)
                $struktur['lainnya'] = $nilai;
            elseif ($fid == 13)
                $struktur['rata_rata'] = $nilai;
        }
        return $struktur;
    }

    private function getDataInheren(): array
    {
        $nilaiData = $this->db->table('risikokredit as r')
            ->select('r.*, u.fullname')->join('users as u', 'u.id = r.user_id', 'left')
            ->where('r.kodebpr', $this->userKodebpr)->where('r.periode_id', $this->periodeId)
            ->whereIn('r.faktor1id', range(1, 14))->orderBy('r.faktor1id', 'ASC')
            ->get()->getResultArray();

        return [
            'nilai' => $this->strukturDataNilai($nilaiData),
            'kalkulator' => $this->kalkulatorModel->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)->first(),
            'nilai13' => $this->nilaiModel->where(['faktor1id' => 13, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first(),
            'nilai14' => $this->nilaiModel->where(['faktor1id' => 14, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first(),
        ];
    }

    private function getDataKPMR(): array
    {
        $kpmrModel = new \App\Models\M_risikokreditkpmr();
        $nilaiData = $this->db->table('risikokredit_kpmr as r')
            ->select('r.*, u.fullname')->join('users as u', 'u.id = r.user_id', 'left')
            ->where('r.kodebpr', $this->userKodebpr)->where('r.periode_id', $this->periodeId)
            ->whereIn('r.faktor1id', range(16, 34))->orderBy('r.faktor1id', 'ASC')
            ->get()->getResultArray();

        return [
            'nilai' => $this->strukturDataNilaiKPMR($nilaiData),
            'nilai33' => $kpmrModel->where(['faktor1id' => 33, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first(),
            'nilai34' => $kpmrModel->where(['faktor1id' => 34, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first(),
        ];
    }

    private function strukturDataNilaiKPMR($nilaiData): array
    {
        $struktur = [
            'pengawasan' => ['kategori' => null, 'children' => []],
            'kebijakan' => ['kategori' => null, 'children' => []],
            'proses' => ['kategori' => null, 'children' => []],
            'pengendalian' => ['kategori' => null, 'children' => []],
            'rata_rata' => null,
        ];
        foreach ($nilaiData as $nilai) {
            $fid = $nilai['faktor1id'];
            if ($fid == 16)
                $struktur['pengawasan']['kategori'] = $nilai;
            elseif (in_array($fid, [17, 18, 19, 20, 21, 22]))
                $struktur['pengawasan']['children'][] = $nilai;
            elseif ($fid == 23)
                $struktur['kebijakan']['kategori'] = $nilai;
            elseif (in_array($fid, [24, 25, 26]))
                $struktur['kebijakan']['children'][] = $nilai;
            elseif ($fid == 27)
                $struktur['proses']['kategori'] = $nilai;
            elseif (in_array($fid, [28, 29]))
                $struktur['proses']['children'][] = $nilai;
            elseif ($fid == 30)
                $struktur['pengendalian']['kategori'] = $nilai;
            elseif (in_array($fid, [31, 32]))
                $struktur['pengendalian']['children'][] = $nilai;
            elseif ($fid == 33)
                $struktur['rata_rata'] = $nilai;
        }
        return $struktur;
    }

    private function getDataKepatuhanInheren(): array
    {
        $model = new \App\Models\M_risikokepatuhan();
        $nilaiData = $this->db->table('risikokepatuhan as r')
            ->select('r.*, u.fullname')->join('users as u', 'u.id = r.user_id', 'left')
            ->where('r.kodebpr', $this->userKodebpr)->where('r.periode_id', $this->periodeId)
            ->whereIn('r.faktor1id', range(73, 82))->orderBy('r.faktor1id', 'ASC')
            ->get()->getResultArray();

        return [
            'nilai' => $this->strukturDataNilaiKepatuhan($nilaiData),
            'nilai81' => $model->where(['faktor1id' => 81, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first(),
            'nilai82' => $model->where(['faktor1id' => 82, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first(),
        ];
    }

    private function strukturDataNilaiKepatuhan($nilaiData): array
    {
        $struktur = ['pelanggaran' => ['kategori' => null, 'children' => []], 'hukum' => ['kategori' => null, 'children' => []], 'lainnya' => null];
        foreach ($nilaiData as $nilai) {
            $fid = $nilai['faktor1id'];
            if ($fid == 73)
                $struktur['pelanggaran']['kategori'] = $nilai;
            elseif (in_array($fid, [74, 75]))
                $struktur['pelanggaran']['children'][] = $nilai;
            elseif ($fid == 76)
                $struktur['hukum']['kategori'] = $nilai;
            elseif (in_array($fid, [77, 78, 79]))
                $struktur['hukum']['children'][] = $nilai;
            elseif ($fid == 80)
                $struktur['lainnya'] = $nilai;
        }
        return $struktur;
    }

    private function getDataKepatuhanKPMR(): array
    {
        $model = new \App\Models\M_kepatuhankpmr();
        $nilaiData = $this->db->table('risikokepatuhan_kpmr as r')
            ->select('r.*, u.fullname')->join('users as u', 'u.id = r.user_id', 'left')
            ->where('r.kodebpr', $this->userKodebpr)->where('r.periode_id', $this->periodeId)
            ->whereIn('r.faktor1id', range(84, 103))->orderBy('r.faktor1id', 'ASC')
            ->get()->getResultArray();

        return [
            'nilai' => $this->strukturDataNilaiKepatuhanKPMR($nilaiData),
            'nilai102' => $model->where(['faktor1id' => 102, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first(),
            'nilai103' => $model->where(['faktor1id' => 103, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first(),
        ];
    }

    private function strukturDataNilaiKepatuhanKPMR($nilaiData): array
    {
        $struktur = [
            'pengawasan' => ['kategori' => null, 'children' => []],
            'kebijakan' => ['kategori' => null, 'children' => []],
            'proses' => ['kategori' => null, 'children' => []],
            'pengendalian' => ['kategori' => null, 'children' => []],
        ];
        foreach ($nilaiData as $nilai) {
            $fid = $nilai['faktor1id'];
            if ($fid == 84)
                $struktur['pengawasan']['kategori'] = $nilai;
            elseif (in_array($fid, [85, 86, 87, 88, 89, 90, 91]))
                $struktur['pengawasan']['children'][] = $nilai;
            elseif ($fid == 92)
                $struktur['kebijakan']['kategori'] = $nilai;
            elseif (in_array($fid, [93, 94, 95]))
                $struktur['kebijakan']['children'][] = $nilai;
            elseif ($fid == 96)
                $struktur['proses']['kategori'] = $nilai;
            elseif (in_array($fid, [97, 98]))
                $struktur['proses']['children'][] = $nilai;
            elseif ($fid == 99)
                $struktur['pengendalian']['kategori'] = $nilai;
            elseif (in_array($fid, [100, 101]))
                $struktur['pengendalian']['children'][] = $nilai;
        }
        return $struktur;
    }

    private function getDataLikuiditas(): array
    {
        $model = new \App\Models\M_likuiditasinheren();
        $nilaiData = $this->db->table('likuiditasinheren as r')
            ->select('r.*, u.fullname')->join('users as u', 'u.id = r.user_id', 'left')
            ->where('r.kodebpr', $this->userKodebpr)->where('r.periode_id', $this->periodeId)
            ->whereIn('r.faktor1id', range(105, 116))->orderBy('r.faktor1id', 'ASC')
            ->get()->getResultArray();

        return [
            'nilai' => $this->strukturDataNilaiLikuiditas($nilaiData),
            'nilai115' => $model->where(['faktor1id' => 115, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first(),
            'nilai116' => $model->where(['faktor1id' => 116, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first(),
        ];
    }

    private function strukturDataNilaiLikuiditas($nilaiData): array
    {
        $struktur = ['konsentrasi' => ['kategori' => null, 'children' => []], 'kerentanan' => ['kategori' => null, 'children' => []], 'lainnya' => null];
        foreach ($nilaiData as $nilai) {
            $fid = $nilai['faktor1id'];
            if ($fid == 105)
                $struktur['konsentrasi']['kategori'] = $nilai;
            elseif (in_array($fid, [106, 107, 108, 109, 110]))
                $struktur['konsentrasi']['children'][] = $nilai;
            elseif ($fid == 111)
                $struktur['kerentanan']['kategori'] = $nilai;
            elseif (in_array($fid, [112, 113]))
                $struktur['kerentanan']['children'][] = $nilai;
            elseif ($fid == 114)
                $struktur['lainnya'] = $nilai;
        }
        return $struktur;
    }

    private function getDataLikuiditasKPMR(): array
    {
        $model = new \App\Models\M_likuiditaskpmr();
        $nilaiData = $this->db->table('likuiditaskpmr as r')
            ->select('r.*, u.fullname')->join('users as u', 'u.id = r.user_id', 'left')
            ->where('r.kodebpr', $this->userKodebpr)->where('r.periode_id', $this->periodeId)
            ->whereIn('r.faktor1id', range(118, 136))->orderBy('r.faktor1id', 'ASC')
            ->get()->getResultArray();

        return [
            'nilai' => $this->strukturDataNilaiLikuiditasKPMR($nilaiData),
            'nilai135' => $model->where(['faktor1id' => 135, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first(),
            'nilai136' => $model->where(['faktor1id' => 136, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first(),
        ];
    }

    private function strukturDataNilaiLikuiditasKPMR($nilaiData): array
    {
        $struktur = [
            'pengawasan' => ['kategori' => null, 'children' => []],
            'kebijakan' => ['kategori' => null, 'children' => []],
            'proses' => ['kategori' => null, 'children' => []],
            'pengendalian' => ['kategori' => null, 'children' => []],
        ];
        foreach ($nilaiData as $nilai) {
            $fid = $nilai['faktor1id'];
            if ($fid == 118)
                $struktur['pengawasan']['kategori'] = $nilai;
            elseif (in_array($fid, [119, 120, 121, 122, 123, 124]))
                $struktur['pengawasan']['children'][] = $nilai;
            elseif ($fid == 125)
                $struktur['kebijakan']['kategori'] = $nilai;
            elseif (in_array($fid, [126, 127, 128]))
                $struktur['kebijakan']['children'][] = $nilai;
            elseif ($fid == 129)
                $struktur['proses']['kategori'] = $nilai;
            elseif (in_array($fid, [130, 131]))
                $struktur['proses']['children'][] = $nilai;
            elseif ($fid == 132)
                $struktur['pengendalian']['kategori'] = $nilai;
            elseif (in_array($fid, [133, 134]))
                $struktur['pengendalian']['children'][] = $nilai;
        }
        return $struktur;
    }

    private function buildOperasionalInherenStructure(array $dataArray): array
    {
        $lookup = array_column($dataArray, null, 'faktor1id');
        return [
            'nilai' => [
                'kompleksitas' => ['kategori' => $lookup[36] ?? null, 'children' => array_values(array_filter([$lookup[37] ?? null, $lookup[38] ?? null, $lookup[39] ?? null, $lookup[40] ?? null]))],
                'sdm' => ['kategori' => $lookup[41] ?? null, 'children' => array_values(array_filter([$lookup[42] ?? null, $lookup[43] ?? null]))],
                'ti' => $lookup[44] ?? null,
                'fraud' => $lookup[45] ?? null,
                'eksternal' => $lookup[46] ?? null,
                'lainnya' => $lookup[47] ?? null,
            ],
            'nilai13' => $lookup[48] ?? null,
            'nilai14' => $lookup[49] ?? null,
        ];
    }

    private function buildOperasionalKPMRStructure(array $dataArray): array
    {
        $lookup = array_column($dataArray, null, 'faktor1id');
        return [
            'nilai' => [
                'pengawasan' => ['kategori' => $lookup[51] ?? null, 'children' => array_values(array_filter([$lookup[52] ?? null, $lookup[53] ?? null, $lookup[54] ?? null, $lookup[55] ?? null, $lookup[56] ?? null, $lookup[57] ?? null]))],
                'kebijakan' => ['kategori' => $lookup[58] ?? null, 'children' => array_values(array_filter([$lookup[59] ?? null, $lookup[60] ?? null, $lookup[61] ?? null]))],
                'proses' => ['kategori' => $lookup[62] ?? null, 'children' => array_values(array_filter([$lookup[63] ?? null, $lookup[64] ?? null, $lookup[65] ?? null, $lookup[66] ?? null]))],
                'pengendalian' => ['kategori' => $lookup[67] ?? null, 'children' => array_values(array_filter([$lookup[68] ?? null, $lookup[69] ?? null]))],
            ],
            'nilai33' => $lookup[70] ?? null,
            'nilai34' => $lookup[71] ?? null,
        ];
    }

    private function buildKepatuhanInherenStructure(array $dataArray): array
    {
        $lookup = array_column($dataArray, null, 'faktor1id');
        return [
            'nilai' => [
                'pelanggaran' => ['kategori' => $lookup[73] ?? null, 'children' => array_values(array_filter([$lookup[74] ?? null, $lookup[75] ?? null]))],
                'hukum' => ['kategori' => $lookup[76] ?? null, 'children' => array_values(array_filter([$lookup[77] ?? null, $lookup[78] ?? null, $lookup[79] ?? null]))],
                'lainnya' => $lookup[80] ?? null,
            ],
            'nilai81' => $lookup[81] ?? null,
            'nilai82' => $lookup[82] ?? null,
        ];
    }

    private function buildKepatuhanKPMRStructure(array $dataArray): array
    {
        $lookup = array_column($dataArray, null, 'faktor1id');
        return [
            'nilai' => [
                'pengawasan' => ['kategori' => $lookup[84] ?? null, 'children' => array_values(array_filter([$lookup[85] ?? null, $lookup[86] ?? null, $lookup[87] ?? null, $lookup[88] ?? null, $lookup[89] ?? null, $lookup[90] ?? null, $lookup[91] ?? null]))],
                'kebijakan' => ['kategori' => $lookup[92] ?? null, 'children' => array_values(array_filter([$lookup[93] ?? null, $lookup[94] ?? null, $lookup[95] ?? null]))],
                'proses' => ['kategori' => $lookup[96] ?? null, 'children' => array_values(array_filter([$lookup[97] ?? null, $lookup[98] ?? null]))],
                'pengendalian' => ['kategori' => $lookup[99] ?? null, 'children' => array_values(array_filter([$lookup[100] ?? null, $lookup[101] ?? null]))],
            ],
            'nilai102' => $lookup[102] ?? null,
            'nilai103' => $lookup[103] ?? null,
        ];
    }

    private function buildLikuiditasInherenStructure(array $dataArray): array
    {
        $lookup = array_column($dataArray, null, 'faktor1id');
        return [
            'nilai' => [
                'konsentrasi' => ['kategori' => $lookup[105] ?? null, 'children' => array_values(array_filter([$lookup[106] ?? null, $lookup[107] ?? null, $lookup[108] ?? null, $lookup[109] ?? null, $lookup[110] ?? null]))],
                'kerentanan' => ['kategori' => $lookup[111] ?? null, 'children' => array_values(array_filter([$lookup[112] ?? null, $lookup[113] ?? null]))],
                'lainnya' => $lookup[114] ?? null,
            ],
            'nilai115' => $lookup[115] ?? null,
            'nilai116' => $lookup[116] ?? null,
        ];
    }

    private function buildLikuiditasKPMRStructure(array $dataArray): array
    {
        $lookup = array_column($dataArray, null, 'faktor1id');
        return [
            'nilai' => [
                'pengawasan' => ['kategori' => $lookup[118] ?? null, 'children' => array_values(array_filter([$lookup[119] ?? null, $lookup[120] ?? null, $lookup[121] ?? null, $lookup[122] ?? null, $lookup[123] ?? null, $lookup[124] ?? null]))],
                'kebijakan' => ['kategori' => $lookup[125] ?? null, 'children' => array_values(array_filter([$lookup[126] ?? null, $lookup[127] ?? null, $lookup[128] ?? null]))],
                'proses' => ['kategori' => $lookup[129] ?? null, 'children' => array_values(array_filter([$lookup[130] ?? null, $lookup[131] ?? null]))],
                'pengendalian' => ['kategori' => $lookup[132] ?? null, 'children' => array_values(array_filter([$lookup[133] ?? null, $lookup[134] ?? null]))],
            ],
            'nilai102' => $lookup[135] ?? null,
            'nilai103' => $lookup[136] ?? null,
        ];
    }

    private function buildReputasiInherenStructure($dataArray)
    {
        $lookup = [];
        foreach ($dataArray as $item) {
            $lookup[$item['faktor1id']] = $item;
        }

        $structure = [
            'nilai' => [
                'pengelolaan' => [
                    'kategori' => $lookup[138] ?? null,
                    'children' => array_filter([
                        $lookup[139] ?? null,
                        $lookup[140] ?? null
                    ])
                ],
                'frekuensi' => $lookup[141] ?? null,
                'keluhan' => [
                    'kategori' => $lookup[142] ?? null,
                    'children' => array_filter([
                        $lookup[143] ?? null
                    ])
                ],
                'etika' => [
                    'kategori' => $lookup[144] ?? null,
                    'children' => array_filter([
                        $lookup[145] ?? null,
                        $lookup[146] ?? null
                    ])
                ],
                'lainnya' => $lookup[147] ?? null
            ],
            'nilai148' => $lookup[148] ?? null,
            'nilai149' => $lookup[149] ?? null
        ];

        return $structure;
    }

    private function buildReputasiKPMRStructure($dataArray)
    {
        $lookup = [];
        foreach ($dataArray as $item) {
            $lookup[$item['faktor1id']] = $item;
        }

        $structure = [
            'nilai' => [
                'pengawasan' => [
                    'kategori' => $lookup[151] ?? null,
                    'children' => array_filter([
                        $lookup[152] ?? null,
                        $lookup[153] ?? null,
                        $lookup[154] ?? null,
                        $lookup[155] ?? null,
                        $lookup[156] ?? null,
                        $lookup[157] ?? null
                    ])
                ],
                'kebijakan' => [
                    'kategori' => $lookup[158] ?? null,
                    'children' => array_filter([
                        $lookup[159] ?? null,
                        $lookup[160] ?? null,
                        $lookup[161] ?? null
                    ])
                ],
                'proses' => [
                    'kategori' => $lookup[162] ?? null,
                    'children' => array_filter([
                        $lookup[163] ?? null,
                        $lookup[164] ?? null
                    ])
                ],
                'pengendalian' => [
                    'kategori' => $lookup[165] ?? null,
                    'children' => array_filter([
                        $lookup[166] ?? null,
                        $lookup[167] ?? null
                    ])
                ]
            ],
            'nilai168' => $lookup[168] ?? null,
            'nilai169' => $lookup[169] ?? null
        ];

        return $structure;
    }

    private function buildStratejikInherenStructure($dataArray)
    {
        $lookup = [];
        foreach ($dataArray as $item) {
            $lookup[$item['faktor1id']] = $item;
        }

        $structure = [
            'nilai' => [
                'penetapan' => $lookup[171] ?? null,
                'penyusunan' => [
                    'kategori' => $lookup[172] ?? null,
                    'children' => array_filter([
                        $lookup[173] ?? null,
                        $lookup[174] ?? null
                    ])
                ],
                'pencapaian' => [
                    'kategori' => $lookup[175] ?? null,
                    'children' => array_filter([
                        $lookup[176] ?? null,
                        $lookup[177] ?? null
                    ])
                ],
                'lainnya' => $lookup[178] ?? null
            ],
            'nilai179' => $lookup[179] ?? null,
            'nilai180' => $lookup[180] ?? null
        ];

        return $structure;
    }

    private function buildStratejikKPMRStructure($dataArray)
    {
        $lookup = [];
        foreach ($dataArray as $item) {
            $lookup[$item['faktor1id']] = $item;
        }

        $structure = [
            'nilai' => [
                'pengawasan' => [
                    'kategori' => $lookup[182] ?? null,
                    'children' => array_filter([
                        $lookup[183] ?? null,
                        $lookup[184] ?? null,
                        $lookup[185] ?? null,
                        $lookup[186] ?? null,
                        $lookup[187] ?? null,
                        $lookup[188] ?? null
                    ])
                ],
                'kebijakan' => [
                    'kategori' => $lookup[189] ?? null,
                    'children' => array_filter([
                        $lookup[190] ?? null,
                        $lookup[191] ?? null,
                        $lookup[192] ?? null
                    ])
                ],
                'proses' => [
                    'kategori' => $lookup[193] ?? null,
                    'children' => array_filter([
                        $lookup[194] ?? null,
                        $lookup[195] ?? null
                    ])
                ],
                'pengendalian' => [
                    'kategori' => $lookup[196] ?? null,
                    'children' => array_filter([
                        $lookup[197] ?? null,
                        $lookup[198] ?? null
                    ])
                ]
            ],
            'nilai199' => $lookup[199] ?? null,
            'nilai200' => $lookup[200] ?? null
        ];

        return $structure;
    }

    private function getPenilaianConfig(): array
    {
        return [
            1 => ['descriptions' => [1 => 'Parameter Komposisi Portofolio Aset dan Tingkat Konsentrasi Kredit berada pada tingkat risiko yang Sangat Rendah', 2 => 'Parameter Komposisi Portofolio Aset dan Tingkat Konsentrasi Kredit berada pada tingkat risiko yang Rendah', 3 => 'Parameter Komposisi Portofolio Aset dan Tingkat Konsentrasi Kredit berada pada tingkat risiko yang Sedang', 4 => 'Parameter Komposisi Portofolio Aset dan Tingkat Konsentrasi Kredit berada pada tingkat risiko yang Tinggi', 5 => 'Parameter Komposisi Portofolio Aset dan Tingkat Konsentrasi Kredit berada pada tingkat risiko yang Sangat Tinggi'], 'catatan' => '-'],
            2 => ['threshold' => '95%', 'descriptions' => [1 => '<= 95%', 2 => '>95%, Komponen aset produktif memiliki eksposur Risiko kredit rendah', 3 => '>95%, Komponen aset produktif memiliki eksposur Risiko kredit moderat', 4 => '>95%, Komponen aset produktif memiliki eksposur Risiko kredit tinggi', 5 => '>95%, Komponen aset produktif memiliki eksposur Risiko kredit sangat tinggi'], 'catatan' => 'BPR dengan rasio <= 95% dimungkinkan mendapat peringkat lebih buruk dari 1 antara lain dalam hal BPR memiliki aset produktif dengan eksposur Risiko kredit yang lebih tinggi'],
            3 => ['threshold' => '75%', 'descriptions' => [1 => '<= 75%', 2 => '>75%, Skema kredit sebagian besar atau seluruhnya sederhana, dan jenis kredit tidak beragam', 3 => '>75%, Skema kredit sebagian besar atau seluruhnya sederhana, dan jenis kredit beragam', 4 => '>75%, Skema kredit sebagian besar atau seluruhnya kompleks, dan jenis kredit tidak beragam', 5 => '>75%, Skema kredit sebagian besar atau seluruhnya kompleks, dan jenis kredit beragam'], 'catatan' => 'BPR dengan rasio <=75% dimungkinkan mendapat peringkat lebih buruk dari 1, dalam hal portofolio kredit'],
            4 => ['threshold' => '20%', 'descriptions' => [1 => '<= 20%', 2 => '≥ 20%, Target pasar tidak berubah selama jangka waktu yang sangat lama', 3 => '≥ 20%, Target pasar tidak berubah selama jangka waktu yang lama', 4 => '≥ 20%, Target pasar tidak berubah selama jangka waktu yang cukup lama', 5 => '≥ 20%, Target pasar tidak berubah selama jangka waktu yang singkat'], 'catatan' => 'Konsentrasi kredit pada 25 debitur terbesar'],
            5 => ['threshold' => '85%', 'descriptions' => [1 => '<= 85%', 2 => '≥ 85%, Kredit yang berasal dari 3 (tiga) sektor ekonomi terbesar tidak berubah selama jangka waktu yang sangat lama', 3 => '≥ 85%, Kredit yang berasal dari 3 (tiga) sektor ekonomi terbesar tidak berubah selama jangka waktu yang lama', 4 => '≥ 85%, Kredit yang berasal dari 3 (tiga) sektor ekonomi terbesar tidak berubah selama jangka waktu yang cukup lama', 5 => '≥ 85%, Kredit yang berasal dari 3 (tiga) sektor ekonomi terbesar tidak berubah selama jangka waktu yang singkat'], 'catatan' => 'Parameter rasio kredit per sektor ekonomi terhadap total kredit yang diberikan'],
            6 => ['descriptions' => [1 => 'Parameter Kualitas aset berada pada tingkat risiko yang Sangat Rendah', 2 => 'Parameter Kualitas aset berada pada tingkat risiko yang Rendah', 3 => 'Parameter Kualitas aset berada pada tingkat risiko yang Sedang', 4 => 'Parameter Kualitas aset berada pada tingkat risiko yang Tinggi', 5 => 'Parameter Kualitas aset berada pada tingkat risiko yang Sangat Tinggi'], 'catatan' => '-'],
            7 => ['threshold' => '7%', 'descriptions' => [1 => '<= 7%', 2 => "Rasio di atas ambang batas peringkat 1, dengan kondisi pemberian kredit memiliki kualitas yang baik, antara lain ditunjukkan dengan: <br>• Kredit restrukturisasi tidak signifikan <br>• Penurunan kualitas kredit dari Performing Loan ke Non Performing Loan tidak signifikan <br>• Sektor ekonomi berisiko tinggi tidak signifikan <br>• Jumlah kredit lancar yang menunggak >7 hari tidak signifikan <br>• Komponen aset produktif bermasalah sebagian besar merupakan penempatan pada bank lain", 3 => 'Rasio di atas ambang batas peringkat 1, dengan kondisi pemberian kredit memiliki kualitas yang cukup baik, namun terdapat potensi penurunan, antara lain ditunjukkan dengan: <br>• Kredit restrukturisasi cukup signifikan <br>• Penurunan kualitas kredit dari Performing Loan ke Non Performing Loan cukup signifikan <br>• Sektor ekonomi berisiko tinggi cukup signifikan <br>• Jumlah kredit lancar yang menunggak >7 hari cukup <br>• Komponen aset produktif bermasalah sebagian besar merupakan penempatan pada bank lain signifikan', 4 => 'Rasio di atas ambang batas peringkat 1, dengan kondisi pemberian kredit memiliki kualitas yang kurang baik, antara lain ditunjukkan dengan: <br>• Kredit restrukturisasi signifikan <br>• Penurunan kualitas kredit dari Performing Loan ke Non Performing Loan signifikan <br>• Sektor ekonomi berisiko tinggi signifikan <br>• Jumlah kredit lancar yang menunggak >7 hari signifikan <br>• Komponen aset produktif bermasalah sebagian besar merupakan kredit', 5 => 'Rasio di atas ambang batas peringkat 1, dengan kondisi pemberian kredit memiliki kualitas yang buruk, antara lain ditunjukkan dengan: <br>• Kredit restrukturisasi sangat signifikan <br>• Penurunan kualitas kredit dari Performing Loan ke Non Performing Loan sangat signifikan <br>• Sektor ekonomi berisiko tinggi sangat signifikan <br>• Jumlah kredit lancar yang menunggak >7 hari sangat signifikan <br>• Komponen aset produktif bermasalah sebagian besar merupakan kredit'], 'catatan' => 'Rasio aset produktif bermasalah terhadap total aset produktif'],
            8 => ['threshold' => '5%', 'descriptions' => [1 => '<= 5%', 2 => "Rasio di atas ambang batas peringkat 1, dengan kondisi pemberian kredit memiliki kualitas yang baik, antara lain ditunjukkan dengan: <br>• Kredit restrukturisasi tidak signifikan <br>• Penurunan kualitas kredit dari Performing Loan ke Non Performing Loan tidak signifikan <br>• Sektor ekonomi berisiko tinggi tidak signifikan <br>• Jumlah kredit lancar yang menunggak >7 hari tidak signifikan <br>• Komponen aset produktif bermasalah sebagian besar merupakan penempatan pada bank lain", 3 => 'Rasio di atas ambang batas peringkat 1, dengan kondisi pemberian kredit memiliki kualitas yang cukup baik, namun terdapat potensi penurunan, antara lain ditunjukkan dengan: <br>• Kredit restrukturisasi cukup signifikan <br>• Penurunan kualitas kredit dari Performing Loan ke Non Performing Loan cukup signifikan <br>• Sektor ekonomi berisiko tinggi cukup signifikan <br>• Jumlah kredit lancar yang menunggak >7 hari cukup <br>• Komponen aset produktif bermasalah sebagian besar merupakan penempatan pada bank lain signifikan', 4 => 'Rasio di atas ambang batas peringkat 1, dengan kondisi pemberian kredit memiliki kualitas yang kurang baik, antara lain ditunjukkan dengan: <br>• Kredit restrukturisasi signifikan <br>• Penurunan kualitas kredit dari Performing Loan ke Non Performing Loan signifikan <br>• Sektor ekonomi berisiko tinggi signifikan <br>• Jumlah kredit lancar yang menunggak >7 hari signifikan <br>• Komponen aset produktif bermasalah sebagian besar merupakan kredit', 5 => 'Rasio di atas ambang batas peringkat 1, dengan kondisi pemberian kredit memiliki kualitas yang buruk, antara lain ditunjukkan dengan: <br>• Kredit restrukturisasi sangat signifikan <br>• Penurunan kualitas kredit dari Performing Loan ke Non Performing Loan sangat signifikan <br>• Sektor ekonomi berisiko tinggi sangat signifikan <br>• Jumlah kredit lancar yang menunggak >7 hari sangat signifikan <br>• Komponen aset produktif bermasalah sebagian besar merupakan kredit'], 'catatan' => 'Kredit bermasalah neto / total kredit yang diberikan'],
            9 => ['threshold' => '7%', 'descriptions' => [1 => '<= 7%', 2 => "Rasio di atas ambang batas peringkat 1, dengan kondisi pemberian kredit memiliki kualitas yang baik, antara lain ditunjukkan dengan: <br>• Kredit restrukturisasi tidak signifikan <br>• Penurunan kualitas kredit dari Performing Loan ke Non Performing Loan tidak signifikan <br>• Sektor ekonomi berisiko tinggi tidak signifikan <br>• Jumlah kredit lancar yang menunggak >7 hari tidak signifikan <br>• Komponen aset produktif bermasalah sebagian besar merupakan penempatan pada bank lain", 3 => 'Rasio di atas ambang batas peringkat 1, dengan kondisi pemberian kredit memiliki kualitas yang cukup baik, namun terdapat potensi penurunan, antara lain ditunjukkan dengan: <br>• Kredit restrukturisasi cukup signifikan <br>• Penurunan kualitas kredit dari Performing Loan ke Non Performing Loan cukup signifikan <br>• Sektor ekonomi berisiko tinggi cukup signifikan <br>• Jumlah kredit lancar yang menunggak >7 hari cukup <br>• Komponen aset produktif bermasalah sebagian besar merupakan penempatan pada bank lain signifikan', 4 => 'Rasio di atas ambang batas peringkat 1, dengan kondisi pemberian kredit memiliki kualitas yang kurang baik, antara lain ditunjukkan dengan: <br>• Kredit restrukturisasi signifikan <br>• Penurunan kualitas kredit dari Performing Loan ke Non Performing Loan signifikan <br>• Sektor ekonomi berisiko tinggi signifikan <br>• Jumlah kredit lancar yang menunggak >7 hari signifikan <br>• Komponen aset produktif bermasalah sebagian besar merupakan kredit', 5 => 'Rasio di atas ambang batas peringkat 1, dengan kondisi pemberian kredit memiliki kualitas yang buruk, antara lain ditunjukkan dengan: <br>• Kredit restrukturisasi sangat signifikan <br>• Penurunan kualitas kredit dari Performing Loan ke Non Performing Loan sangat signifikan <br>• Sektor ekonomi berisiko tinggi sangat signifikan <br>• Jumlah kredit lancar yang menunggak >7 hari sangat signifikan <br>• Komponen aset produktif bermasalah sebagian besar merupakan kredit'], 'catatan' => 'Rasio aset produktif bermasalah terhadap total aset produktif'],
            10 => ['descriptions' => [1 => '• Pertumbuhan kredit di atas rata-rata industri, dan <br>• Seluruhnya disalurkan kepada sektor ekonomi yang dikuasai.', 2 => '• Pertumbuhan kredit di atas rata-rata industri, dan <br>• Sebagian besar disalurkan kepada sektor ekonomi yang dikuasai.', 3 => '• Pertumbuhan kredit di atas atau sama dengan rata-rata industri, dan Sebagian kecil atau tidak sama sekali disalurkan kepada sektor ekonomi yang dikuasai atau <br>• Pertumbuhan kredit di bawah rata-rata industri, dan <br>• Seluruhnya disalurkan kepada sektor ekonomi yang dikuasai.', 4 => '• Pertumbuhan kredit di bawah rata-rata industri, dan <br>• Sebagian besar disalurkan kepada sektor ekonomi yang dikuasai.', 5 => '• Pertumbuhan kredit di bawah rata-rata industri, dan <br>• Sebagian kecil atau tidak sama sekali disalurkan kepada sektor ekonomi yang dikuasai.'], 'catatan' => 'Penilaian berdasarkan kualitas pelaksanaan tata kelola'],
            11 => ['descriptions' => [1 => 'Terdapat perubahan faktor eksternal, namun tidak berdampak pada kemampuan debitur untuk membayar kembali pinjaman.', 2 => 'Terdapat perubahan faktor eksternal, yang berdampak pada kemampuan debitur untuk membayar kembali pinjaman sehingga terjadi tunggakan pinjaman namun tidak menyebabkan penurunan kualitas kredit debitur.', 3 => 'Terdapat perubahan faktor eksternal, yang berdampak pada kinerja bisnis debitur sehingga menyebabkan terjadi tunggakan pinjaman tetapi tidak menurunkan kualitas kredit debitur menjadi NPL.', 4 => 'Terdapat perubahan faktor eksternal, yang menyebabkan penurunan kualitas kredit debitur hingga menjadi NPL.', 5 => 'Terdapat perubahan faktor eksternal, yang menyebabkan kebangkrutan debitur.'], 'catatan' => 'Penilaian berdasarkan kualitas pelaksanaan tata kelola'],
            12 => ['descriptions' => [1 => 'Parameter Penilaian Risiko Kredit Inheren Lainnya berada pada tingkat risiko yang Sangat Rendah', 2 => 'Parameter Penilaian Risiko Kredit Inheren Lainnya berada pada tingkat risiko yang Rendah', 3 => 'Parameter Penilaian Risiko Kredit Inheren Lainnya berada pada tingkat risiko yang Sedang', 4 => 'Parameter Penilaian Risiko Kredit Inheren Lainnya berada pada tingkat risiko yang Tinggi', 5 => 'Parameter Penilaian Risiko Kredit Inheren Lainnya berada pada tingkat risiko yang Sangat Tinggi'], 'catatan' => 'Pertumbuhan kredit year-on-year'],
        ];
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

        // Cek apakah periode aktif sudah punya data (untuk peringatan overwrite)
        $currentCount = (int) $this->db->table('risikokredit')
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->whereIn('faktor1id', range(1, 12))
            ->where('penilaiankredit IS NOT NULL')
            ->countAllResults();

        // Cek jumlah data faktor di periode sebelumnya (query ringan — COUNT saja)
        $prevCount = (int) $this->db->table('risikokredit')
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $prevPeriode['id'])
            ->whereIn('faktor1id', range(1, 12))
            ->where('penilaiankredit IS NOT NULL')
            ->countAllResults();

        // Cek kalkulator di periode sebelumnya
        $prevKalkulatorExists = $this->db->table('kalkulator_kredit')
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $prevPeriode['id'])
            ->countAllResults() > 0;

        if ($prevCount === 0 && !$prevKalkulatorExists) {
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
                'has_kalkulator' => $prevKalkulatorExists,
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

        // Ambil semua data faktor 1–12 dari periode sebelumnya sekaligus (1 query)
        $prevRows = $this->db->table('risikokredit')
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $prevPeriode['id'])
            ->whereIn('faktor1id', range(1, 12))
            ->get()->getResultArray();

        // Ambil data existing di periode aktif untuk lookup cepat (1 query)
        $existingRows = $this->db->table('risikokredit')
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->whereIn('faktor1id', range(1, 12))
            ->get()->getResultArray();

        // Buat lookup existing berdasarkan faktor1id → O(1)
        $existingLookup = [];
        foreach ($existingRows as $row) {
            $existingLookup[(int) $row['faktor1id']] = $row;
        }

        // Ambil kalkulator dari periode sebelumnya (1 query)
        $prevKalkulator = $this->kalkulatorModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $prevPeriode['id'])
            ->first();

        $user = $this->userModel->find($this->userId);
        $now = date('Y-m-d H:i:s');

        // Kolom yang disalin dari risikokredit (tidak menyalin approval/accdir2)
        $copyFields = [
            'penilaiankredit',
            'penjelasanpenilaian',
            'keterangan',
            'rasiokredit',
        ];

        $toInsert = [];
        $toUpdate = []; // ['id' => ..., 'data' => [...]]
        $syncedFaktors = [];
        $skippedFaktors = [];

        foreach ($prevRows as $prev) {
            $fid = (int) $prev['faktor1id'];
            if (empty($prev['penilaiankredit'])) {
                continue; // skip baris kosong
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
                    // Sudah ada data dan tidak force → skip
                    $skippedFaktors[] = $fid;
                    continue;
                }
                // Update (preserve rasiokredit jika tidak ada di source)
                $updateData = $rowData;
                $updateData['updated_at'] = $now;
                // Jangan timpa rasiokredit jika sumber tidak punya tapi existing punya
                if (empty($rowData['rasiokredit']) && !empty($existing['rasiokredit'])) {
                    $updateData['rasiokredit'] = $existing['rasiokredit'];
                }
                $toUpdate[] = ['id' => $existing['id'], 'data' => $updateData];
            } else {
                // Insert baru
                $rowData['created_at'] = $now;
                $toInsert[] = $rowData;
            }
            $syncedFaktors[] = $fid;
        }

        if (empty($toInsert) && empty($toUpdate) && empty($syncedFaktors)) {
            return $this->response->setJSON([
                'status' => 'no_change',
                'message' => empty($skippedFaktors)
                    ? 'Tidak ada data yang bisa disinkronisasi'
                    : 'Semua data sudah terisi. Gunakan opsi "Timpa data" jika ingin mengganti.',
                'skipped' => count($skippedFaktors),
            ]);
        }

        // ── Eksekusi dalam satu transaction ──
        $this->db->transStart();

        try {
            // Batch insert (1 query untuk semua insert)
            if (!empty($toInsert)) {
                $this->db->table('risikokredit')->insertBatch($toInsert);
            }

            // Update satu per satu (tidak bisa batch update dengan data berbeda-beda)
            foreach ($toUpdate as $upd) {
                $this->db->table('risikokredit')
                    ->where('id', $upd['id'])
                    ->update($upd['data']);
            }

            // Sync kalkulator jika ada dan (periode aktif belum punya ATAU force)
            $kalSynced = false;
            if ($prevKalkulator) {
                $existingKal = $this->kalkulatorModel
                    ->where('kodebpr', $this->userKodebpr)
                    ->where('periode_id', $this->periodeId)
                    ->first();

                if (!$existingKal || $forceOverwrite) {
                    // Kolom kalkulator yang disalin
                    $kalCopyFields = [
                        'aba',
                        'kydbank',
                        'kydpihak3',
                        'kydgross',
                        'totalaset',
                        'total25debitur',
                        'perdagangan',
                        'jasa',
                        'konsumsirumah',
                        'sektorekonomi',
                        'asetproduktif',
                        'rasioasetproduktif',
                        'rasiokreditdiberikan',
                        'rasio25debitur',
                        'rasioekonomi',
                        'abanpl',
                        'kydnpl3',
                        'kydnpl4',
                        'kydnpl5',
                        'kreditdpk2',
                        'kreditrestruktur1',
                        'kreditbermasalah',
                        'asetproduktifbermasalah',
                        'kydkoleknpl',
                        'rasioasetproduktifbermasalah',
                        'rasiokreditbermasalah',
                        'rasiokreditkualitasrendah',
                    ];
                    $kalData = [
                        'user_id' => $this->userId,
                        'fullname' => $user['fullname'] ?? 'Unknown',
                        'kodebpr' => $this->userKodebpr,
                        'periode_id' => $this->periodeId,
                    ];
                    foreach ($kalCopyFields as $f) {
                        $kalData[$f] = $prevKalkulator[$f] ?? null;
                    }

                    if ($existingKal) {
                        $kalData['updated_at'] = $now;
                        $this->kalkulatorModel->update($existingKal['id'], $kalData);
                    } else {
                        $kalData['created_at'] = $now;
                        $this->kalkulatorModel->insert($kalData);
                    }
                    $kalSynced = true;
                }
            }

            // Recalculate rata-rata faktor 13 jika ada faktor yang baru disync
            if (!empty($syncedFaktors)) {
                // Recalculate category averages (faktor 1 dan 6)
                $catMapping = [1 => [2, 3, 4, 5], 6 => [7, 8, 9]];
                foreach ($catMapping as $catId => $childIds) {
                    if (array_intersect($syncedFaktors, $childIds)) {
                        $this->calculateAndSaveCategoryAverage(
                            $catId,
                            $childIds,
                            $this->userKodebpr,
                            $this->periodeId
                        );
                    }
                }
                // Recalculate faktor 13 (rata-rata inheren)
                $rataRata = $this->nilaiModel->hitungRataRata(13, $this->userKodebpr, $this->periodeId);
                $this->nilaiModel->insertOrUpdateRataRata($rataRata, 13, $this->userKodebpr, $this->periodeId);
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
            if ($kalSynced)
                $msg .= ', termasuk data kalkulator';
            if (!empty($skippedFaktors)) {
                $msg .= sprintf('. %d faktor dilewati (sudah terisi)', count($skippedFaktors));
            }

            return $this->response->setJSON([
                'status' => 'success',
                'message' => $msg,
                'synced_count' => count($syncedFaktors),
                'skipped_count' => count($skippedFaktors),
                'kal_synced' => $kalSynced,
                'prev_periode' => [
                    'semester' => $prevPeriode['semester'],
                    'tahun' => $prevPeriode['tahun'],
                ],
            ]);

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'syncFromPrevPeriode error: ' . $e->getMessage());
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

        if ($semester === 1) {
            // Semester 1 tahun X → cari Semester 2 tahun X-1
            $prevSemester = 2;
            $prevTahun = $tahun - 1;
        } else {
            // Semester 2 tahun X → cari Semester 1 tahun X
            $prevSemester = 1;
            $prevTahun = $tahun;
        }

        // Cari periode yang cocok di tabel periodes
        $prev = $this->periodeModel
            ->where('semester', $prevSemester)
            ->where('tahun', $prevTahun)
            ->first();

        return $prev ?: null;
    }

}