<?php
$peringkatLabels = ['1'=>'Sangat Rendah','2'=>'Rendah','3'=>'Sedang','4'=>'Tinggi','5'=>'Sangat Tinggi'];
$badgeMap = ['1'=>'badge-success','2'=>'badge-info','3'=>'badge-warning','4'=>'badge-orange','5'=>'badge-danger'];

function getBadgeClass($nilai) {
    global $badgeMap;
    return $badgeMap[$nilai] ?? 'badge-secondary';
}

$p = $tkspermodalan ?? [];
$rataRata = $p['rataratapermodalan']   ?? 0;
$peringkatPermodalan = $p['peringkatpermodalan']  ?? 0;
$peringkatLabel = $peringkatLabels[$peringkatPermodalan] ?? 'Belum Ada Data';
$rasioKPMM = $p['rasiokpmm']    ?? 0;
$peringkatKPMM = $p['peringkatkpmm']  ?? 0;
$penilaianKPMM = $p['penilaiankpmm']  ?? 'Belum dinilai';
$rasioMIAPB = $p['rasiomiapb']   ?? 0;
$peringkatMIAPB = $p['peringkatmiapb'] ?? 0;
$penilaianMIAPB = $p['penilaianmiapb'] ?? 'Belum dinilai';
$badgeClass = getBadgeClass($peringkatPermodalan);
$modalIntiDefault = $showtks['modal_inti'] ?? 0;
$modalInti = $p['modalinti'] ?? $modalIntiDefault;
$modalIntiApb = $p['modalintiapb'] ?? $modalInti;
$rataRataBefore = $p['rataratapermodalanbefore']  ?? 0;
$peringkatPermodalanBefore = $p['peringkatpermodalanbefore'] ?? 0;
$peringkatLabelBefore = $peringkatLabels[$peringkatPermodalanBefore] ?? 'Belum Ada Data';
$rasioKPMMBefore = $p['rasiokpmmbefore'] ?? 0;
$peringkatKPMMBefore = $p['peringkatkpmmbefore'] ?? 0;
$penilaianKPMMBefore = $p['penilaiankpmmbefore'] ?? 'Belum dinilai';
$rasioMIAPBBefore = $p['rasiomiapbbefore'] ?? 0;
$peringkatMIAPBBefore = $p['peringkatmiapbbefore'] ?? 0;
$penilaianMIAPBBefore = $p['penilaianmiapbbefore'] ?? 'Belum dinilai';
$badgeClassBefore = getBadgeClass($peringkatPermodalanBefore);
$modalIntiBefore = $p['modalintibefore']    ?? 0;
$modalIntiApbBefore = $p['modalintiapbbefore'] ?? $modalIntiBefore;

$rasioKPMMSet = isset($p['rasiokpmm']) && $p['rasiokpmm'] !== null;
$rasioMIAPBSet = isset($p['rasiomiapb']) && $p['rasiomiapb'] !== null;
$rasioKPMMBeforeSet = isset($p['rasiokpmmbefore']) && $p['rasiokpmmbefore'] !== null;
$rasioMIAPBBeforeSet = isset($p['rasiomiapbbefore']) && $p['rasiomiapbbefore'] !== null;

function fmtCurrency($v) {
    if (!$v && $v !== 0) return '';
    $v = (string)(int)$v;
    return number_format((int)$v, 0, ',', '.');
}
function fmtRasio($v, $isSet) { return $isSet ? number_format($v,2).'%' : '-'; }
?>

<div class="alert alert-info my-2">
    <?php if (isset($bprData,$periodeDetail)): ?>
        <strong><?= esc($bprData['namabpr'] ?? 'Nama BPR') ?></strong> - Periode Pelaporan
        Semester <?= esc($periodeDetail['semester']) ?> Tahun <?= esc($periodeDetail['tahun']) ?>
    <?php endif; ?>
</div>

