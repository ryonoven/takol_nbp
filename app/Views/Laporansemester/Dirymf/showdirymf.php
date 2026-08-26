<?php
$anyPending = false;
foreach ($factors as $f) {
    if (!(isset($f['is_approved']) && $f['is_approved'] == 1)) {
        $anyPending = true;
        break;
    }
}

// Pre-compute values used repeatedly
$semDisplay = esc($periode_semester);
$semNum = strtolower($semDisplay) === 'ganjil' ? '1' : (strtolower($semDisplay) === 'genap' ? '2' : $semDisplay);
$escapedTahun = esc($periode_tahun);
$escapedNamabpr = esc($namabpr);
$isBprEmpty = empty($namabpr) || $namabpr === 'Nama BPR Tidak Ditemukan';

// Pre-define card themes (avoid repeated array lookups)
$cardThemes = [
    0 => ['color'=>'#ef4444','bg'=>'linear-gradient(135deg,#fee2e2 0%,#fecaca 60%,#fca5a5 100%)','numcolor'=>'#dc2626'],
    1 => ['color'=>'#8b5cf6','bg'=>'linear-gradient(135deg,#ede9fe 0%,#ddd6fe 60%,#c4b5fd 100%)','numcolor'=>'#7c3aed'],
    2 => ['color'=>'#10b981','bg'=>'linear-gradient(135deg,#d1fae5 0%,#a7f3d0 60%,#6ee7b7 100%)','numcolor'=>'#059669'],
    3 => ['color'=>'#f59e0b','bg'=>'linear-gradient(135deg,#fef3c7 0%,#fde68a 60%,#fbbf24 100%)','numcolor'=>'#d97706'],
    4 => ['color'=>'#3b82f6','bg'=>'linear-gradient(135deg,#dbeafe 0%,#bfdbfe 60%,#93c5fd 100%)','numcolor'=>'#1d4ed8'],
    5 => ['color'=>'#FF5CC0','bg'=>'linear-gradient(135deg,#FFE6F5 0%,#FFB8E4 60%,#FF8AD2 100%)','numcolor'=>'#FF5CC0'],
];
$romanNumerals = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];

// Pre-render flash messages once
$flashMsg = session()->getFlashdata('message');
$flashErr = session()->getFlashdata('err');
$flashErrors = session()->getFlashdata('errors');

// ===== Validasi kelengkapan Lembar Persetujuan =====
$missingLembar = [];
if (empty(trim($showfaktor['direktur'] ?? '')))
    $missingLembar[] = 'Nama Direktur yang Membawahi Fungsi Kepatuhan';
if (empty(trim($showfaktor['tanggal'] ?? '')))
    $missingLembar[] = 'Tanggal Pelaporan';
if (empty(trim($showfaktor['lokasi'] ?? '')))
    $missingLembar[] = 'Lokasi';
$lembarFilled = empty($missingLembar);

// ===== Validasi faktor yang belum disetujui =====
$unapprovedFactorNames = [];
foreach ($factors as $f) {
    if (!(isset($f['is_approved']) && $f['is_approved'] == 1)) {
        $unapprovedFactorNames[] = $f['name'];
    }
}
$allFactorsApproved = empty($unapprovedFactorNames);

// ===== Validasi upload file =====
$fileUploaded = !empty($showfaktor['pdfdir_filename']);

// ===== Status tombol =====
$disablePengesahan = !$lembarFilled;
$disableCetakPdf = !($allFactorsApproved && $fileUploaded);

