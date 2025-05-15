<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_tgjwbkomite;
use App\Models\M_infobpr;
use App\Models\M_user;
use Myth\Auth\Config\Services as AuthServices;

class tgjwbkomite extends Controller
{
    protected $model;
    protected $infobprModel;
    protected $usermodel;
    protected $auth;
    protected $tgjwbkomiteModel;
    protected $session;
    public function __construct()
    {
        $this->model = new M_tgjwbkomite();
        $this->tgjwbkomiteModel = new M_tgjwbkomite();
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

        $tgjwbkomiteData = $this->tgjwbkomiteModel->getAllData();

        $authorize = AuthServices::authorization();
        $userInGroupPE = $authorize->inGroup('pe', $this->auth->id());
        $userInGroupAdmin = $authorize->inGroup('admin', $this->auth->id());
        $userInGroupDekom = $authorize->inGroup('dekom', $this->auth->id());
        $userInGroupDireksi = $authorize->inGroup('direksi', $this->auth->id());

        $data = [
            'judul' => '4. Tugas, Tanggung Jawab, Program Kerja, dan Realisasi Program Kerja Komite',
            'tgjwbkomite' => $tgjwbkomiteData,
            // 'tgjwbkomite' => $this->model->getAllData(),
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
        echo view('tgjwbkomite/index', $data);
        echo view('templates/v_footer');
        $userId = service('authentication')->id();
        $data['userInGroupPE'] = service('authorization')->inGroup('pe', $userId);
        $data['userInGroupAdmin'] = service('authorization')->inGroup('admin', $userId);
        $data['userInGroupDekom'] = service('authorization')->inGroup('dekom', $userId);
        $data['userInGroupDireksi'] = service('authorization')->inGroup('direksi', $userId);
    }

    public function tambahtgjwbkomite()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
        }

