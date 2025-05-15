<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_tgjwbdekom;
use App\Models\M_infobpr;
use App\Models\M_user;
use Myth\Auth\Config\Services as AuthServices;

class tgjwbdekom extends Controller
{
    protected $model;
    protected $infobprModel;
    protected $usermodel;
    protected $auth;
    protected $tgjwbdekomModel;
    protected $session;

    public function __construct()
    {
        $this->model = new M_tgjwbdekom();
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

        $tgjwbdekomData = $this->tgjwbdekomModel->getAllData();

        $authorize = AuthServices::authorization();
        $userInGroupPE = $authorize->inGroup('pe', $this->auth->id());
        $userInGroupAdmin = $authorize->inGroup('admin', $this->auth->id());
        $userInGroupDekom = $authorize->inGroup('dekom', $this->auth->id());
        $userInGroupDireksi = $authorize->inGroup('direksi', $this->auth->id());

        $data = [
            'judul' => '3. Pelaksanaan Tugas dan Tanggung Jawab Anggota Dewan Komisaris',
            'tgjwbdekom' => $tgjwbdekomData,
            //'tgjwbdekom' => $this->model->getAllData(),
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
        echo view('tgjwbdekom/index', $data);
        echo view('templates/v_footer');
        $userId = service('authentication')->id();
        $data['userInGroupPE'] = service('authorization')->inGroup('pe', $userId);
        $data['userInGroupAdmin'] = service('authorization')->inGroup('admin', $userId);
        $data['userInGroupDekom'] = service('authorization')->inGroup('dekom', $userId);
        $data['userInGroupDireksi'] = service('authorization')->inGroup('direksi', $userId);
    }

    public function tambahtgjwbdekom()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
        }

