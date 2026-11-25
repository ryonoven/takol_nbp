<?php
// [warna garis, warna judul] — dipakai bergiliran per kartu
$themes = [
    ['#0ea5e9', '#0369a1'],
    ['#8b5cf6', '#6d28d9'],
    ['#10b981', '#047857'],
    ['#f59e0b', '#b45309'],
    ['#ef4444', '#b91c1c'],
    ['#6366f1', '#4338ca'],
    ['#ec4899', '#be185d'],
    ['#14b8a6', '#0f766e'],
    ['#f97316', '#c2410c'],
    ['#3b82f6', '#1d4ed8'],
    ['#a855f7', '#7e22ce'],
];

$icons = [
    'Riwayatbpr' => 'ic-riwayat',
    'Penjelasannpl' => 'ic-npl',
    'Usahasignifikan' => 'ic-usaha',
    'Kebijaksanaanmanathn' => 'ic-strategi',
    'Bidusahathn' => 'ic-bidang',
    'Titahunan' => 'ic-ti',
    'Perkembanganpasar' => 'ic-pasar',
    'Kerjasamabpr' => 'ic-kerjasama',
    'Kegiatanpengembangan' => 'ic-pengembangan',
    'Laporanakuntan' => 'ic-akuntan',
    'Suratpernyataandireksi' => 'ic-surat',
    'Dokumenpendukung' => 'ic-dokumen',
    'Showtransparansi' => 'ic-transparansi',
];
$roman = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII', 'XIII', 'XIV', 'XV'];
?>

<a href="<?= base_url('Periodetahunan') ?>" class="btn-kembali mb-3">← Kembali ke halaman periode</a>

<?php if (session()->getFlashdata('message')): ?>
    <div class="flash flash-success">
        <?= esc(session()->getFlashdata('message')) ?>
        <button onclick="this.parentElement.remove()">×</button>
    </div>
<?php endif; ?>
<?php foreach (['err', 'error'] as $k): ?>
    <?php if (session()->getFlashdata($k)): ?>
        <div class="flash flash-error"><?= esc(session()->getFlashdata($k)) ?></div>
    <?php endif; ?>
<?php endforeach; ?>

<div class="page-header">
    <h1 class="page-title"><?= esc($judul) ?></h1>
    <div class="page-subtitle">Periode Tahun Pelaporan: <?= esc($periode_tahun) ?></div>
    <span class="badge-namabpr"><?= esc($namabpr ?? 'Nama BPR Tidak Ditemukan') ?></span>
</div>

<?php if (empty($namabpr)): ?>
    <div class="alert-perhatian">
        <strong>Perhatian!</strong>
        Anda belum melengkapi informasi BPR. Klik <a href="<?= base_url('infobpr') ?>">di sini</a> untuk mengisi nama dan
        sandi BPR.
    </div>
<?php endif; ?>

