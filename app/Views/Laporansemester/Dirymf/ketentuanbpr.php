<div id="toast-wrap" style="position:fixed;top:20px;right:20px;z-index:99999;min-width:290px;pointer-events:none;"></div>
<div class="container-fluid">
    <a href="<?= base_url('Showdirymf') ?>" class="btn-kembali mb-3">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>

    <?php if (session()->getFlashdata('message')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <strong>
                <?= esc(session()->getFlashdata('message')) ?>
            </strong>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('err')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <strong>
                <?= esc(session()->getFlashdata('err')) ?>
            </strong>
        </div>
    <?php endif; ?>
    <div class="card card-body mb-3 shadow-sm">
        <div class="page-header">
            <div style="position:relative; min-height:50px;" class="mb-3">
                <div id="badge-approval-dekom" class="approval-status-badge badge-status-secondary"
                    style="position:absolute; left:0; top:0;">
                    <i class="fas fa-clock"></i>
                    <div>
                        <span class="approval-status-label">Direktur Yang Membawahi Fungsi Kepatuhan</span><br>
                        <span class="approval-status-sub">Belum disetujui seluruhnya</span>
                    </div>
                </div>
            </div>
            <h1 class="page-title">
                <?= ($judul); ?>
            </h1>
            <div class="page-subtitle">
                <?php
                $semDisplay = esc($periodeDetail['semester']);
                $semNum = strtolower($semDisplay) === 'ganjil' ? '1' : (strtolower($semDisplay) === 'genap' ? '2' : $semDisplay);
                ?>
                Semester
                <?= $semNum ?> Tahun
                <?= esc($periodeDetail['tahun']); ?>
            </div>
            <span class="badge-namabpr">
                <?= esc($bprData['namabpr'] ?? 'Nama BPR'); ?>
            </span>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center"
            style="background: var(--grad);">
            <h5 class="m-0 font-weight-bold text-white">
                <i class="fas fa-file-alt"></i> Ketentuan Internal Yang Dimiliki BPR
            </h5>

            <div class="d-flex align-items-center" style="gap:8px;">
                <?php if (($userInGroupPE ?? false) || ($userInGroupAdmin ?? false)): ?>
                    <button type="button" class="btn btn-secondary btn-sm" id="btn-nodata">
                        <i class="fas fa-times-circle"></i> Tidak Ada Data
                    </button>
                <?php endif; ?>

                <?php if (($userInGroupPE ?? false) || ($userInGroupAdmin ?? false)): ?>
                    <button type="button" class="btn btn-success btn-sm" id="btn-show-import" title="Impor Excel">
                        <i class="fas fa-file-excel"></i>  Impor Excel
                    </button>
                <?php endif; ?>

                <?php if (($userInGroupPE ?? false) || ($userInGroupAdmin ?? false)): ?>
                    <button type="button" class="btn btn-primaryy btn-sm" id="btn-show-tambah" title="Tambah Data">
                        <i class="fas fa-plus"></i>
                    </button>
                <?php endif; ?>                

                <?php if (($userInGroupPE ?? false) || ($userInGroupAdmin ?? false)): ?>
                <div class="position-relative d-inline-block">
                    <button type="button"
                            class="btn-sync-periode"
                            id="btn-sync-periode"
                            title="Sinkronisasi data dari periode sebelumnya"
                            disabled>
                        <i class="fas fa-sync-alt" id="sync-icon"></i>
                    </button>
                    <span id="sync-tooltip" class="sync-tooltip">Memeriksa periode sebelumnya…</span>
                </div>
                <?php endif; ?>

                <?php if (
                    ($userInGroupAdmin ?? false) || ($userInGroupDekom ?? false) || ($userInGroupDireksi ?? false)
                    || ($userInGroupPE ?? false) || ($userInGroupDekom2 ?? false) || ($userInGroupDireksi2 ?? false)
                ): ?>
                    <div class="position-relative d-inline-block">
                        <button type="button" class="btn-komentar-global-btn" id="btn-komentar-global" title="Komentar">
                            <i class="fas fa-comment-dots"></i>
                            <span class="btn-komentar-ripple"></span>
                        </button>
                        <span id="notif-global" class="notif-badge" style="display:none;">0</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card-body p-3">
            <div id="nodata-banner" class="alert alert-secondary alert-dismissible mb-3" style="display:none;">
                <div class="d-flex align-items-center" style="gap:10px;">
                    <i class="fas fa-minus-circle fa-lg text-secondary"></i>
                    <div>
                        <strong>Tidak ada data</strong><br>
                        <small>Tidak terdapat Ketentuan Internal Yang Dimiliki BPR pada periode ini.</small>
                    </div>
                </div>
                <?php if (($userInGroupPE ?? false) || ($userInGroupAdmin ?? false)): ?>
                    <button type="button" class="btn btn-sm btn-outline-danger ml-4 mt-1" id="btn-batal-nodata">
                        <i class="fas fa-undo"></i> Batalkan "Tidak Ada Data"
                    </button>
                <?php endif; ?>
            </div>

            <div id="pk-content-wrap">
                <div id="skeleton-container">
                    <?php for ($i = 0; $i < 3; $i++): ?>
                            <div class="ketentuan-card-skel">
                                <div class="skel-card-header"></div>
                                <div class="skel-card-body">
                                    <div class="skel" style="width:60%; height:18px; margin-bottom:10px;"></div>
                                    <div class="skel" style="width:40%; height:14px; margin-bottom:8px;"></div>
                                    <div class="skel" style="width:100%; height:14px; margin-bottom:6px;"></div>
                                    <div class="skel" style="width:80%; height:14px;"></div>
                                </div>
                            </div>
                    <?php endfor; ?>
                </div>

                <div id="error-state" class="empty-state-box" style="display:none;">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3 d-block"></i>
                    <h6 class="text-muted mb-1">Gagal memuat data</h6>
                    <p class="text-muted small mb-3" id="error-state-msg">Terjadi kesalahan saat mengambil data.</p>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="btn-retry">
                        <i class="fas fa-redo"></i> Coba Lagi
                    </button>
                </div>

                <div id="table-wrapper" style="display:none;">
                    <div id="cards-ketentuanbpr" class="ketentuan-cards-grid"></div>

                    <div id="empty-state" class="empty-state-box" style="display:none;">
                        <i class="fas fa-file-slash fa-3x text-muted mb-3 d-block"></i>
                        <h6 class="text-muted mb-1">Belum ada data Ketentuan Internal Yang Dimiliki BPR</h6>
                        <p class="text-muted small mb-3">Data Ketentuan Internal Yang Dimiliki BPR untuk periode ini belum diinput.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="text-center mt-2 mb-2">
        <div style="position:relative; min-height:80px;" class="mb-2">
            <?php if (($userInGroupAdmin ?? false) || ($userInGroupDekom ?? false) || ($userInGroupDireksi2 ?? false)): ?>
                <div style="position:absolute;left:50%;top:0;transform:translateX(-50%);min-width:220px;">
                    <div class="card shadow-sm action-card">
                        <div class="card-body py-2 text-center">
                            <small class="d-block font-weight-bold text-dark mb-2">
                                <i class="fas fa-user-tie text-success"></i> Direktur Yang Membawahkan Fungsi Kepatuhan
                            </small>
                            <div class="d-flex justify-content-center flex-wrap" style="gap:4px;">
                                <button type="button" class="btn btn-primary btn-sm btn-approve-all-dekom">
                                    <i class="fas fa-check-double"></i> Setujui
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm btn-unapprove-all-dekom">
                                    <i class="fas fa-ban"></i> Batal
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <!-- <div class="card shadow-sm action-card d-inline-block" style="min-width:200px;">
            <div class="card-body py-2 text-center">
                <small class="d-block font-weight-bold text-dark mb-2">
                    <i class="fas fa-file-export text-warning"></i> Export Data
                </small>
                <a href="<?= base_url('Ketentuanbpr/exporttxtketentuanbpr') ?>" class="btn btn-warning btn-sm">
                    <i class="fas fa-download"></i> Export .txt
                </a>
            </div>
        </div> -->
    </div>
</div>

<!-- MODAL TAMBAH -->
<div class="modal fade" id="modalTambah" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header modal-header-sdm">
                <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Tambah Data Ketentuan Internal Yang Dimiliki BPR</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="font-weight-bold">Judul Ketentuan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control char-input" id="t-judulketentuan"
                                placeholder="Masukkan Judul Ketentuan" maxlength="2000" autocomplete="off"
                                data-counter="t-judul-count" data-max="2000">
                            <div class="char-counter-wrap">
                                <span id="t-judul-count" class="char-counter">0 / 2000</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="font-weight-bold">Nomor Ketentuan Internal <span class="text-danger">*</span></label>
                            <input type="text" class="form-control char-input" id="t-nomorketentuan"
                                placeholder="Masukkan Nomor Ketentuan Internal" maxlength="1000" autocomplete="off"
                                data-counter="t-nomor-count" data-max="1000">
                            <div class="char-counter-wrap">
                                <span id="t-nomor-count" class="char-counter">0 / 1000</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label class="font-weight-bold">Tanggal Pemberlakuan <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="t-tanggalberlaku" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="font-weight-bold">Keterangan <span class="text-danger">*</span></label>
                            <textarea class="form-control char-input" id="t-keterangan" rows="4"
                                maxlength="2000" placeholder="Masukkan keterangan..."
                                data-counter="t-ket-count" data-max="2000"></textarea>
                            <div class="char-counter-wrap">
                                <span id="t-ket-count" class="char-counter">0 / 2000</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="tambah-err" class="alert alert-danger mt-2" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="button" class="btn btn-primary" id="btn-simpan-tambah">
                    <i class="fas fa-save"></i> Simpan Data
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL UBAH -->
<div class="modal fade" id="modalUbah" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header modal-header-sdm">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Ubah Data Ketentuan Internal Yang Dimiliki BPR</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="u-id">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="font-weight-bold">Judul Ketentuan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control char-input" id="u-judulketentuan"
                                maxlength="2000" autocomplete="off"
                                data-counter="u-judul-count" data-max="2000">
                            <div class="char-counter-wrap">
                                <span id="u-judul-count" class="char-counter">0 / 2000</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="font-weight-bold">Nomor Ketentuan Internal <span class="text-danger">*</span></label>
                            <input type="text" class="form-control char-input" id="u-nomorketentuan"
                                maxlength="1000" autocomplete="off"
                                data-counter="u-nomor-count" data-max="1000">
                            <div class="char-counter-wrap">
                                <span id="u-nomor-count" class="char-counter">0 / 1000</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label class="font-weight-bold">Tanggal Pemberlakuan <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="u-tanggalberlaku" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="font-weight-bold">Keterangan <span class="text-danger">*</span></label>
                            <textarea class="form-control char-input" id="u-keterangan" rows="4"
                                maxlength="2000"
                                data-counter="u-ket-count" data-max="2000"></textarea>
                            <div class="char-counter-wrap">
                                <span id="u-ket-count" class="char-counter">0 / 2000</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="alert alert-warning mb-0">
                    <small><i class="fas fa-info-circle"></i>
                        <strong>Perhatian:</strong> Mengubah data akan me-reset status approval.
                    </small>
                </div>
                <div id="ubah-err" class="alert alert-danger mt-2" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="button" class="btn btn-primary" id="btn-simpan-ubah">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL HAPUS -->
<div class="modal fade" id="modalHapus" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Konfirmasi Hapus</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="h-id">
                <p class="mb-3">Anda akan menghapus data berikut:</p>
                <table class="table table-bordered table-sm mb-3">
                    <tr>
                        <th width="40%">Judul Ketentuan</th>
                        <td id="h-judulketentuan"></td>
                    </tr>
                    <tr>
                        <th>Nomor Ketentuan Internal</th>
                        <td id="h-nomorketentuan"></td>
                    </tr>
                    <tr>
                        <th>Tanggal Pemberlakuan</th>
                        <td id="h-tanggalberlaku"></td>
                    </tr>
                </table>
                <div class="alert alert-danger mb-0">
                    <strong>Peringatan:</strong> Data yang dihapus tidak dapat dikembalikan.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" id="btn-konfirmasi-hapus">
                    <i class="fas fa-trash-alt"></i> Hapus
                </button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Batal
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL APPROVE DEKOM -->
<div class="modal fade" id="modalApproveKom" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#155724;color:white;">
                <h5 class="modal-title"><i class="fas fa-user-tie"></i> Approval Direktur Yang Membawahkan Fungsi Kepatuhan</h5>
                <button type="button" class="close" style="color:white;" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="ak-id">
                <input type="hidden" id="ak-action">
                <p id="ak-msg" class="mb-3"></p>
                <table class="table table-bordered table-sm">
                    <tr>
                        <th width="40%">Judul Ketentuan</th>
                        <td id="ak-judulketentuan"></td>
                    </tr>
                    <tr>
                        <th>Nomor Ketentuan Internal</th>
                        <td id="ak-nomorketentuan"></td>
                    </tr>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="btn-konfirmasi-approve-kom">
                    <i class="fas fa-check"></i> <span id="ak-btn-label">Setujui</span>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalSyncPeriode" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;border:none;">
            <div class="modal-header" style="background:linear-gradient(135deg,#0f766e 0%,#0d9488 100%);color:white;border:none;">
                <h5 class="modal-title">
                    <i class="fas fa-sync-alt"></i> Sinkronisasi Data Periode Sebelumnya
                </h5>
                <button type="button" class="close" style="color:white;opacity:1;" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding:22px 24px!important;">
 
                <!-- Info periode aktif vs sumber -->
                <div class="sync-info-box">
                    <div class="sync-info-periode">
                        <span class="sync-info-label">Periode Aktif</span>
                        <span class="sync-info-val" id="sync-current-label">—</span>
                    </div>
                    <div class="sync-arrow"><i class="fas fa-arrow-up"></i></div>
                    <div class="sync-info-periode">
                        <span class="sync-info-label">Sumber Data</span>
                        <span class="sync-info-val" id="sync-prev-label">—</span>
                    </div>
                </div>
 
                <!-- Preview data -->
                <div id="sync-preview-area" style="margin-top:14px;">
                    <div class="sync-count-badge" id="sync-count-badge">
                        <i class="fas fa-file-alt"></i>
                        <span id="sync-count-num">0</span> data akan disalin
                    </div>
                    <div class="sync-preview-list" id="sync-preview-list"></div>
                </div>
 
                <div class="alert alert-warning mb-0 mt-3" style="font-size:12.5px;border-radius:10px;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Perhatian:</strong> Data yang sudah ada di periode aktif
                    <strong>tidak akan ditimpa</strong>. Hanya data baru (berdasarkan
                    Nomor Ketentuan) yang akan ditambahkan. Status approval akan direset.
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #e2e8f0;padding:12px 24px!important;">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="button" class="btn btn-sm" id="btn-konfirmasi-sync"
                        style="background:linear-gradient(135deg,#0f766e,#0d9488);color:white;font-weight:700;border:none;">
                    <i class="fas fa-sync-alt"></i> Sinkronkan Sekarang
                </button>
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
                    <div class="komentar-modal-icon-wrap">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div>
                        <div class="komentar-modal-title">Komentar — Ketentuan Internal Yang Dimiliki BPR</div>
                    </div>
                </div>
                <button type="button" class="komentar-close-btn" data-dismiss="modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="modal-body komentar-modal-body">
                <div class="komentar-list-label">
                    <i class="fas fa-history"></i> Riwayat Komentar
                </div>
                <div id="komentar-list-wrapper" class="komentar-list-wrapper">
                    <div id="komentar-list">
                        <div class="komentar-loading">
                            <i class="fas fa-spinner fa-spin"></i> Memuat...
                        </div>
                    </div>
                </div>

                <?php if (
                    ($userInGroupAdmin ?? false) || ($userInGroupDekom ?? false) || ($userInGroupDireksi ?? false)
                    || ($userInGroupPE ?? false) || ($userInGroupDekom2 ?? false) || ($userInGroupDireksi2 ?? false)
                ): ?>
                        <div class="komentar-input-wrap">
                            <div class="komentar-input-label">
                                <i class="fas fa-pen-alt"></i> Tulis Komentar
                            </div>
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
                <?php if (
                    ($userInGroupAdmin ?? false) || ($userInGroupDekom ?? false) || ($userInGroupDireksi ?? false)
                    || ($userInGroupPE ?? false) || ($userInGroupDekom2 ?? false) || ($userInGroupDireksi2 ?? false)
                ): ?>
                        <button type="button" class="btn btn-primary btn-sm" id="btn-kirim-komentar">
                            <i class="fas fa-paper-plane"></i> Kirim
                        </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalImport" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header modal-header-sdm">
                <h5 class="modal-title"><i class="fas fa-file-excel"></i> Impor Data dari Excel</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="import-step-upload">
                    <p class="small text-muted mb-2">
                        Format wajib: <strong>Judul Ketentuan | Nomor Ketentuan Internal | Tanggal Pemberlakuan | Keterangan</strong>.
                        <a href="<?= base_url('assets/template/Template Impor Ketentuan Internal yang Dimiliki.xlsx') ?>">Unduh template</a>.
                    </p>
                    <div class="form-group">
                        <input type="file" class="form-control" id="import-file" accept=".xlsx,.xls">
                    </div>
                    <div id="import-upload-err" class="alert alert-danger" style="display:none;"></div>
                </div>

                <div id="import-step-preview" style="display:none;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge badge-success" id="import-ok-count">0 baris siap disimpan</span>
                        <span class="text-muted small" id="import-total-count"></span>
                    </div>
                    <div style="max-height:320px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:8px;">
                        <table class="table table-sm mb-0" id="import-preview-table">
                            <thead><tr><th>#</th><th>Judul</th><th>Nomor</th><th>Tgl</th><th>Status</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Batal</button>
                <button type="button" class="btn btn-primary" id="btn-parse-import" style="display:none;">
                    <i class="fas fa-search"></i> Cek Data
                </button>
                <button type="button" class="btn btn-success" id="btn-simpan-import" style="display:none;">
                    <i class="fas fa-save"></i> Simpan Data
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

    :root {
        --brand: #4f46e5;
        --brand-light: #818cf8;
        --brand-dark: #3730a3;
        --brand-bg: #eef2ff;
        --success: #10b981;
        --success-bg: #d1fae5;
        --danger: #ef4444;
        --danger-bg: #fee2e2;
        --warning: #f59e0b;
        --warning-bg: #fef3c7;
        --info: #06b6d4;
        --info-bg: #cffafe;
        --gray-50: #f8fafc;
        --gray-100: #f1f5f9;
        --gray-200: #e2e8f0;
        --gray-400: #94a3b8;
        --gray-500: #64748b;
        --gray-700: #334155;
        --gray-800: #1e293b;
        --gray-900: #0f172a;
        --blue: #1f2aa8;
        --r-sm: 8px;
        --r: 12px;
        --r-lg: 16px;
        --sh-sm: 0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.06);
        --sh: 0 4px 6px -1px rgba(0,0,0,.1), 0 2px 4px -1px rgba(0,0,0,.06);
        --sh-lg: 0 10px 25px -3px rgba(0,0,0,.1), 0 4px 6px -2px rgba(0,0,0,.05);
        --tr: all .2s cubic-bezier(.4,0,.2,1);
        --grad: linear-gradient(135deg, #141863 0%, #1f2aa8 100%);
    }

    * { font-family: 'Plus Jakarta Sans', sans-serif; }

    .page-header {
        text-align: center;
        padding: 32px 20px 24px;
        background: white;
        border-bottom: 1px solid var(--gray-100);
        position: relative;
        overflow: hidden;
    }
    .page-header::before, .page-header::after {
        content: '';
        position: absolute;
        border-radius: 50%;
    }
    .page-header::before {
        top: -60px; right: -60px;
        width: 200px; height: 200px;
        background: radial-gradient(circle, #eef2ff 0%, transparent 70%);
    }
    .page-header::after {
        bottom: -40px; left: -40px;
        width: 160px; height: 160px;
        background: radial-gradient(circle, #f0fdf4 0%, transparent 70%);
    }
    .page-title {
        font-size: clamp(1.3rem, 3.5vw, 1.75rem);
        font-weight: 800;
        color: var(--gray-900);
        margin-bottom: 6px;
        letter-spacing: -.5px;
        position: relative;
        z-index: 1;
    }
    .page-subtitle {
        font-size: 1rem;
        font-weight: 600;
        color: var(--gray-900);
        margin-bottom: 12px;
        position: relative;
        z-index: 1;
    }
    .badge-namabpr {
        display: inline-block;
        background: var(--grad);
        color: white;
        font-size: 1rem;
        font-weight: 700;
        padding: 6px 20px;
        border-radius: 100px;
        letter-spacing: .3px;
        position: relative;
        z-index: 1;
        box-shadow: 0 4px 12px rgba(79,70,229,.3);
    }
    .approval-status-badge {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 16px;
        border-radius: var(--r);
        font-size: 13px;
        font-weight: 600;
        transition: var(--tr);
        border: 1.5px solid transparent;
    }
    .approval-status-badge i { font-size: 18px; flex-shrink: 0; }
    .approval-status-label { font-weight: 700; font-size: 12px; }
    .approval-status-sub { font-size: 11px; opacity: .8; }
    .badge-status-secondary { background: var(--gray-100); color: var(--gray-500); border-color: var(--gray-200); }
    .badge-status-success { background: var(--success-bg); color: #065f46; border-color: #6ee7b7; }
    .action-card {
        border-radius: var(--r) !important;
        border: 1.5px solid var(--gray-200) !important;
        transition: var(--tr);
        overflow: hidden;
    }
    .action-card:hover {
        border-color: var(--brand-light) !important;
        box-shadow: 0 8px 24px rgba(79,70,229,.12) !important;
        transform: translateY(-3px);
    }
    .card-header { border-radius: var(--r-lg) var(--r-lg) 0 0 !important; }
    /* ── SKELETON CARD ── */
    .ketentuan-card-skel {
        background: white;
        border-radius: var(--r-lg);
        overflow: hidden;
        margin-bottom: 14px;
        box-shadow: var(--sh-sm);
    }
    .skel-card-header {
        height: 48px;
        background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
        background-size: 200% 100%;
        animation: skAnim 1.4s infinite;
    }
    .skel-card-body { padding: 16px; }

    /* ── CARD GRID ── */
    .ketentuan-cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 16px;
        padding: 4px 2px;
    }

    /* ── SINGLE CARD ── */
    .ketentuan-card {
        background: white;
        border-radius: var(--r-lg);
        box-shadow: var(--sh-sm);
        border: 1.5px solid var(--gray-100);
        overflow: hidden;
        transition: var(--tr);
        animation: rowIn .3s ease both;
        display: flex;
        flex-direction: column;
    }
    .ketentuan-card:hover {
        box-shadow: var(--sh-lg);
        border-color: var(--brand-light);
        transform: translateY(-3px);
    }

    /* ── CARD HEADER ── */
    .kcard-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        background: var(--grad);
        gap: 10px;
    }
    .kcard-nomor {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
        flex: 1;
    }
    .kcard-index {
        background: rgba(255,255,255,.2);
        color: white;
        font-size: 11px;
        font-weight: 800;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .kcard-nomor-text {
        color: rgba(255,255,255,.9);
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        letter-spacing: .3px;
    }
    .kcard-actions {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-shrink: 0;
    }
    .kcard-acc-icon {
        font-size: 20px;
        cursor: pointer;
        transition: var(--tr);
        color: white;
        opacity: .7;
    }
    .kcard-acc-icon.acc-ok  { opacity: 1; color: #6ee7b7; }
    .kcard-acc-icon.acc-no  { opacity: .5; color: white; }
    .kcard-acc-icon:hover   { transform: scale(1.25); opacity: 1; }
    .kcard-btn {
        width: 28px;
        height: 28px;
        padding: 0 !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--r-sm) !important;
        font-size: 12px !important;
    }

    /* ── CARD BODY ── */
    .kcard-body {
        padding: 14px 16px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        flex: 1;
    }
    .kcard-judul {
        font-size: 15px;
        font-weight: 700;
        color: var(--gray-900);
        line-height: 1.4;
    }
    .kcard-meta-row {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }
    .kcard-meta-item {
        display: flex;
        flex-direction: column;
        gap: 3px;
        min-width: 0;
    }
    .kcard-meta-label {
        font-size: 10.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .7px;
        color: var(--gray-400);
    }
    .kcard-meta-label i { margin-right: 3px; }
    .kcard-meta-val {
        font-size: 13px;
        font-weight: 600;
        color: var(--gray-700);
    }
    .kcard-acc-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 12px;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 100px;
        border: 1.5px solid transparent;
    }
    .acc-badge-ok  {
        background: var(--success-bg);
        color: #065f46;
        border-color: #a7f3d0;
    }
    .acc-badge-no  {
        background: var(--warning-bg);
        color: #92400e;
        border-color: #fcd34d;
    }
    .kcard-keterangan-wrap {
        background: var(--gray-50);
        border: 1.5px solid var(--gray-100);
        border-radius: var(--r-sm);
        padding: 10px 12px;
    }
    .kcard-ket-label {
        font-size: 10.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .7px;
        color: var(--gray-400);
        margin-bottom: 6px;
    }
    .kcard-ket-label i { margin-right: 4px; }
    .kcard-ket-text {
        font-size: 13.5px;
        color: var(--gray-700);
        line-height: 1.6;
        white-space: pre-wrap;
        word-break: break-word;
    }

    @media (max-width: 576px) {
        .ketentuan-cards-grid {
            grid-template-columns: 1fr;
        }
    }
    .btn { transition: var(--tr); font-weight: 600; }
    .btn:hover { transform: translateY(-2px); box-shadow: var(--sh); }
    .btn:active { transform: scale(.96) !important; }
    .btn-sm { font-size: 12px; }
    .btn-ubah-row {
        background: linear-gradient(135deg, #f59e0b, #fbbf24);
        border: none; color: white;
        border-radius: var(--r-sm);
        padding: 5px 10px;
        box-shadow: 0 2px 8px rgba(245,158,11,.35);
    }
    .btn-hapus-row {
        background: linear-gradient(135deg, #ef4444, #f87171);
        border: none; color: white;
        border-radius: var(--r-sm);
        padding: 5px 10px;
        box-shadow: 0 2px 8px rgba(239,68,68,.35);
    }
    .btn-kembali {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: white;
        border: 1.5px solid var(--gray-200);
        border-radius: var(--r-sm);
        padding: 8px 16px;
        font-size: .875rem;
        font-weight: 600;
        color: var(--gray-500);
        text-decoration: none;
        transition: var(--tr);
        margin: 14px 0 0 16px;
    }
    .btn-kembali:hover {
        background: var(--brand-bg);
        border-color: var(--brand-light);
        color: var(--brand);
        text-decoration: none;
    }
    .btn-primaryy {
        background: white;
        color: var(--blue);
        border: none;
        border-radius: var(--r-sm);
        width: 32px; height: 32px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        font-weight: 700;
        box-shadow: 0 2px 8px rgba(0,0,0,.15);
        transition: var(--tr);
    }
    .btn-primaryy:hover { background: var(--brand-bg); transform: rotate(90deg) scale(1.1); }
    .acc-ok, .acc-no { font-size: 22px; cursor: pointer; transition: var(--tr); }
    .acc-ok { color: var(--success); }
    .acc-no { color: var(--gray-200); }
    .acc-ok:hover { transform: scale(1.3); filter: drop-shadow(0 0 6px rgba(16,185,129,.5)); }
    .acc-no:hover { transform: scale(1.3); color: var(--gray-400); }
    .notif-badge {
        position: absolute;
        top: -7px; right: -7px;
        background: var(--danger);
        color: #fff;
        border-radius: 50%;
        padding: 2px 5px;
        font-size: 10px;
        font-weight: 700;
        min-width: 17px;
        text-align: center;
        line-height: 1.3;
        box-shadow: 0 2px 6px rgba(239,68,68,.5);
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(239,68,68,.4) }
        50% { box-shadow: 0 0 0 5px rgba(239,68,68,0) }
    }
    .empty-state-box {
        text-align: center;
        padding: 60px 20px;
        background: var(--gray-50);
        border-radius: var(--r-lg);
        border: 2px dashed var(--gray-200);
        margin: 10px 0;
    }
    .btn-komentar-global-btn {
        position: relative;
        width: 36px; height: 36px;
        border-radius: 50%;
        background: white;
        border: none;
        color: var(--blue);
        font-size: 17px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 10px rgba(0,0,0,.18);
        cursor: pointer;
        transition: all .25s cubic-bezier(.4,0,.2,1);
        outline: none;
    }
    .btn-komentar-global-btn:hover {
        background: var(--brand-bg);
        color: var(--brand);
        transform: scale(1.13);
        box-shadow: 0 4px 18px rgba(79,70,229,.25);
    }
    .btn-komentar-global-btn:active { transform: scale(.93); }
    .btn-komentar-global-btn.has-unread {
        animation: btn-shake 1.8s ease infinite;
        background: var(--brand-bg);
        color: var(--brand);
    }
    @keyframes btn-shake {
        0%, 100% { transform: rotate(0deg) scale(1); }
        10% { transform: rotate(-14deg) scale(1.08); }
        20% { transform: rotate(12deg) scale(1.08); }
        30% { transform: rotate(-10deg) scale(1.05); }
        40% { transform: rotate(8deg) scale(1.05); }
        50% { transform: rotate(0deg) scale(1); }
    }
    .btn-komentar-ripple {
        position: absolute; inset: -4px;
        border-radius: 50%;
        border: 2px solid var(--brand-light);
        opacity: 0;
        pointer-events: none;
    }
    .btn-komentar-global-btn.has-unread .btn-komentar-ripple { animation: ripple-out 1.8s ease infinite; }
    @keyframes ripple-out {
        0% { transform: scale(.9); opacity: .7; }
        100% { transform: scale(1.6); opacity: 0; }
    }
    .komentar-modal-content { border-radius: 18px !important; overflow: hidden; border: none; box-shadow: 0 20px 60px rgba(0,0,0,.18) !important; }
    .komentar-modal-header { background: var(--grad); padding: 18px 22px !important; border: none !important; display: flex; align-items: center; justify-content: space-between; }
    .komentar-modal-title-wrap { display: flex; align-items: center; gap: 12px; }
    .komentar-modal-icon-wrap { width: 40px; height: 40px; border-radius: 12px; background: rgba(255,255,255,.18); display: flex; align-items: center; justify-content: center; font-size: 18px; color: white; flex-shrink: 0; }
    .komentar-modal-title { font-size: 15px; font-weight: 800; color: white; line-height: 1.2; }
    .komentar-close-btn { width: 32px; height: 32px; border-radius: 50%; background: rgba(255,255,255,.15); border: none; color: white; font-size: 14px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all .2s; flex-shrink: 0; }
    .komentar-close-btn:hover { background: rgba(255,255,255,.3); transform: rotate(90deg); }
    .komentar-modal-body { padding: 20px 22px !important; background: var(--gray-50); }
    .komentar-modal-footer { background: white; border-top: 1px solid var(--gray-100) !important; padding: 12px 22px !important; }
    .komentar-list-label { font-size: 12px; font-weight: 700; color: var(--gray-500); text-transform: uppercase; letter-spacing: .8px; margin-bottom: 10px; }
    .komentar-list-wrapper { background: white; border: 1.5px solid var(--gray-200); border-radius: 12px; max-height: 320px; overflow-y: auto; padding: 12px; margin-bottom: 16px; scroll-behavior: smooth; }
    .komentar-list-wrapper::-webkit-scrollbar { width: 5px; }
    .komentar-list-wrapper::-webkit-scrollbar-track { background: transparent; }
    .komentar-list-wrapper::-webkit-scrollbar-thumb { background: var(--gray-200); border-radius: 10px; }
    .komentar-loading, .komentar-empty { text-align: center; color: var(--gray-400); padding: 30px 0; font-size: 13px; }
    .km-item { background: var(--gray-50); border-left: 3px solid var(--brand-light); border-radius: 10px; padding: 10px 14px; margin-bottom: 8px; box-shadow: var(--sh-sm); animation: kmIn .25s ease both; }
    .km-item:last-child { margin-bottom: 0; }
    .km-item.km-own { border-left-color: var(--success); background: #f0fdf4; }
    @keyframes kmIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
    .km-meta { display: flex; justify-content: space-between; margin-bottom: 5px; flex-wrap: wrap; gap: 4px; }
    .km-author { font-weight: 700; font-size: 12px; color: var(--gray-800); display: flex; align-items: center; gap: 5px; }
    .km-you { font-size: 9px; background: var(--success); color: white; padding: 1px 6px; border-radius: 100px; font-weight: 700; }
    .km-time { font-size: 11px; color: var(--gray-400); }
    .km-text { font-size: 13px; white-space: pre-wrap; margin: 0; color: var(--gray-700); line-height: 1.5; }
    .komentar-input-wrap { background: white; border: 1.5px solid var(--gray-200); border-radius: 12px; padding: 14px; transition: border-color .2s; }
    .komentar-input-wrap:focus-within { border-color: var(--brand); box-shadow: 0 0 0 3px rgba(79,70,229,.1); }
    .komentar-input-label { font-size: 12px; font-weight: 700; color: var(--gray-500); margin-bottom: 8px; text-transform: uppercase; letter-spacing: .6px; }
    .komentar-textarea { width: 100%; border: none; outline: none; resize: none; font-size: 13.5px; color: var(--gray-700); font-family: 'Plus Jakarta Sans', sans-serif; line-height: 1.6; background: transparent; }
    .komentar-input-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 8px; padding-top: 8px; border-top: 1px solid var(--gray-100); }
    .komentar-hint { font-size: 11px; color: var(--gray-400); }
    .komentar-char { font-size: 11px; color: var(--gray-400); font-weight: 600; transition: color .2s; }
    .komentar-char.danger { color: var(--danger); }
    .modal-header-sdm { background: var(--grad); color: white; }
    .modal-content { border-radius: var(--r-lg); overflow: hidden; border: none; box-shadow: var(--sh-lg); }
    .modal-body { padding: 20px 24px !important; }
    .modal-footer { padding: 12px 24px !important; }
    .modal-body .form-group { margin-bottom: 16px !important; }
    .modal-body .form-control { width: 100% !important; box-sizing: border-box !important; border-radius: 8px !important; border: 1.5px solid var(--gray-200) !important; padding: 8px 12px !important; font-size: 13.5px !important; transition: var(--tr) !important; }
    .modal-body .form-control:focus { border-color: var(--brand) !important; box-shadow: 0 0 0 3px rgba(79,70,229,.15) !important; outline: none !important; }
    .modal-body .row { margin-left: -8px !important; margin-right: -8px !important; }
    .modal-body .row>[class*="col-"] { padding-left: 8px !important; padding-right: 8px !important; }
    .modal-dialog { margin: 20px auto !important; max-width: 720px !important; }
    .t-item { background: white; border-radius: var(--r); padding: 14px 18px; margin-bottom: 10px; box-shadow: var(--sh-lg); display: flex; align-items: center; gap: 12px; font-size: 13.5px; animation: tIn .3s ease; pointer-events: all; border-left: 4px solid; }
    .t-item.t-success { border-color: var(--success); color: #065f46; }
    .t-item.t-error { border-color: var(--danger); color: #991b1b; }
    .t-item.t-info { border-color: var(--info); color: #164e63; }
    @keyframes tIn { from { transform: translateX(110%); opacity: 0 } to { transform: translateX(0); opacity: 1 } }
    .skeleton-row { display: flex; gap: 6px; margin-bottom: 10px; }
    .skel { height: 52px; border-radius: var(--r); background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%); background-size: 200% 100%; animation: skAnim 1.4s infinite; }
    @keyframes skAnim { 0% { background-position: 200% 0 } 100% { background-position: -200% 0 } }
    .blk-disabled { opacity: .4; pointer-events: none; }

    .btn-sync-periode {
    width: 36px; height: 36px; border-radius: 50%;
    background: white; border: none;
    color: #0d9488; font-size: 15px;
    display: inline-flex; align-items: center; justify-content: center;
    box-shadow: 0 2px 10px rgba(0,0,0,.18);
    cursor: pointer; transition: all .25s cubic-bezier(.4,0,.2,1);
    outline: none; position: relative;
    }
    .btn-sync-periode:disabled {
        color: #94a3b8; background: #f1f5f9; cursor: not-allowed;
        box-shadow: 0 1px 4px rgba(0,0,0,.08);
    }
    .btn-sync-periode:not(:disabled):hover {
        background: #f0fdfa; color: #0f766e;
        transform: scale(1.13); box-shadow: 0 4px 18px rgba(13,148,136,.3);
    }
    .btn-sync-periode.syncing .fas { animation: spin360 1s linear infinite; }
    @keyframes spin360 { to { transform: rotate(360deg); } }
    
    /* ── Tooltip ── */
    .sync-tooltip {
        position: absolute; bottom: calc(100% + 8px); left: 50%;
        transform: translateX(-50%);
        background: #1e293b; color: white;
        font-size: 11px; font-weight: 600;
        padding: 5px 10px; border-radius: 8px;
        white-space: nowrap; pointer-events: none;
        opacity: 0; transition: opacity .2s; z-index: 100;
    }
    .sync-tooltip::after {
        content: ''; position: absolute; top: 100%; left: 50%;
        transform: translateX(-50%);
        border: 5px solid transparent; border-top-color: #1e293b;
    }
    .btn-sync-periode:hover + .sync-tooltip,
    .btn-sync-periode:focus + .sync-tooltip { opacity: 1; }
    
    /* ── Sync modal components ── */
    .sync-info-box {
        display: flex; align-items: center; justify-content: center;
        gap: 16px; background: #f0fdfa;
        border: 1.5px solid #99f6e4; border-radius: 12px;
        padding: 14px 18px;
    }
    .sync-info-periode {
        display: flex; flex-direction: column; align-items: center;
        gap: 3px; flex: 1; text-align: center;
    }
    .sync-info-label {
        font-size: 10px; font-weight: 700; text-transform: uppercase;
        letter-spacing: .7px; color: #5eead4;
    }
    .sync-info-val { font-size: 14px; font-weight: 800; color: #0f766e; }
    .sync-arrow { font-size: 20px; color: #0d9488; }
    .sync-count-badge {
        display: inline-flex; align-items: center; gap: 8px;
        background: #d1fae5; color: #065f46;
        font-size: 13px; font-weight: 700;
        padding: 6px 14px; border-radius: 100px;
        border: 1.5px solid #a7f3d0; margin-bottom: 10px;
    }
    .sync-preview-list {
        max-height: 200px; overflow-y: auto;
        border: 1.5px solid #e2e8f0; border-radius: 10px;
        background: white;
    }
    .sync-preview-list::-webkit-scrollbar { width: 4px; }
    .sync-preview-list::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    .sync-prev-item {
        display: flex; align-items: flex-start; gap: 10px;
        padding: 9px 14px; border-bottom: 1px solid #f1f5f9;
        font-size: 12.5px;
    }
    .sync-prev-item:last-child { border-bottom: none; }
    .sync-prev-item-idx {
        width: 20px; height: 20px; border-radius: 50%;
        background: #f0fdfa; color: #0d9488;
        font-size: 10px; font-weight: 800; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        margin-top: 2px;
    }
    #pk-content-wrap{transition:opacity .25s ease}
    #pk-content-wrap.pk-blurred{opacity:.45;pointer-events:none;filter:grayscale(.15)}
    .sync-prev-item-body { flex: 1; min-width: 0; }
    .sync-prev-item-judul { font-weight: 700; color: #1e293b; line-height: 1.35; }
    .sync-prev-item-meta { font-size: 11px; color: #64748b; margin-top: 2px; }
    .sync-prev-item.will-skip { opacity: .4; }
    .sync-skip-badge {
        font-size: 10px; font-weight: 700;
        background: #fef3c7; color: #92400e;
        padding: 2px 7px; border-radius: 6px;
        flex-shrink: 0; margin-top: 2px;
    }

    .char-counter-wrap {
        display: flex;
        justify-content: flex-end;
        margin-top: 4px;
    }
    .char-counter {
        font-size: 11px;
        font-weight: 600;
        color: var(--gray-400);
        transition: color .2s;
    }
    .char-counter.warn  { color: var(--warning); }
    .char-counter.danger { color: var(--danger); }
    @media (max-width:768px) {
        .modal-dialog { margin: 10px !important; max-width: calc(100% - 20px) !important; }
        .modal-body { padding: 14px 16px !important; }
        .modal-body .row>[class*="col-"] { padding-left: 6px !important; padding-right: 6px !important; }
    }
</style>

<?php
$_canEdit = (($userInGroupPE ?? false) || ($userInGroupAdmin ?? false)) ? 'true' : 'false';
$_canKom = (($userInGroupAdmin ?? false) || ($userInGroupDekom ?? false) || ($userInGroupDireksi2 ?? false) || ($userInGroupDireksi ?? false)) ? 'true' : 'false';
$_canKomentar = (($userInGroupAdmin ?? false) || ($userInGroupDekom ?? false) || ($userInGroupDireksi ?? false)
    || ($userInGroupPE ?? false) || ($userInGroupDekom2 ?? false) || ($userInGroupDireksi2 ?? false)) ? 'true' : 'false';
$_base = rtrim(base_url(), '/');
$_csrf = csrf_token();
$_hash = csrf_hash();
$_userId = (int) ($userId ?? 0);
$_fullname = addslashes($fullname ?? 'Unknown');
$_periodeId = (int) ($periodeId ?? 0);
$_kodebpr = addslashes($kodebpr ?? '');
?>
<script>
(function wait() {
    if (typeof jQuery === 'undefined') return setTimeout(wait, 50);
    jQuery(document).ready(init);
})();

function init($) {
    var BASE  = '<?= $_base ?>',  CSRF = '<?= $_csrf ?>', HASH = '<?= $_hash ?>',
        UID   = <?= $_userId ?>,  FNAME = '<?= $_fullname ?>',
        PID   = <?= $_periodeId ?>, KBPR = '<?= $_kodebpr ?>',
        CAN_EDIT = <?= $_canEdit ?>,
        CAN_KOM  = <?= $_canKom ?>;

    function esc(s) {
        return s == null ? '' : String(s)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function debounce(fn, ms) {
        var t; return function() { var a=arguments,c=this; clearTimeout(t); t=setTimeout(function(){ fn.apply(c,a); },ms); };
    }
    function throttle(fn, ms) {
        var busy=false; return function() {
            if(busy) return; busy=true; fn.apply(this,arguments); setTimeout(function(){ busy=false; },ms);
        };
    }
    function toast(msg, type) {
        type = type || 'success';
        var icons = { success:'check-circle', error:'exclamation-circle', info:'info-circle' };
        var el = $('<div class="t-item t-'+type+'"><i class="fas fa-'+icons[type]+' fa-lg"></i><div>'+msg+'</div></div>');
        $('#toast-wrap').append(el);
        setTimeout(function(){ el.fadeOut(400, function(){ $(this).remove(); }); }, 3500);
    }
    function csrf() { var d={}; d[CSRF]=HASH; return d; }
    function updateCsrf(res) {
        if (res && res.csrf_hash) HASH = res.csrf_hash;
        var m = document.cookie.match(/ci_csrf_token=([^;]+)/);
        if (m) HASH = decodeURIComponent(m[1]);
    }
    function parseErr(xhr) {
        try { var r=JSON.parse(xhr.responseText); if(r&&r.message) return r.message; } catch(e){}
        return null;
    }
    function doPost(url, data, cb, errCb) {
        $.ajax({
            url: BASE+'/'+url, method:'POST', dataType:'json',
            data: $.extend({}, csrf(), data),
            headers: {'X-Requested-With':'XMLHttpRequest'},
            success: function(res){ updateCsrf(res); cb(res); },
            error: function(xhr){
                var m = parseErr(xhr) || 'Terjadi kesalahan server.';
                if (xhr.status===403){ m='Sesi keamanan kedaluwarsa. Halaman akan di-refresh...'; setTimeout(function(){ location.reload(); },2000); }
                errCb ? errCb(m) : toast(m,'error');
            }
        });
    }
    function doGet(url, params, cb) {
        if (typeof params==='function'){ cb=params; params={}; }
        $.ajax({
            url: BASE+'/'+url, method:'GET', dataType:'json', data:params,
            headers: {'X-Requested-With':'XMLHttpRequest'},
            success: cb,
            error: function(xhr){
                $('#skeleton-container').hide();
                var m = parseErr(xhr) || 'Gagal memuat data.';
                if (xhr.status===401){ m='Sesi telah berakhir. Mengalihkan...'; setTimeout(function(){ location.href=BASE+'/login'; },1500); }
                toast(m,'error');
            }
        });
    }
    function btnLoading(btn) {
        var ori=btn.html(); btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled',true);
        return function(){ btn.html(ori).prop('disabled',false); };
    }
    function fmtTanggal(s) {
        if (!s) return '-';
        var parts = s.replace(/-/g,'/').split('/');
        if (parts.length!==3) return s;
        return parts[2]+'/'+parts[1]+'/'+parts[0];
    }
    function timeAgo(s) {
        if (!s) return '-';
        var d=new Date(s), diff=Math.floor((Date.now()-d.getTime())/60000);
        if (diff<1) return 'Baru saja';
        if (diff<60) return diff+' menit lalu';
        if (diff<1440) return Math.floor(diff/60)+' jam lalu';
        return ('0'+d.getDate()).slice(-2)+'/'+('0'+(d.getMonth()+1)).slice(-2)+'/'+d.getFullYear()
            +' '+('0'+d.getHours()).slice(-2)+':'+('0'+d.getMinutes()).slice(-2);
    }
    function updateNotifBadge(count) {
        var btn=$('#btn-komentar-global'), badge=$('#notif-global');
        if (count>0){ badge.text(count).show(); btn.addClass('has-unread'); }
        else { badge.hide(); btn.removeClass('has-unread'); }
    }

    function initCharCounter(el) {
        var $el  = $(el);
        var max  = parseInt($el.data('max')) || 0;
        var cid  = $el.data('counter');
        if (!cid || !max) return;
        var $cnt = $('#' + cid);

        function update() {
            var len = $el.val().length;
            var pct = len / max;
            $cnt.text(len + ' / ' + max);
            $cnt.removeClass('warn danger');
            if (pct >= 1)       $cnt.addClass('danger');
            else if (pct >= 0.85) $cnt.addClass('warn');
        }
        $el.on('input', update);
        update();
    }

    $('#modalTambah, #modalUbah').on('shown.bs.modal', function(){
        $(this).find('.char-input').each(function(){ initCharCounter(this); });
    });

    function refreshUbahCounters() {
        $('#modalUbah .char-input').each(function(){
            var $el  = $(this);
            var max  = parseInt($el.data('max')) || 0;
            var cid  = $el.data('counter');
            if (!cid || !max) return;
            var len  = $el.val().length;
            var $cnt = $('#' + cid);
            var pct  = len / max;
            $cnt.text(len + ' / ' + max);
            $cnt.removeClass('warn danger');
            if (pct >= 1)        $cnt.addClass('danger');
            else if (pct >= 0.85) $cnt.addClass('warn');
        });
    }
    function loadKomentarList(scrollToBottom) {
        var list=$('#komentar-list');
        list.html('<div class="komentar-loading"><i class="fas fa-spinner fa-spin"></i> Memuat...</div>');
        $.ajax({
            url: BASE+'/Ketentuanbpr/getKomentar', method:'GET',
            headers: {'X-Requested-With':'XMLHttpRequest'},
            success: function(res){
                var items = Array.isArray(res) ? res : [];
                if (!items.length){
                    list.html('<div class="komentar-empty"><i class="fas fa-comment-slash fa-2x mb-2 d-block"></i>Belum ada komentar.</div>');
                    return;
                }
                var html='';
                $.each(items, function(i,k){
                    var own = parseInt(k.user_id)===UID;
                    html += '<div class="km-item'+(own?' km-own':'')+'"><div class="km-meta">'
                        +'<span class="km-author"><i class="fas fa-user-circle"></i> '+esc(k.fullname||'?')
                        +(own?' <span class="km-you">Anda</span>':'')+' </span>'
                        +'<span class="km-time"><i class="fas fa-clock"></i> '+timeAgo(k.created_at)+'</span>'
                        +'</div><p class="km-text">'+esc(k.komentar)+'</p></div>';
                });
                list.html(html);
                if (scrollToBottom){
                    var w=document.getElementById('komentar-list-wrapper');
                    if (w) setTimeout(function(){ w.scrollTop=w.scrollHeight; },100);
                }
            },
            error: function(){
                list.html('<div class="komentar-empty text-danger"><i class="fas fa-exclamation-circle"></i> Gagal memuat komentar.</div>');
            }
        });
    }

    function pollUnreadCount() {
        $.ajax({
            url: BASE+'/Ketentuanbpr/getUnreadCounts', method:'GET',
            headers: {'X-Requested-With':'XMLHttpRequest'},
            success: function(res){
                if (res&&res.status==='success') updateNotifBadge(parseInt(res.count)||0);
            }
        });
    }
    
    function renderRows(rows) {
        var container = document.getElementById('cards-ketentuanbpr');
        if (!rows||!rows.length){ container.innerHTML=''; $('#empty-state').show(); return; }
        $('#empty-state').hide();
        var html='';
        $.each(rows, function(i,r){
            var dOk   = parseInt(r.accdir2)===1;
            var accCls = dOk ? 'acc-badge-ok' : 'acc-badge-no';
            var accIcon= dOk ? 'fa-check-circle' : 'fa-clock';
            var accTxt = dOk ? 'Disetujui' : 'Menunggu Persetujuan';

            var dataA = dataAttrs(r,['id','judulketentuan','nomorketentuan','tanggalberlaku','keterangan']);
            var dataH = dataAttrs(r,['id','judulketentuan','nomorketentuan','tanggalberlaku']);
            var dataK = ' data-id="'+esc(r.id)+'" data-judulketentuan="'+esc(r.judulketentuan)+'" data-nomorketentuan="'+esc(r.nomorketentuan)+'" data-action="'+(dOk?'unapprove':'approve')+'"';

            html += '<div class="ketentuan-card" id="kcard-'+r.id+'">'

                // ── Header card
                +  '<div class="kcard-header">'
                +    '<div class="kcard-nomor"><span class="kcard-index">'+(i+1)+'</span>'
                +      '<span class="kcard-nomor-text">'+esc(r.nomorketentuan||'-')+'</span></div>'
                +    '<div class="kcard-actions">';

            // Approval icon
            if (CAN_KOM) {
                html += '<i class="fas '+accIcon+' btn-toggle-kom kcard-acc-icon '+(dOk?'acc-ok':'acc-no')+'"'
                      + dataK+' title="'+(dOk?'Batalkan approval':'Setujui')+'"></i>';
            }

            // Edit & Hapus
            if (CAN_EDIT) {
                html += '<button class="btn btn-sm btn-warning btn-ubah kcard-btn" title="Ubah"'+dataA+'>'
                      +   '<i class="fas fa-pencil-alt"></i></button>'
                      + '<button class="btn btn-sm btn-danger btn-hapus kcard-btn" title="Hapus"'+dataH+'>'
                      +   '<i class="fas fa-trash-alt"></i></button>';
            }

            html += '  </div>'
                +  '</div>'

                // ── Body card
                +  '<div class="kcard-body">'
                +    '<div class="kcard-judul">'+esc(r.judulketentuan)+'</div>'

                +    '<div class="kcard-meta-row">'
                +      '<div class="kcard-meta-item">'
                +        '<span class="kcard-meta-label"><i class="fas fa-calendar-alt"></i> Tanggal Berlaku</span>'
                +        '<span class="kcard-meta-val">'+(r.tanggalberlaku ? fmtTanggal(r.tanggalberlaku) : '<em class="text-muted">-</em>')+'</span>'
                +      '</div>'
                +      '<div class="kcard-meta-item">'
                +        '<span class="kcard-meta-label"><i class="fas fa-user-tie"></i> Status Persetujuan</span>'
                +        '<span class="kcard-acc-badge '+accCls+'"><i class="fas '+accIcon+'"></i> '+accTxt+'</span>'
                +      '</div>'
                +    '</div>'

                +    '<div class="kcard-keterangan-wrap">'
                +      '<div class="kcard-ket-label"><i class="fas fa-align-left"></i> Keterangan</div>'
                +      '<div class="kcard-ket-text">'+esc(r.keterangan||'-')+'</div>'
                +    '</div>'
                +  '</div>'
                +'</div>';
        });
        container.innerHTML = html;
    }

    function renderNodataState(isNull){
        var wrap = $('#pk-content-wrap');
        if (isNull) {
            $('#nodata-banner').show();
            wrap.addClass('pk-blurred');
            $('#btn-show-tambah,#btn-sync-periode,#btn-nodata').hide();
        } else {
            $('#nodata-banner').hide();
            wrap.removeClass('pk-blurred');
            $('#btn-show-tambah,#btn-sync-periode,#btn-nodata').show();
        }
    }

    function btn(cls, icon, title, attrs) {
        return '<button class="btn btn-sm '+cls+'" title="'+title+'"'+attrs+'><i class="fas '+icon+'"></i></button>';
    }

    function dataAttrs(r, keys) {
        return keys.map(function(k){ return ' data-'+k+'="'+esc(r[k])+'"'; }).join('');
    }

    // Import Excel
    var _importPreview = [];
    $(document).on('click', '#btn-show-import', function(){
        $('#import-file').val('');
        $('#import-step-preview').hide();
        $('#import-step-upload').show();
        $('#import-upload-err').hide();
        $('#btn-simpan-import,#btn-parse-import').hide();
        _importPreview = [];
        $('#modalImport').modal('show');
    });

    $(document).on('change', '#import-file', function(){
        $('#btn-parse-import').toggle(!!this.files.length);
    });

    $('#btn-parse-import').on('click', function(){
        var f = $('#import-file')[0].files[0];
        if (!f) return;
        var fd = new FormData();
        fd.append('file', f);
        fd.append(CSRF, HASH);
        var restore = btnLoading($(this));
        $.ajax({
            url: BASE + '/Ketentuanbpr/previewImportExcel',
            method: 'POST', data: fd, processData: false, contentType: false,
            dataType: 'json',
            headers: {'X-Requested-With':'XMLHttpRequest'},
            success: function(res){
                restore(); updateCsrf(res);
                if (res.status !== 'success'){ $('#import-upload-err').text(res.message).show(); return; }
                _importPreview = res.preview;
                renderImportPreview(res);
                $('#import-step-upload').hide();
                $('#import-step-preview').show();
                $('#btn-parse-import').hide();
                $('#btn-simpan-import').toggle(res.ok_count > 0);
            },
            error: function(xhr){ restore(); $('#import-upload-err').text(parseErr(xhr)||'Gagal memproses file').show(); }
        });
    });

    function renderImportPreview(res){
        $('#import-ok-count').text(res.ok_count + ' baris siap disimpan');
        $('#import-total-count').text('dari ' + res.total + ' baris terbaca');
        var body = $('#import-preview-table tbody').empty();
        $.each(res.preview, function(i, r){
            var badge = r.status === 'ok' ? '<span class="badge badge-success">Siap</span>'
                    : r.status === 'skip' ? '<span class="badge badge-warning" title="'+esc(r.dup_reason||'')+'">Lewati</span>'
                    : '<span class="badge badge-danger" title="'+esc((r.errors||[]).join(', '))+'">Error</span>';
            body.append('<tr><td>'+r.line+'</td><td>'+esc(r.judulketentuan)+'</td><td>'+esc(r.nomorketentuan)+'</td><td>'+(r.tanggalberlaku?fmtTanggal(r.tanggalberlaku):'-')+'</td><td>'+badge+'</td></tr>');
        });
    }

    $('#btn-simpan-import').on('click', function(){
        var okRows = _importPreview.filter(function(r){ return r.status === 'ok'; });
        if (!okRows.length) return;
        var restore = btnLoading($(this));
        doPost('Ketentuanbpr/confirmImportExcel', { rows: JSON.stringify(okRows) }, function(res){
            restore();
            if (res.status === 'success'){
                $('#modalImport').modal('hide');
                toast(res.message);
                loadData();
                refreshApprovalStatus();
            } else {
                toast(res.message, 'error');
            }
        }, function(m){ restore(); toast(m, 'error'); });
    });

    // LOAD DATA
    function loadData() {
        $('#skeleton-container').show();
        $('#table-wrapper,#empty-state,#error-state').hide();
        $.ajax({
            url: BASE+'/Ketentuanbpr/getData', method:'GET', dataType:'json',
            headers: {'X-Requested-With':'XMLHttpRequest'},
            success: function(res){
                $('#skeleton-container').hide(); $('#table-wrapper').show();
                if (res&&res.status==='success'){ renderNodataState(!!res.is_null_record); renderRows(res.data||[]); pollUnreadCount(); }
                else { $('#empty-state').show(); toast((res&&res.message)||'Gagal memuat data','error'); }
            },
            error: function(xhr){
                $('#skeleton-container').hide(); $('#table-wrapper').show();
                var errMap={401:'Sesi berakhir. Mengalihkan ke login...',403:'Akses ditolak (403).',404:'Endpoint tidak ditemukan (404).'};
                var m=errMap[xhr.status]||parseErr(xhr)||'Gagal memuat data dari server.';
                if (xhr.status===401) setTimeout(function(){ location.href=BASE+'/login'; },1500);
                $('#error-state-msg').text(m); $('#error-state').show(); toast(m,'error');
            }
        });
    }

    // APPROVAL STATUS
    function refreshApprovalStatus() {
        doGet('Ketentuanbpr/getApprovalStatus', function(res){
            if (!res||res.status!=='success') return;
            updateBadge('#badge-approval-dekom','Direktur Yang Membawahi Fungsi Kepatuhan',res.dekom_approved,res.dekom_approved_at);
        });
    }

    function updateBadge(sel, label, approved, at) {
        var bd=$(sel);
        bd.removeClass('badge-status-secondary badge-status-success')
          .addClass(approved ? 'badge-status-success' : 'badge-status-secondary');
        var icon = approved ? 'fa-check-circle' : 'fa-clock';
        var sub  = approved ? 'Disetujui · '+timeAgo(at) : 'Belum disetujui seluruhnya';
        bd.html('<i class="fas '+icon+'"></i><div><span class="approval-status-label">'+label+'</span><br>'
            +'<span class="approval-status-sub">'+sub+'</span></div>');
    }

    // CRUD EXEC
    var execTambah = debounce(function(){
        var judul  = $.trim($('#t-judulketentuan').val());
        var nomor  = $.trim($('#t-nomorketentuan').val());
        if (!judul||!nomor){ $('#tambah-err').text('Judul Ketentuan dan Nomor Ketentuan wajib diisi.').show(); return; }
        $('#tambah-err').hide();
        var restore = btnLoading($('#btn-simpan-tambah'));
        doPost('Ketentuanbpr/tambah', {
            judulketentuan  : judul,
            nomorketentuan  : nomor,
            tanggalberlaku  : $('#t-tanggalberlaku').val(),
            keterangan      : $('#t-keterangan').val()
        }, function(res){
            restore();
            res.status==='success'
                ? ($('#modalTambah').modal('hide'), toast(res.message||'Data berhasil ditambahkan'), loadData(), refreshApprovalStatus())
                : $('#tambah-err').text(res.message||'Gagal menyimpan data').show();
        }, function(m){ restore(); $('#tambah-err').text(m).show(); });
    }, 400);

    var execUbah = debounce(function(){
        var id    = $('#u-id').val();
        var judul = $.trim($('#u-judulketentuan').val());
        var nomor = $.trim($('#u-nomorketentuan').val());
        if (!judul||!nomor){ $('#ubah-err').text('Judul Ketentuan dan Nomor Ketentuan wajib diisi.').show(); return; }
        $('#ubah-err').hide();
        var restore = btnLoading($('#btn-simpan-ubah'));
        doPost('Ketentuanbpr/ubah', {
            id              : id,
            judulketentuan  : judul,
            nomorketentuan  : nomor,
            tanggalberlaku  : $('#u-tanggalberlaku').val(),
            keterangan      : $('#u-keterangan').val()
        }, function(res){
            restore();
            res.status==='success'
                ? ($('#modalUbah').modal('hide'), toast(res.message||'Data berhasil diubah'), loadData(), refreshApprovalStatus())
                : $('#ubah-err').text(res.message||'Gagal mengubah data').show();
        }, function(m){ restore(); $('#ubah-err').text(m).show(); });
    }, 400);

    var execHapus = debounce(function(){
        var restore = btnLoading($('#btn-konfirmasi-hapus'));
        doPost('Ketentuanbpr/hapus', { id: $('#h-id').val() }, function(res){
            restore(); $('#modalHapus').modal('hide');
            res.status==='success'
                ? (toast(res.message||'Data berhasil dihapus'), loadData(), refreshApprovalStatus())
                : toast(res.message||'Gagal menghapus data','error');
        }, function(m){ restore(); toast(m,'error'); });
    }, 400);

    function makeApproveExec(idSel, actionSel, modalSel, btnSel, urlMap){
        return debounce(function(){
            var id=$(idSel).val(), action=$(actionSel).val();
            var restore = btnLoading($(btnSel));
            doPost(urlMap[action]||urlMap.default, {id:id}, function(res){
                restore(); $(modalSel).modal('hide');
                res.status==='success'
                    ? (toast(res.message), loadData(), refreshApprovalStatus())
                    : toast(res.message||'Gagal menyimpan approval','error');
            }, function(m){ restore(); toast(m,'error'); });
        }, 400);
    }

    var execApproveKom = makeApproveExec('#ak-id','#ak-action','#modalApproveKom','#btn-konfirmasi-approve-kom',
        { approve:'Ketentuanbpr/approveKomRow', unapprove:'Ketentuanbpr/unapproveKomRow' });

    function bulkApproval(url, msg){
        if (!confirm(msg)) return;
        doPost(url, {}, function(res){ toast(res.message, res.status==='success'?'success':'error'); loadData(); refreshApprovalStatus(); });
    }

    var execKomentar = debounce(function(){
        var teks = $.trim($('#k-teks').val());
        if (!teks){ toast('Komentar tidak boleh kosong.','error'); return; }
        if (teks.length<3){ toast('Komentar minimal 3 karakter.','error'); return; }
        var restore = btnLoading($('#btn-kirim-komentar'));
        doPost('Ketentuanbpr/tambahKomentar', { komentar:teks, fullname:FNAME }, function(res){
            restore();
            if (res.status==='success'){
                $('#k-teks').val('');
                $('#k-char-count').text('0 / 1000').removeClass('danger');
                toast(res.message||'Komentar terkirim');
                loadKomentarList(true);
            } else { toast(res.message||'Gagal mengirim komentar','error'); }
        }, function(m){ restore(); toast(m,'error'); });
    }, 400);

    function markAllRead(){
        doPost('Ketentuanbpr/markKomentarAsRead', {}, function(res){
            if (res&&res.status==='success') updateNotifBadge(0);
        });
    }

    // Tambah
    $(document).on('click','#btn-show-tambah', function(){
        $('#t-judulketentuan,#t-nomorketentuan,#t-tanggalberlaku,#t-keterangan').val('');
        $('#tambah-err').hide(); $('#modalTambah').modal('show');
        setTimeout(function(){ $('#t-judulketentuan').focus(); },450);
    });

    $('#btn-nodata').on('click', function(){
        if (!confirm('Konfirmasi: tandai bahwa tidak ada data Ketentuan Internal Yang Dimiliki BPR pada periode ini?')) return;
        var restore = btnLoading($(this));
        doPost('Ketentuanbpr/setNodataAjax', {}, function(res){
            restore();
            if (res.status === 'success') { toast(res.message); loadData(); refreshApprovalStatus(); }
            else toast(res.message || 'Gagal', 'error');
        }, function(m){ restore(); toast(m, 'error'); });
    });

$('#btn-batal-nodata').on('click', function(){
    if (!confirm('Batalkan status "Tidak Ada Data"?')) return;
    var restore = btnLoading($(this));
    doPost('Ketentuanbpr/batalNodataAjax', {}, function(res){
        restore();
        if (res.status === 'success') { toast(res.message); loadData(); refreshApprovalStatus(); }
        else toast(res.message || 'Gagal', 'error');
    }, function(m){ restore(); toast(m, 'error'); });
});

    // Ubah
    $(document).on('click','.btn-ubah', function(){
        var d=$(this).data();
        $('#u-id').val(d.id);
        $('#u-judulketentuan').val(d.judulketentuan);
        $('#u-nomorketentuan').val(d.nomorketentuan);
        $('#u-tanggalberlaku').val(d.tanggalberlaku||'');
        $('#u-keterangan').val(d.keterangan||'');
        $('#ubah-err').hide(); $('#modalUbah').modal('show');
        setTimeout(function(){ $('#u-judulketentuan').focus(); refreshUbahCounters(); },450);
    });

    // Hapus
    $(document).on('click','.btn-hapus', function(){
        var d=$(this).data();
        $('#h-id').val(d.id);
        $('#h-judulketentuan').text(d.judulketentuan||'-');
        $('#h-nomorketentuan').text(d.nomorketentuan||'-');
        $('#h-tanggalberlaku').text(d.tanggalberlaku ? fmtTanggal(d.tanggalberlaku) : '-');
        $('#modalHapus').modal('show');
    });

    // Approval Dekom
    $(document).on('click','.btn-toggle-kom', function(){
        var d=$(this).data(), ap=d.action==='approve';
        $('#ak-id').val(d.id); $('#ak-action').val(d.action);
        $('#ak-judulketentuan').text(d.judulketentuan||'-');
        $('#ak-nomorketentuan').text(d.nomorketentuan||'-');
        $('#ak-btn-label').text(ap?'Setujui':'Batalkan');
        $('#btn-konfirmasi-approve-kom').removeClass('btn-success btn-danger').addClass(ap?'btn-success':'btn-danger');
        $('#ak-msg').html(ap
            ? '<i class="fas fa-check-circle text-success"></i> Anda akan <strong>menyetujui</strong> data ini sebagai <strong>Direktur yang membawahi fungsi kepatuhan</strong>:'
            : '<i class="fas fa-ban text-danger"></i> Anda akan <strong>membatalkan approval</strong> data ini:');
        $('#modalApproveKom').modal('show');
    });

    // Komentar global
    $('#btn-komentar-global').on('click', function(){
        loadKomentarList(true);
        markAllRead();
        $('#modalKomentar').modal('show');
    });

    // Retry
    $('#btn-retry').on('click', function(){ loadData(); refreshApprovalStatus(); });

    // Simpan
    $('#btn-simpan-tambah').on('click', execTambah);
    $('#btn-simpan-ubah').on('click', execUbah);
    $('#btn-konfirmasi-hapus').on('click', execHapus);
    $('#btn-konfirmasi-approve-kom').on('click', execApproveKom);
    $('#btn-kirim-komentar').on('click', execKomentar);

    // Enter shortcut modal
    $('#modalTambah,#modalUbah').on('keydown', function(e){
        if (e.key==='Enter'&&!$(e.target).is('textarea')) $(this).is('#modalTambah') ? execTambah() : execUbah();
    });

    // Komentar input
    $('#k-teks')
        .on('keydown', function(e){ if(e.ctrlKey&&e.key==='Enter'){ e.preventDefault(); execKomentar(); } })
        .on('input', function(){
            var l=$(this).val().length;
            var el=$('#k-char-count'); el.text(l+' / 1000');
            l>950 ? el.addClass('danger') : el.removeClass('danger');
        });

    // Bulk approval
    $.each({
        '.btn-approve-all-dekom' : ['Ketentuanbpr/approveSemuaKom','Setujui SEMUA data sebagai Direktur Yang Membawahi Fungsi Kepatuhan?'],
        '.btn-unapprove-all-dekom':['Ketentuanbpr/unapproveSemuaKom','Batalkan SEMUA approval Direktur yang membawahkan fungsi kepatuhan?']
    }, function(sel,cfg){
        $(sel).on('click', throttle(function(){ bulkApproval(cfg[0],cfg[1]); },2000));
    });

    $(document).on('hidden.bs.modal','.modal', function(){
        $(this).find('.alert-danger').hide().text('');
        $(this).find('input[type=text],textarea').not('[readonly]').val('');
        $(this).find('.char-input').each(function(){
            var cid = $(this).data('counter');
            var max = parseInt($(this).data('max')) || 0;
            if (cid && max) $('#'+cid).text('0 / '+max).removeClass('warn danger');
        });
    });

    var _syncChecked = false;
    var _syncData    = null;
 
    function syncCheckPrev(cb) {
        if (_syncChecked) { cb && cb(_syncData); return; }
        $.ajax({
            url: BASE + '/Ketentuanbpr/checkPrevPeriode',
            method: 'GET', dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                _syncChecked = true; _syncData = res; cb && cb(res);
            },
            error: function() {
                _syncChecked = true;
                _syncData = { status: 'error' };
                cb && cb(_syncData);
            }
        });
    }
 
    function initSyncBtn() {
        var btn = $('#btn-sync-periode');
        var tip = $('#sync-tooltip');
        if (!btn.length) return;
 
        tip.text('Memeriksa periode sebelumnya…');
 
        syncCheckPrev(function(res) {
            if (res && res.status === 'found') {
                btn.prop('disabled', false);
                tip.text('Sinkron dari ' + (res.prev_label || 'periode sebelumnya'));
            } else {
                btn.prop('disabled', true);
                tip.text(
                    res && res.message
                        ? res.message
                        : 'Tidak ada data di periode sebelumnya'
                );
            }
        });
 
        btn.on('click', function() {
            if (!_syncData || _syncData.status !== 'found') return;
 
            $('#sync-current-label').text(_syncData.current_label || '-');
            $('#sync-prev-label').text(_syncData.prev_label || '-');
 
            var rows         = _syncData.rows         || [];
            var existingNomors = _syncData.existing_nomors || [];
 
            // O(1) lookup set
            var nomorSet = {};
            for (var i = 0; i < existingNomors.length; i++) {
                nomorSet[existingNomors[i]] = true;
            }
 
            var newCount = 0;
            var html = '';
            for (var j = 0; j < rows.length; j++) {
                var r    = rows[j];
                var skip = !!nomorSet[r.nomorketentuan];
                if (!skip) newCount++;
 
                html += '<div class="sync-prev-item' + (skip ? ' will-skip' : '') + '">'
                      + '<span class="sync-prev-item-idx">' + (j + 1) + '</span>'
                      + '<div class="sync-prev-item-body">'
                      +   '<div class="sync-prev-item-judul">' + esc(r.judulketentuan || '-') + '</div>'
                      +   '<div class="sync-prev-item-meta">'
                      +     '<i class="fas fa-hashtag" style="font-size:10px;"></i> '
                      +     esc(r.nomorketentuan || '-')
                      +     (r.tanggalberlaku
                            ? ' &nbsp;·&nbsp; <i class="fas fa-calendar-alt" style="font-size:10px;"></i> '
                              + esc(r.tanggalberlaku)
                            : '')
                      +   '</div>'
                      + '</div>'
                      + (skip ? '<span class="sync-skip-badge">Sudah ada</span>' : '')
                      + '</div>';
            }
 
            $('#sync-count-num').text(newCount);
            $('#sync-preview-list').html(
                html || '<div style="padding:14px;text-align:center;color:#94a3b8;font-size:13px;">Tidak ada data</div>'
            );
            $('#btn-konfirmasi-sync').prop('disabled', newCount === 0);
 
            $('#modalSyncPeriode').modal('show');
        });
    }
 
    var execSync = debounce(function() {
        var restore = btnLoading($('#btn-konfirmasi-sync'));
        var btn = $('#btn-sync-periode');
        btn.addClass('syncing');
 
        doPost('Ketentuanbpr/syncFromPrevPeriode', {}, function(res) {
            restore(); btn.removeClass('syncing');
            $('#modalSyncPeriode').modal('hide');
 
            if (res.status === 'success') {
                toast(res.message || 'Sinkronisasi berhasil', 'success');
                loadData();
                refreshApprovalStatus();
                _syncChecked = false;
                _syncData    = null;
                $('#btn-sync-periode').prop('disabled', true);
                $('#sync-tooltip').text('Data sudah disinkronisasi');
            } else {
                toast(res.message || 'Gagal sinkronisasi', 'error');
            }
        }, function(m) {
            restore(); btn.removeClass('syncing');
            toast(m, 'error');
        });
    }, 500);
 
    $('#btn-konfirmasi-sync').on('click', execSync);
    initSyncBtn();

    loadData();
    refreshApprovalStatus();
    pollUnreadCount();
    setInterval(function(){ pollUnreadCount(); refreshApprovalStatus(); }, 10000);
}
</script>
