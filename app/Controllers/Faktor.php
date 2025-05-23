<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_faktor;
use App\Models\M_user;
use App\Models\M_faktorkomentar;
use App\Models\M_nilaifaktor;
use App\Models\M_infobpr;
use Myth\Auth\Config\Services as AuthServices;

class Faktor extends Controller
{
    // Variabel yang dihapus karena tidak digunakan atau diganti dengan yang lebih spesifik
    // protected $model; 
    // protected $usermodel; // Diganti dengan $this->userModel

    protected $auth;
    protected $faktorModel; // Ini sudah benar
    protected $userModel;   // Tambahkan ini jika belum ada
    protected $komentarModel;
    protected $nilaiModel;
    protected $infobprModel;
    protected $session;

    // Tambahkan properti untuk grup pengguna di sini agar bisa diakses di semua method tanpa redeklarasi
    protected $userInGroupPE;
    protected $userInGroupAdmin;
    protected $userInGroupDekom;
    protected $userInGroupDireksi;

    public function __construct()
    {
        // PENTING: Panggil constructor parent di awal
        date_default_timezone_set('Asia/Jakarta');
        $this->faktorModel = new M_faktor();
        $this->userModel = new M_user(); // Pastikan inisialisasi M_user
        $this->komentarModel = new M_faktorkomentar();
        $this->nilaiModel = new M_nilaifaktor();
        $this->infobprModel = new M_infobpr();
        helper('url');
        $this->session = service('session');
        $this->auth = service('authentication');

        $authorize = AuthServices::authorization();

        $this->userInGroupPE = $authorize->inGroup('pe', $this->auth->id());
        $this->userInGroupAdmin = $authorize->inGroup('admin', $this->auth->id());
        $this->userInGroupDekom = $authorize->inGroup('dekom', $this->auth->id());
        $this->userInGroupDireksi = $authorize->inGroup('direksi', $this->auth->id());
    }

