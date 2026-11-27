<?php

namespace App\Libraries;

/**
 * Menggambar halaman cover (dipakai PdfSelfassessment & CoverPdf untuk pratinjau).
 * Teks (judul, periode, nama BPR), logo, dan kontak menyesuaikan tata letak desain (CoverDesign):
 * nama BPR dibungkus sesuai lebar area & mengecil bila panjang, logo diskalakan proporsional.
 */
trait CoverRenderTrait
{
    // ───────────────────────── primitif gambar ─────────────────────────

    private function cvEnc(string $s): string
    {
        $r = @iconv('UTF-8', 'windows-1252//TRANSLIT', $s);
        return $r === false ? $s : $r;
    }

    private function cvFill(array $c): void
    {
        $this->SetFillColor($c[0], $c[1], $c[2]);
    }

    /** Poligon terisi; $pts = [[x, y], …] dalam mm. */
    private function cvPoly(array $pts, string $op = 'f'): void
    {
        $s = '';
        foreach ($pts as $i => [$x, $y]) {
            $s .= sprintf('%.2F %.2F %s ', $x * $this->k, ($this->h - $y) * $this->k, $i ? 'l' : 'm');
        }
        $this->_out($s . 'h ' . $op);
    }

    private function cvCircle(float $x, float $y, float $r, string $op = 'f'): void
    {
        $k = $this->k;
        $H = $this->h;
        $c = 0.5523 * $r;
        $p = static fn($a, $b) => sprintf('%.2F %.2F', $a * $k, ($H - $b) * $k);
        $this->_out($p($x + $r, $y) . ' m '
            . $p($x + $r, $y - $c) . ' ' . $p($x + $c, $y - $r) . ' ' . $p($x, $y - $r) . ' c '
            . $p($x - $c, $y - $r) . ' ' . $p($x - $r, $y - $c) . ' ' . $p($x - $r, $y) . ' c '
            . $p($x - $r, $y + $c) . ' ' . $p($x - $c, $y + $r) . ' ' . $p($x, $y + $r) . ' c '
            . $p($x + $c, $y + $r) . ' ' . $p($x + $r, $y + $c) . ' ' . $p($x + $r, $y) . ' c ' . $op);
    }

    private function cvRound(float $x, float $y, float $w, float $h, float $r, string $op = 'f'): void
    {
        $k = $this->k;
        $H = $this->h;
        $c = 0.5523 * $r;
        $p = static fn($a, $b) => sprintf('%.2F %.2F', $a * $k, ($H - $b) * $k);
        $this->_out($p($x + $r, $y) . ' m ' . $p($x + $w - $r, $y) . ' l '
            . $p($x + $w - $r + $c, $y) . ' ' . $p($x + $w, $y + $r - $c) . ' ' . $p($x + $w, $y + $r) . ' c '
            . $p($x + $w, $y + $h - $r) . ' l '
            . $p($x + $w, $y + $h - $r + $c) . ' ' . $p($x + $w - $r + $c, $y + $h) . ' ' . $p($x + $w - $r, $y + $h) . ' c '
            . $p($x + $r, $y + $h) . ' l '
            . $p($x + $r - $c, $y + $h) . ' ' . $p($x, $y + $h - $r + $c) . ' ' . $p($x, $y + $h - $r) . ' c '
            . $p($x, $y + $r) . ' l '
            . $p($x, $y + $r - $c) . ' ' . $p($x + $r - $c, $y) . ' ' . $p($x + $r, $y) . ' c ' . $op);
    }

    /** Gradien vertikal sederhana (pita-pita tipis). */
    private function cvGrad(float $x, float $y, float $w, float $h, array $a, array $b, int $steps = 48): void
    {
        $sh = $h / $steps;
        for ($i = 0; $i < $steps; $i++) {
            $t = $i / max(1, $steps - 1);
            $this->SetFillColor((int) ($a[0] + ($b[0] - $a[0]) * $t), (int) ($a[1] + ($b[1] - $a[1]) * $t), (int) ($a[2] + ($b[2] - $a[2]) * $t));
            $this->Rect($x, $y + $i * $sh, $w, min($sh * 2, $y + $h - ($y + $i * $sh)), 'F');
        }
    }