// ===== Susun pesan warning (satu item per baris) =====
$warningItems = [];
foreach ($unapprovedFactorNames as $namaFaktor) {
    $warningItems[] = 'Faktor belum disetujui: <strong>' . esc($namaFaktor) . '</strong>';
}
foreach ($missingLembar as $namaField) {
    $warningItems[] = 'Lembar Persetujuan belum lengkap: <strong>' . esc($namaField) . '</strong>';
}
if (!$fileUploaded) {
    $warningItems[] = '<strong>Lembar Pengesahan yang sudah ditandatangani belum diunggah</strong>';
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
    <h1 class="page-title"><?= ($judul) ?></h1>
    <div class="page-subtitle">Semester <?= $semNum ?> Tahun <?= $escapedTahun ?></div>
    <span class="badge-namabpr"><?= $escapedNamabpr ?></span>
</div>

<?php if ($isBprEmpty): ?>
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

        <div class="actual-cards" id="actualCards" style="display:none">
            <?php foreach ($factors as $index => $factor):
                $theme = $cardThemes[$index] ?? $cardThemes[0];
                $dirutApproved = isset($factor['is_approved']) && $factor['is_approved'] == 1;
                $numeral = $romanNumerals[$index] ?? ($index + 1);
                $approvalClass = $dirutApproved ? 'ok' : 'no';
                $approvalMark = $dirutApproved ? '✓' : '○';
            ?>
            <a href="<?= esc($factor['link']) ?>"
               class="report-card<?= $dirutApproved ? ' approved' : '' ?>"
               style="--card-color:<?= $theme['color'] ?>;--card-bg:<?= $theme['bg'] ?>">
                <div class="card-left">
                    <div class="card-roman" style="color:<?= $theme['numcolor'] ?>">
                        <?= $numeral ?>. <?= esc($factor['name']) ?>
                    </div>
                    <div class="card-periode-info">
                        Periode: Semester <?= $semNum ?> Tahun <?= $escapedTahun ?> &nbsp;·&nbsp; <?= $escapedNamabpr ?>
                    </div>
                    <div class="card-approvals">
                        <span class="approval-chip <?= $approvalClass ?>"><?= $approvalMark ?> Telah disetujui oleh: Direktur Yang Membawahkan Fungsi Kepatuhan</span>
                    </div>
                    <div class="card-cta-row">Klik di sini untuk memulai pengisian Form →</div>
                </div>
                <div class="card-right" style="background:<?= $theme['bg'] ?>">
                    <div class="card-right-glow" style="background:radial-gradient(circle,<?= $theme['color'] ?>40 0%,transparent 70%)"></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="section-card">
        <div class="section-header" style="--accent:#10b981">
            <div class="section-accent"></div>
            <h2>Lembar Persetujuan</h2>
        </div>
        <div class="section-body">
            <form method="post" action="<?= base_url('Showdirymf/updatettd') ?>">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="direktur">Nama Direktur yang Membawahi Fungsi Kepatuhan <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="direktur" name="direktur"
                        value="<?= esc($showfaktor['direktur'] ?? '') ?>">
                </div>
                <div class="form-group" style="margin-top:12px">
                    <label for="tanggal">Tanggal Pelaporan <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="tanggal" name="tanggal"
                        value="<?= esc($showfaktor['tanggal'] ?? '') ?>">
                </div>
                <div class="form-group" style="margin-top:12px">
                    <label for="lokasi">Lokasi <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="lokasi" name="lokasi"
                        value="<?= esc($showfaktor['lokasi'] ?? '') ?>">
                </div>

                <?php if (!$lembarFilled): ?>
                <div class="field-warning">
                    <i>⚠</i> Lengkapi field berikut sebelum mencetak Lembar Pengesahan: <?= implode(', ', $missingLembar) ?>
                </div>
                <?php endif; ?>

                <div class="form-footer">
                    <button type="submit" class="btn-save">Simpan Data</button>
                </div>
                <div class="form-footer">
                    <?php if ($disablePengesahan): ?>
                        <a href="#" class="btn-generate-pdf disabled" aria-disabled="true" title="Lengkapi Nama Direktur, Tanggal Pelaporan, dan Lokasi terlebih dahulu">Cetak Lembar Pengesahan</a>
                    <?php else: ?>
                        <a href="<?= base_url('Showdirymf/cetakPengesahan') ?>" target="_blank" class="btn-generate-pdf">Cetak Lembar Pengesahan</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- UPLOAD DOKUMEN CARD -->
    <div class="section-card">
        <div class="section-header" style="--accent:#f59e0b">
            <div class="section-accent"></div>
            <h2>Upload Lembar Pengesahan yang sudah ditandatangani</h2>
        </div>
        <div class="section-body">
            <p class="section-desc">
                Silakan unggah file PDF Lembar Pengesahan yang sudah ditandatangani (.PDF)
            </p>

            <?php if ($flashErrors): ?>
            <div class="flash-error">
                <?php foreach ($flashErrors as $err): ?><div><?= esc($err) ?></div><?php endforeach; ?>
            </div>
            <?php endif; ?>

            <form method="post" action="<?= base_url('Showdirymf/uploadPdf') ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="form">
                    <div class="form-group">
                        <input type="file" name="pdf1" id="pdf1Input" class="form-control file-input" accept="application/pdf">
                        <small>Maksimal ukuran file .PDF 2 MB</small>
                        <div id="fileSizeWarning" style="color:#dc2626;font-size:.78rem;display:none;margin-top:4px">
                            ⚠ File melebihi 2 MB. Silakan pilih file yang lebih kecil.
                        </div>
                        <?php if (!empty($showfaktor['pdfdir_filename'])): ?>
                        <div class="uploaded-file" style="margin-top:8px">
                            📄 <a href="<?= base_url('Showdirymf/downloadPdf/'.esc($showfaktor['pdfdir_filename'])) ?>" target="_blank" rel="noopener">
                                <?= esc($showfaktor['pdfdir_filename']) ?>
                            </a>
                            <span style="font-size:.75rem;color:var(--green-d);font-weight:600;margin-left:6px">✓ Sudah diunggah</span>
                        </div>
                        <?php else: ?>
                        <div class="field-warning" style="margin-top:8px">
                            <i>⚠</i> File Lembar Pengesahan yang sudah ditandatangani belum diunggah.
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

<div class="pdf-action-row" style="gap:12px;flex-wrap:wrap">
    <?php if ($disableCetakPdf): ?>
        <a href="#" class="btn-generate-pdf disabled" aria-disabled="true" title="Pastikan seluruh faktor telah disetujui dan file Lembar Pengesahan telah diunggah">Cetak PDF</a>
    <?php else: ?>
        <a href="<?= base_url('LaporanPdfGcg/LaporanDirekturYangMembawahiFungsiKepatuhan') ?>" target="_blank" class="btn-generate-pdf">Cetak PDF</a>
    <?php endif; ?>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" media="print" onload="this.media='all'">
<script src="https://cdn.jsdelivr.net/npm/flatpickr" defer></script>

<script>
(function () {
    function revealCards() {
        var sk = document.getElementById('skeletonList');
        var ac = document.getElementById('actualCards');
        if (sk) sk.style.display = 'none';
        if (ac) ac.style.display = 'flex';
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            setTimeout(function () { requestAnimationFrame(revealCards); }, 600);
        });
    } else {
        setTimeout(function () { requestAnimationFrame(revealCards); }, 600);
    }

    // PDF size check
    document.addEventListener('change', function (e) {
        if (e.target && e.target.id === 'pdf1Input') {
            var maxSize = 2097152;
            var warning = document.getElementById('fileSizeWarning');
            var btn = document.getElementById('btnUpload');
            var over = e.target.files.length > 0 && e.target.files[0].size > maxSize;
            if (warning) warning.style.display = over ? 'block' : 'none';
            if (btn) { btn.disabled = over; btn.style.opacity = over ? '0.5' : '1'; }
        }
    });

    // Cegah klik pada tombol cetak yang sedang disable
    document.querySelectorAll('a.btn-generate-pdf.disabled').forEach(function (el) {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            alert(el.getAttribute('title') || 'Lengkapi data yang diperlukan terlebih dahulu.');
        });
    });
})();
</script>

