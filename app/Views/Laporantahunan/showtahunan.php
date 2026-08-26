<?php
$anyPending = false;
foreach ($factors as $f) {
    if (!(isset($f['is_approved']) && $f['is_approved'] == 1)) {
        $anyPending = true;
        break;
    }
} ?>

<a href="<?= base_url('Periodetahunan') ?>" class="btn-kembali mb-3">
    ← Kembali ke halaman periode
</a>

<?php if (session()->getFlashdata('message')): ?>
    <div class="flash-success">
        <?= esc(session()->getFlashdata('message')); ?>
        <button onclick="this.parentElement.remove()">×</button>
    </div>
<?php endif; ?>
<?php if (session()->getFlashdata('err')): ?>
    <div class="flash-error">
        <?= esc(session()->getFlashdata('err')); ?>
    </div>
<?php endif; ?>

<div class="page-header">
    <h1 class="page-title">
        <?= esc($judul); ?>
    </h1>
    <div class="page-subtitle">
        Periode Tahun Pelaporan: <?= esc($periode_tahun); ?>
    </div>
    <span class="badge-namabpr">
        <?= esc($namabpr); ?>
    </span>
</div>

<?php if (empty($namabpr) || $namabpr === 'Nama BPR Tidak Ditemukan'): ?>
    <div class="alert-perhatian">
        <strong>Perhatian!</strong>
        Anda belum melengkapi informasi BPR. Klik <a href="<?= base_url('infobpr') ?>">di sini</a> untuk mengisi nama dan
        sandi BPR.
    </div>
<?php endif; ?>

