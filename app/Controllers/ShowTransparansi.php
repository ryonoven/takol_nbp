<?php

namespace App\Controllers;

use App\Models\M_periodetransparansi;
use CodeIgniter\Controller;
use App\Models\M_showtransparansi;
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
use App\Models\M_danasosial;
use App\Models\M_user;

use Myth\Auth\Config\Services as AuthServices;

class ShowTransparansi extends Controller
{
    protected $auth;
    protected $session;
    protected $userKodebpr;
    protected $userId;

    protected $showtransparansiModel;
    protected $userModel;
    protected $infobprModel;
    protected $periodetransparansiModel;
    protected $komentarModel;
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
    protected $danasosialModel;

    private $userPermissions = null;
    private $userData = null;

    protected $userInGroupPE;
    protected $userInGroupAdmin;
    protected $userInGroupDekom;
    protected $userInGroupDireksi;

    public function __construct()
    {
        $this->penjelasanumumModel = new M_penjelasanumum();
        $this->tgjwbdirModel = new M_tgjwbdir();
        $this->tgjwbdekomModel = new M_tgjwbdekom();
        $this->tgjwbkomiteModel = new M_tgjwbkomite();
        $this->strukturkomiteModel = new M_strukturkomite;
        $this->sahamdirdekomModel = new M_sahamdirdekom();
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
        $this->danasosialModel = new M_danasosial();
        $this->infobprModel = new M_infobpr();
        // $this->periodetransparansiModel = new M_periodetransparansi();
        $this->userModel = new M_user();
        $this->showtransparansiModel = new M_showtransparansi();
        $this->session = service('session');
        $this->auth = service('authentication');

        helper('text');

        if ($this->auth->isLoggedIn()) {
            $userId = $this->auth->id();
            $user = $this->userModel->find($userId);
            $this->userKodebpr = $user['kodebpr'] ?? null;

            $authorize = AuthServices::authorization();
            $this->userInGroupPE = $authorize->inGroup('pe', $userId);
            $this->userInGroupAdmin = $authorize->inGroup('admin', $userId);
            $this->userInGroupDekom = $authorize->inGroup('dekom', $userId);
            $this->userInGroupDireksi = $authorize->inGroup('direksi', $userId);
        } else {
            $this->userKodebpr = null;
            $this->userInGroupPE = false;
            $this->userInGroupAdmin = false;
            $this->userInGroupDekom = false;
            $this->userInGroupDireksi = false;
        }
    }

    private function getPeriodeModel()
    {
        if (!$this->periodetransparansiModel) {
            $this->periodetransparansiModel = new M_periodetransparansi();
        }
        return $this->periodetransparansiModel;
    }

