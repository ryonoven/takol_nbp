<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_keluargadirdekompshm;
use App\Models\M_tgjwbdir;
use App\Models\M_tgjwbdekom;
use App\Models\M_shmusahadirdekom;
use App\Models\M_infobpr;
use App\Models\M_user;
use Myth\Auth\Config\Services as AuthServices;

class keluargadirdekompshm extends Controller
{
    protected $keluargadirdekompshmModel;
    protected $tgjwbdirModel;
    protected $tgjwbdekomModel;
    protected $shmusahadirdekomModel;
    protected $infobprModel;
    protected $usermodel;
    protected $session;
    protected $auth;

    public function __construct()
    {
        $this->keluargadirdekompshmModel = new M_keluargadirdekompshm();
        $this->tgjwbdirModel = new M_tgjwbdir();
        $this->tgjwbdekomModel = new M_tgjwbdekom();
        $this->userModel = new M_user();
        $this->shmusahadirdekomModel = new M_shmusahadirdekom();
        $this->infobprModel = new M_infobpr();
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

        $keluargadirdekompshmData = $this->keluargadirdekompshmModel->getAllData();

        $authorize = AuthServices::authorization();
        $userInGroupPE = $authorize->inGroup('pe', $this->auth->id());
        $userInGroupAdmin = $authorize->inGroup('admin', $this->auth->id());
        $userInGroupDekom = $authorize->inGroup('dekom', $this->auth->id());
        $userInGroupDireksi = $authorize->inGroup('direksi', $this->auth->id());

        $data = [
            'judul' => '10. Hubungan Keluarga Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR',
            'keluargadirdekompshm' => $keluargadirdekompshmData,
            //'keluargadirdekompshm' => $this->keluargadirdekompshmModel->getAllData(),
            'tgjwbdir' => $this->tgjwbdirModel->getAllData(),
            'tgjwbdekom' => $this->tgjwbdekomModel->getAllData(),
            'shmusahadirdekom' => $this->shmusahadirdekomModel->getAllData(),
            'infobpr' => $this->infobprModel->getAllData(),
            'userInGroupPE' => $userInGroupPE,
            'userInGroupAdmin' => $userInGroupAdmin,
            'userInGroupDekom' => $userInGroupDekom,
            'userInGroupDireksi' => $userInGroupDireksi,
            'fullname' => $fullname,
        ];

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('keluargadirdekompshm/index', $data);
        echo view('templates/v_footer');
    }

