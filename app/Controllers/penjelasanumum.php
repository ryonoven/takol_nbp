<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_penjelasanumum;
use App\Models\M_infobpr;
use App\Models\M_user;
use App\Models\M_periodetransparansi;
use App\Models\M_transparansicomments;
use App\Models\M_transparansicommentsread;
use Myth\Auth\Config\Services as AuthServices;

class penjelasanumum extends Controller
{
    protected $auth;
    protected $penjelasanModel;
    protected $userModel;
    protected $komentarModel;
    protected $infobprModel;
    protected $periodeModel;
    protected $session;
    protected $userKodebpr;
    protected $commentReadsModel;

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
        $this->penjelasanModel = new M_penjelasanumum();
        $this->periodeModel = new M_periodetransparansi();
        $this->userModel = new M_user();
        $this->komentarModel = new M_transparansicomments();
        $this->infobprModel = new M_infobpr();
        $this->commentReadsModel = new M_transparansicommentsread();
        helper('url');
        $this->session = service('session');
        $this->auth = service('authentication');

        $userId = $this->auth->id();
        $user = $this->userModel->find($userId);
        $this->userKodebpr = $user['kodebpr'] ?? null;

        $auth = AuthServices::authentication();
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
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        if (!session('active_periode')) {
            return redirect()->to('/Periodetransparansi');
        }

        $user = $this->userModel->find(user_id());
        $periodeId = session('active_periode');
        $periodeDetail = $this->periodeModel->getPeriodeDetail($periodeId);
        $kodebpr = $this->userKodebpr;
        $bprData = $this->infobprModel->getBprByKode($this->userKodebpr);

        $penjelasanData = $this->penjelasanModel
            ->where('periode_id', $periodeId)
            ->where('kodebpr', $kodebpr)
            ->findAll();  // This should work if the model is properly set up

        $data['periodetransparansi'] = $this->periodeModel->find($periodeId);

        // Mengambil data accdekom, accdekom_by, accdekom_at
        $accdekomData = $this->penjelasanModel
            ->select('accdekom, accdekom_by, accdekom_at, komut')
            ->where('periode_id', $periodeId)
            ->where('kodebpr', $kodebpr)
            ->findAll();

        $accdirutData = $this->penjelasanModel
            ->select('is_approved, approved_by, approved_at, dirut')
            ->where('periode_id', $periodeId)
            ->where('kodebpr', $kodebpr)
            ->findAll();

        $komentarList = $this->komentarModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->findAll();

        $fullname = $user['fullname'] ?? 'Unknown';
        $kodebpr = $user['kodebpr'] ?? null;

        if (!$kodebpr) {
            // Handle if user does not have a valid kodebpr
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
        }

        // Prepare data for the view
        $lastVisit = session('last_visit_komentar') ?? date('Y-m-d H:i:s', strtotime('-1 day'));
        session()->set('last_visit_komentar', date('Y-m-d H:i:s'));

        $canApprove = true;

        // Ambil semua data dengan kondisi yang sesuai
        $accdekomValues = $this->penjelasanModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->findAll();  // Mengambil semua data yang sesuai

        // Loop melalui setiap data
        foreach ($accdekomValues as $accdekomValue) {
            if ($accdekomValue['accdekom'] != 1) {
                // Jika ada data yang accdekom tidak 1, set canApprove ke false
                $canApprove = false;
                break;  // Tidak perlu melanjutkan jika sudah ditemukan yang tidak valid
            }
        }

