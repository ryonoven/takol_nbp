<?php

namespace App\Controllers;

use App\Libraries\GcgFaktorController;
use App\Models\M_nilaifaktor;

/**
 * Self Assessment Tata Kelola — Faktor 1: Aspek Pemegang Saham (tabel nilaifaktor, parameter 1–11, ringkasan 12).
 * Logika halaman di App\Libraries\GcgFaktorController; ekspor TXT dipakai Showsemesteran.
 */
class Faktor extends GcgFaktorController
{
    protected const NO = 1;
    protected const TITLE = 'Aspek Pemegang Saham';
    protected const ROUTE = 'faktor';
    protected const T_PARAM = 'faktor';
    protected const T_NILAI = 'nilaifaktor';
    protected const C_ID = 'faktor1id';
    protected const C_N = 'nfaktor';
    protected const T_KOM = 'faktor_comments';
    protected const T_READ = 'comment_reads';
    protected const SUMMARY_ID = 12;

    protected function penjelasan(int $nilai): string
    {
        return (new M_nilaifaktor())->getPenjelasanNilai($nilai);
    }

    /** Ekspor TXT TKSBPRKS-1001 (dipakai Showsemesteran). Format keluaran tetap. */
    public function exporttxtfaktor()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        // Periode aktif bukan milik BPR user → tidak ada berkas
        if (!$this->periodeId || !$this->userKodebpr) {
            return service('response')->setContentType('text/plain')->setBody('');
        }
        $kodebpr = $this->userKodebpr;
        $periodeId = $this->periodeId;

        // Periode detail
        $periodeDetail = $this->periode;

        $modalinti = $periodeDetail['modalinti'];
        $nomorsurat = $periodeDetail['nomorsurat'];
        $jeniskoreksi = $periodeDetail['jeniskoreksi'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];
        $kategori = $periodeDetail['kategori'];

        $tahun = $periodeDetail['tahun'] ?? date('Y');
        $semester = $periodeDetail['semester'] ?? '';

        if (strtolower($semester) === 'ganjil' || $semester === '1') {
            $exportDate = "{$tahun}-06-30";
        } else {
            $exportDate = "{$tahun}-12-31";
        }

        $titleDate = str_replace('-', '', $exportDate);

        // Fetch data
        $data_faktor = (new M_nilaifaktor())
            ->getDataByKodebprAndPeriode($kodebpr, $periodeId);

        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        // BPR Info
        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'] ?? '';
            $kodejenis = $infobpr['kodejenis'] ?? '';
        }

        // Sanitizer
        $sanitizeTxtfaktor1 = static function ($text) {
            // Replace newlines with space
            $text = str_replace(["\r", "\n"], ' ', $text);

            // Replace special characters
            $text = str_replace(
                [
                    "\u{201C}",
                    "\u{201D}", // " "
                    "\u{2018}",
                    "\u{2019}", // ' '
                    '%',
                    '|'
                ],
                [
                    '"',
                    '"',
                    "'",
                    "'",
                    ' persen ',
                    ' '
                ],
                $text
            );

            $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);

            return trim(preg_replace('/\s+/', ' ', $text));
        };

        $kodeMap = [
            1 => 'S0101',
            2 => 'S0102',
            3 => 'P0101',
            4 => 'P0102',
            5 => 'P0103',
            6 => 'P0104',
            7 => 'P0105',
            8 => 'H0101',
            9 => 'H0102',
            10 => 'H0103',
            11 => 'H0104',
        ];

        // Index data by faktor1id
        $indexedData = [];
        foreach ($data_faktor as $row) {
            if (isset($row['faktor1id'])) {
                $indexedData[$row['faktor1id']] = $row;
            }
        }

        $output = '';

        // HEADER
        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|1001|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";

        $output .= "D01|S0100|\r\n";

        // Struktur (S0101 - S0102)
        for ($faktorId = 1; $faktorId <= 2; $faktorId++) {
            $kode = $kodeMap[$faktorId];
            if (isset($indexedData[$faktorId])) {
                $row = $indexedData[$faktorId];
                $keterangan = $sanitizeTxtfaktor1($row['keterangan'] ?? '-');
            } else {
                $keterangan = '';
            }
            $output .= "D01|{$kode}|{$keterangan}\r\n";
        }

        // Baris kosong P0100
        $output .= "D01|P0100|\r\n";

        // Proses (P0101 - P0105)
        for ($faktorId = 3; $faktorId <= 7; $faktorId++) {
            $kode = $kodeMap[$faktorId];
            if (isset($indexedData[$faktorId])) {
                $row = $indexedData[$faktorId];
                $keterangan = $sanitizeTxtfaktor1($row['keterangan'] ?? '-');
            } else {
                $keterangan = '';
            }
            $output .= "D01|{$kode}|{$keterangan}\r\n";
        }

        // Baris kosong H0100
        $output .= "D01|H0100|\r\n";

        // Hasil (H0101 - H0104)
        for ($faktorId = 8; $faktorId <= 11; $faktorId++) {
            $kode = $kodeMap[$faktorId];
            if (isset($indexedData[$faktorId])) {
                $row = $indexedData[$faktorId];
                $keterangan = $sanitizeTxtfaktor1($row['keterangan'] ?? '-');
            } else {
                $keterangan = '';
            }
            $output .= "D01|{$kode}|{$keterangan}\r\n";
        }

        // Ambil data kesimpulan dari salah satu faktor (biasanya faktor 1 atau yang memiliki data kesimpulan)
        $kesimpulanData = null;
        foreach ($indexedData as $row) {
            if (isset($row['positifstruktur']) && !empty($row['positifstruktur'])) {
                $kesimpulanData = $row;
                break;
            }
        }

        // Data kesimpulan (F0101 - F0107)
        $positifstruktur = $sanitizeTxtfaktor1($kesimpulanData['positifstruktur'] ?? '');
        $negatifstruktur = $sanitizeTxtfaktor1($kesimpulanData['negatifstruktur'] ?? '');
        $positifproses = $sanitizeTxtfaktor1($kesimpulanData['positifproses'] ?? '');
        $negatifproses = $sanitizeTxtfaktor1($kesimpulanData['negatifproses'] ?? '');
        $positifhasil = $sanitizeTxtfaktor1($kesimpulanData['positifhasil'] ?? '');
        $negatifhasil = $sanitizeTxtfaktor1($kesimpulanData['negatifhasil'] ?? '');

        // Hitung rata-rata atau ambil dari data
        $rataRata = (new M_nilaifaktor())->hitungRataRata(1, $kodebpr, $periodeId);
        $nfaktor = round($rataRata, 2);

        $output .= "D01|F0101|{$positifstruktur}\r\n";
        $output .= "D01|F0102|{$negatifstruktur}\r\n";
        $output .= "D01|F0103|{$positifproses}\r\n";
        $output .= "D01|F0104|{$negatifproses}\r\n";
        $output .= "D01|F0105|{$positifhasil}\r\n";
        $output .= "D01|F0106|{$negatifhasil}\r\n";
        $output .= "D01|F0107|{$nfaktor}\r\n";

        // Filename
        $filename = "TKSBPRKS-1001-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        // Response
        $response = service('response');
        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader(
            'Content-Disposition',
            'attachment; filename="' . $filename . '"'
        );

        return $response->setBody($output);
    }
}
