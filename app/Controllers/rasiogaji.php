<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_rasiogaji;
use App\Models\M_infobpr;
use App\Models\M_user;
use Myth\Auth\Config\Services as AuthServices;

class rasiogaji extends Controller
{
    protected $model;
    protected $infobprModel;
    protected $rasiogajiModel;
    protected $usermodel;
    protected $session;
    protected $auth;
    public function __construct()
    {
        $this->rasiogajiModel = new M_rasiogaji();
        $this->model = new M_rasiogaji();
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

        $rasiogajiData = $this->rasiogajiModel->getAllData();

        $authorize = AuthServices::authorization();
        $userInGroupPE = $authorize->inGroup('pe', $this->auth->id());
        $userInGroupAdmin = $authorize->inGroup('admin', $this->auth->id());
        $userInGroupDekom = $authorize->inGroup('dekom', $this->auth->id());
        $userInGroupDireksi = $authorize->inGroup('direksi', $this->auth->id());

        $data = [
            'judul' => '12. Rasio Gaji Tertinggi dan Gaji Terendah',
            'rasiogaji' => $rasiogajiData,
            // 'rasiogaji' => $this->model->getAllData(),
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
        echo view('rasiogaji/index', $data);
        echo view('templates/v_footer');
    }


    public function tambahrasio()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
        }

        if (isset($_POST['tambahrasio'])) {
            $val = $this->validate([
                'pegawaitinggi' => [
                    'label' => 'Gaji Pegawai Tertinggi',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'pegawairendah' => [
                    'label' => 'Gaji Pegawai Terendah:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'dirtinggi' => [
                    'label' => 'Gaji Direksi Tertinggi:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'dirrendah' => [
                    'label' => 'Gaji Direksi Terendah:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'dekomtinggi' => [
                    'label' => 'Gaji Dewan Komisaris Tertinggi:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'dekomrendah' => [
                    'label' => 'Gaji Dewan Komisaris Terendah:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => '12. Rasio Gaji Tertinggi dan Gaji Terendah',
                    'rasiogaji' => $this->model->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('rasiogaji/index', $data);
                echo view('templates/v_footer');
            } else {

                $data = [
                    'pegawaitinggi' => $this->request->getPost('pegawaitinggi'),
                    'pegawairendah' => $this->request->getPost('pegawairendah'),
                    'dirtinggi' => $this->request->getPost('dirtinggi'),
                    'dirrendah' => $this->request->getPost('dirrendah'),
                    'dekomtinggi' => $this->request->getPost('dekomrendah'),
                    'dekomrendah' => $this->request->getPost('dekomrendah')
                ];

                // Insert data
                $this->model->checkIncrement();
                $success = $this->model->tambahrasio($data);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil ditambahkan ');
                    return redirect()->to(base_url('rasiogaji'));
                }
            }
        } else {
            return redirect()->to(base_url('rasiogaji'));
        }
    }

    public function ubahrasio()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
        }

