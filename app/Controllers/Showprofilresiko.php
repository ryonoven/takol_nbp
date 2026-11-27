<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_periodesemesteran;
use App\Models\M_showprofilresiko;
use App\Models\M_infobpr;
use App\Models\M_user;
use App\Models\M_nilairisikokredit;
use App\Models\M_risikokreditkpmr;
use App\Models\M_risikooperasional;
use App\Models\M_risikooperasionalkpmr;
use App\Models\M_risikokepatuhan;
use App\Models\M_kepatuhankpmr;
use App\Models\M_likuiditasinheren;
use App\Models\M_likuiditaskpmr;
use App\Models\M_reputasiinheren;
use App\Models\M_reputasikpmr;
use App\Models\M_stratejikinheren;
use App\Models\M_stratejikkpmr;
use Myth\Auth\Config\Services as AuthServices;

/**
 * Laporan Profil Risiko — halaman ringkasan.
 *
 *  - index      : peringkat 6 jenis risiko (inheren × KPMR → matriks) periode ini & sebelumnya, peringkat komposit,
 *                 kesimpulan & lembar persetujuan. 1 query per tabel risiko; baris showprofilresiko hanya ditulis
 *                 bila nilainya berubah.
 *  - save       : simpan kesimpulan + lembar persetujuan (POST JSON). Hanya PE/Admin.
 *  - setTotal   : override / reset peringkat komposit (POST JSON, act=set|reset). Hanya PE/Admin.
 * Export TXT/PDF/JSON di bawah tidak diubah (dipakai ZIP Laporan Semesteran & halaman PDF).
 */
class Showprofilresiko extends Controller
{
    protected $auth;
    protected $session;
    protected $userKodebpr;
    protected $userId;
    protected $periodeId;

    // Models
    protected $periodeModel;
    protected $showprofilresikoModel;
    protected $kreditinherenModel;
    protected $kreditkpmrModel;
    protected $operasionalinherenModel;
    protected $operasionalkpmrModel;

    protected $kepatuhaninherenModel;
    protected $kepatuhankpmrModel;
    protected $likuiditasinherenModel;
    protected $likuiditaskpmrModel;

    protected $reputasiinherenModel;
    protected $reputasikpmrModel;
    protected $stratejikinherenModel;
    protected $stratejikkpmrModel;

    protected $userModel;
    protected $infobprModel;

    protected $userGroups = [];

    private $riskModelsConfig = [];

    /** Lembar persetujuan: kolom => [label, maks]. */
    public const TTD_FIELDS = [
        'dirut' => ['Nama Direktur Utama', 255],
        'dirkep' => ['Nama Direktur yang membawahi fungsi kepatuhan', 255],
        'pe' => ['Nama Pejabat Eksekutif Manajemen Risiko', 255],
        'lokasi' => ['Lokasi', 255],
    ];
    public const MAX_KESIMPULAN = 10000;
    public const LABELS = [1 => 'Sangat Rendah', 2 => 'Rendah', 3 => 'Sedang', 4 => 'Tinggi', 5 => 'Sangat Tinggi'];
    public const KPMR_LABELS = [1 => 'Sangat Memadai', 2 => 'Memadai', 3 => 'Cukup Memadai', 4 => 'Kurang Memadai', 5 => 'Tidak Memadai'];

    public function __construct()
    {
        $this->infobprModel = new M_infobpr();
        $this->periodeModel = new M_periodesemesteran();
        $this->showprofilresikoModel = new M_showprofilresiko();
        $this->kreditinherenModel = new M_nilairisikokredit();
        $this->kreditkpmrModel = new M_risikokreditkpmr();
        $this->operasionalinherenModel = new M_risikooperasional();
        $this->operasionalkpmrModel = new M_risikooperasionalkpmr();

        $this->kepatuhaninherenModel = new M_risikokepatuhan();
        $this->kepatuhankpmrModel = new M_kepatuhankpmr();
        $this->likuiditasinherenModel = new M_likuiditasinheren();
        $this->likuiditaskpmrModel = new M_likuiditaskpmr();

        $this->reputasiinherenModel = new M_reputasiinheren();
        $this->reputasikpmrModel = new M_reputasikpmr();
        $this->stratejikinherenModel = new M_stratejikinheren();
        $this->stratejikkpmrModel = new M_stratejikkpmr();

        $this->userModel = new M_user();

        $this->session = service('session');
        $this->auth = service('authentication');

        helper('text');

        $this->initializeUser();

        $this->initializeRiskModels();
    }

    private function initializeUser()
    {
        $this->userGroups = array_fill_keys(['pe', 'admin', 'dekom', 'direksi'], false);
        $this->userKodebpr = null;
        if ($this->auth->isLoggedIn()) {
            $this->userId = $this->auth->id();
            $user = $this->userModel->find($this->userId);
            $this->userKodebpr = $user['kodebpr'] ?? null;

            $authorize = AuthServices::authorization();
            foreach (array_keys($this->userGroups) as $group) {
                $this->userGroups[$group] = (bool) $authorize->inGroup($group, $this->userId);
            }
        }

        // Periode wajib milik BPR user (cegah akses data BPR lain lewat session active_periode)
        $this->periodeId = null;
        $pid = (int) session('active_periode');
        if ($pid > 0 && $this->userKodebpr) {
            $p = $this->periodeModel->find($pid);
            if ($p && (string) $p['kodebpr'] === (string) $this->userKodebpr) {
                $this->periodeId = $pid;
            }
        }
    }

    /** Model per jenis risiko: tabel inheren/KPMR + faktor ringkasan (saat ini & periode sebelumnya). */
    private function initializeRiskModels()
    {
        $this->riskModelsConfig = [
            'kredit' => [
                'name' => 'Risiko Kredit', 'link' => 'Risikokredit', 'icon' => 'fa-hand-holding-usd',
                'inheren' => ['model' => $this->kreditinherenModel, 'faktor' => 13],
                'kpmr' => ['model' => $this->kreditkpmrModel, 'faktor' => 33],
                'beluminheren' => ['model' => $this->kreditinherenModel, 'faktor' => 14],
                'belumkpmr' => ['model' => $this->kreditkpmrModel, 'faktor' => 34],
                'result_key' => 'nkredit', 'result_belumkey' => 'nbelumkredit', 'kesimpulan_key' => 'kesimpulan1',
            ],
            'operasional' => [
                'name' => 'Risiko Operasional', 'link' => 'Risikooperasional', 'icon' => 'fa-cogs',
                'inheren' => ['model' => $this->operasionalinherenModel, 'faktor' => 48],
                'kpmr' => ['model' => $this->operasionalkpmrModel, 'faktor' => 70],
                'beluminheren' => ['model' => $this->operasionalinherenModel, 'faktor' => 49],
                'belumkpmr' => ['model' => $this->operasionalkpmrModel, 'faktor' => 71],
                'result_key' => 'noperasional', 'result_belumkey' => 'nbelumoperasional', 'kesimpulan_key' => 'kesimpulan2',
            ],
            'kepatuhan' => [
                'name' => 'Risiko Kepatuhan', 'link' => 'Risikokepatuhan', 'icon' => 'fa-balance-scale',
                'inheren' => ['model' => $this->kepatuhaninherenModel, 'faktor' => 81],
                'kpmr' => ['model' => $this->kepatuhankpmrModel, 'faktor' => 102],
                'beluminheren' => ['model' => $this->kepatuhaninherenModel, 'faktor' => 82],
                'belumkpmr' => ['model' => $this->kepatuhankpmrModel, 'faktor' => 103],
                'result_key' => 'nkepatuhan', 'result_belumkey' => 'nbelumkepatuhan', 'kesimpulan_key' => 'kesimpulan3',
            ],
            'likuiditas' => [
                'name' => 'Risiko Likuiditas', 'link' => 'Likuiditasinheren', 'icon' => 'fa-tint',
                'inheren' => ['model' => $this->likuiditasinherenModel, 'faktor' => 115],
                'kpmr' => ['model' => $this->likuiditaskpmrModel, 'faktor' => 135],
                'beluminheren' => ['model' => $this->likuiditasinherenModel, 'faktor' => 116],
                'belumkpmr' => ['model' => $this->likuiditaskpmrModel, 'faktor' => 136],
                'result_key' => 'nlikuiditas', 'result_belumkey' => 'nbelumlikuiditas', 'kesimpulan_key' => 'kesimpulan4',
            ],
            'reputasi' => [
                'name' => 'Risiko Reputasi', 'link' => 'Reputasiinheren', 'icon' => 'fa-bullhorn',
                'inheren' => ['model' => $this->reputasiinherenModel, 'faktor' => 148],
                'kpmr' => ['model' => $this->reputasikpmrModel, 'faktor' => 168],
                'beluminheren' => ['model' => $this->reputasiinherenModel, 'faktor' => 149],
                'belumkpmr' => ['model' => $this->reputasikpmrModel, 'faktor' => 169],
                'result_key' => 'nreputasi', 'result_belumkey' => 'nbelumreputasi', 'kesimpulan_key' => 'kesimpulan5',
            ],
            'stratejik' => [
                'name' => 'Risiko Stratejik', 'link' => 'Stratejikinheren', 'icon' => 'fa-chess',
                'inheren' => ['model' => $this->stratejikinherenModel, 'faktor' => 179],
                'kpmr' => ['model' => $this->stratejikkpmrModel, 'faktor' => 199],
                'beluminheren' => ['model' => $this->stratejikinherenModel, 'faktor' => 180],
                'belumkpmr' => ['model' => $this->stratejikkpmrModel, 'faktor' => 200],
                'result_key' => 'nstratejik', 'result_belumkey' => 'nbelumstratejik', 'kesimpulan_key' => 'kesimpulan6',
            ],
        ];
    }

    private function checkAuth()
    {
        if (!$this->auth->check()) {
            session()->set('redirect_url', current_url());
            return redirect()->to('/login');
        }
        return null;
    }

    private function checkPeriode()
    {
        if (!$this->periodeId) {
            return redirect()->to('/Periodesemesteran')
                ->with('error', 'Silakan pilih periode aktif terlebih dahulu.');
        }
        return null;
    }

    private function canEdit(): bool
    {
        return $this->userGroups['pe'] || $this->userGroups['admin'];
    }

    private function json(int $status, array $payload)
    {
        $payload['csrf'] = ['name' => csrf_token(), 'hash' => csrf_hash()];
        return $this->response->setStatusCode($status)->setJSON($payload);
    }

    private function guardPost()
    {
        if (!$this->auth->check()) {
            return $this->json(401, ['ok' => false, 'message' => 'Sesi berakhir, silakan login kembali.']);
        }
        if (strtolower((string) $this->request->getMethod()) !== 'post') {
            return $this->json(405, ['ok' => false, 'message' => 'Metode tidak diizinkan.']);
        }
        if (!$this->periodeId) {
            return $this->json(400, ['ok' => false, 'message' => 'Periode aktif tidak valid. Silakan pilih periode kembali.']);
        }
        if (!$this->canEdit()) {
            return $this->json(403, ['ok' => false, 'message' => 'Anda tidak memiliki akses untuk mengubah data ini.']);
        }
        return null;
    }

    private static function isKategoriA(?array $periode): bool
    {
        return strtoupper((string) ($periode['kategori'] ?? '')) === 'A';
    }

    private static function rataBulat(array $vals): ?int
    {
        $vals = array_filter($vals, static fn($v) => is_numeric($v) && $v !== null);
        if (!$vals) {
            return null;
        }
        $avg = array_sum($vals) / count($vals);
        return (int) (($avg - floor($avg) >= 0.5) ? ceil($avg) : floor($avg));
    }

    /**
     * Seluruh nilai risiko periode aktif & sebelumnya — satu query per tabel risiko (12 tabel).
     * @return array{rows: array, now: array, before: array} rows = data per jenis risiko untuk tampilan
     */
    private function computeAll(string $kodebpr, int $periodeId, ?array $periode): array
    {
        $vals = [];   // [jenis][inheren|kpmr|beluminheren|belumkpmr] => ['penilaiankredit','is_approved','accdekom']
        $byModel = [];
        foreach ($this->riskModelsConfig as $key => $c) {
            foreach (['inheren' => 'beluminheren', 'kpmr' => 'belumkpmr'] as $now => $bef) {
                $cls = get_class($c[$now]['model']);
                $byModel[$cls]['model'] = $c[$now]['model'];
                $byModel[$cls]['faktor'][$c[$now]['faktor']] = [$key, $now];
                $byModel[$cls]['faktor'][$c[$bef]['faktor']] = [$key, $bef];
            }
        }
        foreach ($byModel as $m) {
            $rows = $m['model']->builder()->select('faktor1id, penilaiankredit, is_approved')
                ->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->whereIn('faktor1id', array_keys($m['faktor']))->get()->getResultArray();
            foreach ($rows as $r) {
                [$key, $slot] = $m['faktor'][(int) $r['faktor1id']];
                $vals[$key][$slot] ??= $r; // baris pertama (sama seperti ->first())
            }
        }

        $now = ['ntotalrisk' => null];
        $before = ['nbelumtotalrisk' => null];
        $display = [];
        foreach ($this->riskModelsConfig as $key => $c) {
            $in = $vals[$key]['inheren']['penilaiankredit'] ?? null;
            $kp = $vals[$key]['kpmr']['penilaiankredit'] ?? null;
            $bin = $vals[$key]['beluminheren']['penilaiankredit'] ?? null;
            $bkp = $vals[$key]['belumkpmr']['penilaiankredit'] ?? null;
            $m = (is_numeric($in) && is_numeric($kp)) ? $this->getRiskMatrix($in, $kp) : ['nilai' => null, 'kesimpulan' => null];
            $mb = (is_numeric($bin) && is_numeric($bkp)) ? $this->getRiskMatrix($bin, $bkp) : ['nilai' => null, 'kesimpulan' => null];
            $now[$c['result_key']] = $m['nilai'];
            $now[$c['kesimpulan_key']] = $m['kesimpulan'];
            $before[$c['result_belumkey']] = $mb['nilai'];
            $display[$key] = [
                'key' => $key, 'name' => $c['name'], 'link' => base_url($c['link']), 'icon' => $c['icon'],
                'inheren' => $in, 'kpmr' => $kp, 'nilai' => $m['nilai'], 'kesimpulan' => $m['kesimpulan'],
                'b_inheren' => $bin, 'b_kpmr' => $bkp, 'b_nilai' => $mb['nilai'],
                'approved' => (int) ($vals[$key]['inheren']['is_approved'] ?? 0) === 1,
                'filled' => isset($vals[$key]['inheren']) || isset($vals[$key]['kpmr']),
            ];
        }
        $keys = ['kredit', 'operasional', 'kepatuhan', 'likuiditas'];
        if (self::isKategoriA($periode)) {
            $keys[] = 'reputasi';
            $keys[] = 'stratejik';
        }
        $now['_auto_total'] = self::rataBulat(array_map(fn($k) => $now[$this->riskModelsConfig[$k]['result_key']], $keys));
        $before['nbelumtotalrisk'] = self::rataBulat(array_map(fn($k) => $before[$this->riskModelsConfig[$k]['result_belumkey']], $keys));
        return ['rows' => $display, 'now' => $now, 'before' => $before, 'keys' => $keys];
    }

    /**
     * Sinkronkan nilai hasil hitung ke tabel showprofilresiko — hanya menulis bila ada perubahan.
     * Peringkat komposit (ntotalrisk) yang di-override manual tetap dipertahankan selama nilai risiko tidak berubah.
     */
    private function syncRow(string $kodebpr, int $periodeId, ?array $periode, array $calc): array
    {
        $row = $this->showprofilresikoModel->getByKodebprAndPeriode($kodebpr, $periodeId);
        $now = $calc['now'];
        $auto = $now['_auto_total'];
        unset($now['_auto_total']);

        $riskKeys = ['nkredit', 'noperasional', 'nkepatuhan', 'nlikuiditas', 'nreputasi', 'nstratejik'];
        $changed = !$row;
        foreach ($riskKeys as $k) {
            if ($row && (string) ($row[$k] ?? '') !== (string) ($now[$k] ?? '')) {
                $changed = true;
            }
        }
        $now['ntotalrisk'] = $changed ? $auto : ($row['ntotalrisk'] ?? $auto);

        // Perbaikan: sebelumnya kesimpulan1–6 tertimpa kesimpulan periode sebelumnya (array_merge key sama)
        $data = $now + $calc['before'] + ['tahun' => $periode['tahun'] ?? date('Y')];

        if (!$row) {
            $tahun = (int) ($periode['tahun'] ?? date('Y'));
            $this->showprofilresikoModel->insert($data + [
                'kodebpr' => $kodebpr, 'periode_id' => $periodeId,
                'dirut' => '', 'dirkep' => '', 'pe' => '', 'lokasi' => '', 'kesimpulan' => '',
                'pdf1_filename' => '', 'pdf2_filename' => '', 'cover' => '',
                // tanggal NOT NULL tanpa default: tanggal akhir semester (dapat diubah user)
                'tanggal' => ((string) ($periode['semester'] ?? '1') === '1') ? "{$tahun}-06-30" : "{$tahun}-12-31",
            ]);
        } else {
            $diff = [];
            foreach ($data as $k => $v) {
                if ((string) ($row[$k] ?? '') !== (string) ($v ?? '')) {
                    $diff[$k] = $v;
                }
            }
            if ($diff) {
                $this->showprofilresikoModel->update((int) $row['id'], $diff + ['updated_at' => date('Y-m-d H:i:s')]);
            }
        }
        return $this->showprofilresikoModel->getByKodebprAndPeriode($kodebpr, $periodeId) ?? [];
    }

