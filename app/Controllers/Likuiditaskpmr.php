<?php

namespace App\Controllers;

use App\Libraries\ProfilRisikoController;
use App\Models\M_likuiditaskpmr;

/**
 * Penilaian Risiko Likuiditas KPMR (tabel likuiditaskpmr, faktor 118–136).
 * Logika halaman di App\Libraries\ProfilRisikoController; ekspor TXT dipakai Showprofilresiko & Showsemesteran.
 */
class Likuiditaskpmr extends ProfilRisikoController
{
    protected const TABLE = 'likuiditaskpmr';
    protected const COL_NILAI = 'penilaiankredit';
    protected const SUBKATEGORI = 'LIKUIDITASKPMR';
    protected const ROUTE = 'Likuiditaskpmr';
    protected const TITLE = 'Risiko Likuiditas KPMR';
    protected const RISK = 'Risiko Likuiditas';
    protected const ICON = 'fa-water';
    protected const TOTAL_ID = 135;
    protected const PREV_ID = 136;
    protected const NAV = ['Likuiditasinheren' => 'Inheren', 'Likuiditaskpmr' => 'KPMR'];

    private const KODE_MAP = [
        118 => '4310', 119 => '4311', 120 => '4312', 121 => '4313', 122 => '4314', 123 => '4315', 124 => '4316',
        125 => '4320', 126 => '4321', 127 => '4322', 128 => '4323',
        129 => '4330', 130 => '4331', 131 => '4332',
        132 => '4340', 133 => '4341', 134 => '4342',
        135 => '4350',
    ];

    protected $nilaiModel;

    public function __construct()
    {
        parent::__construct();
        $this->nilaiModel = new M_likuiditaskpmr();
    }

    protected function structure(): array
    {
        return [
            ['id' => 118, 'title' => 'Pengawasan Direksi dan Dewan Komisaris', 'children' => [
                ['id' => 119, 'title' => 'Apakah Dewan Komisaris telah melakukan persetujuan terhadap kebijakan manajemen risiko likuiditas yang disusun oleh Direksi dan melakukan evaluasi secara berkala?'],
                ['id' => 120, 'title' => 'Apakah Dewan Komisaris telah melakukan evaluasi terhadap pertanggungjawaban Direksi atas pelaksanaan kebijakan manajemen risiko likuiditas secara berkala dan memastikan tindak lanjut hasil evaluasi dimaksud?'],
                ['id' => 121, 'title' => 'Apakah Direksi telah menyusun kebijakan manajemen risiko likuiditas, melaksanakan secara konsisten, dan melakukan pengkinian secara berkala?'],
                ['id' => 122, 'title' => 'Apakah Direksi telah memiliki kemampuan untuk mengambil tindakan yang diperlukan dalam rangka mitigasi risiko likuiditas, dan melakukan komunikasi kebijakan manajemen risiko likuiditas terhadap seluruh jenjang organisasi BPR?'],
                ['id' => 123, 'title' => 'Apakah BPR telah memiliki kecukupan organisasi yang menangani fungsi likuiditas dan fungsi manajemen risiko likuiditas?'],
                ['id' => 124, 'title' => 'Apakah Direksi telah menerapkan kebijakan pengelolaan SDM dalam rangka penerapan Manajemen Risiko likuiditas?'],
            ]],
            ['id' => 125, 'title' => 'Kecukupan Kebijakan, Prosedur, dan Limit', 'children' => [
                ['id' => 126, 'title' => 'Apakah BPR telah memiliki kebijakan manajemen risiko likuiditas yang memadai dan disusun dengan mempertimbangkan visi, misi, skala usaha dan kompleksitas bisnis, serta kecukupan SDM?'],
                ['id' => 127, 'title' => 'Apakah BPR memiliki prosedur manajemen risiko likuiditas dan penetapan limit risiko likuiditas, melaksanakannya secara konsisten, serta melakukan evaluasi dan pengkinian secara berkala?'],
                ['id' => 128, 'title' => 'Apakah BPR telah memiliki kebijakan dan prosedur penerbitan produk dan/atau pelaksanaan aktivitas baru yang mencakup identifikasi dan mitigasi risiko likuiditas sesuai dengan ketentuan?'],
            ]],
            ['id' => 129, 'title' => 'Kecukupan Proses dan Sistem Manajemen Informasi', 'children' => [
                ['id' => 130, 'title' => 'Apakah BPR telah melaksanakan proses manajemen risiko likuiditas yang melekat pada kegiatan usaha BPR?'],
                ['id' => 131, 'title' => 'Apakah BPR telah memiliki sistem informasi manajemen risiko yang mendukung Direksi dalam pengambilan keputusan terkait risiko likuiditas serta telah dilaporkan kepada Direksi secara berkala?'],
            ]],
            ['id' => 132, 'title' => 'Sistem Pengendalian Internal yang Menyeluruh', 'children' => [
                ['id' => 133, 'title' => 'Apakah SKAI atau PEAI telah melaksanakan audit secara berkala terhadap penerapan manajemen risiko likuiditas, menyampaikan laporan hasil audit intern, dan memastikan tindaklanjut atas temuan pemeriksaan?'],
                ['id' => 134, 'title' => 'Apakah sistem pengendalian intern terhadap risiko likuiditas telah dilaksanakan oleh seluruh jenjang organisasi BPR?'],
            ]],
        ];
    }

