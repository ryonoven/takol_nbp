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
            <h1 class="page-title"><?= esc($judul)?></h1>
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
                <i class="fas fa-file-alt"></i> Ruang Lingkup dan Waktu Pelaksanaan Audit
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
                        <small>Tidak terdapat Ruang Lingkup dan Waktu Pelaksanaan Audit pada periode ini.</small>
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
                    <div id="cards-lingkupaudit" class="ketentuan-cards-grid"></div>
                    <div id="empty-state" class="empty-state-box" style="display:none;">
                        <i class="fas fa-file-slash fa-3x text-muted mb-3 d-block"></i>
                        <h6 class="text-muted mb-1">Belum ada data Ruang Lingkup dan Waktu Pelaksanaan Audit</h6>
                        <p class="text-muted small mb-3">Data Ruang Lingkup dan Waktu Pelaksanaan Audit untuk periode ini belum diinput.</p>
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
                <a href="<?= base_url('Lingkupaudit/exporttxtlingkupaudit') ?>" class="btn btn-warning btn-sm">
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
                <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Tambah Data Ruang Lingkup Audit</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Tanggal Mulai Pelaksanaan Audit <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="t-tanggalmulai" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Tanggal Akhir Pelaksanaan Audit <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="t-tanggalberakhir" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="font-weight-bold">Ruang Lingkup Audit <span class="text-danger">*</span></label>
                            <textarea class="form-control char-input" id="t-ruanglingkup" rows="8" placeholder="Masukkan ruang lingkup audit..." maxlength="4000" data-counter="t-rl-count" data-max="4000"></textarea>
                            <div class="char-counter-wrap"><span id="t-rl-count" class="char-counter">0 / 4000</span></div>
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
                <h5 class="modal-title"><i class="fas fa-edit"></i> Ubah Data Ruang Lingkup Audit</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="u-id">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Tanggal Mulai Pelaksanaan Audit <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="u-tanggalmulai" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Tanggal Akhir Pelaksanaan Audit <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="u-tanggalberakhir" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="font-weight-bold">Ruang Lingkup Audit <span class="text-danger">*</span></label>
                            <textarea class="form-control char-input" id="u-ruanglingkup" rows="8" maxlength="4000" data-counter="u-rl-count" data-max="4000"></textarea>
                            <div class="char-counter-wrap"><span id="u-rl-count" class="char-counter">0 / 4000</span></div>
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
                    <tr><th width="45%">Ruang Lingkup Audit</th><td id="h-ruanglingkup"></td></tr>
                    <tr><th>Tanggal Mulai</th><td id="h-tanggalmulai"></td></tr>
                    <tr><th>Tanggal Akhir</th><td id="h-tanggalberakhir"></td></tr>
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
                    <tr><th width="45%">Ruang Lingkup Audit</th><td id="ak-ruanglingkup"></td></tr>
                    <tr><th>Tanggal Mulai</th><td id="ak-tanggalmulai"></td></tr>
                    <tr><th>Tanggal Akhir</th><td id="ak-tanggalberakhir"></td></tr>
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
                    <div><div class="komentar-modal-title">Komentar — Ruang Lingkup dan Waktu Pelaksanaan Audit</div></div>
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
                    <strong>tidak akan ditimpa</strong>. Hanya data baru (berdasarkan
                    Tanggal Mulai + Tanggal Akhir) yang akan ditambahkan.
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
                        Format wajib: <strong>Tanggal Mulai Pelaksanaan Audit | Tanggal Akhir Pelaksanaan Audit | Ruang Lingkup Audit</strong>.
                        <a href="<?= base_url('assets/template/Template Impor Ruang Lingkup dan Waktu Pelaksanaan Audit.xlsx') ?>">Unduh template</a>.
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
                            <thead><tr><th>#</th><th>Mulai</th><th>Akhir</th><th>Ruang Lingkup</th><th></th></tr></thead>
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
:root{--brand:#4f46e5;--brand-light:#818cf8;--brand-dark:#3730a3;--brand-bg:#eef2ff;--success:#10b981;--success-bg:#d1fae5;--danger:#ef4444;--danger-bg:#fee2e2;--warning:#f59e0b;--warning-bg:#fef3c7;--info:#06b6d4;--info-bg:#cffafe;--gray-50:#f8fafc;--gray-100:#f1f5f9;--gray-200:#e2e8f0;--gray-400:#94a3b8;--gray-500:#64748b;--gray-700:#334155;--gray-800:#1e293b;--gray-900:#0f172a;--blue:#1f2aa8;--r-sm:8px;--r:12px;--r-lg:16px;--sh-sm:0 1px 3px rgba(0,0,0,.08),0 1px 2px rgba(0,0,0,.06);--sh:0 4px 6px -1px rgba(0,0,0,.1),0 2px 4px -1px rgba(0,0,0,.06);--sh-lg:0 10px 25px -3px rgba(0,0,0,.1),0 4px 6px -2px rgba(0,0,0,.05);--tr:all .2s cubic-bezier(.4,0,.2,1);--grad:linear-gradient(135deg,#141863 0%,#1f2aa8 100%)}
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
.badge-status-secondary{background:var(--gray-100);color:var(--gray-500);border-color:var(--gray-200);align-items:center}
.badge-status-success{background:var(--success-bg);color:#065f46;border-color:#6ee7b7}
.action-card{border-radius:var(--r)!important;border:1.5px solid var(--gray-200)!important;transition:var(--tr);overflow:hidden}
.action-card:hover{border-color:var(--brand-light)!important;box-shadow:0 8px 24px rgba(79,70,229,.12)!important;transform:translateY(-3px)}
.card-header{border-radius:var(--r-lg) var(--r-lg) 0 0!important}
.ketentuan-card-skel{background:white;border-radius:var(--r-lg);overflow:hidden;margin-bottom:14px;box-shadow:var(--sh-sm)}
.skel-card-header{height:48px;background:linear-gradient(90deg,#f1f5f9 25%,#e2e8f0 50%,#f1f5f9 75%);background-size:200% 100%;animation:skAnim 1.4s infinite}
.skel-card-body{padding:16px}
.ketentuan-cards-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(360px,1fr));gap:16px;padding:4px 2px}
.ketentuan-card{background:white;border-radius:var(--r-lg);box-shadow:var(--sh-sm);border:1.5px solid var(--gray-100);overflow:hidden;transition:var(--tr);animation:rowIn .3s ease both;display:flex;flex-direction:column}
.ketentuan-card:hover{box-shadow:var(--sh-lg);border-color:var(--brand-light);transform:translateY(-3px)}
.kcard-header{display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:var(--grad);gap:10px}
.kcard-nomor{display:flex;align-items:center;gap:8px;min-width:0;flex:1}
.kcard-index{background:rgba(255,255,255,.2);color:white;font-size:11px;font-weight:800;width:24px;height:24px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0}
.kcard-nomor-text{color:rgba(255,255,255,.9);font-size:14px;font-weight:800;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;letter-spacing:.3px}
.kcard-actions{display:flex;align-items:center;gap:6px;flex-shrink:0}
.kcard-acc-icon{font-size:20px;cursor:pointer;transition:var(--tr);color:white;opacity:.7}
.kcard-acc-icon.acc-ok{opacity:1;color:#6ee7b7}
.kcard-acc-icon.acc-no{opacity:.5;color:white}
.kcard-acc-icon:hover{transform:scale(1.25);opacity:1}
.kcard-btn{width:28px;height:28px;padding:0!important;display:inline-flex;align-items:center;justify-content:center;border-radius:var(--r-sm)!important;font-size:12px!important}
.kcard-body{padding:14px 16px;display:flex;flex-direction:column;gap:12px;flex:1}
.kcard-judul{font-size:14px;font-weight:400;color:var(--gray-900);line-height:1.5}
.kcard-acc-badge{display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:700;padding:3px 10px;border-radius:100px;border:1.5px solid transparent}
.acc-badge-ok{background:var(--success-bg);color:#065f46;border-color:#a7f3d0}
.acc-badge-no{background:var(--warning-bg);color:#92400e;border-color:#fcd34d}
.kcard-daterange{display:flex;align-items:center;gap:8px;background:var(--gray-50);border:1.5px solid var(--gray-100);border-radius:var(--r-sm);padding:8px 12px;flex-wrap:wrap}
.kcard-date-item{display:flex;flex-direction:column;gap:2px}
.kcard-date-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--gray-400)}
.kcard-date-val{font-size:13px;font-weight:700;color:var(--gray-800)}
.kcard-date-arrow{color:var(--gray-400);font-size:14px;margin-top:10px}
@media(max-width:576px){.ketentuan-cards-grid{grid-template-columns:1fr}}
.btn{transition:var(--tr);font-weight:600}
.btn:hover{transform:translateY(-2px);box-shadow:var(--sh)}
.btn:active{transform:scale(.96)!important}
.btn-sm{font-size:12px}
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
.komentar-list-wrapper::-webkit-scrollbar-track{background:transparent}
.komentar-list-wrapper::-webkit-scrollbar-thumb{background:var(--gray-200);border-radius:10px}
.komentar-loading,.komentar-empty{text-align:center;color:var(--gray-400);padding:30px 0;font-size:13px}
.km-item{background:var(--gray-50);border-left:3px solid var(--brand-light);border-radius:10px;padding:10px 14px;margin-bottom:8px;box-shadow:var(--sh-sm);animation:kmIn .25s ease both}
.km-item:last-child{margin-bottom:0}
.km-item.km-own{border-left-color:var(--success);background:#f0fdf4}
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
#pk-content-wrap{transition:opacity .25s ease}
#pk-content-wrap.pk-blurred{opacity:.45;pointer-events:none;filter:grayscale(.15)}
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
 
/* Tooltip */
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
 
/* ── Sync modal ── */
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
    max-height: 220px; overflow-y: auto;
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
.sync-prev-item-body { flex: 1; min-width: 0; }
.sync-prev-item-dates {
    font-weight: 700; color: #1e293b; font-size: 13px;
}
.sync-prev-item-preview {
    font-size: 11px; color: #64748b; margin-top: 3px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.sync-prev-item.will-skip { opacity: .4; }
.sync-skip-badge {
    font-size: 10px; font-weight: 700;
    background: #fef3c7; color: #92400e;
    padding: 2px 7px; border-radius: 6px;
    flex-shrink: 0; margin-top: 2px;
}
@keyframes tIn{from{transform:translateX(110%);opacity:0}to{transform:translateX(0);opacity:1}}
.skel{height:52px;border-radius:var(--r);background:linear-gradient(90deg,#f1f5f9 25%,#e2e8f0 50%,#f1f5f9 75%);background-size:200% 100%;animation:skAnim 1.4s infinite}
@keyframes skAnim{0%{background-position:200% 0}100%{background-position:-200% 0}}
@keyframes rowIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
.char-counter-wrap{display:flex;justify-content:flex-end;margin-top:4px}
.char-counter{font-size:11px;font-weight:600;color:var(--gray-400);transition:color .2s}
.char-counter.warn{color:var(--warning)}
.char-counter.danger{color:var(--danger)}
@media(max-width:768px){.modal-dialog{margin:10px!important;max-width:calc(100% - 20px)!important}.modal-body{padding:14px 16px!important}.kcard-daterange{flex-direction:column;gap:4px}.kcard-date-arrow{display:none}}
</style>

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
(function(){
    var BASE='<?= $_base ?>',CSRF='<?= $_csrf ?>',HASH='<?= $_hash ?>',
        UID=<?= $_userId ?>,FNAME='<?= $_fullname ?>',PID=<?= $_periodeId ?>,
        KBPR='<?= $_kodebpr ?>',CAN_EDIT=<?= $_canEdit ?>,CAN_KOM=<?= $_canKom ?>;

    var POLL_INTERVAL = 30000;
    var pollTimer = null;
    var _pendingPost = false;

    function esc(s){return s==null?'':String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
    function fmtTanggal(s){if(!s)return'-';var p=s.replace(/-/g,'/').split('/');if(p.length!==3)return s;return p[2]+'/'+p[1]+'/'+p[0];}
    function timeAgo(s){if(!s)return'-';var d=new Date(s),diff=Math.floor((Date.now()-d.getTime())/60000);if(diff<1)return'Baru saja';if(diff<60)return diff+' menit lalu';if(diff<1440)return Math.floor(diff/60)+' jam lalu';return('0'+d.getDate()).slice(-2)+'/'+('0'+(d.getMonth()+1)).slice(-2)+'/'+d.getFullYear()+' '+('0'+d.getHours()).slice(-2)+':'+('0'+d.getMinutes()).slice(-2);}
    function escNl(s){return esc(s).replace(/\n/g,'<br>');}

    function toast(msg,type){
        type=type||'success';
        var icons={success:'check-circle',error:'exclamation-circle',info:'info-circle'};
        var el=document.createElement('div');
        el.className='t-item t-'+type;
        el.innerHTML='<i class="fas fa-'+icons[type]+' fa-lg"></i><div>'+msg+'</div>';
        document.getElementById('toast-wrap').appendChild(el);
        setTimeout(function(){el.style.transition='opacity .4s';el.style.opacity='0';setTimeout(function(){el.remove();},400);},3500);
    }

    function updateCsrf(res){
        if(res&&res.csrf_hash)HASH=res.csrf_hash;
    }

    function doPost(url,data,cb,errCb){
        if(_pendingPost)return;
        _pendingPost=true;
        var body=new URLSearchParams();
        body.append(CSRF,HASH);
        Object.keys(data).forEach(function(k){body.append(k,data[k]);});
        fetch(BASE+'/'+url,{
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
            body:body.toString()
        }).then(function(r){
            _pendingPost=false;
            if(r.status===403){toast('Sesi keamanan kedaluwarsa. Halaman akan di-refresh...','error');setTimeout(function(){location.reload();},2000);return null;}
            if(r.status===401){toast('Sesi berakhir.','error');setTimeout(function(){location.href=BASE+'/login';},1500);return null;}
            return r.json();
        }).then(function(res){
            if(!res)return;
            updateCsrf(res);
            cb(res);
        }).catch(function(){
            _pendingPost=false;
            var m='Terjadi kesalahan server.';
            errCb?errCb(m):toast(m,'error');
        });
    }

    function doGet(url,cb,errCb){
        fetch(BASE+'/'+url,{headers:{'X-Requested-With':'XMLHttpRequest'}})
        .then(function(r){return r.json();})
        .then(cb)
        .catch(function(){errCb&&errCb();});
    }

    function btnLoading(btn){
        var ori=btn.innerHTML;btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>';btn.disabled=true;
        return function(){btn.innerHTML=ori;btn.disabled=false;};
    }

    function debounce(fn,ms){var t;return function(){var a=arguments,c=this;clearTimeout(t);t=setTimeout(function(){fn.apply(c,a);},ms);};}
    function throttle(fn,ms){var busy=false;return function(){if(busy)return;busy=true;fn.apply(this,arguments);setTimeout(function(){busy=false;},ms);};}

    function updateNotifBadge(count){
        var btn=document.getElementById('btn-komentar-global'),badge=document.getElementById('notif-global');
        if(count>0){badge.textContent=count;badge.style.display='';btn.classList.add('has-unread');}
        else{badge.style.display='none';btn.classList.remove('has-unread');}
    }

    function initCharCounter(el){
        var max=parseInt(el.dataset.max)||0,cid=el.dataset.counter;
        if(!cid||!max)return;
        var cnt=document.getElementById(cid);
        function update(){var len=el.value.length,pct=len/max;cnt.textContent=len+' / '+max;cnt.classList.remove('warn','danger');if(pct>=1)cnt.classList.add('danger');else if(pct>=0.85)cnt.classList.add('warn');}
        el.addEventListener('input',update);update();
    }

    function renderRows(rows){
        var container=document.getElementById('cards-lingkupaudit');
        var empty=document.getElementById('empty-state');
        if(!rows||!rows.length){container.innerHTML='';empty.style.display='';return;}
        empty.style.display='none';
        var frag=document.createDocumentFragment();
        rows.forEach(function(r,i){
            var dOk=parseInt(r.accdir2)===1;
            var accCls=dOk?'acc-badge-ok':'acc-badge-no';
            var accIcon=dOk?'fa-check-circle':'fa-clock';
            var accTxt=dOk?'Disetujui':'Menunggu Persetujuan';

            var card=document.createElement('div');
            card.className='ketentuan-card';
            card.id='kcard-'+r.id;

            var actions='';
            if(CAN_KOM){
                actions+='<i class="fas '+accIcon+' btn-toggle-kom kcard-acc-icon '+(dOk?'acc-ok':'acc-no')+'"'
                    +' data-id="'+esc(r.id)+'" data-ruanglingkup="'+esc(r.ruanglingkup)+'"'
                    +' data-tanggalmulai="'+esc(r.tanggalmulai)+'" data-tanggalberakhir="'+esc(r.tanggalberakhir)+'"'
                    +' data-action="'+(dOk?'unapprove':'approve')+'"'
                    +' title="'+(dOk?'Batalkan approval':'Setujui')+'"></i>';
            }
            if(CAN_EDIT){
                actions+='<button class="btn btn-sm btn-warning btn-ubah kcard-btn" title="Ubah"'
                    +' data-id="'+esc(r.id)+'" data-ruanglingkup="'+esc(r.ruanglingkup)+'"'
                    +' data-tanggalmulai="'+esc(r.tanggalmulai)+'" data-tanggalberakhir="'+esc(r.tanggalberakhir)+'">'
                    +'<i class="fas fa-pencil-alt"></i></button>'
                    +'<button class="btn btn-sm btn-danger btn-hapus kcard-btn" title="Hapus"'
                    +' data-id="'+esc(r.id)+'" data-ruanglingkup="'+esc(r.ruanglingkup)+'"'
                    +' data-tanggalmulai="'+esc(r.tanggalmulai)+'" data-tanggalberakhir="'+esc(r.tanggalberakhir)+'">'
                    +'<i class="fas fa-trash-alt"></i></button>';
            }

            var mulai=r.tanggalmulai?fmtTanggal(r.tanggalmulai):'<em style="color:var(--gray-400);font-weight:400;">Belum diisi</em>';
            var akhir=r.tanggalberakhir?fmtTanggal(r.tanggalberakhir):'<em style="color:var(--gray-400);font-weight:400;">Belum diisi</em>';

            card.innerHTML='<div class="kcard-header">'
                +'<div class="kcard-nomor"><span class="kcard-index">'+(i+1)+'</span>'
                +'<span class="kcard-nomor-text"><i class="fas fa-search-location" style="margin-right:4px;opacity:.7;"></i>Ruang Lingkup #'+(i+1)+'</span></div>'
                +'<div class="kcard-actions">'+actions+'</div></div>'
                +'<div class="kcard-body">'
                +'<div class="kcard-daterange">'
                +'<div class="kcard-date-item"><span class="kcard-date-label"><i class="fas fa-play-circle"></i> Tanggal Mulai</span><span class="kcard-date-val">'+mulai+'</span></div>'
                +'<i class="fas fa-arrow-right kcard-date-arrow"></i>'
                +'<div class="kcard-date-item"><span class="kcard-date-label"><i class="fas fa-stop-circle"></i> Tanggal Akhir</span><span class="kcard-date-val">'+akhir+'</span></div>'
                +'</div>'
                +'<div class="kcard-judul">'+escNl(r.ruanglingkup||'-')+'</div>'
                +'<div><span class="kcard-acc-badge '+accCls+'"><i class="fas '+accIcon+'"></i> '+accTxt+'</span></div>'
                +'</div>';
            frag.appendChild(card);
        });
        container.innerHTML='';
        container.appendChild(frag);
    }

    function renderNodataState(isNull){
        var banner=document.getElementById('nodata-banner');
        var wrap=document.getElementById('pk-content-wrap');
        var btnTambah=document.getElementById('btn-show-tambah');
        var btnSync=document.getElementById('btn-sync-periode');
        var btnNodata=document.getElementById('btn-nodata');
        if(isNull){
            banner.style.display='';
            wrap.classList.add('pk-blurred');
            if(btnTambah)btnTambah.style.display='none';
            if(btnSync)btnSync.parentElement.style.display='none';
            if(btnNodata)btnNodata.style.display='none';
        }else{
            banner.style.display='none';
            wrap.classList.remove('pk-blurred');
            if(btnTambah)btnTambah.style.display='';
            if(btnSync)btnSync.parentElement.style.display='';
            if(btnNodata)btnNodata.style.display='';
        }
    }

    function loadData(){
        var skel=document.getElementById('skeleton-container');
        var wrap=document.getElementById('table-wrapper');
        var empty=document.getElementById('empty-state');
        var errState=document.getElementById('error-state');
        skel.style.display='';
        wrap.style.display='none';
        empty.style.display='none';
        errState.style.display='none';

        fetch(BASE+'/Lingkupaudit/getData',{headers:{'X-Requested-With':'XMLHttpRequest'}})
        .then(function(r){
            if(r.status===401){setTimeout(function(){location.href=BASE+'/login';},1500);throw new Error('unauthorized');}
            return r.json();
        })
        .then(function(res){
            skel.style.display='none';
            wrap.style.display='';
            if(res&&res.status==='success'){
                renderNodataState(!!res.is_null_record);
                renderRows(res.data||[]);
                pollUnreadCount();
            }else{
                empty.style.display='';
                toast((res&&res.message)||'Gagal memuat data','error');
            }
        })
        .catch(function(){
            skel.style.display='none';
            wrap.style.display='';
            document.getElementById('error-state-msg').textContent='Gagal memuat data dari server.';
            errState.style.display='';
            toast('Gagal memuat data dari server.','error');
        });
    }

    function refreshApprovalStatus(){
        doGet('Lingkupaudit/getApprovalStatus',function(res){
            if(!res||res.status!=='success')return;
            updateBadge('#badge-approval-dekom','Direktur Utama',res.dekom_approved,res.dekom_approved_at);
        });
    }

    function updateBadge(sel,label,approved,at){
        var bd=document.querySelector(sel);
        if(!bd)return;
        bd.classList.remove('badge-status-secondary','badge-status-success');
        bd.classList.add(approved?'badge-status-success':'badge-status-secondary');
        var icon=approved?'fa-check-circle':'fa-clock';
        var sub=approved?'Disetujui · '+timeAgo(at):'Belum disetujui seluruhnya';
        bd.innerHTML='<i class="fas '+icon+'"></i><div><span class="approval-status-label">'+label+'</span><br><span class="approval-status-sub">'+sub+'</span></div>';
    }

    function pollUnreadCount(){
        doGet('Lingkupaudit/getUnreadCounts',function(res){
            if(res&&res.status==='success')updateNotifBadge(parseInt(res.count)||0);
        });
    }

    function startPolling(){
        clearInterval(pollTimer);
        pollTimer=setInterval(function(){
            pollUnreadCount();
            if(!document.hidden)refreshApprovalStatus();
        },POLL_INTERVAL);
    }

    document.addEventListener('visibilitychange',function(){
        if(document.hidden){clearInterval(pollTimer);}else{startPolling();pollUnreadCount();}
    });

    var _komentarLoaded=false;
    function loadKomentarList(scrollBottom){
        var list=document.getElementById('komentar-list');
        list.innerHTML='<div class="komentar-loading"><i class="fas fa-spinner fa-spin"></i> Memuat...</div>';
        doGet('Lingkupaudit/getKomentar',function(items){
            items=Array.isArray(items)?items:[];
            if(!items.length){list.innerHTML='<div class="komentar-empty"><i class="fas fa-comment-slash fa-2x mb-2 d-block"></i>Belum ada komentar.</div>';return;}
            var frag=document.createDocumentFragment();
            items.forEach(function(k){
                var own=parseInt(k.user_id)===UID;
                var div=document.createElement('div');
                div.className='km-item'+(own?' km-own':'');
                div.innerHTML='<div class="km-meta">'
                    +'<span class="km-author"><i class="fas fa-user-circle"></i> '+esc(k.fullname||'?')+(own?' <span class="km-you">Anda</span>':'')+' </span>'
                    +'<span class="km-time"><i class="fas fa-clock"></i> '+timeAgo(k.created_at)+'</span></div>'
                    +'<p class="km-text">'+esc(k.komentar)+'</p>';
                frag.appendChild(div);
            });
            list.innerHTML='';
            list.appendChild(frag);
            if(scrollBottom){var w=document.getElementById('komentar-list-wrapper');if(w)setTimeout(function(){w.scrollTop=w.scrollHeight;},80);}
        },function(){list.innerHTML='<div class="komentar-empty text-danger"><i class="fas fa-exclamation-circle"></i> Gagal memuat komentar.</div>';});
    }

    function markAllRead(){
        var body=new URLSearchParams();body.append(CSRF,HASH);
        fetch(BASE+'/Lingkupaudit/markKomentarAsRead',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},body:body.toString()})
        .then(function(r){return r.json();}).then(function(res){updateCsrf(res);if(res&&res.status==='success')updateNotifBadge(0);}).catch(function(){});
    }

    var execTambah=debounce(function(){
        var rl=document.getElementById('t-ruanglingkup').value.trim();
        var tm=document.getElementById('t-tanggalmulai').value;
        var tb=document.getElementById('t-tanggalberakhir').value;
        var err=document.getElementById('tambah-err');
        if(!rl||!tm||!tb){err.textContent='Ruang Lingkup, Tanggal Mulai, dan Tanggal Akhir wajib diisi.';err.style.display='';return;}
        err.style.display='none';
        var btn=document.getElementById('btn-simpan-tambah'),restore=btnLoading(btn);
        doPost('Lingkupaudit/tambah',{ruanglingkup:rl,tanggalmulai:tm,tanggalberakhir:tb},function(res){
            restore();
            if(res.status==='success'){jQuery('#modalTambah').modal('hide');toast(res.message||'Data berhasil ditambahkan');loadData();refreshApprovalStatus();}
            else{err.textContent=res.message||'Gagal menyimpan data';err.style.display='';}
        },function(m){restore();err.textContent=m;err.style.display='';});
    },400);

    var execUbah=debounce(function(){
        var id=document.getElementById('u-id').value;
        var rl=document.getElementById('u-ruanglingkup').value.trim();
        var tm=document.getElementById('u-tanggalmulai').value;
        var tb=document.getElementById('u-tanggalberakhir').value;
        var err=document.getElementById('ubah-err');
        if(!rl||!tm||!tb){err.textContent='Ruang Lingkup, Tanggal Mulai, dan Tanggal Akhir wajib diisi.';err.style.display='';return;}
        err.style.display='none';
        var btn=document.getElementById('btn-simpan-ubah'),restore=btnLoading(btn);
        doPost('Lingkupaudit/ubah',{id:id,ruanglingkup:rl,tanggalmulai:tm,tanggalberakhir:tb},function(res){
            restore();
            if(res.status==='success'){jQuery('#modalUbah').modal('hide');toast(res.message||'Data berhasil diubah');loadData();refreshApprovalStatus();}
            else{err.textContent=res.message||'Gagal mengubah data';err.style.display='';}
        },function(m){restore();err.textContent=m;err.style.display='';});
    },400);

    var execHapus=debounce(function(){
        var btn=document.getElementById('btn-konfirmasi-hapus'),restore=btnLoading(btn);
        doPost('Lingkupaudit/hapus',{id:document.getElementById('h-id').value},function(res){
            restore();jQuery('#modalHapus').modal('hide');
            if(res.status==='success'){toast(res.message||'Data berhasil dihapus');loadData();refreshApprovalStatus();}
            else toast(res.message||'Gagal menghapus data','error');
        },function(m){restore();toast(m,'error');});
    },400);

    var execApproveKom=debounce(function(){
        var id=document.getElementById('ak-id').value,action=document.getElementById('ak-action').value;
        var btn=document.getElementById('btn-konfirmasi-approve-kom'),restore=btnLoading(btn);
        var url=action==='approve'?'Lingkupaudit/approveKomRow':'Lingkupaudit/unapproveKomRow';
        doPost(url,{id:id},function(res){
            restore();jQuery('#modalApproveKom').modal('hide');
            if(res.status==='success'){toast(res.message);loadData();refreshApprovalStatus();}
            else toast(res.message||'Gagal menyimpan approval','error');
        },function(m){restore();toast(m,'error');});
    },400);

    var execKomentar=debounce(function(){
        var teks=document.getElementById('k-teks').value.trim();
        if(!teks){toast('Komentar tidak boleh kosong.','error');return;}
        if(teks.length<3){toast('Komentar minimal 3 karakter.','error');return;}
        var btn=document.getElementById('btn-kirim-komentar'),restore=btnLoading(btn);
        doPost('Lingkupaudit/tambahKomentar',{komentar:teks,fullname:FNAME},function(res){
            restore();
            if(res.status==='success'){
                document.getElementById('k-teks').value='';
                document.getElementById('k-char-count').textContent='0 / 1000';
                document.getElementById('k-char-count').classList.remove('danger');
                toast(res.message||'Komentar terkirim');loadKomentarList(true);
            }else toast(res.message||'Gagal mengirim komentar','error');
        },function(m){restore();toast(m,'error');});
    },400);

    var execNodata=debounce(function(){
        if(!confirm('Konfirmasi: tandai bahwa tidak ada data Ruang Lingkup dan Waktu Pelaksanaan Audit pada periode ini?'))return;
        var btn=document.getElementById('btn-nodata'),restore=btnLoading(btn);
        doPost('Lingkupaudit/setNodataAjax',{},function(res){
            restore();
            if(res.status==='success'){toast(res.message);loadData();refreshApprovalStatus();}
            else toast(res.message||'Gagal','error');
        },function(m){restore();toast(m,'error');});
    },400);

    var execBatalNodata=debounce(function(){
        if(!confirm('Batalkan status "Tidak Ada Data"?'))return;
        var btn=document.getElementById('btn-batal-nodata'),restore=btnLoading(btn);
        doPost('Lingkupaudit/batalNodataAjax',{},function(res){
            restore();
            if(res.status==='success'){toast(res.message);loadData();refreshApprovalStatus();}
            else toast(res.message||'Gagal','error');
        },function(m){restore();toast(m,'error');});
    },400);

    function bulkApproval(url,msg){
        if(!confirm(msg))return;
        doPost(url,{},function(res){toast(res.message,res.status==='success'?'success':'error');loadData();refreshApprovalStatus();},function(m){toast(m,'error');});
    }

    document.addEventListener('click',function(e){
        var t=e.target.closest('#btn-show-tambah');
        if(t){
            ['t-ruanglingkup','t-tanggalmulai','t-tanggalberakhir'].forEach(function(id){document.getElementById(id).value='';});
            document.getElementById('tambah-err').style.display='none';
            jQuery('#modalTambah').modal('show');
            setTimeout(function(){document.getElementById('t-ruanglingkup').focus();},450);
            return;
        }
        t=e.target.closest('.btn-ubah');
        if(t){
            var d=t.dataset;
            document.getElementById('u-id').value=d.id;
            document.getElementById('u-ruanglingkup').value=d.ruanglingkup||'';
            document.getElementById('u-tanggalmulai').value=d.tanggalmulai||'';
            document.getElementById('u-tanggalberakhir').value=d.tanggalberakhir||'';
            document.getElementById('ubah-err').style.display='none';
            jQuery('#modalUbah').modal('show');
            setTimeout(function(){
                document.getElementById('u-ruanglingkup').focus();
                document.querySelectorAll('#modalUbah .char-input').forEach(initCharCounter);
            },450);
            return;
        }
        t=e.target.closest('.btn-hapus');
        if(t){
            var d=t.dataset;
            document.getElementById('h-id').value=d.id;
            document.getElementById('h-ruanglingkup').textContent=d.ruanglingkup||'-';
            document.getElementById('h-tanggalmulai').textContent=d.tanggalmulai?fmtTanggal(d.tanggalmulai):'-';
            document.getElementById('h-tanggalberakhir').textContent=d.tanggalberakhir?fmtTanggal(d.tanggalberakhir):'-';
            jQuery('#modalHapus').modal('show');
            return;
        }
        t=e.target.closest('.btn-toggle-kom');
        if(t){
            var d=t.dataset,ap=d.action==='approve';
            document.getElementById('ak-id').value=d.id;
            document.getElementById('ak-action').value=d.action;
            document.getElementById('ak-ruanglingkup').textContent=d.ruanglingkup||'-';
            document.getElementById('ak-tanggalmulai').textContent=d.tanggalmulai?fmtTanggal(d.tanggalmulai):'-';
            document.getElementById('ak-tanggalberakhir').textContent=d.tanggalberakhir?fmtTanggal(d.tanggalberakhir):'-';
            document.getElementById('ak-btn-label').textContent=ap?'Setujui':'Batalkan';
            var kb=document.getElementById('btn-konfirmasi-approve-kom');
            kb.className='btn '+(ap?'btn-success':'btn-danger');
            document.getElementById('ak-msg').innerHTML=ap
                ?'<i class="fas fa-check-circle text-success"></i> Anda akan <strong>menyetujui</strong> data ini sebagai <strong>Direktur Utama</strong>:'
                :'<i class="fas fa-ban text-danger"></i> Anda akan <strong>membatalkan approval</strong> data ini:';
            jQuery('#modalApproveKom').modal('show');
            return;
        }
        t=e.target.closest('#btn-komentar-global');
        if(t){loadKomentarList(true);markAllRead();jQuery('#modalKomentar').modal('show');return;}
        t=e.target.closest('#btn-retry');
        if(t){loadData();refreshApprovalStatus();return;}
        t=e.target.closest('#btn-nodata');
        if(t){execNodata();return;}
        t=e.target.closest('#btn-batal-nodata');
        if(t){execBatalNodata();return;}
        t=e.target.closest('#btn-simpan-tambah');if(t){execTambah();return;}
        t=e.target.closest('#btn-simpan-ubah');if(t){execUbah();return;}
        t=e.target.closest('#btn-konfirmasi-hapus');if(t){execHapus();return;}
        t=e.target.closest('#btn-konfirmasi-approve-kom');if(t){execApproveKom();return;}
        t=e.target.closest('#btn-kirim-komentar');if(t){execKomentar();return;}
        var bAD=e.target.closest('.btn-approve-all-dekom');
        if(bAD){throttle(function(){bulkApproval('Lingkupaudit/approveSemuaKom','Setujui SEMUA data sebagai Direktur Utama?');},2000)();return;}
        var bUD=e.target.closest('.btn-unapprove-all-dekom');
        if(bUD){throttle(function(){bulkApproval('Lingkupaudit/unapproveSemuaKom','Batalkan SEMUA approval Direktur Utama?');},2000)();return;}
    });

    // Komentar char count + ctrl+enter
    var kteks=document.getElementById('k-teks');
    if(kteks){
        kteks.addEventListener('keydown',function(e){if(e.ctrlKey&&e.key==='Enter'){e.preventDefault();execKomentar();}});
        kteks.addEventListener('input',function(){
            var l=this.value.length,el=document.getElementById('k-char-count');
            el.textContent=l+' / 1000';
            l>950?el.classList.add('danger'):el.classList.remove('danger');
        });
    }

    // Modal cleanup
    document.querySelectorAll('.modal').forEach(function(m){
        m.addEventListener('hidden.bs.modal',function(){
            this.querySelectorAll('.alert-danger').forEach(function(a){a.style.display='none';a.textContent='';});
            this.querySelectorAll('input[type=text],input[type=date],textarea').forEach(function(i){if(!i.readOnly)i.value='';});
            this.querySelectorAll('.char-input').forEach(function(el){
                var cid=el.dataset.counter,max=parseInt(el.dataset.max)||0;
                if(cid&&max){var c=document.getElementById(cid);if(c){c.textContent='0 / '+max;c.classList.remove('warn','danger');}}
            });
        });
    });

    (function initSync() {
        var _syncChecked = false;
        var _syncData    = null;

        var syncBtn  = document.getElementById('btn-sync-periode');
        var syncTip  = document.getElementById('sync-tooltip');

        if (!syncBtn) return;

        function syncCheckPrev(cb) {
            if (_syncChecked) { cb && cb(_syncData); return; }
            fetch(BASE + '/Lingkupaudit/checkPrevPeriode', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                _syncChecked = true; _syncData = res; cb && cb(res);
            })
            .catch(function() {
                _syncChecked = true;
                _syncData = { status: 'error' };
                cb && cb(_syncData);
            });
        }

        syncTip.textContent = 'Memeriksa periode sebelumnya…';

        syncCheckPrev(function(res) {
            if (res && res.status === 'found') {
                syncBtn.disabled = false;
                syncTip.textContent = 'Sinkron dari ' + (res.prev_label || 'periode sebelumnya');
            } else {
                syncBtn.disabled = true;
                syncTip.textContent = (res && res.message)
                    ? res.message
                    : 'Tidak ada data di periode sebelumnya';
            }
        });

        syncBtn.addEventListener('click', function() {
            if (!_syncData || _syncData.status !== 'found') return;

            document.getElementById('sync-current-label').textContent = _syncData.current_label || '-';
            document.getElementById('sync-prev-label').textContent    = _syncData.prev_label    || '-';

            var rows = _syncData.rows || [];
            var existingKeys = _syncData.existing_keys || [];

            var keySet = {};
            existingKeys.forEach(function(k) { keySet[k] = true; });

            var newCount = 0;
            var html = '';
            rows.forEach(function(r, j) {
                var compositeKey = (r.tanggalmulai || '') + '||' + (r.tanggalberakhir || '');
                var skip = !!keySet[compositeKey];
                if (!skip) newCount++;

                var rlPrev = (r.ruanglingkup || '').substring(0, 90);
                if ((r.ruanglingkup || '').length > 90) rlPrev += '…';

                html += '<div class="sync-prev-item' + (skip ? ' will-skip' : '') + '">'
                      + '<span class="sync-prev-item-idx">' + (j + 1) + '</span>'
                      + '<div class="sync-prev-item-body">'
                      +   '<div class="sync-prev-item-dates">'
                      +     '<i class="fas fa-play-circle" style="color:#0d9488;font-size:10px;margin-right:3px;"></i>'
                      +     esc(fmtTanggal(r.tanggalmulai) || '-')
                      +     ' &rarr; '
                      +     '<i class="fas fa-stop-circle" style="color:#ef4444;font-size:10px;margin-right:3px;"></i>'
                      +     esc(fmtTanggal(r.tanggalberakhir) || '-')
                      +   '</div>'
                      +   '<div class="sync-prev-item-preview">' + esc(rlPrev || '-') + '</div>'
                      + '</div>'
                      + (skip ? '<span class="sync-skip-badge">Sudah ada</span>' : '')
                      + '</div>';
            });

            document.getElementById('sync-count-num').textContent = newCount;
            document.getElementById('sync-preview-list').innerHTML = html
                || '<div style="padding:14px;text-align:center;color:#94a3b8;font-size:13px;">Tidak ada data</div>';
            document.getElementById('btn-konfirmasi-sync').disabled = (newCount === 0);

            jQuery('#modalSyncPeriode').modal('show');
        });

        var _syncPending = false;
        document.getElementById('btn-konfirmasi-sync').addEventListener('click', debounce(function() {
            if (_syncPending) return;
            _syncPending = true;

            var konfirmBtn = document.getElementById('btn-konfirmasi-sync');
            var oriLabel   = konfirmBtn.innerHTML;
            konfirmBtn.innerHTML  = '<i class="fas fa-spinner fa-spin"></i>';
            konfirmBtn.disabled   = true;
            syncBtn.classList.add('syncing');

            var body = new URLSearchParams();
            body.append(CSRF, HASH);

            fetch(BASE + '/Lingkupaudit/syncFromPrevPeriode', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: body.toString()
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                _syncPending = false;
                konfirmBtn.innerHTML = oriLabel;
                konfirmBtn.disabled  = false;
                syncBtn.classList.remove('syncing');

                if (res && res.csrf_hash) HASH = res.csrf_hash;

                jQuery('#modalSyncPeriode').modal('hide');

                if (res && res.status === 'success') {
                    toast(res.message || 'Sinkronisasi berhasil', 'success');
                    loadData();
                    refreshApprovalStatus();
                    _syncChecked = false;
                    _syncData    = null;
                    syncBtn.disabled      = true;
                    syncTip.textContent   = 'Data sudah disinkronisasi';
                } else {
                    toast((res && res.message) || 'Gagal sinkronisasi', 'error');
                }
            })
            .catch(function() {
                _syncPending = false;
                konfirmBtn.innerHTML = oriLabel;
                konfirmBtn.disabled  = false;
                syncBtn.classList.remove('syncing');
                toast('Terjadi kesalahan server.', 'error');
            });
        }, 500));

        var _importPreview = [];

        document.addEventListener('click', function(e){
            var t = e.target.closest('#btn-show-import');
            if (t) {
                document.getElementById('import-file').value = '';
                document.getElementById('import-step-preview').style.display = 'none';
                document.getElementById('import-step-upload').style.display = '';
                document.getElementById('import-upload-err').style.display = 'none';
                document.getElementById('btn-simpan-import').style.display = 'none';
                document.getElementById('btn-parse-import').style.display = 'none';
                _importPreview = [];
                jQuery('#modalImport').modal('show');
            }
        });

        document.getElementById('import-file').addEventListener('change', function(){
            document.getElementById('btn-parse-import').style.display = this.files.length ? '' : 'none';
        });

        document.getElementById('btn-parse-import').addEventListener('click', function(){
            var f = document.getElementById('import-file').files[0];
            if (!f) return;
            var fd = new FormData();
            fd.append('file', f);
            fd.append(CSRF, HASH);
            var btn = this, restore = btnLoading(btn);
            fetch(BASE + '/Lingkupaudit/previewImportExcel', {
                method: 'POST',
                headers: {'X-Requested-With':'XMLHttpRequest'},
                body: fd
            })
            .then(function(r){ return r.json(); })
            .then(function(res){
                restore();
                updateCsrf(res);
                var errBox = document.getElementById('import-upload-err');
                if (res.status !== 'success') {
                    errBox.textContent = res.message; errBox.style.display = '';
                    return;
                }
                _importPreview = res.preview;
                renderImportPreview(res);
                document.getElementById('import-step-upload').style.display = 'none';
                document.getElementById('import-step-preview').style.display = '';
                document.getElementById('btn-parse-import').style.display = 'none';
                document.getElementById('btn-simpan-import').style.display = res.ok_count > 0 ? '' : 'none';
            })
            .catch(function(){
                restore();
                var errBox = document.getElementById('import-upload-err');
                errBox.textContent = 'Gagal memproses file'; errBox.style.display = '';
            });
        });

        function renderImportPreview(res){
            document.getElementById('import-ok-count').textContent = res.ok_count + ' baris siap disimpan';
            document.getElementById('import-total-count').textContent = 'dari ' + res.total + ' baris terbaca';
            var body = document.querySelector('#import-preview-table tbody');
            body.innerHTML = '';
            res.preview.forEach(function(r){
                var badge = r.row_status === 'ok'
                    ? '<span class="badge badge-success">Siap</span>'
                    : '<span class="badge badge-danger" title="'+esc((r.errors||[]).join(', '))+'">Error</span>';
                var rlShort = (r.ruanglingkup||'').length > 60 ? r.ruanglingkup.substring(0,60)+'…' : r.ruanglingkup;
                var tr = document.createElement('tr');
                tr.innerHTML = '<td>'+r.line+'</td>'
                    + '<td>'+(r.tanggalmulai ? fmtTanggal(r.tanggalmulai) : '-')+'</td>'
                    + '<td>'+(r.tanggalberakhir ? fmtTanggal(r.tanggalberakhir) : '-')+'</td>'
                    + '<td>'+esc(rlShort||'-')+'</td>'
                    + '<td>'+badge+'</td>';
                body.appendChild(tr);
            });
        }

        document.getElementById('btn-simpan-import').addEventListener('click', function(){
            var okRows = _importPreview.filter(function(r){ return r.row_status === 'ok'; });
            if (!okRows.length) return;
            var btn = this, restore = btnLoading(btn);
            doPost('Lingkupaudit/confirmImportExcel', { rows: JSON.stringify(okRows) }, function(res){
                restore();
                if (res.status === 'success') {
                    jQuery('#modalImport').modal('hide');
                    toast(res.message);
                    loadData();
                    refreshApprovalStatus();
                } else {
                    toast(res.message, 'error');
                }
            }, function(m){ restore(); toast(m, 'error'); });
        });

    })();

    loadData();
    refreshApprovalStatus();
    pollUnreadCount();
    startPolling();
})();
</script>