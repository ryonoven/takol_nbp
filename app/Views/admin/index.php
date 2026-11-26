<?php
/**
 * Admin — Daftar User.
 * Variabel: $users, $groups (name => label), $bprs (kodebpr => nama), $warnings, $dupRoles, $me
 */
$total = count($users);
$banned = count(array_filter($users, static fn($u) => $u['banned']));
$noRole = array_values(array_filter($users, static fn($u) => !$u['roles']));
$roleColor = ['admin' => 'red', 'dekom' => 'indigo', 'direksi' => 'indigo', 'pe' => 'green', 'auditor' => 'amber'];
$fmtDate = static fn($d) => $d ? date('d/m/Y H:i', strtotime($d)) : '—';
?>
<link rel="stylesheet" href="<?= base_url('asset/css/transparansi.css') ?>?v=<?= @filemtime(FCPATH . 'asset/css/transparansi.css') ?>">
<div id="pu-toast" class="pu-toast" aria-live="polite"></div>

<div class="container-fluid pu usr">
    <?php foreach (['message' => 'success', 'error' => 'danger'] as $k => $cls): ?>
        <?php if ($msg = session()->getFlashdata($k)): ?>
            <div class="alert alert-<?= $cls ?> mt-3"><?= esc($msg) ?></div>
        <?php endif; ?>
    <?php endforeach; ?>

    <header class="pu-head">
        <div class="pu-head-main">
            <span class="pu-kicker">Admin · Manajemen User</span>
            <h1 class="pu-title">Daftar User</h1>
            <div class="pu-meta"><span class="pu-year">Kelola profil, BPR, peran, password, dan status akun</span></div>
        </div>
    </header>

    <section class="mon-kpis">
        <div class="mon-kpi"><span class="mon-kpi-val"><?= $total ?></span><span class="mon-kpi-lbl">Total user</span></div>
        <div class="mon-kpi ok"><span class="mon-kpi-val"><?= $total - $banned ?></span><span class="mon-kpi-lbl">Aktif</span></div>
        <div class="mon-kpi"><span class="mon-kpi-val"><?= $banned ?></span><span class="mon-kpi-lbl">Diblokir</span></div>
        <div class="mon-kpi"><span class="mon-kpi-val"><?= count($noRole) ?></span><span class="mon-kpi-lbl">Tanpa peran</span></div>
        <div class="mon-kpi"><span class="mon-kpi-val"><?= count($warnings) ?></span><span class="mon-kpi-lbl">BPR perlu dicek</span></div>
    </section>

    <?php if ($warnings || $noRole || $dupRoles): ?>
        <details class="pu-card usr-warn" <?= count($warnings) <= 5 ? 'open' : '' ?>>
            <summary><i class="fas fa-exclamation-triangle"></i> Perlu perhatian
                <small>(<?= count($warnings) ?> BPR<?= $noRole ? ', ' . count($noRole) . ' user tanpa peran' : '' ?><?= $dupRoles ? ', ' . $dupRoles . ' baris peran ganda' : '' ?>)</small>
            </summary>
            <div class="pu-card-body">
                <p class="text-muted small mb-2">Setiap BPR sebaiknya memiliki tepat satu Komisaris Utama dan satu Direktur Utama agar alur persetujuan berjalan.</p>
                <ul class="usr-warn-list">
                    <?php foreach ($warnings as $w): ?>
                        <li><b><?= esc($w['kodebpr']) ?></b> <?= esc($w['nama'] ?: '(nama BPR belum diisi)') ?> — <?= esc(implode('; ', $w['msgs'])) ?></li>
                    <?php endforeach; ?>
                    <?php foreach ($noRole as $u): ?>
                        <li>User <a href="<?= base_url('admin/' . $u['id']) ?>"><?= esc($u['username'] ?: $u['email']) ?></a> belum memiliki peran (tidak dapat memakai menu pelaporan).</li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($dupRoles): ?>
                    <div class="usr-dup">
                        <span><?= $dupRoles ?> baris peran tercatat ganda di database (tidak berbahaya, tetapi membuat data tidak rapi).</span>
                        <button type="button" class="pu-btn pu-btn-light" id="usrClean"><i class="fas fa-broom"></i> Rapikan</button>
                    </div>
                <?php endif; ?>
            </div>
        </details>
    <?php endif; ?>

    <section class="pu-card">
        <div class="pu-card-head usr-filter">
            <input type="search" id="fQ" class="pu-input" placeholder="Cari nama, username, email…" aria-label="Cari user">
            <select id="fBpr" class="pu-input" aria-label="Filter BPR">
                <option value="">Semua BPR</option>
                <?php foreach ($bprs as $k => $n): ?><option value="<?= esc($k) ?>"><?= esc($k . ($n ? ' — ' . $n : '')) ?></option><?php endforeach; ?>
            </select>
            <select id="fRole" class="pu-input" aria-label="Filter peran">
                <option value="">Semua peran</option>
                <?php foreach ($groups as $k => $n): ?><option value="<?= esc($k) ?>"><?= esc($n) ?></option><?php endforeach; ?>
                <option value="-">Tanpa peran</option>
            </select>
            <select id="fSt" class="pu-input" aria-label="Filter status">
                <option value="">Semua status</option><option value="active">Aktif</option><option value="banned">Diblokir</option>
            </select>
            <span class="usr-count"><b id="fCount"><?= $total ?></b> user</span>
        </div>

        <div class="usr-wrap">
            <table class="usr-table">
                <thead><tr><th>User</th><th>BPR</th><th>Peran</th><th>Login terakhir</th><th>Status</th><th></th></tr></thead>
                <tbody id="usrBody">
                    <?php foreach ($users as $u): ?>
                        <tr data-q="<?= esc(strtolower($u['fullname'] . ' ' . $u['username'] . ' ' . $u['email']), 'attr') ?>"
                            data-bpr="<?= esc($u['kodebpr'], 'attr') ?>" data-roles="<?= esc(implode(',', $u['roles'] ?: ['-']), 'attr') ?>"
                            data-st="<?= $u['banned'] ? 'banned' : 'active' ?>">
                            <td data-label="User">
                                <a class="usr-name" href="<?= base_url('admin/' . $u['id']) ?>"><?= esc($u['fullname'] ?: $u['username']) ?></a>
                                <?php if ((int) $u['id'] === $me): ?><span class="pu-tag utama">Anda</span><?php endif; ?>
                                <div class="usr-sub">@<?= esc($u['username']) ?> · <?= esc($u['email']) ?></div>
                            </td>
                            <td data-label="BPR"><b><?= esc($u['kodebpr']) ?></b><div class="usr-sub"><?= esc($u['namabpr'] ?: '—') ?></div></td>
                            <td data-label="Peran">
                                <?php foreach ($u['roles'] as $r): ?>
                                    <span class="usr-role c-<?= $roleColor[$r] ?? 'gray' ?>"><?= esc($groups[$r] ?? $r) ?></span>
                                <?php endforeach; ?>
                                <?php if (!$u['roles']): ?><span class="usr-role c-red">Tanpa peran</span><?php endif; ?>
                            </td>
                            <td data-label="Login terakhir"><span class="usr-sub"><?= $fmtDate($u['last_login']) ?></span></td>
                            <td data-label="Status"><span class="mon-badge <?= $u['banned'] ? 'c-red' : 'c-green' ?>"><?= $u['banned'] ? 'Diblokir' : 'Aktif' ?></span></td>
                            <td class="usr-act"><a class="pu-btn pu-btn-light pu-btn-icon" href="<?= base_url('admin/' . $u['id']) ?>"><i class="fas fa-pen"></i> Kelola</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="pu-empty" id="usrEmpty" hidden><i class="fas fa-user-slash"></i>Tidak ada user yang cocok dengan filter.</div>
        </div>
    </section>
    <input type="hidden" id="puCsrf" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
