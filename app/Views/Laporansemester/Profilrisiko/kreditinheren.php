<?php
$peringkat13 = isset($nilai13) && is_array($nilai13) ? ($nilai13['penilaiankredit'] ?? null) : null;
$keterangan13 = isset($nilai13) && is_array($nilai13) ? ($nilai13['keterangan'] ?? '') : '';
$peringkat14 = isset($nilai14) && is_array($nilai14) ? ($nilai14['penilaiankredit'] ?? null) : null;
$keterangan14 = isset($nilai14) && is_array($nilai14) ? ($nilai14['keterangan'] ?? '') : '';
$peringkatLabels  = ['1' => 'Sangat Rendah', '2' => 'Rendah', '3' => 'Sedang', '4' => 'Tinggi', '5' => 'Sangat Tinggi'];

function getBadgeClass($nilai)
{
    $map = ['1' => 'badge-info', '2' => 'badge-success', '3' => 'badge-warning', '4' => 'badge-danger', '5' => 'badge-dark'];
    return $map[$nilai] ?? 'badge-secondary';
}

$activePeriodeId = session()->get('active_periode');
$isManual13 = isset($nilai13['is_manual']) && $nilai13['is_manual'] == 1;
$kesimpulan13 = $nilai13 ?? null;
$k = $kalkulatorData ?? [];
$factorsData = $faktorsData ?? [];
$unreadCounts = $unreadCounts ?? [];
?>

<!-- Flash -->
<div class="alert alert-info my-2">
    <?php if (isset($bprData) && isset($periodeDetail)): ?>
        <strong><?= esc($bprData['namabpr'] ?? 'Nama BPR') ?></strong>
        — Periode Pelaporan Semester <?= esc($periodeDetail['semester']) ?> Tahun <?= esc($periodeDetail['tahun']) ?>
    <?php elseif (isset($periodeDetail)): ?>
        <strong>Periode:</strong> Semester <?= esc($periodeDetail['semester']) ?> Tahun <?= esc($periodeDetail['tahun']) ?>
    <?php else: ?>
        <strong>Periode belum ditentukan</strong>
    <?php endif; ?>
</div>

<!-- Toast container -->
<div id="toast-wrapper" aria-live="polite"></div>
<div id="sync-bar" style="display:none;"
     class="alert alert-warning d-flex justify-content-between align-items-center py-2 px-3 mb-2">
    <div id="sync-bar-text" style="font-size:.9em;"></div>
    <button type="button" id="btn-sync-trigger" class="btn btn-sm btn-warning ml-2"
            style="white-space:nowrap;">
        <i class="fas fa-sync-alt"></i> Sinkronisasi
    </button>
</div>

