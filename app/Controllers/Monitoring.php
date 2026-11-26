<?php

namespace App\Controllers;

use App\Models\M_monitoring;
use CodeIgniter\Controller;
use Myth\Auth\Config\Services as AuthServices;

/**
 * Monitoring pelaporan seluruh BPR (khusus admin).
 *   GET monitoring?tahun=2025[&refresh=1]   dashboard
 *   GET monitoring/csv?tahun=2025           unduh rekap CSV
 * Data di-cache 60 detik per tahun; tombol "Muat ulang" memaksa hitung ulang.
 */
class Monitoring extends Controller
{
    private const CACHE_TTL = 60;

    protected $auth;
    protected $model;

    public function __construct()
    {
        $this->auth = service('authentication');
        $this->model = new M_monitoring();
    }

    /** Hanya admin; selain itu redirect / 404. */
    private function guard()
    {
        if (!$this->auth->check()) {
            session()->set('redirect_url', current_url());
            return redirect()->to('/login');
        }
        if (!AuthServices::authorization()->inGroup('admin', (int) $this->auth->id())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        return null;
    }

    /** Tahun terpilih (valid) + daftar tahun. */
    private function tahun(): array
    {
        $list = $this->model->tahunList();
        $req = (int) $this->request->getGet('tahun');
        $tahun = in_array($req, $list, true) ? $req : ($list[0] ?? (int) date('Y') - 1);
        return [$tahun, $list];
    }

    private function data(int $tahun, bool $refresh = false): array
    {
        $key = 'monitoring_tahunan_' . $tahun;
        $cache = service('cache');
        if (!$refresh && ($d = $cache->get($key))) {
            return $d;
        }
        $d = $this->model->tahunan($tahun);
        $cache->save($key, $d, self::CACHE_TTL);
        return $d;
    }

    public function index()
    {
        if ($r = $this->guard()) {
            return $r;
        }
        [$tahun, $list] = $this->tahun();

        $data = [
            'judul' => 'Monitoring Pelaporan',
            'tahun' => $tahun,
            'tahunList' => $list,
            'mon' => $this->data($tahun, (bool) $this->request->getGet('refresh')),
        ];

        return view('templates/v_header', $data)
            . view('templates/v_sidebar')
            . view('templates/v_topbar')
            . view('admin/monitoring', $data)
            . view('templates/v_footer');
    }

    /** Rekap CSV (dibuka di Excel; pemisah titik koma). */
    public function csv()
    {
        if ($r = $this->guard()) {
            return $r;
        }
        [$tahun] = $this->tahun();
        $mon = $this->data($tahun);
        $label = ['none' => 'Belum diisi', 'draft' => 'Draf', 'komut' => 'Disetujui Komut', 'ok' => 'Selesai', 'skip' => 'Tidak ada data'];
        $statusLabel = ['noperiode' => 'Belum membuat periode', 'notstarted' => 'Belum mulai', 'progress' => 'Dalam proses', 'ready' => 'Siap export'];

        $fh = fopen('php://temp', 'w+');
        fwrite($fh, "\xEF\xBB\xBF");   // BOM agar Excel membaca UTF-8
        fputcsv($fh, array_merge(['Kode BPR', 'Nama BPR', 'Sandi', 'Periode', 'Status', 'Progres (%)'], array_column($mon['sections'], 'name')), ';');
        foreach ($mon['bprs'] as $b) {
            $cells = array_map(static fn($c) => $label[$c['st']] . ($c['note'] !== '' ? ' (' . $c['note'] . ')' : ''), $b['cells']);
            if (!$b['periode']) {
                $cells = array_fill(0, count($mon['sections']), '-');
            }
            fputcsv($fh, array_merge([
                "'" . $b['kodebpr'], $b['nama'], $b['sandi'],
                $b['periode'] ? ($b['periode']['kategori'] === 'K' ? 'Koreksi' : 'Rutin') : '-',
                $statusLabel[$b['status']], $b['percent'],
            ], $cells), ';');
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="monitoring-laporan-tahunan-' . $tahun . '.csv"')
            ->setBody($csv);
    }
}
