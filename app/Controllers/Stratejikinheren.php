<?php

namespace App\Controllers;

use App\Libraries\ProfilRisikoController;
use App\Models\M_stratejikinheren;

/**
 * Penilaian Risiko Stratejik Inheren (tabel stratejikinheren, faktor 171–180).
 * Logika halaman di App\Libraries\ProfilRisikoController; ekspor TXT dipakai Showprofilresiko & Showsemesteran.
 */
class Stratejikinheren extends ProfilRisikoController
{
    protected const TABLE = 'stratejikinheren';
    protected const COL_NILAI = 'penilaiankredit';
    protected const SUBKATEGORI = 'STRATEJIKINHEREN';
    protected const ROUTE = 'Stratejikinheren';
    protected const TITLE = 'Risiko Stratejik Inheren';
    protected const RISK = 'Risiko Stratejik';
    protected const ICON = 'fa-chess';
    protected const TOTAL_ID = 179;
    protected const PREV_ID = 180;
    protected const OPTIONAL = [178];
    protected const NAV = ['Stratejikinheren' => 'Inheren', 'Stratejikkpmr' => 'KPMR'];

    private const KODE_MAP = [171 => '6210', 172 => '6220', 173 => '6221', 174 => '6222', 175 => '6230', 176 => '6231',
        177 => '6232', 178 => '6299', 179 => '6292'];

    protected $nilaiModel;

    public function __construct()
    {
        parent::__construct();
        $this->nilaiModel = new M_stratejikinheren();
    }

    protected function structure(): array
    {
        return [
            ['id' => 171, 'title' => 'Penetapan strategi bisnis'],
            ['id' => 172, 'title' => 'Penyusunan rencana bisnis', 'children' => [
                ['id' => 173, 'title' => 'Pertimbangan faktor eksternal dan internal dalam menyusun rencana dan model bisnis'],
                ['id' => 174, 'title' => 'Keunggulan kompetitif BPR dan ancaman dari kompetitor'],
            ]],
            ['id' => 175, 'title' => 'Pencapaian target bisnis', 'children' => [
                ['id' => 176, 'title' => 'Perbandingan realisasi dan target indikator keuangan utama sesuai ketentuan rencana bisnis BPR'],
                ['id' => 177, 'title' => 'Rekam jejak (track record) keberhasilan BPR dalam menerapkan keputusan strategis terkait dengan faktor pengembangan produk/jasa baru, perubahan sasaran bisnis, investasi strategis, rencana penggabungan, peleburan, dan pengambilalihan, serta pencapaian target bisnis'],
            ]],
            ['id' => 178, 'title' => 'Lainnya'],
        ];
    }

