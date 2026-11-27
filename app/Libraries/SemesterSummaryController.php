<?php

namespace App\Libraries;

/**
 * Basis halaman ringkasan laporan semesteran (Audit Intern, Direktur YMF Kepatuhan).
 *
 *  - index      : status setiap bagian (1 query UNION) + dokumen PDF + (opsional) lembar persetujuan; tidak menulis DB.
 *  - uploadPdf  : unggah / ganti dokumen PDF (POST multipart, JSON). Hanya PE/Admin.
 *  - deletePdf  : hapus dokumen PDF (POST JSON). Hanya PE/Admin.
 *  - downloadPdf: tampilkan dokumen — hanya milik BPR user (atau admin).
 *  - saveTtd    : simpan lembar persetujuan (bila TTD diisi). Hanya PE/Admin.
 * Baris ringkasan (tabel SUMMARY_TABLE) dibuat saat pertama kali disimpan.
 */
abstract class SemesterSummaryController extends TksBaseController
{
    protected const PERIODE_URL = '/Periodesemesteran';

    public const MAX_PDF_KB = 10240;

    public const JUDUL = '';
    public const KICKER = 'Laporan Semesteran';

    /** key => [judul, controller, tabel, punya kolom is_null_record (default true)] */
    public const SECTIONS = [];

    /** Tabel ringkasan, kolom nama file, folder file (di WRITEPATH/uploads/). */
    public const SUMMARY_TABLE = '';
    public const FILE_COL = '';
    public const FILE_DIR = '';
    /** [judul kartu, deskripsi, nama file saat diunduh, label kelengkapan] */
    public const FILE_TEXT = ['Dokumen Pendukung', '', 'Dokumen.pdf', 'Dokumen pendukung (PDF)'];

    /** URL cetak laporan lengkap. */
    public const PRINT_URL = '';

    /** Lembar persetujuan: kolom => [label, type text|date, max]. Kosong = tidak ada. */
    public const TTD = [];
    /** URL cetak lembar pengesahan (bila TTD). */
    public const TTD_PRINT_URL = '';

    /** Nilai awal kolom NOT NULL tanpa default saat baris ringkasan dibuat. */
    protected const EXTRA_DEFAULTS = [];

    private function dir(): string
    {
        return WRITEPATH . 'uploads/' . static::FILE_DIR . '/';
    }

    private function table()
    {
        return db_connect()->table(static::SUMMARY_TABLE);
    }

    protected function summaryRow(): ?array
    {
        return $this->table()->where(['kodebpr' => (string) $this->userKodebpr, 'periode_id' => $this->periodeId])
            ->get()->getRowArray() ?: null;
    }

    /** Buat baris ringkasan bila belum ada; kembalikan id. */
    private function ensureRow(?array $row): int
    {
        if ($row) {
            return (int) $row['id'];
        }
        [$sem, $tahun] = $this->semesters();
        $this->table()->insert([
            'user_id' => $this->userId, 'kodebpr' => (string) $this->userKodebpr, 'periode_id' => $this->periodeId,
            'tahun' => $tahun, 'semester' => (string) $sem, static::FILE_COL => '',
        ] + static::EXTRA_DEFAULTS);
        return (int) db_connect()->insertID();
    }

    /** Status setiap bagian dalam satu query. */
    private function sections(): array
    {
        $sql = [];
        $bind = [];
        foreach (static::SECTIONS as $key => $s) {
            $null = ($s[3] ?? true) ? 'MAX(is_null_record)' : '0';
            // Bagian dapat berisi banyak baris: disetujui bila SELURUH baris disetujui
            $sql[] = "SELECT '{$key}' AS src, COUNT(*) AS n, SUM(accdir2 = 1) AS ok, MAX(accdir2_at) AS accdir2_at,
                        {$null} AS is_null_record FROM {$s[2]} WHERE kodebpr = ? AND periode_id = ?";
            array_push($bind, (string) $this->userKodebpr, $this->periodeId);
        }
        $rows = [];
        foreach (db_connect()->query(implode(' UNION ALL ', $sql), $bind)->getResultArray() as $r) {
            $rows[$r['src']] = $r;
        }
        $out = [];
        foreach (static::SECTIONS as $key => $s) {
            $r = $rows[$key] ?? null;
            $n = (int) ($r['n'] ?? 0);
            $out[$key] = [
                'name' => $s[0],
                'url' => base_url($s[1]),
                'filled' => $n > 0,
                'count' => $n,
                'nodata' => $n > 0 && (int) $r['is_null_record'] === 1,
                'approved' => $n > 0 && (int) $r['ok'] === $n,
                'approved_at' => $r['accdir2_at'] ?? null,
            ];
        }
        return $out;
    }

