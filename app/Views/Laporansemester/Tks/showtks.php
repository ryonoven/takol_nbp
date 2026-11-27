<?php
/**
 * Laporan Tingkat Kesehatan BPR (TKS).
 * Variabel: $bprData, $periodeDetail, $showtks, $faktorTKSData, $kompositData, $canEdit
 */
use App\Controllers\Showtks;

$label = [1 => 'Sangat Baik', 2 => 'Baik', 3 => 'Cukup Baik', 4 => 'Kurang Baik', 5 => 'Tidak Baik'];
$num = static fn($v, int $d = 2) => ($v === null || $v === '' || !is_numeric($v)) ? '—' : number_format((float) $v, $d, ',', '.');
$sem = (string) ($periodeDetail['semester'] ?? '');
$semLabel = ($sem === '1' || strtolower($sem) === 'ganjil') ? 'Semester I (Juni)' : 'Semester II (Desember)';
$dis = $canEdit ? '' : 'disabled';
$tgl = (string) ($showtks['tanggal'] ?? '');
$showtks['tanggal'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl) && $tgl !== '0000-00-00' ? $tgl : '';

// Kelengkapan untuk cetak PDF
$approved = count(array_filter($faktorTKSData, static fn($f) => $f['is_approved'] === 1));
$missing = [];
foreach ($faktorTKSData as $f) {
    if ($f['is_approved'] !== 1) {
        $missing[] = $f['nama_faktor'] . ($f['filled'] ? ' belum disetujui' : ' belum diisi');
    }
}
if (trim((string) ($showtks['kesimpulan'] ?? '')) === '') {
    $missing[] = 'Kesimpulan/analisis TKS belum diisi';
}
foreach (Showtks::TTD_FIELDS + ['tanggal' => ['Tanggal pelaporan']] as $k => [$lbl]) {
    if (empty($showtks[$k])) {
        $missing[] = $lbl . ' belum diisi';
    }
}
$ready = !$missing;
$komposit = [
    ['Saat ini', $kompositData['nkomposittks'], $kompositData['peringkatkomposittks']],
    ['Periode sebelumnya', $kompositData['nkomposittksbefore'], $kompositData['peringkatkomposittksbefore']],
];
?>
<link rel="stylesheet" href="<?= base_url('asset/css/transparansi.css') ?>?v=<?= @filemtime(FCPATH . 'asset/css/transparansi.css') ?>">
<div id="pu-toast" class="pu-toast" aria-live="polite"></div>

