<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_paketkebijakandirdekom;
use App\Models\M_tgjwbdir;
use App\Models\M_tgjwbdekom;
use App\Models\M_infobpr;
use App\Models\M_user;
use Myth\Auth\Config\Services as AuthServices;

class paketkebijakandirdekom extends Controller
{
    protected $paketkebijakandirdekomModel;
    protected $tgjwbdirModel;
    protected $tgjwbdekomModel;
    protected $shmusahadirdekomModel;
    protected $infobprModel;
    protected $usermodel;
    protected $session;
    protected $auth;

    public function __construct()
    {
        $this->paketkebijakandirdekomModel = new M_paketkebijakandirdekom();
        $this->tgjwbdirModel = new M_tgjwbdir();
        $this->tgjwbdekomModel = new M_tgjwbdekom();
        $this->userModel = new M_user();
        $this->infobprModel = new M_infobpr();
        $this->session = service('session');
        $this->auth = service('authentication');
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

        $paketkebijakandirdekomData = $this->paketkebijakandirdekomModel->getAllData();

        $authorize = AuthServices::authorization();
        $userInGroupPE = $authorize->inGroup('pe', $this->auth->id());
        $userInGroupAdmin = $authorize->inGroup('admin', $this->auth->id());
        $userInGroupDekom = $authorize->inGroup('dekom', $this->auth->id());
        $userInGroupDireksi = $authorize->inGroup('direksi', $this->auth->id());

        $data = [
            'judul' => '11. Paket/Kebijakan Remunerasi dan Fasilitas Lain bagi Direksi dan Dewan Komisaris',
            'paketkebijakandirdekom' => $this->paketkebijakandirdekomModel->getAllData(),
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
        echo view('paketkebijakandirdekom/index', $data);
        echo view('templates/v_footer');
    }

    public function tambahgaji()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
        }

        if (isset($_POST['tambahgaji'])) {
            $val = $this->validate([
                'penerimagajidir' => [
                    'label' => 'Jumlah Direksi Penerima Gaji',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nominalgajidir' => [
                    'label' => 'Jumlah Nominal Keseluruhan Gaji Direksi (Rp)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'penerimagajidekom' => [
                    'label' => 'Jumlah Komisaris Penerima Gaji',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nominalgajidekom' => [
                    'label' => 'Jumlah Nominal Keseluruhan Gaji Komisaris (Rp)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]

            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Gaji Bagi Direksi dan Dewan Komisaris',
                    'paketkebijakandirdekom' => $this->paketkebijakandirdekomModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('paketkebijakandirdekom/index', $data);
                echo view('templates/v_footer');
            } else {
                $data = [
                    'penerimagajidir' => $this->request->getPost('penerimagajidir'),
                    'nominalgajidir' => $this->request->getPost('nominalgajidir'),
                    'penerimagajidekom' => $this->request->getPost('penerimagajidekom'),
                    'nominalgajidekom' => $this->request->getPost('nominalgajidekom')
                ];

                // Insert data
                $this->paketkebijakandirdekomModel->checkIncrement();
                $success = $this->paketkebijakandirdekomModel->tambahgaji($data);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil ditambahkan ');
                    return redirect()->to(base_url('paketkebijakandirdekom'));
                }
            }
        } else {
            return redirect()->to(base_url('paketkebijakandirdekom'));
        }
    }

