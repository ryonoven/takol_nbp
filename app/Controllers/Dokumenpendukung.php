<?php

namespace App\Controllers;

use App\Libraries\PdfLogo;
use App\Libraries\PdfMerger;
use App\Models\M_dokumenpendukung;
use App\Models\M_infobpr;
use App\Models\M_periodetahunan;
use App\Models\M_suratpernyataandireksi;
use App\Models\M_user;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RedirectResponse;
use Dompdf\Dompdf;
use Dompdf\Options;

class Dokumenpendukung extends Controller
{
    protected M_dokumenpendukung $dokModel;
    protected M_periodetahunan $periodeModel;
    protected M_infobpr $infobprModel;
    protected $auth;
    protected int $userId = 0;
    protected ?string $userKodebpr = null;

    private const VIEW = 'Laporantahunan/dokumenpendukung';
    private const VIEW_LEMBAR = 'Laporantahunan/pdf_lembarpernyataan';

    public function __construct()
    {
        $this->dokModel = new M_dokumenpendukung();
        $this->periodeModel = new M_periodetahunan();
        $this->infobprModel = new M_infobpr();
        $this->auth = service('authentication');
        if ($this->auth->check()) {
            $this->userId = (int) $this->auth->id();
            $this->userKodebpr = (new M_user())->find($this->userId)['kodebpr'] ?? null;
        }
    }

    private function checkAuth(): ?RedirectResponse
    {
        if (!$this->auth->check()) {
            session()->set('redirect_url', current_url());
            return redirect()->to('/login');
        }
        if (!session('active_periode')) {
            return redirect()->to('/periodetahunan')->with('error', 'Silakan pilih periode tahunan aktif terlebih dahulu.');
        }
        if (!$this->userKodebpr) {
            return redirect()->to('/periodetahunan')->with('error', 'User tidak memiliki kode BPR yang valid.');
        }
        return null;
    }

    private function periode(): ?array
    {
        $id = (int) session('active_periode');
        if ($id <= 0 || !$this->userKodebpr) {
            return null;
        }
        $p = $this->periodeModel->find($id);
        if (!$p) {
            return null;
        }
        $p['kodebpr'] = $this->userKodebpr;
        return $p;
    }

    private function canEdit(): bool
    {
        return in_groups(['pe', 'admin']);
    }

    private function json(int $status, array $payload)
    {
        $payload['csrf'] = ['name' => csrf_token(), 'hash' => csrf_hash()];
        return $this->response->setStatusCode($status)->setJSON($payload);
    }

    private function guardPost()
    {
        if (strtolower($this->request->getMethod()) !== 'post') {
            return $this->json(405, ['ok' => false, 'message' => 'Metode tidak diizinkan.']);
        }
        if (!$this->auth->check()) {
            return $this->json(401, ['ok' => false, 'message' => 'Sesi berakhir. Silakan login kembali.']);
        }
        if (!$this->canEdit()) {
            return $this->json(403, ['ok' => false, 'message' => 'Anda tidak memiliki akses untuk mengubah halaman ini.']);
        }
        $periode = $this->periode();
        if (!$periode) {
            return $this->json(422, ['ok' => false, 'message' => 'Periode tahunan aktif tidak ditemukan.']);
        }
        return [$periode, $this->dokModel->getOrCreate((int) $periode['id'], $periode['kodebpr'], $this->userId)];
    }

    private static function dir(string $kodebpr): string
    {
        return WRITEPATH . M_dokumenpendukung::PDF_DIR . preg_replace('/[^A-Za-z0-9_-]/', '', $kodebpr) . '/';
    }

    private static function slotPath(?array $row, string $slot): ?string
    {
        $col = M_dokumenpendukung::SLOTS[$slot][0] ?? null;
        $name = $row && $col ? (string) ($row[$col] ?? '') : '';
        if ($name === '' || !preg_match('/^[A-Za-z0-9_-]+\.pdf$/', $name)) {
            return null;
        }
        $path = self::dir((string) $row['kodebpr']) . $name;
        return is_file($path) ? $path : null;
    }

