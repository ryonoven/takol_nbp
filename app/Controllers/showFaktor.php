<?php

namespace App\Controllers;

use App\Libraries\CoverDesign;
use App\Libraries\CoverPdf;
use App\Libraries\PdfNormalizer;
use App\Libraries\TksBaseController;
use App\Models\M_showfaktor;

/**
 * Ringkasan Self Assessment Tata Kelola (12 faktor GCG) — nilai/peringkat komposit, kesimpulan,
 * lembar persetujuan, dokumen pendukung, cover, dan tombol Generate Laporan (Pdfself::generateFullReport).
 *
 * Kolom & lokasi file tetap (dipakai Pdfself, Showsemesteran, Tkstatakelola):
 *  - showfaktor.nilaikomposit/peringkatkomposit disinkronkan saat halaman dibuka;
 *  - pdf1/pdf2 di writable/uploads/pdf_attachments/, pdf3 (lembar persetujuan bertanda tangan) di writable/uploads/.
 */
class ShowFaktor extends TksBaseController
{
    protected const PERIODE_URL = '/Periodesemesteran';

    /** no => [judul, halaman, id faktor ringkasan] — tabel nilaifaktor{no}, kolom faktor{no}id & nfaktor{no}. */
    public const FACTORS = [
        1 => ['Aspek Pemegang Saham', 'faktor', 12],
        2 => ['Pelaksanaan Tugas, Tanggung Jawab, dan Wewenang Direksi', 'faktor2', 29],
        3 => ['Pelaksanaan Tugas, Tanggung Jawab, dan Wewenang Dewan Komisaris', 'faktor3', 27],
        4 => ['Kelengkapan dan Pelaksanaan Tugas Komite', 'faktor4', 12],
        5 => ['Penanganan Benturan Kepentingan', 'faktor5', 6],
        6 => ['Penerapan Fungsi Kepatuhan', 'faktor6', 10],
        7 => ['Penerapan Fungsi Audit Intern', 'faktor7', 12],
        8 => ['Penerapan Fungsi Audit Ekstern', 'faktor8', 6],
        9 => ['Penerapan Manajemen Risiko dan Strategi Anti Fraud', 'faktor9', 18],
        10 => ['Batas Maksimum Pemberian Kredit', 'faktor10', 6],
        11 => ['Integritas Pelaporan dan Sistem Teknologi Informasi', 'faktor11', 13],
        12 => ['Rencana Bisnis BPR', 'faktor12', 8],
    ];

    public const PERINGKAT = [1 => 'Sangat Baik', 2 => 'Baik', 3 => 'Cukup', 4 => 'Kurang Baik', 5 => 'Buruk'];

    /** kolom => [label, maks karakter] */
    public const TEXTS = [
        'kesimpulan' => ['Kesimpulan', 2000],
        'positifstruktur' => ['Struktur', 650],
        'positifproses' => ['Proses', 650],
        'positifhasil' => ['Hasil', 650],
        'negatifstruktur' => ['Struktur', 650],
        'negatifproses' => ['Proses', 650],
        'negatifhasil' => ['Hasil', 650],
    ];

    /** kolom => [label, tipe input, maks] */
    public const TTD = [
        'dirut' => ['Nama Direktur Utama', 'text', 255],
        'komut' => ['Nama Komisaris Utama', 'text', 255],
        'tanggal' => ['Tanggal pelaporan', 'date', 10],
        'lokasi' => ['Lokasi', 'text', 255],
    ];

    /** slot => [kolom, subfolder writable/, judul, normalisasi PDF 1.4] */
    public const PDFS = [
        1 => ['pdf1_filename', 'uploads/pdf_attachments/', 'Laporan pokok pelaksanaan tugas anggota Direksi yang membawahkan fungsi kepatuhan', true],
        2 => ['pdf2_filename', 'uploads/pdf_attachments/', 'Laporan pelaksanaan dan pokok hasil audit intern', true],
        3 => ['pdf3_filename', 'uploads/', 'Lembar persetujuan yang telah ditandatangani', false],
    ];
    public const MAX_PDF_KB = 2048;

    protected M_showfaktor $showfaktorModel;

    public function __construct()
    {
        parent::__construct();
        $this->showfaktorModel = new M_showfaktor();
    }

    // ───────────────────────── data ─────────────────────────

