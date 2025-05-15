<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_fraudinternal;
use App\Models\M_infobpr;
use App\Models\M_user;
use Myth\Auth\Config\Services as AuthServices;

class fraudinternal extends Controller
{
    protected $model;
    protected $fraudinternalModel;
    protected $usermodel;
    protected $session;
    protected $auth;
    protected $infobprModel;
    public function __construct()
    {
        $this->model = new M_fraudinternal();
        $this->infobprModel = new M_infobpr();
        $this->userModel = new M_user();
        $this->fraudinternalModel = new M_fraudinternal();
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
        $user = $this->userModel->find($userId);

        $fullname = $user['fullname'] ?? 'Unknown';

        $fraudinternalData = $this->fraudinternalModel->getAllData();

        $authorize = AuthServices::authorization();
        $userInGroupPE = $authorize->inGroup('pe', $this->auth->id());
        $userInGroupAdmin = $authorize->inGroup('admin', $this->auth->id());
        $userInGroupDekom = $authorize->inGroup('dekom', $this->auth->id());
        $userInGroupDireksi = $authorize->inGroup('direksi', $this->auth->id());

        $data = [
            'judul' => '15. Jumlah Penyimpangan Intern (Internal Fraud)',
            'fraudinternal' => $fraudinternalData,
            // 'fraudinternal' => $this->model->getAllData(),
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
        echo view('fraudinternal/index', $data);
        echo view('templates/v_footer');
    }

    public function tambahfrauddir()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
        }

        if (isset($_POST['tambahfrauddir'])) {
            $val = $this->validate([
                'fraudtahunlaporandir' => [
                    'label' => 'Total Fraud Pada Tahun Laporan (Direksi)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'fraudtahunsebelumdir' => [
                    'label' => 'Total Fraud Pada Tahun Sebelumnya (Direksi)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'selesaitahunlaporandir' => [
                    'label' => 'Telah Diselesaikan Pada Tahun Laporan (Direksi)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'prosestahunlaporandir' => [
                    'label' => 'Dalam Proses Penyelesaian Pada Tahun Laporan (Direksi)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'prosestahunsebelumdir' => [
                    'label' => 'Dalam Proses Penyelesaian Pada Tahun Sebelumnya (Direksi)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'belumtahunlaporandir' => [
                    'label' => 'Belum Diupayakan Penyelesaiannya Pada Tahun Laporan (Direksi)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'belumtahunsebelumdir' => [
                    'label' => 'Belum Diupayakan Penyelesaiannya Pada Tahun Sebelumnya (Direksi)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hukumtahunlaporandir' => [
                    'label' => 'Telah ditindaklanjuti Melalui Proses Hukum Pada Tahun Laporan (Direksi)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],

            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Jumlah Penyimpangan Internal oleh Anggota Direksi',
                    'fraudinternal' => $this->model->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('fraudinternal/index', $data);
                echo view('templates/v_footer');
            } else {
                $data = [
                    'fraudtahunlaporandir' => $this->request->getPost('fraudtahunlaporandir'),
                    'fraudtahunsebelumdir' => $this->request->getPost('fraudtahunsebelumdir'),
                    'selesaitahunlaporandir' => $this->request->getPost('selesaitahunlaporandir'),
                    'prosestahunlaporandir' => $this->request->getPost('prosestahunlaporandir'),
                    'prosestahunsebelumdir' => $this->request->getPost('prosestahunsebelumdir'),
                    'belumtahunlaporandir' => $this->request->getPost('belumtahunlaporandir'),
                    'belumtahunsebelumdir' => $this->request->getPost('belumtahunsebelumdir'),
                    'hukumtahunlaporandir' => $this->request->getPost('hukumtahunlaporandir'),
                ];

                // Insert data
                $this->model->checkIncrement();
                $success = $this->model->tambahfrauddir($data);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil ditambahkan ');
                    return redirect()->to(base_url('fraudinternal'));
                }
            }
        } else {
            return redirect()->to(base_url('fraudinternal'));
        }
    }

    public function tambahfrauddekom()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
        }

