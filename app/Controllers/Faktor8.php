<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_faktor8;
use App\Models\M_user;
use App\Models\M_faktor8komentar;
use App\Models\M_nilaifaktor8;
use App\Models\M_infobpr;
use App\Models\M_periode;
use App\Models\M_commentreads8;
use Myth\Auth\Config\Services as AuthServices;

class Faktor8 extends Controller
{
    protected $auth;
    protected $faktor8Model;
    protected $userModel;
    protected $komentarModel;
    protected $nilai8Model;
    protected $infobprModel;
    protected $periodeModel;
    protected $session;
    protected $userKodebpr;
    protected $commentReads8Model;

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
        $this->faktor8Model = new M_faktor8();
        $this->periodeModel = new M_periode();
        $this->userModel = new M_user(); // Pastikan inisialisasi M_user
        $this->komentarModel = new M_faktor8komentar();
        $this->nilai8Model = new M_nilaifaktor8();
        $this->infobprModel = new M_infobpr();
        $this->commentReads8Model = new M_commentreads8();
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

        // Loop untuk memeriksa faktor8id dari 1 hingga 9
        for ($faktor8Id = 1; $faktor8Id <= 6; $faktor8Id++) {
            $accdekomValue = $this->nilai8Model
                ->where('faktor8id', $faktor8Id)
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->first();  // Dapatkan data pertama yang sesuai

            // Jika salah satu accdekom tidak bernilai 1, set $canApprove menjadi false
            if (!isset($accdekomValue) || $accdekomValue['accdekom'] != 1) {
                $canApprove = false;
                break; // Jika ditemukan satu yang tidak bernilai 1, keluar dari loop
            }
        }

        // Mengambil nilai accdekom untuk faktor8id 1 hingga 9
        $accdekomApproved = true;
        for ($faktor8Id = 1; $faktor8Id <= 5; $faktor8Id++) {
            $nilaiAccdekom = $this->nilai8Model
                ->where('faktor8id', $faktor8Id)
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', session('active_periode'))
                ->first();

            if (!$nilaiAccdekom || $nilaiAccdekom['accdekom'] != 1) {
                $accdekomApproved = false;
                break;
            }
        }

        // Ambil data dengan filter periode dan kodebpr
        $nilaiData = $this->nilai8Model
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

        $faktorData = $this->faktor8Model->getAllData();
        $factorsWithDetails = [];

        // Memeriksa apakah nilai sudah disetujui untuk faktor8id 1-8
        $allApproved = true;
        $requiredFaktorIds = range(1, 6); // Membuat array dari 1 hingga 6
        foreach ($requiredFaktorIds as $faktor8Id) {
            $associatedNilai = $this->nilai8Model
                ->where('faktor8id', $faktor8Id)
                ->where('kodebpr', $kodebpr)
                ->first();

            if ($associatedNilai === null || $associatedNilai['is_approved'] != 1) {
                $allApproved = false;
                break;
            }
        }

        // foreach ($requiredFaktorIds as $faktor8Id) {
        //     // Ambil nilai untuk faktor8id
        //     $associatedNilai = $this->nilai8Model->where('faktor8id', $faktor8Id)->first();

        //     // Jika nilai tidak ada atau is_approved bukan 1, set $allApproved ke false
        //     if ($associatedNilai === null || $associatedNilai['is_approved'] != 1) {
        //         $allApproved = false;
        //         break; // Jika ada yang belum disetujui, hentikan pengecekan
        //     }
        // }


