<?php

if (!$canEdit) {
    return;
}
$k = $kal ?? [];
$num = static fn($v) => ($v === null || $v === '') ? '' : number_format((float) $v, 0, ',', '.');

$ratios = [
    106 => ['Aset likuid / total aset', 15, true],
    107 => ['Aset likuid / kewajiban lancar', 20, true],
    108 => ['LDR (kredit / DPK)', 90, false],
    109 => ['25 deposan & penabung / DPK', 25, false],
    110 => ['Pendanaan non inti / total pendanaan', 10, false],
];
$has = !empty($k['totalaset']);
$upd = !empty($k['updated_at']) && ($t = strtotime($k['updated_at'])) > 0 ? date('d/m/Y H:i', $t) : '';
?>
<section class="pu-card kk">
    <div class="pu-card-head">
        <h2><i class="fas fa-calculator"></i> Kalkulator Rasio Likuiditas</h2>
        <div class="pu-card-tools">
            <button type="button" class="pu-btn pu-btn-primary pu-btn-icon" data-kal><i class="fas fa-calculator"></i> <?= $has ? 'Ubah data kalkulator' : 'Isi kalkulator' ?></button>
        </div>
    </div>
    <div class="pu-card-body">
        <div class="kk-grid">
            <?php foreach ($ratios as $fid => [$lbl, $th, $ge]):
                $c = $kalRasio[$fid];
                $v = (float) ($k[$c] ?? 0);
                $inRow = isset($rows[$fid][$colR]) && $rows[$fid][$colR] !== null && $has && abs((float) $rows[$fid][$colR] - $v) < 0.005;
                $over = $has && ($ge ? $v < $th : $v > $th);
                ?>
                <a class="kk-r <?= $has ? '' : 'empty' ?> <?= $over ? 'over' : '' ?>" href="#f<?= $fid ?>">
                    <span><?= esc($lbl) ?></span>
                    <b><?= $has ? number_format($v, 2, ',', '.') . '%' : '—' ?></b>
                    <small><?= $ge ? '≥' : '≤' ?> <?= $th ?>%</small>
                    <?php if ($has && !$inRow): ?><small class="warn"><i class="fas fa-exclamation-circle"></i> Belum sama dengan kertas kerja</small><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
        <p class="kk-foot"><?= $upd ? '<i class="far fa-clock"></i> Diperbarui ' . $upd . ' · ' : '' ?>Menyimpan kalkulator otomatis memasukkan rasio ke parameter 1.1–1.5 kertas kerja.</p>
    </div>
</section>

<dialog class="pu-dialog kk-dlg" id="kkDlg" aria-labelledby="kkT">
    <div class="pu-dialog-head"><h3 id="kkT"><i class="fas fa-calculator"></i> Kalkulator Rasio Likuiditas</h3><button type="button" class="pu-dialog-x" data-close aria-label="Tutup">&times;</button></div>
    <form class="kk-form" novalidate>
        <div class="pu-dialog-body">
            <div class="kk-fields">
                <?php foreach ($kalFields as $grp => $fields): ?>
                    <div class="pu-section kk-span"><?= esc($grp) ?></div>
                    <?php foreach ($fields as $f => $lbl): ?>
                        <div class="pu-field" data-f="<?= $f ?>">
                            <label for="kk_<?= $f ?>"><?= esc($lbl) ?></label>
                            <div class="kk-rp"><span>Rp</span><input id="kk_<?= $f ?>" name="<?= $f ?>" class="pu-input" inputmode="numeric" autocomplete="off"
                                value="<?= $num($k[$f] ?? '') ?>" placeholder="0"></div>
                            <div class="pu-foot"><span class="pu-err" data-msg="<?= $f ?>"></span></div>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
            <div class="pu-section">Hasil (dihitung langsung)</div>
            <div class="kk-out" data-out></div>
        </div>
        <div class="pu-dialog-foot">
            <button type="button" class="pu-btn pu-btn-light" data-close>Tutup</button>
            <button type="submit" class="pu-btn pu-btn-primary"><i class="fas fa-file-import"></i> Simpan & masukkan ke kertas kerja</button>
        </div>
    </form>
