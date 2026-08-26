<?php

namespace App\Libraries;

use App\Models\M_periodesemesteran;
use App\Models\M_showprofilresiko;
use App\Models\M_infobpr;
use App\Models\M_nilairisikokredit;
use App\Models\M_risikokreditkpmr;
use App\Models\M_risikooperasional;
use App\Models\M_risikooperasionalkpmr;
use App\Models\M_risikokepatuhan;
use App\Models\M_kepatuhankpmr;
use App\Models\M_likuiditasinheren;
use App\Models\M_likuiditaskpmr;
use App\Models\M_reputasiinheren;
use App\Models\M_reputasikpmr;
use App\Models\M_stratejikinheren;
use App\Models\M_stratejikkpmr;

/**
 * LaporanProfilRisikoPdf
 *
 * Generator PDF "Laporan Profil Risiko" berdiri sendiri (standalone), dipisah dari
 * Showprofilresiko agar bisa dipanggil dari controller manapun (mis. Showsemesteran
 * untuk form TKSBPRKS-0001) tanpa perlu instansiasi controller penuh.
 *
 * Isi & tampilan disamakan 1:1 secara struktur dengan generateLaporanProfilRisikoPDF()
 * pada export_all_pdf_zip.html (versi client-side pdf-lib):
 *   - Palet warna rating (biru/hijau/kuning/orange/merah)
 *   - Format Rupiah untuk Modal Inti & Total Aset
 *   - Label "Ya"/"Tidak" untuk kolom ATM/kartu debit
 *   - Header tabel penilaian (Inheren Tingkat Risiko / Risiko Manajemen Penerapan
 *     Tingkat Kualitas / Tingkat Risiko)
 *   - Baris "Peringkat Risiko" abu-abu gelap dengan teks putih
 *   - Halaman tanda tangan: Direktur Utama (kiri) + Direktur (kanan)
 *   - Nomor halaman "Halaman X dari Y"
 *
 * Cara pakai (mis. di Showsemesteran::exportAllToZip()):
 *
 *   $pdfGenerator = new \App\Libraries\LaporanProfilRisikoPdf();
 *   $pdfBytes = $pdfGenerator->generate($kodebpr, $periodeId);
 *   if (!empty($pdfBytes)) {
 *       $zip->addFromString($pdfFileName, $pdfBytes);
 *   }
 */
class LaporanProfilRisikoPdf
{
    protected M_periodesemesteran $periodeModel;
    protected M_showprofilresiko $showprofilresikoModel;
    protected M_infobpr $infobprModel;

    protected M_nilairisikokredit $kreditinherenModel;
    protected M_risikokreditkpmr $kreditkpmrModel;
    protected M_risikooperasional $operasionalinherenModel;
    protected M_risikooperasionalkpmr $operasionalkpmrModel;
    protected M_risikokepatuhan $kepatuhaninherenModel;
    protected M_kepatuhankpmr $kepatuhankpmrModel;
    protected M_likuiditasinheren $likuiditasinherenModel;
    protected M_likuiditaskpmr $likuiditaskpmrModel;
    protected M_reputasiinheren $reputasiinherenModel;
    protected M_reputasikpmr $reputasikpmrModel;
    protected M_stratejikinheren $stratejikinherenModel;
    protected M_stratejikkpmr $stratejikkpmrModel;

    protected string $kodebpr;
    protected int $periodeId;

    public function __construct()
    {
        $this->periodeModel = new M_periodesemesteran();
        $this->showprofilresikoModel = new M_showprofilresiko();
        $this->infobprModel = new M_infobpr();

        $this->kreditinherenModel = new M_nilairisikokredit();
        $this->kreditkpmrModel = new M_risikokreditkpmr();
        $this->operasionalinherenModel = new M_risikooperasional();
        $this->operasionalkpmrModel = new M_risikooperasionalkpmr();
        $this->kepatuhaninherenModel = new M_risikokepatuhan();
        $this->kepatuhankpmrModel = new M_kepatuhankpmr();
        $this->likuiditasinherenModel = new M_likuiditasinheren();
        $this->likuiditaskpmrModel = new M_likuiditaskpmr();
        $this->reputasiinherenModel = new M_reputasiinheren();
        $this->reputasikpmrModel = new M_reputasikpmr();
        $this->stratejikinherenModel = new M_stratejikinheren();
        $this->stratejikkpmrModel = new M_stratejikkpmr();
    }

