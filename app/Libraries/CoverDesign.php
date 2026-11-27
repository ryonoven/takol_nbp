<?php

namespace App\Libraries;

/**
 * Desain cover laporan Self Assessment Tata Kelola.
 *
 * Nilai kolom showfaktor.cover:
 *  - "Cover.png" … "Cover4.png"  → gambar bawaan lama (judul sudah tercetak di gambar);
 *  - "tpl:<kunci>"                → template vektor (digambar langsung di PDF, ringan & tajam);
 *  - "custom:<kodebpr>/<file>"    → desain unggahan BPR (writable/uploads/covers/<kodebpr>/), pengaturan di <file>.json.
 *
 * Tata letak (mm, A4 210×297): box = [x, y, w, anchor top|middle|bottom, align L|C|R], warna RGB,
 * logo = [x, y, maks lebar, maks tinggi, align, badge putih?], kontak = [x, y, w, align, rgb] atau null.
 */
class CoverDesign
{
    public const NAVY = [20, 24, 99];
    public const WHITE = [255, 255, 255];
    public const DIR = 'uploads/covers/';
    public const MAX_UPLOAD_KB = 5120;

    /** Opsi pengaturan desain unggahan: kunci => [label, [nilai => label]] */
    public const CUSTOM_OPTS = [
        'pos' => ['Posisi teks', ['top' => 'Atas', 'middle' => 'Tengah', 'bottom' => 'Bawah']],
        'align' => ['Perataan', ['L' => 'Kiri', 'C' => 'Tengah', 'R' => 'Kanan']],
        'tone' => ['Warna teks', ['auto' => 'Otomatis', 'light' => 'Putih', 'dark' => 'Gelap']],
        'logo' => ['Logo BPR', ['tl' => 'Kiri atas', 'tc' => 'Tengah atas', 'tr' => 'Kanan atas', 'none' => 'Tanpa logo']],
        'title' => ['Judul laporan', ['1' => 'Tampilkan', '0' => 'Sembunyikan (sudah ada di desain)']],
        'panel' => ['Latar teks', ['none' => 'Tanpa', 'light' => 'Panel terang', 'dark' => 'Panel gelap']],
    ];
    public const CUSTOM_DEFAULT = ['pos' => 'top', 'align' => 'L', 'tone' => 'auto', 'logo' => 'tr', 'title' => '1', 'panel' => 'none'];

