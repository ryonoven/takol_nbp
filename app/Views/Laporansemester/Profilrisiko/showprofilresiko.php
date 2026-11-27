<?php
/**
 * Laporan Profil Risiko — ringkasan.
 * Variabel: $judul, $bprData, $periodeLabel, $prevLabel, $risks, $row, $autoTotal, $beforeTotal, $canEdit
 */
use App\Controllers\Showprofilresiko as C;

$L = C::LABELS;
$KL = C::KPMR_LABELS;
$total = (int) ($row['ntotalrisk'] ?? 0);
$override = $total && $autoTotal !== null && $total !== (int) $autoTotal;
$dis = $canEdit ? '' : 'disabled';
$tgl = (string) ($row['tanggal'] ?? '');
$tgl = preg_match('/^\d{4}-\d{2}-\d{2}/', $tgl) && !str_starts_with($tgl, '0000') ? substr($tgl, 0, 10) : '';
$pk = static fn($v) => is_numeric($v) && (int) $v >= 1 && (int) $v <= 5 ? (int) $v : 0;

$missing = [];
foreach ($risks as $r) {
    if (!$r['approved']) {
        $missing[] = $r['name'] . ($r['filled'] ? ' belum disetujui Direktur Utama' : ' belum diisi');
    }
}
if (trim((string) ($row['kesimpulan'] ?? '')) === '') {
    $missing[] = 'Kesimpulan/analisis profil risiko belum diisi';
}
foreach (C::TTD_FIELDS as $k => [$lbl]) {
    if (trim((string) ($row[$k] ?? '')) === '') {
        $missing[] = $lbl . ' belum diisi';
    }
}
if ($tgl === '') {
    $missing[] = 'Tanggal pelaporan belum diisi';
}
$ready = !$missing;
$approvedN = count(array_filter($risks, static fn($r) => $r['approved']));
?>
<link rel="stylesheet" href="<?= base_url('asset/css/transparansi.css') ?>?v=<?= @filemtime(FCPATH . 'asset/css/transparansi.css') ?>">
<div id="pu-toast" class="pu-toast" aria-live="polite"></div>