<div class="main-wrapper">

    <!-- Sprite SVG: definisi sekali, dipakai berkali-kali via <use> -->
    <svg style="display:none" aria-hidden="true">
        <symbol id="ic-f1" viewBox="0 0 24 24" fill="none">
            <path d="M4 5c0-1.1.9-2 2-2h5v18H6c-1.1 0-2-.9-2-2V5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
            <path d="M20 5c0-1.1-.9-2-2-2h-5v18h5c1.1 0 2-.9 2-2V5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
            <path d="M7 7h2M7 10h2M15 7h2M15 10h2" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
        </symbol>
        <symbol id="ic-f2" viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="7" r="3" stroke="currentColor" stroke-width="1.6"/>
            <circle cx="5" cy="17" r="2.4" stroke="currentColor" stroke-width="1.6"/>
            <circle cx="19" cy="17" r="2.4" stroke="currentColor" stroke-width="1.6"/>
            <path d="M12 10v3M12 13 7 15.2M12 13l5 2.2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
        </symbol>
        <symbol id="ic-f3" viewBox="0 0 24 24" fill="none">
            <path d="M12 3 5 6v5c0 5 3 8.5 7 10 4-1.5 7-5 7-10V6l-7-3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
            <path d="M9.5 12 11 13.5 14.5 10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
        </symbol>
        <symbol id="ic-f4" viewBox="0 0 24 24" fill="none">
            <path d="M4 19h16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
            <path d="M5 16 9 11l4 3 6-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M15 7h4v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        </symbol>
        <symbol id="ic-f5" viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.5"/>
            <circle cx="12" cy="12" r="4.5" stroke="currentColor" stroke-width="1.5"/>
            <circle cx="12" cy="12" r="1.3" fill="currentColor"/>
        </symbol>
        <symbol id="ic-f6" viewBox="0 0 24 24" fill="none">
            <rect x="5" y="3.5" width="14" height="17" rx="1.5" stroke="currentColor" stroke-width="1.6"/>
            <path d="M9 3.5h6v2.5H9z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
            <path d="M8 11h8M8 14h8M8 17h5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
        </symbol>
        <symbol id="ic-f7" viewBox="0 0 24 24" fill="none">
            <path d="M12 4 2 8l10 4 10-4-10-4Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
            <path d="M6 10.5v4.3c0 1.2 2.7 2.7 6 2.7s6-1.5 6-2.7v-4.3" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
            <path d="M22 8v6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </symbol>
        <symbol id="ic-f8" viewBox="0 0 24 24" fill="none">
            <rect x="4" y="4" width="16" height="16" rx="1.5" stroke="currentColor" stroke-width="1.6"/>
            <path d="M8 16v-3M12 16V8M16 16v-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
        </symbol>
        <symbol id="ic-f9" viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="9" r="5.5" stroke="currentColor" stroke-width="1.6"/>
            <path d="M9.3 9 11 10.7 14.7 7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M8.5 13.8 7 20l5-2 5 2-1.5-6.2" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
        </symbol>
        <symbol id="ic-f10" viewBox="0 0 24 24" fill="none">
            <rect x="3" y="5" width="18" height="14" rx="1.5" stroke="currentColor" stroke-width="1.6"/>
            <path d="m3.5 6 8.5 6.5L20.5 6" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
            <path d="M14.5 14.5 16.5 16.5 20 12.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
        </symbol>
        <symbol id="ic-f11" viewBox="0 0 24 24" fill="none">
            <path d="M2 12s3.6-6 10-6 10 6 10 6-3.6 6-10 6-10-6-10-6Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
            <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.6"/>
        </symbol>
    </svg>

    <div class="report-cards-list" id="factorsContainer">
        <div class="skeleton-list" id="skeletonList">
            <?php for ($i = 0; $i < 3; $i++): ?>
                <div class="skeleton-card">
                    <div class="skeleton-left">
                        <div class="sk sk-title"></div>
                        <div class="sk sk-sub"></div>
                        <div class="sk sk-box"></div>
                        <div class="sk sk-badge"></div>
                    </div>
                    <div class="skeleton-right sk"></div>
                </div>
            <?php endfor; ?>
        </div>

        <div class="actual-cards" id="actualCards" style="display:none;">
            <?php
            $cardThemes = [
                0  => ['color' => '#0ea5e9', 'numcolor' => '#0369a1', 'icon' => 'ic-f1'],
                1  => ['color' => '#8b5cf6', 'numcolor' => '#6d28d9', 'icon' => 'ic-f2'],
                2  => ['color' => '#10b981', 'numcolor' => '#047857', 'icon' => 'ic-f3'],
                3  => ['color' => '#f59e0b', 'numcolor' => '#b45309', 'icon' => 'ic-f4'],
                4  => ['color' => '#ef4444', 'numcolor' => '#b91c1c', 'icon' => 'ic-f5'],
                5  => ['color' => '#6366f1', 'numcolor' => '#4338ca', 'icon' => 'ic-f6'],
                6  => ['color' => '#ec4899', 'numcolor' => '#be185d', 'icon' => 'ic-f7'],
                7  => ['color' => '#14b8a6', 'numcolor' => '#0f766e', 'icon' => 'ic-f8'],
                8  => ['color' => '#f97316', 'numcolor' => '#c2410c', 'icon' => 'ic-f9'],
                9  => ['color' => '#3b82f6', 'numcolor' => '#1d4ed8', 'icon' => 'ic-f10'],
                10 => ['color' => '#a855f7', 'numcolor' => '#7e22ce', 'icon' => 'ic-f11'],
            ];

            $romanNumerals = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

            foreach ($factors as $index => $factor):
                $theme = $cardThemes[$index] ?? $cardThemes[0];

                if ($index === 0) {
                    $factorKey = 'nfaktor';
                } else {
                    $factorKey = 'nfaktor' . ($index + 1);
                }
                $nfaktorValue = $factor[$factorKey] ?? 'N/A';
                if ($nfaktorValue === '0' || $nfaktorValue == 0 || $nfaktorValue === '') {
                    $nfaktorValue = 'N/A';
                }

                $komusApproved = isset($factor['accdekom']) && $factor['accdekom'] == 1;
                $dirutApproved = isset($factor['is_approved']) && $factor['is_approved'] == 1;
                $fullyApproved = $komusApproved && $dirutApproved;

                $numeral = $romanNumerals[$index] ?? ($index + 1);
                ?>
                <a href="<?= esc($factor['link']); ?>" class="report-card <?= $fullyApproved ? 'approved' : '' ?>"
                    style="--card-color: <?= $theme['color'] ?>;">

                    <div class="card-left">
                        <div class="card-roman" style="color: <?= $theme['numcolor'] ?>;">
                            <?= $numeral ?>.
                            <?= esc($factor['name']); ?>
                        </div>
                        <div class="card-periode-info">
                            Periode: Tahun
                            <?= esc($periode_tahun); ?> &nbsp;·&nbsp;
                            <?= esc($namabpr); ?>
                        </div>

                        <div class="card-approvals">
                            <span class="approval-chip <?= $dirutApproved ? 'ok' : 'no' ?>">
                                <?= $dirutApproved ? '✓' : '○' ?> Disetujui oleh: Direktur Utama
                            </span>
                        </div>
                    </div>

                    <div class="card-right" style="background: color-mix(in srgb, <?= $theme['color'] ?> 12%, white);">
                        <svg width="52" height="52" style="color: <?= $theme['color'] ?>;">
                            <use href="#<?= $theme['icon'] ?>"></use>
                        </svg>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php
    $allApproved = true;
    $unapprovedList = [];
    foreach ($factors as $f) {
        if (!(isset($f['is_approved']) && $f['is_approved'] == 1)) {
            $allApproved = false;
            $unapprovedList[] = esc($f['name']);
        }
    }
    $conclusionOk = !empty($showfaktor['kesimpulan'])
        && !empty($showfaktor['positifstruktur'])
        && !empty($showfaktor['positifproses'])
        && !empty($showfaktor['positifhasil'])
        && !empty($showfaktor['negatifstruktur'])
        && !empty($showfaktor['negatifproses'])
        && !empty($showfaktor['negatifhasil']);
    $formOk = !empty($showfaktor['dirut'])
        && !empty($showfaktor['komut'])
        && !empty($showfaktor['tanggal'])
        && !empty($showfaktor['lokasi']);
    $coverOk = !empty($showfaktor['cover']);
    $pdfOk = !empty($showfaktor['pdf1_filename']) && !empty($showfaktor['pdf2_filename']);
    $canGenerate = $allApproved && $conclusionOk && $formOk && $coverOk;

    if ($allApproved && $conclusionOk && $formOk && $coverOk && $pdfOk) {
        $alertMsg = 'Seluruh faktor telah disetujui, dan data kesimpulan serta informasi pendukung lainnya telah terisi.';
        $alertType = 'success';
    } elseif (!$allApproved && !$conclusionOk && !$formOk) {
        $alertMsg = 'ZIP tidak dapat dibuat. Persetujuan Direktur Utama untuk beberapa faktor belum lengkap, dan data kesimpulan serta informasi lainnya belum terisi.';
        $alertType = 'warning';
    } elseif (!$allApproved) {
        $alertMsg = 'ZIP tidak dapat dibuat. Persetujuan Direktur Utama untuk faktor berikut belum lengkap: ' . implode(', ', $unapprovedList) . '.';
        $alertType = 'warning';
    } elseif (!$conclusionOk) {
        $alertMsg = 'ZIP tidak dapat dibuat. Data kesimpulan belum terisi lengkap.';
        $alertType = 'warning';
    } elseif (!$formOk) {
        $alertMsg = 'ZIP tidak dapat dibuat. Data informasi (Direktur Utama, Komisaris Utama, Tanggal, Lokasi) belum terisi lengkap.';
        $alertType = 'warning';
    } elseif (!$coverOk) {
        $alertMsg = 'ZIP tidak dapat dibuat. Cover laporan belum dipilih.';
        $alertType = 'warning';
    } elseif (!$pdfOk) {
        $alertMsg = 'ZIP tidak dapat dibuat. Dokumen pendukung belum di-upload.';
        $alertType = 'warning';
    } else {
        $alertMsg = '';
        $alertType = '';
    }
    ?>

    <?php if ($alertMsg): ?>
        <div class="status-alert <?= $alertType ?>">
            <?= $alertType === 'success' ? '✓' : '⚠' ?>
            <?= $alertMsg ?>
        </div>
    <?php endif; ?>

    <div class="generate-section">
        <p class="generate-note">Cek kembali seluruh data, pastikan informasi BPR dan data yang diperlukan telah terisi
            dengan benar</p>

        <?php if ($canGenerate): ?>
            <a href="<?= base_url('Showtahunan/exportAllToZip') ?>" class="btn-generate" id="btnExportZip">
                EXPORT PELAPORAN APOLO OJK (.ZIP)
            </a>
        <?php else: ?>
            <span class="btn-generate disabled" aria-disabled="true">
                EXPORT PELAPORAN APOLO OJK (.ZIP)
            </span>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(() => {
            document.getElementById('skeletonList').style.display = 'none';
            document.getElementById('actualCards').style.display = 'flex';
        }, 800);

        function debounce(fn, wait) {
            let t;
            return function (...args) { clearTimeout(t); t = setTimeout(() => fn(...args), wait); };
        }

        const debouncedSave = debounce((field, el) => {
            el.style.borderColor = '#10b981';
            setTimeout(() => el.style.borderColor = '', 1500);
        }, 1000);
        document.querySelectorAll('[data-field]').forEach(el => {
            el.addEventListener('input', () => debouncedSave(el.dataset.field, el));
        });

        const btnExport = document.getElementById('btnExportZip');
        if (btnExport) {
            btnExport.addEventListener('click', function (e) {
                this.textContent = 'Sedang menyiapkan file…';
                this.style.opacity = '0.75';
                this.style.pointerEvents = 'none';
                setTimeout(() => {
                    this.textContent = 'EXPORT PELAPORAN APOLO OJK (.ZIP)';
                    this.style.opacity = '';
                    this.style.pointerEvents = '';
                }, 8000);
            });
        }
    });
