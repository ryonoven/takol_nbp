<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_paramprofilrisiko;
use App\Models\M_user;
use App\Models\M_profilrisikocomments;
use App\Models\M_stratejikkpmr;
use App\Models\M_infobpr;
use App\Models\M_periodeprofilresiko;
use App\Models\M_profilrisikocommentsread;
use App\Models\M_showprofilresiko;
use Myth\Auth\Config\Services as AuthServices;

class Stratejikkpmr extends Controller
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
        $this->nilaiModel = new M_stratejikkpmr();
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
            'faktor1id' => 199,
            'kodebpr' => $this->userKodebpr,
            'periode_id' => $this->periodeId
        ])->first();

        $nilai14 = $this->nilaiModel->where([
            'faktor1id' => 200,
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
            ->whereIn('faktor1id', range(182, 199))
            ->first();

        $allFilled = ($approvalData['filled_count'] == 18); // 182-199 = 18
        $allApproved = ($allFilled && $approvalData['approved_count'] == 18);
        $canApprove = $allFilled;

        $user = $this->userModel->find($this->userId);

        $data = [
            'judul' => 'Penilaian Risiko Stratejik KPMR',
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
            . view('risikostratejik/stratejikkpmr', $data)
            . view('templates/v_footer');
    }

    private function checkCanApprove($nilaiLookup)
    {
        $requiredFaktorIds = range(182, 198);

        foreach ($requiredFaktorIds as $faktorId) {
            if (empty($nilaiLookup[$faktorId])) {
                return false;
            }
        }

        return true;
    }

    private function checkAccdekomApproved($nilaiLookup)
    {
        for ($faktorId = 182; $faktorId <= 198; $faktorId++) {
            if (!isset($nilaiLookup[$faktorId]) || $nilaiLookup[$faktorId]['is_approved'] != 1) {
                return false;
            }
        }
        return true;
    }

    private function checkAllApproved($nilaiLookup)
    {
        $requiredFaktorIds = range(182, 199);
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

        $nilaiData = $this->db->table('stratejikkpmr as r')
            ->select('r.*, u.fullname')
            ->join('users as u', 'u.id = r.user_id', 'left')
            ->where('r.kodebpr', $this->userKodebpr)
            ->where('r.periode_id', $this->periodeId)
            ->whereIn('r.faktor1id', range(182, 199))
            ->get()
            ->getResultArray();

        $nilaiLookup = [];
        foreach ($nilaiData as $nilai) {
            $nilaiLookup[$nilai['faktor1id']] = $nilai;
        }

        $structure = [
            [
                'id' => 182,
                'title' => 'Pilar Pengawasan Direksi dan Dewan Komisaris',
                'type' => 'category',
                'faktor_id' => 182,
                'children' => [
                    ['id' => 183, 'title' => 'Apakah Dewan Komisaris telah melakukan persetujuan terhadap kebijakan manajemen risiko stratejik <br> yang disusun oleh Direksi dan melakukan evaluasi secara berkala?', 'faktor_id' => 183],
                    ['id' => 184, 'title' => 'Apakah Dewan Komisaris telah melakukan evaluasi terhadap pertanggungjawaban Direksi atas pelaksanaan <br> kebijakan manajemen risiko stratejik secara berkala dan memastikan tindak lanjut hasil evaluasi dimaksud?', 'faktor_id' => 184],
                    ['id' => 185, 'title' => 'Apakah Direksi telah menyusun kebijakan manajemen risiko stratejik, melaksanakan secara konsisten, <br> dan melakukan pengkinian secara berkala?', 'faktor_id' => 185],
                    ['id' => 186, 'title' => 'Apakah Direksi telah memiliki kemampuan untuk mengambil tindakan yang diperlukan dalam rangka mitigasi <br> risiko stratejik, dan melakukan komunikasi kebijakan manajemen risiko stratejik terhadap seluruh jenjang <br> organisasi BPR?', 'faktor_id' => 186],
                    ['id' => 187, 'title' => 'Apakah BPR telah memiliki kecukupan organisasi yang menangani fungsi stratejik dan fungsi manajemen <br> risiko stratejik?', 'faktor_id' => 187],
                    ['id' => 188, 'title' => 'Apakah Direksi telah menerapkan kebijakan pengelolaan SDM dalam rangka penerapan Manajemen Risiko <br> stratejik?', 'faktor_id' => 188]
                ]
            ],
            [
                'id' => 189,
                'title' => 'Pilar Kecukupan Kebijakan, Prosedur, dan Limit',
                'type' => 'category',
                'faktor_id' => 189,
                'children' => [
                    [
                        'id' => 190,
                        'title' => 'Apakah BPR telah memiliki kebijakan manajemen risiko stratejik yang memadai dan disusun dengan <br> mempertimbangkan visi, misi, skala usaha dan kompleksitas bisnis, serta kecukupan SDM?',
                        'faktor_id' => 190
                    ],
                    [
                        'id' => 191,
                        'title' => 'Apakah BPR: 
                    ● Memiliki prosedur manajemen risiko stratejik dan penetapan limit risiko stratejik yang ditetapkan <br> oleh Direksi; 
                    ● Melaksanakan prosedur manajemen risiko stratejik dan penetapan limit risiko stratejik secara <br> konsisten untuk seluruh aktivitas; dan 
                    ● Melakukan evaluasi dan pengkinian terhadap prosedur manajemen risiko stratejik dan penetapan <br> limit risiko stratejik secara berkala?',
                        'faktor_id' => 191
                    ],
                    [
                        'id' => 192,
                        'title' => 'Apakah BPR telah memiliki kebijakan dan prosedur penerbitan produk dan/atau pelaksanaan aktivitas <br> baru yang mencakup identifikasi dan mitigasi risiko stratejik sesuai dengan ketentuan?',
                        'faktor_id' => 192
                    ]
                ]
            ],
            [
                'id' => 193,
                'title' => 'Kecukupan Proses dan Sistem Manajemen Informasi',
                'type' => 'category',
                'faktor_id' => 193,
                'children' => [
                    [
                        'id' => 194,
                        'title' => 'Apakah BPR telah melaksanakan proses manajemen risiko stratejik yang melekat pada kegiatan <br> usaha BPR?',
                        'faktor_id' => 194
                    ],
                    [
                        'id' => 195,
                        'title' => 'Apakah BPR telah memiliki sistem informasi manajemen risiko yang mendukung Direksi dalam <br> pengambilan <br> keputusan terkait risiko  stratejik serta telah dilaporkan kepada Direksi secara berkala?',
                        'faktor_id' => 195
                    ]
                ]
            ],
            [
                'id' => 196,
                'title' => 'Sistem Pengendalian Internal yang Menyeluruh',
                'type' => 'category',
                'faktor_id' => 196,
                'children' => [
                    ['id' => 197, 'title' => 'Apakah SKAI atau PEAI telah melaksanakan audit secara berkala terhadap penerapan <br> manajemen risiko stratejik, menyampaikan laporan hasil audit intern, dan memastikan <br> tindaklanjut atas temuan pemeriksaan?', 'faktor_id' => 197],
                    ['id' => 198, 'title' => 'Apakah sistem pengendalian intern terhadap risiko stratejik telah dilaksanakan oleh <br> seluruh jenjang organisasi BPR?', 'faktor_id' => 198]
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

            182 => [
                'descriptions' => [
                    1 => 'Sangat Baik',
                    2 => 'Baik',
                    3 => 'Cukup',
                    4 => 'Kurang Baik',
                    5 => 'Buruk'
                ],
                'catatan' => 'Penilaian parameter risiko stratejik KPMR'
            ],

            183 => [
                'descriptions' => [
                    1 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko stratejik; <br>
                    • Dewan Komisaris telah melakukan evaluasi terhadap kebijakan Manajemen Risiko stratejik; <br>
                    • evaluasi dilakukan oleh Dewan Komisaris secara berkala paling sedikit satu kali dalam satu tahun atau sewaktu-waktu dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan; dan <br>
                    • evaluasi yang diberikan relevan dengan kebutuhan penyesuaian kebijakan Manajemen Risiko stratejik.',
                    2 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko stratejik; <br>
                    • Dewan Komisaris telah melakukan evaluasi terhadap kebijakan Manajemen Risiko stratejik; dan <br>
                    • evaluasi dilakukan oleh Dewan Komisaris secara berkala paling sedikit satu kali dalam satu tahun atau sewaktu-waktu dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan.',
                    3 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko stratejik; <br>
                    • Dewan Komisaris telah melakukan evaluasi terhadap kebijakan Manajemen Risiko stratejik; dan <br>
                    • evaluasi tidak dilakukan oleh Dewan Komisaris secara berkala paling sedikit satu kali dalam satu tahun atau sewaktu-waktu dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan.',
                    4 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko stratejik; dan <br>
                    • Dewan Komisaris tidak melakukan evaluasi terhadap kebijakan Manajemen Risiko stratejik.',
                    5 => '• Dewan Komisaris tidak memberikan persetujuan terhadap kebijakan Manajemen Risiko stratejik; dan <br>
                    • Dewan Komisaris tidak melakukan evaluasi terhadap kebijakan Manajemen Risiko stratejik.'
                ],
                'catatan' => 'Penilaian parameter risiko stratejik KPMR'
            ],

            184 => [
                'descriptions' => [
                    1 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan manajemen stratejik oleh Direksi; <br>
                    • evaluasi oleh Dewan Komisaris dilakukan secara berkala setiap semester atau lebih berdasarkan laporan yang disampaikan Direksi dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan; <br>
                    • Dewan Komisaris telah memastikan tindak lanjut hasil evaluasi dalam setiap periode laporan; dan <br>
                    • evaluasi yang diberikan relevan dengan pelaksanaan kebijakan Manajemen Risiko stratejik dalam rangka mendukung perbaikan kinerja BPR.',
                    2 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko stratejik oleh Direksi; <br>
                    • evaluasi oleh Dewan Komisaris dilakukan secara berkala setiap semester atau lebih berdasarkan laporan yang disampaikan Direksi dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan; dan <br>
                    • Dewan Komisaris telah memastikan tindak lanjut hasil evaluasi dalam setiap periode laporan.',
                    3 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko stratejik oleh Direksi; <br>
                    • evaluasi oleh Dewan Komisaris dilakukan secara berkala setiap semester atau lebih berdasarkan laporan yang disampaikan Direksi dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan; dan <br>
                    • Dewan Komisaris tidak memastikan tindak lanjut hasil evaluasi dalam setiap periode laporan.',
                    4 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko stratejik oleh Direksi; <br>
                    • evaluasi oleh Dewan Komisaris tidak dilakukan secara berkala setiap semester atau lebih berdasarkan laporan yang disampaikan Direksi dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan; dan <br>
                    • Dewan Komisaris tidak memastikan tindak lanjut hasil evaluasi dalam setiap periode laporan.',
                    5 => 'Dewan Komisaris tidak melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko stratejik oleh Direksi.'
                ],
                'catatan' => 'Penilaian parameter risiko stratejik KPMR'
            ],

            185 => [
                'descriptions' => [
                    1 => '• Direksi telah menyusun kebijakan Manajemen Risiko stratejik; <br>
                    • menjalankan kegiatan usaha berdasarkan kebijakan Manajemen Risiko stratejik yang telah ditetapkan; <br>
                    • Direksi melakukan pengkinian terhadap kebijakan Manajemen Risiko stratejik dalam hal terdapat perubahan ketentuan peraturan perundangundangan, perubahan bisnis, dan hasil evaluasi kebijakan Manajemen Risiko oleh Dewan Komisaris; dan <br>
                    • kebijakan Manajemen Risiko stratejik yang dijalankan terbukti memitigasi terjadinya Risiko stratejik.',
                    2 => '• Direksi telah menyusun kebijakan Manajemen Risiko stratejik; <br>
                    • menjalankan kegiatan usaha berdasarkan kebijakan Manajemen Risiko stratejik yang telah ditetapkan; dan <br>
                    • Direksi melakukan pengkinian terhadap kebijakan Manajemen Risiko stratejik dalam hal terdapat perubahan ketentuan peraturan perundangundangan, perubahan bisnis, dan hasil evaluasi kebijakan Manajemen Risiko oleh Dewan Komisaris.',
                    3 => '• Direksi telah menyusun kebijakan Manajemen Risiko stratejik; <br>
                    • menjalankan kegiatan usaha berdasarkan kebijakan Manajemen Risiko stratejik yang telah ditetapkan; dan <br>
                    • Direksi tidak melakukan pengkinian terhadap kebijakan Manajemen Risiko stratejik dalam hal terdapat perubahan ketentuan peraturan perundangundangan, perubahan bisnis, dan hasil evaluasi kebijakan Manajemen Risiko oleh Dewan Komisaris.',
                    4 => '• Direksi telah menyusun kebijakan Manajemen Risiko stratejik; <br>
                    • tidak menjalankan kegiatan usaha berdasarkan kebijakan Manajemen Risiko stratejik yang telah ditetapkan; dan <br>
                    • Direksi tidak melakukan pengkinian terhadap kebijakan Manajemen Risiko stratejik dalam hal terdapat perubahan ketentuan peraturan perundangundangan, perubahan bisnis, dan hasil evaluasi kebijakan Manajemen Risiko oleh Dewan Komisaris.',
                    5 => 'Direksi tidak menyusun kebijakan Manajemen Risiko stratejik.'
                ],
                'catatan' => 'Penilaian parameter risiko stratejik KPMR'
            ],

            186 => [
                'descriptions' => [
                    1 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko saat menjalankan kebijakan Manajemen Risiko stratejik; <br>
                    • Direksi mengomunikasi kan kebijakan Manajemen Risiko stratejik; dan <br>
                    • seluruh jenjang organisasi BPR mampu memahami kebijakan Manajemen Risiko stratejik yang diterapkan.',
                    2 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko saat menjalankan kebijakan Manajemen Risiko stratejik; <br>
                    • Direksi mengomunikasi kan kebijakan Manajemen Risiko stratejik; dan <br>
                    • tidak seluruh jenjang organisasi BPR mampu memahami kebijakan Manajemen Risiko stratejik yang diterapkan namun tidak menimbulkan dampak yang signifikan.',
                    3 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko saat menjalankan kebijakan Manajemen Risiko stratejik; <br>
                    • Direksi mengomunikasi kan kebijakan Manajemen Risiko stratejik; dan <br>
                    • tidak seluruh jenjang organisasiBPR mampu memahami kebijakan Manajemen Risiko stratejik yang diterapkan dan menimbulkan dampak yang signifikan.',
                    4 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko saat menjalankan kebijakan Manajemen Risiko stratejik; <br>
                    • Direksi tidak mengomunikasi kan kebijakan Manajemen Risiko stratejik; dan <br>
                    • seluruh jenjang organisasi BPR tidak mampu memahami kebijakan Manajemen Risiko stratejik yang diterapkan.',
                    5 => '• Direksi tidak mengambil tindakan yang diperlukan untuk memitigasi Risiko saat menjalankan kebijakan Manajemen Risiko stratejik; <br>
                    • Direksi tidak mengomunikasi kan kebijakan Manajemen Risiko stratejik; dan <br>
                    • seluruh jenjang organisasi BPR tidak mampu memahami kebijakan Manajemen Risiko stratejik yang diterapkan.'
                ],
                'catatan' => 'Penilaian parameter risiko stratejik KPMR'
            ],

            187 => [
                'descriptions' => [
                    1 => '• memiliki unit kerja yang menangani fungsi stratejik; <br>
                    • unit kerja yang menangani fungsi stratejik telah melaksanakan tugas dan wewenangnya sesuai dengan pedoman yang ditetapkan; dan <br>
                    • memiliki SKMR atau PEMR yang mampu melaksanakan fungsinya untuk memitigasi risiko Stratejik',
                    2 => '• memiliki unit kerja yang menangani fungsi stratejik namun tidak lengkap; <br>
                    • unit kerja yang menangani fungsi stratejik telah melaksanakan tugas dan wewenangnya sesuai dengan pedoman yang ditetapkan; dan <br>
                    • memiliki SKMR atau PEMR yang mampu melaksanakan fungsinya untuk memitigasi Risiko stratejik.',
                    3 => '• memiliki unit kerja yang menangani fungsi stratejik; <br>
                    • unit kerja yang menangani fungsi stratejik telah melaksanakan tugas dan wewenangnya sesuai dengan pedoman yang ditetapkan; dan <br>
                    • memiliki SKMR atau PEMR yang tidak mampu melaksanakan fungsinya untuk memitigasi Risiko stratejik.',
                    4 => '• memiliki unit kerja yang menangani fungsi stratejik namun tidak lengkap; <br>
                    • unit kerja yang menangani fungsi stratejik telah melaksanakan tugas dan wewenangnya sesuai dengan pedoman yang ditetapkan; dan <br>
                    • memiliki SKMR atau PEMR yang tidak mampu melaksanakan fungsinya untukmemitigasi Risiko stratejik.',
                    5 => '• memiliki unit kerja yang menangani fungsi stratejik namun tidak lengkap; <br>
                    • unit kerja yang menangani fungsi stratejik tidak melaksanakan tugas dan wewenangnya sesuai dengan pedoman yang ditetapkan; dan <br>
                    • memiliki SKMR atau PEMR yang tidak mampu melaksanakan fungsinya untuk memitigasi Risiko stratejik.'
                ],
                'catatan' => 'Penilaian parameter risiko stratejik KPMR'
            ],

            188 => [
                'descriptions' => [
                    1 => '• terdapat kesesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan; <br>
                    • terdapat upaya peningkatan kompetensi SDM secara konsisten; dan <br>
                    • tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi stratejik sesuai dengan tugas dan tanggung jawab.',
                    2 => '• terdapat ketidaksesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan namun tidak memberikan dampak yang signifikan; <br>
                    • terdapat upaya peningkatan kompetensi SDM secara konsisten; dan <br>
                    • tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi stratejik sesuai dengan tugas dan tanggung jawab. ',
                    3 => '• terdapat ketidaksesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan namun tidak memberikan dampak yang signifikan; <br>
                    • terdapat upaya peningkatan kompetensi SDM namun tidak secara konsisten; dan <br>
                    • tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi stratejik sesuai dengan tugas dan tanggung jawab ',
                    4 => '• terdapat ketidaksesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan dan memberikan dampak yang signifikan; <br>
                    • terdapat upaya peningkatan kompetensi SDM namun tidak secara konsisten; dan <br>
                    • tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi stratejik tidak sesuai dengan tugas dan tanggung jawab namun tidak memberikan dampak yang signifikan.',
                    5 => '• terdapat ketidaksesuaian kualifikasi SDMdengan jabatan dan bidang pekerjaan dan memberikan dampak yang signifikan; <br>
                    • tidak terdapat upaya peningkatan kompetensi SDM; dan <br>
                    • tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi stratejik tidak sesuai dengan tugas dan tanggung jawab dan memberikan dampak yang signifikan'
                ],
                'catatan' => 'Penilaian parameter risiko stratejik KPMR'
            ],

            189 => [
                'descriptions' => [
                    1 => 'Sangat Baik',
                    2 => 'Baik',
                    3 => 'Cukup',
                    4 => 'Kurang Baik',
                    5 => 'Buruk'
                ],
                'catatan' => 'Penilaian parameter risiko stratejik KPMR'
            ],

            190 => [
                'descriptions' => [
                    1 => '• telah memiliki kebijakan Manajemen Risiko stratejik; <br>
                    • terdapat kesesuaian antara substansi kebijakan Manajemen Risiko stratejik dengan ketentuan Manajemen Risiko BPR termasuk target pencapaian tahunan BPR yang tertuang dalam rencana bisnis BPR; dan <br>
                    • terdapat keselarasan antara kebijakan Manajemen Risiko stratejik dengan visi, misi, skala usaha, dan kompleksitas bisnis, serta kecukupan SDM dalam menetapkan kebijakan Manajemen Risiko stratejik.',
                    2 => "• telah memiliki kebijakan Manajemen Risiko stratejik; <br>
                    • terdapat ketidaksesuaian yang tidak signifikan antara substansi kebijakan Manajemen Risiko stratejik dengan ketentuan Manajemen Risiko BPR termasuk target pencapaian tahunan BPR yang tertuang dalam rencana bisnis BPR; dan <br>
                    • terdapat keselarasan antara kebijakan Manajemen Risiko stratejik dengan visi, misi, skala usaha, dan kompleksitas bisnis, serta kecukupan SDM dalam menetapkan kebijakan Manajemen Risiko stratejik.",
                    3 => '• telah memiliki kebijakan Manajemen Risiko stratejik; <br>
                    • terdapat ketidaksesuaian yang tidak signifikan antara substansi kebijakan Manajemen Risiko stratejik dengan ketentuan Manajemen Risiko BPR termasuk target pencapaian tahunan BPR yang tertuang dalam rencana bisnis BPR; dan <br>
                    • terdapat ketidakselarasan antara kebijakan Manajemen Risiko stratejik dengan visi, misi, skala usaha, dan kompleksitas bisnis, serta kecukupan SDM dalam menetapkan kebijakan Manajemen Risiko stratejik namun tidak menimbulkan dampak yang signifikan.',
                    4 => '• telah memiliki kebijakan Manajemen Risiko stratejik; <br>
                    • terdapat ketidaksesuaian yang signifikan antara substansi kebijakan Manajemen Risiko stratejik dengan ketentuan Manajemen Risiko BPR termasuk target pencapaian tahunan BPR yang tertuang dalam rencana bisnis BPR; dan <br>
                    • terdapat ketidakselarasan antara kebijakan Manajemen Risiko stratejik dengan visi, misi, skala usaha, dan kompleksitas bisnis, serta kecukupan SDM dalam menetapkan kebijakan Manajemen Risiko stratejik dan menimbulkan dampak yang signifikan.',
                    5 => 'tidak memiliki kebijakan Manajemen Risiko stratejik.'
                ],
                'catatan' => 'Penilaian parameter risiko stratejik KPMR'
            ],

            191 => [
                'descriptions' => [
                    1 => '• memiliki prosedur Manajemen Risiko stratejik dan penetapan limit Risiko stratejik yang ditetapkan oleh Direksi paling sedikit meliputi jenjang delegasi wewenang dan pertanggungjawaban yang jelas serta terdokumentasi dengan baik sehingga memudahkan keperluan jejak audit untuk keperluan pengendalian intern; <br>
                    • melaksanakan prosedur Manajemen Risiko stratejik dan penetapan limit Risiko stratejik dalam setiap aktivitas fungsional secara konsisten; dan <br>
                    • melakukan evaluasi dan pengkinian prosedur Manajemen Risiko stratejik dan penetapan limit Risiko stratejik dalam hal terdapat perubahan bisnis yang signifikan dan/atau ketentuan peraturan perundang-undangan.',
                    2 => "• memiliki prosedur Manajemen Risiko stratejik dan penetapan limit Risiko stratejik yang ditetapkan oleh Direksi paling sedikit meliputi jenjang delegasi wewenang dan pertanggungjawaban yang jelas serta terdokumentasi dengan baik sehingga memudahkan keperluan jejak audit untuk keperluan pengendalian intern; <br>
                    • melaksanakan prosedur Manajemen Risiko stratejik dan penetapan limit Risiko stratejik dalam setiap aktivitas fungsional secara konsisten; dan <br>
                    • tidak melakukan evaluasi dan pengkinian prosedur Manajemen Risiko stratejik dan penetapan limit Risiko stratejik dalam hal terdapat perubahan bisnis yang signifikan dan/atau ketentuan peraturan perundangundangan, namun tidak menimbulkan dampak yang signifikan.",
                    3 => '• memiliki prosedur Manajemen Risiko stratejik dan penetapan limit Risiko stratejik yang ditetapkan oleh Direksi paling sedikit meliputi jenjang delegasi wewenang dan pertanggungjawaban yang jelas serta terdokumentasi dengan baik sehingga memudahkan keperluan jejak audit untuk keperluan pengendalian intern; <br>
                    • melaksanakan prosedur Manajemen Risiko stratejik dan penetapan limit Risiko stratejik dalam setiap aktivitas fungsional secara konsisten; dan <br>
                    • tidak melakukan evaluasi dan pengkinian prosedur Manajemen Risiko stratejik dan penetapan limit Risiko stratejik dalam hal terdapat perubahan bisnis yang signifikan dan/atau ketentuan peraturan perundangundangan dan menimbulkan dampak yang signifikan.',
                    4 => '• memiliki prosedur Manajemen Risiko stratejik dan penetapan limit Risiko stratejik yang ditetapkan oleh Direksi paling sedikit meliputi jenjang delegasi wewenang dan pertanggungjawaban yang jelas serta terdokumentasi dengan baik sehingga memudahkan keperluan jejak audit untuk keperluan pengendalian intern; <br>
                    • tidak melaksanakan prosedur Manajemen Risiko stratejik dan penetapan limit Risiko stratejik dalam setiap aktivitas fungsional secara konsisten; dan <br>
                    • tidak melakukan evaluasi dan pengkinian prosedur Manajemen Risiko stratejik dan penetapan limit Risiko stratejik dalam hal terdapat perubahan bisnis yang signifikan dan/atau ketentuan peraturan perundangundangan.',
                    5 => 'tidak memiliki prosedur Manajemen Risiko stratejik dan penetapan limit Risiko stratejik yang ditetapkan oleh Direksi.'
                ],
                'catatan' => 'Penilaian parameter risiko stratejik KPMR'
            ],

            192 => [
                'descriptions' => [
                    1 => '• BPR tidak dalam proses penggabungan, peleburan, dan pengambil alihan; <br>
                    • BPR tidak dalam proses pemindahan kantor pusat BPR; dan <br>
                    • BPR tidak dalam proses penerbitan produk dan/atau pelaksanaan aktivitas baru.',
                    2 => '• BPR tidak dalam proses penggabungan, peleburan, dan pengambil alihan; <br>
                    • Terdapat prosespemindahan kantor pusat BPR; dan/atau <br>
                    • BPR dalam proses pengembangan produk dan/atau aktivitas baru (yang hanya memerlukan pelaporan ke OJK).',
                    3 => '• Terdapat proses pemindahan kantor pusat BPR; <br>
                    • BPR menerbitkan produk dan/atau melaksanakan aktivitas baru (memerlukan persetujuan OJK) bekerja sama dengan pihak ketiga (tidak ada biaya investasi - capital expenditure BPR); dan/atau <br>
                    • BPR melaksanakan penggabungan, peleburan, dan pengambilalihan pada jangka waktu sangat lama sebelum periode penilaian. <br>
                    • Proses pengambilalihan tidak berpengaruh terhadap strategi bisnis dan budaya perusahaan',
                    4 => '• Terdapat proses pemindahan kantor pusat BPR; <br>
                    • BPR menerbitkan produk dan/atau melaksanakan aktivitas baru (memerlukan persetujuan OJK) yang memerlukan biaya investasi - capital expenditure BPR; dan/atau <br>
                    • BPR melaksanakan penggabungan, peleburan, dan pengambilalihan pada jangka waktu lama sebelum periode penilaian. <br>
                    • Proses pengambilalihan berpengaruh terhadap strategi bisnis dan budaya perusahaan',
                    5 => '• Terdapat proses pemindahan kantor pusat BPR; <br>
                    • BPR menerbitkan produk dan/atau melaksanakan aktivitas baru (memerlukan persetujuan OJK) yang memerlukan biaya investasi - capital expenditure BPR; dan/atau <br>
                    • BPR melaksanakan penggabungan, peleburan, dan pengambilalihan pada jangka waktu tidak lama sebelum periode penilaian. <br>
                    • Proses pengambilalihan berpengaruh terhadap strategi bisnis dan budaya perusahaan'
                ],
                'catatan' => 'Penilaian parameter risiko stratejik KPMR'
            ],

            193 => [
                'descriptions' => [
                    1 => 'Sangat Baik',
                    2 => 'Baik',
                    3 => 'Cukup',
                    4 => 'Kurang Baik',
                    5 => 'Buruk'
                ],
                'catatan' => 'Penilaian parameter risiko stratejik KPMR'
            ],

            194 => [
                'descriptions' => [
                    1 => '• memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru yang memiliki eksposur Risiko stratejik; <br>
                    • menerapkan kebijakan dan prosedur dalam hal terdapat penerbitan produk atau pelaksanaan aktivitas baru; dan <br>
                    • terdapat kesesuaian antara kebijakan dan prosedur produk dan/atau aktivitas baru dengan ketentuan.',
                    2 => '• memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru yang memiliki eksposur Risiko stratejik; <br>
                    • menerapkan kebijakan dan prosedur dalam hal terdapat penerbitan produk atau pelaksanaan aktivitas baru; dan <br>
                    • terdapat ketidaksesuaian yang tidak signifikan antara kebijakan dan prosedur produk dan/atau aktivitas baru dengan ketentuan.',
                    3 => '• memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru yang memiliki eksposur Risiko stratejik; <br>
                    • menerapkan kebijakan dan prosedur dalam hal terdapat penerbitan produk atau pelaksanaan aktivitas baru; dan <br>
                    • terdapat ketidaksesuaian yang signifikan antara kebijakan dan prosedur produk dan/atau aktivitas baru dengan ketentuan.',
                    4 => '• memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru yang memiliki eksposur Risiko stratejik; <br>
                    • tidak menerapkan kebijakan dan prosedur dalam hal terdapat penerbitan produk atau pelaksanaan aktivitas baru; dan <br>
                    • terdapat ketidaksesuaian yang signifikan antara kebijakan dan prosedur produk dan/atau aktivitas baru dengan ketentuan.',
                    5 => 'tidak memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru yang memiliki eksposur Risiko stratejik.'
                ],
                'catatan' => 'Penilaian parameter risiko stratejik KPMR'
            ],

            195 => [
                'descriptions' => [
                    1 => '• telah melaksanakan proses Manajemen Risiko stratejik meliputi identifikasi, pengukuran, pemantauan, dan pengendalian Risiko stratejik terhadap seluruh kegiatan usaha BPR termasuk realisasi dari target pencapaian BPR; <br>
                    • penerapan Manajemen Risiko stratejik dilakukan dengan sangat memadai; dan <br>
                    • penerapan Manajemen Risiko stratejik dilakukan secara konsisten.',
                    2 => "• telah melaksanakan proses Manajemen Risiko stratejik meliputi identifikasi, pengukuran, pemantauan, dan pengendalian Risiko stratejik terhadap seluruh kegiatan usaha BPR termasuk realisasi dari target pencapaian BPR; <br>
                    • penerapan Manajemen Risiko stratejik dilakukan dengan memadai; dan <br>
                    • penerapan Manajemen Risiko stratejik dilakukan cukup konsisten. ",
                    3 => '• telah melaksanakan proses Manajemen Risiko stratejik meliputi identifikasi, pengukuran, pemantauan, dan pengendalian Risiko stratejik terhadap seluruh kegiatan usaha BPR termasuk realisasi dari target pencapaian BPR; <br>
                    • penerapan Manajemen Risiko stratejik dilakukan dengan memadai; dan <br>
                    • penerapan Manajemen Risiko stratejik tidak dilakukan secara konsisten namun tidak menimbulkan dampak yang signifikan.',
                    4 => '• telah melaksanakan proses Manajemen Risiko stratejik namun tidak secara keseluruhan meliputi identifikasi, pengukuran, pemantauan, dan pengendalian Risiko stratejik terhadap seluruh kegiatan usaha BPR termasuk realisasi dari target pencapaian BPR; <br>
                    • penerapan Manajemen Risiko stratejik tidak memadai; dan <br>
                    • penerapan Manajemen Risiko stratejik tidak dilakukan secara konsisten sehingga menimbulkan dampak yang signifikan.',
                    5 => 'tidak melaksanakan proses Manajemen Risiko stratejik meliputi identifikasi, pengukuran, pemantauan, dan pengendalian Risiko stratejik.'
                ],
                'catatan' => 'Penilaian parameter risiko stratejik KPMR'
            ],

            196 => [
                'descriptions' => [
                    1 => 'Sangat Baik',
                    2 => 'Baik',
                    3 => 'Cukup',
                    4 => 'Kurang Baik',
                    5 => 'Buruk'
                ],
                'catatan' => 'Penilaian parameter risiko stratejik KPMR'
            ],

            197 => [
                'descriptions' => [
                    1 => '• SKAI atau PEAItelah melaksanakan audit intern terhadap penerapan Manajemen Risiko stratejik, memberikan rekomendasi, dan melaporkan hasil audit intern kepada Direktur Utama; <br>
                    • audit intern telah dilaksanakan oleh SKAI atau PEAI meliputi pelaksanaan kebijakan dan prosedur Manajemen Risiko stratejik dengan mempertimbangk an ketentuan serta kondisi BPR; dan <br>
                    • hasil temuan audit intern yang dijadikan rekomendasi telah ditindaklanjuti',
                    2 => "• SKAI atau PEAI telah melaksanakan audit intern terhadap penerapan Manajemen Risiko stratejik, memberikan rekomendasi, dan melaporkan hasil audit intern kepada Direktur Utama; <br>
                    • audit intern telah dilaksanakan oleh SKAI atau PEAI meliputi pelaksanaan kebijakan dan prosedur Manajemen Risiko stratejik dengan mempertimbangk an ketentuan serta kondisi BPR; dan <br>
                    • hasil temuan audit intern yang dijadikan rekomendasi tidak sepenuhnya ditindaklanjuti namun tidak menimbulkan dampak yang signifikan.",
                    3 => '• SKAI atau PEAItelah melaksanakan audit intern terhadap penerapan Manajemen Risiko stratejik, memberikan rekomendasi, dan melaporkan hasil audit intern kepada Direktur Utama; <br>
                    • audit intern telah dilaksanakan oleh SKAI atau PEAI meliputi pelaksanaan kebijakan dan prosedur Manajemen Risiko stratejik dengan mempertimbangkan ketentuan serta kondisi BPR; dan <br>
                    • hasil temuan audit intern yang dijadikan rekomendasi tidak sepenuhnya ditindaklanjuti dan menimbulkan dampak yang signifikan.',
                    4 => '• SKAI atau PEAtelah melaksanakan audit intern terhadap penerapan Manajemen Risiko stratejik, memberikan rekomendasi, dan melaporkan hasil audit intern kepada Direktur Utama; <br>
                    • audit intern telah dilaksanakan oleh SKAI atau PEAI namun tidak sesuai dengan cakupan pelaksanaan kebijakan dan prosedur Manajemen Risiko stratejik dengan mempertimbangk an ketentuan serta kondisi BPR; dan <br>
                    • hasil temuan audit intern yang dijadikan rekomendasi tidak ditindaklanjuti.',
                    5 => 'SKAI atau PEAItidak melaksanakan audit intern terhadap penerapan Manajemen Risiko stratejik.'
                ],
                'catatan' => 'Penilaian parameter risiko stratejik KPMR'
            ],

            198 => [
                'descriptions' => [
                    1 => 'Tidak terjadihuman error pada BPR. ',
                    2 => "• Terjadi human error pada BPR; namun <br>
                    • Tidak berdampak finansial bagi BPR. ",
                    3 => '• Terjadi human error pada BPR; dan <br>
                    • mengurangi keuntungan namun tidak menyebabkan BPR membukukan laba negatif.',
                    4 => '• Terjadi human error pada BPR; dan <br>
                    • BPR membukukan laba negatif yang menyebabkan rasio permodalan menurun namun masih sesuai ketentuan KPMM ',
                    5 => '• Terjadi human error pada BPR; dan <br>
                    • BPR membukukan laba negatif yang menyebabkan rasio permodalan menurun di bawah ketentuan KPMM.'
                ],
                'catatan' => 'Penilaian parameter risiko stratejik KPMR'
            ],

        ];
    }

    public function cekKomentarBaru()
    {
        $subkategori = 'STRATEJIKKPMR';
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
            'STRATEJIKKPMR',
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
            $this->db->table('stratejikkpmr')
                ->where('faktor1id', $faktor1id)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update($data);

            $categoryMapping = [
                182 => [183, 184, 185, 186, 187, 188],
                189 => [190, 191, 192],
                193 => [194, 195],
                196 => [197, 198]
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

            if (in_array($faktor1id, [182, 183, 184, 185, 186, 187, 188, 189, 190, 191, 192, 194, 195, 196, 197, 198])) {
                $rataRata = $this->nilaiModel->hitungRataRata(199, $this->userKodebpr, $this->periodeId);
                $this->nilaiModel->insertOrUpdateRataRata($rataRata, 199, $this->userKodebpr, $this->periodeId);
            }

            $this->db->table('stratejikkpmr')
                ->where('faktor1id', 199)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update(['accdir2' => 0, 'is_approved' => 0]);

            $nilai13 = $this->nilaiModel->where([
                'faktor1id' => 199,
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
            $this->db->table('stratejikkpmr')
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
            $result = $this->db->table('stratejikkpmr')
                ->where('faktor1id', $faktor1id)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update($data);

            if ($result) {
                $this->db->table('stratejikkpmr')
                    ->where('faktor1id', 199)
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
            $this->db->table('stratejikkpmr')->insert($data);

            $categoryMapping = [
                182 => [183, 184, 185, 186, 187, 188],
                189 => [190, 191, 192],
                193 => [194, 195],
                196 => [197, 198]
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

            if (in_array($faktorId, [182, 183, 184, 185, 186, 187, 188, 189, 190, 191, 192, 194, 195, 196, 197, 198])) {
                $rataRata = $this->nilaiModel->hitungRataRata(199, $this->userKodebpr, $this->periodeId);
                $this->nilaiModel->insertOrUpdateRataRata($rataRata, 199, $this->userKodebpr, $this->periodeId);
            }

            $nilai13 = $this->nilaiModel->where([
                'faktor1id' => 199,
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
            return redirect()->to(base_url('Stratejikkpmr'));
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
                'subkategori' => "STRATEJIKKPMR",
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
                        'STRATEJIKKPMR',
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

                return redirect()->to(base_url('Stratejikkpmr') . '?modal_komentar=' . $faktorId)
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

        return redirect()->to(base_url('Stratejikkpmr'));
    }

    public function approve($idNilai = null)
    {
        if ($idNilai === null) {
            return redirect()->back()->with('err', 'ID tidak valid');
        }

        $this->db->table('stratejikkpmr')
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
            ->whereIn('faktor1id', range(182, 199))
            ->first();

        if ($checkData['filled_count'] < 18) {
            return redirect()->back()->with('err', 'Semua faktor harus diisi terlebih dahulu');
        }

        try {
            $this->db->table('stratejikkpmr')
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->whereIn('faktor1id', range(182, 199))
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
            $this->db->table('stratejikkpmr')
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->whereIn('faktor1id', range(182, 199))
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

        $this->db->table('stratejikkpmr')
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
            return redirect()->to('/Stratejikkpmr')->with('error', 'Data tidak lengkap');
        }

        $nilaiFaktor = $this->nilaiModel
            ->where('faktor1id', $faktor1id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if (!$nilaiFaktor) {
            return redirect()->to('/Stratejikkpmr')->with('error', 'Data tidak ditemukan');
        }

        $dataUpdate = [
            'accdir2' => 0,
            'accdir2_by' => $this->userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->nilaiModel->update($nilaiFaktor['id'], $dataUpdate)) {
            return redirect()->to('/Stratejikkpmr')->with('message', 'Persetujuan dibatalkan');
        }

        return redirect()->to('/Stratejikkpmr')->with('error', 'Gagal memperbarui data');
    }

    public function recalculateCategoryAverage()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $faktorId = $this->request->getPost('faktor_id');

        $categoryMapping = [
            182 => [183, 184, 185, 186, 187, 188],
            189 => [190, 191, 192],
            193 => [194, 195],
            196 => [197, 198]
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

            $rataRata = $this->nilaiModel->hitungRataRata(199, $this->userKodebpr, $this->periodeId);
            $this->nilaiModel->insertOrUpdateRataRata($rataRata, 199, $this->userKodebpr, $this->periodeId);

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
            "STRATEJIKKPMR",
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
            "STRATEJIKKPMR",
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

        $faktorIds = range(182, 199);
        $counts = [];

        foreach ($faktorIds as $faktorId) {
            $count = $this->commentReadsModel->countUnreadCommentsForUserByFactor(
                $faktorId,
                "STRATEJIKKPMR",
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

        $faktorId = 200;
        $penilaiankredit = $this->request->getPost('penilaiankredit');
        $user = $this->userModel->find($this->userId);

        $penjelasanMapping = [
            '1' => 'Tingkat Risiko Stratejik KPMR: Sangat Rendah',
            '2' => 'Tingkat Risiko Stratejik KPMR: Rendah',
            '3' => 'Tingkat Risiko Stratejik KPMR: Sedang',
            '4' => 'Tingkat Risiko Stratejik KPMR: Tinggi',
            '5' => 'Tingkat Risiko Stratejik KPMR: Sangat Tinggi'
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
                    'keterangan' => 'Tingkat Risiko Stratejik Inheren Posisi Sebelumnya',
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
                    return redirect()->to(base_url('Stratejikkpmr'))
                        ->with('message', 'Data Tingkat Risiko Kredit Inheren berhasil diupdate');
                } else {
                    throw new \Exception('Gagal mengupdate data');
                }

            } else {
                $insertData = [
                    'faktor1id' => $faktorId,
                    'penilaiankredit' => $penilaiankredit,
                    'penjelasanpenilaian' => $penjelasanpenilaian,
                    'keterangan' => 'Tingkat Risiko Stratejik Inheren Posisi Sebelumnya',
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
                    return redirect()->to(base_url('Stratejikkpmr'))
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
        $faktorId = 199;
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
            ->where('faktor1id', 199)
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

        $faktorId = 199;
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
                    return redirect()->to(base_url('Stratejikkpmr'))
                        ->with('message', 'Kesimpulan berhasil diupdate');
                } else {
                    throw new \Exception('Gagal mengupdate kesimpulan');
                }

            } else {
                $rataRata = $this->nilaiModel->hitungRataRata($faktorId, $this->userKodebpr, $this->periodeId);
                $rataRata = ($rataRata - floor($rataRata) >= 0.5) ? ceil($rataRata) : floor($rataRata);

                $penjelasanMapping = [
                    '1' => 'Tingkat Risiko Stratejik KPMR: Sangat Rendah',
                    '2' => 'Tingkat Risiko Stratejik KPMR: Rendah',
                    '3' => 'Tingkat Risiko Stratejik KPMR: Sedang',
                    '4' => 'Tingkat Risiko Stratejik KPMR: Tinggi',
                    '5' => 'Tingkat Risiko Stratejik KPMR: Sangat Tinggi'
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
                    return redirect()->to(base_url('Stratejikkpmr'))
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
            ->where('faktor1id', 200)
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

        $existingCategory = $this->nilaiModel
            ->where('faktor1id', $categoryFaktorId)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $dataCategory = [
            'faktor1id' => $categoryFaktorId,
            'penilaiankredit' => $roundedAverage,
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
                'id' => 182,
                'title' => 'Pilar Pengawasan Direksi dan Dewan Komisaris',
                'type' => 'category',
                'faktor_id' => 182,
                'faktor_ids' => [183, 184, 185, 186, 187, 188],
                'description' => 'Pilar Pengawasan Direksi dan Dewan Komisaris',
                'children' => [
                    [
                        'id' => 183,
                        'title' => 'Apakah Dewan Komisaris telah melakukan persetujuan terhadap kebijakan manajemen risiko kepatuhan yang disusun oleh Direksi dan melakukan evaluasi secara berkala?',
                        'type' => 'parameter',
                        'faktor_id' => 183,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 184,
                        'title' => 'Apakah Dewan Komisaris telah melakukan evaluasi terhadap pertanggungjawaban Direksi atas pelaksanaan kebijakan manajemen risiko kepatuhan secara berkala dan memastikan tindak lanjut hasil evaluasi dimaksud?',
                        'type' => 'parameter',
                        'faktor_id' => 184,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 185,
                        'title' => 'Apakah Direksi telah menyusun kebijakan manajemen risiko kepatuhan, melaksanakan secara konsisten, dan melakukan pengkinian secara berkala?',
                        'type' => 'parameter',
                        'faktor_id' => 185,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 186,
                        'title' => 'Apakah Direksi telah memiliki kemampuan untuk mengambil tindakan yang diperlukan dalam rangka mitigasi risiko kepatuhan, dan melakukan komunikasi kebijakan manajemen risiko kepatuhan terhadap seluruh jenjang organisasi BPR?',
                        'type' => 'parameter',
                        'faktor_id' => 186,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 187,
                        'title' => 'Apakah BPR telah memiliki kecukupan organisasi yang menangani fungsi kepatuhan dan fungsi manajemen risiko kepatuhan?',
                        'type' => 'parameter',
                        'faktor_id' => 187,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 188,
                        'title' => 'Apakah Direksi telah menerapkan kebijakan pengelolaan SDM dalam rangka penerapan Manajemen Risiko kepatuhan?',
                        'type' => 'parameter',
                        'faktor_id' => 188,
                        'previous_periode_faktor_id' => null
                    ]
                ]
            ],
            [
                'id' => 189,
                'title' => 'Pilar Kecukupan Kebijakan, Prosedur, dan Limit',
                'type' => 'category',
                'faktor_id' => 189,
                'faktor_ids' => [190, 191, 192],
                'description' => 'Pilar Kecukupan Kebijakan, Prosedur, dan Limit',
                'children' => [
                    [
                        'id' => 190,
                        'title' => 'Apakah BPR telah memiliki kebijakan manajemen risiko kepatuhan yang memadai dan disusun dengan mempertimbangkan visi, misi, skala usaha dan kompleksitas bisnis, serta kecukupan SDM?',
                        'type' => 'parameter',
                        'faktor_id' => 190,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 191,
                        'title' => 'Apakah BPR: 
                        ● Memiliki prosedur manajemen risiko kepatuhan dan penetapan limit risiko kepatuhan yang ditetapkan oleh Direksi; 
                        ● Melaksanakan prosedur manajemen risiko kepatuhan dan penetapan limit risiko kepatuhan secara konsisten untuk seluruh aktivitas; dan 
                        ● Melakukan evaluasi dan pengkinian terhadap prosedur manajemen risiko kepatuhan dan penetapan limit risiko kepatuhan secara berkala?',
                        'type' => 'parameter',
                        'faktor_id' => 191,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 192,
                        'title' => 'Apakah BPR telah memiliki kebijakan dan prosedur penerbitan produk dan/atau pelaksanaan aktivitas baru yang mencakup identifikasi dan mitigasi risiko kepatuhan sesuai dengan ketentuan?',
                        'type' => 'parameter',
                        'faktor_id' => 192,
                        'previous_periode_faktor_id' => null
                    ]
                ]
            ],
            [
                'id' => 193,
                'title' => 'Kecukupan Proses dan Sistem Manajemen Informasi',
                'type' => 'category',
                'faktor_id' => 193,
                'faktor_ids' => [194, 195],
                'description' => 'Kecukupan Proses dan Sistem Manajemen Informasi',
                'children' => [
                    [
                        'id' => 194,
                        'title' => 'Apakah BPR telah melaksanakan proses manajemen risiko kepatuhan yang melekat pada kegiatan usaha BPR?',
                        'type' => 'parameter',
                        'faktor_id' => 194,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 195,
                        'title' => 'Apakah BPR telah memiliki sistem informasi manajemen risiko yang mendukung Direksi dalam pengambilan keputusan terkait risiko  kepatuhan serta telah dilaporkan kepada Direksi secara berkala?',
                        'type' => 'parameter',
                        'faktor_id' => 195,
                        'previous_periode_faktor_id' => null
                    ]
                ]
            ],
            [
                'id' => 196,
                'title' => 'Sistem Pengendalian Internal yang Menyeluruh',
                'type' => 'category',
                'faktor_id' => 196,
                'faktor_ids' => [197, 198],
                'description' => 'Sistem Pengendalian Internal yang Menyeluruh',
                'children' => [
                    [
                        'id' => 197,
                        'title' => 'Apakah SKAI atau PEAI telah melaksanakan audit secara berkala terhadap penerapan manajemen risiko kepatuhan, menyampaikan laporan hasil audit intern, dan memastikan tindaklanjut atas temuan pemeriksaan?',
                        'type' => 'parameter',
                        'faktor_id' => 197,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 198,
                        'title' => 'Apakah sistem pengendalian intern terhadap risiko kepatuhan telah dilaksanakan oleh seluruh jenjang organisasi BPR?',
                        'type' => 'parameter',
                        'faktor_id' => 198,
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

    private function recalculateCategoryAverages($changedFaktorId, $kodebpr, $periodeId)
    {
        $categoryMapping = [
            182 => [183, 184, 185, 186, 187, 188],
            189 => [190, 191, 192],
            193 => [194, 195],
            196 => [197, 198]
        ];

        foreach ($categoryMapping as $categoryId => $childrenIds) {
            if (in_array($changedFaktorId, $childrenIds)) {
                $this->calculateAndSaveCategoryAverage($categoryId, $childrenIds, $kodebpr, $periodeId);
                break;
            }
        }
    }

    public function exporttxtrisikostratejikkpmr()
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
        $data_stratejikkpmr = $this->nilaiModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        // BPR Info
        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'] ?? '';
            $kodejenis = $infobpr['kodejenis'] ?? '';
        }

        function sanitizeTxtStraKpmr($text)
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
            182 => '6310',
            183 => '6311',
            184 => '6312',
            185 => '6313',
            186 => '6314',
            187 => '6315',
            188 => '6316',
            189 => '6320',
            190 => '6321',
            191 => '6322',
            192 => '6323',
            193 => '6330',
            194 => '6331',
            195 => '6332',
            196 => '6340',
            197 => '6341',
            198 => '6342',
            199 => '6350',
        ];

        $output = "";

        // Header
        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|PRBPRKS|0602|{$modalinti}|{$totalaset}|{$kantorcabang}|{$atmdebit}|{$kategori}||\r\n";

        usort($data_stratejikkpmr, function ($a, $b) {
            return ($a['faktor1id'] ?? 0) <=> ($b['faktor1id'] ?? 0);
        });

        // Data rows
        foreach ($data_stratejikkpmr as $row) {
            $faktorId = $row['faktor1id'] ?? null;
            if (!isset($kodeMap[$faktorId])) {
                continue; // <- skip data yang tidak punya kode
            }
            $kode = $kodeMap[$faktorId] ?? ''; // ambil kode sesuai faktor

            $penilaiankredit = sanitizeTxtStraKpmr($row['penilaiankredit']);
            $keterangan = sanitizeTxtStraKpmr($row['keterangan']);

            $output .= "D01|{$kode}|{$penilaiankredit}|{$keterangan}\r\n";
        }

        // Filename
        $filename = "PRBPRKS-0602-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        // Response
        $response = service('response');
        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response->setBody($output);
    }
}