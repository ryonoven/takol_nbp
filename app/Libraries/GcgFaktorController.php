<?php

namespace App\Libraries;

/**
 * Basis halaman faktor Self Assessment Tata Kelola (GCG). Skema database lama dipertahankan:
 *  - tabel nilai{T}: satu baris per parameter (kolom C_ID = id parameter, `nilai`, `keterangan`) + baris ringkasan
 *    (C_ID = SUMMARY_ID) berisi nilai faktor (C_N), penjelasan, dan 6 kolom kesimpulan;
 *  - persetujuan: Komisaris Utama (accdekom) lalu Direktur Utama (is_approved) untuk SELURUH faktor;
 *    paraf opsional per parameter oleh anggota Dewan Komisaris lain (accdekom2) & Direksi lain (accdir2);
 *  - setiap perubahan isi membatalkan persetujuan faktor — HANYA untuk BPR & periode aktif user.
 * Periode aktif wajib milik BPR user (TksBaseController).
 */
abstract class GcgFaktorController extends TksBaseController
{
    protected const PERIODE_URL = '/Periodesemesteran';

    protected const NO = 1;
    protected const TITLE = '';
    protected const ROUTE = 'faktor';
    protected const T_PARAM = 'faktor';
    protected const T_NILAI = 'nilaifaktor';
    protected const C_ID = 'faktor1id';
    protected const C_N = 'nfaktor';
    protected const T_KOM = 'faktor_comments';
    protected const T_READ = 'comment_reads';
    /** Tabel status baca memiliki kolom id parameter (C_ID)? */
    protected const READ_HAS_FID = true;
    protected const SUMMARY_ID = 12;
    /** Koreksi kode parameter yang salah di tabel referensi: [id => kode] (sesuai kode pelaporan TXT). */
    protected const CODES = [];
    /** Faktor boleh tidak diterapkan (seluruh parameter kosong) — nilai faktor 0, dikecualikan dari nilai komposit. */
    protected const OPTIONAL = false;
    protected const MAX_KET = 2000;
    protected const MAX_KOM = 1000;

    public const KESIMPULAN = [
        'positifstruktur' => ['Struktur', 'pos'], 'negatifstruktur' => ['Struktur', 'neg'],
        'positifproses' => ['Proses', 'pos'], 'negatifproses' => ['Proses', 'neg'],
        'positifhasil' => ['Hasil', 'pos'], 'negatifhasil' => ['Hasil', 'neg'],
    ];
    public const SKALA = [
        1 => 'Sangat Baik', 2 => 'Baik', 3 => 'Cukup Baik', 4 => 'Kurang Baik', 5 => 'Buruk',
    ];
    /** Uraian pilihan nilai (diikuti nama peringkat bercetak tebal). */
    public const SKALA_DESC = 'Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai ketentuan, proses pelaksanaan tata kelola dilakukan dengan memadai dan ditunjukkan dengan hasil pelaksanaan tata kelola yang';
    public const SPH = ['Struktur' => 'Struktur dan Infrastruktur Tata Kelola', 'Proses' => 'Proses Penerapan Tata Kelola', 'Hasil' => 'Hasil Penerapan Tata Kelola'];

    protected array $userGroups = ['pe' => false, 'admin' => false, 'dekom' => false, 'dekom2' => false, 'dekom3' => false, 'dekom4' => false,
        'dekom5' => false, 'direksi' => false, 'direksi2' => false, 'auditor' => false];

    /** Penjelasan nilai faktor (teks ketentuan per peringkat 1–5). */
    abstract protected function penjelasan(int $nilai): string;

    // ───────────────────────── data ─────────────────────────

    protected function tbl()
    {
        return db_connect()->table(static::T_NILAI);
    }

    protected function scope($b)
    {
        return $b->where('kodebpr', (string) $this->userKodebpr)->where('periode_id', (int) $this->periodeId);
    }

    /** Parameter penilaian: [id => [id, number, sph, sub_category]] (tanpa baris ringkasan). */
    protected function params(): array
    {
        $out = [];
        foreach (db_connect()->table(static::T_PARAM)->select('id, number, sph, sub_category')->where('id <', static::SUMMARY_ID)
            ->orderBy('id', 'ASC')->get()->getResultArray() as $p) {
            $p['number'] = static::CODES[(int) $p['id']] ?? trim(str_replace('\t', '', (string) $p['number']));
            $out[(int) $p['id']] = $p;
        }
        return $out;
    }