<div class="container-fluid pu spr">
    <a href="<?= base_url('Showsemesteran') ?>" class="pu-back"><i class="fas fa-arrow-left"></i> Laporan Semesteran</a>

    <header class="pu-head">
        <div class="pu-head-main">
            <span class="pu-kicker">Laporan Semesteran · Profil Risiko</span>
            <h1 class="pu-title"><?= esc($judul) ?></h1>
            <div class="pu-meta">
                <span class="pu-bpr"><?= esc($bprData['namabpr'] ?? 'Nama BPR') ?></span>
                <span class="pu-year"><i class="far fa-calendar"></i> <?= $periodeLabel ?></span>
            </div>
        </div>
        <div class="spr-head-tools">
            <a class="pu-btn pu-btn-light" href="<?= site_url('showprofilresiko/exportAllToZip') ?>"><i class="fas fa-file-archive"></i> TXT (ZIP)</a>
        </div>
    </header>

    <!-- Peringkat komposit -->
    <section class="spr-komposit">
        <div class="spr-kcard pk-<?= $total ?>">
            <span class="spr-klbl">Peringkat profil risiko — <?= $periodeLabel ?></span>
            <?php if ($total): ?>
                <span class="spr-kval"><?= $total ?> <small><?= esc($L[$total]) ?></small></span>
                <span class="spr-ksub"><?= $override ? 'Diubah manual · hasil hitung ' . (int) $autoTotal . ' (' . esc($L[(int) $autoTotal] ?? '-') . ')' : 'Rata-rata peringkat risiko (dibulatkan)' ?></span>
            <?php else: ?>
                <span class="spr-kval">—</span><span class="spr-ksub">Lengkapi penilaian risiko</span>
            <?php endif; ?>
            <?php if ($canEdit && $row): ?>
                <details class="spr-override">
                    <summary><i class="fas fa-sliders-h"></i> Ubah peringkat</summary>
                    <div class="spr-override-body">
                        <select id="sprTotal" class="pu-input">
                            <?php foreach ($L as $v => $t): ?><option value="<?= $v ?>" <?= $total === $v ? 'selected' : '' ?>><?= $v ?> — <?= esc($t) ?></option><?php endforeach; ?>
                        </select>
                        <button type="button" class="pu-btn pu-btn-light spr-total" data-act="set"><i class="fas fa-save"></i> Simpan</button>
                        <?php if ($override): ?><button type="button" class="pu-btn pu-btn-ghost spr-total" data-act="reset"><i class="fas fa-undo"></i> Hasil hitung</button><?php endif; ?>
                    </div>
                </details>
            <?php endif; ?>
        </div>
        <?php $b = $pk($beforeTotal); ?>
        <div class="spr-kcard prev pk-<?= $b ?>">
            <span class="spr-klbl">Periode sebelumnya — <?= $prevLabel ?></span>
            <span class="spr-kval"><?= $b ? $b . ' <small>' . esc($L[$b]) . '</small>' : '—' ?></span>
            <span class="spr-ksub"><?= $b ? 'Berdasarkan penilaian periode sebelumnya' : 'Belum ada penilaian periode sebelumnya' ?></span>
        </div>
        <div class="spr-kcard spr-progress">
            <span class="spr-klbl">Risiko disetujui Direktur Utama</span>
            <span class="spr-kval"><?= $approvedN ?>/<?= count($risks) ?></span>
            <span class="spr-bar"><span style="width:<?= count($risks) ? round($approvedN / count($risks) * 100) : 0 ?>%"></span></span>
        </div>
    </section>

    <!-- Jenis risiko -->
    <section class="spr-grid">
        <?php foreach ($risks as $r): $n = $pk($r['nilai']); $bn = $pk($r['b_nilai']); $in = $pk($r['inheren']); $kp = $pk($r['kpmr']); ?>
            <a class="spr-card <?= $r['approved'] ? 'ok' : '' ?>" href="<?= esc($r['link']) ?>">
                <div class="spr-card-head">
                    <span class="spr-ico"><i class="fas <?= $r['icon'] ?>"></i></span>
                    <b><?= esc($r['name']) ?></b>
                    <span class="spr-pk pk-<?= $n ?>" title="Tingkat risiko"><?= $n ?: '—' ?></span>
                </div>
                <?php if (!$r['filled']): ?>
                    <div class="spr-empty"><i class="fas fa-pen"></i> Belum diisi — klik untuk mengisi</div>
                <?php else: ?>
                    <div class="spr-rows">
                        <div><small>Risiko inheren</small><span class="spr-dot pk-<?= $in ?>"></span><?= $in ? $in . ' · ' . esc($L[$in]) : '—' ?></div>
                        <div><small>KPMR</small><span class="spr-dot pk-<?= $kp ?>"></span><?= $kp ? $kp . ' · ' . esc($KL[$kp]) : '—' ?></div>
                        <div><small>Tingkat risiko</small><b><?= $n ? esc($L[$n]) : '—' ?></b></div>
                        <div class="muted"><small>Sebelumnya</small><?= $bn ? $bn . ' · ' . esc($L[$bn]) : '—' ?></div>
                    </div>
                    <?php if (!empty($r['kesimpulan'])): ?><p class="spr-kes"><?= esc($r['kesimpulan']) ?></p><?php endif; ?>
                <?php endif; ?>
                <div class="spr-foot">
                    <span class="spr-chip <?= $r['approved'] ? 'ok' : '' ?>"><i class="fas <?= $r['approved'] ? 'fa-check-circle' : 'fa-clock' ?>"></i>
                        <?= $r['approved'] ? 'Disetujui Direktur Utama' : 'Belum disetujui' ?></span>
                    <span class="spr-open">Buka <i class="fas fa-arrow-right"></i></span>
                </div>
            </a>
        <?php endforeach; ?>
    </section>

    <!-- Kesimpulan & lembar persetujuan -->
    <form class="pu-card" id="sprForm" autocomplete="off" novalidate>
        <input type="hidden" id="puCsrf" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
        <div class="pu-card-head"><h2><i class="fas fa-edit"></i> Kesimpulan &amp; Lembar Persetujuan</h2></div>
        <div class="pu-card-body"><div class="row">
            <?php if (!$canEdit): ?>
                <div class="col-12"><div class="pu-note"><i class="fas fa-eye"></i> Mode baca — hanya Pengelola (PE) atau Admin yang dapat mengubah data.</div></div>
            <?php endif; ?>
            <div class="col-12 pu-field" data-f="kesimpulan">
                <label for="f_kesimpulan">Kesimpulan dan analisis profil risiko</label>
                <textarea id="f_kesimpulan" name="kesimpulan" class="pu-input pu-area" rows="7" maxlength="<?= C::MAX_KESIMPULAN ?>" data-max="<?= C::MAX_KESIMPULAN ?>"
                    placeholder="Analisis profil risiko BPR secara keseluruhan…" <?= $dis ?>><?= esc($row['kesimpulan'] ?? '') ?></textarea>
                <div class="pu-foot"><span class="pu-err" data-msg="kesimpulan"></span><span class="pu-count" data-cnt="kesimpulan"></span></div>
            </div>
            <?php foreach (C::TTD_FIELDS as $k => [$lbl, $max]): ?>
                <div class="col-md-6 pu-field" data-f="<?= $k ?>">
                    <label for="f_<?= $k ?>"><?= esc($lbl) ?></label>
                    <input id="f_<?= $k ?>" name="<?= $k ?>" class="pu-input" maxlength="<?= $max ?>" value="<?= esc($row[$k] ?? '') ?>" <?= $dis ?>>
                    <div class="pu-foot"><span class="pu-err" data-msg="<?= $k ?>"></span></div>
                </div>
            <?php endforeach; ?>
            <div class="col-md-6 pu-field" data-f="tanggal">
                <label for="f_tanggal">Tanggal pelaporan</label>
                <input id="f_tanggal" name="tanggal" type="date" class="pu-input" value="<?= esc($tgl) ?>" <?= $dis ?>>
                <div class="pu-foot"><span class="pu-err" data-msg="tanggal"></span></div>
            </div>
        </div></div>
        <?php if ($canEdit): ?>
            <div class="pu-savebar">
                <button type="submit" class="pu-btn pu-btn-primary" id="sprSave"><i class="fas fa-save"></i> Simpan <kbd class="d-none d-md-inline">Ctrl+S</kbd></button>
                <span class="pu-dirty" id="sprDirty"><i class="fas fa-circle"></i> <span>Semua perubahan tersimpan</span></span>
            </div>
        <?php endif; ?>
    </form>

    <!-- Kelengkapan -->
    <section class="pu-card spr-check <?= $ready ? 'ok' : '' ?>">
        <div class="pu-card-body">
            <?php if ($ready): ?>
                <p class="mb-0"><i class="fas fa-check-circle"></i> Seluruh data lengkap dan seluruh risiko telah disetujui. Laporan siap di-generate.</p>
            <?php else: ?>
                <p class="mb-2"><i class="fas fa-exclamation-triangle"></i> <b>Laporan belum dapat di-generate</b> — lengkapi:</p>
                <ul class="mb-0"><?php foreach ($missing as $m): ?><li><?= esc($m) ?></li><?php endforeach; ?></ul>
            <?php endif; ?>
        </div>
    </section>

    <!-- Generate PDF (tengah bawah) -->
    <section class="spr-generate">
        <p class="spr-generate-note">Cek kembali seluruh data, pastikan informasi BPR dan data yang diperlukan telah terisi dengan benar.</p>
        <?php if ($ready): ?>
            <button type="button" class="spr-btn-generate" id="sprPdf"><i class="fas fa-file-pdf"></i> GENERATE PDF LAPORAN PROFIL RISIKO</button>
        <?php else: ?>
            <button type="button" class="spr-btn-generate" disabled title="Lengkapi data terlebih dahulu"><i class="fas fa-lock"></i> GENERATE PDF LAPORAN PROFIL RISIKO</button>
        <?php endif; ?>
        <div class="ez-box" id="sprBox" hidden aria-live="polite">
            <div class="ez-head"><span class="ez-stage">Menyiapkan…</span><span class="ez-pct">0%</span></div>
            <div class="ez-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="ez-fill"></div></div>
            <div class="ez-note"></div>
            <ul class="ez-files"></ul>
        </div>
    </section>
