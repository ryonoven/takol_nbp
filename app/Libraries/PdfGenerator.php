<?php
namespace App\Libraries;

use FPDF;

class PdfGenerator extends FPDF
{
    protected $headerData = [];
    protected $isCoverPage = true;

    public function setHeaderData(array $data)
    {
        $this->headerData = $data;
    }

    public function generateFullReport(array $penjelasanData, array $tgjwbdirData, array $infoData, array $tgjwbdekomData, array $tgjwbkomiteData, array $strukturkomiteData, array $sahamdirdekomData, array $shmusahadirdekomData, array $shmdirdekomlainData, array $keuangandirdekompshm, array $keluargadirdekompshm, array $paketkebijakandirdekom, array $rasiogaji, array $rapat, array $kehadirandekom, array $fraudinternal, array $masalahhukum, array $transaksikepentingan, array $danasosial)
    {
        $this->AddPage();
        $this->SetAutoPageBreak(true);
        $this->generatePenjelasanUmum($penjelasanData, $infoData);
        $this->AliasNbPages();

        $this->AddPage();
        $this->generateTanggungJawabDireksi($tgjwbdirData, $infoData);

        $this->AddPage();
        $this->generateTanggungJawabDekom($tgjwbdekomData, $infoData);

        $this->AddPage();
        $this->generateTanggungJawabKomite($tgjwbkomiteData, $infoData);

        $this->AddPage();
        $this->generateStrukturKomite($strukturkomiteData, $infoData);

        $this->AddPage();
        $this->generateSahamdirdekom($sahamdirdekomData, $infoData);

        $this->AddPage();
        $this->generateUsahadirdekom($shmusahadirdekomData, $infoData);

        $this->AddPage();
        $this->generateDirdekomlain($shmdirdekomlainData, $infoData);

        $this->AddPage();
        $this->generateKeuangan($keuangandirdekompshm, $infoData);

        $this->AddPage();
        $this->generateKeluarga($keluargadirdekompshm, $infoData);

        $this->AddPage();
        $this->generatePaket($paketkebijakandirdekom, $infoData);

        $this->AddPage();
        $this->generateRasio($rasiogaji, $infoData);

        $this->AddPage();
        $this->generateRapat($rapat, $infoData);

        $this->AddPage();
        $this->generateHadir($kehadirandekom, $infoData);

        $this->AddPage();
        $this->generateFraud($fraudinternal, $infoData);

        $this->AddPage();
        $this->generateMasalah($masalahhukum, $infoData);

        $this->AddPage();
        $this->generateBentur($transaksikepentingan, $infoData);

        $this->AddPage();
        $this->generateDanasosial($danasosial, $infoData);
    }


    public function generateCoverPage(array $infoData)
    {
        $this->AddPage();

        $this->SetAutoPageBreak(false);

        $coverImagePath = $_SERVER['DOCUMENT_ROOT'] . '/assets/img/Cover.png';
        $this->Image($coverImagePath, 0, 0, 210, 297);

        $this->SetFont('Arial', 'B', 26);
        $this->SetTextColor(0, 0, 0);
        $this->SetY(25);
        $this->Cell(0, 10, 'Laporan Transparansi Tata Kelola', 0, 1, 'C');
        $this->Cell(0, 10, '' . ($infoData['namabpr'] ?? '') . ' Tahun 2025', 0, 1, 'C');

        $logoPath = $_SERVER['DOCUMENT_ROOT'] . '/assets/img/' . ($infoData['logo'] ?? '');
        if (!empty($logoPath) && file_exists($logoPath)) {
            $this->Image($logoPath, 180, 10, 20);
        }

        $this->isCoverPage = true;
    }



