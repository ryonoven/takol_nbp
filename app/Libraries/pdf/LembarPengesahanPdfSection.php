<?php

namespace App\Libraries\Pdf;

use setasign\Fpdi\Fpdi;

/**
 * Menempelkan file PDF Lembar Persetujuan yang SUDAH diunggah dan
 * ditandatangani oleh user (kolom pdfdir_filename di tabel showdirymf)
 * ke dalam dokumen laporan gabungan, apa adanya — bukan menggambar ulang
 * kontennya dengan FPDF.
 *
 * $pdf HARUS berupa instance \setasign\Fpdi\Fpdi (bukan \FPDF biasa),
 * karena hanya Fpdi yang punya method setSourceFile/importPage/useTemplate.
 */
class LembarPengesahanPdfSection
{
    /**
     * @return bool true jika berhasil menempelkan minimal 1 halaman, false jika file tidak ada/gagal dibaca
     */
    public static function render(Fpdi $pdf, string $pdfPath): bool
    {
        if (!$pdfPath || !file_exists($pdfPath)) {
            return false;
        }

        try {
            $pageCount = $pdf->setSourceFile($pdfPath);
        } catch (\Throwable $e) {
            log_message('error', '[LembarPengesahanPdfSection] Gagal membaca PDF: ' . $e->getMessage());
            return false;
        }

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $tplIdx = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($tplIdx);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($tplIdx);
        }

        return true;
    }
}