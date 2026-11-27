<?php

namespace App\Libraries;

use App\Models\M_semesterlist;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Basis halaman daftar laporan semesteran (banyak baris per periode).
 *
 *  - index         : daftar baris (dirender server, tanpa AJAX tambahan)
 *  - save          : tambah / ubah satu baris (POST JSON). Ubah = persetujuan baris dibatalkan.
 *  - delete        : hapus satu baris
 *  - approve       : persetujuan Direktur Utama — act=approve|cancel, id=<baris> atau id kosong = semua baris
 *  - nodata        : act=set|cancel — tandai periode "Tidak ada data"
 *  - sync          : salin baris periode sebelumnya (baris yang isinya sama dilewati)
 *  - importPreview : baca Excel → pratinjau + validasi;  importConfirm : simpan baris valid
 * Semua aksi tulis = POST JSON + CSRF, dibatasi ke BPR & periode milik user.
 */
abstract class SemesterListController extends TksBaseController
{
    protected const PERIODE_URL = '/Periodesemesteran';

    /** Key halaman (komentar & nama controller). */
    public const PAGE = '';
    public const JUDUL = '';
    /** Teks kecil di atas judul. */
    public const KICKER = 'Laporan Semesteran';
    /** Kembali ke halaman ringkasan. */
    public const BACK = ['Showsemesteran', 'Laporan Semesteran'];
    /** Navigasi antar halaman sekelompok: controller => label. */
    public const NAV = [];
    /** Nama file template impor (public/assets/template/...). Kosong = tanpa impor. */
    public const IMPORT_TEMPLATE = '';
    /** Nama method export TXT (kosong = tanpa tombol). */
    public const TXT_METHOD = '';

    // Batas baris impor: M_semesterlist::IMPORT_MAX_ROWS (per halaman)
    private const IMPORT_MAX_KB = 3072;

    protected M_semesterlist $model;

    abstract protected function newModel(): M_semesterlist;

    public function __construct()
    {
        parent::__construct();
        $this->model = $this->newModel();
    }

    protected function canEdit(): bool
    {
        return $this->userGroups['pe'] || $this->userGroups['admin'] || $this->userGroups['auditor'];
    }

    protected function canApprove(): bool
    {
        return $this->userGroups['direksi'] || $this->userGroups['direksi2'] || $this->userGroups['admin'];
    }

    private function kode(): string
    {
        return (string) $this->userKodebpr;
    }

    private function nullRow(array $rows): ?array
    {
        foreach ($rows as $r) {
            if ((int) ($r['is_null_record'] ?? 0) === 1) {
                return $r;
            }
        }
        return null;
    }

    /** Kolom tetap baris baru. */
    private function baseRow(): array
    {
        [$sem, $tahun] = $this->semesters();
        return [
            'user_id' => $this->userId,
            'kodebpr' => $this->kode(),
            'periode_id' => $this->periodeId,
            'tahun' => $tahun,
            'semester' => (string) $sem,
            'fullname' => (string) ($this->user['fullname'] ?? $this->user['username'] ?? ''),
        ] + $this->model::insertDefaults();
    }

    /** Baris periode sebelumnya yang belum ada di periode aktif. */
    private function prevCandidates(array $currentRows): array
    {
        $prevId = $this->prevPeriodeId();
        if (!$prevId) {
            return [];
        }
        $have = [];
        foreach ($currentRows as $r) {
            $have[$this->model::rowKey($r)] = true;
        }
        $out = [];
        foreach ($this->model->rows($this->kode(), $prevId) as $r) {
            if ((int) ($r['is_null_record'] ?? 0) === 1 || isset($have[$k = $this->model::rowKey($r)])) {
                continue;
            }
            $have[$k] = true;
            $out[] = $r;
        }
        return $out;
    }

    // ------------------------------------------------------------------ halaman

