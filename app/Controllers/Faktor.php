<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_faktor;
use App\Models\M_user;
use App\Models\M_faktorkomentar;
use Myth\Auth\Config\Services as AuthServices;

class Faktor extends Controller
{
    protected $model;
    protected $usermodel;
    protected $auth;
    protected $faktorModel;
    protected $komentarModel;
    protected $session;

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->model = new M_faktor();
        $this->faktorModel = new M_faktor();
        $this->userModel = new M_user();
        $this->komentarModel = new M_faktorkomentar();
        $this->session = service('session');
        $this->auth = service('authentication');
        $auth = AuthServices::authentication();
        $authorize = AuthServices::authorization();

        $userInGroupPE = $authorize->inGroup('pe', $auth->id());
        $userInGroupAdmin = $authorize->inGroup('admin', $auth->id());
        $userInGroupDekom = $authorize->inGroup('dekom', $auth->id());
        $userInGroupDireksi = $authorize->inGroup('direksi', $auth->id());

        $data['userInGroupPE'] = $userInGroupPE;
        $data['userInGroupAdmin'] = $userInGroupAdmin;
        $data['userInGroupDekom'] = $userInGroupDekom;
        $data['userInGroupDireksi'] = $userInGroupDireksi;
    }

    public function index()
    {
        date_default_timezone_set('Asia/Jakarta');
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $userId = $this->auth->id(); // ambil ID user yang login
        $user = $this->userModel->find($userId); // ambil data user
        $fullname = $user['fullname'] ?? 'Unknown';

        // Ambil semua data faktor
        $faktorData = $this->faktorModel->getAllData();

        // Ambil semua komentar untuk faktor yang terkait
        $komentarList = $this->komentarModel->getAllData();

        $authorize = AuthServices::authorization();
        $userInGroupPE = $authorize->inGroup('pe', $this->auth->id());
        $userInGroupAdmin = $authorize->inGroup('admin', $this->auth->id());
        $userInGroupDekom = $authorize->inGroup('dekom', $this->auth->id());
        $userInGroupDireksi = $authorize->inGroup('direksi', $this->auth->id());

        // Mengirimkan semua variabel ke view
        $data = [
            'judul' => 'Faktor 1',
            'faktor' => $faktorData,
            'userId' => $userId, // Pastikan ini sudah ada
            'komentarList' => $komentarList,
            'userInGroupPE' => $userInGroupPE,
            'userInGroupAdmin' => $userInGroupAdmin,
            'userInGroupDekom' => $userInGroupDekom,
            'userInGroupDireksi' => $userInGroupDireksi,
            'fullname' => $fullname,
        ];

        // Pastikan $data dikirimkan ke view
        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('faktor/index', $data);  // Pastikan $data dikirim ke view
        echo view('templates/v_footer');
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
                return redirect()->back(); // Kembali ke form jika validasi gagal
            } else {
                // Ambil data dari form
                $userId = service('authentication')->id();
                $faktor1Id = $this->request->getPost('faktor_id');
                $data = [
                    'faktor1id' => $faktor1Id,
                    //'user_id' => $this->request->getPost('user_id'), // Ambil user_id yang dikirimkan
                    'komentar' => $this->request->getPost('komentar'),
                    'fullname' => $this->request->getPost('fullname'),
                    'user_id' => $userId,
                    'created_at' => date('Y-m-d H:i:s'), // Timestamp saat komentar ditambahkan
                ];

                $this->komentarModel->insertKomentar($data); // Menyimpan data komentar

                session()->setFlashdata('message', 'Komentar berhasil ditambahkan');
                return redirect()->to(base_url('faktor')); // Redirect setelah komentar ditambahkan
            }
        } else {
            return redirect()->to(base_url('faktor')); // Kembali ke halaman faktor jika tidak ada data POST
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
                    'faktor' => $this->model->getAllData()
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

                // Update data
                $success = $this->model->ubah($data, $id);
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
            'faktor' => $this->model->getAllData()
        ];

        echo view('faktor/excel', $data);
    }

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

    public function setNullKolom($id)
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        // Mengatur kolom 'nilai' dan 'keterangan' menjadi NULL untuk ID tertentu
        $success = $this->model->setNullKolom($id);

        if ($success) {
            session()->setFlashdata('message', 'Data berhasil dihapus');
        } else {
            session()->setFlashdata('err', 'Data gagal dihapus');
        }

        return redirect()->to(base_url('faktor'));
    }
}
