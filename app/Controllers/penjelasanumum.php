<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_penjelasanumum;
use App\Models\M_infobpr;
use App\Models\M_user;
use Myth\Auth\Config\Services as AuthServices;

class penjelasanumum extends Controller
{
    protected $model;
    protected $infobprModel;
    protected $penjelasanModel;
    protected $usermodel;
    protected $auth;
    protected $session;
    public function __construct()
    {
        $this->model = new M_penjelasanumum();
        $this->penjelasanModel = new M_penjelasanumum();
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

        $userId = $this->auth->id();
        $user = $this->userModel->find($userId);

        $fullname = $user['fullname'] ?? 'Unknown';

        $penjelasanData = $this->penjelasanModel->getAllData();
        $infobprData = $this->infobprModel->getAllData();

        $authorize = AuthServices::authorization();
        $userInGroupPE = $authorize->inGroup('pe', $this->auth->id());
        $userInGroupAdmin = $authorize->inGroup('admin', $this->auth->id());
        $userInGroupDekom = $authorize->inGroup('dekom', $this->auth->id());
        $userInGroupDireksi = $authorize->inGroup('direksi', $this->auth->id());

        $data = [
            'judul' => '1. Penjelasan Umum',
            'penjelasanumum' => $penjelasanData,
            'infobpr' => $infobprData,
            //'infobpr' => $this->infobprModel->getAllData(),
            //'penjelasanumum' => $this->model->getAllData(),
            'userInGroupPE' => $userInGroupPE,
            'userInGroupAdmin' => $userInGroupAdmin,
            'userInGroupDekom' => $userInGroupDekom,
            'userInGroupDireksi' => $userInGroupDireksi,
            'fullname' => $fullname,
        ];

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('penjelasanumum/index', $data);
        echo view('templates/v_footer');
        $userId = service('authentication')->id();
        $data['userInGroupPE'] = service('authorization')->inGroup('pe', $userId);
        $data['userInGroupAdmin'] = service('authorization')->inGroup('admin', $userId);
        $data['userInGroupDekom'] = service('authorization')->inGroup('dekom', $userId);
        $data['userInGroupDireksi'] = service('authorization')->inGroup('direksi', $userId);

    }

