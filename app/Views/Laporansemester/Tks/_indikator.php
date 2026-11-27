<?php
/**
 * Halaman faktor TKS berbasis indikator rasio (App\Libraries\TksIndikatorController: Rentabilitas, Permodalan).
 * Variabel: $judul, $cfg, $bprData, $row, $outdated, $prev, $curLabel, $prevLabel, $canEdit, $canApprove, $maxText, $nav
 */
$M = $cfg['model'];
$L = $M::LABELS;
$bobotPct = (int) round($M::BOBOT * 100);
$indList = esc($cfg['ind_list']);
$route = $cfg['route'];
$namaFaktor = esc(preg_replace('/^Penilaian /', '', $judul));
$approved = (int) ($row['is_approved'] ?? 0) === 1;
$dis = $canEdit ? '' : 'disabled';
$fmtDt = static function ($v) {
    $t = $v ? strtotime((string) $v) : false;
    return ($t && $t > 0) ? date('d/m/Y H:i', $t) : '';
};
// Kolom periode p (0 = saat ini, 1 = sebelumnya) dianggap "kosong" bila semua nominalnya 0
$filled = [false, false];
foreach ($M::INDIKATOR as $ind) {
    foreach ($ind['inputs'] as [, $c, $b]) {
        $filled[0] = $filled[0] || (int) ($row[$c] ?? 0) !== 0;
        $filled[1] = $filled[1] || (int) ($row[$b] ?? 0) !== 0;
    }
}
$nom = static fn($col, $p) => $filled[$p] ? number_format((float) ($row[$col] ?? 0), 0, ',', '.') : '';
$periods = [[$curLabel, 'Periode saat ini'], [$prevLabel, 'Periode sebelumnya']];
$crit = static function (array $ind) {
    $op = $ind['dir'] === 'high' ? '≥' : '≤';
    $out = [];
    foreach ($ind['limits'] as $i => $l) {
        $out[] = ($i + 1) . ': ' . $op . ' ' . str_replace('.', ',', (string) $l) . '%';
    }
    $out[] = '5: ' . ($ind['dir'] === 'high' ? '<' : '>') . ' ' . str_replace('.', ',', (string) end($ind['limits'])) . '%';
    return $out;
};
// Konfigurasi untuk perhitungan langsung di layar (rumus sama dengan M_tksindikator::compute)
$jsInd = [];
foreach ($M::INDIKATOR as $k => $ind) {
    $jsInd[$k] = ['calc' => $ind['calc'], 'den' => $ind['den'] ?? 'nonzero', 'dir' => $ind['dir'], 'limits' => $ind['limits'], 'inputs' => array_map(static fn($i) => [$i[1], $i[2]], $ind['inputs'])];
}
?>
<link rel="stylesheet" href="<?= base_url('asset/css/transparansi.css') ?>?v=<?= @filemtime(FCPATH . 'asset/css/transparansi.css') ?>">
<div id="pu-toast" class="pu-toast" aria-live="polite"></div>

