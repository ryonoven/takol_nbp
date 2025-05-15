<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_rapat;
use App\Models\M_infobpr;
use App\Models\M_user;
use Myth\Auth\Config\Services as AuthServices;

class rapat extends Controller
{
    protected $model;
    protected $rapatModel;
    protected $usermodel;
    protected $session;
    protected $auth;
    protected $infobprModel;
    public function __construct()
    {
        $this->model = new M_rapat();
        $this->infobprModel = new M_infobpr();
        $this->userModel = new M_user();
        $this->rapatModel = new M_rapat();
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

        $rapatData = $this->rapatModel->getAllData();

        $authorize = AuthServices::authorization();
        $userInGroupPE = $authorize->inGroup('pe', $this->auth->id());
        $userInGroupAdmin = $authorize->inGroup('admin', $this->auth->id());
        $userInGroupDekom = $authorize->inGroup('dekom', $this->auth->id());
        $userInGroupDireksi = $authorize->inGroup('direksi', $this->auth->id());

        $data = [
            'judul' => '13. Pelaksanaan Rapat dalam 1 (satu) tahun',
            'rapat' => $rapatData,
            //'rapat' => $this->model->getAllData(),
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
        echo view('rapat/index', $data);
        echo view('templates/v_footer');

    }

    public function tambahrapat()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
        }

        if (isset($_POST['tambahrapat'])) {
            $val = $this->validate([
                'tanggalrapat' => [
                    'label' => 'Tanggal Rapat',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'jumlahpeserta' => [
                    'label' => '2. Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'topikrapat' => [
                    'label' => '2. Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]

            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Pelaksanaan Rapat dalam 1 (satu) tahun',
                    'rapat' => $this->model->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('rapat/index', $data);
                echo view('templates/v_footer');
            } else {
                $data = [
                    'tanggalrapat' => $this->request->getPost('tanggalrapat'),
                    'jumlahpeserta' => $this->request->getPost('jumlahpeserta'),
                    'topikrapat' => $this->request->getPost('topikrapat')
                ];

                // Insert data
                $this->model->checkIncrement();
                $success = $this->model->tambahrapat($data);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil ditambahkan ');
                    return redirect()->to(base_url('rapat'));
                }
            }
        } else {
            return redirect()->to(base_url('rapat'));
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
                'tanggalrapat' => [
                    'label' => 'Tanggal Rapat',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'jumlahpeserta' => [
                    'label' => '2. Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'topikrapat' => [
                    'label' => '2. Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judultindak' => 'Tindak Lanjut Direksi',
                    'rapat' => $this->model->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('rapat/index', $data);
                echo view('templates/v_footer');

            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'tanggalrapat' => $this->request->getPost('tanggalrapat'),
                    'jumlahpeserta' => $this->request->getPost('jumlahpeserta'),
                    'topikrapat' => $this->request->getPost('topikrapat')
                ];

                // Update data
                $success = $this->model->ubah($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil diubah ');
                    return redirect()->to(base_url('rapat'));
                }
            }
        } else {
            return redirect()->to(base_url('rapat'));
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
                    'judul' => 'Pelaksanaan Rapat dalam 1 (satu) tahun',
                    'rapat' => $this->model->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('rapat/index', $data);
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
                    return redirect()->to(base_url('rapat'));
                }
            }
        } else {
            return redirect()->to(base_url('rapat'));
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

        return redirect()->to(base_url('rapat'));

    }

    public function approve($idrapat)
    {
        if (!is_numeric($idrapat) || $idrapat <= 0) {
            session()->setFlashdata('err', 'ID Data Rapat tidak valid.');
            return redirect()->back();
        }

        $rapat = $this->rapatModel->find($idrapat);
        if (!$rapat) {
            session()->setFlashdata('err', 'Data Data Rapat dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idrapat,
            'is_approved' => 1,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->rapatModel->save($dataUpdate)) {
            session()->setFlashdata('message', 'Data Rapat berhasil disetujui.');
            return redirect()->back();
        } else {
            session()->setFlashdata('err', 'Terjadi kesalahan saat melakukan approval.');
            return redirect()->back();
        }
    }

    public function unapprove($idrapat)
    {
        if (!is_numeric($idrapat) || $idrapat <= 0) {
            session()->setFlashdata('err', 'ID Data Rapat tidak valid.');
            return redirect()->back();
        }

        $rapat = $this->rapatModel->find($idrapat);
        if (!$rapat) {
            session()->setFlashdata('err', 'Data Data Rapat dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idrapat,
            'is_approved' => 2,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->rapatModel->save($dataUpdate)) {
            session()->setFlashdata('err', 'Approval Data Rapat dibatalkan.');
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

        $this->rapatModel->builder()->update($dataUpdate);

        session()->setFlashdata('message', 'Semua Data Rapat berhasil disetujui.');
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

        $this->rapatModel->builder()->update($dataUpdate);

        session()->setFlashdata('err', 'Semua approval data Rapat dibatalkan.');
        return redirect()->back();
    }

    public function excel()
    {
        $data = [
            'rapat' => $this->model->getAllData()
        ];

        echo view('rapat/excel', $data);

    }

    public function exporttxtrapat()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }
        $data_rapat = $this->model->getAllData();
        $data_infobpr = $this->infobprModel->getAllData();

        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'];
            $kodejenis = $infobpr['kodejenis'];
        }

        $output = "";
        $output .= "H01|" . $kodejenis . "|" . $sandibpr . "|2025-05-31|LTBPRK|E0701|0|\n";
        foreach ($data_rapat as $row) {
            $output .= "D01|" . "081010000000" . "|" . $row['tanggalrapat'] . "|" . $row['jumlahpeserta'] . "|" . $row['topikrapat'] . "\n";
        }

        if (!empty($data_rapat)) {
            $footer_row = end($data_rapat);
            $output .= "F01|" . "Footer 1" . "|" . $footer_row['keterangan'];
        } else {
            $output .= "F01|" . "Footer 1" . "|";
        }

        $response = service('response');

        $filename = "LTBPRK-E0701-R-A-20250531-" . $sandibpr . "-01.txt";

        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->setBody($output);

        return $response;
    }

}


