<?php

namespace App\Controllers;

use App\Libraries\GcgFaktorController;
use App\Models\M_nilaifaktor8;

/**
 * Self Assessment Tata Kelola — Faktor 8: Penerapan Fungsi Audit Ekstern (tabel nilaifaktor8, parameter 1–5, ringkasan 6).
 * Logika halaman di App\Libraries\GcgFaktorController; ekspor TXT dipakai Showsemesteran.
 */
class Faktor8 extends GcgFaktorController
{
    protected const NO = 8;
    protected const TITLE = 'Penerapan Fungsi Audit Ekstern';
    protected const ROUTE = 'faktor8';
    protected const T_PARAM = 'faktor8';
    protected const T_NILAI = 'nilaifaktor8';
    protected const C_ID = 'faktor8id';
    protected const C_N = 'nfaktor8';
    protected const T_KOM = 'faktor8_comments';
    protected const T_READ = 'comment_reads8';
    protected const READ_HAS_FID = false;
    protected const SUMMARY_ID = 6;

    protected function penjelasan(int $nilai): string
    {
        return (new M_nilaifaktor8())->getPenjelasanNilai($nilai);
    }

    /** Ekspor TXT (dipakai Showsemesteran). Format keluaran tetap. */
    public function exporttxtfaktor8()
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
        $data_faktor = (new M_nilaifaktor8())
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
        $sanitizeTxtfaktor8 = static function ($text) {
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
            1 => 'S0801',
            2 => 'P0801',
            3 => 'P0802',
            4 => 'H0801',
            5 => 'H0802'
        ];

        // Index data by faktor8id
        $indexedData = [];
        foreach ($data_faktor as $row) {
            if (isset($row['faktor8id'])) {
                $indexedData[$row['faktor8id']] = $row;
            }
        }

        $output = '';

        // HEADER
        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|1008|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";

        // Baris kosong S0800
        $output .= "D01|S0800|\r\n";

        // Struktur (S0801)
        $faktor8Id = 1;
        $kode = $kodeMap[$faktor8Id];
        if (isset($indexedData[$faktor8Id])) {
            $row = $indexedData[$faktor8Id];
            $keterangan = $sanitizeTxtfaktor8($row['keterangan'] ?? '-');
        } else {
            $keterangan = '';
        }
        $output .= "D01|{$kode}|{$keterangan}\r\n";

        // Baris kosong P0800
        $output .= "D01|P0800|\r\n";

        // Proses (P0801 - P0802)
        for ($faktor8Id = 2; $faktor8Id <= 3; $faktor8Id++) {
            $kode = $kodeMap[$faktor8Id];
            if (isset($indexedData[$faktor8Id])) {
                $row = $indexedData[$faktor8Id];
                $keterangan = $sanitizeTxtfaktor8($row['keterangan'] ?? '-');
            } else {
                $keterangan = '';
            }
            $output .= "D01|{$kode}|{$keterangan}\r\n";
        }

        // Baris kosong H0800
        $output .= "D01|H0800|\r\n";

        // Hasil (H0801 - H0802)
        for ($faktor8Id = 4; $faktor8Id <= 5; $faktor8Id++) {
            $kode = $kodeMap[$faktor8Id];
            if (isset($indexedData[$faktor8Id])) {
                $row = $indexedData[$faktor8Id];
                $keterangan = $sanitizeTxtfaktor8($row['keterangan'] ?? '-');
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

        // Data kesimpulan (F0801 - F0807)
        $positifstruktur = $sanitizeTxtfaktor8($kesimpulanData['positifstruktur'] ?? '');
        $negatifstruktur = $sanitizeTxtfaktor8($kesimpulanData['negatifstruktur'] ?? '');
        $positifproses = $sanitizeTxtfaktor8($kesimpulanData['positifproses'] ?? '');
        $negatifproses = $sanitizeTxtfaktor8($kesimpulanData['negatifproses'] ?? '');
        $positifhasil = $sanitizeTxtfaktor8($kesimpulanData['positifhasil'] ?? '');
        $negatifhasil = $sanitizeTxtfaktor8($kesimpulanData['negatifhasil'] ?? '');

        // Hitung rata-rata atau ambil dari data
        $rataRata = (new M_nilaifaktor8())->hitungRataRata(1, $kodebpr, $periodeId);
        $nfaktor = round($rataRata, 2);

        $output .= "D01|F0801|{$positifstruktur}\r\n";
        $output .= "D01|F0802|{$negatifstruktur}\r\n";
        $output .= "D01|F0803|{$positifproses}\r\n";
        $output .= "D01|F0804|{$negatifproses}\r\n";
        $output .= "D01|F0805|{$positifhasil}\r\n";
        $output .= "D01|F0806|{$negatifhasil}\r\n";
        $output .= "D01|F0807|{$nfaktor}\r\n";

        // Filename
        $filename = "TKSBPRKS-1008-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

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
