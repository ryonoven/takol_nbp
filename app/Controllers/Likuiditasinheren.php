<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_paramprofilrisiko;
use App\Models\M_user;
use App\Models\M_profilrisikocomments;
use App\Models\M_likuiditasinheren;
use App\Models\M_infobpr;
use App\Models\M_periodesemesteran;
use App\Models\M_profilrisikocommentsread;
use App\Models\M_kalkulatorlikuiditas;
use App\Models\M_showprofilresiko;
use App\Models\M_showtks;
use App\Models\M_periodetks;
use Myth\Auth\Config\Services as AuthServices;

class Likuiditasinheren extends Controller
{
    protected $db;
    protected $auth;
    protected $paramprofilrisikoModel;
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
    protected $showprofilresikoModel;
    protected $showtksModel;
    protected $periodetksModel;
    protected $userGroups = [];

    private static $_penilaianConfig = null;
    private static $_strukturFaktor = null;

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->db = \Config\Database::connect();

        $this->paramprofilrisikoModel = new M_paramprofilrisiko();
        $this->periodeModel = new M_periodesemesteran();
        $this->userModel = new M_user();
        $this->komentarModel = new M_profilrisikocomments();
        $this->nilaiModel = new M_likuiditasinheren();
        $this->infobprModel = new M_infobpr();
        $this->commentReadsModel = new M_profilrisikocommentsread();
        $this->kalkulatorModel = new M_kalkulatorlikuiditas();
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

