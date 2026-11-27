<?php

namespace App\Libraries;

use App\Models\M_tksfaktor;

/**
 * Basis halaman faktor TKS (Profil Risiko, Tata Kelola) — satu peringkat 1–5 per periode yang dapat
 * disinkronkan dari laporan sumbernya atau diisi manual.
 *
 *  - sync    : salin peringkat, peringkat periode sebelumnya & kesimpulan dari laporan sumber (mode tersinkron).
 *  - unsync  : lepas sinkronisasi → peringkat & analisis dapat diisi manual.
 *  - save    : simpan peringkat (mode manual), peringkat sebelumnya, deskripsi & analisis (mode manual).
 *  - approve : persetujuan Direktur Utama (act=approve|cancel). Setiap perubahan data membatalkan persetujuan.
 * Semua aksi tulis = POST JSON + CSRF, dibatasi ke BPR & periode milik user.
 */
abstract class TksFaktorController extends TksBaseController
{
    /** Key halaman (komentar & nama controller), mis. 'Tksprofilrisiko'. */
    public const PAGE = '';
    /** Nomor faktor TKS (1–4). */
    public const NO = 1;
    public const JUDUL = '';
    public const ICON = 'fa-shield-alt';
    /** Nama & controller laporan sumber sinkronisasi. */
    public const SOURCE_NAME = '';
    public const SOURCE_URL = '';

    protected M_tksfaktor $model;

    abstract protected function newModel(): M_tksfaktor;

    /**
     * Data laporan sumber per periode.
     * @param int[] $periodeIds
     * @return array<int, array{pk:mixed, pk_before:mixed, kesimpulan:?string}> periode_id => data
     */
    abstract protected function sourceRows(array $periodeIds): array;

    public function __construct()
    {
        parent::__construct();
        $this->model = $this->newModel();
    }

    // ------------------------------------------------------------------ helper

    private function row(): ?array
    {
        return $this->model->getByKodebprAndPeriode($this->userKodebpr, $this->periodeId);
    }

    private function isSyncedRow(?array $row): bool
    {
        return $row && (int) ($row[$this->model::SYNC_FLAG] ?? 0) === 1;
    }

    /** Simpan (insert/update) + selalu batalkan persetujuan karena data berubah. */
    private function store(?array $row, array $data): void
    {
        $data += [
            'user_id' => $this->userId,
            'fullname' => (string) ($this->user['fullname'] ?? $this->user['username'] ?? ''),
            'updated_at' => date('Y-m-d H:i:s'),
            'is_approved' => '0',
        ];
        if ($row) {
            $this->model->update((int) $row['id'], $data);
        } else {
            $this->model->insert($data + $this->model->insertDefaults() + [
                'kodebpr' => $this->userKodebpr,
                'periode_id' => $this->periodeId,
            ]);
        }
    }

    protected static function pk($v): int
    {
        $v = is_numeric($v) ? (int) round((float) $v) : 0;
        return ($v >= 1 && $v <= 5) ? $v : 0;
    }

    /**
     * Data laporan sumber untuk periode aktif.
     * @return array{available:bool, pk:int, pk_before:int, kesimpulan:string, sem:int, tahun:int, sem_before:int, tahun_before:int}
     */
    private function sumber(): array
    {
        [$sem, $tahun, $semB, $tahunB] = $this->semesters();
        $out = ['available' => false, 'pk' => 0, 'pk_before' => 0, 'kesimpulan' => '',
            'sem' => $sem, 'tahun' => $tahun, 'sem_before' => $semB, 'tahun_before' => $tahunB];

        $prevId = $this->prevPeriodeId();
        $rows = $this->sourceRows(array_values(array_filter([(int) $this->periodeId, $prevId])));

        $cur = $rows[(int) $this->periodeId] ?? null;
        $out['pk'] = self::pk($cur['pk'] ?? 0);
        if (!$out['pk']) {
            return $out;
        }
        $out['available'] = true;
        $out['kesimpulan'] = trim(str_replace("\r\n", "\n", (string) ($cur['kesimpulan'] ?? '')));
        $out['pk_before'] = self::pk($cur['pk_before'] ?? 0) ?: self::pk($rows[$prevId]['pk'] ?? 0);
        return $out;
    }

    // ------------------------------------------------------------------ halaman

