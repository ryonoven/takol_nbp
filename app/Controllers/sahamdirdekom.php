<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_sahamdirdekom;
use App\Models\M_tgjwbdir;
use App\Models\M_tgjwbdekom;
use App\Models\M_user;
use Myth\Auth\Config\Services as AuthServices;

class sahamdirdekom extends Controller
{
    protected $model;
    protected $sahamdirdekomModel;
    protected $tgjwbdirModel;
    protected $tgjwbdekomModel;
    protected $usermodel;
    protected $session;
    protected $auth;

    public function __construct()
    {
        $this->sahamdirdekomModel = new M_sahamdirdekom();
        $this->tgjwbdirModel = new M_tgjwbdir();
        $this->tgjwbdekomModel = new M_tgjwbdekom();
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

        $userId = $this->auth->id();
        $user = $this->userModel->find($userId);

        $fullname = $user['fullname'] ?? 'Unknown';

        $sahamdirdekomData = $this->sahamdirdekomModel->getAllData();

        $authorize = AuthServices::authorization();
        $userInGroupPE = $authorize->inGroup('pe', $this->auth->id());
        $userInGroupAdmin = $authorize->inGroup('admin', $this->auth->id());
        $userInGroupDekom = $authorize->inGroup('dekom', $this->auth->id());
        $userInGroupDireksi = $authorize->inGroup('direksi', $this->auth->id());

        $data = [
            'judul' => '6. Kepemilikan Saham Anggota Direksi dan Dewan Komisaris pada BPR',
            'sahamdirdekom' => $sahamdirdekomData,
            //'sahamdirdekom' => $this->sahamdirdekomModel->getAllData(),
            'tgjwbdir' => $this->tgjwbdirModel->getAllData(),
            'tgjwbdekom' => $this->tgjwbdekomModel->getAllData(),
            'userInGroupPE' => $userInGroupPE,
            'userInGroupAdmin' => $userInGroupAdmin,
            'userInGroupDekom' => $userInGroupDekom,
            'userInGroupDireksi' => $userInGroupDireksi,
            'fullname' => $fullname,
        ];

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('sahamdirdekom/index', $data);
        echo view('templates/v_footer');
    }