        if (isset($_POST['tambahfrauddekom'])) {
            $val = $this->validate([
                'fraudtahunlaporandekom' => [
                    'label' => 'Total Fraud Pada Tahun Laporan (Dekompensasi)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'fraudtahunsebelumdekom' => [
                    'label' => 'Total Fraud Pada Tahun Sebelumnya (Dekompensasi)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'selesaitahunlaporandekom' => [
                    'label' => 'Telah Diselesaikan Pada Tahun Laporan (Dekompensasi)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'prosestahunlaporandekom' => [
                    'label' => 'Dalam Proses Penyelesaian Pada Tahun Laporan (Dekompensasi)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'prosestahunsebelumdekom' => [
                    'label' => 'Dalam Proses Penyelesaian Pada Tahun Sebelumnya (Dekompensasi)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'belumtahunlaporandekom' => [
                    'label' => 'Belum Diupayakan Penyelesaiannya Pada Tahun Laporan (Dekompensasi)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'belumtahunsebelumdekom' => [
                    'label' => 'Belum Diupayakan Penyelesaiannya Pada Tahun Sebelumnya (Dekompensasi)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hukumtahunlaporandekom' => [
                    'label' => 'Telah ditindaklanjuti Melalui Proses Hukum Pada Tahun Laporan (Dekompensasi)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],

            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Jumlah Penyimpangan Internal oleh Anggota Direksi (Dekompensasi)',
                    'fraudinternal' => $this->model->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('fraudinternal/index', $data);
                echo view('templates/v_footer');
            } else {
                $data = [
                    'fraudtahunlaporandekom' => $this->request->getPost('fraudtahunlaporandekom'),
                    'fraudtahunsebelumdekom' => $this->request->getPost('fraudtahunsebelumdekom'),
                    'selesaitahunlaporandekom' => $this->request->getPost('selesaitahunlaporandekom'),
                    'prosestahunlaporandekom' => $this->request->getPost('prosestahunlaporandekom'),
                    'prosestahunsebelumdekom' => $this->request->getPost('prosestahunsebelumdekom'),
                    'belumtahunlaporandekom' => $this->request->getPost('belumtahunlaporandekom'),
                    'belumtahunsebelumdekom' => $this->request->getPost('belumtahunsebelumdekom'),
                    'hukumtahunlaporandekom' => $this->request->getPost('hukumtahunlaporandekom'),
                ];

                // Insert data
                $this->model->checkIncrement();
                $success = $this->model->tambahfrauddekom($data);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil ditambahkan ');
                    return redirect()->to(base_url('fraudinternal'));
                }
            }
        } else {
            return redirect()->to(base_url('fraudinternal'));
        }
    }

    public function tambahfraudkartap()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
        }

