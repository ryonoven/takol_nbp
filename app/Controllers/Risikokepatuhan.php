<?php

namespace App\Controllers;

use App\Libraries\ProfilRisikoController;
use App\Models\M_risikokepatuhan;

/**
 * Penilaian Risiko Kepatuhan Inheren (tabel risikokepatuhan, faktor 73–82).
 * Logika halaman di App\Libraries\ProfilRisikoController; ekspor TXT dipakai Showprofilresiko & Showsemesteran.
 */
class Risikokepatuhan extends ProfilRisikoController
{
    protected const TABLE = 'risikokepatuhan';
    protected const COL_NILAI = 'penilaiankredit';
    protected const SUBKATEGORI = 'KEPATUHANINHEREN';
    protected const ROUTE = 'Risikokepatuhan';
    protected const TITLE = 'Risiko Kepatuhan Inheren';
    protected const RISK = 'Risiko Kepatuhan';
    protected const ICON = 'fa-balance-scale';
    protected const TOTAL_ID = 81;
    protected const PREV_ID = 82;
    protected const OPTIONAL = [80];
    protected const NAV = ['Risikokepatuhan' => 'Inheren', 'Kepatuhankpmr' => 'KPMR'];

    private const KODE_MAP = [73 => '3210', 74 => '3211', 75 => '3212', 76 => '3220', 77 => '3221', 78 => '3222',
        79 => '3223', 80 => '3299', 81 => '3292'];

    protected $nilaiModel;

    public function __construct()
    {
        parent::__construct();
        $this->nilaiModel = new M_risikokepatuhan();
    }

    protected function structure(): array
    {
        return [
            ['id' => 73, 'title' => 'Pelanggaran terhadap ketentuan peraturan perundang-undangan dan ketentuan lain', 'children' => [
                ['id' => 74, 'title' => 'Jenis, signifikansi, dan frekuensi pelanggaran yang dilakukan'],
                ['id' => 75, 'title' => 'Signifikansi tindak lanjut atas temuan pelanggaran'],
            ]],
            ['id' => 76, 'title' => 'Faktor kelemahan aspek hukum', 'children' => [
                ['id' => 77, 'title' => 'Kelemahan dalam perikatan'],
                ['id' => 78, 'title' => 'Litigasi terkait nominal gugatan atau estimasi kerugian yang dialami BPR akibat gugatan'],
                ['id' => 79, 'title' => 'Litigasi terkait kerugian yang dialami karena putusan pengadilan berkekuatan hukum tetap'],
            ]],
            ['id' => 80, 'title' => 'Lainnya'],
        ];
    }

