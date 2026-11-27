<?php

namespace App\Controllers;

use App\Libraries\GcgFaktorController;
use App\Models\M_nilaifaktor11;

/**
 * Self Assessment Tata Kelola — Faktor 11: Integritas Pelaporan dan Sistem Teknologi Informasi (tabel nilaifaktor11, parameter 1–12, ringkasan 13).
 * Logika halaman di App\Libraries\GcgFaktorController; ekspor TXT dipakai Showsemesteran.
 */
class Faktor11 extends GcgFaktorController
{
    protected const NO = 11;
    protected const TITLE = 'Integritas Pelaporan dan Sistem Teknologi Informasi';
    protected const ROUTE = 'faktor11';
    protected const T_PARAM = 'faktor11';
    protected const T_NILAI = 'nilaifaktor11';
    protected const C_ID = 'faktor11id';
    protected const C_N = 'nfaktor11';
    protected const T_KOM = 'faktor11_comments';
    protected const T_READ = 'comment_reads11';
    protected const READ_HAS_FID = false;
    protected const SUMMARY_ID = 13;

    protected function penjelasan(int $nilai): string
    {
        return (new M_nilaifaktor11())->getPenjelasanNilai($nilai);
    }

    /** Ekspor TXT (dipakai Showsemesteran). Format keluaran tetap. */
    public function exporttxtfaktor11()
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
        $data_faktor = (new M_nilaifaktor11())
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
        $sanitizeTxtfaktor11 = static function ($text) {
            $text = str_replace(["\r", "\n"], ' ', $text);

            $text = str_replace(
                [
                    "\u{201C}",
                    "\u{201D}",
                    "\u{2018}",
                    "\u{2019}",
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
            1 => 'S1101',
            2 => 'S1102',
            3 => 'S1103',
            4 => 'P1101',
            5 => 'P1102',
            6 => 'P1103',
            7 => 'P1104',
            8 => 'P1105',
            9 => 'P1106',
            10 => 'H1101',
            11 => 'H1102',
            12 => 'H1103'
        ];

        // Index data by faktor11id
        $indexedData = [];
        foreach ($data_faktor as $row) {
            if (isset($row['faktor11id'])) {
                $indexedData[$row['faktor11id']] = $row;
            }
        }

        $output = '';

        // HEADER
        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|1011|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";

        // Baris kosong S1100
        $output .= "D01|S1100|\r\n";

        // Struktur (S1101 - S1103)
        for ($faktor11Id = 1; $faktor11Id <= 3; $faktor11Id++) {
            $kode = $kodeMap[$faktor11Id];
            if (isset($indexedData[$faktor11Id])) {
                $row = $indexedData[$faktor11Id];
                $keterangan = $sanitizeTxtfaktor11($row['keterangan'] ?? '-');
            } else {
                $keterangan = '';
            }
            $output .= "D01|{$kode}|{$keterangan}\r\n";
        }

        // Baris kosong P0900
        $output .= "D01|P1100|\r\n";

        // Proses (P1101 - P1106)
        for ($faktor11Id = 4; $faktor11Id <= 9; $faktor11Id++) {
            $kode = $kodeMap[$faktor11Id];
            if (isset($indexedData[$faktor11Id])) {
                $row = $indexedData[$faktor11Id];
                $keterangan = $sanitizeTxtfaktor11($row['keterangan'] ?? '-');
            } else {
                $keterangan = '';
            }
            $output .= "D01|{$kode}|{$keterangan}\r\n";
        }

        // Baris kosong H1100
        $output .= "D01|H1100|\r\n";

        // Hasil (H1101 - H1103)
        for ($faktor11Id = 10; $faktor11Id <= 12; $faktor11Id++) {
            $kode = $kodeMap[$faktor11Id];
            if (isset($indexedData[$faktor11Id])) {
                $row = $indexedData[$faktor11Id];
                $keterangan = $sanitizeTxtfaktor11($row['keterangan'] ?? '-');
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
        $positifstruktur = $sanitizeTxtfaktor11($kesimpulanData['positifstruktur'] ?? '');
        $negatifstruktur = $sanitizeTxtfaktor11($kesimpulanData['negatifstruktur'] ?? '');
        $positifproses = $sanitizeTxtfaktor11($kesimpulanData['positifproses'] ?? '');
        $negatifproses = $sanitizeTxtfaktor11($kesimpulanData['negatifproses'] ?? '');
        $positifhasil = $sanitizeTxtfaktor11($kesimpulanData['positifhasil'] ?? '');
        $negatifhasil = $sanitizeTxtfaktor11($kesimpulanData['negatifhasil'] ?? '');

        // Hitung rata-rata atau ambil dari data
        $rataRata = (new M_nilaifaktor11())->hitungRataRata(1, $kodebpr, $periodeId);
        $nfaktor = round($rataRata, 2);

        $output .= "D01|F1101|{$positifstruktur}\r\n";
        $output .= "D01|F1102|{$negatifstruktur}\r\n";
        $output .= "D01|F1103|{$positifproses}\r\n";
        $output .= "D01|F1104|{$negatifproses}\r\n";
        $output .= "D01|F1105|{$positifhasil}\r\n";
        $output .= "D01|F1106|{$negatifhasil}\r\n";
        $output .= "D01|F1107|{$nfaktor}\r\n";

        // Filename
        $filename = "TKSBPRKS-1011-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

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
