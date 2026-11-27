<?php

namespace App\Controllers;

use App\Libraries\ProfilRisikoController;
use App\Models\M_likuiditasinheren;

/**
 * Penilaian Risiko Likuiditas Inheren (tabel likuiditasinheren, faktor 105–116) + kalkulator rasio (kalkulator_likuiditas).
 * Logika halaman di App\Libraries\ProfilRisikoController; ekspor TXT dipakai Showprofilresiko & Showsemesteran.
 */
class Likuiditasinheren extends ProfilRisikoController
{
    protected const TABLE = 'likuiditasinheren';
    protected const COL_NILAI = 'penilaiankredit';
    protected const COL_RASIO = 'rasiokredit';
    protected const SUBKATEGORI = 'LIKUIDITASINHEREN';
    protected const ROUTE = 'Likuiditasinheren';
    protected const TITLE = 'Risiko Likuiditas Inheren';
    protected const RISK = 'Risiko Likuiditas';
    protected const ICON = 'fa-tint';
    protected const TOTAL_ID = 115;
    protected const PREV_ID = 116;
    protected const OPTIONAL = [114];
    protected const NAV = ['Likuiditasinheren' => 'Inheren', 'Likuiditaskpmr' => 'KPMR'];
    protected const VIEW_EXTRA = 'Laporansemester/Profilrisiko/_kalkulatorlikuiditas';

    /** Input kalkulator per kelompok (judul => [kolom => label]); urutan = urutan tampilan. */
    public const KAL_FIELDS = [
        'Aset' => [
            'totalaset' => 'Total Aset',
            'kas' => 'Kas',
            'girobanklain' => 'Giro pada Bank Lain',
            'tabunganbanklain' => 'Tabungan pada Bank Lain',
        ],
        'Kewajiban & DPK' => [
            'kewajibansegera' => 'Kewajiban Segera',
            'tabungandpk' => 'Tabungan DPK',
            'depositodpk' => 'Deposito DPK',
            'kreditkyd' => 'Kredit yang Diberikan',
            'penabung25deposan' => '25 Deposan dan Penabung Terbesar',
        ],
        'Pendanaan' => [
            'totalpendanaan' => 'Total Pendanaan',
            'tabunganabp' => 'Tabungan ABP',
            'depositoabp' => 'Deposito ABP',
            'pinjamanditerima' => 'Pinjaman yang Diterima',
            'dpkdiataslps' => 'DPK di atas Penjaminan LPS',
            'pinjamananmungkinditarik' => 'Pinjaman yang Mungkin Ditarik Sewaktu-waktu',
        ],
    ];
    private const KAL_DERIVED = ['asetlikuid', 'kewajibanlancar', 'totaldpk', 'transaksibpr', 'pendanaannoninti'];
    /** faktor => kolom rasio di kalkulator_likuiditas */
    public const KAL_RASIO = [106 => 'rasioasetlikuidtotalaset', 107 => 'rasioasetlikuidkewajiban', 108 => 'rasiokreditterhadapdpk',
        109 => 'rasio25deposan', 110 => 'rasiononinti'];
    private const KAL_MAX = 9_000_000_000_000_000;

    private const KODE_MAP = [105 => '4210', 106 => '4211', 107 => '4212', 108 => '4213', 109 => '4214', 110 => '4215',
        111 => '4220', 112 => '4221', 113 => '4222', 114 => '4299', 115 => '4292'];

    protected $nilaiModel;

    public function __construct()
    {
        parent::__construct();
        $this->nilaiModel = new M_likuiditasinheren();
    }