    protected function penilaianConfig(): array
    {
        // catatan lama generik ("Peniliaian Parameter …") → tidak ditampilkan
        return array_map(static fn($c) => array_diff_key($c, ['catatan' => 1]), [
            73 => ['descriptions' => [
                1 => 'Parameter Pilar pelanggaran terhadap ketentuan peraturan perundang-undangan dan ketentuan lain berada pada tingkat risiko yang Sangat Rendah',
                2 => 'Parameter Pilar pelanggaran terhadap ketentuan peraturan perundang-undangan dan ketentuan lain berada pada tingkat risiko yang Rendah',
                3 => 'Parameter Pilar pelanggaran terhadap ketentuan peraturan perundang-undangan dan ketentuan lain berada pada tingkat risiko yang Sedang',
                4 => 'Parameter Pilar pelanggaran terhadap ketentuan peraturan perundang-undangan dan ketentuan lain berada pada tingkat risiko yang Tinggi',
                5 => 'Parameter Pilar pelanggaran terhadap ketentuan peraturan perundang-undangan dan ketentuan lain berada pada tingkat risiko yang Sangat Tinggi',
            ], 'catatan' => 'Peniliaian Parameter Risiko Kepatuhan Inheren'],
            74 => ['descriptions' => [
                1 => 'Tidak terdapat pelanggaran terhadap ketentuan peraturan perundang-undangan dan/atau ketentuan lain selama periode penilaian.',
                2 => '• Terdapat pelanggaran terhadap ketentuan peraturan perundang-undangan dengan jenis sanksi ringan; dan<br>• Frekuensi pelanggaran rendah.',
                3 => '• Terdapat pelanggaran terhadap ketentuan peraturan perundang-undangan dengan jenis sanksi ringan; dan<br>• Frekuensi pelanggaran sedang.',
                4 => '• Terdapat pelanggaran terhadap ketentuan peraturan perundang-undangan dengan jenis sanksi ringan dengan frekuensi pelanggaran tinggi; dan/atau<br>• Terdapat dugaan pelanggaran terhadap Undang-Undang Perbankan antara lain pasal 49 yang dilakukan oleh pejabat atau pegawai BPR.',
                5 => '• Terdapat pelanggaran dengan sanksi berat dan jenis pelanggaran signifikan; dan/atau<br>• Terdapat dugaan pelanggaran terhadap Undang-Undang Perbankan antara lain pasal 49 yang dilakukan oleh anggota Direksi dan/atau Dewan Komisaris BPR.',
            ], 'catatan' => 'Peniliaian Parameter Risiko Kepatuhan Inheren'],
            75 => ['descriptions' => [
                1 => 'Tidak terdapat pelanggaran berulang, dan pelanggaran di periode sebelumnya sudah selesai ditindaklanjuti.',
                2 => 'Terdapat pelanggaran berulang pada dua periode sebelumnya dengan jenis yang sama namun terdapat penurunan frekuensi yang tinggi dibandingkan periode sebelumnya.',
                3 => 'Terdapat pelanggaran berulang pada dua periode sebelumnya dengan jenis yang sama namun terdapat penurunan frekuensi yang sedang dibandingkan periode sebelumnya.',
                4 => 'Terdapat pelanggaran berulang pada dua periode sebelumnya dengan jenis yang sama namun terdapat penurunan frekuensi yang rendah dibandingkan periode sebelumnya.',
                5 => 'Terdapat pelanggaran berulang pada dua periode sebelumnya dengan jenis yang sama dengan frekuensi lebih banyak dari periode sebelumnya; dan/atau Terdapat pelanggaran berulang yang merupakan pelanggaran yang bersifat signifikan antara lain terhadap ketentuan BMPK, jumlah minimal pengurus, dan permodalan.',
            ], 'catatan' => 'Peniliaian Parameter Risiko Kepatuhan Inheren'],
            76 => ['descriptions' => [
                1 => 'Parameter Faktor kelemahan aspek hukum berada pada tingkat risiko yang Sangat Rendah',
                2 => 'Parameter Faktor kelemahan aspek hukum berada pada tingkat risiko yang Rendah',
                3 => 'Parameter Faktor kelemahan aspek hukum berada pada tingkat risiko yang Sedang',
                4 => 'Parameter Faktor kelemahan aspek hukum berada pada tingkat risiko yang Tinggi',
                5 => 'Parameter Faktor kelemahan aspek hukum berada pada tingkat risiko yang Sangat Tinggi',
            ], 'catatan' => 'Peniliaian Parameter Risiko Kepatuhan Inheren'],
            77 => ['descriptions' => [
                1 => '• Terdapat perjanjian kredit atau kerja sama lain yang memenuhi syarat sah perjanjian; dan<br>• Tidak terdapat kelemahan dalam klausula perjanjian.',
                2 => '• Terdapat perjanjian kredit atau kerja sama lain yang memenuhi syarat sah perjanjian; dan/atau<br>• Terdapat kelemahan dalam klausula perjanjian dan menyebabkan tidak dapat dilaksanakannya klausula dalam perjanjian namun tidak berpotensi menimbulkan gugatan hukum dan kerugian yang material.',
                3 => '• Terdapat perjanjian kredit atau kerja sama lain yang memenuhi syarat sah perjanjian; dan/atau<br>• Terdapat kelemahan dalam klausula perjanjian yang berpotensi menimbulkan gugatan hukum dan/atau kerugian yang material.',
                4 => '• Terdapat perjanjian kredit atau kerja sama lain yang tidak memenuhi syarat sah perjanjian; dan/atau<br>• Terdapat kelemahan dalam klausula perjanjian yang berpotensi menimbulkan gugatan hukum dan/atau kerugian yang sangat material.',
                5 => 'Terdapat pemberian kredit atau kerja sama lain yang tidak didukung dengan perjanjian tertulis.',
            ], 'catatan' => 'Peniliaian Parameter Risiko Kepatuhan Inheren'],
            78 => ['descriptions' => [
                1 => 'Tidak terdapat gugatan atau tidak terdapat estimasi kerugian yang dialami BPR akibat gugatan.',
                2 => 'Terdapat nominal gugatan atau estimasi kerugian akibat gugatan dengan nilai tidak signifikan dibanding modal BPR.',
                3 => 'Terdapat nominal gugatan atau estimasi kerugian akibat gugatan dengan nilai kurang signifikan dibanding modal BPR.',
                4 => 'Terdapat nominal gugatan atau estimasi kerugian akibat gugatan dengan nilai cukup signifikan dibanding modal BPR.',
                5 => 'Terdapat nominal gugatan atau estimasi kerugian akibat gugatan yang menyebabkan permodalan menurun di bawah ketentuan KPMM',
            ], 'catatan' => 'Peniliaian Parameter Risiko Kepatuhan Inheren'],
            79 => ['descriptions' => [
                1 => 'Tidak terdapat kerugian yang dialami BPR karena putusan pengadilan berkekuatan hukum tetap selama periode penilaian.',
                2 => 'Terdapat kerugian karena putusan pengadilan berkekuatan hukum tetap, dengan nilai tidak signifikan dibanding modal BPR selama periode penilaian.',
                3 => 'Terdapat kerugian karena putusan pengadilan berkekuatan hukum tetap, dengan nilai kurang signifikan dibanding modal BPR selama periode penilaian.',
                4 => 'Terdapat kerugian karena putusan pengadilan berkekuatan hukum tetap, dengan nilai cukup signifikan dibanding modal BPR selama periode penilaian.',
                5 => 'Terdapat kerugian karena putusan pengadilan berkekuatan hukum tetap selama periode penilaian yang menyebabkan permodalan menurun di bawah ketentuan KPMM.',
            ], 'catatan' => 'Peniliaian Parameter Risiko Kepatuhan Inheren'],
            80 => ['descriptions' => [
                1 => 'Peringkat Risiko Kepatuhan Inheren Lainnya berada pada tingkat yang Sangat Rendah',
                2 => 'Peringkat Risiko Kepatuhan Inheren Lainnya berada pada tingkat yang Rendah',
                3 => 'Peringkat Risiko Kepatuhan Inheren Lainnya berada pada tingkat yang Sedang',
                4 => 'Peringkat Risiko Kepatuhan Inheren Lainnya berada pada tingkat yang Tinggi',
                5 => 'Peringkat Risiko Kepatuhan Inheren Lainnya berada pada tingkat yang Sangat Tinggi',
            ], 'catatan' => 'Peniliaian Parameter Risiko Kepatuhan Inheren'],
        ]);
    }