        if (isset($_POST['tambahfraudkartap'])) {
            $val = $this->validate([
                'fraudtahunlaporankartap' => [
                    'label' => 'Total Fraud Pada Tahun Laporan (Anggota Tetap)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'fraudtahunsebelumkartap' => [
                    'label' => 'Total Fraud Pada Tahun Sebelumnya (Anggota Tetap)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'selesaitahunlaporankartap' => [
                    'label' => 'Telah Diselesaikan Pada Tahun Laporan (Anggota Tetap)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'prosestahunlaporankartap' => [
                    'label' => 'Dalam Proses Penyelesaian Pada Tahun Laporan (Anggota Tetap)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'prosestahunsebelumkartap' => [
                    'label' => 'Dalam Proses Penyelesaian Pada Tahun Sebelumnya (Anggota Tetap)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'belumtahunlaporankartap' => [
                    'label' => 'Belum Diupayakan Penyelesaiannya Pada Tahun Laporan (Anggota Tetap)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'belumtahunsebelumkartap' => [
                    'label' => 'Belum Diupayakan Penyelesaiannya Pada Tahun Sebelumnya (Anggota Tetap)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hukumtahunlaporankartap' => [
                    'label' => 'Telah ditindaklanjuti Melalui Proses Hukum Pada Tahun Laporan (Anggota Tetap)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],

            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Jumlah Penyimpangan Internal oleh Anggota Tetap',
                    'fraudinternal' => $this->model->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('fraudinternal/index', $data);
                echo view('templates/v_footer');
            } else {
                $data = [
                    'fraudtahunlaporankartap' => $this->request->getPost('fraudtahunlaporankartap'),
                    'fraudtahunsebelumkartap' => $this->request->getPost('fraudtahunsebelumkartap'),
                    'selesaitahunlaporankartap' => $this->request->getPost('selesaitahunlaporankartap'),
                    'prosestahunlaporankartap' => $this->request->getPost('prosestahunlaporankartap'),
                    'prosestahunsebelumkartap' => $this->request->getPost('prosestahunsebelumkartap'),
                    'belumtahunlaporankartap' => $this->request->getPost('belumtahunlaporankartap'),
                    'belumtahunsebelumkartap' => $this->request->getPost('belumtahunsebelumkartap'),
                    'hukumtahunlaporankartap' => $this->request->getPost('hukumtahunlaporankartap'),
                ];

                // Insert data
                $this->model->checkIncrement();
                $success = $this->model->tambahfraudkartap($data);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil ditambahkan ');
                    return redirect()->to(base_url('fraudinternal'));
                }
            }
        } else {
            return redirect()->to(base_url('fraudinternal'));
        }
    }

    public function tambahfraudkontrak()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
        }

        if (isset($_POST['tambahfraudkontrak'])) {
            $val = $this->validate([
                'fraudtahunlaporankontrak' => [
                    'label' => 'Total Fraud Pada Tahun Laporan (Anggota Kontrak)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'fraudtahunsebelumkontrak' => [
                    'label' => 'Total Fraud Pada Tahun Sebelumnya (Anggota Kontrak)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'selesaitahunlaporankontrak' => [
                    'label' => 'Telah Diselesaikan Pada Tahun Laporan (Anggota Kontrak)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'prosestahunlaporankontrak' => [
                    'label' => 'Dalam Proses Penyelesaian Pada Tahun Laporan (Anggota Kontrak)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'prosestahunsebelumkontrak' => [
                    'label' => 'Dalam Proses Penyelesaian Pada Tahun Sebelumnya (Anggota Kontrak)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'belumtahunlaporankontrak' => [
                    'label' => 'Belum Diupayakan Penyelesaiannya Pada Tahun Laporan (Anggota Kontrak)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'belumtahunsebelumkontrak' => [
                    'label' => 'Belum Diupayakan Penyelesaiannya Pada Tahun Sebelumnya (Anggota Kontrak)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hukumtahunlaporankontrak' => [
                    'label' => 'Telah ditindaklanjuti Melalui Proses Hukum Pada Tahun Laporan (Anggota Kontrak)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],

            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Jumlah Penyimpangan Internal oleh Anggota Kontrak',
                    'fraudinternal' => $this->model->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('fraudinternal/index', $data);
                echo view('templates/v_footer');
            } else {
                $data = [
                    'fraudtahunlaporankontrak' => $this->request->getPost('fraudtahunlaporankontrak'),
                    'fraudtahunsebelumkontrak' => $this->request->getPost('fraudtahunsebelumkontrak'),
                    'selesaitahunlaporankontrak' => $this->request->getPost('selesaitahunlaporankontrak'),
                    'prosestahunlaporankontrak' => $this->request->getPost('prosestahunlaporankontrak'),
                    'prosestahunsebelumkontrak' => $this->request->getPost('prosestahunsebelumkontrak'),
                    'belumtahunlaporankontrak' => $this->request->getPost('belumtahunlaporankontrak'),
                    'belumtahunsebelumkontrak' => $this->request->getPost('belumtahunsebelumkontrak'),
                    'hukumtahunlaporankontrak' => $this->request->getPost('hukumtahunlaporankontrak'),
                ];

                // Insert data
                $this->model->checkIncrement();
                $success = $this->model->tambahfraudkontrak($data);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil ditambahkan ');
                    return redirect()->to(base_url('fraudinternal'));
                }
            }
        } else {
            return redirect()->to(base_url('fraudinternal'));
        }
    }

    public function ubahfrauddir()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
        }

