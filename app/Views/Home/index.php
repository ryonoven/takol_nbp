<?php

$p = $progress;
$progressPct = $p['total'] ? (int) round($p['done'] / $p['total'] * 100) : 0;
$totalPeriode = array_sum(array_column($periodes, 'count'));

$latestLabel = static function (?array $l, bool $semester): string {
    if (!$l) {
        return 'Belum ada periode';
    }
    return 'Terakhir: ' . ($semester ? 'Semester ' . (int) $l['semester'] . ' ' : 'Tahun ') . esc($l['tahun']);
};

$reports = [
    [
        'key' => 'semesteran', 'semester' => true, 'color' => 'green', 'icon' => 'fa-clipboard-check',
        'title' => 'Laporan Semesteran', 'url' => base_url('Periodesemesteran'),
        'items' => ['Self Assessment Tata Kelola', 'Pokok Pelaksanaan Tugas Direksi Fungsi Kepatuhan', 'Pelaksanaan & Pokok Hasil Audit Intern', 'Self Assessment Tingkat Kesehatan Bank'],
    ],
    [
        'key' => 'tahunan', 'semester' => false, 'color' => 'blue', 'icon' => 'fa-chart-line',
        'title' => 'Laporan Tahunan', 'url' => base_url('periodetahunan'),
        'items' => ['Laporan Tahunan BPR', 'Laporan Transparansi Tata Kelola', 'Laporan Keberlanjutan'],
    ],
    [
        'key' => 'saf', 'semester' => true, 'color' => 'red', 'icon' => 'fa-user-secret',
        'title' => 'Laporan Strategi Anti Fraud', 'url' => base_url('Periodesaf'),
        'items' => ['Penerapan Strategi Anti Fraud', 'Pelaporan per semester'],
    ],
];

