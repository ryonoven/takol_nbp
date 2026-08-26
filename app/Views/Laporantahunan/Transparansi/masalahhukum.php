<div id="toast-wrap" style="position:fixed;top:20px;right:20px;z-index:99999;min-width:290px;pointer-events:none;"></div>

<div class="container-fluid">
    <a href="<?= base_url('ShowTransparansi') ?>" class="btn-kembali mb-3">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>

    <?php if (session()->getFlashdata('message')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <strong><?= esc(session()->getFlashdata('message')) ?></strong>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('err')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <strong><?= esc(session()->getFlashdata('err')) ?></strong>
        </div>
    <?php endif; ?>

    <?php
    $allAccdekomApproved = !empty($accdekomData) && array_reduce($accdekomData, fn($c, $i) => $c && $i['accdekom'] == 1, true);
    $allAccdirutApproved = !empty($accdirutData) && array_reduce($accdirutData, fn($c, $i) => $c && $i['is_approved'] == 1, true);
    $lastDekomAt = $allAccdekomApproved && !empty($accdekomData) ? date('d/m/Y H:i', strtotime($accdekomData[count($accdekomData) - 1]['accdekom_at'])) : '';
    $lastDirutAt = $allAccdirutApproved && !empty($accdirutData) ? date('d/m/Y H:i', strtotime($accdirutData[0]['approved_at'])) : '';
    $canEdit     = (($userInGroupPE ?? false) || ($userInGroupAdmin ?? false));
    $row0        = $masalahhukum[0] ?? [];
    $isNullRecord = (int) ($isNullRecord ?? 0);
    ?>

    <div class="card card-body mb-3 shadow-sm">
        <div class="page-header">
            <div class="approval-badges-row mb-3">
                <div class="approval-status-badge <?= $allAccdekomApproved ? 'badge-status-success' : 'badge-status-secondary' ?>" id="badge-dekom">
                    <i class="fas <?= $allAccdekomApproved ? 'fa-check-circle' : 'fa-clock' ?>"></i>
                    <div>
                        <span class="approval-status-label">Komisaris Utama</span><br>
                        <span class="approval-status-sub">
                            <?= $allAccdekomApproved ? 'Disetujui &middot; ' . $lastDekomAt : 'Belum disetujui seluruhnya' ?>
                        </span>
                    </div>
                </div>
                <div class="approval-status-badge <?= $allAccdirutApproved ? 'badge-status-success' : 'badge-status-secondary' ?>" id="badge-dirut">
                    <i class="fas <?= $allAccdirutApproved ? 'fa-check-circle' : 'fa-clock' ?>"></i>
                    <div>
                        <span class="approval-status-label">Direktur Utama</span><br>
                        <span class="approval-status-sub">
                            <?= $allAccdirutApproved ? 'Disetujui &middot; ' . $lastDirutAt : 'Belum disetujui seluruhnya' ?>
                        </span>
                    </div>
                </div>
            </div>
            <h1 class="page-title"><?= esc($judul) ?></h1>
            <div class="page-subtitle">Tahun <?= esc($periodeDetail['tahun']) ?></div>
            <span class="badge-namabpr"><?= esc($bprData['namabpr'] ?? 'Nama BPR') ?></span>
        </div>
    </div>

    <?php if ($canEdit): ?>
        <?php if ($isNullRecord): ?>
            <div class="alert alert-secondary alert-dismissible mb-3" id="null-record-banner">
                <div class="d-flex align-items-center" style="gap:10px;">
                    <i class="fas fa-minus-circle fa-lg text-secondary"></i>
                    <div>
                        <strong>Tidak ada data</strong><br>
                        <small>Tidak terdapat Permasalahan Hukum pada periode ini.</small>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger ml-4 mt-1" id="btn-batal-nodata">
                    <i class="fas fa-undo"></i> Batalkan "Tidak Ada Data"
                </button>
            </div>
        <?php else: ?>
            <div class="d-flex align-items-center mb-3" style="gap:8px;">
                <div id="autosave-bar" class="autosave-bar as-idle flex-grow-1">
                    <i class="fas fa-circle as-dot-idle as-icon"></i>
                    <span class="as-text">Siap menyimpan</span>
                    <span class="as-hint">&middot; Setiap perubahan tersimpan otomatis</span>
                </div>
                <button type="button" class="btn btn-warning btn-sm" id="btn-nodata">
                    <i class="fas fa-times-circle"></i> Tidak Ada Data
                </button>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="autosave-bar as-idle mb-3">
            <i class="fas fa-eye as-icon"></i>
            <span class="as-text">Mode baca</span>
            <span class="as-hint">&middot; Anda tidak memiliki akses untuk mengubah data ini</span>
        </div>
    <?php endif; ?>

    <form id="masalahForm" autocomplete="off" <?= $isNullRecord ? 'style="opacity:0.5;pointer-events:none;"' : '' ?>>

        <div class="card shadow mb-3">
            <div class="card-header py-2 d-flex align-items-center" style="background:var(--grad);">
                <span class="fraud-section-badge">1.1</span>
                <h6 class="m-0 font-weight-bold text-white">Permasalahan Hukum yang Telah Selesai (telah mempunyai kekuatan hukum yang tetap)</h6>
            </div>
            <div class="card-body p-3">
                <div class="fraud-row-2col">
                    <div class="fraud-field">
                        <label class="fraud-label">a. Permasalahan Hukum Perdata yang Telah Selesai:</label>
                        <div class="fraud-input-wrap<?= $canEdit ? '' : ' fraud-input-readonly' ?>">
                            <input type="number" class="form-control fraud-input mh-num" name="hukumperdataselesai" id="hukumperdataselesai"
                                value="<?= esc($row0['hukumperdataselesai'] ?? '0') ?>" min="0" step="1" placeholder="0" <?= $canEdit ? '' : 'readonly' ?>>
                            <span class="fraud-input-suffix">Kasus</span>
                        </div>
                    </div>
                    <div class="fraud-field">
                        <label class="fraud-label">b. Permasalahan Hukum Pidana yang Telah Selesai:</label>
                        <div class="fraud-input-wrap<?= $canEdit ? '' : ' fraud-input-readonly' ?>">
                            <input type="number" class="form-control fraud-input mh-num" name="hukumpidanaselesai" id="hukumpidanaselesai"
                                value="<?= esc($row0['hukumpidanaselesai'] ?? '0') ?>" min="0" step="1" placeholder="0" <?= $canEdit ? '' : 'readonly' ?>>
                            <span class="fraud-input-suffix">Kasus</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-3">
            <div class="card-header py-2 d-flex align-items-center" style="background:var(--grad);">
                <span class="fraud-section-badge">1.2</span>
                <h6 class="m-0 font-weight-bold text-white">Permasalahan Hukum yang Dalam Proses Penyelesaian</h6>
            </div>
            <div class="card-body p-3">
                <div class="fraud-row-2col">
                    <div class="fraud-field">
                        <label class="fraud-label">Permasalahan Hukum Perdata yang Dalam Proses Penyelesaian:</label>
                        <div class="fraud-input-wrap<?= $canEdit ? '' : ' fraud-input-readonly' ?>">
                            <input type="number" class="form-control fraud-input mh-num" name="hukumperdataproses" id="hukumperdataproses"
                                value="<?= esc($row0['hukumperdataproses'] ?? '0') ?>" min="0" step="1" placeholder="0" <?= $canEdit ? '' : 'readonly' ?>>
                            <span class="fraud-input-suffix">Kasus</span>
                        </div>
                    </div>
                    <div class="fraud-field">
                        <label class="fraud-label">Permasalahan Hukum Pidana yang Dalam Proses Penyelesaian:</label>
                        <div class="fraud-input-wrap<?= $canEdit ? '' : ' fraud-input-readonly' ?>">
                            <input type="number" class="form-control fraud-input mh-num" name="hukumpidanaproses" id="hukumpidanaproses"
                                value="<?= esc($row0['hukumpidanaproses'] ?? '0') ?>" min="0" step="1" placeholder="0" <?= $canEdit ? '' : 'readonly' ?>>
                            <span class="fraud-input-suffix">Kasus</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TOTAL BANNER -->
        <div class="mh-total-banner mb-3">
            <div class="mh-total-title"><i class="fas fa-calculator"></i> Total</div>
            <div class="fraud-row-2col">
                <div class="fraud-field">
                    <label class="fraud-label">Total Permasalahan Hukum yang Telah Selesai:</label>
                    <div class="fraud-input-wrap fraud-input-readonly">
                        <input type="text" id="totalselesai" name="totalselesai" class="form-control fraud-input"
                            value="<?= esc($row0['totalselesai'] ?? '0') ?>" readonly>
                        <span class="fraud-input-suffix">Kasus</span>
                    </div>
                </div>
                <div class="fraud-field">
                    <label class="fraud-label">Total Permasalahan Hukum Dalam Proses:</label>
                    <div class="fraud-input-wrap fraud-input-readonly">
                        <input type="text" id="totalproses" name="totalproses" class="form-control fraud-input"
                            value="<?= esc($row0['totalproses'] ?? '0') ?>" readonly>
                        <span class="fraud-input-suffix">Kasus</span>
                    </div>
                </div>
            </div>
        </div>

    </form>

    <!-- KOMENTAR BUTTON -->
    <?php if (($userInGroupAdmin || $userInGroupDekom || $userInGroupDireksi || $userInGroupPE || $userInGroupDekom2 || $userInGroupDireksi2) ?? false): ?>
        <div class="pkd-bottom-bar">
            <div class="position-relative d-inline-block">
                <button type="button" class="btn-komentar-global-btn" id="btn-komentar-global" title="Komentar">
                    <i class="fas fa-comment-dots"></i>
                    <span class="btn-komentar-ripple"></span>
                </button>
                <span id="notif-global" class="notif-badge" style="display:none;">0</span>
            </div>
        </div>
    <?php endif; ?>

    <!-- PENJELASAN LEBIH LANJUT -->
    <div class="card shadow mb-4">
        <div class="card-header py-3" style="background:var(--grad);">
            <h5 class="m-0 font-weight-bold text-white"><i class="fas fa-tasks"></i> Penjelasan Lebih Lanjut (Opsional)</h5>
        </div>
        <div class="card-body p-3">
            <?php if ($canEdit): ?>
                <div class="mb-3" id="tindak-btn-wrap">
                    <?php if (empty($penjelastindak)): ?>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#modalTambahTindak">
                            <i class="fas fa-plus"></i> Tambah Penjelasan
                        </button>
                    <?php else: ?>
                        <?php foreach ($penjelastindak as $prow): ?>
                            <button type="button" class="btn btn-sm btn-outline-warning" data-toggle="modal"
                                data-target="#modalEditTindak" data-id="<?= esc($prow['id']) ?>"
                                data-tindaklanjut="<?= esc($prow['tindaklanjut'] ?? '') ?>">
                                <i class="fas fa-edit"></i> Ubah Penjelasan
                            </button>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div id="tindak-content-wrap">
                <?php if (empty($penjelastindak)): ?>
                    <div class="tindak-empty"><i class="fas fa-inbox fa-2x text-muted mb-2 d-block"></i><span class="text-muted">Belum ada penjelasan lebih lanjut.</span></div>
                <?php else: ?>
                    <?php foreach ($penjelastindak as $prow): ?>
                        <div class="tindak-card">
                            <div class="tindak-label"><i class="fas fa-check-circle text-success"></i> Penjelasan Lebih Lanjut</div>
                            <div class="tindak-content">
                                <?php if (!empty($prow['tindaklanjut'])): ?>
                                    <?= nl2br(esc($prow['tindaklanjut'])) ?>
                                    <?php if ($canEdit): ?>
                                        <a href="#" class="btn-icon-del btn-del-tindak" data-id="<?= esc($prow['id']) ?>" title="Hapus">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted fst-italic">Belum diisi</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="approval-actions-row mt-3">
        <?php if (($userInGroupAdmin ?? false) || ($userInGroupDekom ?? false)): ?>
            <div class="action-card card shadow-sm" id="action-dekom">
                <div class="card-body py-2 text-center">
                    <small class="d-block font-weight-bold text-dark mb-2"><i class="fas fa-user-tie text-success"></i> Komisaris Utama</small>
                    <div class="d-flex justify-content-center flex-wrap" style="gap:4px;">
                        <a href="<?= base_url('Masalahhukum/approveSemuaKom') ?>"
                            onclick="return confirm('Setujui semua data sebagai Komisaris Utama?')"
                            class="btn <?= $allAccdekomApproved ? 'btn-success' : 'btn-primary' ?> btn-sm" id="btn-approve-dekom">
                            <i class="fas fa-check-double"></i> Setujui
                        </a>
                        <a href="<?= base_url('Masalahhukum/unapproveSemuaKom') ?>"
                            onclick="return confirm('Batalkan approval Komisaris Utama?')"
                            class="btn btn-outline-danger btn-sm"><i class="fas fa-ban"></i> Batal</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <?php if (($userInGroupAdmin ?? false) || ($userInGroupDireksi ?? false)): ?>
            <div class="action-card card shadow-sm <?= (!($canApprove ?? false)) ? 'blk-disabled' : '' ?>" id="action-dirut">
                <div class="card-body py-2 text-center">
                    <small class="d-block font-weight-bold text-dark mb-2"><i class="fas fa-user-shield text-primary"></i> Direktur Utama</small>
                    <?php if (!($canApprove ?? false)): ?>
                        <small class="text-muted d-block mb-1" style="font-size:10px;"><i class="fas fa-lock"></i> Komisaris harus setuju dulu</small>
                    <?php endif; ?>
                    <div class="d-flex justify-content-center flex-wrap" style="gap:4px;">
                        <a href="<?= base_url('Masalahhukum/approveSemuaDirut') ?>"
                            onclick="return confirm('Setujui semua data sebagai Direktur Utama?')"
                            class="btn <?= $allAccdirutApproved ? 'btn-success' : 'btn-primary' ?> btn-sm" id="btn-approve-dirut">
                            <i class="fas fa-check-double"></i> Setujui
                        </a>
                        <a href="<?= base_url('Masalahhukum/unapproveSemuaDirut') ?>"
                            onclick="return confirm('Batalkan approval Direktur Utama?')"
                            class="btn btn-outline-danger btn-sm"><i class="fas fa-ban"></i> Batal</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="cardpilihfaktor mb-4 mt-4">
        <div class="cardpilihfaktor-header"><h6>Pilih Halaman</h6></div>
        <div class="cardpilihfaktor-body">
            <div class="nav-pages-wrap">
                <?php
                $pages = [
                    ['url' => 'Penjelasanumum',       'label' => '1'],
                    ['url' => 'Tgjwbdir',              'label' => '2'],
                    ['url' => 'Tgjwbdekom',            'label' => '3'],
                    ['url' => 'Tgjwbkomite',           'label' => '4'],
                    ['url' => 'Strukturkomite',        'label' => '5'],
                    ['url' => 'Sahamdirdekom',         'label' => '6'],
                    ['url' => 'Shmusahadirdekom',      'label' => '7'],
                    ['url' => 'Shmdirdekomlain',       'label' => '8'],
                    ['url' => 'Keuangandirdekompshm',  'label' => '9'],
                    ['url' => 'Keluargadirdekompshm',  'label' => '10'],
                    ['url' => 'Paketkebijakandirdekom','label' => '11'],
                    ['url' => 'Rasiogaji',             'label' => '12'],
                    ['url' => 'Rapat',                 'label' => '13'],
                    ['url' => 'Kehadirandekom',        'label' => '14'],
                    ['url' => 'Fraudinternal',         'label' => '15'],
                    ['url' => 'Masalahhukum',          'label' => '16'],
                    ['url' => 'Transaksikepentingan',  'label' => '17'],
                    ['url' => 'Danasosial',            'label' => '18'],
                ];
                foreach ($pages as $p):
                ?>
                    <a href="<?= base_url($p['url']) ?>"
                        class="nav-page-btn <?= $p['url'] === 'Masalahhukum' ? 'active' : '' ?>"><?= $p['label'] ?></a>
                <?php endforeach; ?>
            </div>
            <div style="text-align:center;padding:4px 0 8px;">
                <a href="<?= base_url('ShowTransparansi') ?>"
                    style="font-size:12px;color:var(--gray-400);font-weight:600;">Kembali ke halaman periode</a>
            </div>
        </div>
    </div>
</div>

<!-- MODAL TAMBAH PENJELASAN -->
<div class="modal fade" id="modalTambahTindak" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header modal-header-dir">
                <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Tambah Penjelasan Lebih Lanjut</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="font-weight-bold">Penjelasan <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="tt-tindaklanjut" rows="5" placeholder="Tuliskan penjelasan lebih lanjut..."></textarea>
                </div>
                <div id="tindak-tambah-err" class="alert alert-danger mt-2" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Batal</button>
                <button type="button" class="btn btn-primary" id="btn-simpan-tindak"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL EDIT PENJELASAN -->
<div class="modal fade" id="modalEditTindak" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header modal-header-dir">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Ubah Penjelasan Lebih Lanjut</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="et-id">
                <div class="form-group">
                    <label class="font-weight-bold">Penjelasan</label>
                    <textarea class="form-control" id="et-tindaklanjut" rows="5"></textarea>
                </div>
                <div id="tindak-edit-err" class="alert alert-danger mt-2" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Batal</button>
                <button type="button" class="btn btn-primary" id="btn-simpan-edit-tindak"><i class="fas fa-save"></i> Simpan Perubahan</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL KOMENTAR -->
<div class="modal fade" id="modalKomentar" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content komentar-modal-content">
            <div class="modal-header komentar-modal-header">
                <div class="komentar-modal-title-wrap">
                    <div class="komentar-modal-icon-wrap"><i class="fas fa-comments"></i></div>
                    <div><div class="komentar-modal-title">Komentar — Permasalahan Hukum</div></div>
                </div>
                <button type="button" class="komentar-close-btn" data-dismiss="modal"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body komentar-modal-body">
                <div class="komentar-list-label"><i class="fas fa-history"></i> Riwayat Komentar</div>
                <div id="komentar-list-wrapper" class="komentar-list-wrapper">
                    <div id="komentar-list">
                        <div class="komentar-loading"><i class="fas fa-spinner fa-spin"></i> Memuat...</div>
                    </div>
                </div>
                <?php if (($userInGroupAdmin || $userInGroupDekom || $userInGroupDireksi || $userInGroupPE || $userInGroupDekom2 || $userInGroupDireksi2) ?? false): ?>
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
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal"><i class="fas fa-times"></i> Tutup</button>
                <?php if (($userInGroupAdmin || $userInGroupDekom || $userInGroupDireksi || $userInGroupPE || $userInGroupDekom2 || $userInGroupDireksi2) ?? false): ?>
                    <button type="button" class="btn btn-primary btn-sm" id="btn-kirim-komentar">
                        <i class="fas fa-paper-plane"></i> Kirim
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$_canEdit    = $canEdit ? 'true' : 'false';
$_canComment = (($userInGroupAdmin || $userInGroupDekom || $userInGroupDireksi || $userInGroupPE || $userInGroupDekom2 || $userInGroupDireksi2) ?? false) ? 'true' : 'false';
?>
<script>
(function () {
    'use strict';

    var CFG = {
        base:       '<?= rtrim(base_url(), '/') ?>',
        csrfName:   '<?= csrf_token() ?>',
        csrfHash:   '<?= csrf_hash() ?>',
        uid:        <?= (int) ($userId ?? 0) ?>,
        fname:      <?= json_encode($fullname ?? 'Unknown', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
        kbpr:       <?= json_encode($kodebpr ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
        pid:        <?= (int) ($periodeId ?? 0) ?>,
        canEdit:    <?= $_canEdit ?>,
        canComment: <?= $_canComment ?>,
        pollBase:   <?= (int) ($pollIntervalMs ?? 10000) ?>,
        isNull:     <?= $isNullRecord ? 'true' : 'false' ?>
    };

    function ready(fn) { if (typeof jQuery !== 'undefined') jQuery(fn); else setTimeout(function () { ready(fn); }, 40); }

    ready(function ($) {

        /* ── UTILS ── */
        function esc(s) { return s == null ? '' : String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
        function debounce(fn, ms) { var t; return function () { var a = arguments, c = this; clearTimeout(t); t = setTimeout(function () { fn.apply(c, a); }, ms); }; }
        function toast(msg, type) {
            type = type || 'success';
            var ic = { success:'check-circle', error:'exclamation-circle', info:'info-circle' };
            var el = $('<div class="t-item t-' + type + '"><i class="fas fa-' + ic[type] + ' fa-lg"></i><div>' + esc(msg) + '</div></div>');
            $('#toast-wrap').append(el);
            setTimeout(function () { el.fadeOut(400, function () { $(this).remove(); }); }, 3500);
        }
        function readCsrf() { var m = document.cookie.match(/ci_csrf_token=([^;]+)/); if (m) CFG.csrfHash = decodeURIComponent(m[1]); }
        function csrf() { readCsrf(); var d = {}; d[CFG.csrfName] = CFG.csrfHash; return d; }
        function updateCsrf(res) { readCsrf(); if (res && res.csrf_hash) CFG.csrfHash = res.csrf_hash; }
        function btnLoading(btn) { var ori = btn.html(); btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true); return function () { btn.html(ori).prop('disabled', false); }; }
        function timeAgo(s) {
            if (!s) return '-';
            var d = new Date(s.replace(' ','T')), diff = Math.floor((Date.now() - d.getTime()) / 60000);
            if (isNaN(diff) || diff < 1) return 'Baru saja';
            if (diff < 60) return diff + ' menit lalu';
            if (diff < 1440) return Math.floor(diff / 60) + ' jam lalu';
            return ('0'+d.getDate()).slice(-2)+'/'+('0'+(d.getMonth()+1)).slice(-2)+'/'+d.getFullYear();
        }
        function updateNotifBadge(n) {
            n = parseInt(n) || 0;
            var $btn = $('#btn-komentar-global'), $badge = $('#notif-global');
            if (n > 0) { $badge.text(n).show(); $btn.addClass('has-unread'); }
            else { $badge.hide(); $btn.removeClass('has-unread'); }
        }

        /* ── AUTOSAVE BAR ── */
        var $bar = $('#autosave-bar');
        function setSaveStatus(state, text) {
            if (!$bar.length) return;
            $bar.attr('class', 'autosave-bar as-' + state + ' flex-grow-1');
            var icons = { idle:'fa-circle as-dot-idle', saving:'fa-spinner fa-spin', saved:'fa-check-circle', error:'fa-exclamation-triangle' };
            var texts = { idle:'Siap menyimpan', saving:'Menyimpan...', saved:'Tersimpan otomatis', error:'Gagal menyimpan' };
            $bar.find('.as-icon').attr('class', 'fas ' + (icons[state] || 'fa-circle') + ' as-icon');
            $bar.find('.as-text').text(text || texts[state] || '');
        }

        function resetApprovalUI() {
            ['badge-dekom','badge-dirut'].forEach(function (id) {
                var $b = $('#' + id);
                $b.removeClass('badge-status-success').addClass('badge-status-secondary');
                $b.find('i').removeClass('fa-check-circle').addClass('fa-clock');
                $b.find('.approval-status-sub').text('Belum disetujui seluruhnya');
            });
            $('#btn-approve-dekom,#btn-approve-dirut').removeClass('btn-success').addClass('btn-primary');
            $('#action-dirut').addClass('blk-disabled');
        }

        /* ── TOTALS ── */
        function recalcTotals() {
            var selesai = (parseInt($('#hukumperdataselesai').val()) || 0) + (parseInt($('#hukumpidanaselesai').val()) || 0);
            var proses  = (parseInt($('#hukumperdataproses').val())  || 0) + (parseInt($('#hukumpidanaproses').val())  || 0);
            $('#totalselesai').val(selesai);
            $('#totalproses').val(proses);
        }

        /* ── AUTOSAVE ── */
        var _saveXhr = null, _debTimer = null;

        function triggerDebounce() {
            if (!CFG.canEdit || CFG.isNull) return;
            setSaveStatus('saving', 'Mengetik...');
            clearTimeout(_debTimer);
            _debTimer = setTimeout(doSave, 800);
        }

        function doSave() {
            setSaveStatus('saving', 'Menyimpan...');
            if (_saveXhr) _saveXhr.abort();

            var formData = {};
            $('#masalahForm').serializeArray().forEach(function (item) { formData[item.name] = item.value; });

            _saveXhr = $.ajax({
                url: CFG.base + '/Masalahhukum/tambahpenjelasAjax',
                method: 'POST', dataType: 'json',
                data: $.extend({}, csrf(), formData),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) {
                    updateCsrf(res);
                    if (res.status === 'success') {
                        setSaveStatus('saved', 'Tersimpan otomatis');
                        toast('Tersimpan otomatis', 'success');
                        resetApprovalUI();
                        setTimeout(function () { setSaveStatus('idle', 'Siap menyimpan'); }, 3500);
                    } else {
                        setSaveStatus('error', 'Gagal menyimpan');
                        toast(res.message || 'Gagal menyimpan', 'error');
                    }
                },
                error: function (xhr) {
                    if (xhr.statusText === 'abort') return;
                    setSaveStatus('error', 'Error');
                    toast('Terjadi kesalahan koneksi', 'error');
                }
            });
        }

        if (CFG.canEdit && !CFG.isNull) {
            $('#masalahForm').on('input change', '.mh-num', function () {
                if (parseInt($(this).val()) < 0) $(this).val(0);
                recalcTotals();
                triggerDebounce();
            });
        }

        recalcTotals();

        /* ── NO DATA ── */
        $('#btn-nodata').on('click', function () {
            if (!confirm('Konfirmasi: Tandai bahwa tidak ada data permasalahan hukum untuk periode ini?')) return;
            var restore = btnLoading($(this));
            $.ajax({
                url: CFG.base + '/Masalahhukum/setNodataAjax', method: 'POST', dataType: 'json',
                data: csrf(), headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) {
                    restore(); updateCsrf(res);
                    if (res.status === 'success') { toast(res.message || 'Data disimpan'); setTimeout(function () { location.reload(); }, 800); }
                    else { toast(res.message || 'Gagal', 'error'); }
                },
                error: function () { restore(); toast('Terjadi kesalahan server', 'error'); }
            });
        });

        $('#btn-batal-nodata').on('click', function () {
            if (!confirm('Batalkan status "Tidak Ada Data"?')) return;
            var restore = btnLoading($(this));
            $.ajax({
                url: CFG.base + '/Masalahhukum/batalNodataAjax', method: 'POST', dataType: 'json',
                data: csrf(), headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) {
                    restore(); updateCsrf(res);
                    if (res.status === 'success') { toast(res.message || 'Status dibatalkan'); setTimeout(function () { location.reload(); }, 800); }
                    else { toast(res.message || 'Gagal', 'error'); }
                },
                error: function () { restore(); toast('Terjadi kesalahan server', 'error'); }
            });
        });

        /* ── TINDAK LANJUT RENDER ── */
        function renderTindakLanjut(rows) {
            var $bw = $('#tindak-btn-wrap'), $cw = $('#tindak-content-wrap');
            if (CFG.canEdit) {
                if (!rows || !rows.length) {
                    $bw.html('<button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#modalTambahTindak"><i class="fas fa-plus"></i> Tambah Penjelasan</button>');
                } else {
                    $bw.html('<button type="button" class="btn btn-sm btn-outline-warning" data-toggle="modal" data-target="#modalEditTindak"'
                        + ' data-id="' + esc(rows[0].id) + '" data-tindaklanjut="' + esc(rows[0].tindaklanjut || '') + '">'
                        + '<i class="fas fa-edit"></i> Ubah Penjelasan</button>');
                }
            }
            var html = '';
            if (!rows || !rows.length) {
                html = '<div class="tindak-empty"><i class="fas fa-inbox fa-2x text-muted mb-2 d-block"></i><span class="text-muted">Belum ada penjelasan lebih lanjut.</span></div>';
            } else {
                rows.forEach(function (row) {
                    html += '<div class="tindak-card"><div class="tindak-label"><i class="fas fa-check-circle text-success"></i> Penjelasan Lebih Lanjut</div>'
                        + '<div class="tindak-content">';
                    if (row.tindaklanjut) {
                        html += esc(row.tindaklanjut).replace(/\n/g,'<br>');
                        if (CFG.canEdit) html += ' <a href="#" class="btn-icon-del btn-del-tindak" data-id="' + esc(row.id) + '" title="Hapus"><i class="fas fa-trash-alt"></i></a>';
                    } else { html += '<span class="text-muted fst-italic">Belum diisi</span>'; }
                    html += '</div></div>';
                });
            }
            $cw.html(html);
            $('#tt-tindaklanjut').val('');
            $('#tindak-tambah-err').hide();
        }

        /* ── TAMBAH TINDAK ── */
        var execTambahTindak = debounce(function () {
            var tindak = $.trim($('#tt-tindaklanjut').val());
            if (!tindak) { $('#tindak-tambah-err').text('Penjelasan wajib diisi.').show(); return; }
            var restore = btnLoading($('#btn-simpan-tindak'));
            $('#tindak-tambah-err').hide();
            $.ajax({
                url: CFG.base + '/Masalahhukum/tambahketerangan', method: 'POST', dataType: 'json',
                data: $.extend({}, csrf(), { tindaklanjut: tindak }),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) {
                    restore(); updateCsrf(res);
                    if (res.status === 'success') { $('#modalTambahTindak').modal('hide'); toast(res.message || 'Penjelasan berhasil disimpan'); renderTindakLanjut(res.penjelastindak || []); }
                    else { $('#tindak-tambah-err').text(res.message || 'Terjadi kesalahan').show(); }
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

        /* ── EDIT TINDAK ── */
        $('#modalEditTindak').on('show.bs.modal', function (e) {
            var btn = $(e.relatedTarget);
            $('#et-id').val(btn.data('id'));
            $('#et-tindaklanjut').val(btn.data('tindaklanjut'));
            $('#tindak-edit-err').hide();
        });

        var execEditTindak = debounce(function () {
            var id = $('#et-id').val(), tindak = $.trim($('#et-tindaklanjut').val());
            if (!tindak) { $('#tindak-edit-err').text('Penjelasan tidak boleh kosong.').show(); return; }
            var restore = btnLoading($('#btn-simpan-edit-tindak'));
            $('#tindak-edit-err').hide();
            $.ajax({
                url: CFG.base + '/Masalahhukum/editketerangan', method: 'POST', dataType: 'json',
                data: $.extend({}, csrf(), { id: id, tindaklanjut: tindak }),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) {
                    restore(); updateCsrf(res);
                    if (res.status === 'success') { $('#modalEditTindak').modal('hide'); toast(res.message || 'Penjelasan berhasil diubah'); renderTindakLanjut(res.penjelastindak || []); }
                    else { $('#tindak-edit-err').text(res.message || 'Terjadi kesalahan').show(); }
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

        /* ── HAPUS TINDAK ── */
        $(document).on('click', '.btn-del-tindak', function (e) {
            e.preventDefault();
            var id = $(this).data('id'), $btn = $(this);
            if (!confirm('Hapus penjelasan ini?')) return;
            $btn.html('<i class="fas fa-spinner fa-spin"></i>').css('pointer-events', 'none');
            $.ajax({
                url: CFG.base + '/Masalahhukum/setNullKolomTindak', method: 'POST', dataType: 'json',
                data: $.extend({}, csrf(), { id: id }),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) {
                    updateCsrf(res);
                    if (res.status === 'success') { toast(res.message || 'Data dihapus'); renderTindakLanjut([]); }
                    else { toast(res.message || 'Gagal menghapus', 'error'); $btn.html('<i class="fas fa-trash-alt"></i>').css('pointer-events', ''); }
                },
                error: function () { toast('Terjadi kesalahan server', 'error'); $btn.html('<i class="fas fa-trash-alt"></i>').css('pointer-events', ''); }
            });
        });

        /* ── KOMENTAR ── */
        function loadKomentarList(scroll) {
            var list = $('#komentar-list');
            list.html('<div class="komentar-loading"><i class="fas fa-spinner fa-spin"></i> Memuat...</div>');
            $.ajax({
                url: CFG.base + '/Masalahhukum/getKomentarByFaktorId',
                method: 'GET', dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) {
                    var items = Array.isArray(res) ? res : (res.comments || []);
                    if (res.unread_count !== undefined) updateNotifBadge(res.unread_count);
                    if (!items.length) { list.html('<div class="komentar-empty"><i class="fas fa-comment-slash fa-2x mb-2 d-block"></i>Belum ada komentar.</div>'); return; }
                    var frag = document.createDocumentFragment(), tmp = document.createElement('div');
                    items.forEach(function (k) {
                        var own = parseInt(k.user_id) === CFG.uid;
                        tmp.innerHTML = '<div class="km-item' + (own ? ' km-own' : '') + '"><div class="km-meta">'
                            + '<span class="km-author"><i class="fas fa-user-circle"></i> ' + esc(k.fullname || '?') + (own ? ' <span class="km-you">Anda</span>' : '') + '</span>'
                            + '<span class="km-time"><i class="fas fa-clock"></i> ' + timeAgo(k.created_at) + '</span>'
                            + '</div><p class="km-text">' + esc(k.komentar) + '</p></div>';
                        frag.appendChild(tmp.firstElementChild);
                    });
                    list.empty()[0].appendChild(frag);
                    if (scroll) { var w = document.getElementById('komentar-list-wrapper'); if (w) requestAnimationFrame(function () { w.scrollTop = w.scrollHeight; }); }
                },
                error: function () { list.html('<div class="komentar-empty text-danger"><i class="fas fa-exclamation-circle"></i> Gagal memuat.</div>'); }
            });
        }

        function markAllRead() {
            $.ajax({
                url: CFG.base + '/Masalahhukum/markUserCommentsAsRead', method: 'POST',
                data: $.extend({}, csrf(), { id: 'Masalahhukum', kodebpr: CFG.kbpr, user_id: CFG.uid, periode_id: CFG.pid }),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) { updateCsrf(res); updateNotifBadge(0); _lastUnread = 0; }
            });
        }

        var _sendingKomentar = false;
        var execKomentar = debounce(function () {
            if (_sendingKomentar) return;
            var teks = $.trim($('#k-teks').val());
            if (!teks)           { toast('Komentar tidak boleh kosong.', 'error'); return; }
            if (teks.length < 3) { toast('Komentar minimal 3 karakter.', 'error'); return; }
            _sendingKomentar = true;
            var restore = btnLoading($('#btn-kirim-komentar'));
            $.ajax({
                url: CFG.base + '/Masalahhukum/Tambahkomentar', method: 'POST', dataType: 'json',
                data: $.extend({}, csrf(), { TambahKomentar: 1, id: 'Masalahhukum', komentar: teks, fullname: CFG.fname, kodebpr: CFG.kbpr, date: new Date().toISOString().slice(0,19).replace('T',' ') }),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) {
                    _sendingKomentar = false; restore(); updateCsrf(res);
                    if (res && res.status === 'success') { $('#k-teks').val(''); $('#k-char-count').text('0 / 1000').removeClass('danger'); toast('Komentar berhasil dikirim'); loadKomentarList(true); _lastUnread = 0; _pollDelay = 5000; }
                    else { toast((res && res.message) || 'Gagal mengirim', 'error'); }
                },
                error: function () { _sendingKomentar = false; restore(); toast('Gagal mengirim komentar', 'error'); }
            });
        }, 400);

        $('#btn-komentar-global').on('click', function () { loadKomentarList(true); markAllRead(); $('#modalKomentar').modal('show'); });
        $('#btn-kirim-komentar').on('click', execKomentar);
        $('#k-teks')
            .on('keydown', function (e) { if (e.ctrlKey && e.key === 'Enter') { e.preventDefault(); execKomentar(); } })
            .on('input', function () { var l = this.value.length, $el = $('#k-char-count'); $el.text(l + ' / 1000'); l > 950 ? $el.addClass('danger') : $el.removeClass('danger'); });

        /* ── POLL ── */
        var _pollTimer = null, _pollDelay = CFG.pollBase, _lastUnread = 0, _pollAbort = null;

        function schedulePoll(delay) { clearTimeout(_pollTimer); _pollTimer = setTimeout(doPoll, delay); }
        function doPoll() {
            if (_pollAbort) { try { _pollAbort.abort(); } catch (e) { } }
            var aborter = { aborted: false, abort: function () { this.aborted = true; } };
            _pollAbort = aborter;
            $.ajax({
                url: CFG.base + '/Masalahhukum/getUnreadCommentCountForFactor',
                method: 'GET', dataType: 'json',
                data: { id: 'Masalahhukum', kodebpr: CFG.kbpr, user_id: CFG.uid, periode_id: CFG.pid },
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) {
                    if (aborter.aborted) return;
                    var n = parseInt(res.unread_count) || 0;
                    updateNotifBadge(n);
                    _pollDelay = n > _lastUnread ? Math.max(5000, CFG.pollBase / 2) : Math.min(60000, CFG.pollBase);
                    _lastUnread = n;
                    if (res.next_poll_ms) _pollDelay = Math.max(5000, parseInt(res.next_poll_ms));
                    schedulePoll(_pollDelay);
                },
                error: function () {
                    if (aborter.aborted) return;
                    _pollDelay = Math.min(120000, _pollDelay * 2);
                    schedulePoll(_pollDelay);
                }
            });
        }

        $(document).on('hidden.bs.modal', '.modal', function () { $(this).find('.alert-danger').hide().text(''); });

        document.addEventListener('visibilitychange', function () {
            if (document.hidden) { clearTimeout(_pollTimer); }
            else { _pollDelay = CFG.pollBase; schedulePoll(1000); }
        }, { passive: true });

        schedulePoll(_pollDelay);
    });
}());
</script>

<style>
    :root {
        --brand: #4f46e5; --brand-light: #818cf8; --brand-bg: #eef2ff;
        --success: #10b981; --success-bg: #d1fae5; --danger: #ef4444; --danger-bg: #fee2e2;
        --warning: #f59e0b; --warning-bg: #fef3c7;
        --gray-50: #f8fafc; --gray-100: #f1f5f9; --gray-200: #e2e8f0; --gray-400: #94a3b8;
        --gray-500: #64748b; --gray-700: #334155; --gray-800: #1e293b; --gray-900: #0f172a;
        --blue: #1f2aa8;
        --sh-sm: 0 1px 3px rgba(0,0,0,.08),0 1px 2px rgba(0,0,0,.06);
        --sh: 0 4px 6px -1px rgba(0,0,0,.1),0 2px 4px -1px rgba(0,0,0,.06);
        --sh-lg: 0 10px 25px -3px rgba(0,0,0,.1),0 4px 6px -2px rgba(0,0,0,.05);
        --tr: all .2s cubic-bezier(.4,0,.2,1);
        --grad: linear-gradient(135deg,#141863 0%,#1f2aa8 100%);
        --r-sm: 8px; --r: 12px; --r-lg: 16px;
    }
    * { font-family: 'Plus Jakarta Sans', sans-serif; }

    .page-header { text-align:center; padding:28px 16px 20px; background:white; border-bottom:1px solid var(--gray-100); position:relative; overflow:hidden; }
    .page-header::before { content:''; position:absolute; top:-60px; right:-60px; width:180px; height:180px; border-radius:50%; background:radial-gradient(circle,#eef2ff,transparent 70%); }
    .page-title { font-size:clamp(1.05rem,3.5vw,1.5rem); font-weight:800; color:var(--gray-900); margin-bottom:6px; letter-spacing:-.5px; position:relative; z-index:1; }
    .page-subtitle { font-size:.95rem; font-weight:600; color:var(--gray-700); margin-bottom:10px; position:relative; z-index:1; }
    .badge-namabpr { display:inline-block; background:var(--grad); color:white; font-size:.9rem; font-weight:700; padding:5px 18px; border-radius:100px; letter-spacing:.3px; position:relative; z-index:1; box-shadow:0 4px 12px rgba(79,70,229,.3); }

    .approval-badges-row { display:flex; gap:10px; flex-wrap:wrap; justify-content:center; }
    .approval-status-badge { display:flex; align-items:center; gap:10px; padding:9px 14px; border-radius:var(--r); font-size:12.5px; font-weight:600; border:1.5px solid transparent; transition:var(--tr); }
    .approval-status-badge i { font-size:18px; flex-shrink:0; }
    .approval-status-label { font-weight:700; font-size:12px; }
    .approval-status-sub { font-size:11px; opacity:.8; }
    .badge-status-secondary { background:var(--gray-100); color:var(--gray-500); border-color:var(--gray-200); }
    .badge-status-success { background:var(--success-bg); color:#065f46; border-color:#6ee7b7; }

    /* AUTOSAVE BAR */
    .autosave-bar { display:inline-flex; align-items:center; gap:8px; padding:7px 14px; border-radius:var(--r); font-size:12px; font-weight:600; border:1.5px solid var(--gray-200); background:white; transition:var(--tr); }
    .autosave-bar.as-idle { color:var(--gray-400); }
    .autosave-bar.as-saving { color:var(--warning); border-color:#fcd34d; background:var(--warning-bg); }
    .autosave-bar.as-saved { color:#065f46; border-color:#6ee7b7; background:var(--success-bg); }
    .autosave-bar.as-error { color:#991b1b; border-color:#fca5a5; background:var(--danger-bg); }
    .as-icon { font-size:10px; }
    .as-dot-idle { color:var(--gray-300); }
    .as-hint { color:var(--gray-400); font-weight:400; }

    /* FRAUD INPUT (reused from fraudinternal) */
    .fraud-row-2col { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:12px; }
    .fraud-row-center { display:grid; grid-template-columns:1fr; gap:14px; margin-bottom:12px; max-width:50%; }
    .fraud-field { display:flex; flex-direction:column; gap:4px; }
    .fraud-label { font-size:12px; font-weight:700; color:var(--gray-700); margin:0; }
    .fraud-input-wrap { display:flex; align-items:center; border:1.5px solid var(--gray-200); border-radius:var(--r-sm); overflow:hidden; background:white; transition:var(--tr); }
    .fraud-input-wrap:focus-within { border-color:var(--brand); box-shadow:0 0 0 3px rgba(79,70,229,.1); }
    .fraud-input-readonly { background:var(--gray-50); }
    .fraud-input-readonly .fraud-input { background:var(--gray-50); color:var(--gray-500); }
    .fraud-input { border:none!important; outline:none!important; box-shadow:none!important; border-radius:0!important; padding:8px 10px!important; font-size:14px!important; font-weight:700!important; color:var(--gray-800)!important; width:100%; background:transparent; }
    .fraud-input-suffix { padding:0 10px; font-size:11px; font-weight:700; color:var(--gray-400); white-space:nowrap; border-left:1px solid var(--gray-100); background:var(--gray-50); }
    .fraud-section-badge { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; background:rgba(255,255,255,.2); color:white; font-size:11px; font-weight:800; border-radius:8px; margin-right:10px; flex-shrink:0; }

    /* TOTAL BANNER */
    .mh-total-banner { background:linear-gradient(135deg,#eef2ff 0%,#e0e7ff 100%); border-radius:var(--r-lg); border:1.5px solid #c7d2fe; padding:16px; margin-bottom:16px; }
    .mh-total-title { font-size:13px; font-weight:800; color:var(--blue); margin-bottom:12px; }
    .mh-total-banner .fraud-input { font-size:20px!important; font-weight:800!important; color:var(--blue)!important; }

    /* PKD BOTTOM BAR */
    .pkd-bottom-bar { position:fixed; bottom:24px; right:24px; z-index:9990; display:flex; flex-direction:column; align-items:flex-end; gap:8px; }

    /* KOMENTAR BUTTON */
    .btn-komentar-global-btn { position:relative; width:36px; height:36px; border-radius:50%; background:white; border:none; color:var(--blue); font-size:17px; display:inline-flex; align-items:center; justify-content:center; box-shadow:0 2px 10px rgba(0,0,0,.18); cursor:pointer; transition:all .25s cubic-bezier(.4,0,.2,1); outline:none; }
    .btn-komentar-global-btn:hover { background:var(--brand-bg); color:var(--brand); transform:scale(1.13); }
    .btn-komentar-global-btn.has-unread { animation:btn-shake 1.8s ease infinite; background:var(--brand-bg); color:var(--brand); }
    @keyframes btn-shake { 0%,100%{transform:rotate(0)scale(1)} 10%{transform:rotate(-14deg)scale(1.08)} 20%{transform:rotate(12deg)scale(1.08)} 30%{transform:rotate(-10deg)scale(1.05)} 40%{transform:rotate(8deg)scale(1.05)} 50%{transform:rotate(0)scale(1)} }
    .btn-komentar-ripple { position:absolute; inset:-4px; border-radius:50%; border:2px solid var(--brand-light); opacity:0; pointer-events:none; }
    .btn-komentar-global-btn.has-unread .btn-komentar-ripple { animation:ripple-out 1.8s ease infinite; }
    @keyframes ripple-out { 0%{transform:scale(.9);opacity:.7} 100%{transform:scale(1.6);opacity:0} }

    .notif-badge { position:absolute; top:-7px; right:-7px; background:var(--danger); color:#fff; border-radius:50%; padding:2px 5px; font-size:10px; font-weight:700; min-width:17px; text-align:center; line-height:1.3; box-shadow:0 2px 6px rgba(239,68,68,.5); animation:pulse 2s infinite; }
    @keyframes pulse { 0%,100%{box-shadow:0 0 0 0 rgba(239,68,68,.4)} 50%{box-shadow:0 0 0 5px rgba(239,68,68,0)} }

    /* TINDAK LANJUT */
    .tindak-card { background:var(--gray-50); border-radius:var(--r-lg); border:1.5px solid var(--gray-200); padding:16px; margin-bottom:12px; }
    .tindak-label { font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:var(--gray-500); margin-bottom:8px; }
    .tindak-content { font-size:13.5px; color:var(--gray-700); line-height:1.7; background:white; border:1.5px solid var(--gray-200); border-radius:var(--r-sm); padding:10px 14px; }
    .tindak-empty { text-align:center; padding:30px 16px; color:var(--gray-400); }
    .btn-icon-del { display:inline-flex; align-items:center; justify-content:center; width:24px; height:24px; border-radius:6px; background:var(--danger-bg); color:var(--danger); font-size:11px; margin-left:6px; vertical-align:middle; text-decoration:none; transition:var(--tr); }
    .btn-icon-del:hover { background:var(--danger); color:white; }

    /* APPROVAL */
    .approval-actions-row { display:flex; gap:12px; flex-wrap:wrap; justify-content:center; }
    .action-card { border-radius:var(--r)!important; border:1.5px solid var(--gray-200)!important; transition:var(--tr); overflow:hidden; min-width:200px; }
    .action-card:hover { border-color:var(--brand-light)!important; box-shadow:0 8px 24px rgba(79,70,229,.12)!important; transform:translateY(-2px); }
    .blk-disabled { opacity:.4; pointer-events:none; }

    /* BUTTONS */
    .btn { transition:var(--tr); font-weight:600; }
    .btn:hover { transform:translateY(-2px); box-shadow:var(--sh); }
    .btn:active { transform:scale(.96)!important; }
    .btn-sm { font-size:12px; }
    .btn-kembali { display:inline-flex; align-items:center; gap:6px; background:white; border:1.5px solid var(--gray-200); border-radius:var(--r-sm); padding:7px 14px; font-size:.875rem; font-weight:600; color:var(--gray-500); text-decoration:none; transition:var(--tr); margin:12px 0 0 8px; }
    .btn-kembali:hover { background:var(--brand-bg); border-color:var(--brand-light); color:var(--brand); text-decoration:none; }

    /* NAV PAGES */
    .cardpilihfaktor { border:1px solid var(--gray-200); border-radius:var(--r); max-width:720px; margin:0 auto; }
    .cardpilihfaktor-header { text-align:center; background:var(--gray-50); padding:6px; border-bottom:1px solid var(--gray-200); }
    .cardpilihfaktor-header h6 { margin:0; font-size:.9rem; font-weight:700; }
    .cardpilihfaktor-body { padding:8px 10px; }
    .nav-pages-wrap { display:flex; flex-wrap:wrap; gap:4px; justify-content:center; }
    .nav-page-btn { display:inline-flex; align-items:center; justify-content:center; min-width:32px; height:32px; padding:0 6px; border-radius:6px; border:1.5px solid var(--gray-200); background:white; color:var(--gray-700); font-size:12px; font-weight:600; text-decoration:none; transition:var(--tr); }
    .nav-page-btn:hover { background:var(--brand-bg); border-color:var(--brand-light); color:var(--brand); text-decoration:none; }
    .nav-page-btn.active { background:var(--grad); color:white; border-color:var(--blue); }

    /* KOMENTAR MODAL */
    .komentar-modal-content { border-radius:18px!important; overflow:hidden; border:none; box-shadow:0 20px 60px rgba(0,0,0,.18)!important; }
    .komentar-modal-header { background:var(--grad); padding:18px 22px!important; border:none!important; display:flex; align-items:center; justify-content:space-between; }
    .komentar-modal-title-wrap { display:flex; align-items:center; gap:12px; }
    .komentar-modal-icon-wrap { width:40px; height:40px; border-radius:12px; background:rgba(255,255,255,.18); display:flex; align-items:center; justify-content:center; font-size:18px; color:white; flex-shrink:0; }
    .komentar-modal-title { font-size:14px; font-weight:800; color:white; }
    .komentar-close-btn { width:32px; height:32px; border-radius:50%; background:rgba(255,255,255,.15); border:none; color:white; font-size:14px; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all .2s; flex-shrink:0; }
    .komentar-close-btn:hover { background:rgba(255,255,255,.3); transform:rotate(90deg); }
    .komentar-modal-body { padding:18px 20px!important; background:var(--gray-50); }
    .komentar-modal-footer { background:white; border-top:1px solid var(--gray-100)!important; padding:10px 20px!important; }
    .komentar-list-label { font-size:11px; font-weight:700; color:var(--gray-500); text-transform:uppercase; letter-spacing:.8px; margin-bottom:8px; }
    .komentar-list-wrapper { background:white; border:1.5px solid var(--gray-200); border-radius:12px; max-height:300px; overflow-y:auto; padding:10px; margin-bottom:14px; scroll-behavior:smooth; }
    .komentar-list-wrapper::-webkit-scrollbar { width:5px; }
    .komentar-list-wrapper::-webkit-scrollbar-thumb { background:var(--gray-200); border-radius:10px; }
    .komentar-loading,.komentar-empty { text-align:center; color:var(--gray-400); padding:24px 0; font-size:13px; }
    .km-item { background:var(--gray-50); border-left:3px solid var(--brand-light); border-radius:10px; padding:9px 12px; margin-bottom:7px; box-shadow:var(--sh-sm); animation:kmIn .25s ease both; }
    .km-item.km-own { border-left-color:var(--success); background:#f0fdf4; }
    @keyframes kmIn { from{opacity:0;transform:translateY(6px)} to{opacity:1;transform:translateY(0)} }
    .km-meta { display:flex; justify-content:space-between; margin-bottom:4px; flex-wrap:wrap; gap:3px; }
    .km-author { font-weight:700; font-size:12px; color:var(--gray-800); display:flex; align-items:center; gap:5px; }
    .km-you { font-size:9px; background:var(--success); color:white; padding:1px 5px; border-radius:100px; font-weight:700; }
    .km-time { font-size:11px; color:var(--gray-400); }
    .km-text { font-size:13px; white-space:pre-wrap; margin:0; color:var(--gray-700); line-height:1.5; }
    .komentar-input-wrap { background:white; border:1.5px solid var(--gray-200); border-radius:12px; padding:12px; transition:border-color .2s; }
    .komentar-input-wrap:focus-within { border-color:var(--brand); box-shadow:0 0 0 3px rgba(79,70,229,.1); }
    .komentar-input-label { font-size:11px; font-weight:700; color:var(--gray-500); margin-bottom:6px; text-transform:uppercase; letter-spacing:.6px; }
    .komentar-textarea { width:100%; border:none; outline:none; resize:none; font-size:13px; color:var(--gray-700); line-height:1.6; background:transparent; }
    .komentar-input-footer { display:flex; justify-content:space-between; align-items:center; margin-top:6px; padding-top:6px; border-top:1px solid var(--gray-100); }
    .komentar-hint { font-size:11px; color:var(--gray-400); }
    .komentar-char { font-size:11px; color:var(--gray-400); font-weight:600; transition:color .2s; }
    .komentar-char.danger { color:var(--danger); }

    /* MODAL */
    .modal-header-dir { background:var(--grad); color:white; }
    .modal-content { border-radius:var(--r-lg); overflow:hidden; border:none; box-shadow:var(--sh-lg); }
    .modal-body { padding:18px 22px!important; }
    .modal-footer { padding:12px 22px!important; }
    .modal-body .form-group { margin-bottom:14px!important; }
    .modal-body .form-control { width:100%!important; box-sizing:border-box!important; border-radius:8px!important; border:1.5px solid var(--gray-200)!important; padding:8px 12px!important; font-size:13px!important; transition:var(--tr)!important; }
    .modal-body .form-control:focus { border-color:var(--brand)!important; box-shadow:0 0 0 3px rgba(79,70,229,.15)!important; outline:none!important; }
    .modal-dialog { margin:16px auto!important; max-width:680px!important; }

    /* TOAST */
    .t-item { background:white; border-radius:var(--r); padding:12px 16px; margin-bottom:8px; box-shadow:var(--sh-lg); display:flex; align-items:center; gap:10px; font-size:13px; animation:tIn .3s ease; pointer-events:all; border-left:4px solid; }
    .t-item.t-success { border-color:var(--success); color:#065f46; }
    .t-item.t-error { border-color:var(--danger); color:#991b1b; }
    .t-item.t-info { border-color:#06b6d4; color:#164e63; }
    @keyframes tIn { from{transform:translateX(110%);opacity:0} to{transform:translateX(0);opacity:1} }

    .card-header { border-radius:var(--r-lg) var(--r-lg) 0 0!important; }

    @media(max-width:576px) {
        .fraud-row-2col { grid-template-columns:1fr; }
        .fraud-row-center { max-width:100%; }
        .modal-dialog { margin:8px!important; max-width:calc(100% - 16px)!important; }
        .approval-actions-row { flex-direction:column; align-items:center; }
        .action-card { width:100%; max-width:340px; }
        .approval-badges-row { flex-direction:column; align-items:center; }
        .nav-pages-wrap { gap:3px; }
        .nav-page-btn { min-width:28px; height:28px; font-size:11px; }
    }
</style>