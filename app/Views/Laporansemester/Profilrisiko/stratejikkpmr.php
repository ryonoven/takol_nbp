<?php
$peringkat13 = isset($nilai13) && is_array($nilai13) ? ($nilai13['penilaiankredit'] ?? null) : null;
$peringkat14 = isset($nilai14) && is_array($nilai14) ? ($nilai14['penilaiankredit'] ?? null) : null;

$peringkatLabels = ['1' => 'Sangat Rendah', '2' => 'Rendah', '3' => 'Sedang', '4' => 'Tinggi', '5' => 'Sangat Tinggi'];

function getBadgeClassStraKpmr($nilai)
{
    $map = ['1' => 'badge-info', '2' => 'badge-success', '3' => 'badge-warning', '4' => 'badge-danger', '5' => 'badge-dark'];
    return $map[$nilai] ?? 'badge-secondary';
}

$currentUserId = session()->get('user_id');
$activePeriodeId = session()->get('active_periode');
$canEdit = ($userInGroupAdmin ?? false) || ($userInGroupPE ?? false);
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

<div class="container-fluid">
    <?php if (session()->get('message')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <strong><?= session()->getFlashdata('message') ?></strong>
        </div>
    <?php endif; ?>

    <div class="card card-body">
        <div class="table-vertical">

            <!-- Button bar -->
            <div class="button-bar">
                <a href="Stratejikinheren" class="kpmr-btn">🡸 Laporan Risiko Stratejik Inheren</a>
            </div>

            <!-- Page title -->
            <div class="text-center mb-3 mt-3">
                <h4 class="mb-0">
                    <i class="fas fa-info-circle text-primary"></i>
                    <strong>Tingkat Risiko Stratejik KPMR</strong>
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
                    <span id="badge-peringkat13" class="badge badge-lg <?= getBadgeClassStraKpmr($peringkat13) ?>"
                        style="font-size:1.1em;padding:.5em 1em;">
                        <i class="fas fa-check-circle"></i>
                        Peringkat penilaian: <?= esc($peringkat13 ?? 'N/A') ?>
                        [<?= $peringkatLabels[$peringkat13] ?? 'N/A' ?>]
                    </span>
                    <?php if ($canEdit): ?>
                        <?php if (!$isManual13): ?>
                            <button type="button" class="btn btn-sm btn-outline-info ml-2" data-bs-toggle="collapse"
                                data-bs-target="#editPeringkat13">
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
                                    <option value="<?= $val ?>" <?= $peringkat13 == $val ? 'selected' : '' ?>>
                                        <?= $val ?> - <?= $label ?>
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
                    <span class="badge badge-lg <?= getBadgeClassStraKpmr($peringkat14) ?>"
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
                    <form action="<?= base_url('Stratejikkpmr/simpanNilai14') ?>" method="post">
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
                <div class="mb-2"><strong>A. Kertas Kerja Stratejik KPMR</strong></div>
                <div id="factors-skeleton" style="display:block;">
                    <?php for ($i = 0; $i < 4; $i++): ?>
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
                    <h6 class="mb-0"><i class="fas fa-clipboard-list"></i> Kesimpulan Penilaian Risiko Stratejik KPMR
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
                                <form action="<?= base_url('Stratejikkpmr/simpanKesimpulan13') ?>" method="post">
                                    <?= csrf_field() ?>
                                    <div class="form-group">
                                        <label><strong>Edit Keterangan Kesimpulan:</strong></label>
                                        <textarea class="form-control" name="keterangan" rows="5"
                                            required><?= esc($kesimpulan13['keterangan']) ?></textarea>
                                    </div>
                                    <div class="d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-secondary" data-bs-toggle="collapse"
                                            data-bs-target="#editKesimpulan13">
                                            <i class="fas fa-times"></i> Batal
                                        </button>
                                        <button type="submit" class="btn btn-primary ml-2">
                                            <i class="fas fa-save"></i> Update Kesimpulan
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if ($canEdit): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> <strong>Kesimpulan belum diisi.</strong>
                            </div>
                            <form action="<?= base_url('Stratejikkpmr/simpanKesimpulan13') ?>" method="post">
                                <?= csrf_field() ?>
                                <div class="form-group">
                                    <label><strong>Keterangan Kesimpulan:</strong></label>
                                    <textarea class="form-control" name="keterangan" rows="5"
                                        placeholder="Masukkan kesimpulan penilaian risiko stratejik KPMR..."
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

            <!-- Export -->
            <div class="d-flex justify-content-center gap-2 mt-3">
                <a href="<?= base_url('Stratejikkpmr/exporttxtrisikostratejikkpmr') ?>"
                    class="btn btn-secondary btn-sm">
                    <i class="fa fa-file-alt"></i> Export .txt
                </a>
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
                                    <a href="<?= base_url('Stratejikkpmr/approveSemua') ?>" id="btnApproveSemua"
                                        class="btn approval-btn approval-btn-approve <?= !($canApprove ?? false) ? 'disabled-btn' : '' ?>">
                                        Approve
                                    </a>
                                    <a href="<?= base_url('Stratejikkpmr/unapproveSemua') ?>" id="btnUnapproveSemua"
                                        class="btn approval-btn approval-btn-reject <?= !($allApproved ?? false) ? 'disabled-btn' : '' ?>">
                                        Tolak
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- ═══ MODALS ════════════════════════════════════════════════════ -->

<div class="modal fade" id="modalUbah" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ubah Data Penilaian</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="formUbah" data-action="<?= base_url('Stratejikkpmr/ubah') ?>">
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

<div class="modal fade" id="modalUbahkesimpulan" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ubah Keterangan Kesimpulan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="formUbahKesimpulan" data-action="<?= base_url('Stratejikkpmr/ubahkesimpulan') ?>">
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

<div class="modal fade" id="modaltambahNilai" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Nilai Faktor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="formTambahNilai" data-action="<?= base_url('Stratejikkpmr/tambahNilai') ?>">
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
                        <button type="submit" name="tambahNilai" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modaltambahKomentar" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Komentar Direksi dan Dewan Komisaris</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="formKomentar" data-action="<?= base_url('Stratejikkpmr/tambahKomentar') ?>">
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

<!-- ═══ JAVASCRIPT ════════════════════════════════════════════════ -->
<script>
    (function () {
        'use strict';

        const CTRL = 'Stratejikkpmr';
        const GLOBAL_KODEBPR = '<?= esc($kodebpr ?? '') ?>';
        const GLOBAL_PERIODE_ID = '<?= esc($activePeriodeId ?? '') ?>';
        const BASE_URL = '<?= base_url() ?>';
        const PENILAIAN_CONFIG = <?= json_encode($penilaianConfig ?? []) ?>;
        const CSRF_TOKEN_NAME = '<?= csrf_token() ?>';
        let CSRF_HASH = '<?= csrf_hash() ?>';

        const CAN_EDIT = <?= json_encode(($userInGroupAdmin ?? false) || ($userInGroupPE ?? false)) ?>;
        const CAN_APPROVE = <?= json_encode(($userInGroupAdmin ?? false) || ($userInGroupDireksi ?? false) || ($userInGroupDireksi2 ?? false)) ?>;

        /* ═══ UTILS ══════════════════════════════════════════════════════ */
        function debounce(fn, ms) {
            let t; return function (...a) { clearTimeout(t); t = setTimeout(() => fn.apply(this, a), ms); };
        }
        const $id = id => document.getElementById(id);
        function escH(s) {
            return String(s ?? '').replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m]));
        }

        /* ═══ CSRF ═══════════════════════════════════════════════════════ */
        function refreshCsrf() {
            const m = document.querySelector('meta[name="csrf-token"]') || document.querySelector('meta[name="X-CSRF-TOKEN"]');
            if (m) CSRF_HASH = m.getAttribute('content');
        }

        /* ═══ POST HELPER ════════════════════════════════════════════════ */
        function postJSON(url, data) {
            data[CSRF_TOKEN_NAME] = CSRF_HASH;
            const full = url.startsWith('http') ? url : BASE_URL + url.replace(/^\//, '');
            return fetch(full, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: new URLSearchParams(data)
            }).then(r => { refreshCsrf(); if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); });
        }

        /* ═══ TOAST ══════════════════════════════════════════════════════ */
        function showToast(title, msg, type = 'info') {
            const c = { success: '#28a745', error: '#dc3545', warning: '#e0a800', info: '#17a2b8' };
            const i = { success: 'check-circle', error: 'exclamation-circle', warning: 'exclamation-triangle', info: 'info-circle' };
            const t = document.createElement('div');
            t.className = 'toast-notification';
            t.style.background = c[type] || c.info;
            t.innerHTML = `<i class="fas fa-${i[type]}"></i><div><strong>${escH(title)}</strong><br><span>${escH(msg)}</span></div>`;
            $id('toast-wrapper').appendChild(t);
            requestAnimationFrame(() => t.classList.add('show'));
            setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 350); }, 3500);
        }

        /* ═══ MODAL ══════════════════════════════════════════════════════ */
        const _mods = {};
        function modalShow(id) {
            const el = $id(id); if (!el) return;
            if (window.bootstrap?.Modal) {
                if (!_mods[id]) { _mods[id] = new bootstrap.Modal(el, { backdrop: true, keyboard: true }); el.addEventListener('hidden.bs.modal', () => delete _mods[id], { once: true }); }
                _mods[id].show();
            } else if (window.$) window.$('#' + id).modal('show');
        }
        function modalHide(id) {
            if (_mods[id]) _mods[id].hide();
            else if (window.bootstrap?.Modal) bootstrap.Modal.getInstance($id(id))?.hide();
            else if (window.$) window.$('#' + id).modal('hide');
        }
        document.querySelectorAll('[data-bs-dismiss="modal"]').forEach(b => b.addEventListener('click', function () { const m = this.closest('.modal'); if (m) modalHide(m.id); }));
        document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(b => b.addEventListener('click', function () { const t = document.querySelector(this.getAttribute('data-bs-target')); if (t) t.classList.toggle('show'); }));

        /* ═══ PERINGKAT BADGE ════════════════════════════════════════════ */
        const _pl = { 1: 'Sangat Rendah', 2: 'Rendah', 3: 'Sedang', 4: 'Tinggi', 5: 'Sangat Tinggi' };
        const _bc = { 1: 'badge-info', 2: 'badge-success', 3: 'badge-warning', 4: 'badge-danger', 5: 'badge-dark' };
        function updateBadgePeringkat13(p) {
            const el = $id('badge-peringkat13'); if (!el) return;
            const n = parseInt(p);
            el.className = `badge badge-lg ${_bc[n] || 'badge-secondary'}`;
            el.style.cssText = 'font-size:1.1em;padding:.5em 1em;';
            el.innerHTML = `<i class="fas fa-check-circle"></i> Peringkat penilaian: ${n} [${_pl[n] || 'N/A'}]`;
        }
        function updateMainApprovalBadge(ok, at = null) {
            const b = $id('approval-badge-main'); if (!b) return;
            if (ok) { b.className = 'badge badge-success'; b.style.fontSize = '14px'; b.innerHTML = `Disetujui oleh <strong>Direktur Utama</strong><br>${at ? new Date(at).toLocaleString('id-ID') : '-'}`; }
            else { b.className = 'badge badge-secondary'; b.style.fontSize = '14px'; b.innerHTML = 'Belum Disetujui Seluruhnya<br>Oleh Direktur Utama'; }
        }

        /* ═══ RATING OPTIONS ═════════════════════════════════════════════ */
        function generateRatingOptions(faktorId, containerId) {
            const cfg = PENILAIAN_CONFIG[faktorId] || {};
            const el = $id(containerId); if (!el) return cfg;
            let html = '';
            for (let i = 1; i <= 5; i++) {
                const desc = (cfg.descriptions && cfg.descriptions[i]) || 'Tidak ada deskripsi';
                html += `<a href="#" class="list-group-item list-group-item-action py-2 px-3" data-value="${i}" data-description="${escH(desc)}"><h6 class="mb-1"><b>Nilai ${i}</b></h6><p class="mb-0 small">${desc}</p></a>`;
            }
            el.innerHTML = html; return cfg;
        }
        function updateCatatanInfo(cfg, pfx) {
            let html = '';
            if (cfg.threshold) html += `<strong>Threshold:</strong> ${cfg.threshold}<br>`;
            if (cfg.catatan) html += `<strong>Catatan:</strong> ${cfg.catatan}`;
            const el = $id(pfx + '-catatan-info'), tx = $id(pfx + '-catatan-text');
            if (!el || !tx) return;
            if (html) { tx.innerHTML = html; el.classList.remove('d-none'); } else el.classList.add('d-none');
        }

        /* ═══ RENDER ═════════════════════════════════════════════════════ */
        function renderApprovalBtn(faktorId, nilai) {
            <?php if (($userInGroupAdmin ?? false) || ($userInGroupDireksi ?? false) || ($userInGroupDireksi2 ?? false)): ?>
                if (!nilai || !nilai.penilaiankredit) return '<span class="text-muted ml-2">-</span>';
                const ok = nilai.accdir2 == 1;
                return `<form action="${BASE_URL}${CTRL}/${ok ? 'unapprovedir2' : 'accdir2'}" method="POST" style="display:inline-block;" class="ml-2 form-approval">
                <input type="hidden" name="${CSRF_TOKEN_NAME}" value="${CSRF_HASH}">
                <input type="hidden" name="faktor1id"  value="${faktorId}">
                <input type="hidden" name="kodebpr"    value="${GLOBAL_KODEBPR}">
                <input type="hidden" name="periode_id" value="${GLOBAL_PERIODE_ID}">
                <button type="submit" class="btn btn-sm ${ok ? 'btn-success' : 'btn-dark'}"><i class="fas fa-${ok ? 'check' : 'times'}-circle"></i></button></form>`;
            <?php else: ?>
                return '';
            <?php endif; ?>
        }

        function renderCategoryChildren(factor) {
            if (!factor.children?.length) return '';
            return factor.children.map((child, idx) => {
                const nilai = child.nilai, hasData = nilai && nilai.penilaiankredit;
                const title = escH(child.title).replace(/\\n/g, '<br>').replace(/\n/g, '<br>').replace(/&lt;br&gt;/gi, '<br>');
                return `<div class="border-left border-success pl-4 mb-3">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div class="flex-grow-1"><h6 class="font-weight-bold">${idx + 1}. ${title}</h6></div>
                    <div class="child-btn-group">
                        ${hasData ? `<span class="badge badge-success"><i class="fas fa-check"></i> Peringkat: ${escH(nilai.penilaiankredit)}</span>` : '<span class="badge badge-secondary">Belum dinilai</span>'}
                        ${CAN_EDIT ? (hasData
                        ? `<button class="btn btn-sm btn-warning ml-1 btn-edit" data-id="${child.faktor_id}" data-title="${escH(child.title)}" data-penilaiankredit="${escH(nilai.penilaiankredit)}" data-keterangan="${escH(nilai.keterangan || '')}"><i class="fas fa-edit"></i></button>`
                        : `<button type="button" class="btn btn-sm btn-success ml-1 btn-add" data-id="${child.faktor_id}" data-title="${escH(child.title)}"><i class="fas fa-plus"></i></button>`) : ''}
                        <button type="button" class="btn btn-sm btn-primary ml-1 position-relative komentar-btn" data-faktor-id="${child.faktor_id}">
                            <i class="fas fa-comment"></i>
                            <span id="notification-badge-${child.faktor_id}" class="badge badge-danger notification-badge" style="display:none;">0</span>
                        </button>
                        ${renderApprovalBtn(child.faktor_id, nilai)}
                    </div>
                </div>
            </div>`;
            }).join('');
        }

        function renderFactors(factors) {
            $id('factors-container').innerHTML = factors.map((f, idx) => {
                const nilai = f.nilai, hasData = nilai && nilai.penilaiankredit;
                return `<div class="card shadow mb-4">
                <div class="card-header py-3 factor-card-header">
                    <h6 class="m-0 font-weight-bold text-primary">${idx + 1}. ${escH(f.title)}</h6>
                    <div class="factor-btn-group">
                        ${hasData ? `<span class="badge badge-info"><i class="fas fa-check"></i> Peringkat: ${escH(nilai.penilaiankredit)}</span>` : '<span class="badge badge-secondary">Belum dinilai</span>'}
                        ${f.type === 'category' ? `
                            ${CAN_EDIT ? `<button type="button" class="btn btn-sm btn-warning ml-1 btn-edit-keterangan" data-id="${f.faktor_id}" data-title="${escH(f.title)}" data-keterangan="${escH(nilai?.keterangan || '')}"><i class="fas fa-edit"></i></button>` : ''}
                            <button type="button" class="btn btn-sm btn-primary ml-1 position-relative komentar-btn" data-faktor-id="${f.faktor_id}">
                                <i class="fas fa-comment"></i>
                                <span id="notification-badge-${f.faktor_id}" class="badge badge-danger notification-badge" style="display:none;">0</span>
                            </button>
                            ${renderApprovalBtn(f.faktor_id, nilai)}
                        ` : ''}
                    </div>
                </div>
                <div class="card-body">${f.type === 'category' ? renderCategoryChildren(f) : ''}</div>
            </div>`;
            }).join('');
        }

        /* ═══ DELEGATED EVENTS ═══════════════════════════════════════════ */
        document.addEventListener('click', function (e) {
            const ri = e.target.closest('#editRatingList .list-group-item, #addRatingList .list-group-item');
            if (ri) {
                e.preventDefault();
                const inEdit = !!ri.closest('#editRatingList'), pfx = inEdit ? 'edit' : 'add';
                document.querySelectorAll(`#${pfx}RatingList .list-group-item`).forEach(i => i.classList.remove('active'));
                ri.classList.add('active');
                const vEl = $id(`${pfx}-penilaiankredit`), pEl = $id(`${pfx}-penjelasanpenilaian`), sEl = $id(`${pfx}SelectedValue`);
                if (vEl) vEl.value = ri.dataset.value;
                if (pEl) pEl.value = ri.dataset.description;
                if (sEl) sEl.textContent = 'Nilai ' + ri.dataset.value;
                return;
            }
            const bEdit = e.target.closest('.btn-edit');
            if (bEdit) {
                e.preventDefault();
                $id('edit-faktor-id').value = bEdit.dataset.id;
                $id('edit-parameterpenilaian').value = bEdit.dataset.title;
                $id('edit-penilaiankredit').value = bEdit.dataset.penilaiankredit;
                $id('edit-keterangan').value = bEdit.dataset.keterangan || '';
                const cfg = generateRatingOptions(bEdit.dataset.id, 'editRatingList');
                updateCatatanInfo(cfg, 'edit');
                requestAnimationFrame(() => {
                    document.querySelectorAll('#editRatingList .list-group-item').forEach(item => {
                        if (item.dataset.value == bEdit.dataset.penilaiankredit) { item.classList.add('active'); const s = $id('editSelectedValue'); if (s) s.textContent = 'Nilai ' + bEdit.dataset.penilaiankredit; }
                    });
                });
                modalShow('modalUbah'); return;
            }
            const bEK = e.target.closest('.btn-edit-keterangan');
            if (bEK) {
                e.preventDefault();
                $id('edit-faktor-id-kesimpulan').value = bEK.dataset.id;
                $id('edit-parameterpenilaian-kesimpulan').value = bEK.dataset.title;
                $id('edit-keterangan-kesimpulan').value = bEK.dataset.keterangan || '';
                modalShow('modalUbahkesimpulan'); return;
            }
            const bAdd = e.target.closest('.btn-add');
            if (bAdd) {
                e.preventDefault();
                $id('add-faktor-id').value = bAdd.dataset.id;
                $id('add-parameterpenilaian').value = bAdd.dataset.title;
                ['add-penilaiankredit', 'add-penjelasanpenilaian', 'add-keterangan'].forEach(id => { const el = $id(id); if (el) el.value = ''; });
                const s = $id('addSelectedValue'); if (s) s.textContent = 'Belum ada';
                updateCatatanInfo(generateRatingOptions(bAdd.dataset.id, 'addRatingList'), 'add');
                modalShow('modaltambahNilai'); return;
            }
            const bKom = e.target.closest('.komentar-btn');
            if (bKom) {
                e.preventDefault();
                const fid = bKom.dataset.faktorId;
                const cf = $id('comment-faktor-id'); if (cf) cf.value = fid;
                loadKomentar(fid); markRead(fid); modalShow('modaltambahKomentar'); return;
            }
            const apBtn = e.target.closest('.form-approval button[type="submit"]');
            if (apBtn) {
                e.preventDefault();
                const form = apBtn.closest('.form-approval'), orig = apBtn.innerHTML;
                apBtn.disabled = true; apBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                const data = new URLSearchParams(new FormData(form)); data.set(CSRF_TOKEN_NAME, CSRF_HASH);
                fetch(form.action, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' }, body: data })
                    .then(r => { refreshCsrf(); return r.text(); })
                    .then(() => { showToast('Berhasil', 'Status approval diubah', 'success'); loadFactorsData(); })
                    .catch(() => { apBtn.disabled = false; apBtn.innerHTML = orig; showToast('Error', 'Gagal', 'error'); });
                return;
            }
        });

        /* ═══ AJAX FORM HANDLER ══════════════════════════════════════════ */
        function makeAjaxFormHandler(formId, onSuccess, opts = {}) {
            const form = $id(formId); if (!form) return;
            form.addEventListener('submit', e => e.preventDefault(), true);
            form.addEventListener('submit', debounce(function (e) {
                e.preventDefault(); e.stopPropagation();
                const btn = this.querySelector('button[type="submit"]'); if (!btn) return;
                const orig = btn.innerHTML;
                btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
                const data = new URLSearchParams(new FormData(this)); data.set(CSRF_TOKEN_NAME, CSRF_HASH);
                const url = this.dataset.action || (this.action !== window.location.href ? this.action : null);
                if (!url) { btn.disabled = false; btn.innerHTML = orig; showToast('Error', 'URL tidak ditemukan', 'error'); return; }
                fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' }, body: data })
                    .then(r => { refreshCsrf(); return r.json(); })
                    .then(r => {
                        btn.disabled = false; btn.innerHTML = orig;
                        if (r.success || r.status === 'success') {
                            showToast('Berhasil', opts.successMsg || 'Data berhasil disimpan', 'success');
                            if (opts.hideModal) modalHide(opts.hideModal);
                            if (onSuccess) onSuccess(r);
                            if (!opts.noRefresh) { updateMainApprovalBadge(false); loadFactorsData(); }
                        } else showToast('Error', r.message || 'Gagal menyimpan', 'error');
                    })
                    .catch(err => { btn.disabled = false; btn.innerHTML = orig; showToast('Error', 'Koneksi: ' + err.message, 'error'); });
            }, 300));
        }

        makeAjaxFormHandler('formUbah', r => { if (r.peringkat13) updateBadgePeringkat13(r.peringkat13); }, { hideModal: 'modalUbah', successMsg: 'Data berhasil diubah' });
        makeAjaxFormHandler('formUbahKesimpulan', null, { hideModal: 'modalUbahkesimpulan', successMsg: 'Keterangan berhasil diubah' });
        makeAjaxFormHandler('formTambahNilai', r => { if (r.peringkat13) updateBadgePeringkat13(r.peringkat13); const s = $id('addSelectedValue'); if (s) s.textContent = 'Belum ada'; }, { hideModal: 'modaltambahNilai', successMsg: 'Nilai berhasil ditambahkan' });
        makeAjaxFormHandler('formKomentar', () => { loadKomentar($id('comment-faktor-id')?.value); pollUnreadCounts(); const ta = $id('komentar'); if (ta) ta.value = ''; }, { noRefresh: true, successMsg: 'Komentar berhasil ditambahkan' });

        /* ═══ KOMENTAR ═══════════════════════════════════════════════════ */
        function loadKomentar(faktorId) {
            const list = $id('komentarLamaList'); list.innerHTML = '<li>Memuat komentar...</li>';
            postJSON(CTRL + '/getKomentarByFaktorId/' + faktorId, { kodebpr: GLOBAL_KODEBPR, periode_id: GLOBAL_PERIODE_ID })
                .then(data => {
                    const rows = Array.isArray(data) ? data : (data.data || []);
                    list.innerHTML = rows.length ? rows.map(k => `<li>${escH(k.komentar)} — <em>${escH(k.fullname)} &bull; ${k.created_at}</em></li>`).join('') : '<li>Tidak ada komentar.</li>';
                }).catch(() => { list.innerHTML = '<li>Gagal memuat komentar.</li>'; });
        }
        function markRead(faktorId) {
            postJSON(CTRL + '/markUserCommentsAsRead', { faktor_id: faktorId, kodebpr: GLOBAL_KODEBPR, periode_id: GLOBAL_PERIODE_ID })
                .then(r => { if (r.status === 'success') { const b = $id('notification-badge-' + faktorId); if (b) { b.style.display = 'none'; b.textContent = '0'; } } })
                .catch(() => { });
        }

        /* ═══ BADGE POLLING — FIX: dipanggil setelah DOM badge ada ══════ */
        function pollUnreadCounts() {
            fetch(BASE_URL + CTRL + '/getAllUnreadCounts?' + new URLSearchParams({ kodebpr: GLOBAL_KODEBPR, periode_id: GLOBAL_PERIODE_ID }), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(r => r.json())
                .then(r => {
                    if (r.status !== 'success') return;
                    document.querySelectorAll('.notification-badge').forEach(b => { b.style.display = 'none'; b.textContent = '0'; });
                    Object.entries(r.counts || {}).forEach(([fid, cnt]) => {
                        const n = parseInt(cnt) || 0; if (n <= 0) return;
                        const b = $id('notification-badge-' + fid);
                        if (b) { b.textContent = n; b.style.display = 'inline-flex'; }
                    });
                }).catch(() => { });
        }

        let _pollTimer = null;
        function startPolling() {
            if (_pollTimer) return;
            pollUnreadCounts();
            _pollTimer = setInterval(pollUnreadCounts, 30000);
        }

        /* ═══ APPROVE / UNAPPROVE SEMUA ══════════════════════════════════ */
        function wireApproveLink(id, confirmMsg, successMsg, approved) {
            const lnk = $id(id); if (!lnk) return;
            lnk.addEventListener('click', function (e) {
                e.preventDefault(); if (!confirm(confirmMsg)) return;
                let path; try { path = new URL(this.href).pathname; } catch (_) { path = this.href.replace(/^https?:\/\/[^/]+/, ''); }
                const orig = this.innerHTML; this.classList.add('disabled-btn'); this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                postJSON(path, {})
                    .then(() => { showToast('Berhasil', successMsg, 'success'); if (approved) updateMainApprovalBadge(true, new Date().toISOString()); else updateMainApprovalBadge(false); loadFactorsData(); this.classList.remove('disabled-btn'); this.innerHTML = orig; })
                    .catch(() => { this.classList.remove('disabled-btn'); this.innerHTML = orig; showToast('Error', 'Gagal', 'error'); });
            });
        }
        wireApproveLink('btnApproveSemua', 'Apakah Anda yakin ingin melakukan approval?', 'Semua data berhasil disetujui', true);
        wireApproveLink('btnUnapproveSemua', 'Batalkan semua approval?', 'Semua approval berhasil dibatalkan', false);

        /* ═══ LOAD FACTORS — badge polling mulai setelah render ═════════ */
        function loadFactorsData() {
            const skl = $id('factors-skeleton'), cnt = $id('factors-container');
            if (skl) skl.style.display = 'block';
            if (cnt) cnt.style.display = 'none';
            postJSON(CTRL + '/getFactorsData', { kodebpr: GLOBAL_KODEBPR, periode_id: GLOBAL_PERIODE_ID })
                .then(r => {
                    if (!r || r.status !== 'success' || !Array.isArray(r.data)) throw new Error(r?.message || 'Response tidak valid');
                    renderFactors(r.data);
                    if (skl) skl.style.display = 'none';
                    if (cnt) cnt.style.display = 'block';
                    startPolling(); // ★ DOM badge sudah ada, baru mulai polling
                })
                .catch(err => {
                    if (skl) skl.innerHTML = `<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Gagal memuat data: ${escH(err.message)}. <a href="#" onclick="location.reload()" class="alert-link ml-2">Refresh halaman</a></div>`;
                });
        }

        /* ═══ PERINGKAT 13 MANUAL / RESET ════════════════════════════════ */
        const bSimpan = $id('btnSimpanPeringkat13');
        if (bSimpan) bSimpan.addEventListener('click', debounce(function () {
            const p = $id('peringkat13_manual')?.value; if (!p) { showToast('Error', 'Pilih peringkat terlebih dahulu', 'error'); return; }
            const orig = this.innerHTML; this.disabled = true; this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
            postJSON(CTRL + '/ubahPeringkat13Manual', { penilaiankredit: p })
                .then(r => { if (r.status === 'success') { showToast('Berhasil', 'Peringkat berhasil diubah', 'success'); setTimeout(() => location.reload(), 1000); } else { showToast('Error', r.message || 'Gagal', 'error'); this.disabled = false; this.innerHTML = orig; } })
                .catch(() => { this.disabled = false; this.innerHTML = orig; showToast('Error', 'Gagal', 'error'); });
        }, 300));

        const bReset = $id('btnResetPeringkat13');
        if (bReset) bReset.addEventListener('click', debounce(function () {
            if (!confirm('Kembalikan ke perhitungan otomatis?')) return;
            const orig = this.innerHTML; this.disabled = true; this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            postJSON(CTRL + '/resetPeringkat13Auto', {})
                .then(r => { if (r.status === 'success') { showToast('Berhasil', 'Peringkat dikembalikan ke otomatis', 'success'); setTimeout(() => location.reload(), 1000); } else { showToast('Error', r.message || 'Gagal', 'error'); this.disabled = false; this.innerHTML = orig; } })
                .catch(() => { this.disabled = false; this.innerHTML = orig; showToast('Error', 'Gagal', 'error'); });
        }, 300));

        /* ═══ INIT ════════════════════════════════════════════════════════ */
        loadFactorsData();

    })();