    /** Baris nilai BPR & periode aktif per id parameter (termasuk ringkasan). */
    protected function rows(?int $periodeId = null): array
    {
        $b = $this->tbl()->where('kodebpr', (string) $this->userKodebpr)->where('periode_id', $periodeId ?? (int) $this->periodeId)
            ->where(static::C_ID . ' >=', 1)->where(static::C_ID . ' <=', static::SUMMARY_ID)->orderBy('id', 'ASC');
        // Bila ada baris ganda untuk parameter yang sama (data lama), pakai baris yang berisi data; seri → yang terbaru
        $fill = function (array $r): int {
            $n = self::nv($r) > 0 ? 2 : 0;
            $n += (int) ($r[static::C_N] ?? 0) > 0 ? 1 : 0;
            foreach (array_keys(self::KESIMPULAN) as $k) {
                $n += trim((string) ($r[$k] ?? '')) !== '' ? 1 : 0;
            }
            return $n;
        };
        $out = [];
        foreach ($b->get()->getResultArray() as $r) {
            $fid = (int) $r[static::C_ID];
            if (!isset($out[$fid]) || $fill($r) >= $fill($out[$fid])) {
                $out[$fid] = $r;
            }
        }
        return $out;
    }

    protected static function nv($row): int
    {
        $v = (int) ($row['nilai'] ?? 0);
        return $v >= 1 && $v <= 5 ? $v : 0;
    }

    /** Nilai default untuk kolom NOT NULL tanpa default (aman untuk MySQL strict). */
    protected function defaults(): array
    {
        static $cache = [];
        $t = static::T_NILAI;
        if (!isset($cache[$t])) {
            $cache[$t] = [];
            foreach (db_connect()->getFieldData($t) as $f) {
                if ($f->primary_key || $f->nullable || $f->default !== null) {
                    continue;
                }
                $type = strtolower((string) $f->type);
                $cache[$t][$f->name] = preg_match('/int|dec|float|double|year/', $type) ? 0
                    : (preg_match('/date|time/', $type) ? date('Y-m-d H:i:s') : '');
            }
        }
        return $cache[$t];
    }

    protected function upsert(int $fid, array $data, array &$rows): void
    {
        $now = date('Y-m-d H:i:s');
        if (isset($rows[$fid])) {
            $this->tbl()->where('id', $rows[$fid]['id'])->update($data);
            $rows[$fid] = $data + $rows[$fid];
            return;
        }
        $row = $data + [
            static::C_ID => $fid, 'kodebpr' => (string) $this->userKodebpr, 'periode_id' => (int) $this->periodeId,
            'user_id' => $this->userId, 'fullname' => $this->user['fullname'] ?? '', 'created_at' => $now,
        ] + $this->defaults();
        $this->tbl()->insert($row);
        $row['id'] = db_connect()->insertID();
        $rows[$fid] = $row;
    }

    /** Batalkan persetujuan faktor (BPR & periode aktif saja); paraf pada parameter yang berubah ikut direset. */
    protected function resetApproval(array $changedFids = []): void
    {
        $this->scope($this->tbl())->update(['accdekom' => 0, 'is_approved' => 0]);
        if ($changedFids) {
            $this->scope($this->tbl())->whereIn(static::C_ID, $changedFids)->update(['accdekom2' => 0, 'accdir2' => 0]);
        }
    }

    /** Nilai faktor = rata-rata nilai parameter, dibulatkan (≥ ,5 ke atas). 0 bila belum ada nilai. */
    protected function average(array $rows): int
    {
        $v = array_filter(array_map([static::class, 'nv'], array_intersect_key($rows, $this->params())));
        if (!$v) {
            return 0;
        }
        $avg = array_sum($v) / count($v);
        return (int) (($avg - floor($avg)) >= 0.5 ? ceil($avg) : floor($avg));
    }

