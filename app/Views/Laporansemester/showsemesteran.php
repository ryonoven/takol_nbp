<?php
$anyPending = false;
foreach ($factors as $f) {
    if (!(isset($f['is_approved']) && $f['is_approved'] == 1)) {
        $anyPending = true;
        break;
    }
} ?>

<a href="<?= base_url('Periodesemesteran') ?>" class="btn-kembali mb-3">
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
    <h1 class="page-title"><?= esc($judul); ?></h1>
    <div class="page-subtitle">
        <?php
        $semDisplay = esc($periode_semester);
        $semNum = strtolower($semDisplay) === 'ganjil' ? '1' : (strtolower($semDisplay) === 'genap' ? '2' : $semDisplay);
        ?>
        Semester <?= $semNum ?> Tahun <?= esc($periode_tahun); ?>
    </div>
    <span class="badge-namabpr"><?= esc($namabpr); ?></span>
</div>

<?php if (empty($namabpr) || $namabpr === 'Nama BPR Tidak Ditemukan'): ?>
    <div class="alert-perhatian">
        <strong>Perhatian!</strong>
        Anda belum melengkapi informasi BPR. Klik <a href="<?= base_url('infobpr') ?>">di sini</a> untuk mengisi nama dan
        sandi BPR.
    </div>
<?php endif; ?>

