<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_paramprofilrisiko;
use App\Models\M_user;
use App\Models\M_profilrisikocomments;
use App\Models\M_risikokreditkpmr;
use App\Models\M_infobpr;
use App\Models\M_periodeprofilresiko;
use App\Models\M_profilrisikocommentsread;
use App\Models\M_showprofilresiko;
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

    protected $userGroups = [];

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');

        $this->db = \Config\Database::connect();

        $this->paramprofilrisikoModel = new M_paramprofilrisiko();
        $this->periodeModel = new M_periodeprofilresiko();
        $this->userModel = new M_user();
        $this->komentarModel = new M_profilrisikocomments();
        $this->nilaiModel = new M_risikokreditkpmr();
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
            'faktor1id' => 33,
            'kodebpr' => $this->userKodebpr,
            'periode_id' => $this->periodeId
        ])->first();

        $nilai14 = $this->nilaiModel->where([
            'faktor1id' => 34,
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
            ->whereIn('faktor1id', range(16, 33))
            ->first();

        $allFilled = ($approvalData['filled_count'] == 18); // 16-33 = 18
        $allApproved = ($allFilled && $approvalData['approved_count'] == 18);
        $canApprove = $allFilled;

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
            'loadDataViaAjax' => true,

        ];

        return view('templates/v_header', $data)
            . view('templates/v_sidebar')
            . view('templates/v_topbar')
            . view('risikokredit/kpmr', $data)
            . view('templates/v_footer');
    }

    private function checkCanApprove($nilaiLookup)
    {
        $requiredFaktorIds = range(16, 32);

        foreach ($requiredFaktorIds as $faktorId) {
            if (empty($nilaiLookup[$faktorId])) {
                return false;
            }
        }

        return true;
    }

    private function checkAccdekomApproved($nilaiLookup)
    {
        for ($faktorId = 16; $faktorId <= 32; $faktorId++) {
            if (!isset($nilaiLookup[$faktorId]) || $nilaiLookup[$faktorId]['is_approved'] != 1) {
                return false;
            }
        }
        return true;
    }

    private function checkAllApproved($nilaiLookup)
    {
        $requiredFaktorIds = range(16, 33);
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

        $nilaiData = $this->db->table('risikokredit_kpmr as r')
            ->select('r.*, u.fullname')
            ->join('users as u', 'u.id = r.user_id', 'left')
            ->where('r.kodebpr', $this->userKodebpr)
            ->where('r.periode_id', $this->periodeId)
            ->whereIn('r.faktor1id', range(16, 33))
            ->get()
            ->getResultArray();

        $nilaiLookup = [];
        foreach ($nilaiData as $nilai) {
            $nilaiLookup[$nilai['faktor1id']] = $nilai;
        }

        $structure = [
            [
                'id' => 16,
                'title' => 'Pilar Pengawasan Direksi dan Dewan Komisaris',
                'type' => 'category',
                'faktor_id' => 16,
                'children' => [
                    ['id' => 17, 'title' => 'Apakah Dewan Komisaris telah melakukan persetujuan terhadap kebijakan manajemen risiko kredit <br> yang disusun oleh Direksi dan melakukan evaluasi secara berkala?', 'faktor_id' => 17],
                    ['id' => 18, 'title' => 'Apakah Dewan Komisaris telah melakukan evaluasi terhadap pertanggungjawaban Direksi atas pelaksanaan <br> kebijakan manajemen risiko kredit secara berkala dan memastikan tindak lanjut hasil evaluasi dimaksud?', 'faktor_id' => 18],
                    ['id' => 19, 'title' => 'Apakah Direksi telah menyusun kebijakan manajemen risiko kredit, melaksanakan secara konsisten, <br> dan melakukan pengkinian secara berkala?', 'faktor_id' => 19],
                    ['id' => 20, 'title' => 'Apakah Direksi telah memiliki kemampuan untuk mengambil tindakan yang diperlukan dalam rangka mitigasi <br> risiko kredit, dan melakukan komunikasi kebijakan manajemen risiko kredit terhadap seluruh jenjang <br> organisasi BPR?', 'faktor_id' => 20],
                    ['id' => 21, 'title' => 'Apakah BPR telah memiliki kecukupan organisasi yang menangani fungsi kredit dan fungsi manajemen <br> risiko kredit?', 'faktor_id' => 21],
                    ['id' => 22, 'title' => 'Apakah Direksi telah menerapkan kebijakan pengelolaan SDM dalam rangka penerapan Manajemen Risiko <br> kredit?', 'faktor_id' => 22]
                ]
            ],
            [
                'id' => 23,
                'title' => 'Pilar Kecukupan Kebijakan, Prosedur, dan Limit',
                'type' => 'category',
                'faktor_id' => 23,
                'children' => [
                    [
                        'id' => 24,
                        'title' => 'Apakah BPR telah memiliki kebijakan manajemen risiko kredit yang memadai dan disusun dengan <br> mempertimbangkan visi, misi, skala usaha dan kompleksitas bisnis, serta kecukupan SDM?',
                        'faktor_id' => 24
                    ],
                    [
                        'id' => 25,
                        'title' => 'Apakah BPR: 
                    ● Memiliki prosedur manajemen risiko kredit dan penetapan limit risiko kredit yang ditetapkan <br> oleh Direksi; 
                    ● Melaksanakan prosedur manajemen risiko kredit dan penetapan limit risiko kredit secara <br> konsisten untuk seluruh aktivitas; dan 
                    ● Melakukan evaluasi dan pengkinian terhadap prosedur manajemen risiko kredit dan penetapan <br> limit risiko kredit secara berkala?',
                        'faktor_id' => 25
                    ],
                    [
                        'id' => 26,
                        'title' => 'Apakah BPR telah memiliki kebijakan dan prosedur penerbitan produk dan/atau pelaksanaan aktivitas <br> baru yang mencakup identifikasi dan mitigasi risiko kredit sesuai dengan ketentuan?',
                        'faktor_id' => 26
                    ]
                ]
            ],
            [
                'id' => 27,
                'title' => 'Kecukupan Proses dan Sistem Manajemen Informasi',
                'type' => 'category',
                'faktor_id' => 27,
                'children' => [
                    [
                        'id' => 28,
                        'title' => 'Apakah BPR telah melaksanakan proses manajemen risiko kredit yang melekat pada kegiatan <br> usaha BPR?',
                        'faktor_id' => 28
                    ],
                    [
                        'id' => 29,
                        'title' => 'Apakah BPR telah memiliki sistem informasi manajemen risiko yang mendukung Direksi dalam <br> pengambilan <br> keputusan terkait risiko  kredit serta telah dilaporkan kepada Direksi secara berkala?',
                        'faktor_id' => 29
                    ]
                ]
            ],
            [
                'id' => 30,
                'title' => 'Sistem Pengendalian Internal yang Menyeluruh',
                'type' => 'category',
                'faktor_id' => 30,
                'children' => [
                    ['id' => 31, 'title' => 'Apakah SKAI atau PEAI telah melaksanakan audit secara berkala terhadap penerapan <br> manajemen risiko kredit, menyampaikan laporan hasil audit intern, dan memastikan <br> tindaklanjut atas temuan pemeriksaan?', 'faktor_id' => 31],
                    ['id' => 32, 'title' => 'Apakah sistem pengendalian intern terhadap risiko kredit telah dilaksanakan oleh <br> seluruh jenjang organisasi BPR?', 'faktor_id' => 32]
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
            16 => [
                'descriptions' => [
                    1 => 'Parameter Pengawasan Direksi dan Komisaris berada pada tingkat risiko yang Sangat Rendah',
                    2 => 'Parameter Pengawasan Direksi dan Komisaris berada pada tingkat risiko yang Rendah',
                    3 => 'Parameter Pengawasan Direksi dan Komisaris berada pada tingkat risiko yang Sedang',
                    4 => 'Parameter Pengawasan Direksi dan Komisaris berada pada tingkat risiko yang Tinggi',
                    5 => 'Parameter Pengawasan Direksi dan Komisaris berada pada tingkat risiko yang Sangat Tinggi'
                ],
                'catatan' => 'Peniliaian Risiko Kredit Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            17 => [
                'descriptions' => [
                    1 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko kredit <br>
                    • Dewan Komisaris telah melakukan evaluasi terhadap kebijakan Manajemen Risiko kredit <br>
                    • Evaluasi dilakukan oleh Dewan Komisaris secara berkala paling sedikit satu kali dalamsatu tahun atausewaktu-waktu dalam hal terdapat perubahan yangmemengaruhi kegiatan usaha BPR secara signifikan; dan <br>
                    • Evaluasi yang dilakukan relevan dengan kebutuhan penyesuaian kebijakan Manajemen Risiko kredit. ',
                    2 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko kredit; <br>
                    • Dewan Komisaris telah melakukan evaluasi terhadap kebijakan Manajemen Risiko kredit; dan <br> 
                    • Evaluasi dilakukan oleh Dewan Komisaris secara berkala paling sedikit satu kali dalam satu tahun atau sewaktu-waktu dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan',
                    3 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko kredit; <br>
                    • Dewan Komisaris telah melakukan evaluasi terhadap kebijakan Manajemen Risiko kredit; dan <br>
                    • Evaluasi tidak dilakukan oleh Dewan Komisaris secara berkala paling sedikit satu kali dalam satu tahun atau sewaktu-waktu dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan',
                    4 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko kredit; dan <br>
                    • Dewan Komisaris tidak melakukan evaluasi terhadap kebijakan Manajemen Risiko kredit.',
                    5 => '• Dewan Komisaris tidak memberikan persetujuan terhadap kebijakan Manajemen Risiko kredit; dan <br>
                    • Dewan Komisaris tidak melakukan evaluasi terhadap kebijakan Manajemen Risiko kredit.'
                ],
                'catatan' => 'Peniliaian Risiko Kredit Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            18 => [
                'descriptions' => [
                    1 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko kredit oleh Direksi; <br>
                    • Evaluasi oleh Dewan Komisaris dilakukan secara berkala setiap semester atau lebih berdasarkan laporan yang disampaikan Direksi dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan; <br>
                    • Dewan Komisaris telah memastikan tindak lanjut hasil evaluasi dalam setiap periode laporan; dan <br>
                    • Evaluasi yang diberikan relevan dengan pelaksanaan kebijakan Manajemen Risiko kredit dalam rangka mendukung perbaikan kinerja BPR.',
                    2 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko kredit oleh Direksi; <br>
                    • Evaluasi oleh Dewan Komisaris dilakukan secara berkala setiap semester atau lebih berdasarkan laporan yang disampaikan Direksi dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan; <br>
                    • Dewan Komisaris telah memastikan tindak lanjut hasil evaluasi dalam setiap periode laporan.',
                    3 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko kredit oleh Direksi; <br>
                     • Evaluasi oleh Dewan Komisaris dilakukan secara berkala setiap semester atau lebih berdasarkan laporan yang disampaikan Direksi dalam hal terdapat perubahan yangmemengaruhi kegiatan usaha BPR secara signifikan; dan <br> 
                     • Dewan Komisaris tidak memastikan tindak lanjut hasil evaluasi dalam setiap periode laporan.',
                    4 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko kredit oleh Direksi; <br> 
                    • Evaluasi oleh Dewan Komisaris tidak dilakukan secara berkala; dan <br>
                    • Dewan Komisaris tidak memastikan tindak lanjut hasil evaluasi dalam setiap periode pelaporan',
                    5 => 'Dewan Komisaris tidak melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko kredit oleh Direksi.'
                ],
                'catatan' => 'Peniliaian Risiko Kredit Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            19 => [
                'descriptions' => [
                    1 => '• Direksi telah menyusun kebijakan Manajemen Risiko kredit; <br>
                    • Menjalankan kegiatan usaha berdasarkan kebijakan Manajemen Risiko kredit yang telah ditetapkan; <br>
                    • Direksi melakukan pengkinian terhadap kebijakan Manajemen Risiko kredit dalam hal terdapat perubahan ketentuan peraturan perundangundangan, perubahan bisnis, dan hasilevaluasi kebijakan Manajemen Risiko oleh Dewan Komisaris; dan <br>
                    • Kebijakan Manajemen Risiko kredit yang dijalankan terbukti memitigasi terjadinya Risiko kredit.',
                    2 => '• Direksi telah menyusun kebijakan Manajemen Risiko kredit; <br>
                    • Menjalankan kegiatan usaha berdasarkan kebijakan Manajemen Risiko kredit yang telah ditetapkan; dan <br>
                    • Direksi melakukan pengkinian terhadap kebijakan Manajemen Risiko kredit dalam hal terdapat perubahan ketentuan peraturan perundangundangan, perubahan bisnis, dan hasil evaluasi kebijakan Manajemen Risiko oleh Dewan Komisaris.',
                    3 => '• Direksi telah menyusun kebijakan Manajemen Risiko kredit; <br>
                    • Menjalankan kegiatan usaha berdasarkan kebijakan Manajemen Risiko kredit yang telah ditetapkan; dan <br>
                    • Direksi tidak melakukan pengkinian terhadap kebijakan Manajemen Risiko kredit dalam hal terdapat perubahan ketentuan peraturan perundangundangan, perubahan bisnis, dan hasil evaluasi kebijakan Manajemen Risiko oleh Dewan Komisaris.',
                    4 => '• Direksi telah menyusun kebijakan Manajemen Risiko kredit; <br>
                    • Tidak menjalankan kegiatan usaha berdasarkan kebijakan Manajemen Risiko kredit yang telah ditetapkan; dan <br>
                    • Direksi tidak melakukan pengkinian terhadap kebijakan Manajemen Risiko kredit dalam hal terdapat perubahan ketentuan peraturan perundangundangan, perubahan bisnis, dan hasil evaluasi kebijakan Manajemen Risiko oleh Dewan Komisaris.',
                    5 => 'Direksi tidak menyusun kebijakan Manajemen Risiko kredit.'
                ],
                'catatan' => 'Peniliaian Risiko Kredit Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            20 => [
                'descriptions' => [
                    1 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko saat menjalankan kebijakan Manajemen Risiko kredit; <br>
                    • Direksi mengomunikasi kan kebijakan Manajemen Risiko kredit; dan <br>
                    • Seluruh jenjang organisasi BPR mampu memahami kebijakan Manajemen Risiko kredit yang diterapkan.',
                    2 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko saat menjalankan kebijakan Manajemen Risiko kredit; <br>
                    • Direksi mengomunikasi kan kebijakan Manajemen Risiko kredit; dan <br>
                    • Tidak seluruh jenjang organisasi BPR mampu memahami kebijakan Manajemen Risiko kredit yang diterapkan namun tidak menimbulkan dampak yang signifikan',
                    3 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko saat menjalankan kebijakan Manajemen Risiko kredit; <br>
                    • Direksi mengomunikasi kan kebijakan Manajemen Risiko kredit; dan <br>
                    • Tidak seluruh jenjang organisasi BPR mampu memahami kebijakan Manajemen Risiko kredit yang diterapkan dan menimbulkan dampak yang signifikan',
                    4 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko saat menjalankan kebijakan Manajemen Risiko kredit; <br>
                    • Direksi tidak mengomunikasi kan kebijakan Manajemen Risiko kredit; dan <br>
                    • Seluruh jenjang organisasi BPR tidak mampu memahami kebijakan Manajemen Risiko kredit yang diterapkan.',
                    5 => '• Direksi tidak mengambil tindakan yangdiperlukan untukmemitigasi Risikosaat menjalankan kebijakan Manajemen Risiko kredit; <br>
                    • Direksi tidak mengomunikasi kan kebijakan Manajemen Risiko kredit; dan <br>
                    • Seluruh jenjang organisasi BPR tidak mampu memahami kebijakan Manajemen Risiko kredit yang diterapkan.'
                ],
                'catatan' => 'Peniliaian Risiko Kredit Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            21 => [
                'descriptions' => [
                    1 => '• Memiliki unit kerja yang menangani fungsi kredit secara lengkap dan tidak terdapat rangkap jabatan yang dapat menyebabkan tidak terlaksananya tata kelola yangbaik. 
                    • Unit kerja yangmenangani fungsi kredit telah melaksanakan tugas dan wewenangnya sesuai dengan pedoman yang ditetapkan; dan 
                    • Memiliki SKMR atau PEMR danmampu melaksanakan fungsinya untuk memitigasi Risiko kredit.',
                    2 => '• Memiliki unit kerja yang menangani fungsi kredit namun tidak lengkap dan tidak terdapat rangkap jabatan yang dapat menyebabkan tidak terlaksananya tata kelola yang baik. <br>
                    • Unit kerja yang menangani fungsi kredit telah melaksanakan tugas dan wewenangnya sesuai dengan pedoman yang ditetapkan; dan <br>
                    • Memiliki SKMR atau PEMR dan mampu melaksanakan fungsinya untuk memitigasi Risiko kredit',
                    3 => '• Memiliki unit kerja yang menangani fungsi kredit namun tidak lengkap dan terdapat rangkap jabatan namun tidak menyebabkan tidak terlaksananya tata kelola yangbaik. <br>
                    • Unit kerja yangmenangani fungsi kredit telah melaksanakan tugas dan wewenangnya sesuai dengan pedoman yang ditetapkan; dan <br>
                    • Memiliki SKMR atau PEMR namun tidak mampu melaksanakan fungsinya untuk memitigasi Risiko kredit.',
                    4 => '• memiliki unit kerja yang menangani fungsi kredit namun tidak lengkap dan terdapat rangkap jabatan yang dapat menyebabkan tidak terlaksananya tata kelola yang baik. <br>
                    • Unit kerja yang menangani fungsi kredit telah melaksanakan tugas dan wewenangnya sesuai dengan pedoman yang ditetapkan; dan <br>
                    • Memiliki SKMR atau PEMR namun tidak mampu melaksanakan fungsinya untuk memitigasi Risiko kredit.',
                    5 => '• Memiliki unit kerja yang menangani fungsi kredit namun tidak lengkap dan terdapat rangkap jabatan yang dapat menyebabkan tidak terlaksananya tata kelola yang baik. <br>
                    • Unit kerja yang menangani fungsi kredit tidak melaksanakan tugas dan wewenangnya sesuai dengan pedoman yang ditetapkan; dan <br>
                    • Memiliki SKMRatau PEMR namun tidak mampu melaksanakan fungsinya untukmemitigasi Risiko kredit. '
                ],
                'catatan' => 'Peniliaian Risiko Kredit Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            22 => [
                'descriptions' => [
                    1 => '• Terdapat kesesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan; <br>
                    • Terdapat upaya peningkatan kompetensi SDM secara konsisten; dan <br>
                    • Tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi kredit sesuai dengan tugas dan tanggung jawab.',
                    2 => "• Terdapat ketidaksesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan namun tidak memberikan dampak yang signifikan; <br>
                    • Terdapat upaya peningkatan kompetensi SDM secara konsisten; dan <br>
                    • Tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi kredit sesuai dengan tugas dan tanggung jawab.",
                    3 => '• Terdapat ketidaksesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan namun tidak memberikan dampak yang signifikan; <br>
                    • Terdapat upaya peningkatan kompetensi SDM namun tidak secara konsisten; dan <br>
                    • Tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi kredit sesuai dengan tugas dan tanggung jawab.',
                    4 => '• Terdapat ketidaksesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan dan memberikan dampak yang signifikan; <br>
                    • Terdapat upaya peningkatan kompetensi SDM namun tidak secara konsisten; dan <br>
                    • Tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi kredit tidak sesuai dengan tugas dan tanggung jawab namun tidak memberikan dampak yang signifikan.',
                    5 => '• Terdapat ketidaksesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan dan memberikan dampak yang signifikan; <br>
                    • Tidak terdapat upaya peningkatan kompetensi SDM; dan <br>
                    • Tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi kredit tidak sesuai dengan tugas dan tanggung jawab dan memberikan dampak yang signifikan.'
                ],
                'catatan' => 'Peniliaian Risiko Kredit Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            23 => [
                'descriptions' => [
                    1 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Sangat Rendah',
                    2 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Rendah',
                    3 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Sedang',
                    4 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Tinggi',
                    5 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Sangat Tinggi'
                ],
                'catatan' => 'Peniliaian Risiko Kredit Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            24 => [
                'descriptions' => [
                    1 => '• Telah memiliki kebijakan Manajemen Risiko kredit; <br>
                    • Terdapat kesesuaian antara substansi kebijakan Manajemen Risiko kredit dengan ketentuan Manajemen Risiko BPR antara lain memiliki strategi Manajemen Risiko, kriteria pemberian kredit yang sehat, serta penetapan sistem informasi Manajemen Risiko untuk Risiko kredit; dan <br>
                    • Terdapat keselarasan antara kebijakan Manajemen Risiko kredit dengan visi, misi, skala usaha, dan kompleksitas bisnis, serta kecukupan SDM dalam menetapkan kebijakan Manajemen Risiko kredit.',
                    2 => "• Telah memiliki kebijakan Manajemen Risiko kredit; <br>
                    • Terdapat ketidaksesuaian yang tidak signifikan antara substansi kebijakan Manajemen Risiko kredit dengan ketentuan Manajemen Risiko BPR antara lain memiliki strategi Manajemen Risiko, kriteria pemberian kredit yang sehat, serta penetapan sistem informasi Manajemen Risiko untuk Risiko kredit; dan <br>
                    • Terdapat keselarasan antara kebijakan Manajemen Risiko kredit dengan visi, misi, skala usaha, dan kompleksitas bisnis, serta kecukupan SDM dalam menetapkan kebijakan Manajemen Risiko kredit.",
                    3 => '• Telah memiliki kebijakan Manajemen Risiko kredit; <br>
                    • Terdapat ketidaksesuaian yang tidak signifikan antara substansi kebijakan Manajemen Risiko kredit dengan ketentuan Manajemen Risiko BPR antara lain memiliki strategi Manajemen Risiko, kriteria pemberian kredit yang sehat, serta penetapan sistem informasi Manajemen Risiko untuk Risiko kredit; dan <br> 
                    • Terdapat ketidakselarasan antara kebijakan Manajemen Risiko kredit dengan visi, misi, skala usaha, dan kompleksitas bisnis, serta kecukupan SDM dalam menetapkan kebijakan Manajemen Risiko kredit, namun tidak menimbulkan dampak yang signifikan.',
                    4 => '• Telah memiliki kebijakan Manajemen Risiko kredit; <br>
                    • Terdapat ketidaksesuaian yang signifikan antara substansi kebijakan Manajemen Risiko kredit dengan ketentuan Manajemen Risiko BPR antara lain memiliki strategi Manajemen Risiko, kriteria pemberian kredit yang sehat, serta penetapan sistem informasi Manajemen Risiko untuk Risiko kredit; dan <br>
                    • Terdapat ketidakselarasan antara kebijakan Manajemen Risiko kredit dengan visi, misi, skala usaha, dan kompleksitas  bisnis, serta kecukupan SDM dalam menetapkan kebijakan Manajemen Risiko kredit dan menimbulkan dampak yang signifikan.',
                    5 => 'tidak memiliki kebijakan Manajemen Risiko kredit.'
                ],
                'catatan' => 'Peniliaian Risiko Kredit Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            25 => [
                'descriptions' => [
                    1 => '• Memiliki prosedur Manajemen Risiko kredit dan penetapan limit Risiko kredit yang ditetapkan oleh Direksi paling sedikit meliputi jenjang delegasi wewenang dan pertanggung jawaban yang jelas serta terdokumentasi  dengan baik sehingga memudahkan keperluan jejak audit untuk keperluan pengendalian intern; <br>
                    • Melaksanakan prosedur Manajemen Risiko kredit danpenetapan limit Risiko kredit dalam setiap aktivitas fungsional secara konsisten; dan <br>
                    • Melakukan evaluasi dan pengkinian prosedur Manajemen Risiko kredit danpenetapan limit Risiko kredit dalam hal terdapat  perubahan bisnis yang signifikan dan/atau ketentuan peraturan perundangundangan.',
                    2 => '• Memiliki prosedur Manajemen Risiko kredit dan penetapan limit Risiko kredit yang ditetapkan oleh Direksi paling sedikit meliputi jenjang delegasi wewenang dan pertanggung jawaban yang jelas serta terdokumentasi dengan baik sehingga memudahkan keperluan jejak audit untuk keperluan pengendalian intern; <br>
                    • Melaksanakan prosedur Manajemen Risiko kredit dan penetapan limit Risiko kredit dalam setiap aktivitas fungsional secara konsisten; dan <br>
                    • Tidak melakukan evaluasi dan pengkinian prosedur Manajemen Risiko kredit dan penetapan limit Risiko kredit dalam hal terdapat  perubahan bisnis yang signifikan dan/atau ketentuan peraturan perundangundangan, namun tidak menimbulkan dampak yang signifikan.',
                    3 => '• Memiliki prosedur Manajemen Risiko kredit dan penetapan limit Risiko kredit yang ditetapkan oleh Direksi paling sedikit meliputi jenjang delegasi wewenang dan pertanggung jawaban yang jelas serta terdokumentasi dengan baik sehingga memudahkan keperluan jejak audit untuk keperluan pengendalian intern; <br>
                    • Melaksanakan prosedur Manajemen Risiko kredit dan penetapan limit Risiko kredit dalam setiap aktivitas fungsional secara konsisten; dan <br>
                    • Tidak melakukan evaluasi dan pengkinian prosedur Manajemen Risiko kredit dan penetapan limit Risiko kredit dalam hal terdapat  perubahan bisnis yang signifikan dan/atau ketentuan peraturan perundangundangan, dan menimbulkan dampak yang signifikan.',
                    4 => '• Memiliki prosedur Manajemen Risiko kredit dan penetapan limit Risiko kredit yang ditetapkan oleh Direksi paling sedikit meliputi jenjang delegasi wewenang dan pertanggung jawaban yang jelas serta terdokumentasi  dengan baik sehingga memudahkan keperluan jejak audit untuk keperluan pengendalian intern; <br>
                    • Tidak melaksanakan prosedur Manajemen Risiko kredit danpenetapan limit Risiko kredit dalam setiap aktivitas fungsional secara konsisten; dan <br> 
                    • Tidak melakukan evaluasi dan pengkinian prosedur Manajemen Risiko kredit danpenetapan limit Risiko kredit dalam hal  terdapat perubahan bisnis yang signifikan dan/atau ketentuan peraturan perundangundangan.',
                    5 => '• Tidak memiliki prosedur Manajemen Risiko kredit dan penetapan limit Risiko kredit yangditetapkan oleh Direksi.'
                ],
                'catatan' => 'Peniliaian Risiko Kredit Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            26 => [
                'descriptions' => [
                    1 => '• Memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru yang memiliki eksposur Risiko kredit; <br>
                    • Menerapkan kebijakan dan prosedur dalam hal terdapat penerbitan produk atau pelaksanaan aktivitas baru; dan <br>
                    • Terdapat kesesuaian antara kebijakan dan prosedur produk dan/atau aktivitas baru dengan ketentuan.',
                    2 => '• Memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru yang memiliki eksposur Risiko kredit; <br>
                    • Menerapkan kebijakan dan prosedur dalam hal terdapat penerbitan produk atau pelaksanaan aktivitas baru; dan <br>
                    • Terdapat ketidaksesuaian yang tidak signifikan antara kebijakan dan prosedur produk dan/atau aktivitas baru dengan ketentuan.',
                    3 => '• Memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru yang memiliki eksposur Risiko kredit; <br>
                    • Menerapkan kebijakan dan prosedur dalam hal terdapat penerbitan produk atau pelaksanaan aktivitas baru; dan <br>
                    • Terdapat ketidaksesuaian yang signifikan antara kebijakan dan prosedur produk dan/atau aktivitas baru dengan ketentuan.',
                    4 => '• Memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atauaktivitas baru yang memiliki eksposur Risiko kredit; <br>
                    • Tidak menerapkan kebijakan dan prosedur dalam hal terdapat penerbitan produk atau pelaksanaan aktivitas baru; dan <br>
                    • Terdapat ketidaksesuaian yang signifikan antara kebijakan dan prosedur produk dan/atau aktivitas baru dengan ketentuan.',
                    5 => 'Tidak memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru yang memiliki eksposur Risiko kredit.'
                ],
                'catatan' => 'Peniliaian Risiko Kredit Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            27 => [
                'descriptions' => [
                    1 => 'Parameter Kecukupan Proses dan Sistem Manajemen Risiko berada pada tingkat risiko yang Sangat Rendah',
                    2 => 'Parameter Kecukupan Proses dan Sistem Manajemen Risiko berada pada tingkat risiko yang Rendah',
                    3 => 'Parameter Kecukupan Proses dan Sistem Manajemen Risiko berada pada tingkat risiko yang Sedang',
                    4 => 'Parameter Kecukupan Proses dan Sistem Manajemen Risiko berada pada tingkat risiko yang Tinggi',
                    5 => 'Parameter Kecukupan Proses dan Sistem Manajemen Risiko berada pada tingkat risiko yang Sangat Tinggi'
                ],
                'catatan' => 'Peniliaian Risiko Kredit Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            28 => [
                'descriptions' => [
                    1 => '• Telah melaksanakan proses Manajemen Risiko kredit meliputi identifikasi, pengukuran, pemantauan, dan pengendalian Risiko kredit terhadap kegiatan usaha BPR yang terkait dengan Risiko kredit paling  sedikit mencakup kondisi keuangan atau laporan keuangan terakhir, hasil proyeksi arus kas, dan dokumen lain yang dapat digunakan untuk menganalisis kondisi dan kredibilitas debitur; <br>
                    • Penerapan Manajemen Risiko kredit dilakukan dengan sangat memadai; dan <br>
                    • Penerapan Manajemen Risiko kredit dilakukan secara konsisten. ',
                    2 => '• Telah melaksanakan proses Manajemen Risiko kredit meliputi identifikasi, pengukuran, pemantauan, dan pengendalian Risiko kredit terhadap kegiatan usaha BPR yang terkait dengan Risiko kredit paling sedikit mencakup kondisi keuanganatau laporan keuangan terakhir, hasil proyeksi arus kas, dan dokumen lain yang dapat digunakan untukmenganalisis kondisi dan kredibilitas debitur; <br>
                    • Penerapan Manajemen Risiko kredit dilakukan dengan memadai; dan <br>
                    • Penerapan Manajemen Risiko kredit dilakukan cukup konsisten. ',
                    3 => '• Telah melaksanakan proses Manajemen Risiko kredit meliputi identifikasi, pengukuran, pemantauan, danpengendalian Risiko kredit terhadap kegiatan usaha BPR yang terkaitdengan Risiko kredit paling  sedikit mencakup kondisi keuanganatau laporan keuangan terakhir, hasil proyeksi arus kas, dan dokumen lain yang dapat digunakan untukmenganalisis kondisi dan kredibilitas debitur; <br>
                    • Penerapan Manajemen Risiko kredit dilakukan dengan memadai; dan <br>
                    • Penerapan Manajemen Risiko kredit tidak dilakukan secara konsisten namun tidak menimbulkan dampak yang signifikan.',
                    4 => '• Telah melaksanakan proses Manajemen Risiko kredit namun tidak secara keseluruhan meliputi identifikasi, pengukuran, pemantauan, dan pengendalian Risiko kredit terhadap kegiatan usaha BPR yang terkait dengan Risiko kredit paling sedikit mencakup kondisi keuangan atau laporan keuangan terakhir, hasil proyeksi arus kas, dan dokumen lain yang dapat digunakan untuk menganalisis kondisi dan kredibilitas debitur; <br>
                    • Penerapan Manajemen Risiko kredit tidak memadai; dan <br>
                    • Penerapan Manajemen Risiko kredit tidak dilakukan secara konsisten sehingga menimbulkan dampak yang signifikan.',
                    5 => '• Tidak melaksanakan proses Manajemen Risiko kredit meliputi identifikasi, pengukuran, pemantauan, dan pengendalian Risiko kredit terhadap kegiatan usaha BPR yang terkait dengan Risiko kredit.'
                ],
                'catatan' => 'Peniliaian Risiko Kredit Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            29 => [
                'descriptions' => [
                    1 => '• Telah memiliki sistem informasi Manajemen Risiko yang mencerminkan Risiko kredit; <br>
                    • Data pada sisteminformasi Manajemen Risiko telah lengkap, akurat, kini, dan utuh; <br>
                    • Sistem informasi Manajemen Risiko mendukung Direksi dalam pengambilan keputusan; dan <br>
                    • Sistem informasi Manajemen Risiko sangat mendukung SKMR atau PEMR dalam pembuatan laporan kepada Direksi setiap semester.',
                    2 => '• Telah memiliki sistem informasi Manajemen Risiko yang mencerminkan Risiko kredit; <br>
                    • Data pada sisteminformasi Manajemen Risiko cukup lengkap, akurat, kini, dan utuh; <br>
                    • Sistem informasi Manajemen Risiko mendukung Direksi dalam pengambilan keputusan; dan <br>
                    • Sistem informasi Manajemen Risiko cukup mendukung SKMR atau PEMR dalam pembuatan laporan kepada Direksi setiap semester.',
                    3 => '• Telah memiliki sistem informasi Manajemen Risiko yang mencerminkan Risiko kredit; <br>
                    • Data pada sistem informasi Manajemen Risiko kurang lengkap, akurat, kini, dan utuh; <br>
                    • Sistem informasi Manajemen Risiko tidak sepenuhnya mendukung Direksi dalam pengambilan keputusan; dan  sistem informasi Manajemen Risiko kurang mendukung SKMR atau  PEMR dalam pembuatan laporan kepada Direksi setiap semester.',
                    4 => '• Telah memiliki sistem informasi Manajemen Risiko yang mencerminkan Risiko kredit; <br>
                    • Data pada sistem informasi Manajemen Risiko tidak lengkap, akurat, kini, dan utuh; <br>
                    • Sistem informasi Manajemen Risiko tidak sepenuhnya mendukung Direksi dalam pengambilan keputusan; dan <br>
                    • Sistem informasi Manajemen Risiko tidak mendukung SKMR atau PEMR dalam pembuatan laporan kepada Direksi setiap semester.',
                    5 => 'Tidak memiliki sistem informasi Manajemen Risikoyang mencerminkan Risiko kredit.'
                ],
                'catatan' => 'Peniliaian Risiko Kredit Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            30 => [
                'descriptions' => [
                    1 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Sangat Rendah',
                    2 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Rendah',
                    3 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Sedang',
                    4 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Tinggi',
                    5 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Sangat Tinggi'
                ],
                'catatan' => 'Peniliaian Risiko Kredit Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            31 => [
                'descriptions' => [
                    1 => '• SKAI atau PEAI telah melaksanakan audit intern terhadap penerapan Manajemen Risiko kredit, memberikan rekomendasi, dan melaporkan hasil audit intern kepada Direktur Utama; <br>
                    • audit intern telah dilaksanakan oleh SKAI atau PEAI meliputi pelaksanaan kebijakan dan prosedur Manajemen Risiko kredit dengan mempertimbang kan ketentuan serta kondisi BPR; dan <br> 
                    • hasil temuan audit intern yang dijadikan rekomendasi telah ditindaklanjuti.',
                    2 => '• SKAI atau PEAI telah melaksanakan audit intern terhadap penerapan Manajemen Risiko kredit, memberikan rekomendasi, dan melaporkan hasil audit internkepada Direktur Utama; <br>
                    • audit intern telahdilaksanakan oleh SKAI atau PEAI meliputi pelaksanaan kebijakan dan prosedur Manajemen Risiko kredit dengan mempertimbang kan ketentuan serta kondisi BPR; dan <br>
                    • hasil temuan audit intern yang dijadikan rekomendasi tidak sepenuhnya ditindaklanjuti namun tidak menimbulkan dampak yang signifikan.',
                    3 => '• SKAI atau PEAI telah melaksanakan audit intern terhadap penerapan Manajemen Risiko kredit, memberikan rekomendasi, dan melaporkan hasil audit intern kepada Direktur Utama; <br>
                    • audit intern telah dilaksanakan oleh SKAI atau PEAI meliputi pelaksanaan kebijakan dan prosedur Manajemen Risiko kredit dengan mempertimbang kan ketentuan serta kondisi BPR; dan <br>
                    • hasil temuan audit intern yang dijadikan rekomendasi tidak sepenuhnya ditindaklanjuti dan menimbulkan dampak yang signifikan.',
                    4 => '• SKAI atau PEAI telah melaksanakan audit intern terhadap penerapan Manajemen Risiko kredit, memberikan rekomendasi, dan melaporkan hasil audit intern kepada Direktur Utama; <br>
                    • audit intern telah dilaksanakan oleh SKAI atau PEAI namun tidak sesuai dengan cakupan pelaksanaan kebijakan dan prosedur Manajemen Risiko kredit; dan <br>
                    • hasil temuan audit intern yangdijadikan rekomendasi tidak ditindaklanjuti.',
                    5 => 'SKAI atau PEAI tidak melaksanakan audit intern terhadap penerapan Manajemen Risiko kredit.'
                ],
                'catatan' => 'Peniliaian Risiko Kredit Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            32 => [
                'descriptions' => [
                    1 => '• Seluruh jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kredit telah melaksanakan fungsi pengendalian intern dengan memerhatikan kebijakan Manajemen Risiko, prosedur Manajemen Risiko, serta penetapan limit Risiko kredit; <br>
                    • Terdapat kejelasan wewenang dan tanggung jawab dari masing- masing jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kredit; <br>
                    • SKMR atau PEMR terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kredit; dan <br>
                    • SKAI atau PEAI terpisah dari unityang berkaitan dengan aktivitas yang memiliki eksposur Risiko kredit.',
                    2 => '• Seluruh jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kredit telah melaksanakan fungsi pengendalian intern namun tidak sepenuhnya memerhatikan kebijakan Manajemen Risiko, prosedur Manajemen Risiko, serta penetapan limit Risiko kredit dan tidak berdampak signifikan; <br>
                    • Terdapat kejelasan wewenang dan tanggung jawab dari masing- masing jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kredit; <br>
                    • SKMR atau PEMR terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kredit; dan <br>
                    • SKAI atau PEAI terpisah dari unityang berkaitan dengan aktivitas yang memiliki eksposur Risiko kredit.',
                    3 => '• Seluruh jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kredit telah melaksanakan fungsi pengendalian intern namun tidak sepenuhnya memerhatikan kebijakan Manajemen Risiko, prosedur Manajemen Risiko, serta penetapan limit Risiko kredit dan berdampak signifikan; <br>
                    • Terdapat kejelasan wewenang dan tanggung jawab dari masing- masing jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kredit; <br>
                    • SKMR atau PEMR terpisah dari unit yang berkaitan dengan aktivitas yang  memiliki eksposur Risiko kredit; dan <br>
                    • SKAI atau PEAI terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kredit.',
                    4 => '• Tidak seluruh jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kredit telah melaksanakan fungsi pengendalian intern dengan memerhatikan kebijakan Manajemen Risiko, prosedur Manajemen Risiko, serta penetapan limit Risiko kredit dan berdampak sangat signifikan; <br>
                    • Tidak terdapat kejelasan wewenang dan tanggung jawab dari masing- masing jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kredit; <br>
                    • SKMR atau PEMR terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kredit; dan <br>
                    • SKAI atau PEAI terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kredit.',
                    5 => '• Seluruh jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kredit tidak melaksanakan fungsi pengendalian intern; <br>
                    • Tidak terdapat kejelasan wewenang dan tanggung jawab dari masing- masing jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kredit; <br>
                    • SKMR atau PEMR tidak terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kredit; dan <br>
                    • SKAI atau PEAI tidak terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kredit.'
                ],
                'catatan' => 'Peniliaian Risiko Kredit Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],
        ];
    }

    public function cekKomentarBaru()
    {
        $subkategori = 'KREDITKPMR';
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
            'KREDITKPMR',
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
            $this->db->table('risikokredit_kpmr')
                ->where('faktor1id', $faktor1id)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update($data);

            $categoryMapping = [
                16 => [17, 18, 19, 20, 21, 22],
                23 => [24, 25, 26],
                27 => [28, 29],
                30 => [31, 32]
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

            if (in_array($faktor1id, [16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32])) {
                $rataRata = $this->nilaiModel->hitungRataRata(33, $this->userKodebpr, $this->periodeId);
                $this->nilaiModel->insertOrUpdateRataRata($rataRata, 33, $this->userKodebpr, $this->periodeId);
            }

            $this->db->table('risikokredit_kpmr')
                ->where('faktor1id', 33)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update(['accdir2' => 0, 'is_approved' => 0]);

            $nilai13 = $this->nilaiModel->where([
                'faktor1id' => 33,
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
            $this->db->table('risikokredit_kpmr')
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
            $result = $this->db->table('risikokredit_kpmr')
                ->where('faktor1id', $faktor1id)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update($data);

            if ($result) {
                $this->db->table('risikokredit_kpmr')
                    ->where('faktor1id', 33)
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
            $this->db->table('risikokredit_kpmr')->insert($data);

            $categoryMapping = [
                16 => [17, 18, 19, 20, 21, 22],
                23 => [24, 25, 26],
                27 => [28, 29],
                30 => [31, 32]
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

            if (in_array($faktorId, [16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32])) {
                $rataRata = $this->nilaiModel->hitungRataRata(33, $this->userKodebpr, $this->periodeId);
                $this->nilaiModel->insertOrUpdateRataRata($rataRata, 33, $this->userKodebpr, $this->periodeId);
            }

            $nilai13 = $this->nilaiModel->where([
                'faktor1id' => 33,
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
            return redirect()->to(base_url('Risikokreditkpmr'));
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
                'subkategori' => "KREDITKPMR",
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
                        'KREDITKPMRs',
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

                return redirect()->to(base_url('Risikokreditkpmr') . '?modal_komentar=' . $faktorId)
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

        return redirect()->to(base_url('Risikokreditkpmr'));
    }

    public function approve($idNilai = null)
    {
        if ($idNilai === null) {
            return redirect()->back()->with('err', 'ID tidak valid');
        }

        $this->db->table('risikokredit_kpmr')
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
            ->whereIn('faktor1id', range(16, 33))
            ->first();

        if ($checkData['filled_count'] < 18) {
            return redirect()->back()->with('err', 'Semua faktor harus diisi terlebih dahulu');
        }

        try {
            $this->db->table('risikokredit_kpmr')
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->whereIn('faktor1id', range(16, 33))
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
            $this->db->table('risikokredit_kpmr')
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->whereIn('faktor1id', range(84, 102))
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

        $this->db->table('risikokredit_kpmr')
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
            return redirect()->to('/Risikokreditkpmr')->with('error', 'Data tidak lengkap');
        }

        $nilaiFaktor = $this->nilaiModel
            ->where('faktor1id', $faktor1id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if (!$nilaiFaktor) {
            return redirect()->to('/Risikokreditkpmr')->with('error', 'Data tidak ditemukan');
        }

        $dataUpdate = [
            'accdir2' => 0,
            'accdir2_by' => $this->userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->nilaiModel->update($nilaiFaktor['id'], $dataUpdate)) {
            return redirect()->to('/Risikokreditkpmr')->with('message', 'Persetujuan dibatalkan');
        }

        return redirect()->to('/Risikokreditkpmr')->with('error', 'Gagal memperbarui data');
    }

    public function recalculateCategoryAverage()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $faktorId = $this->request->getPost('faktor_id');

        $categoryMapping = [
            16 => [17, 18, 19, 20, 21, 22],
            23 => [24, 25, 26],
            27 => [28, 29],
            30 => [31, 32]
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

            $rataRata = $this->nilaiModel->hitungRataRata(33, $this->userKodebpr, $this->periodeId);
            $this->nilaiModel->insertOrUpdateRataRata($rataRata, 33, $this->userKodebpr, $this->periodeId);

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
            "KREDITKPMR",
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
            "KREDITKPMR",
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

        $faktorIds = range(16, 33);
        $counts = [];

        foreach ($faktorIds as $faktorId) {
            $count = $this->commentReadsModel->countUnreadCommentsForUserByFactor(
                $faktorId,
                "KREDITKPMR",
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

        $faktorId = 34;
        $penilaiankredit = $this->request->getPost('penilaiankredit');
        $user = $this->userModel->find($this->userId);

        $penjelasanMapping = [
            '1' => 'Tingkat Risiko Kredit KPMR: Sangat Rendah',
            '2' => 'Tingkat Risiko Kredit KPMR: Rendah',
            '3' => 'Tingkat Risiko Kredit KPMR: Sedang',
            '4' => 'Tingkat Risiko Kredit KPMR: Tinggi',
            '5' => 'Tingkat Risiko Kredit KPMR: Sangat Tinggi'
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
                    'keterangan' => 'Tingkat Risiko Kredit KPMR Posisi Sebelumnya',
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
                    return redirect()->to(base_url('Risikokreditkpmr'))
                        ->with('message', 'Data Tingkat Risiko Kredit KPMR berhasil diupdate');
                } else {
                    throw new \Exception('Gagal mengupdate data');
                }

            } else {
                $insertData = [
                    'faktor1id' => $faktorId,
                    'penilaiankredit' => $penilaiankredit,
                    'penjelasanpenilaian' => $penjelasanpenilaian,
                    'keterangan' => 'Tingkat Risiko Kredit KPMR Posisi Sebelumnya',
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
                    return redirect()->to(base_url('Risikokreditkpmr'))
                        ->with('message', 'Data Tingkat Risiko Kredit KPMR berhasil disimpan');
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
        $faktorId = 33;
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
            ->where('faktor1id', 33)
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

        $faktorId = 33;
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
                    return redirect()->to(base_url('Risikokreditkpmr'))
                        ->with('message', 'Kesimpulan berhasil diupdate');
                } else {
                    throw new \Exception('Gagal mengupdate kesimpulan');
                }

            } else {
                $rataRata = $this->nilaiModel->hitungRataRata($faktorId, $this->userKodebpr, $this->periodeId);
                $rataRata = ($rataRata - floor($rataRata) >= 0.5) ? ceil($rataRata) : floor($rataRata);

                $penjelasanMapping = [
                    '1' => 'Tingkat Risiko Kredit KPMR: Sangat Rendah',
                    '2' => 'Tingkat Risiko Kredit KPMR: Rendah',
                    '3' => 'Tingkat Risiko Kredit KPMR: Sedang',
                    '4' => 'Tingkat Risiko Kredit KPMR: Tinggi',
                    '5' => 'Tingkat Risiko Kredit KPMR: Sangat Tinggi'
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
                    return redirect()->to(base_url('Risikokreditkpmr'))
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
            ->where('faktor1id', 34)
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
                'id' => 16,
                'title' => 'Pilar Pengawasan Direksi dan Komisaris',
                'type' => 'category',
                'faktor_id' => 16,
                'faktor_ids' => [17, 18, 19, 20, 21, 22],
                'description' => 'Penilaian terhadap pengawasan direksi dan komisaris',
                'children' => [
                    [
                        'id' => 17,
                        'title' => 'Apakah Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko kredit <br> yang disusun oleh Direksi dan melakukan evaluasi secara berkala?',
                        'type' => 'parameter',
                        'faktor_id' => 17,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 18,
                        'title' => 'Apakah Dewan Komisaris telah melakukan evaluasi terhadap pertanggungjawaban Direksi atas <br> 
                        pelaksanaan kebijakan Manajemen Risiko kredit secara berkala dan memastikan tindak lanjut hasil <br> evaluasi dimaksud?',
                        'type' => 'parameter',
                        'faktor_id' => 18,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 19,
                        'title' => 'Apakah Direksi telah menyusun kebijakan <br> 
                        Manajemen Risiko kredit, melaksanakan secara konsisten, dan melakukan pengkinian secara berkala?',
                        'type' => 'parameter',
                        'faktor_id' => 19,
                        'previous_periode_faktor_id' => null
                    ]
                    ,
                    [
                        'id' => 20,
                        'title' => 'Apakah Direksi telah memiliki kemampuan untuk mengambil tindakan yang diperlukan dalam rangka <br> mitigasi 
                        risiko kredit, dan melakukan komunikasi kebijakan Manajemen Risiko kredit terhadap seluruh jenjang <br> organisasi BPR?',
                        'type' => 'parameter',
                        'faktor_id' => 20,
                        'previous_periode_faktor_id' => null
                    ]
                    ,
                    [
                        'id' => 21,
                        'title' => 'Apakah BPR telah memiliki kecukupan organisasi yang menangani fungsi kredit dan fungsi Manajemen <br>
                        Risiko kredit?',
                        'type' => 'parameter',
                        'faktor_id' => 21,
                        'previous_periode_faktor_id' => null
                    ]
                    ,
                    [
                        'id' => 22,
                        'title' => 'Apakah Direksi telah menerapkan kebijakan pengelolaan SDM dalam rangka penerapan Manajemen Risiko <br>
                        kredit?',
                        'type' => 'parameter',
                        'faktor_id' => 22,
                        'previous_periode_faktor_id' => null
                    ]
                ]
            ],
            [
                'id' => 23,
                'title' => 'Pilar Kecukupan Kebijakan, Prosedur, dan Limit',
                'type' => 'category',
                'faktor_id' => 23,
                'faktor_ids' => [24, 25, 26],
                'description' => 'Kecukupan Kebijakan, Prosedur, dan Limit',
                'children' => [
                    [
                        'id' => 24,
                        'title' => 'Apakah BPR telah memiliki kebijakan Manajemen Risiko kredit yang memadai dan disusun dengan <br> mempertimbangkan
                        visi, misi, skala usaha dan kompleksitas bisnis, serta kecukupan SDM?',
                        'type' => 'parameter',
                        'faktor_id' => 24,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 25,
                        'title' => 'Apakah BPR: <br>
                            ● Memiliki prosedur manajemen risiko kredit dan penetapan limit risiko kredit yang ditetapkan oleh Direksi; <br>
                            ● Melaksanakan prosedur Manajemen Risiko kredit dan penetapan limit risiko kredit secara konsisten untuk seluruh aktivitas; dan <br>
                            ● Melakukan evaluasi dan pengkinian terhadap prosedur Manajemen Risiko kredit dan penetapan limit risiko kredit secara berkala?',
                        'type' => 'parameter',
                        'faktor_id' => 25,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 26,
                        'title' => 'Apakah BPR telah memiliki kebijakan dan prosedur penerbitan produk dan/atau pelaksanaan aktivitas <br> 
                        baru yang mencakup
                        identifikasi dan mitigasi risiko kredit sesuai dengan ketentuan?',
                        'type' => 'parameter',
                        'faktor_id' => 26,
                        'previous_periode_faktor_id' => null
                    ]
                ]
            ],
            [
                'id' => 27,
                'title' => 'Kecukupan Proses dan Sistem Manajemen Risiko',
                'type' => 'category',
                'faktor_id' => 27,
                'faktor_ids' => [28, 29],
                'description' => 'Kecukupan Proses dan Sistem Manajemen Risiko',
                'children' => [
                    [
                        'id' => 28,
                        'title' => 'Apakah BPR  telah melaksanakan proses Manajemen Risiko kredit yang melekat pada kegiatan usaha <br>
                        BPR yang terkait dengan Risiko kredit?',
                        'type' => 'parameter',
                        'faktor_id' => 28,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 29,
                        'title' => 'Apakah BPR telah memiliki sistem informasi manajemen risiko yang mendukung Direksi dalam pengambilan <br>
                        keputusan terkait risiko kredit serta telah dilaporkan kepada Direksi secara berkala?',
                        'type' => 'parameter',
                        'faktor_id' => 29,
                        'previous_periode_faktor_id' => null
                    ]
                ]
            ],
            [
                'id' => 30,
                'title' => 'Sistem Pengendalian Internal yang Menyeluruh',
                'type' => 'category',
                'faktor_id' => 30,
                'faktor_ids' => [31, 32],
                'description' => 'Sistem Pengendalian Internal yang Menyeluruh',
                'children' => [
                    [
                        'id' => 31,
                        'title' => 'Apakah SKAI atau PEAI telah melaksanakan audit secara berkala terhadap penerapan manajemen risiko kredit,<br>
                        menyampaikan laporan hasil audit intern, dan memastikan tindaklanjut atas temuan pemeriksaan?',
                        'type' => 'parameter',
                        'faktor_id' => 31,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 32,
                        'title' => 'Apakah sistem pengendalian intern terhadap risiko kredit telah dilaksanakan oleh seluruh jenjang organisasi BPR?',
                        'faktor_id' => 32,
                        'previous_periode_faktor_id' => null
                    ]
                ]
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
            16 => [17, 18, 19, 20, 21, 22],
            23 => [24, 25, 26],
            27 => [28, 29],
            30 => [31, 32]
        ];

        foreach ($categoryMapping as $categoryId => $childrenIds) {
            if (in_array($changedFaktorId, $childrenIds)) {
                $this->calculateAndSaveCategoryAverage($categoryId, $childrenIds, $kodebpr, $periodeId);
                break;
            }
        }
    }

    public function exporttxtrisikokreditkpmr()
    {
        // Authentication check
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        // Get parameters
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        // Get periode detail
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

        // Fetch data
        $data_risikokreditkpmr = $this->nilaiModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        // BPR Info
        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'] ?? '';
            $kodejenis = $infobpr['kodejenis'] ?? '';
        }

        function sanitizeTxtKredKPMR($text)
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

        // Mapping kode per faktor1id
        $kodeMap = [
            16 => '1310',
            17 => '1311',
            18 => '1312',
            19 => '1313',
            20 => '1314',
            21 => '1315',
            22 => '1316',
            23 => '1320',
            24 => '1321',
            25 => '1322',
            26 => '1323',
            27 => '1330',
            28 => '1331',
            29 => '1332',
            30 => '1340',
            31 => '1341',
            32 => '1342',
            33 => '1350',
        ];

        $output = "";

        // Header
        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|PRBPRKS|0102|{$modalinti}|{$totalaset}|{$kantorcabang}|{$atmdebit}|{$kategori}||\r\n";

        usort($data_risikokreditkpmr, function ($a, $b) {
            return ($a['faktor1id'] ?? 0) <=> ($b['faktor1id'] ?? 0);
        });

        // Data rows
        foreach ($data_risikokreditkpmr as $row) {
            $faktorId = $row['faktor1id'] ?? null;
            if (!isset($kodeMap[$faktorId])) {
                continue; // <- skip data yang tidak punya kode
            }
            $kode = $kodeMap[$faktorId] ?? ''; // ambil kode sesuai faktor

            $penilaiankredit = sanitizeTxtKredKPMR($row['penilaiankredit']);
            $keterangan = sanitizeTxtKredKPMR($row['keterangan']);

            $output .= "D01|{$kode}|{$penilaiankredit}|{$keterangan}\r\n";
        }

        // Filename
        $filename = "PRBPRKS-0102-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        // Response
        $response = service('response');
        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response->setBody($output);
    }

}