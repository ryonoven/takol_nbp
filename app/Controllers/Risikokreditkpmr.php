<?php

namespace App\Controllers;

use App\Libraries\ProfilRisikoController;
use App\Models\M_risikokreditkpmr;

/**
 * Penilaian Risiko Kredit KPMR (tabel risikokredit_kpmr, faktor 16–34).
 * Logika halaman di App\Libraries\ProfilRisikoController; ekspor TXT dipakai Showprofilresiko & Showsemesteran.
 */
class Risikokreditkpmr extends ProfilRisikoController
{
    protected const TABLE = 'risikokredit_kpmr';
    protected const COL_NILAI = 'penilaiankredit';
    protected const SUBKATEGORI = 'KREDITKPMR';
    protected const ROUTE = 'Risikokreditkpmr';
    protected const TITLE = 'Risiko Kredit KPMR';
    protected const RISK = 'Risiko Kredit';
    protected const ICON = 'fa-tasks';
    protected const TOTAL_ID = 33;
    protected const PREV_ID = 34;
    protected const NAV = ['Risikokredit' => 'Inheren', 'Risikokreditkpmr' => 'KPMR'];

    protected $nilaiModel;

    public function __construct()
    {
        parent::__construct();
        $this->nilaiModel = new M_risikokreditkpmr();
    }

    protected function structure(): array
    {
        return [
            ['id' => 16, 'title' => 'Pengawasan Direksi dan Dewan Komisaris', 'children' => [
                ['id' => 17, 'title' => 'Apakah Dewan Komisaris telah melakukan persetujuan terhadap kebijakan manajemen risiko kredit yang disusun oleh Direksi dan melakukan evaluasi secara berkala?'],
                ['id' => 18, 'title' => 'Apakah Dewan Komisaris telah melakukan evaluasi terhadap pertanggungjawaban Direksi atas pelaksanaan kebijakan manajemen risiko kredit secara berkala dan memastikan tindak lanjut hasil evaluasi dimaksud?'],
                ['id' => 19, 'title' => 'Apakah Direksi telah menyusun kebijakan manajemen risiko kredit, melaksanakan secara konsisten, dan melakukan pengkinian secara berkala?'],
                ['id' => 20, 'title' => 'Apakah Direksi telah memiliki kemampuan untuk mengambil tindakan yang diperlukan dalam rangka mitigasi risiko kredit, dan melakukan komunikasi kebijakan manajemen risiko kredit terhadap seluruh jenjang organisasi BPR?'],
                ['id' => 21, 'title' => 'Apakah BPR telah memiliki kecukupan organisasi yang menangani fungsi kredit dan fungsi manajemen risiko kredit?'],
                ['id' => 22, 'title' => 'Apakah Direksi telah menerapkan kebijakan pengelolaan SDM dalam rangka penerapan Manajemen Risiko kredit?'],
            ]],
            ['id' => 23, 'title' => 'Kecukupan Kebijakan, Prosedur, dan Limit', 'children' => [
                ['id' => 24, 'title' => 'Apakah BPR telah memiliki kebijakan manajemen risiko kredit yang memadai dan disusun dengan mempertimbangkan visi, misi, skala usaha dan kompleksitas bisnis, serta kecukupan SDM?'],
                ['id' => 25, 'title' => "Apakah BPR:\n● Memiliki prosedur manajemen risiko kredit dan penetapan limit risiko kredit yang ditetapkan oleh Direksi;\n● Melaksanakan prosedur manajemen risiko kredit dan penetapan limit risiko kredit secara konsisten untuk seluruh aktivitas; dan\n● Melakukan evaluasi dan pengkinian terhadap prosedur manajemen risiko kredit dan penetapan limit risiko kredit secara berkala?"],
                ['id' => 26, 'title' => 'Apakah BPR telah memiliki kebijakan dan prosedur penerbitan produk dan/atau pelaksanaan aktivitas baru yang mencakup identifikasi dan mitigasi risiko kredit sesuai dengan ketentuan?'],
            ]],
            ['id' => 27, 'title' => 'Kecukupan Proses dan Sistem Manajemen Informasi', 'children' => [
                ['id' => 28, 'title' => 'Apakah BPR telah melaksanakan proses manajemen risiko kredit yang melekat pada kegiatan usaha BPR?'],
                ['id' => 29, 'title' => 'Apakah BPR telah memiliki sistem informasi manajemen risiko yang mendukung Direksi dalam pengambilan keputusan terkait risiko kredit serta telah dilaporkan kepada Direksi secara berkala?'],
            ]],
            ['id' => 30, 'title' => 'Sistem Pengendalian Internal yang Menyeluruh', 'children' => [
                ['id' => 31, 'title' => 'Apakah SKAI atau PEAI telah melaksanakan audit secara berkala terhadap penerapan manajemen risiko kredit, menyampaikan laporan hasil audit intern, dan memastikan tindaklanjut atas temuan pemeriksaan?'],
                ['id' => 32, 'title' => 'Apakah sistem pengendalian intern terhadap risiko kredit telah dilaksanakan oleh seluruh jenjang organisasi BPR?'],
            ]],
        ];
    }

