<?php

namespace App\Controllers;

use App\Libraries\ProfilRisikoController;
use App\Models\M_risikooperasional;

/**
 * Penilaian Risiko Operasional Inheren (tabel risikooperasional, faktor 36–49).
 * Logika halaman di App\Libraries\ProfilRisikoController; ekspor TXT dipakai Showprofilresiko & Showsemesteran.
 */
class Risikooperasional extends ProfilRisikoController
{
    protected const TABLE = 'risikooperasional';
    protected const COL_NILAI = 'penilaiankredit';
    protected const SUBKATEGORI = 'OPERASIONALINHEREN';
    protected const ROUTE = 'Risikooperasional';
    protected const TITLE = 'Risiko Operasional Inheren';
    protected const RISK = 'Risiko Operasional';
    protected const ICON = 'fa-cogs';
    protected const TOTAL_ID = 48;
    protected const PREV_ID = 49;
    protected const OPTIONAL = [47];
    protected const NAV = ['Risikooperasional' => 'Inheren', 'Risikooperasionalkpmr' => 'KPMR'];

    private const KODE_MAP = [36 => '2210', 37 => '2211', 38 => '2212', 39 => '2213', 40 => '2214', 41 => '2220', 42 => '2221',
        43 => '2222', 44 => '2230', 45 => '2240', 46 => '2250', 47 => '2299', 48 => '2292'];

    protected $nilaiModel;

    public function __construct()
    {
        parent::__construct();
        $this->nilaiModel = new M_risikooperasional();
    }

    protected function structure(): array
    {
        return [
            ['id' => 36, 'title' => 'Kompleksitas bisnis dan kelembagaan', 'children' => [
                ['id' => 37, 'title' => 'Skala usaha dan struktur organisasi'],
                ['id' => 38, 'title' => 'Jaringan kantor, rentang kendali dan lokasi kantor cabang'],
                ['id' => 39, 'title' => 'Keberagaman produk dan/atau jasa'],
                ['id' => 40, 'title' => 'Tindakan korporasi'],
            ]],
            ['id' => 41, 'title' => 'Sumber daya manusia (SDM)', 'children' => [
                ['id' => 42, 'title' => 'Kecukupan kuantitas dan kualitas SDM'],
                ['id' => 43, 'title' => 'Permasalahan operasional karena faktor manusia (human error)'],
            ]],
            ['id' => 44, 'title' => 'Penyelenggaraan teknologi informasi (TI)'],
            ['id' => 45, 'title' => 'Penyimpangan (fraud)'],
            ['id' => 46, 'title' => 'Faktor eksternal'],
            ['id' => 47, 'title' => 'Lainnya'],
        ];
    }

