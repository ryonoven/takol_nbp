<?php
/**
 * Tampilan bersama Laporan Transparansi berbentuk daftar (Tgjwbdir, Tgjwbdekom, Tgjwbkomite, ...).
 * Dirender oleh App\Libraries\TransparansiAnggotaController; field & kartu mengikuti $cfg['fields'].
 * Variabel: $cfg, $periode, $rows, $nodata, $approval, $tindak, $namabpr, $canEdit, $canKomut, $canDirut, $isAdmin, $userId, $hasPrev
 */
use App\Models\M_showtransparansi;

$PAGE = $cfg['page'];
$entity = $cfg['entity'];
$komutOk = $approval['komut'];
$dirutOk = $approval['dirut'];
$fmt = static fn($at): string => $at ? ' · ' . esc(date('d/m/Y H:i', strtotime($at))) : '';
$hasRows = !empty($rows);
$filled = $hasRows || $nodata;
$tindakFields = array_filter([['tindaklanjut', $cfg['tindak'][0]], ['penjelasanlanjut', $cfg['tindak'][1] ?? null]], static fn($t) => $t[1] !== null);

// [type, label, disetujui, waktu, oleh, nama, boleh aksi]
$approvals = [
    ['komut', 'Komisaris Utama', $komutOk, $approval['komut_at'], $approval['komut_by'], $approval['komut_nama'], $canKomut],
    ['dirut', 'Direktur Utama', $dirutOk, $approval['dirut_at'], $approval['dirut_by'], $approval['dirut_nama'], $canDirut],
];
$secNo = array_search($PAGE, array_column(M_showtransparansi::SECTIONS, 'controller'), true) + 1;
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

        <!-- Header -->
        <header class="pu-head">
            <div class="pu-head-main">
                <span class="pu-kicker">Laporan Transparansi · <?= $secNo ?> dari <?= count(M_showtransparansi::SECTIONS) ?><?= $cfg['form'] !== '' ? ' · ' . esc($cfg['form']) : '' ?></span>
                <h1 class="pu-title"><?= esc($cfg['judul']) ?></h1>
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
                            <small><?= $ok ? 'Disetujui' . ($nama ? ' oleh ' . esc($nama) : '') . $fmt($at) : ($filled ? 'Menunggu persetujuan' : 'Data belum diisi') ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </header>

        <!-- Daftar -->
        <section class="pu-card">
            <div class="pu-card-head">
                <h2><i class="fas <?= esc($cfg['icon']) ?>"></i> <?= esc($cfg['listTitle']) ?> <span class="pu-count-badge" id="tdCount"><?= count($rows) ?></span></h2>
                <div class="pu-card-tools">
                    <?php if ($canEdit && !$nodata): $prevTahun = (int) $periode['tahun'] - 1; ?>
                        <button type="button" class="pu-btn pu-btn-ghost" id="puSync" data-prev="<?= $prevTahun ?>"
                            title="<?= $hasPrev ? "Salin & simpan data tahun {$prevTahun} ke tahun ini" : "Tidak ada data tahun {$prevTahun}" ?>"
                            <?= $hasPrev ? '' : 'disabled' ?>>
                            <i class="fas fa-sync-alt"></i> Sinkronkan dari <?= $prevTahun ?>
                        </button>
                        <?php if ($cfg['nodataEnabled'] && !$hasRows): ?>
                            <button type="button" class="pu-btn pu-btn-light pu-nodata" data-act="set"><i class="fas fa-ban"></i> Tidak Ada Data</button>
                        <?php endif; ?>
                        <button type="button" class="pu-btn pu-btn-primary" id="tdAdd"><i class="fas fa-plus"></i> Tambah <?= esc($entity) ?></button>
                    <?php endif; ?>
                    <?php if ($filled && $cfg['export']): ?>
                        <a class="pu-btn pu-btn-light" href="<?= $cfg['base'] . '/' . $cfg['export'] ?>"><i class="fas fa-file-alt"></i> Export TXT</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="pu-card-body">
                <?php if (!$canEdit): ?>
                    <div class="pu-note"><i class="fas fa-eye"></i> Mode baca — hanya Pengelola (PE) atau Admin yang dapat mengubah data.</div>
                <?php elseif ($approval['any']): ?>
                    <div class="pu-note warn"><i class="fas fa-info-circle"></i> Data sudah disetujui. Perubahan data akan me-reset seluruh persetujuan.</div>
                <?php elseif (!$filled && !empty($cfg['nodataNote'])): ?>
                    <div class="pu-note warn"><i class="fas fa-exclamation-triangle"></i> <?= esc($cfg['nodataNote']) ?></div>
                <?php endif; ?>

                <?php if ($nodata): ?>
                    <div class="pu-empty">
                        <i class="fas fa-ban"></i><b>Ditandai "Tidak Ada Data"</b> untuk periode ini.
                        <?php if ($canEdit): ?>
                            <div class="mt-3"><button type="button" class="pu-btn pu-btn-danger pu-nodata" data-act="cancel"><i class="fas fa-undo"></i> Batalkan "Tidak Ada Data"</button></div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="pu-list" id="tdList" aria-live="polite"></div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Tindak lanjut (F01 / F02) -->
        <section class="pu-card">
            <div class="pu-card-head">
                <h2><i class="fas fa-tasks"></i> Tindak Lanjut<?= count($tindakFields) > 1 ? ' &amp; Penjelasan' : '' ?></h2>
            </div>
            <form id="tlForm" class="pu-card-body" autocomplete="off" novalidate>
                <?php foreach ($tindakFields as [$name, $label]): $v = (string) ($tindak[$name] ?? ''); ?>
                    <div class="pu-field" data-f="<?= $name ?>">
                        <label for="tl_<?= $name ?>"><?= esc($label) ?> <small class="text-muted">(opsional)</small></label>
                        <?php if ($canEdit): ?>
                            <textarea id="tl_<?= $name ?>" name="<?= $name ?>" class="pu-input pu-area" rows="4" style="min-height:100px"
                                maxlength="<?= $cfg['maxTindak'] ?>" data-max="<?= $cfg['maxTindak'] ?>"><?= esc($v) ?></textarea>
                            <div class="pu-foot"><span class="pu-err" data-msg="<?= $name ?>"></span><span class="pu-count" data-cnt="<?= $name ?>"></span></div>
                        <?php else: ?>
                            <div class="pu-read <?= $v === '' ? 'empty' : '' ?>"><?= $v === '' ? 'Belum diisi' : esc($v) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </form>
            <?php if ($canEdit): ?>
                <div class="pu-savebar">
                    <button type="submit" form="tlForm" class="pu-btn pu-btn-primary" id="tlSave"><i class="fas fa-save"></i> Simpan Tindak Lanjut</button>
                    <span class="pu-dirty" id="tlDirty"><i class="fas fa-circle"></i> <span>Semua perubahan tersimpan</span></span>
                </div>
            <?php endif; ?>
        </section>

        <!-- Approval -->
        <?php if ($canKomut || $canDirut): ?>
            <section class="pu-approve">
                <?php foreach ($approvals as [$type, $label, $ok, , $by, , $allowed]): ?>
                    <?php if (!$allowed) continue; ?>
                    <?php $locked = $type === 'dirut' && !$komutOk && !$ok; $mine = $isAdmin || $by === (int) $userId; ?>
                    <div class="pu-approve-card <?= $ok ? 'ok' : '' ?>">
                        <div class="pu-approve-who"><i class="fas <?= $type === 'komut' ? 'fa-user-tie' : 'fa-user-shield' ?>"></i> <?= $label ?></div>
                        <?php if ($locked): ?>
                            <button type="button" class="pu-btn pu-btn-primary" disabled><i class="fas fa-lock"></i> Setujui</button>
                            <small>Menunggu persetujuan Komisaris Utama</small>
                        <?php elseif (!$ok): ?>
                            <button type="button" class="pu-btn pu-btn-primary pu-act" data-type="<?= $type ?>" data-act="approve"><i class="fas fa-check-double"></i> Setujui Semua</button>
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

        <!-- Navigasi halaman transparansi -->
        <nav class="pu-nav" aria-label="Halaman Laporan Transparansi">
            <?php foreach (M_showtransparansi::SECTIONS as $i => $s): $cur = $s['controller'] === $PAGE; ?>
                <a href="<?= base_url($s['controller']) ?>" title="<?= esc($s['name'], 'attr') ?>" class="<?= $cur ? 'active' : '' ?>"
                    <?= $cur ? 'aria-current="page"' : '' ?>><?= $i + 1 ?></a>
            <?php endforeach; ?>
            <a href="<?= base_url('ShowTransparansi') ?>" class="all">Semua</a>
        </nav>

        <?php if ($canEdit && !$nodata): ?>
            <!-- Dialog tambah / ubah -->
            <dialog class="pu-dialog" id="tdDialog" aria-labelledby="tdDialogTitle">
                <form id="tdForm" autocomplete="off" novalidate>
                    <div class="pu-dialog-head">
                        <h3 id="tdDialogTitle">Tambah <?= esc($entity) ?></h3>
                        <button type="button" class="pu-dialog-x" data-close aria-label="Tutup"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="pu-dialog-body row">
                        <input type="hidden" name="id" value="">
                        <?php foreach ($cfg['fields'] as $k => $f): $id = 'td_' . $k; $col = 'col-md-' . ($f['col_md'] ?? 12); ?>
                            <?php if (!empty($f['section'])): ?><div class="col-12 pu-section"><?= esc($f['section']) ?></div><?php endif; ?>
                            <div class="<?= $col ?> pu-field" data-f="<?= $k ?>"<?= isset($f['showIf']) ? ' data-showif="' . esc($f['showIf'][0], 'attr') . '" data-showval="' . esc($f['showIf'][1], 'attr') . '" hidden' : '' ?>>
                                <label for="<?= $id ?>"><?= esc($f['label']) ?> <span class="text-danger">*</span></label>
                                <?php if ($f['type'] === 'select'): ?>
                                    <select id="<?= $id ?>" name="<?= $k ?>" class="pu-input" required>
                                        <option value="">— Pilih <?= esc(strtolower($f['label'])) ?> —</option>
                                        <?php foreach ($f['options'] as $ov => $ol): ?>
                                            <option value="<?= esc($ov) ?>"><?= esc($ol) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php elseif ($f['type'] === 'textarea'): ?>
                                    <textarea id="<?= $id ?>" name="<?= $k ?>" class="pu-input pu-area" rows="5" maxlength="<?= $f['max'] ?>" data-max="<?= $f['max'] ?>" required></textarea>
                                <?php elseif ($f['type'] === 'percent'): ?>
                                    <input type="number" id="<?= $id ?>" name="<?= $k ?>" class="pu-input" min="0" max="100" step="0.01" inputmode="decimal" placeholder="0 – 100" required>
                                <?php elseif ($f['type'] === 'rupiah'): ?>
                                    <input type="text" id="<?= $id ?>" name="<?= $k ?>" class="pu-input" data-rp inputmode="numeric" placeholder="Rp 0" style="text-align:right" required>
                                <?php elseif ($f['type'] === 'date'): ?>
                                    <input type="date" id="<?= $id ?>" name="<?= $k ?>" class="pu-input" required>
                                <?php elseif ($f['type'] === 'number'): ?>
                                    <input type="number" id="<?= $id ?>" name="<?= $k ?>" class="pu-input" min="<?= (int) ($f['min'] ?? 0) ?>" max="<?= $f['max'] ?>" step="1" inputmode="numeric" required>
                                <?php elseif ($f['type'] === 'idnum'): ?>
                                    <input type="text" id="<?= $id ?>" name="<?= $k ?>" class="pu-input" data-nik data-idnum="<?= esc($f['typeField'], 'attr') ?>" inputmode="numeric" maxlength="<?= max($f['lengths']) ?>" data-max="<?= max($f['lengths']) ?>" required>
                                <?php elseif ($f['type'] === 'nik'): ?>
                                    <input type="text" id="<?= $id ?>" name="<?= $k ?>" class="pu-input" data-nik inputmode="numeric" maxlength="16" data-max="16" pattern="\d{16}" placeholder="16 digit" required>
                                <?php else: ?>
                                    <input type="text" id="<?= $id ?>" name="<?= $k ?>" class="pu-input" maxlength="<?= $f['max'] ?>" required>
                                <?php endif; ?>
                                <div class="pu-foot"><span class="pu-err" data-msg="<?= $k ?>"></span><?php if (in_array($f['type'], ['textarea', 'nik', 'idnum'], true)): ?><span class="pu-count" data-cnt="<?= $k ?>"></span><?php endif; ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="pu-dialog-foot">
                        <button type="button" class="pu-btn pu-btn-light" data-close>Batal</button>
                        <button type="submit" class="pu-btn pu-btn-primary" id="tdSave"><i class="fas fa-save"></i> Simpan</button>
                    </div>
                </form>
            </dialog>
        <?php endif; ?>

    <?php endif; ?>
