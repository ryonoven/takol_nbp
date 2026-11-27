<?php

namespace App\Controllers;

use App\Libraries\ProfilRisikoController;
use App\Models\M_reputasiinheren;

/**
 * Penilaian Risiko Reputasi Inheren (tabel reputasiinheren, faktor 138–149).
 * Logika halaman di App\Libraries\ProfilRisikoController; ekspor TXT dipakai Showprofilresiko & Showsemesteran.
 */
class Reputasiinheren extends ProfilRisikoController
{
    protected const TABLE = 'reputasiinheren';
    protected const COL_NILAI = 'penilaiankredit';
    protected const SUBKATEGORI = 'REPUTASIINHEREN';
    protected const ROUTE = 'Reputasiinheren';
    protected const TITLE = 'Risiko Reputasi Inheren';
    protected const RISK = 'Risiko Reputasi';
    protected const ICON = 'fa-bullhorn';
    protected const TOTAL_ID = 148;
    protected const PREV_ID = 149;
    protected const OPTIONAL = [147];
    protected const NAV = ['Reputasiinheren' => 'Inheren', 'Reputasikpmr' => 'KPMR'];

    private const KODE_MAP = [138 => '5210', 139 => '5211', 140 => '5212', 141 => '5220', 142 => '5221', 143 => '5222',
        144 => '5230', 145 => '5231', 146 => '5232', 147 => '5299', 148 => '5292'];

    protected $nilaiModel;

    public function __construct()
    {
        parent::__construct();
        $this->nilaiModel = new M_reputasiinheren();
    }

    /**
     * Struktur sesuai kode pelaporan (52x0 = pilar, 52x1/52x2 = parameter) dan deskripsi peringkat.
     * (Versi lama memakai judul Risiko Operasional untuk pilar 1 dan menjadikan 142 sebagai pilar.)
     */
    protected function structure(): array
    {
        return [
            ['id' => 138, 'title' => 'Pengaruh reputasi dari pihak yang berasosiasi dengan BPR', 'children' => [
                ['id' => 139, 'title' => 'Pemberitaan negatif mengenai BPR, termasuk anggota Direksi dan Dewan Komisaris, pemegang saham, dan perusahaan terkait BPR'],
                ['id' => 140, 'title' => 'Kejadian reputasi'],
            ]],
            ['id' => 141, 'title' => 'Frekuensi dan signifikansi pengaduan nasabah', 'children' => [
                ['id' => 142, 'title' => 'Administrasi dan tindak lanjut pengaduan nasabah'],
                ['id' => 143, 'title' => 'Signifikansi dan materialitas pengaduan nasabah'],
            ]],
            ['id' => 144, 'title' => 'Pelanggaran etika bisnis', 'children' => [
                ['id' => 145, 'title' => 'Transparansi informasi keuangan'],
                ['id' => 146, 'title' => 'Transparansi produk dan layanan BPR'],
            ]],
            ['id' => 147, 'title' => 'Lainnya'],
        ];
    }