    /** Template bawaan: kunci => [nama, latar (file gambar | null = vektor), tata letak] */
    public static function templates(): array
    {
        $W = self::WHITE;
        $N = self::NAVY;
        $logoTR = [150, 14, 44, 24, 'R', true];
        return [
            // ── Gambar bawaan lama (judul tercetak di gambar) ──
            'Cover.png' => ['Kuning Kerja', 'Cover.png', ['title' => false, 'box' => [17, 44, 150, 'top', 'L'], 'fg' => $N, 'sub' => [40, 40, 90], 'accent' => $N,
                'logo' => $logoTR, 'contact' => [12, 262, 110, 'L', $W]]],
            'Cover1.png' => ['Biru Gedung', 'Cover1.png', ['title' => false, 'box' => [17, 52, 120, 'top', 'L'], 'fg' => $W, 'sub' => [210, 225, 245], 'accent' => [125, 211, 252],
                'logo' => [158, 12, 40, 24, 'R', false], 'contact' => [8, 266, 72, 'L', $W]]],
            'Cover2.png' => ['Navy Kolase', 'Cover2.png', ['title' => false, 'box' => [17, 56, 150, 'top', 'L'], 'fg' => $W, 'sub' => [200, 215, 245], 'accent' => [96, 165, 250],
                'logo' => [150, 30, 44, 22, 'R', true], 'contact' => [17, 266, 176, 'L', $W]]],
            'Cover3.png' => ['Merah Hitam', 'Cover3.png', ['title' => false, 'box' => [17, 56, 150, 'top', 'L'], 'fg' => $W, 'sub' => [220, 220, 220], 'accent' => [239, 68, 68],
                'logo' => $logoTR, 'contact' => [110, 266, 90, 'R', $W]]],
            'Cover4.png' => ['Putih Navy', 'Cover4.png', ['title' => false, 'box' => [17, 100, 160, 'top', 'L'], 'fg' => $N, 'sub' => [71, 85, 105], 'accent' => $N,
                'logo' => [150, 14, 44, 24, 'R', false], 'contact' => [6, 268, 70, 'L', $W]]],

            // ── Template vektor baru ──
            'tpl:korporat' => ['Biru Korporat', null, ['title' => true, 'box' => [20, 70, 150, 'top', 'L'], 'fg' => $W, 'sub' => [199, 210, 254], 'accent' => [250, 204, 21],
                'logo' => [20, 20, 48, 26, 'L', true], 'contact' => [20, 268, 170, 'L', [199, 210, 254]]]],
            'tpl:minimalis' => ['Minimalis', null, ['title' => true, 'box' => [34, 148, 150, 'middle', 'L'], 'fg' => $N, 'sub' => [71, 85, 105], 'accent' => [37, 99, 235],
                'logo' => [34, 24, 50, 28, 'L', false], 'contact' => [34, 270, 160, 'L', [100, 116, 139]]]],
            'tpl:emerald' => ['Hijau Elegan', null, ['title' => true, 'box' => [25, 150, 160, 'middle', 'C'], 'fg' => $W, 'sub' => [209, 250, 229], 'accent' => [212, 175, 55],
                'logo' => [85, 40, 40, 26, 'C', true], 'contact' => [25, 268, 160, 'C', [167, 243, 208]]]],
            'tpl:klasik' => ['Klasik Emas', null, ['title' => true, 'box' => [30, 150, 150, 'middle', 'C'], 'fg' => $N, 'sub' => [92, 74, 30], 'accent' => [180, 140, 40],
                'logo' => [80, 42, 50, 28, 'C', false], 'contact' => [30, 262, 150, 'C', [92, 74, 30]]]],
            'tpl:geometris' => ['Geometris', null, ['title' => true, 'box' => [20, 56, 140, 'top', 'L'], 'fg' => $N, 'sub' => [51, 65, 85], 'accent' => [14, 165, 233],
                'logo' => [146, 18, 44, 24, 'R', false], 'contact' => [20, 200, 110, 'L', [71, 85, 105]]]],
            'tpl:marun' => ['Merah Marun', null, ['title' => true, 'box' => [20, 62, 170, 'top', 'L'], 'fg' => $W, 'sub' => [254, 226, 226], 'accent' => [253, 186, 116],
                'logo' => [146, 18, 44, 24, 'R', true], 'contact' => [20, 270, 170, 'L', [100, 30, 40]]]],
            'tpl:teal' => ['Teal Modern', null, ['title' => true, 'box' => [20, 250, 170, 'bottom', 'L'], 'fg' => $W, 'sub' => [204, 251, 241], 'accent' => [253, 224, 71],
                'logo' => [20, 20, 48, 26, 'L', true], 'contact' => [20, 278, 170, 'L', [204, 251, 241]]]],
            'tpl:split' => ['Abu Split', null, ['title' => true, 'box' => [14, 150, 72, 'middle', 'L'], 'fg' => $W, 'sub' => [203, 213, 225], 'accent' => [56, 189, 248],
                'logo' => [14, 18, 56, 24, 'L', true], 'contact' => [14, 266, 72, 'L', [203, 213, 225]]]],
        ];
    }

    /** Daftar pilihan template untuk halaman: [nilai => nama]. */
    public static function choices(): array
    {
        return array_map(static fn($t) => $t[0], self::templates());
    }

    public static function thumbUrl(string $key): string
    {
        return base_url('assets/img/covers/thumb-' . preg_replace('/[^a-z0-9]+/i', '-', strtolower(str_replace('tpl:', '', $key))) . '.jpg');
    }

    // ───────────────────────── desain unggahan ─────────────────────────

    public static function customDir(string $kodebpr): string
    {
        return WRITEPATH . self::DIR . preg_replace('/[^A-Za-z0-9_-]/', '', $kodebpr) . '/';
    }

