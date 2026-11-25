<?php
$_formType = $formType ?? 'A';
$_isB = ($_formType === 'B');

$_semRaw = strtolower(trim((string) ($periodeDetail['semester'] ?? '')));
$_semNum = in_array($_semRaw, ['ganjil', '1'], true) ? '1' : (in_array($_semRaw, ['genap', '2'], true) ? '2' : '');
$_periodeLabel = ($_semNum !== '' ? 'Semester ' . $_semNum . ' ' : '') . 'Tahun ' . esc($periodeDetail['tahun'] ?? '');
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" media="print" onload="this.media='all'"
    href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">

<div id="toast-wrap" style="position:fixed;top:20px;right:20px;z-index:99999;min-width:290px;pointer-events:none;"></div>

<div class="container-fluid">
    <a href="<?= base_url('Showsaf') ?>" class="btn-kembali mb-3">
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
            <div class="approval-badge-bar mb-3">
                <div id="badge-approval-dekom" class="approval-status-badge badge-status-secondary">
                    <i class="fas fa-clock"></i>
                    <div>
                        <span class="approval-status-label">Dewan Komisaris</span><br>
                        <span class="approval-status-sub">Belum disetujui</span>
                    </div>
                </div>
                <div id="badge-approval-direksi" class="approval-status-badge badge-status-secondary">
                    <i class="fas fa-clock"></i>
                    <div>
                        <span class="approval-status-label">Direktur Utama</span><br>
                        <span class="approval-status-sub">Belum disetujui</span>
                    </div>
                </div>
            </div>
            <h1 class="page-title"><?= ($judul); ?></h1>
            <div class="page-subtitle">
                <?php
                $semDisplay = esc($periodeDetail['semester']);
                $semNum = strtolower($semDisplay) === 'ganjil' ? '1' : (strtolower($semDisplay) === 'genap' ? '2' : $semDisplay);
                ?>
                Semester <?= $semNum ?> Tahun <?= esc($periodeDetail['tahun']); ?>
                <span class="badge-formtype"><?= $_isB ? 'Form 01B - Insidental' : 'Form 01A' ?></span>
            </div>
            <span class="badge-namabpr"><?= esc($bprData['namabpr'] ?? 'Nama BPR'); ?></span>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center"
            style="background: var(--grad);">
            <h5 class="m-0 font-weight-bold text-white">
                <i class="fas fa-file-alt"></i> Penerapan SAF yang mencakup informasi kejadian fraud dan informasi
                pelaku fraud
            </h5>

            <div class="d-flex align-items-center" style="gap:8px;">
                <?php if (!empty($canInput)): ?>
                    <button type="button" class="btn btn-secondary btn-sm" id="btn-nodata">
                        <i class="fas fa-times-circle"></i> Tidak Ada Fraud
                    </button>
                    <!-- <button type="button" class="btn btn-success btn-sm" id="btn-show-import" title="Impor Excel">
                        <i class="fas fa-file-excel"></i> Impor Excel
                    </button> -->
                    <button type="button" class="btn btn-primaryy btn-sm" id="btn-show-tambah" title="Tambah Data">
                        <i class="fas fa-plus"></i>
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="card-body p-3">
            <div id="nodata-banner" class="nodata-box" style="display:none;">
                <div>Tidak ada fraud yang terjadi pada:</div>
                <div>
                    <?= $_periodeLabel ?>.
                </div>
                <?php if (!empty($canInput)): ?>
                    <button type="button" class="btn btn-link btn-sm text-danger p-0 ml-2" id="btn-batal-nodata">
                        Batalkan
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
                        <h6 class="text-muted mb-1">Belum ada data kejadian fraud</h6>
                        <p class="text-muted small mb-3">Data Penerapan SAF untuk periode ini belum diinput.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    $_apr = [
        ['dekom', 'Dewan Komisaris', !empty($canApproveDekom)],
        ['direksi', 'Direktur Utama', !empty($canApproveDireksi)],
    ];
    ?>
    <div class="d-flex justify-content-center flex-wrap mt-2 mb-2" style="gap:16px;">
        <?php foreach ($_apr as [$lv, $lvLabel, $lvCan]):
            if (!$lvCan)
                continue; ?>
            <div class="card shadow-sm action-card" id="card-approve-<?= $lv ?>" style="min-width:220px;">
                <div class="card-body py-2 text-center">
                    <small class="d-block font-weight-bold text-dark mb-2">
                        <i class="fas fa-user-tie text-success"></i> <?= $lvLabel ?>
                    </small>
                    <div class="d-flex justify-content-center flex-wrap" style="gap:4px;">
                        <button type="button" class="btn btn-primary btn-sm btn-approve-all" data-level="<?= $lv ?>">
                            <i class="fas fa-check-double"></i> Setujui
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm btn-unapprove-all"
                            data-level="<?= $lv ?>">
                            <i class="fas fa-ban"></i> Batal
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- MODAL FORM -->
<div class="modal fade" id="modalForm" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header modal-header-sdm">
                <h5 class="modal-title" id="form-modal-title">
                    <i class="fas fa-plus-circle"></i> Tambah Data Kejadian Fraud
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="f-id">
                <div class="form-section-title"><i class="fas fa-info-circle"></i> Kejadian Fraud</div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Kejadian Fraud Menurut Pelaku <span
                                    class="text-danger">*</span></label>
                            <select class="form-control" id="f-kejadianfraud">
                                <option value="">-- Pilih --</option>
                                <option value="A">Pelaku Internal</option>
                                <option value="B">Pelaku Eksternal</option>
                                <option value="C">Pelaku Internal &amp; Eksternal</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">ID Kejadian Fraud <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="f-idkejadian" maxlength="13"
                                placeholder="Cth: A12024000001" autocomplete="off">
                            <small class="form-text text-muted">Maks 12 karakter huruf/angka. Baris lanjutan kejadian
                                yang sama pakai ID yang sama.</small>
                        </div>
                    </div>
                </div>

                <div class="form-section-title"><i class="fas fa-search"></i> Jenis, Aktivitas &amp;
                    Deskripsi Fraud</div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Jenis Fraud <span class="text-danger">*</span></label>
                            <select class="form-control" id="f-jenisfraud">
                                <option value="">-- Pilih jenis fraud --</option>
                                <option value="201">Pemerasan (Korupsi)</option>
                                <option value="202">Benturan kepentingan yang merugikan LJK dan/atau Konsumen (Korupsi)
                                </option>
                                <option value="203">Penyuapan (Korupsi)</option>
                                <option value="204">Penerimaan tidak sah (Korupsi)</option>
                                <option value="301">Penyalahgunaan uang tunai (Penyalahgunaan aset)</option>
                                <option value="302">Penyalahgunaan persediaan (Penyalahgunaan aset)</option>
                                <option value="303">Penyalahgunaan aset lainnya (Penyalahgunaan aset)</option>
                                <option value="401">Kecurangan laporan keuangan meliputi melebihkan atau mengurangi
                                    kekayaan bersih dan/atau pendapatan bersih yang berdampak pada ketidaksesuaian
                                    informasi dengan kondisi sebenarnya (Kecurangan laporan keuangan)</option>
                                <option value="501">Penipuan</option>
                                <option value="601">Pembocoran informasi rahasia</option>
                                <option value="701">Tindakan lain yang dapat dipersamakan dengan Fraud</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Aktivitas Terkait Fraud <span
                                    class="text-danger">*</span></label>
                            <select class="form-control" id="f-aktivitasfraud">
                                <option value="">-- Pilih aktivitas --</option>
                                <option value="301">Pendanaan</option>
                                <option value="302">Perkreditan/pembiayaan</option>
                                <option value="303">Penggunaan identitas dan data orang, pihak lain, atau konsumen</option>
                                <option value="304">Pengelolaan aset /investasi</option>
                                <option value="305">Penggunaan siber</option>
                                <option value="306">Pembukuan dan penyajian laporan keuangan</option>
                                <option value="307">Anti pencucian uang (APU), pencegahan pendanaan terorisme (PPT) dan
                                    pencegahan pendanaan proliferasi senjata pemusnah massal (PPPSPM)</option>
                                <option value="308">Transaksi efek</option>
                                <option value="309">Pemasaran</option>
                                <option value="310">Kustodian</option>
                                <option value="311">Penjatahan efek</option>
                                <option value="312">Due diligence penjaminan emisi efek</option>
                                <option value="313">Riset investasi</option>
                                <option value="314">Proses underwriting</option>
                                <option value="315">Pengelolaan iuran/premi/kontribusi/imbalan jasa penjaminan/kafalah</option>
                                <option value="316">Pengurusan klaim/manfaat pensiun</option>
                                <option value="317">Penilaian kerugian asuransi</option>
                                <option value="318">Proses pemilihan asuransi/reasuransi</option>
                                <option value="319">Pengelolaan surplus underwriting</option>
                                <option value="320">Pengelolaan data kepesertaan</option>
                                <option value="321">Proses subrogasi</option>
                                <option value="322">Pemberian jasa manajemen</option>
                                <option value="323">Layanan pendanaan bersama berbasis teknologi informasi</option>
                                <option value="324">Bullion</option>
                                <option value="325">Sekuritisasi</option>
                                <option value="326">Pendukung Pasar</option>
                                <option value="327">Aktivitas terkait Aset Keuangan Digital, termasuk Aset Kripto</option>
                                <option value="399">Aktivitas lain</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-12" id="wrap-ketjenisfraud" hidden>
                        <div class="form-group">
                            <label class="font-weight-bold">Keterangan Jenis Fraud <span
                                    class="text-danger">*</span></label>
                            <textarea class="form-control" id="f-ketjenisfraud" rows="4" maxlength="500"
                                placeholder="Wajib diisi karena Jenis Fraud = 701 (Tindakan lain)"></textarea>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="font-weight-bold">Deskripsi Fraud / Modus Operandi</label>
                            <textarea class="form-control" id="f-descfraud" rows="5" maxlength="4000"
                                placeholder="Uraian singkat modus operandi..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-section-title"><i class="fas fa-map-marker-alt"></i> Lokasi &amp;
                    Divisi</div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Lokasi Fraud <span class="text-danger">*</span></label>
                            <select class="form-control" id="f-lokfraud">
                                <option value="">-- Pilih lokasi fraud --</option>
                                <option value="01">Kantor Pusat Operasional</option>
                                <option value="02">Kantor Pusat Non Operasional</option>
                                <option value="03">Kantor Cabang LJK yang berkedudukan di Luar Negeri</option>
                                <option value="04">Kantor Wilayah</option>
                                <option value="05">Kantor Cabang (Dalam Negeri)</option>
                                <option value="06">Kantor cabang dari bank yang berada di Luar Negeri</option>
                                <option value="07">Kantor Cabang Pembantu LJK yang berkedudukan di Luar Negeri</option>
                                <option value="08">Kantor Cabang Pembantu (Dalam Negeri)</option>
                                <option value="09">Kantor Cabang Pembantu (Luar Negeri)</option>
                                <option value="10">Kantor Kas</option>
                                <option value="11">Kantor Fungsional/ Kantor Selain Kantor Cabang/ Kantor Pemasaran
                                    Reksadana/Gerai/Unit Layanan (Outlet)</option>
                                <option value="12">Payment Point</option>
                                <option value="13">Kas Keliling/Kas Mobil/Kas Terapung</option>
                                <option value="14">Kantor Perwakilan LJK yang berkedudukan di Luar Negeri</option>
                                <option value="15">Automatic Teller Machine/Cash Deposit Machine/Cash Recycling Machine
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Keterangan Lokasi <span class="text-danger">*</span></label>
                            <select class="form-control" id="f-ketlokfraud">
                                <option value="">-- Pilih keterangan lokasi --</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="font-weight-bold">Divisi / Unit Kerja / Lini Bisnis</label>
                            <input type="text" class="form-control" id="f-divisifraud" maxlength="250"
                                placeholder="Cth: Kartu Kredit">
                        </div>
                    </div>
                </div>

                <div class="form-section-title"><i class="fas fa-balance-scale"></i> Pihak Dirugikan,
                    Waktu &amp; Kerugian</div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="font-weight-bold">Pihak Yang Dirugikan <span
                                    class="text-danger">*</span></label>
                            <select class="form-control" id="f-pihakrugi">
                                <option value="">-- Pilih pihak yang dirugikan--</option>
                                <option value="001">Lembaga Jasa Keuangan (LJK)</option>
                                <option value="002">Konsumen</option>
                                <option value="003">Pihak Lain</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="font-weight-bold">Waktu Terjadi (Awal)</label>
                            <input type="date" class="form-control" id="f-wterjadiawal">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="font-weight-bold">Waktu Terjadi (Akhir)</label>
                            <input type="date" class="form-control" id="f-wterjadiakhir">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="font-weight-bold">Waktu ketika Fraud diketahui</label>
                            <input type="date" class="form-control" id="f-wfrauddiketahui">
                        </div>
                    </div>
                </div>

                <?php if ($_isB): ?>
                    <div class="kerugian-box">
                        <div class="kerugian-box-title">Jumlah Kerugian Potensial (Rp) &ndash; Total Keseluruhan</div>
                        <div class="kerugian-note">
                            <i class="fas fa-info-circle"></i>
                            <span>Wajib diisi. Jika tidak ada kerugian potensial, isi <strong>0</strong>.</span>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group mb-0">
                                    <label class="small font-weight-bold">Total Kerugian Potensial <span
                                            class="text-danger">*</span></label>
                                    <div class="money-wrap"><input type="text" inputmode="numeric"
                                            class="form-control money" id="f-rugipotensitotal" placeholder="0"
                                            autocomplete="off"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="kerugian-box">
                        <div class="kerugian-box-title">Jumlah Kerugian (Rp) &ndash; isi sesuai Pihak Yang Dirugikan</div>
                        <div class="kerugian-note">
                            <i class="fas fa-info-circle"></i>
                            <span>
                                Kolom <strong>LJK</strong> wajib diisi. Jika tidak ada kerugian, isi <strong>0</strong>.
                                Kolom <strong>Konsumen</strong> dan <strong>Pihak Lain</strong> bersifat opsional;
                                jika dikosongkan akan dikirim kosong pada file TXT.
                                <em>Riil</em> = kerugian yang benar-benar terjadi,
                                <em>Potensial</em> = perkiraan kerugian yang dapat timbul,
                                <em>Recovery</em> = jumlah yang berhasil dipulihkan.
                            </span>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <label class="small font-weight-bold">LJK &ndash; Riil <span
                                        class="text-danger">*</span></label>
                                <div class="money-wrap"><input type="text" inputmode="numeric" class="form-control money"
                                        id="f-rugiljkriil" placeholder="0" autocomplete="off"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="small font-weight-bold">LJK &ndash; Potensial <span
                                        class="text-danger">*</span></label>
                                <div class="money-wrap"><input type="text" inputmode="numeric" class="form-control money"
                                        id="f-rugiljkpot" placeholder="0" autocomplete="off"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="small font-weight-bold">LJK &ndash; Recovery <span
                                        class="text-danger">*</span></label>
                                <div class="money-wrap"><input type="text" inputmode="numeric" class="form-control money"
                                        id="f-rugiljkrec" placeholder="0" autocomplete="off"></div>
                            </div>

                            <div class="col-md-4 mt-2">
                                <label class="small font-weight-bold">Konsumen &ndash; Riil</label>
                                <div class="money-wrap"><input type="text" inputmode="numeric" class="form-control money"
                                        id="f-rugikonsumenriil" placeholder="0" autocomplete="off"></div>
                            </div>
                            <div class="col-md-4 mt-2">
                                <label class="small font-weight-bold">Konsumen &ndash; Potensial</label>
                                <div class="money-wrap"><input type="text" inputmode="numeric" class="form-control money"
                                        id="f-rugikonsumenpot" placeholder="0" autocomplete="off"></div>
                            </div>
                            <div class="col-md-4 mt-2">
                                <label class="small font-weight-bold">Konsumen &ndash; Recovery</label>
                                <div class="money-wrap"><input type="text" inputmode="numeric" class="form-control money"
                                        id="f-rugikonsumenrec" placeholder="0" autocomplete="off"></div>
                            </div>

                            <div class="col-md-4 mt-2">
                                <label class="small font-weight-bold">Pihak Lain &ndash; Riil</label>
                                <div class="money-wrap"><input type="text" inputmode="numeric" class="form-control money"
                                        id="f-rugilainriil" placeholder="0" autocomplete="off"></div>
                            </div>
                            <div class="col-md-4 mt-2">
                                <label class="small font-weight-bold">Pihak Lain &ndash; Potensial</label>
                                <div class="money-wrap"><input type="text" inputmode="numeric" class="form-control money"
                                        id="f-rugilainpot" placeholder="0" autocomplete="off"></div>
                            </div>
                            <div class="col-md-4 mt-2">
                                <label class="small font-weight-bold">Pihak Lain &ndash; Recovery</label>
                                <div class="money-wrap"><input type="text" inputmode="numeric" class="form-control money"
                                        id="f-rugilainrec" placeholder="0" autocomplete="off"></div>
                            </div>
                        </div>
                        <div class="kerugian-total">
                            <span class="kt-item"><span class="kt-label">Total Riil</span><strong
                                    id="tot-riil">Rp0</strong></span>
                            <span class="kt-item"><span class="kt-label">Total Potensial</span><strong
                                    id="tot-pot">Rp0</strong></span>
                            <span class="kt-item"><span class="kt-label">Total Recovery</span><strong
                                    id="tot-rec">Rp0</strong></span>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($_isB): ?>
                    <div class="form-section-title"><i class="fas fa-tools"></i> Tindak Lanjut LJK</div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="font-weight-bold">Tindak Lanjut LJK <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="f-tindaklanjutljk" rows="5" maxlength="2000"
                                    placeholder="Uraikan tindak lanjut yang dilakukan LJK atas kejadian ini..."></textarea>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="font-weight-bold">Keterangan Tindak Lanjut LJK</label>
                                <textarea class="form-control" id="f-kettindaklanjutljk" rows="3" maxlength="1000"
                                    placeholder="Keterangan tambahan (opsional)"></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Target Waktu Pelaksanaan <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="f-targetpelaksanaan" maxlength="100">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Realisasi Pelaksanaan <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="f-realpelaksanaan" maxlength="100">
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="form-section-title"><i class="fas fa-tools"></i> Kelemahan &amp; Tindakan
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="font-weight-bold">Kelemahan Penyebab Fraud <span
                                        class="text-danger">*</span></label>
                                <select class="form-control" id="f-kelemahanfraud">
                                    <option value="">-- Pilih kelemahan penyebab fraud --</option>
                                    <option value="101">Sumber Daya Manusia - Integritas</option>
                                    <option value="102">Sumber Daya Manusia - Kompetensi</option>
                                    <option value="201">Sistem Pengendalian internal - Pengendalian internal Pimpinan
                                    </option>
                                    <option value="202">Sistem Pengendalian internal - Pada Kebijakan internal LJK</option>
                                    <option value="203">Sistem Pengendalian internal - Ketidaksesuaian atas Tingkat dan
                                        Toleransi Risiko</option>
                                    <option value="204">Sistem Pengendalian internal - Pelanggaran Standar dan Prosedur LJK
                                    </option>
                                    <option value="205">Sistem Pengendalian internal - Tidak Berjalannya Pemisahan Fungsi
                                        (Four Eyes Principle)</option>
                                    <option value="206">Sistem Pengendalian internal - Pelaporan Keuangan dan Kegiatan
                                        Operasional yang Tidak Akurat dan Tidak Tepat Waktu</option>
                                    <option value="207">Sistem Pengendalian internal - Struktur Organisasi yang Belum
                                        Berjalan Efektif</option>
                                    <option value="301">Teknologi Informasi</option>
                                    <option value="401">Penerapan Strategi Anti Fraud Belum Berjalan Efektif</option>
                                    <option value="501">Eksternal - Kelalaian Konsumen</option>
                                    <option value="502">Eksternal - Pemahaman Konsumen menjaga Kerahasiaan Data Pribadi
                                    </option>
                                    <option value="503">Eksternal - Kecurangan Konsumen</option>
                                    <option value="504">Eksternal - Kecurangan Pihak Lain</option>
                                    <option value="901">Kelemahan Lain</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-12" id="wrap-ketkelemahanfraud" hidden>
                            <div class="form-group">
                                <label class="font-weight-bold">Keterangan Kelemahan <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="f-ketkelemahanfraud" maxlength="1000"
                                    placeholder="Wajib diisi karena Kelemahan Penyebab Fraud = 901 (Kelemahan Lain)">
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="font-weight-bold">Tindakan Penanganan Fraud <span
                                        class="text-danger">*</span></label>
                                <select class="form-control" id="f-tindakpenanganan">
                                    <option value="">-- Pilih tindak penanganan fraud --</option>
                                    <option value="01">Pemberian surat peringatan</option>
                                    <option value="02">Rotasi atau mutasi</option>
                                    <option value="03">Penurunan jabatan</option>
                                    <option value="04">Pengunduran diri sukarela</option>
                                    <option value="05">Pemutusan hubungan kerja</option>
                                    <option value="06">Pemblokiran kartu debit/kartu kredit</option>
                                    <option value="07">Pemblokiran rekening</option>
                                    <option value="08">Penggantian kartu debit/kartu kredit</option>
                                    <option value="09">Pelaporan kepolisian atau tindakan hukum</option>
                                    <option value="10">Ganti rugi</option>
                                    <option value="11">Pembatalan polis/kontrak</option>
                                    <option value="12">Pencatatan dalam track record</option>
                                    <option value="13">Pelaporan kepada asosiasi/regulator/instansi</option>
                                    <option value="19">Tindakan Lain</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-12" id="wrap-kettindakpenanganan" hidden>
                            <div class="form-group">
                                <label class="font-weight-bold">Keterangan Tindakan Untuk Penanganan Fraud
                                    <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="f-kettindakpenanganan" maxlength="1000"
                                    placeholder="Wajib diisi karena Tindakan Penanganan = 19 (Tindakan Lain)">
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="font-weight-bold">Tindakan Perbaikan <span
                                        class="text-danger">*</span></label>
                                <select class="form-control" id="f-tindakperbaikan">
                                    <option value="">-- Pilih tindak perbaikan fraud --</option>
                                    <option value="100">Sumber daya manusia</option>
                                    <option value="200">Sistem pengendalian internal</option>
                                    <option value="300">Teknologi informasi</option>
                                    <option value="400">Penerapan Strategi Anti Fraud</option>
                                    <option value="500">Koordinasi dengan asosiasi/regulator/instansi</option>
                                    <option value="900">Tindakan lain</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-12" id="wrap-kettindakperbaikan">
                            <div class="form-group">
                                <label class="font-weight-bold">Keterangan Tindakan Perbaikan
                                    <span class="text-danger" id="star-kettindakperbaikan" hidden>*</span></label>
                                <input type="text" class="form-control" id="f-kettindakperbaikan" maxlength="1000"
                                    placeholder="Keterangan Mengenai Tindakan Perbaikan yang dilakukan pasca fraud">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Target Waktu Pelaksanaan <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="f-targetpelaksanaan" maxlength="100">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Realisasi Pelaksanaan <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="f-realpelaksanaan" maxlength="100">
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="form-section-title"><i class="fas fa-user-secret"></i> Data Pelaku Fraud</div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="font-weight-bold">Pelaku berasal dari <span
                                    class="text-danger">*</span></label>
                            <select class="form-control" id="f-pelakufraud">
                                <option value="">-- Pilih --</option>
                                <option value="001">Internal</option>
                                <option value="002">Eksternal</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label class="font-weight-bold">Nama Pelaku <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="f-namapelaku" maxlength="50">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="font-weight-bold">Jenis Identitas <span class="text-danger">*</span></label>
                            <select class="form-control" id="f-jenisidentitas">
                                <option value="">-- Pilih --</option>
                                <option value="001">KTP (NIK)</option>
                                <option value="002">Paspor</option>
                                <option value="003">NPWP</option>
                                <option value="009">Tidak diketahui</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="font-weight-bold">Nomor Identitas</label>
                            <input type="text" class="form-control" id="f-noidentitas" maxlength="50">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="font-weight-bold">Jenis Kelamin</label>
                            <select class="form-control" id="f-jeniskelamin">
                                <option value="">-- Pilih --</option>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="font-weight-bold">Tanggal Lahir</label>
                            <input type="date" class="form-control" id="f-tanggallahir">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Alamat Identitas</label>
                            <textarea class="form-control" id="f-alamatidentitas" rows="4" maxlength="500"></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Alamat Domisili</label>
                            <textarea class="form-control" id="f-alamatdomisili" rows="4" maxlength="500"></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Tempat Lahir</label>
                            <input type="text" class="form-control" id="f-tempatlahir" maxlength="150">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Status Pelaku <span class="text-danger">*</span></label>
                            <select class="form-control" id="f-statuspelaku">
                                <option value="">-- Pilih status--</option>
                                <option value="001">Pelaku Utama</option>
                                <option value="002">Pelaku Terlibat</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="font-weight-bold">Jabatan Saat Fraud Terjadi <span
                                    class="text-danger">*</span></label>
                            <select class="form-control" id="f-jabatanterjadi">
                                <option value="">-- Pilih jabatan--</option>
                                <option value="001">Direktur Utama/Ketua Pengurus</option>
                                <option value="002">Direktur / Pengurus</option>
                                <option value="003">Direktur Kepatuhan/Pengurus bidang Kepatuhan</option>
                                <option value="004">Komisaris Utama/Ketua Dewan Pengawas</option>
                                <option value="005">Komisaris/Dewan Pengawas</option>
                                <option value="006">Dewan Pengawas Syariah</option>
                                <option value="007">Pejabat Eksekutif</option>
                                <option value="008">Pemegang Saham Pengendali</option>
                                <option value="009">Pemegang Saham</option>
                                <option value="010">Tenaga Ahli dan Konsultan</option>
                                <option value="011">Komisaris Independen / Dewan Pengawas Independen</option>
                                <option value="012">Pejabat non Pejabat Eksekutif</option>
                                <option value="019">Pegawai non Pejabat</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="font-weight-bold">Keterangan Jabatan (Saat Terjadi)</label>
                            <input type="text" class="form-control" id="f-ketjabatanterjadi" maxlength="100">
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="font-weight-bold">Jabatan Saat Fraud Diketahui <span
                                    class="text-danger">*</span></label>
                            <select class="form-control" id="f-jabatandiketahui">
                                <option value="">-- Pilih jabatan--</option>
                                <option value="001">Direktur Utama/Ketua Pengurus</option>
                                <option value="002">Direktur / Pengurus</option>
                                <option value="003">Direktur Kepatuhan/Pengurus bidang Kepatuhan</option>
                                <option value="004">Komisaris Utama/Ketua Dewan Pengawas</option>
                                <option value="005">Komisaris/Dewan Pengawas</option>
                                <option value="006">Dewan Pengawas Syariah</option>
                                <option value="007">Pejabat Eksekutif</option>
                                <option value="008">Pemegang Saham Pengendali</option>
                                <option value="009">Pemegang Saham</option>
                                <option value="010">Tenaga Ahli dan Konsultan</option>
                                <option value="011">Komisaris Independen / Dewan Pengawas Independen</option>
                                <option value="012">Pejabat non Pejabat Eksekutif</option>
                                <option value="019">Pegawai non Pejabat</option>
                                <option value="041">Pensiun Karir</option>
                                <option value="042">Pensiun Dini / Disabilitas</option>
                                <option value="043">Diberhentikan atas keinginan sendiri</option>
                                <option value="044">Berakhir masa kontrak / penugasan</option>
                                <option value="045">Meninggal Dunia</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="font-weight-bold">Keterangan Jabatan (Saat Diketahui)</label>
                            <input type="text" class="form-control" id="f-ketjabatanketahui" maxlength="100">
                        </div>
                    </div>
                    <div class="col-md-12" id="wrap-keteranganpelaku" hidden>
                        <div class="form-group">
                            <label class="font-weight-bold">Keterangan Pelaku <span class="text-danger">*</span></label>
                            <select class="form-control" id="f-keteranganpelaku">
                                <option value="">-- Pilih Keterangan--</option>
                                <option value="001">Konsumen</option>
                                <option value="002">Pihak yang bekerjasama dengan LJK (antara lain vendor, investor,
                                    supplier, pejabat negara, atau rekanan)</option>
                                <option value="003">Pihak yang tidak berhubungan langsung dengan LJK</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section-title"><i class="fas fa-gavel"></i> Status Penanganan</div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Pengenaan Sanksi</label>
                            <input type="text" class="form-control" id="f-pengenaansanksi" maxlength="200">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Status Penanganan <span class="text-danger">*</span></label>
                            <select class="form-control" id="f-statuspenanganan">
                                <option value="">-- Pilih Keterangan--</option>
                                <option value="001">Proses internal LJK</option>
                                <option value="002">Selesai diproses internal LJK</option>
                                <option value="003">Dalam proses penanganan aparat penegak hukum</option>
                                <option value="004">Berkekuatan hukum tetap (Inkracht)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="alert alert-warning mb-0" id="form-ubah-warning" style="display:none;">
                    <small><i class="fas fa-info-circle"></i>
                        <strong>Perhatian:</strong> Mengubah data akan me-reset status approval.
                    </small>
                </div>
                <div id="form-err" class="alert alert-danger mt-2" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="button" class="btn btn-primary" id="btn-simpan-form">
                    <i class="fas fa-save"></i> Simpan Data
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
                        <th width="40%">ID Kejadian</th>
                        <td id="h-idkejadian"></td>
                    </tr>
                    <tr>
                        <th>Nama Pelaku</th>
                        <td id="h-namapelaku"></td>
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

