<?php
$peringkat13 = null;
$keterangan13 = '';
$peringkat14 = null;
$keterangan14 = '';

if (isset($nilai13) && is_array($nilai13)) {
    $peringkat13 = $nilai13['penilaiankredit'] ?? null;
    $keterangan13 = $nilai13['keterangan'] ?? '';
}

if (isset($nilai14) && is_array($nilai14)) {
    $peringkat14 = $nilai14['penilaiankredit'] ?? null;
    $keterangan14 = $nilai14['keterangan'] ?? '';
}

$peringkatLabels = [
    '1' => 'Sangat Rendah',
    '2' => 'Rendah',
    '3' => 'Sedang',
    '4' => 'Tinggi',
    '5' => 'Sangat Tinggi'
];

function getBadgeClass($nilai)
{
    switch ($nilai) {
        case '1':
            return 'badge-info';
        case '2':
            return 'badge-success';
        case '3':
            return 'badge-warning';
        case '4':
            return 'badge-danger';
        case '5':
            return 'badge-dark';
        default:
            return 'badge-secondary';
    }
}

$currentUserId = session()->get('user_id');
$activePeriodeId = session()->get('active_periode');
$subkategori = 'LIKUIDITASKPMR';
$canEdit = $userInGroupAdmin ?? false || $userInGroupPE ?? false;
$canApproveDir = $userInGroupAdmin ?? false || $userInGroupDireksi2 ?? false || $userInGroupDireksi ?? false;

$kesimpulan13 = $nilai13 ?? null;
?>

<div class="alert alert-info my-2">
    <?php if (isset($bprData) && isset($periodeDetail)): ?>
        <strong><?= esc($bprData['namabpr'] ?? 'Nama BPR') ?></strong> - Periode Pelaporan
        Semester <?= esc($periodeDetail['semester']) ?> Tahun <?= esc($periodeDetail['tahun']) ?>
    <?php elseif (isset($periodeDetail)): ?>
        <strong>Periode:</strong>
        Semester <?= esc($periodeDetail['semester']) ?> Tahun <?= esc($periodeDetail['tahun']) ?>
    <?php else: ?>
        <strong>Periode belum ditentukan</strong>
    <?php endif; ?>
</div>

<div id="ajaxAlert" class="alert alert-success alert-dismissible fade show d-none" role="alert">
    <span id="ajaxAlertMsg"></span>
    <button type="button" class="close" data-dismiss="alert">&times;</button>
</div>

