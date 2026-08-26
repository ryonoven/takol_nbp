<?php

namespace App\Libraries\Pdf;

use FPDF;

/**
 * Section builder untuk data Ketentuan Internal Yang Dimiliki BPR
 * di dalam laporan PDF gabungan GCG.
 *
 * Tampilan mengikuti pola FormasisdmPdfSection: kartu vertikal per data
 * (nomor di kolom kiri berwarna biru, baris Label | Value di kanan,
 * dengan border hitam di setiap sel). Baris "Keterangan" tingginya
 * menyesuaikan panjang teks secara dinamis karena bisa berisi paragraf.
 */
class LingkupauditPdfSection
{
    // ── Layout constants (mm), area cetak diasumsikan 180mm (A4, margin 15mm kiri-kanan)
    private const TABLE_WIDTH = 180;
    private const NUM_COL_WIDTH = 10;
    private const LABEL_COL_WIDTH = 55;
    private const ROW_HEIGHT = 7;       // tinggi baris untuk field satu baris
    private const LINE_HEIGHT = 4.5;    // tinggi per baris teks untuk field multi-baris (Keterangan)
    private const BLOCK_GAP = 3;        // jarak antar blok data
    private const PAGE_BOTTOM_LIMIT = 282; // 297 - margin bawah 15

    // Warna
    private const COLOR_BLUE = [31, 42, 168];
    private const COLOR_ROW_BG = [235, 238, 242];
    private const COLOR_LABEL = [0, 0, 0];
    private const COLOR_VALUE = [15, 23, 42];
    private const COLOR_HEADER_BG = [31, 42, 168];
    private const COLOR_BORDER = [0, 0, 0];

    public static function render(
        FPDF $pdf,
        array $rows,
        string $namabpr,
        string $semDisplay,
        string $tahun,
        bool $startNewPage = true,
        string $alamat = '',
        string $email = '',
        string $nomor = '',
        ?string $logoPath = null
    ): void {
        if ($startNewPage) {
            $pdf->AddPage();
            PdfPageHeader::render($pdf, $namabpr, $alamat, $email, $nomor, $logoPath);
        }

        $semNum = self::normalizeSemester($semDisplay);

        // ── Lingkup laporan (sama pola dengan FormasisdmPdfSection) ──
        self::renderTitle($pdf, $namabpr, $semNum, $tahun);

        // Pisahkan record "Tidak Ada Data" (is_null_record) dari data asli
        $dataRows = [];
        $isNullRecord = false;
        foreach ($rows as $r) {
            if (!empty($r['is_null_record'])) {
                $isNullRecord = true;
                continue;
            }
            $dataRows[] = $r;
        }

        self::renderSectionHeader($pdf, 'Ruang Lingkup dan Waktu Pelaksanaan Audit:');

        if ($isNullRecord) {
            $pdf->SetFont('Arial', 'I', 9);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->Cell(self::TABLE_WIDTH, 8, self::t('Tidak ada data pada periode ini.'), 0, 1, 'C');
        } elseif (empty($dataRows)) {
            $pdf->SetFont('Arial', 'I', 9);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->Cell(self::TABLE_WIDTH, 8, self::t('Belum ada data.'), 0, 1, 'C');
        } else {
            foreach ($dataRows as $i => $r) {
                self::renderRecordBlock($pdf, $i + 1, $r, $namabpr, $alamat, $email, $nomor, $logoPath);
            }
        }
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

    private static function renderTitle(FPDF $pdf, string $namabpr, string $semNum, string $tahun): void
    {
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->MultiCell(0, 6, self::t('LAPORAN RUANG LINGKUP DAN WAKTU PELAKSANAAN AUDIT'), 0, 'C');
        $pdf->Ln(1);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 5, self::t('' . $namabpr), 0, 1, 'C');
        $pdf->Cell(0, 5, self::t('Semester ' . $semNum . ' Tahun ' . $tahun), 0, 1, 'C');
        $pdf->Ln(4);
    }

    private static function t(string $text): string
    {
        $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
        return $converted !== false ? $converted : $text;
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
     * Menghitung jumlah baris hasil word-wrap teks pada lebar tertentu,
     * menggunakan GetStringWidth (font aktif harus sudah di-set sebelumnya).
     */
    private static function countWrappedLines(FPDF $pdf, string $text, float $width): int
    {
        $text = trim($text);
        if ($text === '') {
            return 1;
        }

        $paragraphs = explode("\n", str_replace("\r\n", "\n", $text));
        $totalLines = 0;
        $spaceWidth = $pdf->GetStringWidth(' ');

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);

            if ($paragraph === '') {
                $totalLines += 1; // baris kosong tetap makan 1 baris
                continue;
            }

            $words = preg_split('/\s+/', $paragraph);
            $lines = 1;
            $currentLineWidth = 0;

            foreach ($words as $word) {
                $wordWidth = $pdf->GetStringWidth($word);

                if ($currentLineWidth === 0) {
                    $currentLineWidth = $wordWidth;
                    continue;
                }

                if ($currentLineWidth + $spaceWidth + $wordWidth > $width) {
                    $lines++;
                    $currentLineWidth = $wordWidth;
                } else {
                    $currentLineWidth += $spaceWidth + $wordWidth;
                }
            }

            $totalLines += $lines;
        }

