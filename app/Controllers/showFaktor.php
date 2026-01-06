<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_faktor;
use App\Models\M_nilaifaktor;
use App\Models\M_faktor2;
use App\Models\M_nilaifaktor2;
use App\Models\M_faktor3;
use App\Models\M_nilaifaktor3;
use App\Models\M_faktor4;
use App\Models\M_nilaifaktor4;
use App\Models\M_faktor5;
use App\Models\M_nilaifaktor5;
use App\Models\M_faktor6;
use App\Models\M_nilaifaktor6;
use App\Models\M_faktor7;
use App\Models\M_nilaifaktor7;
use App\Models\M_faktor8;
use App\Models\M_nilaifaktor8;
use App\Models\M_faktor9;
use App\Models\M_nilaifaktor9;
use App\Models\M_faktor10;
use App\Models\M_nilaifaktor10;
use App\Models\M_faktor11;
use App\Models\M_nilaifaktor11;
use App\Models\M_faktor12;
use App\Models\M_nilaifaktor12;
use App\Models\M_infobpr;
use App\Models\M_periode;
use App\Models\M_user;
use App\Models\M_showfaktor;
use Myth\Auth\Config\Services as AuthServices;

class ShowFaktor extends Controller
{
    protected $faktorModel;
    protected $nilaiModel;

    protected $faktor2Model;
    protected $nilai2Model;

    protected $faktor3Model;
    protected $nilai3Model;

    protected $faktor4Model;
    protected $nilai4Model;

    protected $faktor5Model;
    protected $nilai5Model;

    protected $faktor6Model;
    protected $nilai6Model;

    protected $faktor7Model;
    protected $nilai7Model;

    protected $faktor8Model;
    protected $nilai8Model;

    protected $faktor9Model;
    protected $nilai9Model;

    protected $faktor10Model;
    protected $nilai10Model;

    protected $faktor11Model;
    protected $nilai11Model;

    protected $faktor12Model;
    protected $nilai12Model;

    protected $showfaktorModel;

    // Properti umum lainnya
    protected $infobprModel;
    protected $periodeModel;
    protected $userModel;
    protected $session;
    protected $auth;
    protected $userKodebpr;

    protected $userInGroupPE;
    protected $userInGroupAdmin;
    protected $userInGroupDekom;
    protected $userInGroupDireksi;