<div class="container-fluid">
    <?php if (session()->get('message')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <strong><?= session()->getFlashdata('message'); ?></strong>
        </div>
    <?php endif; ?>

    <div class="card card-body">
        <div class="table-vertical">
            <div class="button-bar">
                <a href="Likuiditasinheren" class="kpmr-btn icon-link icon-link-hover" id="btn-laporan">🡸 Laporan
                    Risiko
                    Likuiditas Inheren</a>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
                <div class="text-center flex-grow-1">
                    <div class="text-center">
                        <h4 class="mb-0">
                            <i class="fas fa-info-circle text-primary"></i>
                            <strong>Tingkat Risiko Likuiditas KPMR</strong>
                        </h4>
                        <small class="text-muted">
                            <i class="fas fa-file-alt"></i>
                            Laporan Profil Risiko Semester <?= esc($periodeDetail['semester'] ?? ''); ?> Tahun
                            <?= esc($periodeDetail['tahun'] ?? ''); ?>
                        </small>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
                <div class="text-center flex-grow-1">
                    <div class="text-center">
                        <div class="mt-2">
                            <span class="badge badge-lg <?= getBadgeClass($peringkat13) ?>"
                                style="font-size: 1.1em; padding: 0.5em 1em;">
                                <i class="fas fa-check-circle"></i>
                                Peringkat penilaian: <?= esc($peringkat13 ?? 'N/A') ?>
                                [<?= $peringkatLabels[$peringkat13] ?? 'N/A' ?>]
                            </span>
                        </div>
                        <div class="mt-2">
                            <span class="badge badge-lg <?= getBadgeClass($peringkat14) ?>"
                                style="font-size: 1.1em; padding: 0.5em 1em;">
                                <i class="fas fa-check-circle"></i>
                                Peringkat penilaian pada periode sebelumnya: <?= esc($peringkat14 ?? 'N/A') ?>
                                [<?= $peringkatLabels[$peringkat14] ?? 'N/A' ?>]
                            </span>
                            <?php if (($userInGroupPE ?? false) || ($userInGroupAdmin ?? false)): ?>
                                <button type="button" class="btn btn-sm btn-outline-primary ml-2" data-toggle="collapse"
                                    data-target="#editNilai14">
                                    <i class="fa fa-caret-down"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Edit Nilai 14 -->
            <div id="editNilai14" class="collapse">
                <div class="p-3 border-top">
                    <?php if (!empty($nilai14)): ?>

                        <form action="<?= base_url('Likuiditaskpmr/simpanNilai14') ?>" method="post">
                            <?= csrf_field() ?>
                            <select name="penilaiankredit" class="form-control w-50 mx-auto" required>
                                <option value="" disabled selected>
                                    -- Nilai Saat Ini: <?= $nilai14['penilaiankredit'] ?> --
                                </option>
                                <option value="1">Sangat Rendah (1)</option>
                                <option value="2">Rendah (2)</option>
                                <option value="3">Cukup (3)</option>
                                <option value="4">Tinggi (4)</option>
                                <option value="5">Sangat Tinggi (5)</option>
                            </select>
                            <div class="text-center">
                                <button type="submit" class="btn btn-warning btn-sm mt-2">
                                    <i class="fas fa-edit"></i> Update
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <form action="<?= base_url('Likuiditaskpmr/simpanNilai14') ?>" method="post">
                            <?= csrf_field() ?>
                            <select name="penilaiankredit" class="form-control w-50 mx-auto" required>
                                <option value="">-- Pilih Tingkat Risiko --</option>
                                <option value="1">Sangat Rendah (1)</option>
                                <option value="2">Rendah (2)</option>
                                <option value="3">Cukup (3)</option>
                                <option value="4">Tinggi (4)</option>
                                <option value="5">Sangat Tinggi (5)</option>
                            </select>
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary btn-sm mt-2">
                                    <i class="fas fa-save"></i> Simpan
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Approval Status -->
            <div style="text-align: right;">
                <span id="approval-badge-main"
                    class="badge <?= ($allApproved ?? false) ? 'badge-success' : 'badge-secondary' ?>"
                    style="font-size: 14px;">
                    <?php if ($allApproved ?? false): ?>
                        Disetujui oleh <strong>Direktur Utama</strong><br>
                        <?php
                        $approvedAt = $nilai13['approved_at'] ?? null;
                        echo $approvedAt ? date('d/m/Y H:i:s', strtotime($approvedAt)) : '-';
                        ?>
                    <?php else: ?>
                        Belum Disetujui Seluruhnya<br>Oleh Direktur Utama
                    <?php endif; ?>
                </span>
            </div>

            <div class="card-body">
                <div class="mb-2">
                    <strong>A. Kertas Kerja Likuiditas KPMR</strong>
                </div>

                <!-- SKELETON LOADER -->
                <div id="factors-skeleton" style="display: block;">
                    <?php for ($i = 1; $i <= 3; $i++): ?>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <div class="skeleton skeleton-text" style="width: 60%;"></div>
                            </div>
                            <div class="card-body">
                                <div class="skeleton skeleton-text mb-2" style="width: 80%;"></div>
                                <div class="skeleton skeleton-text" style="width: 70%;"></div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>

                <!-- REAL DATA - Load via AJAX -->
                <div id="factors-container" style="display: none;"></div>
            </div>

            <!-- Kesimpulan Card -->
            <div class="card mt-3">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-clipboard-list"></i> Kesimpulan Penilaian Risiko Likuiditas KPMR
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($kesimpulan13) && !empty($kesimpulan13['keterangan'])): ?>
                        <div class="alert border">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="alert-heading">
                                        <i class="fas fa-check-circle"></i> Kesimpulan Tersimpan
                                    </h6>
                                    <p class="mb-0"><?= nl2br(esc($kesimpulan13['keterangan'])) ?></p>
                                </div>
                                <?php if (($userInGroupPE ?? false) || ($userInGroupAdmin ?? false)): ?>
                                    <button type="button" class="btn btn-sm btn-warning" data-toggle="collapse"
                                        data-target="#editKesimpulan13">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (($userInGroupPE ?? false) || ($userInGroupAdmin ?? false)): ?>
                            <div class="collapse" id="editKesimpulan13">
                                <hr>
                                <form action="<?= base_url('Likuiditaskpmr/simpanKesimpulan13') ?>" method="post">
                                    <?= csrf_field() ?>
                                    <div class="form-group">
                                        <label for="keterangan_edit"><strong>Edit Keterangan Kesimpulan:</strong></label>
                                        <textarea class="form-control" name="keterangan" id="keterangan_edit" rows="5"
                                            required><?= esc($kesimpulan13['keterangan']) ?></textarea>
                                    </div>
                                    <div class="d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-secondary" data-toggle="collapse"
                                            data-target="#editKesimpulan13">
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

                            <form action="<?= base_url('Likuiditaskpmr/simpanKesimpulan13') ?>" method="post">
                                <?= csrf_field() ?>
                                <div class="form-group">
                                    <label for="keterangan_new"><strong>Keterangan Kesimpulan:</strong></label>
                                    <textarea class="form-control" name="keterangan" id="keterangan_new" rows="5"
                                        placeholder="Masukkan kesimpulan penilaian risiko kepatuhan inheren..."
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
                                <i class="fas fa-info-circle"></i>
                                Kesimpulan belum diisi oleh Tim Penilaian Risiko.
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="d-flex justify-content-center gap-2 mt-3">
                <a href="<?= base_url('Likuiditaskpmr/exporttxtrisikolikuiditaskpmr'); ?>"
                    class="btn btn-secondary btn-sm">
                    <i class="fa fa-file-alt"></i> Export .txt
                </a>
            </div>

            <!-- Approval Direktur Utama Section -->
            <?php if (($userInGroupAdmin ?? false) || ($userInGroupDireksi ?? false)): ?>
                <div class="d-flex justify-content-center mt-3">
                    <div class="col-md-3 mx-auto">
                        <div class="card shadow-sm approvaldir-card" style="height: 120px;">
                            <div class="card-body approvaldir-card-body">
                                <div class="approval-badge-container">
                                    <span class="badge approval-badge">Approval Direktur Utama</span>
                                </div>
                                <div class="approval-buttons-container">
                                    <a href="<?= base_url('Likuiditaskpmr/approveSemua') ?>"
                                        class="btn approval-btn approval-btn-approve <?= !$canApprove ? 'disabled-btn' : '' ?>"
                                        onclick="return <?= $canApprove ? "confirm('Apakah Anda yakin ingin melakukan approval?')" : 'false' ?>;">
                                        Approve
                                    </a>
                                    <a href="<?= base_url('Likuiditaskpmr/unapproveSemua') ?>"
                                        class="btn approval-btn approval-btn-reject <?= !$allApproved ? 'disabled-btn' : '' ?>"
                                        onclick="return <?= $allApproved ? "confirm('Batalkan semua approval?')" : 'false' ?>;">
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

