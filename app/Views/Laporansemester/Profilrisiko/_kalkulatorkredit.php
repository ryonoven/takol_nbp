<?php
/**
 * Kalkulator rasio Risiko Kredit Inheren (partial VIEW_EXTRA untuk _risiko.php).
 * Variabel: $kal (baris kalkulator_kredit|null), $kalFields, $canEdit, $rows, $colR, $route
 */
if (!$canEdit) {
    return;
}
$k = $kal ?? [];
$num = static fn($v) => ($v === null || $v === '') ? '' : number_format((float) $v, 0, ',', '.');
$ratios = [
    2 => ['Aset produktif / total aset', 'rasioasetproduktif', 'komposisi'],
    3 => ['Kredit / aset produktif', 'rasiokreditdiberikan', 'komposisi'],
    4 => ['25 debitur terbesar / kredit', 'rasio25debitur', 'komposisi'],
    5 => ['3 sektor ekonomi / kredit', 'rasioekonomi', 'komposisi'],
    7 => ['Aset produktif bermasalah / AP', 'rasioasetproduktifbermasalah', 'kualitas'],
    8 => ['Kredit bermasalah neto / kredit', 'rasiokreditbermasalah', 'kualitas'],
    9 => ['Kredit kualitas rendah / kredit', 'rasiokreditkualitasrendah', 'kualitas'],
];
$hasKomp = !empty($k['totalaset']);
$hasKual = $hasKomp && ((float) ($k['abanpl'] ?? 0) + (float) ($k['kydnpl3'] ?? 0) + (float) ($k['kydnpl4'] ?? 0) + (float) ($k['kydnpl5'] ?? 0)
    + (float) ($k['kreditbermasalah'] ?? 0) + (float) ($k['kreditdpk2'] ?? 0) + (float) ($k['kreditrestruktur1'] ?? 0)) > 0;
$upd = !empty($k['updated_at']) && ($t = strtotime($k['updated_at'])) > 0 ? date('d/m/Y H:i', $t) : '';
?>
<section class="pu-card kk">
    <div class="pu-card-head">
        <h2><i class="fas fa-calculator"></i> Kalkulator Rasio</h2>
        <div class="pu-card-tools">
            <button type="button" class="pu-btn pu-btn-ghost pu-btn-icon" data-kal="komposisi"><i class="fas fa-layer-group"></i> Komposisi Portofolio</button>
            <button type="button" class="pu-btn pu-btn-ghost pu-btn-icon" data-kal="kualitas" <?= $hasKomp ? '' : 'disabled title="Isi Komposisi Portofolio terlebih dahulu"' ?>><i class="fas fa-shield-alt"></i> Kualitas Aset</button>
        </div>
    </div>
    <div class="pu-card-body">
        <div class="kk-grid">
            <?php foreach ($ratios as $fid => [$lbl, $c, $grp]):
                $done = $grp === 'komposisi' ? $hasKomp : $hasKual;
                $inRow = isset($rows[$fid][$colR]) && $rows[$fid][$colR] !== null && $done
                    && abs((float) $rows[$fid][$colR] - (float) ($k[$c] ?? 0)) < 0.005;
                ?>
                <a class="kk-r <?= $done ? '' : 'empty' ?>" href="#f<?= $fid ?>">
                    <span><?= esc($lbl) ?></span>
                    <b><?= $done ? number_format((float) $k[$c], 2, ',', '.') . '%' : '—' ?></b>
                    <?php if ($done && !$inRow): ?><small class="warn"><i class="fas fa-exclamation-circle"></i> Belum sama dengan kertas kerja</small><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
        <p class="kk-foot"><?= $upd ? '<i class="far fa-clock"></i> Diperbarui ' . $upd . ' · ' : '' ?>Menyimpan kalkulator otomatis memasukkan rasio ke parameter kertas kerja.</p>
    </div>
</section>

