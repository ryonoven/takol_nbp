public function generateLaporanProfilRisikoPdfBytes(): string
{
$periodeDetail = $this->periodeModel->find($this->periodeId);
$bprData = $this->infobprModel->getBprByKode($this->userKodebpr);
$showprofilresiko = $this->showprofilresikoModel
->getByKodebprAndPeriode($this->userKodebpr, $this->periodeId);

if (!$showprofilresiko)
return '';

// ── Logo base64 ──────────────────────────────────────────────
$logoBase64 = '';
$logoMime = 'image/png';
if (!empty($bprData['logo'])) {
$logoPath = FCPATH . 'asset/img/' . $bprData['logo'];
if (file_exists($logoPath)) {
$logoBase64 = base64_encode(file_get_contents($logoPath));
$logoMime = mime_content_type($logoPath);
}
}
$logoTag = $logoBase64
? '<img src="data:' . $logoMime . ';base64,' . $logoBase64 . '" style="height:40px;width:auto">'
: '';

// ── Periode sebelumnya ───────────────────────────────────────
$periodeSebelum = $this->getPeriodeSebelumnya(
$periodeDetail['semester'],
$periodeDetail['tahun']
);

// ── Helper functions ─────────────────────────────────────────
$getRiskColor = function ($v): string {
switch ((int) $v) {
case 1:
return '#00FF00';
case 2:
return '#90EE90';
case 3:
return '#FFFF00';
case 4:
return '#FFA500';
case 5:
return '#FF0000';
default:
return '#CCCCCC';
}
};
$getRiskText = function ($v): string {
switch ((int) $v) {
case 1:
return 'Sangat Rendah';
case 2:
return 'Rendah';
case 3:
return 'Sedang';
case 4:
return 'Tinggi';
case 5:
return 'Sangat Tinggi';
default:
return '-';
}
};

// ── Ambil semua nilai risiko current ─────────────────────────
$fetch = function ($model, $faktorId) {
return $model->where('kodebpr', $this->userKodebpr)
->where('periode_id', $this->periodeId)
->where('faktor1id', $faktorId)
->first();
};

$kreditI = $fetch($this->kreditinherenModel, 13);
$kreditK = $fetch($this->kreditkpmrModel, 33);
$kreditIB = $fetch($this->kreditinherenModel, 14);
$kreditKB = $fetch($this->kreditkpmrModel, 34);

$opI = $fetch($this->operasionalinherenModel, 48);
$opK = $fetch($this->operasionalkpmrModel, 70);
$opIB = $fetch($this->operasionalinherenModel, 49);
$opKB = $fetch($this->operasionalkpmrModel, 71);

$kepI = $fetch($this->kepatuhaninherenModel, 81);
$kepK = $fetch($this->kepatuhankpmrModel, 102);
$kepIB = $fetch($this->kepatuhaninherenModel, 82);
$kepKB = $fetch($this->kepatuhankpmrModel, 103);

$likI = $fetch($this->likuiditasinherenModel, 115);
$likK = $fetch($this->likuiditaskpmrModel, 135);
$likIB = $fetch($this->likuiditasinherenModel, 116);
$likKB = $fetch($this->likuiditaskpmrModel, 136);

$repI = $fetch($this->reputasiinherenModel, 148);
$repK = $fetch($this->reputasikpmrModel, 168);
$repIB = $fetch($this->reputasiinherenModel, 149);
$repKB = $fetch($this->reputasikpmrModel, 169);

$strI = $fetch($this->stratejikinherenModel, 179);
$strK = $fetch($this->stratejikkpmrModel, 199);
$strIB = $fetch($this->stratejikinherenModel, 180);
$strKB = $fetch($this->stratejikkpmrModel, 200);

// ── Tanggal ──────────────────────────────────────────────────
$tanggal = '';
if (!empty($showprofilresiko['tanggal'])) {
$d = \DateTime::createFromFormat('Y-m-d', $showprofilresiko['tanggal']);
if ($d) {
$bl = [
'01' => 'Januari',
'02' => 'Februari',
'03' => 'Maret',
'04' => 'April',
'05' => 'Mei',
'06' => 'Juni',
'07' => 'Juli',
'08' => 'Agustus',
'09' => 'September',
'10' => 'Oktober',
'11' => 'November',
'12' => 'Desember'
];
$tanggal = $d->format('d') . ' ' . $bl[$d->format('m')] . ' ' . $d->format('Y');
}
}

// ── Shorthand variabel ───────────────────────────────────────
$s = fn($v) => htmlspecialchars((string) ($v ?? ''));
$spr = $showprofilresiko;
$sm = $s($periodeDetail['semester']);
$th = $s($periodeDetail['tahun']);
$smB = $s($periodeSebelum['semester']);
$thB = $s($periodeSebelum['tahun']);

// ══════════════════════════════════════════════════════════════
// HELPER: render satu baris tabel risiko
// ══════════════════════════════════════════════════════════════
$risikoRow = function (string $label, $iC, $kC, $nC, $iP, $kP, $nP) use ($getRiskColor): string {
$cell = fn($v) => '<td style="text-align:center;font-weight:bold;font-size:8.5pt;'
                . 'background:' . $getRiskColor($v) . ';border:1px solid #000;padding:6px 3px;vertical-align:middle">'
    . ($v ?? '') . '</td>';
return '<tr>'
    . '<td style="border:none solid #000;padding:6px 8px;font-size:8.5pt;vertical-align:middle">' .
        htmlspecialchars($label) . '</td>'
    . $cell($iC) . $cell($kC) . $cell($nC)
    . $cell($iP) . $cell($kP) . $cell($nP)
    . '</tr>';
};

// ── Baris risiko ─────────────────────────────────────────────
$tableRows = $risikoRow(
'Risiko Kredit',
$kreditI['penilaiankredit'] ?? null,
$kreditK['penilaiankredit'] ?? null,
$spr['nkredit'] ?? null,
$kreditIB['penilaiankredit'] ?? null,
$kreditKB['penilaiankredit'] ?? null,
$spr['nbelumkredit'] ?? null
);
$tableRows .= $risikoRow(
'Risiko Operasional',
$opI['penilaiankredit'] ?? null,
$opK['penilaiankredit'] ?? null,
$spr['noperasional'] ?? null,
$opIB['penilaiankredit'] ?? null,
$opKB['penilaiankredit'] ?? null,
$spr['nbelumoperasional'] ?? null
);
$tableRows .= $risikoRow(
'Risiko Kepatuhan',
$kepI['penilaiankredit'] ?? null,
$kepK['penilaiankredit'] ?? null,
$spr['nkepatuhan'] ?? null,
$kepIB['penilaiankredit'] ?? null,
$kepKB['penilaiankredit'] ?? null,
$spr['nbelumkepatuhan'] ?? null
);
$tableRows .= $risikoRow(
'Risiko Likuiditas',
$likI['penilaiankredit'] ?? null,
$likK['penilaiankredit'] ?? null,
$spr['nlikuiditas'] ?? null,
$likIB['penilaiankredit'] ?? null,
$likKB['penilaiankredit'] ?? null,
$spr['nbelumlikuiditas'] ?? null
);

if (!empty($repI)) {
$tableRows .= $risikoRow(
'Risiko Reputasi',
$repI['penilaiankredit'] ?? null,
$repK['penilaiankredit'] ?? null,
$spr['nreputasi'] ?? null,
$repIB['penilaiankredit'] ?? null,
$repKB['penilaiankredit'] ?? null,
$spr['nbelumreputasi'] ?? null
);
}
if (!empty($strI)) {
$tableRows .= $risikoRow(
'Risiko Stratejik',
$strI['penilaiankredit'] ?? null,
$strK['penilaiankredit'] ?? null,
$spr['nstratejik'] ?? null,
$strIB['penilaiankredit'] ?? null,
$strKB['penilaiankredit'] ?? null,
$spr['nbelumstratejik'] ?? null
);
}

// ── Baris Peringkat ──────────────────────────────────────────
$pcC = $getRiskColor($spr['ntotalrisk'] ?? null);
$pcP = $getRiskColor($spr['nbelumtotalrisk'] ?? null);
$tableRows .= '<tr style="background:#aaaaaa">
    <td colspan="3"
        style="border:1px solid #000;padding:6px 8px;font-weight:bold;font-size:8.5pt;vertical-align:middle">Peringkat
        Risiko</td>
    <td
        style="text-align:center;font-weight:bold;font-size:10pt;background:' . $pcC . ';border:1px solid #000;padding:6px;vertical-align:middle">
        ' . ($spr['ntotalrisk'] ?? '') . '</td>
    <td colspan="2" style="border:1px solid #000"></td>
    <td
        style="text-align:center;font-weight:bold;font-size:10pt;background:' . $pcP . ';border:1px solid #000;padding:6px;vertical-align:middle">
        ' . ($spr['nbelumtotalrisk'] ?? '') . '</td>
</tr>';

// ── Analisis bullet ──────────────────────────────────────────
$analisisList = [
'Kredit' => [$kreditI, $kreditK],
'Operasional' => [$opI, $opK],
'Kepatuhan' => [$kepI, $kepK],
'Likuiditas' => [$likI, $likK],
];
if (!empty($repI))
$analisisList['Reputasi'] = [$repI, $repK];
if (!empty($strI))
$analisisList['Stratejik'] = [$strI, $strK];

$analisisHtml = '';
foreach ($analisisList as $jenis => [$inh, $kpmr]) {
$ni = $inh['penilaiankredit'] ?? 0;
$nk = $kpmr['penilaiankredit'] ?? 0;
$analisisHtml .= '<li style="margin-bottom:4px;font-size:9pt">Risiko ' . $jenis
    . ': Risiko Inheren peringkat ' . $ni . ' (' . $getRiskText($ni)
    . '), Risiko KPMR peringkat ' . $nk . ' (' . $getRiskText($nk) . ')</li>';
}

// ── Profil rows ──────────────────────────────────────────────
$profilRows = [
['Periode', "Semester {$sm} Tahun {$th}"],
['Nama BPR', $bprData['namabpr'] ?? ''],
['Alamat', $bprData['alamat'] ?? ''],
['Nomor Telepon', $bprData['nomor'] ?? ''],
['Modal Inti', $periodeDetail['modalinti'] ?? ''],
['Total Aset', $periodeDetail['totalaset'] ?? ''],
['Jumlah Kantor Cabang', $periodeDetail['kantorcabang'] ?? ''],
['Kegiatan sebagai penerbit kartu ATM atau kartu debit', $periodeDetail['atmdebit'] ?? ''],
];
$profilHtml = '';
foreach ($profilRows as [$label, $value]) {
$profilHtml .= '<tr>
    <td style="width:230px;padding:3px 6px;font-size:8.5pt;vertical-align:top">'
        . htmlspecialchars($label) . '</td>
    <td style="width:12px;padding:3px 2px;vertical-align:top;font-size:8.5pt">:</td>
    <td style="padding:3px 6px;font-size:8.5pt;vertical-align:top">'
        . htmlspecialchars($value) . '</td>
</tr>';
}

// ── Kesimpulan ───────────────────────────────────────────────
$kesimpulanHtml = '';
if (!empty($spr['kesimpulan'])) {
$kesimpulanHtml = '
<div style="border:1px solid #000;padding:10px 12px;margin-top:12px;font-size:9pt">
    <div style="font-weight:bold;margin-bottom:8px;font-size:9.5pt">Kesimpulan</div>
    <div style="font-size:9pt;line-height:1.5">' . nl2br(htmlspecialchars($spr['kesimpulan'])) . '</div>
</div>';
}

// ══════════════════════════════════════════════════════════════
// HELPER: Header HTML (dipakai di setiap halaman baru)
// ══════════════════════════════════════════════════════════════
$headerHtml = '
<table style="width:100%;border-collapse:collapse;margin-bottom:0;padding-bottom:6px">
    <tr>
        <td style="width:90px;vertical-align:middle;padding-right:8px">' . $logoTag . '</td>
        <td style="text-align:center;vertical-align:middle">
            <div style="font-size:12pt;font-weight:bold;line-height:1.4">' . $s($bprData['namabpr']) . '</div>
            <div style="font-size:8pt;margin-top:2px">' . $s($bprData['alamat']) . '</div>
            <div style="font-size:8pt;margin-top:1px">Telepon: ' . $s($bprData['nomor']) . '</div>
            <div style="font-size:8pt;margin-top:1px">Website: ' . $s($bprData['webbpr'] ?? '-')
                . '&nbsp;&nbsp;&nbsp;Email: ' . $s($bprData['email'] ?? '-') . '</div>
        </td>
    </tr>
</table>
<div style="border-top:2.5px solid #000;margin-top:6px;margin-bottom:14px"></div>';

// ══════════════════════════════════════════════════════════════
// HELPER: query keterangan langsung dari DB
// ══════════════════════════════════════════════════════════════
$getKeteranganRows = function (string $riskType, int $faktorId): array {
$ranges = $this->getFaktorRanges($riskType, $faktorId > 100);

$modelMap = [
'kredit' => [$this->kreditinherenModel, $this->kreditkpmrModel],
'operasional' => [$this->operasionalinherenModel, $this->operasionalkpmrModel],
'kepatuhan' => [$this->kepatuhaninherenModel, $this->kepatuhankpmrModel],
'likuiditas' => [$this->likuiditasinherenModel, $this->likuiditaskpmrModel],
'reputasi' => [$this->reputasiinherenModel, $this->reputasikpmrModel],
'stratejik' => [$this->stratejikinherenModel, $this->stratejikkpmrModel],
];

$kpmrFaktorIds = [33, 34, 70, 71, 102, 103, 135, 136, 168, 169, 199, 200];
$isKpmr = in_array($faktorId, $kpmrFaktorIds);
$model = $modelMap[$riskType][$isKpmr ? 1 : 0];

return $model->where('kodebpr', $this->userKodebpr)
->where('periode_id', $this->periodeId)
->whereIn('faktor1id', $ranges)
->orderBy('faktor1id', 'ASC')
->findAll();
};

// ══════════════════════════════════════════════════════════════
// HALAMAN B — ANALISIS PER JENIS RISIKO
// ══════════════════════════════════════════════════════════════
$risikoArray = [
['key' => 'kredit', 'title' => 'ANALISIS RISIKO KREDIT', 'iF' => 13, 'kF' => 33],
['key' => 'operasional', 'title' => 'ANALISIS RISIKO OPERASIONAL', 'iF' => 48, 'kF' => 70],
['key' => 'kepatuhan', 'title' => 'ANALISIS RISIKO KEPATUHAN', 'iF' => 81, 'kF' => 102],
['key' => 'likuiditas', 'title' => 'ANALISIS RISIKO LIKUIDITAS', 'iF' => 115, 'kF' => 135],
];
if (!empty($repI))
$risikoArray[] = ['key' => 'reputasi', 'title' => 'ANALISIS RISIKO REPUTASI', 'iF' => 148, 'kF' => 168];
if (!empty($strI))
$risikoArray[] = ['key' => 'stratejik', 'title' => 'ANALISIS RISIKO STRATEJIK', 'iF' => 179, 'kF' => 199];

// Map key → nilai current
$risikoNilai = [
'kredit' => ['iC' => $kreditI['penilaiankredit'] ?? null, 'kC' => $kreditK['penilaiankredit'] ?? null, 'n' =>
$spr['nkredit'] ?? null],
'operasional' => ['iC' => $opI['penilaiankredit'] ?? null, 'kC' => $opK['penilaiankredit'] ?? null, 'n' =>
$spr['noperasional'] ?? null],
'kepatuhan' => ['iC' => $kepI['penilaiankredit'] ?? null, 'kC' => $kepK['penilaiankredit'] ?? null, 'n' =>
$spr['nkepatuhan'] ?? null],
'likuiditas' => ['iC' => $likI['penilaiankredit'] ?? null, 'kC' => $likK['penilaiankredit'] ?? null, 'n' =>
$spr['nlikuiditas'] ?? null],
'reputasi' => ['iC' => $repI['penilaiankredit'] ?? null, 'kC' => $repK['penilaiankredit'] ?? null, 'n' =>
$spr['nreputasi'] ?? null],
'stratejik' => ['iC' => $strI['penilaiankredit'] ?? null, 'kC' => $strK['penilaiankredit'] ?? null, 'n' =>
$spr['nstratejik'] ?? null],
];

$halamanBHtml = '';
foreach ($risikoArray as $idx => $risiko) {
$nilaiRisiko = $risikoNilai[$risiko['key']];
$tingkat = $nilaiRisiko['n'];

$inherenRows = $getKeteranganRows($risiko['key'], $risiko['iF']);
$kpmrRows = $getKeteranganRows($risiko['key'], $risiko['kF']);

// Build list keterangan inheren
$inherenList = '';
if (!empty($inherenRows)) {
foreach ($inherenRows as $i => $row) {
$inherenList .= '<li style="margin-bottom:4px;font-size:9pt;line-height:1.5">'
    . htmlspecialchars($row['keterangan'] ?? '') . '</li>';
}
$inherenList = '<ol style="margin:4px 0 0 22px;padding:0;font-size:9pt">' . $inherenList . '</ol>';
} else {
$inherenList = '<p style="font-size:9pt;margin:4px 0 0 0">Tingkat Risiko Inheren: '
    . ($nilaiRisiko['iC'] ?? '-') . ' (' . $getRiskText($nilaiRisiko['iC']) . ')</p>';
}

// Build list keterangan KPMR
$kpmrList = '';
if (!empty($kpmrRows)) {
foreach ($kpmrRows as $i => $row) {
$kpmrList .= '<li style="margin-bottom:4px;font-size:9pt;line-height:1.5">'
    . htmlspecialchars($row['keterangan'] ?? '') . '</li>';
}
$kpmrList = '<ol style="margin:4px 0 0 22px;padding:0;font-size:9pt">' . $kpmrList . '</ol>';
} else {
$kpmrList = '<p style="font-size:9pt;margin:4px 0 0 0">Tingkat KPMR: '
    . ($nilaiRisiko['kC'] ?? '-') . ' (' . $getRiskText($nilaiRisiko['kC']) . ')</p>';
}

$judulB = ($idx === 0)
? '<div style="font-size:11pt;font-weight:bold;margin-bottom:14px">B. ANALISIS PER JENIS RISIKO</div>'
: '';

$halamanBHtml .= '
<div style="page-break-before:always">
    ' . $headerHtml . '
    ' . $judulB . '
    <div style="font-size:10.5pt;font-weight:bold;margin-bottom:10px;margin-left:10px">' . $risiko['title'] . '</div>

    <table class="no-border" style="margin-bottom:12px;font-size:8.5pt;margin-left:10px">
        <tr>
            <td style="width:80px;padding:2px 0;vertical-align:top">Nama BPR</td>
            <td style="width:12px;padding:2px 0;vertical-align:top">:</td>
            <td style="padding:2px 0;vertical-align:top">' . $s($bprData['namabpr']) . '</td>
        </tr>
        <tr>
            <td style="padding:2px 0;vertical-align:top">Periode</td>
            <td style="padding:2px 0;vertical-align:top">:</td>
            <td style="padding:2px 0;vertical-align:top">Semester ' . $sm . ' Tahun ' . $th . '</td>
        </tr>
    </table>

    <!-- Box Analisis (bagian 1 & 2) -->
    <div style="border:1px solid #000;padding:12px 14px;font-size:9pt;margin-bottom:10px">
        <div style="font-weight:bold;font-size:10pt;margin-bottom:10px">Analisis</div>

        <div style="font-weight:bold;font-size:9.5pt;margin-bottom:6px">1. Tingkat Risiko:</div>
        <p style="margin:0 0 12px 16px;font-size:9pt">Peringkat ' . ($tingkat ?? '-')
            . ', ' . $getRiskText($tingkat) . '</p>

        <div style="font-weight:bold;font-size:9.5pt;margin-bottom:6px">2. Risiko Inheren:</div>
        <div style="margin-left:10px">' . $inherenList . '</div>
    </div>

    <!-- Box KPMR (bagian 3) -->
    <div style="border:1px solid #000;padding:12px 14px;font-size:9pt">
        <div style="font-weight:bold;font-size:9.5pt;margin-bottom:6px">3. Kualitas Penerapan Manajemen Risiko:</div>
        <div style="margin-left:10px">' . $kpmrList . '</div>
    </div>
</div>';
}

// ══════════════════════════════════════════════════════════════
// HALAMAN TANDA TANGAN
// ══════════════════════════════════════════════════════════════
$ttdHtml = '
<div style="page-break-before:always">
    ' . $headerHtml . '
    <div style="margin-top:90px;text-align:center;font-size:10pt">
        <p style="margin-bottom:6px">' . $s($spr['lokasi'] ?? '') . ', ' . $s($tanggal) . '</p>
        <p style="font-weight:bold;font-size:11pt">' . $s($bprData['namabpr']) . '</p>
        <div style="margin-top:75px">
            <div style="display:inline-block;text-align:center">
                <div style="font-weight:bold;border-bottom:1px solid #000;
                                padding:0 20px 4px 20px;display:inline-block;font-size:10.5pt">
                    ' . $s($spr['dirkep'] ?? '') . '
                </div>
                <div style="margin-top:5px;font-size:10pt">Direktur</div>
            </div>
        </div>
    </div>
</div>';

// ══════════════════════════════════════════════════════════════
// RENDER HTML LENGKAP
// ══════════════════════════════════════════════════════════════
$html = '
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 30px 30px 30px 30px;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
            color: #000;
        }

        .container {
            width: 100%;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #000;
        }

        .no-border td {
            border: none;
        }

        .header-line {
            border-top: 2px solid #000;
            margin: 10px 0 15px 0;
        }

        .title {
            text-align: center;
            font-weight: bold;
            font-size: 13pt;
            margin-bottom: 15px;
        }

        .section-title {
            font-weight: bold;
            font-size: 10.5pt;
            margin-bottom: 8px;
        }

        .box {
            border: 1px solid #000;
            padding: 10px;
            margin-top: 10px;
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>

<body>

    <div class="container">

        ' . $headerHtml . '

        <div class="title">LAPORAN PROFIL RISIKO</div>

        <div class="section-title">A. PROFIL RISIKO</div>

        <table class="no-border" style="margin-bottom:15px;font-size:9pt">
            ' . $profilHtml . '
        </table>

        <!-- TABLE RISIKO -->
        <table style="font-size:8pt;margin-bottom:10px">

            <thead>
                <tr>
                    <th rowspan="2" style="width:120px;background:#d9d9d9">Jenis Risiko</th>
                    <th colspan="3" style="background:#d9d9d9">Penilaian Per Posisi</th>
                    <th colspan="3" style="background:#d9d9d9">
                        Penilaian Posisi Sebelumnya<br>
                        <span style="font-size:7pt">(Semester ' . $smB . ' Tahun ' . $thB . ')</span>
                    </th>
                </tr>

                <tr>
                    <th>Inheren</th>
                    <th>KPMR</th>
                    <th>Risiko</th>
                    <th>Inheren</th>
                    <th>KPMR</th>
                    <th>Risiko</th>
                </tr>
            </thead>

            <tbody>
                ' . $tableRows . '
            </tbody>

        </table>

        <p style="text-align:center;font-size:8pt;color:#555;margin-bottom:12px">
            Keterangan Peringkat: 1 (Sangat Rendah), 2 (Rendah), 3 (Sedang), 4 (Tinggi), 5 (Sangat Tinggi)
        </p>

        <!-- ANALISIS -->
        <div class="box">
            <b>Analisis</b>
            <p style="margin-top:5px">
                Hasil dari analisis profil risiko Semester ' . $sm . ' Tahun ' . $th . ' sebagai berikut:
            </p>
            <ul style="margin-left:15px;margin-top:5px">
                ' . $analisisHtml . '
            </ul>
        </div>

        ' . $kesimpulanHtml . '

        ' . $halamanBHtml . '

        ' . $ttdHtml . '

    </div>
</body>

</html>';

// ── Dompdf render ────────────────────────────────────────────
$options = new \Dompdf\Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'Arial');
$options->set('isFontSubsettingEnabled', true);
$options->set('defaultMediaType', 'print');

$dompdf = new \Dompdf\Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');

// Set margin: top=30px, right=30px, bottom=30px, left=30px
// Dompdf menggunakan @page CSS untuk margin

$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

return $dompdf->output();
}