<?php

namespace App\Libraries\Pdf;

use FPDF;

/**
 * Section builder untuk data Formasi Sumber Daya Manusia (Formasisdm)
 * di dalam laporan PDF gabungan GCG.
 *
 * Tampilan: kartu vertikal per data (nomor di kolom kiri berwarna biru,
 * baris Label | Value di kanan, dengan border hitam di setiap sel) —
 * meniru gaya card pada UI aplikasi.
 *
 * Class ini HANYA menambahkan konten ke instance FPDF yang sudah ada,
 * sehingga bisa dipakai berulang oleh controller manapun yang sedang
 * menyusun PDF gabungan multi-modul (mis. LaporanPdfGcg::cetak()).
 */
class FormasisdmPdfSection
{
    private static array $jabatanLabel = [
        '110' => 'Direktur Utama',
        '120' => 'Direktur yang membawahkan fungsi kepatuhan',
        '130' => 'Direktur',
        '210' => 'Komisaris Utama',
        '220' => 'Komisaris',
        '230' => 'Komisaris Independen',
        '310' => 'Komite Audit',
        '320' => 'Komite Pemantau Resiko',
        '330' => 'Komite Remunerasi dan Nominasi',
        '340' => 'Komite Manajemen Resiko',
        '350' => 'Komite Lainnya',
        '410' => 'Pejabat Eksekutif Kepatuhan',
        '420' => 'Pejabat Eksekutif Manajemen Resiko',
        '430' => 'Pejabat Eksekutif Audit Intern',
        '440' => 'Pejabat Eksekutif APU PPT',
        '450' => 'Pejabat Eksekutif Lainnya',
    ];

    private static array $statusLabel = [
        '01' => 'Pengangkatan Baru',
        '02' => 'Pengangkatan Kembali',
        '03' => 'Perubahan Jabatan',
        '04' => 'Tidak Berubah',
    ];

    private static array $pendidikanLabel = [
        '01' => 'S3',
        '02' => 'S2',
        '03' => 'S1',
        '04' => 'D3',
        '05' => 'SLTA',
        '99' => 'Lainnya',
    ];

    // ── Layout constants (mm), area cetak diasumsikan 180mm (A4, margin 15mm kiri-kanan)
    private const TABLE_WIDTH = 180;
    private const NUM_COL_WIDTH = 10;
    private const LABEL_COL_WIDTH = 70;
    private const ROW_HEIGHT = 7;
    private const BLOCK_GAP = 3;      // jarak antar blok data
    private const PAGE_BOTTOM_LIMIT = 282; // 297 - margin bawah 15

    // Warna
    private const COLOR_BLUE = [31, 42, 168];   // biru kolom nomor
    private const COLOR_ROW_BG = [235, 238, 242]; // abu-abu muda baris
    private const COLOR_LABEL = [0, 0, 0];    // teks label
    private const COLOR_VALUE = [15, 23, 42];     // teks value (bold, gelap)
    private const COLOR_HEADER_BG = [31, 42, 168];
    private const COLOR_BORDER = [0, 0, 0];       // border hitam

    public static function render(
        FPDF $pdf,
        array $rows,
        array $bpr,
        string $semDisplay,
        string $tahun,
        bool $startNewPage = true
    ): void {
        $namabpr = $bpr['namabpr'] ?? '-';

        if ($startNewPage) {
            $pdf->AddPage();
            \App\Libraries\Pdf\PdfPageHeader::render(
                $pdf,
                $namabpr,
                $bpr['alamat'] ?? '',
                $bpr['email'] ?? '',
                $bpr['nomor'] ?? '',
                $bpr['logoPath'] ?? null
            );
        }

        $semNum = self::normalizeSemester($semDisplay);

        self::renderTitle($pdf, $namabpr, $semNum, $tahun);
        self::renderSectionHeader($pdf, 'Formasi Sumber Daya Manusia:');

        if (empty($rows)) {
            $pdf->SetFont('Arial', 'I', 9);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->Cell(self::TABLE_WIDTH, 8, self::t('Belum ada data.'), 0, 1, 'C');
        } else {
            foreach ($rows as $i => $r) {
                self::renderRecordBlock($pdf, $i + 1, $r);
            }
        }

        self::renderRingkasan($pdf, $rows);
    }

    private static function normalizeSemester(string $semDisplay): string
    {
        $s = strtolower(trim($semDisplay));
        if ($s === 'ganjil' || $s === '1')
            return '1';
        if ($s === 'genap' || $s === '2')
            return '2';
        return $semDisplay;
    }

    /**
     * FPDF core font (Arial) cuma dukung ISO-8859-1, jadi karakter UTF-8
     * (mis. nama dengan huruf beraksen) perlu dikonversi supaya tidak
     * tampil sebagai karakter aneh di PDF.
     */
    private static function t(string $text): string
    {
        $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
        return $converted !== false ? $converted : $text;
    }

