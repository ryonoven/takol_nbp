<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_faktor4;
use App\Models\M_user;
use App\Models\M_faktor4komentar;
use App\Models\M_nilaifaktor4;
use App\Models\M_infobpr;
use App\Models\M_periode;
use App\Models\M_commentreads4;
use Myth\Auth\Config\Services as AuthServices;

class Faktor4 extends Controller
{
    protected $auth;
    protected $faktor4Model;
    protected $userModel;
    protected $komentarModel;
    protected $nilai4Model;
    protected $infobprModel;
    protected $periodeModel;
    protected $session;
    protected $userKodebpr;
    protected $commentReads4Model;

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
        // PENTING: Panggil constructor parent di awal
        date_default_timezone_set('Asia/Jakarta');
        $this->faktor4Model = new M_faktor4();
        $this->periodeModel = new M_periode();
        $this->userModel = new M_user(); // Pastikan inisialisasi M_user
        $this->komentarModel = new M_faktor4komentar();
        $this->nilai4Model = new M_nilaifaktor4();
        $this->infobprModel = new M_infobpr();
        $this->commentReads4Model = new M_commentreads4();
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
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        if (!session('active_periode')) {
            return redirect()->to('/periode');
        }

        $user = $this->userModel->find(user_id());
        $periodeId = session('active_periode');
        $periodeDetail = $this->periodeModel->getPeriodeDetail($periodeId);
        $kodebpr = $this->userKodebpr;
        $bprData = $this->infobprModel->getBprByKode($this->userKodebpr);

        //Cek komisaris telah approves
        $canApprove = true; // Inisialisasi variabel dengan nilai true

        // Loop untuk memeriksa faktor4id dari 1 hingga 11
        for ($faktor4Id = 1; $faktor4Id <= 12; $faktor4Id++) {
            $accdekomValue = $this->nilai4Model
                ->where('faktor4id', $faktor4Id)
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->first();  // Dapatkan data pertama yang sesuai

            // Jika salah satu accdekom tidak bernilai 1, set $canApprove menjadi false
            if (!isset($accdekomValue) || $accdekomValue['accdekom'] != 1) {
                $canApprove = false;
                break; // Jika ditemukan satu yang tidak bernilai 1, keluar dari loop
            }
        }

        // Mengambil nilai accdekom untuk faktor4id 1 hingga 12
        $accdekomApproved = true;
        for ($faktor4Id = 1; $faktor4Id <= 11; $faktor4Id++) {
            $nilaiAccdekom = $this->nilai4Model
                ->where('faktor4id', $faktor4Id)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', session('active_periode'))
                ->first();

            if (!$nilaiAccdekom || $nilaiAccdekom['accdekom'] != 1) {
                $accdekomApproved = false;
                break;
            }
        }

        // Ambil data dengan filter periode dan kodebpr
        $nilaiData = $this->nilai4Model
            ->where('periode_id', $periodeId)
            ->where('kodebpr', $kodebpr)
            ->findAll();

        $data['periode'] = $this->periodeModel->find($periodeId);

        $komentarList = $this->komentarModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->findAll();

        $userId = $this->auth->id();
        $user = $this->userModel->find($userId);
        $infobprId = $this->auth->id();
        $infobpr = $this->infobprModel->find($infobprId);
        $fullname = $user['fullname'] ?? 'Unknown';
        $kodebpr = $user['kodebpr'] ?? null;

        if (!$kodebpr) {
            // Handle jika user tidak memiliki kodebpr
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
        }

        $faktorData = $this->faktor4Model->getAllData();
        $factorsWithDetails = [];

        // Memeriksa apakah nilai sudah disetujui untuk faktor4id 1-8
        $allApproved = true;
        $requiredFaktorIds = range(1, 12); // Membuat array dari 1 hingga 12
        foreach ($requiredFaktorIds as $faktor4Id) {
            $associatedNilai = $this->nilai4Model
                ->where('faktor4id', $faktor4Id)
                ->where('kodebpr', $kodebpr)
                ->first();

            if ($associatedNilai === null || $associatedNilai['is_approved'] != 1) {
                $allApproved = false;
                break;
            }
        }

        // foreach ($requiredFaktorIds as $faktor4Id) {
        //     // Ambil nilai untuk faktor4id
        //     $associatedNilai = $this->nilai4Model->where('faktor4id', $faktor4Id)->first();

        //     // Jika nilai tidak ada atau is_approved bukan 1, set $allApproved ke false
        //     if ($associatedNilai === null || $associatedNilai['is_approved'] != 1) {
        //         $allApproved = false;
        //         break; // Jika ada yang belum disetujui, hentikan pengecekan
        //     }
        // }