    protected function penilaianConfig(): array
    {
        // catatan lama generik ("Peniliaian …") → tidak ditampilkan
        return array_map(static fn($c) => array_diff_key($c, ['catatan' => 1]), [
            171 => ['descriptions' => [
                1 => '• Tidak terdapat produk/aktivitas baru yang dimiliki BPR, pilihan strategi sesuai sumber daya yang dimiliki dengan tingkat keberhasilan strategi yang tinggi; dan/atau<br>• BPR melakukan kegiatan usaha dalam pangsa pasar/sektor ekonomi dan nasabah yang telah dikenal/ada sebelumnya, termasuk tidak ada strategi pengembangan jaringan kantor.',
                2 => '• BPR memiliki beberapa strategi baru tetapi masih dalam bisnis utama dan kompetensi BPR (terdapat beberapa produk baru) serta sesuai sumber daya yang dimiliki dengan tingkat keberhasilan strategi yang cukup tinggi; dan/atau<br>• BPR melakukan kegiatan usaha dalam pangsa pasar/sektor ekonomi dan nasabah yang telah dikenal/ada sebelumnya, dengan pangsa pasar yang semakin luas.',
                3 => '• BPR memiliki beberapa strategi baru termasuk adanya produk baru yang tergolong berisiko tinggi antara lain dimiliki memerlukan SDM dengan keahlian khusus dan/atau infrastruktur TI yang lebih kompleks dengan tingkat keberhasilan strategi BPR tergolong moderat; dan/atau<br>• Sebagian besar kegiatan usaha BPR berada dalam pangsa pasar/sektor ekonomi dan nasabah yang telah dikenal/ada sebelumnya, terdapat perluasan pangsa pasar dan nasabah baru namun tanpa melalui strategi pengembangan jaringan kantor.',
                4 => '• Mayoritas strategi BPR beralih kepada strategi baru dengan produk baru yang tergolong berisiko tinggi antara lain memerlukan SDM dengan keahlian khusus dan/atau infrastruktur TI yang lebih kompleks dengan tingkat keberhasilan yang belum dapat dipastikan; dan/atau<br>• Sebagian besar kegiatan usaha BPR berada dalam pangsa pasar/sektor ekonomi dan nasabah baru, termasuk melalui strategi pengembangan jaringan kantor.',
                5 => '• BPR mengubah strategi bisnis untuk memasuki produk baru yang tergolong berisiko tinggi antara lain memerlukan SDM dengan keahlian khusus dan/atau infrastruktur TI yang lebih kompleks yang bukan merupakan bisnis utama dan kompetensi BPR dengan tingkat keberhasilan yang belum dapat dipastikan;<br>• Seluruh kegiatan usaha BPR berada dalam pangsa pasar/sektor ekonomi dan nasabah baru, termasuk melalui strategi pengembangan jaringan kantor; dan/atau<br>• BPR baru beroperasi.',
            ], 'catatan' => 'Penilaian parameter risiko stratejik inheren'],
            172 => ['descriptions' => [
                1 => 'Sangat Rendah',
                2 => 'Rendah',
                3 => 'Sedang',
                4 => 'Tinggi',
                5 => 'Sangat Tinggi',
            ], 'catatan' => 'Penilaian parameter risiko stratejik inheren'],
            173 => ['descriptions' => [
                1 => '• Penyusunan strategi (rencana dan model bisnis) BPR telah mempertimbangkan seluruh faktor yang memengaruhi lingkungan bisnis BPR baik faktor internal maupun faktor eksternal secara komprehensif; dan/atau<br>• Tingkat kecepatan respon BPR terhadap perubahan faktor eksternal tergolong tinggi, dilakukan perubahan rencana bisnis jika dibutuhkan secara tepat waktu.',
                2 => '• Penyusunan strategi (rencana dan model bisnis) BPR telah mempertimbangkan seluruh faktor yang memengaruhi lingkungan bisnis BPR baik faktor internal maupun faktor eksternal, namun terdapat beberapa kelemahan; dan/atau<br>• Tingkat kecepatan respon BPRterhadap perubahan faktor eksternal tergolong sedang, dilakukan perubahan rencana bisnis jika dibutuhkan namun membutuhkan',
                3 => '• Penyusunan strategi (rencana dan model bisnis) BPR telah mempertimbangkan sebagian besar faktor yang memengaruhi lingkungan bisnis BPR baik faktor internal maupun faktor eksternal, namun terdapat beberapa kelemahan; dan/atau<br>• Tingkat kecepatan respon BPR terhadap perubahan faktor eksternal tergolong rendah, dilakukan perubahan rencana bisnis jika dibutuhkan namun membutuhkan waktu cukup lama.',
                4 => '• Penyusunan strategi (rencana dan model bisnis) BPR hanya mempertimbangkan sebagian faktor yang memengaruhi lingkungan bisnis BPR baik faktor internal maupun faktor eksternal, dan terdapat kelemahan yang tergolong sangat signifikan; dan/atau<br>• Tingkat kecepatan respon BPR terhadap perubahan faktor eksternal tergolong sangat rendah, dilakukan perubahan rencana bisnis jika dibutuhkan namun membutuhkan waktu sangat lama.',
                5 => '• Penyusunan strategi (rencana dan model bisnis) BPR belum mempertimbangkan lingkungan bisnis BPR baik faktor internal maupun faktor eksternal; dan/atau<br>• BPR tidak merespon perubahan faktor eksternal yaitu tidak melakukan perubahan rencana bisnis yang dibutuhkan.',
            ], 'catatan' => 'Penilaian parameter risiko stratejik inheren'],
            174 => ['descriptions' => [
                1 => 'BPR memiliki keunggulan kompetitif yang stabil dan tidak terdapat ancaman dari kompetitor.',
                2 => 'BPR memiliki keunggulan kompetitif yang moderat namun terdapat ancaman dari kompetitor yang tidak memengaruhi BPR (contoh: pertumbuhan kredit dan dana pihak ketiga (DPK) masih di atas target).',
                3 => 'BPR memiliki keunggulan kompetitif yang moderat dan terdapat ancaman dari kompetitor yang memengaruhi BPR (contoh: terdapat deviasi pencapaian pertumbuhan kredit dan DPK namun masih tergolong rendah).',
                4 => 'BPR kurang memiliki keunggulan kompetitif, dan/atau terdapat ancaman signifikan dari kompetitor yang berdampak pada kinerja keuangan BPR (contoh: terdapat deviasi pencapaian pertumbuhan kredit dan DPK yang tergolong sedang).',
                5 => 'BPR tidak memiliki keunggulan kompetitif, dan/atau terdapat ancaman sangat signifikan dari kompetitor dan berdampak signifikan pada kinerja keuangan BPR (contoh: terdapat deviasi pencapaian pertumbuhan kredit dan DPK yang tergolong tinggi).',
            ], 'catatan' => 'Penilaian parameter risiko stratejik inheren'],
            175 => ['descriptions' => [
                1 => 'Sangat Rendah',
                2 => 'Rendah',
                3 => 'Sedang',
                4 => 'Tinggi',
                5 => 'Sangat Tinggi',
            ], 'catatan' => 'Penilaian parameter risiko stratejik inheren'],
            176 => ['descriptions' => [
                1 => '• Realisasi di atas target kuantitatif atau terdapat deviasi paling besar 5% dari target; dan<br>• sebagian besar atau seluruh target kualitatif tercapai.',
                2 => '• Deviasi rendah dibanding target kuantitatif; dan<br>• sebagian besar target kualitatif tercapai.',
                3 => '• Deviasi sedang dibanding target kuantitatif; dan<br>• sebagian besar target kualitatif tercapai.',
                4 => '• Deviasi tinggi dibanding dari target kuantitatif; dan<br>• sebagian kecil target kualitatif tercapai.',
                5 => '• Deviasi sangat tinggi dibanding target kuantitatif; dan<br>• sebagian kecil target kualitatif tercapai atau tidak ada target yang tercapai.',
            ], 'catatan' => 'Penilaian parameter risiko stratejik inheren'],
            177 => ['descriptions' => [
                1 => 'Secara historis, BPR memiliki rekam jejak yang sangat baik dalam menerapkan keputusan strategis terkait keempat faktor penilaian rekam jejak',
                2 => 'Secara historis, BPR memiliki rekam jejak yang baik dalam menerapkan keputusan strategis terkait keempat faktor penilaian rekam jejak',
                3 => 'Secara historis, BPR memiliki rekam jejak yang cukup baik dalam menerapkan keputusan strategis terkait keempat faktor penilaian rekam jejak',
                4 => 'Secara historis, BPR memiliki rekam jejak yang kurang baik dalam menerapkan keputusan strategis terkait keempat faktor penilaian rekam jejak',
                5 => 'Secara historis, BPR memiliki rekam jejak yang tidak baik dalam menerapkan keputusan strategis terterkait keempat faktor penilaian rekam jejak',
            ], 'catatan' => 'Penilaian parameter risiko stratejik inheren'],
            178 => ['descriptions' => [
                1 => 'Sangat Rendah',
                2 => 'Rendah',
                3 => 'Sedang',
                4 => 'Tinggi',
                5 => 'Sangat Tinggi',
            ], 'catatan' => 'Penilaian parameter risiko stratejik inheren'],
        ]);
    }

