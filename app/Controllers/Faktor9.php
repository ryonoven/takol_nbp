<?php

namespace App\Controllers;

use App\Libraries\GcgFaktorController;
use App\Models\M_nilaifaktor9;

/**
 * Self Assessment Tata Kelola — Faktor 9: Penerapan Manajemen Risiko dan Strategi Anti Fraud (tabel nilaifaktor9, parameter 1–17, ringkasan 18).
 * Logika halaman di App\Libraries\GcgFaktorController; ekspor TXT dipakai Showsemesteran.
 */
class Faktor9 extends GcgFaktorController
{
    protected const NO = 9;
    protected const TITLE = 'Penerapan Manajemen Risiko dan Strategi Anti Fraud';
    protected const ROUTE = 'faktor9';
    protected const T_PARAM = 'faktor9';
    protected const T_NILAI = 'nilaifaktor9';
    protected const C_ID = 'faktor9id';
    protected const C_N = 'nfaktor9';
    protected const T_KOM = 'faktor9_comments';
    protected const T_READ = 'comment_reads9';
    protected const READ_HAS_FID = false;
    protected const SUMMARY_ID = 18;

    protected function penjelasan(int $nilai): string
    {
        return (new M_nilaifaktor9())->getPenjelasanNilai($nilai);
    }

    /** Ekspor TXT (dipakai Showsemesteran). Format keluaran tetap. */
    public function exporttxtfaktor9()
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
        $data_faktor = (new M_nilaifaktor9())
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
        $sanitizeTxtfaktor9 = static function ($text) {
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
            1 => 'S0901',
            2 => 'S0902',
            3 => 'S0903',
            4 => 'P0901',
            5 => 'P0902',
            6 => 'P0903',
            7 => 'P0904',
            8 => 'P0905',
            9 => 'P0906',
            10 => 'P0907',
            11 => 'P0908',
            12 => 'P0909',
            13 => 'P0910',
            14 => 'P0911',
            15 => 'H0901',
            16 => 'H0902',
            17 => 'H0903'
        ];

        // Index data by faktor9id
        $indexedData = [];
        foreach ($data_faktor as $row) {
            if (isset($row['faktor9id'])) {
                $indexedData[$row['faktor9id']] = $row;
            }
        }

        $output = '';

        // HEADER
        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|1009|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";

        // Baris kosong S0900
        $output .= "D01|S0900|\r\n";

        // Struktur (S0901 - S0903)
        for ($faktor9Id = 1; $faktor9Id <= 3; $faktor9Id++) {
            $kode = $kodeMap[$faktor9Id];
            if (isset($indexedData[$faktor9Id])) {
                $row = $indexedData[$faktor9Id];
                $keterangan = $sanitizeTxtfaktor9($row['keterangan'] ?? '-');
            } else {
                $keterangan = '';
            }
            $output .= "D01|{$kode}|{$keterangan}\r\n";
        }

        // Baris kosong P0900
        $output .= "D01|P0900|\r\n";

        // Proses (P0901 - P0912)
        for ($faktor9Id = 4; $faktor9Id <= 15; $faktor9Id++) {
            $kode = $kodeMap[$faktor9Id];
            if (isset($indexedData[$faktor9Id])) {
                $row = $indexedData[$faktor9Id];
                $keterangan = $sanitizeTxtfaktor9($row['keterangan'] ?? '-');
            } else {
                $keterangan = '';
            }
            $output .= "D01|{$kode}|{$keterangan}\r\n";
        }

        // Baris kosong H0900
        $output .= "D01|H0900|\r\n";

        // Hasil (H0901 - H0902)
        for ($faktor9Id = 16; $faktor9Id <= 17; $faktor9Id++) {
            $kode = $kodeMap[$faktor9Id];
            if (isset($indexedData[$faktor9Id])) {
                $row = $indexedData[$faktor9Id];
                $keterangan = $sanitizeTxtfaktor9($row['keterangan'] ?? '-');
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
        $positifstruktur = $sanitizeTxtfaktor9($kesimpulanData['positifstruktur'] ?? '');
        $negatifstruktur = $sanitizeTxtfaktor9($kesimpulanData['negatifstruktur'] ?? '');
        $positifproses = $sanitizeTxtfaktor9($kesimpulanData['positifproses'] ?? '');
        $negatifproses = $sanitizeTxtfaktor9($kesimpulanData['negatifproses'] ?? '');
        $positifhasil = $sanitizeTxtfaktor9($kesimpulanData['positifhasil'] ?? '');
        $negatifhasil = $sanitizeTxtfaktor9($kesimpulanData['negatifhasil'] ?? '');

        // Hitung rata-rata atau ambil dari data
        $rataRata = (new M_nilaifaktor9())->hitungRataRata(1, $kodebpr, $periodeId);
        $nfaktor = round($rataRata, 2);

        $output .= "D01|F0901|{$positifstruktur}\r\n";
        $output .= "D01|F0902|{$negatifstruktur}\r\n";
        $output .= "D01|F0903|{$positifproses}\r\n";
        $output .= "D01|F0904|{$negatifproses}\r\n";
        $output .= "D01|F0905|{$positifhasil}\r\n";
        $output .= "D01|F0906|{$negatifhasil}\r\n";
        $output .= "D01|F0907|{$nfaktor}\r\n";

        // Filename
        $filename = "TKSBPRKS-1009-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

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