        foreach ($faktorData as $faktorItem) {
            $faktor4Id = $faktorItem['id'];

            // Retrieve the latest associated nilai (value) for this factor
            $associatedNilai = $this->nilai4Model
                ->where('faktor4id', $faktor4Id)
                ->where('kodebpr', $kodebpr)
                ->orderBy('created_at', 'DESC')
                ->first();

            // Calculate the average value (rata-rata) for this factor
            $rataRata = $this->nilai4Model->hitungRataRata($faktor4Id, $kodebpr);

            // Get explanation for the calculated average value (rata-rata)
            $penjelasfaktor = $this->nilai4Model->getPenjelasanNilai($rataRata);

            // Insert or update the calculated average value (rata-rata) into the database
            $this->nilai4Model->insertOrUpdateRataRata($rataRata, $faktor4Id, $kodebpr);

            // Fetch the kodebpr from the authenticated user's data
            $userModel = new \App\Models\M_user(); // Make sure the user model is loaded
            $userId = service('authentication')->id(); // Get authenticated user ID
            $user = $userModel->find($userId); // Fetch user details using the user ID
            $kodebpr = $user['kodebpr'] ?? null; // Fetch the 'kodebpr' for the user

            $lastVisit = session('last_visit_komentar') ?? date('Y-m-d H:i:s', strtotime('-1 day'));
            session()->set('last_visit_komentar', date('Y-m-d H:i:s'));

            // Handle cases where kodebpr is not found (optional, set a default or handle error)
            if (!$kodebpr) {
                $kodebpr = 'default_kodebpr'; // Replace with a default value or handle as necessary
            }

            // Prepare the data with the necessary fields for each factor
            $factorsWithDetails[] = [
                'id' => $faktorItem['id'],
                'sph' => $faktorItem['sph'],
                'sub_category' => $faktorItem['sub_category'],
                'nilai' => $associatedNilai['nilai'] ?? null,
                'nfaktor4' => $rataRata,
                'penjelasfaktor' => $penjelasfaktor,
                'keterangan' => $associatedNilai['keterangan'] ?? null,
                'kodebpr' => $kodebpr, // Use dynamically fetched kodebpr
                'is_approved' => $associatedNilai['is_approved'] ?? 0,
                'approved_at' => $associatedNilai['approved_at'] ?? 0,
                'positifstruktur' => $associatedNilai['positifstruktur'] ?? null,
                'negatifstruktur' => $associatedNilai['negatifstruktur'] ?? null,
                'positifproses' => $associatedNilai['positifproses'] ?? null,
                'negatifproses' => $associatedNilai['negatifproses'] ?? null,
                'positifhasil' => $associatedNilai['positifhasil'] ?? null,
                'negatifhasil' => $associatedNilai['negatifhasil'] ?? null,
                'periode_id' => $associatedNilai['periode_id'] ?? null,
                'accdekom' => $associatedNilai['accdekom'] ?? null,
                'accdekom_by' => $associatedNilai['accdekom_by'] ?? null,
                'accdekom_at' => $associatedNilai['accdekom_at'] ?? 0,
                'accdekom2' => $associatedNilai['accdekom2'] ?? null,
                'accdekom2_by' => $associatedNilai['accdekom2_by'] ?? null,
                'accdir2' => $associatedNilai['accdir2'] ?? null,
                'accdir2_by' => $associatedNilai['accdir2_by'] ?? null,
                // Add any other columns as needed
                // 'nama_kolom_lain_faktor' => $faktorItem['nama_kolom_lain_faktor'],
            ];
        }
        $data['lastVisit'] = $lastVisit;
        $data = [
            'judul' => 'Faktor 4',
            'faktor4' => $faktorData,
            'userId' => $userId,
            'faktors4' => $factorsWithDetails,
            'rataRata' => $rataRata,
            'penjelasfaktor' => $penjelasfaktor,
            // 'komentarList' => $komentarList, // Hapus ini atau set ke array kosong
            'komentarList' => $komentarList,
            // 'nilaiList' => $nilaiData,
            'userInGroupPE' => $this->userInGroupPE, // Gunakan properti yang sudah diinisialisasi
            'userInGroupAdmin' => $this->userInGroupAdmin,
            'userInGroupDekom' => $this->userInGroupDekom,
            'userInGroupDekom2' => $this->userInGroupDekom2,
            'userInGroupDekom3' => $this->userInGroupDekom3,
            'userInGroupDekom4' => $this->userInGroupDekom4,
            'userInGroupDekom5' => $this->userInGroupDekom5,
            'userInGroupDireksi' => $this->userInGroupDireksi,
            'userInGroupDireksi2' => $this->userInGroupDireksi2,
            // 'faktor4Id' => $faktor4Id, // Tidak perlu dikirim ke view jika tidak digunakan
            'fullname' => $fullname,
            'allApproved' => $allApproved,
            'kodebpr' => $this->userKodebpr,
            'nilaiData' => $nilaiData,
            'komentarModel' => $this->komentarModel,
            'commentReads4Model' => $this->commentReads4Model,
            'lastVisit' => $lastVisit,
            'periodeId' => $periodeId,
            'periodeDetail' => $periodeDetail,
            'bprData' => $bprData,
            'canApprove' => $canApprove,
            'accdekomApproved' => $accdekomApproved,
        ];

