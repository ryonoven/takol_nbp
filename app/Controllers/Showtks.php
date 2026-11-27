<?php

namespace App\Controllers;

use App\Libraries\LaporanTKSPdfGenerator;
use App\Models\M_infobpr;
use App\Models\M_periodesemesteran;
use App\Models\M_showtks;
use App\Models\M_tkspermodalan;
use App\Models\M_tksprofilrisiko;
use App\Models\M_tksrentabilitas;
use App\Models\M_tkstatakelola;
use App\Models\M_user;
use CodeIgniter\Controller;
use Myth\Auth\Config\Services as AuthServices;

class Showtks extends Controller
{
    protected $auth;
    protected $userId;
    protected $userKodebpr;
    protected $userGroups = [];
    protected $periodeId;

    protected $periodeModel;
    protected $showtksModel;
    protected $tksprofilrisikoModel;
    protected $tkstatakelolaModel;
    protected $tksrentabilitasModel;
    protected $tkspermodalanModel;
    protected $userModel;
    protected $infobprModel;

    private array $queryCache = [];

    /** Field lembar persetujuan => [label, panjang maks]. */
    public const TTD_FIELDS = [
        'dirut' => ['Nama Direktur Utama', 255],
        'dirkep' => ['Nama Direktur yang membawahi fungsi kepatuhan', 255],
        'pe' => ['Nama Pejabat Eksekutif Manajemen Risiko', 255],
        'lokasi' => ['Lokasi', 255],
    ];
    public const MAX_KESIMPULAN = 4000;

    public function __construct()
    {
        $this->auth = service('authentication');
        $this->infobprModel = new M_infobpr();
        $this->periodeModel = new M_periodesemesteran();
        $this->showtksModel = new M_showtks();
        $this->tksprofilrisikoModel = new M_tksprofilrisiko();
        $this->tkstatakelolaModel = new M_tkstatakelola();
        $this->tksrentabilitasModel = new M_tksrentabilitas();
        $this->tkspermodalanModel = new M_tkspermodalan();
        $this->userModel = new M_user();

        $this->initializeUser();
    }

    private function initializeUser(): void
    {
        $this->userGroups = array_fill_keys(['pe', 'admin', 'dekom', 'direksi'], false);
        $this->userKodebpr = null;
        if ($this->auth->isLoggedIn()) {
            $this->userId = (int) $this->auth->id();
            $user = $this->userModel->find($this->userId);
            $this->userKodebpr = $user['kodebpr'] ?? null;

            $authorize = AuthServices::authorization();
            foreach (array_keys($this->userGroups) as $group) {
                $this->userGroups[$group] = (bool) $authorize->inGroup($group, $this->userId);
            }
        }

        // Periode wajib milik BPR user (cegah akses data BPR lain lewat session active_periode)
        $pid = (int) session('active_periode');
        $p = ($pid > 0 && $this->userKodebpr) ? $this->cachedPeriodeDetail($pid) : [];
        $this->periodeId = ($p && (string) ($p['kodebpr'] ?? '') === (string) $this->userKodebpr) ? $pid : null;
    }

    private function canEdit(): bool
    {
        return $this->userGroups['pe'] || $this->userGroups['admin'];
    }

    private function memo(string $key, callable $fn)
    {
        if (!array_key_exists($key, $this->queryCache)) {
            $this->queryCache[$key] = $fn();
        }
        return $this->queryCache[$key];
    }

    private function cachedTksProfilRisiko(string $kodebpr, $periodeId)
    {
        return $this->memo("pr_{$kodebpr}_{$periodeId}", fn() => $this->tksprofilrisikoModel->getByKodebprAndPeriode($kodebpr, $periodeId) ?: []);
    }

    private function cachedTksTataKelola(string $kodebpr, $periodeId)
    {
        return $this->memo("tk_{$kodebpr}_{$periodeId}", fn() => $this->tkstatakelolaModel->getByKodebprAndPeriode($kodebpr, $periodeId) ?: []);
    }

    private function cachedTksRentabilitas(string $kodebpr, $periodeId)
    {
        return $this->memo("re_{$kodebpr}_{$periodeId}", fn() => $this->tksrentabilitasModel->getByKodebprAndPeriode($kodebpr, $periodeId) ?: []);
    }