    /** Nilai terbaru 12 faktor (1 query): [no => ['v' => int|null, 'komut' => bool, 'dirut' => bool]]. */
    protected function factorValues(): array
    {
        $sql = [];
        $bind = [];
        foreach (self::FACTORS as $no => [, , $fid]) {
            $s = $no === 1 ? '' : (string) $no;
            $sql[] = "(SELECT {$no} AS no, nfaktor{$s} AS v, accdekom, is_approved FROM nilaifaktor{$s}"
                . " WHERE faktor{$no}id = ? AND kodebpr = ? AND periode_id = ? ORDER BY created_at DESC, id DESC LIMIT 1)";
            array_push($bind, $fid, (string) $this->userKodebpr, (int) $this->periodeId);
        }
        $out = array_fill_keys(array_keys(self::FACTORS), ['v' => null, 'komut' => false, 'dirut' => false]);
        foreach (db_connect()->query(implode(' UNION ALL ', $sql), $bind)->getResultArray() as $r) {
            $out[(int) $r['no']] = [
                'v' => is_numeric($r['v']) ? (int) $r['v'] : null,
                'komut' => (int) $r['accdekom'] === 1,
                'dirut' => (int) $r['is_approved'] === 1,
            ];
        }
        return $out;
    }

    /**
     * Nilai komposit (rumus lama dipertahankan): faktor 4 hanya dihitung bila ≠ 0; pembagi 12 bila seluruh faktor
     * bernilai, selain itu 11 (faktor 4/komite tidak wajib); dibulatkan ke atas mulai ,5.
     */
    public static function composite(array $vals): int
    {
        $total = 0;
        $n = 0;
        foreach ($vals as $no => $f) {
            if ($f['v'] === null || ($no === 4 && $f['v'] === 0)) {
                continue;
            }
            $total += $f['v'];
            $n++;
        }
        if ($n === 0) {
            return 0;
        }
        $avg = $total / ($n < 12 ? 11 : 12);
        return (int) (($avg - floor($avg)) >= 0.5 ? ceil($avg) : floor($avg));
    }

    public static function peringkat(int $nilai): string
    {
        return self::PERINGKAT[$nilai] ?? 'Nilai komposit tidak valid';
    }

