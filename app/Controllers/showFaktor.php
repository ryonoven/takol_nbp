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

        $allShowFaktor = $this->showfaktorModel->findAll();

        log_message('debug', 'ShowFaktor: User ID: ' . $this->auth->id() . ', Kode BPR: ' . $kodebpr . ', Periode ID: ' . $periodeId);

        if (!$kodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid.');
        }

        $periodeDetail = $this->periodeModel->find($periodeId);
        $bprData = $this->infobprModel->getBprByKode($kodebpr);

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
        ];

        $totalNilai = 0;
        $validCount = 0;
        $validFactors = 12;

        foreach ($factors as $factor) {
            if (isset($factor['nfaktor4']) && is_numeric($factor['nfaktor4']) && $factor['nfaktor4'] != 'N/A' && $factor['nfaktor4'] != 0 && $factor['nfaktor4'] !== null) {
                $totalNilai += $factor['nfaktor4'];
                $validCount++;
            }

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

        $nilaikomposit = $validCount > 0 ? $totalNilai / ($validFactors - ($validFactors === 12 && $validCount < 12 ? 1 : 0)) : 0;

        $nilaikomposit = ($nilaikomposit - floor($nilaikomposit)) >= 0.5 ? ceil($nilaikomposit) : floor($nilaikomposit);

        $peringkatkomposit = $this->hitungPeringkatKomposit($nilaikomposit);

        $colorClass = $this->getColorClassForPeringkat($peringkatkomposit);

        foreach ($factors as $index => $factor) {
            $factor['accdekom_status'] = isset($factor['accdekom']) && $factor['accdekom'] == 1 ? 'Telah disetujui oleh Komisaris Utama' : 'Belum disetujui oleh Komisaris Utama';
            $factor['direksi_status'] = isset($factor['is_approved']) && $factor['is_approved'] == 1 ? 'Telah disetujui oleh Direktur Utama' : 'Belum disetujui oleh Direktur Utama';

            $factors[$index] = $factor;
        }

        $data = [
            'judul' => 'Self Assessment Tata Kelola',
            'namabpr' => $bprData['namabpr'] ?? 'Nama BPR Tidak Ditemukan',
            'periode_semester' => $periodeDetail['semester'] ?? 'N/A',
            'periode_tahun' => $periodeDetail['tahun'] ?? 'N/A',
            'factors' => $factors,
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
            . view('Laporansemester/Tatakelola/showfaktor', $data)
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

    private function wrapByWordCount(string $text, int $maxWords): array
    {
        $words = preg_split('/\s+/', trim($text));
        $lines = [];
        foreach (array_chunk($words, $maxWords) as $chunk) {
            $lines[] = implode(' ', $chunk);
        }
        return $lines ?: [''];
    }

    public function cetakPengesahan()
    {
        if (!$this->auth->check()) {
            return redirect()->to('/login');
        }

        if (!session('active_periode')) {
            return redirect()->to('/periode')->with('error', 'Silakan pilih periode aktif terlebih dahulu.');
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        if (!$kodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid.');
        }

        $showfaktor = $this->showfaktorModel
            ->select('dirut, komut, tanggal, lokasi')
            ->where(['kodebpr' => $kodebpr, 'periode_id' => $periodeId])
            ->first();

        $bprData = $this->infobprModel->getBprByKode($kodebpr);
        $periodeDetail = $this->periodeModel->find($periodeId);

        $namabpr = $bprData['namabpr'] ?? 'Nama BPR Tidak Ditemukan';
        $alamat = $bprData['alamat'] ?? '';
        $email = $bprData['email'] ?? '';
        $nomor = $bprData['nomor'] ?? '';
        $webbpr = $bprData['webbpr'] ?? '';
        $logo = $bprData['logo'] ?? '';

        $semester = $periodeDetail['semester'] ?? 'N/A';
        $semNum = strtolower($semester) === 'ganjil' ? '1' : (strtolower($semester) === 'genap' ? '2' : $semester);
        $tahun = $periodeDetail['tahun'] ?? 'N/A';

        $dirut = $showfaktor['dirut'] ?? '';
        $komut = $showfaktor['komut'] ?? '';
        $lokasi = $showfaktor['lokasi'] ?? '';
        $tanggalRaw = $showfaktor['tanggal'] ?? '';

        $bulanIndo = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        $tanggalFormatted = '';
        if (!empty($tanggalRaw)) {
            try {
                // Data tanggal disimpan dengan format Y/m/d (lihat flatpickr di view)
                $dt = \DateTime::createFromFormat('Y/m/d', $tanggalRaw) ?: new \DateTime($tanggalRaw);
                $tanggalFormatted = $dt->format('d') . ' ' . $bulanIndo[(int) $dt->format('n')] . ' ' . $dt->format('Y');
            } catch (\Exception $e) {
                $tanggalFormatted = esc($tanggalRaw);
            }
        }

        $pdf = new \FPDF('P', 'mm', 'A4');
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();

        // ===== HEADER: logo kiri, info BPR kanan (rata kanan) =====
        $headerTop = $pdf->GetY();
        $logoSize = 18;

        $logoPath = $logo ? FCPATH . 'asset/img/' . $logo : null;
        if ($logoPath && file_exists($logoPath)) {
            $pdf->Image($logoPath, 15, $headerTop, $logoSize);
        }
        $logoBottom = $headerTop + $logoSize;

        $pdf->SetXY(15, $headerTop);
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->Cell(180, 6, $namabpr, 0, 2, 'R');

        $pdf->SetFont('Arial', '', 9);
        if ($alamat) {
            foreach ($this->wrapByWordCount($alamat, 9) as $alamatLine) {
                $pdf->SetX(15);
                $pdf->Cell(180, 4.5, $alamatLine, 0, 2, 'R');
            }
        }

        $kontak = array_filter([$webbpr, $email, $nomor]);
        if ($kontak) {
            $pdf->SetX(15);
            $pdf->Cell(180, 4.5, implode(' | ', $kontak), 0, 1, 'R');
        }

        $textBottom = $pdf->GetY();
        $pdf->SetY(max($textBottom, $logoBottom) + 3);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(5);

        // ===== JUDUL =====
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->MultiCell(
            0,
            6,
            "LEMBAR PERSETUJUAN DAN PENANDATANGANAN\nLAPORAN PELAKSANAAN SELF ASSESSMENT\nTATA KELOLA (GOOD CORPORATE GOVERNANCE)",
            0,
            'C'
        );
        $pdf->Ln(8);

        // ===== PERNYATAAN =====
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 6, 'Dengan ini kami menyatakan bahwa:', 0, 1, 'C');
        $pdf->Ln(4);

        $pdf->SetFont('Arial', 'B', 11);
        $pdf->MultiCell(
            0,
            6,
            "Laporan Pelaksanaan Self Assessment Tata Kelola\n{$namabpr} Semester {$semNum} Tahun {$tahun}",
            0,
            'C'
        );
        $pdf->Ln(6);

        $pdf->SetFont('Arial', '', 10);
        $pdf->MultiCell(
            0,
            5.5,
            "Telah disusun mengacu pada ketentuan Peraturan Otoritas Jasa Keuangan mengenai Penerapan Tata Kelola bagi Bank Perekonomian Rakyat.",
            0,
            'J'
        );
        $pdf->Ln(16);

        // ===== LOKASI, TANGGAL =====
        $pdf->SetFont('Arial', 'B', 10);
        $lokasiTanggal = trim($lokasi . ($tanggalFormatted ? ', ' . $tanggalFormatted : ''));
        $pdf->Cell(0, 6, $lokasiTanggal, 0, 1, 'C');
        $pdf->Ln(20);

        // ===== TANDA TANGAN: Direktur Utama (kiri) & Komisaris Utama (kanan) =====
        $colWidth = 90;

        $pdf->SetFont('Arial', 'BU', 10);
        $pdf->Cell($colWidth, 6, $dirut, 0, 0, 'C');
        $pdf->Cell($colWidth, 6, $komut, 0, 1, 'C');

        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell($colWidth, 6, 'Direktur Utama', 0, 0, 'C');
        $pdf->Cell($colWidth, 6, 'Komisaris Utama', 0, 1, 'C');

        $filenameSafe = preg_replace('/[^A-Za-z0-9_-]/', '_', $namabpr);
        $filename = "Lembar-Pengesahan-GCG-{$filenameSafe}.pdf";

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->setBody($pdf->Output('S'));
    }

    public function updatecover()
    {
        $id = $this->request->getPost('id');
        $data = [
            'cover' => $this->request->getPost('cover')
        ];

        if (!empty($id)) {
            $this->showfaktorModel->update($id, $data);

            return redirect()->to(base_url('ShowFaktor'))->with('message', 'Data berhasil diupdate!');
        } else {
            return redirect()->to(base_url('ShowFaktor'))->with('error', 'ID tidak ditemukan!');
        }
    }

    public function autosave()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Permintaan tidak valid']);
        }

        $id = $this->request->getPost('id');
        $field = $this->request->getPost('field');
        $value = $this->request->getPost('value');

        $allowedFields = [
            'kesimpulan',
            'positifstruktur',
            'positifproses',
            'positifhasil',
            'negatifstruktur',
            'negatifproses',
            'negatifhasil',
            'dirut',
            'komut',
            'tanggal',
            'lokasi'
        ];

        if (!in_array($field, $allowedFields, true)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Field tidak diizinkan']);
        }

        if (empty($id)) {
            return $this->response->setJSON(['success' => false, 'message' => 'ID tidak ditemukan']);
        }

        $existing = $this->showfaktorModel->find($id);
        if (!$existing) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak ditemukan']);
        }

        $this->showfaktorModel->update($id, [$field => $value]);

        return $this->response->setJSON(['success' => true]);
    }

    public function exporttxtshowfaktor()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        $periodeDetail = $this->periodeModel->find($periodeId);
        $modalinti = $periodeDetail['modalinti'];
        $kategori = $periodeDetail['kategori'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];
        $tahun = $periodeDetail['tahun'] ?? date('Y');
        $semester = $periodeDetail['semester'] ?? '';

        if (strtolower($semester) === 'ganjil' || $semester === '1') {
            $exportDate = "{$tahun}-06-30";
        } else {
            $exportDate = "{$tahun}-12-31";
        }
        $titleDate = str_replace('-', '', $exportDate);

        $showfaktor = $this->showfaktorModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        // Ambil info BPR
        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);
        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $sandibpr = $data_infobpr[0]['sandibpr'] ?? '';
            $kodejenis = $data_infobpr[0]['kodejenis'] ?? '';
        }

        // Sanitizer
        $sanitize = function ($text) {
            $text = str_replace(["\r", "\n"], ' ', $text);
            $text = str_replace(
                ['"', '"', '%', '|'],
                ['"', '"', "'", "'", ' persen ', ' '],
                $text
            );
            $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
            return trim(preg_replace('/\s+/', ' ', $text));
        };

        // Ambil nilai masing-masing faktor
        $nfaktor1 = $this->nilaiModel->where('faktor1id', 12)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['nfaktor'] ?? '';
        $nfaktor2 = $this->nilai2Model->where('faktor2id', 29)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['nfaktor2'] ?? '';
        $nfaktor3 = $this->nilai3Model->where('faktor3id', 27)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['nfaktor3'] ?? '';
        $nfaktor4 = $this->nilai4Model->where('faktor4id', 12)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['nfaktor4'] ?? '';
        $nfaktor5 = $this->nilai5Model->where('faktor5id', 6)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['nfaktor5'] ?? '';
        $nfaktor6 = $this->nilai6Model->where('faktor6id', 10)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['nfaktor6'] ?? '';
        $nfaktor7 = $this->nilai7Model->where('faktor7id', 12)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['nfaktor7'] ?? '';
        $nfaktor8 = $this->nilai8Model->where('faktor8id', 6)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['nfaktor8'] ?? '';
        $nfaktor9 = $this->nilai9Model->where('faktor9id', 18)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['nfaktor9'] ?? '';
        $nfaktor10 = $this->nilai10Model->where('faktor10id', 6)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['nfaktor10'] ?? '';
        $nfaktor11 = $this->nilai11Model->where('faktor11id', 13)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['nfaktor11'] ?? '';
        $nfaktor12 = $this->nilai12Model->where('faktor12id', 8)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->orderBy('created_at', 'DESC')->first()['nfaktor12'] ?? '';

        // Kalau nfaktor4 tidak ada data atau 0, paksa jadi 1 (baik untuk output F0400 maupun perhitungan komposit)
        if (!is_numeric($nfaktor4) || $nfaktor4 == 0) {
            $nfaktor4 = '';
        }

        // Hitung ulang nilaikomposit langsung dari nfaktor1..12 (tidak bergantung pada tabel showfaktor)
        $nfaktorList = [$nfaktor1, $nfaktor2, $nfaktor3, $nfaktor4, $nfaktor5, $nfaktor6, $nfaktor7, $nfaktor8, $nfaktor9, $nfaktor10, $nfaktor11, $nfaktor12];

        $totalNilai = 0;
        $validCount = 0;
        foreach ($nfaktorList as $nf) {
            if (is_numeric($nf) && $nf !== '') {
                $totalNilai += $nf;
                $validCount++;
            }
        }

        $divisor = 12 - ($validCount < 12 ? 1 : 0);
        $nilaikomposit = $validCount > 0 ? $totalNilai / $divisor : 0;
        $nilaikomposit = ($nilaikomposit - floor($nilaikomposit)) >= 0.5 ? ceil($nilaikomposit) : floor($nilaikomposit);

        // Data kesimpulan dari showfaktor
        $kesimpulan = $sanitize($showfaktor['kesimpulan'] ?? '');
        $positifstruktur = $sanitize($showfaktor['positifstruktur'] ?? '');
        $positifproses = $sanitize($showfaktor['positifproses'] ?? '');
        $positifhasil = $sanitize($showfaktor['positifhasil'] ?? '');
        $negatifstruktur = $sanitize($showfaktor['negatifstruktur'] ?? '');
        $negatifproses = $sanitize($showfaktor['negatifproses'] ?? '');
        $negatifhasil = $sanitize($showfaktor['negatifhasil'] ?? '');

        // Gabungkan positif dan negatif
        $f1600 = trim("{$positifstruktur} {$positifproses} {$positifhasil}");
        $f1700 = trim("{$negatifstruktur} {$negatifproses} {$negatifhasil}");

        // Build output
        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|1000|{$modalinti}|{$kategori}||0\r\n";
        $output .= "D01|F0100|{$nfaktor1}\r\n";
        $output .= "D01|F0200|{$nfaktor2}\r\n";
        $output .= "D01|F0300|{$nfaktor3}\r\n";
        $output .= "D01|F0400|{$nfaktor4}\r\n";
        $output .= "D01|F0500|{$nfaktor5}\r\n";
        $output .= "D01|F0600|{$nfaktor6}\r\n";
        $output .= "D01|F0700|{$nfaktor7}\r\n";
        $output .= "D01|F0800|{$nfaktor8}\r\n";
        $output .= "D01|F0900|{$nfaktor9}\r\n";
        $output .= "D01|F1000|{$nfaktor10}\r\n";
        $output .= "D01|F1100|{$nfaktor11}\r\n";
        $output .= "D01|F1200|{$nfaktor12}\r\n";
        $output .= "D01|F1300|{$nilaikomposit}\r\n";
        $output .= "D01|F1400|\r\n";
        $output .= "D01|F1500|{$kesimpulan}\r\n";
        $output .= "D01|F1600|{$f1600}\r\n";
        $output .= "D01|F1700|{$f1700}\r\n";

        $filename = "TKSBPRKS-1000-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }

}
