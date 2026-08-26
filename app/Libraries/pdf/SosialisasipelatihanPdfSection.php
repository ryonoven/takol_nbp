<?php

namespace App\Libraries\Pdf;

use FPDF;

/**
 * Section builder untuk data Pelaksanaan Sosialisasi dan Pelatihan Berkelanjutan
 * di dalam laporan PDF gabungan GCG.
 *
 * Mengikuti pola KetentuanbprPdfSection: halaman sendiri dengan judul laporan,
 * kartu vertikal per data (nomor biru kiri, baris Label | Value kanan, border
 * hitam), dan field yang berpotensi panjang (Topik, Uraian Kegiatan) dibuat
 * dinamis tingginya berdasarkan hasil word-wrap sesungguhnya.
 */
class SosialisasipelatihanPdfSection
{
    private static array $pihakLabel = [
        '01' => 'Internal BPR',
        '02' => 'Eksternal BPR',
        '03' => 'Kolaborasi Dengan Lembaga Lain',
    ];

    private static array $kategoriLabel = [
        '01' => 'Seluruh Pegawai',
        '02' => 'Direksi dan/atau Dewan Komisaris',
        '03' => 'Satuan Kerja atau Pejabat Eksekutif',
        '04' => 'Komite',
    ];

    // ── Layout constants (mm), area cetak diasumsikan 180mm (A4, margin 15mm kiri-kanan)
    private const TABLE_WIDTH = 180;
    private const NUM_COL_WIDTH = 10;
    private const LABEL_COL_WIDTH = 55;
    private const ROW_HEIGHT = 7;       // tinggi baris untuk field satu baris
    private const LINE_HEIGHT = 4.5;    // tinggi per baris teks untuk field multi-baris
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

        self::renderSectionHeader($pdf, 'Pelaksanaan Sosialisasi dan Pelatihan Berkelanjutan:');

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
                self::renderRecordBlock($pdf, $i + 1, $r);
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

    private static function t(string $text): string
    {
        $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
        return $converted !== false ? $converted : $text;
    }

