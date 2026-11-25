<?php

namespace App\Controllers;

use App\Models\M_komentar;
use App\Models\M_showtahunan;

class Home extends BaseController
{
    private const CACHE_TTL = 300;

    private const PROFIL_FIELDS = [
        'namabpr' => 'Nama BPR', 'sandibpr' => 'Sandi BPR', 'kodejenis' => 'Kode jenis',
        'alamat' => 'Alamat', 'nomor' => 'Telepon', 'email' => 'Email', 'logo' => 'Logo',
    ];

    protected $auth;

    public function __construct()
    {
        $this->auth = service('authentication');
    }

    public function index()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $userId = (int) $this->auth->id();
        $user = db_connect()->table('users')->select('fullname, username, kodebpr')->where('id', $userId)->get()->getRowArray() ?? [];
        $kodebpr = (string) ($user['kodebpr'] ?? '');

        $summary = $kodebpr !== ''
            ? cache()->remember('home_' . preg_replace('/[^A-Za-z0-9_]/', '', $kodebpr), self::CACHE_TTL, fn() => $this->summary($kodebpr))
            : $this->summary('');

        $tahunan = $summary['periodes']['tahunan']['latest'] ?? null;
        $unread = ($kodebpr !== '' && $tahunan)
            ? array_sum((new M_komentar())->unreadCounts($kodebpr, (int) $tahunan['id'], $userId, M_komentar::GROUP_TAHUNAN))
            : 0;

        $data = [
            'judul' => 'Dashboard',
            'nama' => trim((string) ($user['fullname'] ?: ($user['username'] ?? ''))),
            'unreadComments' => $unread,
        ] + $summary;

        return view('templates/v_header', $data)
            . view('templates/v_sidebar')
            . view('templates/v_topbar')
            . view('Home/index', $data)
            . view('templates/v_footer');
    }

    /** Ringkasan BPR. */
    private function summary(string $kodebpr): array
    {
        $db = db_connect();
        $bpr = $kodebpr !== '' ? ($db->table('infobpr')->where('kodebpr', $kodebpr)->get()->getRowArray() ?? []) : [];

        $missing = [];
        foreach (self::PROFIL_FIELDS as $f => $label) {
            if (trim((string) ($bpr[$f] ?? '')) === '') {
                $missing[] = $label;
            }
        }
        $profilPct = (int) round((count(self::PROFIL_FIELDS) - count($missing)) / count(self::PROFIL_FIELDS) * 100);

        // Periode per jenis laporan: jumlah + periode terbaru
        $periodes = [];
        foreach (['semesteran' => 'periode_semesteran', 'tahunan' => 'periode_tahunan', 'saf' => 'periode_saf'] as $key => $table) {
            $hasSemester = $key !== 'tahunan';
            $rows = $kodebpr !== '' ? $db->table($table)
                ->select('id, tahun' . ($hasSemester ? ', semester' : ''))
                ->where('kodebpr', $kodebpr)
                ->orderBy('tahun', 'DESC')->orderBy($hasSemester ? 'semester' : 'id', 'DESC')
                ->get()->getResultArray() : [];
            $periodes[$key] = ['count' => count($rows), 'latest' => $rows[0] ?? null];
        }

        // Progres approval laporan tahunan terbaru (Direktur Utama)
        $progress = ['done' => 0, 'total' => 0, 'tahun' => null, 'pending' => []];
        if ($latest = $periodes['tahunan']['latest']) {
            $sections = array_filter((new M_showtahunan())->getSections($kodebpr, (int) $latest['id']), static fn($s) => $s['table']);
            $progress = [
                'done' => count(array_filter($sections, static fn($s) => $s['approved'])),
                'total' => count($sections),
                'tahun' => $latest['tahun'],
                'pending' => array_values(array_column(array_filter($sections, static fn($s) => !$s['approved']), 'name')),
            ];
        }

        return [
            'bpr' => ['namabpr' => $bpr['namabpr'] ?? null, 'sandibpr' => $bpr['sandibpr'] ?? null],
            'profilPct' => $profilPct,
            'profilMissing' => $missing,
            'periodes' => $periodes,
            'progress' => $progress,
        ];
    }
}
