<?php
/**
 * Halaman faktor TKS (dipakai App\Libraries\TksFaktorController: Profil Risiko, Tata Kelola).
 * Variabel: $judul, $cfg, $bprData, $periodeDetail, $row, $src, $synced, $stale, $canEdit, $canApprove
 */
$L = $cfg['labels'];
$bobotPct = (int) round($cfg['bobot'] * 100);
$srcName = esc($cfg['source_name']);
$route = $cfg['route'];
$pk = (int) ($row['nilaiperingkat'] ?? 0);
$pkB = (int) ($row['nilaiperingkatbefore'] ?? 0);
$approved = (int) ($row['is_approved'] ?? 0) === 1;
$fmtNum = static fn($v) => number_format((float) $v, 2, ',', '.');
$fmtDt = static function ($v) {
    $t = $v ? strtotime((string) $v) : false;
    return ($t && $t > 0) ? date('d/m/Y H:i', $t) : '';
};
$semLabel = static fn($s, $t) => 'Semester ' . ((int) $s === 1 ? 'I' : 'II') . ' ' . $t;
$curLabel = $semLabel($src['sem'], $src['tahun']);
$prevLabel = $semLabel($src['sem_before'], $src['tahun_before']);
$dis = $canEdit ? '' : 'disabled';
$pkSelect = static function (string $name, int $val, bool $disabled, string $empty) use ($L) {
    $h = '<select id="f_' . $name . '" name="' . $name . '" class="pu-input" data-faktor="' . $name . '"' . ($disabled ? ' disabled' : '') . '>';
    $h .= '<option value="">' . $empty . '</option>';
    foreach ($L as $v => $t) {
        $h .= '<option value="' . $v . '"' . ($val === $v ? ' selected' : '') . '>' . $v . ' — ' . esc($t) . '</option>';
    }
    return $h . '</select>';
};
$scores = [
    ['Peringkat ' . $curLabel, $pk, true],
    ['Peringkat ' . $prevLabel, $pkB, false],
];
?>
<link rel="stylesheet" href="<?= base_url('asset/css/transparansi.css') ?>?v=<?= @filemtime(FCPATH . 'asset/css/transparansi.css') ?>">
<div id="pu-toast" class="pu-toast" aria-live="polite"></div>