    public function tambahsahamdir()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        if (isset($_POST['tambahsahamdir'])) {
            $val = $this->validate([
                'direksi' => [
                    'label' => 'Nama Direksi',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'persensahamdir' => [
                    'label' => 'Persentase Kepemilikan (%):',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Kepemilikan Saham Anggota Direksi dan Dewan Komisaris pada BPR',
                    'sahamdirdekom' => $this->sahamdirdekomModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('sahamdirdekom/index', $data);
                echo view('templates/v_footer');
            } else {
                $data = [
                    'direksi' => $this->request->getPost('direksi'),
                    'persensahamdir' => $this->request->getPost('persensahamdir')
                ];

                // Insert data using the correct model
                $this->sahamdirdekomModel->checkIncrement();
                $success = $this->sahamdirdekomModel->tambahsahamdir($data);
                if ($success) {
                    session()->setFlashdata('message', 'Kepemilikan Saham Anggota Direksi pada BPR berhasil ditambahkan ');
                    return redirect()->to(base_url('sahamdirdekom'));
                }
            }
        } else {
            return redirect()->to(base_url('sahamdirdekom'));
        }
    }

    public function tambahsahamdekom()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        if (isset($_POST['tambahsahamdekom'])) {
            $val = $this->validate([
                'dekom' => [
                    'label' => 'Nama Dewan Komisaris',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'persensahamdekom' => [
                    'label' => 'Persentase Kepemilikan (%):',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Kepemilikan Saham Anggota Direksi dan Dewan Komisaris pada BPR',
                    'sahamdirdekom' => $this->sahamdirdekomModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('sahamdirdekom/index', $data);
                echo view('templates/v_footer');
            } else {
                $data = [
                    'dekom' => $this->request->getPost('dekom'),
                    'persensahamdekom' => $this->request->getPost('persensahamdekom'),
                ];

                // Insert data using the correct model
                $this->sahamdirdekomModel->checkIncrement();
                $success = $this->sahamdirdekomModel->tambahsahamdekom($data);
                if ($success) {
                    session()->setFlashdata('message', 'Kepemilikan Saham Anggota Dewan Komisaris pada BPR berhasil ditambahkan ');
                    return redirect()->to(base_url('sahamdirdekom'));
                }
            }
        } else {
            return redirect()->to(base_url('sahamdirdekom'));
        }
    }

    public function hapus($id)
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        // Memanggil fungsi hapus pada model yang benar
        $this->sahamdirdekomModel->hapus($id);
        session()->setFlashdata('message', 'Data berhasil dihapus');

        return redirect()->to(base_url('sahamdirdekom'));
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
                'direksi' => [
                    'label' => 'Nama Direksi',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'persensahamdir' => [
                    'label' => 'Persentase Kepemilikan (%):',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Kepemilikan Saham Anggota Direksi dan Dewan Komisaris pada BPR',
                    'sahamdirdekom' => $this->sahamdirdekomModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('sahamdirdekom/index', $data); // Memperbaiki pemanggilan view
                echo view('templates/v_footer');
            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'direksi' => $this->request->getPost('direksi'),
                    'persensahamdir' => $this->request->getPost('persensahamdir')
                ];

                // Update data menggunakan model yang benar
                $success = $this->sahamdirdekomModel->ubah($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Kepemilikan Saham Anggota Direksi dan Dewan Komisaris pada BPR berhasil diubah ');
                    return redirect()->to(base_url('sahamdirdekom'));
                }
            }
        } else {
            return redirect()->to(base_url('sahamdirdekom'));
        }
    }

    public function ubahdekom()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        if (isset($_POST['ubahdekom'])) {
            $val = $this->validate([
                'dekom' => [
                    'label' => 'Nama Anggota Dewan Komisaris:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'persensahamdekom' => [
                    'label' => 'Persentase Kepemilikan (%):',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Kepemilikan Saham Anggota Dewan Komisaris pada BPR',
                    'sahamdirdekom' => $this->sahamdirdekomModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('sahamdirdekom/index', $data); // Memperbaiki pemanggilan view
                echo view('templates/v_footer');
            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'dekom' => $this->request->getPost('dekom'),
                    'persensahamdekom' => $this->request->getPost('persensahamdekom')
                ];

                // Update data menggunakan model yang benar
                $success = $this->sahamdirdekomModel->ubahdekom($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Kepemilikan Saham Anggota Dewan Komisaris pada BPR berhasil diubah ');
                    return redirect()->to(base_url('sahamdirdekom'));
                }
            }
        } else {
            return redirect()->to(base_url('sahamdirdekom'));
        }
    }

    public function approve($idsahamdirdekom)
    {
        if (!is_numeric($idsahamdirdekom) || $idsahamdirdekom <= 0) {
            session()->setFlashdata('err', 'ID Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi tidak valid.');
            return redirect()->back();
        }

        $sahamdirdekom = $this->sahamdirdekomModel->find($idsahamdirdekom);
        if (!$sahamdirdekom) {
            session()->setFlashdata('err', 'Data Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idsahamdirdekom,
            'is_approved' => 1,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->sahamdirdekomModel->save($dataUpdate)) {
            session()->setFlashdata('message', 'Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi berhasil disetujui.');
            return redirect()->back();
        } else {
            session()->setFlashdata('err', 'Terjadi kesalahan saat melakukan approval.');
            return redirect()->back();
        }
    }

    public function unapprove($idsahamdirdekom)
    {
        if (!is_numeric($idsahamdirdekom) || $idsahamdirdekom <= 0) {
            session()->setFlashdata('err', 'ID Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi tidak valid.');
            return redirect()->back();
        }

        $sahamdirdekom = $this->sahamdirdekomModel->find($idsahamdirdekom);
        if (!$sahamdirdekom) {
            session()->setFlashdata('err', 'Data Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idsahamdirdekom,
            'is_approved' => 2,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->sahamdirdekomModel->save($dataUpdate)) {
            session()->setFlashdata('err', 'Approval Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi dibatalkan.');
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

        $this->sahamdirdekomModel->builder()->update($dataUpdate);

        session()->setFlashdata('message', 'Semua Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi berhasil disetujui.');
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

        $this->sahamdirdekomModel->builder()->update($dataUpdate);

        session()->setFlashdata('err', 'Semua approval Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi dibatalkan.');
        return redirect()->back();
    }

    public function excel()
    {
        $data = [
            'sahamdirdekom' => $this->sahamdirdekomModel->getAllData()
        ];

        echo view('sahamdirdekom/excel', $data);
    }

}