    public function tambahtunjangan()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
        }

        if (isset($_POST['tambahtunjangan'])) {
            $val = $this->validate([
                'terimatunjangandir' => [
                    'label' => 'Jumlah Direksi Penerima Tunjangan',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nominaltunjangandir' => [
                    'label' => 'Jumlah Nominal Keseluruhan Tunjangan Direksi',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'terimatunjangandekom' => [
                    'label' => 'Jumlah Komisaris Penerima Tunjangan',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nominaltunjangandekom' => [
                    'label' => 'Jumlah Nominal Keseluruhan Tunjangan Komisaris (Rp)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Tunjangan Bagi Direksi dan Dewan Komisaris',
                    'paketkebijakandirdekom' => $this->paketkebijakandirdekomModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('paketkebijakandirdekom/index', $data);
                echo view('templates/v_footer');
            } else {
                $data = [
                    'terimatunjangandir' => $this->request->getPost('terimatunjangandir'),
                    'nominaltunjangandir' => $this->request->getPost('nominaltunjangandir'),
                    'terimatunjangandekom' => $this->request->getPost('terimatunjangandekom'),
                    'nominaltunjangandekom' => $this->request->getPost('nominaltunjangandekom')
                ];

                // Insert data
                $this->paketkebijakandirdekomModel->checkIncrement();
                $success = $this->paketkebijakandirdekomModel->tambahtunjangan($data);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil ditambahkan ');
                    return redirect()->to(base_url('paketkebijakandirdekom'));
                }
            }
        } else {
            return redirect()->to(base_url('paketkebijakandirdekom'));
        }
    }

    public function tambahtantiem()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
        }

        if (isset($_POST['tambahtantiem'])) {
            $val = $this->validate([
                'terimatantiemdir' => [
                    'label' => 'Jumlah Direksi Penerima Tantiem',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nominaltantiemdir' => [
                    'label' => 'Jumlah Nominal Keseluruhan Tantiem Direksi (Rp)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'terimatantiemdekom' => [
                    'label' => 'Jumlah Komisaris Penerima Tantiem',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nominaltantiemdekom' => [
                    'label' => 'Jumlah Nominal Keseluruhan Tantiem Komisaris (Rp)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Tantiem Bagi Direksi dan Dewan Komisaris',
                    'paketkebijakandirdekom' => $this->paketkebijakandirdekomModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('paketkebijakandirdekom/index', $data);
                echo view('templates/v_footer');
            } else {
                $data = [
                    'terimatantiemdir' => $this->request->getPost('terimatantiemdir'),
                    'nominaltantiemdir' => $this->request->getPost('nominaltantiemdir'),
                    'terimatantiemdekom' => $this->request->getPost('terimatantiemdekom'),
                    'nominaltantiemdekom' => $this->request->getPost('nominaltantiemdekom')
                ];

                // Insert data
                $this->paketkebijakandirdekomModel->checkIncrement();
                $success = $this->paketkebijakandirdekomModel->tambahtantiem($data);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil ditambahkan ');
                    return redirect()->to(base_url('paketkebijakandirdekom'));
                }
            }
        } else {
            return redirect()->to(base_url('paketkebijakandirdekom'));
        }
    }

    public function tambahsaham()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
        }

        if (isset($_POST['tambahsaham'])) {
            $val = $this->validate([
                'terimashmdir' => [
                    'label' => 'Jumlah Direksi Penerima Kompensasi berbasis saham',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nominalshmdir' => [
                    'label' => 'Jumlah Nominal Keseluruhan Kompensasi berbasis saham Direksi (Rp)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'terimashmdekom' => [
                    'label' => 'Jumlah Komisaris Penerima Kompensasi berbasis saham',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nominalshmdekom' => [
                    'label' => 'Jumlah Nominal Keseluruhan Kompensasi berbasis saham Komisaris (Rp)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Kompensasi berbasis saham Bagi Direksi dan Dewan Komisaris',
                    'paketkebijakandirdekom' => $this->paketkebijakandirdekomModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('paketkebijakandirdekom/index', $data);
                echo view('templates/v_footer');
            } else {
                $data = [
                    'terimashmdir' => $this->request->getPost('terimashmdir'),
                    'nominalshmdir' => $this->request->getPost('nominalshmdir'),
                    'terimashmdekom' => $this->request->getPost('terimashmdekom'),
                    'nominalshmdekom' => $this->request->getPost('nominalshmdekom')
                ];

                // Insert data
                $this->paketkebijakandirdekomModel->checkIncrement();
                $success = $this->paketkebijakandirdekomModel->tambahsaham($data);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil ditambahkan ');
                    return redirect()->to(base_url('paketkebijakandirdekom'));
                }
            }
        } else {
            return redirect()->to(base_url('paketkebijakandirdekom'));
        }
    }

    public function tambahremun()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
        }

        if (isset($_POST['tambahremun'])) {
            $val = $this->validate([
                'terimaremunlaindir' => [
                    'label' => 'Jumlah Direksi Penerima Remunerasi lainnya',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nominalremunlaindir' => [
                    'label' => 'Jumlah Nominal Keseluruhan Remunerasi lainnya Direksi (Rp)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'terimaremunlaindekom' => [
                    'label' => 'Jumlah Komisaris Penerima Remunerasi lainnya',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nominalremunlaindekom' => [
                    'label' => 'Jumlah Nominal Keseluruhan Remunerasi lainnya Komisaris (Rp)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Remunerasi lainnya Bagi Direksi dan Dewan Komisaris',
                    'paketkebijakandirdekom' => $this->paketkebijakandirdekomModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('paketkebijakandirdekom/index', $data);
                echo view('templates/v_footer');
            } else {
                $data = [
                    'terimaremunlaindir' => $this->request->getPost('terimaremunlaindir'),
                    'nominalremunlaindir' => $this->request->getPost('nominalremunlaindir'),
                    'terimaremunlaindekom' => $this->request->getPost('terimaremunlaindekom'),
                    'nominalremunlaindekom' => $this->request->getPost('nominalremunlaindekom')
                ];

                // Insert data
                $this->paketkebijakandirdekomModel->checkIncrement();
                $success = $this->paketkebijakandirdekomModel->tambahremun($data);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil ditambahkan ');
                    return redirect()->to(base_url('paketkebijakandirdekom'));
                }
            }
        } else {
            return redirect()->to(base_url('paketkebijakandirdekom'));
        }
    }

    public function tambahrumah()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
        }

        if (isset($_POST['tambahrumah'])) {
            $val = $this->validate([
                'terimarumahdir' => [
                    'label' => 'Jumlah Direksi Penerima Perumahan (Orang)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nominalrumahdir' => [
                    'label' => 'Jumlah Nominal Perumahan Direksi (Rp)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'terimarumahdekom' => [
                    'label' => 'Jumlah Komisaris Penerima Perumahan (Orang)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nominalrumahdekom' => [
                    'label' => 'Jumlah Nominal Perumahan Komisaris (Rp)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Perumahan Bagi Direksi dan Dewan Komisaris',
                    'paketkebijakandirdekom' => $this->paketkebijakandirdekomModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('paketkebijakandirdekom/index', $data);
                echo view('templates/v_footer');
            } else {
                $data = [
                    'terimarumahdir' => $this->request->getPost('terimarumahdir'),
                    'nominalrumahdir' => $this->request->getPost('nominalrumahdir'),
                    'terimarumahdekom' => $this->request->getPost('terimarumahdekom'),
                    'nominalrumahdekom' => $this->request->getPost('nominalrumahdekom')
                ];

                // Insert data
                $this->paketkebijakandirdekomModel->checkIncrement();
                $success = $this->paketkebijakandirdekomModel->tambahrumah($data);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil ditambahkan ');
                    return redirect()->to(base_url('paketkebijakandirdekom'));
                }
            }
        } else {
            return redirect()->to(base_url('paketkebijakandirdekom'));
        }
    }

    public function tambahtransport()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
        }

        if (isset($_POST['tambahtransport'])) {
            $val = $this->validate([
                'terimatransportdir' => [
                    'label' => 'Jumlah Direksi Penerima Transportasi (Orang)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nominaltransportdir' => [
                    'label' => 'Jumlah Nominal Transportasi Direksi (Rp)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'terimatransportdekom' => [
                    'label' => 'Jumlah Komisaris Penerima Transportasi (Orang)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nominaltransportdekom' => [
                    'label' => 'Jumlah Nominal Transportasi Komisaris (Rp)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Transportasi Bagi Direksi dan Dewan Komisaris',
                    'paketkebijakandirdekom' => $this->paketkebijakandirdekomModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('paketkebijakandirdekom/index', $data);
                echo view('templates/v_footer');
            } else {
                $data = [
                    'terimatransportdir' => $this->request->getPost('terimatransportdir'),
                    'nominaltransportdir' => $this->request->getPost('nominaltransportdir'),
                    'terimatransportdekom' => $this->request->getPost('terimatransportdekom'),
                    'nominaltransportdekom' => $this->request->getPost('nominaltransportdekom')
                ];

                // Insert data
                $this->paketkebijakandirdekomModel->checkIncrement();
                $success = $this->paketkebijakandirdekomModel->tambahtransport($data);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil ditambahkan ');
                    return redirect()->to(base_url('paketkebijakandirdekom'));
                }
            }
        } else {
            return redirect()->to(base_url('paketkebijakandirdekom'));
        }
    }

    public function tambahasuransi()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
        }

        if (isset($_POST['tambahasuransi'])) {
            $val = $this->validate([
                'terimaasuransidir' => [
                    'label' => 'Jumlah Direksi Penerima Asuransi Kesehatan (Orang)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nominalasuransidir' => [
                    'label' => 'Jumlah Nominal Asuransi Kesehatan Direksi (Rp)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'terimaasuransidekom' => [
                    'label' => 'Jumlah Komisaris Penerima Asuransi Kesehatan (Orang)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nominalasuransidekom' => [
                    'label' => 'Jumlah Nominal Asuransi Kesehatan Komisaris (Rp)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Asuransi Kesehatan Bagi Direksi dan Dewan Komisaris',
                    'paketkebijakandirdekom' => $this->paketkebijakandirdekomModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('paketkebijakandirdekom/index', $data);
                echo view('templates/v_footer');
            } else {
                $data = [
                    'terimaasuransidir' => $this->request->getPost('terimaasuransidir'),
                    'nominalasuransidir' => $this->request->getPost('nominalasuransidir'),
                    'terimaasuransidekom' => $this->request->getPost('terimaasuransidekom'),
                    'nominalasuransidekom' => $this->request->getPost('nominalasuransidekom')
                ];

                // Insert data
                $this->paketkebijakandirdekomModel->checkIncrement();
                $success = $this->paketkebijakandirdekomModel->tambahasuransi($data);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil ditambahkan ');
                    return redirect()->to(base_url('paketkebijakandirdekom'));
                }
            }
        } else {
            return redirect()->to(base_url('paketkebijakandirdekom'));
        }
    }

    public function tambahfasilitas()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
        }

        if (isset($_POST['tambahfasilitas'])) {
            $val = $this->validate([
                'terimafasilitasdir' => [
                    'label' => 'Jumlah Direksi Penerima Fasilitas Lain-Lainnya (Orang)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nominalfasilitasdir' => [
                    'label' => 'Jumlah Nominal Fasilitas Lain-Lainnya Direksi (Rp)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'terimafasilitasdekom' => [
                    'label' => 'Jumlah Komisaris Penerima Fasilitas Lain-Lainnya (Orang)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nominalfasilitasdekom' => [
                    'label' => 'Jumlah Nominal Fasilitas Lain-Lainnya Komisaris (Rp)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Fasilitas Lain-Lainnya Bagi Direksi dan Dewan Komisaris',
                    'paketkebijakandirdekom' => $this->paketkebijakandirdekomModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('paketkebijakandirdekom/index', $data);
                echo view('templates/v_footer');
            } else {
                $data = [
                    'terimafasilitasdir' => $this->request->getPost('terimafasilitasdir'),
                    'nominalfasilitasdir' => $this->request->getPost('nominalfasilitasdir'),
                    'terimafasilitasdekom' => $this->request->getPost('terimafasilitasdekom'),
                    'nominalfasilitasdekom' => $this->request->getPost('nominalfasilitasdekom')
                ];

                // Insert data
                $this->paketkebijakandirdekomModel->checkIncrement();
                $success = $this->paketkebijakandirdekomModel->tambahfasilitas($data);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil ditambahkan ');
                    return redirect()->to(base_url('paketkebijakandirdekom'));
                }
            }
        } else {
            return redirect()->to(base_url('paketkebijakandirdekom'));
        }
    }

    public function ubahgaji()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
        }

        if (isset($_POST['ubahgaji'])) {
            $val = $this->validate([
                'penerimagajidir' => [
                    'label' => 'Jumlah Direksi Penerima Gaji',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nominalgajidir' => [
                    'label' => 'Jumlah Nominal Keseluruhan Gaji Direksi (Rp)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'penerimagajidekom' => [
                    'label' => 'Jumlah Komisaris Penerima Gaji',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nominalgajidekom' => [
                    'label' => 'Jumlah Nominal Keseluruhan Gaji Komisaris (Rp)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Jumlah Penyimpangan Internal oleh Anggota Direksi',
                    'paketkebijakandirdekom' => $this->paketkebijakandirdekomModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('paketkebijakandirdekom/index', $data);
                echo view('templates/v_footer');
            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'penerimagajidir' => $this->request->getPost('penerimagajidir'),
                    'nominalgajidir' => $this->request->getPost('nominalgajidir'),
                    'penerimagajidekom' => $this->request->getPost('penerimagajidekom'),
                    'nominalgajidekom' => $this->request->getPost('nominalgajidekom')
                ];

                // Insert data
                $this->paketkebijakandirdekomModel->checkIncrement();
                $success = $this->paketkebijakandirdekomModel->ubahgaji($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil diubahkan ');
                    return redirect()->to(base_url('paketkebijakandirdekom'));
                }
            }
        } else {
            return redirect()->to(base_url('paketkebijakandirdekom'));
        }
    }

    public function ubahtunjangan()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
        }

        if (isset($_POST['ubahtunjangan'])) {
            $val = $this->validate([
                'terimatunjangandir' => [
                    'label' => 'Jumlah Direksi Penerima Tunjangan',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nominaltunjangandir' => [
                    'label' => 'Jumlah Nominal Keseluruhan Tunjangan Direksi',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'terimatunjangandekom' => [
                    'label' => 'Jumlah Komisaris Penerima Tunjangan',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nominaltunjangandekom' => [
                    'label' => 'Jumlah Nominal Keseluruhan Tunjangan Komisaris (Rp)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Tunjangan Bagi Direksi dan Dewan Komisaris',
                    'paketkebijakandirdekom' => $this->paketkebijakandirdekomModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('paketkebijakandirdekom/index', $data);
                echo view('templates/v_footer');
            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'terimatunjangandir' => $this->request->getPost('terimatunjangandir'),
                    'nominaltunjangandir' => $this->request->getPost('nominaltunjangandir'),
                    'terimatunjangandekom' => $this->request->getPost('terimatunjangandekom'),
                    'nominaltunjangandekom' => $this->request->getPost('nominaltunjangandekom')
                ];

                // Insert data
                $this->paketkebijakandirdekomModel->checkIncrement();
                $success = $this->paketkebijakandirdekomModel->ubahtunjangan($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil diubahkan ');
                    return redirect()->to(base_url('paketkebijakandirdekom'));
                }
            }
        } else {
            return redirect()->to(base_url('paketkebijakandirdekom'));
        }
    }

    public function ubahtantiem()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
        }

        if (isset($_POST['ubahtantiem'])) {
            $val = $this->validate([
                'terimatantiemdir' => [
                    'label' => 'Jumlah Direksi Penerima Tantiem',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nominaltantiemdir' => [
                    'label' => 'Jumlah Nominal Keseluruhan Tantiem Direksi (Rp)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'terimatantiemdekom' => [
                    'label' => 'Jumlah Komisaris Penerima Tantiem',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nominaltantiemdekom' => [
                    'label' => 'Jumlah Nominal Keseluruhan Tantiem Komisaris (Rp)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Tantiem Bagi Direksi dan Dewan Komisaris',
                    'paketkebijakandirdekom' => $this->paketkebijakandirdekomModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('paketkebijakandirdekom/index', $data);
                echo view('templates/v_footer');
            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'terimatantiemdir' => $this->request->getPost('terimatantiemdir'),
                    'nominaltantiemdir' => $this->request->getPost('nominaltantiemdir'),
                    'terimatantiemdekom' => $this->request->getPost('terimatantiemdekom'),
                    'nominaltantiemdekom' => $this->request->getPost('nominaltantiemdekom')
                ];

                // Insert data
                $this->paketkebijakandirdekomModel->checkIncrement();
                $success = $this->paketkebijakandirdekomModel->ubahtantiem($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil diubahkan ');
                    return redirect()->to(base_url('paketkebijakandirdekom'));
                }
            }
        } else {
            return redirect()->to(base_url('paketkebijakandirdekom'));
        }
    }

    public function ubahsaham()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
        }

        if (isset($_POST['ubahsaham'])) {
            $val = $this->validate([
                'terimashmdir' => [
                    'label' => 'Jumlah Direksi Penerima Kompensasi berbasis saham',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nominalshmdir' => [
                    'label' => 'Jumlah Nominal Keseluruhan Kompensasi berbasis saham Direksi (Rp)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'terimashmdekom' => [
                    'label' => 'Jumlah Komisaris Penerima Kompensasi berbasis saham',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nominalshmdekom' => [
                    'label' => 'Jumlah Nominal Keseluruhan Kompensasi berbasis saham Komisaris (Rp)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Kompensasi berbasis saham Bagi Direksi dan Dewan Komisaris',
                    'paketkebijakandirdekom' => $this->paketkebijakandirdekomModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('paketkebijakandirdekom/index', $data);
                echo view('templates/v_footer');
            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'terimashmdir' => $this->request->getPost('terimashmdir'),
                    'nominalshmdir' => $this->request->getPost('nominalshmdir'),
                    'terimashmdekom' => $this->request->getPost('terimashmdekom'),
                    'nominalshmdekom' => $this->request->getPost('nominalshmdekom')
                ];

                // Insert data
                $this->paketkebijakandirdekomModel->checkIncrement();
                $success = $this->paketkebijakandirdekomModel->ubahsaham($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil diubahkan ');
                    return redirect()->to(base_url('paketkebijakandirdekom'));
                }
            }
        } else {
            return redirect()->to(base_url('paketkebijakandirdekom'));
        }
    }

    public function ubahremun()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
        }

        if (isset($_POST['ubahremun'])) {
            $val = $this->validate([
                'terimaremunlaindir' => [
                    'label' => 'Jumlah Direksi Penerima Remunerasi lainnya',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nominalremunlaindir' => [
                    'label' => 'Jumlah Nominal Keseluruhan Remunerasi lainnya Direksi (Rp)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'terimaremunlaindekom' => [
                    'label' => 'Jumlah Komisaris Penerima Remunerasi lainnya',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nominalremunlaindekom' => [
                    'label' => 'Jumlah Nominal Keseluruhan Remunerasi lainnya Komisaris (Rp)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Remunerasi lainnya Bagi Direksi dan Dewan Komisaris',
                    'paketkebijakandirdekom' => $this->paketkebijakandirdekomModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('paketkebijakandirdekom/index', $data);
                echo view('templates/v_footer');
            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'terimaremunlaindir' => $this->request->getPost('terimaremunlaindir'),
                    'nominalremunlaindir' => $this->request->getPost('nominalremunlaindir'),
                    'terimaremunlaindekom' => $this->request->getPost('terimaremunlaindekom'),
                    'nominalremunlaindekom' => $this->request->getPost('nominalremunlaindekom')
                ];

                // Insert data
                $this->paketkebijakandirdekomModel->checkIncrement();
                $success = $this->paketkebijakandirdekomModel->ubahremun($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil diubahkan ');
                    return redirect()->to(base_url('paketkebijakandirdekom'));
                }
            }
        } else {
            return redirect()->to(base_url('paketkebijakandirdekom'));
        }
    }

    public function ubahrumah()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
        }

        if (isset($_POST['ubahrumah'])) {
            $val = $this->validate([
                'terimarumahdir' => [
                    'label' => 'Jumlah Direksi Penerima Perumahan (Orang)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nominalrumahdir' => [
                    'label' => 'Jumlah Nominal Perumahan Direksi (Rp)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'terimarumahdekom' => [
                    'label' => 'Jumlah Komisaris Penerima Perumahan (Orang)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nominalrumahdekom' => [
                    'label' => 'Jumlah Nominal Perumahan Komisaris (Rp)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Perumahan Bagi Direksi dan Dewan Komisaris',
                    'paketkebijakandirdekom' => $this->paketkebijakandirdekomModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('paketkebijakandirdekom/index', $data);
                echo view('templates/v_footer');
            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'terimarumahdir' => $this->request->getPost('terimarumahdir'),
                    'nominalrumahdir' => $this->request->getPost('nominalrumahdir'),
                    'terimarumahdekom' => $this->request->getPost('terimarumahdekom'),
                    'nominalrumahdekom' => $this->request->getPost('nominalrumahdekom')
                ];

                // Insert data
                $this->paketkebijakandirdekomModel->checkIncrement();
                $success = $this->paketkebijakandirdekomModel->ubahrumah($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil diubahkan ');
                    return redirect()->to(base_url('paketkebijakandirdekom'));
                }
            }
        } else {
            return redirect()->to(base_url('paketkebijakandirdekom'));
        }
    }

    public function ubahtransport()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
        }

        if (isset($_POST['ubahtransport'])) {
            $val = $this->validate([
                'terimatransportdir' => [
                    'label' => 'Jumlah Direksi Penerima Transportasi (Orang)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nominaltransportdir' => [
                    'label' => 'Jumlah Nominal Transportasi Direksi (Rp)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'terimatransportdekom' => [
                    'label' => 'Jumlah Komisaris Penerima Transportasi (Orang)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nominaltransportdekom' => [
                    'label' => 'Jumlah Nominal Transportasi Komisaris (Rp)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Transportasi Bagi Direksi dan Dewan Komisaris',
                    'paketkebijakandirdekom' => $this->paketkebijakandirdekomModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('paketkebijakandirdekom/index', $data);
                echo view('templates/v_footer');
            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'terimatransportdir' => $this->request->getPost('terimatransportdir'),
                    'nominaltransportdir' => $this->request->getPost('nominaltransportdir'),
                    'terimatransportdekom' => $this->request->getPost('terimatransportdekom'),
                    'nominaltransportdekom' => $this->request->getPost('nominaltransportdekom')
                ];

                // Insert data
                $this->paketkebijakandirdekomModel->checkIncrement();
                $success = $this->paketkebijakandirdekomModel->ubahtransport($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil diubahkan ');
                    return redirect()->to(base_url('paketkebijakandirdekom'));
                }
            }
        } else {
            return redirect()->to(base_url('paketkebijakandirdekom'));
        }
    }

    public function ubahasuransi()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
        }

        if (isset($_POST['ubahasuransi'])) {
            $val = $this->validate([
                'terimaasuransidir' => [
                    'label' => 'Jumlah Direksi Penerima Asuransi Kesehatan (Orang)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nominalasuransidir' => [
                    'label' => 'Jumlah Nominal Asuransi Kesehatan Direksi (Rp)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'terimaasuransidekom' => [
                    'label' => 'Jumlah Komisaris Penerima Asuransi Kesehatan (Orang)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nominalasuransidekom' => [
                    'label' => 'Jumlah Nominal Asuransi Kesehatan Komisaris (Rp)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Asuransi Kesehatan Bagi Direksi dan Dewan Komisaris',
                    'paketkebijakandirdekom' => $this->paketkebijakandirdekomModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('paketkebijakandirdekom/index', $data);
                echo view('templates/v_footer');
            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'terimaasuransidir' => $this->request->getPost('terimaasuransidir'),
                    'nominalasuransidir' => $this->request->getPost('nominalasuransidir'),
                    'terimaasuransidekom' => $this->request->getPost('terimaasuransidekom'),
                    'nominalasuransidekom' => $this->request->getPost('nominalasuransidekom')
                ];

                // Insert data
                $this->paketkebijakandirdekomModel->checkIncrement();
                $success = $this->paketkebijakandirdekomModel->ubahasuransi($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil diubahkan ');
                    return redirect()->to(base_url('paketkebijakandirdekom'));
                }
            }
        } else {
            return redirect()->to(base_url('paketkebijakandirdekom'));
        }
    }

    public function ubahfasilitas()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
        }

        if (isset($_POST['ubahfasilitas'])) {
            $val = $this->validate([
                'terimafasilitasdir' => [
                    'label' => 'Jumlah Direksi Penerima Fasilitas Lain-Lainnya (Orang)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nominalfasilitasdir' => [
                    'label' => 'Jumlah Nominal Fasilitas Lain-Lainnya Direksi (Rp)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'terimafasilitasdekom' => [
                    'label' => 'Jumlah Komisaris Penerima Fasilitas Lain-Lainnya (Orang)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nominalfasilitasdekom' => [
                    'label' => 'Jumlah Nominal Fasilitas Lain-Lainnya Komisaris (Rp)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Fasilitas Lain-Lainnya Bagi Direksi dan Dewan Komisaris',
                    'paketkebijakandirdekom' => $this->paketkebijakandirdekomModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('paketkebijakandirdekom/index', $data);
                echo view('templates/v_footer');
            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'terimafasilitasdir' => $this->request->getPost('terimafasilitasdir'),
                    'nominalfasilitasdir' => $this->request->getPost('nominalfasilitasdir'),
                    'terimafasilitasdekom' => $this->request->getPost('terimafasilitasdekom'),
                    'nominalfasilitasdekom' => $this->request->getPost('nominalfasilitasdekom')
                ];

                // Insert data
                $this->paketkebijakandirdekomModel->checkIncrement();
                $success = $this->paketkebijakandirdekomModel->ubahfasilitas($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil diubahkan ');
                    return redirect()->to(base_url('paketkebijakandirdekom'));
                }
            }
        } else {
            return redirect()->to(base_url('paketkebijakandirdekom'));
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
                    'paketkebijakandirdekom' => $this->paketkebijakandirdekomModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('paketkebijakandirdekom/index', $data);
                echo view('templates/v_footer');

            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'keterangan' => $this->request->getPost('keterangan')
                ];

                // Update data
                $success = $this->paketkebijakandirdekomModel->ubah($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Tindak Lanjut Direksi berhasil diubah ');
                    return redirect()->to(base_url('paketkebijakandirdekom'));
                }
            }
        } else {
            return redirect()->to(base_url('paketkebijakandirdekom'));
        }
    }

    public function hapus($id)
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()->to($redirectURL);
        }

        // Memanggil fungsi hapus pada paketkebijakandirdekomModel dan menyimpan hasilnya dalam variabel $success
        $this->paketkebijakandirdekomModel->hapus($id);
        session()->setFlashdata('message', 'Data berhasil dihapus');

        return redirect()->to(base_url('paketkebijakandirdekom'));

    }

    public function approve($idpaketkebijakandirdekom)
    {
        if (!is_numeric($idpaketkebijakandirdekom) || $idpaketkebijakandirdekom <= 0) {
            session()->setFlashdata('err', 'ID Hubungan Keuangan Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR tidak valid.');
            return redirect()->back();
        }

        $paketkebijakandirdekom = $this->paketkebijakandirdekomModel->find($idpaketkebijakandirdekom);
        if (!$paketkebijakandirdekom) {
            session()->setFlashdata('err', 'Data Hubungan Keuangan Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idpaketkebijakandirdekom,
            'is_approved' => 1,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->paketkebijakandirdekomModel->save($dataUpdate)) {
            session()->setFlashdata('message', 'Hubungan Keuangan Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR berhasil disetujui.');
            return redirect()->back();
        } else {
            session()->setFlashdata('err', 'Terjadi kesalahan saat melakukan approval.');
            return redirect()->back();
        }
    }

    public function unapprove($idpaketkebijakandirdekom)
    {
        if (!is_numeric($idpaketkebijakandirdekom) || $idpaketkebijakandirdekom <= 0) {
            session()->setFlashdata('err', 'ID Hubungan Keuangan Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR tidak valid.');
            return redirect()->back();
        }

        $paketkebijakandirdekom = $this->paketkebijakandirdekomModel->find($idpaketkebijakandirdekom);
        if (!$paketkebijakandirdekom) {
            session()->setFlashdata('err', 'Data Hubungan Keuangan Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idpaketkebijakandirdekom,
            'is_approved' => 2,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->paketkebijakandirdekomModel->save($dataUpdate)) {
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

        $this->paketkebijakandirdekomModel->builder()->update($dataUpdate);

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

        $this->paketkebijakandirdekomModel->builder()->update($dataUpdate);

        session()->setFlashdata('err', 'Semua approval Hubungan Keuangan Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR dibatalkan.');
        return redirect()->back();
    }

    public function excel()
    {
        $data = [
            'paketkebijakandirdekom' => $this->paketkebijakandirdekomModel->getAllData()
        ];

        echo view('paketkebijakandirdekom/excel', $data);

    }



    public function exporttxtpaketkebijakandirdekom()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $data_paketkebijakandirdekom = $this->paketkebijakandirdekomModel->getAllData();
        $data_infobpr = $this->infobprModel->getAllData();

        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'];
            $kodejenis = $infobpr['kodejenis'];
        }

        $output = "";
        $output .= "H01|" . $kodejenis . "|" . $sandibpr . "|2025-05-31|LTBPRK|E0500|0|\n";

        // Initialize totals
        $totalNominalDireksiAll = 0;
        $totalNominalDekomAll = 0;
        $totalAdditionalDireksiAll = 0;
        $totalAdditionalDekomAll = 0;

        foreach ($data_paketkebijakandirdekom as $row) {
            $hasDetail = false;
            $detailRows = [];

            // Gaji
            if (!empty($row['penerimagajidir']) || !empty($row['nominalgajidir']) || !empty($row['penerimagajidekom']) || !empty($row['nominalgajidekom'])) {
                $detailRows[] = "D01|" . "611" . "|" . $row['penerimagajidir'] . "|" . $row['nominalgajidir'] . "|" . $row['penerimagajidekom'] . "|" . $row['nominalgajidekom'];
                $hasDetail = true;
            }

            // Tunjangan
            if (!empty($row['terimatunjangandir']) || !empty($row['nominaltunjangandir']) || !empty($row['terimatunjangandekom']) || !empty($row['nominaltunjangandekom'])) {
                $detailRows[] = "D01|" . "612" . "|" . $row['terimatunjangandir'] . "|" . $row['nominaltunjangandir'] . "|" . $row['terimatunjangandekom'] . "|" . $row['nominaltunjangandekom'];
                $hasDetail = true;
            }

            // Tantiem
            if (!empty($row['terimatantiemdir']) || !empty($row['nominaltantiemdir']) || !empty($row['terimatantiemdekom']) || !empty($row['nominaltantiemdekom'])) {
                $detailRows[] = "D01|" . "613" . "|" . $row['terimatantiemdir'] . "|" . $row['nominaltantiemdir'] . "|" . $row['terimatantiemdekom'] . "|" . $row['nominaltantiemdekom'];
                $hasDetail = true;
            }

            // SHM
            if (!empty($row['terimashmdir']) || !empty($row['nominalshmdir']) || !empty($row['terimashmdekom']) || !empty($row['nominalshmdekom'])) {
                $detailRows[] = "D01|" . "614" . "|" . $row['terimashmdir'] . "|" . $row['nominalshmdir'] . "|" . $row['terimashmdekom'] . "|" . $row['nominalshmdekom'];
                $hasDetail = true;
            }

            // Remunerasi Lain
            if (!empty($row['terimaremunlaindir']) || !empty($row['nominalremunlaindir']) || !empty($row['terimaremunlaindekom']) || !empty($row['nominalremunlaindekom'])) {
                $detailRows[] = "D01|" . "615" . "|" . $row['terimaremunlaindir'] . "|" . $row['nominalremunlaindir'] . "|" . $row['terimaremunlaindekom'] . "|" . $row['nominalremunlaindekom'];
                $hasDetail = true;
            }

            // Perumahan
            if (!empty($row['terimarumahdir']) || !empty($row['nominalrumahdir']) || !empty($row['terimarumahdekom']) || !empty($row['nominalrumahdekom'])) {
                $detailRows[] = "D01|" . "621" . "|" . $row['terimarumahdir'] . "|" . $row['nominalrumahdir'] . "|" . $row['terimarumahdekom'] . "|" . $row['nominalrumahdekom'];
                $hasDetail = true;
            }

            // Transportasi
            if (!empty($row['terimatransportdir']) || !empty($row['nominaltransportdir']) || !empty($row['terimatransportdekom']) || !empty($row['nominaltransportdekom'])) {
                $detailRows[] = "D01|" . "622" . "|" . $row['terimatransportdir'] . "|" . $row['nominaltransportdir'] . "|" . $row['terimatransportdekom'] . "|" . $row['nominaltransportdekom'];
                $hasDetail = true;
            }

            // Asuransi
            if (!empty($row['terimaasuransidir']) || !empty($row['nominalasuransidir']) || !empty($row['terimaasuransidekom']) || !empty($row['nominalasuransidekom'])) {
                $detailRows[] = "D01|" . "623" . "|" . $row['terimaasuransidir'] . "|" . $row['nominalasuransidir'] . "|" . $row['terimaasuransidekom'] . "|" . $row['nominalasuransidekom'];
                $hasDetail = true;
            }

            // Fasilitas Lain
            if (!empty($row['terimafasilitasdir']) || !empty($row['nominalfasilitasdir']) || !empty($row['terimafasilitasdekom']) || !empty($row['nominalfasilitasdekom'])) {
                $detailRows[] = "D01|" . "624" . "|" . $row['terimafasilitasdir'] . "|" . $row['nominalfasilitasdir'] . "|" . $row['terimafasilitasdekom'] . "|" . $row['nominalfasilitasdekom'];
                $hasDetail = true;
            }

            // Cetak baris detail
            foreach ($detailRows as $detailRow) {
                $output .= $detailRow . "\n";
            }

            // Calculate totals for this row (611-615)
            $totalNominalDireksi = (float) ($row['nominalgajidir'] ?? 0) +
                (float) ($row['nominaltunjangandir'] ?? 0) +
                (float) ($row['nominaltantiemdir'] ?? 0) +
                (float) ($row['nominalshmdir'] ?? 0) +
                (float) ($row['nominalremunlaindir'] ?? 0);

            $totalNominalDekom = (float) ($row['nominalgajidekom'] ?? 0) +
                (float) ($row['nominaltunjangandekom'] ?? 0) +
                (float) ($row['nominaltantiemdekom'] ?? 0) +
                (float) ($row['nominalshmdekom'] ?? 0) +
                (float) ($row['nominalremunlaindekom'] ?? 0);

            // Calculate additional benefits totals (621-624)
            $totalAdditionalDireksi = (float) ($row['nominalrumahdir'] ?? 0) +
                (float) ($row['nominaltransportdir'] ?? 0) +
                (float) ($row['nominalasuransidir'] ?? 0) +
                (float) ($row['nominalfasilitasdir'] ?? 0);

            $totalAdditionalDekom = (float) ($row['nominalrumahdekom'] ?? 0) +
                (float) ($row['nominaltransportdekom'] ?? 0) +
                (float) ($row['nominalasuransidekom'] ?? 0) +
                (float) ($row['nominalfasilitasdekom'] ?? 0);

            // Add to grand totals
            $totalNominalDireksiAll += $totalNominalDireksi;
            $totalNominalDekomAll += $totalNominalDekom;
            $totalAdditionalDireksiAll += $totalAdditionalDireksi;
            $totalAdditionalDekomAll += $totalAdditionalDekom;
        }

        // Add the total lines at the end if there were any details
        if (!empty($data_paketkebijakandirdekom)) {
            // Total for 611-615 (placed after all records, not per record)
            $output .= "D01|" . "616" . "|" . "|" . $totalNominalDireksiAll . "|" . "|" . $totalNominalDekomAll . "\n";

            // Total for 621-624
            $output .= "D01|" . "625" . "|" . "|" . $totalAdditionalDireksiAll . "|" . "|" . $totalAdditionalDekomAll . "\n";

            // Grand Total (616 + 625)
            $grandTotalDireksi = $totalNominalDireksiAll + $totalAdditionalDireksiAll;
            $grandTotalDekom = $totalNominalDekomAll + $totalAdditionalDekomAll;
            $output .= "D01|" . "630" . "|" . "|" . $grandTotalDireksi . "|" . "|" . $grandTotalDekom . "\n";

            $footer_row = end($data_paketkebijakandirdekom);
            $output .= "F01|" . "Footer 1" . "|" . $footer_row['keterangan'];
        } else {
            $output .= "F01|" . "Footer 1" . "|";
        }

        $this->response->setHeader('Content-Type', 'text/plain');
        $this->response->setHeader('Content-Disposition', 'attachment; filename="LTBPRK-E0500-R-A-20250531-"' . $sandibpr . '-01.txt"');

        echo $output;
    }


}


