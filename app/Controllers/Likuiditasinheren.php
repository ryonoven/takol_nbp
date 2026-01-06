<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_paramprofilrisiko;
use App\Models\M_user;
use App\Models\M_profilrisikocomments;
use App\Models\M_likuiditasinheren;
use App\Models\M_infobpr;
use App\Models\M_periodeprofilresiko;
use App\Models\M_profilrisikocommentsread;
use App\Models\M_kalkulatorlikuiditas;
use App\Models\M_showprofilresiko;
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
    protected $userGroups = [];

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');

        $this->db = \Config\Database::connect();

        // Initialize models
        $this->paramprofilrisikoModel = new M_paramprofilrisiko();
        $this->periodeModel = new M_periodeprofilresiko();
        $this->userModel = new M_user();
        $this->komentarModel = new M_profilrisikocomments();
        $this->nilaiModel = new M_likuiditasinheren();
        $this->infobprModel = new M_infobpr();
        $this->commentReadsModel = new M_profilrisikocommentsread();
        $this->kalkulatorModel = new M_kalkulatorlikuiditas();
        $this->showprofilresikoModel = new M_showprofilresiko();

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

        $nilai13 = $this->nilaiModel->where([
            'faktor1id' => 115,
            'kodebpr' => $this->userKodebpr,
            'periode_id' => $this->periodeId
        ])->first();

        $nilai14 = $this->nilaiModel->where([
            'faktor1id' => 116,
            'kodebpr' => $this->userKodebpr,
            'periode_id' => $this->periodeId
        ])->first();

        $requiredFaktor = array_merge(range(105, 113), [115]);
        $totalRequired = count($requiredFaktor);

        $approvalData = $this->nilaiModel
            ->select('
        COUNT(CASE WHEN penilaiankredit IS NOT NULL THEN 1 END) as filled_count,
        SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as approved_count
    ')
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->whereIn('faktor1id', $requiredFaktor)
            ->first();

        $allFilled = ($approvalData['filled_count'] == $totalRequired);
        $allApproved = ($allFilled && $approvalData['approved_count'] == $totalRequired);
        $canApprove = $allFilled;

        $kalkulatorData = $this->kalkulatorModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->first();

        if (!$kalkulatorData) {
            $kalkulatorData = [
                'totalaset' => '',
                'asetlikuid' => '',
                'kas' => '',
                'girobanklain' => '',
                'tabunganbanklain' => '',
                'kewajibanlancar' => '',
                'kewajibansegera' => '',
                'depositodpk' => '',
                'tabunganabp' => '',
                'depositoabp' => 0,
                'pinjamanditerima' => 0,
                'kreditkyd' => 0,
                'totaldpk' => 0,
                'tabungandpk' => 0,
                'penabung25deposan' => 0,
                'totalpendanaan' => 0,
                'transaksibpr' => '',
                'pendanaannoninti' => '',
                'dpkdiataslps' => '',
                'pinjamananmungkinditarik' => '',
                'rasioasetlikuidtotalaset' => '',
                'rasioasetlikuidkewajiban' => '',
                'rasiokreditterhadapdpk' => 0,
                'rasio25deposan' => 0,
                'rasiononinti' => 0,
                'created_at' => 0,
                'updated_at' => 0,
            ];
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
        ];

        $data['kalkulatorData'] = $kalkulatorData;

        return view('templates/v_header', $data)
            . view('templates/v_sidebar')
            . view('templates/v_topbar')
            . view('risikolikuiditas/index', $data)
            . view('templates/v_footer');
    }

    private function checkCanApprove($nilaiLookup)
    {
        $requiredFaktorIds = range(105, 114);

        // cek apakah semua faktor 36-47 ada datanya
        foreach ($requiredFaktorIds as $faktorId) {
            if (empty($nilaiLookup[$faktorId])) {
                return false;
            }
        }

        return true;
    }

    private function checkAccdekomApproved($nilaiLookup)
    {
        for ($faktorId = 105; $faktorId <= 114; $faktorId++) {
            if (!isset($nilaiLookup[$faktorId]) || $nilaiLookup[$faktorId]['is_approved'] != 1) {
                return false;
            }
        }
        return true;
    }

    private function checkAllApproved($nilaiLookup)
    {
        $requiredFaktorIds = range(105, 115);
        foreach ($requiredFaktorIds as $faktorId) {
            if (!isset($nilaiLookup[$faktorId]) || $nilaiLookup[$faktorId]['is_approved'] != 1) {
                return false;
            }
        }
        return true;
    }

    private function buildFactorsWithDetails($faktorData, $nilaiLookup)
    {
        $factorsWithDetails = [];

        foreach ($faktorData as $faktorItem) {
            $faktorId = $faktorItem['id'];
            $associatedNilai = $nilaiLookup[$faktorId] ?? null;

            $rataRata = $this->nilaiModel->hitungRataRata($faktorId, $this->userKodebpr, $this->periodeId);

            $this->nilaiModel->insertOrUpdateRataRata($rataRata, $faktorId, $this->userKodebpr, $this->periodeId);

            $factorsWithDetails[] = [
                'id' => $faktorItem['id'],
                'risiko' => $faktorItem['risiko'],
                'inheren_kpmr' => $faktorItem['inheren_kpmr'],
                'parameterpenilaian' => $faktorItem['parameterpenilaian'],
                'penilaiankredit' => $associatedNilai['penilaiankredit'] ?? null,
                'penjelasanpenilaian' => $associatedNilai['penjelasanpenilaian'] ?? null,
                'nfaktor' => $rataRata,
                'rasiokredit' => $associatedNilai['rasiokredit'] ?? null,
                'keterangan' => $associatedNilai['keterangan'] ?? null,
                'kodebpr' => $this->userKodebpr,
                'is_approved' => $associatedNilai['is_approved'] ?? 0,
                'approved_at' => $associatedNilai['approved_at'] ?? 0,
                'positifstruktur' => $associatedNilai['positifstruktur'] ?? null,
                'negatifstruktur' => $associatedNilai['negatifstruktur'] ?? null,
                'positifproses' => $associatedNilai['positifproses'] ?? null,
                'negatifproses' => $associatedNilai['negatifproses'] ?? null,
                'positifhasil' => $associatedNilai['positifhasil'] ?? null,
                'negatifhasil' => $associatedNilai['negatifhasil'] ?? null,
                'periode_id' => $associatedNilai['periode_id'] ?? null,
                'accdir2' => $associatedNilai['accdir2'] ?? null,
                'accdir2_by' => $associatedNilai['accdir2_by'] ?? null,
            ];
        }

        return $factorsWithDetails;
    }

    public function getFactorsData()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $nilaiData = $this->db->table('likuiditasinheren as r')
            ->select('r.*, u.fullname')
            ->join('users as u', 'u.id = r.user_id', 'left')
            ->where('r.kodebpr', $this->userKodebpr)
            ->where('r.periode_id', $this->periodeId)
            ->whereIn('r.faktor1id', range(105, 115))
            ->get()
            ->getResultArray();

        $nilaiLookup = [];
        foreach ($nilaiData as $nilai) {
            $nilaiLookup[$nilai['faktor1id']] = $nilai;
        }

        $structure = [
            [
                'id' => 105,
                'title' => 'Komposisi dan konsentrasi aset dan kewajiban',
                'type' => 'category',
                'faktor_id' => 105,
                'children' => [
                    [
                        'id' => 106,
                        'title' => 'Rasio aset likuid terhadap total aset',
                        'faktor_id' => 106
                    ],
                    [
                        'id' => 107,
                        'title' => 'Rasio aset likuid terhadap kewajiban lancar',
                        'faktor_id' => 107
                    ],
                    [
                        'id' => 108,
                        'title' => 'Rasio kredit yang diberikan terhadap total dana pihak ketiga bukan bank (Loan to Deposit Ratio/LDR)',
                        'faktor_id' => 108
                    ],
                    [
                        'id' => 109,
                        'title' => 'Rasio 25 deposan dan penabung terbesar terhadap total dana pihak ketiga',
                        'faktor_id' => 109
                    ],
                    [
                        'id' => 110,
                        'title' => 'Rasio Pendanaan non inti terhadap total pendanaan',
                        'faktor_id' => 110
                    ]
                ]
            ],
            [
                'id' => 111,
                'title' => 'Kerentanan pada kebutuhan pendanaan serta akses pada sumber pendanaan',
                'type' => 'category',
                'faktor_id' => 111,
                'children' => [
                    [
                        'id' => 112,
                        'title' => 'Penilaian kebutuhan pendanaan BPR pada situasi normal maupun krisis, dan kemampuan BPR untuk memenuhi  Kebutuhan pendanaan',
                        'faktor_id' => 112
                    ],
                    [
                        'id' => 113,
                        'title' => 'Penilaian terhadap seberapa luas atau seberapa besar BPR memiliki komitmen pendanaan yang dapat digunakan jika dibutuhkan.',
                        'faktor_id' => 113
                    ]
                ]
            ],
            [
                'id' => 114,
                'title' => 'Lainnya',
                'type' => 'single',
                'faktor_id' => 114
            ]
        ];

        foreach ($structure as &$item) {
            $item['nilai'] = $nilaiLookup[$item['faktor_id']] ?? null;

            if (isset($item['children'])) {
                foreach ($item['children'] as &$child) {
                    $child['nilai'] = $nilaiLookup[$child['faktor_id']] ?? null;
                }
            }
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data' => $structure
        ]);
    }

    private function getPenilaianConfig()
    {
        return [
            105 => [
                'descriptions' => [
                    1 => 'Parameter Komposisi dan konsentrasi aset dan kewajiban berada pada tingkat risiko yang Sangat Rendah',
                    2 => 'Parameter Komposisi dan konsentrasi aset dan kewajiban berada pada tingkat risiko yang Rendah',
                    3 => 'Parameter Komposisi dan konsentrasi aset dan kewajiban berada pada tingkat risiko yang Sedang',
                    4 => 'Parameter Komposisi dan konsentrasi aset dan kewajiban berada pada tingkat risiko yang Tinggi',
                    5 => 'Parameter Komposisi dan konsentrasi aset dan kewajiban berada pada tingkat risiko yang Sangat Tinggi'
                ],
                'catatan' => 'Parameter Komposisi dan konsentrasi aset dan kewajiban'
            ],

            106 => [
                'descriptions' => [
                    1 => '≥ 15% ',
                    2 => 'Komposisi aset likuid lebih rendah dari 15% terhadap total aset dan komposisi aset',
                    3 => 'Komposisi aset likuid lebih rendah dari 15% terhadap total aset dan komposisi aset',
                    4 => 'Komposisi aset likuid lebih rendah dari 15% terhadap total aset dan komposisi aset',
                    5 => 'Komposisi aset likuid lebih rendah dari 15% terhadap total'
                ],
                'catatan' => 'Rasio aset likuid terhadap total aset'
            ],

            107 => [
                'descriptions' => [
                    1 => '≥ 20%',
                    2 => 'likuid lebih rendah dari 20% terhadap kewajiban lancar, namun masih memadai untuk menutup kewajiban jatuh tempo',
                    3 => 'likuid lebih rendah dari 20% terhadap kewajiban lancar, namun cukup memadai untuk menutup kewajiban jatuh tempo',
                    4 => 'likuid lebih rendah dari 20% terhadap kewajiban lancar, namun cukup memadai untuk menutup kewajiban jatuh tempo',
                    5 => 'aset dan Komposisi aset likuid lebih rendah dari 20% terhadap kewajiban lancar, dan tidak memadai untuk menutup kewajiban jatuh tempo; dan/atau <br>
                    • Rasio aset likuid/kewajiban lancar memenuhi kriteria BDPI'
                ],
                'catatan' => 'Rasio aset likuid terhadap kewajiban lancar'
            ],

            108 => [
                'threshold' => '90%',
                'descriptions' => [
                    1 => '≤ 90% ',
                    2 => 'LDR lebih tinggi dari 90% dan kredit berkualitas tidak baik tidak signifikan',
                    3 => 'LDR lebih tinggi dari 90% namun kredit berkualitas tidak baik kurang signifikan',
                    4 => 'LDR lebih tinggi dari 90% namun kredit berkualitas tidak baik cukup signifikan',
                    5 => 'LDR lebih tinggi dari 90% dan kredit berkualitas tidak baik sangat signifikan'
                ],
                'catatan' => 'Rasio kredit yang diberikan terhadap total dana pihak ketiga bukan bank (Loan to Deposit Ratio/LDR)'
            ],

            109 => [
                'threshold' => '25%',
                'descriptions' => [
                    1 => '≤ 25% ',
                    2 => 'Komposisi 25 deposan dan penabung terbesar lebih dari 25%  dan seluruhnya merupakan nasabah lama',
                    3 => 'Komposisi 25 deposan dan penabung terbesar lebih dari 25% dan sebagian besar merupakan nasabah lama',
                    4 => 'Komposisi 25 deposan dan penabung terbesar lebih dari 25% dan sebagian besar merupakan nasabah baru',
                    5 => 'Komposisi 25 deposan dan penabung terbesar lebih dari 25% namun seluruhnya merupakan nasabah baru'
                ],
                'catatan' => 'Rasio 25 deposan dan penabung terbesar terhadap total dana pihak ketiga'
            ],

            110 => [
                'descriptions' => [
                    1 => '≤ 10% ',
                    2 => 'Rasio pendanaan non inti lebih besar dari 10% namun tidak signifikan terhadap total pendanaan, dan masih dapat dikelola oleh BPR',
                    3 => 'Rasio pendanaan non inti lebih besar dari 10% dan cukup signifikan terhadap total pendanaan',
                    4 => 'Rasio pendanaan non inti lebih besar dari 10%, dan signifikan sehingga hampir mendominasi BPR',
                    5 => 'Rasio pendanaan non inti sangat besar dan mendominasi pendanaan BPR'
                ],
                'catatan' => 'Rasio Pendanaan non inti terhadap total pendanaan'
            ],

            111 => [
                'descriptions' => [
                    1 => 'Parameter Kerentanan pada kebutuhan pendanaan serta akses pada sumber pendanaan berada pada tingkat risiko yang Sangat Rendah',
                    2 => 'Parameter Kerentanan pada kebutuhan pendanaan serta akses pada sumber pendanaan berada pada tingkat risiko yang Rendah',
                    3 => 'Parameter Kerentanan pada kebutuhan pendanaan serta akses pada sumber pendanaan berada pada tingkat risiko yang Sedang',
                    4 => 'Parameter Kerentanan pada kebutuhan pendanaan serta akses pada sumber pendanaan berada pada tingkat risiko yang Tinggi',
                    5 => 'Parameter Kerentanan pada kebutuhan pendanaan serta akses pada sumber pendanaan berada pada tingkat risiko yang Sangat Tinggi'
                ],
                'catatan' => 'Parameter Kerentanan pada kebutuhan pendanaan serta akses pada sumber pendanaan'
            ],

            112 => [
                'descriptions' => [
                    1 => '• BPR sangat mampu memenuhi kewajiban dan kebutuhan arus kas pada kondisi normal maupun krisis; dan/atau <br>
                    • Arus kas BPR yang berasal dari aset dan kewajiban dapat saling tutup dengan sangat baik (well matched).',
                    2 => "• BPR mampu memenuhi kewajiban dan kebutuhan arus kas dan pada kondisi normal maupun krisis; dan/atau <br>
                    • Arus kas BPR yang berasal dari aset dan kewajiban dapat saling tutup pada mayoritas skala waktu dengan baik.",
                    3 => '• BPR cukup mampu memenuhi kewajiban dan kebutuhan arus kas pada kondisi normal maupun krisis (100%) dan/atau <br>
                    • Arus kas BPR yang berasal dari aset dan kewajiban dapat saling tutup dengan cukup baik (100%), terutama pada jangka pendek.',
                    4 => '• BPR kurang mampu memenuhi kewajiban dan kebutuhan arus kas pada kondisi normal maupun krisis; dan/atau <br>
                    • Selisih (mismatch) arus kas BPR pada berbagai skala waktu yang cukup signifikan.',
                    5 => '• BPR tidak mampu memenuhi kewajiban dan kebutuhan arus kas pada kondisi normal maupun krisis; dan/atau <br>
                    • Arus kas BPR tidak dapat saling tutup.'
                ],
                'catatan' => 'Penilaian kebutuhan pendanaan BPR pada situasi normal maupun krisis, dan kemampuan BPR untuk memenuhi  Kebutuhan pendanaan'
            ],

            113 => [
                'descriptions' => [
                    1 => 'Akses BPR pada sumber pendanaan sangat memadai dibuktikan dengan reputasi BPR sangat baik, pinjaman bank yang sewaktu-waktu dapat ditarik sangat memadai, dan terdapat komitmen/ dukungan likuiditas dari pemegang saham pengendali/ perusahaan induk/intra grup BPR.',
                    2 => "Akses BPR pada sumber pendanaan memadai dibuktikan dengan reputasi BPR baik, pinjaman bank yang sewaktu waktu dapat ditarik memadai, dan terdapat komitmen/ dukungan likuiditas dari pemegang saham pengendali/ perusahaan induk/intra grup BPR.",
                    3 => 'Akses BPR pada sumber pendanaan cukup memadai dibuktikan dengan reputasi BPR cukup baik, pinjaman bank yang sewaktu-waktu dapat ditarik cukup memadai, dan terdapat komitmen/ dukungan likuiditas dari pemegang saham pengendali/ perusahaan induk/intra grup BPR yang cukup memadai.',
                    4 => 'Akses BPR pada sumber pendanaan kurang memadai dibuktikan dengan reputasi BPR menurun, pinjaman bank yang sewaktu-waktu dapat ditarik kurang memadai, dan komitmen/ dukungan likuiditas dari pemegang saham pengendali/ perusahaan induk/intra grup BPR yang sangat terbatas.',
                    5 => 'Akses BPR pada sumber pendanaan tidak memadai dibuktikan dengan reputasi BPR buruk sehingga BPR kesulitan memperoleh pendanaan, tidak terdapat pinjaman bank yang sewaktu-waktu dapat ditarik, dan tidak terdapat komitmen/ dukungan likuiditas dari pemegang saham pengendali/ perusahaan induk/intra grup BPR.'
                ],
                'catatan' => 'Penilaian terhadap seberapa luas atau seberapa besar BPR memiliki komitmen pendanaan yang dapat digunakan jika dibutuhkan.'
            ],

            114 => [
                'descriptions' => [
                    1 => 'Parameter Likuiditas Inheren Lainnya berada pada tingkat risiko yang Sangat Rendah',
                    2 => 'Parameter Likuiditas Inheren Lainnya berada pada tingkat risiko yang Rendah',
                    3 => 'Parameter Likuiditas Inheren Lainnya berada pada tingkat risiko yang Sedang',
                    4 => 'Parameter Likuiditas Inheren Lainnya berada pada tingkat risiko yang Tinggi',
                    5 => 'Parameter Likuiditas Inheren Lainnya berada pada tingkat risiko yang Sangat Tinggi'
                ],
                'catatan' => 'Parameter Likuiditas Inheren Lainnya'
            ],
        ];
    }

    public function cekKomentarBaru()
    {
        $subkategori = 'LIKUIDITASINHEREN';
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

    public function getKomentarByFaktorId($faktorId)
    {
        if (!$this->request->isAJAX() || !is_numeric($faktorId)) {
            return $this->response->setStatusCode(404)->setBody('Not Found');
        }

        $komentarList = $this->komentarModel->getKomentarByFaktorId(
            'LIKUIDITASINHEREN',
            $faktorId,
            $this->userKodebpr,
            $this->periodeId
        );

        return $this->response->setJSON($komentarList);
    }

    public function getNilaiByFaktorId($faktorId)
    {
        if (!$this->request->isAJAX() || !is_numeric($faktorId)) {
            return $this->response->setStatusCode(404)->setBody('Not Found');
        }

        $nilaiList = $this->nilaiModel->getNilaiByFaktorId($faktorId);
        return $this->response->setJSON($nilaiList);
    }

    public function ubah()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back()->with('error', 'Invalid request');
        }

        $faktor1id = $this->request->getPost('faktor1id');

        if (!$faktor1id || !$this->userKodebpr || !$this->periodeId) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Data tidak valid'
            ]);
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
            'updated_at' => date('Y-m-d H:i:s')
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

            $categoryMapping = [
                105 => [106, 107, 108, 109, 110],
                111 => [112, 113]
            ];

            foreach ($categoryMapping as $categoryId => $childrenIds) {
                if (in_array($faktor1id, $childrenIds)) {
                    $this->calculateAndSaveCategoryAverage(
                        $categoryId,
                        $childrenIds,
                        $this->userKodebpr,
                        $this->periodeId
                    );
                    break;
                }
            }

            if (in_array($faktor1id, [105, 106, 107, 108, 109, 110, 111, 112, 113, 114])) {
                $rataRata = $this->nilaiModel->hitungRataRata(115, $this->userKodebpr, $this->periodeId);
                $this->nilaiModel->insertOrUpdateRataRata($rataRata, 115, $this->userKodebpr, $this->periodeId);
            }

            $this->db->table('likuiditasinheren')
                ->where('faktor1id', 115)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update(['accdir2' => 0, 'is_approved' => 0]);

            $nilai13 = $this->nilaiModel->where([
                'faktor1id' => 115,
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId
            ])->first();

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Data berhasil diubah',
                'peringkat13' => $nilai13['penilaiankredit'] ?? null
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error ubah: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ]);
        }
    }

    public function ubahkesimpulan()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back()->with('error', 'Invalid request');
        }

        $faktor1id = $this->request->getPost('faktor1id');
        $keterangan = $this->request->getPost('keterangan');

        if (!$faktor1id) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'ID tidak ditemukan.'
            ]);
        }

        if (!$this->userKodebpr || !$this->periodeId) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Data user atau periode tidak valid'
            ]);
        }

        $existingData = $this->nilaiModel
            ->where('faktor1id', $faktor1id)
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->first();

        if (!$existingData) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Data tidak ditemukan'
            ]);
        }

        $data = [
            'keterangan' => $keterangan,
            'penilaiankredit' => $existingData['penilaiankredit'],
            'user_id' => $this->userId,
            'updated_at' => date('Y-m-d H:i:s'),
            'is_approved' => 0
        ];

        try {
            $result = $this->db->table('likuiditasinheren')
                ->where('faktor1id', $faktor1id)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update($data);

            if ($result) {
                $this->db->table('likuiditasinheren')
                    ->where('faktor1id', 115)
                    ->where('kodebpr', $this->userKodebpr)
                    ->where('periode_id', $this->periodeId)
                    ->update(['accdir2' => 0, 'is_approved' => 0]);

                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Keterangan kesimpulan berhasil diubah'
                ]);
            }

            throw new \Exception('Gagal mengubah keterangan');
        } catch (\Exception $e) {
            log_message('error', 'Error ubah kesimpulan: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Gagal mengubah keterangan: ' . $e->getMessage()
            ]);
        }
    }

    public function ubahketerangan()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back()->with('error', 'Invalid request');
        }

        $faktor1id = $this->request->getPost('faktor1id');
        $keterangan = $this->request->getPost('keterangan');

        if (!$faktor1id) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'ID tidak ditemukan.'
            ]);
        }

        if (!$this->userKodebpr || !$this->periodeId) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Data user atau periode tidak valid'
            ]);
        }

        $existingData = $this->nilaiModel
            ->where('faktor1id', $faktor1id)
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->first();

        if (!$existingData) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Data tidak ditemukan'
            ]);
        }

        $data = [
            'keterangan' => $keterangan,
            'penilaiankredit' => $existingData['penilaiankredit'],
            'penjelasanpenilaian' => $existingData['penjelasanpenilaian'],
            'user_id' => $this->userId,
            'updated_at' => date('Y-m-d H:i:s'),
            'is_approved' => 0,
            'accdir2' => 0
        ];

        if (isset($existingData['rasiokredit']) && !empty($existingData['rasiokredit'])) {
            $data['rasiokredit'] = $existingData['rasiokredit'];
        }

        try {
            $this->db->table('likuiditasinheren')
                ->where('faktor1id', $faktor1id)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update($data);

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Keterangan berhasil diubah'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error ubah keterangan: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Gagal mengubah keterangan: ' . $e->getMessage()
            ]);
        }
    }

    public function tambahNilai()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back()->with('error', 'Invalid request');
        }

        $validation = $this->validate([
            'penilaiankredit' => 'required',
            'keterangan' => 'required',
        ]);

        if (!$validation) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $this->validator->listErrors()
            ]);
        }

        $faktorId = $this->request->getPost('faktor_id');

        if (!$faktorId) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'ID tidak ditemukan'
            ]);
        }

        $user = $this->userModel->find($this->userId);

        // Cek apakah data sudah ada
        $existingData = $this->nilaiModel
            ->where('faktor1id', $faktorId)
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
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
            'created_at' => date('Y-m-d H:i:s'),
        ];

        try {
            if ($existingData) {
                // ✅ PRESERVE rasiokredit
                if (isset($existingData['rasiokredit']) && !empty($existingData['rasiokredit'])) {
                    $data['rasiokredit'] = $existingData['rasiokredit'];
                }

                $data['updated_at'] = date('Y-m-d H:i:s');

                $this->db->table('likuiditasinheren')
                    ->where('faktor1id', $faktorId)
                    ->where('kodebpr', $this->userKodebpr)
                    ->where('periode_id', $this->periodeId)
                    ->update($data);
            } else {
                // Insert data baru
                $data['created_at'] = date('Y-m-d H:i:s');
                $this->db->table('likuiditasinheren')->insert($data);
            }

            $categoryMapping = [
                105 => [106, 107, 108, 109, 110],
                111 => [112, 113]
            ];

            foreach ($categoryMapping as $categoryId => $childrenIds) {
                if (in_array($faktorId, $childrenIds)) {
                    $this->calculateAndSaveCategoryAverage(
                        $categoryId,
                        $childrenIds,
                        $this->userKodebpr,
                        $this->periodeId
                    );
                    break;
                }
            }

            if (in_array($faktorId, [105, 106, 107, 108, 109, 110, 111, 112, 113, 114])) {
                $rataRata = $this->nilaiModel->hitungRataRata(115, $this->userKodebpr, $this->periodeId);
                $this->nilaiModel->insertOrUpdateRataRata($rataRata, 115, $this->userKodebpr, $this->periodeId);
            }

            $nilai13 = $this->nilaiModel->where([
                'faktor1id' => 115,
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId
            ])->first();

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Nilai berhasil ditambahkan',
                'peringkat13' => $nilai13['penilaiankredit'] ?? null
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error tambahNilai: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Gagal menambahkan nilai: ' . $e->getMessage()
            ]);
        }
    }

    public function tambahNilairasio()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;

        if (!isset($_POST['tambahNilairasio'])) {
            return redirect()->to(base_url('Likuiditasinheren'));
        }

        $validation = $this->validate([
            'rasiokredit' => 'required',
            'penilaiankredit' => 'required',
            'keterangan' => 'required',
        ]);

        if (!$validation) {
            return redirect()->back()->with('err', $this->validator->listErrors());
        }

        if (!$this->userKodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
        }

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

        // Calculate and save average
        $rataRata = $this->nilaiModel->hitungRataRata($faktorId, $this->userKodebpr, $this->periodeId);
        $rataRata = ($rataRata - floor($rataRata) >= 0.5) ? ceil($rataRata) : floor($rataRata);
        $this->nilaiModel->insertOrUpdateRataRata($rataRata, 1, $this->userKodebpr, $this->periodeId);

        return redirect()->to(base_url('Likuiditasinheren') . '?modal_nilai=' . $faktorId)
            ->with('message', 'Nilai berhasil ditambahkan');
    }

    public function tambahKomentar()
    {
        $isAjax = $this->request->isAJAX();

        if (!$isAjax && !isset($_POST['tambahKomentar'])) {
            return redirect()->to(base_url('Likuiditasinheren'));
        }

        $validation = $this->validate(['komentar' => 'required']);

        if (!$validation) {
            if ($isAjax) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Komentar tidak boleh kosong'
                ]);
            }
            return redirect()->back()->with('err', $this->validator->listErrors());
        }

        $faktorId = $this->request->getPost('faktor_id');
        $komentar = $this->request->getPost('komentar');

        if (!$faktorId) {
            if ($isAjax) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'ID tidak ditemukan'
                ]);
            }
            return redirect()->back()->with('err', 'ID tidak ditemukan');
        }

        try {
            $data = [
                'subkategori' => "LIKUIDITASINHEREN",
                'faktor1id' => $faktorId,
                'komentar' => $komentar,
                'user_id' => $this->userId,
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $result = $this->komentarModel->insert($data);

            if ($result) {
                if ($isAjax) {
                    $komentarList = $this->komentarModel->getKomentarByFaktorId(
                        'LIKUIDITASINHEREN',
                        $faktorId,
                        $this->userKodebpr,
                        $this->periodeId
                    );

                    return $this->response->setJSON([
                        'status' => 'success',
                        'message' => 'Komentar berhasil ditambahkan',
                        'comments' => $komentarList
                    ]);
                }

                return redirect()->to(base_url('Likuiditasinheren') . '?modal_komentar=' . $faktorId)
                    ->with('message', 'Komentar berhasil ditambahkan');
            } else {
                throw new \Exception('Gagal menyimpan komentar');
            }

        } catch (\Exception $e) {
            log_message('error', 'Error tambahKomentar: ' . $e->getMessage());

            if ($isAjax) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Gagal menambahkan komentar: ' . $e->getMessage()
                ]);
            }

            return redirect()->back()->with('err', 'Gagal menambahkan komentar');
        }
    }

    public function simpanKalkulator()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }

        if (!isset($_POST['simpanKalkulator'])) {
            return redirect()->to(base_url('Likuiditasinheren'));
        }

        if (!$this->userKodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
        }

        if (!$this->periodeId) {
            return redirect()->back()->with('error', 'Periode tidak valid');
        }

        $validation = $this->validate([
            'totalaset' => 'required|decimal',
            'kas' => 'required|decimal',
            'girobanklain' => 'required|decimal',
            'tabunganbanklain' => 'required|decimal',
            'kewajibansegera' => 'required|decimal',
            'tabungandpk' => 'required|decimal',
            'depositodpk' => 'required|decimal',
            'tabunganabp' => 'required|decimal',
            'depositoabp' => 'required|decimal',
            'pinjamanditerima' => 'required|decimal',
            'kreditkyd' => 'required|decimal',
            'penabung25deposan' => 'required|decimal',
            'totalpendanaan' => 'required|decimal',
            'dpkdiataslps' => 'required|decimal',
            'pinjamananmungkinditarik' => 'required|decimal',
        ]);

        if (!$validation) {
            return redirect()->back()->with('err', $this->validator->listErrors());
        }

        $totalaset = $this->request->getPost('totalaset');
        $kas = $this->request->getPost('kas');
        $girobanklain = $this->request->getPost('girobanklain');
        $tabunganbanklain = $this->request->getPost('tabunganbanklain');
        $kewajibansegera = $this->request->getPost('kewajibansegera');
        $tabungandpk = $this->request->getPost('tabungandpk');
        $depositodpk = $this->request->getPost('depositodpk');
        $tabunganabp = $this->request->getPost('tabunganabp');
        $depositoabp = $this->request->getPost('depositoabp');
        $pinjamanditerima = $this->request->getPost('pinjamanditerima');
        $kreditkyd = $this->request->getPost('kreditkyd');
        $penabung25deposan = $this->request->getPost('penabung25deposan');
        $totalpendanaan = $this->request->getPost('totalpendanaan');
        $pinjamanditerima = $this->request->getPost('pinjamanditerima');
        $dpkdiataslps = $this->request->getPost('dpkdiataslps');
        $pinjamananmungkinditarik = $this->request->getPost('pinjamananmungkinditarik');

        // Hitung nilai-nilai turunan
        $asetlikuid = $kas + $girobanklain + $tabunganbanklain;
        $kewajibanlancar = $kewajibansegera + $tabungandpk + $depositodpk + $tabunganabp + $depositoabp + $pinjamanditerima;
        $totaldpk = $tabungandpk + $depositodpk;
        $transaksibpr = $tabunganabp + $depositoabp;
        $pendanaannoninti = $dpkdiataslps + $transaksibpr + $pinjamananmungkinditarik;
        $rasioasetlikuidtotalaset = $totalaset > 0 ? ($asetlikuid / $totalaset) * 100 : 0;
        $rasioasetlikuidkewajiban = $kewajibanlancar > 0 ? ($asetlikuid / $kewajibanlancar) * 100 : 0;
        $rasiokreditterhadapdpk = $kreditkyd > 0 ? ($totaldpk / $kreditkyd) * 100 : 0;
        $rasio25deposan = $totaldpk + $penabung25deposan + $totaldpk;
        $rasiononinti = $totalpendanaan > 0 ? ($pendanaannoninti / $totalpendanaan) * 100 : 0;

        // Ambil data user
        $user = $this->userModel->find($this->userId);

        // Siapkan data untuk disimpan
        $data = [
            'user_id' => $this->userId,
            'fullname' => $user['fullname'] ?? 'Unknown',
            'kodebpr' => $this->userKodebpr,
            'periode_id' => $this->periodeId,
            'totalaset' => $totalaset,
            'kas' => $kas,
            'girobanklain' => $girobanklain,
            'tabunganbanklain' => $tabunganbanklain,
            'tabungandpk' => $tabungandpk,
            'depositodpk' => $depositodpk,
            'pinjamanditerima' => $pinjamanditerima,
            'penabung25deposan' => $penabung25deposan,
            'totalpendanaan' => $totalpendanaan,
            'dpkdiataslps' => $dpkdiataslps,
            'transaksibpr' => $transaksibpr,
            'pinjamananmungkinditarik' => $pinjamananmungkinditarik,
            'asetlikuid' => $asetlikuid,
            'kewajibanlancar' => $kewajibanlancar,
            'totaldpk' => $totaldpk,
            'pendanaannoninti' => $pendanaannoninti,
            'rasioasetlikuidtotalaset' => $rasioasetlikuidtotalaset,
            'rasioasetlikuidkewajiban' => $rasioasetlikuidkewajiban,
            'rasiokreditterhadapdpk' => $rasiokreditterhadapdpk,
            'rasio25deposan' => $rasio25deposan,
            'rasiononinti' => $rasiononinti
        ];

        try {
            // Cek apakah data sudah ada berdasarkan kodebpr dan periode_id
            $existingData = $this->kalkulatorModel
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->first();

            if ($existingData) {
                // Data sudah ada, lakukan UPDATE
                $data['updated_at'] = date('Y-m-d H:i:s');

                $result = $this->kalkulatorModel
                    ->where('kodebpr', $this->userKodebpr)
                    ->where('periode_id', $this->periodeId)
                    ->set($data)
                    ->update();

                if ($result) {
                    return redirect()->to(base_url('Likuiditasinheren'))
                        ->with('message', 'Data kalkulator berhasil diupdate');
                } else {
                    throw new \Exception('Gagal mengupdate data');
                }

            } else {
                // Data belum ada, lakukan INSERT
                $data['created_at'] = date('Y-m-d H:i:s');

                $result = $this->kalkulatorModel->insert($data);

                if ($result) {
                    return redirect()->to(base_url('Likuiditasinheren'))
                        ->with('message', 'Data kalkulator berhasil disimpan');
                } else {
                    throw new \Exception('Gagal menyimpan data');
                }
            }

        } catch (\Exception $e) {
            log_message('error', 'Error simpanKalkulator: ' . $e->getMessage());
            return redirect()->back()
                ->with('err', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function insertRasioToKertasKerja()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }

        if (!$this->request->isAJAX()) {
            return redirect()->to(base_url('Likuiditasinheren'));
        }

        if (!$this->userKodebpr || !$this->periodeId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'User tidak memiliki kode BPR atau periode yang valid'
            ]);
        }

        // ✅ Ambil SEMUA data dari request (termasuk data kalkulator)
        $totalaset = $this->request->getPost('totalaset');
        $kas = $this->request->getPost('kas');
        $girobanklain = $this->request->getPost('girobanklain');
        $tabunganbanklain = $this->request->getPost('tabunganbanklain');
        $kewajibansegera = $this->request->getPost('kewajibansegera');
        $tabungandpk = $this->request->getPost('tabungandpk');
        $depositodpk = $this->request->getPost('depositodpk');
        $tabunganabp = $this->request->getPost('tabunganabp');
        $depositoabp = $this->request->getPost('depositoabp');
        $pinjamanditerima = $this->request->getPost('pinjamanditerima');
        $kreditkyd = $this->request->getPost('kreditkyd');
        $penabung25deposan = $this->request->getPost('penabung25deposan');
        $totalpendanaan = $this->request->getPost('totalpendanaan');
        $dpkdiataslps = $this->request->getPost('dpkdiataslps');
        $pinjamananmungkinditarik = $this->request->getPost('pinjamananmungkinditarik');

        // Ambil rasio yang sudah dihitung
        $rasioasetlikuidtotalaset = $this->request->getPost('rasioasetlikuidtotalaset');
        $rasioasetlikuidkewajiban = $this->request->getPost('rasioasetlikuidkewajiban');
        $rasiokreditterhadapdpk = $this->request->getPost('rasiokreditterhadapdpk');
        $rasio25deposan = $this->request->getPost('rasio25deposan');
        $rasiononinti = $this->request->getPost('rasiononinti');

        // ✅ Hitung nilai turunan
        $asetlikuid = ($kas ?? 0) + ($girobanklain ?? 0) + ($tabunganbanklain ?? 0);
        $kewajibanlancar = ($kewajibansegera ?? 0) + ($tabungandpk ?? 0) + ($depositodpk ?? 0) +
            ($tabunganabp ?? 0) + ($depositoabp ?? 0) + ($pinjamanditerima ?? 0);
        $totaldpk = ($tabungandpk ?? 0) + ($depositodpk ?? 0);
        $transaksibpr = ($tabunganabp ?? 0) + ($depositoabp ?? 0);
        $pendanaannoninti = ($dpkdiataslps ?? 0) + $transaksibpr + ($pinjamananmungkinditarik ?? 0);

        // ✅ Debug log
        log_message('info', 'Insert Rasio - Data diterima: ' . json_encode([
            'rasioasetlikuidtotalaset' => $rasioasetlikuidtotalaset,
            'rasioasetlikuidkewajiban' => $rasioasetlikuidkewajiban,
            'rasiokreditterhadapdpk' => $rasiokreditterhadapdpk,
            'rasio25deposan' => $rasio25deposan,
            'rasiononinti' => $rasiononinti
        ]));

        // Validasi - pastikan ada minimal 1 rasio
        if (
            empty($rasioasetlikuidtotalaset) && empty($rasioasetlikuidkewajiban) &&
            empty($rasiokreditterhadapdpk) && empty($rasio25deposan) && empty($rasiononinti)
        ) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Tidak ada data rasio untuk dimasukkan. Silakan hitung terlebih dahulu.'
            ]);
        }

        $user = $this->userModel->find($this->userId);

        try {
            // ✅ STEP 1: Simpan/Update ke database kalkulator_likuiditas
            $existingKalkulator = $this->kalkulatorModel
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->first();

            $kalkulatorData = [
                'user_id' => $this->userId,
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId,
                'totalaset' => $totalaset ?? 0,
                'kas' => $kas ?? 0,
                'girobanklain' => $girobanklain ?? 0,
                'tabunganbanklain' => $tabunganbanklain ?? 0,
                'kewajibansegera' => $kewajibansegera ?? 0,
                'tabungandpk' => $tabungandpk ?? 0,
                'depositodpk' => $depositodpk ?? 0,
                'tabunganabp' => $tabunganabp ?? 0,
                'depositoabp' => $depositoabp ?? 0,
                'pinjamanditerima' => $pinjamanditerima ?? 0,
                'kreditkyd' => $kreditkyd ?? 0,
                'penabung25deposan' => $penabung25deposan ?? 0,
                'totalpendanaan' => $totalpendanaan ?? 0,
                'dpkdiataslps' => $dpkdiataslps ?? 0,
                'pinjamananmungkinditarik' => $pinjamananmungkinditarik ?? 0,
                'asetlikuid' => $asetlikuid,
                'kewajibanlancar' => $kewajibanlancar,
                'totaldpk' => $totaldpk,
                'transaksibpr' => $transaksibpr,
                'pendanaannoninti' => $pendanaannoninti,
                'rasioasetlikuidtotalaset' => $rasioasetlikuidtotalaset ?? 0,
                'rasioasetlikuidkewajiban' => $rasioasetlikuidkewajiban ?? 0,
                'rasiokreditterhadapdpk' => $rasiokreditterhadapdpk ?? 0,
                'rasio25deposan' => $rasio25deposan ?? 0,
                'rasiononinti' => $rasiononinti ?? 0,
            ];

            if ($existingKalkulator) {
                $kalkulatorData['updated_at'] = date('Y-m-d H:i:s');
                $this->kalkulatorModel->update($existingKalkulator['id'], $kalkulatorData);
                log_message('info', 'Kalkulator data updated for ID: ' . $existingKalkulator['id']);
            } else {
                $kalkulatorData['created_at'] = date('Y-m-d H:i:s');
                $this->kalkulatorModel->insert($kalkulatorData);
                log_message('info', 'Kalkulator data inserted successfully');
            }

            // ✅ STEP 2: Insert/Update rasio ke tabel likuiditasinheren
            $rasioMapping = [
                106 => $rasioasetlikuidtotalaset,
                107 => $rasioasetlikuidkewajiban,
                108 => $rasiokreditterhadapdpk,
                109 => $rasio25deposan,
                110 => $rasiononinti
            ];

            $successCount = 0;
            $errorMessages = [];
            $processedFaktors = [];

            foreach ($rasioMapping as $faktorId => $rasioValue) {
                // ✅ PERBAIKAN: Hanya skip jika benar-benar kosong atau null
                // Nilai 0 tetap diproses (karena 0% adalah nilai valid)
                if ($rasioValue === null || $rasioValue === '' || $rasioValue === false) {
                    log_message('info', "Faktor {$faktorId}: Skipped (null or empty)");
                    continue;
                }

                // ✅ Convert ke float untuk memastikan tipe data benar
                $rasioValue = (float) $rasioValue;

                log_message('info', "Processing Faktor {$faktorId}: {$rasioValue}");

                $existingData = $this->nilaiModel
                    ->where('faktor1id', $faktorId)
                    ->where('kodebpr', $this->userKodebpr)
                    ->where('periode_id', $this->periodeId)
                    ->first();

                if ($existingData) {
                    // UPDATE
                    $updateData = [
                        'rasiokredit' => $rasioValue,
                        'user_id' => $this->userId,
                        'fullname' => $user['fullname'] ?? 'Unknown',
                        'updated_at' => date('Y-m-d H:i:s')
                    ];

                    $result = $this->nilaiModel
                        ->where('faktor1id', $faktorId)
                        ->where('kodebpr', $this->userKodebpr)
                        ->where('periode_id', $this->periodeId)
                        ->set($updateData)
                        ->update();

                    if ($result) {
                        $successCount++;
                        $processedFaktors[] = $faktorId;
                        log_message('info', "Faktor {$faktorId}: Updated successfully with value {$rasioValue}");
                    } else {
                        $errorMessages[] = "Gagal update rasio untuk Faktor ID {$faktorId}";
                        log_message('error', "Faktor {$faktorId}: Update failed");
                    }
                } else {
                    // INSERT
                    $insertData = [
                        'faktor1id' => $faktorId,
                        'rasiokredit' => $rasioValue,
                        'fullname' => $user['fullname'] ?? 'Unknown',
                        'user_id' => $this->userId,
                        'kodebpr' => $this->userKodebpr,
                        'periode_id' => $this->periodeId,
                        'is_approved' => 0,
                        'accdir2' => 0,
                        'created_at' => date('Y-m-d H:i:s'),
                    ];

                    $result = $this->nilaiModel->insert($insertData);

                    if ($result) {
                        $successCount++;
                        $processedFaktors[] = $faktorId;
                        log_message('info', "Faktor {$faktorId}: Inserted successfully with value {$rasioValue}");
                    } else {
                        $errorMessages[] = "Gagal insert rasio untuk Faktor ID {$faktorId}";
                        log_message('error', "Faktor {$faktorId}: Insert failed");
                    }
                }

                // Update rata-rata kategori jika berhasil
                if ($result && in_array($faktorId, [106, 107, 108, 109, 110])) {
                    $this->calculateAndSaveCategoryAverage(105, [106, 107, 108, 109, 110], $this->userKodebpr, $this->periodeId);
                }
            }

            // Update rata-rata total
            if ($successCount > 0) {
                $rataRata = $this->nilaiModel->hitungRataRata(115, $this->userKodebpr, $this->periodeId);
                $this->nilaiModel->insertOrUpdateRataRata($rataRata, 115, $this->userKodebpr, $this->periodeId);
            }

            // Response
            if ($successCount > 0) {
                $message = "Berhasil menyimpan data kalkulator dan memasukkan {$successCount} rasio ke kertas kerja (Faktor: " .
                    implode(', ', $processedFaktors) . ")";

                return $this->response->setJSON([
                    'success' => true,
                    'message' => $message,
                    'count' => $successCount,
                    'processed_faktors' => $processedFaktors
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Tidak ada rasio yang berhasil dimasukkan',
                    'errors' => $errorMessages
                ]);
            }

        } catch (\Exception $e) {
            log_message('error', 'Error insertRasioToKertasKerja: ' . $e->getMessage());

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'errors' => $errorMessages ?? []
            ]);
        }
    }

    private function checkAuthentication()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }
        return null;
    }

    public function setNullKolom($id)
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $success = $this->nilaiModel->setNullKolom($id);

        if ($success) {
            session()->setFlashdata('message', 'Data berhasil dihapus');
        } else {
            session()->setFlashdata('err', 'Data gagal dihapus');
        }

        return redirect()->to(base_url('Likuiditasinheren'));
    }

    public function approve($idNilai = null)
    {
        if ($idNilai === null) {
            return redirect()->back()->with('err', 'ID tidak valid');
        }

        $this->db->table('likuiditasinheren')
            ->where('id', $idNilai)
            ->update([
                'is_approved' => 1,
                'approved_by' => $this->userId,
                'approved_at' => date('Y-m-d H:i:s')
            ]);

        return redirect()->back()->with('message', 'Data berhasil disetujui');
    }

    public function unapprove($idNilai)
    {
        if (!is_numeric($idNilai) || $idNilai <= 0) {
            return redirect()->back()->with('err', 'ID tidak valid.');
        }

        $nilaiFaktor = $this->nilaiModel->find($idNilai);
        if (!$nilaiFaktor) {
            return redirect()->back()->with('err', 'Data tidak ditemukan.');
        }

        $dataUpdate = [
            'is_approved' => 0,
            'approved_by' => $this->userId,
            'approved_at' => date('Y-m-d H:i:s')
        ];

        if ($this->nilaiModel->update($idNilai, $dataUpdate)) {
            return redirect()->back()->with('message', 'Data approval dibatalkan.');
        }

        return redirect()->back()->with('err', 'Terjadi kesalahan saat membatalkan approval.');
    }

    public function approveSemua()
    {
        if (!$this->userKodebpr || !$this->periodeId) {
            return redirect()->back()->with('err', 'Kode BPR atau Periode ID tidak valid');
        }

        // Faktor wajib: 105–115
        $requiredFaktor = array_merge(range(105, 113), [115]);
        $totalRequired = count($requiredFaktor);

        // Cek semua faktor wajib sudah diisi
        $checkData = $this->nilaiModel
            ->select('COUNT(CASE WHEN penilaiankredit IS NOT NULL THEN 1 END) AS filled_count')
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->whereIn('faktor1id', $requiredFaktor)
            ->first();

        if ((int) $checkData['filled_count'] < $totalRequired) {
            return redirect()->back()
                ->with('err', 'Semua faktor wajib harus diisi terlebih dahulu');
        }

        try {
            // Approve semua faktor wajib
            $this->db->table('likuiditasinheren')
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->whereIn('faktor1id', $requiredFaktor)
                ->update([
                    'is_approved' => 1,
                    'approved_by' => $this->userId,
                    'approved_at' => date('Y-m-d H:i:s')
                ]);

            return redirect()->back()->with('message', 'Semua data berhasil disetujui.');
        } catch (\Exception $e) {
            log_message('error', 'Error in approveSemua: ' . $e->getMessage());
            return redirect()->back()->with('err', 'Terjadi kesalahan sistem');
        }
    }

    public function unapproveSemua()
    {
        if (!$this->userKodebpr || !$this->periodeId) {
            return redirect()->back()->with('err', 'Kode BPR atau Periode ID tidak valid');
        }

        $count = $this->nilaiModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->countAllResults();

        if ($count === 0) {
            return redirect()->back()->with('err', 'Tidak ada data yang bisa diupdate untuk periode ini');
        }

        $dataUpdate = [
            'is_approved' => 0,
            'approved_by' => $this->userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        try {
            $this->nilaiModel
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update(null, $dataUpdate);

            $rataRata = $this->nilaiModel->hitungRataRata(1, $this->userKodebpr, $this->periodeId);
            $this->nilaiModel->insertOrUpdateRataRata($rataRata, 1, $this->userKodebpr, $this->periodeId);

            return redirect()->back()->with('message', 'Semua data berhasil dibatalkan persetujuannya.');
        } catch (\Exception $e) {
            log_message('error', 'Error in unapproveSemua: ' . $e->getMessage());
            return redirect()->back()->with('err', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    public function accdir2()
    {
        $faktor1id = $this->request->getPost('faktor1id');

        if (!$faktor1id) {
            return redirect()->back()->with('error', 'Data tidak lengkap');
        }

        $this->db->table('likuiditasinheren')
            ->where('faktor1id', $faktor1id)
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->update([
                'accdir2' => 1,
                'accdir2_by' => $this->userId,
                'approved_at' => date('Y-m-d H:i:s')
            ]);

        return redirect()->back()->with('message', 'Data berhasil disetujui');
    }

    public function unapprovedir2()
    {
        if ($this->request->getMethod() !== 'post') {
            return $this->response->setStatusCode(405, 'Method Not Allowed');
        }

        $faktor1id = $this->request->getPost('faktor1id');
        $kodebpr = $this->request->getPost('kodebpr');
        $periodeId = $this->request->getPost('periode_id');

        if (!$faktor1id || !$kodebpr || !$periodeId) {
            return redirect()->to('/Likuiditasinheren')->with('error', 'Data tidak lengkap');
        }

        $nilaiFaktor = $this->nilaiModel
            ->where('faktor1id', $faktor1id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if (!$nilaiFaktor) {
            return redirect()->to('/Likuiditasinheren')->with('error', 'Data tidak ditemukan');
        }

        $dataUpdate = [
            'accdir2' => 0,
            'accdir2_by' => $this->userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->nilaiModel->update($nilaiFaktor['id'], $dataUpdate)) {
            return redirect()->to('/Likuiditasinheren')->with('message', 'Persetujuan dibatalkan');
        }

        return redirect()->to('/Likuiditasinheren')->with('error', 'Gagal memperbarui data');
    }

    public function recalculateCategoryAverage()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $faktorId = $this->request->getPost('faktor_id');

        $categoryMapping = [
            105 => [106, 107, 108, 109, 110],
            111 => [112, 113]
        ];

        $categoryToRecalculate = null;

        foreach ($categoryMapping as $categoryId => $childrenIds) {
            if (in_array($faktorId, $childrenIds)) {
                $categoryToRecalculate = $categoryId;
                break;
            }
        }

        if ($categoryToRecalculate) {
            $this->calculateAndSaveCategoryAverage(
                $categoryToRecalculate,
                $categoryMapping[$categoryToRecalculate],
                $this->userKodebpr,
                $this->periodeId
            );

            $rataRata = $this->nilaiModel->hitungRataRata(115, $this->userKodebpr, $this->periodeId);
            $this->nilaiModel->insertOrUpdateRataRata($rataRata, 115, $this->userKodebpr, $this->periodeId);

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Kategori berhasil di-recalculate'
            ]);
        }

        return $this->response->setJSON([
            'status' => 'error',
            'message' => 'Kategori tidak ditemukan'
        ]);
    }

    public function getUnreadCommentCountForFactor()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => 'error',
                'message' => 'Forbidden'
            ]);
        }

        $faktorId = $this->request->getGet('faktor_id');

        if (!$faktorId || !$this->userKodebpr || !$this->userId || !$this->periodeId) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 'error',
                'message' => 'Missing data.'
            ]);
        }

        $count = $this->commentReadsModel->countUnreadCommentsForUserByFactor(
            $faktorId,
            "LIKUIDITASINHEREN",
            $this->userKodebpr,
            $this->userId,
            $this->periodeId
        );

        return $this->response->setJSON(['unread_count' => $count]);
    }

    public function markUserCommentsAsRead()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => 'error',
                'message' => 'Forbidden'
            ]);
        }

        $faktorId = $this->request->getPost('faktor_id');

        if (!$faktorId || !$this->userKodebpr || !$this->userId || !$this->periodeId) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 'error',
                'message' => 'Missing data.'
            ]);
        }

        $marked = $this->commentReadsModel->markAllAsReadForFactor(
            $faktorId,
            "LIKUIDITASINHEREN",
            $this->userKodebpr,
            $this->userId,
            $this->periodeId
        );

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Comments marked as read for this user.'
        ]);
    }

    public function getAllUnreadCounts()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => 'error',
                'message' => 'Forbidden'
            ]);
        }

        if (!$this->userKodebpr || !$this->userId || !$this->periodeId) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Missing data'
            ]);
        }

        $faktorIds = range(105, 115);
        $counts = [];

        foreach ($faktorIds as $faktorId) {
            $count = $this->commentReadsModel->countUnreadCommentsForUserByFactor(
                $faktorId,
                "LIKUIDITASINHEREN",
                $this->userKodebpr,
                $this->userId,
                $this->periodeId
            );

            if ($count > 0) {
                $counts[$faktorId] = $count;
            }
        }

        return $this->response->setJSON([
            'status' => 'success',
            'counts' => $counts
        ]);
    }

    public function simpanNilai14()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }

        $validation = $this->validate([
            'penilaiankredit' => 'required|in_list[1,2,3,4,5]'
        ]);

        if (!$validation) {
            return redirect()->back()->with('err', 'Pilih tingkat risiko terlebih dahulu');
        }

        if (!$this->userKodebpr || !$this->periodeId) {
            return redirect()->back()->with('error', 'Data user atau periode tidak valid');
        }

        $faktorId = 116;
        $penilaiankredit = $this->request->getPost('penilaiankredit');

        $user = $this->userModel->find($this->userId);

        $penjelasanMapping = [
            '1' => 'Tingkat Risiko Likuiditas Inheren: Sangat Baik (Low)',
            '2' => 'Tingkat Risiko Likuiditas Inheren: Baik (Low to Moderate)',
            '3' => 'Tingkat Risiko Likuiditas Inheren: Cukup (Moderate)',
            '4' => 'Tingkat Risiko Likuiditas Inheren: Kurang Baik (Moderate to High)',
            '5' => 'Tingkat Risiko Likuiditas Inheren: Buruk (High)'
        ];

        $penjelasanpenilaian = $penjelasanMapping[$penilaiankredit] ?? 'N/A';

        try {
            // Cek apakah data sudah ada
            $existingData = $this->nilaiModel
                ->where('faktor1id', $faktorId)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->first();

            if ($existingData) {
                $updateData = [
                    'penilaiankredit' => $penilaiankredit,
                    'penjelasanpenilaian' => $penjelasanpenilaian,
                    'keterangan' => 'Tingkat Risiko Likuiditas Inheren Posisi Sebelumnya',
                    'user_id' => $this->userId,
                    'fullname' => $user['fullname'] ?? 'Unknown',
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                $result = $this->nilaiModel
                    ->where('faktor1id', $faktorId)
                    ->where('kodebpr', $this->userKodebpr)
                    ->where('periode_id', $this->periodeId)
                    ->set($updateData)
                    ->update();

                if ($result) {
                    return redirect()->to(base_url('Likuiditasinheren'))
                        ->with('message', 'Data Tingkat Risiko Likuid Inheren berhasil diupdate');
                } else {
                    throw new \Exception('Gagal mengupdate data');
                }

            } else {
                // DATA BELUM ADA - LAKUKAN INSERT
                $insertData = [
                    'faktor1id' => $faktorId,
                    'penilaiankredit' => $penilaiankredit,
                    'penjelasanpenilaian' => $penjelasanpenilaian,
                    'keterangan' => 'Tingkat Risiko Likuid Inheren Posisi Sebelumnya',
                    'fullname' => $user['fullname'] ?? 'Unknown',
                    'user_id' => $this->userId,
                    'kodebpr' => $this->userKodebpr,
                    'periode_id' => $this->periodeId,
                    'is_approved' => 0,
                    'accdir2' => 0,
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $result = $this->nilaiModel->insert($insertData);

                if ($result) {
                    return redirect()->to(base_url('Likuiditasinheren'))
                        ->with('message', 'Data Tingkat Risiko Likuiditas Inheren berhasil disimpan');
                } else {
                    throw new \Exception('Gagal menyimpan data');
                }
            }

        } catch (\Exception $e) {
            log_message('error', 'Error simpanNilai14: ' . $e->getMessage());
            return redirect()->back()
                ->with('err', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function simpanNilai13()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');
        $faktorId = 115;
        $rataRata = 0; // dihitung ulang di model
        $keterangan = $this->request->getPost('keterangan'); // ← ambil dari form

        $this->nilaiModel->insertOrUpdateRataRata($rataRata, $faktorId, $kodebpr, $periodeId, $keterangan);

        return redirect()->back()->with('message', 'Kesimpulan berhasil disimpan atau diperbarui.');
    }

    private function getNilai13()
    {
        if (!$this->userKodebpr || !$this->periodeId) {
            return null;
        }

        return $this->nilaiModel
            ->where('faktor1id', 115)
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->first();
    }

    public function simpanKesimpulan13()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }

        $validation = $this->validate([
            'keterangan' => 'required'
        ]);

        if (!$validation) {
            return redirect()->back()
                ->with('err', 'Keterangan kesimpulan harus diisi');
        }

        if (!$this->userKodebpr || !$this->periodeId) {
            return redirect()->back()->with('error', 'Data user atau periode tidak valid');
        }

        $faktorId = 115;
        $keterangan = $this->request->getPost('keterangan');
        $user = $this->userModel->find($this->userId);

        try {
            $existingData = $this->nilaiModel
                ->where('faktor1id', $faktorId)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->first();

            if ($existingData) {
                $updateData = [
                    'keterangan' => $keterangan,
                    'user_id' => $this->userId,
                    'fullname' => $user['fullname'] ?? 'Unknown',
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                $result = $this->nilaiModel
                    ->where('faktor1id', $faktorId)
                    ->where('kodebpr', $this->userKodebpr)
                    ->where('periode_id', $this->periodeId)
                    ->set($updateData)
                    ->update();

                if ($result) {
                    return redirect()->to(base_url('Likuiditasinheren'))
                        ->with('message', 'Kesimpulan berhasil diupdate');
                } else {
                    throw new \Exception('Gagal mengupdate kesimpulan');
                }

            } else {
                $rataRata = $this->nilaiModel->hitungRataRata($faktorId, $this->userKodebpr, $this->periodeId);
                $rataRata = ($rataRata - floor($rataRata) >= 0.5) ? ceil($rataRata) : floor($rataRata);

                $penjelasanMapping = [
                    '1' => 'Tingkat Risiko Likuiditas Inheren: Sangat Rendah',
                    '2' => 'Tingkat Risiko Likuiditas Inheren: Rendah',
                    '3' => 'Tingkat Risiko Likuiditas Inheren: Sedang',
                    '4' => 'Tingkat Risiko Likuiditas Inheren: Tinggi',
                    '5' => 'Tingkat Risiko Likuiditas Inheren: Sangat Tinggi'
                ];

                $insertData = [
                    'faktor1id' => $faktorId,
                    'penilaiankredit' => $rataRata,
                    'penjelasanpenilaian' => $penjelasanMapping[$rataRata] ?? 'N/A',
                    'keterangan' => $keterangan,
                    'fullname' => $user['fullname'] ?? 'Unknown',
                    'user_id' => $this->userId,
                    'kodebpr' => $this->userKodebpr,
                    'periode_id' => $this->periodeId,
                    'is_approved' => 0,
                    'accdir2' => 0,
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $result = $this->nilaiModel->insert($insertData);

                if ($result) {
                    return redirect()->to(base_url('Likuiditasinheren'))
                        ->with('message', 'Kesimpulan berhasil disimpan');
                } else {
                    throw new \Exception('Gagal menyimpan kesimpulan');
                }
            }

        } catch (\Exception $e) {
            log_message('error', 'Error simpan kesimpulan13: ' . $e->getMessage());
            return redirect()->back()
                ->with('err', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function getNilai14()
    {
        if (!$this->userKodebpr || !$this->periodeId) {
            return null;
        }

        return $this->nilaiModel
            ->where('faktor1id', 116)
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->first();
    }

    public function saveKomentar()
    {
        $data = [
            'faktor1id' => $this->request->getPost('faktor_id'),
            'kodebpr' => $this->request->getPost('kodebpr'),
            'komentar' => $this->request->getPost('komentar'),
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'user_id' => $this->userId
        ];

        $this->komentarModel->insert($data);
        return $this->response->setJSON(['status' => 'comment_saved']);
    }

    private function calculateAndSaveCategoryAverage($categoryFaktorId, $childrenFaktorIds, $kodebpr, $periodeId)
    {
        $childValues = [];
        foreach ($childrenFaktorIds as $childId) {
            $childData = $this->nilaiModel
                ->where('faktor1id', $childId)
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->first();

            if ($childData && !empty($childData['penilaiankredit'])) {
                $childValues[] = (int) $childData['penilaiankredit'];
            }
        }

        if (empty($childValues)) {
            return null;
        }

        $average = array_sum($childValues) / count($childValues);
        $roundedAverage = round($average);

        $user = $this->userModel->find($this->userId);

        $penilaianConfig = $this->getPenilaianConfig();
        $autoKeterangan = '';

        if (isset($penilaianConfig[$categoryFaktorId]['descriptions'][$roundedAverage])) {
            $autoKeterangan = $penilaianConfig[$categoryFaktorId]['descriptions'][$roundedAverage];
        }

        $existingCategory = $this->nilaiModel
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
            'updated_at' => date('Y-m-d H:i:s')
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

    public function exporttxtrisikolikuiditas()
    {
        // Authentication check
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        // Parameters
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        // Profil risiko
        $profilResiko = $this->showprofilresikoModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        // Periode detail
        $periodeDetail = $this->periodeModel->find($this->periodeId);
        $modalinti = $periodeDetail['modalinti'];
        $totalaset = $periodeDetail['totalaset'];
        $kantorcabang = $periodeDetail['kantorcabang'];
        $atmdebit = $periodeDetail['atmdebit'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];
        $kategori = $periodeDetail['kategori'];

        $tanggal = new \DateTime($profilResiko['tanggal']);
        $titleDate = $tanggal->format('Ymd');
        $exportDate = $tanggal->format('Y-m-d');

        // Fetch data
        $data_risikolikuiditas = $this->nilaiModel
            ->getDataByKodebprAndPeriode($kodebpr, $periodeId);

        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        // BPR Info
        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'] ?? '';
            $kodejenis = $infobpr['kodejenis'] ?? '';
        }

        // Sanitizer
        function sanitizeTxtLikInheren($text)
        {
            $text = str_replace(["\r", "\n"], ' ', $text);
            $text = str_replace(
                ['“', '”', '‘', '’', '%', '|'],
                ['"', '"', "'", "'", ' persen ', ' '],
                $text
            );
            $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
            return trim(preg_replace('/\s+/', ' ', $text));
        }

        // Mapping faktor → kode (WAJIB LENGKAP)
        $kodeMap = [
            105 => '4210',
            106 => '4211',
            107 => '4212',
            108 => '4213',
            109 => '4214',
            110 => '4215',
            111 => '4220',
            112 => '4221',
            113 => '4222',
            114 => '4299', // wajib muncul walau kosong
            115 => '4292',
        ];

        // Index data by faktor1id
        $indexedData = [];
        foreach ($data_risikolikuiditas as $row) {
            if (isset($row['faktor1id'])) {
                $indexedData[$row['faktor1id']] = $row;
            }
        }

        $output = '';

        // HEADER
        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|PRBPRKS|0401|{$modalinti}|{$totalaset}|{$kantorcabang}|{$atmdebit}|{$kategori}||\r\n";

        // DETAIL — loop berdasarkan kodeMap
        foreach ($kodeMap as $faktorId => $kode) {

            if (isset($indexedData[$faktorId])) {
                $row = $indexedData[$faktorId];

                $rasio = sanitizeTxtLikInheren($row['rasiokredit'] ?? '');
                $penilaiankredit = sanitizeTxtLikInheren($row['penilaiankredit'] ?? '');
                $keterangan = sanitizeTxtLikInheren($row['keterangan'] ?? '');
            } else {
                // data tidak diisi user
                $rasio = '';
                $penilaiankredit = '';
                $keterangan = '';
            }

            $output .= "D01|{$kode}|{$rasio}|{$penilaiankredit}|{$keterangan}\r\n";
        }

        // Filename
        $filename = "PRBPRKS-0401-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        // Response
        $response = service('response');
        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader(
            'Content-Disposition',
            'attachment; filename="' . $filename . '"'
        );

        return $response->setBody($output);
    }
}