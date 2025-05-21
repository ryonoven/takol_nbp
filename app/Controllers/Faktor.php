<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_faktor;
use App\Models\M_user;
use App\Models\M_faktorkomentar;
use Myth\Auth\Config\Services as AuthServices;

class Faktor extends Controller
{
    protected $auth;
    protected $faktorModel; // Ini sudah benar
    protected $userModel;   // Tambahkan ini jika belum ada
    protected $komentarModel;
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
        helper('url');
        $this->session = service('session');
        $this->auth = service('authentication');

        // Pastikan service AuthServices di-instansiasi hanya sekali jika diperlukan
        $authorize = AuthServices::authorization();

        // Inisialisasi status grup pengguna di constructor
        $this->userInGroupPE = $authorize->inGroup('pe', $this->auth->id());
        $this->userInGroupAdmin = $authorize->inGroup('admin', $this->auth->id());
        $this->userInGroupDekom = $authorize->inGroup('dekom', $this->auth->id());
        $this->userInGroupDireksi = $authorize->inGroup('direksi', $this->auth->id());
    }

    public function index()
    {
        // date_default_timezone_set('Asia/Jakarta'); // Sudah di constructor

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $userId = $this->auth->id();
        $user = $this->userModel->find($userId);
        $fullname = $user['fullname'] ?? 'Unknown';

        $faktorData = $this->faktorModel->getAllData();

        $data = [
            'judul' => 'Faktor 1',
            'faktor' => $faktorData,
            'userId' => $userId,
            // 'komentarList' => $komentarList, // Hapus ini atau set ke array kosong
            'komentarList' => [], // Untuk memastikan tidak ada komentar statis yang terkirim ke modal
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
        // echo view('templates/v_footer');
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
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        if (isset($_POST['ubah'])) {
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
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Faktor',
                    'faktor' => $this->faktorModel->getAllData() // <<< PERBAIKI DI SINI: $this->model -> $this->faktorModel
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('faktor/index', $data);
                echo view('templates/v_footer');
            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'nilai' => $this->request->getPost('nilai'),
                    'keterangan' => $this->request->getPost('keterangan')
                ];

                $success = $this->faktorModel->ubah($data, $id); // <<< PERBAIKI DI SINI: $this->model -> $this->faktorModel
                if ($success) {
                    session()->setFlashdata('message', 'Faktor berhasil diubah');
                    return redirect()->to(base_url('faktor'));
                }
            }
        } else {
            return redirect()->to(base_url('faktor'));
        }
    }

    public function excel()
    {
        $data = [
            'faktor' => $this->faktorModel->getAllData() // <<< PERBAIKI DI SINI: $this->model -> $this->faktorModel
        ];

        echo view('faktor/excel', $data);
    }

    public function setNullKolom($id)
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $success = $this->faktorModel->setNullKolom($id); // <<< PERBAIKI DI SINI: $this->model -> $this->faktorModel

        if ($success) {
            session()->setFlashdata('message', 'Data berhasil dihapus');
        } else {
            session()->setFlashdata('err', 'Data gagal dihapus');
        }

        return redirect()->to(base_url('faktor'));
    }

    // Fungsi approve dan unapprove sudah menggunakan $this->faktorModel dengan benar
    public function approve($idFaktor)
    {
        if (!is_numeric($idFaktor) || $idFaktor <= 0) {
            session()->setFlashdata('err', 'ID Faktor tidak valid.');
            return redirect()->back();
        }

        $faktor = $this->faktorModel->find($idFaktor);
        if (!$faktor) {
            session()->setFlashdata('err', 'Data Faktor dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');
        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idFaktor,
            'is_approved' => 1,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->faktorModel->save($dataUpdate)) {
            session()->setFlashdata('message', 'Faktor berhasil disetujui.');
            return redirect()->back();
        } else {
            session()->setFlashdata('err', 'Terjadi kesalahan saat melakukan approval.');
            return redirect()->back();
        }
    }

    public function unapprove($idFaktor)
    {
        if (!is_numeric($idFaktor) || $idFaktor <= 0) {
            session()->setFlashdata('err', 'ID Faktor tidak valid.');
            return redirect()->back();
        }

        $faktor = $this->faktorModel->find($idFaktor);
        if (!$faktor) {
            session()->setFlashdata('err', 'Data Faktor dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');
        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idFaktor,
            'is_approved' => 2,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->faktorModel->save($dataUpdate)) {
            session()->setFlashdata('err', 'Approval faktor dibatalkan.');
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

        $this->faktorModel->builder()->update($dataUpdate);

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

        $this->faktorModel->builder()->update($dataUpdate);

        session()->setFlashdata('err', 'Semua approval faktor dibatalkan.');
        return redirect()->back();
    }

}