    /** Lembar persetujuan lengkap? */
    protected static function ttdComplete(?array $row): bool
    {
        foreach (array_keys(static::TTD) as $k) {
            $v = trim((string) ($row[$k] ?? ''));
            if ($v === '' || $v === '0000-00-00') {
                return false;
            }
        }
        return true;
    }

    // ------------------------------------------------------------------ halaman

    public function index()
    {
        if ($r = $this->guardPage()) {
            return $r;
        }
        $row = $this->summaryRow();
        $file = null;
        $fn = $row[static::FILE_COL] ?? '';
        if ($fn !== '' && is_file($path = $this->dir() . basename($fn))) {
            $file = ['name' => $fn, 'size' => filesize($path), 'time' => filemtime($path)];
        }
        [$sem, $tahun] = $this->semesters();
        $route = strtolower((new \ReflectionClass($this))->getShortName());

        return $this->render('Laporansemester/_semestersummary', [
            'judul' => static::JUDUL,
            'cfg' => [
                'route' => $route,
                'kicker' => static::KICKER,
                'file_text' => static::FILE_TEXT,
                'print_url' => static::PRINT_URL,
                'ttd' => static::TTD,
                'ttd_print_url' => static::TTD_PRINT_URL,
            ],
            'bprData' => $this->infobprModel->getBprByKode((string) $this->userKodebpr) ?? [],
            'periodeLabel' => 'Semester ' . ($sem === 1 ? 'I' : 'II') . ' ' . $tahun,
            'sections' => $this->sections(),
            'row' => $row ?? [],
            'ttdComplete' => static::TTD ? static::ttdComplete($row) : true,
            'file' => $file,
            'canEdit' => $this->canEdit(),
            'maxKb' => self::MAX_PDF_KB,
        ]);
    }

    // ------------------------------------------------------------------ lembar persetujuan

    public function saveTtd()
    {
        if ($r = $this->guardPost()) {
            return $r;
        }
        if (!static::TTD) {
            return $this->json(404, ['ok' => false, 'message' => 'Tidak tersedia.']);
        }
        $data = [];
        $errors = [];
        foreach (static::TTD as $k => [$label, $type, $max]) {
            $v = trim((string) $this->request->getPost($k));
            if ($v === '') {
                $errors[$k] = 'Wajib diisi.';
            } elseif ($type === 'date') {
                $d = \DateTime::createFromFormat('!Y-m-d', $v);
                if (!$d || $d->format('Y-m-d') !== $v) {
                    $errors[$k] = 'Format tanggal tidak valid.';
                }
            } elseif (mb_strlen($v) > $max) {
                $errors[$k] = "Maksimal {$max} karakter.";
            }
            $data[$k] = $v;
        }
        if ($errors) {
            return $this->json(422, ['ok' => false, 'message' => 'Periksa kembali isian yang ditandai.', 'errors' => $errors]);
        }
        $row = $this->summaryRow();
        if ($row && !array_diff_assoc($data, array_intersect_key($row, $data))) {
            return $this->json(200, ['ok' => true, 'message' => 'Tidak ada perubahan.']);
        }
        try {
            $id = $this->ensureRow($row);
            $this->table()->where('id', $id)->update($data);
        } catch (\Throwable $e) {
            log_message('error', static::class . '::saveTtd ' . $e->getMessage());
            return $this->json(500, ['ok' => false, 'message' => 'Data gagal disimpan.']);
        }
        return $this->json(200, ['ok' => true, 'reload' => true, 'message' => 'Lembar persetujuan disimpan.']);
    }

