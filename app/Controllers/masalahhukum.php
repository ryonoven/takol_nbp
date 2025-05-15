<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_masalahhukum;
use App\Models\M_infobpr;
use App\Models\M_user;
use Myth\Auth\Config\Services as AuthServices;

class masalahhukum extends Controller
{
    protected $model;
    protected $masalahhukumModel;
    protected $usermodel;
    protected $session;
    protected $auth;
    protected $infobprModel;
    public function __construct()
    {
        $this->model = new M_masalahhukum();
        $this->masalahhukumModel = new M_masalahhukum();
        $this->infobprModel = new M_infobpr();
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
        $user = $this->userModel->find($userId);

        $fullname = $user['fullname'] ?? 'Unknown';

        $masalahhukumData = $this->masalahhukumModel->getAllData();

        $authorize = AuthServices::authorization();
        $userInGroupPE = $authorize->inGroup('pe', $this->auth->id());
        $userInGroupAdmin = $authorize->inGroup('admin', $this->auth->id());
        $userInGroupDekom = $authorize->inGroup('dekom', $this->auth->id());
        $userInGroupDireksi = $authorize->inGroup('direksi', $this->auth->id());

        $data = [
            'judul' => '16. Permasalahan Hukum yang Dihadapi',
            'masalahhukum' => $masalahhukumData,
            // 'masalahhukum' => $this->model->getAllData(),
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
        echo view('masalahhukum/index', $data);
        echo view('templates/v_footer');
    }

    public function tambahmasalahhukum()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
        }

        if (isset($_POST['tambahmasalahhukum'])) {
            $val = $this->validate([
                'hukumperdataselesai' => [
                    'label' => 'Permasalahan Hukum Perdata yang Telah Selesai (telah mempunyai kekuatan hukum yang tetap)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hukumpidanaselesai' => [
                    'label' => 'Permasalahan Hukum Pidana yang Telah Selesai (telah mempunyai kekuatan hukum yang tetap)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hukumperdataproses' => [
                    'label' => 'Permasalahan Hukum Perdata yang Dalam Proses Penyelesaian',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hukumpidanaproses' => [
                    'label' => 'Permasalahan Hukum Pidana yang Dalam Proses Penyelesaian',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]

            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Permasalahan Hukum yang Dihadapi',
                    'masalahhukum' => $this->model->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('masalahhukum/index', $data);
                echo view('templates/v_footer');
            } else {
                $data = [
                    'hukumperdataselesai' => $this->request->getPost('hukumperdataselesai'),
                    'hukumpidanaselesai' => $this->request->getPost('hukumpidanaselesai'),
                    'hukumperdataproses' => $this->request->getPost('hukumperdataproses'),
                    'hukumpidanaproses' => $this->request->getPost('hukumpidanaproses')
                ];

                // Insert data
                $this->model->checkIncrement();
                $success = $this->model->tambahmasalahhukum($data);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil ditambahkan ');
                    return redirect()->to(base_url('masalahhukum'));
                }
            }
        } else {
            return redirect()->to(base_url('masalahhukum'));
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
                'hukumperdataselesai' => [
                    'label' => 'Permasalahan Hukum Perdata yang Telah Selesai (telah mempunyai kekuatan hukum yang tetap)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hukumpidanaselesai' => [
                    'label' => 'Permasalahan Hukum Pidana yang Telah Selesai (telah mempunyai kekuatan hukum yang tetap)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Permasalahan Hukum yang Dihadapi',
                    'masalahhukum' => $this->model->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('masalahhukum/index', $data);
                echo view('templates/v_footer');

            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'hukumperdataselesai' => $this->request->getPost('hukumperdataselesai'),
                    'hukumpidanaselesai' => $this->request->getPost('hukumpidanaselesai'),
                ];

                // Update data
                $success = $this->model->ubah($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Permasalahan Hukum yang Dihadapi');
                    return redirect()->to(base_url('masalahhukum'));
                }
            }
        } else {
            return redirect()->to(base_url('masalahhukum'));
        }
    }

    public function ubahproses()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
        }

