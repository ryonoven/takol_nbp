<?php

namespace App\Libraries\Pdf;

use FPDF;

class PdfPageHeader
{
    private const MARGIN_LEFT = 15;
    private const CONTENT_WIDTH = 180;
    private const LOGO_SIZE = 28;

    public static function render(
        FPDF $pdf,
        string $namabpr,
        string $alamat,
        string $email,
        string $nomor,
        ?string $logoPath
    ): void {
        $top = $pdf->GetY();

        if ($logoPath && file_exists($logoPath)) {
            $pdf->Image($logoPath, self::MARGIN_LEFT, $top, self::LOGO_SIZE);
        }
        $logoBottom = $top + self::LOGO_SIZE - 10;

        $pdf->SetXY(self::MARGIN_LEFT, $top);
        $pdf->SetFont('Arial', 'B', 15);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(self::CONTENT_WIDTH, 5, self::t($namabpr), 0, 2, 'R');

        $pdf->SetFont('Arial', '', 8);
        if ($alamat !== '') {
            foreach (self::wrapByWordCount($alamat, 10) as $line) {
                $pdf->SetX(self::MARGIN_LEFT);
                $pdf->Cell(self::CONTENT_WIDTH, 4, self::t($line), 0, 2, 'R');
            }
        }

        $kontak = array_filter([$email, $nomor]);
        if ($kontak) {
            $pdf->SetX(self::MARGIN_LEFT);
            $pdf->Cell(self::CONTENT_WIDTH, 4, self::t(implode(' | ', $kontak)), 0, 1, 'R');
        }

        $textBottom = $pdf->GetY();
        $bottom = max($textBottom, $logoBottom) + 2;

        $pdf->SetY($bottom);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->Line(self::MARGIN_LEFT, $pdf->GetY(), self::MARGIN_LEFT + self::CONTENT_WIDTH, $pdf->GetY());
        $pdf->Ln(4);
    }

    private static function t(string $text): string
    {
        $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
        return $converted !== false ? $converted : $text;
    }

    private static function wrapByWordCount(string $text, int $maxWords): array
    {
        $words = preg_split('/\s+/', trim($text));
        $lines = [];
        foreach (array_chunk($words, $maxWords) as $chunk) {
            $lines[] = implode(' ', $chunk);
        }
        return $lines ?: [''];
    }
}