<?php
/**
 * Ringkasan Pelaporan Strategi Anti Fraud.
 * Variabel: $namabpr, $semester, $tahun, $jenis, $periodeId, $sections, $canExport, $hasLaporanPdf, $unreadComments
 */
$roman = ['I', 'II', 'III'];
$themes = [['#ef4444', '#b91c1c', '#fef2f2'], ['#4f46e5', '#3730a3', '#eef2ff']];
$pending = array_column(array_filter($sections, static fn($s) => !$s['done']), 'name');
$jenisLabel = ['SAFTS' => 'Semesteran', 'SAFTA' => 'Tahunan', 'SAFTI3' => 'Insidental 3HK', 'SAFTI6' => 'Insidental 6HK'][$jenis] ?? $jenis;
$asset = static fn(string $path) => base_url($path) . '?v=' . @filemtime(FCPATH . $path);
?>
<div class="sf">
    <a href="<?= base_url('Periodesaf') ?>" class="sf-back"><i class="fas fa-arrow-left"></i> Kembali ke halaman periode</a>

    <?php if ($m = session()->getFlashdata('message')): ?>
        <div class="sf-flash ok"><i class="fas fa-check-circle"></i> <?= esc($m) ?></div>
    <?php endif; ?>
    <?php foreach (['error', 'err'] as $k): ?>
        <?php if ($m = session()->getFlashdata($k)): ?>
            <div class="sf-flash err"><i class="fas fa-exclamation-circle"></i> <?= esc($m) ?></div>
        <?php endif; ?>
    <?php endforeach; ?>

    <header class="sf-head">
        <h1><?= esc($judul) ?></h1>
        <div class="sf-sub">Semester <?= esc($semester) ?> Tahun <?= esc($tahun) ?> · <?= esc($jenisLabel) ?></div>
        <span class="sf-bpr"><?= esc($namabpr ?? 'Nama BPR belum diisi') ?></span>
    </header>

    <?php if (!$namabpr): ?>
        <a class="sf-warn" href="<?= base_url('infobpr') ?>"><i class="fas fa-exclamation-triangle"></i> Informasi BPR belum lengkap. Klik untuk mengisi nama dan sandi BPR.</a>
    <?php endif; ?>

    <div class="sf-cards">
        <?php foreach ($sections as $i => $s):
            [$c, $c2, $soft] = $themes[$i % count($themes)];
            $unread = (int) ($unreadComments[$s['key']] ?? 0); ?>
            <a href="<?= esc($s['link'], 'attr') ?>" class="sf-card<?= $s['done'] ? ' done' : '' ?>" style="--c:<?= $c ?>;--c2:<?= $c2 ?>;--soft:<?= $soft ?>">
                <div class="sf-card-body">
                    <div class="sf-card-title"><?= $roman[$i] ?? $i + 1 ?>. <?= esc($s['name']) ?></div>
                    <div class="sf-card-meta">Semester <?= esc($semester) ?> Tahun <?= esc($tahun) ?> · <?= esc($namabpr ?? '') ?></div>
                    <div class="sf-chips">
                        <?php if (!$s['hasData']): ?>
                            <span class="sf-chip todo"><i class="fas fa-pen"></i> Belum diisi</span>
                        <?php elseif (!$s['levels']): ?>
                            <span class="sf-chip <?= $s['done'] ? 'ok' : 'no' ?>"><?= $s['done'] ? '✓ Selesai' : '○ Menunggu persetujuan' ?></span>
                        <?php else: ?>
                            <?php foreach ($s['levels'] as $lv): ?>
                                <span class="sf-chip <?= $lv['ok'] ? 'ok' : 'no' ?>"><?= $lv['ok'] ? '✓' : '○' ?> <?= esc($lv['label']) ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <?php if ($unread): ?>
                            <span class="sf-chip new"><i class="fas fa-comment-dots"></i> <?= $unread ?> komentar baru</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="sf-card-icon" aria-hidden="true"><i class="fas <?= esc($s['icon']) ?>"></i></div>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="sf-status <?= $canExport ? 'ok' : 'warn' ?>">
        <?php if ($canExport): ?>
            <i class="fas fa-check-circle"></i> Seluruh bagian laporan SAF telah disetujui dan siap diekspor.
        <?php else: ?>
            <i class="fas fa-exclamation-triangle"></i> ZIP belum dapat dibuat. Persetujuan belum lengkap untuk: <?= esc(implode('; ', $pending)) ?>.
        <?php endif; ?>
    </div>

    <?php if ($hasLaporanPdf): ?>
        <div class="sf-pdfs">
            <a href="<?= base_url('Laporanpdfsaf/pdf/' . $periodeId) ?>" target="_blank" rel="noopener" class="sf-pdf">
                <i class="fas fa-file-pdf"></i> Laporan Perkembangan Pelaksanaan Penerapan SAF
            </a>
            <a href="<?= base_url('Laporanpdfsaf/pdffraud/' . $periodeId) ?>" target="_blank" rel="noopener" class="sf-pdf">
                <i class="fas fa-file-pdf"></i> Laporan Deskripsi Fraud / Modus Operandi
            </a>
        </div>
    <?php endif; ?>

    <div class="sf-export">
        <p>Cek kembali seluruh data sebelum mengekspor laporan SAF.</p>
        <?php if ($canExport): ?>
            <a href="<?= base_url('Showsaf/exportZip') ?>" class="sf-btn" data-export-zip
                data-stages='[[0,"Memeriksa persetujuan…"],[15,"Membuat TXT Penerapan SAF…"],[40,"Membuat PDF laporan SAF…"],[80,"Mengemas file ZIP…"]]'>
                <i class="fas fa-file-archive"></i> EXPORT LAPORAN SAF (.ZIP)
            </a>
        <?php else: ?>
            <span class="sf-btn disabled" aria-disabled="true"><i class="fas fa-file-archive"></i> EXPORT LAPORAN SAF (.ZIP)</span>
        <?php endif; ?>
    </div>
