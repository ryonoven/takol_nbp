<?php
$peringkatLabels = ['1'=>'Sangat Baik','2'=>'Baik','3'=>'Cukup','4'=>'Kurang Baik','5'=>'Buruk'];
$badgeMap = ['1'=>'badge-success','2'=>'badge-info','3'=>'badge-warning','4'=>'badge-orange','5'=>'badge-danger'];

function getBadgeClass($nilai) {
    global $badgeMap;
    return $badgeMap[$nilai] ?? 'badge-secondary';
}

$r = $tksrentabilitas;

$rataRata = $r['rataratarentabilitas'] ?? 0;
$peringkatRentabilitas = $r['peringkatrentabilitas'] ?? 0;
$peringkatLabel = $peringkatLabels[$peringkatRentabilitas] ?? 'Belum Ada Data';
$rasioROA = $r['rasioroa'] ?? 0;
$peringkatROA = $r['peringkatroa'] ?? 0;
$penilaianROA = $r['penilaianroa'] ?? 'Belum dinilai';
$rasioBOPO = $r['rasiobopo'] ?? 0;
$peringkatBOPO = $r['peringkatbopo'] ?? 0;
$penilaianBOPO = $r['penilaianbopo'] ?? 'Belum dinilai';
$rasioNIM = $r['rasionim'] ?? 0;
$peringkatNIM = $r['peringkatnim'] ?? 0;
$penilaianNIM = $r['penilaiannim'] ?? 'Belum dinilai';
$badgeClass = getBadgeClass($peringkatRentabilitas);

$rasioROABefore = $r['rasioroabefore'] ?? 0;
$peringkatROABefore = $r['peringkatroabefore'] ?? 0;
$penilaianROABefore = $r['penilaianroabefore'] ?? 'Belum dinilai';
$rasioBOPOBefore = $r['rasiobopobefore'] ?? 0;
$peringkatBOPOBefore = $r['peringkatbopobefore'] ?? 0;
$penilaianBOPOBefore = $r['penilaianbopobefore'] ?? 'Belum dinilai';
$rasioNIMBefore = $r['rasionimbefore'] ?? 0;
$peringkatNIMBefore = $r['peringkatnimbefore'] ?? 0;
$penilaianNIMBefore = $r['penilaiannimbefore'] ?? 'Belum dinilai';
$rataRataBefore = $r['rataratarentabilitasbefore']  ?? 0;
$peringkatRentabilitasBefore = $r['peringkatrentabilitasbefore'] ?? 0;
$peringkatLabelBefore = $peringkatLabels[$peringkatRentabilitasBefore] ?? 'Belum Ada Data';
$badgeClassBefore = getBadgeClass($peringkatRentabilitasBefore);

$rasioROASet  = isset($r['rasioroa'])  && $r['rasioroa']  !== null;
$rasioBOPOSet = isset($r['rasiobopo']) && $r['rasiobopo'] !== null;
$rasioNIMSet  = isset($r['rasionim'])  && $r['rasionim']  !== null;

