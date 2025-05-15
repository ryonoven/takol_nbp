<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_strukturkomite;
use App\Models\M_infobpr;
use App\Models\M_user;
use Myth\Auth\Config\Services as AuthServices;

class strukturkomite extends Controller
{
    protected $model;
    protected $infobprModel;
    protected $usermodel;
    protected $auth;
    protected $strukturkomiteModel;
    protected $session;
    public function __construct()
    {
        $this->model = new M_strukturkomite();
        $this->strukturkomiteModel = new M_strukturkomite();
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

        $strukturkomiteData = $this->strukturkomiteModel->getAllData();

        $authorize = AuthServices::authorization();
        $userInGroupPE = $authorize->inGroup('pe', $this->auth->id());
        $userInGroupAdmin = $authorize->inGroup('admin', $this->auth->id());
        $userInGroupDekom = $authorize->inGroup('dekom', $this->auth->id());
        $userInGroupDireksi = $authorize->inGroup('direksi', $this->auth->id());

        $data = [
            'judul' => '5. Struktur, Keanggotaan, Keahlian, dan Independensi Anggota Komite',
            'strukturkomite' => $strukturkomiteData,
            //'strukturkomite' => $this->model->getAllData(),
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
        echo view('strukturkomite/index', $data);
        echo view('templates/v_footer');


    }

    public function tambahstrukturkomite()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()
                ->to($redirectURL);
        }

