<?php
/**
 * Monitoring Pelaporan (admin): matriks BPR × bagian Laporan Tahunan.
 * Variabel: $tahun, $tahunList, $mon ['sections', 'transSections', 'bprs', 'summary', 'generated']
 */
$short = [
    'Riwayatbpr' => 'Riwayat', 'Penjelasannpl' => 'NPL', 'Usahasignifikan' => 'Usaha Sig.', 'Kebijaksanaanmanathn' => 'Strategi',
    'Bidusahathn' => 'Bid. Usaha', 'Titahunan' => 'TI', 'Perkembanganpasar' => 'Pasar', 'Kerjasamabpr' => 'Kerja Sama',
    'Kegiatanpengembangan' => 'Pengemb.', 'Laporanakuntan' => 'Akuntan', 'Suratpernyataandireksi' => 'Surat Pern.',
    'Dokumenpendukung' => 'Dok. Pend.', 'Showtransparansi' => 'Transparansi',
];
$stLabel = ['none' => 'Belum diisi', 'draft' => 'Draf (belum disetujui)', 'komut' => 'Disetujui Komisaris Utama', 'ok' => 'Selesai / disetujui', 'skip' => 'Tidak ada data (opsional)'];
$stIcon = ['none' => 'fa-minus', 'draft' => 'fa-pen', 'komut' => 'fa-user-check', 'ok' => 'fa-check', 'skip' => 'fa-circle'];
$rowLabel = ['noperiode' => ['Belum membuat periode', 'gray'], 'notstarted' => ['Belum mulai', 'red'], 'progress' => ['Dalam proses', 'amber'], 'ready' => ['Siap export', 'green']];
$s = $mon['summary'];
$counts = array_count_values(array_column($mon['bprs'], 'status'));
?>
<link rel="stylesheet" href="<?= base_url('asset/css/transparansi.css') ?>?v=<?= @filemtime(FCPATH . 'asset/css/transparansi.css') ?>">

