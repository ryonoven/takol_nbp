<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_kehadirandekom;
use App\Models\M_tgjwbdekom;
use App\Models\M_infobpr;
use App\Models\M_user;
use Myth\Auth\Config\Services as AuthServices;


class kehadirandekom extends Controller
{
    protected $kehadirandekomModel;
    protected $tgjwbdekomModel;
    protected $infobprModel;
    protected $userModel;
    protected $session;
    protected $auth;

    public function __construct()
    {
        $this->kehadirandekomModel = new M_kehadirandekom();
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
        $user = $this->userModel->find($userId);

        $fullname = $user['fullname'] ?? 'Unknown';

        $kehadirandekomData = $this->kehadirandekomModel->getAllData();

        $authorize = AuthServices::authorization();
        $userInGroupPE = $authorize->inGroup('pe', $this->auth->id());
        $userInGroupAdmin = $authorize->inGroup('admin', $this->auth->id());
        $userInGroupDekom = $authorize->inGroup('dekom', $this->auth->id());
        $userInGroupDireksi = $authorize->inGroup('direksi', $this->auth->id());

        $data = [
            'judul' => '14. Kehadiran Anggota Dewan Komisaris',
            //'kehadirandekom' => $this->kehadirandekomModel->getAllData(),
            'tgjwbdekom' => $this->tgjwbdekomModel->getAllData(),
            'infobpr' => $this->infobprModel->getAllData(),
            'kehadirandekom' => $kehadirandekomData,
            'userInGroupPE' => $userInGroupPE,
            'userInGroupAdmin' => $userInGroupAdmin,
            'userInGroupDekom' => $userInGroupDekom,
            'userInGroupDireksi' => $userInGroupDireksi,
            'fullname' => $fullname,
        ];

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('kehadirandekom/index', $data);
        echo view('templates/v_footer');
    }

