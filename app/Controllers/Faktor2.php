<?php

namespace App\Controllers;

use App\Libraries\GcgFaktorController;
use App\Models\M_nilaifaktor2;

/**
 * Self Assessment Tata Kelola — Faktor 2: Pelaksanaan Tugas, Tanggung Jawab, dan Wewenang Direksi (tabel nilaifaktor2, parameter 1–28, ringkasan 29).
 * Logika halaman di App\Libraries\GcgFaktorController; ekspor TXT dipakai Showsemesteran.
 */
class Faktor2 extends GcgFaktorController
{
    protected const NO = 2;
    protected const TITLE = 'Pelaksanaan Tugas, Tanggung Jawab, dan Wewenang Direksi';
    protected const ROUTE = 'faktor2';
    protected const T_PARAM = 'faktor2';
    protected const T_NILAI = 'nilaifaktor2';
    protected const C_ID = 'faktor2id';
    protected const C_N = 'nfaktor2';
    protected const T_KOM = 'faktor2_comments';
    protected const T_READ = 'comment_reads2';
    protected const READ_HAS_FID = false;
    protected const SUMMARY_ID = 29;

    protected function penjelasan(int $nilai): string
    {
        return (new M_nilaifaktor2())->getPenjelasanNilai($nilai);
    }

    /** Ekspor TXT (dipakai Showsemesteran). Format keluaran tetap. */
    public function exporttxtfaktor2()
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
        $data_faktor = (new M_nilaifaktor2())
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
        $sanitizeTxtfaktor2 = static function ($text) {
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
            1 => 'S0201',
            2 => 'S0202',
            3 => 'S0203',
            4 => 'S0204',
            5 => 'S0205',
            6 => 'S0206',
            7 => 'S0207',
            8 => 'S0208',
            9 => 'S0209',
            10 => 'P0201',
            11 => 'P0202',
            12 => 'P0203',
            13 => 'P0204',
            14 => 'P0205',
            15 => 'P0206',
            16 => 'P0207',
            17 => 'P0208',
            18 => 'P0209',
            19 => 'P0210',
            20 => 'P0211',
            21 => 'P0212',
            22 => 'P0213',
            23 => 'H0201',
            24 => 'H0202',
            25 => 'H0203',
            26 => 'H0204',
            27 => 'H0205',
            28 => 'H0206'
        ];

        // Index data by faktor2id
        $indexedData = [];
        foreach ($data_faktor as $row) {
            if (isset($row['faktor2id'])) {
                $indexedData[$row['faktor2id']] = $row;
            }
        }

        $output = '';

        // HEADER
        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|1002|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";

        // Baris kosong S0200
        $output .= "D01|S0200|\r\n";

        // Struktur (S0201 - S0209)
        for ($faktor2Id = 1; $faktor2Id <= 9; $faktor2Id++) {
            $kode = $kodeMap[$faktor2Id];
            if (isset($indexedData[$faktor2Id])) {
                $row = $indexedData[$faktor2Id];
                $keterangan = $sanitizeTxtfaktor2($row['keterangan'] ?? '-');
            } else {
                $keterangan = '';
            }
            $output .= "D01|{$kode}|{$keterangan}\r\n";
        }

        // Baris kosong P0200
        $output .= "D01|P0200|\r\n";

        // Proses (P0201 - P0213)
        for ($faktor2Id = 10; $faktor2Id <= 22; $faktor2Id++) {
            $kode = $kodeMap[$faktor2Id];
            if (isset($indexedData[$faktor2Id])) {
                $row = $indexedData[$faktor2Id];
                $keterangan = $sanitizeTxtfaktor2($row['keterangan'] ?? '-');
            } else {
                $keterangan = '';
            }
            $output .= "D01|{$kode}|{$keterangan}\r\n";
        }

        // Baris kosong H0200
        $output .= "D01|H0200|\r\n";

        // Hasil (H0201 - H0206)
        for ($faktor2Id = 23; $faktor2Id <= 28; $faktor2Id++) {
            $kode = $kodeMap[$faktor2Id];
            if (isset($indexedData[$faktor2Id])) {
                $row = $indexedData[$faktor2Id];
                $keterangan = $sanitizeTxtfaktor2($row['keterangan'] ?? '-');
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
        $positifstruktur = $sanitizeTxtfaktor2($kesimpulanData['positifstruktur'] ?? '');
        $negatifstruktur = $sanitizeTxtfaktor2($kesimpulanData['negatifstruktur'] ?? '');
        $positifproses = $sanitizeTxtfaktor2($kesimpulanData['positifproses'] ?? '');
        $negatifproses = $sanitizeTxtfaktor2($kesimpulanData['negatifproses'] ?? '');
        $positifhasil = $sanitizeTxtfaktor2($kesimpulanData['positifhasil'] ?? '');
        $negatifhasil = $sanitizeTxtfaktor2($kesimpulanData['negatifhasil'] ?? '');

        // Hitung rata-rata atau ambil dari data
        $rataRata = (new M_nilaifaktor2())->hitungRataRata(1, $kodebpr, $periodeId);
        $nfaktor = round($rataRata, 2);

        $output .= "D01|F0201|{$positifstruktur}\r\n";
        $output .= "D01|F0202|{$negatifstruktur}\r\n";
        $output .= "D01|F0203|{$positifproses}\r\n";
        $output .= "D01|F0204|{$negatifproses}\r\n";
        $output .= "D01|F0205|{$positifhasil}\r\n";
        $output .= "D01|F0206|{$negatifhasil}\r\n";
        $output .= "D01|F0207|{$nfaktor}\r\n";

        // Filename
        $filename = "TKSBPRKS-1002-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

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
