<?php
/**
 * Halaman ringkasan laporan semesteran (App\Libraries\SemesterSummaryController).
 * Variabel: $judul, $cfg, $bprData, $periodeLabel, $sections, $row, $ttdComplete, $file, $canEdit, $maxKb
 */
$approvedN = count(array_filter($sections, static fn($s) => $s['approved']));
$total = count($sections);
$fmtDt = static function ($v) {
    $t = is_numeric($v) ? (int) $v : ($v ? strtotime((string) $v) : false);
    return ($t && $t > 0) ? date('d/m/Y H:i', $t) : '';
};
$fmtSize = static fn(int $b) => $b >= 1048576 ? number_format($b / 1048576, 1, ',', '.') . ' MB' : number_format(max(1, $b / 1024), 0, ',', '.') . ' KB';

$missing = [];
foreach ($sections as $s) {
    if (!$s['approved']) {
        $missing[] = $s['name'] . ($s['filled'] ? ' — belum disetujui Direktur Utama' : ' — belum diisi');
    }
}
foreach ($cfg['ttd'] as $k => [$lbl, $type]) {
    $v = trim((string) ($row[$k] ?? ''));
    if ($v === '' || $v === '0000-00-00') {
        $missing[] = 'Lembar persetujuan: ' . $lbl . ' belum diisi';
    }
}
if (!$file) {
    $missing[] = $cfg['file_text'][3] . ' belum diunggah';
}
$route = $cfg['route'];
$ready = !$missing;
$namabpr = $bprData['namabpr'] ?? '';
?>
<link rel="stylesheet" href="<?= base_url('asset/css/transparansi.css') ?>?v=<?= @filemtime(FCPATH . 'asset/css/transparansi.css') ?>">
<div id="pu-toast" class="pu-toast" aria-live="polite"></div>