    // ───────────────────────── latar template vektor ─────────────────────────

    private function cvBackground(string $key): void
    {
        switch ($key) {
            case 'minimalis':
                $this->cvFill([255, 255, 255]); $this->Rect(0, 0, 210, 297, 'F');
                $this->cvFill([20, 24, 99]); $this->Rect(0, 0, 16, 297, 'F');
                $this->cvFill([37, 99, 235]); $this->Rect(16, 0, 3, 297, 'F');
                $this->cvFill([241, 245, 249]); $this->Rect(19, 255, 191, 42, 'F');
                $this->cvFill([224, 231, 255]); $this->cvCircle(190, 70, 38);
                $this->cvFill([238, 242, 255]); $this->cvCircle(172, 104, 18);
                break;
            case 'emerald':
                $this->cvGrad(0, 0, 210, 297, [6, 78, 59], [3, 40, 38]);
                $this->SetDrawColor(212, 175, 55);
                foreach ([[205, 20, [44, 58, 72]], [5, 285, [36, 50, 64]]] as [$cx, $cy, $rs]) {
                    foreach ($rs as $i => $r) {
                        $this->SetLineWidth(0.9 - $i * 0.25);
                        $this->cvCircle($cx, $cy, $r, 'S');
                    }
                }
                $this->cvFill([212, 175, 55]); $this->Rect(95, 205, 20, 0.8, 'F');
                break;
            case 'klasik':
                $this->cvFill([250, 247, 238]); $this->Rect(0, 0, 210, 297, 'F');
                $this->SetDrawColor(180, 140, 40);
                $this->SetLineWidth(1.2); $this->Rect(10, 10, 190, 277);
                $this->SetLineWidth(0.3); $this->Rect(14, 14, 182, 269);
                $this->cvFill([180, 140, 40]);
                foreach ([[14, 14], [196, 14], [14, 283], [196, 283]] as [$x, $y]) {
                    $this->cvPoly([[$x, $y - 3.5], [$x + 3.5, $y], [$x, $y + 3.5], [$x - 3.5, $y]]);
                }
                $this->cvPoly([[105, 222], [109, 226], [105, 230], [101, 226]]);
                $this->Rect(60, 225.6, 37, 0.5, 'F'); $this->Rect(113, 225.6, 37, 0.5, 'F');
                break;
            case 'geometris':
                $this->cvFill([255, 255, 255]); $this->Rect(0, 0, 210, 297, 'F');
                $this->cvFill([224, 242, 254]); $this->cvPoly([[0, 0], [46, 0], [0, 40]]);
                $this->cvFill([20, 24, 99]); $this->cvPoly([[210, 150], [210, 297], [40, 297]]);
                $this->cvFill([14, 165, 233]); $this->cvPoly([[210, 205], [210, 297], [100, 297]]);
                $this->cvFill([56, 189, 248]); $this->cvPoly([[210, 240], [210, 297], [150, 297]]);
                $this->cvFill([31, 42, 168]); $this->cvPoly([[150, 220], [176, 246], [150, 272], [124, 246]]);
                $this->SetDrawColor(14, 165, 233); $this->SetLineWidth(0.6);
                $this->Line(20, 44, 60, 44);
                break;
            case 'marun':
                $this->cvFill([255, 255, 255]); $this->Rect(0, 0, 210, 297, 'F');
                $this->cvGrad(0, 0, 210, 215, [127, 29, 29], [88, 14, 22]);
                $this->cvFill([255, 255, 255]); $this->cvPoly([[0, 205], [210, 170], [210, 216], [0, 216]]);
                $this->cvFill([234, 88, 12]); $this->cvPoly([[0, 205], [210, 170], [210, 175], [0, 210]]);
                $this->cvFill([254, 242, 242]); $this->cvPoly([[0, 250], [210, 222], [210, 297], [0, 297]]);
                break;
            case 'teal':
                $this->cvGrad(0, 0, 210, 297, [17, 94, 89], [8, 51, 68]);
                $this->cvFill([20, 184, 166]);
                foreach ([0, 22, 44] as $i => $o) {
                    $this->cvFill([[20, 184, 166], [45, 212, 191], [94, 234, 212]][$i]);
                    $this->cvPoly([[110 + $o, 0], [124 + $o, 0], [210, 86 - $o * 1.0], [210, 100 - $o * 1.0]]);
                }
                $this->cvFill([253, 224, 71]); $this->cvCircle(180, 150, 5);
                $this->cvFill([15, 118, 110]); $this->cvCircle(150, 175, 26);
                break;
            case 'split':
                $this->cvFill([241, 245, 249]); $this->Rect(0, 0, 210, 297, 'F');
                $this->cvGrad(0, 0, 94, 297, [30, 41, 59], [15, 23, 42]);
                $cols = [[56, 189, 248], [31, 42, 168], [148, 163, 184], [14, 165, 233], [20, 24, 99], [203, 213, 225]];
                for ($r = 0; $r < 5; $r++) {
                    for ($c = 0; $c < 3; $c++) {
                        if (($r + $c) % 2 === 0 || ($r === 2 && $c === 1)) {
                            $this->cvFill($cols[($r * 3 + $c) % 6]);
                            $this->cvRound(110 + $c * 32, 52 + $r * 40, 26, 26, 5);
                        }
                    }
                }
                break;
            case 'korporat':
            default:
                $this->cvGrad(0, 0, 210, 297, [31, 42, 168], [15, 18, 72]);
                $this->cvFill([39, 52, 186]); $this->cvCircle(196, 40, 70);
                $this->cvFill([47, 63, 199]); $this->cvCircle(214, 8, 44);
                $this->cvFill([24, 31, 120]); $this->cvPoly([[0, 236], [210, 196], [210, 297], [0, 297]]);
                $this->cvFill([250, 204, 21]); $this->cvPoly([[0, 236], [210, 196], [210, 198.5], [0, 238.5]]);
                break;
        }
        $this->SetLineWidth(0.2);
        $this->SetDrawColor(0, 0, 0);
    }

