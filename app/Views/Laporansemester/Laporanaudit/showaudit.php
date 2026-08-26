<?php
// Pre-compute once, avoid repeated isset/array access in loop
$anyPending = false;
foreach ($factors as $f) {
    if (empty($f['is_approved']) || $f['is_approved'] != 1) {
        $anyPending = true;
        break;
    }
}

// Pre-compute period display outside loop
$semDisplay = esc($periode_semester);
$semNum = match (strtolower($semDisplay)) {
    'ganjil' => '1',
    'genap' => '2',
    default => $semDisplay,
};
$safeNamabpr = esc($namabpr);
$safePeriodeTahun = esc($periode_tahun);
$namabprEmpty = empty($namabpr) || $namabpr === 'Nama BPR Tidak Ditemukan';

// Pre-define static arrays outside loop
$cardThemes = [
    0 => ['color' => '#ef4444', 'bg' => 'linear-gradient(135deg,#fee2e2 0%,#fecaca 60%,#fca5a5 100%)', 'numcolor' => '#dc2626'],
    1 => ['color' => '#8b5cf6', 'bg' => 'linear-gradient(135deg,#ede9fe 0%,#ddd6fe 60%,#c4b5fd 100%)', 'numcolor' => '#7c3aed'],
    2 => ['color' => '#10b981', 'bg' => 'linear-gradient(135deg,#d1fae5 0%,#a7f3d0 60%,#6ee7b7 100%)', 'numcolor' => '#059669'],
    3 => ['color' => '#f59e0b', 'bg' => 'linear-gradient(135deg,#fef3c7 0%,#fde68a 60%,#fbbf24 100%)', 'numcolor' => '#d97706'],
    4 => ['color' => '#3b82f6', 'bg' => 'linear-gradient(135deg,#dbeafe 0%,#bfdbfe 60%,#93c5fd 100%)', 'numcolor' => '#1d4ed8'],
    5 => ['color' => '#FF5CC0', 'bg' => 'linear-gradient(135deg,#FFE6F5 0%,#FFB8E4 60%,#FF8AD2 100%)', 'numcolor' => '#FF5CC0'],
];
$romanNumerals = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

// Pre-compute flash data once
$flashMsg = session()->getFlashdata('message');
$flashErr = session()->getFlashdata('err');
$flashErrors = session()->getFlashdata('errors');

// Validasi faktor yang belum disetujui
$unapprovedFactorNames = [];
foreach ($factors as $f) {
    if (empty($f['is_approved']) || $f['is_approved'] != 1) {
        $unapprovedFactorNames[] = $f['name'];
    }
}
$allFactorsApproved = empty($unapprovedFactorNames);

// Validasi upload file
$fileUploaded = !empty($showfaktor['pdfaudit_filename']);

// Status tombol Cetak PDF
$disableCetakPdf = !($allFactorsApproved && $fileUploaded);

// Susun pesan warning
$warningItems = [];
foreach ($unapprovedFactorNames as $namaFaktor) {
    $warningItems[] = 'Faktor belum disetujui: <strong>' . esc($namaFaktor) . '</strong>';
}
if (!$fileUploaded) {
    $warningItems[] = '<strong>Dokumen pendukung belum diunggah</strong>';
}
?>

<!-- BACK BUTTON -->
<a href="<?= base_url('Showsemesteran') ?>" class="btn-kembali mb-3">← Kembali ke Laporan Semesteran</a>

<?php if ($flashMsg): ?>
    <div class="flash-success">✓ <?= esc($flashMsg) ?><button onclick="this.parentElement.remove()">×</button></div>
<?php endif; ?>
<?php if ($flashErr): ?>
    <div class="flash-error"><?= esc($flashErr) ?></div>
<?php endif; ?>

<!-- PAGE HEADER -->
<div class="page-header">
    <h1 class="page-title"><?= esc($judul) ?></h1>
    <div class="page-subtitle">Semester <?= $semNum ?> Tahun <?= $safePeriodeTahun ?></div>
    <span class="badge-namabpr"><?= $safeNamabpr ?></span>
</div>

<?php if ($namabprEmpty): ?>
    <div class="alert-perhatian">
        <strong>Perhatian!</strong> Anda belum melengkapi informasi BPR.
        Klik <a href="<?= base_url('infobpr') ?>">di sini</a> untuk mengisi nama dan sandi BPR.
    </div>
<?php endif; ?>