<div class="container-fluid pu tks">
    <a href="<?= base_url('Showtks') ?>" class="pu-back"><i class="fas fa-arrow-left"></i> Laporan TKS</a>

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

    <!-- Peringkat faktor (diperbarui langsung saat nominal diubah) -->
    <section class="tpr-scores">
        <?php foreach ($periods as $p => [$lbl]): $pk = (int) ($row[$M::FAKTOR_COLS[$p][1]] ?? 0); ?>
            <div class="tpr-score <?= $p ? '' : 'main' ?> pk-<?= $pk ?>" data-score="<?= $p ?>">
                <span class="tpr-lbl">Peringkat <?= $lbl ?></span>
                <span class="tpr-val" data-s="val"><?= $pk ? $pk . ' <small>' . esc($L[$pk]) . '</small>' : '—' ?></span>
                <span class="tpr-sub" data-s="sub"><?= $pk
                    ? 'Rata-rata ' . number_format((float) $row[$M::FAKTOR_COLS[$p][0]], 2, ',', '.') . ' · nilai faktor ' . $pk . ' × ' . $bobotPct . '% = <b>' . number_format($pk * $M::BOBOT, 2, ',', '.') . '</b>'
                    : 'Lengkapi ' . $indList ?></span>
            </div>
        <?php endforeach; ?>
    </section>

    <form id="puForm" autocomplete="off" novalidate>
        <input type="hidden" id="puCsrf" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">

        <?php if (!$canEdit): ?>
            <div class="pu-note"><i class="fas fa-eye"></i> Mode baca — hanya Pengelola (PE) atau Admin yang dapat mengubah data.</div>
        <?php else: ?>
            <section class="tre-tools">
                <div><b>Isi cepat</b><small>Nilai diisikan ke form — periksa, lalu klik <b>Simpan</b>.</small></div>
                <div class="tre-tools-btn">
                    <button type="button" class="pu-btn pu-btn-light" id="treMis"><i class="fas fa-database"></i> Tarik dari MIS TKS</button>
                    <button type="button" class="pu-btn pu-btn-light" id="trePrev" <?= $prev ? '' : 'disabled' ?>
                        title="<?= $prev ? 'Salin nominal ' . $prevLabel . ' ke kolom periode sebelumnya' : 'Belum ada data ' . $prevLabel ?>">
                        <i class="fas fa-history"></i> Salin data <?= $prevLabel ?></button>
                </div>
                <?php if ($outdated): ?>
                    <div class="pu-note warn mb-0"><i class="fas fa-calculator"></i> Rasio/peringkat yang tersimpan belum sesuai perhitungan dari nominal.
                        Tampilan di bawah sudah dihitung ulang — klik <b>Simpan</b> untuk memperbarui data.</div>
                <?php endif; ?>
                <?php if ($approved): ?>
                    <div class="pu-note warn mb-0"><i class="fas fa-info-circle"></i> Data sudah disetujui. Menyimpan perubahan akan membatalkan persetujuan Direktur Utama.</div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php $no = 0; foreach ($M::INDIKATOR as $key => $ind): $no++; ?>
            <section class="pu-card tre-ind" data-ind="<?= $key ?>">
                <div class="pu-card-head">
                    <h2><span class="tre-no"><?= $no ?></span> <?= esc($ind['title']) ?></h2>
                    <small class="tre-formula"><?= esc($ind['formula']) ?></small>
                </div>
                <div class="pu-card-body tre-grid">
                    <?php foreach ($periods as $p => [$lbl, $sub]): ?>
                        <div class="tre-col <?= $p ? 'prev' : '' ?>">
                            <div class="tre-colhead"><b><?= $lbl ?></b><small><?= $sub ?></small></div>
                            <?php foreach ($ind['inputs'] as [$ilbl, $cCur, $cBef]): $col = $p ? $cBef : $cCur; ?>
                                <div class="pu-field" data-f="<?= $col ?>">
                                    <label for="f_<?= $col ?>"><?= esc($ilbl) ?></label>
                                    <div class="tre-rp"><span>Rp</span>
                                        <input id="f_<?= $col ?>" name="<?= $col ?>" class="pu-input tre-num" inputmode="decimal"
                                            value="<?= $nom($col, $p) ?>" placeholder="0" <?= $dis ?>>
                                    </div>
                                    <div class="pu-foot"><span class="pu-err" data-msg="<?= $col ?>"></span></div>
                                </div>
                            <?php endforeach; ?>
                            <?php $rk = (int) ($row[$ind['rank'][$p]] ?? 0); ?>
                            <div class="tre-res" data-res="<?= $key ?>-<?= $p ?>">
                                <div><small>Rasio</small><b data-r="ratio"><?= $rk ? number_format((float) $row[$ind['ratio'][$p]], 2, ',', '.') . '%' : '—' ?></b></div>
                                <div><small>Peringkat</small><span class="tks-pk pk-<?= $rk ?>" data-r="rank"><?= $rk ?: '—' ?></span></div>
                                <div><small>Penilaian</small><b data-r="label"><?= $rk ? esc($L[$rk]) : '—' ?></b></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="tre-crit"><i class="fas fa-ruler"></i> Kriteria peringkat:
                    <?php foreach ($crit($ind) as $c): ?><span><?= $c ?></span><?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>

        <section class="pu-card">
            <div class="pu-card-head"><h2><i class="fas fa-file-alt"></i> Deskripsi &amp; Analisa</h2></div>
            <div class="pu-card-body">
                <?php foreach (array_combine($M::TEXT_COLS, [['Deskripsi peringkat', 4, 'Penjelasan singkat peringkat faktor…'],
                    ['Analisa / penjelasan', 7, 'Analisis komprehensif faktor (' . $cfg['ind_list'] . ')…']]) as $f => [$lbl, $rows, $ph]): ?>
                    <div class="pu-field" data-f="<?= $f ?>">
                        <label for="f_<?= $f ?>"><?= $lbl ?></label>
                        <textarea id="f_<?= $f ?>" name="<?= $f ?>" class="pu-input pu-area" rows="<?= $rows ?>" maxlength="<?= $maxText ?>"
                            data-max="<?= $maxText ?>" placeholder="<?= $ph ?>" <?= $dis ?>><?= esc($row[$f] ?? '') ?></textarea>
                        <div class="pu-foot"><span class="pu-err" data-msg="<?= $f ?>"></span><span class="pu-count" data-cnt="<?= $f ?>"></span></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if ($canEdit): ?>
                <div class="pu-savebar">
                    <button type="submit" class="pu-btn pu-btn-primary" id="puSave"><i class="fas fa-save"></i> Simpan <kbd class="d-none d-md-inline">Ctrl+S</kbd></button>
                    <span class="pu-dirty" id="puDirty"><i class="fas fa-circle"></i> <span>Belum ada perubahan</span></span>
                </div>
            <?php endif; ?>
        </section>
    </form>

    <?php if ($canApprove): $pkNow = (int) ($row[$M::FAKTOR_COLS[0][1]] ?? 0); ?>
        <section class="pu-approve">
            <div class="pu-approve-card <?= $approved ? 'ok' : '' ?>">
                <div class="pu-approve-who"><i class="fas fa-user-shield"></i> Direktur Utama</div>
                <?php if ($approved): ?>
                    <button type="button" class="pu-btn pu-btn-danger tpr-act" data-act="cancel" data-confirm="Batalkan persetujuan <?= $namaFaktor ?>?"><i class="fas fa-ban"></i> Batalkan</button>
                    <small>Disetujui <?= $fmtDt($row['approved_at'] ?? '') ?></small>
                <?php elseif ($pkNow): ?>
                    <button type="button" class="pu-btn pu-btn-primary tpr-act" data-act="approve" data-confirm="Setujui penilaian <?= $namaFaktor ?>?"><i class="fas fa-check-double"></i> Setujui</button>
                <?php else: ?>
                    <button type="button" class="pu-btn pu-btn-primary" disabled><i class="fas fa-lock"></i> Setujui</button>
                    <small>Lengkapi <?= $indList ?> periode saat ini</small>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <nav class="pu-nav" aria-label="Faktor TKS">
        <?php foreach ($nav as $i => $n): ?>
            <a href="<?= base_url($i) ?>" class="tpr-nav <?= $i === $cfg['page'] ? 'active' : '' ?>" <?= $i === $cfg['page'] ? 'aria-current="page"' : '' ?>><?= $n ?></a>
        <?php endforeach; ?>
        <a href="<?= base_url('Showtks') ?>" class="all">Ringkasan TKS</a>
    </nav>