    // ------------------------------------------------------------------ dokumen PDF

    public function uploadPdf()
    {
        if ($r = $this->guardPost()) {
            return $r;
        }
        $f = $this->request->getFile('pdf');
        if (!$f || !$f->isValid() || $f->hasMoved()) {
            return $this->json(422, ['ok' => false, 'message' => 'Pilih file PDF terlebih dahulu.']);
        }
        if ($f->getSizeByUnit('kb') > self::MAX_PDF_KB) {
            return $this->json(422, ['ok' => false, 'message' => 'Ukuran file maksimal ' . (self::MAX_PDF_KB / 1024) . ' MB.']);
        }
        $fh = @fopen($f->getTempName(), 'rb');
        $magic = $fh ? fread($fh, 5) : '';
        if ($fh) {
            fclose($fh);
        }
        if (strtolower($f->getClientExtension()) !== 'pdf' || $magic !== '%PDF-') {
            return $this->json(422, ['ok' => false, 'message' => 'File harus berupa dokumen PDF yang valid.']);
        }

        $dir = $this->dir();
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return $this->json(500, ['ok' => false, 'message' => 'Folder penyimpanan tidak dapat dibuat.']);
        }
        $row = $this->summaryRow();
        $old = $row[static::FILE_COL] ?? '';
        $newName = $f->getRandomName();
        try {
            $f->move($dir, $newName);
            $id = $this->ensureRow($row);
            $this->table()->where('id', $id)->update([static::FILE_COL => $newName]);
        } catch (\Throwable $e) {
            @unlink($dir . $newName);
            log_message('error', static::class . '::uploadPdf ' . $e->getMessage());
            return $this->json(500, ['ok' => false, 'message' => 'File gagal disimpan.']);
        }
        // Hapus file lama setelah file baru tersimpan
        if ($old !== '' && $old !== $newName) {
            @unlink($dir . basename($old));
        }
        return $this->json(200, ['ok' => true, 'reload' => true, 'message' => static::FILE_TEXT[0] . ' berhasil diunggah.']);
    }

    public function deletePdf()
    {
        if ($r = $this->guardPost()) {
            return $r;
        }
        $row = $this->summaryRow();
        $fn = $row[static::FILE_COL] ?? '';
        if ($fn === '') {
            return $this->json(200, ['ok' => true, 'reload' => true, 'message' => 'Belum ada dokumen.']);
        }
        try {
            $this->table()->where('id', (int) $row['id'])->update([static::FILE_COL => '']);
        } catch (\Throwable $e) {
            log_message('error', static::class . '::deletePdf ' . $e->getMessage());
            return $this->json(500, ['ok' => false, 'message' => 'Dokumen gagal dihapus.']);
        }
        @unlink($this->dir() . basename($fn));
        return $this->json(200, ['ok' => true, 'reload' => true, 'message' => static::FILE_TEXT[0] . ' dihapus.']);
    }

    public function downloadPdf(string $filename = '')
    {
        if (!$this->auth->check()) {
            session()->set('redirect_url', current_url());
            return redirect()->to('/login');
        }
        $filename = basename($filename);
        $q = $this->table()->select('id')->where(static::FILE_COL, $filename);
        if (!$this->userGroups['admin']) {
            $q->where('kodebpr', (string) $this->userKodebpr); // hanya dokumen milik BPR user
        }
        $path = $this->dir() . $filename;
        if ($filename === '' || !$q->get()->getRowArray() || !is_file($path)) {
            return $this->response->setStatusCode(404)->setBody('File tidak ditemukan.');
        }
        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . static::FILE_TEXT[2] . '"')
            ->setHeader('Content-Length', (string) filesize($path))
            ->setHeader('Cache-Control', 'private, max-age=600')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody(file_get_contents($path));
    }
}