    public function index()
    {
        if ($r = $this->guardPage()) {
            return $r;
        }
        $all = $this->model->rows($this->kode(), (int) $this->periodeId);
        $nullRow = $this->nullRow($all);
        $rows = array_values(array_filter($all, static fn($r) => (int) ($r['is_null_record'] ?? 0) !== 1));
        [$sem, $tahun, $semB, $tahunB] = $this->semesters();
        $m = $this->model;

        return $this->render('Laporansemester/_semesterlist', [
            'judul' => static::JUDUL,
            'cfg' => [
                'page' => static::PAGE,
                'route' => strtolower(static::PAGE),
                'kicker' => static::KICKER,
                'back' => static::BACK,
                'nav' => static::NAV,
                'fields' => $m::FIELDS,
                'import' => static::IMPORT_TEMPLATE,
                'import_cols' => $m::IMPORT_COLS,
                'nodata' => $m::HAS_NODATA,
                'card' => $m::CARD,
                'row_approval' => $m::ROW_APPROVAL,
                'import_max' => $m::IMPORT_MAX_ROWS,
                'txt' => static::TXT_METHOD,
            ],
            'bprData' => $this->infobprModel->getBprByKode($this->kode()) ?? [],
            'periodeLabel' => 'Semester ' . ($sem === 1 ? 'I' : 'II') . ' ' . $tahun,
            'prevLabel' => 'Semester ' . ($semB === 1 ? 'I' : 'II') . ' ' . $tahunB,
            'rows' => $rows,
            'nullRow' => $nullRow,
            'prevCount' => $nullRow ? 0 : count($this->prevCandidates($all)),
            'canEdit' => $this->canEdit(),
            'canApprove' => $this->canApprove(),
        ]);
    }

    // ------------------------------------------------------------------ aksi (JSON)

    /** Kunci baris (DUP_KEY) yang sudah ada di periode aktif: key => true. */
    private function existingKeys(int $exceptId = 0): array
    {
        if (!$this->model::DUP_KEY) {
            return [];
        }
        $out = [];
        foreach ($this->model->rows($this->kode(), (int) $this->periodeId) as $r) {
            if ((int) ($r['is_null_record'] ?? 0) !== 1 && (int) $r['id'] !== $exceptId) {
                $out[$this->model::rowKey($r)] = true;
            }
        }
        return $out;
    }

    /** Error field bila data sama (DUP_KEY) dengan baris lain di periode aktif. */
    private function duplicateOf(array $data, int $exceptId): ?array
    {
        if (!$this->model::DUP_KEY || !isset($this->existingKeys($exceptId)[$this->model::rowKey($data)])) {
            return null;
        }
        $names = array_map(fn($k) => strtolower($this->model::FIELDS[$k][0] ?? $k), $this->model::DUP_KEY);
        return [$this->model::DUP_KEY[0] => 'Data dengan ' . implode(' dan ', $names) . ' yang sama sudah ada.'];
    }

    public function save()
    {
        if ($r = $this->guardPost()) {
            return $r;
        }
        $id = (int) $this->request->getPost('id');
        [$data, $errors] = $this->model::validateRow($this->request->getPost() ?? []);
        if (!$errors && ($dup = $this->duplicateOf($data, $id))) {
            $errors = $dup;
        }
        if ($errors) {
            return $this->json(422, ['ok' => false, 'message' => 'Periksa kembali isian yang ditandai.', 'errors' => $errors]);
        }
        try {
            if ($id) {
                $row = $this->model->findOwned($id, $this->kode(), (int) $this->periodeId);
                if (!$row || (int) ($row['is_null_record'] ?? 0) === 1) {
                    return $this->json(404, ['ok' => false, 'message' => 'Data tidak ditemukan.']);
                }
                $changed = false;
                foreach ($data as $k => $v) {
                    if (str_replace("\r\n", "\n", (string) ($row[$k] ?? '')) !== (string) $v) {
                        $changed = true;
                        break;
                    }
                }
                if (!$changed) {
                    return $this->json(200, ['ok' => true, 'message' => 'Tidak ada perubahan.']);
                }
                $this->model->update($id, $data + ['accdir2' => 0, 'accdir2_by' => 0, 'accdir2_at' => $this->model::accAtReset()]);
                $msg = (int) $row['accdir2'] === 1
                    ? ($this->model::ROW_APPROVAL ? 'Data diubah. Persetujuan baris ini dibatalkan.' : 'Data diubah. Persetujuan Direktur Utama perlu diulang.')
                    : 'Data berhasil diubah.';
            } else {
                if ($this->nullRow($this->model->rows($this->kode(), (int) $this->periodeId))) {
                    return $this->json(409, ['ok' => false, 'message' => 'Batalkan status "Tidak ada data" terlebih dahulu.']);
                }
                $this->model->insert($data + $this->baseRow());
                $msg = 'Data berhasil ditambahkan.';
            }
        } catch (\Throwable $e) {
            log_message('error', static::PAGE . '::save ' . $e->getMessage());
            return $this->json(500, ['ok' => false, 'message' => 'Data gagal disimpan.']);
        }
        return $this->json(200, ['ok' => true, 'reload' => true, 'message' => $msg]);
    }