    protected function penilaianConfig(): array
    {
        // catatan lama generik ("Peniliaian …") → tidak ditampilkan
        return array_map(static fn($c) => array_diff_key($c, ['catatan' => 1]), [
            138 => ['descriptions' => [
                1 => 'Parameter Pengaruh reputasi pihak yang berasosiasi dengan BPR berada pada tingkat risiko yang Sangat Rendah',
                2 => 'Parameter Pengaruh reputasi pihak yang berasosiasi dengan BPR berada pada tingkat risiko yang Rendah',
                3 => 'Parameter Pengaruh reputasi pihak yang berasosiasi dengan BPR berada pada tingkat risiko yang Sedang',
                4 => 'Parameter Pengaruh reputasi pihak yang berasosiasi dengan BPR berada pada tingkat risiko yang Tinggi',
                5 => 'Parameter Pengaruh reputasi pihak yang berasosiasi dengan BPR berada pada tingkat risiko yang Sangat Tinggi',
            ], 'catatan' => 'Peniliaian Risiko Reputasi Inheren'],
            139 => ['descriptions' => [
                1 => 'Tidak terdapat pemberitaan negatif mengenai BPR termasuk anggota Direksi dan Dewan Komisaris, pemegang saham, dan perusahaan terkait BPR, di media massa (cetak dan elektronik) dan media lainnya yang dapat diakses oleh masyarakat',
                2 => 'Terdapat pemberitaan negatif mengenai BPR termasuk anggota Direksi dan Dewan Komisaris, pemegang saham, dan perusahaan terkait BPR, di media massa (cetak dan elektronik) dan media lainnya yang dapat diakses oleh masyarakat, namun skala pengaruhnya tidak material dan dapat dimitigasi dengan baik.',
                3 => 'Terdapat pemberitaan negatif mengenai BPR termasuk anggota Direksi dan Dewan Komisaris, pemegang saham, dan perusahaan terkait BPR, di media massa (cetak dan elektronik) dan media lainnya yang dapat diakses oleh masyarakat, dengan skala pengaruh cukup material terhadap kinerja BPR namun masih dapat dikendalikan.',
                4 => 'Terdapat pemberitaan negatif mengenai BPR termasuk anggota Direksi dan Dewan Komisaris, pemegang saham, dan perusahaan terkait BPR, di media massa (cetak dan elektronik) dan media lainnya yang dapat diakses oleh masyarakat, dengan skala pengaruh yang material terhadap kinerja BPR dan memerlukan perhatian khusus.',
                5 => 'Terdapat pemberitaan negatif mengenai BPR termasuk anggota Direksi dan Dewan Komisaris, pemegang saham, dan perusahaan terkait BPR, di media massa (cetak dan elektronik) dan media lainnya yang dapat diakses oleh masyarakat, dengan skala pengaruh yang sangat material terhadap kinerja BPR, sehingga memerlukan tindak lanjut dengan segera.',
            ], 'catatan' => 'Peniliaian Risiko Reputasi Inheren'],
            140 => ['descriptions' => [
                1 => 'Tidak terdapat kejadian reputasi.',
                2 => '• Terdapat kejadian reputasi dengan frekuensi yang rendah; namun<br>• Tidak berpengaruh pada reputasi BPR',
                3 => '• Terdapat kejadian reputasi dengan frekuensi yang rendah; dan<br>• Berpengaruh cukup material pada reputasi BPR relatif terhadap ukuran dan skala usaha BPR. atau<br>• Terdapat kejadian reputasi dengan frekuensi cukup tinggi; namun<br>• tidak berpengaruh pada reputasi BPR.',
                4 => '• Terdapat kejadian reputasi dengan frekuensi yang cukup tinggi; dan<br>• Berpengaruh material pada reputasi BPR relatif terhadap ukuran dan skala usaha BPR. atau<br>• Terdapat kejadian reputasi dengan frekuensi yang sangat tinggi; namun<br>• Tidak seluruhnya berpengaruh material pada reputasi BPR relatif terhadap ukuran dan skala usaha BPR.',
                5 => '• Terdapat kejadian reputasi dengan frekuensi yang sangat tinggi; dan<br>• Berpengaruh sangat material pada reputasi BPR relatif terhadap ukuran dan skala usaha BPR.',
            ], 'catatan' => 'Peniliaian Risiko Reputasi Inheren'],
            141 => ['descriptions' => [
                1 => 'Parameter Frekuensi dan signifikansi pengaduan nasabah berada pada tingkat risiko yang Sangat Rendah',
                2 => 'Parameter Frekuensi dan signifikansi pengaduan nasabah berada pada tingkat risiko yang Rendah',
                3 => 'Parameter Frekuensi dan signifikansi pengaduan nasabah berada pada tingkat risiko yang Sedang',
                4 => 'Parameter Frekuensi dan signifikansi pengaduan nasabah berada pada tingkat risiko yang Tinggi',
                5 => 'Parameter Frekuensi dan signifikansi pengaduan nasabah berada pada tingkat risiko yang Sangat Tinggi',
            ], 'catatan' => 'Peniliaian Risiko Reputasi Inheren'],
            142 => ['descriptions' => [
                1 => '• Pengaduan nasabah diadministrasikan dengan tertib dan informatif (ada, lengkap, rutin); dan/atau<br>• Seluruh pengaduan telah diselesaikan',
                2 => '• Pengaduan nasabah diadministrasikan dengan cukup tertib dan informatif (sebagian besar ada, sebagian besar lengkap, sebagian besar rutin); dan/atau<br>• Sebagian besar pengaduan telah diselesaikan.',
                3 => '• Pengaduan nasabah diadministrasikan dengan cukup tertib dan informatif (sebagian besar ada, sebagian besar lengkap, sebagian besar rutin); dan/atau<br>• Sebagian kecil pengaduan telah diselesaikan.',
                4 => '• Pengaduan nasabah diadministrasikan dengan kurang tertib dan informatif (sebagian kecil ada, sebagian kecil lengkap, sebagian kecil rutin); dan/atau<br>• Sebagian kecil pengaduan telah diselesaikan.',
                5 => 'Tidak terdapat administrasi mengenai pengaduan nasabah dan/atau seluruhnya tidak diselesaikan.',
            ], 'catatan' => 'Penilaian parameter risiko reputasi inheren'],
            143 => ['descriptions' => [
                1 => 'Frekuensi pengaduan nasabah sangat minimal dan sangat tidak material.',
                2 => 'Frekuensi pengaduan nasabah minimal dan tidak material.',
                3 => 'Frekuensi pengaduan nasabah cukup tinggi dan cukup material.',
                4 => 'Frekuensi pengaduan nasabah tinggi dan material',
                5 => 'Frekuensi pengaduan nasabah sangat tinggi serta sangat material dan/atau disebabkan penyimpangan ketentuan perbankan.',
            ], 'catatan' => 'Penilaian parameter risiko reputasi inheren'],
            144 => ['descriptions' => [
                1 => 'Parameter Pelanggaran etika bisnis berada pada tingkat risiko yang Sangat Rendah',
                2 => 'Parameter Pelanggaran etika bisnis berada pada tingkat risiko yang Rendah',
                3 => 'Parameter Pelanggaran etika bisnis berada pada tingkat risiko yang Sedang',
                4 => 'Parameter Pelanggaran etika bisnis berada pada tingkat risiko yang Tinggi',
                5 => 'Parameter Pelanggaran etika bisnis berada pada tingkat risiko yang Sangat Tinggi',
            ], 'catatan' => 'Peniliaian Risiko Reputasi Inheren'],
            145 => ['descriptions' => [
                1 => 'Laporan dan informasi keuangan yang disampaikan BPR kepada seluruh pihak yang memiliki kepentingan dengan BPR lengkap, akurat, kini, dan utuh sesuai dengan ketentuan yang berlaku.',
                2 => 'Laporan dan informasi keuangan yang disampaikan BPR kepada seluruh pihak yang memiliki kepentingan dengan BPR lengkap, akurat, kini, namun tidak utuh.',
                3 => 'Laporan dan informasi keuangan yang disampaikan BPR kepada seluruh pihak yang memiliki kepentingan dengan BPR kurang lengkap dan masih terdapat informasi yang disampaikan tidak sesuai dengan ketentuan yang berlaku, namun tidak mengakibatkan penilaian yang tidak sesuai dengan kondisi keuangan yang sebenarnya.',
                4 => 'Laporan dan informasi keuangan yang disampaikan BPR kepada seluruh pihak yang memiliki kepentingan dengan BPR kurang lengkap dan masih terdapat informasi yang disampaikan tidak sesuai dengan ketentuan yang berlaku, serta mengakibatkan penilaian yang tidak sesuai dengan kondisi keuangan yang sebenarnya.',
                5 => 'BPR tidak menyampaikan informasi dan laporan keuangan sesuai dengan ketentuan yang berlaku, dan mengakibatkan tidak diketahuinya kondisi keuangan BPR yang sebenarnya.',
            ], 'catatan' => 'Penilaian parameter risiko reputasi inheren'],
            146 => ['descriptions' => [
                1 => 'Produk dan layanan BPR memiliki skema sederhana, serta tidak membutuhkan pemahaman khusus nasabah atau mitra bisnis BPR, dan BPR memberikan informasi terkait spesifikasi produk dan layanan BPR kepada nasabah atau mitra bisnis BPR secara jelas dan lengkap.',
                2 => 'Produk dan layanan BPR memiliki skema kompleks, serta membutuhkan pemahaman khusus nasabah atau mitra bisnis BPR, dan BPR memberikan informasi terkait spesifikasi produk dan layanan BPR kepada nasabah atau mitra bisnis BPR secara jelas dan lengkap.',
                3 => 'Terdapat produk dan layanan BPR yang memiliki skema kompleks, serta membutuhkan pemahaman khusus nasabah atau mitra bisnis BPR, namun BPR belum sepenuhnya memberikan informasi terkait spesifikasi produk dan layanan BPR kepada nasabah atau mitra bisnis BPR secara jelas dan lengkap.',
                4 => 'Terdapat produk dan layanan BPR yang memiliki skema kompleks, serta membutuhkan pemahaman khusus nasabah atau mitra bisnis BPR, namun BPR tidak memberikan informasi terkait spesifikasi produk dan layanan BPR kepada nasabah atau mitra bisnis BPR secara jelas dan lengkap.',
                5 => 'Terdapat produk dan layanan BPR yang memiliki skema kompleks, serta membutuhkan pemahaman khusus nasabah atau mitra bisnis BPR, namun BPR memberikan informasi yang tidak benar kepada nasabah atau mitra bisnis BPR terkait spesifikasi produk dan layanan BPR.',
            ], 'catatan' => 'Penilaian parameter risiko reputasi inheren'],
            147 => ['descriptions' => [
                1 => 'Parameter Penilaian Risiko Reputasi Inheren Lainnya berada pada tingkat risiko yang Sangat Rendah',
                2 => 'Parameter Penilaian Risiko Reputasi Inheren Lainnya berada pada tingkat risiko yang Rendah',
                3 => 'Parameter Penilaian Risiko Reputasi Inheren Lainnya berada pada tingkat risiko yang Sedang',
                4 => 'Parameter Penilaian Risiko Reputasi Inheren Lainnya berada pada tingkat risiko yang Tinggi',
                5 => 'Parameter Penilaian Risiko Reputasi Inheren Lainnya berada pada tingkat risiko yang Sangat Tinggi',
            ], 'catatan' => 'Peniliaian Risiko Reputasi Inheren'],
        ]);
    }

