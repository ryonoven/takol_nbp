<div class="alert alert-primary my-2">
    <?php if (isset($bprData) && isset($periodeDetail)): ?>
        <strong><?= esc($bprData['namabpr'] ?? 'Nama BPR') ?></strong> - Periode Pelaporan
        Semester <?= esc($periodeDetail['semester']) ?> Tahun <?= esc($periodeDetail['tahun']) ?>
    <?php endif; ?>
</div>
<?php
$cs6 = (int) ($periodeDetail['semester'] ?? 1);
$ct6 = (int) ($periodeDetail['tahun'] ?? date('Y'));
$prevSemLabel6 = $cs6 == 1 ? 2 : 1;
$prevTahunLabel6 = $cs6 == 1 ? $ct6 - 1 : $ct6;
?>

<div class="container-fluid">
    <div class="d-flex justify-content-start mb-2">
        <a href="<?= base_url('ShowFaktor') ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>
    <div class="card shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-left" style="flex: 1;">
                    <span id="badge-approval-dekom" class="badge badge-secondary" style="font-size: 14px;">
                        Belum Disetujui Seluruhnya<br>Oleh Komisaris Utama
                    </span>
                </span>

                <span class="text-right" style="flex: 1;">
                    <span id="badge-approval-dir" class="badge badge-secondary" style="font-size: 14px;">
                        Belum Disetujui Seluruhnya<br>Oleh Direktur Utama
                    </span>
                </span>
            </div>

            <!-- Row Tombol Approval -->
            <div class="row mt-3">
                <?php if ($userInGroupAdmin || $userInGroupDekom): ?>
                    <div class="col-md-3">
                        <div class="card shadow-sm" style="width: 100%; height: 120px;">
                            <div class="card-body">
                                <div class="text-center">
                                    <span class="badge badge-primary">Approval Komisaris Utama</span>
                                </div>
                                <div class="d-flex justify-content-center align-items-center mt-3">
                                    <button type="button" class="btn btn-success shadow mr-2 btn-approve-all-dekom">
                                        Approve
                                    </button>
                                    <!-- <button type="button" class="btn btn-danger shadow btn-unapprove-all-dekom">
                                        Tolak
                                    </button> -->
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($userInGroupAdmin || $userInGroupDireksi): ?>
                    <div class="col-md-3" style="margin-left: auto;">
                        <div class="card shadow-sm" id="card-approval-dir" style="width: 100%; height: 120px;">
                            <div class="card-body">
                                <div class="text-center">
                                    <span class="badge badge-primary">Approval Direktur Utama</span>
                                </div>
                                <div class="d-flex justify-content-center align-items-center mt-3">
                                    <button type="button" class="btn btn-success shadow mr-2 btn-approve-all-dir"
                                        id="btn-approve-all-dir">
                                        Approve
                                    </button>
                                    <!-- <button type="button" class="btn btn-danger shadow btn-unapprove-all-dir">
                                        Tolak
                                    </button> -->
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php if ($userInGroupAdmin || $userInGroupPE): ?>
        <button type="button" class="btn btn-primary mb-3" id="btnSyncPrevPeriode">
            <i class="fas fa-sync-alt"></i> Sinkron Data Periode Sebelumnya
        </button>
    <?php endif; ?>
    <div id="factors-skeleton" style="display: none;">
        <?php for ($i = 0; $i < 3; $i++): ?>
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <div class="skeleton-line" style="width: 40%; height: 20px;"></div>
                </div>
                <div class="card-body">
                    <div class="skeleton-line mb-2" style="height: 15px;"></div>
                    <div class="skeleton-line mb-2" style="width: 80%; height: 15px;"></div>
                    <div class="skeleton-line" style="width: 60%; height: 15px;"></div>
                </div>
            </div>
        <?php endfor; ?>
    </div>

    <!-- Main Content Container -->
    <div id="factors-container" style="display: none;"></div>

    <div class="row mt-3">
        <?php if ($userInGroupAdmin || $userInGroupDekom): ?>
            <div class="col-md-3">
                <div class="card shadow-sm" style="width: 100%; height: 120px;">
                    <div class="card-body">
                        <div class="text-center">
                            <span class="badge badge-primary">Approval Komisaris Utama</span>
                        </div>
                        <div class="d-flex justify-content-center align-items-center mt-3">
                            <button type="button" class="btn btn-success shadow mr-2 btn-approve-all-dekom">
                                Approve
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($userInGroupAdmin || $userInGroupDireksi): ?>
            <div class="col-md-3" style="margin-left: auto;">
                <div class="card shadow-sm" id="card-approval-dir" style="width: 100%; height: 120px;">
                    <div class="card-body">
                        <div class="text-center">
                            <span class="badge badge-primary">Approval Direktur Utama</span>
                        </div>
                        <div class="d-flex justify-content-center align-items-center mt-3">
                            <button type="button" class="btn btn-success shadow mr-2 btn-approve-all-dir"
                                id="btn-approve-all-dir">
                                Approve
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="row mt-2 mb-4">
        <div class="col-12 text-center">
            <a href="<?= base_url('ShowFaktor') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>
</div>

