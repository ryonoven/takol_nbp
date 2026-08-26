<?php
$_semDisplay = esc($periodeDetail['semester']);
$_semNum = strtolower($_semDisplay) === 'ganjil' ? '1' : (strtolower($_semDisplay) === 'genap' ? '2' : $_semDisplay);
$_tahun = esc($periodeDetail['tahun']);
$_namabpr = esc($bprData['namabpr'] ?? 'Nama BPR');
$_canEdit = (($userInGroupPE ?? false) || ($userInGroupAdmin ?? false));
$_canKom  = (($userInGroupAdmin ?? false) || ($userInGroupDekom ?? false) || ($userInGroupDireksi2 ?? false) || ($userInGroupDireksi ?? false));
$_canKomentar = (($userInGroupAdmin ?? false) || ($userInGroupDekom ?? false) || ($userInGroupDireksi ?? false) || ($userInGroupPE ?? false) || ($userInGroupDekom2 ?? false) || ($userInGroupDireksi2 ?? false));
$_flashMsg = session()->getFlashdata('message');
$_flashErr = session()->getFlashdata('err');
?>

<div id="toast-wrap" style="position:fixed;top:20px;right:20px;z-index:99999;min-width:290px;pointer-events:none;"></div>

<div class="container-fluid">
    <a href="<?= base_url('Showdirymf') ?>" class="btn-kembali mb-3">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>

    <?php if ($_flashMsg): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <strong><?= esc($_flashMsg) ?></strong>
    </div>
    <?php endif; ?>
    <?php if ($_flashErr): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <strong><?= esc($_flashErr) ?></strong>
    </div>
    <?php endif; ?>

    <div class="card card-body mb-3 shadow-sm">
        <div class="page-header">
            <div style="position:relative;min-height:50px;" class="mb-3">
                <div id="badge-approval-dekom" class="approval-status-badge badge-status-secondary" style="position:absolute;left:0;top:0;">
                    <i class="fas fa-clock"></i>
                    <div>
                        <span class="approval-status-label">Direktur Yang Membawahi Fungsi Kepatuhan</span><br>
                        <span class="approval-status-sub">Belum disetujui seluruhnya</span>
                    </div>
                </div>
            </div>
            <h1 class="page-title"><?= ($judul) ?></h1>
            <div class="page-subtitle">Semester <?= $_semNum ?> Tahun <?= $_tahun ?></div>
            <span class="badge-namabpr"><?= $_namabpr ?></span>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center" style="background:var(--grad);">
            <h5 class="m-0 font-weight-bold text-white">
                <i class="fas fa-users"></i> Formasi Sumber Daya Manusia
            </h5>
            <div class="d-flex align-items-center" style="gap:8px;">
                <?php if ($_canEdit): ?>
                    <button type="button" class="btn btn-success btn-sm" id="btn-show-import" title="Impor Excel">
                        <i class="fas fa-file-excel"></i> Impor Excel
                    </button>
                <?php endif; ?>
                <?php if ($_canEdit): ?>
                <button type="button" class="btn btn-primaryy btn-sm" id="btn-show-tambah" title="Tambah Data">
                    <i class="fas fa-plus"></i>
                </button>
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
                <?php if ($_canKomentar): ?>
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
            <div id="skeleton-container">
                <?php for ($i = 0; $i < 3; $i++): ?>
                <div class="sdm-card-skel">
                    <div class="skel-card-header"></div>
                    <div class="skel-card-body">
                        <div class="skel" style="width:55%;height:18px;margin-bottom:10px;"></div>
                        <div class="skel" style="width:70%;height:14px;margin-bottom:8px;"></div>
                        <div class="skel" style="width:40%;height:14px;"></div>
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
                <div id="cards-formasisdm" class="sdm-cards-grid"></div>
                <div id="empty-state" class="empty-state-box" style="display:none;">
                    <i class="fas fa-users-slash fa-3x text-muted mb-3 d-block"></i>
                    <h6 class="text-muted mb-1">Belum ada data Formasi Sumber Daya Manusia</h6>
                    <p class="text-muted small mb-3">Data SDM untuk periode ini belum diinput.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mt-2 mb-2">
        <div style="position:relative;min-height:80px;" class="mb-2">
            <?php if ($_canKom): ?>
            <div style="position:absolute;left:50%;top:0;transform:translateX(-50%);min-width:220px;">
                <div class="card shadow-sm action-card">
                    <div class="card-body py-2 text-center">
                        <small class="d-block font-weight-bold text-dark mb-2">
                            <i class="fas fa-user-tie text-success"></i>
                            Direktur Yang Membawahkan Fungsi Kepatuhan
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
                <a href="<?= base_url('Formasisdm/exporttxtformasisdm') ?>" class="btn btn-warning btn-sm">
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
                <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Tambah Data Formasi Sumber Daya Manusia</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">NIK <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="t-nik" placeholder="Masukkan NIK" maxlength="30" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="t-nama" placeholder="Masukkan nama lengkap" maxlength="255" autocomplete="off">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Jabatan <span class="text-danger">*</span></label>
                            <select class="form-control" id="t-jabatan">
                                <option value="">-- Pilih Jabatan --</option>
                                <option value="110">Direktur Utama</option>
                                <option value="120">Direktur yang membawahkan fungsi kepatuhan</option>
                                <option value="130">Direktur</option>
                                <option value="210">Komisaris Utama</option>
                                <option value="220">Komisaris</option>
                                <option value="230">Komisaris Independen</option>
                                <option value="310">Komite Audit</option>
                                <option value="320">Komite Pemantau Resiko</option>
                                <option value="330">Komite Remunerasi dan Nominasi</option>
                                <option value="340">Komite Manajemen Resiko</option>
                                <option value="350">Komite Lainnya</option>
                                <option value="410">Pejabat Eksekutif Kepatuhan</option>
                                <option value="420">Pejabat Eksekutif Manajemen Resiko</option>
                                <option value="430">Pejabat Eksekutif Audit Intern</option>
                                <option value="440">Pejabat Eksekutif APU PPT</option>
                                <option value="450">Pejabat Eksekutif Lainnya</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Status Kepegawaian</label>
                            <select class="form-control" id="t-status">
                                <option value="">-- Pilih Status --</option>
                                <option value="01">Pengangkatan Baru</option>
                                <option value="02">Pengangkatan Kembali</option>
                                <option value="03">Perubahan Jabatan</option>
                                <option value="04">Tidak Berubah</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Tingkat Pendidikan</label>
                            <select class="form-control" id="t-tingkatpendidikan">
                                <option value="">-- Pilih Pendidikan --</option>
                                <option value="01">S3</option>
                                <option value="02">S2</option>
                                <option value="03">S1</option>
                                <option value="04">D3</option>
                                <option value="05">SLTA</option>
                                <option value="99">Lainnya</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Tanggal Pengangkatan</label>
                            <input type="date" class="form-control" id="t-masajabatan" autocomplete="off">
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
                <h5 class="modal-title"><i class="fas fa-edit"></i> Ubah Data Formasi Sumber Daya Manusia</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="u-id">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">NIK <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="u-nik" maxlength="30" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="u-nama" maxlength="255" autocomplete="off">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Jabatan <span class="text-danger">*</span></label>
                            <select class="form-control" id="u-jabatan">
                                <option value="">-- Pilih Jabatan --</option>
                                <option value="110">Direktur Utama</option>
                                <option value="120">Direktur yang membawahkan fungsi kepatuhan</option>
                                <option value="130">Direktur</option>
                                <option value="210">Komisaris Utama</option>
                                <option value="220">Komisaris</option>
                                <option value="230">Komisaris Independen</option>
                                <option value="310">Komite Audit</option>
                                <option value="320">Komite Pemantau Resiko</option>
                                <option value="330">Komite Remunerasi dan Nominasi</option>
                                <option value="340">Komite Manajemen Resiko</option>
                                <option value="350">Komite Lainnya</option>
                                <option value="410">Pejabat Eksekutif Kepatuhan</option>
                                <option value="420">Pejabat Eksekutif Manajemen Resiko</option>
                                <option value="430">Pejabat Eksekutif Audit Intern</option>
                                <option value="440">Pejabat Eksekutif APU PPT</option>
                                <option value="450">Pejabat Eksekutif Lainnya</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Status Kepegawaian</label>
                            <select class="form-control" id="u-status">
                                <option value="">-- Pilih Status --</option>
                                <option value="01">Pengangkatan Baru</option>
                                <option value="02">Pengangkatan Kembali</option>
                                <option value="03">Perubahan Jabatan</option>
                                <option value="04">Tidak Berubah</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Tingkat Pendidikan</label>
                            <select class="form-control" id="u-tingkatpendidikan">
                                <option value="">-- Pilih Pendidikan --</option>
                                <option value="01">S3</option>
                                <option value="02">S2</option>
                                <option value="03">S1</option>
                                <option value="04">D3</option>
                                <option value="05">SLTA</option>
                                <option value="99">Lainnya</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Tanggal Pengangkatan</label>
                            <input type="date" class="form-control" id="u-masajabatan" autocomplete="off">
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
                <div id="sync-info-box" class="sync-info-box">
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
                    <div class="sync-count-badge" id="sync-count-badge">
                        <i class="fas fa-users"></i>
                        <span id="sync-count-num">0</span> data akan disalin
                    </div>
                    <div class="sync-preview-list" id="sync-preview-list"></div>
                </div>
 
                <div class="alert alert-warning mb-0 mt-3" style="font-size:12.5px;border-radius:10px;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Perhatian:</strong> Data yang sudah ada di periode aktif
                    <strong>tidak akan ditimpa</strong>. Hanya data baru (berdasarkan NIK)
                    yang akan ditambahkan. Status approval akan direset ke awal.
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
                    <tr><th width="40%">NIK</th><td id="h-nik"></td></tr>
                    <tr><th>Nama</th><td id="h-nama"></td></tr>
                    <tr><th>Jabatan</th><td id="h-jabatan"></td></tr>
                    <tr><th>Status</th><td id="h-status"></td></tr>
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