    private function sanitizeTxt(string $text): string
    {
        $text = str_replace(["\r", "\n"], ' ', $text);
        $text = str_replace(
            ['“', '”', '‘', '’', '%', '|'],
            ['"', '"', "'", "'", ' persen ', ' '],
            $text
        );
        $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    public function exporttxtrisikokepatuhan()
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

        [
            'modalinti' => $modalinti,
            'nomorsurat' => $nomorsurat,
            'jeniskoreksi' => $jeniskoreksi,
            'jenispelaporan' => $jenispelaporan,
            'kategori' => $kategori,
        ] = $this->periode;
        $tahun = $this->periode['tahun'] ?? date('Y');
        $semester = $this->periode['semester'] ?? '';

        $exportDate = (strtolower($semester) === 'ganjil' || $semester === '1')
            ? "{$tahun}-06-30"
            : "{$tahun}-12-31";
        $titleDate = str_replace('-', '', $exportDate);

        $indexedData = [];
        foreach ($this->nilaiModel->getDataByKodebprAndPeriode($this->userKodebpr, $this->periodeId) as $row) {
            if (isset($row['faktor1id'])) {
                $indexedData[$row['faktor1id']] = $row;
            }
        }

        $infobpr = $this->infobprModel->getDataByKodebpr($this->userKodebpr);
        $sandibpr = $infobpr[0]['sandibpr'] ?? '';
        $kodejenis = $infobpr[0]['kodejenis'] ?? '';

        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0301|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";

        foreach (self::KODE_MAP as $faktorId => $kode) {
            if (isset($indexedData[$faktorId])) {
                $row = $indexedData[$faktorId];
                $penilaiankredit = $this->sanitizeTxt((string) ($row['penilaiankredit'] ?? ''));
                $keterangan = $this->sanitizeTxt((string) ($row['keterangan'] ?? '-'));
            } else {
                $penilaiankredit = '';
                $keterangan = '';
            }
            $output .= "D01|{$kode}|{$penilaiankredit}|{$keterangan}\r\n";
        }

        $filename = "TKSBPRKS-0301-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }
}