    public function index()
    {
        if (!$this->auth->check()) {
            session()->set('redirect_url', current_url());
            return redirect()->to('/login');
        }

        if (!session('active_periode')) {
            return redirect()->to('/Periodetransparansi')->with('error', 'Silakan pilih periode aktif terlebih dahulu.');
        }

        $periodeId = session('active_periode');
        $kodebpr = $this->userKodebpr;

        $showtransparansi = $this->showtransparansiModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $id = $showtransparansi['id'] ?? null;

        $allShowTransparansi = $this->showtransparansiModel->findAll(); // untuk tabel

        log_message('debug', 'ShowTransparansi: User ID: ' . $this->auth->id() . ', Kode BPR: ' . $kodebpr . ', Periode ID: ' . $periodeId);

        if (!$kodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid.');
        }

        $periodeDetail = $this->getPeriodeModel()->getPeriodeDetail($periodeId);
        $bprData = $this->infobprModel->getBprByKode($kodebpr);

        $transparan = [
            [
                'name' => '1. Penjelasan Umum Penerapan Tata Kelola',
                'link' => base_url('Penjelasanumum'),
                'accdekom' => $this->penjelasanumumModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['accdekom'] ?? null,
                'is_approved' => $this->penjelasanumumModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['is_approved'] ?? null,
            ],
            [
                'name' => '2. Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi',
                'link' => base_url('Tgjwbdir'),
                'accdekom' => $this->tgjwbdirModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['accdekom'] ?? null,
                'is_approved' => $this->tgjwbdirModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['is_approved'] ?? null,
            ],
            [
                'name' => '3. Pelaksanaan Tugas dan Tanggung Jawab Anggota Dewan Komisaris',
                'link' => base_url('Tgjwbdekom'),
                'accdekom' => $this->tgjwbdekomModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['accdekom'] ?? null,
                'is_approved' => $this->tgjwbdekomModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['is_approved'] ?? null,
            ],
            [
                'name' => '4. Tugas, Tanggung Jawab, Program Kerja, dan Realisasi Program Kerja Komite',
                'link' => base_url('Tgjwbkomite'),
                'accdekom' => $this->tgjwbkomiteModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['accdekom'] ?? null,
                'is_approved' => $this->tgjwbkomiteModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['is_approved'] ?? null,
            ],
            [
                'name' => '5. Struktur, Keanggotaan, Keahlian, dan Independensi Anggota Komite',
                'link' => base_url('Strukturkomite'),
                'accdekom' => $this->strukturkomiteModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['accdekom'] ?? null,
                'is_approved' => $this->strukturkomiteModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['is_approved'] ?? null,
            ],
            [
                'name' => '6. Kepemilikan Saham Anggota Direksi dan Dewan Komisaris pada BPR',
                'link' => base_url('Sahamdirdekom'),
                'accdekom' => $this->sahamdirdekomModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['accdekom'] ?? null,
                'is_approved' => $this->sahamdirdekomModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['is_approved'] ?? null,
            ],
            [
                'name' => '7. Kepemilikan Saham Anggota Direksi, Dewan Komisaris, dan Pemegang Saham pada Kelompok Usaha BPR',
                'link' => base_url('Shmusahadirdekom'),
                'accdekom' => $this->shmusahadirdekomModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['accdekom'] ?? null,
                'is_approved' => $this->shmusahadirdekomModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['is_approved'] ?? null,
            ],
            [
                'name' => '8. Kepemilikan Saham Anggota Direksi dan Dewan Komisaris pada Perusahaan Lain',
                'link' => base_url('Shmdirdekomlain'),
                'accdekom' => $this->shmdirdekomlainModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['accdekom'] ?? null,
                'is_approved' => $this->shmdirdekomlainModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['is_approved'] ?? null,
            ],
            [
                'name' => '9. Hubungan Keuangan Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR',
                'link' => base_url('Keuangandirdekompshm'),
                'accdekom' => $this->keuangandirdekompshmModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['accdekom'] ?? null,
                'is_approved' => $this->keuangandirdekompshmModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['is_approved'] ?? null,
            ],
            [
                'name' => '10. Hubungan Keluarga Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR',
                'link' => base_url('Keluargadirdekompshm'),
                'accdekom' => $this->keluargadirdekompshmModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['accdekom'] ?? null,
                'is_approved' => $this->keluargadirdekompshmModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['is_approved'] ?? null,
            ],
            [
                'name' => '11. Paket/Kebijakan Remunerasi dan Fasilitas Lain bagi Direksi dan Dewan Komisaris',
                'link' => base_url('Paketkebijakandirdekom'),
                'accdekom' => $this->paketkebijakandirdekomModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['accdekom'] ?? null,
                'is_approved' => $this->paketkebijakandirdekomModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['is_approved'] ?? null,
            ],
            [
                'name' => '12. Rasio Gaji Tertinggi dan Gaji Terendah',
                'link' => base_url('Rasiogaji'),
                'accdekom' => $this->rasiogajiModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['accdekom'] ?? null,
                'is_approved' => $this->rasiogajiModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['is_approved'] ?? null,
            ],
            [
                'name' => '13. Pelaksanaan Rapat dalam 1 (satu) tahun',
                'link' => base_url('Rapat'),
                'accdekom' => $this->rapatModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['accdekom'] ?? null,
                'is_approved' => $this->rapatModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['is_approved'] ?? null,
            ],
            [
                'name' => '14. Kehadiran Anggota Dewan Komisaris',
                'link' => base_url('Kehadirandekom'),
                'accdekom' => $this->kehadirandekomModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['accdekom'] ?? null,
                'is_approved' => $this->kehadirandekomModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['is_approved'] ?? null,
            ],
            [
                'name' => '15. Jumlah Penyimpangan Intern (Internal Fraud)',
                'link' => base_url('Fraudinternal'),
                'accdekom' => $this->fraudinternalModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['accdekom'] ?? null,
                'is_approved' => $this->fraudinternalModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['is_approved'] ?? null,
            ],
            [
                'name' => '16. Permasalahan Hukum yang Dihadapi',
                'link' => base_url('Masalahhukum'),
                'accdekom' => $this->masalahhukumModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['accdekom'] ?? null,
                'is_approved' => $this->masalahhukumModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['is_approved'] ?? null,
            ],
            [
                'name' => '17. Transaksi yang Mengandung Benturan Kepentingan',
                'link' => base_url('Transaksikepentingan'),
                'accdekom' => $this->transaksikepentinganModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['accdekom'] ?? null,
                'is_approved' => $this->transaksikepentinganModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['is_approved'] ?? null,
            ],
            [
                'name' => '18. Pemberian Dana untuk Kegiatan Sosial dan Kegiatan Politik',
                'link' => base_url('Danasosial'),
                'accdekom' => $this->danasosialModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['accdekom'] ?? null,
                'is_approved' => $this->danasosialModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first()['is_approved'] ?? null,
            ],
        ];

        // Kirim data ke view
        $data = [
            'judul' => 'Laporan Transparansi Tata Kelola',
            'bprData' => $bprData,
            'transparan' => $transparan,
            'periodeDetail' => $periodeDetail,
            'showtransparansi' => $showtransparansi,
            'allShowTransparansi' => $allShowTransparansi
        ];

        $showTransparansiModel = new M_showtransparansi();
        $showTransparansiModel->simpanShowTransparansi([
            'kodebpr' => $kodebpr,
            'periode_id' => $periodeId,
            'semester' => $periodeDetail['semester'] ?? 'N/A',
            'tahun' => $periodeDetail['tahun'] ?? 'N/A',
        ]);

        return view('templates/v_header', $data)
            . view('templates/v_sidebar')
            . view('templates/v_topbar')
            . view('ShowTransparansi/index', $data)
            . view('templates/v_footer');
    }