    protected function row(): ?array
    {
        return $this->showfaktorModel->where(['kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])
            ->orderBy('id', 'ASC')->first();
    }

    /** Pastikan baris showfaktor ada (kolom NOT NULL terisi — aman untuk MySQL strict). */
    protected function ensureRow(?array $row, array $set = []): int
    {
        if ($row) {
            if ($set) {
                $this->showfaktorModel->update($row['id'], $set);
            }
            return (int) $row['id'];
        }
        $this->showfaktorModel->insert($set + [
            'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId,
            'semester' => (string) ($this->periode['semester'] ?? ''), 'tahun' => (int) ($this->periode['tahun'] ?? date('Y')),
            'nilaikomposit' => '0', 'peringkatkomposit' => self::peringkat(0),
            'dirut' => '', 'komut' => '', 'lokasi' => '', 'cover' => '', 'pdf3_filename' => '',
        ]);
        return (int) $this->showfaktorModel->getInsertID();
    }

    private function pdfPath(int $slot, ?string $name): ?string
    {
        $name = basename((string) $name);
        if ($name === '' || !isset(self::PDFS[$slot])) {
            return null;
        }
        $p = WRITEPATH . self::PDFS[$slot][1] . $name;
        return is_file($p) ? $p : null;
    }

    // ───────────────────────── halaman ─────────────────────────

    public function index()
    {
        if ($r = $this->guardPage()) {
            return $r;
        }
        $vals = $this->factorValues();
        $nilai = self::composite($vals);
        $peringkat = self::peringkat($nilai);

        // Sinkron nilai komposit ke showfaktor (dibaca laporan lain) — tulis hanya bila berubah
        $row = $this->row();
        if (!$row || (string) $row['nilaikomposit'] !== (string) $nilai || $row['peringkatkomposit'] !== $peringkat) {
            $this->ensureRow($row, ['nilaikomposit' => (string) $nilai, 'peringkatkomposit' => $peringkat]);
            $row = $this->row();
        }

        $files = [];
        foreach (self::PDFS as $slot => [$col]) {
            $p = $this->pdfPath($slot, $row[$col] ?? null);
            $files[$slot] = $p ? ['size' => (int) filesize($p), 'time' => (int) filemtime($p)] : null;
        }

        $sem = (int) ($this->periode['semester'] ?? 0);
        return $this->render('Laporansemester/Tatakelola/showfaktor', [
            'judul' => 'Self Assessment Tata Kelola',
            'bprData' => $this->infobprModel->getBprByKode($this->userKodebpr) ?? [],
            'periodeLabel' => 'Semester ' . ($sem ?: esc((string) ($this->periode['semester'] ?? '-'))) . ' Tahun ' . esc((string) ($this->periode['tahun'] ?? '')),
            'vals' => $vals,
            'nilai' => $nilai,
            'peringkat' => $peringkat,
            'row' => $row ?? [],
            'files' => $files,
            'covers' => CoverDesign::choices(),
            'customCovers' => CoverDesign::customList((string) $this->userKodebpr),
            'coverOk' => $this->validCover((string) ($row['cover'] ?? '')),
            'canEdit' => $this->canEdit(),
        ]);
    }

    // ───────────────────────── aksi (JSON) ─────────────────────────

    private function text(string $key): string
    {
        return trim(str_replace("\r\n", "\n", (string) $this->request->getPost($key)));
    }

    public function saveText()
    {
        if ($r = $this->guardPost()) {
            return $r;
        }
        $set = $errors = [];
        foreach (self::TEXTS as $k => [$lbl, $max]) {
            $v = $this->text($k);
            if (mb_strlen($v) > $max) {
                $errors[$k] = "Maksimal {$max} karakter.";
            }
            $set[$k] = $v;
        }
        if ($errors) {
            return $this->json(422, ['ok' => false, 'message' => 'Periksa kembali isian.', 'errors' => $errors]);
        }
        $this->ensureRow($this->row(), $set);
        return $this->json(200, ['ok' => true, 'message' => 'Kesimpulan tersimpan.']);
    }

    public function saveTtd()
    {
        if ($r = $this->guardPost()) {
            return $r;
        }
        $set = $errors = [];
        foreach (self::TTD as $k => [$lbl, $type, $max]) {
            $v = $this->text($k);
            if ($type === 'date') {
                if ($v !== '' && !(preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && checkdate((int) substr($v, 5, 2), (int) substr($v, 8, 2), (int) substr($v, 0, 4)))) {
                    $errors[$k] = 'Tanggal tidak valid.';
                }
                $set[$k] = $v === '' ? null : $v;
                continue;
            }
            if (mb_strlen($v) > $max) {
                $errors[$k] = "Maksimal {$max} karakter.";
            }
            $set[$k] = $v;
        }
        if ($errors) {
            return $this->json(422, ['ok' => false, 'message' => 'Periksa kembali isian.', 'errors' => $errors]);
        }
        $this->ensureRow($this->row(), $set);
        return $this->json(200, ['ok' => true, 'reload' => true, 'message' => 'Lembar persetujuan tersimpan.']);
    }

    // ───────────────────────── cover laporan ─────────────────────────

    /** Nilai cover sah untuk BPR ini: template bawaan atau desain unggahan milik BPR sendiri. */
    private function validCover(string $cover): bool
    {
        if (isset(CoverDesign::templates()[$cover])) {
            return true;
        }
        $c = CoverDesign::parseCustom($cover);
        return $c && $c[0] === (string) $this->userKodebpr && is_file(CoverDesign::customDir($c[0]) . $c[1]);
    }

    public function saveCover()
    {
        if ($r = $this->guardPost()) {
            return $r;
        }
        $cover = (string) $this->request->getPost('cover');
        if (!$this->validCover($cover)) {
            return $this->json(422, ['ok' => false, 'message' => 'Cover tidak valid.']);
        }
        if (($c = CoverDesign::parseCustom($cover)) && $this->request->getPost('settings') !== null) {
            $s = CoverDesign::cleanSettings((array) $this->request->getPost('settings')
                + ['name' => CoverDesign::customSettings($c[0], $c[1])['name']]);
            file_put_contents(CoverDesign::customDir($c[0]) . $c[1] . '.json', json_encode($s));
        }
        $this->ensureRow($this->row(), ['cover' => $cover]);
        return $this->json(200, ['ok' => true, 'message' => 'Cover laporan disimpan.']);
    }

    /** Unggah desain cover sendiri (JPG/PNG) — disimpan per BPR dan langsung dipilih. */
    public function uploadCover()
    {
        if ($r = $this->guardPost()) {
            return $r;
        }
        $file = $this->request->getFile('img');
        if (!$file || !$file->isValid()) {
            return $this->json(422, ['ok' => false, 'message' => 'File belum dipilih atau gagal diunggah.']);
        }
        if ($file->getSize() > CoverDesign::MAX_UPLOAD_KB * 1024) {
            return $this->json(422, ['ok' => false, 'message' => 'Ukuran file maksimal ' . (CoverDesign::MAX_UPLOAD_KB / 1024) . ' MB.']);
        }
        if (count(CoverDesign::customList((string) $this->userKodebpr)) >= 12) {
            return $this->json(422, ['ok' => false, 'message' => 'Maksimal 12 desain sendiri. Hapus desain yang tidak dipakai terlebih dahulu.']);
        }
        $name = CoverDesign::storeUpload($file->getTempName(), (string) $this->userKodebpr);
        if ($name[0] === '!') {
            return $this->json(422, ['ok' => false, 'message' => substr($name, 1)]);
        }
        $label = mb_substr(trim(pathinfo($file->getClientName(), PATHINFO_FILENAME)), 0, 60) ?: 'Desain sendiri';
        $dir = CoverDesign::customDir((string) $this->userKodebpr);
        file_put_contents($dir . $name . '.json', json_encode(CoverDesign::cleanSettings(['name' => $label])));
        $this->ensureRow($this->row(), ['cover' => 'custom:' . $this->userKodebpr . '/' . $name]);
        return $this->json(200, ['ok' => true, 'reload' => true, 'message' => 'Desain diunggah dan dipilih. Atur posisi teks & logo bila perlu.']);
    }

    /** Hapus desain sendiri — ditolak bila masih dipakai pada periode mana pun milik BPR ini. */
    public function deleteCover()
    {
        if ($r = $this->guardPost()) {
            return $r;
        }
        $cover = (string) $this->request->getPost('cover');
        $c = CoverDesign::parseCustom($cover);
        if (!$c || $c[0] !== (string) $this->userKodebpr) {
            return $this->json(422, ['ok' => false, 'message' => 'Desain tidak valid.']);
        }
        $used = $this->showfaktorModel->select('periode_id')->where(['kodebpr' => $this->userKodebpr, 'cover' => $cover])->findAll();
        if ($used) {
            $sem = $this->periodeModel->select('semester, tahun')->whereIn('id', array_column($used, 'periode_id'))->findAll();
            $lbl = implode(', ', array_map(static fn($p) => 'Semester ' . $p['semester'] . ' ' . $p['tahun'], $sem)) ?: 'periode lain';
            return $this->json(422, ['ok' => false, 'message' => 'Desain masih dipakai pada laporan ' . $lbl . '. Pilih cover lain terlebih dahulu.']);
        }
        $base = CoverDesign::customDir($c[0]) . $c[1];
        foreach ([$base, $base . '.json', substr($base, 0, -4) . '_t.jpg'] as $p) {
            if (is_file($p)) {
                @unlink($p);
            }
        }
        return $this->json(200, ['ok' => true, 'reload' => true, 'message' => 'Desain dihapus.']);
    }

    /** Gambar desain sendiri milik BPR (thumbnail atau ukuran penuh). */
    public function coverImage($file = '', $size = 't')
    {
        if (!$this->auth->check() || !$this->userKodebpr) {
            return $this->response->setStatusCode(403);
        }
        $file = basename((string) $file);
        if (!preg_match('/^[a-f0-9]{16}\.jpg$/', $file)) {
            return $this->response->setStatusCode(404);
        }
        $p = CoverDesign::customDir((string) $this->userKodebpr) . ($size === 'full' ? $file : substr($file, 0, -4) . '_t.jpg');
        if (!is_file($p)) {
            return $this->response->setStatusCode(404);
        }
        return $this->response->setHeader('Content-Type', 'image/jpeg')->setHeader('Cache-Control', 'private, max-age=86400')
            ->setBody(file_get_contents($p));
    }

    /** Pratinjau cover (1 halaman PDF) dengan data BPR & periode aktif; pengaturan desain sendiri dapat dicoba sebelum disimpan. */
    public function coverPreview()
    {
        if ($r = $this->guardPage()) {
            return $r;
        }
        $cover = (string) ($this->request->getGet('cover') ?? ($this->row()['cover'] ?? ''));
        if (!$this->validCover($cover)) {
            $cover = 'tpl:korporat';
        }
        $settings = CoverDesign::parseCustom($cover) && is_array($this->request->getGet('settings')) ? $this->request->getGet('settings') : null;
        $bpr = $this->infobprModel->getBprByKode($this->userKodebpr) ?? [];
        $pdf = new CoverPdf();
        $pdf->SetTitle('Pratinjau Cover');
        $pdf->AddPage();
        $pdf->renderCover([
            'namabpr' => $bpr['namabpr'] ?? '', 'nomor' => $bpr['nomor'] ?? '', 'email' => $bpr['email'] ?? '', 'webbpr' => $bpr['webbpr'] ?? '',
            'semester' => $this->periode['semester'] ?? 1, 'tahun' => $this->periode['tahun'] ?? date('Y'),
            'logo' => !empty($bpr['logo']) ? FCPATH . 'asset/img/' . basename($bpr['logo']) : '',
            'cover' => $cover, 'cover_settings' => $settings,
        ]);
        return $this->response->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="Pratinjau-Cover.pdf"')
            ->setBody($pdf->Output('S'));
    }

    public function uploadPdf()
    {
        if ($r = $this->guardPost()) {
            return $r;
        }
        $slot = (int) $this->request->getPost('slot');
        if (!isset(self::PDFS[$slot])) {
            return $this->json(400, ['ok' => false, 'message' => 'Jenis dokumen tidak valid.']);
        }
        [$col, $sub, , $normalize] = self::PDFS[$slot];
        $file = $this->request->getFile('pdf');
        if (!$file || !$file->isValid()) {
            return $this->json(422, ['ok' => false, 'message' => 'File belum dipilih atau gagal diunggah.']);
        }
        if ($file->getSize() > self::MAX_PDF_KB * 1024) {
            return $this->json(422, ['ok' => false, 'message' => 'Ukuran file maksimal ' . (self::MAX_PDF_KB / 1024) . ' MB.']);
        }
        $fh = @fopen($file->getTempName(), 'rb');
        $magic = $fh ? fread($fh, 5) : '';
        if ($fh) {
            fclose($fh);
        }
        if (strtolower($file->getClientExtension()) !== 'pdf' || $magic !== '%PDF-') {
            return $this->json(422, ['ok' => false, 'message' => 'File harus berupa PDF.']);
        }

        $dir = WRITEPATH . $sub;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $name = $file->getRandomName();
        $file->move($dir, $name);
        if ($normalize) {
            $norm = (new PdfNormalizer())->normalizeToV14($dir . $name);
            if ($norm) {
                rename($norm, $dir . $name);
            }
        }

        $row = $this->row();
        $old = $this->pdfPath($slot, $row[$col] ?? null);
        $this->ensureRow($row, [$col => $name]);
        if ($old) {
            @unlink($old);
        }
        return $this->json(200, ['ok' => true, 'reload' => true, 'message' => 'Dokumen berhasil diunggah.']);
    }

    public function deletePdf()
    {
        if ($r = $this->guardPost()) {
            return $r;
        }
        $slot = (int) $this->request->getPost('slot');
        if (!isset(self::PDFS[$slot])) {
            return $this->json(400, ['ok' => false, 'message' => 'Jenis dokumen tidak valid.']);
        }
        $col = self::PDFS[$slot][0];
        $row = $this->row();
        if ($row && ($p = $this->pdfPath($slot, $row[$col] ?? null))) {
            @unlink($p);
        }
        if ($row) {
            $this->showfaktorModel->update($row['id'], [$col => $slot === 3 ? '' : null]);
        }
        return $this->json(200, ['ok' => true, 'reload' => true, 'message' => 'Dokumen dihapus.']);
    }

    /** Tampilkan dokumen milik BPR & periode aktif (slot 1–3). */
    public function downloadPdf($slot = 0)
    {
        if ($r = $this->guardPage()) {
            return $r;
        }
        $slot = (int) $slot;
        $row = isset(self::PDFS[$slot]) ? $this->row() : null;
        $p = $row ? $this->pdfPath($slot, $row[self::PDFS[$slot][0]] ?? null) : null;
        if (!$p) {
            return $this->response->setStatusCode(404)->setBody('Dokumen tidak ditemukan.');
        }
        return $this->response->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="Dokumen-' . $slot . '.pdf"')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody(file_get_contents($p));
    }

    // ───────────────────────── cetak & ekspor ─────────────────────────

    private function wrapByWordCount(string $text, int $maxWords): array
    {
        $words = preg_split('/\s+/', trim($text));
        $lines = [];
        foreach (array_chunk($words, $maxWords) as $chunk) {
            $lines[] = implode(' ', $chunk);
        }
        return $lines ?: [''];
    }

    public function cetakPengesahan()
    {
        if ($r = $this->guardPage()) {
            return $r;
        }
        $showfaktor = $this->row() ?? [];
        $bprData = $this->infobprModel->getBprByKode($this->userKodebpr);
        $periodeDetail = $this->periode;

        $namabpr = $bprData['namabpr'] ?? 'Nama BPR Tidak Ditemukan';
        $alamat = $bprData['alamat'] ?? '';
        $email = $bprData['email'] ?? '';
        $nomor = $bprData['nomor'] ?? '';
        $webbpr = $bprData['webbpr'] ?? '';
        $logo = $bprData['logo'] ?? '';

        $semester = $periodeDetail['semester'] ?? 'N/A';
        $semNum = strtolower($semester) === 'ganjil' ? '1' : (strtolower($semester) === 'genap' ? '2' : $semester);
        $tahun = $periodeDetail['tahun'] ?? 'N/A';

        $dirut = $showfaktor['dirut'] ?? '';
        $komut = $showfaktor['komut'] ?? '';
        $lokasi = $showfaktor['lokasi'] ?? '';
        $tanggalRaw = $showfaktor['tanggal'] ?? '';

        $bulanIndo = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli',
            8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];

        $tanggalFormatted = '';
        if (!empty($tanggalRaw) && $tanggalRaw !== '0000-00-00') {
            try {
                $dt = \DateTime::createFromFormat('Y/m/d', $tanggalRaw) ?: new \DateTime($tanggalRaw);
                $tanggalFormatted = $dt->format('d') . ' ' . $bulanIndo[(int) $dt->format('n')] . ' ' . $dt->format('Y');
            } catch (\Exception $e) {
                $tanggalFormatted = esc($tanggalRaw);
            }
        }

        $pdf = new \FPDF('P', 'mm', 'A4');
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();

        // ===== HEADER: logo kiri, info BPR kanan (rata kanan) =====
        $headerTop = $pdf->GetY();
        $logoSize = 18;
        $logoPath = $logo ? FCPATH . 'asset/img/' . $logo : null;
        if ($logoPath && file_exists($logoPath)) {
            $pdf->Image($logoPath, 15, $headerTop, $logoSize);
        }
        $logoBottom = $headerTop + $logoSize;

        $pdf->SetXY(15, $headerTop);
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->Cell(180, 6, $namabpr, 0, 2, 'R');

        $pdf->SetFont('Arial', '', 9);
        if ($alamat) {
            foreach ($this->wrapByWordCount($alamat, 9) as $alamatLine) {
                $pdf->SetX(15);
                $pdf->Cell(180, 4.5, $alamatLine, 0, 2, 'R');
            }
        }
        $kontak = array_filter([$webbpr, $email, $nomor]);
        if ($kontak) {
            $pdf->SetX(15);
            $pdf->Cell(180, 4.5, implode(' | ', $kontak), 0, 1, 'R');
        }

        $pdf->SetY(max($pdf->GetY(), $logoBottom) + 3);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(5);

        // ===== JUDUL =====
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->MultiCell(0, 6, "LEMBAR PERSETUJUAN DAN PENANDATANGANAN\nLAPORAN PELAKSANAAN SELF ASSESSMENT\nTATA KELOLA (GOOD CORPORATE GOVERNANCE)", 0, 'C');
        $pdf->Ln(8);

        // ===== PERNYATAAN =====
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 6, 'Dengan ini kami menyatakan bahwa:', 0, 1, 'C');
        $pdf->Ln(4);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->MultiCell(0, 6, "Laporan Pelaksanaan Self Assessment Tata Kelola\n{$namabpr} Semester {$semNum} Tahun {$tahun}", 0, 'C');
        $pdf->Ln(6);
        $pdf->SetFont('Arial', '', 10);
        $pdf->MultiCell(0, 5.5, 'Telah disusun mengacu pada ketentuan Peraturan Otoritas Jasa Keuangan mengenai Penerapan Tata Kelola bagi Bank Perekonomian Rakyat.', 0, 'J');
        $pdf->Ln(16);

        // ===== LOKASI, TANGGAL =====
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 6, trim($lokasi . ($tanggalFormatted ? ', ' . $tanggalFormatted : '')), 0, 1, 'C');
        $pdf->Ln(20);