</div>

<?php if ($periode): ?>
    <input type="hidden" id="puCsrf" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
    <script>
        (function () {
            'use strict';
            if (!window.fetch) return;
            var csrf = document.getElementById('puCsrf');
            var C = <?= json_encode([
                'base' => $cfg['base'], 'entity' => $entity, 'fields' => $cfg['fields'], 'title' => $cfg['titleField'],
                'tag' => $cfg['tagField'], 'sub' => $cfg['subField'], 'subFormat' => $cfg['subFormat'],
                'body' => $cfg['bodyFields'], 'utama' => array_values($cfg['utama']),
                'chips' => $cfg['chipFields'], 'chipSkip' => $cfg['chipSkip'], 'lines' => $cfg['extraLines'],
            ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
            var CAN_EDIT = <?= $canEdit ? 'true' : 'false' ?>;
            var rows = <?= json_encode($rows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
            var leaving = false;
            var $ = function (id) { return document.getElementById(id); };

            function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
            function toast(msg, err) {
                var d = document.createElement('div');
                if (err) d.className = 'e';
                d.textContent = msg;
                $('pu-toast').appendChild(d);
                setTimeout(function () { d.remove(); }, 3800);
            }
            function post(path, fd) {
                fd.set(csrf.name, csrf.value);
                return fetch(C.base + '/' + path, { method: 'POST', body: fd, credentials: 'same-origin', headers: { Accept: 'application/json' } })
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
            function reloadSoon() { leaving = true; setTimeout(function () { location.reload(); }, 700); }
            function count(form, el) {
                var c = form.querySelector('[data-cnt="' + el.name + '"]');
                if (!c) return;
                var max = +(el.dataset.max || el.maxLength), len = el.value.length;
                c.textContent = len + ' / ' + max;
                // NIK: tepat 16 digit = benar (tidak diberi warna peringatan)
                c.className = 'pu-count' + (el.hasAttribute('data-nik') ? '' : len >= max ? ' max' : len >= max * 0.9 ? ' warn' : '');
            }
            function clearErr(form) {
                form.querySelectorAll('.pu-field.err').forEach(function (n) { n.classList.remove('err'); });
                form.querySelectorAll('[data-msg]').forEach(function (n) { n.textContent = ''; });
            }
            function showErr(form, errors) {
                var first = null;
                Object.keys(errors || {}).forEach(function (k) {
                    var w = form.querySelector('[data-f="' + k + '"]');
                    if (!w) return;
                    w.classList.add('err');
                    w.querySelector('[data-msg]').textContent = errors[k];
                    first = first || w.querySelector('.pu-input');
                });
                if (first) first.focus();
            }
            function busyBtn(btn, text) {
                var ori = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + text;
                return function () { btn.disabled = false; btn.innerHTML = ori; };
            }
            function confirmPost(btn, path, data, question) {
                if (!confirm(question)) return;
                var done = busyBtn(btn, '');
                var fd = new FormData();
                Object.keys(data).forEach(function (k) { fd.set(k, data[k]); });
                post(path, fd).then(function (j) {
                    toast(j.message || 'Selesai.', !j.ok);
                    j.ok ? reloadSoon() : done();
                }).catch(function () { toast('Koneksi bermasalah.', true); done(); });
            }

            // ---------------- kartu data
            function optLabel(k, v) {
                var f = C.fields[k];
                if (f && f.type === 'rupiah') return 'Rp ' + (parseInt(v, 10) || 0).toLocaleString('id-ID');
                if (f && f.type === 'date' && v) { var d = new Date(v + 'T00:00:00'); return isNaN(d) ? v : d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }); }
                return f && f.options ? (f.options[v] || '') : v;
            }
            function avatar(r) {
                var f = C.fields[C.title];
                if (f.type === 'select') return esc(r[C.title]);
                if (f.type === 'date') return esc(String(r[C.title] || '').slice(8, 10) || '?');
                return esc(String(r[C.title] || '?').trim().split(/\s+/).slice(0, 2).map(function (w) { return w.charAt(0); }).join('').toUpperCase());
            }
            var list = $('tdList');
            function render() {
                $('tdCount').textContent = rows.length;
                if (!list) return;
                if (!rows.length) {
                    list.innerHTML = '<div class="pu-empty" style="grid-column:1/-1"><i class="fas fa-folder-open"></i>Belum ada data ' + esc(C.entity) + ' untuk periode ini.'
                        + (CAN_EDIT ? '<br><small>Klik <b>Tambah ' + esc(C.entity) + '</b> atau <b>Sinkronkan</b> dari tahun sebelumnya.</small>' : '') + '</div>';
                    return;
                }
                var labelled = C.body.length > 1;
                list.innerHTML = rows.map(function (r) {
                    var tag = C.tag ? '<span class="pu-tag' + (C.utama.indexOf(r[C.tag]) >= 0 ? ' utama' : '') + '">' + esc(optLabel(C.tag, r[C.tag]) || C.fields[C.tag].label + ' belum diisi') + '</span>' : '';
                    var sub = '';
                    if (C.sub) {
                        var txt = C.subFormat;
                        [].concat(C.sub).forEach(function (k) { txt = txt.replace('%s', r[k]); });
                        sub = '<div class="pu-item-nik">' + esc(txt) + '</div>';
                    }
                    (C.lines || []).forEach(function (ln) {
                        var t2 = ln[0];
                        ln[1].forEach(function (k) { t2 = t2.replace('%s', optLabel(k, r[k])); });
                        sub += '<div class="pu-item-nik">' + esc(t2) + '</div>';
                    });
                    var chips = C.chips.filter(function (k) { return C.chipSkip.indexOf(r[k]) < 0; }).map(function (k) {
                        return '<span class="pu-chipmini"><b>' + esc(C.fields[k].short || C.fields[k].label) + '</b> ' + esc((C.fields[k].options ? optLabel(k, r[k]).replace(/^\d+\.\s*/, '') : optLabel(k, r[k])) + (C.fields[k].unit || '')) + '</span>';
                    });
                    if (C.chips.length) chips = '<div class="pu-chips">' + (chips.length ? chips.join('') : '<span class="pu-chipmini muted">Belum menjabat di komite mana pun</span>') + '</div>';
                    else chips = '';
                    var body = C.body.map(function (k) {
                        var t = r[k] || '';
                        return (labelled ? '<div class="pu-item-label">' + esc(C.fields[k].short || C.fields[k].label) + '</div>' : '')
                            + '<p class="pu-item-text">' + esc(t) + '</p>'
                            + (t.length > 260 ? '<button type="button" class="pu-more" data-more>Selengkapnya</button>' : '');
                    }).join('');
                    return '<article class="pu-item">'
                        + '<div class="pu-item-top"><div class="pu-avatar" aria-hidden="true">' + avatar(r) + '</div><div>'
                        + '<div class="pu-item-name">' + esc(optLabel(C.title, r[C.title])) + '</div>' + tag + sub + '</div></div>'
                        + chips + body
                        + (CAN_EDIT ? '<div class="pu-item-actions">'
                            + '<button type="button" class="pu-btn pu-btn-light pu-btn-icon" data-edit="' + r.id + '"><i class="fas fa-pen"></i> Ubah</button>'
                            + '<button type="button" class="pu-btn pu-btn-danger pu-btn-icon" data-del="' + r.id + '"><i class="fas fa-trash"></i> Hapus</button>'
                            + '</div>' : '')
                        + '</article>';
                }).join('');
            }
            render();

            function findRow(id) { return rows.filter(function (r) { return r.id === +id; })[0]; }
            if (list) list.addEventListener('click', function (e) {
                var b = e.target.closest('button');
                if (!b) return;
                if (b.hasAttribute('data-more')) {
                    var open = b.previousElementSibling.classList.toggle('open');
                    b.textContent = open ? 'Ringkas' : 'Selengkapnya';
                } else if (b.dataset.edit) {
                    openDialog(findRow(b.dataset.edit));
                } else if (b.dataset.del) {
                    var r = findRow(b.dataset.del);
                    if (!r || !confirm('Hapus data ' + optLabel(C.title, r[C.title]) + '?')) return;
                    var done = busyBtn(b, '');
                    var fd = new FormData(); fd.set('id', r.id);
                    post('hapus', fd).then(function (j) {
                        toast(j.message || 'Selesai.', !j.ok);
                        if (!j.ok) return done();
                        rows = j.rows || [];
                        if (j.reload || !rows.length) return reloadSoon();
                        render();
                    }).catch(function () { toast('Koneksi bermasalah.', true); done(); });
                }
            });

            // ---------------- approval & "Tidak Ada Data"
            document.querySelectorAll('.pu-act').forEach(function (b) {
                b.addEventListener('click', function () {
                    confirmPost(b, 'approve', { type: b.dataset.type, act: b.dataset.act },
                        b.dataset.act === 'cancel' ? 'Batalkan persetujuan ini?' : 'Setujui seluruh data ' + C.entity + ' periode ini?');
                });
            });
            document.querySelectorAll('.pu-nodata').forEach(function (b) {
                b.addEventListener('click', function () {
                    confirmPost(b, 'nodata', { act: b.dataset.act }, b.dataset.act === 'set'
                        ? 'Tandai halaman ini "Tidak Ada Data" untuk periode ini?'
                        : 'Batalkan status "Tidak Ada Data"?');
                });
            });

            if (!CAN_EDIT) return;

            // ---------------- dialog tambah / ubah
            var dlg = $('tdDialog'), form = $('tdForm');
            function openDialog(r) {
                clearErr(form);
                form.reset();
                form.elements.id.value = r ? r.id : '';
                Object.keys(C.fields).forEach(function (k) {
                    var el = form.elements[k];
                    if (r && r[k] !== '') el.value = el.hasAttribute('data-rp') ? (parseInt(r[k], 10) || 0).toLocaleString('id-ID') : r[k];
                    else if (C.fields[k].default !== undefined) el.value = C.fields[k].default;
                    count(form, el);
                });
                syncIdnum();
                syncShowIf();
                $('tdDialogTitle').textContent = (r ? 'Ubah ' : 'Tambah ') + C.entity;
                dlg.showModal ? dlg.showModal() : dlg.setAttribute('open', '');
                setTimeout(function () { form.querySelector('.pu-input').focus(); }, 30);
            }
            // Field bersyarat (showIf): tampil hanya bila field acuan bernilai tertentu
            function syncShowIf() {
                form.querySelectorAll('[data-showif]').forEach(function (w) {
                    var show = form.elements[w.dataset.showif].value === w.dataset.showval;
                    w.hidden = !show;
                    w.querySelectorAll('.pu-input').forEach(function (el) { el.required = show; if (!show) el.value = ''; });
                });
            }
            // Nomor identitas: panjang mengikuti jenis identitas terpilih (mis. NIK 16 / NIB 13)
            function syncIdnum() {
                form.querySelectorAll('[data-idnum]').forEach(function (el) {
                    var f = C.fields[el.name], jenis = form.elements[f.typeField].value, len = f.lengths[jenis] || 16;
                    el.maxLength = len; el.dataset.max = len;
                    el.placeholder = len + ' digit' + (jenis ? ' ' + jenis : '');
                    if (el.value.length > len) el.value = el.value.slice(0, len);
                    count(form, el);
                });
            }
            function closeDialog() { dlg.close ? dlg.close() : dlg.removeAttribute('open'); }
            if (dlg) {
                $('tdAdd').addEventListener('click', function () { openDialog(null); });
                dlg.addEventListener('click', function (e) { if (e.target === dlg || e.target.closest('[data-close]')) closeDialog(); });
                form.addEventListener('change', function (e) { if (e.target.tagName === 'SELECT') { syncIdnum(); syncShowIf(); } });
                form.addEventListener('input', function (e) {
                    if (e.target.hasAttribute('data-rp')) { var d = e.target.value.replace(/\D/g, '').replace(/^0+/, '').slice(0, 15); e.target.value = d ? parseInt(d, 10).toLocaleString('id-ID') : ''; }
                    if (e.target.hasAttribute('data-nik')) e.target.value = e.target.value.replace(/\D/g, '').slice(0, e.target.maxLength > 0 ? e.target.maxLength : 16);
                    count(form, e.target);
                    var w = e.target.closest('.pu-field');
                    if (w) { w.classList.remove('err'); w.querySelector('[data-msg]').textContent = ''; }
                });
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    var done = busyBtn($('tdSave'), 'Menyimpan…');
                    clearErr(form);
                    post('save', new FormData(form)).then(function (j) {
                        if (!j.ok) { toast(j.message || 'Gagal.', true); showErr(form, j.errors); return; }
                        toast(j.message);
                        closeDialog();
                        var wasEmpty = !rows.length;
                        if (j.rows) { rows = j.rows; render(); }
                        if (j.reload || wasEmpty) reloadSoon();   // status approval / tombol berubah
                    }).catch(function () { toast('Koneksi bermasalah, data belum tersimpan.', true); }).then(done);
                });
            }

            // ---------------- tindak lanjut
            var tl = $('tlForm'), tlDirty = $('tlDirty');
            function tlSer() { return new URLSearchParams(new FormData(tl)).toString(); }
            var tlSnap = tlSer();
            function tlSetDirty() {
                var d = tlSer() !== tlSnap;
                tlDirty.classList.toggle('on', d);
                tlDirty.lastElementChild.textContent = d ? 'Ada perubahan belum disimpan' : 'Semua perubahan tersimpan';
            }
            tl.querySelectorAll('[data-max]').forEach(function (el) { count(tl, el); });
            var tlTimer;
            tl.addEventListener('input', function (e) { count(tl, e.target); clearTimeout(tlTimer); tlTimer = setTimeout(tlSetDirty, 200); });
            function tlSave() {
                var done = busyBtn($('tlSave'), 'Menyimpan…');
                clearErr(tl);
                post('savetindak', new FormData(tl)).then(function (j) {
                    toast(j.message || 'Selesai.', !j.ok);
                    if (!j.ok) return showErr(tl, j.errors);
                    tlSnap = tlSer(); tlSetDirty();
                    if (j.reload) reloadSoon();
                }).catch(function () { toast('Koneksi bermasalah, data belum tersimpan.', true); }).then(done);
            }
            tl.addEventListener('submit', function (e) { e.preventDefault(); tlSave(); });
            document.addEventListener('keydown', function (e) {
                if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S') && !(dlg && dlg.open)) { e.preventDefault(); tlSave(); }
            });
            window.addEventListener('beforeunload', function (e) {
                if (!leaving && tlSer() !== tlSnap) { e.preventDefault(); e.returnValue = ''; }
            });

            // ---------------- sinkronisasi dari tahun sebelumnya (langsung tersimpan)
            var syncBtn = $('puSync');
            if (syncBtn && !syncBtn.disabled) {
                syncBtn.addEventListener('click', function () {
                    var yr = syncBtn.dataset.prev;
                    confirmPost(syncBtn, 'syncFromPrevPeriode', {}, 'Sinkronkan data ' + C.entity + ' tahun ' + yr + ' ke tahun ini?\n\n'
                        + 'Data tahun ' + yr + ' yang belum ada akan ditambahkan dan langsung tersimpan. '
                        + 'Tindak lanjut ikut disalin bila tahun ini masih kosong. Status persetujuan akan di-reset.');
                });
            }
        })();
    </script>
    <?= view('templates/komentar', ['komentarPage' => $PAGE]) ?>
<?php endif; ?>
