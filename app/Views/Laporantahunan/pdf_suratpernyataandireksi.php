<?php
/**
 * PDF Surat Pernyataan Direksi tentang Tanggung Jawab atas Laporan Keuangan (Dompdf, A4).
 * Variabel: $row, $bpr, $logo (data URI|null), $tahun, $direksi (list), $direksiFields, $pernyataan
 */
$namabpr = (string) ($bpr['namabpr'] ?? '');

$bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$tglIndo = static function (?string $ymd) use ($bulan): string {
    $ts = $ymd ? strtotime($ymd) : false;
    return $ts ? date('j', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts) : '';
};

$people = array_values($direksi);
// Lebih dari 2 direksi: tata letak dirapatkan agar tetap muat satu halaman.
$compact = count($people) > 2;
$tempatTanggal = trim(($row['kota'] ?? '') . (($row['kota'] ?? '') !== '' && !empty($row['tanggalsurat']) ? ', ' : '') . $tglIndo($row['tanggalsurat'] ?? null));
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title><?= esc('Surat Pernyataan Direksi - ' . $namabpr . ' - Tahun ' . $tahun) ?></title>
    <style>
        @page { margin: 2.2cm 2.3cm; }
        body { margin: 0; font-family: Helvetica, sans-serif; font-size: 10.5pt; line-height: 1.45; color: #000; }
        .header-bpr { width: 100%; border-collapse: collapse; margin-top: -1.2cm; margin-bottom: 8pt; }
        .header-bpr td { vertical-align: middle; }
        .kop-line { border: none; border-top: 1.5pt solid #000; margin: 0 0 14pt; }
        .judul { text-align: center; font-weight: bold; font-size: 11.5pt; line-height: 1.3; margin: 0 0 20pt; }
        p { margin: 0 0 8pt; text-align: justify; }
        table.id { border-collapse: collapse; margin: 0 0 14pt 14pt; }
        table.id td { padding: 0 0 1pt; vertical-align: top; }
        table.id td.k { width: 105pt; }
        table.id td.s { width: 22pt; text-align: center; }
        ol { margin: 0 0 14pt; padding-left: 18pt; }
        ol li { text-align: justify; margin-bottom: 2pt; padding-left: 4pt; }
        .penutup { page-break-inside: avoid; }
        .ttd { width: 100%; border-collapse: collapse; margin-top: 4pt; }
        .ttd tr { page-break-inside: avoid; }
        .ttd td { vertical-align: top; width: 50%; }
        .ttd .space { height: 72pt; }
        .ttd .nm { font-weight: bold; text-decoration: underline; }
        <?php if ($compact): ?>
        @page { margin: 1.5cm 2cm; }
        body { font-size: 9.5pt; line-height: 1.25; }
        .header-bpr { margin-top: -0.8cm; margin-bottom: 6pt; }
        .kop-line { margin-bottom: 8pt; }
        .judul { font-size: 11pt; margin-bottom: 10pt; }
        p { margin-bottom: 5pt; }
        table.id { margin-bottom: 6pt; }
        table.id td { padding: 0; }
        ol { margin-bottom: 6pt; }
        ol li { margin-bottom: 1pt; }
        .ttd .space { height: 48pt; }
        <?php endif; ?>
    </style>
</head>

<body>
    <!-- Kop surat: logo + identitas BPR (sama dengan PDF laporan tahunan lainnya) -->
    <table class="header-bpr">
        <tr>
            <td style="width:22%;">
                <?php if ($logo): ?>
                    <img src="<?= $logo ?>" style="max-height:20mm; max-width:90%;">
                <?php endif; ?>
            </td>
            <td style="width:78%; text-align:right;">
                <div style="font-size:9.5pt; font-weight:bold;"><?= esc(mb_strtoupper($namabpr)) ?></div>
                <?php if (!empty($bpr['alamat'])): ?>
                    <div style="font-size:8pt;"><?= esc($bpr['alamat']) ?></div>
                <?php endif; ?>
                <?php
                $kontak = array_filter([
                    !empty($bpr['webbpr']) ? 'Web: ' . $bpr['webbpr'] : null,
                    !empty($bpr['email']) ? 'Email: ' . $bpr['email'] : null,
                    !empty($bpr['nomor']) ? $bpr['nomor'] : null,
                ]);
                ?>
                <?php if ($kontak): ?>
                    <div style="font-size:8pt;"><?= esc(implode(' | ', $kontak)) ?></div>
                <?php endif; ?>
            </td>
        </tr>
    </table>
    <hr class="kop-line">

    <div class="judul">
        Surat Pernyataan Direksi<br>
        Tentang Tanggung Jawab atas Laporan Keuangan<br>
        Untuk Tahun yang Berakhir Per 31 Desember <?= esc((string) $tahun) ?><br>
        <?= esc($namabpr) ?>
    </div>

    <p>Kami yang bertanda tangan di bawah ini:</p>

    <?php foreach ($people as $p): ?>
        <table class="id">
            <?php foreach ($direksiFields as $f => [$label]): ?>
                <tr>
                    <td class="k"><?= esc($label) ?></td>
                    <td class="s">:</td>
                    <td><?= esc($p[$f]) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endforeach; ?>

    <p>Menyatakan bahwa:</p>
    <ol>
        <?php foreach ($pernyataan as $s): ?>
            <li><?= esc($s) ?></li>
        <?php endforeach; ?>
    </ol>

    <p>Demikian surat pernyataan ini dibuat dengan sebenarnya.</p>

    <!-- Penutup (tempat/tanggal, nama BPR, tanda tangan) tidak terpisah halaman -->
    <div class="penutup">
    <p style="margin-top:10pt;"><?= esc($tempatTanggal) ?></p>
    <p style="font-weight:bold; margin-bottom:0;"><?= esc($namabpr) ?></p>

    <!-- Tanda tangan: dua kolom per baris -->
    <table class="ttd">
        <?php foreach (array_chunk($people, 2) as $pair): ?>
            <tr>
                <?php foreach ($pair as $p): ?>
                    <td>
                        <div class="space"></div>
                        <div class="nm"><?= esc($p['nama']) ?></div>
                        <div><?= esc($p['jabatan']) ?></div>
                    </td>
                <?php endforeach; ?>
                <?php if (count($pair) < 2): ?><td></td><?php endif; ?>
            </tr>
        <?php endforeach; ?>
    </table>
    </div>
</body>

</html>
