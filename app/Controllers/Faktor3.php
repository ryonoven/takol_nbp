<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_faktor3;
use App\Models\M_user;
use Myth\Auth\Config\Services as AuthServices;

class Faktor3 extends Controller
{
    protected $model;
    protected $usermodel;
    protected $auth;
    protected $faktor3Model;
    protected $session;
    public function __construct()
    {
        // Most services in this controller require
        // the session to be started - so fire it up!
        date_default_timezone_set('Asia/Jakarta');
        $this->model = new M_faktor3();
        $this->faktor3Model = new M_faktor3();
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

        $faktor3Data = $this->faktor3Model->getAllData();

        $authorize = AuthServices::authorization();
        $userInGroupPE = $authorize->inGroup('pe', $this->auth->id());
        $userInGroupAdmin = $authorize->inGroup('admin', $this->auth->id());
        $userInGroupDekom = $authorize->inGroup('dekom', $this->auth->id());
        $userInGroupDireksi = $authorize->inGroup('direksi', $this->auth->id());

        $data = [
            'judul' => 'Faktor 3',
            'faktor3' => $faktor3Data,
            //'faktor3' => $this->model->getAllData(),
            'userInGroupPE' => $userInGroupPE,
            'userInGroupAdmin' => $userInGroupAdmin,
            'userInGroupDekom' => $userInGroupDekom,
            'userInGroupDireksi' => $userInGroupDireksi,
            'fullname' => $fullname,
        ];

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('faktor3/index', $data);
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
                    'judul' => 'Faktor 3',
                    'faktor3' => $this->model->getAllData()
                ];
                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('faktor3/index', $data);
                //echo view('templates/v_footer');
            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'komentar' => $this->request->getPost('komentar'),
                ];

                // Insert Data
                $success = $this->model->tambahKomentar($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil ditambahkan');
                    return redirect()->to(base_url('faktor3'));
                }
            }
        } else {
            return redirect()->to(base_url('faktor3'));
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
                        'required' => '{field} tidak boleh kosong'
                    ]
                ],
                'keterangan' => [
                    'label' => 'Keterangan',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong'
                    ]
                ],
            ]);
            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Faktor 3',
                    'faktor3' => $this->model->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('faktor3/index', $data);
                //echo view('templates/v_footer');
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
                    return redirect()->to(base_url('faktor3'));
                }
            }
        } else {
            return redirect()->to(base_url('faktor3'));
        }

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

        return redirect()->to(base_url('faktor3'));
    }

    public function approve($idFaktor3)
    {
        if (!is_numeric($idFaktor3) || $idFaktor3 <= 0) {
            session()->setFlashdata('err', 'ID Faktor tidak valid.');
            return redirect()->back();
        }

        $faktor = $this->faktor3Model->find($idFaktor3);
        if (!$faktor) {
            session()->setFlashdata('err', 'Data Faktor dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }
        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idFaktor3,
            'is_approved' => 1,  // Approved
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->faktor3Model->save($dataUpdate)) {
            session()->setFlashdata('message', 'Faktor berhasil disetujui.');
            return redirect()->back();
        } else {
            session()->setFlashdata('err', 'Terjadi kesalahan saat melakukan approval.');
            return redirect()->back();
        }
    }

    public function unapprove($idFaktor3)
    {
        if (!is_numeric($idFaktor3) || $idFaktor3 <= 0) {
            session()->setFlashdata('err', 'ID Faktor tidak valid.');
            return redirect()->back();
        }

        $faktor = $this->faktor3Model->find($idFaktor3);
        if (!$faktor) {
            session()->setFlashdata('err', 'Data Faktor dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idFaktor3,
            'is_approved' => 2,  // Unapproved
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->faktor3Model->save($dataUpdate)) {
            session()->setFlashdata('err', 'Approval faktor berhasil dibatalkan.');
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
            'is_approved' => 1,  // Approved
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        // Update all records
        if ($this->faktor3Model->builder()->update($dataUpdate)) {
            session()->setFlashdata('message', 'Semua faktor berhasil disetujui.');
        } else {
            session()->setFlashdata('err', 'Terjadi kesalahan saat menyetujui semua faktor.');
        }

        return redirect()->back();
    }

    public function unapproveSemua()
    {
        date_default_timezone_set('Asia/Jakarta');
        $userId = service('authentication')->id();
        $dataUpdate = [
            'is_approved' => 2,  // Unapproved
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        // Update all records
        if ($this->faktor3Model->builder()->update($dataUpdate)) {
            session()->setFlashdata('err', 'Semua approval faktor berhasil dibatalkan.');
        } else {
            session()->setFlashdata('err', 'Terjadi kesalahan saat membatalkan approval semua faktor.');
        }

        return redirect()->back();
    }

    public function excel()
    {
        $data = [
            'faktor3' => $this->model->getAllData()
        ];

        echo view('faktor3/excel', $data);

    }

}