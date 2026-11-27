<?php

use App\Models\M_showtransparansi;

$val = static fn(string $k): string => esc((string) ($form[$k] ?? ''));
$dis = $canEdit ? '' : 'disabled';

$komutOk = (int) ($row['accdekom'] ?? 0) === 1;
$dirutOk = (int) ($row['is_approved'] ?? 0) === 1;
$fmt = static fn($at): string => $at ? ' · ' . esc(date('d/m/Y H:i', strtotime($at))) : '';

$approvals = [
    ['komut', 'Komisaris Utama', $komutOk, $row['accdekom_at'] ?? null, (int) ($row['accdekom_by'] ?? 0), $row['komut'] ?? '', $canKomut],
    ['dirut', 'Direktur Utama', $dirutOk, $row['approved_at'] ?? null, (int) ($row['approved_by'] ?? 0), $row['dirut'] ?? '', $canDirut],
];

$fields = [
    ['namabpr', 'Nama BPR', 'text', 'col-md-7'],
    ['nomor', 'Nomor Telepon', 'text', 'col-md-5'],
    ['alamat', 'Alamat BPR', 'text', 'col-12'],
    ['penjelasan', 'Penjelasan Umum Penerapan Tata Kelola', 'textarea', 'col-12'],
    ['peringkatkomposit', 'Peringkat Komposit Penerapan Tata Kelola', 'select', 'col-md-6'],
    ['penjelasankomposit', 'Penjelasan Peringkat Komposit', 'textarea', 'col-12'],
];
?>
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

        <!-- Header -->
        <header class="pu-head">
            <div class="pu-head-main">
                <span class="pu-kicker">Laporan Transparansi · 1 dari <?= count(M_showtransparansi::SECTIONS) ?> · <?= \App\Models\M_penjelasanumum::FORM_CODE ?></span>
                <h1 class="pu-title"><?= esc($judul) ?></h1>
                <div class="pu-meta">
                    <span class="pu-bpr"><?= esc($namabpr) ?></span>
                    <span class="pu-year"><i class="far fa-calendar"></i> Tahun <?= esc($periode['tahun']) ?></span>
                </div>
            </div>
            <div class="pu-status">
                <?php foreach ($approvals as [$type, $label, $ok, $at, , $nama]): ?>
                    <div class="pu-chip <?= $ok ? 'ok' : '' ?>">
                        <i class="fas <?= $ok ? 'fa-check-circle' : 'fa-clock' ?>"></i>
                        <div>
                            <b><?= $label ?></b>
                            <small><?= $ok ? 'Disetujui' . ($nama ? ' oleh ' . esc($nama) : '') . $fmt($at) : ($row ? 'Menunggu persetujuan' : 'Data belum diisi') ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </header>

        <!-- Form -->
        <section class="pu-card">
            <div class="pu-card-head">
                <h2><i class="fas fa-file-signature"></i> Data Penjelasan Umum</h2>
                <div class="pu-card-tools">
                    <?php if ($canEdit): $prevTahun = (int) $periode['tahun'] - 1; ?>
                        <button type="button" class="pu-btn pu-btn-ghost" id="puSync" data-prev="<?= $prevTahun ?>"
                            title="<?= $hasPrev ? "Salin & simpan data tahun {$prevTahun} ke tahun ini" : "Tidak ada data tahun {$prevTahun}" ?>"
                            <?= $hasPrev ? '' : 'disabled' ?>>
                            <i class="fas fa-sync-alt"></i> Sinkronkan dari <?= $prevTahun ?>
                        </button>
                    <?php endif; ?>
                    <?php if ($row): ?>
                        <a class="pu-btn pu-btn-light" href="<?= site_url('penjelasanumum/exporttxtpenjelasanumum') ?>">
                            <i class="fas fa-file-alt"></i> Export TXT
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="pu-card-body">
                <?php if (!$canEdit): ?>
                    <div class="pu-note"><i class="fas fa-eye"></i> Mode baca — hanya Pengelola (PE) atau Admin yang dapat mengubah data.</div>
                <?php elseif ($komutOk || $dirutOk): ?>
                    <div class="pu-note warn"><i class="fas fa-info-circle"></i> Data sudah disetujui. Menyimpan perubahan akan me-reset seluruh persetujuan.</div>
                <?php elseif (!$row): ?>
                    <div class="pu-note"><i class="fas fa-magic"></i> Nama, alamat, dan telepon diisi otomatis dari Info BPR — periksa kembali sebelum menyimpan.</div>
                <?php endif; ?>

                <form id="puForm" class="row" autocomplete="off" novalidate>
                    <input type="hidden" id="puCsrf" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
                    <?php foreach ($fields as [$name, $label, $type, $col]): ?>
                        <div class="<?= $col ?> pu-field" data-f="<?= $name ?>">
                            <label for="f_<?= $name ?>"><?= $label ?> <span class="text-danger">*</span></label>
                            <?php if ($type === 'select'): ?>
                                <select id="f_<?= $name ?>" name="<?= $name ?>" class="pu-input" <?= $dis ?>>
                                    <option value="">— Pilih peringkat —</option>
                                    <?php foreach ($peringkat as $v => $t): ?>
                                        <option value="<?= $v ?>" <?= (string) ($form[$name] ?? '') === (string) $v ? 'selected' : '' ?>><?= esc($t) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php elseif ($type === 'textarea'): ?>
                                <textarea id="f_<?= $name ?>" name="<?= $name ?>" class="pu-input pu-area" rows="7"
                                    maxlength="<?= $max[$name] ?>" data-max="<?= $max[$name] ?>" <?= $dis ?>><?= $val($name) ?></textarea>
                            <?php else: ?>
                                <input type="text" id="f_<?= $name ?>" name="<?= $name ?>" class="pu-input" value="<?= $val($name) ?>"
                                    maxlength="<?= $max[$name] ?>" data-max="<?= $max[$name] ?>" <?= $dis ?>>
                            <?php endif; ?>
                            <div class="pu-foot">
                                <span class="pu-err" data-msg="<?= $name ?>"></span>
                                <?php if ($type !== 'select' && $canEdit): ?>
                                    <span class="pu-count" data-cnt="<?= $name ?>"></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </form>
            </div>

            <?php if ($canEdit): ?>
                <div class="pu-savebar">
                    <button type="submit" form="puForm" class="pu-btn pu-btn-primary" id="puSave">
                        <i class="fas fa-save"></i> Simpan <kbd class="d-none d-md-inline">Ctrl+S</kbd>
                    </button>
                    <span class="pu-dirty" id="puDirty"><i class="fas fa-circle"></i> <span>Belum ada perubahan</span></span>
                </div>
            <?php endif; ?>
        </section>

        <!-- Approval -->
        <?php if ($canKomut || $canDirut): ?>
            <section class="pu-approve">
                <?php foreach ($approvals as [$type, $label, $ok, , $by, , $allowed]): ?>
                    <?php if (!$allowed) continue; ?>
                    <?php
                    $locked = $type === 'dirut' && !$komutOk && !$ok;
                    $mine = $isAdmin || $by === (int) $userId;
                    ?>
                    <div class="pu-approve-card <?= $ok ? 'ok' : '' ?>">
                        <div class="pu-approve-who"><i class="fas <?= $type === 'komut' ? 'fa-user-tie' : 'fa-user-shield' ?>"></i> <?= $label ?></div>
                        <?php if ($locked): ?>
                            <button type="button" class="pu-btn pu-btn-primary" disabled><i class="fas fa-lock"></i> Setujui</button>
                            <small>Menunggu persetujuan Komisaris Utama</small>
                        <?php elseif (!$ok): ?>
                            <button type="button" class="pu-btn pu-btn-primary pu-act" data-type="<?= $type ?>" data-act="approve">
                                <i class="fas fa-check-double"></i> Setujui</button>
                        <?php elseif ($mine): ?>
                            <button type="button" class="pu-btn pu-btn-danger pu-act" data-type="<?= $type ?>" data-act="cancel">
                                <i class="fas fa-ban"></i> Batalkan</button>
                            <?php if ($type === 'komut' && $dirutOk): ?><small>Membatalkan juga persetujuan Direktur Utama</small><?php endif; ?>
                        <?php else: ?>
                            <small><i class="fas fa-check"></i> Sudah disetujui</small>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <!-- Navigasi halaman transparansi -->
        <nav class="pu-nav" aria-label="Halaman Laporan Transparansi">
            <?php foreach (M_showtransparansi::SECTIONS as $i => $s): ?>
                <a href="<?= base_url($s['controller']) ?>" title="<?= esc($s['name'], 'attr') ?>"
                    class="<?= $s['controller'] === 'Penjelasanumum' ? 'active' : '' ?>"
                    <?= $s['controller'] === 'Penjelasanumum' ? 'aria-current="page"' : '' ?>><?= $i + 1 ?></a>
            <?php endforeach; ?>
            <a href="<?= base_url('ShowTransparansi') ?>" class="all">Semua</a>
        </nav>

    <?php endif; ?>