<!-- MODAL APPROVE KOM -->
<div class="modal fade" id="modalApproveKom" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#155724;color:white;">
                <h5 class="modal-title"><i class="fas fa-user-tie"></i> Approval Direktur Yang Membawahkan Fungsi Kepatuhan</h5>
                <button type="button" class="close" style="color:white;" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="ak-id">
                <input type="hidden" id="ak-action">
                <p id="ak-msg" class="mb-3"></p>
                <table class="table table-bordered table-sm">
                    <tr><th width="40%">Nama</th><td id="ak-nama"></td></tr>
                    <tr><th>Jabatan</th><td id="ak-jabatan"></td></tr>
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
                    <div><div class="komentar-modal-title">Komentar — Formasi Sumber Daya Manusia</div></div>
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
                <?php if ($_canKomentar): ?>
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
                <?php if ($_canKomentar): ?>
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
                        Format wajib: <strong>Nama | NIK | Jabatan | Status Kepegawaian | Tingkat Pendidikan | Tanggal Pengangkatan</strong>.
                        Jabatan/Status/Pendidikan bisa ditulis dalam bentuk teks (contoh: "Direktur Utama").
                        <a href="<?= base_url('assets/template/Template Impor Formasi Sumber Daya Manusia.xlsx') ?>">Unduh template</a>.
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
                            <thead><tr><th>#</th><th>Nama</th><th>NIK</th><th>Jabatan</th><th>Status</th></tr></thead>
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
:root{--brand:#4f46e5;--brand-light:#818cf8;--brand-dark:#3730a3;--brand-bg:#eef2ff;--success:#10b981;--success-bg:#d1fae5;--danger:#ef4444;--danger-bg:#fee2e2;--warning:#f59e0b;--warning-bg:#fef3c7;--info:#06b6d4;--info-bg:#cffafe;--gray-50:#f8fafc;--gray-100:#f1f5f9;--gray-200:#e2e8f0;--gray-400:#94a3b8;--gray-500:#64748b;--gray-700:#334155;--gray-800:#1e293b;--gray-900:#0f172a;--blue:#1f2aa8;--r-sm:8px;--r:12px;--r-lg:16px;--sh-sm:0 1px 3px rgba(0,0,0,.08),0 1px 2px rgba(0,0,0,.06);--sh:0 4px 6px -1px rgba(0,0,0,.1),0 2px 4px -1px rgba(0,0,0,.06);--sh-lg:0 10px 25px -3px rgba(0,0,0,.1),0 4px 6px -2px rgba(0,0,0,.05);--tr:all .2s cubic-bezier(.4,0,.2,1);--grad:linear-gradient(135deg,#141863 0%,#1f2aa8 100%);}
*{font-family:'Plus Jakarta Sans',sans-serif;}
.page-header{text-align:center;padding:32px 20px 24px;background:white;border-bottom:1px solid var(--gray-100);position:relative;overflow:hidden;}
.page-header::before,.page-header::after{content:'';position:absolute;border-radius:50%;}
.page-header::before{top:-60px;right:-60px;width:200px;height:200px;background:radial-gradient(circle,#eef2ff 0%,transparent 70%);}
.page-header::after{bottom:-40px;left:-40px;width:160px;height:160px;background:radial-gradient(circle,#f0fdf4 0%,transparent 70%);}
.page-title{font-size:clamp(1.3rem,3.5vw,1.75rem);font-weight:800;color:var(--gray-900);margin-bottom:6px;letter-spacing:-.5px;position:relative;z-index:1;}
.page-subtitle{font-size:1rem;font-weight:600;color:var(--gray-900);margin-bottom:12px;position:relative;z-index:1;}
.badge-namabpr{display:inline-block;background:var(--grad);color:white;font-size:1rem;font-weight:700;padding:6px 20px;border-radius:100px;letter-spacing:.3px;position:relative;z-index:1;box-shadow:0 4px 12px rgba(79,70,229,.3);}
.approval-status-badge{display:flex;align-items:center;gap:10px;padding:10px 16px;border-radius:var(--r);font-size:13px;font-weight:600;transition:var(--tr);border:1.5px solid transparent;}
.approval-status-badge i{font-size:18px;flex-shrink:0;}
.approval-status-label{font-weight:700;font-size:12px;}
.approval-status-sub{font-size:11px;opacity:.8;}
.badge-status-secondary{background:var(--gray-100);color:var(--gray-500);border-color:var(--gray-200);}
.badge-status-success{background:var(--success-bg);color:#065f46;border-color:#6ee7b7;}
.action-card{border-radius:var(--r)!important;border:1.5px solid var(--gray-200)!important;transition:var(--tr);overflow:hidden;}
.action-card:hover{border-color:var(--brand-light)!important;box-shadow:0 8px 24px rgba(79,70,229,.12)!important;transform:translateY(-3px);}
.card-header{border-radius:var(--r-lg) var(--r-lg) 0 0!important;}
.btn{transition:var(--tr);font-weight:600;}
.btn:hover{transform:translateY(-2px);box-shadow:var(--sh);}
.btn:active{transform:scale(.96)!important;}
.btn-sm{font-size:12px;}
.btn-kembali{display:inline-flex;align-items:center;gap:6px;background:white;border:1.5px solid var(--gray-200);border-radius:var(--r-sm);padding:8px 16px;font-size:.875rem;font-weight:600;color:var(--gray-500);text-decoration:none;transition:var(--tr);margin:14px 0 0 16px;}
.btn-kembali:hover{background:var(--brand-bg);border-color:var(--brand-light);color:var(--brand);text-decoration:none;}
.btn-primaryy{background:white;color:var(--blue);border:none;border-radius:var(--r-sm);width:32px;height:32px;padding:0;display:inline-flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;box-shadow:0 2px 8px rgba(0,0,0,.15);transition:var(--tr);}
.btn-primaryy:hover{background:var(--brand-bg);transform:rotate(90deg) scale(1.1);}
.sdm-card-skel{background:white;border-radius:var(--r-lg);overflow:hidden;margin-bottom:14px;box-shadow:var(--sh-sm);}
.skel-card-header{height:52px;background:linear-gradient(90deg,#f1f5f9 25%,#e2e8f0 50%,#f1f5f9 75%);background-size:200% 100%;animation:skAnim 1.4s infinite;}
.skel-card-body{padding:16px;}
.skel{border-radius:var(--r);background:linear-gradient(90deg,#f1f5f9 25%,#e2e8f0 50%,#f1f5f9 75%);background-size:200% 100%;animation:skAnim 1.4s infinite;}
@keyframes skAnim{0%{background-position:200% 0}100%{background-position:-200% 0}}
.sdm-cards-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:16px;padding:4px 2px;}
.sdm-card{background:white;border-radius:var(--r-lg);box-shadow:var(--sh-sm);border:1.5px solid var(--gray-100);overflow:hidden;transition:var(--tr);animation:rowIn .3s ease both;display:flex;flex-direction:column;}
.sdm-card:hover{box-shadow:var(--sh-lg);border-color:var(--brand-light);transform:translateY(-3px);}
@keyframes rowIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
.scard-header{display:flex;align-items:center;justify-content:space-between;padding:11px 14px;background:var(--grad);gap:10px;}
.scard-index-wrap{display:flex;align-items:center;gap:8px;min-width:0;flex:1;}
.scard-index{background:rgba(255,255,255,.2);color:white;font-size:11px;font-weight:800;width:24px;height:24px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;}
.scard-nik{color:rgba(255,255,255,.9);font-size:12px;font-weight:700;letter-spacing:.5px;background:rgba(255,255,255,.15);padding:2px 9px;border-radius:6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.scard-actions{display:flex;align-items:center;gap:6px;flex-shrink:0;}
.scard-acc-icon{font-size:20px;cursor:pointer;transition:var(--tr);}
.scard-acc-icon.acc-ok{color:#6ee7b7;}
.scard-acc-icon.acc-no{color:rgba(255,255,255,.35);}
.scard-acc-icon:hover{transform:scale(1.25);}
.scard-acc-icon.acc-ok:hover{filter:drop-shadow(0 0 5px rgba(110,231,183,.7));}
.scard-acc-icon.acc-no:hover{color:rgba(255,255,255,.7);}
.scard-btn{width:28px;height:28px;padding:0!important;display:inline-flex;align-items:center;justify-content:center;border-radius:var(--r-sm)!important;font-size:12px!important;}
.scard-body{padding:14px 16px;display:flex;flex-direction:column;gap:10px;flex:1;}
.scard-nama{font-size:15px;font-weight:800;color:var(--gray-900);line-height:1.3;}
.scard-jabatan{font-size:12.5px;font-weight:600;color:var(--brand);display:flex;align-items:center;gap:5px;line-height:1.4;}
.scard-jabatan i{font-size:11px;opacity:.7;flex-shrink:0;}
.scard-meta-row{display:flex;gap:12px;flex-wrap:wrap;}
.scard-meta-item{display:flex;flex-direction:column;gap:3px;}
.scard-meta-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--gray-400);}
.scard-meta-label i{margin-right:3px;}
.scard-meta-val{font-size:12.5px;font-weight:600;color:var(--gray-700);}
.scard-footer-badge{display:flex;align-items:center;padding-top:6px;border-top:1.5px solid var(--gray-100);}
.bs{display:inline-flex;align-items:center;gap:5px;font-size:11px;padding:3px 9px;border-radius:100px;font-weight:700;white-space:nowrap;border:1.5px solid transparent;}
.bs::before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor;opacity:.7;}
.bs-tetap{background:var(--success-bg);color:#065f46;border-color:#a7f3d0;}
.bs-kontrak{background:var(--brand-bg);color:var(--brand-dark);border-color:var(--brand-light);}
.bs-tidak-tetap{background:var(--warning-bg);color:#92400e;border-color:#fcd34d;}
.bs-magang{background:#f3e8ff;color:#6b21a8;border-color:#d8b4fe;}
.bs-lain{background:var(--danger-bg);color:#991b1b;border-color:#fca5a5;}
.chip-edu{display:inline-block;background:var(--info-bg);color:#164e63;font-size:11px;font-weight:700;padding:3px 9px;border-radius:6px;border:1.5px solid #a5f3fc;}
.scard-acc-badge{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:3px 10px;border-radius:100px;border:1.5px solid transparent;}
.acc-badge-ok{background:var(--success-bg);color:#065f46;border-color:#a7f3d0;}
.acc-badge-no{background:var(--warning-bg);color:#92400e;border-color:#fcd34d;}
.notif-badge{position:absolute;top:-7px;right:-7px;background:var(--danger);color:#fff;border-radius:50%;padding:2px 5px;font-size:10px;font-weight:700;min-width:17px;text-align:center;line-height:1.3;box-shadow:0 2px 6px rgba(239,68,68,.5);animation:pulse 2s infinite;}
@keyframes pulse{0%,100%{box-shadow:0 0 0 0 rgba(239,68,68,.4)}50%{box-shadow:0 0 0 5px rgba(239,68,68,0)}}
.empty-state-box{text-align:center;padding:60px 20px;background:var(--gray-50);border-radius:var(--r-lg);border:2px dashed var(--gray-200);margin:10px 0;}
.btn-komentar-global-btn{position:relative;width:36px;height:36px;border-radius:50%;background:white;border:none;color:var(--blue);font-size:17px;display:inline-flex;align-items:center;justify-content:center;box-shadow:0 2px 10px rgba(0,0,0,.18);cursor:pointer;transition:all .25s cubic-bezier(.4,0,.2,1);outline:none;}
.btn-komentar-global-btn:hover{background:var(--brand-bg);color:var(--brand);transform:scale(1.13);box-shadow:0 4px 18px rgba(79,70,229,.25);}
.btn-komentar-global-btn:active{transform:scale(.93);}
.btn-komentar-global-btn.has-unread{animation:btn-shake 1.8s ease infinite;background:var(--brand-bg);color:var(--brand);}
@keyframes btn-shake{0%,100%{transform:rotate(0deg) scale(1)}10%{transform:rotate(-14deg) scale(1.08)}20%{transform:rotate(12deg) scale(1.08)}30%{transform:rotate(-10deg) scale(1.05)}40%{transform:rotate(8deg) scale(1.05)}50%{transform:rotate(0deg) scale(1)}}
.btn-komentar-ripple{position:absolute;inset:-4px;border-radius:50%;border:2px solid var(--brand-light);opacity:0;pointer-events:none;}
.btn-komentar-global-btn.has-unread .btn-komentar-ripple{animation:ripple-out 1.8s ease infinite;}
@keyframes ripple-out{0%{transform:scale(.9);opacity:.7}100%{transform:scale(1.6);opacity:0}}
.komentar-modal-content{border-radius:18px!important;overflow:hidden;border:none;box-shadow:0 20px 60px rgba(0,0,0,.18)!important;}
.komentar-modal-header{background:var(--grad);padding:18px 22px!important;border:none!important;display:flex;align-items:center;justify-content:space-between;}
.komentar-modal-title-wrap{display:flex;align-items:center;gap:12px;}
.komentar-modal-icon-wrap{width:40px;height:40px;border-radius:12px;background:rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center;font-size:18px;color:white;flex-shrink:0;}
.komentar-modal-title{font-size:15px;font-weight:800;color:white;line-height:1.2;}
.komentar-close-btn{width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,.15);border:none;color:white;font-size:14px;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all .2s;flex-shrink:0;}
.komentar-close-btn:hover{background:rgba(255,255,255,.3);transform:rotate(90deg);}
.komentar-modal-body{padding:20px 22px!important;background:var(--gray-50);}
.komentar-modal-footer{background:white;border-top:1px solid var(--gray-100)!important;padding:12px 22px!important;}
.komentar-list-label{font-size:12px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.8px;margin-bottom:10px;}
.komentar-list-wrapper{background:white;border:1.5px solid var(--gray-200);border-radius:12px;max-height:320px;overflow-y:auto;padding:12px;margin-bottom:16px;scroll-behavior:smooth;}
.komentar-list-wrapper::-webkit-scrollbar{width:5px;}
.komentar-list-wrapper::-webkit-scrollbar-thumb{background:var(--gray-200);border-radius:10px;}
.komentar-loading,.komentar-empty{text-align:center;color:var(--gray-400);padding:30px 0;font-size:13px;}
.km-item{background:var(--gray-50);border-left:3px solid var(--brand-light);border-radius:10px;padding:10px 14px;margin-bottom:8px;box-shadow:var(--sh-sm);animation:kmIn .25s ease both;}
.km-item:last-child{margin-bottom:0;}
.km-item.km-own{border-left-color:var(--success);background:#f0fdf4;}
@keyframes kmIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}
.km-meta{display:flex;justify-content:space-between;margin-bottom:5px;flex-wrap:wrap;gap:4px;}
.km-author{font-weight:700;font-size:12px;color:var(--gray-800);display:flex;align-items:center;gap:5px;}
.km-you{font-size:9px;background:var(--success);color:white;padding:1px 6px;border-radius:100px;font-weight:700;}
.km-time{font-size:11px;color:var(--gray-400);}
.km-text{font-size:13px;white-space:pre-wrap;margin:0;color:var(--gray-700);line-height:1.5;}
.komentar-input-wrap{background:white;border:1.5px solid var(--gray-200);border-radius:12px;padding:14px;transition:border-color .2s;}
.komentar-input-wrap:focus-within{border-color:var(--brand);box-shadow:0 0 0 3px rgba(79,70,229,.1);}
.komentar-input-label{font-size:12px;font-weight:700;color:var(--gray-500);margin-bottom:8px;text-transform:uppercase;letter-spacing:.6px;}
.komentar-textarea{width:100%;border:none;outline:none;resize:none;font-size:13.5px;color:var(--gray-700);font-family:'Plus Jakarta Sans',sans-serif;line-height:1.6;background:transparent;}
.komentar-input-footer{display:flex;justify-content:space-between;align-items:center;margin-top:8px;padding-top:8px;border-top:1px solid var(--gray-100);}
.komentar-hint{font-size:11px;color:var(--gray-400);}
.komentar-char{font-size:11px;color:var(--gray-400);font-weight:600;transition:color .2s;}
.komentar-char.danger{color:var(--danger);}
.modal-header-sdm{background:var(--grad);color:white;}
.modal-content{border-radius:var(--r-lg);overflow:hidden;border:none;box-shadow:var(--sh-lg);}
.modal-body{padding:20px 24px!important;}
.modal-footer{padding:12px 24px!important;}
.modal-body .form-group{margin-bottom:16px!important;}
.modal-body .form-control{width:100%!important;box-sizing:border-box!important;border-radius:8px!important;border:1.5px solid var(--gray-200)!important;padding:8px 12px!important;font-size:13.5px!important;transition:var(--tr)!important;}
.modal-body .form-control:focus{border-color:var(--brand)!important;box-shadow:0 0 0 3px rgba(79,70,229,.15)!important;outline:none!important;}
.modal-body .row{margin-left:-8px!important;margin-right:-8px!important;}
.modal-body .row>[class*="col-"]{padding-left:8px!important;padding-right:8px!important;}
.modal-dialog{margin:20px auto!important;max-width:720px!important;}
.t-item{background:white;border-radius:var(--r);padding:14px 18px;margin-bottom:10px;box-shadow:var(--sh-lg);display:flex;align-items:center;gap:12px;font-size:13.5px;animation:tIn .3s ease;pointer-events:all;border-left:4px solid;}
.t-item.t-success{border-color:var(--success);color:#065f46;}
.t-item.t-error{border-color:var(--danger);color:#991b1b;}
.t-item.t-info{border-color:var(--info);color:#164e63;}
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
    opacity: 0; transition: opacity .2s;
    z-index: 100;
}
.sync-tooltip::after {
    content: ''; position: absolute; top: 100%; left: 50%;
    transform: translateX(-50%);
    border: 5px solid transparent; border-top-color: #1e293b;
}
.btn-sync-periode:hover + .sync-tooltip,
.btn-sync-periode:focus + .sync-tooltip { opacity: 1; }
 
/* Sync modal */
.sync-info-box {
    display: flex; align-items: center; justify-content: center;
    gap: 16px; background: #f0fdfa;
    border: 1.5px solid #99f6e4; border-radius: 12px;
    padding: 14px 18px;
}
.sync-info-periode {
    display: flex; flex-direction: column; align-items: center; gap: 3px;
    flex: 1; text-align: center;
}
.sync-info-label {
    font-size: 10px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .7px; color: #5eead4;
}
.sync-info-val {
    font-size: 14px; font-weight: 800; color: #0f766e;
}
.sync-arrow { font-size: 20px; color: #0d9488; transform: rotate(0deg); }
.sync-count-badge {
    display: inline-flex; align-items: center; gap: 8px;
    background: #d1fae5; color: #065f46;
    font-size: 13px; font-weight: 700;
    padding: 6px 14px; border-radius: 100px;
    border: 1.5px solid #a7f3d0; margin-bottom: 10px;
}
.sync-preview-list {
    max-height: 180px; overflow-y: auto;
    border: 1.5px solid #e2e8f0; border-radius: 10px;
    background: white;
}
.sync-preview-list::-webkit-scrollbar { width: 4px; }
.sync-preview-list::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
.sync-prev-item {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 14px; border-bottom: 1px solid #f1f5f9;
    font-size: 12.5px;
}
.sync-prev-item:last-child { border-bottom: none; }
.sync-prev-item-idx {
    width: 20px; height: 20px; border-radius: 50%;
    background: #f0fdfa; color: #0d9488;
    font-size: 10px; font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.sync-prev-item-name { font-weight: 700; color: #1e293b; flex: 1; min-width: 0; }
.sync-prev-item-nik { font-size: 11px; color: #94a3b8; }
.sync-prev-item-jab { font-size: 11px; color: #64748b; }
.sync-prev-item.will-skip { opacity: .45; }
.sync-skip-badge {
    font-size: 10px; font-weight: 700;
    background: #fef3c7; color: #92400e;
    padding: 2px 7px; border-radius: 6px; flex-shrink: 0;
}
@keyframes tIn{from{transform:translateX(110%);opacity:0}to{transform:translateX(0);opacity:1}}
.blk-disabled{opacity:.4;pointer-events:none;}
@media(max-width:576px){.sdm-cards-grid{grid-template-columns:1fr;}.modal-dialog{margin:10px!important;max-width:calc(100% - 20px)!important;}.modal-body{padding:14px 16px!important;}.modal-body .row>[class*="col-"]{padding-left:6px!important;padding-right:6px!important;}}
</style>

<?php
$_base    = rtrim(base_url(), '/');
$_csrf    = csrf_token();
$_hash    = csrf_hash();
$_userId  = (int)($userId ?? 0);
$_fullname = addslashes($fullname ?? 'Unknown');
$_periodeId = (int)($periodeId ?? 0);
$_kodebpr  = addslashes($kodebpr ?? '');
$_canEditJs  = $_canEdit  ? 'true' : 'false';
$_canKomJs   = $_canKom   ? 'true' : 'false';
?>
<script>
(function wait(){
    if(typeof jQuery==='undefined') return setTimeout(wait,50);
    jQuery(document).ready(init);
})();

function init($){
    var BASE='<?= $_base ?>',CSRF='<?= $_csrf ?>',HASH='<?= $_hash ?>',
        UID=<?= $_userId ?>,FNAME='<?= $_fullname ?>',
        PID=<?= $_periodeId ?>,KBPR='<?= $_kodebpr ?>',
        CAN_EDIT=<?= $_canEditJs ?>,CAN_KOM=<?= $_canKomJs ?>;

    // Utilities
    function esc(s){ return s==null?'':String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
    function debounce(fn,ms){ var t; return function(){ var a=arguments,c=this; clearTimeout(t); t=setTimeout(function(){ fn.apply(c,a); },ms); }; }
    function throttle(fn,ms){ var busy=false; return function(){ if(busy)return; busy=true; fn.apply(this,arguments); setTimeout(function(){ busy=false; },ms); }; }

    function toast(msg,type){
        type=type||'success';
        var icons={success:'check-circle',error:'exclamation-circle',info:'info-circle'};
        var el=$('<div class="t-item t-'+type+'"><i class="fas fa-'+icons[type]+' fa-lg"></i><div>'+msg+'</div></div>');
        $('#toast-wrap').append(el);
        setTimeout(function(){ el.fadeOut(400,function(){ $(this).remove(); }); },3500);
    }

    function csrf(){
        var d={};
        var m=document.cookie.match(/ci_csrf_token=([^;]+)/);
        if(m) HASH=decodeURIComponent(m[1]);
        d[CSRF]=HASH;
        return d;
    }
    function updateCsrf(res){ if(res&&res.csrf_hash) HASH=res.csrf_hash; }
    function parseErr(xhr){ try{ var r=JSON.parse(xhr.responseText); if(r&&r.message)return r.message; }catch(e){} return null; }

    function doPost(url,data,cb,errCb){
        $.ajax({
            url:BASE+'/'+url,method:'POST',dataType:'json',
            data:$.extend({},csrf(),data),
            headers:{'X-Requested-With':'XMLHttpRequest'},
            success:function(res){ updateCsrf(res); cb(res); },
            error:function(xhr){
                var serverMsg=parseErr(xhr);
                if(xhr.status===403 && !serverMsg){
                    var m='Sesi keamanan kedaluwarsa. Halaman akan di-refresh...';
                    setTimeout(function(){ location.reload(); },2000);
                    errCb?errCb(m):toast(m,'error');
                    return;
                }
                var m=serverMsg||'Terjadi kesalahan server.';
                errCb?errCb(m):toast(m,'error');
            }
        });
    }
    function doGet(url,params,cb){
        if(typeof params==='function'){ cb=params; params={}; }
        $.ajax({
            url:BASE+'/'+url,method:'GET',dataType:'json',data:params,
            headers:{'X-Requested-With':'XMLHttpRequest'},
            success:cb,
            error:function(xhr){
                var m=parseErr(xhr)||'Gagal memuat data.';
                if(xhr.status===401){ m='Sesi telah berakhir. Mengalihkan...'; setTimeout(function(){ location.href=BASE+'/login'; },1500); }
                toast(m,'error');
            }
        });
    }

    function btnLoading(btn){
        var ori=btn.html(); btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled',true);
        return function(){ btn.html(ori).prop('disabled',false); };
    }
    function fmtTanggal(s){
        if(!s)return'-';
        var p=s.replace(/-/g,'/').split('/');
        return p.length===3?p[2]+'/'+p[1]+'/'+p[0]:s;
    }
    function toDateInput(s){ return s?s.replace(/\//g,'-'):''; }
    function fromDateInput(s){ return s?s.replace(/-/g,'/'):''; }
    function timeAgo(s){
        if(!s)return'-';
        var d=new Date(s),diff=Math.floor((Date.now()-d.getTime())/60000);
        if(diff<1)return'Baru saja'; if(diff<60)return diff+' menit lalu'; if(diff<1440)return Math.floor(diff/60)+' jam lalu';
        return('0'+d.getDate()).slice(-2)+'/'+('0'+(d.getMonth()+1)).slice(-2)+'/'+d.getFullYear()+' '+('0'+d.getHours()).slice(-2)+':'+('0'+d.getMinutes()).slice(-2);
    }
    function updateNotifBadge(count){
        var btn=$('#btn-komentar-global'),badge=$('#notif-global');
        count>0?(badge.text(count).show(),btn.addClass('has-unread')):(badge.hide(),btn.removeClass('has-unread'));
    }

    var statusMap={'01':'Pengangkatan Baru','02':'Pengangkatan Kembali','03':'Perubahan Jabatan','04':'Tidak Berubah'};
    var pendMap={'01':'S3','02':'S2','03':'S1','04':'D3','05':'SLTA','99':'Lainnya'};
    var jabMap={
        '110':'Direktur Utama','120':'Direktur yang membawahkan fungsi kepatuhan','130':'Direktur',
        '210':'Komisaris Utama','220':'Komisaris','230':'Komisaris Independen',
        '310':'Komite Audit','320':'Komite Pemantau Resiko','330':'Komite Remunerasi dan Nominasi',
        '340':'Komite Manajemen Resiko','350':'Komite Lainnya',
        '410':'Pejabat Eksekutif Kepatuhan','420':'Pejabat Eksekutif Manajemen Resiko',
        '430':'Pejabat Eksekutif Audit Intern','440':'Pejabat Eksekutif APU PPT','450':'Pejabat Eksekutif Lainnya'
    };
    var statusStyle={'01':'bs-tetap','02':'bs-kontrak','03':'bs-tidak-tetap','04':'bs-magang'};

    function statusBadge(s){ return s?'<span class="bs '+(statusStyle[s]||'bs-lain')+'">'+esc(statusMap[s]||s)+'</span>':'<span class="text-muted">-</span>'; }
    function eduChip(s){ return s?'<span class="chip-edu">'+esc(pendMap[s]||s)+'</span>':'<span class="text-muted">-</span>'; }
    function dataAttrs(r,keys){ return keys.map(function(k){ return ' data-'+k+'="'+esc(r[k])+'"'; }).join(''); }

    // ── Card render
    function renderRows(rows){
        var container=document.getElementById('cards-formasisdm');
        if(!rows||!rows.length){ container.innerHTML=''; $('#empty-state').show(); return; }
        $('#empty-state').hide();

        // Build full HTML in one pass; single DOM write
        var parts=[];
        for(var i=0;i<rows.length;i++){
            var r=rows[i];
            var dOk=parseInt(r.accdir2)===1;
            var accCls=dOk?'acc-badge-ok':'acc-badge-no';
            var accIcon=dOk?'fa-check-circle':'fa-clock';
            var accTxt=dOk?'Disetujui':'Menunggu Persetujuan';
            var dataA=dataAttrs(r,['id','nik','nama','jabatan','status','tingkatpendidikan','masajabatan']);
            var dataH=dataAttrs(r,['id','nik','nama','jabatan','status']);
            var dataK=' data-id="'+esc(r.id)+'" data-nama="'+esc(r.nama)+'" data-jabatan="'+esc(r.jabatan)+'" data-action="'+(dOk?'unapprove':'approve')+'"';

            var s='<div class="sdm-card" id="scard-'+r.id+'">'
                +'<div class="scard-header">'
                +  '<div class="scard-index-wrap">'
                +    '<span class="scard-index">'+(i+1)+'</span>'
                +    '<span class="scard-nik">'+esc(r.nik||'-')+'</span>'
                +  '</div>'
                +  '<div class="scard-actions">';

            if(CAN_KOM){
                s+='<i class="fas '+(dOk?'fa-check-circle acc-ok':'fa-times-circle acc-no')+' btn-toggle-kom scard-acc-icon"'
                  +dataK+' title="'+(dOk?'Batalkan approval':'Setujui')+'"></i>';
            }
            if(CAN_EDIT){
                s+='<button class="btn btn-sm btn-warning btn-ubah scard-btn" title="Ubah"'+dataA+'>'
                  +  '<i class="fas fa-pencil-alt"></i></button>'
                  +'<button class="btn btn-sm btn-danger btn-hapus scard-btn" title="Hapus"'+dataH+'>'
                  +  '<i class="fas fa-trash-alt"></i></button>';
            }

            s+='  </div></div>'
              +'<div class="scard-body">'
              +  '<div class="scard-nama">'+esc(r.nama)+'</div>'
              +  '<div class="scard-jabatan"><i class="fas fa-briefcase"></i>'+esc(jabMap[r.jabatan]||r.jabatan||'-')+'</div>'
              +  '<div class="scard-meta-row">'
              +    '<div class="scard-meta-item"><span class="scard-meta-label"><i class="fas fa-id-badge"></i>Status</span>'+statusBadge(r.status)+'</div>'
              +    '<div class="scard-meta-item"><span class="scard-meta-label"><i class="fas fa-graduation-cap"></i>Pendidikan</span>'+eduChip(r.tingkatpendidikan)+'</div>'
              +    '<div class="scard-meta-item"><span class="scard-meta-label"><i class="fas fa-calendar-alt"></i>Tgl. Pengangkatan</span>'
              +      '<span class="scard-meta-val">'+(r.masajabatan?fmtTanggal(r.masajabatan):'<span class="text-muted">-</span>')+'</span></div>'
              +  '</div>'
              +  '<div class="scard-footer-badge">'
              +    '<span class="scard-acc-badge '+accCls+'"><i class="fas '+accIcon+'"></i> '+accTxt+'</span>'
              +  '</div>'
              +'</div></div>';
            parts.push(s);
        }
        container.innerHTML=parts.join('');
    }

    // Data loading
    function loadData(){
        $('#skeleton-container').show();
        $('#table-wrapper,#empty-state,#error-state').hide();
        $.ajax({
            url:BASE+'/Formasisdm/getData',method:'GET',dataType:'json',
            headers:{'X-Requested-With':'XMLHttpRequest'},
            success:function(res){
                $('#skeleton-container').hide(); $('#table-wrapper').show();
                if(res&&res.status==='success'){ renderRows(res.data||[]); pollUnreadCount(); }
                else{ $('#empty-state').show(); toast((res&&res.message)||'Gagal memuat data','error'); }
            },
            error:function(xhr){
                $('#skeleton-container').hide(); $('#table-wrapper').show();
                var errMap={401:'Sesi berakhir.',403:'Akses ditolak.',404:'Endpoint tidak ditemukan.'};
                var m=errMap[xhr.status]||parseErr(xhr)||'Gagal memuat data dari server.';
                if(xhr.status===401) setTimeout(function(){ location.href=BASE+'/login'; },1500);
                $('#error-state-msg').text(m); $('#error-state').show(); toast(m,'error');
            }
        });
    }

    function refreshApprovalStatus(){
        doGet('Formasisdm/getApprovalStatus',function(res){
            if(!res||res.status!=='success')return;
            updateBadge('#badge-approval-dekom','Direktur Yang Membawahi Fungsi Kepatuhan',res.dekom_approved,res.dekom_approved_at);
        });
    }
    function updateBadge(sel,label,approved,at){
        var bd=$(sel);
        bd.removeClass('badge-status-secondary badge-status-success').addClass(approved?'badge-status-success':'badge-status-secondary');
        var icon=approved?'fa-check-circle':'fa-clock';
        var sub=approved?'Disetujui · '+timeAgo(at):'Belum disetujui seluruhnya';
        bd.html('<i class="fas '+icon+'"></i><div><span class="approval-status-label">'+label+'</span><br><span class="approval-status-sub">'+sub+'</span></div>');
    }

    function loadKomentarList(scrollToBottom){
        var list=$('#komentar-list');
        list.html('<div class="komentar-loading"><i class="fas fa-spinner fa-spin"></i> Memuat...</div>');
        $.ajax({
            url:BASE+'/Formasisdm/getKomentar',method:'GET',
            headers:{'X-Requested-With':'XMLHttpRequest'},
            success:function(res){
                var items=Array.isArray(res)?res:[];
                if(!items.length){ list.html('<div class="komentar-empty"><i class="fas fa-comment-slash fa-2x mb-2 d-block"></i>Belum ada komentar.</div>'); return; }
                var html='';
                for(var i=0;i<items.length;i++){
                    var k=items[i],own=parseInt(k.user_id)===UID;
                    html+='<div class="km-item'+(own?' km-own':'')+'"><div class="km-meta">'
                        +'<span class="km-author"><i class="fas fa-user-circle"></i> '+esc(k.fullname||'?')+(own?' <span class="km-you">Anda</span>':'')+' </span>'
                        +'<span class="km-time"><i class="fas fa-clock"></i> '+timeAgo(k.created_at)+'</span>'
                        +'</div><p class="km-text">'+esc(k.komentar)+'</p></div>';
                }
                list.html(html);
                if(scrollToBottom){ var w=document.getElementById('komentar-list-wrapper'); if(w) requestAnimationFrame(function(){ w.scrollTop=w.scrollHeight; }); }
            },
            error:function(){ list.html('<div class="komentar-empty text-danger"><i class="fas fa-exclamation-circle"></i> Gagal memuat komentar.</div>'); }
        });
    }

    // Poll: combined single request instead of separate approval + unread
    var _pollTimer=null;
    function pollUnreadCount(){
        $.ajax({
            url:BASE+'/Formasisdm/getUnreadCounts',method:'GET',
            headers:{'X-Requested-With':'XMLHttpRequest'},
            success:function(res){ if(res&&res.status==='success') updateNotifBadge(parseInt(res.count)||0); }
        });
    }
    function markAllRead(){
        doPost('Formasisdm/markKomentarAsRead',{},function(res){ if(res&&res.status==='success') updateNotifBadge(0); });
    }

    // Action handlers
    var execTambah=debounce(function(){
        var nik=$.trim($('#t-nik').val()),nama=$.trim($('#t-nama').val()),jabatan=$.trim($('#t-jabatan').val());
        if(!nik||!nama||!jabatan){ $('#tambah-err').text('NIK, Nama Lengkap, dan Jabatan wajib diisi.').show(); return; }
        $('#tambah-err').hide();
        var restore=btnLoading($('#btn-simpan-tambah'));
        doPost('Formasisdm/tambah',{nik:nik,nama:nama,jabatan:jabatan,status:$('#t-status').val(),tingkatpendidikan:$('#t-tingkatpendidikan').val(),masajabatan:fromDateInput($('#t-masajabatan').val())},function(res){
            restore();
            res.status==='success'?($('#modalTambah').modal('hide'),toast(res.message||'Data berhasil ditambahkan'),loadData(),refreshApprovalStatus()):$('#tambah-err').text(res.message||'Gagal menyimpan data').show();
        },function(m){ restore(); $('#tambah-err').text(m).show(); });
    },400);

    var execUbah=debounce(function(){
        var id=$('#u-id').val(),nik=$.trim($('#u-nik').val()),nama=$.trim($('#u-nama').val()),jabatan=$.trim($('#u-jabatan').val());
        if(!nik||!nama||!jabatan){ $('#ubah-err').text('NIK, Nama Lengkap, dan Jabatan wajib diisi.').show(); return; }
        $('#ubah-err').hide();
        var restore=btnLoading($('#btn-simpan-ubah'));
        doPost('Formasisdm/ubah',{id:id,nik:nik,nama:nama,jabatan:jabatan,status:$('#u-status').val(),tingkatpendidikan:$('#u-tingkatpendidikan').val(),masajabatan:$.trim($('#u-masajabatan').val())},function(res){
            restore();
            res.status==='success'?($('#modalUbah').modal('hide'),toast(res.message||'Data berhasil diubah'),loadData(),refreshApprovalStatus()):$('#ubah-err').text(res.message||'Gagal mengubah data').show();
        },function(m){ restore(); $('#ubah-err').text(m).show(); });
    },400);

    var execHapus=debounce(function(){
        var restore=btnLoading($('#btn-konfirmasi-hapus'));
        doPost('Formasisdm/hapus',{id:$('#h-id').val()},function(res){
            restore(); $('#modalHapus').modal('hide');
            res.status==='success'?(toast(res.message||'Data berhasil dihapus'),loadData(),refreshApprovalStatus()):toast(res.message||'Gagal menghapus data','error');
        },function(m){ restore(); toast(m,'error'); });
    },400);

    function makeApproveExec(idSel,actionSel,modalSel,btnSel,urlMap){
        return debounce(function(){
            var id=$(idSel).val(),action=$(actionSel).val();
            var restore=btnLoading($(btnSel));
            doPost(urlMap[action]||urlMap.default,{id:id},function(res){
                restore(); $(modalSel).modal('hide');
                res.status==='success'?(toast(res.message),loadData(),refreshApprovalStatus()):toast(res.message||'Gagal menyimpan approval','error');
            },function(m){ restore(); toast(m,'error'); });
        },400);
    }
    var execApproveKom=makeApproveExec('#ak-id','#ak-action','#modalApproveKom','#btn-konfirmasi-approve-kom',
        {approve:'Formasisdm/approveKomRow',unapprove:'Formasisdm/unapproveKomRow'});

    function bulkApproval(url,msg){
        if(!confirm(msg))return;
        doPost(url,{},function(res){ toast(res.message,res.status==='success'?'success':'error'); loadData(); refreshApprovalStatus(); });
    }

    var execKomentar=debounce(function(){
        var teks=$.trim($('#k-teks').val());
        if(!teks){ toast('Komentar tidak boleh kosong.','error'); return; }
        if(teks.length<3){ toast('Komentar minimal 3 karakter.','error'); return; }
        var restore=btnLoading($('#btn-kirim-komentar'));
        doPost('Formasisdm/tambahKomentar',{komentar:teks,fullname:FNAME},function(res){
            restore();
            if(res.status==='success'){ $('#k-teks').val(''); $('#k-char-count').text('0 / 1000').removeClass('danger'); toast(res.message||'Komentar terkirim'); loadKomentarList(true); }
            else toast(res.message||'Gagal mengirim komentar','error');
        },function(m){ restore(); toast(m,'error'); });
    },400);

    // Event bindings
    $(document).on('click','#btn-show-tambah',function(){
        $('#t-nik,#t-nama,#t-jabatan,#t-masajabatan,#t-status,#t-tingkatpendidikan').val('');
        $('#tambah-err').hide(); $('#modalTambah').modal('show');
        setTimeout(function(){ $('#t-nik').focus(); },450);
    });
    $(document).on('click','.btn-ubah',function(){
        var d=$(this).data();
        $('#u-id').val(d.id); $('#u-nik').val(d.nik); $('#u-nama').val(d.nama);
        $('#u-jabatan').val(d.jabatan); $('#u-status').val(d.status);
        $('#u-tingkatpendidikan').val(d.tingkatpendidikan); $('#u-masajabatan').val(toDateInput(d.masajabatan));
        $('#ubah-err').hide(); $('#modalUbah').modal('show');
        setTimeout(function(){ $('#u-nik').focus(); },450);
    });
    $(document).on('click','.btn-hapus',function(){
        var d=$(this).data();
        $('#h-id').val(d.id); $('#h-nik').text(d.nik||'-'); $('#h-nama').text(d.nama||'-');
        $('#h-jabatan').text(jabMap[d.jabatan]||d.jabatan||'-'); $('#h-status').text(statusMap[d.status]||d.status||'-');
        $('#modalHapus').modal('show');
    });
    $(document).on('click','.btn-toggle-kom',function(){
        var d=$(this).data(),ap=d.action==='approve';
        $('#ak-id').val(d.id); $('#ak-action').val(d.action);
        $('#ak-nama').text(d.nama||'-'); $('#ak-jabatan').text(jabMap[d.jabatan]||d.jabatan||'-');
        $('#ak-btn-label').text(ap?'Setujui':'Batalkan');
        $('#btn-konfirmasi-approve-kom').removeClass('btn-success btn-danger').addClass(ap?'btn-success':'btn-danger');
        $('#ak-msg').html(ap
            ?'<i class="fas fa-check-circle text-success"></i> Anda akan <strong>menyetujui</strong> data ini sebagai <strong>Direktur yang membawahi fungsi kepatuhan</strong>:'
            :'<i class="fas fa-ban text-danger"></i> Anda akan <strong>membatalkan approval</strong> data ini:');
        $('#modalApproveKom').modal('show');
    });

    // State sync (lazily loaded once)
    var _syncChecked = false;
    var _syncData = null;
 
    function syncCheckPrev(cb) {
        if (_syncChecked) { cb && cb(_syncData); return; }
        $.ajax({
            url: BASE + '/Formasisdm/checkPrevPeriode',
            method: 'GET',
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                _syncChecked = true;
                _syncData    = res;
                cb && cb(res);
            },
            error: function() {
                _syncChecked = true;
                _syncData    = { status: 'error' };
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
                _syncData = res;
            } else {
                btn.prop('disabled', true);
                tip.text('Tidak ada data di periode sebelumnya');
            }
        });
 
        btn.on('click', function() {
            if (!_syncData || _syncData.status !== 'found') return;
 
            // Isi modal
            $('#sync-current-label').text(_syncData.current_label || '-');
            $('#sync-prev-label').text(_syncData.prev_label || '-');
 
            var rows = _syncData.rows || [];
            var existingNiks = _syncData.existing_niks || [];
 
            // Buat set NIK yang sudah ada untuk O(1) lookup
            var nikSet = {};
            for (var i = 0; i < existingNiks.length; i++) nikSet[existingNiks[i]] = true;
 
            var newCount = 0;
            var html = '';
            for (var j = 0; j < rows.length; j++) {
                var r = rows[j];
                var skip = !!nikSet[r.nik];
                if (!skip) newCount++;
                html += '<div class="sync-prev-item' + (skip ? ' will-skip' : '') + '">'
                      + '<span class="sync-prev-item-idx">' + (j + 1) + '</span>'
                      + '<div style="flex:1;min-width:0;">'
                      +   '<div class="sync-prev-item-name">' + esc(r.nama) + '</div>'
                      +   '<div class="sync-prev-item-nik">' + esc(r.nik) + ' · ' + esc(jabMap[r.jabatan] || r.jabatan || '-') + '</div>'
                      + '</div>'
                      + (skip ? '<span class="sync-skip-badge">Sudah ada</span>' : '')
                      + '</div>';
            }
 
            $('#sync-count-num').text(newCount);
            $('#sync-preview-list').html(html || '<div style="padding:14px;text-align:center;color:#94a3b8;font-size:13px;">Tidak ada data</div>');
            $('#btn-konfirmasi-sync').prop('disabled', newCount === 0);
 
            $('#modalSyncPeriode').modal('show');
        });
    }

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
            url: BASE + '/Formasisdm/previewImportExcel',
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
                    : r.row_status === 'skip' ? '<span class="badge badge-warning" title="'+esc(r.dup_reason||'')+'">Lewati</span>'
                    : '<span class="badge badge-danger" title="'+esc((r.errors||[]).join(', '))+'">Error</span>';
            var jabTxt = jabMap[r.jabatan] || r.jabatan_label || '-';
            body.append('<tr><td>'+r.line+'</td><td>'+esc(r.nama)+'</td><td>'+esc(r.nik)+'</td><td>'+esc(jabTxt)+'</td><td>'+badge+'</td></tr>');
        });
    }

    $('#btn-simpan-import').on('click', function(){
        var okRows = _importPreview.filter(function(r){ return r.row_status === 'ok'; });
        if (!okRows.length) return;
        var restore = btnLoading($(this));
        doPost('Formasisdm/confirmImportExcel', { rows: JSON.stringify(okRows) }, function(res){
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
 
    var execSync = debounce(function() {
        var restore = btnLoading($('#btn-konfirmasi-sync'));
        var btn = $('#btn-sync-periode');
        btn.addClass('syncing');
        doPost('Formasisdm/syncFromPrevPeriode', {}, function(res) {
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

    $('#btn-komentar-global').on('click',function(){ loadKomentarList(true); markAllRead(); $('#modalKomentar').modal('show'); });
    $('#btn-retry').on('click',function(){ loadData(); refreshApprovalStatus(); });
    $('#btn-simpan-tambah').on('click',execTambah);
    $('#btn-simpan-ubah').on('click',execUbah);
    $('#btn-konfirmasi-hapus').on('click',execHapus);
    $('#btn-konfirmasi-approve-kom').on('click',execApproveKom);
    $('#btn-kirim-komentar').on('click',execKomentar);

    $('#modalTambah,#modalUbah').on('keydown',function(e){
        if(e.key==='Enter'&&!$(e.target).is('textarea')) $(this).is('#modalTambah')?execTambah():execUbah();
    });
    $('#k-teks')
        .on('keydown',function(e){ if(e.ctrlKey&&e.key==='Enter'){ e.preventDefault(); execKomentar(); } })
        .on('input',debounce(function(){ var l=$(this).val().length; var el=$('#k-char-count'); el.text(l+' / 1000'); l>950?el.addClass('danger'):el.removeClass('danger'); },100));

    // Bulk approval buttons – throttled to prevent double-submit
    $(document).on('click','.btn-approve-all-dekom',throttle(function(){ bulkApproval('Formasisdm/approveSemuaKom','Setujui SEMUA data sebagai Direktur Yang Membawahi Fungsi Kepatuhan?'); },2000));
    $(document).on('click','.btn-unapprove-all-dekom',throttle(function(){ bulkApproval('Formasisdm/unapproveSemuaKom','Batalkan SEMUA approval Direktur yang membawahkan fungsi kepatuhan?'); },2000));

    // Clean modal state on close
    $(document).on('hidden.bs.modal','.modal',function(){
        $(this).find('.alert-danger').hide().text('');
        $(this).find('input[type=text],textarea').not('[readonly]').val('');
    });

    loadData();
    refreshApprovalStatus();
    pollUnreadCount();

    _pollTimer=setInterval(function(){ pollUnreadCount(); refreshApprovalStatus(); },15000);

    document.addEventListener('visibilitychange',function(){
        if(document.hidden){ clearInterval(_pollTimer); }
        else { pollUnreadCount(); refreshApprovalStatus(); _pollTimer=setInterval(function(){ pollUnreadCount(); refreshApprovalStatus(); },15000); }
    });
}
</script>