        if (isset($_POST['ubahfrauddir'])) {
            $val = $this->validate([
                'fraudtahunlaporandir' => [
                    'label' => 'Total Fraud Pada Tahun Laporan (Direksi)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'fraudtahunsebelumdir' => [
                    'label' => 'Total Fraud Pada Tahun Sebelumnya (Direksi)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'selesaitahunlaporandir' => [
                    'label' => 'Telah Diselesaikan Pada Tahun Laporan (Direksi)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'prosestahunlaporandir' => [
                    'label' => 'Dalam Proses Penyelesaian Pada Tahun Laporan (Direksi)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'prosestahunsebelumdir' => [
                    'label' => 'Dalam Proses Penyelesaian Pada Tahun Sebelumnya (Direksi)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'belumtahunlaporandir' => [
                    'label' => 'Belum Diupayakan Penyelesaiannya Pada Tahun Laporan (Direksi)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'belumtahunsebelumdir' => [
                    'label' => 'Belum Diupayakan Penyelesaiannya Pada Tahun Sebelumnya (Direksi)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hukumtahunlaporandir' => [
                    'label' => 'Telah ditindaklanjuti Melalui Proses Hukum Pada Tahun Laporan (Direksi)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],

            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Jumlah Penyimpangan Internal oleh Anggota Direksi',
                    'fraudinternal' => $this->model->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('fraudinternal/index', $data);
                echo view('templates/v_footer');
            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'fraudtahunlaporandir' => $this->request->getPost('fraudtahunlaporandir'),
                    'fraudtahunsebelumdir' => $this->request->getPost('fraudtahunsebelumdir'),
                    'selesaitahunlaporandir' => $this->request->getPost('selesaitahunlaporandir'),
                    'prosestahunlaporandir' => $this->request->getPost('prosestahunlaporandir'),
                    'prosestahunsebelumdir' => $this->request->getPost('prosestahunsebelumdir'),
                    'belumtahunlaporandir' => $this->request->getPost('belumtahunlaporandir'),
                    'belumtahunsebelumdir' => $this->request->getPost('belumtahunsebelumdir'),
                    'hukumtahunlaporandir' => $this->request->getPost('hukumtahunlaporandir'),
                ];

                // Insert data
                $this->model->checkIncrement();
                $success = $this->model->ubahfrauddir($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil diubahkan ');
                    return redirect()->to(base_url('fraudinternal'));
                }
            }
        } else {
            return redirect()->to(base_url('fraudinternal'));
        }
    }

    public function ubahfrauddekom()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
        }