    public function index()
    {
        if ($r = $this->guardPage()) {
            return $r;
        }

        $row = $this->row();
        $src = $this->sumber();
        $synced = $this->isSyncedRow($row);

        // Data tersinkron tetapi laporan sumber sudah berubah sejak sinkron terakhir
        $stale = $synced && (!$src['available']
            || self::pk($row['nilaiperingkat']) !== $src['pk']
            || ($src['pk_before'] && self::pk($row['nilaiperingkatbefore']) !== $src['pk_before'])
            || trim(str_replace("\r\n", "\n", (string) $row['analisis'])) !== $src['kesimpulan']
        );

        $data = [
            'judul' => static::JUDUL,
            'cfg' => [
                'page' => static::PAGE,
                'route' => strtolower(static::PAGE),
                'no' => static::NO,
                'icon' => static::ICON,
                'source_name' => static::SOURCE_NAME,
                'source_url' => static::SOURCE_URL,
                'bobot' => $this->model::BOBOT,
                'labels' => $this->model::LABELS,
                'nav' => self::FAKTOR_NAV,
                'max' => self::MAX_TEXT,
            ],
            'bprData' => $this->infobprModel->getBprByKode($this->userKodebpr) ?? [],
            'periodeDetail' => $this->periode,
            'row' => $row ?? [],
            'src' => $src,
            'synced' => $synced,
            'stale' => $stale,
            'canEdit' => $this->canEdit(),
            'canApprove' => $this->canApprove(),
        ];

        return $this->render('Laporansemester/Tks/_faktor', $data);
    }

    // ------------------------------------------------------------------ aksi (POST JSON)

    public function sync()
    {
        if ($r = $this->guardPost()) {
            return $r;
        }
        $src = $this->sumber();
        if (!$src['available']) {
            return $this->json(422, ['ok' => false, 'message' => 'Nilai peringkat pada ' . static::SOURCE_NAME . ' periode ini belum tersedia. Lengkapi laporan tersebut terlebih dahulu.']);
        }
        $row = $this->row();
        // Peringkat sebelumnya: dari laporan sumber bila ada, jika tidak pertahankan isian manual
        $pkBefore = $src['pk_before'] ?: self::pk($row['nilaiperingkatbefore'] ?? 0);

        try {
            $this->store($row, $this->model->nilaiFields($src['pk'], $pkBefore) + [
                'analisis' => $src['kesimpulan'],
                $this->model::SYNC_FLAG => 1,
                'sync_date' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', static::PAGE . '::sync ' . $e->getMessage());
            return $this->json(500, ['ok' => false, 'message' => 'Sinkronisasi gagal.']);
        }
        return $this->json(200, ['ok' => true, 'reload' => true, 'message' => 'Data berhasil disinkronkan dengan ' . static::SOURCE_NAME . '.']);
    }

    public function unsync()
    {
        if ($r = $this->guardPost()) {
            return $r;
        }
        $row = $this->row();
        if (!$this->isSyncedRow($row)) {
            return $this->json(200, ['ok' => true, 'reload' => true, 'message' => 'Data sudah dalam mode manual.']);
        }
        try {
            $this->store($row, [$this->model::SYNC_FLAG => 0]);
        } catch (\Throwable $e) {
            log_message('error', static::PAGE . '::unsync ' . $e->getMessage());
            return $this->json(500, ['ok' => false, 'message' => 'Gagal membatalkan sinkronisasi.']);
        }
        return $this->json(200, ['ok' => true, 'reload' => true, 'message' => 'Sinkronisasi dibatalkan. Peringkat & analisis kini dapat diisi manual.']);
    }

    public function save()
    {
        if ($r = $this->guardPost()) {
            return $r;
        }
        $row = $this->row();
        $synced = $this->isSyncedRow($row);
        $errors = [];

        $pk = self::pk(($synced ? null : $this->request->getPost('nilaiperingkat')) ?? ($row['nilaiperingkat'] ?? 0));
        if (!$pk) {
            $errors['nilaiperingkat'] = 'Pilih nilai peringkat 1–5.';
        }
        // Field yang tidak dikirim = tidak diubah
        $rawBefore = $this->request->getPost('nilaiperingkatbefore');
        $rawBefore = $rawBefore === null ? (string) ($row['nilaiperingkatbefore'] ?? '') : (string) $rawBefore;
        $pkBefore = self::pk($rawBefore);
        if ($rawBefore !== '' && $rawBefore !== '0' && !$pkBefore) {
            $errors['nilaiperingkatbefore'] = 'Nilai peringkat tidak valid.';
        }

        $data = $this->model->nilaiFields($pk, $pkBefore);
        foreach ($synced ? ['deskripsi'] : ['deskripsi', 'analisis'] as $f) {
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
                if (!array_key_exists($k, $row)) {
                    continue; // kolom tidak ada di tabel ini
                }
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

        try {
            $this->store($row, $data);
        } catch (\Throwable $e) {
            log_message('error', static::PAGE . '::save ' . $e->getMessage());
            return $this->json(500, ['ok' => false, 'message' => 'Data gagal disimpan.']);
        }
        $msg = ($row && (int) $row['is_approved'] === 1) ? 'Data disimpan. Persetujuan Direktur Utama dibatalkan karena data berubah.' : 'Data berhasil disimpan.';
        return $this->json(200, ['ok' => true, 'reload' => true, 'message' => $msg]);
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
            if (!$row || !self::pk($row['nilaiperingkat'])) {
                return $this->json(422, ['ok' => false, 'message' => 'Lengkapi nilai peringkat terlebih dahulu.']);
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
