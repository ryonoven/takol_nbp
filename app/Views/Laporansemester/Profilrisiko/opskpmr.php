<?php
$peringkat13 = isset($nilai13) && is_array($nilai13) ? ($nilai13['penilaiankredit'] ?? null) : null;
$keterangan13 = isset($nilai13) && is_array($nilai13) ? ($nilai13['keterangan'] ?? '') : '';
$peringkat14 = isset($nilai14) && is_array($nilai14) ? ($nilai14['penilaiankredit'] ?? null) : null;

$peringkatLabels = ['1' => 'Sangat Rendah', '2' => 'Rendah', '3' => 'Sedang', '4' => 'Tinggi', '5' => 'Sangat Tinggi'];

function getBadgeClassOpsKpmr($nilai)
{
    static $map = ['1' => 'badge-info', '2' => 'badge-success', '3' => 'badge-warning', '4' => 'badge-danger', '5' => 'badge-dark'];
    return $map[$nilai] ?? 'badge-secondary';
}

$canEdit = ($userInGroupAdmin ?? false) || ($userInGroupPE ?? false);
$canApproveDir = ($userInGroupAdmin ?? false) || ($userInGroupDireksi2 ?? false) || ($userInGroupDireksi ?? false);
$isManual13 = isset($nilai13['is_manual']) && $nilai13['is_manual'] == 1;
$kesimpulan13 = $nilai13 ?? null;
?>

<div id="toast-wrapper" aria-live="polite"></div>

<div class="alert alert-info my-2">
    <?php if (isset($bprData) && isset($periodeDetail)): ?>
        <strong><?= esc($bprData['namabpr'] ?? 'Nama BPR') ?></strong>
        - Periode Pelaporan Semester <?= esc($periodeDetail['semester']) ?> Tahun <?= esc($periodeDetail['tahun']) ?>
    <?php elseif (isset($periodeDetail)): ?>
        <strong>Periode:</strong> Semester <?= esc($periodeDetail['semester']) ?> Tahun <?= esc($periodeDetail['tahun']) ?>
    <?php else: ?>
        <strong>Periode belum ditentukan</strong>
    <?php endif; ?>
</div>

<div id="sync-bar" style="display:none;">
    <div id="sync-bar-text" class="sync-bar-text"></div>
    <button type="button" id="btn-sync-trigger" class="btn btn-sm btn-warning">
        <i class="fas fa-sync-alt"></i> Sinkronisasi
    </button>
</div>

<!-- Bar: tidak ada data periode sebelumnya -->
<div id="sync-nodata-bar" style="display:none;">
    <i class="fas fa-info-circle"></i>
    <span id="sync-nodata-text"></span>
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

            <div class="button-bar">
                <a href="Risikooperasional" class="kpmr-btn icon-link icon-link-hover">🡸 Laporan Risiko Operasional
                    Inheren</a>
            </div>

            <div class="text-center mb-3 mt-3">
                <h4 class="mb-0">
                    <i class="fas fa-info-circle text-primary"></i>
                    <strong>Tingkat Risiko Operasional KPMR</strong>
                </h4>
                <small class="text-muted">
                    <i class="fas fa-file-alt"></i>
                    Laporan Profil Risiko Semester <?= esc($periodeDetail['semester'] ?? '') ?> Tahun
                    <?= esc($periodeDetail['tahun'] ?? '') ?>
                </small>
            </div>

            <!-- Peringkat 13 -->
            <div class="text-center mb-3">
                <div class="mt-2">
                    <span id="badge-peringkat13" class="badge badge-lg <?= getBadgeClassOpsKpmr($peringkat13) ?>"
                        style="font-size:1.1em;padding:.5em 1em;">
                        <i class="fas fa-check-circle"></i>
                        Peringkat penilaian: <?= esc($peringkat13 ?? 'N/A') ?>
                        [<?= $peringkatLabels[$peringkat13] ?? 'N/A' ?>]
                    </span>
                    <?php if ($canEdit): ?>
                        <?php if (!$isManual13): ?>
                            <button type="button" class="btn btn-sm btn-outline-info ml-2" data-bs-toggle="collapse"
                                data-bs-target="#editPeringkat13" aria-expanded="false">
                                <i class="fa fa-caret-down"></i>
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-sm btn-warning ml-2" id="btnResetPeringkat13"
                                title="Kembalikan ke perhitungan otomatis">
                                <i class="fa fa-undo"></i> Reset Otomatis
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <?php if ($canEdit): ?>
                    <div id="editPeringkat13" class="collapse mt-2">
                        <div class="p-3 border rounded">
                            <select id="peringkat13_manual" class="form-control w-50 mx-auto mb-2">
                                <option value="">-- Pilih Peringkat --</option>
                                <?php foreach ($peringkatLabels as $val => $label): ?>
                                    <option value="<?= $val ?>" <?= $peringkat13 == $val ? 'selected' : '' ?>><?= $val ?> -
                                        <?= $label ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="text-center">
                                <button type="button" class="btn btn-warning btn-sm" id="btnSimpanPeringkat13">
                                    <i class="fas fa-edit"></i> Update
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Peringkat 14 -->
                <div class="mt-2">
                    <span class="badge badge-lg <?= getBadgeClassOpsKpmr($peringkat14) ?>"
                        style="font-size:1.1em;padding:.5em 1em;">
                        <i class="fas fa-check-circle"></i>
                        Peringkat penilaian pada periode sebelumnya: <?= esc($peringkat14 ?? 'N/A') ?>
                        [<?= $peringkatLabels[$peringkat14] ?? 'N/A' ?>]
                    </span>
                    <?php if ($canEdit): ?>
                        <button type="button" class="btn btn-sm btn-outline-info ml-2" data-bs-toggle="collapse"
                            data-bs-target="#editNilai14">
                            <i class="fa fa-caret-down"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Form Edit Nilai 14 -->
            <div id="editNilai14" class="collapse">
                <div class="p-3 border-top">
                    <form action="<?= base_url('Risikooperasionalkpmr/simpanNilai14') ?>" method="post">
                        <?= csrf_field() ?>
                        <select name="penilaiankredit" class="form-control w-50 mx-auto" required>
                            <?php if (!empty($nilai14)): ?>
                                <option value="" disabled selected>-- Nilai Saat Ini:
                                    <?= esc($nilai14['penilaiankredit']) ?> --
                                </option>
                            <?php else: ?>
                                <option value="">-- Pilih Tingkat Risiko --</option>
                            <?php endif; ?>
                            <option value="1">Sangat Rendah (1)</option>
                            <option value="2">Rendah (2)</option>
                            <option value="3">Cukup (3)</option>
                            <option value="4">Tinggi (4)</option>
                            <option value="5">Sangat Tinggi (5)</option>
                        </select>
                        <div class="text-center mt-2">
                            <button type="submit"
                                class="btn <?= !empty($nilai14) ? 'btn-warning' : 'btn-primary' ?> btn-sm">
                                <i class="fas fa-<?= !empty($nilai14) ? 'edit' : 'save' ?>"></i>
                                <?= !empty($nilai14) ? 'Update' : 'Simpan' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Approval badge -->
            <div class="text-right mb-2">
                <span id="approval-badge-main"
                    class="badge <?= ($allApproved ?? false) ? 'badge-success' : 'badge-secondary' ?>"
                    style="font-size:14px;">
                    <?php if ($allApproved ?? false): ?>
                        Disetujui oleh <strong>Direktur Utama</strong><br>
                        <?= !empty($nilai13['approved_at']) ? date('d/m/Y H:i:s', strtotime($nilai13['approved_at'])) : '-' ?>
                    <?php else: ?>
                        Belum Disetujui Seluruhnya<br>Oleh Direktur Utama
                    <?php endif; ?>
                </span>
            </div>

            <!-- Factors area -->
            <div class="card-body">
                <div class="mb-2"><strong>A. Kertas Kerja Operasional KPMR</strong></div>

                <div id="factors-skeleton" style="display:block;">
                    <?php for ($i = 0; $i < 3; $i++): ?>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <div class="skeleton skeleton-text" style="width:60%;"></div>
                            </div>
                            <div class="card-body">
                                <div class="skeleton skeleton-text mb-2" style="width:80%;"></div>
                                <div class="skeleton skeleton-text" style="width:70%;"></div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>

                <div id="factors-container" style="display:none;"></div>
            </div>

            <!-- Kesimpulan -->
            <div class="card mt-3">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-clipboard-list"></i> Kesimpulan Penilaian Risiko Operasional KPMR
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($kesimpulan13) && !empty($kesimpulan13['keterangan'])): ?>
                        <div class="alert border">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                <div>
                                    <h6 class="alert-heading"><i class="fas fa-check-circle"></i> Kesimpulan Tersimpan</h6>
                                    <p class="mb-0"><?= nl2br(esc($kesimpulan13['keterangan'])) ?></p>
                                </div>
                                <?php if ($canEdit): ?>
                                    <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="collapse"
                                        data-bs-target="#editKesimpulan13">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($canEdit): ?>
                            <div class="collapse" id="editKesimpulan13">
                                <hr>
                                <form action="<?= base_url('Risikooperasionalkpmr/simpanKesimpulan13') ?>" method="post">
                                    <?= csrf_field() ?>
                                    <div class="form-group">
                                        <label><strong>Edit Keterangan Kesimpulan:</strong></label>
                                        <textarea class="form-control" name="keterangan" rows="5"
                                            required><?= esc($kesimpulan13['keterangan']) ?></textarea>
                                    </div>
                                    <div class="d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-secondary" data-bs-toggle="collapse"
                                            data-bs-target="#editKesimpulan13"><i class="fas fa-times"></i> Batal</button>
                                        <button type="submit" class="btn btn-primary ml-2"><i class="fas fa-save"></i> Update
                                            Kesimpulan</button>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if ($canEdit): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> <strong>Kesimpulan belum diisi.</strong>
                            </div>
                            <form action="<?= base_url('Risikooperasionalkpmr/simpanKesimpulan13') ?>" method="post">
                                <?= csrf_field() ?>
                                <div class="form-group">
                                    <label><strong>Keterangan Kesimpulan:</strong></label>
                                    <textarea class="form-control" name="keterangan" rows="5"
                                        placeholder="Masukkan kesimpulan penilaian risiko operasional KPMR..."
                                        required></textarea>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Simpan
                                        Kesimpulan</button>
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
                    <div class="col-md-3 col-sm-8 col-10 mx-auto">
                        <div class="card shadow-sm approvaldir-card">
                            <div class="card-body approvaldir-card-body">
                                <div class="approval-badge-container">
                                    <span class="badge approval-badge">Approval Direktur Utama</span>
                                </div>
                                <div class="approval-buttons-container">
                                    <a href="<?= base_url('Risikooperasionalkpmr/approveSemua') ?>" id="btnApproveSemua"
                                        class="btn approval-btn approval-btn-approve <?= !($canApprove ?? false) ? 'disabled-btn' : '' ?>">Approve</a>
                                    <a href="<?= base_url('Risikooperasionalkpmr/unapproveSemua') ?>" id="btnUnapproveSemua"
                                        class="btn approval-btn approval-btn-reject <?= !($allApproved ?? false) ? 'disabled-btn' : '' ?>">Tolak</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Ubah -->