<div class="main-wrapper">

    <div class="report-cards-list" id="factorsContainer">
        <div class="skeleton-list" id="skeletonList" aria-hidden="true">
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
                0 => ['color'=>'#ef4444','bg'=>'linear-gradient(135deg,#fee2e2 0%,#fecaca 60%,#fca5a5 100%)','numcolor'=>'#dc2626','svgType'=>'risk'],
                1 => ['color'=>'#8b5cf6','bg'=>'linear-gradient(135deg,#ede9fe 0%,#ddd6fe 60%,#c4b5fd 100%)','numcolor'=>'#7c3aed','svgType'=>'governance'],
                2 => ['color'=>'#10b981','bg'=>'linear-gradient(135deg,#d1fae5 0%,#a7f3d0 60%,#6ee7b7 100%)','numcolor'=>'#059669','svgType'=>'compliance'],
                3 => ['color'=>'#f59e0b','bg'=>'linear-gradient(135deg,#fef3c7 0%,#fde68a 60%,#fbbf24 100%)','numcolor'=>'#d97706','svgType'=>'audit'],
                4 => ['color'=>'#3b82f6','bg'=>'linear-gradient(135deg,#dbeafe 0%,#bfdbfe 60%,#93c5fd 100%)','numcolor'=>'#1d4ed8','svgType'=>'health'],
            ];
            $romanNumerals = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];

            foreach ($factors as $index => $factor):
                $theme = $cardThemes[$index] ?? $cardThemes[0];
                $factorKey = $index === 0 ? 'nfaktor' : 'nfaktor'.($index+1);
                $nfaktorValue = $factor[$factorKey] ?? 'N/A';
                if ($nfaktorValue === '0' || $nfaktorValue == 0 || $nfaktorValue === '') { $nfaktorValue = 'N/A'; }
                $komusApproved = isset($factor['accdekom']) && $factor['accdekom'] == 1;
                $dirutApproved = isset($factor['is_approved']) && $factor['is_approved'] == 1;
                $fullyApproved = $komusApproved && $dirutApproved;
                $numeral = $romanNumerals[$index] ?? ($index+1);
            ?>
                <a href="<?= esc($factor['link']); ?>" class="report-card <?= $fullyApproved ? 'approved' : '' ?>"
                    style="--card-color:<?= $theme['color'] ?>;--card-bg:<?= $theme['bg'] ?>;">
                    <div class="card-left">
                        <div class="card-roman" style="color:<?= $theme['numcolor'] ?>;">
                            <?= $numeral ?>. <?= esc($factor['name']); ?>
                        </div>
                        <div class="card-periode-info">
                            Periode: Semester <?= $semNum ?> Tahun <?= esc($periode_tahun); ?> &nbsp;·&nbsp; <?= esc($namabpr); ?>
                        </div>
                        <div class="card-approvals">
                            <?php
                                $approvalLabel = ($index === 2)
                                    ? 'Direktur Yang Membawahkan Fungsi Kepatuhan'
                                    : 'Direktur Utama';
                            ?>
                            <span class="approval-chip <?= $dirutApproved ? 'ok' : 'no' ?>">
                                <?= $dirutApproved ? '' : '○' ?> Disetujui oleh: <?= $approvalLabel ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-right" style="background:<?= $theme['bg'] ?>;">
                        <div class="card-right-glow" style="background:radial-gradient(circle,<?= $theme['color'] ?>40 0%,transparent 70%);"></div>
                        <?php if ($theme['svgType']==='risk'): ?>
                            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M12 9V14M12 17.5V18M12 3L2 21H22L12 3Z" stroke="#E53E3E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        <?php elseif ($theme['svgType']==='governance'): ?>
                            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M12 22C12 22 20 18 20 12V5L12 2L4 5V12C4 18 12 22 12 22Z" stroke="#3b1fa8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M9 12L11 14L15 10" stroke="#3b1fa8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        <?php elseif ($theme['svgType']==='compliance'): ?>
                            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M12 11C14.2091 11 16 9.20914 16 7C16 4.79086 14.2091 3 12 3C9.79086 3 8 4.79086 8 7C8 9.20914 9.79086 11 12 11Z" stroke="#2D3748" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M6 21V19C6 16.7909 7.79086 15 10 15H14C16.2091 15 18 16.7909 18 19V21" stroke="#2D3748" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M12 15L10 18H14L12 15Z" fill="#D97706" stroke="#D97706" stroke-width="1"/>
                            </svg>
                        <?php elseif ($theme['svgType']==='audit'): ?>
                            <svg width="80" height="80" viewBox="0 0 160 160" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <circle cx="70" cy="70" r="50" fill="white" fill-opacity="0.8" stroke="#f59e0b" stroke-width="3"/>
                                <circle cx="70" cy="70" r="35" fill="#fef3c7" stroke="#f59e0b" stroke-width="2"/>
                                <line x1="95" y1="95" x2="140" y2="140" stroke="#d97706" stroke-width="10" stroke-linecap="round"/>
                                <rect x="52" y="58" width="36" height="5" rx="2.5" fill="#d97706"/>
                                <rect x="52" y="70" width="26" height="5" rx="2.5" fill="#d97706"/>
                                <rect x="52" y="82" width="30" height="5" rx="2.5" fill="#d97706"/>
                            </svg>
                        <?php else: ?>
                            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M3 21H21M3 10L12 3L21 10M5 21V10M19 21V10M10 21V14H14V21M7 21V14M17 21V14" stroke="#1E3A8A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="12" cy="12" r="11" stroke="#10B981" stroke-width="1.5" stroke-dasharray="4 2"/>
                            </svg>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php
    $allApproved   = true;
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
    $formOk   = !empty($showfaktor['dirut']) && !empty($showfaktor['komut'])
        && !empty($showfaktor['tanggal']) && !empty($showfaktor['lokasi']);
    $coverOk  = !empty($showfaktor['cover']);
    $pdfOk    = !empty($showfaktor['pdf1_filename']) && !empty($showfaktor['pdf2_filename']);
    $canGenerate = $allApproved && $conclusionOk && $formOk && $coverOk;

    if ($allApproved && $conclusionOk && $formOk && $coverOk && $pdfOk) {
        $alertMsg  = 'Seluruh faktor telah disetujui, dan data kesimpulan serta informasi pendukung lainnya telah terisi.';
        $alertType = 'success';
    } elseif (!$allApproved && !$conclusionOk && !$formOk) {
        $alertMsg  = 'ZIP tidak dapat dibuat. Persetujuan Direktur Utama untuk beberapa faktor belum lengkap, dan data kesimpulan serta informasi lainnya belum terisi.';
        $alertType = 'warning';
    } elseif (!$allApproved) {
        $alertMsg  = 'ZIP tidak dapat dibuat. Persetujuan Direktur Utama untuk faktor berikut belum lengkap: ' . implode(', ', $unapprovedList) . '.';
        $alertType = 'warning';
    } elseif (!$conclusionOk) {
        $alertMsg  = 'ZIP tidak dapat dibuat. Data kesimpulan belum terisi lengkap.';
        $alertType = 'warning';
    } elseif (!$formOk) {
        $alertMsg  = 'ZIP tidak dapat dibuat. Data informasi (Direktur Utama, Komisaris Utama, Tanggal, Lokasi) belum terisi lengkap.';
        $alertType = 'warning';
    } elseif (!$coverOk) {
        $alertMsg  = 'ZIP tidak dapat dibuat. Cover laporan pada self assessment tata kelola belum dipilih.';
        $alertType = 'warning';
    } elseif (!$pdfOk) {
        $alertMsg  = 'ZIP tidak dapat dibuat. Dokumen pendukung belum di-upload.';
        $alertType = 'warning';
    } else {
        $alertMsg  = '';
        $alertType = '';
    }
    ?>

    <?php if ($alertMsg): ?>
        <div class="status-alert <?= $alertType ?>">
            <?= $alertType === 'success' ? '' : '⚠' ?> <?= $alertMsg ?>
        </div>
    <?php endif; ?>

    <div class="generate-section">
        <p class="generate-note">Cek kembali seluruh data, pastikan informasi BPR dan data yang diperlukan telah terisi dengan benar</p>

        <?php if ($canGenerate): ?>
            <button class="btn-generate" id="btnExportZip" onclick="startZipExport(event)">
                EXPORT PELAPORAN APOLO OJK (.ZIP)
            </button>
        <?php else: ?>
            <span class="btn-generate disabled" aria-disabled="true">
                EXPORT PELAPORAN APOLO OJK (.ZIP)
            </span>
        <?php endif; ?>
    </div>
