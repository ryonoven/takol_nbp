<?php
$kodebpr = $kodebpr ?? null;
$periodeId = $periodeId ?? null;
$subkategori = 'Rasiogaji';
$currentUserId = (int) ($userId ?? 0);
$activePeriodeId = (int) ($periodeId ?? 0);
$row0 = $rasiogaji[0] ?? [];

$_canEdit = (($userInGroupPE ?? false) || ($userInGroupAdmin ?? false)) ? 'true' : 'false';
$_canComment = (($userInGroupAdmin || $userInGroupDekom || $userInGroupDireksi || $userInGroupPE || $userInGroupDekom2 || $userInGroupDireksi2) ?? false) ? 'true' : 'false';

$allAccdekomApproved = !empty($accdekomData)
    && array_reduce($accdekomData, fn($c, $i) => $c && $i['accdekom'] == 1, true);
$allAccdirutApproved = !empty($accdirutData)
    && array_reduce($accdirutData, fn($c, $i) => $c && $i['is_approved'] == 1, true);

$lastDekomAt = $allAccdekomApproved && !empty($accdekomData)
    ? date('d/m/Y H:i', strtotime($accdekomData[count($accdekomData) - 1]['accdekom_at']))
    : '';
$lastDirutAt = $allAccdirutApproved && !empty($accdirutData)
    ? date('d/m/Y H:i', strtotime($accdirutData[0]['approved_at']))
    : '';

$initialUnreadCount = $commentReadsModel->countUnreadCommentsForUserByFactor($subkategori, $kodebpr, $currentUserId, $activePeriodeId);

$bulanId = [
    '',
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember'
];
?>

<div id="pkd-toast-container"></div>

<div class="pkd-info-bar">
    <i class="fas fa-info-circle"></i>
    <?php if (!empty($bprData) && !empty($periodeDetail)): ?>
        <span><strong><?= esc($bprData['namabpr'] ?? 'Nama BPR') ?></strong> &mdash;
            Periode Pelaporan Tahun <?= esc($periodeDetail['tahun']) ?></span>
    <?php elseif (!empty($periodeDetail)): ?>
        <span><strong>Periode:</strong> Tahun <?= esc($periodeDetail['tahun']) ?></span>
    <?php else: ?>
        <span><strong>Periode belum ditentukan</strong></span>
    <?php endif; ?>
</div>

<a href="<?= base_url('ShowTransparansi') ?>" class="btn-kembali mb-3">
    <i class="fas fa-arrow-left"></i> Kembali
</a>

<?php if (session()->getFlashdata('message')): ?>
    <div class="pkd-flash pkd-flash-success">
        <i class="fas fa-check-circle"></i><?= esc(session()->getFlashdata('message')) ?>
    </div>
<?php endif; ?>
<?php if (session()->getFlashdata('err')): ?>
    <div class="pkd-flash pkd-flash-danger">
        <i class="fas fa-exclamation-circle"></i><?= esc(session()->getFlashdata('err')) ?>
    </div>
<?php endif; ?>

<div class="card card-body mb-3 shadow-sm">
    <div class="page-header">
        <div class="approval-badges-row mb-3">
            <div class="approval-status-badge <?= $allAccdekomApproved ? 'badge-status-success' : 'badge-status-secondary' ?>"
                id="badge-dekom">
                <i class="fas <?= $allAccdekomApproved ? 'fa-check-circle' : 'fa-clock' ?>"></i>
                <div>
                    <span class="approval-status-label">Komisaris Utama</span><br>
                    <span class="approval-status-sub">
                        <?= $allAccdekomApproved ? 'Disetujui &middot; ' . $lastDekomAt : 'Belum disetujui seluruhnya' ?>
                    </span>
                </div>
            </div>
            <div class="approval-status-badge <?= $allAccdirutApproved ? 'badge-status-success' : 'badge-status-secondary' ?>"
                id="badge-dirut">
                <i class="fas <?= $allAccdirutApproved ? 'fa-check-circle' : 'fa-clock' ?>"></i>
                <div>
                    <span class="approval-status-label">Direktur Utama</span><br>
                    <span class="approval-status-sub">
                        <?= $allAccdirutApproved ? 'Disetujui &middot; ' . $lastDirutAt : 'Belum disetujui seluruhnya' ?>
                    </span>
                </div>
            </div>
        </div>
        <h1 class="page-title"><?= $judul ?? '12. Rasio Gaji Tertinggi dan Gaji Terendah' ?></h1>
        <h3 class="page-subtitle">(E0600)</h3>
        <div class="page-subtitle">Tahun <?= esc($periodeDetail['tahun']) ?></div>
        <span class="badge-namabpr"><?= esc($bprData['namabpr'] ?? 'Nama BPR') ?></span>
        <div id="pkd-save-status" class="pkd-save-status save-idle" style="margin-left:auto;">
            <i class="fas fa-cloud" style="font-size:10px;"></i>
            <?= $_canEdit === 'true' ? 'Autosave aktif' : 'Read Only' ?>
        </div>
    </div>
</div>

<div id="pkd-skeleton">
    <?php for ($s = 0; $s < 3; $s++): ?>
        <div class="skel-card">
            <div class="skel skel-header"></div>
            <div class="skel-body">
                <?php for ($f = 0; $f < 4; $f++): ?>
                    <div class="skel skel-field"></div>
                <?php endfor; ?>
            </div>
        </div>
    <?php endfor; ?>
</div>