<div class="container-fluid pu tks">
    <a href="<?= base_url('Showtks') ?>" class="pu-back"><i class="fas fa-arrow-left"></i> Laporan TKS</a>

    <?php foreach (['message' => 'success', 'error' => 'danger'] as $k => $cls): ?>
        <?php if ($msg = session()->getFlashdata($k)): ?>
            <div class="alert alert-<?= $cls ?> mt-3"><?= esc($msg) ?></div>
        <?php endif; ?>
    <?php endforeach; ?>

    <header class="pu-head">
        <div class="pu-head-main">
            <span class="pu-kicker">Laporan Semesteran · TKS · Faktor <?= $cfg['no'] ?> dari 4 · Bobot <?= $bobotPct ?>%</span>
            <h1 class="pu-title"><?= esc($judul) ?></h1>
            <div class="pu-meta">
                <span class="pu-bpr"><?= esc($bprData['namabpr'] ?? 'Nama BPR') ?></span>
                <span class="pu-year"><i class="far fa-calendar"></i> <?= $curLabel ?></span>
            </div>
        </div>
        <div class="pu-status">
            <div class="pu-chip <?= $approved ? 'ok' : '' ?>">
                <i class="fas <?= $approved ? 'fa-check-circle' : 'fa-clock' ?>"></i>
                <div>
                    <b>Direktur Utama</b>
                    <small><?= $approved ? 'Disetujui ' . $fmtDt($row['approved_at'] ?? '') : ($row ? 'Menunggu persetujuan' : 'Data belum diisi') ?></small>
                </div>
            </div>
        </div>
    </header>

    <!-- Ringkasan nilai -->
    <section class="tpr-scores">
        <?php foreach ($scores as [$title, $v, $main]): ?>
            <div class="tpr-score <?= $main ? 'main' : '' ?> pk-<?= $v ?>">
                <span class="tpr-lbl"><?= $title ?></span>
                <?php if ($v): ?>
                    <span class="tpr-val"><?= $v ?> <small><?= esc($L[$v]) ?></small></span>
                    <span class="tpr-sub">Nilai faktor <?= $v ?> × <?= $bobotPct ?>% = <b><?= $fmtNum($v * $cfg['bobot']) ?></b></span>
                <?php else: ?>
                    <span class="tpr-val">—</span>
                    <span class="tpr-sub">Belum diisi</span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </section>

    <!-- Status sinkronisasi dengan laporan sumber -->
    <?php
    if ($synced && !$stale) {
        [$cls, $icon, $title] = ['ok', 'fa-link', 'Tersinkron dengan ' . $srcName];
    } elseif ($synced) {
        [$cls, $icon, $title] = ['warn', 'fa-exclamation-triangle', $src['available']
            ? $srcName . ' berubah sejak sinkronisasi terakhir'
            : 'Tersinkron, tetapi nilai di ' . $srcName . ' ' . $curLabel . ' kini kosong'];
    } elseif ($src['available']) {
        [$cls, $icon, $title] = ['info', 'fa-sync-alt', 'Data ' . $srcName . ' tersedia'];
    } else {
        [$cls, $icon, $title] = ['muted', 'fa-unlink', $srcName . ' ' . $curLabel . ' belum lengkap'];
    }
    ?>
    <section class="tpr-sync <?= $cls ?>">
        <i class="fas <?= $icon ?> tpr-sync-ic"></i>
        <div class="tpr-sync-body">
            <b><?= $title ?></b>
            <?php if ($synced && !$stale): ?>
                <small>Peringkat & analisis diambil otomatis<?= ($d = $fmtDt($row['sync_date'] ?? '')) ? ' · disinkronkan ' . $d : '' ?>.
                    Batalkan sinkronisasi untuk mengisi manual.</small>
            <?php elseif ($src['available']): ?>
                <small>Nilai di <?= $srcName ?>: peringkat <b><?= $src['pk'] ?> (<?= esc($L[$src['pk']]) ?>)</b>
                    <?= $src['pk_before'] ? '· periode sebelumnya <b>' . $src['pk_before'] . ' (' . esc($L[$src['pk_before']]) . ')</b>' : '· periode sebelumnya belum tersedia (isi manual)' ?>
                    · kesimpulan <?= $src['kesimpulan'] !== '' ? number_format(mb_strlen($src['kesimpulan']), 0, ',', '.') . ' karakter' : 'kosong' ?>.</small>
            <?php else: ?>
                <small>Nilai peringkat belum tersedia. Lengkapi <a href="<?= base_url($cfg['source_url']) ?>"><?= $srcName ?></a>
                    untuk sinkron otomatis, atau isi peringkat secara manual di bawah.</small>
            <?php endif; ?>
        </div>
        <?php if ($canEdit): ?>
            <div class="tpr-sync-act">
                <?php if ($src['available'] && (!$synced || $stale)): ?>
                    <button type="button" class="pu-btn pu-btn-primary tpr-act" data-url="<?= site_url($route . '/sync') ?>"
                        data-confirm="Ambil peringkat, peringkat periode sebelumnya & kesimpulan dari <?= $srcName ?>?<?= $approved ? '\n\nPersetujuan Direktur Utama akan dibatalkan.' : '' ?>">
                        <i class="fas fa-sync-alt"></i> <?= $synced ? 'Sinkron ulang' : 'Sinkronkan' ?></button>
                <?php endif; ?>
                <?php if ($synced): ?>
                    <button type="button" class="pu-btn pu-btn-light tpr-act" data-url="<?= site_url($route . '/unsync') ?>"
                        data-confirm="Batalkan sinkronisasi? Nilai tetap tersimpan dan dapat diubah manual.<?= $approved ? '\n\nPersetujuan Direktur Utama akan dibatalkan.' : '' ?>">
                        <i class="fas fa-unlink"></i> Isi manual</button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Form -->
    <section class="pu-card">
        <div class="pu-card-head"><h2><i class="fas <?= $cfg['icon'] ?>"></i> <?= esc($judul) ?></h2></div>
        <div class="pu-card-body">
            <?php if (!$canEdit): ?>
                <div class="pu-note"><i class="fas fa-eye"></i> Mode baca — hanya Pengelola (PE) atau Admin yang dapat mengubah data.</div>
            <?php elseif ($approved): ?>
                <div class="pu-note warn"><i class="fas fa-info-circle"></i> Data sudah disetujui. Menyimpan perubahan akan membatalkan persetujuan Direktur Utama.</div>
            <?php endif; ?>

            <form id="puForm" class="row" autocomplete="off" novalidate>
                <input type="hidden" id="puCsrf" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">

                <div class="col-md-6 pu-field" data-f="nilaiperingkat">
                    <label for="f_nilaiperingkat">Peringkat <?= $curLabel ?> <span class="text-danger">*</span>
                        <?php if ($synced): ?><i class="fas fa-lock tpr-lock" title="Dari <?= $srcName ?>"></i><?php endif; ?></label>
                    <?= $pkSelect('nilaiperingkat', $pk, !$canEdit || $synced, '— Pilih peringkat —') ?>
                    <div class="pu-foot"><span class="pu-err" data-msg="nilaiperingkat"></span><span class="tpr-nf" data-nf="nilaiperingkat"></span></div>
                </div>
                <div class="col-md-6 pu-field" data-f="nilaiperingkatbefore">
                    <label for="f_nilaiperingkatbefore">Peringkat <?= $prevLabel ?> (periode sebelumnya)</label>
                    <?= $pkSelect('nilaiperingkatbefore', $pkB, !$canEdit, '— Belum diisi —') ?>
                    <div class="pu-foot"><span class="pu-err" data-msg="nilaiperingkatbefore"></span><span class="tpr-nf" data-nf="nilaiperingkatbefore"></span></div>
                </div>

                <div class="col-12 pu-field" data-f="analisis">
                    <label for="f_analisis">Analisis / penjelasan
                        <?php if ($synced): ?><i class="fas fa-lock tpr-lock" title="Dari <?= $srcName ?>"></i><?php endif; ?></label>
                    <?php if ($synced): ?>
                        <div class="pu-read <?= trim((string) ($row['analisis'] ?? '')) === '' ? 'empty' : '' ?>"><?= trim((string) ($row['analisis'] ?? '')) !== '' ? esc($row['analisis']) : 'Kesimpulan ' . $cfg['source_name'] . ' masih kosong.' ?></div>
                    <?php else: ?>
                        <textarea id="f_analisis" name="analisis" class="pu-input pu-area" rows="7" maxlength="<?= $cfg['max'] ?>"
                            data-max="<?= $cfg['max'] ?>" placeholder="Analisis komprehensif faktor ini…" <?= $dis ?>><?= esc($row['analisis'] ?? '') ?></textarea>
                    <?php endif; ?>
                    <div class="pu-foot"><span class="pu-err" data-msg="analisis"></span><span class="pu-count" data-cnt="analisis"></span></div>
                </div>

                <div class="col-12 pu-field" data-f="deskripsi">
                    <label for="f_deskripsi">Deskripsi tambahan</label>
                    <textarea id="f_deskripsi" name="deskripsi" class="pu-input pu-area" rows="5" maxlength="<?= $cfg['max'] ?>"
                        data-max="<?= $cfg['max'] ?>" placeholder="Karakteristik dan kondisi BPR berdasarkan peringkat faktor ini…" <?= $dis ?>><?= esc($row['deskripsi'] ?? '') ?></textarea>
                    <div class="pu-foot"><span class="pu-err" data-msg="deskripsi"></span><span class="pu-count" data-cnt="deskripsi"></span></div>
                </div>
            </form>
        </div>
        <?php if ($canEdit): ?>
            <div class="pu-savebar">
                <button type="submit" form="puForm" class="pu-btn pu-btn-primary" id="puSave"><i class="fas fa-save"></i> Simpan <kbd class="d-none d-md-inline">Ctrl+S</kbd></button>
                <span class="pu-dirty" id="puDirty"><i class="fas fa-circle"></i> <span>Belum ada perubahan</span></span>
            </div>
        <?php endif; ?>
    </section>

    <!-- Persetujuan -->
    <?php if ($canApprove): ?>
        <section class="pu-approve">
            <div class="pu-approve-card <?= $approved ? 'ok' : '' ?>">
                <div class="pu-approve-who"><i class="fas fa-user-shield"></i> Direktur Utama</div>
                <?php if ($approved): ?>
                    <button type="button" class="pu-btn pu-btn-danger tpr-act" data-url="<?= site_url($route . '/approve') ?>" data-act="cancel"
                        data-confirm="Batalkan persetujuan <?= esc($judul) ?>?"><i class="fas fa-ban"></i> Batalkan</button>
                    <small>Disetujui <?= $fmtDt($row['approved_at'] ?? '') ?></small>
                <?php elseif ($pk): ?>
                    <button type="button" class="pu-btn pu-btn-primary tpr-act" data-url="<?= site_url($route . '/approve') ?>" data-act="approve"
                        data-confirm="Setujui <?= esc($judul) ?>?"><i class="fas fa-check-double"></i> Setujui</button>
                <?php else: ?>
                    <button type="button" class="pu-btn pu-btn-primary" disabled><i class="fas fa-lock"></i> Setujui</button>
                    <small>Lengkapi nilai peringkat terlebih dahulu</small>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Navigasi faktor TKS -->
    <nav class="pu-nav" aria-label="Faktor TKS">
        <?php foreach ($cfg['nav'] as $i => $n): ?>
            <a href="<?= base_url($i) ?>" title="<?= $n ?>" class="tpr-nav <?= $i === $cfg['page'] ? 'active' : '' ?>"
                <?= $i === $cfg['page'] ? 'aria-current="page"' : '' ?>><?= $n ?></a>
        <?php endforeach; ?>
        <a href="<?= base_url('Showtks') ?>" class="all">Ringkasan TKS</a>
    </nav>