    private function sanitizeTxt(string $text): string
    {
        $text = str_replace(["\r", "\n"], ' ', $text);
        $text = str_replace(['“', '”', '‘', '’', '%', '|'], ['"', '"', "'", "'", ' persen ', ' '], $text);
        $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    public function exporttxtrisikoreputasiinheren()
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

        $p = $this->periode;
        $tahun = $p['tahun'] ?? date('Y');
        $semester = $p['semester'] ?? '';
        $exportDate = (strtolower($semester) === 'ganjil' || $semester === '1') ? "{$tahun}-06-30" : "{$tahun}-12-31";
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
        $filename = "TKSBPRKS-0501-{$p['jenispelaporan']}-S-{$titleDate}-{$sandibpr}-01.txt";

        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0501|{$p['modalinti']}|{$p['kategori']}|{$p['nomorsurat']}|{$p['jeniskoreksi']}\r\n";

        // Format lama dipertahankan: tanpa data 138–146 → header saja; baris mengikuti urutan data; 5299 selalu terakhir.
        $hasMainData = (bool) array_intersect_key($indexedData, array_flip(range(138, 146)));
        if ($hasMainData) {
            foreach ($indexedData as $faktorId => $row) {
                if (!isset(self::KODE_MAP[$faktorId]) || $faktorId == 147) {
                    continue;
                }
                $output .= 'D01|' . self::KODE_MAP[$faktorId] . '|' . $this->sanitizeTxt((string) ($row['penilaiankredit'] ?? ''))
                    . '|' . $this->sanitizeTxt((string) ($row['keterangan'] ?? '-')) . "\r\n";
            }
            $row5299 = $indexedData[147] ?? [];
            $output .= 'D01|5299|' . $this->sanitizeTxt((string) ($row5299['penilaiankredit'] ?? '')) . '|'
                . $this->sanitizeTxt((string) ($row5299['keterangan'] ?? '')) . "\r\n";
        }

        return service('response')
            ->setHeader('Content-Type', 'text/plain')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }
}