    /** Perbarui baris ringkasan (nilai faktor & penjelasan) — dibaca laporan Self Assessment. */
    protected function recalcSummary(array &$rows): void
    {
        $avg = $this->average($rows);
        if ($avg === 0) {
            return;
        }
        $this->upsert(static::SUMMARY_ID, [static::C_N => $avg, 'penjelasfaktor' => $this->penjelasan($avg),
            'keterangan' => 'Nilai rata-rata faktor 1-' . (static::SUMMARY_ID - 1)], $rows);
    }

    /** Status: jumlah parameter dinilai, kelengkapan kesimpulan, persetujuan Komut & Dirut. */
    protected function status(array $rows, array $params): array
    {
        $rated = count(array_filter(array_intersect_key($rows, $params), static fn($r) => self::nv($r) > 0));
        $sum = $rows[static::SUMMARY_ID] ?? [];
        $kesOk = $sum && !array_filter(array_keys(self::KESIMPULAN), static fn($k) => trim((string) ($sum[$k] ?? '')) === '');
        // Faktor opsional (mis. Faktor 4 Komite): bila tidak ada parameter dinilai → "tidak diterapkan", tetap dapat disetujui
        $na = static::OPTIONAL && $rated === 0;
        $complete = ($rated === count($params) && $kesOk) || $na;
        $all = array_intersect_key($rows, $params + [static::SUMMARY_ID => 1]);
        $komut = $complete && ($na ? isset($rows[static::SUMMARY_ID]) : count($all) === count($params) + 1)
            && !array_filter($all, static fn($r) => (int) $r['accdekom'] !== 1);
        $dirut = $komut && !array_filter($all, static fn($r) => (int) $r['is_approved'] !== 1);
        $at = static function (array $all, string $col) {
            $m = '';
            foreach ($all as $r) {
                $v = (string) ($r[$col] ?? '');
                if ($v > $m && !str_starts_with($v, '0000')) {
                    $m = $v;
                }
            }
            return $m;
        };
        // Pemberi persetujuan (dari baris ringkasan) — untuk badge "Disetujui oleh …"
        $komutBy = $komut ? (int) ($sum['accdekom_by'] ?? 0) : 0;
        $dirutBy = $dirut ? (int) ($sum['approved_by'] ?? 0) : 0;
        $names = ($komutBy || $dirutBy) ? array_column(db_connect()->table('users')->select('id, fullname')
            ->whereIn('id', array_filter([$komutBy, $dirutBy]))->get()->getResultArray(), 'fullname', 'id') : [];
        return ['rated' => $rated, 'total' => count($params), 'kesimpulan' => $kesOk, 'complete' => $complete, 'na' => $na,
            'komut' => $komut, 'komut_at' => $komut ? $at($all, 'accdekom_at') : '', 'komut_by' => $komutBy, 'komut_name' => $names[$komutBy] ?? '',
            'dirut' => $dirut, 'dirut_at' => $dirut ? $at($all, 'approved_at') : '', 'dirut_by' => $dirutBy, 'dirut_name' => $names[$dirutBy] ?? ''];
    }

    protected function can(string ...$groups): bool
    {
        foreach ($groups as $g) {
            if (!empty($this->userGroups[$g])) {
                return true;
            }
        }
        return false;
    }

    protected function canComment(): bool
    {
        return $this->can('pe', 'admin', 'dekom', 'dekom2', 'dekom3', 'dekom4', 'dekom5', 'direksi', 'direksi2');
    }

    private function text(string $k, int $max): string
    {
        return mb_substr(trim(str_replace("\r\n", "\n", (string) $this->request->getPost($k))), 0, $max + 1);
    }

    // ───────────────────────── halaman ─────────────────────────

