<?php
/**
 * PDF Kegiatan Usaha Signifikan (Dompdf).
 * Variabel: $row, $bpr, $tahun, $logo (data URI|null)
 */
$namabpr = (string) ($bpr['namabpr'] ?? '');

// Teks multi-baris -> paragraf; kosong -> "-"
$para = static function ($s): string {
    $s = trim((string) $s);
    return $s === '' ? '-' : nl2br(esc($s));
};

?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title><?= esc('Kegiatan Usaha Signifikan - ' . $namabpr . ' - Tahun ' . $tahun) ?></title>
    <style>
        @page {
            margin: 2.2cm 2cm;
        }

        body {
            margin: 0;
            font-family: Helvetica, sans-serif;
            font-size: 10.5pt;
            line-height: 1.45;
            color: #000;
        }

        .header-bpr {
            margin-top: -1.2cm;
            margin-bottom: 10pt;
        }

        p {
            margin: 0 0 5pt;
            text-align: justify;
        }

        .jdl {
            text-align: center;
            font-weight: bold;
            margin: 0;
        }

        .sec {
            font-weight: bold;
            font-size: 11pt;
            margin: 14pt 0 4pt;
            padding-bottom: 2pt;
            border-bottom: 1pt solid #333;
            page-break-after: avoid;
        }

        .box {
            border: 0.75pt solid #888;
            border-radius: 3pt;
            padding: 6pt 9pt;
            margin: 0 0 8pt;
            background: #fafafa;
            text-align: justify;
        }

    </style>
</head>

<body>
    <table class="header-bpr" style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="width:22%; vertical-align:middle;">
                <?php if ($logo): ?>
                    <img src="<?= $logo ?>" style="max-height:20mm; max-width:90%;">
                <?php endif; ?>
            </td>
            <td style="width:78%; text-align:right; vertical-align:middle;">
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
    <hr style="border:none; border-top:1.5pt solid #000; margin:0 0 12pt;">

    <p class="jdl" style="font-size:12.5pt;">LAPORAN PERKEMBANGAN KEGIATAN USAHA SIGNIFIKAN</p>
    <p class="jdl"><?= esc(mb_strtoupper($namabpr)) ?></p>
    <p class="jdl">TAHUN <?= esc((string) $tahun) ?></p>

    <p class="sec">1. KEGIATAN USAHA SIGNIFIKAN</p>
    <div class="box"><?= $para($row['signifikan'] ?? '') ?></div>

    <p class="sec">2. KEGIATAN USAHA LAIN</p>
    <div class="box"><?= $para($row['lain'] ?? '') ?></div>

</body>

</html>