<div class="main-wrapper">
    <svg style="display:none" aria-hidden="true">
        <!-- Riwayat Pendirian BPR: gedung bank -->
        <symbol id="ic-riwayat" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 9.5 12 4l9 5.5"/>
            <path d="M4 9.5h16"/>
            <path d="M6 12v6M10 12v6M14 12v6M18 12v6"/>
            <path d="M3.5 20.5h17"/>
        </symbol>
        <!-- Penjelasan NPL: grafik turun + tanda peringatan -->
        <symbol id="ic-npl" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 20h18"/>
            <path d="M4 6l4.5 4.5 3-3L16 12"/>
            <path d="M16 8.5V12h-3.5"/>
            <path d="M18.5 13.5 21.5 19h-6z"/>
            <path d="M18.5 15.5v1.3"/>
        </symbol>
        <!-- Perkembangan Usaha Signifikan: grafik naik -->
        <symbol id="ic-usaha" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 19h16"/>
            <path d="M5 16 9 11l4 3 6-7"/>
            <path d="M15 7h4v4"/>
        </symbol>
        <!-- Strategi & Kebijakan Manajemen: sasaran + anak panah -->
        <symbol id="ic-strategi" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="13" r="7.5"/>
            <circle cx="11" cy="13" r="4"/>
            <circle cx="11" cy="13" r="0.8" fill="currentColor"/>
            <path d="M11 13 19 5"/>
            <path d="M16.5 4.5 19 5l.5 2.5"/>
        </symbol>
        <!-- Bidang Usaha: tas kerja -->
        <symbol id="ic-bidang" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="7" width="18" height="13" rx="2"/>
            <path d="M9 7V5.5A1.5 1.5 0 0 1 10.5 4h3A1.5 1.5 0 0 1 15 5.5V7"/>
            <path d="M3 12.5h18"/>
            <path d="M11 12.5h2v2h-2z"/>
        </symbol>
        <!-- Teknologi Informasi: monitor + jaringan -->
        <symbol id="ic-ti" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="12" rx="1.5"/>
            <path d="M9 20h6M12 16v4"/>
            <path d="m9.5 8-2 2 2 2M14.5 8l2 2-2 2"/>
        </symbol>
        <!-- Perkembangan & Target Pasar: diagram lingkaran (pangsa pasar) -->
        <symbol id="ic-pasar" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M11 4a8 8 0 1 0 8.5 9H11V4Z"/>
            <path d="M14 2.5a7.5 7.5 0 0 1 7.5 7.5H14V2.5Z"/>
        </symbol>
        <!-- Kerja Sama BPR: dua mata rantai -->
        <symbol id="ic-kerjasama" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <path d="M10 14a4.5 4.5 0 0 0 6.4 0l3-3a4.5 4.5 0 0 0-6.4-6.4l-1.2 1.2"/>
            <path d="M14 10a4.5 4.5 0 0 0-6.4 0l-3 3a4.5 4.5 0 0 0 6.4 6.4l1.2-1.2"/>
        </symbol>
        <!-- Kegiatan Pengembangan: topi wisuda (pelatihan / pengembangan SDM) -->
        <symbol id="ic-pengembangan" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 4 2 8.5l10 4.5 10-4.5L12 4Z"/>
            <path d="M6 10.8v4.4c0 1.3 2.7 2.8 6 2.8s6-1.5 6-2.8v-4.4"/>
            <path d="M22 8.5v6"/>
        </symbol>
        <!-- Laporan Akuntan Publik: dokumen diaudit (kaca pembesar) -->
        <symbol id="ic-akuntan" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 3H6.5A1.5 1.5 0 0 0 5 4.5v15A1.5 1.5 0 0 0 6.5 21H11"/>
            <path d="M14 3l5 5v2"/>
            <path d="M14 3v5h5"/>
            <path d="M8 12h5M8 15h3"/>
            <circle cx="16.5" cy="16.5" r="3"/>
            <path d="m18.7 18.7 2.3 2.3"/>
        </symbol>
        <!-- Surat Pernyataan Direksi: surat + pena tanda tangan -->
        <symbol id="ic-surat" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M13 3H6.5A1.5 1.5 0 0 0 5 4.5v15A1.5 1.5 0 0 0 6.5 21h11a1.5 1.5 0 0 0 1.5-1.5V9"/>
            <path d="M8 8h4M8 11h6"/>
            <path d="M8 17c1-1.5 2-1.5 2.5 0s1.5 1.5 2.5 0"/>
            <path d="m15 13 6.5-6.5a1.4 1.4 0 0 0-2-2L13 11l-.5 2.5Z"/>
        </symbol>
        <!-- Transparansi Tata Kelola: mata -->
        <symbol id="ic-transparansi" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M2 12s3.6-6 10-6 10 6 10 6-3.6 6-10 6-10-6-10-6Z"/>
            <circle cx="12" cy="12" r="3"/>
        </symbol>
        <!-- Cadangan untuk bagian baru -->
        <symbol id="ic-default" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <rect x="5" y="3.5" width="14" height="17" rx="1.5"/>
            <path d="M8 8h8M8 11h8M8 14h8M8 17h5"/>
        </symbol>
    </svg>

    <div class="report-cards">
        <?php foreach ($sections as $i => $s):
            [$color, $numcolor] = $themes[$i % count($themes)];
            $icon = $icons[$s['link'] ?? ''] ?? 'ic-default';
            $tag = $s['available'] ? 'a' : 'div';
            $cls = 'report-card' . ($s['approved'] ? ' approved' : '') . ($s['available'] ? '' : ' unavailable');
            ?>
            <<?= $tag ?> <?= $s['available'] ? 'href="' . esc(base_url($s['link']), 'attr') . '"' : '' ?>
                class="<?= $cls ?>" style="--card-color: <?= $color ?>;">
                <div class="card-left">
                    <div class="card-roman" style="color: <?= $numcolor ?>;">
                        <?= $roman[$i] ?? ($i + 1) ?>. <?= esc($s['name']) ?>
                    </div>
                    <div class="card-periode-info">
                        Periode: Tahun <?= esc($periode_tahun) ?> &nbsp;·&nbsp; <?= esc($namabpr ?? '') ?>
                    </div>
                    <div class="card-approvals">
                        <?php if (!$s['available']): ?>
                            <span class="approval-chip soon">Segera tersedia</span>
                        <?php else: ?>
                            <span class="approval-chip <?= $s['approved'] ? 'ok' : 'no' ?>">
                                <?= $s['approved'] ? '✓' : '○' ?> <?= esc($s['statusLabel'] ?? 'Disetujui oleh: Direktur Utama') ?>
                            </span>
                            <?php if ($n = (int) ($unreadComments[$s['link']] ?? 0)): ?>
                                <span class="approval-chip comment-new" title="Komentar belum dibaca">
                                    <i class="fas fa-comment-dots"></i> <?= $n ?> komentar baru
                                </span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-right" style="background: color-mix(in srgb, <?= $color ?> 12%, white);">
                    <svg width="52" height="52" style="color: <?= $color ?>;" aria-hidden="true"><use href="#<?= $icon ?>"></use></svg>
                </div>
            </<?= $tag ?>>
        <?php endforeach; ?>
    </div>

    <?php if ($warnings): ?>
        <div class="status-alert warning">
            ⚠ <?= $canGenerate ? 'Perhatian:' : 'ZIP tidak dapat dibuat.' ?>
            <?php foreach ($warnings as $w): ?>
                <div><?= esc($w) ?></div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="status-alert success">
            ✓ Seluruh bagian laporan tahunan telah disetujui Direktur Utama. File siap di-export.
        </div>
    <?php endif; ?>

    <div class="generate-section">
        <p class="generate-note">Cek kembali seluruh data, pastikan informasi BPR dan data yang diperlukan telah terisi
            dengan benar</p>

        <?php if ($canGenerate): ?>
            <a href="<?= base_url('Showtahunan/exportAllToZip') ?>" class="btn-generate" id="btnExportZip" data-export-zip
                data-stages='[[0,"Memeriksa persetujuan Direktur Utama…"],[12,"Membuat TXT laporan tahunan…"],[30,"Membuat PDF laporan tahunan…"],[62,"Membuat TXT Laporan Transparansi…"],[82,"Mengemas file ZIP…"]]'>
                EXPORT PELAPORAN APOLO OJK (.ZIP)
            </a>
        <?php else: ?>
            <span class="btn-generate disabled" aria-disabled="true">EXPORT PELAPORAN APOLO OJK (.ZIP)</span>
        <?php endif; ?>
    </div>