    /** "custom:<kodebpr>/<file>" → [kodebpr, file] atau null bila tidak valid. */
    public static function parseCustom(string $value): ?array
    {
        if (!preg_match('#^custom:([A-Za-z0-9_-]+)/([A-Za-z0-9_-]+\.jpg)$#', $value, $m)) {
            return null;
        }
        return [$m[1], $m[2]];
    }

    public static function customSettings(string $kodebpr, string $file): array
    {
        $j = @file_get_contents(self::customDir($kodebpr) . $file . '.json');
        $s = $j ? (json_decode($j, true) ?: []) : [];
        return self::cleanSettings($s);
    }

    public static function cleanSettings(array $s): array
    {
        $out = self::CUSTOM_DEFAULT;
        foreach (self::CUSTOM_OPTS as $k => [, $opts]) {
            if (isset($s[$k]) && isset($opts[(string) $s[$k]])) {
                $out[$k] = (string) $s[$k];
            }
        }
        $out['name'] = mb_substr(trim((string) ($s['name'] ?? '')), 0, 60) ?: 'Desain sendiri';
        return $out;
    }

    /** Daftar desain unggahan BPR (terbaru dahulu). */
    public static function customList(string $kodebpr): array
    {
        $dir = self::customDir($kodebpr);
        $out = [];
        foreach (glob($dir . '*.jpg') ?: [] as $p) {
            $f = basename($p);
            if (str_ends_with($f, '_t.jpg')) {
                continue;
            }
            $out[] = ['file' => $f, 'value' => 'custom:' . $kodebpr . '/' . $f, 'time' => filemtime($p)] + self::customSettings($kodebpr, $f);
        }
        usort($out, static fn($a, $b) => $b['time'] <=> $a['time']);
        return $out;
    }

    /** Tata letak desain unggahan dari pengaturannya; warna teks otomatis dari kecerahan gambar di area teks. */
    public static function customLayout(string $imgPath, array $s): array
    {
        $y = ['top' => 46, 'middle' => 148, 'bottom' => 246][$s['pos']];
        // area teks 150 mm (sisakan ruang untuk elemen desain di tepi)
        $box = [['L' => 18, 'C' => 30, 'R' => 42][$s['align']], $y, 150, $s['pos'], $s['align']];
        $tone = $s['tone'];
        if ($s['panel'] !== 'none') {
            $tone = $s['panel'] === 'light' ? 'dark' : 'light';
        } elseif ($tone === 'auto') {
            $yTop = ['top' => 40, 'middle' => 115, 'bottom' => 190][$s['pos']];
            $tone = self::luminance($imgPath, 18, $yTop, 174, 70) < 0.55 ? 'light' : 'dark';
        }
        $light = $tone === 'light';
        $logoX = ['tl' => 18, 'tc' => 83, 'tr' => 148][$s['logo']] ?? 0;
        $logoA = ['tl' => 'L', 'tc' => 'C', 'tr' => 'R'][$s['logo']] ?? 'L';
        return [
            'title' => $s['title'] === '1',
            'box' => $box,
            'fg' => $light ? self::WHITE : self::NAVY,
            'sub' => $light ? [226, 232, 240] : [51, 65, 85],
            'accent' => $light ? [250, 204, 21] : [37, 99, 235],
            'panel' => $s['panel'] === 'none' ? null : ($s['panel'] === 'light' ? [255, 255, 255] : [15, 23, 42]),
            'logo' => $s['logo'] === 'none' ? null : [$logoX, 16, 44, 26, $logoA, true],
            'contact' => [18, $s['pos'] === 'bottom' ? 280 : 270, 174, $s['align'], $light ? [226, 232, 240] : [51, 65, 85]],
            'contact_panel' => $s['panel'] !== 'none',
        ];
    }

