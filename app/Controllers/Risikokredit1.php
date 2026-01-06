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
use Myth\Auth\Config\Services as AuthServices;

class Risikokredit extends Controller
{
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

    // Cache untuk user groups
    protected $userGroups = [];

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');

        $this->paramprofilrisikoModel = new M_paramprofilrisiko();
        $this->periodeModel = new M_periodeprofilresiko();
        $this->userModel = new M_user();
        $this->komentarModel = new M_profilrisikocomments();
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
        // Quick validation checks
        if ($redirect = $this->checkAuth())
            return $redirect;
        if ($redirect = $this->validatePeriode())
            return $redirect;

        if (!$this->userKodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
        }

        $periodeDetail = $this->periodeModel->getPeriodeDetail($this->periodeId);
        $bprData = $this->infobprModel->getBprByKode($this->userKodebpr);

        $nilaiData = $this->nilaiModel
            ->where('periode_id', $this->periodeId)
            ->where('kodebpr', $this->userKodebpr)
            ->findAll();

        $nilaiLookup = [];
        foreach ($nilaiData as $nilai) {
            $nilaiLookup[$nilai['faktor1id']] = $nilai;
        }

        $unreadCounts = $this->getBatchUnreadCountsInternal();
        $canApprove = $this->checkCanApprove($nilaiLookup);
        $accdekomApproved = $this->checkAccdekomApproved($nilaiLookup);
        $allApproved = $this->checkAllApproved($nilaiLookup);
        $penilaianKreditInherenConfig = $this->getpenilaianKreditInherenConfig();

        // Get komentar list
        $komentarList = $this->komentarModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->findAll();

        // Build factors with details
        $faktorData = $this->paramprofilrisikoModel->getAllData();
        $factorsWithDetails = $this->buildFactorsWithDetailsOptimized($faktorData, $nilaiLookup);

        // Set last visit
        $lastVisit = session('last_visit_komentar') ?? date('Y-m-d H:i:s', strtotime('-1 day'));
        session()->set('last_visit_komentar', date('Y-m-d H:i:s'));

        // Get user data
        $user = $this->userModel->find($this->userId);

        $kalkulatorData = $this->getKalkulatorDataOptimized();

        $data = [
            'judul' => 'Penilaian Risiko Kredit Inheren',
            'faktor' => $faktorData,
            'userId' => $this->userId,
            'faktors' => $factorsWithDetails,
            'komentarList' => $komentarList,
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
            'allApproved' => $allApproved,
            'kodebpr' => $this->userKodebpr,
            'nilaiData' => $nilaiData,
            'komentarModel' => $this->komentarModel,
            'commentReadsModel' => $this->commentReadsModel,
            'lastVisit' => $lastVisit,
            'periodeId' => $this->periodeId,
            'periodeDetail' => $periodeDetail,
            'bprData' => $bprData,
            'canApprove' => $canApprove,
            'accdekomApproved' => $accdekomApproved,
            'periode' => $this->periodeModel->find($this->periodeId),
            'penilaianKreditInherenConfig' => $penilaianKreditInherenConfig,
            'kalkulatorData' => $kalkulatorData,
            'unreadCounts' => $unreadCounts,
        ];