<!-- MODAL APPROVE (Dewan Komisaris / Direksi) -->
<div class="modal fade" id="modalApproveKom" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#155724;color:white;">
                <h5 class="modal-title"><i class="fas fa-user-tie"></i> <span id="ak-title">Approval</span></h5>
                <button type="button" class="close" style="color:white;" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="ak-id">
                <input type="hidden" id="ak-action">
                <input type="hidden" id="ak-level">
                <p id="ak-msg" class="mb-3"></p>
                <table class="table table-bordered table-sm">
                    <tr>
                        <th width="40%">ID Kejadian</th>
                        <td id="ak-idkejadian"></td>
                    </tr>
                    <tr>
                        <th>Nama Pelaku</th>
                        <td id="ak-namapelaku"></td>
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

<!-- MODAL IMPORT EXCEL -->
<div class="modal fade" id="modalImport" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header modal-header-sdm">
                <h5 class="modal-title"><i class="fas fa-file-excel"></i> Impor Data dari Excel</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="import-step-upload">
                    <?php if ($_isB): ?>
                        <p class="small text-muted mb-2">
                            Gunakan template resmi form <strong>01B</strong> (Template SAFT Insidental). Data diisi mulai
                            baris
                            ke-6, kolom C (Kejadian Fraud Menurut Pelaku) s.d. kolom terakhir (Status Penanganan).
                            <a href="<?= base_url('assets/template/Template_SAFT_01B.xlsm') ?>">Unduh template</a>.
                        </p>
                    <?php else: ?>
                        <p class="small text-muted mb-2">
                            Gunakan template resmi form <strong>01A</strong> (Template SAFT). Data diisi mulai baris ke-6,
                            kolom C (Kejadian Fraud Menurut Pelaku) s.d. kolom AW (Status Penanganan).
                            <a href="<?= base_url('assets/template/Template_SAFT_01A.xlsm') ?>">Unduh template</a>.
                        </p>
                    <?php endif; ?>
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
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>ID Kejadian</th>
                                    <th>Jenis Fraud</th>
                                    <th>Pelaku</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Batal
                </button>
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
        --sh-sm: 0 1px 3px rgba(0, 0, 0, .08), 0 1px 2px rgba(0, 0, 0, .06);
        --sh: 0 4px 6px -1px rgba(0, 0, 0, .1), 0 2px 4px -1px rgba(0, 0, 0, .06);
        --sh-lg: 0 10px 25px -3px rgba(0, 0, 0, .1), 0 4px 6px -2px rgba(0, 0, 0, .05);
        --tr: all .2s cubic-bezier(.4, 0, .2, 1);
        --grad: linear-gradient(135deg, #141863 0%, #1f2aa8 100%);
    }

    /* Selector spesifik, bukan `*`, agar tidak memaksa style recalc seluruh DOM */
    body,
    .btn,
    .modal,
    .card,
    input,
    select,
    textarea,
    label,
    h1,
    h5,
    h6,
    p,
    span,
    div {
        font-family: 'Plus Jakarta Sans', system-ui, -apple-system, 'Segoe UI', sans-serif;
    }

    [hidden] {
        display: none !important;
    }

    .page-header {
        text-align: center;
        padding: 32px 20px 24px;
        background: white;
        border-bottom: 1px solid var(--gray-100);
        position: relative;
        overflow: hidden;
    }

    .page-header::before,
    .page-header::after {
        content: '';
        position: absolute;
        border-radius: 50%;
    }

    .page-header::before {
        top: -60px;
        right: -60px;
        width: 200px;
        height: 200px;
        background: radial-gradient(circle, #eef2ff 0%, transparent 70%);
    }

    .page-header::after {
        bottom: -40px;
        left: -40px;
        width: 160px;
        height: 160px;
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

    .badge-formtype {
        display: inline-block;
        margin-left: 8px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .5px;
        background: var(--brand-bg);
        color: var(--brand-dark);
        padding: 3px 10px;
        border-radius: 100px;
        vertical-align: middle;
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
        box-shadow: 0 4px 12px rgba(79, 70, 229, .3);
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

    .approval-status-badge i {
        font-size: 18px;
        flex-shrink: 0;
    }

    .approval-status-label {
        font-weight: 700;
        font-size: 12px;
    }

    .approval-status-sub {
        font-size: 11px;
        opacity: .8;
    }

    .badge-status-secondary {
        background: var(--gray-100);
        color: var(--gray-500);
        border-color: var(--gray-200);
    }

    .badge-status-success {
        background: var(--success-bg);
        color: #065f46;
        border-color: #6ee7b7;
    }

    .action-card {
        border-radius: var(--r) !important;
        border: 1.5px solid var(--gray-200) !important;
        transition: var(--tr);
        overflow: hidden;
    }

    .action-card:hover {
        border-color: var(--brand-light) !important;
        box-shadow: 0 8px 24px rgba(79, 70, 229, .12) !important;
        transform: translateY(-3px);
    }

    .card-header {
        border-radius: var(--r-lg) var(--r-lg) 0 0 !important;
    }

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

    .skel-card-body {
        padding: 16px;
    }

    .ketentuan-cards-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 16px;
        padding: 4px 2px;
    }

    .ketentuan-card {
        background: white;
        border-radius: var(--r-lg);
        box-shadow: var(--sh-sm);
        border: 1.5px solid var(--gray-100);
        overflow: hidden;
        transition: box-shadow .2s, border-color .2s, transform .2s;
        display: flex;
        flex-direction: column;
        content-visibility: auto;
        contain-intrinsic-size: 480px;
    }

    .ketentuan-card:hover {
        box-shadow: var(--sh-lg);
        border-color: var(--brand-light);
        transform: translateY(-3px);
    }

    .kcard-judul {
        font-size: 18px;
        font-weight: 700;
        color: var(--gray-900);
        line-height: 1.4;
        padding-bottom: 8px;
        border-bottom: 1px solid var(--gray-100);
    }

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
        background: rgba(255, 255, 255, .2);
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
        color: rgba(255, 255, 255, .9);
        font-size: 16px;
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

    .kcard-acc-icon.acc-ok {
        opacity: 1;
        color: #6ee7b7;
    }

    .kcard-acc-icon.acc-no {
        opacity: .5;
        color: white;
    }

    .kcard-acc-icon:hover {
        transform: scale(1.25);
        opacity: 1;
    }

    .kcard-lv-wrap {
        display: inline-flex;
        align-items: center;
        gap: 3px;
    }

    .kcard-lv {
        color: rgba(255, 255, 255, .85);
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .4px;
    }

    .kcard-incomplete {
        background: var(--warning-bg);
        color: #92400e;
        border-radius: 100px;
        padding: 2px 9px;
        font-size: 11px;
        font-weight: 700;
        white-space: nowrap;
    }

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

    .kcard-body {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px 24px;
        align-items: start;
        padding: 18px 18px 20px;
    }

    .kcard-col {
        display: flex;
        flex-direction: column;
        gap: 10px;
        min-width: 0;
        padding-left: 14px;
        border-left: 3px solid var(--gray-200);
    }

    .kcard-body .kcard-col:nth-of-type(1) {
        border-left-color: var(--brand-light);
    }

    .kcard-body .kcard-col:nth-of-type(2) {
        border-left-color: var(--warning);
    }

    .kcard-body .kcard-col:nth-of-type(3) {
        border-left-color: var(--info);
    }

    .kcard-body .kcard-col:nth-of-type(4) {
        border-left-color: #a855f7;
    }

    .kcard-col-full {
        grid-column: 1 / -1;
        border-left: none;
        padding-left: 0;
        padding-top: 6px;
        border-top: 1px dashed var(--gray-200);
    }

    .kcard-col-title {
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .6px;
        color: var(--brand-dark);
        background: var(--brand-bg);
        padding: 4px 10px;
        border-radius: 100px;
        display: inline-block;
        width: fit-content;
    }

    .kcard-meta-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
        gap: 10px 14px;
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

    .kcard-meta-label i {
        margin-right: 3px;
    }

    .kcard-meta-val {
        font-size: 13px;
        font-weight: 600;
        color: var(--gray-700);
    }

    .kcard-meta-val.kcard-meta-empty {
        color: var(--gray-400);
        font-weight: 500;
        font-style: italic;
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

    .acc-badge-ok {
        background: var(--success-bg);
        color: #065f46;
        border-color: #a7f3d0;
    }

    .acc-badge-no {
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

    .kcard-ket-label i {
        margin-right: 4px;
    }

    .kcard-ket-text {
        font-size: 13.5px;
        color: var(--gray-700);
        line-height: 1.6;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .form-section-title {
        font-size: 12.5px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: var(--brand-dark);
        background: var(--brand-bg);
        padding: 8px 12px;
        border-radius: var(--r-sm);
        margin: 18px 0 12px;
    }

    .form-section-title:first-of-type {
        margin-top: 4px;
    }

    .form-section-title i {
        margin-right: 6px;
    }

    .section-save-bar {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        margin: -4px 0 6px;
    }

    .section-save-state {
        font-size: 12px;
        font-weight: 600;
        color: var(--gray-400);
    }

    .section-save-state.ok {
        color: #065f46;
    }

    .section-save-state.err {
        color: var(--danger);
    }

    .kerugian-box {
        background: var(--gray-50);
        border: 1.5px dashed var(--gray-200);
        border-radius: var(--r);
        padding: 14px 16px;
        margin-bottom: 4px;
    }

    .kerugian-box-title {
        font-size: 12px;
        font-weight: 700;
        color: var(--gray-500);
        text-transform: uppercase;
        letter-spacing: .5px;
        margin-bottom: 10px;
    }

    .kerugian-total {
        margin-top: 12px;
        padding-top: 10px;
        border-top: 1px solid var(--gray-200);
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 8px 22px;
    }

    .kt-item {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        min-width: 92px;
    }

    .kt-label {
        font-size: 10.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .6px;
        color: var(--gray-400);
    }

    .kt-item strong {
        font-size: 14px;
        color: var(--brand-dark);
        font-variant-numeric: tabular-nums;
    }

    /* Prefix "Rp" tanpa elemen DOM tambahan */
    .money-wrap {
        position: relative;
    }

    .money-wrap::before {
        content: 'Rp';
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 12px;
        font-weight: 700;
        color: var(--gray-400);
        pointer-events: none;
    }

    .money-wrap .form-control {
        padding-left: 34px !important;
        text-align: right;
        font-variant-numeric: tabular-nums;
    }

    .cond-anim {
        animation: condIn .18s ease both;
    }

    .approval-badge-bar {
        position: relative;
        z-index: 1;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 10px;
    }

    .approval-badge-bar #badge-approval-dekom {
        margin-right: auto;
    }

    .approval-badge-bar #badge-approval-direksi {
        margin-left: auto;
    }

    .approval-badge-bar .approval-status-badge {
        min-width: 230px;
        text-align: left;
    }


    @media (max-width: 768px) {
        .kcard-body {
            grid-template-columns: 1fr;
            padding: 16px;
        }

        .kcard-col {
            padding-left: 12px;
        }
    }

    @keyframes condIn {
        from {
            opacity: 0;
            transform: translateY(-4px);
        }

        to {
            opacity: 1;
            transform: none;
        }
    }

    @media (max-width: 576px) {
        .kerugian-total {
            justify-content: space-between;
            gap: 8px 10px;
        }

        .kt-item {
            min-width: 0;
            flex: 1;
            align-items: flex-start;
        }

        .kt-item strong {
            font-size: 13px;
        }
    }

    @media (max-width: 576px) {
        .ketentuan-cards-grid {
            grid-template-columns: 1fr;
        }
    }

    .btn {
        transition: var(--tr);
        font-weight: 600;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: var(--sh);
    }

    .btn:active {
        transform: scale(.96) !important;
    }

    .btn-sm {
        font-size: 12px;
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
        width: 32px;
        height: 32px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        font-weight: 700;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .15);
        transition: var(--tr);
    }

    .btn-primaryy:hover {
        background: var(--brand-bg);
        transform: rotate(90deg) scale(1.1);
    }

    .empty-state-box {
        text-align: center;
        padding: 60px 20px;
        background: var(--gray-50);
        border-radius: var(--r-lg);
        border: 2px dashed var(--gray-200);
        margin: 10px 0;
    }

    .nodata-box {
        text-align: center;
        padding: 16px 20px;
        font-size: 14px;
        font-weight: 600;
        color: var(--gray-700);
        background: var(--gray-50);
        border: 1.5px solid var(--gray-200);
        border-radius: var(--r);
    }

    .kerugian-note {
        display: flex;
        gap: 8px;
        align-items: flex-start;
        background: var(--info-bg);
        color: #164e63;
        border-radius: var(--r-sm);
        padding: 8px 12px;
        margin-bottom: 12px;
        font-size: 12.5px;
        line-height: 1.5;
    }

    .kerugian-note i {
        margin-top: 3px;
        flex-shrink: 0;
    }

    .modal-header-sdm {
        background: var(--grad);
        color: white;
    }

    .modal-content {
        border-radius: var(--r-lg);
        overflow: hidden;
        border: none;
        box-shadow: var(--sh-lg);
    }

    .modal-body {
        padding: 20px 24px !important;
    }

    .modal-footer {
        padding: 12px 24px !important;
    }

    .modal-body .form-group {
        margin-bottom: 16px !important;
    }

    .modal-body .form-control {
        width: 100% !important;
        box-sizing: border-box !important;
        border-radius: 8px !important;
        border: 1.5px solid var(--gray-200) !important;
        padding: 8px 12px !important;
        font-size: 13.5px !important;
        transition: border-color .15s, box-shadow .15s !important;
    }

    .modal-body .form-control:focus {
        border-color: var(--brand) !important;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, .15) !important;
        outline: none !important;
    }

    .modal-body .row {
        margin-left: -8px !important;
        margin-right: -8px !important;
    }

    .modal-body .row>[class*="col-"] {
        padding-left: 8px !important;
        padding-right: 8px !important;
    }

    .modal-dialog {
        margin: 20px auto !important;
        max-width: 720px !important;
    }

    .modal-dialog.modal-xl {
        max-width: 1100px !important;
    }

    .t-item {
        background: white;
        border-radius: var(--r);
        padding: 14px 18px;
        margin-bottom: 10px;
        box-shadow: var(--sh-lg);
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 13.5px;
        animation: tIn .3s ease;
        pointer-events: all;
        border-left: 4px solid;
    }

    .t-item.t-success {
        border-color: var(--success);
        color: #065f46;
    }

    .t-item.t-error {
        border-color: var(--danger);
        color: #991b1b;
    }

    .t-item.t-info {
        border-color: var(--info);
        color: #164e63;
    }

    @keyframes tIn {
        from {
            transform: translateX(110%);
            opacity: 0
        }

        to {
            transform: translateX(0);
            opacity: 1
        }
    }

    .skel {
        height: 52px;
        border-radius: var(--r);
        background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
        background-size: 200% 100%;
        animation: skAnim 1.4s infinite;
    }

    @keyframes skAnim {
        0% {
            background-position: 200% 0
        }

        100% {
            background-position: -200% 0
        }
    }

    #pk-content-wrap {
        transition: opacity .25s ease
    }

    #pk-content-wrap.pk-blurred {
        opacity: .45;
        pointer-events: none;
        filter: grayscale(.15)
    }

    @media (prefers-reduced-motion: reduce) {

        *,
        *::before,
        *::after {
            animation-duration: .001ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: .001ms !important;
        }
    }

    @media (max-width:768px) {
        .modal-dialog {
            margin: 10px !important;
            max-width: calc(100% - 20px) !important;
        }

        .modal-body {
            padding: 14px 16px !important;
        }

        .modal-body .row>[class*="col-"] {
            padding-left: 6px !important;
            padding-right: 6px !important;
        }

        .btn:hover {
            transform: none;
            box-shadow: none;
        }

        .ketentuan-card:hover {
            transform: none;
        }
    }