    // private function hitungPeringkatKomposit($nilaikomposit)
    // {
    //     if ($nilaikomposit == 1) {
    //         return 'Sangat Baik';
    //     } elseif ($nilaikomposit == 2) {
    //         return 'Baik';
    //     } elseif ($nilaikomposit == 3) {
    //         return 'Cukup';
    //     } elseif ($nilaikomposit == 4) {
    //         return 'Kurang Baik';
    //     } elseif ($nilaikomposit == 5) {
    //         return 'Buruk';
    //     } else {
    //         return 'Nilai komposit tidak valid';
    //     }
    // }

    // private function getColorClassForPeringkat($peringkatkomposit)
    // {
    //     switch ($peringkatkomposit) {
    //         case 'Sangat Baik':
    //             return 'text-success';
    //         case 'Baik':
    //             return 'text-info';
    //         case 'Cukup':
    //             return 'text-warning';
    //         case 'Kurang Baik':
    //             return 'text-danger';
    //         case 'Buruk':
    //             return 'text-dark';
    //         default:
    //             return 'text-muted';
    //     }
    // }

    public function update()
    {
        $id = $this->request->getPost('id');
        $data = [
            'kesimpulan' => $this->request->getPost('kesimpulan'),
            'positifstruktur' => $this->request->getPost('positifstruktur'),
            'positifproses' => $this->request->getPost('positifproses'),
            'positifhasil' => $this->request->getPost('positifhasil'),
            'negatifstruktur' => $this->request->getPost('negatifstruktur'),
            'negatifproses' => $this->request->getPost('negatifproses'),
            'negatifhasil' => $this->request->getPost('negatifhasil')
        ];
        $this->showtransparansiModel->update($id, $data);
        return redirect()->to(base_url('ShowTransparansi'))->with('message', 'Data berhasil diupdate!');
    }

    public function updatettd()
    {
        $id = $this->request->getPost('id');
        $data = [
            'dirut' => $this->request->getPost('dirut'),
            'komut' => $this->request->getPost('komut'),
            'tanggal' => $this->request->getPost('tanggal'),
            'lokasi' => $this->request->getPost('lokasi')
        ];
        $this->showtransparansiModel->update($id, $data);
        return redirect()->to(base_url('ShowTransparansi'))->with('message', 'Data berhasil diupdate!');
    }

    public function updatecover()
    {
        $id = $this->request->getPost('id');
        $data = [
            'cover' => $this->request->getPost('cover')
        ];

        if (!empty($id)) {
            $this->showtransparansiModel->update($id, $data);

            return redirect()->to(base_url('ShowTransparansi'))->with('message', 'Data berhasil diupdate!');
        } else {
            return redirect()->to(base_url('ShowTransparansi'))->with('error', 'ID tidak ditemukan!');
        }
    }

    public function exportAllToZip()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $zip = new \ZipArchive();
        $zipFileName = 'APOLO-NBP-LAPORANGCG-' . date('Y-m-d') . '.zip'; /// MASUKIN NAMA BPRNYA SAMA PERIODE
        $zipFilePath = WRITEPATH . 'uploads/' . $zipFileName;

        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
            $this->addTxtToZip('penjelasanumum', 'exporttxtpenjelasanumum', $zip);
            $this->addTxtToZip('tgjwbdir', 'exporttxttgjwbdir', $zip);
            $this->addTxtToZip('tgjwbdekom', 'exporttxttgjwbdekom', $zip);
            $this->addTxtToZip('tgjwbkomite', 'exporttxttgjwbkomite', $zip);
            $this->addTxtToZip('strukturkomite', 'exporttxtstrukturkomite', $zip);
            $this->addTxtToZip('shmusahadirdekom', 'exporttxtshmusahadirdekom', $zip);
            $this->addTxtToZip('shmdirdekomlain', 'exporttxtshmdirdekomlain', $zip);
            $this->addTxtToZip('keuangandirdekompshm', 'exporttxtkeuangandirdekompshm', $zip);
            $this->addTxtToZip('keluargadirdekompshm', 'exporttxtkeluargadirdekompshm', $zip);
            $this->addTxtToZip('paketkebijakandirdekom', 'exporttxtpaketkebijakandirdekom', $zip);
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