    public function delete()
    {
        if ($r = $this->guardPost()) {
            return $r;
        }
        $id = (int) $this->request->getPost('id');
        $row = $id ? $this->model->findOwned($id, $this->kode(), (int) $this->periodeId) : null;
        if (!$row || (int) ($row['is_null_record'] ?? 0) === 1) {
            return $this->json(404, ['ok' => false, 'message' => 'Data tidak ditemukan.']);
        }
        try {
            $this->model->delete($id);
        } catch (\Throwable $e) {
            log_message('error', static::PAGE . '::delete ' . $e->getMessage());
            return $this->json(500, ['ok' => false, 'message' => 'Data gagal dihapus.']);
        }
        return $this->json(200, ['ok' => true, 'reload' => true, 'message' => 'Data berhasil dihapus.']);
    }

    public function approve()
    {
        if ($r = $this->guardPost(false)) {
            return $r;
        }
        if (!$this->canApprove()) {
            return $this->json(403, ['ok' => false, 'message' => 'Hanya Direktur Utama yang dapat memberikan persetujuan.']);
        }
        $act = (string) $this->request->getPost('act');
        if (!in_array($act, ['approve', 'cancel'], true)) {
            return $this->json(400, ['ok' => false, 'message' => 'Aksi tidak dikenal.']);
        }
        $id = (int) $this->request->getPost('id');
        if ($id && !$this->model::ROW_APPROVAL) {
            return $this->json(400, ['ok' => false, 'message' => 'Persetujuan halaman ini dilakukan untuk seluruh data sekaligus.']);
        }
        $q = $this->model->builder()->where(['kodebpr' => $this->kode(), 'periode_id' => $this->periodeId]);
        if ($id) {
            $q->where('id', $id);
        }
        // Hanya baris yang statusnya berubah (jumlah yang dilaporkan akurat)
        $q->where('accdir2', $act === 'approve' ? 0 : 1);
        $upd = $act === 'approve'
            ? ['accdir2' => 1, 'accdir2_by' => $this->userId, 'accdir2_at' => date('Y-m-d H:i:s')]
            : ['accdir2' => 0, 'accdir2_by' => 0, 'accdir2_at' => $this->model::accAtReset()];
        try {
            $q->update($upd);
            $n = db_connect()->affectedRows();
        } catch (\Throwable $e) {
            log_message('error', static::PAGE . '::approve ' . $e->getMessage());
            return $this->json(500, ['ok' => false, 'message' => 'Gagal memproses persetujuan.']);
        }
        if (!$n) {
            return $this->json(200, ['ok' => true, 'reload' => true, 'message' => 'Tidak ada data yang berubah.']);
        }
        $msg = $act === 'approve' ? ($id ? 'Baris disetujui Direktur Utama.'
            : ($this->model::ROW_APPROVAL ? "{$n} baris disetujui Direktur Utama." : 'Seluruh data disetujui Direktur Utama.'))
            : ($id ? 'Persetujuan baris dibatalkan.' : 'Seluruh persetujuan dibatalkan.');
        return $this->json(200, ['ok' => true, 'reload' => true, 'message' => $msg]);
    }