    public function generate(string $kodebpr, int $periodeId): string
    {
        $this->kodebpr = $kodebpr;
        $this->periodeId = $periodeId;

        $periodeDetail = $this->periodeModel->find($periodeId);
        $bprData = $this->infobprModel->getBprByKode($kodebpr);
        $showprofilresiko = $this->showprofilresikoModel->getByKodebprAndPeriode($kodebpr, $periodeId);

        if (!$showprofilresiko) {
            return '';
        }

        $html = $this->buildHtml($periodeDetail, $bprData, $showprofilresiko);

        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', true); // dibutuhkan untuk nomor halaman via page_text()
        $options->set('defaultFont', 'Arial');
        $options->set('isFontSubsettingEnabled', true);
        $options->set('defaultMediaType', 'print');

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    // =====================================================================
    // HELPERS - format & lookup
    // =====================================================================

    private function s($v): string
    {
        return htmlspecialchars((string) ($v ?? ''));
    }

    private function formatRupiah($angka): string
    {
        if (empty($angka)) {
            return 'Rp 0';
        }
        $number = preg_replace('/[^\d]/', '', (string) $angka);
        return 'Rp ' . number_format((float) $number, 0, ',', '.');
    }

    private function getAtmDebitLabel($v): string
    {
        $v = trim((string) $v);
        if ($v === '1')
            return 'Ya';
        if ($v === '2')
            return 'Tidak';
        return '-';
    }

    /** Palet warna disamakan dengan getBackgroundColorByRating() di export_all_pdf_zip (pdf-lib) */
    private function getRiskColor($v): string
    {
        switch ((int) $v) {
            case 1:
                return '#66B2FF'; // Biru - Sangat Rendah
            case 2:
                return '#66E566'; // Hijau - Rendah
            case 3:
                return '#FFCC66'; // Kuning - Sedang
            case 4:
                return '#FF8080'; // Orange - Tinggi
            case 5:
                return '#B34D4D'; // Merah - Sangat Tinggi
            default:
                return '#CCCCCC';
        }
    }

    private function getRiskText($v): string
    {
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
    }

    private function getPeriodeSebelumnya($semester, $tahun): array
    {
        if ($semester == 'I') {
            return ['semester' => 'II', 'tahun' => $tahun - 1];
        }
        return ['semester' => 'I', 'tahun' => $tahun];
    }

    private function fetch($model, int $faktorId): ?array
    {
        return $model->where('kodebpr', $this->kodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', $faktorId)
            ->first();
    }

    /** Sama persis dengan Showprofilresiko::getFaktorRanges() */
    private function getFaktorRanges(string $riskType, bool $isKpmr): array
    {
        switch ($riskType) {
            case 'kredit':
                return $isKpmr ? range(15, 32) : range(1, 12);
            case 'operasional':
                return $isKpmr ? range(50, 69) : range(35, 47);
            case 'kepatuhan':
                return $isKpmr ? range(83, 101) : range(62, 80);
            case 'likuiditas':
                return $isKpmr ? range(118, 134) : range(105, 114);
            case 'reputasi':
                return $isKpmr ? range(150, 167) : range(137, 147);
            case 'stratejik':
                return $isKpmr ? range(181, 198) : range(170, 178);
            default:
                return [];
        }
    }

    private function getKeteranganRows(string $riskType, int $faktorId): array
    {
        $kpmrFaktorIds = [33, 34, 70, 71, 102, 103, 135, 136, 168, 169, 199, 200];
        $isKpmr = in_array($faktorId, $kpmrFaktorIds);
        $ranges = $this->getFaktorRanges($riskType, $isKpmr);

        $modelMap = [
            'kredit' => [$this->kreditinherenModel, $this->kreditkpmrModel],
            'operasional' => [$this->operasionalinherenModel, $this->operasionalkpmrModel],
            'kepatuhan' => [$this->kepatuhaninherenModel, $this->kepatuhankpmrModel],
            'likuiditas' => [$this->likuiditasinherenModel, $this->likuiditaskpmrModel],
            'reputasi' => [$this->reputasiinherenModel, $this->reputasikpmrModel],
            'stratejik' => [$this->stratejikinherenModel, $this->stratejikkpmrModel],
        ];

        $model = $modelMap[$riskType][$isKpmr ? 1 : 0];

        return $model->where('kodebpr', $this->kodebpr)
            ->where('periode_id', $this->periodeId)
            ->whereIn('faktor1id', $ranges)
            ->orderBy('faktor1id', 'ASC')
            ->findAll();
    }

    // =====================================================================
    // MAIN BUILDER
    // =====================================================================

    private function buildHtml(array $periodeDetail, array $bprData, array $showprofilresiko): string
    {
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

        $periodeSebelum = $this->getPeriodeSebelumnya($periodeDetail['semester'], $periodeDetail['tahun']);

        $kreditI = $this->fetch($this->kreditinherenModel, 13);
        $kreditK = $this->fetch($this->kreditkpmrModel, 33);
        $kreditIB = $this->fetch($this->kreditinherenModel, 14);
        $kreditKB = $this->fetch($this->kreditkpmrModel, 34);

        $opI = $this->fetch($this->operasionalinherenModel, 48);
        $opK = $this->fetch($this->operasionalkpmrModel, 70);
        $opIB = $this->fetch($this->operasionalinherenModel, 49);
        $opKB = $this->fetch($this->operasionalkpmrModel, 71);

        $kepI = $this->fetch($this->kepatuhaninherenModel, 81);
        $kepK = $this->fetch($this->kepatuhankpmrModel, 102);
        $kepIB = $this->fetch($this->kepatuhaninherenModel, 82);
        $kepKB = $this->fetch($this->kepatuhankpmrModel, 103);

        $likI = $this->fetch($this->likuiditasinherenModel, 115);
        $likK = $this->fetch($this->likuiditaskpmrModel, 135);
        $likIB = $this->fetch($this->likuiditasinherenModel, 116);
        $likKB = $this->fetch($this->likuiditaskpmrModel, 136);

        $repI = $this->fetch($this->reputasiinherenModel, 148);
        $repK = $this->fetch($this->reputasikpmrModel, 168);
        $repIB = $this->fetch($this->reputasiinherenModel, 149);
        $repKB = $this->fetch($this->reputasikpmrModel, 169);

        $strI = $this->fetch($this->stratejikinherenModel, 179);
        $strK = $this->fetch($this->stratejikkpmrModel, 199);
        $strIB = $this->fetch($this->stratejikinherenModel, 180);
        $strKB = $this->fetch($this->stratejikkpmrModel, 200);

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
                    '12' => 'Desember',
                ];
                $tanggal = $d->format('d') . ' ' . $bl[$d->format('m')] . ' ' . $d->format('Y');
            }
        }