        // Prepare the data array for the view
        $data = [
            'judul' => '1. Penjelasan Umum',
            'penjelasanumum' => $penjelasanData,
            'userId' => user_id(),
            'komentarList' => $komentarList,
            'userInGroupPE' => $this->userInGroupPE,
            'userInGroupAdmin' => $this->userInGroupAdmin,
            'userInGroupDekom' => $this->userInGroupDekom,
            'userInGroupDekom2' => $this->userInGroupDekom2,
            'userInGroupDekom3' => $this->userInGroupDekom3,
            'userInGroupDekom4' => $this->userInGroupDekom4,
            'userInGroupDekom5' => $this->userInGroupDekom5,
            'userInGroupDireksi' => $this->userInGroupDireksi,
            'userInGroupDireksi2' => $this->userInGroupDireksi2,
            'fullname' => $fullname,
            'kodebpr' => $this->userKodebpr,
            'komentarModel' => $this->komentarModel,
            'commentReadsModel' => $this->commentReadsModel,
            'lastVisit' => $lastVisit,
            'periodeId' => $periodeId,
            'periodeDetail' => $periodeDetail,
            'bprData' => $bprData,
            'accdekomData' => $accdekomData,
            'accdirutData' => $accdirutData,
            'canApprove' => $canApprove,
        ];

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('penjelasanumum/index', $data);
        echo view('templates/v_footer');
    }

    public function tambahpenjelasAjax()
    {
        if (!$this->auth->check()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        if ($this->request->getMethod() === 'post') {
            // Validate the incoming data
            $validation = \Config\Services::validation();
            $val = $this->validate([
                'namabpr' => 'required',
                'alamat' => 'required',
                'nomor' => 'required',
                'penjelasan' => 'required',
                'peringkatkomposit' => 'required',
                'penjelasankomposit' => 'required',
            ]);

            if (!$val) {
                return $this->response->setJSON(['status' => 'error', 'message' => $validation->listErrors()]);
            } else {
                // Prepare the data to be inserted
                $userId = $this->auth->id();
                $kodebpr = $this->userKodebpr;
                $periodeId = session('active_periode');
                $existingData = $this->penjelasanModel->where(['kodebpr' => $kodebpr, 'periode_id' => $periodeId])->first();

                $data = [
                    'namabpr' => $this->request->getPost('namabpr'),
                    'alamat' => $this->request->getPost('alamat'),
                    'nomor' => $this->request->getPost('nomor'),
                    'penjelasan' => $this->request->getPost('penjelasan'),
                    'peringkatkomposit' => $this->request->getPost('peringkatkomposit'),
                    'penjelasankomposit' => $this->request->getPost('penjelasankomposit'),
                    'periode_id' => $periodeId,
                    'user_id' => $userId,
                    'kodebpr' => $kodebpr,
                    'fullname' => $this->userModel->find($userId)['fullname'],
                    'accdekom' => 0,
                    'is_approved' => 0,
                ];

                if ($existingData) {
                    $data['id'] = $existingData['id'];
                    $this->penjelasanModel->save($data); // Update existing data
                    return $this->response->setJSON(['status' => 'success', 'message' => 'Data Penjelasan Umum berhasil diubah']);
                } else {
                    $this->penjelasanModel->save($data); // Insert new data
                    return $this->response->setJSON(['status' => 'success', 'message' => 'Data Penjelasan Umum berhasil ditambahkan']);
                }
            }
        }

        return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Invalid request']);
    }


    public function getUnreadCommentCountForFactor()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $Id = $this->request->getGet('id');
        $kodebpr = $this->userKodebpr;
        $userId = user_id();
        $periodeId = session('active_periode');

        if (!$Id || !$kodebpr || !$userId || !$periodeId) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Missing data.']);
        }

        $count = $this->commentReadsModel->countUnreadCommentsForUserByFactor($Id, $kodebpr, $userId, $periodeId);

        return $this->response->setJSON(['unread_count' => $count]);
    }

    public function cekKomentarBaru()
    {
        $subkategori = 'Penjelasanumum';
        $kodebpr = $this->request->getGet('kodebpr');
        $lastVisit = $this->request->getGet('last_visit');
        $periodeId = session('active_periode');

        $results = $this->komentarModel
            ->select('id, COUNT(*) as jumlah')
            ->where('subkategori', $subkategori)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('created_at >', $lastVisit)
            ->groupBy('id')
            ->findAll();

        return $this->response->setJSON($results);
    }

    public function getKomentarByFaktorId()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(404)->setBody('Not Found');
        }
        $subkategori = 'Penjelasanumum';
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        $komentarList = $this->komentarModel->getKomentarByFaktorId($subkategori, $kodebpr, $periodeId);

        return $this->response->setJSON($komentarList);
    }

    public function Tambahkomentar()
    {
        date_default_timezone_set('Asia/Jakarta');
        if (!$this->auth->check()) {
            return redirect()->to('/login');
        }

        if (isset($_POST['TambahKomentar'])) {
            $userId = service('authentication')->id();
            $user = $this->userModel->find($userId);
            $kodebpr = $user['kodebpr'] ?? null;

            if (!$kodebpr) {
                session()->setFlashdata('error', 'User tidak memiliki kode BPR yang valid');
                return redirect()->back();
            }

            $val = $this->validate([
                'komentar' => [
                    'label' => 'Komentar',
                    'rules' => 'required',
                    'errors' => ['required' => '{field} tidak boleh kosong.']
                ],
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                return redirect()->back();
            }

            $data = [
                'id' => $this->request->getPost('id'),
                'subkategori' => 'Penjelasanumum',
                'komentar' => $this->request->getPost('komentar'),
                'fullname' => $this->request->getPost('fullname'),
                'user_id' => $userId,
                'kodebpr' => $kodebpr,
                'periode_id' => session('active_periode'), // Pastikan ini diisi
                'created_at' => date('Y-m-d H:i:s')
            ];

            $this->komentarModel->insertKomentar($data);
            session()->setFlashdata('message', 'Komentar berhasil ditambahkan');
            return redirect()->to(base_url('Penjelasanumum') . '?modal_komentar=' . $this->request->getPost('id'));
        }

        return redirect()->to(base_url('Penjelasanumum'));
    }

    public function markUserCommentsAsRead()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $Id = $this->request->getPost('id');
        $kodebpr = $this->userKodebpr; // Get from property
        $userId = user_id();
        $periodeId = session('active_periode');

        if (!$Id || !$kodebpr || !$userId || !$periodeId) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Missing data.']);
        }

        // Get all comment IDs for this factor, kodebpr, periode, and not by the current user
        $commentsToMark = $this->komentarModel->select('id')
            ->where('subkategori', $Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('user_id !=', $userId) // Mark comments from others as read
            ->findAll();

        if (!empty($commentsToMark)) {
            foreach ($commentsToMark as $comment) {
                $this->commentReadsModel->markAsRead($comment['id'], $userId);
            }
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Comments marked as read for this user.']);
    }

    public function saveKomentar()
    {
        $data = [
            'id' => $this->request->getPost('id'),
            'kodebpr' => $this->request->getPost('kodebpr'),
            'komentar' => $this->request->getPost('komentar'),
            'is_read' => 0, // <--- Ensure this is set to 0 for new comments
            'created_at' => date('Y-m-d H:i:s'),
            'user_id' => session()->get('user_id')
        ];

        $this->komentarModel->insert($data);
        return $this->response->setJSON(['status' => 'comment_saved']);
    }

    public function approveSemuaKom()
    {
        date_default_timezone_set('Asia/Jakarta');
        $userId = service('authentication')->id();  // Mendapatkan ID pengguna
        $user = $this->userModel->find($userId);    // Ambil data pengguna berdasarkan ID
        $komut = $user['fullname'] ?? 'Unknown';    // Ambil fullname pengguna, jika tidak ada, tampilkan 'Unknown'
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        // Validasi data penting
        if (!$kodebpr || !$periodeId) {
            session()->setFlashdata('err', 'Kode BPR atau Periode ID tidak valid');
            return redirect()->back();
        }

        // Cek dulu apakah ada data yang akan diupdate
        $count = $this->penjelasanModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->countAllResults();

        if ($count === 0) {
            session()->setFlashdata('err', 'Tidak ada data yang bisa diupdate untuk periode ini');
            return redirect()->back();
        }

        // Data untuk diupdate
        $dataUpdate = [
            'accdekom' => 1,                // Status disetujui
            'accdekom_by' => $userId,       // Menyimpan siapa yang memberikan approval
            'accdekom_at' => date('Y-m-d H:i:s'),  // Waktu persetujuan
            'komut' => $komut,              // Menyimpan nama yang memberikan persetujuan
        ];

        try {
            // Lakukan update pada tabel penjelasanumum berdasarkan kodebpr dan periode_id
            $updated = $this->penjelasanModel
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->update(null, $dataUpdate);  // Parameter pertama null untuk update semua yang sesuai kriteria

            if (!$updated) {
                session()->setFlashdata('err', 'Gagal mengupdate data approval');
                return redirect()->back();
            }

            session()->setFlashdata('message', 'Penjelasan umum disetujui.');
            return redirect()->back();

        } catch (\Exception $e) {
            // Menangani kesalahan dan mencatat log error
            log_message('error', 'Error in approveSemuaKom: ' . $e->getMessage());
            session()->setFlashdata('err', 'Terjadi kesalahan sistem: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function unapproveSemuaKom()
    {
        date_default_timezone_set('Asia/Jakarta');
        $userId = service('authentication')->id();  // Mendapatkan ID pengguna
        $user = $this->userModel->find($userId);    // Ambil data pengguna berdasarkan ID
        $komut = $user['fullname'] ?? 'Unknown';    // Ambil fullname pengguna, jika tidak ada, tampilkan 'Unknown'
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        // Validasi data penting
        if (!$kodebpr || !$periodeId) {
            session()->setFlashdata('err', 'Kode BPR atau Periode ID tidak valid');
            return redirect()->back();
        }

        // Cek dulu apakah ada data yang akan diupdate
        $count = $this->penjelasanModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->countAllResults();

        if ($count === 0) {
            session()->setFlashdata('err', 'Tidak ada data yang bisa diupdate untuk periode ini');
            return redirect()->back();
        }

        // Data untuk diupdate
        $dataUpdate = [
            'accdekom' => 0,                // Status disetujui
            'accdekom_by' => $userId,       // Menyimpan siapa yang memberikan approval
            'accdekom_at' => date('Y-m-d H:i:s'),  // Waktu persetujuan
            'komut' => $komut,              // Menyimpan nama yang memberikan persetujuan
        ];

        try {
            // Lakukan update pada tabel penjelasanumum berdasarkan kodebpr dan periode_id
            $updated = $this->penjelasanModel
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->update(null, $dataUpdate);  // Parameter pertama null untuk update semua yang sesuai kriteria

            if (!$updated) {
                session()->setFlashdata('err', 'Gagal mengupdate data approval');
                return redirect()->back();
            }

            session()->setFlashdata('message', 'Penjelasan umum disetujui.');
            return redirect()->back();

        } catch (\Exception $e) {
            // Menangani kesalahan dan mencatat log error
            log_message('error', 'Error in approveSemuaKom: ' . $e->getMessage());
            session()->setFlashdata('err', 'Terjadi kesalahan sistem: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function approveSemuaDirut()
    {
        date_default_timezone_set('Asia/Jakarta');
        $userId = service('authentication')->id();  // Mendapatkan ID pengguna
        $user = $this->userModel->find($userId);    // Ambil data pengguna berdasarkan ID
        $dirut = $user['fullname'] ?? 'Unknown';    // Ambil fullname pengguna, jika tidak ada, tampilkan 'Unknown'
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        // Validasi data penting
        if (!$kodebpr || !$periodeId) {
            session()->setFlashdata('err', 'Kode BPR atau Periode ID tidak valid');
            return redirect()->back();
        }

        // Cek dulu apakah ada data yang akan diupdate
        $count = $this->penjelasanModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->countAllResults();

        if ($count === 0) {
            session()->setFlashdata('err', 'Tidak ada data yang bisa diupdate untuk periode ini');
            return redirect()->back();
        }

        // Data untuk diupdate
        $dataUpdate = [
            'is_approved' => 1,                // Status disetujui
            'approved_by' => $userId,       // Menyimpan siapa yang memberikan approval
            'approved_at' => date('Y-m-d H:i:s'),  // Waktu persetujuan
            'dirut' => $dirut,              // Menyimpan nama yang memberikan persetujuan
        ];

        try {
            // Lakukan update pada tabel penjelasanumum berdasarkan kodebpr dan periode_id
            $updated = $this->penjelasanModel
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->update(null, $dataUpdate);  // Parameter pertama null untuk update semua yang sesuai kriteria

            if (!$updated) {
                session()->setFlashdata('err', 'Gagal mengupdate data approval');
                return redirect()->back();
            }

            session()->setFlashdata('message', 'Penjelasan umum disetujui.');
            return redirect()->back();

        } catch (\Exception $e) {
            // Menangani kesalahan dan mencatat log error
            log_message('error', 'Error in approveSemuaKom: ' . $e->getMessage());
            session()->setFlashdata('err', 'Terjadi kesalahan sistem: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function unapproveSemuaDirut()
    {
        date_default_timezone_set('Asia/Jakarta');
        $userId = service('authentication')->id();  // Mendapatkan ID pengguna
        $user = $this->userModel->find($userId);    // Ambil data pengguna berdasarkan ID
        $dirut = $user['fullname'] ?? 'Unknown';    // Ambil fullname pengguna, jika tidak ada, tampilkan 'Unknown'
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        // Validasi data penting
        if (!$kodebpr || !$periodeId) {
            session()->setFlashdata('err', 'Kode BPR atau Periode ID tidak valid');
            return redirect()->back();
        }

        // Cek dulu apakah ada data yang akan diupdate
        $count = $this->penjelasanModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->countAllResults();

        if ($count === 0) {
            session()->setFlashdata('err', 'Tidak ada data yang bisa diupdate untuk periode ini');
            return redirect()->back();
        }

        // Data untuk diupdate
        $dataUpdate = [
            'is_approved' => 0,                // Status disetujui
            'approved_by' => $userId,       // Menyimpan siapa yang memberikan approval
            'approved_at' => date('Y-m-d H:i:s'),  // Waktu persetujuan
            'dirut' => $dirut,              // Menyimpan nama yang memberikan persetujuan
        ];

        try {
            // Lakukan update pada tabel penjelasanumum berdasarkan kodebpr dan periode_id
            $updated = $this->penjelasanModel
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->update(null, $dataUpdate);  // Parameter pertama null untuk update semua yang sesuai kriteria

            if (!$updated) {
                session()->setFlashdata('err', 'Gagal mengupdate data approval');
                return redirect()->back();
            }

            session()->setFlashdata('message', 'Penjelasan umum disetujui.');
            return redirect()->back();

        } catch (\Exception $e) {
            // Menangani kesalahan dan mencatat log error
            log_message('error', 'Error in approveSemuaKom: ' . $e->getMessage());
            session()->setFlashdata('err', 'Terjadi kesalahan sistem: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function exporttxtpenjelasanumum()
    {
        // Authentication check
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        // Get parameters from request
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        // Fetch data for BPR
        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        $periodeDetail = $this->periodeModel->find($periodeId);
        $exportDate = $periodeDetail['tahun'] ?? date('Y');

        // Initialize variables for sandibpr and kodejenis
        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0]; // Get the first row
            $sandibpr = $infobpr['sandibpr'];
            $kodejenis = $infobpr['kodejenis'];
        }

        // Initialize output string for the file content
        $output = "";

        // Filter data based on kodebpr and periode_id
        $data = $this->penjelasanModel->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->findAll();

        $output .= "H01|010201|" . $sandibpr . "|" . $exportDate . "-12-31" . "|LTBPRK|E0100|0|" . "\r\n";
        // Add header row to the output
        foreach ($data as $row) {
            $penjelasan = str_replace(array("\r", "\n"), ' ', $row['penjelasan']);
            $penjelasankomposit = str_replace(array("\r", "\n"), ' ', $row['penjelasankomposit']);
            $output .= "D01|" . "000100000000" . "|" . $row['alamat'] . "|" . $row['nomor'] . "|" . $penjelasan . "|" . $row['peringkatkomposit'] . "|" . $penjelasankomposit . "\r\n";
        }

        // Generate file name based on sandibpr and current date
        $filename = "LTBPRK-E0100-R-A-" . $exportDate . "1231" . "-" . $sandibpr . "-01.txt";

        // Set the response headers for file download
        $response = service('response');
        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

        // Return the file content as the body of the response
        $response->setBody($output);

        return $response;
    }
}