<div class="modal fade" id="modalUbah">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ubah <?= $judul ?? 'Data'; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('Likuiditaskpmr/ubah'); ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="faktor1id" id="edit-faktor-id">

                    <div class="mb-3">
                        <label for="edit-parameterpenilaian"><strong>Parameter penilaian risiko:</strong></label>
                        <textarea class="form-control" id="edit-parameterpenilaian" style="height: 100px"
                            readonly></textarea>
                    </div>

                    <label><strong>Pilih Tingkat Risiko:</strong></label>
                    <div class="alert alert-info mb-2" id="edit-catatan-info" style="display: none; font-size: 0.9em;">
                        <i class="fas fa-info-circle"></i> <span id="edit-catatan-text"></span>
                    </div>
                    <div class="list-group mb-3" id="editRatingList"></div>
                    <p>Nilai yang dipilih: <strong id="editSelectedValue" class="text-primary"></strong></p>
                    <input type="hidden" name="penilaiankredit" id="edit-penilaiankredit">
                    <input type="hidden" name="penjelasanpenilaian" id="edit-penjelasanpenilaian">

                    <div class="mb-3">
                        <label for="edit-keterangan"><strong>Keterangan:</strong></label>
                        <textarea class="form-control" name="keterangan" id="edit-keterangan" style="height: 100px"
                            placeholder="Masukkan keterangan..." required></textarea>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Ubah Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalUbahkesimpulan">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ubah Keterangan Kesimpulan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('Likuiditaskpmr/ubahkesimpulan'); ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="faktor1id" id="edit-faktor-id-kesimpulan">

                    <div class="mb-3">
                        <label for="edit-parameterpenilaian-kesimpulan"><strong>Sub Kategori:</strong></label>
                        <textarea class="form-control" id="edit-parameterpenilaian-kesimpulan" style="height: 100px"
                            readonly></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="edit-keterangan-kesimpulan"><strong>Keterangan:</strong></label>
                        <textarea class="form-control" name="keterangan" id="edit-keterangan-kesimpulan"
                            style="height: 100px" placeholder="Masukkan keterangan..." required></textarea>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Ubah Keterangan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modaltambahNilai">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="<?= base_url('Likuiditaskpmr/tambahNilai'); ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Nilai Faktor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="faktor_id" id="add-faktor-id">
                    <input type="hidden" name="fullname" value="<?= esc($fullname ?? '') ?>">

                    <div class="mb-3">
                        <label for="add-parameterpenilaian"><strong>Parameter penilaian risiko:</strong></label>
                        <textarea class="form-control" id="add-parameterpenilaian" style="height: 100px"
                            readonly></textarea>
                    </div>

                    <label><strong>Pilih Tingkat Risiko:</strong></label>
                    <div class="alert alert-info mb-2" id="add-catatan-info" style="display: none; font-size: 0.9em;">
                        <i class="fas fa-info-circle"></i> <span id="add-catatan-text"></span>
                    </div>
                    <div class="list-group mb-3" id="addRatingList"></div>
                    <p>Nilai yang dipilih: <strong id="addSelectedValue" class="text-primary">Belum ada</strong></p>
                    <input type="hidden" name="penilaiankredit" id="add-penilaiankredit" required>
                    <input type="hidden" name="penjelasanpenilaian" id="add-penjelasanpenilaian">

                    <div class="mb-3">
                        <label for="add-keterangan"><strong>Keterangan:</strong></label>
                        <textarea class="form-control" name="keterangan" id="add-keterangan" style="height: 100px"
                            placeholder="Masukkan keterangan..." required></textarea>
                    </div>
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

<div class="modal fade" id="modaltambahKomentar">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= base_url('Likuiditaskpmr/tambahKomentar'); ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Komentar Direksi dan Dewan Komisaris</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="faktor_id" id="comment-faktor-id">
                    <input type="hidden" name="kodebpr" value="<?= $kodebpr ?? '' ?>">

                    <div class="mb-3">
                        <label>Komentar Saat Ini:</label>
                        <ul id="komentarLamaList" style="list-style-type: none; padding-left: 0;">
                            <li>Memuat komentar...</li>
                        </ul>
                    </div>

                    <?php if (
                        ($userInGroupAdmin ?? false) ||
                        ($userInGroupDekom ?? false) ||
                        ($userInGroupDireksi ?? false) ||
                        ($userInGroupPE ?? false) ||
                        ($userInGroupDekom2 ?? false) ||
                        ($userInGroupDireksi2 ?? false)
                    ): ?>
                        <input type="hidden" name="fullname" value="<?= esc($fullname ?? '') ?>">
                        <div class="mb-3">
                            <label for="komentar">Tambahkan Komentar Baru:</label>
                            <textarea class="form-control" name="komentar" id="komentar" style="height: 100px"></textarea>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" name="tambahKomentar" class="btn btn-primary">Simpan Komentar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

