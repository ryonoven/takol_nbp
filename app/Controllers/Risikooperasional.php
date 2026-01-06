<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_paramprofilrisiko;
use App\Models\M_user;
use App\Models\M_profilrisikocomments;
use App\Models\M_risikooperasional;
use App\Models\M_infobpr;
use App\Models\M_periodeprofilresiko;
use App\Models\M_profilrisikocommentsread;
use App\Models\M_showprofilresiko;
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
    protected $userGroups = [];

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');

        $this->db = \Config\Database::connect();

        $this->paramprofilrisikoModel = new M_paramprofilrisiko();
        $this->periodeModel = new M_periodeprofilresiko();
        $this->userModel = new M_user();
        $this->komentarModel = new M_profilrisikocomments();
        $this->nilaiModel = new M_risikooperasional();
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
            'faktor1id' => 48,
            'kodebpr' => $this->userKodebpr,
            'periode_id' => $this->periodeId
        ])->first();

        $nilai14 = $this->nilaiModel->where([
            'faktor1id' => 49,
            'kodebpr' => $this->userKodebpr,
            'periode_id' => $this->periodeId
        ])->first();

        $requiredFaktor = array_merge(range(36, 46), [48]);
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

        ];

        return view('templates/v_header', $data)
            . view('templates/v_sidebar')
            . view('templates/v_topbar')
            . view('risikooperasional/index', $data)
            . view('templates/v_footer');
    }

    private function checkCanApprove($nilaiLookup)
    {
        $requiredFaktorIds = range(36, 47);

        foreach ($requiredFaktorIds as $faktorId) {
            if (empty($nilaiLookup[$faktorId])) {
                return false;
            }
        }

        return true;
    }

    private function checkAccdekomApproved($nilaiLookup)
    {
        for ($faktorId = 36; $faktorId <= 47; $faktorId++) {
            if (!isset($nilaiLookup[$faktorId]) || $nilaiLookup[$faktorId]['is_approved'] != 1) {
                return false;
            }
        }
        return true;
    }

    private function checkAllApproved($nilaiLookup)
    {
        $requiredFaktorIds = range(36, 48);

        foreach ($requiredFaktorIds as $faktorId) {

            if ($faktorId == 47 && !isset($nilaiLookup[$faktorId])) {
                continue;
            }

            if (
                !isset($nilaiLookup[$faktorId]) ||
                $nilaiLookup[$faktorId]['is_approved'] != 1
            ) {
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

        $nilaiData = $this->db->table('risikooperasional as r')
            ->select('r.*, u.fullname')
            ->join('users as u', 'u.id = r.user_id', 'left')
            ->where('r.kodebpr', $this->userKodebpr)
            ->where('r.periode_id', $this->periodeId)
            ->whereIn('r.faktor1id', range(36, 47))
            ->get()
            ->getResultArray();

        $nilaiLookup = [];
        foreach ($nilaiData as $nilai) {
            $nilaiLookup[$nilai['faktor1id']] = $nilai;
        }

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
                    ['id' => 40, 'title' => 'Tindakan korporasi', 'faktor_id' => 40]
                ]
            ],
            [
                'id' => 41,
                'title' => 'Sumber daya manusia (SDM)',
                'type' => 'category',
                'faktor_id' => 41,
                'children' => [
                    [
                        'id' => 42,
                        'title' => 'Kecukupan kuantitas dan kualitas SDM',
                        'faktor_id' => 42
                    ],
                    [
                        'id' => 43,
                        'title' => 'Permasalahan operasional karena faktor manusia (human error)',
                        'faktor_id' => 43
                    ]
                ]
            ],
            [
                'id' => 44,
                'title' => 'Penyelenggaraan teknologi informasi (TI)',
                'type' => 'single',
                'faktor_id' => 44
            ],
            [
                'id' => 45,
                'title' => 'Pilar penyimpangan (Fraud)',
                'type' => 'single',
                'faktor_id' => 45
            ],
            [
                'id' => 46,
                'title' => 'Faktor eksternal',
                'type' => 'single',
                'faktor_id' => 46
            ],
            [
                'id' => 47,
                'title' => 'Lainnya',
                'type' => 'single',
                'faktor_id' => 47
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

            36 => [
                'descriptions' => [
                    1 => 'Parameter Kompleksitas bisnis dan kelembagaan berada pada tingkat risiko yang Sangat Rendah',
                    2 => 'Parameter Kompleksitas bisnis dan kelembagaan berada pada tingkat risiko yang Rendah',
                    3 => 'Parameter Kompleksitas bisnis dan kelembagaan berada pada tingkat risiko yang Sedang',
                    4 => 'Parameter Kompleksitas bisnis dan kelembagaan berada pada tingkat risiko yang Tinggi',
                    5 => 'Parameter Kompleksitas bisnis dan kelembagaan berada pada tingkat risiko yang Sangat Tinggi'
                ],
                'catatan' => 'Penilaian parameter risiko operasional inheren'
            ],

            37 => [
                'descriptions' => [
                    1 => '• Skala usaha BPR tergolong kecil atau menengah; dan <br>
                    • Struktur organisasi BPR terpenuhi lengkap sesuai ketentuan tata kelola BPR.',
                    2 => '• Skala usaha BPR tergolong besar; dan <br>
                    • Struktur organisasi BPR terpenuhi lengkap sesuai ketentuan tata kelola BPR.',
                    3 => '• Skala usaha BPR tergolong kecil atau menengah; dan <br>
                    • Terdapat ketidaklengkap an struktur organisasi BPR pada fungsi yang tidak signifikan.',
                    4 => '• Skala usaha BPR tergolong besar; dan <br>
                    • Terdapat ketidaklengkap an struktur organisasi BPR pada fungsi yang tidak signifikan.',
                    5 => '• Skala usaha BPR tergolong kecil, menengah, atau besar; dan<br> 
                    • Terdapat ketidaklengkap an struktur organisasi BPR pada fungsi yang signifikan.'
                ],
                'catatan' => 'Penilaian parameter risiko operasional inheren'
            ],

            38 => [
                'descriptions' => [
                    1 => 'BPR tidak memiliki jaringan kantor cabang dan/atau kantor kas.',
                    2 => '• BPR memiliki jumlah jaringan kantor cabang paling banyak 25% dari maksimal yang diperkenankan untuk skala KU <br>
                    • Memiliki kantor kas Rentang kendali kecil dan lokasi kantor cabang dapat diakses dengan mudah',
                    3 => '• BPR memiliki jumlah jaringan kantor cabang lebih dari 25% dan paling banyak 50% dari maksimal yang diperkenankan untuk skala KU <br>
                    • Memiliki kantor kas Rentang kendali kecil namun terdapat lokasi kantor cabang yang sulit diakses',
                    4 => '• BPR memiliki jumlah jaringan kantor cabang lebih dari 50% dan paling banyak 75% dari maksimal yang diperkenankan untuk skala KU <br>
                    • Memiliki kantor kas Rentang kendali besar dan lokasi kantor cabang dapat diakses dengan mudah',
                    5 => '• BPR memiliki jumlah jaringan kantor cabang lebih dari 75% dari maksimal yang diperkenankan untuk skala KU <br>
                    • Memiliki kantor kas Rentang kendali besar dan terdapat lokasi kantor cabang yang sulit diakses'
                ],
                'catatan' => 'Penilaian parameter risiko operasional inheren'
            ],

            39 => [
                'descriptions' => [
                    1 => 'BPR memiliki produk/jasa yang termasuk kegiatan usaha utama',
                    2 => '• BPR memiliki produk/jasa yang termasuk kegiatan usaha utama; dan <br>
                    • penukaran valuta asing; dan/atau <br>
                    • layanan kerjasama pihak ketigayang tidak memerlukan kompetensi tinggi dan tidak melibatkan teknologi (misalnya agen pemasaran uang elektronik berbasis kartuatau e-money)',
                    3 => 'BPR memiliki produk/jasa yang termasuk kegiatan usaha utama dan melaksanakan kegiatan usaha layanan kerjasama pihak ketiga yang melibatkan teknologi milik pihak ketiga (misalnya agen uang elektronik berbasis server atau e-cash)',
                    4 => 'BPR melaksanakan kegiatan usaha sebagai penyelenggara layanan berbasis teknologi misalnya sebagai issuer/penerbit kartu ATM, atau penyelenggara internet banking',
                    5 => 'dan wilayah jaringan kantor BPR berdasarkan modal inti (antara lain kegiatan usaha tidak sesuai dengan kelompok BPRKU, kegiatan usaha tidak dilaporkan atau memperoleh izin/persetujuan dari OJK atau BI)'
                ],
                'catatan' => 'Penilaian parameter risiko operasional inheren'
            ],

            40 => [
                'threshold' => '85%',
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
                'catatan' => 'Penilaian parameter risiko operasional inheren'
            ],

            41 => [
                'descriptions' => [
                    1 => 'Parameter Sumber daya manusia (SDM) berada pada tingkat risiko yang Sangat Rendah',
                    2 => 'Parameter Sumber daya manusia (SDM) berada pada tingkat risiko yang Rendah',
                    3 => 'Parameter Sumber daya manusia (SDM) berada pada tingkat risiko yang Sedang',
                    4 => 'Parameter Sumber daya manusia (SDM) berada pada tingkat risiko yang Tinggi',
                    5 => 'Parameter Sumber daya manusia (SDM) berada pada tingkat risiko yang Sangat Tinggi'
                ],
                'catatan' => 'Penilaian parameter risiko operasional inheren'
            ],

            42 => [
                'descriptions' => [
                    1 => 'Kuantitas dan kualitas SDM BPR sangat memadai.',
                    2 => 'Kuantitas dan kualitas SDM BPR memadai.',
                    3 => 'Kuantitas dan kualitas SDM BPR cukup memadai.',
                    4 => 'Kuantitas dan kualitas SDM BPR kurang memadai.',
                    5 => 'Kuantitas dan kualitas SDM BPR tidak memadai.'
                ],
                'catatan' => 'Penilaian parameter risiko operasional inheren'
            ],

            43 => [
                'threshold' => '5%',
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
                'catatan' => 'Penilaian parameter risiko operasional inheren'
            ],

            44 => [
                'descriptions' => [
                    1 => '• TI BPR sesuai dengan ketentuan mengenai SPTI; dan <br>
                    • BPR tidak sedang dalam proses melakukan perubahan mendasar penyelenggaraan TI.',
                    2 => '• TI BPR sebagian besar sesuai dengan ketentuan mengenai SPTI; dan <br>
                    • BPR tidak sedang dalam proses melakukan perubahan mendasar penyelenggaraan TI.',
                    3 => '• TI BPR sebagian besar sesuai dengan ketentuan mengenai SPTI; dan <br>
                    • BPR sedang dalam proses melakukan perubahan mendasar penyelenggaraan TI.',
                    4 => '• TI BPR sebagian besar tidak sesuai dengan ketentuan mengenai SPTI; dan <br>
                    • BPR tidak sedang dalam proses melakukan perubahan mendasar penyelenggaraan TI.',
                    5 => '• TI BPR sebagian besar tidak sesuai dengan ketentuan mengenai SPTI; dan <br>
                    • BPR sedang dalam proses melakukan perubahan mendasar penyelenggaraan TI.'
                ],
                'catatan' => 'Penilaian parameter risiko operasional inheren'
            ],

            45 => [
                'descriptions' => [
                    1 => 'Tidak terdapat indikasi penyimpangan (fraud) pada BPR',
                    2 => '• Terdapat indikasi penyimpangan (fraud) pada BPR dengan frekuensi yang rendah; dan <br>
                    • belum/tidak berdampak finansial',
                    3 => '• Terdapat indikasi penyimpangan (fraud) pada BPR dengan frekuensi tinggi; dan 
                    • mengurangi keuntungan namun tidak menyebabkan BPR membukukan laba negatif dan tidak menyebabkan rasio permodalan menurun',
                    4 => '• Terdapat indikasi penyimpangan (fraud) pada BPR yang signifikan; dan 
                    • mengurangi keuntungan atau BPR membukukan laba negatif yang menyebabkan rasio permodalan menurun namun masih sesuai ketentuan KPMM',
                    5 => '• Terdapat indikasi penyimpangan (fraud) pada BPR yang sangat signifikan; dan <br>
                    • BPR membukukan laba negatif yang menyebabkan rasio permodalan menurun di bawah ketentuan KPMM'
                ],
                'catatan' => 'Penilaian parameter risiko operasional inheren'
            ],

            46 => [
                'descriptions' => [
                    1 => 'Tidak terdapat kejadian eksternal',
                    2 => '• Terdapat kejadian eksternal; namun <br>
                    • tidak berdampak finansial bagi BPR',
                    3 => '• Terdapat kejadian eksternal; dan <br>
                    • mengurangi keuntungan namun tidak menyebabkan BPR membukukan laba negatif',
                    4 => '• Terdapat kejadian eksternal; dan <br>
                    • BPR membukukan laba negatif yang menyebabkan rasio permodalan menurun namun masih sesuai ketentuan KPMM.',
                    5 => '• Terdapat kejadian eksternal; dan <br>
                    • BPR membukukan laba negatif yang menyebabkan rasio permodalan menurun di bawah ketentuan KPMM.'
                ],
                'catatan' => 'Penilaian parameter risiko operasional inheren'
            ],

            47 => [
                'descriptions' => [
                    1 => 'Parameter Penilaian Risiko Operasional Inheren berada pada tingkat risiko yang Sangat Rendah',
                    2 => 'Parameter Penilaian Risiko Operasional Inheren berada pada tingkat risiko yang Rendah',
                    3 => 'Parameter Penilaian Risiko Operasional Inheren berada pada tingkat risiko yang Sedang',
                    4 => 'Parameter Penilaian Risiko Operasional Inheren berada pada tingkat risiko yang Tinggi',
                    5 => 'Parameter Penilaian Risiko Operasional Inheren berada pada tingkat risiko yang Sangat Tinggi'
                ],
                'catatan' => 'Penilaian parameter risiko operasional inheren'
            ],
        ];
    }

    public function cekKomentarBaru()
    {
        $subkategori = 'OPERASIONALINHEREN';
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
            'OPERASIONALINHEREN',
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
            $this->db->table('risikooperasional')
                ->where('faktor1id', $faktor1id)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update($data);

            $categoryMapping = [
                36 => [37, 38, 39, 40],
                41 => [42, 43, 44],
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

            if (in_array($faktor1id, [36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47])) {
                $rataRata = $this->nilaiModel->hitungRataRata(48, $this->userKodebpr, $this->periodeId);
                $this->nilaiModel->insertOrUpdateRataRata($rataRata, 48, $this->userKodebpr, $this->periodeId);
            }

            $this->db->table('risikooperasional')
                ->where('faktor1id', 48)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update(['accdir2' => 0, 'is_approved' => 0]);

            $nilai13 = $this->nilaiModel->where([
                'faktor1id' => 48,
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
            $this->db->table('risikooperasional')
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
            $result = $this->db->table('risikooperasional')
                ->where('faktor1id', $faktor1id)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update($data);

            if ($result) {
                $this->db->table('risikooperasional')
                    ->where('faktor1id', 48)
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
            $this->db->table('risikooperasional')->insert($data);

            $categoryMapping = [
                36 => [37, 38, 39, 40],
                41 => [42, 43, 44]
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

            if (in_array($faktorId, [36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47])) {
                $rataRata = $this->nilaiModel->hitungRataRata(48, $this->userKodebpr, $this->periodeId);
                $this->nilaiModel->insertOrUpdateRataRata($rataRata, 48, $this->userKodebpr, $this->periodeId);
            }

            $nilai13 = $this->nilaiModel->where([
                'faktor1id' => 48,
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
            return redirect()->to(base_url('Risikooperasional'));
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
                'subkategori' => "OPERASIONALINHEREN",
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
                        'OPERASIONALINHEREN',
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

        return redirect()->to(base_url('Risikooperasional'));
    }

    public function approve($idNilai = null)
    {
        if ($idNilai === null) {
            return redirect()->back()->with('err', 'ID tidak valid');
        }

        $this->db->table('risikooperasional')
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

        // Faktor wajib: 36–46 dan 48 (47 diskip)
        $requiredFaktor = array_merge(range(36, 46), [48]);
        $totalRequired = count($requiredFaktor); // = 12

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
            // Approve hanya faktor wajib
            $this->db->table('risikooperasional')
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
            $this->db->table('risikooperasional')
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->whereIn('faktor1id', range(36, 48))
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

        $this->db->table('risikooperasional')
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
            return redirect()->to('/Risikooperasional')->with('error', 'Data tidak lengkap');
        }

        $nilaiFaktor = $this->nilaiModel
            ->where('faktor1id', $faktor1id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if (!$nilaiFaktor) {
            return redirect()->to('/Risikooperasional')->with('error', 'Data tidak ditemukan');
        }

        $dataUpdate = [
            'accdir2' => 0,
            'accdir2_by' => $this->userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->nilaiModel->update($nilaiFaktor['id'], $dataUpdate)) {
            return redirect()->to('/Risikooperasional')->with('message', 'Persetujuan dibatalkan');
        }

        return redirect()->to('/Risikooperasional')->with('error', 'Gagal memperbarui data');
    }

    public function recalculateCategoryAverage()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $faktorId = $this->request->getPost('faktor_id');

        $categoryMapping = [
            36 => [37, 38, 39, 40],
            41 => [42, 43, 44],
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

            $rataRata = $this->nilaiModel->hitungRataRata(48, $this->userKodebpr, $this->periodeId);
            $this->nilaiModel->insertOrUpdateRataRata($rataRata, 48, $this->userKodebpr, $this->periodeId);

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
            "OPERASIONALINHEREN",
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
            "OPERASIONALINHEREN",
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

        $faktorIds = range(36, 48);
        $counts = [];

        foreach ($faktorIds as $faktorId) {
            $count = $this->commentReadsModel->countUnreadCommentsForUserByFactor(
                $faktorId,
                "OPERASIONALINHEREN",
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

        $faktorId = 49;
        $penilaiankredit = $this->request->getPost('penilaiankredit');

        $user = $this->userModel->find($this->userId);

        $penjelasanMapping = [
            '1' => 'Tingkat Risiko Operasional Inheren: Sangat Rendah',
            '2' => 'Tingkat Risiko Operasional Inheren: Rendah',
            '3' => 'Tingkat Risiko Operasional Inheren: Sedang',
            '4' => 'Tingkat Risiko Operasional Inheren: Tinggi',
            '5' => 'Tingkat Risiko Operasional Inheren: Sangat Tinggi'
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
                    'keterangan' => 'Tingkat Risiko Operasional Inheren Posisi Sebelumnya',
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
                    return redirect()->to(base_url('Risikooperasional'))
                        ->with('message', 'Data Tingkat Risiko Operasional Inheren berhasil diupdate');
                } else {
                    throw new \Exception('Gagal mengupdate data');
                }

            } else {
                $insertData = [
                    'faktor1id' => $faktorId,
                    'penilaiankredit' => $penilaiankredit,
                    'penjelasanpenilaian' => $penjelasanpenilaian,
                    'keterangan' => 'Tingkat Risiko Operasional Inheren Posisi Sebelumnya',
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
                    return redirect()->to(base_url('Risikooperasional'))
                        ->with('message', 'Data Tingkat Risiko Operasional Inheren berhasil disimpan');
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
        $faktorId = 48;
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
            ->where('faktor1id', 48)
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

        $faktorId = 48;
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
                    return redirect()->to(base_url('Risikooperasional'))
                        ->with('message', 'Kesimpulan berhasil diupdate');
                } else {
                    throw new \Exception('Gagal mengupdate kesimpulan');
                }

            } else {
                $rataRata = $this->nilaiModel->hitungRataRata($faktorId, $this->userKodebpr, $this->periodeId);
                $rataRata = ($rataRata - floor($rataRata) >= 0.5) ? ceil($rataRata) : floor($rataRata);

                $penjelasanMapping = [
                    '1' => 'Tingkat Risiko Operasional Inheren: Sangat Rendah',
                    '2' => 'Tingkat Risiko Operasional Inheren: Rendah',
                    '3' => 'Tingkat Risiko Operasional Inheren: Sedang',
                    '4' => 'Tingkat Risiko Operasional Inheren: Tinggi',
                    '5' => 'Tingkat Risiko Operasional Inheren: Sangat Tinggi'
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
                    return redirect()->to(base_url('Risikooperasional'))
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
            ->where('faktor1id', 49)
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
                'id' => 36,
                'title' => 'Pilar Kompleksitas bisnis dan kelembagaan',
                'type' => 'category',
                'faktor_id' => 36,
                'faktor_ids' => [37, 38, 39, 40],
                'description' => 'Kompleksitas bisnis dan kelembagaan',
                'children' => [
                    [
                        'id' => 37,
                        'title' => 'Skala usaha dan struktur organisasi',
                        'type' => 'parameter',
                        'faktor_id' => 37,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 38,
                        'title' => 'Jaringan kantor, Rentang kendali dan lokasi kantor cabang',
                        'type' => 'parameter',
                        'faktor_id' => 38,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 39,
                        'title' => 'Keberagaman produk dan/atau jasa',
                        'type' => 'parameter',
                        'faktor_id' => 39,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 40,
                        'title' => 'Tindakan korporasi',
                        'type' => 'parameter',
                        'faktor_id' => 40,
                        'previous_periode_faktor_id' => null
                    ]
                ]
            ],
            [
                'id' => 41,
                'title' => 'Pilar Sumber daya manusia (SDM)',
                'type' => 'category',
                'faktor_id' => 41,
                'faktor_ids' => [42, 43],
                'description' => 'Sumber daya manusia (SDM)',
                'children' => [
                    [
                        'id' => 42,
                        'title' => 'Kecukupan kuantitas dan kualitas SDM',
                        'type' => 'parameter',
                        'faktor_id' => 42,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 43,
                        'title' => 'Permasalahan operasional karena faktor manusia (human error)',
                        'type' => 'parameter',
                        'faktor_id' => 43,
                        'previous_periode_faktor_id' => null
                    ]
                ]
            ],
            [
                'id' => 44,
                'title' => 'Penyelenggaraan teknologi informasi (TI)',
                'type' => 'single',
                'faktor_id' => 44,
                'description' => 'Penyelenggaraan teknologi informasi (TI)'
            ],
            [
                'id' => 45,
                'title' => 'Pilar penyimpangan (Fraud)',
                'type' => 'single',
                'faktor_id' => 45,
                'description' => 'Pilar penyimpangan (Fraud)'
            ],
            [
                'id' => 46,
                'title' => 'Faktor eksternal',
                'type' => 'single',
                'faktor_id' => 46,
                'description' => 'Faktor eksternal'
            ],
            [
                'id' => 47,
                'title' => 'Lainnya',
                'type' => 'single',
                'faktor_id' => 47,
                'description' => 'Lainnya'
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
            36 => [37, 38, 39, 40],
            41 => [42, 43, 26]
        ];

        foreach ($categoryMapping as $categoryId => $childrenIds) {
            if (in_array($changedFaktorId, $childrenIds)) {
                $this->calculateAndSaveCategoryAverage($categoryId, $childrenIds, $kodebpr, $periodeId);
                break;
            }
        }
    }

    public function exporttxtrisikooperasional()
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

        $data_risikooperasional = $this->nilaiModel
            ->getDataByKodebprAndPeriode($kodebpr, $periodeId);

        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);
        $sandibpr = '';
        $kodejenis = '';

        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'] ?? '';
            $kodejenis = $infobpr['kodejenis'] ?? '';
        }

        function sanitizeTxtOpsInheren($text)
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

        $indexedData = [];
        foreach ($data_risikooperasional as $row) {
            if (isset($row['faktor1id'])) {
                $indexedData[$row['faktor1id']] = $row;
            }
        }

        $output = '';

        // HEADER
        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|PRBPRKS|0201|{$modalinti}|{$totalaset}|{$kantorcabang}|{$atmdebit}|{$kategori}||\r\n";

        foreach ($kodeMap as $faktorId => $kode) {

            if (isset($indexedData[$faktorId])) {
                $row = $indexedData[$faktorId];
                $penilaiankredit = sanitizeTxtOpsInheren($row['penilaiankredit'] ?? '');
                $keterangan = sanitizeTxtOpsInheren($row['keterangan'] ?? '');
            } else {
                $penilaiankredit = '';
                $keterangan = '';
            }

            $output .= "D01|{$kode}|{$penilaiankredit}|{$keterangan}\r\n";
        }

        $filename = "PRBPRKS-0201-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        $response = service('response');
        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader(
            'Content-Disposition',
            'attachment; filename="' . $filename . '"'
        );

        return $response->setBody($output);
    }

    public function exportPDFGabunganOperasional()
    {
        if (!$this->auth->check()) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Unauthorized'
            ]);
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        if (!$kodebpr || !$periodeId) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Data tidak lengkap'
            ]);
        }

        try {
            // Get periode and BPR data
            $periodeDetail = $this->periodeModel->find($periodeId);
            $bprData = $this->infobprModel->where('kodebpr', $kodebpr)->first();

            // Get Risiko Operasional Inheren data (faktor 36-49)
            $dataInheren = $this->db->table('risikooperasional')
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->whereIn('faktor1id', range(36, 49))
                ->orderBy('faktor1id', 'ASC')
                ->get()
                ->getResultArray();

            // Get Risiko Operasional KPMR data (faktor 51-71)
            $dataKPMR = $this->db->table('risikooperasional_kpmr')
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->whereIn('faktor1id', range(51, 71))
                ->orderBy('faktor1id', 'ASC')
                ->get()
                ->getResultArray();

            // Build hierarchical structure for Inheren
            $inheren = $this->buildOperasionalInherenStructure($dataInheren);

            // Build hierarchical structure for KPMR
            $kpmr = $this->buildOperasionalKPMRStructure($dataKPMR);

            // Prepare response data
            $responseData = [
                'status' => 'success',
                'data' => [
                    'periode' => [
                        'semester' => $periodeDetail['semester'] ?? '',
                        'tahun' => $periodeDetail['tahun'] ?? ''
                    ],
                    'bpr' => [
                        'namabpr' => $bprData['namabpr'] ?? '',
                        'kodebpr' => $kodebpr
                    ],
                    'inheren' => $inheren,
                    'kpmr' => $kpmr
                ]
            ];

            return $this->response->setJSON($responseData);

        } catch (\Exception $e) {
            log_message('error', 'Error exportPDFGabunganOperasional: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ]);
        }
    }

    private function buildOperasionalInherenStructure($dataArray)
    {
        $lookup = [];
        foreach ($dataArray as $item) {
            $lookup[$item['faktor1id']] = $item;
        }

        $structure = [
            'nilai' => [
                'kompleksitas' => [
                    'kategori' => $lookup[36] ?? null,
                    'children' => array_filter([
                        $lookup[37] ?? null,
                        $lookup[38] ?? null,
                        $lookup[39] ?? null,
                        $lookup[40] ?? null
                    ])
                ],
                'sdm' => [
                    'kategori' => $lookup[41] ?? null,
                    'children' => array_filter([
                        $lookup[42] ?? null,
                        $lookup[43] ?? null
                    ])
                ],
                'ti' => $lookup[44] ?? null,
                'fraud' => $lookup[45] ?? null,
                'eksternal' => $lookup[46] ?? null,
                'lainnya' => $lookup[47] ?? null
            ],
            'nilai13' => $lookup[48] ?? null,  // Penilaian Risiko
            'nilai14' => $lookup[49] ?? null   // Penilaian Risiko Periode Sebelumnya
        ];

        return $structure;
    }

    private function buildOperasionalKPMRStructure($dataArray)
    {
        $lookup = [];
        foreach ($dataArray as $item) {
            $lookup[$item['faktor1id']] = $item;
        }

        $structure = [
            'nilai' => [
                'pengawasan' => [
                    'kategori' => $lookup[51] ?? null,
                    'children' => array_filter([
                        $lookup[52] ?? null,
                        $lookup[53] ?? null,
                        $lookup[54] ?? null,
                        $lookup[55] ?? null,
                        $lookup[56] ?? null,
                        $lookup[57] ?? null
                    ])
                ],
                'kebijakan' => [
                    'kategori' => $lookup[58] ?? null,
                    'children' => array_filter([
                        $lookup[59] ?? null,
                        $lookup[60] ?? null,
                        $lookup[61] ?? null
                    ])
                ],
                'proses' => [
                    'kategori' => $lookup[62] ?? null,
                    'children' => array_filter([
                        $lookup[63] ?? null,
                        $lookup[64] ?? null,
                        $lookup[65] ?? null,
                        $lookup[66] ?? null
                    ])
                ],
                'pengendalian' => [
                    'kategori' => $lookup[67] ?? null,
                    'children' => array_filter([
                        $lookup[68] ?? null,
                        $lookup[69] ?? null
                    ])
                ]
            ],
            'nilai33' => $lookup[70] ?? null,  // Penilaian Risiko KPMR
            'nilai34' => $lookup[71] ?? null   // Penilaian Risiko KPMR Periode Sebelumnya
        ];

        return $structure;
    }
}