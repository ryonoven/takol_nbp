<?php
$peringkat13 = null;
$keterangan13 = '';

if (isset($nilai13) && is_array($nilai13)) {
    $peringkat13 = $nilai13['penilaiankredit'] ?? null;
    $keterangan13 = $nilai13['keterangan'] ?? '';
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
$subkategori = 'KREDITINHEREN';
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
    <button type="button" class="close" data-bs-dismiss="alert">&times;</button>
</div>
<div class="container-fluid">
    <?php if (session()->get('message')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <button type="button" class="close" data-bs-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <strong><?= session()->getFlashdata('message'); ?></strong>
        </div>
    <?php endif; ?>

    <div class="card card-body">
        <div class="table-vertical">
            <div class="button-bar">
                <a href="Showprofilresiko" class="kpmr-btn icon-link icon-link-hover" id="btn-laporan">🡸 Kembali</a>               
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
                <div class="text-center flex-grow-1">
                    <div class="text-center">
                        <h4 class="mb-0">
                            <i class="fas fa-info-circle text-primary"></i>
                            <strong>Penilaian Profil Risiko</strong>
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
                                <button type="button" class="btn btn-sm btn-outline-primary ml-2" data-bs-toggle="collapse"
                                    data-bs-target="#editNilai14">
                                    <i class="fa fa-caret-down"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div id="editNilai14" class="collapse">
                <div class="p-3 border-top">
                    <?php if (!empty($nilai14)): ?>

                        <form action="<?= base_url('Risikokredit/simpanNilai14') ?>" method="post">
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
                        <form action="<?= base_url('Risikokredit/simpanNilai14') ?>" method="post">
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
                    <strong>A. Kertas Kerja Inheren Kredit</strong>
                </div>                

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

                <div id="factors-container" style="display: none;"></div>                
            </div>

            <div class="card mt-3">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-clipboard-list"></i> Kesimpulan Penilaian Risiko Kepatuhan KPMR
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
                                    <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="collapse"
                                        data-bs-target="#editKesimpulan13">
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
                                        <label for="keterangan_edit"><strong>Edit Keterangan Kesimpulan:</strong></label>
                                        <textarea class="form-control" name="keterangan" id="keterangan_edit" rows="5"
                                            required><?= esc($kesimpulan13['keterangan']) ?></textarea>
                                    </div>
                                    <div class="d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-secondary" data-bs-toggle="collapse"
                                            data-bs-target="#editKesimpulan13">
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
                                    <label for="keterangan_new"><strong>Keterangan Kesimpulan:</strong></label>
                                    <textarea class="form-control" name="keterangan" id="keterangan_new" rows="5"
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
                                <i class="fas fa-info-circle"></i>
                                Kesimpulan belum diisi oleh Tim Penilaian Risiko.
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- <div class="d-flex justify-content-center gap-2">
                <a href="<?= base_url('Risikokredit/exporttxtrisikokredit'); ?>" class="btn btn-secondary btn-sm">
                    <i class="fa fa-file-alt"></i> Export .txt
                </a>
            </div>

            <a href="<?= base_url('Risikokredit/viewPDFGabunganPreview?type=kredit') ?>" class="btn btn-danger"
                target="_blank">
                <i class="fas fa-file-pdf"></i> Export Kredit
            </a>

            <a href="<?= base_url('Risikokredit/viewPDFGabunganPreview?type=operasional') ?>" class="btn btn-warning"
                target="_blank">
                <i class="fas fa-file-pdf"></i> Export Operasional
            </a>

            <a href="<?= base_url('Risikokredit/viewPDFGabunganPreview?type=kepatuhan') ?>" class="btn btn-info"
                target="_blank">
                <i class="fas fa-file-pdf"></i> Export Kepatuhan
            </a>

            <a href="<?= base_url('Risikokredit/viewPDFGabunganPreview?type=likuiditas') ?>" class="btn btn-success"
                target="_blank">
                <i class="fas fa-file-pdf"></i> Export Likuiditas
            </a>

            <a href="<?= base_url('Risikokredit/viewPDFGabunganPreview') ?>" class="btn btn-primary" target="_blank">
                <i class="fas fa-file-pdf"></i> Export Semua Risiko
            </a>

            <button id="btnExportAllPDF" class="btn btn-success btn-lg w-100 mb-3">
                <i class="fas fa-download"></i> Download Semua PDF dalam ZIP
            </button> -->

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
                                    <a href="<?= base_url('Risikokredit/approveSemua') ?>"
                                        class="btn approval-btn approval-btn-approve <?= !$canApprove ? 'disabled-btn' : '' ?>"
                                        onclick="return <?= $canApprove ? "confirm('Apakah Anda yakin ingin melakukan approval?')" : 'false' ?>;">
                                        Approve
                                    </a>
                                    <a href="<?= base_url('Risikokredit/unapproveSemua') ?>"
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
                <form action="<?= base_url('Risikokredit/ubah'); ?>" method="post">
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
                <form action="<?= base_url('Risikokredit/ubahkesimpulan'); ?>" method="post">
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

<!-- Modal Tambah Nilai -->
<div class="modal fade" id="modaltambahNilai">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="<?= base_url('Risikokredit/tambahNilai'); ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Nilai Faktor</h5>
                    <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="faktor_id" id="add-faktor-id">
                    <input type="hidden" name="fullname" value="<?= esc($fullname ?? '') ?>">

                    <div class="mb-3">
                        <label for="add-parameterpenilaian"><strong>Parameter penilaian risiko:</strong></label>
                        <textarea class="form-control" id="add-parameterpenilaian" style="height: 100px"
                            readonly></textarea>
                    </div>

                    <!-- <div class="mb-3">
                        <label for="add-rasiokredit"><strong>Rasio:</strong></label>
                        <input type="text" class="form-control" name="rasiokredit" id="add-rasiokredit"
                            placeholder="Masukkan rasio..." required>
                        <small class="text-muted"><strong>Note:</strong> Pemisah angka pakai titik (contoh:
                            12.31)</small>
                    </div> -->

                    <label><strong>Pilih Tingkat Risiko:</strong></label>
                    <div class="alert alert-info mb-2" id="add-catatan-info" style="display: none; font-size: 0.9em;">
                        <i class="fas fa-info-circle"></i> <span id="add-catatan-text"></span>
                    </div>
                    <div class="list-group mb-3" id="addRatingList">
                        <!-- Akan diisi dinamis dengan JavaScript -->
                    </div>
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

<!-- Modal Komentar -->
<div class="modal fade" id="modaltambahKomentar">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= base_url('Risikokredit/tambahKomentar'); ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Komentar Direksi dan Dewan Komisaris</h5>
                    <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="faktor_id" id="comment-faktor-id">
                    <input type="hidden" name="kodebpr" value="<?= $kodebpr ?>">

                    <div class="form-group">
                        <label>Komentar Saat Ini:</label>
                        <ul id="komentarLamaList" style="list-style-type: none; padding-left: 0;">
                            <li>Memuat komentar...</li>
                        </ul>
                    </div>

                    <?php if ($userInGroupAdmin || $userInGroupDekom || $userInGroupDireksi || $userInGroupPE || $userInGroupDekom2 || $userInGroupDireksi2): ?>
                        <input type="hidden" name="fullname" value="<?= esc($fullname) ?>">
                        <div class="form-group">
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
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>

<script>
    // ===== GLOBAL CONSTANTS =====
    const GLOBAL_KODEBPR = '<?= $kodebpr ?? '' ?>';
    const GLOBAL_PERIODE_ID = '<?= $activePeriodeId ?? '' ?>';
    const GLOBAL_USER_ID = '<?= $currentUserId ?? '' ?>';
    const BASE_URL = '<?= base_url() ?>';
    const PENILAIAN_CONFIG = <?= json_encode($penilaianConfig ?? []) ?>;
    const CSRF_TOKEN_NAME = '<?= csrf_token() ?>';
    const CSRF_HASH = '<?= csrf_hash() ?>';

    const USER_IN_GROUP_ADMIN = <?= json_encode($userInGroupAdmin ?? false) ?>;
    const USER_IN_GROUP_PE = <?= json_encode($userInGroupPE ?? false) ?>;
    const CAN_EDIT = USER_IN_GROUP_ADMIN || USER_IN_GROUP_PE;

    document.addEventListener('DOMContentLoaded', function () {
        const btnExport = document.getElementById('btnExportAllPDF');

        if (btnExport) {
            btnExport.addEventListener('click', function () {
                const btn = this;
                const originalHTML = btn.innerHTML;

                // Disable button dan tampilkan loading
                btn.disabled = true;
                btn.classList.add('processing');
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';

                // Buka window baru untuk export
                const exportWindow = window.open(
                    '<?= base_url('Risikokredit/exportAllPDFToZipView') ?>',
                    'ExportPDFWindow',
                    'width=800,height=600,scrollbars=yes,resizable=yes'
                );

                // Cek apakah popup berhasil dibuka
                if (!exportWindow || exportWindow.closed || typeof exportWindow.closed === 'undefined') {
                    alert('Pop-up blocker terdeteksi!\n\nSilakan izinkan pop-up untuk website ini dan coba lagi.');
                }

                // Reset button setelah 3 detik
                setTimeout(() => {
                    btn.disabled = false;
                    btn.classList.remove('processing');
                    btn.innerHTML = originalHTML;
                }, 3000);
            });
        }
    });

    // ===== UTILITY FUNCTIONS =====
    const formatCurrency = (value) => new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value);

    const getInputValue = (id) => {
        const el = document.getElementById(id);
        if (!el) return 0;

        // Hilangkan format rupiah sebelum parsing
        let value = el.value;
        if (typeof value === 'string') {
            value = unformatRupiah(value);
        }

        return parseFloat(value) || 0;
    };

    const setHiddenValue = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.value = value.toFixed(2);
    };

    const updateDisplay = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    };

    const escapeHtml = (text) => {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    };

    const formatDateTime = (dateString) => {
        if (!dateString) return '-';
        const date = new Date(dateString);
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        const seconds = String(date.getSeconds()).padStart(2, '0');
        return `${day}/${month}/${year} ${hours}:${minutes}:${seconds}`;
    };

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

    // ===== RATING OPTIONS =====
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

    function formatRupiahInput(input) {
        let value = input.value.replace(/[^,\d]/g, '');

        if (value) {
            let split = value.split(',');
            let sisa = split[0].length % 3;
            let rupiah = split[0].substr(0, sisa);
            let ribuan = split[0].substr(sisa).match(/\d{3}/gi);

            if (ribuan) {
                let separator = sisa ? '.' : '';
                rupiah += separator + ribuan.join('.');
            }

            rupiah = split[1] !== undefined ? rupiah + ',' + split[1] : rupiah;
            input.value = 'Rp ' + rupiah;
        } else {
            input.value = '';
        }
    }

    function unformatRupiah(value) {
        return value.replace(/[^,\d]/g, '').replace(/\./g, '').replace(',', '.');
    }

    function setupCurrencyInput(inputId) {
        const input = document.getElementById(inputId);
        if (!input) return;

        // Format saat input
        input.addEventListener('input', function (e) {
            formatRupiahInput(this);
        });

        // Format saat paste
        input.addEventListener('paste', function (e) {
            setTimeout(() => formatRupiahInput(this), 0);
        });

        // Hapus format saat focus untuk edit lebih mudah
        input.addEventListener('focus', function () {
            if (this.value.startsWith('Rp ')) {
                this.dataset.formatted = this.value;
                this.value = unformatRupiah(this.value);
            }
        });

        // Format kembali saat blur
        input.addEventListener('blur', function () {
            if (this.value) {
                formatRupiahInput(this);
            }
        });
    }

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

    function handleRatingSelection(value, description, prefix) {
        $(`#${prefix}-penilaiankredit`).val(value);
        $(`#${prefix}SelectedValue`).text('Nilai ' + value);
        $(`#${prefix}-penjelasanpenilaian`).val(description);
    }

    // ===== KALKULATOR 1: KOMPOSISI PORTOFOLIO ASET =====
    function calculateRiskKalkulator() {
        const aba = getInputValue('aba');
        const kydbank = getInputValue('kydbank');
        const kydpihak3 = getInputValue('kydpihak3');
        const totalaset = getInputValue('totalaset');
        const total25debitur = getInputValue('total25debitur');
        const perdagangan = getInputValue('perdagangan');
        const jasa = getInputValue('jasa');
        const konsumsirumah = getInputValue('konsumsirumah');

        const kydgross = kydbank + kydpihak3;
        const asetproduktif = aba + kydgross;
        const rasioasetproduktif = totalaset > 0 ? (asetproduktif / totalaset) * 100 : 0;
        const rasiokreditdiberikan = asetproduktif > 0 ? (kydgross / asetproduktif) * 100 : 0;
        const rasio25debitur = kydgross > 0 ? (total25debitur / kydgross) * 100 : 0;
        const sektorekonomi = perdagangan + jasa + konsumsirumah;
        const rasioekonomi = kydgross > 0 ? (sektorekonomi / kydgross) * 100 : 0;

        // ✅ PERBAIKAN: Simpan LANGSUNG ke _submit dan _hidden (konsisten dengan kode pertama)
        setHiddenValue('kydgross_hidden', kydgross);
        setHiddenValue('asetproduktif_hidden', asetproduktif);
        setHiddenValue('sektorekonomi_hidden', sektorekonomi);

        // Yang dipakai untuk insert - simpan ke _submit
        setHiddenValue('rasioasetproduktif_submit', rasioasetproduktif);
        setHiddenValue('rasiokreditdiberikan_submit', rasiokreditdiberikan);
        setHiddenValue('rasio25debitur_submit', rasio25debitur);
        setHiddenValue('rasioekonomi_submit', rasioekonomi);

        const btnInsertRasio = document.getElementById('btnInsertRasio');
        if (btnInsertRasio && (aba > 0 || kydbank > 0 || kydpihak3 > 0)) {
            btnInsertRasio.style.display = 'block';
        }

        const detailResult = document.getElementById('detailResult');
        if (detailResult) detailResult.style.display = 'block';

        updateDisplay('asetproduktif', formatCurrency(asetproduktif));
        updateDisplay('kydgross', formatCurrency(kydgross));
        updateDisplay('totalaset_display', formatCurrency(totalaset));
        updateDisplay('rasioasetproduktif', rasioasetproduktif.toFixed(2) + '%');
        updateDisplay('rasiokreditdiberikan', rasiokreditdiberikan.toFixed(2) + '%');
        updateDisplay('rasio25debitur', rasio25debitur.toFixed(2) + '%');
        updateDisplay('sektorekonomi', formatCurrency(sektorekonomi));
        updateDisplay('rasioekonomi', rasioekonomi.toFixed(2) + '%');

        const resultDiv = document.getElementById('riskResult');
        if (resultDiv) {
            resultDiv.innerHTML = `
        <div style="font-size: 18px; margin-bottom: 10px; color: #28a745;">✓ Perhitungan Selesai</div>
        <div style="font-size: 14px;">Silakan lihat detail analisis di bawah</div>`;
            resultDiv.style.display = 'block';
        }
    }

    // ===== KALKULATOR 2: KUALITAS ASET =====
    function calculateRiskKalkulator2() {
        const abanpl = getInputValue('abanpl');
        const kydnpl3 = getInputValue('kydnpl3');
        const kydnpl4 = getInputValue('kydnpl4');
        const kydnpl5 = getInputValue('kydnpl5');
        const kreditdpk2 = getInputValue('kreditdpk2');
        const kreditbermasalah = getInputValue('kreditbermasalah');
        const kreditrestruktur1 = getInputValue('kreditrestruktur1');

        const asetproduktif = getInputValue('asetproduktif_hidden') ||
            parseFloat('<?= $kalkulatorData["asetproduktif"] ?? 0 ?>');
        const kydgross = getInputValue('kydgross_hidden') ||
            parseFloat('<?= $kalkulatorData["kydgross"] ?? 0 ?>');

        if (asetproduktif <= 0 || kydgross <= 0) {
            alert('Silakan isi Kalkulator Komposisi Portofolio Aset terlebih dahulu!');
            return;
        }

        const kydkoleknpl = kydnpl3 + kydnpl4 + kydnpl5;
        const asetproduktifbermasalah = abanpl + kydkoleknpl;
        const kreditkualitasrendah = kreditdpk2 + kydkoleknpl + kreditrestruktur1;
        const rasioasetproduktifbermasalah = asetproduktif > 0 ? (asetproduktifbermasalah / asetproduktif) * 100 : 0;
        const rasiokreditbermasalah = kydgross > 0 ? (kreditbermasalah / kydgross) * 100 : 0;
        const rasiokreditkualitasrendah = kydgross > 0 ? (kreditkualitasrendah / kydgross) * 100 : 0;

        // ✅ PERBAIKAN: Simpan nilai ke hidden input
        setHiddenValue('kydkoleknpl_hidden', kydkoleknpl);
        setHiddenValue('asetproduktifbermasalah_hidden', asetproduktifbermasalah);
        setHiddenValue('kreditkualitasrendah_hidden', kreditkualitasrendah);

        // Yang dipakai untuk insert - simpan ke _submit
        setHiddenValue('rasioasetproduktifbermasalah_submit', rasioasetproduktifbermasalah);
        setHiddenValue('rasiokreditbermasalah_submit', rasiokreditbermasalah);
        setHiddenValue('rasiokreditkualitasrendah_submit', rasiokreditkualitasrendah);

        const btnInsertRasio2 = document.getElementById('btnInsertRasio2');
        if (btnInsertRasio2 && (abanpl > 0 || kydnpl3 > 0 || kydnpl4 > 0 || kydnpl5 > 0)) {
            btnInsertRasio2.style.display = 'block';
        }

        const detailResult2 = document.getElementById('detailResult2');
        if (detailResult2) detailResult2.style.display = 'block';

        updateDisplay('kydkoleknpl_display', formatCurrency(kydkoleknpl));
        updateDisplay('asetproduktifbermasalah_display', formatCurrency(asetproduktifbermasalah));
        updateDisplay('asetproduktif_display2', formatCurrency(asetproduktif));
        updateDisplay('rasioasetproduktifbermasalah_display', rasioasetproduktifbermasalah.toFixed(2) + '%');
        updateDisplay('rasiokreditbermasalah_display', rasiokreditbermasalah.toFixed(2) + '%');
        updateDisplay('rasiokreditkualitasrendah_display', rasiokreditkualitasrendah.toFixed(2) + '%');

        const resultDiv = document.getElementById('riskResult2');
        if (resultDiv) {
            resultDiv.innerHTML = `
        <div style="font-size: 18px; margin-bottom: 10px; color: #28a745;">✓ Perhitungan Selesai</div>
        <div style="font-size: 14px;">Silakan lihat detail analisis di bawah</div>`;
            resultDiv.style.display = 'block';
        }
    }

    // ===== INSERT RASIO =====
    function insertRasioAjax(url, data, btnId, successMsg) {
        if (Object.values(data).every(v => !v)) {
            showToast('Error', 'Tidak ada data rasio untuk dimasukkan. Silakan isi dan hitung terlebih dahulu.', 'error');
            return;
        }

        const btn = $(btnId);
        const originalText = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin"></i> Memproses...').prop('disabled', true);

        $.ajax({
            url: BASE_URL + url,
            method: 'POST',
            data: { ...data, [CSRF_TOKEN_NAME]: CSRF_HASH },
            dataType: 'json',
            success: (result) => {
                btn.html(originalText).prop('disabled', false);
                if (result.success) {
                    showToast('Berhasil', result.message, 'success');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showToast('Error', result.message || 'Gagal memasukkan rasio', 'error');
                }
            },
            error: (xhr) => {
                btn.html(originalText).prop('disabled', false);
                showToast('Error', `Terjadi kesalahan (${xhr.status}). Silakan coba lagi.`, 'error');
            }
        });
    }

    function insertRasioToKertasKerja() {
        // Ambil semua data input dari form
        const aba = getInputValue('aba');
        const kydbank = getInputValue('kydbank');
        const kydpihak3 = getInputValue('kydpihak3');
        const totalaset = getInputValue('totalaset');
        const total25debitur = getInputValue('total25debitur');
        const perdagangan = getInputValue('perdagangan');
        const jasa = getInputValue('jasa');
        const konsumsirumah = getInputValue('konsumsirumah');

        // Ambil rasio yang sudah dihitung
        const rasioasetproduktif = $('#rasioasetproduktif_submit').val();
        const rasiokreditdiberikan = $('#rasiokreditdiberikan_submit').val();
        const rasio25debitur = $('#rasio25debitur_submit').val();
        const rasioekonomi = $('#rasioekonomi_submit').val();

        // Debug log
        console.log('=== DEBUG INSERT RASIO ===');
        console.log('rasioasetproduktif:', rasioasetproduktif);
        console.log('rasiokreditdiberikan:', rasiokreditdiberikan);
        console.log('rasio25debitur:', rasio25debitur);
        console.log('rasioekonomi:', rasioekonomi);

        // Validasi
        if (!rasioasetproduktif && !rasiokreditdiberikan && !rasio25debitur && !rasioekonomi) {
            showToast('Error', 'Tidak ada data rasio untuk dimasukkan. Silakan hitung terlebih dahulu.', 'error');
            return;
        }

        const btn = $('#btnInsertRasio');
        const originalText = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin"></i> Memproses...').prop('disabled', true);

        $.ajax({
            url: BASE_URL + '/Risikokredit/insertRasioToKertasKerja',
            method: 'POST',
            data: {
                // Data input kalkulator
                aba: aba || 0,
                kydbank: kydbank || 0,
                kydpihak3: kydpihak3 || 0,
                totalaset: totalaset || 0,
                total25debitur: total25debitur || 0,
                perdagangan: perdagangan || 0,
                jasa: jasa || 0,
                konsumsirumah: konsumsirumah || 0,
                // Rasio hasil perhitungan
                rasioasetproduktif: rasioasetproduktif || '',
                rasiokreditdiberikan: rasiokreditdiberikan || '',
                rasio25debitur: rasio25debitur || '',
                rasioekonomi: rasioekonomi || '',
                [CSRF_TOKEN_NAME]: CSRF_HASH
            },
            dataType: 'json',
            success: (result) => {
                btn.html(originalText).prop('disabled', false);
                console.log('Response:', result);

                if (result.success) {
                    showToast('Berhasil', result.message, 'success');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showToast('Error', result.message || 'Gagal memasukkan rasio', 'error');
                }
            },
            error: (xhr) => {
                btn.html(originalText).prop('disabled', false);
                console.error('AJAX Error:', xhr);
                showToast('Error', `Terjadi kesalahan (${xhr.status}). Silakan coba lagi.`, 'error');
            }
        });
    }

    function insertRasioKualitasAsetToKertasKerja() {
        // Ambil semua data input
        const abanpl = getInputValue('abanpl');
        const kydnpl3 = getInputValue('kydnpl3');
        const kydnpl4 = getInputValue('kydnpl4');
        const kydnpl5 = getInputValue('kydnpl5');
        const kreditdpk2 = getInputValue('kreditdpk2');
        const kreditbermasalah = getInputValue('kreditbermasalah');
        const kreditrestruktur1 = getInputValue('kreditrestruktur1');

        // Ambil rasio yang sudah dihitung
        const rasioasetproduktifbermasalah = $('#rasioasetproduktifbermasalah_submit').val();
        const rasiokreditbermasalah = $('#rasiokreditbermasalah_submit').val();
        const rasiokreditkualitasrendah = $('#rasiokreditkualitasrendah_submit').val();

        console.log('=== DEBUG INSERT RASIO KUALITAS ASET ===');
        console.log('rasioasetproduktifbermasalah:', rasioasetproduktifbermasalah);
        console.log('rasiokreditbermasalah:', rasiokreditbermasalah);
        console.log('rasiokreditkualitasrendah:', rasiokreditkualitasrendah);

        if (!rasioasetproduktifbermasalah && !rasiokreditbermasalah && !rasiokreditkualitasrendah) {
            showToast('Error', 'Tidak ada data rasio untuk dimasukkan. Silakan hitung terlebih dahulu.', 'error');
            return;
        }

        const btn = $('#btnInsertRasio2');
        const originalText = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin"></i> Memproses...').prop('disabled', true);

        $.ajax({
            url: BASE_URL + '/Risikokredit/insertRasioKualitasAsetToKertasKerja',
            method: 'POST',
            data: {
                abanpl: abanpl || 0,
                kydnpl3: kydnpl3 || 0,
                kydnpl4: kydnpl4 || 0,
                kydnpl5: kydnpl5 || 0,
                kreditdpk2: kreditdpk2 || 0,
                kreditbermasalah: kreditbermasalah || 0,
                kreditrestruktur1: kreditrestruktur1 || 0,
                rasioasetproduktifbermasalah: rasioasetproduktifbermasalah || '',
                rasiokreditbermasalah: rasiokreditbermasalah || '',
                rasiokreditkualitasrendah: rasiokreditkualitasrendah || '',
                [CSRF_TOKEN_NAME]: CSRF_HASH
            },
            dataType: 'json',
            success: (result) => {
                btn.html(originalText).prop('disabled', false);
                console.log('Response:', result);

                if (result.success) {
                    showToast('Berhasil', result.message, 'success');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showToast('Error', result.message || 'Gagal memasukkan rasio', 'error');
                }
            },
            error: (xhr) => {
                btn.html(originalText).prop('disabled', false);
                console.error('AJAX Error:', xhr);
                showToast('Error', `Terjadi kesalahan (${xhr.status}). Silakan coba lagi.`, 'error');
            }
        });
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
                    
                    ${factor.type === 'single' ? renderSingleButtons(factor.faktor_id, factor.title, nilai) : ''}
                    
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

    function renderSingleButtons(faktorId, title, nilai) {
        const hasData = nilai && nilai.penilaiankredit;
        let html = '';

        // ✅ Button Edit/Add - HANYA UNTUK ADMIN DAN PE
        if (CAN_EDIT) {
            if (hasData) {
                html += `
                <button class="btn btn-sm btn-warning ml-2 btn-edit" 
                        data-id="${faktorId}"
                        data-title="${escapeHtml(title)}"
                        data-penilaiankredit="${nilai.penilaiankredit}"
                        data-keterangan="${escapeHtml(nilai.keterangan || '')}">
                    <i class="fas fa-edit"></i>
                </button>
            `;
            } else {
                html += `
                <button type="button" class="btn btn-sm btn-success ml-2 btn-add" 
                        data-id="${faktorId}"
                        data-title="${escapeHtml(title)}">
                    <i class="fas fa-plus"></i>
                </button>
            `;
            }
        }

        // Button Komentar - UNTUK SEMUA USER
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

    function renderCategoryButtons(faktorId, nilai) {
        const keterangan = nilai?.keterangan || '';
        let html = '';

        // ✅ Button Edit Keterangan - HANYA UNTUK ADMIN DAN PE
        if (CAN_EDIT) {
            html += `
            <button type="button" class="btn btn-sm btn-warning ml-2 btn-edit-keterangan" 
                    data-id="${faktorId}" 
                    data-title="Kategori"
                    data-keterangan="${escapeHtml(keterangan)}">
                <i class="fa fa-edit"></i>
            </button>
            `;
        }

        // Button Komentar - UNTUK SEMUA USER
        html += `
        <button type="button" class="btn btn-sm btn-primary ml-2 position-relative komentar-button"
                data-faktor-id="${faktorId}">
            <i class="fas fa-comment"></i>
            <span id="notification-badge-${faktorId}" 
                  class="badge badge-danger notification-badge" 
                  style="display: none;">0</span>
        </button>
        `;

        html += renderApprovalButton(faktorId, nilai);
        return html;
    }

    function renderCategoryChildren(factor) {
        if (!factor.children) return '';

        let html = '';
        factor.children.forEach((child, idx) => {
            const nilai = child.nilai;
            const hasData = nilai && nilai.penilaiankredit;

            let displayTitle = escapeHtml(child.title)
                .replace(/\\n/g, '<br>')
                .replace(/\n/g, '<br>')
                .replace(/&lt;br&gt;/gi, '<br>')
                .replace(/\r\n/g, '<br>')
                .replace(/\r/g, '<br>');

            html += `
            <div class="border-left border-success pl-4 mb-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <h6 class="font-weight-bold">
                        ${idx + 1}. ${displayTitle}
                        </h6>
                        ${nilai?.rasiokredit ? `<p class="mb-2"><strong>Nilai rasio: ${nilai.rasiokredit}</strong></p>` : ''}
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
            </div>`;
        });

        return html;
    }

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
            <form action="${BASE_URL}/Risikokredit/${action}" method="POST" style="display: inline-block;" class="ml-2 form-approval">
                <input type="hidden" name="${CSRF_TOKEN_NAME}" value="${CSRF_HASH}">
                <input type="hidden" name="faktor1id" value="${faktorId}">
                <input type="hidden" name="kodebpr" value="${GLOBAL_KODEBPR}">
                <input type="hidden" name="periode_id" value="${GLOBAL_PERIODE_ID}">
                <button type="submit" class="btn btn-sm ${btnClass}">
                    <i class="fas fa-${icon}-circle"></i>
                </button>
            </form>`;
        <?php else: ?>
            return '';
        <?php endif; ?>
    }

    // ===== EVENT HANDLERS =====
    function attachEventHandlers() {
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

        $('.btn-edit-keterangan').off('click').on('click', function () {
            const id = $(this).data('id');
            const title = $(this).data('title');
            const keterangan = $(this).data('keterangan');

            // ✅ Debug log
            console.log('Edit Keterangan - ID:', id);
            console.log('Edit Keterangan - Title:', title);
            console.log('Edit Keterangan - Keterangan:', keterangan);

            $('#modalUbahkesimpulan #edit-faktor-id-kesimpulan').val(id);
            $('#modalUbahkesimpulan #edit-parameterpenilaian-kesimpulan').val(title);
            $('#modalUbahkesimpulan #edit-keterangan-kesimpulan').val(keterangan);

            $('#modalUbahkesimpulan').modal('show');
        });

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

    // ===== OFFCANVAS HANDLERS =====
    function showOffcanvas(offcanvas) {
        let backdrop = document.querySelector('.offcanvas-backdrop');
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.className = 'offcanvas-backdrop';
            document.body.appendChild(backdrop);
        }
        offcanvas.classList.add('show');
        backdrop.classList.add('show');
        document.body.classList.add('offcanvas-open');
    }

    function hideOffcanvas(offcanvas) {
        const backdrop = document.querySelector('.offcanvas-backdrop');

        if (offcanvas) {
            offcanvas.classList.remove('show');
        }

        if (backdrop) {
            backdrop.classList.remove('show');
            setTimeout(() => {
                if (backdrop.parentNode) {
                    backdrop.parentNode.removeChild(backdrop);
                }
            }, 300);
        }

        document.body.classList.remove('offcanvas-open');
        document.body.style.overflow = '';
    }

    // ===== NOTIFICATION BADGE =====
    function updateBadge(faktorId, count) {
        const badge = $(`#notification-badge-${faktorId}`);

        if (badge.length) {
            badge.text(count);
            if (count > 0) {
                badge.fadeIn(200);
            } else {
                badge.fadeOut(200);
            }
        }
    }

    function pollUnreadCounts() {
        $.ajax({
            url: `${BASE_URL}/Risikokredit/getAllUnreadCounts`,
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    const counts = response.counts || {};
                    $('.notification-badge').fadeOut(200);
                    Object.keys(counts).forEach(faktorId => {
                        updateBadge(faktorId, counts[faktorId]);
                    });
                }
            }
        });
    }

    // ===== UPDATE BADGES =====
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
        </span>`;

        $('.d-flex.justify-content-between.align-items-center .text-center .mt-2:first').find('span.badge:first').replaceWith(badgeHtml);
    }

    function updateMainApprovalBadge(allApproved, approvedAt = null) {
        const badge = $('#approval-badge-main');
        if (!badge.length) return;

        badge.fadeOut(150, function () {
            if (allApproved) {
                badge.removeClass('badge-secondary badge-warning').addClass('badge-success');
                const dateStr = approvedAt ? formatDateTime(approvedAt) : '-';
                badge.html(`Disetujui oleh <strong>Direktur Utama</strong><br>${dateStr}`);
            } else {
                badge.removeClass('badge-success badge-warning').addClass('badge-secondary');
                badge.html(`Belum Disetujui Seluruhnya<br>Oleh Direktur Utama`);
            }
            badge.fadeIn(150);
        });
    }

    function refreshFactorsData() {
        $.ajax({
            url: BASE_URL + '/Risikokredit/getFactorsData',
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
            url: `${BASE_URL}/Risikokredit/getKomentarByFaktorId/${faktorId}`,
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

    // ===== DOCUMENT READY =====
    $(document).ready(function () {
        // Load factors data
        setTimeout(function () {
            $.ajax({
                url: BASE_URL + '/Risikokredit/getFactorsData',
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

        // Poll unread counts
        pollUnreadCounts();
        setInterval(pollUnreadCounts, 10000);

        // Rating list click handlers
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

        // Form submissions
        $('form[action*="Risikokredit/ubah"]').on('submit', function (e) {
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

            if (!penilaian && faktorId != 13) {
                showToast('Error', 'Pilih tingkat risiko terlebih dahulu', 'error');
                return;
            }

            if (!keterangan.trim()) {
                showToast('Error', 'Keterangan harus diisi', 'error');
                return;
            }

            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...').prop('disabled', true);

            const formData = form.serializeArray();
            formData.push({ name: CSRF_TOKEN_NAME, value: CSRF_HASH });

            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: $.param(formData),
                dataType: 'json',
                success: function (response) {
                    $('#modalUbah').modal('hide');
                    showToast('Berhasil', 'Data berhasil diubah', 'success');

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
                }
            });
        });

        $('form[action*="Risikokredit/ubahkesimpulan"]').off('submit').on('submit', function (e) {
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

            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...')
                .prop('disabled', true);

            const formData = form.serializeArray();
            formData.push({ name: CSRF_TOKEN_NAME, value: CSRF_HASH });

            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: $.param(formData),
                dataType: 'json',
                success: function (response) {
                    $('#modalUbahkesimpulan').modal('hide');
                    showToast('Berhasil', 'Keterangan berhasil diubah', 'success');
                    updateMainApprovalBadge(false);
                    refreshFactorsData();
                    submitBtn.html(originalBtnText).prop('disabled', false);
                    form[0].reset();
                },
                error: function (xhr) {
                    submitBtn.html(originalBtnText).prop('disabled', false);
                    const errorMsg = xhr.responseJSON?.message || 'Gagal menyimpan keterangan';
                    showToast('Error', errorMsg, 'error');
                }
            });
        });


        $('form[action*="Risikokredit/ubahketerangan"]').on('submit', function (e) {
            e.preventDefault();

            const form = $(this);
            const submitBtn = form.find('button[type="submit"]');
            const originalBtnText = submitBtn.html();

            const faktorId = form.find('[name="faktor1id"]').val();
            const keterangan = form.find('[name="keterangan"]').val();

            // ✅ Debug log
            console.log('Submit - Faktor ID:', faktorId);
            console.log('Submit - Keterangan:', keterangan);

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
            formData.push({ name: CSRF_TOKEN_NAME, value: CSRF_HASH });

            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: $.param(formData),
                dataType: 'json',
                success: function (response) {
                    console.log('Response:', response); // ✅ Debug
                    $('#modalUbahkesimpulan').modal('hide');
                    showToast('Berhasil', 'Keterangan berhasil diubah', 'success');
                    updateMainApprovalBadge(false);
                    refreshFactorsData();
                    submitBtn.html(originalBtnText).prop('disabled', false);
                    form[0].reset();
                },
                error: function (xhr) {
                    console.error('Error:', xhr); // ✅ Debug
                    submitBtn.html(originalBtnText).prop('disabled', false);
                    const errorMsg = xhr.responseJSON?.message || 'Gagal menyimpan keterangan';
                    showToast('Error', errorMsg, 'error');
                }
            });
        });

        $('form[action*="Risikokredit/tambahNilai"]').on('submit', function (e) {
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
            formData.push({ name: CSRF_TOKEN_NAME, value: CSRF_HASH });

            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: $.param(formData),
                dataType: 'json',
                success: function (response) {
                    $('#modaltambahNilai').modal('hide');
                    showToast('Berhasil', 'Nilai berhasil ditambahkan', 'success');

                    if (response.peringkat13) {
                        updateBadgePeringkat13(response.peringkat13);
                    }

                    updateMainApprovalBadge(false);
                    refreshFactorsData();
                    submitBtn.html(originalBtnText).prop('disabled', false);
                    form[0].reset();
                    $('#addSelectedValue').text('Belum ada');
                },
                error: function (xhr) {
                    submitBtn.html(originalBtnText).prop('disabled', false);
                    const errorMsg = xhr.responseJSON?.message || 'Gagal menambahkan nilai';
                    showToast('Error', errorMsg, 'error');
                }
            });
        });

        $('form[action*="Risikokredit/tambahKomentar"]').on('submit', function (e) {
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
                    pollUnreadCounts();
                },
                error: function () {
                    submitBtn.html(originalBtnText).prop('disabled', false);
                    showToast('Error', 'Gagal menambahkan komentar', 'error');
                }
            });
        });

        // Komentar button handler
        $(document).on('click', '.komentar-button', function () {
            const faktorId = $(this).data('faktor-id');
            $('#comment-faktor-id').val(faktorId);

            // Load komentar
            $.ajax({
                url: `${BASE_URL}/Risikokredit/getKomentarByFaktorId/${faktorId}`,
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

            // Mark as read
            $.ajax({
                url: `${BASE_URL}/Risikokredit/markUserCommentsAsRead`,
                method: 'POST',
                data: {
                    faktor_id: faktorId,
                    kodebpr: GLOBAL_KODEBPR,
                    periode_id: GLOBAL_PERIODE_ID,
                    [CSRF_TOKEN_NAME]: CSRF_HASH
                },
                success: function (response) {
                    if (response.status === 'success') {
                        updateBadge(faktorId, 0);
                    }
                }
            });

            $('#modaltambahKomentar').modal('show');
        });

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

        // Modal reset handlers
        $('#modaltambahNilai, #modalUbah').on('hidden.bs.modal', function () {
            const prefix = $(this).attr('id') === 'modaltambahNilai' ? 'add' : 'edit';
            $(`#${prefix}-penilaiankredit, #${prefix}-penjelasanpenilaian, #${prefix}-keterangan`).val('');
            $(`#${prefix}SelectedValue`).text('Belum ada');
            $(`#${prefix}RatingList .list-group-item`).removeClass('active');
            $(`#${prefix}-catatan-info`).hide();
        });

        // Button animation
        $(document).on('mousedown', 'button, a.btn', function () {
            $(this).css('transform', 'scale(0.95)');
        });

        $(document).on('mouseup mouseleave', 'button, a.btn', function () {
            $(this).css('transform', 'scale(1)');
        });

        // Offcanvas setup
        document.querySelectorAll('[data-bs-toggle="offcanvas"]').forEach(button => {
            button.addEventListener('click', function () {
                const offcanvas = document.querySelector(this.getAttribute('data-bs-target'));
                if (offcanvas) showOffcanvas(offcanvas);
            });
        });

        document.querySelectorAll('[data-bs-dismiss="offcanvas"]').forEach(button => {
            button.addEventListener('click', function () {
                const offcanvas = this.closest('.offcanvas');
                if (offcanvas) hideOffcanvas(offcanvas);
            });
        });

        // Auto-calculate for Kalkulator 1
        const offcanvas1 = document.getElementById('offcanvasWithBothOptions');
        if (offcanvas1) {
            offcanvas1.addEventListener('shown.bs.offcanvas', () => {
                const aba = document.getElementById('aba');
                if (aba && aba.value) calculateRiskKalkulator();
            });

            const riskForm = document.getElementById('riskForm');
            if (riskForm) {
                riskForm.querySelectorAll('input[type="number"]').forEach(el => {
                    el.addEventListener('input', calculateRiskKalkulator);
                });
            }
        }

        // Auto-calculate for Kalkulator 2
        const offcanvas2 = document.getElementById('offcanvasWithBothOptions2');
        if (offcanvas2) {
            offcanvas2.addEventListener('shown.bs.offcanvas', () => {
                const abanpl = document.getElementById('abanpl');
                if (abanpl && abanpl.value) calculateRiskKalkulator2();
            });

            const riskForm2 = document.getElementById('riskForm2');
            if (riskForm2) {
                riskForm2.querySelectorAll('input[type="number"]').forEach(el => {
                    el.addEventListener('input', calculateRiskKalkulator2);
                });
            }
        }

        window.onload = () => {
            const urlParams = new URLSearchParams(window.location.search);
            const type = urlParams.get('type');

            if (type === 'zip') {
                generateAllPDFsToZip();
            } else if (type === 'kredit') {
                generatePDFKredit();
            } else if (type === 'operasional') {
                generatePDFOperasional();
            } else if (type === 'kepatuhan') {
                generatePDFKepatuhan();
            } else if (type === 'likuiditas') {
                generatePDFLikuiditas();
            } else {
                generatePDFGabungan();
            }
        };
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

    /* ===== Reset & Base ===== */
    body {
        font-family: 'Arial', sans-serif;
        background-color: var(--light-bg);
        margin: 0;
        padding: 0;
    }

    /* ===== Container ===== */
    .container-fluid {
        margin-top: 30px;
    }

    /* ===== Typography ===== */
    h3,
    h4 {
        font-weight: bold;
        color: var(--dark-color);
    }

    /* ===== Alerts ===== */
    .alert {
        font-size: 14px;
        padding: 15px;
        border-radius: var(--border-radius);
        margin-bottom: 20px;
    }

    .alert-success {
        background-color: var(--success-color);
        color: var(--white);
    }

    /* ===== Tables ===== */
    .table {
        width: 100%;
        margin-bottom: 1rem;
        background-color: var(--white);
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
    }

    .table th,
    .table td {
        padding: 12px 15px;
        text-align: center;
        border-bottom: 1px solid var(--border-color);
    }

    .table th {
        background-color: #f1f3f5;
        color: #495057;
    }

    .table-striped tbody tr:nth-of-type(odd) {
        background-color: var(--light-bg);
    }

    .table-responsive {
        overflow-x: auto;
    }

    /* Table Column Widths */
    .kolom-parameter {
        width: 600px;
        text-align: left;
    }

    .kolom-keterangan {
        width: 600px;
        max-width: 600px;
        white-space: normal;
        word-wrap: break-word;
    }

    .kolom-aksi,
    .kolom-rasio,
    .kolom-penilaian {
        width: 150px;
        text-align: center;
        white-space: nowrap;
    }

    .kolom-aksi {
        width: 100px;
    }

    /* ===== Badges ===== */
    .badge {
        font-size: 14px;
        padding: 5px 10px;
        border-radius: 20px;
    }

    .badge-success {
        background-color: var(--success-color);
        color: var(--white);
    }

    .badge-secondary {
        background-color: var(--secondary-color);
        color: var(--white);
    }

    .badge-primary {
        background-color: #12131C;
        color: var(--white);
    }

    /* Notification Badge */
    .notification-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background-color: var(--danger-color);
        color: var(--white);
        min-width: 20px;
        height: 20px;
        padding: 5px;
        font-size: 10px;
        line-height: 1;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-sizing: border-box;
    }

    .card {
        margin-bottom: 20px;
        border-radius: 10px;
        box-shadow: var(--box-shadow);
    }

    .card-body {
        padding: 20px;
    }

    .card-header {
        background-color: #f1f3f5;
        border-bottom: 2px solid #ddd;
        font-weight: bold;
    }

    /* Approval Card */
    .approvaldir-card {
        border-radius: 20px;
        background: var(--white);
        overflow: hidden;
        box-shadow: var(--box-shadow);
    }

    .approvaldir-card-body {
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 15px;
        height: 100%;
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

    .cardpilihfaktor {
        width: auto;
        max-width: 440px;
        margin: 10px auto;
        border: 1px solid #ddd;
        border-radius: var(--border-radius);
    }

    .cardpilihfaktor-header {
        text-align: center;
        background-color: var(--light-bg);
        padding: 2px;
        border-bottom: 1px solid #ddd;
        font-size: 1rem;
        font-weight: bold;
    }

    .cardpilihfaktor-body {
        padding: 5px;
    }

    .cardpilihfaktor .btn-toolbar {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
    }

    .cardpilihfaktor .btn-group .btn {
        margin: 1px;
    }

    .btn {
        font-size: 14px;
        padding: 8px 20px;
        border-radius: 5px;
        cursor: pointer;
        transition: var(--transition);
    }

    .btn:hover {
        transform: scale(1.05);
        box-shadow: var(--box-shadow-hover);
    }

    .btn-sm {
        padding: 5px 10px;
        font-size: 12px;
    }

    .btn-outline-primary {
        border: 1px solid var(--primary-color);
        background-color: transparent;
        color: var(--primary-color);
    }

    .btn-outline-primary:hover {
        background-color: var(--primary-color);
        color: var(--white);
        border-color: var(--primary-color);
    }

    /* Approval Buttons */
    .approval-buttons-container {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 9px;
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

    input[type="text"],
    input[type="number"],
    textarea {
        width: 100%;
        padding: 10px;
        font-size: 14px;
        border: 1px solid #ddd;
        border-radius: 5px;
        margin-top: 5px;
        margin-bottom: 15px;
        box-sizing: border-box;
    }

    .modal-header {
        background-color: var(--primary-color);
        color: var(--white);
        font-size: 18px;
        padding: 15px;
    }

    .modal-footer .btn {
        padding: 10px 25px;
        font-size: 16px;
        border-radius: var(--border-radius);
    }

    .custom-link {
        font-size: 18px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        color: var(--primary-color);
        font-weight: 500;
        position: relative;
    }

    .custom-link::before {
        content: "🡸 ";
        display: inline-block;
        transition: transform 0.3s ease;
    }

    .custom-link:hover::before {
        transform: translateX(-6px);
    }

    .custom-link:hover {
        text-decoration: underline;
        color: #0a58ca;
    }

    .disabled-btn,
    .disabled-card {
        opacity: 0.5;
        pointer-events: none;
    }

    .komentar-btn-wrapper {
        position: relative;
        display: inline-block;
    }

    @media (max-width: 768px) {

        .kolom-parameter,
        .kolom-keterangan {
            width: auto;
            max-width: 100%;
        }

        .approval-btn {
            font-size: 0.85em;
            padding: 6px 10px;
        }

        .table th,
        .table td {
            padding: 8px 10px;
            font-size: 12px;
        }
    }

    @media (max-width: 576px) {
        .container-fluid {
            margin-top: 15px;
        }

        h3 {
            font-size: 1.5rem;
        }

        h4 {
            font-size: 1.2rem;
        }

        .approval-buttons-container {
            flex-direction: column;
            gap: 5px;
        }

        .approval-btn {
            max-width: 100%;
        }
    }

    .button-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        margin: 15px 0;
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
        background-color: #10598A;
        transform: translateY(-2px);
        color: #fff;
    }

    .custom-link {
        font-size: 18px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        color: var(--primary-color);
        font-weight: 500;
        position: relative;
    }

    .custom-link::before {
        content: "🡸 ";
        display: inline-block;
        transition: transform 0.3s ease;
    }

    .custom-link:hover::before {
        transform: translateX(-6px);
    }

    .custom-link:hover {
        text-decoration: underline;
        color: #10598A;
    }


    .btn {
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
        border: 1px solid transparent;
        border-radius: 0.375rem;
        cursor: pointer;
        transition: all 0.15s ease-in-out;
        font-weight: 500;
    }

    .btn-primary {
        background-color: #10598A;
        color: white;
        border-color: #10598A;
    }

    .btn-primary:hover {
        background-color: #0b5ed7;
        border-color: #0a58ca;
    }

    .btn-close {
        content: "x";
        background: none;
        border: none;
        padding: 0.5rem;
        cursor: pointer;
        font-size: 1.5rem;
        color: #000;
        width: 2rem;
        height: 2rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .btn-close:hover {
        color: #000;
    }

    .offcanvas {
        position: fixed;
        bottom: 0;
        top: 0;
        z-index: 1045;
        display: flex;
        flex-direction: column;
        max-width: 100%;
        visibility: hidden;
        background-color: #fff;
        transition: visibility 0.3s ease-in-out, transform 0.3s ease-in-out;
    }

    .offcanvas-end {
        right: 0;
        width: 500px;
        transform: translateX(100%);
    }

    .offcanvas.show {
        visibility: visible;
        transform: translateX(0);
    }

    .offcanvas.show.offcanvas-end {
        transform: translateX(0);
    }

    .offcanvas-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        z-index: 1040;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease-in-out, visibility 0.3s ease-in-out;
    }

    .offcanvas-backdrop.show {
        opacity: 1;
        visibility: visible;
    }

    .offcanvas-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #e9ecef;
    }

    .offcanvas-title {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
        color: #212529;
    }

    .offcanvas-body {
        flex: 1;
        padding: 1.5rem;
        overflow-y: auto;
        color: #6c757d;
    }

    body.offcanvas-open {
        overflow: hidden;
    }

    @media (max-width: 768px) {
        .offcanvas-end {
            width: 100%;
        }
    }


    .risk-result {
        padding: 15px;
        border-radius: 8px;
        margin-top: 20px;
        text-align: center;
        font-weight: bold;
        display: none;
    }

    .risk-result.show {
        display: block;
    }

    .risk-low {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .risk-medium {
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeaa7;
    }

    .risk-high {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .form-label {
        font-weight: 600;
        margin-top: 15px;
    }

    .input-group-text {
        background-color: #f8f9fa;
    }

    .list-group-item.active {
        background-color: #007bff !important;
        border-color: #007bff !important;
        color: white !important;
    }

    .list-group-item.active h6,
    .list-group-item.active p,
    .list-group-item.active b {
        color: white !important;
    }

    .list-group-item:hover {
        background-color: #f8f9fa;
        cursor: pointer;
    }

    .list-group-item.active:hover {
        background-color: #0056b3 !important;
    }

    .list-group-item h6 {
        font-size: 1rem;
        margin-bottom: 0.25rem;
    }

    .list-group-item p {
        font-size: 0.875rem;
        line-height: 1.4;
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
</style>