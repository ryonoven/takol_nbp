<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_danasosial;
use App\Models\M_infobpr;
use App\Models\M_penjelasanumum;
use App\Models\M_tgjwbdir;
use App\Models\M_tgjwbdekom;
use App\Models\M_tgjwbkomite;
use App\Models\M_strukturkomite;
use App\Models\M_sahamdirdekom;
use App\Models\M_shmusahadirdekom;
use App\Models\M_shmdirdekomlain;
use App\Models\M_keuangandirdekompshm;
use App\Models\M_keluargadirdekompshm;
use App\Models\M_paketkebijakandirdekom;
use App\Models\M_rasiogaji;
use App\Models\M_rapat;
use App\Models\M_kehadirandekom;
use App\Models\M_fraudinternal;
use App\Models\M_masalahhukum;
use App\Models\M_transaksikepentingan;
use App\Models\M_user;
use Myth\Auth\Config\Services as AuthServices;

class danasosial extends Controller
{
    protected $model;
    protected $danasosialModel;
    protected $infobprModel;
    protected $penjelasanumumModel;
    protected $tgjwbdirModel;
    protected $tgjwbdekomModel;
    protected $tgjwbkomiteModel;
    protected $strukturkomiteModel;
    protected $sahamdirdekomModel;
    protected $shmusahadirdekomModel;
    protected $shmdirdekomlainModel;
    protected $keuangandirdekompshmModel;
    protected $keluargadirdekompshmModel;
    protected $paketkebijakandirdekomModel;
    protected $rasiogajiModel;
    protected $rapatModel;
    protected $kehadirandekomModel;
    protected $fraudinternalModel;
    protected $masalahhukumModel;
    protected $transaksikepentinganModel;
    protected $usermodel;
    protected $session;
    protected $auth;
    public function __construct()
    {
        $this->model = new M_danasosial();
        $this->danasosialModel = new M_danasosial();
        $this->infobprModel = new M_infobpr();
        $this->penjelasanumumModel = new M_penjelasanumum();
        $this->tgjwbdirModel = new M_tgjwbdir();
        $this->tgjwbdekomModel = new M_tgjwbdekom();
        $this->tgjwbkomiteModel = new M_tgjwbkomite();
        $this->sahamdirdekomModel = new M_sahamdirdekom();
        $this->strukturkomiteModel = new M_strukturkomite();
        $this->shmusahadirdekomModel = new M_shmusahadirdekom();
        $this->shmdirdekomlainModel = new M_shmdirdekomlain();
        $this->keuangandirdekompshmModel = new M_keuangandirdekompshm();
        $this->keluargadirdekompshmModel = new M_keluargadirdekompshm();
        $this->paketkebijakandirdekomModel = new M_paketkebijakandirdekom();
        $this->rasiogajiModel = new M_rasiogaji();
        $this->rapatModel = new M_rapat();
        $this->kehadirandekomModel = new M_kehadirandekom();
        $this->fraudinternalModel = new M_fraudinternal();
        $this->masalahhukumModel = new M_masalahhukum();
        $this->transaksikepentinganModel = new M_transaksikepentingan();
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

        $danasosialData = $this->danasosialModel->getAllData();

        $authorize = AuthServices::authorization();
        $userInGroupPE = $authorize->inGroup('pe', $this->auth->id());
        $userInGroupAdmin = $authorize->inGroup('admin', $this->auth->id());
        $userInGroupDekom = $authorize->inGroup('dekom', $this->auth->id());
        $userInGroupDireksi = $authorize->inGroup('direksi', $this->auth->id());

        $data = [
            'judul' => '18. Pemberian Dana untuk Kegiatan Sosial dan Kegiatan Politik',
            'penjelasanumum' => $this->penjelasanumumModel->getAllData(),
            'tgjwbdir' => $this->tgjwbdirModel->getAllData(),
            'tgjwbdekom' => $this->tgjwbdekomModel->getAllData(),
            'tgjwbkomite' => $this->tgjwbkomiteModel->getAllData(),
            'strukturkomite' => $this->strukturkomiteModel->getAllData(),
            'sahamdirdekom' => $this->sahamdirdekomModel->getAllData(),
            'shmusahadirdekom' => $this->shmusahadirdekomModel->getAllData(),
            'shmdirdekomlain' => $this->shmdirdekomlainModel->getAllData(),
            'keuangandirdekompshm' => $this->keuangandirdekompshmModel->getAllData(),
            'keluargadirdekompshm' => $this->keluargadirdekompshmModel->getAllData(),
            'paketkebijakandirdekom' => $this->paketkebijakandirdekomModel->getAllData(),
            'rasiogaji' => $this->rasiogajiModel->getAllData(),
            'rapat' => $this->rapatModel->getAllData(),
            'kehadirandekom' => $this->kehadirandekomModel->getAllData(),
            'fraudinternal' => $this->fraudinternalModel->getAllData(),
            'masalahhukum' => $this->masalahhukumModel->getAllData(),
            'transaksikepentingan' => $this->transaksikepentinganModel->getAllData(),
            'infobpr' => $this->infobprModel->getAllData(),
            // 'danasosial' => $this->model->getAllData(),
            'danasosial' => $danasosialData,
            'userInGroupPE' => $userInGroupPE,
            'userInGroupAdmin' => $userInGroupAdmin,
            'userInGroupDekom' => $userInGroupDekom,
            'userInGroupDireksi' => $userInGroupDireksi,
            'fullname' => $fullname,
        ];

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('danasosial/index', $data);
        echo view('templates/v_footer');


    }

