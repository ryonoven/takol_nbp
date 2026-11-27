<?php

namespace App\Controllers;

use App\Libraries\GcgFaktorController;
use App\Models\M_nilaifaktor6;

/**
 * Self Assessment Tata Kelola — Faktor 6: Penerapan Fungsi Kepatuhan (tabel nilaifaktor6, parameter 1–9, ringkasan 10).
 * Logika halaman di App\Libraries\GcgFaktorController; ekspor TXT dipakai Showsemesteran.
 */
class Faktor6 extends GcgFaktorController
{
    protected const NO = 6;
    protected const TITLE = 'Penerapan Fungsi Kepatuhan';
    protected const ROUTE = 'faktor6';
    protected const T_PARAM = 'faktor6';
    protected const T_NILAI = 'nilaifaktor6';
    protected const C_ID = 'faktor6id';
    protected const C_N = 'nfaktor6';
    protected const T_KOM = 'faktor6_comments';
    protected const T_READ = 'comment_reads6';
    protected const READ_HAS_FID = false;
    protected const SUMMARY_ID = 10;

    protected function penjelasan(int $nilai): string
    {
        return (new M_nilaifaktor6())->getPenjelasanNilai($nilai);
    }

    /** Ekspor TXT (dipakai Showsemesteran). Format keluaran tetap. */
    public function exporttxtfaktor6()
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
        $data_faktor = (new M_nilaifaktor6())
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
        $sanitizeTxtfaktor6 = static function ($text) {
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
            1 => 'S0601',
            2 => 'S0602',
            3 => 'S0603',
            4 => 'S0604',
            5 => 'P0601',
            6 => 'P0602',
            7 => 'P0603',
            8 => 'H0601',
            9 => 'H0602'
        ];

        // Index data by faktor6id
        $indexedData = [];
        foreach ($data_faktor as $row) {
            if (isset($row['faktor6id'])) {
                $indexedData[$row['faktor6id']] = $row;
            }
        }

        $output = '';

        // HEADER
        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|1006|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";

        // Baris kosong S0600
        $output .= "D01|S0600|\r\n";

        // Struktur (S0601 - S0604)
        for ($faktor6Id = 1; $faktor6Id <= 4; $faktor6Id++) {
            $kode = $kodeMap[$faktor6Id];
            if (isset($indexedData[$faktor6Id])) {
                $row = $indexedData[$faktor6Id];
                $keterangan = $sanitizeTxtfaktor6($row['keterangan'] ?? '-');
            } else {
                $keterangan = '';
            }
            $output .= "D01|{$kode}|{$keterangan}\r\n";
        }

        // Baris kosong P0600
        $output .= "D01|P0600|\r\n";

        // Proses (P0601 - P0603)
        for ($faktor6Id = 5; $faktor6Id <= 7; $faktor6Id++) {
            $kode = $kodeMap[$faktor6Id];
            if (isset($indexedData[$faktor6Id])) {
                $row = $indexedData[$faktor6Id];
                $keterangan = $sanitizeTxtfaktor6($row['keterangan'] ?? '-');
            } else {
                $keterangan = '';
            }
            $output .= "D01|{$kode}|{$keterangan}\r\n";
        }

        // Baris kosong H0600
        $output .= "D01|H0600|\r\n";

        // Hasil (H0601 - H0602)
        for ($faktor6Id = 8; $faktor6Id <= 9; $faktor6Id++) {
            $kode = $kodeMap[$faktor6Id];
            if (isset($indexedData[$faktor6Id])) {
                $row = $indexedData[$faktor6Id];
                $keterangan = $sanitizeTxtfaktor6($row['keterangan'] ?? '-');
            } else {
                $keterangan = '';
            }
            $output .= "D01|{$kode}|{$keterangan}\r\n";
        }

        $kesimpulanData = null;
        foreach ($indexedData as $row) {
            if (isset($row['positifstruktur']) && !empty($row['positifstruktur'])) {
                $kesimpulanData = $row;
                break;
            }
        }

        // Data kesimpulan (F0601 - F0607)
        $positifstruktur = $sanitizeTxtfaktor6($kesimpulanData['positifstruktur'] ?? '');
        $negatifstruktur = $sanitizeTxtfaktor6($kesimpulanData['negatifstruktur'] ?? '');
        $positifproses = $sanitizeTxtfaktor6($kesimpulanData['positifproses'] ?? '');
        $negatifproses = $sanitizeTxtfaktor6($kesimpulanData['negatifproses'] ?? '');
        $positifhasil = $sanitizeTxtfaktor6($kesimpulanData['positifhasil'] ?? '');
        $negatifhasil = $sanitizeTxtfaktor6($kesimpulanData['negatifhasil'] ?? '');

        // Hitung rata-rata atau ambil dari data
        $rataRata = (new M_nilaifaktor6())->hitungRataRata(1, $kodebpr, $periodeId);
        $nfaktor = round($rataRata, 2);

        $output .= "D01|F0601|{$positifstruktur}\r\n";
        $output .= "D01|F0602|{$negatifstruktur}\r\n";
        $output .= "D01|F0603|{$positifproses}\r\n";
        $output .= "D01|F0604|{$negatifproses}\r\n";
        $output .= "D01|F0605|{$positifhasil}\r\n";
        $output .= "D01|F0606|{$negatifhasil}\r\n";
        $output .= "D01|F0607|{$nfaktor}\r\n";

        // Filename
        $filename = "TKSBPRKS-1006-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

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
