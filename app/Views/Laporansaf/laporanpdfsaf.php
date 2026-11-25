<?php
$semNum = \App\Models\LaporanPdfSafModel::semNum($h['semester']);
$st = (int) $h['status'];
$kunci = ($st === 2 || !$canEdit) ? 'disabled' : '';
$boot = [
    'periode' => $periodeId,
    'status' => $st,
    'canEdit' => (bool) $canEdit,
    'sem' => $semNum,
    'tahun' => $h['tahun'],
    'fraud' => $fraud,
    'tree' => $tree,
    'csrf' => ['name' => csrf_token(), 'hash' => csrf_hash()],
    'url' => [
        'simpan' => base_url('laporanpdfsaf/simpan'),
        'status' => base_url('laporanpdfsaf/status'),
        'item' => base_url('laporanpdfsaf/simpanitem'),
        'hapus' => base_url('laporanpdfsaf/hapusitem'),
    ],
];

$levelLabel = ['dekom' => 'Dewan Komisaris', 'direksi' => 'Direktur Utama'];
$approval = $approval ?? [];
$canApprove = $canApprove ?? [];

$ikonPilar = [1 => 'fa-shield-alt', 2 => 'fa-search', 3 => 'fa-gavel', 4 => 'fa-chart-line', 5 => 'fa-tasks'];
$backUrl = $backUrl ?? base_url('Showsaf');
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" media="print" onload="this.media='all'"
    href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">

<div id="toast-wrap" style="position:fixed;top:20px;right:20px;z-index:99999;min-width:290px;pointer-events:none;">
</div>