    private function sanitizeTxt(string $text): string
    {
        $text = str_replace(["\r", "\n"], ' ', $text);
        $text = str_replace(['“', '”', '‘', '’', '%', '|'], ['"', '"', "'", "'", ' persen ', ' '], $text);
        $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    public function exporttxtrisikostratejikinheren()
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
        $filename = "TKSBPRKS-0601-{$p['jenispelaporan']}-S-{$titleDate}-{$sandibpr}-01.txt";

        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0601|{$p['modalinti']}|{$p['kategori']}|{$p['nomorsurat']}|{$p['jeniskoreksi']}\r\n";

        // Format lama dipertahankan: tanpa data 171–177 → header saja; baris mengikuti urutan data; 6299 selalu terakhir.
        $hasMainData = (bool) array_intersect_key($indexedData, array_flip(range(171, 177)));
        if ($hasMainData) {
            foreach ($indexedData as $faktorId => $row) {
                if (!isset(self::KODE_MAP[$faktorId]) || $faktorId == 178) {
                    continue;
                }
                $output .= 'D01|' . self::KODE_MAP[$faktorId] . '|' . $this->sanitizeTxt((string) ($row['penilaiankredit'] ?? ''))
                    . '|' . $this->sanitizeTxt((string) ($row['keterangan'] ?? '-')) . "\r\n";
            }
            $row6299 = $indexedData[178] ?? [];
            $output .= 'D01|6299|' . $this->sanitizeTxt((string) ($row6299['penilaiankredit'] ?? '')) . '|'
                . $this->sanitizeTxt((string) ($row6299['keterangan'] ?? '')) . "\r\n";
        }

        return service('response')
            ->setHeader('Content-Type', 'text/plain')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }
}
