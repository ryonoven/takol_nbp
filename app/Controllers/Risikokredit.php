<?php

namespace App\Controllers;

use App\Libraries\ProfilRisikoController;
use App\Libraries\RisikokreditExports;
use App\Models\M_kalkulatorkredit;
use App\Models\M_nilairisikokredit;

/**
 * Penilaian Risiko Kredit Inheren (tabel risikokredit, faktor 1–14) + kalkulator rasio (kalkulator_kredit).
 * Logika halaman di App\Libraries\ProfilRisikoController; endpoint ekspor PDF/TXT di trait RisikokreditExports.
 */
class Risikokredit extends ProfilRisikoController
{
    use RisikokreditExports;

    protected const TABLE = 'risikokredit';
    protected const COL_NILAI = 'penilaiankredit';
    protected const COL_RASIO = 'rasiokredit';
    protected const SUBKATEGORI = 'KREDITINHEREN';
    protected const ROUTE = 'Risikokredit';
    protected const TITLE = 'Risiko Kredit Inheren';
    protected const RISK = 'Risiko Kredit';
    protected const ICON = 'fa-hand-holding-usd';
    protected const TOTAL_ID = 13;
    protected const PREV_ID = 14;
    protected const OPTIONAL = [12];
    protected const NAV = ['Risikokredit' => 'Inheren', 'Risikokreditkpmr' => 'KPMR'];
    protected const VIEW_EXTRA = 'Laporansemester/Profilrisiko/_kalkulatorkredit';

    /** Input kalkulator per jenis: kolom => label. */
    public const KAL_FIELDS = [
        'komposisi' => [
            'aba' => 'Antar Bank Aktiva (ABA)',
            'kydbank' => 'KYD kepada Bank (gross)',
            'kydpihak3' => 'KYD kepada Pihak Ketiga Bukan Bank (gross)',
            'totalaset' => 'Total Aset',
            'total25debitur' => 'Baki Debet 25 Debitur Terbesar',
            'perdagangan' => 'Sektor Perdagangan',
            'jasa' => 'Sektor Jasa',
            'konsumsirumah' => 'Sektor Konsumsi Rumah Tangga',
        ],
        'kualitas' => [
            'abanpl' => 'ABA Non Performing',
            'kydnpl3' => 'KYD Kolektibilitas 3 (Kurang Lancar)',
            'kydnpl4' => 'KYD Kolektibilitas 4 (Diragukan)',
            'kydnpl5' => 'KYD Kolektibilitas 5 (Macet)',
            'kreditbermasalah' => 'Kredit Bermasalah Neto',
            'kreditdpk2' => 'Kredit Kolektibilitas 2 (DPK)',
            'kreditrestruktur1' => 'Kredit Restrukturisasi Kolektibilitas 1',
        ],
    ];
    private const KAL_MAX = 9_000_000_000_000_000;

    protected $nilaiModel;
    protected $kalkulatorModel;

    public function __construct()
    {
        parent::__construct();
        $this->nilaiModel = new M_nilairisikokredit();
        $this->kalkulatorModel = new M_kalkulatorkredit();
    }

    protected function structure(): array
    {
        return [
            ['id' => 1, 'title' => 'Komposisi Portofolio Aset dan Tingkat Konsentrasi Kredit', 'children' => [
                ['id' => 2, 'title' => 'Rasio aset produktif terhadap total aset'],
                ['id' => 3, 'title' => 'Rasio kredit yang diberikan terhadap total aset produktif'],
                ['id' => 4, 'title' => 'Rasio 25 debitur terbesar terhadap total kredit yang diberikan'],
                ['id' => 5, 'title' => 'Rasio kredit per sektor ekonomi terhadap total kredit yang diberikan'],
            ]],
            ['id' => 6, 'title' => 'Kualitas Aset', 'children' => [
                ['id' => 7, 'title' => 'Rasio aset produktif bermasalah terhadap total aset produktif'],
                ['id' => 8, 'title' => 'Rasio kredit bermasalah neto terhadap total kredit yang diberikan'],
                ['id' => 9, 'title' => 'Rasio kredit kualitas rendah terhadap total kredit yang diberikan'],
            ]],
            ['id' => 10, 'title' => 'Strategi Penyediaan Dana'],
            ['id' => 11, 'title' => 'Faktor Eksternal'],
            ['id' => 12, 'title' => 'Faktor Lainnya'],
        ];
    }