<script>
    // CONSTANTS
    const GLOBAL_KODEBPR = '<?= $kodebpr ?? '' ?>';
    const GLOBAL_PERIODE_ID = '<?= $activePeriodeId ?? '' ?>';
    const GLOBAL_USER_ID = '<?= $currentUserId ?? '' ?>';
    const BASE_URL = '<?= base_url() ?>';
    const PENILAIAN_CONFIG = <?= json_encode($penilaianConfig ?? []) ?>;

    const USER_IN_GROUP_ADMIN = <?= json_encode($userInGroupAdmin ?? false) ?>;
    const USER_IN_GROUP_PE = <?= json_encode($userInGroupPE ?? false) ?>;
    const CAN_EDIT = USER_IN_GROUP_ADMIN || USER_IN_GROUP_PE;

    // Escape HTML
    const escapeHtml = (text) => {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    };


    // Generate Rating Options
    function generateRatingOptions(faktorId, containerSelector) {
        const config = PENILAIAN_CONFIG[faktorId] || PENILAIAN_CONFIG['default'] || {};
        const container = $(containerSelector);

        container.empty();
        for (let i = 1; i <= 5; i++) {
            const description = (config.descriptions && config.descriptions[i]) || 'Tidak ada deskripsi';
            container.append(`
            <a href="#" class="list-group-item list-group-item-action py-2 px-3" 
               data-value="${i}" data-description="${escapeHtml(description)}">
                <h6 class="mb-1"><b>Nilai ${i}</b></h6>
                <p class="mb-0 small">${description}</p>
            </a>
        `);
        }

        return config;
    }

    // ===== NOTIFICATION BADGE FUNCTIONS =====
    function updateBadge(faktorId, count) {
        const badge = $(`#notification-badge-${faktorId}`);

        console.log(`📍 updateBadge: faktor=${faktorId}, count=${count}, found=${badge.length > 0}`);

        if (badge.length) {
            badge.text(count);
            if (count > 0) {
                badge.fadeIn(200);
            } else {
                badge.fadeOut(200);
            }
        } else {
            console.warn(`⚠ Badge NOT FOUND: notification-badge-${faktorId}`);
        }
    }

    // ===== POLL UNREAD COUNTS =====
    function pollUnreadCounts() {
        $.ajax({
            url: `${BASE_URL}/Likuiditaskpmr/getAllUnreadCounts`,
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                console.log('📊 Poll response:', response);

                if (response.status === 'success') {
                    const counts = response.counts || {};

                    $('.notification-badge').fadeOut(200);

                    Object.keys(counts).forEach(faktorId => {
                        updateBadge(faktorId, counts[faktorId]);
                    });

                    console.log('✓ Badges updated:', Object.keys(counts).length);
                }
            },
            error: function (xhr, status, error) {
                console.error('✗ Poll error:', error);
            }
        });
    }

    $(document).ready(function () {
        pollUnreadCounts();

        setInterval(pollUnreadCounts, 10000);
    });

    function updateCatatanInfo(config, prefix) {
        const catatanText = $(`#${prefix}-catatan-text`);
        const catatanInfo = $(`#${prefix}-catatan-info`);

        let infoHtml = '';
        if (config.threshold) infoHtml += `<strong>Threshold:</strong> ${config.threshold}<br>`;
        if (config.catatan) infoHtml += `<strong>Catatan:</strong> ${config.catatan}`;

        if (infoHtml) {
            catatanText.html(infoHtml);
            catatanInfo.show();
        } else {
            catatanInfo.hide();
        }
    }

    // Handle Rating Selection
    function handleRatingSelection(value, description, prefix) {
        $(`#${prefix}-penilaiankredit`).val(value);
        $(`#${prefix}SelectedValue`).text('Nilai ' + value);
        $(`#${prefix}-penjelasanpenilaian`).val(description);
    }

    // ===== LAZY LOAD FACTORS DATA =====
    $(document).ready(function () {
        setTimeout(function () {
            $.ajax({
                url: BASE_URL + '/Likuiditaskpmr/getFactorsData',
                method: 'GET',
                beforeSend: function () {
                    $('#factors-skeleton').show();
                    $('#factors-container').hide();
                },
                success: function (response) {
                    if (response.status === 'success') {
                        renderFactors(response.data);
                        $('#factors-skeleton').hide();
                        $('#factors-container').fadeIn(300);
                    }
                },
                error: function () {
                    $('#factors-skeleton').html('<div class="alert alert-danger">Gagal memuat data. <a href="#" onclick="location.reload()">Refresh</a></div>');
                }
            });
        }, 100);
    });

    function renderSingleButtons(faktorId, nilai) {
        const hasData = nilai && nilai.penilaiankredit;

        let html = '';

        // Button Edit/Add
        if (CAN_EDIT) {
            if (hasData) {
                html += `
            <button class="btn btn-sm btn-warning ml-2 btn-edit" 
                    data-id="${faktorId}"
                    data-title="Parameter Lainnya"
                    data-penilaiankredit="${nilai.penilaiankredit}"
                    data-keterangan="${escapeHtml(nilai.keterangan || '')}">
                <i class="fas fa-edit"></i>
            </button>
        `;
            } else {
                html += `
            <button type="button" class="btn btn-sm btn-success ml-2 btn-add" 
                    data-id="${faktorId}"
                    data-title="Parameter Lainnya">
                <i class="fas fa-plus"></i>
            </button>
        `;
            }
        }

        // Button Komentar DENGAN BADGE
        html += `
        <button type="button" class="btn btn-sm btn-primary ml-2 position-relative komentar-button"
                data-faktor-id="${faktorId}">
            <i class="fas fa-comment"></i>
            <span id="notification-badge-${faktorId}" 
                  class="badge badge-danger notification-badge" 
                  style="display: none;">
                0
            </span>
        </button>
    `;

        // Button Approval
        html += renderApprovalButton(faktorId, nilai);

        return html;
    }

    // ===== RENDER FACTORS =====
    function renderFactors(factors) {
        const container = $('#factors-container');
        let html = '';

        factors.forEach((factor, index) => {
            const nilai = factor.nilai;
            const hasData = nilai && nilai.penilaiankredit;

            html += `
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    ${index + 1}. ${escapeHtml(factor.title)}
                </h6>
                <div>
                    ${hasData ?
                    `<span class="badge badge-info">
                            <i class="fas fa-check"></i> Peringkat penilaian: ${nilai.penilaiankredit}
                        </span>` :
                    '<span class="badge badge-secondary">Belum dinilai</span>'
                }
                    
                    ${factor.type === 'single' ? renderSingleButtons(factor.faktor_id, nilai) : ''}
                    
                    ${factor.type === 'category' ? `
                        ${CAN_EDIT ? `
                            <button type="button" class="btn btn-sm btn-warning ml-2 btn-edit-keterangan" 
                                    data-id="${factor.faktor_id}" 
                                    data-title="${escapeHtml(factor.title)}"                                
                                    data-keterangan="${escapeHtml(nilai?.keterangan || '')}">
                                <i class="fa fa-edit"></i>
                            </button>
                        ` : ''}
                        
                        <button type="button" class="btn btn-sm btn-primary ml-2 position-relative komentar-button"
                                data-faktor-id="${factor.faktor_id}">
                            <i class="fas fa-comment"></i>
                            <span id="notification-badge-${factor.faktor_id}" 
                                class="badge badge-danger notification-badge" 
                                style="display: none;">
                                0
                            </span>
                        </button>
                        
                        ${renderApprovalButton(factor.faktor_id, nilai)}
                    ` : ''}
                </div>
            </div>
            
            <div class="card-body">
                ${factor.type === 'category' ? renderCategoryChildren(factor) : ''}
            </div>
        </div>
        `;
        });

        container.html(html);
        attachEventHandlers();

        setTimeout(pollUnreadCounts, 500);
    }

    // ===== RENDER CHILDREN =====
    function renderCategoryChildren(factor) {
        if (!factor.children) return '';

        let html = '';
        factor.children.forEach((child, idx) => {
            const nilai = child.nilai;
            const hasData = nilai && nilai.penilaiankredit;

            let displayTitle = escapeHtml(child.title);
            displayTitle = displayTitle
                .replace(/\\n/g, '<br>')           // Handle escaped \n
                .replace(/\n/g, '<br>')            // Handle real newline
                .replace(/&lt;br&gt;/gi, '<br>')  // Handle escaped <br>
                .replace(/\r\n/g, '<br>')         // Handle Windows newline
                .replace(/\r/g, '<br>');          // Handle Mac newline

            html += `
        <div class="border-left border-success pl-4 mb-3">
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <h6 class="font-weight-bold">
                        ${idx + 1}. ${displayTitle}
                    </h6>
                </div>
                <div class="ml-3">
                    ${hasData ?
                    `<span class="badge badge-success">
                            <i class="fas fa-check"></i> Peringkat: ${nilai.penilaiankredit}
                        </span>` :
                    '<span class="badge badge-secondary">Belum dinilai</span>'
                }
                    
                    ${CAN_EDIT ? (hasData ?
                    `<button class="btn btn-sm btn-warning ml-2 btn-edit" 
                                    data-id="${child.faktor_id}"
                                    data-title="${escapeHtml(child.title)}"
                                    data-penilaiankredit="${nilai.penilaiankredit}"
                                    data-keterangan="${escapeHtml(nilai.keterangan || '')}">
                                <i class="fas fa-edit"></i>
                            </button>` :
                    `<button type="button" class="btn btn-sm btn-success ml-2 btn-add" 
                                    data-id="${child.faktor_id}"
                                    data-title="${escapeHtml(child.title)}">
                                <i class="fas fa-plus"></i>
                            </button>`) : ''
                }
                        <button type="button" class="btn btn-sm btn-primary ml-2 position-relative komentar-button"
                                data-faktor-id="${child.faktor_id}">
                            <i class="fas fa-comment"></i>
                            <span id="notification-badge-${child.faktor_id}" 
                                  class="badge badge-danger notification-badge" 
                                  style="display: none;">0</span>
                        </button>
                        ${renderApprovalButton(child.faktor_id, nilai)}
                </div>
            </div>
        </div>
        `;
        });

        return html;
    }

    // ===== RENDER APPROVAL BUTTON =====
    function renderApprovalButton(faktorId, nilai) {
        <?php if (($userInGroupAdmin ?? false) || ($userInGroupDireksi ?? false) || ($userInGroupDireksi2 ?? false)): ?>
            if (!nilai || !nilai.penilaiankredit) {
                return '<span class="text-muted ml-2">-</span>';
            }

            const isApproved = nilai.accdir2 == 1;
            const btnClass = isApproved ? 'btn-success' : 'btn-dark';
            const icon = isApproved ? 'check' : 'times';
            const action = isApproved ? 'unapprovedir2' : 'accdir2';

            return `
            <form action="${BASE_URL}/Likuiditaskpmr/${action}" method="POST" style="display: inline-block;" class="ml-2 form-approval">
                <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
                <input type="hidden" name="faktor1id" value="${faktorId}">
                <input type="hidden" name="kodebpr" value="${GLOBAL_KODEBPR}">
                <input type="hidden" name="periode_id" value="${GLOBAL_PERIODE_ID}">
                <button type="submit" class="btn btn-sm ${btnClass}">
                    <i class="fas fa-${icon}-circle"></i>
                </button>
            </form>
        `;
        <?php else: ?>
            return '';
        <?php endif; ?>
    }

    // ===== ATTACH EVENT HANDLERS =====
    function attachEventHandlers() {
        // Edit button
        $('.btn-edit').off('click').on('click', function () {
            const id = $(this).data('id');
            const title = $(this).data('title');
            const penilaiankredit = $(this).data('penilaiankredit');
            const keterangan = $(this).data('keterangan');

            $('#modalUbah #edit-faktor-id').val(id);
            $('#modalUbah #edit-parameterpenilaian').val(title);
            $('#modalUbah #edit-penilaiankredit').val(penilaiankredit);
            $('#modalUbah #edit-keterangan').val(keterangan);

            const config = generateRatingOptions(id, '#editRatingList');
            updateCatatanInfo(config, 'edit');

            setTimeout(() => {
                $('#editRatingList .list-group-item').each(function () {
                    if ($(this).data('value') == penilaiankredit) {
                        $(this).addClass('active');
                        $('#editSelectedValue').text('Nilai ' + penilaiankredit);
                    }
                });
            }, 100);

            $('#modalUbah').modal('show');
        });

        // Edit keterangan button
        $('.btn-edit-keterangan').off('click').on('click', function () {
            const id = $(this).data('id');
            const title = $(this).data('title');
            const keterangan = $(this).data('keterangan');

            $('#modalUbahkesimpulan #edit-faktor-id-kesimpulan').val(id);
            $('#modalUbahkesimpulan #edit-parameterpenilaian-kesimpulan').val(title);
            $('#modalUbahkesimpulan #edit-keterangan-kesimpulan').val(keterangan);
            $('#modalUbahkesimpulan #edit-penilaiankredit').remove();

            $('#modalUbahkesimpulan').modal('show');
        });

        $('form[action*="Likuiditaskpmr/ubahkesimpulan"]').off('submit').on('submit', function (e) {
            e.preventDefault();

            const form = $(this);
            const submitBtn = form.find('button[type="submit"]');
            const originalBtnText = submitBtn.html();

            const faktorId = form.find('[name="faktor1id"]').val();
            const keterangan = form.find('[name="keterangan"]').val();

            if (!faktorId) {
                showToast('Error', 'Faktor ID tidak ditemukan', 'error');
                return;
            }

            if (!keterangan.trim()) {
                showToast('Error', 'Keterangan harus diisi', 'error');
                return;
            }

            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...').prop('disabled', true);

            const formData = form.serializeArray();
            formData.push({
                name: CSRF_TOKEN_NAME,
                value: CSRF_HASH
            });

            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: $.param(formData),
                dataType: 'json',
                success: function (response) {
                    $('#modalUbahkesimpulan').modal('hide');
                    showToast('Berhasil', 'Keterangan berhasil diubah', 'success');
                    refreshFactorsData();
                    submitBtn.html(originalBtnText).prop('disabled', false);
                    form[0].reset();
                },
                error: function (xhr) {
                    submitBtn.html(originalBtnText).prop('disabled', false);
                    const errorMsg = xhr.responseJSON?.message || 'Gagal menyimpan keterangan';
                    showToast('Error', errorMsg, 'error');
                    console.error('Error:', xhr);
                }
            });
        });

        // Add button
        $('.btn-add').off('click').on('click', function () {
            const id = $(this).data('id');
            const title = $(this).data('title');

            $('#add-faktor-id').val(id);
            $('#add-parameterpenilaian').val(title);
            $('#add-penilaiankredit, #add-penjelasanpenilaian, #add-keterangan').val('');
            $('#addSelectedValue').text('Belum ada');

            const config = generateRatingOptions(id, '#addRatingList');
            updateCatatanInfo(config, 'add');

            $('#modaltambahNilai').modal('show');
        });

        // Form approval - AJAX submit
        $('.form-approval').off('submit').on('submit', function (e) {
            e.preventDefault();

            const form = $(this);
            const button = form.find('button');
            const originalHtml = button.html();

            button.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: form.serialize(),
                success: function () {
                    const isApproved = form.attr('action').includes('accdir2');
                    const newClass = isApproved ? 'btn-success' : 'btn-dark';
                    const newIcon = isApproved ? 'check' : 'times';
                    const newAction = isApproved ? 'unapprovedir2' : 'accdir2';

                    button.removeClass('btn-success btn-dark').addClass(newClass);
                    button.html(`<i class="fas fa-${newIcon}-circle"></i>`);
                    form.attr('action', form.attr('action').replace(/(accdir2|unapprovedir2)/, newAction));
                    button.prop('disabled', false);

                    showToast('Berhasil', 'Status approval diubah', 'success');
                },
                error: function () {
                    button.html(originalHtml).prop('disabled', false);
                    showToast('Error', 'Gagal mengubah status', 'error');
                }
            });
        });
    }

    $(document).on('click', '.komentar-button', function () {
        const faktorId = $(this).data('faktor-id');
        $('#comment-faktor-id').val(faktorId);

        console.log('🔵 Komentar button clicked for factor:', faktorId);

        // Load komentar
        $.ajax({
            url: `${BASE_URL}/Likuiditaskpmr/getKomentarByFaktorId/${faktorId}`,
            method: 'GET',
            data: {
                kodebpr: GLOBAL_KODEBPR,
                periode_id: GLOBAL_PERIODE_ID
            },
            success: function (response) {
                console.log('✓ Comments loaded:', response.length);
                const list = $('#komentarLamaList');
                list.html(response.length > 0 ?
                    response.map(k => `<li>${escapeHtml(k.komentar)} - (${escapeHtml(k.fullname)} - ${k.created_at})</li>`).join('') :
                    '<li>Tidak ada komentar.</li>');
            },
            error: function (xhr, status, error) {
                console.error('✗ Error loading comments:', error);
            }
        });

        // Mark as read DENGAN FORCE UPDATE
        $.ajax({
            url: `${BASE_URL}/Likuiditaskpmr/markUserCommentsAsRead`,
            method: 'POST',
            data: {
                faktor_id: faktorId,
                kodebpr: GLOBAL_KODEBPR,
                periode_id: GLOBAL_PERIODE_ID,
                '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
            },
            success: function (response) {
                console.log('✓ Mark as read response:', response);

                if (response.status === 'success') {
                    console.log(`✓ Marked ${response.marked_count} comments as read`);

                    // ✅ FORCE update badge ke 0
                    updateBadge(faktorId, 0);

                    console.log(`✓ Badge force updated to 0 for factor ${faktorId}`);
                } else {
                    console.warn('⚠ Mark as read failed:', response.message);
                }
            },
            error: function (xhr, status, error) {
                console.error('✗ Error marking as read:', error);
                console.error('Response:', xhr.responseText);
            }
        });

        $('#modaltambahKomentar').modal('show');
    });

    // ===== RATING LIST HANDLERS =====
    $(document).on('click', '#addRatingList .list-group-item', function (e) {
        e.preventDefault();
        $('#addRatingList .list-group-item').removeClass('active');
        $(this).addClass('active');
        handleRatingSelection($(this).data('value'), $(this).data('description'), 'add');
    });

    $(document).on('click', '#editRatingList .list-group-item', function (e) {
        e.preventDefault();
        $('#editRatingList .list-group-item').removeClass('active');
        $(this).addClass('active');
        handleRatingSelection($(this).data('value'), $(this).data('description'), 'edit');
    });

    const CSRF_TOKEN_NAME = '<?= csrf_token() ?>';
    const CSRF_HASH = '<?= csrf_hash() ?>';

    // ===== FORM SUBMISSIONS =====
    $(document).ready(function () {
        $('form[action*="Likuiditaskpmr/ubah"]').on('submit', function (e) {
            e.preventDefault();

            const form = $(this);
            const submitBtn = form.find('button[type="submit"]');
            const originalBtnText = submitBtn.html();

            const faktorId = form.find('[name="faktor1id"]').val();
            const penilaian = form.find('[name="penilaiankredit"]').val();
            const keterangan = form.find('[name="keterangan"]').val();

            if (!faktorId) {
                showToast('Error', 'Faktor ID tidak ditemukan', 'error');
                return;
            }

            if (!penilaian && faktorId != 102) {
                showToast('Error', 'Pilih tingkat risiko terlebih dahulu', 'error');
                return;
            }

            if (!keterangan.trim()) {
                showToast('Error', 'Keterangan harus diisi', 'error');
                return;
            }

            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...').prop('disabled', true);

            const formData = form.serializeArray();
            formData.push({
                name: CSRF_TOKEN_NAME,
                value: CSRF_HASH
            });

            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: $.param(formData),
                dataType: 'json',
                success: function (response) {
                    $('#modalUbah').modal('hide');
                    showToast('Berhasil', 'Data berhasil diubah', 'success');

                    // Update badge peringkat 13
                    if (response.peringkat13) {
                        updateBadgePeringkat13(response.peringkat13);
                    }

                    updateMainApprovalBadge(false);

                    refreshFactorsData();
                    submitBtn.html(originalBtnText).prop('disabled', false);
                    form[0].reset();
                },
                error: function (xhr) {
                    submitBtn.html(originalBtnText).prop('disabled', false);
                    const errorMsg = xhr.responseJSON?.message || 'Gagal menyimpan data';
                    showToast('Error', errorMsg, 'error');
                    console.error('Error:', xhr);
                }
            });
        });

        function updateBadgePeringkat13(peringkat) {
            const peringkatLabels = {
                1: 'Sangat Rendah',
                2: 'Rendah',
                3: 'Sedang',
                4: 'Tinggi',
                5: 'Sangat Tinggi'
            };

            function getBadgeClass(nilai) {
                const classes = {
                    1: 'badge-info',
                    2: 'badge-success',
                    3: 'badge-warning',
                    4: 'badge-danger',
                    5: 'badge-dark'
                };
                return classes[nilai] || 'badge-secondary';
            }

            const badgeHtml = `
        <span class="badge badge-lg ${getBadgeClass(peringkat)}" 
              style="font-size: 1.1em; padding: 0.5em 1em;">
            <i class="fas fa-check-circle"></i>
            Peringkat penilaian: ${peringkat}
            [${peringkatLabels[peringkat] || 'N/A'}]
        </span>
    `;

            // Find and replace the badge
            $('.d-flex.justify-content-between.align-items-center .text-center .mt-2:first').find('span.badge:first').replaceWith(badgeHtml);
        }

        $('form[action*="Likuiditaskpmr/ubahkesimpulan"]').on('submit', function (e) {
            e.preventDefault();

            const form = $(this);
            const submitBtn = form.find('button[type="submit"]');
            const originalBtnText = submitBtn.html();

            const faktorId = form.find('[name="faktor1id"]').val();
            const keterangan = form.find('[name="keterangan"]').val();

            console.log('Submit ubahkesimpulan:', { faktorId, keterangan });

            if (!faktorId) {
                showToast('Error', 'Faktor ID tidak ditemukan', 'error');
                return;
            }

            if (!keterangan.trim()) {
                showToast('Error', 'Keterangan harus diisi', 'error');
                return;
            }

            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...').prop('disabled', true);

            const formData = form.serializeArray();
            formData.push({
                name: CSRF_TOKEN_NAME,
                value: CSRF_HASH
            });

            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: $.param(formData),
                dataType: 'json',
                success: function (response) {
                    console.log('Success:', response);
                    $('#modalUbahkesimpulan').modal('hide');
                    showToast('Berhasil', 'Keterangan berhasil diubah', 'success');

                    updateMainApprovalBadge(false);

                    refreshFactorsData();
                    submitBtn.html(originalBtnText).prop('disabled', false);
                    form[0].reset();
                },
                error: function (xhr) {
                    console.error('Error:', xhr);
                    submitBtn.html(originalBtnText).prop('disabled', false);
                    const errorMsg = xhr.responseJSON?.message || 'Gagal menyimpan keterangan';
                    showToast('Error', errorMsg, 'error');
                }
            });
        });

        $('form[action*="Likuiditaskpmr/tambahNilai"]').on('submit', function (e) {
            e.preventDefault();

            const form = $(this);
            const submitBtn = form.find('button[type="submit"]');
            const originalBtnText = submitBtn.html();

            const faktorId = form.find('[name="faktor_id"]').val();
            const penilaian = form.find('[name="penilaiankredit"]').val();
            const keterangan = form.find('[name="keterangan"]').val();

            if (!faktorId) {
                showToast('Error', 'Faktor ID tidak ditemukan', 'error');
                return;
            }

            if (!penilaian) {
                showToast('Error', 'Pilih tingkat risiko terlebih dahulu', 'error');
                return;
            }

            if (!keterangan.trim()) {
                showToast('Error', 'Keterangan harus diisi', 'error');
                return;
            }

            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...').prop('disabled', true);

            const formData = form.serializeArray();
            formData.push({
                name: CSRF_TOKEN_NAME,
                value: CSRF_HASH
            });

            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: $.param(formData),
                dataType: 'json',
                success: function (response) {
                    $('#modaltambahNilai').modal('hide');
                    showToast('Berhasil', 'Nilai berhasil ditambahkan', 'success');
                    refreshFactorsData();
                    submitBtn.html(originalBtnText).prop('disabled', false);

                    updateMainApprovalBadge(false);

                    form[0].reset();
                    $('#addSelectedValue').text('Belum ada');
                },
                error: function (xhr) {
                    submitBtn.html(originalBtnText).prop('disabled', false);
                    const errorMsg = xhr.responseJSON?.message || 'Gagal menambahkan nilai';
                    showToast('Error', errorMsg, 'error');
                    console.error('Error:', xhr);
                }
            });
        });

        $('form[action*="Likuiditaskpmr/tambahKomentar"]').on('submit', function (e) {
            e.preventDefault();

            const form = $(this);
            const submitBtn = form.find('button[type="submit"]');
            const originalBtnText = submitBtn.html();
            const komentar = form.find('[name="komentar"]').val();

            if (!komentar.trim()) {
                showToast('Error', 'Komentar tidak boleh kosong', 'error');
                return;
            }

            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...').prop('disabled', true);

            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: form.serialize(),
                success: function () {
                    form.find('[name="komentar"]').val('');
                    const faktorId = form.find('[name="faktor_id"]').val();
                    loadKomentarList(faktorId);
                    showToast('Berhasil', 'Komentar berhasil ditambahkan', 'success');
                    submitBtn.html(originalBtnText).prop('disabled', false);

                    // IMPORTANT: Poll untuk update badge user lain
                    pollUnreadCounts();
                },
                error: function () {
                    submitBtn.html(originalBtnText).prop('disabled', false);
                    showToast('Error', 'Gagal menambahkan komentar', 'error');
                }
            });
        });
    });

    function updateMainApprovalBadge(allApproved, approvedAt = null) {
        const badge = $('#approval-badge-main');

        if (!badge.length) return;

        badge.fadeOut(150, function () {
            if (allApproved) {
                badge.removeClass('badge-secondary badge-warning')
                    .addClass('badge-success');

                const dateStr = approvedAt ? formatDateTime(approvedAt) : '-';
                badge.html(`
                Disetujui oleh <strong>Direktur Utama</strong><br>
                ${dateStr}
            `);
            } else {
                badge.removeClass('badge-success badge-warning')
                    .addClass('badge-secondary');

                badge.html(`
                Belum Disetujui Seluruhnya<br>Oleh Direktur Utama
            `);
            }

            badge.fadeIn(150);
        });
    }

    function formatDateTime(dateString) {
        if (!dateString) return '-';

        const date = new Date(dateString);
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        const seconds = String(date.getSeconds()).padStart(2, '0');

        return `${day}/${month}/${year} ${hours}:${minutes}:${seconds}`;
    }

    $('a[href*="approveSemua"]').on('click', function (e) {
        e.preventDefault();

        if (!confirm('Apakah Anda yakin ingin melakukan approval?')) {
            return;
        }

        const button = $(this);
        const originalHtml = button.html();

        button.html('<i class="fas fa-spinner fa-spin"></i> Processing...').addClass('disabled');

        $.ajax({
            url: $(this).attr('href'),
            method: 'GET',
            success: function (response) {
                showToast('Berhasil', 'Semua data berhasil disetujui', 'success');

                updateMainApprovalBadge(true, new Date().toISOString());

                refreshFactorsData();

                button.html(originalHtml).removeClass('disabled');
            },
            error: function () {
                showToast('Error', 'Gagal melakukan approval', 'error');
                button.html(originalHtml).removeClass('disabled');
            }
        });
    });

    $('a[href*="unapproveSemua"]').on('click', function (e) {
        e.preventDefault();

        if (!confirm('Batalkan semua approval?')) {
            return;
        }

        const button = $(this);
        const originalHtml = button.html();

        button.html('<i class="fas fa-spinner fa-spin"></i> Processing...').addClass('disabled');

        $.ajax({
            url: $(this).attr('href'),
            method: 'GET',
            success: function (response) {
                showToast('Berhasil', 'Semua approval berhasil dibatalkan', 'success');

                updateMainApprovalBadge(false);

                refreshFactorsData();

                button.html(originalHtml).removeClass('disabled');
            },
            error: function () {
                showToast('Error', 'Gagal membatalkan approval', 'error');
                button.html(originalHtml).removeClass('disabled');
            }
        });
    });

    function refreshFactorsData() {
        $.ajax({
            url: BASE_URL + '/Likuiditaskpmr/getFactorsData',
            method: 'GET',
            success: function (response) {
                if (response.status === 'success') {
                    renderFactors(response.data);
                }
            }
        });
    }

    function loadKomentarList(faktorId) {
        $.ajax({
            url: `${BASE_URL}/Likuiditaskpmr/getKomentarByFaktorId/${faktorId}`,
            method: 'GET',
            data: {
                kodebpr: GLOBAL_KODEBPR,
                periode_id: GLOBAL_PERIODE_ID
            },
            success: function (response) {
                const list = $('#komentarLamaList');
                list.html(response.length > 0 ?
                    response.map(k => `<li>${escapeHtml(k.komentar)} - (${escapeHtml(k.fullname)} - ${k.created_at})</li>`).join('') :
                    '<li>Tidak ada komentar.</li>');
            }
        });
    }

    function showToast(title, message, type) {
        const bgColor = type === 'success' ? 'bg-success' : 'bg-danger';
        const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';

        const toast = $(`
        <div class="toast-notification ${bgColor}">
            <i class="fas ${icon}"></i>
            <div>
                <strong>${title}</strong><br>
                <span>${message}</span>
            </div>
        </div>
    `);

        $('body').append(toast);

        setTimeout(() => toast.addClass('show'), 10);

        setTimeout(() => {
            toast.removeClass('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    $(document).on('mousedown', 'button, a.btn', function () {
        $(this).css('transform', 'scale(0.95)');
    });

    $(document).on('mouseup mouseleave', 'button, a.btn', function () {
        $(this).css('transform', 'scale(1)');
    });
</script>

<style>
    /* ===== CSS Variables ===== */
    :root {
        --primary-color: #10598A;
        --success-color: #28a745;
        --danger-color: #dc3545;
        --dark-color: #343a40;
        --secondary-color: #6c757d;
        --light-bg: #f8f9fa;
        --white: #ffffff;
        --border-color: #dee2e6;
        --box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        --box-shadow-hover: 0 4px 8px rgba(0, 0, 0, 0.2);
        --border-radius: 8px;
        --transition: all 0.2s ease-in-out;
    }

    body {
        font-family: 'Arial', sans-serif;
        background-color: var(--light-bg);
    }

    .container-fluid {
        margin-top: 30px;
    }

    .skeleton {
        animation: skeleton-loading 1.2s ease-in-out infinite;
        background: linear-gradient(90deg, #f0f0f0 0%, #e8e8e8 20%, #e8e8e8 40%, #f0f0f0 100%);
        background-size: 200% 100%;
    }

    @keyframes skeleton-loading {
        0% {
            background-position: 200% 0;
        }

        100% {
            background-position: -200% 0;
        }
    }

    .skeleton-text {
        height: 20px;
        border-radius: 4px;
        margin-bottom: 12px;
    }

    .toast-notification {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        padding: 15px 20px;
        border-radius: 8px;
        color: white;
        display: flex;
        align-items: center;
        gap: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        transform: translateX(400px);
        opacity: 0;
        transition: transform 0.3s ease, opacity 0.3s ease;
        min-width: 300px;
    }

    .toast-notification.show {
        transform: translateX(0);
        opacity: 1;
    }

    .toast-notification i {
        font-size: 24px;
    }

    .toast-notification div {
        flex: 1;
    }

    button,
    .btn {
        transition: transform 0.1s ease, box-shadow 0.1s ease;
    }

    button:active,
    .btn:active {
        transform: scale(0.95) !important;
    }

    button:hover,
    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .card {
        margin-bottom: 20px;
        border-radius: 10px;
        box-shadow: var(--box-shadow);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
    }

    .list-group-item {
        transition: background-color 0.15s ease, transform 0.1s ease;
    }

    .list-group-item:hover {
        transform: translateX(3px);
        cursor: pointer;
    }

    .list-group-item.active {
        background-color: #007bff !important;
        border-color: #007bff !important;
        color: white !important;
        animation: pulse 0.3s ease;
    }

    @keyframes pulse {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.02);
        }
    }

    /* Styling lainnya tetap sama */
    .badge {
        font-size: 14px;
        padding: 5px 10px;
        border-radius: 20px;
    }

    .btn-sm {
        padding: 5px 10px;
        font-size: 12px;
    }

    .modal-header {
        background-color: var(--primary-color);
        color: var(--white);
        font-size: 18px;
        padding: 15px;
    }

    .kpmr-btn {
        background-color: #10598A;
        color: #fff;
        font-weight: 600;
        padding: 8px 20px;
        border-radius: 6px;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .kpmr-btn:hover {
        background-color: #0d4a73;
        transform: translateY(-2px);
        color: #fff;
    }

    .button-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        margin: 15px 0;
    }

    .approval-btn {
        flex: 1;
        max-width: 50%;
        padding: 8px 13px;
        font-size: 0.95em;
        font-weight: 500;
        border-radius: 28px;
        text-align: center;
        text-decoration: none;
        white-space: nowrap;
        box-shadow: var(--box-shadow);
        transition: var(--transition);
    }

    .approval-btn-approve {
        background-color: var(--success-color);
        border-color: var(--success-color);
        color: var(--white);
    }

    .approval-btn-approve:hover {
        background-color: #218838;
        border-color: #1e7e34;
        transform: translateY(-1px);
        box-shadow: var(--box-shadow-hover);
    }

    .approval-btn-reject {
        background-color: var(--danger-color);
        border-color: var(--danger-color);
        color: var(--white);
    }

    .approval-btn-reject:hover {
        background-color: #c82333;
        border-color: #bd2130;
        transform: translateY(-1px);
        box-shadow: var(--box-shadow-hover);
    }

    .disabled-btn {
        opacity: 0.5;
        pointer-events: none;
    }

    .approval-badge-container {
        text-align: center;
        margin-bottom: 6px;
    }

    .approval-badge {
        background-color: var(--dark-color);
        color: var(--white);
        font-size: 0.8em;
        font-weight: 500;
        padding: 5px 12px;
        border-radius: 5px;
        display: inline-block;
        box-shadow: var(--box-shadow);
        margin-bottom: 7px;
    }

    .approval-buttons-container {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 9px;
    }

    #approval-badge-main {
        transition: all 0.3s ease;
    }

    #approval-badge-main.badge-success {
        animation: successBounce 0.5s ease;
    }

    #approval-badge-main.badge-secondary {
        animation: fadeIn 0.3s ease;
    }

    @keyframes successBounce {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.05);
        }
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
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
        transition: 0.2s;
    }

    .btn-close:hover {
        background: transparent transform: scale(1.1);
    }
</style>