    public function index()
    {
        if ($r = $this->guardPage()) {
            return $r;
        }
        $params = $this->params();
        $rows = $this->rows();
        $avg = $this->average($rows);
        $sem = (int) ($this->periode['semester'] ?? 0);
        [, , $ps, $pt] = $this->semesters();
        return $this->render('Laporansemester/Tatakelola/_gcgfaktor', [
            'judul' => 'Faktor ' . static::NO . ' · ' . static::TITLE,
            'cfg' => ['no' => static::NO, 'title' => static::TITLE, 'route' => static::ROUTE, 'summary' => static::SUMMARY_ID, 'max_ket' => static::MAX_KET, 'max_kom' => static::MAX_KOM, 'optional' => static::OPTIONAL],
            'bprData' => $this->infobprModel->getBprByKode($this->userKodebpr) ?? [],
            'periodeLabel' => 'Semester ' . ($sem ?: '-') . ' Tahun ' . esc((string) ($this->periode['tahun'] ?? '')),
            'prevLabel' => "Semester {$ps} Tahun {$pt}",
            'params' => $params,
            'rows' => $rows,
            'avg' => $avg,
            'penjelasan' => $avg ? $this->penjelasan($avg) : '',
            'st' => $this->status($rows, $params),
            'userId' => (int) $this->userId,
            'can' => [
                'admin' => $this->can('admin'),
                'edit' => $this->canEdit(),
                'komut' => $this->can('dekom', 'admin'),
                'dirut' => $this->can('direksi', 'admin'),
                'dekom2' => $this->can('dekom2', 'admin'),
                'dir2' => $this->can('direksi2', 'admin'),
                'comment' => $this->canComment(),
            ],
        ]);
    }

    // ───────────────────────── aksi (JSON) ─────────────────────────

    /** Simpan nilai & keterangan satu parameter (PE/Admin). */
    public function save()
    {
        if ($r = $this->guardPost()) {
            return $r;
        }
        $fid = (int) $this->request->getPost('faktor');
        if (!isset($this->params()[$fid])) {
            return $this->json(400, ['ok' => false, 'message' => 'Parameter tidak valid.']);
        }
        $nilai = (int) $this->request->getPost('nilai');
        $ket = $this->text('keterangan', static::MAX_KET);
        $err = [];
        if ($nilai < 1 || $nilai > 5) {
            $err['nilai'] = 'Pilih nilai 1–5.';
        }
        if ($ket === '') {
            $err['keterangan'] = 'Keterangan wajib diisi.';
        } elseif (mb_strlen($ket) > static::MAX_KET) {
            $err['keterangan'] = 'Maksimal ' . static::MAX_KET . ' karakter.';
        }
        if ($err) {
            return $this->json(422, ['ok' => false, 'message' => 'Periksa kembali isian.', 'errors' => $err]);
        }
        $rows = $this->rows();
        $old = $rows[$fid] ?? null;
        if ($old && self::nv($old) === $nilai && trim((string) $old['keterangan']) === $ket) {
            return $this->json(200, ['ok' => true, 'message' => 'Tidak ada perubahan.']);
        }
        $db = db_connect();
        $db->transStart();
        $this->upsert($fid, ['nilai' => $nilai, 'keterangan' => $ket, 'user_id' => $this->userId, 'fullname' => $this->user['fullname'] ?? ''], $rows);
        $this->recalcSummary($rows);
        $this->resetApproval([$fid]);
        $db->transComplete();
        return $this->json(200, ['ok' => true, 'reload' => true, 'message' => 'Penilaian parameter ' . ($this->params()[$fid]['number'] ?? '') . ' tersimpan.']);
    }

    /** Simpan kesimpulan (6 isian) pada baris ringkasan (PE/Admin). */
    public function saveKesimpulan()
    {
        if ($r = $this->guardPost()) {
            return $r;
        }
        $set = $err = [];
        foreach (self::KESIMPULAN as $k => $_) {
            $v = $this->text($k, static::MAX_KET);
            if (mb_strlen($v) > static::MAX_KET) {
                $err[$k] = 'Maksimal ' . static::MAX_KET . ' karakter.';
            }
            $set[$k] = $v;
        }
        if ($err) {
            return $this->json(422, ['ok' => false, 'message' => 'Periksa kembali isian.', 'errors' => $err]);
        }
        $rows = $this->rows();
        $sum = $rows[static::SUMMARY_ID] ?? [];
        if ($sum && !array_filter(array_keys($set), static fn($k) => trim((string) ($sum[$k] ?? '')) !== $set[$k])) {
            return $this->json(200, ['ok' => true, 'message' => 'Tidak ada perubahan.']);
        }
        $db = db_connect();
        $db->transStart();
        $extra = $sum ? [] : ['keterangan' => 'Nilai rata-rata faktor 1-' . (static::SUMMARY_ID - 1)] + (($a = $this->average($rows)) ? [static::C_N => $a, 'penjelasfaktor' => $this->penjelasan($a)] : []);
        $this->upsert(static::SUMMARY_ID, $set + $extra, $rows);
        $this->resetApproval();
        $db->transComplete();
        return $this->json(200, ['ok' => true, 'reload' => true, 'message' => 'Kesimpulan tersimpan.']);
    }

