<div id="toast-wrap" style="position:fixed;top:20px;right:20px;z-index:99999;min-width:290px;pointer-events:none;"></div>
<div class="container-fluid">
    <a href="<?= base_url('Showaudit') ?>" class="btn-kembali mb-3">
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

    <div class="card card-body mb-3 shadow-sm">
        <div class="page-header">
            <div style="position:relative;min-height:50px;" class="mb-3">
                <div id="badge-approval-dekom" class="approval-status-badge badge-status-secondary" style="position:absolute;left:0;top:0;">
                    <i class="fas fa-clock"></i>
                    <div>
                        <span class="approval-status-label">Direktur Utama</span><br>
                        <span class="approval-status-sub">Belum disetujui seluruhnya</span>
                    </div>
                </div>
            </div>
            <h1 class="page-title"><?= esc($judul) ?></h1>
            <div class="page-subtitle">
                <?php
                $semDisplay = esc($periodeDetail['semester']);
                $semNum = strtolower($semDisplay) === 'ganjil' ? '1' : (strtolower($semDisplay) === 'genap' ? '2' : $semDisplay);
                ?>
                Semester <?= $semNum ?> Tahun <?= esc($periodeDetail['tahun']) ?>
            </div>
            <span class="badge-namabpr"><?= esc($bprData['namabpr'] ?? 'Nama BPR') ?></span>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center" style="background: var(--grad);">
            <h5 class="m-0 font-weight-bold text-white">
                <i class="fas fa-file-alt"></i> Hasil Pemantauan Komitmen Auditee
            </h5>

            <div class="d-flex align-items-center" style="gap:8px;">
                <?php if (($userInGroupPE ?? false) || ($userInGroupAdmin ?? false) || ($userInGroupAuditor ?? false)): ?>
                    <button type="button" class="btn btn-secondary btn-sm" id="btn-nodata">
                        <i class="fas fa-times-circle"></i> Tidak Ada Data
                    </button>
                <?php endif; ?>
                <?php if (($userInGroupPE ?? false) || ($userInGroupAdmin ?? false) || ($userInGroupAuditor ?? false)): ?>
                    <button type="button" class="btn btn-success btn-sm" id="btn-show-import" title="Impor Excel">
                        <i class="fas fa-file-excel"></i> Impor Excel
                    </button>
                <?php endif; ?>
                <?php if (($userInGroupPE ?? false) || ($userInGroupAdmin ?? false) || ($userInGroupAuditor ?? false)): ?>
                    <button type="button" class="btn btn-primaryy btn-sm" id="btn-show-tambah" title="Tambah Data">
                        <i class="fas fa-plus"></i>
                    </button>
                <?php endif; ?>

                <?php if (($userInGroupPE ?? false) || ($userInGroupAdmin ?? false) || ($userInGroupAuditor ?? false)): ?>
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
                    || ($userInGroupPE ?? false) || ($userInGroupDekom2 ?? false) || ($userInGroupDireksi2 ?? false) || ($userInGroupAuditor ?? false)
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
                        <small>Tidak terdapat Hasil Pemantauan Komitmen Auditee pada periode ini.</small>
                    </div>
                </div>
                <?php if (($userInGroupPE ?? false) || ($userInGroupAdmin ?? false) || ($userInGroupAuditor ?? false)): ?>
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
                    <div id="cards-pemantauanaudit" class="ketentuan-cards-grid"></div>

                    <div id="empty-state" class="empty-state-box" style="display:none;">
                        <i class="fas fa-file-slash fa-3x text-muted mb-3 d-block"></i>
                        <h6 class="text-muted mb-1">Belum ada data Hasil Pemantauan Komitmen Auditee</h6>
                        <p class="text-muted small mb-3">Data Hasil Pemantauan Komitmen Auditee untuk periode ini belum diinput.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mt-2 mb-2 d-flex justify-content-center flex-wrap" style="gap:12px;">
        <?php if (($userInGroupAdmin ?? false) || ($userInGroupDireksi ?? false) || ($userInGroupDireksi2 ?? false)): ?>
            <div class="card shadow-sm action-card d-inline-block" style="min-width:220px;">
                <div class="card-body py-2 text-center">
                    <small class="d-block font-weight-bold text-dark mb-2">
                        <i class="fas fa-user-tie text-success"></i> Direktur Utama
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
        <?php endif; ?>
        <!-- <div class="card shadow-sm action-card d-inline-block" style="min-width:200px;">
            <div class="card-body py-2 text-center">
                <small class="d-block font-weight-bold text-dark mb-2">
                    <i class="fas fa-file-export text-warning"></i> Export Data
                </small>
                <a href="<?= base_url('Pemantauanaudit/exporttxtpemantauanaudit') ?>" class="btn btn-warning btn-sm">
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
                <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Tambah Data Hasil Pemantauan Komitmen Auditee</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="font-weight-bold">Realisasi Komitmen Auditee <span class="text-danger">*</span></label>
                            <textarea class="form-control char-input" id="t-realisasi" rows="5" placeholder="Masukkan realisasi komitmen auditee..." maxlength="4000" data-counter="t-realisasi-count" data-max="4000"></textarea>
                            <div class="char-counter-wrap"><span id="t-realisasi-count" class="char-counter">0 / 4000</span></div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="font-weight-bold">Hasil Pemantauan Komitmen <span class="text-danger">*</span></label>
                            <textarea class="form-control char-input" id="t-hasil" rows="5" placeholder="Masukkan hasil pemantauan komitmen..." maxlength="4000" data-counter="t-hasil-count" data-max="4000"></textarea>
                            <div class="char-counter-wrap"><span id="t-hasil-count" class="char-counter">0 / 4000</span></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Status Realisasi Komitmen Auditee <span class="text-danger">*</span></label>
                            <select class="form-control" id="t-status">
                                <option value="">-- Pilih Status --</option>
                                <option value="01">Selesai</option>
                                <option value="02">Proses</option>
                                <option value="03">Belum Ditindaklanjuti</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div id="tambah-err" class="alert alert-danger mt-2" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Batal</button>
                <button type="button" class="btn btn-primary" id="btn-simpan-tambah"><i class="fas fa-save"></i> Simpan Data</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL UBAH -->
<div class="modal fade" id="modalUbah" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header modal-header-sdm">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Ubah Data Hasil Pemantauan Komitmen Auditee</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="u-id">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="font-weight-bold">Realisasi Komitmen Auditee <span class="text-danger">*</span></label>
                            <textarea class="form-control char-input" id="u-realisasi" rows="5" maxlength="4000" data-counter="u-realisasi-count" data-max="4000"></textarea>
                            <div class="char-counter-wrap"><span id="u-realisasi-count" class="char-counter">0 / 4000</span></div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="font-weight-bold">Hasil Pemantauan Komitmen <span class="text-danger">*</span></label>
                            <textarea class="form-control char-input" id="u-hasil" rows="5" maxlength="4000" data-counter="u-hasil-count" data-max="4000"></textarea>
                            <div class="char-counter-wrap"><span id="u-hasil-count" class="char-counter">0 / 4000</span></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Status Realisasi Komitmen Auditee <span class="text-danger">*</span></label>
                            <select class="form-control" id="u-status">
                                <option value="">-- Pilih Status --</option>
                                <option value="01">Selesai</option>
                                <option value="02">Proses</option>
                                <option value="03">Belum Ditindaklanjuti</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="alert alert-warning mb-0">
                    <small><i class="fas fa-info-circle"></i> <strong>Perhatian:</strong> Mengubah data akan me-reset status approval.</small>
                </div>
                <div id="ubah-err" class="alert alert-danger mt-2" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Batal</button>
                <button type="button" class="btn btn-primary" id="btn-simpan-ubah"><i class="fas fa-save"></i> Simpan Perubahan</button>
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
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="h-id">
                <p class="mb-3">Anda akan menghapus data berikut:</p>
                <table class="table table-bordered table-sm mb-3">
                    <tr><th width="40%">Realisasi Komitmen Auditee</th><td id="h-realisasi"></td></tr>
                </table>
                <div class="alert alert-danger mb-0"><strong>Peringatan:</strong> Data yang dihapus tidak dapat dikembalikan.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" id="btn-konfirmasi-hapus"><i class="fas fa-trash-alt"></i> Hapus</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Batal</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL APPROVE DEKOM -->