    protected function structure(): array
    {
        return [
            ['id' => 105, 'title' => 'Komposisi dan konsentrasi aset dan kewajiban', 'children' => [
                ['id' => 106, 'title' => 'Rasio aset likuid terhadap total aset'],
                ['id' => 107, 'title' => 'Rasio aset likuid terhadap kewajiban lancar'],
                ['id' => 108, 'title' => 'Rasio kredit yang diberikan terhadap total dana pihak ketiga bukan bank (Loan to Deposit Ratio/LDR)'],
                ['id' => 109, 'title' => 'Rasio 25 deposan dan penabung terbesar terhadap total dana pihak ketiga'],
                ['id' => 110, 'title' => 'Rasio pendanaan non inti terhadap total pendanaan'],
            ]],
            ['id' => 111, 'title' => 'Kerentanan pada kebutuhan pendanaan serta akses pada sumber pendanaan', 'children' => [
                ['id' => 112, 'title' => 'Penilaian kebutuhan pendanaan BPR pada situasi normal maupun krisis, dan kemampuan BPR untuk memenuhi kebutuhan pendanaan'],
                ['id' => 113, 'title' => 'Penilaian terhadap seberapa luas atau seberapa besar BPR memiliki komitmen pendanaan yang dapat digunakan jika dibutuhkan'],
            ]],
            ['id' => 114, 'title' => 'Lainnya'],
        ];
    }