        $s = fn($v) => $this->s($v);
        $spr = $showprofilresiko;
        $sm = $s($periodeDetail['semester']);
        $th = $s($periodeDetail['tahun']);
        $smB = $s($periodeSebelum['semester']);
        $thB = $s($periodeSebelum['tahun']);

        // ---------- HEADER ----------
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

        // PROFIL RISIKO (A)
        $profilRows = [
            ['Periode', "Semester {$sm} Tahun {$th}"],
            ['Nama BPR', $bprData['namabpr'] ?? ''],
            ['Alamat', $bprData['alamat'] ?? ''],
            ['Nomor Telepon', $bprData['nomor'] ?? ''],
            ['Modal Inti', $this->formatRupiah($periodeDetail['modalinti'] ?? '')],
            ['Total Aset', $this->formatRupiah($periodeDetail['totalaset'] ?? '')],
            ['Jumlah Kantor Cabang', $periodeDetail['kantorcabang'] ?? ''],
            ['Kegiatan sebagai penerbit kartu ATM atau kartu debit', $this->getAtmDebitLabel($periodeDetail['atmdebit'] ?? '')],
        ];
        $profilHtml = '';
        foreach ($profilRows as [$label, $value]) {
            $profilHtml .= '<tr>
            <td style="width:230px;padding:3px 6px;font-size:8.5pt;vertical-align:top">' . htmlspecialchars($label) . '</td>
            <td style="width:12px;padding:3px 2px;vertical-align:top;font-size:8.5pt">:</td>
            <td style="padding:3px 6px;font-size:8.5pt;vertical-align:top">' . htmlspecialchars($value) . '</td>
        </tr>';
        }

