<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_faktor10;
use App\Models\M_user;
use App\Models\M_nilaifaktor10;
use App\Models\M_infobpr;
// use App\Models\M_periode;
use App\Models\M_periodesemesteran;
use App\Models\M_faktor10komentar;
use App\Models\M_commentreads10;
use App\Models\M_showfaktor;
use Myth\Auth\Config\Services as AuthServices;

class Faktor10 extends Controller
{
    protected $db;
    protected $auth;
    protected $faktor10Model;
    protected $userModel;
    protected $komentarModel;
    protected $nilai10Model;
    protected $infobprModel;
    protected $periodeModel;
    protected $session;
    protected $userKodebpr;
    protected $commentReads10Model;
    protected $showfaktorModel;

    protected $userInGroupPE;
    protected $userInGroupAdmin;
    protected $userInGroupDekom;
    protected $userInGroupDekom2;
    protected $userInGroupDekom3;
    protected $userInGroupDekom4;
    protected $userInGroupDekom5;
    protected $userInGroupDireksi;
    protected $userInGroupDireksi2;

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->db = \Config\Database::connect();
        $this->faktor10Model = new M_faktor10();
        $this->periodeModel = new M_periodesemesteran();
        $this->userModel = new M_user();
        $this->nilai10Model = new M_nilaifaktor10();
        $this->infobprModel = new M_infobpr();
        $this->komentarModel = new M_faktor10komentar();
        $this->commentReads10Model = new M_commentreads10();
        $this->showfaktorModel = new M_showfaktor();
        helper('url');
        $this->session = service('session');
        $this->auth = service('authentication');

        $userId = $this->auth->id();
        $user = $this->userModel->find($userId);
        $this->userKodebpr = $user['kodebpr'] ?? null;

        $authorize = AuthServices::authorization();