    protected function penilaianConfig(): array
    {
        // catatan lama generik dan threshold sisa salinan (85% / 5%, tidak sesuai deskripsi non-rasio) → tidak ditampilkan
        return array_map(static fn($c) => array_diff_key($c, ['catatan' => 1, 'threshold' => 1]), [
            36 => [
                'descriptions' => [
                    1 => 'Parameter Kompleksitas bisnis dan kelembagaan berada pada tingkat risiko yang Sangat Rendah',
                    2 => 'Parameter Kompleksitas bisnis dan kelembagaan berada pada tingkat risiko yang Rendah',
                    3 => 'Parameter Kompleksitas bisnis dan kelembagaan berada pada tingkat risiko yang Sedang',
                    4 => 'Parameter Kompleksitas bisnis dan kelembagaan berada pada tingkat risiko yang Tinggi',
                    5 => 'Parameter Kompleksitas bisnis dan kelembagaan berada pada tingkat risiko yang Sangat Tinggi',
                ],
                'catatan' => 'Penilaian parameter risiko operasional inheren',
            ],
            37 => [
                'descriptions' => [
                    1 => '• Skala usaha BPR tergolong kecil atau menengah; dan <br>• Struktur organisasi BPR terpenuhi lengkap sesuai ketentuan tata kelola BPR.',
                    2 => '• Skala usaha BPR tergolong besar; dan <br>• Struktur organisasi BPR terpenuhi lengkap sesuai ketentuan tata kelola BPR.',
                    3 => '• Skala usaha BPR tergolong kecil atau menengah; dan <br>• Terdapat ketidaklengkapan struktur organisasi BPR pada fungsi yang tidak signifikan.',
                    4 => '• Skala usaha BPR tergolong besar; dan <br>• Terdapat ketidaklengkapan struktur organisasi BPR pada fungsi yang tidak signifikan.',
                    5 => '• Skala usaha BPR tergolong kecil, menengah, atau besar; dan<br>• Terdapat ketidaklengkapan struktur organisasi BPR pada fungsi yang signifikan.',
                ],
                'catatan' => 'Penilaian parameter risiko operasional inheren',
            ],
            38 => [
                'descriptions' => [
                    1 => 'BPR tidak memiliki jaringan kantor cabang dan/atau kantor kas.',
                    2 => '• BPR memiliki jumlah jaringan kantor cabang paling banyak 25% dari maksimal yang diperkenankan untuk skala KU<br>• Memiliki kantor kas Rentang kendali kecil dan lokasi kantor cabang dapat diakses dengan mudah',
                    3 => '• BPR memiliki jumlah jaringan kantor cabang lebih dari 25% dan paling banyak 50% dari maksimal yang diperkenankan untuk skala KU<br>• Memiliki kantor kas Rentang kendali kecil namun terdapat lokasi kantor cabang yang sulit diakses',
                    4 => '• BPR memiliki jumlah jaringan kantor cabang lebih dari 50% dan paling banyak 75% dari maksimal yang diperkenankan untuk skala KU<br>• Memiliki kantor kas Rentang kendali besar dan lokasi kantor cabang dapat diakses dengan mudah',
                    5 => '• BPR memiliki jumlah jaringan kantor cabang lebih dari 75% dari maksimal yang diperkenankan untuk skala KU<br>• Memiliki kantor kas Rentang kendali besar dan terdapat lokasi kantor cabang yang sulit diakses',
                ],
                'catatan' => 'Penilaian parameter risiko operasional inheren',
            ],
            39 => [
                'descriptions' => [
                    1 => 'BPR memiliki produk/jasa yang termasuk kegiatan usaha utama',
                    2 => '• BPR memiliki produk/jasa yang termasuk kegiatan usaha utama; dan<br>• penukaran valuta asing; dan/atau<br>• layanan kerjasama pihak ketiga yang tidak memerlukan kompetensi tinggi dan tidak melibatkan teknologi',
                    3 => 'BPR memiliki produk/jasa yang termasuk kegiatan usaha utama dan melaksanakan kegiatan usaha layanan kerjasama pihak ketiga yang melibatkan teknologi milik pihak ketiga',
                    4 => 'BPR melaksanakan kegiatan usaha sebagai penyelenggara layanan berbasis teknologi misalnya sebagai issuer/penerbit kartu ATM, atau penyelenggara internet banking',
                    5 => 'BPR melakukan kegiatan usaha yang tidak sesuai dengan kelompok BPRKU atau tidak dilaporkan/memperoleh izin dari OJK atau BI',
                ],
                'catatan' => 'Penilaian parameter risiko operasional inheren',
            ],
            40 => [
                'threshold' => '85%',
                'descriptions' => [
                    1 => '• BPR tidak dalam proses penggabungan, peleburan, dan pengambilalihan;<br>• BPR tidak dalam proses pemindahan kantor pusat BPR; dan<br>• BPR tidak dalam proses penerbitan produk dan/atau pelaksanaan aktivitas baru.',
                    2 => '• BPR tidak dalam proses penggabungan, peleburan, dan pengambilalihan;<br>• Terdapat proses pemindahan kantor pusat BPR; dan/atau<br>• BPR dalam proses pengembangan produk dan/atau aktivitas baru (yang hanya memerlukan pelaporan ke OJK).',
                    3 => '• Terdapat proses pemindahan kantor pusat BPR;<br>• BPR menerbitkan produk dan/atau melaksanakan aktivitas baru (memerlukan persetujuan OJK) bekerja sama dengan pihak ketiga; dan/atau<br>• BPR melaksanakan penggabungan/peleburan/pengambilalihan pada jangka waktu sangat lama sebelum periode penilaian.',
                    4 => '• Terdapat proses pemindahan kantor pusat BPR;<br>• BPR menerbitkan produk dan/atau melaksanakan aktivitas baru yang memerlukan capital expenditure; dan/atau<br>• BPR melaksanakan penggabungan/peleburan/pengambilalihan pada jangka waktu lama sebelum periode penilaian.',
                    5 => '• Terdapat proses pemindahan kantor pusat BPR;<br>• BPR menerbitkan produk dan/atau melaksanakan aktivitas baru yang memerlukan capital expenditure; dan/atau<br>• BPR melaksanakan penggabungan/peleburan/pengambilalihan pada jangka waktu tidak lama sebelum periode penilaian.',
                ],
                'catatan' => 'Penilaian parameter risiko operasional inheren',
            ],
            41 => [
                'descriptions' => [
                    1 => 'Parameter Sumber daya manusia (SDM) berada pada tingkat risiko yang Sangat Rendah',
                    2 => 'Parameter Sumber daya manusia (SDM) berada pada tingkat risiko yang Rendah',
                    3 => 'Parameter Sumber daya manusia (SDM) berada pada tingkat risiko yang Sedang',
                    4 => 'Parameter Sumber daya manusia (SDM) berada pada tingkat risiko yang Tinggi',
                    5 => 'Parameter Sumber daya manusia (SDM) berada pada tingkat risiko yang Sangat Tinggi',
                ],
                'catatan' => 'Penilaian parameter risiko operasional inheren',
            ],
            42 => [
                'descriptions' => [
                    1 => 'Kuantitas dan kualitas SDM BPR sangat memadai.',
                    2 => 'Kuantitas dan kualitas SDM BPR memadai.',
                    3 => 'Kuantitas dan kualitas SDM BPR cukup memadai.',
                    4 => 'Kuantitas dan kualitas SDM BPR kurang memadai.',
                    5 => 'Kuantitas dan kualitas SDM BPR tidak memadai.',
                ],
                'catatan' => 'Penilaian parameter risiko operasional inheren',
            ],
            43 => [
                'threshold' => '5%',
                'descriptions' => [
                    1 => 'Tidak terjadi human error pada BPR.',
                    2 => '• Terjadi human error pada BPR; namun<br>• Tidak berdampak finansial bagi BPR.',
                    3 => '• Terjadi human error pada BPR; dan<br>• mengurangi keuntungan namun tidak menyebabkan BPR membukukan laba negatif.',
                    4 => '• Terjadi human error pada BPR; dan<br>• BPR membukukan laba negatif yang menyebabkan rasio permodalan menurun namun masih sesuai ketentuan KPMM.',
                    5 => '• Terjadi human error pada BPR; dan<br>• BPR membukukan laba negatif yang menyebabkan rasio permodalan menurun di bawah ketentuan KPMM.',
                ],
                'catatan' => 'Penilaian parameter risiko operasional inheren',
            ],
            44 => [
                'descriptions' => [
                    1 => '• TI BPR sesuai dengan ketentuan mengenai SPTI; dan<br>• BPR tidak sedang dalam proses melakukan perubahan mendasar penyelenggaraan TI.',
                    2 => '• TI BPR sebagian besar sesuai dengan ketentuan mengenai SPTI; dan<br>• BPR tidak sedang dalam proses melakukan perubahan mendasar penyelenggaraan TI.',
                    3 => '• TI BPR sebagian besar sesuai dengan ketentuan mengenai SPTI; dan<br>• BPR sedang dalam proses melakukan perubahan mendasar penyelenggaraan TI.',
                    4 => '• TI BPR sebagian besar tidak sesuai dengan ketentuan mengenai SPTI; dan<br>• BPR tidak sedang dalam proses melakukan perubahan mendasar penyelenggaraan TI.',
                    5 => '• TI BPR sebagian besar tidak sesuai dengan ketentuan mengenai SPTI; dan<br>• BPR sedang dalam proses melakukan perubahan mendasar penyelenggaraan TI.',
                ],
                'catatan' => 'Penilaian parameter risiko operasional inheren',
            ],
            45 => [
                'descriptions' => [
                    1 => 'Tidak terdapat indikasi penyimpangan (fraud) pada BPR',
                    2 => '• Terdapat indikasi penyimpangan (fraud) pada BPR dengan frekuensi yang rendah; dan<br>• belum/tidak berdampak finansial',
                    3 => '• Terdapat indikasi penyimpangan (fraud) pada BPR dengan frekuensi tinggi; dan<br>• mengurangi keuntungan namun tidak menyebabkan BPR membukukan laba negatif',
                    4 => '• Terdapat indikasi penyimpangan (fraud) pada BPR yang signifikan; dan<br>• mengurangi keuntungan atau BPR membukukan laba negatif yang menyebabkan rasio permodalan menurun namun masih sesuai ketentuan KPMM',
                    5 => '• Terdapat indikasi penyimpangan (fraud) pada BPR yang sangat signifikan; dan<br>• BPR membukukan laba negatif yang menyebabkan rasio permodalan menurun di bawah ketentuan KPMM',
                ],
                'catatan' => 'Penilaian parameter risiko operasional inheren',
            ],
            46 => [
                'descriptions' => [
                    1 => 'Tidak terdapat kejadian eksternal',
                    2 => '• Terdapat kejadian eksternal; namun<br>• tidak berdampak finansial bagi BPR',
                    3 => '• Terdapat kejadian eksternal; dan<br>• mengurangi keuntungan namun tidak menyebabkan BPR membukukan laba negatif',
                    4 => '• Terdapat kejadian eksternal; dan<br>• BPR membukukan laba negatif yang menyebabkan rasio permodalan menurun namun masih sesuai ketentuan KPMM.',
                    5 => '• Terdapat kejadian eksternal; dan<br>• BPR membukukan laba negatif yang menyebabkan rasio permodalan menurun di bawah ketentuan KPMM.',
                ],
                'catatan' => 'Penilaian parameter risiko operasional inheren',
            ],
            47 => [
                'descriptions' => [
                    1 => 'Parameter Penilaian Risiko Operasional Inheren berada pada tingkat risiko yang Sangat Rendah',
                    2 => 'Parameter Penilaian Risiko Operasional Inheren berada pada tingkat risiko yang Rendah',
                    3 => 'Parameter Penilaian Risiko Operasional Inheren berada pada tingkat risiko yang Sedang',
                    4 => 'Parameter Penilaian Risiko Operasional Inheren berada pada tingkat risiko yang Tinggi',
                    5 => 'Parameter Penilaian Risiko Operasional Inheren berada pada tingkat risiko yang Sangat Tinggi',
                ],
                'catatan' => 'Penilaian parameter risiko operasional inheren',
            ],
        ]);
    }