    private static function renderTitle(FPDF $pdf, string $namabpr, string $semNum, string $tahun): void
    {
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->MultiCell(0, 6, self::t('LAPORAN FORMASI SUMBER DAYA MANUSIA'), 0, 'C');
        $pdf->Ln(1);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 5, self::t('' . $namabpr), 0, 1, 'C');
        $pdf->Cell(0, 5, self::t('Semester ' . $semNum . ' Tahun ' . $tahun), 0, 1, 'C');
        $pdf->Ln(4);
    }

    private static function renderSectionHeader(FPDF $pdf, string $title): void
    {
        $pdf->SetDrawColor(...self::COLOR_BORDER);
        $pdf->SetFillColor(...self::COLOR_HEADER_BG);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(self::TABLE_WIDTH, 8, '  ' . self::t($title), 1, 1, 'L', true);
        $pdf->Ln(1.5);
    }

    /**
     * Satu blok = satu data (satu orang), berisi beberapa baris Label | Value,
     * dengan kolom nomor biru di sebelah kiri yang membentang sepanjang blok.
     * Setiap sel (kolom nomor & tiap baris) diberi border hitam.
     */
    private static function renderRecordBlock(FPDF $pdf, int $index, array $r): void
    {
        $fields = [
            ['Nama', $r['nama'] ?? '-'],
            ['NIK', $r['nik'] ?? '-'],
            ['Jabatan', self::$jabatanLabel[$r['jabatan'] ?? ''] ?? ($r['jabatan'] ?? '-')],
            ['Status Kepegawaian', self::$statusLabel[$r['status'] ?? ''] ?? ($r['status'] ?? '-')],
            ['Tingkat Pendidikan', self::$pendidikanLabel[$r['tingkatpendidikan'] ?? ''] ?? ($r['tingkatpendidikan'] ?? '-')],
            ['Tanggal Pengangkatan', !empty($r['masajabatan']) ? date('d F Y', strtotime($r['masajabatan'])) : '-'],
        ];

        $blockHeight = count($fields) * self::ROW_HEIGHT;

        self::checkPageBreak($pdf, $blockHeight + self::BLOCK_GAP);

        $x = $pdf->GetX();
        $y = $pdf->GetY();

        $pdf->SetDrawColor(...self::COLOR_BORDER);
        $pdf->SetLineWidth(0.2);

        // Kolom nomor (biru), membentang sepanjang tinggi blok, dengan border hitam
        $pdf->SetFillColor(...self::COLOR_BLUE);
        $pdf->Rect($x, $y, self::NUM_COL_WIDTH, $blockHeight, 'DF');

        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetTextColor(255, 255, 255);
        $numY = $y + ($blockHeight / 2) - 4;
        $pdf->SetXY($x, $numY);
        $pdf->Cell(self::NUM_COL_WIDTH, 8, $index . '.', 0, 0, 'C');

        // Baris-baris Label | Value, masing-masing dengan border hitam
        $rowX = $x + self::NUM_COL_WIDTH;
        $valueColWidth = self::TABLE_WIDTH - self::NUM_COL_WIDTH - self::LABEL_COL_WIDTH;
        $rowY = $y;

        foreach ($fields as [$label, $value]) {
            $pdf->SetFillColor(...self::COLOR_ROW_BG);
            $pdf->Rect($rowX, $rowY, self::LABEL_COL_WIDTH + $valueColWidth, self::ROW_HEIGHT, 'DF');
            // garis pemisah vertikal antara kolom label dan value
            $pdf->Line($rowX + self::LABEL_COL_WIDTH, $rowY, $rowX + self::LABEL_COL_WIDTH, $rowY + self::ROW_HEIGHT);

            $pdf->SetXY($rowX + 3, $rowY);
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(...self::COLOR_LABEL);
            $pdf->Cell(self::LABEL_COL_WIDTH - 3, self::ROW_HEIGHT, self::t($label), 0, 0, 'L');

            $pdf->SetXY($rowX + self::LABEL_COL_WIDTH + 3, $rowY);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetTextColor(...self::COLOR_VALUE);
            $pdf->Cell($valueColWidth - 6, self::ROW_HEIGHT, self::t((string) $value), 0, 0, 'L');

            $rowY += self::ROW_HEIGHT;
        }

        $pdf->SetXY($x, $y + $blockHeight + self::BLOCK_GAP);
    }

    private static function checkPageBreak(FPDF $pdf, float $neededHeight): void
    {
        if ($pdf->GetY() + $neededHeight > self::PAGE_BOTTOM_LIMIT) {
            $pdf->AddPage();
        }
    }

    private static function renderRingkasan(FPDF $pdf, array $rows): void
    {
        $total = count($rows);
        $approved = count(array_filter($rows, static fn($r) => isset($r['accdir2']) && (int) $r['accdir2'] === 1));

        self::checkPageBreak($pdf, 8);

        $pdf->Ln(2);
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(0, 0, 0);
        // $pdf->Cell(0, 5, self::t("Ringkasan: {$approved} dari {$total} data telah disetujui."), 0, 1, 'L');

    }
}