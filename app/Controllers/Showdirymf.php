<?php

namespace App\Controllers;

use App\Models\M_periodesemesteran;
use CodeIgniter\Controller;
use App\Models\M_infobpr;
use App\Models\M_user;
use App\Models\M_showdirymf;
use App\Models\M_showprofilresiko;
use App\Models\M_formasisdm;
use App\Models\M_ketentuanbpr;
use App\Models\M_sosialisasipelatihan;
use App\Models\M_budayakepatuhan;
use App\Models\M_penyimpangankepatuhan;
use App\Models\M_komitmenotoritas;
use Myth\Auth\Config\Services as AuthServices;
use FPDF;

class Showdirymf extends Controller
{
    protected $showdirymfModel;
    protected $showprofilrisikoModel;
    protected $infobprModel;
    protected $periodeModel;
    protected $userModel;
    protected $session;
    protected $auth;
    protected $userKodebpr;
    protected $formasisdmModel;
    protected $ketentuanbprModel;
    protected $sosialisasipelatihanModel;
    protected $budayakepatuhanModel;
    protected $penyimpangankepatuhanModel;
    protected $komitmenotoritasModel;

    protected $userInGroupPE;
    protected $userInGroupAdmin;
    protected $userInGroupDekom;
    protected $userInGroupDireksi;

    public function __construct()
    {
        $this->infobprModel = new M_infobpr();
        $this->periodeModel = new M_periodesemesteran();
        $this->userModel = new M_user();
        $this->showdirymfModel = new M_showdirymf();
        $this->showprofilrisikoModel = new M_showprofilresiko();
        $this->formasisdmModel = new M_formasisdm();
        $this->ketentuanbprModel = new M_ketentuanbpr();
        $this->sosialisasipelatihanModel = new M_sosialisasipelatihan();
        $this->budayakepatuhanModel = new M_budayakepatuhan();
        $this->penyimpangankepatuhanModel = new M_penyimpangankepatuhan();
        $this->komitmenotoritasModel = new M_komitmenotoritas();
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
            return redirect()->to('/periodesemesteran')
                ->with('error', 'Silakan pilih periode aktif terlebih dahulu.');
        }

        $periodeId = session('active_periode');
        $kodebpr = $this->userKodebpr;

        if (!$kodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid.');
        }

        $db = \Config\Database::connect();
        $where = ['kodebpr' => $kodebpr, 'periode_id' => $periodeId];

        [
            $formasisdmRow,
            $ketentuanbprRow,
            $sosialisasipelatihanRow,
            $budayakepatuhanRow,
            $penyimpanganRow,
            $komitmenRow
        ] = [
            $this->formasisdmModel->select('accdir2')->where($where)->first(),
            $this->ketentuanbprModel->select('accdir2')->where($where)->first(),
            $this->sosialisasipelatihanModel->select('accdir2')->where($where)->first(),
            $this->budayakepatuhanModel->select('accdir2')->where($where)->first(),
            $this->penyimpangankepatuhanModel->select('accdir2')->where($where)->first(),
            $this->komitmenotoritasModel->select('accdir2')->where($where)->first(),
        ];

        $showfaktor = $this->showdirymfModel
            ->select('id, pdfdir_filename, direktur, tanggal, lokasi, periode_id, kodebpr')
            ->where($where)
            ->first();

        $periodeDetail = $this->periodeModel->find($periodeId);
        $bprData = $this->infobprModel->getBprByKode($kodebpr);

        if (empty($showfaktor)) {
            $this->showdirymfModel->simpanShowFaktor([
                'kodebpr' => $kodebpr,
                'periode_id' => $periodeId,
                'semester' => $periodeDetail['semester'] ?? 'N/A',
                'tahun' => $periodeDetail['tahun'] ?? 'N/A',
            ]);

            $showfaktor = $this->showdirymfModel
                ->select('id, pdfdir_filename, periode_id, kodebpr')
                ->where($where)
                ->first();
        }