    public function tambahkeldir()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        if (isset($_POST['tambahkeldir'])) {
            $val = $this->validate([
                'direksi' => [
                    'label' => 'Nama Direksi',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hubkeldirdir' => [
                    'label' => 'Hubungan Keluarga Dengan Anggota Direksi Lain di BPR',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hubkeldirdekom' => [
                    'label' => 'Hubungan Keluarga Dengan Anggota Dewan Komisaris Lain di BPR:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hubkeldirpshm' => [
                    'label' => 'Hubungan Keluarga Dengan Pemegang Saham Lain di BPR:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => '10.  Hubungan Keluarga Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR',
                    'keluargadirdekompshm' => $this->keluargadirdekompshmModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('keluargadirdekompshm/index', $data);
                echo view('templates/v_footer');
            } else {
                $data = [
                    'direksi' => $this->request->getPost('direksi'),
                    'hubkeldirdir' => $this->request->getPost('hubkeldirdir'),
                    'hubkeldirdekom' => $this->request->getPost('hubkeldirdekom'),
                    'hubkeldirpshm' => $this->request->getPost('hubkeldirpshm')
                ];

                // Insert data using the correct model
                $this->keluargadirdekompshmModel->checkIncrement();
                $success = $this->keluargadirdekompshmModel->tambahkeldir($data);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil ditambahkan ');
                    return redirect()->to(base_url('keluargadirdekompshm'));
                }
            }
        } else {
            return redirect()->to(base_url('keluargadirdekompshm'));
        }
    }

    public function tambahkeldekom()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        if (isset($_POST['tambahkeldekom'])) {
            $val = $this->validate([
                'dekom' => [
                    'label' => 'Nama Dewan Komisaris',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hubkeldekomdir' => [
                    'label' => 'Hubungan Keluarga Dengan Anggota Direksi Lain di BPR',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hubkeldekomdekom' => [
                    'label' => 'Hubungan Keluarga Dengan Anggota Dewan Komisaris Lain di BPR:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hubkeldekompshm' => [
                    'label' => 'Hubungan Keluarga Dengan Pemegang Saham Lain di BPR:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => '10.  Hubungan Keluarga Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR',
                    'keluargadirdekompshm' => $this->keluargadirdekompshmModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('keluargadirdekompshm/index', $data);
                echo view('templates/v_footer');
            } else {
                $data = [
                    'dekom' => $this->request->getPost('dekom'),
                    'hubkeldekomdir' => $this->request->getPost('hubkeldekomdir'),
                    'hubkeldekomdekom' => $this->request->getPost('hubkeldekomdekom'),
                    'hubkeldekompshm' => $this->request->getPost('hubkeldekompshm')
                ];

                // Insert data using the correct model
                $this->keluargadirdekompshmModel->checkIncrement();
                $success = $this->keluargadirdekompshmModel->tambahkeldekom($data);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil ditambahkan');
                    return redirect()->to(base_url('keluargadirdekompshm'));
                }
            }
        } else {
            return redirect()->to(base_url('keluargadirdekompshm'));
        }
    }

    public function tambahkelpshm()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        if (isset($_POST['tambahkelpshm'])) {
            $val = $this->validate([
                'pshm' => [
                    'label' => 'Nama Pemegang Saham',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hubkelpshmdir' => [
                    'label' => 'Hubungan Keluarga Dengan Anggota Direksi Lain di BPR',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hubkelpshmdekom' => [
                    'label' => 'Hubungan Keluarga Dengan Anggota Dewan Komisaris Lain di BPR:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hubkelpshmpshm' => [
                    'label' => 'Hubungan Keluarga Dengan Pemegang Saham Lain di BPR:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => '10.  Hubungan Keluarga Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR',
                    'keluargadirdekompshm' => $this->keluargadirdekompshmModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('keluargadirdekompshm/index', $data);
                echo view('templates/v_footer');
            } else {
                $data = [
                    'pshm' => $this->request->getPost('pshm'),
                    'hubkelpshmdir' => $this->request->getPost('hubkelpshmdir'),
                    'hubkelpshmdekom' => $this->request->getPost('hubkelpshmdekom'),
                    'hubkelpshmpshm' => $this->request->getPost('hubkelpshmpshm')
                ];

                // Insert data using the correct model
                $this->keluargadirdekompshmModel->checkIncrement();
                $success = $this->keluargadirdekompshmModel->tambahkelpshm($data);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil ditambahkan');
                    return redirect()->to(base_url('keluargadirdekompshm'));
                }
            }
        } else {
            return redirect()->to(base_url('keluargadirdekompshm'));
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
        $this->keluargadirdekompshmModel->hapus($id);
        session()->setFlashdata('message', 'Data berhasil dihapus');

        return redirect()->to(base_url('keluargadirdekompshm'));
    }

    public function ubahdir()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        if (isset($_POST['ubahdir'])) {
            $val = $this->validate([
                'direksi' => [
                    'label' => 'Nama Direksi',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hubkeldirdir' => [
                    'label' => 'Hubungan Keluarga Dengan Anggota Direksi Lain di BPR',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hubkeldirdekom' => [
                    'label' => 'Hubungan Keluarga Dengan Anggota Dewan Komisaris Lain di BPR:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hubkeldirpshm' => [
                    'label' => 'Hubungan Keluarga Dengan Pemegang Saham Lain di BPR:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => '10.  Hubungan Keluarga Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR',
                    'keluargadirdekompshm' => $this->keluargadirdekompshmModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('keluargadirdekompshm/index', $data); // Memperbaiki pemanggilan view
                echo view('templates/v_footer');
            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'direksi' => $this->request->getPost('direksi'),
                    'hubkeldirdir' => $this->request->getPost('hubkeldirdir'),
                    'hubkeldirdekom' => $this->request->getPost('hubkeldirdekom'),
                    'hubkeldirpshm' => $this->request->getPost('hubkeldirpshm')
                ];

                // Update data menggunakan model yang benar
                $success = $this->keluargadirdekompshmModel->ubahdir($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil diubah ');
                    return redirect()->to(base_url('keluargadirdekompshm'));
                }
            }
        } else {
            return redirect()->to(base_url('keluargadirdekompshm'));
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
                    'label' => 'Nama Dewan Komisaris',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hubkeldekomdir' => [
                    'label' => 'Hubungan Keluarga Dengan Anggota Direksi Lain di BPR',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hubkeldekomdekom' => [
                    'label' => 'Hubungan Keluarga Dengan Anggota Dewan Komisaris Lain di BPR:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hubkeldekompshm' => [
                    'label' => 'Hubungan Keluarga Dengan Pemegang Saham Lain di BPR:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => '10.  Hubungan Keluarga Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR',
                    'keluargadirdekompshm' => $this->keluargadirdekompshmModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('keluargadirdekompshm/index', $data);
                echo view('templates/v_footer');
            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'dekom' => $this->request->getPost('dekom'),
                    'hubkeldekomdir' => $this->request->getPost('hubkeldekomdir'),
                    'hubkeldekomdekom' => $this->request->getPost('hubkeldekomdekom'),
                    'hubkeldekompshm' => $this->request->getPost('hubkeldekompshm')
                ];

                // Update data menggunakan model yang benar
                $success = $this->keluargadirdekompshmModel->ubahdekom($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil diubah ');
                    return redirect()->to(base_url('keluargadirdekompshm'));
                }
            }
        } else {
            return redirect()->to(base_url('keluargadirdekompshm'));
        }
    }

    public function ubahpshm()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        if (isset($_POST['ubahpshm'])) {
            $val = $this->validate([
                'pshm' => [
                    'label' => 'Nama Pemegang Saham',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hubkelpshmdir' => [
                    'label' => 'Hubungan Keluarga Dengan Anggota Direksi Lain di BPR',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hubkelpshmdekom' => [
                    'label' => 'Hubungan Keluarga Dengan Anggota Dewan Komisaris Lain di BPR:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hubkelpshmpshm' => [
                    'label' => 'Hubungan Keluarga Dengan Pemegang Saham Lain di BPR:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => '10.  Hubungan Keluarga Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR',
                    'keluargadirdekompshm' => $this->keluargadirdekompshmModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('keluargadirdekompshm/index', $data);
                echo view('templates/v_footer');
            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'pshm' => $this->request->getPost('pshm'),
                    'hubkelpshmdir' => $this->request->getPost('hubkelpshmdir'),
                    'hubkelpshmdekom' => $this->request->getPost('hubkelpshmdekom'),
                    'hubkelpshmpshm' => $this->request->getPost('hubkelpshmpshm')
                ];

                // Update data menggunakan model yang benar
                $success = $this->keluargadirdekompshmModel->ubahpshm($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil diubah ');
                    return redirect()->to(base_url('keluargadirdekompshm'));
                }
            }
        } else {
            return redirect()->to(base_url('keluargadirdekompshm'));
        }
    }

    public function ubahketerangan()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        if (isset($_POST['ubahketerangan'])) {
            $val = $this->validate([
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
                    'judul' => '10.  Hubungan Keluarga Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR',
                    'keluargadirdekompshm' => $this->keluargadirdekompshmModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('keluargadirdekompshm/index', $data);
                echo view('templates/v_footer');
            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'keterangan' => $this->request->getPost('keterangan')
                ];

                // Update data menggunakan model yang benar
                $success = $this->keluargadirdekompshmModel->ubahketerangan($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil diubah ');
                    return redirect()->to(base_url('keluargadirdekompshm'));
                }
            }
        } else {
            return redirect()->to(base_url('keluargadirdekompshm'));
        }
    }

    public function approve($idkeluargadirdekompshm)
    {
        if (!is_numeric($idkeluargadirdekompshm) || $idkeluargadirdekompshm <= 0) {
            session()->setFlashdata('err', 'ID Hubungan Keluarga Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR tidak valid.');
            return redirect()->back();
        }

        $keluargadirdekompshm = $this->keluargadirdekompshmModel->find($idkeluargadirdekompshm);
        if (!$keluargadirdekompshm) {
            session()->setFlashdata('err', 'Data Hubungan Keluarga Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idkeluargadirdekompshm,
            'is_approved' => 1,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->keluargadirdekompshmModel->save($dataUpdate)) {
            session()->setFlashdata('message', 'Hubungan Keluarga Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR berhasil disetujui.');
            return redirect()->back();
        } else {
            session()->setFlashdata('err', 'Terjadi kesalahan saat melakukan approval.');
            return redirect()->back();
        }
    }

    public function unapprove($idkeluargadirdekompshm)
    {
        if (!is_numeric($idkeluargadirdekompshm) || $idkeluargadirdekompshm <= 0) {
            session()->setFlashdata('err', 'ID Hubungan Keluarga Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR tidak valid.');
            return redirect()->back();
        }

        $keluargadirdekompshm = $this->keluargadirdekompshmModel->find($idkeluargadirdekompshm);
        if (!$keluargadirdekompshm) {
            session()->setFlashdata('err', 'Data Hubungan Keluarga Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idkeluargadirdekompshm,
            'is_approved' => 2,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->keluargadirdekompshmModel->save($dataUpdate)) {
            session()->setFlashdata('err', 'Approval Hubungan Keluarga Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR dibatalkan.');
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

        $this->keluargadirdekompshmModel->builder()->update($dataUpdate);

        session()->setFlashdata('message', 'Semua Hubungan Keluarga Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR berhasil disetujui.');
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

        $this->keluargadirdekompshmModel->builder()->update($dataUpdate);

        session()->setFlashdata('err', 'Semua approval Hubungan Keluarga Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR dibatalkan.');
        return redirect()->back();
    }

    public function excel()
    {
        $data = [
            'keluargadirdekompshm' => $this->keluargadirdekompshmModel->getAllData()
        ];

        echo view('keluargadirdekompshm/excel', $data);
    }

    public function exporttxtkeluargadirdekompshm()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $data_keluargadirdekompshm = $this->keluargadirdekompshmModel->getAllData();

        $data_tgjwbdir = $this->tgjwbdirModel->getAllData();

        $data_tgjwbdekom = $this->tgjwbdekomModel->getAllData();

        $data_shmusahadirdekom = $this->shmusahadirdekomModel->getAllData();

        $data_infobpr = $this->infobprModel->getAllData();

        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'];
            $kodejenis = $infobpr['kodejenis'];
        }

        usort($data_keluargadirdekompshm, function ($a, $b) {
            if (!empty($a['direksi']) && empty($b['direksi'])) {
                return -1;
            } elseif (empty($a['direksi']) && !empty($b['direksi'])) {
                return 1;
            } elseif (!empty($a['dekom']) && empty($b['dekom']) && empty($a['direksi'])) {
                return -1;
            } elseif (empty($a['dekom']) && !empty($b['dekom']) && empty($b['direksi'])) {
                return 1;
            } else {
                return 0;
            }
        });

        $output = "";
        $output .= "H01|" . $kodejenis . "|" . $sandibpr . "|2025-05-31|LTBPRK|E0402|0|\n";

        foreach ($data_keluargadirdekompshm as $row_shm) {
            $nik = '';

            if (!empty($row_shm['direksi'])) {
                foreach ($data_tgjwbdir as $row_tgj_dir) {
                    if ($row_shm['direksi'] == $row_tgj_dir['direksi']) {
                        $nik = $row_tgj_dir['nik'];
                        break;
                    }
                }
                $output .= "D01|" . "032010000000" . "|" . $nik . "|" . $row_shm['hubkeldirdir'] . "|" . $row_shm['hubkeldirdekom'] . "|" . $row_shm['hubkeldirpshm'] . "\n";
            } elseif (!empty($row_shm['dekom'])) {
                foreach ($data_tgjwbdekom as $row_tgj_dekom) {
                    if ($row_shm['dekom'] == $row_tgj_dekom['dekom']) {
                        $nik = $row_tgj_dekom['nik'];
                        break;
                    }
                }
                $output .= "D01|" . "032010000000" . "|" . $nik . "|" . $row_shm['hubkeldekomdir'] . "|" . $row_shm['hubkeldekomdekom'] . "|" . $row_shm['hubkeldekompshm'] . "\n";
            } elseif (!empty($row_shm['pshm'])) {
                foreach ($data_shmusahadirdekom as $row_shm_pshm) {
                    if ($row_shm['pshm'] == $row_shm_pshm['pshm']) {
                        $nikpshm = $row_shm_pshm['nikpshm'];
                        break;
                    }
                }
                $output .= "D01|" . "032010000000" . "|" . $nikpshm . "|" . $row_shm['hubkelpshmdir'] . "|" . $row_shm['hubkelpshmdekom'] . "|" . $row_shm['hubkelpshmpshm'] . "\n";
            }
        }
        $keterangan_id_1 = '';
        foreach ($data_keluargadirdekompshm as $row_shm) {
            if ($row_shm['id'] == 1) {
                $keterangan_id_1 = trim($row_shm['keterangan']);
                break;
            }
        }

        $output .= "F01|" . "Footer 1" . " " . $keterangan_id_1;

        $response = service('response');

        $filename = "LTBPRK-E0402-R-A-20250531-" . $sandibpr . "-01.txt";

        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->setBody($output);

        return $response;
    }

}