<?php
/**
 * PDF Bidang Usaha (Dompdf, A4 landscape).
 * Variabel: $rows, $bpr, $tahun, $logo, $kategori, $jenis
 */
$namabpr = (string) ($bpr['namabpr'] ?? '');
$cell = static fn($s) => trim((string) $s) === '' ? '-' : nl2br(esc(trim((string) $s)));
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title><?= esc('Bidang Usaha - ' . $namabpr . ' - Tahun ' . $tahun) ?></title>
    <style>
        @page { margin: 2cm 1.6cm; }
        body { margin: 0; font-family: Helvetica, sans-serif; font-size: 9.5pt; line-height: 1.4; color: #000; }
        .header-bpr { margin-top: -1.1cm; margin-bottom: 8pt; }
        .jdl { text-align: center; font-weight: bold; margin: 0; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 12pt; }
        table.data th, table.data td { border: 0.75pt solid #555; padding: 4pt 5pt; vertical-align: top; }
        table.data th { background: #e8eaf6; font-size: 9pt; text-align: center; }
        table.data tr { page-break-inside: avoid; }
        .c { text-align: center; }
    </style>
</head>

<body>
    <table class="header-bpr" style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="width:22%; vertical-align:middle;">
                <?php if ($logo): ?><img src="<?= $logo ?>" style="max-height:18mm; max-width:90%;"><?php endif; ?>
            </td>
            <td style="width:78%; text-align:right; vertical-align:middle;">
                <div style="font-size:9.5pt; font-weight:bold;"><?= esc(mb_strtoupper($namabpr)) ?></div>
                <?php if (!empty($bpr['alamat'])): ?><div style="font-size:8pt;"><?= esc($bpr['alamat']) ?></div><?php endif; ?>
                <?php $kontak = array_filter([
                    !empty($bpr['webbpr']) ? 'Web: ' . $bpr['webbpr'] : null,
                    !empty($bpr['email']) ? 'Email: ' . $bpr['email'] : null,
                    $bpr['nomor'] ?? null,
                ]); ?>
                <?php if ($kontak): ?><div style="font-size:8pt;"><?= esc(implode(' | ', $kontak)) ?></div><?php endif; ?>
            </td>
        </tr>
    </table>
    <hr style="border:none; border-top:1.5pt solid #000; margin:0 0 10pt;">

    <p class="jdl" style="font-size:12pt;">BIDANG USAHA</p>
    <p class="jdl"><?= esc(mb_strtoupper($namabpr)) ?></p>
    <p class="jdl">TAHUN <?= esc((string) $tahun) ?></p>

    <table class="data">
        <thead>
            <tr>
                <th style="width:4%;">No</th>
                <th style="width:16%;">Kategori Kegiatan Usaha</th>
                <th style="width:22%;">Jenis Produk</th>
                <th style="width:22%;">Nama Produk</th>
                <th style="width:36%;">Uraian</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $i => $r): ?>
                <tr>
                    <td class="c"><?= $i + 1 ?></td>
                    <td><?= esc($r['kategoriusaha'] . ' - ' . ($kategori[$r['kategoriusaha']] ?? '')) ?></td>
                    <td><?= esc($r['jenisproduk'] . ' - ' . ($jenis[$r['jenisproduk']] ?? '')) ?></td>
                    <td><?= $cell($r['namaproduk']) ?></td>
                    <td><?= $cell($r['uraian']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if (trim((string) $keterangan) !== ''): ?>
        <p style="margin-top:10pt;"><b>Keterangan:</b><br><?= $cell($keterangan) ?></p>
    <?php endif; ?>

</body>

</html>