</div>

<script src="<?= base_url('asset/js/export-zip.js') ?>?v=<?= @filemtime(FCPATH . 'asset/js/export-zip.js') ?>" defer></script>

<style>
    :root {
        --blue: #3b82f6;
        --green: #10b981;
        --green-d: #059669;
        --red: #ef4444;
        --amber: #f59e0b;
        --gray-50: #f8fafc;
        --gray-200: #e2e8f0;
        --gray-400: #94a3b8;
        --gray-600: #475569;
        --gray-800: #1e293b;
        --shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        --shadow-md: 0 4px 20px rgba(0, 0, 0, 0.12);
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
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--gray-600);
        text-decoration: none;
        transition: all 0.2s;
        margin: 14px 0 0 16px;
    }

    .btn-kembali:hover {
        background: var(--gray-50);
        border-color: var(--gray-400);
        color: var(--gray-800);
    }

    .flash {
        margin: 10px 16px;
        padding: 12px 18px;
        border-radius: var(--radius);
        font-size: 0.88rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .flash-success {
        background: #d1fae5;
        color: #065f46;
        border-left: 4px solid var(--green);
    }

    .flash-error {
        background: #fee2e2;
        color: #991b1b;
        border-left: 4px solid var(--red);
    }

    .flash button {
        background: none;
        border: none;
        cursor: pointer;
        font-size: 1.1rem;
        color: inherit;
        opacity: 0.6;
    }

    .page-header {
        text-align: center;
        padding: 28px 20px 22px;
        background: white;
        border-bottom: 1px solid var(--gray-200);
    }

    .page-title {
        font-size: clamp(1.25rem, 3.5vw, 1.65rem);
        font-weight: 800;
        color: var(--gray-800);
        margin-bottom: 5px;
    }

    .page-subtitle {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--blue);
        margin-bottom: 10px;
    }

    .badge-namabpr {
        display: inline-block;
        background: var(--primary-gradient);
        color: white;
        font-size: 0.92rem;
        font-weight: 600;
        padding: 5px 18px;
        border-radius: 20px;
    }

    .alert-perhatian {
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-left: 4px solid var(--amber);
        margin: 14px 16px;
        padding: 12px 16px;
        border-radius: 8px;
        font-size: 0.87rem;
        color: #78350f;
    }

    .alert-perhatian strong {
        color: #92400e;
    }

    .alert-perhatian a {
        color: var(--amber);
        font-weight: 700;
    }

    .main-wrapper {
        max-width: 1080px;
        margin: 0 auto;
        padding: 20px 16px 50px;
    }

    .report-cards {
        display: flex;
        flex-direction: column;
        gap: 14px;
        margin-bottom: 24px;
    }

    .report-card {
        background: white;
        border-radius: var(--radius-lg);
        overflow: hidden;
        display: flex;
        min-height: 130px;
        box-shadow: var(--shadow);
        border: 1.5px solid var(--gray-200);
        text-decoration: none;
        color: inherit;
        transition: box-shadow 0.2s, border-color 0.2s, transform 0.15s;
        position: relative;
    }

    a.report-card:hover {
        box-shadow: var(--shadow-md);
        border-color: var(--card-color, var(--gray-200));
        transform: translateY(-2px);
        text-decoration: none;
        color: inherit;
    }

    .report-card.approved {
        border-color: #a7f3d0;
    }

    .report-card.unavailable {
        opacity: 0.6;
        cursor: default;
    }

    .report-card::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 5px;
        background: var(--card-color, var(--blue));
    }

    .card-left {
        flex: 1;
        padding: 20px 22px 20px 28px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        justify-content: center;
    }

    .card-roman {
        font-size: 1.2rem;
        font-weight: 800;
        line-height: 1.35;
    }

    .card-periode-info {
        font-size: 0.80rem;
        color: var(--gray-400);
        font-weight: 500;
    }

    .card-approvals {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 2px;
    }

    .approval-chip {
        font-size: 0.78rem;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 6px;
    }

    .approval-chip.ok {
        background: rgba(16, 185, 129, .12);
        color: var(--green-d);
    }

    .approval-chip.no {
        background: rgba(239, 68, 68, .10);
        color: #dc2626;
    }

    .approval-chip.comment-new {
        background: #ef4444;
        color: #fff;
    }

    .approval-chip.soon {
        background: var(--gray-50);
        color: var(--gray-600);
        border: 1px dashed var(--gray-400);
    }

    .card-right {
        width: 110px;
        min-width: 110px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .status-alert {
        padding: 13px 18px;
        border-radius: var(--radius);
        font-size: 0.87rem;
        font-weight: 600;
        margin-top: 18px;
        text-align: center;
        color: white;
    }

    .status-alert.success {
        background: linear-gradient(135deg, #065f46, #059669);
    }

    .status-alert.warning {
        background: linear-gradient(135deg, #92400e, #d97706);
    }

    .generate-section {
        text-align: center;
        margin-top: 5px;
        padding: 28px 0 10px;
    }

    .generate-note {
        font-size: 0.85rem;
        color: var(--gray-400);
        margin-bottom: 16px;
    }

    .btn-generate {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--primary-gradient);
        color: white;
        text-decoration: none;
        padding: 12px 36px;
        border-radius: 25px;
        font-size: 1.05rem;
        font-weight: 800;
        letter-spacing: .03em;
        box-shadow: 0 4px 16px rgba(20, 24, 99, .3);
        transition: all .2s;
    }

    .btn-generate:hover:not(.disabled) {
        transform: translateY(-3px);
        box-shadow: 0 6px 22px rgba(20, 24, 99, .4);
        color: white;
        text-decoration: none;
    }

    .btn-generate.busy {
        opacity: 0.75;
        pointer-events: none;
    }

    .btn-generate.disabled {
        background: #94a3b8;
        box-shadow: none;
        cursor: not-allowed;
        pointer-events: none;
        opacity: 0.7;
    }

    @media (max-width: 768px) {
        .card-right {
            width: 90px;
            min-width: 90px;
        }
    }

    @media (max-width: 576px) {
        .report-card {
            flex-direction: column;
        }

        .card-right {
            width: 100%;
            height: 90px;
            min-width: unset;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        * {
            animation-duration: .01ms !important;
            transition-duration: .01ms !important;
        }
    }
</style>