        if (isset($_POST['ubahfrauddekom'])) {
            $val = $this->validate([
                'fraudtahunlaporandekom' => [
                    'label' => 'Total Fraud Pada Tahun Laporan (Dekom)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'fraudtahunsebelumdekom' => [
                    'label' => 'Total Fraud Pada Tahun Sebelumnya (Dekom)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'selesaitahunlaporandekom' => [
                    'label' => 'Telah Diselesaikan Pada Tahun Laporan (Dekom)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'prosestahunlaporandekom' => [
                    'label' => 'Dalam Proses Penyelesaian Pada Tahun Laporan (Dekom)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'prosestahunsebelumdekom' => [
                    'label' => 'Dalam Proses Penyelesaian Pada Tahun Sebelumnya (Dekom)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'belumtahunlaporandekom' => [
                    'label' => 'Belum Diupayakan Penyelesaiannya Pada Tahun Laporan (Dekom)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'belumtahunsebelumdekom' => [
                    'label' => 'Belum Diupayakan Penyelesaiannya Pada Tahun Sebelumnya (Dekom)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hukumtahunlaporandekom' => [
                    'label' => 'Telah ditindaklanjuti Melalui Proses Hukum Pada Tahun Laporan (Dekom)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],

            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Jumlah Penyimpangan Internal oleh Anggota Dewan Komisaris',
                    'fraudinternal' => $this->model->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('fraudinternal/index', $data);
                echo view('templates/v_footer');
            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'fraudtahunlaporandekom' => $this->request->getPost('fraudtahunlaporandekom'),
                    'fraudtahunsebelumdekom' => $this->request->getPost('fraudtahunsebelumdekom'),
                    'selesaitahunlaporandekom' => $this->request->getPost('selesaitahunlaporandekom'),
                    'prosestahunlaporandekom' => $this->request->getPost('prosestahunlaporandekom'),
                    'prosestahunsebelumdekom' => $this->request->getPost('prosestahunsebelumdekom'),
                    'belumtahunlaporandekom' => $this->request->getPost('belumtahunlaporandekom'),
                    'belumtahunsebelumdekom' => $this->request->getPost('belumtahunsebelumdekom'),
                    'hukumtahunlaporandekom' => $this->request->getPost('hukumtahunlaporandekom'),
                ];

                // Insert data
                $this->model->checkIncrement();
                $success = $this->model->ubahfrauddekom($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil diubahkan ');
                    return redirect()->to(base_url('fraudinternal'));
                }
            }
        } else {
            return redirect()->to(base_url('fraudinternal'));
        }
    }

    public function ubahfraudkartap()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
        }

        if (isset($_POST['ubahfraudkartap'])) {
            $val = $this->validate([
                'fraudtahunlaporankartap' => [
                    'label' => 'Total Fraud Pada Tahun Laporan (Karyawan Tetap)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'fraudtahunsebelumkartap' => [
                    'label' => 'Total Fraud Pada Tahun Sebelumnya (Karyawan Tetap)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'selesaitahunlaporankartap' => [
                    'label' => 'Telah Diselesaikan Pada Tahun Laporan (Karyawan Tetap)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'prosestahunlaporankartap' => [
                    'label' => 'Dalam Proses Penyelesaian Pada Tahun Laporan (Karyawan Tetap)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'prosestahunsebelumkartap' => [
                    'label' => 'Dalam Proses Penyelesaian Pada Tahun Sebelumnya (Karyawan Tetap)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'belumtahunlaporankartap' => [
                    'label' => 'Belum Diupayakan Penyelesaiannya Pada Tahun Laporan (Karyawan Tetap)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'belumtahunsebelumkartap' => [
                    'label' => 'Belum Diupayakan Penyelesaiannya Pada Tahun Sebelumnya (Karyawan Tetap)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hukumtahunlaporankartap' => [
                    'label' => 'Telah ditindaklanjuti Melalui Proses Hukum Pada Tahun Laporan (Karyawan Tetap)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],

            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Jumlah Penyimpangan Internal oleh Karyawan Tetap',
                    'fraudinternal' => $this->model->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('fraudinternal/index', $data);
                echo view('templates/v_footer');
            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'fraudtahunlaporankartap' => $this->request->getPost('fraudtahunlaporankartap'),
                    'fraudtahunsebelumkartap' => $this->request->getPost('fraudtahunsebelumkartap'),
                    'selesaitahunlaporankartap' => $this->request->getPost('selesaitahunlaporankartap'),
                    'prosestahunlaporankartap' => $this->request->getPost('prosestahunlaporankartap'),
                    'prosestahunsebelumkartap' => $this->request->getPost('prosestahunsebelumkartap'),
                    'belumtahunlaporankartap' => $this->request->getPost('belumtahunlaporankartap'),
                    'belumtahunsebelumkartap' => $this->request->getPost('belumtahunsebelumkartap'),
                    'hukumtahunlaporankartap' => $this->request->getPost('hukumtahunlaporankartap'),
                ];

                // Insert data
                $this->model->checkIncrement();
                $success = $this->model->ubahfraudkartap($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil diubahkan ');
                    return redirect()->to(base_url('fraudinternal'));
                }
            }
        } else {
            return redirect()->to(base_url('fraudinternal'));
        }
    }

    public function ubahfraudkontrak()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
        }