    /** Rata-rata kecerahan (0–1) area gambar dalam mm A4. */
    public static function luminance(string $path, float $x, float $y, float $w, float $h): float
    {
        $im = @imagecreatefromjpeg($path);
        if (!$im) {
            return 1.0;
        }
        $sx = imagesx($im) / 210;
        $sy = imagesy($im) / 297;
        $sum = 0;
        $n = 0;
        for ($i = 0; $i < 24; $i++) {
            for ($j = 0; $j < 12; $j++) {
                $px = (int) (($x + $w * ($i + .5) / 24) * $sx);
                $py = (int) (($y + $h * ($j + .5) / 12) * $sy);
                $c = imagecolorat($im, min($px, imagesx($im) - 1), min($py, imagesy($im) - 1));
                $sum += (0.2126 * (($c >> 16) & 255) + 0.7152 * (($c >> 8) & 255) + 0.0722 * ($c & 255)) / 255;
                $n++;
            }
        }
        imagedestroy($im);
        return $n ? $sum / $n : 1.0;
    }

    /**
     * Simpan gambar unggahan sebagai JPEG A4 (1240×1754, isi penuh dengan potong tengah) + thumbnail.
     * Mengembalikan nama file, atau string pesan galat diawali "!".
     */
    public static function storeUpload(string $tmp, string $kodebpr): string
    {
        $info = @getimagesize($tmp);
        if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
            return '!File harus berupa gambar JPG atau PNG.';
        }
        [$w, $h] = $info;
        if ($w < 600 || $h < 800) {
            return '!Resolusi gambar terlalu kecil (minimal 600×800 piksel; disarankan 1240×1754 atau lebih).';
        }
        if ($w * $h > 40_000_000) {
            return '!Resolusi gambar terlalu besar (maksimal 40 megapiksel).';
        }
        @ini_set('memory_limit', '512M');
        $src = $info[2] === IMAGETYPE_PNG ? @imagecreatefrompng($tmp) : @imagecreatefromjpeg($tmp);
        if (!$src) {
            return '!Gambar tidak dapat dibaca.';
        }
        // potong tengah ke rasio A4 lalu skala
        $ratio = 297 / 210;
        if ($h / $w > $ratio) {
            $cw = $w;
            $ch = (int) round($w * $ratio);
        } else {
            $ch = $h;
            $cw = (int) round($h / $ratio);
        }
        $cx = (int) (($w - $cw) / 2);
        $cy = (int) (($h - $ch) / 2);
        $dir = self::customDir($kodebpr);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $name = bin2hex(random_bytes(8)) . '.jpg';
        foreach ([[1240, 1754, $name, 86], [240, 340, substr($name, 0, -4) . '_t.jpg', 80]] as [$tw, $th, $fn, $q]) {
            $dst = imagecreatetruecolor($tw, $th);
            imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255)); // latar putih untuk PNG transparan
            imagecopyresampled($dst, $src, 0, 0, $cx, $cy, $tw, $th, $cw, $ch);
            imageinterlace($dst, false); // FPDF tidak mendukung JPEG progresif
            imagejpeg($dst, $dir . $fn, $q);
            imagedestroy($dst);
        }
        imagedestroy($src);
        return $name;
    }

    // ───────────────────────── resolusi untuk renderer ─────────────────────────

    /**
     * [path latar | null, tata letak, kunci vektor | null] untuk nilai cover; default "tpl:korporat".
     * $settings (opsional) menggantikan pengaturan tersimpan desain unggahan — untuk pratinjau sebelum disimpan.
     */
    public static function resolve(?string $value, ?array $settings = null): array
    {
        $value = (string) $value;
        $tpls = self::templates();
        if ($c = self::parseCustom($value)) {
            $p = self::customDir($c[0]) . $c[1];
            if (is_file($p)) {
                $s = $settings !== null ? self::cleanSettings($settings) : self::customSettings($c[0], $c[1]);
                return [$p, self::customLayout($p, $s), null];
            }
            $value = '';
        }
        if (!isset($tpls[$value])) {
            $value = 'tpl:korporat';
        }
        [, $bg, $layout] = $tpls[$value];
        if ($bg) {
            // salinan JPEG ringan (dibuat sekali) — PNG asli 1–3 MB lambat diproses FPDF
            $jpg = FCPATH . 'assets/img/covers/bg-' . strtolower(pathinfo($bg, PATHINFO_FILENAME)) . '.jpg';
            $bg = is_file($jpg) ? $jpg : FCPATH . 'assets/img/' . $bg;
            return [$bg, $layout, null];
        }
        return [null, $layout, substr($value, 4)];
    }
}
