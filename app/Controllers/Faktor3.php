<?php

namespace App\Controllers;

use App\Libraries\GcgFaktorController;
use App\Models\M_nilaifaktor3;

/**
 * Self Assessment Tata Kelola — Faktor 3: Pelaksanaan Tugas, Tanggung Jawab, dan Wewenang Dewan Komisaris (tabel nilaifaktor3, parameter 1–26, ringkasan 27).
 * Logika halaman di App\Libraries\GcgFaktorController; ekspor TXT dipakai Showsemesteran.
 */
class Faktor3 extends GcgFaktorController
{
    protected const NO = 3;
    protected const TITLE = 'Pelaksanaan Tugas, Tanggung Jawab, dan Wewenang Dewan Komisaris';
    protected const ROUTE = 'faktor3';
    protected const T_PARAM = 'faktor3';
    protected const T_NILAI = 'nilaifaktor3';
    protected const C_ID = 'faktor3id';
    protected const C_N = 'nfaktor3';
    protected const T_KOM = 'faktor3_comments';
    protected const T_READ = 'comment_reads3';
    protected const READ_HAS_FID = false;
    protected const SUMMARY_ID = 27;

    protected function penjelasan(int $nilai): string
    {
        return (new M_nilaifaktor3())->getPenjelasanNilai($nilai);
    }

    /** Ekspor TXT (dipakai Showsemesteran). Format keluaran tetap. */
    public function exporttxtfaktor3()
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
        $data_faktor = (new M_nilaifaktor3())
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
        $sanitizeTxtfaktor3 = static function ($text) {
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

            // Remove non-printable ASCII characters
            $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);

            // Trim and remove multiple spaces
            return trim(preg_replace('/\s+/', ' ', $text));
        };

        $kodeMap = [
            1 => 'S0301',
            2 => 'S0302',
            3 => 'S0303',
            4 => 'S0304',
            5 => 'S0305',
            6 => 'S0306',
            7 => 'S0307',
            8 => 'S0308',
            9 => 'P0301',
            10 => 'P0302',
            11 => 'P0303',
            12 => 'P0304',
            13 => 'P0305',
            14 => 'P0306',
            15 => 'P0307',
            16 => 'P0308',
            17 => 'P0309',
            18 => 'P0310',
            19 => 'P0311',
            20 => 'P0312',
            21 => 'P0313',
            22 => 'H0301',
            23 => 'H0302',
            24 => 'H0303',
            25 => 'H0304',
            26 => 'H0305'
        ];

        // Index data by faktor2id
        $indexedData = [];
        foreach ($data_faktor as $row) {
            if (isset($row['faktor3id'])) {
                $indexedData[$row['faktor3id']] = $row;
            }
        }

        $output = '';

        // HEADER
        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|1003|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";

        // Baris kosong S0300
        $output .= "D01|S0300|\r\n";

        // Struktur (S0301 - S0308)
        for ($faktor3Id = 1; $faktor3Id <= 8; $faktor3Id++) {
            $kode = $kodeMap[$faktor3Id];
            if (isset($indexedData[$faktor3Id])) {
                $row = $indexedData[$faktor3Id];
                $keterangan = $sanitizeTxtfaktor3($row['keterangan'] ?? '-');
            } else {
                $keterangan = '';
            }
            $output .= "D01|{$kode}|{$keterangan}\r\n";
        }

        // Baris kosong P0300
        $output .= "D01|P0300|\r\n";

        // Proses (P0301 - P0313)
        for ($faktor3Id = 9; $faktor3Id <= 21; $faktor3Id++) {
            $kode = $kodeMap[$faktor3Id];
            if (isset($indexedData[$faktor3Id])) {
                $row = $indexedData[$faktor3Id];
                $keterangan = $sanitizeTxtfaktor3($row['keterangan'] ?? '-');
            } else {
                $keterangan = '';
            }
            $output .= "D01|{$kode}|{$keterangan}\r\n";
        }

        // Baris kosong H0300
        $output .= "D01|H0300|\r\n";

        // Hasil (H0301 - H0305)
        for ($faktor3Id = 22; $faktor3Id <= 26; $faktor3Id++) {
            $kode = $kodeMap[$faktor3Id];
            if (isset($indexedData[$faktor3Id])) {
                $row = $indexedData[$faktor3Id];
                $keterangan = $sanitizeTxtfaktor3($row['keterangan'] ?? '-');
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

        // Data kesimpulan (F0301 - F0307)
        $positifstruktur = $sanitizeTxtfaktor3($kesimpulanData['positifstruktur'] ?? '');
        $negatifstruktur = $sanitizeTxtfaktor3($kesimpulanData['negatifstruktur'] ?? '');
        $positifproses = $sanitizeTxtfaktor3($kesimpulanData['positifproses'] ?? '');
        $negatifproses = $sanitizeTxtfaktor3($kesimpulanData['negatifproses'] ?? '');
        $positifhasil = $sanitizeTxtfaktor3($kesimpulanData['positifhasil'] ?? '');
        $negatifhasil = $sanitizeTxtfaktor3($kesimpulanData['negatifhasil'] ?? '');

        // Hitung rata-rata atau ambil dari data
        $rataRata = (new M_nilaifaktor3())->hitungRataRata(1, $kodebpr, $periodeId);
        $nfaktor = round($rataRata, 2);

        $output .= "D01|F0301|{$positifstruktur}\r\n";
        $output .= "D01|F0302|{$negatifstruktur}\r\n";
        $output .= "D01|F0303|{$positifproses}\r\n";
        $output .= "D01|F0304|{$negatifproses}\r\n";
        $output .= "D01|F0305|{$positifhasil}\r\n";
        $output .= "D01|F0306|{$negatifhasil}\r\n";
        $output .= "D01|F0307|{$nfaktor}\r\n";

        // Filename
        $filename = "TKSBPRKS-1003-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

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