        foreach ($faktorData as $faktorItem) {
            $faktor8Id = $faktorItem['id'];

            // Retrieve the latest associated nilai (value) for this factor
            $associatedNilai = $this->nilai8Model
                ->where('faktor8id', $faktor8Id)
                ->where('kodebpr', $kodebpr)
                ->orderBy('created_at', 'DESC')
                ->first();

            // Calculate the average value (rata-rata) for this factor
            $rataRata = $this->nilai8Model->hitungRataRata($faktor8Id, $kodebpr);

            // Get explanation for the calculated average value (rata-rata)
            $penjelasfaktor = $this->nilai8Model->getPenjelasanNilai($rataRata);

            // Insert or update the calculated average value (rata-rata) into the database
            $this->nilai8Model->insertOrUpdateRataRata($rataRata, $faktor8Id, $kodebpr);

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
                'nfaktor8' => $rataRata,
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
            'judul' => 'Faktor 8',
            'faktor8' => $faktorData,
            'userId' => $userId,
            'faktors8' => $factorsWithDetails,
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
            // 'faktor8Id' => $faktor8Id, // Tidak perlu dikirim ke view jika tidak digunakan
            'fullname' => $fullname,
            'allApproved' => $allApproved,
            'kodebpr' => $this->userKodebpr,
            'nilaiData' => $nilaiData,
            'komentarModel' => $this->komentarModel,
            'commentReads8Model' => $this->commentReads8Model,
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
        echo view('faktor8/index', $data);
        echo view('templates/v_footer');
    }

    public function cekKomentarBaru()
    {
        $kodebpr = $this->request->getGet('kodebpr');
        $lastVisit = $this->request->getGet('last_visit');
        $periodeId = session('active_periode');

        $results = $this->komentarModel
            ->select('faktor8id, COUNT(*) as jumlah')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('created_at >', $lastVisit)
            ->groupBy('faktor8id')
            ->findAll();

        return $this->response->setJSON($results);
    }


    // Fungsi untuk AJAX request komentar
    public function getKomentarByFaktorId($faktor8Id)
    {
        if (!$this->request->isAJAX() || !is_numeric($faktor8Id)) {
            return $this->response->setStatusCode(404)->setBody('Not Found');
        }

        $kodebpr = $this->userKodebpr; // Ambil kodebpr dari property
        $periodeId = session('active_periode');

        $komentarList = $this->komentarModel->getKomentarByFaktorId($faktor8Id, $kodebpr, $periodeId);

        return $this->response->setJSON($komentarList);
    }