        // TABEL PENILAIAN
        $risikoRow = function (string $label, $iC, $kC, $nC, $iP, $kP, $nP): string {
            $cell = fn($v) => '<td style="text-align:center;font-weight:bold;font-size:8.5pt;'
                . 'background:' . $this->getRiskColor($v) . ';border:1px solid #000;padding:6px 3px;vertical-align:middle">'
                . ($v ?? '') . '</td>';
            return '<tr>'
                . '<td style="border:1px solid #000;padding:6px 8px;font-size:8.5pt;vertical-align:middle">' . htmlspecialchars($label) . '</td>'
                . $cell($iC) . $cell($kC) . $cell($nC)
                . $cell($iP) . $cell($kP) . $cell($nP)
                . '</tr>';
        };

        $tableRows = $risikoRow('Risiko Kredit', $kreditI['penilaiankredit'] ?? null, $kreditK['penilaiankredit'] ?? null, $spr['nkredit'] ?? null, $kreditIB['penilaiankredit'] ?? null, $kreditKB['penilaiankredit'] ?? null, $spr['nbelumkredit'] ?? null);
        $tableRows .= $risikoRow('Risiko Operasional', $opI['penilaiankredit'] ?? null, $opK['penilaiankredit'] ?? null, $spr['noperasional'] ?? null, $opIB['penilaiankredit'] ?? null, $opKB['penilaiankredit'] ?? null, $spr['nbelumoperasional'] ?? null);
        $tableRows .= $risikoRow('Risiko Kepatuhan', $kepI['penilaiankredit'] ?? null, $kepK['penilaiankredit'] ?? null, $spr['nkepatuhan'] ?? null, $kepIB['penilaiankredit'] ?? null, $kepKB['penilaiankredit'] ?? null, $spr['nbelumkepatuhan'] ?? null);
        $tableRows .= $risikoRow('Risiko Likuiditas', $likI['penilaiankredit'] ?? null, $likK['penilaiankredit'] ?? null, $spr['nlikuiditas'] ?? null, $likIB['penilaiankredit'] ?? null, $likKB['penilaiankredit'] ?? null, $spr['nbelumlikuiditas'] ?? null);

        if (!empty($repI)) {
            $tableRows .= $risikoRow('Risiko Reputasi', $repI['penilaiankredit'] ?? null, $repK['penilaiankredit'] ?? null, $spr['nreputasi'] ?? null, $repIB['penilaiankredit'] ?? null, $repKB['penilaiankredit'] ?? null, $spr['nbelumreputasi'] ?? null);
        }
        if (!empty($strI)) {
            $tableRows .= $risikoRow('Risiko Stratejik', $strI['penilaiankredit'] ?? null, $strK['penilaiankredit'] ?? null, $spr['nstratejik'] ?? null, $strIB['penilaiankredit'] ?? null, $strKB['penilaiankredit'] ?? null, $spr['nbelumstratejik'] ?? null);
        }

        $pcC = $this->getRiskColor($spr['ntotalrisk'] ?? null);
        $pcP = $this->getRiskColor($spr['nbelumtotalrisk'] ?? null);
        $tableRows .= '<tr style="background:#999999">
        <td colspan="3" style="border:1px solid #000;padding:6px 8px;font-weight:bold;font-size:8.5pt;color:#fff;vertical-align:middle">Peringkat Risiko</td>
        <td style="text-align:center;font-weight:bold;font-size:10pt;background:' . $pcC . ';border:1px solid #000;padding:6px;vertical-align:middle">' . ($spr['ntotalrisk'] ?? '') . '</td>
        <td colspan="2" style="border:1px solid #000;background:#999999"></td>
        <td style="text-align:center;font-weight:bold;font-size:10pt;background:' . $pcP . ';border:1px solid #000;padding:6px;vertical-align:middle">' . ($spr['nbelumtotalrisk'] ?? '') . '</td>
    </tr>';