$rasioROABeforeSet  = isset($r['rasioroabefore'])  && $r['rasioroabefore']  !== null;
$rasioBOPOBeforeSet = isset($r['rasiobopobefore']) && $r['rasiobopobefore'] !== null;
$rasioNIMBeforeSet  = isset($r['rasionimbefore'])  && $r['rasionimbefore']  !== null;

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

        <!-- Header -->
        <div class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h4 class="mb-0 fw-bold text-primary">
                            <i class="fas fa-chart-line me-2 text-primary"></i>
                            Faktor 3. Penilaian Rentabilitas
                        </h4>
                        <p class="text-muted mb-0 mt-2"><small>Penilaian rentabilitas meliputi ROA, BOPO, dan NIM</small></p>
                    </div>

                    <div class="col-md-4 d-flex justify-content-end align-items-center gap-4">
                        <!-- Approval Badge -->
                        <span id="approval-badge-main"
                            style="display:inline-flex;flex-direction:column;justify-content:center;
                                padding:10px 18px;font-size:13px;line-height:1.4;border-radius:10px;ml-2;
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

                        <!-- Sync Button (hanya PE & Admin) -->
                        <?php if (($userInGroupPE ?? false) || ($userInGroupAdmin ?? false)): ?>
                        <div class="position-relative ml-2">
                            <button id="btn-sync-periode"
                                    style="width:46px;height:46px;border-radius:10px;border:none;
                                        background:#1e3a5f;color:#fff;font-size:16px;
                                        display:flex;align-items:center;justify-content:center;cursor:pointer;"
                                    disabled>
                                <i class="fas fa-sync-alt" id="sync-icon"></i>
                            </button>
                            <span id="sync-tooltip" class="sync-tooltip-tks">Memeriksa…</span>
                        </div>
                        <?php endif; ?>

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
                    </div>
                    <div class="position-relative ml-2">
                        <button id="btn-tarik-mistks"
                                style="width:46px;height:46px;border-radius:10px;border:none;
                                    background:#7c3aed;color:#fff;font-size:16px;
                                    display:flex;align-items:center;justify-content:center;cursor:pointer;">
                            <i class="fas fa-database" id="mistks-icon"></i>
                        </button>
                        <span id="mistks-tooltip" class="sync-tooltip-tks">Tarik Data MIS TKS</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ringkasan Periode Saat Ini -->
        <?php if ($peringkatRentabilitas > 0): ?>
        <div class="card shadow-sm">
            <div class="card-header bg-gradient-primary">
                <h6 class="mb-0 text-white fw-bold">
                    <i class="fas fa-award me-2"></i> Ringkasan Penilaian Rentabilitas - Periode Saat Ini
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
                            <div class="info-value text-warning" id="displayPeringkat"><?= $peringkatRentabilitas ?></div>
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
                <h6 class="fw-bold mb-3 text-secondary"><i class="fas fa-list-alt me-2"></i> Detail Komponen Penilaian</h6>
                <div class="row g-3">
                    <?php
                    $components = [
                        ['label'=>' ROA',  'color'=>'bg-success', 'textClass'=>'text-success',
                        'rasio'=>$rasioROA,  'rasioSet'=>$rasioROASet,  'peringkat'=>$peringkatROA,  'penilaian'=>$penilaianROA,
                        'ids'=>['summaryRasioROA','summaryPeringkatROA','summaryPenilaianROA']],
                        ['label'=>' BOPO', 'color'=>'bg-warning', 'textClass'=>'text-warning',
                        'rasio'=>$rasioBOPO, 'rasioSet'=>$rasioBOPOSet, 'peringkat'=>$peringkatBOPO, 'penilaian'=>$penilaianBOPO,
                        'ids'=>['summaryRasioBOPO','summaryPeringkatBOPO','summaryPenilaianBOPO']],
                        ['label'=>' NIM',  'color'=>'bg-info',    'textClass'=>'text-info',
                        'rasio'=>$rasioNIM,  'rasioSet'=>$rasioNIMSet,  'peringkat'=>$peringkatNIM,  'penilaian'=>$penilaianNIM,
                        'ids'=>['summaryRasioNIM','summaryPeringkatNIM','summaryPenilaianNIM']],
                    ];
                    foreach ($components as $c): ?>
                    <div class="col-12 col-md-4">
                        <div class="component-box">
                            <div class="component-header <?= $c['color'] ?>">
                                <i class="fas fa-percentage me-2"></i><?= $c['label'] ?>
                            </div>
                            <div class="component-body">
                                <div class="component-item">
                                    <span class="component-label">Rasio:</span>
                                    <span class="component-value <?= $c['textClass'] ?> fw-bold" id="<?= $c['ids'][0] ?>"><?= fmtRasio($c['rasio'], $c['rasioSet']) ?></span>
                                </div>
                                <div class="component-item">
                                    <span class="component-label">Peringkat:</span>
                                    <span class="component-value">
                                        <span class="badge badge-sm <?= getBadgeClass($c['peringkat']) ?>" id="<?= $c['ids'][1] ?>"><?= $c['peringkat'] > 0 ? $c['peringkat'] : '-' ?></span>
                                    </span>
                                </div>
                                <div class="component-item">
                                    <span class="component-label">Penilaian:</span>
                                    <span class="component-value fw-bold" id="<?= $c['ids'][2] ?>"><?= $c['penilaian'] ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Ringkasan Periode Sebelumnya -->
        <?php if ($peringkatRentabilitasBefore > 0): ?>
        <div class="card shadow-sm">
            <div class="card-header" style="background:linear-gradient(135deg,#6366f1 0%,#4f46e5 100%);">
                <h6 class="mb-0 text-white fw-bold">
                    <i class="fas fa-history me-2"></i> Ringkasan Penilaian Rentabilitas - Periode Sebelumnya
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
                            <div class="info-value text-warning" id="displayPeringkatBefore"><?= $peringkatRentabilitasBefore ?></div>
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
                <h6 class="fw-bold mb-3 text-secondary"><i class="fas fa-list-alt me-2"></i> Detail Komponen Penilaian</h6>
                <div class="row g-3">
                    <?php
                    $componentsBefore = [
                        ['label'=>' ROA',  'rasio'=>$rasioROABefore,  'rasioSet'=>$rasioROABeforeSet,  'peringkat'=>$peringkatROABefore,  'penilaian'=>$penilaianROABefore,
                        'ids'=>['summaryRasioROABefore','summaryPeringkatROABefore','summaryPenilaianROABefore']],
                        ['label'=>' BOPO', 'rasio'=>$rasioBOPOBefore, 'rasioSet'=>$rasioBOPOBeforeSet, 'peringkat'=>$peringkatBOPOBefore, 'penilaian'=>$penilaianBOPOBefore,
                        'ids'=>['summaryRasioBOPOBefore','summaryPeringkatBOPOBefore','summaryPenilaianBOPOBefore']],
                        ['label'=>' NIM',  'rasio'=>$rasioNIMBefore,  'rasioSet'=>$rasioNIMBeforeSet,  'peringkat'=>$peringkatNIMBefore,  'penilaian'=>$penilaianNIMBefore,
                        'ids'=>['summaryRasioNIMBefore','summaryPeringkatNIMBefore','summaryPenilaianNIMBefore']],
                    ];
                    foreach ($componentsBefore as $c): ?>
                    <div class="col-12 col-md-4">
                        <div class="component-box">
                            <div class="component-header" style="background:#6366f1;">
                                <i class="fas fa-percentage me-2"></i><?= $c['label'] ?>
                            </div>
                            <div class="component-body">
                                <div class="component-item">
                                    <span class="component-label">Rasio:</span>
                                    <span class="component-value fw-bold" style="color:#6366f1;" id="<?= $c['ids'][0] ?>"><?= fmtRasio($c['rasio'], $c['rasioSet']) ?></span>
                                </div>
                                <div class="component-item">
                                    <span class="component-label">Peringkat:</span>
                                    <span class="component-value">
                                        <span class="badge badge-sm <?= getBadgeClass($c['peringkat']) ?>" id="<?= $c['ids'][1] ?>"><?= $c['peringkat'] > 0 ? $c['peringkat'] : '-' ?></span>
                                    </span>
                                </div>
                                <div class="component-item">
                                    <span class="component-label">Penilaian:</span>
                                    <span class="component-value fw-bold" id="<?= $c['ids'][2] ?>"><?= $c['penilaian'] ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- 1. ROA -->
        <div class="card shadow-sm">
            <div class="card-header bg-gradient-success">
                <h6 class="mb-0 text-white fw-bold"><i class="fas fa-percentage me-2"></i> 1. Return on Asset (ROA)</h6>
            </div>
            <div class="card-body p-3 p-md-4">
                <div class="formula-box">
                    <h6 class="fw-bold mb-3">Rumus ROA:</h6>
                    <div class="formula-display">
                        ROA = <span class="formula-fraction">
                            <span class="formula-numerator">Laba Sebelum Pajak</span>
                            <span class="formula-denominator">Rata-Rata Total Aset</span>
                        </span> × 100%
                    </div>
                </div>
                <div class="row g-4">
                    <div class="col-12 col-lg-6">
                        <div class="period-box current-period">
                            <h6 class="period-title"><i class="fas fa-calendar-day me-2"></i> Periode Saat Ini</h6>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-bold">Laba/(Rugi) Sebelum Pajak (Rp)</label>
                                    <input type="text" class="form-control auto-save currency-input" data-field="labarugibelumpajak"
                                           value="<?= esc($r['labarugibelumpajak'] ?? '') ?>" placeholder="Masukkan laba/rugi sebelum pajak">
                                    <div class="saving-indicator"></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">Rata-rata Aset (Rp)</label>
                                    <input type="text" class="form-control auto-save currency-input" data-field="ratarataaset"
                                           value="<?= esc($r['ratarataaset'] ?? '') ?>" placeholder="Masukkan rata-rata aset">
                                    <div class="saving-indicator"></div>
                                </div>
                            </div>
                            <div class="result-box mt-3" id="resultROA">
                                <div class="result-item mb-2">
                                    <label><i class="fas fa-chart-line me-1"></i> Rasio ROA</label>
                                    <div class="result-value" id="displayRasioROA">
                                        <?php if ($rasioROASet): ?><span class="text-success"><?= number_format($rasioROA,2) ?>%</span>
                                        <?php else: ?><span class="text-muted">Belum dihitung</span><?php endif; ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12 text-center">
                                        <div class="result-item">
                                            <label><i class="fas fa-award me-1"></i> Peringkat</label>
                                            <div class="result-value" id="displayPeringkatROA">
                                                <?= $peringkatROA > 0 ? '<span class="text-primary">'.$peringkatROA.'</span>' : '<span class="text-muted">-</span>' ?>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- <div class="col-6">
                                        <div class="result-item">
                                            <label><i class="fas fa-star me-1"></i> Penilaian</label>
                                            <div class="mt-1"><span class="badge <?= getBadgeClass($peringkatROA) ?> badge-sm" id="displayPenilaianROA"><?= $penilaianROA ?></span></div>
                                        </div>
                                    </div> -->
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="period-box previous-period">
                            <h6 class="period-title"><i class="fas fa-history me-2"></i> Periode Sebelumnya</h6>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-bold">Laba/(Rugi) Sebelum Pajak (Rp)</label>
                                    <input type="text" class="form-control auto-save currency-input" data-field="lababelumpajakbefore"
                                           value="<?= esc($r['lababelumpajakbefore'] ?? '') ?>" placeholder="Masukkan laba/rugi sebelum pajak">
                                    <div class="saving-indicator"></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">Rata-rata Aset (Rp)</label>
                                    <input type="text" class="form-control auto-save currency-input" data-field="ratarataasetbefore"
                                           value="<?= esc($r['ratarataasetbefore'] ?? '') ?>" placeholder="Masukkan rata-rata aset">
                                    <div class="saving-indicator"></div>
                                </div>
                            </div>
                            <div class="result-box mt-3" id="resultROABefore" style="background:linear-gradient(135deg,#eef2ff 0%,#e0e7ff 100%);border-color:#6366f1;">
                                <div class="result-item mb-2">
                                    <label><i class="fas fa-chart-line me-1"></i> Rasio ROA</label>
                                    <div class="result-value" id="displayRasioROABefore">
                                        <?php if ($rasioROABefore > 0): ?><span style="color:#6366f1;"><?= number_format($rasioROABefore,2) ?>%</span>
                                        <?php else: ?><span class="text-muted">Belum dihitung</span><?php endif; ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12 text-center">
                                        <div class="result-item">
                                            <label><i class="fas fa-award me-1"></i> Peringkat</label>
                                            <div class="result-value" id="displayPeringkatROABefore">
                                                <?= $peringkatROABefore > 0 ? '<span class="text-primary">'.$peringkatROABefore.'</span>' : '<span class="text-muted">-</span>' ?>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- <div class="col-6">
                                        <div class="result-item">
                                            <label><i class="fas fa-star me-1"></i> Penilaian</label>
                                            <div class="mt-1"><span class="badge <?= getBadgeClass($peringkatROABefore) ?> badge-sm" id="displayPenilaianROABefore"><?= $penilaianROABefore ?></span></div>
                                        </div>
                                    </div> -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="peringkat-info mt-4">
                    <h6 class="fw-bold mb-3 text-center">Kriteria Peringkat ROA:</h6>
                    <div class="row g-2 justify-content-center text-center">
                        <div class="col-6 col-md-4 col-lg-2"><span class="badge badge-success w-100 py-3"><div class="fw-bold">Peringkat 1</div><div class="small-bold mt-2">ROA ≥ 2%</div></span></div>
                        <div class="col-6 col-md-4 col-lg-2"><span class="badge badge-info w-100 py-3"><div class="fw-bold">Peringkat 2</div><div class="small-bold mt-2">2% > ROA ≥ 1.5%</div></span></div>
                        <div class="col-6 col-md-4 col-lg-2"><span class="badge badge-warning w-100 py-3"><div class="fw-bold">Peringkat 3</div><div class="small-bold mt-2">1.5% > ROA ≥ 1%</div></span></div>
                        <div class="col-6 col-md-4 col-lg-2"><span class="badge badge-orange w-100 py-3"><div class="fw-bold">Peringkat 4</div><div class="small-bold mt-2">1% > ROA ≥ 0.5%</div></span></div>
                        <div class="col-6 col-md-4 col-lg-2"><span class="badge badge-danger w-100 py-3"><div class="fw-bold">Peringkat 5</div><div class="small-bold mt-2">0.5% > ROA</div></span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. BOPO -->
        <div class="card shadow-sm">
            <div class="card-header bg-gradient-warning">
                <h6 class="mb-0 text-white fw-bold"><i class="fas fa-chart-bar me-2"></i> 2. BOPO (Beban Operasional terhadap Pendapatan Operasional)</h6>
            </div>
            <div class="card-body p-3 p-md-4">
                <div class="formula-box">
                    <h6 class="fw-bold mb-3">Rumus BOPO:</h6>
                    <div class="formula-display">
                        BOPO = <span class="formula-fraction">
                            <span class="formula-numerator">Beban Operasional</span>
                            <span class="formula-denominator">Pendapatan Operasional</span>
                        </span> × 100%
                    </div>
                </div>
                <div class="row g-4">
                    <div class="col-12 col-lg-6">
                        <div class="period-box current-period">
                            <h6 class="period-title"><i class="fas fa-calendar-day me-2"></i> Periode Saat Ini</h6>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-bold">Beban Operasional (Rp)</label>
                                    <input type="text" class="form-control auto-save currency-input" data-field="bebanoperasional"
                                           value="<?= esc($r['bebanoperasional'] ?? '') ?>" placeholder="Masukkan beban operasional">
                                    <div class="saving-indicator"></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">Pendapatan Operasional (Rp)</label>
                                    <input type="text" class="form-control auto-save currency-input" data-field="pendapatanoperasional"
                                           value="<?= esc($r['pendapatanoperasional'] ?? '') ?>" placeholder="Masukkan pendapatan operasional">
                                    <div class="saving-indicator"></div>
                                </div>
                            </div>
                            <div class="result-box mt-3" id="resultBOPO">
                                <div class="result-item mb-2">
                                    <label><i class="fas fa-percentage me-1"></i> Rasio BOPO</label>
                                    <div class="result-value" id="displayRasioBOPO">
                                        <?php if ($rasioBOPO > 0): ?><span class="text-warning"><?= number_format($rasioBOPO,2) ?>%</span>
                                        <?php else: ?><span class="text-muted">Belum dihitung</span><?php endif; ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12 text-center">
                                        <div class="result-item">
                                            <label><i class="fas fa-award me-1"></i> Peringkat</label>
                                            <div class="result-value" id="displayPeringkatBOPO">
                                                <?= $peringkatBOPO > 0 ? '<span class="text-primary">'.$peringkatBOPO.'</span>' : '<span class="text-muted">-</span>' ?>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- <div class="col-6">
                                        <div class="result-item">
                                            <label><i class="fas fa-star me-1"></i> Penilaian</label>
                                            <div class="mt-1"><span class="badge <?= getBadgeClass($peringkatBOPO) ?> badge-sm" id="displayPenilaianBOPO"><?= $penilaianBOPO ?></span></div>
                                        </div>
                                    </div> -->
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="period-box previous-period">
                            <h6 class="period-title"><i class="fas fa-history me-2"></i> Periode Sebelumnya</h6>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-bold">Beban Operasional (Rp)</label>
                                    <input type="text" class="form-control auto-save currency-input" data-field="bebanoperasionalbefore"
                                           value="<?= esc($r['bebanoperasionalbefore'] ?? '') ?>" placeholder="Masukkan beban operasional">
                                    <div class="saving-indicator"></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">Pendapatan Operasional (Rp)</label>
                                    <input type="text" class="form-control auto-save currency-input" data-field="pendapatanoperasionalbefore"
                                           value="<?= esc($r['pendapatanoperasionalbefore'] ?? '') ?>" placeholder="Masukkan pendapatan operasional">
                                    <div class="saving-indicator"></div>
                                </div>
                            </div>
                            <div class="result-box mt-3" id="resultBOPOBefore" style="background:linear-gradient(135deg,#eef2ff 0%,#e0e7ff 100%);border-color:#6366f1;">
                                <div class="result-item mb-2">
                                    <label><i class="fas fa-percentage me-1"></i> Rasio BOPO</label>
                                    <div class="result-value" id="displayRasioBOPOBefore">
                                        <?php if ($rasioBOPOBefore > 0): ?><span style="color:#6366f1;"><?= number_format($rasioBOPOBefore,2) ?>%</span>
                                        <?php else: ?><span class="text-muted">Belum dihitung</span><?php endif; ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12 text-center">
                                        <div class="result-item">
                                            <label><i class="fas fa-award me-1"></i> Peringkat</label>
                                            <div class="result-value" id="displayPeringkatBOPOBefore">
                                                <?= $peringkatBOPOBefore > 0 ? '<span class="text-primary">'.$peringkatBOPOBefore.'</span>' : '<span class="text-muted">-</span>' ?>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- <div class="col-6">
                                        <div class="result-item">
                                            <label><i class="fas fa-star me-1"></i> Penilaian</label>
                                            <div class="mt-1"><span class="badge <?= getBadgeClass($peringkatBOPOBefore) ?> badge-sm" id="displayPenilaianBOPOBefore"><?= $penilaianBOPOBefore ?></span></div>
                                        </div>
                                    </div> -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="peringkat-info mt-4">
                    <h6 class="fw-bold mb-3 text-center">Kriteria Peringkat BOPO:</h6>
                    <div class="row g-2 justify-content-center text-center">
                        <div class="col-6 col-md-4 col-lg-2"><span class="badge badge-success w-100 py-3"><div class="fw-bold">Peringkat 1</div><div class="small-bold mt-2">85% ≥ BOPO</div></span></div>
                        <div class="col-6 col-md-4 col-lg-2"><span class="badge badge-info w-100 py-3"><div class="fw-bold">Peringkat 2</div><div class="small-bold mt-2">90% ≥ BOPO > 85%</div></span></div>
                        <div class="col-6 col-md-4 col-lg-2"><span class="badge badge-warning w-100 py-3"><div class="fw-bold">Peringkat 3</div><div class="small-bold mt-2">95% ≥ BOPO > 90%</div></span></div>
                        <div class="col-6 col-md-4 col-lg-2"><span class="badge badge-orange w-100 py-3"><div class="fw-bold">Peringkat 4</div><div class="small-bold mt-2">100% ≥ BOPO > 95%</div></span></div>
                        <div class="col-6 col-md-4 col-lg-2"><span class="badge badge-danger w-100 py-3"><div class="fw-bold">Peringkat 5</div><div class="small-bold mt-2">BOPO > 100%</div></span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. NIM -->
        <div class="card shadow-sm">
            <div class="card-header bg-gradient-info">
                <h6 class="mb-0 text-white fw-bold"><i class="fas fa-chart-line me-2"></i> 3. Net Interest Margin (NIM)</h6>
            </div>
            <div class="card-body p-3 p-md-4">
                <div class="formula-box">
                    <h6 class="fw-bold mb-3">Rumus NIM:</h6>
                    <div class="formula-display">
                        NIM = <span class="formula-fraction">
                            <span class="formula-numerator">Pendapatan Bunga Bersih</span>
                            <span class="formula-denominator">Rata-Rata Aset Produktif</span>
                        </span> × 100%
                    </div>
                </div>
                <div class="row g-4">
                    <div class="col-12 col-lg-6">
                        <div class="period-box current-period">
                            <h6 class="period-title"><i class="fas fa-calendar-day me-2"></i> Periode Saat Ini</h6>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-bold">Pendapatan Bunga (Rp)</label>
                                    <input type="text" class="form-control auto-save currency-input" data-field="pendapatanbunga"
                                           value="<?= esc($r['pendapatanbunga'] ?? '') ?>" placeholder="Masukkan pendapatan bunga">
                                    <div class="saving-indicator"></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">Beban Bunga (Rp)</label>
                                    <input type="text" class="form-control auto-save currency-input" data-field="bebanbunga"
                                           value="<?= esc($r['bebanbunga'] ?? '') ?>" placeholder="Masukkan beban bunga">
                                    <div class="saving-indicator"></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">Rata-rata Aset Produktif (Rp)</label>
                                    <input type="text" class="form-control auto-save currency-input" data-field="ratarataasetproduktif"
                                           value="<?= esc($r['ratarataasetproduktif'] ?? '') ?>" placeholder="Masukkan rata-rata aset produktif">
                                    <div class="saving-indicator"></div>
                                </div>
                            </div>
                            <div class="result-box mt-3" id="resultNIM">
                                <div class="result-item mb-2">
                                    <label><i class="fas fa-chart-bar me-1"></i> Rasio NIM</label>
                                    <div class="result-value" id="displayRasioNIM">
                                        <?php if ($rasioNIM > 0): ?><span class="text-info"><?= number_format($rasioNIM,2) ?>%</span>
                                        <?php else: ?><span class="text-muted">Belum dihitung</span><?php endif; ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12 text-center">
                                        <div class="result-item">
                                            <label><i class="fas fa-trophy me-1"></i> Peringkat</label>
                                            <div class="result-value" id="displayPeringkatNIM">
                                                <?= $peringkatNIM > 0 ? '<span class="text-primary">'.$peringkatNIM.'</span>' : '<span class="text-muted">-</span>' ?>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- <div class="col-6">
                                        <div class="result-item">
                                            <label><i class="fas fa-star me-1"></i> Penilaian</label>
                                            <div class="mt-1"><span class="badge <?= getBadgeClass($peringkatNIM) ?> badge-sm" id="displayPenilaianNIM"><?= $penilaianNIM ?></span></div>
                                        </div>
                                    </div> -->
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="period-box previous-period">
                            <h6 class="period-title"><i class="fas fa-history me-2"></i> Periode Sebelumnya</h6>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-bold">Pendapatan Bunga (Rp)</label>
                                    <input type="text" class="form-control auto-save currency-input" data-field="pendapatanbungabefore"
                                           value="<?= esc($r['pendapatanbungabefore'] ?? '') ?>" placeholder="Masukkan pendapatan bunga">
                                    <div class="saving-indicator"></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">Beban Bunga (Rp)</label>
                                    <input type="text" class="form-control auto-save currency-input" data-field="bebanbungabefore"
                                           value="<?= esc($r['bebanbungabefore'] ?? '') ?>" placeholder="Masukkan beban bunga">
                                    <div class="saving-indicator"></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">Rata-rata Aset Produktif (Rp)</label>
                                    <input type="text" class="form-control auto-save currency-input" data-field="ratarataasetprodbefore"
                                           value="<?= esc($r['ratarataasetprodbefore'] ?? '') ?>" placeholder="Masukkan rata-rata aset produktif">
                                    <div class="saving-indicator"></div>
                                </div>
                            </div>
                            <div class="result-box mt-3" id="resultNIMBefore" style="background:linear-gradient(135deg,#eef2ff 0%,#e0e7ff 100%);border-color:#6366f1;">
                                <div class="result-item mb-2">
                                    <label><i class="fas fa-chart-bar me-1"></i> Rasio NIM</label>
                                    <div class="result-value" id="displayRasioNIMBefore">
                                        <?php if ($rasioNIMBefore > 0): ?><span style="color:#6366f1;"><?= number_format($rasioNIMBefore,2) ?>%</span>
                                        <?php else: ?><span class="text-muted">Belum dihitung</span><?php endif; ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12 text-center">
                                        <div class="result-item">
                                            <label><i class="fas fa-trophy me-1"></i> Peringkat</label>
                                            <div class="result-value" id="displayPeringkatNIMBefore">
                                                <?= $peringkatNIMBefore > 0 ? '<span class="text-primary">'.$peringkatNIMBefore.'</span>' : '<span class="text-muted">-</span>' ?>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- <div class="col-6">
                                        <div class="result-item">
                                            <label><i class="fas fa-star me-1"></i> Penilaian</label>
                                            <div class="mt-1"><span class="badge <?= getBadgeClass($peringkatNIMBefore) ?> badge-sm" id="displayPenilaianNIMBefore"><?= $penilaianNIMBefore ?></span></div>
                                        </div>
                                    </div> -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="peringkat-info mt-4">
                    <h6 class="fw-bold mb-3 text-center">Kriteria Peringkat NIM:</h6>
                    <div class="row g-2 justify-content-center text-center">
                        <div class="col-6 col-md-4 col-lg-2"><span class="badge badge-success w-100 py-3"><div class="fw-bold">Peringkat 1</div><div class="small-bold mt-2">NIM ≥ 10%</div></span></div>
                        <div class="col-6 col-md-4 col-lg-2"><span class="badge badge-info w-100 py-3"><div class="fw-bold">Peringkat 2</div><div class="small-bold mt-2">10% > NIM ≥ 8%</div></span></div>
                        <div class="col-6 col-md-4 col-lg-2"><span class="badge badge-warning w-100 py-3"><div class="fw-bold">Peringkat 3</div><div class="small-bold mt-2">8% > NIM ≥ 6%</div></span></div>
                        <div class="col-6 col-md-4 col-lg-2"><span class="badge badge-orange w-100 py-3"><div class="fw-bold">Peringkat 4</div><div class="small-bold mt-2">6% > NIM ≥ 4%</div></span></div>
                        <div class="col-6 col-md-4 col-lg-2"><span class="badge badge-danger w-100 py-3"><div class="fw-bold">Peringkat 5</div><div class="small-bold mt-2">4% > NIM</div></span></div>
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
                <textarea class="form-control auto-save char-limited" data-field="deskripsirentabilitas" rows="5"
                        maxlength="2000"
                        placeholder="Masukkan deskripsi peringkat rentabilitas..."><?= esc($r['deskripsirentabilitas'] ?? '') ?></textarea>
                <div class="d-flex justify-content-between align-items-center mt-1">
                    <div class="saving-indicator"></div>
                    <small class="char-counter text-muted">0 / 2000</small>
                </div>
            </div>
        </div>

        <!-- Analisa -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-bold"><i class="fas fa-chart-line text-primary"></i> Analisa / Penjelasan</h6>
            </div>
            <div class="card-body p-3 p-md-4">
                <textarea class="form-control auto-save char-limited" data-field="analisarentabilitas" rows="6"
                        maxlength="2000"
                        placeholder="Masukkan analisa komprehensif mengenai rentabilitas BPR..."><?= esc($r['analisarentabilitas'] ?? '') ?></textarea>
                <div class="d-flex justify-content-between align-items-center mt-1">
                    <div class="saving-indicator"></div>
                    <small class="char-counter text-muted">0 / 2000</small>
                </div>
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
                        <a href="<?= base_url('Tksrentabilitas/approve') ?>"
                           class="btn approval-btn approval-btn-approve <?= !$canApprove || $isApproved ? 'disabled-btn' : '' ?>"
                           onclick="return <?= $canApprove && !$isApproved ? "confirm('Apakah Anda yakin ingin menyetujui data ini?')" : 'false' ?>;">
                            <i class="fas fa-check-circle"></i> Approve
                        </a>
                        <a href="<?= base_url('Tksrentabilitas/unapprove') ?>"
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
                    <h5 class="modal-title" id="modalKomentarLabel"><i class="fas fa-comments"></i> Komentar Direksi dan Dewan Komisaris</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="fw-bold">Riwayat Komentar:</label>
                        <div id="komentarList" style="max-height:300px;overflow-y:auto;border:1px solid #dee2e6;border-radius:8px;padding:15px;background:#f8f9fa;">
                            <div class="text-center text-muted"><i class="fas fa-spinner fa-spin"></i> Memuat komentar...</div>
                        </div>
                    </div>
                    <?php if (($userInGroupAdmin??false)||($userInGroupDekom??false)||($userInGroupDireksi??false)||($userInGroupPE??false)): ?>
                    <form id="formKomentar">
                        <?= csrf_field() ?>
                        <input type="hidden" name="fullname" value="<?= esc($fullname ?? '') ?>">
                        <div class="mb-3">
                            <label for="komentar" class="fw-bold">Tambahkan Komentar Baru:</label>
                            <textarea class="form-control" name="komentar" id="komentar" rows="4" placeholder="Tulis komentar Anda di sini..."></textarea>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Kirim Komentar</button>
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

