<?php

namespace App\Libraries;

/**
 * Basis halaman penilaian Profil Risiko (Inheren / KPMR) per jenis risiko.
 *
 * Struktur data (satu baris per faktor di tabel risiko, kunci: kodebpr + periode_id + faktor1id):
 *  - pilar "kategori" (punya children)  → peringkat = rata-rata children (otomatis), keterangan dapat diubah
 *  - pilar tunggal / children (leaf)     → diisi PE: peringkat 1–5 + keterangan (+ rasio dari kalkulator)
 *  - TOTAL_ID                            → peringkat = rata-rata pilar (otomatis, bisa manual) + kesimpulan
 *  - PREV_ID                             → peringkat periode sebelumnya
 *
 * Subclass cukup mendefinisikan konstanta + structure() + penilaianConfig().
 * Ditempatkan di Libraries agar tidak terjangkau auto-routing.
 */
abstract class ProfilRisikoController extends TksBaseController
{
    protected const PERIODE_URL = '/Periodeprofilresiko';
    public const LABELS = [1 => 'Sangat Rendah', 2 => 'Rendah', 3 => 'Sedang', 4 => 'Tinggi', 5 => 'Sangat Tinggi'];
    public const MAX_KOMENTAR = 2000;

    protected const TABLE = '';
    protected const COL_NILAI = 'penilaiankredit';
    /** Kolom rasio (null bila jenis risiko ini tidak memakai rasio). */
    protected const COL_RASIO = null;
    /** Subkategori komentar di profilrisiko_comments. */
    protected const SUBKATEGORI = '';
    protected const ROUTE = '';
    /** Contoh: 'Risiko Kredit Inheren' */
    protected const TITLE = '';
    /** Contoh: 'Risiko Kredit' (kicker & navigasi) */
    protected const RISK = '';
    protected const ICON = 'fa-chart-line';
    protected const TOTAL_ID = 13;
    protected const PREV_ID = 14;
    /** Faktor opsional (tidak wajib untuk persetujuan). */
    protected const OPTIONAL = [];
    /** Navigasi antar halaman jenis risiko ini: [route => label]. */
    protected const NAV = [];
    /** Partial view tambahan (mis. kalkulator) — null bila tidak ada. */
    protected const VIEW_EXTRA = null;

    protected $db;
    protected array $userGroups = ['pe' => false, 'admin' => false, 'dekom' => false, 'dekom2' => false,
        'direksi' => false, 'direksi2' => false, 'auditor' => false];

    /** @return array<int, array{id:int,title:string,children?:array<int,array{id:int,title:string}>}> */
    abstract protected function structure(): array;

    /** @return array<int, array{descriptions:array<int,string>, threshold?:string, catatan?:string}> */
    abstract protected function penilaianConfig(): array;

    public function __construct()
    {
        parent::__construct();
        $this->db = db_connect();
    }

    // ───────────────────────── struktur ─────────────────────────

    /** fid => ['title', 'parent' (fid|null), 'kind' => category|leaf] */
    protected function faktorMap(): array
    {
        static $cache = [];
        if (isset($cache[static::class])) {
            return $cache[static::class];
        }
        $map = [];
        foreach ($this->structure() as $p) {
            $hasChildren = !empty($p['children']);
            $map[(int) $p['id']] = ['title' => $p['title'], 'parent' => null, 'kind' => $hasChildren ? 'category' : 'leaf'];
            foreach ($p['children'] ?? [] as $c) {
                $map[(int) $c['id']] = ['title' => $c['title'], 'parent' => (int) $p['id'], 'kind' => 'leaf'];
            }
        }
        return $cache[static::class] = $map;
    }

    protected function pilarIds(): array
    {
        return array_map(static fn($p) => (int) $p['id'], $this->structure());
    }

    protected function leafIds(): array
    {
        return array_keys(array_filter($this->faktorMap(), static fn($f) => $f['kind'] === 'leaf'));
    }

