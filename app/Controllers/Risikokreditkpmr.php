<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_paramprofilrisiko;
use App\Models\M_user;
use App\Models\M_profilrisikocomments;
use App\Models\M_risikokreditkpmr;
use App\Models\M_infobpr;
use App\Models\M_periodesemesteran;
use App\Models\M_profilrisikocommentsread;
use App\Models\M_showprofilresiko;
use App\Models\M_showtks;
use App\Models\M_periodetks;
use Myth\Auth\Config\Services as AuthServices;

class Risikokreditkpmr extends Controller
{
    protected $db;
    protected $auth;
    protected $paramprofilrisikoModel;
    protected $showprofilresikoModel;
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
    protected $userGroups = [];

    private const FAKTOR_IDS = [16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33];
    private const FAKTOR_RANGE_REQUIRED = [16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33];
    private const FAKTOR_33 = 33;
    private const FAKTOR_34 = 34;

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');

        $this->db = \Config\Database::connect();

        $this->paramprofilrisikoModel = new M_periodesemesteran();
        $this->periodeModel = new M_periodetks();
        $this->userModel = new M_user();
        $this->komentarModel = new M_profilrisikocomments();
        $this->nilaiModel = new M_risikokreditkpmr();
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

    private function getNilaiLookup(): array
    {
        $rows = $this->db->table('risikokredit_kpmr as r')
            ->select('r.*')
            ->where('r.kodebpr', $this->userKodebpr)
            ->where('r.periode_id', $this->periodeId)
            ->whereIn('r.faktor1id', range(16, 34))
            ->get()->getResultArray();

        $lookup = [];
        foreach ($rows as $r) {
            $lookup[(int) $r['faktor1id']] = $r;
        }
        return $lookup;
    }

