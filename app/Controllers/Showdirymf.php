<?php

namespace App\Controllers;

use App\Libraries\SemesterSummaryController;

/**
 * Laporan Pokok Pelaksanaan Tugas Anggota Direksi yang Membawahkan Fungsi Kepatuhan — halaman ringkasan.
 *
 * Fitur dasar (status 6 bagian, lembar persetujuan, unggah/lihat/hapus lembar pengesahan bertanda tangan)
 * dari App\Libraries\SemesterSummaryController. Di sini: konfigurasi + cetak Lembar Pengesahan (FPDF).
 * Kolom & folder file (showdirymf.pdfdir_filename, uploads/pdf_dirymf) tetap — dipakai LaporanPdfGcg & ZIP.
 */
class Showdirymf extends SemesterSummaryController
{
    public const JUDUL = 'Laporan Pokok Pelaksanaan Tugas Anggota Direksi yang Membawahkan Fungsi Kepatuhan';
    public const KICKER = 'Laporan Semesteran · Direktur yang Membawahkan Fungsi Kepatuhan';

    /** Navigasi antar bagian (dipakai halaman bagian). */
    public const NAV = ['Formasisdm' => '1. Formasi SDM', 'Ketentuanbpr' => '2. Ketentuan', 'Sosialisasipelatihan' => '3. Sosialisasi',
        'Budayakepatuhan' => '4. Budaya', 'Penyimpangankepatuhan' => '5. Penyimpangan', 'Komitmenotoritas' => '6. Komitmen',
        'Showdirymf' => 'Ringkasan'];

    public const SECTIONS = [
        'formasi' => ['Formasi Sumber Daya Manusia', 'Formasisdm', 'formasisdm', false],
        'ketentuan' => ['Ketentuan Internal yang Dimiliki BPR', 'Ketentuanbpr', 'ketentuanbpr'],
        'sosialisasi' => ['Pelaksanaan Sosialisasi dan Pelatihan Berkelanjutan', 'Sosialisasipelatihan', 'sosialisasipelatihan'],
        'budaya' => ['Penerapan Budaya Kepatuhan dan Fungsi Kepatuhan', 'Budayakepatuhan', 'budayakepatuhan'],
        'penyimpangan' => ['Penyimpangan terhadap Kepatuhan Peraturan Perundang-undangan', 'Penyimpangankepatuhan', 'penyimpangankepatuhan'],
        'komitmen' => ['Pelaksanaan Pemenuhan Komitmen terhadap Otoritas', 'Komitmenotoritas', 'komitmenotoritas'],
    ];

    public const SUMMARY_TABLE = 'showdirymf';
    public const FILE_COL = 'pdfdir_filename';
    public const FILE_DIR = 'pdf_dirymf';
    public const FILE_TEXT = [
        'Lembar Pengesahan Bertanda Tangan',
        'Cetak Lembar Pengesahan, tandatangani, lalu unggah hasil pindaiannya (PDF). Dokumen ini digabung pada laporan PDF dan ZIP Laporan Semesteran.',
        'Lembar-Pengesahan-DIRYMF.pdf',
        'Lembar pengesahan bertanda tangan (PDF)',
    ];
    public const PRINT_URL = 'LaporanPdfGcg/LaporanDirekturYangMembawahiFungsiKepatuhan';

    public const TTD = [
        'direktur' => ['Nama Direktur yang Membawahkan Fungsi Kepatuhan', 'text', 150],
        'tanggal' => ['Tanggal pelaporan', 'date', 10],
        'lokasi' => ['Lokasi', 'text', 100],
    ];
    public const TTD_PRINT_URL = 'Showdirymf/cetakPengesahan';

    /** direktur & lokasi NOT NULL tanpa default; tanggal default CURRENT_TIMESTAMP. */
    protected const EXTRA_DEFAULTS = ['direktur' => '', 'lokasi' => ''];

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
        if ($r = $this->guardPage()) {
            return $r;
        }
        $kodebpr = (string) $this->userKodebpr;

        $showfaktor = $this->summaryRow() ?? [];

        $bprData = $this->infobprModel->getBprByKode($kodebpr);
        $periodeDetail = $this->periode;

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
}
