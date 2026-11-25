<?php

namespace App\Controllers;

use App\Models\M_masalahhukum;
use App\Models\M_infobpr;
use App\Models\M_periodetahunan;
use App\Models\M_user;
use CodeIgniter\Controller;
use Myth\Auth\Config\Services as AuthServices;

/**
 * Laporan Transparansi 16 — Permasalahan Hukum yang Dihadapi (E0900). Total dihitung di server.
 *   GET  masalahhukum                           halaman
 *   POST masalahhukum/save                     simpan matriks + penjelasan (PE / admin)
 *   POST masalahhukum/nodata (act)             act: set|cancel — status "Tidak Ada Data" (PE / admin)
 *   POST masalahhukum/syncFromPrevPeriode      salin & simpan data tahun sebelumnya (PE / admin)
 *   POST masalahhukum/approve (type, act)      type: komut|dirut, act: approve|cancel
 *   GET  masalahhukum/exporttxtmasalahhukum     TXT E0900 (juga dipanggil ZIP)
 * Komentar memakai widget bersama templates/komentar.
 */
class Masalahhukum extends Controller
{
    private const VIEW = 'Laporantahunan/Transparansi/masalahhukum';
    private const PERIODE_REDIRECT = '/periodetahunan';

    protected $model;
    protected $auth;
    protected int $userId = 0;
    protected ?string $userKodebpr = null;
    protected string $fullname = '';
    protected array $grp = ['pe' => false, 'admin' => false, 'dekom' => false, 'direksi' => false];

    private ?array $periodeCache = null;

    public function __construct()
    {
        $this->model = new M_masalahhukum();
        $this->auth = service('authentication');

        if ($this->auth->check()) {
            $this->userId = (int) $this->auth->id();
            $user = (new M_user())->find($this->userId);
            $this->userKodebpr = $user['kodebpr'] ?? null;
            $this->fullname = (string) ($user['fullname'] ?? $user['username'] ?? '');

            $authorize = AuthServices::authorization();
            foreach (array_keys($this->grp) as $g) {
                $this->grp[$g] = (bool) $authorize->inGroup($g, $this->userId);
            }
        }
    }

    // ------------------------------------------------------------------ helpers

