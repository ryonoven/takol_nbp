<?php

namespace App\Controllers;

use App\Models\M_infobpr;
use App\Models\M_paketkebijakandirdekom;
use App\Models\M_periodetahunan;
use App\Models\M_user;
use CodeIgniter\Controller;
use Myth\Auth\Config\Services as AuthServices;

/**
 * Laporan Transparansi 11 — Paket/Kebijakan Remunerasi dan Fasilitas Lain bagi Direksi dan Dewan Komisaris (E0500).
 *   GET  paketkebijakandirdekom                              halaman
 *   POST paketkebijakandirdekom/save                         simpan matriks + penjelasan (PE / admin)
 *   POST paketkebijakandirdekom/syncFromPrevPeriode          salin & simpan data tahun sebelumnya (PE / admin)
 *   POST paketkebijakandirdekom/approve (type, act)          type: komut|dirut, act: approve|cancel
 *   GET  paketkebijakandirdekom/exporttxtpaketkebijakandirdekom   TXT E0500 (juga dipanggil ZIP)
 * Komentar memakai widget bersama templates/komentar.
 */
class Paketkebijakandirdekom extends Controller
{
    private const VIEW = 'Laporantahunan/Transparansi/paketkebijakandirdekom';
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
        $this->model = new M_paketkebijakandirdekom();
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

    /** Ambil nilai kolom isian dari row DB (null -> 0) sebagai int. */
    private static function values(?array $row): array
    {
        $v = [];
        foreach (M_paketkebijakandirdekom::valueColumns() as $c) {
            $v[$c] = (int) ($row[$c] ?? 0);
        }
        return $v;
    }

    private static function isApproved(?array $row): bool
    {
        return $row && ((int) $row['accdekom'] === 1 || (int) $row['is_approved'] === 1);
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
            'judul' => 'Paket/Kebijakan Remunerasi dan Fasilitas Lain bagi Direksi dan Dewan Komisaris',
            'periode' => $periode,
            'row' => $row,
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
        $periode = $this->periode();
        [$pid, $kode] = [(int) $periode['id'], $periode['kodebpr']];
        $row = $this->model->getByPeriode($pid, $kode);

        $vals = [];
        $errors = [];
        foreach (M_paketkebijakandirdekom::valueColumns() as $c) {
            $raw = preg_replace('/[^0-9]/', '', (string) $this->request->getPost($c));   // buang pemisah ribuan
            if ($raw === '') {
                $raw = '0';
            }
            $max = M_paketkebijakandirdekom::isNominal($c) ? null : M_paketkebijakandirdekom::MAX_PENERIMA;
            if (strlen(ltrim($raw, '0')) > M_paketkebijakandirdekom::MAX_NOMINAL_DIGITS || ($max !== null && (int) $raw > $max)) {
                $errors[$c] = $max !== null ? "Maksimal {$max} orang." : 'Nominal terlalu besar.';
            }
            $vals[$c] = (int) $raw;
        }
        // Nominal > 0 wajib ada penerimanya
        foreach (M_paketkebijakandirdekom::ITEMS as [, , , $pd, $nd, $pk, $nk]) {
            foreach ([[$pd, $nd], [$pk, $nk]] as [$p, $n]) {
                if ($vals[$n] > 0 && $vals[$p] === 0 && !isset($errors[$p])) {
                    $errors[$p] = 'Isi jumlah penerima.';
                }
            }
        }
        $tindak = trim((string) $this->request->getPost('tindaklanjut'));
        if (mb_strlen($tindak) > M_paketkebijakandirdekom::MAX_TINDAK) {
            $errors['tindaklanjut'] = 'Maksimal ' . M_paketkebijakandirdekom::MAX_TINDAK . ' karakter.';
        }
        if ($errors) {
            return $this->json(422, ['ok' => false, 'message' => 'Periksa kembali isian yang ditandai.', 'errors' => $errors]);
        }

        $curTindak = $this->model->getTindak($pid, $kode);
        $matrixChanged = !$row || $vals !== self::values($row);
        $tindakChanged = (string) ($curTindak['tindaklanjut'] ?? '') !== $tindak;
        if (!$matrixChanged && !$tindakChanged) {
            return $this->json(200, ['ok' => true, 'message' => 'Tidak ada perubahan.', 'reload' => false]);
        }

        $wasApproved = self::isApproved($row);
        $db = db_connect();
        $db->transStart();
        $data = $vals + M_paketkebijakandirdekom::computeTotals($vals) + M_paketkebijakandirdekom::resetApprovalData()
            + ['user_id' => $this->userId, 'fullname' => $this->fullname, 'kodebpr' => $kode, 'periode_id' => $pid];
        $this->model->saveRow($row ? (int) $row['id'] : null, $data);
        if ($tindakChanged) {
            $this->model->saveTindak($curTindak ? (int) $curTindak['id'] : null, [
                'tindaklanjut' => $tindak, 'kodebpr' => $kode, 'periode_id' => $pid, 'fullname' => $this->fullname, 'user_id' => $this->userId,
            ]);
        }
        $db->transComplete();

        if (!$db->transStatus()) {
            log_message('error', 'Paketkebijakandirdekom::save transaksi gagal');
            return $this->json(500, ['ok' => false, 'message' => 'Data gagal disimpan.']);
        }
        return $this->json(200, [
            'ok' => true,
            'message' => $wasApproved ? 'Data tersimpan. Status approval direset.' : 'Data berhasil disimpan.',
            'reload' => !$row || $wasApproved,
        ]);
    }