    protected function penilaianConfig(): array
    {
        // catatan lama generik ("Penilaian Risiko Likuiditas KPMR") → tidak ditampilkan
        return [
            118 => ['descriptions' => [
                1 => 'Parameter Pengawasan Direksi dan Dewan Komisaris berada pada tingkat risiko yang Sangat Rendah',
                2 => 'Parameter Pengawasan Direksi dan Dewan Komisaris berada pada tingkat risiko yang Rendah',
                3 => 'Parameter Pengawasan Direksi dan Dewan Komisaris berada pada tingkat risiko yang Sedang',
                4 => 'Parameter Pengawasan Direksi dan Dewan Komisaris berada pada tingkat risiko yang Tinggi',
                5 => 'Parameter Pengawasan Direksi dan Dewan Komisaris berada pada tingkat risiko yang Sangat Tinggi',
            ]],
            119 => ['descriptions' => [
                1 => '• Dewan Komisaris telah memberikan persetujuan dan melakukan evaluasi berkala; relevan dengan kebutuhan penyesuaian kebijakan.',
                2 => '• Dewan Komisaris telah memberikan persetujuan dan melakukan evaluasi paling sedikit satu kali dalam satu tahun.',
                3 => '• Dewan Komisaris telah memberikan persetujuan dan melakukan evaluasi; namun tidak secara berkala.',
                4 => '• Dewan Komisaris telah memberikan persetujuan; namun tidak melakukan evaluasi.',
                5 => '• Dewan Komisaris tidak memberikan persetujuan maupun melakukan evaluasi.',
            ]],
            120 => ['descriptions' => [
                1 => '• Evaluasi dilakukan setiap semester; memastikan tindak lanjut; relevan mendukung perbaikan kinerja.',
                2 => '• Evaluasi dilakukan setiap semester; memastikan tindak lanjut.',
                3 => '• Evaluasi dilakukan setiap semester; tidak memastikan tindak lanjut.',
                4 => '• Evaluasi tidak dilakukan secara berkala; tidak memastikan tindak lanjut.',
                5 => 'Dewan Komisaris tidak melakukan evaluasi sama sekali.',
            ]],
            121 => ['descriptions' => [
                1 => '• Direksi menyusun, menjalankan, melakukan pengkinian, dan terbukti memitigasi risiko.',
                2 => '• Direksi menyusun, menjalankan, dan melakukan pengkinian kebijakan.',
                3 => '• Direksi menyusun dan menjalankan; namun tidak melakukan pengkinian.',
                4 => '• Direksi menyusun kebijakan; namun tidak menjalankan dan tidak melakukan pengkinian.',
                5 => 'Direksi tidak menyusun kebijakan Manajemen Risiko likuiditas.',
            ]],
            122 => ['descriptions' => [
                1 => '• Direksi mengambil tindakan mitigasi dan mengomunikasikan kebijakan; seluruh jenjang memahami.',
                2 => '• Direksi mengambil tindakan dan mengomunikasikan; tidak seluruh jenjang memahami namun tidak signifikan.',
                3 => '• Direksi mengambil tindakan dan mengomunikasikan; tidak seluruh jenjang memahami dan berdampak signifikan.',
                4 => '• Direksi mengambil tindakan; tidak mengomunikasikan; seluruh jenjang tidak memahami.',
                5 => '• Direksi tidak mengambil tindakan, tidak mengomunikasikan, seluruh jenjang tidak memahami.',
            ]],
            123 => ['descriptions' => [
                1 => '• Organisasi lengkap; tidak ada rangkap jabatan bermasalah; SKMR/PEMR mampu berfungsi.',
                2 => '• Organisasi tidak lengkap; tidak ada rangkap jabatan bermasalah; SKMR/PEMR mampu berfungsi.',
                3 => '• Organisasi tidak lengkap; ada rangkap jabatan tidak bermasalah; SKMR/PEMR tidak mampu berfungsi.',
                4 => '• Organisasi tidak lengkap; ada rangkap jabatan bermasalah; SKMR/PEMR tidak mampu berfungsi.',
                5 => '• Organisasi tidak lengkap; rangkap jabatan bermasalah; unit tidak melaksanakan tugas; SKMR/PEMR tidak berfungsi.',
            ]],
            124 => ['descriptions' => [
                1 => '• Kualifikasi SDM sesuai; upaya peningkatan konsisten; standar kinerja terpenuhi.',
                2 => '• Ketidaksesuaian kualifikasi tidak signifikan; upaya peningkatan konsisten; standar terpenuhi.',
                3 => '• Ketidaksesuaian kualifikasi tidak signifikan; upaya peningkatan tidak konsisten; standar terpenuhi.',
                4 => '• Ketidaksesuaian kualifikasi signifikan; upaya tidak konsisten; standar tidak terpenuhi tapi tidak signifikan.',
                5 => '• Ketidaksesuaian kualifikasi signifikan; tidak ada upaya peningkatan; standar tidak terpenuhi dan signifikan.',
            ]],
            125 => ['descriptions' => [
                1 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Sangat Rendah',
                2 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Rendah',
                3 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Sedang',
                4 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Tinggi',
                5 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Sangat Tinggi',
            ]],
            126 => ['descriptions' => [
                1 => '• Memiliki kebijakan; substansi sesuai ketentuan; selaras dengan visi, misi, skala usaha.',
                2 => '• Memiliki kebijakan; ketidaksesuaian substansi tidak signifikan; selaras dengan visi/misi.',
                3 => '• Memiliki kebijakan; ketidaksesuaian substansi tidak signifikan; ketidakselarasan tidak berdampak signifikan.',
                4 => '• Memiliki kebijakan; ketidaksesuaian substansi signifikan; ketidakselarasan berdampak signifikan.',
                5 => 'Tidak memiliki kebijakan Manajemen Risiko likuiditas.',
            ]],
            127 => ['descriptions' => [
                1 => '• Memiliki prosedur dan limit; dilaksanakan konsisten; dilakukan evaluasi dan pengkinian.',
                2 => '• Memiliki prosedur dan limit; dilaksanakan konsisten; tidak melakukan evaluasi namun tidak signifikan.',
                3 => '• Memiliki prosedur dan limit; dilaksanakan konsisten; tidak melakukan evaluasi dan berdampak signifikan.',
                4 => '• Memiliki prosedur dan limit; tidak dilaksanakan konsisten; tidak melakukan evaluasi.',
                5 => 'Tidak memiliki prosedur dan limit Manajemen Risiko likuiditas.',
            ]],
            128 => ['descriptions' => [
                1 => '• Memiliki kebijakan dan prosedur produk baru; diterapkan; sesuai ketentuan.',
                2 => '• Memiliki kebijakan dan prosedur; diterapkan; ketidaksesuaian tidak signifikan.',
                3 => '• Memiliki kebijakan dan prosedur; diterapkan; ketidaksesuaian signifikan.',
                4 => '• Memiliki kebijakan dan prosedur; tidak diterapkan; ketidaksesuaian signifikan.',
                5 => 'Tidak memiliki kebijakan dan prosedur produk/aktivitas baru.',
            ]],
            129 => ['descriptions' => [
                1 => 'Parameter Kecukupan Proses dan Sistem Informasi Manajemen Risiko berada pada tingkat risiko yang Sangat Rendah',
                2 => 'Parameter Kecukupan Proses dan Sistem Informasi Manajemen Risiko berada pada tingkat risiko yang Rendah',
                3 => 'Parameter Kecukupan Proses dan Sistem Informasi Manajemen Risiko berada pada tingkat risiko yang Sedang',
                4 => 'Parameter Kecukupan Proses dan Sistem Informasi Manajemen Risiko berada pada tingkat risiko yang Tinggi',
                5 => 'Parameter Kecukupan Proses dan Sistem Informasi Manajemen Risiko berada pada tingkat risiko yang Sangat Tinggi',
            ]],
            130 => ['descriptions' => [
                1 => '• Proses MR sangat memadai dan konsisten.',
                2 => '• Proses MR memadai dan cukup konsisten.',
                3 => '• Proses MR memadai; tidak konsisten namun tidak signifikan.',
                4 => '• Proses MR tidak secara keseluruhan; tidak memadai; tidak konsisten dan signifikan.',
                5 => 'Tidak melaksanakan proses Manajemen Risiko likuiditas.',
            ]],
            131 => ['descriptions' => [
                1 => '• Sistem informasi MR ada; data lengkap/akurat; mendukung Direksi; sangat mendukung laporan SKMR/PEMR.',
                2 => '• Sistem informasi MR ada; data cukup lengkap; mendukung Direksi; cukup mendukung laporan SKMR/PEMR.',
                3 => '• Sistem informasi MR ada; data kurang lengkap; tidak sepenuhnya mendukung Direksi; kurang mendukung laporan.',
                4 => '• Sistem informasi MR ada; data tidak lengkap; tidak mendukung Direksi; tidak mendukung laporan SKMR/PEMR.',
                5 => 'Tidak memiliki sistem informasi Manajemen Risiko likuiditas.',
            ]],
            132 => ['descriptions' => [
                1 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Sangat Rendah',
                2 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Rendah',
                3 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Sedang',
                4 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Tinggi',
                5 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Sangat Tinggi',
            ]],
            133 => ['descriptions' => [
                1 => '• SKAI/PEAI melaksanakan audit; memberikan rekomendasi; seluruh temuan ditindaklanjuti.',
                2 => '• SKAI/PEAI melaksanakan audit; memberikan rekomendasi; temuan tidak sepenuhnya ditindaklanjuti tapi tidak signifikan.',
                3 => '• SKAI/PEAI melaksanakan audit; memberikan rekomendasi; temuan tidak sepenuhnya ditindaklanjuti dan signifikan.',
                4 => '• SKAI/PEAI melaksanakan audit; cakupan tidak sesuai; temuan tidak ditindaklanjuti.',
                5 => 'SKAI atau PEAI tidak melaksanakan audit intern.',
            ]],
            134 => ['descriptions' => [
                1 => '• Seluruh jenjang melaksanakan pengendalian intern; kejelasan wewenang; SKMR/PEMR dan SKAI/PEAI terpisah.',
                2 => '• Seluruh jenjang melaksanakan pengendalian tidak sepenuhnya; tidak berdampak signifikan; pemisahan ada.',
                3 => '• Seluruh jenjang melaksanakan pengendalian tidak sepenuhnya; berdampak signifikan; pemisahan ada.',
                4 => '• Tidak seluruh jenjang melaksanakan; tidak ada kejelasan wewenang; pemisahan ada.',
                5 => '• Seluruh jenjang tidak melaksanakan; tidak ada kejelasan wewenang; SKMR/PEMR dan SKAI/PEAI tidak terpisah.',
            ]],
        ];
    }

