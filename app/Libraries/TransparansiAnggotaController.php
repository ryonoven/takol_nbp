<?php

namespace App\Libraries;

use App\Models\M_anggotatransparansi;
use App\Models\M_infobpr;
use App\Models\M_periodetahunan;
use App\Models\M_user;
use CodeIgniter\Controller;
use Myth\Auth\Config\Services as AuthServices;

abstract class TransparansiAnggotaController extends Controller
{
    private const PERIODE_REDIRECT = '/periodetahunan';

    /** @var M_anggotatransparansi */
    protected $model;
    protected $auth;
    protected int $userId = 0;
    protected ?string $userKodebpr = null;
    protected string $fullname = '';
    protected array $grp = ['pe' => false, 'admin' => false, 'dekom' => false, 'direksi' => false];

    private ?array $periodeCache = null;

    abstract protected function config(): array;

    abstract protected function newModel(): M_anggotatransparansi;

    public function __construct()
    {
        $this->model = $this->newModel();
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

    // helpers
    private function m(): string
    {
        return get_class($this->model);
    }

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

    private function scope(): array
    {
        $p = $this->periode();
        return [(int) $p['id'], $p['kodebpr']];
    }

    private function publicRows(array $rows): array
    {
        $fields = ($this->m())::fields();
        return array_map(static function ($r) use ($fields) {
            $o = ['id' => (int) $r['id']];
            foreach ($fields as $k => $f) {
                $o[$k] = (string) ($r[$f['col']] ?? '');
            }
            return $o;
        }, $rows);
    }

    private function hadApproval(int $pid, string $kode): bool
    {
        return ($this->m())::approvalSummary($this->model->listAll($pid, $kode))['any'];
    }

    /** Respons sukses setelah perubahan isi (approval direset bila sebelumnya ada). */
    private function changed(string $message, bool $hadApproval, bool $reload = false)
    {
        [$pid, $kode] = $this->scope();
        if ($hadApproval) {
            $this->model->resetApproval($pid, $kode);
            $message .= ' Status approval direset.';
        }
        return $this->json(200, [
            'ok' => true,
            'message' => $message,
            'rows' => $this->publicRows($this->model->listByPeriode($pid, $kode)),
            'reload' => $hadApproval || $reload,
        ]);
    }

    /** Nilai kolom field dari baris DB dalam format yang sama dengan readInput (untuk cek "tidak ada perubahan"). */
    private function normalized(array $row): array
    {
        $out = [];
        foreach (($this->m())::fields() as $f) {
            $v = (string) ($row[$f['col']] ?? '');
            $out[$f['col']] = ($f['type'] === 'percent' && is_numeric($v)) ? (string) (float) $v : $v;
        }
        return $out;
    }

    private function readInput(int $pid, string $kode, ?int $exceptId): array
    {
        $m = $this->m();
        $data = [];
        $errors = [];
        foreach ($m::fields() as $k => $f) {
            $v = trim((string) $this->request->getPost($k));
            if (isset($f['showIf']) && (string) $this->request->getPost($f['showIf'][0]) !== $f['showIf'][1]) {
                $data[$f['col']] = '';
                continue;
            }
            switch ($f['type']) {
                case 'nik':
                    $v = preg_replace('/\s+/', '', $v);
                    if (!preg_match('/^\d{16}$/', $v)) {
                        $errors[$k] = 'NIK harus 16 digit angka.';
                    }
                    break;
                case 'idnum':
                    $v = preg_replace('/\s+/', '', $v);
                    $jenis = (string) $this->request->getPost($f['typeField']);
                    $len = $f['lengths'][$jenis] ?? null;
                    if ($len !== null && !preg_match('/^\d{' . $len . '}$/', $v)) {
                        $errors[$k] = "{$jenis} harus {$len} digit angka.";
                    } elseif ($len === null && $v === '') {
                        $errors[$k] = 'Wajib diisi.';
                    }
                    break;
                case 'select':
                    if (!isset($f['options'][$v])) {
                        $errors[$k] = 'Pilih ' . strtolower($f['label']) . '.';
                    }
                    break;
                case 'percent':
                    $v = str_replace(',', '.', $v);
                    if ($v === '' || !is_numeric($v) || (float) $v < 0 || (float) $v > 100 || !preg_match('/^\d+(\.\d{1,2})?$/', $v)) {
                        $errors[$k] = 'Isi persentase 0 – 100 (maks. 2 desimal).';
                    } else {
                        $v = (string) (float) $v;
                    }
                    break;
                case 'rupiah':
                    $v = ltrim(preg_replace('/\D/', '', $v), '0');
                    if ($v === '') {
                        $v = '0';
                    }
                    if (strlen($v) > 15) {
                        $errors[$k] = 'Nilai terlalu besar.';
                    } elseif ((int) $v < ($f['min'] ?? 0)) {
                        $errors[$k] = 'Wajib diisi.';
                    }
                    break;
                case 'date':
                    $d = \DateTime::createFromFormat('!Y-m-d', $v);
                    if (!$d || $d->format('Y-m-d') !== $v) {
                        $errors[$k] = $v === '' ? 'Pilih tanggal.' : 'Format tanggal tidak valid.';
                    }
                    break;
                case 'number':
                    $min = $f['min'] ?? 0;
                    if ($v === '' || !ctype_digit($v) || (int) $v > $f['max'] || (int) $v < $min) {
                        $errors[$k] = "Isi angka {$min} – {$f['max']}.";
                    } else {
                        $v = (string) (int) $v;
                    }
                    break;
                default:
                    if ($v === '') {
                        $errors[$k] = 'Wajib diisi.';
                    } elseif (mb_strlen($v) > $f['max']) {
                        $errors[$k] = "Maksimal {$f['max']} karakter.";
                    }
            }
            $data[$f['col']] = $v;
        }
        if (!$errors) {
            $errors = $m::validateRow($data);
        }
        $u = $m::UNIQUE;
        if ($u !== null && !isset($errors[$u]) && $this->model->duplicate($data[$m::col($u)], $pid, $kode, $exceptId)) {
            $errors[$u] = $m::UNIQUE_MSG;
        }
        return [$data, $errors];
    }

    // halaman

    public function index()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }

