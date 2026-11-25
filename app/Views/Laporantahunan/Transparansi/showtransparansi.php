<?php
$total = count($sections);
$pct = $total ? (int) round($doneCount / $total * 100) : 0;
?>

<a href="<?= base_url('Showtahunan') ?>" class="btn-kembali">← Kembali ke Pelaporan Tahunan</a>

<?php if (session()->getFlashdata('message')): ?>
    <div class="flash flash-success">
        <?= esc(session()->getFlashdata('message')) ?>
        <button type="button" onclick="this.parentElement.remove()">×</button>
    </div>
<?php endif; ?>
<?php foreach (['err', 'error'] as $k): ?>
    <?php if (session()->getFlashdata($k)): ?>
        <div class="flash flash-error"><?= esc(session()->getFlashdata($k)) ?></div>
    <?php endif; ?>
<?php endforeach; ?>

<div class="page-header">
    <h1 class="page-title"><?= esc($judul) ?></h1>
    <div class="page-subtitle">Periode Tahun Pelaporan: <?= esc($tahun) ?></div>
    <span class="badge-namabpr"><?= esc($namabpr ?? 'Nama BPR Tidak Ditemukan') ?></span>
</div>

<div class="tr-wrap">

    <!-- Ringkasan progres -->
    <div class="tr-summary">
        <div class="tr-summary-top">
            <div>
                <div class="tr-summary-label">Kelengkapan Persetujuan Direktur Utama</div>
                <div class="tr-summary-count"><b><?= $doneCount ?></b> / <?= $total ?> bagian siap</div>
            </div>
            <div class="tr-summary-pct <?= $canExport ? 'ok' : '' ?>"><?= $pct ?>%</div>
        </div>
        <div class="tr-progress"><div class="tr-progress-bar <?= $canExport ? 'ok' : '' ?>" style="width: <?= $pct ?>%;"></div></div>
        <div class="tr-legend">
            <span><i class="dot ok"></i> Disetujui</span>
            <span><i class="dot no"></i> Belum disetujui</span>
            <span><i class="dot skip"></i> Opsional, tidak ada data</span>
        </div>
    </div>

    <!-- Daftar bagian -->
    <div class="tr-grid">
        <?php foreach ($sections as $s):
            $state = $s['skip'] ? 'skip' : ($s['done'] ? 'ok' : 'no'); ?>
            <a href="<?= esc(base_url($s['controller']), 'attr') ?>" class="tr-card <?= $state ?>">
                <div class="tr-card-head">
                    <span class="tr-no"><?= $s['no'] ?></span>
                    <span class="tr-name"><?= esc($s['name']) ?></span>
                </div>
                <div class="tr-chips">
                    <?php if ($s['skip']): ?>
                        <span class="chip skip">— Opsional · tidak perlu diisi jika tidak ada data</span>
                    <?php else: ?>
                        <span class="chip <?= $s['komut'] ? 'ok' : 'no' ?>"><?= $s['komut'] ? '✓' : '○' ?> Komisaris Utama</span>
                        <span class="chip <?= $s['dirut'] ? 'ok' : 'no' ?>"><?= $s['dirut'] ? '✓' : '○' ?> Direktur Utama</span>
                    <?php endif; ?>
                    <?php if ($s['optional'] && $s['hasData']): ?>
                        <span class="chip tag">Opsional</span>
                    <?php endif; ?>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Status export -->
    <?php if ($pending): ?>
        <div class="status-alert warning">
            <div class="status-title">⚠ ZIP belum dapat dibuat. Persetujuan Direktur Utama belum lengkap pada:</div>
            <ol class="status-list">
                <?php foreach ($pending as $p): ?>
                    <li><a href="<?= esc(base_url($p['controller']), 'attr') ?>"><?= $p['no'] ?>. <?= esc($p['name']) ?></a></li>
                <?php endforeach; ?>
            </ol>
        </div>
    <?php else: ?>
        <div class="status-alert success">✓ Seluruh bagian wajib telah disetujui Direktur Utama. ZIP siap dibuat.</div>
    <?php endif; ?>

    <div class="generate-section">
        <p class="generate-note">Cek kembali seluruh data, pastikan informasi BPR dan data yang diperlukan telah terisi
            dengan benar</p>
        <?php if ($canExport): ?>
            <a href="<?= base_url('ShowTransparansi/exportAllToZip') ?>" class="btn-generate" id="btnExportZip">
                GENERATE TXT (.ZIP)
            </a>
        <?php else: ?>
            <span class="btn-generate disabled" aria-disabled="true">GENERATE TXT (.ZIP)</span>
        <?php endif; ?>
    </div>