<!-- Modal Sinkronisasi -->
<div class="modal fade" id="modalSyncPeriode" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;border:none;">
            <div class="modal-header" style="background:linear-gradient(135deg,#0f766e 0%,#0d9488 100%);color:white;border:none;">
                <h5 class="modal-title"><i class="fas fa-sync-alt"></i> Sinkronisasi Data Periode Sebelumnya</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" id="btn-close-sync-modal"></button>
            </div>
            <div class="modal-body" style="padding:22px 24px;">
                <div class="sync-tks-info-box">
                    <div class="sync-tks-periode">
                        <span class="sync-tks-label">Periode Aktif</span>
                        <span class="sync-tks-val" id="sync-current-label">—</span>
                    </div>
                    <div style="font-size:20px;color:#0d9488;"><i class="fas fa-arrow-up"></i></div>
                    <div class="sync-tks-periode">
                        <span class="sync-tks-label">Sumber Data</span>
                        <span class="sync-tks-val" id="sync-prev-label">—</span>
                    </div>
                </div>
                <div id="sync-preview-area" style="margin-top:14px;">
                    <div style="display:inline-flex;align-items:center;gap:8px;background:#d1fae5;color:#065f46;font-size:13px;font-weight:700;padding:6px 14px;border-radius:100px;border:1.5px solid #a7f3d0;margin-bottom:10px;">
                        <i class="fas fa-chart-line"></i> Data input dari periode sebelumnya akan disalin sebagai referensi
                    </div>
                    <div id="sync-preview-fields" style="background:white;border:1.5px solid #e2e8f0;border-radius:10px;padding:12px;font-size:12.5px;"></div>
                </div>

                <!-- RESULT AREA: tampil di dalam modal setelah proses selesai -->
                <div id="sync-result-area" style="display:none;margin-top:14px;"></div>

                <div class="alert alert-warning mb-0 mt-3" id="sync-warning-area" style="font-size:12.5px;border-radius:10px;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Perhatian:</strong>
                    <ul class="mb-0 mt-1" style="padding-left:16px;">
                        <li>Sinkronisasi hanya bisa dilakukan jika periode aktif <strong>belum memiliki data</strong>.</li>
                        <li>Data periode sebelumnya akan disalin ke kolom <strong>"Periode Sebelumnya"</strong> pada form.</li>
                        <li>Anda tetap harus mengisi data <strong>"Periode Saat Ini"</strong> secara manual.</li>
                        <li>Status approval akan direset.</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #e2e8f0;padding:12px 24px;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="btn-batal-sync">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="button" id="btn-konfirmasi-sync" class="btn"
                        style="background:linear-gradient(135deg,#0f766e,#0d9488);color:white;font-weight:700;border:none;">
                    <i class="fas fa-sync-alt"></i> Sinkronkan Sekarang
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTarikMisTks" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;border:none;">
            <div class="modal-header" style="background:linear-gradient(135deg,#7c3aed 0%,#6d28d9 100%);color:white;border:none;">
                <h5 class="modal-title"><i class="fas fa-database"></i> Tarik Data ROA, BOPO, NIM dari MIS TKS</h5>                
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

                    <!-- ROA -->
                    <div class="mistks-group-header" style="background:#ecfdf5;color:#065f46;">
                        <i class="fas fa-percentage me-2"></i> ROA
                    </div>
                    <div id="mistks-preview-roa" class="mistks-preview-box"></div>

                    <!-- BOPO -->
                    <div class="mistks-group-header" style="background:#fffbeb;color:#92400e;">
                        <i class="fas fa-chart-bar me-2"></i> BOPO
                    </div>
                    <div id="mistks-preview-bopo" class="mistks-preview-box"></div>

                    <!-- NIM -->
                    <div class="mistks-group-header" style="background:#ecfeff;color:#155e75;">
                        <i class="fas fa-chart-line me-2"></i> NIM
                    </div>
                    <div id="mistks-preview-nim" class="mistks-preview-box"></div>

                </div>

                <div id="mistks-result-area" style="display:none;margin-top:14px;"></div>

                <div class="alert alert-warning mb-0 mt-3" id="mistks-warning-area" style="font-size:12.5px;border-radius:10px;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Perhatian:</strong> Field yang ditemukan di mis_tks akan <strong>menimpa</strong> nilai pada form ROA, BOPO, dan NIM (periode saat ini & sebelumnya), lalu seluruh rasio terkait dihitung ulang otomatis. Field yang tidak ditemukan di mis_tks tidak akan diubah.
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

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

