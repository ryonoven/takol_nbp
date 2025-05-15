<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_shmdirdekomlain;
use App\Models\M_tgjwbdir;
use App\Models\M_tgjwbdekom;
use App\Models\M_infobpr;
use App\Models\M_user;
use Myth\Auth\Config\Services as AuthServices;


class shmdirdekomlain extends Controller
{
    protected $shmdirdekomlainModel;
    protected $tgjwbdirModel;
    protected $tgjwbdekomModel;
    protected $infobprModel;
    protected $usermodel;
    protected $session;
    protected $auth;

    public function __construct()
    {
        $this->shmdirdekomlainModel = new M_shmdirdekomlain();
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

        $shmdirdekomlainData = $this->shmdirdekomlainModel->getAllData();

        $authorize = AuthServices::authorization();
        $userInGroupPE = $authorize->inGroup('pe', $this->auth->id());
        $userInGroupAdmin = $authorize->inGroup('admin', $this->auth->id());
        $userInGroupDekom = $authorize->inGroup('dekom', $this->auth->id());
        $userInGroupDireksi = $authorize->inGroup('direksi', $this->auth->id());

        $data = [
            'judul' => '8. Kepemilikan Saham Anggota Direksi dan Dewan Komisaris pada Perusahaan Lain',
            'shmdirdekomlain' => $shmdirdekomlainData,
            //'shmdirdekomlain' => $this->shmdirdekomlainModel->getAllData(),
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
        echo view('shmdirdekomlain/index', $data);
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
                'jenisdir' => [
                    'label' => 'Jenis Bank/Perusahaan Lain',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'perusahaandir' => [
                    'label' => 'Nama Bank/Perusahaan Lain',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'persenshmdirlain' => [
                    'label' => 'Persentase Kepemilikan (%):',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => '8. Kepemilikan Saham Anggota Direksi dan Dewan Komisaris pada Perusahaan Lain',
                    'shmdirdekomlain' => $this->shmdirdekomlainModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('shmdirdekomlain/index', $data);
                echo view('templates/v_footer');
            } else {
                $data = [
                    'direksi' => $this->request->getPost('direksi'),
                    'jenisdir' => $this->request->getPost('jenisdir'),
                    'kodedir' => $this->request->getPost('kodedir'),
                    'perusahaandir' => $this->request->getPost('perusahaandir'),
                    'persenshmdirlain' => $this->request->getPost('persenshmdirlain')
                ];

                // Insert data using the correct model
                $this->shmdirdekomlainModel->checkIncrement();
                $success = $this->shmdirdekomlainModel->tambahsahamdir($data);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil ditambahkan ');
                    return redirect()->to(base_url('shmdirdekomlain'));
                }
            }
        } else {
            return redirect()->to(base_url('shmdirdekomlain'));
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
                'jenisdekom' => [
                    'label' => 'Jenis Bank/Perusahaan Lain',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'perusahaandekom' => [
                    'label' => 'Nama Bank/Perusahaan Lain',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'persenshmdekomlain' => [
                    'label' => 'Persentase Kepemilikan (%):',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => '8. Kepemilikan Saham Anggota Direksi dan Dewan Komisaris pada Perusahaan Lain',
                    'shmdirdekomlain' => $this->shmdirdekomlainModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('shmdirdekomlain/index', $data);
                echo view('templates/v_footer');
            } else {
                $data = [
                    'dekom' => $this->request->getPost('dekom'),
                    'jenisdekom' => $this->request->getPost('jenisdekom'),
                    'kodedekom' => $this->request->getPost('kodedekom'),
                    'perusahaandekom' => $this->request->getPost('perusahaandekom'),
                    'persenshmdekomlain' => $this->request->getPost('persenshmdekomlain')
                ];

                // Insert data using the correct model
                $this->shmdirdekomlainModel->checkIncrement();
                $success = $this->shmdirdekomlainModel->tambahsahamdekom($data);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil ditambahkan');
                    return redirect()->to(base_url('shmdirdekomlain'));
                }
            }
        } else {
            return redirect()->to(base_url('shmdirdekomlain'));
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
        $this->shmdirdekomlainModel->hapus($id);
        session()->setFlashdata('message', 'Data berhasil dihapus');

        return redirect()->to(base_url('shmdirdekomlain'));
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
                'jenisdir' => [
                    'label' => 'Jenis Bank/Perusahaan Lain',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'perusahaandir' => [
                    'label' => 'Nama Bank/Perusahaan Lain',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'persenshmdirlain' => [
                    'label' => 'Persentase Kepemilikan (%):',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => '8. Kepemilikan Saham Anggota Direksi pada Perusahaan Lain',
                    'shmdirdekomlain' => $this->shmdirdekomlainModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('shmdirdekomlain/index', $data); // Memperbaiki pemanggilan view
                echo view('templates/v_footer');
            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'direksi' => $this->request->getPost('direksi'),
                    'jenisdir' => $this->request->getPost('jenisdir'),
                    'kodedir' => $this->request->getPost('kodedir'),
                    'perusahaandir' => $this->request->getPost('perusahaandir'),
                    'persenshmdirlain' => $this->request->getPost('persenshmdirlain')
                ];

                // Update data menggunakan model yang benar
                $success = $this->shmdirdekomlainModel->ubahdir($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil diubah ');
                    return redirect()->to(base_url('shmdirdekomlain'));
                }
            }
        } else {
            return redirect()->to(base_url('shmdirdekomlain'));
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
                'jenisdekom' => [
                    'label' => 'Jenis Bank/Perusahaan Lain',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'perusahaandekom' => [
                    'label' => 'Nama Bank/Perusahaan Lain',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'persenshmdekomlain' => [
                    'label' => 'Persentase Kepemilikan (%):',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => '8. Kepemilikan Saham Anggota Dewan Komisaris pada Perusahaan Lain',
                    'shmdirdekomlain' => $this->shmdirdekomlainModel->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('shmdirdekomlain/index', $data);
                echo view('templates/v_footer');
            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'dekom' => $this->request->getPost('dekom'),
                    'jenisdekom' => $this->request->getPost('jenisdekom'),
                    'kodedekom' => $this->request->getPost('kodedekom'),
                    'perusahaandekom' => $this->request->getPost('perusahaandekom'),
                    'persenshmdekomlain' => $this->request->getPost('persenshmdekomlain')
                ];

                // Update data menggunakan model yang benar
                $success = $this->shmdirdekomlainModel->ubahdekom($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil diubah ');
                    return redirect()->to(base_url('shmdirdekomlain'));
                }
            }
        } else {
            return redirect()->to(base_url('shmdirdekomlain'));
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
                    'label' => 'Kepemilikan Saham Anggota Direksi dan Dewan Komisaris pada Perusahaan Lain',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Kepemilikan Saham Anggota Dewan Komisaris pada Perusahaan Lain',
                    'shmdirdekomlain' => $this->model->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('shmdirdekomlain/index', $data);
                echo view('templates/v_footer');

            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'keterangan' => $this->request->getPost('keterangan')
                ];

                // Update data
                $success = $this->shmdirdekomlainModel->ubahketerangan($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Tindak Lanjut Direksi berhasil diubah ');
                    return redirect()->to(base_url('shmdirdekomlain'));
                }
            }
        } else {
            return redirect()->to(base_url('shmdirdekomlain'));
        }
    }

    public function excel()
    {
        $data = [
            'shmdirdekomlain' => $this->shmdirdekomlainModel->getAllData()
        ];

        echo view('shmdirdekomlain/excel', $data);
    }

    public function approve($idshmdirdekomlain)
    {
        if (!is_numeric($idshmdirdekomlain) || $idshmdirdekomlain <= 0) {
            session()->setFlashdata('err', 'ID Kepemilikan Saham Anggota Direksi dan Dewan Komisaris pada Perusahaan Lain tidak valid.');
            return redirect()->back();
        }

        $shmdirdekomlain = $this->shmdirdekomlainModel->find($idshmdirdekomlain);
        if (!$shmdirdekomlain) {
            session()->setFlashdata('err', 'Data Kepemilikan Saham Anggota Direksi dan Dewan Komisaris pada Perusahaan Lain dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idshmdirdekomlain,
            'is_approved' => 1,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->shmdirdekomlainModel->save($dataUpdate)) {
            session()->setFlashdata('message', 'Kepemilikan Saham Anggota Direksi dan Dewan Komisaris pada Perusahaan Lain berhasil disetujui.');
            return redirect()->back();
        } else {
            session()->setFlashdata('err', 'Terjadi kesalahan saat melakukan approval.');
            return redirect()->back();
        }
    }

    public function unapprove($idshmdirdekomlain)
    {
        if (!is_numeric($idshmdirdekomlain) || $idshmdirdekomlain <= 0) {
            session()->setFlashdata('err', 'ID Kepemilikan Saham Anggota Direksi dan Dewan Komisaris pada Perusahaan Lain tidak valid.');
            return redirect()->back();
        }

        $shmdirdekomlain = $this->shmdirdekomlainModel->find($idshmdirdekomlain);
        if (!$shmdirdekomlain) {
            session()->setFlashdata('err', 'Data Kepemilikan Saham Anggota Direksi dan Dewan Komisaris pada Perusahaan Lain dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idshmdirdekomlain,
            'is_approved' => 2,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->shmdirdekomlainModel->save($dataUpdate)) {
            session()->setFlashdata('err', 'Approval Kepemilikan Saham Anggota Direksi dan Dewan Komisaris pada Perusahaan Lain dibatalkan.');
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

        $this->shmdirdekomlainModel->builder()->update($dataUpdate);

        session()->setFlashdata('message', 'Semua Kepemilikan Saham Anggota Direksi dan Dewan Komisaris pada Perusahaan Lain berhasil disetujui.');
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

        $this->shmdirdekomlainModel->builder()->update($dataUpdate);

        session()->setFlashdata('err', 'Semua approval Kepemilikan Saham Anggota Direksi dan Dewan Komisaris pada Perusahaan Lain dibatalkan.');
        return redirect()->back();
    }

    public function exporttxtshmdirdekomlain()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $data_shmdirdekomlain = $this->shmdirdekomlainModel->getAllData();

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

        usort($data_shmdirdekomlain, function ($a, $b) {
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
        $output .= "H01|" . $kodejenis . "|" . $sandibpr . "|2025-05-31|LTBPRK|E0303|0|\n";

        foreach ($data_shmdirdekomlain as $row_shm) {
            $nik = '';

            if (!empty($row_shm['direksi'])) {
                foreach ($data_tgjwbdir as $row_tgj_dir) {
                    if ($row_shm['direksi'] == $row_tgj_dir['direksi']) {
                        $direksi = $row_tgj_dir['direksi'];
                        $nik = $row_tgj_dir['nik'];
                        break;
                    }
                }
                $output .= "D01|" . "022010000000" . "|" . $nik . "|" . $direksi . "|" . $row_shm['kodedir'] . "|" . $row_shm['perusahaandir'] . "|" . $row_shm['persenshmdirlain'] . "\n";
            } elseif (!empty($row_shm['dekom'])) {
                foreach ($data_tgjwbdekom as $row_tgj_dekom) {
                    if ($row_shm['dekom'] == $row_tgj_dekom['dekom']) {
                        $dekom = $row_tgj_dekom['dekom'];
                        $nik = $row_tgj_dekom['nik'];
                        break;
                    }
                }
                $output .= "D01|" . "022010000000" . "|" . $nik . "|" . $dekom . "|" . $row_shm['kodedekom'] . "|" . $row_shm['perusahaandekom'] . "|" . $row_shm['persenshmdekomlain'] . "\n";
            }
        }
        $keterangan_id_1 = '';
        foreach ($data_shmdirdekomlain as $row_shm) {
            if ($row_shm['id'] == 1) {
                $keterangan_id_1 = trim($row_shm['keterangan']);
                break; // Berhenti setelah menemukan id 1
            }
        }

        $output .= "F01|" . "Footer 1" . " " . $keterangan_id_1;

        $response = service('response');

        $filename = "LTBPRK-E0303-R-A-20250531-" . $sandibpr . "-01.txt";

        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->setBody($output);

        return $response;
    }

}