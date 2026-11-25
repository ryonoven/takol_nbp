<?php
$bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$tgl = '';
if (!empty($h['tanggal_laporan'])) {
    [$y, $m, $d] = explode('-', $h['tanggal_laporan']);
    $tgl = (int) $d . ' ' . $bulan[(int) $m] . ' ' . $y;
}

// Render uraian: paragraf atau poin (satu baris = satu poin)
$uraian = static function (?string $t, string $gaya, string $cls): string {
    if ($t === null || trim($t) === '') {
        return '';
    }
    $o = '';
    if ($gaya === 'poin') {
        foreach (preg_split('/\R+/', trim($t)) as $l) {
            $l = trim(preg_replace('/^[\-\x{2022}\s]+/u', '', $l));
            if ($l !== '') {
                $o .= '<div class="poin ' . $cls . '">-&nbsp;&nbsp;' . esc($l) . '</div>';
            }
        }

        return $o;
    }
    foreach (preg_split('/\R{2,}/', trim($t)) as $b) {
        $o .= '<p class="' . $cls . '">' . nl2br(esc($b)) . '</p>';
    }

    return $o;
};
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title><?= esc('Perkembangan Pelaksanaan Penerapan Strategi Anti Fraud - ' . $h['namabpr'] . ' - Semester ' . \App\Models\LaporanPdfSafModel::semNum($h['semester']) . ' Tahun ' . $h['tahun']) ?></title>
    <style>
        @page {
            margin: 2.54cm;
        }

        body {
            margin: 0;
            font-family: Helvetica, sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #000;
        }

        .header-bpr {
            margin-top: -1.6cm;
            margin-bottom: 14pt;
        }

        p {
            margin: 0 0 6pt;
            text-align: justify;
        }

        .jdl {
            text-align: center;
            font-weight: bold;
            margin: 0;
        }

        .h {
            font-weight: bold;
            margin: 8pt 0 2pt;
            page-break-after: avoid;
        }

        .l1 {
            margin-left: 5mm;
        }

        .l2 {
            margin-left: 10mm;
        }

        .b0 {
            margin-left: 5mm;
        }

        .b1 {
            margin-left: 5mm;
        }

        .b2 {
            margin-left: 15mm;
        }

        .poin {
            padding-left: 5mm;
            text-indent: -5mm;
            text-align: justify;
            margin-bottom: 3pt;
        }

        .ttd {
            width: 60%;
            margin: 22pt auto 0;
            text-align: center;
            page-break-inside: avoid;
        }

        .ttd img {
            height: 24mm;
            margin: 4pt 0;
        }
    </style>
</head>

<body>
    <table class="header-bpr" style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="width:22%; vertical-align:middle;">
                <?php if ($logo): ?>
                    <img src="<?= $logo ?>" style="max-height:22mm; max-width:90%;">
                <?php endif; ?>
            </td>
            <td style="width:78%; text-align:right; vertical-align:middle;">
                <div style="font-size:10pt; font-weight:bold;"><?= esc(mb_strtoupper((string) $h['namabpr'])) ?></div>
                <?php if (!empty($h['alamat'])): ?>
                    <div style="font-size:8pt;"><?= esc($h['alamat']) ?></div>
                <?php endif; ?>
                <?php
                $kontak = array_filter([
                    !empty($h['webbpr']) ? 'Web: ' . $h['webbpr'] : null,
                    !empty($h['email']) ? 'Email: ' . $h['email'] : null,
                    !empty($h['nomor']) ? $h['nomor'] : null,
                ]);
                ?>
                <?php if ($kontak): ?>
                    <div style="font-size:8pt;"><?= esc(implode(' | ', $kontak)) ?></div>
                <?php endif; ?>
            </td>
        </tr>
    </table>
    <hr style="border:none; border-top:2pt solid #000; margin:0 0 16pt;">

    <p class="jdl">LAPORAN PERKEMBANGAN PELAKSANAAN PENERAPAN STRATEGI ANTI FRAUD</p>
    <p class="jdl"><?= esc(mb_strtoupper((string) $h['namabpr'])) ?></p>
    <p class="jdl">SEMESTER <?= esc(\App\Models\LaporanPdfSafModel::semNum($h['semester'])) ?> TAHUN
        <?= esc($h['tahun']) ?></p>

    <p class="h" style="margin-top:20pt">Perkembangan Pelaksanaan Penerapan Strategi Anti Fraud</p>
    <?= $uraian($h['ringkasan_umum'], 'paragraf', '') ?>

    <?php foreach ([1, 2, 3, 4] as $p): ?>
        <p class="h" style="margin-top:12pt"><?= $p ?>.&nbsp;&nbsp;<?= esc($pilar[$p]) ?></p>
        <?php
        $items = $tree[$p] ?? [];
        $n = 0;
        if (!$items) {
            echo '<p class="b0"><i>Tidak ada data.</i></p>';
        }
        foreach ($items as $it) {
            $langsung = ($it['judul'] === null || $it['judul'] === '');
            if (!$langsung) {
                $n++;
                echo '<p class="h l1">' . $p . '.' . $n . '&nbsp;&nbsp;' . esc($it['judul']) . '</p>';
            }
            echo $uraian($it['uraian'], $it['gaya'], $langsung ? 'b0' : 'b1');
            foreach ($it['anak'] as $j => $a) {
                $huruf = chr(97 + $j) . '.';
                if ($a['judul'] !== null && $a['judul'] !== '') {
                    echo '<p class="h l2">' . $huruf . '&nbsp;&nbsp;' . esc($a['judul']) . '</p>';
                }
                echo $uraian($a['uraian'], 'paragraf', 'b2');
            }
        }
        ?>
    <?php endforeach; ?>

    <p class="h" style="margin-top:12pt"><?= esc($pilar[5]) ?>:</p>
    <?php if (!empty($tree[5])): ?>
        <p>
            <?= esc($h['namabpr']) ?> berkomitmen untuk segera melakukan perbaikan dan penguatan dalam penerapan Strategi Anti Fraud, dengan fokus pada:
        </p>
        <?php foreach ($tree[5] as $i => $it): ?>
            <div class="poin b0"><?= $i + 1 ?>.&nbsp;&nbsp;<?= esc($it['uraian'] ?: $it['judul']) ?></div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="b0"><i>Belum ada rencana tindak lanjut.</i></p>
    <?php endif; ?>

    <div class="ttd">
        <?= esc((string) $h['kota_ttd']) ?><?= ($h['kota_ttd'] && $tgl) ? ', ' : '' ?><?= esc($tgl) ?><br>
        Dibuat oleh<br>
        <?php if ($ttd): ?><img src="<?= $ttd ?>"><br><?php else: ?><br><br><br><br><?php endif; ?>
        <b><?= esc((string) $h['nama_pembuat']) ?></b><br>
        <b><?= esc((string) $h['jabatan_pembuat']) ?></b>
    </div>
</body>

</html>