    protected function penilaianConfig(): array
    {
        $tingkat = static fn(string $s) => [1 => "$s berada pada tingkat risiko yang Sangat Rendah", 2 => "$s berada pada tingkat risiko yang Rendah",
            3 => "$s berada pada tingkat risiko yang Sedang", 4 => "$s berada pada tingkat risiko yang Tinggi", 5 => "$s berada pada tingkat risiko yang Sangat Tinggi"];
        $kualitas = static fn(string $t) => [
            1 => "<= $t",
            2 => 'Rasio di atas ambang batas peringkat 1, dengan kondisi pemberian kredit memiliki kualitas yang baik, antara lain ditunjukkan dengan: <br>• Kredit restrukturisasi tidak signifikan <br>• Penurunan kualitas kredit dari Performing Loan ke Non Performing Loan tidak signifikan <br>• Sektor ekonomi berisiko tinggi tidak signifikan <br>• Jumlah kredit lancar yang menunggak >7 hari tidak signifikan <br>• Komponen aset produktif bermasalah sebagian besar merupakan penempatan pada bank lain',
            3 => 'Rasio di atas ambang batas peringkat 1, dengan kondisi pemberian kredit memiliki kualitas yang cukup baik, namun terdapat potensi penurunan, antara lain ditunjukkan dengan: <br>• Kredit restrukturisasi cukup signifikan <br>• Penurunan kualitas kredit dari Performing Loan ke Non Performing Loan cukup signifikan <br>• Sektor ekonomi berisiko tinggi cukup signifikan <br>• Jumlah kredit lancar yang menunggak >7 hari cukup <br>• Komponen aset produktif bermasalah sebagian besar merupakan penempatan pada bank lain signifikan',
            4 => 'Rasio di atas ambang batas peringkat 1, dengan kondisi pemberian kredit memiliki kualitas yang kurang baik, antara lain ditunjukkan dengan: <br>• Kredit restrukturisasi signifikan <br>• Penurunan kualitas kredit dari Performing Loan ke Non Performing Loan signifikan <br>• Sektor ekonomi berisiko tinggi signifikan <br>• Jumlah kredit lancar yang menunggak >7 hari signifikan <br>• Komponen aset produktif bermasalah sebagian besar merupakan kredit',
            5 => 'Rasio di atas ambang batas peringkat 1, dengan kondisi pemberian kredit memiliki kualitas yang buruk, antara lain ditunjukkan dengan: <br>• Kredit restrukturisasi sangat signifikan <br>• Penurunan kualitas kredit dari Performing Loan ke Non Performing Loan sangat signifikan <br>• Sektor ekonomi berisiko tinggi sangat signifikan <br>• Jumlah kredit lancar yang menunggak >7 hari sangat signifikan <br>• Komponen aset produktif bermasalah sebagian besar merupakan kredit',
        ];
        return [
            1 => ['descriptions' => $tingkat('Parameter Komposisi Portofolio Aset dan Tingkat Konsentrasi Kredit'), 'catatan' => '-'],
            2 => ['threshold' => '95%', 'descriptions' => [1 => '<= 95%', 2 => '>95%, Komponen aset produktif memiliki eksposur Risiko kredit rendah', 3 => '>95%, Komponen aset produktif memiliki eksposur Risiko kredit moderat', 4 => '>95%, Komponen aset produktif memiliki eksposur Risiko kredit tinggi', 5 => '>95%, Komponen aset produktif memiliki eksposur Risiko kredit sangat tinggi'], 'catatan' => 'BPR dengan rasio <= 95% dimungkinkan mendapat peringkat lebih buruk dari 1 antara lain dalam hal BPR memiliki aset produktif dengan eksposur Risiko kredit yang lebih tinggi'],
            3 => ['threshold' => '75%', 'descriptions' => [1 => '<= 75%', 2 => '>75%, Skema kredit sebagian besar atau seluruhnya sederhana, dan jenis kredit tidak beragam', 3 => '>75%, Skema kredit sebagian besar atau seluruhnya sederhana, dan jenis kredit beragam', 4 => '>75%, Skema kredit sebagian besar atau seluruhnya kompleks, dan jenis kredit tidak beragam', 5 => '>75%, Skema kredit sebagian besar atau seluruhnya kompleks, dan jenis kredit beragam'], 'catatan' => 'BPR dengan rasio <=75% dimungkinkan mendapat peringkat lebih buruk dari 1, dalam hal portofolio kredit'],
            4 => ['threshold' => '20%', 'descriptions' => [1 => '<= 20%', 2 => '≥ 20%, Target pasar tidak berubah selama jangka waktu yang sangat lama', 3 => '≥ 20%, Target pasar tidak berubah selama jangka waktu yang lama', 4 => '≥ 20%, Target pasar tidak berubah selama jangka waktu yang cukup lama', 5 => '≥ 20%, Target pasar tidak berubah selama jangka waktu yang singkat'], 'catatan' => 'Konsentrasi kredit pada 25 debitur terbesar'],
            5 => ['threshold' => '85%', 'descriptions' => [1 => '<= 85%', 2 => '≥ 85%, Kredit yang berasal dari 3 (tiga) sektor ekonomi terbesar tidak berubah selama jangka waktu yang sangat lama', 3 => '≥ 85%, Kredit yang berasal dari 3 (tiga) sektor ekonomi terbesar tidak berubah selama jangka waktu yang lama', 4 => '≥ 85%, Kredit yang berasal dari 3 (tiga) sektor ekonomi terbesar tidak berubah selama jangka waktu yang cukup lama', 5 => '≥ 85%, Kredit yang berasal dari 3 (tiga) sektor ekonomi terbesar tidak berubah selama jangka waktu yang singkat'], 'catatan' => 'Parameter rasio kredit per sektor ekonomi terhadap total kredit yang diberikan'],
            6 => ['descriptions' => $tingkat('Parameter Kualitas aset'), 'catatan' => '-'],
            7 => ['threshold' => '7%', 'descriptions' => $kualitas('7%'), 'catatan' => 'Rasio aset produktif bermasalah terhadap total aset produktif'],
            8 => ['threshold' => '5%', 'descriptions' => $kualitas('5%'), 'catatan' => 'Kredit bermasalah neto / total kredit yang diberikan'],
            9 => ['threshold' => '7%', 'descriptions' => $kualitas('7%'), 'catatan' => 'Rasio aset produktif bermasalah terhadap total aset produktif'],
            10 => ['descriptions' => [1 => '• Pertumbuhan kredit di atas rata-rata industri, dan <br>• Seluruhnya disalurkan kepada sektor ekonomi yang dikuasai.', 2 => '• Pertumbuhan kredit di atas rata-rata industri, dan <br>• Sebagian besar disalurkan kepada sektor ekonomi yang dikuasai.', 3 => '• Pertumbuhan kredit di atas atau sama dengan rata-rata industri, dan Sebagian kecil atau tidak sama sekali disalurkan kepada sektor ekonomi yang dikuasai atau <br>• Pertumbuhan kredit di bawah rata-rata industri, dan <br>• Seluruhnya disalurkan kepada sektor ekonomi yang dikuasai.', 4 => '• Pertumbuhan kredit di bawah rata-rata industri, dan <br>• Sebagian besar disalurkan kepada sektor ekonomi yang dikuasai.', 5 => '• Pertumbuhan kredit di bawah rata-rata industri, dan <br>• Sebagian kecil atau tidak sama sekali disalurkan kepada sektor ekonomi yang dikuasai.'], 'catatan' => 'Penilaian berdasarkan kualitas pelaksanaan tata kelola'],
            11 => ['descriptions' => [1 => 'Terdapat perubahan faktor eksternal, namun tidak berdampak pada kemampuan debitur untuk membayar kembali pinjaman.', 2 => 'Terdapat perubahan faktor eksternal, yang berdampak pada kemampuan debitur untuk membayar kembali pinjaman sehingga terjadi tunggakan pinjaman namun tidak menyebabkan penurunan kualitas kredit debitur.', 3 => 'Terdapat perubahan faktor eksternal, yang berdampak pada kinerja bisnis debitur sehingga menyebabkan terjadi tunggakan pinjaman tetapi tidak menurunkan kualitas kredit debitur menjadi NPL.', 4 => 'Terdapat perubahan faktor eksternal, yang menyebabkan penurunan kualitas kredit debitur hingga menjadi NPL.', 5 => 'Terdapat perubahan faktor eksternal, yang menyebabkan kebangkrutan debitur.'], 'catatan' => 'Penilaian berdasarkan kualitas pelaksanaan tata kelola'],
            12 => ['descriptions' => $tingkat('Parameter Penilaian Risiko Kredit Inheren Lainnya'), 'catatan' => 'Pertumbuhan kredit year-on-year'],
        ];
    }

