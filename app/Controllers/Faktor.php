<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_faktor;
use App\Models\M_user;
use Myth\Auth\Config\Services as AuthServices;

class Faktor extends Controller
{
    protected $model;
    protected $usermodel;
    protected $auth;
    protected $faktorModel;
    protected $session;

    public function __construct()
    {
        $this->model = new M_faktor();
        $this->faktorModel = new M_faktor();
        $this->userModel = new M_user();
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
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()->to($redirectURL);
        }

        $userId = $this->auth->id(); // ambil ID user yang login
        $user = $this->userModel->find($userId); // ambil data user

        $fullname = $user['fullname'] ?? 'Unknown';

        $faktorData = $this->faktorModel->getAllData();

        $authorize = AuthServices::authorization();
        $userInGroupPE = $authorize->inGroup('pe', $this->auth->id());
        $userInGroupAdmin = $authorize->inGroup('admin', $this->auth->id());
        $userInGroupDekom = $authorize->inGroup('dekom', $this->auth->id());
        $userInGroupDireksi = $authorize->inGroup('direksi', $this->auth->id());

        $data = [
            'judul' => 'Faktor 1',
            'faktor' => $faktorData,
            // 'faktor' => $this->faktorModel->getAllData(),
            'userInGroupPE' => $userInGroupPE,
            'userInGroupAdmin' => $userInGroupAdmin,
            'userInGroupDekom' => $userInGroupDekom,
            'userInGroupDireksi' => $userInGroupDireksi,
            'fullname' => $fullname,
        ];

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('faktor/index', $data);
        echo view('templates/v_footer');
        $userId = service('authentication')->id();
        $data['userInGroupPE'] = service('authorization')->inGroup('pe', $userId);
        $data['userInGroupAdmin'] = service('authorization')->inGroup('admin', $userId);
        $data['userInGroupDekom'] = service('authorization')->inGroup('dekom', $userId);
        $data['userInGroupDireksi'] = service('authorization')->inGroup('direksi', $userId);

    }

    public function tambahKomentar()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
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
                $data = [
                    'judul' => 'Faktor',
                    'faktor' => $this->model->getAllData(),
                    'user' => $this->model->getAllData(),
                ];
                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('faktor/index', $data);
                echo view('templates/v_footer');
            } else {
                $data = [
                    'fullname' => $this->request->getPost('fullname'),
                    'date' => $this->request->getPost('date'),
                    'komentar' => $this->request->getPost('komentar'),
                ];

                // Insert Data
                $this->model->checkIncrement();
                $success = $this->model->tambahKomentar($data);
                if ($success) {
                    session()->setFlashdata('message', 'Komentar berhasil ditambahkan');
                    return redirect()->to(base_url('faktor'));
                }
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

            return redirect()
                ->to($redirectURL);
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

                //Update data
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

// function menghapus data
// public function hapus($id)
// {
//     if (!$this->auth->check()) {
//         $redirectURL = session('redirect_url') ?? '/login';
//         unset($_SESSION['redirect_url']);

//         return redirect()->to($redirectURL);
//     }

//     // Memanggil fungsi hapus pada model dan menyimpan hasilnya dalam variabel $success
//     $this->model->hapus($id);
//     session()->setFlashdata('message', 'Data berhasil dihapus');

//     // Redirect pengguna ke halaman "/faktor"
//     return redirect()->to(base_url('faktor'));

// }