        return view('templates/v_header', $data)
            . view('templates/v_sidebar')
            . view('templates/v_topbar')
            . view('Risikokredit/index', $data)
            . view('templates/v_footer');
    }

    private function getBatchUnreadCountsInternal()
    {
        $db = \Config\Database::connect();

        $builder = $db->table('profilrisiko_reads cr');
        $builder->select('cr.comment_id, pc.faktor1id');
        $builder->join('profilrisiko_comments pc', 'pc.id = cr.comment_id');
        $builder->where('pc.kodebpr', $this->userKodebpr);
        $builder->where('pc.periode_id', $this->periodeId);
        $builder->where('pc.subkategori', 'KREDITINHEREN');
        $builder->where('cr.user_id', $this->userId);
        // $builder->where('cr.is_read', 0);

        $results = $builder->get()->getResultArray();

        // Count by faktor
        $counts = [];
        foreach ($results as $row) {
            $faktorId = $row['faktor1id'];
            if (!isset($counts[$faktorId])) {
                $counts[$faktorId] = 0;
            }
            $counts[$faktorId]++;
        }

        return $counts;
    }

    private function buildFactorsWithDetailsOptimized($faktorData, $nilaiLookup)
    {
        $factorsWithDetails = [];

        foreach ($faktorData as $faktorItem) {
            $faktorId = $faktorItem['id'];
            $associatedNilai = $nilaiLookup[$faktorId] ?? null;

            $rataRata = $associatedNilai['nfaktor'] ?? 0;

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

    private function getKalkulatorDataOptimized()
    {
        $kalkulatorData = $this->kalkulatorModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->first();

        if (!$kalkulatorData) {
            return [
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

        return $kalkulatorData;
    }

    public function getBatchUnreadCounts()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'Forbidden'
            ]);
        }

        $faktorIds = $this->request->getPost('faktor_ids');
        $kodebpr = $this->request->getPost('kodebpr');
        $periodeId = $this->request->getPost('periode_id');

        if (!$faktorIds || !is_array($faktorIds)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid data'
            ]);
        }

        $db = \Config\Database::connect();

        // SATU query untuk SEMUA faktor
        $builder = $db->table('profilrisiko_reads cr');
        $builder->select('pc.faktor1id, COUNT(*) as unread_count');
        $builder->join('profilrisiko_comments pc', 'pc.id = cr.comment_id');
        $builder->whereIn('pc.faktor1id', $faktorIds);
        $builder->where('pc.kodebpr', $kodebpr);
        $builder->where('pc.periode_id', $periodeId);
        $builder->where('pc.subkategori', 'KREDITINHEREN');
        $builder->where('cr.user_id', $this->userId);
        // $builder->where('cr.is_read', 0);
        $builder->groupBy('pc.faktor1id');

        $results = $builder->get()->getResultArray();

        // Convert to associative array
        $counts = [];
        foreach ($results as $row) {
            $counts[$row['faktor1id']] = (int) $row['unread_count'];
        }

        // Set 0 for factors with no unread
        foreach ($faktorIds as $id) {
            if (!isset($counts[$id])) {
                $counts[$id] = 0;
            }
        }

        return $this->response->setJSON([
            'success' => true,
            'counts' => $counts
        ]);
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
        $faktor1id = $this->request->getPost('faktor1id');

        if (!$faktor1id) {
            return redirect()->to(base_url('Risikokredit'))
                ->with('err', 'ID Faktor tidak ditemukan.');
        }

        if (!$this->userKodebpr || !$this->periodeId) {
            return redirect()->back()->with('error', 'Data user atau periode tidak valid');
        }

        $data = [
            'penilaiankredit' => $this->request->getPost('penilaiankredit'),
            'penjelasanpenilaian' => $this->request->getPost('penjelasanpenilaian'),
            'keterangan' => $this->request->getPost('keterangan'),
            'user_id' => $this->userId,
            'kodebpr' => $this->userKodebpr,
        ];

        if ($this->nilaiModel->ubahBerdasarkanFaktorId($data, $faktor1id, $this->userKodebpr, $this->periodeId)) {
            // Reset approval for faktor 13
            $this->nilaiModel
                ->where('faktor1id', 13)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->set(['accdir2' => 0, 'is_approved' => 0])
                ->update();

            $this->updateRataRata($faktor1id);

            return redirect()->to(base_url('Risikokredit'))
                ->with('message', 'Data berhasil diubah');
        }

        return redirect()->to(base_url('Risikokredit'))
            ->with('err', 'Gagal mengubah data');
    }

    public function tambahNilai()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;

        if (!isset($_POST['tambahNilai'])) {
            return redirect()->to(base_url('Risikokredit'));
        }

        $validation = $this->validate([
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
            'penilaiankredit' => $this->request->getPost('penilaiankredit'),
            'penjelasanpenilaian' => $this->request->getPost('penjelasanpenilaian'),
            'keterangan' => $this->request->getPost('keterangan'),
            'fullname' => $user['fullname'] ?? 'Unknown',
            'user_id' => $this->userId,
            'kodebpr' => $this->userKodebpr,
            'periode_id' => $this->periodeId,
            'is_approved' => 0,
            'accdir2' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $this->nilaiModel->tambahNilai($data, $faktorId, $this->userKodebpr);

        $this->updateRataRata($faktorId);

        return redirect()->to(base_url('Risikokredit') . '?modal_nilai=' . $faktorId)
            ->with('message', 'Nilai berhasil ditambahkan');
    }

    private function updateRataRata($faktorId)
    {
        $rataRata = $this->nilaiModel->hitungRataRata($faktorId, $this->userKodebpr, $this->periodeId);
        $rataRata = ($rataRata - floor($rataRata) >= 0.5) ? ceil($rataRata) : floor($rataRata);
        $this->nilaiModel->insertOrUpdateRataRata($rataRata, $faktorId, $this->userKodebpr, $this->periodeId);
    }

    private function checkCanApprove($nilaiLookup)
    {
        $requiredFaktorIds = range(1, 12);
        foreach ($requiredFaktorIds as $faktorId) {
            if (empty($nilaiLookup[$faktorId])) {
                return false;
            }
        }
        return true;
    }

    private function checkAccdekomApproved($nilaiLookup)
    {
        for ($faktorId = 1; $faktorId <= 12; $faktorId++) {
            if (!isset($nilaiLookup[$faktorId]) || $nilaiLookup[$faktorId]['is_approved'] != 1) {
                return false;
            }
        }
        return true;
    }

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

    private function getpenilaianKreditInherenConfig()
    {
        return \Config\penilaianKreditInherenConfig::get();
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
        if ($redirect = $this->checkAuth())
            return $redirect;

        if (!isset($_POST['tambahKomentar'])) {
            return redirect()->to(base_url('Risikokredit'));
        }

        if (!$this->userKodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
        }

        $validation = $this->validate([
            'komentar' => 'required',
        ]);

        if (!$validation) {
            return redirect()->back()->with('err', $this->validator->listErrors());
        }

        $user = $this->userModel->find($this->userId);
        $faktorId = $this->request->getPost('faktor_id');

        $data = [
            'subkategori' => "KREDITINHEREN",
            'faktor1id' => $faktorId,
            'komentar' => $this->request->getPost('komentar'),
            'fullname' => $user['fullname'] ?? 'Unknown',
            'user_id' => $this->userId,
            'kodebpr' => $this->userKodebpr,
            'periode_id' => $this->periodeId,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $this->komentarModel->insertKomentar($data, $faktorId);

        return redirect()->to(base_url('Risikokredit') . '?modal_komentar=' . $faktorId)
            ->with('message', 'Komentar berhasil ditambahkan');
    }

    public function simpanKalkulator()
    {
        // Cek autentikasi
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }

        // Cek apakah request dari form
        if (!isset($_POST['simpanKalkulator'])) {
            return redirect()->to(base_url('Risikokredit'));
        }

        // Validasi user kodebpr
        if (!$this->userKodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
        }

        // Validasi periode
        if (!$this->periodeId) {
            return redirect()->back()->with('error', 'Periode tidak valid');
        }

        // Validasi input
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

        // Ambil data input dari form
        $aba = $this->request->getPost('aba');
        $kydbank = $this->request->getPost('kydbank');
        $kydpihak3 = $this->request->getPost('kydpihak3');
        $totalaset = $this->request->getPost('totalaset');
        $total25debitur = $this->request->getPost('total25debitur');
        $perdagangan = $this->request->getPost('perdagangan');
        $jasa = $this->request->getPost('jasa');
        $konsumsirumah = $this->request->getPost('konsumsirumah');

        // Hitung nilai-nilai turunan (sesuai rumus kalkulator)
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
        // Cek autentikasi
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }

        // Cek apakah request dari form
        if (!$this->request->isAJAX() && !isset($_POST['insertRasio'])) {
            return redirect()->to(base_url('Risikokredit'));
        }

        // Validasi user kodebpr
        if (!$this->userKodebpr) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'User tidak memiliki kode BPR yang valid'
                ]);
            }
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
        }

        // Validasi periode
        if (!$this->periodeId) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Periode tidak valid'
                ]);
            }
            return redirect()->back()->with('error', 'Periode tidak valid');
        }

        // Ambil data rasio dari request
        $rasioasetproduktif = $this->request->getPost('rasioasetproduktif');
        $rasiokreditdiberikan = $this->request->getPost('rasiokreditdiberikan');
        $rasio25debitur = $this->request->getPost('rasio25debitur');
        $rasioekonomi = $this->request->getPost('rasioekonomi');

        // Validasi input
        if (
            empty($rasioasetproduktif) && empty($rasiokreditdiberikan) &&
            empty($rasio25debitur) && empty($rasioekonomi)
        ) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Tidak ada data rasio untuk dimasukkan. Silakan hitung terlebih dahulu.'
                ]);
            }
            return redirect()->back()->with('error', 'Tidak ada data rasio untuk dimasukkan');
        }

        // Mapping rasio ke faktor1id
        $rasioMapping = [
            2 => $rasioasetproduktif,      // Rasio Aset Produktif
            3 => $rasiokreditdiberikan,    // Rasio Kredit Diberikan
            4 => $rasio25debitur,          // Rasio 25 Debitur Terbesar
            5 => $rasioekonomi,            // Rasio Sektor Ekonomi
        ];

        $user = $this->userModel->find($this->userId);
        $successCount = 0;
        $errorMessages = [];

        try {
            foreach ($rasioMapping as $faktorId => $rasioValue) {
                // Skip jika nilai kosong
                if (empty($rasioValue)) {
                    continue;
                }

                // Cek apakah data sudah ada
                $existingData = $this->nilaiModel
                    ->where('faktor1id', $faktorId)
                    ->where('kodebpr', $this->userKodebpr)
                    ->where('periode_id', $this->periodeId)
                    ->first();

                if ($existingData) {
                    // Update data yang sudah ada
                    $updateData = [
                        'rasiokredit' => $rasioValue,
                        'user_id' => $this->userId,
                        'fullname' => $user['fullname'] ?? 'Unknown',
                    ];

                    $result = $this->nilaiModel
                        ->where('faktor1id', $faktorId)
                        ->where('kodebpr', $this->userKodebpr)
                        ->where('periode_id', $this->periodeId)
                        ->set($updateData)
                        ->update();

                    if ($result) {
                        $successCount++;
                    } else {
                        $errorMessages[] = "Gagal update rasio untuk Faktor ID {$faktorId}";
                    }
                } else {
                    // Insert data baru (jika belum ada data sama sekali)
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
                    } else {
                        $errorMessages[] = "Gagal insert rasio untuk Faktor ID {$faktorId}";
                    }
                }

                // Update rata-rata setelah insert/update
                if ($result) {
                    $rataRata = $this->nilaiModel->hitungRataRata($faktorId, $this->userKodebpr, $this->periodeId);
                    $rataRata = ($rataRata - floor($rataRata) >= 0.5) ? ceil($rataRata) : floor($rataRata);
                    $this->nilaiModel->insertOrUpdateRataRata($rataRata, $faktorId, $this->userKodebpr, $this->periodeId);
                }
            }

            // Response
            if ($successCount > 0) {
                $message = "Berhasil memasukkan {$successCount} rasio ke kertas kerja";

                if ($this->request->isAJAX()) {
                    return $this->response->setJSON([
                        'success' => true,
                        'message' => $message,
                        'count' => $successCount
                    ]);
                }

                return redirect()->to(base_url('Risikokredit'))
                    ->with('message', $message);
            } else {
                throw new \Exception('Tidak ada rasio yang berhasil dimasukkan');
            }

        } catch (\Exception $e) {
            log_message('error', 'Error insertRasioToKertasKerja: ' . $e->getMessage());

            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                    'errors' => $errorMessages
                ]);
            }

            return redirect()->back()
                ->with('err', 'Terjadi kesalahan: ' . $e->getMessage());
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

        $kydkoleknpl = $kydnpl3 + $kydnpl4;
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
        log_message('info', 'POST data: ' . json_encode($this->request->getPost()));

        if (!$this->userKodebpr) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'User tidak memiliki kode BPR yang valid'
            ]);
        }

        if (!$this->periodeId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Periode tidak valid'
            ]);
        }

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

        $rasioMapping = [
            7 => $rasioasetproduktifbermasalah,
            8 => $rasiokreditbermasalah,
            9 => $rasiokreditkualitasrendah,
        ];

        $user = $this->userModel->find($this->userId);
        $successCount = 0;
        $errorMessages = [];

        try {
            foreach ($rasioMapping as $faktorId => $rasioValue) {
                if (empty($rasioValue)) {
                    continue;
                }

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
                    ];

                    $result = $this->nilaiModel
                        ->where('faktor1id', $faktorId)
                        ->where('kodebpr', $this->userKodebpr)
                        ->where('periode_id', $this->periodeId)
                        ->set($updateData)
                        ->update();

                    if ($result) {
                        $successCount++;
                    } else {
                        $errorMessages[] = "Gagal update rasio untuk Faktor ID {$faktorId}";
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
                    } else {
                        $errorMessages[] = "Gagal insert rasio untuk Faktor ID {$faktorId}";
                    }
                }

                if ($result) {
                    $rataRata = $this->nilaiModel->hitungRataRata($faktorId, $this->userKodebpr, $this->periodeId);
                    $rataRata = ($rataRata - floor($rataRata) >= 0.5) ? ceil($rataRata) : floor($rataRata);
                    $this->nilaiModel->insertOrUpdateRataRata($rataRata, $faktorId, $this->userKodebpr, $this->periodeId);
                }
            }

            if ($successCount > 0) {
                $message = "Berhasil memasukkan {$successCount} rasio kualitas aset ke kertas kerja";

                log_message('info', 'Success: ' . $message);

                return $this->response->setJSON([
                    'success' => true,
                    'message' => $message,
                    'count' => $successCount
                ]);
            } else {
                throw new \Exception('Tidak ada rasio yang berhasil dimasukkan');
            }

        } catch (\Exception $e) {
            log_message('error', 'Error insertRasioKualitasAsetToKertasKerja: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'errors' => $errorMessages
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

        $nilaiFaktor = $this->nilaiModel->find($idNilai);
        if (!$nilaiFaktor) {
            return redirect()->back()->with('err', 'Data tidak ditemukan.');
        }

        $dataUpdate = [
            'is_approved' => 1,
            'approved_by' => $this->userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->nilaiModel->update($idNilai, $dataUpdate)) {
            return redirect()->back()->with('message', 'Data berhasil disetujui.');
        }

        return redirect()->back()->with('err', 'Terjadi kesalahan saat melakukan approval.');
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

        $count = $this->nilaiModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->countAllResults();

        if ($count === 0) {
            return redirect()->back()->with('err', 'Tidak ada data yang bisa diupdate untuk periode ini');
        }

        $dataUpdate = [
            'is_approved' => 1,
            'approved_by' => $this->userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        try {
            $this->nilaiModel
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->update(null, $dataUpdate);

            // Recalculate average
            $rataRata = $this->nilaiModel->hitungRataRata(1, $this->userKodebpr, $this->periodeId);
            $this->nilaiModel->insertOrUpdateRataRata($rataRata, 1, $this->userKodebpr, $this->periodeId);

            return redirect()->back()->with('message', 'Semua faktor berhasil disetujui.');
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

        $count = $this->nilaiModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->countAllResults();

        if ($count === 0) {
            return redirect()->back()->with('err', 'Tidak ada data yang bisa diupdate untuk periode ini');
        }

        $dataUpdate = [
            'is_approved' => 2,
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

            return redirect()->back()->with('message', 'Semua faktor berhasil dibatalkan persetujuannya.');
        } catch (\Exception $e) {
            log_message('error', 'Error in unapproveSemua: ' . $e->getMessage());
            return redirect()->back()->with('err', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    public function accdir2()
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
            'accdir2' => 1,
            'accdir2_by' => $this->userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->nilaiModel->update($nilaiFaktor['id'], $dataUpdate)) {
            return redirect()->to('/Risikokredit')->with('message', 'Faktor berhasil disetujui');
        }

        return redirect()->to('/Risikokredit')->with('error', 'Gagal memperbarui data');
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

        $commentsToMark = $this->komentarModel->select('id')
            ->where('faktor1id', $faktorId)
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('user_id !=', $this->userId)
            ->findAll();

        if (!empty($commentsToMark)) {
            foreach ($commentsToMark as $comment) {
                $this->commentReadsModel->markAsRead($comment['id'], $this->userId);
            }
        }

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Comments marked as read for this user.'
        ]);
    }

    public function saveKomentar()
    {
        $data = [
            'faktor1id' => $this->request->getPost('faktor_id'),
            'kodebpr' => $this->request->getPost('kodebpr'),
            'komentar' => $this->request->getPost('komentar'),
            'created_at' => date('Y-m-d H:i:s'),
            'user_id' => $this->userId
        ];

        $this->komentarModel->insert($data);
        return $this->response->setJSON(['status' => 'comment_saved']);
    }

    public function exporttxtrisikokredit()
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
        $periodeDetail = $this->periodeModel->find($this->periodeId);
        $modalinti = $periodeDetail['modalinti'];
        $totalaset = $periodeDetail['totalaset'];
        $kantorcabang = $periodeDetail['kantorcabang'];
        $atmdebit = $periodeDetail['atmdebit'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];
        $kategori = $periodeDetail['kategori'];
        $exportDate = date('Y-m-d');
        $titleDate = date('Ymd');

        // Fetch data
        $data_risikokredit = $this->nilaiModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        // BPR Info
        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'] ?? '';
            $kodejenis = $infobpr['kodejenis'] ?? '';
        }

        // Mapping kode per faktor1id
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

        $output = "";

        // Header
        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|PRBPRKS|0101|{$modalinti}|{$totalaset}|{$kantorcabang}|{$atmdebit}|{$kategori}||\r\n";

        usort($data_risikokredit, function ($a, $b) {
            return ($a['faktor1id'] ?? 0) <=> ($b['faktor1id'] ?? 0);
        });

        // Data rows
        foreach ($data_risikokredit as $row) {
            $faktorId = $row['faktor1id'] ?? null;
            $kode = $kodeMap[$faktorId] ?? ''; // ambil kode sesuai faktor

            $penilaiankredit = str_replace(["\r", "\n"], ' ', $row['penilaiankredit']);
            $keterangan = str_replace(["\r", "\n"], ' ', $row['keterangan']);
            $rasio = $row['rasiokredit'] ?? ''; // optional

            $output .= "D01|{$kode}|{$rasio}|{$penilaiankredit}|{$keterangan}\r\n";
        }

        // Filename
        $filename = "PRBPRKS-0101-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        // Response
        $response = service('response');
        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response->setBody($output);
    }

}