    public function tambahdekom()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        if (isset($_POST['tambahdekom'])) {
            $val = $this->validate([
                'dekom' => [
                    'label' => 'Nama Anggota Dewan Komisaris',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hadirfisik' => [
                    'label' => 'Frekuensi Kehadiran (Fisik):',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hadironline' => [
                    'label' => 'Frekuensi Kehadiran (Telekonferensi):',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'persen' => [
                    'label' => 'Tingkat Kehadiran (dalam %):',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Kehadiran Anggota Dewan Komisaris',
                    'kehadirandekom' => $this->kehadirandekomModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('kehadirandekom/index', $data);
                echo view('templates/v_footer');
            } else {
                $data = [
                    'dekom' => $this->request->getPost('dekom'),
                    'hadirfisik' => $this->request->getPost('hadirfisik'),
                    'hadironline' => $this->request->getPost('hadironline'),
                    'persen' => $this->request->getPost('persen')
                ];

                // Insert data using the correct model
                $this->kehadirandekomModel->checkIncrement();
                $success = $this->kehadirandekomModel->tambahdekom($data);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil ditambahkan');
                    return redirect()->to(base_url('kehadirandekom'));
                }
            }
        } else {
            return redirect()->to(base_url('kehadirandekom'));
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
        $this->kehadirandekomModel->hapus($id);
        session()->setFlashdata('message', 'Data berhasil dihapus');

        return redirect()->to(base_url('kehadirandekom'));
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
                    'label' => 'Nama Anggota Dewan Komisaris:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hadirfisik' => [
                    'label' => 'Frekuensi Kehadiran (Fisik):',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hadironline' => [
                    'label' => 'Frekuensi Kehadiran (Telekonferensi):',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'persen' => [
                    'label' => 'Tingkat Kehadiran (dalam %):',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Kehadiran Anggota Dewan Komisaris',
                    'kehadirandekom' => $this->kehadirandekomModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('kehadirandekom/index', $data); // Memperbaiki pemanggilan view
                echo view('templates/v_footer');
            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'dekom' => $this->request->getPost('dekom'),
                    'hadirfisik' => $this->request->getPost('hadirfisik'),
                    'hadironline' => $this->request->getPost('hadironline'),
                    'persen' => $this->request->getPost('persen')
                ];

                // Update data menggunakan model yang benar
                $success = $this->kehadirandekomModel->ubahdekom($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil diubah ');
                    return redirect()->to(base_url('kehadirandekom'));
                }
            }
        } else {
            return redirect()->to(base_url('kehadirandekom'));
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
                    'kehadirandekom' => $this->kehadirandekomModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('kehadirandekom/index', $data);
                echo view('templates/v_footer');

            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'keterangan' => $this->request->getPost('keterangan')
                ];

                // Update data
                $success = $this->kehadirandekomModel->ubahketerangan($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil diubah ');
                    return redirect()->to(base_url('kehadirandekom'));
                }
            }
        } else {
            return redirect()->to(base_url('kehadirandekom'));
        }
    }
    
    public function approve($idkehadirandekom)
    {
        if (!is_numeric($idkehadirandekom) || $idkehadirandekom <= 0) {
            session()->setFlashdata('err', 'ID Kehadiran Dewan Komisaris dalam pelaksanaan rapat selama satu tahun tidak valid.');
            return redirect()->back();
        }

        $kehadirandekom = $this->kehadirandekomModel->find($idkehadirandekom);
        if (!$kehadirandekom) {
            session()->setFlashdata('err', 'Data Kehadiran Dewan Komisaris dalam pelaksanaan rapat selama satu tahun dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idkehadirandekom,
            'is_approved' => 1,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->kehadirandekomModel->save($dataUpdate)) {
            session()->setFlashdata('message', 'Kehadiran Dewan Komisaris dalam pelaksanaan rapat selama satu tahun berhasil disetujui.');
            return redirect()->back();
        } else {
            session()->setFlashdata('err', 'Terjadi kesalahan saat melakukan approval.');
            return redirect()->back();
        }
    }

    public function unapprove($idkehadirandekom)
    {
        if (!is_numeric($idkehadirandekom) || $idkehadirandekom <= 0) {
            session()->setFlashdata('err', 'ID Kehadiran Dewan Komisaris dalam pelaksanaan rapat selama satu tahun tidak valid.');
            return redirect()->back();
        }

        $kehadirandekom = $this->kehadirandekomModel->find($idkehadirandekom);
        if (!$kehadirandekom) {
            session()->setFlashdata('err', 'Data Kehadiran Dewan Komisaris dalam pelaksanaan rapat selama satu tahun dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idkehadirandekom,
            'is_approved' => 2,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->kehadirandekomModel->save($dataUpdate)) {
            session()->setFlashdata('err', 'Approval Kehadiran Dewan Komisaris dalam pelaksanaan rapat selama satu tahun dibatalkan.');
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

        $this->kehadirandekomModel->builder()->update($dataUpdate);

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

        $this->kehadirandekomModel->builder()->update($dataUpdate);

        session()->setFlashdata('err', 'Semua approval Kehadiran Dewan Komisaris dalam pelaksanaan rapat selama satu tahun dibatalkan.');
        return redirect()->back();
    }

    public function excel()
    {
        $data = [
            'kehadirandekom' => $this->kehadirandekomModel->getAllData()
        ];

        echo view('kehadirandekom/excel', $data);
    }

    public function exporttxtkehadirandekom()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $data_kehadirandekom = $this->kehadirandekomModel->getAllData();

        $data_tgjwbdekom = $this->tgjwbdekomModel->getAllData();

        $data_infobpr = $this->infobprModel->getAllData();

        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'];
            $kodejenis = $infobpr['kodejenis'];
        }

        $output = "";
        $output .= "H01|" . $kodejenis . "|" . $sandibpr . "|2025-05-31|LTBPRK|E0702|0|\n";

        foreach ($data_kehadirandekom as $row_shm) {
            $nik = '';

            if (!empty($row_shm['dekom'])) {
                foreach ($data_tgjwbdekom as $row_tgj_dekom) {
                    if ($row_shm['dekom'] == $row_tgj_dekom['dekom']) {
                        $nik = $row_tgj_dekom['nik'];
                        $dekom = $row_tgj_dekom['dekom'];
                        break;
                    }
                }
                $output .= "D01|" . "082010000000" . "|" . $nik . "|" . $dekom . "|" . $row_shm['hadirfisik'] . "|" . $row_shm['hadironline'] . "|" . $row_shm['persen'] . "\n";
            }
        }
        $keterangan_id_1 = '';
        foreach ($data_kehadirandekom as $row_shm) {
            if ($row_shm['id'] == 1) {
                $keterangan_id_1 = trim($row_shm['keterangan']);
                break;
            }
        }

        $output .= "F01|" . "Footer 1" . " " . $keterangan_id_1;

        $response = service('response');

        $filename = "LTBPRK-E0702-R-A-20250531-" . $sandibpr . "-01.txt";

        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->setBody($output);

        return $response;
    }

}