    public function generatePenjelasanUmum(array $penjelasanData, array $infoData)
    {
        $this->SetFont('Arial', 'B', 13);
        $this->Cell(0, 10, '1. Penjelasan Umum Penerapan Tata Kelola', 0, 1, 'L');
        $this->Ln(2);

        $this->SetFillColor(20, 24, 99);
        $this->SetTextColor(255);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 8, 'Informasi Umum BPR', 1, 1, 'L', true);

        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 11);
        $this->Cell(50, 8, 'Nama BPR/BPRS', 1);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 8, $infoData['namabpr'] ?? '', 1, 1);

        $this->SetFont('Arial', '', 11);
        $this->Cell(50, 8, 'Alamat', 1);
        $this->SetFont('Arial', 'B', 9.5);
        $this->Cell(0, 8, $infoData['alamat'] ?? '', 1, 1);

        $this->SetFont('Arial', '', 11);
        $this->Cell(50, 8, 'Nomor Telepon', 1);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 8, $infoData['nomor'] ?? '', 1, 1);

        $this->Ln(5);

        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 8, 'Penjelasan Umum:', 0, 1);

        $this->SetFont('Arial', '', 11);
        foreach ($penjelasanData as $row) {
            if ($this->GetY() > 250) {
                $this->AddPage();
                $this->SetY(20);
            }

            $this->MultiCell(0, 8, $row['penjelasan'] ?? '', 0, 'J');
            $this->Ln(4);
        }

        $this->SetFillColor(20, 24, 99);
        $this->SetTextColor(255);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 8, 'Ringkasan Hasil Penilaian Sendiri atas Penerapan Tata Kelola', 1, 1, 'L', true);

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);

        $this->SetFont('Arial', 'B', 9);
        $this->Cell(114, 8, 'Peringkat Komposit Hasil Penilaian Sendiri (Self Assessment) Tata Kelola', 1, 0, 'L');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 8, $row['peringkatkomposit'] ?? '', 1, 1, 'C');
        $this->Ln(8);

        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 8, 'Penjelasan Peringkat Komposit Hasil Penilaian Sendiri (Self Assessment) Tata Kelola:', 0, 1);
        $this->SetFont('Arial', '', 11);

        foreach ($penjelasanData as $row) {
            if ($this->GetY() > 250) {
                $this->AddPage();
                $this->SetY(20);
            }

            $this->MultiCell(0, 8, $row['penjelasankomposit'] ?? '', 0, 'J');
            $this->Ln(4);
        }
        $this->isCoverPage = false;
    }

    public function generateTanggungJawabDireksi(array $tgjwbdirData, array $infoData, $idDireksi = null)
    {
        $filteredData = $tgjwbdirData;
        if ($idDireksi !== null) {
            $filteredData = array_filter($tgjwbdirData, function ($item) use ($idDireksi) {
                return isset($item['id']) && $item['id'] == $idDireksi;
            });
        }

        $this->SetFont('Arial', 'B', 13);
        $this->Cell(0, 10, '2. Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi', 0, 1, 'L');
        $this->Ln(2);

        foreach ($filteredData as $dir) {
            $this->SetFillColor(20, 24, 99);
            $this->SetTextColor(255);
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(0, 8, 'Informasi Direksi', 1, 1, 'L', true);

            $this->SetTextColor(0);
            $this->SetFont('Arial', '', 11);

            $this->Cell(50, 8, 'Nama Direksi', 1);
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(0, 8, $dir['direksi'] ?? '-', 1, 1);

            $this->SetFont('Arial', 'B', 11);
            $this->Cell(50, 8, 'Penjelasan Tugas dan Tanggung Jawab', 0, 1);
            $this->SetFont('Arial', '', 11);
            $this->MultiCell(0, 8, $dir['tugastgjwbdir'] ?? '', 0, 'J');
            $this->Ln(5);

        }
        $dataId1 = null;
        foreach ($tgjwbdirData as $item) {
            if (isset($item['id']) && $item['id'] == 1) {
                $dataId1 = $item;
                break;
            }
        }
        if ($dataId1 !== null && isset($dataId1['tindakdir'])) {
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(50, 8, 'Tindak Lanjut Rekomendasi Dewan Direksi:', 0, 1);
            $this->SetFont('Arial', '', 11);
            $this->MultiCell(0, 8, $dataId1['tindakdir'], 0, 'J');
            $this->Ln(5);
        }

    }

    public function generateTanggungJawabDekom(array $tgjwbdekomData, array $infoData, $idDekom = null)
    {
        $filteredData = $tgjwbdekomData;
        if ($idDekom !== null) {
            $filteredData = array_filter($tgjwbdekomData, function ($item) use ($idDekom) {
                return isset($item['id']) && $item['id'] == $idDekom;
            });
        }

        $this->SetFont('Arial', 'B', 13);
        $this->Cell(0, 10, '3. Pelaksanaan Tugas dan Tanggung Jawab Anggota Dewan Komisaris', 0, 1, 'L');
        $this->Ln(2);

        foreach ($filteredData as $dekom) {
            $this->SetFillColor(20, 24, 99);
            $this->SetTextColor(255);
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(0, 8, 'Informasi Dewan Komisaris', 1, 1, 'L', true);

            $this->SetTextColor(0);
            $this->SetFont('Arial', '', 11);

            $this->Cell(50, 8, 'Nama Dewan Komisaris', 1);
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(0, 8, $dekom['dekom'] ?? '-', 1, 1);

            $this->SetFont('Arial', 'B', 11);
            $this->Cell(50, 8, 'Penjelasan Tugas dan Tanggung Jawab', 0, 1);
            $this->SetFont('Arial', '', 11);
            $this->MultiCell(0, 8, $dekom['tugastgjwbdekom'] ?? '', 0, 'J');
            $this->Ln(5);

        }
        $dataId1 = null;
        foreach ($tgjwbdekomData as $item) {
            if (isset($item['id']) && $item['id'] == 1) {
                $dataId1 = $item;
                break;
            }
        }
        if ($dataId1 !== null && isset($dataId1['tindakdekom'])) {
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(50, 8, 'Tindak Lanjut Rekomendasi Dewan Komisaris:', 0, 1);
            $this->SetFont('Arial', '', 11);
            $this->MultiCell(0, 8, $dataId1['tindakdekom'], 0, 'J');
            $this->Ln(5);
        }

    }

    public function generateTanggungJawabKomite(array $tgjwbkomiteData, array $infoData, $idKomite = null)
    {
        $filteredData = $tgjwbkomiteData;
        if ($idKomite !== null) {
            $filteredData = array_filter($tgjwbkomiteData, function ($item) use ($idKomite) {
                return isset($item['id']) && $item['id'] == $idKomite;
            });
        }

        $this->SetFont('Arial', 'B', 13);
        $this->Cell(0, 10, '4. Tugas, Tanggung Jawab, Program Kerja, dan Realisasi Program Kerja Komite', 0, 1, 'L');
        $this->Ln(2);

        foreach ($filteredData as $komite) {
            $this->SetFillColor(20, 24, 99);
            $this->SetTextColor(255);
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(0, 8, 'Informasi Komite', 1, 1, 'L', true);

            $this->SetTextColor(0);
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(80, 8, 'Komite', 1);
            $this->SetFont('Arial', '', 11);
            $this->Cell(0, 8, $komite['komite'] ?? '-', 1, 1);

            $this->SetFont('Arial', 'B', 11);
            $this->Cell(80, 8, 'Penjelasan Tugas dan Tanggung Jawab', 1);
            $this->SetFont('Arial', '', 11);
            $this->MultiCell(0, 8, $komite['tugastgjwbkomite'] ?? '', 1, 'J');

            $this->SetFont('Arial', 'B', 11);
            $this->Cell(80, 8, 'Jumlah Rapat', 1);
            $this->SetFont('Arial', '', 11);
            $this->MultiCell(0, 8, $komite['jumlahrapat'] ?? '', 1, 'J');

            $this->SetFont('Arial', 'B', 11);
            $this->MultiCell(80, 8, 'Program Kerja', 0);
            $this->SetFont('Arial', '', 11);
            $this->MultiCell(0, 8, $komite['prokerkomite'] ?? '', 0, 'J');

            $this->SetFont('Arial', 'B', 11);
            $this->MultiCell(80, 8, 'Realisasi Program Kerja Komite', 0);
            $this->SetFont('Arial', '', 11);
            $this->MultiCell(0, 8, $komite['hasilprokerkomite'] ?? '', 0, 'J');
            $this->Ln(5);

        }
        $dataId1 = null;
        foreach ($tgjwbkomiteData as $item) {
            if (isset($item['id']) && $item['id'] == 1) {
                $dataId1 = $item;
                break;
            }
        }
        if ($dataId1 !== null && isset($dataId1['tindakkomite'])) {
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(50, 8, 'Tindak Lanjut Rekomendasi Program Kerja dan Realisasi Program Kerja Komite:', 0, 1);
            $this->SetFont('Arial', '', 11);
            $this->MultiCell(0, 8, $dataId1['tindakkomite'], 0, 'J');
            $this->Ln(5);
        }
    }

    public function generateStrukturKomite(array $strukturkomiteData, array $infoData, $idSKomite = null)
    {
        $filteredData = $strukturkomiteData;
        if ($idSKomite !== null) {
            $filteredData = array_filter($strukturkomiteData, function ($item) use ($idSKomite) {
                return isset($item['id']) && $item['id'] == $idSKomite;
            });
        }

        $this->SetFont('Arial', 'B', 13);
        $this->Cell(0, 10, '5. Struktur, Keanggotaan, Keahlian, dan Independensi Anggota Komite', 0, 1, 'L');
        $this->Ln(2);

        foreach ($filteredData as $skomite) {
            $this->SetFillColor(20, 24, 99);
            $this->SetTextColor(255);
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(0, 8, 'Daftar Struktur, Keanggotaan, Keahlian, dan Independensi Anggota Komite', 1, 1, 'L', true);

            $this->SetTextColor(0);
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(95, 8, 'Komite', 1);
            $this->SetFont('Arial', '', 11);
            $this->Cell(0, 8, $skomite['anggotakomite'] ?? '-', 1, 1);

            $this->SetFont('Arial', 'B', 11);
            $this->Cell(95, 8, 'Keahlian', 1);
            $this->SetFont('Arial', '', 11);
            $this->MultiCell(0, 8, $skomite['keahlian'] ?? '', 1, 'J');

            $this->SetFont('Arial', 'B', 11);
            $this->Cell(95, 8, 'Jabatan Dalam Komite Audit', 1);
            $jbtaudit = $skomite['jbtaudit'] ?? '';
            switch ($jbtaudit) {
                case '00':
                    $jabatan = 'Tidak Menjabat';
                    break;
                case '01':
                    $jabatan = 'Ketua';
                    break;
                case '02':
                    $jabatan = 'Anggota';
                    break;
                default:
                    $jabatan = 'Data Tidak Tersedia';
                    break;
            }
            $this->SetFont('Arial', '', 11);
            $this->MultiCell(0, 8, $jabatan, 1, 'J');

            $this->SetFont('Arial', 'B', 11);
            $this->Cell(95, 8, 'Jabatan Dalam Komite Pemantau Risiko', 1);
            $jbtpantauresiko = $skomite['jbtpantauresiko'] ?? '';
            switch ($jbtpantauresiko) {
                case '00':
                    $jabatan = 'Tidak Menjabat';
                    break;
                case '01':
                    $jabatan = 'Ketua';
                    break;
                case '02':
                    $jabatan = 'Anggota';
                    break;
                default:
                    $jabatan = 'Data Tidak Tersedia';
                    break;
            }
            $this->SetFont('Arial', '', 11);
            $this->MultiCell(0, 8, $jabatan, 1, 'J');

            $this->SetFont('Arial', 'B', 11);
            $this->Cell(95, 8, 'Jabatan Dalam Komite Remunerasi dan Nominasi', 1);
            $jbtremunerasi = $skomite['jbtremunerasi'] ?? '';
            switch ($jbtremunerasi) {
                case '00':
                    $jabatan = 'Tidak Menjabat';
                    break;
                case '01':
                    $jabatan = 'Ketua';
                    break;
                case '02':
                    $jabatan = 'Anggota';
                    break;
                default:
                    $jabatan = 'Data Tidak Tersedia';
                    break;
            }
            $this->SetFont('Arial', '', 11);
            $this->MultiCell(0, 8, $jabatan, 1, 'J');

            $this->SetFont('Arial', 'B', 11);
            $this->Cell(95, 8, 'Jabatan Dalam Komite Manajemen Risiko', 1);
            $jbtmanrisk = $skomite['jbtmanrisk'] ?? '';
            switch ($jbtmanrisk) {
                case '00':
                    $jabatan = 'Tidak Menjabat';
                    break;
                case '01':
                    $jabatan = 'Ketua';
                    break;
                case '02':
                    $jabatan = 'Anggota';
                    break;
                default:
                    $jabatan = 'Data Tidak Tersedia';
                    break;
            }
            $this->SetFont('Arial', '', 11);
            $this->MultiCell(0, 8, $jabatan, 1, 'J');

            $this->SetFont('Arial', 'B', 11);
            $this->Cell(95, 8, 'Jabatan Dalam Komite Lainnya', 1);
            $jbtlain = $skomite['jbtlain'] ?? '';
            switch ($jbtlain) {
                case '00':
                    $jabatan = 'Tidak Menjabat';
                    break;
                case '01':
                    $jabatan = 'Ketua';
                    break;
                case '02':
                    $jabatan = 'Anggota';
                    break;
                default:
                    $jabatan = 'Data Tidak Tersedia';
                    break;
            }
            $this->SetFont('Arial', '', 11);
            $this->MultiCell(0, 8, $jabatan, 1, 'J');

            $this->SetFont('Arial', 'B', 11);
            $this->Cell(95, 8, 'Apakah Merupakan Pihak Independen?', 1);
            $independen = $skomite['independen'] ?? '';
            switch ($independen) {
                case '01':
                    $jabatan = 'Ya';
                    break;
                case '02':
                    $jabatan = 'Tidak';
                    break;
                default:
                    $jabatan = 'Data Tidak Tersedia';
                    break;
            }
            $this->SetFont('Arial', '', 11);
            $this->MultiCell(0, 8, $jabatan, 1, 'J');

            $this->Ln(5);

        }
        $dataId1 = null;
        foreach ($strukturkomiteData as $item) {
            if (isset($item['id']) && $item['id'] == 1) {
                $dataId1 = $item;
                break;
            }
        }
        if ($dataId1 !== null && isset($dataId1['tindakstrukturkomite'])) {
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(50, 8, 'Tindak Lanjut Rekomendasi Struktur, Keanggotaan, Keahlian, dan Independensi Anggota Komite:', 0, 1);
            $this->SetFont('Arial', '', 11);
            $this->MultiCell(0, 8, $dataId1['tindakstrukturkomite'], 0, 'J');
            $this->Ln(5);
        }
    }

    public function generateSahamdirdekom(array $sahamdirdekomData, array $infoData, $idshm = null)
    {
        $filteredData = $sahamdirdekomData;
        if ($idshm !== null) {
            $filteredData = array_filter($sahamdirdekomData, function ($item) use ($idshm) {
                return isset($item['id']) && $item['id'] == $idshm;
            });
        }

        $this->SetFont('Arial', 'B', 13);
        $this->Cell(0, 10, '6. Kepemilikan Saham Anggota Direksi dan Dewan Komisaris pada BPR', 0, 1, 'L');
        $this->Ln(2);

        foreach ($filteredData as $shm) {
            if (!empty($shm['direksi'])) {
                $this->SetFillColor(20, 24, 99);
                $this->SetTextColor(255);
                $this->SetFont('Arial', 'B', 11);
                $this->MultiCell(0, 8, 'Informasi Direksi', 1, 1, 'L', true);

                $this->SetTextColor(0);
                $this->SetFont('Arial', '', 11);

                if (!empty($shm['direksi'])) {
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(70, 8, 'Nama Direksi', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $shm['direksi'], 1, 1);
                }

                if (!empty($shm['persensahamdir'])) {
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(70, 8, 'Persentase Kepemilikan (%)', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $shm['persensahamdir'], 1, 'J');
                }
            } elseif (!empty($shm['dekom'])) {
                $this->SetFillColor(20, 24, 99);
                $this->SetTextColor(255);
                $this->SetFont('Arial', 'B', 11);
                $this->MultiCell(0, 8, 'Informasi Dewan Komisaris', 1, 1, 'L', true);

                $this->SetTextColor(0);
                $this->SetFont('Arial', 'B', 11);

                if (!empty($shm['dekom'])) {
                    $this->Cell(70, 8, 'Nama Dewan Komisaris', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $shm['dekom'], 1, 1);
                }

                if (!empty($shm['persensahamdekom'])) {
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(70, 8, 'Persentase Kepemilikan (%)', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $shm['persensahamdekom'], 1, 'J');
                }
            }

            $this->Ln(5);
        }


        $dataId1 = null;
        foreach ($sahamdirdekomData as $item) {
            if (isset($item['id']) && $item['id'] == 1) {
                $dataId1 = $item;
                break;
            }
        }

        if ($dataId1 !== null && isset($dataId1['tindakdir']) && !empty($dataId1['tindakdir'])) {
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(50, 8, 'Tindak Lanjut Rekomendasi Dewan Direksi:', 0, 1);
            $this->SetFont('Arial', '', 11);
            $this->MultiCell(0, 8, $dataId1['tindakdir'], 0, 'J');
            $this->Ln(5);
        }
    }

    public function generateUsahadirdekom(array $shmusahadirdekomData, array $infoData, $idshm = null)
    {
        $filteredData = $shmusahadirdekomData;
        if ($idshm !== null) {
            $filteredData = array_filter($shmusahadirdekomData, function ($item) use ($idshm) {
                return isset($item['id']) && $item['id'] == $idshm;
            });
        }
        $this->SetFillColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 13);
        $this->MultiCell(0, 10, '7. Kepemilikan Saham Anggota Direksi, Dewan Komisaris, dan Pemegang Saham pada Kelompok Usaha BPR', 0, 1, 'L');
        $this->Ln(2);

        foreach ($filteredData as $ush) {
            if (!empty($ush['direksi'])) {
                $this->SetFillColor(20, 24, 99);
                $this->SetTextColor(255);
                $this->SetFont('Arial', 'B', 11);
                $this->MultiCell(0, 8, 'Informasi Direksi', 1, 1, 'L', true);

                $this->SetTextColor(0);
                $this->SetFont('Arial', '', 11);

                if (!empty($ush['direksi'])) {
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Nama Direksi', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $ush['direksi'], 1, 1);
                }

                if (!empty($ush['usahadir'])) {
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Nama Kelompok Usaha BPR :', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $ush['usahadir'], 1, 'J');
                }

                if (!empty($ush['persenshmdir'])) {
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Persentase Kepemilikan (%) :', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $ush['persenshmdir'], 1, 'J');
                }

                if (!empty($ush['persenshmdirlalu'])) {
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Persentase Kepemilikan (%) Tahun Sebelumnya: ', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $ush['persenshmdirlalu'], 1, 'J');
                }
            } elseif (!empty($ush['dekom'])) {
                $this->SetFillColor(20, 24, 99);
                $this->SetTextColor(255);
                $this->SetFont('Arial', 'B', 11);
                $this->MultiCell(0, 8, 'Informasi Dewan Komisaris', 1, 1, 'L', true);

                $this->SetTextColor(0);
                $this->SetFont('Arial', '', 11);

                if (!empty($ush['dekom'])) {
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Nama Dewan Komisaris:', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $ush['dekom'], 1, 1);
                }

                if (!empty($ush['usahadekom'])) {
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Nama Kelompok Usaha BPR:', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $ush['usahadekom'], 1, 'J');
                }

                if (!empty($ush['persenshmdekom'])) {
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Persentase Kepemilikan (%):', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $ush['persenshmdekom'], 1, 'J');
                }

                if (!empty($ush['persenshmdekomlalu'])) {
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Persentase Kepemilikan (%) Tahun Sebelumnya:', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $ush['persenshmdekomlalu'], 1, 'J');
                }
            } elseif (!empty($ush['pshm'])) {
                $this->SetFillColor(20, 24, 99);
                $this->SetTextColor(255);
                $this->SetFont('Arial', 'B', 11);
                $this->MultiCell(0, 8, 'Informasi Pemegang Saham', 1, 1, 'L', true);

                $this->SetTextColor(0);
                $this->SetFont('Arial', '', 11);

                if (!empty($ush['pshm'])) {
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Nama Pemegang Saham:', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $ush['pshm'], 1, 1);
                }

                if (!empty($ush['usahapshm'])) {
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Nama Kelompok Usaha BPR:', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $ush['usahapshm'], 1, 'J');
                }

                if (!empty($ush['persenpshm'])) {
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Persentase Kepemilikan (%):', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $ush['persenpshm'], 1, 'J');
                }

                if (!empty($ush['persenpshmlalu'])) {
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Persentase Kepemilikan (%) Tahun Sebelumnya:', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $ush['persenpshmlalu'], 1, 'J');
                }
            }

            $this->Ln(5);
        }


        // $dataId1 = null;
        // foreach ($shmusahadirdekomData as $item) {
        //     if (isset($item['id']) && $item['id'] == 1) {
        //         $dataId1 = $item;
        //         break;
        //     }
        // }

        // if ($dataId1 !== null && isset($dataId1['tindakdir']) && !empty($dataId1['tindakdir'])) {
        //     $this->SetFont('Arial', 'B', 11);
        //     $this->Cell(50, 8, 'Tindak Lanjut Rekomendasi Dewan Direksi:', 0, 1);
        //     $this->SetFont('Arial', '', 11);
        //     $this->MultiCell(0, 8, $dataId1['tindakdir'], 0, 'J');
        //     $this->Ln(5);
        // }
    }

    public function generateDirdekomlain(array $shmdirdekomlainData, array $infoData, $idlain = null)
    {
        $filteredData = $shmdirdekomlainData;
        if ($idlain !== null) {
            $filteredData = array_filter($shmdirdekomlainData, function ($item) use ($idlain) {
                return isset($item['id']) && $item['id'] == $idlain;
            });
        }

        $this->SetFillColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 13);
        $this->MultiCell(0, 10, '8. Kepemilikan Saham Anggota Direksi dan Dewan Komisaris pada Perusahaan Lain', 0, 1, 'L');
        $this->Ln(2);

        foreach ($filteredData as $lain) {
            if (!empty($lain['direksi'])) {
                $this->SetFillColor(20, 24, 99);
                $this->SetTextColor(255);
                $this->SetFont('Arial', 'B', 11);
                $this->MultiCell(0, 8, 'Kepemilikan Saham Anggota Direksi pada Perusahaan Lain', 1, 1, 'L', true);

                $this->SetTextColor(0);
                $this->SetFont('Arial', '', 11);

                if (!empty($lain['direksi'])) {
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Nama Direksi', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $lain['direksi'], 1, 1);
                }

                if (!empty($lain['perusahaandir'])) {
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Nama Kelompok Usaha BPR :', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $lain['perusahaandir'], 1, 'J');
                }

                if (!empty($lain['persenshmdirlain'])) {
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Persentase Kepemilikan (%) :', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $lain['persenshmdirlain'], 1, 'J');
                }
                $this->Ln(5);
            }
        }
        $this->Ln(5);

        foreach ($filteredData as $lain) {
            if (!empty($lain['dekom'])) {
                $this->SetFillColor(20, 24, 99);
                $this->SetTextColor(255);
                $this->SetFont('Arial', 'B', 11);
                $this->MultiCell(0, 8, 'Kepemilikan Saham Anggota Dewan Komisaris pada Perusahaan Lain', 1, 1, 'L', true);

                $this->SetTextColor(0);
                $this->SetFont('Arial', '', 11);

                if (!empty($lain['dekom'])) {
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Nama Dewan Komisaris:', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $lain['dekom'], 1, 1);
                }

                if (!empty($lain['perusahaandekom'])) {
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Nama Kelompok Usaha BPR:', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $lain['perusahaandekom'], 1, 'J');
                }

                if (!empty($lain['persenshmdekomlain'])) {
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Persentase Kepemilikan (%):', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $lain['persenshmdekomlain'], 1, 'J');
                }
                $this->Ln(5);
            }
        }
    }

    public function generateKeuangan(array $keuangandirdekompshmData, array $infoData, $iduang = null)
    {
        $filteredData = $keuangandirdekompshmData;
        if ($iduang !== null) {
            $filteredData = array_filter($keuangandirdekompshmData, function ($item) use ($iduang) {
                return isset($item['id']) && $item['id'] == $iduang;
            });
        }

        $this->SetFillColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 13);
        $this->MultiCell(0, 10, '9. Hubungan Keuangan Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR', 0, 1, 'L');
        $this->Ln(2);

        foreach ($filteredData as $uang) {
            if (!empty($uang['direksi'])) {
                $this->SetFillColor(20, 24, 99);
                $this->SetTextColor(255);
                $this->SetFont('Arial', 'B', 11);
                $this->Cell(0, 8, 'Hubungan Keuangan Anggota Direksi pada BPR', 1, 1, 'L', true);

                $this->SetTextColor(0);
                $this->SetFont('Arial', '', 11);

                if (!empty($uang['direksi'])) {
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(90, 8, 'Nama Direksi', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $uang['direksi'], 1, 1);
                }

                if (!empty($uang['hubdirdir'])) {
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(90, 8, "Hubungan Keuangan Dengan\nAnggota Direksi Lain di BPR :", 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 90, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->Cell(0, 16, $uang['hubdirdir'], 1, 0, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($uang['hubdirdekom'])) {
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(90, 8, "Hubungan Keuangan Dengan Anggota\nDewan Komisaris Lain di BPR :", 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 90, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->Cell(0, 16, $uang['hubdirdekom'], 1, 0, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($uang['hubdirpshm'])) {
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(90, 8, "Hubungan Keuangan Dengan Pemegang\nSaham Lain di BPR :", 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 90, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->Cell(0, 16, $uang['hubdirpshm'], 1, 0, 'L');

                    $this->SetXY($startX, $endY);
                }
                $this->Ln(5);
            }
        }

        foreach ($filteredData as $uang) {
            if (!empty($uang['dekom'])) {
                $this->SetFillColor(20, 24, 99);
                $this->SetTextColor(255);
                $this->SetFont('Arial', 'B', 11);
                $this->MultiCell(0, 8, 'Hubungan Keuangan Dewan Komisaris pada BPR', 1, 1, 'L', true);

                $this->SetTextColor(0);
                $this->SetFont('Arial', '', 11);

                if (!empty($uang['dekom'])) {
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(90, 8, 'Nama Dewan Komisaris:', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $uang['dekom'], 1, 1);
                }

                if (!empty($uang['hubdekomdir'])) {
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(90, 8, "Hubungan Keuangan Dengan\nAnggota Direksi Lain di BPR :", 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 90, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->Cell(0, 16, $uang['hubdekomdir'], 1, 0, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($uang['hubdekomdekom'])) {
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(90, 8, "Hubungan Keuangan Dengan\nDewan Komisaris Lain di BPR :", 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 90, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->Cell(0, 16, $uang['hubdekomdekom'], 1, 0, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($uang['hubdekompshm'])) {
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(90, 8, "Hubungan Keuangan Dengan\nPemegang Saham Lain di BPR :", 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 90, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->Cell(0, 16, $uang['hubdekompshm'], 1, 0, 'L');

                    $this->SetXY($startX, $endY);
                }

                $this->Ln(5);
            }
        }

        foreach ($filteredData as $uang) {
            if (!empty($uang['pshm'])) {
                $this->SetFillColor(20, 24, 99);
                $this->SetTextColor(255);
                $this->SetFont('Arial', 'B', 11);
                $this->MultiCell(0, 8, 'Hubungan Keuangan Pemegang Saham pada BPR', 1, 1, 'L', true);

                $this->SetTextColor(0);
                $this->SetFont('Arial', '', 11);

                if (!empty($uang['pshm'])) {
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(90, 8, 'Nama Pemegang Saham:', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $uang['pshm'], 1, 1);
                }

                if (!empty($uang['hubpshmdir'])) {
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(90, 8, "Hubungan Keuangan Dengan\nAnggota Direksi Lain di BPR :", 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 90, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->Cell(0, 16, $uang['hubpshmdir'], 1, 0, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($uang['hubpshmdekom'])) {
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(90, 8, "Hubungan Keuangan Dengan\nDewan Komisaris Lain di BPR :", 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 90, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->Cell(0, 16, $uang['hubpshmdekom'], 1, 0, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($uang['hubpshmpshm'])) {
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(90, 8, "Hubungan Keuangan Dengan\nPemegang Saham Lain di BPR :", 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 90, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->Cell(0, 16, $uang['hubpshmpshm'], 1, 0, 'L');

                    $this->SetXY($startX, $endY);
                }

            }
        }
    }

    public function generateKeluarga(array $keluargadirdekompshmData, array $infoData, $idkel = null)
    {
        $filteredData = $keluargadirdekompshmData;
        if ($idkel !== null) {
            $filteredData = array_filter($keluargadirdekompshmData, function ($item) use ($idkel) {
                return isset($item['id']) && $item['id'] == $idkel;
            });
        }

        $this->SetFillColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 13);
        $this->MultiCell(0, 10, '10. Hubungan Keluarga Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR', 0, 1, 'L');
        $this->Ln(2);

        foreach ($filteredData as $kel) {
            if (!empty($kel['direksi'])) {
                $this->SetFillColor(20, 24, 99);
                $this->SetTextColor(255);
                $this->SetFont('Arial', 'B', 11);
                $this->Cell(0, 8, 'Hubungan Keluarga Anggota Direksi pada BPR', 1, 1, 'L', true);

                $this->SetTextColor(0);
                $this->SetFont('Arial', '', 11);

                if (!empty($kel['direksi'])) {
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(90, 8, 'Nama Direksi', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $kel['direksi'], 1, 1);
                }

                if (!empty($kel['hubkeldirdir'])) {
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(90, 8, "Hubungan Keluarga Dengan\nAnggota Direksi Lain di BPR :", 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 90, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->Cell(0, 16, $kel['hubkeldirdir'], 1, 0, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($kel['hubkeldirdekom'])) {
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(90, 8, "Hubungan Keluarga Dengan Anggota\nDewan Komisaris Lain di BPR :", 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 90, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->Cell(0, 16, $kel['hubkeldirdekom'], 1, 0, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($kel['hubkeldirpshm'])) {
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(90, 8, "Hubungan Keluarga Dengan Pemegang\nSaham Lain di BPR :", 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 90, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->Cell(0, 16, $kel['hubkeldirpshm'], 1, 0, 'L');

                    $this->SetXY($startX, $endY);
                }

            }
        }
        $this->Ln(5);

        foreach ($filteredData as $kel) {
            if (!empty($kel['dekom'])) {
                $this->SetFillColor(20, 24, 99);
                $this->SetTextColor(255);
                $this->SetFont('Arial', 'B', 11);
                $this->MultiCell(0, 8, 'Hubungan Keluarga Dewan Komisaris pada BPR', 1, 1, 'L', true);

                $this->SetTextColor(0);
                $this->SetFont('Arial', '', 11);

                if (!empty($kel['dekom'])) {
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(90, 8, 'Nama Dewan Komisaris:', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $kel['dekom'], 1, 1);
                }

                if (!empty($kel['hubkeldekomdir'])) {
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(90, 8, "Hubungan Keluarga Dengan\nAnggota Direksi Lain di BPR :", 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 90, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->Cell(0, 16, $kel['hubkeldekomdir'], 1, 0, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($kel['hubkeldekomdekom'])) {
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(90, 8, "Hubungan Keluarga Dengan\nDewan Komisaris Lain di BPR :", 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 90, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->Cell(0, 16, $kel['hubkeldekomdekom'], 1, 0, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($kel['hubkeldekompshm'])) {
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(90, 8, "Hubungan Keluarga Dengan\nPemegang Saham Lain di BPR :", 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 90, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->Cell(0, 16, $kel['hubkeldekompshm'], 1, 0, 'L');

                    $this->SetXY($startX, $endY);
                }
                $this->Ln(5);
            }

        }

        foreach ($filteredData as $kel) {
            if (!empty($kel['pshm'])) {
                $this->SetFillColor(20, 24, 99);
                $this->SetTextColor(255);
                $this->SetFont('Arial', 'B', 11);
                $this->MultiCell(0, 8, 'Hubungan Keluarga Pemegang Saham pada BPR', 1, 1, 'L', true);

                $this->SetTextColor(0);
                $this->SetFont('Arial', '', 11);

                if (!empty($kel['pshm'])) {
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(90, 8, 'Nama Pemegang Saham:', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $kel['pshm'], 1, 1);
                }

                if (!empty($kel['hubkelpshmdir'])) {
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(90, 8, "Hubungan Keluarga Dengan\nAnggota Direksi Lain di BPR :", 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 90, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->Cell(0, 16, $kel['hubkelpshmdir'], 1, 0, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($kel['hubkelpshmdekom'])) {
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(90, 8, "Hubungan Keluarga Dengan\nDewan Komisaris Lain di BPR :", 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 90, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->Cell(0, 16, $kel['hubkelpshmdekom'], 1, 0, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($kel['hubkelpshmpshm'])) {
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(90, 8, "Hubungan Keluarga Dengan\nPemegang Saham Lain di BPR :", 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 90, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->Cell(0, 16, $kel['hubkelpshmpshm'], 1, 0, 'L');

                    $this->SetXY($startX, $endY);
                }

                $this->Ln(5);
            }
        }
    }

    public function generatePaket(array $paketkebijakandirdekomData, array $infoData, $idpaket = null)
    {
        $filteredData = $paketkebijakandirdekomData;
        if ($idpaket !== null) {
            $filteredData = array_filter($paketkebijakandirdekomData, function ($item) use ($idpaket) {
                return isset($item['id']) && $item['id'] == $idpaket;
            });
        }

        $this->SetFillColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 13);
        $this->MultiCell(0, 10, '11. Paket/Kebijakan Remunerasi dan Fasilitas Lain bagi Direksi dan Dewan Komisaris', 0, 1, 'L');
        $this->Ln(2);

        $footerHeight = 12;
        $bottomMargin = 15;

        foreach ($filteredData as $paket) {
            if (!empty($paket['penerimagajidir'])) {
                $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                    $this->AddPage();
                }
                $this->SetFillColor(20, 24, 99);
                $this->SetTextColor(255);
                $this->SetFont('Arial', 'B', 11);
                $this->Cell(0, 8, '1.1. Gaji Bagi Direksi dan Dewan Komisaris', 1, 1, 'L', true);

                $this->SetTextColor(0);
                $this->SetFont('Arial', '', 11);

                if (!empty($paket['penerimagajidir'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Jumlah Direksi Penerima Gaji', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $paket['penerimagajidir'], 1, 1);
                }

                if (!empty($paket['nominalgajidir'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Jumlah Nominal Keseluruhan Gaji Direksi (Rp)', 1);
                    $this->SetFont('Arial', '', 11);
                    $formatted_nominal = 'Rp ' . number_format($paket['nominalgajidir'], 0, ',', '.');
                    $this->MultiCell(0, 8, $formatted_nominal, 1, 1);
                }

                if (!empty($paket['penerimagajidekom'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Jumlah Dewan Komisaris Penerima Gaji', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $paket['penerimagajidekom'], 1, 1);
                }

                if (!empty($paket['nominalgajidekom'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, "Jumlah Nominal Keseluruhan Gaji Dewan Komisaris (Rp)", 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $formatted_nominal = 'Rp ' . number_format($paket['nominalgajidekom'], 0, ',', '.');
                    $this->Cell(0, 16, $formatted_nominal, 1, 0, 'L');

                    $this->SetXY($startX, $endY);
                }
            }
        }
        $this->Ln(5);


        foreach ($filteredData as $paket) {
            if (!empty($paket['terimatunjangandir'])) {
                $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                    $this->AddPage();
                }
                $this->SetFillColor(20, 24, 99);
                $this->SetTextColor(255);
                $this->SetFont('Arial', 'B', 11);
                $this->MultiCell(0, 8, '1.2. Tunjangan Bagi Direksi dan Dewan Komisaris', 1, 1, 'L', true);

                $this->SetTextColor(0);
                $this->SetFont('Arial', '', 11);

                if (!empty($paket['terimatunjangandir'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Jumlah Direksi Penerima Tunjangan:', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $paket['terimatunjangandir'], 1, 1);
                }

                if (!empty($paket['nominaltunjangandir'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, "Jumlah Nominal Keseluruhan Tunjangan Direksi (Rp)", 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $formatted_nominal = 'Rp ' . number_format($paket['nominaltunjangandir'], 0, ',', '.');
                    $this->Cell(0, 16, $formatted_nominal, 1, 0, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($paket['terimatunjangandekom'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Jumlah Dewan Komisaris Penerima Tunjangan:', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $paket['terimatunjangandekom'], 1, 1);
                }

                if (!empty($paket['nominaltunjangandekom'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, "Jumlah Nominal Keseluruhan Tunjangan Dewan Komisaris (Rp)", 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $formatted_nominal = 'Rp ' . number_format($paket['nominaltunjangandekom'], 0, ',', '.');
                    $this->Cell(0, 16, $formatted_nominal, 1, 0, 'L');

                    $this->SetXY($startX, $endY);
                }
            }
        }
        $this->Ln(5);

        foreach ($filteredData as $paket) {
            if (!empty($paket['terimatantiemdir'])) {
                $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                    $this->AddPage();
                }
                $this->SetFillColor(20, 24, 99);
                $this->SetTextColor(255);
                $this->SetFont('Arial', 'B', 11);
                $this->MultiCell(0, 8, '1.3. Tantiem Bagi Direksi dan Dewan Komisaris', 1, 1, 'L', true);

                $this->SetTextColor(0);
                $this->SetFont('Arial', '', 11);

                if (!empty($paket['terimatantiemdir'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Jumlah Direksi Penerima Tantiem:', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $paket['terimatantiemdir'], 1, 1);
                }

                if (!empty($paket['nominaltantiemdir'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, "Jumlah Nominal Keseluruhan Tantiem Direksi (Rp)", 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $formatted_nominal = 'Rp ' . number_format($paket['nominaltantiemdir'], 0, ',', '.');
                    $this->Cell(0, 8, $formatted_nominal, 1, 0, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($paket['terimatantiemdekom'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Jumlah Dewan Komisaris Penerima Tantiem:', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $paket['terimatantiemdekom'], 1, 1);
                }

                if (!empty($paket['nominaltantiemdekom'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, "Jumlah Nominal Keseluruhan Tantiem Dewan Komisaris (Rp)", 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $formatted_nominal = 'Rp ' . number_format($paket['nominaltantiemdekom'], 0, ',', '.');
                    $this->Cell(0, 16, $formatted_nominal, 1, 0, 'L');

                    $this->SetXY($startX, $endY);
                }

                $this->Ln(5);
            }
        }

        foreach ($filteredData as $paket) {
            if (!empty($paket['terimashmdir'])) {
                $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                    $this->AddPage();
                }
                $this->SetFillColor(20, 24, 99);
                $this->SetTextColor(255);
                $this->SetFont('Arial', 'B', 11);
                $this->MultiCell(0, 8, '1.4. Kompensasi berbasis saham Bagi Direksi dan Dewan Komisaris', 1, 1, 'L', true);

                $this->SetTextColor(0);
                $this->SetFont('Arial', '', 11);

                if (!empty($paket['terimashmdir'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Jumlah Direksi Penerima Kompensasi berbasis saham:', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 16, $paket['terimashmdir'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($paket['nominalshmdir'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, "Jumlah Nominal Keseluruhan Kompensasi berbasis saham Direksi (Rp)", 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $formatted_nominal = 'Rp ' . number_format($paket['nominalshmdir'], 0, ',', '.');
                    $this->Cell(0, 16, $formatted_nominal, 1, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($paket['terimashmdekom'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Jumlah Komisaris Penerima Kompensasi berbasis saham:', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 16, $paket['terimashmdekom'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($paket['nominalshmdekom'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, "Jumlah Nominal Keseluruhan Kompensasi berbasis saham Komisaris (Rp)", 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $formatted_nominal = 'Rp ' . number_format($paket['nominalshmdekom'], 0, ',', '.');
                    $this->Cell(0, 16, $formatted_nominal, 1, 0, 'L');

                    $this->SetXY($startX, $endY);
                }
                $this->Ln(5);
            }
        }

        foreach ($filteredData as $paket) {
            if (!empty($paket['terimaremunlaindir'])) {
                $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                    $this->AddPage();
                }
                $this->SetFillColor(20, 24, 99);
                $this->SetTextColor(255);
                $this->SetFont('Arial', 'B', 11);
                $this->MultiCell(0, 8, '1.5. Remunerasi lainnya Bagi Direksi dan Dewan Komisaris', 1, 1, 'L', true);

                $this->SetTextColor(0);
                $this->SetFont('Arial', '', 11);

                if (!empty($paket['terimaremunlaindir'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Jumlah Direksi Penerima Remunerasi lainnya:', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $paket['terimaremunlaindir'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($paket['nominalremunlaindir'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, "Jumlah Nominal Keseluruhan Remunerasi lainnya Direksi (Rp)", 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $formatted_nominal = 'Rp ' . number_format($paket['nominalremunlaindir'], 0, ',', '.');
                    $this->Cell(0, 16, $formatted_nominal, 1, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($paket['terimaremunlaindekom'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Jumlah Komisaris Penerima Remunerasi lainnya:', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $paket['terimaremunlaindekom'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($paket['nominalremunlaindekom'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, "Jumlah Nominal Keseluruhan Remunerasi lainnya Komisaris (Rp)", 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $formatted_nominal = 'Rp ' . number_format($paket['nominalremunlaindekom'], 0, ',', '.');
                    $this->Cell(0, 16, $formatted_nominal, 1, 0, 'L');

                    $this->SetXY($startX, $endY);
                }
                $this->Ln(5);
            }
        }

        foreach ($filteredData as $paket) {
            if (!empty($paket['terimarumahdir'])) {
                $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                    $this->AddPage();
                }
                $this->SetFillColor(20, 24, 99);
                $this->SetTextColor(255);
                $this->SetFont('Arial', 'B', 11);
                $this->MultiCell(0, 8, '2.1. Perumahan Bagi Direksi dan Dewan Komisaris', 1, 1, 'L', true);

                $this->SetTextColor(0);
                $this->SetFont('Arial', '', 11);

                if (!empty($paket['terimarumahdir'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Jumlah Direksi Penerima Perumahan (Orang):', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $paket['terimarumahdir'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($paket['nominalrumahdir'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, "Jumlah Nominal Perumahan Direksi (Rp) ", 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $formatted_nominal = 'Rp ' . number_format($paket['nominalrumahdir'], 0, ',', '.');
                    $this->Cell(0, 8, $formatted_nominal, 1, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($paket['terimarumahdekom'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Jumlah Komisaris Penerima Perumahan (Orang)', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $paket['terimarumahdekom'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($paket['nominalrumahdekom'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, "Jumlah Nominal Perumahan Komisaris (Rp)", 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $formatted_nominal = 'Rp ' . number_format($paket['nominalrumahdekom'], 0, ',', '.');
                    $this->Cell(0, 8, $formatted_nominal, 1, 0, 'L');

                    $this->SetXY($startX, $endY);
                }
                $this->Ln(5);
            }
        }

        foreach ($filteredData as $paket) {
            if (!empty($paket['terimatransportdir'])) {
                $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                    $this->AddPage();
                }
                $this->SetFillColor(20, 24, 99);
                $this->SetTextColor(255);
                $this->SetFont('Arial', 'B', 11);
                $this->MultiCell(0, 8, '2.2. Transportasi Bagi Direksi dan Dewan Komisaris', 1, 1, 'L', true);

                $this->SetTextColor(0);
                $this->SetFont('Arial', '', 11);

                if (!empty($paket['terimatransportdir'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Jumlah Direksi Penerima Transportasi (Orang):', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $paket['terimatransportdir'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($paket['nominaltransportdir'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, "Jumlah Nominal Transportasi Direksi (Rp) ", 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $formatted_nominal = 'Rp ' . number_format($paket['nominaltransportdir'], 0, ',', '.');
                    $this->Cell(0, 8, $formatted_nominal, 1, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($paket['terimatransportdekom'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Jumlah Komisaris Penerima Transportasi (Orang)', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $paket['terimatransportdekom'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($paket['nominaltransportdekom'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, "Jumlah Nominal Transportasi Komisaris (Rp)", 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $formatted_nominal = 'Rp ' . number_format($paket['nominaltransportdekom'], 0, ',', '.');
                    $this->Cell(0, 8, $formatted_nominal, 1, 0, 'L');

                    $this->SetXY($startX, $endY);
                }
                $this->Ln(5);
            }
        }

        foreach ($filteredData as $paket) {
            if (!empty($paket['terimaasuransidir'])) {
                $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                    $this->AddPage();
                }
                $this->SetFillColor(20, 24, 99);
                $this->SetTextColor(255);
                $this->SetFont('Arial', 'B', 11);
                $this->MultiCell(0, 8, '2.3. Asuransi Kesehatan Bagi Direksi dan Dewan Komisaris', 1, 1, 'L', true);

                $this->SetTextColor(0);
                $this->SetFont('Arial', '', 11);

                if (!empty($paket['terimaasuransidir'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Jumlah Direksi Penerima Asuransi Kesehatan (Orang):', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 16, $paket['terimaasuransidir'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($paket['nominalasuransidir'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, "Jumlah Nominal Asuransi Kesehatan Direksi (Rp)", 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $formatted_nominal = 'Rp ' . number_format($paket['nominalasuransidir'], 0, ',', '.');
                    $this->Cell(0, 8, $formatted_nominal, 1, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($paket['terimaasuransidekom'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Jumlah Komisaris Penerima Asuransi Kesehatan (Orang)', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 16, $paket['terimaasuransidekom'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($paket['nominalasuransidekom'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, "Jumlah Nominal Asuransi Kesehatan Komisaris (Rp)", 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $formatted_nominal = 'Rp ' . number_format($paket['nominalasuransidekom'], 0, ',', '.');
                    $this->Cell(0, 8, $formatted_nominal, 1, 0, 'L');

                    $this->SetXY($startX, $endY);
                }
                $this->Ln(5);
            }
        }

        foreach ($filteredData as $paket) {
            if (!empty($paket['terimafasilitasdir'])) {
                $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                    $this->AddPage();
                }
                $this->SetFillColor(20, 24, 99);
                $this->SetTextColor(255);
                $this->SetFont('Arial', 'B', 11);
                $this->MultiCell(0, 8, '2.4. Fasilitas Lain-Lainnya Bagi Direksi dan Dewan Komisaris', 1, 1, 'L', true);

                $this->SetTextColor(0);
                $this->SetFont('Arial', '', 11);

                if (!empty($paket['terimafasilitasdir'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Jumlah Direksi Penerima Fasilitas LainLainnya (Orang):', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 16, $paket['terimafasilitasdir'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($paket['nominalfasilitasdir'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, "Jumlah Nominal Fasilitas Lain-Lainnya Direksi (Rp)", 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $formatted_nominal = 'Rp ' . number_format($paket['nominalfasilitasdir'], 0, ',', '.');
                    $this->Cell(0, 8, $formatted_nominal, 1, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($paket['terimafasilitasdekom'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Jumlah Komisaris Penerima Fasilitas Lain-Lainnya (Orang)', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 16, $paket['terimafasilitasdekom'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($paket['nominalfasilitasdekom'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, "Jumlah Nominal Fasilitas Lain-Lainnya Komisaris (Rp)", 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $formatted_nominal = 'Rp ' . number_format($paket['nominalfasilitasdekom'], 0, ',', '.');
                    $this->Cell(0, 16, $formatted_nominal, 1, 0, 'L');

                    $this->SetXY($startX, $endY);
                }
                $this->Ln(5);
            }
        }
    }

    public function generateRasio(array $rasiogajiData, array $infoData, $idrasio = null)
    {
        $filteredData = $rasiogajiData;
        if ($idrasio !== null) {
            $filteredData = array_filter($rasiogajiData, function ($item) use ($idrasio) {
                return isset($item['id']) && $item['id'] == $idrasio;
            });
        }

        $this->SetFillColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 13);
        $this->MultiCell(0, 10, '12. Rasio Gaji Tertinggi dan Gaji Terendah', 0, 1, 'L');
        $this->Ln(2);

        foreach ($filteredData as $rasio) {
            if (!empty($rasio['pegawaitinggi'])) {
                $this->SetFillColor(20, 24, 99);
                $this->SetTextColor(255);
                $this->SetFont('Arial', 'B', 11);
                $this->MultiCell(0, 8, '1. Rasio (a) gaji pegawai yang tertinggi dan (b) gaji pegawai yang terendah', 1, 1, 'L', true);

                $this->SetTextColor(0);
                $this->SetFont('Arial', '', 11);

                if (!empty($rasio['dirtinggi'])) {
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Rasio (a/b)', 1);
                    $this->SetFont('Arial', '', 11);

                    if (
                        isset($rasio['pegawaitinggi']) && is_numeric($rasio['pegawaitinggi']) &&
                        isset($rasio['pegawairendah']) && is_numeric($rasio['pegawairendah']) &&
                        $rasio['pegawairendah'] != 0
                    ) {
                        $hasil_rasio = $rasio['pegawaitinggi'] / $rasio['pegawairendah'];
                        $this->MultiCell(0, 8, number_format($hasil_rasio, 2, ',', '.'), 1, 1); // Format dengan 2 desimal
                    } else {
                        $this->MultiCell(0, 8, 'N/A', 1, 1); // Tampilkan 'N/A' jika data tidak valid atau pembagian dengan nol
                    }
                }

                $this->Ln(5);
            }
        }

        foreach ($filteredData as $rasio) {
            if (!empty($rasio['dirtinggi'])) {
                $this->SetFillColor(20, 24, 99);
                $this->SetTextColor(255);
                $this->SetFont('Arial', 'B', 11);
                $this->MultiCell(0, 8, '2. Rasio (a) gaji anggota Direksi yang tertinggi dan (b) gaji anggota Direksi yang terendah', 1, 1, 'L', true);

                $this->SetTextColor(0);
                $this->SetFont('Arial', '', 11);

                if (!empty($rasio['dirtinggi'])) {
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Rasio (a/b)', 1);
                    $this->SetFont('Arial', '', 11);

                    if (
                        isset($rasio['dirtinggi']) && is_numeric($rasio['dirtinggi']) &&
                        isset($rasio['dirrendah']) && is_numeric($rasio['dirrendah']) &&
                        $rasio['pegawairendah'] != 0
                    ) {
                        $hasil_rasio = $rasio['dirtinggi'] / $rasio['dirrendah'];
                        $this->MultiCell(0, 8, number_format($hasil_rasio, 2, ',', '.'), 1, 1);
                    } else {
                        $this->MultiCell(0, 8, 'N/A', 1, 1);
                    }
                }
                $this->Ln(5);

            }
        }

        foreach ($filteredData as $rasio) {
            if (!empty($rasio['dirtinggi'])) {
                $this->SetFillColor(20, 24, 99);
                $this->SetTextColor(255);
                $this->SetFont('Arial', 'B', 11);
                $this->MultiCell(0, 8, '2. Rasio (a) gaji anggota Direksi yang tertinggi dan (b) gaji anggota Direksi yang terendah', 1, 1, 'L', true);

                $this->SetTextColor(0);
                $this->SetFont('Arial', '', 11);

                if (!empty($rasio['dirtinggi'])) {
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Rasio (a/b)', 1);
                    $this->SetFont('Arial', '', 11);

                    if (
                        isset($rasio['dirtinggi']) && is_numeric($rasio['dirtinggi']) &&
                        isset($rasio['dirrendah']) && is_numeric($rasio['dirrendah']) &&
                        $rasio['pegawairendah'] != 0
                    ) {
                        $hasil_rasio = $rasio['dirtinggi'] / $rasio['dirrendah'];
                        $this->MultiCell(0, 8, number_format($hasil_rasio, 2, ',', '.'), 1, 1);
                    } else {
                        $this->MultiCell(0, 8, 'N/A', 1, 1);
                    }
                }
                $this->Ln(5);

            }
        }

        foreach ($filteredData as $rasio) {
            if (!empty($rasio['dekomtinggi'])) {
                $this->SetFillColor(20, 24, 99);
                $this->SetTextColor(255);
                $this->SetFont('Arial', 'B', 11);
                $this->MultiCell(0, 8, '4. Rasio (a) gaji anggota Direksi yang tertinggi dan (b) gaji anggota Dewan Komisaris yang tertinggi', 1, 1, 'L', true);

                $this->SetTextColor(0);
                $this->SetFont('Arial', '', 11);

                if (!empty($rasio['dekomtinggi'])) {
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Rasio (a/b)', 1);
                    $this->SetFont('Arial', '', 11);

                    if (
                        isset($rasio['dirtinggi']) && is_numeric($rasio['dirtinggi']) &&
                        isset($rasio['dekomtinggi']) && is_numeric($rasio['dekomtinggi']) &&
                        $rasio['dekomtinggi'] != 0
                    ) {
                        $hasil_rasio = $rasio['dirtinggi'] / $rasio['dekomtinggi'];
                        $this->MultiCell(0, 8, number_format($hasil_rasio, 2, ',', '.'), 1, 1);
                    } else {
                        $this->MultiCell(0, 8, 'N/A', 1, 1);
                    }
                }
                $this->Ln(5);
            }
        }

        foreach ($filteredData as $rasio) {
            if (!empty($rasio['dekomtinggi'])) {
                $this->SetFillColor(20, 24, 99);
                $this->SetTextColor(255);
                $this->SetFont('Arial', 'B', 11);
                $this->MultiCell(0, 8, '5. Rasio (a) gaji anggota Direksi yang tertinggi dan (b) gaji pegawai yang tertinggi', 1, 1, 'L', true);

                $this->SetTextColor(0);
                $this->SetFont('Arial', '', 11);

                if (!empty($rasio['dekomtinggi'])) {
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Rasio (a/b)', 1);
                    $this->SetFont('Arial', '', 11);

                    if (
                        isset($rasio['dirtinggi']) && is_numeric($rasio['dirtinggi']) &&
                        isset($rasio['pegawaitinggi']) && is_numeric($rasio['pegawaitinggi']) &&
                        $rasio['dekomtinggi'] != 0
                    ) {
                        $hasil_rasio = $rasio['dirtinggi'] / $rasio['pegawaitinggi'];
                        $this->MultiCell(0, 8, number_format($hasil_rasio, 2, ',', '.'), 1, 1);
                    } else {
                        $this->MultiCell(0, 8, 'N/A', 1, 1);
                    }
                }
                $this->Ln(5);
            }
        }
    }

    public function generateRapat(array $rapatData, array $infoData, $idrapat = null)
    {
        $filteredData = $rapatData;
        if ($idrapat !== null) {
            $filteredData = array_filter($rapatData, function ($item) use ($idrapat) {
                return isset($item['id']) && $item['id'] == $idrapat;
            });
        }

        $footerHeight = 12;
        $bottomMargin = 15;

        $this->SetFillColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 13);
        $this->MultiCell(0, 10, '13. Pelaksanaan Rapat dalam 1 (satu) tahun', 0, 1, 'L');
        $this->Ln(2);

        foreach ($filteredData as $rapat) {
            if (!empty($rapat['tanggalrapat'])) {
                $estimatedHeightGaji = 8 * 4;
                if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                    $this->AddPage();
                }
                $this->SetFillColor(20, 24, 99);
                $this->SetTextColor(255);
                $this->SetFont('Arial', 'B', 11);
                $this->MultiCell(0, 8, 'Pelaksanaan Rapat', 1, 1, 'L', true);

                $this->SetTextColor(0);
                $this->SetFont('Arial', '', 11);

                if (!empty($rapat['tanggalrapat'])) {
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(70, 8, 'Tanggal Rapat', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $rapat['tanggalrapat'], 1, 1);
                }
                if (!empty($rapat['jumlahpeserta'])) {
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(70, 8, 'Jumlah Peserta', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $rapat['jumlahpeserta'], 1, 1);
                }
                if (!empty($rapat['topikrapat'])) {
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(70, 8, 'Topik/Materi Pembahasan', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $rapat['topikrapat'], 1, 1);
                }
                $this->Ln(5);
            }
        }
        $dataId1 = null;
        foreach ($rapatData as $item) {
            if (isset($item['id']) && $item['id'] == 1) {
                $dataId1 = $item;
                break;
            }
        }
        if ($dataId1 !== null && isset($dataId1['keterangan'])) {
            $estimatedHeightGaji = 8 * 4;
            if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                $this->AddPage();
            }
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(50, 8, 'Keterangan:', 0, 1);
            $this->SetFont('Arial', '', 11);
            $this->MultiCell(0, 8, $dataId1['keterangan'], 0, 'J');
            $this->Ln(5);
        }
        $this->Ln(5);

    }

    public function generateHadir(array $hadirData, array $infoData, $idhadir = null)
    {
        $filteredData = $hadirData;
        if ($idhadir !== null) {
            $filteredData = array_filter($hadirData, function ($item) use ($idhadir) {
                return isset($item['id']) && $item['id'] == $idhadir;
            });
        }

        $footerHeight = 12;
        $bottomMargin = 15;

        $this->SetFillColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 13);
        $this->MultiCell(0, 10, '14. Kehadiran Anggota Dewan Komisaris', 0, 1, 'L');
        $this->Ln(2);

        foreach ($filteredData as $hadir) {
            if (!empty($hadir['dekom'])) {
                $estimatedHeightGaji = 8 * 4;
                if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                    $this->AddPage();
                }
                $this->SetFillColor(20, 24, 99);
                $this->SetTextColor(255);
                $this->SetFont('Arial', 'B', 11);
                $this->MultiCell(0, 8, 'Kehadiran Anggota Dewan Komisaris dalam Pelaksanaan Rapat dalam 1 Tahun', 1, 1, 'L', true);

                $this->SetTextColor(0);
                $this->SetFont('Arial', '', 11);

                if (!empty($hadir['dekom'])) {
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(70, 8, 'Nama Anggota Dewan Komisaris', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $hadir['dekom'], 1, 1);
                }
                if (!empty($hadir['hadirfisik'])) {
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(70, 8, 'Jumlah Kehadiran (Fisik)', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $hadir['hadirfisik'], 1, 1);
                }
                if (!empty($hadir['hadironline'])) {
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(70, 8, 'Jumlah Kehadiran (Online)', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $hadir['hadironline'], 1, 1);
                }
                $this->Ln(5);
            }
        }
        $dataId1 = null;
        foreach ($hadirData as $item) {
            if (isset($item['id']) && $item['id'] == 1) {
                $dataId1 = $item;
                break;
            }
        }
        if ($dataId1 !== null && isset($dataId1['keterangan'])) {
            $estimatedHeightGaji = 8 * 4;
            if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                $this->AddPage();
            }
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(50, 8, 'Keterangan:', 0, 1);
            $this->SetFont('Arial', '', 11);
            $this->MultiCell(0, 8, $dataId1['keterangan'], 0, 'J');
            $this->Ln(5);
        }
        $this->Ln(5);
    }

    public function generateFraud(array $fraudinternalData, array $infoData, $fraud = null)
    {
        $filteredData = $fraudinternalData;
        if ($fraud !== null) {
            $filteredData = array_filter($fraudinternalData, function ($item) use ($fraud) {
                return isset($item['id']) && $item['id'] == $fraud;
            });
        }

        $footerHeight = 12;
        $bottomMargin = 15;

        $this->SetFillColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 13);
        $this->MultiCell(0, 10, '15. Jumlah Penyimpangan Intern (Internal Fraud)', 0, 1, 'L');
        $this->Ln(2);

        foreach ($filteredData as $fraud) {
            if (!empty($fraud['fraudtahunlaporandir'])) {
                $estimatedHeightGaji = 8 * 4;
                if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                    $this->AddPage();
                }
                $this->SetFillColor(20, 24, 99);
                $this->SetTextColor(255);
                $this->SetFont('Arial', 'B', 11);
                $this->MultiCell(0, 8, '1.1. Jumlah Penyimpangan Internal oleh Anggota Direksi', 1, 1, 'L', true);

                $this->SetTextColor(0);
                $this->SetFont('Arial', '', 11);

                if (!empty($fraud['fraudtahunlaporandir'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Total Fraud Pada Tahun Laporan', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $fraud['fraudtahunlaporandir'], 1, 1);
                }
                if (!empty($fraud['fraudtahunsebelumdir'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Total Fraud Pada Tahun Sebelumnya', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $fraud['fraudtahunsebelumdir'], 1, 1);
                }
                if (!empty($fraud['selesaitahunlaporandir'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Telah Diselesaikan Pada Tahun Laporan ', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $fraud['selesaitahunlaporandir'], 1, 1);
                }
                if (!empty($fraud['prosestahunlaporandir'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Dalam Proses Penyelesaian Pada Tahun Laporan', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $fraud['prosestahunlaporandir'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }
                if (!empty($fraud['prosestahunsebelumdir'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Dalam Proses Penyelesaian Pada Tahun Sebelumnya', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 16, $fraud['prosestahunsebelumdir'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }
                if (!empty($fraud['belumtahunlaporandir'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Belum Diupayakan Penyelesaiannya Pada Tahun Laporan', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 16, $fraud['belumtahunlaporandir'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }
                if (!empty($fraud['belumtahunsebelumdir'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Belum Diupayakan Penyelesaiannya Pada Tahun Sebelumnya', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 16, $fraud['belumtahunsebelumdir'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }
                if (!empty($fraud['hukumtahunlaporandir'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Telah ditindaklanjuti Melalui Proses Hukum Pada Tahun Laporan', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 16, $fraud['hukumtahunlaporandir'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }
                $this->Ln(5);
            }
        }

        foreach ($filteredData as $fraud) {
            if (!empty($fraud['fraudtahunlaporandekom'])) {
                $estimatedHeightGaji = 8 * 4;
                if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                    $this->AddPage();
                }
                $this->SetFillColor(20, 24, 99);
                $this->SetTextColor(255);
                $this->SetFont('Arial', 'B', 11);
                $this->MultiCell(0, 8, '1.2. Jumlah Penyimpangan Internal oleh Anggota Dewan Komisaris', 1, 1, 'L', true);

                $this->SetTextColor(0);
                $this->SetFont('Arial', '', 11);

                if (!empty($fraud['fraudtahunlaporandekom'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Total Fraud Pada Tahun Laporan', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $fraud['fraudtahunlaporandekom'], 1, 1);
                }
                if (!empty($fraud['fraudtahunsebelumdekom'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Total Fraud Pada Tahun Sebelumnya', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $fraud['fraudtahunsebelumdekom'], 1, 1);
                }
                if (!empty($fraud['selesaitahunlaporandekom'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Telah Diselesaikan Pada Tahun Laporan ', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $fraud['selesaitahunlaporandekom'], 1, 1);
                }
                if (!empty($fraud['prosestahunlaporandekom'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Dalam Proses Penyelesaian Pada Tahun Laporan', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $fraud['prosestahunlaporandekom'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }
                if (!empty($fraud['prosestahunsebelumdekom'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Dalam Proses Penyelesaian Pada Tahun Sebelumnya', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 16, $fraud['prosestahunsebelumdekom'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }
                if (!empty($fraud['belumtahunlaporandekom'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Belum Diupayakan Penyelesaiannya Pada Tahun Laporan', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 16, $fraud['belumtahunlaporandekom'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }
                if (!empty($fraud['belumtahunsebelumdekom'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Belum Diupayakan Penyelesaiannya Pada Tahun Sebelumnya', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 16, $fraud['belumtahunsebelumdekom'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }
                if (!empty($fraud['hukumtahunlaporandekom'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Telah ditindaklanjuti Melalui Proses Hukum Pada Tahun Laporan', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 16, $fraud['hukumtahunlaporandekom'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }
                $this->Ln(5);
            }
        }

        foreach ($filteredData as $fraud) {
            if (!empty($fraud['fraudtahunlaporankartap'])) {
                $estimatedHeightGaji = 8 * 4;
                if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                    $this->AddPage();
                }
                $this->SetFillColor(20, 24, 99);
                $this->SetTextColor(255);
                $this->SetFont('Arial', 'B', 11);
                $this->MultiCell(0, 8, '1.3. Jumlah Penyimpangan Internal oleh Pegawai Tetap', 1, 1, 'L', true);

                $this->SetTextColor(0);
                $this->SetFont('Arial', '', 11);

                if (!empty($fraud['fraudtahunlaporankartap'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Total Fraud Pada Tahun Laporan', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $fraud['fraudtahunlaporankartap'], 1, 1);
                }
                if (!empty($fraud['fraudtahunsebelumkartap'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Total Fraud Pada Tahun Sebelumnya', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $fraud['fraudtahunsebelumkartap'], 1, 1);
                }
                if (!empty($fraud['selesaitahunlaporankartap'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Telah Diselesaikan Pada Tahun Laporan ', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $fraud['selesaitahunlaporankartap'], 1, 1);
                }
                if (!empty($fraud['prosestahunlaporankartap'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Dalam Proses Penyelesaian Pada Tahun Laporan', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $fraud['prosestahunlaporankartap'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }
                if (!empty($fraud['prosestahunsebelumkartap'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Dalam Proses Penyelesaian Pada Tahun Sebelumnya', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 16, $fraud['prosestahunsebelumkartap'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }
                if (!empty($fraud['belumtahunlaporankartap'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Belum Diupayakan Penyelesaiannya Pada Tahun Laporan', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 16, $fraud['belumtahunlaporankartap'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }
                if (!empty($fraud['belumtahunsebelumkartap'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Belum Diupayakan Penyelesaiannya Pada Tahun Sebelumnya', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 16, $fraud['belumtahunsebelumkartap'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }
                if (!empty($fraud['hukumtahunlaporankartap'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Telah ditindaklanjuti Melalui Proses Hukum Pada Tahun Laporan', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 16, $fraud['hukumtahunlaporankartap'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }
                $this->Ln(5);
            }
        }

        foreach ($filteredData as $fraud) {
            if (!empty($fraud['fraudtahunlaporankontrak'])) {
                $estimatedHeightGaji = 8 * 4;
                if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                    $this->AddPage();
                }
                $this->SetFillColor(20, 24, 99);
                $this->SetTextColor(255);
                $this->SetFont('Arial', 'B', 11);
                $this->MultiCell(0, 8, '1.4. Jumlah Penyimpangan Internal oleh Pegawai Tidak Tetap', 1, 1, 'L', true);

                $this->SetTextColor(0);
                $this->SetFont('Arial', '', 11);

                if (!empty($fraud['fraudtahunlaporankontrak'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Total Fraud Pada Tahun Laporan', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $fraud['fraudtahunlaporankontrak'], 1, 1);
                }
                if (!empty($fraud['fraudtahunsebelumkontrak'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Total Fraud Pada Tahun Sebelumnya', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $fraud['fraudtahunsebelumkontrak'], 1, 1);
                }
                if (!empty($fraud['selesaitahunlaporankontrak'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $this->SetFont('Arial', 'B', 11);
                    $this->Cell(100, 8, 'Telah Diselesaikan Pada Tahun Laporan ', 1);
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $fraud['selesaitahunlaporankontrak'], 1, 1);
                }
                if (!empty($fraud['prosestahunlaporankontrak'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Dalam Proses Penyelesaian Pada Tahun Laporan', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $fraud['prosestahunlaporankontrak'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }
                if (!empty($fraud['prosestahunsebelumkontrak'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Dalam Proses Penyelesaian Pada Tahun Sebelumnya', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 16, $fraud['prosestahunsebelumkontrak'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }
                if (!empty($fraud['belumtahunlaporankontrak'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Belum Diupayakan Penyelesaiannya Pada Tahun Laporan', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 16, $fraud['belumtahunlaporankontrak'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }
                if (!empty($fraud['belumtahunsebelumkontrak'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Belum Diupayakan Penyelesaiannya Pada Tahun Sebelumnya', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 16, $fraud['belumtahunsebelumkontrak'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }
                if (!empty($fraud['hukumtahunlaporankontrak'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Telah ditindaklanjuti Melalui Proses Hukum Pada Tahun Laporan', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 16, $fraud['hukumtahunlaporankontrak'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }
                $this->Ln(5);
            }
        }

        $dataId1 = null;
        foreach ($fraudinternalData as $item) {
            if (isset($item['id']) && $item['id'] == 1) {
                $dataId1 = $item;
                break;
            }
        }
        if ($dataId1 !== null && isset($dataId1['keterangan'])) {
            $estimatedHeightGaji = 8 * 4;
            if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                $this->AddPage();
            }
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(50, 8, 'Keterangan:', 0, 1);
            $this->SetFont('Arial', '', 11);
            $this->MultiCell(0, 8, $dataId1['keterangan'], 0, 'J');
            $this->Ln(5);
        }
        $this->Ln(5);
    }

    public function generateMasalah(array $masalahData, array $infoData, $idmasalah = null)
    {
        $filteredData = $masalahData;
        if ($idmasalah !== null) {
            $filteredData = array_filter($masalahData, function ($item) use ($idmasalah) {
                return isset($item['id']) && $item['id'] == $idmasalah;
            });
        }

        $footerHeight = 12;
        $bottomMargin = 15;

        $this->SetFillColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 13);
        $this->MultiCell(0, 10, '16. Permasalahan Hukum yang Dihadapi', 0, 1, 'L');
        $this->Ln(2);

        foreach ($filteredData as $masalah) {
            if (!empty($masalah['hukumperdataselesai'])) {
                $estimatedHeightGaji = 8 * 4;
                if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                    $this->AddPage();
                }
                $this->SetFillColor(20, 24, 99);
                $this->SetTextColor(255);
                $this->SetFont('Arial', 'B', 11);
                $this->MultiCell(0, 8, '1.1. Permasalah Hukum yang Telah Selesai ', 1, 1, 'L', true);

                $this->SetTextColor(0);
                $this->SetFont('Arial', '', 11);

                if (!empty($masalah['hukumperdataselesai'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Permasalahan Hukum Perdata yang Telah Selesai (telah mempunyai kekuatan hukum yang tetap)', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 16, $masalah['hukumperdataselesai'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }
                if (!empty($masalah['hukumpidanaselesai'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Permasalahan Hukum Pidana yang Telah Selesai (telah mempunyai kekuatan hukum yang tetap)', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 16, $masalah['hukumpidanaselesai'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }
                $this->Ln(5);
            }
        }

        foreach ($filteredData as $masalah) {
            if (!empty($masalah['hukumperdataproses'])) {
                $estimatedHeightGaji = 8 * 4;
                if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                    $this->AddPage();
                }
                $this->SetFillColor(20, 24, 99);
                $this->SetTextColor(255);
                $this->SetFont('Arial', 'B', 11);
                $this->MultiCell(0, 8, '1.2. Permasalah Hukum yang Dalam Proses Penyelesaian', 1, 1, 'L', true);

                $this->SetTextColor(0);
                $this->SetFont('Arial', '', 11);

                if (!empty($masalah['hukumperdataproses'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Permasalahan Hukum Perdata yang Dalam Proses Penyelesaian', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 16, $masalah['hukumperdataproses'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }
                if (!empty($masalah['hukumpidanaproses'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Permasalahan Hukum Pidana yang Dalam Proses Penyelesaian', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 16, $masalah['hukumpidanaproses'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }
                $this->Ln(5);
            }
        }

        $dataId1 = null;
        foreach ($masalahData as $item) {
            if (isset($item['id']) && $item['id'] == 1) {
                $dataId1 = $item;
                break;
            }
        }
        if ($dataId1 !== null && isset($dataId1['keterangan'])) {
            $estimatedHeightGaji = 8 * 4;
            if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                $this->AddPage();
            }
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(50, 8, 'Keterangan:', 0, 1);
            $this->SetFont('Arial', '', 11);
            $this->MultiCell(0, 8, $dataId1['keterangan'], 0, 'J');
            $this->Ln(5);
        }
        $this->Ln(5);
    }

    public function generateBentur(array $transaksikepentinganData, array $infoData, $idbentur = null)
    {
        $filteredData = $transaksikepentinganData;
        if ($idbentur !== null) {
            $filteredData = array_filter($transaksikepentinganData, function ($item) use ($idbentur) {
                return isset($item['id']) && $item['id'] == $idbentur;
            });
        }

        $footerHeight = 12;
        $bottomMargin = 15;

        $this->SetFillColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 13);
        $this->MultiCell(0, 10, '17. Transaksi yang Mengandung Benturan Kepentingan', 0, 1, 'L');
        $this->Ln(2);

        foreach ($filteredData as $bentur) {
            if (!empty($bentur['namapihakbenturan'])) {
                $estimatedHeightGaji = 8 * 4;
                if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                    $this->AddPage();
                }
                $this->SetFillColor(20, 24, 99);
                $this->SetTextColor(255);
                $this->SetFont('Arial', 'B', 11);
                $this->MultiCell(0, 8, 'Transaksi yang Mengandung Benturan Kepentingan ', 1, 1, 'L', true);

                $this->SetTextColor(0);
                $this->SetFont('Arial', '', 11);

                if (!empty($bentur['namapihakbenturan'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Nama Pihak yang Memiliki Benturan Kepentingan', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $bentur['namapihakbenturan'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($bentur['jbtbenturan'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Jabatan Pihak yang Memiliki Benturan Kepentingan', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $bentur['jbtbenturan'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($bentur['nikbenturan'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'NIK Pihak yang Memiliki Benturan Kepentingan', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $bentur['nikbenturan'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($bentur['pengambilkeputusan'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Nama Pengambil Keputusan', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $bentur['pengambilkeputusan'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($bentur['jbtpengambilkeputusan'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Jabatan Pengambil Keputusan', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $bentur['jbtpengambilkeputusan'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($bentur['nikpengambilkeputusan'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'NIK Pengambil Keputusan', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $bentur['nikpengambilkeputusan'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($bentur['jenistransaksi'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Jenis Transaksi', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $bentur['jenistransaksi'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($bentur['nilaitransaksi'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Nilai Transaksi (Jutaan Rupiah)', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $formatted_nominal = 'Rp ' . number_format($bentur['nilaitransaksi'], 0, ',', '.');
                    $this->MultiCell(0, 8, $formatted_nominal, 1, 1);

                    $this->SetXY($startX, $endY);
                }

                $this->Ln(5);
            }
        }

        $dataId1 = null;
        foreach ($transaksikepentinganData as $item) {
            if (isset($item['id']) && $item['id'] == 1) {
                $dataId1 = $item;
                break;
            }
        }
        if ($dataId1 !== null && isset($dataId1['keterangan'])) {
            $estimatedHeightGaji = 8 * 4;
            if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                $this->AddPage();
            }
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(50, 8, 'Keterangan:', 0, 1);
            $this->SetFont('Arial', '', 11);
            $this->MultiCell(0, 8, $dataId1['keterangan'], 0, 'J');
            $this->Ln(5);
        }
        $this->Ln(5);
    }

    public function generateDanasosial(array $danasosialData, array $infoData, $iddanasosial = null)
    {
        $filteredData = $danasosialData;
        if ($iddanasosial !== null) {
            $filteredData = array_filter($danasosialData, function ($item) use ($iddanasosial) {
                return isset($item['id']) && $item['id'] == $iddanasosial;
            });
        }

        $footerHeight = 12;
        $bottomMargin = 15;

        $this->SetFillColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 13);
        $this->MultiCell(0, 10, '18. Pemberian Dana untuk Kegiatan Sosial dan Kegiatan Politik', 0, 1, 'L');
        $this->Ln(2);

        foreach ($filteredData as $danasosial) {
            if (!empty($danasosial['tanggalpelaksanaan'])) {
                $estimatedHeightGaji = 8 * 4;
                if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                    $this->AddPage();
                }
                $this->SetFillColor(20, 24, 99);
                $this->SetTextColor(255);
                $this->SetFont('Arial', 'B', 11);
                $this->MultiCell(0, 8, 'Pemberian Dana untuk Kegiatan Sosial dan Kegiatan Politik', 1, 1, 'L', true);

                $this->SetTextColor(0);
                $this->SetFont('Arial', '', 11);

                if (!empty($danasosial['tanggalpelaksanaan'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Tanggal Pelaksanaan', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $danasosial['tanggalpelaksanaan'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($danasosial['jeniskegiatan'])) {
                    // Estimate the height for the "gaji" section
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                
                    // Check if the page height will be exceeded
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage(); // Add a new page if necessary
                    }
                
                    // Set starting X and Y positions
                    $startX = $this->GetX();
                    $startY = $this->GetY();
                
                    // Set font for "Jenis Kegiatan (Sosial/Politik)" heading
                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Jenis Kegiatan (Sosial/Politik)', 1, 'L');
                
                    // Store the Y position after the heading
                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);
                
                    // Set font for the content
                    // $this->SetFont('Arial', '', 11);
                    // $this->MultiCell(0, 8, $danasosial['jeniskegiatan'], 1, 'L');
                
                    // Determine the type of activity
                    $jenis = 'Data Tidak Tersedia'; // Default value
                    switch ($danasosial['jeniskegiatan']) {
                        case '01':
                            $jenis = '01. Kegiatan Sosial';
                            break;
                        case '02':
                            $jenis = '02. Kegiatan Politik';
                            break;
                    }
                
                    // Output the determined type
                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $jenis, 1, 'J');
                
                    // Set the position back to the initial X and the bottom Y of the previous section
                    $this->SetXY($startX, $endY);
                }                

                if (!empty($danasosial['penerimadana'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Penerima Dana', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $danasosial['penerimadana'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($danasosial['penjelasankegiatan'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Penjelasan Kegiatan', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $danasosial['penjelasankegiatan'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($danasosial['jumlah'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Nilai Transaksi', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $formatted_nominal = 'Rp ' . number_format($danasosial['jumlah'], 0, ',', '.');
                    $this->MultiCell(0, 8, $formatted_nominal, 1, 1);

                    $this->SetXY($startX, $endY);
                }

                if (!empty($danasosial['nikpengambilkeputusan'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'NIK Pengambil Keputusan', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $danasosial['nikpengambilkeputusan'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($danasosial['jenistransaksi'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Jenis Transaksi', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $this->MultiCell(0, 8, $danasosial['jenistransaksi'], 1, 'L');

                    $this->SetXY($startX, $endY);
                }

                if (!empty($danasosial['nilaitransaksi'])) {
                    $estimatedHeightGaji = 8 * 4; // Perkiraan tinggi bagian gaji
                    if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                        $this->AddPage();
                    }
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->SetFont('Arial', 'B', 11);
                    $this->MultiCell(100, 8, 'Nilai Transaksi (Jutaan Rupiah)', 1, 'L');

                    $endY = $this->GetY();
                    $this->SetXY($startX + 100, $startY);

                    $this->SetFont('Arial', '', 11);
                    $formatted_nominal = 'Rp ' . number_format($danasosial['nilaitransaksi'], 0, ',', '.');
                    $this->MultiCell(0, 8, $formatted_nominal, 1, 1);

                    $this->SetXY($startX, $endY);
                }

                $this->Ln(5);
            }
        }

        $dataId1 = null;
        foreach ($danasosialData as $item) {
            if (isset($item['id']) && $item['id'] == 1) {
                $dataId1 = $item;
                break;
            }
        }
        if ($dataId1 !== null && isset($dataId1['keterangan'])) {
            $estimatedHeightGaji = 8 * 4;
            if ($this->GetY() + $estimatedHeightGaji + $footerHeight + $bottomMargin > $this->getPageHeight()) {
                $this->AddPage();
            }
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(50, 8, 'Keterangan:', 0, 1);
            $this->SetFont('Arial', '', 11);
            $this->MultiCell(0, 8, $dataId1['keterangan'], 0, 'J');
            $this->Ln(5);
        }
        $this->Ln(5);
    }

    public function Header()
    {
        if (!empty($this->headerData)) {
            if (!empty($this->headerData['logo']) && file_exists($this->headerData['logo'])) {
                try {
                    $this->Image($this->headerData['logo'], 10, 6, 20);
                } catch (Exception $e) {
                    log_message('error', 'Failed to load logo: ' . $e->getMessage());
                }
            }

            $this->SetFont('Arial', 'B', 17);

            $pageWidth = $this->GetPageWidth();
            $rightMargin = 10;

            $this->SetX($pageWidth - $this->GetStringWidth($this->headerData['namabpr'] ?? '') - $rightMargin);
            $this->Cell(0, 5, $this->headerData['namabpr'] ?? '', 0, 1);

            $this->SetFont('Arial', '', 9);
            $this->SetX($pageWidth - $this->GetStringWidth($this->headerData['alamat'] ?? '') - $rightMargin);
            $this->Cell(0, 5, $this->headerData['alamat'] ?? '', 0, 1);

            $this->SetFont('Arial', '', 9);
            $this->SetX($pageWidth - $this->GetStringWidth('Telp: ' . $this->headerData['nomor'] ?? '') - $rightMargin);
            $this->Cell(0, 5, 'Telp: ' . $this->headerData['nomor'] ?? '', 0, 1);

            $contactInfo = sprintf(
                'Website: %s | Email: %s',
                $this->headerData['webbpr'] ?? '',
                $this->headerData['email'] ?? ''
            );
            $this->SetX($pageWidth - $this->GetStringWidth($contactInfo) - $rightMargin);
            $this->Cell(0, 5, $contactInfo, 0, 1);

            $this->Ln(5);
        }
    }

    public function Footer()
    {
        if ($this->isCoverPage) {
            return;
        } else {
            $this->SetY(-12);
            $this->SetFont('Arial', 'I', 8);
            $this->SetTextColor(0);

            $footerText = 'Laporan Transparansi Tata Kelola PT BPR NBP 20 Tahun ' . date('Y') . ' - Halaman ' . $this->PageNo();

            $textWidth = $this->GetStringWidth($footerText);
            $pageWidth = $this->GetPageWidth();
            $centerPos = ($pageWidth - $textWidth) / 2;

            $this->SetX($centerPos);
            $this->Cell(0, 10, $footerText, 0, 0, 'R');

        }
    }



}