    // ───────────────────────── teks & logo ─────────────────────────

    /** Bungkus teks sesuai lebar (font aktif). */
    private function cvWrap(string $text, float $w): array
    {
        $lines = [];
        $cur = '';
        foreach (preg_split('/\s+/', trim($text)) as $word) {
            $try = $cur === '' ? $word : $cur . ' ' . $word;
            if ($cur !== '' && $this->GetStringWidth($try) > $w) {
                $lines[] = $cur;
                $cur = $word;
            } else {
                $cur = $try;
            }
        }
        if ($cur !== '') {
            $lines[] = $cur;
        }
        return $lines;
    }

    private function cvText(float $x, float $y, float $w, float $lh, string $s, string $align): void
    {
        $this->SetXY($x, $y);
        $this->Cell($w, $lh, $s, 0, 0, $align);
    }

    /**
     * Gambar cover lengkap pada halaman aktif.
     * $d: namabpr, semester, tahun, nomor, email, webbpr, logo (path file), cover (nilai showfaktor.cover).
     */
    public function renderCover(array $d): void
    {
        [$bg, $L, $vector] = CoverDesign::resolve($d['cover'] ?? '', $d['cover_settings'] ?? null);
        $this->SetAutoPageBreak(false);
        if ($bg && is_file($bg)) {
            $this->Image($bg, 0, 0, 210, 297);
        } else {
            $this->cvBackground((string) $vector);
        }

        [$bx, $by, $bw, $anchor, $al] = $L['box'];
        $sem = (string) ($d['semester'] ?? '');
        $sem = ['ganjil' => '1', 'genap' => '2'][strtolower($sem)] ?? $sem;
        $periode = $this->cvEnc('SEMESTER ' . ($sem ?: '-') . ' TAHUN ' . ($d['tahun'] ?? date('Y')));
        $nama = $this->cvEnc(trim((string) ($d['namabpr'] ?? '')) ?: 'Nama BPR');

        // Ukur blok teks
        $items = [];
        if ($L['title']) {
            $items[] = ['LAPORAN PELAKSANAAN', 'B', 9, 5.2, 'sub'];
            $tSize = $bw < 100 ? 19 : 31;
            $this->SetFont('Arial', 'B', $tSize);
            foreach (['SELF ASSESSMENT', 'TATA KELOLA'] as $t) {
                foreach ($this->cvWrap($t, $bw) as $ln) {
                    $items[] = [$ln, 'B', $tSize, $tSize * 0.43, 'fg'];
                }
            }
            $items[] = ['(GOOD CORPORATE GOVERNANCE)', '', 9.5, 6, 'sub'];
        }
        $items[] = ['@accent', '', 0, 7, 'accent'];
        $items[] = [$periode, 'B', 13.5, 7, 'fg'];
        $size = 19;
        do {
            $this->SetFont('Arial', 'B', $size);
            $nl = $this->cvWrap($nama, $bw);
            $size -= 1;
        } while (count($nl) > 3 && $size >= 11);
        $items[] = ['@gap', '', 0, 1.5, ''];
        foreach ($nl as $ln) {
            $items[] = [$ln, 'B', $size + 1, ($size + 1) * 0.46, 'fg'];
        }
        $hTotal = array_sum(array_column($items, 3));
        $y = $anchor === 'middle' ? $by - $hTotal / 2 : ($anchor === 'bottom' ? $by - $hTotal : $by);

        if (!empty($L['panel'])) {
            $this->cvFill($L['panel']);
            $this->cvRound($bx - 8, $y - 8, $bw + 16, $hTotal + 16, 4);
        }
        foreach ($items as [$s, $st, $sz, $lh, $col]) {
            if ($s === '@accent') {
                $ax = $al === 'C' ? $bx + ($bw - 22) / 2 : ($al === 'R' ? $bx + $bw - 22 : $bx);
                $this->cvFill($L['accent']);
                $this->Rect($ax, $y + 2.6, 22, 1.4, 'F');
            } elseif ($s !== '@gap') {
                $c = $col === 'sub' ? $L['sub'] : $L['fg'];
                $this->SetTextColor($c[0], $c[1], $c[2]);
                $this->SetFont('Arial', $st, $sz);
                $this->cvText($bx, $y, $bw, $lh, $s, $al);
            }
            $y += $lh;
        }

        // Logo BPR
        $logo = (string) ($d['logo'] ?? '');
        if (!empty($L['logo']) && $logo !== '' && is_file($logo) && ($sz = @getimagesize($logo)) && in_array($sz[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF], true)) {
            [$lx, $ly, $mw, $mh, $la, $badge] = $L['logo'];
            $ratio = min($mw / $sz[0], $mh / $sz[1]);
            $w = $sz[0] * $ratio;
            $h = $sz[1] * $ratio;
            $x = $la === 'C' ? $lx + ($mw - $w) / 2 : ($la === 'R' ? $lx + $mw - $w : $lx);
            if ($badge) {
                $this->cvFill([255, 255, 255]);
                $this->cvRound($x - 3, $ly - 3, $w + 6, $h + 6, 3);
            }
            try {
                $this->Image($logo, $x, $ly, $w, $h);
            } catch (\Throwable $e) {
                log_message('error', 'Cover: logo tidak dapat dimuat: ' . $e->getMessage());
            }
        }

        // Kontak
        if (!empty($L['contact'])) {
            [$cx, $cy, $cw, $ca, $cc] = $L['contact'];
            $lines = array_values(array_filter(array_map(static fn($v) => trim((string) $v), [$d['nomor'] ?? '', $d['email'] ?? '', $d['webbpr'] ?? '']),
                static fn($v) => $v !== '' && strtoupper($v) !== 'N/A'));
            if ($lines) {
                $this->SetFont('Arial', '', 9);
                if (!empty($L['contact_panel']) && !empty($L['panel'])) {
                    $this->cvFill($L['panel']);
                    $this->cvRound($cx - 6, $cy - 4, $cw + 12, count($lines) * 4.6 + 6, 3);
                }
                $this->SetTextColor($cc[0], $cc[1], $cc[2]);
                foreach ($lines as $i => $ln) {
                    $this->cvText($cx, $cy - 1 + $i * 4.6, $cw, 4.6, $this->cvEnc($ln), $ca);
                }
            }
        }
        $this->SetTextColor(0, 0, 0);
        $this->SetFillColor(255, 255, 255);
    }
}