<style>
:root{
    --blue:#3b82f6;--blue-d:#1d4ed8;--green:#10b981;--green-d:#059669;
    --red:#ef4444;--amber:#f59e0b;--purple:#8b5cf6;
    --gray-50:#f8fafc;--gray-100:#f1f5f9;--gray-200:#e2e8f0;
    --gray-400:#94a3b8;--gray-600:#475569;--gray-800:#1e293b;
    --shadow:0 2px 12px rgba(0,0,0,.08);--shadow-md:0 4px 20px rgba(0,0,0,.12);
    --radius:12px;--radius-lg:16px;
    --primary-gradient:linear-gradient(135deg,#141863 0%,#1f2aa8 100%);
}
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
.alert-perhatian strong{color:#92400e}.alert-perhatian a{color:var(--amber);font-weight:700}
.alert-warning-box{background:#fffbeb;border:1px solid #fde68a;border-left:4px solid var(--amber);padding:14px 18px;border-radius:var(--radius);margin-bottom:18px;font-size:.85rem;color:#78350f}
.alert-warning-title{font-weight:700;color:#92400e;display:flex;align-items:center;gap:6px;margin-bottom:6px}
.alert-warning-box ul{margin:0;padding-left:20px}
.alert-warning-box li{margin-bottom:4px;line-height:1.5}
.alert-success-box{background:#d1fae5;border:1px solid #a7f3d0;border-left:4px solid var(--green);padding:12px 18px;border-radius:var(--radius);margin-bottom:18px;font-size:.85rem;color:#065f46;font-weight:600;display:flex;align-items:center;gap:8px}
.field-warning{background:#fef2f2;border:1px solid #fecaca;border-left:3px solid var(--red);padding:8px 12px;border-radius:6px;font-size:.78rem;color:#991b1b;margin-top:10px;display:flex;align-items:flex-start;gap:6px}
.text-danger{color:var(--red)}
.main-wrapper{max-width:1080px;margin:0 auto;padding:20px 16px 50px}
.report-cards-list{margin-bottom:24px}
.skeleton-list{display:flex;flex-direction:column;gap:14px}
.skeleton-card{background:#fff;border-radius:var(--radius-lg);overflow:hidden;display:flex;min-height:160px;box-shadow:var(--shadow)}
.skeleton-left{flex:1;padding:24px;display:flex;flex-direction:column;gap:12px}
.skeleton-right{width:280px}
.sk{background:linear-gradient(90deg,#f0f0f0 0%,#e0e0e0 30%,#f0f0f0 60%);background-size:200% 100%;animation:shimmer 1.5s ease-in-out infinite;border-radius:6px}
.sk-title{height:20px;width:70%}.sk-sub{height:13px;width:50%}.sk-box{height:36px;width:55%;border-radius:8px}.sk-badge{height:26px;width:65%;border-radius:6px}
@keyframes shimmer{0%{background-position:-200% 0}100%{background-position:200% 0}}
.actual-cards{flex-direction:column;gap:8px}
.report-card{background:#fff;border-radius:var(--radius-lg);overflow:hidden;display:flex;min-height:125px;box-shadow:var(--shadow);border:1.5px solid var(--gray-200);text-decoration:none;color:inherit;transition:box-shadow .25s,transform .2s;position:relative;cursor:pointer}
.report-card:hover{box-shadow:var(--shadow-md);transform:translateY(-2px)}
.report-card.approved{border-color:#a7f3d0}
.report-card::before{content:'';position:absolute;left:0;top:0;bottom:0;width:5px;background:var(--card-color,var(--blue))}
.card-left{flex:1;padding:20px 22px 20px 28px;display:flex;flex-direction:column;gap:5px;justify-content:center}
.card-roman{font-size:1.2rem;font-weight:800;line-height:1.35;color:var(--gray-800)}
.card-periode-info{font-size:.80rem;color:var(--gray-400);font-weight:500}
.card-approvals{display:flex;gap:8px;flex-wrap:wrap;margin-top:2px}
.approval-chip{font-size:.78rem;font-weight:600;padding:4px 10px;border-radius:6px;display:flex;align-items:center;gap:5px}
.approval-chip.ok{background:rgba(16,185,129,.12);color:var(--green-d)}
.approval-chip.no{background:rgba(239,68,68,.1);color:#dc2626}
.card-cta-row{font-size:.78rem;color:var(--blue);font-weight:600;margin-top:4px}
.card-right{width:70px;min-width:50px;position:relative;overflow:hidden;display:flex;align-items:center;justify-content:center;padding:16px}
.card-right-glow{position:absolute;right:-30px;top:50%;transform:translateY(-50%);width:200px;height:200px;border-radius:50%}
.section-card{background:#fff;border-radius:var(--radius-lg);box-shadow:var(--shadow);border:1px solid var(--gray-200);margin-top:18px;overflow:hidden}
.section-header{padding:16px 22px;border-bottom:1px solid var(--gray-100);display:flex;align-items:center;gap:10px}
.section-accent{width:4px;height:22px;border-radius:4px;background:var(--accent,var(--blue));flex-shrink:0}
.section-header h2{font-size:1rem;font-weight:700;color:var(--gray-800);margin:0}
.section-body{padding:20px 22px}
.section-desc{font-size:.84rem;color:var(--gray-400);margin-bottom:16px}
.form-group{display:flex;flex-direction:column;gap:6px}
.form-control{width:100%;padding:9px 13px;border:1.5px solid var(--gray-200);border-radius:8px;font-size:.87rem;font-family:inherit;color:var(--gray-800);background:var(--gray-50);transition:border-color .2s,box-shadow .2s;outline:none}
.form-control:focus{border-color:var(--blue);background:#fff;box-shadow:0 0 0 3px rgba(59,130,246,.1)}
.file-input{border:2px dashed var(--blue);background:#eff6ff;cursor:pointer;padding:6px 13px}
.file-input:hover{background:#dbeafe;border-color:var(--blue-d)}
.form-group small{font-size:.74rem;color:var(--gray-400)}
.uploaded-file{font-size:.8rem;color:var(--gray-600);margin-top:4px}
.uploaded-file a{color:var(--blue);font-weight:600;text-decoration:none}
.form-footer{display:flex;justify-content:center;padding-top:16px}
.pdf-action-row{display:flex;justify-content:center;padding:14px 16px 0}
.form-group label{font-size:.82rem;font-weight:600;color:var(--gray-600);margin-bottom:2px;display:block}
.btn-generate-pdf{display:inline-flex;align-items:center;gap:8px;background:var(--primary-gradient);color:#fff;text-decoration:none;font-weight:700;font-size:.9rem;padding:10px 26px;border-radius:25px;box-shadow:0 2px 8px rgba(31,42,168,.3);transition:all .2s}
.btn-generate-pdf:hover{transform:translateY(-2px);box-shadow:0 4px 14px rgba(31,42,168,.4);color:#fff;text-decoration:none}
.btn-generate-pdf.disabled{background:#9ca3af;color:#f3f4f6;box-shadow:none;cursor:not-allowed;opacity:.75}
.btn-generate-pdf.disabled:hover{transform:none;box-shadow:none;background:#9ca3af}
.btn-save{background:linear-gradient(135deg,var(--green),var(--green-d));color:#fff;border:none;border-radius:25px;padding:10px 32px;font-size:.92rem;font-weight:700;cursor:pointer;font-family:inherit;transition:all .2s;box-shadow:0 2px 8px rgba(16,185,129,.3)}
.btn-save:hover{transform:translateY(-2px);box-shadow:0 4px 14px rgba(16,185,129,.4)}
@media(max-width:768px){.card-right{width:160px;min-width:130px}}
@media(max-width:576px){.report-card{flex-direction:row}.card-right{width:110px;min-width:90px;flex-shrink:0}.card-roman{font-size:1rem}.card-left{padding:14px 12px 14px 22px}}
@media(prefers-reduced-motion:reduce){*{animation-duration:.01ms!important;transition-duration:.01ms!important}}
</style>