    /** Salin nilai tahun sebelumnya (dan penjelasan bila tahun ini kosong) lalu langsung disimpan. */
    public function syncFromPrevPeriode()
    {
        if ($e = $this->guardPost(true)) {
            return $e;
        }
        $periode = $this->periode();
        [$pid, $kode, $tahun] = [(int) $periode['id'], $periode['kodebpr'], (int) $periode['tahun']];
        $prevTahun = $tahun - 1;

        $prev = $this->model->getPrevYear($kode, $tahun);
        if (!$prev) {
            return $this->json(404, ['ok' => false, 'message' => "Tidak ada data Paket/Kebijakan Remunerasi tahun {$prevTahun}."]);
        }
        $row = $this->model->getByPeriode($pid, $kode);
        $vals = self::values($prev);

        $prevTindak = $this->model->getTindak((int) $prev['periode_id'], $kode);
        $curTindak = $this->model->getTindak($pid, $kode);
        $copyTindak = $prevTindak && !$curTindak && trim((string) $prevTindak['tindaklanjut']) !== '';

        if ($row && $vals === self::values($row) && !$copyTindak) {
            return $this->json(200, ['ok' => true, 'message' => "Data sudah sama dengan tahun {$prevTahun}.", 'reload' => false]);
        }

        $db = db_connect();
        $db->transStart();
        $this->model->saveRow($row ? (int) $row['id'] : null, $vals + M_paketkebijakandirdekom::computeTotals($vals)
            + M_paketkebijakandirdekom::resetApprovalData()
            + ['user_id' => $this->userId, 'fullname' => $this->fullname, 'kodebpr' => $kode, 'periode_id' => $pid]);
        if ($copyTindak) {
            $this->model->saveTindak(null, ['tindaklanjut' => $prevTindak['tindaklanjut'], 'kodebpr' => $kode,
                'periode_id' => $pid, 'fullname' => $this->fullname, 'user_id' => $this->userId]);
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

        $periode = $this->periode();
        $row = $this->model->getByPeriode((int) $periode['id'], $periode['kodebpr']);
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
     * TXT LTBPRK E0500 (format tidak berubah):
     *   D01|<kode pos>|penerima dir|nominal dir|penerima dekom|nominal dekom
     *   D01|<kode total>||total dir||total dekom
     *   F01|penjelasan   (F01| kosong bila belum diisi)
     */
    public function exporttxtpaketkebijakandirdekom()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }
        $periode = $this->periode();
        if (!$periode) {
            return redirect()->to(self::PERIODE_REDIRECT)->with('error', 'Periode tahunan aktif tidak ditemukan.');
        }
        [$pid, $kode] = [(int) $periode['id'], $periode['kodebpr']];

        $bpr = (new M_infobpr())->getBprByKode($kode) ?? [];
        $sandibpr = $bpr['sandibpr'] ?? '';
        $kodejenis = $bpr['kodejenis'] ?? '';
        $tahun = $periode['tahun'] ?? date('Y');
        $form = M_paketkebijakandirdekom::FORM_CODE;
        $c = static fn($v) => self::txtClean($v);

        $output = "H01|{$kodejenis}|{$sandibpr}|{$tahun}-12-31|LTBPRK|{$form}|0|\r\n";
        $row = $this->model->getByPeriode($pid, $kode);
        if ($row) {
            foreach (M_paketkebijakandirdekom::TXT_ORDER as $code) {
                if (isset(M_paketkebijakandirdekom::ITEMS[$code])) {
                    [, , , $pd, $nd, $pk, $nk] = M_paketkebijakandirdekom::ITEMS[$code];
                    $output .= "D01|{$code}|" . $c($row[$pd]) . '|' . $c($row[$nd]) . '|' . $c($row[$pk]) . '|' . $c($row[$nk]) . "\r\n";
                } else {
                    [, $td, $tk] = M_paketkebijakandirdekom::TOTALS[$code];
                    $output .= "D01|{$code}||" . $c($row[$td]) . '||' . $c($row[$tk]) . "\r\n";
                }
            }
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