        if (isset($_POST['tambahtgjwbkomite'])) {
            $val = $this->validate([
                'komite' => [
                    'label' => 'Komite',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'tugastgjwbkomite' => [
                    'label' => 'Penjelasan Tugas dan Tanggung Jawab:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'jumlahrapat' => [
                    'label' => 'Jumlah Rapat:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'prokerkomite' => [
                    'label' => 'Program Kerja Komite:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hasilprokerkomite' => [
                    'label' => 'Realisasi Program Kerja Komite:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]

            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Tugas, Tanggung Jawab, Program Kerja, dan Realisasi Program Kerja Komite',
                    'tgjwbkomite' => $this->model->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('tgjwbkomite/index', $data);
                echo view('templates/v_footer');
            } else {
                $data = [
                    'komite' => $this->request->getPost('komite'),
                    'tugastgjwbkomite' => $this->request->getPost('tugastgjwbkomite'),
                    'jumlahrapat' => $this->request->getPost('jumlahrapat'),
                    'prokerkomite' => $this->request->getPost('prokerkomite'),
                    'hasilprokerkomite' => $this->request->getPost('hasilprokerkomite'),
                    'tindakkomite' => $this->request->getPost('tindakkomite')
                ];

                // Insert data
                $this->model->checkIncrement();
                $success = $this->model->tambahtgjwbkomite($data);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil ditambahkan ');
                    return redirect()->to(base_url('tgjwbkomite'));
                }
            }
        } else {
            return redirect()->to(base_url('tgjwbkomite'));
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
                'komite' => [
                    'label' => 'Komite',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'tugastgjwbkomite' => [
                    'label' => 'Penjelasan Tugas dan Tanggung Jawab:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'jumlahrapat' => [
                    'label' => 'Jumlah Rapat:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'prokerkomite' => [
                    'label' => 'Program Kerja Komite:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hasilprokerkomite' => [
                    'label' => 'Realisasi Program Kerja Komite:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Tugas, Tanggung Jawab, Program Kerja, dan Realisasi Program Kerja Komite',
                    'tgjwbkomite' => $this->model->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('tgjwbkomite/index', $data);
                echo view('templates/v_footer');

            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'komite' => $this->request->getPost('komite'),
                    'tugastgjwbkomite' => $this->request->getPost('tugastgjwbkomite'),
                    'jumlahrapat' => $this->request->getPost('jumlahrapat'),
                    'prokerkomite' => $this->request->getPost('prokerkomite'),
                    'hasilprokerkomite' => $this->request->getPost('hasilprokerkomite'),
                    'tindakkomite' => $this->request->getPost('tindakkomite')
                ];

                // Update data
                $success = $this->model->ubah($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil diubah ');
                    return redirect()->to(base_url('tgjwbkomite'));
                }
            }
        } else {
            return redirect()->to(base_url('tgjwbkomite'));
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
                'tindakkomite' => [
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
                    'judul' => 'Tugas, Tanggung Jawab, Program Kerja, dan Realisasi Program Kerja Komite',
                    'tgjwbkomite' => $this->model->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('tgjwbkomite/index', $data);
                echo view('templates/v_footer');

            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'tindakkomite' => $this->request->getPost('tindakkomite')
                ];

                // Update data
                $success = $this->model->ubah($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil diubah ');
                    return redirect()->to(base_url('tgjwbkomite'));
                }
            }
        } else {
            return redirect()->to(base_url('tgjwbkomite'));
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

        return redirect()->to(base_url('tgjwbkomite'));

    }

    public function approve($idTgjwbkomite)
    {
        if (!is_numeric($idTgjwbkomite) || $idTgjwbkomite <= 0) {
            session()->setFlashdata('err', 'ID Pelaksanaan Tugas, Tanggung Jawab, Program Kerja, dan Realisasi Program Kerja Komite tidak valid.');
            return redirect()->back();
        }

        $tgjwbdir = $this->tgjwbkomiteModel->find($idTgjwbkomite);
        if (!$tgjwbdir) {
            session()->setFlashdata('err', 'Data Pelaksanaan Tugas, Tanggung Jawab, Program Kerja, dan Realisasi Program Kerja Komite dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idTgjwbkomite,
            'is_approved' => 1,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->tgjwbkomiteModel->save($dataUpdate)) {
            session()->setFlashdata('message', 'Pelaksanaan Tugas, Tanggung Jawab, Program Kerja, dan Realisasi Program Kerja Komite berhasil disetujui.');
            return redirect()->back();
        } else {
            session()->setFlashdata('err', 'Terjadi kesalahan saat melakukan approval.');
            return redirect()->back();
        }
    }

    public function unapprove($idTgjwbkomite)
    {
        if (!is_numeric($idTgjwbkomite) || $idTgjwbkomite <= 0) {
            session()->setFlashdata('err', 'ID Pelaksanaan Tugas, Tanggung Jawab, Program Kerja, dan Realisasi Program Kerja Komite tidak valid.');
            return redirect()->back();
        }

        $tgjwbdir = $this->tgjwbkomiteModel->find($idTgjwbkomite);
        if (!$tgjwbdir) {
            session()->setFlashdata('err', 'Data Pelaksanaan Tugas, Tanggung Jawab, Program Kerja, dan Realisasi Program Kerja Komite dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idTgjwbkomite,
            'is_approved' => 2,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->tgjwbkomiteModel->save($dataUpdate)) {
            session()->setFlashdata('err', 'Approval Pelaksanaan Tugas, Tanggung Jawab, Program Kerja, dan Realisasi Program Kerja Komite dibatalkan.');
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

        $this->tgjwbkomiteModel->builder()->update($dataUpdate);

        session()->setFlashdata('message', 'Semua Pelaksanaan Tugas, Tanggung Jawab, Program Kerja, dan Realisasi Program Kerja Komite berhasil disetujui.');
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

        $this->tgjwbkomiteModel->builder()->update($dataUpdate);

        session()->setFlashdata('err', 'Semua approval Pelaksanaan Tugas, Tanggung Jawab, Program Kerja, dan Realisasi Program Kerja Komite dibatalkan.');
        return redirect()->back();
    }

    public function excel()
    {
        $data = [
            'tgjwbkomite' => $this->model->getAllData()
        ];

        echo view('tgjwbkomite/excel', $data);

    }

    public function exporttxttgjwbkomite()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }
        $data_tgjwbkomite = $this->model->getAllData();
        $data_infobpr = $this->infobprModel->getAllData();

        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'];
            $kodejenis = $infobpr['kodejenis'];
        }

        $output = "";
        $output .= "H01|" . $kodejenis . "|" . $sandibpr . "|2025-05-31|LTBPRK|E0203|0|\n";
        foreach ($data_tgjwbkomite as $row) {
            $output .= "D01|" . "013301000000" . "|" . $row['komite'] . "|" . $row['tugastgjwbkomite'] . "|" . $row['prokerkomite'] . "|" . $row['hasilprokerkomite'] . "|" . $row['jumlahrapat'] . "\n";
        }

        if (!empty($data_tgjwbkomite)) {
            $footer_row = end($data_tgjwbkomite);
            $output .= "F01|" . "Footer 1" . " " . $footer_row['tindakkomite'];
        } else {
            $output .= "F01|" . "Footer 1";
        }

        $response = service('response');
        $filename = "LTBPRK-E0203-R-A-20250531-" . $sandibpr . "-01.txt";

        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->setBody($output);

        return $response;
    }

}