</div>

<!-- ZIP Progress Modal -->
<div id="zipModal" class="zip-modal-overlay" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="zipModalTitle">
    <div class="zip-modal-box">
        <div class="zip-modal-header">
            <span id="zipModalTitle" class="zip-modal-title">Menyiapkan File Export</span>
        </div>
        <div class="zip-modal-body">
            <div class="zip-icon-wrap">
                <svg id="zipIconSpinner" class="zip-spinner-svg" width="54" height="54" viewBox="0 0 54 54" fill="none">
                    <circle cx="27" cy="27" r="22" stroke="#e2e8f0" stroke-width="5"/>
                    <circle id="zipSpinnerArc" cx="27" cy="27" r="22" stroke="#1f2aa8" stroke-width="5"
                        stroke-linecap="round" stroke-dasharray="138" stroke-dashoffset="138"
                        transform="rotate(-90 27 27)"/>
                </svg>
                <svg id="zipIconDone" class="zip-done-svg" style="display:none;" width="54" height="54" viewBox="0 0 54 54" fill="none">
                    <circle cx="27" cy="27" r="25" fill="#d1fae5" stroke="#10b981" stroke-width="3"/>
                    <path d="M16 27L23 34L38 19" stroke="#059669" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <svg id="zipIconError" class="zip-error-svg" style="display:none;" width="54" height="54" viewBox="0 0 54 54" fill="none">
                    <circle cx="27" cy="27" r="25" fill="#fee2e2" stroke="#ef4444" stroke-width="3"/>
                    <path d="M19 19L35 35M35 19L19 35" stroke="#dc2626" stroke-width="3.5" stroke-linecap="round"/>
                </svg>
            </div>

            <div class="zip-progress-track">
                <div class="zip-progress-bar" id="zipProgressBar" style="width:0%"></div>
            </div>
            <div class="zip-progress-meta">
                <span id="zipProgressPct">0%</span>
                <span id="zipProgressLabel" class="zip-label">Memulai...</span>
            </div>

            <div id="zipStatusMsg" class="zip-status-msg"></div>

            <div id="zipActions" style="display:none; text-align:center; margin-top:18px;">
                <a id="zipDownloadLink" href="#" class="btn-generate" style="font-size:.92rem; padding:10px 28px;">
                    Unduh Sekarang
                </a>
            </div>

            <button class="zip-modal-close" id="zipModalClose" onclick="closeZipModal()" style="display:none;">
                Tutup
            </button>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" media="print" onload="this.media='all'">