    /** Persetujuan faktor: who=komut|dirut, op=approve|cancel. */
    public function approve()
    {
        if ($r = $this->guardPost(false)) {
            return $r;
        }
        $who = (string) $this->request->getPost('who');
        $op = (string) $this->request->getPost('op');
        if (!in_array($op, ['approve', 'cancel'], true) || !in_array($who, ['komut', 'dirut'], true)) {
            return $this->json(400, ['ok' => false, 'message' => 'Aksi tidak valid.']);
        }
        if ($who === 'komut' ? !$this->can('dekom', 'admin') : !$this->can('direksi', 'admin')) {
            return $this->json(403, ['ok' => false, 'message' => 'Hanya ' . ($who === 'komut' ? 'Komisaris Utama' : 'Direktur Utama') . ' atau Admin yang dapat melakukan ini.']);
        }
        $params = $this->params();
        $rows = $this->rows();
        $st = $this->status($rows, $params);
        $now = date('Y-m-d H:i:s');
        $scope = fn() => $this->scope($this->tbl())->where(static::C_ID . ' >=', 1)->where(static::C_ID . ' <=', static::SUMMARY_ID);

        if ($op === 'approve') {
            if (!$st['complete']) {
                return $this->json(422, ['ok' => false, 'message' => 'Belum dapat disetujui: lengkapi nilai seluruh parameter (' . $st['rated'] . '/' . $st['total'] . ') dan kesimpulan'
                    . (static::OPTIONAL ? ', atau kosongkan seluruh parameter bila faktor ini tidak diterapkan' : '') . '.']);
            }
            if ($st['na'] && !isset($rows[static::SUMMARY_ID])) {
                // faktor tidak diterapkan: buat baris ringkasan (tanpa nilai faktor) agar persetujuan tercatat
                $this->upsert(static::SUMMARY_ID, ['keterangan' => 'Faktor tidak diterapkan'], $rows);
            }
            if ($who === 'dirut' && !$st['komut']) {
                return $this->json(422, ['ok' => false, 'message' => 'Menunggu persetujuan Komisaris Utama terlebih dahulu.']);
            }
            $set = $who === 'komut'
                ? ['accdekom' => 1, 'accdekom_by' => $this->userId, 'accdekom_at' => $now]
                : ['is_approved' => 1, 'approved_by' => $this->userId, 'approved_at' => $now];
            $scope()->update($set);
            return $this->json(200, ['ok' => true, 'reload' => true, 'message' => 'Faktor ' . static::NO . ' disetujui ' . ($who === 'komut' ? 'Komisaris Utama' : 'Direktur Utama') . '.']);
        }
        // Pembatalan hanya oleh pemberi persetujuan sendiri atau Admin (sama seperti Laporan Transparansi)
        if (!$this->can('admin') && $st[$who] && $st[$who . '_by'] !== (int) $this->userId) {
            return $this->json(403, ['ok' => false, 'message' => 'Persetujuan hanya dapat dibatalkan oleh pemberi persetujuan atau Admin.']);
        }
        $scope()->update($who === 'komut' ? ['accdekom' => 0, 'is_approved' => 0] : ['is_approved' => 0]);
        return $this->json(200, ['ok' => true, 'reload' => true, 'message' => 'Persetujuan ' . ($who === 'komut' ? 'Komisaris Utama (dan Direktur Utama)' : 'Direktur Utama') . ' dibatalkan.']);
    }