    private function checkAuth(): ?\CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->auth->check()) {
            session()->set('redirect_url', current_url());
            return redirect()->to('/login');
        }
        if (!session('active_periode')) {
            return redirect()->to(self::PERIODE_REDIRECT)->with('error', 'Silakan pilih periode tahunan aktif terlebih dahulu.');
        }
        if (!$this->userKodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid.');
        }
        return null;
    }

    private function periode(): ?array
    {
        if ($this->periodeCache === null) {
            $id = (int) session('active_periode');
            $p = ($this->userKodebpr && $id > 0) ? (new M_periodetahunan())->find($id) : null;
            // Periode wajib milik BPR user (cegah akses data BPR lain lewat session active_periode)
            if ($p && (string) ($p['kodebpr'] ?? '') !== (string) $this->userKodebpr) {
                $p = null;
            }
            $this->periodeCache = $p ?: [];
        }
        return $this->periodeCache ?: null;
    }

    private function canEdit(): bool
    {
        return $this->grp['pe'] || $this->grp['admin'];
    }

    private function json(int $status, array $payload)
    {
        $payload['csrf'] = ['name' => csrf_token(), 'hash' => csrf_hash()];
        return $this->response->setStatusCode($status)->setJSON($payload);
    }

    private function guardPost(bool $editor = false)
    {
        if (strtolower((string) $this->request->getMethod()) !== 'post') {
            return $this->json(405, ['ok' => false, 'message' => 'Metode tidak diizinkan.']);
        }
        if (!$this->auth->check()) {
            return $this->json(401, ['ok' => false, 'message' => 'Sesi berakhir. Silakan login kembali.']);
        }
        if (!$this->periode()) {
            return $this->json(422, ['ok' => false, 'message' => 'Periode tahunan aktif tidak ditemukan.']);
        }
        if ($editor && !$this->canEdit()) {
            return $this->json(403, ['ok' => false, 'message' => 'Anda tidak memiliki akses untuk mengubah data ini.']);
        }
        return null;
    }

    /** Nilai isian dari row DB sebagai int (null -> 0). */
    private static function values(?array $row): array
    {
        $v = [];
        foreach (M_masalahhukum::valueColumns() as $c) {
            $v[$c] = (int) ($row[$c] ?? 0);
        }
        return $v;
    }

    private static function isNodata(?array $row): bool
    {
        return $row && (int) ($row['is_null_record'] ?? 0) === 1;
    }

    private static function isApproved(?array $row): bool
    {
        return $row && ((int) $row['accdekom'] === 1 || (int) $row['is_approved'] === 1);
    }

    private function scope(): array
    {
        $p = $this->periode();
        return [(int) $p['id'], $p['kodebpr']];
    }

    private function baseData(int $pid, string $kode): array
    {
        return ['user_id' => $this->userId, 'fullname' => $this->fullname, 'kodebpr' => $kode, 'periode_id' => $pid];
    }

    // ------------------------------------------------------------------ halaman

    public function index()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }

        $periode = $this->periode();
        $row = $periode ? $this->model->getByPeriode((int) $periode['id'], $periode['kodebpr']) : null;
        $canEdit = $periode && $this->canEdit();

        $data = [
            'judul' => 'Permasalahan Hukum yang Dihadapi',
            'periode' => $periode,
            'row' => $row,
            'nodata' => self::isNodata($row),
            'values' => self::values($row),
            'tindak' => $periode ? $this->model->getTindak((int) $periode['id'], $periode['kodebpr']) : null,
            'namabpr' => (new M_infobpr())->getBprByKode($this->userKodebpr)['namabpr'] ?? 'Nama BPR',
            'canEdit' => $canEdit,
            'canKomut' => $row && ($this->grp['admin'] || $this->grp['dekom']),
            'canDirut' => $row && ($this->grp['admin'] || $this->grp['direksi']),
            'isAdmin' => $this->grp['admin'],
            'userId' => $this->userId,
            'hasPrev' => $canEdit && $this->model->getPrevYear($periode['kodebpr'], (int) $periode['tahun']) !== null,
        ];

        return view('templates/v_header', $data)
            . view('templates/v_sidebar')
            . view('templates/v_topbar')
            . view(self::VIEW, $data)
            . view('templates/v_footer');
    }

    // ------------------------------------------------------------------ simpan

    public function save()
    {
        if ($e = $this->guardPost(true)) {
            return $e;
        }
        [$pid, $kode] = $this->scope();
        $row = $this->model->getByPeriode($pid, $kode);
        if (self::isNodata($row)) {
            return $this->json(409, ['ok' => false, 'message' => 'Batalkan status "Tidak Ada Data" terlebih dahulu.']);
        }

        $vals = [];
        $errors = [];
        foreach (M_masalahhukum::valueColumns() as $c) {
            $raw = trim((string) $this->request->getPost($c));
            if ($raw === '') {
                $raw = '0';
            }
            if (!ctype_digit($raw) || (int) $raw > M_masalahhukum::MAX_VALUE) {
                $errors[$c] = 'Isi angka 0 – ' . M_masalahhukum::MAX_VALUE . '.';
            }
            $vals[$c] = (int) $raw;
        }
        $tindak = trim((string) $this->request->getPost('tindaklanjut'));
        if (mb_strlen($tindak) > M_masalahhukum::MAX_TINDAK) {
            $errors['tindaklanjut'] = 'Maksimal ' . M_masalahhukum::MAX_TINDAK . ' karakter.';
        }
        if ($errors) {
            return $this->json(422, ['ok' => false, 'message' => 'Periksa kembali isian yang ditandai.', 'errors' => $errors]);
        }

        $curTindak = $this->model->getTindak($pid, $kode);
        $changed = !$row || $vals !== self::values($row);
        $tindakChanged = (string) ($curTindak['tindaklanjut'] ?? '') !== $tindak;
        if (!$changed && !$tindakChanged) {
            return $this->json(200, ['ok' => true, 'message' => 'Tidak ada perubahan.', 'reload' => false]);
        }

        $wasApproved = self::isApproved($row);
        $db = db_connect();
        $db->transStart();
        $this->model->saveRow($row ? (int) $row['id'] : null, $vals + M_masalahhukum::computeTotals($vals) + ['is_null_record' => 0]
            + M_masalahhukum::resetApprovalData() + $this->baseData($pid, $kode));
        if ($tindakChanged) {
            $this->model->saveTindak($curTindak ? (int) $curTindak['id'] : null,
                ['tindaklanjut' => $tindak] + $this->baseData($pid, $kode));
        }
        $db->transComplete();

        if (!$db->transStatus()) {
            log_message('error', 'Masalahhukum::save transaksi gagal');
            return $this->json(500, ['ok' => false, 'message' => 'Data gagal disimpan.']);
        }
        return $this->json(200, [
            'ok' => true,
            'message' => $wasApproved ? 'Data tersimpan. Status approval direset.' : 'Data berhasil disimpan.',
            'reload' => !$row || $wasApproved,
        ]);
    }

    /** "Tidak Ada Data": set = seluruh nilai 0 + is_null_record 1; cancel = kembali ke form isian (nilai 0). */
    public function nodata()
    {
        if ($e = $this->guardPost(true)) {
            return $e;
        }
        [$pid, $kode] = $this->scope();
        $row = $this->model->getByPeriode($pid, $kode);
        $act = (string) $this->request->getPost('act');

        if ($act === 'set') {
            if (self::isNodata($row)) {
                return $this->json(409, ['ok' => false, 'message' => 'Status "Tidak Ada Data" sudah aktif.']);
            }
            $data = M_masalahhukum::zeroValues() + M_masalahhukum::computeTotals([]) + ['is_null_record' => 1];
            $msg = 'Ditandai "Tidak Ada Data".';
        } elseif ($act === 'cancel') {
            if (!self::isNodata($row)) {
                return $this->json(409, ['ok' => false, 'message' => 'Status "Tidak Ada Data" tidak aktif.']);
            }
            $data = ['is_null_record' => 0];
            $msg = 'Status "Tidak Ada Data" dibatalkan. Silakan isi data.';
        } else {
            return $this->json(422, ['ok' => false, 'message' => 'Permintaan tidak valid.']);
        }

        $ok = $this->model->saveRow($row ? (int) $row['id'] : null,
            $data + M_masalahhukum::resetApprovalData() + $this->baseData($pid, $kode));
        return $ok
            ? $this->json(200, ['ok' => true, 'message' => $msg, 'reload' => true])
            : $this->json(500, ['ok' => false, 'message' => 'Gagal memproses permintaan.']);
    }

    /** Salin isian (atau status "Tidak Ada Data") tahun sebelumnya, plus penjelasan bila tahun ini kosong. */
    public function syncFromPrevPeriode()
    {
        if ($e = $this->guardPost(true)) {
            return $e;
        }
        [$pid, $kode] = $this->scope();
        $tahun = (int) $this->periode()['tahun'];
        $prevTahun = $tahun - 1;

        $prev = $this->model->getPrevYear($kode, $tahun);
        if (!$prev) {
            return $this->json(404, ['ok' => false, 'message' => "Tidak ada data Permasalahan Hukum tahun {$prevTahun}."]);
        }
        $row = $this->model->getByPeriode($pid, $kode);
        $vals = self::values($prev) + ['is_null_record' => self::isNodata($prev) ? 1 : 0];

        $prevTindak = $this->model->getTindak((int) $prev['periode_id'], $kode);
        $curTindak = $this->model->getTindak($pid, $kode);
        $copyTindak = $prevTindak && !$curTindak && trim((string) $prevTindak['tindaklanjut']) !== '';

        if ($row && $vals === self::values($row) + ['is_null_record' => self::isNodata($row) ? 1 : 0] && !$copyTindak) {
            return $this->json(200, ['ok' => true, 'message' => "Data sudah sama dengan tahun {$prevTahun}.", 'reload' => false]);
        }

        $db = db_connect();
        $db->transStart();
        $this->model->saveRow($row ? (int) $row['id'] : null, $vals + M_masalahhukum::computeTotals($vals) + M_masalahhukum::resetApprovalData() + $this->baseData($pid, $kode));
        if ($copyTindak) {
            $this->model->saveTindak(null, ['tindaklanjut' => $prevTindak['tindaklanjut']] + $this->baseData($pid, $kode));
        }
        $db->transComplete();

        if (!$db->transStatus()) {
            return $this->json(500, ['ok' => false, 'message' => 'Sinkronisasi gagal disimpan.']);
        }
        return $this->json(200, ['ok' => true, 'reload' => true,
            'message' => "Data tahun {$prevTahun} berhasil disinkronkan ke tahun {$tahun}" . ($copyTindak ? ' (termasuk penjelasan).' : '.')]);
    }

    // ------------------------------------------------------------------ approval

    public function approve()
    {
        if ($e = $this->guardPost()) {
            return $e;
        }
        $type = (string) $this->request->getPost('type');
        $act = (string) $this->request->getPost('act');
        if (!in_array($type, ['komut', 'dirut'], true) || !in_array($act, ['approve', 'cancel'], true)) {
            return $this->json(422, ['ok' => false, 'message' => 'Permintaan tidak valid.']);
        }
        $isAdmin = $this->grp['admin'];
        if ($type === 'komut' && !$isAdmin && !$this->grp['dekom']) {
            return $this->json(403, ['ok' => false, 'message' => 'Hanya Komisaris Utama yang dapat melakukan approval ini.']);
        }
        if ($type === 'dirut' && !$isAdmin && !$this->grp['direksi']) {
            return $this->json(403, ['ok' => false, 'message' => 'Hanya Direktur Utama yang dapat melakukan approval ini.']);
        }

        [$pid, $kode] = $this->scope();
        $row = $this->model->getByPeriode($pid, $kode);
        if (!$row) {
            return $this->json(422, ['ok' => false, 'message' => 'Data belum diisi, tidak dapat di-approve.']);
        }
        [$flag, $by] = $type === 'komut' ? ['accdekom', 'accdekom_by'] : ['is_approved', 'approved_by'];
        $approved = (int) $row[$flag] === 1;

        if ($act === 'approve') {
            if ($approved) {
                return $this->json(409, ['ok' => false, 'message' => 'Sudah disetujui sebelumnya.']);
            }
            if ($type === 'dirut' && (int) $row['accdekom'] !== 1) {
                return $this->json(409, ['ok' => false, 'message' => 'Komisaris Utama harus menyetujui terlebih dahulu.']);
            }
        } else {
            if (!$approved) {
                return $this->json(409, ['ok' => false, 'message' => 'Belum disetujui.']);
            }
            if (!$isAdmin && (int) $row[$by] !== $this->userId) {
                return $this->json(403, ['ok' => false, 'message' => 'Hanya yang menyetujui yang dapat membatalkan.']);
            }
        }

        $ok = $this->model->setApproval((int) $row['id'], $type, $act === 'approve', $this->userId);
        $label = $type === 'komut' ? 'Komisaris Utama' : 'Direktur Utama';
        return $ok
            ? $this->json(200, ['ok' => true, 'message' => $act === 'approve' ? "Disetujui oleh {$label}." : "Persetujuan {$label} dibatalkan."])
            : $this->json(500, ['ok' => false, 'message' => 'Gagal memproses approval.']);
    }

    // ------------------------------------------------------------------ export TXT

    private static function txtClean($text): string
    {
        $text = str_replace(["\r", "\n", "\t", '|'], ' ', (string) $text);
        $text = str_replace(["\xE2\x80\x9C", "\xE2\x80\x9D"], '"', $text);
        $text = str_replace(["\xE2\x80\x98", "\xE2\x80\x99"], "'", $text);
        $text = str_replace('%', ' persen ', $text);
        $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    /**
     * TXT LTBPRK E0900 (format tidak berubah):
     *   D01|1001|perdata selesai|pidana selesai
     *   D01|1002|perdata proses|pidana proses
     *   D01|1003|total perdata|total pidana   (hanya bila tidak nol)
     *   "Tidak Ada Data" / belum diisi: 1001, 1002, 1003 bernilai 0|0
     *   F01|penjelasan  (F01| kosong bila belum diisi)
     */
    public function exporttxtmasalahhukum()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }
        $periode = $this->periode();
        if (!$periode) {
            return redirect()->to(self::PERIODE_REDIRECT)->with('error', 'Periode tahunan aktif tidak ditemukan.');
        }
        [$pid, $kode] = $this->scope();

        $bpr = (new M_infobpr())->getBprByKode($kode) ?? [];
        $sandibpr = $bpr['sandibpr'] ?? '';
        $kodejenis = $bpr['kodejenis'] ?? '';
        $tahun = $periode['tahun'] ?? date('Y');
        $form = M_masalahhukum::FORM_CODE;

        $output = "H01|{$kodejenis}|{$sandibpr}|{$tahun}-12-31|LTBPRK|{$form}|0|\r\n";
        $row = $this->model->getByPeriode($pid, $kode);
        if ($row && !self::isNodata($row)) {
            $v = self::values($row);
            foreach (M_masalahhukum::ROWS as [$code, , $perdata, $pidana]) {
                $output .= "D01|{$code}|{$v[$perdata]}|{$v[$pidana]}\r\n";
            }
            $tPerdata = $v['hukumperdataselesai'] + $v['hukumperdataproses'];
            $tPidana = $v['hukumpidanaselesai'] + $v['hukumpidanaproses'];
            if ($tPerdata || $tPidana) {
                $output .= "D01|1003|{$tPerdata}|{$tPidana}\r\n";
            }
        } else {
            $output .= "D01|1001|0|0\r\nD01|1002|0|0\r\nD01|1003|0|0\r\n";
        }
        $t = $this->model->getTindak($pid, $kode);
        $output .= 'F01|' . self::txtClean($t['tindaklanjut'] ?? '') . "\r\n";

        $filename = "LTBPRK-{$form}-{$periode['jenispelaporan']}-A-{$tahun}1231-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }
}