<!-- Modal Ubah -->
<div class="modal fade" id="modalUbah">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="<?= base_url('Faktor6/ubah'); ?>" method="post">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title">Ubah Data Penilaian</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="faktor6id" id="edit-faktor-id">

                    <div class="form-group">
                        <label class="font-weight-bold">Sub Kategori:</label>
                        <textarea class="form-control" name="sub_category" id="edit-sub_category" rows="3"
                            readonly></textarea>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Pilih Nilai:</label>
                        <input type="hidden" name="nilai" id="edit-nilai" value="">

                        <div class="list-group" id="editRatingList">
                            <a href="#" class="list-group-item list-group-item-action py-2 px-3" data-value="1">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><b>Nilai 1</b></h6>
                                </div>
                                <p class="mb-0 small">Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur
                                    sesuai ketentuan, proses pelaksanaan tata kelola dilakukan dengan memadai dan
                                    ditunjukkan dengan hasil pelaksanaan tata kelola yang <b>Sangat Baik</b></p>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action py-2 px-3" data-value="2">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><b>Nilai 2</b></h6>
                                </div>
                                <p class="mb-0 small">Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur
                                    sesuai ketentuan, proses pelaksanaan tata kelola dilakukan dengan memadai dan
                                    ditunjukkan dengan hasil pelaksanaan tata kelola yang <b>Baik</b></p>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action py-2 px-3" data-value="3">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><b>Nilai 3</b></h6>
                                </div>
                                <p class="mb-0 small">Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur
                                    sesuai ketentuan, proses pelaksanaan tata kelola dilakukan dengan memadai dan
                                    ditunjukkan dengan hasil pelaksanaan tata kelola yang <b>Cukup Baik</b></p>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action py-2 px-3" data-value="4">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><b>Nilai 4</b></h6>
                                </div>
                                <p class="mb-0 small">Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur
                                    sesuai ketentuan, proses pelaksanaan tata kelola dilakukan dengan memadai dan
                                    ditunjukkan dengan hasil pelaksanaan tata kelola yang <b>Kurang Baik</b></p>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action py-2 px-3" data-value="5">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><b>Nilai 5</b></h6>
                                </div>
                                <p class="mb-0 small">Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur
                                    sesuai ketentuan, proses pelaksanaan tata kelola dilakukan dengan memadai dan
                                    ditunjukkan dengan hasil pelaksanaan tata kelola yang <b>Buruk</b></p>
                            </a>
                        </div>
                        <p class="mt-2 mb-0">Nilai yang dipilih: <strong id="editSelectedValue">Belum ada</strong></p>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Keterangan:</label>
                        <textarea class="form-control keterangan-limited" name="keterangan" id="edit-keterangan"
                            rows="5" maxlength="2000" placeholder="Masukkan keterangan penilaian..."
                            required></textarea>
                        <small class="form-text text-muted text-right d-block">
                            <span class="char-counter" data-target="edit-keterangan">0</span>/2000 karakter
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tambah Nilai -->
<div class="modal fade" id="modaltambahNilai">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="<?= base_url('Faktor6/tambahNilai'); ?>" method="post">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title">Tambah Nilai Penilaian</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="faktor6_id" id="add-faktor-id">
                    <input type="hidden" name="fullname" value="<?= htmlspecialchars($fullname ?? '') ?>">

                    <div class="form-group">
                        <label class="font-weight-bold">Sub Kategori:</label>
                        <textarea class="form-control" name="sub_category" id="add-sub_category" rows="3"
                            readonly></textarea>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Pilih Nilai:</label>
                        <input type="hidden" name="nilai" id="add-nilai" value="">

                        <div class="list-group" id="addRatingList">
                            <a href="#" class="list-group-item list-group-item-action py-2 px-3" data-value="1">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><b>Nilai 1</b></h6>
                                </div>
                                <p class="mb-0 small">Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur
                                    sesuai ketentuan, proses pelaksanaan tata kelola dilakukan dengan memadai dan
                                    ditunjukkan dengan hasil pelaksanaan tata kelola yang <b>Sangat Memadai</b></p>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action py-2 px-3" data-value="2">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><b>Nilai 2</b></h6>
                                </div>
                                <p class="mb-0 small">Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur
                                    sesuai ketentuan, proses pelaksanaan tata kelola dilakukan dengan memadai dan
                                    ditunjukkan dengan hasil pelaksanaan tata kelola yang <b>Baik</b></p>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action py-2 px-3" data-value="3">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><b>Nilai 3</b></h6>
                                </div>
                                <p class="mb-0 small">Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur
                                    sesuai ketentuan, proses pelaksanaan tata kelola dilakukan dengan memadai dan
                                    ditunjukkan dengan hasil pelaksanaan tata kelola yang <b>Cukup Baik</b></p>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action py-2 px-3" data-value="4">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><b>Nilai 4</b></h6>
                                </div>
                                <p class="mb-0 small">Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur
                                    sesuai ketentuan, proses pelaksanaan tata kelola dilakukan dengan memadai dan
                                    ditunjukkan dengan hasil pelaksanaan tata kelola yang <b>Kurang Baik</b></p>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action py-2 px-3" data-value="5">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><b>Nilai 5</b></h6>
                                </div>
                                <p class="mb-0 small">Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur
                                    sesuai ketentuan, proses pelaksanaan tata kelola dilakukan dengan memadai dan
                                    ditunjukkan dengan hasil pelaksanaan tata kelola yang <b>Buruk</b></p>
                            </a>
                        </div>
                        <p class="mt-2 mb-0">Nilai yang dipilih: <strong id="addSelectedValue">Belum ada</strong></p>
                        <small class="form-text text-muted">
                            Pilih nilai berdasarkan kondisi struktur, proses, dan hasil penerapan tata kelola
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Keterangan:</label>
                        <textarea class="form-control keterangan-limited" name="keterangan" id="add-keterangan" rows="5"
                            maxlength="2000" placeholder="Masukkan keterangan penilaian..." required></textarea>
                        <small class="form-text text-muted text-right d-block">
                            <span class="char-counter" data-target="add-keterangan">0</span>/2000 karakter
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-plus"></i> Tambah Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Kesimpulan -->
<div class="modal fade" id="modalUbahkesimpulan">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form action="<?= base_url('Faktor6/ubahkesimpulan'); ?>" method="post" id="form-kesimpulan">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title">Kesimpulan Penilaian Faktor 6</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="faktor6id" id="kesimpulan-faktor-id" value="10">

                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="font-weight-bold text-success mb-3">
                                <i class="fas fa-plus-circle"></i> Faktor Positif
                            </h6>

                            <div class="form-group">
                                <label class="font-weight-bold">Struktur: <span class="text-danger">*</span></label>
                                <textarea class="form-control keterangan-limited" name="positifstruktur"
                                    id="kesimpulan-positifstruktur" rows="4" maxlength="2000"
                                    placeholder="Jelaskan faktor positif dari struktur..."></textarea>
                                <small class="form-text text-muted text-right d-block">
                                    <span class="char-counter" data-target="kesimpulan-positifstruktur">0</span>/2000
                                    karakter
                                </small>
                                <small class="text-danger" id="error-positifstruktur" style="display:none;">Field ini
                                    wajib diisi</small>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold">Proses: <span class="text-danger">*</span></label>
                                <textarea class="form-control keterangan-limited" name="positifproses"
                                    id="kesimpulan-positifproses" rows="4" maxlength="2000"
                                    placeholder="Jelaskan faktor positif dari proses..."></textarea>
                                <small class="form-text text-muted text-right d-block">
                                    <span class="char-counter" data-target="kesimpulan-positifproses">0</span>/2000
                                    karakter
                                </small>
                                <small class="text-danger" id="error-positifproses" style="display:none;">Field ini
                                    wajib diisi</small>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold">Hasil: <span class="text-danger">*</span></label>
                                <textarea class="form-control keterangan-limited" name="positifhasil"
                                    id="kesimpulan-positifhasil" rows="4" maxlength="2000"
                                    placeholder="Jelaskan faktor positif dari hasil..."></textarea>
                                <small class="form-text text-muted text-right d-block">
                                    <span class="char-counter" data-target="kesimpulan-positifhasil">0</span>/2000
                                    karakter
                                </small>
                                <small class="text-danger" id="error-positifhasil" style="display:none;">Field ini wajib
                                    diisi</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h6 class="font-weight-bold text-danger mb-3">
                                <i class="fas fa-minus-circle"></i> Faktor Negatif
                            </h6>

                            <div class="form-group">
                                <label class="font-weight-bold">Struktur: <span class="text-danger">*</span></label>
                                <textarea class="form-control keterangan-limited" name="negatifstruktur"
                                    id="kesimpulan-negatifstruktur" rows="4" maxlength="2000"
                                    placeholder="Jelaskan faktor negatif dari struktur..."></textarea>
                                <small class="form-text text-muted text-right d-block">
                                    <span class="char-counter" data-target="kesimpulan-negatifstruktur">0</span>/2000
                                    karakter
                                </small>
                                <small class="text-danger" id="error-negatifstruktur" style="display:none;">Field ini
                                    wajib diisi</small>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold">Proses: <span class="text-danger">*</span></label>
                                <textarea class="form-control keterangan-limited" name="negatifproses"
                                    id="kesimpulan-negatifproses" rows="4" maxlength="2000"
                                    placeholder="Jelaskan faktor negatif dari proses..."></textarea>
                                <small class="form-text text-muted text-right d-block">
                                    <span class="char-counter" data-target="kesimpulan-negatifproses">0</span>/2000
                                    karakter
                                </small>
                                <small class="text-danger" id="error-negatifproses" style="display:none;">Field ini
                                    wajib diisi</small>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold">Hasil: <span class="text-danger">*</span></label>
                                <textarea class="form-control keterangan-limited" name="negatifhasil"
                                    id="kesimpulan-negatifhasil" rows="4" maxlength="2000"
                                    placeholder="Jelaskan faktor negatif dari hasil..."></textarea>
                                <small class="form-text text-muted text-right d-block">
                                    <span class="char-counter" data-target="kesimpulan-negatifhasil">0</span>/2000
                                    karakter
                                </small>
                                <small class="text-danger" id="error-negatifhasil" style="display:none;">Field ini wajib
                                    diisi</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Kesimpulan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Komentar -->
