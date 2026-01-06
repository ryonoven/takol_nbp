<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_paramprofilrisiko;
use App\Models\M_user;
use App\Models\M_profilrisikocomments;
use App\Models\M_reputasiinheren;
use App\Models\M_infobpr;
use App\Models\M_periodeprofilresiko;
use App\Models\M_profilrisikocommentsread;
use App\Models\M_showprofilresiko;
use Myth\Auth\Config\Services as AuthServices;

class Reputasiinheren extends Controller
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
    protected $userGroups = [];

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');

        $this->db = \Config\Database::connect();

        $this->paramprofilrisikoModel = new M_paramprofilrisiko();
        $this->periodeModel = new M_periodeprofilresiko();
        $this->userModel = new M_user();
        $this->komentarModel = new M_profilrisikocomments();
        $this->nilaiModel = new M_reputasiinheren();
        $this->infobprModel = new M_infobpr();
        $this->commentReadsModel = new M_profilrisikocommentsread();
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
            'faktor1id' => 148,
            'kodebpr' => $this->userKodebpr,
            'periode_id' => $this->periodeId
        ])->first();

        $nilai14 = $this->nilaiModel->where([
            'faktor1id' => 149,
            'kodebpr' => $this->userKodebpr,
            'periode_id' => $this->periodeId
        ])->first();

        //     $approvalData = $this->nilaiModel
        //         ->select('
        //     COUNT(CASE WHEN penilaiankredit IS NOT NULL THEN 1 END) as filled_count,
        //     SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as approved_count
        // ')
        //         ->where('kodebpr', $this->userKodebpr)
        //         ->where('periode_id', $this->periodeId)
        //         ->whereIn('faktor1id', range(138, 148))
        //         ->first();

        //     $allFilled = ($approvalData['filled_count'] == 11); // 138-148 = 11
        //     $allApproved = ($allFilled && $approvalData['approved_count'] == 11);
        //     $canApprove = $allFilled;

        $requiredFaktor = array_merge(range(138, 146), [148]);
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

        $user = $this->userModel->find($this->userId);

        $data = [
            'judul' => 'Penilaian Risiko Reputasi Inheren',
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
            . view('risikoreputasi/index', $data)
            . view('templates/v_footer');
    }

    private function checkCanApprove($nilaiLookup)
    {
        $requiredFaktorIds = range(138, 147);

        foreach ($requiredFaktorIds as $faktorId) {
            if (empty($nilaiLookup[$faktorId])) {
                return false;
            }
        }

        return true;
    }

    private function checkAccdekomApproved($nilaiLookup)
    {
        for ($faktorId = 138; $faktorId <= 147; $faktorId++) {
            if (!isset($nilaiLookup[$faktorId]) || $nilaiLookup[$faktorId]['is_approved'] != 1) {
                return false;
            }
        }
        return true;
    }

    private function checkAllApproved($nilaiLookup)
    {
        $requiredFaktorIds = range(138, 148);
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

        $nilaiData = $this->db->table('reputasiinheren as r')
            ->select('r.*, u.fullname')
            ->join('users as u', 'u.id = r.user_id', 'left')
            ->where('r.kodebpr', $this->userKodebpr)
            ->where('r.periode_id', $this->periodeId)
            ->whereIn('r.faktor1id', range(138, 147))
            ->get()
            ->getResultArray();

        $nilaiLookup = [];
        foreach ($nilaiData as $nilai) {
            $nilaiLookup[$nilai['faktor1id']] = $nilai;
        }

        $structure = [
            [
                'id' => 138,
                'title' => 'Pilar Kompleksitas bisnis dan kelembagaan',
                'type' => 'category',
                'faktor_id' => 138,
                'children' => [
                    ['id' => 139, 'title' => 'Skala usaha dan struktur organisasi', 'faktor_id' => 139],
                    ['id' => 140, 'title' => 'Jaringan kantor, Rentang kendali dan lokasi kantor cabang', 'faktor_id' => 140]
                ]
            ],
            [
                'id' => 141,
                'title' => 'Frekuensi dan signifikansi pengaduan nasabah',
                'type' => 'single',
                'faktor_id' => 141
            ],
            [
                'id' => 142,
                'title' => 'Administrasi dan tindak lanjut pengaduan nasabah',
                'type' => 'category',
                'faktor_id' => 142,
                'children' => [
                    [
                        'id' => 143,
                        'title' => 'Signifikansi dan materialitas pengaduan nasabah',
                        'faktor_id' => 143
                    ]
                ]
            ],
            [
                'id' => 144,
                'title' => 'Pilar Pelanggaran etika bisnis',
                'type' => 'category',
                'faktor_id' => 144,
                'children' => [
                    ['id' => 145, 'title' => 'Transparansi informasi keuangan', 'faktor_id' => 145],
                    ['id' => 146, 'title' => 'Transparansi produk dan layanan BPR', 'faktor_id' => 146]
                ]
            ],
            [
                'id' => 147,
                'title' => 'Lainnya',
                'type' => 'single',
                'faktor_id' => 147
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
            138 => [
                'descriptions' => [
                    1 => 'Parameter Pengaruh reputasi pihak yang berasosiasi dengan BPR berada pada tingkat risiko yang Sangat Rendah',
                    2 => 'Parameter Pengaruh reputasi pihak yang berasosiasi dengan BPR berada pada tingkat risiko yang ',
                    3 => 'Parameter Pengaruh reputasi pihak yang berasosiasi dengan BPR berada pada tingkat risiko yang Sedang',
                    4 => 'Parameter Pengaruh reputasi pihak yang berasosiasi dengan BPR berada pada tingkat risiko yang Tinggi',
                    5 => 'Parameter Pengaruh reputasi pihak yang berasosiasi dengan BPR berada pada tingkat risiko yang Sangat Tinggi'
                ],
                'catatan' => 'Peniliaian Risiko Reputasi Inheren'
            ],

            139 => [
                'descriptions' => [
                    1 => 'Tidak terdapat pemberitaan negatif mengenai BPR termasuk anggota Direksi dan Dewan Komisaris, pemegang saham, dan perusahaan terkait BPR, di media massa (cetak dan elektronik) dan media lainnya yang dapat diakses oleh masyarakat',
                    2 => 'Terdapat pemberitaan negatif mengenai BPR termasuk anggota Direksi dan Dewan Komisaris, pemegang saham, dan perusahaan terkait BPR, di media massa (cetak dan elektronik) dan media lainnya yang dapat diakses oleh masyarakat, namun skala pengaruhnya tidak material dan dapat dimitigasi dengan baik.',
                    3 => 'Terdapat pemberitaan negatif mengenai BPR termasuk anggota Direksi dan Dewan Komisaris, pemegang saham, dan perusahaan terkait  BPR, di media massa (cetak dan elektronik) dan media lainnya yang dapat diakses oleh masyarakat, dengan skala pengaruh cukup material terhadap kinerja BPR namun masih dapat dikendalikan.',
                    4 => 'Terdapat pemberitaan negatif mengenai BPR termasuk anggota Direksi dan Dewan Komisaris, pemegang saham, dan perusahaan terkait  BPR, di media massa (cetak dan elektronik) dan media lainnya yang dapat diakses oleh masyarakat, dengan skala pengaruh yang material terhadap kinerja BPR dan memerlukan perhatian khusus.',
                    5 => 'Terdapat pemberitaan negatif mengenai BPR termasuk anggota Direksi dan Dewan Komisaris, pemegang saham, dan perusahaan terkait  BPR, di media massa (cetak dan elektronik) dan media lainnya yang dapat diakses oleh masyarakat, dengan skala pengaruh yang sangat material terhadap kinerja BPR, sehingga memerlukan tindak lanjut dengan segera.'
                ],
                'catatan' => 'Peniliaian Risiko Reputasi Inheren'
            ],

            140 => [
                'descriptions' => [
                    1 => 'Tidak terdapat kejadian reputasi.',
                    2 => '• Terdapat kejadian reputasi dengan frekuensi yang rendah; namun <br> 
                    • Tidak berpengaruh pada reputasi BPR',
                    3 => '• Terdapat kejadian reputasi dengan frekuensi yang rendah; dan <br>
                    • Berpengaruh cukup material pada reputasi BPR relatif terhadap ukuran dan skala usaha BPR. atau <br>
                    • Terdapat kejadian reputasi dengan frekuensi cukup tinggi; namun • tidak berpengaruh pada reputasi BPR.',
                    4 => '• Terdapat kejadian reputasi dengan frekuensi yang cukup tinggi; dan <br>
                    • Berpengaruh material pada reputasi BPR relatif terhadap ukuran dan skala usaha BPR. atau <br>
                    • Terdapat kejadian reputasi dengan frekuensi yang sangat tinggi; namun <br>
                    • Tidak seluruhnya berpengaruh material pada reputasi BPR relatif terhadap ukuran dan skala usaha BPR.',
                    5 => '• Terdapat kejadian reputasi dengan frekuensi yang sangat tinggi; dan <br>
                    • Berpengaruh sangat material pada reputasi BPR relatif terhadap ukuran dan skala usaha BPR.'
                ],
                'catatan' => 'Peniliaian Risiko Reputasi Inheren'
            ],

            141 => [
                'descriptions' => [
                    1 => 'Parameter Frekuensi dan signifikansi pengaduan nasabah berada pada tingkat risiko yang Sangat Rendah',
                    2 => 'Parameter Frekuensi dan signifikansi pengaduan nasabah berada pada tingkat risiko yang Rendah',
                    3 => 'Parameter Frekuensi dan signifikansi pengaduan nasabah berada pada tingkat risiko yang Sedang',
                    4 => 'Parameter Frekuensi dan signifikansi pengaduan nasabah berada pada tingkat risiko yang Tinggi',
                    5 => 'Parameter Frekuensi dan signifikansi pengaduan nasabah berada pada tingkat risiko yang Sangat Tinggi'
                ],
                'catatan' => 'Peniliaian Risiko Reputasi Inheren'
            ],

            142 => [
                'descriptions' => [
                    1 => '• Pengaduan nasabah diadministrasi kan dengan tertib dan informatif (ada, lengkap, rutin); dan/atau <br>
                    • Seluruh pengaduan telah diselesaikan',
                    2 => '• Pengaduan nasabah diadministrasi kan dengan cukup tertib dan informatif (sebagian besar ada, sebagian besar lengkap, sebagian besar rutin); dan/atau <br>
                    • Sebagian besar pengaduan telah diselesaikan.',
                    3 => '• Pengaduan nasabah diadministrasi kan dengan cukup tertib dan informatif (sebagian besar ada, sebagian besar lengkap, sebagian besar rutin); dan/atau <br>
                    • Sebagian kecil pengaduan telah diselesaikan.',
                    4 => '• Pengaduan nasabah diadministrasi kan dengan kurang tertib dan informatif (sebagian kecil ada, sebagian kecil lengkap, sebagian kecil rutin); dan/atau <br>
                    • Sebagian kecil pengaduan telah diselesaikan.',
                    5 => 'Tidak terdapatadministrasi mengenai pengaduan nasabah dan/atau seluruhnya tidak diselesaikan.'
                ],
                'catatan' => 'Penilaian parameter risiko reputasi inheren'
            ],

            143 => [
                'descriptions' => [
                    1 => 'Frekuensi pengaduan nasabah sangat minimal dan sangat tidak material.',
                    2 => 'Frekuensi pengaduan nasabah minimal dan tidak material.',
                    3 => 'Frekuensi pengaduan nasabah cukup tinggi dan cukup material.',
                    4 => 'Frekuensi pengaduan nasabah tinggi dan material',
                    5 => 'Frekuensi pengaduan nasabah sangat tinggi serta sangat material dan/atau disebabkan penyimpangan ketentuan perbankan.'
                ],
                'catatan' => 'Penilaian parameter risiko reputasi inheren'
            ],

            144 => [
                'descriptions' => [
                    1 => 'Parameter Pelanggaran etika bisnis berada pada tingkat risiko yang Sangat Rendah',
                    2 => 'Parameter Pelanggaran etika bisnis berada pada tingkat risiko yang Rendah',
                    3 => 'Parameter Pelanggaran etika bisnis berada pada tingkat risiko yang Sedang',
                    4 => 'Parameter Pelanggaran etika bisnis berada pada tingkat risiko yang Tinggi',
                    5 => 'Parameter Pelanggaran etika bisnis berada pada tingkat risiko yang Sangat Tinggi'
                ],
                'catatan' => 'Peniliaian Risiko Reputasi Inheren'
            ],

            145 => [
                'descriptions' => [
                    1 => 'Laporan dan informasi keuangan yang disampaikan BPR kepada seluruh pihak yang memiliki kepentingan dengan BPR lengkap, akurat, kini, dan utuh sesuai dengan ketentuan yang berlaku. ',
                    2 => "Laporan dan informasi keuangan yang disampaikan BPR kepada seluruh pihak yang memiliki kepentingan dengan BPR lengkap, akurat, kini, namun tidak utuh.",
                    3 => 'Laporan dan informasi keuangan yang disampaikan BPR kepada seluruh pihak yang memiliki kepentingan dengan BPR kurang lengkap dan masih terdapat informasi yang disampaikan tidak sesuai dengan ketentuan yang berlaku, namun tidak mengakibatkan penilaian yang tidak sesuai dengan kondisi keuangan yang sebenarnya.',
                    4 => 'Laporan dan informasi keuangan yang disampaikan BPR kepada seluruh pihak yang memiliki kepentingan dengan BPR kurang lengkap dan masih terdapat informasi yang disampaikan tidak sesuai dengan ketentuan yang berlaku, serta mengakibatkan penilaian yang tidak sesuai dengan kondisi keuangan yang sebenarnya.',
                    5 => 'BPR tidak menyampaikan informasi dan laporan keuangan sesuai dengan ketentuan yang berlaku, dan mengakibatkan tidak diketahuinya kondisi keuangan BPR yang sebenarnya.'
                ],
                'catatan' => 'Penilaian parameter risiko reputasi inheren'
            ],

            146 => [
                'descriptions' => [
                    1 => 'Produk dan layanan BPR memiliki skema sederhana, serta tidak membutuhkan pemahaman khusus nasabah atau mitra bisnis BPR, dan BPR memberikan informasi terkait spesifikasi produk dan layanan BPR kepada nasabah atau mitra bisnis BPR secara jelas dan lengkap.  ',
                    2 => "Produk dan layanan BPR memiliki skema kompleks, serta membutuhkan pemahaman khusus nasabah atau mitra bisnis BPR, dan BPR memberikan informasi terkait spesifikasi produk dan layanan BPR kepada nasabah atau mitra bisnis BPR secara jelas dan lengkap.",
                    3 => 'Terdapat produk dan layanan BPR yang memiliki skema kompleks, serta membutuhkan pemahaman khusus nasabah atau mitra bisnis BPR, namun BPR belum sepenuhnya memberikan informasi terkait spesifikasi produk dan layanan BPR kepada nasabah atau mitra bisnis BPR secara jelas dan lengkap.',
                    4 => 'Terdapat produk dan layanan BPR yang memiliki skema kompleks, serta membutuhkan pemahaman khusus nasabah atau mitra bisnis BPR, namun BPR tidak memberikan informasi terkait spesifikasi produk dan layanan BPR kepada nasabah atau mitra bisnis BPR secara jelas dan lengkap.',
                    5 => 'Terdapat produk dan layanan BPR yang memiliki skema kompleks, serta membutuhkan pemahaman khusus nasabah atau mitra bisnis BPR, namun BPR memberikan informasi yang tidak benar kepada nasabah atau mitra bisnis BPR terkait spesifikasi produk dan layanan BPR.'
                ],
                'catatan' => 'Penilaian parameter risiko reputasi inheren'
            ],

            147 => [
                'descriptions' => [
                    1 => 'Parameter Penilaian Risiko Reputasi Inheren Lainnya berada pada tingkat risiko yang Sangat Rendah',
                    2 => 'Parameter Penilaian Risiko Reputasi Inheren Lainnya berada pada tingkat risiko yang Rendah',
                    3 => 'Parameter Penilaian Risiko Reputasi Inheren Lainnya berada pada tingkat risiko yang Sedang',
                    4 => 'Parameter Penilaian Risiko Reputasi Inheren Lainnya berada pada tingkat risiko yang Tinggi',
                    5 => 'Parameter Penilaian Risiko Reputasi Inheren Lainnya berada pada tingkat risiko yang Sangat Tinggi'
                ],
                'catatan' => 'Peniliaian Risiko Reputasi Inheren'
            ],

        ];
    }

    public function cekKomentarBaru()
    {
        $subkategori = 'REPUTASIINHEREN';
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
            'REPUTASIINHEREN',
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
            $this->db->table('reputasiinheren')
                ->where('faktor1id', $faktor1id)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update($data);

            $categoryMapping = [
                138 => [139, 140],
                142 => [143],
                144 => [145, 146],
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

            if (in_array($faktor1id, [138, 139, 140, 141, 142, 143, 144, 145, 146, 147])) {
                $rataRata = $this->nilaiModel->hitungRataRata(148, $this->userKodebpr, $this->periodeId);
                $this->nilaiModel->insertOrUpdateRataRata($rataRata, 148, $this->userKodebpr, $this->periodeId);
            }

            $this->db->table('reputasiinheren')
                ->where('faktor1id', 148)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update(['accdir2' => 0, 'is_approved' => 0]);

            $nilai13 = $this->nilaiModel->where([
                'faktor1id' => 148,
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

        $data = [
            'keterangan' => $keterangan,
            'user_id' => $this->userId,
            'updated_at' => date('Y-m-d H:i:s'),
            'is_approved' => 0,
            'accdir2' => 0
        ];

        try {
            $this->db->table('reputasiinheren')
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
            $result = $this->db->table('reputasiinheren')
                ->where('faktor1id', $faktor1id)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update($data);

            if ($result) {
                $this->db->table('reputasiinheren')
                    ->where('faktor1id', 148)
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
            $this->db->table('reputasiinheren')->insert($data);

            $categoryMapping = [
                138 => [139, 140],
                142 => [143],
                144 => [145, 146]
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

            if (in_array($faktorId, [138, 139, 140, 141, 142, 143, 144, 145, 146, 147])) {
                $rataRata = $this->nilaiModel->hitungRataRata(148, $this->userKodebpr, $this->periodeId);
                $this->nilaiModel->insertOrUpdateRataRata($rataRata, 148, $this->userKodebpr, $this->periodeId);
            }

            $nilai13 = $this->nilaiModel->where([
                'faktor1id' => 148,
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

    public function tambahKomentar()
    {
        $isAjax = $this->request->isAJAX();

        if (!$isAjax && !isset($_POST['tambahKomentar'])) {
            return redirect()->to(base_url('Reputasiinheren'));
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
                'subkategori' => "REPUTASIINHEREN",
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
                        'REPUTASIINHEREN',
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

                return redirect()->to(base_url('Reputasiinheren') . '?modal_komentar=' . $faktorId)
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

        return redirect()->to(base_url('Reputasiinheren'));
    }

    public function approve($idNilai = null)
    {
        if ($idNilai === null) {
            return redirect()->back()->with('err', 'ID tidak valid');
        }

        $this->db->table('reputasiinheren')
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

        // Faktor wajib: 138–148
        $requiredFaktor = array_merge(range(138, 146), [148]);
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
            $this->db->table('reputasiinheren')
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

        try {
            $this->db->table('reputasiinheren')
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->whereIn('faktor1id', range(138, 148))
                ->update([
                    'is_approved' => 0,
                    'approved_by' => $this->userId,
                    'approved_at' => date('Y-m-d H:i:s')
                ]);

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

        $this->db->table('reputasiinheren')
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
            return redirect()->to('/Reputasiinheren')->with('error', 'Data tidak lengkap');
        }

        $nilaiFaktor = $this->nilaiModel
            ->where('faktor1id', $faktor1id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if (!$nilaiFaktor) {
            return redirect()->to('/Reputasiinheren')->with('error', 'Data tidak ditemukan');
        }

        $dataUpdate = [
            'accdir2' => 0,
            'accdir2_by' => $this->userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->nilaiModel->update($nilaiFaktor['id'], $dataUpdate)) {
            return redirect()->to('/Reputasiinheren')->with('message', 'Persetujuan dibatalkan');
        }

        return redirect()->to('/Reputasiinheren')->with('error', 'Gagal memperbarui data');
    }

    public function recalculateCategoryAverage()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $faktorId = $this->request->getPost('faktor_id');

        $categoryMapping = [
            138 => [139, 140],
            142 => [143],
            144 => [145, 146],
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

            $rataRata = $this->nilaiModel->hitungRataRata(148, $this->userKodebpr, $this->periodeId);
            $this->nilaiModel->insertOrUpdateRataRata($rataRata, 148, $this->userKodebpr, $this->periodeId);

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
            "REPUTASIINHEREN",
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
            "REPUTASIINHEREN",
            $this->userKodebpr,
            $this->userId,
            $this->periodeId
        );

        log_message('info', "User {$this->userId} marked {$marked} comments as read for factor {$faktorId}");

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Comments marked as read',
            'marked_count' => $marked
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

        $faktorIds = range(138, 148);
        $counts = [];

        foreach ($faktorIds as $faktorId) {
            $count = $this->commentReadsModel->countUnreadCommentsForUserByFactor(
                $faktorId,
                "REPUTASIINHEREN",
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

        $faktorId = 149;
        $penilaiankredit = $this->request->getPost('penilaiankredit');

        $user = $this->userModel->find($this->userId);

        $penjelasanMapping = [
            '1' => 'Tingkat Risiko Reputasi Inheren: Sangat Rendah',
            '2' => 'Tingkat Risiko Reputasi Inheren: Rendah',
            '3' => 'Tingkat Risiko Reputasi Inheren: Sedang',
            '4' => 'Tingkat Risiko Reputasi Inheren: Tinggi',
            '5' => 'Tingkat Risiko Reputasi Inheren: Sangat Tinggi'
        ];

        $penjelasanpenilaian = $penjelasanMapping[$penilaiankredit] ?? 'N/A';

        try {
            $existingData = $this->nilaiModel
                ->where('faktor1id', $faktorId)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->first();

            if ($existingData) {
                $updateData = [
                    'penilaiankredit' => $penilaiankredit,
                    'penjelasanpenilaian' => $penjelasanpenilaian,
                    'keterangan' => 'Tingkat Risiko Reputasi Inheren Posisi Sebelumnya',
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
                    return redirect()->to(base_url('Reputasiinheren'))
                        ->with('message', 'Data Tingkat Risiko Reputasi Inheren berhasil diupdate');
                } else {
                    throw new \Exception('Gagal mengupdate data');
                }

            } else {
                $insertData = [
                    'faktor1id' => $faktorId,
                    'penilaiankredit' => $penilaiankredit,
                    'penjelasanpenilaian' => $penjelasanpenilaian,
                    'keterangan' => 'Tingkat Risiko Reputasi Inheren Posisi Sebelumnya',
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
                    return redirect()->to(base_url('Reputasiinheren'))
                        ->with('message', 'Data Tingkat Risiko Reputasi Inheren berhasil disimpan');
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
        $faktorId = 148;
        $rataRata = 0;
        $keterangan = $this->request->getPost('keterangan');

        $this->nilaiModel->insertOrUpdateRataRata($rataRata, $faktorId, $kodebpr, $periodeId, $keterangan);

        return redirect()->back()->with('message', 'Kesimpulan berhasil disimpan atau diperbarui.');
    }

    private function getNilai13()
    {
        if (!$this->userKodebpr || !$this->periodeId) {
            return null;
        }

        return $this->nilaiModel
            ->where('faktor1id', 148)
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

        $faktorId = 148;
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
                    return redirect()->to(base_url('Reputasiinheren'))
                        ->with('message', 'Kesimpulan berhasil diupdate');
                } else {
                    throw new \Exception('Gagal mengupdate kesimpulan');
                }

            } else {
                $rataRata = $this->nilaiModel->hitungRataRata($faktorId, $this->userKodebpr, $this->periodeId);
                $rataRata = ($rataRata - floor($rataRata) >= 0.5) ? ceil($rataRata) : floor($rataRata);

                $penjelasanMapping = [
                    '1' => 'Tingkat Risiko Reputasi Inheren: Sangat Rendah',
                    '2' => 'Tingkat Risiko Reputasi Inheren: Rendah',
                    '3' => 'Tingkat Risiko Reputasi Inheren: Sedang',
                    '4' => 'Tingkat Risiko Reputasi Inheren: Tinggi',
                    '5' => 'Tingkat Risiko Reputasi Inheren: Sangat Tinggi'
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
                    return redirect()->to(base_url('Reputasiinheren'))
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
            ->where('faktor1id', 149)
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

    private function getHierarchicalFactors($kodebpr, $periodeId)
    {
        $structure = [
            [
                'id' => 138,
                'title' => 'Pilar Kompleksitas bisnis dan kelembagaan',
                'type' => 'category',
                'faktor_id' => 138,
                'faktor_ids' => [139, 140],
                'description' => 'Kompleksitas bisnis dan kelembagaan',
                'children' => [
                    [
                        'id' => 139,
                        'title' => 'Skala usaha dan struktur organisasi',
                        'type' => 'parameter',
                        'faktor_id' => 139,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 140,
                        'title' => 'Jaringan kantor, Rentang kendali dan lokasi kantor cabang',
                        'type' => 'parameter',
                        'faktor_id' => 140,
                        'previous_periode_faktor_id' => null
                    ]
                ]
            ],
            [
                'id' => 141,
                'title' => 'Frekuensi dan signifikansi pengaduan nasabah',
                'type' => 'single',
                'faktor_id' => 141,
                'description' => 'Frekuensi dan signifikansi pengaduan nasabah'
            ],
            [
                'id' => 142,
                'title' => 'Administrasi dan tindak lanjut pengaduan nasabah',
                'type' => 'category',
                'faktor_id' => 142,
                'faktor_ids' => [143],
                'description' => 'Administrasi dan tindak lanjut pengaduan nasabah',
                'children' => [
                    [
                        'id' => 143,
                        'title' => 'Signifikansi dan materialitas pengaduan nasabah',
                        'type' => 'parameter',
                        'faktor_id' => 143,
                        'previous_periode_faktor_id' => null
                    ]
                ]
            ],
            [
                'id' => 144,
                'title' => 'Pilar Pelanggaran etika bisnis',
                'type' => 'category',
                'faktor_id' => 144,
                'faktor_ids' => [145, 146],
                'description' => 'Pilar Pelanggaran etika bisnis',
                'children' => [
                    [
                        'id' => 145,
                        'title' => 'Transparansi informasi keuangan',
                        'type' => 'parameter',
                        'faktor_id' => 145,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 146,
                        'title' => 'Transparansi produk dan layanan BPR',
                        'type' => 'parameter',
                        'faktor_id' => 146,
                        'previous_periode_faktor_id' => null
                    ]
                ]
            ],
            [
                'id' => 147,
                'title' => 'Lainnya',
                'type' => 'single',
                'faktor_id' => 147,
                'description' => 'Lainnya'
            ]
        ];

        foreach ($structure as &$item) {
            if ($item['type'] === 'category') {
                $categoryData = $this->nilaiModel->getByFaktor($item['faktor_id'], $periodeId, $kodebpr);

                foreach ($item['children'] as &$child) {
                    $nilai = $this->nilaiModel->getByFaktor($child['faktor_id'], $periodeId, $kodebpr);
                    $child['nilai'] = $nilai['penilaiankredit'] ?? null;
                    $child['keterangan'] = $nilai['keterangan'] ?? null;
                    $child['is_approved'] = $nilai['is_approved'] ?? 0;
                    $child['rasiokredit'] = $nilai['rasiokredit'] ?? '';
                }

                $item['nilai'] = $categoryData['penilaiankredit'] ?? null;
                $item['keterangan'] = $categoryData['keterangan'] ?? null;
                $item['is_approved'] = $categoryData['is_approved'] ?? 0;

            } else if ($item['type'] === 'single') {
                $nilai = $this->nilaiModel->getByFaktor($item['faktor_id'], $periodeId, $kodebpr);
                $item['nilai'] = $nilai['penilaiankredit'] ?? null;
                $item['keterangan'] = $nilai['keterangan'] ?? null;
                $item['is_approved'] = $nilai['is_approved'] ?? 0;
            }
        }

        return $structure;
    }

    private function recalculateCategoryAverages($changedFaktorId, $kodebpr, $periodeId)
    {
        $categoryMapping = [
            138 => [139, 140],
            142 => [143],
            144 => [145, 146]
        ];

        foreach ($categoryMapping as $categoryId => $childrenIds) {
            if (in_array($changedFaktorId, $childrenIds)) {
                $this->calculateAndSaveCategoryAverage($categoryId, $childrenIds, $kodebpr, $periodeId);
                break;
            }
        }
    }

    public function exporttxtrisikoreputasiinheren()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        $profilResiko = $this->showprofilresikoModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

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

        $data_risikoreputasi = $this->nilaiModel
            ->getDataByKodebprAndPeriode($kodebpr, $periodeId);

        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'] ?? '';
            $kodejenis = $infobpr['kodejenis'] ?? '';
        }

        function sanitizeTxtRepInheren($text)
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

        $kodeMap = [
            138 => '5210',
            139 => '5211',
            140 => '5212',
            141 => '5220',
            142 => '5221',
            143 => '5222',
            144 => '5230',
            145 => '5231',
            146 => '5232',
            147 => '5299', // kondisional
            148 => '5292',
        ];

        // Index data
        $indexedData = [];
        foreach ($data_risikoreputasi as $row) {
            if (isset($row['faktor1id'])) {
                $indexedData[$row['faktor1id']] = $row;
            }
        }

        // Cek apakah ADA data 138–146
        $hasMainData = false;
        foreach (range(138, 146) as $fid) {
            if (isset($indexedData[$fid])) {
                $hasMainData = true;
                break;
            }
        }

        $output = '';
        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|PRBPRKS|0501|{$modalinti}|{$totalaset}|{$kantorcabang}|{$atmdebit}|{$kategori}||\r\n";

        // Jika TIDAK ADA data utama → STOP (header saja)
        if (!$hasMainData) {
            return service('response')
                ->setHeader('Content-Type', 'text/plain')
                ->setHeader(
                    'Content-Disposition',
                    'attachment; filename="PRBPRKS-0501-' . $jenispelaporan . '-S-' . $titleDate . '-' . $sandibpr . '-01.txt"'
                )
                ->setBody($output);
        }

        // Generate data 138–146 & 148 (jika ada)
        foreach ($indexedData as $faktorId => $row) {

            if (!isset($kodeMap[$faktorId])) {
                continue;
            }

            if ($faktorId == 147) {
                continue; // 5299 diproses terpisah
            }

            $kode = $kodeMap[$faktorId];
            $penilaiankredit = sanitizeTxtRepInheren($row['penilaiankredit'] ?? '');
            $keterangan = sanitizeTxtRepInheren($row['keterangan'] ?? '');

            $output .= "D01|{$kode}|{$penilaiankredit}|{$keterangan}\r\n";
        }

        // WAJIB generate 5299 jika ada data utama
        $row5299 = $indexedData[147] ?? [];
        $output .= "D01|5299|" .
            sanitizeTxtRepInheren($row5299['penilaiankredit'] ?? '') . "|" .
            sanitizeTxtRepInheren($row5299['keterangan'] ?? '') . "\r\n";

        $filename = "PRBPRKS-0501-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }

}