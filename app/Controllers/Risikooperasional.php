<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_paramprofilrisiko;
use App\Models\M_user;
use App\Models\M_profilrisikocomments;
use App\Models\M_risikooperasional;
use App\Models\M_infobpr;
use App\Models\M_periodesemesteran;
use App\Models\M_profilrisikocommentsread;
use App\Models\M_showprofilresiko;
use App\Models\M_showtks;
use App\Models\M_periodetks;
use Myth\Auth\Config\Services as AuthServices;

class Risikooperasional extends Controller
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
    private const REQUIRED_FAKTOR = [36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 48];
    private const ALL_FAKTOR = [36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48];

    private const CATEGORY_MAP = [
        36 => [37, 38, 39, 40],
        41 => [42, 43],
    ];

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');

        $this->db = \Config\Database::connect();

        $this->paramprofilrisikoModel = new M_paramprofilrisiko();
        $this->periodeModel = new M_periodesemesteran();
        $this->userModel = new M_user();
        $this->komentarModel = new M_profilrisikocomments();
        $this->nilaiModel = new M_risikooperasional();
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

    private function getNilaiLookup(array $faktorIds): array
    {
        $rows = $this->db->table('risikooperasional')
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
            ['"', '"', "'", "'", ' persen ', ' '],
            $text
        );
        $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
        return trim(preg_replace('/\s+/', ' ', $text));
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

        $nilaiRows = $this->getNilaiLookup([48, 49]);
        $nilai13 = $nilaiRows[48] ?? null;
        $nilai14 = $nilaiRows[49] ?? null;

        $showtks = $this->showtksModel->getByKodebprAndPeriode($this->userKodebpr, $this->periodeId);
        if ($showtks && !empty($showtks['tanggal'])) {
            $showtks['tanggal_display'] = $this->formatDateForDisplay($showtks['tanggal']);
        } else {
            $showtks['tanggal_display'] = '';
        }

        $approvalData = $this->nilaiModel
            ->select('
                COUNT(CASE WHEN penilaiankredit IS NOT NULL THEN 1 END) AS filled_count,
                SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) AS approved_count
            ')
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->whereIn('faktor1id', self::REQUIRED_FAKTOR)
            ->first();

        $totalRequired = count(self::REQUIRED_FAKTOR);
        $allFilled = ((int) $approvalData['filled_count'] === $totalRequired);
        $allApproved = ($allFilled && (int) $approvalData['approved_count'] === $totalRequired);
        $canApprove = $allFilled;

        $user = $this->userModel->find($this->userId);

        $data = [
            'judul' => 'Penilaian Risiko Operasional Inheren',
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
            . view('Laporansemester/Profilrisiko/operasionalinheren', $data)
            . view('templates/v_footer');
    }

    public function getFactorsData()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $allFaktorIds = range(36, 47);
        $nilaiLookup = $this->getNilaiLookup($allFaktorIds);

        $structure = [
            [
                'id' => 36,
                'title' => 'Kompleksitas bisnis dan kelembagaan',
                'type' => 'category',
                'faktor_id' => 36,
                'children' => [
                    ['id' => 37, 'title' => 'Skala usaha dan struktur organisasi', 'faktor_id' => 37],
                    ['id' => 38, 'title' => 'Jaringan kantor, Rentang kendali dan lokasi kantor cabang', 'faktor_id' => 38],
                    ['id' => 39, 'title' => 'Keberagaman produk dan/atau jasa', 'faktor_id' => 39],
                    ['id' => 40, 'title' => 'Tindakan korporasi', 'faktor_id' => 40],
                ],
            ],
            [
                'id' => 41,
                'title' => 'Sumber daya manusia (SDM)',
                'type' => 'category',
                'faktor_id' => 41,
                'children' => [
                    ['id' => 42, 'title' => 'Kecukupan kuantitas dan kualitas SDM', 'faktor_id' => 42],
                    ['id' => 43, 'title' => 'Permasalahan operasional karena faktor manusia (human error)', 'faktor_id' => 43],
                ],
            ],
            ['id' => 44, 'title' => 'Penyelenggaraan teknologi informasi (TI)', 'type' => 'single', 'faktor_id' => 44],
            ['id' => 45, 'title' => 'Pilar penyimpangan (Fraud)', 'type' => 'single', 'faktor_id' => 45],
            ['id' => 46, 'title' => 'Faktor eksternal', 'type' => 'single', 'faktor_id' => 46],
            ['id' => 47, 'title' => 'Lainnya', 'type' => 'single', 'faktor_id' => 47],
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
            return $this->response
                ->setStatusCode(403)
                ->setJSON([
                    'status' => 'error',
                    'message' => 'Forbidden'
                ]);
        }

        if (
            !$this->userKodebpr ||
            !$this->userId ||
            !$this->periodeId
        ) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Missing data'
            ]);
        }

        try {
            $subkategori = 'OPERASIONALINHEREN';

            $counts = $this->db->query("
            SELECT 
                c.faktor1id,
                COUNT(*) AS unread_count
            FROM profilrisiko_comments c
            LEFT JOIN profilrisiko_reads r
                ON r.comment_id = c.id
                AND r.user_id = ?
            WHERE c.subkategori = ?
                AND c.kodebpr = ?
                AND c.periode_id = ?
                AND c.user_id != ?
                AND r.id IS NULL
            GROUP BY c.faktor1id
        ", [
                $this->userId,
                $subkategori,
                $this->userKodebpr,
                $this->periodeId,
                $this->userId
            ])->getResultArray();

            $countsMap = [];

            foreach ($counts as $row) {
                $countsMap[(int) $row['faktor1id']] = (int) $row['unread_count'];
            }

            return $this->response->setJSON([
                'status' => 'success',
                'counts' => $countsMap
            ]);

        } catch (\Throwable $e) {

            log_message(
                'error',
                'Error getAllUnreadCounts: ' . $e->getMessage()
            );

            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getKomentarByFaktorId($faktorId)
    {
        if (!$this->request->isAJAX() || !is_numeric($faktorId)) {
            return $this->response->setStatusCode(404)->setBody('Not Found');
        }

        $komentarList = $this->komentarModel->getKomentarByFaktorId(
            'OPERASIONALINHEREN',
            $faktorId,
            $this->userKodebpr,
            $this->periodeId
        );

        return $this->response->setJSON($komentarList);
    }

    public function tambahKomentar()
    {
        $isAjax = $this->request->isAJAX();

        if (!$isAjax && !isset($_POST['tambahKomentar'])) {
            return redirect()->to(base_url('Risikooperasional'));
        }

        if (!$this->validate(['komentar' => 'required'])) {
            if ($isAjax) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Komentar tidak boleh kosong']);
            }
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
            $this->komentarModel->insert([
                'subkategori' => 'OPERASIONALINHEREN',
                'faktor1id' => $faktorId,
                'komentar' => $komentar,
                'user_id' => $this->userId,
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            if ($isAjax) {
                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Komentar berhasil ditambahkan',
                    'comments' => $this->komentarModel->getKomentarByFaktorId(
                        'OPERASIONALINHEREN',
                        $faktorId,
                        $this->userKodebpr,
                        $this->periodeId
                    ),
                ]);
            }

            return redirect()->to(base_url('Risikooperasional') . '?modal_komentar=' . $faktorId)
                ->with('message', 'Komentar berhasil ditambahkan');

        } catch (\Exception $e) {
            log_message('error', 'Error tambahKomentar: ' . $e->getMessage());
            if ($isAjax) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menambahkan komentar']);
            }
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
            $this->db->table('risikooperasional')->insert($data);
            $this->recalculateAfterChange((int) $faktorId);

            $nilai13 = $this->nilaiModel->where([
                'faktor1id' => 48,
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
            $this->db->table('risikooperasional')
                ->where('faktor1id', $faktorId)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update($data);

            $this->recalculateAfterChange((int) $faktorId);

            $nilai13 = $this->nilaiModel->where([
                'faktor1id' => 48,
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

        $existingData = $this->nilaiModel
            ->where('faktor1id', $faktorId)
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->first();

        if (!$existingData) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak ditemukan']);
        }

        try {
            $this->db->table('risikooperasional')
                ->where('faktor1id', $faktorId)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update([
                    'keterangan' => $keterangan,
                    'penilaiankredit' => $existingData['penilaiankredit'],
                    'user_id' => $this->userId,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'is_approved' => 0,
                ]);

            // Reset approval on summary row
            $this->db->table('risikooperasional')
                ->where('faktor1id', 48)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update(['accdir2' => 0, 'is_approved' => 0]);

            return $this->response->setJSON(['status' => 'success', 'message' => 'Keterangan kesimpulan berhasil diubah']);
        } catch (\Exception $e) {
            log_message('error', 'Error ubah kesimpulan: ' . $e->getMessage());
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal mengubah keterangan']);
        }
    }

    private const PERINGKAT_LABEL_MAP = [
        '1' => 'Tingkat Risiko Operasional Inheren: Sangat Rendah',
        '2' => 'Tingkat Risiko Operasional Inheren: Rendah',
        '3' => 'Tingkat Risiko Operasional Inheren: Sedang',
        '4' => 'Tingkat Risiko Operasional Inheren: Tinggi',
        '5' => 'Tingkat Risiko Operasional Inheren: Sangat Tinggi',
    ];

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
                ->where('faktor1id', 48)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->set([
                    'penilaiankredit' => $penilaiankredit,
                    'penjelasanpenilaian' => self::PERINGKAT_LABEL_MAP[$penilaiankredit],
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
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid request']);
        }

        try {
            $rataRata = $this->nilaiModel->hitungRataRata(48, $this->userKodebpr, $this->periodeId);
            $rataRata = ($rataRata - floor($rataRata) >= 0.5) ? (int) ceil($rataRata) : (int) floor($rataRata);

            $this->nilaiModel
                ->where('faktor1id', 48)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->set([
                    'penilaiankredit' => $rataRata,
                    'penjelasanpenilaian' => self::PERINGKAT_LABEL_MAP[(string) $rataRata] ?? 'N/A',
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
            return $this->_approveJsonOrRedirect(false, 'Kode BPR atau Periode ID tidak valid');
        }

        $checkData = $this->nilaiModel
            ->select('COUNT(CASE WHEN penilaiankredit IS NOT NULL THEN 1 END) AS filled_count')
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->whereIn('faktor1id', self::REQUIRED_FAKTOR)
            ->first();

        if ((int) $checkData['filled_count'] < count(self::REQUIRED_FAKTOR)) {
            return $this->_approveJsonOrRedirect(false, 'Semua faktor wajib harus diisi terlebih dahulu');
        }

        try {
            $this->db->table('risikooperasional')
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->whereIn('faktor1id', self::REQUIRED_FAKTOR)
                ->update([
                    'is_approved' => 1,
                    'approved_by' => $this->userId,
                    'approved_at' => date('Y-m-d H:i:s'),
                ]);

            return $this->_approveJsonOrRedirect(true, 'Semua data berhasil disetujui.');
        } catch (\Exception $e) {
            log_message('error', 'Error approveSemua: ' . $e->getMessage());
            return $this->_approveJsonOrRedirect(false, 'Terjadi kesalahan sistem');
        }
    }

    public function unapproveSemua()
    {
        if (!$this->userKodebpr || !$this->periodeId) {
            return $this->_approveJsonOrRedirect(false, 'Kode BPR atau Periode ID tidak valid');
        }

        try {
            $this->db->table('risikooperasional')
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->whereIn('faktor1id', self::ALL_FAKTOR)
                ->update([
                    'is_approved' => 0,
                    'approved_by' => $this->userId,
                    'approved_at' => date('Y-m-d H:i:s'),
                ]);

            return $this->_approveJsonOrRedirect(true, 'Semua approval berhasil dibatalkan.');
        } catch (\Exception $e) {
            log_message('error', 'Error unapproveSemua: ' . $e->getMessage());
            return $this->_approveJsonOrRedirect(false, 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    public function accdir2()
    {
        $faktor1id = $this->request->getPost('faktor1id');
        if (!$faktor1id)
            return redirect()->back()->with('error', 'Data tidak lengkap');

        $this->db->table('risikooperasional')
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
            return redirect()->to('/Risikooperasional')->with('error', 'Data tidak lengkap');
        }

        $nilaiFaktor = $this->nilaiModel
            ->where('faktor1id', $faktorId)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if (!$nilaiFaktor) {
            return redirect()->to('/Risikooperasional')->with('error', 'Data tidak ditemukan');
        }

        if (
            $this->nilaiModel->update($nilaiFaktor['id'], [
                'accdir2' => 0,
                'accdir2_by' => $this->userId,
                'approved_at' => date('Y-m-d H:i:s'),
            ])
        ) {
            return redirect()->to('/Risikooperasional')->with('message', 'Persetujuan dibatalkan');
        }

        return redirect()->to('/Risikooperasional')->with('error', 'Gagal memperbarui data');
    }

    private function _approveJsonOrRedirect(bool $success, string $message)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status' => $success ? 'success' : 'error',
                'message' => $message,
            ]);
        }
        return $success
            ? redirect()->back()->with('message', $message)
            : redirect()->back()->with('err', $message);
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
        $penjelasanpenilaian = self::PERINGKAT_LABEL_MAP[$penilaiankredit] ?? 'N/A';
        $user = $this->userModel->find($this->userId);
        $faktorId = 49;

        try {
            $existing = $this->nilaiModel
                ->where('faktor1id', $faktorId)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->first();

            $commonData = [
                'penilaiankredit' => $penilaiankredit,
                'penjelasanpenilaian' => $penjelasanpenilaian,
                'keterangan' => 'Tingkat Risiko Operasional Inheren Posisi Sebelumnya',
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
                $msg = 'Data Tingkat Risiko Operasional Inheren berhasil diupdate';
            } else {
                $this->nilaiModel->insert(array_merge($commonData, [
                    'faktor1id' => $faktorId,
                    'kodebpr' => $this->userKodebpr,
                    'periode_id' => $this->periodeId,
                    'is_approved' => 0,
                    'accdir2' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                ]));
                $msg = 'Data Tingkat Risiko Operasional Inheren berhasil disimpan';
            }

            return redirect()->to(base_url('Risikooperasional'))->with('message', $msg);
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

        if (!$this->userKodebpr || !$this->periodeId) {
            return redirect()->back()->with('error', 'Data user atau periode tidak valid');
        }

        $faktorId = 48;
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
                        'user_id' => $this->userId,
                        'fullname' => $user['fullname'] ?? 'Unknown',
                        'updated_at' => date('Y-m-d H:i:s'),
                    ])->update();

                return redirect()->to(base_url('Risikooperasional'))->with('message', 'Kesimpulan berhasil diupdate');
            }

            $rataRata = $this->nilaiModel->hitungRataRata($faktorId, $this->userKodebpr, $this->periodeId);
            $rataRata = ($rataRata - floor($rataRata) >= 0.5) ? (int) ceil($rataRata) : (int) floor($rataRata);

            $this->nilaiModel->insert([
                'faktor1id' => $faktorId,
                'penilaiankredit' => $rataRata,
                'penjelasanpenilaian' => self::PERINGKAT_LABEL_MAP[(string) $rataRata] ?? 'N/A',
                'keterangan' => $keterangan,
                'fullname' => $user['fullname'] ?? 'Unknown',
                'user_id' => $this->userId,
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId,
                'is_approved' => 0,
                'accdir2' => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            return redirect()->to(base_url('Risikooperasional'))->with('message', 'Kesimpulan berhasil disimpan');
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
                    $categoryId,
                    $childrenIds,
                    $this->userKodebpr,
                    $this->periodeId
                );
                break;
            }
        }

        if (in_array($faktorId, array_merge([36, 41, 44, 45, 46, 47], ...array_values(self::CATEGORY_MAP)), true)) {
            $rataRata = $this->nilaiModel->hitungRataRata(48, $this->userKodebpr, $this->periodeId);
            $this->nilaiModel->insertOrUpdateRataRata($rataRata, 48, $this->userKodebpr, $this->periodeId);
        }

        $this->db->table('risikooperasional')
            ->where('faktor1id', 48)
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

        if (empty($childRows))
            return null;

        $values = array_column($childRows, 'penilaiankredit');
        $average = array_sum($values) / count($values);
        $roundedAverage = (int) round($average);

        $config = $this->getPenilaianConfig();
        $autoKeterangan = $config[$categoryFaktorId]['descriptions'][$roundedAverage] ?? '';

        $user = $this->userModel->find($this->userId);
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
            $dataCategory['is_approved'] = 0;
            $dataCategory['accdir2'] = 0;
            $this->nilaiModel->insert($dataCategory);
        }

        return $roundedAverage;
    }

    private function getPenilaianConfig(): array
    {
        if (self::$_penilaianConfigCache !== null) {
            return self::$_penilaianConfigCache;
        }

        self::$_penilaianConfigCache = [
            36 => [
                'descriptions' => [
                    1 => 'Parameter Kompleksitas bisnis dan kelembagaan berada pada tingkat risiko yang Sangat Rendah',
                    2 => 'Parameter Kompleksitas bisnis dan kelembagaan berada pada tingkat risiko yang Rendah',
                    3 => 'Parameter Kompleksitas bisnis dan kelembagaan berada pada tingkat risiko yang Sedang',
                    4 => 'Parameter Kompleksitas bisnis dan kelembagaan berada pada tingkat risiko yang Tinggi',
                    5 => 'Parameter Kompleksitas bisnis dan kelembagaan berada pada tingkat risiko yang Sangat Tinggi',
                ],
                'catatan' => 'Penilaian parameter risiko operasional inheren',
            ],
            37 => [
                'descriptions' => [
                    1 => '• Skala usaha BPR tergolong kecil atau menengah; dan <br>• Struktur organisasi BPR terpenuhi lengkap sesuai ketentuan tata kelola BPR.',
                    2 => '• Skala usaha BPR tergolong besar; dan <br>• Struktur organisasi BPR terpenuhi lengkap sesuai ketentuan tata kelola BPR.',
                    3 => '• Skala usaha BPR tergolong kecil atau menengah; dan <br>• Terdapat ketidaklengkapan struktur organisasi BPR pada fungsi yang tidak signifikan.',
                    4 => '• Skala usaha BPR tergolong besar; dan <br>• Terdapat ketidaklengkapan struktur organisasi BPR pada fungsi yang tidak signifikan.',
                    5 => '• Skala usaha BPR tergolong kecil, menengah, atau besar; dan<br>• Terdapat ketidaklengkapan struktur organisasi BPR pada fungsi yang signifikan.',
                ],
                'catatan' => 'Penilaian parameter risiko operasional inheren',
            ],
            38 => [
                'descriptions' => [
                    1 => 'BPR tidak memiliki jaringan kantor cabang dan/atau kantor kas.',
                    2 => '• BPR memiliki jumlah jaringan kantor cabang paling banyak 25% dari maksimal yang diperkenankan untuk skala KU<br>• Memiliki kantor kas Rentang kendali kecil dan lokasi kantor cabang dapat diakses dengan mudah',
                    3 => '• BPR memiliki jumlah jaringan kantor cabang lebih dari 25% dan paling banyak 50% dari maksimal yang diperkenankan untuk skala KU<br>• Memiliki kantor kas Rentang kendali kecil namun terdapat lokasi kantor cabang yang sulit diakses',
                    4 => '• BPR memiliki jumlah jaringan kantor cabang lebih dari 50% dan paling banyak 75% dari maksimal yang diperkenankan untuk skala KU<br>• Memiliki kantor kas Rentang kendali besar dan lokasi kantor cabang dapat diakses dengan mudah',
                    5 => '• BPR memiliki jumlah jaringan kantor cabang lebih dari 75% dari maksimal yang diperkenankan untuk skala KU<br>• Memiliki kantor kas Rentang kendali besar dan terdapat lokasi kantor cabang yang sulit diakses',
                ],
                'catatan' => 'Penilaian parameter risiko operasional inheren',
            ],
            39 => [
                'descriptions' => [
                    1 => 'BPR memiliki produk/jasa yang termasuk kegiatan usaha utama',
                    2 => '• BPR memiliki produk/jasa yang termasuk kegiatan usaha utama; dan<br>• penukaran valuta asing; dan/atau<br>• layanan kerjasama pihak ketiga yang tidak memerlukan kompetensi tinggi dan tidak melibatkan teknologi',
                    3 => 'BPR memiliki produk/jasa yang termasuk kegiatan usaha utama dan melaksanakan kegiatan usaha layanan kerjasama pihak ketiga yang melibatkan teknologi milik pihak ketiga',
                    4 => 'BPR melaksanakan kegiatan usaha sebagai penyelenggara layanan berbasis teknologi misalnya sebagai issuer/penerbit kartu ATM, atau penyelenggara internet banking',
                    5 => 'BPR melakukan kegiatan usaha yang tidak sesuai dengan kelompok BPRKU atau tidak dilaporkan/memperoleh izin dari OJK atau BI',
                ],
                'catatan' => 'Penilaian parameter risiko operasional inheren',
            ],
            40 => [
                'threshold' => '85%',
                'descriptions' => [
                    1 => '• BPR tidak dalam proses penggabungan, peleburan, dan pengambilalihan;<br>• BPR tidak dalam proses pemindahan kantor pusat BPR; dan<br>• BPR tidak dalam proses penerbitan produk dan/atau pelaksanaan aktivitas baru.',
                    2 => '• BPR tidak dalam proses penggabungan, peleburan, dan pengambilalihan;<br>• Terdapat proses pemindahan kantor pusat BPR; dan/atau<br>• BPR dalam proses pengembangan produk dan/atau aktivitas baru (yang hanya memerlukan pelaporan ke OJK).',
                    3 => '• Terdapat proses pemindahan kantor pusat BPR;<br>• BPR menerbitkan produk dan/atau melaksanakan aktivitas baru (memerlukan persetujuan OJK) bekerja sama dengan pihak ketiga; dan/atau<br>• BPR melaksanakan penggabungan/peleburan/pengambilalihan pada jangka waktu sangat lama sebelum periode penilaian.',
                    4 => '• Terdapat proses pemindahan kantor pusat BPR;<br>• BPR menerbitkan produk dan/atau melaksanakan aktivitas baru yang memerlukan capital expenditure; dan/atau<br>• BPR melaksanakan penggabungan/peleburan/pengambilalihan pada jangka waktu lama sebelum periode penilaian.',
                    5 => '• Terdapat proses pemindahan kantor pusat BPR;<br>• BPR menerbitkan produk dan/atau melaksanakan aktivitas baru yang memerlukan capital expenditure; dan/atau<br>• BPR melaksanakan penggabungan/peleburan/pengambilalihan pada jangka waktu tidak lama sebelum periode penilaian.',
                ],
                'catatan' => 'Penilaian parameter risiko operasional inheren',
            ],
            41 => [
                'descriptions' => [
                    1 => 'Parameter Sumber daya manusia (SDM) berada pada tingkat risiko yang Sangat Rendah',
                    2 => 'Parameter Sumber daya manusia (SDM) berada pada tingkat risiko yang Rendah',
                    3 => 'Parameter Sumber daya manusia (SDM) berada pada tingkat risiko yang Sedang',
                    4 => 'Parameter Sumber daya manusia (SDM) berada pada tingkat risiko yang Tinggi',
                    5 => 'Parameter Sumber daya manusia (SDM) berada pada tingkat risiko yang Sangat Tinggi',
                ],
                'catatan' => 'Penilaian parameter risiko operasional inheren',
            ],
            42 => [
                'descriptions' => [
                    1 => 'Kuantitas dan kualitas SDM BPR sangat memadai.',
                    2 => 'Kuantitas dan kualitas SDM BPR memadai.',
                    3 => 'Kuantitas dan kualitas SDM BPR cukup memadai.',
                    4 => 'Kuantitas dan kualitas SDM BPR kurang memadai.',
                    5 => 'Kuantitas dan kualitas SDM BPR tidak memadai.',
                ],
                'catatan' => 'Penilaian parameter risiko operasional inheren',
            ],
            43 => [
                'threshold' => '5%',
                'descriptions' => [
                    1 => 'Tidak terjadi human error pada BPR.',
                    2 => '• Terjadi human error pada BPR; namun<br>• Tidak berdampak finansial bagi BPR.',
                    3 => '• Terjadi human error pada BPR; dan<br>• mengurangi keuntungan namun tidak menyebabkan BPR membukukan laba negatif.',
                    4 => '• Terjadi human error pada BPR; dan<br>• BPR membukukan laba negatif yang menyebabkan rasio permodalan menurun namun masih sesuai ketentuan KPMM.',
                    5 => '• Terjadi human error pada BPR; dan<br>• BPR membukukan laba negatif yang menyebabkan rasio permodalan menurun di bawah ketentuan KPMM.',
                ],
                'catatan' => 'Penilaian parameter risiko operasional inheren',
            ],
            44 => [
                'descriptions' => [
                    1 => '• TI BPR sesuai dengan ketentuan mengenai SPTI; dan<br>• BPR tidak sedang dalam proses melakukan perubahan mendasar penyelenggaraan TI.',
                    2 => '• TI BPR sebagian besar sesuai dengan ketentuan mengenai SPTI; dan<br>• BPR tidak sedang dalam proses melakukan perubahan mendasar penyelenggaraan TI.',
                    3 => '• TI BPR sebagian besar sesuai dengan ketentuan mengenai SPTI; dan<br>• BPR sedang dalam proses melakukan perubahan mendasar penyelenggaraan TI.',
                    4 => '• TI BPR sebagian besar tidak sesuai dengan ketentuan mengenai SPTI; dan<br>• BPR tidak sedang dalam proses melakukan perubahan mendasar penyelenggaraan TI.',
                    5 => '• TI BPR sebagian besar tidak sesuai dengan ketentuan mengenai SPTI; dan<br>• BPR sedang dalam proses melakukan perubahan mendasar penyelenggaraan TI.',
                ],
                'catatan' => 'Penilaian parameter risiko operasional inheren',
            ],
            45 => [
                'descriptions' => [
                    1 => 'Tidak terdapat indikasi penyimpangan (fraud) pada BPR',
                    2 => '• Terdapat indikasi penyimpangan (fraud) pada BPR dengan frekuensi yang rendah; dan<br>• belum/tidak berdampak finansial',
                    3 => '• Terdapat indikasi penyimpangan (fraud) pada BPR dengan frekuensi tinggi; dan<br>• mengurangi keuntungan namun tidak menyebabkan BPR membukukan laba negatif',
                    4 => '• Terdapat indikasi penyimpangan (fraud) pada BPR yang signifikan; dan<br>• mengurangi keuntungan atau BPR membukukan laba negatif yang menyebabkan rasio permodalan menurun namun masih sesuai ketentuan KPMM',
                    5 => '• Terdapat indikasi penyimpangan (fraud) pada BPR yang sangat signifikan; dan<br>• BPR membukukan laba negatif yang menyebabkan rasio permodalan menurun di bawah ketentuan KPMM',
                ],
                'catatan' => 'Penilaian parameter risiko operasional inheren',
            ],
            46 => [
                'descriptions' => [
                    1 => 'Tidak terdapat kejadian eksternal',
                    2 => '• Terdapat kejadian eksternal; namun<br>• tidak berdampak finansial bagi BPR',
                    3 => '• Terdapat kejadian eksternal; dan<br>• mengurangi keuntungan namun tidak menyebabkan BPR membukukan laba negatif',
                    4 => '• Terdapat kejadian eksternal; dan<br>• BPR membukukan laba negatif yang menyebabkan rasio permodalan menurun namun masih sesuai ketentuan KPMM.',
                    5 => '• Terdapat kejadian eksternal; dan<br>• BPR membukukan laba negatif yang menyebabkan rasio permodalan menurun di bawah ketentuan KPMM.',
                ],
                'catatan' => 'Penilaian parameter risiko operasional inheren',
            ],
            47 => [
                'descriptions' => [
                    1 => 'Parameter Penilaian Risiko Operasional Inheren berada pada tingkat risiko yang Sangat Rendah',
                    2 => 'Parameter Penilaian Risiko Operasional Inheren berada pada tingkat risiko yang Rendah',
                    3 => 'Parameter Penilaian Risiko Operasional Inheren berada pada tingkat risiko yang Sedang',
                    4 => 'Parameter Penilaian Risiko Operasional Inheren berada pada tingkat risiko yang Tinggi',
                    5 => 'Parameter Penilaian Risiko Operasional Inheren berada pada tingkat risiko yang Sangat Tinggi',
                ],
                'catatan' => 'Penilaian parameter risiko operasional inheren',
            ],
        ];

        return self::$_penilaianConfigCache;
    }

    private const KODE_MAP = [
        36 => '2210',
        37 => '2211',
        38 => '2212',
        39 => '2213',
        40 => '2214',
        41 => '2220',
        42 => '2221',
        43 => '2222',
        44 => '2230',
        45 => '2240',
        46 => '2250',
        47 => '2299',
        48 => '2292',
    ];

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

        // COUNT data periode aktif (cek apakah sudah ada data)
        $currentCount = (int) $this->db->table('risikooperasional')
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->whereIn('faktor1id', range(36, 47))
            ->where('penilaiankredit IS NOT NULL')
            ->countAllResults();

        // COUNT data periode sebelumnya (ringan — hanya angka)
        $prevCount = (int) $this->db->table('risikooperasional')
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $prevPeriode['id'])
            ->whereIn('faktor1id', range(36, 47))
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

        $syncFaktorIds = range(36, 47);

        $prevRows = $this->db->table('risikooperasional')
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

        $existingRows = $this->db->table('risikooperasional')
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
                $this->db->table('risikooperasional')->insertBatch($toInsert);
            }

            foreach ($toUpdate as $upd) {
                $this->db->table('risikooperasional')
                    ->where('id', $upd['id'])
                    ->update($upd['data']);
            }

            $catMap = self::CATEGORY_MAP;
            foreach ($catMap as $catId => $childIds) {
                if (array_intersect($syncedFaktors, $childIds)) {
                    $this->calculateAndSaveCategoryAverage(
                        $catId,
                        $childIds,
                        $this->userKodebpr,
                        $this->periodeId
                    );
                }
            }

            $rataRata = $this->nilaiModel->hitungRataRata(48, $this->userKodebpr, $this->periodeId);
            $this->nilaiModel->insertOrUpdateRataRata($rataRata, 48, $this->userKodebpr, $this->periodeId);

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
            log_message('error', 'syncFromPrevPeriode [Operasional] error: ' . $e->getMessage());
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

    public function exporttxtrisikooperasional()
    {
        if (!$this->auth->check()) {
            return redirect()->to(session('redirect_url') ?? '/login');
        }

        $periodeDetail = $this->periodeModel->find($this->periodeId);
        if (!$periodeDetail)
            return redirect()->back()->with('error', 'Periode tidak ditemukan');

        [
            'modalinti' => $modalinti,
            'nomorsurat' => $nomorsurat,
            'jeniskoreksi' => $jeniskoreksi,
            'jenispelaporan' => $jenispelaporan,
            'kategori' => $kategori,
            'tahun' => $tahun,
            'semester' => $semester
        ] = $periodeDetail;

        $exportDate = (strtolower($semester) === 'ganjil' || $semester === '1')
            ? "{$tahun}-06-30"
            : "{$tahun}-12-31";
        $titleDate = str_replace('-', '', $exportDate);

        $indexedData = $this->getNilaiLookup(array_keys(self::KODE_MAP));

        $infobpr = $this->infobprModel->getDataByKodebpr($this->userKodebpr);
        $sandibpr = $infobpr[0]['sandibpr'] ?? '';
        $kodejenis = $infobpr[0]['kodejenis'] ?? '';

        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0201|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";

        foreach (self::KODE_MAP as $faktorId => $kode) {
            $row = $indexedData[$faktorId] ?? [];
            $penilaiankredit = $this->sanitizeTxt($row['penilaiankredit'] ?? '');
            $keterangan = $this->sanitizeTxt($row['keterangan'] ?? '');
            $output .= "D01|{$kode}|{$penilaiankredit}|{$keterangan}\r\n";
        }

        $filename = "TKSBPRKS-0201-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }

    public function exporttxtrisikooperasionaltks()
    {
        if (!$this->auth->check()) {
            return redirect()->to(session('redirect_url') ?? '/login');
        }

        $periodeTKS = $this->periodetksModel->find($this->periodeId);
        if (!$periodeTKS)
            return redirect()->back()->with('error', 'Periode TKS tidak ditemukan');

        $periodeProfilRisikoId = $this->getMatchingProfilRisikoPeriodeId($this->periodeId);
        if (!$periodeProfilRisikoId) {
            return redirect()->back()->with('error', 'Tidak ada periode Profil Risiko yang cocok');
        }

        [
            'modalinti' => $modalinti,
            'jenispelaporan' => $jenispelaporan,
            'kategori' => $kategori,
            'tahun' => $tahun,
            'semester' => $semester
        ] = $periodeTKS;

        $exportDate = (strtolower($semester) === 'ganjil' || $semester === '1')
            ? "{$tahun}-01-31"
            : "{$tahun}-06-30";
        $titleDate = str_replace('-', '', $exportDate);

        $rows = $this->db->table('risikooperasional')
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $periodeProfilRisikoId)
            ->whereIn('faktor1id', array_keys(self::KODE_MAP))
            ->get()->getResultArray();
        $indexedData = array_column($rows, null, 'faktor1id');

        $infobpr = $this->infobprModel->getDataByKodebpr($this->userKodebpr);
        $sandibpr = $infobpr[0]['sandibpr'] ?? '';
        $kodejenis = $infobpr[0]['kodejenis'] ?? '';

        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0201|{$modalinti}|{$kategori}||\r\n";

        foreach (self::KODE_MAP as $faktorId => $kode) {
            $row = $indexedData[$faktorId] ?? [];
            $penilaiankredit = $this->sanitizeTxt($row['penilaiankredit'] ?? '');
            $keterangan = $this->sanitizeTxt($row['keterangan'] ?? '');
            $output .= "D01|{$kode}|{$penilaiankredit}|{$keterangan}\r\n";
        }

        $filename = "TKSBPRKS-0201-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }

    public function exportPDFGabunganOperasional()
    {
        if (!$this->auth->check()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if (!$this->userKodebpr || !$this->periodeId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak lengkap']);
        }

        try {
            $periodeDetail = $this->periodeModel->find($this->periodeId);
            $bprData = $this->infobprModel->where('kodebpr', $this->userKodebpr)->first();

            $dataInheren = $this->db->table('risikooperasional')
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->whereIn('faktor1id', range(36, 49))
                ->orderBy('faktor1id', 'ASC')
                ->get()->getResultArray();

            $dataKPMR = $this->db->table('risikooperasional_kpmr')
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->whereIn('faktor1id', range(51, 71))
                ->orderBy('faktor1id', 'ASC')
                ->get()->getResultArray();

            return $this->response->setJSON([
                'status' => 'success',
                'data' => [
                    'periode' => ['semester' => $periodeDetail['semester'] ?? '', 'tahun' => $periodeDetail['tahun'] ?? ''],
                    'bpr' => ['namabpr' => $bprData['namabpr'] ?? '', 'kodebpr' => $this->userKodebpr],
                    'inheren' => $this->buildOperasionalInherenStructure($dataInheren),
                    'kpmr' => $this->buildOperasionalKPMRStructure($dataKPMR),
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error exportPDFGabunganOperasional: ' . $e->getMessage());
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal mengambil data']);
        }
    }

    private function buildOperasionalInherenStructure(array $dataArray): array
    {
        $lookup = array_column($dataArray, null, 'faktor1id');

        return [
            'nilai' => [
                'kompleksitas' => [
                    'kategori' => $lookup[36] ?? null,
                    'children' => array_filter([$lookup[37] ?? null, $lookup[38] ?? null, $lookup[39] ?? null, $lookup[40] ?? null]),
                ],
                'sdm' => [
                    'kategori' => $lookup[41] ?? null,
                    'children' => array_filter([$lookup[42] ?? null, $lookup[43] ?? null]),
                ],
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
                'pengawasan' => ['kategori' => $lookup[51] ?? null, 'children' => array_filter([$lookup[52] ?? null, $lookup[53] ?? null, $lookup[54] ?? null, $lookup[55] ?? null, $lookup[56] ?? null, $lookup[57] ?? null])],
                'kebijakan' => ['kategori' => $lookup[58] ?? null, 'children' => array_filter([$lookup[59] ?? null, $lookup[60] ?? null, $lookup[61] ?? null])],
                'proses' => ['kategori' => $lookup[62] ?? null, 'children' => array_filter([$lookup[63] ?? null, $lookup[64] ?? null, $lookup[65] ?? null, $lookup[66] ?? null])],
                'pengendalian' => ['kategori' => $lookup[67] ?? null, 'children' => array_filter([$lookup[68] ?? null, $lookup[69] ?? null])],
            ],
            'nilai33' => $lookup[70] ?? null,
            'nilai34' => $lookup[71] ?? null,
        ];
    }

    public function setNullKolom($id)
    {
        if (!$this->auth->check()) {
            return redirect()->to(session('redirect_url') ?? '/login');
        }

        $success = $this->nilaiModel->setNullKolom($id);
        session()->setFlashdata($success ? 'message' : 'err', $success ? 'Data berhasil dihapus' : 'Data gagal dihapus');

        return redirect()->to(base_url('Risikooperasional'));
    }

    private function getMatchingProfilRisikoPeriodeId($periodeTKSId)
    {
        $periodeTKS = $this->periodetksModel->find($periodeTKSId);
        if (!$periodeTKS)
            return null;

        $list = $this->periodeModel->where([
            'semester' => $periodeTKS['semester'],
            'tahun' => $periodeTKS['tahun'],
            'kodebpr' => $this->userKodebpr,
        ])->findAll();

        return $list[0]['id'] ?? null;
    }
}