/** Lingkaran progres SVG (r = 26 -> keliling ±163.4). */
$ring = static function (int $pct, string $color): string {
    $c = 163.36;
    return '<svg class="db-ring" viewBox="0 0 64 64" aria-hidden="true">'
        . '<circle cx="32" cy="32" r="26" class="db-ring-bg"/>'
        . '<circle cx="32" cy="32" r="26" class="db-ring-fg" style="stroke:' . $color . ';--off:' . round($c * (1 - $pct / 100), 2) . '"/>'
        . '</svg>';
};
?>
<div class="db">
    <!-- Hero -->
    <section class="db-hero">
        <div class="db-hero-inner">
            <div>
                <p class="db-greet" id="dbGreet">Selamat datang</p>
                <h1 class="db-name"><?= esc($nama !== '' ? $nama : 'Pengguna') ?></h1>
                <div class="db-hero-meta">
                    <?php if (!empty($bpr['namabpr'])): ?>
                        <span class="db-chip"><i class="fas fa-university"></i> <?= esc($bpr['namabpr']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($bpr['sandibpr'])): ?>
                        <span class="db-chip"><i class="fas fa-hashtag"></i> Sandi <?= esc($bpr['sandibpr']) ?></span>
                    <?php endif; ?>
                    <span class="db-chip"><i class="far fa-calendar"></i> <span id="dbToday"><?= date('d/m/Y') ?></span></span>
                </div>
            </div>
            <div class="db-hero-brand">
                <span>NBP Simpel</span>
                <small>Sistem Pelaporan Bank Perekonomian Rakyat</small>
            </div>
        </div>
    </section>

    <div class="db-body">
        <?php if ($profilPct < 100): ?>
            <a class="db-alert reveal" href="<?= base_url('infobpr') ?>">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <strong>Lengkapi informasi BPR sebelum memulai pelaporan.</strong>
                    <span>Belum diisi: <?= esc(implode(', ', $profilMissing)) ?></span>
                </div>
                <i class="fas fa-arrow-right db-alert-go"></i>
            </a>
        <?php endif; ?>

        <!-- Ringkasan -->
        <div class="db-stats">
            <a class="db-stat reveal" href="<?= base_url('infobpr') ?>">
                <?= $ring($profilPct, '#6366f1') ?>
                <div>
                    <div class="db-stat-val"><span data-count="<?= $profilPct ?>">0</span>%</div>
                    <div class="db-stat-lbl">Profil BPR</div>
                    <div class="db-stat-sub"><?= $profilPct === 100 ? 'Lengkap' : count($profilMissing) . ' data belum diisi' ?></div>
                </div>
            </a>

            <a class="db-stat reveal" href="<?= base_url('periodetahunan') ?>">
                <?= $ring($progressPct, '#10b981') ?>
                <div>
                    <div class="db-stat-val"><span data-count="<?= (int) $p['done'] ?>">0</span><small>/<?= (int) $p['total'] ?></small></div>
                    <div class="db-stat-lbl">Laporan Tahunan <?= esc((string) ($p['tahun'] ?? '')) ?></div>
                    <div class="db-stat-sub"><?= $p['total'] ? 'bagian disetujui Direktur Utama' : 'Belum ada periode tahunan' ?></div>
                </div>
            </a>

            <a class="db-stat reveal<?= $unreadComments ? ' db-stat-alert' : '' ?>" href="<?= base_url('periodetahunan') ?>">
                <div class="db-stat-icon"><i class="fas fa-comment-dots"></i></div>
                <div>
                    <div class="db-stat-val"><span data-count="<?= (int) $unreadComments ?>">0</span></div>
                    <div class="db-stat-lbl">Komentar Baru</div>
                    <div class="db-stat-sub"><?= $unreadComments ? 'pada laporan tahunan' : 'Semua sudah dibaca' ?></div>
                </div>
            </a>

            <div class="db-stat reveal">
                <div class="db-stat-icon db-stat-icon-amber"><i class="fas fa-layer-group"></i></div>
                <div>
                    <div class="db-stat-val"><span data-count="<?= (int) $totalPeriode ?>">0</span></div>
                    <div class="db-stat-lbl">Periode Pelaporan</div>
                    <div class="db-stat-sub"><?= (int) $periodes['semesteran']['count'] ?> semester · <?= (int) $periodes['tahunan']['count'] ?> tahunan · <?= (int) $periodes['saf']['count'] ?> SAF</div>
                </div>
            </div>
        </div>

        <div class="db-grid">
            <!-- Jenis pelaporan -->
            <section>
                <h2 class="db-h2"><i class="fas fa-folder-open"></i> Jenis Pelaporan</h2>
                <p class="db-lead">Pilih jenis laporan yang hendak Anda isi</p>
                <div class="db-reports">
                    <?php foreach ($reports as $r):
                        $info = $periodes[$r['key']]; ?>
                        <a class="db-report db-<?= $r['color'] ?> reveal" href="<?= $r['url'] ?>">
                            <div class="db-report-top">
                                <div class="db-report-icon"><i class="fas <?= $r['icon'] ?>"></i></div>
                                <span class="db-report-count"><?= (int) $info['count'] ?> periode</span>
                            </div>
                            <h3><?= esc($r['title']) ?></h3>
                            <ul>
                                <?php foreach ($r['items'] as $it): ?>
                                    <li><?= esc($it) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="db-report-foot">
                                <span><?= $latestLabel($info['latest'], $r['semester']) ?></span>
                                <span class="db-go">Mulai <i class="fas fa-arrow-right"></i></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Yang perlu diselesaikan -->
            <aside class="db-todo reveal">
                <h2 class="db-h2"><i class="fas fa-tasks"></i> Perlu Diselesaikan</h2>
                <?php if (!$p['total']): ?>
                    <p class="db-lead">Belum ada periode laporan tahunan.</p>
                    <a class="db-btn" href="<?= base_url('periodetahunan') ?>"><i class="fas fa-plus"></i> Buat periode tahunan</a>
                <?php elseif (!$p['pending']): ?>
                    <div class="db-done"><i class="fas fa-check-circle"></i> Semua bagian Laporan Tahunan <?= esc((string) $p['tahun']) ?> sudah disetujui. Siap di-export.</div>
                <?php else: ?>
                    <p class="db-lead">Laporan Tahunan <?= esc((string) $p['tahun']) ?> — menunggu persetujuan Direktur Utama:</p>
                    <ul class="db-todo-list">
                        <?php foreach (array_slice($p['pending'], 0, 6) as $name): ?>
                            <li><i class="far fa-circle"></i> <?= esc($name) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if (count($p['pending']) > 6): ?>
                        <p class="db-more">+<?= count($p['pending']) - 6 ?> bagian lainnya</p>
                    <?php endif; ?>
                    <div class="db-bar" role="progressbar" aria-valuenow="<?= $progressPct ?>" aria-valuemin="0" aria-valuemax="100">
                        <span style="--w: <?= $progressPct ?>%"></span>
                    </div>
                    <div class="db-bar-lbl"><?= $progressPct ?>% selesai</div>
                <?php endif; ?>
                <small class="db-note">Ringkasan diperbarui tiap 5 menit.</small>
            </aside>
        </div>
    </div>