<?php if (!empty($warningItems)): ?>
    <div class="main-wrapper" style="padding-bottom:0">
        <div class="alert-warning-box">
            <div class="alert-warning-title"><i>⚠</i> Data belum lengkap</div>
            <ul>
                <?php foreach ($warningItems as $item): ?>
                    <li><?= $item ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php else: ?>
    <div class="main-wrapper" style="padding-bottom:0">
        <div class="alert-success-box">
            <i>✓</i> Seluruh data telah lengkap dan disetujui. Laporan siap untuk dicetak.
        </div>
    </div>
<?php endif; ?>

<div class="main-wrapper">
    <div class="report-cards-list" id="factorsContainer">
        <!-- SKELETON LOADING -->
        <div class="skeleton-list" id="skeletonList" aria-hidden="true">
            <div class="skeleton-card">
                <div class="skeleton-left">
                    <div class="sk sk-title"></div>
                    <div class="sk sk-sub"></div>
                    <div class="sk sk-box"></div>
                    <div class="sk sk-badge"></div>
                </div>
                <div class="skeleton-right sk"></div>
            </div>
            <div class="skeleton-card">
                <div class="skeleton-left">
                    <div class="sk sk-title"></div>
                    <div class="sk sk-sub"></div>
                    <div class="sk sk-box"></div>
                    <div class="sk sk-badge"></div>
                </div>
                <div class="skeleton-right sk"></div>
            </div>
            <div class="skeleton-card">
                <div class="skeleton-left">
                    <div class="sk sk-title"></div>
                    <div class="sk sk-sub"></div>
                    <div class="sk sk-box"></div>
                    <div class="sk sk-badge"></div>
                </div>
                <div class="skeleton-right sk"></div>
            </div>
        </div>

        <!-- ACTUAL FACTOR CARDS -->
        <div class="actual-cards" id="actualCards" style="display:none;">
            <?php foreach ($factors as $index => $factor):
                $theme = $cardThemes[$index] ?? $cardThemes[0];
                $dirutApproved = !empty($factor['is_approved']) && $factor['is_approved'] == 1;
                $numeral = $romanNumerals[$index] ?? ($index + 1);
                $cardClass = $dirutApproved ? 'report-card approved' : 'report-card';
                ?>
                <a href="<?= esc($factor['link']) ?>" class="<?= $cardClass ?>"
                    style="--card-color:<?= $theme['color'] ?>;--card-bg:<?= $theme['bg'] ?>;">
                    <div class="card-left">
                        <div class="card-roman" style="color:<?= $theme['numcolor'] ?>;">
                            <?= $numeral ?>. <?= esc($factor['name']) ?>
                        </div>
                        <div class="card-periode-info">
                            Periode: Semester <?= $semNum ?> Tahun <?= $safePeriodeTahun ?> &nbsp;·&nbsp;
                            <?= $safeNamabpr ?>
                        </div>
                        <div class="card-approvals">
                            <span class="approval-chip <?= $dirutApproved ? 'ok' : 'no' ?>">
                                <?= $dirutApproved ? '✓ Disetujui oleh: ' : '○ Belum disetujui oleh: ' ?>Direktur Utama
                            </span>
                        </div>
                        <div class="card-cta-row">Klik di sini untuk memulai pengisian Form →</div>
                    </div>
                    <div class="card-right" style="background:<?= $theme['bg'] ?>;">
                        <div class="card-right-glow"
                            style="background:radial-gradient(circle,<?= $theme['color'] ?>40 0%,transparent 70%);"></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- UPLOAD DOKUMEN -->
    <div class="section-card">
        <div class="section-header" style="--accent:#f59e0b;">
            <div class="section-accent"></div>
            <h2>Upload Dokumen Pendukung</h2>
        </div>
        <div class="section-body">
            <p class="section-desc">Silakan unggah file PDF pendukung Laporan Pelaksanaan dan Pokok Hasil Audit Intern
                (.PDF)</p>

            <?php if ($flashErrors): ?>
                <div class="flash-error" style="margin-bottom:12px;">
                    <?php foreach ($flashErrors as $err): ?>
                        <div><?= esc($err) ?></div><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= base_url('Showaudit/uploadPdf') ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="form">
                    <div class="form-group">
                        <input type="file" name="pdf1" id="pdf1Input" class="form-control file-input"
                            accept="application/pdf">
                        <small>Maksimal ukuran file .PDF 10 MB</small>
                        <div id="fileSizeWarning" style="color:#dc2626;font-size:.78rem;display:none;margin-top:4px;">
                            ⚠ File melebihi 10 MB. Silakan pilih file yang lebih kecil.
                        </div>
                        <?php if (!empty($showfaktor['pdfaudit_filename'])): ?>
                            <div class="uploaded-file" style="margin-top:8px;">
                                <a href="<?= base_url('Showaudit/downloadPdf/' . esc($showfaktor['pdfaudit_filename'])) ?>"
                                    target="_blank" rel="noopener"><?= esc($showfaktor['pdfaudit_filename']) ?></a>
                                <span style="font-size:.75rem;color:var(--green-d);font-weight:600;margin-left:6px;">✓ Sudah
                                    diunggah</span>
                            </div>
                        <?php else: ?>
                            <div class="field-warning" style="margin-top:8px;">
                                <i>⚠</i> File dokumen pendukung belum diunggah.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-footer">
                    <button type="submit" id="btnUpload" class="btn-save">Upload PDF</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="pdf-action-row" style="display:flex; justify-content:center; gap:12px; flex-wrap:wrap;">
    <?php if ($disableCetakPdf): ?>
        <a href="#" class="btn-generate disabled" aria-disabled="true"
            title="Pastikan seluruh faktor telah disetujui dan dokumen pendukung telah diunggah">
            Cetak PDF
        </a>
    <?php else: ?>
        <a href="<?= base_url('LaporanPdfAudit/LaporanAuditInternal') ?>" target="_blank" class="btn-generate">
            Cetak PDF
        </a>
    <?php endif; ?>