<div class="modal fade" id="modaltambahKomentar">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="<?= base_url('Faktor6/tambahKomentar'); ?>" method="post">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-comments"></i> Komentar Direksi dan Dewan Komisaris
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="faktor6_id" id="komentar-faktor-id">
                    <input type="hidden" name="kodebpr" value="<?= $kodebpr ?>">
                    <input type="hidden" name="fullname" value="<?= htmlspecialchars($fullname ?? '') ?>">

                    <div class="form-group">
                        <label class="font-weight-bold">
                            <i class="fas fa-list"></i> Riwayat Komentar:
                        </label>
                        <div class="border rounded p-3" style="background: #f8f9fa;">
                            <ul id="komentarLamaList" class="mb-0">
                                <li class="text-muted text-center">
                                    <i class="fas fa-spinner fa-spin"></i> Memuat komentar...
                                </li>
                            </ul>
                        </div>
                    </div>

                    <?php if ($userInGroupAdmin || $userInGroupDekom || $userInGroupDireksi || $userInGroupPE || $userInGroupDekom2 || $userInGroupDireksi2): ?>
                        <div class="form-group">
                            <label class="font-weight-bold">
                                <i class="fas fa-pen"></i> Tambahkan Komentar Baru:
                            </label>
                            <textarea class="form-control" name="komentar" id="komentar-text" rows="4"
                                placeholder="Tulis komentar Anda di sini..." maxlength="1000"></textarea>
                            <small class="form-text text-muted">
                                <i class="fas fa-keyboard"></i> Minimal 3 karakter, maksimal 1000 karakter
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Tutup
                    </button>
                    <?php if ($userInGroupAdmin || $userInGroupDekom || $userInGroupDireksi || $userInGroupPE || $userInGroupDekom2 || $userInGroupDireksi2): ?>
                        <button type="submit" name="tambahKomentar" class="btn btn-success">
                            <i class="fas fa-paper-plane"></i> Kirim Komentar
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalSyncPrev" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-sync-alt"></i> Sinkronisasi Data Periode Sebelumnya
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning mb-3">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Perhatian!</strong> Tindakan ini akan:
                    <ul class="mb-0 mt-2">
                        <li>Menyalin semua data nilai &amp; keterangan dari periode sebelumnya</li>
                        <li><strong>Menimpa data yang sudah ada</strong> pada periode ini</li>
                        <li>Mereset semua status approval (perlu disetujui ulang)</li>
                        <li>Data yang disalin dapat langsung diedit</li>
                    </ul>
                </div>
                <p class="mb-0">
                    <i class="fas fa-info-circle text-info"></i>
                    Data akan diambil dari
                    <strong id="syncFromPeriodeLabel">memuat...</strong>.
                    Apakah Anda yakin ingin melanjutkan?
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="button" class="btn btn-primary" id="btnConfirmSync">
                    <i class="fas fa-sync-alt"></i> Ya, Sinkronkan
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-gradient-primary {
        background: linear-gradient(135deg, #1f2aa8 0%, #141863 100%);
        color: white;
    }

    .skeleton-line {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: loading 1.5s infinite;
        border-radius: 4px;
    }

    @keyframes loading {
        0% {
            background-position: 200% 0;
        }

        100% {
            background-position: -200% 0;
        }
    }

    .toast-notification {
        position: fixed;
        top: 20px;
        right: 20px;
        min-width: 300px;
        background: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        display: flex;
        align-items: center;
        gap: 12px;
        transform: translateX(400px);
        transition: transform 0.3s ease;
        z-index: 9999;
    }

    .toast-notification.show {
        transform: translateX(0);
    }

    .toast-notification.bg-success {
        background: #28a745;
        color: white;
    }

    .toast-notification.bg-danger {
        background: #dc3545;
        color: white;
    }

    .notification-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background: #dc3545;
        color: white;
        border-radius: 50%;
        padding: 4px 8px;
        font-size: 11px;
        font-weight: bold;
        min-width: 20px;
        text-align: center;
        display: none;
        /* ✅ Default hidden */
        justify-content: center;
        align-items: center;
    }

    .notification-badge.show {
        display: inline-flex;
        /* ✅ Tampilkan saat ada class 'show' */
    }

    .cardpilihfaktor {
        width: auto;
        max-width: 440px;
        margin: 10px auto;
        border: 1px solid #ddd;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .cardpilihfaktor-header {
        text-align: center;
        background-color: #f8f9fa;
        padding: 10px;
        border-bottom: 1px solid #ddd;
        font-size: 1rem;
        font-weight: bold;
    }

    .cardpilihfaktor-body {
        padding: 15px;
    }

    .btn-outline-primary {
        border-width: 1px;
        transition: all 0.3s ease;
    }

    .btn-outline-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 5px rgba(0, 123, 255, 0.3);
    }

    .table {
        font-size: 14px;
    }

    .table thead th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.5px;
    }

    .table-hover tbody tr:hover {
        background-color: #f1f3f5;
        transition: background-color 0.2s;
    }

    .modal-header {
        border-bottom: 2px solid rgba(255, 255, 255, 0.2);
    }

    .modal-body {
        padding: 25px;
    }

    .form-control:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    #komentarLamaList {
        list-style: none;
        padding: 0;
        margin: 0;
        max-height: 400px;
        overflow-y: auto;
    }

    #komentarLamaList::-webkit-scrollbar {
        width: 8px;
    }

    #komentarLamaList::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    #komentarLamaList::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 4px;
    }

    #komentarLamaList::-webkit-scrollbar-thumb:hover {
        background: #555;
    }

    .komentar-item {
        transition: all 0.3s ease;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .komentar-item:hover {
        transform: translateX(5px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    /* ✅ Highlight untuk komentar baru */
    .new-comment {
        position: relative;
        animation: pulse-animation 1s ease-in-out;
    }

    .new-comment::before {
        content: '';
        position: absolute;
        top: -5px;
        left: -5px;
        right: -5px;
        bottom: -5px;
        background: rgba(40, 167, 69, 0.1);
        border-radius: 8px;
        z-index: -1;
    }

    /* ✅ Pulse animation */
    @keyframes pulse-animation {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.02);
        }
    }

    /* ✅ Badge BARU */
    .badge-success {
        animation: badge-glow 2s ease-in-out infinite;
    }

    @keyframes badge-glow {

        0%,
        100% {
            box-shadow: 0 0 5px rgba(40, 167, 69, 0.5);
        }

        50% {
            box-shadow: 0 0 15px rgba(40, 167, 69, 0.8);
        }
    }

    /* ✅ Toast notification animation */
    @keyframes slideInRight {
        from {
            transform: translateX(400px);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    .custom-toast {
        animation: slideInRight 0.3s ease;
    }

    /* ✅ Notification badge (existing) */
    .notification-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background: #dc3545;
        color: white;
        border-radius: 50%;
        padding: 4px 8px;
        font-size: 11px;
        font-weight: bold;
        min-width: 20px;
        text-align: center;
        display: none;
        justify-content: center;
        align-items: center;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        animation: badge-pop 0.3s ease;
    }

    .notification-badge.show {
        display: inline-flex;
    }

    @keyframes badge-pop {
        0% {
            transform: scale(0);
        }

        50% {
            transform: scale(1.2);
        }

        100% {
            transform: scale(1);
        }
    }

    /* ✅ Modal komentar styling */
    #modaltambahKomentar .modal-body {
        padding: 20px;
    }

    #modaltambahKomentar .form-group label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 8px;
    }

    #komentar-text {
        resize: vertical;
        min-height: 100px;
    }

    #komentar-text:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    /* ✅ Empty state */
    .text-muted.text-center {
        padding: 30px;
        color: #6c757d;
    }

    /* ✅ Responsive */
    @media (max-width: 768px) {
        #komentarLamaList {
            max-height: 300px;
        }

        .komentar-item {
            font-size: 14px;
        }

        .custom-toast {
            right: 10px;
            left: 10px;
            min-width: auto;
        }
    }

    #badge-approval-dekom,
    #badge-approval-dir {
        display: inline-block;
        padding: 10px 15px;
        border-radius: 5px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .disabled-card {
        opacity: 0.5;
        pointer-events: none;
    }

    .disabled-btn {
        opacity: 0.5;
        pointer-events: none;
    }

    .btn-approve-all-dekom,
    .btn-approve-all-dir,
    .btn-unapprove-all-dekom,
    .btn-unapprove-all-dir {
        transition: all 0.3s ease;
        font-weight: 600;
    }

    .btn-approve-all-dekom:active,
    .btn-approve-all-dir:active,
    .btn-unapprove-all-dekom:active,
    .btn-unapprove-all-dir:active {
        transform: scale(0.95);
    }

    .btn-processing {
        opacity: 0.7;
        pointer-events: none;
    }

    .btn-outline-secondary {
        border: 2px solid #6b7280;
        color: #6b7280;
    }

    .btn-outline-secondary:hover {
        background-color: #6b7280;
        color: white;
    }

    @media (max-width: 768px) {
        .modal-dialog {
            margin: 10px;
        }

        .cardpilihfaktor {
            max-width: 100%;
        }

        .btn-group .btn {
            font-size: 12px;
            padding: 4px 8px;
        }
    }

    @media (max-width: 768px) {

        /* Container & Layout */
        .container-fluid {
            padding: 10px;
        }

        /* Alert Info */
        .alert-info {
            font-size: 12px;
            padding: 10px;
            margin-bottom: 10px;
        }

        .alert-info strong {
            display: block;
            margin-bottom: 5px;
        }

        /* Back Button */
        .d-flex.justify-content-start {
            margin-bottom: 10px !important;
        }

        .btn-outline-secondary {
            font-size: 13px;
            padding: 6px 12px;
        }

        /* Approval Card */
        .card.shadow-sm {
            margin-bottom: 15px;
        }

        .card-body.py-3 {
            padding: 10px !important;
        }

        /* Approval Badges */
        #badge-approval-dekom,
        #badge-approval-dir {
            font-size: 11px !important;
            padding: 8px 10px;
            line-height: 1.4;
        }

        /* Approval Buttons Row */
        .row.mt-3 {
            margin-top: 10px !important;
        }

        .row.mt-3>div {
            margin-bottom: 10px;
            padding: 0 5px;
        }

        .row.mt-3 .col-md-3 {
            width: 100%;
            max-width: 100%;
            flex: 0 0 100%;
            margin-left: 0 !important;
        }

        /* Approval Card Buttons */
        .row.mt-3 .card {
            height: auto !important;
            min-height: 100px;
        }

        .row.mt-3 .card-body {
            padding: 10px;
        }

        .row.mt-3 .badge {
            font-size: 11px;
        }

        .row.mt-3 .btn {
            font-size: 12px;
            padding: 6px 12px;
        }

        /* Factor Cards */
        .cardpilihfaktor {
            max-width: 100%;
            margin: 10px 0;
        }

        .cardpilihfaktor-header {
            font-size: 14px;
            padding: 8px;
        }

        .cardpilihfaktor-body {
            padding: 10px;
        }

        /* Main Factor Card */
        .card.shadow.mb-4 {
            margin-bottom: 15px !important;
        }

        .card-header.py-3 {
            padding: 10px !important;
        }

        .card-header h5 {
            font-size: 16px;
            margin: 0;
        }

        .card-body {
            padding: 10px;
        }

        /* Category Sections */
        .mb-4 {
            margin-bottom: 15px !important;
        }

        h6.font-weight-bold {
            font-size: 14px;
            margin-bottom: 10px !important;
        }

        /* Table Responsive */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .table {
            font-size: 11px;
            min-width: 800px;
        }

        .table thead th {
            font-size: 10px;
            padding: 8px 4px;
            white-space: nowrap;
        }

        .table tbody td {
            padding: 8px 4px;
            font-size: 11px;
        }

        /* Table Buttons */
        .table .btn-sm {
            padding: 4px 8px;
            font-size: 11px;
        }

        .table .btn i {
            font-size: 11px;
        }

        /* Badge in Table */
        .table .badge {
            font-size: 10px;
            padding: 4px 6px;
        }

        /* Notification Badge */
        .notification-badge {
            font-size: 9px;
            padding: 2px 5px;
            min-width: 16px;
            top: -5px;
            right: -5px;
        }

        /* Kesimpulan Section */
        .alert.alert-primary {
            padding: 10px;
        }

        .alert.alert-primary .row {
            flex-direction: column;
        }

        .alert.alert-primary .col-md-2,
        .alert.alert-primary .col-md-10 {
            width: 100%;
            max-width: 100%;
            margin-bottom: 10px;
        }

        .alert.alert-primary .display-4 {
            font-size: 2rem;
        }

        .alert.alert-primary h6 {
            font-size: 13px;
        }

        /* Kesimpulan Cards */
        .row.mt-3 .col-md-6 {
            width: 100%;
            max-width: 100%;
            flex: 0 0 100%;
            margin-bottom: 10px;
        }

        .card.border-success,
        .card.border-danger {
            margin-bottom: 10px;
        }

        .card.border-success .card-header,
        .card.border-danger .card-header {
            font-size: 12px;
            padding: 8px;
        }

        .card.border-success .card-body,
        .card.border-danger .card-body {
            padding: 10px;
            font-size: 12px;
        }

        /* Buttons */
        .btn {
            font-size: 13px;
        }

        .btn-edit-kesimpulan {
            float: none !important;
            display: block;
            width: 100%;
            margin-top: 10px;
            margin-bottom: 10px;
        }

        /* Modal Adjustments */
        .modal-dialog {
            margin: 10px;
            max-width: calc(100% - 20px);
        }

        .modal-dialog.modal-lg,
        .modal-dialog.modal-xl {
            max-width: calc(100% - 20px);
        }

        .modal-header {
            padding: 10px 15px;
        }

        .modal-header .modal-title {
            font-size: 16px;
        }

        .modal-body {
            padding: 15px !important;
        }

        .modal-footer {
            padding: 10px 15px;
            flex-wrap: wrap;
        }

        .modal-footer .btn {
            margin: 5px;
            flex: 1 1 auto;
            min-width: 100px;
        }

        /* Form Groups in Modal */
        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            font-size: 13px;
            margin-bottom: 5px;
        }

        .form-control {
            font-size: 13px;
            padding: 8px;
        }

        textarea.form-control {
            min-height: 80px;
        }

        .form-text {
            font-size: 11px;
        }

        /* Komentar List */
        #komentarLamaList {
            max-height: 300px;
            font-size: 12px;
        }

        .komentar-item {
            padding: 10px !important;
            margin-bottom: 10px !important;
        }

        .komentar-item strong {
            font-size: 12px;
        }

        .komentar-item small {
            font-size: 10px;
        }

        .komentar-item p {
            font-size: 12px;
            margin-top: 5px;
        }

        .badge.badge-success {
            font-size: 9px;
            padding: 2px 5px;
        }

        /* Toast Notification */
        .toast-notification,
        .custom-toast {
            left: 10px;
            right: 10px;
            top: 10px;
            min-width: auto;
            width: calc(100% - 20px);
            font-size: 13px;
            padding: 12px 15px;
        }

        .toast-notification i,
        .custom-toast i {
            font-size: 16px;
        }

        /* Skeleton Loading */
        .skeleton-line {
            height: 12px !important;
        }

        /* HR Divider */
        .my-4 {
            margin-top: 20px !important;
            margin-bottom: 20px !important;
        }

        /* Spacing Utilities */
        .mt-3 {
            margin-top: 15px !important;
        }

        .mb-3 {
            margin-bottom: 15px !important;
        }

        .p-3 {
            padding: 10px !important;
        }
    }

    /* Tablet (768px - 1024px) */
    @media (min-width: 768px) and (max-width: 1024px) {
        .row.mt-3 .col-md-3 {
            width: 50%;
            max-width: 50%;
            flex: 0 0 50%;
        }

        .table {
            font-size: 12px;
        }

        .table thead th {
            font-size: 11px;
            padding: 10px 6px;
        }

        .table tbody td {
            padding: 10px 6px;
        }

        .modal-dialog.modal-lg {
            max-width: 90%;
        }

        .modal-dialog.modal-xl {
            max-width: 95%;
        }

        .alert.alert-primary .col-md-2 {
            width: 30%;
            max-width: 30%;
            flex: 0 0 30%;
        }

        .alert.alert-primary .col-md-10 {
            width: 70%;
            max-width: 70%;
            flex: 0 0 70%;
        }
    }

    /* Small Mobile (max-width: 480px) */
    @media (max-width: 480px) {
        .card-header h5 {
            font-size: 14px;
        }

        .card-header h5 i {
            display: block;
            margin-bottom: 5px;
        }

        .table {
            font-size: 10px;
            min-width: 700px;
        }

        .table thead th {
            font-size: 9px;
            padding: 6px 3px;
        }

        .table tbody td {
            padding: 6px 3px;
            font-size: 10px;
        }

        .btn-sm {
            padding: 3px 6px;
            font-size: 10px;
        }

        .modal-footer .btn {
            font-size: 12px;
            padding: 8px 12px;
        }

        #badge-approval-dekom,
        #badge-approval-dir {
            font-size: 10px !important;
            padding: 6px 8px;
        }
    }

    /* Landscape Mobile */
    @media (max-width: 896px) and (orientation: landscape) {
        .modal-dialog {
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-body {
            max-height: 60vh;
            overflow-y: auto;
        }

        #komentarLamaList {
            max-height: 200px;
        }
    }

    /* iOS Safe Area */
    @supports (padding: max(0px)) {
        @media (max-width: 768px) {
            .container-fluid {
                padding-left: max(10px, env(safe-area-inset-left));
                padding-right: max(10px, env(safe-area-inset-right));
            }

            .toast-notification,
            .custom-toast {
                top: max(10px, env(safe-area-inset-top));
                left: max(10px, env(safe-area-inset-left));
                right: max(10px, env(safe-area-inset-right));
            }
        }
    }

    /* Prevent horizontal scroll */
    body {
        overflow-x: hidden;
    }

    .table-responsive {
        margin-bottom: 1rem;
    }

    /* Touch-friendly clickable areas */
    @media (max-width: 768px) {

        button,
        a.btn,
        .btn {
            min-height: 44px;
            min-width: 44px;
        }

        .table .btn-sm {
            min-height: 36px;
            min-width: 36px;
        }
    }

    /* Hide scrollbar but keep functionality */
    .table-responsive::-webkit-scrollbar {
        height: 6px;
    }

    .table-responsive::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    .table-responsive::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 3px;
    }

    .table-responsive::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