    /** Faktor wajib dinilai sebelum persetujuan. */
    protected function requiredIds(): array
    {
        return array_values(array_diff($this->leafIds(), static::OPTIONAL));
    }

    protected function allIds(): array
    {
        return array_merge(array_keys($this->faktorMap()), [static::TOTAL_ID, static::PREV_ID]);
    }

    protected function label(int $v): string
    {
        return static::LABELS[$v] ?? '';
    }

    // ───────────────────────── data ─────────────────────────

    protected function table()
    {
        return $this->db->table(static::TABLE);
    }

    protected function scoped(array $fids = null)
    {
        $b = $this->table()->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId);
        if ($fids !== null) {
            $b->whereIn('faktor1id', $fids ?: [0]);
        }
        return $b;
    }

    /** fid => row (satu query; baris terakhir menang bila ada duplikat). */
    protected function rows(?int $periodeId = null): array
    {
        $b = $this->table()->where('kodebpr', $this->userKodebpr)->where('periode_id', $periodeId ?? $this->periodeId)
            ->whereIn('faktor1id', $this->allIds())->orderBy('id', 'ASC');
        $out = [];
        foreach ($b->get()->getResultArray() as $r) {
            $out[(int) $r['faktor1id']] = $r;
        }
        return $out;
    }

    protected function nilai(?array $row): int
    {
        $v = (int) ($row[static::COL_NILAI] ?? 0);
        return $v >= 1 && $v <= 5 ? $v : 0;
    }

    /** Nilai default baris baru (semua kolom NOT NULL terisi — aman untuk MySQL strict). */
    protected function newRow(int $fid): array
    {
        $now = date('Y-m-d H:i:s');
        return [
            'faktor1id' => $fid, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId,
            'user_id' => $this->userId, 'fullname' => $this->user['fullname'] ?? 'Unknown',
            'penjelasanpenilaian' => '', 'is_approved' => '0', 'approved_by' => 0, 'accdir2' => '0', 'accdir2_by' => 0,
            'is_manual' => 0, 'created_at' => $now, 'updated_at' => $now,
        ];
    }

    /** Insert atau update baris faktor (update mengenai juga baris duplikat lama). */
    protected function upsert(int $fid, array $data, array &$rows): void
    {
        $data['user_id'] = $this->userId;
        $data['fullname'] = $this->user['fullname'] ?? 'Unknown';
        $data['updated_at'] = date('Y-m-d H:i:s');
        if (isset($rows[$fid])) {
            $this->scoped([$fid])->update($data);
            $rows[$fid] = array_merge($rows[$fid], $data);
        } else {
            $row = array_merge($this->newRow($fid), $data);
            $this->table()->insert($row);
            $rows[$fid] = $row;
        }
    }

    protected static function roundHalfUp(float $v): int
    {
        return (int) floor($v + 0.5);
    }

    /** Hitung ulang peringkat pilar kategori dan TOTAL (kecuali TOTAL manual). Hanya menulis bila berubah. */
    protected function recalc(array &$rows, array $touched): void
    {
        $map = $this->faktorMap();
        $cfg = $this->penilaianConfig();
        $parents = [];
        foreach ($touched as $fid) {
            $p = $map[$fid]['parent'] ?? null;
            if ($p) {
                $parents[$p] = true;
            }
        }
        foreach (array_keys($parents) as $pid) {
            $vals = [];
            foreach ($this->structure() as $p) {
                if ((int) $p['id'] !== $pid) {
                    continue;
                }
                foreach ($p['children'] as $c) {
                    if ($v = $this->nilai($rows[(int) $c['id']] ?? null)) {
                        $vals[] = $v;
                    }
                }
            }
            if (!$vals) {
                continue;
            }
            $avg = self::roundHalfUp(array_sum($vals) / count($vals));
            $old = $rows[$pid] ?? null;
            $desc = $cfg[$pid]['descriptions'][$avg] ?? '';
            if ($old && $this->nilai($old) === $avg && (string) ($old['penjelasanpenilaian'] ?? '') === $desc) {
                continue;
            }
            // Keterangan pilar mengikuti deskripsi otomatis, kecuali sudah diubah manual oleh PE
            $oldKet = trim((string) ($old['keterangan'] ?? ''));
            $autoKet = $oldKet === '' || in_array($oldKet, array_map('strval', $cfg[$pid]['descriptions'] ?? []), true);
            $data = [static::COL_NILAI => $avg, 'penjelasanpenilaian' => $desc];
            if ($autoKet) {
                $data['keterangan'] = $desc;
            }
            $this->upsert($pid, $data, $rows);
        }
        $this->recalcTotal($rows);
    }

    protected function autoTotal(array $rows): int
    {
        $vals = [];
        foreach ($this->pilarIds() as $pid) {
            if ($v = $this->nilai($rows[$pid] ?? null)) {
                $vals[] = $v;
            }
        }
        return $vals ? self::roundHalfUp(array_sum($vals) / count($vals)) : 0;
    }

    protected function recalcTotal(array &$rows, bool $forceAuto = false): void
    {
        $tid = static::TOTAL_ID;
        $old = $rows[$tid] ?? null;
        if (!$forceAuto && $old && (int) ($old['is_manual'] ?? 0) === 1) {
            return;
        }
        $avg = $this->autoTotal($rows);
        if (!$avg || ($old && !$forceAuto && $this->nilai($old) === $avg)) {
            return;
        }
        $data = [static::COL_NILAI => $avg, 'is_manual' => 0, 'penjelasanpenilaian' => 'Tingkat ' . static::TITLE . ': ' . $this->label($avg)];
        if (!$old) {
            $data['keterangan'] = 'Hasil Penilaian Tingkat ' . static::TITLE;
        }
        $this->upsert($tid, $data, $rows);
    }

    /** Perubahan data membatalkan persetujuan Direktur Utama (seluruh halaman) + persetujuan baris terkait. */
    protected function resetApproval(array $fids): void
    {
        $this->scoped()->whereIn('faktor1id', $this->allIds())->where('is_approved', '1')->update(['is_approved' => '0']);
        $map = $this->faktorMap();
        $ids = [static::TOTAL_ID];
        foreach ($fids as $f) {
            $ids[] = $f;
            if ($p = $map[$f]['parent'] ?? null) {
                $ids[] = $p;
            }
        }
        $this->scoped(array_values(array_unique($ids)))->where('accdir2', '1')->update(['accdir2' => '0']);
    }

    protected function canAccRow(): bool
    {
        return $this->userGroups['admin'] || $this->userGroups['direksi'] || $this->userGroups['direksi2'];
    }

    protected function canComment(): bool
    {
        foreach (['admin', 'pe', 'dekom', 'dekom2', 'direksi', 'direksi2'] as $g) {
            if ($this->userGroups[$g]) {
                return true;
            }
        }
        return false;
    }

    protected function isApproved(array $rows): bool
    {
        foreach (array_merge($this->requiredIds(), [static::TOTAL_ID]) as $fid) {
            if ((string) ($rows[$fid]['is_approved'] ?? '') !== '1') {
                return false;
            }
        }
        return true;
    }

    protected function missing(array $rows): array
    {
        return array_values(array_filter($this->requiredIds(), fn($f) => !$this->nilai($rows[$f] ?? null)));
    }

    protected function text(string $key, int $max = self::MAX_TEXT): string
    {
        $v = str_replace("\r\n", "\n", (string) $this->request->getPost($key));
        return trim(mb_substr($v, 0, $max));
    }

    protected function postFid(): int
    {
        return (int) $this->request->getPost('faktor');
    }

    // ───────────────────────── halaman ─────────────────────────

    public function index()
    {
        if ($r = $this->guardPage()) {
            return $r;
        }
        $rows = $this->rows();
        $prev = ['id' => 0, 'count' => 0, 'total' => 0, 'label' => ''];
        if ($this->canEdit() && ($pid = $this->prevPeriodeId())) {
            $prevRows = $this->table()->select('faktor1id, ' . static::COL_NILAI . ' AS v')
                ->where(['kodebpr' => $this->userKodebpr, 'periode_id' => $pid])
                ->whereIn('faktor1id', array_merge(array_keys($this->faktorMap()), [static::TOTAL_ID]))
                ->get()->getResultArray();
            [, , $semB, $tahunB] = $this->semesters();
            $prev = ['id' => $pid, 'count' => 0, 'total' => 0, 'label' => 'Semester ' . $semB . ' Tahun ' . $tahunB];
            foreach ($prevRows as $r) {
                $v = (int) $r['v'];
                if ($v >= 1 && $v <= 5) {
                    (int) $r['faktor1id'] === static::TOTAL_ID ? $prev['total'] = $v : $prev['count']++;
                }
            }
        }

        $data = [
            'judul' => 'Penilaian ' . static::TITLE,
            'pr' => [
                'route' => static::ROUTE, 'title' => static::TITLE, 'risk' => static::RISK, 'icon' => static::ICON,
                'nav' => static::NAV, 'labels' => static::LABELS, 'total_id' => static::TOTAL_ID, 'prev_id' => static::PREV_ID,
                'col' => static::COL_NILAI, 'col_rasio' => static::COL_RASIO, 'optional' => static::OPTIONAL,
                'extra' => static::VIEW_EXTRA, 'max' => self::MAX_TEXT, 'max_komentar' => self::MAX_KOMENTAR,
            ],
            'structure' => $this->structure(),
            'config' => $this->penilaianConfig(),
            'rows' => $rows,
            'unread' => $this->unreadCounts(),
            'missing' => $this->missing($rows),
            'required' => $this->requiredIds(),
            'approved' => $this->isApproved($rows),
            'autoTotal' => $this->autoTotal($rows),
            'prev' => $prev,
            'bprData' => $this->infobprModel->select('namabpr')->where('kodebpr', $this->userKodebpr)->first(),
            'periodeDetail' => $this->periode,
            'canEdit' => $this->canEdit(),
            'canApprove' => $this->canApprove(),
            'canAccRow' => $this->canAccRow(),
            'canComment' => $this->canComment(),
        ];
        return $this->render('Laporansemester/Profilrisiko/_risiko', $data + $this->extraData($rows));
    }

    /** Data tambahan untuk VIEW_EXTRA (override di subclass). */
    protected function extraData(array $rows): array
    {
        return [];
    }

    // ───────────────────────── aksi JSON ─────────────────────────

    /** Simpan penilaian faktor leaf: peringkat + keterangan. */
    public function save()
    {
        if ($r = $this->guardPost()) {
            return $r;
        }
        $fid = $this->postFid();
        $map = $this->faktorMap();
        if (($map[$fid]['kind'] ?? '') !== 'leaf') {
            return $this->json(400, ['ok' => false, 'message' => 'Faktor tidak valid.']);
        }
        $v = (int) $this->request->getPost('nilai');
        $ket = $this->text('keterangan');
        $errors = [];
        if ($v < 1 || $v > 5) {
            $errors['nilai'] = 'Pilih tingkat risiko.';
        }
        if ($ket === '') {
            $errors['keterangan'] = 'Keterangan wajib diisi.';
        }
        if ($errors) {
            return $this->json(422, ['ok' => false, 'message' => 'Periksa kembali isian.', 'errors' => $errors]);
        }
        $rows = $this->rows();
        $desc = (string) ($this->penilaianConfig()[$fid]['descriptions'][$v] ?? '');
        $this->db->transStart();
        $this->upsert($fid, [static::COL_NILAI => $v, 'penjelasanpenilaian' => $desc, 'keterangan' => $ket], $rows);
        $this->recalc($rows, [$fid]);
        $this->resetApproval([$fid]);
        $this->db->transComplete();
        return $this->json(200, ['ok' => true, 'reload' => true, 'message' => 'Penilaian "' . $map[$fid]['title'] . '" tersimpan.']);
    }

    /** Ubah keterangan pilar kategori (peringkatnya otomatis). */
    public function saveKet()
    {
        if ($r = $this->guardPost()) {
            return $r;
        }
        $fid = $this->postFid();
        if (($this->faktorMap()[$fid]['kind'] ?? '') !== 'category') {
            return $this->json(400, ['ok' => false, 'message' => 'Faktor tidak valid.']);
        }
        $ket = $this->text('keterangan');
        if ($ket === '') {
            return $this->json(422, ['ok' => false, 'message' => 'Keterangan wajib diisi.', 'errors' => ['keterangan' => 'Keterangan wajib diisi.']]);
        }
        $rows = $this->rows();
        if (!$this->nilai($rows[$fid] ?? null)) {
            return $this->json(422, ['ok' => false, 'message' => 'Nilai parameter pilar ini terlebih dahulu.']);
        }
        $this->upsert($fid, ['keterangan' => $ket], $rows);
        $this->resetApproval([$fid]);
        return $this->json(200, ['ok' => true, 'reload' => true, 'message' => 'Keterangan pilar tersimpan.']);
    }

    /** Peringkat total: act=manual (nilai) | auto. */
    public function setTotal()
    {
        if ($r = $this->guardPost()) {
            return $r;
        }
        $rows = $this->rows();
        $tid = static::TOTAL_ID;
        if ($this->request->getPost('act') === 'auto') {
            if (!$this->autoTotal($rows)) {
                return $this->json(422, ['ok' => false, 'message' => 'Belum ada pilar yang dinilai.']);
            }
            $this->recalcTotal($rows, true);
            $msg = 'Peringkat dikembalikan ke perhitungan otomatis.';
        } else {
            $v = (int) $this->request->getPost('nilai');
            if ($v < 1 || $v > 5) {
                return $this->json(422, ['ok' => false, 'message' => 'Pilih peringkat yang valid.']);
            }
            $data = [static::COL_NILAI => $v, 'is_manual' => 1, 'penjelasanpenilaian' => 'Tingkat ' . static::TITLE . ': ' . $this->label($v)];
            if (!isset($rows[$tid])) {
                $data['keterangan'] = 'Hasil Penilaian Tingkat ' . static::TITLE;
            }
            $this->upsert($tid, $data, $rows);
            $msg = 'Peringkat diubah manual menjadi ' . $v . ' (' . $this->label($v) . ').';
        }
        $this->resetApproval([$tid]);
        return $this->json(200, ['ok' => true, 'reload' => true, 'message' => $msg]);
    }

    /** Kesimpulan (keterangan baris TOTAL). */
    public function saveKesimpulan()
    {
        if ($r = $this->guardPost()) {
            return $r;
        }
        $ket = $this->text('keterangan');
        if ($ket === '') {
            return $this->json(422, ['ok' => false, 'message' => 'Kesimpulan wajib diisi.', 'errors' => ['keterangan' => 'Kesimpulan wajib diisi.']]);
        }
        $rows = $this->rows();
        $tid = static::TOTAL_ID;
        $data = ['keterangan' => $ket];
        if (!isset($rows[$tid])) {
            if (!($v = $this->autoTotal($rows))) {
                return $this->json(422, ['ok' => false, 'message' => 'Nilai minimal satu pilar terlebih dahulu sebelum mengisi kesimpulan.']);
            }
            $data += [static::COL_NILAI => $v, 'penjelasanpenilaian' => 'Tingkat ' . static::TITLE . ': ' . $this->label($v)];
        }
        $this->upsert($tid, $data, $rows);
        $this->resetApproval([$tid]);
        return $this->json(200, ['ok' => true, 'reload' => true, 'message' => 'Kesimpulan tersimpan.']);
    }

    /** Peringkat periode sebelumnya. */
    public function savePrev()
    {
        if ($r = $this->guardPost()) {
            return $r;
        }
        $v = (int) $this->request->getPost('nilai');
        if ($v < 1 || $v > 5) {
            return $this->json(422, ['ok' => false, 'message' => 'Pilih peringkat yang valid.']);
        }
        $rows = $this->rows();
        $this->upsert(static::PREV_ID, $this->prevData($v), $rows);
        return $this->json(200, ['ok' => true, 'reload' => true, 'message' => 'Peringkat periode sebelumnya tersimpan.']);
    }

    protected function prevData(int $v): array
    {
        return [static::COL_NILAI => $v, 'penjelasanpenilaian' => 'Tingkat ' . static::TITLE . ': ' . $this->label($v),
            'keterangan' => 'Tingkat ' . static::TITLE . ' Posisi Sebelumnya'];
    }

    /** Persetujuan Direktur Utama untuk seluruh halaman: act=approve|cancel. */
    public function approve()
    {
        if ($r = $this->guardPost(false)) {
            return $r;
        }
        if (!$this->canApprove()) {
            return $this->json(403, ['ok' => false, 'message' => 'Hanya Direktur Utama atau Admin yang dapat memberi persetujuan.']);
        }
        $ids = array_values(array_diff($this->allIds(), [static::PREV_ID]));
        if ($this->request->getPost('act') === 'cancel') {
            $this->scoped($ids)->update(['is_approved' => '0', 'approved_by' => $this->userId, 'approved_at' => date('Y-m-d H:i:s')]);
            return $this->json(200, ['ok' => true, 'reload' => true, 'message' => 'Persetujuan dibatalkan.']);
        }
        $rows = $this->rows();
        if ($m = $this->missing($rows)) {
            return $this->json(422, ['ok' => false, 'message' => count($m) . ' parameter wajib belum dinilai.']);
        }
        if (!$this->nilai($rows[static::TOTAL_ID] ?? null)) {
            return $this->json(422, ['ok' => false, 'message' => 'Peringkat ' . static::TITLE . ' belum tersedia.']);
        }
        $this->scoped($ids)->update(['is_approved' => '1', 'approved_by' => $this->userId, 'approved_at' => date('Y-m-d H:i:s')]);
        return $this->json(200, ['ok' => true, 'reload' => true, 'message' => static::TITLE . ' disetujui.']);
    }

    /** Persetujuan per faktor (Direksi): toggle. */
    public function accRow()
    {
        if ($r = $this->guardPost(false)) {
            return $r;
        }
        if (!$this->canAccRow()) {
            return $this->json(403, ['ok' => false, 'message' => 'Anda tidak memiliki akses persetujuan faktor.']);
        }
        $fid = $this->postFid();
        $rows = $this->rows();
        if (!isset($this->faktorMap()[$fid]) || !$this->nilai($rows[$fid] ?? null)) {
            return $this->json(422, ['ok' => false, 'message' => 'Faktor belum dinilai.']);
        }
        $on = (string) ($rows[$fid]['accdir2'] ?? '') !== '1';
        $this->scoped([$fid])->update(['accdir2' => $on ? '1' : '0', 'accdir2_by' => $this->userId]);
        return $this->json(200, ['ok' => true, 'acc' => $on, 'message' => $on ? 'Faktor disetujui Direksi.' : 'Persetujuan faktor dibatalkan.']);
    }

    // ───────────────────────── komentar ─────────────────────────

    protected function unreadCounts(): array
    {
        $rows = $this->db->table('profilrisiko_comments c')
            ->select('c.faktor1id, COUNT(*) AS n')
            ->join('profilrisiko_reads r', 'r.comment_id = c.id AND r.user_id = ' . (int) $this->userId, 'left')
            ->where(['c.subkategori' => static::SUBKATEGORI, 'c.kodebpr' => $this->userKodebpr, 'c.periode_id' => $this->periodeId])
            ->where('c.user_id !=', $this->userId)->where('r.id IS NULL')
            ->groupBy('c.faktor1id')->get()->getResultArray();
        return array_column(array_map(static fn($r) => [(int) $r['faktor1id'], (int) $r['n']], $rows), 1, 0);
    }

    /** GET ?faktor= → daftar komentar + tandai sudah dibaca. */
    public function komentar()
    {
        if ($r = $this->guardPost(false, false)) {
            return $r;
        }
        $fid = (int) $this->request->getGet('faktor');
        if (!in_array($fid, $this->allIds(), true)) {
            return $this->json(400, ['ok' => false, 'message' => 'Faktor tidak valid.']);
        }
        $list = $this->db->table('profilrisiko_comments c')
            ->select('c.id, c.komentar, c.created_at, c.user_id, COALESCE(u.fullname, c.fullname) AS fullname')
            ->join('users u', 'u.id = c.user_id', 'left')
            ->where(['c.subkategori' => static::SUBKATEGORI, 'c.faktor1id' => $fid, 'c.kodebpr' => $this->userKodebpr, 'c.periode_id' => $this->periodeId])
            ->orderBy('c.created_at', 'ASC')->orderBy('c.id', 'ASC')->get()->getResultArray();

        $read = [];
        if ($others = array_column(array_filter($list, fn($c) => (int) $c['user_id'] !== $this->userId), 'id')) {
            $done = array_column($this->db->table('profilrisiko_reads')->select('comment_id')
                ->where('user_id', $this->userId)->whereIn('comment_id', $others)->get()->getResultArray(), 'comment_id');
            $now = date('Y-m-d H:i:s');
            foreach (array_diff($others, $done) as $cid) {
                $read[] = ['comment_id' => (int) $cid, 'user_id' => $this->userId, 'read_at' => $now, 'created_at' => $now, 'updated_at' => $now];
            }
            if ($read) {
                $this->db->table('profilrisiko_reads')->insertBatch($read);
            }
        }
        $out = array_map(fn($c) => [
            'text' => $c['komentar'], 'by' => $c['fullname'] ?: 'Pengguna', 'mine' => (int) $c['user_id'] === $this->userId,
            'at' => ($t = strtotime((string) $c['created_at'])) ? date('d/m/Y H:i', $t) : '',
        ], $list);
        return $this->json(200, ['ok' => true, 'list' => $out]);
    }

    public function addKomentar()
    {
        if ($r = $this->guardPost(false)) {
            return $r;
        }
        if (!$this->canComment()) {
            return $this->json(403, ['ok' => false, 'message' => 'Anda tidak memiliki akses menambah komentar.']);
        }
        $fid = $this->postFid();
        $txt = $this->text('komentar', self::MAX_KOMENTAR);
        if (!in_array($fid, $this->allIds(), true)) {
            return $this->json(400, ['ok' => false, 'message' => 'Faktor tidak valid.']);
        }
        if ($txt === '') {
            return $this->json(422, ['ok' => false, 'message' => 'Komentar tidak boleh kosong.']);
        }
        $this->db->table('profilrisiko_comments')->insert([
            'subkategori' => static::SUBKATEGORI, 'faktor1id' => $fid, 'komentar' => $txt, 'user_id' => $this->userId,
            'fullname' => $this->user['fullname'] ?? 'Unknown', 'kodebpr' => $this->userKodebpr,
            'periode_id' => $this->periodeId, 'created_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->json(200, ['ok' => true, 'message' => 'Komentar terkirim.']);
    }

    /** GET → jumlah komentar belum dibaca per faktor (untuk polling ringan). */
    public function unread()
    {
        if ($r = $this->guardPost(false, false)) {
            return $r;
        }
        return $this->json(200, ['ok' => true, 'counts' => (object) $this->unreadCounts()])
            ->setHeader('Cache-Control', 'no-store');
    }

    // ───────────────────────── sinkron periode sebelumnya ─────────────────────────

    /** Salin penilaian periode sebelumnya (milik BPR yang sama). force=1 menimpa faktor yang sudah dinilai. */
    public function syncPrev()
    {
        if ($r = $this->guardPost()) {
            return $r;
        }
        $pid = $this->prevPeriodeId();
        if (!$pid) {
            return $this->json(404, ['ok' => false, 'message' => 'Periode sebelumnya belum terdaftar.']);
        }
        $force = $this->request->getPost('force') === '1';
        $prevRows = $this->rows($pid);
        $rows = $this->rows();
        $copy = ['penjelasanpenilaian', 'keterangan', static::COL_NILAI];
        if (static::COL_RASIO) {
            $copy[] = static::COL_RASIO;
        }
        $synced = $skipped = [];
        $this->db->transStart();
        foreach ($this->faktorMap() as $fid => $f) {
            $src = $prevRows[$fid] ?? null;
            if (!$this->nilai($src) || ($f['kind'] === 'category')) {
                continue;
            }
            if (!$force && $this->nilai($rows[$fid] ?? null)) {
                $skipped[] = $fid;
                continue;
            }
            $data = [];
            foreach ($copy as $c) {
                $data[$c] = $src[$c] ?? null;
            }
            $data['penjelasanpenilaian'] = (string) ($data['penjelasanpenilaian'] ?? '');
            $data['keterangan'] = (string) ($data['keterangan'] ?? '');
            if (static::COL_RASIO && $data[static::COL_RASIO] === null && isset($rows[$fid][static::COL_RASIO])) {
                unset($data[static::COL_RASIO]);
            }
            $this->upsert($fid, $data, $rows);
            $synced[] = $fid;
        }
        // Peringkat periode sebelumnya = peringkat total periode sebelumnya
        $prevSet = false;
        if (($v = $this->nilai($prevRows[static::TOTAL_ID] ?? null)) && ($force || !$this->nilai($rows[static::PREV_ID] ?? null))) {
            $this->upsert(static::PREV_ID, $this->prevData($v), $rows);
            $prevSet = true;
        }
        $extra = $this->afterSync($pid, $force);
        if ($synced) {
            $this->recalc($rows, $synced);
            $this->resetApproval($synced);
        }
        $this->db->transComplete();

        if (!$synced && !$prevSet && !$extra) {
            return $this->json(200, ['ok' => false, 'message' => $skipped
                ? 'Semua faktor sudah dinilai. Centang "Timpa" untuk mengganti dengan data periode sebelumnya.'
                : 'Tidak ada data yang dapat disalin dari periode sebelumnya.']);
        }
        $msg = count($synced) . ' faktor disalin dari periode sebelumnya';
        if ($prevSet) {
            $msg .= ', peringkat periode sebelumnya diperbarui';
        }
        if ($extra) {
            $msg .= ', ' . $extra;
        }
        if ($skipped) {
            $msg .= '. ' . count($skipped) . ' faktor dilewati (sudah dinilai)';
        }
        return $this->json(200, ['ok' => true, 'reload' => true, 'message' => $msg . '.']);
    }

    /** Hook sinkron data tambahan (mis. kalkulator). Kembalikan deskripsi singkat bila ada yang disalin. */
    protected function afterSync(int $prevPeriodeId, bool $force): string
    {
        return '';
    }

    /** Untuk endpoint ekspor lama: tolak bila periode aktif bukan milik BPR user. */
    protected function denyForeignPeriode()
    {
        if (!$this->auth->check()) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }
        if (!$this->periodeId) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Periode aktif tidak valid']);
        }
        return null;
    }
}
