<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_transaksikepentingan;
use App\Models\M_infobpr;
use App\Models\M_user;
use Myth\Auth\Config\Services as AuthServices;

class transaksikepentingan extends Controller
{
    protected $model;
    protected $transaksikepentinganModel;
    protected $usermodel;
    protected $session;
    protected $auth;
    protected $infobprModel;
    public function __construct()
    {
        $this->model = new M_transaksikepentingan();
        $this->transaksikepentinganModel = new M_transaksikepentingan();
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
        $user = $this->userModel->find($userId);

        $fullname = $user['fullname'] ?? 'Unknown';

        $transaksikepentinganData = $this->transaksikepentinganModel->getAllData();

        $authorize = AuthServices::authorization();
        $userInGroupPE = $authorize->inGroup('pe', $this->auth->id());
        $userInGroupAdmin = $authorize->inGroup('admin', $this->auth->id());
        $userInGroupDekom = $authorize->inGroup('dekom', $this->auth->id());
        $userInGroupDireksi = $authorize->inGroup('direksi', $this->auth->id());

        $data = [
            'judul' => '17. Transaksi yang Mengandung Benturan Kepentingan',
            'transaksikepentingan' => $transaksikepentinganData,
            // 'transaksikepentingan' => $this->model->getAllData(),
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
        echo view('transaksikepentingan/index', $data);
        echo view('templates/v_footer');
    }

    public function tambahtransaksikepentingan()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
        }