<div class="container-fluid" id="saf-app">
    <a href="<?= esc($backUrl) ?>" class="btn-kembali mb-3">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>

    <!-- HEADER HALAMAN -->
    <div class="card card-body mb-3 shadow-sm">
        <div class="page-header">
            <div class="saf-badge-wrap">
                <?php foreach ($levelLabel as $lv => $lb):
                    $a = $approval[$lv] ?? ['required' => true, 'approved' => false, 'at' => null];
                    if (empty($a['required'])) {
                        $cls = 'badge-status-secondary';
                        $ic = 'fa-minus-circle';
                        $sub = 'Tidak diperlukan';
                    } elseif (!empty($a['approved'])) {
                        $cls = 'badge-status-success';
                        $ic = 'fa-check-circle';
                        $sub = 'Disetujui' . (!empty($a['at']) ? ' · ' . date('d/m/Y H:i', strtotime($a['at'])) : '');
                    } else {
                        $cls = 'badge-status-secondary';
                        $ic = 'fa-clock';
                        $sub = 'Belum disetujui seluruhnya';
                    }
                    ?>
                    <div class="approval-status-badge saf-badge-<?= $lv === 'dekom' ? 'left' : 'right' ?> <?= $cls ?>"
                        data-level="<?= $lv ?>">
                        <i class="fas <?= $ic ?>"></i>
                        <div>
                            <span class="approval-status-label"><?= esc($lb) ?></span><br>
                            <span class="approval-status-sub"><?= esc($sub) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <h1 class="page-title">Laporan Deskripsi Fraud / Modus Operandi</h1>
            <div class="page-subtitle">Semester <?= esc($semNum) ?> Tahun <?= esc($h['tahun']) ?></div>
            <?php if (!empty($h['namabpr'])): ?>
                <span class="badge-namabpr"><?= esc($h['namabpr']) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($fraud !== null): ?>
        <?php
        $fs = static fn($n, $z) => $n > 0 ? $n . ' data' : ($z ? 'Tidak Ada Data' : 'Belum diinput');
        $fc = static fn($n, $z) => $n > 0 ? 'saf-chip-ok' : ($z ? 'saf-chip-none' : 'saf-chip-wait');
        $fic = static fn($n, $z) => $n > 0 ? 'fa-check-circle' : ($z ? 'fa-minus-circle' : 'fa-clock');
        ?>
        <div class="saf-info">
            <div class="saf-info-icon"><i class="fas fa-link"></i></div>
            <div class="saf-info-body">
                <div class="saf-info-title">Data terkait periode ini (Penerapan SAF)</div>
                <div class="saf-chips">
                    <span class="saf-chip <?= $fc($fraud['st'], $fraud['st_null']) ?>">
                        <i class="fas <?= $fic($fraud['st'], $fraud['st_null']) ?>"></i>
                        Semesteran: <?= $fs($fraud['st'], $fraud['st_null']) ?>
                    </span>
                    <span class="saf-chip <?= $fc($fraud['inc'], $fraud['inc_null']) ?>">
                        <i class="fas <?= $fic($fraud['inc'], $fraud['inc_null']) ?>"></i>
                        Insidental: <?= $fs($fraud['inc'], $fraud['inc_null']) ?>
                    </span>
                </div>
            </div>
            <button type="button" id="btnFraud" class="btn btn-primary btn-sm" <?= $kunci ?>>
                <i class="fas fa-magic"></i> Isi draf Pilar 3
            </button>
        </div>
    <?php endif; ?>

    <!-- PARAGRAF PEMBUKA -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 saf-card-head">
            <h5 class="m-0 font-weight-bold text-white">
                <i class="fas fa-align-left"></i> Perkembangan Pelaksanaan
                <small class="saf-head-sub">Paragraf Pembuka</small>
            </h5>
        </div>
        <div class="card-body p-3">
            <textarea id="ringkasan" class="form-control" rows="8" maxlength="5000" <?= $kunci ?>><?= esc((string) $h['ringkasan_umum']) ?></textarea>
            <div class="saf-count-row"><span id="ringkasan-count" class="saf-count">0 / 5000</span></div>
        </div>
    </div>

    <!-- PILAR -->
    <?php foreach ($pilar as $p => $nama): ?>
        <div class="card shadow mb-4">
            <div class="card-header py-3 saf-card-head">
                <h5 class="m-0 font-weight-bold text-white saf-pilar-title">
                    <?php if ($p < 5): ?>
                        <span class="kcard-index"><?= (int) $p ?></span>
                    <?php else: ?>
                        <span class="kcard-index"><i class="fas <?= $ikonPilar[$p] ?? 'fa-tasks' ?>"></i></span>
                    <?php endif; ?>
                    <span><?= esc($nama) ?></span>
                </h5>
                <span class="saf-count-pill" id="pilar-count-<?= (int) $p ?>"></span>
            </div>
            <div class="card-body p-3" id="pilar-<?= (int) $p ?>"></div>
            <div class="card-footer saf-card-foot">
                <button type="button" class="btn btn-outline-primary btn-sm" data-add="<?= (int) $p ?>">
                    <i class="fas fa-plus"></i> <?= $p < 5 ? 'Tambah program' : 'Tambah poin' ?>
                </button>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- PENGESAHAN -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 saf-card-head">
            <h5 class="m-0 font-weight-bold text-white"><i class="fas fa-signature"></i> Pengesahan</h5>
        </div>
        <div class="card-body p-3">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="font-weight-bold">Kota</label>
                        <input id="kota" class="form-control" maxlength="100"
                            value="<?= esc((string) $h['kota_ttd']) ?>" <?= $kunci ?>>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="font-weight-bold">Tanggal laporan</label>
                        <input id="tanggal" type="date" class="form-control"
                            value="<?= esc((string) $h['tanggal_laporan']) ?>" <?= $kunci ?>>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="font-weight-bold">Tanda tangan <small
                                class="text-muted font-weight-normal">(PNG/JPG, maks. 150 KB. Opsional)</small></label>
                        <input id="ttd" type="file" accept="image/png,image/jpeg" class="form-control" <?= $kunci ?>>
                        <?php if ($h['ttd_path']): ?>
                            <small class="saf-ttd-ok"><i class="fas fa-check-circle"></i> Tanda tangan sudah
                                tersimpan.</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label class="font-weight-bold">Nama pembuat</label>
                        <input id="nama" class="form-control" maxlength="150"
                            value="<?= esc((string) $h['nama_pembuat']) ?>" <?= $kunci ?>>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label class="font-weight-bold">Jabatan</label>
                        <input id="jabatan" class="form-control" maxlength="200"
                            value="<?= esc((string) $h['jabatan_pembuat']) ?>" <?= $kunci ?>>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- AKSI -->
    <div class="text-center mt-2 mb-3">
        <div class="card shadow-sm action-card saf-actions">
            <div class="card-body py-3 text-center">
                <button type="button" id="btnSimpan" class="btn btn-primary" <?= $kunci ?>>
                    <i class="fas fa-save"></i> Simpan Semua
                </button>
                <!-- <a href="<?= base_url('laporanpdfsaf/pdf/' . $periodeId) ?>" target="_blank" rel="noopener"
                    class="btn btn-outline-secondary">
                    <i class="fas fa-file-pdf"></i> Lihat PDF
                </a>
                <a href="<?= base_url('laporanpdfsaf/pdffraud/' . $periodeId) ?>" target="_blank" rel="noopener"
                    class="btn btn-outline-secondary">
                    <i class="fas fa-file-pdf"></i> Lihat PDF Kejadian Fraud
                </a> -->
                <?php if ($canEdit): ?>
                    <small class="d-block text-muted mt-2">
                        <i class="fas fa-info-circle"></i> Menyimpan perubahan akan mereset approval.
                    </small>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- APPROVAL: satu card per level yang dibutuhkan & boleh di-approve user ini -->
    <div class="saf-appr-cards mb-5">
        <?php foreach ($levelLabel as $lv => $lb):
            $a = $approval[$lv] ?? [];
            if (empty($a['required']) || empty($canApprove[$lv])) {
                continue;
            }
            $sudah = !empty($a['approved']); ?>
            <div class="saf-appr-card">
                <div class="saf-appr-title"><i class="fas fa-user-tie"></i> <?= esc($lb) ?></div>
                <div class="saf-appr-btns">
                    <button type="button" class="btn btn-primary btn-sm" data-aksi="setujui" data-level="<?= $lv ?>"
                        data-label="<?= esc($lb) ?>" <?= $sudah ? 'disabled' : '' ?>>
                        <i class="fas fa-check-double"></i> Setujui
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm" data-aksi="batal" data-level="<?= $lv ?>"
                        data-label="<?= esc($lb) ?>" <?= $sudah ? '' : 'disabled' ?>>
                        <i class="fas fa-ban"></i> Batal
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #141863 0%, #1f2aa8 100%);
        --brand: #4f46e5;
        --brand-light: #818cf8;
        --brand-dark: #3730a3;
        --brand-bg: #eef2ff;
        --success: #10b981;
        --success-bg: #d1fae5;
        --danger: #ef4444;
        --danger-bg: #fee2e2;
        --warning: #f59e0b;
        --warning-bg: #fef3c7;
        --info: #06b6d4;
        --gray-50: #f8fafc;
        --gray-100: #f1f5f9;
        --gray-200: #e2e8f0;
        --gray-400: #94a3b8;
        --gray-500: #64748b;
        --gray-700: #334155;
        --gray-800: #1e293b;
        --gray-900: #0f172a;
        --blue: #1f2aa8;
        --r-sm: 8px;
        --r: 12px;
        --r-lg: 16px;
        --sh-sm: 0 1px 3px rgba(0, 0, 0, .08), 0 1px 2px rgba(0, 0, 0, .06);
        --sh: 0 4px 6px -1px rgba(0, 0, 0, .1), 0 2px 4px -1px rgba(0, 0, 0, .06);
        --sh-lg: 0 10px 25px -3px rgba(0, 0, 0, .1), 0 4px 6px -2px rgba(0, 0, 0, .05);
        --tr: all .2s cubic-bezier(.4, 0, .2, 1);
        --grad: linear-gradient(135deg, #141863 0%, #1f2aa8 100%);
    }

    #saf-app,
    #toast-wrap {
        font-family: 'Plus Jakarta Sans', system-ui, -apple-system, 'Segoe UI', sans-serif;
    }

    /*Header halaman*/
    #saf-app .page-header {
        text-align: center;
        padding: 32px 20px 24px;
        background: white;
        border-bottom: 1px solid var(--gray-100);
        position: relative;
        overflow: hidden;
    }

    #saf-app .page-header::before,
    #saf-app .page-header::after {
        content: '';
        position: absolute;
        border-radius: 50%;
    }

    #saf-app .page-header::before {
        top: -60px;
        right: -60px;
        width: 200px;
        height: 200px;
        background: radial-gradient(circle, #eef2ff 0%, transparent 70%);
    }

    #saf-app .page-header::after {
        bottom: -40px;
        left: -40px;
        width: 160px;
        height: 160px;
        background: radial-gradient(circle, #f0fdf4 0%, transparent 70%);
    }

    #saf-app .page-title {
        font-size: clamp(1.3rem, 3.5vw, 1.75rem);
        font-weight: 800;
        color: var(--gray-900);
        margin-bottom: 6px;
        letter-spacing: -.5px;
        position: relative;
        z-index: 1;
    }

    #saf-app .page-subtitle {
        font-size: 1rem;
        font-weight: 600;
        color: var(--gray-900);
        margin-bottom: 12px;
        position: relative;
        z-index: 1;
    }

    #saf-app .badge-namabpr {
        display: inline-block;
        background: var(--primary-gradient);
        color: white;
        font-size: 1rem;
        font-weight: 700;
        padding: 6px 20px;
        border-radius: 100px;
        letter-spacing: .3px;
        position: relative;
        z-index: 1;
        box-shadow: 0 4px 12px rgba(79, 70, 229, .3);
    }

    /* Dua badge approval: Dewan Komisaris di pojok kiri, Direktur Utama di pojok kanan */
    .saf-badge-wrap {
        position: relative;
        z-index: 1;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 16px;
    }

    .saf-badge-wrap .saf-badge-left {
        margin-right: auto;
    }

    .saf-badge-wrap .saf-badge-right {
        margin-left: auto;
    }

    #saf-app .approval-status-badge {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 16px;
        border-radius: var(--r);
        font-size: 13px;
        font-weight: 600;
        text-align: center;
        min-width: 240px;
        justify-content: center;
        border: 1.5px solid transparent;
    }

    #saf-app .approval-status-badge i {
        font-size: 18px;
        flex-shrink: 0;
    }

    #saf-app .approval-status-label {
        font-weight: 700;
        font-size: 12px;
    }

    #saf-app .approval-status-sub {
        font-size: 11px;
        opacity: .8;
    }

    .badge-status-secondary {
        background: var(--gray-100);
        color: var(--gray-500);
        border-color: var(--gray-200) !important;
    }

    .badge-status-warning {
        background: var(--warning-bg);
        color: #92400e;
        border-color: #fcd34d !important;
    }

    .badge-status-success {
        background: var(--success-bg);
        color: #065f46;
        border-color: #6ee7b7 !important;
    }

    /*Tombol kembali*/
    .btn-kembali {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: white;
        border: 1.5px solid var(--gray-200);
        border-radius: var(--r-sm);
        padding: 8px 16px;
        font-size: .875rem;
        font-weight: 600;
        color: var(--gray-500);
        text-decoration: none;
        transition: var(--tr);
        margin: 14px 0 0 16px;
    }

    .btn-kembali:hover {
        background: var(--brand-bg);
        border-color: var(--brand-light);
        color: var(--brand);
        text-decoration: none;
    }

    /*Banner info fraud*/
    .saf-info {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 14px;
        background: var(--brand-bg);
        border: 1.5px solid #c7d2fe;
        border-radius: var(--r);
        padding: 12px 16px;
        margin-bottom: 20px;
    }

    .saf-info-icon {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: var(--grad);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .saf-info-body {
        flex: 1;
        min-width: 220px;
    }

    .saf-info-title {
        font-size: 13px;
        font-weight: 800;
        color: var(--brand-dark);
        margin-bottom: 6px;
    }

    .saf-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .saf-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 12px;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 100px;
        border: 1.5px solid transparent;
    }

    .saf-chip-ok {
        background: var(--success-bg);
        color: #065f46;
        border-color: #a7f3d0;
    }

    .saf-chip-wait {
        background: var(--warning-bg);
        color: #92400e;
        border-color: #fcd34d;
    }

    .saf-chip-none {
        background: var(--gray-100);
        color: var(--gray-500);
        border-color: var(--gray-200);
    }

    /*Card & header section*/
    #saf-app .card {
        border-radius: var(--r-lg);
        overflow: hidden;
    }

    #saf-app .saf-card-head {
        background: var(--grad);
        border: none;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .saf-head-sub {
        font-size: 12px;
        font-weight: 600;
        color: rgba(255, 255, 255, .7);
        margin-left: 8px;
    }

    .saf-pilar-title {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }

    .saf-count-pill {
        background: rgba(255, 255, 255, .18);
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 12px;
        border-radius: 100px;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .saf-card-foot {
        background: var(--gray-50);
        border-top: 1px solid var(--gray-100);
        padding: 10px 16px;
    }

    .saf-count-row {
        display: flex;
        justify-content: flex-end;
        margin-top: 6px;
    }

    .saf-count {
        font-size: 11px;
        color: var(--gray-400);
        font-weight: 600;
        transition: color .2s;
    }

    .saf-count.danger {
        color: var(--danger);
    }

    .saf-ttd-ok {
        display: block;
        margin-top: 6px;
        color: #065f46;
        font-weight: 600;
    }

    /*Kartu program / poin*/
    .saf-item {
        background: white;
        border-radius: var(--r-lg);
        box-shadow: var(--sh-sm);
        border: 1.5px solid var(--gray-100);
        overflow: hidden;
        margin-bottom: 14px;
        transition: box-shadow .2s, border-color .2s;
    }

    .saf-item:last-child {
        margin-bottom: 0;
    }

    .saf-item:hover {
        box-shadow: var(--sh-lg);
        border-color: var(--brand-light);
    }

    .saf-item-body {
        padding: 16px 18px 6px;
    }

    .saf-sub {
        margin: 4px 0 16px 6px;
        padding-left: 14px;
        border-left: 3px solid var(--warning);
    }

    .kcard-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        background: var(--grad);
        gap: 10px;
    }

    .kcard-nomor {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
        flex: 1;
    }

    .kcard-index {
        background: rgba(255, 255, 255, .2);
        color: white;
        font-size: 11px;
        font-weight: 800;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .kcard-nomor-text {
        color: rgba(255, 255, 255, .9);
        font-size: 16px;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        letter-spacing: .3px;
    }

    .kcard-actions {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-shrink: 0;
    }

    .kcard-btn {
        width: 28px;
        height: 28px;
        padding: 0 !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--r-sm) !important;
        font-size: 12px !important;
    }

    .kcard-btn.kcard-btn-txt {
        width: auto;
        padding: 0 10px !important;
        gap: 5px;
    }

    .kcard-col-title {
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .6px;
        color: var(--brand-dark);
        background: var(--brand-bg);
        padding: 4px 10px;
        border-radius: 100px;
        display: inline-block;
        width: fit-content;
    }

    .empty-state-box {
        text-align: center;
        padding: 60px 20px;
        background: var(--gray-50);
        border-radius: var(--r-lg);
        border: 2px dashed var(--gray-200);
    }

    .empty-state-box.saf-empty {
        padding: 28px 20px;
    }

    /*Form*/
    #saf-app .form-group {
        margin-bottom: 16px;
    }

    #saf-app .form-control {
        width: 100%;
        box-sizing: border-box;
        height: auto;
        border-radius: 8px;
        border: 1.5px solid var(--gray-200);
        padding: 8px 12px;
        font-size: 13.5px;
        transition: border-color .15s, box-shadow .15s;
    }

    #saf-app .form-control:focus {
        border-color: var(--brand);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, .15);
        outline: none;
    }

    #saf-app .form-control:disabled {
        background: var(--gray-100);
        color: var(--gray-500);
    }

    #saf-app label {
        font-size: 13px;
        color: var(--gray-700);
        margin-bottom: 6px;
    }

    /*Tombol*/
    #saf-app .btn {
        transition: var(--tr);
        font-weight: 600;
    }

    #saf-app .btn:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: var(--sh);
    }

    #saf-app .btn:active:not(:disabled) {
        transform: scale(.96);
    }

    #saf-app .btn-sm {
        font-size: 12px;
    }

    .saf-actions {
        display: inline-block;
        min-width: 260px;
        max-width: 100%;
        border-radius: var(--r) !important;
        border: 1.5px solid var(--gray-200) !important;
    }

    /*Kartu approval*/
    .saf-appr-cards {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 16px;
    }

    .saf-appr-card {
        min-width: 230px;
        background: #fff;
        border: 1.5px solid var(--gray-200);
        border-radius: var(--r);
        box-shadow: var(--sh);
        padding: 12px 18px 14px;
        text-align: center;
        transition: var(--tr);
    }

    .saf-appr-card:hover {
        border-color: var(--brand-light);
        box-shadow: 0 8px 24px rgba(79, 70, 229, .12);
        transform: translateY(-3px);
    }

    .saf-appr-title {
        font-size: 14px;
        font-weight: 700;
        color: var(--gray-800);
        margin-bottom: 10px;
    }

    .saf-appr-title i {
        color: var(--success);
        margin-right: 4px;
    }

    .saf-appr-btns {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 6px;
    }

    /*Toast*/
    .t-item {
        background: white;
        border-radius: var(--r);
        padding: 14px 18px;
        margin-bottom: 10px;
        box-shadow: var(--sh-lg);
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 13.5px;
        animation: tIn .3s ease;
        pointer-events: all;
        border-left: 4px solid;
    }

    .t-item.t-success {
        border-color: var(--success);
        color: #065f46;
    }

    .t-item.t-error {
        border-color: var(--danger);
        color: #991b1b;
    }

    @keyframes tIn {
        from {
            transform: translateX(110%);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    /*Responsif*/
    @media (max-width: 576px) {
        .saf-head-sub {
            display: block;
            margin: 2px 0 0;
        }
    }

    @media (max-width: 768px) {
        #saf-app .btn:hover:not(:disabled) {
            transform: none;
            box-shadow: none;
        }

        .saf-item-body {
            padding: 14px 14px 4px;
        }
    }

    @media (prefers-reduced-motion: reduce) {

        #saf-app *,
        .t-item {
            animation-duration: .001ms !important;
            transition-duration: .001ms !important;
        }
    }
</style>

<script>
    (() => {
        const B = <?= json_encode($boot, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
        const locked = B.status === 2 || !B.canEdit;
        const $ = id => document.getElementById(id);
        const el = (t, p = {}, ...k) => { const e = document.createElement(t); Object.assign(e, p); e.append(...k); return e; };

        // State: S[pilar] = [{judul, uraian, gaya, anak:[{judul, uraian}]}]
        // id = id baris di database (null = belum pernah disimpan)
        const S = {};
        for (let p = 1; p <= 5; p++) S[p] = (B.tree[p] || []).map(x => ({
            id: x.id ? +x.id : null, judul: x.judul || '', uraian: x.uraian || '', gaya: x.gaya || 'paragraf',
            anak: (x.anak || []).map(a => ({ id: a.id ? +a.id : null, judul: a.judul || '', uraian: a.uraian || '' }))
        }));

        // Toast
        const toast = (t, ok = true) => {
            const e = el('div', { className: 't-item ' + (ok ? 't-success' : 't-error') },
                el('i', { className: 'fas fa-lg fa-' + (ok ? 'check-circle' : 'exclamation-circle') }),
                el('div', { textContent: t || '' }));
            $('toast-wrap').append(e);
            setTimeout(() => {
                e.style.transition = 'opacity .4s'; e.style.opacity = 0;
                setTimeout(() => e.remove(), 400);
            }, 3500);
        };
        const msg = (t, ok) => toast(t, ok);

        const loading = b => {
            const ori = b.innerHTML;
            b.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; b.disabled = true;
            return () => { b.innerHTML = ori; b.disabled = false; };
        };

        const field = (o, key, tag, label, ph, rows) => {
            const e = el(tag, { className: 'form-control', placeholder: ph || '', value: o[key] });
            if (rows) e.rows = rows;
            e.maxLength = tag === 'input' ? 255 : 5000;
            e.oninput = () => { o[key] = e.value; };
            return el('div', { className: 'form-group' },
                el('label', { className: 'font-weight-bold', textContent: label }), e);
        };

        const btn = (html, cls, fn, title) => el('button', {
            type: 'button', className: 'btn btn-sm ' + cls, innerHTML: html, title: title || '', onclick: fn
        });

        const render = p => {
            const box = $('pilar-' + p); box.replaceChildren();
            const unit = p < 5 ? 'Program' : 'Poin';
            const cnt = $('pilar-count-' + p);
            if (cnt) cnt.textContent = S[p].length + ' ' + unit.toLowerCase();

            if (!S[p].length) {
                box.append(el('div', { className: 'empty-state-box saf-empty' },
                    el('i', { className: 'fas fa-folder-open fa-2x text-muted mb-2 d-block' }),
                    el('h6', { className: 'text-muted mb-1', textContent: 'Belum ada ' + unit.toLowerCase() }),
                    el('p', { className: 'text-muted small mb-0', textContent: locked ? 'Laporan terkunci, tidak dapat diubah.' : 'Klik tombol tambah di bawah untuk mengisi.' })));
                return;
            }

            S[p].forEach((it, i) => {
                const c = el('div', { className: 'saf-item' });
                const act = el('div', { className: 'kcard-actions' });
                const bSimpan = btn('<i class="fas fa-save"></i> Simpan', 'btn-success kcard-btn kcard-btn-txt',
                    () => simpanItem(p, it, bSimpan), 'Simpan ' + unit.toLowerCase() + ' ini');
                act.append(bSimpan);
                if (p < 5) act.append(btn('<i class="fas fa-plus"></i> Sub', 'btn-light kcard-btn kcard-btn-txt',
                    () => { it.anak.push({ id: null, judul: '', uraian: '' }); render(p); }, 'Tambah sub-program'));
                act.append(btn('<i class="fas fa-trash-alt"></i>', 'btn-danger kcard-btn',
                    () => hapusItem(p, S[p], i, it), 'Hapus ' + unit.toLowerCase()));

                c.append(el('div', { className: 'kcard-header' },
                    el('div', { className: 'kcard-nomor' },
                        el('span', { className: 'kcard-index', textContent: i + 1 }),
                        el('span', { className: 'kcard-nomor-text', textContent: unit + ' ' + (i + 1) })),
                    act));

                const body = el('div', { className: 'saf-item-body' });
                if (p < 5) {
                    body.append(field(it, 'judul', 'input', 'Judul program', 'Mis. Kesadaran Anti Fraud'));
                    const g = el('select', { className: 'form-control' },
                        el('option', { value: 'paragraf', textContent: 'Uraian berupa paragraf' }),
                        el('option', { value: 'poin', textContent: 'Uraian berupa poin (satu baris = satu poin)' }));
                    g.value = it.gaya; g.onchange = () => { it.gaya = g.value; };
                    body.append(el('div', { className: 'form-group' },
                        el('label', { className: 'font-weight-bold', textContent: 'Bentuk uraian' }), g));
                }
                body.append(field(it, 'uraian', 'textarea',
                    p < 5 ? 'Uraian' : 'Uraian rencana tindak lanjut',
                    p < 5 ? 'Boleh kosong jika memakai sub-uraian' : 'Tuliskan rencana tindak lanjut...', 5));

                it.anak.forEach((a, j) => {
                    const bSub = btn('<i class="fas fa-save"></i> Simpan', 'btn-success kcard-btn kcard-btn-txt',
                        () => simpanItem(p, it, bSub, a, j), 'Simpan sub-program ini');
                    body.append(el('div', { className: 'saf-sub' },
                        el('div', { className: 'd-flex justify-content-between align-items-center mb-2' },
                            el('span', { className: 'kcard-col-title', textContent: 'Sub-program ' + String.fromCharCode(97 + j) }),
                            el('div', { className: 'kcard-actions' }, bSub,
                                btn('<i class="fas fa-trash-alt"></i>', 'btn-outline-danger kcard-btn',
                                    () => hapusItem(p, it.anak, j, a), 'Hapus sub-program'))),
                        field(a, 'judul', 'input', 'Judul sub-program', 'Judul sub-program'),
                        field(a, 'uraian', 'textarea', 'Uraian sub-program', 'Uraian', 5)));
                });

                c.append(body);
                box.append(c);
            });
            if (locked) box.querySelectorAll('input,textarea,select,button').forEach(x => { x.disabled = true; });
        };

        for (let p = 1; p <= 5; p++) render(p);
        document.querySelectorAll('[data-add]').forEach(b => {
            b.disabled = locked;
            b.onclick = () => { const p = +b.dataset.add; S[p].push({ id: null, judul: '', uraian: '', gaya: 'paragraf', anak: [] }); render(p); };
        });

        // Penghitung karakter paragraf pembuka
        const rc = $('ringkasan'), rn = $('ringkasan-count');
        const updCount = () => {
            const l = rc.value.length;
            rn.textContent = l + ' / 5000';
            rn.classList.toggle('danger', l > 4750);
        };
        rc.addEventListener('input', updCount); updCount();

        const kirim = async (url, fd) => {
            fd.append(B.csrf.name, B.csrf.hash);
            try {
                const r = await fetch(url, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const j = await r.json(); if (j.csrf) B.csrf.hash = j.csrf; return j;
            } catch (e) { return { ok: false, msg: 'Gagal tersambung ke server.' }; }
        };

        const postItem = obj => {
            const fd = new FormData(); fd.append('periode_id', B.periode);
            Object.entries(obj).forEach(([k, v]) => fd.append(k, v == null ? '' : v));
            return kirim(B.url.item, fd);
        };

        const simpanItem = async (p, it, tombol, sub, j) => {
            const done = loading(tombol);
            let r = { ok: true };
            if (!sub || !it.id) {
                r = await postItem({
                    pilar: p, id: it.id || '', judul: it.judul, uraian: it.uraian, gaya: it.gaya,
                    urutan: S[p].indexOf(it) + 1, implisit: sub ? 1 : 0
                });
                if (r.ok) it.id = r.id;
            }
            if (r.ok && sub) {
                r = await postItem({
                    pilar: p, parent_id: it.id, id: sub.id || '', judul: sub.judul, uraian: sub.uraian, urutan: j + 1
                });
                if (r.ok) sub.id = r.id;
            }
            done(); msg(r.msg, r.ok);
            if (r.ok && r.reload) setTimeout(() => location.reload(), 700);
        };
        const hapusItem = async (p, arr, idx, obj) => {
            if (obj.id) {
                if (!confirm('Hapus data ini secara permanen dari database?')) return;
                const fd = new FormData(); fd.append('periode_id', B.periode); fd.append('id', obj.id);
                const r = await kirim(B.url.hapus, fd); msg(r.msg, r.ok);
                if (!r.ok) return;
                arr.splice(idx, 1); render(p);
                if (r.reload) setTimeout(() => location.reload(), 700);
                return;
            }
            arr.splice(idx, 1); render(p);
        };

        const bs = $('btnSimpan');
        bs.onclick = async () => {
            const done = loading(bs);
            const fd = new FormData(); fd.append('periode_id', B.periode);
            fd.append('data', JSON.stringify({
                ringkasan_umum: $('ringkasan').value, kota_ttd: $('kota').value, tanggal_laporan: $('tanggal').value,
                nama_pembuat: $('nama').value, jabatan_pembuat: $('jabatan').value, pilar: S
            }));
            if ($('ttd').files[0]) fd.append('ttd', $('ttd').files[0]);
            const j = await kirim(B.url.simpan, fd);
            msg(j.msg, j.ok);
            if (j.ok) setTimeout(() => location.reload(), 700); else done();
        };

        // Approval per level (data-level = dekom | direksi, data-aksi = setujui | batal)
        document.querySelectorAll('[data-aksi]').forEach(b => b.onclick = async () => {
            const lb = b.dataset.label || '';
            const tanya = b.dataset.aksi === 'setujui'
                ? 'Setujui laporan ini sebagai ' + lb + '?'
                : 'Batalkan approval ' + lb + '?';
            if (!confirm(tanya)) return;
                                                                                                    
            const done = loading(b);
            const fd = new FormData();
            fd.append('periode_id', B.periode);
            fd.append('aksi', b.dataset.aksi);
            fd.append('level', b.dataset.level);
            const j = await kirim(B.url.status, fd); msg(j.msg, j.ok);
            if (j.ok) setTimeout(() => location.reload(), 600); else done();
        });

        const bf = $('btnFraud');
        if (bf) bf.onclick = () => {
            const f = B.fraud, w = `Semester ${B.sem} Tahun ${B.tahun}`;
            if (f.st + f.inc === 0 && !f.st_null) {
                msg('Data Penerapan SAF semesteran belum diinput atau ditandai "Tidak Ada Data". Lengkapi dulu agar draf akurat.', false);
                return;
            }
            const t = (f.st + f.inc) === 0
                ? `Selama ${w} tidak terdapat indikasi maupun kejadian fraud, sehingga tidak dilakukan proses investigasi, pelaporan kepada regulator, maupun pemberian sanksi terkait fraud.`
                : `Selama ${w} tercatat ${f.st} data pada laporan Penerapan SAF semesteran dan ${f.inc} data pada laporan insidental. Rincian kejadian, penanganan, dan tindak lanjutnya mengacu pada laporan tersebut.`;
            S[3].push({ id: null, judul: '', uraian: t, gaya: 'paragraf', anak: [] }); render(3);
        };
    })();
</script>

<style>
    /* Tampilan ponsel: badge approval sejajar penuh */
    @media (max-width: 576px) {
        .saf-badge-wrap { flex-direction: column; align-items: stretch; }
        .saf-badge-wrap .saf-badge-left, .saf-badge-wrap .saf-badge-right { margin: 0; }
        #saf-app .approval-status-badge { min-width: 0; width: 100%; }
    }
</style>

<?= view('templates/komentar', ['komentarPage' => 'Laporanpdfsaf']) ?>