<div class="container-fluid py-4">
    <div class="main-content">
        <div class="d-flex justify-content-start mb-3">
            <a href="<?= base_url('Showtks') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h4 class="mb-0 fw-bold text-primary">
                            <i class="fas fa-chart-line me-2 text-primary"></i>
                            Faktor 4. Penilaian Permodalan
                        </h4>
                        <p class="text-muted mb-0 mt-2">
                            <small>Penilaian permodalan meliputi KPMM dan MIAPB untuk periode saat ini dan periode sebelumnya</small>
                        </p>
                    </div>
                    <div class="col-md-4 d-flex justify-content-end align-items-center gap-4">
                        <!-- Approval Badge -->
                        <span id="approval-badge-main"
                            style="display:inline-flex;flex-direction:column;justify-content:center;
                                padding:10px 18px;font-size:13px;line-height:1.4;border-radius:10px;
                                background:<?= $isApproved ? '#16a34a' : '#6b7280' ?>;color:#fff;">
                            <?php if ($isApproved): ?>
                                <span style="font-weight:600;display:flex;align-items:center;gap:6px;">
                                    <i class="fas fa-check-circle"></i> Disetujui oleh <strong>Direktur Utama</strong>
                                </span>
                                <span style="font-size:11.5px;opacity:.85;margin-top:2px;">
                                    <?= $approvedAt ? date('d/m/Y H:i:s', strtotime($approvedAt)) : '-' ?>
                                </span>
                            <?php else: ?>
                                <span style="font-weight:600;display:flex;align-items:center;gap:6px;">
                                    <i class="fas fa-clock"></i> Belum Disetujui
                                </span>
                                <span style="font-size:11.5px;opacity:.85;margin-top:2px;">Oleh Direktur Utama</span>
                            <?php endif; ?>
                        </span>

                        <!-- Komentar Button -->
                        <div class="position-relative ml-2">
                            <button type="button" class="komentar-button"
                                    style="width:46px;height:46px;border-radius:10px;border:none;
                                        background:#1e3a5f;color:#fff;font-size:16px;
                                        display:flex;align-items:center;justify-content:center;cursor:pointer;"
                                    aria-label="Komentar">
                                <i class="fas fa-comment"></i>
                                <span id="notification-badge" class="badge badge-danger notification-badge"
                                    style="display:<?= ($unreadCount ?? 0) > 0 ? 'inline-block' : 'none' ?>;">
                                    <?= $unreadCount ?? 0 ?>
                                </span>
                            </button>
                        </div>

                        <!-- Sync Button (hanya PE & Admin) -->
                        <?php if (($userInGroupPE ?? false) || ($userInGroupAdmin ?? false)): ?>
                        <div class="position-relative ml-2">
                            <button id="btnSyncPrevious"
                                    style="width:46px;height:46px;border-radius:10px;border:none;
                                        background:#1e3a5f;color:#fff;font-size:16px;
                                        display:flex;align-items:center;justify-content:center;cursor:pointer;"
                                    disabled>
                                <i class="fas fa-sync-alt"></i>
                                <span id="syncSpinner" class="spinner-border spinner-border-sm ms-1" style="display:none;position:absolute;"></span>
                            </button>
                            <span id="sync-tooltip" class="sync-tooltip-tks">Memeriksa…</span>
                        </div>

                        <!-- Tarik Data mis_tks Button -->
                        <div class="position-relative ml-2">
                            <button id="btnTarikMisTks"
                                    style="width:46px;height:46px;border-radius:10px;border:none;
                                        background:#7c3aed;color:#fff;font-size:16px;
                                        display:flex;align-items:center;justify-content:center;cursor:pointer;">
                                <i class="fas fa-database"></i>
                            </button>
                            <span id="mistks-tooltip" class="sync-tooltip-tks">Tarik Data MIS TKS</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ringkasan Periode Saat Ini -->
        <?php if ($peringkatPermodalan > 0): ?>
        <div class="card shadow-sm">
            <div class="card-header bg-gradient-primary">
                <h6 class="mb-0 text-white fw-bold">
                    <i class="fas fa-award me-2"></i>Ringkasan Penilaian - Periode Saat Ini
                </h6>
            </div>
            <div class="card-body p-3 p-md-4">
                <div class="row g-3 g-md-4 mb-4 pb-4" style="border-bottom:2px solid #e5e7eb;">
                    <div class="col-12 col-md-4">
                        <div class="info-box" style="background:linear-gradient(135deg,#eff6ff 0%,#dbeafe 100%);border:2px solid #3b82f6;">
                            <div class="info-label"><i class="fas fa-calculator me-1"></i> Rata-rata Peringkat</div>
                            <div class="info-value text-primary" id="displayRataRata"><?= number_format($rataRata,2) ?></div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="info-box" style="background:linear-gradient(135deg,#fef3c7 0%,#fde68a 100%);border:2px solid #f59e0b;">
                            <div class="info-label"><i class="fas fa-medal me-1"></i> Peringkat Akhir</div>
                            <div class="info-value text-warning" id="displayPeringkat"><?= $peringkatPermodalan ?></div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="info-box" style="background:white;">
                            <div class="info-label"><i class="fas fa-star me-1"></i> Kategori Penilaian</div>
                            <div class="mt-2">
                                <span class="badge <?= $badgeClass ?> badge-xl" id="displayKategori"><?= $peringkatLabel ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold mb-3 text-secondary"><i class="fas fa-list-alt me-2"></i>Detail Komponen Penilaian</h6>
                <div class="row g-3">
                    <!-- KPMM -->
                    <div class="col-12 col-md-6">
                        <div class="component-box">
                            <div class="component-header bg-success"><i class="fas fa-percentage me-2"></i>KPMM</div>
                            <div class="component-body">
                                <div class="component-item">
                                    <span class="component-label">Rasio:</span>
                                    <span class="component-value text-success fw-bold" id="summaryRasioKPMM"><?= fmtRasio($rasioKPMM, $rasioKPMMSet) ?></span>
                                </div>
                                <div class="component-item">
                                    <span class="component-label">Peringkat:</span>
                                    <span class="component-value">
                                        <span class="badge badge-sm <?= getBadgeClass($peringkatKPMM) ?>" id="summaryPeringkatKPMM">
                                            <?= $peringkatKPMM > 0 ? $peringkatKPMM : '-' ?>
                                        </span>
                                    </span>
                                </div>
                                <div class="component-item">
                                    <span class="component-label">Penilaian:</span>
                                    <span class="component-value fw-bold" id="summaryPenilaianKPMM"><?= $penilaianKPMM ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- MIAPB -->
                    <div class="col-12 col-md-6">
                        <div class="component-box">
                            <div class="component-header bg-warning"><i class="fas fa-chart-bar me-2"></i>MIAPB</div>
                            <div class="component-body">
                                <div class="component-item">
                                    <span class="component-label">Rasio:</span>
                                    <span class="component-value text-warning fw-bold" id="summaryRasioMIAPB"><?= fmtRasio($rasioMIAPB, $rasioMIAPBSet) ?></span>
                                </div>
                                <div class="component-item">
                                    <span class="component-label">Peringkat:</span>
                                    <span class="component-value">
                                        <span class="badge badge-sm <?= getBadgeClass($peringkatMIAPB) ?>" id="summaryPeringkatMIAPB">
                                            <?= $peringkatMIAPB > 0 ? $peringkatMIAPB : '-' ?>
                                        </span>
                                    </span>
                                </div>
                                <div class="component-item">
                                    <span class="component-label">Penilaian:</span>
                                    <span class="component-value fw-bold" id="summaryPenilaianMIAPB"><?= $penilaianMIAPB ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Ringkasan Periode Sebelumnya -->
        <?php if ($peringkatPermodalanBefore > 0): ?>
        <div class="card shadow-sm">
            <div class="card-header" style="background:linear-gradient(135deg,#6366f1 0%,#4f46e5 100%);">
                <h6 class="mb-0 text-white fw-bold">
                    <i class="fas fa-history me-2"></i>Ringkasan Penilaian - Periode Sebelumnya
                </h6>
            </div>
            <div class="card-body p-3 p-md-4">
                <div class="row g-3 g-md-4 mb-4 pb-4" style="border-bottom:2px solid #e5e7eb;">
                    <div class="col-12 col-md-4">
                        <div class="info-box" style="background:linear-gradient(135deg,#eff6ff 0%,#dbeafe 100%);border:2px solid #6366f1;">
                            <div class="info-label"><i class="fas fa-calculator me-1"></i> Rata-rata Peringkat</div>
                            <div class="info-value text-primary" id="displayRataRataBefore"><?= number_format($rataRataBefore,2) ?></div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="info-box" style="background:linear-gradient(135deg,#fef3c7 0%,#fde68a 100%);border:2px solid #f59e0b;">
                            <div class="info-label"><i class="fas fa-medal me-1"></i> Peringkat Akhir</div>
                            <div class="info-value text-warning" id="displayPeringkatBefore"><?= $peringkatPermodalanBefore ?></div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="info-box" style="background:white;">
                            <div class="info-label"><i class="fas fa-star me-1"></i> Kategori Penilaian</div>
                            <div class="mt-2">
                                <span class="badge <?= $badgeClassBefore ?> badge-xl" id="displayKategoriBefore"><?= $peringkatLabelBefore ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold mb-3 text-secondary"><i class="fas fa-list-alt me-2"></i>Detail Komponen Penilaian</h6>
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="component-box">
                            <div class="component-header" style="background:#6366f1;"><i class="fas fa-percentage me-2"></i>KPMM</div>
                            <div class="component-body">
                                <div class="component-item">
                                    <span class="component-label">Rasio:</span>
                                    <span class="component-value fw-bold" style="color:#6366f1;" id="summaryRasioKPMMBefore"><?= fmtRasio($rasioKPMMBefore, $rasioKPMMBeforeSet) ?></span>
                                </div>
                                <div class="component-item">
                                    <span class="component-label">Peringkat:</span>
                                    <span class="component-value">
                                        <span class="badge badge-sm <?= getBadgeClass($peringkatKPMMBefore) ?>" id="summaryPeringkatKPMMBefore">
                                            <?= $peringkatKPMMBefore > 0 ? $peringkatKPMMBefore : '-' ?>
                                        </span>
                                    </span>
                                </div>
                                <div class="component-item">
                                    <span class="component-label">Penilaian:</span>
                                    <span class="component-value fw-bold" id="summaryPenilaianKPMMBefore"><?= $penilaianKPMMBefore ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="component-box">
                            <div class="component-header" style="background:#6366f1;"><i class="fas fa-chart-bar me-2"></i>MIAPB</div>
                            <div class="component-body">
                                <div class="component-item">
                                    <span class="component-label">Rasio:</span>
                                    <span class="component-value fw-bold" style="color:#6366f1;" id="summaryRasioMIAPBBefore"><?= fmtRasio($rasioMIAPBBefore, $rasioMIAPBBeforeSet) ?></span>
                                </div>
                                <div class="component-item">
                                    <span class="component-label">Peringkat:</span>
                                    <span class="component-value">
                                        <span class="badge badge-sm <?= getBadgeClass($peringkatMIAPBBefore) ?>" id="summaryPeringkatMIAPBBefore">
                                            <?= $peringkatMIAPBBefore > 0 ? $peringkatMIAPBBefore : '-' ?>
                                        </span>
                                    </span>
                                </div>
                                <div class="component-item">
                                    <span class="component-label">Penilaian:</span>
                                    <span class="component-value fw-bold" id="summaryPenilaianMIAPBBefore"><?= $penilaianMIAPBBefore ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- 1. KPMM -->
        <div class="card shadow-sm">
            <div class="card-header bg-gradient-success">
                <h6 class="mb-0 text-white fw-bold">
                    <i class="fas fa-percentage me-2"></i>1. Rasio Kecukupan Pemenuhan Modal Minimum (KPMM)
                </h6>
            </div>
            <div class="card-body p-3 p-md-4">
                <div class="formula-box">
                    <h6 class="fw-bold mb-3">Rumus KPMM:</h6>
                    <div class="formula-display">
                        KPMM = <span class="formula-fraction">
                            <span class="formula-numerator">Modal Inti + Modal Pelengkap</span>
                            <span class="formula-denominator">ATMR</span>
                        </span> × 100%
                    </div>
                    <p class="text-muted mt-3 mb-0">
                        <small>Modal inti, pelengkap dan ATMR mengacu pada Peraturan Otoritas Jasa Keuangan mengenai kewajiban penyediaan modal minimum dan pemenuhan modal inti minimum BPR</small>
                    </p>
                </div>

                <div class="row g-4">
                    <div class="col-12 col-lg-6">
                        <div class="period-box current-period">
                            <h6 class="period-title"><i class="fas fa-calendar-day me-2"></i>Periode Saat Ini</h6>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-bold">Modal Inti (Rp)</label>
                                    <input type="text" class="form-control auto-save currency-input"
                                           data-field="modalinti"
                                           value="<?= esc(fmtCurrency($modalInti)) ?>"
                                           placeholder="Otomatis dari TKS (dapat diedit)">
                                    <div class="saving-indicator"></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">Modal Pelengkap (Rp)</label>
                                    <input type="text" class="form-control auto-save currency-input"
                                           data-field="modalpelengkap"
                                           value="<?= esc(fmtCurrency($p['modalpelengkap'] ?? '')) ?>"
                                           placeholder="Masukkan modal pelengkap">
                                    <div class="saving-indicator"></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">ATMR (Rp)</label>
                                    <input type="text" class="form-control auto-save currency-input"
                                           data-field="atmr"
                                           value="<?= esc(fmtCurrency($p['atmr'] ?? '')) ?>"
                                           placeholder="Masukkan ATMR">
                                    <div class="saving-indicator"></div>
                                </div>
                            </div>
                            <div class="result-box mt-3" id="resultKPMM">
                                <div class="result-item mb-2">
                                    <label><i class="fas fa-chart-line me-1"></i> Rasio KPMM</label>
                                    <div class="result-value" id="displayRasioKPMM">
                                        <?php if ($rasioKPMMSet): ?>
                                            <span class="text-success"><?= number_format($rasioKPMM,2) ?>%</span>
                                        <?php else: ?><span class="text-muted">Belum dihitung</span><?php endif; ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12 text-center">
                                        <div class="result-item">
                                            <label><i class="fas fa-award me-1"></i> Peringkat</label>
                                            <div class="result-value" id="displayPeringkatKPMM">
                                                <?= $peringkatKPMM > 0 ? '<span class="text-primary">'.$peringkatKPMM.'</span>' : '<span class="text-muted">-</span>' ?>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- <div class="col-6">
                                        <div class="result-item">
                                            <label><i class="fas fa-star me-1"></i> Penilaian</label>
                                            <div class="mt-1">
                                                <span class="badge <?= getBadgeClass($peringkatKPMM) ?> badge-sm" id="displayPenilaianKPMM"><?= $penilaianKPMM ?></span>
                                            </div>
                                        </div>
                                    </div> -->
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Previous -->
                    <div class="col-12 col-lg-6">
                        <div class="period-box previous-period">
                            <h6 class="period-title"><i class="fas fa-history me-2"></i>Periode Sebelumnya</h6>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-bold">Modal Inti (Rp)</label>
                                    <input type="text" class="form-control auto-save currency-input"
                                           data-field="modalintibefore"
                                           value="<?= esc(fmtCurrency($p['modalintibefore'] ?? '')) ?>"
                                           placeholder="Masukkan modal inti">
                                    <div class="saving-indicator"></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">Modal Pelengkap (Rp)</label>
                                    <input type="text" class="form-control auto-save currency-input"
                                           data-field="modalpelengkapbefore"
                                           value="<?= esc(fmtCurrency($p['modalpelengkapbefore'] ?? '')) ?>"
                                           placeholder="Masukkan modal pelengkap">
                                    <div class="saving-indicator"></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">ATMR (Rp)</label>
                                    <input type="text" class="form-control auto-save currency-input"
                                           data-field="atmrbefore"
                                           value="<?= esc(fmtCurrency($p['atmrbefore'] ?? '')) ?>"
                                           placeholder="Masukkan ATMR">
                                    <div class="saving-indicator"></div>
                                </div>
                            </div>
                            <div class="result-box mt-3" id="resultKPMMBefore"
                                 style="background:linear-gradient(135deg,#eef2ff 0%,#e0e7ff 100%);border-color:#6366f1;">
                                <div class="result-item mb-2">
                                    <label><i class="fas fa-chart-line me-1"></i> Rasio KPMM</label>
                                    <div class="result-value" id="displayRasioKPMMBefore">
                                        <?php if ($rasioKPMMBeforeSet): ?>
                                            <span style="color:#6366f1;"><?= number_format($rasioKPMMBefore,2) ?>%</span>
                                        <?php else: ?><span class="text-muted">Belum dihitung</span><?php endif; ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12 text-center">
                                        <div class="result-item">
                                            <label><i class="fas fa-award me-1"></i> Peringkat</label>
                                            <div class="result-value" id="displayPeringkatKPMMBefore">
                                                <?= $peringkatKPMMBefore > 0 ? '<span class="text-primary">'.$peringkatKPMMBefore.'</span>' : '<span class="text-muted">-</span>' ?>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- <div class="col-6">
                                        <div class="result-item">
                                            <label><i class="fas fa-star me-1"></i> Penilaian</label>
                                            <div class="mt-1">
                                                <span class="badge <?= getBadgeClass($peringkatKPMMBefore) ?> badge-sm" id="displayPenilaianKPMMBefore"><?= $penilaianKPMMBefore ?></span>
                                            </div>
                                        </div>
                                    </div> -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="peringkat-info mt-4">
                    <h6 class="fw-bold mb-3 text-center">Kriteria Peringkat KPMM:</h6>
                    <div class="row g-2 justify-content-center text-center">
                        <div class="col-6 col-md-4 col-lg-2"><span class="badge badge-success w-100 py-3"><div class="fw-bold">Peringkat 1</div><div class="small-bold mt-2">KPMM ≥ 15%</div></span></div>
                        <div class="col-6 col-md-4 col-lg-2"><span class="badge badge-info w-100 py-3"><div class="fw-bold">Peringkat 2</div><div class="small-bold mt-2">15% > KPMM ≥ 13%</div></span></div>
                        <div class="col-6 col-md-4 col-lg-2"><span class="badge badge-warning w-100 py-3"><div class="fw-bold">Peringkat 3</div><div class="small-bold mt-2">13% > KPMM ≥ 12%</div></span></div>
                        <div class="col-6 col-md-4 col-lg-2"><span class="badge badge-orange w-100 py-3"><div class="fw-bold">Peringkat 4</div><div class="small-bold mt-2">12% > KPMM ≥ 8%</div></span></div>
                        <div class="col-6 col-md-4 col-lg-2"><span class="badge badge-danger w-100 py-3"><div class="fw-bold">Peringkat 5</div><div class="small-bold mt-2">KPMM &lt; 8%</div></span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. MIAPB -->
        <div class="card shadow-sm">
            <div class="card-header bg-gradient-warning">
                <h6 class="mb-0 text-white fw-bold">
                    <i class="fas fa-percentage me-2"></i>2. Rasio Modal Inti terhadap Aset Produktif Bermasalah Netto (MIAPB)
                </h6>
            </div>
            <div class="card-body p-3 p-md-4">
                <div class="formula-box-red">
                    <h6 class="fw-bold mb-3">Rumus MIAPB:</h6>
                    <div class="formula-display">
                        MIAPB = <span class="formula-fraction">
                            <span class="formula-numerator">Modal Inti</span>
                            <span class="formula-denominator">Aset Produktif Bermasalah</span>
                        </span> × 100%
                    </div>
                    <p class="text-muted mt-3 mb-0">
                        <small>Modal inti mengacu pada POJK mengenai KPMM dan pemenuhan modal inti minimum BPR. Aset Produktif Bermasalah adalah aset produktif yang memiliki kualitas NPL setelah dikurangi dengan penyisihan penghapusan aset produktif</small>
                    </p>
                </div>

                <div class="row g-4">
                    <!-- Current -->
                    <div class="col-12 col-lg-6">
                        <div class="period-box current-period">
                            <h6 class="period-title"><i class="fas fa-calendar-day me-2"></i>Periode Saat Ini</h6>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-bold">Modal Inti (Rp)</label>
                                    <input type="text" class="form-control auto-save currency-input"
                                           data-field="modalintiapb"
                                           value="<?= esc(fmtCurrency($modalIntiApb)) ?>"
                                           placeholder="Otomatis dari Modal Inti KPMM (dapat diedit)">
                                    <div class="saving-indicator"></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">Total Aset Produktif Bermasalah (Rp)</label>
                                    <input type="text" class="form-control auto-save currency-input"
                                           data-field="totalasetbermasalah"
                                           value="<?= esc(fmtCurrency($p['totalasetbermasalah'] ?? '')) ?>"
                                           placeholder="Masukkan total aset produktif bermasalah">
                                    <div class="saving-indicator"></div>
                                </div>
                            </div>
                            <div class="result-box mt-3" id="resultMIAPB">
                                <div class="result-item mb-2">
                                    <label><i class="fas fa-chart-bar me-1"></i> Rasio MIAPB</label>
                                    <div class="result-value" id="displayRasioMIAPB">
                                        <?php if ($rasioMIAPBSet): ?>
                                            <span class="text-warning"><?= number_format($rasioMIAPB,2) ?>%</span>
                                        <?php else: ?><span class="text-muted">Belum dihitung</span><?php endif; ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12 text-center">
                                        <div class="result-item">
                                            <label><i class="fas fa-trophy me-1"></i> Peringkat</label>
                                            <div class="result-value" id="displayPeringkatMIAPB">
                                                <?= $peringkatMIAPB > 0 ? '<span class="text-primary">'.$peringkatMIAPB.'</span>' : '<span class="text-muted">-</span>' ?>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- <div class="col-6">
                                        <div class="result-item">
                                            <label><i class="fas fa-star me-1"></i> Penilaian</label>
                                            <div class="mt-1">
                                                <span class="badge <?= $peringkatMIAPB > 0 ? getBadgeClass($peringkatMIAPB) : 'badge-secondary' ?> badge-sm" id="displayPenilaianMIAPB">
                                                    <?= $penilaianMIAPB ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div> -->
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Previous -->
                    <div class="col-12 col-lg-6">
                        <div class="period-box previous-period">
                            <h6 class="period-title"><i class="fas fa-history me-2"></i>Periode Sebelumnya</h6>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-bold">Modal Inti (Rp)</label>
                                    <input type="text" class="form-control auto-save currency-input"
                                           data-field="modalintiapbbefore"
                                           value="<?= esc(fmtCurrency($modalIntiApbBefore)) ?>"
                                           placeholder="Otomatis dari Modal Inti KPMM (dapat diedit)">
                                    <div class="saving-indicator"></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">Total Aset Produktif Bermasalah (Rp)</label>
                                    <input type="text" class="form-control auto-save currency-input"
                                           data-field="totalasetbermasalahbefore"
                                           value="<?= esc(fmtCurrency($p['totalasetbermasalahbefore'] ?? '')) ?>"
                                           placeholder="Masukkan total aset produktif bermasalah">
                                    <div class="saving-indicator"></div>
                                </div>
                            </div>
                            <div class="result-box mt-3" id="resultMIAPBBefore"
                                 style="background:linear-gradient(135deg,#eef2ff 0%,#e0e7ff 100%);border-color:#6366f1;">
                                <div class="result-item mb-2">
                                    <label><i class="fas fa-chart-bar me-1"></i> Rasio MIAPB</label>
                                    <div class="result-value" id="displayRasioMIAPBBefore">
                                        <?php if ($rasioMIAPBBeforeSet): ?>
                                            <span style="color:#6366f1;"><?= number_format($rasioMIAPBBefore,2) ?>%</span>
                                        <?php else: ?><span class="text-muted">Belum dihitung</span><?php endif; ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12 text-center">
                                        <div class="result-item">
                                            <label><i class="fas fa-trophy me-1"></i> Peringkat</label>
                                            <div class="result-value" id="displayPeringkatMIAPBBefore">
                                                <?= $peringkatMIAPBBefore > 0 ? '<span class="text-primary">'.$peringkatMIAPBBefore.'</span>' : '<span class="text-muted">-</span>' ?>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- <div class="col-6">
                                        <div class="result-item">
                                            <label><i class="fas fa-star me-1"></i> Penilaian</label>
                                            <div class="mt-1">
                                                <span class="badge <?= getBadgeClass($peringkatMIAPBBefore) ?> badge-sm" id="displayPenilaianMIAPBBefore"><?= $penilaianMIAPBBefore ?></span>
                                            </div>
                                        </div>
                                    </div> -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="peringkat-info mt-4">
                    <h6 class="fw-bold mb-3 text-center">Kriteria Peringkat MIAPB:</h6>
                    <div class="row g-2 justify-content-center text-center">
                        <div class="col-6 col-md-4 col-lg-2"><span class="badge badge-success w-100 py-3"><div class="fw-bold">Peringkat 1</div><div class="small-bold mt-2">MIAPB ≥ 200%</div></span></div>
                        <div class="col-6 col-md-4 col-lg-2"><span class="badge badge-info w-100 py-3"><div class="fw-bold">Peringkat 2</div><div class="small-bold mt-2">200% > MIAPB ≥ 180%</div></span></div>
                        <div class="col-6 col-md-4 col-lg-2"><span class="badge badge-warning w-100 py-3"><div class="fw-bold">Peringkat 3</div><div class="small-bold mt-2">180% > MIAPB ≥ 150%</div></span></div>
                        <div class="col-6 col-md-4 col-lg-2"><span class="badge badge-orange w-100 py-3"><div class="fw-bold">Peringkat 4</div><div class="small-bold mt-2">120% > MIAPB ≥ 150%</div></span></div>
                        <div class="col-6 col-md-4 col-lg-2"><span class="badge badge-danger w-100 py-3"><div class="fw-bold">Peringkat 5</div><div class="small-bold mt-2">MIAPB &lt; 120%</div></span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Deskripsi -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-bold"><i class="fas fa-file-alt text-primary"></i> Deskripsi Peringkat</h6>
            </div>
            <div class="card-body p-3 p-md-4">
                <textarea class="form-control auto-save char-limited" data-field="deskripsipermodalan" rows="5"
                        maxlength="2000"
                        placeholder="Masukkan deskripsi peringkat permodalan..."><?= esc($p['deskripsipermodalan'] ?? '') ?></textarea>
                <div class="d-flex justify-content-between align-items-center mt-1">
                    <div class="saving-indicator"></div>
                    <small class="char-counter text-muted">0 / 2000</small>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-bold"><i class="fas fa-chart-line text-primary"></i> Analisa / Penjelasan</h6>
            </div>
            <div class="card-body p-3 p-md-4">
                <textarea class="form-control auto-save char-limited" data-field="analisispermodalan" rows="6"
                        maxlength="2000"
                        placeholder="Masukkan analisa komprehensif mengenai permodalan BPR..."><?= esc($p['analisispermodalan'] ?? '') ?></textarea>
                <div class="d-flex justify-content-between align-items-center mt-1">
                    <div class="saving-indicator"></div>
                    <small class="char-counter text-muted">0 / 2000</small>
                </div>
            </div>
        </div>

        <!-- Approval -->
        <?php if (($userInGroupAdmin??false) || ($userInGroupDireksi??false)): ?>
        <div class="d-flex justify-content-center mt-4 mb-4">
            <div class="col-md-4 mx-auto">
                <div class="card shadow-sm approvaldir-card">
                    <div class="card-body approvaldir-card-body">
                        <div class="approval-badge-container">
                            <span class="badge approval-badge"><i class="fas fa-user-tie"></i> Approval Direktur Utama</span>
                        </div>
                        <div class="approval-buttons-container">
                            <a href="<?= base_url('Tkspermodalan/approve') ?>"
                               class="btn approval-btn approval-btn-approve <?= !$canApprove || $isApproved ? 'disabled-btn' : '' ?>"
                               onclick="return <?= $canApprove && !$isApproved ? "confirm('Apakah Anda yakin ingin menyetujui data ini?')" : 'false' ?>;">
                                <i class="fas fa-check-circle"></i> Approve
                            </a>
                            <a href="<?= base_url('Tkspermodalan/unapprove') ?>"
                               class="btn approval-btn approval-btn-reject <?= !$isApproved ? 'disabled-btn' : '' ?>"
                               onclick="return <?= $isApproved ? "confirm('Batalkan persetujuan?')" : 'false' ?>;">
                                <i class="fas fa-times-circle"></i> Tolak
                            </a>
                        </div>
                        <?php if (!$canApprove): ?>
                        <div class="text-center mt-2">
                            <small class="text-muted"><i class="fas fa-info-circle"></i> Lengkapi data terlebih dahulu sebelum approval</small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Komentar Modal -->
        <div class="modal fade" id="modalKomentar" tabindex="-1" aria-labelledby="modalKomentarLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalKomentarLabel">
                            <i class="fas fa-comments"></i> Komentar Direksi dan Dewan Komisaris
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="fw-bold">Riwayat Komentar:</label>
                            <div id="komentarList"
                                 style="max-height:300px;overflow-y:auto;border:1px solid #dee2e6;border-radius:8px;padding:15px;background:#f8f9fa;">
                                <div class="text-center text-muted"><i class="fas fa-spinner fa-spin"></i> Memuat komentar...</div>
                            </div>
                        </div>
                        <?php if (($userInGroupAdmin??false)||($userInGroupDekom??false)||($userInGroupDireksi??false)||($userInGroupPE??false)): ?>
                        <form id="formKomentar">
                            <?= csrf_field() ?>
                            <input type="hidden" name="fullname" value="<?= esc($fullname ?? '') ?>">
                            <div class="mb-3">
                                <label for="komentar" class="fw-bold">Tambahkan Komentar Baru:</label>
                                <textarea class="form-control" name="komentar" id="komentar" rows="4"
                                          placeholder="Tulis komentar Anda di sini..."></textarea>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Kirim Komentar
                                </button>
                            </div>
                        </form>
                        <?php else: ?>
                        <div class="alert alert-info"><i class="fas fa-info-circle"></i> Anda tidak memiliki akses untuk menambahkan komentar.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTarikMisTks" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;border:none;">
            <div class="modal-header" style="background:linear-gradient(135deg,#7c3aed 0%,#6d28d9 100%);color:white;border:none;">
                <h5 class="modal-title"><i class="fas fa-database"></i> Tarik Data KPMM & MIAPB dari MIS TKS</h5>                
            </div>
            <div class="modal-body" style="padding:22px 24px;max-height:70vh;overflow-y:auto;">
                <div class="sync-tks-info-box" style="background:#f5f3ff;border-color:#ddd6fe;">
                    <div class="sync-tks-periode">
                        <span class="sync-tks-label">Periode Saat Ini</span>
                        <span class="sync-tks-val" id="mistks-current-label" style="color:#6d28d9;">—</span>
                    </div>
                    <div style="font-size:20px;color:#7c3aed;"><i class="fas fa-arrow-left"></i></div>
                    <div class="sync-tks-periode">
                        <span class="sync-tks-label">Sumber (mis_tks)</span>
                        <span class="sync-tks-val" id="mistks-prev-label" style="color:#6d28d9;">—</span>
                    </div>
                </div>

                <div id="mistks-preview-area" style="margin-top:14px;">
                    <div class="mistks-group-header" style="background:#ecfdf5;color:#065f46;">
                        <i class="fas fa-percentage me-2"></i> KPMM (Modal)
                    </div>
                    <div id="mistks-preview-kpmm" class="mistks-preview-box"></div>

                    <div class="mistks-group-header" style="background:#fffbeb;color:#92400e;">
                        <i class="fas fa-chart-bar me-2"></i> MIAPB
                    </div>
                    <div id="mistks-preview-miapb" class="mistks-preview-box"></div>
                </div>

                <div id="mistks-result-area" style="display:none;margin-top:14px;"></div>

                <div class="alert alert-warning mb-0 mt-3" id="mistks-warning-area" style="font-size:12.5px;border-radius:10px;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Perhatian:</strong> Field yang ditemukan di mis_tks akan <strong>menimpa</strong> nilai pada form KPMM dan MIAPB (periode saat ini & sebelumnya), lalu rasio dihitung ulang otomatis. Field yang tidak ditemukan tidak akan diubah.
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #e2e8f0;padding:12px 24px;">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" id="btn-batal-mistks">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="button" id="btn-konfirmasi-mistks" class="btn"
                        style="background:linear-gradient(135deg,#7c3aed,#6d28d9);color:white;font-weight:700;border:none;">
                    <i class="fas fa-database"></i> Tarik Sekarang
                </button>
            </div>
        </div>
    </div>