    protected function penilaianConfig(): array
    {
        // threshold "≥ x%" = makin tinggi makin baik (aset likuid); lainnya ≤ x%. Catatan lama = judul parameter → tidak ditampilkan.
        return [
            105 => ['descriptions' => [
                1 => 'Parameter Komposisi dan konsentrasi aset dan kewajiban berada pada tingkat risiko yang Sangat Rendah',
                2 => 'Parameter Komposisi dan konsentrasi aset dan kewajiban berada pada tingkat risiko yang Rendah',
                3 => 'Parameter Komposisi dan konsentrasi aset dan kewajiban berada pada tingkat risiko yang Sedang',
                4 => 'Parameter Komposisi dan konsentrasi aset dan kewajiban berada pada tingkat risiko yang Tinggi',
                5 => 'Parameter Komposisi dan konsentrasi aset dan kewajiban berada pada tingkat risiko yang Sangat Tinggi',
            ]],
            106 => ['threshold' => '≥ 15%', 'descriptions' => [
                1 => '≥ 15%',
                2 => 'Komposisi aset likuid lebih rendah dari 15% terhadap total aset dan komposisi aset',
                3 => 'Komposisi aset likuid lebih rendah dari 15% terhadap total aset dan komposisi aset',
                4 => 'Komposisi aset likuid lebih rendah dari 15% terhadap total aset dan komposisi aset',
                5 => 'Komposisi aset likuid lebih rendah dari 15% terhadap total aset',
            ]],
            107 => ['threshold' => '≥ 20%', 'descriptions' => [
                1 => '≥ 20%',
                2 => 'Komposisi aset likuid lebih rendah dari 20% terhadap kewajiban lancar, namun masih memadai untuk menutup kewajiban jatuh tempo',
                3 => 'Komposisi aset likuid lebih rendah dari 20% terhadap kewajiban lancar, namun cukup memadai untuk menutup kewajiban jatuh tempo',
                4 => 'Komposisi aset likuid lebih rendah dari 20% terhadap kewajiban lancar, namun cukup memadai untuk menutup kewajiban jatuh tempo',
                5 => '• Komposisi aset likuid lebih rendah dari 20% terhadap kewajiban lancar, dan tidak memadai untuk menutup kewajiban jatuh tempo; dan/atau<br>• Rasio aset likuid/kewajiban lancar memenuhi kriteria BDPI',
            ]],
            108 => ['threshold' => '90%', 'descriptions' => [
                1 => '≤ 90%',
                2 => 'LDR lebih tinggi dari 90% dan kredit berkualitas tidak baik tidak signifikan',
                3 => 'LDR lebih tinggi dari 90% namun kredit berkualitas tidak baik kurang signifikan',
                4 => 'LDR lebih tinggi dari 90% namun kredit berkualitas tidak baik cukup signifikan',
                5 => 'LDR lebih tinggi dari 90% dan kredit berkualitas tidak baik sangat signifikan',
            ]],
            109 => ['threshold' => '25%', 'descriptions' => [
                1 => '≤ 25%',
                2 => 'Komposisi 25 deposan dan penabung terbesar lebih dari 25% dan seluruhnya merupakan nasabah lama',
                3 => 'Komposisi 25 deposan dan penabung terbesar lebih dari 25% dan sebagian besar merupakan nasabah lama',
                4 => 'Komposisi 25 deposan dan penabung terbesar lebih dari 25% dan sebagian besar merupakan nasabah baru',
                5 => 'Komposisi 25 deposan dan penabung terbesar lebih dari 25% namun seluruhnya merupakan nasabah baru',
            ]],
            110 => ['threshold' => '10%', 'descriptions' => [
                1 => '≤ 10%',
                2 => 'Rasio pendanaan non inti lebih besar dari 10% namun tidak signifikan terhadap total pendanaan, dan masih dapat dikelola oleh BPR',
                3 => 'Rasio pendanaan non inti lebih besar dari 10% dan cukup signifikan terhadap total pendanaan',
                4 => 'Rasio pendanaan non inti lebih besar dari 10%, dan signifikan sehingga hampir mendominasi BPR',
                5 => 'Rasio pendanaan non inti sangat besar dan mendominasi pendanaan BPR',
            ]],
            111 => ['descriptions' => [
                1 => 'Parameter Kerentanan pada kebutuhan pendanaan serta akses pada sumber pendanaan berada pada tingkat risiko yang Sangat Rendah',
                2 => 'Parameter Kerentanan pada kebutuhan pendanaan serta akses pada sumber pendanaan berada pada tingkat risiko yang Rendah',
                3 => 'Parameter Kerentanan pada kebutuhan pendanaan serta akses pada sumber pendanaan berada pada tingkat risiko yang Sedang',
                4 => 'Parameter Kerentanan pada kebutuhan pendanaan serta akses pada sumber pendanaan berada pada tingkat risiko yang Tinggi',
                5 => 'Parameter Kerentanan pada kebutuhan pendanaan serta akses pada sumber pendanaan berada pada tingkat risiko yang Sangat Tinggi',
            ]],
            112 => ['descriptions' => [
                1 => '• BPR sangat mampu memenuhi kewajiban dan kebutuhan arus kas pada kondisi normal maupun krisis; dan/atau<br>• Arus kas BPR yang berasal dari aset dan kewajiban dapat saling tutup dengan sangat baik (well matched).',
                2 => '• BPR mampu memenuhi kewajiban dan kebutuhan arus kas pada kondisi normal maupun krisis; dan/atau<br>• Arus kas BPR yang berasal dari aset dan kewajiban dapat saling tutup pada mayoritas skala waktu dengan baik.',
                3 => '• BPR cukup mampu memenuhi kewajiban dan kebutuhan arus kas pada kondisi normal maupun krisis (100%) dan/atau<br>• Arus kas BPR yang berasal dari aset dan kewajiban dapat saling tutup dengan cukup baik (100%), terutama pada jangka pendek.',
                4 => '• BPR kurang mampu memenuhi kewajiban dan kebutuhan arus kas pada kondisi normal maupun krisis; dan/atau<br>• Selisih (mismatch) arus kas BPR pada berbagai skala waktu yang cukup signifikan.',
                5 => '• BPR tidak mampu memenuhi kewajiban dan kebutuhan arus kas pada kondisi normal maupun krisis; dan/atau<br>• Arus kas BPR tidak dapat saling tutup.',
            ]],
            113 => ['descriptions' => [
                1 => 'Akses BPR pada sumber pendanaan sangat memadai dibuktikan dengan reputasi BPR sangat baik, pinjaman bank yang sewaktu-waktu dapat ditarik sangat memadai, dan terdapat komitmen/dukungan likuiditas dari pemegang saham pengendali/perusahaan induk/intra grup BPR.',
                2 => 'Akses BPR pada sumber pendanaan memadai dibuktikan dengan reputasi BPR baik, pinjaman bank yang sewaktu-waktu dapat ditarik memadai, dan terdapat komitmen/dukungan likuiditas dari pemegang saham pengendali/perusahaan induk/intra grup BPR.',
                3 => 'Akses BPR pada sumber pendanaan cukup memadai dibuktikan dengan reputasi BPR cukup baik, pinjaman bank yang sewaktu-waktu dapat ditarik cukup memadai, dan terdapat komitmen/dukungan likuiditas dari pemegang saham pengendali/perusahaan induk/intra grup BPR yang cukup memadai.',
                4 => 'Akses BPR pada sumber pendanaan kurang memadai dibuktikan dengan reputasi BPR menurun, pinjaman bank yang sewaktu-waktu dapat ditarik kurang memadai, dan komitmen/dukungan likuiditas dari pemegang saham pengendali/perusahaan induk/intra grup BPR yang sangat terbatas.',
                5 => 'Akses BPR pada sumber pendanaan tidak memadai dibuktikan dengan reputasi BPR buruk sehingga BPR kesulitan memperoleh pendanaan, tidak terdapat pinjaman bank yang sewaktu-waktu dapat ditarik, dan tidak terdapat komitmen/dukungan likuiditas dari pemegang saham pengendali/perusahaan induk/intra grup BPR.',
            ]],
            114 => ['descriptions' => [
                1 => 'Parameter Likuiditas Inheren Lainnya berada pada tingkat risiko yang Sangat Rendah',
                2 => 'Parameter Likuiditas Inheren Lainnya berada pada tingkat risiko yang Rendah',
                3 => 'Parameter Likuiditas Inheren Lainnya berada pada tingkat risiko yang Sedang',
                4 => 'Parameter Likuiditas Inheren Lainnya berada pada tingkat risiko yang Tinggi',
                5 => 'Parameter Likuiditas Inheren Lainnya berada pada tingkat risiko yang Sangat Tinggi',
            ]],
        ];
    }