        $m = $this->m();
        $cfg = $this->config();
        $periode = $this->periode();
        [$pid, $kode] = $periode ? $this->scope() : [0, ''];
        $all = $periode ? $this->model->listAll($pid, $kode) : [];
        $rows = $periode ? $this->model->listByPeriode($pid, $kode) : [];
        $nodata = $periode && $this->model->hasNodata($pid, $kode);
        $canEdit = $periode && $this->canEdit();

        $data = [
            'judul' => $cfg['judul'],
            'cfg' => $cfg + [
                'form' => $m::FORM_CODE,
                'fields' => $m::fields(),
                'titleField' => $m::TITLE_FIELD,
                'tagField' => $m::TAG_FIELD,
                'subField' => $m::SUB_FIELD,
                'subFormat' => $m::SUB_FORMAT,
                'bodyFields' => $m::BODY_FIELDS,
                'chipFields' => $m::CHIP_FIELDS,
                'chipSkip' => $m::CHIP_SKIP,
                'extraLines' => $m::EXTRA_LINES,
                'utama' => $m::UTAMA,
                'nodataEnabled' => $m::NODATA,
                'maxTindak' => $m::MAX_TINDAK,
                'base' => rtrim(site_url(strtolower($cfg['page'])), '/'),
                'export' => $cfg['exportMethod'] ?? null,
            ],
            'periode' => $periode,
            'rows' => $this->publicRows($rows),
            'nodata' => $nodata,
            'approval' => $m::approvalSummary($all),
            'tindak' => $periode ? $this->model->getTindak($pid, $kode) : null,
            'namabpr' => (new M_infobpr())->getBprByKode($this->userKodebpr)['namabpr'] ?? 'Nama BPR',
            'canEdit' => $canEdit,
            'canKomut' => $all && ($this->grp['admin'] || $this->grp['dekom']),
            'canDirut' => $all && ($this->grp['admin'] || $this->grp['direksi']),
            'isAdmin' => $this->grp['admin'],
            'userId' => $this->userId,
            'hasPrev' => $canEdit && $this->model->prevYearPeriodeId($kode, (int) $periode['tahun']) !== null,
        ];