</style>

<?php
$_canEdit = !empty($canInput) ? 'true' : 'false';
$_canAppDekom = !empty($canApproveDekom) ? 'true' : 'false';
$_canAppDireksi = !empty($canApproveDireksi) ? 'true' : 'false';
$_base = rtrim(base_url(), '/');
$_csrf = csrf_token();
$_hash = csrf_hash();
$_userId = (int) ($userId ?? 0);
$_fullname = addslashes($fullname ?? 'Unknown');
?>
<script>
    (function wait() {
        if (typeof jQuery === 'undefined') return setTimeout(wait, 50);
        jQuery(document).ready(init);
    })();

    function init($) {
        'use strict';

        var BASE = '<?= $_base ?>', CSRF = '<?= $_csrf ?>', HASH = '<?= $_hash ?>',
            UID = <?= $_userId ?>, FNAME = '<?= $_fullname ?>',
            CAN_EDIT = <?= $_canEdit ?>,
            CAN_APPROVE = { dekom: <?= $_canAppDekom ?>, direksi: <?= $_canAppDireksi ?> };

        // Level approval: dekom = kolom accdir, direksi = kolom accdir2
        var LEVELS = ['dekom', 'direksi'];
        var LEVEL_LABEL = { dekom: 'Dewan Komisaris', direksi: 'Direktur Utama' };
        var LEVEL_COL = { dekom: 'accdir', direksi: 'accdir2' };
        var FORM_TYPE = '<?= esc($_formType) ?>';
        var IS_B = FORM_TYPE === 'B';

        var POLL_MS = 30000;

        // Definisi field per tipe form
        var DATA_FIELDS = IS_B ? [
            'kejadianfraud', 'idkejadian', 'jenisfraud', 'ketjenisfraud', 'aktivitasfraud',
            'descfraud', 'lokfraud', 'ketlokfraud', 'divisifraud', 'pihakrugi',
            'wterjadiawal', 'wterjadiakhir', 'wfrauddiketahui',
            'rugipotensitotal',
            'tindaklanjutljk', 'kettindaklanjutljk', 'targetpelaksanaan', 'realpelaksanaan',
            'pelakufraud', 'namapelaku', 'jenisidentitas', 'noidentitas', 'jeniskelamin',
            'alamatidentitas', 'alamatdomisili', 'tempatlahir', 'tanggallahir',
            'statuspelaku', 'jabatanterjadi', 'ketjabatanterjadi', 'jabatandiketahui', 'ketjabatanketahui',
            'keteranganpelaku', 'pengenaansanksi', 'statuspenanganan'
        ] : [
            'kejadianfraud', 'idkejadian', 'jenisfraud', 'ketjenisfraud', 'aktivitasfraud',
            'descfraud', 'lokfraud', 'ketlokfraud', 'divisifraud', 'pihakrugi',
            'wterjadiawal', 'wterjadiakhir', 'wfrauddiketahui',
            'rugiljkriil', 'rugiljkpot', 'rugiljkrec',
            'rugikonsumenriil', 'rugikonsumenpot', 'rugikonsumenrec',
            'rugilainriil', 'rugilainpot', 'rugilainrec',
            'kelemahanfraud', 'ketkelemahanfraud',
            'tindakpenanganan', 'kettindakpenanganan',
            'tindakperbaikan', 'kettindakperbaikan', 'targetpelaksanaan', 'realpelaksanaan',
            'pelakufraud', 'namapelaku', 'jenisidentitas', 'noidentitas', 'jeniskelamin',
            'alamatidentitas', 'alamatdomisili', 'tempatlahir', 'tanggallahir',
            'statuspelaku', 'jabatanterjadi', 'ketjabatanterjadi', 'jabatandiketahui', 'ketjabatanketahui',
            'keteranganpelaku', 'pengenaansanksi', 'statuspenanganan'
        ];

        // Field uang: tampil terformat (1.500.000), tersimpan numeric (1500000)
        var MONEY_FIELDS = IS_B ? ['rugipotensitotal'] : [
            'rugiljkriil', 'rugiljkpot', 'rugiljkrec',
            'rugikonsumenriil', 'rugikonsumenpot', 'rugikonsumenrec',
            'rugilainriil', 'rugilainpot', 'rugilainrec'
        ];
        var MONEY_SET = {};
        for (var _mi = 0; _mi < MONEY_FIELDS.length; _mi++) MONEY_SET[MONEY_FIELDS[_mi]] = 1;
        var TOTAL_GROUPS = IS_B ? [] : [
            ['tot-riil', ['rugiljkriil', 'rugikonsumenriil', 'rugilainriil']],
            ['tot-pot', ['rugiljkpot', 'rugikonsumenpot', 'rugilainpot']],
            ['tot-rec', ['rugiljkrec', 'rugikonsumenrec', 'rugilainrec']]
        ];

        // [select pemicu, nilai pemicu, wrapper, input, nama field, label]
        var COND_FIELDS = IS_B ? [
            ['#f-jenisfraud', '701', '#wrap-ketjenisfraud', '#f-ketjenisfraud', 'ketjenisfraud', 'Keterangan Jenis Fraud']
        ] : [
            ['#f-jenisfraud', '701', '#wrap-ketjenisfraud', '#f-ketjenisfraud', 'ketjenisfraud', 'Keterangan Jenis Fraud'],
            ['#f-kelemahanfraud', '901', '#wrap-ketkelemahanfraud', '#f-ketkelemahanfraud', 'ketkelemahanfraud', 'Keterangan Kelemahan'],
            ['#f-tindakpenanganan', '19', '#wrap-kettindakpenanganan', '#f-kettindakpenanganan', 'kettindakpenanganan', 'Keterangan Tindakan Penanganan'],
            ['#f-tindakperbaikan', '900', '#wrap-kettindakperbaikan', '#f-kettindakperbaikan', 'kettindakperbaikan', 'Keterangan Tindakan Perbaikan']
        ];
        var COND_TRIGGERS = IS_B ? '#f-jenisfraud' : '#f-jenisfraud,#f-kelemahanfraud,#f-tindakpenanganan,#f-tindakperbaikan';

        COND_FIELDS.push(['#f-pelakufraud', '002', '#wrap-keteranganpelaku', '#f-keteranganpelaku', 'keteranganpelaku', 'Keterangan Pelaku']);
        COND_TRIGGERS += ',#f-pelakufraud';

        // Field wajib tanpa syarat
        var WAJIB = [
            ['kejadianfraud', 'Kejadian Fraud Menurut Pelaku'],
            ['idkejadian', 'ID Kejadian Fraud'],
            ['jenisfraud', 'Jenis Fraud'],
            ['aktivitasfraud', 'Aktivitas Terkait Fraud'],
            ['lokfraud', 'Lokasi Fraud'],
            ['ketlokfraud', 'Keterangan Lokasi'],
            ['pihakrugi', 'Pihak Yang Dirugikan']
        ];
        if (IS_B) {
            WAJIB.push(['tindaklanjutljk', 'Tindak Lanjut LJK']);
        } else {
            WAJIB.push(['kelemahanfraud', 'Kelemahan Penyebab Fraud']);
            WAJIB.push(['tindakpenanganan', 'Tindakan Penanganan Fraud']);
            WAJIB.push(['tindakperbaikan', 'Tindakan Perbaikan']);
        }
        if (IS_B) {
            WAJIB.push(['rugipotensitotal', 'Total Kerugian Potensial']);
        } else {
            WAJIB.push(['rugiljkriil', 'Kerugian LJK - Riil']);
            WAJIB.push(['rugiljkpot', 'Kerugian LJK - Potensial']);
            WAJIB.push(['rugiljkrec', 'Kerugian LJK - Recovery']);
        }
        WAJIB = WAJIB.concat([
            ['targetpelaksanaan', 'Target Waktu Pelaksanaan'],
            ['realpelaksanaan', 'Realisasi Pelaksanaan'],
            ['pelakufraud', 'Pelaku berasal dari'],
            ['namapelaku', 'Nama Pelaku'],
            ['jenisidentitas', 'Jenis Identitas'],
            ['statuspelaku', 'Status Pelaku'],
            ['jabatanterjadi', 'Jabatan Saat Fraud Terjadi'],
            ['jabatandiketahui', 'Jabatan Saat Fraud Diketahui'],
            ['statuspenanganan', 'Status Penanganan']
        ]);

        // Daftar keterangan lokasi — diisi ke <select> secara lazy (hemat ±8KB HTML per request)
        var LOKASI = [
            ['0102', 'Kab. Bekasi'], ['0103', 'Kab. Purwakarta'], ['0106', 'Kab. Karawang'],
            ['0108', 'Kab. Bogor'], ['0109', 'Kab. Sukabumi'], ['0110', 'Kab. Cianjur'],
            ['0111', 'Kab. Bandung'], ['0112', 'Kab. Sumedang'], ['0113', 'Kab. Tasikmalaya'],
            ['0114', 'Kab. Garut'], ['0115', 'Kab. Ciamis'], ['0116', 'Kab. Cirebon'],
            ['0117', 'Kab. Kuningan'], ['0118', 'Kab. Indramayu'], ['0119', 'Kab. Majalengka'],
            ['0121', 'Kab. Subang'], ['0122', 'Kab. Bandung Barat'], ['0123', 'Kab. Pangandaran'],
            ['0180', 'Kota Banjar'], ['0191', 'Kota Bandung'], ['0192', 'Kota Bogor'],
            ['0193', 'Kota Sukabumi'], ['0194', 'Kota Cirebon'], ['0195', 'Kota Tasikmalaya'],
            ['0196', 'Kota Cimahi'], ['0197', 'Kota Depok'], ['0198', 'Kota Bekasi'],
            ['0201', 'Kab. Lebak'], ['0202', 'Kab. Pandeglang'], ['0203', 'Kab. Serang'],
            ['0204', 'Kab. Tangerang'], ['0291', 'Kota Cilegon'], ['0292', 'Kota Tangerang'],
            ['0293', 'Kota Serang'], ['0294', 'Kota Tangerang Selatan'],
            ['0391', 'Wil. Kota Jakarta Pusat'], ['0392', 'Wil. Kota Jakarta Utara'],
            ['0393', 'Wil. Kota Jakarta Barat'], ['0394', 'Wil. Kota Jakarta Selatan'],
            ['0395', 'Wil. Kota Jakarta Timur'], ['0396', 'Wil. Kepulauan Seribu'],
            ['0501', 'Kab. Bantul'], ['0502', 'Kab. Sleman'], ['0503', 'Kab. Gunung Kidul'],
            ['0504', 'Kab. Kulon Progo'], ['0591', 'Kota Yogyakarta'],
            ['0901', 'Kab. Semarang'], ['0902', 'Kab. Kendal'], ['0903', 'Kab. Demak'],
            ['0904', 'Kab. Grobogan'], ['0905', 'Kab. Pekalongan'], ['0906', 'Kab. Tegal'],
            ['0907', 'Kab. Brebes'], ['0908', 'Kab. Pati'], ['0909', 'Kab. Kudus'],
            ['0910', 'Kab. Pemalang'], ['0911', 'Kab. Jepara'], ['0912', 'Kab. Rembang'],
            ['0913', 'Kab. Blora'], ['0914', 'Kab. Banyumas'], ['0915', 'Kab. Cilacap'],
            ['0916', 'Kab. Purbalingga'], ['0917', 'Kab. Banjarnegara'], ['0918', 'Kab. Magelang'],
            ['0919', 'Kab. Temanggung'], ['0920', 'Kab. Wonosobo'], ['0921', 'Kab. Purworejo'],
            ['0922', 'Kab. Kebumen'], ['0923', 'Kab. Klaten'], ['0924', 'Kab. Boyolali'],
            ['0925', 'Kab. Sragen'], ['0926', 'Kab. Sukoharjo'], ['0927', 'Kab. Karanganyar'],
            ['0928', 'Kab. Wonogiri'], ['0929', 'Kab. Batang'], ['0991', 'Kota Semarang'],
            ['0992', 'Kota Salatiga'], ['0993', 'Kota Pekalongan'], ['0994', 'Kota Tegal'],
            ['0995', 'Kota Magelang'], ['0996', 'Kota Surakarta/Solo'],
            ['1201', 'Kab. Gresik'], ['1202', 'Kab. Sidoarjo'], ['1203', 'Kab. Mojokerto'],
            ['1204', 'Kab. Jombang'], ['1205', 'Kab. Sampang'], ['1206', 'Kab. Pamekasan'],
            ['1207', 'Kab. Sumenep'], ['1208', 'Kab. Bangkalan'], ['1209', 'Kab. Bondowoso'],
            ['1211', 'Kab. Banyuwangi'], ['1212', 'Kab. Jember'], ['1213', 'Kab. Malang'],
            ['1214', 'Kab. Pasuruan'], ['1215', 'Kab. Probolinggo'], ['1216', 'Kab. Lumajang'],
            ['1217', 'Kab. Kediri'], ['1218', 'Kab. Nganjuk'], ['1219', 'Kab. Tulungagung'],
            ['1220', 'Kab. Trenggalek'], ['1221', 'Kab. Blitar'], ['1222', 'Kab. Madiun'],
            ['1223', 'Kab. Ngawi'], ['1224', 'Kab. Magetan'], ['1225', 'Kab. Ponorogo'],
            ['1226', 'Kab. Pacitan'], ['1227', 'Kab. Bojonegoro'], ['1228', 'Kab. Tuban'],
            ['1229', 'Kab. Lamongan'], ['1230', 'Kab. Situbondo'], ['1271', 'Kota Batu'],
            ['1291', 'Kota Surabaya'], ['1292', 'Kota Mojokerto'], ['1293', 'Kota Malang'],
            ['1294', 'Kota Pasuruan'], ['1295', 'Kota Probolinggo'], ['1296', 'Kota Blitar'],
            ['1297', 'Kota Kediri'], ['1298', 'Kota Madiun'], ['9999', 'Di Luar Indonesia']
        ];

        var LABELS = {
            kejadianfraud: { A: 'Pelaku internal', B: 'Pelaku eksternal', C: 'Pelaku internal & eksternal' },
            jenisfraud: {
                '201': 'Pemerasan (Korupsi)', '202': 'Benturan kepentingan (Korupsi)',
                '203': 'Penyuapan (Korupsi)', '204': 'Penerimaan tidak sah (Korupsi)',
                '301': 'Penyalahgunaan uang tunai', '302': 'Penyalahgunaan persediaan',
                '303': 'Penyalahgunaan aset lainnya', '401': 'Kecurangan laporan keuangan',
                '501': 'Penipuan', '601': 'Pembocoran informasi rahasia',
                '701': 'Tindakan lain'
            },
            aktivitasfraud: {
                '301': 'Pendanaan', '302': 'Perkreditan/pembiayaan', '303': 'Penggunaan identitas/data',
                '304': 'Pengelolaan aset/investasi', '305': 'Penggunaan siber', '306': 'Pembukuan & laporan keuangan',
                '307': 'APU-PPT-PPPSPM', '308': 'Transaksi efek', '309': 'Pemasaran', '310': 'Kustodian',
                '311': 'Penjatahan efek', '312': 'Due diligence penjaminan emisi', '313': 'Riset investasi',
                '314': 'Proses underwriting', '315': 'Pengelolaan iuran/premi', '316': 'Pengurusan klaim/manfaat pensiun',
                '317': 'Penilaian kerugian asuransi', '318': 'Pemilihan asuransi/reasuransi', '319': 'Surplus underwriting',
                '320': 'Pengelolaan data kepesertaan', '321': 'Proses subrogasi', '322': 'Pemberian jasa manajemen',
                '323': 'Pendanaan bersama berbasis TI', '324': 'Bullion', '325': 'Sekuritisasi', '326': 'Pendukung Pasar',
                '327': 'Aset Keuangan Digital/Kripto', '399': 'Aktivitas lain'
            },
            lokfraud: {
                '01': 'Kantor Pusat Operasional', '02': 'Kantor Pusat Non Operasional',
                '03': 'Kantor Cabang LJK (Luar Negeri)', '04': 'Kantor Wilayah',
                '05': 'Kantor Cabang (Dalam Negeri)', '06': 'Kantor cabang bank (Luar Negeri)',
                '07': 'Kantor Cabang Pembantu LJK (Luar Negeri)', '08': 'Kantor Cabang Pembantu (Dalam Negeri)',
                '09': 'Kantor Cabang Pembantu (Luar Negeri)', '10': 'Kantor Kas',
                '11': 'Kantor Fungsional/Outlet', '12': 'Payment Point',
                '13': 'Kas Keliling/Mobil/Terapung', '14': 'Kantor Perwakilan LJK (Luar Negeri)',
                '15': 'ATM/CDM/CRM'
            },
            pihakrugi: { '001': 'Lembaga Jasa Keuangan (LJK)', '002': 'Konsumen', '003': 'Pihak Lain' },
            kelemahan: {
                '101': 'SDM - Integritas', '102': 'SDM - Kompetensi',
                '201': 'SPI - Pengendalian internal Pimpinan', '202': 'SPI - Kebijakan internal LJK',
                '203': 'SPI - Ketidaksesuaian Tingkat/Toleransi Risiko', '204': 'SPI - Pelanggaran Standar & Prosedur',
                '205': 'SPI - Tidak berjalan Four Eyes Principle', '206': 'SPI - Pelaporan tidak akurat/tepat waktu',
                '207': 'SPI - Struktur organisasi belum efektif', '301': 'Teknologi Informasi',
                '401': 'Strategi Anti Fraud belum efektif', '501': 'Eksternal - Kelalaian Konsumen',
                '502': 'Eksternal - Pemahaman Konsumen jaga data', '503': 'Eksternal - Kecurangan Konsumen',
                '504': 'Eksternal - Kecurangan Pihak Lain', '901': 'Kelemahan Lain'
            },
            tindakpenanganan: {
                '01': 'Surat peringatan', '02': 'Rotasi/mutasi', '03': 'Penurunan jabatan',
                '04': 'Pengunduran diri sukarela', '05': 'Pemutusan hubungan kerja', '06': 'Pemblokiran kartu debit/kredit',
                '07': 'Pemblokiran rekening', '08': 'Penggantian kartu debit/kredit', '09': 'Pelaporan kepolisian/hukum',
                '10': 'Ganti rugi', '11': 'Pembatalan polis/kontrak', '12': 'Pencatatan track record',
                '13': 'Pelaporan ke asosiasi/regulator', '19': 'Tindakan Lain'
            },
            pelakufraud: { '001': 'Internal', '002': 'Eksternal' },
            jenisidentitas: { '001': 'KTP (NIK)', '002': 'Paspor', '003': 'NPWP', '009': 'Tidak diketahui' },
            jeniskelamin: { L: 'Laki-laki', P: 'Perempuan' },
            statuspelaku: { '001': 'Pelaku Utama', '002': 'Pelaku Terlibat' },
            jabatan: {
                '001': 'Direktur Utama/Ketua Pengurus', '002': 'Direktur/Pengurus',
                '003': 'Direktur Kepatuhan', '004': 'Komisaris Utama/Ketua Dewan Pengawas',
                '005': 'Komisaris/Dewan Pengawas', '006': 'Dewan Pengawas Syariah',
                '007': 'Pejabat Eksekutif', '008': 'Pemegang Saham Pengendali', '009': 'Pemegang Saham',
                '010': 'Tenaga Ahli/Konsultan', '011': 'Komisaris Independen', '012': 'Pejabat non Pejabat Eksekutif',
                '019': 'Pegawai non Pejabat', '041': 'Pensiun Karir', '042': 'Pensiun Dini/Disabilitas',
                '043': 'Diberhentikan sendiri', '044': 'Kontrak berakhir', '045': 'Meninggal Dunia'
            },
            keteranganpelaku: {
                '001': 'Konsumen', '002': 'Rekanan/vendor/investor', '003': 'Tidak berhubungan langsung dengan LJK'
            },
            statuspenanganan: {
                '001': 'Proses internal LJK', '002': 'Selesai diproses internal LJK',
                '003': 'Proses penegak hukum', '004': 'Berkekuatan hukum tetap (Inkracht)'
            }
        };
        LABELS.tindakperbaikan = {
            '100': 'Sumber daya manusia',
            '200': 'Sistem pengendalian internal',
            '300': 'Teknologi informasi',
            '400': 'Penerapan Strategi Anti Fraud',
            '500': 'Koordinasi dengan asosiasi/regulator/instansi',
            '900': 'Tindakan lain'
        };


        var LOKASI_MAP = {};
        for (var _li = 0; _li < LOKASI.length; _li++) LOKASI_MAP[LOKASI[_li][0]] = LOKASI[_li][1];

        function lbl(map, code) {
            if (code == null || code === '') return '-';
            return (map && map[code]) ? map[code] : code;
        }
        function metaRow(label, icon, value) {
            var v = (value === null || value === undefined || value === '' || value === false) ? '-' : value;
            var cls = (v === '-') ? ' kcard-meta-empty' : '';
            return '<div class="kcard-meta-item"><span class="kcard-meta-label"><i class="fas ' + icon + '"></i> '
                + label + '</span><span class="kcard-meta-val' + cls + '">' + esc(v) + '</span></div>';
        }
        function ketBox(label, icon, value) {
            if (!value) return '';
            return '<div class="kcard-keterangan-wrap"><div class="kcard-ket-label"><i class="fas ' + icon + '"></i> '
                + label + '</div><div class="kcard-ket-text">' + esc(value) + '</div></div>';
        }

        // Util
        function esc(s) {
            return s == null ? '' : String(s)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
        function debounce(fn, ms) {
            var t;
            return function () {
                var a = arguments, c = this;
                clearTimeout(t);
                t = setTimeout(function () { fn.apply(c, a); }, ms);
            };
        }
        function throttle(fn, ms) {
            var busy = false;
            return function () {
                if (busy) return;
                busy = true;
                fn.apply(this, arguments);
                setTimeout(function () { busy = false; }, ms);
            };
        }
        function toast(msg, type) {
            type = type || 'success';
            var icons = { success: 'check-circle', error: 'exclamation-circle', info: 'info-circle' };
            var el = $('<div class="t-item t-' + type + '"><i class="fas fa-' + icons[type] + ' fa-lg"></i><div>' + msg + '</div></div>');
            $('#toast-wrap').append(el);
            setTimeout(function () { el.fadeOut(400, function () { $(this).remove(); }); }, 3500);
        }
        function csrf() { var d = {}; d[CSRF] = HASH; return d; }
        function updateCsrf(res) {
            if (res && res.csrf_hash) HASH = res.csrf_hash;
            var m = document.cookie.match(/ci_csrf_token=([^;]+)/);
            if (m) HASH = decodeURIComponent(m[1]);
        }
        function parseErr(xhr) {
            try { var r = JSON.parse(xhr.responseText); if (r && r.message) return r.message; } catch (e) { }
            return null;
        }
        function doPost(url, data, cb, errCb) {
            $.ajax({
                url: BASE + '/' + url, method: 'POST', dataType: 'json',
                data: $.extend({}, csrf(), data),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) { updateCsrf(res); cb(res); },
                error: function (xhr) {
                    var m = parseErr(xhr) || 'Terjadi kesalahan server.';
                    if (xhr.status === 403) {
                        m = 'Sesi keamanan kedaluwarsa. Halaman akan di-refresh...';
                        setTimeout(function () { location.reload(); }, 2000);
                    }
                    errCb ? errCb(m) : toast(m, 'error');
                }
            });
        }
        function doGet(url, params, cb) {
            if (typeof params === 'function') { cb = params; params = {}; }
            $.ajax({
                url: BASE + '/' + url, method: 'GET', dataType: 'json', data: params,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: cb,
                error: function (xhr) {
                    $('#skeleton-container').hide();
                    var m = parseErr(xhr) || 'Gagal memuat data.';
                    if (xhr.status === 401) {
                        m = 'Sesi telah berakhir. Mengalihkan...';
                        setTimeout(function () { location.href = BASE + '/login'; }, 1500);
                    }
                    toast(m, 'error');
                }
            });
        }
        function btnLoading(btn) {
            var ori = btn.html();
            btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
            return function () { btn.html(ori).prop('disabled', false); };
        }
        function timeAgo(s) {
            if (!s) return '-';
            var d = new Date(s), diff = Math.floor((Date.now() - d.getTime()) / 60000);
            if (diff < 1) return 'Baru saja';
            if (diff < 60) return diff + ' menit lalu';
            if (diff < 1440) return Math.floor(diff / 60) + ' jam lalu';
            return ('0' + d.getDate()).slice(-2) + '/' + ('0' + (d.getMonth() + 1)).slice(-2) + '/' + d.getFullYear()
                + ' ' + ('0' + d.getHours()).slice(-2) + ':' + ('0' + d.getMinutes()).slice(-2);
        }

        // Format uang
        function onlyDigits(v) {
            return String(v == null ? '' : v).replace(/\D/g, '').replace(/^0+(?=\d)/, '');
        }
        function fmtMoney(v) {
            var d = onlyDigits(v);
            return d ? d.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
        }
        function fmtRupiah(n) {
            n = parseFloat(n || 0);
            if (!n) return null;
            return 'Rp' + fmtMoney(Math.round(n));
        }
        function fmtRupiahNullable(v) {
            if (v == null || v === '') return null;
            return 'Rp' + (fmtMoney(Math.round(parseFloat(v))) || '0');
        }
        function fmtTgl(s) {
            if (!s) return null;
            var m = /^(\d{4})-(\d{2})-(\d{2})/.exec(s);
            return m ? m[3] + '/' + m[2] + '/' + m[1] : s;
        }

        // Format live sambil menjaga posisi caret
        $(document).on('input', '.money', function () {
            var el = this;
            var caret = el.selectionStart || 0;
            var before = el.value.slice(0, caret).replace(/\D/g, '').length;
            var f = fmtMoney(el.value);
            if (f !== el.value) {
                el.value = f;
                var pos = f.length, cnt = 0;
                if (before > 0) {
                    for (var i = 0; i < f.length; i++) {
                        var c = f.charCodeAt(i);
                        if (c > 47 && c < 58) cnt++;
                        if (cnt === before) { pos = i + 1; break; }
                    }
                } else { pos = 0; }
                try { el.setSelectionRange(pos, pos); } catch (e) { }
            }
            updateTotalRugi();
        });

        function updateTotalRugi() {
            for (var g = 0; g < TOTAL_GROUPS.length; g++) {
                var fields = TOTAL_GROUPS[g][1], t = 0;
                for (var i = 0; i < fields.length; i++) {
                    t += parseFloat(onlyDigits($('#f-' + fields[i]).val()) || 0);
                }
                var el = document.getElementById(TOTAL_GROUPS[g][0]);
                if (el) el.textContent = 'Rp' + (fmtMoney(t) || '0');
            }
        }

        var ALWAYS_SHOW = ['kettindakperbaikan'];

        function syncCondFields(keepValue) {
            for (var i = 0; i < COND_FIELDS.length; i++) {
                var c = COND_FIELDS[i];
                var show = $(c[0]).val() === c[1];
                var wrap = $(c[2]);

                if (ALWAYS_SHOW.indexOf(c[4]) > -1) {
                    $('#star-' + c[4]).prop('hidden', !show);
                    continue;
                }

                if (show) {
                    if (wrap.prop('hidden')) wrap.prop('hidden', false).addClass('cond-anim');
                } else {
                    wrap.prop('hidden', true).removeClass('cond-anim');
                    if (!keepValue) $(c[3]).val('');
                }
            }
        }
        $(document).on('change', COND_TRIGGERS, function () { syncCondFields(false); });

        // Lazy fill select lokasi
        var _lokFilled = false;
        function fillLokasi() {
            if (_lokFilled) return;
            var s = document.getElementById('f-ketlokfraud');
            if (!s) return;
            var h = '<option value="">-- Pilih keterangan lokasi --</option>';
            for (var i = 0; i < LOKASI.length; i++) {
                h += '<option value="' + LOKASI[i][0] + '">' + LOKASI[i][1] + '</option>';
            }
            s.innerHTML = h;
            _lokFilled = true;
        }

        // Render kartu
        var _rowsCache = {};

        function renderKolomKerugian(r) {
            if (IS_B) {
                return '<div class="kcard-col">'
                    + '<span class="kcard-col-title"><i class="fas fa-coins"></i> Kerugian Potensial</span>'
                    + '<div class="kcard-meta-row">'
                    + metaRow('Total Kerugian Potensial', 'fa-calculator', fmtRupiah(r.rugipotensitotal) || '0')
                    + '</div>'
                    + '</div>';
            }
            var totRiil = (parseFloat(r.rugiljkriil) || 0) + (parseFloat(r.rugikonsumenriil) || 0) + (parseFloat(r.rugilainriil) || 0);
            var totPot = (parseFloat(r.rugiljkpot) || 0) + (parseFloat(r.rugikonsumenpot) || 0) + (parseFloat(r.rugilainpot) || 0);
            var totRec = (parseFloat(r.rugiljkrec) || 0) + (parseFloat(r.rugikonsumenrec) || 0) + (parseFloat(r.rugilainrec) || 0);
            return '<div class="kcard-col">'
                + '<span class="kcard-col-title"><i class="fas fa-coins"></i> Rincian Kerugian (Rp)</span>'
                + '<div class="kcard-meta-row">'
                + metaRow('LJK Riil', 'fa-university', fmtRupiah(r.rugiljkriil) || '0')
                + metaRow('LJK Potensial', 'fa-university', fmtRupiah(r.rugiljkpot) || '0')
                + metaRow('LJK Recovery', 'fa-university', fmtRupiah(r.rugiljkrec) || '0')
                + metaRow('Konsumen Riil', 'fa-user', fmtRupiahNullable(r.rugikonsumenriil))
                + metaRow('Konsumen Potensial', 'fa-user', fmtRupiahNullable(r.rugikonsumenpot))
                + metaRow('Konsumen Recovery', 'fa-user', fmtRupiahNullable(r.rugikonsumenrec))
                + metaRow('Pihak Lain Riil', 'fa-user-friends', fmtRupiahNullable(r.rugilainriil))
                + metaRow('Pihak Lain Potensial', 'fa-user-friends', fmtRupiahNullable(r.rugilainpot))
                + metaRow('Pihak Lain Recovery', 'fa-user-friends', fmtRupiahNullable(r.rugilainrec))
                + '</div>'
                + '<div class="kcard-keterangan-wrap">'
                + '<div class="kcard-meta-row">'
                + metaRow('Total Riil', 'fa-calculator', fmtRupiah(totRiil) || '0')
                + metaRow('Total Potensial', 'fa-calculator', fmtRupiah(totPot) || '0')
                + metaRow('Total Recovery', 'fa-calculator', fmtRupiah(totRec) || '0')
                + '</div></div>'
                + '</div>';
        }

        function renderKolomTindakan(r) {
            if (IS_B) {
                return '<div class="kcard-col">'
                    + '<span class="kcard-col-title"><i class="fas fa-tools"></i> Tindak Lanjut LJK</span>'
                    + '<div class="kcard-meta-row">'
                    + metaRow('Target Pelaksanaan', 'fa-bullseye', fmtTgl(r.targetpelaksanaan))
                    + metaRow('Realisasi Pelaksanaan', 'fa-check-double', fmtTgl(r.realpelaksanaan))
                    + '</div>'
                    + ketBox('Tindak Lanjut LJK', 'fa-info-circle', r.tindaklanjutljk)
                    + ketBox('Keterangan Tindak Lanjut LJK', 'fa-info-circle', r.kettindaklanjutljk)
                    + '</div>';
            }
            return '<div class="kcard-col">'
                + '<span class="kcard-col-title"><i class="fas fa-tools"></i> Kelemahan & Tindakan</span>'
                + '<div class="kcard-meta-row">'
                + metaRow('Kelemahan Penyebab', 'fa-tools', lbl(LABELS.kelemahan, r.kelemahanfraud))
                + metaRow('Tindakan Penanganan', 'fa-hand-paper', lbl(LABELS.tindakpenanganan, r.tindakpenanganan))
                + metaRow('Tindakan Perbaikan', 'fa-wrench', lbl(LABELS.tindakperbaikan, r.tindakperbaikan))
                + metaRow('Target Pelaksanaan', 'fa-bullseye', fmtTgl(r.targetpelaksanaan))
                + metaRow('Realisasi Pelaksanaan', 'fa-check-double', fmtTgl(r.realpelaksanaan))
                + '</div>'
                + ketBox('Keterangan Kelemahan', 'fa-info-circle', r.ketkelemahanfraud)
                + ketBox('Keterangan Tindakan Penanganan', 'fa-info-circle', r.kettindakpenanganan)
                + ketBox('Keterangan Tindakan Perbaikan', 'fa-info-circle', r.kettindakperbaikan)
                + '</div>';
        }

        function isNullRow(r) {
            var keys = ['idkejadian', 'kejadianfraud', 'jenisfraud', 'aktivitasfraud', 'namapelaku', 'descfraud'];
            for (var i = 0; i < keys.length; i++) {
                var v = r[keys[i]];
                if (v !== null && v !== undefined && String(v).trim() !== '') return false;
            }
            return true;
        }

        function renderRows(rows) {
            var container = document.getElementById('cards-ketentuanbpr');
            _rowsCache = {};
            if (!rows || !rows.length) {
                container.innerHTML = '';
                $('#empty-state').show();
                return;
            }
            $('#empty-state').hide();

            var html = '';
            for (var i = 0; i < rows.length; i++) {
                var r = rows[i];
                _rowsCache[r.id] = r;

                var req = r.req_levels || LEVELS;
                var miss = r.missing || [];
                var complete = miss.length === 0;
                var iconsHtml = '', badgesHtml = '';

                for (var li = 0; li < LEVELS.length; li++) {
                    var lv = LEVELS[li];
                    if (req.indexOf(lv) < 0) continue;
                    var ok = parseInt(r[LEVEL_COL[lv]], 10) === 1;

                    badgesHtml += '<div class="kcard-meta-item"><span class="kcard-meta-label"><i class="fas fa-user-tie"></i> Persetujuan '
                        + LEVEL_LABEL[lv] + '</span>'
                        + '<span class="kcard-acc-badge ' + (ok ? 'acc-badge-ok' : 'acc-badge-no') + '"><i class="fas '
                        + (ok ? 'fa-check-circle' : 'fa-clock') + '"></i> '
                        + (ok ? 'Telah disetujui' : 'Belum disetujui') + '</span></div>';

                    if (CAN_APPROVE[lv] && complete) {
                        iconsHtml += '<span class="kcard-lv-wrap"><small class="kcard-lv">' + (lv === 'dekom' ? 'Dekom' : 'Direksi') + '</small>'
                            + '<i class="fas ' + (ok ? 'fa-check-circle' : 'fa-clock') + ' btn-toggle-kom kcard-acc-icon '
                            + (ok ? 'acc-ok' : 'acc-no') + '"'
                            + ' data-id="' + esc(r.id) + '" data-level="' + lv + '"'
                            + ' data-idkejadian="' + esc(r.idkejadian) + '" data-namapelaku="' + esc(r.namapelaku) + '"'
                            + ' data-action="' + (ok ? 'unapprove' : 'approve') + '"'
                            + ' title="' + (ok ? 'Batalkan approval ' : 'Setujui sebagai ') + LEVEL_LABEL[lv] + '"></i></span>';
                    }
                }
                if (!complete) {
                    iconsHtml = '<span class="kcard-incomplete" title="' + esc(miss.join(', ')) + '">'
                        + '<i class="fas fa-exclamation-triangle"></i> Belum lengkap (' + miss.length + ')</span>' + iconsHtml;
                }

                html += '<div class="ketentuan-card" id="kcard-' + esc(r.id) + '">'
                    + '<div class="kcard-header">'
                    + '<div class="kcard-nomor"><span class="kcard-index">' + (i + 1) + '</span>'
                    + '<span class="kcard-nomor-text">' + esc(r.idkejadian || '-') + '</span></div>'
                    + '<div class="kcard-actions">';

                html += iconsHtml;
                if (CAN_EDIT) {
                    html += '<button class="btn btn-sm btn-warning btn-ubah kcard-btn" title="Ubah" data-id="' + esc(r.id) + '">'
                        + '<i class="fas fa-pencil-alt"></i></button>'
                        + '<button class="btn btn-sm btn-danger btn-hapus kcard-btn" title="Hapus" data-id="' + esc(r.id) + '"'
                        + ' data-idkejadian="' + esc(r.idkejadian) + '" data-namapelaku="' + esc(r.namapelaku) + '">'
                        + '<i class="fas fa-trash-alt"></i></button>';
                }
                html += '</div></div>';

                html += '<div class="kcard-body">'

                    // KOLOM 1: Kejadian & Lokasi
                    + '<div class="kcard-col">'
                    + '<div class="kcard-judul">' + esc(r.descfraud || lbl(LABELS.jenisfraud, r.jenisfraud) || '-') + '</div>'
                    + '<span class="kcard-col-title"><i class="fas fa-info-circle"></i> Kejadian & Lokasi</span>'

                    + '<div class="kcard-meta-row">'
                    + metaRow('Pelaku Menurut', 'fa-users', lbl(LABELS.kejadianfraud, r.kejadianfraud))
                    + metaRow('Jenis Fraud', 'fa-tag', lbl(LABELS.jenisfraud, r.jenisfraud))
                    + metaRow('Aktivitas', 'fa-briefcase', lbl(LABELS.aktivitasfraud, r.aktivitasfraud))
                    + metaRow('Lokasi', 'fa-map-marker-alt', lbl(LABELS.lokfraud, r.lokfraud))
                    + metaRow('Keterangan Lokasi', 'fa-map-pin', lbl(LOKASI_MAP, r.ketlokfraud))
                    + metaRow('Divisi/Unit', 'fa-building', r.divisifraud)
                    + metaRow('Pihak Dirugikan', 'fa-balance-scale', lbl(LABELS.pihakrugi, r.pihakrugi))
                    + metaRow('Waktu Terjadi', 'fa-calendar', (r.wterjadiawal || '-') + ' s/d ' + (r.wterjadiakhir || '-'))
                    + metaRow('Waktu Diketahui', 'fa-calendar-check', r.wfrauddiketahui)
                    + '</div>'
                    + ketBox('Keterangan Jenis Fraud', 'fa-info-circle', r.ketjenisfraud)
                    + '</div>'

                    // Kerugian
                    + renderKolomKerugian(r)

                    // Kelemahan & Tindakan / Tindak Lanjut LJK
                    + renderKolomTindakan(r)

                    // KOLOM 4: Data Pelaku
                    + '<div class="kcard-col">'
                    + '<span class="kcard-col-title"><i class="fas fa-user-secret"></i> Data Pelaku</span>'
                    + '<div class="kcard-meta-row">'
                    + metaRow('Pelaku Berasal Dari', 'fa-sign-in-alt', lbl(LABELS.pelakufraud, r.pelakufraud))
                    + metaRow('Nama Pelaku', 'fa-user-tag', r.namapelaku)
                    + metaRow('Jenis/No. Identitas', 'fa-id-card', lbl(LABELS.jenisidentitas, r.jenisidentitas) + (r.noidentitas ? ' - ' + r.noidentitas : ''))
                    + metaRow('Jenis Kelamin', 'fa-venus-mars', lbl(LABELS.jeniskelamin, r.jeniskelamin))
                    + metaRow('Tempat, Tgl Lahir', 'fa-birthday-cake', (r.tempatlahir || '-') + ', ' + (r.tanggallahir || '-'))
                    + metaRow('Status Pelaku', 'fa-user-check', lbl(LABELS.statuspelaku, r.statuspelaku))
                    + metaRow('Jabatan Saat Terjadi', 'fa-id-badge', lbl(LABELS.jabatan, r.jabatanterjadi) + (r.ketjabatanterjadi ? ' - ' + r.ketjabatanterjadi : ''))
                    + metaRow('Jabatan Saat Diketahui', 'fa-id-badge', lbl(LABELS.jabatan, r.jabatandiketahui) + (r.ketjabatanketahui ? ' - ' + r.ketjabatanketahui : ''))
                    + metaRow('Keterangan Pelaku', 'fa-info', lbl(LABELS.keteranganpelaku, r.keteranganpelaku))
                    + '</div>'
                    + ketBox('Alamat Identitas', 'fa-address-card', r.alamatidentitas)
                    + ketBox('Alamat Domisili', 'fa-home', r.alamatdomisili)
                    + '</div>'

                    // FULL WIDTH: Sanksi & Status
                    + '<div class="kcard-col kcard-col-full">'
                    + '<div class="kcard-meta-row">'
                    + metaRow('Pengenaan Sanksi', 'fa-gavel', r.pengenaansanksi)
                    + metaRow('Status Penanganan', 'fa-flag', lbl(LABELS.statuspenanganan, r.statuspenanganan))
                    + badgesHtml
                    + '</div>'
                    + '</div>'

                    + '</div></div>';
            }
            container.innerHTML = html;
        }

        function renderNodataState(isNull) {
            if (isNull) {
                $('#cards-ketentuanbpr').empty();
                _rowsCache = {};
                $('#table-wrapper,#empty-state').hide();
                $('#nodata-banner').show();
                $('#btn-show-tambah,#btn-nodata,#btn-show-import').hide();
            } else {
                $('#nodata-banner').hide();
                $('#table-wrapper').show();
                $('#btn-show-tambah,#btn-nodata,#btn-show-import').show();
            }
        }

        // Load data & approval
        function loadData() {
            $('#skeleton-container').show();
            $('#table-wrapper,#empty-state,#error-state').hide();
            $.ajax({
                url: BASE + '/Penerapansafst/getData', method: 'GET', dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) {
                    $('#skeleton-container').hide();
                    if (res && res.status === 'success') {
                        var rows = res.data || [];
                        var realRows = rows.filter(function (r) { return !isNullRow(r); });
                        var isNull = !!res.is_null_record || (rows.length > 0 && realRows.length === 0);

                        if (isNull) {
                            renderNodataState(true);
                        } else {
                            $('#table-wrapper').show();
                            renderRows(realRows);
                            renderNodataState(false);
                        }
                    } else {
                        $('#table-wrapper').show();
                        $('#empty-state').show();
                        toast((res && res.message) || 'Gagal memuat data', 'error');
                    }
                },
                error: function (xhr) {
                    $('#skeleton-container').hide();
                    $('#table-wrapper').show();
                    var errMap = {
                        401: 'Sesi berakhir. Mengalihkan ke login...',
                        403: 'Akses ditolak (403).',
                        404: 'Endpoint tidak ditemukan (404).'
                    };
                    var m = errMap[xhr.status] || parseErr(xhr) || 'Gagal memuat data dari server.';
                    if (xhr.status === 401) setTimeout(function () { location.href = BASE + '/login'; }, 1500);
                    $('#error-state-msg').text(m);
                    $('#error-state').show();
                    toast(m, 'error');
                }
            });
        }

        function refreshApprovalStatus() {
            doGet('Penerapansafst/getApprovalStatus', function (res) {
                if (!res || res.status !== 'success') return;
                updateBadge('#badge-approval-dekom', 'Dewan Komisaris', res.dekom, res.has_rows);
                updateBadge('#badge-approval-direksi', 'Direktur Utama', res.direksi, res.has_rows);
                $('#card-approve-dekom').toggle(!!(res.dekom && res.dekom.required));
                $('#card-approve-direksi').toggle(!!(res.direksi && res.direksi.required));
            });
        }

        function updateBadge(sel, label, st, hasRows) {
            st = st || {};
            var cls = 'badge-status-secondary', icon = 'fa-clock', sub;

            if (!st.required) {
                icon = 'fa-minus-circle';
                sub = hasRows ? 'Tidak diperlukan' : 'Belum ada data';
            } else if (st.approved) {
                cls = 'badge-status-success';
                icon = 'fa-check-circle';
                sub = 'Telah disetujui · ' + timeAgo(st.approved_at);
            } else {
                sub = 'Belum disetujui';
            }

            $(sel).removeClass('badge-status-secondary badge-status-success').addClass(cls)
                .html('<i class="fas ' + icon + '"></i><div><span class="approval-status-label">' + label + '</span><br>'
                    + '<span class="approval-status-sub">' + sub + '</span></div>');
        }

        // Form: isi, baca, validasi
        function fillForm(r) {
            for (var i = 0; i < DATA_FIELDS.length; i++) {
                var f = DATA_FIELDS[i];
                var v = r ? (r[f] == null ? '' : r[f]) : '';
                if (MONEY_SET[f]) v = fmtMoney(v);
                $('#f-' + f).val(v);
            }
            updateTotalRugi();
        }

        function readForm() {
            var data = {};
            for (var i = 0; i < DATA_FIELDS.length; i++) {
                var f = DATA_FIELDS[i], v = $('#f-' + f).val();
                data[f] = MONEY_SET[f] ? onlyDigits(v) : v;
            }
            return data;
        }

        var _firstMissing = '';
        function validateForm(data, only) {
            var kurang = [];
            _firstMissing = '';
            function inScope(name) { return !only || only.indexOf(name) > -1; }

            for (var i = 0; i < WAJIB.length; i++) {
                if (!inScope(WAJIB[i][0])) continue;
                if (!$.trim(data[WAJIB[i][0]] || '')) {
                    kurang.push(WAJIB[i][1]);
                    if (!_firstMissing) _firstMissing = WAJIB[i][0];
                }
            }
            for (var j = 0; j < COND_FIELDS.length; j++) {
                var c = COND_FIELDS[j];
                if (!inScope(c[4])) continue;
                if ($(c[0]).val() === c[1] && !$.trim(data[c[4]] || '')) {
                    kurang.push(c[5]);
                    if (!_firstMissing) _firstMissing = c[4];
                }
            }
            return kurang;
        }

        var _needReload = false;

        $(document).on('click', '#btn-show-tambah', function () {
            fillLokasi();
            $('#f-id').val('');
            fillForm(null);
            syncCondFields(true);
            $('#form-modal-title').html('<i class="fas fa-plus-circle"></i> Tambah Data Kejadian Fraud');
            $('#form-ubah-warning').hide();
            $('#form-err').hide();
            $('.section-save-state').removeClass('ok err').text('');
            _needReload = false;
            syncSectionButtons();
            $('#modalForm').modal('show');
        });

        $(document).on('click', '.btn-ubah', function () {
            var id = $(this).data('id');
            var r = _rowsCache[id];
            if (!r) { toast('Data tidak ditemukan di cache, muat ulang halaman.', 'error'); return; }
            fillLokasi();
            $('#f-id').val(r.id);
            fillForm(r);
            syncCondFields(true);
            $('#form-modal-title').html('<i class="fas fa-edit"></i> Ubah Data Kejadian Fraud');
            $('#form-ubah-warning').show();
            $('#form-err').hide();
            $('.section-save-state').removeClass('ok err').text('');
            _needReload = false;
            syncSectionButtons();
            $('#modalForm').modal('show');
        });

        var execSimpanForm = debounce(function () {
            var id = $('#f-id').val();
            var data = readForm();

            var kurang = validateForm(data);
            if (kurang.length) {
                $('#form-err').html('<i class="fas fa-exclamation-circle"></i> Belum lengkap: <strong>'
                    + esc(kurang.slice(0, 5).join(', '))
                    + (kurang.length > 5 ? ', dan ' + (kurang.length - 5) + ' lainnya' : '')
                    + '</strong>').show();
                var firstEl = document.getElementById('f-' + _firstMissing);
                if (firstEl && firstEl.scrollIntoView) {
                    firstEl.scrollIntoView({ block: 'center' });
                    try { firstEl.focus({ preventScroll: true }); } catch (e) { firstEl.focus(); }
                }
                return;
            }
            $('#form-err').hide();

            var restore = btnLoading($('#btn-simpan-form'));
            var url = id ? 'Penerapansafst/ubah' : 'Penerapansafst/tambah';
            if (id) data.id = id;

            doPost(url, data, function (res) {
                restore();
                if (res.status === 'success') {
                    _needReload = false;
                    $('#modalForm').modal('hide');
                    toast(res.message || 'Data berhasil disimpan');
                    loadData();
                    refreshApprovalStatus();
                } else {
                    $('#form-err').text(res.message || 'Gagal menyimpan data').show();
                }
            }, function (m) { restore(); $('#form-err').text(m).show(); });
        }, 400);

        $('#btn-simpan-form').on('click', execSimpanForm);

        // Simpan per bagian
        var SECTION_KEYS = ['kejadian', 'jenis', 'lokasi', 'kerugian', 'tindakan', 'pelaku', 'status'];
        var SECTION_FIELDS = {};

        (function buildSectionBars() {
            $('#modalForm .form-section-title').each(function (i) {
                var key = SECTION_KEYS[i];
                if (!key) return;
                var $els = $(this).nextUntil('.form-section-title, #form-ubah-warning, #form-err');
                var names = [];
                $els.find('[id^="f-"]').each(function () { names.push(this.id.substring(2)); });
                SECTION_FIELDS[key] = names;

                var $bar = $('<div class="section-save-bar">'
                    + '<span class="section-save-state"></span>'
                    + '<button type="button" class="btn btn-outline-primary btn-sm btn-save-section" data-section="' + key + '">'
                    + '<i class="fas fa-save"></i> Simpan bagian ini</button></div>');
                ($els.length ? $els.last() : $(this)).after($bar);
            });
        })();

        // Sebelum baris dibuat, hanya bagian "Kejadian Fraud" yang bisa disimpan
        function syncSectionButtons() {
            var hasId = !!$('#f-id').val();
            $('.btn-save-section').each(function () {
                var locked = !hasId && $(this).data('section') !== 'kejadian';
                $(this).prop('disabled', locked)
                    .attr('title', locked ? 'Simpan bagian "Kejadian Fraud" terlebih dahulu' : '');
            });
        }
        syncSectionButtons();

        $(document).on('click', '.btn-save-section', function () {
            var $btn = $(this), key = $btn.data('section'), fields = SECTION_FIELDS[key] || [];
            var $state = $btn.siblings('.section-save-state');
            var all = readForm(), data = { section: key }, id = $('#f-id').val();

            for (var i = 0; i < fields.length; i++) data[fields[i]] = all[fields[i]];
            if (id) data.id = id;

            var kurang = validateForm(data, fields);
            if (kurang.length) {
                $state.removeClass('ok').addClass('err').text('Belum lengkap: ' + kurang.slice(0, 4).join(', '));
                var el = document.getElementById('f-' + _firstMissing);
                if (el) { try { el.focus(); } catch (e) { } }
                return;
            }

            var restore = btnLoading($btn);
            doPost('Penerapansafst/simpanSection', data, function (res) {
                restore();
                if (res.status === 'success') {
                    var t = new Date();
                    $('#f-id').val(res.id);
                    $('#form-modal-title').html('<i class="fas fa-edit"></i> Ubah Data Kejadian Fraud');
                    $('#form-ubah-warning').show();
                    $('#form-err').hide();
                    $state.removeClass('err').addClass('ok')
                        .text('\u2713 Tersimpan ' + ('0' + t.getHours()).slice(-2) + ':' + ('0' + t.getMinutes()).slice(-2));
                    _needReload = true;
                    syncSectionButtons();
                    toast(res.message || 'Bagian form tersimpan');
                } else {
                    $state.removeClass('ok').addClass('err').text(res.message || 'Gagal menyimpan');
                }
            }, function (m) {
                restore();
                syncSectionButtons();
                $state.removeClass('ok').addClass('err').text(m);
            });
        });

        $('#modalForm').on('hidden.bs.modal', function () {
            if (_needReload) {
                _needReload = false;
                loadData();
                refreshApprovalStatus();
            }
        });

        // Hapus
        $(document).on('click', '.btn-hapus', function () {
            var d = $(this).data();
            $('#h-id').val(d.id);
            $('#h-idkejadian').text(d.idkejadian || '-');
            $('#h-namapelaku').text(d.namapelaku || '-');
            $('#modalHapus').modal('show');
        });

        var execHapus = debounce(function () {
            var restore = btnLoading($('#btn-konfirmasi-hapus'));
            doPost('Penerapansafst/hapus', { id: $('#h-id').val() }, function (res) {
                restore();
                $('#modalHapus').modal('hide');
                if (res.status === 'success') {
                    toast(res.message || 'Data berhasil dihapus');
                    loadData();
                    refreshApprovalStatus();
                } else {
                    toast(res.message || 'Gagal menghapus data', 'error');
                }
            }, function (m) { restore(); toast(m, 'error'); });
        }, 400);

        // Approval (level: dekom / direksi)
        var execApproveKom = debounce(function () {
            var restore = btnLoading($('#btn-konfirmasi-approve-kom'));
            var url = $('#ak-action').val() === 'approve'
                ? 'Penerapansafst/approveKomRow' : 'Penerapansafst/unapproveKomRow';

            doPost(url, { id: $('#ak-id').val(), level: $('#ak-level').val() }, function (res) {
                restore();
                $('#modalApproveKom').modal('hide');
                if (res.status === 'success') {
                    toast(res.message);
                    loadData();
                    refreshApprovalStatus();
                } else {
                    toast(res.message || 'Gagal menyimpan approval', 'error');
                }
            }, function (m) { restore(); toast(m, 'error'); });
        }, 400);

        $(document).on('click', '.btn-toggle-kom', function () {
            var d = $(this).data(), ap = d.action === 'approve', lbl = LEVEL_LABEL[d.level] || '';
            $('#ak-id').val(d.id);
            $('#ak-action').val(d.action);
            $('#ak-level').val(d.level);
            $('#ak-title').text('Approval ' + lbl);
            $('#ak-idkejadian').text(d.idkejadian || '-');
            $('#ak-namapelaku').text(d.namapelaku || '-');
            $('#ak-btn-label').text(ap ? 'Setujui' : 'Batalkan');
            $('#btn-konfirmasi-approve-kom').removeClass('btn-success btn-danger').addClass(ap ? 'btn-success' : 'btn-danger');
            $('#ak-msg').html(ap
                ? '<i class="fas fa-check-circle text-success"></i> Anda akan <strong>menyetujui</strong> data ini sebagai <strong>' + lbl + '</strong>:'
                : '<i class="fas fa-ban text-danger"></i> Anda akan <strong>membatalkan approval ' + lbl + '</strong> data ini:');
            $('#modalApproveKom').modal('show');
        });

        function bulkApproval(url, level, msg) {
            if (!confirm(msg)) return;
            doPost(url, { level: level }, function (res) {
                toast(res.message, res.status === 'success' ? 'success' : 'error');
                loadData();
                refreshApprovalStatus();
            });
        }

        $(document).on('click', '.btn-approve-all', throttle(function () {
            var lv = $(this).data('level');
            bulkApproval('Penerapansafst/approveSemuaKom', lv,
                'Setujui SEMUA data yang memerlukan approval ' + LEVEL_LABEL[lv] + '?');
        }, 2000));

        $(document).on('click', '.btn-unapprove-all', throttle(function () {
            var lv = $(this).data('level');
            bulkApproval('Penerapansafst/unapproveSemuaKom', lv,
                'Batalkan SEMUA approval ' + LEVEL_LABEL[lv] + '?');
        }, 2000));

        // Tidak Ada Data
        $('#btn-nodata').on('click', function () {
            if (!confirm('Konfirmasi: tandai bahwa tidak ada kejadian fraud yang dilaporkan pada periode ini?')) return;
            var restore = btnLoading($(this));
            doPost('Penerapansafst/setNodataAjax', {}, function (res) {
                restore();
                if (res.status === 'success') { toast(res.message); loadData(); refreshApprovalStatus(); }
                else toast(res.message || 'Gagal', 'error');
            }, function (m) { restore(); toast(m, 'error'); });
        });

        $('#btn-batal-nodata').on('click', function () {
            if (!confirm('Batalkan status "Tidak Ada Data"?')) return;
            var restore = btnLoading($(this));
            doPost('Penerapansafst/batalNodataAjax', {}, function (res) {
                restore();
                if (res.status === 'success') { toast(res.message); loadData(); refreshApprovalStatus(); }
                else toast(res.message || 'Gagal', 'error');
            }, function (m) { restore(); toast(m, 'error'); });
        });

        // Impor Excel
        var _importPreview = [];

        $(document).on('click', '#btn-show-import', function () {
            $('#import-file').val('');
            $('#import-step-preview').hide();
            $('#import-step-upload').show();
            $('#import-upload-err').hide();
            $('#btn-simpan-import,#btn-parse-import').hide();
            _importPreview = [];
            $('#modalImport').modal('show');
        });

        $(document).on('change', '#import-file', function () {
            $('#btn-parse-import').toggle(!!this.files.length);
        });

        $('#btn-parse-import').on('click', function () {
            var f = $('#import-file')[0].files[0];
            if (!f) return;
            var fd = new FormData();
            fd.append('file', f);
            fd.append(CSRF, HASH);
            var restore = btnLoading($(this));
            $.ajax({
                url: BASE + '/Penerapansafst/previewImportExcel',
                method: 'POST', data: fd, processData: false, contentType: false,
                dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) {
                    restore(); updateCsrf(res);
                    if (res.status !== 'success') { $('#import-upload-err').text(res.message).show(); return; }
                    _importPreview = res.preview;
                    renderImportPreview(res);
                    $('#import-step-upload').hide();
                    $('#import-step-preview').show();
                    $('#btn-parse-import').hide();
                    $('#btn-simpan-import').toggle(res.ok_count > 0);
                },
                error: function (xhr) {
                    restore();
                    $('#import-upload-err').text(parseErr(xhr) || 'Gagal memproses file').show();
                }
            });
        });

        function renderImportPreview(res) {
            $('#import-ok-count').text(res.ok_count + ' baris siap disimpan');
            $('#import-total-count').text('dari ' + res.total + ' baris terbaca');
            var html = '';
            for (var i = 0; i < res.preview.length; i++) {
                var r = res.preview[i];
                var badge = r.status === 'ok'
                    ? '<span class="badge badge-success" title="' + esc(r.dup_reason || '') + '">Siap</span>'
                    : '<span class="badge badge-danger" title="' + esc((r.errors || []).join(', ')) + '">Error</span>';
                html += '<tr><td>' + esc(r.line) + '</td><td>' + esc(r.idkejadian) + '</td><td>'
                    + esc(r.jenisfraud) + '</td><td>' + esc(r.namapelaku) + '</td><td>' + badge + '</td></tr>';
            }
            document.querySelector('#import-preview-table tbody').innerHTML = html;
        }

        $('#btn-simpan-import').on('click', function () {
            var okRows = _importPreview.filter(function (r) { return r.status === 'ok'; });
            if (!okRows.length) return;
            var restore = btnLoading($(this));
            doPost('Penerapansafst/confirmImportExcel', { rows: JSON.stringify(okRows) }, function (res) {
                restore();
                if (res.status === 'success') {
                    $('#modalImport').modal('hide');
                    toast(res.message);
                    loadData();
                    refreshApprovalStatus();
                } else {
                    toast(res.message, 'error');
                }
            }, function (m) { restore(); toast(m, 'error'); });
        });

        $('#btn-retry').on('click', function () { loadData(); refreshApprovalStatus(); });
        $('#btn-konfirmasi-hapus').on('click', execHapus);
        $('#btn-konfirmasi-approve-kom').on('click', execApproveKom);

        $(document).on('hidden.bs.modal', '.modal', function () {
            $(this).find('.alert-danger').hide().text('');
        });

        loadData();
        refreshApprovalStatus();
        var pollTimer = setInterval(function () {
            if (document.hidden) return;
            refreshApprovalStatus();
        }, POLL_MS);

        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) refreshApprovalStatus();
        });

        $(window).on('beforeunload', function () { clearInterval(pollTimer); });
    }
</script>

<style>
    /* Tampilan ponsel: badge approval sejajar penuh, header kartu boleh turun baris */
    @media (max-width: 576px) {
        .approval-badge-bar { flex-direction: column; align-items: stretch; }
        .approval-badge-bar .approval-status-badge { min-width: 0; width: 100%; margin: 0 !important; }
        .card-header.d-flex { flex-wrap: wrap; gap: 10px; }
        .card-header.d-flex > h5 { flex: 1 1 100%; font-size: 1rem; line-height: 1.35; }
    }
</style>

<?= view('templates/komentar', ['komentarPage' => 'Penerapansafst']) ?>