    private function initializeUserGroups()
    {
        if (!$this->userId)
            return;

        $groups = ['pe', 'admin', 'dekom', 'dekom2', 'dekom3', 'dekom4', 'dekom5', 'direksi', 'direksi2'];
        $authorize = AuthServices::authorization();
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
        $showtks = $this->showtksModel->getByKodebprAndPeriode($this->userKodebpr, $this->periodeId);

        $nilaiRows = $this->nilaiModel
            ->whereIn('faktor1id', [115, 116])
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->findAll();

        $nilai13 = null;
        $nilai14 = null;
        foreach ($nilaiRows as $row) {
            if ($row['faktor1id'] == 115)
                $nilai13 = $row;
            elseif ($row['faktor1id'] == 116)
                $nilai14 = $row;
        }

        // Approval check — single query
        $requiredFaktor = array_merge(range(105, 113), [115]);
        $totalRequired = count($requiredFaktor);

        $approvalData = $this->nilaiModel
            ->select("COUNT(CASE WHEN penilaiankredit IS NOT NULL THEN 1 END) as filled_count,
                      SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as approved_count")
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->whereIn('faktor1id', $requiredFaktor)
            ->first();

        $allFilled = ($approvalData['filled_count'] == $totalRequired);
        $allApproved = ($allFilled && $approvalData['approved_count'] == $totalRequired);
        $canApprove = $allFilled;

        // Kalkulator data
        $kalkulatorData = $this->kalkulatorModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->first();

        if (!$kalkulatorData) {
            $kalkulatorData = array_fill_keys([
                'totalaset',
                'asetlikuid',
                'kas',
                'girobanklain',
                'tabunganbanklain',
                'kewajibanlancar',
                'kewajibansegera',
                'depositodpk',
                'tabunganabp',
                'depositoabp',
                'pinjamanditerima',
                'kreditkyd',
                'totaldpk',
                'tabungandpk',
                'penabung25deposan',
                'totalpendanaan',
                'transaksibpr',
                'pendanaannoninti',
                'dpkdiataslps',
                'pinjamananmungkinditarik',
                'rasioasetlikuidtotalaset',
                'rasioasetlikuidkewajiban',
                'rasiokreditterhadapdpk',
                'rasio25deposan',
                'rasiononinti',
                'created_at',
                'updated_at',
            ], '');
        }

        $user = $this->userModel->find($this->userId);

        $data = [
            'judul' => 'Penilaian Risiko Likuiditas Inheren',
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
        ];

        return view('templates/v_header', $data)
            . view('templates/v_sidebar')
            . view('templates/v_topbar')
            . view('Laporansemester/Profilrisiko/likuiditasinheren', $data)
            . view('templates/v_footer');
    }

    public function getFactorsData()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $nilaiData = $this->db->table('likuiditasinheren as r')
            ->select('r.*')
            ->where('r.kodebpr', $this->userKodebpr)
            ->where('r.periode_id', $this->periodeId)
            ->whereIn('r.faktor1id', range(105, 115))
            ->get()
            ->getResultArray();

        $nilaiLookup = [];
        foreach ($nilaiData as $nilai) {
            $nilaiLookup[$nilai['faktor1id']] = $nilai;
        }

        $structure = $this->getFactorStructure();

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

    private function getFactorStructure(): array
    {
        if (self::$_strukturFaktor !== null)
            return self::$_strukturFaktor;

        self::$_strukturFaktor = [
            [
                'id' => 105,
                'title' => 'Komposisi dan konsentrasi aset dan kewajiban',
                'type' => 'category',
                'faktor_id' => 105,
                'children' => [
                    ['id' => 106, 'title' => 'Rasio aset likuid terhadap total aset', 'faktor_id' => 106],
                    ['id' => 107, 'title' => 'Rasio aset likuid terhadap kewajiban lancar', 'faktor_id' => 107],
                    ['id' => 108, 'title' => 'Rasio kredit yang diberikan terhadap total dana pihak ketiga bukan bank (Loan to Deposit Ratio/LDR)', 'faktor_id' => 108],
                    ['id' => 109, 'title' => 'Rasio 25 deposan dan penabung terbesar terhadap total dana pihak ketiga', 'faktor_id' => 109],
                    ['id' => 110, 'title' => 'Rasio Pendanaan non inti terhadap total pendanaan', 'faktor_id' => 110],
                ],
            ],
            [
                'id' => 111,
                'title' => 'Kerentanan pada kebutuhan pendanaan serta akses pada sumber pendanaan',
                'type' => 'category',
                'faktor_id' => 111,
                'children' => [
                    ['id' => 112, 'title' => 'Penilaian kebutuhan pendanaan BPR pada situasi normal maupun krisis, dan kemampuan BPR untuk memenuhi  Kebutuhan pendanaan', 'faktor_id' => 112],
                    ['id' => 113, 'title' => 'Penilaian terhadap seberapa luas atau seberapa besar BPR memiliki komitmen pendanaan yang dapat digunakan jika dibutuhkan.', 'faktor_id' => 113],
                ],
            ],
            [
                'id' => 114,
                'title' => 'Lainnya',
                'type' => 'single',
                'faktor_id' => 114,
            ],
        ];

        return self::$_strukturFaktor;
    }

    private function getPenilaianConfig(): array
    {
        if (self::$_penilaianConfig !== null)
            return self::$_penilaianConfig;

        self::$_penilaianConfig = [
            105 => ['descriptions' => [1 => 'Parameter Komposisi dan konsentrasi aset dan kewajiban berada pada tingkat risiko yang Sangat Rendah', 2 => 'Parameter Komposisi dan konsentrasi aset dan kewajiban berada pada tingkat risiko yang Rendah', 3 => 'Parameter Komposisi dan konsentrasi aset dan kewajiban berada pada tingkat risiko yang Sedang', 4 => 'Parameter Komposisi dan konsentrasi aset dan kewajiban berada pada tingkat risiko yang Tinggi', 5 => 'Parameter Komposisi dan konsentrasi aset dan kewajiban berada pada tingkat risiko yang Sangat Tinggi'], 'catatan' => 'Parameter Komposisi dan konsentrasi aset dan kewajiban'],
            106 => ['descriptions' => [1 => '≥ 15% ', 2 => 'Komposisi aset likuid lebih rendah dari 15% terhadap total aset dan komposisi aset', 3 => 'Komposisi aset likuid lebih rendah dari 15% terhadap total aset dan komposisi aset', 4 => 'Komposisi aset likuid lebih rendah dari 15% terhadap total aset dan komposisi aset', 5 => 'Komposisi aset likuid lebih rendah dari 15% terhadap total'], 'catatan' => 'Rasio aset likuid terhadap total aset'],
            107 => ['descriptions' => [1 => '≥ 20%', 2 => 'likuid lebih rendah dari 20% terhadap kewajiban lancar, namun masih memadai untuk menutup kewajiban jatuh tempo', 3 => 'likuid lebih rendah dari 20% terhadap kewajiban lancar, namun cukup memadai untuk menutup kewajiban jatuh tempo', 4 => 'likuid lebih rendah dari 20% terhadap kewajiban lancar, namun cukup memadai untuk menutup kewajiban jatuh tempo', 5 => 'aset dan Komposisi aset likuid lebih rendah dari 20% terhadap kewajiban lancar, dan tidak memadai untuk menutup kewajiban jatuh tempo; dan/atau • Rasio aset likuid/kewajiban lancar memenuhi kriteria BDPI'], 'catatan' => 'Rasio aset likuid terhadap kewajiban lancar'],
            108 => ['threshold' => '90%', 'descriptions' => [1 => '≤ 90% ', 2 => 'LDR lebih tinggi dari 90% dan kredit berkualitas tidak baik tidak signifikan', 3 => 'LDR lebih tinggi dari 90% namun kredit berkualitas tidak baik kurang signifikan', 4 => 'LDR lebih tinggi dari 90% namun kredit berkualitas tidak baik cukup signifikan', 5 => 'LDR lebih tinggi dari 90% dan kredit berkualitas tidak baik sangat signifikan'], 'catatan' => 'Rasio kredit yang diberikan terhadap total dana pihak ketiga bukan bank (Loan to Deposit Ratio/LDR)'],
            109 => ['threshold' => '25%', 'descriptions' => [1 => '≤ 25% ', 2 => 'Komposisi 25 deposan dan penabung terbesar lebih dari 25%  dan seluruhnya merupakan nasabah lama', 3 => 'Komposisi 25 deposan dan penabung terbesar lebih dari 25% dan sebagian besar merupakan nasabah lama', 4 => 'Komposisi 25 deposan dan penabung terbesar lebih dari 25% dan sebagian besar merupakan nasabah baru', 5 => 'Komposisi 25 deposan dan penabung terbesar lebih dari 25% namun seluruhnya merupakan nasabah baru'], 'catatan' => 'Rasio 25 deposan dan penabung terbesar terhadap total dana pihak ketiga'],
            110 => ['descriptions' => [1 => '≤ 10% ', 2 => 'Rasio pendanaan non inti lebih besar dari 10% namun tidak signifikan terhadap total pendanaan, dan masih dapat dikelola oleh BPR', 3 => 'Rasio pendanaan non inti lebih besar dari 10% dan cukup signifikan terhadap total pendanaan', 4 => 'Rasio pendanaan non inti lebih besar dari 10%, dan signifikan sehingga hampir mendominasi BPR', 5 => 'Rasio pendanaan non inti sangat besar dan mendominasi pendanaan BPR'], 'catatan' => 'Rasio Pendanaan non inti terhadap total pendanaan'],
            111 => ['descriptions' => [1 => 'Parameter Kerentanan pada kebutuhan pendanaan serta akses pada sumber pendanaan berada pada tingkat risiko yang Sangat Rendah', 2 => 'Parameter Kerentanan pada kebutuhan pendanaan serta akses pada sumber pendanaan berada pada tingkat risiko yang Rendah', 3 => 'Parameter Kerentanan pada kebutuhan pendanaan serta akses pada sumber pendanaan berada pada tingkat risiko yang Sedang', 4 => 'Parameter Kerentanan pada kebutuhan pendanaan serta akses pada sumber pendanaan berada pada tingkat risiko yang Tinggi', 5 => 'Parameter Kerentanan pada kebutuhan pendanaan serta akses pada sumber pendanaan berada pada tingkat risiko yang Sangat Tinggi'], 'catatan' => 'Parameter Kerentanan pada kebutuhan pendanaan serta akses pada sumber pendanaan'],
            112 => ['descriptions' => [1 => '• BPR sangat mampu memenuhi kewajiban dan kebutuhan arus kas pada kondisi normal maupun krisis; dan/atau • Arus kas BPR yang berasal dari aset dan kewajiban dapat saling tutup dengan sangat baik (well matched).', 2 => '• BPR mampu memenuhi kewajiban dan kebutuhan arus kas dan pada kondisi normal maupun krisis; dan/atau • Arus kas BPR yang berasal dari aset dan kewajiban dapat saling tutup pada mayoritas skala waktu dengan baik.', 3 => '• BPR cukup mampu memenuhi kewajiban dan kebutuhan arus kas pada kondisi normal maupun krisis (100%) dan/atau • Arus kas BPR yang berasal dari aset dan kewajiban dapat saling tutup dengan cukup baik (100%), terutama pada jangka pendek.', 4 => '• BPR kurang mampu memenuhi kewajiban dan kebutuhan arus kas pada kondisi normal maupun krisis; dan/atau • Selisih (mismatch) arus kas BPR pada berbagai skala waktu yang cukup signifikan.', 5 => '• BPR tidak mampu memenuhi kewajiban dan kebutuhan arus kas pada kondisi normal maupun krisis; dan/atau • Arus kas BPR tidak dapat saling tutup.'], 'catatan' => 'Penilaian kebutuhan pendanaan BPR pada situasi normal maupun krisis, dan kemampuan BPR untuk memenuhi  Kebutuhan pendanaan'],
            113 => ['descriptions' => [1 => 'Akses BPR pada sumber pendanaan sangat memadai dibuktikan dengan reputasi BPR sangat baik, pinjaman bank yang sewaktu-waktu dapat ditarik sangat memadai, dan terdapat komitmen/ dukungan likuiditas dari pemegang saham pengendali/ perusahaan induk/intra grup BPR.', 2 => 'Akses BPR pada sumber pendanaan memadai dibuktikan dengan reputasi BPR baik, pinjaman bank yang sewaktu waktu dapat ditarik memadai, dan terdapat komitmen/ dukungan likuiditas dari pemegang saham pengendali/ perusahaan induk/intra grup BPR.', 3 => 'Akses BPR pada sumber pendanaan cukup memadai dibuktikan dengan reputasi BPR cukup baik, pinjaman bank yang sewaktu-waktu dapat ditarik cukup memadai, dan terdapat komitmen/ dukungan likuiditas dari pemegang saham pengendali/ perusahaan induk/intra grup BPR yang cukup memadai.', 4 => 'Akses BPR pada sumber pendanaan kurang memadai dibuktikan dengan reputasi BPR menurun, pinjaman bank yang sewaktu-waktu dapat ditarik kurang memadai, dan komitmen/ dukungan likuiditas dari pemegang saham pengendali/ perusahaan induk/intra grup BPR yang sangat terbatas.', 5 => 'Akses BPR pada sumber pendanaan tidak memadai dibuktikan dengan reputasi BPR buruk sehingga BPR kesulitan memperoleh pendanaan, tidak terdapat pinjaman bank yang sewaktu-waktu dapat ditarik, dan tidak terdapat komitmen/ dukungan likuiditas dari pemegang saham pengendali/ perusahaan induk/intra grup BPR.'], 'catatan' => 'Penilaian terhadap seberapa luas atau seberapa besar BPR memiliki komitmen pendanaan yang dapat digunakan jika dibutuhkan.'],
            114 => ['descriptions' => [1 => 'Parameter Likuiditas Inheren Lainnya berada pada tingkat risiko yang Sangat Rendah', 2 => 'Parameter Likuiditas Inheren Lainnya berada pada tingkat risiko yang Rendah', 3 => 'Parameter Likuiditas Inheren Lainnya berada pada tingkat risiko yang Sedang', 4 => 'Parameter Likuiditas Inheren Lainnya berada pada tingkat risiko yang Tinggi', 5 => 'Parameter Likuiditas Inheren Lainnya berada pada tingkat risiko yang Sangat Tinggi'], 'catatan' => 'Parameter Likuiditas Inheren Lainnya'],
        ];

        return self::$_penilaianConfig;
    }

    public function ubah()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back()->with('error', 'Invalid request');
        }