    private static function renderTitle(FPDF $pdf, string $namabpr, string $semNum, string $tahun): void
    {
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->MultiCell(0, 6, self::t('LAPORAN PELAKSANAAN SOSIALISASI DAN PELATIHAN BERKELANJUTAN'), 0, 'C');
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
     * Menghitung jumlah baris hasil word-wrap teks pada lebar tertentu,
     * menggunakan GetStringWidth (font aktif harus sudah di-set sebelumnya).
     */
    private static function countWrappedLines(FPDF $pdf, string $text, float $width): int
    {
        $text = trim($text);
        if ($text === '') {
            return 1;
        }

        $words = preg_split('/\s+/', $text);
        $lines = 1;
        $currentLineWidth = 0;
        $spaceWidth = $pdf->GetStringWidth(' ');

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

        return $lines;
    }

    /**
     * Menggambar satu baris field dengan tinggi tetap (Cell biasa).
     */
    private static function drawSingleLineRow(FPDF $pdf, float $rowX, float $rowY, float $valueColWidth, string $label, string $value): float
    {
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

        return self::ROW_HEIGHT;
    }

    /**
     * Menggambar satu baris field dengan tinggi dinamis (MultiCell), untuk teks yang bisa panjang.
     */
    private static function drawMultiLineRow(FPDF $pdf, float $rowX, float $rowY, float $valueColWidth, string $label, string $value, float $rowHeight): float
    {
        $pdf->SetFillColor(...self::COLOR_ROW_BG);
        $pdf->Rect($rowX, $rowY, self::LABEL_COL_WIDTH + $valueColWidth, $rowHeight, 'DF');
        $pdf->Line($rowX + self::LABEL_COL_WIDTH, $rowY, $rowX + self::LABEL_COL_WIDTH, $rowY + $rowHeight);

        $pdf->SetXY($rowX + 3, $rowY);
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(...self::COLOR_LABEL);
        $pdf->Cell(self::LABEL_COL_WIDTH - 3, self::ROW_HEIGHT, self::t($label), 0, 0, 'L');

        $pdf->SetXY($rowX + self::LABEL_COL_WIDTH + 3, $rowY + 1);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetTextColor(...self::COLOR_VALUE);
        $pdf->MultiCell($valueColWidth - 6, self::LINE_HEIGHT, $value, 0, 'L');

        return $rowHeight;
    }

    private static function renderRecordBlock(FPDF $pdf, int $index, array $r): void
    {
        $valueColWidth = self::TABLE_WIDTH - self::NUM_COL_WIDTH - self::LABEL_COL_WIDTH;

        $topikTxt = self::t($r['topik'] ?? '-');
        $tglTxt = !empty($r['tanggal']) ? date('d F Y', strtotime($r['tanggal'])) : '-';
        $pihakTxt = self::t(self::$pihakLabel[$r['pihakpelaksana'] ?? ''] ?? ($r['pihakpelaksana'] ?? '-'));
        $namaPihakTxt = self::t($r['namapihak'] ?? '-');
        $kategoriTxt = self::t(self::$kategoriLabel[$r['kategoripeserta'] ?? ''] ?? ($r['kategoripeserta'] ?? '-'));
        $keteranganPesertaTxt = trim((string) ($r['keteranganpeserta'] ?? ''));
        $jumlahTxt = self::t((string) ($r['jumlahpeserta'] ?? '0')) . ' orang';
        $uraianTxt = self::t($r['uraian'] ?? '-');

        // Hitung tinggi field dinamis (Topik & Uraian) berdasarkan wrap sesungguhnya
        $pdf->SetFont('Arial', 'B', 9);
        $topikLines = self::countWrappedLines($pdf, $topikTxt, $valueColWidth - 6);
        $topikHeight = max(self::ROW_HEIGHT, $topikLines * self::LINE_HEIGHT + 2);

        $uraianLines = self::countWrappedLines($pdf, $uraianTxt, $valueColWidth - 6);
        $uraianHeight = max(self::ROW_HEIGHT, $uraianLines * self::LINE_HEIGHT + 2);

        // Baris single-line yang selalu tampil
        $singleLineFields = [
            ['Tanggal Pelaksanaan', self::t($tglTxt)],
            ['Pihak Pelaksana', $pihakTxt],
            ['Nama Pihak Pelaksana', $namaPihakTxt],
            ['Kategori Peserta', $kategoriTxt],
        ];

        // "Keterangan Peserta" hanya muncul kalau ada isinya
        $showKeteranganPeserta = $keteranganPesertaTxt !== '';
        if ($showKeteranganPeserta) {
            $singleLineFields[] = ['Keterangan Peserta', self::t($keteranganPesertaTxt)];
        }

        $singleLineFields[] = ['Jumlah Peserta', $jumlahTxt];

        $blockHeight = $topikHeight
            + (count($singleLineFields) * self::ROW_HEIGHT)
            + $uraianHeight;

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

        $rowX = $x + self::NUM_COL_WIDTH;
        $rowY = $y;

        // ── Baris "Topik Sosialisasi/Pelatihan" (multi-baris) ──
        $rowY += self::drawMultiLineRow($pdf, $rowX, $rowY, $valueColWidth, 'Topik Sosialisasi/Pelatihan', $topikTxt, $topikHeight);

        // ── Baris-baris field satu baris ──
        foreach ($singleLineFields as [$label, $value]) {
            $rowY += self::drawSingleLineRow($pdf, $rowX, $rowY, $valueColWidth, $label, $value);
        }

        // ── Baris "Uraian Kegiatan" (multi-baris) ──
        $rowY += self::drawMultiLineRow($pdf, $rowX, $rowY, $valueColWidth, 'Uraian Kegiatan', $uraianTxt, $uraianHeight);

        $pdf->SetXY($x, $y + $blockHeight + self::BLOCK_GAP);
    }

    private static function checkPageBreak(FPDF $pdf, float $neededHeight): void
    {
        if ($pdf->GetY() + $neededHeight > self::PAGE_BOTTOM_LIMIT) {
            $pdf->AddPage();
        }
    }
}