    public function index()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;
        if ($redirect = $this->checkPeriode())
            return $redirect;

        $periode = $this->periodeModel->find($this->periodeId);
        $kodebpr = (string) $this->userKodebpr;
        $calc = $this->computeAll($kodebpr, (int) $this->periodeId, $periode);
        $row = $this->syncRow($kodebpr, (int) $this->periodeId, $periode, $calc);
        $sem = (int) ($periode['semester'] ?? 1) === 2 ? 2 : 1;

        $data = [
            'judul' => 'Laporan Profil Risiko',
            'bprData' => $this->infobprModel->getBprByKode($kodebpr) ?? [],
            'periodeLabel' => 'Semester ' . ($sem === 1 ? 'I' : 'II') . ' ' . ($periode['tahun'] ?? ''),
            'prevLabel' => $sem === 1 ? 'Semester II ' . ((int) ($periode['tahun'] ?? 0) - 1) : 'Semester I ' . ($periode['tahun'] ?? ''),
            'risks' => array_intersect_key($calc['rows'], array_flip($calc['keys'])),
            'row' => $row,
            'autoTotal' => $calc['now']['_auto_total'],
            'beforeTotal' => $calc['before']['nbelumtotalrisk'],
            'canEdit' => $this->canEdit(),
        ];

        return view('templates/v_header', $data)
            . view('templates/v_sidebar')
            . view('templates/v_topbar')
            . view('Laporansemester/Profilrisiko/showprofilresiko', $data)
            . view('templates/v_footer');
    }

    /** Simpan kesimpulan + lembar persetujuan (POST JSON). */
    public function save()
    {
        if ($r = $this->guardPost()) {
            return $r;
        }
        $row = $this->showprofilresikoModel->getByKodebprAndPeriode((string) $this->userKodebpr, (int) $this->periodeId);
        if (!$row) {
            return $this->json(409, ['ok' => false, 'message' => 'Muat ulang halaman terlebih dahulu.']);
        }
        $errors = [];
        $data = [];
        $kes = trim(str_replace("\r\n", "\n", (string) $this->request->getPost('kesimpulan')));
        if (mb_strlen($kes) > self::MAX_KESIMPULAN) {
            $errors['kesimpulan'] = 'Maksimal ' . number_format(self::MAX_KESIMPULAN, 0, ',', '.') . ' karakter.';
        }
        $data['kesimpulan'] = $kes;
        foreach (self::TTD_FIELDS as $f => [, $max]) {
            $v = trim((string) $this->request->getPost($f));
            if (mb_strlen($v) > $max) {
                $errors[$f] = "Maksimal {$max} karakter.";
            }
            $data[$f] = $v;
        }
        $tgl = trim((string) $this->request->getPost('tanggal'));
        if ($tgl !== '') {
            $d = \DateTime::createFromFormat('!Y-m-d', $tgl);
            if (!$d || $d->format('Y-m-d') !== $tgl) {
                $errors['tanggal'] = 'Format tanggal tidak valid.';
            }
            $data['tanggal'] = $tgl; // kolom NOT NULL: tanggal kosong = tidak diubah
        }
        if ($errors) {
            return $this->json(422, ['ok' => false, 'message' => 'Periksa kembali isian yang ditandai.', 'errors' => $errors]);
        }
        $diff = array_filter($data, static fn($v, $k) => str_replace("\r\n", "\n", (string) ($row[$k] ?? '')) !== (string) $v, ARRAY_FILTER_USE_BOTH);
        if (!$diff) {
            return $this->json(200, ['ok' => true, 'message' => 'Tidak ada perubahan.']);
        }
        try {
            $this->showprofilresikoModel->update((int) $row['id'], $diff + ['updated_at' => date('Y-m-d H:i:s')]);
        } catch (\Throwable $e) {
            log_message('error', 'Showprofilresiko::save ' . $e->getMessage());
            return $this->json(500, ['ok' => false, 'message' => 'Data gagal disimpan.']);
        }
        return $this->json(200, ['ok' => true, 'reload' => true, 'message' => 'Kesimpulan & lembar persetujuan disimpan.']);
    }

    /** Override (act=set, nilai 1–5) atau kembalikan ke hasil hitung (act=reset) peringkat komposit. */
    public function setTotal()
    {
        if ($r = $this->guardPost()) {
            return $r;
        }
        $kodebpr = (string) $this->userKodebpr;
        $row = $this->showprofilresikoModel->getByKodebprAndPeriode($kodebpr, (int) $this->periodeId);
        if (!$row) {
            return $this->json(409, ['ok' => false, 'message' => 'Muat ulang halaman terlebih dahulu.']);
        }
        $act = (string) $this->request->getPost('act');
        if ($act === 'reset') {
            $periode = $this->periodeModel->find($this->periodeId);
            $val = $this->computeAll($kodebpr, (int) $this->periodeId, $periode)['now']['_auto_total'];
            $msg = 'Peringkat komposit dikembalikan ke hasil perhitungan' . ($val ? " ({$val})." : '.');
        } elseif ($act === 'set') {
            $val = (int) $this->request->getPost('ntotalrisk');
            if ($val < 1 || $val > 5) {
                return $this->json(422, ['ok' => false, 'message' => 'Peringkat harus antara 1–5.']);
            }
            $msg = "Peringkat komposit diubah menjadi {$val} (" . self::LABELS[$val] . ').';
        } else {
            return $this->json(400, ['ok' => false, 'message' => 'Aksi tidak dikenal.']);
        }
        try {
            $this->showprofilresikoModel->update((int) $row['id'], ['ntotalrisk' => $val, 'updated_at' => date('Y-m-d H:i:s')]);
        } catch (\Throwable $e) {
            log_message('error', 'Showprofilresiko::setTotal ' . $e->getMessage());
            return $this->json(500, ['ok' => false, 'message' => 'Gagal menyimpan peringkat.']);
        }
        return $this->json(200, ['ok' => true, 'reload' => true, 'message' => $msg]);
    }

    /** Export/JSON: tolak bila periode aktif bukan milik BPR user (periodeId null). */
    private function denyForeignPeriode()
    {
        if (!$this->auth->check()) {
            return redirect()->to('/login');
        }
        if (!$this->periodeId) {
            return service('response')->setStatusCode(403)->setBody('Periode aktif tidak valid.');
        }
        return null;
    }

    private function getRiskMatrix($inheren, $kpmr)
    {
        $matrix = [
            '1-1' => ['nilai' => 1, 'kesimpulan' => 'Tingkat Risiko Inheren pada tingkat Sangat Rendah dan Tingkat Kualitas Penerapan Manajemen Risiko Sangat Memadai'],
            '1-2' => ['nilai' => 1, 'kesimpulan' => 'Tingkat Risiko Inheren pada tingkat Sangat Rendah dan Tingkat Kualitas Penerapan Manajemen Risiko Memadai'],
            '1-3' => ['nilai' => 1, 'kesimpulan' => 'Tingkat Risiko Inheren pada tingkat Sangat Rendah dan Tingkat Kualitas Penerapan Manajemen Risiko Cukup Memadai'],
            '1-4' => ['nilai' => 1, 'kesimpulan' => 'Tingkat Risiko Inheren pada tingkat Sangat Rendah dan Tingkat Kualitas Penerapan Manajemen Risiko Kurang Memadai'],
            '1-5' => ['nilai' => 1, 'kesimpulan' => 'Kaji ulang terbatas, rencana tindak dilaporkan semesteran'],
            '2-1' => ['nilai' => 1, 'kesimpulan' => 'Tingkat Risiko Inheren pada tingkat Sangat Rendah dan Tingkat Kualitas Penerapan Manajemen Risiko Sangat Memadai'],
            '2-2' => ['nilai' => 2, 'kesimpulan' => 'Tingkat Risiko Inheren pada tingkat Sangat Rendah dan Tingkat Kualitas Penerapan Manajemen Risiko Memadai'],
            '2-3' => ['nilai' => 2, 'kesimpulan' => 'Tingkat Risiko Inheren pada tingkat Sangat Rendah dan Tingkat Kualitas Penerapan Manajemen Risiko Cukup Memadai'],
            '2-4' => ['nilai' => 2, 'kesimpulan' => 'Kaji ulang terbatas, rencana tindak dilaporkan semesteran'],
            '2-5' => ['nilai' => 2, 'kesimpulan' => 'Kaji ulang terbatas, rencana tindak dilaporkan triwulanan'],
            '3-1' => ['nilai' => 2, 'kesimpulan' => 'Tingkat Risiko Inheren pada tingkat Sedang dan Tingkat Kualitas Penerapan Manajemen Risiko Sangat Memadai'],
            '3-2' => ['nilai' => 2, 'kesimpulan' => 'Tingkat Risiko Inheren pada tingkat Sedang dan Tingkat Kualitas Penerapan Manajemen Risiko Memadai'],
            '3-3' => ['nilai' => 3, 'kesimpulan' => 'Kaji ulang terbatas, rencana tindak dilaporkan semesteran'],
            '3-4' => ['nilai' => 3, 'kesimpulan' => 'Kaji ulang terbatas, rencana tindak dilaporkan triwulanan'],
            '3-5' => ['nilai' => 3, 'kesimpulan' => 'Kaji ulang menyeluruh, rencana tindak dilaporkan triwulanan'],
            '4-1' => ['nilai' => 2, 'kesimpulan' => 'Tingkat Risiko Inheren pada tingkat Tinggidan Tingkat Kualitas Penerapan Manajemen Risiko Sangat Memadai'],
            '4-2' => ['nilai' => 3, 'kesimpulan' => 'Kaji ulang terbatas, rencana tindak dilaporkan triwulanan'],
            '4-3' => ['nilai' => 4, 'kesimpulan' => 'Kaji ulang terbatas, rencana tindak dilaporkan triwulanan'],
            '4-4' => ['nilai' => 4, 'kesimpulan' => 'Kaji ulang menyeluruh, rencana tindak dilaporkan triwulanan'],
            '4-5' => ['nilai' => 4, 'kesimpulan' => 'Kaji ulang menyeluruh, rencana tindak dilaporkan bulanan'],
            '5-1' => ['nilai' => 3, 'kesimpulan' => 'Kaji ulang terbatas, rencana tindak dilaporkan triwulanan'],
            '5-2' => ['nilai' => 3, 'kesimpulan' => 'Kaji ulang terbatas, rencana tindak dilaporkan bulanan'],
            '5-3' => ['nilai' => 4, 'kesimpulan' => 'Kaji ulang terbatas, rencana tindak dilaporkan bulanan'],
            '5-4' => ['nilai' => 5, 'kesimpulan' => 'Kaji ulang menyeluruh, rencana tindak dilaporkan bulanan'],
            '5-5' => ['nilai' => 5, 'kesimpulan' => 'Pengawasan melekat, membutuhkan pemantauan secara lebih mendalam'],
        ];

        return $matrix[$inheren . '-' . $kpmr] ?? ['nilai' => null, 'kesimpulan' => ''];
    }

    private function convertDateFormat($date)
    {
        if (empty($date)) {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }
        $dateObj = \DateTime::createFromFormat('d/m/Y', $date) ?: \DateTime::createFromFormat('Y-m-d', $date);
        return $dateObj ? $dateObj->format('Y-m-d') : null;
    }

    public function exporttxtlapprofrisk()
    {
        if ($deny = $this->denyForeignPeriode()) {
            return $deny;
        }
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = $this->periodeId;

        $profilResiko = $this->showprofilresikoModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $output = "";
        $periodeDetail = $this->periodeModel->find($this->periodeId);
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
        $data_showprofresiko = $this->showprofilresikoModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        // BPR Info
        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'] ?? '';
            $kodejenis = $infobpr['kodejenis'] ?? '';
        }

        $profilResiko = $this->showprofilresikoModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $output = "";

        // Header
        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0000|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";

        usort($data_showprofresiko, function ($a, $b) {
            return ($a['faktor1id'] ?? 0) <=> ($b['faktor1id'] ?? 0);
        });

        $kreditInheren = $this->kreditinherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 13)
            ->first();

        $kreditKpmr = $this->kreditkpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 33)
            ->first();

        $kreditbelumInheren = $this->kreditinherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 14)
            ->first();

        $kreditbelumKpmr = $this->kreditkpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 34)
            ->first();

        $inherenkredit = isset($kreditInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $kreditInheren['penilaiankredit']) : '0';
        $kpmrkredit = isset($kreditKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $kreditKpmr['penilaiankredit']) : '0';
        $inherenbelumkredit = isset($kreditbelumInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $kreditbelumInheren['penilaiankredit']) : '0';
        $kpmrbelumkredit = isset($kreditbelumKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $kreditbelumKpmr['penilaiankredit']) : '0';
        $nkredit = isset($profilResiko['nkredit']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nkredit']) : '0';
        $nbelumkredit = isset($profilResiko['nbelumkredit']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nbelumkredit']) : '0';

        $output .= "D01|0001|{$inherenkredit}|{$kpmrkredit}|{$nkredit}|{$inherenbelumkredit}|{$kpmrbelumkredit}|{$nbelumkredit}\r\n";

        // Risiko Operasional
        $operasionalInheren = $this->operasionalinherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 48)
            ->first();

        $operasionalKpmr = $this->operasionalkpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 70)
            ->first();

        $operasionalbelumInheren = $this->operasionalinherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 49)
            ->first();

        $operasionalbelumKpmr = $this->operasionalkpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 71)
            ->first();

        $inherenOperasional = isset($operasionalInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $operasionalInheren['penilaiankredit']) : '0';
        $kpmrOperasional = isset($operasionalKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $operasionalKpmr['penilaiankredit']) : '0';
        $inherenbelumOperasional = isset($operasionalbelumInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $operasionalbelumInheren['penilaiankredit']) : '0';
        $kpmrbelumOperasional = isset($operasionalbelumKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $operasionalbelumKpmr['penilaiankredit']) : '0';
        $nOperasional = isset($profilResiko['noperasional']) ? str_replace(["\r", "\n"], ' ', $profilResiko['noperasional']) : '0';
        $nbelumOperasional = isset($profilResiko['nbelumoperasional']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nbelumoperasional']) : '0';

        $output .= "D01|0002|{$inherenOperasional}|{$kpmrOperasional}|{$nOperasional}|{$inherenbelumOperasional}|{$kpmrbelumOperasional}|{$nbelumOperasional}\r\n";

        // Risiko Kepatuhan
        $kepatuhanInheren = $this->kepatuhaninherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 81)
            ->first();

        $kepatuhanKpmr = $this->kepatuhankpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 102)
            ->first();

        $kepatuhanbelumInheren = $this->kepatuhaninherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 82)
            ->first();

        $kepatuhanbelumKpmr = $this->kepatuhankpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 103)
            ->first();

        $inherenKepatuhan = isset($kepatuhanInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $kepatuhanInheren['penilaiankredit']) : '0';
        $kpmrKepatuhan = isset($kepatuhanKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $kepatuhanKpmr['penilaiankredit']) : '0';
        $inherenbelumKepatuhan = isset($kepatuhanbelumInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $kepatuhanbelumInheren['penilaiankredit']) : '0';
        $kpmrbelumKepatuhan = isset($kepatuhanbelumKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $kepatuhanbelumKpmr['penilaiankredit']) : '0';
        $nKepatuhan = isset($profilResiko['nkepatuhan']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nkepatuhan']) : '0';
        $nbelumKepatuhan = isset($profilResiko['nbelumkepatuhan']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nbelumkepatuhan']) : '0';

        $output .= "D01|0003|{$inherenKepatuhan}|{$kpmrKepatuhan}|{$nKepatuhan}|{$inherenbelumKepatuhan}|{$kpmrbelumKepatuhan}|{$nbelumKepatuhan}\r\n";

        // Risiko Likuiditas
        $likuiditasInheren = $this->likuiditasinherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 115)
            ->first();

        $likuiditasKpmr = $this->likuiditaskpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 135)
            ->first();

        $likuiditasbelumInheren = $this->likuiditasinherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 116)
            ->first();

        $likuiditasbelumKpmr = $this->likuiditaskpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 136)
            ->first();

        $inherenLikuiditas = isset($likuiditasInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $likuiditasInheren['penilaiankredit']) : '0';
        $kpmrLikuiditas = isset($likuiditasKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $likuiditasKpmr['penilaiankredit']) : '0';
        $inherenLikuiditas = isset($likuiditasbelumInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $likuiditasbelumInheren['penilaiankredit']) : '0';
        $kpmrLikuiditas = isset($likuiditasbelumKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $likuiditasbelumKpmr['penilaiankredit']) : '0';
        $nLikuiditas = isset($profilResiko['nlikuiditas']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nlikuiditas']) : '0';
        $nbelumLikuiditas = isset($profilResiko['nbelumlikuiditas']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nbelumlikuiditas']) : '0';

        $output .= "D01|0004|{$inherenLikuiditas}|{$kpmrLikuiditas}|{$nLikuiditas}|{$inherenLikuiditas}|{$kpmrLikuiditas}|{$nbelumLikuiditas}\r\n";


        // Risiko Reputasi
        $reputasiInheren = $this->reputasiinherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 148)
            ->first();

        $reputasiKpmr = $this->reputasikpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 168)
            ->first();

        $reputasibelumInheren = $this->reputasiinherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 149)
            ->first();

        $reputasibelumKpmr = $this->reputasikpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 169)
            ->first();

        $inherenReputasi = isset($reputasiInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $reputasiInheren['penilaiankredit']) : '0';
        $kpmrReputasi = isset($reputasiKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $reputasiKpmr['penilaiankredit']) : '0';
        $inherenbelumReputasi = isset($reputasibelumInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $reputasibelumInheren['penilaiankredit']) : '0';
        $kpmrbelumReputasi = isset($reputasibelumKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $reputasibelumKpmr['penilaiankredit']) : '0';
        $nReputasi = isset($profilResiko['nreputasi']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nreputasi']) : '0';
        $nbelumReputasi = isset($profilResiko['nbelumreputasi']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nbelumreputasi']) : '0';

        $output .= "D01|0005|{$inherenReputasi}|{$kpmrReputasi}|{$nReputasi}|{$inherenbelumReputasi}|{$kpmrbelumReputasi}|{$nbelumReputasi}\r\n";

        // $output .= "D01|0005|0|0|0|0|0|0\r\n";

        // Risiko Stratejik
        $stratejikInheren = $this->stratejikinherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 179)
            ->first();

        $stratejikKpmr = $this->stratejikkpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 199)
            ->first();

        $stratejikbelumInheren = $this->stratejikinherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 180)
            ->first();

        $stratejikbelumKpmr = $this->stratejikkpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 200)
            ->first();

        $operasionalbelumKpmr = $this->operasionalkpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 71)
            ->first();

        $inherenStratejik = isset($stratejikInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $stratejikInheren['penilaiankredit']) : '0';
        $kpmrStratejik = isset($stratejikKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $stratejikKpmr['penilaiankredit']) : '0';
        $inherenbelumStratejik = isset($stratejikbelumInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $stratejikbelumInheren['penilaiankredit']) : '0';
        $kpmrbelumStratejik = isset($stratejikbelumKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $stratejikbelumKpmr['penilaiankredit']) : '0';
        $nStratejik = isset($profilResiko['nstratejik']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nstratejik']) : '0';
        $nbelumStratejik = isset($profilResiko['nbelumstratejik']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nbelumstratejik']) : '0';
        $nTotalrisk = isset($profilResiko['ntotalrisk']) ? str_replace(["\r", "\n"], ' ', $profilResiko['ntotalrisk']) : '0';
        $nbelumTotalrisk = isset($profilResiko['nbelumtotalrisk']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nbelumtotalrisk']) : '0';
        // $kesimpulan = isset($profilResiko['kesimpulan']) ? str_replace(["\r", "\n"], ' ', $profilResiko['kesimpulan']) : ' ';

        function sanitizeText($text)
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

        $kesimpulan = isset($profilResiko['kesimpulan']) ? sanitizeText($profilResiko['kesimpulan']) : ' ';

        $kpmrbelumOperasional = isset($operasionalbelumKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $operasionalbelumKpmr['penilaiankredit']) : '0';

        $output .= "D01|0006|{$inherenStratejik}|{$kpmrStratejik}|{$nStratejik}|{$inherenbelumStratejik}|{$kpmrbelumStratejik}|{$nbelumStratejik}\r\n";

        $output .= "D01|0000|||{$nTotalrisk}|||{$nbelumTotalrisk}\r\n";
        $output .= "F01|{$kesimpulan}\r\n";

        // Filename
        $filename = "TKSBPRKS-0000-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        $response = service('response');
        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response->setBody($output);
    }

    public function exporttxtriskinheren()
    {
        if ($deny = $this->denyForeignPeriode()) {
            return $deny;
        }
        // Authentication check
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        // Get parameters
        $kodebpr = $this->userKodebpr;
        $periodeId = $this->periodeId;

        // Get periode detail
        $profilResiko = $this->showprofilresikoModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $output = "";
        $periodeDetail = $this->periodeModel->find($this->periodeId);
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
        $data_showprofresiko = $this->showprofilresikoModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        // BPR Info
        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'] ?? '';
            $kodejenis = $infobpr['kodejenis'] ?? '';
        }

        $profilResiko = $this->showprofilresikoModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $output = "";

        // Header
        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0100|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";

        usort($data_showprofresiko, function ($a, $b) {
            return ($a['faktor1id'] ?? 0) <=> ($b['faktor1id'] ?? 0);
        });

        $kreditInheren = $this->kreditinherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 13)
            ->first();

        $kreditKpmr = $this->kreditkpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 33)
            ->first();

        $inherenkredit = isset($kreditInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $kreditInheren['penilaiankredit']) : '';
        $keterangankredit = isset($kreditInheren['keterangan']) ? str_replace(["\r", "\n"], ' ', $kreditInheren['keterangan']) : '';
        $kpmrkredit = isset($kreditKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $kreditKpmr['penilaiankredit']) : '';
        $keterangankpmr = isset($kreditKpmr['keterangan']) ? str_replace(["\r", "\n"], ' ', $kreditKpmr['keterangan']) : '';
        $nkredit = isset($profilResiko['nkredit']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nkredit']) : '';
        $kesimpulan1 = isset($profilResiko['kesimpulan1']) ? str_replace(["\r", "\n"], ' ', $profilResiko['kesimpulan1']) : '';

        $output .= "D01|1100|{$nkredit}|{$kesimpulan1}\r\n";
        $output .= "D01|1200|{$inherenkredit}|{$keterangankredit}\r\n";
        $output .= "D01|1300|{$kpmrkredit}|{$keterangankpmr}\r\n";

        // Filename
        $filename = "TKSBPRKS-0100-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        // Response
        $response = service('response');
        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response->setBody($output);
    }

    public function exporttxtoperasional()
    {
        if ($deny = $this->denyForeignPeriode()) {
            return $deny;
        }
        // Authentication check
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        // Get parameters
        $kodebpr = $this->userKodebpr;
        $periodeId = $this->periodeId;

        // Get periode detail
        $profilResiko = $this->showprofilresikoModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $output = "";
        $periodeDetail = $this->periodeModel->find($this->periodeId);
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
        $data_showprofresiko = $this->showprofilresikoModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        // BPR Info
        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'] ?? '';
            $kodejenis = $infobpr['kodejenis'] ?? '';
        }

        $profilResiko = $this->showprofilresikoModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $output = "";

        // Header
        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0200|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";

        usort($data_showprofresiko, function ($a, $b) {
            return ($a['faktor1id'] ?? 0) <=> ($b['faktor1id'] ?? 0);
        });

        $operasionalInheren = $this->operasionalinherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 48)
            ->first();

        $operasionalKpmr = $this->operasionalkpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 70)
            ->first();

        $inherenoperasional = isset($operasionalInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $operasionalInheren['penilaiankredit']) : '';
        $keterangankredit = isset($operasionalInheren['keterangan']) ? str_replace(["\r", "\n"], ' ', $operasionalInheren['keterangan']) : '';
        $kpmroperasional = isset($operasionalKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $operasionalKpmr['penilaiankredit']) : '';
        $keterangankpmr = isset($operasionalKpmr['keterangan']) ? str_replace(["\r", "\n"], ' ', $operasionalKpmr['keterangan']) : '';
        $noperasional = isset($profilResiko['noperasional']) ? str_replace(["\r", "\n"], ' ', $profilResiko['noperasional']) : '';
        $kesimpulan2 = isset($profilResiko['kesimpulan2']) ? str_replace(["\r", "\n"], ' ', $profilResiko['kesimpulan2']) : '';

        $output .= "D01|2100|{$noperasional}|{$kesimpulan2}\r\n";
        $output .= "D01|2200|{$inherenoperasional}|{$keterangankredit}\r\n";
        $output .= "D01|2300|{$kpmroperasional}|{$keterangankpmr}\r\n";

        // Filename
        $filename = "TKSBPRKS-0200-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        // Response
        $response = service('response');
        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response->setBody($output);
    }

    public function exporttxtkepatuhan()
    {
        if ($deny = $this->denyForeignPeriode()) {
            return $deny;
        }
        // Authentication check
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        // Get parameters
        $kodebpr = $this->userKodebpr;
        $periodeId = $this->periodeId;

        // Get periode detail
        $profilResiko = $this->showprofilresikoModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $output = "";
        $periodeDetail = $this->periodeModel->find($this->periodeId);
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
        $data_showprofresiko = $this->showprofilresikoModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        // BPR Info
        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'] ?? '';
            $kodejenis = $infobpr['kodejenis'] ?? '';
        }

        $profilResiko = $this->showprofilresikoModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $output = "";

        // Header
        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0300|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";

        usort($data_showprofresiko, function ($a, $b) {
            return ($a['faktor1id'] ?? 0) <=> ($b['faktor1id'] ?? 0);
        });

        $kepatuhanInheren = $this->kepatuhaninherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 81)
            ->first();

        $kepatuhanKpmr = $this->kepatuhankpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 102)
            ->first();

        $inherenkepatuhan = isset($kepatuhanInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $kepatuhanInheren['penilaiankredit']) : '';
        $keterangankpmr = isset($operasionalInheren['keterangan']) ? str_replace(["\r", "\n"], ' ', $kepatuhanInheren['keterangan']) : '';
        $kpmrkepatuhan = isset($kepatuhanKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $kepatuhanKpmr['penilaiankredit']) : '';
        $keterangankpmr = isset($kepatuhanKpmr['keterangan']) ? str_replace(["\r", "\n"], ' ', $kepatuhanKpmr['keterangan']) : '';
        $nkepatuhan = isset($profilResiko['nkepatuhan']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nkepatuhan']) : '';
        $kesimpulan3 = isset($profilResiko['kesimpulan3']) ? str_replace(["\r", "\n"], ' ', $profilResiko['kesimpulan3']) : '';

        $output .= "D01|3100|{$nkepatuhan}|{$kesimpulan3}\r\n";
        $output .= "D01|3200|{$inherenkepatuhan}|{$keterangankpmr}\r\n";
        $output .= "D01|3300|{$kpmrkepatuhan}|{$keterangankpmr}\r\n";

        // Filename
        $filename = "TKSBPRKS-0300-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        // Response
        $response = service('response');
        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response->setBody($output);
    }

    public function exporttxtlikuiditas()
    {
        if ($deny = $this->denyForeignPeriode()) {
            return $deny;
        }
        // Authentication check
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        // Get parameters
        $kodebpr = $this->userKodebpr;
        $periodeId = $this->periodeId;

        // Get periode detail
        $profilResiko = $this->showprofilresikoModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $output = "";
        $periodeDetail = $this->periodeModel->find($this->periodeId);
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
        $data_showprofresiko = $this->showprofilresikoModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        // BPR Info
        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'] ?? '';
            $kodejenis = $infobpr['kodejenis'] ?? '';
        }

        $profilResiko = $this->showprofilresikoModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $output = "";

        // Header
        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0400|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";

        usort($data_showprofresiko, function ($a, $b) {
            return ($a['faktor1id'] ?? 0) <=> ($b['faktor1id'] ?? 0);
        });

        $likuiditasInheren = $this->likuiditasinherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 115)
            ->first();

        $likuiditasKpmr = $this->likuiditaskpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 135)
            ->first();

        $inherenlikuiditas = isset($likuiditasInheren['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $likuiditasInheren['penilaiankredit']) : '';
        $keterangankpmr = isset($operasionalInheren['keterangan']) ? str_replace(["\r", "\n"], ' ', $likuiditasInheren['keterangan']) : '';
        $kpmrlikuiditas = isset($likuiditasKpmr['penilaiankredit']) ? str_replace(["\r", "\n"], ' ', $likuiditasKpmr['penilaiankredit']) : '';
        $keterangankpmr = isset($likuiditasKpmr['keterangan']) ? str_replace(["\r", "\n"], ' ', $likuiditasKpmr['keterangan']) : '';
        $nlikuiditas = isset($profilResiko['nlikuiditas']) ? str_replace(["\r", "\n"], ' ', $profilResiko['nlikuiditas']) : '';
        $kesimpulan4 = isset($profilResiko['kesimpulan4']) ? str_replace(["\r", "\n"], ' ', $profilResiko['kesimpulan4']) : '';

        $output .= "D01|4100|{$nlikuiditas}|{$kesimpulan4}\r\n";
        $output .= "D01|4200|{$inherenlikuiditas}|{$keterangankpmr}\r\n";
        $output .= "D01|4300|{$kpmrlikuiditas}|{$keterangankpmr}\r\n";

        // Filename
        $filename = "TKSBPRKS-0400-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        // Response
        $response = service('response');
        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response->setBody($output);
    }

    public function exporttxtreputasi()
    {
        if ($deny = $this->denyForeignPeriode()) {
            return $deny;
        }
        // Authentication check
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = $this->periodeId;

        $profilResiko = $this->showprofilresikoModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $periodeDetail = $this->periodeModel->find($this->periodeId);

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

        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'] ?? '';
            $kodejenis = $infobpr['kodejenis'] ?? '';
        }

        function sanitizeTxtReputasi($text)
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

        $output = "";

        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0500|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";

        $reputasiInheren = $this->reputasiinherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 148)
            ->first();

        $reputasiKpmr = $this->reputasikpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 168)
            ->first();

        $nreputasi = sanitizeTxtReputasi($profilResiko['nreputasi'] ?? '');
        $kesimpulan5 = sanitizeTxtReputasi($profilResiko['kesimpulan5'] ?? '');

        $inherenreputasi = sanitizeTxtReputasi($reputasiInheren['penilaiankredit'] ?? '');
        $ketInheren = sanitizeTxtReputasi($reputasiInheren['keterangan'] ?? '');

        $kpmrreputasi = sanitizeTxtReputasi($reputasiKpmr['penilaiankredit'] ?? '');
        $ketKpmr = sanitizeTxtReputasi($reputasiKpmr['keterangan'] ?? '');

        if ($nreputasi !== '' && $kesimpulan5 !== '') {
            $output .= "D01|5100|{$nreputasi}|{$kesimpulan5}\r\n";
        }

        if ($inherenreputasi !== '' && $ketInheren !== '') {
            $output .= "D01|5200|{$inherenreputasi}|{$ketInheren}\r\n";
        }

        if ($kpmrreputasi !== '' && $ketKpmr !== '') {
            $output .= "D01|5300|{$kpmrreputasi}|{$ketKpmr}\r\n";
        }

        $filename = "TKSBPRKS-0500-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        $response = service('response');
        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response->setBody($output);
    }

    public function exporttxtstratejik()
    {
        if ($deny = $this->denyForeignPeriode()) {
            return $deny;
        }
        // Authentication check
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = $this->periodeId;

        $profilResiko = $this->showprofilresikoModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        $periodeDetail = $this->periodeModel->find($this->periodeId);

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

        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'] ?? '';
            $kodejenis = $infobpr['kodejenis'] ?? '';
        }

        function sanitizeTxtStratejik($text)
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

        $output = "";

        $output .= "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0600|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";

        $stratejikInheren = $this->stratejikinherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 179)
            ->first();

        $stratejikKpmr = $this->stratejikkpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('faktor1id', 199)
            ->first();

        $nstratejik = sanitizeTxtStratejik($profilResiko['nstratejik'] ?? '');
        $kesimpulan6 = sanitizeTxtStratejik($profilResiko['kesimpulan6'] ?? '');

        $inherenstratejik = sanitizeTxtStratejik($stratejikInheren['penilaiankredit'] ?? '');
        $ketInheren = sanitizeTxtStratejik($stratejikInheren['keterangan'] ?? '');

        $kpmrstratejik = sanitizeTxtStratejik($stratejikKpmr['penilaiankredit'] ?? '');
        $ketKpmr = sanitizeTxtStratejik($stratejikKpmr['keterangan'] ?? '');

        if ($nstratejik !== '' && $kesimpulan6 !== '') {
            $output .= "D01|6100|{$nstratejik}|{$kesimpulan6}\r\n";
        }

        if ($inherenstratejik !== '' && $ketInheren !== '') {
            $output .= "D01|6200|{$inherenstratejik}|{$ketInheren}\r\n";
        }

        if ($kpmrstratejik !== '' && $ketKpmr !== '') {
            $output .= "D01|6300|{$kpmrstratejik}|{$ketKpmr}\r\n";
        }

        $filename = "TKSBPRKS-0600-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        $response = service('response');
        $response->setHeader('Content-Type', 'text/plain');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response->setBody($output);
    }

    public function exportAllPDFToZipView()
    {
        if ($deny = $this->denyForeignPeriode()) {
            return $deny;
        }
        if ($redirect = $this->checkAuth())
            return $redirect;
        if ($redirect = $this->checkPeriode())
            return $redirect;

        $data = [
            'baseUrl' => base_url()
        ];

        return view('Laporansemester/Pdfsemesteran/export_all_pdf_zip', $data);
    }

    public function exportAllToZip()
    {
        if ($deny = $this->denyForeignPeriode()) {
            return $deny;
        }
        if ($redirect = $this->checkAuth())
            return $redirect;

        $profilResiko = $this->showprofilresikoModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->first();

        $bprData = $this->infobprModel->getBprByKode($this->userKodebpr);
        $namaBpr = preg_replace('/[^A-Za-z0-9\s]/', '', $bprData['namabpr']);
        $namaBpr = preg_replace('/\s+/', ' ', $namaBpr);
        $namaBpr = trim($namaBpr);
        $laporanDate = new \DateTime($profilResiko['tanggal']);
        $zipDate = $laporanDate->format('d-m-Y');


        $zipFileName = 'LAPORAN PROFIL RISIKO NBPSIMPEL - ' . $namaBpr . " (" . $zipDate . ")" . '.zip';
        $zipFilePath = WRITEPATH . 'uploads/' . $zipFileName;

        $zip = new \ZipArchive();

        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
            return redirect()->back()->with('error', 'Gagal membuat file ZIP');
        }

        // Add files to ZIP
        $exports = [
            ['Showprofilresiko', 'exporttxtlapprofrisk'],
            ['Showprofilresiko', 'exporttxtriskinheren'],
            ['Risikokredit', 'exporttxtrisikokredit'],
            ['Risikokreditkpmr', 'exporttxtrisikokreditkpmr'],
            ['Showprofilresiko', 'exporttxtoperasional'],
            ['Risikooperasional', 'exporttxtrisikooperasional'],
            ['Risikooperasionalkpmr', 'exporttxtrisikooperasionalkpmr'],
            ['Showprofilresiko', 'exporttxtkepatuhan'],
            ['Risikokepatuhan', 'exporttxtrisikokepatuhan'],
            ['Kepatuhankpmr', 'exporttxtrisikokepatuhankpmr'],
            ['Showprofilresiko', 'exporttxtlikuiditas'],
            ['Likuiditasinheren', 'exporttxtrisikolikuiditas'],
            ['Likuiditaskpmr', 'exporttxtrisikolikuiditaskpmr'],
            ['Showprofilresiko', 'exporttxtreputasi'],
            ['Reputasiinheren', 'exporttxtrisikoreputasiinheren'],
            ['Reputasikpmr', 'exporttxtrisikoreputasikpmr'],
            ['Showprofilresiko', 'exporttxtstratejik'],
            ['Stratejikinheren', 'exporttxtrisikostratejikinheren'],
            ['Stratejikkpmr', 'exporttxtrisikostratejikkpmr'],
        ];

        foreach ($exports as [$controller, $method]) {
            $this->addTxtToZip($controller, $method, $zip);
        }

        $zip->close();

        // Send file
        return $this->response
            ->setHeader('Content-Type', 'application/zip')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $zipFileName . '"')
            ->setHeader('Content-Length', filesize($zipFilePath))
            ->setBody(file_get_contents($zipFilePath))
            ->send();
    }

    private function addTxtToZip(string $controllerName, string $methodName, \ZipArchive &$zip)
    {
        $controllerClassName = 'App\Controllers\\' . ucfirst($controllerName);

        if (!class_exists($controllerClassName)) {
            log_message('warning', "Controller '$controllerName' tidak ditemukan");
            return;
        }

        $controllerInstance = new $controllerClassName();

        if (!method_exists($controllerInstance, $methodName)) {
            log_message('warning', "Method '$methodName' tidak ditemukan di controller '$controllerName'");
            return;
        }

        $response = $controllerInstance->$methodName();

        if (!$response instanceof \CodeIgniter\HTTP\Response) {
            log_message('warning', "Method '$methodName' di controller '$controllerName' tidak mengembalikan Response yang valid");
            return;
        }

        $txtContent = $response->getBody();
        $fileNameInZip = $controllerName . '.txt';

        $contentDisposition = $response->getHeaderLine('Content-Disposition');
        if (preg_match('/filename="([^"]+)"/', $contentDisposition, $matches)) {
            $fileNameInZip = $matches[1];
        }

        $zip->addFromString($fileNameInZip, $txtContent);
    }

    public function viewLembarPernyataan()
    {
        if ($deny = $this->denyForeignPeriode()) {
            return $deny;
        }
        // Validasi auth dan periode
        if ($redirect = $this->checkAuth())
            return $redirect;
        if ($redirect = $this->checkPeriode())
            return $redirect;

        if (!$this->userKodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid.');
        }

        // Get data
        $periodeDetail = $this->periodeModel->find($this->periodeId);
        $bprData = $this->infobprModel->getBprByKode($this->userKodebpr);
        $showprofilresiko = $this->showprofilresikoModel->getByKodebprAndPeriode($this->userKodebpr, $this->periodeId);

        // Validasi data
        if (!$showprofilresiko) {
            return redirect()->back()->with('error', 'Data profil risiko belum tersedia.');
        }

        // Prepare data untuk view
        $data = [
            'bpr' => $bprData,
            'periode' => $periodeDetail,
            'profil' => $showprofilresiko
        ];

        // Return view
        return view('showprofilresiko/lembar_pernyataan_pdf', $data);
    }

    public function exportLembarPernyataanJSON()
    {
        if ($deny = $this->denyForeignPeriode()) {
            return $deny;
        }
        // Validasi auth dan periode
        if ($redirect = $this->checkAuth())
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        if ($redirect = $this->checkPeriode())
            return $this->response->setJSON(['status' => 'error', 'message' => 'Periode tidak dipilih']);

        if (!$this->userKodebpr) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Kode BPR tidak valid']);
        }

        // Get data
        $periodeDetail = $this->periodeModel->find($this->periodeId);
        $bprData = $this->infobprModel->getBprByKode($this->userKodebpr);
        $showprofilresiko = $this->showprofilresikoModel->getByKodebprAndPeriode($this->userKodebpr, $this->periodeId);

        // Validasi data
        if (!$showprofilresiko) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Data profil risiko belum tersedia'
            ]);
        }

        // Format tanggal
        $tanggal = '';
        if (!empty($showprofilresiko['tanggal'])) {
            $dateObj = \DateTime::createFromFormat('Y-m-d', $showprofilresiko['tanggal']);
            if ($dateObj) {
                // Format: 31 Juli 2025
                $bulan = [
                    '01' => 'Januari',
                    '02' => 'Februari',
                    '03' => 'Maret',
                    '04' => 'April',
                    '05' => 'Mei',
                    '06' => 'Juni',
                    '07' => 'Juli',
                    '08' => 'Agustus',
                    '09' => 'September',
                    '10' => 'Oktober',
                    '11' => 'November',
                    '12' => 'Desember'
                ];
                $tanggal = $dateObj->format('d') . ' ' . $bulan[$dateObj->format('m')] . ' ' . $dateObj->format('Y');
            }
        }

        // Prepare logo (convert to base64 if exists)
        $logoBase64 = '';
        if (!empty($bprData['logo'])) {
            $logoPath = FCPATH . 'asset/img/' . $bprData['logo'];
            if (file_exists($logoPath)) {
                $imageData = file_get_contents($logoPath);
                $imageType = mime_content_type($logoPath);
                $logoBase64 = 'data:' . $imageType . ';base64,' . base64_encode($imageData);
            }
        }

        // Return JSON
        return $this->response->setJSON([
            'status' => 'success',
            'data' => [
                'bpr' => [
                    'logo' => $logoBase64,
                    'namabpr' => $bprData['namabpr'] ?? '',
                    'alamat' => $bprData['alamat'] ?? '',
                    'nomor' => $bprData['nomor'] ?? '',
                    'webbpr' => $bprData['webbpr'] ?? '',
                    'email' => $bprData['email'] ?? ''
                ],
                'periode' => [
                    'semester' => $periodeDetail['semester'] ?? '',
                    'tahun' => $periodeDetail['tahun'] ?? '',
                    'kategori' => $periodeDetail['kategori'] ?? 'B',
                ],
                'profil' => [
                    'lokasi' => $showprofilresiko['lokasi'] ?? '',
                    'tanggal' => $tanggal,
                    'pe' => $showprofilresiko['pe'] ?? '',
                    'dirut' => $showprofilresiko['dirut'] ?? '',
                    'dirkep' => $showprofilresiko['dirkep'] ?? ''
                ]
            ]
        ]);
    }

    public function viewLaporanProfilRisiko()
    {
        if ($deny = $this->denyForeignPeriode()) {
            return $deny;
        }
        // Validasi auth dan periode
        if ($redirect = $this->checkAuth())
            return $redirect;
        if ($redirect = $this->checkPeriode())
            return $redirect;

        if (!$this->userKodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid.');
        }

        // Return view
        return view('Showprofilresiko/laporan_profil_risiko_pdf');
    }

    public function exportLaporanProfilRisikoJSON()
    {
        if ($deny = $this->denyForeignPeriode()) {
            return $deny;
        }
        // Validasi auth dan periode
        if ($redirect = $this->checkAuth())
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        if ($redirect = $this->checkPeriode())
            return $this->response->setJSON(['status' => 'error', 'message' => 'Periode tidak dipilih']);

        if (!$this->userKodebpr) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Kode BPR tidak valid']);
        }

        // Get data
        $periodeDetail = $this->periodeModel->find($this->periodeId);
        $bprData = $this->infobprModel->getBprByKode($this->userKodebpr);
        $showprofilresiko = $this->showprofilresikoModel->getByKodebprAndPeriode($this->userKodebpr, $this->periodeId);

        // Validasi data
        if (!$showprofilresiko) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Data profil risiko belum tersedia'
            ]);
        }

        // Prepare logo
        $logoBase64 = '';
        if (!empty($bprData['logo'])) {
            $logoPath = FCPATH . 'asset/img/' . $bprData['logo'];
            if (file_exists($logoPath)) {
                $imageData = file_get_contents($logoPath);
                $imageType = mime_content_type($logoPath);
                $logoBase64 = 'data:' . $imageType . ';base64,' . base64_encode($imageData);
            }
        }

        // Get periode sebelumnya
        $periodeSebelum = $this->getPeriodeSebelumnya($periodeDetail['semester'], $periodeDetail['tahun']);

        // Ambil data risiko inheren dan KPMR untuk periode current
        $kreditInheren = $this->kreditinherenModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 13)
            ->first();

        $kreditKpmr = $this->kreditkpmrModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 33)
            ->first();

        $operasionalInheren = $this->operasionalinherenModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 48)
            ->first();

        $operasionalKpmr = $this->operasionalkpmrModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 70)
            ->first();

        $kepatuhanInheren = $this->kepatuhaninherenModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 81)
            ->first();

        $kepatuhanKpmr = $this->kepatuhankpmrModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 102)
            ->first();

        $likuiditasInheren = $this->likuiditasinherenModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 115)
            ->first();

        $likuiditasKpmr = $this->likuiditaskpmrModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 135)
            ->first();

        // Data risiko reputasi (jika ada)
        $reputasiInheren = $this->reputasiinherenModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 148)
            ->first();

        $reputasiKpmr = $this->reputasikpmrModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 168)
            ->first();

        // Data risiko stratejik (jika ada)
        $stratejikInheren = $this->stratejikinherenModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 179)
            ->first();

        $stratejikKpmr = $this->stratejikkpmrModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 199)
            ->first();

        // Ambil data periode sebelumnya (belum)
        $kreditInherenBelum = $this->kreditinherenModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 14)
            ->first();

        $kreditKpmrBelum = $this->kreditkpmrModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 34)
            ->first();

        $operasionalInherenBelum = $this->operasionalinherenModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 49)
            ->first();

        $operasionalKpmrBelum = $this->operasionalkpmrModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 71)
            ->first();

        $kepatuhanInherenBelum = $this->kepatuhaninherenModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 82)
            ->first();

        $kepatuhanKpmrBelum = $this->kepatuhankpmrModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 103)
            ->first();

        $likuiditasInherenBelum = $this->likuiditasinherenModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 116)
            ->first();

        $likuiditasKpmrBelum = $this->likuiditaskpmrModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 136)
            ->first();

        $reputasiInherenBelum = $this->reputasiinherenModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 149)
            ->first();

        $reputasiKpmrBelum = $this->reputasikpmrModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 169)
            ->first();

        $stratejikInherenBelum = $this->stratejikinherenModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 180)
            ->first();

        $stratejikKpmrBelum = $this->stratejikkpmrModel
            ->where('kodebpr', $this->userKodebpr)
            ->where('periode_id', $this->periodeId)
            ->where('faktor1id', 200)
            ->first();

        $tanggal = '';
        if (!empty($showprofilresiko['tanggal'])) {
            $dateObj = \DateTime::createFromFormat('Y-m-d', $showprofilresiko['tanggal']);
            if ($dateObj) {
                // Format: 31 Juli 2025
                $bulan = [
                    '01' => 'Januari',
                    '02' => 'Februari',
                    '03' => 'Maret',
                    '04' => 'April',
                    '05' => 'Mei',
                    '06' => 'Juni',
                    '07' => 'Juli',
                    '08' => 'Agustus',
                    '09' => 'September',
                    '10' => 'Oktober',
                    '11' => 'November',
                    '12' => 'Desember'
                ];
                $tanggal = $dateObj->format('d') . ' ' . $bulan[$dateObj->format('m')] . ' ' . $dateObj->format('Y');
            }
        }

        // Return JSON
        return $this->response->setJSON([
            'status' => 'success',
            'data' => [
                'bpr' => [
                    'logo' => $logoBase64,
                    'namabpr' => $bprData['namabpr'] ?? '',
                    'alamat' => $bprData['alamat'] ?? '',
                    'nomor' => $bprData['nomor'] ?? '',
                    'webbpr' => $bprData['webbpr'] ?? '',
                    'email' => $bprData['email'] ?? ''
                ],
                'periode' => [
                    'semester' => $periodeDetail['semester'] ?? '',
                    'tahun' => $periodeDetail['tahun'] ?? '',
                    'modalinti' => $periodeDetail['modalinti'] ?? '',
                    'totalaset' => $periodeDetail['totalaset'] ?? '',
                    'kantorcabang' => $periodeDetail['kantorcabang'] ?? '',
                    'atmdebit' => $periodeDetail['atmdebit'] ?? '',
                    'periodeSebelumSemester' => $periodeSebelum['semester'],
                    'periodeSebelumTahun' => $periodeSebelum['tahun']
                ],
                'kesimpulan' => $showprofilresiko['kesimpulan'] ?? '',
                'profil' => [
                    'lokasi' => $showprofilresiko['lokasi'] ?? '',
                    'tanggal' => $tanggal,
                    'pe' => $showprofilresiko['pe'] ?? '',
                    'dirut' => $showprofilresiko['dirut'] ?? '',
                    'dirkep' => $showprofilresiko['dirkep'] ?? ''
                ],
                'risiko' => [
                    'kredit' => [
                        'inherenCurrent' => $kreditInheren['penilaiankredit'] ?? null,
                        'kpmrCurrent' => $kreditKpmr['penilaiankredit'] ?? null,
                        'current' => $showprofilresiko['nkredit'] ?? null,
                        'inherenPrevious' => $kreditInherenBelum['penilaiankredit'] ?? null,
                        'kpmrPrevious' => $kreditKpmrBelum['penilaiankredit'] ?? null,
                        'previous' => $showprofilresiko['nbelumkredit'] ?? null
                    ],
                    'operasional' => [
                        'inherenCurrent' => $operasionalInheren['penilaiankredit'] ?? null,
                        'kpmrCurrent' => $operasionalKpmr['penilaiankredit'] ?? null,
                        'current' => $showprofilresiko['noperasional'] ?? null,
                        'inherenPrevious' => $operasionalInherenBelum['penilaiankredit'] ?? null,
                        'kpmrPrevious' => $operasionalKpmrBelum['penilaiankredit'] ?? null,
                        'previous' => $showprofilresiko['nbelumoperasional'] ?? null
                    ],
                    'kepatuhan' => [
                        'inherenCurrent' => $kepatuhanInheren['penilaiankredit'] ?? null,
                        'kpmrCurrent' => $kepatuhanKpmr['penilaiankredit'] ?? null,
                        'current' => $showprofilresiko['nkepatuhan'] ?? null,
                        'inherenPrevious' => $kepatuhanInherenBelum['penilaiankredit'] ?? null,
                        'kpmrPrevious' => $kepatuhanKpmrBelum['penilaiankredit'] ?? null,
                        'previous' => $showprofilresiko['nbelumkepatuhan'] ?? null
                    ],
                    'likuiditas' => [
                        'inherenCurrent' => $likuiditasInheren['penilaiankredit'] ?? null,
                        'kpmrCurrent' => $likuiditasKpmr['penilaiankredit'] ?? null,
                        'current' => $showprofilresiko['nlikuiditas'] ?? null,
                        'inherenPrevious' => $likuiditasInherenBelum['penilaiankredit'] ?? null,
                        'kpmrPrevious' => $likuiditasKpmrBelum['penilaiankredit'] ?? null,
                        'previous' => $showprofilresiko['nbelumlikuiditas'] ?? null
                    ],
                    'reputasi' => [
                        'inherenCurrent' => $reputasiInheren['penilaiankredit'] ?? null,
                        'kpmrCurrent' => $reputasiKpmr['penilaiankredit'] ?? null,
                        'current' => $showprofilresiko['nreputasi'] ?? null,
                        'inherenPrevious' => $reputasiInherenBelum['penilaiankredit'] ?? null,
                        'kpmrPrevious' => $reputasiKpmrBelum['penilaiankredit'] ?? null,
                        'previous' => $showprofilresiko['nbelumreputasi'] ?? null
                    ],
                    'stratejik' => [
                        'inherenCurrent' => $stratejikInheren['penilaiankredit'] ?? null,
                        'kpmrCurrent' => $stratejikKpmr['penilaiankredit'] ?? null,
                        'current' => $showprofilresiko['nstratejik'] ?? null,
                        'inherenPrevious' => $stratejikInherenBelum['penilaiankredit'] ?? null,
                        'kpmrPrevious' => $stratejikKpmrBelum['penilaiankredit'] ?? null,
                        'previous' => $showprofilresiko['nbelumstratejik'] ?? null
                    ],
                    'peringkat' => [
                        'current' => $showprofilresiko['ntotalrisk'] ?? null,
                        'previous' => $showprofilresiko['nbelumtotalrisk'] ?? null
                    ]
                ],
                'analisis' => [
                    'intro' => "Hasil dari analisis Profil Risiko semester {$periodeDetail['semester']} tahun {$periodeDetail['tahun']} sebagai berikut:",
                    'kredit' => $this->generateAnalisisText('Kredit', $showprofilresiko, $kreditInheren, $kreditKpmr),
                    'operasional' => $this->generateAnalisisText('Operasional', $showprofilresiko, $operasionalInheren, $operasionalKpmr),
                    'kepatuhan' => $this->generateAnalisisText('Kepatuhan', $showprofilresiko, $kepatuhanInheren, $kepatuhanKpmr),
                    'likuiditas' => $this->generateAnalisisText('Likuiditas', $showprofilresiko, $likuiditasInheren, $likuiditasKpmr),
                    'reputasi' => ($reputasiInheren && $reputasiKpmr) ? $this->generateAnalisisText('Reputasi', $showprofilresiko, $reputasiInheren, $reputasiKpmr) : null,
                    'stratejik' => ($stratejikInheren && $stratejikKpmr) ? $this->generateAnalisisText('Stratejik', $showprofilresiko, $stratejikInheren, $stratejikKpmr) : null
                ]
            ]
        ]);
    }

    private function getPeriodeSebelumnya($semester, $tahun)
    {
        if ($semester == 'I') {
            return ['semester' => 'II', 'tahun' => $tahun - 1];
        } else {
            return ['semester' => 'I', 'tahun' => $tahun];
        }
    }

    private function generateAnalisisText($jenis, $profil, $inheren, $kpmr)
    {
        $nilaiInheren = $inheren['penilaiankredit'] ?? 0;
        $nilaiKpmr = $kpmr['penilaiankredit'] ?? 0;

        $textInheren = $this->getNilaiText($nilaiInheren);
        $textKpmr = $this->getKpmrText($nilaiKpmr);

        return "Risiko {$jenis} : Risiko Inheren peringkat {$nilaiInheren} ({$textInheren}), Risiko KPMR peringkat {$nilaiKpmr} ({$textKpmr})";
    }

    private function getNilaiText($nilai)
    {
        switch ($nilai) {
            case 1:
                return 'Sangat Rendah';
            case 2:
                return 'Rendah';
            case 3:
                return 'Sedang';
            case 4:
                return 'Tinggi';
            case 5:
                return 'Sangat Tinggi';
            default:
                return 'Tidak ada data';
        }
    }

    private function getKpmrText($nilai)
    {
        switch ($nilai) {
            case 1:
                return 'Sangat Rendah';
            case 2:
                return 'Rendah';
            case 3:
                return 'Menengah';
            case 4:
                return 'Tinggi';
            case 5:
                return 'Sangat Tinggi';
            default:
                return 'Tidak ada data';
        }
    }

    public function getRiskKeterangan($riskType, $faktorId)
    {
        if ($deny = $this->denyForeignPeriode()) {
            return $deny;
        }
        // Validasi auth
        if (!$this->auth->check()) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Unauthorized'
            ]);
        }

        if (!$this->periodeId) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Periode tidak dipilih'
            ]);
        }

        try {
            $model = null;
            $isKpmr = false;

            switch ($riskType) {
                case 'kredit':
                    if ($faktorId == 13 || $faktorId == 14) {
                        $model = $this->kreditinherenModel;
                    } else if ($faktorId == 33 || $faktorId == 34) {
                        $model = $this->kreditkpmrModel;
                        $isKpmr = true;
                    }
                    break;

                case 'operasional':
                    if ($faktorId == 48 || $faktorId == 49) {
                        $model = $this->operasionalinherenModel;
                    } else if ($faktorId == 70 || $faktorId == 71) {
                        $model = $this->operasionalkpmrModel;
                        $isKpmr = true;
                    }
                    break;

                case 'kepatuhan':
                    if ($faktorId == 81 || $faktorId == 82) {
                        $model = $this->kepatuhaninherenModel;
                    } else if ($faktorId == 102 || $faktorId == 103) {
                        $model = $this->kepatuhankpmrModel;
                        $isKpmr = true;
                    }
                    break;

                case 'likuiditas':
                    if ($faktorId == 115 || $faktorId == 116) {
                        $model = $this->likuiditasinherenModel;
                    } else if ($faktorId == 135 || $faktorId == 136) {
                        $model = $this->likuiditaskpmrModel;
                        $isKpmr = true;
                    }
                    break;

                case 'reputasi':
                    if ($faktorId == 148 || $faktorId == 149) {
                        $model = $this->reputasiinherenModel;
                    } else if ($faktorId == 168 || $faktorId == 169) {
                        $model = $this->reputasikpmrModel;
                        $isKpmr = true;
                    }
                    break;

                case 'stratejik':
                    if ($faktorId == 179 || $faktorId == 180) {
                        $model = $this->stratejikinherenModel;
                    } else if ($faktorId == 199 || $faktorId == 200) {
                        $model = $this->stratejikkpmrModel;
                        $isKpmr = true;
                    }
                    break;

                default:
                    return $this->response->setJSON([
                        'status' => 'error',
                        'message' => 'Risk type tidak valid'
                    ]);
            }

            if (!$model) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Model tidak ditemukan untuk faktor ID: ' . $faktorId
                ]);
            }

            // Ambil range faktor berdasarkan jenis risiko
            $ranges = $this->getFaktorRanges($riskType, $isKpmr);

            // Query data dengan whereIn yang benar
            $data = $model
                ->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->whereIn('faktor1id', $ranges) // Langsung gunakan array
                ->orderBy('faktor1id', 'ASC')
                ->findAll();

            // Format data untuk response
            $formattedData = [];
            foreach ($data as $item) {
                // Skip faktor utama (parent)
                if ($item['faktor1id'] == $faktorId) {
                    continue;
                }

                $formattedData[] = [
                    'faktor1id' => $item['faktor1id'],
                    'keterangan' => $item['keterangan'] ?? '',
                    'penilaian' => $item['penilaiankredit'] ?? null
                ];
            }

            return $this->response->setJSON([
                'status' => 'success',
                'data' => $formattedData
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Helper function untuk mendapatkan range faktor ID
     * berdasarkan jenis risiko dan tipe (inheren/kpmr)
     */
    private function getFaktorRanges($riskType, $isKpmr)
    {
        $ranges = [];

        switch ($riskType) {
            case 'kredit':
                if ($isKpmr) {
                    // KPMR Kredit: faktor 15-32 (current) atau 35-52 (previous)
                    $ranges = range(15, 32);
                } else {
                    // Inheren Kredit: faktor 1-12 (current) atau 14-26 (previous)
                    $ranges = range(1, 12);
                }
                break;

            case 'operasional':
                if ($isKpmr) {
                    // KPMR Operasional: faktor 50-69 (current) atau 71-90 (previous)
                    $ranges = range(50, 69);
                } else {
                    // Inheren Operasional: faktor 35-47 (current) atau 49-61 (previous)
                    $ranges = range(35, 47);
                }
                break;

            case 'kepatuhan':
                if ($isKpmr) {
                    // KPMR Kepatuhan: faktor 83-101 (current) atau 103-121 (previous)
                    $ranges = range(83, 101);
                } else {
                    // Inheren Kepatuhan: faktor 62-80 (current) atau 82-100 (previous)
                    $ranges = range(62, 80);
                }
                break;

            case 'likuiditas':
                if ($isKpmr) {
                    // KPMR Likuiditas: faktor 118-134 (current) atau 136-153 (previous)
                    $ranges = range(118, 134);
                } else {
                    // Inheren Likuiditas: faktor 104-114 (current) atau 116-126 (previous)
                    $ranges = range(105, 114);
                }
                break;

            case 'reputasi':
                if ($isKpmr) {
                    // KPMR Reputasi: faktor 150-167 (current) atau 169-186 (previous)
                    $ranges = range(150, 167);
                } else {
                    // Inheren Reputasi: faktor 137-147 (current) atau 149-159 (previous)
                    $ranges = range(137, 147);
                }
                break;

            case 'stratejik':
                if ($isKpmr) {
                    // KPMR Stratejik: faktor 181-198 (current) atau 200-217 (previous)
                    $ranges = range(181, 198);
                } else {
                    // Inheren Stratejik: faktor 170-178 (current) atau 180-188 (previous)
                    $ranges = range(170, 178);
                }
                break;
        }

        return $ranges;
    }

    public function exportPDFGabunganKreditJSON()
    {
        if ($deny = $this->denyForeignPeriode()) {
            return $deny;
        }
        // Validasi auth dan periode
        if (!$this->auth->check()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if (!$this->periodeId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Periode tidak dipilih']);
        }

        if (!$this->userKodebpr) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Kode BPR tidak valid']);
        }

        try {
            // Get periode and BPR data
            $periodeDetail = $this->periodeModel->find($this->periodeId);
            $bprData = $this->infobprModel->getBprByKode($this->userKodebpr);

            // Prepare logo
            $logoBase64 = '';
            if (!empty($bprData['logo'])) {
                $logoPath = FCPATH . 'asset/img/' . $bprData['logo'];
                if (file_exists($logoPath)) {
                    $imageData = file_get_contents($logoPath);
                    $imageType = mime_content_type($logoPath);
                    $logoBase64 = 'data:' . $imageType . ';base64,' . base64_encode($imageData);
                }
            }

            // Get Inheren data with all children
            $dataInheren = $this->getKreditInherenWithChildren($this->userKodebpr, $this->periodeId);

            // Get KPMR data with all children
            $dataKPMR = $this->getKreditKPMRWithChildren($this->userKodebpr, $this->periodeId);

            return $this->response->setJSON([
                'status' => 'success',
                'data' => [
                    'bpr' => [
                        'logo' => $logoBase64,
                        'namabpr' => $bprData['namabpr'] ?? '',
                        'alamat' => $bprData['alamat'] ?? '',
                        'nomor' => $bprData['nomor'] ?? '',
                        'webbpr' => $bprData['webbpr'] ?? '',
                        'email' => $bprData['email'] ?? ''
                    ],
                    'periode' => [
                        'semester' => $periodeDetail['semester'] ?? '',
                        'tahun' => $periodeDetail['tahun'] ?? ''
                    ],
                    'inheren' => [
                        'bpr' => [
                            'namabpr' => $bprData['namabpr'] ?? ''
                        ],
                        'periode' => [
                            'semester' => $periodeDetail['semester'] ?? '',
                            'tahun' => $periodeDetail['tahun'] ?? ''
                        ],
                        'nilai' => $dataInheren['nilai'],
                        'nilai13' => $dataInheren['nilai13'],
                        'nilai14' => $dataInheren['nilai14']
                    ],
                    'kpmr' => [
                        'bpr' => [
                            'namabpr' => $bprData['namabpr'] ?? ''
                        ],
                        'periode' => [
                            'semester' => $periodeDetail['semester'] ?? '',
                            'tahun' => $periodeDetail['tahun'] ?? ''
                        ],
                        'nilai' => $dataKPMR['nilai'],
                        'nilai33' => $dataKPMR['nilai33'],
                        'nilai34' => $dataKPMR['nilai34']
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error exportPDFGabunganKreditJSON: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    private function getKreditInherenWithChildren($kodebpr, $periodeId)
    {
        $result = [
            'nilai' => [
                'komposisi' => ['kategori' => null, 'children' => []],
                'kualitas' => ['kategori' => null, 'children' => []],
                'strategi' => null,
                'eksternal' => null,
                'lainnya' => null
            ],
            'nilai13' => null,
            'nilai14' => null
        ];

        // Get all data (faktor 1-116)
        $allData = $this->kreditinherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->whereIn('faktor1id', range(1, 14))
            ->orderBy('faktor1id', 'ASC')
            ->findAll();

        foreach ($allData as $item) {
            $faktorId = $item['faktor1id'];

            // komposisi (1-5)
            if ($faktorId >= 1 && $faktorId <= 5) {
                if ($faktorId == 1) {
                    $result['nilai']['komposisi']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['komposisi']['children'][] = $item;
                }
            }
            // Kualitas (6-9)
            else if ($faktorId >= 6 && $faktorId <= 9) {
                if ($faktorId == 6) {
                    $result['nilai']['kualitas']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['kualitas']['children'][] = $item;
                }
            }

            // Penilaian Strategi Penyediaan Dana
            else if ($faktorId == 10) {
                $result['nilai']['strategi'] = $item;
            }

            // Faktor Eksternal
            else if ($faktorId == 11) {
                $result['nilai']['eksternal'] = $item;
            }

            // Lainnya
            else if ($faktorId == 12) {
                $result['nilai']['lainnya'] = $item;
            }

            // Penilaian Risiko Current
            else if ($faktorId == 13) {
                $result['nilai13'] = $item;
            }
            // Penilaian Risiko Previous
            else if ($faktorId == 14) {
                $result['nilai14'] = $item;
            }
        }

        return $result;
    }

    private function getKreditKPMRWithChildren($kodebpr, $periodeId)
    {
        $result = [
            'nilai' => [
                'pengawasan' => ['kategori' => null, 'children' => []],
                'kebijakan' => ['kategori' => null, 'children' => []],
                'proses' => ['kategori' => null, 'children' => []],
                'pengendalian' => ['kategori' => null, 'children' => []]
            ],
            'nilai33' => null,
            'nilai34' => null
        ];

        // Get all data (faktor 16-34)
        $allData = $this->kreditkpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->whereIn('faktor1id', range(16, 34))
            ->orderBy('faktor1id', 'ASC')
            ->findAll();

        foreach ($allData as $item) {
            $faktorId = $item['faktor1id'];

            // Pengawasan (16-22)
            if ($faktorId >= 16 && $faktorId <= 22) {
                if ($faktorId == 16) {
                    $result['nilai']['pengawasan']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['pengawasan']['children'][] = $item;
                }
            }
            // Kebijakan (23-26)
            else if ($faktorId >= 23 && $faktorId <= 26) {
                if ($faktorId == 23) {
                    $result['nilai']['kebijakan']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['kebijakan']['children'][] = $item;
                }
            }
            // Proses (27-29)
            else if ($faktorId >= 27 && $faktorId <= 29) {
                if ($faktorId == 27) {
                    $result['nilai']['proses']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['proses']['children'][] = $item;
                }
            }
            // Pengendalian (30-32)
            else if ($faktorId >= 30 && $faktorId <= 32) {
                if ($faktorId == 30) {
                    $result['nilai']['pengendalian']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['pengendalian']['children'][] = $item;
                }
            }
            // Penilaian Risiko KPMR Current
            else if ($faktorId == 33) {
                $result['nilai33'] = $item;
            }
            // Penilaian Risiko KPMR Previous
            else if ($faktorId == 34) {
                $result['nilai34'] = $item;
            }
        }

        return $result;
    }

    public function exportPDFGabunganOperasionalJSON()
    {
        if ($deny = $this->denyForeignPeriode()) {
            return $deny;
        }
        // Validasi auth dan periode
        if (!$this->auth->check()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if (!$this->periodeId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Periode tidak dipilih']);
        }

        if (!$this->userKodebpr) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Kode BPR tidak valid']);
        }

        try {
            // Get periode and BPR data
            $periodeDetail = $this->periodeModel->find($this->periodeId);
            $bprData = $this->infobprModel->getBprByKode($this->userKodebpr);

            // Prepare logo
            $logoBase64 = '';
            if (!empty($bprData['logo'])) {
                $logoPath = FCPATH . 'asset/img/' . $bprData['logo'];
                if (file_exists($logoPath)) {
                    $imageData = file_get_contents($logoPath);
                    $imageType = mime_content_type($logoPath);
                    $logoBase64 = 'data:' . $imageType . ';base64,' . base64_encode($imageData);
                }
            }

            // Get Inheren data with all children
            $dataInheren = $this->getOperasionalInherenWithChildren($this->userKodebpr, $this->periodeId);

            // Get KPMR data with all children
            $dataKPMR = $this->getOperasionalKPMRWithChildren($this->userKodebpr, $this->periodeId);

            return $this->response->setJSON([
                'status' => 'success',
                'data' => [
                    'bpr' => [
                        'logo' => $logoBase64,
                        'namabpr' => $bprData['namabpr'] ?? '',
                        'alamat' => $bprData['alamat'] ?? '',
                        'nomor' => $bprData['nomor'] ?? '',
                        'webbpr' => $bprData['webbpr'] ?? '',
                        'email' => $bprData['email'] ?? ''
                    ],
                    'periode' => [
                        'semester' => $periodeDetail['semester'] ?? '',
                        'tahun' => $periodeDetail['tahun'] ?? ''
                    ],
                    'inheren' => [
                        'bpr' => [
                            'namabpr' => $bprData['namabpr'] ?? ''
                        ],
                        'periode' => [
                            'semester' => $periodeDetail['semester'] ?? '',
                            'tahun' => $periodeDetail['tahun'] ?? ''
                        ],
                        'nilai' => $dataInheren['nilai'],
                        'nilai13' => $dataInheren['nilai13'],
                        'nilai14' => $dataInheren['nilai14']
                    ],
                    'kpmr' => [
                        'bpr' => [
                            'namabpr' => $bprData['namabpr'] ?? ''
                        ],
                        'periode' => [
                            'semester' => $periodeDetail['semester'] ?? '',
                            'tahun' => $periodeDetail['tahun'] ?? ''
                        ],
                        'nilai' => $dataKPMR['nilai'],
                        'nilai33' => $dataKPMR['nilai33'],
                        'nilai34' => $dataKPMR['nilai34']
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error exportPDFGabunganOperasionalJSON: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    private function getOperasionalInherenWithChildren($kodebpr, $periodeId)
    {
        $result = [
            'nilai' => [
                'kompleksitas' => ['kategori' => null, 'children' => []],
                'sdm' => ['kategori' => null, 'children' => []],
                'ti' => null,
                'fraud' => null,
                'eksternal' => null,
                'lainnya' => null
            ],
            'nilai13' => null,
            'nilai14' => null
        ];

        // Get all data
        $allData = $this->operasionalinherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->whereIn('faktor1id', range(35, 49))
            ->orderBy('faktor1id', 'ASC')
            ->findAll();

        foreach ($allData as $item) {
            $faktorId = $item['faktor1id'];

            // Kompleksitas (36-40)
            if ($faktorId >= 36 && $faktorId <= 40) {
                if ($faktorId == 36) {
                    $result['nilai']['kompleksitas']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['kompleksitas']['children'][] = $item;
                }
            }
            // SDM (41-43)
            else if ($faktorId >= 41 && $faktorId <= 43) {
                if ($faktorId == 41) {
                    $result['nilai']['sdm']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['sdm']['children'][] = $item;
                }
            }
            // Single factors
            else if ($faktorId == 44) {
                $result['nilai']['ti'] = $item;
            } else if ($faktorId == 45) {
                $result['nilai']['fraud'] = $item;
            } else if ($faktorId == 46) {
                $result['nilai']['eksternal'] = $item;
            } else if ($faktorId == 47) {
                $result['nilai']['lainnya'] = $item;
            } else if ($faktorId == 48) {
                $result['nilai13'] = $item;
            } else if ($faktorId == 49) {
                $result['nilai14'] = $item;
            }
        }

        return $result;
    }

    private function getOperasionalKPMRWithChildren($kodebpr, $periodeId)
    {
        $result = [
            'nilai' => [
                'pengawasan' => ['kategori' => null, 'children' => []],
                'kebijakan' => ['kategori' => null, 'children' => []],
                'proses' => ['kategori' => null, 'children' => []],
                'pengendalian' => ['kategori' => null, 'children' => []]
            ],
            'nilai33' => null,
            'nilai34' => null
        ];

        // Get all data
        $allData = $this->operasionalkpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->whereIn('faktor1id', range(50, 71))
            ->orderBy('faktor1id', 'ASC')
            ->findAll();

        foreach ($allData as $item) {
            $faktorId = $item['faktor1id'];

            // Pengawasan (51-57)
            if ($faktorId >= 51 && $faktorId <= 57) {
                if ($faktorId == 51) {
                    $result['nilai']['pengawasan']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['pengawasan']['children'][] = $item;
                }
            }
            // Kebijakan (58-61)
            else if ($faktorId >= 58 && $faktorId <= 61) {
                if ($faktorId == 58) {
                    $result['nilai']['kebijakan']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['kebijakan']['children'][] = $item;
                }
            }
            // Proses (62-66)
            else if ($faktorId >= 62 && $faktorId <= 66) {
                if ($faktorId == 62) {
                    $result['nilai']['proses']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['proses']['children'][] = $item;
                }
            }
            // Pengendalian (67-69)
            else if ($faktorId >= 67 && $faktorId <= 69) {
                if ($faktorId == 67) {
                    $result['nilai']['pengendalian']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['pengendalian']['children'][] = $item;
                }
            } else if ($faktorId == 70) {
                $result['nilai33'] = $item;
            } else if ($faktorId == 71) {
                $result['nilai34'] = $item;
            }
        }

        return $result;
    }

    public function exportPDFGabunganKepatuhanJSON()
    {
        if ($deny = $this->denyForeignPeriode()) {
            return $deny;
        }
        // Validasi auth dan periode
        if (!$this->auth->check()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if (!$this->periodeId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Periode tidak dipilih']);
        }

        if (!$this->userKodebpr) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Kode BPR tidak valid']);
        }

        try {
            // Get periode and BPR data
            $periodeDetail = $this->periodeModel->find($this->periodeId);
            $bprData = $this->infobprModel->getBprByKode($this->userKodebpr);

            // Prepare logo
            $logoBase64 = '';
            if (!empty($bprData['logo'])) {
                $logoPath = FCPATH . 'asset/img/' . $bprData['logo'];
                if (file_exists($logoPath)) {
                    $imageData = file_get_contents($logoPath);
                    $imageType = mime_content_type($logoPath);
                    $logoBase64 = 'data:' . $imageType . ';base64,' . base64_encode($imageData);
                }
            }

            // Get Inheren data with all children
            $dataInheren = $this->getKepatuhanInherenWithChildren($this->userKodebpr, $this->periodeId);

            // Get KPMR data with all children
            $dataKPMR = $this->getKepatuhanKPMRWithChildren($this->userKodebpr, $this->periodeId);

            return $this->response->setJSON([
                'status' => 'success',
                'data' => [
                    'bpr' => [
                        'logo' => $logoBase64,
                        'namabpr' => $bprData['namabpr'] ?? '',
                        'alamat' => $bprData['alamat'] ?? '',
                        'nomor' => $bprData['nomor'] ?? '',
                        'webbpr' => $bprData['webbpr'] ?? '',
                        'email' => $bprData['email'] ?? ''
                    ],
                    'periode' => [
                        'semester' => $periodeDetail['semester'] ?? '',
                        'tahun' => $periodeDetail['tahun'] ?? ''
                    ],
                    'inheren' => [
                        'bpr' => [
                            'namabpr' => $bprData['namabpr'] ?? ''
                        ],
                        'periode' => [
                            'semester' => $periodeDetail['semester'] ?? '',
                            'tahun' => $periodeDetail['tahun'] ?? ''
                        ],
                        'nilai' => $dataInheren['nilai'],
                        'nilai81' => $dataInheren['nilai81'],
                        'nilai82' => $dataInheren['nilai82']
                    ],
                    'kpmr' => [
                        'bpr' => [
                            'namabpr' => $bprData['namabpr'] ?? ''
                        ],
                        'periode' => [
                            'semester' => $periodeDetail['semester'] ?? '',
                            'tahun' => $periodeDetail['tahun'] ?? ''
                        ],
                        'nilai' => $dataKPMR['nilai'],
                        'nilai102' => $dataKPMR['nilai102'],
                        'nilai103' => $dataKPMR['nilai103']
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error exportPDFGabunganKepatuhanJSON: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    private function getKepatuhanInherenWithChildren($kodebpr, $periodeId)
    {
        $result = [
            'nilai' => [
                'pelanggaran' => ['kategori' => null, 'children' => []],
                'hukum' => ['kategori' => null, 'children' => []],
                'lainnya' => null
            ],
            'nilai81' => null,
            'nilai82' => null
        ];

        // Get all data
        $allData = $this->kepatuhaninherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->whereIn('faktor1id', range(73, 82))
            ->orderBy('faktor1id', 'ASC')
            ->findAll();

        foreach ($allData as $item) {
            $faktorId = $item['faktor1id'];

            // Pelanggaran (36-40)
            if ($faktorId >= 73 && $faktorId <= 75) {
                if ($faktorId == 73) {
                    $result['nilai']['pelanggaran']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['pelanggaran']['children'][] = $item;
                }
            }
            // Hukum (41-43)
            else if ($faktorId >= 76 && $faktorId <= 79) {
                if ($faktorId == 76) {
                    $result['nilai']['hukum']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['hukum']['children'][] = $item;
                }
            }
            // Single factors
            else if ($faktorId == 80) {
                $result['nilai']['lainnya'] = $item;
            } else if ($faktorId == 81) {
                $result['nilai81'] = $item;
            } else if ($faktorId == 82) {
                $result['nilai82'] = $item;
            }
        }

        return $result;
    }

    private function getKepatuhanKPMRWithChildren($kodebpr, $periodeId)
    {
        $result = [
            'nilai' => [
                'pengawasan' => ['kategori' => null, 'children' => []],
                'kebijakan' => ['kategori' => null, 'children' => []],
                'proses' => ['kategori' => null, 'children' => []],
                'pengendalian' => ['kategori' => null, 'children' => []]
            ],
            'nilai102' => null,
            'nilai103' => null
        ];

        // Get all data
        $allData = $this->kepatuhankpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->whereIn('faktor1id', range(83, 103))
            ->orderBy('faktor1id', 'ASC')
            ->findAll();

        foreach ($allData as $item) {
            $faktorId = $item['faktor1id'];

            // Pengawasan (51-57)
            if ($faktorId >= 84 && $faktorId <= 91) {
                if ($faktorId == 84) {
                    $result['nilai']['pengawasan']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['pengawasan']['children'][] = $item;
                }
            }
            // Kebijakan (58-61)
            else if ($faktorId >= 92 && $faktorId <= 95) {
                if ($faktorId == 92) {
                    $result['nilai']['kebijakan']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['kebijakan']['children'][] = $item;
                }
            }
            // Proses (62-66)
            else if ($faktorId >= 96 && $faktorId <= 98) {
                if ($faktorId == 96) {
                    $result['nilai']['proses']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['proses']['children'][] = $item;
                }
            }
            // Pengendalian (67-69)
            else if ($faktorId >= 99 && $faktorId <= 101) {
                if ($faktorId == 99) {
                    $result['nilai']['pengendalian']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['pengendalian']['children'][] = $item;
                }
            } else if ($faktorId == 102) {
                $result['nilai102'] = $item;
            } else if ($faktorId == 103) {
                $result['nilai103'] = $item;
            }
        }

        return $result;
    }

    public function exportPDFGabunganLikuiditasJSON()
    {
        if ($deny = $this->denyForeignPeriode()) {
            return $deny;
        }
        // Validasi auth dan periode
        if (!$this->auth->check()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if (!$this->periodeId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Periode tidak dipilih']);
        }

        if (!$this->userKodebpr) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Kode BPR tidak valid']);
        }

        try {
            // Get periode and BPR data
            $periodeDetail = $this->periodeModel->find($this->periodeId);
            $bprData = $this->infobprModel->getBprByKode($this->userKodebpr);

            // Prepare logo
            $logoBase64 = '';
            if (!empty($bprData['logo'])) {
                $logoPath = FCPATH . 'asset/img/' . $bprData['logo'];
                if (file_exists($logoPath)) {
                    $imageData = file_get_contents($logoPath);
                    $imageType = mime_content_type($logoPath);
                    $logoBase64 = 'data:' . $imageType . ';base64,' . base64_encode($imageData);
                }
            }

            // Get Inheren data with all children
            $dataInheren = $this->getLikuiditasInherenWithChildren($this->userKodebpr, $this->periodeId);

            // Get KPMR data with all children
            $dataKPMR = $this->getLikuiditasKPMRWithChildren($this->userKodebpr, $this->periodeId);

            return $this->response->setJSON([
                'status' => 'success',
                'data' => [
                    'bpr' => [
                        'logo' => $logoBase64,
                        'namabpr' => $bprData['namabpr'] ?? '',
                        'alamat' => $bprData['alamat'] ?? '',
                        'nomor' => $bprData['nomor'] ?? '',
                        'webbpr' => $bprData['webbpr'] ?? '',
                        'email' => $bprData['email'] ?? ''
                    ],
                    'periode' => [
                        'semester' => $periodeDetail['semester'] ?? '',
                        'tahun' => $periodeDetail['tahun'] ?? ''
                    ],
                    'inheren' => [
                        'bpr' => [
                            'namabpr' => $bprData['namabpr'] ?? ''
                        ],
                        'periode' => [
                            'semester' => $periodeDetail['semester'] ?? '',
                            'tahun' => $periodeDetail['tahun'] ?? ''
                        ],
                        'nilai' => $dataInheren['nilai'],
                        'nilai115' => $dataInheren['nilai115'],
                        'nilai116' => $dataInheren['nilai116']
                    ],
                    'kpmr' => [
                        'bpr' => [
                            'namabpr' => $bprData['namabpr'] ?? ''
                        ],
                        'periode' => [
                            'semester' => $periodeDetail['semester'] ?? '',
                            'tahun' => $periodeDetail['tahun'] ?? ''
                        ],
                        'nilai' => $dataKPMR['nilai'],
                        'nilai135' => $dataKPMR['nilai135'],
                        'nilai136' => $dataKPMR['nilai136']
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error exportPDFGabunganLikuiditasJSON: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    private function getLikuiditasInherenWithChildren($kodebpr, $periodeId)
    {
        $result = [
            'nilai' => [
                'konsentrasi' => ['kategori' => null, 'children' => []],
                'kerentanan' => ['kategori' => null, 'children' => []],
                'lainnya' => null
            ],
            'nilai115' => null,
            'nilai116' => null
        ];

        // Get all data (faktor 105-116)
        $allData = $this->likuiditasinherenModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->whereIn('faktor1id', range(105, 116))
            ->orderBy('faktor1id', 'ASC')
            ->findAll();

        foreach ($allData as $item) {
            $faktorId = $item['faktor1id'];

            // Komposisi dan konsentrasi (105-110)
            if ($faktorId >= 105 && $faktorId <= 110) {
                if ($faktorId == 105) {
                    $result['nilai']['konsentrasi']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['konsentrasi']['children'][] = $item;
                }
            }
            // Kerentanan (111-113)
            else if ($faktorId >= 111 && $faktorId <= 113) {
                if ($faktorId == 111) {
                    $result['nilai']['kerentanan']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['kerentanan']['children'][] = $item;
                }
            }
            // Lainnya
            else if ($faktorId == 114) {
                $result['nilai']['lainnya'] = $item;
            }
            // Penilaian Risiko Current
            else if ($faktorId == 115) {
                $result['nilai115'] = $item;
            }
            // Penilaian Risiko Previous
            else if ($faktorId == 116) {
                $result['nilai116'] = $item;
            }
        }

        return $result;
    }

    private function getLikuiditasKPMRWithChildren($kodebpr, $periodeId)
    {
        $result = [
            'nilai' => [
                'pengawasan' => ['kategori' => null, 'children' => []],
                'kebijakan' => ['kategori' => null, 'children' => []],
                'proses' => ['kategori' => null, 'children' => []],
                'pengendalian' => ['kategori' => null, 'children' => []]
            ],
            'nilai135' => null,
            'nilai136' => null
        ];

        $allData = $this->likuiditaskpmrModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->whereIn('faktor1id', range(118, 136))
            ->orderBy('faktor1id', 'ASC')
            ->findAll();

        foreach ($allData as $item) {
            $faktorId = $item['faktor1id'];

            // Pengawasan (118-125)
            if ($faktorId >= 118 && $faktorId <= 125) {
                if ($faktorId == 118) {
                    $result['nilai']['pengawasan']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['pengawasan']['children'][] = $item;
                }
            }
            // Kebijakan (126-129)
            else if ($faktorId >= 126 && $faktorId <= 129) {
                if ($faktorId == 126) {
                    $result['nilai']['kebijakan']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['kebijakan']['children'][] = $item;
                }
            }
            // Proses (130-132)
            else if ($faktorId >= 130 && $faktorId <= 132) {
                if ($faktorId == 130) {
                    $result['nilai']['proses']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['proses']['children'][] = $item;
                }
            }
            // Pengendalian (133-134)
            else if ($faktorId >= 133 && $faktorId <= 134) {
                if ($faktorId == 133) {
                    $result['nilai']['pengendalian']['kategori'] = $item['penilaiankredit'];
                } else {
                    $result['nilai']['pengendalian']['children'][] = $item;
                }
            }
            // Penilaian Risiko KPMR Current
            else if ($faktorId == 135) {
                $result['nilai135'] = $item;
            }
            // Penilaian Risiko KPMR Previous
            else if ($faktorId == 136) {
                $result['nilai136'] = $item;
            }
        }

        return $result;
    }

    public function generateLaporanProfilRisikoPdfBytes(): string
    {
        $periodeDetail = $this->periodeModel->find($this->periodeId);
        $bprData = $this->infobprModel->getBprByKode($this->userKodebpr);
        $showprofilresiko = $this->showprofilresikoModel
            ->getByKodebprAndPeriode($this->userKodebpr, $this->periodeId);

        if (!$showprofilresiko)
            return '';

        $logoBase64 = '';
        $logoMime = 'image/png';
        if (!empty($bprData['logo'])) {
            $logoPath = FCPATH . 'asset/img/' . $bprData['logo'];
            if (file_exists($logoPath)) {
                $logoBase64 = base64_encode(file_get_contents($logoPath));
                $logoMime = mime_content_type($logoPath);
            }
        }
        $logoTag = $logoBase64
            ? '<img src="data:' . $logoMime . ';base64,' . $logoBase64 . '" style="height:40px;width:auto">'
            : '';

        $periodeSebelum = $this->getPeriodeSebelumnya(
            $periodeDetail['semester'],
            $periodeDetail['tahun']
        );

        $getRiskColor = function ($v): string {
            switch ((int) $v) {
                case 1:
                    return '#00FF00';
                case 2:
                    return '#90EE90';
                case 3:
                    return '#FFFF00';
                case 4:
                    return '#FFA500';
                case 5:
                    return '#FF0000';
                default:
                    return '#CCCCCC';
            }
        };
        $getRiskText = function ($v): string {
            switch ((int) $v) {
                case 1:
                    return 'Sangat Rendah';
                case 2:
                    return 'Rendah';
                case 3:
                    return 'Sedang';
                case 4:
                    return 'Tinggi';
                case 5:
                    return 'Sangat Tinggi';
                default:
                    return '-';
            }
        };

        $fetch = function ($model, $faktorId) {
            return $model->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->where('faktor1id', $faktorId)
                ->first();
        };

        $kreditI = $fetch($this->kreditinherenModel, 13);
        $kreditK = $fetch($this->kreditkpmrModel, 33);
        $kreditIB = $fetch($this->kreditinherenModel, 14);
        $kreditKB = $fetch($this->kreditkpmrModel, 34);

        $opI = $fetch($this->operasionalinherenModel, 48);
        $opK = $fetch($this->operasionalkpmrModel, 70);
        $opIB = $fetch($this->operasionalinherenModel, 49);
        $opKB = $fetch($this->operasionalkpmrModel, 71);

        $kepI = $fetch($this->kepatuhaninherenModel, 81);
        $kepK = $fetch($this->kepatuhankpmrModel, 102);
        $kepIB = $fetch($this->kepatuhaninherenModel, 82);
        $kepKB = $fetch($this->kepatuhankpmrModel, 103);

        $likI = $fetch($this->likuiditasinherenModel, 115);
        $likK = $fetch($this->likuiditaskpmrModel, 135);
        $likIB = $fetch($this->likuiditasinherenModel, 116);
        $likKB = $fetch($this->likuiditaskpmrModel, 136);

        $repI = $fetch($this->reputasiinherenModel, 148);
        $repK = $fetch($this->reputasikpmrModel, 168);
        $repIB = $fetch($this->reputasiinherenModel, 149);
        $repKB = $fetch($this->reputasikpmrModel, 169);

        $strI = $fetch($this->stratejikinherenModel, 179);
        $strK = $fetch($this->stratejikkpmrModel, 199);
        $strIB = $fetch($this->stratejikinherenModel, 180);
        $strKB = $fetch($this->stratejikkpmrModel, 200);

        $tanggal = '';
        if (!empty($showprofilresiko['tanggal'])) {
            $d = \DateTime::createFromFormat('Y-m-d', $showprofilresiko['tanggal']);
            if ($d) {
                $bl = [
                    '01' => 'Januari',
                    '02' => 'Februari',
                    '03' => 'Maret',
                    '04' => 'April',
                    '05' => 'Mei',
                    '06' => 'Juni',
                    '07' => 'Juli',
                    '08' => 'Agustus',
                    '09' => 'September',
                    '10' => 'Oktober',
                    '11' => 'November',
                    '12' => 'Desember'
                ];
                $tanggal = $d->format('d') . ' ' . $bl[$d->format('m')] . ' ' . $d->format('Y');
            }
        }

        $s = fn($v) => htmlspecialchars((string) ($v ?? ''));
        $spr = $showprofilresiko;
        $sm = $s($periodeDetail['semester']);
        $th = $s($periodeDetail['tahun']);
        $smB = $s($periodeSebelum['semester']);
        $thB = $s($periodeSebelum['tahun']);

        $risikoRow = function (string $label, $iC, $kC, $nC, $iP, $kP, $nP) use ($getRiskColor): string {
            $cell = fn($v) => '<td style="text-align:center;font-weight:bold;font-size:8.5pt;'
                . 'background:' . $getRiskColor($v) . ';border:1px solid #000;padding:6px 3px;vertical-align:middle">'
                . ($v ?? '') . '</td>';
            return '<tr>'
                . '<td style="border:none solid #000;padding:6px 8px;font-size:8.5pt;vertical-align:middle">' . htmlspecialchars($label) . '</td>'
                . $cell($iC) . $cell($kC) . $cell($nC)
                . $cell($iP) . $cell($kP) . $cell($nP)
                . '</tr>';
        };

        $tableRows = $risikoRow(
            'Risiko Kredit',
            $kreditI['penilaiankredit'] ?? null,
            $kreditK['penilaiankredit'] ?? null,
            $spr['nkredit'] ?? null,
            $kreditIB['penilaiankredit'] ?? null,
            $kreditKB['penilaiankredit'] ?? null,
            $spr['nbelumkredit'] ?? null
        );
        $tableRows .= $risikoRow(
            'Risiko Operasional',
            $opI['penilaiankredit'] ?? null,
            $opK['penilaiankredit'] ?? null,
            $spr['noperasional'] ?? null,
            $opIB['penilaiankredit'] ?? null,
            $opKB['penilaiankredit'] ?? null,
            $spr['nbelumoperasional'] ?? null
        );
        $tableRows .= $risikoRow(
            'Risiko Kepatuhan',
            $kepI['penilaiankredit'] ?? null,
            $kepK['penilaiankredit'] ?? null,
            $spr['nkepatuhan'] ?? null,
            $kepIB['penilaiankredit'] ?? null,
            $kepKB['penilaiankredit'] ?? null,
            $spr['nbelumkepatuhan'] ?? null
        );
        $tableRows .= $risikoRow(
            'Risiko Likuiditas',
            $likI['penilaiankredit'] ?? null,
            $likK['penilaiankredit'] ?? null,
            $spr['nlikuiditas'] ?? null,
            $likIB['penilaiankredit'] ?? null,
            $likKB['penilaiankredit'] ?? null,
            $spr['nbelumlikuiditas'] ?? null
        );

        if (!empty($repI)) {
            $tableRows .= $risikoRow(
                'Risiko Reputasi',
                $repI['penilaiankredit'] ?? null,
                $repK['penilaiankredit'] ?? null,
                $spr['nreputasi'] ?? null,
                $repIB['penilaiankredit'] ?? null,
                $repKB['penilaiankredit'] ?? null,
                $spr['nbelumreputasi'] ?? null
            );
        }
        if (!empty($strI)) {
            $tableRows .= $risikoRow(
                'Risiko Stratejik',
                $strI['penilaiankredit'] ?? null,
                $strK['penilaiankredit'] ?? null,
                $spr['nstratejik'] ?? null,
                $strIB['penilaiankredit'] ?? null,
                $strKB['penilaiankredit'] ?? null,
                $spr['nbelumstratejik'] ?? null
            );
        }

        $pcC = $getRiskColor($spr['ntotalrisk'] ?? null);
        $pcP = $getRiskColor($spr['nbelumtotalrisk'] ?? null);
        $tableRows .= '<tr style="background:#aaaaaa">
        <td colspan="3" style="border:1px solid #000;padding:6px 8px;font-weight:bold;font-size:8.5pt;vertical-align:middle">Peringkat Risiko</td>
        <td style="text-align:center;font-weight:bold;font-size:10pt;background:' . $pcC . ';border:1px solid #000;padding:6px;vertical-align:middle">' . ($spr['ntotalrisk'] ?? '') . '</td>
        <td colspan="2" style="border:1px solid #000"></td>
        <td style="text-align:center;font-weight:bold;font-size:10pt;background:' . $pcP . ';border:1px solid #000;padding:6px;vertical-align:middle">' . ($spr['nbelumtotalrisk'] ?? '') . '</td>
    </tr>';

        $analisisList = [
            'Kredit' => [$kreditI, $kreditK],
            'Operasional' => [$opI, $opK],
            'Kepatuhan' => [$kepI, $kepK],
            'Likuiditas' => [$likI, $likK],
        ];
        if (!empty($repI))
            $analisisList['Reputasi'] = [$repI, $repK];
        if (!empty($strI))
            $analisisList['Stratejik'] = [$strI, $strK];

        $analisisHtml = '';
        foreach ($analisisList as $jenis => [$inh, $kpmr]) {
            $ni = $inh['penilaiankredit'] ?? 0;
            $nk = $kpmr['penilaiankredit'] ?? 0;
            $analisisHtml .= '<li style="margin-bottom:4px;font-size:9pt">Risiko ' . $jenis
                . ': Risiko Inheren peringkat ' . $ni . ' (' . $getRiskText($ni)
                . '), Risiko KPMR peringkat ' . $nk . ' (' . $getRiskText($nk) . ')</li>';
        }

        $profilRows = [
            ['Periode', "Semester {$sm} Tahun {$th}"],
            ['Nama BPR', $bprData['namabpr'] ?? ''],
            ['Alamat', $bprData['alamat'] ?? ''],
            ['Nomor Telepon', $bprData['nomor'] ?? ''],
            ['Modal Inti', $periodeDetail['modalinti'] ?? ''],
            ['Total Aset', $periodeDetail['totalaset'] ?? ''],
            ['Jumlah Kantor Cabang', $periodeDetail['kantorcabang'] ?? ''],
            ['Kegiatan sebagai penerbit kartu ATM atau kartu debit', $periodeDetail['atmdebit'] ?? ''],
        ];
        $profilHtml = '';
        foreach ($profilRows as [$label, $value]) {
            $profilHtml .= '<tr>
            <td style="width:230px;padding:3px 6px;font-size:8.5pt;vertical-align:top">'
                . htmlspecialchars($label) . '</td>
            <td style="width:12px;padding:3px 2px;vertical-align:top;font-size:8.5pt">:</td>
            <td style="padding:3px 6px;font-size:8.5pt;vertical-align:top">'
                . htmlspecialchars($value) . '</td>
        </tr>';
        }

        $kesimpulanHtml = '';
        if (!empty($spr['kesimpulan'])) {
            $kesimpulanHtml = '
        <div style="border:1px solid #000;padding:10px 12px;margin-top:12px;font-size:9pt">
            <div style="font-weight:bold;margin-bottom:8px;font-size:9.5pt">Kesimpulan</div>
            <div style="font-size:9pt;line-height:1.5">' . nl2br(htmlspecialchars($spr['kesimpulan'])) . '</div>
        </div>';
        }

        $headerHtml = '
    <table style="width:100%;border-collapse:collapse;margin-bottom:0;padding-bottom:6px">
        <tr>
            <td style="width:90px;vertical-align:middle;padding-right:8px">' . $logoTag . '</td>
            <td style="text-align:center;vertical-align:middle">
                <div style="font-size:12pt;font-weight:bold;line-height:1.4">' . $s($bprData['namabpr']) . '</div>
                <div style="font-size:8pt;margin-top:2px">' . $s($bprData['alamat']) . '</div>
                <div style="font-size:8pt;margin-top:1px">Telepon: ' . $s($bprData['nomor']) . '</div>
                <div style="font-size:8pt;margin-top:1px">Website: ' . $s($bprData['webbpr'] ?? '-')
            . '&nbsp;&nbsp;&nbsp;Email: ' . $s($bprData['email'] ?? '-') . '</div>
            </td>
        </tr>
    </table>
    <div style="border-top:2.5px solid #000;margin-top:6px;margin-bottom:14px"></div>';
        $getKeteranganRows = function (string $riskType, int $faktorId): array {
            $ranges = $this->getFaktorRanges($riskType, $faktorId > 100);

            $modelMap = [
                'kredit' => [$this->kreditinherenModel, $this->kreditkpmrModel],
                'operasional' => [$this->operasionalinherenModel, $this->operasionalkpmrModel],
                'kepatuhan' => [$this->kepatuhaninherenModel, $this->kepatuhankpmrModel],
                'likuiditas' => [$this->likuiditasinherenModel, $this->likuiditaskpmrModel],
                'reputasi' => [$this->reputasiinherenModel, $this->reputasikpmrModel],
                'stratejik' => [$this->stratejikinherenModel, $this->stratejikkpmrModel],
            ];

            $kpmrFaktorIds = [33, 34, 70, 71, 102, 103, 135, 136, 168, 169, 199, 200];
            $isKpmr = in_array($faktorId, $kpmrFaktorIds);
            $model = $modelMap[$riskType][$isKpmr ? 1 : 0];

            return $model->where('kodebpr', $this->userKodebpr)
                ->where('periode_id', $this->periodeId)
                ->whereIn('faktor1id', $ranges)
                ->orderBy('faktor1id', 'ASC')
                ->findAll();
        };

        $risikoArray = [
            ['key' => 'kredit', 'title' => 'ANALISIS RISIKO KREDIT', 'iF' => 13, 'kF' => 33],
            ['key' => 'operasional', 'title' => 'ANALISIS RISIKO OPERASIONAL', 'iF' => 48, 'kF' => 70],
            ['key' => 'kepatuhan', 'title' => 'ANALISIS RISIKO KEPATUHAN', 'iF' => 81, 'kF' => 102],
            ['key' => 'likuiditas', 'title' => 'ANALISIS RISIKO LIKUIDITAS', 'iF' => 115, 'kF' => 135],
        ];
        if (!empty($repI))
            $risikoArray[] = ['key' => 'reputasi', 'title' => 'ANALISIS RISIKO REPUTASI', 'iF' => 148, 'kF' => 168];
        if (!empty($strI))
            $risikoArray[] = ['key' => 'stratejik', 'title' => 'ANALISIS RISIKO STRATEJIK', 'iF' => 179, 'kF' => 199];

        // Map key → nilai current
        $risikoNilai = [
            'kredit' => ['iC' => $kreditI['penilaiankredit'] ?? null, 'kC' => $kreditK['penilaiankredit'] ?? null, 'n' => $spr['nkredit'] ?? null],
            'operasional' => ['iC' => $opI['penilaiankredit'] ?? null, 'kC' => $opK['penilaiankredit'] ?? null, 'n' => $spr['noperasional'] ?? null],
            'kepatuhan' => ['iC' => $kepI['penilaiankredit'] ?? null, 'kC' => $kepK['penilaiankredit'] ?? null, 'n' => $spr['nkepatuhan'] ?? null],
            'likuiditas' => ['iC' => $likI['penilaiankredit'] ?? null, 'kC' => $likK['penilaiankredit'] ?? null, 'n' => $spr['nlikuiditas'] ?? null],
            'reputasi' => ['iC' => $repI['penilaiankredit'] ?? null, 'kC' => $repK['penilaiankredit'] ?? null, 'n' => $spr['nreputasi'] ?? null],
            'stratejik' => ['iC' => $strI['penilaiankredit'] ?? null, 'kC' => $strK['penilaiankredit'] ?? null, 'n' => $spr['nstratejik'] ?? null],
        ];

        $halamanBHtml = '';
        foreach ($risikoArray as $idx => $risiko) {
            $nilaiRisiko = $risikoNilai[$risiko['key']];
            $tingkat = $nilaiRisiko['n'];

            $inherenRows = $getKeteranganRows($risiko['key'], $risiko['iF']);
            $kpmrRows = $getKeteranganRows($risiko['key'], $risiko['kF']);

            // Build list keterangan inheren
            $inherenList = '';
            if (!empty($inherenRows)) {
                foreach ($inherenRows as $i => $row) {
                    $inherenList .= '<li style="margin-bottom:4px;font-size:9pt;line-height:1.5">'
                        . htmlspecialchars($row['keterangan'] ?? '') . '</li>';
                }
                $inherenList = '<ol style="margin:4px 0 0 22px;padding:0;font-size:9pt">' . $inherenList . '</ol>';
            } else {
                $inherenList = '<p style="font-size:9pt;margin:4px 0 0 0">Tingkat Risiko Inheren: '
                    . ($nilaiRisiko['iC'] ?? '-') . ' (' . $getRiskText($nilaiRisiko['iC']) . ')</p>';
            }

            // Build list keterangan KPMR
            $kpmrList = '';
            if (!empty($kpmrRows)) {
                foreach ($kpmrRows as $i => $row) {
                    $kpmrList .= '<li style="margin-bottom:4px;font-size:9pt;line-height:1.5">'
                        . htmlspecialchars($row['keterangan'] ?? '') . '</li>';
                }
                $kpmrList = '<ol style="margin:4px 0 0 22px;padding:0;font-size:9pt">' . $kpmrList . '</ol>';
            } else {
                $kpmrList = '<p style="font-size:9pt;margin:4px 0 0 0">Tingkat KPMR: '
                    . ($nilaiRisiko['kC'] ?? '-') . ' (' . $getRiskText($nilaiRisiko['kC']) . ')</p>';
            }

            $judulB = ($idx === 0)
                ? '<div style="font-size:11pt;font-weight:bold;margin-bottom:14px">B. ANALISIS PER JENIS RISIKO</div>'
                : '';

            $halamanBHtml .= '
        <div style="page-break-before:always">
            ' . $headerHtml . '
            ' . $judulB . '
            <div style="font-size:10.5pt;font-weight:bold;margin-bottom:10px;margin-left:10px">' . $risiko['title'] . '</div>

            <table class="no-border" style="margin-bottom:12px;font-size:8.5pt;margin-left:10px">
                <tr>
                    <td style="width:80px;padding:2px 0;vertical-align:top">Nama BPR</td>
                    <td style="width:12px;padding:2px 0;vertical-align:top">:</td>
                    <td style="padding:2px 0;vertical-align:top">' . $s($bprData['namabpr']) . '</td>
                </tr>
                <tr>
                    <td style="padding:2px 0;vertical-align:top">Periode</td>
                    <td style="padding:2px 0;vertical-align:top">:</td>
                    <td style="padding:2px 0;vertical-align:top">Semester ' . $sm . ' Tahun ' . $th . '</td>
                </tr>
            </table>

            <!-- Box Analisis (bagian 1 & 2) -->
            <div style="border:1px solid #000;padding:12px 14px;font-size:9pt;margin-bottom:10px">
                <div style="font-weight:bold;font-size:10pt;margin-bottom:10px">Analisis</div>

                <div style="font-weight:bold;font-size:9.5pt;margin-bottom:6px">1. Tingkat Risiko:</div>
                <p style="margin:0 0 12px 16px;font-size:9pt">Peringkat ' . ($tingkat ?? '-')
                . ', ' . $getRiskText($tingkat) . '</p>

                <div style="font-weight:bold;font-size:9.5pt;margin-bottom:6px">2. Risiko Inheren:</div>
                <div style="margin-left:10px">' . $inherenList . '</div>
            </div>

            <!-- Box KPMR (bagian 3) -->
            <div style="border:1px solid #000;padding:12px 14px;font-size:9pt">
                <div style="font-weight:bold;font-size:9.5pt;margin-bottom:6px">3. Kualitas Penerapan Manajemen Risiko:</div>
                <div style="margin-left:10px">' . $kpmrList . '</div>
            </div>
        </div>';
        }
        $ttdHtml = '
    <div style="page-break-before:always">
        ' . $headerHtml . '
        <div style="margin-top:90px;text-align:center;font-size:10pt">
            <p style="margin-bottom:6px">' . $s($spr['lokasi'] ?? '') . ', ' . $s($tanggal) . '</p>
            <p style="font-weight:bold;font-size:11pt">' . $s($bprData['namabpr']) . '</p>
            <div style="margin-top:75px">
                <div style="display:inline-block;text-align:center">
                    <div style="font-weight:bold;border-bottom:1px solid #000;
                                padding:0 20px 4px 20px;display:inline-block;font-size:10.5pt">
                        ' . $s($spr['dirkep'] ?? '') . '
                    </div>
                    <div style="margin-top:5px;font-size:10pt">Direktur</div>
                </div>
            </div>
        </div>
    </div>';
        $html = '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>

  @page {
    margin: 30px 30px 30px 30px;
  }

  body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 9pt;
    color: #000;
  }

  .container {
    width: 100%;
  }

  table {
    border-collapse: collapse;
    width: 100%;
  }

  th, td {
    border: 1px solid #000;
  }

  .no-border td {
    border: none;
  }

  .header-line {
    border-top: 2px solid #000;
    margin: 10px 0 15px 0;
  }

  .title {
    text-align: center;
    font-weight: bold;
    font-size: 13pt;
    margin-bottom: 15px;
  }

  .section-title {
    font-weight: bold;
    font-size: 10.5pt;
    margin-bottom: 8px;
  }

  .box {
    border: 1px solid #000;
    padding: 10px;
    margin-top: 10px;
  }

  .page-break {
    page-break-before: always;
  }

</style>
</head>

<body>

<div class="container">

' . $headerHtml . '

<div class="title">LAPORAN PROFIL RISIKO</div>

<div class="section-title">A. PROFIL RISIKO</div>

<table class="no-border" style="margin-bottom:15px;font-size:9pt">
' . $profilHtml . '
</table>

<!-- TABLE RISIKO -->
<table style="font-size:8pt;margin-bottom:10px">

<thead>
<tr>
<th rowspan="2" style="width:120px;background:#d9d9d9">Jenis Risiko</th>
<th colspan="3" style="background:#d9d9d9">Penilaian Per Posisi</th>
<th colspan="3" style="background:#d9d9d9">
Penilaian Posisi Sebelumnya<br>
<span style="font-size:7pt">(Semester ' . $smB . ' Tahun ' . $thB . ')</span>
</th>
</tr>

<tr>
<th>Inheren</th>
<th>KPMR</th>
<th>Risiko</th>
<th>Inheren</th>
<th>KPMR</th>
<th>Risiko</th>
</tr>
</thead>

<tbody>
' . $tableRows . '
</tbody>

</table>

<p style="text-align:center;font-size:8pt;color:#555;margin-bottom:12px">
Keterangan Peringkat: 1 (Sangat Rendah), 2 (Rendah), 3 (Sedang), 4 (Tinggi), 5 (Sangat Tinggi)
</p>

<!-- ANALISIS -->
<div class="box">
<b>Analisis</b>
<p style="margin-top:5px">
Hasil dari analisis profil risiko Semester ' . $sm . ' Tahun ' . $th . ' sebagai berikut:
</p>
<ul style="margin-left:15px;margin-top:5px">
' . $analisisHtml . '
</ul>
</div>

' . $kesimpulanHtml . '

' . $halamanBHtml . '

' . $ttdHtml . '

</div>
</body>
</html>';
        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Arial');
        $options->set('isFontSubsettingEnabled', true);
        $options->set('defaultMediaType', 'print');

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');

        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    public function generateLembarPernyataanPdfBytes(): string
    {
        $periodeDetail = $this->periodeModel->find($this->periodeId);
        $bprData = $this->infobprModel->getBprByKode($this->userKodebpr);
        $showprofilresiko = $this->showprofilresikoModel
            ->getByKodebprAndPeriode($this->userKodebpr, $this->periodeId);

        if (!$showprofilresiko)
            return '';

        $logoBase64 = '';
        $logoMime = 'image/png';
        if (!empty($bprData['logo'])) {
            $logoPath = FCPATH . 'asset/img/' . $bprData['logo'];
            if (file_exists($logoPath)) {
                $logoBase64 = base64_encode(file_get_contents($logoPath));
                $logoMime = mime_content_type($logoPath);
            }
        }
        $logoTag = $logoBase64
            ? '<img src="data:' . $logoMime . ';base64,' . $logoBase64 . '" style="height:40px;width:auto">'
            : '';

        $s = fn($v) => htmlspecialchars((string) ($v ?? ''));
        $spr = $showprofilresiko;
        $sm = $s($periodeDetail['semester']);
        $th = $s($periodeDetail['tahun']);

        $tanggalTampil = '';
        if (!empty($spr['tanggal'])) {
            $d = \DateTime::createFromFormat('Y-m-d', $spr['tanggal']);
            if (!$d)
                $d = \DateTime::createFromFormat('d/m/Y', $spr['tanggal']);
            if ($d) {
                $bl = [
                    '01' => 'Januari',
                    '02' => 'Februari',
                    '03' => 'Maret',
                    '04' => 'April',
                    '05' => 'Mei',
                    '06' => 'Juni',
                    '07' => 'Juli',
                    '08' => 'Agustus',
                    '09' => 'September',
                    '10' => 'Oktober',
                    '11' => 'November',
                    '12' => 'Desember',
                ];
                $tanggalTampil = $d->format('d') . ' ' . ($bl[$d->format('m')] ?? '') . ' ' . $d->format('Y');
            }
        }

        $headerHtml = '
    <table style="width:100%;border-collapse:collapse;margin-bottom:0;padding-bottom:6px">
        <tr>
            <td style="width:90px;vertical-align:middle;padding-right:8px">' . $logoTag . '</td>
            <td style="text-align:center;vertical-align:middle">
                <div style="font-size:12pt;font-weight:bold;line-height:1.4">' . $s($bprData['namabpr']) . '</div>
                <div style="font-size:8pt;margin-top:2px">' . $s($bprData['alamat']) . '</div>
                <div style="font-size:8pt;margin-top:1px">Telepon: ' . $s($bprData['nomor']) . '</div>
                <div style="font-size:8pt;margin-top:1px">Website: ' . $s($bprData['webbpr'] ?? '-')
            . '&nbsp;&nbsp;&nbsp;Email: ' . $s($bprData['email'] ?? '-') . '</div>
            </td>
            <td style="width:90px"></td>
        </tr>
    </table>
    <div style="border-top:2.5px solid #000;margin-top:6px;margin-bottom:20px"></div>';

        $html = '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  @page { margin: 30px 30px 30px 30px; }
  body  { font-family: Arial, Helvetica, sans-serif; font-size: 10pt; color: #000; }
  table { border-collapse: collapse; width: 100%; }
  .no-border td { border: none; }
</style>
</head>
<body>
 
<div style="width:100%">
 
' . $headerHtml . '
 
<!-- ======== JUDUL ======== -->
<div style="text-align:center;font-size:14pt;font-weight:bold;
            letter-spacing:1px;margin-bottom:30px">
    LEMBAR PERNYATAAN
</div>
 
<!-- ======== ISI ======== -->
<div style="text-align:center;margin-bottom:8px;line-height:1.7">
    Dengan ini kami menyatakan bahwa
</div>
<div style="text-align:center;font-weight:bold;margin-bottom:8px;line-height:1.7">
    Laporan Profil Risiko ' . $s($bprData['namabpr']) . '
</div>
<div style="text-align:center;font-weight:bold;margin-bottom:20px;line-height:1.7">
    Semester ' . $sm . ' Tahun ' . $th . '
</div>
 
<div style="margin:14px auto;width:85%;text-align:left;line-height:1.8;font-size:9.5pt">
    <p style="margin-bottom:10px">
        Telah disusun sesuai dengan hasil penilaian atas Penerapan Manajemen Risiko BPR
        yang mengacu pada ketentuan OJK sebagai berikut:
    </p>
    <p style="margin-bottom:6px">
        1. POJK No. 13/POJK.03/2015 tentang Penerapan Manajemen Risiko bagi BPR
        tanggal 12 November 2015.
    </p>
    <p>
        2. Surat Edaran OJK (SEOJK) No. 1/SEOJK.03/2019 tentang Penerapan Manajemen
        Risiko bagi BPR tanggal 21 Januari 2019.
    </p>
</div>
 
<!-- ======== TANDA TANGAN ======== -->
<div style="margin-top:40px;text-align:center;font-size:10pt">
 
    <p style="margin-bottom:4px">' . $s($spr['lokasi'] ?? '') . ', ' . $tanggalTampil . '</p>
    <p style="font-weight:bold;font-size:11pt;margin-bottom:4px">' . $s($bprData['namabpr']) . '</p>
 
    <!-- Penyusun -->
    <p style="font-weight:bold;margin-bottom:0">Penyusun</p>
    <div style="height:80px"></div>
    <p style="font-weight:bold;margin-bottom:2px">' . $s($spr['pe'] ?? '') . '</p>
    <p style="font-size:9pt">Pejabat Eksekutif Manajemen Risiko</p>
 
    <!-- Menyetujui -->
    <p style="font-weight:bold;margin-top:30px;margin-bottom:0">Menyetujui</p>
 
    <table class="no-border" style="margin-top:0;width:100%">
        <tr>
            <!-- Direktur Utama -->
            <td style="width:50%;text-align:center;vertical-align:top;padding-top:0">
                <div style="height:80px"></div>
                <div style="font-weight:bold;font-size:10pt">' . $s($spr['dirut'] ?? '') . '</div>
                <div style="font-size:9pt">Direktur Utama</div>
            </td>
            <!-- Direktur Kepatuhan -->
            <td style="width:50%;text-align:center;vertical-align:top;padding-top:0">
                <div style="height:80px"></div>
                <div style="font-weight:bold;font-size:10pt">' . $s($spr['dirkep'] ?? '') . '</div>
                <div style="font-size:9pt">Direktur</div>
                <div style="font-size:9pt">fungsi kepatuhan</div>
            </td>
        </tr>
    </table>
 
</div>
 
</div>
</body>
</html>';

        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Arial');

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $output = $dompdf->output();

        if (empty($output)) {
            throw new \RuntimeException('Dompdf->output() menghasilkan string kosong.');
        }

        return $output;
    }


}