    private function sanitizeTxt(string $text): string
    {
        $text = str_replace(["\r", "\n"], ' ', $text);
        $text = str_replace(['“', '”', '‘', '’', '%', '|'], ['"', '"', "'", "'", ' persen ', ' '], $text);
        $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    public function exporttxtrisikolikuiditaskpmr()
    {
        if (!$this->auth->check()) {
            return redirect()->to(session('redirect_url') ?? '/login');
        }
        // Periode aktif bukan milik BPR user → tidak ada berkas
        if (!$this->periodeId || !$this->userKodebpr) {
            return service('response')->setContentType('text/plain')->setBody('');
        }

        ['modalinti' => $modalinti, 'nomorsurat' => $nomorsurat, 'jeniskoreksi' => $jeniskoreksi,
            'jenispelaporan' => $jenispelaporan, 'kategori' => $kategori, 'tahun' => $tahun, 'semester' => $semester] = $this->periode;

        $exportDate = (strtolower($semester) === 'ganjil' || $semester === '1') ? "{$tahun}-06-30" : "{$tahun}-12-31";
        $titleDate = str_replace('-', '', $exportDate);

        $indexedData = array_column($this->nilaiModel->getDataByKodebprAndPeriode($this->userKodebpr, $this->periodeId), null, 'faktor1id');

        $infobpr = $this->infobprModel->getDataByKodebpr($this->userKodebpr);
        $sandibpr = $infobpr[0]['sandibpr'] ?? '';
        $kodejenis = $infobpr[0]['kodejenis'] ?? '';

        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0402|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";
        foreach (self::KODE_MAP as $faktorId => $kode) {
            $row = $indexedData[$faktorId] ?? [];
            $output .= "D01|{$kode}|{$this->sanitizeTxt((string) ($row['penilaiankredit'] ?? ''))}|{$this->sanitizeTxt((string) ($row['keterangan'] ?? ''))}\r\n";
        }

        $filename = "TKSBPRKS-0402-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }
}