    public function getNilaiByFaktorId($faktor8Id)
    {
        if (!$this->request->isAJAX() || !is_numeric($faktor8Id)) {
            // log_message('debug', 'Invalid AJAX or faktor8Id: ' . $faktor8Id); // Tambahkan log untuk debug
            return $this->response->setStatusCode(404)->setBody('Not Found');
        }

        $nilaiList = $this->nilai8Model->getNilaiByFaktorId($faktor8Id);

        // log_message('debug', 'Comments returned for faktor8Id ' . $faktor8Id . ': ' . json_encode($komentarList)); // Tambahkan log untuk debug
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

                $faktor8Id = $this->request->getPost('faktor8_id');
                $data = [
                    'faktor8id' => $faktor8Id,
                    'nilai' => $this->request->getPost('nilai'),
                    'keterangan' => $this->request->getPost('keterangan'),
                    'fullname' => $this->request->getPost('fullname'),
                    'user_id' => $userId, // Dynamically set the user ID
                    'kodebpr' => $kodebpr,
                    'periode_id' => $periodeId,
                    'created_at' => date('Y-m-d H:i:s'),
                ];

                $this->nilai8Model->tambahNilai($data, $faktor8Id, $kodebpr);

                // Calculate average value from faktor8id 1–8
                $rataRata = $this->nilai8Model->hitungRataRata($faktor8Id, $kodebpr);
                $rataRata = ($rataRata - floor($rataRata) >= 0.5) ? ceil($rataRata) : floor($rataRata);

                // Save to faktor8id 1 in nfaktor column (insert or update)
                $this->nilai8Model->insertOrUpdateRataRata($rataRata, 1, $kodebpr);

                session()->setFlashdata('message', 'Nilai berhasil ditambahkan');
                return redirect()->to(base_url('faktor8') . '?modal_nilai=' . $faktor8Id);
            }
        } else {
            return redirect()->to(base_url('faktor8'));
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
                'faktor8id' => $this->request->getPost('faktor8_id'),
                'komentar' => $this->request->getPost('komentar'),
                'fullname' => $this->request->getPost('fullname'),
                'user_id' => $userId,
                'kodebpr' => $kodebpr,
                'periode_id' => session('active_periode'), // Pastikan ini diisi
                'created_at' => date('Y-m-d H:i:s')
            ];

            $this->komentarModel->insertKomentar($data);
            session()->setFlashdata('message', 'Komentar berhasil ditambahkan');
            return redirect()->to(base_url('faktor8') . '?modal_komentar=' . $this->request->getPost('faktor8_id'));
        }

        return redirect()->to(base_url('faktor8'));
    }

    public function ubah()
    {
        $faktor8id = $this->request->getPost('faktor8id');
        $userId = service('authentication')->id();
        $userModel = new \App\Models\M_user(); // Ensure M_user model is used
        $user = $userModel->find($userId);
        $kodebpr = $user['kodebpr'] ?? null;

        if (!$faktor8id) {
            session()->setFlashdata('err', 'ID Faktor tidak ditemukan.');
            return redirect()->to(base_url('faktor8'));
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

        // Pastikan update berdasarkan faktor8id, kodebpr, dan periode_id
        if ($this->nilai8Model->ubahBerdasarkanFaktorId($data, $faktor8id, $kodebpr, $periodeId)) {
            // Update accdekom dan is_approved untuk faktor8id 6
            $this->nilai8Model->where('faktor8id', 6)
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->set(['accdekom' => 0, 'is_approved' => 0])
                ->update();

            session()->setFlashdata('message', 'Data berhasil diubah');
        } else {
            session()->setFlashdata('err', 'Gagal mengubah data');
        }

        return redirect()->to(base_url('faktor8'));
    }

    public function ubahkesimpulan()
    {
        // Cek apakah ada faktor8id
        $faktor8Id = $this->request->getPost('faktor8id');

        if (!$faktor8Id) {
            session()->setFlashdata('err', 'ID Faktor tidak ditemukan.');
            return redirect()->to(base_url('faktor8'));
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

        // Pastikan update berdasarkan faktor8id, kodebpr, dan periode_id
        if ($this->nilai8Model->ubahBerdasarkanFaktorId($data, $faktor8Id, $kodebpr, $periodeId)) {
            session()->setFlashdata('message', 'Data berhasil diubah');
        } else {
            session()->setFlashdata('err', 'Gagal mengubah data');
        }

        return redirect()->to(base_url('faktor8'));
    }

    public function excel()
    {
        $data = [
            'faktor8' => $this->faktor8Model->getAllData() // <<< PERBAIKI DI SINI: $this->model -> $this->faktor8Model
        ];

        echo view('faktor8/excel', $data);
    }

    public function hapus($id)
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()->to($redirectURL);
        }

        // Memanggil fungsi hapus pada model dan menyimpan hasilnya dalam variabel $success
        $this->nilai8Model->hapus($id);
        session()->setFlashdata('message', 'Data berhasil dihapus');

        return redirect()->to(base_url('faktor8'));

    }

    public function setNullKolom($id)
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $success = $this->faktor8Model->setNullKolom($id);

        if ($success) {
            session()->setFlashdata('message', 'Data berhasil dihapus');
        } else {
            session()->setFlashdata('err', 'Data gagal dihapus');
        }

        return redirect()->to(base_url('faktor8'));
    }

    // Fungsi approve dan unapprove sudah menggunakan $this->faktor8Model dengan benar
    public function approve($idNilai = null)
    {
        if ($idNilai === null) {
            session()->setFlashdata('err', 'ID Faktor tidak valid.');
            return redirect()->back();
        }

        // Ambil data dari tabel nilaifaktor berdasarkan faktor8_id
        $nilaiFaktor8 = $this->nilai8Model->find($idNilai);
        if (!$nilaiFaktor8) {
            session()->setFlashdata('err', 'Data tidak ditemukan.');
            return redirect()->back();
        }
        // Memeriksa apakah nilai faktor8 sudah diapprove atau tidak
        date_default_timezone_set('Asia/Jakarta');
        $userId = service('authentication')->id();

        $dataUpdate = [
            'is_approved' => 1,  // Status disetujui
            'approved_by' => $userId,  // Menyimpan siapa yang memberikan approval
            'approved_at' => date('Y-m-d H:i:s'),  // Waktu persetujuan
        ];

        // Update status approval di tabel nilaifaktor
        if ($this->nilai8Model->update($idNilai, $dataUpdate)) {
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

        // Ambil data dari tabel nilaifaktor berdasarkan faktor8_id
        $nilaiFaktor8 = $this->nilai8Model->find($idNilai);
        if (!$nilaiFaktor8) {
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
        if ($this->nilai8Model->update($idNilai, $dataUpdate)) {
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
        $count = $this->nilai8Model
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
            $updated = $this->nilai8Model
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->update(null, $dataUpdate); // Parameter pertama null untuk update semua yang sesuai kriteria

            if (!$updated) {
                session()->setFlashdata('err', 'Gagal mengupdate data approval');
                return redirect()->back();
            }

            // Hitung ulang rata-rata
            $rataRata = $this->nilai8Model->hitungRataRata(1, $kodebpr, $periodeId);
            $this->nilai8Model->insertOrUpdateRataRata($rataRata, 1, $kodebpr, $periodeId);

            session()->setFlashdata('message', 'Semua faktor8 berhasil disetujui.');
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
        $count = $this->nilai8Model
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
            $updated = $this->nilai8Model
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->update(null, $dataUpdate); // Parameter pertama null untuk update semua yang sesuai kriteria

            if (!$updated) {
                session()->setFlashdata('err', 'Gagal mengupdate data approval');
                return redirect()->back();
            }

            // Hitung ulang rata-rata
            $rataRata = $this->nilai8Model->hitungRataRata(1, $kodebpr, $periodeId);
            $this->nilai8Model->insertOrUpdateRataRata($rataRata, 1, $kodebpr, $periodeId);

            session()->setFlashdata('message', 'Semua faktor8 berhasil disetujui.');
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
        $count = $this->nilai8Model
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
            $updated = $this->nilai8Model
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->update(null, $dataUpdate); // Parameter pertama null untuk update semua yang sesuai kriteria

            if (!$updated) {
                session()->setFlashdata('err', 'Gagal mengupdate data approval');
                return redirect()->back();
            }

            // Hitung ulang rata-rata
            $rataRata = $this->nilai8Model->hitungRataRata(1, $kodebpr, $periodeId);
            $this->nilai8Model->insertOrUpdateRataRata($rataRata, 1, $kodebpr, $periodeId);

            session()->setFlashdata('message', 'Semua faktor8 berhasil disetujui.');
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
            $updated = $this->nilai8Model
                ->where('faktor8id', 6)  // Only update where faktor8id = 6
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->update(null, $dataUpdate); // Parameter pertama null untuk update semua yang sesuai kriteria

            if (!$updated) {
                session()->setFlashdata('err', 'Gagal mengupdate data approval untuk faktor8');
                return redirect()->back();
            }

            // Optionally, recalculate the average (if needed)
            // $rataRata = $this->nilai8Model->hitungRataRata(1, $kodebpr, $periodeId);
            // $this->nilai8Model->insertOrUpdateRataRata($rataRata, 1, $kodebpr, $periodeId);

            session()->setFlashdata('message', 'Faktor 8 berhasil di-unapprove.');
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
        $faktor8id = $this->request->getPost('faktor8id');
        $kodebpr = $this->request->getPost('kodebpr');
        $periodeId = $this->request->getPost('periode_id');

        if (!$faktor8id || !$kodebpr || !$periodeId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak lengkap']);
        }

        // Ambil data faktor8 berdasarkan faktor8id, kodebpr, dan periode_id
        $nilaiFaktor8 = $this->nilai8Model
            ->where('faktor8id', $faktor8id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if (!$nilaiFaktor8) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak ditemukan']);
        }

        $dataUpdateForFaktor8 = [
            'accdekom' => 0,
            'is_approved' => 0,
            'accdekom_by' => service('authentication')->id(), // ID pengguna yang menyetujui
            'accdekom_at' => date('Y-m-d H:i:s'),
        ];

        // Update record untuk faktor1id 12
        $this->nilai8Model->where('faktor8id', 6)->update(null, $dataUpdateForFaktor8);

        // Update status approval
        $dataUpdate = [
            'accdekom' => 1,
            'accdekom_by' => service('authentication')->id(), // ID pengguna yang menyetujui
            'accdekom_at' => date('Y-m-d H:i:s'),
        ];

        // Update record dalam database
        if ($this->nilai8Model->update($nilaiFaktor8['id'], $dataUpdate)) {
            return redirect()->to('/faktor8')->with('message', 'Faktor berhasil disetujui');
        } else {
            return redirect()->to('/faktor8')->with('error', 'Gagal memperbarui data');
        }

    }

    public function unapprovedekom()
    {
        // Pastikan metode POST
        if ($this->request->getMethod() !== 'post') {
            return $this->response->setStatusCode(405, 'Method Not Allowed');
        }

        // Ambil data dari request
        $faktor8id = $this->request->getPost('faktor8id');
        $kodebpr = $this->request->getPost('kodebpr');
        $periodeId = $this->request->getPost('periode_id');

        if (!$faktor8id || !$kodebpr || !$periodeId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak lengkap']);
        }

        // Ambil data faktor8 berdasarkan faktor8id, kodebpr, dan periode_id
        $nilaiFaktor8 = $this->nilai8Model
            ->where('faktor8id', $faktor8id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if (!$nilaiFaktor8) {
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
        if ($this->nilai8Model->update($nilaiFaktor8['id'], $dataUpdate)) {
            // Update accdekom untuk faktor8id 6
            $this->nilai8Model->where('faktor8id', 6)
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->set(['accdekom' => 0])
                ->update();

            return redirect()->to('/faktor8')->with('message', 'Faktor belum disetujui dekom');
        } else {
            return redirect()->to('/faktor8')->with('error', 'Gagal memperbarui data');
        }

    }

    public function accdekom2()
    {
        // Pastikan metode POST
        if ($this->request->getMethod() !== 'post') {
            return $this->response->setStatusCode(405, 'Method Not Allowed');
        }

        // Ambil data dari request
        $faktor8id = $this->request->getPost('faktor8id');
        $kodebpr = $this->request->getPost('kodebpr');
        $periodeId = $this->request->getPost('periode_id');

        if (!$faktor8id || !$kodebpr || !$periodeId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak lengkap']);
        }

        // Ambil data faktor8 berdasarkan faktor8id, kodebpr, dan periode_id
        $nilaiFaktor8 = $this->nilai8Model
            ->where('faktor8id', $faktor8id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if (!$nilaiFaktor8) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak ditemukan']);
        }

        // Update status approval
        $dataUpdate = [
            'accdekom2' => 1,
            'accdekom2_by' => service('authentication')->id(), // ID pengguna yang menyetujui
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        // Update record dalam database
        if ($this->nilai8Model->update($nilaiFaktor8['id'], $dataUpdate)) {
            return redirect()->to('/faktor8')->with('message', 'Faktor berhasil disetujui');
        } else {
            return redirect()->to('/faktor8')->with('error', 'Gagal memperbarui data');
        }

    }

    public function unapprovedekom2()
    {
        // Pastikan metode POST
        if ($this->request->getMethod() !== 'post') {
            return $this->response->setStatusCode(405, 'Method Not Allowed');
        }

        // Ambil data dari request
        $faktor8id = $this->request->getPost('faktor8id');
        $kodebpr = $this->request->getPost('kodebpr');
        $periodeId = $this->request->getPost('periode_id');

        if (!$faktor8id || !$kodebpr || !$periodeId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak lengkap']);
        }

        // Ambil data faktor8 berdasarkan faktor8id, kodebpr, dan periode_id
        $nilaiFaktor8 = $this->nilai8Model
            ->where('faktor8id', $faktor8id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if (!$nilaiFaktor8) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak ditemukan']);
        }

        // Update status approval
        $dataUpdate = [
            'accdekom2' => 0,
            'accdekom2_by' => service('authentication')->id(), // ID pengguna yang menyetujui
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        // Update record dalam database
        if ($this->nilai8Model->update($nilaiFaktor8['id'], $dataUpdate)) {
            return redirect()->to('/faktor8')->with('message', 'Faktor belum disetujui dekom');
        } else {
            return redirect()->to('/faktor8')->with('error', 'Gagal memperbarui data');
        }

    }

    public function accdir2()
    {
        // Pastikan metode POST
        if ($this->request->getMethod() !== 'post') {
            return $this->response->setStatusCode(405, 'Method Not Allowed');
        }

        // Ambil data dari request
        $faktor8id = $this->request->getPost('faktor8id');
        $kodebpr = $this->request->getPost('kodebpr');
        $periodeId = $this->request->getPost('periode_id');

        if (!$faktor8id || !$kodebpr || !$periodeId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak lengkap']);
        }

        // Ambil data faktor8 berdasarkan faktor8id, kodebpr, dan periode_id
        $nilaiFaktor8 = $this->nilai8Model
            ->where('faktor8id', $faktor8id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if (!$nilaiFaktor8) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak ditemukan']);
        }

        // Update status approval
        $dataUpdate = [
            'accdir2' => 1,
            'accdir2_by' => service('authentication')->id(), // ID pengguna yang menyetujui
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        // Update record dalam database
        if ($this->nilai8Model->update($nilaiFaktor8['id'], $dataUpdate)) {
            return redirect()->to('/faktor8')->with('message', 'Faktor berhasil disetujui');
        } else {
            return redirect()->to('/faktor8')->with('error', 'Gagal memperbarui data');
        }

    }

    public function unapprovedir2()
    {
        // Pastikan metode POST
        if ($this->request->getMethod() !== 'post') {
            return $this->response->setStatusCode(405, 'Method Not Allowed');
        }

        // Ambil data dari request
        $faktor8id = $this->request->getPost('faktor8id');
        $kodebpr = $this->request->getPost('kodebpr');
        $periodeId = $this->request->getPost('periode_id');

        if (!$faktor8id || !$kodebpr || !$periodeId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak lengkap']);
        }

        // Ambil data faktor8 berdasarkan faktor8id, kodebpr, dan periode_id
        $nilaiFaktor8 = $this->nilai8Model
            ->where('faktor8id', $faktor8id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if (!$nilaiFaktor8) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak ditemukan']);
        }

        // Update status approval
        $dataUpdate = [
            'accdir2' => 0,
            'accdir2_by' => service('authentication')->id(), // ID pengguna yang menyetujui
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        // Update record dalam database
        if ($this->nilai8Model->update($nilaiFaktor8['id'], $dataUpdate)) {
            return redirect()->to('/faktor8')->with('message', 'Faktor belum disetujui dekom');
        } else {
            return redirect()->to('/faktor8')->with('error', 'Gagal memperbarui data');
        }

    }

    public function checkAccDekomApproval()
    {
        // Get the user and periode
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        // Check if accdekom is 1 for faktor8id 1 to 9
        $allApproved = true;
        for ($faktor8Id = 1; $faktor8Id <= 9; $faktor8Id++) {
            $nilaiFaktor8 = $this->nilai8Model
                ->where('faktor8id', $faktor8Id)
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->first();

            // If accdekom is not 1, set $allApproved to false and break
            if (!$nilaiFaktor8 || $nilaiFaktor8['accdekom'] != 1) {
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

        $faktor8Id = $this->request->getGet('faktor8_id');
        $kodebpr = $this->userKodebpr;
        $userId = user_id();
        $periodeId = session('active_periode');

        if (!$faktor8Id || !$kodebpr || !$userId || !$periodeId) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Missing data.']);
        }

        $count = $this->commentReads8Model->countUnreadCommentsForUserByFactor($faktor8Id, $kodebpr, $userId, $periodeId);

        return $this->response->setJSON(['unread_count' => $count]);
    }


    public function markUserCommentsAsRead()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $faktor8Id = $this->request->getPost('faktor8_id');
        $kodebpr = $this->userKodebpr; // Get from property
        $userId = user_id();
        $periodeId = session('active_periode');

        if (!$faktor8Id || !$kodebpr || !$userId || !$periodeId) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Missing data.']);
        }

        // Get all comment IDs for this factor, kodebpr, periode, and not by the current user
        $commentsToMark = $this->komentarModel->select('id')
            ->where('faktor8id', $faktor8Id)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('user_id !=', $userId) // Mark comments from others as read
            ->findAll();

        if (!empty($commentsToMark)) {
            foreach ($commentsToMark as $comment) {
                $this->commentReads8Model->markAsRead($comment['id'], $userId);
            }
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Comments marked as read for this user.']);
    }


    // Make sure your saveKomentar also sets is_read to 0 for new comments:
    public function saveKomentar()
    {
        $data = [
            'faktor8id' => $this->request->getPost('faktor8_id'),
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