        // Pastikan $data dikirimkan ke view
        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('faktor4/index', $data);
        echo view('templates/v_footer');
    }

    public function cekKomentarBaru()
    {
        $kodebpr = $this->request->getGet('kodebpr');
        $lastVisit = $this->request->getGet('last_visit');
        $periodeId = session('active_periode');

        $results = $this->komentarModel
            ->select('faktor4id, COUNT(*) as jumlah')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('created_at >', $lastVisit)
            ->groupBy('faktor4id')
            ->findAll();

        return $this->response->setJSON($results);
    }


    // Fungsi untuk AJAX request komentar
    public function getKomentarByFaktorId($faktor4Id)
    {
        if (!$this->request->isAJAX() || !is_numeric($faktor4Id)) {
            return $this->response->setStatusCode(404)->setBody('Not Found');
        }

        $kodebpr = $this->userKodebpr; // Ambil kodebpr dari property
        $periodeId = session('active_periode');

        $komentarList = $this->komentarModel->getKomentarByFaktorId($faktor4Id, $kodebpr, $periodeId);

        return $this->response->setJSON($komentarList);
    }

    public function getNilaiByFaktorId($faktor4Id)
    {
        if (!$this->request->isAJAX() || !is_numeric($faktor4Id)) {
            // log_message('debug', 'Invalid AJAX or faktor4Id: ' . $faktor4Id); // Tambahkan log untuk debug
            return $this->response->setStatusCode(404)->setBody('Not Found');
        }

        $nilaiList = $this->nilai4Model->getNilaiByFaktorId($faktor4Id);

        // log_message('debug', 'Comments returned for faktor4Id ' . $faktor4Id . ': ' . json_encode($komentarList)); // Tambahkan log untuk debug
        return $this->response->setJSON($nilaiList);
    }

    public function tambahNilai()
    {
        date_default_timezone_set('Asia/Jakarta');
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
                // Get the current authenticated user ID
                $userId = service('authentication')->id();

                // Fetch user data to get kodebpr
                $userModel = new \App\Models\M_user(); // Make sure M_user model is used
                $user = $userModel->find($userId);
                $kodebpr = $user['kodebpr'] ?? null; // Fetch the kodebpr from the user record

                // If kodebpr is not found, set to a default value (optional)
                if (!$kodebpr) {
                    return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
                }

                $faktor4Id = $this->request->getPost('faktor4_id');
                $data = [
                    'faktor4id' => $faktor4Id,
                    'nilai' => $this->request->getPost('nilai'),
                    'keterangan' => $this->request->getPost('keterangan'),
                    'fullname' => $this->request->getPost('fullname'),
                    'user_id' => $userId, // Dynamically set the user ID
                    'kodebpr' => $kodebpr,
                    'periode_id' => $periodeId,
                    'created_at' => date('Y-m-d H:i:s'),
                ];

                $this->nilai4Model->tambahNilai($data, $faktor4Id, $kodebpr);

                // Calculate average value from faktor4id 1–8
                $rataRata = $this->nilai4Model->hitungRataRata($faktor4Id, $kodebpr);
                $rataRata = ($rataRata - floor($rataRata) >= 0.5) ? ceil($rataRata) : floor($rataRata);

                // Save to faktor4id 1 in nfaktor column (insert or update)
                $this->nilai4Model->insertOrUpdateRataRata($rataRata, 1, $kodebpr);

                session()->setFlashdata('message', 'Nilai berhasil ditambahkan');
                return redirect()->to(base_url('faktor4') . '?modal_nilai=' . $faktor4Id);
            }
        } else {
            return redirect()->to(base_url('faktor4'));
        }
    }


    public function tambahKomentar()
    {
        date_default_timezone_set('Asia/Jakarta');
        if (!$this->auth->check()) {
            return redirect()->to('/login');
        }

        if (isset($_POST['tambahKomentar'])) {
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
                'faktor4id' => $this->request->getPost('faktor4_id'),
                'komentar' => $this->request->getPost('komentar'),
                'fullname' => $this->request->getPost('fullname'),
                'user_id' => $userId,
                'kodebpr' => $kodebpr,
                'periode_id' => session('active_periode'), // Pastikan ini diisi
                'created_at' => date('Y-m-d H:i:s')
            ];

            $this->komentarModel->insertKomentar($data);
            session()->setFlashdata('message', 'Komentar berhasil ditambahkan');
            return redirect()->to(base_url('faktor4') . '?modal_komentar=' . $this->request->getPost('faktor4_id'));
        }

        return redirect()->to(base_url('faktor4'));
    }

    public function ubah()
    {
        $faktor4id = $this->request->getPost('faktor4id');
        $userId = service('authentication')->id();
        $userModel = new \App\Models\M_user(); // Ensure M_user model is used
        $user = $userModel->find($userId);
        $kodebpr = $user['kodebpr'] ?? null;

        if (!$faktor4id) {
            session()->setFlashdata('err', 'ID Faktor tidak ditemukan.');
            return redirect()->to(base_url('faktor4'));
        }

        if (!$kodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
        }

        // Periksa periode aktif
        $periodeId = session('active_periode');
        if (!$periodeId) {
            return redirect()->back()->with('error', 'Periode tidak valid');
        }

        // Ambil data yang akan diubah
        $data = [
            'nilai' => $this->request->getPost('nilai'),
            'keterangan' => $this->request->getPost('keterangan'),
            'is_approved' => 0,
            'accdekom' => 0,
            'user_id' => $userId,
            'kodebpr' => $kodebpr,
        ];

        // Pastikan update berdasarkan faktor4id, kodebpr, dan periode_id
        if ($this->nilai4Model->ubahBerdasarkanFaktorId($data, $faktor4id, $kodebpr, $periodeId)) {
            // Update accdekom dan is_approved untuk faktor4id 11
            $this->nilai4Model->where('faktor4id', 12)
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->set(['accdekom' => 0, 'is_approved' => 0])
                ->update();

            session()->setFlashdata('message', 'Data berhasil diubah');
        } else {
            session()->setFlashdata('err', 'Gagal mengubah data');
        }

        return redirect()->to(base_url('faktor4'));
    }

    public function ubahkesimpulan()
    {
        // Cek apakah ada faktor4id
        $faktor4Id = $this->request->getPost('faktor4id');

        if (!$faktor4Id) {
            session()->setFlashdata('err', 'ID Faktor tidak ditemukan.');
            return redirect()->to(base_url('faktor4'));
        }

        $userId = service('authentication')->id();
        $userModel = new \App\Models\M_user(); // Pastikan model M_user digunakan
        $user = $userModel->find($userId);
        $kodebpr = $user['kodebpr'] ?? null;

        if (!$kodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
        }

        // Periksa periode aktif
        $periodeId = session('active_periode');
        if (!$periodeId) {
            return redirect()->back()->with('error', 'Periode tidak valid');
        }

        // Ambil data yang akan diubah
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

        // Pastikan update berdasarkan faktor4id, kodebpr, dan periode_id
        if ($this->nilai4Model->ubahBerdasarkanFaktorId($data, $faktor4Id, $kodebpr, $periodeId)) {
            session()->setFlashdata('message', 'Data berhasil diubah');
        } else {
            session()->setFlashdata('err', 'Gagal mengubah data');
        }

        return redirect()->to(base_url('faktor4'));
    }

    public function excel()
    {
        $data = [
            'faktor4' => $this->faktor4Model->getAllData() // <<< PERBAIKI DI SINI: $this->model -> $this->faktor4Model
        ];

        echo view('faktor4/excel', $data);
    }

    public function hapus($id)
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()->to($redirectURL);
        }

        // Memanggil fungsi hapus pada model dan menyimpan hasilnya dalam variabel $success
        $this->nilai4Model->hapus($id);
        session()->setFlashdata('message', 'Data berhasil dihapus');

        return redirect()->to(base_url('faktor4'));

    }

    public function setNullKolom($id)
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $success = $this->faktor4Model->setNullKolom($id);

        if ($success) {
            session()->setFlashdata('message', 'Data berhasil dihapus');
        } else {
            session()->setFlashdata('err', 'Data gagal dihapus');
        }

        return redirect()->to(base_url('faktor4'));
    }

    // Fungsi approve dan unapprove sudah menggunakan $this->faktor4Model dengan benar
    public function approve($idNilai = null)
    {
        if ($idNilai === null) {
            session()->setFlashdata('err', 'ID Faktor tidak valid.');
            return redirect()->back();
        }

        // Ambil data dari tabel nilaifaktor berdasarkan faktor4_id
        $nilaiFaktor4 = $this->nilai4Model->find($idNilai);
        if (!$nilaiFaktor4) {
            session()->setFlashdata('err', 'Data tidak ditemukan.');
            return redirect()->back();
        }
        // Memeriksa apakah nilai faktor4 sudah diapprove atau tidak
        date_default_timezone_set('Asia/Jakarta');
        $userId = service('authentication')->id();

        $dataUpdate = [
            'is_approved' => 1,  // Status disetujui
            'approved_by' => $userId,  // Menyimpan siapa yang memberikan approval
            'approved_at' => date('Y-m-d H:i:s'),  // Waktu persetujuan
        ];

        // Update status approval di tabel nilaifaktor
        if ($this->nilai4Model->update($idNilai, $dataUpdate)) {
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

        // Ambil data dari tabel nilaifaktor berdasarkan faktor4_id
        $nilaiFaktor4 = $this->nilai4Model->find($idNilai);
        if (!$nilaiFaktor4) {
            session()->setFlashdata('err', 'Data tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');
        $userId = service('authentication')->id();

        // Data untuk diupdate
        $dataUpdate = [
            'is_approved' => 0,  // Status tidak disetujui
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s')
        ];

        // Update status approval di tabel nilaifaktor
        if ($this->nilai4Model->update($idNilai, $dataUpdate)) {
            session()->setFlashdata('message', 'Data approval dibatalkan.');
            return redirect()->back();
        } else {
            session()->setFlashdata('err', 'Terjadi kesalahan saat membatalkan approval.');
            return redirect()->back();
        }
    }

    public function approveSemua()
    {
        date_default_timezone_set('Asia/Jakarta');
        $userId = service('authentication')->id();
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        // Validasi data penting
        if (!$kodebpr || !$periodeId) {
            session()->setFlashdata('err', 'Kode BPR atau Periode ID tidak valid');
            return redirect()->back();
        }

        // Cek dulu apakah ada data yang akan diupdate
        $count = $this->nilai4Model
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->countAllResults();

        if ($count === 0) {
            session()->setFlashdata('err', 'Tidak ada data yang bisa diupdate untuk periode ini');
            return redirect()->back();
        }

        // Data untuk diupdate
        $dataUpdate = [
            'is_approved' => 1,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        try {
            // Lakukan update
            $updated = $this->nilai4Model
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->update(null, $dataUpdate); // Parameter pertama null untuk update semua yang sesuai kriteria

            if (!$updated) {
                session()->setFlashdata('err', 'Gagal mengupdate data approval');
                return redirect()->back();
            }

            // Hitung ulang rata-rata
            $rataRata = $this->nilai4Model->hitungRataRata(1, $kodebpr, $periodeId);
            $this->nilai4Model->insertOrUpdateRataRata($rataRata, 1, $kodebpr, $periodeId);

            session()->setFlashdata('message', 'Semua faktor4 berhasil disetujui.');
            return redirect()->back();

        } catch (\Exception $e) {
            log_message('error', 'Error in approveSemua: ' . $e->getMessage());
            session()->setFlashdata('err', 'Terjadi kesalahan sistem: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function unapproveSemua()
    {
        date_default_timezone_set('Asia/Jakarta');
        $userId = service('authentication')->id();
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        // Validasi data penting
        if (!$kodebpr || !$periodeId) {
            session()->setFlashdata('err', 'Kode BPR atau Periode ID tidak valid');
            return redirect()->back();
        }

        // Cek dulu apakah ada data yang akan diupdate
        $count = $this->nilai4Model
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->countAllResults();

        if ($count === 0) {
            session()->setFlashdata('err', 'Tidak ada data yang bisa diupdate untuk periode ini');
            return redirect()->back();
        }

        // Data untuk diupdate
        $dataUpdate = [
            'is_approved' => 0,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        try {
            // Lakukan update
            $updated = $this->nilai4Model
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->update(null, $dataUpdate); // Parameter pertama null untuk update semua yang sesuai kriteria

            if (!$updated) {
                session()->setFlashdata('err', 'Gagal mengupdate data approval');
                return redirect()->back();
            }

            // Hitung ulang rata-rata
            $rataRata = $this->nilai4Model->hitungRataRata(1, $kodebpr, $periodeId);
            $this->nilai4Model->insertOrUpdateRataRata($rataRata, 1, $kodebpr, $periodeId);

            session()->setFlashdata('message', 'Semua faktor4 berhasil disetujui.');
            return redirect()->back();

        } catch (\Exception $e) {
            log_message('error', 'Error in approveSemua: ' . $e->getMessage());
            session()->setFlashdata('err', 'Terjadi kesalahan sistem: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function approveSemuaKom()
    {
        date_default_timezone_set('Asia/Jakarta');
        $userId = service('authentication')->id();
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        // Validasi data penting
        if (!$kodebpr || !$periodeId) {
            session()->setFlashdata('err', 'Kode BPR atau Periode ID tidak valid');
            return redirect()->back();
        }

        // Cek dulu apakah ada data yang akan diupdate
        $count = $this->nilai4Model
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->countAllResults();

        if ($count === 0) {
            session()->setFlashdata('err', 'Tidak ada data yang bisa diupdate untuk periode ini');
            return redirect()->back();
        }

        // Data untuk diupdate
        $dataUpdate = [
            'accdekom' => 1,
            'accdekom_by' => $userId,
            'accdekom_at' => date('Y-m-d H:i:s'),
        ];

        try {
            // Lakukan update
            $updated = $this->nilai4Model
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->update(null, $dataUpdate); // Parameter pertama null untuk update semua yang sesuai kriteria

            if (!$updated) {
                session()->setFlashdata('err', 'Gagal mengupdate data approval');
                return redirect()->back();
            }

            // Hitung ulang rata-rata
            $rataRata = $this->nilai4Model->hitungRataRata(1, $kodebpr, $periodeId);
            $this->nilai4Model->insertOrUpdateRataRata($rataRata, 1, $kodebpr, $periodeId);

            session()->setFlashdata('message', 'Semua faktor4 berhasil disetujui.');
            return redirect()->back();

        } catch (\Exception $e) {
            log_message('error', 'Error in approveSemua: ' . $e->getMessage());
            session()->setFlashdata('err', 'Terjadi kesalahan sistem: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function unapprovekom()
    {
        date_default_timezone_set('Asia/Jakarta');
        $userId = service('authentication')->id();
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        // Validasi data penting
        if (!$kodebpr || !$periodeId) {
            session()->setFlashdata('err', 'Kode BPR atau Periode ID tidak valid');
            return redirect()->back();
        }

        // Data untuk diupdate
        $dataUpdate = [
            'accdekom' => 0,
            'accdekom_by' => $userId,
            'accdekom_at' => date('Y-m-d H:i:s'),
            'is_approved' => 0,
        ];

        try {
            $updated = $this->nilai4Model
                ->where('faktor4id', 12)  // Only update where faktor4id = 12
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->update(null, $dataUpdate); // Parameter pertama null untuk update semua yang sesuai kriteria

            if (!$updated) {
                session()->setFlashdata('err', 'Gagal mengupdate data approval untuk faktor4');
                return redirect()->back();
            }

            // Optionally, recalculate the average (if needed)
            // $rataRata = $this->nilai4Model->hitungRataRata(1, $kodebpr, $periodeId);
            // $this->nilai4Model->insertOrUpdateRataRata($rataRata, 1, $kodebpr, $periodeId);

            session()->setFlashdata('message', 'Faktor 4 berhasil di-unapprove.');
            return redirect()->back();

        } catch (\Exception $e) {
            log_message('error', 'Error in unapproveSemuaKom: ' . $e->getMessage());
            session()->setFlashdata('err', 'Terjadi kesalahan sistem: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    // Controller
    public function accdekom()
    {
        // Pastikan metode POST
        if ($this->request->getMethod() !== 'post') {
            return $this->response->setStatusCode(405, 'Method Not Allowed');
        }

        // Ambil data dari request
        $faktor4id = $this->request->getPost('faktor4id');
        $kodebpr = $this->request->getPost('kodebpr');
        $periodeId = $this->request->getPost('periode_id');

        if (!$faktor4id || !$kodebpr || !$periodeId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak lengkap']);
        }

        // Ambil data faktor4 berdasarkan faktor4id, kodebpr, dan periode_id
        $nilaiFaktor4 = $this->nilai4Model
            ->where('faktor4id', $faktor4id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if (!$nilaiFaktor4) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak ditemukan']);
        }

        $dataUpdateForFaktor12 = [
            'accdekom' => 0,
            'is_approved' => 0,
            'accdekom_by' => service('authentication')->id(), // ID pengguna yang menyetujui
            'accdekom_at' => date('Y-m-d H:i:s'),
        ];

        // Update record untuk faktor1id 12
        $this->nilai4Model->where('faktor4id', 12)->update(null, $dataUpdateForFaktor12);

        // Update status approval
        $dataUpdate = [
            'accdekom' => 1,
            'accdekom_by' => service('authentication')->id(), // ID pengguna yang menyetujui
            'accdekom_at' => date('Y-m-d H:i:s'),
        ];

        // Update record dalam database
        if ($this->nilai4Model->update($nilaiFaktor4['id'], $dataUpdate)) {
            return redirect()->to('/faktor4')->with('message', 'Faktor berhasil disetujui');
        } else {
            return redirect()->to('/faktor4')->with('error', 'Gagal memperbarui data');
        }

    }

    public function unapprovedekom()
    {
        // Pastikan metode POST
        if ($this->request->getMethod() !== 'post') {
            return $this->response->setStatusCode(405, 'Method Not Allowed');
        }

        // Ambil data dari request
        $faktor4id = $this->request->getPost('faktor4id');
        $kodebpr = $this->request->getPost('kodebpr');
        $periodeId = $this->request->getPost('periode_id');

        if (!$faktor4id || !$kodebpr || !$periodeId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak lengkap']);
        }

        // Ambil data faktor4 berdasarkan faktor4id, kodebpr, dan periode_id
        $nilaiFaktor4 = $this->nilai4Model
            ->where('faktor4id', $faktor4id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if (!$nilaiFaktor4) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak ditemukan']);
        }

        // Update status approval
        $dataUpdate = [
            'accdekom' => 0,
            'accdekom_by' => service('authentication')->id(), // ID pengguna yang menyetujui
            'accdekom_at' => date('Y-m-d H:i:s'),
            'is_approved' => 0
        ];

        // Update record dalam database
        if ($this->nilai4Model->update($nilaiFaktor4['id'], $dataUpdate)) {
            // Update accdekom untuk faktor4id 12
            $this->nilai4Model->where('faktor4id', 12)
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->set(['accdekom' => 0])
                ->set(['is_approved' => 0])
                ->update();

            return redirect()->to('/faktor4')->with('message', 'Faktor belum disetujui dekom');
        } else {
            return redirect()->to('/faktor4')->with('error', 'Gagal memperbarui data');
        }

    }

    public function accdekom2()
    {
        // Pastikan metode POST
        if ($this->request->getMethod() !== 'post') {
            return $this->response->setStatusCode(405, 'Method Not Allowed');
        }

        // Ambil data dari request
        $faktor4id = $this->request->getPost('faktor4id');
        $kodebpr = $this->request->getPost('kodebpr');
        $periodeId = $this->request->getPost('periode_id');

        if (!$faktor4id || !$kodebpr || !$periodeId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak lengkap']);
        }

        // Ambil data faktor4 berdasarkan faktor4id, kodebpr, dan periode_id
        $nilaiFaktor4 = $this->nilai4Model
            ->where('faktor4id', $faktor4id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if (!$nilaiFaktor4) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak ditemukan']);
        }

        // Update status approval
        $dataUpdate = [
            'accdekom2' => 1,
            'accdekom2_by' => service('authentication')->id(), // ID pengguna yang menyetujui
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        // Update record dalam database
        if ($this->nilai4Model->update($nilaiFaktor4['id'], $dataUpdate)) {
            return redirect()->to('/faktor4')->with('message', 'Faktor berhasil disetujui');
        } else {
            return redirect()->to('/faktor4')->with('error', 'Gagal memperbarui data');
        }

    }

    public function unapprovedekom2()
    {
        // Pastikan metode POST
        if ($this->request->getMethod() !== 'post') {
            return $this->response->setStatusCode(405, 'Method Not Allowed');
        }

        // Ambil data dari request
        $faktor4id = $this->request->getPost('faktor4id');
        $kodebpr = $this->request->getPost('kodebpr');
        $periodeId = $this->request->getPost('periode_id');

        if (!$faktor4id || !$kodebpr || !$periodeId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak lengkap']);
        }

        // Ambil data faktor4 berdasarkan faktor4id, kodebpr, dan periode_id
        $nilaiFaktor4 = $this->nilai4Model
            ->where('faktor4id', $faktor4id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if (!$nilaiFaktor4) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak ditemukan']);
        }

        // Update status approval
        $dataUpdate = [
            'accdekom2' => 0,
            'accdekom2_by' => service('authentication')->id(), // ID pengguna yang menyetujui
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        // Update record dalam database
        if ($this->nilai4Model->update($nilaiFaktor4['id'], $dataUpdate)) {
            return redirect()->to('/faktor4')->with('message', 'Faktor belum disetujui dekom');
        } else {
            return redirect()->to('/faktor4')->with('error', 'Gagal memperbarui data');
        }

    }

    public function accdir2()
    {
        // Pastikan metode POST
        if ($this->request->getMethod() !== 'post') {
            return $this->response->setStatusCode(405, 'Method Not Allowed');
        }

        // Ambil data dari request
        $faktor4id = $this->request->getPost('faktor4id');
        $kodebpr = $this->request->getPost('kodebpr');
        $periodeId = $this->request->getPost('periode_id');

        if (!$faktor4id || !$kodebpr || !$periodeId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak lengkap']);
        }

        // Ambil data faktor4 berdasarkan faktor4id, kodebpr, dan periode_id
        $nilaiFaktor4 = $this->nilai4Model
            ->where('faktor4id', $faktor4id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if (!$nilaiFaktor4) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak ditemukan']);
        }

        // Update status approval
        $dataUpdate = [
            'accdir2' => 1,
            'accdir2_by' => service('authentication')->id(), // ID pengguna yang menyetujui
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        // Update record dalam database
        if ($this->nilai4Model->update($nilaiFaktor4['id'], $dataUpdate)) {
            return redirect()->to('/faktor4')->with('message', 'Faktor berhasil disetujui');
        } else {
            return redirect()->to('/faktor4')->with('error', 'Gagal memperbarui data');
        }

    }

    public function unapprovedir2()
    {
        // Pastikan metode POST
        if ($this->request->getMethod() !== 'post') {
            return $this->response->setStatusCode(405, 'Method Not Allowed');
        }

        // Ambil data dari request
        $faktor4id = $this->request->getPost('faktor4id');
        $kodebpr = $this->request->getPost('kodebpr');
        $periodeId = $this->request->getPost('periode_id');

        if (!$faktor4id || !$kodebpr || !$periodeId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak lengkap']);
        }

        // Ambil data faktor4 berdasarkan faktor4id, kodebpr, dan periode_id
        $nilaiFaktor4 = $this->nilai4Model
            ->where('faktor4id', $faktor4id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if (!$nilaiFaktor4) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak ditemukan']);
        }

        // Update status approval
        $dataUpdate = [
            'accdir2' => 0,
            'accdir2_by' => service('authentication')->id(), // ID pengguna yang menyetujui
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        // Update record dalam database
        if ($this->nilai4Model->update($nilaiFaktor4['id'], $dataUpdate)) {
            return redirect()->to('/faktor4')->with('message', 'Faktor belum disetujui dekom');
        } else {
            return redirect()->to('/faktor4')->with('error', 'Gagal memperbarui data');
        }

    }

    public function checkAccDekomApproval()
    {
        // Get the user and periode
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        // Check if accdekom is 1 for faktor4id 1 to 11
        $allApproved = true;
        for ($faktor4Id = 1; $faktor4Id <= 11; $faktor4Id++) {
            $nilaiFaktor4 = $this->nilai4Model
                ->where('faktor4id', $faktor4Id)
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->first();

            // If accdekom is not 1, set $allApproved to false and break
            if (!$nilaiFaktor4 || $nilaiFaktor4['accdekom'] != 1) {
                $allApproved = false;
                break;
            }
        }

        return $allApproved;
    }

    public function getUnreadCommentCountForFactor()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $faktor4Id = $this->request->getGet('faktor4_id');
        $kodebpr = $this->userKodebpr;
        $userId = user_id();
        $periodeId = session('active_periode');

        if (!$faktor4Id || !$kodebpr || !$userId || !$periodeId) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Missing data.']);
        }

        $count = $this->commentReads4Model->countUnreadCommentsForUserByFactor($faktor4Id, $kodebpr, $userId, $periodeId);

        return $this->response->setJSON(['unread_count' => $count]);
    }


    public function markUserCommentsAsRead()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $faktor4Id = $this->request->getPost('faktor4_id');
        $kodebpr = $this->userKodebpr; // Get from property
        $userId = user_id();
        $periodeId = session('active_periode');

        if (!$faktor4Id || !$kodebpr || !$userId || !$periodeId) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Missing data.']);
        }

        // Get all comment IDs for this factor, kodebpr, periode, and not by the current user
        $commentsToMark = $this->komentarModel->select('id')
            ->where('faktor4id', $faktor4Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('user_id !=', $userId) // Mark comments from others as read
            ->findAll();

        if (!empty($commentsToMark)) {
            foreach ($commentsToMark as $comment) {
                $this->commentReads4Model->markAsRead($comment['id'], $userId);
            }
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Comments marked as read for this user.']);
    }


    // Make sure your saveKomentar also sets is_read to 0 for new comments:
    public function saveKomentar()
    {
        $data = [
            'faktor4id' => $this->request->getPost('faktor4_id'),
            'kodebpr' => $this->request->getPost('kodebpr'),
            'komentar' => $this->request->getPost('komentar'),
            'is_read' => 0, // <--- Ensure this is set to 0 for new comments
            'created_at' => date('Y-m-d H:i:s'),
            'user_id' => session()->get('user_id')
        ];

        $this->komentarModel->insert($data);
        return $this->response->setJSON(['status' => 'comment_saved']);
    }

}
