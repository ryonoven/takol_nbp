<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_paramprofilrisiko;
use App\Models\M_user;
use App\Models\M_profilrisikocomments;
use App\Models\M_risikooperasionalkpmr;
use App\Models\M_infobpr;
use App\Models\M_periodeprofilresiko;
use App\Models\M_profilrisikocommentsread;
use App\Models\M_showprofilresiko;
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
        $this->nilaiModel = new M_risikooperasionalkpmr();
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
            'faktor1id' => 70,
            'kodebpr' => $this->userKodebpr,
            'periode_id' => $this->periodeId
        ])->first();

        $nilai14 = $this->nilaiModel->where([
            'faktor1id' => 71,
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
            ->whereIn('faktor1id', range(51, 70))
            ->first();

        $allFilled = ($approvalData['filled_count'] == 20); // 70-51 = 20
        $allApproved = ($allFilled && $approvalData['approved_count'] == 20);
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
            . view('risikooperasional/opskpmr', $data)
            . view('templates/v_footer');
    }

    private function checkCanApprove($nilaiLookup)
    {
        $requiredFaktorIds = range(51, 69);

        foreach ($requiredFaktorIds as $faktorId) {
            if (empty($nilaiLookup[$faktorId])) {
                return false;
            }
        }

        return true;
    }

    private function checkAccdekomApproved($nilaiLookup)
    {
        for ($faktorId = 51; $faktorId <= 69; $faktorId++) {
            if (!isset($nilaiLookup[$faktorId]) || $nilaiLookup[$faktorId]['is_approved'] != 1) {
                return false;
            }
        }
        return true;
    }

    private function checkAllApproved($nilaiLookup)
    {
        $requiredFaktorIds = range(51, 69);
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

        $nilaiData = $this->db->table('risikooperasional_kpmr as r')
            ->select('r.*, u.fullname')
            ->join('users as u', 'u.id = r.user_id', 'left')
            ->where('r.kodebpr', $this->userKodebpr)
            ->where('r.periode_id', $this->periodeId)
            ->whereIn('r.faktor1id', range(51, 69))
            ->get()
            ->getResultArray();

        $nilaiLookup = [];
        foreach ($nilaiData as $nilai) {
            $nilaiLookup[$nilai['faktor1id']] = $nilai;
        }

        $structure = [
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
                    ['id' => 57, 'title' => 'Apakah Direksi telah menerapkan kebijakan pengelolaan SDM dalam rangka penerapan Manajemen Risiko <br> operasional?', 'faktor_id' => 57]
                ]
            ],
            [
                'id' => 58,
                'title' => 'Pilar Kecukupan Kebijakan, Prosedur, dan Limit',
                'type' => 'category',
                'faktor_id' => 58,
                'children' => [
                    [
                        'id' => 59,
                        'title' => 'Apakah BPR telah memiliki kebijakan manajemen risiko operasional yang memadai dan disusun dengan <br> mempertimbangkan visi, misi, skala usaha dan kompleksitas bisnis, serta kecukupan SDM?',
                        'faktor_id' => 59
                    ],
                    [
                        'id' => 60,
                        'title' => 'Apakah BPR: 
                    ● Memiliki prosedur manajemen risiko operasional dan penetapan limit risiko operasional yang ditetapkan <br> oleh Direksi; 
                    ● Melaksanakan prosedur manajemen risiko operasional dan penetapan limit risiko operasional secara <br> konsisten untuk seluruh aktivitas; dan 
                    ● Melakukan evaluasi dan pengkinian terhadap prosedur manajemen risiko operasional dan penetapan <br> limit risiko operasional secara berkala?',
                        'faktor_id' => 60
                    ],
                    [
                        'id' => 61,
                        'title' => 'Apakah BPR telah memiliki kebijakan dan prosedur penerbitan produk dan/atau pelaksanaan aktivitas <br> baru yang mencakup identifikasi dan mitigasi risiko operasional sesuai dengan ketentuan?',
                        'faktor_id' => 61
                    ]
                ]
            ],
            [
                'id' => 62,
                'title' => 'Kecukupan Proses dan Sistem Manajemen Risiko',
                'type' => 'category',
                'faktor_id' => 62,
                'children' => [
                    [
                        'id' => 63,
                        'title' => 'Apakah BPR telah melaksanakan proses manajemen risiko operasional yang melekat pada kegiatan <br> usaha BPR?',
                        'faktor_id' => 63
                    ],
                    [
                        'id' => 64,
                        'title' => 'Apakah BPR telah memiliki sistem informasi manajemen risiko yang mendukung Direksi dalam <br> pengambilan <br> keputusan terkait risiko operasional serta telah dilaporkan kepada Direksi secara berkala?',
                        'faktor_id' => 64
                    ],
                    [
                        'id' => 65,
                        'title' => 'Apakah BPR telah memiliki kebijakan dan prosedur penyelenggaraan TI?',
                        'faktor_id' => 65
                    ],
                    [
                        'id' => 66,
                        'title' => 'Apakah BPR telah melakukan langkah mitigasi risiko terkait kejadian eksternal?',
                        'faktor_id' => 66
                    ]
                ]
            ],
            [
                'id' => 67,
                'title' => 'Sistem Pengendalian Internal yang Menyeluruh',
                'type' => 'category',
                'faktor_id' => 67,
                'children' => [
                    ['id' => 68, 'title' => 'Apakah SKAI atau PEAI telah melaksanakan audit secara berkala terhadap penerapan <br> manajemen risiko operasional, menyampaikan laporan hasil audit intern, dan memastikan <br> tindaklanjut atas temuan pemeriksaan?', 'faktor_id' => 68],
                    ['id' => 69, 'title' => 'Apakah sistem pengendalian intern terhadap risiko operasional telah dilaksanakan oleh <br> seluruh jenjang organisasi BPR?', 'faktor_id' => 69]
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
            51 => [
                'descriptions' => [
                    1 => 'Parameter Pengawasan Direksi dan Komisaris berada pada tingkat risiko yang Sangat Rendah',
                    2 => 'Parameter Pengawasan Direksi dan Komisaris berada pada tingkat risiko yang Rendah',
                    3 => 'Parameter Pengawasan Direksi dan Komisaris berada pada tingkat risiko yang Sedang',
                    4 => 'Parameter Pengawasan Direksi dan Komisaris berada pada tingkat risiko yang Tinggi',
                    5 => 'Parameter Pengawasan Direksi dan Komisaris berada pada tingkat risiko yang Sangat Tinggi'
                ],
                'catatan' => 'Peniliaian Risiko Operasional Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            52 => [
                'descriptions' => [
                    1 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko operasional <br>
                    • Dewan Komisaris telah melakukan evaluasi terhadap kebijakan Manajemen Risiko operasional <br>
                    • Evaluasi dilakukan oleh Dewan Komisaris secara berkala paling sedikit satu kali dalamsatu tahun atausewaktu-waktu dalam hal terdapat perubahan yangmemengaruhi kegiatan usaha BPR secara signifikan; dan <br>
                    • Evaluasi yang dilakukan relevan dengan kebutuhan penyesuaian kebijakan Manajemen Risiko operasional. ',
                    2 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko operasional; <br>
                    • Dewan Komisaris telah melakukan evaluasi terhadap kebijakan Manajemen Risiko operasional; dan <br> 
                    • Evaluasi dilakukan oleh Dewan Komisaris secara berkala paling sedikit satu kali dalam satu tahun atau sewaktu-waktu dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan',
                    3 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko operasional; <br>
                    • Dewan Komisaris telah melakukan evaluasi terhadap kebijakan Manajemen Risiko operasional; dan <br>
                    • Evaluasi tidak dilakukan oleh Dewan Komisaris secara berkala paling sedikit satu kali dalam satu tahun atau sewaktu-waktu dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan',
                    4 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko operasional; dan <br>
                    • Dewan Komisaris tidak melakukan evaluasi terhadap kebijakan Manajemen Risiko operasional.',
                    5 => '• Dewan Komisaris tidak memberikan persetujuan terhadap kebijakan Manajemen Risiko operasional; dan <br>
                    • Dewan Komisaris tidak melakukan evaluasi terhadap kebijakan Manajemen Risiko operasional.'
                ],
                'catatan' => 'Peniliaian Risiko Operasional Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            53 => [
                'descriptions' => [
                    1 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko operasional oleh Direksi; <br>
                    • Evaluasi oleh Dewan Komisaris dilakukan secara berkala setiap semester atau lebih berdasarkan laporan yang disampaikan Direksi dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan; <br>
                    • Dewan Komisaris telah memastikan tindak lanjut hasil evaluasi dalam setiap periode laporan; dan <br>
                    • Evaluasi yang diberikan relevan dengan pelaksanaan kebijakan Manajemen Risiko operasional dalam rangka mendukung perbaikan kinerja BPR.',
                    2 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko operasional oleh Direksi; <br>
                    • Evaluasi oleh Dewan Komisaris dilakukan secara berkala setiap semester atau lebih berdasarkan laporan yang disampaikan Direksi dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan; <br>
                    • Dewan Komisaris telah memastikan tindak lanjut hasil evaluasi dalam setiap periode laporan.',
                    3 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko operasional oleh Direksi; <br>
                     • Evaluasi oleh Dewan Komisaris dilakukan secara berkala setiap semester atau lebih berdasarkan laporan yang disampaikan Direksi dalam hal terdapat perubahan yangmemengaruhi kegiatan usaha BPR secara signifikan; dan <br> 
                     • Dewan Komisaris tidak memastikan tindak lanjut hasil evaluasi dalam setiap periode laporan.',
                    4 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko operasional oleh Direksi; <br> 
                    • Evaluasi oleh Dewan Komisaris tidak dilakukan secara berkala; dan <br>
                    • Dewan Komisaris tidak memastikan tindak lanjut hasil evaluasi dalam setiap periode pelaporan',
                    5 => 'Dewan Komisaris tidak melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko operasional oleh Direksi.'
                ],
                'catatan' => 'Peniliaian Risiko Operasional Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            54 => [
                'descriptions' => [
                    1 => '• Direksi telah menyusun kebijakan Manajemen Risiko operasional; <br>
                    • Menjalankan kegiatan usaha berdasarkan kebijakan Manajemen Risiko operasional yang telah ditetapkan; <br>
                    • Direksi melakukan pengkinian terhadap kebijakan Manajemen Risiko operasional dalam hal terdapat perubahan ketentuan peraturan perundangundangan, perubahan bisnis, dan hasilevaluasi kebijakan Manajemen Risiko oleh Dewan Komisaris; dan <br>
                    • Kebijakan Manajemen Risiko operasional yang dijalankan terbukti memitigasi terjadinya Risiko operasional.',
                    2 => '• Direksi telah menyusun kebijakan Manajemen Risiko operasional; <br>
                    • Menjalankan kegiatan usaha berdasarkan kebijakan Manajemen Risiko operasional yang telah ditetapkan; dan <br>
                    • Direksi melakukan pengkinian terhadap kebijakan Manajemen Risiko operasional dalam hal terdapat perubahan ketentuan peraturan perundangundangan, perubahan bisnis, dan hasil evaluasi kebijakan Manajemen Risiko oleh Dewan Komisaris.',
                    3 => '• Direksi telah menyusun kebijakan Manajemen Risiko operasional; <br>
                    • Menjalankan kegiatan usaha berdasarkan kebijakan Manajemen Risiko operasional yang telah ditetapkan; dan <br>
                    • Direksi tidak melakukan pengkinian terhadap kebijakan Manajemen Risiko operasional dalam hal terdapat perubahan ketentuan peraturan perundangundangan, perubahan bisnis, dan hasil evaluasi kebijakan Manajemen Risiko oleh Dewan Komisaris.',
                    4 => '• Direksi telah menyusun kebijakan Manajemen Risiko operasional; <br>
                    • Tidak menjalankan kegiatan usaha berdasarkan kebijakan Manajemen Risiko operasional yang telah ditetapkan; dan <br>
                    • Direksi tidak melakukan pengkinian terhadap kebijakan Manajemen Risiko operasional dalam hal terdapat perubahan ketentuan peraturan perundangundangan, perubahan bisnis, dan hasil evaluasi kebijakan Manajemen Risiko oleh Dewan Komisaris.',
                    5 => 'Direksi tidak menyusun kebijakan Manajemen Risiko operasional.'
                ],
                'catatan' => 'Peniliaian Risiko Operasional Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            55 => [
                'descriptions' => [
                    1 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko saat menjalankan kebijakan Manajemen Risiko operasional; <br>
                    • Direksi mengomunikasi kan kebijakan Manajemen Risiko operasional; dan <br>
                    • Seluruh jenjang organisasi BPR mampu memahami kebijakan Manajemen Risiko operasional yang diterapkan.',
                    2 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko saat menjalankan kebijakan Manajemen Risiko operasional; <br>
                    • Direksi mengomunikasi kan kebijakan Manajemen Risiko operasional; dan <br>
                    • Tidak seluruh jenjang organisasi BPR mampu memahami kebijakan Manajemen Risiko operasional yang diterapkan namun tidak menimbulkan dampak yang signifikan',
                    3 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko saat menjalankan kebijakan Manajemen Risiko operasional; <br>
                    • Direksi mengomunikasi kan kebijakan Manajemen Risiko operasional; dan <br>
                    • Tidak seluruh jenjang organisasi BPR mampu memahami kebijakan Manajemen Risiko operasional yang diterapkan dan menimbulkan dampak yang signifikan',
                    4 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko saat menjalankan kebijakan Manajemen Risiko operasional; <br>
                    • Direksi tidak mengomunikasi kan kebijakan Manajemen Risiko operasional; dan <br>
                    • Seluruh jenjang organisasi BPR tidak mampu memahami kebijakan Manajemen Risiko operasional yang diterapkan.',
                    5 => '• Direksi tidak mengambil tindakan yangdiperlukan untukmemitigasi Risikosaat menjalankan kebijakan Manajemen Risiko operasional; <br>
                    • Direksi tidak mengomunikasi kan kebijakan Manajemen Risiko operasional; dan <br>
                    • Seluruh jenjang organisasi BPR tidak mampu memahami kebijakan Manajemen Risiko operasional yang diterapkan.'
                ],
                'catatan' => 'Peniliaian Risiko Operasional Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            56 => [
                'descriptions' => [
                    1 => '• Memiliki unit kerja yang menangani fungsi operasional secara lengkap dan tidak terdapat rangkap jabatan yang dapat menyebabkan tidak terlaksananya tata kelola yangbaik. 
                    • Unit kerja yangmenangani fungsi operasional telah melaksanakan tugas dan wewenangnya sesuai dengan pedoman yang ditetapkan; dan 
                    • Memiliki SKMR atau PEMR danmampu melaksanakan fungsinya untuk memitigasi Risiko operasional.',
                    2 => '• Memiliki unit kerja yang menangani fungsi operasional namun tidak lengkap dan tidak terdapat rangkap jabatan yang dapat menyebabkan tidak terlaksananya tata kelola yang baik. <br>
                    • Unit kerja yang menangani fungsi operasional telah melaksanakan tugas dan wewenangnya sesuai dengan pedoman yang ditetapkan; dan <br>
                    • Memiliki SKMR atau PEMR dan mampu melaksanakan fungsinya untuk memitigasi Risiko operasional',
                    3 => '• Memiliki unit kerja yang menangani fungsi operasional namun tidak lengkap dan terdapat rangkap jabatan namun tidak menyebabkan tidak terlaksananya tata kelola yangbaik. <br>
                    • Unit kerja yangmenangani fungsi operasional telah melaksanakan tugas dan wewenangnya sesuai dengan pedoman yang ditetapkan; dan <br>
                    • Memiliki SKMR atau PEMR namun tidak mampu melaksanakan fungsinya untuk memitigasi Risiko operasional.',
                    4 => '• Memiliki unit kerja yang menangani fungsi operasional namun tidak lengkap dan terdapat rangkap jabatan yang dapat menyebabkan tidak terlaksananya tata kelola yang baik. <br>
                    • Unit kerja yang menangani fungsi operasional telah melaksanakan tugas dan wewenangnya sesuai dengan pedoman yang ditetapkan; dan <br>
                    • Memiliki SKMR atau PEMR namun tidak mampu melaksanakan fungsinya untuk memitigasi Risiko operasional.',
                    5 => '• Memiliki unit kerja yang menangani fungsi operasional namun tidak lengkap dan terdapat rangkap jabatan yang dapat menyebabkan tidak terlaksananya tata kelola yang baik. <br>
                    • Unit kerja yang menangani fungsi operasional tidak melaksanakan tugas dan wewenangnya sesuai dengan pedoman yang ditetapkan; dan <br>
                    • Memiliki SKMRatau PEMR namun tidak mampu melaksanakan fungsinya untukmemitigasi Risikokredit. '
                ],
                'catatan' => 'Peniliaian Risiko Operasional Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            57 => [
                'descriptions' => [
                    1 => '• Terdapat kesesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan; <br>
                    • Terdapat upaya peningkatan kompetensi SDM secara konsisten; dan <br>
                    • Tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi operasional sesuai dengan tugas dan tanggung jawab.',
                    2 => "• Terdapat ketidaksesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan namun tidak memberikan dampak yang signifikan; <br>
                    • Terdapat upaya peningkatan kompetensi SDM secara konsisten; dan <br>
                    • Tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi operasional sesuai dengan tugas dan tanggung jawab.",
                    3 => '• Terdapat ketidaksesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan namun tidak memberikan dampak yang signifikan; <br>
                    • Terdapat upaya peningkatan kompetensi SDM namun tidak secara konsisten; dan <br>
                    • Tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi operasional sesuai dengan tugas dan tanggung jawab.',
                    4 => '• Terdapat ketidaksesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan dan memberikan dampak yang signifikan; <br>
                    • Terdapat upaya peningkatan kompetensi SDM namun tidak secara konsisten; dan <br>
                    • Tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi operasional tidak sesuai dengan tugas dan tanggung jawab namun tidak memberikan dampak yang signifikan.',
                    5 => '• Terdapat ketidaksesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan dan memberikan dampak yang signifikan; <br>
                    • Tidak terdapat upaya peningkatan kompetensi SDM; dan <br>
                    • Tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi operasional tidak sesuai dengan tugas dan tanggung jawab dan memberikan dampak yang signifikan.'
                ],
                'catatan' => 'Peniliaian Risiko Operasional Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            58 => [
                'descriptions' => [
                    1 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Sangat Rendah',
                    2 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Rendah',
                    3 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Sedang',
                    4 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Tinggi',
                    5 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Sangat Tinggi'
                ],
                'catatan' => 'Peniliaian Risiko Operasional Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            59 => [
                'descriptions' => [
                    1 => '• Telah memiliki kebijakan Manajemen Risiko operasional; <br>
                    • Terdapat kesesuaian antara substansi kebijakan Manajemen Risiko operasional dengan ketentuan Manajemen Risiko BPR antara lain memiliki strategi Manajemen Risiko, kriteria pemberian operasional yang sehat, serta penetapan sistem informasi Manajemen Risiko untuk Risiko operasional; dan <br>
                    • Terdapat keselarasan antara kebijakan Manajemen Risiko operasional dengan visi, misi, skala usaha, dan kompleksitas bisnis, serta kecukupan SDM dalam menetapkan kebijakan Manajemen Risiko operasional.',
                    2 => "• Telah memiliki kebijakan Manajemen Risiko operasional; <br>
                    • Terdapat ketidaksesuaian yang tidak signifikan antara substansi kebijakan Manajemen Risiko operasional dengan ketentuan Manajemen Risiko BPR antara lain memiliki strategi Manajemen Risiko, kriteria pemberian operasional yang sehat, serta penetapan sistem informasi Manajemen Risiko untuk Risiko operasional; dan <br>
                    • Terdapat keselarasan antara kebijakan Manajemen Risiko operasional dengan visi, misi, skala usaha, dan kompleksitas bisnis, serta kecukupan SDM dalam menetapkan kebijakan Manajemen Risiko operasional.",
                    3 => '• Telah memiliki kebijakan Manajemen Risiko operasional; <br>
                    • Terdapat ketidaksesuaian yang tidak signifikan antara substansi kebijakan Manajemen Risiko operasional dengan ketentuan Manajemen Risiko BPR antara lain memiliki strategi Manajemen Risiko, kriteria pemberian operasional yang sehat, serta penetapan sistem informasi Manajemen Risiko untuk Risiko operasional; dan <br> 
                    • Terdapat ketidakselarasan antara kebijakan Manajemen Risiko operasional dengan visi, misi, skala usaha, dan kompleksitas bisnis, serta kecukupan SDM dalam menetapkan kebijakan Manajemen Risiko operasional, namun tidak menimbulkan dampak yang signifikan.',
                    4 => '• Telah memiliki kebijakan Manajemen Risiko operasional; <br>
                    • Terdapat ketidaksesuaian yang signifikan antara substansi kebijakan Manajemen Risiko operasional dengan ketentuan Manajemen Risiko BPR antara lain memiliki strategi Manajemen Risiko, kriteria pemberian operasional yang sehat, serta penetapan sistem informasi Manajemen Risiko untuk Risiko operasional; dan <br>
                    • Terdapat ketidakselarasan antara kebijakan Manajemen Risiko operasional dengan visi, misi, skala usaha, dan kompleksitas  bisnis, serta kecukupan SDM dalam menetapkan kebijakan Manajemen Risiko operasional dan menimbulkan dampak yang signifikan.',
                    5 => 'Tidak memiliki kebijakan Manajemen Risiko operasional.'
                ],
                'catatan' => 'Peniliaian Risiko Operasional Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            60 => [
                'descriptions' => [
                    1 => '• Memiliki prosedur Manajemen Risiko operasional dan penetapan limit Risiko operasional yang ditetapkan oleh Direksi paling sedikit meliputi jenjang delegasi wewenang dan pertanggung jawaban yang jelas serta terdokumentasi  dengan baik sehingga memudahkan keperluan jejak audit untuk keperluan pengendalian intern; <br>
                    • Melaksanakan prosedur Manajemen Risiko operasional danpenetapan limit Risiko operasional dalam setiap aktivitas fungsional secara konsisten; dan <br>
                    • Melakukan evaluasi dan pengkinian prosedur Manajemen Risiko operasional danpenetapan limit Risiko operasional dalam hal terdapat  perubahan bisnis yang signifikan dan/atau ketentuan peraturan perundangundangan.',
                    2 => '• Memiliki prosedur Manajemen Risiko operasional dan penetapan limit Risiko operasional yang ditetapkan oleh Direksi paling sedikit meliputi jenjang delegasi wewenang dan pertanggung jawaban yang jelas serta terdokumentasi dengan baik sehingga memudahkan keperluan jejak audit untuk keperluan pengendalian intern; <br>
                    • Melaksanakan prosedur Manajemen Risiko operasional dan penetapan limit Risiko operasional dalam setiap aktivitas fungsional secara konsisten; dan <br>
                    • Tidak melakukan evaluasi dan pengkinian prosedur Manajemen Risiko operasional dan penetapan limit Risiko operasional dalam hal terdapat  perubahan bisnis yang signifikan dan/atau ketentuan peraturan perundangundangan, namun tidak menimbulkan dampak yang signifikan.',
                    3 => '• Memiliki prosedur Manajemen Risiko operasional dan penetapan limit Risiko operasional yang ditetapkan oleh Direksi paling sedikit meliputi jenjang delegasi wewenang dan pertanggung jawaban yang jelas serta terdokumentasi dengan baik sehingga memudahkan keperluan jejak audit untuk keperluan pengendalian intern; <br>
                    • Melaksanakan prosedur Manajemen Risiko operasional dan penetapan limit Risiko operasional dalam setiap aktivitas fungsional secara konsisten; dan <br>
                    • Tidak melakukan evaluasi dan pengkinian prosedur Manajemen Risiko operasional dan penetapan limit Risiko operasional dalam hal terdapat  perubahan bisnis yang signifikan dan/atau ketentuan peraturan perundangundangan, dan menimbulkan dampak yang signifikan.',
                    4 => '• Memiliki prosedur Manajemen Risiko operasional dan penetapan limit Risiko operasional yang ditetapkan oleh Direksi paling sedikit meliputi jenjang delegasi wewenang dan pertanggung jawaban yang jelas serta terdokumentasi  dengan baik sehingga memudahkan keperluan jejak audit untuk keperluan pengendalian intern; <br>
                    • Tidak melaksanakan prosedur Manajemen Risiko operasional danpenetapan limit Risiko operasional dalam setiap aktivitas fungsional secara konsisten; dan <br> 
                    • Tidak melakukan evaluasi dan pengkinian prosedur Manajemen Risiko operasional danpenetapan limit Risiko operasional dalam hal  terdapat perubahan bisnis yang signifikan dan/atau ketentuan peraturan perundangundangan.',
                    5 => '• tidak memiliki prosedur Manajemen Risikokredit dan penetapan limit Risiko operasional yangditetapkan oleh Direksi.'
                ],
                'catatan' => 'Peniliaian Risiko Operasional Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            61 => [
                'descriptions' => [
                    1 => '• Memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru yang memiliki eksposur Risiko operasional; <br>
                    • Menerapkan kebijakan dan prosedur dalam hal terdapat penerbitan produk atau pelaksanaan aktivitas baru; dan <br>
                    • Terdapat kesesuaian antara kebijakan dan prosedur produk dan/atau aktivitas baru dengan ketentuan.',
                    2 => '• Memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru yang memiliki eksposur Risiko operasional; <br>
                    • Menerapkan kebijakan dan prosedur dalam hal terdapat penerbitan produk atau pelaksanaan aktivitas baru; dan <br>
                    • Terdapat ketidaksesuaian yang tidak signifikan antara kebijakan dan prosedur produk dan/atau aktivitas baru dengan ketentuan.',
                    3 => '• Memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru yang memiliki eksposur Risiko operasional; <br>
                    • Menerapkan kebijakan dan prosedur dalam hal terdapat penerbitan produk atau pelaksanaan aktivitas baru; dan <br>
                    • Terdapat ketidaksesuaian yang signifikan antara kebijakan dan prosedur produk dan/atau aktivitas baru dengan ketentuan.',
                    4 => '• Memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atauaktivitas baru yang memiliki eksposur Risiko operasional; <br>
                    • Tidak menerapkan kebijakan dan prosedur dalam hal terdapat penerbitan produk atau pelaksanaan aktivitas baru; dan <br>
                    • Terdapat ketidaksesuaian yang signifikan antara kebijakan dan prosedur produk dan/atau aktivitas baru dengan ketentuan.',
                    5 => 'Tidak memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru yang memiliki eksposur Risiko operasional.'
                ],
                'catatan' => 'Peniliaian Risiko Operasional Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            62 => [
                'descriptions' => [
                    1 => 'Parameter Kecukupan Proses dan Sistem Manajemen Risiko berada pada tingkat risiko yang Sangat Rendah',
                    2 => 'Parameter Kecukupan Proses dan Sistem Manajemen Risiko berada pada tingkat risiko yang Rendah',
                    3 => 'Parameter Kecukupan Proses dan Sistem Manajemen Risiko berada pada tingkat risiko yang Sedang',
                    4 => 'Parameter Kecukupan Proses dan Sistem Manajemen Risiko berada pada tingkat risiko yang Tinggi',
                    5 => 'Parameter Kecukupan Proses dan Sistem Manajemen Risiko berada pada tingkat risiko yang Sangat Tinggi'
                ],
                'catatan' => 'Peniliaian Risiko Operasional Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            63 => [
                'descriptions' => [
                    1 => '• Telah melaksanakan proses Manajemen Risiko operasional meliputi identifikasi, pengukuran, pemantauan, dan pengendalian Risiko operasional terhadap kegiatan usaha BPR yang terkait dengan Risiko operasional paling  sedikit mencakup kondisi keuangan atau laporan keuangan terakhir, hasil proyeksi arus kas, dan dokumen lain yang dapat digunakan untuk menganalisis kondisi dan kredibilitas debitur; <br>
                    • Penerapan Manajemen Risiko operasional dilakukan dengan sangat memadai; dan <br>
                    • Penerapan Manajemen Risiko operasional dilakukan secara konsisten. ',
                    2 => '• Telah melaksanakan proses Manajemen Risiko operasional meliputi identifikasi, pengukuran, pemantauan, dan pengendalian Risiko operasional terhadap kegiatan usaha BPR yang terkait dengan Risiko operasional paling sedikit mencakup kondisi keuanganatau laporan keuangan terakhir, hasil proyeksi arus kas, dan dokumen lain yang dapat digunakan untukmenganalisis kondisi dan kredibilitas debitur; <br>
                    • Penerapan Manajemen Risiko operasional dilakukan dengan memadai; dan <br>
                    • Penerapan Manajemen Risiko operasional dilakukan cukup konsisten. ',
                    3 => '• Telah melaksanakan proses Manajemen Risiko operasional meliputi identifikasi, pengukuran, pemantauan, danpengendalian Risiko operasional terhadap kegiatan usaha BPR yang terkaitdengan Risiko operasional paling  sedikit mencakup kondisi keuanganatau laporan keuangan terakhir, hasil proyeksi arus kas, dan dokumen lain yang dapat digunakan untukmenganalisis kondisi dan kredibilitas debitur; <br>
                    • Penerapan Manajemen Risiko operasional dilakukan dengan memadai; dan <br>
                    • Penerapan Manajemen Risiko operasional tidak dilakukan secara konsisten namun tidak menimbulkan dampak yang signifikan.',
                    4 => '• Telah melaksanakan proses Manajemen Risiko operasional namun tidak secara keseluruhan meliputi identifikasi, pengukuran, pemantauan, dan pengendalian Risiko operasional terhadap kegiatan usaha BPR yang terkait dengan Risiko operasional paling sedikit mencakup kondisi keuangan atau laporan keuangan terakhir, hasil proyeksi arus kas, dan dokumen lain yang dapat digunakan untuk menganalisis kondisi dan kredibilitas debitur; <br>
                    • Penerapan Manajemen Risiko operasional tidak memadai; dan <br>
                    • Penerapan Manajemen Risiko operasional tidak dilakukan secara konsisten sehingga menimbulkan dampak yang signifikan.',
                    5 => '• Tidak melaksanakan proses Manajemen Risiko operasional meliputi identifikasi, pengukuran, pemantauan, dan pengendalian Risiko operasional terhadap kegiatan usaha BPR yang terkait dengan Risiko operasional.'
                ],
                'catatan' => 'Peniliaian Risiko Operasional Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            64 => [
                'descriptions' => [
                    1 => '• Telah memiliki sistem informasi Manajemen Risiko yang mencerminkan Risiko operasional; <br>
                    • Data pada sisteminformasi Manajemen Risiko telah lengkap, akurat, kini, dan utuh; <br>
                    • Sistem informasi Manajemen Risiko mendukung Direksi dalam pengambilan keputusan; dan <br>
                    • Sistem informasi Manajemen Risiko sangat mendukung SKMR atau PEMR dalam pembuatan laporan kepada Direksi setiap semester.',
                    2 => '• Telah memiliki sistem informasi Manajemen Risiko yang mencerminkan Risiko operasional; <br>
                    • Data pada sisteminformasi Manajemen Risiko cukup lengkap, akurat, kini, dan utuh; <br>
                    • Sistem informasi Manajemen Risiko mendukung Direksi dalam pengambilan keputusan; dan <br>
                    • Sistem informasi Manajemen Risiko cukup mendukung SKMR atau PEMR dalam pembuatan laporan kepada Direksi setiap semester.',
                    3 => '• Telah memiliki sistem informasi Manajemen Risiko yang mencerminkan Risiko operasional; <br>
                    • Data pada sistem informasi Manajemen Risiko kurang lengkap, akurat, kini, dan utuh; <br>
                    • Sistem informasi Manajemen Risiko tidak sepenuhnya mendukung Direksi dalam pengambilan keputusan; dan  sistem informasi Manajemen Risiko kurang mendukung SKMR atau  PEMR dalam pembuatan laporan kepada Direksi setiap semester.',
                    4 => '• Telah memiliki sistem informasi Manajemen Risiko yang mencerminkan Risiko operasional; <br>
                    • Data pada sistem informasi Manajemen Risiko tidak lengkap, akurat, kini, dan utuh; <br>
                    • Sistem informasi Manajemen Risiko tidak sepenuhnya mendukung Direksi dalam pengambilan keputusan; dan <br>
                    • Sistem informasi Manajemen Risiko tidak mendukung SKMR atau PEMR dalam pembuatan laporan kepada Direksi setiap semester.',
                    5 => 'Tidak memiliki sistem informasi Manajemen Risikoyang mencerminkan Risiko operasional.'
                ],
                'catatan' => 'Peniliaian Risiko Operasional Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            65 => [
                'descriptions' => [
                    1 => '• Telah memiliki kebijakan dan prosedur penyelenggaraan TI yang telah ditetapkan oleh Direksi;<br>
                    • Telah menjalankan kegiatan operasional sesuai dengan kebijakan dan prosedur penyelenggaraan TI; dan <br>
                    • Telah memiliki aspek pengamanan TI sesuai dengan ketentuan Otoritas Jasa Keuangan.',
                    2 => '• Telah memiliki kebijakan dan prosedur penyelenggaraan TI yang telah ditetapkan oleh Direksi;;<br>
                    • Telah menjalankan kegiatan operasional namun tidak sepenuhnya sesuai dengan kebijakan dan prosedur penyelenggaraan TI dan tidak menimbulkan dampak yang signifikan; dan <br>
                    • Telah memiliki aspek pengamanan TI sesuai dengan ketentuan Otoritas Jasa Keuangan.',
                    3 => '• Telah memiliki kebijakan dan prosedur penyelenggaraan TI yang telah ditetapkan oleh Direksi;<br>
                    • Telah menjalankan kegiatan operasional namun tidak sepenuhnya sesuai dengan kebijakan dan prosedur penyelenggaraan TI dan menimbulkan dampak yang signifikan; dan <br>
                    • Telah memiliki aspek pengamanan TI sesuai dengan ketentuan Otoritas Jasa Keuangan.',
                    4 => '• Telah memiliki kebijakan dan prosedur penyelenggaraan TI yang telah ditetapkan oleh Direksi;<br>
                    • Telah menjalankan kegiatan operasional namun tidak sepenuhnya sesuai dengan kebijakan dan prosedur penyelenggaraan TI dan menimbulkan dampak yang signifikan; dan <br>
                    • Telah memiliki aspek pengamanan TI tidak sesuai dengan ketentuan Otoritas Jasa Keuangan.',
                    5 => 'Tidak memiliki kebijakan dan prosedur penyelenggaraan TI yang telah ditetapkan oleh Direksi.'
                ],
                'catatan' => 'Peniliaian Risiko Operasional Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            66 => [
                'descriptions' => [
                    1 => '• Telah memiliki antara lain rekam cadang, sumber listrik cadangan, jaringan komunikasi alternatif, dan pusat pemulihan bencana sesuai dengan ketentuan Otoritas Jasa Keuangan; <br>
                    • Telah memiliki rencana pemulihan bencana sesuai dengan ketentuan Otoritas Jasa Keuangan; dan<br> 
                    • Telah melakukan uji coba terhadap rencana pemulihan bencana sesuai dengan ketentuan Otoritas Jasa Keuangan.',
                    2 => '• Telah memiliki antara lain rekam cadang, sumber listrik cadangan, jaringan komunikasi alternatif, dan pusat pemulihan bencana sesuai dengan ketentuan Otoritas Jasa Keuangan; <br>
                    • Telah memiliki rencana pemulihan bencana sesuai dengan ketentuan Otoritas Jasa Keuangan; dan<br> 
                    • Telah melakukan uji coba terhadap rencana pemulihan bencana namun tidak sepenuhnya sesuai dengan ketentuan Otoritas Jasa Keuangan.',
                    3 => '• Telah memiliki antara lain rekam cadang, sumber listrik cadangan, jaringan komunikasi alternatif, dan pusat pemulihan bencana sesuai dengan ketentuan Otoritas Jasa Keuangan; <br>
                    • Telah memiliki rencana pemulihan bencana namun tidak sepenuhnya sesuai dengan ketentuan Otoritas Jasa Keuangan dan tidak menimbulkan dampak yang signifikan; dan<br> 
                    • Telah melakukan uji coba terhadap rencana pemulihan bencana namun tidak sepenuhnya sesuai dengan ketentuan Otoritas Jasa Keuangan.',
                    4 => '• Telah memiliki antara lain rekam cadang, sumber listrik cadangan, jaringan komunikasi alternatif, dan pusat pemulihan bencana sesuai dengan ketentuan Otoritas Jasa Keuangan; <br>
                    • Telah memiliki rencana pemulihan bencana namun tidak sepenuhnya sesuai dengan ketentuan Otoritas Jasa Keuangan dan menimbulkan dampak yang signifikan; dan<br> 
                    • Telah melakukan uji coba terhadap rencana pemulihan bencana namun tidak sepenuhnya sesuai dengan ketentuan Otoritas Jasa Keuangan.',
                    5 => '• Tidak memiliki antara lain rekam cadang, sumber listrik cadangan, jaringan komunikasi alternatif, dan pusat pemulihan bencana sesuai dengan ketentuan Otoritas Jasa Keuangan; <br>
                    • Telah memiliki rencana pemulihan bencana namun tidak sepenuhnya sesuai dengan ketentuan Otoritas Jasa Keuangan dan menimbulkan dampak yang signifikan; dan<br>
                    • Telah melakukan uji coba terhadap rencana pemulihan bencana namun tidak sepenuhnya sesuai dengan ketentuan Otoritas Jasa Keuangan.'
                ],
                'catatan' => 'Peniliaian Risiko Operasional Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            67 => [
                'descriptions' => [
                    1 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Sangat Rendah',
                    2 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Rendah',
                    3 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Sedang',
                    4 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Tinggi',
                    5 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Sangat Tinggi'
                ],
                'catatan' => 'Peniliaian Risiko Operasional Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            68 => [
                'descriptions' => [
                    1 => '• SKAI atau PEAI telah melaksanakan audit intern terhadap penerapan Manajemen Risiko operasional, memberikan rekomendasi, dan melaporkan hasil audit intern kepada Direktur Utama; <br>
                    • Audit intern telah dilaksanakan oleh SKAI atau PEAI meliputi pelaksanaan kebijakan dan prosedur Manajemen Risiko operasional dengan mempertimbang kan ketentuan serta kondisi BPR; dan <br> 
                    • Hasil temuan audit intern yang dijadikan rekomendasi telah ditindaklanjuti.',
                    2 => '• SKAI atau PEAI telah melaksanakan audit intern terhadap penerapan Manajemen Risiko operasional, memberikan rekomendasi, dan melaporkan hasil audit internkepada Direktur Utama; <br>
                    • Audit intern telahdilaksanakan oleh SKAI atau PEAI meliputi pelaksanaan kebijakan dan prosedur Manajemen Risiko operasional dengan mempertimbang kan ketentuan serta kondisi BPR; dan <br>
                    • Hasil temuan audit intern yang dijadikan rekomendasi tidak sepenuhnya ditindaklanjuti namun tidak menimbulkan dampak yang signifikan.',
                    3 => '• SKAI atau PEAI telah melaksanakan audit intern terhadap penerapan Manajemen Risiko operasional, memberikan rekomendasi, dan melaporkan hasil audit intern kepada Direktur Utama; <br>
                    • Audit intern telah dilaksanakan oleh SKAI atau PEAI meliputi pelaksanaan kebijakan dan prosedur Manajemen Risiko operasional dengan mempertimbang kan ketentuan serta kondisi BPR; dan <br>
                    • Hasil temuan audit intern yang dijadikan rekomendasi tidak sepenuhnya ditindaklanjuti dan menimbulkan dampak yang signifikan.',
                    4 => '• SKAI atau PEAI telah melaksanakan audit intern terhadap penerapan Manajemen Risiko operasional, memberikan rekomendasi, dan melaporkan hasil audit intern kepada Direktur Utama; <br>
                    • Audit intern telah dilaksanakan oleh SKAI atau PEAI namun tidak sesuai dengan cakupan pelaksanaan kebijakan dan prosedur Manajemen Risiko operasional; dan <br>
                    • Hasil temuan audit intern yangdijadikan rekomendasi tidak ditindaklanjuti.',
                    5 => 'SKAI atau PEAI tidak melaksanakan audit intern terhadap penerapan Manajemen Risiko operasional.'
                ],
                'catatan' => 'Peniliaian Risiko Operasional Kualitas Penerapan Manajemen Risiko (KPMR)'
            ],

            69 => [
                'descriptions' => [
                    1 => '• Seluruh jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko operasional telah melaksanakan fungsi pengendalian intern dengan memerhatikan kebijakan Manajemen Risiko, prosedur Manajemen Risiko, serta penetapan limit Risiko operasional; <br>
                    • Terdapat kejelasan wewenang dan tanggung jawab dari masing- masing jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko operasional; <br>
                    • SKMR atau PEMR terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko operasional; dan <br>
                    • SKAI atau PEAI terpisah dari unityang berkaitan dengan aktivitas yang memiliki eksposur Risiko operasional.',
                    2 => '• Seluruh jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko operasional telah melaksanakan fungsi pengendalian intern namun tidak sepenuhnya memerhatikan kebijakan Manajemen Risiko, prosedur Manajemen Risiko, serta penetapan limit Risiko operasional dan tidak berdampak signifikan; <br>
                    • Terdapat kejelasan wewenang dan tanggung jawab dari masing- masing jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko operasional; <br>
                    • SKMR atau PEMR terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko operasional; dan <br>
                    • SKAI atau PEAI terpisah dari unityang berkaitan dengan aktivitas yang memiliki eksposur Risiko operasional.',
                    3 => '• Seluruh jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko operasional telah melaksanakan fungsi pengendalian intern namun tidak sepenuhnya memerhatikan kebijakan Manajemen Risiko, prosedur Manajemen Risiko, serta penetapan limit Risiko operasional dan berdampak signifikan; <br>
                    • Terdapat kejelasan wewenang dan tanggung jawab dari masing- masing jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko operasional; <br>
                    • SKMR atau PEMR terpisah dari unit yang berkaitan dengan aktivitas yang  memiliki eksposur Risiko operasional; dan <br>
                    • SKAI atau PEAI terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko operasional.',
                    4 => '• Tidak seluruh jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko operasional telah melaksanakan fungsi pengendalian intern dengan memerhatikan kebijakan Manajemen Risiko, prosedur Manajemen Risiko, serta penetapan limit Risiko operasional dan berdampak sangat signifikan; <br>
                    • Tidak terdapat kejelasan wewenang dan tanggung jawab dari masing- masing jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko operasional; <br>
                    • SKMR atau PEMR terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko operasional; dan <br>
                    • SKAI atau PEAI terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko operasional.',
                    5 => '• Seluruh jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko operasional tidak melaksanakan fungsi pengendalian intern; <br>
                    • Tidak terdapat kejelasan wewenang dan tanggung jawab dari masing- masing jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko operasional; <br>
                    • SKMR atau PEMR tidak terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko operasional; dan <br>
                    • SKAI atau PEAI tidak terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko operasional.'
                ],
                'catatan' => 'Peniliaian Risiko Operasional Kualitas Penerapan Manajemen Risiko (KPMR)'
            ]
        ];
    }

    public function cekKomentarBaru()
    {
        $subkategori = 'OPERASIONALKPMR';
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
            'OPERASIONALKPMR',
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
            $this->db->table('risikooperasional_kpmr')
                ->where('faktor1id', $faktor1id)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update($data);

            $categoryMapping = [
                51 => [52, 53, 54, 55, 56, 57],
                58 => [59, 60, 61],
                62 => [63, 64, 65, 66],
                67 => [68, 69]
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

            if (in_array($faktor1id, [51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63, 64, 65, 66, 67, 68, 69])) {
                $rataRata = $this->nilaiModel->hitungRataRata(70, $this->userKodebpr, $this->periodeId);
                $this->nilaiModel->insertOrUpdateRataRata($rataRata, 70, $this->userKodebpr, $this->periodeId);
            }

            $this->db->table('risikooperasional_kpmr')
                ->where('faktor1id', 70)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update(['accdir2' => 0, 'is_approved' => 0]);

            $nilai13 = $this->nilaiModel->where([
                'faktor1id' => 70,
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
            $this->db->table('risikooperasional_kpmr')
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
            $result = $this->db->table('risikooperasional_kpmr')
                ->where('faktor1id', $faktor1id)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update($data);

            if ($result) {
                $this->db->table('risikooperasional_kpmr')
                    ->where('faktor1id', 70)
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
            $this->db->table('risikooperasional_kpmr')->insert($data);

            $categoryMapping = [
                51 => [52, 53, 54, 55, 56, 57],
                58 => [59, 60, 61],
                62 => [63, 64, 65, 66],
                67 => [68, 69]
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

            if (in_array($faktorId, [51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63, 64, 65, 66, 67, 68, 69])) {
                $rataRata = $this->nilaiModel->hitungRataRata(70, $this->userKodebpr, $this->periodeId);
                $this->nilaiModel->insertOrUpdateRataRata($rataRata, 70, $this->userKodebpr, $this->periodeId);
            }

            $nilai13 = $this->nilaiModel->where([
                'faktor1id' => 70,
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
            return redirect()->to(base_url('Risikooperasionalkpmr'));
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
                'subkategori' => "OPERASIONALKPMR",
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
                        'OPERASIONALKPMR',
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

                return redirect()->to(base_url('Risikooperasional') . '?modal_komentar=' . $faktorId)
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

        return redirect()->to(base_url('Risikooperasionalkpmr'));
    }

    public function approve($idNilai = null)
    {
        if ($idNilai === null) {
            return redirect()->back()->with('err', 'ID tidak valid');
        }

        $this->db->table('risikooperasional_kpmr')
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
            ->whereIn('faktor1id', range(51, 70))
            ->first();

        if ($checkData['filled_count'] < 20) {
            return redirect()->back()->with('err', 'Semua faktor harus diisi terlebih dahulu');
        }

        try {
            $this->db->table('risikooperasional_kpmr')
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->whereIn('faktor1id', range(51, 70))
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
            $this->db->table('risikooperasional_kpmr')
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->whereIn('faktor1id', range(51, 70))
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

        $this->db->table('risikooperasional_kpmr')
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
            return redirect()->to('/Risikooperasionalkpmr')->with('error', 'Data tidak lengkap');
        }

        $nilaiFaktor = $this->nilaiModel
            ->where('faktor1id', $faktor1id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if (!$nilaiFaktor) {
            return redirect()->to('/Risikooperasionalkpmr')->with('error', 'Data tidak ditemukan');
        }

        $dataUpdate = [
            'accdir2' => 0,
            'accdir2_by' => $this->userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->nilaiModel->update($nilaiFaktor['id'], $dataUpdate)) {
            return redirect()->to('/Risikooperasionalkpmr')->with('message', 'Persetujuan dibatalkan');
        }

        return redirect()->to('/Risikooperasionalkpmr')->with('error', 'Gagal memperbarui data');
    }

    public function recalculateCategoryAverage()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $faktorId = $this->request->getPost('faktor_id');

        $categoryMapping = [
            51 => [52, 53, 54, 55, 56, 57],
            58 => [59, 60, 61],
            62 => [63, 64, 65, 66],
            67 => [68, 69]
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

            $rataRata = $this->nilaiModel->hitungRataRata(70, $this->userKodebpr, $this->periodeId);
            $this->nilaiModel->insertOrUpdateRataRata($rataRata, 70, $this->userKodebpr, $this->periodeId);

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
            "OPERASIONALKPMR",
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
            "OPERASIONALKPMR",
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

        $faktorIds = range(51, 70);
        $counts = [];

        foreach ($faktorIds as $faktorId) {
            $count = $this->commentReadsModel->countUnreadCommentsForUserByFactor(
                $faktorId,
                "OPERASIONALKPMR",
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

        $faktorId = 71;
        $penilaiankredit = $this->request->getPost('penilaiankredit');
        $user = $this->userModel->find($this->userId);

        $penjelasanMapping = [
            '1' => 'Tingkat Risiko Operasional KPMR: Sangat Rendah',
            '2' => 'Tingkat Risiko Operasional KPMR: Rendah',
            '3' => 'Tingkat Risiko Operasional KPMR: Sedang',
            '4' => 'Tingkat Risiko Operasional KPMR: Tinggi',
            '5' => 'Tingkat Risiko Operasional KPMR: Sangat Tinggi'
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
                    'keterangan' => 'Tingkat Risiko Operasional KPMR Posisi Sebelumnya',
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
                    return redirect()->to(base_url('Risikooperasionalkpmr'))
                        ->with('message', 'Data Tingkat Risiko Operasional KPMR berhasil diupdate');
                } else {
                    throw new \Exception('Gagal mengupdate data');
                }

            } else {
                // DATA BELUM ADA - LAKUKAN INSERT
                $insertData = [
                    'faktor1id' => $faktorId,
                    'penilaiankredit' => $penilaiankredit,
                    'penjelasanpenilaian' => $penjelasanpenilaian,
                    'keterangan' => 'Tingkat Risiko Operasional KPMR Posisi Sebelumnya',
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
                    return redirect()->to(base_url('Risikooperasionalkpmr'))
                        ->with('message', 'Data Tingkat Risiko Operasional KPMR berhasil disimpan');
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
        $faktorId = 70;
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
            ->where('faktor1id', 70)
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

        $faktorId = 70;
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
                    return redirect()->to(base_url('Risikooperasionalkpmr'))
                        ->with('message', 'Kesimpulan berhasil diupdate');
                } else {
                    throw new \Exception('Gagal mengupdate kesimpulan');
                }

            } else {
                $rataRata = $this->nilaiModel->hitungRataRata($faktorId, $this->userKodebpr, $this->periodeId);
                $rataRata = ($rataRata - floor($rataRata) >= 0.5) ? ceil($rataRata) : floor($rataRata);

                $penjelasanMapping = [
                    '1' => 'Tingkat Risiko Operasional KPMR: Sangat Rendah',
                    '2' => 'Tingkat Risiko Operasional KPMR: Rendah',
                    '3' => 'Tingkat Risiko Operasional KPMR: Sedang',
                    '4' => 'Tingkat Risiko Operasional KPMR: Tinggi',
                    '5' => 'Tingkat Risiko Operasional KPMR: Sangat Tinggi'
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
                    return redirect()->to(base_url('Risikooperasionalkpmr'))
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
            ->where('faktor1id', 71)
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
                'id' => 51,
                'title' => 'Pengawasan Direksi dan Komisaris',
                'type' => 'category',
                'faktor_id' => 51,
                'faktor_ids' => [52, 53, 54, 55, 56, 57],
                'description' => 'Pengawasan Direksi dan Komisaris',
                'children' => [
                    [
                        'id' => 52,
                        'title' => 'Apakah Dewan Komisaris telah melakukan persetujuan terhadap kebijakan manajemen risiko <br>
                        operasional yang disusun oleh Direksi dan melakukan evaluasi secara berkala?',
                        'type' => 'parameter',
                        'faktor_id' => 52,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 53,
                        'title' => 'Apakah Dewan Komisaris telah melakukan evaluasi terhadap pertanggungjawaban Direksi <br>
                        atas pelaksanaan kebijakan manajemen risiko operasional secara berkala dan memastikan tindak <br>
                        lanjut hasil evaluasi dimaksud?',
                        'type' => 'parameter',
                        'faktor_id' => 53,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 54,
                        'title' => 'Apakah Direksi telah menyusun kebijakan manajemen risiko operasional,melaksanakan secara <br>
                        konsisten, dan melakukan pengkinian secara berkala?',
                        'type' => 'parameter',
                        'faktor_id' => 54,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 55,
                        'title' => 'Apakah Direksi telah memiliki kemampuan untuk mengambil tindakan yang diperlukan dalam <br>
                        rangka mitigasi risiko operasional, dan melakukan komunikasi kebijakan manajemen risiko <br>
                        operasional terhadap seluruh jenjang organisasi BPR?',
                        'type' => 'parameter',
                        'faktor_id' => 55,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 56,
                        'title' => 'Apakah BPR telah memiliki kecukupan organisasi yang menangani fungsi operasional dan <br>
                        fungsi manajemen risiko operasional?',
                        'type' => 'parameter',
                        'faktor_id' => 56,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 57,
                        'title' => 'Apakah Direksi telah menerapkan kebijakan pengelolaan SDM dalam rangka penerapan <br>
                        manajemen risiko operasional?',
                        'type' => 'parameter',
                        'faktor_id' => 57,
                        'previous_periode_faktor_id' => null
                    ]
                ]
            ],
            [
                'id' => 58,
                'title' => 'Kecukupan Kebijakan, Prosedur, dan Limit',
                'type' => 'category',
                'faktor_id' => 58,
                'faktor_ids' => [59, 60, 61],
                'description' => 'Kecukupan Kebijakan, Prosedur, dan Limit',
                'children' => [
                    [
                        'id' => 59,
                        'title' => 'Apakah BPR telah memiliki kebijakan manajemen risiko operasional yang memadai dan disusun <br>
                        dengan mempertimbangkan visi, misi, skala usaha dan kompleksitas bisnis, serta kecukupan SDM?',
                        'type' => 'parameter',
                        'faktor_id' => 59,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 60,
                        'title' => 'Apakah BPR:<br>
                        ● Memiliki prosedur manajemen risiko operasional dan penetapan limit risiko operasional yang ditetapkan <br>
                        oleh Direksi; <br>
                        ● Melaksanakan prosedur manajemen risiko operasional dan penetapan limit risiko operasional secara konsisten <br>
                        untuk seluruh aktivitas; dan <br>
                        ● Melakukan evaluasi dan pengkinian terhadap prosedur manajemen risiko operasional dan penetapan limit risiko <br>
                        operasional secara berkala?',
                        'type' => 'parameter',
                        'faktor_id' => 60,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 61,
                        'title' => 'Apakah BPR telah memiliki kebijakan dan prosedur penerbitan produk dan/atau pelaksanaan aktivitas baru <br>
                        yang mencakup identifikasi dan mitigasi risiko operasional sesuai ketentuan?',
                        'type' => 'parameter',
                        'faktor_id' => 61,
                        'previous_periode_faktor_id' => null
                    ]
                ]
            ],
            [
                'id' => 62,
                'title' => 'Kecukupan Proses dan Sistem Manajemen Risiko',
                'type' => 'category',
                'faktor_id' => 62,
                'faktor_ids' => [63, 64, 65, 66],
                'description' => 'Kecukupan Proses dan Sistem Manajemen Risiko',
                'children' => [
                    [
                        'id' => 63,
                        'title' => 'Apakah BPR telah melaksanakan proses manajemen risiko operasional yang melekat pada kegiatan <br>
                        usaha BPR?',
                        'type' => 'parameter',
                        'faktor_id' => 63,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 64,
                        'title' => 'Apakah BPR telah memiliki sistem informasi manajemen risiko yang mendukung Direksi dalam pengambilan <br>
                        keputusan terkait risiko operasional serta telah dilaporkan kepada Direksi secara berkala?',
                        'type' => 'parameter',
                        'faktor_id' => 64,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 65,
                        'title' => 'Apakah BPR telah memiliki kebijakan dan prosedur penyelenggaraan TI?',
                        'type' => 'parameter',
                        'faktor_id' => 65,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 66,
                        'title' => 'Apakah BPR telah melakukan langkah mitigasi risiko terkait kejadian eksternal?',
                        'type' => 'parameter',
                        'faktor_id' => 66,
                        'previous_periode_faktor_id' => null
                    ]
                ]
            ],
            [
                'id' => 67,
                'title' => 'Sistem Pengendalian Internal yang Menyeluruh',
                'type' => 'category',
                'faktor_id' => 67,
                'faktor_ids' => [68, 69],
                'description' => 'Sistem Pengendalian Internal yang Menyeluruh',
                'children' => [
                    [
                        'id' => 68,
                        'title' => 'Apakah SKAI atau PE Audit Intern telah melaksanakan audit secara berkala terhadap penerapan manajemen <br>
                        risiko operasional, menyampaikan laporan hasil audit intern, dan memastikan tindaklanjut atas temuan pemeriksaan?',
                        'type' => 'parameter',
                        'faktor_id' => 68,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 69,
                        'title' => 'Apakah sistem pengendalian intern terhadap risiko operasional telah dilaksanakan oleh seluruh jenjang <br>
                        organisasi BPR?',
                        'type' => 'parameter',
                        'faktor_id' => 69,
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
            51 => [52, 53, 54, 55, 56, 57],
            58 => [59, 60, 61],
            62 => [63, 64, 65, 66],
            67 => [68, 69]
        ];

        foreach ($categoryMapping as $categoryId => $childrenIds) {
            if (in_array($changedFaktorId, $childrenIds)) {
                $this->calculateAndSaveCategoryAverage($categoryId, $childrenIds, $kodebpr, $periodeId);
                break;
            }
        }
    }

    public function exporttxtrisikooperasionalkpmr()
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
        $data_risikooperasionalkpmr = $this->nilaiModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        // BPR Info
        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'] ?? '';
            $kodejenis = $infobpr['kodejenis'] ?? '';
        }

        function sanitizeTxtOpsKpmr($text)
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

        $output = "";

        // Header
        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|PRBPRKS|0202|{$modalinti}|{$totalaset}|{$kantorcabang}|{$atmdebit}|{$kategori}||\r\n";

        usort($data_risikooperasionalkpmr, function ($a, $b) {
            return ($a['faktor1id'] ?? 0) <=> ($b['faktor1id'] ?? 0);
        });

        // Data rows
        foreach ($data_risikooperasionalkpmr as $row) {
            $faktorId = $row['faktor1id'] ?? null;

            if (!isset($kodeMap[$faktorId])) {
                continue; // <- skip data yang tidak punya kode
            }

            $kode = $kodeMap[$faktorId] ?? ''; // ambil kode sesuai faktor

            $penilaiankredit = sanitizeTxtOpsKpmr($row['penilaiankredit']);
            $keterangan = sanitizeTxtOpsKpmr($row['keterangan']);

            $output .= "D01|{$kode}|{$penilaiankredit}|{$keterangan}\r\n";
        }

        // Filename
        $filename = "PRBPRKS-0202-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        // Response
        $response = service('response');
        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response->setBody($output);
    }
}