        if (isset($_POST['ubahproses'])) {
            $val = $this->validate([
                'hukumperdataproses' => [
                    'label' => 'Permasalahan Hukum Perdata yang Dalam Proses Penyelesaian',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hukumpidanaproses' => [
                    'label' => 'Permasalahan Hukum Pidana yang Dalam Proses Penyelesaian',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Permasalahan Hukum yang Dihadapi',
                    'masalahhukum' => $this->model->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('masalahhukum/index', $data);
                echo view('templates/v_footer');

            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'hukumperdataproses' => $this->request->getPost('hukumperdataproses'),
                    'hukumpidanaproses' => $this->request->getPost('hukumpidanaproses')
                ];

                // Update data
                $success = $this->model->ubah($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Permasalahan Hukum yang Dihadapi');
                    return redirect()->to(base_url('masalahhukum'));
                }
            }
        } else {
            return redirect()->to(base_url('masalahhukum'));
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
                    'label' => 'keterangan',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Permasalahan Hukum yang Dihadapi',
                    'masalahhukum' => $this->model->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('masalahhukum/index', $data);
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
                    return redirect()->to(base_url('masalahhukum'));
                }
            }
        } else {
            return redirect()->to(base_url('masalahhukum'));
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

        return redirect()->to(base_url('masalahhukum'));

    }

    public function approve($idmasalahhukum)
    {
        if (!is_numeric($idmasalahhukum) || $idmasalahhukum <= 0) {
            session()->setFlashdata('err', 'ID Data Permasalahan Hukum
    yang Dihadapi tidak valid.');
            return redirect()->back();
        }

        $masalahhukum = $this->masalahhukumModel->find($idmasalahhukum);
        if (!$masalahhukum) {
            session()->setFlashdata('err', 'Data Data Permasalahan Hukum yang Dihadapi dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idmasalahhukum,
            'is_approved' => 1,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->masalahhukumModel->save($dataUpdate)) {
            session()->setFlashdata('message', 'Data Permasalahan Hukum yang Dihadapi berhasil disetujui.');
            return redirect()->back();
        } else {
            session()->setFlashdata('err', 'Terjadi kesalahan saat melakukan approval.');
            return redirect()->back();
        }
    }

    public function unapprove($idmasalahhukum)
    {
        if (!is_numeric($idmasalahhukum) || $idmasalahhukum <= 0) {
            session()->setFlashdata('err', 'ID Data Permasalahan
        Hukum yang Dihadapi tidak valid.');
            return redirect()->back();
        }

        $masalahhukum = $this->masalahhukumModel->find($idmasalahhukum);
        if (!$masalahhukum) {
            session()->setFlashdata('err', 'Data Data Permasalahan Hukum yang Dihadapi dengan ID tersebut tidak
        ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idmasalahhukum,
            'is_approved' => 2,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->masalahhukumModel->save($dataUpdate)) {
            session()->setFlashdata('err', 'Approval Data Permasalahan Hukum yang Dihadapi dibatalkan.');
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

        $this->masalahhukumModel->builder()->update($dataUpdate);

        session()->setFlashdata('message', 'Semua Data Permasalahan Hukum yang Dihadapi berhasil disetujui.');
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

        $this->masalahhukumModel->builder()->update($dataUpdate);

        session()->setFlashdata('err', 'Semua approval data Permasalahan Hukum yang Dihadapi dibatalkan.');
        return redirect()->back();
    }

    public function excel()
    {
        $data = [
            'masalahhukum' => $this->model->getAllData()
        ];

        echo view('masalahhukum/excel', $data);

    }

    public function exporttxtmasalahhukum()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }
        $data_masalahhukum = $this->model->getAllData();
        $data_infobpr = $this->infobprModel->getAllData();

        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'];
            $kodejenis = $infobpr['kodejenis'];
        }

        $output = "";
        $output .= "H01|" . $kodejenis . "|" . $sandibpr . "|2025-05-31|LTBPRK|E0900|0|\n";

        foreach ($data_masalahhukum as $row) {
            $output .= "D01|" . "1001" . "|" . $row['hukumperdataselesai'] . "|" . $row['hukumpidanaselesai'] . "\n";
            $output .= "D01|" . "1002" . "|" . $row['hukumperdataproses'] . "|" . $row['hukumpidanaproses'] . "\n";

            // Calculate sums for 1003
            $perdata_total = (int) $row['hukumperdataselesai'] + (int) $row['hukumperdataproses'];
            $pidana_total = (int) $row['hukumpidanaselesai'] + (int) $row['hukumpidanaproses'];

            $output .= "D01|" . "1003" . "|" . $perdata_total . "|" . $pidana_total . "\n";
        }

        if (!empty($data_masalahhukum)) {
            $footer_row = end($data_masalahhukum);
            $output .= "F01|" . "Footer 1" . " " . $footer_row['keterangan'];
        } else {
            $output .= "F01|" . "Footer 1";
        }

        $response = service('response');

        $filename = "LTBPRK-E0900-R-A-20250531-" . $sandibpr . "-01.txt";

        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->setBody($output);

        return $response;
    }
}