</div>

<!-- Kalender -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" media="print"
    onload="this.media='all'">
<script>
    (function () {
        // Skeleton → cards reveal
        var sk = document.getElementById('skeletonList');
        var ac = document.getElementById('actualCards');
        setTimeout(function () { if (sk) sk.style.display = 'none'; if (ac) ac.style.display = 'flex'; }, 800);

        // PDF file size guard
        var inp = document.getElementById('pdf1Input');
        var warn = document.getElementById('fileSizeWarning');
        var btn = document.getElementById('btnUpload');
        if (inp) {
            inp.addEventListener('change', function () {
                var over = this.files.length > 0 && this.files[0].size > 10485760;
                warn.style.display = over ? 'block' : 'none';
                btn.disabled = over;
                btn.style.opacity = over ? '0.5' : '1';
            });
        }

        // Flatpickr — deferred, only init if the field exists
        function loadFlatpickr() {
            var s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/flatpickr';
            s.onload = function () { if (document.getElementById('tanggal')) flatpickr('#tanggal', { dateFormat: 'Y/m/d' }); };
            document.head.appendChild(s);
        }
        if ('requestIdleCallback' in window) { requestIdleCallback(loadFlatpickr); }
        else { setTimeout(loadFlatpickr, 500); }

        // Cover selection (lightweight event delegation instead of querySelectorAll loop)
        document.addEventListener('click', function (e) {
            var opt = e.target.closest('.cover-option');
            if (!opt) return;
            document.querySelectorAll('.cover-option').forEach(function (o) { o.classList.remove('active'); });
            opt.classList.add('active');
            var cv = document.getElementById('cover');
            if (cv) cv.value = opt.dataset.value;
        });

        // Lazy-load images via IntersectionObserver (single observer instance)
        if ('IntersectionObserver' in window) {
            var imgObs = new IntersectionObserver(function (entries, obs) {
                entries.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('loaded'); obs.unobserve(e.target); } });
            });
            document.querySelectorAll('img[loading="lazy"]').forEach(function (img) { imgObs.observe(img); });
        } else {
            document.querySelectorAll('img[loading="lazy"]').forEach(function (img) { img.classList.add('loaded'); });
        }

        // Auto-save highlight — single delegated listener + debounce
        var debTimer;
        document.addEventListener('input', function (e) {
            if (!e.target.dataset.field) return;
            clearTimeout(debTimer);
            var el = e.target;
            debTimer = setTimeout(function () {
                el.style.borderColor = '#10b981';
                setTimeout(function () { el.style.borderColor = ''; }, 1500);
            }, 1000);
        });

        // Cegah klik pada tombol cetak yang sedang disable
        document.querySelectorAll('a.btn-generate.disabled').forEach(function (el) {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                alert(el.getAttribute('title') || 'Lengkapi data yang diperlukan terlebih dahulu.');
            });
        });
    })();
</script>

