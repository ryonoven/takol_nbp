<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_paramprofilrisiko;
use App\Models\M_user;
use App\Models\M_profilrisikocomments;
use App\Models\M_nilairisikokredit;
use App\Models\M_infobpr;
use App\Models\M_periodeprofilresiko;
use App\Models\M_profilrisikocommentsread;
use App\Models\M_kalkulatorkredit;
use App\Models\M_showprofilresiko;
use Myth\Auth\Config\Services as AuthServices;

class Risikokredit extends Controller
{
    protected $db;
    protected $auth;
    protected $paramprofilrisikoModel;
    protected $showprofilresikoModel;
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
    protected $userGroups = [];

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');

        $this->db = \Config\Database::connect();

        $this->paramprofilrisikoModel = new M_paramprofilrisiko();
        $this->periodeModel = new M_periodeprofilresiko();
        $this->userModel = new M_user();
        $this->komentarModel = new M_profilrisikocomments();
        $this->showprofilresikoModel = new M_showprofilresiko();
        $this->nilaiModel = new M_nilairisikokredit();
        $this->infobprModel = new M_infobpr();
        $this->commentReadsModel = new M_profilrisikocommentsread();
        $this->kalkulatorModel = new M_kalkulatorkredit();

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
            'faktor1id' => 13,
            'kodebpr' => $this->userKodebpr,
            'periode_id' => $this->periodeId
        ])->first();

        $nilai14 = $this->nilaiModel->where([
            'faktor1id' => 14,
            'kodebpr' => $this->userKodebpr,
            'periode_id' => $this->periodeId
        ])->first();

        $requiredFaktor = array_merge(range(1, 11), [13]);
        $requiredFaktor = array_diff($requiredFaktor, [12]);
        $totalRequired = count($requiredFaktor);

        $approvalData = $this->nilaiModel
            ->select('
        COUNT(CASE WHEN penilaiankredit IS NOT NULL THEN 1 END) AS filled_count,
        COUNT(CASE WHEN is_approved = 1 THEN 1 END) AS approved_count
    ')
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->whereIn('faktor1id', $requiredFaktor)
            ->first();

        $allFilled = ((int) $approvalData['filled_count'] === $totalRequired);
        $allApproved = (
            $allFilled &&
            (int) $approvalData['approved_count'] === $totalRequired
        );

        $canApprove = $allFilled;

        $kalkulatorData = $this->kalkulatorModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->first();

        if (!$kalkulatorData) {
            $kalkulatorData = [
                'aba' => '',
                'kydbank' => '',
                'kydpihak3' => '',
                'totalaset' => '',
                'total25debitur' => '',
                'perdagangan' => '',
                'jasa' => '',
                'konsumsirumah' => '',
                'kydgross' => 0,
                'asetproduktif' => 0,
                'rasioasetproduktif' => 0,
                'rasiokreditdiberikan' => 0,
                'rasio25debitur' => 0,
                'sektorekonomi' => 0,
                'rasioekonomi' => 0,
                'abanpl' => '',
                'kydnpl3' => '',
                'kydnpl4' => '',
                'kydnpl5' => '',
                'kreditdpk2' => '',
                'kreditbermasalah' => '',
                'kreditrestruktur1' => '',
                'kydkoleknpl' => 0,
                'asetproduktifbermasalah' => 0,
                'rasioasetproduktifbermasalah' => 0,
                'rasiokreditbermasalah' => 0,
                'rasiokreditkualitasrendah' => 0,
            ];
        }

        $user = $this->userModel->find($this->userId);

        $data = [
            'judul' => 'Penilaian Risiko Kredit Inheren',
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
            . view('risikokredit/index', $data)
            . view('templates/v_footer');
    }

    // private function checkCanApprove($nilaiLookup)
    // {
    //     $requiredFaktorIds = range(1, 12);

    //     foreach ($requiredFaktorIds as $faktorId) {
    //         if (empty($nilaiLookup[$faktorId])) {
    //             return false;
    //         }
    //     }

    //     return true;
    // }

    // private function checkAccdekomApproved($nilaiLookup)
    // {
    //     for ($faktorId = 1; $faktorId <= 13; $faktorId++) {
    //         if (!isset($nilaiLookup[$faktorId]) || $nilaiLookup[$faktorId]['is_approved'] != 1) {
    //             return false;
    //         }
    //     }
    //     return true;
    // }

    private function checkAllApproved($nilaiLookup)
    {
        $requiredFaktorIds = range(1, 13);
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

        $nilaiData = $this->db->table('risikokredit as r')
            ->select('r.*, u.fullname')
            ->join('users as u', 'u.id = r.user_id', 'left')
            ->where('r.kodebpr', $this->userKodebpr)
            ->where('r.periode_id', $this->periodeId)
            ->whereIn('r.faktor1id', range(1, 13))
            ->get()
            ->getResultArray();

        $nilaiLookup = [];
        foreach ($nilaiData as $nilai) {
            $nilaiLookup[$nilai['faktor1id']] = $nilai;
        }

        $structure = [
            [
                'id' => 1,
                'title' => 'Pilar Komposisi Portofolio Aset dan Tingkat Konsentrasi Kredit',
                'type' => 'category',
                'faktor_id' => 1,
                'children' => [
                    ['id' => 2, 'title' => 'Parameter rasio aset produktif terhadap total aset', 'faktor_id' => 2],
                    ['id' => 3, 'title' => 'Parameter rasio kredit yang diberikan terhadap total aset produktif', 'faktor_id' => 3],
                    ['id' => 4, 'title' => 'Parameter rasio 25 debitur terbesar terhadap total kredit yang diberikan', 'faktor_id' => 4],
                    ['id' => 5, 'title' => 'Parameter rasio kredit per sektor ekonomi terhadap total kredit yang diberikan', 'faktor_id' => 5]
                ]
            ],
            [
                'id' => 6,
                'title' => 'Pilar Kualitas Aset',
                'type' => 'category',
                'faktor_id' => 6,
                'children' => [
                    ['id' => 7, 'title' => 'Parameter rasio aset produktif bermasalah terhadap total aset produktif', 'faktor_id' => 7],
                    ['id' => 8, 'title' => 'Parameter kredit bermasalah neto terhadap total kredit yang diberikan', 'faktor_id' => 8],
                    ['id' => 9, 'title' => 'Parameter kredit kualitas rendah terhadap total kredit yang diberikan', 'faktor_id' => 9]
                ]
            ],
            [
                'id' => 10,
                'title' => 'Pilar Strategi Penyediaan Dana',
                'type' => 'single',
                'faktor_id' => 10
            ],
            [
                'id' => 11,
                'title' => 'Pilar Faktor Eksternal',
                'type' => 'single',
                'faktor_id' => 11
            ],
            [
                'id' => 12,
                'title' => 'Faktor Lainnya',
                'type' => 'single',
                'faktor_id' => 12
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

            // Pilar Komposisi Portofolio Aset dan Tingkat Konsentrasi Kredit 
            1 => [
                'descriptions' => [
                    1 => 'Parameter Komposisi Portofolio Aset dan Tingkat Konsentrasi Kredit berada pada tingkat risiko yang Sangat Rendah',
                    2 => 'Parameter Komposisi Portofolio Aset dan Tingkat Konsentrasi Kredit berada pada tingkat risiko yang Rendah',
                    3 => 'Parameter Komposisi Portofolio Aset dan Tingkat Konsentrasi Kredit berada pada tingkat risiko yang Sedang',
                    4 => 'Parameter Komposisi Portofolio Aset dan Tingkat Konsentrasi Kredit berada pada tingkat risiko yang Tinggi',
                    5 => 'Parameter Komposisi Portofolio Aset dan Tingkat Konsentrasi Kredit berada pada tingkat risiko yang Sangat Tinggi'
                ],
                'catatan' => '-'
            ],

            // Parameter rasio aset produktif terhadap total aset
            2 => [
                'threshold' => '95%',
                'descriptions' => [
                    1 => '<= 95%',
                    2 => '>95%, Komponen aset produktif memiliki eksposur Risiko kredit rendah',
                    3 => '>95%, Komponen aset produktif memiliki eksposur Risiko kredit moderat',
                    4 => '>95%, Komponen aset produktif memiliki eksposur Risiko kredit tinggi',
                    5 => '>95%, Komponen aset produktif memiliki eksposur Risiko kredit sangat tinggi'
                ],
                'catatan' => 'BPR dengan rasio <= 95% dimungkinkan mendapat peringkat lebih buruk dari 1 antara lain dalam hal BPR memiliki aset produktif dengan eksposur Risiko kredit yang lebih tinggi, misalnya penempatan dana pada'
            ],

            // Parameter rasio kredit yang diberikan terhadap total aset produktif
            3 => [
                'threshold' => '75%',
                'descriptions' => [
                    1 => '<= 75%',
                    2 => '>75%, Skema kredit sebagian besar atau seluruhnya sederhana, dan jenis kredit tidak beragam',
                    3 => '>75%, Skema kredit sebagian besar atau seluruhnya sederhana, dan jenis kredit beragam',
                    4 => '>75%, Skema kredit sebagian besar atau seluruhnya kompleks, dan jenis kredit tidak beragam',
                    5 => '>75%, Skema kredit sebagian besar atau seluruhnya kompleks, dan jenis kredit beragam'
                ],
                'catatan' => 'BPR dengan rasio <=75% dimungkinkan mendapat peringkat lebih buruk dari 1, dalam hal portofolio kredit'
            ],

            // Parameter rasio 25 debitur terbesar terhadap total kredit yang diberikan
            4 => [
                'threshold' => '20%',
                'descriptions' => [
                    1 => '<= 20%',
                    2 => '≥ 20%, Target pasar tidak berubah selama jangka waktu yang sangat lama',
                    3 => '≥ 20%, Target pasar tidak berubah selama jangka waktu yang lama',
                    4 => '≥ 20%, Target pasar tidak berubah selama jangka waktu yang cukup lama',
                    5 => '≥ 20%, Target pasar tidak berubah selama jangka waktu yang singkat'
                ],
                'catatan' => 'Konsentrasi kredit pada 25 debitur terbesar'
            ],

            // Parameter rasio kredit per sektor ekonomi terhadap total kredit yang diberikan
            5 => [
                'threshold' => '85%',
                'descriptions' => [
                    1 => '<= 85%',
                    2 => '≥ 85%, Kredit yang berasal dari 3 (tiga) sektor ekonomi terbesar tidak berubah selama jangka waktu yang sangat lama',
                    3 => '≥ 85%, Kredit yang berasal dari 3 (tiga) sektor ekonomi terbesar tidak berubah selama jangka waktu yang lama',
                    4 => '≥ 85%, Kredit yang berasal dari 3 (tiga) sektor ekonomi terbesar tidak berubah selama jangka waktu yang cukup lama',
                    5 => '≥ 85%, Kredit yang berasal dari 3 (tiga) sektor ekonomi terbesar tidak berubah selama jangka waktu yang singkat'
                ],
                'catatan' => 'Parameter rasio kredit per sektor ekonomi terhadap total kredit yang diberikan'
            ],

            // Parameter rasio kredit per sektor ekonomi terhadap total kredit yang diberikan (Kualitas Aset)
            6 => [
                'descriptions' => [
                    1 => 'Parameter Kualitas aset berada pada tingkat risiko yang Sangat Rendah',
                    2 => 'Parameter Kualitas aset berada pada tingkat risiko yang Rendah',
                    3 => 'Parameter Kualitas aset berada pada tingkat risiko yang Sedang',
                    4 => 'Parameter Kualitas aset berada pada tingkat risiko yang Tinggi',
                    5 => 'Parameter Kualitas aset berada pada tingkat risiko yang Sangat Tinggi'
                ],
                'catatan' => 'Parameter rasio kredit per sektor ekonomi terhadap total kredit yang diberikan (Kualitas Aset)'
            ],

            // Parameter rasio kredit per sektor ekonomi terhadap total kredit yang diberikan (Kualitas Aset)
            7 => [
                'threshold' => '7%',
                'descriptions' => [
                    1 => '<= 7%',
                    2 => "Rasio di atas ambang batas peringkat 1, dengan kondisi pemberian kredit memiliki kualitas yang baik, antara lain ditunjukkan dengan: <br>
                    • Kredit restrukturisasi tidak signifikan <br>
                    • Penurunan kualitas kredit dari Performing Loan ke Non Performing Loan tidak signifikan <br>
                    • Sektor ekonomi berisiko tinggi tidak signifikan <br>
                    • Jumlah kredit lancar yang menunggak >7 hari tidak signifikan <br>
                    • Komponen aset produktif bermasalah sebagian besar merupakan penempatan pada bank lain",
                    3 => 'Rasio di atas  ambang batas  peringkat 1, dengan  kondisi pemberian  kredit memiliki kualitas yang cukup baik, namun terdapat potensi penurunan, antara lain ditunjukkan dengan: <br>
                    • Kredit  restrukturisasi cukup signifikan <br>
                    • Penurunan  kualitas kredit  dari Performing Loan ke Non Performing Loan  cukup signifikan <br>
                    • Sektor  ekonomi berisiko tinggi cukup signifikan <br>
                    • Jumlah kredit  lancar yang menunggak >7 hari cukup <br>
                    • Komponen aset produktif bermasalah sebagian besar merupakan penempatan pada bank lain signifikan',
                    4 => 'Rasio di atas  ambang batas peringkat 1, dengan kondisi pemberian kredit memiliki kualitas yang kurang baik, antara lain ditunjukkan dengan: <br> 
                    • Kredit  restrukturisasi signifikan <br>
                    • Penurunan kualitas kreditdari Performing Loan ke Non Performing Loan signifikan <br>
                    • Sektor ekonomi berisiko tinggi signifikan <br>
                    • Jumlah kredit lancar yang menunggak >7 hari signifikan <br>
                    • Komponen aset produktif bermasalah sebagian besar merupakan kredit',
                    5 => 'Rasio di atas ambang batas peringkat 1, dengan kondisi pemberian kredit memiliki kualitas yang buruk, antara lain ditunjukkan dengan: <br>
                    • Kredit restrukturisasi sangat signifikan <br>
                    • Penurunan kualitas kredit dari Performing Loan ke Non Performing Loan sangat signifikan <br>
                    • Sektor ekonomi berisiko tinggi sangat signifikan <br>
                    • Jumlah kredit lancar yang menunggak >7 hari sangat signifikan <br>
                    • Komponen aset produktif bermasalah sebagian besar merupakan kredit '
                ],
                'catatan' => 'Parameter rasio kredit per sektor ekonomi terhadap total kredit yang diberikan (Kualitas Aset)'
            ],

            // Kredit bermasalah neto / total kredit yang diberikan
            8 => [
                'threshold' => '5%',
                'descriptions' => [
                    1 => '<= 5%',
                    2 => "Rasio di atas ambang batas peringkat 1, dengan kondisi pemberian kredit memiliki kualitas yang baik, antara lain ditunjukkan dengan: <br>
                    • Kredit restrukturisasi tidak signifikan <br>
                    • Penurunan kualitas kredit dari Performing Loan ke Non Performing Loan tidak signifikan <br>
                    • Sektor ekonomi berisiko tinggi tidak signifikan <br>
                    • Jumlah kredit lancar yang menunggak >7 hari tidak signifikan <br>
                    • Komponen aset produktif bermasalah sebagian besar merupakan penempatan pada bank lain",
                    3 => 'Rasio di atas  ambang batas  peringkat 1, dengan  kondisi pemberian  kredit memiliki kualitas yang cukup baik, namun terdapat potensi penurunan, antara lain ditunjukkan dengan: <br>
                    • Kredit  restrukturisasi cukup signifikan <br>
                    • Penurunan  kualitas kredit  dari Performing Loan ke Non Performing Loan  cukup signifikan <br>
                    • Sektor  ekonomi berisiko tinggi cukup signifikan <br>
                    • Jumlah kredit  lancar yang menunggak >7 hari cukup <br>
                    • Komponen aset produktif bermasalah sebagian besar merupakan penempatan pada bank lain signifikan',
                    4 => 'Rasio di atas  ambang batas peringkat 1, dengan kondisi pemberian kredit memiliki kualitas yang kurang baik, antara lain ditunjukkan dengan: <br>
                    • Kredit  restrukturisasi signifikan <br>
                    • Penurunan kualitas kreditdari Performing Loan ke Non Performing Loan signifikan <br>
                    • Sektor ekonomi berisiko tinggi signifikan <br>
                    • Jumlah kredit lancar yang menunggak >7 hari signifikan <br>
                    • Komponen aset produktif bermasalah sebagian besar merupakan kredit',
                    5 => 'Rasio di atas ambang batas peringkat 1, dengan kondisi pemberian kredit memiliki kualitas yang buruk, antara lain ditunjukkan dengan: <br>
                    • Kredit restrukturisasi sangat signifikan <br>
                    • Penurunan kualitas kredit dari Performing Loan ke Non Performing Loan sangat signifikan <br>
                    • Sektor ekonomi berisiko tinggi sangat signifikan <br>
                    • Jumlah kredit lancar yang menunggak >7 hari sangat signifikan <br>
                    • Komponen aset produktif bermasalah sebagian besar merupakan kredit'
                ],
                'catatan' => 'Kredit bermasalah neto / total kredit yang diberikan'
            ],

            // Kredit kualitas rendah / total kredit yang diberikan
            // Pilar strategi penyediaan dana
            9 => [
                'threshold' => '7%',
                'descriptions' => [
                    1 => '<= 7%',
                    2 => "Rasio di atas ambang batas peringkat 1, dengan kondisi pemberian kredit memiliki kualitas yang baik, antara lain ditunjukkan dengan: <br>
                    • Kredit restrukturisasi tidak signifikan <br>
                    • Penurunan kualitas kredit dari Performing Loan ke Non Performing Loan tidak signifikan <br>
                    • Sektor ekonomi berisiko tinggi tidak signifikan <br>
                    • Jumlah kredit lancar yang menunggak >7 hari tidak signifikan <br>
                    • Komponen aset produktif bermasalah sebagian besar merupakan penempatan pada bank lain",
                    3 => 'Rasio di atas  ambang batas  peringkat 1, dengan  kondisi pemberian  kredit memiliki kualitas yang cukup baik, namun terdapat potensi penurunan, antara lain ditunjukkan dengan: <br>
                    • Kredit  restrukturisasi cukup signifikan <br>
                    • Penurunan  kualitas kredit  dari Performing Loan ke Non Performing Loan  cukup signifikan <br>
                    • Sektor  ekonomi berisiko tinggi cukup signifikan <br>
                    • Jumlah kredit  lancar yang menunggak >7 hari cukup <br>
                    • Komponen aset produktif bermasalah sebagian besar merupakan penempatan pada bank lain signifikan',
                    4 => 'Rasio di atas  ambang batas peringkat 1, dengan kondisi pemberian kredit memiliki kualitas yang kurang baik, antara lain ditunjukkan dengan: <br>
                    • Kredit  restrukturisasi signifikan <br>
                    • Penurunan kualitas kreditdari Performing Loan ke Non Performing Loan signifikan <br>
                    • Sektor ekonomi berisiko tinggi signifikan <br>
                    • Jumlah kredit lancar yang menunggak >7 hari signifikan <br>
                    • Komponen aset produktif bermasalah sebagian besar merupakan kredit',
                    5 => 'Rasio di atas ambang batas peringkat 1, dengan kondisi pemberian kredit memiliki kualitas yang buruk, antara lain ditunjukkan dengan: <br>
                    • Kredit restrukturisasi sangat signifikan <br>
                    • Penurunan kualitas kredit dari Performing Loan ke Non Performing Loan sangat signifikan <br>
                    • Sektor ekonomi berisiko tinggi sangat signifikan <br>
                    • Jumlah kredit lancar yang menunggak >7 hari sangat signifikan <br>
                    • Komponen aset produktif bermasalah sebagian besar merupakan kredit '
                ],
                'catatan' => 'Rasio aset produktif bermasalah terhadap total aset produktif'
            ],

            // Pilar strategi penyediaan dana
            10 => [
                'descriptions' => [
                    1 => '• Pertumbuhan kredit di atas rata-rata industri, dan <br>
                        • Seluruhnya disalurkan kepada sektor ekonomi yang dikuasai.  ',
                    2 => '• Pertumbuhan kredit di atas rata-rata industri, dan <br>
                    • Sebagian besar disalurkan kepada sektor ekonomi yang dikuasai.  ',
                    3 => '• Pertumbuhan kredit di atas atau sama dengan ratarata industri, dan Sebagian kecilatau tidak sama sekali disalurkan  kepada sektor ekonomi yang dikuasai atau <br>
                    • Pertumbuhan kredit di bawah rata-rata industri, dan <br>
                    • Seluruhnya disalurkan kepada sektor ekonomi yang dikuasai. ',
                    4 => '• Pertumbuhan kredit di bawah rata-rata industri, dan <br>
                    • Sebagian besar disalurkan kepada sektor ekonomi yang dikuasai.',
                    5 => '• Pertumbuhan kredit di bawah rata-rata industri, dan <br>
                    • Sebagian kecil atau tidak sama sekali disalurkan kepada sektor ekonomi yang dikuasai. '
                ],
                'catatan' => 'Penilaian berdasarkan kualitas pelaksanaan tata kelola'
            ],

            // Pilar faktor eksternal
            11 => [
                'descriptions' => [
                    1 => 'Terdapat perubahan faktor eksternal, namun tidak berdampak pada kemampuan debitur untuk membayar kembali pinjaman.',
                    2 => 'Terdapat perubahan faktor eksternal, yang berdampak pada kemampuan debitur untuk membayar kembali pinjaman sehingga terjadi tunggakan pinjaman namun tidak menyebabkan penurunan kualitas kredit debitur.',
                    3 => 'Terdapat perubahan faktor eksternal, yang berdampak pada kinerja bisnis debitur sehingga menyebabkan terjadi tunggakan pinjaman tetapi tidak menurunkan kualitas kredit debitur menjadi NPL.',
                    4 => 'Terdapat perubahan faktor eksternal, yang menyebabkan penurunan kualitas kredit debitur hingga menjadi NPL.',
                    5 => 'Terdapat perubahan faktor eksternal, yang menyebabkan kebangkrutan debitur.'
                ],
                'catatan' => 'Penilaian berdasarkan kualitas pelaksanaan tata kelola'
            ],

            // Faktor Lainnya
            12 => [
                'descriptions' => [
                    1 => 'Parameter Penilaian Risiko Kredit Inheren Lainnya berada pada tingkat risiko yang Sangat Rendah',
                    2 => 'Parameter Penilaian Risiko Kredit Inheren Lainnya berada pada tingkat risiko yang Rendah',
                    3 => 'Parameter Penilaian Risiko Kredit Inheren Lainnya berada pada tingkat risiko yang Sedang',
                    4 => 'Parameter Penilaian Risiko Kredit Inheren Lainnya berada pada tingkat risiko yang Tinggi',
                    5 => 'Parameter Penilaian Risiko Kredit Inheren Lainnya berada pada tingkat risiko yang Sangat Tinggi'
                ],
                'catatan' => 'Pertumbuhan kredit year-on-year'
            ]

            // 13 => [
            //     'descriptions' => [
            //         1 => 'Tingkat Risiko: Sangat Rendah',
            //         2 => "Tingkat Risiko: Rendah",
            //         3 => 'Tingkat Risiko: Sedang',
            //         4 => 'Tingkat Risiko: Tinggi',
            //         5 => 'Tingkat Risiko: Sangat Tinggi'
            //     ],
            //     'catatan' => 'Pertumbuhan kredit year-on-year'
            // ],
        ];
    }

    public function cekKomentarBaru()
    {
        $subkategori = 'KREDITINHEREN';
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
            'KREDITINHEREN',
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

        // ✅ AMBIL DATA EXISTING untuk preserve rasiokredit
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

        // ✅ PRESERVE rasiokredit jika ada
        if ($existingData && !empty($existingData['rasiokredit'])) {
            $data['rasiokredit'] = $existingData['rasiokredit'];
        }

        try {
            $this->db->table('risikokredit')
                ->where('faktor1id', $faktor1id)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update($data);

            $categoryMapping = [
                1 => [2, 3, 4, 5],
                6 => [7, 8, 9]
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

            if (in_array($faktor1id, [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12])) {
                $rataRata = $this->nilaiModel->hitungRataRata(13, $this->userKodebpr, $this->periodeId);
                $this->nilaiModel->insertOrUpdateRataRata($rataRata, 13, $this->userKodebpr, $this->periodeId);
            }

            $this->db->table('risikokredit')
                ->where('faktor1id', 13)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update(['accdir2' => 0, 'is_approved' => 0]);

            $nilai13 = $this->nilaiModel->where([
                'faktor1id' => 13,
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
            $result = $this->db->table('risikokredit')
                ->where('faktor1id', $faktor1id)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update($data);

            if ($result) {
                $this->db->table('risikokredit')
                    ->where('faktor1id', 13)
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
            $this->db->table('risikokredit')
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
        ];

        try {
            // Jika data sudah ada, update dan preserve rasiokredit
            if ($existingData) {
                // ✅ PRESERVE rasiokredit
                if (isset($existingData['rasiokredit']) && !empty($existingData['rasiokredit'])) {
                    $data['rasiokredit'] = $existingData['rasiokredit'];
                }

                $data['updated_at'] = date('Y-m-d H:i:s');

                $this->db->table('risikokredit')
                    ->where('faktor1id', $faktorId)
                    ->where('kodebpr', $this->userKodebpr)
                    ->where('periode_id', $this->periodeId)
                    ->update($data);
            } else {
                // Insert data baru
                $data['created_at'] = date('Y-m-d H:i:s');
                $this->db->table('risikokredit')->insert($data);
            }

            // Update category average
            $categoryMapping = [
                1 => [2, 3, 4, 5],
                6 => [7, 8, 9]
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

            // Update factor 13
            if (in_array($faktorId, [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12])) {
                $rataRata = $this->nilaiModel->hitungRataRata(13, $this->userKodebpr, $this->periodeId);
                $this->nilaiModel->insertOrUpdateRataRata($rataRata, 13, $this->userKodebpr, $this->periodeId);
            }

            // Get updated nilai13
            $nilai13 = $this->nilaiModel->where([
                'faktor1id' => 13,
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
            return redirect()->to(base_url('Risikokredit'));
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

        return redirect()->to(base_url('Risikokredit') . '?modal_nilai=' . $faktorId)
            ->with('message', 'Nilai berhasil ditambahkan');
    }

    public function tambahKomentar()
    {
        $isAjax = $this->request->isAJAX();

        if (!$isAjax && !isset($_POST['tambahKomentar'])) {
            return redirect()->to(base_url('Risikokredit'));
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
                'subkategori' => "KREDITINHEREN",
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
                        'KREDITINHEREN',
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

                return redirect()->to(base_url('Risikokredit') . '?modal_komentar=' . $faktorId)
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
            return redirect()->to(base_url('Risikokredit'));
        }

        if (!$this->userKodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
        }

        if (!$this->periodeId) {
            return redirect()->back()->with('error', 'Periode tidak valid');
        }

        $validation = $this->validate([
            'aba' => 'required|decimal',
            'kydbank' => 'required|decimal',
            'kydpihak3' => 'required|decimal',
            'totalaset' => 'required|decimal',
            'total25debitur' => 'required|decimal',
            'perdagangan' => 'required|decimal',
            'jasa' => 'required|decimal',
            'konsumsirumah' => 'required|decimal',
        ]);

        if (!$validation) {
            return redirect()->back()->with('err', $this->validator->listErrors());
        }

        $aba = $this->request->getPost('aba');
        $kydbank = $this->request->getPost('kydbank');
        $kydpihak3 = $this->request->getPost('kydpihak3');
        $totalaset = $this->request->getPost('totalaset');
        $total25debitur = $this->request->getPost('total25debitur');
        $perdagangan = $this->request->getPost('perdagangan');
        $jasa = $this->request->getPost('jasa');
        $konsumsirumah = $this->request->getPost('konsumsirumah');

        // Hitung nilai-nilai turunan 
        $kydgross = $kydbank + $kydpihak3;
        $asetproduktif = $aba + $kydgross;
        $rasioasetproduktif = $totalaset > 0 ? ($asetproduktif / $totalaset) * 100 : 0;
        $rasiokreditdiberikan = $asetproduktif > 0 ? ($kydgross / $asetproduktif) * 100 : 0;
        $rasio25debitur = $kydgross > 0 ? ($total25debitur / $kydgross) * 100 : 0;
        $sektorekonomi = $perdagangan + $jasa + $konsumsirumah;
        $rasioekonomi = $kydgross > 0 ? ($sektorekonomi / $kydgross) * 100 : 0;

        // Ambil data user
        $user = $this->userModel->find($this->userId);

        // Siapkan data untuk disimpan
        $data = [
            'user_id' => $this->userId,
            'fullname' => $user['fullname'] ?? 'Unknown',
            'kodebpr' => $this->userKodebpr,
            'periode_id' => $this->periodeId,
            'aba' => $aba,
            'kydbank' => $kydbank,
            'kydpihak3' => $kydpihak3,
            'kydgross' => $kydgross,
            'totalaset' => $totalaset,
            'total25debitur' => $total25debitur,
            'perdagangan' => $perdagangan,
            'jasa' => $jasa,
            'konsumsirumah' => $konsumsirumah,
            'sektorekonomi' => $sektorekonomi,
            'asetproduktif' => $asetproduktif,
            'rasioasetproduktif' => $rasioasetproduktif,
            'rasiokreditdiberikan' => $rasiokreditdiberikan,
            'rasio25debitur' => $rasio25debitur,
            'rasioekonomi' => $rasioekonomi,
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
                    return redirect()->to(base_url('Risikokredit'))
                        ->with('message', 'Data kalkulator berhasil diupdate');
                } else {
                    throw new \Exception('Gagal mengupdate data');
                }

            } else {
                // Data belum ada, lakukan INSERT
                $data['created_at'] = date('Y-m-d H:i:s');

                $result = $this->kalkulatorModel->insert($data);

                if ($result) {
                    return redirect()->to(base_url('Risikokredit'))
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
            return redirect()->to(base_url('Risikokredit'));
        }

        if (!$this->userKodebpr || !$this->periodeId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'User tidak memiliki kode BPR atau periode yang valid'
            ]);
        }

        // ✅ Ambil SEMUA data dari request (termasuk data kalkulator)
        $aba = $this->request->getPost('aba');
        $kydbank = $this->request->getPost('kydbank');
        $kydpihak3 = $this->request->getPost('kydpihak3');
        $totalaset = $this->request->getPost('totalaset');
        $total25debitur = $this->request->getPost('total25debitur');
        $perdagangan = $this->request->getPost('perdagangan');
        $jasa = $this->request->getPost('jasa');
        $konsumsirumah = $this->request->getPost('konsumsirumah');

        // Ambil rasio yang sudah dihitung
        $rasioasetproduktif = $this->request->getPost('rasioasetproduktif');
        $rasiokreditdiberikan = $this->request->getPost('rasiokreditdiberikan');
        $rasio25debitur = $this->request->getPost('rasio25debitur');
        $rasioekonomi = $this->request->getPost('rasioekonomi');

        // ✅ Hitung nilai turunan
        $kydgross = ($kydbank ?? 0) + ($kydpihak3 ?? 0);
        $asetproduktif = ($aba ?? 0) + $kydgross;
        $sektorekonomi = ($perdagangan ?? 0) + ($jasa ?? 0) + ($konsumsirumah ?? 0);

        // ✅ Debug log
        log_message('info', 'Insert Rasio - Data diterima: ' . json_encode([
            'rasioasetproduktif' => $rasioasetproduktif,
            'rasiokreditdiberikan' => $rasiokreditdiberikan,
            'rasio25debitur' => $rasio25debitur,
            'rasioekonomi' => $rasioekonomi
        ]));

        // Validasi - pastikan ada minimal 1 rasio
        if (
            empty($rasioasetproduktif) && empty($rasiokreditdiberikan) &&
            empty($rasio25debitur) && empty($rasioekonomi)
        ) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Tidak ada data rasio untuk dimasukkan. Silakan hitung terlebih dahulu.'
            ]);
        }

        $user = $this->userModel->find($this->userId);

        try {
            // ✅ STEP 1: Simpan/Update ke database kalkulator_kredit
            $existingKalkulator = $this->kalkulatorModel
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->first();

            $kalkulatorData = [
                'user_id' => $this->userId,
                'fullname' => $user['fullname'] ?? 'Unknown',
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId,
                'aba' => $aba ?? 0,
                'kydbank' => $kydbank ?? 0,
                'kydpihak3' => $kydpihak3 ?? 0,
                'totalaset' => $totalaset ?? 0,
                'total25debitur' => $total25debitur ?? 0,
                'perdagangan' => $perdagangan ?? 0,
                'jasa' => $jasa ?? 0,
                'konsumsirumah' => $konsumsirumah ?? 0,
                'kydgross' => $kydgross,
                'asetproduktif' => $asetproduktif,
                'sektorekonomi' => $sektorekonomi,
                'rasioasetproduktif' => $rasioasetproduktif ?? 0,
                'rasiokreditdiberikan' => $rasiokreditdiberikan ?? 0,
                'rasio25debitur' => $rasio25debitur ?? 0,
                'rasioekonomi' => $rasioekonomi ?? 0,
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

            // ✅ STEP 2: Insert/Update rasio ke tabel risikokredit
            $rasioMapping = [
                2 => $rasioasetproduktif,
                3 => $rasiokreditdiberikan,
                4 => $rasio25debitur,
                5 => $rasioekonomi
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
                if ($result && in_array($faktorId, [2, 3, 4, 5])) {
                    $this->calculateAndSaveCategoryAverage(1, [2, 3, 4, 5], $this->userKodebpr, $this->periodeId);
                }
            }

            // Update rata-rata total
            if ($successCount > 0) {
                $rataRata = $this->nilaiModel->hitungRataRata(13, $this->userKodebpr, $this->periodeId);
                $this->nilaiModel->insertOrUpdateRataRata($rataRata, 13, $this->userKodebpr, $this->periodeId);
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

    public function simpanKalkulatorKualitasAset()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }

        if (!isset($_POST['simpanKalkulatorKualitasAset'])) {
            return redirect()->to(base_url('Risikokredit'));
        }

        if (!$this->userKodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
        }

        if (!$this->periodeId) {
            return redirect()->back()->with('error', 'Periode tidak valid');
        }

        $validation = $this->validate([
            'abanpl' => 'required|decimal',
            'kydnpl3' => 'required|decimal',
            'kydnpl4' => 'required|decimal',
            'kydnpl5' => 'required|decimal',
            'kreditdpk2' => 'required|decimal',
            'kreditbermasalah' => 'required|decimal',
            'kreditrestruktur1' => 'required|decimal',
        ]);

        if (!$validation) {
            return redirect()->back()->with('err', $this->validator->listErrors());
        }

        $abanpl = $this->request->getPost('abanpl');
        $kydnpl3 = $this->request->getPost('kydnpl3');
        $kydnpl4 = $this->request->getPost('kydnpl4');
        $kydnpl5 = $this->request->getPost('kydnpl5');
        $kreditdpk2 = $this->request->getPost('kreditdpk2');
        $kreditbermasalah = $this->request->getPost('kreditbermasalah');
        $kreditrestruktur1 = $this->request->getPost('kreditrestruktur1');

        $kalkulatorData1 = $this->kalkulatorModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->first();

        if (!$kalkulatorData1 || empty($kalkulatorData1['asetproduktif']) || empty($kalkulatorData1['kydgross'])) {
            return redirect()->back()->with('error', 'Silakan isi Kalkulator Komposisi Portofolio Aset terlebih dahulu');
        }

        $asetproduktif = $kalkulatorData1['asetproduktif'];
        $kydgross = $kalkulatorData1['kydgross'];

        $kydkoleknpl = $kydnpl3 + $kydnpl4 + $kydnpl5;
        $asetproduktifbermasalah = $abanpl + $kydkoleknpl;
        $kreditkualitasrendah = $kreditdpk2 + $kreditbermasalah + $kreditrestruktur1;

        $rasioasetproduktifbermasalah = $asetproduktif > 0 ?
            ($asetproduktifbermasalah / $asetproduktif) * 100 : 0;
        $rasiokreditbermasalah = $kydgross > 0 ?
            ($kreditbermasalah / $kydgross) * 100 : 0;
        $rasiokreditkualitasrendah = $kydgross > 0 ?
            ($kreditkualitasrendah / $kydgross) * 100 : 0;

        $user = $this->userModel->find($this->userId);

        $data = [
            'abanpl' => $abanpl,
            'kydnpl3' => $kydnpl3,
            'kydnpl4' => $kydnpl4,
            'kydnpl5' => $kydnpl5,
            'kreditdpk2' => $kreditdpk2,
            'kreditrestruktur1' => $kreditrestruktur1,
            'asetproduktifbermasalah' => $asetproduktifbermasalah,
            'kydkoleknpl' => $kydkoleknpl,
            'kreditbermasalah' => $kreditbermasalah,
            'rasioasetproduktifbermasalah' => $rasioasetproduktifbermasalah,
            'rasiokreditbermasalah' => $rasiokreditbermasalah,
            'rasiokreditkualitasrendah' => $rasiokreditkualitasrendah,
        ];

        try {
            $existingData = $this->kalkulatorModel
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->first();

            if ($existingData) {
                $data['updated_at'] = date('Y-m-d H:i:s');

                $result = $this->kalkulatorModel
                    ->where('kodebpr', $this->userKodebpr)
                    ->where('periode_id', $this->periodeId)
                    ->set($data)
                    ->update();

                if ($result) {
                    return redirect()->to(base_url('Risikokredit'))
                        ->with('message', 'Data kalkulator kualitas aset berhasil diupdate');
                } else {
                    throw new \Exception('Gagal mengupdate data');
                }

            } else {
                return redirect()->back()
                    ->with('error', 'Data kalkulator pertama belum ada. Silakan isi terlebih dahulu.');
            }

        } catch (\Exception $e) {
            log_message('error', 'Error simpanKalkulatorKualitasAset: ' . $e->getMessage());
            return redirect()->back()
                ->with('err', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function insertRasioKualitasAsetToKertasKerja()
    {
        if ($redirect = $this->checkAuth()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
        }

        log_message('info', 'insertRasioKualitasAsetToKertasKerja dipanggil');

        if (!$this->userKodebpr || !$this->periodeId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'User tidak memiliki kode BPR atau periode yang valid'
            ]);
        }

        // ✅ Ambil SEMUA data dari request
        $abanpl = $this->request->getPost('abanpl');
        $kydnpl3 = $this->request->getPost('kydnpl3');
        $kydnpl4 = $this->request->getPost('kydnpl4');
        $kydnpl5 = $this->request->getPost('kydnpl5');
        $kreditdpk2 = $this->request->getPost('kreditdpk2');
        $kreditbermasalah = $this->request->getPost('kreditbermasalah');
        $kreditrestruktur1 = $this->request->getPost('kreditrestruktur1');

        // Ambil rasio yang sudah dihitung
        $rasioasetproduktifbermasalah = $this->request->getPost('rasioasetproduktifbermasalah');
        $rasiokreditbermasalah = $this->request->getPost('rasiokreditbermasalah');
        $rasiokreditkualitasrendah = $this->request->getPost('rasiokreditkualitasrendah');

        log_message('info', 'Rasio values: ' . json_encode([
            'rasioasetproduktifbermasalah' => $rasioasetproduktifbermasalah,
            'rasiokreditbermasalah' => $rasiokreditbermasalah,
            'rasiokreditkualitasrendah' => $rasiokreditkualitasrendah
        ]));

        if (
            empty($rasioasetproduktifbermasalah) && empty($rasiokreditbermasalah) &&
            empty($rasiokreditkualitasrendah)
        ) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Tidak ada data rasio untuk dimasukkan. Silakan hitung terlebih dahulu.'
            ]);
        }

        $user = $this->userModel->find($this->userId);

        try {
            // ✅ STEP 1: Simpan/Update ke database kalkulator_kredit
            $existingKalkulator = $this->kalkulatorModel
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->first();

            if (!$existingKalkulator) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Data kalkulator pertama belum ada. Silakan isi Kalkulator Komposisi Portofolio Aset terlebih dahulu.'
                ]);
            }

            // Hitung nilai turunan
            $kydkoleknpl = ($kydnpl3 ?? 0) + ($kydnpl4 ?? 0) + ($kydnpl5 ?? 0);
            $asetproduktifbermasalah = ($abanpl ?? 0) + $kydkoleknpl;
            $kreditkualitasrendah = ($kreditdpk2 ?? 0) + ($kreditbermasalah ?? 0) + ($kreditrestruktur1 ?? 0);

            $kalkulatorData = [
                'abanpl' => $abanpl ?? 0,
                'kydnpl3' => $kydnpl3 ?? 0,
                'kydnpl4' => $kydnpl4 ?? 0,
                'kydnpl5' => $kydnpl5 ?? 0,
                'kreditdpk2' => $kreditdpk2 ?? 0,
                'kreditbermasalah' => $kreditbermasalah ?? 0,
                'kreditrestruktur1' => $kreditrestruktur1 ?? 0,
                'kydkoleknpl' => $kydkoleknpl,
                'asetproduktifbermasalah' => $asetproduktifbermasalah,
                'rasioasetproduktifbermasalah' => $rasioasetproduktifbermasalah ?? 0,
                'rasiokreditbermasalah' => $rasiokreditbermasalah ?? 0,
                'rasiokreditkualitasrendah' => $rasiokreditkualitasrendah ?? 0,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $this->kalkulatorModel->update($existingKalkulator['id'], $kalkulatorData);
            log_message('info', 'Kalkulator kualitas aset updated');

            // ✅ STEP 2: Insert/Update rasio ke tabel risikokredit
            $rasioMapping = [
                7 => $rasioasetproduktifbermasalah,
                8 => $rasiokreditbermasalah,
                9 => $rasiokreditkualitasrendah,
            ];

            $successCount = 0;
            $errorMessages = [];
            $processedFaktors = [];

            foreach ($rasioMapping as $faktorId => $rasioValue) {
                if ($rasioValue === null || $rasioValue === '' || $rasioValue === false) {
                    continue;
                }

                $rasioValue = (float) $rasioValue;

                $existingData = $this->nilaiModel
                    ->where('faktor1id', $faktorId)
                    ->where('kodebpr', $this->userKodebpr)
                    ->where('periode_id', $this->periodeId)
                    ->first();

                if ($existingData) {
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
                    }
                } else {
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
                    }
                }

                if ($result && in_array($faktorId, [7, 8, 9])) {
                    $this->calculateAndSaveCategoryAverage(6, [7, 8, 9], $this->userKodebpr, $this->periodeId);
                }
            }

            if ($successCount > 0) {
                $rataRata = $this->nilaiModel->hitungRataRata(13, $this->userKodebpr, $this->periodeId);
                $this->nilaiModel->insertOrUpdateRataRata($rataRata, 13, $this->userKodebpr, $this->periodeId);

                $message = "Berhasil menyimpan data kalkulator dan memasukkan {$successCount} rasio kualitas aset (Faktor: " .
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
                    'message' => 'Tidak ada rasio yang berhasil dimasukkan'
                ]);
            }

        } catch (\Exception $e) {
            log_message('error', 'Error insertRasioKualitasAsetToKertasKerja: ' . $e->getMessage());

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
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

        return redirect()->to(base_url('Risikokredit'));
    }

    public function approve($idNilai = null)
    {
        if ($idNilai === null) {
            return redirect()->back()->with('err', 'ID Faktor tidak valid.');
        }

        $this->db->table('risikokredit')
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
            return redirect()->back()->with('err', 'Kode BPR atau Periode ID tidak valid');
        }

        // Faktor wajib: 1–11 dan 13 (12 diskip)
        $requiredFaktor = array_merge(range(1, 11), [13]);
        $totalRequired = count($requiredFaktor);

        // Cek apakah semua faktor wajib sudah diisi
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
            $this->db->table('risikokredit')
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
            $this->db->table('risikokredit')
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->whereIn('faktor1id', range(1, 13))
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

        $this->db->table('risikokredit')
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
            return redirect()->to('/Risikokredit')->with('error', 'Data tidak lengkap');
        }

        $nilaiFaktor = $this->nilaiModel
            ->where('faktor1id', $faktor1id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if (!$nilaiFaktor) {
            return redirect()->to('/Risikokredit')->with('error', 'Data tidak ditemukan');
        }

        $dataUpdate = [
            'accdir2' => 0,
            'accdir2_by' => $this->userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->nilaiModel->update($nilaiFaktor['id'], $dataUpdate)) {
            return redirect()->to('/Risikokredit')->with('message', 'Persetujuan dibatalkan');
        }

        return redirect()->to('/Risikokredit')->with('error', 'Gagal memperbarui data');
    }

    public function recalculateCategoryAverage()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $faktorId = $this->request->getPost('faktor_id');

        $categoryMapping = [
            1 => [2, 3, 4, 5],
            6 => [7, 8, 9]
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

            $rataRata = $this->nilaiModel->hitungRataRata(13, $this->userKodebpr, $this->periodeId);
            $this->nilaiModel->insertOrUpdateRataRata($rataRata, 13, $this->userKodebpr, $this->periodeId);

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
            "KREDITINHEREN",
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
            "KREDITINHEREN",
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

        $faktorIds = range(1, 13);
        $counts = [];

        foreach ($faktorIds as $faktorId) {
            $count = $this->commentReadsModel->countUnreadCommentsForUserByFactor(
                $faktorId,
                "KREDITINHEREN",
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

        $faktorId = 14;
        $penilaiankredit = $this->request->getPost('penilaiankredit');
        $user = $this->userModel->find($this->userId);

        $penjelasanMapping = [
            '1' => 'Tingkat Risiko Kredit Inheren: Sangat Rendah',
            '2' => 'Tingkat Risiko Kredit Inheren: Rendah',
            '3' => 'Tingkat Risiko Kredit Inheren: Sedang',
            '4' => 'Tingkat Risiko Kredit Inheren: Tinggi',
            '5' => 'Tingkat Risiko Kredit Inheren: Sangat Tinggis'
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
                    'keterangan' => 'Tingkat Risiko Kredit Inheren Posisi Sebelumnya',
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
                    return redirect()->to(base_url('Risikokredit'))
                        ->with('message', 'Data Tingkat Risiko Kredit Inheren berhasil diupdate');
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
                    return redirect()->to(base_url('Risikokredit'))
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
        $faktorId = 13;
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
            ->where('faktor1id', 13)
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

        $faktorId = 13;
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
                    return redirect()->to(base_url('Risikokredit'))
                        ->with('message', 'Kesimpulan berhasil diupdate');
                } else {
                    throw new \Exception('Gagal mengupdate kesimpulan');
                }

            } else {
                $rataRata = $this->nilaiModel->hitungRataRata($faktorId, $this->userKodebpr, $this->periodeId);
                $rataRata = ($rataRata - floor($rataRata) >= 0.5) ? ceil($rataRata) : floor($rataRata);

                $penjelasanMapping = [
                    '1' => 'Tingkat Risiko Kredit Inheren: Sangat Rendah',
                    '2' => 'Tingkat Risiko Kredit Inheren: Rendah',
                    '3' => 'Tingkat Risiko Kredit Inheren: Sedang',
                    '4' => 'Tingkat Risiko Kredit Inheren: Tinggi',
                    '5' => 'Tingkat Risiko Kredit Inheren: Sangat Tinggi'
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
                    return redirect()->to(base_url('Risikokredit'))
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
            ->where('faktor1id', 14)
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
                'id' => 1,
                'title' => 'Pilar Komposisi Portofolio Aset dan Tingkat Konsentrasi Kredit',
                'type' => 'category',
                'faktor_id' => 1,
                'faktor_ids' => [2, 3, 4, 5],
                'description' => 'Penilaian terhadap komposisi portofolio aset dan tingkat konsentrasi kredit',
                'children' => [
                    [
                        'id' => 2,
                        'title' => 'Parameter rasio aset produktif terhadap total aset',
                        'type' => 'parameter',
                        'faktor_id' => 2,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 3,
                        'title' => 'Parameter rasio kredit yang diberikan terhadap total aset produktif',
                        'type' => 'parameter',
                        'faktor_id' => 3,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 4,
                        'title' => 'Parameter rasio 25 debitur terbesar terhadap total kredit yang diberikan',
                        'type' => 'parameter',
                        'faktor_id' => 4,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 5,
                        'title' => 'Parameter rasio kredit per sektor ekonomi terhadap total kredit yang diberikan',
                        'type' => 'parameter',
                        'faktor_id' => 5,
                        'previous_periode_faktor_id' => null
                    ]
                ]
            ],
            [
                'id' => 6,
                'title' => 'Pilar Kualitas Aset',
                'type' => 'category',
                'faktor_id' => 6,
                'faktor_ids' => [7, 8, 9],
                'description' => 'Penilaian terhadap kualitas aset produktif',
                'children' => [
                    [
                        'id' => 7,
                        'title' => 'Parameter rasio aset produktif bermasalah terhadap total aset produktif',
                        'type' => 'parameter',
                        'faktor_id' => 7,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 8,
                        'title' => 'Parameter kredit bermasalah neto / total kredit yang diberikan',
                        'type' => 'parameter',
                        'faktor_id' => 8,
                        'previous_periode_faktor_id' => null
                    ],
                    [
                        'id' => 9,
                        'title' => 'Parameter kredit kualitas rendah / total kredit yang diberikan',
                        'type' => 'parameter',
                        'faktor_id' => 9,
                        'previous_periode_faktor_id' => null
                    ]
                ]
            ],
            [
                'id' => 10,
                'title' => 'Pilar Strategi Penyediaan Dana',
                'type' => 'single',
                'faktor_id' => 10,
                'description' => 'Penilaian strategi penyediaan dana'
            ],
            [
                'id' => 11,
                'title' => 'Pilar Faktor Eksternal',
                'type' => 'single',
                'faktor_id' => 11,
                'description' => 'Penilaian pengaruh faktor eksternal'
            ],
            [
                'id' => 12,
                'title' => 'Faktor Lainnya',
                'type' => 'single',
                'faktor_id' => 12,
                'description' => 'Faktor penilaian lainnya'
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
            1 => [2, 3, 4, 5],
            6 => [7, 8, 9],
        ];

        foreach ($categoryMapping as $categoryId => $childrenIds) {
            if (in_array($changedFaktorId, $childrenIds)) {
                $this->calculateAndSaveCategoryAverage($categoryId, $childrenIds, $kodebpr, $periodeId);
                break;
            }
        }
    }

    public function exporttxtrisikokredit()
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

        function sanitizeTxt($text)
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

        $data_risikokredit = $this->nilaiModel
            ->getDataByKodebprAndPeriode($kodebpr, $periodeId);

        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);
        $sandibpr = '';
        $kodejenis = '';

        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'] ?? '';
            $kodejenis = $infobpr['kodejenis'] ?? '';
        }

        $kodeMap = [
            1 => '1210',
            2 => '1211',
            3 => '1212',
            4 => '1213',
            5 => '1214',
            6 => '1220',
            7 => '1221',
            8 => '1222',
            9 => '1223',
            10 => '1230',
            11 => '1240',
            12 => '1299',
            13 => '1292',
        ];

        $indexedData = [];
        foreach ($data_risikokredit as $row) {
            if (isset($row['faktor1id'])) {
                $indexedData[$row['faktor1id']] = $row;
            }
        }

        $output = '';

        // HEADER
        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|PRBPRKS|0101|{$modalinti}|{$totalaset}|{$kantorcabang}|{$atmdebit}|{$kategori}||\r\n";

        foreach ($kodeMap as $faktorId => $kode) {

            if (isset($indexedData[$faktorId])) {
                $row = $indexedData[$faktorId];

                $rasio = sanitizeTxt($row['rasiokredit'] ?? '');
                $penilaiankredit = sanitizeTxt($row['penilaiankredit'] ?? '');
                $keterangan = sanitizeTxt($row['keterangan'] ?? '');
            } else {
                $rasio = '';
                $penilaiankredit = '';
                $keterangan = '';
            }

            $output .= "D01|{$kode}|{$rasio}|{$penilaiankredit}|{$keterangan}\r\n";
        }

        $filename = "PRBPRKS-0101-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        $response = service('response');
        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader(
            'Content-Disposition',
            'attachment; filename="' . $filename . '"'
        );

        return $response->setBody($output);
    }

    // PDF
    public function exportPDF()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }

        if (!$this->userKodebpr || !$this->periodeId) {
            return redirect()->back()->with('error', 'Data tidak valid');
        }

        $periodeDetail = $this->periodeModel->find($this->periodeId);
        $bprData = $this->infobprModel->where('kodebpr', $this->userKodebpr)->first();

        $nilaiData = $this->db->table('risikokredit as r')
            ->select('r.*, u.fullname')
            ->join('users as u', 'u.id = r.user_id', 'left')
            ->where('r.kodebpr', $this->userKodebpr)
            ->where('r.periode_id', $this->periodeId)
            ->whereIn('r.faktor1id', range(1, 13))
            ->orderBy('r.faktor1id', 'ASC')
            ->get()
            ->getResultArray();

        $kalkulatorData = $this->kalkulatorModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->first();

        $nilai13 = $this->nilaiModel->where([
            'faktor1id' => 13,
            'kodebpr' => $this->userKodebpr,
            'periode_id' => $this->periodeId
        ])->first();

        // Ambil nilai14 (Penilaian Risiko Periode Sebelumnya)
        $nilai14 = $this->nilaiModel->where([
            'faktor1id' => 14,
            'kodebpr' => $this->userKodebpr,
            'periode_id' => $this->periodeId
        ])->first();

        $pdfData = [
            'periode' => $periodeDetail,
            'bpr' => $bprData,
            'nilai' => $this->strukturDataNilai($nilaiData),
            'kalkulator' => $kalkulatorData,
            'nilai13' => $nilai13,  // Tambahkan ini
            'nilai14' => $nilai14,  // Tambahkan ini
            'kesimpulan' => $nilai13  // Kesimpulan sama dengan nilai13
        ];

        return $this->response->setJSON([
            'status' => 'success',
            'data' => $pdfData
        ]);
    }

    private function strukturDataNilai($nilaiData)
    {
        $struktur = [
            'komposisi' => [
                'kategori' => null,
                'children' => []
            ],
            'kualitas' => [
                'kategori' => null,
                'children' => []
            ],
            'strategi' => null,
            'eksternal' => null,
            'lainnya' => null,
            'rata_rata' => null
        ];

        foreach ($nilaiData as $nilai) {
            $faktorId = $nilai['faktor1id'];

            if ($faktorId == 1) {
                $struktur['komposisi']['kategori'] = $nilai;
            } elseif (in_array($faktorId, [2, 3, 4, 5])) {
                $struktur['komposisi']['children'][] = $nilai;
            } elseif ($faktorId == 6) {
                $struktur['kualitas']['kategori'] = $nilai;
            } elseif (in_array($faktorId, [7, 8, 9])) {
                $struktur['kualitas']['children'][] = $nilai;
            } elseif ($faktorId == 10) {
                $struktur['strategi'] = $nilai;
            } elseif ($faktorId == 11) {
                $struktur['eksternal'] = $nilai;
            } elseif ($faktorId == 12) {
                $struktur['lainnya'] = $nilai;
            } elseif ($faktorId == 13) {
                $struktur['rata_rata'] = $nilai;
            }
        }

        return $struktur;
    }

    public function viewPDFPreview()
    {
        return view('Risikokredit/export_pdf');
    }

    public function exportPDFGabungan()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }

        if (!$this->userKodebpr || !$this->periodeId) {
            return redirect()->back()->with('error', 'Data tidak valid');
        }

        // Ambil data untuk kedua bagian
        $dataInheren = $this->getDataInheren();
        $dataKPMR = $this->getDataKPMR();
        $dataKepatuhan = $this->getDataKepatuhanInheren();
        $dataKepatuhankpmr = $this->getDataKepatuhanKPMR();
        $dataLikuiditas = $this->getDataLikuiditas();
        $dataLikuiditasKPMR = $this->getDataLikuiditasKPMR();
        $bprData = $this->infobprModel->where('kodebpr', $this->userKodebpr)->first();

        $logoBase64 = '';
        if (!empty($bprData['logo'])) {
            $logoPath = FCPATH . 'asset/img/' . $bprData['logo'];
            if (file_exists($logoPath)) {
                $imageData = file_get_contents($logoPath);
                $imageType = mime_content_type($logoPath);
                $logoBase64 = 'data:' . $imageType . ';base64,' . base64_encode($imageData);
            }
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data' => [
                'inheren' => $dataInheren,
                'kpmr' => $dataKPMR,
                'kepatuhan' => $dataKepatuhan,
                'kepatuhankpmr' => $dataKepatuhankpmr,
                'likuiditas' => $dataLikuiditas,
                'likuidiaskpmr' => $dataLikuiditasKPMR,
                'periode' => $this->periodeModel->find($this->periodeId),
                'bpr' => $this->infobprModel->where('kodebpr', $this->userKodebpr)->first()
            ]
        ]);
    }

    // 2. Method helper untuk ambil data Inheren
    private function getDataInheren()
    {
        $nilaiData = $this->db->table('risikokredit as r')
            ->select('r.*, u.fullname')
            ->join('users as u', 'u.id = r.user_id', 'left')
            ->where('r.kodebpr', $this->userKodebpr)
            ->where('r.periode_id', $this->periodeId)
            ->whereIn('r.faktor1id', range(1, 14))
            ->orderBy('r.faktor1id', 'ASC')
            ->get()
            ->getResultArray();

        return [
            'nilai' => $this->strukturDataNilai($nilaiData),
            'kalkulator' => $this->kalkulatorModel
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->first(),
            'nilai13' => $this->nilaiModel->where([
                'faktor1id' => 13,
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId
            ])->first(),
            'nilai14' => $this->nilaiModel->where([
                'faktor1id' => 14,
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId
            ])->first()
        ];
    }

    // 3. Method helper untuk ambil data KPMR
    private function getDataKPMR()
    {
        $kpmrModel = new \App\Models\M_risikokreditkpmr();

        $nilaiData = $this->db->table('risikokredit_kpmr as r')
            ->select('r.*, u.fullname')
            ->join('users as u', 'u.id = r.user_id', 'left')
            ->where('r.kodebpr', $this->userKodebpr)
            ->where('r.periode_id', $this->periodeId)
            ->whereIn('r.faktor1id', range(16, 34))
            ->orderBy('r.faktor1id', 'ASC')
            ->get()
            ->getResultArray();

        return [
            'nilai' => $this->strukturDataNilaiKPMR($nilaiData),
            'nilai33' => $kpmrModel->where([
                'faktor1id' => 33,
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId
            ])->first(),
            'nilai34' => $kpmrModel->where([
                'faktor1id' => 34,
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId
            ])->first()
        ];
    }

    // 4. Helper untuk struktur data KPMR
    private function strukturDataNilaiKPMR($nilaiData)
    {
        $struktur = [
            'pengawasan' => [
                'kategori' => null,
                'children' => []
            ],
            'kebijakan' => [
                'kategori' => null,
                'children' => []
            ],
            'proses' => [
                'kategori' => null,
                'children' => []
            ],
            'pengendalian' => [
                'kategori' => null,
                'children' => []
            ],
            'rata_rata' => null
        ];

        foreach ($nilaiData as $nilai) {
            $faktorId = $nilai['faktor1id'];

            if ($faktorId == 16) {
                $struktur['pengawasan']['kategori'] = $nilai;
            } elseif (in_array($faktorId, [17, 18, 19, 20, 21, 22])) {
                $struktur['pengawasan']['children'][] = $nilai;
            } elseif ($faktorId == 23) {
                $struktur['kebijakan']['kategori'] = $nilai;
            } elseif (in_array($faktorId, [24, 25, 26])) {
                $struktur['kebijakan']['children'][] = $nilai;
            } elseif ($faktorId == 27) {
                $struktur['proses']['kategori'] = $nilai;
            } elseif (in_array($faktorId, [28, 29])) {
                $struktur['proses']['children'][] = $nilai;
            } elseif ($faktorId == 30) {
                $struktur['pengendalian']['kategori'] = $nilai;
            } elseif (in_array($faktorId, [31, 32])) {
                $struktur['pengendalian']['children'][] = $nilai;
            } elseif ($faktorId == 33) {
                $struktur['rata_rata'] = $nilai;
            }
        }

        return $struktur;
    }

    // 5. View untuk preview PDF Gabungan
    public function viewPDFGabunganPreview()
    {
        return view('Risikokredit/export_pdf');
    }

    public function viewExportPDFOperasional()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $data = [
            'judul' => 'Export PDF Risiko Operasional'
        ];

        return view('Risikokredit/export_pdf_operasional_view', $data);
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

            // Build hierarchical structure
            $inheren = $this->buildOperasionalInherenStructure($dataInheren);
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

    public function exportPDFGabunganKepatuhan()
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
            $dataInheren = $this->db->table('risikokepatuhan')
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->whereIn('faktor1id', range(73, 82))
                ->orderBy('faktor1id', 'ASC')
                ->get()
                ->getResultArray();

            //Get Risiko Operasional KPMR data (faktor 51-71)
            $dataKPMR = $this->db->table('risikokepatuhan_kpmr')
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->whereIn('faktor1id', range(84, 103))
                ->orderBy('faktor1id', 'ASC')
                ->get()
                ->getResultArray();

            // Build hierarchical structure
            $inheren = $this->buildKepatuhanInherenStructure($dataInheren);
            $kpmr = $this->buildKepatuhanKPMRStructure($dataKPMR);

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
            log_message('error', 'Error exportPDFGabunganKepatuhan: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ]);
        }
    }

    public function exportPDFGabunganLikuiditas()
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
            $dataInheren = $this->db->table('likuiditasinheren')
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->whereIn('faktor1id', range(105, 116))
                ->orderBy('faktor1id', 'ASC')
                ->get()
                ->getResultArray();

            //Get Risiko Operasional KPMR data (faktor 51-71)
            $dataKPMR = $this->db->table('likuiditaskpmr')
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->whereIn('faktor1id', range(118, 136))
                ->orderBy('faktor1id', 'ASC')
                ->get()
                ->getResultArray();

            // Build hierarchical structure
            $inheren = $this->buildLikuiditasInherenStructure($dataInheren);
            $kpmr = $this->buildLikuiditasKPMRStructure($dataKPMR);

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
            log_message('error', 'Error exportPDFGabunganLikuiditas: ' . $e->getMessage());
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
            'nilai13' => $lookup[48] ?? null,
            'nilai14' => $lookup[49] ?? null
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
            'nilai33' => $lookup[70] ?? null,
            'nilai34' => $lookup[71] ?? null
        ];

        return $structure;
    }

    private function buildKepatuhanInherenStructure($dataArray)
    {
        $lookup = [];
        foreach ($dataArray as $item) {
            $lookup[$item['faktor1id']] = $item;
        }

        $structure = [
            'nilai' => [
                'pelanggaran' => [
                    'kategori' => $lookup[73] ?? null,
                    'children' => array_filter([
                        $lookup[74] ?? null,
                        $lookup[75] ?? null
                    ])
                ],
                'hukum' => [
                    'kategori' => $lookup[76] ?? null,
                    'children' => array_filter([
                        $lookup[77] ?? null,
                        $lookup[78] ?? null,
                        $lookup[79] ?? null
                    ])
                ],
                'lainnya' => $lookup[80] ?? null
            ],
            'nilai81' => $lookup[81] ?? null,
            'nilai82' => $lookup[82] ?? null
        ];

        return $structure;
    }

    private function buildKepatuhanKPMRStructure($dataArray)
    {
        $lookup = [];
        foreach ($dataArray as $item) {
            $lookup[$item['faktor1id']] = $item;
        }

        $structure = [
            'nilai' => [
                'pengawasan' => [
                    'kategori' => $lookup[84] ?? null,
                    'children' => array_filter([
                        $lookup[85] ?? null,
                        $lookup[86] ?? null,
                        $lookup[87] ?? null,
                        $lookup[88] ?? null,
                        $lookup[89] ?? null,
                        $lookup[90] ?? null,
                        $lookup[91] ?? null
                    ])
                ],
                'kebijakan' => [
                    'kategori' => $lookup[92] ?? null,
                    'children' => array_filter([
                        $lookup[93] ?? null,
                        $lookup[94] ?? null,
                        $lookup[95] ?? null
                    ])
                ],
                'proses' => [
                    'kategori' => $lookup[96] ?? null,
                    'children' => array_filter([
                        $lookup[97] ?? null,
                        $lookup[98] ?? null
                    ])
                ],
                'pengendalian' => [
                    'kategori' => $lookup[99] ?? null,
                    'children' => array_filter([
                        $lookup[100] ?? null,
                        $lookup[101] ?? null
                    ])
                ]
            ],
            'nilai102' => $lookup[102] ?? null,
            'nilai103' => $lookup[103] ?? null
        ];

        return $structure;
    }

    private function buildLikuiditasInherenStructure($dataArray)
    {
        $lookup = [];
        foreach ($dataArray as $item) {
            $lookup[$item['faktor1id']] = $item;
        }

        $structure = [
            'nilai' => [ // ✅ BENAR
                'konsentrasi' => [ // ✅ BENAR
                    'kategori' => $lookup[105] ?? null,
                    'children' => array_filter([
                        $lookup[106] ?? null,
                        $lookup[107] ?? null,
                        $lookup[108] ?? null,
                        $lookup[109] ?? null,
                        $lookup[110] ?? null,
                    ])
                ],
                'kerentanan' => [
                    'kategori' => $lookup[111] ?? null,
                    'children' => array_filter([
                        $lookup[112] ?? null,
                        $lookup[113] ?? null
                    ])
                ],
                'lainnya' => $lookup[114] ?? null
            ],
            'nilai115' => $lookup[115] ?? null,
            'nilai116' => $lookup[116] ?? null
        ];

        return $structure;
    }

    private function buildLikuiditasKPMRStructure($dataArray)
    {
        $lookup = [];
        foreach ($dataArray as $item) {
            $lookup[$item['faktor1id']] = $item;
        }

        $structure = [
            'nilai' => [
                'pengawasan' => [
                    'kategori' => $lookup[118] ?? null,
                    'children' => array_filter([
                        $lookup[119] ?? null,
                        $lookup[120] ?? null,
                        $lookup[121] ?? null,
                        $lookup[122] ?? null,
                        $lookup[123] ?? null,
                        $lookup[124] ?? null,

                    ])
                ],
                'kebijakan' => [
                    'kategori' => $lookup[125] ?? null,
                    'children' => array_filter([
                        $lookup[126] ?? null,
                        $lookup[127] ?? null,
                        $lookup[128] ?? null
                    ])
                ],
                'proses' => [
                    'kategori' => $lookup[129] ?? null,
                    'children' => array_filter([
                        $lookup[130] ?? null,
                        $lookup[131] ?? null
                    ])
                ],
                'pengendalian' => [
                    'kategori' => $lookup[132] ?? null,
                    'children' => array_filter([
                        $lookup[133] ?? null,
                        $lookup[134] ?? null
                    ])
                ]
            ],
            'nilai102' => $lookup[135] ?? null,
            'nilai103' => $lookup[136] ?? null
        ];

        return $structure;
    }

    private function getDataKepatuhanInheren()
    {
        $kepatuhanModel = new \App\Models\M_risikokepatuhan();

        $nilaiData = $this->db->table('risikokepatuhan as r')
            ->select('r.*, u.fullname')
            ->join('users as u', 'u.id = r.user_id', 'left')
            ->where('r.kodebpr', $this->userKodebpr)
            ->where('r.periode_id', $this->periodeId)
            ->whereIn('r.faktor1id', range(73, 82))
            ->orderBy('r.faktor1id', 'ASC')
            ->get()
            ->getResultArray();

        return [
            'nilai' => $this->strukturDataNilaiKepatuhan($nilaiData),
            'nilai81' => $kepatuhanModel->where([
                'faktor1id' => 81,
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId
            ])->first(),
            'nilai82' => $kepatuhanModel->where([
                'faktor1id' => 82,
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId
            ])->first()
        ];
    }

    private function strukturDataNilaiKepatuhan($nilaiData)
    {
        $struktur = [
            'pelanggaran' => [
                'kategori' => null,
                'children' => []
            ],
            'hukum' => [
                'kategori' => null,
                'children' => []
            ],
            'lainnya' => null
        ];

        foreach ($nilaiData as $nilai) {
            $faktorId = $nilai['faktor1id'];

            if ($faktorId == 73) {
                $struktur['pelanggaran']['kategori'] = $nilai;
            } elseif (in_array($faktorId, [74, 75])) {
                $struktur['pelanggaran']['children'][] = $nilai;
            } elseif ($faktorId == 76) {
                $struktur['hukum']['kategori'] = $nilai;
            } elseif (in_array($faktorId, [77, 78, 79])) {
                $struktur['hukum']['children'][] = $nilai;
            } elseif ($faktorId == 80) {
                $struktur['lainnya'] = $nilai;
            }
        }

        return $struktur;
    }

    private function getDataKepatuhanKPMR()
    {
        $kepatuhanKPMRModel = new \App\Models\M_kepatuhankpmr();

        $nilaiData = $this->db->table('risikokepatuhan_kpmr as r')
            ->select('r.*, u.fullname')
            ->join('users as u', 'u.id = r.user_id', 'left')
            ->where('r.kodebpr', $this->userKodebpr)
            ->where('r.periode_id', $this->periodeId)
            ->whereIn('r.faktor1id', range(84, 103))
            ->orderBy('r.faktor1id', 'ASC')
            ->get()
            ->getResultArray();

        return [
            'nilai' => $this->strukturDataNilaiKepatuhanKPMR($nilaiData),
            'nilai102' => $kepatuhanKPMRModel->where([
                'faktor1id' => 102,
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId
            ])->first(),
            'nilai103' => $kepatuhanKPMRModel->where([
                'faktor1id' => 103,
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId
            ])->first()
        ];
    }

    private function strukturDataNilaiKepatuhanKPMR($nilaiData)
    {
        $struktur = [
            'pengawasan' => [
                'kategori' => null,
                'children' => []
            ],
            'kebijakan' => [  // ✅ Ganti dari 'kecukupan'
                'kategori' => null,
                'children' => []
            ],
            'proses' => [
                'kategori' => null,
                'children' => []
            ],
            'pengendalian' => [  // ✅ Ganti dari 'sistem'
                'kategori' => null,
                'children' => []
            ],
        ];

        foreach ($nilaiData as $nilai) {
            $faktorId = $nilai['faktor1id'];

            if ($faktorId == 84) {
                $struktur['pengawasan']['kategori'] = $nilai;
            } elseif (in_array($faktorId, [85, 86, 87, 88, 89, 90, 91])) {
                $struktur['pengawasan']['children'][] = $nilai;
            } elseif ($faktorId == 92) {
                $struktur['kebijakan']['kategori'] = $nilai;  // ✅ Ubah
            } elseif (in_array($faktorId, [93, 94, 95])) {
                $struktur['kebijakan']['children'][] = $nilai;  // ✅ Ubah
            } elseif ($faktorId == 96) {
                $struktur['proses']['kategori'] = $nilai;
            } elseif (in_array($faktorId, [97, 98])) {
                $struktur['proses']['children'][] = $nilai;
            } elseif ($faktorId == 99) {
                $struktur['pengendalian']['kategori'] = $nilai;  // ✅ Ubah
            } elseif (in_array($faktorId, [100, 101])) {
                $struktur['pengendalian']['children'][] = $nilai;  // ✅ Ubah
            }
        }

        return $struktur;
    }

    private function getDataLikuiditas()
    {
        $likuiditasModel = new \App\Models\M_likuiditasinheren();

        $nilaiData = $this->db->table('likuiditasinheren as r')
            ->select('r.*, u.fullname')
            ->join('users as u', 'u.id = r.user_id', 'left')
            ->where('r.kodebpr', $this->userKodebpr)
            ->where('r.periode_id', $this->periodeId)
            ->whereIn('r.faktor1id', range(105, 116))
            ->orderBy('r.faktor1id', 'ASC')
            ->get()
            ->getResultArray();

        return [
            'nilai' => $this->strukturDataNilaiLikuiditas($nilaiData),
            'nilai115' => $likuiditasModel->where([
                'faktor1id' => 115,
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId
            ])->first(),
            'nilai116' => $likuiditasModel->where([
                'faktor1id' => 116,
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId
            ])->first()
        ];
    }

    private function strukturDataNilaiLikuiditas($nilaiData)
    {
        $struktur = [
            'konsentrasi' => [
                'kategori' => null,
                'children' => []
            ],
            'kerentanan' => [
                'kategori' => null,
                'children' => []
            ],
            'lainnya' => null
        ];

        foreach ($nilaiData as $nilai) {
            $faktorId = $nilai['faktor1id'];

            if ($faktorId == 105) {
                $struktur['konsentrasi']['kategori'] = $nilai;
            } elseif (in_array($faktorId, [106, 107, 108, 109, 110])) {
                $struktur['konsentrasi']['children'][] = $nilai;
            } elseif ($faktorId == 111) {
                $struktur['kerentanan']['kategori'] = $nilai;
            } elseif (in_array($faktorId, [112, 113])) {
                $struktur['kerentanan']['children'][] = $nilai;
            } elseif ($faktorId == 114) {
                $struktur['lainnya'] = $nilai;
            }
        }

        return $struktur;
    }

    private function getDataLikuiditasKPMR()
    {
        $likuiditaskpmrModel = new \App\Models\M_likuiditaskpmr();

        $nilaiData = $this->db->table('likuiditaskpmr as r')
            ->select('r.*, u.fullname')
            ->join('users as u', 'u.id = r.user_id', 'left')
            ->where('r.kodebpr', $this->userKodebpr)
            ->where('r.periode_id', $this->periodeId)
            ->whereIn('r.faktor1id', range(118, 136))
            ->orderBy('r.faktor1id', 'ASC')
            ->get()
            ->getResultArray();

        return [
            'nilai' => $this->strukturDataNilaiLikuiditasKPMR($nilaiData),
            'nilai135' => $likuiditaskpmrModel->where([
                'faktor1id' => 135,
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId
            ])->first(),
            'nilai136' => $likuiditaskpmrModel->where([
                'faktor1id' => 136,
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId
            ])->first()
        ];
    }

    private function strukturDataNilaiLikuiditasKPMR($nilaiData)
    {
        $struktur = [
            'pengawasan' => [
                'kategori' => null,
                'children' => []
            ],
            'kebijakan' => [
                'kategori' => null,
                'children' => []
            ],
            'proses' => [
                'kategori' => null,
                'children' => []
            ],
            'pengendalian' => [
                'kategori' => null,
                'children' => []
            ],
        ];

        foreach ($nilaiData as $nilai) {
            $faktorId = $nilai['faktor1id'];

            if ($faktorId == 118) {
                $struktur['pengawasan']['kategori'] = $nilai;
            } elseif (in_array($faktorId, [119, 120, 121, 122, 123, 124])) {
                $struktur['pengawasan']['children'][] = $nilai;
            } elseif ($faktorId == 125) {
                $struktur['kebijakan']['kategori'] = $nilai;  // ✅ Ubah
            } elseif (in_array($faktorId, [126, 127, 128])) {
                $struktur['kebijakan']['children'][] = $nilai;  // ✅ Ubah
            } elseif ($faktorId == 129) {
                $struktur['proses']['kategori'] = $nilai;
            } elseif (in_array($faktorId, [130, 131])) {
                $struktur['proses']['children'][] = $nilai;
            } elseif ($faktorId == 132) {
                $struktur['pengendalian']['kategori'] = $nilai;  // ✅ Ubah
            } elseif (in_array($faktorId, [133, 134])) {
                $struktur['pengendalian']['children'][] = $nilai;  // ✅ Ubah
            }
        }

        return $struktur;
    }

    public function exportAllPDFToZipView()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;
        if ($redirect = $this->checkPeriode())
            return $redirect;

        $data = [
            'baseUrl' => base_url()
        ];

        return view('risikokredit/export_pdf', $data);
    }

    private function checkPeriode()
    {
        if (!$this->periodeId) {
            return redirect()->to('/Periodeprofilresiko')
                ->with('error', 'Silakan pilih periode aktif terlebih dahulu.');
        }
        return null;
    }



}