        $factors = [
            [
                'name' => 'Formasi Sumber Daya Manusia',
                'link' => base_url('Formasisdm'),
                'is_approved' => $formasisdmRow['accdir2'] ?? 0,
            ],
            [
                'name' => 'Ketentuan internal yang dimiliki BPR',
                'link' => base_url('Ketentuanbpr'),
                'is_approved' => $ketentuanbprRow['accdir2'] ?? 0,
            ],
            [
                'name' => 'Pelaksanaan Sosialisasi dan Pelatihan Berkelanjutan',
                'link' => base_url('Sosialisasipelatihan'),
                'is_approved' => $sosialisasipelatihanRow['accdir2'] ?? 0,
            ],
            [
                'name' => 'Penerapan Budaya Kepatuhan dan Fungsi Kepatuhan',
                'link' => base_url('Budayakepatuhan'),
                'is_approved' => $budayakepatuhanRow['accdir2'] ?? 0,
            ],
            [
                'name' => 'Penyimpangan Terhadap Kepatuhan Peraturan Perundang-undangan',
                'link' => base_url('Penyimpangankepatuhan'),
                'is_approved' => $penyimpanganRow['accdir2'] ?? 0,
            ],
            [
                'name' => 'Pelaksanaan Pemenuhan Komitmen Terhadap Otoritas',
                'link' => base_url('Komitmenotoritas'),
                'is_approved' => $komitmenRow['accdir2'] ?? 0,
            ],
        ];

        $totalNilai = 0;
        $validCount = 0;
        $validFactors = 12;

        foreach ($factors as $factor) {
            for ($n = 4; $n <= 12; $n++) {
                $key = $n === 4 ? 'nfaktor4' : "nfaktor{$n}";
                if (isset($factor[$key]) && is_numeric($factor[$key]) && $factor[$key] != 0 && $factor[$key] !== 'N/A') {
                    $totalNilai += $factor[$key];
                    $validCount++;
                    break;
                }
            }
            if (isset($factor['nfaktor']) && is_numeric($factor['nfaktor']) && $factor['nfaktor'] !== 'N/A') {
                $totalNilai += $factor['nfaktor'];
                $validCount++;
            }
        }

        $nilaikomposit = $validCount > 0
            ? round($totalNilai / $validFactors)
            : 0;

        $data = [
            'judul' => 'Laporan Pokok Pelaksanaan Tugas Anggota <br>Direksi yang Membawahkan Fungsi Kepatuhan',
            'namabpr' => $bprData['namabpr'] ?? 'Nama BPR Tidak Ditemukan',
            'periode_semester' => $periodeDetail['semester'] ?? 'N/A',
            'periode_tahun' => $periodeDetail['tahun'] ?? 'N/A',
            'factors' => $factors,
            'showfaktor' => $showfaktor,
            // 'allShowFaktor' removed – was fetched (findAll!) but never used in view
        ];

