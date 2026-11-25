<?php
/**
 * Laporan Transparansi 12 — Rasio Gaji Tertinggi dan Gaji Terendah.
 * Variabel: $periode, $row, $amounts, $tindak, $namabpr, $canEdit, $canKomut, $canDirut, $isAdmin, $userId, $hasPrev
 */
use App\Models\M_rasiogaji as R;
use App\Models\M_showtransparansi;

$PAGE = 'Rasiogaji';
$komutOk = (int) ($row['accdekom'] ?? 0) === 1;
$dirutOk = (int) ($row['is_approved'] ?? 0) === 1;
$fmt = static fn($at): string => $at ? ' · ' . esc(date('d/m/Y H:i', strtotime($at))) : '';
$rp = static fn($n): string => (int) $n > 0 ? number_format((int) $n, 0, ',', '.') : '';
$dis = $canEdit ? '' : 'disabled';
$ratios = R::computeRatios($amounts);
$ratioText = static fn($r): string => $r === null ? '—' : number_format($r, 2, ',', '.') . ' : 1';
$tindakVal = (string) ($tindak['tindaklanjut'] ?? '');

$approvals = [
    ['komut', 'Komisaris Utama', $komutOk, $row['accdekom_at'] ?? null, (int) ($row['accdekom_by'] ?? 0), $canKomut],
    ['dirut', 'Direktur Utama', $dirutOk, $row['approved_at'] ?? null, (int) ($row['approved_by'] ?? 0), $canDirut],
];
$secNo = array_search($PAGE, array_column(M_showtransparansi::SECTIONS, 'controller'), true) + 1;
$groups = [['Pegawai', 'pegawaitinggi', 'pegawairendah'], ['Anggota Direksi', 'dirtinggi', 'dirrendah'], ['Anggota Dewan Komisaris', 'dekomtinggi', 'dekomrendah']];

$input = static function (string $col, string $label) use ($amounts, $dis, $rp) {
    return '<td data-label="' . esc($label, 'attr') . '"><input type="text" name="' . $col . '" id="f_' . $col . '"'
        . ' class="pu-input pk-num pk-rp" inputmode="numeric" autocomplete="off" placeholder="0" data-rp'
        . ' value="' . $rp($amounts[$col] ?? 0) . '" ' . $dis . '><div class="pu-err" data-msg="' . $col . '"></div></td>';
};
?>
<link rel="stylesheet" href="<?= base_url('asset/css/transparansi.css') ?>?v=<?= @filemtime(FCPATH . 'asset/css/transparansi.css') ?>">
<div id="pu-toast" class="pu-toast" aria-live="polite"></div>