        // ANALISIS
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
                . ': Risiko Inheren peringkat ' . $ni . ' (' . $this->getRiskText($ni)
                . '), Risiko KPMR peringkat ' . $nk . ' (' . $this->getRiskText($nk) . ')</li>';
        }

        $kesimpulanHtml = '';
        if (!empty($spr['kesimpulan'])) {
            $kesimpulanHtml = '
        <div style="border:1px solid #000;padding:10px 12px;margin-top:12px;font-size:9pt">
            <div style="font-weight:bold;margin-bottom:8px;font-size:9.5pt">Kesimpulan</div>
            <div style="font-size:9pt;line-height:1.5">' . nl2br(htmlspecialchars($spr['kesimpulan'])) . '</div>
        </div>';
        }

        // HALAMAN B: ANALISIS PER JENIS RISIKO
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

        $risikoNilai = [
            'kredit' => ['iC' => $kreditI['penilaiankredit'] ?? null, 'kC' => $kreditK['penilaiankredit'] ?? null, 'n' => $spr['nkredit'] ?? null],
            'operasional' => ['iC' => $opI['penilaiankredit'] ?? null, 'kC' => $opK['penilaiankredit'] ?? null, 'n' => $spr['noperasional'] ?? null],
            'kepatuhan' => ['iC' => $kepI['penilaiankredit'] ?? null, 'kC' => $kepK['penilaiankredit'] ?? null, 'n' => $spr['nkepatuhan'] ?? null],
            'likuiditas' => ['iC' => $likI['penilaiankredit'] ?? null, 'kC' => $likK['penilaiankredit'] ?? null, 'n' => $spr['nlikuiditas'] ?? null],
            'reputasi' => ['iC' => $repI['penilaiankredit'] ?? null, 'kC' => $repK['penilaiankredit'] ?? null, 'n' => $spr['nreputasi'] ?? null],
            'stratejik' => ['iC' => $strI['penilaiankredit'] ?? null, 'kC' => $strK['penilaiankredit'] ?? null, 'n' => $spr['nstratejik'] ?? null],
        ];

        $halamanBHtml = '';
        foreach ($risikoArray as $idx => $risiko) {
            $nilaiRisiko = $risikoNilai[$risiko['key']];
            $tingkat = $nilaiRisiko['n'];

            $inherenRows = $this->getKeteranganRows($risiko['key'], $risiko['iF']);
            $kpmrRows = $this->getKeteranganRows($risiko['key'], $risiko['kF']);

            $inherenList = '';
            if (!empty($inherenRows)) {
                foreach ($inherenRows as $row) {
                    $inherenList .= '<li style="margin-bottom:4px;font-size:9pt;line-height:1.5">' . htmlspecialchars($row['keterangan'] ?? '') . '</li>';
                }
                $inherenList = '<ol style="margin:4px 0 0 22px;padding:0;font-size:9pt">' . $inherenList . '</ol>';
            } else {
                $inherenList = '<p style="font-size:9pt;margin:4px 0 0 0">Tingkat Risiko Inheren: '
                    . ($nilaiRisiko['iC'] ?? '-') . ' (' . $this->getRiskText($nilaiRisiko['iC']) . ')</p>';
            }

            $kpmrList = '';
            if (!empty($kpmrRows)) {
                foreach ($kpmrRows as $row) {
                    $kpmrList .= '<li style="margin-bottom:4px;font-size:9pt;line-height:1.5">' . htmlspecialchars($row['keterangan'] ?? '') . '</li>';
                }
                $kpmrList = '<ol style="margin:4px 0 0 22px;padding:0;font-size:9pt">' . $kpmrList . '</ol>';
            } else {
                $kpmrList = '<p style="font-size:9pt;margin:4px 0 0 0">Tingkat KPMR: '
                    . ($nilaiRisiko['kC'] ?? '-') . ' (' . $this->getRiskText($nilaiRisiko['kC']) . ')</p>';
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

            <div style="border:1px solid #000;padding:12px 14px;font-size:9pt;margin-bottom:10px">
                <div style="font-weight:bold;font-size:10pt;margin-bottom:10px">Analisis</div>

                <div style="font-weight:bold;font-size:9.5pt;margin-bottom:6px">1. Tingkat Risiko:</div>
                <p style="margin:0 0 12px 16px;font-size:9pt">Peringkat ' . ($tingkat ?? '-')
                . ', ' . $this->getRiskText($tingkat) . '</p>

                <div style="font-weight:bold;font-size:9.5pt;margin-bottom:6px">2. Risiko Inheren:</div>
                <div style="margin-left:10px">' . $inherenList . '</div>
            </div>

            <div style="border:1px solid #000;padding:12px 14px;font-size:9pt">
                <div style="font-weight:bold;font-size:9.5pt;margin-bottom:6px">3. Kualitas Penerapan Manajemen Risiko:</div>
                <div style="margin-left:10px">' . $kpmrList . '</div>
            </div>
        </div>';
        }

        // HALAMAN TANDA TANGAN (Direktur Utama + Direktur)
        $ttdHtml = '
    <div style="page-break-before:always">
        ' . $headerHtml . '
        <div style="margin-top:70px;text-align:center;font-size:10pt">
            <p style="margin-bottom:6px">' . $s($spr['lokasi'] ?? '') . ', ' . $s($tanggal) . '</p>
            <p style="font-weight:bold;font-size:11pt;margin-bottom:0">' . $s($bprData['namabpr']) . '</p>

            <p style="font-weight:bold;margin-top:20px;margin-bottom:0">Menyetujui</p>

            <table class="no-border" style="margin-top:0;width:100%">
                <tr>
                    <td style="width:50%;text-align:center;vertical-align:top;padding-top:0">
                        <div style="height:80px"></div>
                        <div style="font-weight:bold;border-bottom:1px solid #000;
                                    padding:0 20px 4px 20px;display:inline-block;font-size:10.5pt">
                            ' . $s($spr['dirkep'] ?? '') . '
                        </div>
                        <div style="margin-top:5px;font-size:10pt">Direktur</div>
                    </td>
                    <td style="width:50%;text-align:center;vertical-align:top;padding-top:0">
                        <div style="height:80px"></div>
                        <div style="font-weight:bold;border-bottom:1px solid #000;
                                    padding:0 20px 4px 20px;display:inline-block;font-size:10.5pt">
                            ' . $s($spr['pe'] ?? '') . '
                        </div>
                        <div style="margin-top:5px;font-size:10pt">Pejabat Eksekutif Manajemen Risiko</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>';

        // DOKUMEN LENGKAP
        $html = '<!DOCTYPE html>
        <html>
        <head>
        <meta charset="UTF-8">
        <style>
        @page { margin: 30px 30px 30px 30px; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 9pt; color: #000; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000; }
        .no-border td { border: none; }
        .title { text-align: center; font-weight: bold; font-size: 13pt; margin-bottom: 15px; }
        .section-title { font-weight: bold; font-size: 10.5pt; margin-bottom: 8px; }
        .box { border: 1px solid #000; padding: 10px; margin-top: 10px; }
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
        <th style="font-size:7pt">Inheren<br>Tingkat Risiko</th>
        <th style="font-size:7pt">Risiko Manajemen<br>Penerapan Tingkat<br>Kualitas</th>
        <th style="font-size:7pt">Tingkat Risiko</th>
        <th style="font-size:7pt">Inheren<br>Tingkat Risiko</th>
        <th style="font-size:7pt">Risiko Manajemen<br>Penerapan Tingkat<br>Kualitas</th>
        <th style="font-size:7pt">Tingkat Risiko</th>
        </tr>
        </thead>
        <tbody>
        ' . $tableRows . '
        </tbody>
        </table>

        <p style="text-align:center;font-size:8pt;color:#555;margin-bottom:12px">
        Keterangan Peringkat: 1 (Sangat Rendah), 2 (Rendah), 3 (Sedang), 4 (Tinggi), 5 (Sangat Tinggi)
        </p>

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

        <script type="text/php">
        if (isset($pdf)) {
            $font  = $fontMetrics->get_font("Arial", "normal");
            $size  = 9;
            $text  = "Halaman {PAGE_NUM} dari {PAGE_COUNT}";
            $width = $fontMetrics->get_text_width($text, $font, $size);
            $x     = ($pdf->get_width() - $width) / 2;
            $y     = $pdf->get_height() - 30;
            $pdf->page_text($x, $y, $text, $font, $size, array(0, 0, 0));
        }
        </script>

        </body>
        </html>';

        return $html;
    }
}