        return view('templates/v_header', $data)
            . view('templates/v_sidebar')
            . view('templates/v_topbar')
            . view('Laporansemester/Dirymf/showdirymf', $data)
            . view('templates/v_footer');
    }

    public function LaporanDirekturYMFKepatuhan()
    {
        if (!$this->auth->check()) {
            return redirect()->to(session('redirect_url') ?? '/login');
        }

        if (!session('active_periode')) {
            return redirect()->to('/periodesemesteran')
                ->with('error', 'Silakan pilih periode aktif terlebih dahulu.');
        }

        $periodeId = session('active_periode');
        $kodebpr = $this->userKodebpr;

        if (!$kodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid.');
        }

        $where = ['kodebpr' => $kodebpr, 'periode_id' => $periodeId];

        [
            $formasisdmRow,
            $ketentuanbprRow,
            $sosialisasipelatihanRow,
            $budayakepatuhanRow,
            $penyimpanganRow,
            $komitmenRow
        ] = [
            $this->formasisdmModel->select('accdir2')->where($where)->first(),
            $this->ketentuanbprModel->select('accdir2')->where($where)->first(),
            $this->sosialisasipelatihanModel->select('accdir2')->where($where)->first(),
            $this->budayakepatuhanModel->select('accdir2')->where($where)->first(),
            $this->penyimpangankepatuhanModel->select('accdir2')->where($where)->first(),
            $this->komitmenotoritasModel->select('accdir2')->where($where)->first(),
        ];

        $periodeDetail = $this->periodeModel->find($periodeId);
        $bprData = $this->infobprModel->getBprByKode($kodebpr);

        $factors = [
            ['name' => 'Formasi Sumber Daya Manusia', 'is_approved' => $formasisdmRow['accdir2'] ?? 0],
            ['name' => 'Ketentuan internal yang dimiliki BPR', 'is_approved' => $ketentuanbprRow['accdir2'] ?? 0],
            ['name' => 'Pelaksanaan Sosialisasi dan Pelatihan Berkelanjutan', 'is_approved' => $sosialisasipelatihanRow['accdir2'] ?? 0],
            ['name' => 'Penerapan Budaya Kepatuhan dan Fungsi Kepatuhan', 'is_approved' => $budayakepatuhanRow['accdir2'] ?? 0],
            ['name' => 'Penyimpangan Terhadap Kepatuhan Peraturan Perundang-undangan', 'is_approved' => $penyimpanganRow['accdir2'] ?? 0],
            ['name' => 'Pelaksanaan Pemenuhan Komitmen Terhadap Otoritas', 'is_approved' => $komitmenRow['accdir2'] ?? 0],
        ];

        $namabpr = $bprData['namabpr'] ?? 'Nama BPR Tidak Ditemukan';
        $semester = $periodeDetail['semester'] ?? 'N/A';
        $semNum = strtolower($semester) === 'ganjil' ? '1' : (strtolower($semester) === 'genap' ? '2' : $semester);
        $tahun = $periodeDetail['tahun'] ?? 'N/A';

        $approvedCount = count(array_filter($factors, fn($f) => (int) $f['is_approved'] === 1));

        $pdf = new \FPDF('P', 'mm', 'A4');
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();

        // Header
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->MultiCell(0, 6, 'LAPORAN POKOK PELAKSANAAN TUGAS ANGGOTA DIREKSI', 0, 'C');
        $pdf->MultiCell(0, 6, 'YANG MEMBAWAHKAN FUNGSI KEPATUHAN', 0, 'C');
        $pdf->Ln(2);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 5, 'Nama BPR : ' . $namabpr, 0, 1, 'C');
        $pdf->Cell(0, 5, 'Periode  : Semester ' . $semNum . ' Tahun ' . $tahun, 0, 1, 'C');
        $pdf->Ln(6);

        // Table header
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(31, 42, 168);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(10, 8, 'No', 1, 0, 'C', true);
        $pdf->Cell(115, 8, 'Faktor', 1, 0, 'C', true);
        $pdf->Cell(50, 8, 'Status Persetujuan', 1, 1, 'C', true);

        // Table body
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(0, 0, 0);
        $lineHeight = 6;

        foreach ($factors as $i => $f) {
            $approved = (int) $f['is_approved'] === 1;
            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $charsPerLine = 60; // estimasi kasar untuk font Arial 9pt di lebar 115mm
            $lines = max(1, ceil(mb_strlen($f['name']) / $charsPerLine));
            $rowHeight = $lines * $lineHeight;

            $fill = $approved ? [225, 245, 235] : [254, 226, 226];
            $pdf->SetFillColor($fill[0], $fill[1], $fill[2]);

            $pdf->Cell(10, $rowHeight, (string) ($i + 1), 1, 0, 'C', true);
            $pdf->SetXY($x + 10, $y);
            $pdf->MultiCell(115, $lineHeight, $f['name'], 1, 'L', true);
            $pdf->SetXY($x + 10 + 115, $y);
            $pdf->Cell(50, $rowHeight, $approved ? 'Telah Disetujui' : 'Belum Disetujui', 1, 0, 'C', true);
            $pdf->SetXY($x, $y + $rowHeight);
        }

        $pdf->Ln(6);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(0, 5, 'Ringkasan: ' . $approvedCount . ' dari ' . count($factors) . ' faktor telah disetujui.', 0, 1, 'L');

        $pdf->Ln(6);
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->Cell(0, 5, 'Dicetak pada: ' . date('d/m/Y H:i') . ' WIB', 0, 1, 'R');

        $filenameSafe = preg_replace('/[^A-Za-z0-9_-]/', '_', $namabpr);
        $filename = "Laporan-DIRYMF-{$filenameSafe}-S{$semNum}-{$tahun}.pdf";

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->setBody($pdf->Output('S'));
    }

    public function exporttxtfaktor10()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        $faktor = $this->showdirymfModel
            ->select('tanggal')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $periodeDetail = $this->periodeModel->find($periodeId);

        $modalinti = $periodeDetail['modalinti'];
        $kategori = $periodeDetail['kategori'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];

        $tanggal = new \DateTime($faktor['tanggal']);
        $titleDate = $tanggal->format('Ymd');
        $exportDate = $tanggal->format('Y-m-d');

        $data_faktor = $this->showdirymfModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'] ?? '';
            $kodejenis = $infobpr['kodejenis'] ?? '';
        }

        $kodeMap = [1 => 'S1001', 2 => 'P1001', 3 => 'P1002', 4 => 'H1001', 5 => 'H1002'];

        $indexedData = [];
        foreach ($data_faktor as $row) {
            if (isset($row['faktor10id'])) {
                $indexedData[$row['faktor10id']] = $row;
            }
        }

        $sanitize = static function (string $text): string {
            $text = str_replace(["\r", "\n"], ' ', $text);
            $text = str_replace(
                ["\u{201C}", "\u{201D}", "\u{2018}", "\u{2019}", '%', '|'],
                ['"', '"', "'", "'", ' persen ', ' '],
                $text
            );
            $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
            return trim(preg_replace('/\s+/', ' ', $text));
        };

        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|1010|{$modalinti}|{$kategori}||0\r\n";
        $output .= "D01|S1000|\r\n";

        $keterangan = isset($indexedData[1]) ? $sanitize($indexedData[1]['keterangan'] ?? '-') : '';
        $output .= "D01|S1001|{$keterangan}\r\n";
        $output .= "D01|P1000|\r\n";

        for ($id = 2; $id <= 3; $id++) {
            $keterangan = isset($indexedData[$id]) ? $sanitize($indexedData[$id]['keterangan'] ?? '-') : '';
            $output .= "D01|{$kodeMap[$id]}|{$keterangan}\r\n";
        }

        $output .= "D01|H1000|\r\n";

        for ($id = 4; $id <= 5; $id++) {
            $keterangan = isset($indexedData[$id]) ? $sanitize($indexedData[$id]['keterangan'] ?? '-') : '';
            $output .= "D01|{$kodeMap[$id]}|{$keterangan}\r\n";
        }

        $filename = "TKSBPRKS-1010-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }

    public function updatettd()
    {
        if (!$this->auth->check()) {
            return redirect()->to('/login');
        }

        if (!session('active_periode')) {
            return redirect()->back()->with('error', 'Silakan pilih periode aktif terlebih dahulu.');
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        if (!$kodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid.');
        }

        $rules = [
            'direktur' => 'permit_empty|string|max_length[150]',
            'tanggal' => 'permit_empty|string|max_length[20]',
            'lokasi' => 'permit_empty|string|max_length[100]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        $showfaktor = $this->showdirymfModel
            ->select('id')
            ->where(['kodebpr' => $kodebpr, 'periode_id' => $periodeId])
            ->first();

        if (empty($showfaktor)) {
            return redirect()->back()->with('error', 'Data belum ada. Silakan buka ulang halaman ini.');
        }

        $this->showdirymfModel->update($showfaktor['id'], [
            'direktur' => $this->request->getPost('direktur'),
            'tanggal' => $this->request->getPost('tanggal'),
            'lokasi' => $this->request->getPost('lokasi'),
        ]);

        return redirect()->back()->with('message', 'Lembar Persetujuan berhasil disimpan.');
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
            return redirect()->to('/periodesemesteran')
                ->with('error', 'Silakan pilih periode aktif terlebih dahulu.');
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        if (!$kodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid.');
        }

        $showfaktor = $this->showdirymfModel
            ->select('direktur, tanggal, lokasi')
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

        $direktur = $showfaktor['direktur'] ?? '';
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
                $dt = new \DateTime($tanggalRaw);
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
        $logoSize = 25; // NOTE: sesuaikan bila ingin logo lebih besar/kecil

        // NOTE: sesuaikan path ini bila struktur folder upload logo berbeda
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

        // garis pemisah mengikuti sisi yang lebih tinggi (logo atau teks), bukan jarak tetap
        $pdf->SetY(max($textBottom, $logoBottom) + 3);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(5);

        // ===== JUDUL =====
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->MultiCell(
            0,
            6,
            "LEMBAR PERSETUJUAN DAN PENANDATANGANAN\nLAPORAN POKOK PELAKSANAAN TUGAS ANGGOTA DIREKSI\nYANG MEMBAWAHKAN FUNGSI KEPATUHAN",
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
            "Laporan Pokok Pelaksanaan Tugas Anggota Direksi\nyang Membawahkan Fungsi Kepatuhan\n{$namabpr} Semester {$semNum} Tahun {$tahun}",
            0,
            'C'
        );
        $pdf->Ln(6);

        $pdf->SetFont('Arial', '', 10);
        $pdf->MultiCell(
            0,
            5.5,
            "Telah disusun mengacu pada ketentuan SEOJK No. 8/SEOJK.03/2025 tanggal 26 Mei 2025 tentang Penerapan Fungsi Kepatuhan bagi Bank Perekonomian Rakyat dan Bank Perekonomian Rakyat Syariah.",
            0,
            'J'
        );
        $pdf->Ln(16);

        // ===== LOKASI, TANGGAL, TANDA TANGAN =====
        $pdf->SetFont('Arial', 'B', 10);
        $lokasiTanggal = trim($lokasi . ($tanggalFormatted ? ', ' . $tanggalFormatted : ''));
        $pdf->Cell(0, 6, $lokasiTanggal, 0, 1, 'C');
        $pdf->Cell(0, 6, $namabpr, 0, 1, 'C');

        $pdf->Ln(25);

        $pdf->SetFont('Arial', 'BU', 10);
        $pdf->Cell(0, 6, $direktur, 0, 1, 'C');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 6, 'Direktur yang Membawahkan Fungsi Kepatuhan', 0, 1, 'C');

        $filenameSafe = preg_replace('/[^A-Za-z0-9_-]/', '_', $namabpr);
        $filename = "Lembar-Pengesahan-DIRYMF-{$filenameSafe}.pdf";

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->setBody($pdf->Output('S'));
    }

    public function uploadPdf()
    {
        if (!$this->auth->check()) {
            return redirect()->to('/login');
        }

        $validationRules = [
            'pdf1' => [
                'rules' => 'if_exist|uploaded[pdf1]|ext_in[pdf1,pdf]|max_size[pdf1,10240]',
                'errors' => [
                    'uploaded' => 'File PDF tidak diunggah.',
                    'ext_in' => 'File harus berekstensi .pdf.',
                    'max_size' => 'Ukuran file terlalu besar (maksimal 10MB).',
                ],
            ],
        ];

        if (!$this->validate($validationRules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        if (!$kodebpr || !$periodeId) {
            return redirect()->back()->with('error', 'Data BPR atau Periode tidak ditemukan.');
        }

        $showdirymfRecord = $this->showdirymfModel
            ->select('id, pdfdir_filename')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if (empty($showdirymfRecord)) {
            return redirect()->back()->with('error', 'Data belum ada. Harap buat terlebih dahulu.');
        }

        $pdfUploadPath = WRITEPATH . 'uploads/pdf_dirymf/';
        if (!is_dir($pdfUploadPath)) {
            mkdir($pdfUploadPath, 0775, true);
        }

        $pdf1 = $this->request->getFile('pdf1');

        if ($pdf1 && $pdf1->isValid() && !$pdf1->hasMoved()) {
            $oldPdf1 = $showdirymfRecord['pdfdir_filename'] ?? null;
            if ($oldPdf1) {
                $oldPath = $pdfUploadPath . $oldPdf1;
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            $newName = $pdf1->getRandomName();
            $pdf1->move($pdfUploadPath, $newName);

            $this->showdirymfModel->update($showdirymfRecord['id'], [
                'pdfdir_filename' => $newName,
            ]);

            return redirect()->back()->with('message', 'File PDF berhasil diunggah.');
        }

        return redirect()->back()->with('error', 'Tidak ada file yang diunggah atau file tidak valid.');
    }

    public function downloadPdf($filename)
    {
        $filename = basename($filename);
        $pdfPath = WRITEPATH . 'uploads/pdf_dirymf/' . $filename;

        if (!file_exists($pdfPath)) {
            return $this->response->setStatusCode(404)->setBody('File tidak ditemukan.');
        }

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->setHeader('Cache-Control', 'private, max-age=3600')
            ->setBody(file_get_contents($pdfPath));
    }
}