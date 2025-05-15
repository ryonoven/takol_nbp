<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_shmusahadirdekom;
use App\Models\M_tgjwbdir;
use App\Models\M_tgjwbdekom;
use App\Models\M_infobpr;
use App\Models\M_user;
use Myth\Auth\Config\Services as AuthServices;

class shmusahadirdekom extends Controller
{
    protected $shmusahadirdekomModel;
    protected $tgjwbdirModel;
    protected $tgjwbdekomModel;
    protected $infobprModel;
    protected $session;
    protected $auth;

    public function __construct()
    {
        $this->shmusahadirdekomModel = new M_shmusahadirdekom();
        $this->tgjwbdirModel = new M_tgjwbdir();
        $this->tgjwbdekomModel = new M_tgjwbdekom();
        $this->userModel = new M_user();
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

        $shmusahadirdekomData = $this->shmusahadirdekomModel->getAllData();

        $authorize = AuthServices::authorization();
        $userInGroupPE = $authorize->inGroup('pe', $this->auth->id());
        $userInGroupAdmin = $authorize->inGroup('admin', $this->auth->id());
        $userInGroupDekom = $authorize->inGroup('dekom', $this->auth->id());
        $userInGroupDireksi = $authorize->inGroup('direksi', $this->auth->id());

        $data = [
            'judul' => '7. Kepemilikan Saham Anggota Direksi, Dewan Komisaris, dan Pemegang Saham pada Kelompok Usaha BPR',
            'shmusahadirdekom' => $shmusahadirdekomData,
            //'shmusahadirdekom' => $this->shmusahadirdekomModel->getAllData(),
            'tgjwbdir' => $this->tgjwbdirModel->getAllData(),
            'tgjwbdekom' => $this->tgjwbdekomModel->getAllData(),
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
        echo view('shmusahadirdekom/index', $data);
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
                'usahadir' => [
                    'label' => 'Nama Kelompok Usaha BPR',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'persenshmdir' => [
                    'label' => 'Persentase Kepemilikan (%):',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'persenshmdirlalu' => [
                    'label' => 'Persentase Kepemilikan (%) Tahun Sebelumnya:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Kepemilikan Saham Anggota Direksi, Dewan Komisaris, dan Pemegang Saham pada Kelompok Usaha BPR ',
                    'shmusahadirdekom' => $this->shmusahadirdekomModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('shmusahadirdekom/index', $data);
                echo view('templates/v_footer');
            } else {
                $data = [
                    'direksi' => $this->request->getPost('direksi'),
                    'usahadir' => $this->request->getPost('usahadir'),
                    'persenshmdir' => $this->request->getPost('persenshmdir'),
                    'persenshmdirlalu' => $this->request->getPost('persenshmdirlalu')
                ];

                // Insert data using the correct model
                $this->shmusahadirdekomModel->checkIncrement();
                $success = $this->shmusahadirdekomModel->tambahsahamdir($data);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil ditambahkan ');
                    return redirect()->to(base_url('shmusahadirdekom'));
                }
            }
        } else {
            return redirect()->to(base_url('shmusahadirdekom'));
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
                'usahadekom' => [
                    'label' => 'Nama Kelompok Usaha BPR',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'persenshmdekom' => [
                    'label' => 'Persentase Kepemilikan (%):',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'persenshmdekomlalu' => [
                    'label' => 'Persentase Kepemilikan (%) Tahun Sebelumnya:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Kepemilikan Saham Anggota Direksi, Dewan Komisaris, dan Pemegang Saham pada Kelompok Usaha BPR',
                    'shmusahadirdekom' => $this->shmusahadirdekomModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('shmusahadirdekom/index', $data);
                echo view('templates/v_footer');
            } else {
                $data = [
                    'dekom' => $this->request->getPost('dekom'),
                    'usahadekom' => $this->request->getPost('usahadekom'),
                    'persenshmdekom' => $this->request->getPost('persenshmdekom'),
                    'persenshmdekomlalu' => $this->request->getPost('persenshmdekomlalu')
                ];

                // Insert data using the correct model
                $this->shmusahadirdekomModel->checkIncrement();
                $success = $this->shmusahadirdekomModel->tambahsahamdekom($data);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil ditambahkan');
                    return redirect()->to(base_url('shmusahadirdekom'));
                }
            }
        } else {
            return redirect()->to(base_url('shmusahadirdekom'));
        }
    }

    public function tambahsahampshm()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        if (isset($_POST['tambahsahampshm'])) {
            $val = $this->validate([
                'pshm' => [
                    'label' => 'Nama Pemegang Saham',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nikpshm' => [
                    'label' => 'NIK',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'usahapshm' => [
                    'label' => 'Nama Kelompok Usaha BPR',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'persenpshm' => [
                    'label' => 'Persentase Kepemilikan (%):',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'persenpshmlalu' => [
                    'label' => 'Persentase Kepemilikan (%) Tahun Sebelumnya:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Kepemilikan Saham Anggota Direksi, Dewan Komisaris, dan Pemegang Saham pada Kelompok Usaha BPR',
                    'shmusahadirdekom' => $this->shmusahadirdekomModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('shmusahadirdekom/index', $data);
                echo view('templates/v_footer');
            } else {
                $data = [
                    'pshm' => $this->request->getPost('pshm'),
                    'nikpshm' => $this->request->getPost('nikpshm'),
                    'usahapshm' => $this->request->getPost('usahapshm'),
                    'persenpshm' => $this->request->getPost('persenpshm'),
                    'persenpshmlalu' => $this->request->getPost('persenpshmlalu')
                ];

                // Insert data using the correct model
                $this->shmusahadirdekomModel->checkIncrement();
                $success = $this->shmusahadirdekomModel->tambahsahampshm($data);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil ditambahkan');
                    return redirect()->to(base_url('shmusahadirdekom'));
                }
            }
        } else {
            return redirect()->to(base_url('shmusahadirdekom'));
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
        $this->shmusahadirdekomModel->hapus($id);
        session()->setFlashdata('message', 'Data berhasil dihapus');

        return redirect()->to(base_url('shmusahadirdekom'));
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
                'usahadir' => [
                    'label' => 'Nama Kelompok Usaha BPR',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'persenshmdir' => [
                    'label' => 'Persentase Kepemilikan (%):',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'persenshmdirlalu' => [
                    'label' => 'Persentase Kepemilikan (%) Tahun Sebelumnya:',
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
                    'shmusahadirdekom' => $this->shmusahadirdekomModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('shmusahadirdekom/index', $data);
                echo view('templates/v_footer');
            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'direksi' => $this->request->getPost('direksi'),
                    'usahadir' => $this->request->getPost('usahadir'),
                    'persenshmdir' => $this->request->getPost('persenshmdir'),
                    'persenshmdirlalu' => $this->request->getPost('persenshmdirlalu')
                ];

                // Update data menggunakan model yang benar
                $success = $this->shmusahadirdekomModel->ubahdir($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil diubah ');
                    return redirect()->to(base_url('shmusahadirdekom'));
                }
            }
        } else {
            return redirect()->to(base_url('shmusahadirdekom'));
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
                'usahadekom' => [
                    'label' => 'Nama Kelompok Usaha BPR',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'persenshmdekom' => [
                    'label' => 'Persentase Kepemilikan (%):',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'persenshmdekomlalu' => [
                    'label' => 'Persentase Kepemilikan (%) Tahun Sebelumnya:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Kepemilikan Saham Anggota Direksi, Dewan Komisaris, dan Pemegang Saham pada Kelompok Usaha BPR',
                    'shmusahadirdekom' => $this->shmusahadirdekomModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('shmusahadirdekom/index', $data);
                echo view('templates/v_footer');
            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'dekom' => $this->request->getPost('dekom'),
                    'usahadekom' => $this->request->getPost('usahadekom'),
                    'persenshmdekom' => $this->request->getPost('persenshmdekom'),
                    'persenshmdekomlalu' => $this->request->getPost('persenshmdekomlalu')
                ];

                // Update data menggunakan model yang benar
                $success = $this->shmusahadirdekomModel->ubahdekom($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil diubah ');
                    return redirect()->to(base_url('shmusahadirdekom'));
                }
            }
        } else {
            return redirect()->to(base_url('shmusahadirdekom'));
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
                'usahapshm' => [
                    'label' => 'Nama Kelompok Usaha BPR',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'persenpshm' => [
                    'label' => 'Persentase Kepemilikan (%):',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'persenpshmlalu' => [
                    'label' => 'Persentase Kepemilikan (%) Tahun Sebelumnya:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Kepemilikan Saham Anggota Direksi, Dewan Komisaris, dan Pemegang Saham pada Kelompok Usaha BPR',
                    'shmusahadirdekom' => $this->shmusahadirdekomModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('shmusahadirdekom/index', $data);
                echo view('templates/v_footer');
            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'pshm' => $this->request->getPost('pshm'),
                    'usahapshm' => $this->request->getPost('usahapshm'),
                    'persenpshm' => $this->request->getPost('persenpshm'),
                    'persenpshmlalu' => $this->request->getPost('persenpshmlalu')
                ];

                $success = $this->shmusahadirdekomModel->ubahpshm($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil diubah ');
                    return redirect()->to(base_url('shmusahadirdekom'));
                }
            }
        } else {
            return redirect()->to(base_url('shmusahadirdekom'));
        }
    }

    public function ubahketerangan()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
        }

        if (isset($_POST['ubahketerangan'])) {
            $val = $this->validate([
                'keterangan' => [
                    'label' => 'Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi',
                    'shmusahadirdekom' => $this->model->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('shmusahadirdekom/index', $data);
                echo view('templates/v_footer');

            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'keterangan' => $this->request->getPost('keterangan')
                ];

                // Update data
                $success = $this->shmusahadirdekomModel->ubahketerangan($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Tindak Lanjut Direksi berhasil diubah ');
                    return redirect()->to(base_url('shmusahadirdekom'));
                }
            }
        } else {
            return redirect()->to(base_url('shmusahadirdekom'));
        }
    }

    public function excel()
    {
        $data = [
            'shmusahadirdekom' => $this->shmusahadirdekomModel->getAllData()
        ];

        echo view('shmusahadirdekom/excel', $data);
    }

    public function approve($idshmusahadirdekom)
    {
        if (!is_numeric($idshmusahadirdekom) || $idshmusahadirdekom <= 0) {
            session()->setFlashdata('err', 'ID Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi tidak valid.');
            return redirect()->back();
        }

        $shmusahadirdekom = $this->shmusahadirdekomModel->find($idshmusahadirdekom);
        if (!$shmusahadirdekom) {
            session()->setFlashdata('err', 'Data Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idshmusahadirdekom,
            'is_approved' => 1,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->shmusahadirdekomModel->save($dataUpdate)) {
            session()->setFlashdata('message', 'Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi berhasil disetujui.');
            return redirect()->back();
        } else {
            session()->setFlashdata('err', 'Terjadi kesalahan saat melakukan approval.');
            return redirect()->back();
        }
    }

    public function unapprove($idshmusahadirdekom)
    {
        if (!is_numeric($idshmusahadirdekom) || $idshmusahadirdekom <= 0) {
            session()->setFlashdata('err', 'ID Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi tidak valid.');
            return redirect()->back();
        }

        $shmusahadirdekom = $this->shmusahadirdekomModel->find($idshmusahadirdekom);
        if (!$shmusahadirdekom) {
            session()->setFlashdata('err', 'Data Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idshmusahadirdekom,
            'is_approved' => 2,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->shmusahadirdekomModel->save($dataUpdate)) {
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

        $this->shmusahadirdekomModel->builder()->update($dataUpdate);

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

        $this->shmusahadirdekomModel->builder()->update($dataUpdate);

        session()->setFlashdata('err', 'Semua approval Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi dibatalkan.');
        return redirect()->back();
    }

    public function exporttxtshmusahadirdekom()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $data_shmusahadirdekom = $this->shmusahadirdekomModel->getAllData();

        $data_tgjwbdir = $this->tgjwbdirModel->getAllData();

        $data_tgjwbdekom = $this->tgjwbdekomModel->getAllData();

        $data_infobpr = $this->infobprModel->getAllData();

        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'];
            $kodejenis = $infobpr['kodejenis'];
        }

        usort($data_shmusahadirdekom, function ($a, $b) {
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
        $output .= "H01|" . $kodejenis . "|" . $sandibpr . "|2025-05-31|LTBPRK|E0302|0|\n";

        foreach ($data_shmusahadirdekom as $row_shm) {
            $nik = '';

            if (!empty($row_shm['direksi'])) {
                foreach ($data_tgjwbdir as $row_tgj_dir) {
                    if ($row_shm['direksi'] == $row_tgj_dir['direksi']) {
                        $nik = $row_tgj_dir['nik'];
                        break;
                    }
                }
                $output .= "D01|" . "042010000000" . "|" . $nik . "|" . $row_shm['usahadir'] . "|" . $row_shm['persenshmdir'] . "|" . $row_shm['persenshmdirlalu'] . "\n";
            } elseif (!empty($row_shm['dekom'])) {
                foreach ($data_tgjwbdekom as $row_tgj_dekom) {
                    if ($row_shm['dekom'] == $row_tgj_dekom['dekom']) {
                        $nik = $row_tgj_dekom['nik'];
                        break;
                    }
                }
                $output .= "D01|" . "042010000000" . "|" . $nik . "|" . $row_shm['usahadekom'] . "|" . $row_shm['persenshmdekom'] . "|" . $row_shm['persenshmdekomlalu'] . "\n";
            } elseif (!empty($row_shm['pshm'])) {
                $output .= "D01|" . "042010000000" . "|" . $row_shm['nikpshm'] . "|" . $row_shm['usahapshm'] . "|" . $row_shm['persenpshm'] . "|" . $row_shm['persenpshmlalu'] . "\n";
            }
        }
        $keterangan_id_1 = '';
        foreach ($data_shmusahadirdekom as $row_shm) {
            if ($row_shm['id'] == 1) {
                $keterangan_id_1 = trim($row_shm['keterangan']);
                break; // Berhenti setelah menemukan id 1
            }
        }

        $output .= "F01|" . "Footer 1" . " " . $keterangan_id_1;

        $response = service('response');

        $filename = "LTBPRK-E0302-R-A-20250531-" . $sandibpr . "-01.txt";

        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->setBody($output);

        return $response;
    }

}