<dialog class="pu-dialog kk-dlg" id="kkDlg" aria-labelledby="kkT">
    <div class="pu-dialog-head"><h3 id="kkT"><i class="fas fa-calculator"></i> Kalkulator Rasio</h3><button type="button" class="pu-dialog-x" data-close aria-label="Tutup">&times;</button></div>
    <div class="kk-tabs" role="tablist">
        <button type="button" role="tab" data-tab="komposisi">Komposisi Portofolio</button>
        <button type="button" role="tab" data-tab="kualitas" <?= $hasKomp ? '' : 'disabled' ?>>Kualitas Aset</button>
    </div>
    <?php foreach ($kalFields as $type => $fields): ?>
        <form class="kk-form" data-type="<?= $type ?>" novalidate hidden>
            <div class="pu-dialog-body">
                <?php if ($type === 'kualitas'): ?>
                    <div class="pu-note mb-3">Dasar perhitungan (dari Komposisi tersimpan): aset produktif <b>Rp <?= $num($k['asetproduktif'] ?? 0) ?></b> · KYD gross <b>Rp <?= $num($k['kydgross'] ?? 0) ?></b></div>
                <?php endif; ?>
                <div class="kk-fields">
                    <?php foreach ($fields as $f => $lbl): ?>
                        <?php if ($f === 'perdagangan'): ?><div class="pu-section kk-span">Tiga sektor ekonomi terbesar</div><?php endif; ?>
                        <div class="pu-field" data-f="<?= $f ?>">
                            <label for="kk_<?= $f ?>"><?= esc($lbl) ?></label>
                            <div class="kk-rp"><span>Rp</span><input id="kk_<?= $f ?>" name="<?= $f ?>" class="pu-input" inputmode="numeric" autocomplete="off"
                                value="<?= $num($k[$f] ?? '') ?>" placeholder="0"></div>
                            <div class="pu-foot"><span class="pu-err" data-msg="<?= $f ?>"></span></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="pu-section">Hasil (dihitung langsung)</div>
                <div class="kk-out" data-out="<?= $type ?>"></div>
            </div>
            <div class="pu-dialog-foot">
                <button type="button" class="pu-btn pu-btn-light" data-close>Tutup</button>
                <button type="submit" class="pu-btn pu-btn-primary"><i class="fas fa-file-import"></i> Simpan & masukkan ke kertas kerja</button>
            </div>
        </form>
    <?php endforeach; ?>
</dialog>

