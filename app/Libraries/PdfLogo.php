<?php

namespace App\Libraries;

/**
 * Logo BPR sebagai data URI untuk Dompdf.
 *
 * Logo asli bisa sangat besar (mis. PNG RGBA 2374x892). Dompdf memproses kanal alpha PNG
 * piksel demi piksel di PHP sehingga satu PDF bisa makan beberapa detik dan export ZIP
 * (9 PDF) melewati max_execution_time. Bila GD tersedia, logo diperkecil sekali lalu
 * disimpan di cache sebagai JPEG berlatar putih (tanpa alpha -> cepat di-render).
 * Tanpa GD: logo asli dipakai apa adanya.
 */
class PdfLogo
{
    private const MAX_W = 600;
    private const MAX_H = 240;

    /** Data URI logo dari kolom infobpr.logo; null bila tidak ada. */
    public static function fromBpr(array $bpr): ?string
    {
        if (empty($bpr['logo'])) {
            return null;
        }
        return self::dataUri(FCPATH . 'asset/img/' . basename((string) $bpr['logo']));
    }

    public static function dataUri(?string $file): ?string
    {
        if (!$file || !is_file($file)) {
            return null;
        }

        $small = self::downscaled($file);
        if ($small !== null) {
            return 'data:image/jpeg;base64,' . base64_encode($small);
        }

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mime = ['png' => 'image/png', 'gif' => 'image/gif', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg'][$ext] ?? null;
        return $mime ? 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($file)) : null;
    }

    /** JPEG kecil (cache di WRITEPATH/cache), atau null bila GD tidak tersedia / gagal. */
    private static function downscaled(string $file): ?string
    {
        if (!function_exists('imagecreatefromstring')) {
            return null;
        }

        $cache = WRITEPATH . 'cache/pdflogo_' . md5($file . '|' . filemtime($file) . '|' . self::MAX_W . 'x' . self::MAX_H) . '.jpg';
        if (is_file($cache)) {
            return (string) file_get_contents($cache);
        }

        $src = @imagecreatefromstring((string) file_get_contents($file));
        if (!$src) {
            return null;
        }
        $w = imagesx($src);
        $h = imagesy($src);
        $scale = min(1, self::MAX_W / $w, self::MAX_H / $h);
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));

        $dst = imagecreatetruecolor($nw, $nh);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255)); // latar putih menggantikan transparansi
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        ob_start();
        imagejpeg($dst, null, 90);
        $jpg = (string) ob_get_clean();
        imagedestroy($src);
        imagedestroy($dst);

        if ($jpg === '') {
            return null;
        }
        @file_put_contents($cache, $jpg);
        return $jpg;
    }
}