    /** Dipakai export lama (strukturDataNilai dsb.). */
    private function getPenilaianConfig(): array
    {
        return $this->penilaianConfig();
    }

    // ───────────────────────── kalkulator rasio ─────────────────────────

    private function kalRow(?int $periodeId = null): ?array
    {
        return $this->db->table('kalkulator_kredit')
            ->where(['kodebpr' => $this->userKodebpr, 'periode_id' => $periodeId ?? $this->periodeId])
            ->orderBy('id', 'DESC')->get(1)->getRowArray() ?: null;
    }

    /** Hitung kolom turunan + rasio dari input (rumus sama dengan kalkulator sebelumnya). */
    public static function kalCompute(string $type, array $in, array $base = []): array
    {
        $pct = static fn(float $a, float $b) => $b > 0 ? round($a / $b * 100, 2) : 0.0;
        if ($type === 'komposisi') {
            $kydgross = $in['kydbank'] + $in['kydpihak3'];
            $ap = $in['aba'] + $kydgross;
            $sektor = $in['perdagangan'] + $in['jasa'] + $in['konsumsirumah'];
            return [
                'derived' => ['kydgross' => $kydgross, 'asetproduktif' => $ap, 'sektorekonomi' => $sektor],
                'rasio' => [
                    2 => ['rasioasetproduktif', $pct($ap, $in['totalaset'])],
                    3 => ['rasiokreditdiberikan', $pct($kydgross, $ap)],
                    4 => ['rasio25debitur', $pct($in['total25debitur'], $kydgross)],
                    5 => ['rasioekonomi', $pct($sektor, $kydgross)],
                ],
            ];
        }
        $ap = (float) ($base['asetproduktif'] ?? 0);
        $kydgross = (float) ($base['kydgross'] ?? 0);
        $kolek = $in['kydnpl3'] + $in['kydnpl4'] + $in['kydnpl5'];
        $apb = $in['abanpl'] + $kolek;
        return [
            'derived' => ['kydkoleknpl' => $kolek, 'asetproduktifbermasalah' => $apb],
            'rasio' => [
                7 => ['rasioasetproduktifbermasalah', $pct($apb, $ap)],
                8 => ['rasiokreditbermasalah', $pct($in['kreditbermasalah'], $kydgross)],
                9 => ['rasiokreditkualitasrendah', $pct($in['kreditdpk2'] + $kolek + $in['kreditrestruktur1'], $kydgross)],
            ],
        ];
    }