<div class="container-fluid pu tks">
    <a href="<?= base_url('Showsemesteran') ?>" class="pu-back"><i class="fas fa-arrow-left"></i> Laporan Semesteran</a>

    <?php foreach (['message' => 'success', 'error' => 'danger', 'err' => 'danger'] as $k => $cls): ?>
        <?php if ($msg = session()->getFlashdata($k)): ?>
            <div class="alert alert-<?= $cls ?> mt-3"><?= esc($msg) ?></div>
        <?php endif; ?>
    <?php endforeach; ?>

    <header class="pu-head">
        <div class="pu-head-main">
            <span class="pu-kicker">Laporan Semesteran · TKSBPRKS 0700</span>
            <h1 class="pu-title"><?= esc($judul) ?></h1>
            <div class="pu-meta">
                <span class="pu-bpr"><?= esc($bprData['namabpr'] ?? 'Nama BPR') ?></span>
                <span class="pu-year"><i class="far fa-calendar"></i> <?= $semLabel ?> <?= esc($periodeDetail['tahun'] ?? '') ?></span>
            </div>
        </div>
        <div class="tks-head-tools">
            <a class="pu-btn pu-btn-light" href="<?= site_url('showtks/exporttxttks') ?>"><i class="fas fa-file-alt"></i> Export TXT</a>
            <?php if ($ready): ?>
                <a class="pu-btn pu-btn-primary" href="<?= site_url('showtks/LaporanPDFTKS') ?>" target="_blank" rel="noopener"><i class="fas fa-file-pdf"></i> Cetak PDF</a>
            <?php else: ?>
                <button type="button" class="pu-btn pu-btn-primary" disabled title="Lengkapi data terlebih dahulu"><i class="fas fa-lock"></i> Cetak PDF</button>
            <?php endif; ?>
        </div>
    </header>

    <!-- Nilai komposit -->
    <section class="tks-komposit">
        <?php foreach ($komposit as $i => [$title, $nilai, $pk]): ?>
            <div class="tks-kcard pk-<?= $pk ?: 0 ?> <?= $i ? 'prev' : '' ?>">
                <span class="tks-klbl">Nilai komposit — <?= $title ?></span>
                <?php if ($nilai === null || $pk < 1 || $pk > 5): ?>
                    <span class="tks-kval">—</span><span class="tks-ksub">Belum dapat dihitung</span>
                <?php else: ?>
                    <span class="tks-kval">PK-<?= (int) $pk ?></span>
                    <span class="tks-ksub"><?= esc($label[$pk] ?? '-') ?> · nilai <?= $num($nilai) ?></span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <div class="tks-kcard tks-progress">
            <span class="tks-klbl">Faktor disetujui Direktur Utama</span>
            <span class="tks-kval"><?= $approved ?>/4</span>
            <span class="mon-bar"><span style="width:<?= $approved * 25 ?>%"></span></span>
        </div>
    </section>

    <!-- 4 faktor -->
    <section class="tks-faktor">
        <?php foreach ($faktorTKSData as $f): $p = (int) $f['nilai_peringkat']; $pb = (int) $f['nilai_peringkat_before']; ?>
            <a class="tks-fcard" href="<?= esc($f['url']) ?>">
                <div class="tks-fhead">
                    <b><?= esc($f['nama_faktor']) ?></b>
                    <span class="tks-bobot">Bobot <?= $f['bobot'] ?></span>
                </div>
                <?php if (!$f['filled']): ?>
                    <div class="tks-fempty"><i class="fas fa-pen"></i> Belum diisi — klik untuk mengisi</div>
                <?php else: ?>
                    <div class="tks-fgrid">
                        <div><small>Peringkat</small><span class="tks-pk pk-<?= $p ?>"><?= $p ?: '—' ?></span></div>
                        <div><small>Nilai faktor</small><b><?= $num($f['nilai_faktor']) ?></b></div>
                        <div class="muted"><small>Sebelumnya</small><span class="tks-pk sm pk-<?= $pb ?>"><?= $pb ?: '—' ?></span> <b><?= $num($f['nilai_faktor_before']) ?></b></div>
                    </div>
                <?php endif; ?>
                <div class="tks-ffoot">
                    <span class="mon-badge <?= $f['is_approved'] === 1 ? 'c-green' : 'c-amber' ?>">
                        <i class="fas <?= $f['is_approved'] === 1 ? 'fa-check-circle' : 'fa-clock' ?>"></i>
                        <?= $f['is_approved'] === 1 ? 'Disetujui Direktur Utama' : 'Belum disetujui' ?>
                    </span>
                    <span class="tks-open">Buka <i class="fas fa-arrow-right"></i></span>
                </div>
            </a>
        <?php endforeach; ?>
    </section>

    <!-- Kesimpulan & lembar persetujuan -->
    <form class="pu-card" id="tksForm" autocomplete="off" novalidate>
        <input type="hidden" id="puCsrf" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
        <div class="pu-card-head"><h2><i class="fas fa-edit"></i> Kesimpulan &amp; Lembar Persetujuan</h2></div>
        <div class="pu-card-body"><div class="row">
            <?php if (!$canEdit): ?>
                <div class="col-12"><div class="pu-note"><i class="fas fa-eye"></i> Mode baca — hanya Pengelola (PE) atau Admin yang dapat mengubah data.</div></div>
            <?php endif; ?>
            <div class="col-12 pu-field" data-f="kesimpulan">
                <label for="f_kesimpulan">Kesimpulan dan analisis Tingkat Kesehatan BPR</label>
                <textarea id="f_kesimpulan" name="kesimpulan" class="pu-input pu-area" rows="7" maxlength="<?= Showtks::MAX_KESIMPULAN ?>"
                    data-max="<?= Showtks::MAX_KESIMPULAN ?>" placeholder="Analisis komprehensif tingkat kesehatan BPR berdasarkan keempat faktor…" <?= $dis ?>><?= esc($showtks['kesimpulan'] ?? '') ?></textarea>
                <div class="pu-foot"><span class="pu-err" data-msg="kesimpulan"></span><span class="pu-count" data-cnt="kesimpulan"></span></div>
            </div>
            <?php foreach (Showtks::TTD_FIELDS as $k => [$lbl, $max]): ?>
                <div class="col-md-6 pu-field" data-f="<?= $k ?>">
                    <label for="f_<?= $k ?>"><?= esc($lbl) ?></label>
                    <input id="f_<?= $k ?>" name="<?= $k ?>" class="pu-input" maxlength="<?= $max ?>" value="<?= esc($showtks[$k] ?? '') ?>" <?= $dis ?>>
                    <div class="pu-foot"><span class="pu-err" data-msg="<?= $k ?>"></span></div>
                </div>
            <?php endforeach; ?>
            <div class="col-md-6 pu-field" data-f="tanggal">
                <label for="f_tanggal">Tanggal pelaporan</label>
                <input id="f_tanggal" name="tanggal" type="date" class="pu-input" value="<?= esc($showtks['tanggal'] ?? '') ?>" <?= $dis ?>>
                <div class="pu-foot"><span class="pu-err" data-msg="tanggal"></span></div>
            </div>
        </div></div>
        <?php if ($canEdit): ?>
            <div class="pu-savebar">
                <button type="submit" class="pu-btn pu-btn-primary" id="tksSave"><i class="fas fa-save"></i> Simpan <kbd class="d-none d-md-inline">Ctrl+S</kbd></button>
                <span class="pu-dirty" id="tksDirty"><i class="fas fa-circle"></i> <span>Semua perubahan tersimpan</span></span>
            </div>
        <?php endif; ?>
    </form>

    <!-- Kelengkapan -->
    <section class="pu-card tks-check <?= $ready ? 'ok' : '' ?>">
        <div class="pu-card-body">
            <?php if ($ready): ?>
                <p class="mb-0"><i class="fas fa-check-circle"></i> Seluruh data lengkap dan keempat faktor telah disetujui. Laporan siap dicetak.</p>
            <?php else: ?>
                <p class="mb-2"><i class="fas fa-exclamation-triangle"></i> <b>Belum dapat mencetak PDF</b> — lengkapi:</p>
                <ul class="mb-0"><?php foreach ($missing as $m): ?><li><?= esc($m) ?></li><?php endforeach; ?></ul>
            <?php endif; ?>
        </div>
    </section>