<div class="container-fluid pu aud">
    <a href="<?= base_url('Showsemesteran') ?>" class="pu-back"><i class="fas fa-arrow-left"></i> Laporan Semesteran</a>

    <?php foreach (['message' => 'success', 'error' => 'danger', 'err' => 'danger'] as $k => $cls): ?>
        <?php if ($msg = session()->getFlashdata($k)): ?>
            <div class="alert alert-<?= $cls ?> mt-3"><?= esc($msg) ?></div>
        <?php endif; ?>
    <?php endforeach; ?>

    <header class="pu-head">
        <div class="pu-head-main">
            <span class="pu-kicker"><?= esc($cfg['kicker']) ?></span>
            <h1 class="pu-title"><?= esc($judul) ?></h1>
            <div class="pu-meta">
                <span class="pu-bpr"><?= esc($namabpr ?: 'Nama BPR belum diisi') ?></span>
                <span class="pu-year"><i class="far fa-calendar"></i> <?= $periodeLabel ?></span>
            </div>
        </div>
        <div class="aud-progress">
            <span><b><?= $approvedN ?></b>/<?= $total ?> bagian disetujui</span>
            <span class="aud-bar"><span style="width:<?= round($approvedN / $total * 100) ?>%"></span></span>
        </div>
    </header>

    <?php if ($namabpr === ''): ?>
        <div class="pu-note warn"><i class="fas fa-info-circle"></i> Informasi BPR belum lengkap.
            <a href="<?= base_url('Infobpr') ?>">Lengkapi nama dan sandi BPR</a>.</div>
    <?php endif; ?>

    <!-- 5 bagian laporan -->
    <section class="aud-grid">
        <?php $no = 0; foreach ($sections as $key => $s): $no++;
            if ($s['approved']) {
                [$cls, $icon, $txt] = ['ok', 'fa-check-circle', 'Disetujui Direktur Utama'];
            } elseif ($s['filled']) {
                [$cls, $icon, $txt] = ['wait', 'fa-clock', 'Menunggu persetujuan'];
            } else {
                [$cls, $icon, $txt] = ['empty', 'fa-pen', 'Belum diisi'];
            }
            ?>
            <a class="aud-card <?= $cls ?>" href="<?= esc($s['url']) ?>">
                <span class="aud-no"><?= $no ?></span>
                <div class="aud-body">
                    <b><?= esc($s['name']) ?></b>
                    <span class="aud-chip"><i class="fas <?= $icon ?>"></i> <?= $txt ?>
                        <?php if ($s['nodata']): ?> · <em>Nihil</em><?php endif; ?>
                        <?php if ($s['approved'] && ($d = $fmtDt($s['approved_at']))): ?><small> · <?= $d ?></small><?php endif; ?>
                    </span>
                </div>
                <i class="fas fa-chevron-right aud-go"></i>
            </a>
        <?php endforeach; ?>
    </section>

    <?php if ($cfg['ttd']): ?>
        <!-- Lembar persetujuan -->
        <section class="pu-card">
            <div class="pu-card-head"><h2><i class="fas fa-file-signature"></i> Lembar Persetujuan</h2></div>
            <div class="pu-card-body">
                <form id="ttdForm" class="row" autocomplete="off" novalidate>
                    <?php foreach ($cfg['ttd'] as $k => [$lbl, $type, $max]):
                        $v = (string) ($row[$k] ?? '');
                        if ($type === 'date' && ($v === '0000-00-00' || !preg_match('/^\d{4}-\d{2}-\d{2}/', $v))) {
                            $v = '';
                        } ?>
                        <div class="<?= $type === 'date' ? 'col-md-4' : ($k === array_key_first($cfg['ttd']) ? 'col-md-8' : 'col-md-6') ?> pu-field" data-f="<?= $k ?>">
                            <label for="ttd_<?= $k ?>"><?= esc($lbl) ?> <span class="text-danger">*</span></label>
                            <input id="ttd_<?= $k ?>" name="<?= $k ?>" class="pu-input" type="<?= $type ?>" value="<?= esc(substr($v, 0, $type === 'date' ? 10 : 500)) ?>"
                                <?= $type === 'text' ? 'maxlength="' . $max . '"' : '' ?> <?= $canEdit ? '' : 'disabled' ?>>
                            <div class="pu-foot"><span class="pu-err" data-msg="<?= $k ?>"></span></div>
                        </div>
                    <?php endforeach; ?>
                </form>
            </div>
            <div class="pu-savebar">
                <?php if ($canEdit): ?>
                    <button type="button" class="pu-btn pu-btn-primary" id="ttdSave"><i class="fas fa-save"></i> Simpan</button>
                <?php endif; ?>
                <?php if ($ttdComplete): ?>
                    <a class="pu-btn pu-btn-light" href="<?= base_url($cfg['ttd_print_url']) ?>" target="_blank" rel="noopener"><i class="fas fa-print"></i> Cetak Lembar Pengesahan</a>
                <?php else: ?>
                    <button type="button" class="pu-btn pu-btn-light" disabled title="Lengkapi lembar persetujuan terlebih dahulu"><i class="fas fa-lock"></i> Cetak Lembar Pengesahan</button>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Dokumen PDF -->
    <section class="pu-card">
        <div class="pu-card-head"><h2><i class="fas fa-file-pdf"></i> <?= esc($cfg['file_text'][0]) ?></h2></div>
        <div class="pu-card-body">
            <p class="aud-desc"><?= esc($cfg['file_text'][1]) ?> Maksimal <?= $maxKb / 1024 ?> MB.</p>

            <?php if ($file): ?>
                <div class="aud-file">
                    <i class="fas fa-file-pdf"></i>
                    <div><b><?= esc($cfg['file_text'][2]) ?></b><small><?= $fmtSize((int) $file['size']) ?> · diunggah <?= $fmtDt($file['time']) ?></small></div>
                    <div class="aud-file-act">
                        <a class="pu-btn pu-btn-light" href="<?= site_url($route . '/downloadPdf/' . rawurlencode($file['name'])) ?>" target="_blank" rel="noopener"><i class="fas fa-eye"></i> Lihat</a>
                        <?php if ($canEdit): ?>
                            <button type="button" class="pu-btn pu-btn-danger" id="audDel"><i class="fas fa-trash"></i> Hapus</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif (!$canEdit): ?>
                <div class="pu-note"><i class="fas fa-info-circle"></i> <?= esc($cfg['file_text'][0]) ?> belum diunggah.</div>
            <?php endif; ?>

            <?php if ($canEdit): ?>
                <form id="audForm" class="aud-drop" novalidate>
                                        <input type="file" name="pdf" id="audFile" accept="application/pdf,.pdf" hidden>
                    <label for="audFile" class="aud-drop-zone" id="audZone">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span id="audZoneTxt"><b><?= $file ? 'Ganti dokumen' : 'Pilih file PDF' ?></b> atau seret ke sini</span>
                        <small>PDF · maks. <?= $maxKb / 1024 ?> MB</small>
                    </label>
                    <div class="aud-up" id="audUp" hidden><span class="aud-bar"><span id="audUpBar"></span></span><small id="audUpTxt">0%</small></div>
                </form>
            <?php endif; ?>
        </div>
    </section>

    <!-- Kelengkapan & cetak -->
    <section class="pu-card aud-check <?= $ready ? 'ok' : '' ?>">
        <div class="pu-card-body">
            <?php if ($ready): ?>
                <p class="mb-0"><i class="fas fa-check-circle"></i> Seluruh bagian telah disetujui dan dokumen lengkap. Laporan siap dicetak.</p>
            <?php else: ?>
                <p class="mb-2"><i class="fas fa-exclamation-triangle"></i> <b>Belum dapat mencetak PDF</b> — lengkapi:</p>
                <ul class="mb-0"><?php foreach ($missing as $m): ?><li><?= esc($m) ?></li><?php endforeach; ?></ul>
            <?php endif; ?>
        </div>
        <div class="pu-savebar">
            <?php if ($ready): ?>
                <a class="pu-btn pu-btn-primary" href="<?= base_url($cfg['print_url']) ?>" target="_blank" rel="noopener"><i class="fas fa-file-pdf"></i> Cetak PDF</a>
            <?php else: ?>
                <button type="button" class="pu-btn pu-btn-primary" disabled><i class="fas fa-lock"></i> Cetak PDF</button>
            <?php endif; ?>
        </div>
    </section>