        if (isset($_POST['tambahtransaksikepentingan'])) {
            $val = $this->validate([
                'namapihakbenturan' => [
                    'label' => 'Nama Pihak yang Memiliki Benturan Kepentingan',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'jbtbenturan' => [
                    'label' => 'Jabatan Pihak yang Memiliki Benturan Kepentingan',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nikbenturan' => [
                    'label' => 'NIK Pihak yang Memiliki Benturan Kepentingan',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'pengambilkeputusan' => [
                    'label' => 'Nama Pengambil Keputusan',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'jbtpengambilkeputusan' => [
                    'label' => 'Jabatan Pengambil Keputusan',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nikpengambilkeputusan' => [
                    'label' => 'NIK Pengambil Keputusan',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'jenistransaksi' => [
                    'label' => 'Jenis Transaksi',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nilaitransaksi' => [
                    'label' => 'Nilai Transaksi (Jutaan Rupiah)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
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
                    'judul' => 'Transaksi yang Mengandung Benturan Kepentingan',
                    'transaksikepentingan' => $this->model->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('transaksikepentingan/index', $data);
                echo view('templates/v_footer');
            } else {
                $data = [
                    'namapihakbenturan' => $this->request->getPost('namapihakbenturan'),
                    'jbtbenturan' => $this->request->getPost('jbtbenturan'),
                    'nikbenturan' => $this->request->getPost('nikbenturan'),
                    'pengambilkeputusan' => $this->request->getPost('pengambilkeputusan'),
                    'jbtpengambilkeputusan' => $this->request->getPost('jbtpengambilkeputusan'),
                    'nikpengambilkeputusan' => $this->request->getPost('nikpengambilkeputusan'),
                    'jenistransaksi' => $this->request->getPost('jenistransaksi'),
                    'nilaitransaksi' => $this->request->getPost('nilaitransaksi'),
                    'keterangan' => $this->request->getPost('keterangan')

                ];

                // Insert data
                $this->model->checkIncrement();
                $success = $this->model->tambahtransaksikepentingan($data);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil ditambahkan ');
                    return redirect()->to(base_url('transaksikepentingan'));
                }
            }
        } else {
            return redirect()->to(base_url('transaksikepentingan'));
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
                'namapihakbenturan' => [
                    'label' => 'Nama Pihak yang Memiliki Benturan Kepentingan',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'jbtbenturan' => [
                    'label' => 'Jabatan Pihak yang Memiliki Benturan Kepentingan',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nikbenturan' => [
                    'label' => 'NIK Pihak yang Memiliki Benturan Kepentingan',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'pengambilkeputusan' => [
                    'label' => 'Nama Pengambil Keputusan',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'jbtpengambilkeputusan' => [
                    'label' => 'Jabatan Pengambil Keputusan',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nikpengambilkeputusan' => [
                    'label' => 'NIK Pengambil Keputusan',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'jenistransaksi' => [
                    'label' => 'Jenis Transaksi',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nilaitransaksi' => [
                    'label' => 'Nilai Transaksi (Jutaan Rupiah)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
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
                    'judul' => 'Transaksi yang Mengandung Benturan Kepentingan',
                    'transaksikepentingan' => $this->model->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('transaksikepentingan/index', $data);
                echo view('templates/v_footer');

            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'namapihakbenturan' => $this->request->getPost('namapihakbenturan'),
                    'jbtbenturan' => $this->request->getPost('jbtbenturan'),
                    'nikbenturan' => $this->request->getPost('nikbenturan'),
                    'pengambilkeputusan' => $this->request->getPost('pengambilkeputusan'),
                    'jbtpengambilkeputusan' => $this->request->getPost('jbtpengambilkeputusan'),
                    'nikpengambilkeputusan' => $this->request->getPost('nikpengambilkeputusan'),
                    'jenistransaksi' => $this->request->getPost('jenistransaksi'),
                    'nilaitransaksi' => $this->request->getPost('nilaitransaksi'),
                    'keterangan' => $this->request->getPost('keterangan')
                ];

                // Update data
                $success = $this->model->ubah($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Transaksi yang Mengandung Benturan Kepentingan berhasil diubah ');
                    return redirect()->to(base_url('transaksikepentingan'));
                }
            }
        } else {
            return redirect()->to(base_url('transaksikepentingan'));
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

        return redirect()->to(base_url('transaksikepentingan'));

    }

    public function approve($idtransaksikepentingan)
    {
        if (!is_numeric($idtransaksikepentingan) || $idtransaksikepentingan <= 0) {
            session()->setFlashdata('err', 'ID Data Transaksi yang Mengandung Benturan Kepentingan tidak valid.');
            return redirect()->back();
        }

        $transaksikepentingan = $this->transaksikepentinganModel->find($idtransaksikepentingan);
        if (!$transaksikepentingan) {
            session()->setFlashdata('err', 'Data Data Transaksi yang Mengandung Benturan Kepentingan dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idtransaksikepentingan,
            'is_approved' => 1,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->transaksikepentinganModel->save($dataUpdate)) {
            session()->setFlashdata('message', 'Data Transaksi yang Mengandung Benturan Kepentingan berhasil disetujui.');
            return redirect()->back();
        } else {
            session()->setFlashdata('err', 'Terjadi kesalahan saat melakukan approval.');
            return redirect()->back();
        }
    }

    public function unapprove($idtransaksikepentingan)
    {
        if (!is_numeric($idtransaksikepentingan) || $idtransaksikepentingan <= 0) {
            session()->setFlashdata('err', 'ID Data Transaksi yang Mengandung Benturan Kepentingan tidak valid.');
            return redirect()->back();
        }

        $transaksikepentingan = $this->transaksikepentinganModel->find($idtransaksikepentingan);
        if (!$transaksikepentingan) {
            session()->setFlashdata('err', 'Data Data Transaksi yang Mengandung Benturan Kepentingan dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idtransaksikepentingan,
            'is_approved' => 2,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->transaksikepentinganModel->save($dataUpdate)) {
            session()->setFlashdata('err', 'Approval Data Transaksi yang Mengandung Benturan Kepentingan dibatalkan.');
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

        $this->transaksikepentinganModel->builder()->update($dataUpdate);

        session()->setFlashdata('message', 'Semua Data Transaksi yang Mengandung Benturan Kepentingan berhasil disetujui.');
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

        $this->transaksikepentinganModel->builder()->update($dataUpdate);

        session()->setFlashdata('err', 'Semua approval data Transaksi yang Mengandung Benturan Kepentingan dibatalkan.');
        return redirect()->back();
    }

    public function excel()
    {
        $data = [
            'transaksikepentingan' => $this->model->getAllData()
        ];

        echo view('transaksikepentingan/excel', $data);

    }

    public function exporttxttransaksikepentingan()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }
        $data_transaksikepentingan = $this->model->getAllData();
        $data_infobpr = $this->infobprModel->getAllData();

        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'];
            $kodejenis = $infobpr['kodejenis'];
        }

        $output = "";
        $output .= "H01|" . $kodejenis . "|" . $sandibpr . "|2025-05-31|LTBPRK|E01000|0|\n";
        foreach ($data_transaksikepentingan as $row) {
            $output .= "D01|" . "110100000000" . "|" . $row['namapihakbenturan'] . "|" . $row['jbtbenturan'] . "|" . $row['nikbenturan'] . "|" . $row['pengambilkeputusan'] . "|" . $row['jbtpengambilkeputusan'] . "|" . $row['nikpengambilkeputusan'] . "|" . $row['jenistransaksi'] . "|" . $row['nilaitransaksi'] . "|" . $row['keterangan'] . "\n";
        }

        if (!empty($data_transaksikepentingan)) {
            $footer_row = end($data_transaksikepentingan);
            $output .= "F01|" . "Footer 1" . "|" . $footer_row['tindakbenturan'];
        } else {
            $output .= "F01|" . "Footer 1" . "|";
        }

        $response = service('response');

        $filename = "LTBPRK-E01000-R-A-20250531-" . $sandibpr . "-01.txt";

        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->setBody($output);

        return $response;
    }

}