<form id="rasiogajiForm" style="display:none;">
    <input type="hidden" id="rg_user_id" value="<?= esc($userId ?? '-') ?>">
    <input type="hidden" id="rg_fullname" value="<?= esc($fullname ?? '-') ?>">
    <input type="hidden" id="rg_kodebpr" value="<?= esc($kodebpr ?? '') ?>">
    <input type="hidden" id="rg_periode" value="<?= esc($periodeId ?? '') ?>">

    <div class="pkd-card">
        <div class="pkd-section-header">
            <span class="pkd-section-num">1</span>
            <span class="pkd-section-title">
                Rasio gaji pegawai yang tertinggi (a) dan gaji pegawai yang terendah (b) dalam 1 (satu) tahun
            </span>
        </div>
        <div class="pkd-card-body">
            <div class="pkd-grid">
                <div class="pkd-field">
                    <label class="pkd-label" for="pegawaitinggi">a. Gaji pegawai tertinggi</label>
                    <div class="pkd-input-group <?= $_canEdit === 'false' ? 'readonly-group' : '' ?>">
                        <span class="pkd-addon">Rp</span>
                        <input type="text" id="pegawaitinggi" name="pegawaitinggi"
                            class="pkd-input pkd-currency rg-calc" value="<?= esc($row0['pegawaitinggi'] ?? '') ?>"
                            placeholder="0" <?= $_canEdit === 'false' ? 'readonly' : '' ?>>
                    </div>
                </div>
                <div class="pkd-field">
                    <label class="pkd-label" for="pegawairendah">b. Gaji pegawai terendah</label>
                    <div class="pkd-input-group <?= $_canEdit === 'false' ? 'readonly-group' : '' ?>">
                        <span class="pkd-addon">Rp</span>
                        <input type="text" id="pegawairendah" name="pegawairendah"
                            class="pkd-input pkd-currency rg-calc" value="<?= esc($row0['pegawairendah'] ?? '') ?>"
                            placeholder="0" <?= $_canEdit === 'false' ? 'readonly' : '' ?>>
                    </div>
                </div>
            </div>
            <div class="pkd-rasio-result-wrap">
                <div class="pkd-field" style="max-width:340px;margin:0 auto;">
                    <label class="pkd-label" style="text-align:center;">Rasio gaji pegawai tertinggi : terendah</label>
                    <div class="pkd-input-group readonly-group">
                        <input type="text" id="pegawaitinggirendah" name="pegawaitinggirendah"
                            class="pkd-input pkd-ratio-display" readonly placeholder="—"
                            value="<?= esc($row0['pegawaitinggirendah'] ?? '') ?>">
                        <span class="pkd-addon pkd-addon-right"
                            style="background:var(--brand-bg);color:var(--brand);font-weight:800;">x</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="pkd-card">
        <div class="pkd-section-header">
            <span class="pkd-section-num">2</span>
            <span class="pkd-section-title">
                Rasio gaji anggota direksi yang tertinggi (a) dan gaji anggota direksi yang terendah (b) dalam 1 (satu)
                tahun
            </span>
        </div>
        <div class="pkd-card-body">
            <div class="pkd-grid">
                <div class="pkd-field">
                    <label class="pkd-label" for="dirtinggi">a. Gaji direksi tertinggi</label>
                    <div class="pkd-input-group <?= $_canEdit === 'false' ? 'readonly-group' : '' ?>">
                        <span class="pkd-addon">Rp</span>
                        <input type="text" id="dirtinggi" name="dirtinggi" class="pkd-input pkd-currency rg-calc"
                            value="<?= esc($row0['dirtinggi'] ?? '') ?>" placeholder="0" <?= $_canEdit === 'false' ? 'readonly' : '' ?>>
                    </div>
                </div>
                <div class="pkd-field">
                    <label class="pkd-label" for="dirrendah">b. Gaji direksi terendah</label>
                    <div class="pkd-input-group <?= $_canEdit === 'false' ? 'readonly-group' : '' ?>">
                        <span class="pkd-addon">Rp</span>
                        <input type="text" id="dirrendah" name="dirrendah" class="pkd-input pkd-currency rg-calc"
                            value="<?= esc($row0['dirrendah'] ?? '') ?>" placeholder="0" <?= $_canEdit === 'false' ? 'readonly' : '' ?>>
                    </div>
                </div>
            </div>
            <div class="pkd-rasio-result-wrap">
                <div class="pkd-field" style="max-width:340px;margin:0 auto;">
                    <label class="pkd-label" style="text-align:center;">Rasio gaji direksi tertinggi : terendah</label>
                    <div class="pkd-input-group readonly-group">
                        <input type="text" id="direksitinggirendah" name="direksitinggirendah"
                            class="pkd-input pkd-ratio-display" readonly placeholder="—"
                            value="<?= esc($row0['direksitinggirendah'] ?? '') ?>">
                        <span class="pkd-addon pkd-addon-right"
                            style="background:var(--brand-bg);color:var(--brand);font-weight:800;">x</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="pkd-card">
        <div class="pkd-section-header">
            <span class="pkd-section-num">3</span>
            <span class="pkd-section-title">
                Rasio gaji anggota dewan komisaris yang tertinggi (a) dan gaji anggota dewan komisaris yang terendah (b)
                dalam 1 (satu) tahun
            </span>
        </div>
        <div class="pkd-card-body">
            <div class="pkd-grid">
                <div class="pkd-field">
                    <label class="pkd-label" for="dekomtinggi">a. Gaji dewan komisaris tertinggi</label>
                    <div class="pkd-input-group <?= $_canEdit === 'false' ? 'readonly-group' : '' ?>">
                        <span class="pkd-addon">Rp</span>
                        <input type="text" id="dekomtinggi" name="dekomtinggi" class="pkd-input pkd-currency rg-calc"
                            value="<?= esc($row0['dekomtinggi'] ?? '') ?>" placeholder="0" <?= $_canEdit === 'false' ? 'readonly' : '' ?>>
                    </div>
                </div>
                <div class="pkd-field">
                    <label class="pkd-label" for="dekomrendah">b. Gaji dewan komisaris terendah</label>
                    <div class="pkd-input-group <?= $_canEdit === 'false' ? 'readonly-group' : '' ?>">
                        <span class="pkd-addon">Rp</span>
                        <input type="text" id="dekomrendah" name="dekomrendah" class="pkd-input pkd-currency rg-calc"
                            value="<?= esc($row0['dekomrendah'] ?? '') ?>" placeholder="0" <?= $_canEdit === 'false' ? 'readonly' : '' ?>>
                    </div>
                </div>
            </div>
            <div class="pkd-rasio-result-wrap">
                <div class="pkd-field" style="max-width:340px;margin:0 auto;">
                    <label class="pkd-label" style="text-align:center;">Rasio gaji dewan komisaris tertinggi :
                        terendah</label>
                    <div class="pkd-input-group readonly-group">
                        <input type="text" id="dekomtinggirendah" name="dekomtinggirendah"
                            class="pkd-input pkd-ratio-display" readonly placeholder="—"
                            value="<?= esc($row0['dekomtinggirendah'] ?? '') ?>">
                        <span class="pkd-addon pkd-addon-right"
                            style="background:var(--brand-bg);color:var(--brand);font-weight:800;">x</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="pkd-card">
        <div class="pkd-section-header">
            <span class="pkd-section-num">4</span>
            <span class="pkd-section-title">
                Rasio gaji anggota direksi yang tertinggi (a) dan gaji anggota dewan komisaris yang tertinggi (b) dalam
                1 (satu) tahun
            </span>
        </div>
        <div class="pkd-card-body">
            <div class="pkd-grid">
                <div class="pkd-field">
                    <label class="pkd-label">a. Gaji direksi tertinggi</label>
                    <div class="pkd-input-group readonly-group">
                        <span class="pkd-addon">Rp</span>
                        <input type="text" id="dirtinggi_ref4" class="pkd-input pkd-currency" readonly
                            placeholder="Diisi dari seksi 2" value="<?= esc($row0['dirtinggi'] ?? '') ?>">
                    </div>
                    <span style="font-size:11px;color:var(--gray-400);margin-top:3px;">
                        <i class="fas fa-link" style="margin-right:3px;"></i>Sinkron dari seksi 2
                    </span>
                </div>
                <div class="pkd-field">
                    <label class="pkd-label">b. Gaji dewan komisaris tertinggi</label>
                    <div class="pkd-input-group readonly-group">
                        <span class="pkd-addon">Rp</span>
                        <input type="text" id="dekomtinggi_ref4" class="pkd-input pkd-currency" readonly
                            placeholder="Diisi dari seksi 3" value="<?= esc($row0['dekomtinggi'] ?? '') ?>">
                    </div>
                    <span style="font-size:11px;color:var(--gray-400);margin-top:3px;">
                        <i class="fas fa-link" style="margin-right:3px;"></i>Sinkron dari seksi 3
                    </span>
                </div>
            </div>
            <div class="pkd-rasio-result-wrap">
                <div class="pkd-field" style="max-width:340px;margin:0 auto;">
                    <label class="pkd-label" style="text-align:center;">Rasio gaji direksi tertinggi : dewan komisaris
                        tertinggi</label>
                    <div class="pkd-input-group readonly-group">
                        <input type="text" id="dirdekomtinggirendah" name="dirdekomtinggirendah"
                            class="pkd-input pkd-ratio-display" readonly placeholder="—"
                            value="<?= esc($row0['dirdekomtinggirendah'] ?? '') ?>">
                        <span class="pkd-addon pkd-addon-right"
                            style="background:var(--brand-bg);color:var(--brand);font-weight:800;">x</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="pkd-card">
        <div class="pkd-section-header">
            <span class="pkd-section-num">5</span>
            <span class="pkd-section-title">
                Rasio gaji direksi yang tertinggi (a) dan gaji pegawai yang tertinggi (b) dalam 1 (satu) tahun
            </span>
        </div>
        <div class="pkd-card-body">
            <div class="pkd-grid">
                <div class="pkd-field">
                    <label class="pkd-label">a. Gaji direksi tertinggi</label>
                    <div class="pkd-input-group readonly-group">
                        <span class="pkd-addon">Rp</span>
                        <input type="text" id="dirtinggi_ref5" class="pkd-input pkd-currency" readonly
                            placeholder="Diisi dari seksi 2" value="<?= esc($row0['dirtinggi'] ?? '') ?>">
                    </div>
                    <span style="font-size:11px;color:var(--gray-400);margin-top:3px;">
                        <i class="fas fa-link" style="margin-right:3px;"></i>Sinkron dari seksi 2
                    </span>
                </div>
                <div class="pkd-field">
                    <label class="pkd-label">b. Gaji pegawai tertinggi</label>
                    <div class="pkd-input-group readonly-group">
                        <span class="pkd-addon">Rp</span>
                        <input type="text" id="pegawaitinggi_ref5" class="pkd-input pkd-currency" readonly
                            placeholder="Diisi dari seksi 1" value="<?= esc($row0['pegawaitinggi'] ?? '') ?>">
                    </div>
                    <span style="font-size:11px;color:var(--gray-400);margin-top:3px;">
                        <i class="fas fa-link" style="margin-right:3px;"></i>Sinkron dari seksi 1
                    </span>
                </div>
            </div>
            <div class="pkd-rasio-result-wrap">
                <div class="pkd-field" style="max-width:340px;margin:0 auto;">
                    <label class="pkd-label" style="text-align:center;">Rasio gaji direksi tertinggi : pegawai
                        tertinggi</label>
                    <div class="pkd-input-group readonly-group">
                        <input type="text" id="dirpegawaitinggirendah" name="dirpegawaitinggirendah"
                            class="pkd-input pkd-ratio-display" readonly placeholder="—"
                            value="<?= esc($row0['dirpegawaitinggirendah'] ?? '') ?>">
                        <span class="pkd-addon pkd-addon-right"
                            style="background:var(--brand-bg);color:var(--brand);font-weight:800;">x</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="pkd-penjelasan-card">
        <div class="pkd-penjelasan-hdr">
            <span class="pkd-penjelasan-title">
                <i class="fas fa-file-alt" style="margin-right:6px;color:var(--brand);"></i>
                Penjelasan Lebih Lanjut (Opsional)
            </span>
            <?php if (($userInGroupPE ?? false) || ($userInGroupAdmin ?? false)): ?>
                <?php if (empty($penjelastindak)): ?>
                    <button type="button" class="btn-pkd-sm btn-pkd-add" data-toggle="modal"
                        data-target="#modalTambahketerangan">
                        <i class="fas fa-plus"></i> Tambah
                    </button>
                <?php else: ?>
                    <?php foreach ($penjelastindak as $penRow): ?>
                        <button type="button" class="btn-pkd-sm btn-pkd-edit" data-toggle="modal" data-target="#modaleditketerangan"
                            data-id="<?= esc($penRow['id']) ?>" data-tindaklanjut="<?= esc($penRow['tindaklanjut'] ?? '') ?>">
                            <i class="fas fa-edit"></i> Ubah
                        </button>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <div id="pkd-penjelasan-body" class="pkd-penjelasan-body">
            <?php if (empty($penjelastindak)): ?>
                <div class="pkd-penjelasan-empty">
                    <i class="fas fa-inbox" style="font-size:28px;margin-bottom:6px;display:block;"></i>
                    Tidak ada penjelasan lebih lanjut
                </div>
            <?php else: ?>
                <?php foreach ($penjelastindak as $penRow): ?>
                    <div class="pkd-penjelasan-content"><?= nl2br(esc($penRow['tindaklanjut'])) ?></div>
                    <?php if (($userInGroupPE ?? false) || ($userInGroupAdmin ?? false)): ?>
                        <div style="margin-top:8px;">
                            <button type="button" class="btn-pkd-sm btn-pkd-del btn-setnulltindak"
                                data-id="<?= esc($penRow['id']) ?>">
                                <i class="fas fa-trash"></i> Hapus
                            </button>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if (
        (($userInGroupAdmin || $userInGroupDekom) && !empty($accdekomData)) ||
        (($userInGroupAdmin || $userInGroupDireksi) && !empty($accdirutData))
    ): ?>
        <div class="approval-actions-row mt-3">
            <?php if (($userInGroupAdmin ?? false) || ($userInGroupDekom ?? false)): ?>
                <div class="action-card card shadow-sm" id="action-dekom">
                    <div class="card-body py-2 text-center">
                        <small class="d-block font-weight-bold text-dark mb-2">
                            <i class="fas fa-user-tie text-success"></i> Komisaris Utama
                        </small>
                        <div class="d-flex justify-content-center flex-wrap" style="gap:4px;">
                            <a href="<?= base_url('Rasiogaji/approveSemuaKom') ?>"
                                onclick="return confirm('Setujui semua data sebagai Komisaris Utama?')"
                                class="btn <?= $allAccdekomApproved ? 'btn-success' : 'btn-primary' ?> btn-sm"
                                id="btn-approve-dekom">
                                <i class="fas fa-check-double"></i> Setujui
                            </a>
                            <a href="<?= base_url('Rasiogaji/unapproveSemuaKom') ?>"
                                onclick="return confirm('Batalkan semua approval Komisaris Utama?')"
                                class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-ban"></i> Batal
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (($userInGroupAdmin ?? false) || ($userInGroupDireksi ?? false)): ?>
                <div class="action-card card shadow-sm <?= (isset($canApprove) && !$canApprove) ? 'blk-disabled' : '' ?>"
                    id="action-dirut">
                    <div class="card-body py-2 text-center">
                        <small class="d-block font-weight-bold text-dark mb-2">
                            <i class="fas fa-user-shield text-primary"></i> Direktur Utama
                        </small>
                        <?php if (isset($canApprove) && !$canApprove): ?>
                            <small class="text-muted d-block mb-1" style="font-size:10px;">
                                <i class="fas fa-lock"></i> Komisaris harus setuju dulu
                            </small>
                        <?php endif; ?>
                        <div class="d-flex justify-content-center flex-wrap" style="gap:4px;">
                            <a href="<?= base_url('Rasiogaji/approveSemuaDirut') ?>"
                                onclick="return confirm('Setujui semua data sebagai Direktur Utama?')"
                                class="btn <?= $allAccdirutApproved ? 'btn-success' : 'btn-primary' ?> btn-sm"
                                id="btn-approve-dirut">
                                <i class="fas fa-check-double"></i> Setujui
                            </a>
                            <a href="<?= base_url('Rasiogaji/unapproveSemuaDirut') ?>"
                                onclick="return confirm('Batalkan semua approval Direktur Utama?')"
                                class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-ban"></i> Batal
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="pkd-bottom-bar">
        <?php if (($userInGroupDekom || $userInGroupDireksi || $userInGroupPE || $userInGroupAdmin) ?? false): ?>
            <div class="pkd-komentar-wrap">
                <button type="button" class="btn-komentar-pkd" id="btn-komentar-rg" title="Komentar">
                    <i class="fas fa-comment-dots"></i>
                    <span class="btn-komentar-ripple"></span>
                </button>
                <span class="notif-dot" id="notif-rg"
                    style="display:<?= $initialUnreadCount > 0 ? 'inline-flex' : 'none' ?>">
                    <?= $initialUnreadCount ?>
                </span>
            </div>
        <?php endif; ?>
    </div>