    /** Simpan kalkulator (type=komposisi|kualitas) dan masukkan rasio ke kertas kerja. */
    public function kalkulator()
    {
        if ($r = $this->guardPost()) {
            return $r;
        }
        $type = (string) $this->request->getPost('type');
        if (!isset(self::KAL_FIELDS[$type])) {
            return $this->json(400, ['ok' => false, 'message' => 'Jenis kalkulator tidak valid.']);
        }
        $in = $errors = [];
        foreach (self::KAL_FIELDS[$type] as $f => $label) {
            $raw = preg_replace('/\D/', '', (string) $this->request->getPost($f));
            if ($raw === '') {
                $errors[$f] = 'Wajib diisi (isi 0 bila tidak ada).';
                continue;
            }
            if (strlen($raw) > 16 || (float) $raw > self::KAL_MAX) {
                $errors[$f] = 'Nilai terlalu besar.';
                continue;
            }
            $in[$f] = (int) $raw;
        }
        if (!$errors && $type === 'komposisi' && $in['totalaset'] <= 0) {
            $errors['totalaset'] = 'Total aset harus lebih dari 0.';
        }
        $kal = $this->kalRow();
        if (!$errors && $type === 'kualitas' && (empty($kal['asetproduktif']) || empty($kal['kydgross']))) {
            return $this->json(422, ['ok' => false, 'message' => 'Simpan Kalkulator Komposisi Portofolio terlebih dahulu (aset produktif & KYD gross dibutuhkan).']);
        }
        if ($errors) {
            return $this->json(422, ['ok' => false, 'message' => 'Periksa kembali isian kalkulator.', 'errors' => $errors]);
        }

        $res = self::kalCompute($type, $in, $kal ?? []);
        $data = $in + $res['derived'];
        foreach ($res['rasio'] as [$col, $v]) {
            $data[$col] = $v;
        }
        $now = date('Y-m-d H:i:s');
        $data['user_id'] = $this->userId;
        $data['updated_at'] = $now;

        $rows = $this->rows();
        $changed = [];
        $this->db->transStart();
        if ($kal) {
            $this->db->table('kalkulator_kredit')->where('id', $kal['id'])->update($data);
        } else {
            $this->db->table('kalkulator_kredit')->insert($data + $this->kalDefaults() + [
                'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId, 'created_at' => $now]);
        }
        foreach ($res['rasio'] as $fid => [, $v]) {
            $old = $rows[$fid][self::COL_RASIO] ?? null;
            if ($old === null || abs((float) $old - $v) >= 0.005) {
                $this->upsert($fid, [self::COL_RASIO => $v], $rows);
                $changed[] = $fid;
            }
        }
        if ($changed) {
            $this->resetApproval($changed);
        }
        $this->db->transComplete();

        $n = count($res['rasio']);
        return $this->json(200, ['ok' => true, 'reload' => true, 'message' => 'Kalkulator tersimpan — ' . $n . ' rasio dimasukkan ke kertas kerja'
            . ($changed ? ' (' . count($changed) . ' berubah)' : ' (tidak ada perubahan rasio)') . '.']);
    }

    /** Semua kolom angka kalkulator_kredit (NOT NULL tanpa default) = 0. */
    private function kalDefaults(): array
    {
        $cols = array_merge(array_keys(self::KAL_FIELDS['komposisi']), array_keys(self::KAL_FIELDS['kualitas']), [
            'kydgross', 'asetproduktif', 'sektorekonomi', 'rasioasetproduktif', 'rasiokreditdiberikan', 'rasio25debitur', 'rasioekonomi',
            'kydkoleknpl', 'asetproduktifbermasalah', 'rasioasetproduktifbermasalah', 'rasiokreditbermasalah', 'rasiokreditkualitasrendah']);
        return array_fill_keys($cols, 0);
    }

    protected function afterSync(int $prevPeriodeId, bool $force): string
    {
        $prev = $this->kalRow($prevPeriodeId);
        $cur = $this->kalRow();
        if (!$prev || ($cur && !$force)) {
            return '';
        }
        $data = array_intersect_key($prev, $this->kalDefaults());
        $data['user_id'] = $this->userId;
        $data['updated_at'] = date('Y-m-d H:i:s');
        if ($cur) {
            $this->db->table('kalkulator_kredit')->where('id', $cur['id'])->update($data);
        } else {
            $this->db->table('kalkulator_kredit')->insert($data + ['kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId,
                'created_at' => $data['updated_at']]);
        }
        return 'termasuk data kalkulator';
    }

    protected function extraData(array $rows): array
    {
        return ['kal' => $this->canEdit() ? $this->kalRow() : null, 'kalFields' => self::KAL_FIELDS];
    }
}