    public function index()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }


        $userId = $this->auth->id();
        $user = $this->userModel->find($userId);
        $infobprId = $this->auth->id();
        $infobpr = $this->infobprModel->find($infobprId);
        $fullname = $user['fullname'] ?? 'Unknown';

        $faktorData = $this->faktorModel->getAllData();
        $factorsWithDetails = [];

        foreach ($faktorData as $faktorItem) {
            $faktorId = $faktorItem['id'];
            $associatedNilai = $this->nilaiModel->where('faktor1id', $faktorId)
                ->orderBy('created_at', 'DESC') // Urutkan untuk mendapatkan yang terbaru
                ->first(); // Ambil hanya 1 record (yang terbaru)

            // Buat array baru yang berisi gabungan data
            $factorsWithDetails[] = [
                'id' => $faktorItem['id'],
                'sph' => $faktorItem['sph'],
                'category' => $faktorItem['category'],
                'sub_category' => $faktorItem['sub_category'],
                'nilai' => $associatedNilai['nilai'] ?? null,         // Ambil nilai, atau null jika tidak ada
                'keterangan' => $associatedNilai['keterangan'] ?? null,
                'bpr_id' => $associatedNilai['id'] ?? null,    // Ambil keterangan, atau null jika tidak ada
                'is_approved' => $associatedNilai['is_approved'] ?? null,   // Ambil is_approved, atau null jika tidak ada
                // Tambahkan kolom lain dari faktorItem jika dibutuhkan
                // 'nama_kolom_lain_faktor' => $faktorItem['nama_kolom_lain_faktor'],
            ];
        }

        $rataRata = $this->nilaiModel->hitungRataRata($faktorId);

        $data = [
            'judul' => 'Faktor 1',
            'faktor' => $faktorData,
            'userId' => $userId,
            'faktors' => $factorsWithDetails,
            'rataRata' => $rataRata,
            // 'komentarList' => $komentarList, // Hapus ini atau set ke array kosong
            'komentarList' => [],
            // 'nilaiList' => $nilaiData,
            'userInGroupPE' => $this->userInGroupPE, // Gunakan properti yang sudah diinisialisasi
            'userInGroupAdmin' => $this->userInGroupAdmin,
            'userInGroupDekom' => $this->userInGroupDekom,
            'userInGroupDireksi' => $this->userInGroupDireksi,
            // 'faktorId' => $faktorId, // Tidak perlu dikirim ke view jika tidak digunakan
            'fullname' => $fullname,
        ];

        // Pastikan $data dikirimkan ke view
        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('faktor/index', $data);
        echo view('templates/v_footer');
    }

    // Fungsi untuk AJAX request komentar
    public function getKomentarByFaktorId($faktorId)
    {
        if (!$this->request->isAJAX() || !is_numeric($faktorId)) {
            // log_message('debug', 'Invalid AJAX or faktorId: ' . $faktorId); // Tambahkan log untuk debug
            return $this->response->setStatusCode(404)->setBody('Not Found');
        }

        $komentarList = $this->komentarModel->getKomentarByFaktorId($faktorId);

        // log_message('debug', 'Comments returned for faktorId ' . $faktorId . ': ' . json_encode($komentarList)); // Tambahkan log untuk debug
        return $this->response->setJSON($komentarList);
    }

    public function getNilaiByFaktorId($faktorId)
    {
        if (!$this->request->isAJAX() || !is_numeric($faktorId)) {
            // log_message('debug', 'Invalid AJAX or faktorId: ' . $faktorId); // Tambahkan log untuk debug
            return $this->response->setStatusCode(404)->setBody('Not Found');
        }

        $nilaiList = $this->nilaiModel->getNilaiByFaktorId($faktorId);

        // log_message('debug', 'Comments returned for faktorId ' . $faktorId . ': ' . json_encode($komentarList)); // Tambahkan log untuk debug
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
                $userId = service('authentication')->id();
                $faktor1Id = $this->request->getPost('faktor_id');
                $infobprId = service('authentication')->id();
                $id = $this->request->getPost('id');
                $data = [
                    'faktor1id' => $faktor1Id,
                    'nilai' => $this->request->getPost('nilai'),
                    'keterangan' => $this->request->getPost('keterangan'),
                    'fullname' => $this->request->getPost('fullname'),
                    'user_id' => $userId,
                    'bpr_id' => $infobprId,
                    'created_at' => date('Y-m-d H:i:s'),
                ];

                $this->nilaiModel->tambahNilai($data);

                $rataRata = $this->nilaiModel->hitungRataRata($faktor1Id);
                $this->nilaiModel->updateRataRata($faktor1Id, $rataRata);

                session()->setFlashdata('message', 'Nilai berhasil ditambahkan');
                return redirect()->to(base_url('faktor') . '?modal_nilai=' . $faktor1Id);
            }
        } else {
            return redirect()->to(base_url('faktor'));
        }
    }

    public function tambahKomentar()
    {
        date_default_timezone_set('Asia/Jakarta');
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        if (isset($_POST['tambahKomentar'])) {
            $val = $this->validate([
                'komentar' => [
                    'label' => 'Komentar',
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
                $userId = service('authentication')->id();
                $faktor1Id = $this->request->getPost('faktor_id');
                $data = [
                    'faktor1id' => $faktor1Id,
                    'komentar' => $this->request->getPost('komentar'),
                    'fullname' => $this->request->getPost('fullname'),
                    'user_id' => $userId,
                    'created_at' => date('Y-m-d H:i:s'),
                ];

                $this->komentarModel->insertKomentar($data);

                session()->setFlashdata('message', 'Komentar berhasil ditambahkan');
                // Redirect kembali ke halaman dengan ID faktor yang sama agar modal bisa dibuka lagi
                return redirect()->to(base_url('faktor') . '?modal_komentar=' . $faktor1Id);
            }
        } else {
            return redirect()->to(base_url('faktor'));
        }
    }

    public function ubah()
    {
        // Cek apakah ada faktor1id
        $faktor1id = $this->request->getPost('faktor1id');

        if (!$faktor1id) {
            session()->setFlashdata('err', 'ID Faktor tidak ditemukan.');
            return redirect()->to(base_url('faktor'));
        }

        // Ambil data yang akan diubah
        $data = [
            'nilai' => $this->request->getPost('nilai'),
            'keterangan' => $this->request->getPost('keterangan'),
        ];

        // Update data berdasarkan faktor1id
        if ($this->nilaiModel->ubahBerdasarkanFaktor1Id($data, $faktor1id)) {
            session()->setFlashdata('message', 'Data berhasil diubah');
        } else {
            session()->setFlashdata('err', 'Gagal mengubah data');
        }

        return redirect()->to(base_url('faktor'));
    }

    public function excel()
    {
        $data = [
            'faktor' => $this->faktorModel->getAllData() // <<< PERBAIKI DI SINI: $this->model -> $this->faktorModel
        ];

        echo view('faktor/excel', $data);
    }

    public function hapus($id)
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()->to($redirectURL);
        }

        // Memanggil fungsi hapus pada model dan menyimpan hasilnya dalam variabel $success
        $this->nilaiModel->hapus($id);
        session()->setFlashdata('message', 'Data berhasil dihapus');

        return redirect()->to(base_url('faktor'));

    }

    public function setNullKolom($id)
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $success = $this->faktorModel->setNullKolom($id);

        if ($success) {
            session()->setFlashdata('message', 'Data berhasil dihapus');
        } else {
            session()->setFlashdata('err', 'Data gagal dihapus');
        }

        return redirect()->to(base_url('faktor'));
    }

    // Fungsi approve dan unapprove sudah menggunakan $this->faktorModel dengan benar
    public function approve($idNilai = null)
    {
        if ($idNilai === null) {
            session()->setFlashdata('err', 'ID Faktor tidak valid.');
            return redirect()->back();
        }

        // Ambil data dari tabel nilaifaktor berdasarkan faktor_id
        $nilaiFaktor = $this->nilaiModel->find($idNilai);
        if (!$nilaiFaktor) {
            session()->setFlashdata('err', 'Data tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');
        $userId = service('authentication')->id();

        $dataUpdate = [
            'is_approved' => 1,  // Status disetujui
            'approved_by' => $userId,  // Menyimpan siapa yang memberikan approval
            'approved_at' => date('Y-m-d H:i:s'),  // Waktu persetujuan
        ];

        // Update status approval di tabel nilaifaktor
        if ($this->nilaiModel->update($idNilai, $dataUpdate)) {
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

        // Ambil data dari tabel nilaifaktor berdasarkan faktor_id
        $nilaiFaktor = $this->nilaiModel->find($idNilai);
        if (!$nilaiFaktor) {
            session()->setFlashdata('err', 'Data tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');
        $userId = service('authentication')->id();

        // Data untuk diupdate
        $dataUpdate = [
            'is_approved' => 2,  // Status tidak disetujui
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s')
        ];

        // Update status approval di tabel nilaifaktor
        if ($this->nilaiModel->update($idNilai, $dataUpdate)) {
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
        $dataUpdate = [
            'is_approved' => 1,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        $this->nilaiModel->builder()->update($dataUpdate);

        session()->setFlashdata('message', 'Semua faktor berhasil disetujui.');
        return redirect()->back();
    }

    public function unapproveSemua()
    {
        date_default_timezone_set('Asia/Jakarta');
        $userId = service('authentication')->id();
        $dataUpdate = [
            'is_approved' => 2,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        $this->nilaiModel->builder()->update($dataUpdate);

        session()->setFlashdata('err', 'Semua approval faktor dibatalkan.');
        return redirect()->back();
    }

}