<div class="container-fluid">
    <?php if (session()->get('message')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <strong><?= session()->getFlashdata('message') ?></strong>
    </div>
    <?php endif; ?>

    <div class="card card-body">
        <div class="table-vertical">

            <!-- Nav buttons -->
            <div class="button-bar">
                <a href="Showprofilresiko" class="kpmr-btn icon-link icon-link-hover" id="btn-laporan">🡸 Laporan Profil Risiko</a>
                <a href="Risikokreditkpmr" class="kpmr-btn" id="btn-risiko">Risiko Kredit KPMR 🡺</a>
            </div>

            <!-- Title -->
            <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
                <div class="text-center flex-grow-1">
                    <h4 class="mb-0">
                        <i class="fas fa-info-circle text-primary"></i>
                        <strong>Tingkat Risiko Kredit</strong>
                    </h4>
                    <small class="text-muted">
                        <i class="fas fa-file-alt"></i>
                        Laporan Profil Risiko Semester <?= esc($periodeDetail['semester'] ?? '') ?> Tahun <?= esc($periodeDetail['tahun'] ?? '') ?>
                    </small>
                </div>
            </div>

            <!-- Peringkat 13 -->
            <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
                <div class="text-center flex-grow-1">
                    <div class="mt-2">
                        <span class="badge badge-lg <?= getBadgeClass($peringkat13) ?>"
                              style="font-size:1.1em;padding:.5em 1em;" id="badge-peringkat13">
                            <i class="fas fa-check-circle"></i>
                            Peringkat penilaian: <?= esc($peringkat13 ?? 'N/A') ?>
                            [<?= $peringkatLabels[$peringkat13] ?? 'N/A' ?>]
                        </span>
                        <?php if (($userInGroupPE ?? false) || ($userInGroupAdmin ?? false)): ?>
                            <?php if (!$isManual13): ?>
                                <button type="button" class="btn btn-sm btn-outline-primary ml-2"
                                        data-bs-toggle="collapse" data-bs-target="#editPeringkat13">
                                    <i class="fa fa-caret-down"></i>
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-sm btn-warning ml-2" id="btnResetPeringkat13"
                                        title="Kembalikan ke perhitungan otomatis">
                                    <i class="fa fa-undo"></i>
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Edit peringkat 13 collapse -->
            <?php if (($userInGroupPE ?? false) || ($userInGroupAdmin ?? false)): ?>
            <div id="editPeringkat13" class="collapse">
                <div class="p-3 border-top">
                    <select id="peringkat13_manual" class="form-control w-50 mx-auto">
                        <option value="">-- Pilih Peringkat --</option>
                        <?php foreach ($peringkatLabels as $v => $l): ?>
                        <option value="<?= $v ?>" <?= $peringkat13 == $v ? 'selected' : '' ?>>
                            <?= $v ?> - <?= $l ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="text-center">
                        <button type="button" class="btn btn-warning btn-sm mt-2" id="btnSimpanPeringkat13">
                            <i class="fas fa-edit"></i> Update
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Peringkat 14 -->
            <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
                <div class="text-center flex-grow-1">
                    <div class="mt-2">
                        <span class="badge badge-lg <?= getBadgeClass($peringkat14) ?>"
                              style="font-size:1.1em;padding:.5em 1em;">
                            <i class="fas fa-check-circle"></i>
                            Peringkat penilaian pada periode sebelumnya: <?= esc($peringkat14 ?? 'N/A') ?>
                            [<?= $peringkatLabels[$peringkat14] ?? 'N/A' ?>]
                        </span>
                        <?php if (($userInGroupPE ?? false) || ($userInGroupAdmin ?? false)): ?>
                            <button type="button" class="btn btn-sm btn-outline-primary ml-2"
                                    data-bs-toggle="collapse" data-bs-target="#editNilai14">
                                <i class="fa fa-caret-down"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Edit nilai 14 -->
            <div id="editNilai14" class="collapse">
                <div class="p-3 border-top">
                    <form action="<?= base_url('Risikokredit/simpanNilai14') ?>" method="post">
                        <?= csrf_field() ?>
                        <select name="penilaiankredit" class="form-control w-50 mx-auto" required>
                            <?php if (!empty($nilai14)): ?>
                                <option value="" disabled selected>-- Nilai Saat Ini: <?= $nilai14['penilaiankredit'] ?> --</option>
                            <?php else: ?>
                                <option value="">-- Pilih Tingkat Risiko --</option>
                            <?php endif; ?>
                            <option value="1">Sangat Rendah (1)</option>
                            <option value="2">Rendah (2)</option>
                            <option value="3">Cukup (3)</option>
                            <option value="4">Tinggi (4)</option>
                            <option value="5">Sangat Tinggi (5)</option>
                        </select>
                        <div class="text-center">
                            <button type="submit" class="btn <?= !empty($nilai14) ? 'btn-warning' : 'btn-primary' ?> btn-sm mt-2">
                                <i class="fas <?= !empty($nilai14) ? 'fa-edit' : 'fa-save' ?>"></i>
                                <?= !empty($nilai14) ? 'Update' : 'Simpan' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Approval badge -->
            <div style="text-align:right;">
                <span id="approval-badge-main"
                      class="badge <?= ($allApproved ?? false) ? 'badge-success' : 'badge-secondary' ?>"
                      style="font-size:14px;">
                    <?php if ($allApproved ?? false): ?>
                        Disetujui oleh <strong>Direktur Utama</strong><br>
                        <?= isset($nilai13['approved_at']) ? date('d/m/Y H:i:s', strtotime($nilai13['approved_at'])) : '-' ?>
                    <?php else: ?>
                        Belum Disetujui Seluruhnya<br>Oleh Direktur Utama
                    <?php endif; ?>
                </span>
            </div>

            <div class="card-body">
                <div class="mb-2"><strong>A. Kertas Kerja Inheren Kredit</strong></div>

                <?php if (($userInGroupPE ?? false) || ($userInGroupAdmin ?? false)): ?>
                <button class="btn btn-primary mr-2 mb-3" type="button"
                        data-bs-toggle="offcanvas" data-bs-target="#offcanvasWithBothOptions">
                    Kalkulator Komposisi Portofolio Aset dan Tingkat Konsentrasi Kredit
                </button>
                <button class="btn btn-primary mb-3" type="button"
                        data-bs-toggle="offcanvas" data-bs-target="#offcanvasWithBothOptions2">
                    Kalkulator Kualitas Aset
                </button>
                <?php endif; ?>

                <?php
                $factorStructure = [
                    [
                        'faktor_id' => 1,
                        'title'     => 'Pilar Komposisi Portofolio Aset dan Tingkat Konsentrasi Kredit',
                        'type'      => 'category',
                        'children'  => [
                            ['faktor_id' => 2, 'title' => 'Parameter rasio aset produktif terhadap total aset'],
                            ['faktor_id' => 3, 'title' => 'Parameter rasio kredit yang diberikan terhadap total aset produktif'],
                            ['faktor_id' => 4, 'title' => 'Parameter rasio 25 debitur terbesar terhadap total kredit yang diberikan'],
                            ['faktor_id' => 5, 'title' => 'Parameter rasio kredit per sektor ekonomi terhadap total kredit yang diberikan'],
                        ],
                    ],
                    [
                        'faktor_id' => 6,
                        'title'     => 'Pilar Kualitas Aset',
                        'type'      => 'category',
                        'children'  => [
                            ['faktor_id' => 7, 'title' => 'Parameter rasio aset produktif bermasalah terhadap total aset produktif'],
                            ['faktor_id' => 8, 'title' => 'Parameter kredit bermasalah neto terhadap total kredit yang diberikan'],
                            ['faktor_id' => 9, 'title' => 'Parameter kredit kualitas rendah terhadap total kredit yang diberikan'],
                        ],
                    ],
                    ['faktor_id' => 10, 'title' => 'Pilar Strategi Penyediaan Dana', 'type' => 'single'],
                    ['faktor_id' => 11, 'title' => 'Pilar Faktor Eksternal', 'type' => 'single'],
                    ['faktor_id' => 12, 'title' => 'Faktor Lainnya', 'type' => 'single'],
                ];

                $nilaiLookup = $nilaiLookup ?? [];

                foreach ($factorStructure as $idx => $factor):
                    $fid = $factor['faktor_id'];
                    $nilai = $nilaiLookup[$fid] ?? null;
                    $hasData = !empty($nilai['penilaiankredit']);
                    $unread = (int)($unreadCounts[$fid] ?? 0);
                    $canEdit = ($userInGroupAdmin ?? false) || ($userInGroupPE ?? false);
                    $canApprDir = ($userInGroupAdmin ?? false) || ($userInGroupDireksi ?? false) || ($userInGroupDireksi2 ?? false);
                ?>
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap" style="gap:6px;">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <?= $idx + 1 ?>. <?= esc($factor['title']) ?>
                        </h6>
                        <div class="d-flex align-items-center flex-wrap" style="gap:4px;">
                            <?php if ($hasData): ?>
                                <span class="badge badge-info">
                                    <i class="fas fa-check"></i> Peringkat: <?= esc($nilai['penilaiankredit']) ?>
                                </span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Belum dinilai</span>
                            <?php endif; ?>

                            <!-- Edit / Tambah (single) -->
                            <?php if ($factor['type'] === 'single' && $canEdit): ?>
                                <?php if ($hasData): ?>
                                    <button class="btn btn-sm btn-warning ml-2 btn-edit"
                                            data-id="<?= $fid ?>"
                                            data-title="<?= esc($factor['title']) ?>"
                                            data-penilaiankredit="<?= esc($nilai['penilaiankredit']) ?>"
                                            data-keterangan="<?= esc($nilai['keterangan'] ?? '') ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-success ml-2 btn-add"
                                            data-id="<?= $fid ?>"
                                            data-title="<?= esc($factor['title']) ?>">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>

                            <!-- Edit keterangan (category) -->
                            <?php if ($factor['type'] === 'category' && $canEdit): ?>
                                <button class="btn btn-sm btn-warning ml-2 btn-edit-keterangan"
                                        data-id="<?= $fid ?>"
                                        data-title="<?= esc($factor['title']) ?>"
                                        data-keterangan="<?= esc($nilai['keterangan'] ?? '') ?>">
                                    <i class="fa fa-edit"></i>
                                </button>
                            <?php endif; ?>

                            <!-- Komentar button dengan badge notifikasi -->
                            <button type="button"
                                    class="btn btn-sm btn-primary ml-2 position-relative komentar-button"
                                    data-faktor-id="<?= $fid ?>">
                                <i class="fas fa-comment"></i>
                                <span class="badge badge-danger notification-badge"
                                      id="notification-badge-<?= $fid ?>"
                                      style="<?= $unread > 0 ? '' : 'display:none;' ?>">
                                    <?= $unread > 0 ? $unread : '' ?>
                                </span>
                            </button>

                            <!-- Approval dir2 -->
                            <?php if ($canApprDir && $hasData): ?>
                                <?php $accOk = !empty($nilai['accdir2']) && $nilai['accdir2'] == 1; ?>
                                <form action="<?= base_url('Risikokredit/' . ($accOk ? 'unapprovedir2' : 'accdir2')) ?>"
                                      method="POST" style="display:inline-block;" class="ml-2 form-approval">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="faktor1id"  value="<?= $fid ?>">
                                    <input type="hidden" name="kodebpr"    value="<?= esc($kodebpr ?? '') ?>">
                                    <input type="hidden" name="periode_id" value="<?= esc($activePeriodeId ?? '') ?>">
                                    <button type="submit" class="btn btn-sm <?= $accOk ? 'btn-success' : 'btn-dark' ?>">
                                        <i class="fas fa-<?= $accOk ? 'check' : 'times' ?>-circle"></i>
                                    </button>
                                </form>
                            <?php elseif ($canApprDir && !$hasData): ?>
                                <span class="text-muted ml-2">-</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Category children -->
                    <?php if ($factor['type'] === 'category' && !empty($factor['children'])): ?>
                    <div class="card-body">
                        <?php foreach ($factor['children'] as $cidx => $child):
                            $cfid     = $child['faktor_id'];
                            $cnilai   = $nilaiLookup[$cfid] ?? null;
                            $cHasData = !empty($cnilai['penilaiankredit']);
                            $cUnread  = (int)($unreadCounts[$cfid] ?? 0);
                            $cAccOk   = !empty($cnilai['accdir2']) && $cnilai['accdir2'] == 1;
                        ?>
                        <div class="border-left border-success pl-4 mb-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="font-weight-bold">
                                        <?= $cidx + 1 ?>. <?= nl2br(esc($child['title'])) ?>
                                    </h6>
                                    <?php if (!empty($cnilai['rasiokredit'])): ?>
                                        <p class="mb-2">
                                            <strong>Nilai rasio: <?= esc($cnilai['rasiokredit']) ?></strong>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="ml-3 d-flex align-items-center flex-wrap" style="gap:4px;">
                                    <?php if ($cHasData): ?>
                                        <span class="badge badge-success">
                                            <i class="fas fa-check"></i> Peringkat: <?= esc($cnilai['penilaiankredit']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Belum dinilai</span>
                                    <?php endif; ?>

                                    <?php if ($canEdit): ?>
                                        <?php if ($cHasData): ?>
                                            <button class="btn btn-sm btn-warning ml-2 btn-edit"
                                                    data-id="<?= $cfid ?>"
                                                    data-title="<?= esc($child['title']) ?>"
                                                    data-penilaiankredit="<?= esc($cnilai['penilaiankredit']) ?>"
                                                    data-keterangan="<?= esc($cnilai['keterangan'] ?? '') ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-success ml-2 btn-add"
                                                    data-id="<?= $cfid ?>"
                                                    data-title="<?= esc($child['title']) ?>">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <!-- Komentar + badge -->
                                    <button type="button"
                                            class="btn btn-sm btn-primary ml-2 position-relative komentar-button"
                                            data-faktor-id="<?= $cfid ?>">
                                        <i class="fas fa-comment"></i>
                                        <span class="badge badge-danger notification-badge"
                                              id="notification-badge-<?= $cfid ?>"
                                              style="<?= $cUnread > 0 ? '' : 'display:none;' ?>">
                                            <?= $cUnread > 0 ? $cUnread : '' ?>
                                        </span>
                                    </button>

                                    <!-- Approval dir2 child -->
                                    <?php if ($canApprDir && $cHasData): ?>
                                        <form action="<?= base_url('Risikokredit/' . ($cAccOk ? 'unapprovedir2' : 'accdir2')) ?>"
                                              method="POST" style="display:inline-block;" class="ml-2 form-approval">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="faktor1id"  value="<?= $cfid ?>">
                                            <input type="hidden" name="kodebpr"    value="<?= esc($kodebpr ?? '') ?>">
                                            <input type="hidden" name="periode_id" value="<?= esc($activePeriodeId ?? '') ?>">
                                            <button type="submit" class="btn btn-sm <?= $cAccOk ? 'btn-success' : 'btn-dark' ?>">
                                                <i class="fas fa-<?= $cAccOk ? 'check' : 'times' ?>-circle"></i>
                                            </button>
                                        </form>
                                    <?php elseif ($canApprDir && !$cHasData): ?>
                                        <span class="text-muted ml-2">-</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>

                <!-- OFFCANVAS 1 -->
                <?php if (($userInGroupPE ?? false) || ($userInGroupAdmin ?? false)): ?>
                <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasWithBothOptions">
                    <div class="offcanvas-header">
                        <h5 class="offcanvas-title">Kalkulator Komposisi Portofolio Aset</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas">&times;</button>
                    </div>
                    <div class="offcanvas-body">
                        <form id="riskForm">
                            <?php
                            $fields1 = [
                                'aba'            => 'Antar Bank Aktiva (ABA)',
                                'kydbank'        => 'Kredit Yang Diberikan (KYD) Kepada Bank (Gross)',
                                'kydpihak3'      => 'Kredit Yang Diberikan (KYD) Kepada Pihak Ketiga Bukan Bank (Gross)',
                                'totalaset'      => 'Total Aset',
                                'total25debitur' => 'Total Baki Debet 25 Debitur Terbesar',
                            ];
                            foreach ($fields1 as $fid => $flabel): ?>
                            <div class="mb-3">
                                <label for="<?= $fid ?>" class="form-label"><?= $flabel ?></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control currency-input" id="<?= $fid ?>"
                                           data-raw="<?= esc($k[$fid] ?? '') ?>"
                                           placeholder="<?= !empty($k[$fid]) ? number_format((float)$k[$fid], 0, ',', '.') : 'Masukkan nilai' ?>">
                                </div>
                            </div>
                            <?php endforeach; ?>

                            <h5><b>Tiga Sektor Ekonomi Terbesar</b></h5>
                            <?php
                            $fields1b = [
                                'perdagangan'   => 'Sektor Perdagangan',
                                'jasa'          => 'Sektor Jasa',
                                'konsumsirumah' => 'Sektor Konsumsi Rumah Tangga',
                            ];
                            foreach ($fields1b as $fid => $flabel): ?>
                            <div class="mb-3">
                                <label for="<?= $fid ?>" class="form-label"><?= $flabel ?></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control currency-input" id="<?= $fid ?>"
                                           data-raw="<?= esc($k[$fid] ?? '') ?>"
                                           placeholder="<?= !empty($k[$fid]) ? number_format((float)$k[$fid], 0, ',', '.') : 'Masukkan nilai' ?>">
                                </div>
                            </div>
                            <?php endforeach; ?>

                            <input type="hidden" id="kydgross_hidden">
                            <input type="hidden" id="asetproduktif_hidden">
                            <input type="hidden" id="sektorekonomi_hidden">
                            <input type="hidden" id="rasioasetproduktif_submit">
                            <input type="hidden" id="rasiokreditdiberikan_submit">
                            <input type="hidden" id="rasio25debitur_submit">
                            <input type="hidden" id="rasioekonomi_submit">

                            <button type="button" class="btn btn-primary w-100" onclick="calculateRiskKalkulator()">
                                <i class="fas fa-calculator"></i> Hitung
                            </button>
                        </form>

                        <div id="riskResult" class="risk-result mt-3"></div>
                        <div id="detailResult" style="display:none;" class="mt-4">
                            <h6>Hasil Perhitungan:</h6>
                            <table class="table table-sm">
                                <tr><td>Aset Produktif</td>        <td id="asetproduktif">-</td></tr>
                                <tr><td>KYD Gross</td>             <td id="kydgross">-</td></tr>
                                <tr><td>Total Aset</td>            <td id="totalaset_display">-</td></tr>
                                <tr><td>Rasio Aset Produktif</td>  <td id="rasioasetproduktif">-</td></tr>
                                <tr><td>Rasio Kredit Diberikan</td><td id="rasiokreditdiberikan">-</td></tr>
                                <tr><td>Rasio 25 Debitur</td>      <td id="rasio25debitur">-</td></tr>
                                <tr><td>Sektor Ekonomi</td>        <td id="sektorekonomi">-</td></tr>
                                <tr><td>Rasio Ekonomi</td>         <td id="rasioekonomi">-</td></tr>
                            </table>
                            <button type="button" id="btnInsertRasio" class="btn btn-success w-100 mt-2"
                                    style="display:none;" onclick="insertRasioToKertasKerja()">
                                <i class="fas fa-save"></i> Masukkan ke Kertas Kerja
                            </button>
                        </div>
                    </div>
                </div>

                <!-- OFFCANVAS 2 -->
                <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasWithBothOptions2">
                    <div class="offcanvas-header">
                        <h5 class="offcanvas-title">Kalkulator Kualitas Aset</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas">&times;</button>
                    </div>
                    <div class="offcanvas-body">
                        <form id="riskForm2">
                            <?php
                            $fields2 = [
                                'abanpl' => 'ABA NPL',
                                'kydnpl3' => 'KYD NPL Kolektibilitas 3',
                                'kydnpl4' => 'KYD NPL Kolektibilitas 4',
                                'kydnpl5' => 'KYD NPL Kolektibilitas 5',
                                'kreditbermasalah' => 'Kredit Bermasalah Neto',
                                'kreditdpk2' => 'Kredit Kolektibilitas 2 (DPK)',
                                'kreditrestruktur1'=> 'Kredit Restrukturisasi Kolektibilitas 1',
                            ];
                            foreach ($fields2 as $fid => $flabel): ?>
                            <div class="mb-3">
                                <label for="<?= $fid ?>" class="form-label"><?= $flabel ?></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control currency-input" id="<?= $fid ?>"
                                           data-raw="<?= esc($k[$fid] ?? '') ?>"
                                           placeholder="<?= !empty($k[$fid]) ? number_format((float)$k[$fid], 0, ',', '.') : 'Masukkan nilai' ?>">
                                </div>
                            </div>
                            <?php endforeach; ?>

                            <input type="hidden" id="kydkoleknpl_hidden">
                            <input type="hidden" id="asetproduktifbermasalah_hidden">
                            <input type="hidden" id="rasioasetproduktifbermasalah_submit">
                            <input type="hidden" id="rasiokreditbermasalah_submit">
                            <input type="hidden" id="rasiokreditkualitasrendah_submit">

                            <button type="button" class="btn btn-primary w-100" onclick="calculateRiskKalkulator2()">
                                <i class="fas fa-calculator"></i> Hitung
                            </button>
                        </form>

                        <div id="riskResult2" class="risk-result mt-3"></div>
                        <div id="detailResult2" style="display:none;" class="mt-4">
                            <h6>Hasil Perhitungan:</h6>
                            <table class="table table-sm">
                                <tr><td>KYD Kolek NPL</td><td id="kydkoleknpl_display">-</td></tr>
                                <tr><td>Aset Produktif Bermasalah</td><td id="asetproduktifbermasalah_display">-</td></tr>
                                <tr><td>Aset Produktif</td><td id="asetproduktif_display2">-</td></tr>
                                <tr><td>Rasio Aset Produktif Bermasalah</td><td id="rasioasetproduktifbermasalah_display">-</td></tr>
                                <tr><td>Rasio Kredit Bermasalah</td><td id="rasiokreditbermasalah_display">-</td></tr>
                                <tr><td>Rasio Kredit Kualitas Rendah</td><td id="rasiokreditkualitasrendah_display">-</td></tr>
                            </table>
                            <button type="button" id="btnInsertRasio2" class="btn btn-success w-100 mt-2"
                                    style="display:none;" onclick="insertRasioKualitasAsetToKertasKerja()">
                                <i class="fas fa-save"></i> Masukkan ke Kertas Kerja
                            </button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- KESIMPULAN -->
            <div class="card mt-3">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-clipboard-list"></i> Kesimpulan Penilaian Risiko Kredit Inheren</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($kesimpulan13) && !empty($kesimpulan13['keterangan'])): ?>
                        <div class="alert border">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="alert-heading"><i class="fas fa-check-circle"></i> Kesimpulan Tersimpan</h6>
                                    <p class="mb-0"><?= nl2br(esc($kesimpulan13['keterangan'])) ?></p>
                                </div>
                                <?php if (($userInGroupPE ?? false) || ($userInGroupAdmin ?? false)): ?>
                                <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="collapse" data-bs-target="#editKesimpulan13">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (($userInGroupPE ?? false) || ($userInGroupAdmin ?? false)): ?>
                        <div class="collapse" id="editKesimpulan13">
                            <hr>
                            <form action="<?= base_url('Risikokredit/simpanKesimpulan13') ?>" method="post">
                                <?= csrf_field() ?>
                                <div class="form-group">
                                    <label><strong>Edit Keterangan Kesimpulan:</strong></label>
                                    <textarea class="form-control" name="keterangan" rows="5" required><?= esc($kesimpulan13['keterangan']) ?></textarea>
                                </div>
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-secondary" data-bs-toggle="collapse" data-bs-target="#editKesimpulan13">
                                        <i class="fas fa-times"></i> Batal
                                    </button>
                                    <button type="submit" class="btn btn-primary ml-3">
                                        <i class="fas fa-save"></i> Update Kesimpulan
                                    </button>
                                </div>
                            </form>
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if (($userInGroupPE ?? false) || ($userInGroupAdmin ?? false)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Kesimpulan belum diisi.</strong> Silakan isi keterangan kesimpulan di bawah ini.
                        </div>
                        <form action="<?= base_url('Risikokredit/simpanKesimpulan13') ?>" method="post">
                            <?= csrf_field() ?>
                            <div class="form-group">
                                <label><strong>Keterangan Kesimpulan:</strong></label>
                                <textarea class="form-control" name="keterangan" rows="5"
                                          placeholder="Masukkan kesimpulan penilaian risiko kredit inheren..."
                                          required></textarea>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> Simpan Kesimpulan
                                </button>
                            </div>
                        </form>
                        <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Kesimpulan belum diisi oleh Tim Penilaian Risiko.
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Approval Direktur Utama -->
            <?php if (($userInGroupAdmin ?? false) || ($userInGroupDireksi ?? false)): ?>
            <div class="d-flex justify-content-center mt-3">
                <div class="col-md-3 mx-auto">
                    <div class="card shadow-sm approvaldir-card" style="height:120px;">
                        <div class="card-body approvaldir-card-body">
                            <div class="approval-badge-container">
                                <span class="badge approval-badge">Approval Direktur Utama</span>
                            </div>
                            <div class="approval-buttons-container">
                                <a href="<?= base_url('Risikokredit/approveSemua') ?>"
                                   class="btn approval-btn approval-btn-approve <?= !($canApprove ?? false) ? 'disabled-btn' : '' ?>"
                                   id="btnApproveSemua">Approve</a>
                                <a href="<?= base_url('Risikokredit/unapproveSemua') ?>"
                                   class="btn approval-btn approval-btn-reject <?= !($allApproved ?? false) ? 'disabled-btn' : '' ?>"
                                   id="btnUnapproveSemua">Tolak</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- MODAL: Konfirmasi Sinkronisasi Periode Sebelumnya -->
<!-- MODAL: Konfirmasi Sinkronisasi Periode Sebelumnya -->
<div class="modal fade" id="modalSyncConfirm">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
        <div class="modal-content" style="border:none;border-radius:16px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.2);">

            <!-- Header -->
            <div style="background:linear-gradient(135deg,#141863 0%,#1f2aa8 100%);padding:20px 24px;display:flex;align-items:center;justify-content:space-between;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:38px;height:38px;background:rgba(255,255,255,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-sync-alt" style="font-size:16px;color:#fff;"></i>
                    </div>
                    <div>
                        <div style="color:#fff;font-size:15px;font-weight:600;line-height:1.3;">Sinkronisasi Periode</div>
                        <div style="color:rgba(255,255,255,0.65);font-size:12px;">Salin data dari periode sebelumnya</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                        style="width:28px;height:28px;background:rgba(255,255,255,0.12);border:none;border-radius:7px;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px;padding:0;opacity:1;">
                    &times;
                </button>
            </div>

            <!-- Body -->
            <div style="padding:20px 24px;background:#fff;">

                <!-- Info sumber periode (diisi oleh JS) -->
                <div id="sync-source-card" style="background:#EAF3DE;border:0.5px solid #97C459;border-radius:10px;padding:14px 16px;margin-bottom:16px;display:flex;align-items:flex-start;gap:12px;">
                    <div style="width:34px;height:34px;background:#639922;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">
                        <i class="fas fa-calendar-alt" style="font-size:16px;color:#fff;"></i>
                    </div>
                    <div id="sync-modal-info" style="flex:1;min-width:0;">
                        <div style="font-size:13px;font-weight:600;color:#27500A;margin-bottom:2px;">Sumber data</div>
                        <div style="font-size:12px;color:#3B6D11;"></div>
                    </div>
                </div>

                <!-- Yang akan disalin -->
                <div style="margin-bottom:16px;">
                    <div style="font-size:11px;font-weight:600;color:#888;text-transform:uppercase;letter-spacing:.6px;margin-bottom:10px;">Yang akan disalin</div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                        <div style="background:#f8f9fa;border-radius:8px;padding:9px 12px;display:flex;align-items:center;gap:8px;">
                            <i class="fas fa-star-half-alt" style="font-size:13px;color:#6c757d;"></i>
                            <span style="font-size:12px;color:#495057;">Penilaian kredit</span>
                        </div>
                        <div style="background:#f8f9fa;border-radius:8px;padding:9px 12px;display:flex;align-items:center;gap:8px;">
                            <i class="fas fa-align-left" style="font-size:13px;color:#6c757d;"></i>
                            <span style="font-size:12px;color:#495057;">Keterangan</span>
                        </div>
                        <div style="background:#f8f9fa;border-radius:8px;padding:9px 12px;display:flex;align-items:center;gap:8px;">
                            <i class="fas fa-file-alt" style="font-size:13px;color:#6c757d;"></i>
                            <span style="font-size:12px;color:#495057;">Penjelasan penilaian</span>
                        </div>
                        <div style="background:#f8f9fa;border-radius:8px;padding:9px 12px;display:flex;align-items:center;gap:8px;">
                            <i class="fas fa-calculator" style="font-size:13px;color:#6c757d;"></i>
                            <span style="font-size:12px;color:#495057;">Rata-rata ulang</span>
                        </div>
                    </div>
                </div>

                <!-- Warning data existing (hidden by default, shown by JS jika current_has_data) -->
                <div id="sync-overwrite-warning" style="display:none;background:#FAEEDA;border:0.5px solid #EF9F27;border-radius:10px;padding:12px 14px;margin-bottom:16px;display:none;align-items:flex-start;gap:10px;">
                    <i class="fas fa-exclamation-triangle" style="font-size:15px;color:#854F0B;flex-shrink:0;margin-top:1px;"></i>
                    <div style="font-size:12px;color:#633806;line-height:1.5;">
                        Periode aktif sudah memiliki sebagian data. Aktifkan <em>"Timpa data"</em> untuk mengganti semua nilai yang sudah ada.
                    </div>
                </div>

                <!-- Toggle timpa data -->
                <label id="sync-toggle-label" style="display:flex;align-items:center;gap:12px;cursor:pointer;padding:12px 14px;background:#f8f9fa;border-radius:10px;border:0.5px solid #dee2e6;margin-bottom:0;user-select:none;">
                    <div style="position:relative;width:40px;height:22px;flex-shrink:0;">
                        <input type="checkbox" id="sync-force-overwrite"
                               style="position:absolute;opacity:0;width:0;height:0;">
                        <div id="sync-toggle-track"
                             style="width:40px;height:22px;background:#ced4da;border-radius:11px;transition:background .2s ease;cursor:pointer;"></div>
                        <div id="sync-toggle-thumb"
                             style="position:absolute;top:3px;left:3px;width:16px;height:16px;background:#fff;border-radius:50%;transition:left .2s ease;pointer-events:none;box-shadow:0 1px 3px rgba(0,0,0,.2);"></div>
                    </div>
                    <div>
                        <div style="font-size:13px;font-weight:600;color:#343a40;">Timpa data yang sudah ada</div>
                        <div style="font-size:11px;color:#6c757d;margin-top:2px;">Semua faktor akan diganti dengan data periode sebelumnya</div>
                    </div>
                </label>

                <!-- Progress -->
                <div id="sync-progress" style="display:none;margin-top:14px;background:#e9f3ff;border-radius:10px;padding:12px 14px;display:none;align-items:center;gap:10px;">
                    <i class="fas fa-spinner fa-spin" style="font-size:16px;color:#1f2aa8;"></i>
                    <span style="font-size:13px;color:#1f2aa8;font-weight:500;">Sedang menyinkronisasi data...</span>
                </div>

            </div>

            <!-- Footer -->
            <div style="padding:16px 24px;border-top:1px solid #f0f0f0;background:#fff;display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                        style="padding:9px 20px;border-radius:8px;font-size:13px;font-weight:500;background:#fff;color:#495057;border:1px solid #ced4da;">
                    Batal
                </button>
                <button type="button" id="btn-sync-execute"
                        style="padding:9px 22px;border-radius:8px;font-size:13px;font-weight:600;border:none;background:linear-gradient(135deg,#141863 0%,#1f2aa8 100%);color:#fff;cursor:pointer;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-sync-alt"></i> Mulai Sinkronisasi
                </button>
            </div>

        </div>
    </div>
</div>

<!-- MODAL: Ubah Nilai-->
<div class="modal fade" id="modalUbah">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ubah <?= esc($judul ?? 'Data') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="formUbah" data-action="<?= base_url('Risikokredit/ubah') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="faktor1id" id="edit-faktor-id">
                    <div class="mb-3">
                        <label><strong>Parameter penilaian risiko:</strong></label>
                        <textarea class="form-control" id="edit-parameterpenilaian" style="height:100px;" readonly></textarea>
                    </div>
                    <label><strong>Pilih Tingkat Risiko:</strong></label>
                    <div class="alert alert-info mb-2 d-none" id="edit-catatan-info" style="font-size:.9em;">
                        <i class="fas fa-info-circle"></i> <span id="edit-catatan-text"></span>
                    </div>
                    <div class="list-group mb-3" id="editRatingList"></div>
                    <p>Nilai yang dipilih: <strong id="editSelectedValue" class="text-primary"></strong></p>
                    <input type="hidden" name="penilaiankredit" id="edit-penilaiankredit">
                    <input type="hidden" name="penjelasanpenilaian" id="edit-penjelasanpenilaian">
                    <div class="mb-3">
                        <label><strong>Keterangan:</strong></label>
                        <textarea class="form-control" name="keterangan" id="edit-keterangan"
                                  style="height:100px;" placeholder="Masukkan keterangan..." required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Ubah Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Ubah Keterangan Kesimpulan -->
<div class="modal fade" id="modalUbahkesimpulan">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ubah Keterangan Kesimpulan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="formUbahKesimpulan" data-action="<?= base_url('Risikokredit/ubahkesimpulan') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="faktor1id" id="edit-faktor-id-kesimpulan">
                    <div class="mb-3">
                        <label><strong>Sub Kategori:</strong></label>
                        <textarea class="form-control" id="edit-parameterpenilaian-kesimpulan"
                                  style="height:100px;" readonly></textarea>
                    </div>
                    <div class="mb-3">
                        <label><strong>Keterangan:</strong></label>
                        <textarea class="form-control" name="keterangan" id="edit-keterangan-kesimpulan"
                                  style="height:100px;" placeholder="Masukkan keterangan..." required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Ubah Keterangan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Tambah Nilai -->
<div class="modal fade" id="modaltambahNilai">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Nilai Faktor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="formTambahNilai" data-action="<?= base_url('Risikokredit/tambahNilai') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="faktor_id" id="add-faktor-id">
                    <input type="hidden" name="fullname" value="<?= esc($fullname ?? '') ?>">
                    <div class="mb-3">
                        <label><strong>Parameter penilaian risiko:</strong></label>
                        <textarea class="form-control" id="add-parameterpenilaian" style="height:100px;" readonly></textarea>
                    </div>
                    <label><strong>Pilih Tingkat Risiko:</strong></label>
                    <div class="alert alert-info mb-2 d-none" id="add-catatan-info" style="font-size:.9em;">
                        <i class="fas fa-info-circle"></i> <span id="add-catatan-text"></span>
                    </div>
                    <div class="list-group mb-3" id="addRatingList"></div>
                    <p>Nilai yang dipilih: <strong id="addSelectedValue" class="text-primary">Belum ada</strong></p>
                    <input type="hidden" name="penilaiankredit" id="add-penilaiankredit" required>
                    <input type="hidden" name="penjelasanpenilaian" id="add-penjelasanpenilaian">
                    <div class="mb-3">
                        <label><strong>Keterangan:</strong></label>
                        <textarea class="form-control" name="keterangan" id="add-keterangan"
                                  style="height:100px;" placeholder="Masukkan keterangan..." required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" name="tambahNilai" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Komentar -->
<div class="modal fade" id="modaltambahKomentar">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Komentar Direksi dan Dewan Komisaris</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="formKomentar" data-action="<?= base_url('Risikokredit/tambahKomentar') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="faktor_id" id="comment-faktor-id">
                    <input type="hidden" name="kodebpr" value="<?= esc($kodebpr ?? '') ?>">
                    <div class="form-group">
                        <label>Komentar Saat Ini:</label>
                        <ul id="komentarLamaList" style="list-style-type:none;padding-left:0;">
                            <li>Memuat komentar...</li>
                        </ul>
                    </div>
                    <?php if (
                        ($userInGroupAdmin ?? false) || ($userInGroupDekom ?? false) ||
                        ($userInGroupDireksi ?? false) || ($userInGroupPE ?? false) ||
                        ($userInGroupDekom2 ?? false) || ($userInGroupDireksi2 ?? false)
                    ): ?>
                    <input type="hidden" name="fullname" value="<?= esc($fullname ?? '') ?>">
                    <div class="form-group">
                        <label for="komentar">Tambahkan Komentar Baru:</label>
                        <textarea class="form-control" name="komentar" id="komentar" style="height:100px;"></textarea>
                    </div>
                    <?php endif; ?>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" name="tambahKomentar" class="btn btn-primary">Simpan Komentar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<!-- JAVASCRIPT -->
<script>
(function () {
    'use strict';

    /* ══════════════════════════════════════════════════════════════
       CONSTANTS
    ══════════════════════════════════════════════════════════════ */
    const GLOBAL_KODEBPR    = '<?= esc($kodebpr ?? '') ?>';
    const GLOBAL_PERIODE_ID = '<?= esc($activePeriodeId ?? '') ?>';
    const BASE_URL          = '<?= base_url() ?>';
    const PENILAIAN_CONFIG  = <?= json_encode($penilaianConfig ?? []) ?>;
    const CSRF_TOKEN_NAME   = '<?= csrf_token() ?>';
    let   CSRF_HASH         = '<?= csrf_hash() ?>';

    const USER_IN_GROUP_ADMIN = <?= json_encode($userInGroupAdmin ?? false) ?>;
    const USER_IN_GROUP_PE    = <?= json_encode($userInGroupPE ?? false) ?>;

    /* ══════════════════════════════════════════════════════════════
       UTILITIES
    ══════════════════════════════════════════════════════════════ */
    function debounce(fn, ms) {
        let t;
        return function (...args) { clearTimeout(t); t = setTimeout(() => fn.apply(this, args), ms); };
    }
    const $id  = id => document.getElementById(id);
    const escH = s  => String(s ?? '').replace(/[&<>"']/g,
        m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
    const fmtCur = n =>
        new Intl.NumberFormat('id-ID', {style:'currency',currency:'IDR',minimumFractionDigits:0}).format(n);

    function getRaw(id) {
        const el = $id(id); if (!el) return 0;
        const raw = el.dataset.raw !== undefined ? el.dataset.raw : el.value.replace(/[^\d]/g,'');
        return parseFloat(raw) || 0;
    }
    function setHidden(id, val) { const el=$id(id); if(el) el.value=val.toFixed(4); }
    function setText(id, val)   { const el=$id(id); if(el) el.textContent=val; }

    function refreshCsrf() {
        const m = document.querySelector('meta[name="csrf-token"]') ||
                  document.querySelector('meta[name="X-CSRF-TOKEN"]');
        if (m) CSRF_HASH = m.getAttribute('content');
    }

    /* Inflight abort controller per key */
    const _ctrl = {};
    function abortPrev(key) {
        if (_ctrl[key]) _ctrl[key].abort();
        _ctrl[key] = new AbortController();
        return _ctrl[key].signal;
    }

    /* FETCH HELPER */
    function postJSON(url, data, abortKey) {
        data[CSRF_TOKEN_NAME] = CSRF_HASH;
        const fullUrl = url.startsWith('http') ? url : BASE_URL + url.replace(/^\//, '');
        const opts = {
            method : 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
            body   : new URLSearchParams(data),
        };
        if (abortKey) opts.signal = abortPrev(abortKey);
        return fetch(fullUrl, opts).then(r => {
            refreshCsrf();
            if (!r.ok) return r.text().then(t => { throw new Error(`HTTP ${r.status}: ${t.substring(0,200)}`); });
            return r.json();
        });
    }

    /* TOAST */
    function showToast(title, message, type) {
        const colors = {success:'#28a745',error:'#dc3545',warning:'#e0a800',info:'#17a2b8'};
        const icons  = {success:'check-circle',error:'exclamation-circle',warning:'exclamation-triangle',info:'info-circle'};
        const wrap   = $id('toast-wrapper');
        if (!wrap) return;
        const t      = document.createElement('div');
        t.className  = 'toast-notification';
        t.style.background = colors[type] || colors.info;
        t.innerHTML  = `<i class="fas fa-${icons[type]||'info-circle'}"></i>
                        <div><strong>${escH(title)}</strong><br><span>${escH(message)}</span></div>`;
        wrap.appendChild(t);
        requestAnimationFrame(() => t.classList.add('show'));
        setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 300); }, 3500);
    }
    /* Expose agar sync module bisa pakai */
    window.showToast = showToast;

    /* MODAL HELPER */
    const _modalInst = {};
    function modalShow(id) {
        const el=$id(id); if(!el) return;
        if (window.bootstrap?.Modal) {
            if(!_modalInst[id]) {
                _modalInst[id] = new bootstrap.Modal(el, {backdrop:true,keyboard:true});
                el.addEventListener('hidden.bs.modal', () => delete _modalInst[id], {once:true});
            }
            _modalInst[id].show();
        } else if (window.$) window.$('#'+id).modal('show');
    }
    function modalHide(id) {
        const el=$id(id); if(!el) return;
        if(_modalInst[id]) _modalInst[id].hide();
        else if(window.bootstrap?.Modal) { const i=bootstrap.Modal.getInstance(el); if(i) i.hide(); }
        else if(window.$) window.$('#'+id).modal('hide');
    }
    document.querySelectorAll('[data-bs-dismiss="modal"]').forEach(btn =>
        btn.addEventListener('click', function() {
            const m=this.closest('.modal'); if(m) modalHide(m.id);
        })
    );

    /* COLLAPSE */
    document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(btn =>
        btn.addEventListener('click', function() {
            const t=document.querySelector(this.getAttribute('data-bs-target'));
            if(t) t.classList.toggle('show');
        })
    );

    /* CURRENCY INPUTS */
    function setupCurrencyInputs() {
        document.querySelectorAll('.currency-input').forEach(inp => {
            if(inp.dataset.raw && parseFloat(inp.dataset.raw))
                inp.value = parseFloat(inp.dataset.raw).toLocaleString('id-ID');
            const fmt = function() {
                const d=this.value.replace(/[^\d]/g,'');
                this.dataset.raw=d;
                this.value=d?d.replace(/\B(?=(\d{3})+(?!\d))/g,'.') : '';
            };
            inp.addEventListener('input', fmt);
            inp.addEventListener('paste', function() { setTimeout(fmt.bind(this),0); });
        });
    }

    /* OFFCANVAS */
    function offcanvasShow(id) {
        const el=$id(id); if(!el) return;
        let bd=$id('rk-oc-backdrop');
        if(!bd) {
            bd=document.createElement('div');
            bd.id='rk-oc-backdrop'; bd.className='offcanvas-backdrop';
            bd.addEventListener('click', offcanvasHideAll);
            document.body.appendChild(bd);
        }
        el.classList.add('show'); bd.classList.add('show'); document.body.classList.add('offcanvas-open');
    }
    function offcanvasHideAll() {
        document.querySelectorAll('.offcanvas.show').forEach(el => el.classList.remove('show'));
        const bd=$id('rk-oc-backdrop');
        if(bd) { bd.classList.remove('show'); setTimeout(()=>bd.remove(),300); }
        document.body.classList.remove('offcanvas-open'); document.body.style.overflow='';
    }
    let _ocAttached=false;
    function attachOffcanvasListeners() {
        if(_ocAttached) return; _ocAttached=true;
        document.querySelectorAll('[data-bs-toggle="offcanvas"]').forEach(btn =>
            btn.addEventListener('click', () => {
                const t=btn.getAttribute('data-bs-target')?.replace('#',''); if(t) offcanvasShow(t);
            })
        );
        document.querySelectorAll('[data-bs-dismiss="offcanvas"]').forEach(btn =>
            btn.addEventListener('click', offcanvasHideAll)
        );
    }

    /* KALKULATOR 1 — Komposisi Portofolio Aset */
    window.calculateRiskKalkulator = function() {
        const aba=getRaw('aba'),kydbank=getRaw('kydbank'),kydpihak3=getRaw('kydpihak3');
        const totalaset=getRaw('totalaset'),total25=getRaw('total25debitur');
        const perdagangan=getRaw('perdagangan'),jasa=getRaw('jasa'),konsumsi=getRaw('konsumsirumah');
        const kydgross=kydbank+kydpihak3, asetprod=aba+kydgross, sektorekonomi=perdagangan+jasa+konsumsi;
        const rasioAP=totalaset>0?(asetprod/totalaset)*100:0;
        const rasioKD=asetprod>0?(kydgross/asetprod)*100:0;
        const rasio25=kydgross>0?(total25/kydgross)*100:0;
        const rasioE=kydgross>0?(sektorekonomi/kydgross)*100:0;

        setHidden('kydgross_hidden',kydgross);
        setHidden('asetproduktif_hidden',asetprod);
        setHidden('sektorekonomi_hidden',sektorekonomi);
        setHidden('rasioasetproduktif_submit',rasioAP);
        setHidden('rasiokreditdiberikan_submit',rasioKD);
        setHidden('rasio25debitur_submit',rasio25);
        setHidden('rasioekonomi_submit',rasioE);

        setText('asetproduktif',fmtCur(asetprod));
        setText('kydgross',fmtCur(kydgross));
        setText('totalaset_display',fmtCur(totalaset));
        setText('rasioasetproduktif',rasioAP.toFixed(2)+'%');
        setText('rasiokreditdiberikan',rasioKD.toFixed(2)+'%');
        setText('rasio25debitur',rasio25.toFixed(2)+'%');
        setText('sektorekonomi',fmtCur(sektorekonomi));
        setText('rasioekonomi',rasioE.toFixed(2)+'%');

        const det=$id('detailResult'); if(det) det.style.display='block';
        const btn=$id('btnInsertRasio');
        if(btn) btn.style.display='block';
        const res=$id('riskResult');
        if(res){res.innerHTML='<div style="font-size:18px;color:#28a745;">✓ Perhitungan Selesai</div>';res.style.display='block';}
    };

    /* KALKULATOR 2 — Kualitas Aset */
    window.calculateRiskKalkulator2 = function() {
        const abanpl=getRaw('abanpl'),kydnpl3=getRaw('kydnpl3'),kydnpl4=getRaw('kydnpl4'),kydnpl5=getRaw('kydnpl5');
        const dpk2=getRaw('kreditdpk2'),bermasalah=getRaw('kreditbermasalah'),restruk=getRaw('kreditrestruktur1');
        let ap=parseFloat($id('asetproduktif_hidden')?.value)||0;
        let kydg=parseFloat($id('kydgross_hidden')?.value)||0;
        if(ap<=0) ap=<?= json_encode((float)($kalkulatorData['asetproduktif']??0)) ?>;
        if(kydg<=0) kydg=<?= json_encode((float)($kalkulatorData['kydgross']??0)) ?>;
        if(ap<=0&&kydg<=0){
            showToast('Peringatan','Silakan isi Kalkulator Komposisi Portofolio Aset terlebih dahulu!','warning');
            return;
        }
        const kydkolek=kydnpl3+kydnpl4+kydnpl5, apBm=abanpl+kydkolek, kkr=dpk2+kydkolek+restruk;
        const rAPB=ap>0?(apBm/ap)*100:0;
        const rKB=kydg>0?(bermasalah/kydg)*100:0;
        const rKKR=kydg>0?(kkr/kydg)*100:0;

        setHidden('kydkoleknpl_hidden',kydkolek);
        setHidden('asetproduktifbermasalah_hidden',apBm);
        setHidden('rasioasetproduktifbermasalah_submit',rAPB);
        setHidden('rasiokreditbermasalah_submit',rKB);
        setHidden('rasiokreditkualitasrendah_submit',rKKR);

        setText('kydkoleknpl_display',fmtCur(kydkolek));
        setText('asetproduktifbermasalah_display',fmtCur(apBm));
        setText('asetproduktif_display2',fmtCur(ap));
        setText('rasioasetproduktifbermasalah_display',rAPB.toFixed(2)+'%');
        setText('rasiokreditbermasalah_display',rKB.toFixed(2)+'%');
        setText('rasiokreditkualitasrendah_display',rKKR.toFixed(2)+'%');

        const det=$id('detailResult2'); if(det) det.style.display='block';
        const btn=$id('btnInsertRasio2');
        if(btn) btn.style.display='block';
        const res=$id('riskResult2');
        if(res){res.innerHTML='<div style="font-size:18px;color:#28a745;">✓ Perhitungan Selesai</div>';res.style.display='block';}
    };

    /* INSERT RASIO TO KERTAS KERJA */
    function doInsertRasio(url, data, btnId) {
        const btn=$id(btnId), orig=btn.innerHTML;
        btn.disabled=true; btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Memproses...';
        postJSON(url, data)
            .then(r => {
                btn.disabled=false; btn.innerHTML=orig;
                if(r.success||r.status==='success') showToast('Berhasil',r.message||'Berhasil','success');
                else showToast('Error',r.message||'Gagal','error');
            })
            .catch(err=>{btn.disabled=false;btn.innerHTML=orig;showToast('Error','Kesalahan: '+err.message,'error');});
    }

    window.insertRasioToKertasKerja = function() {
        const rap=$id('rasioasetproduktif_submit')?.value,
              rkd=$id('rasiokreditdiberikan_submit')?.value;
        const r25=$id('rasio25debitur_submit')?.value,
              re=$id('rasioekonomi_submit')?.value;
        if(!rap&&!rkd&&!r25&&!re){showToast('Error','Hitung terlebih dahulu','error');return;}
        doInsertRasio('Risikokredit/insertRasioToKertasKerja',{
            aba:getRaw('aba'),kydbank:getRaw('kydbank'),kydpihak3:getRaw('kydpihak3'),
            totalaset:getRaw('totalaset'),total25debitur:getRaw('total25debitur'),
            perdagangan:getRaw('perdagangan'),jasa:getRaw('jasa'),konsumsirumah:getRaw('konsumsirumah'),
            rasioasetproduktif:rap,rasiokreditdiberikan:rkd,rasio25debitur:r25,rasioekonomi:re,
        },'btnInsertRasio');
    };

    window.insertRasioKualitasAsetToKertasKerja = function() {
        const rapb=$id('rasioasetproduktifbermasalah_submit')?.value;
        const rkb=$id('rasiokreditbermasalah_submit')?.value,
              rkkr=$id('rasiokreditkualitasrendah_submit')?.value;
        if(!rapb&&!rkb&&!rkkr){showToast('Error','Hitung terlebih dahulu','error');return;}
        doInsertRasio('Risikokredit/insertRasioKualitasAsetToKertasKerja',{
            abanpl:getRaw('abanpl'),kydnpl3:getRaw('kydnpl3'),kydnpl4:getRaw('kydnpl4'),
            kydnpl5:getRaw('kydnpl5'),kreditdpk2:getRaw('kreditdpk2'),
            kreditbermasalah:getRaw('kreditbermasalah'),kreditrestruktur1:getRaw('kreditrestruktur1'),
            rasioasetproduktifbermasalah:rapb,rasiokreditbermasalah:rkb,rasiokreditkualitasrendah:rkkr,
        },'btnInsertRasio2');
    };

    /* RATING OPTIONS GENERATOR */
    function generateRatingOptions(faktorId, containerId) {
        const cfg=PENILAIAN_CONFIG[faktorId]||PENILAIAN_CONFIG['default']||{};
        const container=$id(containerId); if(!container) return cfg;
        let html='';
        for(let i=1;i<=5;i++){
            const desc=(cfg.descriptions&&cfg.descriptions[i])||'Tidak ada deskripsi';
            html+=`<a href="#" class="list-group-item list-group-item-action py-2 px-3"
                data-value="${i}" data-description="${escH(desc)}">
                <h6 class="mb-1"><b>Nilai ${i}</b></h6>
                <p class="mb-0 small">${desc}</p></a>`;
        }
        container.innerHTML=html; return cfg;
    }

    function updateCatatanInfo(cfg, prefix) {
        let html='';
        if(cfg.threshold) html+=`<strong>Threshold:</strong> ${cfg.threshold}<br>`;
        if(cfg.catatan)   html+=`<strong>Catatan:</strong> ${cfg.catatan}`;
        const el=$id(prefix+'-catatan-info'), tx=$id(prefix+'-catatan-text');
        if(!el||!tx) return;
        if(html){tx.innerHTML=html;el.classList.remove('d-none');}else el.classList.add('d-none');
    }

    /* MODAL OPEN HANDLERS */
    document.addEventListener('click', function(e) {
        /* btn-edit */
        const btnEdit = e.target.closest('.btn-edit');
        if(btnEdit) {
            $id('edit-faktor-id').value         = btnEdit.dataset.id;
            $id('edit-parameterpenilaian').value = btnEdit.dataset.title;
            $id('edit-penilaiankredit').value    = btnEdit.dataset.penilaiankredit;
            $id('edit-keterangan').value         = btnEdit.dataset.keterangan||'';
            const cfg=generateRatingOptions(btnEdit.dataset.id,'editRatingList');
            updateCatatanInfo(cfg,'edit');
            document.querySelectorAll('#editRatingList .list-group-item').forEach(li=>{
                if(li.dataset.value==btnEdit.dataset.penilaiankredit){
                    li.classList.add('active');
                    $id('editSelectedValue').textContent='Nilai '+btnEdit.dataset.penilaiankredit;
                }
            });
            modalShow('modalUbah');
            return;
        }

        /* btn-add */
        const btnAdd = e.target.closest('.btn-add');
        if(btnAdd) {
            $id('add-faktor-id').value         = btnAdd.dataset.id;
            $id('add-parameterpenilaian').value = btnAdd.dataset.title;
            $id('add-penilaiankredit').value    = '';
            $id('add-keterangan').value         = '';
            $id('addSelectedValue').textContent = 'Belum ada';
            const cfg=generateRatingOptions(btnAdd.dataset.id,'addRatingList');
            updateCatatanInfo(cfg,'add');
            modalShow('modaltambahNilai');
            return;
        }

        /* btn-edit-keterangan */
        const btnEK = e.target.closest('.btn-edit-keterangan');
        if(btnEK) {
            $id('edit-faktor-id-kesimpulan').value          = btnEK.dataset.id;
            $id('edit-parameterpenilaian-kesimpulan').value = btnEK.dataset.title;
            $id('edit-keterangan-kesimpulan').value         = btnEK.dataset.keterangan||'';
            modalShow('modalUbahkesimpulan');
            return;
        }

        /* komentar button */
        const btnKom = e.target.closest('.komentar-button');
        if(btnKom) {
            const fid=btnKom.dataset.faktorId;
            $id('comment-faktor-id').value=fid;
            loadKomentar(fid);
            markRead(fid);
            modalShow('modaltambahKomentar');
            return;
        }
    });



    /* RATING ITEM CLICK */
    document.addEventListener('click', function(e) {
        const item=e.target.closest('#addRatingList .list-group-item,#editRatingList .list-group-item');
        if(!item) return; e.preventDefault();
        const list=item.closest('.list-group');
        list.querySelectorAll('.list-group-item').forEach(li=>li.classList.remove('active'));
        item.classList.add('active');
        const isAdd=list.id==='addRatingList', pfx=isAdd?'add':'edit';
        $id(pfx+'-penilaiankredit').value     = item.dataset.value;
        $id(pfx+'-penjelasanpenilaian').value = item.dataset.description;
        $id(pfx+'SelectedValue').textContent  = 'Nilai '+item.dataset.value;
    });

    /* APPROVAL FORM (inline AJAX) */
    document.addEventListener('submit', function(e) {
        const form=e.target.closest('.form-approval');
        if(!form) return;
        e.preventDefault();
        const btn=form.querySelector('button'), orig=btn.innerHTML;
        btn.disabled=true; btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>';
        const data=new URLSearchParams(new FormData(form));
        data.set(CSRF_TOKEN_NAME,CSRF_HASH);
        fetch(form.action,{
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
            body:data,
        })
        .then(()=>{
            const isApp=form.action.includes('accdir2');
            btn.className=`btn btn-sm ${isApp?'btn-success':'btn-dark'}`;
            btn.innerHTML=`<i class="fas fa-${isApp?'check':'times'}-circle"></i>`;
            form.action=form.action.replace(/(accdir2|unapprovedir2)/,isApp?'unapprovedir2':'accdir2');
            const ci=form.querySelector(`[name="${CSRF_TOKEN_NAME}"]`); if(ci) ci.value=CSRF_HASH;
            btn.disabled=false;
            showToast('Berhasil','Status approval diubah','success');
        })
        .catch(()=>{btn.disabled=false;btn.innerHTML=orig;showToast('Error','Gagal','error');});
    });

    /* AJAX FORM HANDLER FACTORY */
    function makeAjaxFormHandler(formId, onSuccess, opts={}) {
        const form=$id(formId); if(!form) return;
        form.addEventListener('submit', function(e){e.preventDefault();}, true);
        form.addEventListener('submit', debounce(function(e) {
            e.preventDefault(); e.stopPropagation();
            const btn=this.querySelector('button[type="submit"]'); if(!btn) return;
            const orig=btn.innerHTML; btn.disabled=true;
            btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
            const data=new URLSearchParams(new FormData(this));
            data.set(CSRF_TOKEN_NAME,CSRF_HASH);
            const url=this.dataset.action||(this.action!==window.location.href?this.action:null);
            if(!url){btn.disabled=false;btn.innerHTML=orig;showToast('Error','URL tidak ditemukan','error');return;}
            fetch(url,{
                method:'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
                body:data,
            })
            .then(r=>{refreshCsrf();return r.json();})
            .then(r=>{
                btn.disabled=false; btn.innerHTML=orig;
                if(r.success||r.status==='success'){
                    showToast('Berhasil',opts.successMsg||'Data berhasil disimpan','success');
                    if(opts.hideModal) modalHide(opts.hideModal);
                    if(onSuccess) onSuccess(r);
                    if(opts.reload) setTimeout(()=>location.reload(),800);
                } else {
                    showToast('Error',r.message||'Gagal menyimpan','error');
                }
            })
            .catch(err=>{
                btn.disabled=false;btn.innerHTML=orig;
                showToast('Error','Koneksi gagal: '+err.message,'error');
            });
        },400));
    }

    makeAjaxFormHandler('formUbah',
        r=>{ if(r.peringkat13) updateBadgePeringkat13(r.peringkat13); },
        {hideModal:'modalUbah', successMsg:'Data berhasil diubah', reload:true}
    );
    makeAjaxFormHandler('formUbahKesimpulan', null,
        {hideModal:'modalUbahkesimpulan', successMsg:'Keterangan berhasil diubah', reload:true}
    );
    makeAjaxFormHandler('formTambahNilai',
        r=>{ if(r.peringkat13) updateBadgePeringkat13(r.peringkat13); $id('addSelectedValue').textContent='Belum ada'; },
        {hideModal:'modaltambahNilai', successMsg:'Nilai berhasil ditambahkan', reload:true}
    );
    makeAjaxFormHandler('formKomentar',
        ()=>{
            const fid=$id('comment-faktor-id')?.value;
            loadKomentar(fid);
            pollUnreadCounts();
            const ta=$id('komentar'); if(ta) ta.value='';
        },
        {successMsg:'Komentar berhasil ditambahkan'}
    );

    /* KOMENTAR */
    function loadKomentar(fid) {
        const list=$id('komentarLamaList');
        list.innerHTML='<li>Memuat komentar...</li>';
        postJSON('Risikokredit/getKomentarByFaktorId/'+fid,
                 {kodebpr:GLOBAL_KODEBPR, periode_id:GLOBAL_PERIODE_ID})
            .then(data=>{
                const rows=Array.isArray(data)?data:(data.data||[]);
                list.innerHTML=rows.length
                    ?rows.map(k=>`<li style="margin-bottom:6px;padding:6px;background:#f8f9fa;border-radius:4px;">
                        ${escH(k.komentar)}<br>
                        <small class="text-muted"><em>${escH(k.fullname)} &bull; ${k.created_at}</em></small>
                    </li>`).join('')
                    :'<li>Tidak ada komentar.</li>';
            })
            .catch(()=>{list.innerHTML='<li>Gagal memuat komentar.</li>';});
    }

    function markRead(fid) {
        postJSON('Risikokredit/markUserCommentsAsRead',
                 {faktor_id:fid, kodebpr:GLOBAL_KODEBPR, periode_id:GLOBAL_PERIODE_ID})
            .then(r=>{
                if(r.status==='success') {
                    const b=$id('notification-badge-'+fid);
                    if(b) { b.style.display='none'; b.textContent=''; }
                }
            })
            .catch(()=>{});
    }

    /* BADGE UPDATERS */
    const _peringkatLabels = {1:'Sangat Rendah',2:'Rendah',3:'Sedang',4:'Tinggi',5:'Sangat Tinggi'};
    const _badgeClasses    = {1:'badge-info',2:'badge-success',3:'badge-warning',4:'badge-danger',5:'badge-dark'};

    function updateBadgePeringkat13(p) {
        const badge=$id('badge-peringkat13'); if(!badge) return;
        badge.className=`badge badge-lg ${_badgeClasses[p]||'badge-secondary'}`;
        badge.style.cssText='font-size:1.1em;padding:.5em 1em;';
        badge.innerHTML=`<i class="fas fa-check-circle"></i> Peringkat penilaian: ${p} [${_peringkatLabels[p]||'N/A'}]`;
    }

    function updateMainApprovalBadge(approved, dt=null) {
        const badge=$id('approval-badge-main'); if(!badge) return;
        badge.className='badge '+(approved?'badge-success':'badge-secondary');
        badge.style.fontSize='14px';
        badge.innerHTML=approved
            ?`Disetujui oleh <strong>Direktur Utama</strong><br>${dt||'-'}`
            :`Belum Disetujui Seluruhnya<br>Oleh Direktur Utama`;
    }

    /* PERINGKAT 13 BUTTONS */
    const btnS13=$id('btnSimpanPeringkat13');
    if(btnS13) btnS13.addEventListener('click', debounce(function() {
        const p=$id('peringkat13_manual')?.value;
        if(!p){showToast('Error','Pilih peringkat terlebih dahulu','error');return;}
        const orig=this.innerHTML; this.disabled=true;
        this.innerHTML='<i class="fas fa-spinner fa-spin"></i>';
        postJSON('Risikokredit/ubahPeringkat13Manual',{penilaiankredit:p})
            .then(r=>{
                if(r.status==='success'){
                    showToast('Berhasil','Peringkat berhasil diubah','success');
                    setTimeout(()=>location.reload(),800);
                } else {
                    showToast('Error',r.message,'error');
                    this.disabled=false; this.innerHTML=orig;
                }
            })
            .catch(()=>{this.disabled=false;this.innerHTML=orig;showToast('Error','Gagal','error');});
    },400));

    const btnR13=$id('btnResetPeringkat13');
    if(btnR13) btnR13.addEventListener('click', debounce(function() {
        if(!confirm('Kembalikan ke perhitungan otomatis?')) return;
        const orig=this.innerHTML; this.disabled=true;
        this.innerHTML='<i class="fas fa-spinner fa-spin"></i>';
        postJSON('Risikokredit/resetPeringkat13Auto',{})
            .then(r=>{
                if(r.status==='success'){
                    showToast('Berhasil','Peringkat direset ke otomatis','success');
                    setTimeout(()=>location.reload(),800);
                } else {
                    showToast('Error',r.message,'error');
                    this.disabled=false; this.innerHTML=orig;
                }
            })
            .catch(()=>{this.disabled=false;this.innerHTML=orig;showToast('Error','Gagal','error');});
    },400));

    /* APPROVE LINKS (btnApproveSemua / btnUnapproveSemua) */
    function wireApproveLink(id, confirmMsg, successMsg, approved) {
        const lnk=$id(id); if(!lnk) return;
        lnk.addEventListener('click', function(e) {
            e.preventDefault();
            if(!confirm(confirmMsg)) return;
            let path;
            try{path=new URL(this.href).pathname;}catch(_){path=this.href.replace(/^https?:\/\/[^/]+/,'');}
            const orig=this.innerHTML; this.classList.add('disabled-btn');
            this.innerHTML='<i class="fas fa-spinner fa-spin"></i> Processing...';
            postJSON(path,{})
                .then(r=>{
                    showToast('Berhasil',successMsg,'success');
                    if(approved) updateMainApprovalBadge(true,new Date().toLocaleString('id-ID'));
                    else updateMainApprovalBadge(false);
                    this.classList.remove('disabled-btn'); this.innerHTML=orig;
                    setTimeout(()=>location.reload(),800);
                })
                .catch(err=>{
                    this.classList.remove('disabled-btn'); this.innerHTML=orig;
                    showToast('Error','Gagal: '+err.message,'error');
                });
        });
    }
    wireApproveLink('btnApproveSemua','Apakah Anda yakin ingin melakukan approval?','Semua data berhasil disetujui',true);
    wireApproveLink('btnUnapproveSemua','Batalkan semua approval?','Semua approval berhasil dibatalkan',false);

    /* UNREAD COMMENT POLLING */
    function pollUnreadCounts() {
        postJSON('Risikokredit/getAllUnreadCounts',
                 {kodebpr:GLOBAL_KODEBPR, periode_id:GLOBAL_PERIODE_ID},
                 'unreadPoll')
        .then(r => {
            if(r.status !== 'success') return;
            const counts = r.counts || {};
            document.querySelectorAll('.notification-badge').forEach(b => {
                b.style.display='none'; b.textContent='';
            });
            Object.entries(counts).forEach(([fid, cnt]) => {
                const b=$id('notification-badge-'+fid);
                if(b && cnt>0){ b.textContent=cnt; b.style.display='inline-flex'; }
            });
        })
        .catch(()=>{});
    }

    let _pollTimer=null;
    function startPolling(ms) {
        if(_pollTimer) clearInterval(_pollTimer);
        _pollTimer=setInterval(pollUnreadCounts, ms);
    }

    document.addEventListener('visibilitychange', ()=>{
        if(document.hidden) {
            startPolling(120_000);
        } else {
            pollUnreadCounts();
            startPolling(60_000);
        }
    });

    /* SYNC DARI PERIODE SEBELUMNYA */
    (function initSyncModule() {
        const syncBar        = $id('sync-bar');
        const syncBarText    = $id('sync-bar-text');
        const btnSyncTrigger = $id('btn-sync-trigger');
        const syncNodataBar  = $id('sync-nodata-bar');
        const syncNodataText = $id('sync-nodata-text');
        const syncModalInfo  = $id('sync-modal-info');
        const syncProgress   = $id('sync-progress');
        const syncForceChk   = $id('sync-force-overwrite');
        const btnSyncExecute = $id('btn-sync-execute');
        const toggleTrack = $id('sync-toggle-track');
        const toggleThumb = $id('sync-toggle-thumb');

        if (syncForceChk && toggleTrack && toggleThumb) {
            const toggleLabel = $id('sync-toggle-label');
            toggleLabel && toggleLabel.addEventListener('click', function(e) {
                if (e.target === syncForceChk) return;
                e.preventDefault();
                syncForceChk.checked = !syncForceChk.checked;
                toggleTrack.style.background = syncForceChk.checked ? '#1f2aa8' : '#ced4da';
                toggleThumb.style.left       = syncForceChk.checked ? '21px' : '3px';
            });
        }

        /* Cek elemen ada semua — kalau tidak ada, skip */
        if (!syncBar || !btnSyncTrigger || !btnSyncExecute) return;

        let _prevInfo       = null;
        let _currentHasData = false;
        let _checkDone      = false;

        /* ── STEP 1: check (ringan — hanya COUNT, 1x per load) ── */
        function checkPrevPeriode() {
            if (_checkDone) return;
            _checkDone = true;

            postJSON('Risikokredit/checkPrevPeriode', {})
                .then(r => {
                    if (r.status === 'available') {
                        _prevInfo       = r.prev_info;
                        _currentHasData = !!r.current_has_data;

                        const pi = r.prev_info;
                        syncBarText.innerHTML =
                            '<i class="fas fa-info-circle mr-1"></i>' +
                            'Data <strong>Semester ' + pi.semester + ' Tahun ' + pi.tahun + '</strong> ' +
                            'tersedia &mdash; ' + pi.faktor_count + ' form' +
                            (pi.has_kalkulator ? ' + kalkulator' : '') +
                            (_currentHasData
                                ? '. <em class="text-muted">Periode aktif sudah punya sebagian data.</em>'
                                : '. Periode aktif belum ada data.');
                        syncBar.style.display = '';

                    } else if (r.status === 'no_data' || r.status === 'no_prev') {
                        if (syncNodataText) syncNodataText.textContent = r.message || 'Tidak ada data periode sebelumnya.';
                        if (syncNodataBar) syncNodataBar.style.display = '';
                    }
                })
                .catch(() => { /* network error → silent */ });
        }

        /* STEP 2: buka modal konfirmasi */
        btnSyncTrigger.addEventListener('click', function () {
            if (!_prevInfo) return;
            const pi = _prevInfo;

            /* Update source card */
            const infoEl = $id('sync-modal-info');
            if (infoEl) {
                infoEl.innerHTML =
                    '<div style="font-size:13px;font-weight:600;color:#27500A;margin-bottom:2px;">Sumber data</div>' +
                    '<div style="font-size:15px;font-weight:600;color:#173404;">Semester ' + pi.semester + ' Tahun ' + pi.tahun + '</div>' +
                    '<div style="font-size:12px;color:#3B6D11;margin-top:3px;">' + pi.faktor_count + ' faktor penilaian tersedia</div>';
            }

            /* Tampilkan warning jika periode aktif sudah ada data */
            const warnEl = $id('sync-overwrite-warning');
            if (warnEl) warnEl.style.display = _currentHasData ? 'flex' : 'none';

            /* Reset toggle */
            if (syncForceChk)   syncForceChk.checked = false;
            if (toggleTrack)    toggleTrack.style.background = '#ced4da';
            if (toggleThumb)    toggleThumb.style.left = '3px';
            if (syncProgress)   syncProgress.style.display = 'none';

            btnSyncExecute.disabled = false;
            btnSyncExecute.innerHTML = '<i class="fas fa-sync-alt"></i> Mulai Sinkronisasi';

            modalShow('modalSyncConfirm');
        });

        /* STEP 3: eksekusi sync */
        btnSyncExecute.addEventListener('click', function () {
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
            if (syncProgress) syncProgress.style.display = 'block';

            const forceOverwrite = syncForceChk && syncForceChk.checked ? 1 : 0;

            postJSON('Risikokredit/syncFromPrevPeriode', {force_overwrite: forceOverwrite})
                .then(r => {
                    if (syncProgress) syncProgress.style.display = 'none';
                    modalHide('modalSyncConfirm');

                    if (r.status === 'success') {
                        showToast('Sinkronisasi Berhasil', r.message, 'success');
                        syncBar.style.display = 'none';
                        setTimeout(() => location.reload(), 1200);

                    } else if (r.status === 'no_change') {
                        showToast('Tidak Ada Perubahan', r.message, 'info');
                        if (!forceOverwrite) {
                            if (syncForceChk) syncForceChk.checked = true;
                            modalShow('modalSyncConfirm');
                        }
                        this.disabled = false;
                        this.innerHTML = '<i class="fas fa-sync-alt"></i> Mulai Sinkronisasi';

                    } else {
                        showToast('Gagal', r.message || 'Terjadi kesalahan saat sinkronisasi', 'error');
                        this.disabled = false;
                        this.innerHTML = '<i class="fas fa-sync-alt"></i> Mulai Sinkronisasi';
                    }
                })
                .catch(err => {
                    if (syncProgress) syncProgress.style.display = 'none';
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-sync-alt"></i> Mulai Sinkronisasi';
                    showToast('Error', 'Koneksi gagal: ' + err.message, 'error');
                });
        });

        /* ── Jalankan check setelah halaman stabil (delay 800ms) ── */
        setTimeout(checkPrevPeriode, 800);
    })();

    /* INIT */
    pollUnreadCounts();
    startPolling(60_000);
    setupCurrencyInputs();
    attachOffcanvasListeners();

})();
</script>


<!-- CSS -->
<style>
:root {
    --primary-color: linear-gradient(135deg, #141863 0%, #1f2aa8 100%);
    --success-color: #28a745;
    --danger-color: #dc3545;
    --dark-color: #343a40;
    --secondary-color: #6c757d;
    --light-bg: #f8f9fa;
    --white: #ffffff;
    --border-color: #dee2e6;
    --box-shadow: 0 2px 4px rgba(0,0,0,.1);
    --box-shadow-hover: 0 4px 8px rgba(0,0,0,.2);
    --border-radius: 8px;
    --transition: all .2s ease-in-out;
}

body { font-family: Arial, sans-serif; background: var(--light-bg); margin:0; padding:0; }
.container-fluid { margin-top:30px; }
h3,h4 { font-weight:bold; color:var(--dark-color); }

.alert { font-size:14px; padding:15px; border-radius:var(--border-radius); margin-bottom:20px; }
.alert-success { background:var(--success-color); color:#fff; }

.table { width:100%; background:var(--white); border-radius:var(--border-radius); box-shadow:var(--box-shadow); }
.table th,.table td { padding:12px 15px; text-align:center; border-bottom:1px solid var(--border-color); }
.table th { background:#f1f3f5; color:#495057; }

.card { margin-bottom:20px; border-radius:10px; box-shadow:var(--box-shadow); }
.card-body { padding:20px; }
.card-header { background:#f1f3f5; border-bottom:2px solid #ddd; font-weight:bold; }

/* ── Badges ── */
.badge { font-size:14px; padding:5px 10px; border-radius:20px; }
.badge-success   { background:var(--success-color); color:#fff; }
.badge-secondary { background:var(--secondary-color); color:#fff; }
.badge-primary   { background:#12131C; color:#fff; }
.badge-info      { background:#17a2b8; color:#fff; }
.badge-warning   { background:#ffc107; color:#212529; }
.badge-danger    { background:var(--danger-color); color:#fff; }
.badge-dark      { background:var(--dark-color); color:#fff; }

/* Notification badge — absolut di atas tombol komentar */
.notification-badge {
    position: absolute;
    top: -6px;
    right: -6px;
    min-width: 18px;
    height: 18px;
    padding: 0 4px;
    font-size: 10px;
    font-weight: 700;
    border-radius: 9px;
    background: var(--danger-color);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
    line-height: 1;
    pointer-events: none;
    z-index: 1;
}
.komentar-button { position: relative; }

/* Approval */
.approvaldir-card { border-radius:20px; background:var(--white); overflow:hidden; box-shadow:var(--box-shadow); }
.approvaldir-card-body { display:flex; flex-direction:column; justify-content:center; padding:15px; height:100%; }
.approval-badge-container { text-align:center; margin-bottom:6px; }
.approval-badge { background:var(--dark-color); color:#fff; font-size:.8em; font-weight:500; padding:5px 12px; border-radius:5px; display:inline-block; margin-bottom:7px; }
.approval-buttons-container { display:flex; justify-content:center; align-items:center; gap:9px; }
.approval-btn { flex:1; max-width:50%; padding:8px 13px; font-size:.95em; font-weight:500; border-radius:28px; text-align:center; text-decoration:none; white-space:nowrap; box-shadow:var(--box-shadow); transition:var(--transition); }
.approval-btn-approve { background:var(--success-color); border-color:var(--success-color); color:#fff; }
.approval-btn-approve:hover { background:#218838; transform:translateY(-1px); }
.approval-btn-reject { background:var(--danger-color); border-color:var(--danger-color); color:#fff; }
.approval-btn-reject:hover { background:#c82333; transform:translateY(-1px); }

/* ── Buttons ── */
.btn { font-size:14px; padding:8px 20px; border-radius:5px; cursor:pointer; transition:var(--transition); font-weight:500; border:1px solid transparent; }
.btn:hover:not(:disabled):not(.disabled-btn) { transform:scale(1.05); box-shadow:var(--box-shadow-hover); }
.btn-sm { padding:5px 10px; font-size:12px; }
.btn-primary { background:var(--primary-color); color:#fff; border-color:var(--primary-color); }
.btn-primary:hover { background:#0b5ed7; }
.btn-outline-primary { border:1px solid var(--primary-color); background:transparent; color:var(--primary-color); }
.btn-outline-primary:hover { background:var(--primary-color); color:#fff; }
.disabled-btn { opacity:.5; pointer-events:none; }
.btn-close { background:none; border:none; padding:.5rem; cursor:pointer; font-size:1.5rem; color:#000; display:inline-flex; align-items:center; justify-content:center; width:2rem; height:2rem; }

/* ── Nav ── */
.button-bar { display:flex; justify-content:space-between; align-items:center; width:100%; margin:15px 0; flex-wrap:wrap; gap:8px; }
.kpmr-btn { background:var(--primary-color); color:#fff; font-weight:600; padding:8px 20px; border-radius:6px; text-decoration:none; transition:var(--transition); }
.kpmr-btn:hover { background:#0d4a73; transform:translateY(-2px); color:#fff; }

/* ── Offcanvas ── */
.offcanvas { position:fixed; bottom:0; top:0; z-index:1045; display:flex; flex-direction:column; max-width:100%; visibility:hidden; background:#fff; transition:visibility .3s ease,transform .3s ease; }
.offcanvas-end { right:0; width:500px; transform:translateX(100%); }
.offcanvas.show { visibility:visible; transform:translateX(0); }
.offcanvas-backdrop { position:fixed; top:0; left:0; z-index:1040; width:100%; height:100%; background:rgba(0,0,0,.5); opacity:0; visibility:hidden; transition:opacity .3s ease,visibility .3s ease; }
.offcanvas-backdrop.show { opacity:1; visibility:visible; }
.offcanvas-header { display:flex; align-items:center; justify-content:space-between; padding:1rem 1.5rem; border-bottom:1px solid #e9ecef; }
.offcanvas-title { margin:0; font-size:1.25rem; font-weight:600; color:#212529; }
.offcanvas-body { flex:1; padding:1.5rem; overflow-y:auto; color:#6c757d; }
body.offcanvas-open { overflow:hidden; }

/* ── Modal ── */
.modal-header { background:var(--primary-color); color:#fff; font-size:18px; padding:15px; }
.modal-footer .btn { padding:10px 25px; font-size:16px; border-radius:var(--border-radius); }

/* ── Risk result ── */
.risk-result { padding:15px; border-radius:8px; margin-top:20px; text-align:center; font-weight:bold; display:none; }

/* ── Rating list ── */
.list-group-item.active { background:#007bff!important; border-color:#007bff!important; color:#fff!important; }
.list-group-item.active h6,.list-group-item.active p,.list-group-item.active b { color:#fff!important; }
.list-group-item:hover:not(.active) { background:#f8f9fa; cursor:pointer; }
.list-group-item h6 { font-size:1rem; margin-bottom:.25rem; }
.list-group-item p  { font-size:.875rem; line-height:1.4; }

/* ── Collapse ── */
.collapse { display:none; }
.collapse.show { display:block; }

/* ── Toast ── */
#toast-wrapper { position:fixed; top:20px; right:20px; z-index:9999; display:flex; flex-direction:column; gap:8px; max-width:min(360px,calc(100vw - 40px)); pointer-events:none; }
.toast-notification { border-radius:8px; color:#fff; display:flex; align-items:center; gap:12px; box-shadow:0 4px 12px rgba(0,0,0,.3); transform:translateX(400px); opacity:0; transition:transform .3s ease,opacity .3s ease; padding:14px 18px; min-width:250px; pointer-events:all; }
.toast-notification.show { transform:translateX(0); opacity:1; }
.toast-notification i { font-size:22px; }
.toast-notification div { flex:1; }

/* ── Form ── */
.form-label { font-weight:600; margin-top:10px; }
.input-group-text { background:#f8f9fa; }

/* Sync bar */
#sync-bar,
#sync-nodata-bar {
    border-radius: var(--border-radius);
    font-size: .9em;
    margin-bottom: 12px;
    padding: 10px 14px;
}
#sync-bar {
    background: #fff3cd;
    border: 1px solid #ffc107;
    color: #856404;
}
#sync-nodata-bar {
    background: #d1ecf1;
    border: 1px solid #bee5eb;
    color: #0c5460;
}
#btn-sync-trigger {
    flex-shrink: 0;
    font-size: .85em;
    padding: 4px 12px;
    border-radius: 5px;
    white-space: nowrap;
}
/* Sync modal — progress area */
#sync-progress {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 10px;
    font-size: .88em;
}
/* Custom-control polyfill jika Bootstrap 5 (tidak ada .custom-control) */
.custom-control { display:flex; align-items:flex-start; gap:8px; padding:4px 0; }
.custom-control-input { margin-top:3px; cursor:pointer; width:16px; height:16px; flex-shrink:0; }
.custom-control-label { cursor:pointer; line-height:1.4; }

/* Responsive */
@media (max-width:768px) {
    .container-fluid { margin-top:10px; padding-left:10px; padding-right:10px; }
    .card-body { padding:12px; }
    .offcanvas-end { width:100%; }
    .button-bar { flex-direction:column; align-items:stretch; }
    .kpmr-btn { text-align:center; }
    .approval-btn { font-size:.85em; padding:6px 10px; }
    .table th,.table td { padding:8px 10px; font-size:12px; }
    .badge.badge-lg { font-size:.85em!important; padding:.35em .6em!important; }
    #toast-wrapper { top:8px; right:8px; left:8px; max-width:none; }
    .toast-notification { min-width:auto; }
    .d-flex.justify-content-between { flex-direction:column; gap:8px; }
    .d-flex.justify-content-between .ml-3 { margin-left:0!important; }
    .modal-dialog { margin:8px; }
    #sync-bar,#sync-nodata-bar { flex-direction:column; align-items:flex-start!important; gap:8px; }
    #btn-sync-trigger { width:100%; }
}
@media (max-width:576px) {
    h3 { font-size:1.5rem; } h4 { font-size:1.2rem; }
    .approval-buttons-container { flex-direction:column; gap:5px; }
    .approval-btn { max-width:100%; }
    .btn-sm { font-size:11px; padding:4px 8px; }
    .card-header { flex-direction:column; align-items:flex-start!important; }
    .card-header>div { margin-top:6px; flex-wrap:wrap; }
    .border-left.pl-4 { padding-left:8px!important; }
    .d-flex.align-items-start { flex-direction:column; }
    .d-flex.align-items-start .ml-3 { margin-left:0!important; margin-top:8px; }
}
</style>


<!-- CSS -->
<style>
:root {
    --primary-color: linear-gradient(135deg, #141863 0%, #1f2aa8 100%);
    --success-color: #28a745;
    --danger-color: #dc3545;
    --dark-color: #343a40;
    --secondary-color: #6c757d;
    --light-bg: #f8f9fa;
    --white: #ffffff;
    --border-color: #dee2e6;
    --box-shadow: 0 2px 4px rgba(0,0,0,.1);
    --box-shadow-hover: 0 4px 8px rgba(0,0,0,.2);
    --border-radius: 8px;
    --transition: all .2s ease-in-out;
}

/* Base */
body { font-family: Arial, sans-serif; background: var(--light-bg); margin: 0; padding: 0; }
.container-fluid { margin-top: 30px; }
h3, h4 { font-weight: bold; color: var(--dark-color); }

/* Alert */
.alert { font-size: 14px; padding: 15px; border-radius: var(--border-radius); margin-bottom: 20px; }
.alert-success { background: var(--success-color); color: #fff; }

/* Table */
.table { width: 100%; background: var(--white); border-radius: var(--border-radius); box-shadow: var(--box-shadow); }
.table th, .table td { padding: 12px 15px; text-align: center; border-bottom: 1px solid var(--border-color); }
.table th { background: #f1f3f5; color: #495057; }

/* Card */
.card { margin-bottom: 20px; border-radius: 10px; box-shadow: var(--box-shadow); }
.card-body { padding: 20px; }
.card-header { background: #f1f3f5; border-bottom: 2px solid #ddd; font-weight: bold; }

/* Badges */
.badge { font-size: 14px; padding: 5px 10px; border-radius: 20px; }
.badge-success   { background: var(--success-color); color: #fff; }
.badge-secondary { background: var(--secondary-color); color: #fff; }
.badge-primary   { background: #12131C; color: #fff; }
.badge-info      { background: #17a2b8; color: #fff; }
.badge-warning   { background: #ffc107; color: #212529; }
.badge-danger    { background: var(--danger-color); color: #fff; }
.badge-dark      { background: var(--dark-color); color: #fff; }

/* Notification badge — absolut di atas tombol komentar */
.notification-badge {
    position: absolute;
    top: -6px;
    right: -6px;
    min-width: 18px;
    height: 18px;
    padding: 0 4px;
    font-size: 10px;
    font-weight: 700;
    border-radius: 9px;
    background: var(--danger-color);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
    line-height: 1;
    pointer-events: none;
    z-index: 1;
}
.komentar-button { position: relative; }

/* Approval card */
.approvaldir-card { border-radius: 20px; background: var(--white); overflow: hidden; box-shadow: var(--box-shadow); }
.approvaldir-card-body { display: flex; flex-direction: column; justify-content: center; padding: 15px; height: 100%; }
.approval-badge-container { text-align: center; margin-bottom: 6px; }
.approval-badge { background: var(--dark-color); color: #fff; font-size: .8em; font-weight: 500; padding: 5px 12px; border-radius: 5px; display: inline-block; margin-bottom: 7px; }
.approval-buttons-container { display: flex; justify-content: center; align-items: center; gap: 9px; }
.approval-btn { flex: 1; max-width: 50%; padding: 8px 13px; font-size: .95em; font-weight: 500; border-radius: 28px; text-align: center; text-decoration: none; white-space: nowrap; box-shadow: var(--box-shadow); transition: var(--transition); }
.approval-btn-approve { background: var(--success-color); border-color: var(--success-color); color: #fff; }
.approval-btn-approve:hover { background: #218838; transform: translateY(-1px); }
.approval-btn-reject { background: var(--danger-color); border-color: var(--danger-color); color: #fff; }
.approval-btn-reject:hover { background: #c82333; transform: translateY(-1px); }

/* ── Buttons ── */
.btn { font-size: 14px; padding: 8px 20px; border-radius: 5px; cursor: pointer; transition: var(--transition); font-weight: 500; border: 1px solid transparent; }
.btn:hover:not(:disabled):not(.disabled-btn) { transform: scale(1.05); box-shadow: var(--box-shadow-hover); }
.btn-sm { padding: 5px 10px; font-size: 12px; }
.btn-primary { background: var(--primary-color); color: #fff; border-color: var(--primary-color); }
.btn-primary:hover { background: #0b5ed7; }
.btn-outline-primary { border: 1px solid var(--primary-color); background: transparent; color: var(--primary-color); }
.btn-outline-primary:hover { background: var(--primary-color); color: #fff; }
.disabled-btn { opacity: .5; pointer-events: none; }
.btn-close { background: none; border: none; padding: .5rem; cursor: pointer; font-size: 1.5rem; color: #000; display: inline-flex; align-items: center; justify-content: center; width: 2rem; height: 2rem; }

/* ── Nav ── */
.button-bar { display: flex; justify-content: space-between; align-items: center; width: 100%; margin: 15px 0; flex-wrap: wrap; gap: 8px; }
.kpmr-btn { background: var(--primary-color); color: #fff; font-weight: 600; padding: 8px 20px; border-radius: 6px; text-decoration: none; transition: var(--transition); }
.kpmr-btn:hover { background: #0d4a73; transform: translateY(-2px); color: #fff; }

/* ── Offcanvas ── */
.offcanvas { position: fixed; bottom: 0; top: 0; z-index: 1045; display: flex; flex-direction: column; max-width: 100%; visibility: hidden; background: #fff; transition: visibility .3s ease, transform .3s ease; }
.offcanvas-end { right: 0; width: 500px; transform: translateX(100%); }
.offcanvas.show { visibility: visible; transform: translateX(0); }
.offcanvas-backdrop { position: fixed; top: 0; left: 0; z-index: 1040; width: 100%; height: 100%; background: rgba(0,0,0,.5); opacity: 0; visibility: hidden; transition: opacity .3s ease, visibility .3s ease; }
.offcanvas-backdrop.show { opacity: 1; visibility: visible; }
.offcanvas-header { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.5rem; border-bottom: 1px solid #e9ecef; }
.offcanvas-title { margin: 0; font-size: 1.25rem; font-weight: 600; color: #212529; }
.offcanvas-body { flex: 1; padding: 1.5rem; overflow-y: auto; color: #6c757d; }
body.offcanvas-open { overflow: hidden; }

/* ── Modal ── */
.modal-header { background: var(--primary-color); color: #fff; font-size: 18px; padding: 15px; }
.modal-footer .btn { padding: 10px 25px; font-size: 16px; border-radius: var(--border-radius); }

/* ── Risk result (kalkulator) ── */
.risk-result { padding: 15px; border-radius: 8px; margin-top: 20px; text-align: center; font-weight: bold; display: none; }

/* ── Rating list ── */
.list-group-item.active { background: #007bff !important; border-color: #007bff !important; color: #fff !important; }
.list-group-item.active h6,
.list-group-item.active p,
.list-group-item.active b { color: #fff !important; }
.list-group-item:hover:not(.active) { background: #f8f9fa; cursor: pointer; }
.list-group-item h6 { font-size: 1rem; margin-bottom: .25rem; }
.list-group-item p  { font-size: .875rem; line-height: 1.4; }

/* ── Collapse ── */
.collapse { display: none; }
.collapse.show { display: block; }

/* ── Toast ── */
#toast-wrapper {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 8px;
    max-width: min(360px, calc(100vw - 40px));
    pointer-events: none;
}
.toast-notification {
    border-radius: 8px;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,.3);
    transform: translateX(400px);
    opacity: 0;
    transition: transform .3s ease, opacity .3s ease;
    padding: 14px 18px;
    min-width: 250px;
    pointer-events: all;
}
.toast-notification.show { transform: translateX(0); opacity: 1; }
.toast-notification i { font-size: 22px; }
.toast-notification div { flex: 1; }

/* ── Form inputs ── */
.form-label { font-weight: 600; margin-top: 10px; }
.input-group-text { background: #f8f9fa; }

/* ── Sync bar ── */
#sync-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    background: #fff3cd;
    border: 1px solid #ffc107;
    border-radius: var(--border-radius);
    color: #856404;
    font-size: .9em;
    padding: 10px 14px;
    margin-bottom: 12px;
}
#sync-nodata-bar {
    background: #d1ecf1;
    border: 1px solid #bee5eb;
    border-radius: var(--border-radius);
    color: #0c5460;
    font-size: .9em;
    padding: 10px 14px;
    margin-bottom: 12px;
}
#btn-sync-trigger {
    flex-shrink: 0;
    font-size: .85em;
    padding: 4px 12px;
    white-space: nowrap;
}
#sync-progress {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 10px;
    font-size: .88em;
}

#sync-force-overwrite:checked ~ #sync-toggle-track,
.sync-toggle-checked #sync-toggle-track {
    background: #1f2aa8;
}
#sync-force-overwrite:checked ~ #sync-toggle-thumb,
.sync-toggle-checked #sync-toggle-thumb {
    left: 21px;
}

.custom-control { display: flex; align-items: flex-start; gap: 8px; padding: 4px 0; }
.custom-control-input { margin-top: 3px; cursor: pointer; width: 16px; height: 16px; flex-shrink: 0; }
.custom-control-label { cursor: pointer; line-height: 1.4; }


@media (max-width: 768px) {
    .container-fluid { margin-top: 10px; padding-left: 10px; padding-right: 10px; }
    .card-body { padding: 12px; }
    .offcanvas-end { width: 100%; }
    .button-bar { flex-direction: column; align-items: stretch; }
    .kpmr-btn { text-align: center; }
    .approval-btn { font-size: .85em; padding: 6px 10px; }
    .table th, .table td { padding: 8px 10px; font-size: 12px; }
    .badge.badge-lg { font-size: .85em !important; padding: .35em .6em !important; }
    #toast-wrapper { top: 8px; right: 8px; left: 8px; max-width: none; }
    .toast-notification { min-width: auto; }
    .d-flex.justify-content-between { flex-direction: column; gap: 8px; }
    .d-flex.justify-content-between .ml-3 { margin-left: 0 !important; }
    .modal-dialog { margin: 8px; }
    #sync-bar { flex-direction: column; align-items: flex-start; }
    #btn-sync-trigger { width: 100%; }
}
@media (max-width: 576px) {
    h3 { font-size: 1.5rem; }
    h4 { font-size: 1.2rem; }
    .approval-buttons-container { flex-direction: column; gap: 5px; }
    .approval-btn { max-width: 100%; }
    .btn-sm { font-size: 11px; padding: 4px 8px; }
    .card-header { flex-direction: column; align-items: flex-start !important; }
    .card-header > div { margin-top: 6px; flex-wrap: wrap; }
    .border-left.pl-4 { padding-left: 8px !important; }
    .d-flex.align-items-start { flex-direction: column; }
    .d-flex.align-items-start .ml-3 { margin-left: 0 !important; margin-top: 8px; }
}
</style>