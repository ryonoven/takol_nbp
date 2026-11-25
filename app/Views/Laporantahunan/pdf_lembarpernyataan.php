<?php
/**
 * PDF Lembar Pernyataan Anggota Direksi dan Anggota Dewan Komisaris
 * tentang Tanggung Jawab atas Laporan Tahunan (Dompdf, A4).
 * Variabel: $row (kota, tanggalsurat), $bpr, $logo (data URI|null), $tahun, $anggota ['direksi'=>[], 'dekom'=>[]]
 */
$namabpr = (string) ($bpr['namabpr'] ?? '');

$bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$ts = !empty($row['tanggalsurat']) ? strtotime($row['tanggalsurat']) : false;
$tanggal = $ts ? date('j', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts) : '';
$kota = trim((string) ($row['kota'] ?? ''));
$tempatTanggal = trim($kota . ($kota !== '' && $tanggal !== '' ? ', ' : '') . $tanggal);

// Tanda tangan: Direksi kolom kiri, Dewan Komisaris kolom kanan (berpasangan per baris)
$direksi = array_values($anggota['direksi'] ?? []);
$dekom = array_values($anggota['dekom'] ?? []);
$rows = max(count($direksi), count($dekom), 1);
$compact = $rows > 3;
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title><?= esc('Lembar Pernyataan Direksi dan Dewan Komisaris - ' . $namabpr . ' - Tahun ' . $tahun) ?></title>
    <style>
        @page { margin: 2.2cm 2.3cm; }
        body { margin: 0; font-family: Helvetica, sans-serif; font-size: 11pt; line-height: 1.45; color: #000; }
        .header-bpr { width: 100%; border-collapse: collapse; margin-top: -1.2cm; margin-bottom: 8pt; }
        .header-bpr td { vertical-align: middle; }
        .kop-line { border: none; border-top: 1.5pt solid #000; margin: 0 0 22pt; }
        .judul { text-align: center; font-weight: bold; font-size: 12pt; line-height: 1.35; margin: 0 0 24pt; }
        p { margin: 0 0 14pt; text-align: justify; }
        .penutup { page-break-inside: avoid; margin-top: 10pt; }
        .tempat { text-align: center; margin: 0 0 6pt; }
        .bpr { text-align: center; font-weight: bold; margin: 0; }
        .ttd { width: 100%; border-collapse: collapse; margin-top: 4pt; }
        .ttd tr { page-break-inside: avoid; }
        .ttd td { width: 50%; text-align: center; vertical-align: top; }
        .ttd .space { height: <?= $compact ? 52 : 72 ?>pt; }
        .ttd .nm { font-weight: bold; text-decoration: underline; }
    </style>
</head>

<body>
    <!-- Kop surat: logo + identitas BPR (template laporan tahunan) -->
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
        Lembar Pernyataan<br>
        Anggota Direksi dan Anggota Dewan Komisaris<br>
        tentang Tanggung Jawab Atas Laporan Tahunan Tahun <?= esc((string) $tahun) ?><br>
        <?= esc($namabpr) ?>
    </div>

    <p>
        Kami, anggota Direksi dan anggota Dewan Komisaris <?= esc($namabpr) ?> yang namanya tercantum di bawah ini,
        dengan ini menegaskan bahwa Laporan Tahunan <?= esc($namabpr) ?> untuk tahun buku yang berakhir pada
        31 Desember <?= esc((string) $tahun) ?> telah kami periksa bersama, dan seluruh informasi yang disajikan di
        dalamnya telah disusun secara utuh serta sesuai dengan keadaan yang sebenarnya.
    </p>
    <p>
        Atas isi Laporan Tahunan tersebut, kami secara bersama-sama memikul tanggung jawab sepenuhnya, termasuk
        atas kelengkapan, ketepatan, dan keandalan informasi yang diungkapkan kepada otoritas maupun pemangku
        kepentingan.
    </p>
    <p>Pernyataan ini kami buat dengan penuh kesadaran dan tanpa paksaan dari pihak mana pun.</p>

    <div class="penutup">
        <p class="tempat"><?= esc($tempatTanggal) ?></p>
        <p class="bpr"><?= esc($namabpr) ?></p>

        <table class="ttd">
            <?php for ($i = 0; $i < $rows; $i++): ?>
                <tr>
                    <?php foreach ([$direksi[$i] ?? null, $dekom[$i] ?? null] as $p): ?>
                        <td>
                            <?php if ($p): ?>
                                <div class="space"></div>
                                <div class="nm"><?= esc($p['nama']) ?></div>
                                <div><?= esc($p['jabatan']) ?></div>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endfor; ?>
        </table>
    </div>
</body>

</html>