    private static function slotStatus(?array $row): array
    {
        $out = [];
        foreach (array_keys(M_dokumenpendukung::SLOTS) as $slot) {
            $p = self::slotPath($row, $slot);
            $out[$slot] = $p ? ['uploaded' => true, 'size' => filesize($p), 'at' => date('d/m/Y H:i', filemtime($p))] : ['uploaded' => false];
        }
        return $out;
    }

    private function ltbprkName(array $periode): string
    {
        $bpr = $this->infobprModel->getBprByKode($periode['kodebpr']) ?? [];
        return 'LTBPRK-' . M_dokumenpendukung::FORM_CODE . '-' . preg_replace('/[^A-Za-z0-9]/', '', (string) ($periode['jenispelaporan'] ?? ''))
            . '-A-' . (int) $periode['tahun'] . '1231-' . preg_replace('/[^A-Za-z0-9]/', '', (string) ($bpr['sandibpr'] ?? '')) . '-01.pdf';
    }

    private static function pdfResponse($response, string $body, string $filename, bool $download = false)
    {
        return $response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', ($download ? 'attachment' : 'inline') . '; filename="' . $filename . '"')
            ->setHeader('Cache-Control', 'private, max-age=0, must-revalidate')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody($body);
    }

    private function mergedPath(array $row): ?string
    {
        $paths = [];
        foreach (array_keys(M_dokumenpendukung::SLOTS) as $slot) {
            if (!$p = self::slotPath($row, $slot)) {
                return null;
            }
            $paths[] = $p;
        }

        $dir = WRITEPATH . 'cache/dokumenpendukung/';
        $prefix = preg_replace('/[^A-Za-z0-9_-]/', '', $row['kodebpr']) . '_' . (int) $row['periode_id'] . '_';
        $hash = substr(md5(implode('|', array_map(static fn($p) => basename($p) . ':' . filemtime($p) . ':' . filesize($p), $paths))), 0, 12);
        $out = $dir . $prefix . $hash . '.pdf';
        if (is_file($out)) {
            return $out;
        }

        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return null;
        }
        try {
            PdfMerger::merge($paths, $out . '.tmp');
            rename($out . '.tmp', $out);
        } catch (\Throwable $e) {
            log_message('error', '[Dokumenpendukung] gabung PDF: ' . $e->getMessage());
            @unlink($out . '.tmp');
            return null;
        }
        foreach (glob($dir . $prefix . '*.pdf') ?: [] as $old) {   // hapus gabungan lama
            if ($old !== $out) {
                @unlink($old);
            }
        }
        return $out;
    }

    // ------------------------------------------------------------------ halaman

    public function index()
    {
        if ($r = $this->checkAuth()) {
            return $r;
        }
        $periode = $this->periode();
        if (!$periode) {
            return redirect()->to('/periodetahunan')->with('error', 'Periode tahunan aktif tidak ditemukan.');
        }

        $row = $this->dokModel->getByPeriode((int) $periode['id'], $periode['kodebpr']);
        $anggota = $this->dokModel->getAnggota($row['id'] ?? null);

        // Isian awal dari Surat Pernyataan Direksi bila belum pernah diisi
        $surat = (new M_suratpernyataandireksi())->getByPeriode((int) $periode['id'], $periode['kodebpr']);
        $prefilled = false;
        if (!$anggota['direksi'] && $surat) {
            foreach ((new M_suratpernyataandireksi())->getDireksi((int) $surat['id']) as $d) {
                $anggota['direksi'][] = ['nama' => $d['nama'], 'jabatan' => $d['jabatan']];
                $prefilled = true;
            }
        }
        foreach (M_dokumenpendukung::JENIS as $jenis => [, $contoh]) {
            if (!$anggota[$jenis]) {
                $anggota[$jenis][] = ['nama' => '', 'jabatan' => $contoh];
            }
        }

        $data = [
            'judul' => 'Dokumen Pendukung',
            'periode' => $periode,
            'tahun' => $periode['tahun'],
            'namabpr' => $this->infobprModel->getBprByKode($periode['kodebpr'])['namabpr'] ?? '',
            'row' => $row ?? [],
            'kota' => $row['kota'] ?? ($surat['kota'] ?? ''),
            'tanggalsurat' => $row['tanggalsurat'] ?? ($surat['tanggalsurat'] ?? ''),
            'anggota' => $anggota,
            'prefilled' => $prefilled,
            'hasAnggota' => (bool) ($row && $this->dokModel->getAnggota((int) $row['id'])['direksi']),
            'hasSurat' => (bool) $surat,
            'slots' => self::slotStatus($row),
            'complete' => M_dokumenpendukung::isComplete($row),
            'canEdit' => $this->canEdit(),
            'pdfMaxKb' => M_dokumenpendukung::PDF_MAX_KB,
            'mergedName' => $this->ltbprkName($periode),
        ];

        return view('templates/v_header', $data)
            . view('templates/v_sidebar')
            . view('templates/v_topbar')
            . view(self::VIEW, $data)
            . view('templates/v_footer');
    }

    public function save()
    {
        $g = $this->guardPost();
        if (!is_array($g)) {
            return $g;
        }
        [, $row] = $g;
        $post = $this->request->getPost();
        $errors = [];

        $kota = trim((string) ($post['kota'] ?? ''));
        if ($kota === '') {
            $errors['kota'] = 'Wajib diisi.';
        } elseif (mb_strlen($kota) > M_dokumenpendukung::MAX_KOTA) {
            $errors['kota'] = 'Maksimal ' . M_dokumenpendukung::MAX_KOTA . ' karakter.';
        }
        $tgl = trim((string) ($post['tanggalsurat'] ?? ''));
        $dt = $tgl === '' ? null : \DateTime::createFromFormat('!Y-m-d', $tgl);
        if ($tgl === '') {
            $errors['tanggalsurat'] = 'Wajib diisi.';
        } elseif (!$dt || $dt->format('Y-m-d') !== $tgl) {
            $errors['tanggalsurat'] = 'Tanggal tidak valid.';
        }

        $anggota = [];
        foreach (M_dokumenpendukung::JENIS as $jenis => [$judul]) {
            $nama = (array) ($post[$jenis . '_nama'] ?? []);
            $jab = (array) ($post[$jenis . '_jabatan'] ?? []);
            $anggota[$jenis] = [];
            for ($i = 0, $n = max(count($nama), count($jab)); $i < $n; $i++) {
                $a = ['nama' => trim((string) ($nama[$i] ?? '')), 'jabatan' => trim((string) ($jab[$i] ?? ''))];
                if ($a['nama'] === '' && $a['jabatan'] === '') {
                    continue;
                }
                foreach ($a as $f => $v) {
                    if ($v === '') {
                        $errors["{$jenis}_{$f}.{$i}"] = 'Wajib diisi.';
                    } elseif (mb_strlen($v) > M_dokumenpendukung::MAX_NAMA) {
                        $errors["{$jenis}_{$f}.{$i}"] = 'Maksimal ' . M_dokumenpendukung::MAX_NAMA . ' karakter.';
                    }
                }
                $anggota[$jenis][] = $a;
            }
            $c = count($anggota[$jenis]);
            if ($c < M_dokumenpendukung::MIN_ANGGOTA) {
                $errors[$jenis] = "Isi minimal satu anggota {$judul}.";
            } elseif ($c > M_dokumenpendukung::MAX_ANGGOTA) {
                $errors[$jenis] = 'Maksimal ' . M_dokumenpendukung::MAX_ANGGOTA . " anggota {$judul}.";
            }
        }

        if ($errors) {
            return $this->json(422, ['ok' => false, 'message' => 'Periksa kembali isian yang ditandai.', 'errors' => $errors]);
        }

        try {
            $ok = $this->dokModel->saveAnggota((int) $row['id'], ['kota' => $kota, 'tanggalsurat' => $tgl, 'user_id' => $this->userId], $anggota);
        } catch (\Throwable $e) {
            log_message('error', 'Dokumenpendukung::save ' . $e->getMessage());
            $ok = false;
        }
        return $ok
            ? $this->json(200, ['ok' => true, 'message' => 'Data Direksi & Dewan Komisaris tersimpan. Template Lembar Pernyataan diperbarui.'])
            : $this->json(500, ['ok' => false, 'message' => 'Data gagal disimpan.']);
    }

    public function upload()
    {
        $g = $this->guardPost();
        if (!is_array($g)) {
            return $g;
        }
        [$periode, $row] = $g;

        $slot = (string) $this->request->getPost('slot');
        if (!isset(M_dokumenpendukung::SLOTS[$slot])) {
            return $this->json(422, ['ok' => false, 'message' => 'Bagian tidak valid.']);
        }
        [$col, $prefix] = M_dokumenpendukung::SLOTS[$slot];

        $file = $this->request->getFile('pdf');
        $maxKb = M_dokumenpendukung::PDF_MAX_KB;
        if (!$file || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return $this->json(422, ['ok' => false, 'message' => 'Pilih file PDF terlebih dahulu.']);
        }
        if (!$file->isValid() || $file->hasMoved()) {
            return $this->json(422, ['ok' => false, 'message' => 'Upload gagal: ' . $file->getErrorString()]);
        }
        if (strtolower($file->getClientExtension()) !== 'pdf' || $file->getMimeType() !== 'application/pdf'
            || file_get_contents($file->getTempName(), false, null, 0, 5) !== '%PDF-') {
            return $this->json(422, ['ok' => false, 'message' => 'File harus berformat PDF yang valid.']);
        }
        if ($file->getSize() > $maxKb * 1024) {
            return $this->json(422, ['ok' => false, 'message' => 'Ukuran file maksimal ' . ($maxKb / 1024) . ' MB.']);
        }

        $dir = self::dir($periode['kodebpr']);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return $this->json(500, ['ok' => false, 'message' => 'Folder upload tidak dapat dibuat.']);
        }
        $name = $prefix . (int) $periode['id'] . '_' . bin2hex(random_bytes(6)) . '.pdf';
        try {
            $file->move($dir, $name);
        } catch (\Throwable $e) {
            log_message('error', 'Dokumenpendukung::upload ' . $e->getMessage());
            return $this->json(500, ['ok' => false, 'message' => 'File gagal disimpan.']);
        }

        $path = $dir . $name;
        $ready = PdfMerger::prepare($path);
        if ($ready === null) {
            @unlink($path);
            return $this->json(422, ['ok' => false, 'message' => 'PDF tidak dapat diproses untuk digabung (format PDF terkompresi). '
                . 'Simpan ulang file melalui "Print > Save as PDF / Microsoft Print to PDF", lalu unggah kembali.']);
        }
        if ($ready !== $path) {
            rename($ready, $path);   // simpan versi ternormalisasi
        }

        $old = self::slotPath($row, $slot);
        if (!$this->dokModel->update((int) $row['id'], [$col => $name, 'user_id' => $this->userId])) {
            @unlink($path);
            return $this->json(500, ['ok' => false, 'message' => 'Data gagal disimpan.']);
        }
        if ($old) {
            @unlink($old);
        }

        $row = $this->dokModel->find((int) $row['id']);
        return $this->json(200, ['ok' => true, 'message' => 'PDF berhasil diunggah.', 'slots' => self::slotStatus($row), 'complete' => M_dokumenpendukung::isComplete($row)]);
    }

    public function hapus()
    {
        $g = $this->guardPost();
        if (!is_array($g)) {
            return $g;
        }
        [, $row] = $g;
        $slot = (string) $this->request->getPost('slot');
        if (!isset(M_dokumenpendukung::SLOTS[$slot])) {
            return $this->json(422, ['ok' => false, 'message' => 'Bagian tidak valid.']);
        }
        $old = self::slotPath($row, $slot);
        $this->dokModel->update((int) $row['id'], [M_dokumenpendukung::SLOTS[$slot][0] => null]);
        if ($old) {
            @unlink($old);
        }
        $row = $this->dokModel->find((int) $row['id']);
        return $this->json(200, ['ok' => true, 'message' => 'PDF dihapus.', 'slots' => self::slotStatus($row), 'complete' => false]);
    }

    // ------------------------------------------------------------------ PDF

    /** Lihat PDF unggahan satu slot. */
    public function file($slot = '')
    {
        if ($r = $this->checkAuth()) {
            return $r;
        }
        $periode = $this->periode();
        $row = $periode ? $this->dokModel->getByPeriode((int) $periode['id'], $periode['kodebpr']) : null;
        $path = isset(M_dokumenpendukung::SLOTS[$slot]) ? self::slotPath($row, $slot) : null;
        if (!$path) {
            return redirect()->to(site_url('dokumenpendukung'))->with('error', 'File belum diunggah.');
        }
        return self::pdfResponse($this->response, (string) file_get_contents($path), $slot . '_' . (int) $periode['tahun'] . '.pdf');
    }

    public function lembar()
    {
        if ($r = $this->checkAuth()) {
            return $r;
        }
        $periode = $this->periode();
        $row = $periode ? $this->dokModel->getByPeriode((int) $periode['id'], $periode['kodebpr']) : null;
        $anggota = $this->dokModel->getAnggota($row['id'] ?? null);
        if (!$row || !$anggota['direksi'] || !$anggota['dekom']) {
            return redirect()->to(site_url('dokumenpendukung'))->with('error', 'Lengkapi dan simpan daftar Direksi & Dewan Komisaris (Bagian 1) terlebih dahulu.');
        }

        $bpr = $this->infobprModel->getBprByKode($periode['kodebpr']) ?? [];
        $o = new Options();
        $o->set('isRemoteEnabled', false);
        $o->set('isPhpEnabled', false);
        $o->set('defaultFont', 'Helvetica');
        $o->set('tempDir', WRITEPATH . 'cache');
        $o->set('fontCache', WRITEPATH . 'cache');
        $pdf = new Dompdf($o);
        $pdf->loadHtml(view(self::VIEW_LEMBAR, [
            'row' => $row, 'bpr' => $bpr, 'logo' => PdfLogo::fromBpr($bpr), 'tahun' => $periode['tahun'], 'anggota' => $anggota,
        ]));
        $pdf->setPaper('A4');
        $pdf->render();

        return self::pdfResponse($this->response, $pdf->output(), 'Lembar_Pernyataan_Direksi_Dekom_' . (int) $periode['tahun'] . '.pdf');
    }

    public function gabungan()
    {
        if ($r = $this->checkAuth()) {
            return $r;
        }
        $periode = $this->periode();
        $row = $periode ? $this->dokModel->getByPeriode((int) $periode['id'], $periode['kodebpr']) : null;
        $path = $row ? $this->mergedPath($row) : null;
        if (!$path) {
            return redirect()->to(site_url('dokumenpendukung'))->with('error', 'PDF gabungan belum tersedia: unggah ketiga dokumen terlebih dahulu.');
        }
        return self::pdfResponse($this->response, (string) file_get_contents($path), $this->ltbprkName($periode));
    }

    public function exportpdf()
    {
        if ($r = $this->checkAuth()) {
            return $r;
        }
        $periode = $this->periode();
        $row = $periode ? $this->dokModel->getByPeriode((int) $periode['id'], $periode['kodebpr']) : null;
        $path = $row ? $this->mergedPath($row) : null;
        if (!$path) {
            return redirect()->to(site_url('dokumenpendukung'))->with('error', 'PDF gabungan belum tersedia.');
        }
        return self::pdfResponse(service('response'), (string) file_get_contents($path), $this->ltbprkName($periode), true);
    }
}
