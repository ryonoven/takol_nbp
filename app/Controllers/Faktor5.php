<?php

namespace App\Controllers;

use App\Libraries\GcgFaktorController;
use App\Models\M_nilaifaktor5;

/**
 * Self Assessment Tata Kelola — Faktor 5: Penanganan Benturan Kepentingan (tabel nilaifaktor5, parameter 1–5, ringkasan 6).
 * Logika halaman di App\Libraries\GcgFaktorController; ekspor TXT dipakai Showsemesteran.
 */
class Faktor5 extends GcgFaktorController
{
    protected const NO = 5;
    protected const TITLE = 'Penanganan Benturan Kepentingan';
    protected const ROUTE = 'faktor5';
    protected const T_PARAM = 'faktor5';
    protected const T_NILAI = 'nilaifaktor5';
    protected const C_ID = 'faktor5id';
    protected const C_N = 'nfaktor5';
    protected const T_KOM = 'faktor5_comments';
    protected const T_READ = 'comment_reads5';
    protected const READ_HAS_FID = false;
    protected const SUMMARY_ID = 6;

    protected function penjelasan(int $nilai): string
    {
        return (new M_nilaifaktor5())->getPenjelasanNilai($nilai);
    }

    /** Ekspor TXT (dipakai Showsemesteran). Format keluaran tetap. */
    public function exporttxtfaktor5()
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
        $data_faktor = (new M_nilaifaktor5())
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

        $sanitizeTxtfaktor5 = static function ($text) {
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

            $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
            return trim(preg_replace('/\s+/', ' ', $text));
        };

        $kodeMap = [
            1 => 'S0501',
            2 => 'P0501',
            3 => 'H0501',
            4 => 'H0502',
            5 => 'H0503'
        ];

        // Index data by faktor5id
        $indexedData = [];
        foreach ($data_faktor as $row) {
            if (isset($row['faktor5id'])) {
                $indexedData[$row['faktor5id']] = $row;
            }
        }

        $output = '';

        // HEADER
        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|1005|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";

        // Baris kosong S0500
        $output .= "D01|S0500|\r\n";

        // Struktur (S0501)
        $faktor5Id = 1;
        $kode = $kodeMap[$faktor5Id];
        if (isset($indexedData[$faktor5Id])) {
            $row = $indexedData[$faktor5Id];
            $keterangan = $sanitizeTxtfaktor5($row['keterangan'] ?? '-');
        } else {
            $keterangan = '';
        }
        $output .= "D01|{$kode}|{$keterangan}\r\n";

        // Baris kosong P0500
        $output .= "D01|P0500|\r\n";

        // Proses (P0501 - P0513)
        $faktor5Id = 2;
        $kode = $kodeMap[$faktor5Id];
        if (isset($indexedData[$faktor5Id])) {
            $row = $indexedData[$faktor5Id];
            $keterangan = $sanitizeTxtfaktor5($row['keterangan'] ?? '-');
        } else {
            $keterangan = '';
        }
        $output .= "D01|{$kode}|{$keterangan}\r\n";

        // Baris kosong H0500
        $output .= "D01|H0500|\r\n";

        // Hasil (H0301 - H0305)
        for ($faktor5Id = 3; $faktor5Id <= 5; $faktor5Id++) {
            $kode = $kodeMap[$faktor5Id];
            if (isset($indexedData[$faktor5Id])) {
                $row = $indexedData[$faktor5Id];
                $keterangan = $sanitizeTxtfaktor5($row['keterangan'] ?? '-');
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

        // Data kesimpulan (F0501 - F0507)
        $positifstruktur = $sanitizeTxtfaktor5($kesimpulanData['positifstruktur'] ?? '');
        $negatifstruktur = $sanitizeTxtfaktor5($kesimpulanData['negatifstruktur'] ?? '');
        $positifproses = $sanitizeTxtfaktor5($kesimpulanData['positifproses'] ?? '');
        $negatifproses = $sanitizeTxtfaktor5($kesimpulanData['negatifproses'] ?? '');
        $positifhasil = $sanitizeTxtfaktor5($kesimpulanData['positifhasil'] ?? '');
        $negatifhasil = $sanitizeTxtfaktor5($kesimpulanData['negatifhasil'] ?? '');

        // Hitung rata-rata atau ambil dari data
        $rataRata = (new M_nilaifaktor5())->hitungRataRata(1, $kodebpr, $periodeId);
        $nfaktor = round($rataRata, 2);

        $output .= "D01|F0501|{$positifstruktur}\r\n";
        $output .= "D01|F0502|{$negatifstruktur}\r\n";
        $output .= "D01|F0503|{$positifproses}\r\n";
        $output .= "D01|F0504|{$negatifproses}\r\n";
        $output .= "D01|F0505|{$positifhasil}\r\n";
        $output .= "D01|F0506|{$negatifhasil}\r\n";
        $output .= "D01|F0507|{$nfaktor}\r\n";

        // Filename
        $filename = "TKSBPRKS-1005-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

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