</form>

<div class="pkd-nav-card">
    <div class="pkd-nav-hdr">Pilih Halaman</div>
    <div class="pkd-nav-body">
        <?php
        $navPages = [
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
            18 => 'Danasosial',
        ];
        foreach ($navPages as $n => $url): ?>
            <a class="nav-pg <?= $n == 12 ? 'active' : '' ?>" href="<?= base_url($url) ?>"><?= $n ?></a>
        <?php endforeach; ?>
    </div>
    <div style="text-align:center;padding:4px 0 8px;">
        <a href="<?= base_url('ShowTransparansi') ?>"
            style="font-size:12px;color:var(--gray-400);font-weight:600;">Kembali ke halaman transparansi</a>
    </div>
</div>

<div class="modal fade pkd-modal" id="modalTambahketerangan" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle" style="margin-right:8px;"></i>Tambah Penjelasan
                </h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="pkd-label">Penjelasan lebih lanjut (Opsional)</label>
                    <textarea class="form-control" id="tt-tindaklanjut" rows="5"
                        placeholder="Tulis penjelasan..."></textarea>
                </div>
                <div id="tindak-tambah-err" class="alert alert-danger mt-2" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-pkd-primary" id="btn-simpan-tindak"
                    style="padding:8px 20px;font-size:13px;"><i class="fas fa-save"></i> Simpan</button>
                <button type="button" class="btn-pkd-secondary" data-dismiss="modal"
                    style="padding:8px 16px;font-size:13px;">Batal</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade pkd-modal" id="modaleditketerangan" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit" style="margin-right:8px;"></i>Ubah Penjelasan</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="et-id">
                <div class="form-group">
                    <label class="pkd-label">Penjelasan lebih lanjut</label>
                    <textarea class="form-control" id="et-tindaklanjut" rows="5"
                        placeholder="Tulis penjelasan..."></textarea>
                </div>
                <div id="tindak-edit-err" class="alert alert-danger mt-2" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-pkd-primary" id="btn-simpan-edit-tindak"
                    style="padding:8px 20px;font-size:13px;"><i class="fas fa-save"></i> Simpan Perubahan</button>
                <button type="button" class="btn-pkd-secondary" data-dismiss="modal"
                    style="padding:8px 16px;font-size:13px;">Batal</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade pkd-modal" id="modalKomentar" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content komentar-modal-content">
            <div class="modal-header komentar-modal-header">
                <div class="komentar-modal-title-wrap">
                    <div class="komentar-modal-icon-wrap"><i class="fas fa-comments"></i></div>
                    <div>
                        <div class="komentar-modal-title">Komentar — Rasio Gaji</div>
                    </div>
                </div>
                <button type="button" class="komentar-close-btn" data-dismiss="modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body komentar-modal-body">
                <div class="komentar-list-label"><i class="fas fa-history"></i> Riwayat Komentar</div>
                <div id="komentar-list-wrapper" class="komentar-list-wrapper">
                    <div id="komentar-list">
                        <div class="komentar-loading"><i class="fas fa-spinner fa-spin"></i> Memuat...</div>
                    </div>
                </div>
                <?php if (($_canComment === 'true')): ?>
                    <div class="komentar-input-wrap">
                        <div class="komentar-input-label"><i class="fas fa-pen-alt"></i> Tulis Komentar</div>
                        <textarea id="k-teks" class="komentar-textarea" rows="3"
                            placeholder="Tuliskan komentar, catatan, atau arahan Anda..." maxlength="1000"></textarea>
                        <div class="komentar-input-footer">
                            <span class="komentar-hint"><i class="fas fa-keyboard"></i> Ctrl+Enter untuk kirim</span>
                            <span id="k-char-count" class="komentar-char">0 / 1000</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer komentar-modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
                    <i class="fas fa-times"></i> Tutup
                </button>
                <?php if (($_canComment === 'true')): ?>
                    <button type="button" class="btn btn-primary btn-sm" id="btn-kirim-komentar">
                        <i class="fas fa-paper-plane"></i> Kirim
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$_canEdit = (($userInGroupPE ?? false) || ($userInGroupAdmin ?? false)) ? 'true' : 'false';
$_canComment = (($userInGroupAdmin || $userInGroupDekom || $userInGroupDireksi || $userInGroupPE || $userInGroupDekom2 || $userInGroupDireksi2) ?? false) ? 'true' : 'false';
?>
<script>
    (function () {
        'use strict';

        var CFG = {
            base: '<?= rtrim(base_url(), '/') ?>',
            csrfName: '<?= csrf_token() ?>',
            csrfHash: '<?= csrf_hash() ?>',
            uid: <?= (int) ($userId ?? 0) ?>,
            fname: <?= json_encode($fullname ?? 'Unknown', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
            kbpr: <?= json_encode($kodebpr ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
            pid: <?= (int) ($periodeId ?? 0) ?>,
            canEdit: <?= $_canEdit ?>,
            canComment: <?= $_canComment ?>,
            pollBase: 10000,
        };

        function ready(fn) {
            if (typeof jQuery !== 'undefined') jQuery(fn);
            else setTimeout(function () { ready(fn); }, 40);
        }

        ready(function ($) {

            function esc(s) {
                return s == null ? '' : String(s)
                    .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
            }

            function debounce(fn, ms) {
                var t;
                return function () { var a = arguments, c = this; clearTimeout(t); t = setTimeout(function () { fn.apply(c, a); }, ms); };
            }

            function toast(msg, type) {
                type = type || 'success';
                var ic = { success: 'check-circle', error: 'exclamation-circle', info: 'info-circle' };
                var el = $('<div class="pkd-toast t-' + type + '"><i class="fas fa-' + ic[type] + '"></i>' + esc(msg) + '</div>');
                $('#pkd-toast-container').append(el);
                setTimeout(function () { el.addClass('out'); setTimeout(function () { el.remove(); }, 350); }, 3500);
            }

            function readCsrf() {
                var m = document.cookie.match(/ci_csrf_token=([^;]+)/);
                if (m) CFG.csrfHash = decodeURIComponent(m[1]);
            }

            function csrf() { readCsrf(); var d = {}; d[CFG.csrfName] = CFG.csrfHash; return d; }
            function updateCsrf(res) { readCsrf(); if (res && res.csrf_hash) CFG.csrfHash = res.csrf_hash; }

            function btnLoading(btn) {
                var ori = btn.html();
                btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
                return function () { btn.html(ori).prop('disabled', false); };
            }

            function timeAgo(s) {
                if (!s) return '-';
                var d = new Date(s.replace(' ', 'T')), diff = Math.floor((Date.now() - d.getTime()) / 60000);
                if (isNaN(diff) || diff < 1) return 'Baru saja';
                if (diff < 60) return diff + ' menit lalu';
                if (diff < 1440) return Math.floor(diff / 60) + ' jam lalu';
                return ('0' + d.getDate()).slice(-2) + '/' + ('0' + (d.getMonth() + 1)).slice(-2) + '/' + d.getFullYear();
            }

            var $saveStatus = $('#pkd-save-status');
            function setSaveStatus(state, text) {
                var icons = {
                    idle: '<i class="fas fa-cloud"       style="font-size:10px;"></i>',
                    typing: '<i class="fas fa-pencil-alt"  style="font-size:10px;"></i>',
                    saving: '<div class="pkd-spinner"></div>',
                    saved: '<i class="fas fa-check"       style="font-size:11px;"></i>',
                    error: '<i class="fas fa-times"       style="font-size:11px;"></i>',
                };
                $saveStatus.removeClass('save-idle save-typing save-saving save-saved save-error')
                    .addClass('save-' + state)
                    .html((icons[state] || '') + ' ' + text);
            }

            var fmt = function (v) { return v ? new Intl.NumberFormat('id-ID').format(v) : ''; };
            var rawNum = function (v) { return parseInt((String(v)).replace(/\D/g, ''), 10) || 0; };

            $('.pkd-currency').each(function () {
                if (this.value) this.value = fmt(rawNum(this.value));
            });

            function syncRefFields() {
                var dtVal = $('#dirtinggi').val();
                var dkVal = $('#dekomtinggi').val();
                var pgVal = $('#pegawaitinggi').val();
                $('#dirtinggi_ref4,#dirtinggi_ref5').val(dtVal);
                $('#dekomtinggi_ref4').val(dkVal);
                $('#pegawaitinggi_ref5').val(pgVal);
            }

            function recalcAll() {
                function rasio(aId, bId, rId) {
                    var a = rawNum($('#' + aId).val()), b = rawNum($('#' + bId).val()), el = document.getElementById(rId);
                    if (el) el.value = (a > 0 && b > 0) ? (a / b).toFixed(2) : '';
                }
                rasio('pegawaitinggi', 'pegawairendah', 'pegawaitinggirendah');
                rasio('dirtinggi', 'dirrendah', 'direksitinggirendah');
                rasio('dekomtinggi', 'dekomrendah', 'dekomtinggirendah');
                var dt = rawNum($('#dirtinggi').val()),
                    dk = rawNum($('#dekomtinggi').val()),
                    pg = rawNum($('#pegawaitinggi').val());
                var elDirDekom = document.getElementById('dirdekomtinggirendah');
                var elDirPeg = document.getElementById('dirpegawaitinggirendah');
                if (elDirDekom) elDirDekom.value = (dt > 0 && dk > 0) ? (dt / dk).toFixed(2) : '';
                if (elDirPeg) elDirPeg.value = (dt > 0 && pg > 0) ? (dt / pg).toFixed(2) : '';
                syncRefFields();
            }

            if (CFG.canEdit) {
                $('#rasiogajiForm .pkd-currency:not([readonly])').on('input', function () {
                    var digits = this.value.replace(/\D/g, '').slice(0, 15);
                    this.value = digits ? fmt(parseInt(digits, 10)) : '';
                    recalcAll();
                    triggerDebounce();
                });
            }

            function collectFormData() {
                var fields = [
                    'pegawaitinggi', 'pegawairendah', 'pegawaitinggirendah',
                    'dirtinggi', 'dirrendah', 'direksitinggirendah',
                    'dekomtinggi', 'dekomrendah', 'dekomtinggirendah',
                    'dirdekomtinggirendah', 'dirpegawaitinggirendah',
                ];
                var d = { user_id: CFG.uid, fullname: CFG.fname };
                fields.forEach(function (f) {
                    var el = document.getElementById(f);
                    var isRatio = f.indexOf('tinggirendah') !== -1 || f.indexOf('rendahtinggi') !== -1;
                    d[f] = el ? (isRatio ? (el.value || '') : rawNum(el.value)) : '';
                });
                return d;
            }

            var _debTimer = null;
            function triggerDebounce() {
                setSaveStatus('typing', 'Mengetik...');
                clearTimeout(_debTimer);
                _debTimer = setTimeout(doSave, 600);
            }

            function doSave() {
                if (!CFG.canEdit) return;
                setSaveStatus('saving', 'Menyimpan...');
                $.ajax({
                    url: CFG.base + '/Rasiogaji/tambahpenjelasAjax',
                    method: 'POST', dataType: 'json',
                    data: $.extend({}, csrf(), collectFormData()),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function (res) {
                        updateCsrf(res);
                        if (res.status === 'success') {
                            setSaveStatus('saved', 'Tersimpan');
                            toast(res.message || 'Data berhasil tersimpan', 'success');
                            setTimeout(function () { setSaveStatus('idle', 'Autosave aktif'); }, 3500);
                        } else {
                            setSaveStatus('error', 'Gagal simpan');
                            toast('Gagal menyimpan: ' + (res.message || 'Error'), 'error');
                        }
                    },
                    error: function (xhr) {
                        setSaveStatus('error', 'Error');
                        if (xhr.status === 401 || xhr.status === 403) {
                            toast('Sesi kedaluwarsa — halaman akan di-refresh…', 'error');
                            setTimeout(function () { location.reload(); }, 2000);
                        } else {
                            toast('Terjadi kesalahan koneksi', 'error');
                        }
                    }
                });
            }

            $('#rasiogajiForm').on('submit', function (e) { e.preventDefault(); });

            var execTambahTindak = debounce(function () {
                var tindak = $.trim($('#tt-tindaklanjut').val());
                if (!tindak) { $('#tindak-tambah-err').text('Penjelasan tidak boleh kosong.').show(); return; }
                var restore = btnLoading($('#btn-simpan-tindak'));
                $('#tindak-tambah-err').hide();
                $.ajax({
                    url: CFG.base + '/Rasiogaji/tambahketerangan',
                    method: 'POST', dataType: 'json',
                    data: $.extend({}, csrf(), { tindaklanjut: tindak }),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function (res) {
                        restore(); updateCsrf(res);
                        if (res.status === 'success') {
                            $('#modalTambahketerangan').modal('hide');
                            toast(res.message || 'Penjelasan berhasil disimpan');
                            renderPenjelasan(res.penjelastindak || []);
                        } else {
                            $('#tindak-tambah-err').text(res.message || 'Terjadi kesalahan').show();
                        }
                    },
                    error: function (xhr) {
                        restore();
                        var m = 'Terjadi kesalahan server.';
                        try { var r = JSON.parse(xhr.responseText); if (r && r.message) m = r.message; } catch (e) { }
                        if (xhr.status === 403) { m = 'Sesi kedaluwarsa — halaman akan di-refresh…'; setTimeout(function () { location.reload(); }, 2000); }
                        $('#tindak-tambah-err').text(m).show();
                    }
                });
            }, 400);

            $('#btn-simpan-tindak').on('click', execTambahTindak);

            $('#modaleditketerangan').on('show.bs.modal', function (e) {
                var btn = $(e.relatedTarget);
                $('#et-id').val(btn.data('id'));
                $('#et-tindaklanjut').val(btn.data('tindaklanjut'));
                $('#tindak-edit-err').hide();
            });

            var execEditTindak = debounce(function () {
                var tindak = $.trim($('#et-tindaklanjut').val());
                if (!tindak) { $('#tindak-edit-err').text('Penjelasan tidak boleh kosong.').show(); return; }
                var restore = btnLoading($('#btn-simpan-edit-tindak'));
                $('#tindak-edit-err').hide();
                $.ajax({
                    url: CFG.base + '/Rasiogaji/editketerangan',
                    method: 'POST', dataType: 'json',
                    data: $.extend({}, csrf(), { id: $('#et-id').val(), tindaklanjut: tindak }),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function (res) {
                        restore(); updateCsrf(res);
                        if (res.status === 'success') {
                            $('#modaleditketerangan').modal('hide');
                            toast(res.message || 'Penjelasan berhasil diubah');
                            renderPenjelasan(res.penjelastindak || []);
                        } else {
                            $('#tindak-edit-err').text(res.message || 'Terjadi kesalahan').show();
                        }
                    },
                    error: function (xhr) {
                        restore();
                        var m = 'Terjadi kesalahan server.';
                        try { var r = JSON.parse(xhr.responseText); if (r && r.message) m = r.message; } catch (e) { }
                        if (xhr.status === 403) { m = 'Sesi kedaluwarsa — halaman akan di-refresh…'; setTimeout(function () { location.reload(); }, 2000); }
                        $('#tindak-edit-err').text(m).show();
                    }
                });
            }, 400);

            $('#btn-simpan-edit-tindak').on('click', execEditTindak);

            $(document).on('click', '.btn-setnulltindak', function (e) {
                e.preventDefault();
                if (!confirm('Hapus penjelasan ini?')) return;
                var id = $(this).data('id'), $btn = $(this);
                $btn.html('<i class="fas fa-spinner fa-spin"></i>').css('pointer-events', 'none');
                $.ajax({
                    url: CFG.base + '/Rasiogaji/setNullKolomTindakAjax',
                    method: 'POST', dataType: 'json',
                    data: $.extend({}, csrf(), { id: id }),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function (res) {
                        updateCsrf(res);
                        if (res.status === 'success') { toast(res.message || 'Penjelasan berhasil dihapus'); renderPenjelasan([]); }
                        else { toast(res.message || 'Gagal menghapus', 'error'); $btn.html('<i class="fas fa-trash"></i>').css('pointer-events', ''); }
                    },
                    error: function () { toast('Terjadi kesalahan server', 'error'); $btn.html('<i class="fas fa-trash"></i>').css('pointer-events', ''); }
                });
            });

            function renderPenjelasan(rows) {
                var $hdr = $('.pkd-penjelasan-hdr');
                var $body = $('#pkd-penjelasan-body');
                var html = '';

                if (CFG.canEdit) {
                    $hdr.find('.btn-pkd-add,.btn-pkd-edit').remove();
                    if (!rows || !rows.length) {
                        $hdr.append('<button type="button" class="btn-pkd-sm btn-pkd-add"'
                            + ' data-toggle="modal" data-target="#modalTambahketerangan">'
                            + '<i class="fas fa-plus"></i> Tambah</button>');
                    } else {
                        $hdr.append('<button type="button" class="btn-pkd-sm btn-pkd-edit"'
                            + ' data-toggle="modal" data-target="#modaleditketerangan"'
                            + ' data-id="' + esc(rows[0].id) + '" data-tindaklanjut="' + esc(rows[0].tindaklanjut || '') + '">'
                            + '<i class="fas fa-edit"></i> Ubah</button>');
                    }
                }

                if (!rows || !rows.length) {
                    html = '<div class="pkd-penjelasan-empty">'
                        + '<i class="fas fa-inbox" style="font-size:28px;margin-bottom:6px;display:block;"></i>'
                        + 'Tidak ada penjelasan lebih lanjut</div>';
                } else {
                    rows.forEach(function (row) {
                        html += '<div class="pkd-penjelasan-content">'
                            + esc(row.tindaklanjut || '').replace(/\n/g, '<br>') + '</div>';
                        if (CFG.canEdit) {
                            html += '<div style="margin-top:8px;">'
                                + '<button type="button" class="btn-pkd-sm btn-pkd-del btn-setnulltindak"'
                                + ' data-id="' + esc(row.id) + '">'
                                + '<i class="fas fa-trash"></i> Hapus</button></div>';
                        }
                    });
                }

                $body.html(html);
                $('#tt-tindaklanjut').val('');
                $('#tindak-tambah-err').hide();
            }

            function updateNotifBadge(n) {
                n = parseInt(n) || 0;
                var $btn = $('#btn-komentar-rg');
                var $badge = $('#notif-rg');
                if (n > 0) { $badge.text(n).show(); $btn.addClass('has-unread'); }
                else { $badge.hide(); $btn.removeClass('has-unread'); }
            }

            function loadKomentarList(scroll) {
                var list = $('#komentar-list');
                list.html('<div class="komentar-loading"><i class="fas fa-spinner fa-spin"></i> Memuat...</div>');
                $.ajax({
                    url: CFG.base + '/Rasiogaji/getKomentarByFaktorId',
                    method: 'GET', dataType: 'json',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function (data) {
                        var items = Array.isArray(data) ? data : (data.comments || []);
                        if (!items.length) {
                            list.html('<div class="komentar-empty"><i class="fas fa-comment-slash fa-2x mb-2 d-block"></i>Belum ada komentar.</div>');
                            return;
                        }
                        var frag = document.createDocumentFragment(), tmp = document.createElement('div');
                        items.forEach(function (k) {
                            var own = parseInt(k.user_id) === CFG.uid;
                            tmp.innerHTML = '<div class="km-item' + (own ? ' km-own' : '') + '">'
                                + '<div class="km-meta">'
                                + '<span class="km-author"><i class="fas fa-user-circle"></i> ' + esc(k.fullname || '?')
                                + (own ? ' <span class="km-you">Anda</span>' : '') + '</span>'
                                + '<span class="km-time"><i class="fas fa-clock"></i> ' + timeAgo(k.created_at) + '</span>'
                                + '</div><p class="km-text">' + esc(k.komentar) + '</p></div>';
                            frag.appendChild(tmp.firstElementChild);
                        });
                        list.empty()[0].appendChild(frag);
                        if (scroll) {
                            var w = document.getElementById('komentar-list-wrapper');
                            if (w) requestAnimationFrame(function () { w.scrollTop = w.scrollHeight; });
                        }
                    },
                    error: function () {
                        list.html('<div class="komentar-empty text-danger"><i class="fas fa-exclamation-circle"></i> Gagal memuat komentar.</div>');
                    }
                });
            }

            function markAllRead() {
                $.ajax({
                    url: CFG.base + '/Rasiogaji/markUserCommentsAsRead',
                    method: 'POST',
                    data: $.extend({}, csrf(), { id: 'Rasiogaji', kodebpr: CFG.kbpr, user_id: CFG.uid, periode_id: CFG.pid }),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function (res) { updateCsrf(res); updateNotifBadge(0); }
                });
            }

            var _sendingKomentar = false;
            var execKomentar = debounce(function () {
                if (_sendingKomentar) return;
                var teks = $.trim($('#k-teks').val());
                if (!teks) { toast('Komentar tidak boleh kosong.', 'error'); return; }
                if (teks.length < 3) { toast('Komentar minimal 3 karakter.', 'error'); return; }
                _sendingKomentar = true;
                var restore = btnLoading($('#btn-kirim-komentar'));
                $.ajax({
                    url: CFG.base + '/Rasiogaji/Tambahkomentar',
                    method: 'POST', dataType: 'json',
                    data: $.extend({}, csrf(), {
                        TambahKomentar: 1, id: 'Rasiogaji',
                        komentar: teks, fullname: CFG.fname,
                        kodebpr: CFG.kbpr,
                        date: new Date().toISOString().slice(0, 19).replace('T', ' ')
                    }),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function (res) {
                        _sendingKomentar = false; restore(); updateCsrf(res);
                        if (res && res.status === 'success') {
                            $('#k-teks').val('');
                            $('#k-char-count').text('0 / 1000').removeClass('danger');
                            toast('Komentar berhasil dikirim');
                            loadKomentarList(true);
                        } else {
                            toast((res && res.message) || 'Gagal mengirim', 'error');
                        }
                    },
                    error: function () { _sendingKomentar = false; restore(); toast('Gagal mengirim komentar', 'error'); }
                });
            }, 400);

            $('#btn-komentar-rg').on('click', function () {
                loadKomentarList(true);
                markAllRead();
                $('#modalKomentar').modal('show');
            });

            $('#btn-kirim-komentar').on('click', execKomentar);
            $('#k-teks')
                .on('keydown', function (e) { if (e.ctrlKey && e.key === 'Enter') { e.preventDefault(); execKomentar(); } })
                .on('input', function () {
                    var l = this.value.length, $el = $('#k-char-count');
                    $el.text(l + ' / 1000');
                    l > 950 ? $el.addClass('danger') : $el.removeClass('danger');
                });

            var _pollTimer = null, _pollDelay = CFG.pollBase, _lastUnread = 0;

            function schedulePoll(delay) { clearTimeout(_pollTimer); _pollTimer = setTimeout(doPoll, delay); }

            function doPoll() {
                $.ajax({
                    url: CFG.base + '/Rasiogaji/getUnreadCommentCountForFactor',
                    method: 'GET', dataType: 'json',
                    data: { id: 'Rasiogaji', kodebpr: CFG.kbpr, user_id: CFG.uid, periode_id: CFG.pid },
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function (res) {
                        var n = parseInt(res.unread_count) || 0;
                        updateNotifBadge(n);
                        _pollDelay = n > _lastUnread ? Math.max(5000, CFG.pollBase / 2) : Math.min(60000, CFG.pollBase);
                        _lastUnread = n;
                        schedulePoll(_pollDelay);
                    },
                    error: function () {
                        _pollDelay = Math.min(120000, _pollDelay * 2);
                        schedulePoll(_pollDelay);
                    }
                });
            }

            document.addEventListener('visibilitychange', function () {
                if (document.hidden) { clearTimeout(_pollTimer); }
                else { _pollDelay = CFG.pollBase; schedulePoll(1000); }
            }, { passive: true });

            $(document).on('hidden.bs.modal', '.modal', function () {
                $(this).find('.alert-danger').hide().text('');
            });

            (function boot() {
                $('#pkd-skeleton').show();
                $('#rasiogajiForm').hide();
                requestAnimationFrame(function () {
                    setTimeout(function () {
                        recalcAll();
                        $('#pkd-skeleton').hide();
                        $('#rasiogajiForm').show();
                        if (!CFG.canEdit) {
                            setSaveStatus('idle', 'Read Only');
                        }
                    }, 180);
                });
            }());

            schedulePoll(_pollDelay);

        });
    }());
</script>

<style>
    :root {
        --brand: #4f46e5;
        --brand-light: #818cf8;
        --brand-dark: #3730a3;
        --brand-bg: #eef2ff;
        --navy: #141863;
        --blue: #1f2aa8;
        --grad: linear-gradient(135deg, #141863 0%, #1f2aa8 100%);
        --success: #10b981;
        --success-bg: #d1fae5;
        --danger: #ef4444;
        --danger-bg: #fee2e2;
        --warning: #f59e0b;
        --warning-bg: #fef3c7;
        --gray-50: #f8fafc;
        --gray-100: #f1f5f9;
        --gray-200: #e2e8f0;
        --gray-300: #cbd5e1;
        --gray-400: #94a3b8;
        --gray-500: #64748b;
        --gray-600: #475569;
        --gray-700: #334155;
        --gray-800: #1e293b;
        --gray-900: #0f172a;
        --sh-sm: 0 1px 3px rgba(0, 0, 0, .08), 0 1px 2px rgba(0, 0, 0, .06);
        --sh: 0 4px 6px -1px rgba(0, 0, 0, .1), 0 2px 4px -1px rgba(0, 0, 0, .06);
        --sh-lg: 0 10px 25px -3px rgba(0, 0, 0, .1), 0 4px 6px -2px rgba(0, 0, 0, .05);
        --tr: all .2s cubic-bezier(.4, 0, .2, 1);
        --r-sm: 8px;
        --r: 12px;
        --r-lg: 16px;
    }

    *,
    *::before,
    *::after {
        box-sizing: border-box;
    }

    * {
        font-family: 'Plus Jakarta Sans', sans-serif;
    }

    .pkd-info-bar {
        display: flex;
        align-items: center;
        gap: 10px;
        background: var(--brand-bg);
        border: 1.5px solid #c7d2fe;
        border-radius: var(--r);
        padding: 12px 18px;
        margin-bottom: 20px;
        font-size: 13.5px;
        font-weight: 600;
        color: #3730a3;
    }

    .pkd-info-bar i {
        font-size: 18px;
        color: var(--brand);
        flex-shrink: 0;
    }

    .pkd-flash {
        border-radius: var(--r);
        padding: 12px 16px;
        font-size: 13px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 14px;
    }

    .pkd-flash-success {
        background: var(--success-bg);
        color: #065f46;
        border: 1.5px solid #a7f3d0;
    }

    .pkd-flash-danger {
        background: var(--danger-bg);
        color: #991b1b;
        border: 1.5px solid #fca5a5;
    }

    .pkd-approvals {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 18px;
    }

    .page-header {
        text-align: center;
        padding: 28px 16px 20px;
        background: white;
        border-bottom: 1px solid var(--gray-100);
        position: relative;
        overflow: hidden;
    }

    .page-header::before {
        content: '';
        position: absolute;
        top: -60px;
        right: -60px;
        width: 180px;
        height: 180px;
        border-radius: 50%;
        background: radial-gradient(circle, #eef2ff, transparent 70%);
    }

    .page-subtitle {
        font-size: .9rem;
        font-weight: 600;
        color: var(--gray-500);
        margin-bottom: 10px;
        position: relative;
        z-index: 1;
    }

    .approval-badges-row {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: center;
        align-items: center;
    }

    .approval-status-badge {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 14px;
        border-radius: var(--r);
        font-size: 12.5px;
        font-weight: 600;
        border: 1.5px solid transparent;
        transition: var(--tr);
    }

    .approval-status-badge i {
        font-size: 18px;
        flex-shrink: 0;
    }

    .approval-status-label {
        font-weight: 700;
        font-size: 12px;
    }

    .approval-status-sub {
        font-size: 11px;
        opacity: .8;
    }

    .badge-status-secondary {
        background: var(--gray-100);
        color: var(--gray-500);
        border-color: var(--gray-200);
    }

    .badge-status-success {
        background: var(--success-bg);
        color: #065f46;
        border-color: #6ee7b7;
    }

    .pkd-card {
        background: white;
        border-radius: var(--r-lg);
        border: 1.5px solid var(--gray-200);
        box-shadow: var(--sh-sm);
        overflow: hidden;
        margin-bottom: 20px;
        transition: box-shadow .25s;
    }

    .pkd-card:hover {
        box-shadow: var(--sh);
    }

    .pkd-section-header {
        display: flex;
        align-items: center;
        gap: 10px;
        background: var(--grad);
        padding: 11px 18px;
        border-radius: var(--r-lg) var(--r-lg) 0 0;
    }

    .pkd-section-num {
        background: rgba(255, 255, 255, .2);
        color: white;
        font-size: 11px;
        font-weight: 800;
        width: 26px;
        height: 26px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .pkd-section-title {
        font-size: 13.5px;
        font-weight: 700;
        color: rgba(255, 255, 255, .95);
    }

    .pkd-card-body {
        padding: 20px;
    }

    .pkd-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    @media(max-width:640px) {
        .pkd-grid {
            grid-template-columns: 1fr;
        }
    }

    .pkd-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .pkd-label {
        font-size: 12px;
        font-weight: 700;
        color: var(--gray-600);
        text-transform: uppercase;
        letter-spacing: .4px;
    }

    .pkd-input-group {
        display: flex;
        align-items: stretch;
        border: 1.5px solid var(--gray-200);
        border-radius: var(--r-sm);
        overflow: hidden;
        transition: border-color .2s, box-shadow .2s;
        background: white;
    }

    .pkd-input-group:focus-within {
        border-color: var(--brand);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, .12);
    }

    .pkd-input-group.readonly-group {
        background: var(--gray-50);
    }

    .pkd-addon {
        display: flex;
        align-items: center;
        padding: 0 10px;
        background: var(--gray-100);
        border-right: 1.5px solid var(--gray-200);
        font-size: 12px;
        font-weight: 700;
        color: var(--gray-500);
        white-space: nowrap;
        flex-shrink: 0;
    }

    .pkd-addon-right {
        border-right: none;
        border-left: 1.5px solid var(--gray-200);
    }

    .pkd-input {
        flex: 1;
        border: none;
        outline: none;
        padding: 10px 12px;
        font-size: 13.5px;
        font-weight: 600;
        color: var(--gray-800);
        background: transparent;
        min-width: 0;
    }

    .pkd-input::placeholder {
        color: var(--gray-300);
        font-weight: 400;
    }

    .pkd-input:read-only {
        color: var(--gray-500);
        cursor: not-allowed;
    }

    .pkd-rasio-result-wrap {
        margin-top: 16px;
    }

    .pkd-save-status {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        font-size: 12px;
        font-weight: 600;
        padding: 5px 12px;
        border-radius: 100px;
        transition: var(--tr);
    }

    .save-idle {
        background: var(--gray-100);
        color: var(--gray-400);
    }

    .save-typing {
        background: var(--warning-bg);
        color: #92400e;
    }

    .save-saving {
        background: #dbeafe;
        color: #1e40af;
    }

    .save-saved {
        background: var(--success-bg);
        color: #065f46;
    }

    .save-error {
        background: var(--danger-bg);
        color: #991b1b;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    .pkd-spinner {
        width: 13px;
        height: 13px;
        border: 2px solid currentColor;
        border-top-color: transparent;
        border-radius: 50%;
        animation: spin .6s linear infinite;
        flex-shrink: 0;
    }

    @keyframes skShimmer {
        0% {
            background-position: 200% 0
        }

        100% {
            background-position: -200% 0
        }
    }

    .skel {
        background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
        background-size: 200% 100%;
        animation: skShimmer 1.4s infinite;
        border-radius: var(--r-sm);
    }

    .skel-card {
        background: white;
        border-radius: var(--r-lg);
        border: 1.5px solid var(--gray-200);
        overflow: hidden;
        margin-bottom: 20px;
    }

    .skel-header {
        height: 46px;
    }

    .skel-body {
        padding: 20px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
    }

    .skel-field {
        height: 42px;
    }

    .pkd-penjelasan-card {
        background: var(--gray-50);
        border: 1.5px solid var(--gray-200);
        border-radius: var(--r-lg);
        overflow: hidden;
        margin-bottom: 20px;
    }

    .pkd-penjelasan-hdr {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 16px;
        background: white;
        border-bottom: 1.5px solid var(--gray-200);
    }

    .pkd-penjelasan-title {
        font-size: 12.5px;
        font-weight: 700;
        color: var(--gray-700);
    }

    .pkd-penjelasan-body {
        padding: 14px 16px;
    }

    .pkd-penjelasan-content {
        font-size: 13.5px;
        color: var(--gray-700);
        line-height: 1.7;
        background: white;
        border: 1.5px solid var(--gray-200);
        border-radius: var(--r-sm);
        padding: 10px 14px;
    }

    .pkd-penjelasan-empty {
        text-align: center;
        color: var(--gray-400);
        padding: 20px;
        font-size: 13px;
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
        margin: 12px 0 0 8px;
    }

    .btn-kembali:hover {
        background: var(--brand-bg);
        border-color: var(--brand-light);
        color: var(--brand);
        text-decoration: none;
    }

    .btn-pkd-primary {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: var(--grad);
        color: white;
        border: none;
        border-radius: var(--r-sm);
        padding: 10px 22px;
        font-size: 13.5px;
        font-weight: 700;
        cursor: pointer;
        transition: var(--tr);
        box-shadow: 0 4px 14px rgba(31, 42, 168, .3);
    }

    .btn-pkd-primary:hover {
        opacity: .9;
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(31, 42, 168, .35);
    }

    .btn-pkd-primary:active {
        transform: scale(.97);
    }

    .btn-pkd-secondary {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: white;
        color: var(--gray-700);
        border: 1.5px solid var(--gray-300);
        border-radius: var(--r-sm);
        padding: 10px 20px;
        font-size: 13.5px;
        font-weight: 600;
        cursor: pointer;
        transition: var(--tr);
    }

    .btn-pkd-secondary:hover {
        border-color: var(--brand-light);
        color: var(--brand);
        background: var(--brand-bg);
    }

    .btn-pkd-sm {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 6px 14px;
        border-radius: var(--r-sm);
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: var(--tr);
    }

    .btn-pkd-edit {
        background: var(--brand-bg);
        color: var(--brand);
        border: 1.5px solid #c7d2fe;
    }

    .btn-pkd-edit:hover {
        background: var(--brand);
        color: white;
    }

    .btn-pkd-add {
        background: var(--success-bg);
        color: #065f46;
        border: 1.5px solid #a7f3d0;
    }

    .btn-pkd-add:hover {
        background: var(--success);
        color: white;
    }

    .btn-pkd-del {
        background: var(--danger-bg);
        color: var(--danger);
        border: 1.5px solid #fca5a5;
    }

    .btn-pkd-del:hover {
        background: var(--danger);
        color: white;
    }

    .approval-actions-row {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        justify-content: center;
    }

    .action-card {
        border-radius: var(--r) !important;
        border: 1.5px solid var(--gray-200) !important;
        transition: var(--tr);
        overflow: hidden;
        min-width: 200px;
    }

    .action-card:hover {
        border-color: var(--brand-light) !important;
        box-shadow: 0 8px 24px rgba(79, 70, 229, .12) !important;
        transform: translateY(-2px);
    }

    .blk-disabled {
        opacity: .4;
        pointer-events: none;
    }

    @media(max-width:600px) {
        .pkd-approval-split-row {
            flex-direction: column;
        }

        .pkd-approval-split-side {
            justify-content: center !important;
        }

        .pkd-action-card {
            width: 100%;
        }
    }

    .pkd-komentar-wrap {
        position: relative;
        display: inline-flex;
    }

    .btn-komentar-pkd {
        position: relative;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: white;
        border: none;
        color: var(--blue);
        font-size: 17px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 12px rgba(0, 0, 0, .18);
        cursor: pointer;
        transition: var(--tr);
        outline: none;
    }

    .btn-komentar-pkd:hover {
        background: var(--brand-bg);
        color: var(--brand);
        transform: scale(1.1);
    }

    .btn-komentar-pkd.has-unread {
        animation: btn-shake 1.8s ease infinite;
        background: var(--brand-bg);
        color: var(--brand);
    }

    @keyframes btn-shake {

        0%,
        100% {
            transform: rotate(0)scale(1)
        }

        10% {
            transform: rotate(-14deg)scale(1.08)
        }

        20% {
            transform: rotate(12deg)scale(1.08)
        }

        30% {
            transform: rotate(-10deg)scale(1.05)
        }

        40% {
            transform: rotate(8deg)scale(1.05)
        }

        50% {
            transform: rotate(0)scale(1)
        }
    }

    .btn-komentar-ripple {
        position: absolute;
        inset: -4px;
        border-radius: 50%;
        border: 2px solid var(--brand-light);
        opacity: 0;
        pointer-events: none;
    }

    .btn-komentar-pkd.has-unread .btn-komentar-ripple {
        animation: ripple-out 1.8s ease infinite;
    }

    @keyframes ripple-out {
        0% {
            transform: scale(.9);
            opacity: .7
        }

        100% {
            transform: scale(1.6);
            opacity: 0
        }
    }

    .notif-dot {
        position: absolute;
        top: -4px;
        right: -4px;
        background: var(--danger);
        color: white;
        border-radius: 50%;
        font-size: 10px;
        font-weight: 700;
        min-width: 17px;
        height: 17px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 6px rgba(239, 68, 68, .5);
        animation: pulse 2s infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            box-shadow: 0 0 0 0 rgba(239, 68, 68, .4)
        }

        50% {
            box-shadow: 0 0 0 5px rgba(239, 68, 68, 0)
        }
    }

    .pkd-bottom-bar {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        flex-wrap: wrap;
        padding: 20px 0 8px;
    }

    .pkd-nav-card {
        border: 1.5px solid var(--gray-200);
        border-radius: var(--r);
        max-width: 720px;
        margin: 28px auto 0;
    }

    .pkd-nav-hdr {
        text-align: center;
        background: var(--gray-50);
        padding: 6px;
        border-bottom: 1.5px solid var(--gray-200);
        font-size: .85rem;
        font-weight: 700;
    }

    .pkd-nav-body {
        padding: 8px;
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        justify-content: center;
    }

    .nav-pg {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 32px;
        height: 32px;
        padding: 0 6px;
        border-radius: 6px;
        border: 1.5px solid var(--gray-200);
        background: white;
        color: var(--gray-700);
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        transition: var(--tr);
    }

    .nav-pg:hover {
        background: var(--brand-bg);
        border-color: var(--brand-light);
        color: var(--brand);
        text-decoration: none;
    }

    .nav-pg.active {
        background: var(--grad);
        color: white;
        border-color: var(--blue);
    }

    /* Komentar modal */
    .komentar-modal-content {
        border-radius: 18px !important;
        overflow: hidden;
        border: none;
        box-shadow: 0 20px 60px rgba(0, 0, 0, .18) !important;
    }

    .komentar-modal-header {
        background: var(--grad);
        padding: 18px 22px !important;
        border: none !important;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .komentar-modal-title-wrap {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .komentar-modal-icon-wrap {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        background: rgba(255, 255, 255, .18);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        color: white;
        flex-shrink: 0;
    }

    .komentar-modal-title {
        font-size: 14px;
        font-weight: 800;
        color: white;
    }

    .komentar-close-btn {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .15);
        border: none;
        color: white;
        font-size: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all .2s;
        flex-shrink: 0;
    }

    .komentar-close-btn:hover {
        background: rgba(255, 255, 255, .3);
        transform: rotate(90deg);
    }

    .komentar-modal-body {
        padding: 18px 20px !important;
        background: var(--gray-50);
    }

    .komentar-modal-footer {
        background: white;
        border-top: 1px solid var(--gray-100) !important;
        padding: 10px 20px !important;
    }

    .komentar-list-label {
        font-size: 11px;
        font-weight: 700;
        color: var(--gray-500);
        text-transform: uppercase;
        letter-spacing: .8px;
        margin-bottom: 8px;
    }

    .komentar-list-wrapper {
        background: white;
        border: 1.5px solid var(--gray-200);
        border-radius: 12px;
        max-height: 300px;
        overflow-y: auto;
        padding: 10px;
        margin-bottom: 14px;
        scroll-behavior: smooth;
    }

    .komentar-list-wrapper::-webkit-scrollbar {
        width: 5px;
    }

    .komentar-list-wrapper::-webkit-scrollbar-thumb {
        background: var(--gray-200);
        border-radius: 10px;
    }

    .komentar-loading,
    .komentar-empty {
        text-align: center;
        color: var(--gray-400);
        padding: 24px 0;
        font-size: 13px;
    }

    .km-item {
        background: var(--gray-50);
        border-left: 3px solid var(--brand-light);
        border-radius: 10px;
        padding: 9px 12px;
        margin-bottom: 7px;
        box-shadow: var(--sh-sm);
        animation: kmIn .25s ease both;
    }

    .km-item.km-own {
        border-left-color: var(--success);
        background: #f0fdf4;
    }

    @keyframes kmIn {
        from {
            opacity: 0;
            transform: translateY(6px)
        }

        to {
            opacity: 1;
            transform: translateY(0)
        }
    }

    .km-meta {
        display: flex;
        justify-content: space-between;
        margin-bottom: 4px;
        flex-wrap: wrap;
        gap: 3px;
    }

    .km-author {
        font-weight: 700;
        font-size: 12px;
        color: var(--gray-800);
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .km-you {
        font-size: 9px;
        background: var(--success);
        color: white;
        padding: 1px 5px;
        border-radius: 100px;
        font-weight: 700;
    }

    .km-time {
        font-size: 11px;
        color: var(--gray-400);
    }

    .km-text {
        font-size: 13px;
        white-space: pre-wrap;
        margin: 0;
        color: var(--gray-700);
        line-height: 1.5;
    }

    .komentar-input-wrap {
        background: white;
        border: 1.5px solid var(--gray-200);
        border-radius: 12px;
        padding: 12px;
        transition: border-color .2s;
    }

    .komentar-input-wrap:focus-within {
        border-color: var(--brand);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, .1);
    }

    .komentar-input-label {
        font-size: 11px;
        font-weight: 700;
        color: var(--gray-500);
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: .6px;
    }

    .komentar-textarea {
        width: 100%;
        border: none;
        outline: none;
        resize: none;
        font-size: 13px;
        color: var(--gray-700);
        line-height: 1.6;
        background: transparent;
    }

    .komentar-input-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 6px;
        padding-top: 6px;
        border-top: 1px solid var(--gray-100);
    }

    .komentar-hint {
        font-size: 11px;
        color: var(--gray-400);
    }

    .komentar-char {
        font-size: 11px;
        color: var(--gray-400);
        font-weight: 600;
        transition: color .2s;
    }

    .komentar-char.danger {
        color: var(--danger);
    }

    /* Generic modal */
    .pkd-modal .modal-content {
        border-radius: var(--r-lg);
        overflow: hidden;
        border: none;
        box-shadow: var(--sh-lg);
    }

    .pkd-modal .modal-header {
        background: var(--grad);
        padding: 14px 20px;
        border: none;
    }

    .pkd-modal .modal-title {
        color: white;
        font-weight: 800;
        font-size: 14px;
    }

    .pkd-modal .modal-header .close {
        color: rgba(255, 255, 255, .8);
        opacity: 1;
        text-shadow: none;
        transition: var(--tr);
    }

    .pkd-modal .modal-header .close:hover {
        color: white;
        transform: rotate(90deg);
    }

    .pkd-modal .modal-body {
        padding: 18px 20px;
        background: var(--gray-50);
    }

    .pkd-modal .modal-footer {
        background: white;
        border-top: 1.5px solid var(--gray-100);
        padding: 10px 20px;
    }

    .pkd-modal .form-control {
        border-radius: var(--r-sm);
        border: 1.5px solid var(--gray-200);
        font-size: 13px;
        transition: var(--tr);
    }

    .pkd-modal .form-control:focus {
        border-color: var(--brand);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, .12);
        outline: none;
    }

    .badge-namabpr {
        display: inline-block;
        background: var(--grad);
        color: white;
        font-size: .9rem;
        font-weight: 700;
        padding: 5px 18px;
        border-radius: 100px;
        letter-spacing: .3px;
        position: relative;
        z-index: 1;
        box-shadow: 0 4px 12px rgba(79, 70, 229, .3);
    }

    /* Toast */
    #pkd-toast-container {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 8px;
        pointer-events: none;
        max-width: 320px;
    }

    .pkd-toast {
        background: white;
        border-radius: var(--r);
        padding: 12px 16px;
        box-shadow: var(--sh-lg);
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 13px;
        font-weight: 600;
        pointer-events: all;
        border-left: 4px solid;
        animation: toastIn .3s ease;
    }

    .pkd-toast.t-success {
        border-color: var(--success);
        color: #065f46;
    }

    .pkd-toast.t-error {
        border-color: var(--danger);
        color: #991b1b;
    }

    .pkd-toast.t-info {
        border-color: #06b6d4;
        color: #164e63;
    }

    @keyframes toastIn {
        from {
            transform: translateX(110%);
            opacity: 0
        }

        to {
            transform: translateX(0);
            opacity: 1
        }
    }

    @keyframes toastOut {
        to {
            transform: translateX(120%);
            opacity: 0
        }
    }

    .pkd-toast.out {
        animation: toastOut .3s ease forwards;
    }

    .page-title {
        font-size: clamp(1.05rem, 3.5vw, 1.5rem);
        font-weight: 800;
        color: var(--gray-900);
        margin-bottom: 6px;
        letter-spacing: -.5px;
        position: relative;
        z-index: 1
    }

    .pkd-page-sub {
        text-align: center;
        font-size: .85rem;
        color: var(--gray-500);
        margin-bottom: 18px;
    }
</style>