    private function sanitizeTxt(string $text): string
    {
        $text = str_replace(["\r", "\n"], ' ', $text);
        $text = str_replace(
            ['"', '"', '‘', '’', '%', '|'],
            ['"', '"', "'", "'", ' persen ', ' '],
            $text
        );
        $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    public function exporttxtrisikooperasional()
    {
        if (!$this->auth->check()) {
            return redirect()->to(session('redirect_url') ?? '/login');
        }
        // Periode aktif bukan milik BPR user → tidak ada berkas
        if (!$this->periodeId || !$this->userKodebpr) {
            return service('response')->setContentType('text/plain')->setBody('');
        }
        $periodeDetail = $this->periode;

        [
            'modalinti' => $modalinti,
            'nomorsurat' => $nomorsurat,
            'jeniskoreksi' => $jeniskoreksi,
            'jenispelaporan' => $jenispelaporan,
            'kategori' => $kategori,
            'tahun' => $tahun,
            'semester' => $semester
        ] = $periodeDetail;

        $exportDate = (strtolower($semester) === 'ganjil' || $semester === '1')
            ? "{$tahun}-06-30"
            : "{$tahun}-12-31";
        $titleDate = str_replace('-', '', $exportDate);

        $indexedData = $this->rows();

        $infobpr = $this->infobprModel->getDataByKodebpr($this->userKodebpr);
        $sandibpr = $infobpr[0]['sandibpr'] ?? '';
        $kodejenis = $infobpr[0]['kodejenis'] ?? '';

        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0201|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";

        foreach (self::KODE_MAP as $faktorId => $kode) {
            $row = $indexedData[$faktorId] ?? [];
            $penilaiankredit = $this->sanitizeTxt($row['penilaiankredit'] ?? '');
            $keterangan = $this->sanitizeTxt($row['keterangan'] ?? '');
            $output .= "D01|{$kode}|{$penilaiankredit}|{$keterangan}\r\n";
        }

        $filename = "TKSBPRKS-0201-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }
}