</div>

<style>
    .sf { max-width: 1080px; margin: 0 auto; padding: 0 16px 48px; color: #1e293b; }
    .sf-back { display: inline-flex; align-items: center; gap: 6px; margin-bottom: 14px; padding: 8px 14px; border: 1.5px solid #e2e8f0; border-radius: 8px; background: #fff; color: #475569; font-weight: 600; font-size: .88rem; text-decoration: none; }
    .sf-back:hover { border-color: #94a3b8; color: #1e293b; text-decoration: none; }
    .sf-flash { margin-bottom: 12px; padding: 12px 16px; border-radius: 12px; font-size: .88rem; font-weight: 600; }
    .sf-flash.ok { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
    .sf-flash.err { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }

    .sf-head { text-align: center; padding: 22px 16px; margin-bottom: 16px; background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(15, 23, 42, .06); }
    .sf-head h1 { font-size: clamp(1.2rem, 3.5vw, 1.6rem); font-weight: 800; margin: 0 0 4px; }
    .sf-sub { color: #3b82f6; font-weight: 700; margin-bottom: 10px; }
    .sf-bpr { display: inline-block; padding: 5px 16px; border-radius: 999px; background: linear-gradient(135deg, #1f2aa8, #141863); color: #fff; font-weight: 600; font-size: .9rem; }
    .sf-warn { display: block; margin-bottom: 14px; padding: 12px 16px; border-radius: 10px; background: #fffbeb; border: 1px solid #fde68a; color: #92400e; font-size: .88rem; text-decoration: none; }
    .sf-warn:hover { color: #78350f; text-decoration: none; border-color: #f59e0b; }

    .sf-cards { display: flex; flex-direction: column; gap: 14px; }
    .sf-card {
        position: relative; display: flex; min-height: 130px; overflow: hidden; background: #fff; color: inherit; text-decoration: none;
        border: 1.5px solid #e2e8f0; border-radius: 16px; box-shadow: 0 2px 12px rgba(15, 23, 42, .06); transition: transform .15s, box-shadow .2s, border-color .2s;
    }
    .sf-card::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 5px; background: var(--c); }
    .sf-card:hover { transform: translateY(-2px); box-shadow: 0 10px 26px rgba(15, 23, 42, .1); border-color: var(--c); color: inherit; text-decoration: none; }
    .sf-card.done { border-color: #a7f3d0; }
    .sf-card-body { flex: 1; min-width: 0; padding: 18px 20px 18px 26px; display: flex; flex-direction: column; justify-content: center; gap: 8px; }
    .sf-card-title { font-size: 1.1rem; font-weight: 800; line-height: 1.35; color: var(--c2); }
    .sf-card-meta { font-size: .8rem; color: #94a3b8; }
    .sf-chips { display: flex; flex-wrap: wrap; gap: 6px; }
    .sf-chip { font-size: .78rem; font-weight: 600; padding: 4px 10px; border-radius: 6px; }
    .sf-chip.ok { background: rgba(16, 185, 129, .12); color: #059669; }
    .sf-chip.no { background: rgba(239, 68, 68, .1); color: #dc2626; }
    .sf-chip.todo { background: #f1f5f9; color: #475569; border: 1px dashed #94a3b8; }
    .sf-chip.new { background: #ef4444; color: #fff; }
    .sf-card-icon { width: 110px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: var(--soft); color: var(--c); font-size: 2.6rem; }

    .sf-status { margin-top: 18px; padding: 13px 18px; border-radius: 12px; color: #fff; font-weight: 600; font-size: .88rem; text-align: center; }
    .sf-status.ok { background: linear-gradient(135deg, #065f46, #059669); }
    .sf-status.warn { background: linear-gradient(135deg, #92400e, #d97706); }

    .sf-pdfs { display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; margin-top: 16px; }
    .sf-pdf { display: inline-flex; align-items: center; gap: 8px; padding: 9px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; background: #fff; color: #334155; font-size: .86rem; font-weight: 600; text-decoration: none; }
    .sf-pdf i { color: #dc2626; }
    .sf-pdf:hover { border-color: #94a3b8; color: #1e293b; text-decoration: none; }

    .sf-export { text-align: center; padding: 26px 0 0; }
    .sf-export p { font-size: .85rem; color: #94a3b8; margin-bottom: 14px; }
    .sf-btn {
        display: inline-flex; align-items: center; gap: 8px; padding: 12px 32px; border-radius: 25px; background: linear-gradient(135deg, #1f2aa8, #141863);
        color: #fff; font-weight: 800; letter-spacing: .03em; text-decoration: none; box-shadow: 0 4px 16px rgba(20, 24, 99, .3); transition: transform .2s, box-shadow .2s;
    }
    .sf-btn:hover:not(.disabled) { transform: translateY(-3px); box-shadow: 0 6px 22px rgba(20, 24, 99, .4); color: #fff; text-decoration: none; }
    .sf-btn.disabled { background: #94a3b8; box-shadow: none; cursor: not-allowed; opacity: .75; }

    @media (max-width: 576px) {
        .sf-card { flex-direction: column-reverse; }
        .sf-card-icon { width: 100%; height: 70px; font-size: 2rem; }
        .sf-card-body { padding: 16px 16px 16px 22px; }
        .sf-card-title { font-size: 1rem; }
        .sf-btn { padding: 12px 20px; font-size: .9rem; }
    }
    @media (prefers-reduced-motion: reduce) { .sf-card, .sf-btn { transition: none; } }
</style>

<script src="<?= $asset('asset/js/export-zip.js') ?>" defer></script>
