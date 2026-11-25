<?php

namespace App\Controllers;

use App\Models\M_infobpr;
use App\Models\M_penjelasanumum;
use App\Models\M_periodetahunan;
use App\Models\M_user;
use CodeIgniter\Controller;
use Myth\Auth\Config\Services as AuthServices;

class Penjelasanumum extends Controller
{
    private const VIEW = 'Laporantahunan/Transparansi/penjelasanumum';
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
        $this->model = new M_penjelasanumum();
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

    /** Periode aktif + kodebpr user; null bila tidak valid. */
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

    /** Validasi umum endpoint POST; mengembalikan response error atau null. */
    private function guardPost()
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
        return null;
    }

    // ------------------------------------------------------------------ halaman

    public function index()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }

        $periode = $this->periode();
        $row = $periode ? $this->model->getByPeriode((int) $periode['id'], $periode['kodebpr']) : null;
        $bpr = (new M_infobpr())->getBprByKode($this->userKodebpr) ?? [];
        $canEdit = $periode && $this->canEdit();

        // Form baru: identitas BPR diisi otomatis dari Info BPR
        $form = $row ?? [
            'namabpr' => $bpr['namabpr'] ?? '',
            'alamat' => $bpr['alamat'] ?? '',
            'nomor' => $bpr['nomor'] ?? '',
        ];

        $data = [
            'judul' => 'Penjelasan Umum',
            'periode' => $periode,
            'row' => $row,
            'form' => $form,
            'max' => M_penjelasanumum::MAX,
            'peringkat' => M_penjelasanumum::PERINGKAT,
            'namabpr' => $bpr['namabpr'] ?? 'Nama BPR',
            'canEdit' => $canEdit,
            'canKomut' => $row && ($this->grp['admin'] || $this->grp['dekom']),
            'canDirut' => $row && ($this->grp['admin'] || $this->grp['direksi']),
            'isAdmin' => $this->grp['admin'],
            'userId' => $this->userId,
            // Tombol sinkronisasi (editor saja): aktif bila data tahun sebelumnya ada
            'hasPrev' => $canEdit && $this->model->getPrevYear($periode['kodebpr'], (int) $periode['tahun']) !== null,
        ];

        return view('templates/v_header', $data)
            . view('templates/v_sidebar')
            . view('templates/v_topbar')
            . view(self::VIEW, $data)
            . view('templates/v_footer');
    }

    public function save()
    {
        if ($e = $this->guardPost()) {
            return $e;
        }
        if (!$this->canEdit()) {
            return $this->json(403, ['ok' => false, 'message' => 'Anda tidak memiliki akses untuk mengubah data ini.']);
        }

        $periode = $this->periode();
        $row = $this->model->getByPeriode((int) $periode['id'], $periode['kodebpr']);

        $data = [];
        $errors = [];
        foreach (M_penjelasanumum::MAX as $field => $max) {
            $v = trim((string) $this->request->getPost($field));
            if ($v === '') {
                $errors[$field] = 'Wajib diisi.';
            } elseif (mb_strlen($v) > $max) {
                $errors[$field] = "Maksimal {$max} karakter.";
            }
            $data[$field] = $v;
        }
        if (!isset($errors['peringkatkomposit']) && !isset(M_penjelasanumum::PERINGKAT[$data['peringkatkomposit']])) {
            $errors['peringkatkomposit'] = 'Pilih peringkat komposit.';
        }
        if ($errors) {
            return $this->json(422, ['ok' => false, 'message' => 'Periksa kembali isian yang ditandai.', 'errors' => $errors]);
        }

        $changed = !$row;
        foreach ($row ? $data : [] as $k => $v) {
            if ((string) ($row[$k] ?? '') !== $v) {
                $changed = true;
                break;
            }
        }
        if (!$changed) {
            return $this->json(200, ['ok' => true, 'message' => 'Tidak ada perubahan.', 'reload' => false]);
        }

        // Mengubah isi me-reset kedua approval.
        $wasApproved = $row && ((int) $row['accdekom'] === 1 || (int) $row['is_approved'] === 1);
        $data += M_penjelasanumum::resetApprovalData() + [
            'user_id' => $this->userId,
            'fullname' => $this->fullname,
            'kodebpr' => $periode['kodebpr'],
            'periode_id' => (int) $periode['id'],
        ];

        try {
            $ok = $this->model->saveRow($row ? (int) $row['id'] : null, $data);
        } catch (\Throwable $e) {
            log_message('error', 'Penjelasanumum::save ' . $e->getMessage());
            $ok = false;
        }

        if (!$ok) {
            return $this->json(500, ['ok' => false, 'message' => 'Data gagal disimpan.']);
        }
        return $this->json(200, [
            'ok' => true,
            'message' => $wasApproved ? 'Data tersimpan. Status approval direset.' : 'Data berhasil disimpan.',
            // Muat ulang bila tampilan approval perlu berubah (data baru / approval direset)
            'reload' => !$row || $wasApproved,
        ]);
    }

    /**
     * Sinkronisasi: salin data Penjelasan Umum tahun sebelumnya (tahun - 1) ke periode aktif
     * dan langsung disimpan (insert bila belum ada, update bila sudah ada). Approval di-reset.
     */
    public function syncFromPrevPeriode()
    {
        if ($e = $this->guardPost()) {
            return $e;
        }
        if (!$this->canEdit()) {
            return $this->json(403, ['ok' => false, 'message' => 'Tidak memiliki akses untuk sinkronisasi data.']);
        }

        $periode = $this->periode();
        $prevTahun = (int) $periode['tahun'] - 1;
        $prev = $this->model->getPrevYear($periode['kodebpr'], (int) $periode['tahun']);
        if (!$prev) {
            return $this->json(404, ['ok' => false, 'message' => "Tidak ada data Penjelasan Umum tahun {$prevTahun}."]);
        }

        $row = $this->model->getByPeriode((int) $periode['id'], $periode['kodebpr']);

        $data = [];
        foreach (array_keys(M_penjelasanumum::MAX) as $f) {
            $data[$f] = (string) ($prev[$f] ?? '');
        }

        $same = $row !== null;
        foreach ($row ? $data : [] as $k => $v) {
            if ((string) ($row[$k] ?? '') !== $v) {
                $same = false;
                break;
            }
        }
        if ($same) {
            return $this->json(200, ['ok' => true, 'message' => "Data sudah sama dengan tahun {$prevTahun}.", 'reload' => false]);
        }

        $data += M_penjelasanumum::resetApprovalData() + [
            'user_id' => $this->userId,
            'fullname' => $this->fullname,
            'kodebpr' => $periode['kodebpr'],
            'periode_id' => (int) $periode['id'],
        ];

        try {
            $ok = $this->model->saveRow($row ? (int) $row['id'] : null, $data);
        } catch (\Throwable $e) {
            log_message('error', 'Penjelasanumum::syncFromPrevPeriode ' . $e->getMessage());
            $ok = false;
        }

        return $ok
            ? $this->json(200, ['ok' => true, 'reload' => true,
                'message' => "Data tahun {$prevTahun} berhasil disinkronkan ke tahun {$periode['tahun']}."])
            : $this->json(500, ['ok' => false, 'message' => 'Sinkronisasi gagal disimpan.']);
    }

    public function approve()
    {
        if ($e = $this->guardPost()) {
            return $e;
        }

        $type = (string) $this->request->getPost('type');   // komut | dirut
        $act = (string) $this->request->getPost('act');     // approve | cancel
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

        try {
            $ok = $this->model->setApproval((int) $row['id'], $type, $act === 'approve', $this->userId, $this->fullname);
        } catch (\Throwable $e) {
            log_message('error', 'Penjelasanumum::approve ' . $e->getMessage());
            $ok = false;
        }

        $label = $type === 'komut' ? 'Komisaris Utama' : 'Direktur Utama';
        return $ok
            ? $this->json(200, ['ok' => true, 'message' => $act === 'approve' ? "Disetujui oleh {$label}." : "Persetujuan {$label} dibatalkan."])
            : $this->json(500, ['ok' => false, 'message' => 'Gagal memproses approval.']);
    }

    // ------------------------------------------------------------------ export TXT

    private static function txtClean($text): string
    {
        $text = str_replace(["\r", "\n", "\t", '|'], ' ', (string) $text);
        $text = preg_replace('/[^a-zA-Z0-9 @#$%^&*(){}[\]<=>~_\-+`\'".,;:\\/?!]/', '', $text);
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    /**
     * TXT LTBPRK E0100 (format tidak berubah dari versi sebelumnya).
     * Memakai service('response') karena juga dipanggil langsung oleh controller ZIP.
     */
    public function exporttxtpenjelasanumum()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }
        $periode = $this->periode();
        if (!$periode) {
            return redirect()->to(self::PERIODE_REDIRECT)->with('error', 'Periode tahunan aktif tidak ditemukan.');
        }

        $row = $this->model->getByPeriode((int) $periode['id'], $periode['kodebpr']);
        if (!$row) {
            return redirect()->to(site_url('penjelasanumum'))->with('error', 'Data belum diisi, tidak dapat diekspor.');
        }

        $bpr = (new M_infobpr())->getBprByKode($periode['kodebpr']) ?? [];
        $sandibpr = $bpr['sandibpr'] ?? '';
        $tahun = $periode['tahun'] ?? date('Y');
        $form = M_penjelasanumum::FORM_CODE;

        $output = "H01|010201|{$sandibpr}|{$tahun}-12-31|LTBPRK|{$form}|0|\r\n"
            . 'D01|000100000000|' . self::txtClean($row['alamat']) . '|' . self::txtClean($row['nomor'])
            . '|' . self::txtClean($row['penjelasan']) . '|' . self::txtClean($row['peringkatkomposit'])
            . '|' . self::txtClean($row['penjelasankomposit']) . "\r\n";

        $filename = "LTBPRK-{$form}-{$periode['jenispelaporan']}-A-{$tahun}1231-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }
}