        // ===== TANDA TANGAN =====
        $colWidth = 90;
        $pdf->SetFont('Arial', 'BU', 10);
        $pdf->Cell($colWidth, 6, $dirut, 0, 0, 'C');
        $pdf->Cell($colWidth, 6, $komut, 0, 1, 'C');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell($colWidth, 6, 'Direktur Utama', 0, 0, 'C');
        $pdf->Cell($colWidth, 6, 'Komisaris Utama', 0, 1, 'C');

        $filename = 'Lembar-Pengesahan-GCG-' . preg_replace('/[^A-Za-z0-9_-]/', '_', $namabpr) . '.pdf';
        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->setBody($pdf->Output('S'));
    }

    /** Ekspor TXT TKSBPRKS-1000 (dipakai Showsemesteran). Format keluaran tetap. */
    public function exporttxtshowfaktor()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }
        // Periode aktif bukan milik BPR user → tidak ada berkas
        if (!$this->periodeId || !$this->userKodebpr) {
            return service('response')->setContentType('text/plain')->setBody('');
        }

        $p = $this->periode;
        $tahun = $p['tahun'] ?? date('Y');
        $semester = $p['semester'] ?? '';
        $exportDate = (strtolower($semester) === 'ganjil' || $semester === '1') ? "{$tahun}-06-30" : "{$tahun}-12-31";
        $titleDate = str_replace('-', '', $exportDate);

        $showfaktor = $this->row() ?? [];
        $infobpr = $this->infobprModel->getDataByKodebpr($this->userKodebpr);
        $sandibpr = $infobpr[0]['sandibpr'] ?? '';
        $kodejenis = $infobpr[0]['kodejenis'] ?? '';

        // Sanitizer lama dipertahankan (daftar pengganti tidak sejajar: "%" → "'", "|" → "'")
        $sanitize = static function ($text) {
            $text = str_replace(["\r", "\n"], ' ', (string) $text);
            $text = str_replace(['"', '"', '%', '|'], ['"', '"', "'", "'", ' persen ', ' '], $text);
            $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
            return trim(preg_replace('/\s+/', ' ', $text));
        };

        $vals = $this->factorValues();
        $nf = [];
        foreach ($vals as $no => $f) {
            $nf[$no] = $f['v'] === null || ($no === 4 && $f['v'] === 0) ? '' : (string) $f['v'];
        }
        $nilaikomposit = self::composite($vals);

        $f1600 = trim($sanitize($showfaktor['positifstruktur'] ?? '') . ' ' . $sanitize($showfaktor['positifproses'] ?? '') . ' ' . $sanitize($showfaktor['positifhasil'] ?? ''));
        $f1700 = trim($sanitize($showfaktor['negatifstruktur'] ?? '') . ' ' . $sanitize($showfaktor['negatifproses'] ?? '') . ' ' . $sanitize($showfaktor['negatifhasil'] ?? ''));

        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|1000|{$p['modalinti']}|{$p['kategori']}||0\r\n";
        foreach ($nf as $no => $v) {
            $output .= sprintf("D01|F%02d00|%s\r\n", $no, $v);
        }
        $output .= "D01|F1300|{$nilaikomposit}\r\n";
        $output .= "D01|F1400|\r\n";
        $output .= 'D01|F1500|' . $sanitize($showfaktor['kesimpulan'] ?? '') . "\r\n";
        $output .= "D01|F1600|{$f1600}\r\n";
        $output .= "D01|F1700|{$f1700}\r\n";

        return service('response')
            ->setHeader('Content-Type', 'text/plain')
            ->setHeader('Content-Disposition', 'attachment; filename="TKSBPRKS-1000-' . $p['jenispelaporan'] . '-S-' . $titleDate . '-' . $sandibpr . '-01.txt"')
            ->setBody($output);
    }
}