</div>

<style>
    .mon-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 16px; }
    .mon-kpi { background: #fff; border: 1px solid var(--bd); border-radius: 14px; padding: 14px 16px; display: flex; flex-direction: column; }
    .mon-kpi.ok { border-color: #6ee7b7; background: var(--okbg); }
    .mon-kpi-val { font-size: 1.6rem; font-weight: 800; color: var(--txt); }
    .mon-kpi-lbl { font-size: .78rem; color: var(--mut); font-weight: 600; }
    .mon-badge { display: inline-block; font-size: .7rem; font-weight: 700; padding: 2px 9px; border-radius: 999px; }
    .c-gray { background: #f1f5f9; color: #475569; } .c-red { background: #fee2e2; color: #b91c1c; } .c-green { background: #d1fae5; color: #065f46; }
    .c-amber { background: #fef3c7; color: #92400e; } .c-indigo { background: #e0e7ff; color: #3730a3; }
    .usr-warn summary { cursor: pointer; padding: 14px 20px; font-weight: 700; color: #92400e; list-style: none; }
    .usr-warn summary small { font-weight: 600; color: var(--mut); }
    .usr-warn[open] summary { border-bottom: 1px solid var(--bd); }
    .usr-warn-list { margin: 0; padding-left: 18px; font-size: .84rem; color: #334155; max-height: 260px; overflow: auto; }
    .usr-warn-list li { margin-bottom: 4px; }
    .usr-dup { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px; margin: 12px 0 8px; padding: 10px 12px; background: #f8fafc; border-radius: 10px; font-size: .82rem; }
    .usr-filter { gap: 8px; }
    .usr-filter .pu-input { width: auto; flex: 1 1 170px; }
    .usr-count { font-size: .82rem; color: var(--mut); white-space: nowrap; }
    .usr-wrap { overflow-x: auto; }
    .usr-table { width: 100%; border-collapse: collapse; font-size: .85rem; }
    .usr-table th { background: #f1f5f9; color: #334155; font-size: .72rem; font-weight: 700; text-align: left; padding: 9px 12px; white-space: nowrap; }
    .usr-table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
    .usr-table tbody tr:hover td { background: #f8fafc; }
    .usr-name { font-weight: 700; color: var(--txt); }
    .usr-sub { font-size: .74rem; color: var(--mut); word-break: break-all; }
    .usr-role { display: inline-block; margin: 0 4px 4px 0; font-size: .7rem; font-weight: 700; padding: 2px 8px; border-radius: 999px; }
    .usr-act { text-align: right; white-space: nowrap; }
    @media (max-width: 767px) {
        .usr-table thead { display: none; }
        .usr-table, .usr-table tbody, .usr-table tr, .usr-table td { display: block; width: 100%; }
        .usr-table tr { border: 1px solid var(--bd); border-radius: 12px; margin-bottom: 10px; padding: 6px 4px; }
        .usr-table td { border: none; padding: 4px 10px; }
        .usr-table td[data-label]::before { content: attr(data-label); display: block; font-size: .68rem; color: var(--mut); font-weight: 700; text-transform: uppercase; }
        .usr-act { text-align: left; }
    }
</style>

<script>
    (function () {
        'use strict';
        var rows = [].slice.call(document.querySelectorAll('#usrBody tr'));
        var q = document.getElementById('fQ'), bpr = document.getElementById('fBpr'), role = document.getElementById('fRole'), st = document.getElementById('fSt');
        var t;
        function apply() {
            var s = q.value.trim().toLowerCase(), b = bpr.value, r = role.value, x = st.value, n = 0;
            rows.forEach(function (tr) {
                var ok = (!s || tr.dataset.q.indexOf(s) >= 0) && (!b || tr.dataset.bpr === b)
                    && (!r || tr.dataset.roles.split(',').indexOf(r) >= 0) && (!x || tr.dataset.st === x);
                tr.hidden = !ok; n += ok ? 1 : 0;
            });
            document.getElementById('fCount').textContent = n;
            document.getElementById('usrEmpty').hidden = n > 0;
        }
        q.addEventListener('input', function () { clearTimeout(t); t = setTimeout(apply, 150); });
        [bpr, role, st].forEach(function (el) { el.addEventListener('change', apply); });

        var clean = document.getElementById('usrClean');
        if (clean) clean.addEventListener('click', function () {
            if (!confirm('Hapus baris peran yang tercatat ganda? Peran setiap user tidak berubah.')) return;
            clean.disabled = true;
            var csrf = document.getElementById('puCsrf'), fd = new FormData();
            fd.set(csrf.name, csrf.value);
            fetch('<?= site_url('admin/cleanroles') ?>', { method: 'POST', body: fd, credentials: 'same-origin', headers: { Accept: 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (j) { alert(j.message || 'Selesai.'); location.reload(); })
                .catch(function () { alert('Koneksi bermasalah.'); clean.disabled = false; });
        });
    })();
</script>