    private function cachedTksPermodalan(string $kodebpr, $periodeId)
    {
        return $this->memo("pm_{$kodebpr}_{$periodeId}", fn() => $this->tkspermodalanModel->getByKodebprAndPeriode($kodebpr, $periodeId) ?: []);
    }

    private function cachedShowtks(string $kodebpr, $periodeId)
    {
        return $this->memo("st_{$kodebpr}_{$periodeId}", fn() => $this->showtksModel->getByKodebprAndPeriode($kodebpr, $periodeId) ?: []);
    }

    private function cachedPeriodeDetail($periodeId)
    {
        return $this->memo("periode_{$periodeId}", fn() => $this->periodeModel->find($periodeId) ?: []);
    }

    // ------------------------------------------------------------------ helpers

    private function sanitizeText(string $text): string
    {
        $text = str_replace(["\r", "\n"], ' ', $text);
        $text = str_replace(
            ["\u{201C}", "\u{201D}", "\u{2018}", "\u{2019}", '%', '|'],
            ['"', '"', "'", "'", ' persen ', ' '],
            $text
        );
        $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
        return trim(preg_replace('/\s+/', ' ', $text));
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
            return redirect()->to('/Periodesemesteran')->with('error', 'Silakan pilih periode semesteran aktif terlebih dahulu.');
        }
        return null;
    }

    private function json(int $status, array $payload)
    {
        $payload['csrf'] = ['name' => csrf_token(), 'hash' => csrf_hash()];
        return $this->response->setStatusCode($status)->setJSON($payload);
    }

    /** Data 4 faktor TKS untuk tampilan & PDF. */
    private function getFaktorTKSData(string $kodebpr, $periodeId): array
    {
        $pr = $this->cachedTksProfilRisiko($kodebpr, $periodeId);
        $tk = $this->cachedTksTataKelola($kodebpr, $periodeId);
        $re = $this->cachedTksRentabilitas($kodebpr, $periodeId);
        $pm = $this->cachedTksPermodalan($kodebpr, $periodeId);

        $f = static fn(string $nama, string $link, string $bobot, array $row, string $pk, string $pkb, string $an) => [
            'nama_faktor' => $nama,
            'url' => base_url($link),
            'bobot' => $bobot,
            'filled' => (bool) $row,
            'nilai_peringkat' => $row[$pk] ?? null,
            'nilai_peringkat_before' => $row[$pkb] ?? null,
            'nilai_faktor' => $row['nilaifaktor'] ?? null,
            'nilai_faktor_before' => $row['nilaifaktorbefore'] ?? null,
            'analisis' => $row[$an] ?? '',
            'is_approved' => (int) ($row['is_approved'] ?? 0),
        ];

        return [
            'profil_risiko' => $f('Faktor Profil Risiko', 'Tksprofilrisiko', '25%', $pr, 'nilaiperingkat', 'nilaiperingkatbefore', 'analisis'),
            'tata_kelola' => $f('Faktor Tata Kelola', 'Tkstatakelola', '30%', $tk, 'nilaiperingkat', 'nilaiperingkatbefore', 'analisis'),
            'rentabilitas' => $f('Faktor Rentabilitas', 'Tksrentabilitas', '15%', $re, 'peringkatrentabilitas', 'peringkatrentabilitasbefore', 'analisarentabilitas'),
            'permodalan' => $f('Faktor Permodalan', 'Tkspermodalan', '30%', $pm, 'penilaianpermodalan', 'penilaianpermodalanbefore', 'analisispermodalan'),
        ];
    }

    /**
     * Nilai yang disimpan ke tabel showtks (dipakai TXT). Analisis faktor selalu diisi (null bila faktor belum diisi)
     * agar tidak tersisa teks lama.
     */
    private function getTksData(string $kodebpr, $periodeId): array
    {
        $map = [
            ['profil_risiko', 'nprofilrisiko', 'nfaktorprofilrisiko', 'kesimpulan2'],
            ['tata_kelola', 'ntatakelola', 'nfaktortakol', 'kesimpulan3'],
            ['rentabilitas', 'nrentabilitas', 'nfaktorrentabilitas', 'kesimpulan4'],
            ['permodalan', 'npermodalan', 'nfaktorpermodalan', 'kesimpulan5'],
        ];
        $faktor = $this->getFaktorTKSData($kodebpr, $periodeId);
        $out = [];
        foreach ($map as [$key, $n, $nf, $kes]) {
            $d = $faktor[$key];
            $out[$n] = $d['nilai_peringkat'];
            $out[$n . 'before'] = $d['nilai_peringkat_before'];
            $out[$nf] = $d['nilai_faktor'];
            $out[$nf . 'before'] = $d['nilai_faktor_before'];
            $out[$kes] = $d['analisis'] !== '' ? $d['analisis'] : null;
        }
        return $out;
    }

    private function calculateKompositTKS(array $tksData): array
    {
        $sum = static function (array $fields) use ($tksData) {
            $vals = array_filter(array_map(static fn($f) => $tksData[$f] ?? null, $fields), 'is_numeric');
            return $vals ? array_sum($vals) : null;
        };
        $nkomposittks = $sum(['nfaktorprofilrisiko', 'nfaktortakol', 'nfaktorrentabilitas', 'nfaktorpermodalan']);
        $nkomposittksbefore = $sum(['nfaktorprofilrisikobefore', 'nfaktortakolbefore', 'nfaktorrentabilitasbefore', 'nfaktorpermodalanbefore']);

        return [
            'nkomposittks' => $nkomposittks,
            'nkomposittksbefore' => $nkomposittksbefore,
            'peringkatkomposittks' => $this->getPeringkatKomposit($nkomposittks) ?? 0,
            'peringkatkomposittksbefore' => $this->getPeringkatKomposit($nkomposittksbefore) ?? 0,
        ];
    }

    private function getPeringkatKomposit($nilai): ?int
    {
        if ($nilai === null || !is_numeric($nilai)) {
            return null;
        }
        foreach ([[1.00, 1.50, 1], [1.51, 2.60, 2], [2.61, 3.40, 3], [3.41, 4.20, 4], [4.21, 5.00, 5]] as [$lo, $hi, $p]) {
            if ($nilai >= $lo && $nilai <= $hi) {
                return $p;
            }
        }
        return null;
    }

    /** Sinkronkan nilai faktor + komposit ke tabel showtks; hanya menulis bila ada perubahan. */
    private function syncShowtks(string $kodebpr, int $periodeId, array $periode): array
    {
        $data = $this->getTksData($kodebpr, $periodeId);
        $data += $this->calculateKompositTKS($data);
        $data['tahun'] = $periode['tahun'] ?? date('Y');
        // Kolom NOT NULL (MySQL strict): null → 0, kecuali kolom yang memang nullable
        $nullable = ['nkomposittks', 'nkomposittksbefore', 'kesimpulan2', 'kesimpulan3', 'kesimpulan4', 'kesimpulan5'];
        foreach ($data as $k => $v) {
            if ($v === null && !in_array($k, $nullable, true)) {
                $data[$k] = 0;
            }
        }

        $row = $this->cachedShowtks($kodebpr, $periodeId);
        if (!$row) {
            $tahun = (int) $data['tahun'];
            $this->showtksModel->insert($data + [
                'kodebpr' => $kodebpr,
                'periode_id' => $periodeId,
                'dirut' => '', 'dirkep' => '', 'pe' => '', 'lokasi' => '', 'kesimpulan' => '',
                // tanggal NOT NULL tanpa default: isi tanggal akhir semester (dapat diubah user)
                'tanggal' => ((string) ($periode['semester'] ?? '1') === '1') ? "{$tahun}-06-30" : "{$tahun}-12-31",
            ]);
        } else {
            $diff = [];
            foreach ($data as $k => $v) {
                $cur = $row[$k] ?? null;
                $same = (is_numeric($cur) && is_numeric($v)) ? abs((float) $cur - (float) $v) < 0.0001 : (string) $cur === (string) $v;
                if (!$same) {
                    $diff[$k] = $v;
                }
            }
            if ($diff) {
                $this->showtksModel->update((int) $row['id'], $diff + ['kodebpr' => $kodebpr, 'periode_id' => $periodeId]);
            }
        }
        unset($this->queryCache["st_{$kodebpr}_{$periodeId}"]);
        return $this->cachedShowtks($kodebpr, $periodeId);
    }

    // ------------------------------------------------------------------ halaman

    public function index()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }
        if ($redirect = $this->checkPeriode()) {
            return $redirect;
        }

        $kodebpr = $this->userKodebpr;
        $periode = $this->cachedPeriodeDetail($this->periodeId);
        $showtks = $this->syncShowtks($kodebpr, (int) $this->periodeId, $periode);
        $faktor = $this->getFaktorTKSData($kodebpr, $this->periodeId);

        $data = [
            'judul' => 'Laporan Tingkat Kesehatan BPR (TKS)',
            'bprData' => $this->infobprModel->getBprByKode($kodebpr) ?? [],
            'periodeDetail' => $periode,
            'showtks' => $showtks,
            'faktorTKSData' => $faktor,
            'kompositData' => [
                'nkomposittks' => $showtks['nkomposittks'] ?? null,
                'nkomposittksbefore' => $showtks['nkomposittksbefore'] ?? null,
                'peringkatkomposittks' => (int) ($showtks['peringkatkomposittks'] ?? 0),
                'peringkatkomposittksbefore' => (int) ($showtks['peringkatkomposittksbefore'] ?? 0),
            ],
            'canEdit' => $this->canEdit(),
        ];

        return view('templates/v_header', $data)
            . view('templates/v_sidebar')
            . view('templates/v_topbar')
            . view('Laporansemester/Tks/showtks', $data)
            . view('templates/v_footer');
    }

    /** Simpan kesimpulan + lembar persetujuan (satu permintaan). */
    public function save()
    {
        if (strtolower((string) $this->request->getMethod()) !== 'post') {
            return $this->json(405, ['ok' => false, 'message' => 'Metode tidak diizinkan.']);
        }
        if (!$this->auth->check()) {
            return $this->json(401, ['ok' => false, 'message' => 'Sesi berakhir. Silakan login kembali.']);
        }
        if (!$this->periodeId || !$this->userKodebpr) {
            return $this->json(422, ['ok' => false, 'message' => 'Periode semesteran aktif tidak ditemukan.']);
        }
        if (!$this->canEdit()) {
            return $this->json(403, ['ok' => false, 'message' => 'Anda tidak memiliki akses untuk mengubah data ini.']);
        }

        $data = [];
        $errors = [];
        $kes = trim((string) $this->request->getPost('kesimpulan'));
        if (mb_strlen($kes) > self::MAX_KESIMPULAN) {
            $errors['kesimpulan'] = 'Maksimal ' . self::MAX_KESIMPULAN . ' karakter.';
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
        }
        if ($tgl !== '') {
            $data['tanggal'] = $tgl; // kolom NOT NULL: tanggal kosong = tidak diubah
        }
        if ($errors) {
            return $this->json(422, ['ok' => false, 'message' => 'Periksa kembali isian yang ditandai.', 'errors' => $errors]);
        }

        $row = $this->syncShowtks($this->userKodebpr, (int) $this->periodeId, $this->cachedPeriodeDetail($this->periodeId));
        $changed = false;
        foreach ($data as $k => $v) {
            if ((string) ($row[$k] ?? '') !== (string) $v) {
                $changed = true;
                break;
            }
        }
        if (!$changed) {
            return $this->json(200, ['ok' => true, 'message' => 'Tidak ada perubahan.']);
        }
        try {
            $this->showtksModel->update((int) $row['id'], $data);
        } catch (\Throwable $e) {
            log_message('error', 'Showtks::save ' . $e->getMessage());
            return $this->json(500, ['ok' => false, 'message' => 'Data gagal disimpan.']);
        }
        return $this->json(200, ['ok' => true, 'message' => 'Kesimpulan & lembar persetujuan disimpan.', 'reload' => true]);
    }

    public function LaporanPDFTKS()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;
        if ($redirect = $this->checkPeriode())
            return $redirect;

        if (!$this->userKodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid.');
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = $this->periodeId;

        $bprData = $this->infobprModel->getBprByKode($kodebpr);
        $periodeDetail = $this->cachedPeriodeDetail($periodeId);
        $showtks = $this->cachedShowtks($kodebpr, $periodeId);

        $tksData = $this->getTksData($kodebpr, $periodeId);
        $kompositData = $this->calculateKompositTKS($tksData);
        $faktorTKSData = $this->getFaktorTKSData($kodebpr, $periodeId);

        $rentabilitas = $this->cachedTksRentabilitas($kodebpr, $periodeId);
        $permodalan = $this->cachedTksPermodalan($kodebpr, $periodeId);

        // Tentukan bulan posisi laporan & posisi sebelumnya berdasarkan semester
        $semester = (string) ($periodeDetail['semester'] ?? '1');
        $tahun = $periodeDetail['tahun'] ?? date('Y');
        $isGanjil = ($semester === '1' || strtolower($semester) === 'ganjil');

        $bulanCurrent = $isGanjil ? 'Juni' : 'Desember';
        if ($isGanjil) {
            $bulanSebelumnya = 'Desember';
            $tahunSebelumnya = $tahun - 1;
        } else {
            $bulanSebelumnya = 'Juni';
            $tahunSebelumnya = $tahun;
        }

        $pdfData = [
            'bpr' => [
                'namabpr' => $bprData['namabpr'] ?? '',
                'alamat' => $bprData['alamat'] ?? '',
                'nomor' => $bprData['nomor'] ?? '',
                'webbpr' => $bprData['webbpr'] ?? '',
                'email' => $bprData['email'] ?? '',
                'logo' => $this->resolveLogoForPdf($bprData['logo'] ?? ''),
            ],
            'periode' => [
                'bulan' => $bulanCurrent,
                'tahun' => $tahun,
                'periodeSebelumBulan' => $bulanSebelumnya,
                'periodeSebelumTahun' => $tahunSebelumnya,
            ],
            'tks' => [
                'profilrisiko' => [
                    'peringkatCurrent' => $faktorTKSData['profil_risiko']['nilai_peringkat'] ?? '',
                    'nilaifaktorCurrent' => $faktorTKSData['profil_risiko']['nilai_faktor'] ?? '',
                    'peringkatPrevious' => $faktorTKSData['profil_risiko']['nilai_peringkat_before'] ?? '',
                    'nilaifaktorPrevious' => $faktorTKSData['profil_risiko']['nilai_faktor_before'] ?? '',
                ],
                'tatakelola' => [
                    'peringkatCurrent' => $faktorTKSData['tata_kelola']['nilai_peringkat'] ?? '',
                    'nilaifaktorCurrent' => $faktorTKSData['tata_kelola']['nilai_faktor'] ?? '',
                    'peringkatPrevious' => $faktorTKSData['tata_kelola']['nilai_peringkat_before'] ?? '',
                    'nilaifaktorPrevious' => $faktorTKSData['tata_kelola']['nilai_faktor_before'] ?? '',
                ],
                'rentabilitas' => [
                    'peringkatCurrent' => $faktorTKSData['rentabilitas']['nilai_peringkat'] ?? '',
                    'nilaifaktorCurrent' => $faktorTKSData['rentabilitas']['nilai_faktor'] ?? '',
                    'peringkatPrevious' => $faktorTKSData['rentabilitas']['nilai_peringkat_before'] ?? '',
                    'nilaifaktorPrevious' => $faktorTKSData['rentabilitas']['nilai_faktor_before'] ?? '',
                    'roa' => [
                        'rasioCurrent' => $rentabilitas['rasioroa'] ?? '',
                        'peringkatCurrent' => $rentabilitas['peringkatroa'] ?? '',
                        'rasioPrevious' => $rentabilitas['rasioroabefore'] ?? '',
                        'peringkatPrevious' => $rentabilitas['peringkatroabefore'] ?? '',
                    ],
                    'bopo' => [
                        'rasioCurrent' => $rentabilitas['rasiobopo'] ?? '',
                        'peringkatCurrent' => $rentabilitas['peringkatbopo'] ?? '',
                        'rasioPrevious' => $rentabilitas['rasiobopobefore'] ?? '',
                        'peringkatPrevious' => $rentabilitas['peringkatbopobefore'] ?? '',
                    ],
                    'nim' => [
                        'rasioCurrent' => $rentabilitas['rasionim'] ?? '',
                        'peringkatCurrent' => $rentabilitas['peringkatnim'] ?? '',
                        'rasioPrevious' => $rentabilitas['rasionimbefore'] ?? '',
                        'peringkatPrevious' => $rentabilitas['peringkatnimbefore'] ?? '',
                    ],
                ],
                'permodalan' => [
                    'peringkatCurrent' => $faktorTKSData['permodalan']['nilai_peringkat'] ?? '',
                    'nilaifaktorCurrent' => $faktorTKSData['permodalan']['nilai_faktor'] ?? '',
                    'peringkatPrevious' => $faktorTKSData['permodalan']['nilai_peringkat_before'] ?? '',
                    'nilaifaktorPrevious' => $faktorTKSData['permodalan']['nilai_faktor_before'] ?? '',
                    'kpmm' => [
                        'rasioCurrent' => $permodalan['rasiokpmm'] ?? '',
                        'peringkatCurrent' => $permodalan['peringkatkpmm'] ?? '',
                        'rasioPrevious' => $permodalan['rasiokpmmbefore'] ?? '',
                        'peringkatPrevious' => $permodalan['peringkatkpmmbefore'] ?? '',
                    ],
                    'miapb' => [
                        'rasioCurrent' => $permodalan['rasiomiapb'] ?? '',
                        'peringkatCurrent' => $permodalan['peringkatmiapb'] ?? '',
                        'rasioPrevious' => $permodalan['rasiomiapbbefore'] ?? '',
                        'peringkatPrevious' => $permodalan['peringkatmiapbbefore'] ?? '',
                    ],
                ],
                'komposit' => [
                    'nilaiCurrent' => $kompositData['nkomposittks'] ?? '',
                    'nilaiPrevious' => $kompositData['nkomposittksbefore'] ?? '',
                    'peringkatCurrent' => $kompositData['peringkatkomposittks'] ?? '',
                    'peringkatPrevious' => $kompositData['peringkatkomposittksbefore'] ?? '',
                ],
            ],
            'analisis' => [
                'kesimpulan' => $showtks['kesimpulan'] ?? '',
                'profilrisiko' => $faktorTKSData['profil_risiko']['analisis'] ?? '',
                'tatakelola' => $faktorTKSData['tata_kelola']['analisis'] ?? '',
                'rentabilitas' => $faktorTKSData['rentabilitas']['analisis'] ?? '',
                'permodalan' => $faktorTKSData['permodalan']['analisis'] ?? '',
            ],
            'profil' => [
                'tanggal' => $showtks['tanggal'] ?? date('Y-m-d'),
                'lokasi' => $showtks['lokasi'] ?? '',
                'dirkep' => $showtks['dirkep'] ?? '',
                'pe' => $showtks['pe'] ?? '',
            ],
        ];

        try {
            $generator = new LaporanTKSPdfGenerator();
            $pdfOutput = $generator->setData($pdfData)->generate();

            $namaBpr = preg_replace('/[^A-Za-z0-9\-]/', '_', $bprData['namabpr'] ?? 'BPR');
            $filename = "TKS_{$namaBpr}_{$tahun}_{$semester}.pdf";

            return service('response')
                ->setHeader('Content-Type', 'application/pdf')
                ->setHeader('Content-Disposition', "inline; filename=\"{$filename}\"")
                ->setBody($pdfOutput);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membuat PDF: ' . $e->getMessage());
        }
    }

    private function resolveLogoForPdf(?string $logoFilename): string
    {
        if (empty($logoFilename)) {
            return '';
        }

        $path = FCPATH . 'asset/img/' . $logoFilename;

        if (!is_file($path)) {
            return '';
        }

        $imageData = file_get_contents($path);
        if ($imageData === false) {
            return '';
        }

        $mime = mime_content_type($path) ?: 'image/png';
        return 'data:' . $mime . ';base64,' . base64_encode($imageData);
    }

    public function exporttxttks()
    {
        if (!$this->auth->check())
            return redirect()->to(session('redirect_url') ?? '/login');

        $kodebpr = $this->userKodebpr;
        $periodeId = $this->periodeId; // hanya periode milik BPR user
        if (!$periodeId) {
            return redirect()->to('/Periodesemesteran');
        }

        $profilTks = $this->cachedShowtks($kodebpr, $periodeId);
        $periodeDetail = $this->cachedPeriodeDetail($periodeId);
        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        $tksrentabilitas = $this->tksrentabilitasModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();
        $tkspermodalan = $this->tkspermodalanModel->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();

        $modalinti = $periodeDetail['modalinti'];
        $nomorsurat = $periodeDetail['nomorsurat'];
        $jeniskoreksi = $periodeDetail['jeniskoreksi'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];
        $kategori = $periodeDetail['kategori'];
        $tahun = $periodeDetail['tahun'] ?? date('Y');
        $semester = $periodeDetail['semester'] ?? '';
        $exportDate = (strtolower($semester) === 'ganjil' || $semester === '1') ? "{$tahun}-06-30" : "{$tahun}-12-31";
        $titleDate = str_replace('-', '', $exportDate);
        $sandibpr = $data_infobpr[0]['sandibpr'] ?? '';
        $kodejenis = $data_infobpr[0]['kodejenis'] ?? '';

        $t = fn($v) => $this->sanitizeText($v ?? ' ');

        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0700|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";
        $output .= "D01|7100||{$t($profilTks['nprofilrisiko'])}|25.00|{$t($profilTks['nfaktorprofilrisiko'])}||{$t($profilTks['nprofilrisikobefore'])}|25.00|{$t($profilTks['nfaktorprofilrisikobefore'])}\r\n";
        $output .= "D01|7200||{$t($profilTks['ntatakelola'])}|30.00|{$t($profilTks['nfaktortakol'])}||{$t($profilTks['ntatakelolabefore'])}|30.00|{$t($profilTks['nfaktortakolbefore'])}\r\n";
        $output .= "D01|7300||{$t($profilTks['nrentabilitas'])}|15.00|{$t($profilTks['nfaktorrentabilitas'])}||{$t($profilTks['nrentabilitasbefore'])}|15.00|{$t($profilTks['nfaktorrentabilitasbefore'])}\r\n";
        $output .= "D01|7310|{$t($tksrentabilitas['rasioroa'])}|{$t($tksrentabilitas['peringkatroa'])}|||{$t($tksrentabilitas['rasioroabefore'])}|{$t($tksrentabilitas['peringkatroabefore'])}||\r\n";
        $output .= "D01|7311|{$t($tksrentabilitas['rasiobopo'])}|{$t($tksrentabilitas['peringkatbopo'])}|||{$t($tksrentabilitas['rasiobopobefore'])}|{$t($tksrentabilitas['peringkatbopobefore'])}||\r\n";
        $output .= "D01|7312|{$t($tksrentabilitas['rasionim'])}|{$t($tksrentabilitas['peringkatnim'])}|||{$t($tksrentabilitas['rasionimbefore'])}|{$t($tksrentabilitas['peringkatnimbefore'])}||\r\n";
        $output .= "D01|7400||{$t($profilTks['npermodalan'])}|30.00|{$t($profilTks['nfaktorpermodalan'])}||{$t($profilTks['npermodalanbefore'])}|30.00|{$t($profilTks['nfaktorpermodalanbefore'])}\r\n";
        $output .= "D01|7410|{$t($tkspermodalan['rasiokpmm'])}|{$t($tkspermodalan['peringkatkpmm'])}|||{$t($tkspermodalan['rasiokpmmbefore'])}|{$t($tkspermodalan['peringkatkpmmbefore'])}||\r\n";
        $output .= "D01|7411|{$t($tkspermodalan['rasiomiapb'])}|{$t($tkspermodalan['peringkatmiapb'])}|||{$t($tkspermodalan['rasiomiapbbefore'])}|{$t($tkspermodalan['peringkatmiapbbefore'])}||\r\n";
        $output .= "D01|7500||||{$t($profilTks['nkomposittks'])}||||{$t($profilTks['nkomposittksbefore'])}\r\n";
        $output .= "D01|7600||||{$t($profilTks['peringkatkomposittks'])}||||{$t($profilTks['peringkatkomposittksbefore'])}\r\n";
        $output .= "F01|{$t($profilTks['kesimpulan'])}\r\n";
        $output .= "F02|{$t($profilTks['kesimpulan2'])}\r\n";
        $output .= "F03|{$t($profilTks['kesimpulan3'])}\r\n";
        $output .= "F04|{$t($profilTks['kesimpulan4'])}\r\n";
        $output .= "F05|{$t($profilTks['kesimpulan5'])}\r\n";

        $filename = "TKSBPRKS-0700-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";
        return service('response')
            ->setHeader('Content-Type', 'text/plain')
            ->setHeader('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->setBody($output);
    }
}