</div>

<script>window.jQuery || document.write('<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"><\/script>');</script>
<script>window.bootstrap || document.write('<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"><\/script>');</script>

<script>
(function ($) {
    'use strict';

    const BASE_URL   = '<?= base_url() ?>';
    const CSRF_NAME  = '<?= csrf_token() ?>';

    const BADGE_MAP = {
        '1':'badge-success','2':'badge-info',
        '3':'badge-warning','4':'badge-orange','5':'badge-danger'
    };
    const LABEL_MAP = {
        '1':'Sangat Rendah','2':'Rendah',
        '3':'Sedang','4':'Tinggi','5':'Sangat Tinggi'
    };

    let csrfHash        = '<?= csrf_hash() ?>';
    let saveTimers      = {};
    let pollTimer       = null;
    let komentarLoading = false;
    let lastReadTime    = new Date().toISOString();
    let syncCheckCache = null;
    let syncInProgress  = false;

    const escEl = (function () {
        const d = document.createElement('div');
        return function (s) { d.textContent = s; return d.innerHTML; };
    }());

    function getCsrfData()  { const o = {}; o[CSRF_NAME] = csrfHash; return o; }
    function badgeClass(p)  { return BADGE_MAP[String(p)] || 'badge-secondary'; }
    function rankLabel(p)   { return LABEL_MAP[String(p)] || 'Belum Ada Data'; }

    function formatRupiah(raw) {
        if (!raw && raw !== 0) return '';
        const neg     = String(raw).charAt(0) === '-';
        const clean   = String(raw).replace(/[^0-9,]/g, '');
        const [int, dec] = clean.split(',');
        const grouped = int.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        return (neg ? '-' : '') + grouped + (dec !== undefined ? ',' + dec : '');
    }

    function unformatRupiah(v) {
        if (!v) return '';
        return String(v).replace(/\./g, '').replace(',', '.');
    }

    /* Toast */
    function showToast(type, message) {
        $('.custom-toast').remove();
        const $t = $('<div class="custom-toast"></div>')
            .addClass(type === 'success' ? 'toast-success' : 'toast-error')
            .html('<i class="fas ' + (type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle') + ' me-2"></i>' + escEl(message));
        $('body').append($t);
        requestAnimationFrame(() => requestAnimationFrame(() => $t.addClass('show')));
        setTimeout(() => { $t.removeClass('show'); setTimeout(() => $t.remove(), 400); }, 3000);
    }

    function updateBadge(count) {
        const $b = $('#notification-badge');
        $b.text(count);
        count > 0 ? $b.fadeIn(200) : $b.fadeOut(200);
    }

    /* Polling */
    function pollUnreadCount() {
        $.get(BASE_URL + '/Tkspermodalan/getUnreadCount', function (r) {
            if (r.status === 'success') updateBadge(r.count);
        }, 'json').fail(function () {});
    }

    function checkSyncAvailability() {
        const $btn = $('#btnSyncPrevious');
        const $tip = $('#sync-tooltip');
        $tip.text('Memeriksa periode sebelumnya…');
        $.get(BASE_URL + '/Tkspermodalan/checkSyncStatus', function (r) {
            syncCheckCache = r;
            if (r && r.available) {
                $btn.prop('disabled', false);
                $tip.text('Sinkron dari ' + (r.periode_label || 'periode sebelumnya'));
            } else {
                $btn.prop('disabled', true);
                $tip.text(r && r.periode_label ? 'Periode aktif sudah memiliki data' : 'Tidak ada data pada periode sebelumnya');
            }
        }, 'json').fail(function () {
            $btn.prop('disabled', true);
            $tip.text('Gagal memeriksa data periode sebelumnya');
        });
    }

    function startPolling() { pollUnreadCount(); pollTimer = setInterval(pollUnreadCount, 30000); }
    function stopPolling()  { clearInterval(pollTimer); }

    document.addEventListener('visibilitychange', function () {
        document.hidden ? stopPolling() : startPolling();
    });

    /* Update display after save */
    function updateDisplay(d) {
        if (!d) return;

        function setRatio(id, val, cls) {
            const v = parseFloat(val);
            if (isNaN(v) || val == null) return;
            $('#' + id).html('<span class="' + cls + '">' + v.toFixed(2) + '%</span>');
        }
        function setPeringkat(dispId, sumId, val) {
            if (!val || val <= 0) return;
            $('#' + dispId).html('<span class="text-primary">' + val + '</span>');
            if (sumId) $('#' + sumId).text(val).attr('class', 'badge badge-sm ' + badgeClass(val));
        }
        function setPenilaian(dispId, sumId, val, p) {
            if (!val) return;
            const cls = 'badge badge-sm ' + badgeClass(String(p));
            $('#' + dispId).text(val).attr('class', cls);
            if (sumId) $('#' + sumId).text(val);
        }

        // Current KPMM
        if (d.rasiokpmm    != null) { setRatio('displayRasioKPMM', d.rasiokpmm, 'text-success'); $('#summaryRasioKPMM').text(parseFloat(d.rasiokpmm).toFixed(2) + '%'); }
        if (d.peringkatkpmm!= null) setPeringkat('displayPeringkatKPMM', 'summaryPeringkatKPMM', d.peringkatkpmm);
        if (d.penilaiankpmm!= null) setPenilaian('displayPenilaianKPMM', 'summaryPenilaianKPMM', d.penilaiankpmm, d.peringkatkpmm);

        if (d.rasiokpmm || d.peringkatkpmm || d.penilaiankpmm) {
            $('#resultKPMM').addClass('highlight-box');
            setTimeout(() => $('#resultKPMM').removeClass('highlight-box'), 1000);
        }

        // Current MIAPB
        if (d.rasiomiapb    != null) { setRatio('displayRasioMIAPB', d.rasiomiapb, 'text-warning'); $('#summaryRasioMIAPB').text(parseFloat(d.rasiomiapb).toFixed(2) + '%'); }
        if (d.peringkatmiapb!= null) setPeringkat('displayPeringkatMIAPB', 'summaryPeringkatMIAPB', d.peringkatmiapb);
        if (d.penilaianmiapb!= null) setPenilaian('displayPenilaianMIAPB', 'summaryPenilaianMIAPB', d.penilaianmiapb, d.peringkatmiapb);

        if (d.rasiomiapb || d.peringkatmiapb || d.penilaianmiapb) {
            $('#resultMIAPB').addClass('highlight-box');
            setTimeout(() => $('#resultMIAPB').removeClass('highlight-box'), 1000);
        }

        // Previous KPMM
        if (d.rasiokpmmbefore    != null) { setRatio('displayRasioKPMMBefore', d.rasiokpmmbefore, ''); $('#displayRasioKPMMBefore span').css('color','#6366f1'); $('#summaryRasioKPMMBefore').text(parseFloat(d.rasiokpmmbefore).toFixed(2) + '%'); }
        if (d.peringkatkpmmbefore!= null) setPeringkat('displayPeringkatKPMMBefore', 'summaryPeringkatKPMMBefore', d.peringkatkpmmbefore);
        if (d.penilaiankpmmbefore!= null) setPenilaian('displayPenilaianKPMMBefore', 'summaryPenilaianKPMMBefore', d.penilaiankpmmbefore, d.peringkatkpmmbefore);

        if (d.rasiokpmmbefore || d.peringkatkpmmbefore || d.penilaiankpmmbefore) {
            $('#resultKPMMBefore').addClass('highlight-box');
            setTimeout(() => $('#resultKPMMBefore').removeClass('highlight-box'), 1000);
        }

        // Previous MIAPB
        if (d.rasiomiapbbefore    != null) { setRatio('displayRasioMIAPBBefore', d.rasiomiapbbefore, ''); $('#displayRasioMIAPBBefore span').css('color','#6366f1'); $('#summaryRasioMIAPBBefore').text(parseFloat(d.rasiomiapbbefore).toFixed(2) + '%'); }
        if (d.peringkatmiapbbefore!= null) setPeringkat('displayPeringkatMIAPBBefore', 'summaryPeringkatMIAPBBefore', d.peringkatmiapbbefore);
        if (d.penilaianmiapbbefore!= null) setPenilaian('displayPenilaianMIAPBBefore', 'summaryPenilaianMIAPBBefore', d.penilaianmiapbbefore, d.peringkatmiapbbefore);

        if (d.rasiomiapbbefore || d.peringkatmiapbbefore || d.penilaianmiapbbefore) {
            $('#resultMIAPBBefore').addClass('highlight-box');
            setTimeout(() => $('#resultMIAPBBefore').removeClass('highlight-box'), 1000);
        }

        // Summary current
        if (d.rataratapermodalan  != null) $('#displayRataRata').text(parseFloat(d.rataratapermodalan).toFixed(2));
        if (d.peringkatpermodalan != null && d.peringkatpermodalan > 0) {
            const p = String(d.peringkatpermodalan);
            $('#displayPeringkat').text(p);
            $('#displayKategori').text(rankLabel(p)).attr('class', 'badge badge-xl ' + badgeClass(p));
        }

        // Summary previous
        if (d.rataratapermodalanbefore  != null) $('#displayRataRataBefore').text(parseFloat(d.rataratapermodalanbefore).toFixed(2));
        if (d.peringkatpermodalanbefore != null && d.peringkatpermodalanbefore > 0) {
            const p = String(d.peringkatpermodalanbefore);
            $('#displayPeringkatBefore').text(p);
            $('#displayKategoriBefore').text(rankLabel(p)).attr('class', 'badge badge-xl ' + badgeClass(p));
        }

        if (d.modalintiapb != null) {
            const $mi = $('input[data-field="modalintiapb"]');
            if ($mi.length && !$mi.is(':focus')) {
                $mi.val(formatRupiah(String(Math.round(d.modalintiapb))));
            }
        }
        if (d.modalintiapbbefore != null) {
            const $mi = $('input[data-field="modalintiapbbefore"]');
            if ($mi.length && !$mi.is(':focus')) {
                $mi.val(formatRupiah(String(Math.round(d.modalintiapbbefore))));
            }
        }
    }

    function autoSave(field, value, $el) {
        clearTimeout(saveTimers[field]);
        const $ind    = $el.siblings('.saving-indicator');
        const rawValue = $el.hasClass('currency-input') ? unformatRupiah(value) : value;

        $ind.html('<small class="text-warning"><i class="fas fa-spinner fa-spin"></i> Menyimpan...</small>').show();

        saveTimers[field] = setTimeout(function () {
            const postData = $.extend({ field: field, value: rawValue }, getCsrfData());

            $.ajax({
                url:      BASE_URL + '/Tkspermodalan/autoSave',
                method:   'POST',
                data:     postData,
                dataType: 'json',
                success: function (res) {
                    if (res.success) {
                        if (res.csrf_hash) csrfHash = res.csrf_hash;
                        $ind.html('<small class="text-success"><i class="fas fa-check-circle"></i> Tersimpan</small>');
                        updateDisplay(res.data);
                        showToast('success', 'Data berhasil disimpan');
                        setTimeout(() => $ind.fadeOut(300), 2000);
                    } else {
                        $ind.html('<small class="text-danger"><i class="fas fa-times-circle"></i> ' + escEl(res.message || 'Gagal') + '</small>');
                        showToast('error', res.message || 'Gagal menyimpan data');
                    }
                },
                error: function (xhr) {
                    const msgs = { 403:'CSRF invalid – refresh halaman', 404:'URL tidak ditemukan', 500:'Server error' };
                    const msg  = msgs[xhr.status] || 'Error koneksi';
                    $ind.html('<small class="text-danger"><i class="fas fa-times-circle"></i> ' + msg + '</small>');
                    showToast('error', msg);
                }
            });
        }, 1000);
    }

    /* Komentar */
    function formatDateTime(dateStr) {
        const date  = new Date(dateStr), now = new Date();
        const ms    = now - date;
        const mins  = Math.floor(ms / 60000);
        const hours = Math.floor(ms / 3600000);
        const days  = Math.floor(ms / 86400000);
        if (mins  <  1) return 'Baru saja';
        if (mins  < 60) return mins  + ' menit yang lalu';
        if (hours < 24) return hours + ' jam yang lalu';
        if (days  <  7) return days  + ' hari yang lalu';
        return ('0'+date.getDate()).slice(-2)+'/'+('0'+(date.getMonth()+1)).slice(-2)+'/'+date.getFullYear()
             + ' '+('0'+date.getHours()).slice(-2)+':'+('0'+date.getMinutes()).slice(-2);
    }

    function loadKomentarList(scrollToBottom) {
        if (komentarLoading) return;
        komentarLoading = true;

        $.get(BASE_URL + '/Tkspermodalan/getKomentar', function (list) {
            const $c = $('#komentarList');
            if (!list || !list.length) {
                $c.html('<div class="text-center text-muted py-4"><i class="fas fa-inbox fa-3x mb-3" style="opacity:.3;"></i><p>Belum ada komentar</p></div>');
                return;
            }
            let html = '';
            list.forEach(function (k) {
                const isNew = new Date(k.created_at) > new Date(lastReadTime);
                const border = isNew ? '#28a745' : '#007bff';
                const bg = isNew ? '#d4edda'  : '#ffffff';
                const newBadge = isNew ? '<span class="badge badge-success ms-2"><i class="fas fa-star"></i> BARU</span>' : '';
                html += '<div class="komentar-item mb-3 p-3" style="background:'+bg+';border-radius:6px;border-left:3px solid '+border+';">'
                      + '<div class="d-flex justify-content-between align-items-start mb-2">'
                      + '<strong style="color:'+border+';"><i class="fas fa-user-circle"></i> '+escEl(k.fullname||'Unknown')+newBadge+'</strong>'
                      + '<small class="text-muted"><i class="fas fa-clock"></i> '+formatDateTime(k.created_at)+'</small></div>'
                      + '<p class="mb-0" style="white-space:pre-wrap;">'+escEl(k.komentar)+'</p></div>';
            });
            $c.html(html);
            if (scrollToBottom) $c.scrollTop($c[0].scrollHeight);
        }, 'json')
        .fail(function () {
            $('#komentarList').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Gagal memuat komentar</div>');
        })
        .always(function () { komentarLoading = false; });
    }

    function getKomentarModal() {
        const el = document.getElementById('modalKomentar');
        if (!el) return null;
        return bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
    }

    /* Ready */
    $(function () {
        $('.currency-input').each(function () {
            const v = $(this).val();
            if (v) $(this).val(formatRupiah(v));
        });

        $('#btnSyncPrevious').on('click', function () {
            if (syncInProgress) return;
            if (!syncCheckCache || !syncCheckCache.available) {
                showToast('error', 'Tidak ada data pada periode sebelumnya');
                return;
            }
            if (!confirm('Sinkronkan data dari ' + (syncCheckCache.periode_label || 'periode sebelumnya') + ' ke periode saat ini?')) return;

            const $btn = $(this);
            syncInProgress = true;
            $btn.prop('disabled', true).find('i').addClass('fa-spin');

            $.ajax({
                url:      BASE_URL + '/Tkspermodalan/syncFromPrevious',
                method:   'POST',
                data:     getCsrfData(),
                dataType: 'json',
                success: function (res) {
                    if (res.success) {
                        if (res.csrf_hash) csrfHash = res.csrf_hash;
                        showToast('success', res.message || 'Data berhasil disinkronkan');
                        setTimeout(() => location.reload(), 800);
                    } else {
                        showToast('error', res.message || 'Gagal melakukan sinkronisasi');
                        syncInProgress = false;
                        $btn.prop('disabled', false).find('i').removeClass('fa-spin');
                    }
                },
                error: function () {
                    showToast('error', 'Gagal melakukan sinkronisasi');
                    syncInProgress = false;
                    $btn.prop('disabled', false).find('i').removeClass('fa-spin');
                }
            });
        });
        checkSyncAvailability();

        $(document).on('input', '.currency-input', function () {
            const el = this;
            const oldValue = el.value;
            const oldPos = el.selectionStart;

            // Hitung jumlah karakter "bermakna" (digit + koma desimal) sebelum kursor
            const meaningfulBeforeCursor = oldValue.slice(0, oldPos).replace(/[^0-9,]/g, '').length;

            // Format ulang nilai
            const newValue = formatRupiah(oldValue);
            el.value = newValue;

            // Cari posisi baru: taruh kursor setelah karakter bermakna ke-N yang sama
            let count = 0;
            let newPos = newValue.length;
            if (meaningfulBeforeCursor === 0) {
                newPos = 0;
            } else {
                for (let i = 0; i < newValue.length; i++) {
                    if (/[0-9,]/.test(newValue[i])) {
                        count++;
                        if (count === meaningfulBeforeCursor) {
                            newPos = i + 1;
                            break;
                        }
                    }
                }
            }

            try { el.setSelectionRange(newPos, newPos); } catch (e) {}
        });

        $(document).on('change', '.auto-save', function () {
            const field = $(this).data('field');
            if (field) autoSave(field, $(this).val(), $(this));
        });

        $(document).on('keyup', 'textarea.auto-save', function () {
            const field = $(this).data('field');
            if (field) autoSave(field, $(this).val(), $(this));
        });

        $(document).on('keydown', 'input.auto-save', function (e) {
            if (e.which === 13) { e.preventDefault(); $(this).trigger('change'); }
        });

        // Komentar button
        $('.komentar-button').on('click', function () {
            const modal = getKomentarModal();
            if (!modal) { console.error('Modal #modalKomentar not found'); return; }

            komentarLoading = false;
            loadKomentarList(true);

            $.ajax({
                url:    BASE_URL + '/Tkspermodalan/markCommentsAsRead',
                method: 'POST',
                data:   getCsrfData(),
                success: function (res) {
                    if (res.status === 'success') {
                        updateBadge(0);
                        lastReadTime = new Date().toISOString();
                        if (res.csrf_hash) csrfHash = res.csrf_hash;
                    }
                }
            });

            modal.show();
        });

        // Submit komentar
        $('#formKomentar').on('submit', function (e) {
            e.preventDefault();
            const $form = $(this);
            const $btn  = $form.find('button[type="submit"]');
            const txt   = $form.find('[name="komentar"]').val().trim();

            if (!txt) { showToast('error', 'Komentar tidak boleh kosong'); return; }

            $btn.html('<i class="fas fa-spinner fa-spin"></i> Mengirim...').prop('disabled', true);

            $.ajax({
                url:      BASE_URL + '/Tkspermodalan/tambahKomentar',
                method:   'POST',
                data:     $form.serialize() + '&' + encodeURIComponent(CSRF_NAME) + '=' + encodeURIComponent(csrfHash),
                dataType: 'json',
                success: function (res) {
                    if (res.status === 'success') {
                        $form.find('[name="komentar"]').val('');
                        loadKomentarList(true);
                        showToast('success', 'Komentar berhasil dikirim');
                        pollUnreadCount();
                    } else {
                        showToast('error', res.message || 'Gagal menambahkan komentar');
                    }
                },
                error: function () { showToast('error', 'Gagal menambahkan komentar'); },
                complete: function () {
                    $btn.html('<i class="fas fa-paper-plane"></i> Kirim Komentar').prop('disabled', false);
                }
            });
        });

        function updateCharCounter($textarea) {
            var $counter = $textarea.closest('.card-body').find('.char-counter');
            var len = $textarea.val().length;
            $counter.text(len + ' / 2000');
            $counter.toggleClass('text-danger', len >= 2000).toggleClass('text-muted', len < 2000);
        }

        $('.char-limited').each(function () { updateCharCounter($(this)); });

        $(document).on('input', '.char-limited', function () {
            var $el = $(this);
            if ($el.val().length > 2000) $el.val($el.val().substring(0, 2000));
            updateCharCounter($el);
        });

        document.querySelectorAll('[data-bs-toggle="popover"]').forEach(function (el) {
            new bootstrap.Popover(el, { container:'body', trigger:'hover focus', html:true, sanitize:false });
        });

        startPolling();
    });

    (function initMisTksTarik() {
        var btn        = document.getElementById('btnTarikMisTks');
        var konfirmBtn = document.getElementById('btn-konfirmasi-mistks');
        var batalBtn   = document.getElementById('btn-batal-mistks');
        var closeBtn   = document.getElementById('btn-close-mistks-modal');
        var resultArea = document.getElementById('mistks-result-area');
        var warningArea= document.getElementById('mistks-warning-area');
        var modalEl    = document.getElementById('modalTarikMisTks');

        if (!btn || !modalEl) return;

        var _pending = false;
        var _modal   = null;

        function fmtPreview(val) {
            if (val === null || val === undefined || val === '') return '<em style="color:#94a3b8;">tidak ditemukan</em>';
            var n = parseFloat(val);
            return isNaN(n) ? '-' : 'Rp ' + n.toLocaleString('id-ID', { maximumFractionDigits: 0 });
        }

        function buildPreviewGroup(rows) {
            var html = '';
            rows.forEach(function (r) {
                html += '<div class="sync-preview-row"><span class="sync-preview-label">' + r[0] + '</span>'
                    + '<span class="sync-preview-value">' + fmtPreview(r[1]) + '</span></div>';
            });
            return html;
        }

        function resetModal() {
            konfirmBtn.disabled  = false;
            konfirmBtn.innerHTML = '<i class="fas fa-database"></i> Tarik Sekarang';
            konfirmBtn.style.display = '';
            if (batalBtn) { batalBtn.disabled = false; batalBtn.style.display = ''; }
            if (closeBtn) { closeBtn.disabled = false; }
            resultArea.style.display  = 'none';
            resultArea.innerHTML      = '';
            warningArea.style.display = '';
            document.getElementById('mistks-preview-kpmm').innerHTML  = '';
            document.getElementById('mistks-preview-miapb').innerHTML = '';
        }

        function showResult(ok, message) {
            warningArea.style.display = 'none';
            konfirmBtn.style.display  = 'none';
            if (batalBtn) batalBtn.style.display = 'none';
            resultArea.style.display = '';
            resultArea.innerHTML = ok
                ? '<div style="text-align:center;padding:20px 10px;">'
                    + '<div style="font-size:48px;color:#10b981;margin-bottom:12px;"><i class="fas fa-check-circle"></i></div>'
                    + '<div style="font-size:16px;font-weight:700;color:#065f46;margin-bottom:6px;">Data Berhasil Ditarik!</div>'
                    + '<div style="font-size:13px;color:#6b7280;">' + escEl(message) + '</div>'
                    + '<div style="margin-top:16px;padding:10px 16px;background:#d1fae5;border-radius:8px;font-size:13px;color:#065f46;">'
                    + '<i class="fas fa-spinner fa-spin me-2"></i> Memuat ulang halaman...</div></div>'
                : '<div style="text-align:center;padding:16px 10px;">'
                    + '<div style="font-size:40px;color:#ef4444;margin-bottom:10px;"><i class="fas fa-times-circle"></i></div>'
                    + '<div style="font-size:15px;font-weight:700;color:#7f1d1d;margin-bottom:6px;">Gagal Menarik Data</div>'
                    + '<div style="font-size:13px;color:#6b7280;">' + escEl(message) + '</div></div>';
            if (!ok) {
                if (batalBtn) { batalBtn.disabled = false; batalBtn.style.display = ''; }
                if (closeBtn) { closeBtn.disabled = false; }
            }
        }

        btn.addEventListener('click', function () {
            $.ajax({
                url: BASE_URL + '/Tkspermodalan/checkMisTksData',
                method: 'GET', dataType: 'json',
                success: function (res) {
                    if (res.status !== 'found') {
                        showToast('error', res.message || 'Data tidak ditemukan di mis_tks');
                        return;
                    }
                    resetModal();
                    document.getElementById('mistks-current-label').textContent = res.periode_current;
                    document.getElementById('mistks-prev-label').textContent    = res.periode_prev;

                    var d = res.data || {};

                    document.getElementById('mistks-preview-kpmm').innerHTML = buildPreviewGroup([
                        ['Modal Inti (saat ini)',        d.modalinti],
                        ['Modal Pelengkap (saat ini)',   d.modalpelengkap],
                        ['ATMR (saat ini)',              d.atmr],
                        ['Modal Inti (sebelumnya)',      d.modalintibefore],
                        ['Modal Pelengkap (sebelumnya)', d.modalpelengkapbefore],
                        ['ATMR (sebelumnya)',            d.atmrbefore],
                    ]);

                    document.getElementById('mistks-preview-miapb').innerHTML = buildPreviewGroup([
                        ['Modal Inti APB (saat ini)',              d.modalintiapb],
                        ['Total Aset Produktif Bermasalah (saat ini)', d.totalasetbermasalah],
                        ['Modal Inti APB (sebelumnya)',            d.modalintiapbbefore],
                        ['Total Aset Produktif Bermasalah (sebelumnya)', d.totalasetbermasalahbefore],
                    ]);

                    if (!_modal) _modal = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false });
                    _modal.show();
                },
                error: function () { showToast('error', 'Gagal memeriksa data mis_tks'); }
            });
        });

        konfirmBtn.addEventListener('click', function () {
            if (_pending) return;
            _pending = true;
            konfirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menarik data...';
            konfirmBtn.disabled  = true;
            if (batalBtn) batalBtn.disabled = true;
            if (closeBtn) closeBtn.disabled = true;

            $.ajax({
                url: BASE_URL + '/Tkspermodalan/tarikDataMisTks',
                method: 'POST', data: getCsrfData(), dataType: 'json',
                success: function (res) {
                    _pending = false;
                    if (res.csrf_hash) csrfHash = res.csrf_hash;
                    if (res.status === 'success' || res.success) {
                        showResult(true, res.message || 'Data berhasil disimpan');
                        setTimeout(function () { location.reload(); }, 2000);
                    } else {
                        showResult(false, res.message || 'Gagal menarik data');
                    }
                },
                error: function (xhr) {
                    _pending = false;
                    var msg = 'Terjadi kesalahan server.';
                    if (xhr.status === 403) msg = 'Sesi CSRF kedaluwarsa, silakan refresh halaman';
                    showResult(false, msg);
                }
            });
        });

        modalEl.addEventListener('hidden.bs.modal', function () {
            if (!_pending) resetModal();
        });
    })();

}(jQuery));
</script>