<div class="container-fluid pu">
    <a href="<?= base_url('ShowTransparansi') ?>" class="pu-back"><i class="fas fa-arrow-left"></i> Kembali</a>

    <?php foreach (['message' => 'success', 'error' => 'danger', 'err' => 'danger'] as $k => $cls): ?>
        <?php if ($msg = session()->getFlashdata($k)): ?>
            <div class="alert alert-<?= $cls ?> alert-dismissible fade show mt-3" role="alert">
                <button type="button" class="close" data-dismiss="alert">&times;</button><?= esc($msg) ?>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php if (!$periode): ?>
        <div class="alert alert-warning mt-3">Periode tahunan aktif tidak ditemukan. Pilih periode tahunan terlebih dahulu.</div>
    <?php else: ?>

        <header class="pu-head">
            <div class="pu-head-main">
                <span class="pu-kicker">Laporan Transparansi · <?= $secNo ?> dari <?= count(M_showtransparansi::SECTIONS) ?> · <?= R::FORM_CODE ?></span>
                <h1 class="pu-title"><?= esc($judul) ?></h1>
                <div class="pu-meta">
                    <span class="pu-bpr"><?= esc($namabpr) ?></span>
                    <span class="pu-year"><i class="far fa-calendar"></i> Tahun <?= esc($periode['tahun']) ?></span>
                </div>
            </div>
            <div class="pu-status">
                <?php foreach ($approvals as [$type, $label, $ok, $at]): ?>
                    <div class="pu-chip <?= $ok ? 'ok' : '' ?>">
                        <i class="fas <?= $ok ? 'fa-check-circle' : 'fa-clock' ?>"></i>
                        <div><b><?= $label ?></b><small><?= $ok ? 'Disetujui' . $fmt($at) : ($row ? 'Menunggu persetujuan' : 'Data belum diisi') ?></small></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </header>

        <form id="pkForm" autocomplete="off" novalidate>
            <input type="hidden" id="puCsrf" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">

            <section class="pu-card">
                <div class="pu-card-head">
                    <h2><i class="fas fa-money-check-alt"></i> Gaji selama 1 Tahun</h2>
                    <div class="pu-card-tools">
                        <?php if ($canEdit): $prevTahun = (int) $periode['tahun'] - 1; ?>
                            <button type="button" class="pu-btn pu-btn-ghost" id="puSync" data-prev="<?= $prevTahun ?>"
                                title="<?= $hasPrev ? "Salin & simpan data tahun {$prevTahun} ke tahun ini" : "Tidak ada data tahun {$prevTahun}" ?>" <?= $hasPrev ? '' : 'disabled' ?>>
                                <i class="fas fa-sync-alt"></i> Sinkronkan dari <?= $prevTahun ?>
                            </button>
                        <?php endif; ?>
                        <?php if ($row): ?>
                            <a class="pu-btn pu-btn-light" href="<?= site_url('rasiogaji/exporttxtrasiogaji') ?>"><i class="fas fa-file-alt"></i> Export TXT</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="pu-card-body">
                    <?php if (!$canEdit): ?>
                        <div class="pu-note"><i class="fas fa-eye"></i> Mode baca — hanya Pengelola (PE) atau Admin yang dapat mengubah data.</div>
                    <?php elseif ($komutOk || $dirutOk): ?>
                        <div class="pu-note warn"><i class="fas fa-info-circle"></i> Data sudah disetujui. Menyimpan perubahan akan me-reset seluruh persetujuan.</div>
                    <?php else: ?>
                        <div class="pu-note"><i class="fas fa-info-circle"></i> Isi gaji tertinggi dan terendah (Rp, selama 1 tahun). Kelima rasio dihitung otomatis.</div>
                    <?php endif; ?>

                    <div class="pk-wrap">
                        <table class="pk-table">
                            <thead><tr><th class="pk-item">Kelompok</th><th>Gaji Tertinggi (Rp)</th><th>Gaji Terendah (Rp)</th></tr></thead>
                            <tbody>
                                <?php foreach ($groups as [$label, $hi, $lo]): ?>
                                    <tr>
                                        <td class="pk-item"><?= esc($label) ?></td>
                                        <?= $input($hi, 'Gaji tertinggi (Rp)') ?><?= $input($lo, 'Gaji terendah (Rp)') ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <h3 class="rg-title"><i class="fas fa-balance-scale"></i> Rasio (tertinggi : terendah)</h3>
                    <div class="rg-grid">
                        <?php foreach (R::RATIOS as $code => [$no, $label, $col, $a, $b]): ?>
                            <div class="rg-card">
                                <div class="rg-label"><span class="pk-no"><?= $no ?>.</span> <?= esc($label) ?></div>
                                <output class="rg-val" data-ratio="<?= $col ?>" data-a="<?= $a ?>" data-b="<?= $b ?>"><?= $ratioText($ratios[$col]) ?></output>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <section class="pu-card">
                <div class="pu-card-head"><h2><i class="fas fa-tasks"></i> Penjelasan Lebih Lanjut</h2></div>
                <div class="pu-card-body">
                    <div class="pu-field" data-f="tindaklanjut">
                        <label for="f_tindak">Penjelasan lebih lanjut mengenai rasio gaji <small class="text-muted">(opsional)</small></label>
                        <?php if ($canEdit): ?>
                            <textarea id="f_tindak" name="tindaklanjut" class="pu-input pu-area" rows="4" style="min-height:100px"
                                maxlength="<?= R::MAX_TINDAK ?>" data-max="<?= R::MAX_TINDAK ?>"><?= esc($tindakVal) ?></textarea>
                            <div class="pu-foot"><span class="pu-err" data-msg="tindaklanjut"></span><span class="pu-count" data-cnt="tindaklanjut"></span></div>
                        <?php else: ?>
                            <div class="pu-read <?= $tindakVal === '' ? 'empty' : '' ?>"><?= $tindakVal === '' ? 'Belum diisi' : esc($tindakVal) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($canEdit): ?>
                    <div class="pu-savebar">
                        <button type="submit" class="pu-btn pu-btn-primary" id="pkSave"><i class="fas fa-save"></i> Simpan <kbd class="d-none d-md-inline">Ctrl+S</kbd></button>
                        <span class="pu-dirty" id="pkDirty"><i class="fas fa-circle"></i> <span><?= $row ? 'Semua perubahan tersimpan' : 'Data belum disimpan' ?></span></span>
                    </div>
                <?php endif; ?>
            </section>
        </form>

        <?php if ($canKomut || $canDirut): ?>
            <section class="pu-approve">
                <?php foreach ($approvals as [$type, $label, $ok, , $by, $allowed]): ?>
                    <?php if (!$allowed) continue; ?>
                    <?php $locked = $type === 'dirut' && !$komutOk && !$ok; $mine = $isAdmin || $by === (int) $userId; ?>
                    <div class="pu-approve-card <?= $ok ? 'ok' : '' ?>">
                        <div class="pu-approve-who"><i class="fas <?= $type === 'komut' ? 'fa-user-tie' : 'fa-user-shield' ?>"></i> <?= $label ?></div>
                        <?php if ($locked): ?>
                            <button type="button" class="pu-btn pu-btn-primary" disabled><i class="fas fa-lock"></i> Setujui</button>
                            <small>Menunggu persetujuan Komisaris Utama</small>
                        <?php elseif (!$ok): ?>
                            <button type="button" class="pu-btn pu-btn-primary pu-act" data-type="<?= $type ?>" data-act="approve"><i class="fas fa-check-double"></i> Setujui</button>
                        <?php elseif ($mine): ?>
                            <button type="button" class="pu-btn pu-btn-danger pu-act" data-type="<?= $type ?>" data-act="cancel"><i class="fas fa-ban"></i> Batalkan</button>
                            <?php if ($type === 'komut' && $dirutOk): ?><small>Membatalkan juga persetujuan Direktur Utama</small><?php endif; ?>
                        <?php else: ?>
                            <small><i class="fas fa-check"></i> Sudah disetujui</small>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <nav class="pu-nav" aria-label="Halaman Laporan Transparansi">
            <?php foreach (M_showtransparansi::SECTIONS as $i => $s): $cur = $s['controller'] === $PAGE; ?>
                <a href="<?= base_url($s['controller']) ?>" title="<?= esc($s['name'], 'attr') ?>" class="<?= $cur ? 'active' : '' ?>" <?= $cur ? 'aria-current="page"' : '' ?>><?= $i + 1 ?></a>
            <?php endforeach; ?>
            <a href="<?= base_url('ShowTransparansi') ?>" class="all">Semua</a>
        </nav>
    <?php endif; ?>