    public function nodata()
    {
        if ($r = $this->guardPost()) {
            return $r;
        }
        if (!$this->model::HAS_NODATA) {
            return $this->json(404, ['ok' => false, 'message' => 'Fitur "Tidak ada data" tidak tersedia untuk halaman ini.']);
        }
        $all = $this->model->rows($this->kode(), (int) $this->periodeId);
        $null = $this->nullRow($all);
        try {
            if ((string) $this->request->getPost('act') === 'set') {
                if ($null) {
                    return $this->json(200, ['ok' => true, 'reload' => true, 'message' => 'Periode sudah ditandai tidak ada data.']);
                }
                if (count($all)) {
                    return $this->json(409, ['ok' => false, 'message' => 'Masih ada data tersimpan. Hapus seluruh data terlebih dahulu.']);
                }
                $this->model->insert($this->model::nullValues((array) $this->periode) + ['is_null_record' => 1] + $this->baseRow());
                return $this->json(200, ['ok' => true, 'reload' => true, 'message' => 'Periode ditandai "Tidak ada data".']);
            }
            if ($null) {
                $this->model->builder()->where(['kodebpr' => $this->kode(), 'periode_id' => $this->periodeId, 'is_null_record' => 1])->delete();
            }
        } catch (\Throwable $e) {
            log_message('error', static::PAGE . '::nodata ' . $e->getMessage());
            return $this->json(500, ['ok' => false, 'message' => 'Gagal menyimpan status.']);
        }
        return $this->json(200, ['ok' => true, 'reload' => true, 'message' => 'Status "Tidak ada data" dibatalkan.']);
    }

    public function sync()
    {
        if ($r = $this->guardPost()) {
            return $r;
        }
        $all = $this->model->rows($this->kode(), (int) $this->periodeId);
        if ($this->nullRow($all)) {
            return $this->json(409, ['ok' => false, 'message' => 'Batalkan status "Tidak ada data" terlebih dahulu.']);
        }
        $cand = $this->prevCandidates($all);
        if (!$cand) {
            return $this->json(404, ['ok' => false, 'message' => 'Tidak ada data baru dari periode sebelumnya.']);
        }
        $base = $this->baseRow();
        $batch = [];
        foreach ($cand as $r) {
            $batch[] = array_intersect_key($r, $this->model::FIELDS) + $base;
        }
        return $this->insertBatch($batch, count($batch) . ' baris disalin dari periode sebelumnya.');
    }

    private function insertBatch(array $batch, string $msg)
    {
        $db = db_connect();
        try {
            $db->transStart();
            $this->model->insertBatch($batch);
            $db->transComplete();
            if ($db->transStatus() === false) {
                throw new \RuntimeException('transaksi gagal');
            }
        } catch (\Throwable $e) {
            log_message('error', static::PAGE . '::insertBatch ' . $e->getMessage());
            return $this->json(500, ['ok' => false, 'message' => 'Data gagal disimpan.']);
        }
        return $this->json(200, ['ok' => true, 'reload' => true, 'message' => $msg]);
    }

    // ------------------------------------------------------------------ impor Excel

