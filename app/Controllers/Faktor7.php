<?php

namespace App\Controllers;

use App\Libraries\GcgFaktorController;
use App\Models\M_nilaifaktor7;

/**
 * Self Assessment Tata Kelola — Faktor 7: Penerapan Fungsi Audit Intern (tabel nilaifaktor7, parameter 1–11, ringkasan 12).
 * Logika halaman di App\Libraries\GcgFaktorController; ekspor TXT dipakai Showsemesteran.
 */
class Faktor7 extends GcgFaktorController
{
    protected const NO = 7;
    protected const TITLE = 'Penerapan Fungsi Audit Intern';
    protected const ROUTE = 'faktor7';
    protected const T_PARAM = 'faktor7';
    protected const T_NILAI = 'nilaifaktor7';
    protected const C_ID = 'faktor7id';
    protected const C_N = 'nfaktor7';
    protected const T_KOM = 'faktor7_comments';
    protected const T_READ = 'comment_reads7';
    protected const READ_HAS_FID = false;
    protected const SUMMARY_ID = 12;

    protected function penjelasan(int $nilai): string
    {
        return (new M_nilaifaktor7())->getPenjelasanNilai($nilai);
    }

    /** Ekspor TXT (dipakai Showsemesteran). Format keluaran tetap. */
    public function exporttxtfaktor7()
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
        $data_faktor = (new M_nilaifaktor7())
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
        $sanitizeTxtfaktor7 = static function ($text) {
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
            1 => 'S0701',
            2 => 'S0702',
            3 => 'S0703',
            4 => 'S0704',
            5 => 'S0705',
            6 => 'P0701',
            7 => 'P0702',
            8 => 'P0703',
            9 => 'P0704',
            10 => 'H0701',
            11 => 'H0702'
        ];

        // Index data by faktor7id
        $indexedData = [];
        foreach ($data_faktor as $row) {
            if (isset($row['faktor7id'])) {
                $indexedData[$row['faktor7id']] = $row;
            }
        }

        $output = '';

        // HEADER
        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|1007|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";

        // Baris kosong S0700
        $output .= "D01|S0700|\r\n";

        // Struktur (S0701 - S0704)
        for ($faktor7Id = 1; $faktor7Id <= 5; $faktor7Id++) {
            $kode = $kodeMap[$faktor7Id];
            if (isset($indexedData[$faktor7Id])) {
                $row = $indexedData[$faktor7Id];
                $keterangan = $sanitizeTxtfaktor7($row['keterangan'] ?? '-');
            } else {
                $keterangan = '';
            }
            $output .= "D01|{$kode}|{$keterangan}\r\n";
        }

        // Baris kosong P0700
        $output .= "D01|P0700|\r\n";

        // Proses (P0701 - P0703)
        for ($faktor7Id = 6; $faktor7Id <= 9; $faktor7Id++) {
            $kode = $kodeMap[$faktor7Id];
            if (isset($indexedData[$faktor7Id])) {
                $row = $indexedData[$faktor7Id];
                $keterangan = $sanitizeTxtfaktor7($row['keterangan'] ?? '-');
            } else {
                $keterangan = '';
            }
            $output .= "D01|{$kode}|{$keterangan}\r\n";
        }

        // Baris kosong H0700
        $output .= "D01|H0700|\r\n";

        // Hasil (H0701 - H0702)
        for ($faktor7Id = 10; $faktor7Id <= 11; $faktor7Id++) {
            $kode = $kodeMap[$faktor7Id];
            if (isset($indexedData[$faktor7Id])) {
                $row = $indexedData[$faktor7Id];
                $keterangan = $sanitizeTxtfaktor7($row['keterangan'] ?? '-');
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

        // Data kesimpulan (F0701 - F0707)
        $positifstruktur = $sanitizeTxtfaktor7($kesimpulanData['positifstruktur'] ?? '');
        $negatifstruktur = $sanitizeTxtfaktor7($kesimpulanData['negatifstruktur'] ?? '');
        $positifproses = $sanitizeTxtfaktor7($kesimpulanData['positifproses'] ?? '');
        $negatifproses = $sanitizeTxtfaktor7($kesimpulanData['negatifproses'] ?? '');
        $positifhasil = $sanitizeTxtfaktor7($kesimpulanData['positifhasil'] ?? '');
        $negatifhasil = $sanitizeTxtfaktor7($kesimpulanData['negatifhasil'] ?? '');

        // Hitung rata-rata atau ambil dari data
        $rataRata = (new M_nilaifaktor7())->hitungRataRata(1, $kodebpr, $periodeId);
        $nfaktor = round($rataRata, 2);

        $output .= "D01|F0701|{$positifstruktur}\r\n";
        $output .= "D01|F0702|{$negatifstruktur}\r\n";
        $output .= "D01|F0703|{$positifproses}\r\n";
        $output .= "D01|F0704|{$negatifproses}\r\n";
        $output .= "D01|F0705|{$positifhasil}\r\n";
        $output .= "D01|F0706|{$negatifhasil}\r\n";
        $output .= "D01|F0707|{$nfaktor}\r\n";

        // Filename
        $filename = "TKSBPRKS-1007-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

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