</dialog>

<style>
    .kk-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 10px; }
    .kk-r { display: flex; flex-direction: column; gap: 2px; padding: 10px 12px; border: 1.5px solid var(--bd); border-radius: 12px; color: inherit; text-decoration: none !important; }
    .kk-r:hover { border-color: #a5b4fc; }
    .kk-r span { font-size: .76rem; color: var(--mut); font-weight: 600; }
    .kk-r b { font-size: 1.15rem; color: var(--c2); font-variant-numeric: tabular-nums; }
    .kk-r small { font-size: .7rem; color: var(--mut); }
    .kk-r.over b { color: #c2410c; }
    .kk-r.empty b { color: #cbd5e1; }
    .kk-r small.warn { color: #b45309; }
    .kk-foot { font-size: .76rem; color: var(--mut); margin: 10px 0 12px; }
    .kk-dlg { width: 760px; }
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
    @media (max-width: 576px) { .kk-fields { grid-template-columns: 1fr; } .kk-grid { grid-template-columns: 1fr 1fr; gap: 8px; } .kk-r b { font-size: 1rem; } }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        'use strict';
        var dlg = document.getElementById('kkDlg'); if (!dlg || !window.PR) return;
        var form = dlg.querySelector('.kk-form');
        var rp = function (n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID'); };
        var pc = function (a, b) { return b > 0 ? Math.round(a / b * 10000) / 100 : 0; };
        function g(n) { var el = form.elements[n]; return el ? +(el.value.replace(/\D/g, '') || 0) : 0; }
        function compute() {
            var al = g('kas') + g('girobanklain') + g('tabunganbanklain');
            var kl = g('kewajibansegera') + g('tabungandpk') + g('depositodpk');
            var dpk = g('tabungandpk') + g('depositodpk');
            var abp = g('tabunganabp') + g('depositoabp');
            var nonInti = g('dpkdiataslps') + abp + g('pinjamananmungkinditarik');
            var out = [['Aset likuid', rp(al)], ['Kewajiban lancar', rp(kl)], ['Total DPK', rp(dpk)], ['Transaksi antar BPR', rp(abp)], ['Pendanaan non inti', rp(nonInti)],
                ['Aset likuid / total aset', pc(al, g('totalaset')), 15, true], ['Aset likuid / kewajiban lancar', pc(al, kl), 20, true],
                ['LDR', pc(g('kreditkyd'), dpk), 90, false], ['25 deposan & penabung / DPK', pc(g('penabung25deposan'), dpk), 25, false],
                ['Pendanaan non inti / total pendanaan', pc(nonInti, g('totalpendanaan')), 10, false]];
            form.querySelector('[data-out]').innerHTML = out.map(function (o) {
                if (o[2] === undefined) return '<div>' + o[0] + '<b>' + o[1] + '</b></div>';
                var v = o[1], over = o[3] ? v < o[2] : v > o[2], sign = o[3] ? '≥ ' : '≤ ';
                return '<div class="r' + (over ? ' over' : '') + '">' + o[0] + '<b>' + v.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '%</b>'
                    + (over ? 'di luar ambang ' : 'ambang ') + sign + o[2] + '%</div>';
            }).join('');
        }
        document.querySelectorAll('[data-kal]').forEach(function (b) {
            b.addEventListener('click', function () { compute(); dlg.showModal(); var i = form.querySelector('input'); if (i) i.focus(); });
        });
        var t;
        form.addEventListener('input', function (e) {
            if (e.target.name) {
                var el = e.target, d = el.value.replace(/\D/g, '').replace(/^0+(?=\d)/, '').slice(0, 16);
                el.value = d ? (+d).toLocaleString('id-ID') : '';
            }
            clearTimeout(t); t = setTimeout(compute, 120);
        });
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var fd = new FormData();
            Array.prototype.forEach.call(form.elements, function (el) { if (el.name) fd.set(el.name, el.value.replace(/\D/g, '')); });
            PR.post('kalkulator', fd, form.querySelector('[type="submit"]'), form);
        });
    });
</script>