</script>

<style>
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
        --shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        --shadow-md: 0 4px 20px rgba(0, 0, 0, 0.12);
        --radius: 12px;
        --radius-lg: 16px;
        --primary-gradient: linear-gradient(135deg, #141863 0%, #1f2aa8 100%);
    }

    .info-icon {
        background: rgba(255, 255, 255, 0.25);
        width: 17px;
        height: 17px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        font-style: italic;
        font-weight: 900;
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

    .flash-success,
    .flash-error {
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

    .flash-success button,
    .flash-error button {
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

    .report-cards-list {
        margin-bottom: 24px;
    }

    .skeleton-list {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .skeleton-card {
        background: white;
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
        width: 150px;
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

    .actual-cards {
        flex-direction: column;
        gap: 14px;
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
        cursor: pointer;
    }

    .report-card:hover {
        box-shadow: var(--shadow-md);
        border-color: var(--card-color, var(--gray-200));
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
        color: var(--gray-800);
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
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .approval-chip.ok {
        background: rgba(16, 185, 129, .12);
        color: var(--green-d);
    }

    .approval-chip.no {
        background: rgba(239, 68, 68, .10);
        color: #dc2626;
    }

    .card-right {
        width: 110px;
        min-width: 110px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .section-card {
        background: white;
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
        grid-column: 1 / -1;
    }

    .form-group label {
        font-size: 0.95rem;
        font-weight: 650;
        color: var(--gray-600);
    }

    .form-control {
        width: 100%;
        padding: 9px 13px;
        border: 1.5px solid var(--gray-200);
        border-radius: 8px;
        font-size: 0.87rem;
        font-family: inherit;
        color: var(--gray-800);
        background: var(--gray-50);
        transition: border-color .2s, box-shadow .2s;
        outline: none;
    }

    .form-control:focus {
        border-color: var(--blue);
        background: white;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, .1);
    }

    textarea.form-control {
        resize: vertical;
        min-height: 85px;
    }

    .form-footer {
        display: flex;
        justify-content: center;
        padding-top: 16px;
    }

    .btn-save {
        background: linear-gradient(135deg, var(--green), var(--green-d));
        color: white;
        border: none;
        border-radius: 25px;
        padding: 10px 32px;
        font-size: 0.92rem;
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

    .status-alert {
        padding: 13px 18px;
        border-radius: var(--radius);
        font-size: 0.87rem;
        font-weight: 600;
        margin-top: 18px;
        text-align: center;
    }

    .status-alert.success {
        background: linear-gradient(135deg, #065f46, #059669);
        color: white;
    }

    .status-alert.warning {
        background: linear-gradient(135deg, #92400e, #d97706);
        color: white;
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
        background: linear-gradient(135deg, #141863, #1f2aa8);
        color: white;
        text-decoration: none;
        padding: 12px 36px;
        border-radius: 25px;
        font-size: 1.05rem;
        font-weight: 800;
        letter-spacing: .03em;
        box-shadow: 0 4px 16px rgba(20, 24, 99, .3);
        transition: all .2s;
        cursor: pointer;
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
        opacity: 0.7;
    }

    .approval-status-text {
        font-size: 0.75rem;
        font-weight: 600;
        width: 100%;
        margin-top: 2px;
    }

    .text-approved {
        color: #059669;
    }

    .text-pending {
        color: #dc2626;
    }

    @media (max-width: 768px) {
        .card-right {
            width: 90px;
            min-width: 90px;
        }

        .form-grid-2 {
            grid-template-columns: 1fr;
        }

        .form-group.span-2 {
            grid-column: 1;
        }
    }

    @media (max-width: 576px) {
        .card-right {
            width: 100%;
            height: 90px;
            min-width: unset;
        }

        .report-card {
            flex-direction: column;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        * {
            animation-duration: .01ms !important;
            transition-duration: .01ms !important;
        }
    }
</style>