        $this->userInGroupPE = $authorize->inGroup('pe', $this->auth->id());
        $this->userInGroupAdmin = $authorize->inGroup('admin', $this->auth->id());
        $this->userInGroupDekom = $authorize->inGroup('dekom', $this->auth->id());
        $this->userInGroupDekom2 = $authorize->inGroup('dekom2', $this->auth->id());
        $this->userInGroupDekom3 = $authorize->inGroup('dekom3', $this->auth->id());
        $this->userInGroupDekom4 = $authorize->inGroup('dekom4', $this->auth->id());
        $this->userInGroupDekom5 = $authorize->inGroup('dekom5', $this->auth->id());
        $this->userInGroupDireksi = $authorize->inGroup('direksi', $this->auth->id());
        $this->userInGroupDireksi2 = $authorize->inGroup('direksi2', $this->auth->id());
    }

    public function index()
    {
        if (!$this->auth->check()) {
            return redirect()->to(session('redirect_url') ?? '/login');
        }

        if (!session('active_periode')) {
            return redirect()->to('/periode');
        }

        $periodeId = session('active_periode');
        $kodebpr = $this->userKodebpr;

        $canApprove = $this->checkAllFaktorsApproved($kodebpr, $periodeId);

        $accdekomApproved = $this->db->table('nilaifaktor10')
            ->select('COUNT(*) as total, SUM(CASE WHEN accdekom = 1 THEN 1 ELSE 0 END) as approved')
            ->whereIn('faktor10id', range(1, 5))
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->get()
            ->getRowArray();

        $accdekomApproved = $accdekomApproved['total'] > 0 &&
            $accdekomApproved['total'] == $accdekomApproved['approved'];

        $user = $this->userModel->find(user_id());
        $periodeDetail = $this->periodeModel->getPeriodeDetail($periodeId);
        $bprData = $this->infobprModel->getBprByKode($kodebpr);

        $prevSemLabel = '';
        $prevTahunLabel = '';
        if ($periodeDetail) {
            $cs = (int) ($periodeDetail['semester'] ?? 1);
            $ct = (int) ($periodeDetail['tahun'] ?? date('Y'));
            $prevSemLabel = $cs == 1 ? 2 : 1;
            $prevTahunLabel = $cs == 1 ? $ct - 1 : $ct;
        }

        $data = [
            'judul' => 'Faktor 10',
            'userId' => user_id(),
            'userInGroupPE' => $this->userInGroupPE,
            'userInGroupAdmin' => $this->userInGroupAdmin,
            'userInGroupDekom' => $this->userInGroupDekom,
            'userInGroupDekom2' => $this->userInGroupDekom2,
            'userInGroupDekom3' => $this->userInGroupDekom3,
            'userInGroupDekom4' => $this->userInGroupDekom4,
            'userInGroupDekom5' => $this->userInGroupDekom5,
            'userInGroupDireksi' => $this->userInGroupDireksi,
            'userInGroupDireksi2' => $this->userInGroupDireksi2,
            'fullname' => $user['fullname'] ?? 'Unknown',
            'kodebpr' => $kodebpr,
            'periodeId' => $periodeId,
            'periodeDetail' => $periodeDetail,
            'bprData' => $bprData,
            'canApprove' => $canApprove,
            'accdekomApproved' => $accdekomApproved,
            'prevSemLabel' => $prevSemLabel,
            'prevTahunLabel' => $prevTahunLabel
        ];

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('Laporansemester/Tatakelola/faktor10gcg', $data);
        echo view('templates/v_footer');
    }

    private function checkAllFaktorsApproved($kodebpr, $periodeId)
    {
        $result = $this->db->table('nilaifaktor10')
            ->select('COUNT(*) as total, SUM(CASE WHEN accdekom = 1 THEN 1 ELSE 0 END) as approved')
            ->whereIn('faktor10id', range(1, 6))
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->get()
            ->getRowArray();

        return $result['total'] > 0 && $result['total'] == $result['approved'];
    }

    public function getFactorsData()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error']);
        }

        try {
            $kodebpr = $this->userKodebpr;
            $periodeId = session('active_periode');

            if (!$kodebpr || !$periodeId) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Kodebpr atau periode tidak valid'
                ]);
            }

            $builder = $this->db->table('faktor10 as f');
            $builder->select('f.*, n.nilai, n.keterangan, n.id as nilai_id, n.accdekom, n.accdekom2, n.accdir2, n.is_approved, n.positifstruktur, n.negatifstruktur, n.positifproses, n.negatifproses, n.positifhasil, n.negatifhasil, n.accdekom_at, n.approved_at');
            $builder->join('nilaifaktor10 as n', 'n.faktor10id = f.id AND n.kodebpr = ' . $this->db->escape($kodebpr) . ' AND n.periode_id = ' . $this->db->escape($periodeId), 'left');
            $builder->where('f.id <=', 5);
            $builder->orderBy('f.id', 'ASC');

            $query = $builder->get();
            $faktorData = $query->getResultArray();

            $grouped = [
                'struktur' => [],
                'proses' => [],
                'hasil' => [],
                'kesimpulan' => null
            ];

            foreach ($faktorData as $item) {
                $category = strtolower($item['sph'] ?? '');
                if (in_array($category, ['struktur', 'proses', 'hasil'])) {
                    $grouped[$category][] = $item;
                }

                $kesimpulan = $this->db->table('nilaifaktor10')
                    ->where('faktor10id', 6)
                    ->where('kodebpr', $kodebpr)
                    ->where('periode_id', $periodeId)
                    ->get()
                    ->getRowArray();

                $grouped['kesimpulan'] = $kesimpulan;
            }

            $rataRata = $this->nilai10Model->hitungRataRata(1, $kodebpr, $periodeId);

            $penjelasanFromDB = $this->db->table('nilaifaktor10')
                ->select('penjelasfaktor')
                ->where('faktor10id', 6)
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->get()
                ->getRowArray();

            $penjelasan = $penjelasanFromDB['penjelasfaktor'] ?? $this->nilai10Model->getPenjelasanNilai($rataRata);

            return $this->response->setJSON([
                'status' => 'success',
                'data' => $grouped,
                'rataRata' => $rataRata,
                'penjelasan' => $penjelasan
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error in getFactorsData: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem'
            ]);
        }
    }

    public function getApprovalStatus()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error']);
        }

        try {
            $kodebpr = $this->userKodebpr;
            $periodeId = session('active_periode');

            if (!$kodebpr || !$periodeId) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Data tidak lengkap'
                ]);
            }

            $builder = $this->db->table('nilaifaktor10');
            $builder->select('accdekom, accdekom_at, is_approved, approved_at');
            $builder->whereIn('faktor10id', range(1, 6));
            $builder->where('kodebpr', $kodebpr);
            $builder->where('periode_id', $periodeId);

            $nilaiData = $builder->get()->getResultArray();

            if (empty($nilaiData)) {
                return $this->response->setJSON([
                    'status' => 'success',
                    'dekom_approved' => false,
                    'dekom_approved_at' => null,
                    'dir_approved' => false,
                    'dir_approved_at' => null,
                    'can_approve_dir' => false
                ]);
            }

            $dekomApproved = true;
            $dekomApprovedAt = null;
            $dirApproved = true;
            $dirApprovedAt = null;

            foreach ($nilaiData as $nilai) {
                if ($nilai['accdekom'] != 1) {
                    $dekomApproved = false;
                }
                if (!empty($nilai['accdekom_at']) && $nilai['accdekom_at'] !== '0000-00-00 00:00:00') {
                    $dekomApprovedAt = $nilai['accdekom_at'];
                }

                if ($nilai['is_approved'] != 1) {
                    $dirApproved = false;
                }
                if (!empty($nilai['approved_at']) && $nilai['approved_at'] !== '0000-00-00 00:00:00') {
                    $dirApprovedAt = $nilai['approved_at'];
                }
            }

            if (!$dekomApproved)
                $dekomApprovedAt = null;
            if (!$dirApproved)
                $dirApprovedAt = null;

            return $this->response->setJSON([
                'status' => 'success',
                'dekom_approved' => $dekomApproved,
                'dekom_approved_at' => $dekomApprovedAt,
                'dir_approved' => $dirApproved,
                'dir_approved_at' => $dirApprovedAt,
                'can_approve_dir' => $dekomApproved
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error in getApprovalStatus: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem'
            ]);
        }
    }

    public function getAllUnreadCounts()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error']);
        }

        try {
            $kodebpr = $this->userKodebpr;
            $userId = user_id();
            $periodeId = session('active_periode');

            if (!$kodebpr || !$userId || !$periodeId) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Data tidak lengkap',
                    'counts' => []
                ]);
            }

            $result = [];

            for ($faktor10Id = 1; $faktor10Id <= 5; $faktor10Id++) {
                $count = $this->commentReads10Model->countUnreadComments(
                    $kodebpr,
                    $userId,
                    $periodeId,
                    $faktor10Id
                );

                if ($count > 0) {
                    $result[$faktor10Id] = $count;
                }
            }

            // ✅ Tambahkan log untuk debug
            log_message('info', '[getAllUnreadCounts] Result: ' . json_encode($result));

            return $this->response->setJSON([
                'status' => 'success',
                'counts' => $result
            ]);

        } catch (\Exception $e) {
            log_message('error', '[getAllUnreadCounts] Error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem',
                'counts' => []
            ]);
        }
    }

    public function cekKomentarBaru()
    {
        $kodebpr = $this->request->getGet('kodebpr');
        $lastVisit = $this->request->getGet('last_visit');
        $periodeId = session('active_periode');

        $results = $this->komentarModel
            ->select('faktor10id, COUNT(*) as jumlah')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('created_at >', $lastVisit)
            ->groupBy('faktor10id')
            ->findAll();

        return $this->response->setJSON($results);
    }

    public function getKomentarByFaktorId($faktor10Id)
    {
        if (!$this->request->isAJAX() || !is_numeric($faktor10Id)) {
            return $this->response->setStatusCode(404)->setBody('Not Found');
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        $komentarList = $this->komentarModel->getKomentarByPeriode($kodebpr, $periodeId, $faktor10Id);

        return $this->response->setJSON($komentarList);
    }

    public function getKomentar()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(404)->setBody('Not Found');
        }

        $faktor10Id = $this->request->getGet('faktor10_id');
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        $komentarList = $this->komentarModel->getKomentarByPeriode(
            $kodebpr,
            $periodeId,
            $faktor10Id
        );

        return $this->response->setJSON($komentarList);
    }

    public function getNilaiByFaktorId($faktor10Id)
    {
        if (!$this->request->isAJAX() || !is_numeric($faktor10Id)) {
            return $this->response->setStatusCode(404)->setBody('Not Found');
        }

        $nilaiList = $this->nilai10Model->getNilaiByFaktorId($faktor10Id);
        return $this->response->setJSON($nilaiList);
    }

    public function tambahNilai()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        if (isset($_POST['tambahNilai'])) {
            $val = $this->validate([
                'nilai' => [
                    'label' => 'Nilai',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'keterangan' => [
                    'label' => 'Keterangan',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                return redirect()->back();
            } else {
                $periodeId = session('active_periode');
                $userId = service('authentication')->id();

                $userModel = new M_user();
                $user = $userModel->find($userId);
                $kodebpr = $user['kodebpr'] ?? null;

                if (!$kodebpr) {
                    return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
                }

                $faktor10Id = $this->request->getPost('faktor10_id');
                $data = [
                    'faktor10id' => $faktor10Id,
                    'nilai' => $this->request->getPost('nilai'),
                    'keterangan' => $this->request->getPost('keterangan'),
                    'fullname' => $this->request->getPost('fullname'),
                    'user_id' => $userId,
                    'kodebpr' => $kodebpr,
                    'periode_id' => $periodeId,
                    'accdekom' => 0,
                    'is_approved' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                ];

                $this->nilai10Model->tambahNilai($data, $faktor10Id, $kodebpr);

                $rataRata = $this->nilai10Model->hitungRataRata($faktor10Id, $kodebpr);
                $rataRata = ($rataRata - floor($rataRata) >= 0.5) ? ceil($rataRata) : floor($rataRata);

                $this->nilai10Model->insertOrUpdateRataRata($rataRata, 1, $kodebpr);

                session()->setFlashdata('message', 'Nilai berhasil ditambahkan');
                return redirect()->to(base_url('faktor10') . '?modal_nilai=' . $faktor10Id);
            }
        } else {
            return redirect()->to(base_url('faktor10'));
        }
    }


    public function tambahKomentar()
    {
        if (!$this->auth->check()) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(401)->setJSON([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ]);
            }
            return redirect()->to('/login');
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'komentar' => [
                'label' => 'Komentar',
                'rules' => 'required|min_length[3]|max_length[1000]',
                'errors' => [
                    'required' => 'Komentar tidak boleh kosong',
                    'min_length' => 'Komentar minimal 3 karakter',
                    'max_length' => 'Komentar maksimal 1000 karakter'
                ]
            ],
            'faktor10_id' => [
                'label' => 'Faktor ID',
                'rules' => 'required|numeric',
                'errors' => ['required' => 'Faktor ID tidak valid']
            ]
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            $errors = $validation->getErrors();

            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 'error',
                    'message' => implode(', ', $errors)
                ]);
            }

            session()->setFlashdata('err', implode('<br>', $errors));
            return redirect()->back();
        }

        $userId = user_id();
        $user = $this->userModel->find($userId);
        $kodebpr = $user['kodebpr'] ?? null;
        $periodeId = session('active_periode');

        if (!$kodebpr || !$periodeId) {
            $errorMsg = 'Kode BPR atau Periode tidak valid';

            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 'error',
                    'message' => $errorMsg
                ]);
            }

            session()->setFlashdata('error', $errorMsg);
            return redirect()->back();
        }

        $faktor10Id = $this->request->getPost('faktor10_id');
        $komentar = trim($this->request->getPost('komentar'));
        $fullname = $this->request->getPost('fullname');

        try {
            $data = [
                'faktor10id' => $faktor10Id,
                'komentar' => $komentar,
                'fullname' => $fullname,
                'user_id' => $userId,
                'kodebpr' => $kodebpr,
                'periode_id' => $periodeId,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $result = $this->komentarModel->insertKomentar($data);

            if ($result) {
                if ($this->request->isAJAX()) {
                    $komentarList = $this->komentarModel->getKomentarByPeriode(
                        $kodebpr,
                        $periodeId,
                        $faktor10Id
                    );

                    return $this->response->setJSON([
                        'status' => 'success',
                        'message' => 'Komentar berhasil ditambahkan',
                        'comments' => $komentarList
                    ]);
                }

                return redirect()->back()->with('message', 'Komentar berhasil ditambahkan');
            } else {
                throw new \Exception('Gagal menyimpan komentar');
            }

        } catch (\Exception $e) {
            log_message('error', 'Error tambahKomentar: ' . $e->getMessage());

            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Gagal menambahkan komentar: ' . $e->getMessage()
                ]);
            }

            return redirect()->back()->with('error', 'Gagal menambahkan komentar');
        }
    }

    public function ubah()
    {
        $faktor10id = $this->request->getPost('faktor10id');
        $userId = service('authentication')->id();
        $userModel = new M_user();
        $user = $userModel->find($userId);
        $kodebpr = $user['kodebpr'] ?? null;

        if (!$faktor10id) {
            session()->setFlashdata('err', 'ID Faktor tidak ditemukan.');
            return redirect()->to(base_url('faktor4'));
        }

        if (!$kodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
        }

        $periodeId = session('active_periode');
        if (!$periodeId) {
            return redirect()->back()->with('error', 'Periode tidak valid');
        }

        $data = [
            'nilai' => $this->request->getPost('nilai'),
            'keterangan' => $this->request->getPost('keterangan'),
            'is_approved' => 0,
            'accdekom' => 0,
            'user_id' => $userId,
            'kodebpr' => $kodebpr,
        ];

        if ($this->nilai10Model->ubahBerdasarkanFaktorId($data, $faktor10id, $kodebpr, $periodeId)) {
            $this->nilai10Model->where('faktor10id', 6)
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->set(['accdekom' => 0, 'is_approved' => 0])
                ->update();

            session()->setFlashdata('message', 'Data berhasil diubah');
        } else {
            session()->setFlashdata('err', 'Gagal mengubah data');
        }

        return redirect()->to(base_url('faktor10'));
    }

    public function ubahkesimpulan()
    {
        $faktor10Id = 6;
        $userId = service('authentication')->id();
        $userModel = new M_user();
        $user = $userModel->find($userId);
        $kodebpr = $user['kodebpr'] ?? null;

        if (!$kodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
        }

        $periodeId = session('active_periode');
        if (!$periodeId) {
            return redirect()->back()->with('error', 'Periode tidak valid');
        }

        $data = [
            'positifstruktur' => $this->request->getPost('positifstruktur'),
            'negatifstruktur' => $this->request->getPost('negatifstruktur'),
            'positifproses' => $this->request->getPost('positifproses'),
            'negatifproses' => $this->request->getPost('negatifproses'),
            'positifhasil' => $this->request->getPost('positifhasil'),
            'negatifhasil' => $this->request->getPost('negatifhasil'),
            'is_approved' => 0,
            'accdekom' => 0
        ];

        $existing = $this->nilai10Model
            ->where('faktor10id', $faktor10Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if ($existing) {
            $success = $this->nilai10Model->update($existing['id'], $data);
        } else {
            $data['faktor10id'] = $faktor10Id;
            $data['kodebpr'] = $kodebpr;
            $data['periode_id'] = $periodeId;
            $data['user_id'] = $userId;
            $data['fullname'] = $user['fullname'] ?? 'Unknown';
            $data['created_at'] = date('Y-m-d H:i:s');
            $success = $this->nilai10Model->insert($data);
        }

        if ($success) {
            session()->setFlashdata('message', 'Data berhasil diubah');
        } else {
            session()->setFlashdata('err', 'Gagal mengubah data');
        }

        return redirect()->to(base_url('faktor10'));
    }

    public function approve($idNilai = null)
    {
        if ($idNilai === null) {
            session()->setFlashdata('err', 'ID Faktor tidak valid.');
            return redirect()->back();
        }

        $nilaiFaktor10 = $this->nilai10Model->find($idNilai);
        if (!$nilaiFaktor10) {
            session()->setFlashdata('err', 'Data tidak ditemukan.');
            return redirect()->back();
        }

        $userId = service('authentication')->id();

        $dataUpdate = [
            'is_approved' => 1,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->nilai10Model->update($idNilai, $dataUpdate)) {
            session()->setFlashdata('message', 'Data berhasil disetujui.');
            return redirect()->back();
        } else {
            session()->setFlashdata('err', 'Terjadi kesalahan saat melakukan approval.');
            return redirect()->back();
        }
    }

    public function unapprove($idNilai)
    {
        if (!is_numeric($idNilai) || $idNilai <= 0) {
            session()->setFlashdata('err', 'ID Faktor tidak valid.');
            return redirect()->back();
        }

        $nilaiFaktor10 = $this->nilai10Model->find($idNilai);
        if (!$nilaiFaktor10) {
            session()->setFlashdata('err', 'Data tidak ditemukan.');
            return redirect()->back();
        }

        $userId = service('authentication')->id();

        $dataUpdate = [
            'is_approved' => 0,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s')
        ];

        if ($this->nilai10Model->update($idNilai, $dataUpdate)) {
            session()->setFlashdata('message', 'Data approval dibatalkan.');
            return redirect()->back();
        } else {
            session()->setFlashdata('err', 'Terjadi kesalahan saat membatalkan approval.');
            return redirect()->back();
        }
    }

    public function approveSemua()
    {
        $this->db->transStart();

        try {
            $userId = service('authentication')->id();
            $kodebpr = $this->userKodebpr;
            $periodeId = session('active_periode');

            if (!$kodebpr || !$periodeId) {
                $this->db->transRollback();
                session()->setFlashdata('err', 'Kode BPR atau Periode ID tidak valid');
                return redirect()->back();
            }

            $dataUpdate = [
                'is_approved' => 1,
                'approved_by' => $userId,
                'approved_at' => date('Y-m-d H:i:s'),
            ];

            $this->db->table('nilaifaktor10')
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->update($dataUpdate);

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                session()->setFlashdata('err', 'Gagal mengupdate data approval');
                return redirect()->back();
            }

            session()->setFlashdata('message', 'Semua faktor berhasil disetujui.');
            return redirect()->back();

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Error in approveSemua: ' . $e->getMessage());
            session()->setFlashdata('err', 'Terjadi kesalahan sistem: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function unapproveSemua()
    {
        $this->db->transStart();

        try {
            $userId = service('authentication')->id();
            $kodebpr = $this->userKodebpr;
            $periodeId = session('active_periode');

            if (!$kodebpr || !$periodeId) {
                $this->db->transRollback();
                session()->setFlashdata('err', 'Kode BPR atau Periode ID tidak valid');
                return redirect()->back();
            }

            $dataUpdate = [
                'is_approved' => 0,
                'approved_by' => $userId,
                'approved_at' => date('Y-m-d H:i:s'),
            ];

            $this->db->table('nilaifaktor10')
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->update($dataUpdate);

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                session()->setFlashdata('err', 'Gagal mengupdate data approval');
                return redirect()->back();
            }

            session()->setFlashdata('message', 'Semua approval berhasil dibatalkan.');
            return redirect()->back();

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Error in unapproveSemua: ' . $e->getMessage());
            session()->setFlashdata('err', 'Terjadi kesalahan sistem: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function approveSemuaKom()
    {
        $userId = service('authentication')->id();
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        if (!$kodebpr || !$periodeId) {
            session()->setFlashdata('err', 'Kode BPR atau Periode ID tidak valid');
            return redirect()->back();
        }

        $count = $this->nilai10Model
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->countAllResults();

        if ($count === 0) {
            session()->setFlashdata('err', 'Tidak ada data yang bisa diupdate untuk periode ini');
            return redirect()->back();
        }

        $dataUpdate = [
            'accdekom' => 1,
            'accdekom_by' => $userId,
            'accdekom_at' => date('Y-m-d H:i:s'),
        ];

        try {
            $updated = $this->nilai10Model
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->update(null, $dataUpdate);

            if (!$updated) {
                session()->setFlashdata('err', 'Gagal mengupdate data approval');
                return redirect()->back();
            }

            $rataRata = $this->nilai10Model->hitungRataRata(1, $kodebpr, $periodeId);
            $this->nilai10Model->insertOrUpdateRataRata($rataRata, 1, $kodebpr, $periodeId);

            session()->setFlashdata('message', 'Semua faktor10 berhasil disetujui.');
            return redirect()->back();

        } catch (\Exception $e) {
            log_message('error', 'Error in approveSemua: ' . $e->getMessage());
            session()->setFlashdata('err', 'Terjadi kesalahan sistem: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function unapprovekom()
    {
        $userId = service('authentication')->id();
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        if (!$kodebpr || !$periodeId) {
            session()->setFlashdata('err', 'Kode BPR atau Periode ID tidak valid');
            return redirect()->back();
        }

        $dataUpdate = [
            'accdekom' => 0,
            'accdekom_by' => $userId,
            'accdekom_at' => date('Y-m-d H:i:s'),
            'is_approved' => 0,
        ];

        try {
            $updated = $this->nilai10Model
                ->where('faktor10id', 6)
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->update(null, $dataUpdate);

            if (!$updated) {
                session()->setFlashdata('err', 'Gagal mengupdate data approval untuk faktor10');
                return redirect()->back();
            }

            session()->setFlashdata('message', 'Faktor berhasil di-unapprove.');
            return redirect()->back();

        } catch (\Exception $e) {
            log_message('error', 'Error in unapproveSemuaKom: ' . $e->getMessage());
            session()->setFlashdata('err', 'Terjadi kesalahan sistem: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function accdekom()
    {
        if ($this->request->getMethod() !== 'post') {
            return $this->response->setStatusCode(405, 'Method Not Allowed');
        }

        $faktor10id = $this->request->getPost('faktor10id');
        $kodebpr = $this->request->getPost('kodebpr');
        $periodeId = $this->request->getPost('periode_id');

        if (!$faktor10id || !$kodebpr || !$periodeId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak lengkap']);
        }

        $nilaiFaktor10 = $this->nilai10Model
            ->where('faktor10id', $faktor10id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if (!$nilaiFaktor10) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak ditemukan']);
        }

        $dataUpdateForFaktor10 = [
            'accdekom' => 0,
            'is_approved' => 0,
            'accdekom_by' => service('authentication')->id(),
            'accdekom_at' => date('Y-m-d H:i:s'),
        ];

        $this->nilai10Model->where('faktor10id', 6)->update(null, $dataUpdateForFaktor10);

        $this->nilai10Model->where('faktor10id', 6)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->update(null, $dataUpdateForFaktor10);


        $dataUpdate = [
            'accdekom' => 1,
            'accdekom_by' => service('authentication')->id(),
            'accdekom_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->nilai10Model->update($nilaiFaktor10['id'], $dataUpdate)) {
            return redirect()->to('/faktor10')->with('message', 'Faktor berhasil disetujui');
        } else {
            return redirect()->to('/faktor10')->with('error', 'Gagal memperbarui data');
        }

    }

    public function unapproveddekom()
    {
        if ($this->request->getMethod() !== 'post') {
            return $this->response->setStatusCode(405, 'Method Not Allowed');
        }

        $faktor10id = $this->request->getPost('faktor10id');
        $kodebpr = $this->request->getPost('kodebpr');
        $periodeId = $this->request->getPost('periode_id');

        if (!$faktor10id || !$kodebpr || !$periodeId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak lengkap']);
        }

        $nilaiFaktor10 = $this->nilai10Model
            ->where('faktor10id', $faktor10id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if (!$nilaiFaktor10) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak ditemukan']);
        }

        $dataUpdate = [
            'accdekom' => 0,
            'accdekom_by' => service('authentication')->id(),
            'accdekom_at' => date('Y-m-d H:i:s'),
            'is_approved' => 0
        ];

        if ($this->nilai10Model->update($nilaiFaktor10['id'], $dataUpdate)) {
            $this->nilai10Model->where('faktor10id', 6)
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->set(['accdekom' => 0])
                ->set(['is_approved' => 0])
                ->update();

            return redirect()->to('/faktor10')->with('message', 'Faktor belum disetujui dekom');
        } else {
            return redirect()->to('/faktor10')->with('error', 'Gagal memperbarui data');
        }

    }

    public function accdekom2()
    {
        if ($this->request->getMethod() !== 'post') {
            return $this->response->setStatusCode(405, 'Method Not Allowed');
        }

        $faktor10id = $this->request->getPost('faktor10id');
        $kodebpr = $this->request->getPost('kodebpr');
        $periodeId = $this->request->getPost('periode_id');

        if (!$faktor10id || !$kodebpr || !$periodeId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak lengkap']);
        }

        $nilaiFaktor10 = $this->nilai10Model
            ->where('faktor10id', $faktor10id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if (!$nilaiFaktor10) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak ditemukan']);
        }

        $dataUpdate = [
            'accdekom2' => 1,
            'accdekom2_by' => service('authentication')->id(),
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->nilai10Model->update($nilaiFaktor10['id'], $dataUpdate)) {
            return redirect()->to('/faktor10')->with('message', 'Faktor berhasil disetujui');
        } else {
            return redirect()->to('/faktor10')->with('error', 'Gagal memperbarui data');
        }

    }

    public function unapproveddekom2()
    {
        if ($this->request->getMethod() !== 'post') {
            return $this->response->setStatusCode(405, 'Method Not Allowed');
        }

        $faktor10id = $this->request->getPost('faktor10id');
        $kodebpr = $this->request->getPost('kodebpr');
        $periodeId = $this->request->getPost('periode_id');

        if (!$faktor10id || !$kodebpr || !$periodeId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak lengkap']);
        }

        $nilaiFaktor10 = $this->nilai10Model
            ->where('faktor10id', $faktor10id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if (!$nilaiFaktor10) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak ditemukan']);
        }

        $dataUpdate = [
            'accdekom2' => 0,
            'accdekom2_by' => service('authentication')->id(),
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->nilai10Model->update($nilaiFaktor10['id'], $dataUpdate)) {
            return redirect()->to('/faktor10')->with('message', 'Faktor belum disetujui dekom');
        } else {
            return redirect()->to('/faktor10')->with('error', 'Gagal memperbarui data');
        }

    }

    public function accdir2()
    {
        if ($this->request->getMethod() !== 'post') {
            return $this->response->setStatusCode(405, 'Method Not Allowed');
        }

        $faktor10id = $this->request->getPost('faktor10id');
        $kodebpr = $this->request->getPost('kodebpr');
        $periodeId = $this->request->getPost('periode_id');

        if (!$faktor10id || !$kodebpr || !$periodeId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak lengkap']);
        }

        $nilaiFaktor10 = $this->nilai10Model
            ->where('faktor10id', $faktor10id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if (!$nilaiFaktor10) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak ditemukan']);
        }

        $dataUpdate = [
            'accdir2' => 1,
            'accdir2_by' => service('authentication')->id(),
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->nilai10Model->update($nilaiFaktor10['id'], $dataUpdate)) {
            return redirect()->to('/faktor10')->with('message', 'Faktor berhasil disetujui');
        } else {
            return redirect()->to('/faktor10')->with('error', 'Gagal memperbarui data');
        }

    }

    public function unapproveddir2()
    {
        if ($this->request->getMethod() !== 'post') {
            return $this->response->setStatusCode(405, 'Method Not Allowed');
        }

        $faktor10id = $this->request->getPost('faktor10id');
        $kodebpr = $this->request->getPost('kodebpr');
        $periodeId = $this->request->getPost('periode_id');

        if (!$faktor10id || !$kodebpr || !$periodeId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak lengkap']);
        }

        $nilaiFaktor10 = $this->nilai10Model
            ->where('faktor10id', $faktor10id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if (!$nilaiFaktor10) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak ditemukan']);
        }

        $dataUpdate = [
            'accdir2' => 0,
            'accdir2_by' => service('authentication')->id(),
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->nilai10Model->update($nilaiFaktor10['id'], $dataUpdate)) {
            return redirect()->to('/faktor10')->with('message', 'Faktor belum disetujui dekom');
        } else {
            return redirect()->to('/faktor10')->with('error', 'Gagal memperbarui data');
        }

    }

    public function checkAccDekomApproval()
    {
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        $allApproved = true;
        for ($faktor10Id = 1; $faktor10Id <= 6; $faktor10Id++) {
            $nilaiFaktor10 = $this->nilai10Model
                ->where('faktor10id', $faktor10Id)
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->first();

            if (!$nilaiFaktor10 || $nilaiFaktor10['accdekom'] != 1) {
                $allApproved = false;
                break;
            }
        }

        return $allApproved;
    }

    public function getUnreadCount()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => 'error',
                'message' => 'Forbidden'
            ]);
        }

        $faktor10Id = $this->request->getGet('faktor10_id');
        $kodebpr = $this->userKodebpr;
        $userId = user_id();
        $periodeId = session('active_periode');

        $count = $this->commentReads10Model->countUnreadComments(
            $kodebpr,
            $userId,
            $periodeId,
            $faktor10Id
        );

        return $this->response->setJSON([
            'status' => 'success',
            'count' => $count
        ]);
    }

    public function getUnreadCommentCountForFactor()
    {
        return $this->getUnreadCount();
    }

    public function markUserCommentsAsRead()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $faktor10Id = $this->request->getPost('faktor10_id');
        $kodebpr = $this->userKodebpr;
        $userId = user_id();
        $periodeId = session('active_periode');

        if (!$faktor10Id || !$kodebpr || !$userId || !$periodeId) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Missing data.']);
        }

        $commentsToMark = $this->komentarModel->select('id')
            ->where('faktor10id', $faktor10Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('user_id !=', $userId)
            ->findAll();

        if (!empty($commentsToMark)) {
            foreach ($commentsToMark as $comment) {
                $this->commentReads10Model->markAsRead($comment['id'], $userId);
            }
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Comments marked as read for this user.']);
    }

    public function saveKomentar()
    {
        $data = [
            'faktor10id' => $this->request->getPost('faktor10_id'),
            'kodebpr' => $this->request->getPost('kodebpr'),
            'komentar' => $this->request->getPost('komentar'),
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'user_id' => session()->get('user_id')
        ];

        $this->komentarModel->insert($data);
        return $this->response->setJSON(['status' => 'comment_saved']);
    }

    function syncFromPrevPeriode()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error']);
        }

        try {
            $kodebpr = $this->userKodebpr;
            $periodeId = session('active_periode');

            if (!$kodebpr || !$periodeId) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Kode BPR atau periode tidak valid.'
                ]);
            }

            $currentPeriode = $this->periodeModel->find($periodeId);
            if (!$currentPeriode) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Periode aktif tidak ditemukan.'
                ]);
            }

            $cs = (int) $currentPeriode['semester'];
            $ct = (int) $currentPeriode['tahun'];

            $prevSemester = $cs == 1 ? 2 : 1;
            $prevTahun = $cs == 1 ? $ct - 1 : $ct;

            $prevPeriode = $this->periodeModel
                ->where('kodebpr', $kodebpr)
                ->where('semester', $prevSemester)
                ->where('tahun', $prevTahun)
                ->first();

            if (!$prevPeriode) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => "Data periode Semester {$prevSemester} Tahun {$prevTahun} tidak ditemukan."
                ]);
            }

            $prevData = $this->db->table('nilaifaktor10')
                ->whereIn('faktor10id', range(1, 6))
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $prevPeriode['id'])
                ->get()
                ->getResultArray();

            if (empty($prevData)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => "Tidak ada data pada periode Semester {$prevSemester} Tahun {$prevTahun}."
                ]);
            }

            $userId = $this->auth->id();
            $now = date('Y-m-d H:i:s');
            $synced = 0;

            foreach ($prevData as $row) {
                $existing = $this->db->table('nilaifaktor10')
                    ->where('faktor10id', $row['faktor10id'])
                    ->where('kodebpr', $kodebpr)
                    ->where('periode_id', $periodeId)
                    ->get()
                    ->getRowArray();

                $insertData = [
                    'faktor10id' => $row['faktor10id'],
                    'nilai' => $row['nilai'],
                    'keterangan' => $row['keterangan'],
                    'fullname' => $row['fullname'] ?? null,
                    'positifstruktur' => $row['positifstruktur'] ?? null,
                    'negatifstruktur' => $row['negatifstruktur'] ?? null,
                    'positifproses' => $row['positifproses'] ?? null,
                    'negatifproses' => $row['negatifproses'] ?? null,
                    'positifhasil' => $row['positifhasil'] ?? null,
                    'negatifhasil' => $row['negatifhasil'] ?? null,
                    'kodebpr' => $kodebpr,
                    'periode_id' => $periodeId,
                    'user_id' => $userId,
                    // Reset semua approval
                    'accdekom' => 0,
                    'accdekom2' => 0,
                    'accdir2' => 0,
                    'is_approved' => 0
                ];

                if ($row['faktor10id'] == 6) {
                    $insertData['nfaktor10'] = $row['nfaktor10'] ?? null;
                    $insertData['penjelasfaktor'] = $row['penjelasfaktor'] ?? null;
                }

                if ($existing) {
                    $this->db->table('nilaifaktor10')
                        ->where('id', $existing['id'])
                        ->update($insertData);
                } else {
                    $insertData['created_at'] = $now;
                    $this->db->table('nilaifaktor10')->insert($insertData);
                }

                $synced++;
            }

            // Hitung ulang rata-rata setelah sync
            $rataRata = $this->nilai10Model->hitungRataRata(1, $kodebpr, $periodeId);
            $this->nilai10Model->insertOrUpdateRataRata($rataRata, 1, $kodebpr, $periodeId);

            return $this->response->setJSON([
                'success' => true,
                'message' => "Berhasil menyinkronkan {$synced} data dari Semester {$prevSemester} Tahun {$prevTahun}. Semua approval direset dan perlu disetujui ulang.",
                'synced' => $synced,
                'from' => "Semester {$prevSemester} Tahun {$prevTahun}"
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Faktor10 syncFromPrevPeriode error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ]);
        }
    }

    public function exporttxtfaktor10()
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
        $faktor = $this->showfaktorModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        // Periode detail
        $periodeDetail = $this->periodeModel->find($periodeId);

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
        $data_faktor = $this->nilai10Model
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
        function sanitizeTxtfaktor10($text)
        {
            // Replace newlines with space
            $text = str_replace(["\r", "\n"], ' ', $text);

            // Replace special characters
            $text = str_replace(
                [
                    "\u{201C}",
                    "\u{201D}", // " "
                    "\u{2018}",
                    "\u{2019}", // ' '
                    '%',
                    '|'
                ],
                [
                    '"',
                    '"',
                    "'",
                    "'",
                    ' persen ',
                    ' '
                ],
                $text
            );

            // Remove non-printable ASCII characters
            $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);

            // Trim and remove multiple spaces
            return trim(preg_replace('/\s+/', ' ', $text));
        }

        $kodeMap = [
            1 => 'S1001',
            2 => 'P1001',
            3 => 'P1002',
            4 => 'H1001',
            5 => 'H1002'
        ];

        // Index data by faktor10id
        $indexedData = [];
        foreach ($data_faktor as $row) {
            if (isset($row['faktor10id'])) {
                $indexedData[$row['faktor10id']] = $row;
            }
        }

        $output = '';

        // HEADER
        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|1010|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";

        // Baris kosong S1000
        $output .= "D01|S1000|\r\n";

        // Struktur (S0801)
        $faktor10Id = 1;
        $kode = $kodeMap[$faktor10Id];
        if (isset($indexedData[$faktor10Id])) {
            $row = $indexedData[$faktor10Id];
            $keterangan = sanitizeTxtfaktor10($row['keterangan'] ?? '-');
        } else {
            $keterangan = '';
        }
        $output .= "D01|{$kode}|{$keterangan}\r\n";

        // Baris kosong P1000
        $output .= "D01|P1000|\r\n";

        // Proses (P1001 - P1002)
        for ($faktor10Id = 2; $faktor10Id <= 3; $faktor10Id++) {
            $kode = $kodeMap[$faktor10Id];
            if (isset($indexedData[$faktor10Id])) {
                $row = $indexedData[$faktor10Id];
                $keterangan = sanitizeTxtfaktor10($row['keterangan'] ?? '-');
            } else {
                $keterangan = '';
            }
            $output .= "D01|{$kode}|{$keterangan}\r\n";
        }

        // Baris kosong H1000
        $output .= "D01|H1000|\r\n";

        // Hasil (H1001 - H1002)
        for ($faktor10Id = 4; $faktor10Id <= 5; $faktor10Id++) {
            $kode = $kodeMap[$faktor10Id];
            if (isset($indexedData[$faktor10Id])) {
                $row = $indexedData[$faktor10Id];
                $keterangan = sanitizeTxtfaktor10($row['keterangan'] ?? '-');
            } else {
                $keterangan = '';
            }
            $output .= "D01|{$kode}|{$keterangan}\r\n";
        }

        $kesimpulanData = null;
        foreach ($indexedData as $row) {
            if (isset($row['positifstruktur']) && !empty($row['positifstruktur'])) {
                $kesimpulanData = $row;
                break;
            }
        }

        // Data kesimpulan (F1001 - F1007)
        $positifstruktur = sanitizeTxtfaktor10($kesimpulanData['positifstruktur'] ?? '');
        $negatifstruktur = sanitizeTxtfaktor10($kesimpulanData['negatifstruktur'] ?? '');
        $positifproses = sanitizeTxtfaktor10($kesimpulanData['positifproses'] ?? '');
        $negatifproses = sanitizeTxtfaktor10($kesimpulanData['negatifproses'] ?? '');
        $positifhasil = sanitizeTxtfaktor10($kesimpulanData['positifhasil'] ?? '');
        $negatifhasil = sanitizeTxtfaktor10($kesimpulanData['negatifhasil'] ?? '');

        // Hitung rata-rata atau ambil dari data
        $rataRata = $this->nilai10Model->hitungRataRata(1, $kodebpr, $periodeId);
        $nfaktor = round($rataRata, 2);

        $output .= "D01|F1001|{$positifstruktur}\r\n";
        $output .= "D01|F1002|{$negatifstruktur}\r\n";
        $output .= "D01|F1003|{$positifproses}\r\n";
        $output .= "D01|F1004|{$negatifproses}\r\n";
        $output .= "D01|F1005|{$positifhasil}\r\n";
        $output .= "D01|F1006|{$negatifhasil}\r\n";
        $output .= "D01|F1007|{$nfaktor}\r\n";

        // Filename
        $filename = "TKSBPRKS-1010-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

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