    protected function penilaianConfig(): array
    {
        // catatan "KPMR" pada konfigurasi lama tidak informatif → tidak ditampilkan
        return array_map(static fn($c) => array_diff_key($c, ['catatan' => 1]), [
            16 => ['descriptions' => [1 => 'Parameter Pengawasan Direksi dan Komisaris berada pada tingkat risiko yang Sangat Rendah', 2 => 'Parameter Pengawasan Direksi dan Komisaris berada pada tingkat risiko yang Rendah', 3 => 'Parameter Pengawasan Direksi dan Komisaris berada pada tingkat risiko yang Sedang', 4 => 'Parameter Pengawasan Direksi dan Komisaris berada pada tingkat risiko yang Tinggi', 5 => 'Parameter Pengawasan Direksi dan Komisaris berada pada tingkat risiko yang Sangat Tinggi'], 'catatan' => 'KPMR'],
            17 => ['descriptions' => [1 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko kredit <br>• Dewan Komisaris telah melakukan evaluasi terhadap kebijakan Manajemen Risiko kredit <br>• Evaluasi dilakukan secara berkala paling sedikit satu kali dalam satu tahun <br>• Evaluasi yang dilakukan relevan dengan kebutuhan penyesuaian kebijakan.', 2 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko kredit <br>• Dewan Komisaris telah melakukan evaluasi secara berkala paling sedikit satu kali dalam satu tahun.', 3 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko kredit <br>• Evaluasi tidak dilakukan secara berkala.', 4 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko kredit <br>• Dewan Komisaris tidak melakukan evaluasi.', 5 => '• Dewan Komisaris tidak memberikan persetujuan terhadap kebijakan Manajemen Risiko kredit <br>• Dewan Komisaris tidak melakukan evaluasi.'], 'catatan' => 'KPMR'],
            18 => ['descriptions' => [1 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan oleh Direksi <br>• Evaluasi dilakukan secara berkala setiap semester <br>• Dewan Komisaris telah memastikan tindak lanjut hasil evaluasi <br>• Evaluasi relevan dengan pelaksanaan kebijakan.', 2 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan oleh Direksi <br>• Evaluasi dilakukan secara berkala setiap semester <br>• Dewan Komisaris telah memastikan tindak lanjut hasil evaluasi.', 3 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan oleh Direksi <br>• Evaluasi dilakukan secara berkala <br>• Dewan Komisaris tidak memastikan tindak lanjut hasil evaluasi.', 4 => '• Dewan Komisaris telah melakukan evaluasi <br>• Evaluasi tidak dilakukan secara berkala <br>• Dewan Komisaris tidak memastikan tindak lanjut.', 5 => 'Dewan Komisaris tidak melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko kredit oleh Direksi.'], 'catatan' => 'KPMR'],
            19 => ['descriptions' => [1 => '• Direksi telah menyusun kebijakan Manajemen Risiko kredit <br>• Menjalankan kegiatan usaha berdasarkan kebijakan yang ditetapkan <br>• Direksi melakukan pengkinian secara berkala <br>• Kebijakan terbukti memitigasi Risiko kredit.', 2 => '• Direksi telah menyusun kebijakan Manajemen Risiko kredit <br>• Menjalankan kegiatan usaha berdasarkan kebijakan yang ditetapkan <br>• Direksi melakukan pengkinian secara berkala.', 3 => '• Direksi telah menyusun kebijakan Manajemen Risiko kredit <br>• Menjalankan kegiatan usaha berdasarkan kebijakan yang ditetapkan <br>• Direksi tidak melakukan pengkinian secara berkala.', 4 => '• Direksi telah menyusun kebijakan Manajemen Risiko kredit <br>• Tidak menjalankan kegiatan usaha berdasarkan kebijakan yang ditetapkan.', 5 => 'Direksi tidak menyusun kebijakan Manajemen Risiko kredit.'], 'catatan' => 'KPMR'],
            20 => ['descriptions' => [1 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko <br>• Direksi mengomunikasikan kebijakan Manajemen Risiko kredit <br>• Seluruh jenjang organisasi mampu memahami kebijakan.', 2 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko <br>• Direksi mengomunikasikan kebijakan Manajemen Risiko kredit <br>• Tidak seluruh jenjang memahami namun tidak signifikan.', 3 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko <br>• Direksi mengomunikasikan kebijakan <br>• Tidak seluruh jenjang memahami dan menimbulkan dampak signifikan.', 4 => '• Direksi mengambil tindakan yang diperlukan <br>• Direksi tidak mengomunikasikan kebijakan.', 5 => '• Direksi tidak mengambil tindakan yang diperlukan <br>• Direksi tidak mengomunikasikan kebijakan.'], 'catatan' => 'KPMR'],
            21 => ['descriptions' => [1 => '• Memiliki unit kerja yang menangani fungsi kredit secara lengkap tanpa rangkap jabatan <br>• Unit kerja melaksanakan tugas sesuai pedoman <br>• Memiliki SKMR atau PEMR yang mampu memitigasi Risiko kredit.', 2 => '• Memiliki unit kerja tidak lengkap tanpa rangkap jabatan berbahaya <br>• Unit kerja melaksanakan tugas sesuai pedoman <br>• Memiliki SKMR atau PEMR yang mampu memitigasi.', 3 => '• Memiliki unit kerja tidak lengkap dengan rangkap jabatan tidak berbahaya <br>• Unit kerja melaksanakan tugas sesuai pedoman <br>• Memiliki SKMR atau PEMR namun tidak optimal.', 4 => '• Memiliki unit kerja tidak lengkap dengan rangkap jabatan berbahaya <br>• Unit kerja melaksanakan tugas sesuai pedoman <br>• Memiliki SKMR atau PEMR namun tidak optimal.', 5 => '• Memiliki unit kerja tidak lengkap dengan rangkap jabatan berbahaya <br>• Unit kerja tidak melaksanakan tugas sesuai pedoman <br>• Memiliki SKMR atau PEMR namun tidak optimal.'], 'catatan' => 'KPMR'],
            22 => ['descriptions' => [1 => '• Terdapat kesesuaian kualifikasi SDM dengan jabatan <br>• Terdapat upaya peningkatan kompetensi SDM secara konsisten <br>• Pemenuhan standar kinerja SDM sesuai tugas dan tanggung jawab.', 2 => '• Terdapat ketidaksesuaian kualifikasi SDM namun tidak signifikan <br>• Terdapat upaya peningkatan kompetensi secara konsisten <br>• Pemenuhan standar kinerja sesuai tugas.', 3 => '• Terdapat ketidaksesuaian kualifikasi SDM tidak signifikan <br>• Upaya peningkatan kompetensi tidak konsisten <br>• Pemenuhan standar kinerja sesuai tugas.', 4 => '• Terdapat ketidaksesuaian kualifikasi SDM yang signifikan <br>• Upaya peningkatan tidak konsisten <br>• Pemenuhan standar kinerja tidak sesuai namun tidak signifikan.', 5 => '• Terdapat ketidaksesuaian kualifikasi SDM yang signifikan <br>• Tidak terdapat upaya peningkatan kompetensi <br>• Pemenuhan standar kinerja tidak sesuai dan signifikan.'], 'catatan' => 'KPMR'],
            23 => ['descriptions' => [1 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Sangat Rendah', 2 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Rendah', 3 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Sedang', 4 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Tinggi', 5 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Sangat Tinggi'], 'catatan' => 'KPMR'],
            24 => ['descriptions' => [1 => '• Telah memiliki kebijakan Manajemen Risiko kredit <br>• Terdapat kesesuaian antara substansi kebijakan dengan ketentuan <br>• Terdapat keselarasan dengan visi, misi, dan skala usaha.', 2 => '• Telah memiliki kebijakan Manajemen Risiko kredit <br>• Terdapat ketidaksesuaian tidak signifikan antara substansi kebijakan dengan ketentuan <br>• Terdapat keselarasan dengan visi, misi, dan skala usaha.', 3 => '• Telah memiliki kebijakan Manajemen Risiko kredit <br>• Terdapat ketidaksesuaian tidak signifikan <br>• Terdapat ketidakselarasan tidak menimbulkan dampak signifikan.', 4 => '• Telah memiliki kebijakan Manajemen Risiko kredit <br>• Terdapat ketidaksesuaian yang signifikan <br>• Terdapat ketidakselarasan yang menimbulkan dampak signifikan.', 5 => 'Tidak memiliki kebijakan Manajemen Risiko kredit.'], 'catatan' => 'KPMR'],
            25 => ['descriptions' => [1 => '• Memiliki prosedur dan penetapan limit yang ditetapkan Direksi <br>• Melaksanakan prosedur secara konsisten <br>• Melakukan evaluasi dan pengkinian prosedur secara berkala.', 2 => '• Memiliki prosedur dan penetapan limit yang ditetapkan Direksi <br>• Melaksanakan prosedur secara konsisten <br>• Tidak melakukan evaluasi namun tidak berdampak signifikan.', 3 => '• Memiliki prosedur dan penetapan limit yang ditetapkan Direksi <br>• Melaksanakan prosedur secara konsisten <br>• Tidak melakukan evaluasi dan berdampak signifikan.', 4 => '• Memiliki prosedur dan penetapan limit yang ditetapkan Direksi <br>• Tidak melaksanakan prosedur secara konsisten <br>• Tidak melakukan evaluasi dan pengkinian.', 5 => 'Tidak memiliki prosedur Manajemen Risiko kredit dan penetapan limit.'], 'catatan' => 'KPMR'],
            26 => ['descriptions' => [1 => '• Memiliki kebijakan dan prosedur produk/aktivitas baru <br>• Menerapkan kebijakan dan prosedur saat penerbitan produk baru <br>• Terdapat kesesuaian dengan ketentuan.', 2 => '• Memiliki kebijakan dan prosedur produk/aktivitas baru <br>• Menerapkan kebijakan dan prosedur <br>• Terdapat ketidaksesuaian tidak signifikan.', 3 => '• Memiliki kebijakan dan prosedur produk/aktivitas baru <br>• Menerapkan kebijakan dan prosedur <br>• Terdapat ketidaksesuaian yang signifikan.', 4 => '• Memiliki kebijakan dan prosedur produk/aktivitas baru <br>• Tidak menerapkan kebijakan dan prosedur <br>• Terdapat ketidaksesuaian yang signifikan.', 5 => 'Tidak memiliki kebijakan dan prosedur penerbitan produk dan/atau aktivitas baru.'], 'catatan' => 'KPMR'],
            27 => ['descriptions' => [1 => 'Parameter Kecukupan Proses dan Sistem Manajemen Risiko berada pada tingkat risiko yang Sangat Rendah', 2 => 'Parameter Kecukupan Proses dan Sistem Manajemen Risiko berada pada tingkat risiko yang Rendah', 3 => 'Parameter Kecukupan Proses dan Sistem Manajemen Risiko berada pada tingkat risiko yang Sedang', 4 => 'Parameter Kecukupan Proses dan Sistem Manajemen Risiko berada pada tingkat risiko yang Tinggi', 5 => 'Parameter Kecukupan Proses dan Sistem Manajemen Risiko berada pada tingkat risiko yang Sangat Tinggi'], 'catatan' => 'KPMR'],
            28 => ['descriptions' => [1 => '• Telah melaksanakan proses identifikasi, pengukuran, pemantauan, dan pengendalian Risiko kredit <br>• Penerapan dilakukan dengan sangat memadai <br>• Penerapan dilakukan secara konsisten.', 2 => '• Telah melaksanakan proses identifikasi, pengukuran, pemantauan, dan pengendalian Risiko kredit <br>• Penerapan dilakukan dengan memadai <br>• Penerapan dilakukan cukup konsisten.', 3 => '• Telah melaksanakan proses identifikasi, pengukuran, pemantauan, dan pengendalian Risiko kredit <br>• Penerapan dilakukan dengan memadai <br>• Penerapan tidak dilakukan secara konsisten namun tidak signifikan.', 4 => '• Melaksanakan proses tidak secara keseluruhan <br>• Penerapan tidak memadai <br>• Tidak dilakukan secara konsisten sehingga menimbulkan dampak signifikan.', 5 => 'Tidak melaksanakan proses Manajemen Risiko kredit.'], 'catatan' => 'KPMR'],
            29 => ['descriptions' => [1 => '• Telah memiliki sistem informasi Manajemen Risiko <br>• Data lengkap, akurat, kini, dan utuh <br>• Mendukung Direksi dalam pengambilan keputusan <br>• Sangat mendukung SKMR atau PEMR dalam pembuatan laporan.', 2 => '• Telah memiliki sistem informasi Manajemen Risiko <br>• Data cukup lengkap, akurat, kini, dan utuh <br>• Mendukung Direksi dalam pengambilan keputusan <br>• Cukup mendukung SKMR atau PEMR.', 3 => '• Telah memiliki sistem informasi Manajemen Risiko <br>• Data kurang lengkap, akurat, kini, dan utuh <br>• Tidak sepenuhnya mendukung Direksi <br>• Kurang mendukung SKMR atau PEMR.', 4 => '• Telah memiliki sistem informasi Manajemen Risiko <br>• Data tidak lengkap, akurat, kini, dan utuh <br>• Tidak mendukung Direksi <br>• Tidak mendukung SKMR atau PEMR.', 5 => 'Tidak memiliki sistem informasi Manajemen Risiko.'], 'catatan' => 'KPMR'],
            30 => ['descriptions' => [1 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Sangat Rendah', 2 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Rendah', 3 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Sedang', 4 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Tinggi', 5 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Sangat Tinggi'], 'catatan' => 'KPMR'],
            31 => ['descriptions' => [1 => '• SKAI atau PEAI telah melaksanakan audit intern terhadap penerapan Manajemen Risiko kredit <br>• Audit meliputi pelaksanaan kebijakan dan prosedur <br>• Hasil temuan telah ditindaklanjuti.', 2 => '• SKAI atau PEAI telah melaksanakan audit intern <br>• Audit meliputi pelaksanaan kebijakan dan prosedur <br>• Hasil temuan tidak sepenuhnya ditindaklanjuti namun tidak signifikan.', 3 => '• SKAI atau PEAI telah melaksanakan audit intern <br>• Audit meliputi pelaksanaan kebijakan dan prosedur <br>• Hasil temuan tidak sepenuhnya ditindaklanjuti dan menimbulkan dampak signifikan.', 4 => '• SKAI atau PEAI telah melaksanakan audit intern <br>• Audit tidak sesuai cakupan <br>• Hasil temuan tidak ditindaklanjuti.', 5 => 'SKAI atau PEAI tidak melaksanakan audit intern terhadap penerapan Manajemen Risiko kredit.'], 'catatan' => 'KPMR'],
            32 => ['descriptions' => [1 => '• Seluruh jenjang organisasi telah melaksanakan fungsi pengendalian intern <br>• Terdapat kejelasan wewenang dan tanggung jawab <br>• SKMR atau PEMR terpisah dari unit terkait <br>• SKAI atau PEAI terpisah dari unit terkait.', 2 => '• Seluruh jenjang telah melaksanakan pengendalian intern namun tidak sepenuhnya memperhatikan kebijakan dan tidak berdampak signifikan <br>• Terdapat kejelasan wewenang <br>• SKMR dan SKAI terpisah.', 3 => '• Seluruh jenjang telah melaksanakan pengendalian intern namun tidak sepenuhnya dan berdampak signifikan <br>• Terdapat kejelasan wewenang <br>• SKMR dan SKAI terpisah.', 4 => '• Tidak seluruh jenjang melaksanakan fungsi pengendalian intern dan berdampak sangat signifikan <br>• Tidak terdapat kejelasan wewenang <br>• SKMR dan SKAI terpisah.', 5 => '• Seluruh jenjang tidak melaksanakan fungsi pengendalian intern <br>• Tidak terdapat kejelasan wewenang <br>• SKMR atau PEMR tidak terpisah <br>• SKAI atau PEAI tidak terpisah.'], 'catatan' => 'KPMR'],
        ]);
    }

    public function exporttxtrisikokreditkpmr()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $kodebpr = $this->userKodebpr;
        if (!$this->periodeId || !$this->userKodebpr) {
            return service('response')->setContentType('text/plain')->setBody('');
        }
        $periodeId = $this->periodeId;
        $periodeDetail = $this->periodeModel->find($this->periodeId);

        $modalinti = $periodeDetail['modalinti'];
        $nomorsurat = $periodeDetail['nomorsurat'];
        $jeniskoreksi = $periodeDetail['jeniskoreksi'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];
        $kategori = $periodeDetail['kategori'];
        $tahun = $periodeDetail['tahun'] ?? date('Y');
        $semester = $periodeDetail['semester'] ?? '';

        $exportDate = (strtolower($semester) === 'ganjil' || $semester === '1') ? "{$tahun}-06-30" : "{$tahun}-12-31";
        $titleDate = str_replace('-', '', $exportDate);

        $sanitize = function ($text) {
            $text = str_replace(["\r", "\n"], ' ', $text);
            $text = str_replace(['"', '%', '|'], ['"', '"', "'", "'", ' persen ', ' '], $text);
            $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
            return trim(preg_replace('/\s+/', ' ', $text));
        };

        $data_kpmr = $this->nilaiModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $sandibpr = $data_infobpr[0]['sandibpr'] ?? '';
            $kodejenis = $data_infobpr[0]['kodejenis'] ?? '';
        }

        $kodeMap = [16 => '1310', 17 => '1311', 18 => '1312', 19 => '1313', 20 => '1314', 21 => '1315', 22 => '1316', 23 => '1320', 24 => '1321', 25 => '1322', 26 => '1323', 27 => '1330', 28 => '1331', 29 => '1332', 30 => '1340', 31 => '1341', 32 => '1342', 33 => '1350'];

        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0102|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";

        usort($data_kpmr, fn($a, $b) => ($a['faktor1id'] ?? 0) <=> ($b['faktor1id'] ?? 0));

        foreach ($data_kpmr as $row) {
            $fid = $row['faktor1id'] ?? null;
            if (!isset($kodeMap[$fid]))
                continue;
            $output .= "D01|{$kodeMap[$fid]}|{$sanitize($row['penilaiankredit'])}|{$sanitize($row['keterangan'])}\r\n";
        }

        $filename = "TKSBPRKS-0102-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }

}