<style>
/* Layout */
body { background-color:#f8f9fa; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; }
.main-content { max-width:1400px; margin:0 auto; }

/* Cards */
.card { border-radius:12px; border:none; box-shadow:0 2px 8px rgba(0,0,0,.08); margin-bottom:1.5rem; }
.card-header { border-bottom:1px solid #e5e7eb; padding:1rem 1.25rem; border-radius:12px 12px 0 0 !important; }
.bg-gradient-primary { background:linear-gradient(135deg,#3b82f6 0%,#2563eb 100%); }
.bg-gradient-success { background:linear-gradient(135deg,#10b981 0%,#059669 100%); }
.bg-gradient-warning { background:linear-gradient(135deg,#f59e0b 0%,#d97706 100%); }

/* Form controls */
.form-control { border:2px solid #e5e7eb; border-radius:8px; padding:.75rem; font-size:15px; transition:border-color .2s,box-shadow .2s; }
.form-control:focus { border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,.1); }

/* Formula boxes */
.formula-box { background:linear-gradient(135deg,#f0f9ff 0%,#e0f2fe 100%); border:2px solid #3b82f6; border-radius:12px; padding:1.5rem; margin-bottom:1.5rem; }
.formula-box-red { background:linear-gradient(135deg,#fef3c7 0%,#fde68a 100%); border:2px solid #f59e0b; border-radius:12px; padding:1.5rem; margin-bottom:1.5rem; }
.formula-display { font-size:1.25rem; font-weight:600; text-align:center; color:#1e40af; }
.formula-fraction { display:inline-flex; flex-direction:column; vertical-align:middle; text-align:center; margin:0 .5rem; }
.formula-numerator,.formula-denominator { padding:.25rem .5rem; }
.formula-numerator { border-bottom:2px solid #1e40af; }

/* Period boxes */
.period-box   { border:2px solid #e5e7eb; border-radius:12px; padding:1.5rem; background:#fff; height:100%; }
.current-period  { border-left:4px solid #10b981; background:linear-gradient(135deg,#fff 0%,#f0fdf4 100%); }
.previous-period { border-left:4px solid #6366f1; background:linear-gradient(135deg,#fff 0%,#eef2ff 100%); }
.period-title { font-weight:700; font-size:1rem; margin-bottom:1rem; padding-bottom:.75rem; border-bottom:2px solid #e5e7eb; }
.current-period  .period-title { color:#059669; }
.previous-period .period-title { color:#4f46e5; }

/* Info boxes */
.info-box   { background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.08); text-align:center; padding:1.5rem; }
.info-label { font-size:.9rem; color:#6b7280; margin-bottom:.5rem; font-weight:500; }
.info-value { font-size:2rem; font-weight:700; color:#1f2937; }

/* Result boxes */
.result-box { background:linear-gradient(135deg,#ecfdf5 0%,#d1fae5 100%); border:2px solid #10b981; border-radius:12px; padding:1.5rem; margin-top:1.5rem; transition:box-shadow .3s; }
@keyframes pulse { 0%,100%{transform:scale(1)} 50%{transform:scale(1.02)} }
.result-box.highlight-box { animation:pulse .6s ease-in-out; box-shadow:0 0 20px rgba(16,185,129,.4); }
.result-item { text-align:center; }
.result-item label { font-size:.9rem; color:#6b7280; margin-bottom:.5rem; font-weight:600; display:block; }
.result-value { font-size:1.75rem; font-weight:700; color:#065f46; }
.result-value .text-muted { font-size:1.2rem; font-weight:500; }

/* Component boxes */
.component-box    { background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,.08); height:100%; }
.component-header { padding:.75rem; color:#fff; font-weight:600; font-size:.95rem; text-align:center; }
.component-body   { padding:1rem; }
.component-item   { display:flex; justify-content:space-between; align-items:center; padding:.5rem 0; border-bottom:1px solid #f3f4f6; }
.component-item:last-child { border-bottom:none; }
.component-label  { font-size:.875rem; color:#6b7280; font-weight:500; }
.component-value  { font-size:.95rem; color:#1f2937; font-weight:600; }

/* Peringkat info */
.peringkat-info { background:#f9fafb; border-radius:8px; padding:1rem; margin-top:1.5rem; }
.small-bold { font-size:.85rem; font-weight:600; }

/* Badges */
.badge          { padding:.5rem 1rem;  font-size:.875rem; font-weight:600; border-radius:6px; }
.badge-sm       { padding:.375rem .75rem; font-size:.8rem; }
.badge-xl       { padding:.75rem 1.5rem; font-size:1.1rem; font-weight:700; }
.badge-success  { background-color:#10b981; color:#fff; }
.badge-info     { background-color:#06b6d4; color:#fff; }
.badge-warning  { background-color:#f59e0b; color:#fff; }
.badge-orange   { background-color:#f97316; color:#fff; }
.badge-danger   { background-color:#ef4444; color:#fff; }
.badge-secondary{ background-color:#6b7280; color:#fff; }

/* Saving...*/
.saving-indicator { margin-top:.5rem; min-height:24px; }

.custom-toast {
    position:fixed; top:24px; right:24px; z-index:9999;
    padding:16px 24px; border-radius:12px; color:#fff;
    display:flex; align-items:center; min-width:300px;
    box-shadow:0 10px 30px rgba(0,0,0,.3);
    transform:translateX(420px); opacity:0;
    transition:transform .4s ease, opacity .4s ease;
    font-weight:500;
}
.custom-toast.show       { transform:translateX(0); opacity:1; }
.custom-toast.toast-success { background:linear-gradient(135deg,#10b981,#059669); }
.custom-toast.toast-error   { background:linear-gradient(135deg,#ef4444,#dc2626); }

/* Buttons */
.btn { padding:.75rem 1.5rem; border-radius:8px; font-weight:500; transition:all .3s; }
.btn-outline-secondary { border:2px solid #6b7280; color:#6b7280; }
.btn-outline-secondary:hover { background:#6b7280; color:#fff; }

/* Approval card */
.approvaldir-card       { border-radius:12px; border:2px solid #e5e7eb; }
.approvaldir-card-body  { padding:1.5rem; }
.approval-badge-container { text-align:center; margin-bottom:1rem; }
.approval-badge   { background:#343a40; color:#fff; font-size:.9rem; font-weight:600; padding:8px 16px; border-radius:6px; display:inline-block; }
.approval-buttons-container { display:flex; justify-content:center; align-items:center; gap:12px; }
.approval-btn     { flex:1; max-width:48%; padding:10px 16px; font-size:.95rem; font-weight:500; border-radius:8px; text-align:center; text-decoration:none; display:flex; align-items:center; justify-content:center; gap:6px; transition:all .3s; }
.approval-btn-approve { background:#28a745; border-color:#28a745; color:#fff; }
.approval-btn-approve:hover:not(.disabled-btn) { background:#218838; transform:translateY(-1px); color:#fff; }
.approval-btn-reject  { background:#dc3545; border-color:#dc3545; color:#fff; }
.approval-btn-reject:hover:not(.disabled-btn)  { background:#c82333; transform:translateY(-1px); color:#fff; }
.disabled-btn { opacity:.5; pointer-events:none; }

/* Notification badge */
.notification-badge { position:absolute; top:-8px; right:-8px; font-size:11px; padding:3px 7px; border-radius:50%; background:#dc3545; color:#fff; font-weight:bold; min-width:20px; text-align:center; }
.komentar-button    { position:relative; }
.komentar-item      { transition:transform .2s ease; }
.komentar-item:hover{ transform:translateX(5px); }

/* Approval badge */
#approval-badge-main { transition:all .3s ease; }

/* Comment scrollbar */
#komentarList::-webkit-scrollbar       { width:8px; }
#komentarList::-webkit-scrollbar-track { background:#f1f1f1; border-radius:4px; }
#komentarList::-webkit-scrollbar-thumb { background:#888; border-radius:4px; }
#komentarList::-webkit-scrollbar-thumb:hover { background:#555; }

.mistks-group-header {
    font-size:12.5px; font-weight:700; text-transform:uppercase; letter-spacing:.5px;
    padding:8px 12px; border-radius:8px 8px 0 0; margin-top:12px;
}
.mistks-preview-box {
    background:white; border:1.5px solid #e2e8f0; border-top:none;
    border-radius:0 0 8px 8px; padding:8px 12px; font-size:12.5px;
}
.mistks-preview-box:empty::before { content:'Memuat...'; color:#94a3b8; font-style:italic; }
.sync-tks-info-box {
    display:flex; align-items:center; justify-content:center; gap:16px;
    border-radius:12px; padding:14px 18px; border:1.5px solid;
}
.sync-tks-periode { display:flex; flex-direction:column; align-items:center; gap:3px; flex:1; text-align:center; }
.sync-tks-label   { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.7px; color:#a78bfa; }
.sync-tks-val     { font-size:14px; font-weight:800; }
.sync-preview-row { display:flex; justify-content:space-between; padding:5px 0; border-bottom:1px solid #f1f5f9; font-size:12px; }
.sync-preview-row:last-child { border-bottom:none; }
.sync-preview-label { color:#64748b; font-weight:600; }
.sync-preview-value { color:#1e293b; font-weight:700; }

/* Sync tooltip - dipakai bersama untuk sync & tarik mis_tks */
#btnSyncPrevious:disabled,
#btnTarikMisTks:disabled { opacity:.45; cursor:not-allowed; }

.sync-tooltip-tks {
    position:absolute; bottom:calc(100% + 8px); left:50%;
    transform:translateX(-50%);
    background:#1e293b; color:white;
    font-size:11px; font-weight:600;
    padding:5px 10px; border-radius:8px;
    white-space:nowrap; pointer-events:none;
    opacity:0; transition:opacity .2s; z-index:100;
}
.sync-tooltip-tks::after {
    content:''; position:absolute; top:100%; left:50%;
    transform:translateX(-50%);
    border:5px solid transparent; border-top-color:#1e293b;
}
#btnSyncPrevious:hover + .sync-tooltip-tks,
#btnSyncPrevious:focus + .sync-tooltip-tks,
#btnTarikMisTks:hover + .sync-tooltip-tks,
#btnTarikMisTks:focus + .sync-tooltip-tks { opacity:1; }

/* Mobile View */
@media (max-width:991px) { .period-box { margin-bottom:1rem; } }
@media (max-width:768px) {
    .card-body      { padding:1rem !important; }
    .period-box     { padding:1rem; }
    .info-value     { font-size:1.5rem; }
    .result-value   { font-size:1.25rem; }
    .formula-display{ font-size:1rem; }
    .component-item { flex-direction:column; align-items:flex-start; gap:.25rem; }
    .custom-toast   { right:12px; top:12px; min-width:250px; font-size:.875rem; }
    .approval-btn   { font-size:.85rem; padding:8px 12px; }
}
@media (max-width:576px) {
    .peringkat-info .badge { font-size:.75rem; padding:.375rem .75rem; }
    .badge-xl { font-size:.95rem; padding:.6rem 1.2rem; }
    .info-value { font-size:1.25rem; }
}
</style>