        if (isset($_POST['ubahrasio'])) {
            $val = $this->validate([
                'pegawaitinggi' => [
                    'label' => 'Gaji Pegawai Tertinggi',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'pegawairendah' => [
                    'label' => 'Gaji Pegawai Terendah:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'dirtinggi' => [
                    'label' => 'Gaji Direksi Tertinggi:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'dirrendah' => [
                    'label' => 'Gaji Direksi Terendah:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'dekomtinggi' => [
                    'label' => 'Gaji Dewan Komisaris Tertinggi:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'dekomrendah' => [
                    'label' => 'Gaji Dewan Komisaris Terendah:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => '12. Rasio Gaji Tertinggi dan Gaji Terendah',
                    'rasiogaji' => $this->model->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('rasiogaji/index', $data);
                echo view('templates/v_footer');

            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'pegawaitinggi' => $this->request->getPost('pegawaitinggi'),
                    'pegawairendah' => $this->request->getPost('pegawairendah'),
                    'dirtinggi' => $this->request->getPost('dirtinggi'),
                    'dirrendah' => $this->request->getPost('dirrendah'),
                    'dekomtinggi' => $this->request->getPost('dekomtinggi'),
                    'dekomrendah' => $this->request->getPost('dekomrendah')
                ];

                // Update data
                $success = $this->model->ubahrasio($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil diubah ');
                    return redirect()->to(base_url('rasiogaji'));
                }
            }
        } else {
            return redirect()->to(base_url('rasiogaji'));
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

        // Redirect pengguna ke halaman "/bisnis"
        return redirect()->to(base_url('rasiogaji'));

    }

    public function excel()
    {
        $data = [
            'rasiogaji' => $this->model->getAllData()
        ];

        echo view('rasiogaji/excel', $data);

    }

    public function approve($idrasiogaji)
    {
        if (!is_numeric($idrasiogaji) || $idrasiogaji <= 0) {
            session()->setFlashdata('err', 'ID Rasio Gaji tidak valid.');
            return redirect()->back();
        }

        $rasiogaji = $this->rasiogajiModel->find($idrasiogaji);
        if (!$rasiogaji) {
            session()->setFlashdata('err', 'Data Rasio Gaji dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idrasiogaji,
            'is_approved' => 1,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->rasiogajiModel->save($dataUpdate)) {
            session()->setFlashdata('message', 'Rasio Gaji berhasil disetujui.');
            return redirect()->back();
        } else {
            session()->setFlashdata('err', 'Terjadi kesalahan saat melakukan approval.');
            return redirect()->back();
        }
    }

    public function unapprove($idrasiogaji)
    {
        if (!is_numeric($idrasiogaji) || $idrasiogaji <= 0) {
            session()->setFlashdata('err', 'ID Rasio Gaji tidak valid.');
            return redirect()->back();
        }

        $rasiogaji = $this->rasiogajiModel->find($idrasiogaji);
        if (!$rasiogaji) {
            session()->setFlashdata('err', 'Data Rasio Gaji dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idrasiogaji,
            'is_approved' => 2,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->rasiogajiModel->save($dataUpdate)) {
            session()->setFlashdata('err', 'Approval Rasio Gaji dibatalkan.');
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

        $this->rasiogajiModel->builder()->update($dataUpdate);

        session()->setFlashdata('message', 'Semua Rasio Gaji berhasil disetujui.');
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

        $this->rasiogajiModel->builder()->update($dataUpdate);

        session()->setFlashdata('err', 'Semua approval Rasio Gaji dibatalkan.');
        return redirect()->back();
    }

    public function exporttxtrasiogaji()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $data_rasiogaji = $this->model->getAllData();
        $data_infobpr = $this->infobprModel->getAllData();

        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'];
            $kodejenis = $infobpr['kodejenis'];
        }

        $output = "";
        $output .= "H01|" . $kodejenis . "|" . $sandibpr . "|2025-05-31|LTBPRK|E0600|0|\n";

        foreach ($data_rasiogaji as $row) {
            // Hitung rasio pegawai
            $gajiTertinggi = $row['pegawaitinggi'] ?? 0;
            $gajiTerendah = $row['pegawairendah'] ?? 0;
            $rasioPegawai = ($gajiTerendah != 0) ? ($gajiTertinggi / $gajiTerendah) : 0;
            $rasioPegawaiFormatted = number_format($rasioPegawai, 2, '.', '');

            // Hitung rasio direksi
            $dirTertinggi = $row['dirtinggi'] ?? 0;
            $dirTerendah = $row['dirrendah'] ?? 0;
            $rasioDireksi = ($dirTerendah != 0) ? ($dirTertinggi / $dirTerendah) : 0;
            $rasioDireksiFormatted = number_format($rasioDireksi, 2, '.', '');

            $dekomTertinggi = $row['dekomtinggi'] ?? 0;
            $dekomTerendah = $row['dekomrendah'] ?? 0;
            $rasioDekom = ($dekomTerendah != 0) ? ($dekomTertinggi / $dekomTerendah) : 0;
            $rasioDekomFormatted = number_format($rasioDekom, 2, '.', '');

            $dirTertinggi = $row['dirtinggi'] ?? 0;
            $dekomTertinggi = $row['dekomtinggi'] ?? 0;
            $rasioDirDekom = ($dirTertinggi != 0) ? ($dirTertinggi / $dekomTertinggi) : 0;
            $rasioDirDekomFormatted = number_format($rasioDirDekom, 2, '.', '');

            $dirTertinggi = $row['dirtinggi'] ?? 0;
            $gajiTertinggi = $row['pegawaitinggi'] ?? 0;
            $rasiopegawaiDireksi = ($dirTertinggi != 0) ? ($dirTertinggi / $gajiTertinggi) : 0;
            $rasiopegawaiDireksiFormatted = number_format($rasiopegawaiDireksi, 2, '.', '');

            // Output kedua rasio
            $output .= "D01|" . "070100000000" . "|" . $rasioPegawaiFormatted . "\n";
            $output .= "D01|" . "070100000000" . "|" . $rasioDireksiFormatted . "\n";
            $output .= "D01|" . "070100000000" . "|" . $rasioDekomFormatted . "\n";
            $output .= "D01|" . "070100000000" . "|" . $rasioDirDekomFormatted . "\n";
            $output .= "D01|" . "070100000000" . "|" . $rasiopegawaiDireksiFormatted . "\n";
        }

        if (!empty($data_rasiogaji)) {
            $footer_row = end($data_rasiogaji);
            $output .= "F01|" . "Footer 1" . "|" . $footer_row['keterangan'];
        } else {
            $output .= "F01|" . "Footer 1" . "|";
        }

        $response = service('response');

        $filename = "LTBPRK-E0600-R-A-20250531LTBPRK-E0204-R-A-20250531-" . $sandibpr . "-01.txt";

        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->setBody($output);

        return $response;
    }

}