</div>

<link rel="stylesheet" href="<?= base_url('asset/css/transparansi.css') ?>?v=<?= @filemtime(FCPATH . 'asset/css/transparansi.css') ?>">

<?php if ($periode): ?>
    <script>
        (function () {
            'use strict';
            var form = document.getElementById('puForm');
            if (!form || !window.fetch) return;
            var csrf = document.getElementById('puCsrf');
            var BASE = '<?= rtrim(site_url('penjelasanumum'), '/') ?>';
            var CAN_EDIT = <?= $canEdit ? 'true' : 'false' ?>;

            function toast(msg, err) {
                var d = document.createElement('div');
                if (err) d.className = 'e';
                d.textContent = msg;
                document.getElementById('pu-toast').appendChild(d);
                setTimeout(function () { d.remove(); }, 3800);
            }

            function post(path, fd) {
                fd.set(csrf.name, csrf.value);
                return fetch(BASE + '/' + path, { method: 'POST', body: fd, credentials: 'same-origin', headers: { Accept: 'application/json' } })
                    .then(function (r) {
                        return r.json().catch(function () { return { ok: false, message: 'Respons server tidak valid (' + r.status + ').' }; })
                            .then(function (j) { j._s = r.status; return j; });
                    })
                    .then(function (j) {
                        if (j.csrf) { csrf.name = j.csrf.name; csrf.value = j.csrf.hash; }
                        if (j._s === 401) setTimeout(function () { location.href = '<?= base_url('login') ?>'; }, 1500);
                        return j;
                    });
            }

            // approval
            document.querySelectorAll('.pu-act').forEach(function (b) {
                b.addEventListener('click', function () {
                    var cancel = b.dataset.act === 'cancel';
                    if (!confirm(cancel ? 'Batalkan persetujuan ini?' : 'Setujui data Penjelasan Umum ini?')) return;
                    b.disabled = true;
                    var fd = new FormData();
                    fd.set('type', b.dataset.type);
                    fd.set('act', b.dataset.act);
                    post('approve', fd).then(function (j) {
                        toast(j.message || 'Selesai.', !j.ok);
                        if (j.ok) setTimeout(function () { location.reload(); }, 600); else b.disabled = false;
                    }).catch(function () { toast('Koneksi bermasalah.', true); b.disabled = false; });
                });
            });

            if (!CAN_EDIT) return;

            // penghitung karakter + status perubahan
            var saveBtn = document.getElementById('puSave');
            var dirtyEl = document.getElementById('puDirty');
            var snapshot = '';
            function serialize() {
                var fd = new FormData(form);
                fd.delete(csrf.name);
                return new URLSearchParams(fd).toString();
            }
            function setDirty() {
                var dirty = serialize() !== snapshot;
                dirtyEl.classList.toggle('on', dirty);
                dirtyEl.lastElementChild.textContent = dirty ? 'Ada perubahan belum disimpan' : 'Semua perubahan tersimpan';
                return dirty;
            }
            function count(el) {
                var c = form.querySelector('[data-cnt="' + el.name + '"]');
                if (!c) return;
                var max = +el.dataset.max, len = el.value.length;
                c.textContent = len + ' / ' + max;
                c.className = 'pu-count' + (len >= max ? ' max' : len >= max * 0.9 ? ' warn' : '');
            }
            form.querySelectorAll('[data-max]').forEach(count);
            snapshot = serialize();
            dirtyEl.lastElementChild.textContent = <?= $row ? "'Semua perubahan tersimpan'" : "'Data belum disimpan'" ?>;
            <?php if (!$row): ?>dirtyEl.classList.add('on');<?php endif; ?>

            var dirtyTimer;
            form.addEventListener('input', function (e) {
                if (e.target.dataset.max) count(e.target);
                var w = e.target.closest('.pu-field');
                if (w && w.classList.contains('err')) { w.classList.remove('err'); w.querySelector('[data-msg]').textContent = ''; }
                clearTimeout(dirtyTimer);
                dirtyTimer = setTimeout(setDirty, 200);
            });
            form.addEventListener('change', setDirty);
            var leaving = false;
            window.addEventListener('beforeunload', function (e) {
                if (!leaving && serialize() !== snapshot) { e.preventDefault(); e.returnValue = ''; }
            });

            // simpan
            var busy = false;
            function save() {
                if (busy) return;
                busy = true;
                var ori = saveBtn.innerHTML;
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan…';
                form.querySelectorAll('.pu-field.err').forEach(function (n) { n.classList.remove('err'); n.querySelector('[data-msg]').textContent = ''; });

                post('save', new FormData(form)).then(function (j) {
                    toast(j.message || 'Selesai.', !j.ok);
                    if (j.errors) {
                        var first = null;
                        Object.keys(j.errors).forEach(function (k) {
                            var w = form.querySelector('[data-f="' + k + '"]');
                            if (!w) return;
                            w.classList.add('err');
                            w.querySelector('[data-msg]').textContent = j.errors[k];
                            first = first || w.querySelector('.pu-input');
                        });
                        if (first) first.focus();
                    }
                    if (j.ok) {
                        snapshot = serialize();
                        setDirty();
                        if (j.reload) setTimeout(function () { location.reload(); }, 700);
                    }
                }).catch(function () {
                    toast('Koneksi bermasalah, data belum tersimpan.', true);
                }).then(function () { busy = false; saveBtn.disabled = false; saveBtn.innerHTML = ori; });
            }
            form.addEventListener('submit', function (e) { e.preventDefault(); save(); });
            document.addEventListener('keydown', function (e) {
                if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) { e.preventDefault(); save(); }
            });

            var syncBtn = document.getElementById('puSync');
            if (syncBtn && !syncBtn.disabled) {
                syncBtn.addEventListener('click', function () {
                    var yr = syncBtn.dataset.prev;
                    if (!confirm('Sinkronkan data Penjelasan Umum tahun ' + yr + ' ke tahun ini?\n\n'
                        + 'Data tahun ini akan ditimpa dan langsung tersimpan. Status persetujuan akan di-reset.')) return;
                    var ori = syncBtn.innerHTML;
                    syncBtn.disabled = true;
                    syncBtn.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> Menyinkronkan…';
                    post('syncFromPrevPeriode', new FormData()).then(function (j) {
                        toast(j.message || 'Selesai.', !j.ok);
                        if (j.ok && j.reload) {
                            leaving = true;
                            setTimeout(function () { location.reload(); }, 800);
                            return;
                        }
                        syncBtn.disabled = false; syncBtn.innerHTML = ori;
                    }).catch(function () {
                        toast('Koneksi bermasalah, sinkronisasi gagal.', true);
                        syncBtn.disabled = false; syncBtn.innerHTML = ori;
                    });
                });
            }
        })();
    </script>
    <?= view('templates/komentar', ['komentarPage' => 'Penjelasanumum']) ?>
<?php endif; ?>
