<?php
/**
 * PDF Kerja Sama BPR dengan Bank atau Lembaga Lain (Dompdf, A4 landscape).
 * Variabel: $rows, $bpr, $tahun, $logo, $reflembaga, $keterangan
 */
$namabpr = (string) ($bpr['namabpr'] ?? '');
$cell = static fn($s) => trim((string) $s) === '' ? '-' : nl2br(esc(trim((string) $s)));
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title><?= esc('Kerja Sama BPR - ' . $namabpr . ' - Tahun ' . $tahun) ?></title>
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

    <p class="jdl" style="font-size:12pt;">KERJA SAMA BPR DENGAN BANK ATAU LEMBAGA LAIN</p>
    <p class="jdl"><?= esc(mb_strtoupper($namabpr)) ?></p>
    <p class="jdl">TAHUN <?= esc((string) $tahun) ?></p>

    <table class="data">
        <thead>
            <tr>
                <th style="width:4%;">No</th>
                <th style="width:9%;">Lembaga</th>
                <th style="width:17%;">Nama Bank / Lembaga</th>
                <th style="width:13%;">Sandi Bank / NPWP</th>
                <th style="width:17%;">Jenis Kerja Sama</th>
                <th style="width:30%;">Uraian Kerja Sama</th>
                <th style="width:10%;">Tgl Mulai</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $i => $r): ?>
                <tr>
                    <td class="c"><?= $i + 1 ?></td>
                    <td><?= esc($reflembaga[$r['reflembaga']] ?? $r['reflembaga']) ?></td>
                    <td><?= $cell($r['namabank']) ?></td>
                    <td><?= esc($r['sandibank'] !== '' ? $r['sandibank'] : ($r['npwp'] !== '' ? $r['npwp'] : '-')) ?></td>
                    <td><?= $cell($r['jeniskerjasama']) ?></td>
                    <td><?= $cell($r['uraiankerjasama']) ?></td>
                    <td class="c"><?= esc(date('d-m-Y', strtotime($r['tglkerjasama']))) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if (trim((string) $keterangan) !== ''): ?>
        <p style="margin-top:10pt;"><b>Keterangan:</b><br><?= $cell($keterangan) ?></p>
    <?php endif; ?>

</body>

</html>