</style>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

<script>
    const GLOBAL_KODEBPR = '<?= $kodebpr ?? '' ?>';
    const GLOBAL_PERIODE_ID = '<?= session('active_periode') ?? '' ?>';
    const GLOBAL_USER_ID = '<?= user_id() ?? '' ?>';
    const BASE_URL = '<?= base_url() ?>';
    const CSRF_TOKEN_NAME = '<?= csrf_token() ?>';
    const CSRF_HASH = '<?= csrf_hash() ?>';

    const USER_IN_GROUP_ADMIN = <?= json_encode($userInGroupAdmin ?? false) ?>;
    const USER_IN_GROUP_PE = <?= json_encode($userInGroupPE ?? false) ?>;
    const USER_IN_GROUP_DEKOM = <?= json_encode($userInGroupDekom ?? false) ?>;
    const USER_IN_GROUP_DEKOM2 = <?= json_encode($userInGroupDekom2 ?? false) ?>;
    const USER_IN_GROUP_DIREKSI = <?= json_encode($userInGroupDireksi ?? false) ?>;
    const USER_IN_GROUP_DIREKSI2 = <?= json_encode($userInGroupDireksi2 ?? false) ?>;

    const CAN_EDIT = USER_IN_GROUP_ADMIN || USER_IN_GROUP_PE;
    const CAN_APPROVE_DEKOM = USER_IN_GROUP_ADMIN || USER_IN_GROUP_DEKOM || USER_IN_GROUP_DEKOM2;
    const CAN_APPROVE_DIR = USER_IN_GROUP_ADMIN || USER_IN_GROUP_DIREKSI || USER_IN_GROUP_DIREKSI2;

    const PREV_SEMESTER = <?= json_encode((int) ($prevSemLabel6 ?? 0)) ?>;
    const PREV_TAHUN = <?= json_encode((int) ($prevTahunLabel6 ?? 0)) ?>;
    const KETERANGAN_MAX = 2000;

    class DataCache {
        constructor(ttl = 60000) {
            this.cache = new Map();
            this.ttl = ttl;
        }

        set(key, value) {
            this.cache.set(key, {
                value: value,
                expiry: Date.now() + this.ttl
            });
        }

        get(key) {
            const item = this.cache.get(key);
            if (!item) return null;

            if (Date.now() > item.expiry) {
                this.cache.delete(key);
                return null;
            }

            return item.value;
        }

        clear() {
            this.cache.clear();
        }
    }

    const approvalCache = new DataCache(30000);
    const unreadCache = new DataCache(15000);
    const factorsCache = new DataCache(60000);
    const syncCheckCache = new DataCache(60000);

    class RequestQueue {
        constructor() {
            this.queue = [];
            this.processing = false;
        }

        async add(requestFn) {
            return new Promise((resolve, reject) => {
                this.queue.push({ requestFn, resolve, reject });
                this.process();
            });
        }

        async process() {
            if (this.processing || this.queue.length === 0) return;

            this.processing = true;
            const { requestFn, resolve, reject } = this.queue.shift();

            try {
                const result = await requestFn();
                resolve(result);
            } catch (error) {
                reject(error);
            } finally {
                this.processing = false;
                this.process();
            }
        }
    }

    const requestQueue = new RequestQueue();

    const escapeHtml = (text) => {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    };

    function throttle(func, limit) {
        let inThrottle;
        return function (...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func(...args), wait);
        };
    }

    function showToast(type, message) {
        const bgColor = type === 'success' ? '#dc3545' : '#28a745';
        const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';

        const toast = $(`
        <div class="custom-toast" style="
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${bgColor};
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideInRight 0.3s ease;
        ">
            <i class="fas fa-${icon}" style="font-size: 20px;"></i>
            <span>${message}</span>
        </div>
    `);

        $('body').append(toast);

        setTimeout(() => {
            toast.fadeOut(300, function () {
                $(this).remove();
            });
        }, 3000);
    }


    // SMART POLLING
    let consecutiveErrors = 0;
    const MAX_CONSECUTIVE_ERRORS = 5;

    // SMART POLLING
    let pollingInterval;
    let userActive = true;
    let lastPollTime = 0;
    const POLL_INTERVAL = 30000;
    const MIN_POLL_GAP = 30000;
    const USER_INACTIVE_TIMEOUT = 30000;
    const CACHE_TTL = 120000;

    // Detect user activity
    let activityTimeout;
    ['mousemove', 'keypress', 'scroll', 'click'].forEach(event => {
        document.addEventListener(event, () => {
            clearTimeout(activityTimeout);
            userActive = true;
            activityTimeout = setTimeout(() => {
                userActive = false;
            }, 5000);
        });
    });

    async function batchPollData() {
        const now = Date.now();

        if (!userActive || (now - lastPollTime < MIN_POLL_GAP)) {
            return;
        }

        if (consecutiveErrors >= MAX_CONSECUTIVE_ERRORS) {
            console.error('Too many consecutive errors, stopping polling');
            stopPolling();
            showToast('Error', 'Koneksi bermasalah. Silakan refresh halaman.', 'error');
            return;
        }

        try {
            const promises = [];

            if (!approvalCache.get('approval_status')) {
                promises.push(checkApprovalStatus());
            }

            if (!unreadCache.get('unread_counts')) {
                promises.push(pollUnreadCounts());
            }

            if (promises.length > 0) {
                await Promise.allSettled(promises);
                lastPollTime = now;
            }

            consecutiveErrors = 0;

        } catch (error) {
            consecutiveErrors++;
            console.error('Batch poll error:', error);
        }
    }

    function startSmartPolling() {
        if (pollingInterval) clearInterval(pollingInterval);
        pollingInterval = setInterval(batchPollData, POLL_INTERVAL);
    }

    function stopPolling() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
    }

    // APPROVAL STATUS
    async function checkApprovalStatus() {
        const cached = approvalCache.get('approval_status');
        if (cached) {
            updateApprovalBadges(cached);
            return cached;
        }

        return requestQueue.add(async () => {
            try {
                const response = await $.ajax({
                    url: BASE_URL + '/Faktor6/getApprovalStatus',
                    method: 'GET',
                    data: {
                        kodebpr: GLOBAL_KODEBPR,
                        periode_id: GLOBAL_PERIODE_ID
                    },
                    timeout: 10000,
                    cache: false
                });

                if (response.status === 'success') {
                    approvalCache.set('approval_status', response);
                    updateApprovalBadges(response);
                }

                return response;
            } catch (error) {
                console.error('Error checking approval status:', error);
                throw error;
            }
        });
    }

    function updateApprovalBadges(response) {
        updateApprovalBadgeDekom(response.dekom_approved, response.dekom_approved_at);
        updateApprovalBadgeDir(response.dir_approved, response.dir_approved_at, response.can_approve_dir);
    }

    function updateApprovalBadgeDekom(allApproved, approvedAt = null) {
        const badge = $('#badge-approval-dekom');
        const timeStr = formatDateTime(approvedAt);

        badge.fadeOut(150, function () {
            if (allApproved) {
                badge.removeClass('badge-secondary').addClass('badge-success');
                badge.html('Disetujui oleh <strong>Komisaris Utama</strong>' +
                    (timeStr ? '<br>' + timeStr : ''));
            } else {
                badge.removeClass('badge-success').addClass('badge-secondary');
                badge.html('Belum Disetujui Seluruhnya<br>Oleh Komisaris Utama');
            }
            badge.fadeIn(150);
        });
    }

    function updateApprovalBadgeDir(allApproved, approvedAt = null, canApprove = true) {
        const badge = $('#badge-approval-dir');
        const card = $('#card-approval-dir');
        const btnApprove = $('#btn-approve-all-dir');
        const btnUnapprove = $('.btn-unapprove-all-dir');
        const timeStr = formatDateTime(approvedAt);

        badge.fadeOut(150, function () {
            if (allApproved) {
                badge.removeClass('badge-secondary').addClass('badge-success');
                badge.html('Disetujui oleh <strong>Direktur Utama</strong>' +
                    (timeStr ? '<br>' + timeStr : ''));
            } else {
                badge.removeClass('badge-success').addClass('badge-secondary');
                badge.html('Belum Disetujui Seluruhnya<br>Oleh Direktur Utama');
            }
            badge.fadeIn(150);
        });

        if (card.length) {
            if (!canApprove) {
                card.addClass('disabled-card');
                btnApprove.addClass('disabled-btn').prop('disabled', true);
                btnUnapprove.addClass('disabled-btn').prop('disabled', true);
            } else {
                card.removeClass('disabled-card');
                btnApprove.removeClass('disabled-btn').prop('disabled', false);
                btnUnapprove.removeClass('disabled-btn').prop('disabled', false);
            }
        }
    }

    async function pollUnreadCounts() {
        try {
            const response = await $.ajax({
                url: BASE_URL + '/faktor6/getAllUnreadCounts',
                method: 'GET',
                timeout: 10000,
                cache: false
            });

            if (response.status === 'success') {
                updateAllBadges(response.counts);
            }

            return response.counts;
        } catch (error) {
            console.error('Error polling unread counts:', error);
            return {};
        }
    }

    function isNewComment(commentCreatedAt) {
        if (!lastReadTime) return true;

        const commentTime = new Date(commentCreatedAt).getTime();
        const lastRead = new Date(lastReadTime).getTime();

        return commentTime > lastRead;
    }


    function updateAllBadges(counts) {
        Object.keys(counts).forEach(faktor6Id => {
            updateBadge(faktor6Id, counts[faktor6Id]);
        });
    }

    function updateBadge(faktor6Id, count) {
        const badge = $(`#notification-badge-${faktor6Id}`);
        badge.text(count);
        if (count > 0) {
            badge.addClass('show');
        } else {
            badge.removeClass('show');
        }
    }

    function getBadgeColor(nilai) {
        const colors = { 1: 'info', 2: 'success', 3: 'warning', 4: 'danger', 5: 'dark' };
        return colors[nilai] || 'secondary';
    }

    function renderEditButtons(item, hasData) {
        if (!hasData) {
            return `
                <button type="button" class="btn btn-sm btn-success btn-add" 
                        data-id="${item.id}" 
                        data-sub_category="${escapeHtml(item.sub_category)}">
                    <i class="fas fa-plus"></i>
                </button>
            `;
        }

        return `
            <button type="button" class="btn btn-sm btn-warning btn-edit" 
                    data-id="${item.id}"
                    data-sub_category="${escapeHtml(item.sub_category)}"
                    data-nilai="${item.nilai}"
                    data-keterangan="${escapeHtml(item.keterangan)}">
                <i class="fa fa-edit"></i>
            </button>
        `;
    }

    function renderKomentarButton(faktor6Id) {
        return `
        <button type="button" class="btn btn-sm btn-primary position-relative komentar-button"
                data-faktor-id="${faktor6Id}">
            <i class="fas fa-comment"></i>
            <span id="notification-badge-${faktor6Id}" class="notification-badge">0</span>
        </button>
    `;
    }

    function renderApprovalButton(item, field, type) {
        const isApproved = item[field] == 1;
        const btnClass = isApproved ? 'btn-success' : 'btn-dark';
        const icon = isApproved ? 'check' : 'times';
        const action = isApproved ? `unapproved${type}` : `acc${type}`;

        return `
            <td class="text-center">
                <form action="${BASE_URL}/Faktor6/${action}" method="POST" class="form-approval" style="display: inline-block;">
                    <input type="hidden" name="${CSRF_TOKEN_NAME}" value="${CSRF_HASH}">
                    <input type="hidden" name="faktor6id" value="${item.id}">
                    <input type="hidden" name="kodebpr" value="${GLOBAL_KODEBPR}">
                    <input type="hidden" name="periode_id" value="${GLOBAL_PERIODE_ID}">
                    <button type="submit" class="btn btn-sm ${btnClass}">
                        <i class="fas fa-${icon}-circle"></i>
                    </button>
                </form>
            </td>
        `;
    }

    function renderFactorRow(item, index) {
        const hasData = item.nilai && item.keterangan;

        return `
            <tr>
                <td class="text-center">${index + 1}</td>
                <td style="text-align: left;">${escapeHtml(item.sub_category || '')}</td>
                <td class="text-center">
                    ${item.nilai ? `<span class="badge badge-${getBadgeColor(item.nilai)}">${item.nilai}</span>` : '-'}
                </td>
                <td style="text-align: left;">${escapeHtml(item.keterangan || '-')}</td>
                <td class="text-center">
                    ${CAN_EDIT ? renderEditButtons(item, hasData) : '-'}
                </td>
                <td class="text-center">
                    ${renderKomentarButton(item.id)}
                </td>
                ${CAN_APPROVE_DEKOM ? renderApprovalButton(item, 'accdekom', 'dekom') : ''}
                ${CAN_APPROVE_DEKOM ? renderApprovalButton(item, 'accdekom2', 'dekom2') : ''}
                ${CAN_APPROVE_DIR ? renderApprovalButton(item, 'accdir2', 'dir2') : ''}
            </tr>
        `;
    }

    function renderCategorySection(title, items) {
        if (!items || items.length === 0) {
            return `
                <div class="mb-4">
                    <h6 class="font-weight-bold mb-3">${title}</h6>
                    <div class="alert alert-info">Tidak ada data untuk kategori ini</div>
                </div>
            `;
        }

        return `
            <div class="mb-4">
                <h6 class="font-weight-bold mb-3">${title}</h6>
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 5%;" class="text-center">No</th>
                                <th style="width: 30%;">Sub Kategori</th>
                                <th style="width: 5%;" class="text-center">Nilai</th>
                                <th style="width: 35%;">Keterangan</th>
                                <th style="width: 5%;" class="text-center">Aksi</th>
                                <th style="width: 5%;" class="text-center">Komentar</th>
                                ${CAN_APPROVE_DEKOM ? '<th style="width: 3%;" class="text-center">Komut</th>' : ''}
                                ${CAN_APPROVE_DEKOM ? '<th style="width: 3%;" class="text-center">Komisaris</th>' : ''}
                                ${CAN_APPROVE_DIR ? '<th style="width: 3%;" class="text-center">Direksi</th>' : ''}
                            </tr>
                        </thead>
                        <tbody>
                            ${items.map((item, index) => renderFactorRow(item, index)).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }

    function renderKesimpulan(data) {
        data = data || {};

        const fields = [
            { key: 'positifstruktur', label: 'Faktor Positif (Struktur)', icon: 'plus-circle', color: 'success' },
            { key: 'negatifstruktur', label: 'Faktor Negatif (Struktur)', icon: 'minus-circle', color: 'danger' },
            { key: 'positifproses', label: 'Faktor Positif (Proses)', icon: 'plus-circle', color: 'success' },
            { key: 'negatifproses', label: 'Faktor Negatif (Proses)', icon: 'minus-circle', color: 'danger' },
            { key: 'positifhasil', label: 'Faktor Positif (Hasil)', icon: 'plus-circle', color: 'success' },
            { key: 'negatifhasil', label: 'Faktor Negatif (Hasil)', icon: 'minus-circle', color: 'danger' }
        ];

        let html = '<div class="row mt-3">';
        fields.forEach(field => {
            const value = data[field.key];
            const content = value
                ? `<p class="mb-0">${escapeHtml(value)}</p>`
                : `<p class="mb-0 text-danger font-weight-bold">Belum diisi</p>`;

            html += `
        <div class="col-md-6 mb-3">
            <div class="card border-${field.color}">
                <div class="card-header bg-${field.color} text-white">
                    <i class="fas fa-${field.icon}"></i> ${field.label}
                </div>
                <div class="card-body">
                    ${content}
                </div>
            </div>
        </div>
    `;
        });
        html += '</div>';

        return html;
    }

    // SESUDAH
    function renderFactors(data, rataRata, penjelasan) {
        const kesimpulanButton = CAN_EDIT ? `
        <button type="button" class="btn btn-primary btn-sm btn-edit-kesimpulan" 
                data-positifstruktur="${escapeHtml(data.kesimpulan?.positifstruktur || '')}"
                data-negatifstruktur="${escapeHtml(data.kesimpulan?.negatifstruktur || '')}"
                data-positifproses="${escapeHtml(data.kesimpulan?.positifproses || '')}"
                data-negatifproses="${escapeHtml(data.kesimpulan?.negatifproses || '')}"
                data-positifhasil="${escapeHtml(data.kesimpulan?.positifhasil || '')}"
                data-negatifhasil="${escapeHtml(data.kesimpulan?.negatifhasil || '')}">
            <i class="fa fa-edit"></i> Faktor Positif & Faktor Negatif
        </button>
    ` : '';

        let html = `
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-gradient-primary text-white">
                <h5 class="m-0">
                    <i class="fas fa-chart-line"></i> Faktor 6 - Penerapan Fungsi Kepatuhan.
                </h5>
            </div>
            <div class="card-body">
                ${renderCategorySection('A. Struktur dan Infrastruktur Tata Kelola (S)', data.struktur)}
                ${renderCategorySection('B. Proses Penerapan Tata Kelola (P)', data.proses)}
                ${renderCategorySection('C. Hasil Penerapan Tata Kelola (H)', data.hasil)}
                
                <hr class="my-4">
                
                <div class="mt-4">
                    <h5 class="font-weight-bold mb-3">
                        <i class="fas fa-file-alt"></i> Kesimpulan Penilaian
                    </h5>
                    
                    <div class="alert alert-primary">
                        <div class="row">
                            <div class="col-md-2">
                                <h2 class="display-4 text-center mb-0">${rataRata || '-'}</h2>
                                <p class="text-center small mb-0">Nilai Faktor</p>
                            </div>
                            <div class="col-md-10">
                                <h6 class="font-weight-bold">Penjelasan Nilai:</h6>
                                <div style="text-align: justify;">${penjelasan || 'Belum ada penilaian'}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-left mb-2">
                        ${kesimpulanButton}
                    </div>
                    ${renderKesimpulan(data.kesimpulan)}
                </div>
            </div>
        </div>
    `;

        $('#factors-container').html(html);
    }

    async function loadFactorsData() {
        const cached = factorsCache.get('factors_data');
        if (cached) {
            renderFactors(cached.data, cached.rataRata, cached.penjelasan);
            $('#factors-skeleton').hide();
            $('#factors-container').fadeIn(300);
            attachEventHandlers();
            return;
        }

        try {
            $('#factors-skeleton').show();
            $('#factors-container').hide();

            const response = await $.ajax({
                url: BASE_URL + '/Faktor6/getFactorsData',
                method: 'GET',
                timeout: 15000
            });

            if (response.status === 'success') {
                factorsCache.set('factors_data', response);
                renderFactors(response.data, response.rataRata, response.penjelasan);
                $('#factors-skeleton').hide();
                $('#factors-container').fadeIn(300);
                attachEventHandlers();
                pollUnreadCounts();
            }
        } catch (error) {
            console.error('Error loading factors:', error);
            $('#factors-skeleton').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Gagal memuat data. 
                    <a href="#" onclick="location.reload()" class="alert-link">Klik di sini untuk refresh</a>
                </div>
            `);
        }
    }

    function attachEventHandlers() {
        $(document).off('click', '.btn-edit').on('click', '.btn-edit', function () {
            const $this = $(this);
            $('#edit-faktor-id').val($this.data('id'));
            $('#edit-sub_category').val($this.data('sub_category'));
            $('#edit-nilai').val($this.data('nilai'));
            $('#edit-keterangan').val($this.data('keterangan'));
            $('#modalUbah').modal('show');
        });

        $(document).off('click', '.btn-add').on('click', '.btn-add', function () {
            const $this = $(this);
            $('#add-faktor-id').val($this.data('id'));
            $('#add-sub_category').val($this.data('sub_category'));
            $('#add-nilai').val('');
            $('#add-keterangan').val('');
            $('#modaltambahNilai').modal('show');
        });

        $(document).off('click', '.btn-edit-kesimpulan').on('click', '.btn-edit-kesimpulan', function () {
            const $this = $(this);

            $('#form-kesimpulan')[0]?.reset();
            $('.text-danger[id^="error-"]').hide();
            $('.form-control').removeClass('is-invalid');

            $('#kesimpulan-faktor-id').val(8);

            const getData = (attrName) => {
                const value = $this.data(attrName);
                if (value === undefined || value === null || value === 'undefined' || value === '') {
                    return '';
                }
                return String(value);
            };

            const positifstruktur = getData('positifstruktur');
            const negatifstruktur = getData('negatifstruktur');
            const positifproses = getData('positifproses');
            const negatifproses = getData('negatifproses');
            const positifhasil = getData('positifhasil');
            const negatifhasil = getData('negatifhasil');

            $('#kesimpulan-positifstruktur').val(positifstruktur);
            $('#kesimpulan-negatifstruktur').val(negatifstruktur);
            $('#kesimpulan-positifproses').val(positifproses);
            $('#kesimpulan-negatifproses').val(negatifproses);
            $('#kesimpulan-positifhasil').val(positifhasil);
            $('#kesimpulan-negatifhasil').val(negatifhasil);

            $('#modalUbahkesimpulan').find('.keterangan-limited').trigger('input');

            $('#modalUbahkesimpulan').modal('show');
        });

        $(document).off('click', '.komentar-button').on('click', '.komentar-button', function () {
            const faktor6Id = $(this).data('faktor-id');
            $('#komentar-faktor-id').val(faktor6Id);
            $('#komentar-text').val('');
            loadKomentarList(faktor6Id);
            markCommentsAsRead(faktor6Id);
            $('#modaltambahKomentar').modal('show');
        });

        $(document).off('submit', '.form-approval').on('submit', '.form-approval', function (e) {
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
                    showToast('Berhasil', 'Status approval diubah', 'success');
                    refreshAfterAction();
                },
                error: function (xhr) {
                    button.html(originalHtml).prop('disabled', false);
                    const errorMsg = xhr.responseJSON?.message || 'Gagal mengubah status';
                    showToast('Error', errorMsg, 'error');
                }
            });
        });
    }

    function loadKomentarList(faktor6Id) {
        const list = $('#komentarLamaList');
        list.html('<li class="text-muted"><i class="fas fa-spinner fa-spin"></i> Memuat komentar...</li>');

        $.ajax({
            url: BASE_URL + '/Faktor6/getKomentar',
            method: 'GET',
            data: {
                faktor6_id: faktor6Id
            },
            timeout: 10000,
            cache: false,
            success: function (response) {
                if (Array.isArray(response) && response.length > 0) {
                    let html = '';
                    let hasNewComments = false;

                    // ✅ Reverse array supaya newest di bawah (ASC order dari database)
                    response.forEach(k => {
                        const isNew = isNewComment(k.created_at);
                        if (isNew) hasNewComments = true;

                        const borderColor = isNew ? '#28a745' : '#007bff';
                        const bgColor = isNew ? '#d4edda' : 'white';
                        const newBadge = isNew ? '<span class="badge badge-success ml-2"><i class="fas fa-star"></i> BARU</span>' : '';

                        html += `
                        <li class="komentar-item ${isNew ? 'new-comment' : ''} mb-3 p-3" 
                            style="background: ${bgColor}; border-radius: 6px; border-left: 3px solid ${borderColor}; transition: all 0.3s ease;">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <strong style="color: ${borderColor};">
                                    <i class="fas fa-user-circle"></i> ${escapeHtml(k.fullname || 'Unknown')}
                                    ${newBadge}
                                </strong>
                                <small class="text-muted">
                                    <i class="fas fa-clock"></i> ${formatDateTime(k.created_at)}
                                </small>
                            </div>
                            <p class="mb-0" style="white-space: pre-wrap; word-wrap: break-word;">${escapeHtml(k.komentar)}</p>
                        </li>
                    `;
                    });

                    list.html(html);

                    // ✅ Auto scroll to bottom (komentar terbaru)
                    setTimeout(() => {
                        list.animate({
                            scrollTop: list[0].scrollHeight
                        }, 500);

                        // ✅ Pulse animation untuk komentar baru
                        if (hasNewComments) {
                            $('.new-comment').addClass('pulse-animation');
                            setTimeout(() => {
                                $('.new-comment').removeClass('pulse-animation');
                            }, 2000);
                        }
                    }, 100);
                } else {
                    list.html('<li class="text-muted text-center py-3"><i class="fas fa-comment-slash"></i> Belum ada komentar untuk item ini</li>');
                }
            },
            error: function (xhr, status, error) {
                console.error('Load komentar error:', { xhr, status, error });
                list.html(`
                <li class="text-danger text-center py-3">
                    <i class="fas fa-exclamation-triangle"></i> Gagal memuat komentar. 
                    <a href="#" onclick="loadKomentarList(${faktor6Id}); return false;" class="alert-link">Coba lagi</a>
                </li>
            `);
            }
        });
    }

    function markCommentsAsRead(faktor6Id) {
        $.ajax({
            url: BASE_URL + '/Faktor6/markUserCommentsAsRead',  // ✅ Perbaiki endpoint
            method: 'POST',
            data: {
                faktor6_id: faktor6Id,
                [CSRF_TOKEN_NAME]: CSRF_HASH
            },
            success: function (response) {
                if (response.status === 'success') {
                    updateBadge(faktor6Id, 0);
                    lastReadTime = new Date().toISOString();
                    console.log('✅ Comments marked as read for faktor6Id:', faktor6Id);
                }
            },
            error: function (xhr, status, error) {
                console.error('❌ Mark as read error:', error);
            }
        });
    }

    const submitFormUbah = debounce(function (form) {
        const submitBtn = form.find('button[type="submit"]');
        const originalBtnText = submitBtn.html();

        const data = {
            faktor6id: form.find('[name="faktor6id"]').val(),
            nilai: form.find('[name="nilai"]').val(),
            keterangan: form.find('[name="keterangan"]').val(),
            [CSRF_TOKEN_NAME]: CSRF_HASH
        };

        if (!data.faktor6id || !data.keterangan.trim()) {
            showToast('Error', 'Mohon lengkapi semua field', 'error');
            return;
        }

        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...').prop('disabled', true);

        $.ajax({
            url: BASE_URL + '/Faktor6/ubah',
            method: 'POST',
            data: data,
            timeout: 15000,
            success: function (response) {
                console.log('Response ubah:', response);

                $('#modalUbah').modal('hide');
                showToast('Berhasil', 'Data berhasil diubah', 'success');
                refreshAfterAction();
            },
            error: function (xhr, status, error) {
                console.error('Error ubah:', { xhr, status, error });
                const errorMsg = xhr.responseJSON?.message || 'Gagal menyimpan data';
                showToast('Error', errorMsg, 'error');
            },
            complete: function () {
                submitBtn.html(originalBtnText).prop('disabled', false);
            }
        });
    });

    const submitFormTambah = debounce(function (form) {
        const submitBtn = form.find('button[type="submit"]');
        const originalBtnText = submitBtn.html();

        const data = {
            faktor6_id: form.find('[name="faktor6_id"]').val(),
            nilai: form.find('[name="nilai"]').val(),
            keterangan: form.find('[name="keterangan"]').val(),
            fullname: form.find('[name="fullname"]').val(),
            tambahNilai: true,
            [CSRF_TOKEN_NAME]: CSRF_HASH
        };

        if (!data.faktor6_id || !data.nilai || !data.keterangan.trim()) {
            showToast('Error', 'Mohon lengkapi semua field', 'error');
            return;
        }

        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...').prop('disabled', true);

        $.ajax({
            url: BASE_URL + '/Faktor6/tambahNilai',
            method: 'POST',
            data: data,
            timeout: 15000,
            success: function (response) {
                console.log('Response tambah:', response);

                $('#modaltambahNilai').modal('hide');
                form[0].reset();
                showToast('Berhasil', 'Data berhasil ditambahkan', 'success');
                refreshAfterAction();
            },
            error: function (xhr, status, error) {
                console.error('Error tambah:', { xhr, status, error });
                const errorMsg = xhr.responseJSON?.message || 'Gagal menyimpan data';
                showToast('Error', errorMsg, 'error');
            },
            complete: function () {
                submitBtn.html(originalBtnText).prop('disabled', false);
            }
        });
    });

    const submitFormKesimpulan = debounce(function (form) {
        const submitBtn = form.find('button[type="submit"]');
        const originalBtnText = submitBtn.html();

        $('.text-danger[id^="error-"]').hide();
        $('.form-control').removeClass('is-invalid');

        const fields = {
            positifstruktur: $('#kesimpulan-positifstruktur').val(),
            negatifstruktur: $('#kesimpulan-negatifstruktur').val(),
            positifproses: $('#kesimpulan-positifproses').val(),
            negatifproses: $('#kesimpulan-negatifproses').val(),
            positifhasil: $('#kesimpulan-positifhasil').val(),
            negatifhasil: $('#kesimpulan-negatifhasil').val()
        };

        let hasError = false;
        const emptyFields = [];

        Object.keys(fields).forEach(key => {
            const value = fields[key];
            const isEmpty = !value || (typeof value === 'string' && value.trim() === '');

            if (isEmpty) {
                hasError = true;
                emptyFields.push(key);
                $(`#error-${key}`).show();
                $(`#kesimpulan-${key}`).addClass('is-invalid');
            }
        });

        if (hasError) {
            const fieldNames = {
                positifstruktur: 'Faktor Positif (Struktur)',
                negatifstruktur: 'Faktor Negatif (Struktur)',
                positifproses: 'Faktor Positif (Proses)',
                negatifproses: 'Faktor Negatif (Proses)',
                positifhasil: 'Faktor Positif (Hasil)',
                negatifhasil: 'Faktor Negatif (Hasil)'
            };

            const emptyFieldNames = emptyFields.map(key => fieldNames[key]);

            showToast('Error', `Mohon lengkapi: ${emptyFieldNames.join(', ')}`, 'error');

            const firstErrorField = $(`#kesimpulan-${emptyFields[0]}`);
            firstErrorField.focus();

            return false;
        }

        // Semua validasi passed
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...').prop('disabled', true);

        const formData = {
            faktor6id: $('#kesimpulan-faktor-id').val(),
            positifstruktur: fields.positifstruktur.trim(),
            negatifstruktur: fields.negatifstruktur.trim(),
            positifproses: fields.positifproses.trim(),
            negatifproses: fields.negatifproses.trim(),
            positifhasil: fields.positifhasil.trim(),
            negatifhasil: fields.negatifhasil.trim(),
            ubahkesimpulan: true,
            [CSRF_TOKEN_NAME]: CSRF_HASH
        };

        $.ajax({
            url: BASE_URL + '/Faktor6/ubahkesimpulan',
            method: 'POST',
            data: formData,
            timeout: 15000,
            success: function (response) {
                console.log('Response kesimpulan:', response);
                $('#modalUbahkesimpulan').modal('hide');
                showToast('Berhasil', 'Kesimpulan berhasil disimpan', 'success');
                refreshAfterAction();
            },
            error: function (xhr, status, error) {
                console.error('Error kesimpulan:', { xhr, status, error });
                const errorMsg = xhr.responseJSON?.message || 'Gagal menyimpan kesimpulan';
                showToast('Error', errorMsg, 'error');
            },
            complete: function () {
                submitBtn.html(originalBtnText).prop('disabled', false);
            }
        });
    });

    const submitFormKomentar = debounce(function (form) {
        const submitBtn = form.find('button[type="submit"]');
        const originalBtnText = submitBtn.html();
        const komentar = form.find('[name="komentar"]').val().trim();

        if (!komentar) {
            showToast('Error', 'Komentar tidak boleh kosong', 'error');
            return;
        }

        if (komentar.length < 3) {
            showToast('Error', 'Komentar minimal 3 karakter', 'error');
            return;
        }

        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Mengirim...')
            .prop('disabled', true);

        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize() + '&' + CSRF_TOKEN_NAME + '=' + CSRF_HASH,
            timeout: 15000,
            success: function (response) {
                console.log('Komentar response:', response);

                if (response.status === 'success') {
                    form.find('[name="komentar"]').val('');
                    const faktor6Id = form.find('[name="faktor6_id"]').val();

                    loadKomentarList(faktor6Id);

                    showToast('Berhasil', 'Komentar berhasil ditambahkan', 'success');

                    unreadCache.clear();
                    setTimeout(() => pollUnreadCounts(), 500);
                } else {
                    throw new Error(response.message || 'Gagal menambahkan komentar');
                }
            },
            error: function (xhr, status, error) {
                console.error('Komentar error:', { xhr, status, error });

                let errorMsg = 'Gagal menambahkan komentar';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else if (status === 'timeout') {
                    errorMsg = 'Request timeout. Silakan coba lagi.';
                }

                showToast('Error', errorMsg, 'error');
            },
            complete: function () {
                submitBtn.html(originalBtnText).prop('disabled', false);
            }
        });
    }, 500);

    // ===== APPROVAL BUTTONS =====
    const handleApproveKomisaris = throttle(function (e) {
        e.preventDefault();

        if (!confirm('Apakah Anda yakin ingin melakukan approval?')) {
            return;
        }

        const btn = $(this);
        const originalHtml = btn.html();

        btn.html('<i class="fas fa-spinner fa-spin"></i> Memproses...')
            .addClass('btn-processing')
            .prop('disabled', true);

        $.ajax({
            url: BASE_URL + '/Faktor6/approveSemuaKom',
            method: 'GET',
            success: function (response) {
                showToast('Berhasil', 'Semua data berhasil disetujui', 'success');
                refreshAfterAction();
            },
            error: function (xhr) {
                showToast('Error', 'Gagal melakukan approval', 'error');
            },
            complete: function () {
                btn.html(originalHtml)
                    .removeClass('btn-processing')
                    .prop('disabled', false);
            }
        });
    }, 2000);

    const handleUnapproveKomisaris = throttle(function (e) {
        e.preventDefault();

        if (!confirm('Batalkan semua approval?')) {
            return;
        }

        const btn = $(this);
        const originalHtml = btn.html();

        btn.html('<i class="fas fa-spinner fa-spin"></i> Memproses...')
            .addClass('btn-processing')
            .prop('disabled', true);

        $.ajax({
            url: BASE_URL + '/Faktor6/unapprovekom',
            method: 'GET',
            success: function (response) {
                showToast('Berhasil', 'Semua approval berhasil dibatalkan', 'success');
                refreshAfterAction();
            },
            error: function (xhr) {
                showToast('Error', 'Gagal membatalkan approval', 'error');
            },
            complete: function () {
                btn.html(originalHtml)
                    .removeClass('btn-processing')
                    .prop('disabled', false);
            }
        });
    }, 2000);

    const handleApproveDirektur = throttle(function (e) {
        e.preventDefault();

        if ($(this).hasClass('disabled-btn')) {
            showToast('Perhatian', 'Komisaris harus menyetujui terlebih dahulu', 'error');
            return;
        }

        if (!confirm('Apakah Anda yakin ingin melakukan approval?')) {
            return;
        }

        const btn = $(this);
        const originalHtml = btn.html();

        btn.html('<i class="fas fa-spinner fa-spin"></i> Memproses...')
            .addClass('btn-processing')
            .prop('disabled', true);

        $.ajax({
            url: BASE_URL + '/Faktor6/approveSemua',
            method: 'GET',
            success: function (response) {
                showToast('Berhasil', 'Semua data berhasil disetujui', 'success');
                refreshAfterAction();
            },
            error: function (xhr) {
                showToast('Error', 'Gagal melakukan approval', 'error');
            },
            complete: function () {
                btn.html(originalHtml)
                    .removeClass('btn-processing')
                    .prop('disabled', false);
            }
        });
    }, 2000);

    const handleUnapproveDirektur = throttle(function (e) {
        e.preventDefault();

        if (!confirm('Batalkan semua approval?')) {
            return;
        }

        const btn = $(this);
        const originalHtml = btn.html();

        btn.html('<i class="fas fa-spinner fa-spin"></i> Memproses...')
            .addClass('btn-processing')
            .prop('disabled', true);

        $.ajax({
            url: BASE_URL + '/Faktor6/unapproveSemua',
            method: 'GET',
            success: function (response) {
                showToast('Berhasil', 'Semua approval berhasil dibatalkan', 'success');
                refreshAfterAction();
            },
            error: function (xhr) {
                showToast('Error', 'Gagal membatalkan approval', 'error');
            },
            complete: function () {
                btn.html(originalHtml)
                    .removeClass('btn-processing')
                    .prop('disabled', false);
            }
        });
    }, 2000);

    function clearAllCaches() {
        approvalCache.clear();
        unreadCache.clear();
        factorsCache.clear();
        syncCheckCache.clear();
    }

    $(document).ready(function () {
        // Initialize lastReadTime
        lastReadTime = new Date().toISOString();

        // Poll unread counts setiap 10 detik
        pollUnreadCounts();
        setInterval(pollUnreadCounts, 10000);

        // Komentar Button Click
        $(document).off('click', '.komentar-button').on('click', '.komentar-button', function () {
            const faktor6Id = $(this).data('faktor-id');
            $('#komentar-faktor-id').val(faktor6Id);
            $('#komentar-text').val('');
            loadKomentarList(faktor6Id);
            markCommentsAsRead(faktor6Id);
            $('#modaltambahKomentar').modal('show');
        });

        // Reset form saat modal ditutup
        $('#modaltambahKomentar').on('hidden.bs.modal', function () {
            $(this).find('form')[0]?.reset();
            $('#komentarLamaList').html('');
        });

        const prevPeriodeLabelDefault = (PREV_SEMESTER && PREV_TAHUN)
            ? 'Semester ' + PREV_SEMESTER + ' Tahun ' + PREV_TAHUN
            : 'periode sebelumnya';

        $('#syncFromPeriodeLabel').text(prevPeriodeLabelDefault);

        $('#btnSyncPrevPeriode').on('click', function () {
            const $btn = $(this);
            if ($btn.prop('disabled')) return;

            const cached = syncCheckCache.get('prev_periode_check');
            if (cached) {
                handleSyncCheckResult(cached, $btn);
                return;
            }

            const originalHtml = $btn.html();
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Memeriksa data...');

            $.ajax({
                url: BASE_URL + '/Faktor6/checkPrevPeriodeData',
                method: 'GET',
                dataType: 'json',
                timeout: 8000,
                cache: false,
                success: function (r) {
                    syncCheckCache.set('prev_periode_check', r);
                    handleSyncCheckResult(r, $btn, originalHtml);
                },
                error: function () {
                    $btn.prop('disabled', false).html(originalHtml);
                    showToast('Error', 'Gagal memeriksa data periode sebelumnya. Silakan coba lagi.', 'error');
                }
            });
        });

        function handleSyncCheckResult(r, $btn, originalHtml) {
            const restoreHtml = originalHtml || '<i class="fas fa-sync-alt"></i> Sinkron Data Periode Sebelumnya';
            const label = r.label || prevPeriodeLabelDefault;

            if (r.status !== 'success') {
                $btn.prop('disabled', false).html(restoreHtml);
                showToast('Error', r.message || 'Gagal memeriksa data periode sebelumnya.', 'error');
                return;
            }

            if (!r.exists) {
                $btn.prop('disabled', true)
                    .addClass('disabled-btn')
                    .html('<i class="fas fa-ban"></i> Tidak Ada Data Periode Sebelumnya');
                showToast('Error', `Tidak ada data pada periode ${label}.`, 'error');
                return;
            }

            $btn.prop('disabled', false).html(restoreHtml);
            $('#syncFromPeriodeLabel').text(label);
            $('#modalSyncPrev').modal('show');
        }

        $('#btnConfirmSync').on('click', function () {
            const $btn = $(this);
            const origHtml = $btn.html();

            $btn.html('<i class="fas fa-spinner fa-spin"></i> Menyinkronkan...')
                .prop('disabled', true);

            $.ajax({
                url: BASE_URL + '/Faktor6/syncFromPrevPeriode',
                method: 'POST',
                data: { [CSRF_TOKEN_NAME]: CSRF_HASH },
                dataType: 'json',
                timeout: 30000,
                success: function (r) {
                    $('#modalSyncPrev').modal('hide');
                    if (r.success) {
                        showSyncResultToast('success',
                            '<i class="fas fa-check-circle fa-lg"></i>',
                            'Sinkronisasi Berhasil', r.message);
                        clearAllCaches();
                        setTimeout(function () { loadFactorsData(); checkApprovalStatus(); }, 800);
                    } else {
                        showSyncResultToast('error',
                            '<i class="fas fa-times-circle fa-lg"></i>',
                            'Sinkronisasi Gagal', r.message);
                    }
                },
                error: function (xhr) {
                    $('#modalSyncPrev').modal('hide');
                    const msg = xhr.status === 0
                        ? 'Tidak dapat terhubung ke server.'
                        : 'Error ' + xhr.status + ': ' + (xhr.responseJSON?.message || 'Terjadi kesalahan.');
                    showSyncResultToast('error',
                        '<i class="fas fa-times-circle fa-lg"></i>',
                        'Error', msg);
                },
                complete: function () {
                    $btn.html(origHtml).prop('disabled', false);
                }
            });
        });

        function showSyncResultToast(type, iconHtml, title, message) {
            const bg = type === 'success' ? '#28a745' : '#dc3545';
            const $t = $('<div>').css({
                position: 'fixed', top: '20px', right: '20px',
                background: bg, color: '#fff',
                padding: '15px 20px', borderRadius: '10px',
                boxShadow: '0 4px 15px rgba(0,0,0,.3)',
                zIndex: 9999, minWidth: '300px',
                display: 'flex', gap: '12px', alignItems: 'flex-start'
            }).html(
                iconHtml +
                '<div><strong style="display:block;margin-bottom:4px;">' + title + '</strong>' +
                '<span style="font-size:13px;">' + message + '</span></div>'
            );
            $('body').append($t);
            setTimeout(function () {
                $t.fadeOut(400, function () { $(this).remove(); });
            }, 5000);
        }
    });

    const refreshAfterAction = throttle(async function () {
        clearAllCaches();

        await Promise.allSettled([
            loadFactorsData(),
            checkApprovalStatus()
        ]);
    }, 2000);

    function formatDateTime(dateStr) {
        if (!dateStr || dateStr === 'null' || dateStr === '0000-00-00 00:00:00') return '';
        const date = new Date(dateStr);
        if (isNaN(date.getTime())) return '';

        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);

        if (diffMins < 1) return 'Baru saja';
        if (diffMins < 60) return `${diffMins} menit yang lalu`;
        if (diffHours < 24) return `${diffHours} jam yang lalu`;
        if (diffDays < 7) return `${diffDays} hari yang lalu`;

        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');

        return `${day}/${month}/${year} ${hours}:${minutes}`;
    }

    $(document).ready(function () {
        setTimeout(() => {
            loadFactorsData();
            checkApprovalStatus();
        }, 100);

        startSmartPolling();

        $('form[action*="Faktor6/ubah"]').on('submit', function (e) {
            e.preventDefault();
            submitFormUbah($(this));
        });

        $('form[action*="Faktor6/tambahNilai"]').on('submit', function (e) {
            e.preventDefault();
            submitFormTambah($(this));
        });

        $('form[action*="Faktor6/ubahkesimpulan"]').on('submit', function (e) {
            e.preventDefault();
            submitFormKesimpulan($(this));
        });

        $('form[action*="Faktor6/tambahKomentar"]').on('submit', function (e) {
            e.preventDefault();
            submitFormKomentar($(this));
        });

        $(document).on('click', '.btn-approve-all-dekom', handleApproveKomisaris);
        $(document).on('click', '.btn-unapprove-all-dekom', handleUnapproveKomisaris);
        $(document).on('click', '.btn-approve-all-dir', handleApproveDirektur);
        $(document).on('click', '.btn-unapprove-all-dir', handleUnapproveDirektur);

        $('.modal').on('hidden.bs.modal', function () {
            $(this).find('form')[0]?.reset();
        });

        $(window).on('beforeunload', function () {
            clearAllCaches();
        });
    });

    $(document).on('click', '#editRatingList .list-group-item', function (e) {
        e.preventDefault();
        $('#editRatingList .list-group-item').removeClass('active');
        $(this).addClass('active');
        const selectedValue = $(this).data('value');
        $('#edit-nilai').val(selectedValue);
        $('#editSelectedValue').text(selectedValue);
    });

    // Handler untuk Modal Tambah
    $(document).on('click', '#addRatingList .list-group-item', function (e) {
        e.preventDefault();
        $('#addRatingList .list-group-item').removeClass('active');
        $(this).addClass('active');
        const selectedValue = $(this).data('value');
        $('#add-nilai').val(selectedValue);
        $('#addSelectedValue').text(selectedValue);
    });

    // Saat Modal Ubah dibuka, set active sesuai nilai yang sudah ada
    $(document).on('click', '.btn-edit', function () {
        const currentNilai = $(this).data('nilai');

        // Reset semua dulu
        $('#editRatingList .list-group-item').removeClass('active');
        $('#edit-nilai').val('');
        $('#editSelectedValue').text('Belum ada');

        // Jika ada nilai sebelumnya, highlight item yang sesuai
        if (currentNilai) {
            const targetItem = $(`#editRatingList .list-group-item[data-value="${currentNilai}"]`);
            targetItem.addClass('active');
            $('#edit-nilai').val(currentNilai);
            $('#editSelectedValue').text(currentNilai);
        }
    });

    // Reset saat Modal Tambah dibuka
    $(document).on('click', '.btn-add', function () {
        $('#addRatingList .list-group-item').removeClass('active');
        $('#add-nilai').val('');
        $('#addSelectedValue').text('Belum ada');
    });

    $(document).on('input', '.keterangan-limited', function () {
        const len = this.value.length;
        const $counter = $(`.char-counter[data-target="${this.id}"]`);
        $counter.text(len);

        // warna warning kalau mendekati/melebihi limit
        if (len >= KETERANGAN_MAX) {
            $counter.css('color', '#dc3545').parent().addClass('font-weight-bold');
        } else if (len >= KETERANGAN_MAX * 0.9) {
            $counter.css('color', '#fd7e14').parent().removeClass('font-weight-bold');
        } else {
            $counter.css('color', '').parent().removeClass('font-weight-bold');
        }
    });

    // inisialisasi counter saat modal dibuka (karena value di-set lewat .val() oleh btn-edit/btn-add)
    $(document).on('shown.bs.modal', '#modalUbah, #modaltambahNilai', function () {
        $(this).find('.keterangan-limited').trigger('input');
    });

    // Reset saat modal ditutup
    $('#modalUbah').on('hidden.bs.modal', function () {
        $('#editRatingList .list-group-item').removeClass('active');
        $('#edit-nilai').val('');
        $('#editSelectedValue').text('Belum ada');
    });

    $('#modaltambahNilai').on('hidden.bs.modal', function () {
        $('#addRatingList .list-group-item').removeClass('active');
        $('#add-nilai').val('');
        $('#addSelectedValue').text('Belum ada');
    });

</script>