    // ───────────────────────── kalkulator rasio ─────────────────────────

    private function kalRow(?int $periodeId = null): ?array
    {
        return $this->db->table('kalkulator_likuiditas')
            ->where(['kodebpr' => $this->userKodebpr, 'periode_id' => $periodeId ?? $this->periodeId])
            ->orderBy('id', 'DESC')->get(1)->getRowArray() ?: null;
    }

    /** Semua kolom input kalkulator (kolom => label). */
    private static function kalInputs(): array
    {
        return array_merge(...array_values(self::KAL_FIELDS));
    }

    /** Kolom turunan + rasio dari input (rumus sama dengan kalkulator sebelumnya). */
    public static function kalCompute(array $in): array
    {
        $pct = static fn(float $a, float $b) => $b > 0 ? round($a / $b * 100, 2) : 0.0;
        $d = [
            'asetlikuid' => $in['kas'] + $in['girobanklain'] + $in['tabunganbanklain'],
            'kewajibanlancar' => $in['kewajibansegera'] + $in['tabungandpk'] + $in['depositodpk'],
            'totaldpk' => $in['tabungandpk'] + $in['depositodpk'],
            'transaksibpr' => $in['tabunganabp'] + $in['depositoabp'],
        ];
        $d['pendanaannoninti'] = $in['dpkdiataslps'] + $d['transaksibpr'] + $in['pinjamananmungkinditarik'];
        return ['derived' => $d, 'rasio' => [
            106 => $pct($d['asetlikuid'], $in['totalaset']),
            107 => $pct($d['asetlikuid'], $d['kewajibanlancar']),
            108 => $pct($in['kreditkyd'], $d['totaldpk']),
            109 => $pct($in['penabung25deposan'], $d['totaldpk']),
            110 => $pct($d['pendanaannoninti'], $in['totalpendanaan']),
        ]];
    }