<div class="modal fade" id="modalUbah" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ubah Data Penilaian</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="formUbah" data-action="<?= base_url('Risikooperasionalkpmr/ubah') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="faktor1id" id="edit-faktor-id">
                    <div class="mb-3">
                        <label><strong>Parameter penilaian risiko:</strong></label>
                        <textarea class="form-control" id="edit-parameterpenilaian" style="height:100px;"
                            readonly></textarea>
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
                        <textarea class="form-control" name="keterangan" id="edit-keterangan" style="height:100px;"
                            placeholder="Masukkan keterangan..." required></textarea>
                    </div>
                    <div class="modal-footer px-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Ubah Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ubah Kesimpulan -->
<div class="modal fade" id="modalUbahkesimpulan" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ubah Keterangan Kesimpulan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="formUbahKesimpulan" data-action="<?= base_url('Risikooperasionalkpmr/ubahkesimpulan') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="faktor1id" id="edit-faktor-id-kesimpulan">
                    <div class="mb-3">
                        <label><strong>Sub Kategori:</strong></label>
                        <textarea class="form-control" id="edit-parameterpenilaian-kesimpulan" style="height:100px;"
                            readonly></textarea>
                    </div>
                    <div class="mb-3">
                        <label><strong>Keterangan:</strong></label>
                        <textarea class="form-control" name="keterangan" id="edit-keterangan-kesimpulan"
                            style="height:100px;" placeholder="Masukkan keterangan..." required></textarea>
                    </div>
                    <div class="modal-footer px-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Ubah
                            Keterangan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Modal Tambah Nilai -->
<div class="modal fade" id="modaltambahNilai" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Nilai Faktor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="formTambahNilai" data-action="<?= base_url('Risikooperasionalkpmr/tambahNilai') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="faktor_id" id="add-faktor-id">
                    <input type="hidden" name="fullname" value="<?= esc($fullname ?? '') ?>">
                    <div class="mb-3">
                        <label><strong>Parameter penilaian risiko:</strong></label>
                        <textarea class="form-control" id="add-parameterpenilaian" style="height:100px;"
                            readonly></textarea>
                    </div>
                    <label><strong>Pilih Tingkat Risiko:</strong></label>
                    <div class="alert alert-info mb-2 d-none" id="add-catatan-info" style="font-size:.9em;">
                        <i class="fas fa-info-circle"></i> <span id="add-catatan-text"></span>
                    </div>
                    <div class="list-group mb-3" id="addRatingList"></div>
                    <p>Nilai yang dipilih: <strong id="addSelectedValue" class="text-primary">Belum ada</strong></p>
                    <input type="hidden" name="penilaiankredit" id="add-penilaiankredit">
                    <input type="hidden" name="penjelasanpenilaian" id="add-penjelasanpenilaian">
                    <div class="mb-3">
                        <label><strong>Keterangan:</strong></label>
                        <textarea class="form-control" name="keterangan" id="add-keterangan" style="height:100px;"
                            placeholder="Masukkan keterangan..." required></textarea>
                    </div>
                    <div class="modal-footer px-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" name="tambahNilai" class="btn btn-primary"><i class="fas fa-save"></i>
                            Simpan Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Komentar -->