    /**
     * Faktor opsional: tandai "tidak diterapkan" — hapus penilaian parameter BPR & periode aktif, nilai faktor 0,
     * persetujuan direset. Kesimpulan & komentar tidak dihapus.
     */
    public function notApplicable()
    {
        if ($r = $this->guardPost()) {
            return $r;
        }
        if (!static::OPTIONAL) {
            return $this->json(400, ['ok' => false, 'message' => 'Faktor ini wajib dinilai.']);
        }
        $rows = $this->rows();
        $db = db_connect();
        $db->transStart();
        $this->scope($this->tbl())->where(static::C_ID . ' >=', 1)->where(static::C_ID . ' <', static::SUMMARY_ID)->delete();
        $this->upsert(static::SUMMARY_ID, [static::C_N => 0, 'penjelasfaktor' => '', 'keterangan' => 'Faktor tidak diterapkan'], $rows);
        $this->resetApproval();
        $db->transComplete();
        return $this->json(200, ['ok' => true, 'reload' => true, 'message' => 'Faktor ' . static::NO . ' ditandai tidak diterapkan. Persetujuan perlu diberikan ulang.']);
    }

    /** Paraf per parameter: kind=dekom2 (anggota Dewan Komisaris) | dir2 (anggota Direksi). Toggle. */
    public function paraf()
    {
        if ($r = $this->guardPost(false)) {
            return $r;
        }
        $kind = (string) $this->request->getPost('kind');
        $map = ['dekom2' => ['accdekom2', 'accdekom2_by', 'dekom2'], 'dir2' => ['accdir2', 'accdir2_by', 'direksi2']];
        if (!isset($map[$kind])) {
            return $this->json(400, ['ok' => false, 'message' => 'Aksi tidak valid.']);
        }
        [$col, $by, $grp] = $map[$kind];
        if (!$this->can($grp, 'admin')) {
            return $this->json(403, ['ok' => false, 'message' => 'Anda tidak memiliki akses paraf ini.']);
        }
        $fid = (int) $this->request->getPost('faktor');
        $rows = $this->rows();
        if (!isset($this->params()[$fid]) || !isset($rows[$fid]) || self::nv($rows[$fid]) === 0) {
            return $this->json(422, ['ok' => false, 'message' => 'Parameter belum dinilai.']);
        }
        $on = (int) $rows[$fid][$col] !== 1;
        $this->tbl()->where('id', $rows[$fid]['id'])->update([$col => $on ? 1 : 0, $by => $this->userId]);
        return $this->json(200, ['ok' => true, 'on' => $on, 'message' => $on ? 'Paraf ditambahkan.' : 'Paraf dibatalkan.']);
    }

    // ───────────────────────── komentar ─────────────────────────

    private function validFidKom(int $fid): bool
    {
        return $fid === static::SUMMARY_ID || isset($this->params()[$fid]);
    }

    public function komentar()
    {
        if ($r = $this->guardPost(false, false)) {
            return $r;
        }
        $fid = (int) $this->request->getGet('faktor');
        if (!$this->validFidKom($fid)) {
            return $this->json(400, ['ok' => false, 'message' => 'Parameter tidak valid.']);
        }
        $db = db_connect();
        $list = $db->table(static::T_KOM . ' c')->select('c.id, c.user_id, c.komentar, c.created_at, COALESCE(NULLIF(u.fullname, \'\'), c.fullname) AS fullname')
            ->join('users u', 'u.id = c.user_id', 'left')
            ->where(['c.kodebpr' => (string) $this->userKodebpr, 'c.periode_id' => (int) $this->periodeId, 'c.' . static::C_ID => $fid])
            ->orderBy('c.created_at', 'ASC')->orderBy('c.id', 'ASC')->get()->getResultArray();
        // tandai dibaca
        $unread = array_column(array_filter($list, fn($c) => (int) $c['user_id'] !== $this->userId), 'id');
        if ($unread) {
            $have = array_column($db->table(static::T_READ)->select('comment_id')->where('user_id', $this->userId)->whereIn('comment_id', $unread)->get()->getResultArray(), 'comment_id');
            $now = date('Y-m-d H:i:s');
            $ins = [];
            foreach (array_diff($unread, $have) as $cid) {
                $ins[] = ['comment_id' => $cid, 'user_id' => $this->userId, 'is_read' => 1, 'read_at' => $now, 'created_at' => $now, 'updated_at' => $now]
                    + (static::READ_HAS_FID ? [static::C_ID => $fid] : []);
            }
            if ($ins) {
                $db->table(static::T_READ)->insertBatch($ins);
            }
        }
        return $this->json(200, ['ok' => true, 'items' => array_map(fn($c) => [
            'name' => $c['fullname'] ?: 'Pengguna', 'text' => $c['komentar'], 'at' => $c['created_at'], 'mine' => (int) $c['user_id'] === $this->userId,
        ], $list)]);
    }