    public function tambahpenjelas()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
        }

        if (isset($_POST['tambahpenjelas'])) {
            $val = $this->validate([
                'namabpr' => [
                    'label' => 'Nama BPR',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'alamat' => [
                    'label' => 'Alamat BPR',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nomor' => [
                    'label' => 'Nomor Telepon BPR',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'penjelasan' => [
                    'label' => 'Penjelasan Umum',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'peringkatkomposit' => [
                    'label' => 'Peringkat Komposit Hasil Penilaian Sendiri (Self Assessment) Tata Kelola',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'penjelasankomposit' => [
                    'label' => 'Penjelasan Peringkat Komposit Hasil Penilaian Sendiri (Self Assessment) Tata Kelola',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],

            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => '1. Penjelasan Umum',
                    'penjelasanumum' => $this->model->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('penjelasanumum/index', $data);
                echo view('templates/v_footer');
            } else {

                $data = [
                    'namabpr' => $this->request->getPost('namabpr'),
                    'alamat' => $this->request->getPost('alamat'),
                    'nomor' => $this->request->getPost('nomor'),
                    'penjelasan' => $this->request->getPost('penjelasan'),
                    'peringkatkomposit' => $this->request->getPost('peringkatkomposit'),
                    'penjelasankomposit' => $this->request->getPost('penjelasankomposit')
                ];

                // Insert data
                $this->model->checkIncrement();
                $success = $this->model->tambahpenjelas($data);
                if ($success) {
                    session()->setFlashdata('message', 'Data Penjelasan Umum berhasil ditambahkan ');
                    return redirect()->to(base_url('penjelasanumum'));
                }
            }
        } else {
            return redirect()->to(base_url('penjelasanumum'));
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
                'namabpr' => [
                    'label' => 'Nama BPR',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'alamat' => [
                    'label' => 'Alamat BPR',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nomor' => [
                    'label' => 'Nomor Telepon BPR',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'penjelasan' => [
                    'label' => 'Penjelasan Umum',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'peringkatkomposit' => [
                    'label' => 'Peringkat Komposit Hasil Penilaian Sendiri (Self Assessment) Tata Kelola',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'penjelasankomposit' => [
                    'label' => 'Penjelasan Peringkat Komposit Hasil Penilaian Sendiri (Self Assessment) Tata Kelola',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => '1. Penjelasan Umum',
                    'penjelasanumum' => $this->model->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('penjelasanumum/index', $data);
                echo view('templates/v_footer');

            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'namabpr' => $this->request->getPost('namabpr'),
                    'alamat' => $this->request->getPost('alamat'),
                    'nomor' => $this->request->getPost('nomor'),
                    'penjelasan' => $this->request->getPost('penjelasan'),
                    'peringkatkomposit' => $this->request->getPost('peringkatkomposit'),
                    'penjelasankomposit' => $this->request->getPost('penjelasankomposit')
                ];

                // Update data
                $success = $this->model->ubah($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Data Penjelasan Umum berhasil diubah ');
                    return redirect()->to(base_url('penjelasanumum'));
                }
            }
        } else {
            return redirect()->to(base_url('penjelasanumum'));
        }
    }

    public function hapus($id)
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()->to($redirectURL);
        }

        $this->model->hapus($id);
        session()->setFlashdata('message', 'Data berhasil dihapus');

        return redirect()->to(base_url('penjelasanumum'));

    }

    public function excel()
    {
        $data = [
            'penjelasanumum' => $this->model->getAllData()
        ];

        echo view('penjelasanumum/excel', $data);

    }
    private function generateTxtPenjelasanumum()
    {
        $data = $this->model->getAllData();
        $output = "";

        foreach ($data as $row) {
            $output .= "H01|010201|609999|2024-12-31|LTBPRK|E0100|0|\n";
            $output .= "D01|" . $row['alamat'] . "|" . $row['nomor'] . "|" . $row['penjelasan'] . "|" . $row['peringkatkomposit'] . "|" . $row['penjelasankomposit'] . "\n";
        }

        return $output;
    }

    public function exporttxtpenjelasanumum()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $data = $this->model->getAllData();
        $data_infobpr = $this->infobprModel->getAllData();

        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'];
            $kodejenis = $infobpr['kodejenis'];
        }

        $output = "";
        foreach ($data as $row) {
            $output .= "H01|" . $kodejenis . "|" . $sandibpr . "|2025-05-31|LTBPRK|E0100|0|\n";
            $output .= "D01" . "|" . $row['alamat'] . "|" . $row['nomor'] . "|" . $row['penjelasan'] . "|" . $row['peringkatkomposit'] . "|" . $row['penjelasankomposit'] . "\n";
        }

        $response = service('response');

        $filename = "LTBPRK-E0100-R-A-20250531-" . $sandibpr . "-01.txt";

        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->setBody($output);

        return $response;
    }

    public function approve($idpenjelasanumum)
    {
        if (!is_numeric($idpenjelasanumum) || $idpenjelasanumum <= 0) {
            session()->setFlashdata('err', 'ID tidak valid.');
            return redirect()->back();
        }

        $penjelasanumum = $this->penjelasanModel->find($idpenjelasanumum);
        if (!$penjelasanumum) {
            session()->setFlashdata('err', 'Data dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }
        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idpenjelasanumum,
            'is_approved' => 1,  // Approved
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->penjelasanModel->save($dataUpdate)) {
            session()->setFlashdata('message', 'Data berhasil disetujui.');
            return redirect()->back();
        } else {
            session()->setFlashdata('err', 'Terjadi kesalahan saat melakukan approval.');
            return redirect()->back();
        }
    }

    public function unapprove($idpenjelasanumum)
    {
        if (!is_numeric($idpenjelasanumum) || $idpenjelasanumum <= 0) {
            session()->setFlashdata('err', 'ID Data tidak valid.');
            return redirect()->back();
        }

        $penjelasanumum = $this->penjelasanModel->find($idpenjelasanumum);     

        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idpenjelasanumum,
            'is_approved' => 2,  // Unapproved
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->penjelasanModel->save($dataUpdate)) {
            session()->setFlashdata('err', 'Approval data dibatalkan.');
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

        $this->penjelasanModel->builder()->update($dataUpdate);

        session()->setFlashdata('message', 'Semua data berhasil disetujui.');
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
        if ($this->penjelasanModel->builder()->update($dataUpdate)) {
            session()->setFlashdata('err', 'Semua approval data dibatalkan.');
        } else {
            session()->setFlashdata('err', 'Terjadi kesalahan saat membatalkan approval semua data.');
        }

        return redirect()->back();
    }


}