    public function tambahdanasosial()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        if (isset($_POST['tambahdanasosial'])) {
            $val = $this->validate([
                'tanggalpelaksanaan' => [
                    'label' => 'Tanggal Pelaksanaan',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'jeniskegiatan' => [
                    'label' => 'Jenis Kegiatan (Sosial/Politik)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'penerimadana' => [
                    'label' => 'Penerima Dana',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'penjelasankegiatan' => [
                    'label' => 'Jenis Kegiatan (Sosial/Politik)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'jumlah' => [
                    'label' => 'Jumlah (Rp)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Penjelasan Tugas dan tanggung jawab',
                    'danasosial' => $this->model->getAllData()
                ];
                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('danasosial/index', $data);
                echo view('templates/v_footer');
            } else {
                // Bersihkan format Rupiah dari input jumlah
                $jumlahInput = $this->request->getPost('jumlah');
                $jumlahBersih = preg_replace('/[^0-9]/', '', $jumlahInput);

                $data = [
                    'tanggalpelaksanaan' => $this->request->getPost('tanggalpelaksanaan'),
                    'jeniskegiatan' => $this->request->getPost('jeniskegiatan'),
                    'penerimadana' => $this->request->getPost('penerimadana'),
                    'penjelasankegiatan' => $this->request->getPost('penjelasankegiatan'),
                    'jumlah' => $jumlahBersih, // Gunakan nilai bersih (angka saja)
                ];

                // Insert data
                $this->model->checkIncrement();
                $success = $this->model->tambahdanasosial($data);
                if ($success) {
                    session()->setFlashdata('message', 'Data berhasil ditambahkan ');
                    return redirect()->to(base_url('danasosial'));
                }
            }
        } else {
            return redirect()->to(base_url('danasosial'));
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
                'tanggalpelaksanaan' => [
                    'label' => 'Tanggal Pelaksanaan',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'jeniskegiatan' => [
                    'label' => 'Jenis Kegiatan (Sosial/Politik)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'penerimadana' => [
                    'label' => 'Penerima Dana',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'penjelasankegiatan' => [
                    'label' => 'Jenis Kegiatan (Sosial/Politik)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'jumlah' => [
                    'label' => 'Jumlah (Rp)',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Pemberian Dana untuk Kegiatan Sosial dan Kegiatan Politik',
                    'danasosial' => $this->model->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('danasosial/index', $data);
                echo view('templates/v_footer');

            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'tanggalpelaksanaan' => $this->request->getPost('tanggalpelaksanaan'),
                    'jeniskegiatan' => $this->request->getPost('jeniskegiatan'),
                    'penerimadana' => $this->request->getPost('penerimadana'),
                    'penjelasankegiatan' => $this->request->getPost('penjelasankegiatan'),
                    'jumlah' => $this->request->getPost('jumlah'),
                ];

                // Update data
                $success = $this->model->ubah($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Pemberian Dana untuk Kegiatan Sosial dan Kegiatan Politik berhasil diubah ');
                    return redirect()->to(base_url('danasosial'));
                }
            }
        } else {
            return redirect()->to(base_url('danasosial'));
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
                    'judul' => 'Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi',
                    'danasosial' => $this->model->getAllData()
                ];

                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('danasosial/index', $data);
                echo view('templates/v_footer');

            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'keterangan' => $this->request->getPost('keterangan')
                ];

                // Update data
                $success = $this->model->ubah($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Keterangan ');
                    return redirect()->to(base_url('danasosial'));
                }
            }
        } else {
            return redirect()->to(base_url('danasosial'));
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

        return redirect()->to(base_url('danasosial'));

    }

    public function approve($iddanasosial)
    {
        if (!is_numeric($iddanasosial) || $iddanasosial <= 0) {
            session()->setFlashdata('err', 'ID Data Pemberian Dana untuk Kegiatan Sosial dan Kegiatan Politik tidak valid.');
            return redirect()->back();
        }

        $danasosial = $this->danasosialModel->find($iddanasosial);
        if (!$danasosial) {
            session()->setFlashdata('err', 'Data Data Pemberian Dana untuk Kegiatan Sosial dan Kegiatan Politik dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $iddanasosial,
            'is_approved' => 1,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->danasosialModel->save($dataUpdate)) {
            session()->setFlashdata('message', 'Data Pemberian Dana untuk Kegiatan Sosial dan Kegiatan Politik berhasil disetujui.');
            return redirect()->back();
        } else {
            session()->setFlashdata('err', 'Terjadi kesalahan saat melakukan approval.');
            return redirect()->back();
        }
    }

    public function unapprove($iddanasosial)
    {
        if (!is_numeric($iddanasosial) || $iddanasosial <= 0) {
            session()->setFlashdata('err', 'ID Data Pemberian Dana untuk Kegiatan Sosial dan Kegiatan Politik tidak valid.');
            return redirect()->back();
        }

        $danasosial = $this->danasosialModel->find($iddanasosial);
        if (!$danasosial) {
            session()->setFlashdata('err', 'Data Data Pemberian Dana untuk Kegiatan Sosial dan Kegiatan Politik dengan ID tersebut tidak ditemukan.');
            return redirect()->back();
        }

        date_default_timezone_set('Asia/Jakarta');

        $userId = service('authentication')->id();

        $dataUpdate = [
            'id' => $iddanasosial,
            'is_approved' => 2,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->danasosialModel->save($dataUpdate)) {
            session()->setFlashdata('err', 'Approval Data Pemberian Dana untuk Kegiatan Sosial dan Kegiatan Politik dibatalkan.');
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

        $this->danasosialModel->builder()->update($dataUpdate);

        session()->setFlashdata('message', 'Semua Data Pemberian Dana untuk Kegiatan Sosial dan Kegiatan Politik berhasil disetujui.');
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

        $this->danasosialModel->builder()->update($dataUpdate);

        session()->setFlashdata('err', 'Semua approval data Pemberian Dana untuk Kegiatan Sosial dan Kegiatan Politik dibatalkan.');
        return redirect()->back();
    }

    public function excel()
    {
        $data = [
            'danasosial' => $this->model->getAllData()
        ];

        echo view('danasosial/excel', $data);

    }

    public function exporttxtdanasosial()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }
        $data_danasosial = $this->model->getAllData();
        $data_infobpr = $this->infobprModel->getAllData();

        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'];
            $kodejenis = $infobpr['kodejenis'];
        }

        $output = "";
        $output .= "H01|" . $kodejenis . "|" . $sandibpr . "|2025-05-31|LTBPRK|E01100|0|\n";
        foreach ($data_danasosial as $row) {
            $output .= "D01|" . "120100000000" . "|" . $row['tanggalpelaksanaan'] . "|" . $row['jeniskegiatan'] . "|" . $row['penerimadana'] . "|" . $row['penjelasankegiatan'] . "|" . $row['jumlah'] . "\n";
        }

        if (!empty($data_danasosial)) {
            $footer_row = end($data_danasosial);
            $output .= "F01|" . "Footer 1" . "|" . $footer_row['keterangan'];
        } else {
            $output .= "F01|" . "Footer 1" . "|";
        }

        $response = service('response');

        $filename = "LTBPRK-E01100-R-A-20250531-" . $sandibpr . "-01.txt";

        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response->setBody($output);


        echo $output;
    }

