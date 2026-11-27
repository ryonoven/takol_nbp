<?php

namespace App\Libraries;

use App\Models\M_tksindikator;

/**
 * Basis halaman faktor TKS berbasis indikator rasio (Rentabilitas, Permodalan).
 *
 *  - index   : form nominal tiap indikator untuk periode saat ini & sebelumnya (rasio & peringkat dihitung langsung di layar).
 *  - save    : simpan seluruh nominal + deskripsi + analisa; rasio, peringkat & nilai faktor dihitung ulang di server.
 *  - mis     : ambil nominal dari MIS TKS (GET JSON) untuk diisikan ke form — tersimpan setelah user klik Simpan.
 *  - approve : persetujuan Direktur Utama (act=approve|cancel). Setiap perubahan data membatalkan persetujuan.
 * Nominal periode sebelumnya dapat disalin dari data periode sebelumnya (dikirim bersama halaman).
 */
abstract class TksIndikatorController extends TksBaseController
{
    /** Key halaman (komentar & nama controller). */
    public const PAGE = '';
    public const NO = 3;
    public const JUDUL = '';

    /** Batas nominal (BIGINT). */
    private const MAX_NOMINAL = 999999999999999999;

    protected M_tksindikator $model;

    abstract protected function newModel(): M_tksindikator;

    /** Nominal dari MIS TKS: kolom input => nilai (null bila tidak ada). */
    abstract protected function misData(string $kodebpr, string $periodeCurrent, string $periodePrev): array;

    public function __construct()
    {
        parent::__construct();
        $this->model = $this->newModel();
    }

    private function row(): ?array
    {
        return $this->model->getByKodebprAndPeriode($this->userKodebpr, $this->periodeId);
    }

    /** "1.234.567" / "-1.234.567,50" / "1234567" → int; '' → 0; tidak valid → null. */
    private static function parseNominal($v): ?int
    {
        $s = str_replace([' ', "\u{00A0}", 'Rp', 'rp'], '', trim((string) $v));
        if ($s === '' || $s === '-') {
            return 0;
        }
        $s = str_replace(',', '.', str_replace('.', '', $s));
        if (!preg_match('/^-?\d+(\.\d+)?$/', $s) || abs((float) $s) > self::MAX_NOMINAL) {
            return null;
        }
        return (int) round((float) $s);
    }

    /** Daftar singkatan indikator, mis. "ROA, BOPO & NIM". */
    private function indList(): string
    {
        $k = array_map('strtoupper', array_keys($this->model::INDIKATOR));
        $last = array_pop($k);
        return $k ? implode(', ', $k) . ' & ' . $last : $last;
    }

    // ------------------------------------------------------------------ halaman

    public function index()
    {
        if ($r = $this->guardPage()) {
            return $r;
        }
        $m = $this->model;
        [$sem, $tahun, $semB, $tahunB] = $this->semesters();

        // Nominal periode sebelumnya → dapat disalin ke kolom "periode sebelumnya"
        $prev = null;
        if ($prevId = $this->prevPeriodeId()) {
            $p = $m->getByKodebprAndPeriode($this->userKodebpr, $prevId);
            if ($p) {
                $prev = [];
                foreach ($m::INDIKATOR as $ind) {
                    foreach ($ind['inputs'] as [, $cur, $bef]) {
                        $prev[$bef] = (int) $p[$cur];
                    }
                }
                if (!array_filter($prev)) {
                    $prev = null;
                }
            }
        }

        // Data lama: rasio/peringkat tersimpan tidak sesuai rumus → minta simpan ulang
        $row = $this->row();
        $outdated = false;
        if ($row) {
            $calc = $m::compute(array_map('intval', array_intersect_key($row, array_flip($m::inputCols()))));
            foreach ($calc as $k => $v) {
                $cur = $row[$k] ?? '';
                if (is_numeric($v) && is_numeric($cur) ? abs((float) $v - (float) $cur) > 0.011 : (string) $v !== (string) $cur) {
                    $outdated = true;
                    break;
                }
            }
        }

        $label = static fn($s, $t) => 'Semester ' . ($s === 1 ? 'I' : 'II') . ' ' . $t;
        return $this->render('Laporansemester/Tks/_indikator', [
            'judul' => static::JUDUL,
            'cfg' => [
                'page' => static::PAGE,
                'route' => strtolower(static::PAGE),
                'no' => static::NO,
                'model' => get_class($m),
                'ind_list' => $this->indList(),
                'copy_if_empty' => defined(get_class($m) . '::COPY_IF_EMPTY') ? $m::COPY_IF_EMPTY : [],
            ],
            'bprData' => $this->infobprModel->getBprByKode($this->userKodebpr) ?? [],
            'row' => $row ?? [],
            'outdated' => $outdated,
            'prev' => $prev,
            'curLabel' => $label($sem, $tahun),
            'prevLabel' => $label($semB, $tahunB),
            'canEdit' => $this->canEdit(),
            'canApprove' => $this->canApprove(),
            'maxText' => self::MAX_TEXT,
            'nav' => self::FAKTOR_NAV,
        ]);
    }

    // ------------------------------------------------------------------ aksi (JSON)