        return $totalLines;
    }

    private static function renderRecordBlock(FPDF $pdf, int $index, array $r, string $namabpr, string $alamat, string $email, string $nomor, ?string $logoPath): void
    {
        $valueColWidth = self::TABLE_WIDTH - self::NUM_COL_WIDTH - self::LABEL_COL_WIDTH;

        $tanggalberakhirTxt = !empty($r['tanggalberakhir']) ? date('d F Y', strtotime($r['tanggalberakhir'])) : '-';
        $tanggalmulaiTxt = !empty($r['tanggalmulai']) ? date('d F Y', strtotime($r['tanggalmulai'])) : '-';
        $ruanglingkupTxt = self::t($r['ruanglingkup'] ?? '-');

        // Field satu baris (diasumsikan selalu pendek)
        $singleLineFields = [
            ['Tanggal Mulai', self::t($tanggalmulaiTxt)],
            ['Tanggal Berakhir', self::t($tanggalberakhirTxt)],
        ];

        // Hitung tinggi baris "Ruang Lingkup Audit" & "Keterangan" (multi-baris)
        // berdasarkan wrap sesungguhnya, karena keduanya bisa berisi teks panjang
        $pdf->SetFont('Arial', 'B', 9);
        $ruanglingkupLines = self::countWrappedLines($pdf, $ruanglingkupTxt, $valueColWidth - 6);
        $ruanglingkupHeight = max(self::ROW_HEIGHT, $ruanglingkupLines * self::LINE_HEIGHT + 2);

        $blockHeight = $ruanglingkupHeight
            + (count($singleLineFields) * self::ROW_HEIGHT);


        self::checkPageBreak($pdf, $blockHeight + self::BLOCK_GAP, $namabpr, $alamat, $email, $nomor, $logoPath);

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

        $rowX = $x + self::NUM_COL_WIDTH;
        $rowY = $y;

        $pdf->SetFillColor(...self::COLOR_ROW_BG);
        $pdf->Rect($rowX, $rowY, self::LABEL_COL_WIDTH + $valueColWidth, $ruanglingkupHeight, 'DF');
        $pdf->Line($rowX + self::LABEL_COL_WIDTH, $rowY, $rowX + self::LABEL_COL_WIDTH, $rowY + $ruanglingkupHeight);

        $pdf->SetXY($rowX + 3, $rowY);
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(...self::COLOR_LABEL);
        $pdf->Cell(self::LABEL_COL_WIDTH - 3, self::ROW_HEIGHT, self::t('Ruang Lingkup'), 0, 0, 'L');

        $pdf->SetXY($rowX + self::LABEL_COL_WIDTH + 3, $rowY + 1);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetTextColor(...self::COLOR_VALUE);
        $pdf->MultiCell($valueColWidth - 6, self::LINE_HEIGHT, $ruanglingkupTxt, 0, 'L');

        $rowY += $ruanglingkupHeight;

        // ── Baris-baris field satu baris (Nomor, Tanggal) ──
        foreach ($singleLineFields as [$label, $value]) {
            $pdf->SetFillColor(...self::COLOR_ROW_BG);
            $pdf->Rect($rowX, $rowY, self::LABEL_COL_WIDTH + $valueColWidth, self::ROW_HEIGHT, 'DF');
            $pdf->Line($rowX + self::LABEL_COL_WIDTH, $rowY, $rowX + self::LABEL_COL_WIDTH, $rowY + self::ROW_HEIGHT);

            $pdf->SetXY($rowX + 3, $rowY);
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(...self::COLOR_LABEL);
            $pdf->Cell(self::LABEL_COL_WIDTH - 3, self::ROW_HEIGHT, self::t($label), 0, 0, 'L');

            $pdf->SetXY($rowX + self::LABEL_COL_WIDTH + 3, $rowY);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetTextColor(...self::COLOR_VALUE);
            $pdf->Cell($valueColWidth - 6, self::ROW_HEIGHT, $value, 0, 0, 'L');

            $rowY += self::ROW_HEIGHT;

            $pdf->SetXY($x, $y + $blockHeight + self::BLOCK_GAP);
        }
    }

    private static function checkPageBreak(
        FPDF $pdf,
        float $neededHeight,
        string $namabpr,
        string $alamat,
        string $email,
        string $nomor,
        ?string $logoPath
    ): void {
        if ($pdf->GetY() + $neededHeight > self::PAGE_BOTTOM_LIMIT) {
            $pdf->AddPage();
            PdfPageHeader::render($pdf, $namabpr, $alamat, $email, $nomor, $logoPath);
        }
    }
}