<div class="container-fluid pu mon">
    <header class="pu-head">
        <div class="pu-head-main">
            <span class="pu-kicker">Admin · Monitoring Pelaporan</span>
            <h1 class="pu-title">Laporan Tahunan <?= (int) $tahun ?></h1>
            <div class="pu-meta">
                <span class="pu-year"><i class="far fa-clock"></i> Data per <?= esc(date('d/m/Y H:i', strtotime($mon['generated']))) ?></span>
            </div>
        </div>
        <form class="mon-tools" method="get" action="<?= site_url('monitoring') ?>">
            <label class="sr-only" for="monTahun">Tahun</label>
            <select id="monTahun" name="tahun" class="pu-input" onchange="this.form.submit()">
                <?php foreach ($tahunList ?: [$tahun] as $t): ?>
                    <option value="<?= $t ?>" <?= $t === $tahun ? 'selected' : '' ?>>Tahun <?= $t ?></option>
                <?php endforeach; ?>
            </select>
            <a class="pu-btn pu-btn-light" href="<?= site_url('monitoring') . '?tahun=' . $tahun . '&refresh=1' ?>" title="Hitung ulang data"><i class="fas fa-sync-alt"></i> Muat ulang</a>
            <a class="pu-btn pu-btn-light" href="<?= site_url('monitoring/csv') . '?tahun=' . $tahun ?>"><i class="fas fa-file-csv"></i> CSV</a>
        </form>
    </header>

    <!-- Ringkasan -->
    <section class="mon-kpis">
        <div class="mon-kpi"><span class="mon-kpi-val"><?= $s['total'] ?></span><span class="mon-kpi-lbl">Total BPR</span></div>
        <div class="mon-kpi"><span class="mon-kpi-val"><?= $s['withPeriode'] ?></span><span class="mon-kpi-lbl">Sudah membuat periode</span></div>
        <div class="mon-kpi"><span class="mon-kpi-val"><?= $s['started'] ?></span><span class="mon-kpi-lbl">Sudah mulai mengisi</span></div>
        <div class="mon-kpi ok"><span class="mon-kpi-val"><?= $s['ready'] ?></span><span class="mon-kpi-lbl">Siap export</span></div>
        <div class="mon-kpi">
            <span class="mon-kpi-val"><?= $s['avg'] ?>%</span><span class="mon-kpi-lbl">Rata-rata progres</span>
            <span class="mon-bar"><span style="width:<?= $s['avg'] ?>%"></span></span>
        </div>
    </section>

    <!-- Filter -->
    <section class="pu-card">
        <div class="pu-card-head mon-filter">
            <div class="mon-chips" role="group" aria-label="Filter status">
                <button type="button" class="mon-chip active" data-f="">Semua <b><?= count($mon['bprs']) ?></b></button>
                <?php foreach ($rowLabel as $k => [$lbl, $c]): ?>
                    <button type="button" class="mon-chip c-<?= $c ?>" data-f="<?= $k ?>"><?= $lbl ?> <b><?= $counts[$k] ?? 0 ?></b></button>
                <?php endforeach; ?>
            </div>
            <input type="search" id="monSearch" class="pu-input mon-search" placeholder="Cari nama / kode BPR…" aria-label="Cari BPR">
        </div>

        <div class="mon-wrap">
            <table class="mon-table" id="monTable">
                <thead>
                    <tr>
                        <th class="mon-bpr">BPR</th>
                        <th class="mon-prog">Progres</th>
                        <?php foreach ($mon['sections'] as $i => $sec): ?>
                            <th title="<?= esc($sec['name'], 'attr') ?>"><span class="mon-no"><?= $i + 1 ?></span><?= esc($short[$sec['link']] ?? $sec['link']) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mon['bprs'] as $idx => $b): [$rl, $rc] = $rowLabel[$b['status']]; ?>
                        <tr class="mon-row" data-status="<?= $b['status'] ?>" data-q="<?= esc(strtolower($b['kodebpr'] . ' ' . $b['nama'] . ' ' . $b['sandi']), 'attr') ?>"
                            data-idx="<?= $idx ?>" tabindex="0" aria-expanded="false">
                            <td class="mon-bpr">
                                <div class="mon-name"><?= esc($b['nama']) ?></div>
                                <div class="mon-sub">Kode <?= esc($b['kodebpr']) ?><?= $b['sandi'] !== '' ? ' · Sandi ' . esc($b['sandi']) : '' ?>
                                    <?php if ($b['periode']): ?> · <?= $b['periode']['kategori'] === 'K' ? 'Koreksi' : 'Rutin' ?><?php endif; ?></div>
                                <span class="mon-badge c-<?= $rc ?>"><?= $rl ?></span>
                            </td>
                            <td class="mon-prog">
                                <?php if ($b['periode']): ?>
                                    <b><?= $b['percent'] ?>%</b> <small><?= $b['done'] ?>/<?= $b['total'] ?></small>
                                    <span class="mon-bar"><span style="width:<?= $b['percent'] ?>%"></span></span>
                                <?php else: ?><small class="text-muted">—</small><?php endif; ?>
                            </td>
                            <?php if (!$b['periode']): ?>
                                <td colspan="<?= count($mon['sections']) ?>" class="mon-noper">Periode tahun <?= (int) $tahun ?> belum dibuat</td>
                            <?php else: foreach ($b['cells'] as $i => $c): ?>
                                <td class="mon-cell st-<?= $c['st'] ?>" title="<?= esc($mon['sections'][$i]['name'] . ': ' . $stLabel[$c['st']] . ($c['note'] !== '' ? ' (' . $c['note'] . ')' : ''), 'attr') ?>">
                                    <i class="fas <?= $stIcon[$c['st']] ?>" aria-hidden="true"></i><?php if ($c['note'] !== ''): ?><small><?= esc($c['note']) ?></small><?php endif; ?>
                                    <span class="sr-only"><?= $stLabel[$c['st']] ?></span>
                                </td>
                            <?php endforeach; endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="pu-empty" id="monEmpty" hidden><i class="fas fa-search"></i>Tidak ada BPR yang cocok dengan filter.</div>
        </div>

        <div class="mon-legend">
            <?php foreach ($stLabel as $k => $lbl): ?>
                <span><i class="mon-cell st-<?= $k ?> fas <?= $stIcon[$k] ?>"></i> <?= $lbl ?></span>
            <?php endforeach; ?>
            <span class="text-muted">· Klik baris untuk melihat 18 bagian Transparansi</span>
        </div>
    </section>
