<?php

namespace App\Libraries;

use Dompdf\Dompdf;
use Dompdf\Options;

class LaporanTKSPdfGenerator
{
    protected $dompdf;
    protected $data;

    public function __construct()
    {
        // Tambahkan limit untuk mencegah timeout
        set_time_limit(60);
        ini_set('memory_limit', '256M');

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false); // Nonaktifkan remote untuk keamanan
        $options->set('defaultFont', 'DejaVu Sans');

        $this->dompdf = new Dompdf($options);
    }

    /**
     * Set data for PDF generation
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Generate PDF and return as string
     */
    public function generate()
    {
        $html = $this->buildHTML();

        $this->dompdf->loadHtml($html);
        $this->dompdf->setPaper('A4', 'portrait');
        $this->dompdf->render();

        return $this->dompdf->output();
    }

    /**
     * Build complete HTML for PDF
     */
    private function buildHTML()
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.3;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 3px solid #000;
            position: relative;
        }
        
        .header-container {
            display: table;
            width: 100%;
        }
        
        .header-logo {
            display: table-cell;
            width: 80px;
            vertical-align: middle;
            text-align: left;
        }
        
        .header-logo img {
            max-width: 70px;
            max-height: 70px;
            display: block;
        }
        
        .header-info {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
        }
        
        .header-info .company-name {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .header-info .address,
        .header-info .contact {
            font-size: 8pt;
            margin: 2px 0;
            word-wrap: break-word;
        }
        
        .header-info .contact {
            font-size: 8pt;
            margin: 2px 0;
            margin-right: 80
        }
        
        .title {
            text-align: center;
            font-size: 13pt;
            font-weight: bold;
            margin: 25px 0 20px 0;
            line-height: 1.4;
        }
        
        .info-section {
            margin: 15px 0 20px 0;
        }
        
        .info-section table {
            width: 60%;
            border: none;
        }
        
        .info-section td {
            padding: 2px 0;
            font-size: 9pt;
            border: none;
        }
        
        .info-section td:first-child {
            width: 80px;
            font-weight: normal;
        }
        
        .info-section td:nth-child(2) {
            width: 15px;
        }
        
        .main-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 8pt;
        }

        .main-table th,
        .main-table td {
            border: 1px solid #000;
            padding: 5px 3px;
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: break-word;
            white-space: normal;
        }

        .main-table th {
            background-color: #d3d3d3;
            color: #000000;
            font-weight: bold;
            font-size: 7.5pt;
            line-height: 1.2;
        }

        .main-table td.left {
            text-align: left;
            padding-left: 6px;
            word-break: break-word;
        }

        .main-table td.sub-item {
            padding-left: 12px;
        }
        
        .main-table .header-row {
            background-color: #d3d3d3;
            color: #000000;
        }
        
        .main-table td.empty-gray {
            background-color: #d3d3d3;
        }
        
        .peringkat-1 { background-color: #90EE90; }
        .peringkat-2 { background-color: #ADFF2F; }
        .peringkat-3 { background-color: #FFFF00; }
        .peringkat-4 { background-color: #FFA500; }
        .peringkat-5 { background-color: #FF6347; }
        
        .komposit-row {
            background-color: #87CEEB;
            font-weight: bold;
        }
        
        .nilai-komposit-row {
            background-color: #ffffff;
        }
        
        .analysis-box {
            border: 2px solid #000;
            padding: 12px;
            margin: 20px 0;
            background-color: #ffffff;
            page-break-before: auto;
            page-break-after: auto;
            page-break-inside: avoid;
        }
        
        .analysis-header {
            background-color: #87CEEB;
            padding: 8px;
            margin: -12px -12px 12px -12px;
            text-align: center;
            font-weight: bold;
            font-size: 10pt;
        }
        
        .analysis-section {
            margin: 8px 0;
            text-align: justify;
        }
        
        .analysis-section .section-title {
            font-weight: bold;
            margin-bottom: 4px;
            display: block;
        }
        
        .analysis-section .section-content {
            text-indent: 0;
            line-height: 1.5;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        
        .signature {
            margin-top: 40px;
            page-break-inside: avoid;
        }
        
        .signature .location {
            margin-bottom: 3px;
            font-size: 9pt;
            text-align: center;
        }
        
        .signature .company {
            font-weight: bold;
            margin: 3px 0 20px 0;
            font-size: 9pt;
            text-align: center;
        }
        
        .signature-container {
            display: table;
            width: 100%;
            margin-top: 10px;
        }
        
        .signature-left {
            display: table-cell;
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding-right: 20px;
        }
        
        .signature-right {
            display: table-cell;
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding-left: 20px;
        }
        
        .signature-left .name,
        .signature-right .name {
            font-weight: bold;
            margin-top: 70px;
            margin-bottom: 0;
            text-decoration: underline;
            font-size: 10pt;
        }
        
        .signature-left .position,
        .signature-right .position {
            font-size: 9pt;
            margin-top: 2px;
        }
        
        .bobot-red {
            color: #000000;
        }
    </style>
</head>
<body>';

        $html .= $this->buildHeader();
        $html .= $this->buildTitleSection();
        $html .= $this->buildInfoSection();
        $html .= $this->buildTKSTable();
        $html .= $this->buildAnalysis();
        $html .= $this->buildSignature();

        $html .= '</body></html>';

        return $html;
    }

    /**
     * Build header section
     */
    private function buildHeader()
    {
        $bpr = $this->data['bpr'];

        $html = '<div class="header">';
        $html .= '<div class="header-container">';

        // Logo
        $html .= '<div class="header-logo">';
        if (!empty($bpr['logo'])) {
            $html .= '<img src="' . $bpr['logo'] . '" alt="Logo">';
        }
        $html .= '</div>';

        // Info
        $html .= '<div class="header-info">';
        $html .= '<div class="company-name">' . htmlspecialchars($bpr['namabpr']) . '</div>';
        $html .= '<div class="address">' . htmlspecialchars($bpr['alamat']) . '</div>';
        $html .= '<div class="contact">Telepon: ' . htmlspecialchars($bpr['nomor']) . '</div>';

        $website = !empty($bpr['webbpr']) ? htmlspecialchars($bpr['webbpr']) : '-';
        $email = !empty($bpr['email']) ? htmlspecialchars($bpr['email']) : '-';
        $html .= '<div class="contact">Website: ' . $website . ', Email: ' . $email . '</div>';
        $html .= '</div>';

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Build title section
     */
    private function buildTitleSection()
    {
        $html = '<div class="title">';
        $html .= 'LAPORAN PENILAIAN SENDIRI<br>';
        $html .= 'TINGKAT KESEHATAN';
        $html .= '</div>';

        return $html;
    }

    /**
     * Build info section
     */
    private function buildInfoSection()
    {
        $periode = $this->data['periode'];
        $bpr = $this->data['bpr'];

        $html = '<div class="info-section">';
        $html .= '<table>';
        $html .= '<tr>';
        $html .= '<td>Nama BPR</td>';
        $html .= '<td>:</td>';
        $html .= '<td>' . htmlspecialchars($bpr['namabpr']) . '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td>Posisi</td>';
        $html .= '<td>:</td>';
        $html .= '<td>Bulan ' . htmlspecialchars($periode['bulan']) . ' Tahun ' . htmlspecialchars($periode['tahun']) . '</td>';
        $html .= '</tr>';
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Build TKS table
     */
    private function buildTKSTable()
    {
        $tks = $this->data['tks'];
        $periode = $this->data['periode'];

        $prevSemester = $periode['periodeSebelumBulan'] === 'Desember' ? '2' : '1';
        $prevTahun = $periode['periodeSebelumTahun'];

        $html = '<table class="main-table">';

        // Header row 1
        $html .= '<tr class="header-row">';
        $html .= '<th rowspan="2" style="width: 23%;">Faktor / Komponen</th>';
        $html .= '<th colspan="4" style="width: 38.5%;">Penilaian Posisi Laporan<br>Bulan ' . htmlspecialchars($periode['bulan']) . ' Tahun ' . htmlspecialchars($periode['tahun']) . '</th>';
        $html .= '<th colspan="4" style="width: 38.5%;">Penilaian Posisi Sebelumnya<br>Semester ' . $prevSemester . ' Tahun ' . $prevTahun . '</th>';
        $html .= '</tr>';

        // Header row 2
        $html .= '<tr class="header-row">';
        $html .= '<th style="width: 9.5%;">Rasio<br>(%)</th>';
        $html .= '<th style="width: 9.5%;">Peringkat<br>(a)</th>';
        $html .= '<th style="width: 9.5%;">Bobot<br>(b)</th>';
        $html .= '<th style="width: 10%;">Nilai Faktor<br>(c) = (a) x (b)</th>';
        $html .= '<th style="width: 9.5%;">Rasio<br>(%)</th>';
        $html .= '<th style="width: 9.5%;">Peringkat<br>(a)</th>';
        $html .= '<th style="width: 9.5%;">Bobot<br>(b)</th>';
        $html .= '<th style="width: 9%;">Nilai Faktor<br>(c) = (a) x (b)</th>';
        $html .= '</tr>';

        // Data rows
        $rows = [
            [
                'name' => 'Profil Risiko',
                'isSub' => false,
                'current' => [
                    'rasio' => '',
                    'peringkat' => $tks['profilrisiko']['peringkatCurrent'] ?? '',
                    'bobot' => '25%',
                    'nilai' => $tks['profilrisiko']['nilaifaktorCurrent'] ?? ''
                ],
                'previous' => [
                    'rasio' => '',
                    'peringkat' => $tks['profilrisiko']['peringkatPrevious'] ?? '',
                    'bobot' => '25%',
                    'nilai' => $tks['profilrisiko']['nilaifaktorPrevious'] ?? ''
                ]
            ],
            [
                'name' => 'Tata Kelola',
                'isSub' => false,
                'current' => [
                    'rasio' => '',
                    'peringkat' => $tks['tatakelola']['peringkatCurrent'] ?? '',
                    'bobot' => '30%',
                    'nilai' => $tks['tatakelola']['nilaifaktorCurrent'] ?? ''
                ],
                'previous' => [
                    'rasio' => '',
                    'peringkat' => $tks['tatakelola']['peringkatPrevious'] ?? '',
                    'bobot' => '30%',
                    'nilai' => $tks['tatakelola']['nilaifaktorPrevious'] ?? ''
                ]
            ],
            [
                'name' => 'Rentabilitas',
                'isSub' => false,
                'current' => [
                    'rasio' => '',
                    'peringkat' => $tks['rentabilitas']['peringkatCurrent'] ?? '',
                    'bobot' => '15%',
                    'nilai' => $tks['rentabilitas']['nilaifaktorCurrent'] ?? ''
                ],
                'previous' => [
                    'rasio' => '',
                    'peringkat' => $tks['rentabilitas']['peringkatPrevious'] ?? '',
                    'bobot' => '15%',
                    'nilai' => $tks['rentabilitas']['nilaifaktorPrevious'] ?? ''
                ]
            ],
            [
                'name' => '1. Return on Asset (ROA)',
                'isSub' => true,
                'current' => [
                    'rasio' => $tks['rentabilitas']['roa']['rasioCurrent'] ?? '',
                    'peringkat' => $tks['rentabilitas']['roa']['peringkatCurrent'] ?? '',
                    'bobot' => '',
                    'nilai' => ''
                ],
                'previous' => [
                    'rasio' => $tks['rentabilitas']['roa']['rasioPrevious'] ?? '',
                    'peringkat' => $tks['rentabilitas']['roa']['peringkatPrevious'] ?? '',
                    'bobot' => '',
                    'nilai' => ''
                ]
            ],
            [
                'name' => '2. Rasio Beban Operasional terhadap Pendapatan Operasional (BOPO)',
                'isSub' => true,
                'current' => [
                    'rasio' => $tks['rentabilitas']['bopo']['rasioCurrent'] ?? '',
                    'peringkat' => $tks['rentabilitas']['bopo']['peringkatCurrent'] ?? '',
                    'bobot' => '',
                    'nilai' => ''
                ],
                'previous' => [
                    'rasio' => $tks['rentabilitas']['bopo']['rasioPrevious'] ?? '',
                    'peringkat' => $tks['rentabilitas']['bopo']['peringkatPrevious'] ?? '',
                    'bobot' => '',
                    'nilai' => ''
                ]
            ],
            [
                'name' => '3. Net Interest Margin (NIM)',
                'isSub' => true,
                'current' => [
                    'rasio' => $tks['rentabilitas']['nim']['rasioCurrent'] ?? '',
                    'peringkat' => $tks['rentabilitas']['nim']['peringkatCurrent'] ?? '',
                    'bobot' => '',
                    'nilai' => ''
                ],
                'previous' => [
                    'rasio' => $tks['rentabilitas']['nim']['rasioPrevious'] ?? '',
                    'peringkat' => $tks['rentabilitas']['nim']['peringkatPrevious'] ?? '',
                    'bobot' => '',
                    'nilai' => ''
                ]
            ],
            [
                'name' => 'Permodalan',
                'isSub' => false,
                'current' => [
                    'rasio' => '',
                    'peringkat' => $tks['permodalan']['peringkatCurrent'] ?? '',
                    'bobot' => '30%',
                    'nilai' => $tks['permodalan']['nilaifaktorCurrent'] ?? ''
                ],
                'previous' => [
                    'rasio' => '',
                    'peringkat' => $tks['permodalan']['peringkatPrevious'] ?? '',
                    'bobot' => '30%',
                    'nilai' => $tks['permodalan']['nilaifaktorPrevious'] ?? ''
                ]
            ],
            [
                'name' => '1. Rasio KPMM',
                'isSub' => true,
                'current' => [
                    'rasio' => $tks['permodalan']['kpmm']['rasioCurrent'] ?? '',
                    'peringkat' => $tks['permodalan']['kpmm']['peringkatCurrent'] ?? '',
                    'bobot' => '',
                    'nilai' => ''
                ],
                'previous' => [
                    'rasio' => $tks['permodalan']['kpmm']['rasioPrevious'] ?? '',
                    'peringkat' => $tks['permodalan']['kpmm']['peringkatPrevious'] ?? '',
                    'bobot' => '',
                    'nilai' => ''
                ]
            ],
            [
                'name' => '2. Rasio MIAPB',
                'isSub' => true,
                'current' => [
                    'rasio' => $tks['permodalan']['miapb']['rasioCurrent'] ?? '',
                    'peringkat' => $tks['permodalan']['miapb']['peringkatCurrent'] ?? '',
                    'bobot' => '',
                    'nilai' => ''
                ],
                'previous' => [
                    'rasio' => $tks['permodalan']['miapb']['rasioPrevious'] ?? '',
                    'peringkat' => $tks['permodalan']['miapb']['peringkatPrevious'] ?? '',
                    'bobot' => '',
                    'nilai' => ''
                ]
            ]
        ];

        foreach ($rows as $row) {
            $html .= '<tr>';
            $tdClass = $row['isSub'] ? 'left sub-item' : 'left';
            $html .= '<td class="' . $tdClass . '">' . htmlspecialchars($row['name']) . '</td>';

            // Current data - Rasio
            $rasioClass = empty($row['current']['rasio']) ? 'empty-gray' : '';
            $html .= '<td class="' . $rasioClass . '">' . htmlspecialchars($row['current']['rasio']) . '</td>';

            // Current data - Peringkat
            $peringkatClass = $this->getPeringkatClass($row['current']['peringkat']);
            if (empty($row['current']['peringkat'])) {
                $peringkatClass = 'empty-gray';
            }
            $html .= '<td class="' . $peringkatClass . '">' . htmlspecialchars($row['current']['peringkat']) . '</td>';

            // Current data - Bobot
            $bobotClass = '';
            if (!empty($row['current']['bobot'])) {
                $bobotClass = 'bobot-red';
            } elseif (empty($row['current']['bobot'])) {
                $bobotClass = 'empty-gray';
            }
            $html .= '<td class="' . $bobotClass . '">' . htmlspecialchars($row['current']['bobot']) . '</td>';

            // Current data - Nilai Faktor
            $nilaiFaktorClass = empty($row['current']['nilai']) ? 'empty-gray' : '';
            $html .= '<td class="' . $nilaiFaktorClass . '">' . htmlspecialchars($row['current']['nilai']) . '</td>';

            // Previous data - Rasio
            $rasioClass = empty($row['previous']['rasio']) ? 'empty-gray' : '';
            $html .= '<td class="' . $rasioClass . '">' . htmlspecialchars($row['previous']['rasio']) . '</td>';

            // Previous data - Peringkat
            $peringkatClass = $this->getPeringkatClass($row['previous']['peringkat']);
            if (empty($row['previous']['peringkat'])) {
                $peringkatClass = 'empty-gray';
            }
            $html .= '<td class="' . $peringkatClass . '">' . htmlspecialchars($row['previous']['peringkat']) . '</td>';

            // Previous data - Bobot
            $bobotClass = '';
            if (!empty($row['previous']['bobot'])) {
                $bobotClass = 'bobot-red';
            } elseif (empty($row['previous']['bobot'])) {
                $bobotClass = 'empty-gray';
            }
            $html .= '<td class="' . $bobotClass . '">' . htmlspecialchars($row['previous']['bobot']) . '</td>';

            // Previous data - Nilai Faktor
            $nilaiFaktorClass = empty($row['previous']['nilai']) ? 'empty-gray' : '';
            $html .= '<td class="' . $nilaiFaktorClass . '">' . htmlspecialchars($row['previous']['nilai']) . '</td>';

            $html .= '</tr>';
        }

        // Nilai Komposit
        $html .= '<tr class="nilai-komposit-row">';
        $html .= '<td class="left" colspan="4"><strong>Nilai Komposit</strong></td>';
        $html .= '<td><strong>' . htmlspecialchars($tks['komposit']['nilaiCurrent'] ?? '') . '</strong></td>';
        $html .= '<td class="left" colspan="3"><strong>Nilai Komposit</strong></td>';
        $html .= '<td><strong>' . htmlspecialchars($tks['komposit']['nilaiPrevious'] ?? '') . '</strong></td>';
        $html .= '</tr>';

        // Peringkat Komposit
        $html .= '<tr class="komposit-row">';
        $html .= '<td class="left" colspan="4"><strong>Peringkat Komposit</strong></td>';
        $html .= '<td class="' . $this->getPeringkatClass($tks['komposit']['peringkatCurrent'] ?? '') . '"><strong>' . $this->getPeringkatText($tks['komposit']['peringkatCurrent'] ?? '') . '</strong></td>';
        $html .= '<td class="left" colspan="3"><strong>Peringkat Komposit</strong></td>';
        $html .= '<td class="' . $this->getPeringkatClass($tks['komposit']['peringkatPrevious'] ?? '') . '"><strong>' . $this->getPeringkatText($tks['komposit']['peringkatPrevious'] ?? '') . '</strong></td>';
        $html .= '</tr>';

        $html .= '</table>';

        return $html;
    }

    /**
     * Build analysis section
     */
    private function buildAnalysis()
    {
        $analisis = $this->data['analisis'];

        $sections = [
            ['title' => '1. Kesimpulan Tingkat Kesehatan', 'content' => $analisis['kesimpulan'] ?? ''],
            ['title' => '2. Profil Risiko', 'content' => $analisis['profilrisiko'] ?? ''],
            ['title' => '3. Tata Kelola', 'content' => $analisis['tatakelola'] ?? ''],
            ['title' => '4. Rentabilitas', 'content' => $analisis['rentabilitas'] ?? ''],
            ['title' => '5. Permodalan', 'content' => $analisis['permodalan'] ?? '']
        ];

        $html = '<div class="analysis-box">';
        $html .= '<div class="analysis-header">Analisis Posisi Laporan</div>';

        foreach ($sections as $section) {
            $html .= '<div class="analysis-section">';
            $html .= '<span class="section-title">' . htmlspecialchars($section['title']) . '</span>';
            $html .= '<div class="section-content">' . nl2br(htmlspecialchars($section['content'])) . '</div>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Build signature section
     */
    private function buildSignature()
    {
        $profil = $this->data['profil'];
        $bpr = $this->data['bpr'];

        $tanggal = $this->formatDateIndonesia($profil['tanggal'] ?? date('Y-m-d'));
        $lokasi = htmlspecialchars($profil['lokasi'] ?? '-');
        $dirkep = htmlspecialchars($profil['dirkep'] ?? 'Nama Direktur');
        $pe = htmlspecialchars($profil['pe'] ?? 'Nama PE');

        $html = '<div class="signature">';
        $html .= '<div class="location">' . $lokasi . ', ' . $tanggal . '</div>';
        $html .= '<div class="company">' . htmlspecialchars($bpr['namabpr']) . '</div>';

        $html .= '<div class="signature-container">';

        // Direktur (Kiri)
        $html .= '<div class="signature-left">';
        $html .= '<div class="name">' . $dirkep . '</div>';
        $html .= '<div class="position">Direktur</div>';
        $html .= '</div>';

        // PE (Kanan)
        $html .= '<div class="signature-right">';
        $html .= '<div class="name">' . $pe . '</div>';
        $html .= '<div class="position">Pejabat Eksekutif (PE)</div>';
        $html .= '</div>';

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Get peringkat CSS class
     */
    private function getPeringkatClass($peringkat)
    {
        if (!$peringkat || $peringkat === '')
            return '';

        $peringkat = intval($peringkat);
        if ($peringkat < 1 || $peringkat > 5)
            return '';

        return 'peringkat-' . $peringkat;
    }

    /**
     * Get peringkat text with description
     */
    private function getPeringkatText($peringkat)
    {
        if (!$peringkat || $peringkat === '')
            return '';

        $texts = [
            1 => '1 (Sangat Sehat)',
            2 => '2 (Sehat)',
            3 => '3 (Cukup Sehat)',
            4 => '4 (Kurang Sehat)',
            5 => '5 (Tidak Sehat)'
        ];

        $p = intval($peringkat);
        return $texts[$p] ?? strval($peringkat);
    }

    /**
     * Format date to Indonesian format
     */
    private function formatDateIndonesia($date)
    {
        if (!$date || $date === '0000-00-00')
            return '';

        try {
            $dateObj = new \DateTime($date);
            $months = [
                1 => 'Januari',
                2 => 'Februari',
                3 => 'Maret',
                4 => 'April',
                5 => 'Mei',
                6 => 'Juni',
                7 => 'Juli',
                8 => 'Agustus',
                9 => 'September',
                10 => 'Oktober',
                11 => 'November',
                12 => 'Desember'
            ];

            $day = $dateObj->format('d');
            $month = $months[(int) $dateObj->format('m')];
            $year = $dateObj->format('Y');

            return $day . ' ' . $month . ' ' . $year;
        } catch (\Exception $e) {
            return $date;
        }
    }
}