</div>

<style>
    .tks-head-tools { display: flex; flex-wrap: wrap; gap: 8px; }
    .tks-komposit { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-bottom: 16px; }
    .tks-kcard { border-radius: 16px; padding: 16px 18px; color: #fff; display: flex; flex-direction: column; gap: 2px; background: #64748b; box-shadow: 0 6px 18px rgba(15,23,42,.08); }
    .tks-kcard.prev { opacity: .88; }
    .tks-klbl { font-size: .74rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; opacity: .9; }
    .tks-kval { font-size: 2rem; font-weight: 800; line-height: 1.1; }
    .tks-ksub { font-size: .85rem; opacity: .95; }
    .tks-kcard.pk-1 { background: linear-gradient(135deg, #0284c7, #0ea5e9); }
    .tks-kcard.pk-2 { background: linear-gradient(135deg, #047857, #10b981); }
    .tks-kcard.pk-3 { background: linear-gradient(135deg, #b45309, #f59e0b); }
    .tks-kcard.pk-4 { background: linear-gradient(135deg, #c2410c, #f97316); }
    .tks-kcard.pk-5 { background: linear-gradient(135deg, #b91c1c, #ef4444); }
    .tks-kcard.tks-progress { background: #fff; color: var(--txt); border: 1px solid var(--bd); }
    .tks-kcard.tks-progress .tks-klbl { color: var(--mut); }
    .mon-bar { display: block; height: 6px; border-radius: 999px; background: #e2e8f0; overflow: hidden; margin-top: 8px; }
    .mon-bar span { display: block; height: 100%; background: linear-gradient(90deg, #1f2aa8, #059669); }
    .mon-badge { display: inline-flex; align-items: center; gap: 4px; font-size: .72rem; font-weight: 700; padding: 3px 10px; border-radius: 999px; }
    .c-green { background: #d1fae5; color: #065f46; } .c-amber { background: #fef3c7; color: #92400e; }
    .tks-faktor { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px; margin-bottom: 16px; }
    .tks-fcard { display: flex; flex-direction: column; gap: 12px; background: #fff; border: 1.5px solid var(--bd); border-radius: 14px; padding: 14px 16px; color: inherit; text-decoration: none !important; transition: border-color .15s, box-shadow .15s, transform .15s; }
    .tks-fcard:hover, .tks-fcard:focus { border-color: #a5b4fc; box-shadow: 0 8px 22px rgba(31,42,168,.1); transform: translateY(-2px); }
    .tks-fhead { display: flex; justify-content: space-between; gap: 8px; align-items: baseline; }
    .tks-fhead b { color: var(--txt); }
    .tks-bobot { font-size: .72rem; color: var(--mut); white-space: nowrap; }
    .tks-fgrid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .tks-fgrid small { display: block; font-size: .7rem; color: var(--mut); font-weight: 600; }
    .tks-fgrid b { font-size: 1.05rem; color: var(--txt); font-variant-numeric: tabular-nums; }
    .tks-fgrid .muted { grid-column: 1 / -1; padding-top: 8px; border-top: 1px dashed var(--bd); }
    .tks-fgrid .muted b { font-size: .9rem; color: #475569; }
    .tks-pk { display: inline-flex; align-items: center; justify-content: center; min-width: 34px; height: 34px; border-radius: 10px; font-weight: 800; color: #fff; background: #cbd5e1; }
    .tks-pk.sm { min-width: 24px; height: 24px; border-radius: 7px; font-size: .78rem; margin-right: 6px; }
    .tks-pk.pk-1 { background: #0ea5e9; } .tks-pk.pk-2 { background: #10b981; } .tks-pk.pk-3 { background: #f59e0b; }
    .tks-pk.pk-4 { background: #f97316; } .tks-pk.pk-5 { background: #ef4444; }
    .tks-fempty { font-size: .85rem; color: #92400e; background: #fffbeb; border-radius: 10px; padding: 12px; }
    .tks-ffoot { display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-top: auto; }
    .tks-open { font-size: .78rem; font-weight: 700; color: var(--c2); }
    .tks-check { border-color: #fcd34d; background: #fffbeb; color: #92400e; font-size: .86rem; }
    .tks-check.ok { border-color: #6ee7b7; background: var(--okbg); color: #065f46; }
    .tks-check ul { padding-left: 20px; }
    @media (prefers-reduced-motion: reduce) { .tks-fcard { transition: none; } .tks-fcard:hover { transform: none; } }
</style>

<?php if ($canEdit): ?>
    <script>
        (function () {
            'use strict';
            if (!window.fetch) return;
            var form = document.getElementById('tksForm'), csrf = document.getElementById('puCsrf');
            var dirtyEl = document.getElementById('tksDirty'), btn = document.getElementById('tksSave'), leaving = false, busy = false;
            function toast(msg, err) {
                var d = document.createElement('div'); if (err) d.className = 'e'; d.textContent = msg;
                document.getElementById('pu-toast').appendChild(d); setTimeout(function () { d.remove(); }, 3800);
            }
            function ser() { var fd = new FormData(form); fd.delete(csrf.name); return new URLSearchParams(fd).toString(); }
            var snap = ser();
            function setDirty() {
                var d = ser() !== snap;
                dirtyEl.classList.toggle('on', d);
                dirtyEl.lastElementChild.textContent = d ? 'Ada perubahan belum disimpan' : 'Semua perubahan tersimpan';
            }
            function count(el) {
                var c = form.querySelector('[data-cnt="' + el.name + '"]'); if (!c) return;
                var max = +el.dataset.max, len = el.value.length;
                c.textContent = len + ' / ' + max; c.className = 'pu-count' + (len >= max ? ' max' : len >= max * 0.9 ? ' warn' : '');
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
                if (busy) return; busy = true;
                var ori = btn.innerHTML; btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan…';
                var fd = new FormData(form); fd.set(csrf.name, csrf.value);
                fetch('<?= site_url('showtks/save') ?>', { method: 'POST', body: fd, credentials: 'same-origin', headers: { Accept: 'application/json' } })
                    .then(function (r) { return r.json().catch(function () { return { ok: false, message: 'Respons server tidak valid (' + r.status + ').' }; }); })
                    .then(function (j) {
                        if (j.csrf) { csrf.name = j.csrf.name; csrf.value = j.csrf.hash; }
                        toast(j.message || 'Selesai.', !j.ok);
                        Object.keys(j.errors || {}).forEach(function (k) {
                            var w = form.querySelector('[data-f="' + k + '"]'); if (!w) return;
                            w.classList.add('err'); w.querySelector('[data-msg]').textContent = j.errors[k];
                        });
                        if (j.ok) { snap = ser(); setDirty(); if (j.reload) { leaving = true; setTimeout(function () { location.reload(); }, 700); } }
                    })
                    .catch(function () { toast('Koneksi bermasalah, data belum tersimpan.', true); })
                    .then(function () { busy = false; btn.disabled = false; btn.innerHTML = ori; });
            }
            form.addEventListener('submit', function (e) { e.preventDefault(); save(); });
            document.addEventListener('keydown', function (e) { if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) { e.preventDefault(); save(); } });
        })();
    </script>
<?php endif; ?>