    private function getUnreadCountsBatch(): array
    {
        return $this->commentReadsModel->getAllUnreadCountsBatch(
            'KREDITKPMR',
            $this->userKodebpr,
            $this->userId,
            $this->periodeId,
            range(16, 33)
        );
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
        $nilaiLookup = $this->getNilaiLookup();

        $nilai13 = $nilaiLookup[self::FAKTOR_33] ?? null;
        $nilai14 = $nilaiLookup[self::FAKTOR_34] ?? null;

        $approvalData = $this->nilaiModel
            ->select('
                COUNT(CASE WHEN penilaiankredit IS NOT NULL THEN 1 END) as filled_count,
                SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as approved_count
            ')
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->whereIn('faktor1id', range(16, 33))
            ->first();

        $allFilled = ((int) $approvalData['filled_count'] === 18);
        $allApproved = ($allFilled && (int) $approvalData['approved_count'] === 18);
        $canApprove = $allFilled;
        $unreadCounts = $this->getUnreadCountsBatch();

        $showtks = $this->showtksModel->getByKodebprAndPeriode($this->userKodebpr, $this->periodeId);
        if ($showtks && !empty($showtks['tanggal'])) {
            $showtks['tanggal_display'] = $this->formatDateForDisplay($showtks['tanggal']);
        } else {
            $showtks['tanggal_display'] = '';
        }

        $user = $this->userModel->find($this->userId);

        $data = [
            'judul' => 'Penilaian Risiko Kredit KPMR',
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
            'nilaiLookup' => $nilaiLookup,
            'unreadCounts' => $unreadCounts,
            'showtks' => $showtks,
        ];

        return view('templates/v_header', $data)
            . view('templates/v_sidebar')
            . view('templates/v_topbar')
            . view('Laporansemester/Profilrisiko/kreditkpmr', $data)
            . view('templates/v_footer');
    }

    public function getFactorsData()
    {
        if (!$this->request->isAJAX())
            return $this->response->setStatusCode(403);

        $nilaiLookup = $this->getNilaiLookup();

        $structure = $this->buildFactorStructure($nilaiLookup);
        $updatedAts = array_map(fn($r) => $r['updated_at'] ?? '', $nilaiLookup);
        $etag = '"' . md5(implode(',', $updatedAts)) . '"';
        $ifNoneMatch = $this->request->getHeaderLine('If-None-Match');

        if ($ifNoneMatch === $etag) {
            return $this->response->setStatusCode(304)->setHeader('ETag', $etag)->setHeader('Cache-Control', 'no-cache');
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
                'id' => 16,
                'title' => 'Pilar Pengawasan Direksi dan Dewan Komisaris',
                'type' => 'category',
                'faktor_id' => 16,
                'children' => [
                    ['id' => 17, 'title' => 'Apakah Dewan Komisaris telah melakukan persetujuan terhadap kebijakan manajemen risiko kredit yang disusun oleh Direksi dan melakukan evaluasi secara berkala?', 'faktor_id' => 17],
                    ['id' => 18, 'title' => 'Apakah Dewan Komisaris telah melakukan evaluasi terhadap pertanggungjawaban Direksi atas pelaksanaan kebijakan manajemen risiko kredit secara berkala dan memastikan tindak lanjut hasil evaluasi dimaksud?', 'faktor_id' => 18],
                    ['id' => 19, 'title' => 'Apakah Direksi telah menyusun kebijakan manajemen risiko kredit, melaksanakan secara konsisten, dan melakukan pengkinian secara berkala?', 'faktor_id' => 19],
                    ['id' => 20, 'title' => 'Apakah Direksi telah memiliki kemampuan untuk mengambil tindakan yang diperlukan dalam rangka mitigasi risiko kredit, dan melakukan komunikasi kebijakan manajemen risiko kredit terhadap seluruh jenjang organisasi BPR?', 'faktor_id' => 20],
                    ['id' => 21, 'title' => 'Apakah BPR telah memiliki kecukupan organisasi yang menangani fungsi kredit dan fungsi manajemen risiko kredit?', 'faktor_id' => 21],
                    ['id' => 22, 'title' => 'Apakah Direksi telah menerapkan kebijakan pengelolaan SDM dalam rangka penerapan Manajemen Risiko kredit?', 'faktor_id' => 22],
                ],
            ],
            [
                'id' => 23,
                'title' => 'Pilar Kecukupan Kebijakan, Prosedur, dan Limit',
                'type' => 'category',
                'faktor_id' => 23,
                'children' => [
                    ['id' => 24, 'title' => 'Apakah BPR telah memiliki kebijakan manajemen risiko kredit yang memadai dan disusun dengan mempertimbangkan visi, misi, skala usaha dan kompleksitas bisnis, serta kecukupan SDM?', 'faktor_id' => 24],
                    ['id' => 25, 'title' => 'Apakah BPR: ● Memiliki prosedur manajemen risiko kredit dan penetapan limit risiko kredit yang ditetapkan oleh Direksi; ● Melaksanakan prosedur manajemen risiko kredit dan penetapan limit risiko kredit secara konsisten untuk seluruh aktivitas; dan ● Melakukan evaluasi dan pengkinian terhadap prosedur manajemen risiko kredit dan penetapan limit risiko kredit secara berkala?', 'faktor_id' => 25],
                    ['id' => 26, 'title' => 'Apakah BPR telah memiliki kebijakan dan prosedur penerbitan produk dan/atau pelaksanaan aktivitas baru yang mencakup identifikasi dan mitigasi risiko kredit sesuai dengan ketentuan?', 'faktor_id' => 26],
                ],
            ],
            [
                'id' => 27,
                'title' => 'Kecukupan Proses dan Sistem Manajemen Informasi',
                'type' => 'category',
                'faktor_id' => 27,
                'children' => [
                    ['id' => 28, 'title' => 'Apakah BPR telah melaksanakan proses manajemen risiko kredit yang melekat pada kegiatan usaha BPR?', 'faktor_id' => 28],
                    ['id' => 29, 'title' => 'Apakah BPR telah memiliki sistem informasi manajemen risiko yang mendukung Direksi dalam pengambilan keputusan terkait risiko kredit serta telah dilaporkan kepada Direksi secara berkala?', 'faktor_id' => 29],
                ],
            ],
            [
                'id' => 30,
                'title' => 'Sistem Pengendalian Internal yang Menyeluruh',
                'type' => 'category',
                'faktor_id' => 30,
                'children' => [
                    ['id' => 31, 'title' => 'Apakah SKAI atau PEAI telah melaksanakan audit secara berkala terhadap penerapan manajemen risiko kredit, menyampaikan laporan hasil audit intern, dan memastikan tindaklanjut atas temuan pemeriksaan?', 'faktor_id' => 31],
                    ['id' => 32, 'title' => 'Apakah sistem pengendalian intern terhadap risiko kredit telah dilaksanakan oleh seluruh jenjang organisasi BPR?', 'faktor_id' => 32],
                ],
            ],
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

        $counts = $this->commentReadsModel->getAllUnreadCountsBatch(
            'KREDITKPMR',
            $this->userKodebpr,
            $this->userId,
            $this->periodeId,
            range(16, 33)
        );

        return $this->response
            ->setHeader('Cache-Control', 'private, max-age=30')
            ->setHeader('Vary', 'Cookie')
            ->setJSON(['status' => 'success', 'counts' => $counts]);
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
            'KREDITKPMR',
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
            'KREDITKPMR',
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
            'KREDITKPMR',
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
            return redirect()->to(base_url('Risikokreditkpmr'));
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
                'subkategori' => 'KREDITKPMR',
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
                        'KREDITKPMR',
                        $faktorId,
                        $this->userKodebpr,
                        $this->periodeId
                    ),
                ]);
            }

            return redirect()->to(base_url('Risikokreditkpmr'))->with('message', 'Komentar berhasil ditambahkan');

        } catch (\Exception $e) {
            log_message('error', 'Error tambahKomentar KPMR: ' . $e->getMessage());
            if ($isAjax)
                return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menambahkan komentar']);
            return redirect()->back()->with('err', 'Gagal menambahkan komentar');
        }
    }

    public function cekKomentarBaru()
    {
        $kodebpr = $this->request->getGet('kodebpr');
        $lastVisit = $this->request->getGet('last_visit');

        $results = $this->komentarModel
            ->select('faktor1id, COUNT(*) as jumlah')
            ->where('subkategori', 'KREDITKPMR')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('created_at >', $lastVisit)
            ->groupBy('faktor1id')
            ->findAll();

        return $this->response->setJSON($results);
    }

    public function saveKomentar()
    {
        $this->komentarModel->insert([
            'faktor1id' => $this->request->getPost('faktor_id'),
            'kodebpr' => $this->request->getPost('kodebpr'),
            'komentar' => $this->request->getPost('komentar'),
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'user_id' => $this->userId,
        ]);
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

        $data = [
            'penilaiankredit' => $this->request->getPost('penilaiankredit'),
            'penjelasanpenilaian' => $this->request->getPost('penjelasanpenilaian'),
            'keterangan' => $this->request->getPost('keterangan'),
            'is_approved' => 0,
            'accdir2' => 0,
            'user_id' => $this->userId,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        try {
            $this->db->table('risikokredit_kpmr')
                ->where('faktor1id', $faktor1id)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
                ->update($data);

            $this->handleCategoryAndGlobalRecalc((int) $faktor1id);

            $nilai13 = $this->nilaiModel->where(['faktor1id' => 33, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first();

            return $this->response->setJSON(['status' => 'success', 'message' => 'Data berhasil diubah', 'peringkat13' => $nilai13['penilaiankredit'] ?? null]);

        } catch (\Exception $e) {
            log_message('error', 'Error ubah KPMR: ' . $e->getMessage());
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
            $this->db->table('risikokredit_kpmr')
                ->where('faktor1id', $faktor1id)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
                ->update(['keterangan' => $keterangan, 'penilaiankredit' => $existing['penilaiankredit'], 'user_id' => $this->userId, 'updated_at' => date('Y-m-d H:i:s'), 'is_approved' => 0]);

            $this->db->table('risikokredit_kpmr')
                ->where('faktor1id', 33)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
                ->update(['accdir2' => 0, 'is_approved' => 0]);

            return $this->response->setJSON(['status' => 'success', 'message' => 'Keterangan kesimpulan berhasil diubah']);

        } catch (\Exception $e) {
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

        try {
            $this->db->table('risikokredit_kpmr')
                ->where('faktor1id', $faktor1id)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
                ->update(['keterangan' => $keterangan, 'user_id' => $this->userId, 'updated_at' => date('Y-m-d H:i:s'), 'is_approved' => 0, 'accdir2' => 0]);

            return $this->response->setJSON(['status' => 'success', 'message' => 'Keterangan berhasil diubah']);

        } catch (\Exception $e) {
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
            $existing = $this->nilaiModel
                ->where('faktor1id', $faktorId)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
                ->first();

            if ($existing) {
                $data['updated_at'] = date('Y-m-d H:i:s');
                unset($data['created_at']);
                $this->db->table('risikokredit_kpmr')
                    ->where('faktor1id', $faktorId)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
                    ->update($data);
            } else {
                $this->db->table('risikokredit_kpmr')->insert($data);
            }

            $this->handleCategoryAndGlobalRecalc((int) $faktorId);

            $nilai13 = $this->nilaiModel->where(['faktor1id' => 33, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first();

            return $this->response->setJSON(['status' => 'success', 'message' => 'Nilai berhasil ditambahkan', 'peringkat13' => $nilai13['penilaiankredit'] ?? null]);

        } catch (\Exception $e) {
            log_message('error', 'Error tambahNilai KPMR: ' . $e->getMessage());
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menambahkan nilai: ' . $e->getMessage()]);
        }
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
        $user = $this->userModel->find($this->userId);

        try {
            $existing = $this->nilaiModel->where(['faktor1id' => 33, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first();

            $updateData = [
                'penilaiankredit' => $penilaiankredit,
                'penjelasanpenilaian' => 'Tingkat Risiko Kredit KPMR: ' . $labels[$penilaiankredit],
                'is_manual' => 1,
                'user_id' => $this->userId,
                'fullname' => $user['fullname'] ?? 'Unknown',
                'is_approved' => 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($existing) {
                $this->nilaiModel->where(['faktor1id' => 33, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->set($updateData)->update();
            } else {
                $updateData['faktor1id'] = 33;
                $updateData['kodebpr'] = $this->userKodebpr;
                $updateData['periode_id'] = $this->periodeId;
                $updateData['accdir2'] = 0;
                $updateData['created_at'] = date('Y-m-d H:i:s');
                $this->nilaiModel->insert($updateData);
            }

            return $this->response->setJSON(['status' => 'success', 'peringkat' => $penilaiankredit, 'is_manual' => true]);

        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function resetPeringkat13Auto()
    {
        if (!$this->request->isAJAX())
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid request']);

        try {
            $rataRata = $this->nilaiModel->hitungRataRata(33, $this->userKodebpr, $this->periodeId);
            $rataRata = ($rataRata - floor($rataRata) >= 0.5) ? ceil($rataRata) : floor($rataRata);
            $labels = ['1' => 'Sangat Rendah', '2' => 'Rendah', '3' => 'Sedang', '4' => 'Tinggi', '5' => 'Sangat Tinggi'];
            $user = $this->userModel->find($this->userId);

            $this->nilaiModel->where(['faktor1id' => 33, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])
                ->set(['penilaiankredit' => $rataRata, 'penjelasanpenilaian' => 'Tingkat Risiko Kredit KPMR: ' . ($labels[$rataRata] ?? ''), 'is_manual' => 0, 'user_id' => $this->userId, 'fullname' => $user['fullname'] ?? 'Unknown', 'is_approved' => 0, 'updated_at' => date('Y-m-d H:i:s')])
                ->update();

            return $this->response->setJSON(['status' => 'success', 'peringkat' => $rataRata, 'is_manual' => false]);

        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
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

        $keterangan = $this->request->getPost('keterangan');
        $user = $this->userModel->find($this->userId);

        try {
            $existing = $this->nilaiModel->where(['faktor1id' => 33, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first();

            if ($existing) {
                $this->nilaiModel->where(['faktor1id' => 33, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])
                    ->set(['keterangan' => $keterangan, 'user_id' => $this->userId, 'fullname' => $user['fullname'] ?? 'Unknown', 'updated_at' => date('Y-m-d H:i:s')])
                    ->update();
                return redirect()->to(base_url('Risikokreditkpmr'))->with('message', 'Kesimpulan berhasil diupdate');
            }

            $rataRata = $this->nilaiModel->hitungRataRata(33, $this->userKodebpr, $this->periodeId);
            $rataRata = ($rataRata - floor($rataRata) >= 0.5) ? ceil($rataRata) : floor($rataRata);
            $labels = ['1' => 'Sangat Rendah', '2' => 'Rendah', '3' => 'Sedang', '4' => 'Tinggi', '5' => 'Sangat Tinggi'];

            $this->nilaiModel->insert([
                'faktor1id' => 33,
                'penilaiankredit' => $rataRata,
                'penjelasanpenilaian' => 'Tingkat Risiko Kredit KPMR: ' . ($labels[$rataRata] ?? 'N/A'),
                'keterangan' => $keterangan,
                'fullname' => $user['fullname'] ?? 'Unknown',
                'user_id' => $this->userId,
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId,
                'is_approved' => 0,
                'accdir2' => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            return redirect()->to(base_url('Risikokreditkpmr'))->with('message', 'Kesimpulan berhasil disimpan');

        } catch (\Exception $e) {
            log_message('error', 'Error simpanKesimpulan13 KPMR: ' . $e->getMessage());
            return redirect()->back()->with('err', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function simpanNilai13()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;
        $keterangan = $this->request->getPost('keterangan');
        $this->nilaiModel->insertOrUpdateRataRata(0, 33, $this->userKodebpr, $this->periodeId, $keterangan);
        return redirect()->back()->with('message', 'Kesimpulan berhasil disimpan atau diperbarui.');
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
        $penjelasan = 'Tingkat Risiko Kredit KPMR: ' . ($labels[$penilaiankredit] ?? '');

        try {
            $existing = $this->nilaiModel->where(['faktor1id' => 34, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first();

            $base = ['penilaiankredit' => $penilaiankredit, 'penjelasanpenilaian' => $penjelasan, 'keterangan' => 'Tingkat Risiko Kredit KPMR Posisi Sebelumnya', 'user_id' => $this->userId, 'fullname' => $user['fullname'] ?? 'Unknown'];

            if ($existing) {
                $this->nilaiModel->where(['faktor1id' => 34, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->set(array_merge($base, ['updated_at' => date('Y-m-d H:i:s')]))->update();
            } else {
                $this->nilaiModel->insert(array_merge($base, ['faktor1id' => 34, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId, 'is_approved' => 0, 'accdir2' => 0, 'created_at' => date('Y-m-d H:i:s')]));
            }

            return redirect()->to(base_url('Risikokreditkpmr'))->with('message', 'Data berhasil disimpan');

        } catch (\Exception $e) {
            return redirect()->back()->with('err', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function approve($idNilai = null)
    {
        if ($idNilai === null)
            return redirect()->back()->with('err', 'ID tidak valid');
        $this->db->table('risikokredit_kpmr')->where('id', $idNilai)->update(['is_approved' => 1, 'approved_by' => $this->userId, 'approved_at' => date('Y-m-d H:i:s')]);
        return redirect()->back()->with('message', 'Data berhasil disetujui');
    }

    public function unapprove($idNilai)
    {
        if (!is_numeric($idNilai) || $idNilai <= 0)
            return redirect()->back()->with('err', 'ID tidak valid.');
        $nilaiFaktor = $this->nilaiModel->find($idNilai);
        if (!$nilaiFaktor)
            return redirect()->back()->with('err', 'Data tidak ditemukan.');
        if ($this->nilaiModel->update($idNilai, ['is_approved' => 0, 'approved_by' => $this->userId, 'approved_at' => date('Y-m-d H:i:s')])) {
            return redirect()->back()->with('message', 'Data approval dibatalkan.');
        }
        return redirect()->back()->with('err', 'Terjadi kesalahan.');
    }

    public function approveSemua()
    {
        $isAjax = $this->request->isAJAX();

        if (!$this->userKodebpr || !$this->periodeId) {
            if ($isAjax)
                return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak valid']);
            return redirect()->back()->with('err', 'Data tidak valid');
        }

        $checkData = $this->nilaiModel
            ->select('COUNT(CASE WHEN penilaiankredit IS NOT NULL THEN 1 END) as filled_count')
            ->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
            ->whereIn('faktor1id', range(16, 33))->first();

        if ((int) $checkData['filled_count'] < 18) {
            if ($isAjax)
                return $this->response->setJSON(['status' => 'error', 'message' => 'Semua faktor harus diisi terlebih dahulu']);
            return redirect()->back()->with('err', 'Semua faktor harus diisi terlebih dahulu');
        }

        try {
            $this->db->table('risikokredit_kpmr')
                ->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
                ->whereIn('faktor1id', range(16, 33))
                ->update(['is_approved' => 1, 'approved_by' => $this->userId, 'approved_at' => date('Y-m-d H:i:s')]);

            if ($isAjax)
                return $this->response->setJSON(['status' => 'success', 'message' => 'Semua data berhasil disetujui.']);
            return redirect()->back()->with('message', 'Semua data berhasil disetujui.');

        } catch (\Exception $e) {
            if ($isAjax)
                return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
            return redirect()->back()->with('err', 'Terjadi kesalahan');
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
            $this->db->table('risikokredit_kpmr')
                ->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
                ->whereIn('faktor1id', range(16, 33))
                ->update(['is_approved' => 0, 'approved_by' => $this->userId, 'approved_at' => date('Y-m-d H:i:s')]);

            if ($isAjax)
                return $this->response->setJSON(['status' => 'success', 'message' => 'Semua data berhasil dibatalkan persetujuannya.']);
            return redirect()->back()->with('message', 'Semua data berhasil dibatalkan persetujuannya.');

        } catch (\Exception $e) {
            log_message('error', 'Error unapproveSemua KPMR: ' . $e->getMessage());
            if ($isAjax)
                return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
            return redirect()->back()->with('err', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function accdir2()
    {
        $faktor1id = $this->request->getPost('faktor1id');
        if (!$faktor1id)
            return redirect()->back()->with('error', 'Data tidak lengkap');
        $this->db->table('risikokredit_kpmr')
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
            return redirect()->to('/Risikokreditkpmr')->with('error', 'Data tidak lengkap');

        $nilaiFaktor = $this->nilaiModel->where(['faktor1id' => $faktor1id, 'kodebpr' => $kodebpr, 'periode_id' => $periodeId])->first();
        if (!$nilaiFaktor)
            return redirect()->to('/Risikokreditkpmr')->with('error', 'Data tidak ditemukan');

        if ($this->nilaiModel->update($nilaiFaktor['id'], ['accdir2' => 0, 'accdir2_by' => $this->userId, 'approved_at' => date('Y-m-d H:i:s')])) {
            return redirect()->to('/Risikokreditkpmr')->with('message', 'Persetujuan dibatalkan');
        }
        return redirect()->to('/Risikokreditkpmr')->with('error', 'Gagal memperbarui data');
    }

    public function recalculateCategoryAverage()
    {
        if (!$this->request->isAJAX())
            return $this->response->setStatusCode(403);

        $faktorId = $this->request->getPost('faktor_id');
        $categoryMapping = [16 => [17, 18, 19, 20, 21, 22], 23 => [24, 25, 26], 27 => [28, 29], 30 => [31, 32]];

        foreach ($categoryMapping as $categoryId => $childrenIds) {
            if (in_array($faktorId, $childrenIds)) {
                $this->calculateAndSaveCategoryAverage($categoryId, $childrenIds, $this->userKodebpr, $this->periodeId);
                $rataRata = $this->nilaiModel->hitungRataRata(33, $this->userKodebpr, $this->periodeId);
                $this->nilaiModel->insertOrUpdateRataRata($rataRata, 33, $this->userKodebpr, $this->periodeId);
                return $this->response->setJSON(['status' => 'success', 'message' => 'Kategori berhasil di-recalculate']);
            }
        }
        return $this->response->setJSON(['status' => 'error', 'message' => 'Kategori tidak ditemukan']);
    }

    public function getNilaiByFaktorId($faktorId)
    {
        if (!$this->request->isAJAX() || !is_numeric($faktorId)) {
            return $this->response->setStatusCode(404)->setBody('Not Found');
        }
        return $this->response->setJSON($this->nilaiModel->getNilaiByFaktorId($faktorId));
    }

    public function setNullKolom($id)
    {
        if ($redirect = $this->checkAuth())
            return $redirect;
        if ($this->nilaiModel->setNullKolom($id)) {
            session()->setFlashdata('message', 'Data berhasil dihapus');
        } else {
            session()->setFlashdata('err', 'Data gagal dihapus');
        }
        return redirect()->to(base_url('Risikokreditkpmr'));
    }

    public function checkPrevPeriode()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error']);
        }
        if (!$this->userKodebpr || !$this->periodeId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak valid']);
        }

        $periodeModel = new M_periodesemesteran();
        $currentPeriode = $periodeModel->find($this->periodeId);
        if (!$currentPeriode) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Periode tidak ditemukan']);
        }

        $prevPeriode = $this->resolvePrevPeriode($currentPeriode, $periodeModel);
        if (!$prevPeriode) {
            return $this->response->setJSON([
                'status' => 'no_prev',
                'message' => 'Tidak ada periode sebelumnya yang terdaftar',
            ]);
        }

        // COUNT ringan — tidak ambil data
        $currentCount = (int) $this->db->table('risikokredit_kpmr')
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->whereIn('faktor1id', range(16, 32))
            ->where('penilaiankredit IS NOT NULL')
            ->countAllResults();

        $prevCount = (int) $this->db->table('risikokredit_kpmr')
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $prevPeriode['id'])
            ->whereIn('faktor1id', range(16, 32))
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

        $periodeModel = new M_periodesemesteran();
        $currentPeriode = $periodeModel->find($this->periodeId);
        if (!$currentPeriode) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Periode tidak ditemukan']);
        }

        $prevPeriode = $this->resolvePrevPeriode($currentPeriode, $periodeModel);
        if (!$prevPeriode) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Periode sebelumnya tidak ditemukan']);
        }

        // Ambil data faktor 16–32 dari periode sebelumnya (1 query)
        $prevRows = $this->db->table('risikokredit_kpmr')
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $prevPeriode['id'])
            ->whereIn('faktor1id', range(16, 32))
            ->get()->getResultArray();

        // Ambil existing di periode aktif untuk lookup (1 query)
        $existingRows = $this->db->table('risikokredit_kpmr')
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->whereIn('faktor1id', range(16, 32))
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
                $rowData['created_at'] = $now;
                $toInsert[] = $rowData;
            }
            $syncedFaktors[] = $fid;
        }

        if (empty($syncedFaktors)) {
            return $this->response->setJSON([
                'status' => 'no_change',
                'message' => empty($skippedFaktors)
                    ? 'Tidak ada data yang bisa disinkronisasi'
                    : 'Semua data sudah terisi. Gunakan opsi "Timpa data" jika ingin mengganti.',
                'skipped' => count($skippedFaktors),
            ]);
        }

        $this->db->transStart();

        try {
            if (!empty($toInsert)) {
                $this->db->table('risikokredit_kpmr')->insertBatch($toInsert);
            }
            foreach ($toUpdate as $upd) {
                $this->db->table('risikokredit_kpmr')
                    ->where('id', $upd['id'])
                    ->update($upd['data']);
            }

            // Recalculate category averages dan faktor 33
            $catMapping = [16 => [17, 18, 19, 20, 21, 22], 23 => [24, 25, 26], 27 => [28, 29], 30 => [31, 32]];
            foreach ($catMapping as $catId => $childIds) {
                if (array_intersect($syncedFaktors, $childIds)) {
                    $this->calculateAndSaveCategoryAverage($catId, $childIds, $this->userKodebpr, $this->periodeId);
                }
            }
            $rataRata = $this->nilaiModel->hitungRataRata(33, $this->userKodebpr, $this->periodeId);
            $this->nilaiModel->insertOrUpdateRataRata($rataRata, 33, $this->userKodebpr, $this->periodeId);

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
                'prev_periode' => ['semester' => $prevPeriode['semester'], 'tahun' => $prevPeriode['tahun']],
            ]);

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'syncFromPrevPeriode KPMR error: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Gagal sinkronisasi: ' . $e->getMessage(),
            ]);
        }
    }

    private function resolvePrevPeriode(array $currentPeriode, $periodeModel = null): ?array
    {
        $semester = (int) $currentPeriode['semester'];
        $tahun = (int) $currentPeriode['tahun'];

        if ($semester === 1) {
            $prevSemester = 2;
            $prevTahun = $tahun - 1;
        } else {
            $prevSemester = 1;
            $prevTahun = $tahun;
        }

        $model = $periodeModel ?? new M_periodesemesteran();

        $prev = $model->where('semester', $prevSemester)->where('tahun', $prevTahun)->first();

        return $prev ?: null;
    }

    public function exporttxtrisikokreditkpmr()
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
        $nomorsurat = $periodeDetail['nomorsurat'];
        $jeniskoreksi = $periodeDetail['jeniskoreksi'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];
        $kategori = $periodeDetail['kategori'];
        $tahun = $periodeDetail['tahun'] ?? date('Y');
        $semester = $periodeDetail['semester'] ?? '';

        $exportDate = (strtolower($semester) === 'ganjil' || $semester === '1') ? "{$tahun}-06-30" : "{$tahun}-12-31";
        $titleDate = str_replace('-', '', $exportDate);

        $sanitize = function ($text) {
            $text = str_replace(["\r", "\n"], ' ', $text);
            $text = str_replace(['"', '%', '|'], ['"', '"', "'", "'", ' persen ', ' '], $text);
            $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
            return trim(preg_replace('/\s+/', ' ', $text));
        };

        $data_kpmr = $this->nilaiModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $sandibpr = $data_infobpr[0]['sandibpr'] ?? '';
            $kodejenis = $data_infobpr[0]['kodejenis'] ?? '';
        }

        $kodeMap = [16 => '1310', 17 => '1311', 18 => '1312', 19 => '1313', 20 => '1314', 21 => '1315', 22 => '1316', 23 => '1320', 24 => '1321', 25 => '1322', 26 => '1323', 27 => '1330', 28 => '1331', 29 => '1332', 30 => '1340', 31 => '1341', 32 => '1342', 33 => '1350'];

        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0102|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";

        usort($data_kpmr, fn($a, $b) => ($a['faktor1id'] ?? 0) <=> ($b['faktor1id'] ?? 0));

        foreach ($data_kpmr as $row) {
            $fid = $row['faktor1id'] ?? null;
            if (!isset($kodeMap[$fid]))
                continue;
            $output .= "D01|{$kodeMap[$fid]}|{$sanitize($row['penilaiankredit'])}|{$sanitize($row['keterangan'])}\r\n";
        }

        $filename = "TKSBPRKS-0102-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }

    private function handleCategoryAndGlobalRecalc(int $faktorId): void
    {
        $categoryMapping = [16 => [17, 18, 19, 20, 21, 22], 23 => [24, 25, 26], 27 => [28, 29], 30 => [31, 32]];

        foreach ($categoryMapping as $categoryId => $childrenIds) {
            if (in_array($faktorId, $childrenIds)) {
                $this->calculateAndSaveCategoryAverage($categoryId, $childrenIds, $this->userKodebpr, $this->periodeId);
                break;
            }
        }

        if (in_array($faktorId, [16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32])) {
            $rataRata = $this->nilaiModel->hitungRataRata(33, $this->userKodebpr, $this->periodeId);
            $this->nilaiModel->insertOrUpdateRataRata($rataRata, 33, $this->userKodebpr, $this->periodeId);
        }

        $this->db->table('risikokredit_kpmr')
            ->where('faktor1id', 33)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
            ->update(['accdir2' => 0, 'is_approved' => 0]);
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

        $existingCategory = $this->nilaiModel->where(['faktor1id' => $categoryFaktorId, 'kodebpr' => $kodebpr, 'periode_id' => $periodeId])->first();

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
            $dataCategory['created_at'] = 'Y-m-d H:i:s';
            $dataCategory['is_approved'] = 0;
            $dataCategory['accdir2'] = 0;
            $this->nilaiModel->insert($dataCategory);
        }

        return $roundedAverage;
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

    private function getMatchingProfilRisikoPeriodeId($periodeTKSId)
    {
        $periodeTKS = $this->periodetksModel->find($periodeTKSId);
        if (!$periodeTKS)
            return null;

        $list = $this->paramprofilrisikoModel->where(['semester' => $periodeTKS['semester'], 'tahun' => $periodeTKS['tahun'], 'kodebpr' => $this->userKodebpr])->findAll();
        return !empty($list) ? $list[0]['id'] : null;
    }

    private function getPenilaianConfig(): array
    {
        return [
            16 => ['descriptions' => [1 => 'Parameter Pengawasan Direksi dan Komisaris berada pada tingkat risiko yang Sangat Rendah', 2 => 'Parameter Pengawasan Direksi dan Komisaris berada pada tingkat risiko yang Rendah', 3 => 'Parameter Pengawasan Direksi dan Komisaris berada pada tingkat risiko yang Sedang', 4 => 'Parameter Pengawasan Direksi dan Komisaris berada pada tingkat risiko yang Tinggi', 5 => 'Parameter Pengawasan Direksi dan Komisaris berada pada tingkat risiko yang Sangat Tinggi'], 'catatan' => 'KPMR'],
            17 => ['descriptions' => [1 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko kredit <br>• Dewan Komisaris telah melakukan evaluasi terhadap kebijakan Manajemen Risiko kredit <br>• Evaluasi dilakukan secara berkala paling sedikit satu kali dalam satu tahun <br>• Evaluasi yang dilakukan relevan dengan kebutuhan penyesuaian kebijakan.', 2 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko kredit <br>• Dewan Komisaris telah melakukan evaluasi secara berkala paling sedikit satu kali dalam satu tahun.', 3 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko kredit <br>• Evaluasi tidak dilakukan secara berkala.', 4 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko kredit <br>• Dewan Komisaris tidak melakukan evaluasi.', 5 => '• Dewan Komisaris tidak memberikan persetujuan terhadap kebijakan Manajemen Risiko kredit <br>• Dewan Komisaris tidak melakukan evaluasi.'], 'catatan' => 'KPMR'],
            18 => ['descriptions' => [1 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan oleh Direksi <br>• Evaluasi dilakukan secara berkala setiap semester <br>• Dewan Komisaris telah memastikan tindak lanjut hasil evaluasi <br>• Evaluasi relevan dengan pelaksanaan kebijakan.', 2 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan oleh Direksi <br>• Evaluasi dilakukan secara berkala setiap semester <br>• Dewan Komisaris telah memastikan tindak lanjut hasil evaluasi.', 3 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan oleh Direksi <br>• Evaluasi dilakukan secara berkala <br>• Dewan Komisaris tidak memastikan tindak lanjut hasil evaluasi.', 4 => '• Dewan Komisaris telah melakukan evaluasi <br>• Evaluasi tidak dilakukan secara berkala <br>• Dewan Komisaris tidak memastikan tindak lanjut.', 5 => 'Dewan Komisaris tidak melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko kredit oleh Direksi.'], 'catatan' => 'KPMR'],
            19 => ['descriptions' => [1 => '• Direksi telah menyusun kebijakan Manajemen Risiko kredit <br>• Menjalankan kegiatan usaha berdasarkan kebijakan yang ditetapkan <br>• Direksi melakukan pengkinian secara berkala <br>• Kebijakan terbukti memitigasi Risiko kredit.', 2 => '• Direksi telah menyusun kebijakan Manajemen Risiko kredit <br>• Menjalankan kegiatan usaha berdasarkan kebijakan yang ditetapkan <br>• Direksi melakukan pengkinian secara berkala.', 3 => '• Direksi telah menyusun kebijakan Manajemen Risiko kredit <br>• Menjalankan kegiatan usaha berdasarkan kebijakan yang ditetapkan <br>• Direksi tidak melakukan pengkinian secara berkala.', 4 => '• Direksi telah menyusun kebijakan Manajemen Risiko kredit <br>• Tidak menjalankan kegiatan usaha berdasarkan kebijakan yang ditetapkan.', 5 => 'Direksi tidak menyusun kebijakan Manajemen Risiko kredit.'], 'catatan' => 'KPMR'],
            20 => ['descriptions' => [1 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko <br>• Direksi mengomunikasikan kebijakan Manajemen Risiko kredit <br>• Seluruh jenjang organisasi mampu memahami kebijakan.', 2 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko <br>• Direksi mengomunikasikan kebijakan Manajemen Risiko kredit <br>• Tidak seluruh jenjang memahami namun tidak signifikan.', 3 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko <br>• Direksi mengomunikasikan kebijakan <br>• Tidak seluruh jenjang memahami dan menimbulkan dampak signifikan.', 4 => '• Direksi mengambil tindakan yang diperlukan <br>• Direksi tidak mengomunikasikan kebijakan.', 5 => '• Direksi tidak mengambil tindakan yang diperlukan <br>• Direksi tidak mengomunikasikan kebijakan.'], 'catatan' => 'KPMR'],
            21 => ['descriptions' => [1 => '• Memiliki unit kerja yang menangani fungsi kredit secara lengkap tanpa rangkap jabatan <br>• Unit kerja melaksanakan tugas sesuai pedoman <br>• Memiliki SKMR atau PEMR yang mampu memitigasi Risiko kredit.', 2 => '• Memiliki unit kerja tidak lengkap tanpa rangkap jabatan berbahaya <br>• Unit kerja melaksanakan tugas sesuai pedoman <br>• Memiliki SKMR atau PEMR yang mampu memitigasi.', 3 => '• Memiliki unit kerja tidak lengkap dengan rangkap jabatan tidak berbahaya <br>• Unit kerja melaksanakan tugas sesuai pedoman <br>• Memiliki SKMR atau PEMR namun tidak optimal.', 4 => '• Memiliki unit kerja tidak lengkap dengan rangkap jabatan berbahaya <br>• Unit kerja melaksanakan tugas sesuai pedoman <br>• Memiliki SKMR atau PEMR namun tidak optimal.', 5 => '• Memiliki unit kerja tidak lengkap dengan rangkap jabatan berbahaya <br>• Unit kerja tidak melaksanakan tugas sesuai pedoman <br>• Memiliki SKMR atau PEMR namun tidak optimal.'], 'catatan' => 'KPMR'],
            22 => ['descriptions' => [1 => '• Terdapat kesesuaian kualifikasi SDM dengan jabatan <br>• Terdapat upaya peningkatan kompetensi SDM secara konsisten <br>• Pemenuhan standar kinerja SDM sesuai tugas dan tanggung jawab.', 2 => '• Terdapat ketidaksesuaian kualifikasi SDM namun tidak signifikan <br>• Terdapat upaya peningkatan kompetensi secara konsisten <br>• Pemenuhan standar kinerja sesuai tugas.', 3 => '• Terdapat ketidaksesuaian kualifikasi SDM tidak signifikan <br>• Upaya peningkatan kompetensi tidak konsisten <br>• Pemenuhan standar kinerja sesuai tugas.', 4 => '• Terdapat ketidaksesuaian kualifikasi SDM yang signifikan <br>• Upaya peningkatan tidak konsisten <br>• Pemenuhan standar kinerja tidak sesuai namun tidak signifikan.', 5 => '• Terdapat ketidaksesuaian kualifikasi SDM yang signifikan <br>• Tidak terdapat upaya peningkatan kompetensi <br>• Pemenuhan standar kinerja tidak sesuai dan signifikan.'], 'catatan' => 'KPMR'],
            23 => ['descriptions' => [1 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Sangat Rendah', 2 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Rendah', 3 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Sedang', 4 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Tinggi', 5 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Sangat Tinggi'], 'catatan' => 'KPMR'],
            24 => ['descriptions' => [1 => '• Telah memiliki kebijakan Manajemen Risiko kredit <br>• Terdapat kesesuaian antara substansi kebijakan dengan ketentuan <br>• Terdapat keselarasan dengan visi, misi, dan skala usaha.', 2 => '• Telah memiliki kebijakan Manajemen Risiko kredit <br>• Terdapat ketidaksesuaian tidak signifikan antara substansi kebijakan dengan ketentuan <br>• Terdapat keselarasan dengan visi, misi, dan skala usaha.', 3 => '• Telah memiliki kebijakan Manajemen Risiko kredit <br>• Terdapat ketidaksesuaian tidak signifikan <br>• Terdapat ketidakselarasan tidak menimbulkan dampak signifikan.', 4 => '• Telah memiliki kebijakan Manajemen Risiko kredit <br>• Terdapat ketidaksesuaian yang signifikan <br>• Terdapat ketidakselarasan yang menimbulkan dampak signifikan.', 5 => 'Tidak memiliki kebijakan Manajemen Risiko kredit.'], 'catatan' => 'KPMR'],
            25 => ['descriptions' => [1 => '• Memiliki prosedur dan penetapan limit yang ditetapkan Direksi <br>• Melaksanakan prosedur secara konsisten <br>• Melakukan evaluasi dan pengkinian prosedur secara berkala.', 2 => '• Memiliki prosedur dan penetapan limit yang ditetapkan Direksi <br>• Melaksanakan prosedur secara konsisten <br>• Tidak melakukan evaluasi namun tidak berdampak signifikan.', 3 => '• Memiliki prosedur dan penetapan limit yang ditetapkan Direksi <br>• Melaksanakan prosedur secara konsisten <br>• Tidak melakukan evaluasi dan berdampak signifikan.', 4 => '• Memiliki prosedur dan penetapan limit yang ditetapkan Direksi <br>• Tidak melaksanakan prosedur secara konsisten <br>• Tidak melakukan evaluasi dan pengkinian.', 5 => 'Tidak memiliki prosedur Manajemen Risiko kredit dan penetapan limit.'], 'catatan' => 'KPMR'],
            26 => ['descriptions' => [1 => '• Memiliki kebijakan dan prosedur produk/aktivitas baru <br>• Menerapkan kebijakan dan prosedur saat penerbitan produk baru <br>• Terdapat kesesuaian dengan ketentuan.', 2 => '• Memiliki kebijakan dan prosedur produk/aktivitas baru <br>• Menerapkan kebijakan dan prosedur <br>• Terdapat ketidaksesuaian tidak signifikan.', 3 => '• Memiliki kebijakan dan prosedur produk/aktivitas baru <br>• Menerapkan kebijakan dan prosedur <br>• Terdapat ketidaksesuaian yang signifikan.', 4 => '• Memiliki kebijakan dan prosedur produk/aktivitas baru <br>• Tidak menerapkan kebijakan dan prosedur <br>• Terdapat ketidaksesuaian yang signifikan.', 5 => 'Tidak memiliki kebijakan dan prosedur penerbitan produk dan/atau aktivitas baru.'], 'catatan' => 'KPMR'],
            27 => ['descriptions' => [1 => 'Parameter Kecukupan Proses dan Sistem Manajemen Risiko berada pada tingkat risiko yang Sangat Rendah', 2 => 'Parameter Kecukupan Proses dan Sistem Manajemen Risiko berada pada tingkat risiko yang Rendah', 3 => 'Parameter Kecukupan Proses dan Sistem Manajemen Risiko berada pada tingkat risiko yang Sedang', 4 => 'Parameter Kecukupan Proses dan Sistem Manajemen Risiko berada pada tingkat risiko yang Tinggi', 5 => 'Parameter Kecukupan Proses dan Sistem Manajemen Risiko berada pada tingkat risiko yang Sangat Tinggi'], 'catatan' => 'KPMR'],
            28 => ['descriptions' => [1 => '• Telah melaksanakan proses identifikasi, pengukuran, pemantauan, dan pengendalian Risiko kredit <br>• Penerapan dilakukan dengan sangat memadai <br>• Penerapan dilakukan secara konsisten.', 2 => '• Telah melaksanakan proses identifikasi, pengukuran, pemantauan, dan pengendalian Risiko kredit <br>• Penerapan dilakukan dengan memadai <br>• Penerapan dilakukan cukup konsisten.', 3 => '• Telah melaksanakan proses identifikasi, pengukuran, pemantauan, dan pengendalian Risiko kredit <br>• Penerapan dilakukan dengan memadai <br>• Penerapan tidak dilakukan secara konsisten namun tidak signifikan.', 4 => '• Melaksanakan proses tidak secara keseluruhan <br>• Penerapan tidak memadai <br>• Tidak dilakukan secara konsisten sehingga menimbulkan dampak signifikan.', 5 => 'Tidak melaksanakan proses Manajemen Risiko kredit.'], 'catatan' => 'KPMR'],
            29 => ['descriptions' => [1 => '• Telah memiliki sistem informasi Manajemen Risiko <br>• Data lengkap, akurat, kini, dan utuh <br>• Mendukung Direksi dalam pengambilan keputusan <br>• Sangat mendukung SKMR atau PEMR dalam pembuatan laporan.', 2 => '• Telah memiliki sistem informasi Manajemen Risiko <br>• Data cukup lengkap, akurat, kini, dan utuh <br>• Mendukung Direksi dalam pengambilan keputusan <br>• Cukup mendukung SKMR atau PEMR.', 3 => '• Telah memiliki sistem informasi Manajemen Risiko <br>• Data kurang lengkap, akurat, kini, dan utuh <br>• Tidak sepenuhnya mendukung Direksi <br>• Kurang mendukung SKMR atau PEMR.', 4 => '• Telah memiliki sistem informasi Manajemen Risiko <br>• Data tidak lengkap, akurat, kini, dan utuh <br>• Tidak mendukung Direksi <br>• Tidak mendukung SKMR atau PEMR.', 5 => 'Tidak memiliki sistem informasi Manajemen Risiko.'], 'catatan' => 'KPMR'],
            30 => ['descriptions' => [1 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Sangat Rendah', 2 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Rendah', 3 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Sedang', 4 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Tinggi', 5 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Sangat Tinggi'], 'catatan' => 'KPMR'],
            31 => ['descriptions' => [1 => '• SKAI atau PEAI telah melaksanakan audit intern terhadap penerapan Manajemen Risiko kredit <br>• Audit meliputi pelaksanaan kebijakan dan prosedur <br>• Hasil temuan telah ditindaklanjuti.', 2 => '• SKAI atau PEAI telah melaksanakan audit intern <br>• Audit meliputi pelaksanaan kebijakan dan prosedur <br>• Hasil temuan tidak sepenuhnya ditindaklanjuti namun tidak signifikan.', 3 => '• SKAI atau PEAI telah melaksanakan audit intern <br>• Audit meliputi pelaksanaan kebijakan dan prosedur <br>• Hasil temuan tidak sepenuhnya ditindaklanjuti dan menimbulkan dampak signifikan.', 4 => '• SKAI atau PEAI telah melaksanakan audit intern <br>• Audit tidak sesuai cakupan <br>• Hasil temuan tidak ditindaklanjuti.', 5 => 'SKAI atau PEAI tidak melaksanakan audit intern terhadap penerapan Manajemen Risiko kredit.'], 'catatan' => 'KPMR'],
            32 => ['descriptions' => [1 => '• Seluruh jenjang organisasi telah melaksanakan fungsi pengendalian intern <br>• Terdapat kejelasan wewenang dan tanggung jawab <br>• SKMR atau PEMR terpisah dari unit terkait <br>• SKAI atau PEAI terpisah dari unit terkait.', 2 => '• Seluruh jenjang telah melaksanakan pengendalian intern namun tidak sepenuhnya memperhatikan kebijakan dan tidak berdampak signifikan <br>• Terdapat kejelasan wewenang <br>• SKMR dan SKAI terpisah.', 3 => '• Seluruh jenjang telah melaksanakan pengendalian intern namun tidak sepenuhnya dan berdampak signifikan <br>• Terdapat kejelasan wewenang <br>• SKMR dan SKAI terpisah.', 4 => '• Tidak seluruh jenjang melaksanakan fungsi pengendalian intern dan berdampak sangat signifikan <br>• Tidak terdapat kejelasan wewenang <br>• SKMR dan SKAI terpisah.', 5 => '• Seluruh jenjang tidak melaksanakan fungsi pengendalian intern <br>• Tidak terdapat kejelasan wewenang <br>• SKMR atau PEMR tidak terpisah <br>• SKAI atau PEAI tidak terpisah.'], 'catatan' => 'KPMR'],
        ];
    }
}