        if (isset($_POST['ubahfraudkontrak'])) {
            $val = $this->validate([
                'fraudtahunlaporankontrak' => [
                    'label' => 'Total Fraud Pada Tahun Laporan (Karyawan Kontrak)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'fraudtahunsebelumkontrak' => [
                    'label' => 'Total Fraud Pada Tahun Sebelumnya (Karyawan Kontrak)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'selesaitahunlaporankontrak' => [
                    'label' => 'Telah Diselesaikan Pada Tahun Laporan (Karyawan Kontrak)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'prosestahunlaporankontrak' => [
                    'label' => 'Dalam Proses Penyelesaian Pada Tahun Laporan (Karyawan Kontrak)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'prosestahunsebelumkontrak' => [
                    'label' => 'Dalam Proses Penyelesaian Pada Tahun Sebelumnya (Karyawan Kontrak)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'belumtahunlaporankontrak' => [
                    'label' => 'Belum Diupayakan Penyelesaiannya Pada Tahun Laporan (Karyawan Kontrak)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'belumtahunsebelumkontrak' => [
                    'label' => 'Belum Diupayakan Penyelesaiannya Pada Tahun Sebelumnya (Karyawan Kontrak)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hukumtahunlaporankontrak' => [
                    'label' => 'Telah ditindaklanjuti Melalui Proses Hukum Pada Tahun Laporan (Karyawan Kontrak)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],

            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Jumlah Penyimpangan Internal oleh Karyawan Kontrak',
                    'fraudinternal' => $this->model->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('fraudinternal/index', $data);
                echo view('templates/v_footer');
            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'fraudtahunlaporankontrak' => $this->request->getPost('fraudtahunlaporankontrak'),
                    'fraudtahunsebelumkontrak' => $this->request->getPost('fraudtahunsebelumkontrak'),
                    'selesaitahunlaporankontrak' => $this->request->getPost('selesaitahunlaporankontrak'),
                    'prosestahunlaporankontrak' => $this->request->getPost('prosestahunlaporankontrak'),
                    'prosestahunsebelumkontrak' => $this->request->getPost('prosestahunsebelumkontrak'),
                    'belumtahunlaporankontrak' => $this->request->getPost('belumtahunlaporankontrak'),
                    'belumtahunsebelumkontrak' => $this->request->getPost('belumtahunsebelumkontrak'),
                    'hukumtahunlaporankontrak' => $this->request->getPost('hukumtahunlaporankontrak'),
                ];

                // Insert data
                $this->model->checkIncrement();
                $success = $this->model->ubahfraudkontrak($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil diubahkan ');
                    return redirect()->to(base_url('fraudinternal'));
                }
            }
        } else {
            return redirect()->to(base_url('fraudinternal'));
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
                    'judul' => 'Keterangan',
                    'fraudinternal' => $this->model->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('fraudinternal/index', $data);
                echo view('templates/v_footer');

            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'keterangan' => $this->request->getPost('keterangan')
                ];

                // Update data
                $success = $this->model->ubah($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Keterangan berhasil diubah ');
                    return redirect()->to(base_url('fraudinternal'));
                }
            }
        } else {
            return redirect()->to(base_url('fraudinternal'));
        }
    }

    public function hapus($id)
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()->to($redirectURL);
        }

        // Memanggil fungsi hapus pada model dan menyimpan hasilnya dalam variabel $success
        $this->model->hapus($id);
        session()->setFlashdata('message', 'Data berhasil dihapus');

        return redirect()->to(base_url('fraudinternal'));

    }

    public function approve($idfraudinternal)
    {
        if (!is_numeric($idfraudinternal) || $idfraudinternal <= 0) {
            session()->setFlashdata('err', 'ID Data Jumlah Penyimpangan Intern (Internal Fraud) tidak valid.');
            return redirect()->back();
        }

        $fraudinternal = $this->fraudinternalModel->find($idfraudinternal);
        if (!$fraudinternal) {
            session()->setFlashdata('err', 'Data Data Jumlah Penyimpangan Intern (Internal Fraud) dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idfraudinternal,
            'is_approved' => 1,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->fraudinternalModel->save($dataUpdate)) {
            session()->setFlashdata('message', 'Data Jumlah Penyimpangan Intern (Internal Fraud) berhasil disetujui.');
            return redirect()->back();
        } else {
            session()->setFlashdata('err', 'Terjadi kesalahan saat melakukan approval.');
            return redirect()->back();
        }
    }

    public function unapprove($idfraudinternal)
    {
        if (!is_numeric($idfraudinternal) || $idfraudinternal <= 0) {
            session()->setFlashdata('err', 'ID Data Jumlah Penyimpangan Intern (Internal Fraud) tidak valid.');
            return redirect()->back();
        }

        $fraudinternal = $this->fraudinternalModel->find($idfraudinternal);
        if (!$fraudinternal) {
            session()->setFlashdata('err', 'Data Data Jumlah Penyimpangan Intern (Internal Fraud) dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idfraudinternal,
            'is_approved' => 2,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->fraudinternalModel->save($dataUpdate)) {
            session()->setFlashdata('err', 'Approval Data Jumlah Penyimpangan Intern (Internal Fraud) dibatalkan.');
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

        $this->fraudinternalModel->builder()->update($dataUpdate);

        session()->setFlashdata('message', 'Semua Data Jumlah Penyimpangan Intern (Internal Fraud) berhasil disetujui.');
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

        $this->fraudinternalModel->builder()->update($dataUpdate);

        session()->setFlashdata('err', 'Semua approval data Jumlah Penyimpangan Intern (Internal Fraud) dibatalkan.');
        return redirect()->back();
    }

    public function excel()
    {
        $data = [
            'fraudinternal' => $this->model->getAllData()
        ];

        echo view('fraudinternal/excel', $data);

    }

    public function exporttxtfraudinternal()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }
        $data_fraudinternal = $this->model->getAllData();
        $data_infobpr = $this->infobprModel->getAllData();

        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'];
            $kodejenis = $infobpr['kodejenis'];
        }

        $output = "";
        $tanggal_laporan = date('Y-m-d', strtotime('+1 month', strtotime('2025-04-30')));
        $output .= "H01|" . $kodejenis . "|" . $sandibpr . "|" . $tanggal_laporan . "|LTBPRK|E0800|0|\n";

        // Generate D01 - Unique non-empty fraud occurrence values (910)
        $fraud_values = [];
        foreach ($data_fraudinternal as $row) {
            $values = [
                $row['fraudtahunsebelumdir'],
                $row['fraudtahunlaporandir'],
                $row['fraudtahunsebelumdekom'],
                $row['fraudtahunlaporandekom'],
                $row['fraudtahunsebelumkartap'],
                $row['fraudtahunlaporankartap'],
                $row['fraudtahunsebelumkontrak'],
                $row['fraudtahunlaporankontrak'],
            ];
            $non_empty_values = array_filter($values, function ($value) {
                return !empty($value);
            });
            $fraud_values = array_merge($fraud_values, $non_empty_values);
        }
        $unique_fraud_values = array_unique($fraud_values);
        if (!empty($unique_fraud_values)) {
            $output .= "D01|" . "910" . "|" . implode("|", $unique_fraud_values) . "\n";
        }

        // Generate D01 - Unique non-empty selesai values (911)
        $selesai_values = [];
        foreach ($data_fraudinternal as $row) {
            $values = [
                $row['selesaitahunlaporandir'],
                $row['selesaitahunlaporandekom'],
                $row['selesaitahunlaporankartap'],
                $row['selesaitahunlaporankontrak'],
            ];
            $non_empty_values = array_filter($values, function ($value) {
                return !empty($value);
            });
            $selesai_values = array_merge($selesai_values, $non_empty_values);
        }
        $unique_selesai_values = array_unique($selesai_values);
        if (!empty($unique_selesai_values)) {
            $output .= "D01|" . "911|" . implode("||", $unique_selesai_values) . "\n";
        }

        // Generate D01 - Unique non-empty proses values (912)
        $proses_values = [];
        foreach ($data_fraudinternal as $row) {
            $values = [
                $row['prosestahunlaporandir'],
                $row['prosestahunsebelumdir'],
                $row['prosestahunlaporandekom'],
                $row['prosestahunsebelumdekom'],
                $row['prosestahunlaporankartap'],
                $row['prosestahunsebelumkartap'],
                $row['prosestahunlaporankontrak'],
                $row['prosestahunsebelumkontrak'],
            ];
            $non_empty_values = array_filter($values, function ($value) {
                return !empty($value);
            });
            $proses_values = array_merge($proses_values, $non_empty_values);
        }
        $unique_proses_values = array_unique($proses_values);
        if (!empty($unique_proses_values)) {
            $output .= "D01|" . "912" . "|" . implode("|", $unique_proses_values) . "\n";
        }

        // Generate D01 - Unique non-empty belum values (913)
        $belum_values = [];
        foreach ($data_fraudinternal as $row) {
            $values = [
                $row['belumtahunsebelumdir'],
                $row['belumtahunlaporandir'],
                $row['belumtahunsebelumdekom'],
                $row['belumtahunlaporandekom'],
                $row['belumtahunsebelumkartap'],
                $row['belumtahunlaporankartap'],
                $row['belumtahunsebelumkontrak'],
                $row['belumtahunlaporankontrak'],
            ];
            $non_empty_values = array_filter($values, function ($value) {
                return !empty($value);
            });
            $belum_values = array_merge($belum_values, $non_empty_values);
        }
        $unique_belum_values = array_unique($belum_values);
        if (!empty($unique_belum_values)) {
            $output .= "D01|" . "913|" . implode("|", $unique_belum_values) . "\n";
        }
        $hukum_values = [];
        foreach ($data_fraudinternal as $row) {
            $values = [
                $row['hukumtahunlaporandir'],
                $row['hukumtahunlaporandekom'],
                $row['hukumtahunlaporankartap'],
                $row['hukumtahunlaporankontrak'],
            ];
            $non_empty_values = array_filter($values, function ($value) {
                return !empty($value);
            });
            $hukum_values = array_merge($hukum_values, $non_empty_values);
        }
        $unique_hukum_values = array_unique($hukum_values);
        if (!empty($unique_hukum_values)) {
            $output .= "D01|" . "920|" . implode("||", $unique_hukum_values) . "\n";
        }

        if (!empty($data_fraudinternal)) {
            $footer_row = end($data_fraudinternal);
            $output .= "F01|" . "Footer 1" . "|" . $footer_row['keterangan'];
        } else {
            $output .= "F01|" . "Footer 1" . "|";
        }

        $response = service('response');

        $filename = "LTBPRK-E0800-R-A-20250531-" . $sandibpr . "-01.txt";

        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->setBody($output);

        return $response;
    }

}