</div>

<style>
    .aud-progress { display: flex; flex-direction: column; gap: 6px; min-width: 200px; color: #fff; font-size: .85rem; }
    .aud-progress b { font-size: 1.4rem; }
    .aud-bar { display: block; height: 7px; border-radius: 999px; background: rgba(148,163,184,.35); overflow: hidden; }
    .aud-bar > span { display: block; height: 100%; background: linear-gradient(90deg, #34d399, #10b981); transition: width .2s; }
    .aud-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 12px; margin-bottom: 16px; }
    .aud-card { display: flex; align-items: center; gap: 14px; padding: 14px 16px; background: #fff; border: 1.5px solid var(--bd, #e2e8f0); border-left-width: 5px; border-radius: 14px; color: inherit; text-decoration: none !important; transition: box-shadow .15s, transform .15s; }
    .aud-card:hover, .aud-card:focus { box-shadow: 0 8px 22px rgba(31,42,168,.1); transform: translateY(-2px); }
    .aud-card.ok { border-left-color: #10b981; }
    .aud-card.wait { border-left-color: #f59e0b; }
    .aud-card.empty { border-left-color: #cbd5e1; }
    .aud-no { flex: none; display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 10px; background: #eef2ff; color: var(--c2, #1f2aa8); font-weight: 800; }
    .aud-card.ok .aud-no { background: #d1fae5; color: #065f46; }
    .aud-body { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 6px; }
    .aud-body b { color: var(--txt, #0f172a); font-size: .92rem; line-height: 1.3; }
    .aud-chip { align-self: flex-start; font-size: .72rem; font-weight: 700; padding: 3px 10px; border-radius: 999px; background: #f1f5f9; color: #475569; }
    .aud-card.ok .aud-chip { background: #d1fae5; color: #065f46; }
    .aud-card.wait .aud-chip { background: #fef3c7; color: #92400e; }
    .aud-chip small { font-weight: 600; }
    .aud-go { color: #94a3b8; }
    .aud-desc { font-size: .84rem; color: var(--mut, #64748b); margin-bottom: 12px; }
    .aud-file { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; padding: 12px 14px; border: 1px solid #bbf7d0; background: #f0fdf4; border-radius: 12px; margin-bottom: 12px; }
    .aud-file > i { font-size: 1.8rem; color: #dc2626; }
    .aud-file > div:nth-child(2) { flex: 1; min-width: 160px; display: flex; flex-direction: column; }
    .aud-file small { font-size: .75rem; color: var(--mut, #64748b); }
    .aud-file-act { display: flex; gap: 8px; flex-wrap: wrap; }
    .aud-drop-zone { display: flex; flex-direction: column; align-items: center; gap: 4px; margin: 0; padding: 22px 16px; border: 2px dashed #c7d2fe; border-radius: 14px; background: #f8faff; color: #312e81; text-align: center; cursor: pointer; transition: background .15s, border-color .15s; }
    .aud-drop-zone i { font-size: 1.8rem; color: #6366f1; }
    .aud-drop-zone small { color: var(--mut, #64748b); font-size: .74rem; }
    .aud-drop-zone.over, .aud-drop-zone:hover { background: #eef2ff; border-color: #818cf8; }
    .aud-drop.busy .aud-drop-zone { pointer-events: none; opacity: .6; }
    .aud-up { display: flex; align-items: center; gap: 10px; margin-top: 10px; }
    .aud-up[hidden] { display: none; }
    .aud-up .aud-bar { flex: 1; background: #e2e8f0; }
    .aud-up small { font-variant-numeric: tabular-nums; color: var(--mut, #64748b); min-width: 36px; text-align: right; }
    .aud-check { border-color: #fcd34d; background: #fffbeb; color: #92400e; font-size: .86rem; }
    .aud-check.ok { border-color: #6ee7b7; background: var(--okbg, #ecfdf5); color: #065f46; }
    .aud-check ul { padding-left: 20px; }
    .aud-check .pu-savebar { background: transparent; justify-content: center; }
    @media (prefers-reduced-motion: reduce) { .aud-card { transition: none; } .aud-card:hover { transform: none; } }
</style>

<input type="hidden" id="puCsrf" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
<?php if ($canEdit): ?>
    <script>
        (function () {
            'use strict';
            var MAX = <?= (int) $maxKb ?> * 1024, busy = false;
            var form = document.getElementById('audForm'), input = document.getElementById('audFile'), zone = document.getElementById('audZone');
            var csrf = document.getElementById('puCsrf'), up = document.getElementById('audUp'), bar = document.getElementById('audUpBar'), pct = document.getElementById('audUpTxt');
            function toast(msg, err) {
                var d = document.createElement('div'); if (err) d.className = 'e'; d.textContent = msg;
                document.getElementById('pu-toast').appendChild(d); setTimeout(function () { d.remove(); }, 3800);
            }
            function done(j) {
                if (j.csrf) { csrf.name = j.csrf.name; csrf.value = j.csrf.hash; }
                toast(j.message || 'Selesai.', !j.ok);
                if (j.ok && j.reload) setTimeout(function () { location.reload(); }, 700);
                return j.ok;
            }
            function upload(file) {
                if (busy || !file) return;
                if (!/\.pdf$/i.test(file.name)) { toast('File harus berupa PDF.', true); return; }
                if (file.size > MAX) { toast('Ukuran file maksimal <?= $maxKb / 1024 ?> MB.', true); return; }
                <?php if ($file): ?>if (!confirm('Ganti dokumen yang sudah ada dengan "' + file.name + '"?')) { input.value = ''; return; }<?php endif; ?>

                busy = true; form.classList.add('busy'); up.hidden = false; bar.style.width = '0'; pct.textContent = '0%';
                var fd = new FormData(); fd.set(csrf.name, csrf.value); fd.set('pdf', file);
                var x = new XMLHttpRequest();
                x.open('POST', '<?= site_url($route . '/uploadPdf') ?>');
                x.setRequestHeader('Accept', 'application/json'); x.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                x.upload.onprogress = function (e) { if (e.lengthComputable) { var p = Math.round(e.loaded / e.total * 100); bar.style.width = p + '%'; pct.textContent = p + '%'; } };
                x.onload = function () {
                    var j; try { j = JSON.parse(x.responseText); } catch (e) { j = { ok: false, message: 'Respons server tidak valid (' + x.status + ').' }; }
                    if (!done(j)) { busy = false; form.classList.remove('busy'); up.hidden = true; input.value = ''; }
                };
                x.onerror = function () { toast('Koneksi bermasalah, file belum terunggah.', true); busy = false; form.classList.remove('busy'); up.hidden = true; };
                x.send(fd);
            }
            input.addEventListener('change', function () { upload(input.files[0]); });
            ['dragenter', 'dragover'].forEach(function (ev) { zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.add('over'); }); });
            ['dragleave', 'drop'].forEach(function (ev) { zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.remove('over'); }); });
            zone.addEventListener('drop', function (e) { if (e.dataTransfer && e.dataTransfer.files.length) upload(e.dataTransfer.files[0]); });

            var ttdBtn = document.getElementById('ttdSave');
            if (ttdBtn) ttdBtn.addEventListener('click', function () {
                if (busy) return; busy = true;
                var f = document.getElementById('ttdForm'), fd = new FormData(f), ori = ttdBtn.innerHTML;
                f.querySelectorAll('.pu-field').forEach(function (w) { w.classList.remove('err'); w.querySelector('[data-msg]').textContent = ''; });
                fd.set(csrf.name, csrf.value); ttdBtn.disabled = true; ttdBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan…';
                fetch('<?= site_url($cfg['route'] . '/saveTtd') ?>', { method: 'POST', body: fd, credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); })
                    .then(function (j) {
                        Object.keys(j.errors || {}).forEach(function (k) {
                            var w = f.querySelector('[data-f="' + k + '"]'); if (w) { w.classList.add('err'); w.querySelector('[data-msg]').textContent = j.errors[k]; }
                        });
                        if (!done(j) || !j.reload) { busy = false; ttdBtn.disabled = false; ttdBtn.innerHTML = ori; }
                    })
                    .catch(function () { toast('Koneksi bermasalah.', true); busy = false; ttdBtn.disabled = false; ttdBtn.innerHTML = ori; });
            });

            var del = document.getElementById('audDel');
            if (del) del.addEventListener('click', function () {
                if (busy || !confirm('Hapus dokumen ini? File akan dihapus permanen.')) return;
                busy = true; del.disabled = true;
                var fd = new FormData(); fd.set(csrf.name, csrf.value);
                fetch('<?= site_url($route . '/deletePdf') ?>', { method: 'POST', body: fd, credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); })
                    .then(function (j) { if (!done(j)) { busy = false; del.disabled = false; } })
                    .catch(function () { toast('Koneksi bermasalah.', true); busy = false; del.disabled = false; });
            });
        })();
    </script>
<?php endif; ?>