    public function exportAllToZip()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $zip = new \ZipArchive();
        $zipFileName = 'APOLO-NBP-LAPORANGCG-' . date('Y-m-d') . '.zip';
        $zipFilePath = WRITEPATH . 'uploads/' . $zipFileName;

        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
            $this->addTxtToZip('penjelasanumum', 'exporttxtpenjelasanumum', $zip);
            $this->addTxtToZip('tgjwbdir', 'exporttxttgjwbdir', $zip);
            $this->addTxtToZip('tgjwbdekom', 'exporttxttgjwbdekom', $zip);
            $this->addTxtToZip('tgjwbkomite', 'exporttxttgjwbkomite', $zip);
            $this->addTxtToZip('strukturkomite', 'exporttxtstrukturkomite', $zip);
            $this->addTxtToZip('sahamdirdekom', 'exporttxtsahamdirdekom', $zip);
            $this->addTxtToZip('shmusahadirdekom', 'exporttxtshmusahadirdekom', $zip);
            $this->addTxtToZip('shmdirdekomlain', 'exporttxtshmdirdekomlain', $zip);
            $this->addTxtToZip('keuangandirdekompshm', 'exporttxtkeuangandirdekompshm', $zip);
            $this->addTxtToZip('keluargadirdekompshm', 'exporttxtkeluargadirdekompshm', $zip);
            $this->addTxtToZip('remunlaindirdekom', 'exporttxtremunlaindirdekom', $zip);
            $this->addTxtToZip('rasiogaji', 'exporttxtrasiogaji', $zip);
            $this->addTxtToZip('rapat', 'exporttxtrapat', $zip);
            $this->addTxtToZip('kehadirandekom', 'exporttxtkehadirandekom', $zip);
            $this->addTxtToZip('fraudinternal', 'exporttxtfraudinternal', $zip);
            $this->addTxtToZip('masalahhukum', 'exporttxtmasalahhukum', $zip);
            $this->addTxtToZip('transaksikepentingan', 'exporttxttransaksikepentingan', $zip);
            $this->addTxtToZip('danasosial', 'exporttxtdanasosial', $zip);