<div class="modal fade" id="modalApproveKom" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#155724;color:white;">
                <h5 class="modal-title"><i class="fas fa-user-tie"></i> Approval Direktur Utama</h5>
                <button type="button" class="close" style="color:white;" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="ak-id">
                <input type="hidden" id="ak-action">
                <p id="ak-msg" class="mb-3"></p>
                <table class="table table-bordered table-sm">
                    <tr><th width="40%">Realisasi Komitmen Auditee</th><td id="ak-realisasi"></td></tr>
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

<!-- MODAL KOMENTAR -->
<div class="modal fade" id="modalKomentar" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content komentar-modal-content">
            <div class="modal-header komentar-modal-header">
                <div class="komentar-modal-title-wrap">
                    <div class="komentar-modal-icon-wrap"><i class="fas fa-comments"></i></div>
                    <div><div class="komentar-modal-title">Komentar — Hasil Pemantauan Komitmen Auditee</div></div>
                </div>
                <button type="button" class="komentar-close-btn" data-dismiss="modal"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body komentar-modal-body">
                <div class="komentar-list-label"><i class="fas fa-history"></i> Riwayat Komentar</div>
                <div id="komentar-list-wrapper" class="komentar-list-wrapper">
                    <div id="komentar-list"><div class="komentar-loading"><i class="fas fa-spinner fa-spin"></i> Memuat...</div></div>
                </div>
                <?php if (($userInGroupAdmin ?? false) || ($userInGroupDekom ?? false) || ($userInGroupDireksi ?? false) || ($userInGroupPE ?? false) || ($userInGroupDekom2 ?? false) || ($userInGroupDireksi2 ?? false) || ($userInGroupAuditor ?? false)): ?>
                    <div class="komentar-input-wrap">
                        <div class="komentar-input-label"><i class="fas fa-pen-alt"></i> Tulis Komentar</div>
                        <textarea id="k-teks" class="komentar-textarea" rows="3" placeholder="Tuliskan komentar, catatan, atau arahan Anda..." maxlength="1000"></textarea>
                        <div class="komentar-input-footer">
                            <span class="komentar-hint"><i class="fas fa-keyboard"></i> Ctrl+Enter untuk kirim</span>
                            <span id="k-char-count" class="komentar-char">0 / 1000</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer komentar-modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal"><i class="fas fa-times"></i> Tutup</button>
                <?php if (($userInGroupAdmin ?? false) || ($userInGroupDekom ?? false) || ($userInGroupDireksi ?? false) || ($userInGroupPE ?? false) || ($userInGroupDekom2 ?? false) || ($userInGroupDireksi2 ?? false) || ($userInGroupAuditor ?? false)): ?>
                    <button type="button" class="btn btn-primary btn-sm" id="btn-kirim-komentar"><i class="fas fa-paper-plane"></i> Kirim</button>
                <?php endif; ?>
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
                <div id="sync-preview-area" style="margin-top:14px;">
                    <div class="sync-count-badge">
                        <i class="fas fa-file-alt"></i>
                        <span id="sync-count-num">0</span> data akan disalin
                    </div>
                    <div class="sync-preview-list" id="sync-preview-list"></div>
                </div>
                <div class="alert alert-warning mb-0 mt-3" style="font-size:12.5px;border-radius:10px;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Perhatian:</strong> Data yang sudah ada di periode aktif
                    <strong>tidak akan ditimpa</strong>. Hanya data baru yang akan ditambahkan.
                    Status approval akan direset.
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #e2e8f0;padding:12px 24px!important;">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="button" id="btn-konfirmasi-sync"
                        class="btn btn-sm"
                        style="background:linear-gradient(135deg,#0f766e,#0d9488);color:white;font-weight:700;border:none;">
                    <i class="fas fa-sync-alt"></i> Sinkronkan Sekarang
                </button>
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
                        Format wajib: <strong>Realisasi Komitmen Auditee | Hasil Pemantauan Komitmen | Status Realisasi Komitmen Auditee</strong>.
                        Kolom Status boleh diisi teks <em>Selesai</em>, <em>Proses</em>, atau <em>Belum Ditindaklanjuti</em>.
                        <a href="<?= base_url('assets/template/Template Impor Hasil Pemantauan Komitmen Auditee.xlsx') ?>">Unduh template</a>.
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
                            <thead><tr><th>#</th><th>Realisasi</th><th>Hasil</th><th>Status</th><th></th></tr></thead>
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

<?php
$_canEdit = (($userInGroupPE ?? false) || ($userInGroupAdmin ?? false) || ($userInGroupAuditor ?? false)) ? 'true' : 'false';
$_canKom = (($userInGroupAdmin ?? false) || ($userInGroupDekom ?? false)) ? 'true' : 'false';
$_base = rtrim(base_url(), '/');
$_csrf = csrf_token();
$_hash = csrf_hash();
$_userId = (int)($userId ?? 0);
$_fullname = addslashes($fullname ?? 'Unknown');
$_periodeId = (int)($periodeId ?? 0);
$_kodebpr = addslashes($kodebpr ?? '');
?>
<script>
(function wait(){if(typeof jQuery==='undefined')return setTimeout(wait,50);jQuery(document).ready(init)})();