        if (isset($_POST['tambahtgjwbdekom'])) {
            $val = $this->validate([
                'dekom' => [
                    'label' => 'Nama Direksi',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nik' => [
                    'label' => 'NIK',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'tugastgjwbdekom' => [
                    'label' => 'Penjelasan Tugas dan Tanggung Jawab:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]

            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Penjelasan Tugas dan tanggung jawab anggota Dewan Komisaris',
                    'tgjwbdekom' => $this->model->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('tgjwbdekom/index', $data);
                echo view('templates/v_footer');
            } else {
                $data = [
                    'dekom' => $this->request->getPost('dekom'),
                    'nik' => $this->request->getPost('nik'),
                    'tugastgjwbdekom' => $this->request->getPost('tugastgjwbdekom'),
                    'tindakdekom' => $this->request->getPost('tindakdekom')
                ];

                // Insert data
                $this->model->checkIncrement();
                $success = $this->model->tambahtgjwbdekom($data);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil ditambahkan ');
                    return redirect()->to(base_url('tgjwbdekom'));
                }
            }
        } else {
            return redirect()->to(base_url('tgjwbdekom'));
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
                'dekom' => [
                    'label' => 'Nama Direksi',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nik' => [
                    'label' => 'NIK',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'tugastgjwbdekom' => [
                    'label' => 'Pelaksanaan Tugas dan Tanggung Jawab Anggota Dewan Komisaris
',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Tindak Lanjut Direksi',
                    'tgjwbdekom' => $this->model->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('tgjwbdekom/index', $data);
                echo view('templates/v_footer');

            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'dekom' => $this->request->getPost('dekom'),
                    'nik' => $this->request->getPost('nik'),
                    'tugastgjwbdekom' => $this->request->getPost('tugastgjwbdekom'),
                    'tindakdekom' => $this->request->getPost('tindakdekom'),
                ];

                // Update data
                $success = $this->model->ubah($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Pelaksanaan Tugas dan Tanggung Jawab Anggota Dewan Komisaris
 berhasil diubah ');
                    return redirect()->to(base_url('tgjwbdekom'));
                }
            }
        } else {
            return redirect()->to(base_url('tgjwbdekom'));
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
                'tindakdekom' => [
                    'label' => 'Tindak Lanjut Rekomendasi Dewan Komisaris:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Pelaksanaan Tugas dan Tanggung Jawab Anggota Dewan Komisaris
',
                    'tgjwbdekom' => $this->model->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('tgjwbdekom/index', $data);
                echo view('templates/v_footer');

            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'tindakdekom' => $this->request->getPost('tindakdekom')
                ];

                // Update data
                $success = $this->model->ubah($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Tindak Lanjut Rekomendasi Dewan Komisaris berhasil diubah ');
                    return redirect()->to(base_url('tgjwbdekom'));
                }
            }
        } else {
            return redirect()->to(base_url('tgjwbdekom'));
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

        return redirect()->to(base_url('tgjwbdekom'));

    }

    public function approve($idTgjwbdekom)
    {
        if (!is_numeric($idTgjwbdekom) || $idTgjwbdekom <= 0) {
            session()->setFlashdata('err', 'ID Pelaksanaan Tugas dan Tanggung Jawab Anggota Dewan Komisaris
 tidak valid.');
            return redirect()->back();
        }

        $tgjwbdir = $this->tgjwbdekomModel->find($idTgjwbdekom);
        if (!$tgjwbdir) {
            session()->setFlashdata('err', 'Data Pelaksanaan Tugas dan Tanggung Jawab Anggota Dewan Komisaris
 dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idTgjwbdekom,
            'is_approved' => 1,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->tgjwbdekomModel->save($dataUpdate)) {
            session()->setFlashdata('message', 'Pelaksanaan Tugas dan Tanggung Jawab Anggota Dewan Komisaris
 berhasil disetujui.');
            return redirect()->back();
        } else {
            session()->setFlashdata('err', 'Terjadi kesalahan saat melakukan approval.');
            return redirect()->back();
        }
    }

    public function unapprove($idTgjwbdekom)
    {
        if (!is_numeric($idTgjwbdekom) || $idTgjwbdekom <= 0) {
            session()->setFlashdata('err', 'ID Pelaksanaan Tugas dan Tanggung Jawab Anggota Dewan Komisaris
 tidak valid.');
            return redirect()->back();
        }

        $tgjwbdir = $this->tgjwbdekomModel->find($idTgjwbdekom);
        if (!$tgjwbdir) {
            session()->setFlashdata('err', 'Data Pelaksanaan Tugas dan Tanggung Jawab Anggota Dewan Komisaris
 dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idTgjwbdekom,
            'is_approved' => 2,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->tgjwbdekomModel->save($dataUpdate)) {
            session()->setFlashdata('err', 'Approval Pelaksanaan Tugas dan Tanggung Jawab Anggota Dewan Komisaris
 dibatalkan.');
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

        $this->tgjwbdekomModel->builder()->update($dataUpdate);

        session()->setFlashdata('message', 'Semua Pelaksanaan Tugas dan Tanggung Jawab Anggota Dewan Komisaris
 berhasil disetujui.');
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

        $this->tgjwbdekomModel->builder()->update($dataUpdate);

        session()->setFlashdata('err', 'Semua approval Pelaksanaan Tugas dan Tanggung Jawab Anggota Dewan Komisaris
 dibatalkan.');
        return redirect()->back();
    }

    public function excel()
    {
        $data = [
            'tgjwbdekom' => $this->model->getAllData()
        ];

        echo view('tgjwbdekom/excel', $data);

    }

    public function exporttxttgjwbdekom()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }
        $data_tgjwbdekom = $this->model->getAllData();
        $data_infobpr = $this->infobprModel->getAllData();

        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'];
            $kodejenis = $infobpr['kodejenis'];
        }

        $output = "";
        $output .= "H01|" . $kodejenis . "|" . $sandibpr . "|2025-05-31|LTBPRK|E0202|0|\n";
        foreach ($data_tgjwbdekom as $row) {
            $output .= "D01|" . "011000000000" . "|" . $row['nik'] . "|" . $row['tugastgjwbdekom'] . "\n";
        }

        if (!empty($data_tgjwbdekom)) {
            $footer_row = end($data_tgjwbdekom);
            $output .= "F01|" . "Footer 1" . "|" . $footer_row['tindakdekom'];
        } else {
            $output .= "F01|" . "Footer 1" . "|";
        }



        $response = service('response');

        $filename = "LTBPRK-E0202-R-A-2020531-" . $sandibpr . "-01.txt";

        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response->setBody($output);


        echo $output;
    }

}


