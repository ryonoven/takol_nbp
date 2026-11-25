<?php

namespace App\Libraries;

use setasign\Fpdi\Fpdi;

/**
 * Gabung beberapa PDF menjadi satu (FPDI).
 *
 * FPDI versi gratis tidak bisa membaca PDF ber-"compressed xref" (umumnya PDF 1.5+ hasil scan / Word).
 * File seperti itu dinormalisasi dulu ke PDF 1.4 via Ghostscript (PdfNormalizer) bila tersedia.
 * Pemeriksaan dilakukan SAAT UPLOAD (prepare), sehingga penggabungan nanti selalu berhasil.
 */
class PdfMerger
{
    /** Jumlah halaman bila FPDI bisa membaca file, atau null. */
    public static function pageCount(string $path): ?int
    {
        try {
            $n = (new Fpdi())->setSourceFile($path);
            return $n > 0 ? $n : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Pastikan file dapat digabung.
     * @return string|null path file siap pakai (asli, atau hasil normalisasi di folder temp), null bila tidak bisa dibaca.
     */
    public static function prepare(string $path): ?string
    {
        if (self::pageCount($path) !== null) {
            return $path;
        }
        $normalized = (new PdfNormalizer())->normalizeToV14($path);
        if ($normalized && self::pageCount($normalized) !== null) {
            return $normalized;
        }
        if ($normalized) {
            @unlink($normalized);
        }
        return null;
    }

    /**
     * Gabung $paths (urut) ke $outPath. Halaman mengikuti ukuran & orientasi aslinya.
     * @throws \RuntimeException bila ada file yang tidak bisa dibaca.
     */
    public static function merge(array $paths, string $outPath): void
    {
        $pdf = new Fpdi();
        $pdf->SetAutoPageBreak(false);
        foreach ($paths as $path) {
            try {
                $n = $pdf->setSourceFile($path);
            } catch (\Throwable $e) {
                throw new \RuntimeException('PDF tidak dapat dibaca: ' . basename($path) . ' (' . $e->getMessage() . ')');
            }
            for ($i = 1; $i <= $n; $i++) {
                $tpl = $pdf->importPage($i);
                $size = $pdf->getTemplateSize($tpl);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($tpl);
            }
        }
        $pdf->Output('F', $outPath);
    }
}