    public function save()
    {
        if ($r = $this->guardPost()) {
            return $r;
        }
        $m = $this->model;
        $row = $this->row();
        $errors = [];
        $in = [];
        foreach ($m::inputCols() as $col) {
            $raw = $this->request->getPost($col);
            if ($raw === null) {
                $in[$col] = (int) ($row[$col] ?? 0); // tidak dikirim = tidak diubah
                continue;
            }
            $v = self::parseNominal($raw);
            if ($v === null) {
                $errors[$col] = 'Nominal tidak valid.';
            }
            $in[$col] = (int) $v;
        }
        $data = $in + $m::compute($in);
        foreach ($m::TEXT_COLS as $f) {
            $v = $this->request->getPost($f) ?? ($row[$f] ?? '');
            $v = trim(str_replace("\r\n", "\n", (string) $v));
            if (mb_strlen($v) > self::MAX_TEXT) {
                $errors[$f] = 'Maksimal ' . number_format(self::MAX_TEXT, 0, ',', '.') . ' karakter.';
            }
            $data[$f] = $v;
        }
        if ($errors) {
            return $this->json(422, ['ok' => false, 'message' => 'Periksa kembali isian yang ditandai.', 'errors' => $errors]);
        }

        if ($row) {
            $changed = false;
            foreach ($data as $k => $v) {
                $cur = $row[$k] ?? '';
                $same = (is_numeric($cur) && is_numeric($v)) ? abs((float) $cur - (float) $v) < 0.0001
                    : str_replace("\r\n", "\n", (string) $cur) === (string) $v;
                if (!$same) {
                    $changed = true;
                    break;
                }
            }
            if (!$changed) {
                return $this->json(200, ['ok' => true, 'message' => 'Tidak ada perubahan.']);
            }
        }

        $data += [
            'user_id' => $this->userId,
            'fullname' => (string) ($this->user['fullname'] ?? $this->user['username'] ?? ''),
            'updated_at' => date('Y-m-d H:i:s'),
            'is_approved' => '0',
        ];
        try {
            if ($row) {
                $m->update((int) $row['id'], $data);
            } else {
                $m->insert($data + $m::insertDefaults() + ['kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId]);
            }
        } catch (\Throwable $e) {
            log_message('error', static::PAGE . '::save ' . $e->getMessage());
            return $this->json(500, ['ok' => false, 'message' => 'Data gagal disimpan.']);
        }
        $msg = ($row && (int) $row['is_approved'] === 1) ? 'Data disimpan. Persetujuan Direktur Utama dibatalkan karena data berubah.' : 'Data berhasil disimpan.';
        return $this->json(200, ['ok' => true, 'reload' => true, 'message' => $msg]);
    }

    /** Nominal indikator dari MIS TKS (periode saat ini & sebelumnya). */
    public function mis()
    {
        if ($r = $this->guardPost(true, false)) {
            return $r;
        }
        [$sem, $tahun, $semB, $tahunB] = $this->semesters();
        $cur = $tahun . ($sem === 1 ? '06' : '12');
        $prev = $tahunB . ($semB === 1 ? '06' : '12');
        try {
            $data = $this->misData((string) $this->userKodebpr, $cur, $prev);
        } catch (\Throwable $e) {
            log_message('error', static::PAGE . '::mis ' . $e->getMessage());
            return $this->json(500, ['ok' => false, 'message' => 'Gagal membaca data MIS TKS.']);
        }
        $data = array_intersect_key($data, array_flip($this->model::inputCols()));
        $data = array_map(static fn($v) => (int) round((float) $v), array_filter($data, static fn($v) => $v !== null && is_numeric($v)));
        if (!$data) {
            return $this->json(404, ['ok' => false, 'message' => "Data MIS TKS periode {$cur} / {$prev} belum tersedia."]);
        }
        return $this->json(200, ['ok' => true, 'data' => $data, 'message' => count($data) . ' nominal diambil dari MIS TKS. Periksa lalu klik Simpan.']);
    }

    public function approve()
    {
        if ($r = $this->guardPost(false)) {
            return $r;
        }
        if (!$this->canApprove()) {
            return $this->json(403, ['ok' => false, 'message' => 'Hanya Direktur Utama yang dapat memberikan persetujuan.']);
        }
        $row = $this->row();
        $act = (string) $this->request->getPost('act');
        if ($act === 'approve') {
            if (!$row || (int) $row[$this->model::FAKTOR_COLS[0][1]] < 1) {
                return $this->json(422, ['ok' => false, 'message' => 'Lengkapi ' . $this->indList() . ' periode saat ini terlebih dahulu.']);
            }
            $upd = ['is_approved' => '1', 'approved_by' => $this->userId, 'approved_at' => date('Y-m-d H:i:s')];
            $msg = 'Data disetujui Direktur Utama.';
        } elseif ($act === 'cancel') {
            if (!$row) {
                return $this->json(404, ['ok' => false, 'message' => 'Data tidak ditemukan.']);
            }
            $upd = ['is_approved' => '0'];
            $msg = 'Persetujuan dibatalkan.';
        } else {
            return $this->json(400, ['ok' => false, 'message' => 'Aksi tidak dikenal.']);
        }
        try {
            $this->model->update((int) $row['id'], $upd);
        } catch (\Throwable $e) {
            log_message('error', static::PAGE . '::approve ' . $e->getMessage());
            return $this->json(500, ['ok' => false, 'message' => 'Gagal memproses persetujuan.']);
        }
        return $this->json(200, ['ok' => true, 'reload' => true, 'message' => $msg]);
    }
}