</div>

<?= view('templates/komentar', ['komentarPage' => $cfg['page']]) ?>

<style>
    .tpr-scores { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px; margin-bottom: 14px; }
    .tpr-score { display: flex; flex-direction: column; gap: 2px; padding: 16px 18px; border-radius: 16px; color: #fff; background: #94a3b8; box-shadow: 0 6px 18px rgba(15,23,42,.08); }
    .tpr-score:not(.main) { opacity: .9; }
    .tpr-score.pk-1 { background: linear-gradient(135deg, #0284c7, #0ea5e9); }
    .tpr-score.pk-2 { background: linear-gradient(135deg, #047857, #10b981); }
    .tpr-score.pk-3 { background: linear-gradient(135deg, #b45309, #f59e0b); }
    .tpr-score.pk-4 { background: linear-gradient(135deg, #c2410c, #f97316); }
    .tpr-score.pk-5 { background: linear-gradient(135deg, #b91c1c, #ef4444); }
    .tpr-lbl { font-size: .74rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; opacity: .92; }
    .tpr-val { font-size: 2rem; font-weight: 800; line-height: 1.15; }
    .tpr-val small { font-size: .95rem; font-weight: 700; opacity: .95; }
    .tpr-sub { font-size: .84rem; opacity: .95; }
    .tpr-sync { display: flex; flex-wrap: wrap; align-items: center; gap: 12px 14px; padding: 14px 16px; border-radius: 14px; border: 1.5px solid; margin-bottom: 16px; font-size: .88rem; }
    .tpr-sync.ok { background: var(--okbg, #ecfdf5); border-color: #6ee7b7; color: #065f46; }
    .tpr-sync.warn { background: #fffbeb; border-color: #fcd34d; color: #92400e; }
    .tpr-sync.info { background: #eef2ff; border-color: #c7d2fe; color: #312e81; }
    .tpr-sync.muted { background: #f8fafc; border-color: #e2e8f0; color: #475569; }
    .tpr-sync-ic { font-size: 1.3rem; }
    .tpr-sync-body { flex: 1 1 260px; display: flex; flex-direction: column; gap: 2px; }
    .tpr-sync-body small { font-size: .8rem; opacity: .95; }
    .tpr-sync-act { display: flex; flex-wrap: wrap; gap: 8px; }
    .tpr-lock { color: #94a3b8; font-size: .75rem; margin-left: 4px; }
    .tpr-nf { font-size: .74rem; color: var(--mut, #64748b); font-variant-numeric: tabular-nums; }
    .pu-nav .tpr-nav { width: auto; padding: 0 12px; }
</style>

<script>
    (function () {
        'use strict';
        if (!window.fetch) return;
        var csrf = document.getElementById('puCsrf'), form = document.getElementById('puForm');
        var busy = false, leaving = false;
        function toast(msg, err) {
            var d = document.createElement('div'); if (err) d.className = 'e'; d.textContent = msg;
            document.getElementById('pu-toast').appendChild(d); setTimeout(function () { d.remove(); }, 3800);
        }
        function post(url, fd, btn) {
            if (busy) return; busy = true;
            var ori = btn ? btn.innerHTML : ''; if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses…'; }
            fd.set(csrf.name, csrf.value);
            return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json().catch(function () { return { ok: false, message: 'Respons server tidak valid (' + r.status + ').' }; }); })
                .then(function (j) {
                    if (j.csrf) { csrf.name = j.csrf.name; csrf.value = j.csrf.hash; }
                    toast(j.message || 'Selesai.', !j.ok);
                    Object.keys(j.errors || {}).forEach(function (k) {
                        var w = form.querySelector('[data-f="' + k + '"]'); if (!w) return;
                        w.classList.add('err'); w.querySelector('[data-msg]').textContent = j.errors[k];
                    });
                    if (j.ok && j.reload) { leaving = true; setTimeout(function () { location.reload(); }, 700); }
                    return j;
                })
                .catch(function () { toast('Koneksi bermasalah, coba lagi.', true); })
                .then(function (j) { busy = false; if (btn && !leaving) { btn.disabled = false; btn.innerHTML = ori; } return j; });
        }

        // Tombol aksi (sinkron, isi manual, setujui/batalkan)
        document.querySelectorAll('.tpr-act').forEach(function (b) {
            b.addEventListener('click', function () {
                if (dirty() && !confirm('Ada perubahan yang belum disimpan dan akan hilang. Lanjutkan?')) return;
                if (b.dataset.confirm && !confirm(b.dataset.confirm.replace(/\\n/g, '\n'))) return;
                var fd = new FormData(); if (b.dataset.act) fd.set('act', b.dataset.act);
                post(b.dataset.url, fd, b);
            });
        });

        // Nilai faktor otomatis (peringkat × 25%)
        function nf(sel) {
            var o = form.querySelector('[data-nf="' + sel.name + '"]'); if (!o) return;
            o.textContent = sel.value ? 'Nilai faktor: ' + (sel.value * <?= (float) $cfg['bobot'] ?>).toFixed(2).replace('.', ',') : '';
        }
        form.querySelectorAll('[data-faktor]').forEach(nf);

        var saveBtn = document.getElementById('puSave');
        function ser() { var fd = new FormData(form); fd.delete(csrf.name); return new URLSearchParams(fd).toString(); }
        var snap = ser();
        function dirty() { return saveBtn ? ser() !== snap : false; }
        if (!saveBtn) return;

        var dirtyEl = document.getElementById('puDirty');
        function setDirty() {
            var d = dirty(); dirtyEl.classList.toggle('on', d);
            dirtyEl.lastElementChild.textContent = d ? 'Ada perubahan belum disimpan' : 'Belum ada perubahan';
        }
        function count(el) {
            var c = form.querySelector('[data-cnt="' + el.name + '"]'); if (!c) return;
            var max = +el.dataset.max, len = el.value.length;
            c.textContent = len.toLocaleString('id-ID') + ' / ' + max.toLocaleString('id-ID');
            c.className = 'pu-count' + (len >= max ? ' max' : len >= max * 0.9 ? ' warn' : '');
        }
        form.querySelectorAll('textarea[data-max]').forEach(count);
        var t;
        form.addEventListener('input', function (e) {
            if (e.target.dataset.max) count(e.target);
            var w = e.target.closest('.pu-field'); if (w) { w.classList.remove('err'); w.querySelector('[data-msg]').textContent = ''; }
            clearTimeout(t); t = setTimeout(setDirty, 200);
        });
        form.addEventListener('change', function (e) { if (e.target.dataset.faktor) nf(e.target); setDirty(); });
        window.addEventListener('beforeunload', function (e) { if (!leaving && dirty()) { e.preventDefault(); e.returnValue = ''; } });

        function save() {
            var p = post('<?= site_url($route . '/save') ?>', new FormData(form), saveBtn);
            if (p) p.then(function (j) { if (j && j.ok) { snap = ser(); setDirty(); } });
        }
        form.addEventListener('submit', function (e) { e.preventDefault(); save(); });
        document.addEventListener('keydown', function (e) { if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) { e.preventDefault(); save(); } });
    })();
</script>
