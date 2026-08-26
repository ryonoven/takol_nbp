<div class="alert beautiful-alert my-4">
    <i class="fas fa-info-circle alert-icon"></i>
    <?php if (isset($bprData) && isset($periodeDetail)): ?>
        <strong><?= esc($bprData['namabpr'] ?? 'Nama BPR') ?></strong> - Periode Pelaporan Tahun
        <?= esc($periodeDetail['tahun']) ?>
    <?php elseif (isset($periodeDetail)): ?>
        <strong>Periode:</strong> Tahun <?= esc($periodeDetail['tahun']) ?>
    <?php else: ?>
        <strong>Periode belum ditentukan</strong>
    <?php endif; ?>
</div>

<?php if (session()->getFlashdata('message')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">&times;</button>
        <strong><?= esc(session()->getFlashdata('message')); ?></strong>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('err')): ?>
    <div class="alert alert-danger" role="alert"><?= esc(session()->getFlashdata('err')); ?></div>
<?php endif; ?>

<a href="<?= base_url('Showtahunan') ?>" class="btn-kembali mb-3">
    <i class="fas fa-arrow-left"></i> Kembali ke halaman periode
</a>

<div class="container-fluid">
    <div class="page-header">
        <h1 class="page-title"><?= esc($judul); ?></h1>
        <div class="page-subtitle">
            Tahun <?= esc($periodeDetail['tahun'] ?? '') ?>
        </div>
        <span class="badge-namabpr"><?= esc($bprData['namabpr'] ?? 'Nama BPR'); ?></span>
    </div>

    <div class="section-title">Ringkasan Transparansi</div>

    <div class="factors-grid-container loading" id="transContainer">
        <div class="skeleton-grid">
            <?php for ($i = 0; $i < 6; $i++): ?>
                <div class="skeleton-factor-card">
                    <div class="skeleton-header skeleton"></div>
                    <div class="skeleton-body">
                        <div class="skeleton skeleton-text title"></div>
                        <div class="skeleton skeleton-text"></div>
                        <div class="skeleton skeleton-badge"></div>
                        <div class="skeleton skeleton-text small"></div>
                        <div class="skeleton skeleton-text small"></div>
                    </div>
                </div>
            <?php endfor; ?>
        </div>

        <div class="factors-grid">
            <?php foreach ($transparan as $trans): ?>
                <div
                    class="factor-grid-card <?= (isset($trans['accdekom']) && $trans['accdekom'] == 1 && isset($trans['is_approved']) && $trans['is_approved'] == 1) ? 'approved-card' : '' ?>">
                    <a href="<?= esc($trans['link']); ?>" style="text-decoration: none; color: inherit;">
                        <div class="factor-card-header">
                            <div class="factor-number"><?= esc($trans['name']); ?></div>
                        </div>
                        <div class="factor-card-body">
                            <div class="factor-info">
                                <div class="bank-name"><?= esc($bprData['namabpr'] ?? 'Nama BPR'); ?></div>
                                <div class="periode-text">Periode Tahun: <?= esc($periodeDetail['tahun'] ?? '') ?></div>
                            </div>

                            <div class="approval-badges">
                                <?php
                                $exemptFactors = [4, 5, 6, 7, 8, 9, 10, 15, 16, 17, 18];
                                $factorNumber = array_search($trans, $transparan) + 1;
                                $isExempt = in_array($factorNumber, $exemptFactors);
                                $komusApproved = isset($trans['accdekom']) && $trans['accdekom'] == 1;
                                $dirutApproved = isset($trans['is_approved']) && $trans['is_approved'] == 1;
                                $hasData = !empty($trans['has_data']);
                                ?>

                                <?php if ($isExempt && !$hasData): ?>
                                    <div class="approval-badge exempt">
                                        <span>—</span> Tidak perlu diisi jika tidak ada data.
                                    </div>

                                <?php else: ?>
                                    <div class="approval-badge <?= $komusApproved ? 'approved' : 'pending' ?>">
                                        <?= $komusApproved ? '✓' : '○' ?>
                                        <?= $komusApproved ? 'Disetujui' : 'Belum disetujui' ?> Komisaris Utama
                                    </div>
                                    <div class="approval-badge <?= $dirutApproved ? 'approved' : 'pending' ?>">
                                        <?= $dirutApproved ? '✓' : '○' ?>
                                        <?= $dirutApproved ? 'Disetujui' : 'Belum disetujui' ?> Direktur Utama
                                    </div>

                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php
    $exemptFactors = [4, 5, 6, 7, 8, 9, 10, 15, 16, 17, 18];
    $allFactorsApproved = true;
    $unapprovedFactors = [];

    foreach ($transparan as $index => $trans) {
        $factorNumber = $index + 1;
        $hasData = !empty($trans['has_data']);

        if (in_array($factorNumber, $exemptFactors) && !$hasData)
            continue;

        if (!(isset($trans['is_approved']) && $trans['is_approved'] == 1)) {
            $allFactorsApproved = false;
            preg_match('/Faktor (\d+)/', $trans['name'], $matches);
            $unapprovedFactors[] = isset($matches[1]) ? 'Faktor ' . $matches[1] : $trans['name'];
        }
    }

    foreach($transparan as $index)

    $disablePdfButton = !$allFactorsApproved;

    $alertMessage = '';
    $alertClass = '';

    if ($allFactorsApproved) {
        $alertMessage = 'Seluruh faktor telah disetujui.';
        $alertClass = 'alert-success';
    } else {
        $alertMessage = 'Persetujuan Direktur Utama pada halaman berikut belum lengkap: ' . implode(', ', $unapprovedFactors);
        $alertClass = 'alert-warning';
    }

    if ($alertMessage !== '' && !$allFactorsApproved) {
        echo '<div class="alert alert-warning" role="alert">';
        echo '<div class="alert-warning-title"><i class="fas fa-exclamation-triangle mr-2"></i><strong>Persetujuan Direktur Utama pada halaman berikut belum lengkap:</strong></div>';
        echo '<ul class="alert-warning-list">';
        foreach ($unapprovedFactors as $i => $factor) {
            echo '<li><span class="alert-num">' . ($i + 1) . '</span><span>' . esc($factor) . '</span></li>';
        }
        echo '</ul></div>';
    }
    ?>
    <div class="card pilihfaktor-card shadow mb-4">
        <div class="card-body">
            <h5 class="font-weight-bold text-gray-800">Pilih Faktor</h5>
            <div class="d-flex justify-content-center flex-wrap gap-1">
                <?php
                $faktors = [
                    1 => 'Penjelasanumum',
                    2 => 'Tgjwbdir',
                    3 => 'Tgjwbdekom',
                    4 => 'Tgjwbkomite',
                    5 => 'Strukturkomite',
                    6 => 'Sahamdirdekom',
                    7 => 'Shmusahadirdekom',
                    8 => 'Shmdirdekomlain',
                    9 => 'Keuangandirdekompshm',
                    10 => 'Keluargadirdekompshm',
                    11 => 'Paketkebijakandirdekom',
                    12 => 'Rasiogaji',
                    13 => 'Rapat',
                    14 => 'Kehadirandekom',
                    15 => 'Fraudinternal',
                    16 => 'Masalahhukum',
                    17 => 'Transaksikepentingan',
                    18 => 'Danasosial'
                ];
                foreach ($faktors as $num => $route): ?>
                    <button type="button" class="btn btn-outline-primary btn-sm faktor-btn"
                        onclick="window.location.href='<?= base_url($route); ?>'">
                        <?= $num ?>
                    </button>
                <?php endforeach; ?>
                <button type="button" class="btn btn-dark btn-sm faktor-btn"
                    onclick="window.location.href='<?= base_url('ShowTransparansi') ?>'">All</button>
            </div>
        </div>
    </div>

    <div class="generate-section">
        <span class="text-secondary">Cek kembali seluruh data, pastikan informasi BPR dan data yang diperlukan telah
            terisi dengan benar</span>
        <a href="/ShowTransparansi/exportAllToZip"
            class="btn btn-outline-info shadow <?= $disablePdfButton ? 'disabled' : '' ?>" <?= $disablePdfButton ? 'aria-disabled="true"' : '' ?>>
            <i class="fa fa-file-archive"></i> GENERATE TXT
        </a>
    </div>