function init($){
    var BASE='<?= $_base ?>',CSRF='<?= $_csrf ?>',HASH='<?= $_hash ?>',
        UID=<?= $_userId ?>,FNAME='<?= $_fullname ?>',
        CAN_EDIT=<?= $_canEdit ?>,CAN_KOM=<?= $_canKom ?>;

    /* Status map */
    var STATUS_MAP={
        '01':{label:'Selesai',cls:'status-01',icon:'fa-check-circle'},
        '02':{label:'Proses',cls:'status-02',icon:'fa-spinner'},
        '03':{label:'Belum Ditindaklanjuti',cls:'status-03',icon:'fa-times-circle'}
    };
    function statusPill(val){
        var s=STATUS_MAP[val]||{label:val||'-',cls:'status-xx',icon:'fa-question-circle'};
        return'<span class="status-pill '+s.cls+'"><i class="fas '+s.icon+'"></i> '+s.label+'</span>';
    }

    /* Utilities */
    var ESC_MAP={'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'};
    function esc(s){return s==null?'':String(s).replace(/[&<>"]/g,function(c){return ESC_MAP[c]})}
    function debounce(fn,ms){var t;return function(){var a=arguments,c=this;clearTimeout(t);t=setTimeout(function(){fn.apply(c,a)},ms)}}
    function throttle(fn,ms){var busy=false;return function(){if(busy)return;busy=true;fn.apply(this,arguments);setTimeout(function(){busy=false},ms)}}
    function toast(msg,type){
        type=type||'success';
        var icons={success:'check-circle',error:'exclamation-circle',info:'info-circle'};
        var el=$('<div class="t-item t-'+type+'"><i class="fas fa-'+icons[type]+' fa-lg"></i><div>'+msg+'</div></div>');
        $('#toast-wrap').append(el);
        setTimeout(function(){el.fadeOut(400,function(){$(this).remove()})},3500);
    }
    function csrf(){var d={};d[CSRF]=HASH;return d}
    function updateCsrf(res){
        if(res&&res.csrf_hash)HASH=res.csrf_hash;
        var m=document.cookie.match(/ci_csrf_token=([^;]+)/);
        if(m)HASH=decodeURIComponent(m[1]);
    }

    function parseErr(xhr){try{var r=JSON.parse(xhr.responseText);if(r&&r.message)return r.message}catch(e){}return null}

    var ajaxDefaults={dataType:'json',headers:{'X-Requested-With':'XMLHttpRequest'}};

    function doPost(url,data,cb,errCb){
        $.ajax($.extend({},ajaxDefaults,{
            url:BASE+'/'+url,method:'POST',
            data:$.extend({},csrf(),data),
            success:function(res){updateCsrf(res);cb(res)},
            error:function(xhr){
                var m=parseErr(xhr)||'Terjadi kesalahan server.';
                if(xhr.status===403){m='Sesi keamanan kedaluwarsa. Halaman akan di-refresh...';setTimeout(function(){location.reload()},2000)}
                errCb?errCb(m):toast(m,'error');
            }
        }));
    }

    function doGet(url,params,cb){
        if(typeof params==='function'){cb=params;params={}}
        $.ajax($.extend({},ajaxDefaults,{
            url:BASE+'/'+url,method:'GET',data:params,
            success:cb,
            error:function(xhr){
                var m=parseErr(xhr)||'Gagal memuat data.';
                if(xhr.status===401)setTimeout(function(){location.href=BASE+'/login'},1500);
                toast(m,'error');
            }
        }));
    }

    function btnLoading(btn){var ori=btn.html();btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled',true);return function(){btn.html(ori).prop('disabled',false)}}
    function timeAgo(s){
        if(!s)return'-';
        var d=new Date(s),diff=Math.floor((Date.now()-d.getTime())/60000);
        if(diff<1)return'Baru saja';
        if(diff<60)return diff+' menit lalu';
        if(diff<1440)return Math.floor(diff/60)+' jam lalu';
        return('0'+d.getDate()).slice(-2)+'/'+('0'+(d.getMonth()+1)).slice(-2)+'/'+d.getFullYear()+' '+('0'+d.getHours()).slice(-2)+':'+('0'+d.getMinutes()).slice(-2);
    }
    function updateNotifBadge(count){
        var btn=$('#btn-komentar-global'),badge=$('#notif-global');
        if(count>0){badge.text(count).show();btn.addClass('has-unread')}
        else{badge.hide();btn.removeClass('has-unread')}
    }

    /* Char counter */
    function updateCounter(el){
        var $el=$(el),max=parseInt($el.data('max'))||0,cid=$el.data('counter');
        if(!cid||!max)return;
        var len=$el.val().length,pct=len/max,$cnt=$('#'+cid);
        $cnt.text(len+' / '+max).removeClass('warn danger');
        if(pct>=1)$cnt.addClass('danger');
        else if(pct>=0.85)$cnt.addClass('warn');
    }
    $(document).on('input','.char-input',function(){updateCounter(this)});
    function refreshCounters(scope){$(scope+' .char-input').each(function(){updateCounter(this)})}

    /* Komentar */
    function loadKomentarList(scrollToBottom){
        var list=$('#komentar-list');
        list.html('<div class="komentar-loading"><i class="fas fa-spinner fa-spin"></i> Memuat...</div>');
        $.ajax($.extend({},ajaxDefaults,{
            url:BASE+'/Pemantauanaudit/getKomentar',method:'GET',
            success:function(res){
                var items=Array.isArray(res)?res:[];
                if(!items.length){list.html('<div class="komentar-empty"><i class="fas fa-comment-slash fa-2x mb-2 d-block"></i>Belum ada komentar.</div>');return}
                var parts=[];
                for(var i=0;i<items.length;i++){
                    var k=items[i],own=parseInt(k.user_id)===UID;
                    parts.push('<div class="km-item'+(own?' km-own':'')+'">'
                        +'<div class="km-meta">'
                        +'<span class="km-author"><i class="fas fa-user-circle"></i> '+esc(k.fullname||'?')+(own?' <span class="km-you">Anda</span>':'')+' </span>'
                        +'<span class="km-time"><i class="fas fa-clock"></i> '+timeAgo(k.created_at)+'</span>'
                        +'</div><p class="km-text">'+esc(k.komentar)+'</p></div>');
                }
                list.html(parts.join(''));
                if(scrollToBottom){var w=document.getElementById('komentar-list-wrapper');if(w)setTimeout(function(){w.scrollTop=w.scrollHeight},100)}
            },
            error:function(){list.html('<div class="komentar-empty text-danger"><i class="fas fa-exclamation-circle"></i> Gagal memuat komentar.</div>')}
        }));
    }

    /* Poll — skips hidden tabs */
    var _pollTimer=null;
    function pollUnreadCount(){
        $.ajax($.extend({},ajaxDefaults,{
            url:BASE+'/Pemantauanaudit/getUnreadCounts',method:'GET',
            success:function(res){if(res&&res.status==='success')updateNotifBadge(parseInt(res.count)||0)}
        }));
    }
    function startPolling(){
        clearInterval(_pollTimer);
        _pollTimer=setInterval(function(){
            if(document.hidden)return;
            pollUnreadCount();
            refreshApprovalStatus();
        },10000);
    }
    document.addEventListener('visibilitychange',function(){
        if(!document.hidden){pollUnreadCount();refreshApprovalStatus()}
    });

    /* Render cards — single innerHTML write */
    function renderRows(rows){
        var container=document.getElementById('cards-pemantauanaudit');
        if(!rows||!rows.length){container.innerHTML='';$('#empty-state').show();return}
        $('#empty-state').hide();
        var parts=[];
        for(var i=0;i<rows.length;i++){
            var r=rows[i],dOk=parseInt(r.accdir2)===1;
            var accCls=dOk?'acc-badge-ok':'acc-badge-no';
            var accIcon=dOk?'fa-check-circle':'fa-clock';
            var accTxt=dOk?'Disetujui':'Menunggu Persetujuan';
            var dataA=' data-id="'+esc(r.id)+'" data-realisasi="'+esc(r.realisasi)+'" data-hasil="'+esc(r.hasil)+'" data-status="'+esc(r.status)+'"';
            var dataH=' data-id="'+esc(r.id)+'" data-realisasi="'+esc(r.realisasi)+'"';
            var dataK=' data-id="'+esc(r.id)+'" data-realisasi="'+esc(r.realisasi)+'" data-action="'+(dOk?'unapprove':'approve')+'"';
            var h='<div class="ketentuan-card" id="kcard-'+r.id+'">'
                +'<div class="kcard-header">'
                +'<div class="kcard-nomor"><span class="kcard-index">'+(i+1)+'</span>'
                +'<span class="kcard-nomor-text"><i class="fas fa-search-plus" style="margin-right:4px;opacity:.7;"></i>Hasil Pemantauan Komitmen Auditee #'+(i+1)+'</span></div>'
                +'<div class="kcard-actions">';
            if(CAN_KOM)h+='<i class="fas '+accIcon+' btn-toggle-kom kcard-acc-icon '+(dOk?'acc-ok':'acc-no')+'"'+dataK+' title="'+(dOk?'Batalkan approval':'Setujui')+'"></i>';
            if(CAN_EDIT)h+='<button class="btn btn-sm btn-warning btn-ubah kcard-btn" title="Ubah"'+dataA+'><i class="fas fa-pencil-alt"></i></button>'
                +'<button class="btn btn-sm btn-danger btn-hapus kcard-btn" title="Hapus"'+dataH+'><i class="fas fa-trash-alt"></i></button>';
            h+='</div></div>'
                +'<div class="kcard-body">'
                +'<div class="kcard-col">'
                +'<div class="kcard-detail-section"><span class="kcard-detail-label"><i class="fas fa-tasks"></i> Realisasi Komitmen Auditee</span><span class="kcard-detail-val">'+esc(r.realisasi||'-')+'</span></div>'
                +'<div class="kcard-detail-section"><span class="kcard-detail-label"><i class="fas fa-tag"></i> Status Realisasi Komitmen Auditee</span><div style="margin-top:6px;">'+statusPill(r.status)+'</div></div>'
                +'</div>'
                +'<div class="kcard-col">'
                +'<div class="kcard-detail-section"><span class="kcard-detail-label"><i class="fas fa-poll"></i> Hasil Pemantauan Komitmen</span><span class="kcard-detail-val">'+esc(r.hasil||'-')+'</span></div>'
                +'</div>'
                +'</div>'
                +'<div class="kcard-footer"><span class="kcard-acc-badge '+accCls+'"><i class="fas '+accIcon+'"></i> '+accTxt+'</span></div>'
                +'</div>';
            parts.push(h);
        }
        container.innerHTML=parts.join('');
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

    /* Load data */
    function loadData() {
        $('#skeleton-container').show();
        $('#table-wrapper,#empty-state,#error-state').hide();
        $.ajax({
            url: BASE+'/Pemantauanaudit/getData', method:'GET', dataType:'json',
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

    function refreshApprovalStatus(){
        doGet('Pemantauanaudit/getApprovalStatus',function(res){
            if(!res||res.status!=='success')return;
            updateBadge('#badge-approval-dekom','Direktur Utama',res.dekom_approved,res.dekom_approved_at);
        });
    }

    function updateBadge(sel,label,approved,at){
        var bd=$(sel);
        bd.removeClass('badge-status-secondary badge-status-success').addClass(approved?'badge-status-success':'badge-status-secondary');
        var icon=approved?'fa-check-circle':'fa-clock';
        var sub=approved?'Disetujui · '+timeAgo(at):'Belum disetujui seluruhnya';
        bd.html('<i class="fas '+icon+'"></i><div><span class="approval-status-label">'+label+'</span><br><span class="approval-status-sub">'+sub+'</span></div>');
    }

    var execTambah=debounce(function(){
        var realisasi=$.trim($('#t-realisasi').val()),hasil=$.trim($('#t-hasil').val()),status=$('#t-status').val();
        if(!realisasi||!hasil||!status){$('#tambah-err').text('Semua field wajib diisi.').show();return}
        $('#tambah-err').hide();
        var restore=btnLoading($('#btn-simpan-tambah'));
        doPost('Pemantauanaudit/tambah',{realisasi:realisasi,hasil:hasil,status:status},
            function(res){restore();res.status==='success'?($('#modalTambah').modal('hide'),toast(res.message||'Data berhasil ditambahkan'),loadData(),refreshApprovalStatus()):$('#tambah-err').text(res.message||'Gagal menyimpan data').show()},
            function(m){restore();$('#tambah-err').text(m).show()});
    },400);

    var execUbah=debounce(function(){
        var id=$('#u-id').val(),realisasi=$.trim($('#u-realisasi').val()),hasil=$.trim($('#u-hasil').val()),status=$('#u-status').val();
        if(!id||!realisasi||!hasil||!status){$('#ubah-err').text('Semua field wajib diisi.').show();return}
        $('#ubah-err').hide();
        var restore=btnLoading($('#btn-simpan-ubah'));
        doPost('Pemantauanaudit/ubah',{id:id,realisasi:realisasi,hasil:hasil,status:status},
            function(res){restore();res.status==='success'?($('#modalUbah').modal('hide'),toast(res.message||'Data berhasil diubah'),loadData(),refreshApprovalStatus()):$('#ubah-err').text(res.message||'Gagal mengubah data').show()},
            function(m){restore();$('#ubah-err').text(m).show()});
    },400);

    var execHapus=debounce(function(){
        var restore=btnLoading($('#btn-konfirmasi-hapus'));
        doPost('Pemantauanaudit/hapus',{id:$('#h-id').val()},
            function(res){restore();$('#modalHapus').modal('hide');res.status==='success'?(toast(res.message||'Data berhasil dihapus'),loadData(),refreshApprovalStatus()):toast(res.message||'Gagal menghapus data','error')},
            function(m){restore();toast(m,'error')});
    },400);

    var execApproveKom=debounce(function(){
        var id=$('#ak-id').val(),action=$('#ak-action').val();
        var restore=btnLoading($('#btn-konfirmasi-approve-kom'));
        var url=action==='approve'?'Pemantauanaudit/approveKomRow':'Pemantauanaudit/unapproveKomRow';
        doPost(url,{id:id},
            function(res){restore();$('#modalApproveKom').modal('hide');res.status==='success'?(toast(res.message),loadData(),refreshApprovalStatus()):toast(res.message||'Gagal menyimpan approval','error')},
            function(m){restore();toast(m,'error')});
    },400);

    function bulkApproval(url,msg){
        if(!confirm(msg))return;
        doPost(url,{},function(res){toast(res.message,res.status==='success'?'success':'error');loadData();refreshApprovalStatus()});
    }

    var execKomentar=debounce(function(){
        var teks=$.trim($('#k-teks').val());
        if(!teks){toast('Komentar tidak boleh kosong.','error');return}
        if(teks.length<3){toast('Komentar minimal 3 karakter.','error');return}
        var restore=btnLoading($('#btn-kirim-komentar'));
        doPost('Pemantauanaudit/tambahKomentar',{komentar:teks,fullname:FNAME},
            function(res){restore();if(res.status==='success'){$('#k-teks').val('');$('#k-char-count').text('0 / 1000').removeClass('danger');toast(res.message||'Komentar terkirim');loadKomentarList(true)}else{toast(res.message||'Gagal mengirim komentar','error')}},
            function(m){restore();toast(m,'error')});
    },400);

    function markAllRead(){
        doPost('Pemantauanaudit/markKomentarAsRead',{},function(res){if(res&&res.status==='success')updateNotifBadge(0)});
    }

    /* Events */
    $(document).on('click','#btn-show-tambah',function(){
        $('#t-realisasi,#t-hasil').val('');$('#t-status').val('');$('#tambah-err').hide();
        $('#modalTambah').modal('show');
        setTimeout(function(){$('#t-realisasi').focus()},450);
    });

    $('#btn-nodata').on('click', function(){
        if (!confirm('Konfirmasi: tandai bahwa tidak ada data Hasil Pemantauan Komitmen Auditee pada periode ini?')) return;
        var restore = btnLoading($(this));
        doPost('Pemantauanaudit/setNodataAjax', {}, function(res){
            restore();
            if (res.status === 'success') { toast(res.message); loadData(); refreshApprovalStatus(); }
            else toast(res.message || 'Gagal', 'error');
        }, function(m){ restore(); toast(m, 'error'); });
    });

    $('#btn-batal-nodata').on('click', function(){
        if (!confirm('Batalkan status "Tidak Ada Data"?')) return;
        var restore = btnLoading($(this));
        doPost('Pemantauanaudit/batalNodataAjax', {}, function(res){
            restore();
            if (res.status === 'success') { toast(res.message); loadData(); refreshApprovalStatus(); }
            else toast(res.message || 'Gagal', 'error');
        }, function(m){ restore(); toast(m, 'error'); });
    });

    $(document).on('click','.btn-ubah',function(){
        var d=$(this).data();
        $('#u-id').val(d.id);$('#u-realisasi').val(d.realisasi||'');$('#u-hasil').val(d.hasil||'');$('#u-status').val(d.status||'');
        $('#ubah-err').hide();$('#modalUbah').modal('show');
        setTimeout(function(){$('#u-realisasi').focus();refreshCounters('#modalUbah')},450);
    });

    $(document).on('click','.btn-hapus',function(){
        var d=$(this).data();$('#h-id').val(d.id);$('#h-realisasi').text(d.realisasi||'-');$('#modalHapus').modal('show');
    });

    $(document).on('click','.btn-toggle-kom',function(){
        var d=$(this).data(),ap=d.action==='approve';
        $('#ak-id').val(d.id);$('#ak-action').val(d.action);$('#ak-realisasi').text(d.realisasi||'-');
        $('#ak-btn-label').text(ap?'Setujui':'Batalkan');
        $('#btn-konfirmasi-approve-kom').removeClass('btn-success btn-danger').addClass(ap?'btn-success':'btn-danger');
        $('#ak-msg').html(ap?'<i class="fas fa-check-circle text-success"></i> Anda akan <strong>menyetujui</strong> data ini sebagai <strong>Direktur Utama</strong>:':'<i class="fas fa-ban text-danger"></i> Anda akan <strong>membatalkan approval</strong> data ini:');
        $('#modalApproveKom').modal('show');
    });

    $('#btn-komentar-global').on('click',function(){loadKomentarList(true);markAllRead();$('#modalKomentar').modal('show')});
    $('#btn-retry').on('click',function(){loadData();refreshApprovalStatus()});
    $('#btn-simpan-tambah').on('click',execTambah);
    $('#btn-simpan-ubah').on('click',execUbah);
    $('#btn-konfirmasi-hapus').on('click',execHapus);
    $('#btn-konfirmasi-approve-kom').on('click',execApproveKom);
    $('#btn-kirim-komentar').on('click',execKomentar);

    $('#k-teks')
        .on('keydown',function(e){if(e.ctrlKey&&e.key==='Enter'){e.preventDefault();execKomentar()}})
        .on('input',function(){var l=$(this).val().length,el=$('#k-char-count');el.text(l+' / 1000');l>950?el.addClass('danger'):el.removeClass('danger')});

    $.each({
        '.btn-approve-all-dekom':['Pemantauanaudit/approveSemuaKom','Setujui SEMUA data sebagai Direktur Utama?'],
        '.btn-unapprove-all-dekom':['Pemantauanaudit/unapproveSemuaKom','Batalkan SEMUA approval Direktur Utama?']
    },function(sel,cfg){$(sel).on('click',throttle(function(){bulkApproval(cfg[0],cfg[1])},2000))});

    $(document).on('hidden.bs.modal','.modal',function(){
        $(this).find('.alert-danger').hide().text('');
        $(this).find('textarea').not('[readonly]').val('');
        $(this).find('select').val('');
        $(this).find('.char-input').each(function(){
            var cid=$(this).data('counter'),max=parseInt($(this).data('max'))||0;
            if(cid&&max)$('#'+cid).text('0 / '+max).removeClass('warn danger');
        });
    });

    (function initSync(){
        var syncBtn = document.getElementById('btn-sync-periode');
        var syncTip = document.getElementById('sync-tooltip');
        if (!syncBtn) return;

        var _syncChecked = false;
        var _syncData    = null;

        function syncCheckPrev(cb) {
            if (_syncChecked) { cb && cb(_syncData); return; }
            $.ajax({
                url: BASE + '/Pemantauanaudit/checkPrevPeriode',
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function(res) { _syncChecked = true; _syncData = res; cb && cb(res); },
                error:   function()    { _syncChecked = true; _syncData = { status: 'error' }; cb && cb(_syncData); }
            });
        }

        syncTip.textContent = 'Memeriksa periode sebelumnya…';

        syncCheckPrev(function(res) {
            if (res && res.status === 'found') {
                syncBtn.disabled    = false;
                syncTip.textContent = 'Sinkron dari ' + (res.prev_label || 'periode sebelumnya');
            } else {
                syncBtn.disabled    = true;
                syncTip.textContent = (res && res.message) ? res.message : 'Tidak ada data di periode sebelumnya';
            }
        });

        $(document).on('click', '#btn-sync-periode', function() {
            if (!_syncData || _syncData.status !== 'found') return;

            $('#sync-current-label').text(_syncData.current_label || '-');
            $('#sync-prev-label').text(_syncData.prev_label || '-');

            var rows         = _syncData.rows         || [];
            var existingKeys = _syncData.existing_keys || [];

            var keySet = {};
            $.each(existingKeys, function(i, k) { keySet[k] = true; });

            var newCount = 0, html = '';
            $.each(rows, function(j, r) {
                // composite key: realisasi[100] || hasil[100]
                var compositeKey = (r.realisasi || '').substring(0, 100)
                    + '||' + (r.hasil || '').substring(0, 100);
                var skip = !!keySet[compositeKey];
                if (!skip) newCount++;

                var titlePreview = (r.realisasi || '').substring(0, 80)
                    + ((r.realisasi || '').length > 80 ? '…' : '');
                var subPreview = (r.hasil || '').substring(0, 60)
                    + ((r.hasil || '').length > 60 ? '…' : '');

                var statusLabels = {'01':'Selesai','02':'Proses','03':'Belum Ditindaklanjuti'};
                var statusLabel  = statusLabels[r.status] || r.status || '-';

                html += '<div class="sync-prev-item' + (skip ? ' will-skip' : '') + '">'
                    + '<span class="sync-prev-item-idx">' + (j + 1) + '</span>'
                    + '<div class="sync-prev-item-body">'
                    + '<div class="sync-prev-item-title">'
                    + '<i class="fas fa-search-plus" style="color:#0d9488;font-size:10px;margin-right:4px;"></i>'
                    + esc(titlePreview || '-')
                    + '</div>'
                    + '<div class="sync-prev-item-preview">'
                    + '<strong>Hasil:</strong> ' + esc(subPreview)
                    + ' &nbsp;·&nbsp; <strong>Status:</strong> ' + esc(statusLabel)
                    + '</div>'
                    + '</div>'
                    + (skip ? '<span class="sync-skip-badge">Sudah ada</span>' : '')
                    + '</div>';
            });

            $('#sync-count-num').text(newCount);
            $('#sync-preview-list').html(html
                || '<div style="padding:14px;text-align:center;color:#94a3b8;font-size:13px;">Tidak ada data</div>');
            $('#btn-konfirmasi-sync').prop('disabled', newCount === 0);
            $('#modalSyncPeriode').modal('show');
        });

        var _syncPending = false;
        $(document).on('click', '#btn-konfirmasi-sync', debounce(function() {
            if (_syncPending) return;
            _syncPending = true;

            var restore = btnLoading($('#btn-konfirmasi-sync'));
            $('#btn-sync-periode').addClass('syncing');

            $.ajax({
                url: BASE + '/Pemantauanaudit/syncFromPrevPeriode',
                method: 'POST',
                dataType: 'json',
                data: $.extend({}, csrf()),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function(res) {
                    _syncPending = false;
                    restore();
                    $('#btn-sync-periode').removeClass('syncing');
                    updateCsrf(res);
                    $('#modalSyncPeriode').modal('hide');
                    if (res && res.status === 'success') {
                        toast(res.message || 'Sinkronisasi berhasil', 'success');
                        loadData();
                        refreshApprovalStatus();
                        _syncChecked = false;
                        _syncData = null;
                        syncBtn.disabled = true;
                        syncTip.textContent = 'Data sudah disinkronisasi';
                    } else {
                        toast((res && res.message) || 'Gagal sinkronisasi', 'error');
                    }
                },
                error: function(xhr) {
                    _syncPending = false;
                    restore();
                    $('#btn-sync-periode').removeClass('syncing');
                    toast(parseErr(xhr) || 'Terjadi kesalahan server.', 'error');
                }
            });
        }, 500));

        var _importPreview = [];
        var STATUS_LABELS = {'01':'Selesai','02':'Proses','03':'Belum Ditindaklanjuti'};

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

        $(document).on('click', '#btn-parse-import', function(){
            var f = $('#import-file')[0].files[0];
            if (!f) return;
            var fd = new FormData();
            fd.append('file', f);
            fd.append(CSRF, HASH);
            var restore = btnLoading($(this));
            $.ajax({
                url: BASE + '/Pemantauanaudit/previewImportExcel',
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
                var badge = r.row_status === 'ok' ? '<span class="badge badge-success">Siap</span>'
                        : '<span class="badge badge-danger" title="'+esc((r.errors||[]).join(', '))+'">Error</span>';
                var rShort = (r.realisasi||'').length > 55 ? r.realisasi.substring(0,55)+'…' : r.realisasi;
                var hShort = (r.hasil||'').length > 55 ? r.hasil.substring(0,55)+'…' : r.hasil;
                var statusTxt = r.status ? esc(STATUS_LABELS[r.status]||r.status) : '<span class="text-danger">'+esc(r.status_raw||'-')+'</span>';
                body.append('<tr><td>'+r.line+'</td><td>'+esc(rShort||'-')+'</td><td>'+esc(hShort||'-')+'</td>'
                    +'<td>'+statusTxt+'</td><td>'+badge+'</td></tr>');
            });
        }

        $(document).on('click', '#btn-simpan-import', function(){
            var okRows = _importPreview.filter(function(r){ return r.row_status === 'ok'; });
            if (!okRows.length) return;
            var restore = btnLoading($(this));
            doPost('Pemantauanaudit/confirmImportExcel', { rows: JSON.stringify(okRows) }, function(res){
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
                    
    })();

    /* Init */
    loadData();
    refreshApprovalStatus();
    pollUnreadCount();
    startPolling();
}
</script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
:root{--brand:#4f46e5;--brand-light:#818cf8;--brand-dark:#3730a3;--brand-bg:#eef2ff;--success:#10b981;--success-bg:#d1fae5;--danger:#ef4444;--danger-bg:#fee2e2;--warning:#f59e0b;--warning-bg:#fef3c7;--info:#06b6d4;--gray-50:#f8fafc;--gray-100:#f1f5f9;--gray-200:#e2e8f0;--gray-400:#94a3b8;--gray-500:#64748b;--gray-700:#334155;--gray-800:#1e293b;--gray-900:#0f172a;--blue:#1f2aa8;--r-sm:8px;--r:12px;--r-lg:16px;--sh-sm:0 1px 3px rgba(0,0,0,.08),0 1px 2px rgba(0,0,0,.06);--sh:0 4px 6px -1px rgba(0,0,0,.1),0 2px 4px -1px rgba(0,0,0,.06);--sh-lg:0 10px 25px -3px rgba(0,0,0,.1),0 4px 6px -2px rgba(0,0,0,.05);--tr:all .2s cubic-bezier(.4,0,.2,1);--grad:linear-gradient(135deg,#141863 0%,#1f2aa8 100%)}
*{font-family:'Plus Jakarta Sans',sans-serif}
.page-header{text-align:center;padding:32px 20px 24px;background:white;border-bottom:1px solid var(--gray-100);position:relative;overflow:hidden}
.page-header::before,.page-header::after{content:'';position:absolute;border-radius:50%}
.page-header::before{top:-60px;right:-60px;width:200px;height:200px;background:radial-gradient(circle,#eef2ff 0%,transparent 70%)}
.page-header::after{bottom:-40px;left:-40px;width:160px;height:160px;background:radial-gradient(circle,#f0fdf4 0%,transparent 70%)}
.page-title{font-size:clamp(1.3rem,3.5vw,1.75rem);font-weight:800;color:var(--gray-900);margin-bottom:6px;letter-spacing:-.5px;position:relative;z-index:1}
.page-subtitle{font-size:1rem;font-weight:600;color:var(--gray-900);margin-bottom:12px;position:relative;z-index:1}
.badge-namabpr{display:inline-block;background:var(--grad);color:white;font-size:1rem;font-weight:700;padding:6px 20px;border-radius:100px;letter-spacing:.3px;position:relative;z-index:1;box-shadow:0 4px 12px rgba(79,70,229,.3)}
.approval-status-badge{display:flex;align-items:center;gap:10px;padding:10px 16px;border-radius:var(--r);font-size:13px;font-weight:600;transition:var(--tr);border:1.5px solid transparent}
.approval-status-badge i{font-size:18px;flex-shrink:0}
.approval-status-label{font-weight:700;font-size:12px}
.approval-status-sub{font-size:11px;opacity:.8}
.badge-status-secondary{background:var(--gray-100);color:var(--gray-500);border-color:var(--gray-200)}
.badge-status-success{background:var(--success-bg);color:#065f46;border-color:#6ee7b7}
.action-card{border-radius:var(--r)!important;border:1.5px solid var(--gray-200)!important;transition:var(--tr);overflow:hidden}
.action-card:hover{border-color:var(--brand-light)!important;box-shadow:0 8px 24px rgba(79,70,229,.12)!important;transform:translateY(-3px)}
.card-header{border-radius:var(--r-lg) var(--r-lg) 0 0!important}
.ketentuan-card-skel{background:white;border-radius:var(--r-lg);overflow:hidden;margin-bottom:14px;box-shadow:var(--sh-sm)}
.skel-card-header{height:48px;background:linear-gradient(90deg,#f1f5f9 25%,#e2e8f0 50%,#f1f5f9 75%);background-size:200% 100%;animation:skAnim 1.4s infinite}
.skel-card-body{padding:16px}
.ketentuan-cards-grid{display:grid;grid-template-columns:1fr;gap:16px;padding:4px 2px}
.ketentuan-card{background:white;border-radius:var(--r-lg);box-shadow:var(--sh-sm);border:1.5px solid var(--gray-100);overflow:hidden;transition:var(--tr);animation:rowIn .3s ease both;display:flex;flex-direction:column;will-change:transform}
.ketentuan-card:hover{box-shadow:var(--sh-lg);border-color:var(--brand-light);transform:translateY(-3px)}
.kcard-header{display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:var(--grad);gap:10px}
.kcard-nomor{display:flex;align-items:center;gap:8px;min-width:0;flex:1}
.kcard-index{background:rgba(255,255,255,.2);color:white;font-size:11px;font-weight:800;width:24px;height:24px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0}
.kcard-nomor-text{color:rgba(255,255,255,.9);font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;letter-spacing:.3px}
.kcard-actions{display:flex;align-items:center;gap:6px;flex-shrink:0}
.kcard-acc-icon{font-size:20px;cursor:pointer;transition:var(--tr);color:white;opacity:.7}
.kcard-acc-icon.acc-ok{opacity:1;color:#6ee7b7}
.kcard-acc-icon.acc-no{opacity:.5;color:white}
.kcard-acc-icon:hover{transform:scale(1.25);opacity:1}
.kcard-btn{width:28px;height:28px;padding:0!important;display:inline-flex;align-items:center;justify-content:center;border-radius:var(--r-sm)!important;font-size:12px!important}
.kcard-body{padding:14px 16px;display:grid;grid-template-columns:1fr 1fr;gap:16px;flex:1}
.kcard-col{display:flex;flex-direction:column;gap:12px}
.kcard-col+.kcard-col{padding-left:16px;border-left:1.5px solid var(--gray-100)}
.kcard-detail-section{display:flex;flex-direction:column;gap:4px}
.kcard-detail-label{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--gray-400)}
.kcard-detail-val{font-size:13px;color:var(--gray-700);line-height:1.6;white-space:pre-wrap;word-break:break-word}
.status-pill{display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:700;padding:4px 12px;border-radius:100px;border:1.5px solid transparent;white-space:nowrap}
.status-01{background:#d1fae5;color:#065f46;border-color:#6ee7b7}
.status-02{background:#dbeafe;color:#1e40af;border-color:#93c5fd}
.status-03{background:#fee2e2;color:#991b1b;border-color:#fca5a5}
.status-xx{background:var(--gray-100);color:var(--gray-500);border-color:var(--gray-200)}
.kcard-acc-badge{display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:700;padding:3px 10px;border-radius:100px;border:1.5px solid transparent}
.acc-badge-ok{background:var(--success-bg);color:#065f46;border-color:#a7f3d0}
.acc-badge-no{background:var(--warning-bg);color:#92400e;border-color:#fcd34d}
.kcard-footer{padding:10px 16px;border-top:1px solid var(--gray-100);background:var(--gray-50);display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.btn{transition:var(--tr);font-weight:600}
.btn:hover{transform:translateY(-2px);box-shadow:var(--sh)}
.btn:active{transform:scale(.96)!important}
.btn-sm{font-size:12px}
#pk-content-wrap{transition:opacity .25s ease}
#pk-content-wrap.pk-blurred{opacity:.45;pointer-events:none;filter:grayscale(.15)}
.btn-kembali{display:inline-flex;align-items:center;gap:6px;background:white;border:1.5px solid var(--gray-200);border-radius:var(--r-sm);padding:8px 16px;font-size:.875rem;font-weight:600;color:var(--gray-500);text-decoration:none;transition:var(--tr);margin:14px 0 0 16px}
.btn-kembali:hover{background:var(--brand-bg);border-color:var(--brand-light);color:var(--brand);text-decoration:none}
.btn-primaryy{background:white;color:var(--blue);border:none;border-radius:var(--r-sm);width:32px;height:32px;padding:0;display:inline-flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;box-shadow:0 2px 8px rgba(0,0,0,.15);transition:var(--tr)}
.btn-primaryy:hover{background:var(--brand-bg);transform:rotate(90deg) scale(1.1)}
.notif-badge{position:absolute;top:-7px;right:-7px;background:var(--danger);color:#fff;border-radius:50%;padding:2px 5px;font-size:10px;font-weight:700;min-width:17px;text-align:center;line-height:1.3;box-shadow:0 2px 6px rgba(239,68,68,.5);animation:pulse 2s infinite}
@keyframes pulse{0%,100%{box-shadow:0 0 0 0 rgba(239,68,68,.4)}50%{box-shadow:0 0 0 5px rgba(239,68,68,0)}}
.empty-state-box{text-align:center;padding:60px 20px;background:var(--gray-50);border-radius:var(--r-lg);border:2px dashed var(--gray-200);margin:10px 0}
.btn-komentar-global-btn{position:relative;width:36px;height:36px;border-radius:50%;background:white;border:none;color:var(--blue);font-size:17px;display:inline-flex;align-items:center;justify-content:center;box-shadow:0 2px 10px rgba(0,0,0,.18);cursor:pointer;transition:all .25s cubic-bezier(.4,0,.2,1);outline:none}
.btn-komentar-global-btn:hover{background:var(--brand-bg);color:var(--brand);transform:scale(1.13);box-shadow:0 4px 18px rgba(79,70,229,.25)}
.btn-komentar-global-btn.has-unread{animation:btn-shake 1.8s ease infinite;background:var(--brand-bg);color:var(--brand)}
@keyframes btn-shake{0%,100%{transform:rotate(0deg) scale(1)}10%{transform:rotate(-14deg) scale(1.08)}20%{transform:rotate(12deg) scale(1.08)}30%{transform:rotate(-10deg) scale(1.05)}40%{transform:rotate(8deg) scale(1.05)}50%{transform:rotate(0deg) scale(1)}}
.btn-komentar-ripple{position:absolute;inset:-4px;border-radius:50%;border:2px solid var(--brand-light);opacity:0;pointer-events:none}
.btn-komentar-global-btn.has-unread .btn-komentar-ripple{animation:ripple-out 1.8s ease infinite}
@keyframes ripple-out{0%{transform:scale(.9);opacity:.7}100%{transform:scale(1.6);opacity:0}}
.komentar-modal-content{border-radius:18px!important;overflow:hidden;border:none;box-shadow:0 20px 60px rgba(0,0,0,.18)!important}
.komentar-modal-header{background:var(--grad);padding:18px 22px!important;border:none!important;display:flex;align-items:center;justify-content:space-between}
.komentar-modal-title-wrap{display:flex;align-items:center;gap:12px}
.komentar-modal-icon-wrap{width:40px;height:40px;border-radius:12px;background:rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center;font-size:18px;color:white;flex-shrink:0}
.komentar-modal-title{font-size:15px;font-weight:800;color:white;line-height:1.2}
.komentar-close-btn{width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,.15);border:none;color:white;font-size:14px;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all .2s;flex-shrink:0}
.komentar-close-btn:hover{background:rgba(255,255,255,.3);transform:rotate(90deg)}
.komentar-modal-body{padding:20px 22px!important;background:var(--gray-50)}
.komentar-modal-footer{background:white;border-top:1px solid var(--gray-100)!important;padding:12px 22px!important}
.komentar-list-label{font-size:12px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.8px;margin-bottom:10px}
.komentar-list-wrapper{background:white;border:1.5px solid var(--gray-200);border-radius:12px;max-height:320px;overflow-y:auto;padding:12px;margin-bottom:16px;scroll-behavior:smooth}
.komentar-list-wrapper::-webkit-scrollbar{width:5px}
.komentar-list-wrapper::-webkit-scrollbar-thumb{background:var(--gray-200);border-radius:10px}
.komentar-loading,.komentar-empty{text-align:center;color:var(--gray-400);padding:30px 0;font-size:13px}
.km-item{background:var(--gray-50);border-left:3px solid var(--brand-light);border-radius:10px;padding:10px 14px;margin-bottom:8px;box-shadow:var(--sh-sm);animation:kmIn .25s ease both}
.km-item:last-child{margin-bottom:0}
.km-item.km-own{border-left-color:var(--success);background:#f0fdf4}
.btn-sync-periode{width:36px;height:36px;border-radius:50%;background:white;border:none;color:#0d9488;font-size:15px;display:inline-flex;align-items:center;justify-content:center;box-shadow:0 2px 10px rgba(0,0,0,.18);cursor:pointer;transition:all .25s cubic-bezier(.4,0,.2,1);outline:none;position:relative;}
.btn-sync-periode:disabled{color:#94a3b8;background:#f1f5f9;cursor:not-allowed;box-shadow:0 1px 4px rgba(0,0,0,.08);}
.btn-sync-periode:not(:disabled):hover{background:#f0fdfa;color:#0f766e;transform:scale(1.13);box-shadow:0 4px 18px rgba(13,148,136,.3);}
.btn-sync-periode.syncing .fas{animation:spin360 1s linear infinite;}
@keyframes spin360{to{transform:rotate(360deg)}}
.sync-tooltip{position:absolute;bottom:calc(100% + 8px);left:50%;transform:translateX(-50%);background:#1e293b;color:white;font-size:11px;font-weight:600;padding:5px 10px;border-radius:8px;white-space:nowrap;pointer-events:none;opacity:0;transition:opacity .2s;z-index:100;}
.sync-tooltip::after{content:'';position:absolute;top:100%;left:50%;transform:translateX(-50%);border:5px solid transparent;border-top-color:#1e293b;}
.btn-sync-periode:hover+.sync-tooltip,.btn-sync-periode:focus+.sync-tooltip{opacity:1;}
.sync-info-box{display:flex;align-items:center;justify-content:center;gap:16px;background:#f0fdfa;border:1.5px solid #99f6e4;border-radius:12px;padding:14px 18px;}
.sync-info-periode{display:flex;flex-direction:column;align-items:center;gap:3px;flex:1;text-align:center;}
.sync-info-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:#5eead4;}
.sync-info-val{font-size:14px;font-weight:800;color:#0f766e;}
.sync-arrow{font-size:20px;color:#0d9488;}
.sync-count-badge{display:inline-flex;align-items:center;gap:8px;background:#d1fae5;color:#065f46;font-size:13px;font-weight:700;padding:6px 14px;border-radius:100px;border:1.5px solid #a7f3d0;margin-bottom:10px;}
.sync-preview-list{max-height:220px;overflow-y:auto;border:1.5px solid #e2e8f0;border-radius:10px;background:white;}
.sync-preview-list::-webkit-scrollbar{width:4px;}
.sync-preview-list::-webkit-scrollbar-thumb{background:#e2e8f0;border-radius:10px;}
.sync-prev-item{display:flex;align-items:flex-start;gap:10px;padding:9px 14px;border-bottom:1px solid #f1f5f9;font-size:12.5px;}
.sync-prev-item:last-child{border-bottom:none;}
.sync-prev-item-idx{width:20px;height:20px;border-radius:50%;background:#f0fdfa;color:#0d9488;font-size:10px;font-weight:800;flex-shrink:0;display:flex;align-items:center;justify-content:center;margin-top:2px;}
.sync-prev-item-body{flex:1;min-width:0;}
.sync-prev-item-title{font-weight:700;color:#1e293b;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.sync-prev-item-preview{font-size:11px;color:#64748b;margin-top:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.sync-prev-item.will-skip{opacity:.4;}
.sync-skip-badge{font-size:10px;font-weight:700;background:#fef3c7;color:#92400e;padding:2px 7px;border-radius:6px;flex-shrink:0;margin-top:2px;}
@keyframes kmIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}
.km-meta{display:flex;justify-content:space-between;margin-bottom:5px;flex-wrap:wrap;gap:4px}
.km-author{font-weight:700;font-size:12px;color:var(--gray-800);display:flex;align-items:center;gap:5px}
.km-you{font-size:9px;background:var(--success);color:white;padding:1px 6px;border-radius:100px;font-weight:700}
.km-time{font-size:11px;color:var(--gray-400)}
.km-text{font-size:13px;white-space:pre-wrap;margin:0;color:var(--gray-700);line-height:1.5}
.komentar-input-wrap{background:white;border:1.5px solid var(--gray-200);border-radius:12px;padding:14px;transition:border-color .2s}
.komentar-input-wrap:focus-within{border-color:var(--brand);box-shadow:0 0 0 3px rgba(79,70,229,.1)}
.komentar-input-label{font-size:12px;font-weight:700;color:var(--gray-500);margin-bottom:8px;text-transform:uppercase;letter-spacing:.6px}
.komentar-textarea{width:100%;border:none;outline:none;resize:none;font-size:13.5px;color:var(--gray-700);font-family:'Plus Jakarta Sans',sans-serif;line-height:1.6;background:transparent}
.komentar-input-footer{display:flex;justify-content:space-between;align-items:center;margin-top:8px;padding-top:8px;border-top:1px solid var(--gray-100)}
.komentar-hint{font-size:11px;color:var(--gray-400)}
.komentar-char{font-size:11px;color:var(--gray-400);font-weight:600;transition:color .2s}
.komentar-char.danger{color:var(--danger)}
.modal-header-sdm{background:var(--grad);color:white}
.modal-content{border-radius:var(--r-lg);overflow:hidden;border:none;box-shadow:var(--sh-lg)}
.modal-body{padding:20px 24px!important}
.modal-footer{padding:12px 24px!important}
.modal-body .form-group{margin-bottom:16px!important}
.modal-body .form-control{width:100%!important;box-sizing:border-box!important;border-radius:8px!important;border:1.5px solid var(--gray-200)!important;padding:8px 12px!important;font-size:13.5px!important;transition:var(--tr)!important}
.modal-body .form-control:focus{border-color:var(--brand)!important;box-shadow:0 0 0 3px rgba(79,70,229,.15)!important;outline:none!important}
.modal-body .row{margin-left:-8px!important;margin-right:-8px!important}
.modal-body .row>[class*="col-"]{padding-left:8px!important;padding-right:8px!important}
.modal-dialog{margin:20px auto!important;max-width:720px!important}
.t-item{background:white;border-radius:var(--r);padding:14px 18px;margin-bottom:10px;box-shadow:var(--sh-lg);display:flex;align-items:center;gap:12px;font-size:13.5px;animation:tIn .3s ease;pointer-events:all;border-left:4px solid}
.t-item.t-success{border-color:var(--success);color:#065f46}
.t-item.t-error{border-color:var(--danger);color:#991b1b}
.t-item.t-info{border-color:var(--info);color:#164e63}
@keyframes tIn{from{transform:translateX(110%);opacity:0}to{transform:translateX(0);opacity:1}}
.skel{height:52px;border-radius:var(--r);background:linear-gradient(90deg,#f1f5f9 25%,#e2e8f0 50%,#f1f5f9 75%);background-size:200% 100%;animation:skAnim 1.4s infinite}
@keyframes skAnim{0%{background-position:200% 0}100%{background-position:-200% 0}}
@keyframes rowIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
.char-counter-wrap{display:flex;justify-content:flex-end;margin-top:4px}
.char-counter{font-size:11px;font-weight:600;color:var(--gray-400);transition:color .2s}
.char-counter.warn{color:var(--warning)}
.char-counter.danger{color:var(--danger)}
@media(max-width:768px){.modal-dialog{margin:10px!important;max-width:calc(100% - 20px)!important}.modal-body{padding:14px 16px!important}.kcard-body{grid-template-columns:1fr}.kcard-col+.kcard-col{padding-left:0;border-left:none;border-top:1.5px solid var(--gray-100);padding-top:12px}}
</style>