</div>

<template id="monTransTpl">
    <tr class="mon-detail"><td colspan="<?= count($mon['sections']) + 2 ?>"><div class="mon-trans"></div></td></tr>
</template>

<style>
    .mon-tools { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
    .mon-tools .pu-input { width: auto; min-width: 140px; }
    .mon-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px; margin-bottom: 16px; }
    .mon-kpi { background: #fff; border: 1px solid var(--bd); border-radius: 14px; padding: 14px 16px; display: flex; flex-direction: column; gap: 2px; }
    .mon-kpi.ok { border-color: #6ee7b7; background: var(--okbg); }
    .mon-kpi-val { font-size: 1.6rem; font-weight: 800; color: var(--txt); font-variant-numeric: tabular-nums; }
    .mon-kpi-lbl { font-size: .78rem; color: var(--mut); font-weight: 600; }
    .mon-bar { display: block; height: 6px; border-radius: 999px; background: #e2e8f0; overflow: hidden; margin-top: 6px; }
    .mon-bar span { display: block; height: 100%; background: linear-gradient(90deg, #1f2aa8, #059669); border-radius: inherit; }
    .mon-filter { gap: 10px; }
    .mon-chips { display: flex; flex-wrap: wrap; gap: 6px; }
    .mon-chip { border: 1.5px solid var(--bd); background: #fff; border-radius: 999px; padding: 5px 12px; font-size: .8rem; font-weight: 600; color: #475569; cursor: pointer; }
    .mon-chip b { margin-left: 4px; }
    .mon-chip.active { background: var(--c2); border-color: var(--c2); color: #fff; }
    .mon-search { max-width: 260px; }
    .mon-wrap { overflow: auto; max-height: 70vh; }
    .mon-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: .82rem; }
    .mon-table th { position: sticky; top: 0; z-index: 2; background: #f1f5f9; color: #334155; font-size: .7rem; font-weight: 700; text-align: center; padding: 8px 6px; border-bottom: 1px solid var(--bd); white-space: nowrap; }
    .mon-no { display: block; font-size: .65rem; color: var(--mut); }
    .mon-table td { padding: 8px 6px; border-bottom: 1px solid #f1f5f9; text-align: center; vertical-align: middle; }
    .mon-table .mon-bpr { position: sticky; left: 0; z-index: 1; background: #fff; text-align: left; min-width: 230px; max-width: 280px; }
    .mon-table th.mon-bpr { z-index: 3; background: #f1f5f9; }
    .mon-row { cursor: pointer; }
    .mon-row:hover td, .mon-row:focus td { background: #f8fafc; }
    .mon-row:focus { outline: 2px solid #a5b4fc; outline-offset: -2px; }
    .mon-name { font-weight: 700; color: var(--txt); }
    .mon-sub { font-size: .72rem; color: var(--mut); }
    .mon-badge { display: inline-block; margin-top: 4px; font-size: .68rem; font-weight: 700; padding: 1px 8px; border-radius: 999px; }
    .c-gray { background: #f1f5f9; color: #64748b; } .c-red { background: #fee2e2; color: #b91c1c; }
    .c-amber { background: #fef3c7; color: #92400e; } .c-green { background: #d1fae5; color: #065f46; }
    .mon-chip.c-gray.active, .mon-chip.c-red.active, .mon-chip.c-amber.active, .mon-chip.c-green.active { color: #fff; }
    .mon-chip.c-red.active { background: #dc2626; border-color: #dc2626; } .mon-chip.c-amber.active { background: #d97706; border-color: #d97706; }
    .mon-chip.c-green.active { background: #059669; border-color: #059669; } .mon-chip.c-gray.active { background: #64748b; border-color: #64748b; }
    .mon-prog { min-width: 110px; text-align: left !important; }
    .mon-noper { color: #94a3b8; font-style: italic; text-align: left !important; }
    .mon-cell { min-width: 64px; }
    .mon-cell i { width: 26px; height: 26px; line-height: 26px; border-radius: 8px; font-size: .72rem; display: inline-block; text-align: center; }
    .mon-cell small { display: block; font-size: .66rem; color: var(--mut); }
    .st-none i, i.st-none { background: #f1f5f9; color: #94a3b8; }
    .st-draft i, i.st-draft { background: #fef3c7; color: #b45309; }
    .st-komut i, i.st-komut { background: #dbeafe; color: #1d4ed8; }
    .st-ok i, i.st-ok { background: #d1fae5; color: #047857; }
    .st-skip i, i.st-skip { background: #ecfdf5; color: #a7f3d0; }
    .mon-legend { display: flex; flex-wrap: wrap; gap: 6px 16px; padding: 12px 20px 16px; font-size: .76rem; color: #334155; border-top: 1px solid var(--bd); }
    .mon-legend i.mon-cell { min-width: 0; width: 20px; height: 20px; line-height: 20px; border-radius: 6px; font-size: .6rem; text-align: center; display: inline-block; }
    .mon-detail td { background: #f8fafc; text-align: left; }
    .mon-trans { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 6px 14px; padding: 4px 8px 8px; }
    .mon-trans div { display: flex; gap: 8px; align-items: center; font-size: .76rem; color: #334155; }
    .mon-trans i { flex: 0 0 22px; width: 22px; height: 22px; line-height: 22px; border-radius: 6px; font-size: .62rem; text-align: center; }
    @media (max-width: 576px) { .mon-search { max-width: none; width: 100%; } .mon-table .mon-bpr { min-width: 170px; } }
</style>

<script>
    (function () {
        'use strict';
        var DATA = <?= json_encode(['trans' => array_column($mon['bprs'], 'trans'), 'names' => array_column($mon['transSections'], 'name')], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
        var ICON = <?= json_encode($stIcon) ?>, LBL = <?= json_encode($stLabel) ?>;
        var table = document.getElementById('monTable'), rows = [].slice.call(table.querySelectorAll('.mon-row'));
        var search = document.getElementById('monSearch'), empty = document.getElementById('monEmpty');
        var filter = '', t;

        function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
        function apply() {
            var q = search.value.trim().toLowerCase(), shown = 0;
            rows.forEach(function (r) {
                var ok = (!filter || r.dataset.status === filter) && (!q || r.dataset.q.indexOf(q) >= 0);
                r.hidden = !ok;
                var d = r.nextElementSibling;
                if (d && d.classList.contains('mon-detail')) d.hidden = !ok;
                shown += ok ? 1 : 0;
            });
            empty.hidden = shown > 0;
        }
        document.querySelectorAll('.mon-chip').forEach(function (c) {
            c.addEventListener('click', function () {
                document.querySelectorAll('.mon-chip').forEach(function (x) { x.classList.remove('active'); });
                c.classList.add('active');
                filter = c.dataset.f;
                apply();
            });
        });
        search.addEventListener('input', function () { clearTimeout(t); t = setTimeout(apply, 150); });

        // Detail 18 bagian Transparansi (dibangun saat pertama dibuka)
        function toggle(r) {
            var next = r.nextElementSibling;
            if (next && next.classList.contains('mon-detail')) {
                next.remove();
                r.setAttribute('aria-expanded', 'false');
                return;
            }
            var st = DATA.trans[+r.dataset.idx] || [];
            if (!st.length) return;
            var tr = document.getElementById('monTransTpl').content.firstElementChild.cloneNode(true);
            tr.querySelector('.mon-trans').innerHTML = st.map(function (s, i) {
                return '<div title="' + esc(LBL[s]) + '"><i class="fas ' + ICON[s] + ' st-' + s + '"></i>' + (i + 1) + '. ' + esc(DATA.names[i]) + '</div>';
            }).join('');
            r.after(tr);
            r.setAttribute('aria-expanded', 'true');
        }
        rows.forEach(function (r) {
            r.addEventListener('click', function () { toggle(r); });
            r.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(r); } });
        });
    })();
</script>
