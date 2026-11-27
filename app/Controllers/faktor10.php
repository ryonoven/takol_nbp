<?php

namespace App\Controllers;

use App\Libraries\GcgFaktorController;
use App\Models\M_nilaifaktor10;

/**
 * Self Assessment Tata Kelola — Faktor 10: Batas Maksimum Pemberian Kredit (tabel nilaifaktor10, parameter 1–5, ringkasan 6).
 * Logika halaman di App\Libraries\GcgFaktorController; ekspor TXT dipakai Showsemesteran.
 */
class Faktor10 extends GcgFaktorController
{
    protected const NO = 10;
    protected const TITLE = 'Batas Maksimum Pemberian Kredit';
    protected const ROUTE = 'faktor10';
    protected const T_PARAM = 'faktor10';
    protected const T_NILAI = 'nilaifaktor10';
    protected const C_ID = 'faktor10id';
    protected const C_N = 'nfaktor10';
    protected const T_KOM = 'faktor10_comments';
    protected const T_READ = 'comment_reads10';
    protected const READ_HAS_FID = false;
    protected const SUMMARY_ID = 6;
    /** Tabel faktor10 mencatat kode H1001 dua kali; parameter 5 = H1002 (sesuai ekspor TXT). */
    protected const CODES = [5 => 'H1002'];

    protected function penjelasan(int $nilai): string
    {
        return (new M_nilaifaktor10())->getPenjelasanNilai($nilai);
    }

    /** Ekspor TXT (dipakai Showsemesteran). Format keluaran tetap. */
    public function exporttxtfaktor10()
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
        $data_faktor = (new M_nilaifaktor10())
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
        $sanitizeTxtfaktor10 = static function ($text) {
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
            1 => 'S1001',
            2 => 'P1001',
            3 => 'P1002',
            4 => 'H1001',
            5 => 'H1002'
        ];

        // Index data by faktor10id
        $indexedData = [];
        foreach ($data_faktor as $row) {
            if (isset($row['faktor10id'])) {
                $indexedData[$row['faktor10id']] = $row;
            }
        }

        $output = '';

        // HEADER
        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|1010|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";

        // Baris kosong S1000
        $output .= "D01|S1000|\r\n";

        // Struktur (S0801)
        $faktor10Id = 1;
        $kode = $kodeMap[$faktor10Id];
        if (isset($indexedData[$faktor10Id])) {
            $row = $indexedData[$faktor10Id];
            $keterangan = $sanitizeTxtfaktor10($row['keterangan'] ?? '-');
        } else {
            $keterangan = '';
        }
        $output .= "D01|{$kode}|{$keterangan}\r\n";

        // Baris kosong P1000
        $output .= "D01|P1000|\r\n";

        // Proses (P1001 - P1002)
        for ($faktor10Id = 2; $faktor10Id <= 3; $faktor10Id++) {
            $kode = $kodeMap[$faktor10Id];
            if (isset($indexedData[$faktor10Id])) {
                $row = $indexedData[$faktor10Id];
                $keterangan = $sanitizeTxtfaktor10($row['keterangan'] ?? '-');
            } else {
                $keterangan = '';
            }
            $output .= "D01|{$kode}|{$keterangan}\r\n";
        }

        // Baris kosong H1000
        $output .= "D01|H1000|\r\n";

        // Hasil (H1001 - H1002)
        for ($faktor10Id = 4; $faktor10Id <= 5; $faktor10Id++) {
            $kode = $kodeMap[$faktor10Id];
            if (isset($indexedData[$faktor10Id])) {
                $row = $indexedData[$faktor10Id];
                $keterangan = $sanitizeTxtfaktor10($row['keterangan'] ?? '-');
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

        // Data kesimpulan (F1001 - F1007)
        $positifstruktur = $sanitizeTxtfaktor10($kesimpulanData['positifstruktur'] ?? '');
        $negatifstruktur = $sanitizeTxtfaktor10($kesimpulanData['negatifstruktur'] ?? '');
        $positifproses = $sanitizeTxtfaktor10($kesimpulanData['positifproses'] ?? '');
        $negatifproses = $sanitizeTxtfaktor10($kesimpulanData['negatifproses'] ?? '');
        $positifhasil = $sanitizeTxtfaktor10($kesimpulanData['positifhasil'] ?? '');
        $negatifhasil = $sanitizeTxtfaktor10($kesimpulanData['negatifhasil'] ?? '');

        // Hitung rata-rata atau ambil dari data
        $rataRata = (new M_nilaifaktor10())->hitungRataRata(1, $kodebpr, $periodeId);
        $nfaktor = round($rataRata, 2);

        $output .= "D01|F1001|{$positifstruktur}\r\n";
        $output .= "D01|F1002|{$negatifstruktur}\r\n";
        $output .= "D01|F1003|{$positifproses}\r\n";
        $output .= "D01|F1004|{$negatifproses}\r\n";
        $output .= "D01|F1005|{$positifhasil}\r\n";
        $output .= "D01|F1006|{$negatifhasil}\r\n";
        $output .= "D01|F1007|{$nfaktor}\r\n";

        // Filename
        $filename = "TKSBPRKS-1010-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

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