</div>

<?php if ($periode): ?>
    <script>
        (function () {
            'use strict';
            if (!window.fetch) return;
            var form = document.getElementById('pkForm'), csrf = document.getElementById('puCsrf');
            var BASE = <?= json_encode(rtrim(site_url('rasiogaji'), '/')) ?>;
            var CAN_EDIT = <?= $canEdit ? 'true' : 'false' ?>;
            var leaving = false, $ = function (id) { return document.getElementById(id); };

            function toast(msg, err) {
                var d = document.createElement('div'); if (err) d.className = 'e'; d.textContent = msg;
                $('pu-toast').appendChild(d); setTimeout(function () { d.remove(); }, 3800);
            }
            function post(path, fd) {
                fd.set(csrf.name, csrf.value);
                return fetch(BASE + '/' + path, { method: 'POST', body: fd, credentials: 'same-origin', headers: { Accept: 'application/json' } })
                    .then(function (r) { return r.json().catch(function () { return { ok: false, message: 'Respons server tidak valid (' + r.status + ').' }; }).then(function (j) { j._s = r.status; return j; }); })
                    .then(function (j) {
                        if (j.csrf) { csrf.name = j.csrf.name; csrf.value = j.csrf.hash; }
                        if (j._s === 401) setTimeout(function () { location.href = '<?= base_url('login') ?>'; }, 1500);
                        return j;
                    });
            }
            function busyBtn(btn, text) { var o = btn.innerHTML; btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + text; return function () { btn.disabled = false; btn.innerHTML = o; }; }
            function reloadSoon() { leaving = true; setTimeout(function () { location.reload(); }, 700); }
            function confirmPost(btn, path, data, q) {
                if (!confirm(q)) return;
                var done = busyBtn(btn, ''), fd = new FormData();
                Object.keys(data).forEach(function (k) { fd.set(k, data[k]); });
                post(path, fd).then(function (j) { toast(j.message || 'Selesai.', !j.ok); j.ok && j.reload !== false ? reloadSoon() : done(); })
                    .catch(function () { toast('Koneksi bermasalah.', true); done(); });
            }

            document.querySelectorAll('.pu-act').forEach(function (b) {
                b.addEventListener('click', function () {
                    confirmPost(b, 'approve', { type: b.dataset.type, act: b.dataset.act },
                        b.dataset.act === 'cancel' ? 'Batalkan persetujuan ini?' : 'Setujui data Rasio Gaji ini?');
                });
            });

            if (!CAN_EDIT) return;

            // ---------------- angka & rasio otomatis
            var rp = function (n) { return (n || 0).toLocaleString('id-ID'); };
            var num = function (name) { var el = form.elements[name]; return el ? parseInt(String(el.value).replace(/\D/g, ''), 10) || 0 : 0; };
            function recalc() {
                form.querySelectorAll('[data-ratio]').forEach(function (o) {
                    var a = num(o.dataset.a), b = num(o.dataset.b);
                    o.textContent = b > 0 && a > 0 ? (Math.round(a / b * 100) / 100).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' : 1' : '—';
                });
            }
            function count(el) {
                var c = form.querySelector('[data-cnt="' + el.name + '"]'); if (!c) return;
                var max = +el.dataset.max, len = el.value.length;
                c.textContent = len + ' / ' + max; c.className = 'pu-count' + (len >= max ? ' max' : len >= max * 0.9 ? ' warn' : '');
            }
            form.querySelectorAll('[data-max]').forEach(count);

            // ---------------- status perubahan
            var dirtyEl = $('pkDirty');
            function ser() { var fd = new FormData(form); fd.delete(csrf.name); return new URLSearchParams(fd).toString(); }
            var snap = ser();
            <?php if (!$row): ?>dirtyEl.classList.add('on');<?php endif; ?>
            function setDirty() {
                var d = ser() !== snap;
                dirtyEl.classList.toggle('on', d);
                dirtyEl.lastElementChild.textContent = d ? 'Ada perubahan belum disimpan' : 'Semua perubahan tersimpan';
            }
            var t;
            form.addEventListener('input', function (e) {
                var el = e.target;
                if (el.classList.contains('pk-num')) {
                    var pos = el.value.length - el.selectionStart;
                    var d = el.value.replace(/\D/g, '').replace(/^0+/, '').slice(0, 15);
                    el.value = d ? rp(parseInt(d, 10)) : '';
                    try { var p = Math.max(0, el.value.length - pos); el.setSelectionRange(p, p); } catch (x) { }
                    recalc();
                } else if (el.dataset.max) count(el);
                var w = el.closest('td,.pu-field'); if (w) { var m = w.querySelector('[data-msg]'); if (m) m.textContent = ''; el.classList.remove('is-err'); }
                clearTimeout(t); t = setTimeout(setDirty, 200);
            });
            window.addEventListener('beforeunload', function (e) { if (!leaving && ser() !== snap) { e.preventDefault(); e.returnValue = ''; } });

            // ---------------- simpan
            var busy = false;
            function save() {
                if (busy) return; busy = true;
                var done = busyBtn($('pkSave'), 'Menyimpan…');
                form.querySelectorAll('[data-msg]').forEach(function (n) { n.textContent = ''; });
                form.querySelectorAll('.is-err').forEach(function (n) { n.classList.remove('is-err'); });
                post('save', new FormData(form)).then(function (j) {
                    toast(j.message || 'Selesai.', !j.ok);
                    if (j.errors) {
                        var first = null;
                        Object.keys(j.errors).forEach(function (k) {
                            var m = form.querySelector('[data-msg="' + k + '"]'), el = form.elements[k];
                            if (m) m.textContent = j.errors[k];
                            if (el) { el.classList.add('is-err'); first = first || el; }
                        });
                        if (first) first.focus();
                    }
                    if (j.ok) { snap = ser(); setDirty(); if (j.reload) reloadSoon(); }
                }).catch(function () { toast('Koneksi bermasalah, data belum tersimpan.', true); })
                    .then(function () { busy = false; done(); });
            }
            form.addEventListener('submit', function (e) { e.preventDefault(); save(); });
            document.addEventListener('keydown', function (e) { if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) { e.preventDefault(); save(); } });

            // ---------------- sinkronisasi
            var sync = $('puSync');
            if (sync && !sync.disabled) sync.addEventListener('click', function () {
                confirmPost(sync, 'syncFromPrevPeriode', {}, 'Sinkronkan data tahun ' + sync.dataset.prev + ' ke tahun ini?\n\n'
                    + 'Seluruh isian tahun ini akan ditimpa dan langsung tersimpan. Status persetujuan akan di-reset.');
            });
        })();
    </script>
    <?= view('templates/komentar', ['komentarPage' => $PAGE]) ?>
<?php endif; ?>