<style>
    .kk-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 10px; }
    .kk-r { display: flex; flex-direction: column; gap: 2px; padding: 10px 12px; border: 1.5px solid var(--bd); border-radius: 12px; color: inherit; text-decoration: none !important; }
    .kk-r:hover { border-color: #a5b4fc; }
    .kk-r span { font-size: .76rem; color: var(--mut); font-weight: 600; }
    .kk-r b { font-size: 1.15rem; color: var(--c2); font-variant-numeric: tabular-nums; }
    .kk-r.empty b { color: #cbd5e1; }
    .kk-r small.warn { font-size: .7rem; color: #b45309; }
    .kk-foot { font-size: .76rem; color: var(--mut); margin: 10px 0 12px; }
    .kk-dlg { width: 760px; }
    .kk-tabs { display: flex; gap: 4px; padding: 10px 20px 0; border-bottom: 1px solid var(--bd); }
    .kk-tabs button { border: none; background: none; padding: 9px 14px; font-weight: 700; font-size: .85rem; color: var(--mut); border-bottom: 3px solid transparent; cursor: pointer; }
    .kk-tabs button.on { color: var(--c2); border-color: var(--c2); }
    .kk-tabs button:disabled { opacity: .45; cursor: not-allowed; }
    .kk-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 0 14px; }
    .kk-span { grid-column: 1 / -1; }
    .kk-rp { display: flex; align-items: stretch; }
    .kk-rp span { display: flex; align-items: center; padding: 0 10px; background: #f1f5f9; border: 1.5px solid var(--bd); border-right: none; border-radius: 10px 0 0 10px; font-size: .8rem; font-weight: 700; color: var(--mut); }
    .kk-rp .pu-input { border-radius: 0 10px 10px 0; text-align: right; font-variant-numeric: tabular-nums; }
    .kk-out { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 8px; margin-bottom: 14px; }
    .kk-out div { background: #f8fafc; border: 1px solid var(--bd); border-radius: 10px; padding: 8px 11px; font-size: .78rem; color: var(--mut); }
    .kk-out div b { display: block; font-size: 1rem; color: var(--txt); font-variant-numeric: tabular-nums; }
    .kk-out div.r b { color: var(--c2); }
    .kk-out div.over b { color: #c2410c; }
    @media (max-width: 576px) { .kk-fields { grid-template-columns: 1fr; } .kk-grid { grid-template-columns: 1fr 1fr; gap: 8px; } .kk-r b { font-size: 1rem; } .kk-tabs { padding: 8px 10px 0; } }
</style>

<script>
    // Dijalankan setelah skrip utama _risiko.php (yang mendefinisikan window.PR) dimuat
    document.addEventListener('DOMContentLoaded', function () {
        'use strict';
        var dlg = document.getElementById('kkDlg'); if (!dlg || !window.PR) return;
        var BASE = { ap: <?= (float) ($k['asetproduktif'] ?? 0) ?>, kyd: <?= (float) ($k['kydgross'] ?? 0) ?> };
        var TH = { 2: 95, 3: 75, 4: 20, 5: 85, 7: 7, 8: 5, 9: 7 };
        var rp = function (n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID'); };
        var pc = function (a, b) { return b > 0 ? Math.round(a / b * 10000) / 100 : 0; };
        function val(form, n) { var el = form.elements[n]; return el ? +(el.value.replace(/\D/g, '') || 0) : 0; }
        function compute(form) {
            var t = form.dataset.type, g = function (n) { return val(form, n); }, out = [];
            if (t === 'komposisi') {
                var kyd = g('kydbank') + g('kydpihak3'), ap = g('aba') + kyd, sek = g('perdagangan') + g('jasa') + g('konsumsirumah');
                out = [['KYD gross', rp(kyd)], ['Aset produktif', rp(ap)], ['Total 3 sektor', rp(sek)],
                    ['Rasio aset produktif', pc(ap, g('totalaset')), 2], ['Rasio kredit diberikan', pc(kyd, ap), 3],
                    ['Rasio 25 debitur', pc(g('total25debitur'), kyd), 4], ['Rasio sektor ekonomi', pc(sek, kyd), 5]];
            } else {
                var kol = g('kydnpl3') + g('kydnpl4') + g('kydnpl5'), apb = g('abanpl') + kol;
                out = [['KYD kolektibilitas 3–5', rp(kol)], ['Aset produktif bermasalah', rp(apb)],
                    ['Rasio AP bermasalah', pc(apb, BASE.ap), 7], ['Rasio kredit bermasalah', pc(g('kreditbermasalah'), BASE.kyd), 8],
                    ['Rasio kredit kualitas rendah', pc(g('kreditdpk2') + kol + g('kreditrestruktur1'), BASE.kyd), 9]];
            }
            form.querySelector('[data-out]').innerHTML = out.map(function (o) {
                if (o[2] === undefined) return '<div>' + o[0] + '<b>' + o[1] + '</b></div>';
                var v = o[1], over = v > TH[o[2]];
                return '<div class="r' + (over ? ' over' : '') + '">' + o[0] + '<b>' + v.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '%</b>'
                    + (over ? 'di atas ambang ' + TH[o[2]] + '%' : 'ambang ≤ ' + TH[o[2]] + '%') + '</div>';
            }).join('');
        }
        function tab(t) {
            dlg.querySelectorAll('[data-tab]').forEach(function (b) { b.classList.toggle('on', b.dataset.tab === t); b.setAttribute('aria-selected', b.dataset.tab === t); });
            dlg.querySelectorAll('.kk-form').forEach(function (f) { f.hidden = f.dataset.type !== t; if (!f.hidden) compute(f); });
        }
        dlg.querySelectorAll('[data-tab]').forEach(function (b) { b.addEventListener('click', function () { if (!b.disabled) tab(b.dataset.tab); }); });
        document.querySelectorAll('[data-kal]').forEach(function (b) {
            b.addEventListener('click', function () { tab(b.dataset.kal); dlg.showModal(); var i = dlg.querySelector('.kk-form:not([hidden]) input'); if (i) i.focus(); });
        });
        dlg.querySelectorAll('.kk-form').forEach(function (form) {
            var t;
            form.addEventListener('input', function (e) {
                if (e.target.name) {
                    var el = e.target, d = el.value.replace(/\D/g, '').replace(/^0+(?=\d)/, '').slice(0, 16);
                    el.value = d ? (+d).toLocaleString('id-ID') : '';
                }
                clearTimeout(t); t = setTimeout(function () { compute(form); }, 120);
            });
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var fd = new FormData(); fd.set('type', form.dataset.type);
                Array.prototype.forEach.call(form.elements, function (el) { if (el.name) fd.set(el.name, el.value.replace(/\D/g, '')); });
                PR.post('kalkulator', fd, form.querySelector('[type="submit"]'), form);
            });
        });
    });
</script>