<div class="modal fade" id="modaltambahKomentar" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Komentar Direksi dan Dewan Komisaris</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="formKomentar" data-action="<?= base_url('Risikooperasionalkpmr/tambahKomentar') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="faktor_id" id="comment-faktor-id">
                    <input type="hidden" name="kodebpr" value="<?= esc($kodebpr ?? '') ?>">
                    <div class="mb-3">
                        <label>Komentar Saat Ini:</label>
                        <ul id="komentarLamaList" class="komentar-list">
                            <li>Memuat komentar...</li>
                        </ul>
                    </div>
                    <?php if (
                        ($userInGroupAdmin ?? false) || ($userInGroupDekom ?? false) ||
                        ($userInGroupDireksi ?? false) || ($userInGroupPE ?? false) ||
                        ($userInGroupDekom2 ?? false) || ($userInGroupDireksi2 ?? false)
                    ): ?>
                        <input type="hidden" name="fullname" value="<?= esc($fullname ?? '') ?>">
                        <div class="mb-3">
                            <label>Tambahkan Komentar Baru:</label>
                            <textarea class="form-control" name="komentar" id="komentar" style="height:100px;"></textarea>
                        </div>
                    <?php endif; ?>
                    <div class="modal-footer px-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" name="tambahKomentar" class="btn btn-primary">Simpan Komentar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalSyncConfirm" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-sync-alt mr-1"></i>
                    Sinkronisasi Data Periode Sebelumnya
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div id="sync-modal-info" class="alert alert-info mb-3" style="font-size:.9em;line-height:1.5;"></div>

                <div class="sync-checkbox-row">
                    <input type="checkbox" id="sync-force-overwrite" class="sync-checkbox">
                    <label for="sync-force-overwrite" class="sync-checkbox-label">
                        <strong>Timpa data yang sudah ada</strong>
                        <span class="sync-checkbox-hint">
                            Jika tidak dicentang, hanya faktor yang belum terisi yang akan disalin.
                        </span>
                    </label>
                </div>

                <div id="sync-progress" style="display:none;" class="sync-progress-area">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    <span>Sedang menyinkronisasi data...</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-warning btn-sm" id="btn-sync-execute">
                    <i class="fas fa-sync-alt"></i> Mulai Sinkronisasi
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        'use strict';

        /* CONSTANTS */
        const GLOBAL_KODEBPR = '<?= esc($kodebpr ?? '') ?>';
        const GLOBAL_PERIODE_ID = '<?= esc($activePeriodeId ?? '') ?>';
        const BASE_URL = '<?= base_url() ?>';
        const PENILAIAN_CONFIG = <?= json_encode($penilaianConfig ?? []) ?>;
        const CSRF_TOKEN_NAME = '<?= csrf_token() ?>';
        let CSRF_HASH = '<?= csrf_hash() ?>';

        const CAN_EDIT = <?= json_encode(($userInGroupAdmin ?? false) || ($userInGroupPE ?? false)) ?>;
        const CAN_APPROVE = <?= json_encode(($userInGroupAdmin ?? false) || ($userInGroupDireksi ?? false) || ($userInGroupDireksi2 ?? false)) ?>;

        /* TINY HELPERS */
        const $id = id => document.getElementById(id);

        function debounce(fn, ms) {
            let t;
            return function (...args) { clearTimeout(t); t = setTimeout(() => fn.apply(this, args), ms); };
        }

        function escH(s) {
            return String(s ?? '').replace(/[&<>"']/g,
                m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m]));
        }

        /* CSRF: only refresh from meta tag when present */
        function refreshCsrf() {
            const meta = document.querySelector('meta[name="csrf-token"],meta[name="X-CSRF-TOKEN"]');
            if (meta) CSRF_HASH = meta.getAttribute('content');
        }

        /* FETCH HELPER */
        function postJSON(url, data) {
            data[CSRF_TOKEN_NAME] = CSRF_HASH;
            const fullUrl = url.startsWith('http') ? url : BASE_URL + url.replace(/^\//, '');
            return fetch(fullUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: new URLSearchParams(data)
            }).then(r => { refreshCsrf(); if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); });
        }

        /* TOAST */
        const _toastColors = { success: '#28a745', error: '#dc3545', warning: '#e0a800', info: '#17a2b8' };
        const _toastIcons = { success: 'check-circle', error: 'exclamation-circle', warning: 'exclamation-triangle', info: 'info-circle' };

        function showToast(title, message, type = 'info') {
            const wrap = $id('toast-wrapper');
            const t = document.createElement('div');
            t.className = 'toast-notification';
            t.style.background = _toastColors[type] || _toastColors.info;
            t.innerHTML = `<i class="fas fa-${_toastIcons[type] || 'info-circle'}"></i><div><strong>${escH(title)}</strong><br><span>${escH(message)}</span></div>`;
            wrap.appendChild(t);
            requestAnimationFrame(() => t.classList.add('show'));
            setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 350); }, 3500);
        }

        (function initSync() {
            const syncBar = $id('sync-bar');
            const syncBarText = $id('sync-bar-text');
            const btnTrigger = $id('btn-sync-trigger');
            const syncNodataBar = $id('sync-nodata-bar');
            const syncNodataText = $id('sync-nodata-text');
            const syncModalInfo = $id('sync-modal-info');
            const syncProgress = $id('sync-progress');
            const forceChk = $id('sync-force-overwrite');
            const btnExecute = $id('btn-sync-execute');

            /* Jika HTML sync belum dipasang → skip tanpa error */
            if (!syncBar || !btnTrigger || !btnExecute) return;

            let _prevInfo = null;
            let _currentHasData = false;
            let _checked = false;

            /* ── STEP 1: check ringan — 2 COUNT query, 1x per load ── */
            function doCheck() {
                if (_checked) return;
                _checked = true;

                postJSON('Risikooperasionalkpmr/checkPrevPeriode', {})
                    .then(r => {
                        if (r.status === 'available') {
                            _prevInfo = r.prev_info;
                            _currentHasData = !!r.current_has_data;
                            const pi = r.prev_info;
                            syncBarText.innerHTML =
                                '<i class="fas fa-info-circle mr-1"></i>' +
                                'Data <strong>Semester ' + pi.semester + ' Tahun ' + pi.tahun + '</strong> ' +
                                'tersedia &mdash; ' + pi.faktor_count + ' faktor.' +
                                (_currentHasData
                                    ? ' <em class="text-muted">Periode aktif sudah ada sebagian data.</em>'
                                    : ' Periode aktif belum ada data.');
                            syncBar.style.display = '';

                        } else if (r.status === 'no_data' || r.status === 'no_prev') {
                            if (syncNodataText) syncNodataText.textContent = r.message || 'Tidak ada data periode sebelumnya.';
                            if (syncNodataBar) syncNodataBar.style.display = '';
                        }
                        /* status error → silent */
                    })
                    .catch(() => { /* network error → silent */ });
            }

            /* ── STEP 2: buka modal konfirmasi ── */
            btnTrigger.addEventListener('click', function () {
                if (!_prevInfo) return;
                const pi = _prevInfo;

                let info =
                    '<i class="fas fa-calendar-alt mr-1"></i>' +
                    'Menyalin dari <strong>Semester ' + pi.semester + ' Tahun ' + pi.tahun + '</strong><br>' +
                    '<span class="text-muted" style="font-size:.9em;">' +
                    pi.faktor_count + ' faktor penilaian akan disalin.</span>';

                if (_currentHasData) {
                    info +=
                        '<div class="alert alert-warning mt-2 mb-0 py-1 px-2" style="font-size:.83em;">' +
                        '<i class="fas fa-exclamation-triangle mr-1"></i>' +
                        'Periode aktif sudah memiliki sebagian data. ' +
                        'Centang <em>"Timpa data"</em> untuk mengganti semua.' +
                        '</div>';
                }

                if (syncModalInfo) syncModalInfo.innerHTML = info;
                if (forceChk) forceChk.checked = false;
                if (syncProgress) syncProgress.style.display = 'none';

                btnExecute.disabled = false;
                btnExecute.innerHTML = '<i class="fas fa-sync-alt"></i> Mulai Sinkronisasi';
                modalShow('modalSyncConfirm');
            });

            /* ── STEP 3: eksekusi sync ── */
            btnExecute.addEventListener('click', function () {
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
                if (syncProgress) syncProgress.style.display = 'block';

                const force = forceChk && forceChk.checked ? 1 : 0;

                postJSON('Risikooperasionalkpmr/syncFromPrevPeriode', { force_overwrite: force })
                    .then(r => {
                        if (syncProgress) syncProgress.style.display = 'none';
                        modalHide('modalSyncConfirm');

                        if (r.status === 'success') {
                            showToast('Sinkronisasi Berhasil', r.message, 'success');
                            syncBar.style.display = 'none';
                            loadFactorsData();
                            if (r.peringkat13) updateBadgePeringkat13(r.peringkat13);

                        } else if (r.status === 'no_change') {
                            showToast('Tidak Ada Perubahan', r.message, 'info');
                            if (!force) {
                                if (forceChk) forceChk.checked = true;
                                modalShow('modalSyncConfirm');
                            }
                            this.disabled = false;
                            this.innerHTML = '<i class="fas fa-sync-alt"></i> Mulai Sinkronisasi';

                        } else {
                            showToast('Gagal', r.message || 'Terjadi kesalahan', 'error');
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

            setTimeout(doCheck, 900);
        })();

        const _modalInstances = {};

        function modalShow(id) {
            const el = $id(id); if (!el) return;
            if (window.bootstrap?.Modal) {
                if (!_modalInstances[id]) {
                    _modalInstances[id] = new bootstrap.Modal(el, { backdrop: true, keyboard: true });
                    el.addEventListener('hidden.bs.modal', () => { delete _modalInstances[id]; }, { once: true });
                }
                _modalInstances[id].show();
            } else if (window.$) { window.$('#' + id).modal('show'); }
        }

        function modalHide(id) {
            const el = $id(id); if (!el) return;
            if (_modalInstances[id]) _modalInstances[id].hide();
            else if (window.bootstrap?.Modal) bootstrap.Modal.getInstance(el)?.hide();
            else if (window.$) window.$('#' + id).modal('hide');
        }

        document.addEventListener('click', function (e) {
            if (e.target.closest('[data-bs-dismiss="modal"]')) {
                const m = e.target.closest('.modal'); if (m) modalHide(m.id);
                return;
            }
            if (e.target.closest('[data-bs-toggle="collapse"]')) {
                const btn = e.target.closest('[data-bs-toggle="collapse"]');
                const target = document.querySelector(btn.getAttribute('data-bs-target'));
                if (target) target.classList.toggle('show');
            }
        });

        /* PERINGKAT BADGE */
        const _peringkatLabels = { 1: 'Sangat Rendah', 2: 'Rendah', 3: 'Sedang', 4: 'Tinggi', 5: 'Sangat Tinggi' };
        const _badgeClasses = { 1: 'badge-info', 2: 'badge-success', 3: 'badge-warning', 4: 'badge-danger', 5: 'badge-dark' };

        function updateBadgePeringkat13(peringkat) {
            const el = $id('badge-peringkat13'); if (!el) return;
            const p = parseInt(peringkat);
            el.className = `badge badge-lg ${_badgeClasses[p] || 'badge-secondary'}`;
            el.style.cssText = 'font-size:1.1em;padding:.5em 1em;';
            el.innerHTML = `<i class="fas fa-check-circle"></i> Peringkat penilaian: ${p} [${_peringkatLabels[p] || 'N/A'}]`;
        }

        function updateMainApprovalBadge(allApproved, approvedAt = null) {
            const badge = $id('approval-badge-main'); if (!badge) return;
            if (allApproved) {
                badge.className = 'badge badge-success';
                badge.style.fontSize = '14px';
                const d = approvedAt ? new Date(approvedAt).toLocaleString('id-ID') : '-';
                badge.innerHTML = `Disetujui oleh <strong>Direktur Utama</strong><br>${d}`;
            } else {
                badge.className = 'badge badge-secondary';
                badge.style.fontSize = '14px';
                badge.innerHTML = 'Belum Disetujui Seluruhnya<br>Oleh Direktur Utama';
            }
        }

        /* RATING LIST */
        function generateRatingOptions(faktorId, containerId) {
            const config = PENILAIAN_CONFIG[faktorId] || {};
            const container = $id(containerId); if (!container) return config;
            const frag = document.createDocumentFragment();
            for (let i = 1; i <= 5; i++) {
                const desc = config.descriptions?.[i] || 'Tidak ada deskripsi';
                const a = document.createElement('a');
                a.href = '#';
                a.className = 'list-group-item list-group-item-action py-2 px-3';
                a.dataset.value = i;
                a.dataset.description = desc;
                a.innerHTML = `<h6 class="mb-1"><b>Nilai ${i}</b></h6><p class="mb-0 small">${desc}</p>`;
                frag.appendChild(a);
            }
            container.innerHTML = '';
            container.appendChild(frag);
            return config;
        }

        function updateCatatanInfo(config, prefix) {
            let html = '';
            if (config.threshold) html += `<strong>Threshold:</strong> ${config.threshold}<br>`;
            if (config.catatan) html += `<strong>Catatan:</strong> ${config.catatan}`;
            const el = $id(prefix + '-catatan-info');
            const tx = $id(prefix + '-catatan-text');
            if (!el || !tx) return;
            if (html) { tx.innerHTML = html; el.classList.remove('d-none'); }
            else el.classList.add('d-none');
        }

        /* RENDER HELPERS */
        function renderApprovalBtn(faktorId, nilai) {
            if (!CAN_APPROVE) return '';
            if (!nilai || !nilai.penilaiankredit) return '<span class="text-muted ml-2">-</span>';
            const approved = nilai.accdir2 == 1;
            const action = approved ? 'unapprovedir2' : 'accdir2';
            const btnCls = approved ? 'btn-success' : 'btn-dark';
            const icon = approved ? 'check' : 'times';
            return `<form action="${BASE_URL}Risikooperasionalkpmr/${action}" method="POST" style="display:inline-block;" class="ml-2 form-approval">
            <input type="hidden" name="${CSRF_TOKEN_NAME}" value="${CSRF_HASH}">
            <input type="hidden" name="faktor1id"  value="${faktorId}">
            <input type="hidden" name="kodebpr"    value="${GLOBAL_KODEBPR}">
            <input type="hidden" name="periode_id" value="${GLOBAL_PERIODE_ID}">
            <button type="submit" class="btn btn-sm ${btnCls}"><i class="fas fa-${icon}-circle"></i></button>
        </form>`;
        }

        function renderNotifBadge(faktorId) {
            return `<button type="button" class="btn btn-sm btn-primary ml-2 position-relative komentar-btn" data-faktor-id="${faktorId}">
            <i class="fas fa-comment"></i>
            <span id="notification-badge-${faktorId}" class="badge badge-danger notification-badge" style="display:none;">0</span>
        </button>`;
        }

        function renderSingleButtons(faktorId, nilai, title) {
            const hasData = nilai && nilai.penilaiankredit;
            let html = '';
            if (CAN_EDIT) {
                html += hasData
                    ? `<button class="btn btn-sm btn-warning ml-2 btn-edit"
                        data-id="${faktorId}" data-title="${escH(title)}"
                        data-penilaiankredit="${escH(nilai.penilaiankredit)}"
                        data-keterangan="${escH(nilai.keterangan || '')}">
                        <i class="fas fa-edit"></i></button>`
                    : `<button type="button" class="btn btn-sm btn-success ml-2 btn-add"
                        data-id="${faktorId}" data-title="${escH(title)}">
                        <i class="fas fa-plus"></i></button>`;
            }
            html += renderNotifBadge(faktorId);
            html += renderApprovalBtn(faktorId, nilai);
            return html;
        }

        function renderCategoryChildren(factor) {
            if (!factor.children?.length) return '';
            return factor.children.map((child, idx) => {
                const nilai = child.nilai;
                const hasData = nilai && nilai.penilaiankredit;
                const title = escH(child.title).replace(/\\n/g, '<br>').replace(/\n/g, '<br>').replace(/&lt;br&gt;/gi, '<br>');
                return `<div class="border-left border-success pl-4 mb-3">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div class="flex-grow-1"><h6 class="font-weight-bold">${idx + 1}. ${title}</h6></div>
                    <div class="child-btn-group">
                        ${hasData
                        ? `<span class="badge badge-success"><i class="fas fa-check"></i> Peringkat: ${escH(nilai.penilaiankredit)}</span>`
                        : '<span class="badge badge-secondary">Belum dinilai</span>'}
                        ${CAN_EDIT ? (hasData
                        ? `<button class="btn btn-sm btn-warning ml-1 btn-edit"
                                    data-id="${child.faktor_id}" data-title="${escH(child.title)}"
                                    data-penilaiankredit="${escH(nilai.penilaiankredit)}"
                                    data-keterangan="${escH(nilai.keterangan || '')}">
                                    <i class="fas fa-edit"></i></button>`
                        : `<button type="button" class="btn btn-sm btn-success ml-1 btn-add"
                                    data-id="${child.faktor_id}" data-title="${escH(child.title)}">
                                    <i class="fas fa-plus"></i></button>`) : ''}
                        ${renderNotifBadge(child.faktor_id)}
                        ${renderApprovalBtn(child.faktor_id, nilai)}
                    </div>
                </div>
            </div>`;
            }).join('');
        }

        function renderFactors(factors) {
            const parts = factors.map((factor, idx) => {
                const nilai = factor.nilai;
                const hasData = nilai && nilai.penilaiankredit;
                return `<div class="card shadow mb-4">
                <div class="card-header py-3 factor-card-header">
                    <h6 class="m-0 font-weight-bold text-primary">${idx + 1}. ${escH(factor.title)}</h6>
                    <div class="factor-btn-group">
                        ${hasData
                        ? `<span class="badge badge-info"><i class="fas fa-check"></i> Peringkat: ${escH(nilai.penilaiankredit)}</span>`
                        : '<span class="badge badge-secondary">Belum dinilai</span>'}
                        ${factor.type === 'single' ? renderSingleButtons(factor.faktor_id, nilai, factor.title) : ''}
                        ${factor.type === 'category' ? `
                            ${CAN_EDIT ? `<button type="button" class="btn btn-sm btn-warning ml-1 btn-edit-keterangan"
                                data-id="${factor.faktor_id}" data-title="${escH(factor.title)}"
                                data-keterangan="${escH(nilai?.keterangan || '')}">
                                <i class="fas fa-edit"></i></button>` : ''}
                            ${renderNotifBadge(factor.faktor_id)}
                            ${renderApprovalBtn(factor.faktor_id, nilai)}
                        ` : ''}
                    </div>
                </div>
                <div class="card-body">${factor.type === 'category' ? renderCategoryChildren(factor) : ''}</div>
            </div>`;
            });

            $id('factors-container').innerHTML = parts.join('');
            setTimeout(pollUnreadCounts, 300);
        }

        document.addEventListener('click', function (e) {
            const ratingItem = e.target.closest('#editRatingList .list-group-item, #addRatingList .list-group-item');
            if (ratingItem) {
                e.preventDefault();
                const inEdit = !!ratingItem.closest('#editRatingList');
                const prefix = inEdit ? 'edit' : 'add';
                const listSel = inEdit ? '#editRatingList' : '#addRatingList';
                document.querySelectorAll(listSel + ' .list-group-item').forEach(i => i.classList.remove('active'));
                ratingItem.classList.add('active');
                const v = ratingItem.dataset.value;
                const d = ratingItem.dataset.description;
                const valEl = $id(`${prefix}-penilaiankredit`); if (valEl) valEl.value = v;
                const penEl = $id(`${prefix}-penjelasanpenilaian`); if (penEl) penEl.value = d;
                const selEl = $id(`${prefix}SelectedValue`); if (selEl) selEl.textContent = 'Nilai ' + v;
                return;
            }

            // btn-edit
            const btnEdit = e.target.closest('.btn-edit');
            if (btnEdit) {
                e.preventDefault();
                $id('edit-faktor-id').value = btnEdit.dataset.id;
                $id('edit-parameterpenilaian').value = btnEdit.dataset.title;
                $id('edit-penilaiankredit').value = btnEdit.dataset.penilaiankredit;
                $id('edit-keterangan').value = btnEdit.dataset.keterangan || '';
                const cfg = generateRatingOptions(btnEdit.dataset.id, 'editRatingList');
                updateCatatanInfo(cfg, 'edit');
                requestAnimationFrame(() => {
                    document.querySelectorAll('#editRatingList .list-group-item').forEach(item => {
                        if (item.dataset.value == btnEdit.dataset.penilaiankredit) {
                            item.classList.add('active');
                            const sel = $id('editSelectedValue'); if (sel) sel.textContent = 'Nilai ' + btnEdit.dataset.penilaiankredit;
                        }
                    });
                });
                modalShow('modalUbah');
                return;
            }

            // btn-edit-keterangan
            const btnEditKet = e.target.closest('.btn-edit-keterangan');
            if (btnEditKet) {
                e.preventDefault();
                $id('edit-faktor-id-kesimpulan').value = btnEditKet.dataset.id;
                $id('edit-parameterpenilaian-kesimpulan').value = btnEditKet.dataset.title;
                $id('edit-keterangan-kesimpulan').value = btnEditKet.dataset.keterangan || '';
                modalShow('modalUbahkesimpulan');
                return;
            }

            // btn-add
            const btnAdd = e.target.closest('.btn-add');
            if (btnAdd) {
                e.preventDefault();
                $id('add-faktor-id').value = btnAdd.dataset.id;
                $id('add-parameterpenilaian').value = btnAdd.dataset.title;
                $id('add-penilaiankredit').value = '';
                $id('add-penjelasanpenilaian').value = '';
                $id('add-keterangan').value = '';
                const selEl = $id('addSelectedValue'); if (selEl) selEl.textContent = 'Belum ada';
                const cfg = generateRatingOptions(btnAdd.dataset.id, 'addRatingList');
                updateCatatanInfo(cfg, 'add');
                modalShow('modaltambahNilai');
                return;
            }

            // komentar-btn
            const btnKomentar = e.target.closest('.komentar-btn');
            if (btnKomentar) {
                e.preventDefault();
                const faktorId = btnKomentar.dataset.faktorId;
                const elFid = $id('comment-faktor-id'); if (elFid) elFid.value = faktorId;
                loadKomentar(faktorId);
                markRead(faktorId);
                modalShow('modaltambahKomentar');
                return;
            }

            // Per-faktor approval form submit
            const approvalBtn = e.target.closest('.form-approval button[type="submit"]');
            if (approvalBtn) {
                e.preventDefault();
                const form = approvalBtn.closest('.form-approval');
                const orig = approvalBtn.innerHTML;
                approvalBtn.disabled = true;
                approvalBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                const data = new URLSearchParams(new FormData(form));
                data.set(CSRF_TOKEN_NAME, CSRF_HASH);
                fetch(form.action, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                    body: data
                }).then(r => { refreshCsrf(); return r.text(); })
                    .then(() => { showToast('Berhasil', 'Status approval diubah', 'success'); loadFactorsData(); })
                    .catch(() => { approvalBtn.disabled = false; approvalBtn.innerHTML = orig; showToast('Error', 'Gagal mengubah status', 'error'); });
                return;
            }
        });

        function makeAjaxFormHandler(formId, onSuccess, opts = {}) {
            const form = $id(formId); if (!form) return;
            form.addEventListener('submit', function (e) { e.preventDefault(); }, true);
            form.addEventListener('submit', debounce(function (e) {
                e.preventDefault(); e.stopPropagation();
                const btn = this.querySelector('button[type="submit"]'); if (!btn) return;
                const orig = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
                const data = new URLSearchParams(new FormData(this));
                data.set(CSRF_TOKEN_NAME, CSRF_HASH);
                const fetchUrl = this.dataset.action || (this.action !== window.location.href ? this.action : null);
                if (!fetchUrl) { btn.disabled = false; btn.innerHTML = orig; showToast('Error', 'URL tidak ditemukan', 'error'); return; }
                fetch(fetchUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                    body: data
                })
                    .then(r => { refreshCsrf(); return r.json(); })
                    .then(r => {
                        btn.disabled = false; btn.innerHTML = orig;
                        if (r.success || r.status === 'success') {
                            showToast('Berhasil', opts.successMsg || 'Data berhasil disimpan', 'success');
                            if (opts.hideModal) modalHide(opts.hideModal);
                            if (onSuccess) onSuccess(r);
                            if (!opts.noRefresh) { updateMainApprovalBadge(false); loadFactorsData(); }
                        } else {
                            showToast('Error', r.message || 'Gagal menyimpan', 'error');
                        }
                    })
                    .catch(err => { btn.disabled = false; btn.innerHTML = orig; showToast('Error', 'Kesalahan koneksi: ' + err.message, 'error'); });
            }, 300));
        }

        makeAjaxFormHandler('formUbah',
            r => { if (r.peringkat13) updateBadgePeringkat13(r.peringkat13); },
            { hideModal: 'modalUbah', successMsg: 'Data berhasil diubah' }
        );
        makeAjaxFormHandler('formUbahKesimpulan', null,
            { hideModal: 'modalUbahkesimpulan', successMsg: 'Keterangan berhasil diubah' }
        );
        makeAjaxFormHandler('formTambahNilai',
            r => { if (r.peringkat13) updateBadgePeringkat13(r.peringkat13); const sel = $id('addSelectedValue'); if (sel) sel.textContent = 'Belum ada'; },
            { hideModal: 'modaltambahNilai', successMsg: 'Nilai berhasil ditambahkan' }
        );
        makeAjaxFormHandler('formKomentar',
            () => { loadKomentar($id('comment-faktor-id')?.value); pollUnreadCounts(); const ta = $id('komentar'); if (ta) ta.value = ''; },
            { noRefresh: true, successMsg: 'Komentar berhasil ditambahkan' }
        );

        /* KOMENTAR */
        function loadKomentar(faktorId) {
            const list = $id('komentarLamaList');
            list.innerHTML = '<li>Memuat komentar...</li>';
            postJSON('Risikooperasionalkpmr/getKomentarByFaktorId/' + faktorId, {
                kodebpr: GLOBAL_KODEBPR, periode_id: GLOBAL_PERIODE_ID
            }).then(data => {
                const rows = Array.isArray(data) ? data : (data.data || []);
                if (!rows.length) { list.innerHTML = '<li>Tidak ada komentar.</li>'; return; }
                const frag = document.createDocumentFragment();
                rows.forEach(k => {
                    const li = document.createElement('li');
                    li.innerHTML = `${escH(k.komentar)} — <em>${escH(k.fullname)} &bull; ${k.created_at}</em>`;
                    frag.appendChild(li);
                });
                list.innerHTML = '';
                list.appendChild(frag);
            }).catch(() => { list.innerHTML = '<li>Gagal memuat komentar.</li>'; });
        }

        function markRead(faktorId) {
            postJSON('Risikooperasionalkpmr/markUserCommentsAsRead', {
                faktor_id: faktorId, kodebpr: GLOBAL_KODEBPR, periode_id: GLOBAL_PERIODE_ID
            }).then(r => {
                if (r.status === 'success') {
                    const b = $id(`notification-badge-${faktorId}`); if (b) b.style.display = 'none';
                }
            }).catch(() => { });
        }

        let _lastPoll = 0;
        let _lastActive = 0;
        const POLL_INTERVAL = 90_000;
        const IDLE_TIMEOUT = 60_000;
        const CHECK_TICK = 30_000;

        const _trackActivity = debounce(() => { _lastActive = Date.now(); }, 500);
        document.addEventListener('mousemove', _trackActivity, { passive: true });
        document.addEventListener('keydown', _trackActivity, { passive: true });
        document.addEventListener('touchstart', _trackActivity, { passive: true });

        function pollUnreadCounts() {
            postJSON('Risikooperasionalkpmr/getAllUnreadCounts', {
                kodebpr: GLOBAL_KODEBPR, periode_id: GLOBAL_PERIODE_ID
            }).then(r => {
                if (r.status !== 'success') return;
                document.querySelectorAll('.notification-badge').forEach(b => b.style.display = 'none');
                Object.entries(r.counts || {}).forEach(([fid, cnt]) => {
                    const b = $id(`notification-badge-${fid}`);
                    if (b && cnt > 0) { b.textContent = cnt; b.style.display = 'inline-flex'; }
                });
            }).catch(() => { });
        }

        setInterval(() => {
            const now = Date.now();
            if ((now - _lastActive) < IDLE_TIMEOUT && (now - _lastPoll) > POLL_INTERVAL) {
                _lastPoll = now;
                pollUnreadCounts();
            }
        }, CHECK_TICK);

        /* APPROVE / UNAPPROVE SEMUA */
        function wireApproveLink(id, confirmMsg, successMsg, approved) {
            const lnk = $id(id); if (!lnk) return;
            lnk.addEventListener('click', function (e) {
                e.preventDefault();
                if (!confirm(confirmMsg)) return;
                let path;
                try { path = new URL(this.href).pathname; } catch (_) { path = this.href.replace(/^https?:\/\/[^/]+/, ''); }
                const orig = this.innerHTML;
                this.classList.add('disabled-btn');
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                postJSON(path, {})
                    .then(() => {
                        showToast('Berhasil', successMsg, 'success');
                        updateMainApprovalBadge(approved, approved ? new Date().toISOString() : null);
                        loadFactorsData();
                        this.classList.remove('disabled-btn');
                        this.innerHTML = orig;
                    })
                    .catch(() => { this.classList.remove('disabled-btn'); this.innerHTML = orig; showToast('Error', 'Gagal', 'error'); });
            });
        }
        wireApproveLink('btnApproveSemua', 'Apakah Anda yakin ingin melakukan approval?', 'Semua data berhasil disetujui', true);
        wireApproveLink('btnUnapproveSemua', 'Batalkan semua approval?', 'Semua approval berhasil dibatalkan', false);

        /* LOAD FACTORS DATA */
        function loadFactorsData() {
            $id('factors-skeleton').style.display = 'block';
            $id('factors-container').style.display = 'none';
            postJSON('Risikooperasionalkpmr/getFactorsData', {
                kodebpr: GLOBAL_KODEBPR, periode_id: GLOBAL_PERIODE_ID
            }).then(r => {
                if (!r || r.status !== 'success' || !Array.isArray(r.data)) throw new Error(r?.message || 'Response tidak valid');
                renderFactors(r.data);
                $id('factors-skeleton').style.display = 'none';
                $id('factors-container').style.display = 'block';
                _lastActive = Date.now();
            }).catch(err => {
                $id('factors-skeleton').innerHTML =
                    `<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i>
                Gagal memuat data: ${escH(err.message)}.
                <a href="#" onclick="location.reload()" class="alert-link ml-2">Refresh halaman</a></div>`;
            });
        }

        /* SIMPAN / RESET PERINGKAT 13 */
        const btnSimpan13 = $id('btnSimpanPeringkat13');
        if (btnSimpan13) {
            btnSimpan13.addEventListener('click', debounce(function () {
                const peringkat = $id('peringkat13_manual')?.value;
                if (!peringkat) { showToast('Error', 'Pilih peringkat terlebih dahulu', 'error'); return; }
                const orig = this.innerHTML;
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
                postJSON('Risikooperasionalkpmr/ubahPeringkat13Manual', { penilaiankredit: peringkat })
                    .then(r => {
                        if (r.status === 'success') { showToast('Berhasil', 'Peringkat berhasil diubah', 'success'); setTimeout(() => location.reload(), 1000); }
                        else { showToast('Error', r.message || 'Gagal', 'error'); this.disabled = false; this.innerHTML = orig; }
                    })
                    .catch(() => { this.disabled = false; this.innerHTML = orig; showToast('Error', 'Gagal', 'error'); });
            }, 300));
        }

        const btnReset13 = $id('btnResetPeringkat13');
        if (btnReset13) {
            btnReset13.addEventListener('click', debounce(function () {
                if (!confirm('Kembalikan ke perhitungan otomatis?')) return;
                const orig = this.innerHTML;
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                postJSON('Risikooperasionalkpmr/resetPeringkat13Auto', {})
                    .then(r => {
                        if (r.status === 'success') { showToast('Berhasil', 'Peringkat dikembalikan ke otomatis', 'success'); setTimeout(() => location.reload(), 1000); }
                        else { showToast('Error', r.message || 'Gagal', 'error'); this.disabled = false; this.innerHTML = orig; }
                    })
                    .catch(() => { this.disabled = false; this.innerHTML = orig; showToast('Error', 'Gagal', 'error'); });
            }, 300));
        }

        if (window.requestIdleCallback) {
            requestIdleCallback(() => loadFactorsData(), { timeout: 500 });
        } else {
            setTimeout(loadFactorsData, 100);
        }

    })();
</script>

<style>
    :root {
        --primary: #10598A;
        --success: #28a745;
        --danger: #dc3545;
        --warning: #e0a800;
        --dark: #343a40;
        --secondary: #6c757d;
        --light-bg: #f8f9fa;
        --white: #ffffff;
        --border: #dee2e6;
        --primary-gradient: linear-gradient(135deg, #141863 0%, #1f2aa8 100%);
        --shadow: 0 2px 4px rgba(0, 0, 0, .1);
        --shadow-lg: 0 4px 12px rgba(0, 0, 0, .18);
        --radius: 8px;
        --transition: all .2s ease-in-out;
    }

    /* Toast */
    #toast-wrapper {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 8px;
        max-width: 340px;
        width: calc(100% - 32px);
        pointer-events: none;
    }

    .toast-notification {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 18px;
        border-radius: var(--radius);
        color: #fff;
        box-shadow: var(--shadow-lg);
        opacity: 0;
        transform: translateX(120%);
        transition: opacity .3s ease, transform .3s ease;
        pointer-events: all;
        will-change: transform, opacity;
        /* GPU-accelerated */
    }

    .toast-notification.show {
        opacity: 1;
        transform: translateX(0);
    }

    .toast-notification i {
        font-size: 22px;
        flex-shrink: 0;
    }

    /* Skeleton */
    .skeleton {
        background: linear-gradient(90deg, #f0f0f0 0%, #e4e4e4 40%, #f0f0f0 100%);
        background-size: 200% 100%;
        animation: shimmer 1.3s ease-in-out infinite;
        border-radius: 4px;
    }

    .skeleton-text {
        height: 18px;
        margin-bottom: 10px;
    }

    @keyframes shimmer {
        0% {
            background-position: 200% 0
        }

        100% {
            background-position: -200% 0
        }
    }

    /* Collapse */
    .collapse {
        display: none;
    }

    .collapse.show {
        display: block;
    }

    /* Cards */
    .card {
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        margin-bottom: 20px;
    }

    /* Factor card header */
    .factor-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }

    .factor-btn-group,
    .child-btn-group {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 4px;
    }

    /* Button bar */
    .button-bar {
        display: flex;
        justify-content: flex-start;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        margin: 12px 0;
    }

    .kpmr-btn {
        background: var(--primary-gradient);
        color: #fff;
        font-weight: 600;
        padding: 8px 20px;
        border-radius: 6px;
        text-decoration: none;
        transition: var(--transition);
        display: inline-block;
    }

    .kpmr-btn:hover {
        background-color: var(--primary);
        transform: translateY(-2px);
        color: #fff;
    }

    /* Approval card */
    .approvaldir-card {
        border-radius: var(--radius);
        height: auto;
        min-height: 110px;
    }

    .approvaldir-card-body {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 16px;
        gap: 8px;
    }

    .approval-badge-container {
        text-align: center;
    }

    .approval-badge {
        background: var(--dark);
        color: #fff;
        font-size: .8em;
        padding: 5px 14px;
        border-radius: 5px;
        display: inline-block;
    }

    .approval-buttons-container {
        display: flex;
        justify-content: center;
        gap: 10px;
        width: 100%;
    }

    .approval-btn {
        flex: 1;
        padding: 8px 12px;
        border-radius: 28px;
        text-align: center;
        font-weight: 500;
        text-decoration: none;
        transition: var(--transition);
        font-size: .9em;
        white-space: nowrap;
    }

    .approval-btn-approve {
        background: var(--success);
        border-color: var(--success);
        color: #fff;
    }

    .approval-btn-approve:hover {
        background: #218838;
        color: #fff;
        transform: translateY(-1px);
    }

    .approval-btn-reject {
        background: var(--danger);
        border-color: var(--danger);
        color: #fff;
    }

    .approval-btn-reject:hover {
        background: #c82333;
        color: #fff;
        transform: translateY(-1px);
    }

    .disabled-btn {
        opacity: .5;
        pointer-events: none;
    }

    /* List group */
    .list-group-item {
        transition: background-color .15s ease;
        cursor: pointer;
    }

    .list-group-item:hover {
        background: #f8f9fa;
    }

    .list-group-item.active {
        background-color: #007bff !important;
        border-color: #007bff !important;
        color: #fff !important;
    }

    .list-group-item.active h6,
    .list-group-item.active p {
        color: #fff !important;
    }

    /* Modal header */
    .modal-header {
        background: var(--primary);
        color: #fff;
    }

    .btn-close {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        border: none;
        background: transparent;
        font-size: 20px;
        font-weight: bold;
        line-height: 34px;
        cursor: pointer;
        color: #fff;
        transition: .2s;
    }

    .btn-close:hover {
        transform: scale(1.1);
    }

    /* Komentar list */
    .komentar-list {
        list-style: none;
        padding-left: 0;
        max-height: 220px;
        overflow-y: auto;
    }

    .komentar-list li {
        padding: 6px 8px;
        border-bottom: 1px solid var(--border);
        font-size: .88em;
        line-height: 1.4;
    }

    /* Notification badge */
    .notification-badge {
        position: absolute;
        top: -6px;
        right: -6px;
        font-size: .65em;
        padding: 2px 5px;
        border-radius: 10px;
        min-width: 18px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    #approval-badge-main {
        font-size: 14px;
    }

    #sync-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        background: #fff3cd;
        border: 1px solid #ffc107;
        border-radius: var(--radius);
        color: #856404;
        font-size: .9em;
        padding: 10px 14px;
        margin-bottom: 12px;
    }

    .sync-bar-text {
        flex: 1;
        line-height: 1.4;
    }

    #sync-nodata-bar {
        background: #d1ecf1;
        border: 1px solid #bee5eb;
        border-radius: var(--radius);
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

    /* Sync modal: checkbox row */
    .sync-checkbox-row {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        padding: 4px 0;
    }

    .sync-checkbox {
        width: 16px;
        height: 16px;
        margin-top: 3px;
        flex-shrink: 0;
        cursor: pointer;
    }

    .sync-checkbox-label {
        cursor: pointer;
        line-height: 1.4;
    }

    .sync-checkbox-hint {
        display: block;
        font-size: .83em;
        color: var(--secondary);
        line-height: 1.3;
        margin-top: 2px;
    }

    /* Sync modal: progress */
    .sync-progress-area {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #f8f9fa;
        border-radius: 6px;
        padding: 10px 14px;
        margin-top: 12px;
        font-size: .88em;
        color: var(--secondary);
    }

    /* RESPONSIVE — Tablet */
    @media (max-width: 768px) {
        .button-bar {
            flex-direction: column;
            align-items: stretch;
        }

        .kpmr-btn {
            text-align: center;
        }

        .factor-card-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .factor-btn-group,
        .child-btn-group {
            width: 100%;
            justify-content: flex-start;
            margin-top: 6px;
            flex-wrap: wrap;
        }

        .approval-buttons-container {
            flex-direction: column;
            gap: 6px;
        }

        .approval-btn {
            max-width: 100%;
        }

        .modal-dialog {
            margin: 8px;
        }

        .badge-lg {
            font-size: .9em !important;
            padding: .4em .7em !important;
            word-break: break-word;
        }

        #editPeringkat13 select,
        #editNilai14 select {
            width: 100% !important;
        }

        h4 {
            font-size: 1.1rem;
        }

        .card-body {
            padding: 12px;
        }

        .btn-sm {
            min-height: 36px;
            padding: 6px 10px;
            font-size: .8em;
        }

        .list-group-item {
            padding: 12px 10px;
        }
    }

    @media (max-width: 576px) {
        .container-fluid {
            padding-left: 8px;
            padding-right: 8px;
        }

        #toast-wrapper {
            top: 10px;
            right: 8px;
            left: 8px;
            max-width: 100%;
        }

        .border-left {
            padding-left: 10px !important;
        }

        .child-btn-group {
            flex-direction: row;
            flex-wrap: wrap;
            gap: 4px;
        }

        .modal-dialog {
            margin: 4px;
            max-width: 100%;
        }

        .modal-content {
            border-radius: 6px;
        }

        .badge-lg {
            white-space: normal;
            text-align: center;
        }

        .approvaldir-card {
            min-height: auto;
        }

        .approval-btn {
            font-size: .82em;
            padding: 7px 8px;
        }

        .card-header {
            padding: 10px 12px !important;
        }

        .card:hover {
            transform: none;
            box-shadow: var(--shadow);
        }

        button:hover,
        .btn:hover {
            transform: none;
            box-shadow: none;
        }
    }
</style>