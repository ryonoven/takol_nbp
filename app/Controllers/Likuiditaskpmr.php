<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_paramprofilrisiko;
use App\Models\M_user;
use App\Models\M_profilrisikocomments;
use App\Models\M_likuiditaskpmr;
use App\Models\M_infobpr;
use App\Models\M_periodesemesteran;
use App\Models\M_profilrisikocommentsread;
use App\Models\M_showprofilresiko;
use App\Models\M_showtks;
use App\Models\M_periodetks;
use Myth\Auth\Config\Services as AuthServices;

class Likuiditaskpmr extends Controller
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
    protected $showprofilresikoModel;
    protected $showtksModel;
    protected $periodetksModel;
    protected $userGroups = [];

    private static ?array $_penilaianConfigCache = null;
    private const SUMMARY_FAKTOR  = 135;
    private const PREV_FAKTOR     = 136;
    private const CHILD_RANGE     = [118, 134];
    private const ALL_RANGE       = [118, 135];

    private const CATEGORY_MAP = [
        118 => [119, 120, 121, 122, 123, 124],
        125 => [126, 127, 128],
        129 => [130, 131],
        132 => [133, 134],
    ];

    private const PERINGKAT_LABEL_MAP = [
        '1' => 'Tingkat Risiko Likuiditas KPMR: Sangat Rendah',
        '2' => 'Tingkat Risiko Likuiditas KPMR: Rendah',
        '3' => 'Tingkat Risiko Likuiditas KPMR: Sedang',
        '4' => 'Tingkat Risiko Likuiditas KPMR: Tinggi',
        '5' => 'Tingkat Risiko Likuiditas KPMR: Sangat Tinggi',
    ];

    private const KODE_MAP = [
        118 => '4310', 119 => '4311', 120 => '4312', 121 => '4313',
        122 => '4314', 123 => '4315', 124 => '4316',
        125 => '4320', 126 => '4321', 127 => '4322', 128 => '4323',
        129 => '4330', 130 => '4331', 131 => '4332',
        132 => '4340', 133 => '4341', 134 => '4342',
        135 => '4350',
    ];

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');

        $this->db = \Config\Database::connect();

        $this->paramprofilrisikoModel = new M_paramprofilrisiko();
        $this->periodeModel           = new M_periodesemesteran();
        $this->userModel              = new M_user();
        $this->komentarModel          = new M_profilrisikocomments();
        $this->nilaiModel             = new M_likuiditaskpmr();
        $this->infobprModel           = new M_infobpr();
        $this->commentReadsModel      = new M_profilrisikocommentsread();
        $this->showprofilresikoModel  = new M_showprofilresiko();
        $this->showtksModel           = new M_showtks();
        $this->periodetksModel        = new M_periodetks();

        helper('url');
        $this->session = service('session');
        $this->auth    = service('authentication');

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
        if (!$this->userId) return;

        $authorize = AuthServices::authorization();
        $groups    = ['pe', 'admin', 'dekom', 'dekom2', 'dekom3', 'dekom4', 'dekom5', 'direksi', 'direksi2'];

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

    private function getNilaiLookup(array $faktorIds): array
    {
        $rows = $this->db->table('likuiditaskpmr')
            ->whereIn('faktor1id', $faktorIds)
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->get()
            ->getResultArray();

        $lookup = [];
        foreach ($rows as $row) {
            $lookup[$row['faktor1id']] = $row;
        }
        return $lookup;
    }

    private function sanitizeTxt(string $text): string
    {
        $text = str_replace(["\r", "\n"], ' ', $text);
        $text = str_replace(
            ['"', '"', '\u2018', '\u2019', '%', '|'],
            ['"', '"', "'",      "'",      ' persen ', ' '],
            $text
        );
        $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    private function formatDateForDisplay(string $date): string
    {
        if (empty($date) || $date === '0000-00-00') return '';
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) return $date;
        $dateObj = \DateTime::createFromFormat('Y-m-d', $date);
        return $dateObj ? $dateObj->format('d/m/Y') : '';
    }

    private function _jsonOrRedirect(bool $success, string $message)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'  => $success ? 'success' : 'error',
                'message' => $message,
            ]);
        }
        return $success
            ? redirect()->back()->with('message', $message)
            : redirect()->back()->with('err', $message);
    }

    public function index()
    {
        if ($redirect = $this->checkAuth())      return $redirect;
        if ($redirect = $this->validatePeriode()) return $redirect;

        if (!$this->userKodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
        }

        $periodeDetail = $this->periodeModel->getPeriodeDetail($this->periodeId);
        $bprData       = $this->infobprModel->getBprByKode($this->userKodebpr);
        $nilaiRows = $this->getNilaiLookup([self::SUMMARY_FAKTOR, self::PREV_FAKTOR]);
        $nilai13   = $nilaiRows[self::SUMMARY_FAKTOR] ?? null;
        $nilai14   = $nilaiRows[self::PREV_FAKTOR]    ?? null;

        $showtks = $this->showtksModel->getByKodebprAndPeriode($this->userKodebpr, $this->periodeId);

        $requiredFaktors = range(self::CHILD_RANGE[0], self::CHILD_RANGE[1]);
        $totalRequired   = count($requiredFaktors);

        $approvalData = $this->nilaiModel
            ->select('
                COUNT(CASE WHEN penilaiankredit IS NOT NULL THEN 1 END) AS filled_count,
                SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) AS approved_count
            ')
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->whereIn('faktor1id', range(self::ALL_RANGE[0], self::ALL_RANGE[1]))
            ->first();

        $totalForApproval = count(range(self::ALL_RANGE[0], self::ALL_RANGE[1]));
        $allFilled   = ((int)$approvalData['filled_count'] >= $totalRequired);
        $allApproved = ($allFilled && (int)$approvalData['approved_count'] === $totalForApproval);
        $canApprove  = $allFilled;

        $user = $this->userModel->find($this->userId);

        $data = [
            'judul' => 'Penilaian Risiko Likuiditas KPMR',
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
            'showtks' => $showtks,
        ];

        return view('templates/v_header', $data)
            . view('templates/v_sidebar')
            . view('templates/v_topbar')
            . view('Laporansemester/Profilrisiko/likuiditaskpmr', $data)
            . view('templates/v_footer');
    }

    public function getFactorsData()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $nilaiLookup = $this->getNilaiLookup(range(self::CHILD_RANGE[0], self::CHILD_RANGE[1]));

        $structure = [
            [
                'id' => 118, 'title' => 'Pilar Pengawasan Direksi dan Dewan Komisaris',
                'type' => 'category', 'faktor_id' => 118,
                'children' => [
                    ['id' => 119, 'title' => 'Apakah Dewan Komisaris telah melakukan persetujuan terhadap kebijakan manajemen risiko likuiditas yang <br> disusun oleh Direksi dan melakukan evaluasi secara berkala?', 'faktor_id' => 119],
                    ['id' => 120, 'title' => 'Apakah Dewan Komisaris telah melakukan evaluasi terhadap pertanggungjawaban Direksi atas pelaksanaan <br> kebijakan manajemen risiko likuiditas secara berkala dan memastikan tindak lanjut hasil evaluasi dimaksud?', 'faktor_id' => 120],
                    ['id' => 121, 'title' => 'Apakah Direksi telah menyusun kebijakan manajemen risiko likuiditas, melaksanakan secara konsisten, <br> dan melakukan pengkinian secara berkala?', 'faktor_id' => 121],
                    ['id' => 122, 'title' => 'Apakah Direksi telah memiliki kemampuan untuk mengambil tindakan yang diperlukan dalam rangka mitigasi <br> risiko likuiditas, dan melakukan komunikasi kebijakan manajemen risiko likuiditas terhadap seluruh jenjang <br> organisasi BPR?', 'faktor_id' => 122],
                    ['id' => 123, 'title' => 'Apakah BPR telah memiliki kecukupan organisasi yang menangani fungsi likuiditas dan fungsi manajemen <br> risiko likuiditas?', 'faktor_id' => 123],
                    ['id' => 124, 'title' => 'Apakah Direksi telah menerapkan kebijakan pengelolaan SDM dalam rangka penerapan Manajemen Risiko <br> likuiditas?', 'faktor_id' => 124],
                ],
            ],
            [
                'id' => 125, 'title' => 'Pilar Kecukupan Kebijakan, Prosedur, dan Limit',
                'type' => 'category', 'faktor_id' => 125,
                'children' => [
                    ['id' => 126, 'title' => 'Apakah BPR telah memiliki kebijakan manajemen risiko likuiditas yang memadai dan disusun dengan <br> mempertimbangkan visi, misi, skala usaha dan kompleksitas bisnis, serta kecukupan SDM?', 'faktor_id' => 126],
                    ['id' => 127, 'title' => 'Apakah BPR memiliki prosedur manajemen risiko likuiditas dan penetapan limit risiko likuiditas, melaksanakannya secara konsisten, serta melakukan evaluasi dan pengkinian secara berkala?', 'faktor_id' => 127],
                    ['id' => 128, 'title' => 'Apakah BPR telah memiliki kebijakan dan prosedur penerbitan produk dan/atau pelaksanaan aktivitas <br> baru yang mencakup identifikasi dan mitigasi risiko likuiditas sesuai dengan ketentuan?', 'faktor_id' => 128],
                ],
            ],
            [
                'id' => 129, 'title' => 'Kecukupan Proses dan Sistem Manajemen Informasi',
                'type' => 'category', 'faktor_id' => 129,
                'children' => [
                    ['id' => 130, 'title' => 'Apakah BPR telah melaksanakan proses manajemen risiko likuiditas yang melekat pada kegiatan <br> usaha BPR?', 'faktor_id' => 130],
                    ['id' => 131, 'title' => 'Apakah BPR telah memiliki sistem informasi manajemen risiko yang mendukung Direksi dalam <br> pengambilan keputusan terkait risiko likuiditas serta telah dilaporkan kepada Direksi secara berkala?', 'faktor_id' => 131],
                ],
            ],
            [
                'id' => 132, 'title' => 'Sistem Pengendalian Internal yang Menyeluruh',
                'type' => 'category', 'faktor_id' => 132,
                'children' => [
                    ['id' => 133, 'title' => 'Apakah SKAI atau PEAI telah melaksanakan audit secara berkala terhadap penerapan <br> manajemen risiko likuiditas, menyampaikan laporan hasil audit intern, dan memastikan <br> tindaklanjut atas temuan pemeriksaan?', 'faktor_id' => 133],
                    ['id' => 134, 'title' => 'Apakah sistem pengendalian intern terhadap risiko likuiditas telah dilaksanakan oleh <br> seluruh jenjang organisasi BPR?', 'faktor_id' => 134],
                ],
            ],
        ];

        foreach ($structure as &$item) {
            $item['nilai'] = $nilaiLookup[$item['faktor_id']] ?? null;
            if (isset($item['children'])) {
                foreach ($item['children'] as &$child) {
                    $child['nilai'] = $nilaiLookup[$child['faktor_id']] ?? null;
                }
            }
        }

        return $this->response->setJSON(['status' => 'success', 'data' => $structure]);
    }

    public function getAllUnreadCounts()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        if (!$this->userKodebpr || !$this->userId || !$this->periodeId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Missing data']);
        }

        $counts = $this->db->query("
            SELECT c.faktor1id,
                   COUNT(*) AS unread_count
            FROM   profilrisikocomments c
            LEFT JOIN profilrisikocommentsread r
                   ON r.comment_id  = c.id
                  AND r.user_id     = ?
                  AND r.kodebpr     = ?
                  AND r.periode_id  = ?
            WHERE  c.subkategori = 'LIKUIDITASKPMR'
              AND  c.kodebpr     = ?
              AND  c.periode_id  = ?
              AND  r.id IS NULL
            GROUP  BY c.faktor1id
        ", [
            $this->userId,
            $this->userKodebpr,
            $this->periodeId,
            $this->userKodebpr,
            $this->periodeId,
        ])->getResultArray();

        $countsMap = [];
        foreach ($counts as $row) {
            $countsMap[$row['faktor1id']] = (int)$row['unread_count'];
        }

        return $this->response->setJSON(['status' => 'success', 'counts' => $countsMap]);
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
            $faktorId, 'LIKUIDITASKPMR',
            $this->userKodebpr, $this->userId, $this->periodeId
        );

        return $this->response->setJSON(['status' => 'success', 'marked_count' => $marked]);
    }

    public function getKomentarByFaktorId($faktorId)
    {
        if (!$this->request->isAJAX() || !is_numeric($faktorId)) {
            return $this->response->setStatusCode(404)->setBody('Not Found');
        }

        return $this->response->setJSON(
            $this->komentarModel->getKomentarByFaktorId(
                'LIKUIDITASKPMR', $faktorId, $this->userKodebpr, $this->periodeId
            )
        );
    }

    public function tambahKomentar()
    {
        $isAjax = $this->request->isAJAX();

        if (!$isAjax && !isset($_POST['tambahKomentar'])) {
            return redirect()->to(base_url('Likuiditaskpmr'));
        }

        if (!$this->validate(['komentar' => 'required'])) {
            if ($isAjax) return $this->response->setJSON(['status' => 'error', 'message' => 'Komentar tidak boleh kosong']);
            return redirect()->back()->with('err', $this->validator->listErrors());
        }

        $faktorId = $this->request->getPost('faktor_id');
        $komentar = $this->request->getPost('komentar');

        if (!$faktorId) {
            if ($isAjax) return $this->response->setJSON(['status' => 'error', 'message' => 'ID tidak ditemukan']);
            return redirect()->back()->with('err', 'ID tidak ditemukan');
        }

        try {
            $this->komentarModel->insert([
                'subkategori' => 'LIKUIDITASKPMR',
                'faktor1id'   => $faktorId,
                'komentar'    => $komentar,
                'user_id'     => $this->userId,
                'kodebpr'     => $this->userKodebpr,
                'periode_id'  => $this->periodeId,
                'created_at'  => date('Y-m-d H:i:s'),
            ]);

            if ($isAjax) {
                return $this->response->setJSON([
                    'status'   => 'success',
                    'message'  => 'Komentar berhasil ditambahkan',
                    'comments' => $this->komentarModel->getKomentarByFaktorId(
                        'LIKUIDITASKPMR', $faktorId, $this->userKodebpr, $this->periodeId
                    ),
                ]);
            }

            return redirect()->to(base_url('Likuiditaskpmr') . '?modal_komentar=' . $faktorId)
                ->with('message', 'Komentar berhasil ditambahkan');
        } catch (\Exception $e) {
            log_message('error', 'Error tambahKomentar: ' . $e->getMessage());
            if ($isAjax) return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menambahkan komentar']);
            return redirect()->back()->with('err', 'Gagal menambahkan komentar');
        }
    }

    public function tambahNilai()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back()->with('error', 'Invalid request');
        }

        if (!$this->validate(['penilaiankredit' => 'required', 'keterangan' => 'required'])) {
            return $this->response->setJSON(['status' => 'error', 'message' => $this->validator->listErrors()]);
        }

        $faktorId = $this->request->getPost('faktor_id');
        if (!$faktorId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'ID tidak ditemukan']);
        }

        try {
            $this->db->table('likuiditaskpmr')->insert([
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
            ]);

            $this->recalculateAfterChange((int)$faktorId);

            $nilai13 = $this->nilaiModel->where([
                'faktor1id' => self::SUMMARY_FAKTOR,
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId,
            ])->first();

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Nilai berhasil ditambahkan',
                'peringkat13' => $nilai13['penilaiankredit'] ?? null,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error tambahNilai: ' . $e->getMessage());
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menambahkan nilai']);
        }
    }

    public function ubah()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back()->with('error', 'Invalid request');
        }

        $faktorId = $this->request->getPost('faktor1id');
        if (!$faktorId || !$this->userKodebpr || !$this->periodeId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak valid']);
        }

        try {
            $this->db->table('likuiditaskpmr')
                ->where('faktor1id', $faktorId)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update([
                    'penilaiankredit' => $this->request->getPost('penilaiankredit'),
                    'penjelasanpenilaian' => $this->request->getPost('penjelasanpenilaian'),
                    'keterangan' => $this->request->getPost('keterangan'),
                    'is_approved' => 0,
                    'accdir2' => 0,
                    'user_id' => $this->userId,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            $this->recalculateAfterChange((int)$faktorId);

            $nilai13 = $this->nilaiModel->where([
                'faktor1id' => self::SUMMARY_FAKTOR,
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId,
            ])->first();

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Data berhasil diubah',
                'peringkat13' => $nilai13['penilaiankredit'] ?? null,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error ubah: ' . $e->getMessage());
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menyimpan data']);
        }
    }

    public function ubahkesimpulan()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back()->with('error', 'Invalid request');
        }

        $faktorId = $this->request->getPost('faktor1id');
        $keterangan = $this->request->getPost('keterangan');

        if (!$faktorId || !$this->userKodebpr || !$this->periodeId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak valid']);
        }

        $existing = $this->nilaiModel
            ->where('faktor1id', $faktorId)
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->first();

        if (!$existing) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak ditemukan']);
        }

        try {
            $this->db->table('likuiditaskpmr')
                ->where('faktor1id', $faktorId)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update([
                    'keterangan' => $keterangan,
                    'penilaiankredit' => $existing['penilaiankredit'],
                    'user_id' => $this->userId,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'is_approved' => 0,
                ]);

            $this->db->table('likuiditaskpmr')
                ->where('faktor1id', self::SUMMARY_FAKTOR)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update(['accdir2' => 0, 'is_approved' => 0]);

            return $this->response->setJSON(['status' => 'success', 'message' => 'Keterangan kesimpulan berhasil diubah']);
        } catch (\Exception $e) {
            log_message('error', 'Error ubah kesimpulan: ' . $e->getMessage());
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal mengubah keterangan']);
        }
    }

    public function ubahPeringkat13Manual()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid request']);
        }

        $penilaiankredit = $this->request->getPost('penilaiankredit');
        if (!$penilaiankredit || !isset(self::PERINGKAT_LABEL_MAP[$penilaiankredit])) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Pilih peringkat yang valid']);
        }

        try {
            $this->nilaiModel
                ->where('faktor1id', self::SUMMARY_FAKTOR)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->set([
                    'penilaiankredit'    => $penilaiankredit,
                    'penjelasanpenilaian'=> self::PERINGKAT_LABEL_MAP[$penilaiankredit],
                    'is_manual'          => 1,
                    'updated_at'         => date('Y-m-d H:i:s'),
                ])->update();

            return $this->response->setJSON(['status' => 'success', 'peringkat' => $penilaiankredit, 'is_manual' => 1]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function resetPeringkat13Auto()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid request']);
        }

        try {
            $rataRata = $this->nilaiModel->hitungRataRata(self::SUMMARY_FAKTOR, $this->userKodebpr, $this->periodeId);
            $rataRata = ($rataRata - floor($rataRata) >= 0.5) ? (int)ceil($rataRata) : (int)floor($rataRata);

            $this->nilaiModel
                ->where('faktor1id', self::SUMMARY_FAKTOR)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->set([
                    'penilaiankredit' => $rataRata,
                    'penjelasanpenilaian' => self::PERINGKAT_LABEL_MAP[(string)$rataRata] ?? 'N/A',
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
            return $this->_jsonOrRedirect(false, 'Kode BPR atau Periode ID tidak valid');
        }

        $allRange = range(self::ALL_RANGE[0], self::ALL_RANGE[1]);
        $checkData = $this->nilaiModel
            ->select('COUNT(CASE WHEN penilaiankredit IS NOT NULL THEN 1 END) AS filled_count')
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->whereIn('faktor1id', $allRange)
            ->first();

        if ((int)$checkData['filled_count'] < count($allRange)) {
            return $this->_jsonOrRedirect(false, 'Semua faktor harus diisi terlebih dahulu');
        }

        try {
            $this->db->table('likuiditaskpmr')
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->whereIn('faktor1id', $allRange)
                ->update([
                    'is_approved' => 1,
                    'approved_by' => $this->userId,
                    'approved_at' => date('Y-m-d H:i:s'),
                ]);

            return $this->_jsonOrRedirect(true, 'Semua data berhasil disetujui.');
        } catch (\Exception $e) {
            log_message('error', 'Error approveSemua: ' . $e->getMessage());
            return $this->_jsonOrRedirect(false, 'Terjadi kesalahan sistem');
        }
    }

    public function unapproveSemua()
    {
        if (!$this->userKodebpr || !$this->periodeId) {
            return $this->_jsonOrRedirect(false, 'Kode BPR atau Periode ID tidak valid');
        }

        try {
            $this->db->table('likuiditaskpmr')
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->whereIn('faktor1id', range(self::ALL_RANGE[0], self::ALL_RANGE[1]))
                ->update([
                    'is_approved' => 0,
                    'approved_by' => $this->userId,
                    'approved_at' => date('Y-m-d H:i:s'),
                ]);

            return $this->_jsonOrRedirect(true, 'Semua data berhasil dibatalkan persetujuannya.');
        } catch (\Exception $e) {
            log_message('error', 'Error unapproveSemua: ' . $e->getMessage());
            return $this->_jsonOrRedirect(false, 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    public function accdir2()
    {
        $faktor1id = $this->request->getPost('faktor1id');
        if (!$faktor1id) return redirect()->back()->with('error', 'Data tidak lengkap');

        $this->db->table('likuiditaskpmr')
            ->where('faktor1id', $faktor1id)
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->update([
                'accdir2' => 1,
                'accdir2_by' => $this->userId,
                'approved_at' => date('Y-m-d H:i:s'),
            ]);

        return redirect()->back()->with('message', 'Data berhasil disetujui');
    }

    public function unapprovedir2()
    {
        if ($this->request->getMethod() !== 'post') {
            return $this->response->setStatusCode(405, 'Method Not Allowed');
        }

        $faktorId = $this->request->getPost('faktor1id');
        $kodebpr = $this->request->getPost('kodebpr');
        $periodeId = $this->request->getPost('periode_id');

        if (!$faktorId || !$kodebpr || !$periodeId) {
            return redirect()->to('/Likuiditaskpmr')->with('error', 'Data tidak lengkap');
        }

        $nilaiFaktor = $this->nilaiModel
            ->where('faktor1id', $faktorId)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if (!$nilaiFaktor) {
            return redirect()->to('/Likuiditaskpmr')->with('error', 'Data tidak ditemukan');
        }

        if ($this->nilaiModel->update($nilaiFaktor['id'], [
            'accdir2'    => 0,
            'accdir2_by' => $this->userId,
            'approved_at'=> date('Y-m-d H:i:s'),
        ])) {
            return redirect()->to('/Likuiditaskpmr')->with('message', 'Persetujuan dibatalkan');
        }

        return redirect()->to('/Likuiditaskpmr')->with('error', 'Gagal memperbarui data');
    }

    public function simpanNilai14()
    {
        if ($redirect = $this->checkAuth()) return $redirect;

        if (!$this->validate(['penilaiankredit' => 'required|in_list[1,2,3,4,5]'])) {
            return redirect()->back()->with('err', 'Pilih tingkat risiko terlebih dahulu');
        }

        if (!$this->userKodebpr || !$this->periodeId) {
            return redirect()->back()->with('error', 'Data user atau periode tidak valid');
        }

        $penilaiankredit = $this->request->getPost('penilaiankredit');
        $penjelasanpenilaian = self::PERINGKAT_LABEL_MAP[$penilaiankredit] ?? 'N/A';
        $user = $this->userModel->find($this->userId);
        $faktorId = self::PREV_FAKTOR;

        try {
            $existing = $this->nilaiModel
                ->where('faktor1id', $faktorId)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->first();

            $commonData = [
                'penilaiankredit' => $penilaiankredit,
                'penjelasanpenilaian' => $penjelasanpenilaian,
                'keterangan' => 'Tingkat Risiko Likuiditas KPMR Posisi Sebelumnya',
                'user_id' => $this->userId,
                'fullname' => $user['fullname'] ?? 'Unknown',
            ];

            if ($existing) {
                $this->nilaiModel
                    ->where('faktor1id', $faktorId)
                    ->where('kodebpr', $this->userKodebpr)
                    ->where('periode_id', $this->periodeId)
                    ->set(array_merge($commonData, ['updated_at' => date('Y-m-d H:i:s')]))
                    ->update();
                $msg = 'Data Tingkat Risiko Likuiditas KPMR berhasil diupdate';
            } else {
                $this->nilaiModel->insert(array_merge($commonData, [
                    'faktor1id' => $faktorId,
                    'kodebpr' => $this->userKodebpr,
                    'periode_id' => $this->periodeId,
                    'is_approved' => 0,
                    'accdir2' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                ]));
                $msg = 'Data Tingkat Risiko Likuiditas KPMR berhasil disimpan';
            }

            return redirect()->to(base_url('Likuiditaskpmr'))->with('message', $msg);
        } catch (\Exception $e) {
            log_message('error', 'Error simpanNilai14: ' . $e->getMessage());
            return redirect()->back()->with('err', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function simpanKesimpulan13()
    {
        if ($redirect = $this->checkAuth()) return $redirect;

        if (!$this->validate(['keterangan' => 'required'])) {
            return redirect()->back()->with('err', 'Keterangan kesimpulan harus diisi');
        }

        if (!$this->userKodebpr || !$this->periodeId) {
            return redirect()->back()->with('error', 'Data user atau periode tidak valid');
        }

        $faktorId = self::SUMMARY_FAKTOR;
        $keterangan = $this->request->getPost('keterangan');
        $user = $this->userModel->find($this->userId);

        try {
            $existing = $this->nilaiModel
                ->where('faktor1id', $faktorId)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->first();

            if ($existing) {
                $this->nilaiModel
                    ->where('faktor1id', $faktorId)
                    ->where('kodebpr', $this->userKodebpr)
                    ->where('periode_id', $this->periodeId)
                    ->set([
                        'keterangan' => $keterangan,
                        'user_id'    => $this->userId,
                        'fullname'   => $user['fullname'] ?? 'Unknown',
                        'updated_at' => date('Y-m-d H:i:s'),
                    ])->update();

                return redirect()->to(base_url('Likuiditaskpmr'))->with('message', 'Kesimpulan berhasil diupdate');
            }

            $rataRata = $this->nilaiModel->hitungRataRata($faktorId, $this->userKodebpr, $this->periodeId);
            $rataRata = ($rataRata - floor($rataRata) >= 0.5) ? (int)ceil($rataRata) : (int)floor($rataRata);

            $this->nilaiModel->insert([
                'faktor1id' => $faktorId,
                'penilaiankredit' => $rataRata,
                'penjelasanpenilaian' => self::PERINGKAT_LABEL_MAP[(string)$rataRata] ?? 'N/A',
                'keterangan' => $keterangan,
                'fullname' => $user['fullname'] ?? 'Unknown',
                'user_id' => $this->userId,
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId,
                'is_approved' => 0,
                'accdir2' => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            return redirect()->to(base_url('Likuiditaskpmr'))->with('message', 'Kesimpulan berhasil disimpan');
        } catch (\Exception $e) {
            log_message('error', 'Error simpan kesimpulan13: ' . $e->getMessage());
            return redirect()->back()->with('err', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    private function recalculateAfterChange(int $faktorId): void
    {
        foreach (self::CATEGORY_MAP as $categoryId => $childrenIds) {
            if (in_array($faktorId, $childrenIds, true)) {
                $this->calculateAndSaveCategoryAverage(
                    $categoryId, $childrenIds, $this->userKodebpr, $this->periodeId
                );
                break;
            }
        }

        $allChildIds = array_merge(...array_values(self::CATEGORY_MAP));
        $categoryIds = array_keys(self::CATEGORY_MAP);

        if (in_array($faktorId, array_merge($allChildIds, $categoryIds), true)) {
            $rataRata = $this->nilaiModel->hitungRataRata(self::SUMMARY_FAKTOR, $this->userKodebpr, $this->periodeId);
            $this->nilaiModel->insertOrUpdateRataRata($rataRata, self::SUMMARY_FAKTOR, $this->userKodebpr, $this->periodeId);
        }

        $this->db->table('likuiditaskpmr')
            ->where('faktor1id', self::SUMMARY_FAKTOR)
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->update(['accdir2' => 0, 'is_approved' => 0]);
    }

    private function calculateAndSaveCategoryAverage(
        int $categoryFaktorId,
        array $childrenFaktorIds,
        string $kodebpr,
        $periodeId
    ): ?int {
        $childRows = $this->nilaiModel
            ->select('penilaiankredit')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->whereIn('faktor1id', $childrenFaktorIds)
            ->where('penilaiankredit IS NOT NULL')
            ->findAll();

        if (empty($childRows)) return null;

        $values         = array_column($childRows, 'penilaiankredit');
        $roundedAverage = (int)round(array_sum($values) / count($values));

        $config         = $this->getPenilaianConfig();
        $autoKeterangan = $config[$categoryFaktorId]['descriptions'][$roundedAverage] ?? '';
        $user           = $this->userModel->find($this->userId);

        $existing = $this->nilaiModel
            ->where('faktor1id', $categoryFaktorId)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
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

        if ($existing) {
            $this->nilaiModel->update($existing['id'], $dataCategory);
        } else {
            $dataCategory['created_at'] = date('Y-m-d H:i:s');
            $dataCategory['is_approved']= 0;
            $dataCategory['accdir2']    = 0;
            $this->nilaiModel->insert($dataCategory);
        }

        return $roundedAverage;
    }

    public function setNullKolom($id)
    {
        if (!$this->auth->check()) {
            return redirect()->to(session('redirect_url') ?? '/login');
        }

        $success = $this->nilaiModel->setNullKolom($id);
        session()->setFlashdata($success ? 'message' : 'err', $success ? 'Data berhasil dihapus' : 'Data gagal dihapus');

        return redirect()->to(base_url('Likuiditaskpmr'));
    }

    private function getMatchingProfilRisikoPeriodeId($periodeTKSId)
    {
        $periodeTKS = $this->periodetksModel->find($periodeTKSId);
        if (!$periodeTKS) return null;

        $list = $this->periodeModel->where([
            'semester' => $periodeTKS['semester'],
            'tahun'    => $periodeTKS['tahun'],
            'kodebpr'  => $this->userKodebpr,
        ])->findAll();

        return $list[0]['id'] ?? null;
    }

    public function exporttxtrisikolikuiditaskpmr()
    {
        if (!$this->auth->check()) {
            return redirect()->to(session('redirect_url') ?? '/login');
        }

        $periodeDetail = $this->periodeModel->find($this->periodeId);
        if (!$periodeDetail) return redirect()->back()->with('error', 'Periode tidak ditemukan');

        ['modalinti' => $modalinti, 'nomorsurat' => $nomorsurat, 'jeniskoreksi' => $jeniskoreksi,
         'jenispelaporan' => $jenispelaporan, 'kategori' => $kategori,
         'tahun' => $tahun, 'semester' => $semester] = $periodeDetail;

        $exportDate = (strtolower($semester) === 'ganjil' || $semester === '1')
            ? "{$tahun}-06-30" : "{$tahun}-12-31";
        $titleDate  = str_replace('-', '', $exportDate);

        $rows        = $this->nilaiModel->getDataByKodebprAndPeriode($this->userKodebpr, $this->periodeId);
        $indexedData = array_column($rows, null, 'faktor1id');

        $infobpr   = $this->infobprModel->getDataByKodebpr($this->userKodebpr);
        $sandibpr  = $infobpr[0]['sandibpr'] ?? '';
        $kodejenis = $infobpr[0]['kodejenis'] ?? '';

        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0402|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";

        foreach (self::KODE_MAP as $faktorId => $kode) {
            $row    = $indexedData[$faktorId] ?? [];
            $output .= "D01|{$kode}|{$this->sanitizeTxt($row['penilaiankredit'] ?? '')}|{$this->sanitizeTxt($row['keterangan'] ?? '')}\r\n";
        }

        $filename = "TKSBPRKS-0402-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }

    public function exporttxtrisikolikuiditaskpmrtks()
    {
        if (!$this->auth->check()) {
            return redirect()->to(session('redirect_url') ?? '/login');
        }

        $periodeTKS = $this->periodetksModel->find($this->periodeId);
        if (!$periodeTKS) return redirect()->back()->with('error', 'Periode TKS tidak ditemukan');

        $periodeProfilRisikoId = $this->getMatchingProfilRisikoPeriodeId($this->periodeId);
        if (!$periodeProfilRisikoId) {
            return redirect()->back()->with('error', 'Tidak ada periode Profil Risiko yang cocok');
        }

        ['modalinti' => $modalinti, 'jenispelaporan' => $jenispelaporan,
         'kategori' => $kategori, 'tahun' => $tahun, 'semester' => $semester] = $periodeTKS;

        $exportDate = (strtolower($semester) === 'ganjil' || $semester === '1')
            ? "{$tahun}-01-31" : "{$tahun}-06-30";
        $titleDate  = str_replace('-', '', $exportDate);

        $rows        = $this->nilaiModel->getDataByKodebprAndPeriode($this->userKodebpr, $periodeProfilRisikoId);
        $indexedData = array_column($rows, null, 'faktor1id');

        $infobpr   = $this->infobprModel->getDataByKodebpr($this->userKodebpr);
        $sandibpr  = $infobpr[0]['sandibpr'] ?? '';
        $kodejenis = $infobpr[0]['kodejenis'] ?? '';

        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0402|{$modalinti}|{$kategori}||\r\n";

        foreach (self::KODE_MAP as $faktorId => $kode) {
            $row    = $indexedData[$faktorId] ?? [];
            $output .= "D01|{$kode}|{$this->sanitizeTxt($row['penilaiankredit'] ?? '')}|{$this->sanitizeTxt($row['keterangan'] ?? '')}\r\n";
        }

        $filename = "TKSBPRKS-0402-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }

    private function getPenilaianConfig(): array
    {
        if (self::$_penilaianConfigCache !== null) {
            return self::$_penilaianConfigCache;
        }

        self::$_penilaianConfigCache = [
            118 => ['descriptions' => [1=>'Parameter Pengawasan Direksi dan Dewan Komisaris berada pada tingkat risiko yang Sangat Rendah',2=>'Parameter Pengawasan Direksi dan Dewan Komisaris berada pada tingkat risiko yang Rendah',3=>'Parameter Pengawasan Direksi dan Dewan Komisaris berada pada tingkat risiko yang Sedang',4=>'Parameter Pengawasan Direksi dan Dewan Komisaris berada pada tingkat risiko yang Tinggi',5=>'Parameter Pengawasan Direksi dan Dewan Komisaris berada pada tingkat risiko yang Sangat Tinggi'],'catatan'=>'Penilaian Risiko Likuiditas KPMR'],
            119 => ['descriptions' => [1=>'• Dewan Komisaris telah memberikan persetujuan dan melakukan evaluasi berkala; relevan dengan kebutuhan penyesuaian kebijakan.',2=>'• Dewan Komisaris telah memberikan persetujuan dan melakukan evaluasi paling sedikit satu kali dalam satu tahun.',3=>'• Dewan Komisaris telah memberikan persetujuan dan melakukan evaluasi; namun tidak secara berkala.',4=>'• Dewan Komisaris telah memberikan persetujuan; namun tidak melakukan evaluasi.',5=>'• Dewan Komisaris tidak memberikan persetujuan maupun melakukan evaluasi.'],'catatan'=>'Penilaian Risiko Likuiditas KPMR'],
            120 => ['descriptions' => [1=>'• Evaluasi dilakukan setiap semester; memastikan tindak lanjut; relevan mendukung perbaikan kinerja.',2=>'• Evaluasi dilakukan setiap semester; memastikan tindak lanjut.',3=>'• Evaluasi dilakukan setiap semester; tidak memastikan tindak lanjut.',4=>'• Evaluasi tidak dilakukan secara berkala; tidak memastikan tindak lanjut.',5=>'Dewan Komisaris tidak melakukan evaluasi sama sekali.'],'catatan'=>'Penilaian Risiko Likuiditas KPMR'],
            121 => ['descriptions' => [1=>'• Direksi menyusun, menjalankan, melakukan pengkinian, dan terbukti memitigasi risiko.',2=>'• Direksi menyusun, menjalankan, dan melakukan pengkinian kebijakan.',3=>'• Direksi menyusun dan menjalankan; namun tidak melakukan pengkinian.',4=>'• Direksi menyusun kebijakan; namun tidak menjalankan dan tidak melakukan pengkinian.',5=>'Direksi tidak menyusun kebijakan Manajemen Risiko likuiditas.'],'catatan'=>'Penilaian Risiko Likuiditas KPMR'],
            122 => ['descriptions' => [1=>'• Direksi mengambil tindakan mitigasi dan mengomunikasikan kebijakan; seluruh jenjang memahami.',2=>'• Direksi mengambil tindakan dan mengomunikasikan; tidak seluruh jenjang memahami namun tidak signifikan.',3=>'• Direksi mengambil tindakan dan mengomunikasikan; tidak seluruh jenjang memahami dan berdampak signifikan.',4=>'• Direksi mengambil tindakan; tidak mengomunikasikan; seluruh jenjang tidak memahami.',5=>'• Direksi tidak mengambil tindakan, tidak mengomunikasikan, seluruh jenjang tidak memahami.'],'catatan'=>'Penilaian Risiko Likuiditas KPMR'],
            123 => ['descriptions' => [1=>'• Organisasi lengkap; tidak ada rangkap jabatan bermasalah; SKMR/PEMR mampu berfungsi.',2=>'• Organisasi tidak lengkap; tidak ada rangkap jabatan bermasalah; SKMR/PEMR mampu berfungsi.',3=>'• Organisasi tidak lengkap; ada rangkap jabatan tidak bermasalah; SKMR/PEMR tidak mampu berfungsi.',4=>'• Organisasi tidak lengkap; ada rangkap jabatan bermasalah; SKMR/PEMR tidak mampu berfungsi.',5=>'• Organisasi tidak lengkap; rangkap jabatan bermasalah; unit tidak melaksanakan tugas; SKMR/PEMR tidak berfungsi.'],'catatan'=>'Penilaian Risiko Likuiditas KPMR'],
            124 => ['descriptions' => [1=>'• Kualifikasi SDM sesuai; upaya peningkatan konsisten; standar kinerja terpenuhi.',2=>'• Ketidaksesuaian kualifikasi tidak signifikan; upaya peningkatan konsisten; standar terpenuhi.',3=>'• Ketidaksesuaian kualifikasi tidak signifikan; upaya peningkatan tidak konsisten; standar terpenuhi.',4=>'• Ketidaksesuaian kualifikasi signifikan; upaya tidak konsisten; standar tidak terpenuhi tapi tidak signifikan.',5=>'• Ketidaksesuaian kualifikasi signifikan; tidak ada upaya peningkatan; standar tidak terpenuhi dan signifikan.'],'catatan'=>'Penilaian Risiko Likuiditas KPMR'],
            125 => ['descriptions' => [1=>'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Sangat Rendah',2=>'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Rendah',3=>'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Sedang',4=>'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Tinggi',5=>'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Sangat Tinggi'],'catatan'=>'Penilaian Risiko Likuiditas KPMR'],
            126 => ['descriptions' => [1=>'• Memiliki kebijakan; substansi sesuai ketentuan; selaras dengan visi, misi, skala usaha.',2=>'• Memiliki kebijakan; ketidaksesuaian substansi tidak signifikan; selaras dengan visi/misi.',3=>'• Memiliki kebijakan; ketidaksesuaian substansi tidak signifikan; ketidakselarasan tidak berdampak signifikan.',4=>'• Memiliki kebijakan; ketidaksesuaian substansi signifikan; ketidakselarasan berdampak signifikan.',5=>'Tidak memiliki kebijakan Manajemen Risiko likuiditas.'],'catatan'=>'Penilaian Risiko Likuiditas KPMR'],
            127 => ['descriptions' => [1=>'• Memiliki prosedur dan limit; dilaksanakan konsisten; dilakukan evaluasi dan pengkinian.',2=>'• Memiliki prosedur dan limit; dilaksanakan konsisten; tidak melakukan evaluasi namun tidak signifikan.',3=>'• Memiliki prosedur dan limit; dilaksanakan konsisten; tidak melakukan evaluasi dan berdampak signifikan.',4=>'• Memiliki prosedur dan limit; tidak dilaksanakan konsisten; tidak melakukan evaluasi.',5=>'Tidak memiliki prosedur dan limit Manajemen Risiko likuiditas.'],'catatan'=>'Penilaian Risiko Likuiditas KPMR'],
            128 => ['descriptions' => [1=>'• Memiliki kebijakan dan prosedur produk baru; diterapkan; sesuai ketentuan.',2=>'• Memiliki kebijakan dan prosedur; diterapkan; ketidaksesuaian tidak signifikan.',3=>'• Memiliki kebijakan dan prosedur; diterapkan; ketidaksesuaian signifikan.',4=>'• Memiliki kebijakan dan prosedur; tidak diterapkan; ketidaksesuaian signifikan.',5=>'Tidak memiliki kebijakan dan prosedur produk/aktivitas baru.'],'catatan'=>'Penilaian Risiko Likuiditas KPMR'],
            129 => ['descriptions' => [1=>'Parameter Kecukupan Proses dan Sistem Informasi Manajemen Risiko berada pada tingkat risiko yang Sangat Rendah',2=>'Parameter Kecukupan Proses dan Sistem Informasi Manajemen Risiko berada pada tingkat risiko yang Rendah',3=>'Parameter Kecukupan Proses dan Sistem Informasi Manajemen Risiko berada pada tingkat risiko yang Sedang',4=>'Parameter Kecukupan Proses dan Sistem Informasi Manajemen Risiko berada pada tingkat risiko yang Tinggi',5=>'Parameter Kecukupan Proses dan Sistem Informasi Manajemen Risiko berada pada tingkat risiko yang Sangat Tinggi'],'catatan'=>'Penilaian Risiko Likuiditas KPMR'],
            130 => ['descriptions' => [1=>'• Proses MR sangat memadai dan konsisten.',2=>'• Proses MR memadai dan cukup konsisten.',3=>'• Proses MR memadai; tidak konsisten namun tidak signifikan.',4=>'• Proses MR tidak secara keseluruhan; tidak memadai; tidak konsisten dan signifikan.',5=>'Tidak melaksanakan proses Manajemen Risiko likuiditas.'],'catatan'=>'Penilaian Risiko Likuiditas KPMR'],
            131 => ['descriptions' => [1=>'• Sistem informasi MR ada; data lengkap/akurat; mendukung Direksi; sangat mendukung laporan SKMR/PEMR.',2=>'• Sistem informasi MR ada; data cukup lengkap; mendukung Direksi; cukup mendukung laporan SKMR/PEMR.',3=>'• Sistem informasi MR ada; data kurang lengkap; tidak sepenuhnya mendukung Direksi; kurang mendukung laporan.',4=>'• Sistem informasi MR ada; data tidak lengkap; tidak mendukung Direksi; tidak mendukung laporan SKMR/PEMR.',5=>'Tidak memiliki sistem informasi Manajemen Risiko likuiditas.'],'catatan'=>'Penilaian Risiko Likuiditas KPMR'],
            132 => ['descriptions' => [1=>'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Sangat Rendah',2=>'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Rendah',3=>'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Sedang',4=>'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Tinggi',5=>'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Sangat Tinggi'],'catatan'=>'Penilaian Risiko Likuiditas KPMR'],
            133 => ['descriptions' => [1=>'• SKAI/PEAI melaksanakan audit; memberikan rekomendasi; seluruh temuan ditindaklanjuti.',2=>'• SKAI/PEAI melaksanakan audit; memberikan rekomendasi; temuan tidak sepenuhnya ditindaklanjuti tapi tidak signifikan.',3=>'• SKAI/PEAI melaksanakan audit; memberikan rekomendasi; temuan tidak sepenuhnya ditindaklanjuti dan signifikan.',4=>'• SKAI/PEAI melaksanakan audit; cakupan tidak sesuai; temuan tidak ditindaklanjuti.',5=>'SKAI atau PEAI tidak melaksanakan audit intern.'],'catatan'=>'Penilaian Risiko Likuiditas KPMR'],
            134 => ['descriptions' => [1=>'• Seluruh jenjang melaksanakan pengendalian intern; kejelasan wewenang; SKMR/PEMR dan SKAI/PEAI terpisah.',2=>'• Seluruh jenjang melaksanakan pengendalian tidak sepenuhnya; tidak berdampak signifikan; pemisahan ada.',3=>'• Seluruh jenjang melaksanakan pengendalian tidak sepenuhnya; berdampak signifikan; pemisahan ada.',4=>'• Tidak seluruh jenjang melaksanakan; tidak ada kejelasan wewenang; pemisahan ada.',5=>'• Seluruh jenjang tidak melaksanakan; tidak ada kejelasan wewenang; SKMR/PEMR dan SKAI/PEAI tidak terpisah.'],'catatan'=>'Penilaian Risiko Likuiditas KPMR'],
            135 => ['descriptions' => [1=>'Tingkat Risiko Likuiditas KPMR: Sangat Rendah',2=>'Tingkat Risiko Likuiditas KPMR: Rendah',3=>'Tingkat Risiko Likuiditas KPMR: Sedang',4=>'Tingkat Risiko Likuiditas KPMR: Tinggi',5=>'Tingkat Risiko Likuiditas KPMR: Sangat Tinggi'],'catatan'=>'Penilaian Risiko Likuiditas KPMR'],
        ];

        return self::$_penilaianConfigCache;
    }
}