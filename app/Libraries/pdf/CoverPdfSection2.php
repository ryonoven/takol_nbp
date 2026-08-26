<?php

namespace App\Libraries\Pdf;

use FPDF;

/**
 * Halaman cover laporan GCG (berdiri sendiri, terpisah dari halaman
 * konten lain). Urutan tampilan dari atas ke bawah:
 * Judul -> Nama BPR & Periode -> Logo BPR -> Alamat.
 */
class CoverPdfSection2
{
    public static function render(
        FPDF $pdf,
        string $namabpr,
        ?string $logoPath,
        string $semDisplay,
        string $tahun,
        string $alamat = ''
    ): void {
        $pdf->AddPage();

        $semNum = self::normalizeSemester($semDisplay);

        // ── Judul ──
        $pdf->SetY(50);
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->MultiCell(
            0,
            8,
            self::t("LAPORAN POKOK PELAKSANAAN TUGAS ANGGOTA DIREKSI\nYANG MEMBAWAHKAN FUNGSI KEPATUHAN"),
            0,
            'C'
        );
        $pdf->Ln(14);

        // ── Nama BPR & Periode ──
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 7, self::t($namabpr), 0, 1, 'C');
        $pdf->Ln(2);

        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 6, self::t('Semester ' . $semNum . ' Tahun ' . $tahun), 0, 1, 'C');
        $pdf->Ln(14);

        // ── Logo BPR ──
        if ($logoPath && file_exists($logoPath)) {
            $logoSize = 43;
            $x = (210 - $logoSize) / 2;
            $pdf->Image($logoPath, $x, $pdf->GetY(), $logoSize);
            $pdf->Ln($logoSize + 10);
        } else {
            $pdf->Ln(14);
        }

        // ── Alamat ──
        if ($alamat !== '') {
            $pdf->SetFont('Arial', '', 10);
            $pdf->SetTextColor(60, 60, 60);
            $pdf->MultiCell(0, 5.5, self::t($alamat), 0, 'C');
        }
    }

    private static function normalizeSemester(string $semDisplay): string
    {
        $s = strtolower(trim($semDisplay));
        if ($s === 'ganjil' || $s === '1') return '1';
        if ($s === 'genap' || $s === '2') return '2';
        return $semDisplay;
    }

    private static function t(string $text): string
    {
        $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
        return $converted !== false ? $converted : $text;
    }
}