            $zip->close();

            $this->response->setHeader('Content-Type', 'application/zip');
            $this->response->setHeader('Content-Disposition', 'attachment; filename="' . $zipFileName . '"');
            $this->response->setHeader('Content-Length', filesize($zipFilePath));

            readfile($zipFilePath);

            unlink($zipFilePath);
        } else {
            echo 'Gagal membuat file ZIP';
        }
    }

    private function addTxtToZip(string $controllerName, string $methodName, \ZipArchive &$zip)
    {
        $controllerClassName = 'App\Controllers\\' . ucfirst($controllerName);
        if (class_exists($controllerClassName)) {
            $controllerInstance = new $controllerClassName();
            if (method_exists($controllerInstance, $methodName)) {
                $response = $controllerInstance->$methodName();
                if ($response instanceof \CodeIgniter\HTTP\Response) {
                    $txtContent = $response->getBody();

                    $contentDisposition = $response->getHeaderLine('Content-Disposition');
                    $fileNameInZip = $controllerName . '.txt';

                    if (preg_match('/filename="([^"]+)"/', $contentDisposition, $matches)) {
                        $fileNameInZip = $matches[1];
                    }

                    $zip->addFromString($fileNameInZip, $txtContent);
                } else {
                    log_message('warning', "Method '$methodName' di controller '$controllerName' tidak mengembalikan objek Response yang valid.");
                }
            } else {
                log_message('warning', "Method '$methodName' tidak ditemukan di controller '$controllerName'");
            }
        } else {
            log_message('warning', "Controller '$controllerName' tidak ditemukan");
        }
    }




}


