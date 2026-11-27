<?php

namespace App\Controllers;

use App\Libraries\GcgFaktorController;
use App\Models\M_nilaifaktor12;

/**
 * Self Assessment Tata Kelola — Faktor 12: Rencana Bisnis BPR (tabel nilaifaktor12, parameter 1–7, ringkasan 8).
 * Logika halaman di App\Libraries\GcgFaktorController; ekspor TXT dipakai Showsemesteran.
 */
class Faktor12 extends GcgFaktorController
{
    protected const NO = 12;
    protected const TITLE = 'Rencana Bisnis BPR';
    protected const ROUTE = 'faktor12';
    protected const T_PARAM = 'faktor12';
    protected const T_NILAI = 'nilaifaktor12';
    protected const C_ID = 'faktor12id';
    protected const C_N = 'nfaktor12';
    protected const T_KOM = 'faktor12_comments';
    protected const T_READ = 'comment_reads12';
    protected const READ_HAS_FID = false;
    protected const SUMMARY_ID = 8;
    /** Tabel faktor12 menulis kode dengan nol berlebih (S01201 …); pakai kode pelaporan TXT. */
    protected const CODES = [1 => 'S1201', 2 => 'S1202', 3 => 'S1203', 4 => 'P1201', 5 => 'P1202', 6 => 'H1201', 7 => 'H1202'];

    protected function penjelasan(int $nilai): string
    {
        return (new M_nilaifaktor12())->getPenjelasanNilai($nilai);
    }

    /** Ekspor TXT (dipakai Showsemesteran). Format keluaran tetap. */
    public function exporttxtfaktor12()
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
        $data_faktor = (new M_nilaifaktor12())
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
        $sanitizeTxtfaktor12 = static function ($text) {
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
            1 => 'S1201',
            2 => 'S1202',
            3 => 'S1203',
            4 => 'P1201',
            5 => 'P1202',
            6 => 'H1201',
            7 => 'H1202'
        ];

        // Index data by faktor12id
        $indexedData = [];
        foreach ($data_faktor as $row) {
            if (isset($row['faktor12id'])) {
                $indexedData[$row['faktor12id']] = $row;
            }
        }

        $output = '';

        // HEADER
        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|1012|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";

        // Baris kosong S1200
        $output .= "D01|S1200|\r\n";

        // Struktur (S1201 - S1203)
        for ($faktor12Id = 1; $faktor12Id <= 3; $faktor12Id++) {
            $kode = $kodeMap[$faktor12Id];
            if (isset($indexedData[$faktor12Id])) {
                $row = $indexedData[$faktor12Id];
                $keterangan = $sanitizeTxtfaktor12($row['keterangan'] ?? '-');
            } else {
                $keterangan = '';
            }
            $output .= "D01|{$kode}|{$keterangan}\r\n";
        }

        // Baris kosong P0900
        $output .= "D01|P1200|\r\n";

        // Proses (P1201 - P1202)
        for ($faktor12Id = 4; $faktor12Id <= 5; $faktor12Id++) {
            $kode = $kodeMap[$faktor12Id];
            if (isset($indexedData[$faktor12Id])) {
                $row = $indexedData[$faktor12Id];
                $keterangan = $sanitizeTxtfaktor12($row['keterangan'] ?? '-');
            } else {
                $keterangan = '';
            }
            $output .= "D01|{$kode}|{$keterangan}\r\n";
        }

        // Baris kosong H1200
        $output .= "D01|H1200|\r\n";

        // Hasil (H1201 - H1202)
        for ($faktor12Id = 6; $faktor12Id <= 7; $faktor12Id++) {
            $kode = $kodeMap[$faktor12Id];
            if (isset($indexedData[$faktor12Id])) {
                $row = $indexedData[$faktor12Id];
                $keterangan = $sanitizeTxtfaktor12($row['keterangan'] ?? '-');
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

        // Data kesimpulan (F0901 - F0907)
        $positifstruktur = $sanitizeTxtfaktor12($kesimpulanData['positifstruktur'] ?? '');
        $negatifstruktur = $sanitizeTxtfaktor12($kesimpulanData['negatifstruktur'] ?? '');
        $positifproses = $sanitizeTxtfaktor12($kesimpulanData['positifproses'] ?? '');
        $negatifproses = $sanitizeTxtfaktor12($kesimpulanData['negatifproses'] ?? '');
        $positifhasil = $sanitizeTxtfaktor12($kesimpulanData['positifhasil'] ?? '');
        $negatifhasil = $sanitizeTxtfaktor12($kesimpulanData['negatifhasil'] ?? '');

        // Hitung rata-rata atau ambil dari data
        $rataRata = (new M_nilaifaktor12())->hitungRataRata(1, $kodebpr, $periodeId);
        $nfaktor = round($rataRata, 2);

        $output .= "D01|F1201|{$positifstruktur}\r\n";
        $output .= "D01|F1202|{$negatifstruktur}\r\n";
        $output .= "D01|F1203|{$positifproses}\r\n";
        $output .= "D01|F1204|{$negatifproses}\r\n";
        $output .= "D01|F1205|{$positifhasil}\r\n";
        $output .= "D01|F1206|{$negatifhasil}\r\n";
        $output .= "D01|F1207|{$nfaktor}\r\n";

        // Filename
        $filename = "TKSBPRKS-1012-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

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