<script src="https://cdn.jsdelivr.net/npm/flatpickr" defer></script>

<script>
(function () {
    'use strict';

    let skeletonTimer = null;
    function revealCards() {
        const sk = document.getElementById('skeletonList');
        const ac = document.getElementById('actualCards');
        if (sk && ac) {
            sk.style.display = 'none';
            ac.style.display = 'flex';
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            skeletonTimer = setTimeout(revealCards, 600);
        });
    } else {
        skeletonTimer = setTimeout(revealCards, 600);
    }

    document.addEventListener('DOMContentLoaded', function () {
        if ('IntersectionObserver' in window) {
            const obs = new IntersectionObserver(entries => {
                entries.forEach(e => {
                    if (e.isIntersecting) { e.target.classList.add('loaded'); obs.unobserve(e.target); }
                });
            }, { rootMargin: '200px' });
            document.querySelectorAll('img[loading="lazy"]').forEach(img => obs.observe(img));
        } else {
            document.querySelectorAll('img[loading="lazy"]').forEach(img => img.classList.add('loaded'));
        }

        const tanggalEl = document.getElementById('tanggal');
        if (tanggalEl && window.flatpickr) {
            flatpickr(tanggalEl, { dateFormat: 'Y/m/d' });
        } else if (tanggalEl) {
            document.querySelector('script[src*="flatpickr"]')?.addEventListener('load', () => {
                flatpickr(tanggalEl, { dateFormat: 'Y/m/d' });
            });
        }

        document.querySelectorAll('.cover-option').forEach(opt => {
            opt.addEventListener('click', function () {
                document.querySelectorAll('.cover-option').forEach(o => o.classList.remove('active'));
                this.classList.add('active');
                const inp = document.getElementById('cover');
                if (inp) inp.value = this.dataset.value;
            });
        });
    });

    let _zipPollInterval = null;
    let _zipToken        = null;
    let _zipStartTime    = null;

    window.startZipExport = function (e) {
        if (e) e.preventDefault();
        openZipModal();
        setProgress(2, 'Memulai proses ekspor...');
        _zipStartTime = Date.now();

        let fakeStep = 3;
        const fakeLabels = [
            'Menyiapkan data BPR...', 'Mengemas data profil risiko...',
            'Mengemas data GCG...', 'Mengemas data kepatuhan...',
            'Mengemas data likuiditas...', 'Mengemas data reputasi...',
            'Mengemas data stratejik...', 'Mengemas faktor GCG (1–4)...',
            'Mengemas faktor GCG (5–8)...', 'Mengemas faktor GCG (9–12)...',
            'Mengemas laporan dirymf...', 'Mengemas laporan audit...',
            'Membuat PDF TKS...', 'Membuat PDF Profil Risiko...',
            'Membuat Lembar Pernyataan...', 'Menambahkan lampiran PDF...',
            'Menutup arsip ZIP...', 'Hampir selesai...',
        ];
        let fakeIdx = 0;

        _zipPollInterval = setInterval(function () {
            if (fakeStep < 90) {
                fakeStep = Math.min(fakeStep + Math.random() * 3.5 + 1.5, 90);
                const label = fakeLabels[fakeIdx] || 'Memproses...';
                fakeIdx = Math.min(fakeIdx + 1, fakeLabels.length - 1);
                setProgress(Math.round(fakeStep), label);
            }
        }, 1200);

        fetch('<?= base_url('Showsemesteran/exportAllToZip') ?>', {
            method : 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
        .then(function (res) {
            if (!res.ok) {
                throw new Error('Server mengembalikan status ' + res.status);
            }
            const contentType = res.headers.get('Content-Type') || '';
            if (contentType.includes('application/zip') || contentType.includes('application/octet-stream')) {
                return res.blob().then(function (blob) {
                    clearInterval(_zipPollInterval);
                    setProgress(100, 'Selesai! Mengunduh file...');

                    const disposition = res.headers.get('Content-Disposition') || '';
                    let filename = 'laporan-semester.zip';
                    const m = disposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/i);
                    if (m && m[1]) filename = m[1].replace(/['"]/g, '');

                    const url  = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href  = url;
                    link.download = filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    setTimeout(() => URL.revokeObjectURL(url), 5000);

                    showDone(filename);
                });
            } else {
                return res.text().then(function (txt) {
                    throw new Error('Respon tidak terduga dari server.');
                });
            }
        })
        .catch(function (err) {
            clearInterval(_zipPollInterval);
            showError(err.message || 'Terjadi kesalahan saat membuat ZIP.');
        });
    };

    function openZipModal() {
        document.getElementById('zipModal').style.display = 'flex';
        document.getElementById('zipIconSpinner').style.display = 'block';
        document.getElementById('zipIconDone').style.display    = 'none';
        document.getElementById('zipIconError').style.display   = 'none';
        document.getElementById('zipActions').style.display     = 'none';
        document.getElementById('zipModalClose').style.display  = 'none';
        document.getElementById('zipStatusMsg').textContent     = '';
    }

    window.closeZipModal = function () {
        clearInterval(_zipPollInterval);
        document.getElementById('zipModal').style.display = 'none';
    };

    function setProgress(pct, label) {
        pct = Math.max(0, Math.min(100, pct));
        const bar = document.getElementById('zipProgressBar');
        const arc = document.getElementById('zipSpinnerArc');
        const circumference = 138;

        bar.style.width = pct + '%';
        document.getElementById('zipProgressPct').textContent  = pct + '%';
        document.getElementById('zipProgressLabel').textContent = label || '';

        if (arc) {
            arc.style.strokeDashoffset = circumference - (circumference * pct / 100);
        }

        if (pct < 40) {
            bar.style.background = 'linear-gradient(90deg,#141863,#1f2aa8)';
        } else if (pct < 80) {
            bar.style.background = 'linear-gradient(90deg,#1f2aa8,#3b82f6)';
        } else {
            bar.style.background = 'linear-gradient(90deg,#3b82f6,#10b981)';
        }
    }

    function showDone(filename) {
        document.getElementById('zipIconSpinner').style.display = 'none';
        document.getElementById('zipIconDone').style.display = 'block';
        document.getElementById('zipModalTitle').textContent = 'Export Berhasil!';
        document.getElementById('zipStatusMsg').textContent = 'File "' + filename + '" berhasil dibuat dan sedang diunduh.';
        document.getElementById('zipModalClose').style.display = 'inline-block';
        document.getElementById('zipProgressLabel').textContent = 'Download dimulai otomatis.';
    }

    function showError(msg) {
        setProgress(0, '');
        document.getElementById('zipIconSpinner').style.display = 'none';
        document.getElementById('zipIconError').style.display = 'block';
        document.getElementById('zipModalTitle').textContent = 'Export Gagal';
        document.getElementById('zipStatusMsg').textContent = msg;
        document.getElementById('zipModalClose').style.display = 'inline-block';
        document.getElementById('zipProgressPct').textContent = '';
        const bar = document.getElementById('zipProgressBar');
        bar.style.width = '100%';
        bar.style.background = 'linear-gradient(90deg,#ef4444,#dc2626)';
    }

})();
</script>

<style>
:root {
    --blue:#3b82f6;--blue-d:#1d4ed8;--green:#10b981;--green-d:#059669;
    --red:#ef4444;--amber:#f59e0b;--purple:#8b5cf6;
    --gray-50:#f8fafc;--gray-100:#f1f5f9;--gray-200:#e2e8f0;
    --gray-400:#94a3b8;--gray-600:#475569;--gray-800:#1e293b;
    --shadow:0 2px 12px rgba(0,0,0,.08);--shadow-md:0 4px 20px rgba(0,0,0,.12);
    --radius:12px;--radius-lg:16px;
    --primary-gradient:linear-gradient(135deg,#141863 0%,#1f2aa8 100%);
}

/* Reset/Base */
.btn-kembali{display:inline-flex;align-items:center;gap:6px;background:#fff;border:1.5px solid var(--gray-200);border-radius:8px;padding:8px 16px;font-size:.9rem;font-weight:600;color:var(--gray-600);text-decoration:none;transition:all .2s;margin:14px 0 0 16px}
.btn-kembali:hover{background:var(--gray-50);border-color:var(--gray-400);color:var(--gray-800)}
.flash-success,.flash-error{margin:10px 16px;padding:12px 18px;border-radius:var(--radius);font-size:.88rem;font-weight:600;display:flex;align-items:center;justify-content:space-between}
.flash-success{background:#d1fae5;color:#065f46;border-left:4px solid var(--green)}
.flash-error{background:#fee2e2;color:#991b1b;border-left:4px solid var(--red)}
.flash-success button,.flash-error button{background:none;border:none;cursor:pointer;font-size:1.1rem;color:inherit;opacity:.6}
.page-header{text-align:center;padding:28px 20px 22px;background:#fff;border-bottom:1px solid var(--gray-200)}
.page-title{font-size:clamp(1.25rem,3.5vw,1.65rem);font-weight:800;color:var(--gray-800);margin-bottom:5px}
.page-subtitle{font-size:1.25rem;font-weight:700;color:var(--blue);margin-bottom:10px}
.badge-namabpr{display:inline-block;background:var(--primary-gradient);color:#fff;font-size:.92rem;font-weight:600;padding:5px 18px;border-radius:20px}
.alert-perhatian{background:#fffbeb;border:1px solid #fde68a;border-left:4px solid var(--amber);margin:14px 16px;padding:12px 16px;border-radius:8px;font-size:.87rem;color:#78350f}
.alert-perhatian strong{color:#92400e}
.alert-perhatian a{color:var(--amber);font-weight:700}
.main-wrapper{max-width:1080px;margin:0 auto;padding:20px 16px 50px}
.report-cards-list{margin-bottom:24px}

/* Skeletons */
.skeleton-list{display:flex;flex-direction:column;gap:14px}
.skeleton-card{background:#fff;border-radius:var(--radius-lg);overflow:hidden;display:flex;min-height:160px;box-shadow:var(--shadow)}
.skeleton-left{flex:1;padding:24px;display:flex;flex-direction:column;gap:12px}
.skeleton-right{width:280px}
.sk{background:linear-gradient(90deg,#f0f0f0 0%,#e0e0e0 30%,#f0f0f0 60%);background-size:200% 100%;animation:shimmer 1.5s ease-in-out infinite;border-radius:6px}
.sk-title{height:20px;width:70%}
.sk-sub{height:13px;width:50%}
.sk-box{height:36px;width:55%;border-radius:8px}
.sk-badge{height:26px;width:65%;border-radius:6px}
@keyframes shimmer{0%{background-position:-200% 0}100%{background-position:200% 0}}

/* Cards */
.actual-cards{flex-direction:column;gap:14px}
.report-card{background:#fff;border-radius:var(--radius-lg);overflow:hidden;display:flex;min-height:155px;box-shadow:var(--shadow);border:1.5px solid var(--gray-200);text-decoration:none;color:inherit;transition:box-shadow .25s,transform .2s;position:relative;cursor:pointer;contain:layout style}
.report-card:hover{box-shadow:var(--shadow-md);transform:translateY(-2px)}
.report-card.approved{border-color:#a7f3d0}
.report-card::before{content:'';position:absolute;left:0;top:0;bottom:0;width:5px;background:var(--card-color,var(--blue))}
.card-left{flex:1;padding:20px 22px 20px 28px;display:flex;flex-direction:column;gap:8px;justify-content:center}
.card-roman{font-size:1.2rem;font-weight:800;line-height:1.35;color:var(--gray-800)}
.card-periode-info{font-size:.80rem;color:var(--gray-400);font-weight:500}
.card-approvals{display:flex;gap:8px;flex-wrap:wrap;margin-top:2px}
.approval-chip{font-size:.78rem;font-weight:600;padding:4px 10px;border-radius:6px;display:flex;align-items:center;gap:5px}
.approval-chip.ok{background:rgba(16,185,129,.12);color:var(--green-d)}
.approval-chip.no{background:rgba(239,68,68,.10);color:#dc2626}
.card-right{width:150px;min-width:150px;position:relative;overflow:hidden;display:flex;align-items:center;justify-content:center;padding:16px}
.card-right-glow{position:absolute;right:-30px;top:50%;transform:translateY(-50%);width:200px;height:200px;border-radius:50%}

/* Status / Generate */
.status-alert{padding:13px 18px;border-radius:var(--radius);font-size:.87rem;font-weight:600;margin-top:18px;text-align:center}
.status-alert.success{background:linear-gradient(135deg,#065f46,#059669);color:#fff}
.status-alert.warning{background:linear-gradient(135deg,#92400e,#d97706);color:#fff}
.generate-section{text-align:center;margin-top:5px;padding:28px 0 10px}
.generate-note{font-size:.85rem;color:var(--gray-400);margin-bottom:16px}
.btn-generate{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#141863,#1f2aa8);color:#fff;text-decoration:none;padding:12px 36px;border-radius:25px;font-size:1.05rem;font-weight:800;letter-spacing:.03em;box-shadow:0 4px 16px rgba(20,24,99,.3);transition:all .2s;cursor:pointer;border:none;font-family:inherit}
.btn-generate:hover:not(.disabled){transform:translateY(-3px);box-shadow:0 6px 22px rgba(20,24,99,.4)}
.btn-generate.disabled{background:#94a3b8;box-shadow:none;cursor:not-allowed;pointer-events:none;opacity:.7}

/* ZIP Progress Modal */
.zip-modal-overlay{position:fixed;inset:0;background:rgba(15,23,42,.55);backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px);z-index:9999;display:flex;align-items:center;justify-content:center;padding:16px}
.zip-modal-box{background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.25);width:100%;max-width:420px;overflow:hidden;animation:modalIn .3s cubic-bezier(.34,1.56,.64,1) both}
@keyframes modalIn{from{opacity:0;transform:scale(.88) translateY(20px)}to{opacity:1;transform:none}}
.zip-modal-header{background:var(--primary-gradient);padding:18px 24px;display:flex;align-items:center;justify-content:space-between}
.zip-modal-title{color:#fff;font-size:1.05rem;font-weight:700;letter-spacing:.02em}
.zip-modal-body{padding:26px 28px 22px;display:flex;flex-direction:column;align-items:center;gap:16px}
.zip-icon-wrap{position:relative;width:54px;height:54px}
.zip-spinner-svg{display:block}
.zip-spinner-svg #zipSpinnerArc{transition:stroke-dashoffset .5s ease}
.zip-done-svg,.zip-error-svg{position:absolute;inset:0}

.zip-progress-track{width:100%;height:10px;background:var(--gray-100);border-radius:8px;overflow:hidden;position:relative}
.zip-progress-bar{height:100%;border-radius:8px;background:var(--primary-gradient);transition:width .6s cubic-bezier(.4,0,.2,1),background .4s;position:relative;overflow:hidden}
.zip-progress-bar::after{content:'';position:absolute;inset:0;background:linear-gradient(90deg,transparent 0%,rgba(255,255,255,.3) 50%,transparent 100%);animation:shimmerBar 1.5s infinite}
@keyframes shimmerBar{0%{transform:translateX(-100%)}100%{transform:translateX(200%)}}

.zip-progress-meta{width:100%;display:flex;align-items:center;justify-content:space-between;gap:12px}
#zipProgressPct{font-size:1.4rem;font-weight:800;color:var(--gray-800);min-width:52px;line-height:1}
.zip-label{font-size:.82rem;color:var(--gray-600);text-align:right;line-height:1.4;flex:1}
.zip-status-msg{font-size:.88rem;color:var(--gray-600);text-align:center;line-height:1.5;min-height:1em}
.zip-modal-close{background:var(--gray-100);border:1px solid var(--gray-200);border-radius:8px;padding:8px 22px;font-size:.88rem;font-weight:600;color:var(--gray-600);cursor:pointer;font-family:inherit;transition:all .2s;margin-top:4px}
.zip-modal-close:hover{background:var(--gray-200);color:var(--gray-800)}

@media(max-width:768px){
    .card-right{width:200px;min-width:160px}
    .zip-modal-box{max-width:95vw}
}
@media(max-width:576px){
    .report-card{flex-direction:column}
    .card-right{width:100%;height:140px;min-width:unset}
}
@media(prefers-reduced-motion:reduce){
    *{animation-duration:.01ms!important;transition-duration:.01ms!important}
    .zip-progress-bar::after{display:none}
}
</style>