</div>

<style>
    .spr-head-tools { display: flex; flex-wrap: wrap; gap: 8px; }
    /* Generate PDF — tengah bawah, gaya sama dengan halaman Laporan Tahunan */
    .spr-generate { text-align: center; padding: 18px 0 30px; }
    .spr-generate-note { font-size: .84rem; color: var(--mut, #64748b); margin-bottom: 14px; }
    .spr-btn-generate { display: inline-flex; align-items: center; gap: 8px; padding: 14px 34px; border: none; border-radius: 999px; font-weight: 800;
        font-size: .95rem; letter-spacing: .02em; color: #fff; background: linear-gradient(135deg, #1f2aa8, #141863); box-shadow: 0 8px 22px rgba(20,24,99,.3);
        cursor: pointer; transition: transform .15s, box-shadow .15s; }
    .spr-btn-generate:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(20,24,99,.35); }
    .spr-btn-generate:disabled { background: #9ca3af; box-shadow: none; cursor: not-allowed; }
    .spr-btn-generate.busy { opacity: .8; cursor: progress; }
    .ez-box { max-width: 520px; margin: 20px auto 0; text-align: left; background: #fff; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 14px 18px; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
    .ez-box[hidden] { display: none; }
    .ez-head { display: flex; justify-content: space-between; gap: 12px; font-size: .88rem; font-weight: 700; color: #1e293b; margin-bottom: 8px; }
    .ez-pct { font-variant-numeric: tabular-nums; color: #1f2aa8; }
    .ez-track { height: 12px; background: #e2e8f0; border-radius: 999px; overflow: hidden; }
    .ez-fill { height: 100%; width: 0; border-radius: 999px; background: linear-gradient(135deg, #1f2aa8, #141863); transition: width .25s ease; position: relative; overflow: hidden; }
    .ez-fill:after { content: ""; position: absolute; inset: 0; background: linear-gradient(90deg, transparent, rgba(255,255,255,.35), transparent); animation: ezShine 1.2s linear infinite; }
    @keyframes ezShine { from { transform: translateX(-100%); } to { transform: translateX(100%); } }
    .ez-note { font-size: .78rem; color: #475569; margin-top: 8px; word-break: break-all; }
    .ez-files { margin: 6px 0 0; padding-left: 18px; font-size: .76rem; color: #dc2626; }
    .ez-files:empty { display: none; }
    .ez-box.done .ez-fill { background: linear-gradient(135deg, #059669, #10b981); } .ez-box.done .ez-stage { color: #059669; }
    .ez-box.error .ez-fill { background: #ef4444; } .ez-box.error .ez-stage { color: #dc2626; }
    .ez-box.done .ez-fill:after, .ez-box.error .ez-fill:after { display: none; }
    @media (prefers-reduced-motion: reduce) { .ez-fill, .ez-fill:after { transition: none; animation: none; } }
    .spr-komposit { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 12px; margin-bottom: 16px; }
    .spr-kcard { position: relative; border-radius: 16px; padding: 16px 18px; color: #fff; display: flex; flex-direction: column; gap: 2px; background: #94a3b8; box-shadow: 0 6px 18px rgba(15,23,42,.08); }
    .spr-kcard.prev { opacity: .9; }
    .spr-klbl { font-size: .74rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; opacity: .92; }
    .spr-kval { font-size: 2rem; font-weight: 800; line-height: 1.15; }
    .spr-kval small { font-size: .95rem; font-weight: 700; }
    .spr-ksub { font-size: .82rem; opacity: .95; }
    .spr-kcard.pk-1 { background: linear-gradient(135deg, #0284c7, #0ea5e9); }
    .spr-kcard.pk-2 { background: linear-gradient(135deg, #047857, #10b981); }
    .spr-kcard.pk-3 { background: linear-gradient(135deg, #b45309, #f59e0b); }
    .spr-kcard.pk-4 { background: linear-gradient(135deg, #c2410c, #f97316); }
    .spr-kcard.pk-5 { background: linear-gradient(135deg, #b91c1c, #ef4444); }
    .spr-kcard.spr-progress { background: #fff; color: var(--txt, #0f172a); border: 1px solid var(--bd, #e2e8f0); }
    .spr-kcard.spr-progress .spr-klbl { color: var(--mut, #64748b); }
    .spr-bar { display: block; height: 6px; border-radius: 999px; background: #e2e8f0; overflow: hidden; margin-top: 8px; }
    .spr-bar span { display: block; height: 100%; background: linear-gradient(90deg, #1f2aa8, #059669); }
    .spr-override { margin-top: 8px; font-size: .8rem; }
    .spr-override summary { cursor: pointer; opacity: .95; list-style: none; }
    .spr-override summary::-webkit-details-marker { display: none; }
    .spr-override-body { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; padding: 10px; border-radius: 12px; background: rgba(255,255,255,.95); }
    .spr-override-body .pu-input { flex: 1; min-width: 150px; color: #0f172a; }
    .spr-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(290px, 1fr)); gap: 12px; margin-bottom: 16px; }
    .spr-card { display: flex; flex-direction: column; gap: 10px; background: #fff; border: 1.5px solid var(--bd, #e2e8f0); border-top: 3px solid #f59e0b; border-radius: 14px; padding: 14px 16px; color: inherit; text-decoration: none !important; transition: box-shadow .15s, transform .15s; }
    .spr-card.ok { border-top-color: #10b981; }
    .spr-card:hover, .spr-card:focus { box-shadow: 0 8px 22px rgba(31,42,168,.1); transform: translateY(-2px); }
    .spr-card-head { display: flex; align-items: center; gap: 10px; }
    .spr-card-head b { flex: 1; color: var(--txt, #0f172a); }
    .spr-ico { width: 36px; height: 36px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; background: #eef2ff; color: var(--c2, #1f2aa8); }
    .spr-pk { min-width: 36px; height: 36px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; font-weight: 800; color: #fff; background: #cbd5e1; }
    .spr-pk.pk-1, .spr-dot.pk-1 { background: #0ea5e9; } .spr-pk.pk-2, .spr-dot.pk-2 { background: #10b981; } .spr-pk.pk-3, .spr-dot.pk-3 { background: #f59e0b; }
    .spr-pk.pk-4, .spr-dot.pk-4 { background: #f97316; } .spr-pk.pk-5, .spr-dot.pk-5 { background: #ef4444; }
    .spr-rows { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 12px; font-size: .84rem; color: #334155; }
    .spr-rows small { display: block; font-size: .68rem; font-weight: 700; color: var(--mut, #64748b); text-transform: uppercase; letter-spacing: .03em; }
    .spr-rows .muted { color: #64748b; }
    .spr-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 6px; background: #cbd5e1; vertical-align: middle; }
    .spr-kes { margin: 0; font-size: .78rem; color: #475569; background: #f8fafc; border-radius: 10px; padding: 8px 10px; }
    .spr-empty { font-size: .84rem; color: #92400e; background: #fffbeb; border-radius: 10px; padding: 12px; }
    .spr-foot { display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-top: auto; }
    .spr-chip { font-size: .72rem; font-weight: 700; padding: 3px 10px; border-radius: 999px; background: #fef3c7; color: #92400e; }
    .spr-chip.ok { background: #d1fae5; color: #065f46; }
    .spr-open { font-size: .78rem; font-weight: 700; color: var(--c2, #1f2aa8); }
    .spr-check { border-color: #fcd34d; background: #fffbeb; color: #92400e; font-size: .86rem; }
    .spr-check.ok { border-color: #6ee7b7; background: var(--okbg, #ecfdf5); color: #065f46; }
    .spr-check ul { padding-left: 20px; }
    @media (prefers-reduced-motion: reduce) { .spr-card { transition: none; } .spr-card:hover { transform: none; } }
</style>

<script>
    (function () {
        'use strict';
        if (!window.fetch) return;
        var csrf = document.getElementById('puCsrf'), busy = false, leaving = false;
        function toast(msg, err) {
            var d = document.createElement('div'); if (err) d.className = 'e'; d.textContent = msg;
            document.getElementById('pu-toast').appendChild(d); setTimeout(function () { d.remove(); }, 3800);
        }
        function post(url, fd, btn) {
            if (busy) return Promise.resolve(null); busy = true;
            var ori = btn ? btn.innerHTML : ''; if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses…'; }
            fd.set(csrf.name, csrf.value);
            return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json().catch(function () { return { ok: false, message: 'Respons server tidak valid (' + r.status + ').' }; }); })
                .then(function (j) {
                    if (j.csrf) { csrf.name = j.csrf.name; csrf.value = j.csrf.hash; }
                    toast(j.message || 'Selesai.', !j.ok);
                    if (j.ok && j.reload) { leaving = true; setTimeout(function () { location.reload(); }, 700); }
                    else if (btn) { btn.disabled = false; btn.innerHTML = ori; }
                    return j;
                })
                .catch(function () { toast('Koneksi bermasalah, coba lagi.', true); if (btn) { btn.disabled = false; btn.innerHTML = ori; } return null; })
                .then(function (j) { busy = false; return j; });
        }

        // ---- Generate PDF: generator pdf-lib yang sama (00–07 PDF) dijalankan di iframe tersembunyi,
        //      progres ditampilkan seperti loading bar halaman Laporan Tahunan.
        var pdf = document.getElementById('sprPdf');
        if (pdf) (function () {
            var box = document.getElementById('sprBox'), fill = box.querySelector('.ez-fill'), track = box.querySelector('.ez-track'),
                pctEl = box.querySelector('.ez-pct'), stage = box.querySelector('.ez-stage'), note = box.querySelector('.ez-note'),
                files = box.querySelector('.ez-files'), label = pdf.innerHTML, frame = null, guard = null, shown = 0, target = 0, tick = null;
            function paint(p) {
                p = Math.max(0, Math.min(100, p));
                fill.style.width = p + '%'; pctEl.textContent = Math.floor(p) + '%'; track.setAttribute('aria-valuenow', Math.floor(p));
            }
            function setPct(p) { target = Math.max(target, p); }
            function finish() {
                clearInterval(tick); clearTimeout(guard);
                if (frame) { frame.remove(); frame = null; }
                pdf.disabled = false; pdf.classList.remove('busy'); pdf.innerHTML = label;
            }
            function fail(msg) { finish(); box.classList.add('error'); stage.textContent = 'Generate PDF gagal'; note.textContent = msg + ' Silakan coba lagi.'; }
            window.addEventListener('message', function (e) {
                var d = e.data;
                if (e.origin !== window.location.origin || !d || d.source !== 'spr-pdf' || !frame) return;
                if (d.ev === 'progress') { setPct(d.pct); stage.textContent = d.text; }
                else if (d.ev === 'file') { stage.textContent = 'Membuat ' + d.name + '…'; }
                else if (d.ev === 'file-error') { var li = document.createElement('li'); li.textContent = d.name + ' — ' + d.text; files.appendChild(li); }
                else if (d.ev === 'error') { fail(d.text || 'Terjadi kesalahan.'); }
                else if (d.ev === 'done') {
                    var url = URL.createObjectURL(d.blob), a = document.createElement('a');
                    a.href = url; a.download = d.name; document.body.appendChild(a); a.click(); a.remove();
                    setTimeout(function () { URL.revokeObjectURL(url); }, 10000);
                    target = shown = 100; paint(100); finish();
                    box.classList.add(d.ok === d.total ? 'done' : 'error');
                    stage.textContent = d.ok === d.total ? 'Selesai — file ZIP berhasil diunduh' : 'Selesai dengan ' + (d.total - d.ok) + ' file gagal';
                    note.textContent = d.name + ' · ' + d.ok + ' dari ' + d.total + ' file PDF';
                }
            });
            pdf.addEventListener('click', function () {
                if (frame) return;
                pdf.disabled = true; pdf.classList.add('busy'); pdf.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sedang menyiapkan PDF…';
                box.hidden = false; box.classList.remove('done', 'error'); files.innerHTML = '';
                stage.textContent = 'Menyiapkan…'; note.textContent = 'Mohon tunggu, jangan tutup atau muat ulang halaman ini.';
                shown = target = 0; paint(0);
                // animasi halus menuju target progres
                tick = setInterval(function () { if (shown < target) { shown += Math.max(0.5, (target - shown) * 0.2); paint(shown); } }, 120);
                frame = document.createElement('iframe');
                frame.src = '<?= site_url('showprofilresiko/exportAllPDFToZipView') ?>';
                frame.setAttribute('aria-hidden', 'true'); frame.tabIndex = -1;
                frame.style.cssText = 'position:absolute;width:1px;height:1px;border:0;left:-9999px;top:0;';
                document.body.appendChild(frame);
                guard = setTimeout(function () { fail('Waktu proses habis.'); }, 5 * 60 * 1000);
            });
        })();

        document.querySelectorAll('.spr-total').forEach(function (b) {
            b.addEventListener('click', function () {
                var fd = new FormData(); fd.set('act', b.dataset.act);
                if (b.dataset.act === 'set') fd.set('ntotalrisk', document.getElementById('sprTotal').value);
                if (b.dataset.act === 'reset' && !confirm('Kembalikan peringkat komposit ke hasil perhitungan?')) return;
                post('<?= site_url('showprofilresiko/setTotal') ?>', fd, b);
            });
        });

        var form = document.getElementById('sprForm'), btn = document.getElementById('sprSave'), dirtyEl = document.getElementById('sprDirty');
        if (!btn) return;
        function ser() { var fd = new FormData(form); fd.delete(csrf.name); return new URLSearchParams(fd).toString(); }
        var snap = ser();
        function setDirty() {
            var d = ser() !== snap; dirtyEl.classList.toggle('on', d);
            dirtyEl.lastElementChild.textContent = d ? 'Ada perubahan belum disimpan' : 'Semua perubahan tersimpan';
        }
        function count(el) {
            var c = form.querySelector('[data-cnt="' + el.name + '"]'); if (!c) return;
            var max = +el.dataset.max, len = el.value.length;
            c.textContent = len.toLocaleString('id-ID') + ' / ' + max.toLocaleString('id-ID');
            c.className = 'pu-count' + (len >= max ? ' max' : len >= max * 0.9 ? ' warn' : '');
        }
        form.querySelectorAll('[data-max]').forEach(count);
        var t;
        form.addEventListener('input', function (e) {
            if (e.target.dataset.max) count(e.target);
            var w = e.target.closest('.pu-field'); if (w) { w.classList.remove('err'); w.querySelector('[data-msg]').textContent = ''; }
            clearTimeout(t); t = setTimeout(setDirty, 200);
        });
        form.addEventListener('change', setDirty);
        window.addEventListener('beforeunload', function (e) { if (!leaving && ser() !== snap) { e.preventDefault(); e.returnValue = ''; } });
        function save() {
            post('<?= site_url('showprofilresiko/save') ?>', new FormData(form), btn).then(function (j) {
                if (!j) return;
                Object.keys(j.errors || {}).forEach(function (k) {
                    var w = form.querySelector('[data-f="' + k + '"]'); if (!w) return;
                    w.classList.add('err'); w.querySelector('[data-msg]').textContent = j.errors[k];
                });
                if (j.ok) { snap = ser(); setDirty(); }
            });
        }
        form.addEventListener('submit', function (e) { e.preventDefault(); save(); });
        document.addEventListener('keydown', function (e) { if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) { e.preventDefault(); save(); } });
    })();
</script>