<style>
    /* VARIABLES & BASE */
    :root {
        --blue: #3b82f6;
        --blue-d: #1d4ed8;
        --green: #10b981;
        --green-d: #059669;
        --red: #ef4444;
        --amber: #f59e0b;
        --purple: #8b5cf6;
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

    /* BACK BUTTON */
    .btn-kembali {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #fff;
        border: 1.5px solid var(--gray-200);
        border-radius: 8px;
        padding: 8px 16px;
        font-size: .9rem;
        font-weight: 600;
        color: var(--gray-600);
        text-decoration: none;
        transition: all .2s;
        margin: 14px 0 0 16px;
    }

    .btn-kembali:hover {
        background: var(--gray-50);
        border-color: var(--gray-400);
        color: var(--gray-800);
    }

    /* FLASH MESSAGES */
    .flash-success,
    .flash-error {
        margin: 10px 16px;
        padding: 12px 18px;
        border-radius: var(--radius);
        font-size: .88rem;
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

    .flash-success button,
    .flash-error button {
        background: none;
        border: none;
        cursor: pointer;
        font-size: 1.1rem;
        color: inherit;
        opacity: .6;
    }

    /* PAGE HEADER */
    .page-header {
        text-align: center;
        padding: 28px 20px 22px;
        background: #fff;
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
        color: #fff;
        font-size: .92rem;
        font-weight: 600;
        padding: 5px 18px;
        border-radius: 20px;
    }

    /* ALERT PERHATIAN */
    .alert-perhatian {
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-left: 4px solid var(--amber);
        margin: 14px 16px;
        padding: 12px 16px;
        border-radius: 8px;
        font-size: .87rem;
        color: #78350f;
    }

    .alert-perhatian strong {
        color: #92400e;
    }

    .alert-perhatian a {
        color: var(--amber);
        font-weight: 700;
    }

    /* WARNING & SUCCESS SUMMARY BOX */
    .alert-warning-box {
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-left: 4px solid var(--amber);
        padding: 14px 18px;
        border-radius: var(--radius);
        margin-bottom: 18px;
        font-size: .85rem;
        color: #78350f;
    }

    .alert-warning-title {
        font-weight: 700;
        color: #92400e;
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 6px;
    }

    .alert-warning-box ul {
        margin: 0;
        padding-left: 20px;
    }

    .alert-warning-box li {
        margin-bottom: 4px;
        line-height: 1.5;
    }

    .alert-success-box {
        background: #d1fae5;
        border: 1px solid #a7f3d0;
        border-left: 4px solid var(--green);
        padding: 12px 18px;
        border-radius: var(--radius);
        margin-bottom: 18px;
        font-size: .85rem;
        color: #065f46;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .field-warning {
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-left: 3px solid var(--red);
        padding: 8px 12px;
        border-radius: 6px;
        font-size: .78rem;
        color: #991b1b;
        display: flex;
        align-items: flex-start;
        gap: 6px;
    }

    /* MAIN WRAPPER */
    .main-wrapper {
        max-width: 1080px;
        margin: 0 auto;
        padding: 20px 16px 50px;
    }

    /* REPORT CARDS LIST */
    .report-cards-list {
        margin-bottom: 24px;
    }

    /* SKELETON */
    .skeleton-list {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .skeleton-card {
        background: #fff;
        border-radius: var(--radius-lg);
        overflow: hidden;
        display: flex;
        min-height: 160px;
        box-shadow: var(--shadow);
    }

    .skeleton-left {
        flex: 1;
        padding: 24px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .skeleton-right {
        width: 280px;
    }

    .sk {
        background: linear-gradient(90deg, #f0f0f0 0%, #e0e0e0 30%, #f0f0f0 60%);
        background-size: 200% 100%;
        animation: shimmer 1.5s ease-in-out infinite;
        border-radius: 6px;
    }

    .sk-title {
        height: 20px;
        width: 70%;
    }

    .sk-sub {
        height: 13px;
        width: 50%;
    }

    .sk-box {
        height: 36px;
        width: 55%;
        border-radius: 8px;
    }

    .sk-badge {
        height: 26px;
        width: 65%;
        border-radius: 6px;
    }

    @keyframes shimmer {
        0% {
            background-position: -200% 0
        }

        100% {
            background-position: 200% 0
        }
    }

    /* ACTUAL CARDS */
    .actual-cards {
        flex-direction: column;
        gap: 8px;
    }

    .report-card {
        background: #fff;
        border-radius: var(--radius-lg);
        overflow: hidden;
        display: flex;
        min-height: 125px;
        box-shadow: var(--shadow);
        border: 1.5px solid var(--gray-200);
        text-decoration: none;
        color: inherit;
        transition: box-shadow .25s, transform .2s;
        position: relative;
        cursor: pointer;
        outline: none;
        /* Promote to own layer for smoother hover on low-end devices */
        will-change: transform;
        contain: layout style;
    }

    .report-card:hover {
        box-shadow: var(--shadow-md);
        transform: translateY(-2px);
    }

    .report-card.approved {
        border-color: #a7f3d0;
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

    /* CARD LEFT */
    .card-left {
        flex: 1;
        padding: 20px 22px 20px 28px;
        display: flex;
        flex-direction: column;
        gap: 5px;
        justify-content: center;
    }

    .card-roman {
        font-size: 1.2rem;
        font-weight: 800;
        line-height: 1.35;
        color: var(--gray-800);
    }

    .card-periode-info {
        font-size: .80rem;
        color: var(--gray-400);
        font-weight: 500;
    }

    .card-stats-row {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 4px;
    }

    .stat-chip {
        border-radius: 8px;
        padding: 6px 12px;
        display: flex;
        flex-direction: column;
        min-width: 110px;
        border: 1px solid var(--gray-200);
    }

    .stat-chip.completed {
        background: #f0fdf4;
        border-color: #a7f3d0;
    }

    .stat-chip.incomplete {
        background: #fafafa;
        border-color: var(--gray-200);
    }

    .stat-chip-label {
        font-size: .68rem;
        font-weight: 600;
        color: var(--gray-400);
        margin-bottom: 2px;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .stat-chip.completed .stat-chip-value {
        color: var(--green-d);
        font-weight: 800;
        font-size: .95rem;
    }

    .stat-chip.incomplete .stat-chip-value {
        color: var(--gray-400);
        font-weight: 600;
        font-size: .82rem;
    }

    .card-warning-tag {
        background: #fff7ed;
        border: 1px solid #fed7aa;
        border-radius: 8px;
        padding: 6px 12px;
        font-size: .78rem;
        color: #c2410c;
        font-weight: 600;
        font-style: italic;
    }

    .card-approvals {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 2px;
    }

    .approval-chip {
        font-size: .78rem;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .approval-chip.ok {
        background: rgba(16, 185, 129, .12);
        color: var(--green-d);
    }

    .approval-chip.no {
        background: rgba(239, 68, 68, .1);
        color: #dc2626;
    }

    .card-cta-row {
        font-size: .78rem;
        color: var(--blue);
        font-weight: 600;
        margin-top: 4px;
    }

    /* CARD RIGHT */
    .card-right {
        width: 70px;
        min-width: 50px;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }

    .card-right-glow {
        position: absolute;
        right: -30px;
        top: 50%;
        transform: translateY(-50%);
        width: 200px;
        height: 200px;
        border-radius: 50%;
    }

    .card-svg {
        width: 130px;
        height: 130px;
        position: relative;
        z-index: 1;
    }

    .card-cta-img {
        position: absolute;
        bottom: 12px;
        right: 14px;
        font-size: .76rem;
        font-weight: 700;
        z-index: 1;
    }

    /*
   SECTION CARDS
   */
    .section-card {
        background: #fff;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow);
        border: 1px solid var(--gray-200);
        margin-top: 18px;
        overflow: hidden;
    }

    .section-header {
        padding: 16px 22px;
        border-bottom: 1px solid var(--gray-100);
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 1rem;
    }

    .section-accent {
        width: 4px;
        height: 22px;
        border-radius: 4px;
        background: var(--accent, var(--blue));
        flex-shrink: 0;
    }

    .section-header h2 {
        font-size: 1rem;
        font-weight: 700;
        color: var(--gray-800);
        margin: 0;
    }

    .section-body {
        padding: 20px 22px;
    }

    .section-desc {
        font-size: .84rem;
        color: var(--gray-400);
        margin-bottom: 16px;
    }

    /* FORM ELEMENTS */
    .form-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .form-group.span-2 {
        grid-column: 1/-1;
    }

    .form-group label {
        font-size: .95rem;
        font-weight: 650;
        color: var(--gray-600);
    }

    .form-control {
        width: 100%;
        padding: 9px 13px;
        border: 1.5px solid var(--gray-200);
        border-radius: 8px;
        font-size: .87rem;
        font-family: inherit;
        color: var(--gray-800);
        background: var(--gray-50);
        transition: border-color .2s, box-shadow .2s;
        outline: none;
    }

    .form-control:focus {
        border-color: var(--blue);
        background: #fff;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, .1);
    }

    textarea.form-control {
        resize: vertical;
        min-height: 85px;
    }

    .file-input {
        border: 2px dashed var(--blue);
        background: #eff6ff;
        cursor: pointer;
        padding: 6px 13px;
    }

    .file-input:hover {
        background: #dbeafe;
        border-color: var(--blue-d);
    }

    .form-group small {
        font-size: .74rem;
        color: var(--gray-400);
    }

    .uploaded-file {
        font-size: .8rem;
        color: var(--gray-600);
        margin-top: 4px;
    }

    .uploaded-file a {
        color: var(--blue);
        font-weight: 600;
        text-decoration: none;
    }

    .form-footer {
        display: flex;
        justify-content: center;
        padding-top: 16px;
    }

    .btn-save {
        background: linear-gradient(135deg, var(--green), var(--green-d));
        color: #fff;
        border: none;
        border-radius: 25px;
        padding: 10px 32px;
        font-size: .92rem;
        font-weight: 700;
        cursor: pointer;
        font-family: inherit;
        transition: all .2s;
        box-shadow: 0 2px 8px rgba(16, 185, 129, .3);
    }

    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 14px rgba(16, 185, 129, .4);
    }

    /* COVER GRID */
    .cover-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
        gap: 12px;
        margin-bottom: 4px;
    }

    .cover-option {
        border-radius: 10px;
        overflow: hidden;
        cursor: pointer;
        border: 2.5px solid transparent;
        transition: all .2s;
        position: relative;
    }

    .cover-option:hover {
        border-color: var(--blue);
        transform: translateY(-3px);
        box-shadow: var(--shadow);
    }

    .cover-option.active {
        border-color: var(--green);
        box-shadow: 0 0 0 3px rgba(16, 185, 129, .2);
    }

    .cover-option img {
        width: 100%;
        height: auto;
        display: block;
        opacity: 0;
        transition: opacity .3s;
    }

    .cover-option img.loaded {
        opacity: 1;
    }

    .cover-check {
        position: absolute;
        top: 6px;
        right: 6px;
        background: var(--green);
        color: #fff;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        font-size: .75rem;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* STATUS ALERT */
    .status-alert {
        padding: 13px 18px;
        border-radius: var(--radius);
        font-size: .87rem;
        font-weight: 600;
        margin-top: 18px;
        text-align: center;
    }

    .status-alert.success {
        background: linear-gradient(135deg, #065f46, #059669);
        color: #fff;
    }

    .status-alert.warning {
        background: linear-gradient(135deg, #92400e, #d97706);
        color: #fff;
    }

    /*
   GENERATE SECTION
   */
    .generate-section {
        text-align: center;
        margin-top: 5px;
        padding: 28px 0 10px;
    }

    .generate-note {
        font-size: .85rem;
        color: var(--gray-400);
        margin-bottom: 16px;
    }

    .btn-generate {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: linear-gradient(135deg, #141863, #1f2aa8);
        color: #fff;
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
    }

    .btn-generate.disabled {
        background: #94a3b8;
        box-shadow: none;
        cursor: not-allowed;
        pointer-events: none;
        opacity: .7;
    }

    .link-back-bottom {
        display: inline-block;
        margin-top: 16px;
        font-size: .85rem;
        color: var(--blue);
        font-weight: 500;
        text-decoration: none;
    }

    .link-back-bottom:hover {
        text-decoration: underline;
    }

    /* RESPONSIVE */
    @media(max-width:768px) {
        .card-right {
            width: 160px;
            min-width: 130px;
        }

        .card-svg {
            width: 100px;
            height: 100px;
        }

        .form-grid-2 {
            grid-template-columns: 1fr;
        }

        .form-group.span-2 {
            grid-column: 1;
        }
    }

    @media(max-width:576px) {
        .report-card {
            flex-direction: row;
        }

        .card-right {
            width: 110px;
            min-width: 90px;
            flex-shrink: 0;
        }

        .card-cta-img {
            font-size: .62rem;
            bottom: 8px;
            right: 8px;
            text-align: right;
        }

        .card-roman {
            font-size: 1rem;
        }

        .cover-grid {
            grid-template-columns: repeat(3, 1fr);
        }

        .card-stats-row {
            flex-direction: column;
            align-items: flex-start;
        }

        .card-left {
            padding: 14px 12px 14px 22px;
        }
    }

    @media(prefers-reduced-motion:reduce) {
        * {
            animation-duration: .01ms !important;
            transition-duration: .01ms !important;
        }
    }
</style>