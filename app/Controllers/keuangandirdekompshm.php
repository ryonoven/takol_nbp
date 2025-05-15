<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_keuangandirdekompshm;
use App\Models\M_tgjwbdir;
use App\Models\M_tgjwbdekom;
use App\Models\M_shmusahadirdekom;
use App\Models\M_infobpr;
use App\Models\M_user;
use Myth\Auth\Config\Services as AuthServices;

class keuangandirdekompshm extends Controller
{
    protected $keuangandirdekompshmModel;
    protected $tgjwbdirModel;
    protected $tgjwbdekomModel;
    protected $shmusahadirdekomModel;
    protected $infobprModel;
    protected $usermodel;
    protected $session;
    protected $auth;

    public function __construct()
    {
        $this->keuangandirdekompshmModel = new M_keuangandirdekompshm();
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

        $keuangandirdekompshmData = $this->keuangandirdekompshmModel->getAllData();

        $authorize = AuthServices::authorization();
        $userInGroupPE = $authorize->inGroup('pe', $this->auth->id());
        $userInGroupAdmin = $authorize->inGroup('admin', $this->auth->id());
        $userInGroupDekom = $authorize->inGroup('dekom', $this->auth->id());
        $userInGroupDireksi = $authorize->inGroup('direksi', $this->auth->id());

        $data = [
            'judul' => '9.  Hubungan Keuangan Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR',
            'keuangandirdekompshm' => $keuangandirdekompshmData,
            //'keuangandirdekompshm' => $this->keuangandirdekompshmModel->getAllData(),
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
        echo view('keuangandirdekompshm/index', $data);
        echo view('templates/v_footer');
    }

    public function tambahuangdir()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        if (isset($_POST['tambahuangdir'])) {
            $val = $this->validate([
                'direksi' => [
                    'label' => 'Nama Direksi',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hubdirdir' => [
                    'label' => 'Hubungan Keuangan Dengan Anggota Direksi Lain di BPR',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hubdirdekom' => [
                    'label' => 'Hubungan Keuangan Dengan Anggota Dewan Komisaris Lain di BPR:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hubdirpshm' => [
                    'label' => 'Hubungan Keuangan Dengan Pemegang Saham Lain di BPR:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => '9.  Hubungan Keuangan Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR',
                    'keuangandirdekompshm' => $this->keuangandirdekompshmModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('keuangandirdekompshm/index', $data);
                echo view('templates/v_footer');
            } else {
                $data = [
                    'direksi' => $this->request->getPost('direksi'),
                    'hubdirdir' => $this->request->getPost('hubdirdir'),
                    'hubdirdekom' => $this->request->getPost('hubdirdekom'),
                    'hubdirpshm' => $this->request->getPost('hubdirpshm')
                ];

                // Insert data using the correct model
                $this->keuangandirdekompshmModel->checkIncrement();
                $success = $this->keuangandirdekompshmModel->tambahuangdir($data);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil ditambahkan ');
                    return redirect()->to(base_url('keuangandirdekompshm'));
                }
            }
        } else {
            return redirect()->to(base_url('keuangandirdekompshm'));
        }
    }

    public function tambahuangdekom()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        if (isset($_POST['tambahuangdekom'])) {
            $val = $this->validate([
                'dekom' => [
                    'label' => 'Nama Dewan Komisaris',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hubdekomdir' => [
                    'label' => 'Hubungan Keuangan Dengan Anggota Direksi Lain di BPR',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hubdekomdekom' => [
                    'label' => 'Hubungan Keuangan Dengan Anggota Dewan Komisaris Lain di BPR:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hubdekompshm' => [
                    'label' => 'Hubungan Keuangan Dengan Pemegang Saham Lain di BPR:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => '9.  Hubungan Keuangan Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR',
                    'keuangandirdekompshm' => $this->keuangandirdekompshmModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('keuangandirdekompshm/index', $data);
                echo view('templates/v_footer');
            } else {
                $data = [
                    'dekom' => $this->request->getPost('dekom'),
                    'hubdekomdir' => $this->request->getPost('hubdekomdir'),
                    'hubdekomdekom' => $this->request->getPost('hubdekomdekom'),
                    'hubdekompshm' => $this->request->getPost('hubdekompshm')
                ];

                // Insert data using the correct model
                $this->keuangandirdekompshmModel->checkIncrement();
                $success = $this->keuangandirdekompshmModel->tambahuangdekom($data);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil ditambahkan');
                    return redirect()->to(base_url('keuangandirdekompshm'));
                }
            }
        } else {
            return redirect()->to(base_url('keuangandirdekompshm'));
        }
    }

    public function tambahuangpshm()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        if (isset($_POST['tambahuangpshm'])) {
            $val = $this->validate([
                'pshm' => [
                    'label' => 'Nama Pemegang Saham',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hubpshmdir' => [
                    'label' => 'Hubungan Keuangan Dengan Anggota Direksi Lain di BPR',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hubpshmdekom' => [
                    'label' => 'Hubungan Keuangan Dengan Anggota Dewan Komisaris Lain di BPR:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hubpshmpshm' => [
                    'label' => 'Hubungan Keuangan Dengan Pemegang Saham Lain di BPR:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => '9.  Hubungan Keuangan Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR',
                    'keuangandirdekompshm' => $this->keuangandirdekompshmModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('keuangandirdekompshm/index', $data);
                echo view('templates/v_footer');
            } else {
                $data = [
                    'pshm' => $this->request->getPost('pshm'),
                    'hubpshmdir' => $this->request->getPost('hubpshmdir'),
                    'hubpshmdekom' => $this->request->getPost('hubpshmdekom'),
                    'hubpshmpshm' => $this->request->getPost('hubpshmpshm')
                ];

                // Insert data using the correct model
                $this->keuangandirdekompshmModel->checkIncrement();
                $success = $this->keuangandirdekompshmModel->tambahuangpshm($data);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil ditambahkan');
                    return redirect()->to(base_url('keuangandirdekompshm'));
                }
            }
        } else {
            return redirect()->to(base_url('keuangandirdekompshm'));
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
        $this->keuangandirdekompshmModel->hapus($id);
        session()->setFlashdata('message', 'Data berhasil dihapus');

        return redirect()->to(base_url('keuangandirdekompshm'));
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
                'hubdirdir' => [
                    'label' => 'Hubungan Keuangan Dengan Anggota Direksi Lain di BPR',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hubdirdekom' => [
                    'label' => 'Hubungan Keuangan Dengan Anggota Dewan Komisaris Lain di BPR:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hubdirpshm' => [
                    'label' => 'Hubungan Keuangan Dengan Pemegang Saham Lain di BPR:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => '9.  Hubungan Keuangan Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR',
                    'keuangandirdekompshm' => $this->keuangandirdekompshmModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('keuangandirdekompshm/index', $data); // Memperbaiki pemanggilan view
                echo view('templates/v_footer');
            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'direksi' => $this->request->getPost('direksi'),
                    'hubdirdir' => $this->request->getPost('hubdirdir'),
                    'hubdirdekom' => $this->request->getPost('hubdirdekom'),
                    'hubdirpshm' => $this->request->getPost('hubdirpshm')
                ];

                // Update data menggunakan model yang benar
                $success = $this->keuangandirdekompshmModel->ubahdir($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil diubah ');
                    return redirect()->to(base_url('keuangandirdekompshm'));
                }
            }
        } else {
            return redirect()->to(base_url('keuangandirdekompshm'));
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
                'hubdekomdir' => [
                    'label' => 'Hubungan Keuangan Dengan Anggota Direksi Lain di BPR',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hubdekomdekom' => [
                    'label' => 'Hubungan Keuangan Dengan Anggota Dewan Komisaris Lain di BPR:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hubdekompshm' => [
                    'label' => 'Hubungan Keuangan Dengan Pemegang Saham Lain di BPR:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => '9.  Hubungan Keuangan Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR',
                    'keuangandirdekompshm' => $this->keuangandirdekompshmModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('keuangandirdekompshm/index', $data);
                echo view('templates/v_footer');
            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'dekom' => $this->request->getPost('dekom'),
                    'hubdekomdir' => $this->request->getPost('hubdekomdir'),
                    'hubdekomdekom' => $this->request->getPost('hubdekomdekom'),
                    'hubdekompshm' => $this->request->getPost('hubdekompshm')
                ];

                // Update data menggunakan model yang benar
                $success = $this->keuangandirdekompshmModel->ubahdekom($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil diubah ');
                    return redirect()->to(base_url('keuangandirdekompshm'));
                }
            }
        } else {
            return redirect()->to(base_url('keuangandirdekompshm'));
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
                'hubpshmdir' => [
                    'label' => 'Hubungan Keuangan Dengan Anggota Direksi Lain di BPR',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hubpshmdekom' => [
                    'label' => 'Hubungan Keuangan Dengan Anggota Dewan Komisaris Lain di BPR:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hubpshmpshm' => [
                    'label' => 'Hubungan Keuangan Dengan Pemegang Saham Lain di BPR:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => '9.  Hubungan Keuangan Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR',
                    'keuangandirdekompshm' => $this->keuangandirdekompshmModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('keuangandirdekompshm/index', $data);
                echo view('templates/v_footer');
            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'pshm' => $this->request->getPost('pshm'),
                    'hubpshmdir' => $this->request->getPost('hubpshmdir'),
                    'hubpshmdekom' => $this->request->getPost('hubpshmdekom'),
                    'hubpshmpshm' => $this->request->getPost('hubpshmpshm')
                ];

                // Update data menggunakan model yang benar
                $success = $this->keuangandirdekompshmModel->ubahpshm($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil diubah ');
                    return redirect()->to(base_url('keuangandirdekompshm'));
                }
            }
        } else {
            return redirect()->to(base_url('keuangandirdekompshm'));
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
                    'judul' => '9.  Hubungan Keuangan Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR',
                    'keuangandirdekompshm' => $this->keuangandirdekompshmModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('keuangandirdekompshm/index', $data);
                echo view('templates/v_footer');
            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'keterangan' => $this->request->getPost('keterangan')
                ];

                // Update data menggunakan model yang benar
                $success = $this->keuangandirdekompshmModel->ubahketerangan($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil diubah ');
                    return redirect()->to(base_url('keuangandirdekompshm'));
                }
            }
        } else {
            return redirect()->to(base_url('keuangandirdekompshm'));
        }
    }

    public function approve($idkeuangandirdekompshm)
    {
        if (!is_numeric($idkeuangandirdekompshm) || $idkeuangandirdekompshm <= 0) {
            session()->setFlashdata('err', 'ID Hubungan Keuangan Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR tidak valid.');
            return redirect()->back();
        }

        $keuangandirdekompshm = $this->keuangandirdekompshmModel->find($idkeuangandirdekompshm);
        if (!$keuangandirdekompshm) {
            session()->setFlashdata('err', 'Data Hubungan Keuangan Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idkeuangandirdekompshm,
            'is_approved' => 1,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->keuangandirdekompshmModel->save($dataUpdate)) {
            session()->setFlashdata('message', 'Hubungan Keuangan Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR berhasil disetujui.');
            return redirect()->back();
        } else {
            session()->setFlashdata('err', 'Terjadi kesalahan saat melakukan approval.');
            return redirect()->back();
        }
    }

    public function unapprove($idkeuangandirdekompshm)
    {
        if (!is_numeric($idkeuangandirdekompshm) || $idkeuangandirdekompshm <= 0) {
            session()->setFlashdata('err', 'ID Hubungan Keuangan Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR tidak valid.');
            return redirect()->back();
        }

        $keuangandirdekompshm = $this->keuangandirdekompshmModel->find($idkeuangandirdekompshm);
        if (!$keuangandirdekompshm) {
            session()->setFlashdata('err', 'Data Hubungan Keuangan Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idkeuangandirdekompshm,
            'is_approved' => 2,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->keuangandirdekompshmModel->save($dataUpdate)) {
            session()->setFlashdata('err', 'Approval Hubungan Keuangan Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR dibatalkan.');
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

        $this->keuangandirdekompshmModel->builder()->update($dataUpdate);

        session()->setFlashdata('message', 'Semua Hubungan Keuangan Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR berhasil disetujui.');
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

        $this->keuangandirdekompshmModel->builder()->update($dataUpdate);

        session()->setFlashdata('err', 'Semua approval Hubungan Keuangan Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR dibatalkan.');
        return redirect()->back();
    }

    public function excel()
    {
        $data = [
            'keuangandirdekompshm' => $this->keuangandirdekompshmModel->getAllData()
        ];

        echo view('keuangandirdekompshm/excel', $data);
    }

    public function exporttxtkeuangandirdekompshm()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $data_keuangandirdekompshm = $this->keuangandirdekompshmModel->getAllData();

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

        usort($data_keuangandirdekompshm, function ($a, $b) {
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
        $output .= "H01|" . $kodejenis . "|" . $sandibpr . "|2025-05-31|LTBPRK|E0401|0|\n";

        foreach ($data_keuangandirdekompshm as $row_shm) {
            $nik = '';

            if (!empty($row_shm['direksi'])) {
                foreach ($data_tgjwbdir as $row_tgj_dir) {
                    if ($row_shm['direksi'] == $row_tgj_dir['direksi']) {
                        $nik = $row_tgj_dir['nik'];
                        break;
                    }
                }
                $output .= "D01|" . "031010000000" . "|" . $nik . "|" . $row_shm['hubdirdir'] . "|" . $row_shm['hubdirdekom'] . "|" . $row_shm['hubdirpshm'] . "\n";
            } elseif (!empty($row_shm['dekom'])) {
                foreach ($data_tgjwbdekom as $row_tgj_dekom) {
                    if ($row_shm['dekom'] == $row_tgj_dekom['dekom']) {
                        $nik = $row_tgj_dekom['nik'];
                        break;
                    }
                }
                $output .= "D01|" . "031010000000" . "|" . $nik . "|" . $row_shm['hubdekomdir'] . "|" . $row_shm['hubdekomdekom'] . "|" . $row_shm['hubdekompshm'] . "\n";
            } elseif (!empty($row_shm['pshm'])) {
                foreach ($data_shmusahadirdekom as $row_shm_pshm) {
                    if ($row_shm['pshm'] == $row_shm_pshm['pshm']) {
                        $nikpshm = $row_shm_pshm['nikpshm'];
                        break;
                    }
                }
                $output .= "D01|" . "031010000000" . "|" . $nikpshm . "|" . $row_shm['hubpshmdir'] . "|" . $row_shm['hubpshmdekom'] . "|" . $row_shm['hubpshmpshm'] . "\n";
            }
        }
        $keterangan_id_1 = '';
        foreach ($data_keuangandirdekompshm as $row_shm) {
            if ($row_shm['id'] == 1) {
                $keterangan_id_1 = trim($row_shm['keterangan']);
                break;
            }
        }

        $output .= "F01|" . "Footer 1" . " " . $keterangan_id_1;

        $response = service('response');

        $filename = "LTBPRK-E0401-R-A-20250531-" . $sandibpr . "-01.txt";

        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->setBody($output);

        return $response;
    }

}