<style>
body { background-color:#f8f9fa; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; }
.main-content { max-width:1400px; margin:0 auto; }

.card { border-radius:12px; border:none; box-shadow:0 2px 8px rgba(0,0,0,.08); margin-bottom:1.5rem; }
.card-header { border-bottom:1px solid #e5e7eb; padding:1rem 1.25rem; border-radius:12px 12px 0 0 !important; }
.bg-gradient-primary { background:linear-gradient(135deg,#3b82f6 0%,#2563eb 100%); }
.bg-gradient-success { background:linear-gradient(135deg,#10b981 0%,#059669 100%); }
.bg-gradient-warning { background:linear-gradient(135deg,#f59e0b 0%,#d97706 100%); }
.bg-gradient-info    { background:linear-gradient(135deg,#06b6d4 0%,#0891b2 100%); }

.form-control { border:2px solid #e5e7eb; border-radius:8px; padding:.75rem; font-size:15px; transition:border-color .2s,box-shadow .2s; }
.form-control:focus { border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,.1); }

.formula-box { background:linear-gradient(135deg,#f0f9ff 0%,#e0f2fe 100%); border:2px solid #3b82f6; border-radius:12px; padding:1.5rem; margin-bottom:1.5rem; }
.formula-display { font-size:1.25rem; font-weight:600; text-align:center; color:#1e40af; }
.formula-fraction { display:inline-flex; flex-direction:column; vertical-align:middle; text-align:center; margin:0 .5rem; }
.formula-numerator,.formula-denominator { padding:.25rem .5rem; }
.formula-numerator { border-bottom:2px solid #1e40af; }

.period-box      { border:2px solid #e5e7eb; border-radius:12px; padding:1.5rem; background:#fff; height:100%; }
.current-period  { border-left:4px solid #10b981; background:linear-gradient(135deg,#fff 0%,#f0fdf4 100%); }
.previous-period { border-left:4px solid #6366f1; background:linear-gradient(135deg,#fff 0%,#eef2ff 100%); }
.period-title    { font-weight:700; font-size:1rem; margin-bottom:1rem; padding-bottom:.75rem; border-bottom:2px solid #e5e7eb; }
.current-period  .period-title { color:#059669; }
.previous-period .period-title { color:#4f46e5; }

.info-box   { background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.08); text-align:center; padding:1.5rem; }
.info-label { font-size:.9rem; color:#6b7280; margin-bottom:.5rem; font-weight:500; }
.info-value { font-size:2rem; font-weight:700; color:#1f2937; }

.result-box { background:linear-gradient(135deg,#ecfdf5 0%,#d1fae5 100%); border:2px solid #10b981; border-radius:12px; padding:1.5rem; margin-top:1.5rem; }
.result-item { text-align:center; }
.result-item label { font-size:.9rem; color:#6b7280; margin-bottom:.5rem; font-weight:600; display:block; }
.result-value { font-size:1.75rem; font-weight:700; color:#065f46; }
.result-value .text-muted { font-size:1.2rem; font-weight:500; }

.component-box    { background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,.08); height:100%; }
.component-header { padding:.75rem; color:#fff; font-weight:600; font-size:.95rem; text-align:center; }
.component-body   { padding:1rem; }
.component-item   { display:flex; justify-content:space-between; align-items:center; padding:.5rem 0; border-bottom:1px solid #f3f4f6; }
.component-item:last-child { border-bottom:none; }
.component-label  { font-size:.875rem; color:#6b7280; font-weight:500; }
.component-value  { font-size:.95rem; color:#1f2937; font-weight:600; }

.peringkat-info { background:#f9fafb; border-radius:8px; padding:1rem; margin-top:1.5rem; }
.small-bold { font-size:.85rem; font-weight:600; }

.badge          { padding:.5rem 1rem; font-size:.875rem; font-weight:600; border-radius:6px; }
.badge-sm       { padding:.375rem .75rem; font-size:.8rem; }
.badge-xl       { padding:.75rem 1.5rem; font-size:1.1rem; font-weight:700; }
.badge-success  { background-color:#10b981; color:#fff; }
.badge-info     { background-color:#06b6d4; color:#fff; }
.badge-warning  { background-color:#f59e0b; color:#fff; }
.badge-orange   { background-color:#f97316; color:#fff; }
.badge-danger   { background-color:#ef4444; color:#fff; }
.badge-secondary{ background-color:#6b7280; color:#fff; }

.saving-indicator { margin-top:.5rem; min-height:24px; }

.custom-toast {
    position:fixed; top:24px; right:24px;
    z-index:99999;
    padding:16px 24px; border-radius:12px; color:#fff;
    display:flex; align-items:center; min-width:300px;
    box-shadow:0 10px 30px rgba(0,0,0,.3);
    transform:translateX(420px); opacity:0;
    transition:transform .4s ease, opacity .4s ease;
    font-weight:500;
}
.custom-toast.show  { transform:translateX(0); opacity:1; }
.custom-toast.toast-success { background:linear-gradient(135deg,#10b981,#059669); }
.custom-toast.toast-error   { background:linear-gradient(135deg,#ef4444,#dc2626); }

.btn { padding:.75rem 1.5rem; border-radius:8px; font-weight:500; transition:all .3s; }
.btn-outline-secondary { border:2px solid #6b7280; color:#6b7280; }
.btn-outline-secondary:hover { background-color:#6b7280; color:#fff; }

.approvaldir-card       { border-radius:12px; border:2px solid #e5e7eb; }
.approvaldir-card-body  { padding:1.5rem; }
.approval-badge-container { text-align:center; margin-bottom:1rem; }
.approval-badge         { background:#343a40; color:#fff; font-size:.9rem; font-weight:600; padding:8px 16px; border-radius:6px; display:inline-block; }
.approval-buttons-container { display:flex; justify-content:center; align-items:center; gap:12px; }
.approval-btn           { flex:1; max-width:48%; padding:10px 16px; font-size:.95rem; font-weight:500; border-radius:8px; text-align:center; text-decoration:none; display:flex; align-items:center; justify-content:center; gap:6px; transition:all .3s; }
.approval-btn-approve   { background:#28a745; border-color:#28a745; color:#fff; }
.approval-btn-approve:hover:not(.disabled-btn) { background:#218838; transform:translateY(-1px); color:#fff; }
.approval-btn-reject    { background:#dc3545; border-color:#dc3545; color:#fff; }
.approval-btn-reject:hover:not(.disabled-btn)  { background:#c82333; transform:translateY(-1px); color:#fff; }
.disabled-btn           { opacity:.5; pointer-events:none; }

.notification-badge { position:absolute; top:-8px; right:-8px; font-size:11px; padding:3px 7px; border-radius:50%; background:#dc3545; color:#fff; font-weight:bold; min-width:20px; text-align:center; }
.komentar-button    { position:relative; }
.komentar-item      { transition:transform .2s ease; }
.komentar-item:hover{ transform:translateX(5px); }
#approval-badge-main { transition:all .3s ease; }

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
.mistks-preview-box:empty::before {
    content:'Memuat...'; color:#94a3b8; font-style:italic;
}

/* Sync tooltip */
#btn-sync-periode:disabled { opacity:.45; cursor:not-allowed; }
#btn-sync-periode.syncing .fas { animation:tks-spin 1s linear infinite; }
@keyframes tks-spin { to { transform:rotate(360deg); } }

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
#btn-sync-periode:hover + .sync-tooltip-tks,
#btn-sync-periode:focus + .sync-tooltip-tks { opacity:1; }

/* Notification badge */
.notification-badge {
    position:absolute; top:-6px; right:-6px;
    font-size:10px; padding:2px 6px;
    border-radius:50%; background:#dc3545;
    color:#fff; font-weight:bold;
    min-width:18px; text-align:center;
    pointer-events:none;
}

/* Toast z-index di atas modal */
.custom-toast { z-index:99999; }

.sync-tks-info-box {
    display:flex; align-items:center; justify-content:center;
    gap:16px; background:#f0fdfa; border:1.5px solid #99f6e4; border-radius:12px; padding:14px 18px;
}
.sync-tks-periode { display:flex; flex-direction:column; align-items:center; gap:3px; flex:1; text-align:center; }
.sync-tks-label   { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.7px; color:#5eead4; }
.sync-tks-val     { font-size:14px; font-weight:800; color:#0f766e; }
.sync-preview-row { display:flex; justify-content:space-between; padding:5px 0; border-bottom:1px solid #f1f5f9; font-size:12px; }
.sync-preview-row:last-child { border-bottom:none; }
.sync-preview-label { color:#64748b; font-weight:600; }
.sync-preview-value { color:#1e293b; font-weight:700; }

@media (max-width:768px) {
    .card-body      { padding:1rem !important; }
    .period-box     { padding:1rem; margin-bottom:1rem; }
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

<script>
(function ($) {
    'use strict';

    const BASE_URL  = '<?= base_url() ?>';
    const CSRF_NAME = '<?= csrf_token() ?>';
    const BADGE_MAP = { '1':'badge-success','2':'badge-info','3':'badge-warning','4':'badge-orange','5':'badge-danger' };
    const LABEL_MAP = { '1':'Sangat Baik','2':'Baik','3':'Cukup','4':'Kurang Baik','5':'Buruk' };

    let csrfHash = '<?= csrf_hash() ?>';
    let saveTimers = {};
    let pollTimer = null;
    let komentarLoading = false;
    let lastReadTime = new Date().toISOString();

    const escEl = (function () {
        const d = document.createElement('div');
        return function (str) { d.textContent = str; return d.innerHTML; };
    }());

    function getCsrfData() { const o = {}; o[CSRF_NAME] = csrfHash; return o; }
    function badgeClass(p) { return BADGE_MAP[String(p)] || 'badge-secondary'; }
    function rankLabel(p)  { return LABEL_MAP[String(p)] || 'Belum Ada Data'; }

    /* Currency */
    function formatRupiah(raw) {
        if (!raw && raw !== 0) return '';
        const neg   = String(raw).charAt(0) === '-';
        const clean = String(raw).replace(/[^0-9]/g, '');
        const grouped = clean.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        return (neg ? '-' : '') + grouped;
    }
    function unformatRupiah(formatted) {
        if (!formatted) return '';
        return String(formatted).replace(/\./g, '');
    }

    /* Toast */
    function showToast(type, message) {
        $('.custom-toast').remove();
        const $t = $('<div class="custom-toast"></div>')
            .addClass(type === 'success' ? 'toast-success' : 'toast-error')
            .html('<i class="fas ' + (type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle') + ' me-2"></i>' + escEl(message));
        $('body').append($t);
        requestAnimationFrame(function () {
            requestAnimationFrame(function () { $t.addClass('show'); });
        });
        setTimeout(function () { $t.removeClass('show'); setTimeout(function () { $t.remove(); }, 400); }, 4000);
    }

    /* Notification badge */
    function updateBadge(count) {
        const $b = $('#notification-badge');
        $b.text(count);
        count > 0 ? $b.fadeIn(200) : $b.fadeOut(200);
    }

    /* Polling */
    function pollUnreadCount() {
        $.get(BASE_URL + '/Tksrentabilitas/getUnreadCount', function (r) {
            if (r.status === 'success') updateBadge(r.count);
        }, 'json').fail(function () {});
    }
    function startPolling() { pollUnreadCount(); pollTimer = setInterval(pollUnreadCount, 30000); }
    function stopPolling()  { clearInterval(pollTimer); }
    document.addEventListener('visibilitychange', function () {
        document.hidden ? stopPolling() : startPolling();
    });

    /* updateDisplay */
    function updateDisplay(d) {
        if (!d) return;

        function setRatio(id, val, colorClass) {
            const v = parseFloat(val);
            if (isNaN(v)) return;
            $('#' + id).html('<span class="' + colorClass + '">' + v.toFixed(2) + '%</span>');
        }
        function setPeringkat(displayId, summaryId, val) {
            if (!val || val <= 0) return;
            const p = String(val);
            $('#' + displayId).html('<span class="text-primary">' + val + '</span>');
            if (summaryId) $('#' + summaryId).text(val).attr('class', 'badge badge-sm ' + badgeClass(p));
        }
        function setPenilaian(displayId, summaryId, val, p) {
            if (!val) return;
            const cls = 'badge badge-sm ' + badgeClass(String(p));
            $('#' + displayId).text(val).attr('class', cls);
            if (summaryId) $('#' + summaryId).text(val);
        }

        if (d.rasioroa != null) { setRatio('displayRasioROA',  d.rasioroa,  'text-success'); $('#summaryRasioROA').text(parseFloat(d.rasioroa).toFixed(2)+'%'); }
        if (d.peringkatroa != null) setPeringkat('displayPeringkatROA',  'summaryPeringkatROA',  d.peringkatroa);
        if (d.penilaianroa != null) setPenilaian('displayPenilaianROA',  'summaryPenilaianROA',  d.penilaianroa,  d.peringkatroa);

        if (d.rasiobopo != null) { setRatio('displayRasioBOPO', d.rasiobopo, 'text-warning'); $('#summaryRasioBOPO').text(parseFloat(d.rasiobopo).toFixed(2)+'%'); }
        if (d.peringkatbopo != null) setPeringkat('displayPeringkatBOPO', 'summaryPeringkatBOPO', d.peringkatbopo);
        if (d.penilaianbopo != null) setPenilaian('displayPenilaianBOPO', 'summaryPenilaianBOPO', d.penilaianbopo, d.peringkatbopo);

        if (d.rasionim != null) { setRatio('displayRasioNIM',  d.rasionim,  'text-info');    $('#summaryRasioNIM').text(parseFloat(d.rasionim).toFixed(2)+'%'); }
        if (d.peringkatnim != null) setPeringkat('displayPeringkatNIM',  'summaryPeringkatNIM',  d.peringkatnim);
        if (d.penilaiannim != null) setPenilaian('displayPenilaianNIM',  'summaryPenilaianNIM',  d.penilaiannim,  d.peringkatnim);

        if (d.rasioroabefore != null) { setRatio('displayRasioROABefore',  d.rasioroabefore,  ''); $('#displayRasioROABefore span').css('color','#6366f1'); $('#summaryRasioROABefore').text(parseFloat(d.rasioroabefore).toFixed(2)+'%'); }
        if (d.peringkatroabefore != null) setPeringkat('displayPeringkatROABefore',  'summaryPeringkatROABefore',  d.peringkatroabefore);
        if (d.penilaianroabefore != null) setPenilaian('displayPenilaianROABefore',  'summaryPenilaianROABefore',  d.penilaianroabefore,  d.peringkatroabefore);

        if (d.rasiobopobefore != null) { setRatio('displayRasioBOPOBefore', d.rasiobopobefore, ''); $('#displayRasioBOPOBefore span').css('color','#6366f1'); $('#summaryRasioBOPOBefore').text(parseFloat(d.rasiobopobefore).toFixed(2)+'%'); }
        if (d.peringkatbopobefore != null) setPeringkat('displayPeringkatBOPOBefore', 'summaryPeringkatBOPOBefore', d.peringkatbopobefore);
        if (d.penilaianbopobefore != null) setPenilaian('displayPenilaianBOPOBefore', 'summaryPenilaianBOPOBefore', d.penilaianbopobefore, d.peringkatbopobefore);

        if (d.rasionimbefore != null) { setRatio('displayRasioNIMBefore',  d.rasionimbefore,  ''); $('#displayRasioNIMBefore span').css('color','#6366f1'); $('#summaryRasioNIMBefore').text(parseFloat(d.rasionimbefore).toFixed(2)+'%'); }
        if (d.peringkatnimbefore != null) setPeringkat('displayPeringkatNIMBefore',  'summaryPeringkatNIMBefore',  d.peringkatnimbefore);
        if (d.penilaiannimbefore != null) setPenilaian('displayPenilaianNIMBefore',  'summaryPenilaianNIMBefore',  d.penilaiannimbefore,  d.peringkatnimbefore);

        if (d.rataratarentabilitas != null) $('#displayRataRata').text(parseFloat(d.rataratarentabilitas).toFixed(2));
        if (d.peringkatrentabilitas != null && d.peringkatrentabilitas > 0) {
            const p = String(d.peringkatrentabilitas);
            $('#displayPeringkat').text(p);
            $('#displayKategori').text(rankLabel(p)).attr('class', 'badge badge-xl ' + badgeClass(p));
        }
        if (d.rataratarentabilitasbefore  != null) $('#displayRataRataBefore').text(parseFloat(d.rataratarentabilitasbefore).toFixed(2));
        if (d.peringkatrentabilitasbefore != null && d.peringkatrentabilitasbefore > 0) {
            const p = String(d.peringkatrentabilitasbefore);
            $('#displayPeringkatBefore').text(p);
            $('#displayKategoriBefore').text(rankLabel(p)).attr('class', 'badge badge-xl ' + badgeClass(p));
        }
    }

    /* Auto-save */
    function autoSave(field, value, $el) {
        clearTimeout(saveTimers[field]);
        const $ind = $el.siblings('.saving-indicator');
        $ind.html('<small class="text-warning"><i class="fas fa-spinner fa-spin"></i> Menyimpan...</small>').show();

        const rawValue = $el.hasClass('currency-input') ? unformatRupiah(value) : value;

        saveTimers[field] = setTimeout(function () {
            const postData = $.extend({ field: field, value: rawValue }, getCsrfData());
            $.ajax({
                url: BASE_URL + '/Tksrentabilitas/autoSave', method: 'POST',
                data: postData, dataType: 'json',
                success: function (res) {
                    if (res.success) {
                        if (res.csrf_hash) csrfHash = res.csrf_hash;
                        $ind.html('<small class="text-success"><i class="fas fa-check-circle"></i> Tersimpan</small>');
                        updateDisplay(res.data);
                        showToast('success', 'Data berhasil disimpan');
                        setTimeout(function () { $ind.fadeOut(300); }, 2000);
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

    function formatDateTime(dateStr) {
        const date = new Date(dateStr), now = new Date();
        const mins = Math.floor((now - date) / 60000);
        const hours = Math.floor((now - date) / 3600000);
        const days  = Math.floor((now - date) / 86400000);
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
        $.get(BASE_URL + '/Tksrentabilitas/getKomentar', function (list) {
            const $c = $('#komentarList');
            if (!list || !list.length) {
                $c.html('<div class="text-center text-muted py-4"><i class="fas fa-inbox fa-3x mb-3" style="opacity:.3;"></i><p>Belum ada komentar</p></div>');
                return;
            }
            let html = '';
            list.forEach(function (k) {
                const isNew = new Date(k.created_at) > new Date(lastReadTime);
                const borderCol = isNew ? '#28a745' : '#007bff';
                const bgCol = isNew ? '#d4edda'  : '#ffffff';
                const newBadge = isNew ? '<span class="badge badge-success ms-2"><i class="fas fa-star"></i> BARU</span>' : '';
                html += '<div class="komentar-item mb-3 p-3" style="background:'+bgCol+';border-radius:6px;border-left:3px solid '+borderCol+';">'
                    + '<div class="d-flex justify-content-between align-items-start mb-2">'
                    + '<strong style="color:'+borderCol+';"><i class="fas fa-user-circle"></i> '+escEl(k.fullname||'Unknown')+newBadge+'</strong>'
                    + '<small class="text-muted"><i class="fas fa-clock"></i> '+formatDateTime(k.created_at)+'</small></div>'
                    + '<p class="mb-0" style="white-space:pre-wrap;">'+escEl(k.komentar)+'</p></div>';
            });
            $c.html(html);
            if (scrollToBottom) $c.scrollTop($c[0].scrollHeight);
        }, 'json')
        .fail(function () { $('#komentarList').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Gagal memuat komentar</div>'); })
        .always(function () { komentarLoading = false; });
    }

    /* SYNC PERIODE (hanya untuk PE/Admin) */
    function initTksSync() {
        var syncBtn = document.getElementById('btn-sync-periode');
        var syncTip = document.getElementById('sync-tooltip');
        var konfirmBtn = document.getElementById('btn-konfirmasi-sync');
        var batalBtn = document.getElementById('btn-batal-sync');
        var closeBtn = document.getElementById('btn-close-sync-modal');
        var resultArea = document.getElementById('sync-result-area');
        var warningArea = document.getElementById('sync-warning-area');
        var modalEl = document.getElementById('modalSyncPeriode');

        if (!syncBtn || !modalEl) return;

        var _syncChecked = false;
        var _syncData = null;
        var _syncPending = false;
        var _syncModal = null;

        function fmtPreview(val) {
            if (!val && val !== 0) return '<em style="color:#94a3b8;">-</em>';
            var n = parseFloat(val);
            return isNaN(n) ? '-' : 'Rp ' + n.toLocaleString('id-ID', { maximumFractionDigits: 0 });
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
            document.getElementById('mistks-preview-roa').innerHTML  = '';
            document.getElementById('mistks-preview-bopo').innerHTML = '';
            document.getElementById('mistks-preview-nim').innerHTML  = '';
        }


        function showModalResult(isSuccess, message) {
            warningArea.style.display = 'none';
            konfirmBtn.style.display  = 'none';
            if (batalBtn) batalBtn.style.display = 'none';

            resultArea.style.display = '';
            if (isSuccess) {
                resultArea.innerHTML =
                    '<div style="text-align:center;padding:20px 10px;">'
                    + '<div style="font-size:48px;color:#10b981;margin-bottom:12px;"><i class="fas fa-check-circle"></i></div>'
                    + '<div style="font-size:16px;font-weight:700;color:#065f46;margin-bottom:6px;">Sinkronisasi Berhasil!</div>'
                    + '<div style="font-size:13px;color:#6b7280;">' + escEl(message) + '</div>'
                    + '<div style="margin-top:16px;padding:10px 16px;background:#d1fae5;border-radius:8px;font-size:13px;color:#065f46;">'
                    + '<i class="fas fa-spinner fa-spin me-2"></i> Memuat ulang halaman dalam beberapa saat...</div>'
                    + '</div>';
            } else {
                resultArea.innerHTML =
                    '<div style="text-align:center;padding:16px 10px;">'
                    + '<div style="font-size:40px;color:#ef4444;margin-bottom:10px;"><i class="fas fa-times-circle"></i></div>'
                    + '<div style="font-size:15px;font-weight:700;color:#7f1d1d;margin-bottom:6px;">Sinkronisasi Gagal</div>'
                    + '<div style="font-size:13px;color:#6b7280;margin-bottom:12px;">' + escEl(message) + '</div>'
                    + '</div>';

                if (batalBtn) { batalBtn.disabled = false; batalBtn.style.display = ''; }
                if (closeBtn) { closeBtn.disabled = false; }
            }
        }

        function doCheck(cb) {
            if (_syncChecked) { cb && cb(_syncData); return; }
            $.ajax({
                url: BASE_URL + '/Tksrentabilitas/checkPrevPeriode',
                method: 'GET', dataType: 'json',
                success: function (res) { _syncChecked = true; _syncData = res; cb && cb(res); },
                error:   function ()    { _syncChecked = true; _syncData = { status: 'error' }; cb && cb(_syncData); }
            });
        }

        syncTip.textContent = 'Memeriksa periode sebelumnya…';
        doCheck(function (res) {
            if (res && res.status === 'found') {
                if ((res.existing_count || 0) > 0) {
                    syncBtn.disabled    = true;
                    syncTip.textContent = 'Periode aktif sudah memiliki data';
                } else {
                    syncBtn.disabled    = false;
                    syncTip.textContent = 'Sinkron dari ' + (res.prev_label || 'periode sebelumnya');
                }
            } else {
                syncBtn.disabled    = true;
                syncTip.textContent = (res && res.message) || 'Tidak ada data di periode sebelumnya';
            }
        });

        syncBtn.addEventListener('click', function () {
            if (!_syncData || _syncData.status !== 'found') return;

            resetModal();

            document.getElementById('sync-current-label').textContent = _syncData.current_label || '-';
            document.getElementById('sync-prev-label').textContent    = _syncData.prev_label    || '-';

            var prev = _syncData.prev_data || {};
            var fields = [
                ['Laba/(Rugi) Sebelum Pajak', prev.labarugibelumpajak],
                ['Rata-rata Aset', prev.ratarataaset],
                ['Beban Operasional', prev.bebanoperasional],
                ['Pendapatan Operasional', prev.pendapatanoperasional],
                ['Pendapatan Bunga', prev.pendapatanbunga],
                ['Beban Bunga', prev.bebanbunga],
                ['Rata-rata Aset Produktif', prev.ratarataasetproduktif],
            ];
            var html = '';
            fields.forEach(function (f) {
                html += '<div class="sync-preview-row"><span class="sync-preview-label">' + f[0] + '</span>'
                    + '<span class="sync-preview-value">' + fmtPreview(f[1]) + '</span></div>';
            });
            document.getElementById('sync-preview-fields').innerHTML = html;

            if (!_syncModal) {
                _syncModal = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false });
            }
            _syncModal.show();
        });

        konfirmBtn.addEventListener('click', function () {
            if (_syncPending) return;
            _syncPending = true;
            konfirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyinkronkan...';
            konfirmBtn.disabled  = true;
            if (batalBtn) batalBtn.disabled = true;
            if (closeBtn) closeBtn.disabled = true;
            syncBtn.classList.add('syncing');

            $.ajax({
                url:      BASE_URL + '/Tksrentabilitas/syncFromPrevPeriode',
                method:   'POST',
                data:     getCsrfData(),
                dataType: 'json',
                success: function (res) {
                    _syncPending = false;
                    syncBtn.classList.remove('syncing');
                    if (res.csrf_hash) csrfHash = res.csrf_hash;

                    if (res.status === 'success') {
                        showModalResult(true, res.message || 'Data berhasil disinkronkan');
                        setTimeout(function () { location.reload(); }, 2500);
                    } else {
                        showModalResult(false, res.message || 'Gagal melakukan sinkronisasi');
                    }
                },
                error: function (xhr) {
                    _syncPending = false;
                    syncBtn.classList.remove('syncing');

                    var msg = 'Terjadi kesalahan server.';
                    if (xhr.status === 403) msg = 'Sesi CSRF kedaluwarsa, silakan refresh halaman';
                    if (xhr.status === 500) msg = 'Internal server error (500)';

                    try {
                        var errRes = JSON.parse(xhr.responseText);
                        if (errRes && errRes.message) msg = errRes.message;
                    } catch(e) {}

                    showModalResult(false, msg);
                }
            });
        });

        modalEl.addEventListener('hidden.bs.modal', function () {
            if (!_syncPending) resetModal();
        });
    }

    /* ================= TARIK DATA MIS TKS (semua role) ================= */
    function initMisTksTarik() {
        var btn        = document.getElementById('btn-tarik-mistks');
        var tip        = document.getElementById('mistks-tooltip');
        var konfirmBtn = document.getElementById('btn-konfirmasi-mistks');
        var batalBtn   = document.getElementById('btn-batal-mistks');
        var closeBtn   = document.getElementById('btn-close-mistks-modal');
        var resultArea = document.getElementById('mistks-result-area');
        var warningArea= document.getElementById('mistks-warning-area');
        var modalEl    = document.getElementById('modalTarikMisTks');

        // Guard independen — tidak lagi bergantung pada elemen sync
        if (!btn || !modalEl) return;

        var _pending = false;
        var _modal   = null;
        var _data    = null;

        function fmtPreview(val) {
            if (val === null || val === undefined || val === '') return '<em style="color:#94a3b8;">tidak ditemukan</em>';
            var n = parseFloat(val);
            return isNaN(n) ? '-' : 'Rp ' + n.toLocaleString('id-ID', { maximumFractionDigits: 0 });
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

        function buildPreviewGroup(rows) {
        var html = '';
        rows.forEach(function (r) {
            html += '<div class="sync-preview-row"><span class="sync-preview-label">' + r[0] + '</span>'
                + '<span class="sync-preview-value">' + fmtPreview(r[1]) + '</span></div>';
        });
        return html;
        }

        btn.addEventListener('click', function () {
            $.ajax({
                url: BASE_URL + '/Tksrentabilitas/checkMisTksData',
                method: 'GET', dataType: 'json',
                success: function (res) {
                    if (res.status !== 'found') {
                        showToast('error', res.message || 'Data tidak ditemukan di mis_tks');
                        return;
                    }
                    _data = res;
                    resetModal();
                    document.getElementById('mistks-current-label').textContent = res.periode_current;
                    document.getElementById('mistks-prev-label').textContent    = res.periode_prev;

                    var d = res.data || {};

                    document.getElementById('mistks-preview-roa').innerHTML = buildPreviewGroup([
                        ['Laba Sebelum Pajak (saat ini)', d.labarugibelumpajak],
                        ['Rata-rata Aset (saat ini)', d.ratarataaset],
                        ['Laba Sebelum Pajak (sebelumnya)', d.lababelumpajakbefore],
                        ['Rata-rata Aset (sebelumnya)', d.ratarataasetbefore],
                    ]);

                    document.getElementById('mistks-preview-bopo').innerHTML = buildPreviewGroup([
                        ['Beban Operasional (saat ini)', d.bebanoperasional],
                        ['Pendapatan Operasional (saat ini)', d.pendapatanoperasional],
                        ['Beban Operasional (sebelumnya)', d.bebanoperasionalbefore],
                        ['Pendapatan Operasional (sebelumnya)', d.pendapatanoperasionalbefore],
                    ]);

                    document.getElementById('mistks-preview-nim').innerHTML = buildPreviewGroup([
                        ['Pendapatan Bunga (saat ini)', d.pendapatanbunga],
                        ['Beban Bunga (saat ini)', d.bebanbunga],
                        ['Rata-rata Aset Produktif (saat ini)', d.ratarataasetproduktif],
                        ['Pendapatan Bunga (sebelumnya)', d.pendapatanbungabefore],
                        ['Beban Bunga (sebelumnya)', d.bebanbungabefore],
                        ['Rata-rata Aset Produktif (sebelumnya)', d.ratarataasetprodbefore],
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
                url: BASE_URL + '/Tksrentabilitas/tarikDataMisTks',
                method: 'POST', data: getCsrfData(), dataType: 'json',
                success: function (res) {
                    _pending = false;
                    if (res.csrf_hash) csrfHash = res.csrf_hash;
                    if (res.status === 'success') {
                        showResult(true, res.message);
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
    }

    $(function () {

        /* Komentar modal */
        const modalKomentarEl = document.getElementById('modalKomentar');
        const komentarModal   = modalKomentarEl ? new bootstrap.Modal(modalKomentarEl) : null;

        /* Format currency inputs on load */
        $('.currency-input').each(function () {
            const v = $(this).val();
            if (v) $(this).val(formatRupiah(v));
        });

        /* Currency live formatting */
        $(document).on('input', '.currency-input', function () {
            const el = this;
            const oldValue = el.value;
            const oldPos = el.selectionStart;

            // Hitung berapa digit yang ada SEBELUM posisi kursor saat ini
            const digitsBeforeCursor = oldValue.slice(0, oldPos).replace(/[^0-9]/g, '').length;

            // Format ulang nilainya
            const newValue = formatRupiah(oldValue);
            el.value = newValue;

            // Cari posisi baru: taruh kursor setelah digit ke-N yang sama (N = digitsBeforeCursor)
            let count = 0;
            let newPos = newValue.length;
            if (digitsBeforeCursor === 0) {
                newPos = 0;
            } else {
                for (let i = 0; i < newValue.length; i++) {
                    if (/[0-9]/.test(newValue[i])) {
                        count++;
                        if (count === digitsBeforeCursor) {
                            newPos = i + 1;
                            break;
                        }
                    }
                }
            }

            try { el.setSelectionRange(newPos, newPos); } catch (e) {}
        });

        /* Char limiter untuk textarea deskripsi & analisa */
        function updateCharCounter($textarea) {
            var $counter = $textarea.closest('.card-body').find('.char-counter');
            var len = $textarea.val().length;
            $counter.text(len + ' / 2000');
            $counter.toggleClass('text-danger', len >= 2000).toggleClass('text-muted', len < 2000);
        }

        $('.char-limited').each(function () { updateCharCounter($(this)); });

        $(document).on('input', '.char-limited', function () {
            var $el = $(this);
            if ($el.val().length > 2000) {
                $el.val($el.val().substring(0, 2000));
            }
            updateCharCounter($el);
        });

        /* Auto-save on change */
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

        /* Komentar button */
        $('.komentar-button').on('click', function () {
            if (!komentarModal) return;
            loadKomentarList(true);
            $.ajax({ url: BASE_URL + '/Tksrentabilitas/markCommentsAsRead', method: 'POST', data: getCsrfData(),
                success: function (res) {
                    if (res.status === 'success') { updateBadge(0); lastReadTime = new Date().toISOString(); }
                }
            });
            komentarModal.show();
        });

        initTksSync();
        initMisTksTarik();

        /* Submit komentar */
        $('#formKomentar').on('submit', function (e) {
            e.preventDefault();
            const $form    = $(this);
            const $btn     = $form.find('button[type="submit"]');
            const komentar = $form.find('[name="komentar"]').val().trim();
            if (!komentar) { showToast('error', 'Komentar tidak boleh kosong'); return; }

            $btn.html('<i class="fas fa-spinner fa-spin"></i> Mengirim...').prop('disabled', true);
            $.ajax({
                url: BASE_URL + '/Tksrentabilitas/tambahKomentar', method: 'POST',
                data: $form.serialize() + '&' + encodeURIComponent(CSRF_NAME) + '=' + encodeURIComponent(csrfHash),
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
                complete: function () { $btn.html('<i class="fas fa-paper-plane"></i> Kirim Komentar').prop('disabled', false); }
            });
        });

        document.querySelectorAll('[data-bs-toggle="popover"]').forEach(function (el) {
            new bootstrap.Popover(el, { container:'body', trigger:'hover focus', html:true, sanitize:false });
        });
        startPolling();
    });
}(jQuery));
</script>