        if (isset($_POST['tambahstrukturkomite'])) {
            $val = $this->validate([
                'anggotakomite' => [
                    'label' => 'Nama Anggota Komite',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nikkomite' => [
                    'label' => 'NIK :',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'keahlian' => [
                    'label' => 'Keahlian :',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'jbtaudit' => [
                    'label' => 'Jabatan Dalam Komite Audit :',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'jbtpantauresiko' => [
                    'label' => 'Jabatan Dalam Komite Pemantau Risiko :',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'jbtremunerasi' => [
                    'label' => 'Jabatan Dalam Komite Remunerasi dan Nominasi :',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'jbtmanrisk' => [
                    'label' => 'Jabatan Dalam Komite Manajemen Risiko :',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'jbtlain' => [
                    'label' => 'Jabatan Dalam Komite Lainnya :',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'independen' => [
                    'label' => 'Merupakan Pihak Independen? :',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],


            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Penjelasan Tugas dan tanggung jawab anggota Dewan Komisaris',
                    'strukturkomite' => $this->model->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('strukturkomite/index', $data);
                echo view('templates/v_footer');
            } else {
                $data = [
                    'anggotakomite' => $this->request->getPost('anggotakomite'),
                    'nikkomite' => $this->request->getPost('nikkomite'),
                    'keahlian' => $this->request->getPost('keahlian'),
                    'jbtaudit' => $this->request->getPost('jbtaudit'),
                    'jbtpantauresiko' => $this->request->getPost('jbtpantauresiko'),
                    'jbtremunerasi' => $this->request->getPost('jbtremunerasi'),
                    'jbtmanrisk' => $this->request->getPost('jbtmanrisk'),
                    'jbtlain' => $this->request->getPost('jbtlain'),
                    'independen' => $this->request->getPost('independen'),
                    'tindakstrukturkomite' => $this->request->getPost('tindakstrukturkomite')
                ];

                // Insert data
                $this->model->checkIncrement();
                $success = $this->model->tambahstrukturkomite($data);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil ditambahkan ');
                    return redirect()->to(base_url('strukturkomite'));
                }
            }
        } else {
            return redirect()->to(base_url('strukturkomite'));
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
                'anggotakomite' => [
                    'label' => 'Nama Anggota Komite',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'nikkomite' => [
                    'label' => 'NIK :',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'keahlian' => [
                    'label' => 'Keahlian :',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'jbtaudit' => [
                    'label' => 'Jabatan Dalam Komite Audit :',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'jbtpantauresiko' => [
                    'label' => 'Jabatan Dalam Komite Pemantau Risiko :',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'jbtremunerasi' => [
                    'label' => 'Jabatan Dalam Komite Remunerasi dan Nominasi :',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'jbtmanrisk' => [
                    'label' => 'Jabatan Dalam Komite Manajemen Risiko :',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'jbtlain' => [
                    'label' => 'Jabatan Dalam Komite Lainnya :',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'independen' => [
                    'label' => 'Merupakan Pihak Independen? :',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Tindak Lanjut Rekomendasi Struktur, Keanggotaan, Keahlian, dan Independensi Anggota Komite',
                    'strukturkomite' => $this->model->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('strukturkomite/index', $data);
                echo view('templates/v_footer');

            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'anggotakomite' => $this->request->getPost('anggotakomite'),
                    'nikkomite' => $this->request->getPost('nikkomite'),
                    'keahlian' => $this->request->getPost('keahlian'),
                    'jbtaudit' => $this->request->getPost('jbtaudit'),
                    'jbtpantauresiko' => $this->request->getPost('jbtpantauresiko'),
                    'jbtremunerasi' => $this->request->getPost('jbtremunerasi'),
                    'jbtmanrisk' => $this->request->getPost('jbtmanrisk'),
                    'jbtlain' => $this->request->getPost('jbtlain'),
                    'independen' => $this->request->getPost('independen'),
                    'tindakstrukturkomite' => $this->request->getPost('tindakstrukturkomite')
                ];

                // Update data
                $success = $this->model->ubah($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil diubah ');
                    return redirect()->to(base_url('strukturkomite'));
                }
            }
        } else {
            return redirect()->to(base_url('strukturkomite'));
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
                'tindakstrukturkomite' => [
                    'label' => 'Tindak Lanjut Rekomendasi Struktur, Keanggotaan, Keahlian, dan Independensi Anggota Komite:',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ]
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Struktur, Keanggotaan, Keahlian, dan Independensi Anggota Komite',
                    'strukturkomite' => $this->model->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('strukturkomite/index', $data);
                echo view('templates/v_footer');

            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'tindakstrukturkomite' => $this->request->getPost('tindakstrukturkomite')
                ];

                // Update data
                $success = $this->model->ubah($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Tindak Lanjut Rekomendasi Dewan Komisaris berhasil diubah ');
                    return redirect()->to(base_url('strukturkomite'));
                }
            }
        } else {
            return redirect()->to(base_url('strukturkomite'));
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

        return redirect()->to(base_url('strukturkomite'));

    }

    public function excel()
    {
        $data = [
            'strukturkomite' => $this->model->getAllData()
        ];

        echo view('strukturkomite/excel', $data);

    }

    public function approve($idStrukturkomite)
    {
        if (!is_numeric($idStrukturkomite) || $idStrukturkomite <= 0) {
            session()->setFlashdata('err', 'ID Struktur, Keanggotaan, Keahlian, dan Independensi Anggota Komite tidak valid.');
            return redirect()->back();
        }

        $tgjwbdir = $this->strukturkomiteModel->find($idStrukturkomite);
        if (!$tgjwbdir) {
            session()->setFlashdata('err', 'Data Struktur, Keanggotaan, Keahlian, dan Independensi Anggota Komite dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idStrukturkomite,
            'is_approved' => 1,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->strukturkomiteModel->save($dataUpdate)) {
            session()->setFlashdata('message', 'Struktur, Keanggotaan, Keahlian, dan Independensi Anggota Komite berhasil disetujui.');
            return redirect()->back();
        } else {
            session()->setFlashdata('err', 'Terjadi kesalahan saat melakukan approval.');
            return redirect()->back();
        }
    }

    public function unapprove($idStrukturkomite)
    {
        if (!is_numeric($idStrukturkomite) || $idStrukturkomite <= 0) {
            session()->setFlashdata('err', 'ID Struktur, Keanggotaan, Keahlian, dan Independensi Anggota Komite tidak valid.');
            return redirect()->back();
        }

        $tgjwbdir = $this->strukturkomiteModel->find($idStrukturkomite);
        if (!$tgjwbdir) {
            session()->setFlashdata('err', 'Data Struktur, Keanggotaan, Keahlian, dan Independensi Anggota Komite dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $idStrukturkomite,
            'is_approved' => 2,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->strukturkomiteModel->save($dataUpdate)) {
            session()->setFlashdata('err', 'Approval Struktur, Keanggotaan, Keahlian, dan Independensi Anggota Komite dibatalkan.');
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

        $this->strukturkomiteModel->builder()->update($dataUpdate);

        session()->setFlashdata('message', 'Semua Struktur, Keanggotaan, Keahlian, dan Independensi Anggota Komite berhasil disetujui.');
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

        $this->strukturkomiteModel->builder()->update($dataUpdate);

        session()->setFlashdata('err', 'Semua approval Struktur, Keanggotaan, Keahlian, dan Independensi Anggota Komite dibatalkan.');
        return redirect()->back();
    }

    public function exporttxtstrukturkomite()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }
        $data_strukturkomite = $this->model->getAllData();
        $data_infobpr = $this->infobprModel->getAllData();

        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'];
            $kodejenis = $infobpr['kodejenis'];
        }

        $output = "";
        $output .= "H01|" . $kodejenis . "|" . $sandibpr . "|2025-05-31|LTBPRK|E0204|0|\n";
        foreach ($data_strukturkomite as $row) {
            $output .= "D01|" . "013201000000" . "|" . $row['anggotakomite'] . "|" . $row['nikkomite'] . "|" . $row['keahlian'] . "|" . $row['jbtaudit'] . "|" . $row['jbtpantauresiko'] . "|" . $row['jbtremunerasi'] . "|" . $row['jbtmanrisk'] . "|" . $row['jbtlain'] . "|" . $row['independen'] . "\n";
        }

        if (!empty($data_strukturkomite)) {
            $footer_row = end($data_strukturkomite);
            $output .= "F01|" . "Footer 1" . " " . $footer_row['tindakstrukturkomite'];
        } else {
            $output .= "F01|" . "Footer 1";
        }

        $response = service('response');

        $filename = "LTBPRK-E0204-R-A-20250531-" . $sandibpr . "-01.txt";

        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->setBody($output);

        return $response;
    }

}