</div>

<style>
    :root {
        --blue: #3b82f6;
        --blue-d: #1d4ed8;
        --primary-gradient: linear-gradient(135deg, #141863 0%, #1f2aa8 100%);
        --success-gradient: linear-gradient(135deg, #146337 0%, #1fa85a 100%);
        --danger-gradient: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
        --warning-gradient: linear-gradient(135deg, #FFA726 0%, #FB8C00 100%);

        --border-radius-sm: 8px;
        --border-radius-md: 12px;
        --border-radius-lg: 20px;
        --border-radius-xl: 25px;

        --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.08);
        --shadow-md: 0 4px 15px rgba(0, 0, 0, 0.12);
        --shadow-lg: 0 8px 25px rgba(0, 0, 0, 0.15);

        --transition-fast: 0.2s ease;
        --transition-normal: 0.3s ease;
    }

        {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        -webkit-font-smoothing: antialiased;
    }

    .container-fluid {
        min-height: 100vh;
        padding: 1.5rem 1rem;
    }

    .page-header {
        text-align: center;
        padding: 28px 20px 22px;
        background: white;
        border-bottom: 1px solid #e9ecef;
    }

    .btn-kembali {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: white;
        border: 1.5px solid var(--gray-200);
        border-radius: var(--r-sm);
        padding: 7px 14px;
        font-size: .875rem;
        font-weight: 600;
        color: var(--gray-500);
        text-decoration: none;
        transition: var(--tr);
        margin: 12px 0 0 8px
    }

    .btn-kembali:hover {
        background: var(--brand-bg);
        border-color: var(--brand-light);
        color: var(--brand);
        text-decoration: none
    }

    .page-title {
        font-size: clamp(1.25rem, 3.5vw, 1.65rem);
        font-weight: 800;
        color: #2c3e50;
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

    .section-title {
        font-size: 1.2rem;
        font-weight: 700;
        color: #2c3e50;
        margin: 1.5rem 0;
        padding-bottom: 0.5rem;
        border-bottom: 3px solid #141863;
    }

    @keyframes skeleton-loading {
        0% {
            background-position: -200% 0;
        }

        100% {
            background-position: 200% 0;
        }
    }

    .skeleton {
        background: linear-gradient(90deg, #f0f0f0 0%, #e0e0e0 20%, #f0f0f0 40%, #f0f0f0 100%);
        background-size: 200% 100%;
        animation: skeleton-loading 1.5s ease-in-out infinite;
        border-radius: var(--border-radius-md);
    }

    .skeleton-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1.5rem;
    }

    .skeleton-factor-card {
        background: white;
        border-radius: var(--border-radius-lg);
        overflow: hidden;
        box-shadow: var(--shadow-md);
    }

    .skeleton-header {
        height: 50px;
        background: linear-gradient(90deg, #141863 0%, #1f2aa8 100%);
    }

    .skeleton-body {
        padding: 1.25rem;
    }

    .skeleton-text {
        height: 14px;
        margin-bottom: 10px;
        border-radius: 4px;
    }

    .skeleton-text.title {
        height: 20px;
        width: 80%;
        margin-bottom: 15px;
    }

    .skeleton-text-title {}

    .skeleton-text.small {
        height: 12px;
        width: 60%;
    }

    .skeleton-badge {
        height: 60px;
        margin: 15px 0;
        border-radius: var(--border-radius-md);
    }

    .factors-grid-container.loading .factors-grid {
        display: none;
    }

    .factors-grid-container.loading .skeleton-grid {
        display: grid;
    }

    .factors-grid-container:not(.loading) .skeleton-grid {
        display: none;
    }

    .factors-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .factor-grid-card {
        background: white;
        border-radius: var(--border-radius-lg);
        overflow: hidden;
        box-shadow: var(--shadow-md);
        transition: transform var(--transition-normal), box-shadow var(--transition-normal);
        display: flex;
        flex-direction: column;
        position: relative;
        will-change: transform;
        backface-visibility: hidden;
        transform: translateZ(0);
    }

    .factor-grid-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--primary-gradient);
        transition: height var(--transition-fast);
    }

    .factor-grid-card.approved-card::before {
        background: var(--success-gradient);
    }

    .factor-grid-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
    }

    .factor-grid-card:hover::before {
        height: 6px;
    }

    .factor-card-header {
        background: var(--primary-gradient);
        color: white;
        padding: 0.75rem 1.25rem;
        text-align: center;
    }

    .factor-grid-card.approved-card .factor-card-header {
        background: var(--success-gradient);
    }

    .factor-number {
        font-weight: 700;
        font-size: 0.95rem;
        margin: 0;
    }

    .factor-card-body {
        padding: 1.25rem;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    .factor-info {
        margin-bottom: 1rem;
    }

    .bank-name {
        color: #141863;
        font-weight: 600;
        font-size: 0.85rem;
        margin-bottom: 0.25rem;
    }

    .periode-text {
        color: #6c757d;
        font-size: 0.75rem;
    }

    .approval-badges {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        margin-top: auto;
    }

    .approval-badge {
        font-size: 0.75rem;
        padding: 0.5rem 0.75rem;
        border-radius: var(--border-radius-sm);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 600;
    }

    .approval-badge.approved {
        background: rgba(56, 239, 125, 0.15);
        color: #11998e;
    }

    .approval-badge.pending {
        background: rgba(255, 107, 107, 0.15);
        color: #e74c3c;
    }

    .pilihfaktor-card {
        border: none;
        border-radius: var(--border-radius-lg);
        background: white;
        box-shadow: var(--shadow-md);
        margin-bottom: 1.5rem;
    }

    .pilihfaktor-card .card-body {
        padding: 1.5rem;
    }

    .pilihfaktor-card h5 {
        color: #2c3e50;
        font-weight: 700;
        font-size: 1.1rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .pilihfaktor-card h5::before {
        content: '';
        width: 4px;
        height: 22px;
        background: var(--primary-gradient);
        border-radius: 4px;
    }

    .gap-1 {
        gap: 4px;
    }

    .flex-wrap {
        flex-wrap: wrap;
    }

    .faktor-btn {
        min-width: 36px;
        font-size: 0.8rem;
        font-weight: 600;
        border-radius: var(--border-radius-sm);
        padding: 4px 10px;
        transition: all var(--transition-fast);
    }

    .alert {
        border: none;
        border-radius: var(--border-radius-md);
        padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow-sm);
    }

    .alert-success {
        background: var(--success-gradient);
        color: white;
    }

    .alert-warning {
        background: var(--warning-gradient);
        color: white;
        font-weight: 500;
    }

    .alert-warning-title {
        font-weight: 700;
        font-size: 0.95rem;
        margin-bottom: 0.75rem;
    }

    .alert-warning-list {
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
        padding: 0;
        margin: 0;
        list-style: none;
    }

    .alert-warning-list li {
        display: flex;
        align-items: flex-start;
        gap: 0.4rem;
        font-size: 0.82rem;
        line-height: 1.4;
    }

    .alert-num {
        background: rgba(255, 255, 255, 0.25);
        border-radius: 50%;
        min-width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.72rem;
        font-weight: 700;
        flex-shrink: 0;
        margin-top: 1px;
    }

    .alert-danger {
        background: var(--danger-gradient);
        color: white;
    }

    .beautiful-alert {
        background-color: #e0f7fa;
        color: #007bb5;
        border-color: #b2ebf2;
        border-radius: 10px;
        padding: 15px 25px;
        display: flex;
        align-items: center;
        font-size: 1.1rem;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .beautiful-alert strong {
        color: #0056b3;
    }

    .beautiful-alert .alert-icon {
        margin-right: 15px;
        font-size: 1.5rem;
        color: #007bff;
    }

    .generate-section {
        text-align: center;
        margin: 2rem 0 1.5rem;
    }

    .generate-section .text-secondary {
        display: block;
        margin-bottom: 1rem;
        color: #6c757d;
        font-size: 0.95rem;
    }

    .btn-outline-info {
        background: var(--primary-gradient);
        border: none;
        color: white;
        padding: 1rem 2.5rem;
        font-size: 1.1rem;
        font-weight: 700;
        border-radius: var(--border-radius-xl);
        box-shadow: var(--shadow-md);
        transition: all var(--transition-normal);
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        will-change: transform;
    }

    .btn-outline-info:hover:not(.disabled) {
        transform: translateY(-3px);
        box-shadow: var(--shadow-lg);
    }

    .btn-outline-info.disabled {
        background: #95a5a6;
        cursor: not-allowed;
        opacity: 0.6;
        box-shadow: none;
    }

    .approval-badge.exempt {
        background: rgba(108, 117, 125, 0.12);
        color: #6c757d;
        font-style: italic;
    }

    .btn-outline-secondary {
        border: 2px solid #6b7280;
        color: #6b7280;
        border-radius: var(--border-radius-sm);
        padding: 6px 14px;
        font-size: 0.9rem;
        background: transparent;
        transition: all var(--transition-fast);
    }

    .btn-outline-secondary:hover {
        background-color: #6b7280;
        color: white;
    }

    .btn-success {
        background: var(--success-gradient);
        border: none;
        border-radius: var(--border-radius-xl);
        padding: 0.75rem 2rem;
        font-size: 1rem;
        font-weight: 600;
        color: white;
        box-shadow: var(--shadow-sm);
        transition: all var(--transition-normal);
        cursor: pointer;
    }

    .btn-success:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
        color: white;
    }

    @media (max-width: 768px) {
        .factors-grid {
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
        }
    }

    @media (max-width: 576px) {
        .factors-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .faktor-btn {
            min-width: 32px;
            padding: 3px 7px;
            font-size: 0.75rem;
        }
    }

    @media (prefers-reduced-motion: reduce) {

        *,
        *::before,
        *::after {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(() => {
            const container = document.getElementById('transContainer');
            if (container) container.classList.remove('loading');
        }, 800);
    });

    function debounce(func, wait) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func(...args), wait);
        };
    }
</script>