</script>

<style>
    :root {
        --primary: #10598A;
        --success: #28a745;
        --danger: #dc3545;
        --dark: #343a40;
        --shadow: 0 2px 4px rgba(0, 0, 0, .1);
        --shadow-lg: 0 4px 12px rgba(0, 0, 0, .18);
        --radius: 8px;
        --transition: all .2s ease-in-out;
        --primary-gradient: linear-gradient(135deg, #141863 0%, #1f2aa8 100%);
    }

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
    }

    .toast-notification.show {
        opacity: 1;
        transform: translateX(0);
    }

    .toast-notification i {
        font-size: 22px;
        flex-shrink: 0;
    }

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

    .collapse {
        display: none;
    }

    .collapse.show {
        display: block;
    }

    .card {
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        margin-bottom: 20px;
    }

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
        display: inline-block;
        transition: var(--transition);
    }

    .kpmr-btn:hover {
        transform: translateY(-2px);
        color: #fff;
    }

    .approvaldir-card {
        border-radius: var(--radius);
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
        color: #fff;
    }

    .approval-btn-approve:hover {
        background: #218838;
        color: #fff;
        transform: translateY(-1px);
    }

    .approval-btn-reject {
        background: var(--danger);
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

    .komentar-list {
        list-style: none;
        padding-left: 0;
        max-height: 220px;
        overflow-y: auto;
    }

    .komentar-list li {
        padding: 6px 8px;
        border-bottom: 1px solid #dee2e6;
        font-size: .88em;
        line-height: 1.4;
    }

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

    /* ── Tablet ≤768px ───────────────────────────────────────── */
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

    /* ── Mobile kecil ≤576px ─────────────────────────────────── */
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

        .modal-dialog {
            margin: 4px;
            max-width: 100%;
        }

        .modal-content {
            border-radius: 6px;
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