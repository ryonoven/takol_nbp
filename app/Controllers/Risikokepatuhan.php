<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_paramprofilrisiko;
use App\Models\M_user;
use App\Models\M_profilrisikocomments;
use App\Models\M_risikokepatuhan;
use App\Models\M_infobpr;
// use App\Models\M_periodeprofilresiko;
use App\Models\M_periodesemesteran;
use App\Models\M_profilrisikocommentsread;
use App\Models\M_showprofilresiko;
use App\Models\M_showtks;
use App\Models\M_periodetks;
use Myth\Auth\Config\Services as AuthServices;

class Risikokepatuhan extends Controller
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

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');

        $this->db = \Config\Database::connect();

        $this->paramprofilrisikoModel = new M_paramprofilrisiko();
        $this->periodeModel = new M_periodesemesteran();
        $this->userModel = new M_user();
        $this->komentarModel = new M_profilrisikocomments();
        $this->nilaiModel = new M_risikokepatuhan();
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
            'faktor1id' => 81,
            'kodebpr' => $this->userKodebpr,
            'periode_id' => $this->periodeId
        ])->first();

        $nilai14 = $this->nilaiModel->where([
            'faktor1id' => 82,
            'kodebpr' => $this->userKodebpr,
            'periode_id' => $this->periodeId
        ])->first();

        $requiredFaktor = array_merge(range(73, 79), [81]);
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

        $showtks = $this->showtksModel->getByKodebprAndPeriode($this->userKodebpr, $this->periodeId);

        $user = $this->userModel->find($this->userId);

        $data = [
            'judul' => 'Penilaian Risiko Kepatuhan Inheren',
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
            . view('Laporansemester/Profilrisiko/kepatuhaninheren', $data)
            . view('templates/v_footer');
    }

    private function checkCanApprove($nilaiLookup)
    {
        $requiredFaktorIds = range(73, 80);

        foreach ($requiredFaktorIds as $faktorId) {
            if (empty($nilaiLookup[$faktorId])) {
                return false;
            }
        }

        return true;
    }

    private function checkAccdekomApproved($nilaiLookup)
    {
        for ($faktorId = 73; $faktorId <= 80; $faktorId++) {
            if (!isset($nilaiLookup[$faktorId]) || $nilaiLookup[$faktorId]['is_approved'] != 1) {
                return false;
            }
        }
        return true;
    }

    private function checkAllApproved($nilaiLookup)
    {
        $requiredFaktorIds = range(73, 81);
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

        $nilaiData = $this->db->table('risikokepatuhan as r')
            ->select('r.*, u.fullname')
            ->join('users as u', 'u.id = r.user_id', 'left')
            ->where('r.kodebpr', $this->userKodebpr)
            ->where('r.periode_id', $this->periodeId)
            ->whereIn('r.faktor1id', range(73, 80))
            ->get()
            ->getResultArray();

        $nilaiLookup = [];
        foreach ($nilaiData as $nilai) {
            $nilaiLookup[$nilai['faktor1id']] = $nilai;
        }

        $structure = [
            [
                'id' => 73,
                'title' => 'Pilar pelanggaran terhadap ketentuan peraturan perundang-undangan dan ketentuan lain',
                'type' => 'category',
                'faktor_id' => 73,
                'children' => [
                    ['id' => 74, 'title' => 'Jenis, signifikansi, dan frekuensi pelanggaran yang dilakukan', 'faktor_id' => 74],
                    ['id' => 75, 'title' => 'Signifikansi tindak lanjut atas temuan pelanggaran', 'faktor_id' => 75]
                ]
            ],
            [
                'id' => 76,
                'title' => 'Pilar Faktor kelemahan aspek hukum',
                'type' => 'category',
                'faktor_id' => 76,
                'children' => [
                    ['id' => 77, 'title' => 'Kelemahan dalam perikatan', 'faktor_id' => 77],
                    ['id' => 78, 'title' => 'Litigasi terkait nominal gugatan atau estimasi kerugian yang dialami BPR akibat gugatan', 'faktor_id' => 78],
                    ['id' => 79, 'title' => 'Litigasi terkait kerugian yang dialami karena putusan pengadilan berkekuatan hukum tetap', 'faktor_id' => 79]
                ]
            ],
            [
                'id' => 80,
                'title' => 'Lainnya',
                'type' => 'single',
                'faktor_id' => 80
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
            73 => [
                'descriptions' => [
                    1 => 'Parameter Pilar pelanggaran terhadap ketentuan peraturan perundang-undangan dan ketentuan lain berada pada tingkat risiko yang Sangat Rendah',
                    2 => 'Parameter Pilar pelanggaran terhadap ketentuan peraturan perundang-undangan dan ketentuan lain berada pada tingkat risiko yang Rendah',
                    3 => 'Parameter Pilar pelanggaran terhadap ketentuan peraturan perundang-undangan dan ketentuan lain berada pada tingkat risiko yang Sedang',
                    4 => 'Parameter Pilar pelanggaran terhadap ketentuan peraturan perundang-undangan dan ketentuan lain berada pada tingkat risiko yang Tinggi',
                    5 => 'Parameter Pilar pelanggaran terhadap ketentuan peraturan perundang-undangan dan ketentuan lain berada pada tingkat risiko yang Sangat Tinggi'
                ],
                'catatan' => 'Peniliaian Parameter Risiko Kepatuhan Inheren'
            ],

            74 => [
                'descriptions' => [
                    1 => 'Tidak terdapat pelanggaran terhadap ketentuan peraturan perundangundangan dan/atau ketentuan lain selama periode penilaian.',
                    2 => '• Terdapat pelanggaran terhadap ketentuan peraturan perundangundangan dengan jenis sanksi ringan; dan <br>
                    • Frekuensi pelanggaran rendah.',
                    3 => '• Terdapat pelanggaran terhadap ketentuan peraturan perundangundangan dengan jenis sanksi ringan; dan <br>
                    • Frekuensi pelanggaran sedang.',
                    4 => '• Terdapat pelanggaran terhadap ketentuan peraturan perundangundangan dengan jenis sanksi ringan dengan frekuensi pelanggaran tinggi; dan/atau <br>
                    • Terdapat dugaan pelanggaran terhadap UndangUndang Perbankan antara lain pasal 49 yang dilakukan oleh pejabat atau pegawai BPR.',
                    5 => '• Terdapat pelanggaran dengan sanksi berat dan jenis pelanggaran signifikan; dan/atau <br>
                    • Terdapat dugaan pelanggaran terhadap UndangUndang Perbankan antara lain pasal 49 yang dilakukan oleh anggota Direksi dan/atau Dewan Komisaris BPR.'
                ],
                'catatan' => 'Peniliaian Parameter Risiko Kepatuhan Inheren'
            ],

            75 => [
                'descriptions' => [
                    1 => 'Tidak terdapat pelanggaran berulang, dan pelanggaran di periode sebelumnya sudah selesai ditindaklanjuti.',
                    2 => 'Terdapat pelanggaran berulang pada dua periode sebelumnya dengan jenis yang sama namun terdapat penurunan frekuensi yang tinggi dibandingkan periode sebelumnya.',
                    3 => 'Terdapat pelanggaran berulang pada dua periode sebelumnya dengan jenis yang sama namun terdapat penurunan frekuensi yang sedang dibandingkan periode sebelumnya.',
                    4 => 'Terdapat pelanggaran berulang pada dua periode • sebelumnya dengan jenis yang sama namun terdapat penurunan frekuensi yang rendah dibandingkan periode sebelumnya.',
                    5 => 'Terdapat pelanggaran berulang pada dua periode sebelumnya dengan jenis yang sama dengan frekuensi lebih banyak dari periode sebelumnya; dan/atau Terdapat pelanggaran berulang yang merupakan pelanggaran yang bersifat signifikan antara lain terhadap ketentuan BMPK, jumlah minimal pengurus, dan permodalan.'
                ],
                'catatan' => 'Peniliaian Parameter Risiko Kepatuhan Inheren'
            ],

            76 => [
                'descriptions' => [
                    1 => 'Parameter Faktor kelemahan aspek hukum berada pada tingkat risiko yang Sangat Rendah',
                    2 => 'Parameter Faktor kelemahan aspek hukum berada pada tingkat risiko yang Rendah',
                    3 => 'Parameter Faktor kelemahan aspek hukum berada pada tingkat risiko yang Sedang',
                    4 => 'Parameter Faktor kelemahan aspek hukum berada pada tingkat risiko yang Tinggi',
                    5 => 'Parameter Faktor kelemahan aspek hukum berada pada tingkat risiko yang Sangat Tinggi'
                ],
                'catatan' => 'Peniliaian Parameter Risiko Kepatuhan Inheren'
            ],

            77 => [
                'descriptions' => [
                    1 => '• Terdapat perjanjian kredit atau kerja sama lain yang memenuhi syarat sah perjanjian; dan <br>
                    • Tidak terdapat kelemahan dalam klausula perjanjian.',
                    2 => '• Terdapat perjanjian kredit atau kerja sama lain yang memenuhi syarat sah perjanjian; dan/atau <br>
                    • Terdapat kelemahan dalam klausula perjanjian dan menyebabkan tidak dapat dilaksanakann ya klausula dalam perjanjian namun tidak berpotensi menimbulkan gugatan hukum dan kerugian yang material.',
                    3 => '• Terdapat perjanjian kredit atau kerja sama lain yang memenuhi syarat sah perjanjian; dan/atau <br>
                    • Terdapat kelemahan dalam klausula perjanjian yang berpotensi menimbulkan gugatan hukum dan/atau kerugian yang material.',
                    4 => '• Terdapat perjanjian kredit atau kerja sama lain yang tidak memenuhi syarat sah 1. perjanjian; dan/atau <br>
                    • Terdapat kelemahan dalam klausula perjanjian yang berpotensi menimbulkan gugatan hukum dan/atau kerugian yang sangat material.',
                    5 => 'Terdapat pemberian kredit atau kerja sama lain yang tidak didukung dengan perjanjian tertulis.'
                ],
                'catatan' => 'Peniliaian Parameter Risiko Kepatuhan Inheren'
            ],

            78 => [
                'descriptions' => [
                    1 => 'Tidak terdapat gugatan atau tidak terdapat estimasi kerugian yang dialami BPR akibat gugatan.',
                    2 => 'Terdapat nominal gugatan atau estimasi kerugian akibat gugatan dengan nilai tidak signifikan dibanding modal BPR.',
                    3 => 'Terdapat nominal gugatan atau estimasi kerugian akibat gugatan dengan nilai kurang signifikan dibanding modal BPR.',
                    4 => 'Terdapat nominal gugatan atau estimasi kerugian akibat gugatan dengan nilai cukup signifikan dibanding modal BPR.',
                    5 => 'Terdapat nominal gugatan atau estimasi kerugian akibat gugatan yang menyebabkan permodalan menurun  di bawah ketentuan KPMM'
                ],
                'catatan' => 'Peniliaian Parameter Risiko Kepatuhan Inheren'
            ],

            79 => [
                'descriptions' => [
                    1 => 'Tidak terdapat kerugian yang dialami BPR karena putusan pengadilan berkekuatan hukum tetap selama periode penilaian.',
                    2 => "Terdapat kerugian karena putusan pengadilan berkekuatan hukum tetap, dengan nilai tidak signifikan dibanding modal BPR selama periode penilaian.",
                    3 => 'Terdapat kerugian karena putusan pengadilan berkekuatan hukum tetap, dengan nilai kurang signifikan dibanding modal BPR selama periode penilaian.',
                    4 => 'Terdapat kerugian karena putusan pengadilan berkekuatan hukum tetap, dengan nilai cukup signifikan dibanding modal BPR selama periode penilaian.',
                    5 => 'Terdapat kerugian karena putusan pengadilan berkekuatan hukum tetap selama periode penilaian yang menyebabkan permodalan menurun  di bawah ketentuan KPMM.'
                ],
                'catatan' => 'Peniliaian Parameter Risiko Kepatuhan Inheren'
            ],

            80 => [
                'descriptions' => [
                    1 => 'Peringkat Risiko Kepatuhan Inheren Lainnya berada pada tingkat yang Sangat Rendah',
                    2 => 'Peringkat Risiko Kepatuhan Inheren Lainnya berada pada tingkat yang Rendah',
                    3 => 'Peringkat Risiko Kepatuhan Inheren Lainnya berada pada tingkat yang Sedang',
                    4 => 'Peringkat Risiko Kepatuhan Inheren Lainnya berada pada tingkat yang Tinggi',
                    5 => 'Peringkat Risiko Kepatuhan Inheren Lainnya berada pada tingkat yang Sangat Tinggi'
                ],
                'catatan' => 'Peniliaian Parameter Risiko Kepatuhan Inheren'
            ],
        ];
    }

    public function cekKomentarBaru()
    {
        $subkategori = 'KEPATUHANINHEREN';
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
            'KEPATUHANINHEREN',
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
            $this->db->table('risikokepatuhan')
                ->where('faktor1id', $faktor1id)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update($data);

            $categoryMapping = [
                73 => [74, 75],
                76 => [77, 78, 79]
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

            if (in_array($faktor1id, [73, 74, 75, 76, 77, 78, 79, 80])) {
                $rataRata = $this->nilaiModel->hitungRataRata(81, $this->userKodebpr, $this->periodeId);
                $this->nilaiModel->insertOrUpdateRataRata($rataRata, 81, $this->userKodebpr, $this->periodeId);
            }

            $this->db->table('risikokepatuhan')
                ->where('faktor1id', 81)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update(['accdir2' => 0, 'is_approved' => 0]);

            $nilai13 = $this->nilaiModel->where([
                'faktor1id' => 81,
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
            $this->db->table('risikokepatuhan_kpmr')
                ->where('faktor1id', $faktor1id)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update($data);

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Keterangan berhasil diubah'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error ubah Keterangan Saja: ' . $e->getMessage());
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
            $result = $this->db->table('risikokepatuhan')
                ->where('faktor1id', $faktor1id)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update($data);

            if ($result) {
                $this->db->table('risikokepatuhan')
                    ->where('faktor1id', 81)
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

    public function ubahPeringkat13Manual()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid request']);
        }

        $penilaiankredit = $this->request->getPost('penilaiankredit');

        if (!$penilaiankredit || !in_array($penilaiankredit, ['1', '2', '3', '4', '5'])) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Pilih peringkat yang valid']);
        }

        $penjelasanMapping = [
            '1' => 'Tingkat Risiko Kepatuhan Inheren: Sangat Rendah',
            '2' => 'Tingkat Risiko Kepatuhan Inheren: Rendah',
            '3' => 'Tingkat Risiko Kepatuhan Inheren: Sedang',
            '4' => 'Tingkat Risiko Kepatuhan Inheren: Tinggi',
            '5' => 'Tingkat Risiko Kepatuhan Inheren: Sangat Tinggi'
        ];

        try {
            $this->nilaiModel
                ->where('faktor1id', 81)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->set([
                    'penilaiankredit' => $penilaiankredit,
                    'penjelasanpenilaian' => $penjelasanMapping[$penilaiankredit],
                    'is_manual' => 1,
                    'updated_at' => date('Y-m-d H:i:s')
                ])
                ->update();

            return $this->response->setJSON([
                'status' => 'success',
                'peringkat' => $penilaiankredit,
                'is_manual' => 1
            ]);
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
            $rataRata = $this->nilaiModel->hitungRataRata(81, $this->userKodebpr, $this->periodeId);
            $rataRata = ($rataRata - floor($rataRata) >= 0.5) ? ceil($rataRata) : floor($rataRata);

            $penjelasanMapping = [
                '1' => 'Tingkat Risiko Kepatuhan Inheren: Sangat Rendah',
                '2' => 'Tingkat Risiko Kepatuhan Inheren: Rendah',
                '3' => 'Tingkat Risiko Kepatuhan Inheren: Sedang',
                '4' => 'Tingkat Risiko Kepatuhan Inheren: Tinggi',
                '5' => 'Tingkat Risiko Kepatuhan Inheren: Sangat Tinggi'
            ];

            $this->nilaiModel
                ->where('faktor1id', 81)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->set([
                    'penilaiankredit' => $rataRata,
                    'penjelasanpenilaian' => $penjelasanMapping[$rataRata],
                    'is_manual' => 0,
                    'updated_at' => date('Y-m-d H:i:s')
                ])
                ->update();

            return $this->response->setJSON([
                'status' => 'success',
                'peringkat' => $rataRata,
                'is_manual' => false
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
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
                'message' => 'Faktor ID tidak ditemukan'
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
            $this->db->table('risikokepatuhan')->insert($data);

            $categoryMapping = [
                73 => [74, 75],
                76 => [77, 78, 79]
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

            if (in_array($faktorId, [73, 74, 75, 76, 77, 78, 79, 80])) {
                $rataRata = $this->nilaiModel->hitungRataRata(81, $this->userKodebpr, $this->periodeId);
                $this->nilaiModel->insertOrUpdateRataRata($rataRata, 81, $this->userKodebpr, $this->periodeId);
            }

            $nilai13 = $this->nilaiModel->where([
                'faktor1id' => 81,
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
            return redirect()->to(base_url('Risikokepatuhan'));
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
                    'message' => 'Faktor ID tidak ditemukan'
                ]);
            }
            return redirect()->back()->with('err', 'Faktor ID tidak ditemukan');
        }

        try {
            $data = [
                'subkategori' => "KEPATUHANINHEREN",
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
                        'KEPATUHANINHEREN',
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

                return redirect()->to(base_url('Risikokepatuhan') . '?modal_komentar=' . $faktorId)
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

        return redirect()->to(base_url('Risikokepatuhan'));
    }

    public function approve($idNilai = null)
    {
        if ($idNilai === null) {
            return redirect()->back()->with('err', 'ID tidak valid');
        }

        $this->db->table('risikokepatuhan')
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
            return redirect()->back()->with('err', 'ID Faktor tidak valid.');
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
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Kode BPR atau Periode ID tidak valid'
                ]);
            }
            return redirect()->back()->with('err', 'Kode BPR atau Periode ID tidak valid');
        }

        // Faktor wajib: 73–79 + 81 (80 = Lainnya, opsional)
        $requiredFaktor = array_merge(range(73, 79), [81]);
        $totalRequired = count($requiredFaktor); // 8

        $checkData = $this->nilaiModel
            ->select('COUNT(CASE WHEN penilaiankredit IS NOT NULL THEN 1 END) AS filled_count')
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->whereIn('faktor1id', $requiredFaktor)
            ->first();

        if ((int) $checkData['filled_count'] < $totalRequired) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Semua faktor wajib harus diisi terlebih dahulu'
                ]);
            }
            return redirect()->back()->with('err', 'Semua faktor wajib harus diisi terlebih dahulu');
        }

        try {
            $this->db->table('risikokepatuhan')
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->whereIn('faktor1id', $requiredFaktor)
                ->update([
                    'is_approved' => 1,
                    'approved_by' => $this->userId,
                    'approved_at' => date('Y-m-d H:i:s')
                ]);

            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Semua data berhasil disetujui.'
                ]);
            }
            return redirect()->back()->with('message', 'Semua data berhasil disetujui.');

        } catch (\Exception $e) {
            log_message('error', 'Error in approveSemua (Kepatuhan): ' . $e->getMessage());

            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
                ]);
            }
            return redirect()->back()->with('err', 'Terjadi kesalahan sistem');
        }
    }

    public function unapproveSemua()
    {
        if (!$this->userKodebpr || !$this->periodeId) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Kode BPR atau Periode ID tidak valid'
                ]);
            }
            return redirect()->back()->with('err', 'Kode BPR atau Periode ID tidak valid');
        }

        try {
            $this->db->table('risikokepatuhan')
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->whereIn('faktor1id', range(73, 81))
                ->update([
                    'is_approved' => 0,
                    'approved_by' => $this->userId,
                    'approved_at' => date('Y-m-d H:i:s')
                ]);

            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Semua data berhasil dibatalkan persetujuannya.'
                ]);
            }
            return redirect()->back()->with('message', 'Semua data berhasil dibatalkan persetujuannya.');

        } catch (\Exception $e) {
            log_message('error', 'Error in unapproveSemua (Kepatuhan): ' . $e->getMessage());

            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
                ]);
            }
            return redirect()->back()->with('err', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    public function accdir2()
    {
        $faktor1id = $this->request->getPost('faktor1id');

        if (!$faktor1id) {
            return redirect()->back()->with('error', 'Data tidak lengkap');
        }

        $this->db->table('risikokepatuhan')
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
            return redirect()->to('/Risikokepatuhan')->with('error', 'Data tidak lengkap');
        }

        $nilaiFaktor = $this->nilaiModel
            ->where('faktor1id', $faktor1id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if (!$nilaiFaktor) {
            return redirect()->to('/Risikokepatuhan')->with('error', 'Data tidak ditemukan');
        }

        $dataUpdate = [
            'accdir2' => 0,
            'accdir2_by' => $this->userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->nilaiModel->update($nilaiFaktor['id'], $dataUpdate)) {
            return redirect()->to('/Risikokepatuhan')->with('message', 'Persetujuan dibatalkan');
        }

        return redirect()->to('/Risikokepatuhan')->with('error', 'Gagal memperbarui data');
    }

    public function recalculateCategoryAverage()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $faktorId = $this->request->getPost('faktor_id');

        $categoryMapping = [
            73 => [74, 75],
            76 => [77, 78, 79]
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

            $rataRata = $this->nilaiModel->hitungRataRata(81, $this->userKodebpr, $this->periodeId);
            $this->nilaiModel->insertOrUpdateRataRata($rataRata, 81, $this->userKodebpr, $this->periodeId);

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
            "KEPATUHANINHEREN",
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
            "KEPATUHANINHEREN",
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

        $faktorIds = range(73, 81);
        $counts = [];

        foreach ($faktorIds as $faktorId) {
            $count = $this->commentReadsModel->countUnreadCommentsForUserByFactor(
                $faktorId,
                "KEPATUHANINHEREN",
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

        $faktorId = 82;
        $penilaiankredit = $this->request->getPost('penilaiankredit');

        $user = $this->userModel->find($this->userId);

        $penjelasanMapping = [
            '1' => 'Tingkat Risiko Kredit Inheren: Sangat Rendah',
            '2' => 'Tingkat Risiko Kredit Inheren: Rendah',
            '3' => 'Tingkat Risiko Kredit Inheren: Sedang',
            '4' => 'Tingkat Risiko Kredit Inheren: Kurang Rendah',
            '5' => 'Tingkat Risiko Kredit Inheren: Buruk'
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
                    'keterangan' => 'Tingkat Risiko Kepatuhan Inheren Posisi Sebelumnya',
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
                    return redirect()->to(base_url('Risikokepatuhan'))
                        ->with('message', 'Data Tingkat Risiko Kredit Inheren berhasil diupdate');
                } else {
                    throw new \Exception('Gagal mengupdate data');
                }

            } else {
                $insertData = [
                    'faktor1id' => $faktorId,
                    'penilaiankredit' => $penilaiankredit,
                    'penjelasanpenilaian' => $penjelasanpenilaian,
                    'keterangan' => 'Tingkat Risiko Kepatuhan Inheren Posisi Sebelumnya',
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
                    return redirect()->to(base_url('Risikokepatuhan'))
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
        $faktorId = 81;
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
            ->where('faktor1id', 81)
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

        $faktorId = 81;
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
                    return redirect()->to(base_url('Risikokepatuhan'))
                        ->with('message', 'Kesimpulan berhasil diupdate');
                } else {
                    throw new \Exception('Gagal mengupdate kesimpulan');
                }

            } else {
                $rataRata = $this->nilaiModel->hitungRataRata($faktorId, $this->userKodebpr, $this->periodeId);
                $rataRata = ($rataRata - floor($rataRata) >= 0.5) ? ceil($rataRata) : floor($rataRata);

                $penjelasanMapping = [
                    '1' => 'Tingkat Risiko Kepatuhan Inheren: Sangat Rendah',
                    '2' => 'Tingkat Risiko Kepatuhan Inheren: Rendah',
                    '3' => 'Tingkat Risiko Kepatuhan Inheren: Sedang',
                    '4' => 'Tingkat Risiko Kepatuhan Inheren: Tinggi',
                    '5' => 'Tingkat Risiko Kepatuhan Inheren: Sangat Tinggi'
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
                    return redirect()->to(base_url('Risikokepatuhan'))
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
            ->where('faktor1id', 82)
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
                'id' => 73,
                'title' => 'Pilar pelanggaran terhadap ketentuan peraturan perundang-undangan dan ketentuan lain',
                'type' => 'category',
                'faktor_id' => 73,
                'faktor_ids' => [74, 75],
                'description' => 'Pilar pelanggaran terhadap ketentuan peraturan perundang-undangan dan ketentuan lain',
                'children' => [
                    [
                        'id' => 74,
                        'title' => 'Jenis, signifikansi, dan frekuensi pelanggaran yang dilakukan',
                        'type' => 'parameter',
                        'faktor_id' => 74,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 75,
                        'title' => 'Signifikansi tindak lanjut atas temuan pelanggaran',
                        'type' => 'parameter',
                        'faktor_id' => 75,
                        'previous_periode_faktor_id' => null
                    ]
                ]
            ],
            [
                'id' => 76,
                'title' => 'Faktor kelemahan aspek hukum',
                'type' => 'category',
                'faktor_id' => 76,
                'faktor_ids' => [77, 78, 79],
                'description' => 'Faktor kelemahan aspek hukum',
                'children' => [
                    [
                        'id' => 77,
                        'title' => 'Kelemahan dalam perikatan',
                        'type' => 'parameter',
                        'faktor_id' => 77,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 78,
                        'title' => 'Litigasi terkait nominal gugatan atau estimasi kerugian yang dialami BPR akibat gugatan',
                        'type' => 'parameter',
                        'faktor_id' => 78,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 79,
                        'title' => 'Litigasi terkait kerugian yang dialami karena putusan pengadilan berkekuatan hukum tetap',
                        'type' => 'parameter',
                        'faktor_id' => 79,
                        'previous_periode_faktor_id' => null
                    ]
                ]
            ],
            [
                'id' => 80,
                'title' => 'Lainnya',
                'type' => 'single',
                'faktor_id' => 80,
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
            73 => [74, 75],
            76 => [77, 78, 79]
        ];

        foreach ($categoryMapping as $categoryId => $childrenIds) {
            if (in_array($changedFaktorId, $childrenIds)) {
                $this->calculateAndSaveCategoryAverage($categoryId, $childrenIds, $kodebpr, $periodeId);
                break;
            }
        }
    }

    public function exporttxtrisikokepatuhan()
    {
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
        $nomorsurat = $periodeDetail['nomorsurat'];
        $jeniskoreksi = $periodeDetail['jeniskoreksi'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];
        $kategori = $periodeDetail['kategori'];

        $tahun = $periodeDetail['tahun'] ?? date('Y');
        $semester = $periodeDetail['semester'] ?? '';

        if (strtolower($semester) === 'ganjil' || $semester === '1') {
            $exportDate = "{$tahun}-06-30";
        } else {
            $exportDate = "{$tahun}-12-31";
        }

        $titleDate = str_replace('-', '', $exportDate);

        // Fetch data
        $data_risikokepatuhan = $this->nilaiModel
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
        function sanitizeTxtKepInheren($text)
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
            73 => '3210',
            74 => '3211',
            75 => '3212',
            76 => '3220',
            77 => '3221',
            78 => '3222',
            79 => '3223',
            80 => '3299', // wajib muncul walau kosong
            81 => '3292',
        ];

        // Index data by faktor1id
        $indexedData = [];
        foreach ($data_risikokepatuhan as $row) {
            if (isset($row['faktor1id'])) {
                $indexedData[$row['faktor1id']] = $row;
            }
        }

        $output = '';

        // HEADER
        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0301|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";

        // DETAIL — loop berdasarkan kodeMap
        foreach ($kodeMap as $faktorId => $kode) {

            if (isset($indexedData[$faktorId])) {
                $row = $indexedData[$faktorId];
                $penilaiankredit = sanitizeTxtKepInheren($row['penilaiankredit'] ?? '');
                $keterangan = sanitizeTxtKepInheren($row['keterangan'] ?? '-');
            } else {
                // data tidak diisi user
                $penilaiankredit = '';
                $keterangan = '';
            }

            $output .= "D01|{$kode}|{$penilaiankredit}|{$keterangan}\r\n";
        }

        // Filename
        $filename = "TKSBPRKS-0301-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        // Response
        $response = service('response');
        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader(
            'Content-Disposition',
            'attachment; filename="' . $filename . '"'
        );

        return $response->setBody($output);
    }

    public function exporttxtrisikokepatuhantks()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');
        $showtks = $this->showtksModel->getByKodebprAndPeriode($this->userKodebpr, $this->periodeId);
        $periodeTKS = $this->periodetksModel->find($periodeId);

        if (!$periodeTKS) {
            return redirect()->back()->with('error', 'Periode TKS tidak ditemukan');
        }

        $periodeProfilRisikoId = $this->getMatchingProfilRisikoPeriodeId($periodeId);

        if (!$periodeProfilRisikoId) {
            return redirect()->back()->with('error', 'Tidak ada periode Profil Risiko yang cocok dengan semester dan tahun periode TKS');
        }

        $output = "";

        // Periode detail
        $modalinti = $periodeTKS['modalinti'];
        $jenispelaporan = $periodeTKS['jenispelaporan'];
        $kategori = $periodeTKS['kategori'];

        $tanggal = new \DateTime($showtks['tanggal']);
        $titleDate = $tanggal->format('Ymd');
        $exportDate = $tanggal->format('Y-m-d');

        $data_risikokepatuhan = $this->nilaiModel
            ->getDataByKodebprAndPeriode($kodebpr, $periodeProfilRisikoId);

        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'] ?? '';
            $kodejenis = $infobpr['kodejenis'] ?? '';
        }

        function sanitizeTxtKepInheren($text)
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
            73 => '3210',
            74 => '3211',
            75 => '3212',
            76 => '3220',
            77 => '3221',
            78 => '3222',
            79 => '3223',
            80 => '3299',
            81 => '3292',
        ];

        $indexedData = [];
        foreach ($data_risikokepatuhan as $row) {
            if (isset($row['faktor1id'])) {
                $indexedData[$row['faktor1id']] = $row;
            }
        }

        $output = '';
        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0301|{$modalinti}|{$kategori}||\r\n";

        foreach ($kodeMap as $faktorId => $kode) {

            if (isset($indexedData[$faktorId])) {
                $row = $indexedData[$faktorId];
                $penilaiankredit = sanitizeTxtKepInheren($row['penilaiankredit'] ?? '');
                $keterangan = sanitizeTxtKepInheren($row['keterangan'] ?? '-');
            } else {
                $penilaiankredit = '';
                $keterangan = '';
            }

            $output .= "D01|{$kode}|{$penilaiankredit}|{$keterangan}\r\n";
        }

        $filename = "TKSBPRKS-0301-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        $response = service('response');
        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader(
            'Content-Disposition',
            'attachment; filename="' . $filename . '"'
        );

        return $response->setBody($output);
    }

    private function getMatchingProfilRisikoPeriodeId($periodeTKSId)
    {
        $periodeTKS = $this->periodetksModel->find($periodeTKSId);

        if (!$periodeTKS) {
            return null;
        }

        $periodeProfilRisikoList = $this->periodeModel->where([
            'semester' => $periodeTKS['semester'],
            'tahun' => $periodeTKS['tahun'],
            'kodebpr' => $this->userKodebpr
        ])->findAll();

        if (empty($periodeProfilRisikoList)) {
            return null;
        }

        return $periodeProfilRisikoList[0]['id'];
    }

    private function formatDateForDisplay($date)
    {
        if (empty($date) || $date === '0000-00-00') {
            return '';
        }

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
            return $date;
        }

        $dateObj = \DateTime::createFromFormat('Y-m-d', $date);
        return $dateObj ? $dateObj->format('d/m/Y') : '';
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

        // COUNT data yang sudah ada di periode aktif
        $currentCount = (int) $this->db->table('risikokepatuhan')
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->whereIn('faktor1id', range(73, 80))
            ->where('penilaiankredit IS NOT NULL')
            ->countAllResults();

        // COUNT data di periode sebelumnya
        $prevCount = (int) $this->db->table('risikokepatuhan')
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $prevPeriode['id'])
            ->whereIn('faktor1id', range(73, 80))
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

        $syncFaktorIds = range(73, 80);

        $prevRows = $this->db->table('risikokepatuhan')
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

        $existingRows = $this->db->table('risikokepatuhan')
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
                $this->db->table('risikokepatuhan')->insertBatch($toInsert);
            }

            foreach ($toUpdate as $upd) {
                $this->db->table('risikokepatuhan')
                    ->where('id', $upd['id'])
                    ->update($upd['data']);
            }

            $categoryMapping = [
                73 => [74, 75],
                76 => [77, 78, 79],
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

            if (array_intersect($syncedFaktors, [73, 74, 75, 76, 77, 78, 79, 80])) {
                $rataRata = $this->nilaiModel->hitungRataRata(81, $this->userKodebpr, $this->periodeId);
                $this->nilaiModel->insertOrUpdateRataRata($rataRata, 81, $this->userKodebpr, $this->periodeId);
            }

            $this->db->table('risikokepatuhan')
                ->where('faktor1id', 81)
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
            if (!empty($skippedFaktors)) {
                $msg .= sprintf('. %d faktor dilewati (sudah terisi)', count($skippedFaktors));
            }

            $nilai81 = $this->nilaiModel->where([
                'faktor1id' => 81,
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId,
            ])->first();

            return $this->response->setJSON([
                'status' => 'success',
                'message' => $msg,
                'synced_count' => count($syncedFaktors),
                'skipped_count' => count($skippedFaktors),
                'peringkat13' => $nilai81['penilaiankredit'] ?? null,
                'prev_periode' => [
                    'semester' => $prevPeriode['semester'],
                    'tahun' => $prevPeriode['tahun'],
                ],
            ]);

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'syncFromPrevPeriode [Kepatuhan] error: ' . $e->getMessage());
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
}