    public function __construct()
    {
        $this->faktorModel = new M_faktor();
        $this->nilaiModel = new M_nilaifaktor();
        $this->faktor2Model = new M_faktor2();
        $this->nilai2Model = new M_nilaifaktor2();
        $this->faktor3Model = new M_faktor3();
        $this->nilai3Model = new M_nilaifaktor3();
        $this->faktor4Model = new M_faktor4();
        $this->nilai4Model = new M_nilaifaktor4();
        $this->faktor5Model = new M_faktor5();
        $this->nilai5Model = new M_nilaifaktor5();
        $this->faktor6Model = new M_faktor6();
        $this->nilai6Model = new M_nilaifaktor6();
        $this->faktor7Model = new M_faktor7();
        $this->nilai7Model = new M_nilaifaktor7();
        $this->faktor8Model = new M_faktor8();
        $this->nilai8Model = new M_nilaifaktor8();
        $this->faktor9Model = new M_faktor9();
        $this->nilai9Model = new M_nilaifaktor9();
        $this->faktor10Model = new M_faktor10();
        $this->nilai10Model = new M_nilaifaktor10();
        $this->faktor11Model = new M_faktor11();
        $this->nilai11Model = new M_nilaifaktor11();
        $this->faktor12Model = new M_faktor12();
        $this->nilai12Model = new M_nilaifaktor12();
        $this->infobprModel = new M_infobpr();
        $this->periodeModel = new M_periode();
        $this->userModel = new M_user();
        $this->showfaktorModel = new M_showfaktor();
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

    public function index()
    {
        if (!$this->auth->check()) {
            session()->set('redirect_url', current_url());
            return redirect()->to('/login');
        }

        if (!session('active_periode')) {
            return redirect()->to('/periode')->with('error', 'Silakan pilih periode aktif terlebih dahulu.');
        }

        $periodeId = session('active_periode');
        $kodebpr = $this->userKodebpr;

        $showfaktor = $this->showfaktorModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $id = $showfaktor['id'] ?? null;

        $allShowFaktor = $this->showfaktorModel->findAll(); // untuk tabel

        log_message('debug', 'ShowFaktor: User ID: ' . $this->auth->id() . ', Kode BPR: ' . $kodebpr . ', Periode ID: ' . $periodeId);

        if (!$kodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid.');
        }

        $periodeDetail = $this->periodeModel->find($periodeId);
        $bprData = $this->infobprModel->getBprByKode($kodebpr);

        // Faktor-faktor dan nilai-nilainya
        $factors = [
            [
                'name' => 'Faktor 1: Aspek Pemegang Saham',
                'link' => base_url('faktor') . '?modal_nilai=' . 12,
                'nfaktor' => $this->nilaiModel->where('faktor1id', 12)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['nfaktor'] ?? 'N/A',
                'accdekom' => $this->nilaiModel->where('faktor1id', 12)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['accdekom'] ?? null,
                'is_approved' => $this->nilaiModel->where('faktor1id', 12)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['is_approved'] ?? null,
            ],
            [
                'name' => 'Faktor 2: Pelaksanaan Tugas, Tanggung Jawab, dan Wewenang Direksi',
                'link' => base_url('faktor2') . '?modal_nilai=' . 29,
                'nfaktor2' => $this->nilai2Model->where('faktor2id', 29)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['nfaktor2'] ?? 'N/A',
                'accdekom' => $this->nilai2Model->where('faktor2id', 29)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['accdekom'] ?? null,
                'is_approved' => $this->nilai2Model->where('faktor2id', 29)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['is_approved'] ?? null,
            ],
            [
                'name' => 'Faktor 3: Pelaksanaan Tugas, Tanggung Jawab, dan Wewenang Dewan Komisaris',
                'link' => base_url('faktor3') . '?modal_nilai=' . 27,
                'nfaktor3' => $this->nilai3Model->where('faktor3id', 27)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['nfaktor3'] ?? 'N/A',
                'accdekom' => $this->nilai3Model->where('faktor3id', 27)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['accdekom'] ?? null,
                'is_approved' => $this->nilai3Model->where('faktor3id', 27)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['is_approved'] ?? null,
            ],
            [
                'name' => 'Faktor 4: Kelengkapan dan Pelaksanaan Tugas Komite',
                'link' => base_url('faktor4') . '?modal_nilai=' . 12,
                'nfaktor4' => $this->nilai4Model->where('faktor4id', 12)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['nfaktor4'] ?? 'N/A',
                'accdekom' => $this->nilai4Model->where('faktor4id', 12)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['accdekom'] ?? null,
                'is_approved' => $this->nilai4Model->where('faktor4id', 12)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['is_approved'] ?? null,
            ],
            [
                'name' => 'Faktor 5: Penanganan Benturan Kepentingan',
                'link' => base_url('faktor5') . '?modal_nilai=' . 6,
                'nfaktor5' => $this->nilai5Model->where('faktor5id', 6)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['nfaktor5'] ?? 'N/A',
                'accdekom' => $this->nilai5Model->where('faktor5id', 6)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['accdekom'] ?? null,
                'is_approved' => $this->nilai5Model->where('faktor5id', 6)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['is_approved'] ?? null,
            ],
            [
                'name' => 'Faktor 6: Penerapan Fungsi Kepatuhan',
                'link' => base_url('faktor6') . '?modal_nilai=' . 10,
                'nfaktor6' => $this->nilai6Model->where('faktor6id', 10)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['nfaktor6'] ?? 'N/A',
                'accdekom' => $this->nilai6Model->where('faktor6id', 10)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['accdekom'] ?? null,
                'is_approved' => $this->nilai6Model->where('faktor6id', 10)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['is_approved'] ?? null,
            ],
            [
                'name' => 'Faktor 7: Penerapan Fungsi Audit Intern',
                'link' => base_url('faktor7') . '?modal_nilai=' . 12,
                'nfaktor7' => $this->nilai7Model->where('faktor7id', 12)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['nfaktor7'] ?? 'N/A',
                'accdekom' => $this->nilai7Model->where('faktor7id', 12)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['accdekom'] ?? null,
                'is_approved' => $this->nilai7Model->where('faktor7id', 12)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['is_approved'] ?? null,
            ],
            [
                'name' => 'Faktor 8: Penerapan Fungsi Audit Ekstern',
                'link' => base_url('faktor8') . '?modal_nilai=' . 6,
                'nfaktor8' => $this->nilai8Model->where('faktor8id', 6)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['nfaktor8'] ?? 'N/A',
                'accdekom' => $this->nilai8Model->where('faktor8id', 6)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['accdekom'] ?? null,
                'is_approved' => $this->nilai8Model->where('faktor8id', 6)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['is_approved'] ?? null,
            ],
            [
                'name' => 'Faktor 9: Penerapan Manajemen Risiko dan Strategi Anti Fraud',
                'link' => base_url('faktor9') . '?modal_nilai=' . 18,
                'nfaktor9' => $this->nilai9Model->where('faktor9id', 18)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['nfaktor9'] ?? 'N/A',
                'accdekom' => $this->nilai9Model->where('faktor9id', 18)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['accdekom'] ?? null,
                'is_approved' => $this->nilai9Model->where('faktor9id', 18)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['is_approved'] ?? null,
            ],
            [
                'name' => 'Faktor 10: Batas Maksimum Pemberian Kredit',
                'link' => base_url('faktor10') . '?modal_nilai=' . 6,
                'nfaktor10' => $this->nilai10Model->where('faktor10id', 6)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['nfaktor10'] ?? 'N/A',
                'accdekom' => $this->nilai10Model->where('faktor10id', 6)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['accdekom'] ?? null,
                'is_approved' => $this->nilai10Model->where('faktor10id', 6)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['is_approved'] ?? null,
            ],
            [
                'name' => 'Faktor 11: Integritas Pelaporan dan Sistem Teknologi Informasi',
                'link' => base_url('faktor11') . '?modal_nilai=' . 13,
                'nfaktor11' => $this->nilai11Model->where('faktor11id', 13)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['nfaktor11'] ?? 'N/A',
                'accdekom' => $this->nilai11Model->where('faktor11id', 13)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['accdekom'] ?? null,
                'is_approved' => $this->nilai11Model->where('faktor11id', 13)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['is_approved'] ?? null,
            ],
            [
                'name' => 'Faktor 12: Rencana Bisnis BPR',
                'link' => base_url('faktor12') . '?modal_nilai=' . 8,
                'nfaktor12' => $this->nilai12Model->where('faktor12id', 8)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['nfaktor12'] ?? 'N/A',
                'accdekom' => $this->nilai12Model->where('faktor12id', 8)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['accdekom'] ?? null,
                'is_approved' => $this->nilai12Model->where('faktor12id', 8)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['is_approved'] ?? null,
            ],
            // Tambahkan faktor lainnya seperti biasa
        ];

        // Menghitung rata-rata nfaktor
        $totalNilai = 0;
        $validCount = 0;
        $validFactors = 12; // Asumsikan ada 12 faktor, tetapi nanti akan disesuaikan jika nfaktor4 kosong

        foreach ($factors as $factor) {
            // Cek nfaktor4 terlebih dahulu
            if (isset($factor['nfaktor4']) && is_numeric($factor['nfaktor4']) && $factor['nfaktor4'] != 'N/A' && $factor['nfaktor4'] != 0 && $factor['nfaktor4'] !== null) {
                // Jika nfaktor4 ada dan valid, perhitungan rata-rata tetap menggunakan 12 faktor
                $totalNilai += $factor['nfaktor4'];
                $validCount++;
            }

            // Jika nfaktor4 kosong, 0, atau null, maka abaikan nfaktor4 dan hitung dengan 11 faktor
            if (isset($factor['nfaktor']) && is_numeric($factor['nfaktor']) && $factor['nfaktor'] != 'N/A') {
                $totalNilai += $factor['nfaktor'];
                $validCount++;
            } elseif (isset($factor['nfaktor2']) && is_numeric($factor['nfaktor2']) && $factor['nfaktor2'] != 'N/A') {
                $totalNilai += $factor['nfaktor2'];
                $validCount++;
            } else if (isset($factor['nfaktor3']) && is_numeric($factor['nfaktor3']) && $factor['nfaktor3'] != 'N/A') {
                $totalNilai += $factor['nfaktor3'];
                $validCount++;
            } else if (isset($factor['nfaktor5']) && is_numeric($factor['nfaktor5']) && $factor['nfaktor5'] != 'N/A') {
                $totalNilai += $factor['nfaktor5'];
                $validCount++;
            } else if (isset($factor['nfaktor6']) && is_numeric($factor['nfaktor6']) && $factor['nfaktor6'] != 'N/A') {
                $totalNilai += $factor['nfaktor6'];
                $validCount++;
            } else if (isset($factor['nfaktor7']) && is_numeric($factor['nfaktor7']) && $factor['nfaktor7'] != 'N/A') {
                $totalNilai += $factor['nfaktor7'];
                $validCount++;
            } else if (isset($factor['nfaktor8']) && is_numeric($factor['nfaktor8']) && $factor['nfaktor8'] != 'N/A') {
                $totalNilai += $factor['nfaktor8'];
                $validCount++;
            } else if (isset($factor['nfaktor9']) && is_numeric($factor['nfaktor9']) && $factor['nfaktor9'] != 'N/A') {
                $totalNilai += $factor['nfaktor9'];
                $validCount++;
            } else if (isset($factor['nfaktor10']) && is_numeric($factor['nfaktor10']) && $factor['nfaktor10'] != 'N/A') {
                $totalNilai += $factor['nfaktor10'];
                $validCount++;
            } else if (isset($factor['nfaktor11']) && is_numeric($factor['nfaktor11']) && $factor['nfaktor11'] != 'N/A') {
                $totalNilai += $factor['nfaktor11'];
                $validCount++;
            } else if (isset($factor['nfaktor12']) && is_numeric($factor['nfaktor12']) && $factor['nfaktor12'] != 'N/A') {
                $totalNilai += $factor['nfaktor12'];
                $validCount++;
            }
        }

        // Jika nfaktor4 kosong atau 0, bagi dengan 11 faktor, jika nfaktor4 valid, bagi dengan 12 faktor
        $nilaikomposit = $validCount > 0 ? $totalNilai / ($validFactors - ($validFactors === 12 && $validCount < 12 ? 1 : 0)) : 0;


        // // Pembulatan nilai komposit (rounding)
        $nilaikomposit = ($nilaikomposit - floor($nilaikomposit)) >= 0.5 ? ceil($nilaikomposit) : floor($nilaikomposit);

        $peringkatkomposit = $this->hitungPeringkatKomposit($nilaikomposit);

        // Menentukan colorClass untuk peringkat
        $colorClass = $this->getColorClassForPeringkat($peringkatkomposit);

        // Menghitung faktor-faktor dan menambahkan status persetujuan
        foreach ($factors as $index => $factor) {
            $factor['accdekom_status'] = isset($factor['accdekom']) && $factor['accdekom'] == 1 ? 'Telah disetujui oleh Komisaris Utama' : 'Belum disetujui oleh Komisaris Utama';
            $factor['direksi_status'] = isset($factor['is_approved']) && $factor['is_approved'] == 1 ? 'Telah disetujui oleh Direktur Utama' : 'Belum disetujui oleh Direktur Utama';

            // Menambahkan faktor yang telah disetujui
            $factors[$index] = $factor;
        }

        // Kirim data ke view
        $data = [
            'judul' => 'Ringkasan Penilaian Self Assessment Tata Kelola',
            'namabpr' => $bprData['namabpr'] ?? 'Nama BPR Tidak Ditemukan',
            'periode_semester' => $periodeDetail['semester'] ?? 'N/A',
            'periode_tahun' => $periodeDetail['tahun'] ?? 'N/A',
            'factors' => $factors,  // Mengirimkan array factors ke view
            'nilaikomposit' => $nilaikomposit,
            'peringkatkomposit' => $peringkatkomposit,
            'colorClass' => $colorClass,
            'showfaktor' => $showfaktor,
            'allShowFaktor' => $allShowFaktor
        ];

        $showFaktorModel = new M_showfaktor();
        $showFaktorModel->simpanShowFaktor([
            'kodebpr' => $kodebpr,
            'periode_id' => $periodeId,
            'nilaikomposit' => $nilaikomposit,
            'peringkatkomposit' => $peringkatkomposit,
            'semester' => $periodeDetail['semester'] ?? 'N/A',
            'tahun' => $periodeDetail['tahun'] ?? 'N/A',
        ]);

        return view('templates/v_header', $data)
            . view('templates/v_sidebar')
            . view('templates/v_topbar')
            . view('showfaktor/index', $data)
            . view('templates/v_footer');
    }

    private function hitungPeringkatKomposit($nilaikomposit)
    {
        if ($nilaikomposit == 1) {
            return 'Sangat Baik';
        } elseif ($nilaikomposit == 2) {
            return 'Baik';
        } elseif ($nilaikomposit == 3) {
            return 'Cukup';
        } elseif ($nilaikomposit == 4) {
            return 'Kurang Baik';
        } elseif ($nilaikomposit == 5) {
            return 'Buruk';
        } else {
            return 'Nilai komposit tidak valid';
        }
    }

    private function getColorClassForPeringkat($peringkatkomposit)
    {
        switch ($peringkatkomposit) {
            case 'Sangat Baik':
                return 'text-success';
            case 'Baik':
                return 'text-info';
            case 'Cukup':
                return 'text-warning';
            case 'Kurang Baik':
                return 'text-danger';
            case 'Buruk':
                return 'text-dark';
            default:
                return 'text-muted';
        }
    }

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
        $this->showfaktorModel->update($id, $data);
        return redirect()->to(base_url('ShowFaktor'))->with('message', 'Data berhasil diupdate!');
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
        $this->showfaktorModel->update($id, $data);
        return redirect()->to(base_url('ShowFaktor'))->with('message', 'Data berhasil diupdate!');
    }

    public function updatecover()
    {
        $id = $this->request->getPost('id');
        $data = [
            'cover' => $this->request->getPost('cover')
        ];

        // Memastikan ID ada dan tidak kosong
        if (!empty($id)) {
            // Memanggil model untuk update data dengan klausa WHERE
            $this->showfaktorModel->update($id, $data);

            // Redirect dengan pesan sukses
            return redirect()->to(base_url('ShowFaktor'))->with('message', 'Data berhasil diupdate!');
        } else {
            // Jika ID tidak valid
            return redirect()->to(base_url('ShowFaktor'))->with('error', 'ID tidak ditemukan!');
        }
    }

}