        $faktor1id = $this->request->getPost('faktor1id');
        if (!$faktor1id || !$this->userKodebpr || !$this->periodeId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak valid']);
        }

        $existingData = $this->nilaiModel
            ->where('faktor1id', $faktor1id)
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
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

        if ($existingData && !empty($existingData['rasiokredit'])) {
            $data['rasiokredit'] = $existingData['rasiokredit'];
        }

        try {
            $this->db->table('likuiditasinheren')
                ->where('faktor1id', $faktor1id)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update($data);

            $this->recalculateCategoryIfNeeded($faktor1id);

            $nilai13 = $this->nilaiModel->where(['faktor1id' => 115, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first();

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

        $faktor1id = $this->request->getPost('faktor1id');
        $keterangan = $this->request->getPost('keterangan');

        if (!$faktor1id || !$this->userKodebpr || !$this->periodeId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak valid']);
        }

        $existingData = $this->nilaiModel
            ->where('faktor1id', $faktor1id)
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->first();

        if (!$existingData) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak ditemukan']);
        }

        try {
            $this->db->table('likuiditasinheren')
                ->where('faktor1id', $faktor1id)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update([
                    'keterangan' => $keterangan,
                    'penilaiankredit' => $existingData['penilaiankredit'],
                    'user_id' => $this->userId,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'is_approved' => 0,
                ]);

            $this->db->table('likuiditasinheren')
                ->where('faktor1id', 115)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update(['accdir2' => 0, 'is_approved' => 0]);

            return $this->response->setJSON(['status' => 'success', 'message' => 'Keterangan kesimpulan berhasil diubah']);
        } catch (\Exception $e) {
            log_message('error', 'Error ubah kesimpulan: ' . $e->getMessage());
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal mengubah keterangan: ' . $e->getMessage()]);
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

        $existingData = $this->nilaiModel
            ->where('faktor1id', $faktor1id)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
            ->first();

        if (!$existingData)
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak ditemukan']);

        $updateData = [
            'keterangan' => $keterangan,
            'penilaiankredit' => $existingData['penilaiankredit'],
            'penjelasanpenilaian' => $existingData['penjelasanpenilaian'],
            'user_id' => $this->userId,
            'updated_at' => date('Y-m-d H:i:s'),
            'is_approved' => 0,
            'accdir2' => 0,
        ];
        if (!empty($existingData['rasiokredit']))
            $updateData['rasiokredit'] = $existingData['rasiokredit'];

        try {
            $this->db->table('likuiditasinheren')
                ->where('faktor1id', $faktor1id)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
                ->update($updateData);
            return $this->response->setJSON(['status' => 'success', 'message' => 'Keterangan berhasil diubah']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal mengubah keterangan: ' . $e->getMessage()]);
        }
    }

    public function ubahPeringkat13Manual()
    {
        if (!$this->request->isAJAX())
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid request']);

        $penilaiankredit = $this->request->getPost('penilaiankredit');
        if (!$penilaiankredit || !in_array($penilaiankredit, ['1', '2', '3', '4', '5'])) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Pilih peringkat yang valid']);
        }

        $labels = ['1' => 'Sangat Rendah', '2' => 'Rendah', '3' => 'Sedang', '4' => 'Tinggi', '5' => 'Sangat Tinggi'];

        try {
            $this->nilaiModel
                ->where('faktor1id', 115)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
                ->set(['penilaiankredit' => $penilaiankredit, 'penjelasanpenilaian' => 'Tingkat Risiko Likuiditas Inheren: ' . $labels[$penilaiankredit], 'is_manual' => 1, 'updated_at' => date('Y-m-d H:i:s')])
                ->update();
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
            $rataRata = $this->nilaiModel->hitungRataRata(115, $this->userKodebpr, $this->periodeId);
            $rataRata = ($rataRata - floor($rataRata) >= 0.5) ? ceil($rataRata) : floor($rataRata);
            $labels = ['1' => 'Sangat Rendah', '2' => 'Rendah', '3' => 'Sedang', '4' => 'Tinggi', '5' => 'Sangat Tinggi'];

            $this->nilaiModel
                ->where('faktor1id', 115)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
                ->set(['penilaiankredit' => $rataRata, 'penjelasanpenilaian' => 'Tingkat Risiko Likuiditas Inheren: ' . ($labels[$rataRata] ?? ''), 'is_manual' => 0, 'updated_at' => date('Y-m-d H:i:s')])
                ->update();
            return $this->response->setJSON(['status' => 'success', 'peringkat' => $rataRata, 'is_manual' => false]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function tambahNilai()
    {
        if (!$this->request->isAJAX())
            return redirect()->back()->with('error', 'Invalid request');

        $faktorId = $this->request->getPost('faktor_id');
        $penilaian = $this->request->getPost('penilaiankredit');
        $keterangan = $this->request->getPost('keterangan');

        if (!$faktorId)
            return $this->response->setJSON(['status' => 'error', 'message' => 'ID tidak ditemukan']);
        if (!$penilaian)
            return $this->response->setJSON(['status' => 'error', 'message' => 'Pilih tingkat risiko']);
        if (!trim($keterangan))
            return $this->response->setJSON(['status' => 'error', 'message' => 'Keterangan harus diisi']);

        $user = $this->userModel->find($this->userId);

        $existingData = $this->nilaiModel
            ->where('faktor1id', $faktorId)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
            ->first();

        $data = [
            'faktor1id' => $faktorId,
            'penilaiankredit' => $penilaian,
            'penjelasanpenilaian' => $this->request->getPost('penjelasanpenilaian'),
            'keterangan' => $keterangan,
            'user_id' => $this->userId,
            'fullname' => $user['fullname'] ?? 'Unknown',
            'kodebpr' => $this->userKodebpr,
            'periode_id' => $this->periodeId,
            'is_approved' => 0,
            'accdir2' => 0,
        ];

        try {
            if ($existingData) {
                if (!empty($existingData['rasiokredit']))
                    $data['rasiokredit'] = $existingData['rasiokredit'];
                $data['updated_at'] = date('Y-m-d H:i:s');
                $this->db->table('likuiditasinheren')
                    ->where('faktor1id', $faktorId)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
                    ->update($data);
            } else {
                $data['created_at'] = date('Y-m-d H:i:s');
                $this->db->table('likuiditasinheren')->insert($data);
            }

            $this->recalculateCategoryIfNeeded($faktorId);

            $nilai13 = $this->nilaiModel->where(['faktor1id' => 115, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first();

            return $this->response->setJSON(['status' => 'success', 'message' => 'Nilai berhasil ditambahkan', 'peringkat13' => $nilai13['penilaiankredit'] ?? null]);
        } catch (\Exception $e) {
            log_message('error', 'Error tambahNilai: ' . $e->getMessage());
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menambahkan nilai: ' . $e->getMessage()]);
        }
    }

    public function tambahKomentar()
    {
        $isAjax = $this->request->isAJAX();
        if (!$isAjax && !isset($_POST['tambahKomentar'])) {
            return redirect()->to(base_url('Likuiditasinheren'));
        }

        $faktorId = $this->request->getPost('faktor_id');
        $komentar = $this->request->getPost('komentar');

        if (!trim($komentar)) {
            return $isAjax ? $this->response->setJSON(['status' => 'error', 'message' => 'Komentar tidak boleh kosong']) : redirect()->back()->with('err', 'Komentar tidak boleh kosong');
        }
        if (!$faktorId) {
            return $isAjax ? $this->response->setJSON(['status' => 'error', 'message' => 'ID tidak ditemukan']) : redirect()->back()->with('err', 'ID tidak ditemukan');
        }

        try {
            $result = $this->komentarModel->insert([
                'subkategori' => 'LIKUIDITASINHEREN',
                'faktor1id' => $faktorId,
                'komentar' => $komentar,
                'user_id' => $this->userId,
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            if ($result) {
                if ($isAjax) {
                    return $this->response->setJSON([
                        'status' => 'success',
                        'message' => 'Komentar berhasil ditambahkan',
                        'comments' => $this->komentarModel->getKomentarByFaktorId('LIKUIDITASINHEREN', $faktorId, $this->userKodebpr, $this->periodeId),
                    ]);
                }
                return redirect()->to(base_url('Likuiditasinheren') . '?modal_komentar=' . $faktorId)->with('message', 'Komentar berhasil ditambahkan');
            }
            throw new \Exception('Gagal menyimpan komentar');
        } catch (\Exception $e) {
            log_message('error', 'Error tambahKomentar: ' . $e->getMessage());
            return $isAjax ? $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menambahkan komentar']) : redirect()->back()->with('err', 'Gagal menambahkan komentar');
        }
    }

    public function insertRasioToKertasKerja()
    {
        if (!$this->request->isAJAX())
            return redirect()->to(base_url('Likuiditasinheren'));
        if (!$this->userKodebpr || !$this->periodeId) {
            return $this->response->setJSON(['success' => false, 'message' => 'User tidak memiliki kode BPR atau periode yang valid']);
        }

        $fields = [
            'totalaset',
            'kas',
            'girobanklain',
            'tabunganbanklain',
            'kewajibansegera',
            'tabungandpk',
            'depositodpk',
            'tabunganabp',
            'depositoabp',
            'pinjamanditerima',
            'kreditkyd',
            'penabung25deposan',
            'totalpendanaan',
            'dpkdiataslps',
            'pinjamananmungkinditarik'
        ];
        $f = [];
        foreach ($fields as $name)
            $f[$name] = (float) ($this->request->getPost($name) ?? 0);

        $asetlikuid = $f['kas'] + $f['girobanklain'] + $f['tabunganbanklain'];
        $kewajibanlancar = $f['kewajibansegera'] + $f['tabungandpk'] + $f['depositodpk'];
        $totaldpk = $f['tabungandpk'] + $f['depositodpk'];
        $transaksibpr = $f['tabunganabp'] + $f['depositoabp'];
        $pendanaannoninti = $f['dpkdiataslps'] + $transaksibpr + $f['pinjamananmungkinditarik'];

        $rasios = [
            106 => (float) ($this->request->getPost('rasioasetlikuidtotalaset') ?? 0),
            107 => (float) ($this->request->getPost('rasioasetlikuidkewajiban') ?? 0),
            108 => (float) ($this->request->getPost('rasiokreditterhadapdpk') ?? 0),
            109 => (float) ($this->request->getPost('rasio25deposan') ?? 0),
            110 => (float) ($this->request->getPost('rasiononinti') ?? 0),
        ];

        if (!array_filter($rasios)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Tidak ada data rasio. Silakan hitung terlebih dahulu.']);
        }

        $user = $this->userModel->find($this->userId);
        $now = date('Y-m-d H:i:s');

        try {
            $this->db->transStart();

            $kalkulatorData = array_merge($f, [
                'user_id' => $this->userId,
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId,
                'asetlikuid' => $asetlikuid,
                'kewajibanlancar' => $kewajibanlancar,
                'totaldpk' => $totaldpk,
                'transaksibpr' => $transaksibpr,
                'pendanaannoninti' => $pendanaannoninti,
                'rasioasetlikuidtotalaset' => $rasios[106],
                'rasioasetlikuidkewajiban' => $rasios[107],
                'rasiokreditterhadapdpk' => $rasios[108],
                'rasio25deposan' => $rasios[109],
                'rasiononinti' => $rasios[110],
            ]);

            $existingKalkulator = $this->kalkulatorModel->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)->first();
            if ($existingKalkulator) {
                $kalkulatorData['updated_at'] = $now;
                $this->kalkulatorModel->update($existingKalkulator['id'], $kalkulatorData);
            } else {
                $kalkulatorData['created_at'] = $now;
                $this->kalkulatorModel->insert($kalkulatorData);
            }

            $existingRows = $this->nilaiModel
                ->whereIn('faktor1id', array_keys($rasios))
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->findAll();
            $existingMap = [];
            foreach ($existingRows as $row)
                $existingMap[$row['faktor1id']] = $row;

            $successCount = 0;
            foreach ($rasios as $faktorId => $rasioValue) {
                if (isset($existingMap[$faktorId])) {
                    $this->nilaiModel
                        ->where('faktor1id', $faktorId)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
                        ->set(['rasiokredit' => $rasioValue, 'user_id' => $this->userId, 'fullname' => $user['fullname'] ?? 'Unknown', 'updated_at' => $now])
                        ->update();
                } else {
                    $this->nilaiModel->insert([
                        'faktor1id' => $faktorId,
                        'rasiokredit' => $rasioValue,
                        'fullname' => $user['fullname'] ?? 'Unknown',
                        'user_id' => $this->userId,
                        'kodebpr' => $this->userKodebpr,
                        'periode_id' => $this->periodeId,
                        'is_approved' => 0,
                        'accdir2' => 0,
                        'created_at' => $now,
                    ]);
                }
                $successCount++;
            }

            $this->calculateAndSaveCategoryAverage(105, [106, 107, 108, 109, 110], $this->userKodebpr, $this->periodeId);

            $rataRata = $this->nilaiModel->hitungRataRata(115, $this->userKodebpr, $this->periodeId);
            $this->nilaiModel->insertOrUpdateRataRata($rataRata, 115, $this->userKodebpr, $this->periodeId);

            $this->db->transComplete();

            if ($this->db->transStatus() === false)
                throw new \Exception('Transaction failed');

            return $this->response->setJSON(['success' => true, 'message' => "Berhasil menyimpan {$successCount} rasio ke kertas kerja", 'count' => $successCount]);
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Error insertRasioToKertasKerja: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    public function getKomentarByFaktorId($faktorId)
    {
        if (!$this->request->isAJAX() || !is_numeric($faktorId)) {
            return $this->response->setStatusCode(404)->setBody('Not Found');
        }
        return $this->response->setJSON(
            $this->komentarModel->getKomentarByFaktorId('LIKUIDITASINHEREN', $faktorId, $this->userKodebpr, $this->periodeId)
        );
    }

    public function markUserCommentsAsRead()
    {
        if (!$this->request->isAJAX())
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);

        $faktorId = $this->request->getPost('faktor_id');
        if (!$faktorId || !$this->userKodebpr || !$this->userId || !$this->periodeId) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Missing data.']);
        }

        $this->commentReadsModel->markAllAsReadForFactor($faktorId, 'LIKUIDITASINHEREN', $this->userKodebpr, $this->userId, $this->periodeId);
        return $this->response->setJSON(['status' => 'success', 'message' => 'Comments marked as read.']);
    }

    public function getAllUnreadCounts()
    {
        if (!$this->request->isAJAX())
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        if (!$this->userKodebpr || !$this->userId || !$this->periodeId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Missing data']);
        }

        $counts = [];
        $faktorIds = range(105, 115);

        // Call individual counts
        foreach ($faktorIds as $faktorId) {
            $count = $this->commentReadsModel->countUnreadCommentsForUserByFactor(
                $faktorId,
                'LIKUIDITASINHEREN',
                $this->userKodebpr,
                $this->userId,
                $this->periodeId
            );
            if ($count > 0)
                $counts[$faktorId] = $count;
        }

        return $this->response->setJSON(['status' => 'success', 'counts' => $counts]);
    }

    public function approveSemua()
    {
        if (!$this->userKodebpr || !$this->periodeId)
            return redirect()->back()->with('err', 'Kode BPR atau Periode ID tidak valid');

        $requiredFaktor = array_merge(range(105, 113), [115]);
        $totalRequired = count($requiredFaktor);

        $checkData = $this->nilaiModel
            ->select('COUNT(CASE WHEN penilaiankredit IS NOT NULL THEN 1 END) AS filled_count')
            ->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
            ->whereIn('faktor1id', $requiredFaktor)->first();

        if ((int) $checkData['filled_count'] < $totalRequired) {
            return redirect()->back()->with('err', 'Semua faktor wajib harus diisi terlebih dahulu');
        }

        try {
            $this->db->table('likuiditasinheren')
                ->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
                ->whereIn('faktor1id', $requiredFaktor)
                ->update(['is_approved' => 1, 'approved_by' => $this->userId, 'approved_at' => date('Y-m-d H:i:s')]);
            return redirect()->back()->with('message', 'Semua data berhasil disetujui.');
        } catch (\Exception $e) {
            log_message('error', 'Error in approveSemua: ' . $e->getMessage());
            return redirect()->back()->with('err', 'Terjadi kesalahan sistem');
        }
    }

    public function unapproveSemua()
    {
        if (!$this->userKodebpr || !$this->periodeId)
            return redirect()->back()->with('err', 'Kode BPR atau Periode ID tidak valid');

        try {
            $this->nilaiModel->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
                ->update(null, ['is_approved' => 0, 'approved_by' => $this->userId, 'approved_at' => date('Y-m-d H:i:s')]);
            return redirect()->back()->with('message', 'Semua data berhasil dibatalkan persetujuannya.');
        } catch (\Exception $e) {
            log_message('error', 'Error in unapproveSemua: ' . $e->getMessage());
            return redirect()->back()->with('err', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    public function accdir2()
    {
        $faktor1id = $this->request->getPost('faktor1id');
        if (!$faktor1id)
            return redirect()->back()->with('error', 'Data tidak lengkap');
        $this->db->table('likuiditasinheren')
            ->where('faktor1id', $faktor1id)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
            ->update(['accdir2' => 1, 'accdir2_by' => $this->userId, 'approved_at' => date('Y-m-d H:i:s')]);
        return redirect()->back()->with('message', 'Data berhasil disetujui');
    }

    public function unapprovedir2()
    {
        if ($this->request->getMethod() !== 'post')
            return $this->response->setStatusCode(405);

        $faktor1id = $this->request->getPost('faktor1id');
        $kodebpr = $this->request->getPost('kodebpr');
        $periodeId = $this->request->getPost('periode_id');

        if (!$faktor1id || !$kodebpr || !$periodeId)
            return redirect()->to('/Likuiditasinheren')->with('error', 'Data tidak lengkap');

        $nilaiFaktor = $this->nilaiModel->where('faktor1id', $faktor1id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();
        if (!$nilaiFaktor)
            return redirect()->to('/Likuiditasinheren')->with('error', 'Data tidak ditemukan');

        if ($this->nilaiModel->update($nilaiFaktor['id'], ['accdir2' => 0, 'accdir2_by' => $this->userId, 'approved_at' => date('Y-m-d H:i:s')])) {
            return redirect()->to('/Likuiditasinheren')->with('message', 'Persetujuan dibatalkan');
        }
        return redirect()->to('/Likuiditasinheren')->with('error', 'Gagal memperbarui data');
    }

    public function simpanNilai14()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;

        $penilaiankredit = $this->request->getPost('penilaiankredit');
        if (!in_array($penilaiankredit, ['1', '2', '3', '4', '5'])) {
            return redirect()->back()->with('err', 'Pilih tingkat risiko terlebih dahulu');
        }

        $labels = ['1' => 'Sangat Baik (Low)', '2' => 'Baik (Low to Moderate)', '3' => 'Cukup (Moderate)', '4' => 'Kurang Baik (Moderate to High)', '5' => 'Buruk (High)'];
        $user = $this->userModel->find($this->userId);
        $faktorId = 116;
        $now = date('Y-m-d H:i:s');

        $existingData = $this->nilaiModel->where('faktor1id', $faktorId)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)->first();

        try {
            if ($existingData) {
                $this->nilaiModel->where('faktor1id', $faktorId)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
                    ->set(['penilaiankredit' => $penilaiankredit, 'penjelasanpenilaian' => 'Tingkat Risiko Likuiditas Inheren: ' . $labels[$penilaiankredit], 'keterangan' => 'Tingkat Risiko Likuiditas Inheren Posisi Sebelumnya', 'user_id' => $this->userId, 'fullname' => $user['fullname'] ?? 'Unknown', 'updated_at' => $now])
                    ->update();
            } else {
                $this->nilaiModel->insert(['faktor1id' => $faktorId, 'penilaiankredit' => $penilaiankredit, 'penjelasanpenilaian' => 'Tingkat Risiko Likuiditas Inheren: ' . $labels[$penilaiankredit], 'keterangan' => 'Tingkat Risiko Likuid Inheren Posisi Sebelumnya', 'fullname' => $user['fullname'] ?? 'Unknown', 'user_id' => $this->userId, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId, 'is_approved' => 0, 'accdir2' => 0, 'created_at' => $now]);
            }
            return redirect()->to(base_url('Likuiditasinheren'))->with('message', 'Data berhasil disimpan');
        } catch (\Exception $e) {
            log_message('error', 'Error simpanNilai14: ' . $e->getMessage());
            return redirect()->back()->with('err', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function simpanKesimpulan13()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;

        $keterangan = $this->request->getPost('keterangan');
        if (!trim($keterangan))
            return redirect()->back()->with('err', 'Keterangan kesimpulan harus diisi');
        if (!$this->userKodebpr || !$this->periodeId)
            return redirect()->back()->with('error', 'Data user atau periode tidak valid');

        $faktorId = 115;
        $user = $this->userModel->find($this->userId);
        $now = date('Y-m-d H:i:s');

        $existingData = $this->nilaiModel->where('faktor1id', $faktorId)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)->first();

        try {
            if ($existingData) {
                $this->nilaiModel->where('faktor1id', $faktorId)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
                    ->set(['keterangan' => $keterangan, 'user_id' => $this->userId, 'fullname' => $user['fullname'] ?? 'Unknown', 'updated_at' => $now])
                    ->update();
                return redirect()->to(base_url('Likuiditasinheren'))->with('message', 'Kesimpulan berhasil diupdate');
            }

            $rataRata = $this->nilaiModel->hitungRataRata($faktorId, $this->userKodebpr, $this->periodeId);
            $rataRata = ($rataRata - floor($rataRata) >= 0.5) ? ceil($rataRata) : floor($rataRata);
            $labels = ['1' => 'Sangat Rendah', '2' => 'Rendah', '3' => 'Sedang', '4' => 'Tinggi', '5' => 'Sangat Tinggi'];

            $this->nilaiModel->insert([
                'faktor1id' => $faktorId,
                'penilaiankredit' => $rataRata,
                'penjelasanpenilaian' => 'Tingkat Risiko Likuiditas Inheren: ' . ($labels[$rataRata] ?? 'N/A'),
                'keterangan' => $keterangan,
                'fullname' => $user['fullname'] ?? 'Unknown',
                'user_id' => $this->userId,
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId,
                'is_approved' => 0,
                'accdir2' => 0,
                'created_at' => $now,
            ]);
            return redirect()->to(base_url('Likuiditasinheren'))->with('message', 'Kesimpulan berhasil disimpan');
        } catch (\Exception $e) {
            log_message('error', 'Error simpan kesimpulan13: ' . $e->getMessage());
            return redirect()->back()->with('err', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    private function recalculateCategoryIfNeeded(int $faktorId)
    {
        $categoryMapping = [
            105 => [106, 107, 108, 109, 110],
            111 => [112, 113],
        ];

        foreach ($categoryMapping as $categoryId => $childrenIds) {
            if (in_array($faktorId, $childrenIds)) {
                $this->calculateAndSaveCategoryAverage($categoryId, $childrenIds, $this->userKodebpr, $this->periodeId);
                break;
            }
        }

        if (in_array($faktorId, [105, 106, 107, 108, 109, 110, 111, 112, 113, 114])) {
            $rataRata = $this->nilaiModel->hitungRataRata(115, $this->userKodebpr, $this->periodeId);
            $this->nilaiModel->insertOrUpdateRataRata($rataRata, 115, $this->userKodebpr, $this->periodeId);
        }

        $this->db->table('likuiditasinheren')
            ->where('faktor1id', 115)->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)
            ->update(['accdir2' => 0, 'is_approved' => 0]);
    }

    private function calculateAndSaveCategoryAverage($categoryFaktorId, $childrenFaktorIds, $kodebpr, $periodeId)
    {
        $rows = $this->nilaiModel
            ->select('penilaiankredit')
            ->whereIn('faktor1id', $childrenFaktorIds)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->findAll();

        $childValues = array_filter(array_column($rows, 'penilaiankredit'));
        if (empty($childValues))
            return null;

        $roundedAverage = (int) round(array_sum($childValues) / count($childValues));
        $user = $this->userModel->find($this->userId);
        $config = $this->getPenilaianConfig();
        $autoKeterangan = $config[$categoryFaktorId]['descriptions'][$roundedAverage] ?? '';

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
            $this->nilaiModel->insert(array_merge($dataCategory, ['created_at' => date('Y-m-d H:i:s'), 'is_approved' => 0, 'accdir2' => 0]));
        }

        return $roundedAverage;
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

        $currentCount = (int) $this->db->table('likuiditasinheren')
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->whereIn('faktor1id', range(105, 114))
            ->where('penilaiankredit IS NOT NULL')
            ->countAllResults();

        $prevCount = (int) $this->db->table('likuiditasinheren')
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $prevPeriode['id'])
            ->whereIn('faktor1id', range(105, 114))
            ->where('penilaiankredit IS NOT NULL')
            ->countAllResults();

        $prevKalkulatorExists = $this->db->table('kalkulator_likuiditas')
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

        $syncFaktorIds = range(105, 114);

        $prevRows = $this->db->table('likuiditasinheren')
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $prevPeriode['id'])
            ->whereIn('faktor1id', $syncFaktorIds)
            ->get()->getResultArray();

        $existingRows = $this->db->table('likuiditasinheren')
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->whereIn('faktor1id', $syncFaktorIds)
            ->get()->getResultArray();

        $existingLookup = [];
        foreach ($existingRows as $row) {
            $existingLookup[(int) $row['faktor1id']] = $row;
        }

        $prevKalkulator = $this->kalkulatorModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $prevPeriode['id'])
            ->first();

        if (empty($prevRows) && !$prevKalkulator) {
            return $this->response->setJSON([
                'status' => 'no_data',
                'message' => "Tidak ada data pada periode Semester {$prevPeriode['semester']} Tahun {$prevPeriode['tahun']}",
            ]);
        }

        $user = $this->userModel->find($this->userId);
        $now = date('Y-m-d H:i:s');

        $copyFields = ['penilaiankredit', 'penjelasanpenilaian', 'keterangan', 'rasiokredit'];

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
                $updateData = $rowData;
                $updateData['updated_at'] = $now;
                if (empty($rowData['rasiokredit']) && !empty($existing['rasiokredit'])) {
                    $updateData['rasiokredit'] = $existing['rasiokredit'];
                }
                $toUpdate[] = ['id' => $existing['id'], 'data' => $updateData];
            } else {
                $toInsert[] = array_merge($rowData, ['created_at' => $now]);
            }
            $syncedFaktors[] = $fid;
        }

        if (empty($toInsert) && empty($toUpdate) && !$prevKalkulator) {
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
                $this->db->table('likuiditasinheren')->insertBatch($toInsert);
            }

            foreach ($toUpdate as $upd) {
                $this->db->table('likuiditasinheren')
                    ->where('id', $upd['id'])
                    ->update($upd['data']);
            }

            $kalSynced = false;
            if ($prevKalkulator) {
                $existingKal = $this->kalkulatorModel
                    ->where('kodebpr', $this->userKodebpr)
                    ->where('periode_id', $this->periodeId)
                    ->first();

                if (!$existingKal || $forceOverwrite) {
                    $kalCopyFields = [
                        'totalaset',
                        'asetlikuid',
                        'kas',
                        'girobanklain',
                        'tabunganbanklain',
                        'kewajibanlancar',
                        'kewajibansegera',
                        'depositodpk',
                        'tabunganabp',
                        'depositoabp',
                        'pinjamanditerima',
                        'kreditkyd',
                        'totaldpk',
                        'tabungandpk',
                        'penabung25deposan',
                        'totalpendanaan',
                        'transaksibpr',
                        'pendanaannoninti',
                        'dpkdiataslps',
                        'pinjamananmungkinditarik',
                        'rasioasetlikuidtotalaset',
                        'rasioasetlikuidkewajiban',
                        'rasiokreditterhadapdpk',
                        'rasio25deposan',
                        'rasiononinti',
                    ];
                    $kalData = [
                        'user_id' => $this->userId,
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

            $categoryMapping = [
                105 => [106, 107, 108, 109, 110],
                111 => [112, 113],
            ];
            foreach ($categoryMapping as $catId => $childIds) {
                if (array_intersect($syncedFaktors, $childIds)) {
                    $this->calculateAndSaveCategoryAverage(
                        $catId,
                        $childIds,
                        $this->userKodebpr,
                        $this->periodeId
                    );
                }
            }

            if (!empty($syncedFaktors)) {
                $rataRata = $this->nilaiModel->hitungRataRata(115, $this->userKodebpr, $this->periodeId);
                $this->nilaiModel->insertOrUpdateRataRata($rataRata, 115, $this->userKodebpr, $this->periodeId);
            }

            $this->db->table('likuiditasinheren')
                ->where('faktor1id', 115)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update(['accdir2' => 0, 'is_approved' => 0]);

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

            $nilai115 = $this->nilaiModel->where([
                'faktor1id' => 115,
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId,
            ])->first();

            return $this->response->setJSON([
                'status' => 'success',
                'message' => $msg,
                'synced_count' => count($syncedFaktors),
                'skipped_count' => count($skippedFaktors),
                'kal_synced' => $kalSynced,
                'peringkat13' => $nilai115['penilaiankredit'] ?? null,
                'prev_periode' => [
                    'semester' => $prevPeriode['semester'],
                    'tahun' => $prevPeriode['tahun'],
                ],
            ]);

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'syncFromPrevPeriode [LikuiditasInheren] error: ' . $e->getMessage());
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

    public function exporttxtrisikolikuiditas()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');
        $periodeDetail = $this->periodeModel->find($this->periodeId);
        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        $modalinti = $periodeDetail['modalinti'];
        $nomorsurat = $periodeDetail['nomorsurat'];
        $jeniskoreksi = $periodeDetail['jeniskoreksi'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];
        $kategori = $periodeDetail['kategori'];
        $tahun = $periodeDetail['tahun'] ?? date('Y');
        $semester = $periodeDetail['semester'] ?? '';
        $exportDate = (strtolower($semester) === 'ganjil' || $semester === '1') ? "{$tahun}-06-30" : "{$tahun}-12-31";
        $titleDate = str_replace('-', '', $exportDate);

        $data_risikolikuiditas = $this->nilaiModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);

        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $sandibpr = $data_infobpr[0]['sandibpr'] ?? '';
            $kodejenis = $data_infobpr[0]['kodejenis'] ?? '';
        }

        $sanitize = function ($text) {
            $text = str_replace(["\r", "\n"], ' ', $text);
            $text = str_replace(['"', '"', '%', '|'], ['"', '"', "'", "'", ' persen ', ' '], $text);
            $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
            return trim(preg_replace('/\s+/', ' ', $text));
        };

        $kodeMap = [105 => '4210', 106 => '4211', 107 => '4212', 108 => '4213', 109 => '4214', 110 => '4215', 111 => '4220', 112 => '4221', 113 => '4222', 114 => '4299', 115 => '4292'];
        $indexedData = [];
        foreach ($data_risikolikuiditas as $row) {
            if (isset($row['faktor1id']))
                $indexedData[$row['faktor1id']] = $row;
        }

        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0401|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";
        foreach ($kodeMap as $faktorId => $kode) {
            $row = $indexedData[$faktorId] ?? [];
            $rasio = $sanitize($row['rasiokredit'] ?? '');
            $nilai = $sanitize($row['penilaiankredit'] ?? '');
            $ket = $sanitize($row['keterangan'] ?? '');
            $output .= "D01|{$kode}|{$rasio}|{$nilai}|{$ket}\r\n";
        }

        return service('response')
            ->setHeader('Content-Type', 'text/plain')
            ->setHeader('Content-Disposition', 'attachment; filename="TKSBPRKS-0401-' . $jenispelaporan .'-S-' . $titleDate . '-' . $sandibpr . '-01.txt"')
            ->setBody($output);
    }

    public function exporttxtrisikolikuiditastks()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');
        $periodeTKS = $this->periodetksModel->find($periodeId);
        if (!$periodeTKS)
            return redirect()->back()->with('error', 'Periode TKS tidak ditemukan');

        $periodeProfilRisikoId = $this->getMatchingProfilRisikoPeriodeId($periodeId);
        if (!$periodeProfilRisikoId)
            return redirect()->back()->with('error', 'Tidak ada periode Profil Risiko yang cocok');

        $data_risikolikuiditas = $this->nilaiModel->getDataByKodebprAndPeriode($kodebpr, $periodeProfilRisikoId);
        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $sandibpr = $data_infobpr[0]['sandibpr'] ?? '';
            $kodejenis = $data_infobpr[0]['kodejenis'] ?? '';
        }

        $tahun = $periodeTKS['tahun'] ?? date('Y');
        $semester = $periodeTKS['semester'] ?? '';
        $exportDate = (strtolower($semester) === 'ganjil' || $semester === '1') ? "{$tahun}-01-31" : "{$tahun}-06-30";
        $titleDate = str_replace('-', '', $exportDate);
        $jenispelaporan = $periodeTKS['jenispelaporan'];
        $modalinti = $periodeTKS['modalinti'];
        $kategori = $periodeTKS['kategori'];

        $sanitize = function ($text) {
            $text = str_replace(["\r", "\n"], ' ', $text);
            $text = str_replace(['"', '"', '%', '|'], ['"', '"', "'", "'", ' persen ', ' '], $text);
            $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
            return trim(preg_replace('/\s+/', ' ', $text));
        };

        $kodeMap = [105 => '4210', 106 => '4211', 107 => '4212', 108 => '4213', 109 => '4214', 110 => '4215', 111 => '4220', 112 => '4221', 113 => '4222', 114 => '4299', 115 => '4292'];
        $indexedData = [];
        foreach ($data_risikolikuiditas as $row) {
            if (isset($row['faktor1id']))
                $indexedData[$row['faktor1id']] = $row;
        }

        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0401|{$modalinti}|{$kategori}||\r\n";
        foreach ($kodeMap as $faktorId => $kode) {
            $row = $indexedData[$faktorId] ?? [];
            $output .= "D01|{$kode}|" . $sanitize($row['rasiokredit'] ?? '') . "|" . $sanitize($row['penilaiankredit'] ?? '') . "|" . $sanitize($row['keterangan'] ?? '') . "\r\n";
        }

        return service('response')
            ->setHeader('Content-Type', 'text/plain')
            ->setHeader('Content-Disposition', 'attachment; filename="TKSBPRKS-0401-' . $jenispelaporan . '-S-' . $titleDate . '-' . $sandibpr . '-01.txt"')
            ->setBody($output);
    }

    private function getMatchingProfilRisikoPeriodeId($periodeTKSId)
    {
        $periodeTKS = $this->periodetksModel->find($periodeTKSId);
        if (!$periodeTKS)
            return null;

        $list = $this->periodeModel->where(['semester' => $periodeTKS['semester'], 'tahun' => $periodeTKS['tahun'], 'kodebpr' => $this->userKodebpr])->findAll();
        return !empty($list) ? $list[0]['id'] : null;
    }

    public function setNullKolom($id)
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }
        $this->nilaiModel->setNullKolom($id) ? session()->setFlashdata('message', 'Data berhasil dihapus') : session()->setFlashdata('err', 'Data gagal dihapus');
        return redirect()->to(base_url('Likuiditasinheren'));
    }

    public function approve($idNilai = null)
    {
        if ($idNilai === null)
            return redirect()->back()->with('err', 'ID tidak valid');
        $this->db->table('likuiditasinheren')->where('id', $idNilai)->update(['is_approved' => 1, 'approved_by' => $this->userId, 'approved_at' => date('Y-m-d H:i:s')]);
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

    public function cekKomentarBaru()
    {
        $kodebpr = $this->request->getGet('kodebpr');
        $lastVisit = $this->request->getGet('last_visit');
        $results = $this->komentarModel->select('faktor1id, COUNT(*) as jumlah')->where('subkategori', 'LIKUIDITASINHEREN')->where('kodebpr', $kodebpr)->where('periode_id', $this->periodeId)->where('created_at >', $lastVisit)->groupBy('faktor1id')->findAll();
        return $this->response->setJSON($results);
    }

    public function getNilaiByFaktorId($faktorId)
    {
        if (!$this->request->isAJAX() || !is_numeric($faktorId))
            return $this->response->setStatusCode(404)->setBody('Not Found');
        return $this->response->setJSON($this->nilaiModel->getNilaiByFaktorId($faktorId));
    }

    public function recalculateCategoryAverage()
    {
        if (!$this->request->isAJAX())
            return $this->response->setStatusCode(403);
        $faktorId = $this->request->getPost('faktor_id');
        $categoryMapping = [105 => [106, 107, 108, 109, 110], 111 => [112, 113]];
        foreach ($categoryMapping as $categoryId => $childrenIds) {
            if (in_array($faktorId, $childrenIds)) {
                $this->calculateAndSaveCategoryAverage($categoryId, $childrenIds, $this->userKodebpr, $this->periodeId);
                $rataRata = $this->nilaiModel->hitungRataRata(115, $this->userKodebpr, $this->periodeId);
                $this->nilaiModel->insertOrUpdateRataRata($rataRata, 115, $this->userKodebpr, $this->periodeId);
                return $this->response->setJSON(['status' => 'success', 'message' => 'Kategori berhasil di-recalculate']);
            }
        }
        return $this->response->setJSON(['status' => 'error', 'message' => 'Kategori tidak ditemukan']);
    }

    public function getApprovalStatus()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $requiredFaktor = array_merge(range(105, 113), [115]);
        $totalRequired  = count($requiredFaktor);

        $approvalData = $this->nilaiModel
            ->select("COUNT(CASE WHEN penilaiankredit IS NOT NULL THEN 1 END) as filled_count,
                    SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as approved_count")
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->whereIn('faktor1id', $requiredFaktor)
            ->first();

        $filledCount  = (int) ($approvalData['filled_count']  ?? 0);
        $approvedCount = (int) ($approvalData['approved_count'] ?? 0);
        $canApprove   = $filledCount >= $totalRequired;
        $allApproved  = $canApprove && $approvedCount >= $totalRequired;

        return $this->response->setJSON([
            'status'       => 'success',
            'can_approve'  => $canApprove,
            'all_approved' => $allApproved,
            'filled'       => $filledCount,
            'required'     => $totalRequired,
        ]);
    }

    public function getUnreadCommentCountForFactor()
    {
        if (!$this->request->isAJAX())
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        $faktorId = $this->request->getGet('faktor_id');
        if (!$faktorId || !$this->userKodebpr || !$this->userId || !$this->periodeId)
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Missing data.']);
        $count = $this->commentReadsModel->countUnreadCommentsForUserByFactor($faktorId, 'LIKUIDITASINHEREN', $this->userKodebpr, $this->userId, $this->periodeId);
        return $this->response->setJSON(['unread_count' => $count]);
    }
}