    /** Simpan kalkulator dan masukkan 5 rasio ke kertas kerja (faktor 106–110). Rasio dihitung di server. */
    public function kalkulator()
    {
        if ($r = $this->guardPost()) {
            return $r;
        }
        $in = $errors = [];
        foreach (self::kalInputs() as $f => $label) {
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
        if (!$errors && $in['totalaset'] <= 0) {
            $errors['totalaset'] = 'Total aset harus lebih dari 0.';
        }
        if ($errors) {
            return $this->json(422, ['ok' => false, 'message' => 'Periksa kembali isian kalkulator.', 'errors' => $errors]);
        }

        $res = self::kalCompute($in);
        $data = $in + $res['derived'];
        foreach ($res['rasio'] as $fid => $v) {
            $data[self::KAL_RASIO[$fid]] = $v;
        }
        $now = date('Y-m-d H:i:s');
        $data['user_id'] = $this->userId;
        $data['updated_at'] = $now;

        $kal = $this->kalRow();
        $rows = $this->rows();
        $changed = [];
        $this->db->transStart();
        if ($kal) {
            $this->db->table('kalkulator_likuiditas')->where('id', $kal['id'])->update($data);
        } else {
            $this->db->table('kalkulator_likuiditas')->insert($data + [
                'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId, 'created_at' => $now]);
        }
        foreach ($res['rasio'] as $fid => $v) {
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

        return $this->json(200, ['ok' => true, 'reload' => true, 'message' => 'Kalkulator tersimpan — 5 rasio dimasukkan ke kertas kerja'
            . ($changed ? ' (' . count($changed) . ' berubah)' : ' (tidak ada perubahan rasio)') . '.']);
    }

    /** Kolom angka kalkulator_likuiditas (NOT NULL tanpa default). */
    private function kalCols(): array
    {
        return array_merge(array_keys(self::kalInputs()), self::KAL_DERIVED, array_values(self::KAL_RASIO));
    }

    protected function afterSync(int $prevPeriodeId, bool $force): string
    {
        $prev = $this->kalRow($prevPeriodeId);
        $cur = $this->kalRow();
        if (!$prev || ($cur && !$force)) {
            return '';
        }
        $data = array_map(static fn($v) => $v ?? 0, array_intersect_key($prev, array_flip($this->kalCols())));
        $data['user_id'] = $this->userId;
        $data['updated_at'] = date('Y-m-d H:i:s');
        if ($cur) {
            $this->db->table('kalkulator_likuiditas')->where('id', $cur['id'])->update($data);
        } else {
            $this->db->table('kalkulator_likuiditas')->insert($data + ['kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId,
                'created_at' => $data['updated_at']]);
        }
        return 'termasuk data kalkulator';
    }

    protected function extraData(array $rows): array
    {
        return ['kal' => $this->canEdit() ? $this->kalRow() : null, 'kalFields' => self::KAL_FIELDS, 'kalRasio' => self::KAL_RASIO];
    }

    // ───────────────────────── ekspor TXT ─────────────────────────

    private function sanitizeTxt(string $text): string
    {
        $text = str_replace(["\r", "\n"], ' ', $text);
        // Lama: daftar pengganti tidak sejajar sehingga "%" menjadi "'" — kini " persen " seperti ekspor risiko lain.
        $text = str_replace(['“', '”', '‘', '’', '%', '|'], ['"', '"', "'", "'", ' persen ', ' '], $text);
        $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    public function exporttxtrisikolikuiditas()
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

        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0401|{$p['modalinti']}|{$p['kategori']}|{$p['nomorsurat']}|{$p['jeniskoreksi']}\r\n";
        foreach (self::KODE_MAP as $faktorId => $kode) {
            $row = $indexedData[$faktorId] ?? [];
            $rasio = $this->sanitizeTxt((string) ($row['rasiokredit'] ?? ''));
            $nilai = $this->sanitizeTxt((string) ($row['penilaiankredit'] ?? ''));
            $ket = $this->sanitizeTxt((string) ($row['keterangan'] ?? ''));
            $output .= "D01|{$kode}|{$rasio}|{$nilai}|{$ket}\r\n";
        }

        return service('response')
            ->setHeader('Content-Type', 'text/plain')
            ->setHeader('Content-Disposition', 'attachment; filename="TKSBPRKS-0401-' . $p['jenispelaporan'] . '-S-' . $titleDate . '-' . $sandibpr . '-01.txt"')
            ->setBody($output);
    }
}
