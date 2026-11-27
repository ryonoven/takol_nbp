<?php

namespace App\Controllers;

use App\Libraries\GcgFaktorController;
use App\Models\M_nilaifaktor4;

/**
 * Self Assessment Tata Kelola — Faktor 4: Kelengkapan dan Pelaksanaan Tugas Komite (tabel nilaifaktor4, parameter 1–11, ringkasan 12).
 * Logika halaman di App\Libraries\GcgFaktorController; ekspor TXT dipakai Showsemesteran.
 */
class Faktor4 extends GcgFaktorController
{
    protected const NO = 4;
    protected const TITLE = 'Kelengkapan dan Pelaksanaan Tugas Komite';
    protected const ROUTE = 'faktor4';
    protected const T_PARAM = 'faktor4';
    protected const T_NILAI = 'nilaifaktor4';
    protected const C_ID = 'faktor4id';
    protected const C_N = 'nfaktor4';
    protected const T_KOM = 'faktor4_comments';
    protected const T_READ = 'comment_reads4';
    protected const READ_HAS_FID = false;
    protected const SUMMARY_ID = 12;
    /** BPR tanpa komite: seluruh parameter kosong, faktor tetap dapat disetujui (dikecualikan dari nilai komposit). */
    protected const OPTIONAL = true;

    protected function penjelasan(int $nilai): string
    {
        return (new M_nilaifaktor4())->getPenjelasanNilai($nilai);
    }

    /** Ekspor TXT (dipakai Showsemesteran). Format keluaran tetap. */
    public function exporttxtfaktor4()
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
        $data_faktor = (new M_nilaifaktor4())
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

        // Hitung rata-rata untuk cek apakah ada data
        $rataRata = (new M_nilaifaktor4())->hitungRataRata(1, $kodebpr);
        $nfaktor = round($rataRata, 2);

        $output = '';

        // HEADER - Selalu ada
        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|1004|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";

        // Cek apakah ada data atau nfaktor = 0
        if (empty($data_faktor) || $nfaktor == 0) {
            // Hanya generate header saja
            // Filename
            $filename = "TKSBPRKS-1004-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

            // Response
            $response = service('response');
            $response->setHeader('Content-Type', 'text/plain');
            $response->setHeader(
                'Content-Disposition',
                'attachment; filename="' . $filename . '"'
            );

            return $response->setBody($output);
        }

        // Sanitizer
        $sanitizeTxtfaktor4 = static function ($text) {
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
            1 => 'S0401',
            2 => 'S0402',
            3 => 'P0403',
            4 => 'P0404',
            5 => 'P0405',
            6 => 'P0406',
            7 => 'P0407',
            8 => 'P0408',
            9 => 'H0401',
            10 => 'H0402',
            11 => 'H0403'
        ];

        // Index data by faktor4id
        $indexedData = [];
        foreach ($data_faktor as $row) {
            if (isset($row['faktor4id'])) {
                $indexedData[$row['faktor4id']] = $row;
            }
        }

        // Baris kosong S0400
        $output .= "D01|S0400|\r\n";

        // Struktur (S0401 - S0402)
        for ($faktor4Id = 1; $faktor4Id <= 2; $faktor4Id++) {
            $kode = $kodeMap[$faktor4Id];
            if (isset($indexedData[$faktor4Id])) {
                $row = $indexedData[$faktor4Id];
                $keterangan = $sanitizeTxtfaktor4($row['keterangan'] ?? '-');
            } else {
                $keterangan = '';
            }
            $output .= "D01|{$kode}|{$keterangan}\r\n";
        }

        // Baris kosong P0400
        $output .= "D01|P0400|\r\n";

        // Proses (P0403 - P0408)
        for ($faktor4Id = 3; $faktor4Id <= 8; $faktor4Id++) {
            $kode = $kodeMap[$faktor4Id];
            if (isset($indexedData[$faktor4Id])) {
                $row = $indexedData[$faktor4Id];
                $keterangan = $sanitizeTxtfaktor4($row['keterangan'] ?? '-');
            } else {
                $keterangan = '';
            }
            $output .= "D01|{$kode}|{$keterangan}\r\n";
        }

        // Baris kosong H0400
        $output .= "D01|H0400|\r\n";

        // Hasil (H0401 - H0403)
        for ($faktor4Id = 9; $faktor4Id <= 11; $faktor4Id++) {
            $kode = $kodeMap[$faktor4Id];
            if (isset($indexedData[$faktor4Id])) {
                $row = $indexedData[$faktor4Id];
                $keterangan = $sanitizeTxtfaktor4($row['keterangan'] ?? '-');
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

        // Data kesimpulan (F0401 - F0407)
        $positifstruktur = $sanitizeTxtfaktor4($kesimpulanData['positifstruktur'] ?? '');
        $negatifstruktur = $sanitizeTxtfaktor4($kesimpulanData['negatifstruktur'] ?? '');
        $positifproses = $sanitizeTxtfaktor4($kesimpulanData['positifproses'] ?? '');
        $negatifproses = $sanitizeTxtfaktor4($kesimpulanData['negatifproses'] ?? '');
        $positifhasil = $sanitizeTxtfaktor4($kesimpulanData['positifhasil'] ?? '');
        $negatifhasil = $sanitizeTxtfaktor4($kesimpulanData['negatifhasil'] ?? '');

        $output .= "D01|F0401|{$positifstruktur}\r\n";
        $output .= "D01|F0402|{$negatifstruktur}\r\n";
        $output .= "D01|F0403|{$positifproses}\r\n";
        $output .= "D01|F0404|{$negatifproses}\r\n";
        $output .= "D01|F0405|{$positifhasil}\r\n";
        $output .= "D01|F0406|{$negatifhasil}\r\n";
        $output .= "D01|F0407|{$nfaktor}\r\n";

        // Filename
        $filename = "TKSBPRKS-1004-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

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