</div>

<?= view('templates/komentar', ['komentarPage' => $cfg['page']]) ?>

<style>
    .tpr-scores { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px; margin-bottom: 14px; }
    .tpr-score { display: flex; flex-direction: column; gap: 2px; padding: 16px 18px; border-radius: 16px; color: #fff; background: #94a3b8; box-shadow: 0 6px 18px rgba(15,23,42,.08); transition: background .2s; }
    .tpr-score:not(.main) { opacity: .9; }
    .pk-1.tpr-score { background: linear-gradient(135deg, #0284c7, #0ea5e9); }
    .pk-2.tpr-score { background: linear-gradient(135deg, #047857, #10b981); }
    .pk-3.tpr-score { background: linear-gradient(135deg, #b45309, #f59e0b); }
    .pk-4.tpr-score { background: linear-gradient(135deg, #c2410c, #f97316); }
    .pk-5.tpr-score { background: linear-gradient(135deg, #b91c1c, #ef4444); }
    .tpr-lbl { font-size: .74rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; opacity: .92; }
    .tpr-val { font-size: 2rem; font-weight: 800; line-height: 1.15; }
    .tpr-val small { font-size: .95rem; font-weight: 700; }
    .tpr-sub { font-size: .84rem; opacity: .95; }
    .tre-tools { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px 14px; padding: 12px 16px; margin-bottom: 14px; background: #eef2ff; border: 1.5px solid #c7d2fe; border-radius: 14px; color: #312e81; font-size: .86rem; }
    .tre-tools > div:first-child { display: flex; flex-direction: column; }
    .tre-tools small { font-size: .78rem; }
    .tre-tools-btn { display: flex; flex-wrap: wrap; gap: 8px; }
    .tre-tools .pu-note { flex-basis: 100%; }
    .tre-no { display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 8px; background: var(--c2, #1f2aa8); color: #fff; font-size: .8rem; margin-right: 4px; }
    .tre-ind .pu-card-head { flex-wrap: wrap; gap: 4px 12px; }
    .tre-formula { font-size: .76rem; color: var(--mut, #64748b); }
    .tre-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 14px; }
    .tre-col { border: 1px solid var(--bd, #e2e8f0); border-radius: 12px; padding: 12px 14px; background: #fff; }
    .tre-col.prev { background: #f8fafc; }
    .tre-colhead { display: flex; justify-content: space-between; align-items: baseline; gap: 8px; margin-bottom: 8px; padding-bottom: 6px; border-bottom: 1px dashed var(--bd, #e2e8f0); }
    .tre-colhead small { font-size: .72rem; color: var(--mut, #64748b); }
    .tre-rp { display: flex; align-items: center; border: 1px solid var(--bd, #e2e8f0); border-radius: 10px; background: #fff; overflow: hidden; }
    .tre-rp span { padding: 0 10px; font-size: .78rem; font-weight: 700; color: var(--mut, #64748b); background: #f1f5f9; align-self: stretch; display: flex; align-items: center; }
    .tre-rp .pu-input { border: 0; border-radius: 0; text-align: right; font-variant-numeric: tabular-nums; min-width: 0; }
    .tre-rp:focus-within { border-color: #818cf8; box-shadow: 0 0 0 3px rgba(99,102,241,.15); }
    .pu-field.err .tre-rp { border-color: #ef4444; }
    .tre-res { display: grid; grid-template-columns: 1fr auto 1.3fr; gap: 8px; align-items: center; margin-top: 6px; padding: 10px 12px; background: #f1f5f9; border-radius: 10px; }
    .tre-res small { display: block; font-size: .68rem; color: var(--mut, #64748b); font-weight: 600; }
    .tre-res b { font-size: .95rem; font-variant-numeric: tabular-nums; }
    .tks-pk { display: inline-flex; align-items: center; justify-content: center; min-width: 30px; height: 30px; border-radius: 9px; font-weight: 800; color: #fff; background: #cbd5e1; }
    .tks-pk.pk-1 { background: #0ea5e9; } .tks-pk.pk-2 { background: #10b981; } .tks-pk.pk-3 { background: #f59e0b; }
    .tks-pk.pk-4 { background: #f97316; } .tks-pk.pk-5 { background: #ef4444; }
    .tre-crit { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; padding: 10px 18px 14px; font-size: .74rem; color: var(--mut, #64748b); }
    .tre-crit span { padding: 2px 8px; border-radius: 999px; background: #f1f5f9; color: #334155; font-variant-numeric: tabular-nums; }
    .pu-nav .tpr-nav { width: auto; padding: 0 12px; }
</style>

<script>
    (function () {
        'use strict';
        if (!window.fetch) return;
        var IND = <?= json_encode($jsInd) ?>, LBL = <?= json_encode($L) ?>, PREV = <?= json_encode($prev) ?>, BOBOT = <?= $M::BOBOT ?>, NIND = Object.keys(IND).length, COPY = <?= json_encode((object) $cfg['copy_if_empty']) ?>;
        var csrf = document.getElementById('puCsrf'), form = document.getElementById('puForm');
        var busy = false, leaving = false;
        var nf = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 });
        var nf2 = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        function toast(msg, err) {
            var d = document.createElement('div'); if (err) d.className = 'e'; d.textContent = msg;
            document.getElementById('pu-toast').appendChild(d); setTimeout(function () { d.remove(); }, 3800);
        }
        function parse(v) { // "1.234.567" / "-1.234,5" → number
            v = String(v || '').replace(/[\s ]|Rp/gi, '').replace(/\./g, '').replace(',', '.');
            return v === '' || v === '-' || isNaN(+v) ? 0 : +v;
        }
        function val(name) { var el = form.elements[name]; return el ? parse(el.value) : 0; }
        function rankOf(ind, r) {
            for (var i = 0; i < ind.limits.length; i++) if (ind.dir === 'high' ? r >= ind.limits[i] : r <= ind.limits[i]) return i + 1;
            return 5;
        }

        // ---- Perhitungan langsung (sama dengan M_tksindikator::compute)
        function recalc() {
            [0, 1].forEach(function (p) {
                var ranks = [];
                Object.keys(IND).forEach(function (k) {
                    var ind = IND[k], v = ind.inputs.map(function (f) { return val(f[p]); });
                    var num = ind.calc === 'sum' ? v[0] + v[1] : ind.calc === 'net' ? v[0] - v[1] : v[0], den = ind.calc === 'ratio' ? v[1] : v[2];
                    var box = document.querySelector('[data-res="' + k + '-' + p + '"]'), rk = 0, ratio = null;
                    if (ind.den === 'positive' ? den > 0 : den !== 0) { ratio = Math.round(num / den * 10000) / 100; rk = rankOf(ind, ratio); ranks.push(rk); }
                    box.querySelector('[data-r="ratio"]').textContent = ratio === null ? '—' : nf2.format(ratio) + '%';
                    var b = box.querySelector('[data-r="rank"]'); b.textContent = rk || '—'; b.className = 'tks-pk pk-' + rk;
                    box.querySelector('[data-r="label"]').textContent = rk ? LBL[rk] : '—';
                });
                var card = document.querySelector('[data-score="' + p + '"]'), pk = 0, avg = 0;
                if (ranks.length === NIND) { avg = ranks.reduce(function (a, b) { return a + b; }, 0) / NIND; pk = Math.round(avg); }
                card.className = 'tpr-score ' + (p ? '' : 'main ') + 'pk-' + pk;
                card.querySelector('[data-s="val"]').innerHTML = pk ? pk + ' <small>' + LBL[pk] + '</small>' : '—';
                card.querySelector('[data-s="sub"]').innerHTML = pk
                    ? 'Rata-rata ' + nf2.format(avg) + ' · nilai faktor ' + pk + ' × <?= $bobotPct ?>% = <b>' + nf2.format(pk * BOBOT) + '</b>'
                    : 'Lengkapi <?= $indList ?>';
            });
        }
        function fmt(el) { if (el.value.trim() !== '' && el.value.trim() !== '-') el.value = nf.format(Math.round(parse(el.value))); }
        form.querySelectorAll('.tre-num').forEach(function (el) {
            el.addEventListener('blur', function () {
                fmt(el);
                // Kolom turunan yang masih kosong mengikuti kolom sumber (mis. Modal inti MIAPB = Modal inti KPMM)
                var t = COPY[el.name] && form.elements[COPY[el.name]];
                if (t && !t.disabled && parse(t.value) === 0 && el.value.trim() !== '') t.value = el.value;
                recalc(); setDirty();
            });
        });
        function fill(data) {
            var n = 0;
            Object.keys(data).forEach(function (k) {
                var el = form.elements[k]; if (!el || el.disabled) return;
                el.value = nf.format(data[k]); n++;
                var w = el.closest('.pu-field'); if (w) w.classList.add('tre-filled');
            });
            recalc(); setDirty(); return n;
        }

        // ---- Simpan & status perubahan
        var saveBtn = document.getElementById('puSave'), dirtyEl = document.getElementById('puDirty');
        function ser() {
            var fd = new FormData(form); fd.delete(csrf.name);
            form.querySelectorAll('.tre-num').forEach(function (el) { fd.set(el.name, Math.round(parse(el.value))); });
            return new URLSearchParams(fd).toString();
        }
        var snap = ser();
        function dirty() { return saveBtn ? ser() !== snap : false; }
        <?php if ($outdated): ?>recalc(); if (saveBtn) { snap = ''; setDirty(); }<?php endif; ?>

        function setDirty() {
            if (!dirtyEl) return; var d = dirty(); dirtyEl.classList.toggle('on', d);
            dirtyEl.lastElementChild.textContent = d ? 'Ada perubahan belum disimpan' : 'Belum ada perubahan';
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

        document.querySelectorAll('.tpr-act').forEach(function (b) {
            b.addEventListener('click', function () {
                if (dirty() && !confirm('Ada perubahan yang belum disimpan dan akan hilang. Lanjutkan?')) return;
                if (!confirm(b.dataset.confirm)) return;
                var fd = new FormData(); fd.set('act', b.dataset.act);
                post('<?= site_url($route . '/approve') ?>', fd, b);
            });
        });
        if (!saveBtn) return;

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
            clearTimeout(t); t = setTimeout(function () { if (e.target.classList.contains('tre-num')) recalc(); setDirty(); }, 200);
        });
        window.addEventListener('beforeunload', function (e) { if (!leaving && dirty()) { e.preventDefault(); e.returnValue = ''; } });

        function save() {
            form.querySelectorAll('.tre-num').forEach(fmt);
            var fd = new FormData(form);
            form.querySelectorAll('.tre-num').forEach(function (el) { fd.set(el.name, el.value); });
            var p = post('<?= site_url($route . '/save') ?>', fd, saveBtn);
            if (p) p.then(function (j) { if (j && j.ok) { snap = ser(); setDirty(); } });
        }
        form.addEventListener('submit', function (e) { e.preventDefault(); save(); });
        document.addEventListener('keydown', function (e) { if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) { e.preventDefault(); save(); } });

        // ---- Isi cepat
        var prevBtn = document.getElementById('trePrev');
        if (prevBtn && PREV) prevBtn.addEventListener('click', function () {
            if (!confirm('Isi kolom "periode sebelumnya" dengan nominal <?= $prevLabel ?>?\nNilai yang sudah ada di kolom tersebut akan diganti.')) return;
            toast(fill(PREV) + ' nominal disalin. Periksa lalu klik Simpan.');
        });
        var misBtn = document.getElementById('treMis');
        misBtn.addEventListener('click', function () {
            if (busy) return; busy = true;
            var ori = misBtn.innerHTML; misBtn.disabled = true; misBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengambil…';
            fetch('<?= site_url($route . '/mis') ?>', { credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (j) {
                    if (!j.ok) { toast(j.message || 'Data MIS TKS tidak tersedia.', true); return; }
                    if (!confirm(Object.keys(j.data).length + ' nominal ditemukan di MIS TKS. Isikan ke form? Nilai yang ada akan diganti.')) return;
                    fill(j.data); toast(j.message);
                })
                .catch(function () { toast('Gagal mengambil data MIS TKS.', true); })
                .then(function () { busy = false; misBtn.disabled = false; misBtn.innerHTML = ori; });
        });
    })();
</script>