</div>

<script>
    // Cegah klik ganda saat ZIP sedang disiapkan
    (function () {
        var btn = document.getElementById('btnExportZip');
        if (!btn) return;
        btn.addEventListener('click', function () {
            var label = btn.textContent;
            btn.textContent = 'Sedang menyiapkan file…';
            btn.classList.add('busy');
            setTimeout(function () { btn.textContent = label; btn.classList.remove('busy'); }, 8000);
        });
    })();
</script>

<style>
    :root {
        --blue: #3b82f6;
        --green: #10b981;
        --green-d: #059669;
        --red: #ef4444;
        --amber: #f59e0b;
        --gray-50: #f8fafc;
        --gray-100: #f1f5f9;
        --gray-200: #e2e8f0;
        --gray-400: #94a3b8;
        --gray-600: #475569;
        --gray-800: #1e293b;
        --shadow: 0 2px 12px rgba(0, 0, 0, .08);
        --shadow-md: 0 4px 20px rgba(0, 0, 0, .12);
        --radius: 12px;
        --radius-lg: 16px;
        --primary-gradient: linear-gradient(135deg, #141863 0%, #1f2aa8 100%);
    }

    .btn-kembali {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: white;
        border: 1.5px solid var(--gray-200);
        border-radius: 8px;
        padding: 8px 16px;
        font-size: .9rem;
        font-weight: 600;
        color: var(--gray-600);
        text-decoration: none;
        transition: all .2s;
        margin: 14px 0 12px 16px;
    }

    .btn-kembali:hover {
        background: var(--gray-50);
        border-color: var(--gray-400);
        color: var(--gray-800);
        text-decoration: none;
    }

    .flash {
        margin: 10px 16px;
        padding: 12px 18px;
        border-radius: var(--radius);
        font-size: .88rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .flash-success { background: #d1fae5; color: #065f46; border-left: 4px solid var(--green); }
    .flash-error { background: #fee2e2; color: #991b1b; border-left: 4px solid var(--red); }
    .flash button { background: none; border: none; cursor: pointer; font-size: 1.1rem; color: inherit; opacity: .6; }

    .page-header { text-align: center; padding: 28px 20px 22px; background: white; border-bottom: 1px solid var(--gray-200); }
    .page-title { font-size: clamp(1.25rem, 3.5vw, 1.65rem); font-weight: 800; color: var(--gray-800); margin-bottom: 5px; }
    .page-subtitle { font-size: 1.15rem; font-weight: 700; color: var(--blue); margin-bottom: 10px; }
    .badge-namabpr { display: inline-block; background: var(--primary-gradient); color: white; font-size: .92rem; font-weight: 600; padding: 5px 18px; border-radius: 20px; }

    .tr-wrap { max-width: 1180px; margin: 0 auto; padding: 20px 16px 50px; }

    /* ringkasan */
    .tr-summary { background: white; border: 1px solid var(--gray-200); border-radius: var(--radius-lg); box-shadow: var(--shadow); padding: 18px 22px; margin-bottom: 20px; }
    .tr-summary-top { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 10px; }
    .tr-summary-label { font-size: .8rem; font-weight: 600; color: var(--gray-400); text-transform: uppercase; letter-spacing: .03em; }
    .tr-summary-count { font-size: 1.05rem; color: var(--gray-800); }
    .tr-summary-pct { font-size: 1.6rem; font-weight: 800; color: var(--amber); }
    .tr-summary-pct.ok { color: var(--green-d); }
    .tr-progress { height: 10px; background: var(--gray-100); border-radius: 99px; overflow: hidden; }
    .tr-progress-bar { height: 100%; background: linear-gradient(90deg, #f59e0b, #fbbf24); border-radius: 99px; transition: width .4s; }
    .tr-progress-bar.ok { background: linear-gradient(90deg, #059669, #10b981); }
    .tr-legend { display: flex; flex-wrap: wrap; gap: 16px; margin-top: 10px; font-size: .78rem; color: var(--gray-600); }
    .tr-legend .dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 5px; vertical-align: -1px; }
    .dot.ok { background: var(--green); }
    .dot.no { background: var(--amber); }
    .dot.skip { background: var(--gray-400); }

    /* kartu */
    .tr-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 14px; margin-bottom: 22px; }
    .tr-card {
        position: relative; display: flex; flex-direction: column; gap: 12px;
        background: white; border: 1.5px solid var(--gray-200); border-radius: var(--radius-lg);
        padding: 16px 18px 16px 22px; box-shadow: var(--shadow);
        color: inherit; text-decoration: none; transition: box-shadow .2s, border-color .2s, transform .15s;
    }
    .tr-card::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 5px; border-radius: var(--radius-lg) 0 0 var(--radius-lg); background: var(--amber); }
    .tr-card.ok::before { background: var(--green); }
    .tr-card.skip::before { background: var(--gray-400); }
    .tr-card.ok { border-color: #a7f3d0; }
    .tr-card.skip { background: var(--gray-50); }
    .tr-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); color: inherit; text-decoration: none; border-color: #1f2aa8; }

    .tr-card-head { display: flex; gap: 12px; align-items: flex-start; }
    .tr-no {
        flex-shrink: 0; width: 30px; height: 30px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        background: var(--primary-gradient); color: white; font-weight: 800; font-size: .85rem;
    }
    .tr-card.ok .tr-no { background: linear-gradient(135deg, #059669, #10b981); }
    .tr-card.skip .tr-no { background: var(--gray-400); }
    .tr-name { font-weight: 700; font-size: .92rem; line-height: 1.35; color: var(--gray-800); }

    .tr-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: auto; }
    .chip { font-size: .74rem; font-weight: 600; padding: 4px 9px; border-radius: 6px; }
    .chip.ok { background: rgba(16, 185, 129, .12); color: var(--green-d); }
    .chip.no { background: rgba(245, 158, 11, .14); color: #b45309; }
    .chip.skip { background: var(--gray-100); color: var(--gray-600); font-style: italic; }
    .chip.tag { background: #eef2ff; color: #4338ca; }

    /* status export */
    .status-alert { padding: 14px 18px; border-radius: var(--radius); font-size: .87rem; font-weight: 600; color: white; }
    .status-alert.success { background: linear-gradient(135deg, #065f46, #059669); text-align: center; }
    .status-alert.warning { background: linear-gradient(135deg, #92400e, #d97706); }
    .status-title { margin-bottom: 8px; }
    .status-list { margin: 0; padding-left: 0; list-style: none; display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 4px 18px; }
    .status-list a { color: white; text-decoration: underline; text-decoration-color: rgba(255, 255, 255, .45); font-weight: 500; }
    .status-list a:hover { text-decoration-color: white; }

    .generate-section { text-align: center; padding: 26px 0 10px; }
    .generate-note { font-size: .85rem; color: var(--gray-400); margin-bottom: 16px; }
    .btn-generate {
        display: inline-flex; align-items: center; gap: 8px;
        background: var(--primary-gradient); color: white; text-decoration: none;
        padding: 12px 36px; border-radius: 25px; font-size: 1.05rem; font-weight: 800; letter-spacing: .03em;
        box-shadow: 0 4px 16px rgba(20, 24, 99, .3); transition: all .2s;
    }
    .btn-generate:hover:not(.disabled) { transform: translateY(-3px); box-shadow: 0 6px 22px rgba(20, 24, 99, .4); color: white; text-decoration: none; }
    .btn-generate.busy { opacity: .75; pointer-events: none; }
    .btn-generate.disabled { background: #94a3b8; box-shadow: none; cursor: not-allowed; pointer-events: none; opacity: .7; }

    @media (max-width: 576px) {
        .tr-grid, .status-list { grid-template-columns: 1fr; }
        .tr-summary-pct { font-size: 1.3rem; }
    }

    @media (prefers-reduced-motion: reduce) {
        * { transition-duration: .01ms !important; }
    }
</style>
