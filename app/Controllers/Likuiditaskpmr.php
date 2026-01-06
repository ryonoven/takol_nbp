<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_paramprofilrisiko;
use App\Models\M_user;
use App\Models\M_profilrisikocomments;
use App\Models\M_likuiditaskpmr;
use App\Models\M_infobpr;
use App\Models\M_periodeprofilresiko;
use App\Models\M_profilrisikocommentsread;
use App\Models\M_showprofilresiko;
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

    protected $userGroups = [];

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');

        $this->db = \Config\Database::connect();

        $this->paramprofilrisikoModel = new M_paramprofilrisiko();
        $this->periodeModel = new M_periodeprofilresiko();
        $this->userModel = new M_user();
        $this->komentarModel = new M_profilrisikocomments();
        $this->nilaiModel = new M_likuiditaskpmr();
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

        $periodeDetail = $this->periodeModel->getPeriodeDetail($this->periodeId);
        $bprData = $this->infobprModel->getBprByKode($this->userKodebpr);

        $nilai13 = $this->nilaiModel->where([
            'faktor1id' => 135,
            'kodebpr' => $this->userKodebpr,
            'periode_id' => $this->periodeId
        ])->first();

        $nilai14 = $this->nilaiModel->where([
            'faktor1id' => 136,
            'kodebpr' => $this->userKodebpr,
            'periode_id' => $this->periodeId
        ])->first();

        $approvalData = $this->nilaiModel
            ->select('
        COUNT(CASE WHEN penilaiankredit IS NOT NULL THEN 1 END) as filled_count,
        SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as approved_count
    ')
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->whereIn('faktor1id', range(118, 135))
            ->first();

        $allFilled = ($approvalData['filled_count'] == 18); // 118-135 = 18
        $allApproved = ($allFilled && $approvalData['approved_count'] == 18);
        $canApprove = $allFilled;

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

        ];

        return view('templates/v_header', $data)
            . view('templates/v_sidebar')
            . view('templates/v_topbar')
            . view('risikolikuiditas/likuiditaskpmr', $data)
            . view('templates/v_footer');
    }

    private function checkCanApprove($nilaiLookup)
    {
        $requiredFaktorIds = range(118, 134);

        foreach ($requiredFaktorIds as $faktorId) {
            if (empty($nilaiLookup[$faktorId])) {
                return false;
            }
        }

        return true;
    }

    private function checkAccdekomApproved($nilaiLookup)
    {
        for ($faktorId = 118; $faktorId <= 134; $faktorId++) {
            if (!isset($nilaiLookup[$faktorId]) || $nilaiLookup[$faktorId]['is_approved'] != 1) {
                return false;
            }
        }
        return true;
    }

    private function checkAllApproved($nilaiLookup)
    {
        $requiredFaktorIds = range(118, 135);
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

        $nilaiData = $this->db->table('likuiditaskpmr as r')
            ->select('r.*, u.fullname')
            ->join('users as u', 'u.id = r.user_id', 'left')
            ->where('r.kodebpr', $this->userKodebpr)
            ->where('r.periode_id', $this->periodeId)
            ->whereIn('r.faktor1id', range(118, 134))
            ->get()
            ->getResultArray();

        $nilaiLookup = [];
        foreach ($nilaiData as $nilai) {
            $nilaiLookup[$nilai['faktor1id']] = $nilai;
        }

        $structure = [
            [
                'id' => 118,
                'title' => 'Pilar Pengawasan Direksi dan Dewan Komisaris',
                'type' => 'category',
                'faktor_id' => 118,
                'children' => [
                    ['id' => 119, 'title' => 'Apakah Dewan Komisaris telah melakukan persetujuan terhadap kebijakan manajemen risiko likuiditas yang <br> disusun oleh Direksi dan melakukan evaluasi secara berkala?', 'faktor_id' => 119],
                    ['id' => 120, 'title' => 'Apakah Dewan Komisaris telah melakukan evaluasi terhadap pertanggungjawaban Direksi atas pelaksanaan <br> kebijakan manajemen risiko likuiditas secara berkala dan memastikan tindak lanjut hasil evaluasi dimaksud?', 'faktor_id' => 120],
                    ['id' => 121, 'title' => 'Apakah Direksi telah menyusun kebijakan manajemen risiko likuiditas, melaksanakan secara konsisten, <br> dan melakukan pengkinian secara berkala?', 'faktor_id' => 121],
                    ['id' => 122, 'title' => 'Apakah Direksi telah memiliki kemampuan untuk mengambil tindakan yang diperlukan dalam rangka mitigasi <br> risiko likuiditas, dan melakukan komunikasi kebijakan manajemen risiko likuiditas terhadap seluruh jenjang <br> organisasi BPR?', 'faktor_id' => 122],
                    ['id' => 123, 'title' => 'Apakah BPR telah memiliki kecukupan organisasi yang menangani fungsi likuiditas dan fungsi manajemen <br> risiko likuiditas?', 'faktor_id' => 123],
                    ['id' => 124, 'title' => 'Apakah Direksi telah menerapkan kebijakan pengelolaan SDM dalam rangka penerapan Manajemen Risiko <br> likuiditas?', 'faktor_id' => 124]
                ]
            ],
            [
                'id' => 125,
                'title' => 'Pilar Kecukupan Kebijakan, Prosedur, dan Limit',
                'type' => 'category',
                'faktor_id' => 125,
                'children' => [
                    [
                        'id' => 126,
                        'title' => 'Apakah BPR telah memiliki kebijakan manajemen risiko likuiditas yang memadai dan disusun dengan <br> mempertimbangkan visi, misi, skala usaha dan kompleksitas bisnis, serta kecukupan SDM?',
                        'faktor_id' => 126
                    ],
                    [
                        'id' => 127,
                        'title' => 'Apakah BPR: 
                    ● Memiliki prosedur manajemen risiko likuiditas dan penetapan limit risiko likuiditas yang ditetapkan <br> oleh Direksi; 
                    ● Melaksanakan prosedur manajemen risiko likuiditas dan penetapan limit risiko likuiditas secara <br> konsisten untuk seluruh aktivitas; dan 
                    ● Melakukan evaluasi dan pengkinian terhadap prosedur manajemen risiko likuiditas dan penetapan <br> limit risiko likuiditas secara berkala?',
                        'faktor_id' => 127
                    ],
                    [
                        'id' => 128,
                        'title' => 'Apakah BPR telah memiliki kebijakan dan prosedur penerbitan produk dan/atau pelaksanaan aktivitas <br> baru yang mencakup identifikasi dan mitigasi risiko likuiditas sesuai dengan ketentuan?',
                        'faktor_id' => 128
                    ]
                ]
            ],
            [
                'id' => 129,
                'title' => 'Kecukupan Proses dan Sistem Manajemen Informasi',
                'type' => 'category',
                'faktor_id' => 129,
                'children' => [
                    [
                        'id' => 130,
                        'title' => 'Apakah BPR telah melaksanakan proses manajemen risiko likuiditas yang melekat pada kegiatan <br> usaha BPR?',
                        'faktor_id' => 130
                    ],
                    [
                        'id' => 131,
                        'title' => 'Apakah BPR telah memiliki sistem informasi manajemen risiko yang mendukung Direksi dalam <br> pengambilan <br> keputusan terkait risiko  likuiditas serta telah dilaporkan kepada Direksi secara berkala?',
                        'faktor_id' => 131
                    ]
                ]
            ],
            [
                'id' => 132,
                'title' => 'Sistem Pengendalian Internal yang Menyeluruh',
                'type' => 'category',
                'faktor_id' => 132,
                'children' => [
                    ['id' => 133, 'title' => 'Apakah SKAI atau PEAI telah melaksanakan audit secara berkala terhadap penerapan <br> manajemen risiko likuiditas, menyampaikan laporan hasil audit intern, dan memastikan <br> tindaklanjut atas temuan pemeriksaan?', 'faktor_id' => 133],
                    ['id' => 134, 'title' => 'Apakah sistem pengendalian intern terhadap risiko likuiditas telah dilaksanakan oleh <br> seluruh jenjang organisasi BPR?', 'faktor_id' => 134]
                ]
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
            118 => [
                'descriptions' => [
                    1 => 'Parameter Pengawasan Direksi dan Dewan Komisaris berada pada tingkat risiko yang Sangat Rendah',
                    2 => 'Parameter Pengawasan Direksi dan Dewan Komisaris berada pada tingkat risiko yang Rendah',
                    3 => 'Parameter Pengawasan Direksi dan Dewan Komisaris berada pada tingkat risiko yang Sedang',
                    4 => 'Parameter Pengawasan Direksi dan Dewan Komisaris berada pada tingkat risiko yang Tinggi',
                    5 => 'Parameter Pengawasan Direksi dan Dewan Komisaris berada pada tingkat risiko yang Sangat Tinggi'
                ],
                'catatan' => 'Peniliaian Risiko Likuiditas Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            119 => [
                'descriptions' => [
                    1 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko likuiditas <br>
                    • Dewan Komisaris telah melakukan evaluasi terhadap kebijakan Manajemen Risiko likuiditas <br>
                    • Evaluasi dilakukan oleh Dewan Komisaris secara berkala paling sedikit satu kali dalamsatu tahun atausewaktu-waktu dalam hal terdapat perubahan yangmemengaruhi kegiatan usaha BPR secara signifikan; dan <br>
                    • Evaluasi yang dilakukan relevan dengan kebutuhan penyesuaian kebijakan Manajemen Risiko likuiditas. ',
                    2 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko likuiditas; <br>
                    • Dewan Komisaris telah melakukan evaluasi terhadap kebijakan Manajemen Risiko likuiditas; dan <br> 
                    • Evaluasi dilakukan oleh Dewan Komisaris secara berkala paling sedikit satu kali dalam satu tahun atau sewaktu-waktu dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan',
                    3 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko likuiditas; <br>
                    • Dewan Komisaris telah melakukan evaluasi terhadap kebijakan Manajemen Risiko likuiditas; dan <br>
                    • Evaluasi tidak dilakukan oleh Dewan Komisaris secara berkala paling sedikit satu kali dalam satu tahun atau sewaktu-waktu dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan',
                    4 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko likuiditas; dan <br>
                    • Dewan Komisaris tidak melakukan evaluasi terhadap kebijakan Manajemen Risiko likuiditas.',
                    5 => '• Dewan Komisaris tidak memberikan persetujuan terhadap kebijakan Manajemen Risiko likuiditas; dan <br>
                    • Dewan Komisaris tidak melakukan evaluasi terhadap kebijakan Manajemen Risiko likuiditas.'
                ],
                'catatan' => 'Peniliaian Risiko Likuiditas Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            120 => [
                'descriptions' => [
                    1 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko likuiditas oleh Direksi; <br>
                    • Evaluasi oleh Dewan Komisaris dilakukan secara berkala setiap semester atau lebih berdasarkan laporan yang disampaikan Direksi dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan; <br>
                    • Dewan Komisaris telah memastikan tindak lanjut hasil evaluasi dalam setiap periode laporan; dan <br>
                    • Evaluasi yang diberikan relevan dengan pelaksanaan kebijakan Manajemen Risiko likuiditas dalam rangka mendukung perbaikan kinerja BPR.',
                    2 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko likuiditas oleh Direksi; <br>
                    • Evaluasi oleh Dewan Komisaris dilakukan secara berkala setiap semester atau lebih berdasarkan laporan yang disampaikan Direksi dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan; <br>
                    • Dewan Komisaris telah memastikan tindak lanjut hasil evaluasi dalam setiap periode laporan.',
                    3 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko likuiditas oleh Direksi; <br>
                     • Evaluasi oleh Dewan Komisaris dilakukan secara berkala setiap semester atau lebih berdasarkan laporan yang disampaikan Direksi dalam hal terdapat perubahan yangmemengaruhi kegiatan usaha BPR secara signifikan; dan <br> 
                     • Dewan Komisaris tidak memastikan tindak lanjut hasil evaluasi dalam setiap periode laporan.',
                    4 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko likuiditas oleh Direksi; <br> 
                    • Evaluasi oleh Dewan Komisaris tidak dilakukan secara berkala; dan <br>
                    • Dewan Komisaris tidak memastikan tindak lanjut hasil evaluasi dalam setiap periode pelaporan',
                    5 => 'Dewan Komisaris tidak melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko likuiditas oleh Direksi.'
                ],
                'catatan' => 'Peniliaian Risiko Likuiditas Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            121 => [
                'descriptions' => [
                    1 => '• Direksi telah menyusun kebijakan Manajemen Risiko likuiditas; <br>
                    • Menjalankan kegiatan usaha berdasarkan kebijakan Manajemen Risiko likuiditas yang telah ditetapkan; <br>
                    • Direksi melakukan pengkinian terhadap kebijakan Manajemen Risiko likuiditas dalam hal terdapat perubahan ketentuan peraturan perundangundangan, perubahan bisnis, dan hasilevaluasi kebijakan Manajemen Risiko oleh Dewan Komisaris; dan <br>
                    • Kebijakan Manajemen Risiko likuiditas yang dijalankan terbukti memitigasi terjadinya Risiko likuiditas.',
                    2 => '• Direksi telah menyusun kebijakan Manajemen Risiko likuiditas; <br>
                    • Menjalankan kegiatan usaha berdasarkan kebijakan Manajemen Risiko likuiditas yang telah ditetapkan; dan <br>
                    • Direksi melakukan pengkinian terhadap kebijakan Manajemen Risiko likuiditas dalam hal terdapat perubahan ketentuan peraturan perundangundangan, perubahan bisnis, dan hasil evaluasi kebijakan Manajemen Risiko oleh Dewan Komisaris.',
                    3 => '• Direksi telah menyusun kebijakan Manajemen Risiko likuiditas; <br>
                    • Menjalankan kegiatan usaha berdasarkan kebijakan Manajemen Risiko likuiditas yang telah ditetapkan; dan <br>
                    • Direksi tidak melakukan pengkinian terhadap kebijakan Manajemen Risiko likuiditas dalam hal terdapat perubahan ketentuan peraturan perundangundangan, perubahan bisnis, dan hasil evaluasi kebijakan Manajemen Risiko oleh Dewan Komisaris.',
                    4 => '• Direksi telah menyusun kebijakan Manajemen Risiko likuiditas; <br>
                    • Tidak menjalankan kegiatan usaha berdasarkan kebijakan Manajemen Risiko likuiditas yang telah ditetapkan; dan <br>
                    • Direksi tidak melakukan pengkinian terhadap kebijakan Manajemen Risiko likuiditas dalam hal terdapat perubahan ketentuan peraturan perundangundangan, perubahan bisnis, dan hasil evaluasi kebijakan Manajemen Risiko oleh Dewan Komisaris.',
                    5 => 'Direksi tidak menyusun kebijakan Manajemen Risiko likuiditas.'
                ],
                'catatan' => 'Peniliaian Risiko Likuiditas Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            122 => [
                'descriptions' => [
                    1 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko saat menjalankan kebijakan Manajemen Risiko likuiditas; <br>
                    • Direksi mengomunikasi kan kebijakan Manajemen Risiko likuiditas; dan <br>
                    • Seluruh jenjang organisasi BPR mampu memahami kebijakan Manajemen Risiko likuiditas yang diterapkan.',
                    2 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko saat menjalankan kebijakan Manajemen Risiko likuiditas; <br>
                    • Direksi mengomunikasi kan kebijakan Manajemen Risiko likuiditas; dan <br>
                    • Tidak seluruh jenjang organisasi BPR mampu memahami kebijakan Manajemen Risiko likuiditas yang diterapkan namun tidak menimbulkan dampak yang signifikan',
                    3 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko saat menjalankan kebijakan Manajemen Risiko likuiditas; <br>
                    • Direksi mengomunikasi kan kebijakan Manajemen Risiko likuiditas; dan <br>
                    • Tidak seluruh jenjang organisasi BPR mampu memahami kebijakan Manajemen Risiko likuiditas yang diterapkan dan menimbulkan dampak yang signifikan',
                    4 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko saat menjalankan kebijakan Manajemen Risiko likuiditas; <br>
                    • Direksi tidak mengomunikasi kan kebijakan Manajemen Risiko likuiditas; dan <br>
                    • Seluruh jenjang organisasi BPR tidak mampu memahami kebijakan Manajemen Risiko likuiditas yang diterapkan.',
                    5 => '• Direksi tidak mengambil tindakan yangdiperlukan untukmemitigasi Risikosaat menjalankan kebijakan Manajemen Risiko likuiditas; <br>
                    • Direksi tidak mengomunikasi kan kebijakan Manajemen Risiko likuiditas; dan <br>
                    • Seluruh jenjang organisasi BPR tidak mampu memahami kebijakan Manajemen Risiko likuiditas yang diterapkan.'
                ],
                'catatan' => 'Peniliaian Risiko Likuiditas Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            123 => [
                'descriptions' => [
                    1 => '• Memiliki unit kerja yang menangani fungsi likuiditas secara lengkap dan tidak terdapat rangkap jabatan yang dapat menyebabkan tidak terlaksananya tata kelola yangbaik. 
                    • Unit kerja yangmenangani fungsi likuiditas telah melaksanakan tugas dan wewenangnya sesuai dengan pedoman yang ditetapkan; dan 
                    • Memiliki SKMR atau PEMR danmampu melaksanakan fungsinya untuk memitigasi Risiko likuiditas.',
                    2 => '• Memiliki unit kerja yang menangani fungsi likuiditas namun tidak lengkap dan tidak terdapat rangkap jabatan yang dapat menyebabkan tidak terlaksananya tata kelola yang baik. <br>
                    • Unit kerja yang menangani fungsi likuiditas telah melaksanakan tugas dan wewenangnya sesuai dengan pedoman yang ditetapkan; dan <br>
                    • Memiliki SKMR atau PEMR dan mampu melaksanakan fungsinya untuk memitigasi Risiko likuiditas',
                    3 => '• Memiliki unit kerja yang menangani fungsi likuiditas namun tidak lengkap dan terdapat rangkap jabatan namun tidak menyebabkan tidak terlaksananya tata kelola yangbaik. <br>
                    • Unit kerja yangmenangani fungsi likuiditas telah melaksanakan tugas dan wewenangnya sesuai dengan pedoman yang ditetapkan; dan <br>
                    • Memiliki SKMR atau PEMR namun tidak mampu melaksanakan fungsinya untuk memitigasi Risiko likuiditas.',
                    4 => '• Memiliki unit kerja yang menangani fungsi likuiditas namun tidak lengkap dan terdapat rangkap jabatan yang dapat menyebabkan tidak terlaksananya tata kelola yang baik. <br>
                    • Unit kerja yang menangani fungsi likuiditas telah melaksanakan tugas dan wewenangnya sesuai dengan pedoman yang ditetapkan; dan <br>
                    • Memiliki SKMR atau PEMR namun tidak mampu melaksanakan fungsinya untuk memitigasi Risiko likuiditas.',
                    5 => '• Memiliki unit kerja yang menangani fungsi likuiditas namun tidak lengkap dan terdapat rangkap jabatan yang dapat menyebabkan tidak terlaksananya tata kelola yang baik. <br>
                    • Unit kerja yang menangani fungsi likuiditas tidak melaksanakan tugas dan wewenangnya sesuai dengan pedoman yang ditetapkan; dan <br>
                    • Memiliki SKMRatau PEMR namun tidak mampu melaksanakan fungsinya untukmemitigasi Risikokredit. '
                ],
                'catatan' => 'Peniliaian Risiko Likuiditas Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            124 => [
                'descriptions' => [
                    1 => '• Terdapat kesesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan; <br>
                    • Terdapat upaya peningkatan kompetensi SDM secara konsisten; dan <br>
                    • Tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi likuiditas sesuai dengan tugas dan tanggung jawab.',
                    2 => "• Terdapat ketidaksesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan namun tidak memberikan dampak yang signifikan; <br>
                    • Terdapat upaya peningkatan kompetensi SDM secara konsisten; dan <br>
                    • Tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi likuiditas sesuai dengan tugas dan tanggung jawab.",
                    3 => '• Terdapat ketidaksesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan namun tidak memberikan dampak yang signifikan; <br>
                    • Terdapat upaya peningkatan kompetensi SDM namun tidak secara konsisten; dan <br>
                    • Tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi likuiditas sesuai dengan tugas dan tanggung jawab.',
                    4 => '• Terdapat ketidaksesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan dan memberikan dampak yang signifikan; <br>
                    • Terdapat upaya peningkatan kompetensi SDM namun tidak secara konsisten; dan <br>
                    • Tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi likuiditas tidak sesuai dengan tugas dan tanggung jawab namun tidak memberikan dampak yang signifikan.',
                    5 => '• Terdapat ketidaksesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan dan memberikan dampak yang signifikan; <br>
                    • Tidak terdapat upaya peningkatan kompetensi SDM; dan <br>
                    • Tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi likuiditas tidak sesuai dengan tugas dan tanggung jawab dan memberikan dampak yang signifikan.'
                ],
                'catatan' => 'Peniliaian Risiko Likuiditas Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            125 => [
                'descriptions' => [
                    1 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Sangat Rendah',
                    2 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Rendah',
                    3 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Sedang',
                    4 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Tinggi',
                    5 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Sangat Tinggi'
                ],
                'catatan' => 'Peniliaian Risiko Likuiditas Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            126 => [
                'descriptions' => [
                    1 => '• Telah memiliki kebijakan Manajemen Risiko likuiditas; <br>
                    • Terdapat kesesuaian antara substansi kebijakan Manajemen Risiko likuiditas dengan ketentuan Manajemen Risiko BPR antara lain memiliki strategi Manajemen Risiko, kriteria pemberian likuiditas yang sehat, serta penetapan sistem informasi Manajemen Risiko untuk Risiko likuiditas; dan <br>
                    • Terdapat keselarasan antara kebijakan Manajemen Risiko likuiditas dengan visi, misi, skala usaha, dan kompleksitas bisnis, serta kecukupan SDM dalam menetapkan kebijakan Manajemen Risiko likuiditas.',
                    2 => "• Telah memiliki kebijakan Manajemen Risiko likuiditas; <br>
                    • Terdapat ketidaksesuaian yang tidak signifikan antara substansi kebijakan Manajemen Risiko likuiditas dengan ketentuan Manajemen Risiko BPR antara lain memiliki strategi Manajemen Risiko, kriteria pemberian likuiditas yang sehat, serta penetapan sistem informasi Manajemen Risiko untuk Risiko likuiditas; dan <br>
                    • Terdapat keselarasan antara kebijakan Manajemen Risiko likuiditas dengan visi, misi, skala usaha, dan kompleksitas bisnis, serta kecukupan SDM dalam menetapkan kebijakan Manajemen Risiko likuiditas.",
                    3 => '• Telah memiliki kebijakan Manajemen Risiko likuiditas; <br>
                    • Terdapat ketidaksesuaian yang tidak signifikan antara substansi kebijakan Manajemen Risiko likuiditas dengan ketentuan Manajemen Risiko BPR antara lain memiliki strategi Manajemen Risiko, kriteria pemberian likuiditas yang sehat, serta penetapan sistem informasi Manajemen Risiko untuk Risiko likuiditas; dan <br> 
                    • Terdapat ketidakselarasan antara kebijakan Manajemen Risiko likuiditas dengan visi, misi, skala usaha, dan kompleksitas bisnis, serta kecukupan SDM dalam menetapkan kebijakan Manajemen Risiko likuiditas, namun tidak menimbulkan dampak yang signifikan.',
                    4 => '• Telah memiliki kebijakan Manajemen Risiko likuiditas; <br>
                    • Terdapat ketidaksesuaian yang signifikan antara substansi kebijakan Manajemen Risiko likuiditas dengan ketentuan Manajemen Risiko BPR antara lain memiliki strategi Manajemen Risiko, kriteria pemberian likuiditas yang sehat, serta penetapan sistem informasi Manajemen Risiko untuk Risiko likuiditas; dan <br>
                    • Terdapat ketidakselarasan antara kebijakan Manajemen Risiko likuiditas dengan visi, misi, skala usaha, dan kompleksitas  bisnis, serta kecukupan SDM dalam menetapkan kebijakan Manajemen Risiko likuiditas dan menimbulkan dampak yang signifikan.',
                    5 => 'Tidak memiliki kebijakan Manajemen Risiko likuiditas.'
                ],
                'catatan' => 'Peniliaian Risiko Likuiditas Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            127 => [
                'descriptions' => [
                    1 => '• Memiliki prosedur Manajemen Risiko likuiditas dan penetapan limit Risiko likuiditas yang ditetapkan oleh Direksi paling sedikit meliputi jenjang delegasi wewenang dan pertanggung jawaban yang jelas serta terdokumentasi  dengan baik sehingga memudahkan keperluan jejak audit untuk keperluan pengendalian intern; <br>
                    • Melaksanakan prosedur Manajemen Risiko likuiditas danpenetapan limit Risiko likuiditas dalam setiap aktivitas fungsional secara konsisten; dan <br>
                    • Melakukan evaluasi dan pengkinian prosedur Manajemen Risiko likuiditas danpenetapan limit Risiko likuiditas dalam hal terdapat  perubahan bisnis yang signifikan dan/atau ketentuan peraturan perundangundangan.',
                    2 => '• Memiliki prosedur Manajemen Risiko likuiditas dan penetapan limit Risiko likuiditas yang ditetapkan oleh Direksi paling sedikit meliputi jenjang delegasi wewenang dan pertanggung jawaban yang jelas serta terdokumentasi dengan baik sehingga memudahkan keperluan jejak audit untuk keperluan pengendalian intern; <br>
                    • Melaksanakan prosedur Manajemen Risiko likuiditas dan penetapan limit Risiko likuiditas dalam setiap aktivitas fungsional secara konsisten; dan <br>
                    • Tidak melakukan evaluasi dan pengkinian prosedur Manajemen Risiko likuiditas dan penetapan limit Risiko likuiditas dalam hal terdapat  perubahan bisnis yang signifikan dan/atau ketentuan peraturan perundangundangan, namun tidak menimbulkan dampak yang signifikan.',
                    3 => '• Memiliki prosedur Manajemen Risiko likuiditas dan penetapan limit Risiko likuiditas yang ditetapkan oleh Direksi paling sedikit meliputi jenjang delegasi wewenang dan pertanggung jawaban yang jelas serta terdokumentasi dengan baik sehingga memudahkan keperluan jejak audit untuk keperluan pengendalian intern; <br>
                    • Melaksanakan prosedur Manajemen Risiko likuiditas dan penetapan limit Risiko likuiditas dalam setiap aktivitas fungsional secara konsisten; dan <br>
                    • Tidak melakukan evaluasi dan pengkinian prosedur Manajemen Risiko likuiditas dan penetapan limit Risiko likuiditas dalam hal terdapat  perubahan bisnis yang signifikan dan/atau ketentuan peraturan perundangundangan, dan menimbulkan dampak yang signifikan.',
                    4 => '• Memiliki prosedur Manajemen Risiko likuiditas dan penetapan limit Risiko likuiditas yang ditetapkan oleh Direksi paling sedikit meliputi jenjang delegasi wewenang dan pertanggung jawaban yang jelas serta terdokumentasi  dengan baik sehingga memudahkan keperluan jejak audit untuk keperluan pengendalian intern; <br>
                    • Tidak melaksanakan prosedur Manajemen Risiko likuiditas danpenetapan limit Risiko likuiditas dalam setiap aktivitas fungsional secara konsisten; dan <br> 
                    • Tidak melakukan evaluasi dan pengkinian prosedur Manajemen Risiko likuiditas danpenetapan limit Risiko likuiditas dalam hal  terdapat perubahan bisnis yang signifikan dan/atau ketentuan peraturan perundangundangan.',
                    5 => '• Tidak memiliki prosedur Manajemen Risikokredit dan penetapan limit Risiko likuiditas yangditetapkan oleh Direksi.'
                ],
                'catatan' => 'Peniliaian Risiko Likuiditas Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            128 => [
                'descriptions' => [
                    1 => '• Memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru yang memiliki eksposur Risiko likuiditas; <br>
                    • Menerapkan kebijakan dan prosedur dalam hal terdapat penerbitan produk atau pelaksanaan aktivitas baru; dan <br>
                    • Terdapat kesesuaian antara kebijakan dan prosedur produk dan/atau aktivitas baru dengan ketentuan.',
                    2 => '• Memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru yang memiliki eksposur Risiko likuiditas; <br>
                    • Menerapkan kebijakan dan prosedur dalam hal terdapat penerbitan produk atau pelaksanaan aktivitas baru; dan <br>
                    • Terdapat ketidaksesuaian yang tidak signifikan antara kebijakan dan prosedur produk dan/atau aktivitas baru dengan ketentuan.',
                    3 => '• Memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru yang memiliki eksposur Risiko likuiditas; <br>
                    • Menerapkan kebijakan dan prosedur dalam hal terdapat penerbitan produk atau pelaksanaan aktivitas baru; dan <br>
                    • Terdapat ketidaksesuaian yang signifikan antara kebijakan dan prosedur produk dan/atau aktivitas baru dengan ketentuan.',
                    4 => '• Memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atauaktivitas baru yang memiliki eksposur Risiko likuiditas; <br>
                    • Tidak menerapkan kebijakan dan prosedur dalam hal terdapat penerbitan produk atau pelaksanaan aktivitas baru; dan <br>
                    • Terdapat ketidaksesuaian yang signifikan antara kebijakan dan prosedur produk dan/atau aktivitas baru dengan ketentuan.',
                    5 => 'Tidak memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru yang memiliki eksposur Risiko likuiditas.'
                ],
                'catatan' => 'Peniliaian Risiko Likuiditas Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            129 => [
                'descriptions' => [
                    1 => 'Parameter Kecukupan Proses dan Sistem Informasi Manajemen Risiko berada pada tingkat risiko yang Sangat Rendah',
                    2 => 'Parameter Kecukupan Proses dan Sistem Informasi Manajemen Risiko berada pada tingkat risiko yang Rendah',
                    3 => 'Parameter Kecukupan Proses dan Sistem Informasi Manajemen Risiko berada pada tingkat risiko yang Sedang',
                    4 => 'Parameter Kecukupan Proses dan Sistem Informasi Manajemen Risiko berada pada tingkat risiko yang Tinggi',
                    5 => 'Parameter Kecukupan Proses dan Sistem Informasi Manajemen Risiko berada pada tingkat risiko yang Sangat Tinggi'
                ],
                'catatan' => 'Peniliaian Risiko Likuiditas Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            130 => [
                'descriptions' => [
                    1 => '• Telah melaksanakan proses Manajemen Risiko likuiditas meliputi identifikasi, pengukuran, pemantauan, dan pengendalian Risiko likuiditas terhadap kegiatan usaha BPR yang terkait dengan Risiko likuiditas paling  sedikit mencakup kondisi keuangan atau laporan keuangan terakhir, hasil proyeksi arus kas, dan dokumen lain yang dapat digunakan untuk menganalisis kondisi dan kredibilitas debitur; <br>
                    • Penerapan Manajemen Risiko likuiditas dilakukan dengan sangat memadai; dan <br>
                    • Penerapan Manajemen Risiko likuiditas dilakukan secara konsisten. ',
                    2 => '• Telah melaksanakan proses Manajemen Risiko likuiditas meliputi identifikasi, pengukuran, pemantauan, dan pengendalian Risiko likuiditas terhadap kegiatan usaha BPR yang terkait dengan Risiko likuiditas paling sedikit mencakup kondisi keuanganatau laporan keuangan terakhir, hasil proyeksi arus kas, dan dokumen lain yang dapat digunakan untukmenganalisis kondisi dan kredibilitas debitur; <br>
                    • Penerapan Manajemen Risiko likuiditas dilakukan dengan memadai; dan <br>
                    • Penerapan Manajemen Risiko likuiditas dilakukan cukup konsisten. ',
                    3 => '• Telah melaksanakan proses Manajemen Risiko likuiditas meliputi identifikasi, pengukuran, pemantauan, danpengendalian Risiko likuiditas terhadap kegiatan usaha BPR yang terkaitdengan Risiko likuiditas paling  sedikit mencakup kondisi keuanganatau laporan keuangan terakhir, hasil proyeksi arus kas, dan dokumen lain yang dapat digunakan untukmenganalisis kondisi dan kredibilitas debitur; <br>
                    • Penerapan Manajemen Risiko likuiditas dilakukan dengan memadai; dan <br>
                    • Penerapan Manajemen Risiko likuiditas tidak dilakukan secara konsisten namun tidak menimbulkan dampak yang signifikan.',
                    4 => '• Telah melaksanakan proses Manajemen Risiko likuiditas namun tidak secara keseluruhan meliputi identifikasi, pengukuran, pemantauan, dan pengendalian Risiko likuiditas terhadap kegiatan usaha BPR yang terkait dengan Risiko likuiditas paling sedikit mencakup kondisi keuangan atau laporan keuangan terakhir, hasil proyeksi arus kas, dan dokumen lain yang dapat digunakan untuk menganalisis kondisi dan kredibilitas debitur; <br>
                    • Penerapan Manajemen Risiko likuiditas tidak memadai; dan <br>
                    • Penerapan Manajemen Risiko likuiditas tidak dilakukan secara konsisten sehingga menimbulkan dampak yang signifikan.',
                    5 => '• Tidak melaksanakan proses Manajemen Risiko likuiditas meliputi identifikasi, pengukuran, pemantauan, dan pengendalian Risiko likuiditas terhadap kegiatan usaha BPR yang terkait dengan Risiko likuiditas.'
                ],
                'catatan' => 'Peniliaian Risiko Likuiditas Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            131 => [
                'descriptions' => [
                    1 => '• Telah memiliki sistem informasi Manajemen Risiko yang mencerminkan Risiko likuiditas; <br>
                    • Data pada sisteminformasi Manajemen Risiko telah lengkap, akurat, kini, dan utuh; <br>
                    • Sistem informasi Manajemen Risiko mendukung Direksi dalam pengambilan keputusan; dan <br>
                    • Sistem informasi Manajemen Risiko sangat mendukung SKMR atau PEMR dalam pembuatan laporan kepada Direksi setiap semester.',
                    2 => '• Telah memiliki sistem informasi Manajemen Risiko yang mencerminkan Risiko likuiditas; <br>
                    • Data pada sisteminformasi Manajemen Risiko cukup lengkap, akurat, kini, dan utuh; <br>
                    • Sistem informasi Manajemen Risiko mendukung Direksi dalam pengambilan keputusan; dan <br>
                    • Sistem informasi Manajemen Risiko cukup mendukung SKMR atau PEMR dalam pembuatan laporan kepada Direksi setiap semester.',
                    3 => '• Telah memiliki sistem informasi Manajemen Risiko yang mencerminkan Risiko likuiditas; <br>
                    • Data pada sistem informasi Manajemen Risiko kurang lengkap, akurat, kini, dan utuh; <br>
                    • Sistem informasi Manajemen Risiko tidak sepenuhnya mendukung Direksi dalam pengambilan keputusan; dan  sistem informasi Manajemen Risiko kurang mendukung SKMR atau  PEMR dalam pembuatan laporan kepada Direksi setiap semester.',
                    4 => '• Telah memiliki sistem informasi Manajemen Risiko yang mencerminkan Risiko likuiditas; <br>
                    • Data pada sistem informasi Manajemen Risiko tidak lengkap, akurat, kini, dan utuh; <br>
                    • Sistem informasi Manajemen Risiko tidak sepenuhnya mendukung Direksi dalam pengambilan keputusan; dan <br>
                    • Sistem informasi Manajemen Risiko tidak mendukung SKMR atau PEMR dalam pembuatan laporan kepada Direksi setiap semester.',
                    5 => 'Tidak memiliki sistem informasi Manajemen Risikoyang mencerminkan Risiko likuiditas.'
                ],
                'catatan' => 'Peniliaian Risiko Likuiditas Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            132 => [
                'descriptions' => [
                    1 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Sangat Rendah',
                    2 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Rendah',
                    3 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Sedang',
                    4 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Tinggi',
                    5 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Sangat Tinggi'
                ],
                'catatan' => 'Peniliaian Risiko Likuiditas Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            133 => [
                'descriptions' => [
                    1 => '• SKAI atau PEAI telah melaksanakan audit intern terhadap penerapan Manajemen Risiko likuiditas, memberikan rekomendasi, dan melaporkan hasil audit intern kepada Direktur Utama; <br>
                    • Audit intern telah dilaksanakan oleh SKAI atau PEAI meliputi pelaksanaan kebijakan dan prosedur Manajemen Risiko likuiditas dengan mempertimbang kan ketentuan serta kondisi BPR; dan <br> 
                    • Hasil temuan audit intern yang dijadikan rekomendasi telah ditindaklanjuti.',
                    2 => '• SKAI atau PEAI telah melaksanakan audit intern terhadap penerapan Manajemen Risiko likuiditas, memberikan rekomendasi, dan melaporkan hasil audit internkepada Direktur Utama; <br>
                    • Audit intern telahdilaksanakan oleh SKAI atau PEAI meliputi pelaksanaan kebijakan dan prosedur Manajemen Risiko likuiditas dengan mempertimbang kan ketentuan serta kondisi BPR; dan <br>
                    • Hasil temuan audit intern yang dijadikan rekomendasi tidak sepenuhnya ditindaklanjuti namun tidak menimbulkan dampak yang signifikan.',
                    3 => '• SKAI atau PEAI telah melaksanakan audit intern terhadap penerapan Manajemen Risiko likuiditas, memberikan rekomendasi, dan melaporkan hasil audit intern kepada Direktur Utama; <br>
                    • Audit intern telah dilaksanakan oleh SKAI atau PEAI meliputi pelaksanaan kebijakan dan prosedur Manajemen Risiko likuiditas dengan mempertimbang kan ketentuan serta kondisi BPR; dan <br>
                    • Hasil temuan audit intern yang dijadikan rekomendasi tidak sepenuhnya ditindaklanjuti dan menimbulkan dampak yang signifikan.',
                    4 => '• SKAI atau PEAI telah melaksanakan audit intern terhadap penerapan Manajemen Risiko likuiditas, memberikan rekomendasi, dan melaporkan hasil audit intern kepada Direktur Utama; <br>
                    • Audit intern telah dilaksanakan oleh SKAI atau PEAI namun tidak sesuai dengan cakupan pelaksanaan kebijakan dan prosedur Manajemen Risiko likuiditas; dan <br>
                    • Hasil temuan audit intern yangdijadikan rekomendasi tidak ditindaklanjuti.',
                    5 => 'SKAI atau PEAI tidak melaksanakan audit intern terhadap penerapan Manajemen Risiko likuiditas.'
                ],
                'catatan' => 'Peniliaian Risiko Likuiditas Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            134 => [
                'descriptions' => [
                    1 => '• Seluruh jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko likuiditas telah melaksanakan fungsi pengendalian intern dengan memerhatikan kebijakan Manajemen Risiko, prosedur Manajemen Risiko, serta penetapan limit Risiko likuiditas; <br>
                    • Terdapat kejelasan wewenang dan tanggung jawab dari masing- masing jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko likuiditas; <br>
                    • SKMR atau PEMR terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko likuiditas; dan <br>
                    • SKAI atau PEAI terpisah dari unityang berkaitan dengan aktivitas yang memiliki eksposur Risiko likuiditas.',
                    2 => '• Seluruh jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko likuiditas telah melaksanakan fungsi pengendalian intern namun tidak sepenuhnya memerhatikan kebijakan Manajemen Risiko, prosedur Manajemen Risiko, serta penetapan limit Risiko likuiditas dan tidak berdampak signifikan; <br>
                    • Terdapat kejelasan wewenang dan tanggung jawab dari masing- masing jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko likuiditas; <br>
                    • SKMR atau PEMR terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko likuiditas; dan <br>
                    • SKAI atau PEAI terpisah dari unityang berkaitan dengan aktivitas yang memiliki eksposur Risiko likuiditas.',
                    3 => '• Seluruh jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko likuiditas telah melaksanakan fungsi pengendalian intern namun tidak sepenuhnya memerhatikan kebijakan Manajemen Risiko, prosedur Manajemen Risiko, serta penetapan limit Risiko likuiditas dan berdampak signifikan; <br>
                    • Terdapat kejelasan wewenang dan tanggung jawab dari masing- masing jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko likuiditas; <br>
                    • SKMR atau PEMR terpisah dari unit yang berkaitan dengan aktivitas yang  memiliki eksposur Risiko likuiditas; dan <br>
                    • SKAI atau PEAI terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko likuiditas.',
                    4 => '• Tidak seluruh jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko likuiditas telah melaksanakan fungsi pengendalian intern dengan memerhatikan kebijakan Manajemen Risiko, prosedur Manajemen Risiko, serta penetapan limit Risiko likuiditas dan berdampak sangat signifikan; <br>
                    • Tidak terdapat kejelasan wewenang dan tanggung jawab dari masing- masing jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko likuiditas; <br>
                    • SKMR atau PEMR terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko likuiditas; dan <br>
                    • SKAI atau PEAI terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko likuiditas.',
                    5 => '• Seluruh jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko likuiditas tidak melaksanakan fungsi pengendalian intern; <br>
                    • Tidak terdapat kejelasan wewenang dan tanggung jawab dari masing- masing jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko likuiditas; <br>
                    • SKMR atau PEMR tidak terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko likuiditas; dan <br>
                    • SKAI atau PEAI tidak terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko likuiditas.'
                ],
                'catatan' => 'Peniliaian Risiko Likuiditas Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            135 => [
                'descriptions' => [
                    1 => '• Seluruh jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko likuiditas telah melaksanakan fungsi pengendalian intern dengan memerhatikan kebijakan Manajemen Risiko, prosedur Manajemen Risiko, serta penetapan limit Risiko likuiditas; <br>
                    • Terdapat kejelasan wewenang dan tanggung jawab dari masing- masing jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko likuiditas; <br>
                    • SKMR atau PEMR terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko likuiditas; dan <br>
                    • SKAI atau PEAI terpisah dari unityang berkaitan dengan aktivitas yang memiliki eksposur Risiko likuiditas.',
                    2 => '• Seluruh jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko likuiditas telah melaksanakan fungsi pengendalian intern namun tidak sepenuhnya memerhatikan kebijakan Manajemen Risiko, prosedur Manajemen Risiko, serta penetapan limit Risiko likuiditas dan tidak berdampak signifikan; <br>
                    • Terdapat kejelasan wewenang dan tanggung jawab dari masing- masing jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko likuiditas; <br>
                    • SKMR atau PEMR terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko likuiditas; dan <br>
                    • SKAI atau PEAI terpisah dari unityang berkaitan dengan aktivitas yang memiliki eksposur Risiko likuiditas.',
                    3 => '• Seluruh jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko likuiditas telah melaksanakan fungsi pengendalian intern namun tidak sepenuhnya memerhatikan kebijakan Manajemen Risiko, prosedur Manajemen Risiko, serta penetapan limit Risiko likuiditas dan berdampak signifikan; <br>
                    • Terdapat kejelasan wewenang dan tanggung jawab dari masing- masing jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko likuiditas; <br>
                    • SKMR atau PEMR terpisah dari unit yang berkaitan dengan aktivitas yang  memiliki eksposur Risiko likuiditas; dan <br>
                    • SKAI atau PEAI terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko likuiditas.',
                    4 => '• Tidak seluruh jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko likuiditas telah melaksanakan fungsi pengendalian intern dengan memerhatikan kebijakan Manajemen Risiko, prosedur Manajemen Risiko, serta penetapan limit Risiko likuiditas dan berdampak sangat signifikan; <br>
                    • Tidak terdapat kejelasan wewenang dan tanggung jawab dari masing- masing jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko likuiditas; <br>
                    • SKMR atau PEMR terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko likuiditas; dan <br>
                    • SKAI atau PEAI terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko likuiditas.',
                    5 => '• Seluruh jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko likuiditas tidak melaksanakan fungsi pengendalian intern; <br>
                    • Tidak terdapat kejelasan wewenang dan tanggung jawab dari masing- masing jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko likuiditas; <br>
                    • SKMR atau PEMR tidak terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko likuiditas; dan <br>
                    • SKAI atau PEAI tidak terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko likuiditas.'
                ],
                'catatan' => 'Peniliaian Risiko Likuiditas Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],
        ];
    }

    public function cekKomentarBaru()
    {
        $subkategori = 'LIKUIDITASKPMR';
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
            'LIKUIDITASKPMR',
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
            $this->db->table('likuiditaskpmr')
                ->where('faktor1id', $faktor1id)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update($data);

            $categoryMapping = [
                118 => [119, 120, 121, 122, 123, 124],
                125 => [126, 127, 128],
                129 => [130, 131],
                132 => [133, 134]
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

            if (in_array($faktor1id, [118, 119, 120, 121, 122, 123, 124, 125, 126, 127, 128, 129, 130, 131, 132, 133, 134, 135])) {
                $rataRata = $this->nilaiModel->hitungRataRata(135, $this->userKodebpr, $this->periodeId);
                $this->nilaiModel->insertOrUpdateRataRata($rataRata, 135, $this->userKodebpr, $this->periodeId);
            }

            $this->db->table('likuiditaskpmr')
                ->where('faktor1id', 135)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update(['accdir2' => 0, 'is_approved' => 0]);

            $nilai13 = $this->nilaiModel->where([
                'faktor1id' => 135,
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
            $this->db->table('likuiditaskpmr')
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
            $result = $this->db->table('likuiditaskpmr')
                ->where('faktor1id', $faktor1id)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update($data);

            if ($result) {
                $this->db->table('likuiditaskpmr')
                    ->where('faktor1id', 135)
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
            $this->db->table('likuiditaskpmr')->insert($data);

            $categoryMapping = [
                118 => [119, 120, 121, 122, 123, 124],
                125 => [126, 127, 128],
                129 => [130, 131],
                132 => [133, 134]
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

            if (in_array($faktorId, [118, 119, 120, 121, 122, 123, 124, 125, 126, 127, 128, 129, 130, 131, 132, 133, 134, 135])) {
                $rataRata = $this->nilaiModel->hitungRataRata(135, $this->userKodebpr, $this->periodeId);
                $this->nilaiModel->insertOrUpdateRataRata($rataRata, 135, $this->userKodebpr, $this->periodeId);
            }

            $nilai13 = $this->nilaiModel->where([
                'faktor1id' => 135,
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
            return redirect()->to(base_url('Likuiditaskpmr'));
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
                'subkategori' => "LIKUIDITASKPMR",
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
                        'LIKUIDITASKPMR',
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

                return redirect()->to(base_url('Likuiditaskpmr') . '?modal_komentar=' . $faktorId)
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

        return redirect()->to(base_url('Likuiditaskpmr'));
    }

    public function approve($idNilai = null)
    {
        if ($idNilai === null) {
            return redirect()->back()->with('err', 'ID tidak valid');
        }

        $this->db->table('likuiditaskpmr')
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

        $checkData = $this->nilaiModel
            ->select('COUNT(CASE WHEN penilaiankredit IS NOT NULL THEN 1 END) as filled_count')
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->whereIn('faktor1id', range(118, 135))
            ->first();

        if ($checkData['filled_count'] < 18) {
            return redirect()->back()->with('err', 'Semua faktor harus diisi terlebih dahulu');
        }

        try {
            $this->db->table('likuiditaskpmr')
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->whereIn('faktor1id', range(118, 135))
                ->update([
                    'is_approved' => 1,
                    'approved_by' => $this->userId,
                    'approved_at' => date('Y-m-d H:i:s')
                ]);

            return redirect()->back()->with('message', 'Semua data berhasil disetujui.');
        } catch (\Exception $e) {
            log_message('error', 'Error in approveSemua: ' . $e->getMessage());
            return redirect()->back()->with('err', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    public function unapproveSemua()
    {
        if (!$this->userKodebpr || !$this->periodeId) {
            return redirect()->back()->with('err', 'Kode BPR atau Periode ID tidak valid');
        }

        try {
            $this->db->table('likuiditaskpmr')
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->whereIn('faktor1id', range(118, 135))
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

        $this->db->table('likuiditaskpmr')
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
            return redirect()->to('/Likuiditaskpmr')->with('error', 'Data tidak lengkap');
        }

        $nilaiFaktor = $this->nilaiModel
            ->where('faktor1id', $faktor1id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if (!$nilaiFaktor) {
            return redirect()->to('/Likuiditaskpmr')->with('error', 'Data tidak ditemukan');
        }

        $dataUpdate = [
            'accdir2' => 0,
            'accdir2_by' => $this->userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->nilaiModel->update($nilaiFaktor['id'], $dataUpdate)) {
            return redirect()->to('/Likuiditaskpmr')->with('message', 'Persetujuan dibatalkan');
        }

        return redirect()->to('/Likuiditaskpmr')->with('error', 'Gagal memperbarui data');
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
            "LIKUIDITASKPMR",
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
            "LIKUIDITASKPMR",
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

        $faktorIds = range(118, 135);
        $counts = [];

        foreach ($faktorIds as $faktorId) {
            $count = $this->commentReadsModel->countUnreadCommentsForUserByFactor(
                $faktorId,
                "LIKUIDITASKPMR",
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

        $faktorId = 136;
        $penilaiankredit = $this->request->getPost('penilaiankredit');

        $user = $this->userModel->find($this->userId);

        $penjelasanMapping = [
            '1' => 'Tingkat Risiko Likuiditas KPMR: Sangat Rendah',
            '2' => 'Tingkat Risiko Likuiditas KPMR: Rendah',
            '3' => 'Tingkat Risiko Likuiditas KPMR: Sedang',
            '4' => 'Tingkat Risiko Likuiditas KPMR: Tinggi',
            '5' => 'Tingkat Risiko Likuiditas KPMR: Sangat Tinggi'
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
                    'keterangan' => 'Tingkat Risiko Likuiditas KPMR Posisi Sebelumnya',
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
                    return redirect()->to(base_url('Likuiditaskpmr'))
                        ->with('message', 'Data Tingkat Risiko Likuiditas KPMR berhasil diupdate');
                } else {
                    throw new \Exception('Gagal mengupdate data');
                }

            } else {
                $insertData = [
                    'faktor1id' => $faktorId,
                    'penilaiankredit' => $penilaiankredit,
                    'penjelasanpenilaian' => $penjelasanpenilaian,
                    'keterangan' => 'Tingkat Risiko Kredit Inheren Posisi Sebelumnya',
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
                    return redirect()->to(base_url('Likuiditaskpmr'))
                        ->with('message', 'Data Tingkat Risiko Kredit Inheren berhasil disimpan');
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
        $faktorId = 135;
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
            ->where('faktor1id', 135)
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

        $faktorId = 102;
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
                    return redirect()->to(base_url('Likuiditaskpmr'))
                        ->with('message', 'Kesimpulan berhasil diupdate');
                } else {
                    throw new \Exception('Gagal mengupdate kesimpulan');
                }

            } else {
                $rataRata = $this->nilaiModel->hitungRataRata($faktorId, $this->userKodebpr, $this->periodeId);
                $rataRata = ($rataRata - floor($rataRata) >= 0.5) ? ceil($rataRata) : floor($rataRata);

                $penjelasanMapping = [
                    '1' => 'Tingkat Risiko Likuiditas KPMR: Sangat Rendah',
                    '2' => 'Tingkat Risiko Likuiditas KPMR: Rendah',
                    '3' => 'Tingkat Risiko Likuiditas KPMR: Sedang',
                    '4' => 'Tingkat Risiko Likuiditas KPMR: Tinggi',
                    '5' => 'Tingkat Risiko Likuiditas KPMR: Sangat Tinggi'
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
                    return redirect()->to(base_url('Likuiditaskpmr'))
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
            ->where('faktor1id', 136)
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
                'id' => 118,
                'title' => 'Pilar Pengawasan Direksi dan Dewan Komisaris',
                'type' => 'category',
                'faktor_id' => 118,
                'faktor_ids' => [119, 120, 121, 122, 123, 124],
                'description' => 'Pilar Pengawasan Direksi dan Dewan Komisaris',
                'children' => [
                    [
                        'id' => 119,
                        'title' => 'Apakah Dewan Komisaris telah melakukan persetujuan terhadap kebijakan manajemen risiko likuiditas yang disusun oleh Direksi dan melakukan evaluasi secara berkala?',
                        'type' => 'parameter',
                        'faktor_id' => 119,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 120,
                        'title' => 'Apakah Dewan Komisaris telah melakukan evaluasi terhadap pertanggungjawaban Direksi atas pelaksanaan kebijakan manajemen risiko likuiditas secara berkala dan memastikan tindak lanjut hasil evaluasi dimaksud?',
                        'type' => 'parameter',
                        'faktor_id' => 120,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 121,
                        'title' => 'Apakah Direksi telah menyusun kebijakan manajemen risiko likuiditas, melaksanakan secara konsisten, dan melakukan pengkinian secara berkala?',
                        'type' => 'parameter',
                        'faktor_id' => 121,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 122,
                        'title' => 'Apakah Direksi telah memiliki kemampuan untuk mengambil tindakan yang diperlukan dalam rangka mitigasi risiko likuiditas, dan melakukan komunikasi kebijakan manajemen risiko likuiditas terhadap seluruh jenjang organisasi BPR?',
                        'type' => 'parameter',
                        'faktor_id' => 122,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 123,
                        'title' => 'Apakah BPR telah memiliki kecukupan organisasi yang menangani fungsi likuiditas dan fungsi manajemen risiko likuiditas?',
                        'type' => 'parameter',
                        'faktor_id' => 123,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 124,
                        'title' => 'Apakah Direksi telah menerapkan kebijakan pengelolaan SDM dalam rangka penerapan Manajemen Risiko likuiditas?',
                        'type' => 'parameter',
                        'faktor_id' => 124,
                        'previous_periode_faktor_id' => null
                    ]
                ]
            ],
            [
                'id' => 125,
                'title' => 'Pilar Kecukupan Kebijakan, Prosedur, dan Limit',
                'type' => 'category',
                'faktor_id' => 125,
                'faktor_ids' => [126, 127, 128],
                'description' => 'Pilar Kecukupan Kebijakan, Prosedur, dan Limit',
                'children' => [
                    [
                        'id' => 126,
                        'title' => 'Apakah BPR telah memiliki kebijakan manajemen risiko likuiditas yang memadai dan disusun dengan mempertimbangkan visi, misi, skala usaha dan kompleksitas bisnis, serta kecukupan SDM?',
                        'type' => 'parameter',
                        'faktor_id' => 126,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 127,
                        'title' => 'Apakah BPR: 
                        ● Memiliki prosedur manajemen risiko likuiditas dan penetapan limit risiko likuiditas yang ditetapkan oleh Direksi; 
                        ● Melaksanakan prosedur manajemen risiko likuiditas dan penetapan limit risiko likuiditas secara konsisten untuk seluruh aktivitas; dan 
                        ● Melakukan evaluasi dan pengkinian terhadap prosedur manajemen risiko likuiditas dan penetapan limit risiko likuiditas secara berkala?',
                        'type' => 'parameter',
                        'faktor_id' => 127,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 128,
                        'title' => 'Apakah BPR telah memiliki kebijakan dan prosedur penerbitan produk dan/atau pelaksanaan aktivitas baru yang mencakup identifikasi dan mitigasi risiko likuiditas sesuai dengan ketentuan?',
                        'type' => 'parameter',
                        'faktor_id' => 128,
                        'previous_periode_faktor_id' => null
                    ]
                ]
            ],
            [
                'id' => 129,
                'title' => 'Kecukupan Proses dan Sistem Manajemen Informasi',
                'type' => 'category',
                'faktor_id' => 129,
                'faktor_ids' => [130, 131],
                'description' => 'Kecukupan Proses dan Sistem Manajemen Informasi',
                'children' => [
                    [
                        'id' => 130,
                        'title' => 'Apakah BPR telah melaksanakan proses manajemen risiko likuiditas yang melekat pada kegiatan usaha BPR?',
                        'type' => 'parameter',
                        'faktor_id' => 130,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 131,
                        'title' => 'Apakah BPR telah memiliki sistem informasi manajemen risiko yang mendukung Direksi dalam pengambilan keputusan terkait risiko  likuiditas serta telah dilaporkan kepada Direksi secara berkala?',
                        'type' => 'parameter',
                        'faktor_id' => 131,
                        'previous_periode_faktor_id' => null
                    ]
                ]
            ],
            [
                'id' => 132,
                'title' => 'Sistem Pengendalian Internal yang Menyeluruh',
                'type' => 'category',
                'faktor_id' => 132,
                'faktor_ids' => [133, 134],
                'description' => 'Sistem Pengendalian Internal yang Menyeluruh',
                'children' => [
                    [
                        'id' => 133,
                        'title' => 'Apakah SKAI atau PEAI telah melaksanakan audit secara berkala terhadap penerapan manajemen risiko likuiditas, menyampaikan laporan hasil audit intern, dan memastikan tindaklanjut atas temuan pemeriksaan?',
                        'type' => 'parameter',
                        'faktor_id' => 133,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 134,
                        'title' => 'Apakah sistem pengendalian intern terhadap risiko likuiditas telah dilaksanakan oleh seluruh jenjang organisasi BPR?',
                        'type' => 'parameter',
                        'faktor_id' => 134,
                        'previous_periode_faktor_id' => null
                    ]
                ]
            ],
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

    public function recalculateCategoryAverage()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $faktorId = $this->request->getPost('faktor_id');

        $categoryMapping = [
            118 => [119, 120, 121, 122, 123, 124],
            125 => [126, 127, 128],
            129 => [130, 131],
            132 => [133, 134]
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

            $rataRata = $this->nilaiModel->hitungRataRata(135, $this->userKodebpr, $this->periodeId);
            $this->nilaiModel->insertOrUpdateRataRata($rataRata, 135, $this->userKodebpr, $this->periodeId);

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

    public function exporttxtrisikolikuiditaskpmr()
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

        $output = "";
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

        $data_risikokredit = $this->nilaiModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'] ?? '';
            $kodejenis = $infobpr['kodejenis'] ?? '';
        }

        function sanitizeTxtLikKpmr($text)
        {
            // ganti enter
            $text = str_replace(["\r", "\n"], ' ', $text);

            // ganti karakter tipografi ke ASCII
            $text = str_replace(
                ['“', '”', '‘', '’', '%', '|'],
                ['"', '"', "'", "'", ' persen ', ' '],
                $text
            );

            // hilangkan karakter non-ASCII
            $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);

            // rapikan spasi
            return trim(preg_replace('/\s+/', ' ', $text));
        }

        $kodeMap = [
            118 => '4310',
            119 => '4311',
            120 => '4312',
            121 => '4313',
            122 => '4314',
            123 => '4315',
            124 => '4316',
            125 => '4320',
            126 => '4321',
            127 => '4322',
            128 => '4323',
            129 => '4330',
            130 => '4331',
            131 => '4332',
            132 => '4340',
            133 => '4341',
            134 => '4342',
            135 => '4350'
        ];

        $output = "";

        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|PRBPRKS|0402|{$modalinti}|{$totalaset}|{$kantorcabang}|{$atmdebit}|{$kategori}||\r\n";

        usort($data_risikokredit, function ($a, $b) {
            return ($a['faktor1id'] ?? 0) <=> ($b['faktor1id'] ?? 0);
        });

        foreach ($data_risikokredit as $row) {
            $faktorId = $row['faktor1id'] ?? null;
            if (!isset($kodeMap[$faktorId])) {
                continue;
            }
            $kode = $kodeMap[$faktorId] ?? '';

            $penilaiankredit = sanitizeTxtLikKpmr($row['penilaiankredit']);
            $keterangan = sanitizeTxtLikKpmr($row['keterangan']);

            $output .= "D01|{$kode}|{$penilaiankredit}|{$keterangan}\r\n";
        }

        $filename = "PRBPRKS-0402-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        $response = service('response');
        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response->setBody($output);
    }
}