    private static function cellDate($v): ?string
    {
        if ($v === null || $v === '') {
            return '';
        }
        if (is_numeric($v)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $v)->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }
        $v = trim((string) $v);
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d', 'd.m.Y'] as $fmt) {
            $d = \DateTime::createFromFormat('!' . $fmt, $v);
            if ($d && $d->format($fmt) === $v) {
                return $d->format('Y-m-d');
            }
        }
        return null;
    }

    /** Nilai pilihan dari sel Excel: kode ("01"), angka tanpa nol depan (1), atau teks label ("Selesai"). */
    private static function cellOption($v, array $options, array $aliases = []): string
    {
        $v = trim((string) $v);
        if ($v === '' || isset($options[$v])) {
            return $v;
        }
        $low = mb_strtolower(preg_replace('/\s+/', ' ', $v));
        if (isset($aliases[$low])) {
            return (string) $aliases[$low];
        }
        foreach ($options as $code => $label) {
            if ((is_numeric($v) && is_numeric($code) && (int) $v === (int) $code) || mb_strtolower($label) === mb_strtolower($v)) {
                return (string) $code;
            }
        }
        return $v; // tidak dikenal → ditolak validateRow
    }

    public function importPreview()
    {
        if ($r = $this->guardPost()) {
            return $r;
        }
        if (!static::IMPORT_TEMPLATE) {
            return $this->json(404, ['ok' => false, 'message' => 'Impor tidak tersedia.']);
        }
        $f = $this->request->getFile('file');
        if (!$f || !$f->isValid() || $f->hasMoved()) {
            return $this->json(422, ['ok' => false, 'message' => 'Pilih file Excel terlebih dahulu.']);
        }
        $ext = strtolower($f->getClientExtension());
        if (!in_array($ext, ['xlsx', 'xls'], true) || $f->getSizeByUnit('kb') > self::IMPORT_MAX_KB) {
            return $this->json(422, ['ok' => false, 'message' => 'File harus .xlsx / .xls dengan ukuran maksimal 3 MB.']);
        }
        try {
            $reader = IOFactory::createReader($ext === 'xlsx' ? 'Xlsx' : 'Xls');
            $reader->setReadDataOnly(true);
            $sheet = $reader->load($f->getTempName())->getActiveSheet();
            $last = $sheet->getHighestDataRow();
            if ($last - 1 > $this->model::IMPORT_MAX_ROWS) {
                return $this->json(422, ['ok' => false, 'message' => 'Maksimal ' . $this->model::IMPORT_MAX_ROWS . ' baris per impor (file berisi ' . ($last - 1) . ' baris).']);
            }
            $fields = $this->model::FIELDS;
            $out = [];
            $have = $this->existingKeys();
            for ($i = 2; $i <= $last; $i++) {
                $in = [];
                $bad = [];
                $empty = true;
                foreach ($this->model::IMPORT_COLS as $c => $name) {
                    $raw = $sheet->getCell([$c + 1, $i])->getValue();
                    if ($raw !== null && trim((string) $raw) !== '') {
                        $empty = false;
                    }
                    if (($fields[$name]['type'] ?? '') === 'select') {
                        $in[$name] = self::cellOption($raw, $fields[$name]['options'] ?? [], $fields[$name]['aliases'] ?? []);
                    } elseif (($fields[$name]['type'] ?? '') === 'date') {
                        $d = self::cellDate($raw);
                        if ($d === null) {
                            $bad[$name] = 'Format tanggal tidak dikenali.';
                        }
                        $in[$name] = (string) $d;
                    } else {
                        // Angka panjang (mis. NIK) dari sel numerik → tanpa notasi ilmiah / desimal
                        if ((is_float($raw) || is_int($raw)) && floor((float) $raw) == $raw) {
                            $raw = number_format((float) $raw, 0, '', '');
                        }
                        $in[$name] = trim((string) $raw);
                    }
                }
                if ($empty) {
                    continue;
                }
                [$data, $err] = $this->model::validateRow($in);
                $err = $bad + $err;
                if (!$err && $this->model::DUP_KEY) {
                    $k = $this->model::rowKey($data);
                    if (isset($have[$k])) {
                        $err[$this->model::DUP_KEY[0]] = $have[$k] === true ? 'Sudah ada di periode ini (dilewati).' : 'Duplikat dengan baris ' . $have[$k] . ' (dilewati).';
                    } else {
                        $have[$k] = $i;
                    }
                }
                $out[] = ['line' => $i, 'data' => $data, 'errors' => array_values(array_map(
                    static fn($k, $m) => ($fields[$k][0] ?? $k) . ': ' . $m, array_keys($err), $err))];
            }
        } catch (\Throwable $e) {
            log_message('error', static::PAGE . '::importPreview ' . $e->getMessage());
            return $this->json(422, ['ok' => false, 'message' => 'Gagal membaca file Excel. Pastikan format sesuai template.']);
        }
        if (!$out) {
            return $this->json(422, ['ok' => false, 'message' => 'Tidak ada baris data pada file.']);
        }
        $ok = count(array_filter($out, static fn($r) => !$r['errors']));
        return $this->json(200, ['ok' => true, 'rows' => $out, 'ok_count' => $ok, 'message' => "{$ok} dari " . count($out) . ' baris siap diimpor.']);
    }

    public function importConfirm()
    {
        if ($r = $this->guardPost()) {
            return $r;
        }
        if ($this->nullRow($this->model->rows($this->kode(), (int) $this->periodeId))) {
            return $this->json(409, ['ok' => false, 'message' => 'Batalkan status "Tidak ada data" terlebih dahulu.']);
        }
        $rows = json_decode((string) $this->request->getPost('rows'), true);
        if (!is_array($rows) || !$rows || count($rows) > $this->model::IMPORT_MAX_ROWS) {
            return $this->json(422, ['ok' => false, 'message' => 'Tidak ada data valid untuk disimpan.']);
        }
        $base = $this->baseRow();
        $batch = [];
        $have = $this->existingKeys();
        foreach ($rows as $in) {
            [$data, $err] = $this->model::validateRow(is_array($in) ? $in : []);
            if (!$err && $this->model::DUP_KEY) {
                $k = $this->model::rowKey($data);
                if (isset($have[$k])) {
                    continue; // duplikat dilewati
                }
                $have[$k] = true;
            }
            if (!$err) {
                $batch[] = $data + $base; // divalidasi ulang di server
            }
        }
        if (!$batch) {
            return $this->json(422, ['ok' => false, 'message' => 'Tidak ada baris valid untuk disimpan.']);
        }
        return $this->insertBatch($batch, count($batch) . ' baris berhasil diimpor dari Excel.');
    }

    // ------------------------------------------------------------------ TXT

    /**
     * Data untuk export TXT. Dipanggil juga oleh ZIP Laporan Semesteran (controller dibuat tanpa
     * initController → gunakan service('response'), bukan $this->response/$this->request).
     * @return array{0: array, 1: array, 2: array}|null [periode, infobpr, baris data]
     */
    protected function txtContext(): ?array
    {
        if (!$this->auth->check() || !$this->periodeId) {
            return null;
        }
        $info = $this->infobprModel->getDataByKodebpr($this->kode());
        $rows = array_values(array_filter($this->model->rows($this->kode(), (int) $this->periodeId),
            static fn($r) => (int) ($r['is_null_record'] ?? 0) !== 1));
        return [$this->periode, $info[0] ?? [], $rows];
    }

    protected static function txtClean(?string $text): string
    {
        $text = str_replace(["\r", "\n"], ' ', (string) $text);
        $text = str_replace(['|', '%'], [' ', ' persen '], $text);
        $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    /**
     * TXT TKSBPRKS-{kode}: header H01 + satu baris D01 per data (callback mengembalikan isi baris tanpa "D01|",
     * atau null untuk melewati baris). $empty (opsional): isi baris D01 bila tidak ada baris data sama sekali;
     * menerima tanggal laporan "Ymd".
     */
    protected function txtBuild(string $formCode, callable $line, ?callable $empty = null)
    {
        if (!($ctx = $this->txtContext())) {
            return redirect()->to('/login');
        }
        [$p, $info, $rows] = $ctx;
        $tahun = $p['tahun'] ?? date('Y');
        $sem = strtolower((string) ($p['semester'] ?? ''));
        $tgl = ($sem === 'ganjil' || $sem === '1') ? "{$tahun}-06-30" : "{$tahun}-12-31";
        $sandi = $info['sandibpr'] ?? '';

        $out = 'H01|' . ($info['kodejenis'] ?? '') . "|{$sandi}|{$tgl}|TKSBPRKS|{$formCode}|" . ($p['modalinti'] ?? '') . '|'
            . ($p['kategori'] ?? '') . '|' . ($p['nomorsurat'] ?? '') . '|' . ($p['jeniskoreksi'] ?? '') . "\r\n";
        $n = 0;
        foreach ($rows as $r) {
            if (($l = $line($r)) !== null) {
                $out .= 'D01|' . $l . "\r\n";
                $n++;
            }
        }
        if (!$n && $empty) {
            $out .= 'D01|' . $empty(str_replace('-', '', $tgl)) . "\r\n";
        }
        $file ="TKSBPRKS-{$formCode}-" . ($p['jenispelaporan'] ?? '') . '-S-' . str_replace('-', '', $tgl) . "-{$sandi}-01.txt";
        return $this->txtResponse($out, $file);
    }

    protected static function txtDate(?string $d): string
    {
        return !empty($d) ? str_replace('-', '', self::txtClean($d)) : '';
    }

    protected function txtResponse(string $body, string $filename)
    {
        return service('response')
            ->setHeader('Content-Type', 'text/plain')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($body);
    }
}