    public function addKomentar()
    {
        if ($r = $this->guardPost(false)) {
            return $r;
        }
        if (!$this->canComment()) {
            return $this->json(403, ['ok' => false, 'message' => 'Anda tidak memiliki akses menambah komentar.']);
        }
        $fid = (int) $this->request->getPost('faktor');
        $txt = $this->text('komentar', static::MAX_KOM);
        if (!$this->validFidKom($fid)) {
            return $this->json(400, ['ok' => false, 'message' => 'Parameter tidak valid.']);
        }
        if (mb_strlen($txt) < 3 || mb_strlen($txt) > static::MAX_KOM) {
            return $this->json(422, ['ok' => false, 'message' => 'Komentar 3–' . static::MAX_KOM . ' karakter.']);
        }
        db_connect()->table(static::T_KOM)->insert([static::C_ID => $fid, 'komentar' => $txt, 'fullname' => $this->user['fullname'] ?? '',
            'user_id' => $this->userId, 'kodebpr' => (string) $this->userKodebpr, 'periode_id' => (int) $this->periodeId, 'is_read' => 0, 'created_at' => date('Y-m-d H:i:s')]);
        return $this->json(200, ['ok' => true, 'message' => 'Komentar terkirim.']);
    }

    /** Jumlah komentar belum dibaca per parameter. */
    public function unread()
    {
        if ($r = $this->guardPost(false, false)) {
            return $r;
        }
        $rows = db_connect()->table(static::T_KOM . ' c')->select('c.' . static::C_ID . ' AS f, COUNT(*) AS n')
            ->join(static::T_READ . ' r', 'r.comment_id = c.id AND r.user_id = ' . (int) $this->userId, 'left')
            ->where(['c.kodebpr' => (string) $this->userKodebpr, 'c.periode_id' => (int) $this->periodeId, 'c.user_id !=' => $this->userId])
            ->where('r.id IS NULL')->groupBy('c.' . static::C_ID)->get()->getResultArray();
        return $this->json(200, ['ok' => true, 'counts' => array_column($rows, 'n', 'f')]);
    }

    // ───────────────────────── sinkron ─────────────────────────

    /** Salin nilai, keterangan & kesimpulan periode sebelumnya (BPR yang sama); persetujuan direset. */
    public function syncPrev()
    {
        if ($r = $this->guardPost()) {
            return $r;
        }
        $prevId = $this->prevPeriodeId();
        $prev = $prevId ? $this->rows($prevId) : [];
        [, , $ps, $pt] = $this->semesters();
        if (!$prev) {
            return $this->json(422, ['ok' => false, 'message' => "Tidak ada data Faktor " . static::NO . " pada Semester {$ps} Tahun {$pt}."]);
        }
        $rows = $this->rows();
        $db = db_connect();
        $db->transStart();
        $n = 0;
        foreach ($prev as $fid => $p) {
            if ($fid !== static::SUMMARY_ID && !self::nv($p)) {
                continue;
            }
            $set = $fid === static::SUMMARY_ID
                ? array_map(static fn($k) => (string) ($p[$k] ?? ''), array_combine(array_keys(self::KESIMPULAN), array_keys(self::KESIMPULAN)))
                : ['nilai' => self::nv($p), 'keterangan' => (string) ($p['keterangan'] ?? '')];
            $this->upsert($fid, $set + ['user_id' => $this->userId, 'accdekom2' => 0, 'accdir2' => 0], $rows);
            $n++;
        }
        $this->recalcSummary($rows);
        $this->resetApproval();
        $db->transComplete();
        return $this->json(200, ['ok' => true, 'reload' => true, 'message' => "{$n} data disalin dari Semester {$ps} Tahun {$pt}. Persetujuan perlu diberikan ulang."]);
    }
}