        return view('templates/v_header', $data)
            . view('templates/v_sidebar')
            . view('templates/v_topbar')
            . view('Laporantahunan/Transparansi/_anggota', $data)
            . view('templates/v_footer');
    }

    // data

    public function save()
    {
        if ($e = $this->guardPost(true)) {
            return $e;
        }
        [$pid, $kode] = $this->scope();
        if ($this->model->hasNodata($pid, $kode)) {
            return $this->json(409, ['ok' => false, 'message' => 'Batalkan status "Tidak Ada Data" terlebih dahulu.']);
        }

        $id = (int) $this->request->getPost('id');
        $row = $id ? $this->model->findInPeriode($id, $pid, $kode) : null;
        if ($id && !$row) {
            return $this->json(404, ['ok' => false, 'message' => 'Data tidak ditemukan.']);
        }

        [$data, $errors] = $this->readInput($pid, $kode, $id ?: null);
        if ($errors) {
            return $this->json(422, ['ok' => false, 'message' => 'Periksa kembali isian yang ditandai.', 'errors' => $errors]);
        }

        if ($row && !array_diff_assoc($data, $this->normalized($row))) {
            return $this->json(200, ['ok' => true, 'message' => 'Tidak ada perubahan.', 'reload' => false]);
        }

        $hadApproval = $this->hadApproval($pid, $kode);
        $data += ['user_id' => $this->userId, 'fullname' => $this->fullname];

        try {
            $ok = $row
                ? (bool) $this->model->update((int) $row['id'], $data)
                : $this->model->insertRow($data + ['kodebpr' => $kode, 'periode_id' => $pid]);
        } catch (\Throwable $e) {
            log_message('error', static::class . '::save ' . $e->getMessage());
            $ok = false;
        }
        if (!$ok) {
            return $this->json(500, ['ok' => false, 'message' => 'Data gagal disimpan.']);
        }
        $entity = $this->config()['entity'];
        return $this->changed($row ? "Data {$entity} diperbarui." : "Data {$entity} ditambahkan.", $hadApproval);
    }

    public function hapus()
    {
        if ($e = $this->guardPost(true)) {
            return $e;
        }
        $m = $this->m();
        [$pid, $kode] = $this->scope();

        $row = $this->model->findInPeriode((int) $this->request->getPost('id'), $pid, $kode);
        if (!$row) {
            return $this->json(404, ['ok' => false, 'message' => 'Data tidak ditemukan.']);
        }
        $hadApproval = $this->hadApproval($pid, $kode);

        try {
            $ok = (bool) $this->model->delete((int) $row['id']);
        } catch (\Throwable $e) {
            log_message('error', static::class . '::hapus ' . $e->getMessage());
            $ok = false;
        }
        $t = $m::fields()[$m::TITLE_FIELD];
        $name = match ($t['type']) {
            'select' => $t['options'][$row[$t['col']]] ?? $row[$t['col']],
            'date' => ($ts = strtotime((string) $row[$t['col']])) ? 'rapat tanggal ' . date('d-m-Y', $ts) : $row[$t['col']],
            default => $row[$t['col']],
        };
        return $ok
            ? $this->changed('Data ' . $name . ' dihapus.', $hadApproval)
            : $this->json(500, ['ok' => false, 'message' => 'Data gagal dihapus.']);
    }

    public function nodata()
    {
        if ($e = $this->guardPost(true)) {
            return $e;
        }
        if (!($this->m())::NODATA) {
            return $this->json(404, ['ok' => false, 'message' => 'Tidak tersedia untuk halaman ini.']);
        }
        [$pid, $kode] = $this->scope();
        $act = (string) $this->request->getPost('act');
        $has = $this->model->hasNodata($pid, $kode);

        if ($act === 'set') {
            if ($has) {
                return $this->json(409, ['ok' => false, 'message' => 'Status "Tidak Ada Data" sudah aktif.']);
            }
            if ($this->model->listByPeriode($pid, $kode)) {
                return $this->json(409, ['ok' => false, 'message' => 'Hapus data yang ada terlebih dahulu sebelum menandai "Tidak Ada Data".']);
            }
            $ok = $this->model->setNodata($pid, $kode, $this->userId, $this->fullname);
            $msg = 'Ditandai "Tidak Ada Data".';
        } elseif ($act === 'cancel') {
            if (!$has) {
                return $this->json(409, ['ok' => false, 'message' => 'Status "Tidak Ada Data" tidak aktif.']);
            }
            $ok = $this->model->clearNodata($pid, $kode);
            $msg = 'Status "Tidak Ada Data" dibatalkan.';
        } else {
            return $this->json(422, ['ok' => false, 'message' => 'Permintaan tidak valid.']);
        }
        return $ok
            ? $this->json(200, ['ok' => true, 'message' => $msg, 'reload' => true])
            : $this->json(500, ['ok' => false, 'message' => 'Gagal memproses permintaan.']);
    }

    public function savetindak()
    {
        if ($e = $this->guardPost(true)) {
            return $e;
        }
        $m = $this->m();
        [$pid, $kode] = $this->scope();

        $data = [
            'tindaklanjut' => trim((string) $this->request->getPost('tindaklanjut')),
            'penjelasanlanjut' => trim((string) $this->request->getPost('penjelasanlanjut')),
        ];
        $errors = [];
        foreach ($data as $f => $v) {
            if (mb_strlen($v) > $m::MAX_TINDAK) {
                $errors[$f] = 'Maksimal ' . $m::MAX_TINDAK . ' karakter.';
            }
        }
        if ($errors) {
            return $this->json(422, ['ok' => false, 'message' => 'Periksa kembali isian yang ditandai.', 'errors' => $errors]);
        }

        $cur = $this->model->getTindak($pid, $kode);
        if ($this->config()['tindak'][1] === null) {
            $data['penjelasanlanjut'] = (string) ($cur['penjelasanlanjut'] ?? '');   // F02 tidak dipakai halaman ini
        }
        if ((string) ($cur['tindaklanjut'] ?? '') === $data['tindaklanjut'] && (string) ($cur['penjelasanlanjut'] ?? '') === $data['penjelasanlanjut']) {
            return $this->json(200, ['ok' => true, 'message' => 'Tidak ada perubahan.', 'reload' => false]);
        }

        $hadApproval = $this->hadApproval($pid, $kode);
        try {
            $ok = $this->model->saveTindak($cur ? (int) $cur['id'] : null, $data + [
                'kodebpr' => $kode, 'periode_id' => $pid, 'fullname' => $this->fullname, 'user_id' => $this->userId,
            ]);
        } catch (\Throwable $e) {
            log_message('error', static::class . '::savetindak ' . $e->getMessage());
            $ok = false;
        }
        return $ok
            ? $this->changed('Tindak lanjut disimpan.', $hadApproval)
            : $this->json(500, ['ok' => false, 'message' => 'Tindak lanjut gagal disimpan.']);
    }

    // sinkronisasi

    public function syncFromPrevPeriode()
    {
        if ($e = $this->guardPost(true)) {
            return $e;
        }
        $m = $this->m();
        $entity = $this->config()['entity'];
        [$pid, $kode] = $this->scope();
        $tahun = (int) $this->periode()['tahun'];
        $prevTahun = $tahun - 1;

        if ($this->model->hasNodata($pid, $kode)) {
            return $this->json(409, ['ok' => false, 'message' => 'Batalkan status "Tidak Ada Data" terlebih dahulu sebelum sinkronisasi.']);
        }
        $prevPid = $this->model->prevYearPeriodeId($kode, $tahun);
        if (!$prevPid) {
            return $this->json(404, ['ok' => false, 'message' => "Tidak ada data {$entity} tahun {$prevTahun}."]);
        }

        $cols = array_column($m::fields(), 'col');
        $sig = static fn(array $r): string => implode("\x1F", array_map(static fn($c) => (string) ($r[$c] ?? ''), $cols));
        $current = $this->model->listByPeriode($pid, $kode);
        $seenSig = array_flip(array_map($sig, $current));
        $uCol = $m::UNIQUE !== null ? $m::col($m::UNIQUE) : null;
        $seenUnique = $uCol ? array_flip(array_column($current, $uCol)) : [];
        $hadApproval = $this->hadApproval($pid, $kode);

        $added = 0;
        $nodataCopied = false;
        $tindakCopied = false;
        $db = db_connect();
        $db->transStart();

        $prevRows = $this->model->listByPeriode($prevPid, $kode);
        foreach ($prevRows as $r) {
            $r = $m::syncRow($r);
            $s = $sig($r);
            $u = $uCol ? (string) $r[$uCol] : null;
            if (isset($seenSig[$s]) || ($u !== null && isset($seenUnique[$u]) && !in_array($u, $m::UNIQUE_EXCEPT, true))) {
                continue;
            }
            $row = ['kodebpr' => $kode, 'periode_id' => $pid, 'user_id' => $this->userId, 'fullname' => $this->fullname];
            foreach ($cols as $c) {
                $row[$c] = $r[$c];
            }
            $this->model->insertRow($row);
            $seenSig[$s] = true;
            if ($u !== null) {
                $seenUnique[$u] = true;
            }
            $added++;
        }
        if (!$prevRows && !$current && $this->model->hasNodata($prevPid, $kode)) {
            $this->model->setNodata($pid, $kode, $this->userId, $this->fullname);
            $nodataCopied = true;
        }
        $prevTindak = $this->model->getTindak($prevPid, $kode);
        if ($prevTindak && !$this->model->getTindak($pid, $kode)
            && (trim((string) $prevTindak['tindaklanjut']) !== '' || trim((string) $prevTindak['penjelasanlanjut']) !== '')) {
            $this->model->saveTindak(null, [
                'tindaklanjut' => $prevTindak['tindaklanjut'], 'penjelasanlanjut' => $prevTindak['penjelasanlanjut'],
                'kodebpr' => $kode, 'periode_id' => $pid, 'fullname' => $this->fullname, 'user_id' => $this->userId,
            ]);
            $tindakCopied = true;
        }
        $db->transComplete();

        if (!$db->transStatus()) {
            log_message('error', static::class . '::syncFromPrevPeriode transaksi gagal');
            return $this->json(500, ['ok' => false, 'message' => 'Sinkronisasi gagal disimpan.']);
        }
        if (!$added && !$nodataCopied && !$tindakCopied) {
            return $this->json(200, ['ok' => true, 'message' => "Semua data {$entity} tahun {$prevTahun} sudah ada di tahun ini.", 'reload' => false]);
        }
        if ($hadApproval) {
            $this->model->resetApproval($pid, $kode);
        }

        $parts = [];
        if ($added) {
            $parts[] = "{$added} data {$entity} ditambahkan";
        }
        if ($nodataCopied) {
            $parts[] = 'status "Tidak Ada Data" disalin';
        }
        if ($tindakCopied) {
            $parts[] = 'tindak lanjut disalin';
        }
        return $this->json(200, ['ok' => true, 'reload' => true,
            'message' => "Sinkronisasi dari tahun {$prevTahun}: " . implode(', ', $parts) . '.']);
    }

    // ------------------------------------------------------------------ approval

    public function approve()
    {
        if ($e = $this->guardPost()) {
            return $e;
        }
        $m = $this->m();

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
        $rows = $this->model->listAll($pid, $kode);
        if (!$rows) {
            return $this->json(422, ['ok' => false, 'message' => 'Data belum diisi, tidak dapat di-approve.']);
        }
        $s = $m::approvalSummary($rows);
        $approved = $s[$type];

        if ($act === 'approve') {
            if ($approved) {
                return $this->json(409, ['ok' => false, 'message' => 'Sudah disetujui sebelumnya.']);
            }
            if ($type === 'dirut' && !$s['komut']) {
                return $this->json(409, ['ok' => false, 'message' => 'Komisaris Utama harus menyetujui terlebih dahulu.']);
            }
        } else {
            if (!$approved) {
                return $this->json(409, ['ok' => false, 'message' => 'Belum disetujui.']);
            }
            if (!$isAdmin && $s[$type . '_by'] !== $this->userId) {
                return $this->json(403, ['ok' => false, 'message' => 'Hanya yang menyetujui yang dapat membatalkan.']);
            }
        }

        try {
            $ok = $this->model->setApproval($pid, $kode, $type, $act === 'approve', $this->userId, $this->fullname);
        } catch (\Throwable $e) {
            log_message('error', static::class . '::approve ' . $e->getMessage());
            $ok = false;
        }

        $label = $type === 'komut' ? 'Komisaris Utama' : 'Direktur Utama';
        return $ok
            ? $this->json(200, ['ok' => true, 'message' => $act === 'approve' ? "Disetujui oleh {$label}." : "Persetujuan {$label} dibatalkan."])
            : $this->json(500, ['ok' => false, 'message' => 'Gagal memproses approval.']);
    }

    // export TXT

    public static function txtClean($text): string
    {
        $text = str_replace(["\r", "\n", "\t", '|'], ' ', (string) $text);
        $text = str_replace(["\xE2\x80\x9C", "\xE2\x80\x9D"], '"', $text);
        $text = str_replace(["\xE2\x80\x98", "\xE2\x80\x99"], "'", $text);
        $text = str_replace('%', ' persen ', $text);
        $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    protected function exportTxt()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }
        $periode = $this->periode();
        if (!$periode) {
            return redirect()->to(self::PERIODE_REDIRECT)->with('error', 'Periode tahunan aktif tidak ditemukan.');
        }
        $m = $this->m();
        [$pid, $kode] = $this->scope();
        $clean = [self::class, 'txtClean'];

        $bpr = (new M_infobpr())->getBprByKode($kode) ?? [];
        $sandibpr = $bpr['sandibpr'] ?? '';
        $kodejenis = $bpr['kodejenis'] ?? '';
        $tahun = $periode['tahun'] ?? date('Y');
        $form = $m::FORM_CODE;

        $output = "H01|{$kodejenis}|{$sandibpr}|{$tahun}-12-31|LTBPRK|{$form}|0|\r\n";
        $rows = $this->model->listByPeriode($pid, $kode);
        foreach ($rows as $r) {
            $output .= $this->model->txtLine($r, $clean) . "\r\n";
        }
        $nodataRow = null;
        if (!$rows && $m::NODATA) {
            foreach ($this->model->listAll($pid, $kode) as $r) {
                if ((int) ($r['is_null_record'] ?? 0) === 1) {
                    $nodataRow = $r;
                    break;
                }
            }
        }
        if (!$rows && ($empty = $this->model->txtEmptyLine(['sandibpr' => $sandibpr, 'kodejenis' => $kodejenis, 'tahun' => $tahun, 'nodata' => $nodataRow])) !== null) {
            $output .= $empty . "\r\n";
        }
        $t = $this->model->getTindak($pid, $kode);
        if ($t && trim((string) $t['tindaklanjut']) !== '') {
            $output .= 'F01|' . self::txtClean($t['tindaklanjut']) . "\r\n";
        } elseif ($m::TXT_EMPTY_F01) {
            $output .= "F01|\r\n";
        }
        if ($t && trim((string) $t['penjelasanlanjut']) !== '') {
            $output .= 'F02|' . self::txtClean($t['penjelasanlanjut']) . "\r\n";
        }

        $filename = "LTBPRK-{$form}-{$periode['jenispelaporan']}-A-{$tahun}1231-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }
}