</div>

<style>
    .db { --ink: #1e293b; --muted: #64748b; --line: #e2e8f0; --brand: #1f2aa8; --brand-d: #141863; color: var(--ink); }

    /* Hero */
    .db-hero { background: linear-gradient(135deg, var(--brand), var(--brand-d)); color: #fff; padding: 32px 24px 84px; position: relative; overflow: hidden; }
    .db-hero::after {
        content: ''; position: absolute; right: -80px; top: -80px; width: 320px; height: 320px; border-radius: 50%;
        background: radial-gradient(circle, rgba(255, 255, 255, .14), transparent 70%); pointer-events: none;
    }
    .db-hero-inner { max-width: 1140px; margin: 0 auto; display: flex; justify-content: space-between; align-items: flex-end; gap: 24px; flex-wrap: wrap; position: relative; z-index: 1; }
    .db-greet { margin: 0; opacity: .85; font-size: .95rem; }
    .db-name { margin: 2px 0 12px; font-size: clamp(1.5rem, 4vw, 2.2rem); font-weight: 800; color: #fff; }
    .db-hero-meta { display: flex; flex-wrap: wrap; gap: 8px; }
    .db-chip { display: inline-flex; align-items: center; gap: 6px; background: rgba(255, 255, 255, .14); border: 1px solid rgba(255, 255, 255, .2); padding: 5px 12px; border-radius: 999px; font-size: .82rem; }
    .db-hero-brand { text-align: right; }
    .db-hero-brand span { display: block; font-size: 1.3rem; font-weight: 800; letter-spacing: .02em; }
    .db-hero-brand small { opacity: .8; }

    .db-body { max-width: 1140px; margin: -60px auto 40px; padding: 0 16px; position: relative; z-index: 2; }

    .db-alert {
        display: flex; align-items: center; gap: 14px; padding: 14px 18px; margin-bottom: 16px; border-radius: 14px;
        background: #fffbeb; border: 1px solid #fde68a; color: #92400e; text-decoration: none; box-shadow: 0 4px 14px rgba(0, 0, 0, .06);
    }
    .db-alert:hover { color: #78350f; text-decoration: none; border-color: #f59e0b; }
    .db-alert > i:first-child { font-size: 1.4rem; color: #f59e0b; }
    .db-alert div { flex: 1; display: flex; flex-direction: column; font-size: .88rem; }
    .db-alert-go { transition: transform .2s; }
    .db-alert:hover .db-alert-go { transform: translateX(4px); }

    /* Ringkasan */
    .db-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 14px; margin-bottom: 28px; }
    .db-stat {
        display: flex; align-items: center; gap: 14px; padding: 18px; background: #fff; border-radius: 16px; border: 1px solid var(--line);
        box-shadow: 0 6px 20px rgba(15, 23, 42, .06); color: inherit; text-decoration: none; transition: transform .2s, box-shadow .2s, border-color .2s;
    }
    a.db-stat:hover { transform: translateY(-3px); box-shadow: 0 12px 28px rgba(15, 23, 42, .1); border-color: #c7d2fe; color: inherit; text-decoration: none; }
    .db-stat-alert { border-color: #fecaca; background: linear-gradient(135deg, #fff, #fff5f5); }
    .db-stat-val { font-size: 1.7rem; font-weight: 800; line-height: 1; font-variant-numeric: tabular-nums; }
    .db-stat-val small { font-size: 1rem; color: var(--muted); font-weight: 600; }
    .db-stat-lbl { font-weight: 700; font-size: .9rem; margin-top: 4px; }
    .db-stat-sub { font-size: .76rem; color: var(--muted); }
    .db-stat-icon { width: 64px; height: 64px; flex-shrink: 0; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; background: #fee2e2; color: #ef4444; }
    .db-stat-icon-amber { background: #fef3c7; color: #d97706; }
    .db-ring { width: 64px; height: 64px; flex-shrink: 0; transform: rotate(-90deg); }
    .db-ring circle { fill: none; stroke-width: 7; }
    .db-ring-bg { stroke: #eef2f7; }
    .db-ring-fg { stroke-linecap: round; stroke-dasharray: 163.36; stroke-dashoffset: 163.36; transition: stroke-dashoffset 1.1s cubic-bezier(.2, .8, .2, 1); }
    .is-in .db-ring-fg { stroke-dashoffset: var(--off); }

    /* Grid utama */
    .db-grid { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 24px; align-items: start; }
    .db-h2 { font-size: 1.15rem; font-weight: 800; margin: 0 0 2px; display: flex; align-items: center; gap: 8px; }
    .db-lead { color: var(--muted); font-size: .88rem; margin: 0 0 14px; }

    .db-reports { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px; }
    .db-report {
        --c: #3b82f6; --c2: #2563eb; --soft: #eff6ff;
        display: flex; flex-direction: column; padding: 18px; background: #fff; border-radius: 16px; border: 1px solid var(--line);
        border-top: 4px solid var(--c); color: inherit; text-decoration: none; transition: transform .2s, box-shadow .2s;
    }
    .db-green { --c: #10b981; --c2: #059669; --soft: #ecfdf5; }
    .db-red { --c: #ef4444; --c2: #dc2626; --soft: #fef2f2; }
    .db-report:hover { transform: translateY(-4px); box-shadow: 0 14px 30px rgba(15, 23, 42, .12); color: inherit; text-decoration: none; }
    .db-report-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
    .db-report-icon {
        width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.35rem;
        background: linear-gradient(135deg, var(--c), var(--c2)); color: #fff; transition: transform .25s;
    }
    .db-report:hover .db-report-icon { transform: scale(1.08) rotate(-4deg); }
    .db-report-count { font-size: .74rem; font-weight: 700; padding: 4px 10px; border-radius: 999px; background: var(--soft); color: var(--c2); }
    .db-report h3 { font-size: 1.05rem; font-weight: 800; margin: 0 0 8px; }
    .db-report ul { margin: 0 0 14px; padding-left: 18px; color: var(--muted); font-size: .82rem; line-height: 1.55; flex: 1; }
    .db-report-foot { display: flex; justify-content: space-between; align-items: center; gap: 8px; padding-top: 12px; border-top: 1px dashed var(--line); font-size: .8rem; color: var(--muted); }
    .db-go { font-weight: 700; color: var(--c2); white-space: nowrap; }
    .db-go i { transition: transform .2s; }
    .db-report:hover .db-go i { transform: translateX(4px); }

    /* Perlu diselesaikan */
    .db-todo { background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 18px; box-shadow: 0 6px 20px rgba(15, 23, 42, .06); }
    .db-todo-list { list-style: none; margin: 0 0 8px; padding: 0; }
    .db-todo-list li { display: flex; gap: 8px; padding: 7px 0; border-bottom: 1px solid #f1f5f9; font-size: .84rem; }
    .db-todo-list li i { color: #f59e0b; margin-top: 3px; }
    .db-more { font-size: .78rem; color: var(--muted); margin: 0 0 10px; }
    .db-bar { height: 8px; border-radius: 999px; background: #eef2f7; overflow: hidden; }
    .db-bar span { display: block; height: 100%; width: 0; border-radius: inherit; background: linear-gradient(90deg, #10b981, #059669); transition: width 1.1s cubic-bezier(.2, .8, .2, 1); }
    .is-in .db-bar span { width: var(--w); }
    .db-bar-lbl { font-size: .76rem; color: var(--muted); margin-top: 4px; }
    .db-done { padding: 12px; border-radius: 12px; background: #ecfdf5; color: #065f46; font-size: .86rem; font-weight: 600; }
    .db-btn { display: inline-flex; gap: 6px; align-items: center; padding: 9px 14px; border-radius: 10px; background: var(--brand); color: #fff; font-weight: 600; font-size: .85rem; text-decoration: none; }
    .db-btn:hover { color: #fff; text-decoration: none; background: var(--brand-d); }
    .db-note { display: block; margin-top: 14px; color: #94a3b8; font-size: .72rem; }

    /* Muncul saat terlihat */
    .reveal { opacity: 0; transform: translateY(12px); transition: opacity .5s ease, transform .5s ease; }
    .reveal.is-in { opacity: 1; transform: none; }

    @media (max-width: 991px) {
        .db-grid { grid-template-columns: 1fr; }
        .db-hero-brand { display: none; }
    }
    @media (max-width: 576px) {
        .db-hero { padding: 24px 16px 76px; }
        .db-stat { padding: 14px; }
        .db-stat-val { font-size: 1.45rem; }
    }
    @media (prefers-reduced-motion: reduce) {
        .reveal, .db-ring-fg, .db-bar span { transition: none !important; }
        .db-report:hover, a.db-stat:hover { transform: none; }
    }
</style>

<script>
    (function () {
        'use strict';
        // Salam sesuai jam lokal + tanggal panjang
        var h = new Date().getHours();
        document.getElementById('dbGreet').textContent =
            h < 11 ? 'Selamat pagi,' : h < 15 ? 'Selamat siang,' : h < 18 ? 'Selamat sore,' : 'Selamat malam,';
        document.getElementById('dbToday').textContent =
            new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

        var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        function countUp(root) {
            root.querySelectorAll('[data-count]').forEach(function (el) {
                var to = parseInt(el.dataset.count, 10) || 0;
                if (reduce || to === 0) { el.textContent = to; return; }
                var t0 = null, dur = 900;
                (function step(t) {
                    t0 = t0 || t;
                    var k = Math.min(1, (t - t0) / dur);
                    el.textContent = Math.round(to * (1 - Math.pow(1 - k, 3)));
                    if (k < 1) requestAnimationFrame(step);
                })(performance.now());
            });
        }

        var items = document.querySelectorAll('.reveal');
        if (reduce || !('IntersectionObserver' in window)) {
            items.forEach(function (el) { el.classList.add('is-in'); countUp(el); });
            return;
        }
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                if (!e.isIntersecting) return;
                var el = e.target;
                setTimeout(function () { el.classList.add('is-in'); countUp(el); }, (el.dataset.delay || 0));
                io.unobserve(el);
            });
        }, { threshold: 0.15 });
        items.forEach(function (el, i) { el.dataset.delay = Math.min(i, 8) * 60; io.observe(el); });
    })();
</script>
