<div id="toast-wrap" style="position:fixed;top:20px;right:20px;z-index:99999;min-width:290px;pointer-events:none;">
</div>

<div class="container-fluid">
    <a href="<?= base_url('ShowTransparansi') ?>" class="btn-kembali mb-3">
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

    <?php
    $allAccdekomApproved = !empty($accdekomData) && array_reduce($accdekomData, fn($c, $i) => $c && $i['accdekom'] == 1, true);
    $allAccdirutApproved = !empty($accdirutData) && array_reduce($accdirutData, fn($c, $i) => $c && $i['is_approved'] == 1, true);
    $lastDekomAt = $allAccdekomApproved && !empty($accdekomData)
        ? date('d/m/Y H:i', strtotime($accdekomData[count($accdekomData) - 1]['accdekom_at']))
        : '';
    $lastDirutAt = $allAccdirutApproved && !empty($accdirutData)
        ? date('d/m/Y H:i', strtotime($accdirutData[0]['approved_at']))
        : '';
    ?>

    <div class="card card-body mb-3 shadow-sm">
        <div class="page-header">
            <div class="approval-badges-row mb-3">
                <div class="approval-status-badge <?= $allAccdekomApproved ? 'badge-status-success' : 'badge-status-secondary' ?>"
                    id="badge-dekom">
                    <i class="fas <?= $allAccdekomApproved ? 'fa-check-circle' : 'fa-clock' ?>"></i>
                    <div>
                        <span class="approval-status-label">Komisaris Utama</span><br>
                        <span class="approval-status-sub">
                            <?= $allAccdekomApproved ? 'Disetujui &middot; ' . $lastDekomAt : 'Belum disetujui seluruhnya' ?>
                        </span>
                    </div>
                </div>
                <div class="approval-status-badge <?= $allAccdirutApproved ? 'badge-status-success' : 'badge-status-secondary' ?>"
                    id="badge-dirut">
                    <i class="fas <?= $allAccdirutApproved ? 'fa-check-circle' : 'fa-clock' ?>"></i>
                    <div>
                        <span class="approval-status-label">Direktur Utama</span><br>
                        <span class="approval-status-sub">
                            <?= $allAccdirutApproved ? 'Disetujui &middot; ' . $lastDirutAt : 'Belum disetujui seluruhnya' ?>
                        </span>
                    </div>
                </div>
            </div>
            <h1 class="page-title"><?= esc($judul) ?></h1>
            <div class="page-subtitle">Tahun <?= esc($periodeDetail['tahun']) ?></div>
            <span class="badge-namabpr"><?= esc($bprData['namabpr'] ?? 'Nama BPR') ?></span>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center" style="background:var(--grad);">
            <h5 class="m-0 font-weight-bold text-white">
                <i class="fas fa-sitemap"></i> Struktur, Keanggotaan, Keahlian, dan Independensi Anggota Komite
            </h5>
            <div class="d-flex align-items-center" style="gap:8px;">
                <?php if (($userInGroupPE ?? false) || ($userInGroupAdmin ?? false)): ?>
                    <button type="button" class="btn-primaryy" id="btn-show-tambah" title="Tambah Data">
                        <i class="fas fa-plus"></i>
                    </button>
                <?php endif; ?>
                <?php if (($userInGroupAdmin || $userInGroupDekom || $userInGroupDireksi || $userInGroupPE || $userInGroupDekom2 || $userInGroupDireksi2) ?? false): ?>
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
                <?php for ($i = 0; $i < 2; $i++): ?>
                    <div class="dir-card-skel">
                        <div class="skel-card-header"></div>
                        <div class="skel-card-body">
                            <div class="skel" style="width:60%;height:18px;margin-bottom:10px;"></div>
                            <div class="skel" style="width:80%;height:13px;margin-bottom:8px;"></div>
                            <div class="skel" style="width:45%;height:13px;margin-bottom:8px;"></div>
                            <div class="skel" style="width:70%;height:13px;"></div>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
            <div id="error-state" class="empty-state-box" style="display:none;">
                <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3 d-block"></i>
                <h6 class="text-muted mb-1">Gagal memuat data</h6>
                <p class="text-muted small mb-3">Terjadi kesalahan saat mengambil data.</p>
                <button type="button" class="btn btn-outline-primary btn-sm" id="btn-retry">
                    <i class="fas fa-redo"></i> Coba Lagi
                </button>
            </div>

            <!-- empty state -->
            <div id="empty-state" class="empty-state-box" style="display:none;">
                <div id="empty-null-record" style="display:none;">
                    <i class="fas fa-minus-circle fa-3x text-secondary mb-3 d-block"></i>
                    <h6 class="text-muted mb-1">Tidak ada data untuk periode ini.</h6>
                    <p class="text-muted small mb-3">Data anggota komite dinyatakan kosong untuk periode ini.</p>
                    <?php if (($userInGroupPE ?? false) || ($userInGroupAdmin ?? false)): ?>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="btn-batal-nodata">
                            <i class="fas fa-undo"></i> Batalkan "Tidak Ada Data"
                        </button>
                    <?php endif; ?>
                </div>
                <div id="empty-no-action" style="display:none;">
                    <i class="fas fa-users-slash fa-3x text-muted mb-3 d-block"></i>
                    <h6 class="text-muted mb-1">Belum ada data Anggota Komite</h6>
                    <p class="text-muted small mb-3">Data untuk periode ini belum diinput.</p>
                    <?php if (($userInGroupPE ?? false) || ($userInGroupAdmin ?? false)): ?>
                        <button type="button" class="btn btn-warning btn-sm mt-1" id="btn-nodata">
                            <i class="fas fa-times-circle"></i> Tidak Ada Data
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div id="table-wrapper" style="display:none;">
                <div id="cards-komite" class="dir-cards-grid"></div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3" style="background:var(--grad);">
            <h5 class="m-0 font-weight-bold text-white"><i class="fas fa-tasks"></i> Tindak Lanjut Rekomendasi</h5>
        </div>
        <div class="card-body p-3" id="tindak-body">
            <?php if (($userInGroupPE ?? false) || ($userInGroupAdmin ?? false)): ?>
                <div class="mb-3" id="tindak-btn-wrap">
                    <?php if (empty($penjelastindak)): ?>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal"
                            data-target="#modalTambahTindak">
                            <i class="fas fa-plus"></i> Tambah Tindak Lanjut
                        </button>
                    <?php else: ?>
                        <?php foreach ($penjelastindak as $row): ?>
                            <button type="button" class="btn btn-sm btn-outline-warning" data-toggle="modal"
                                data-target="#modalEditTindak" data-id="<?= esc($row['id']) ?>"
                                data-tindaklanjut="<?= esc($row['tindaklanjut'] ?? '') ?>">
                                <i class="fas fa-edit"></i> Ubah Tindak Lanjut
                            </button>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div id="tindak-content-wrap">
                <?php if (empty($penjelastindak)): ?>
                    <div class="tindak-empty"><i class="fas fa-inbox fa-2x text-muted mb-2 d-block"></i><span
                            class="text-muted">Belum ada data tindak lanjut.</span></div>
                <?php else: ?>
                    <?php foreach ($penjelastindak as $row): ?>
                        <div class="tindak-card">
                            <div class="tindak-section">
                                <div class="tindak-label"><i class="fas fa-check-circle text-success"></i> Tindak Lanjut
                                    Rekomendasi Struktur, Keanggotaan, Keahlian, dan Independensi Anggota Komite</div>
                                <div class="tindak-content">
                                    <?php if (!empty($row['tindaklanjut'])): ?>
                                        <?= nl2br(esc($row['tindaklanjut'])) ?>
                                        <?php if (($userInGroupPE ?? false) || ($userInGroupAdmin ?? false)): ?>
                                            <a href="#" class="btn-icon-del btn-del-tindak" data-id="<?= esc($row['id']) ?>"
                                                title="Hapus"><i class="fas fa-trash-alt"></i></a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic">Belum diisi</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="approval-actions-row mt-3">
        <?php if (($userInGroupAdmin ?? false) || ($userInGroupDekom ?? false)): ?>
            <div class="action-card card shadow-sm" id="action-dekom">
                <div class="card-body py-2 text-center">
                    <small class="d-block font-weight-bold text-dark mb-2"><i class="fas fa-user-tie text-success"></i>
                        Komisaris Utama</small>
                    <div class="d-flex justify-content-center flex-wrap" style="gap:4px;">
                        <a href="<?= base_url('Strukturkomite/approveSemuaKom') ?>"
                            onclick="return confirm('Setujui semua data sebagai Komisaris Utama?')"
                            class="btn <?= $allAccdekomApproved ? 'btn-success' : 'btn-primary' ?> btn-sm"
                            id="btn-approve-dekom">
                            <i class="fas fa-check-double"></i> Setujui
                        </a>
                        <a href="<?= base_url('Strukturkomite/unapproveSemuaKom') ?>"
                            onclick="return confirm('Batalkan semua approval Komisaris Utama?')"
                            class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-ban"></i> Batal
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <?php if (($userInGroupAdmin ?? false) || ($userInGroupDireksi ?? false)): ?>
            <div class="action-card card shadow-sm <?= (!($canApprove ?? false)) ? 'blk-disabled' : '' ?>"
                id="action-dirut">
                <div class="card-body py-2 text-center">
                    <small class="d-block font-weight-bold text-dark mb-2"><i class="fas fa-user-shield text-primary"></i>
                        Direktur Utama</small>
                    <?php if (!($canApprove ?? false)): ?>
                        <small class="text-muted d-block mb-1" style="font-size:10px;"><i class="fas fa-lock"></i> Komisaris
                            harus setuju dulu</small>
                    <?php endif; ?>
                    <div class="d-flex justify-content-center flex-wrap" style="gap:4px;">
                        <a href="<?= base_url('Strukturkomite/approveSemuaDirut') ?>"
                            onclick="return confirm('Setujui semua data sebagai Direktur Utama?')"
                            class="btn <?= $allAccdirutApproved ? 'btn-success' : 'btn-primary' ?> btn-sm"
                            id="btn-approve-dirut">
                            <i class="fas fa-check-double"></i> Setujui
                        </a>
                        <a href="<?= base_url('Strukturkomite/unapproveSemuaDirut') ?>"
                            onclick="return confirm('Batalkan semua approval Direktur Utama?')"
                            class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-ban"></i> Batal
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="cardpilihfaktor mb-4 mt-4">
        <div class="cardpilihfaktor-header">
            <h6>Pilih Halaman</h6>
        </div>
        <div class="cardpilihfaktor-body">
            <div class="nav-pages-wrap">
                <?php
                $pages = [
                    ['url' => 'Penjelasanumum', 'label' => '1'],
                    ['url' => 'Tgjwbdir', 'label' => '2'],
                    ['url' => 'Tgjwbdekom', 'label' => '3'],
                    ['url' => 'Tgjwbkomite', 'label' => '4'],
                    ['url' => 'Strukturkomite', 'label' => '5'],
                    ['url' => 'Sahamdirdekom', 'label' => '6'],
                    ['url' => 'Shmusahadirdekom', 'label' => '7'],
                    ['url' => 'Shmdirdekomlain', 'label' => '8'],
                    ['url' => 'Keuangandirdekompshm', 'label' => '9'],
                    ['url' => 'Keluargadirdekompshm', 'label' => '10'],
                    ['url' => 'Paketkebijakandirdekom', 'label' => '11'],
                    ['url' => 'Rasiogaji', 'label' => '12'],
                    ['url' => 'Rapat', 'label' => '13'],
                    ['url' => 'Kehadirandekom', 'label' => '14'],
                    ['url' => 'Fraudinternal', 'label' => '15'],
                    ['url' => 'Masalahhukum', 'label' => '16'],
                    ['url' => 'Transaksikepentingan', 'label' => '17'],
                    ['url' => 'Danasosial', 'label' => '18'],
                ];
                foreach ($pages as $p): ?>
                    <a href="<?= base_url($p['url']) ?>"
                        class="nav-page-btn <?= $p['url'] === 'Strukturkomite' ? 'active' : '' ?>"><?= $p['label'] ?></a>
                <?php endforeach; ?>
            </div>
            <div style="text-align:center;padding:4px 0 8px;">
                <a href="<?= base_url('ShowTransparansi') ?>"
                    style="font-size:12px;color:var(--gray-400);font-weight:600;">Kembali ke halaman transparansi</a>
            </div>
        </div>
    </div>
</div>

<!-- ═══ MODAL TAMBAH / UBAH (shared) ═══ -->
<div class="modal fade" id="modalTambah" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header modal-header-dir">
                <h5 class="modal-title" id="modalTambahTitle"><i class="fas fa-plus-circle"></i> Tambah Data Anggota
                    Komite</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="t-id">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Nama Anggota Komite <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="t-anggota" placeholder="Masukkan nama anggota"
                                maxlength="255" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">NIK <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="t-nik" maxlength="16" placeholder="16 digit NIK"
                                autocomplete="off" inputmode="numeric">
                            <small id="nik-counter" class="form-text text-muted">0 / 16 digit</small>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold">Keahlian <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="t-keahlian" rows="4"
                        placeholder="Tuliskan keahlian (satu per baris)..."></textarea>
                    <small class="text-muted"><i class="fas fa-lightbulb text-warning"></i> Pisahkan setiap poin dengan
                        baris baru (Enter)</small>
                </div>
                <div class="row">
                    <?php foreach ([
                        ['id' => 't-jbtaudit', 'label' => 'Jabatan Komite Audit'],
                        ['id' => 't-jbtpantauresiko', 'label' => 'Jabatan Komite Pemantau Risiko'],
                        ['id' => 't-jbtremunerasi', 'label' => 'Jabatan Komite Remunerasi &amp; Nominasi'],
                        ['id' => 't-jbtmanrisk', 'label' => 'Jabatan Komite Manajemen Risiko'],
                        ['id' => 't-jbtlain', 'label' => 'Jabatan Komite Lainnya'],
                    ] as $s): ?>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold"><?= $s['label'] ?> <span
                                        class="text-danger">*</span></label>
                                <select class="form-control" id="<?= $s['id'] ?>">
                                    <option value="">-- Pilih --</option>
                                    <option value="00">00. Tidak menjabat</option>
                                    <option value="01">01. Ketua</option>
                                    <option value="02">02. Anggota</option>
                                </select>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Pihak Independen? <span class="text-danger">*</span></label>
                            <select class="form-control" id="t-independen">
                                <option value="">-- Pilih --</option>
                                <option value="1">Ya</option>
                                <option value="2">Tidak</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div id="tambah-err" class="alert alert-danger mt-2" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i>
                    Batal</button>
                <button type="button" class="btn btn-primary" id="btn-simpan-tambah"><i class="fas fa-save"></i> Simpan
                    Data</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══ MODAL HAPUS ═══ -->
<div class="modal fade" id="modalHapus" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Konfirmasi Hapus</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="h-id">
                <p class="mb-2">Anda akan menghapus data anggota komite berikut:</p>
                <table class="table table-bordered table-sm mb-3">
                    <tr>
                        <th width="35%">Nama</th>
                        <td id="h-nama"></td>
                    </tr>
                    <tr>
                        <th>NIK</th>
                        <td id="h-nik"></td>
                    </tr>
                </table>
                <div class="alert alert-danger mb-0"><strong>Peringatan:</strong> Data yang dihapus tidak dapat
                    dikembalikan.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i>
                    Batal</button>
                <button type="button" id="btn-konfirmasi-hapus" class="btn btn-danger"><i class="fas fa-trash-alt"></i>
                    Hapus</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══ MODAL TAMBAH TINDAK ═══ -->
<div class="modal fade" id="modalTambahTindak" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header modal-header-dir">
                <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Tambah Tindak Lanjut</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="font-weight-bold">Tindak Lanjut Rekomendasi <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="tt-tindaklanjut" rows="5"
                        placeholder="Tuliskan tindak lanjut rekomendasi..."></textarea>
                </div>
                <div id="tindak-tambah-err" class="alert alert-danger mt-2" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i>
                    Batal</button>
                <button type="button" class="btn btn-primary" id="btn-simpan-tindak"><i class="fas fa-save"></i>
                    Simpan</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══ MODAL EDIT TINDAK ═══ -->
<div class="modal fade" id="modalEditTindak" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header modal-header-dir">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Ubah Tindak Lanjut</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="et-id">
                <div class="form-group">
                    <label class="font-weight-bold">Tindak Lanjut Rekomendasi</label>
                    <textarea class="form-control" id="et-tindaklanjut" rows="5"></textarea>
                </div>
                <div id="tindak-edit-err" class="alert alert-danger mt-2" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i>
                    Batal</button>
                <button type="button" class="btn btn-primary" id="btn-simpan-edit-tindak"><i class="fas fa-save"></i>
                    Simpan Perubahan</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══ MODAL KOMENTAR ═══ -->
<div class="modal fade" id="modalKomentar" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content komentar-modal-content">
            <div class="modal-header komentar-modal-header">
                <div class="komentar-modal-title-wrap">
                    <div class="komentar-modal-icon-wrap"><i class="fas fa-comments"></i></div>
                    <div>
                        <div class="komentar-modal-title">Komentar — Struktur &amp; Keanggotaan Komite</div>
                    </div>
                </div>
                <button type="button" class="komentar-close-btn" data-dismiss="modal"><i
                        class="fas fa-times"></i></button>
            </div>
            <div class="modal-body komentar-modal-body">
                <div class="komentar-list-label"><i class="fas fa-history"></i> Riwayat Komentar</div>
                <div id="komentar-list-wrapper" class="komentar-list-wrapper">
                    <div id="komentar-list">
                        <div class="komentar-loading"><i class="fas fa-spinner fa-spin"></i> Memuat...</div>
                    </div>
                </div>
                <?php if (($userInGroupAdmin || $userInGroupDekom || $userInGroupDireksi || $userInGroupPE || $userInGroupDekom2 || $userInGroupDireksi2) ?? false): ?>
                    <div class="komentar-input-wrap">
                        <div class="komentar-input-label"><i class="fas fa-pen-alt"></i> Tulis Komentar</div>
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
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal"><i class="fas fa-times"></i>
                    Tutup</button>
                <?php if (($userInGroupAdmin || $userInGroupDekom || $userInGroupDireksi || $userInGroupPE || $userInGroupDekom2 || $userInGroupDireksi2) ?? false): ?>
                    <button type="button" class="btn btn-primary btn-sm" id="btn-kirim-komentar"><i
                            class="fas fa-paper-plane"></i> Kirim</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$_canEdit = (($userInGroupPE ?? false) || ($userInGroupAdmin ?? false)) ? 'true' : 'false';
$_canComment = (($userInGroupAdmin || $userInGroupDekom || $userInGroupDireksi || $userInGroupPE || $userInGroupDekom2 || $userInGroupDireksi2) ?? false) ? 'true' : 'false';

$jsRows = [];
if (!empty($strukturkomite)) {
    foreach ($strukturkomite as $r) {
        $jsRows[] = [
            'id' => (int) $r['id'],
            'anggotakomite' => $r['anggotakomite'] ?? '',
            'nikkomite' => $r['nikkomite'] ?? '',
            'keahlian' => $r['keahlian'] ?? '',
            'jbtaudit' => $r['jbtaudit'] ?? '00',
            'jbtpantauresiko' => $r['jbtpantauresiko'] ?? '00',
            'jbtremunerasi' => $r['jbtremunerasi'] ?? '00',
            'jbtmanrisk' => $r['jbtmanrisk'] ?? '00',
            'jbtlain' => $r['jbtlain'] ?? '00',
            'independen' => (int) ($r['independen'] ?? 2),
            'accdekom' => (int) ($r['accdekom'] ?? 0),
            'accdekom_at' => $r['accdekom_at'] ?? '',
            'is_approved' => (int) ($r['is_approved'] ?? 0),
            'approved_at' => $r['approved_at'] ?? '',
            'is_null_record' => (int) ($r['is_null_record'] ?? 0),
        ];
    }
}
?>
<script>
    (function () {
        'use strict';

        var CFG = {
            base: '<?= rtrim(base_url(), '/') ?>',
            csrfName: '<?= csrf_token() ?>',
            csrfHash: '<?= csrf_hash() ?>',
            uid: <?= (int) ($userId ?? 0) ?>,
            fname: <?= json_encode($fullname ?? 'Unknown', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
            kbpr: <?= json_encode($kodebpr ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
            pid: <?= (int) ($periodeId ?? 0) ?>,
            canEdit: <?= $_canEdit ?>,
            canComment: <?= $_canComment ?>,
            rows: <?= json_encode($jsRows, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
            pollBase: <?= (int) ($pollIntervalMs ?? 10000) ?>
        };

        function ready(fn) {
            if (typeof jQuery !== 'undefined') jQuery(fn);
            else setTimeout(function () { ready(fn); }, 40);
        }

        ready(function ($) {

            function esc(s) {
                return s == null ? '' : String(s)
                    .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
            }
            function debounce(fn, ms) {
                var t; return function () { var a = arguments, c = this; clearTimeout(t); t = setTimeout(function () { fn.apply(c, a); }, ms); };
            }
            function toast(msg, type) {
                type = type || 'success';
                var ic = { success: 'check-circle', error: 'exclamation-circle', info: 'info-circle' };
                var el = $('<div class="t-item t-' + type + '"><i class="fas fa-' + ic[type] + ' fa-lg"></i><div>' + esc(msg) + '</div></div>');
                $('#toast-wrap').append(el);
                setTimeout(function () { el.fadeOut(400, function () { $(this).remove(); }); }, 3500);
            }
            function readCsrf() {
                var m = document.cookie.match(/ci_csrf_token=([^;]+)/);
                if (m) CFG.csrfHash = decodeURIComponent(m[1]);
            }
            function csrf() { readCsrf(); var d = {}; d[CFG.csrfName] = CFG.csrfHash; return d; }
            function updateCsrf(res) { readCsrf(); if (res && res.csrf_hash) CFG.csrfHash = res.csrf_hash; }
            function btnLoading(btn) {
                var ori = btn.html(); btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
                return function () { btn.html(ori).prop('disabled', false); };
            }
            function timeAgo(s) {
                if (!s) return '-';
                var d = new Date(s.replace(' ', 'T')), diff = Math.floor((Date.now() - d.getTime()) / 60000);
                if (isNaN(diff) || diff < 1) return 'Baru saja';
                if (diff < 60) return diff + ' menit lalu';
                if (diff < 1440) return Math.floor(diff / 60) + ' jam lalu';
                return ('0' + d.getDate()).slice(-2) + '/' + ('0' + (d.getMonth() + 1)).slice(-2) + '/' + d.getFullYear();
            }

            function syncApprovalUI(rows) {
                if (!rows || !rows.length) {
                    setAppBadge('badge-dekom', false, '');
                    setAppBadge('badge-dirut', false, '');
                    setAppBtn('btn-approve-dekom', false);
                    setAppBtn('btn-approve-dirut', false);
                    return;
                }
                var allDekom = rows.every(function (r) { return parseInt(r.accdekom) === 1; });
                var allDirut = rows.every(function (r) { return parseInt(r.is_approved) === 1; });

                // Ambil timestamp dari row terakhir untuk dekom, row pertama untuk dirut
                var lastDekomTs = '';
                var lastDirutTs = '';

                if (allDekom) {
                    var dekomRow = rows[rows.length - 1];
                    if (dekomRow.accdekom_at) {
                        lastDekomTs = formatTs(dekomRow.accdekom_at);
                    }
                }
                if (allDirut) {
                    var dirutRow = rows[0];
                    if (dirutRow.approved_at) {
                        lastDirutTs = formatTs(dirutRow.approved_at);
                    }
                }

                setAppBadge('badge-dekom', allDekom, lastDekomTs);
                setAppBadge('badge-dirut', allDirut, lastDirutTs);
                setAppBtn('btn-approve-dekom', allDekom);
                setAppBtn('btn-approve-dirut', allDirut);
                $('#action-dirut').toggleClass('blk-disabled', !allDekom);
            }

            function formatTs(s) {
                if (!s) return '';
                var d = new Date(s.replace(' ', 'T'));
                if (isNaN(d.getTime())) return '';
                var dd = ('0' + d.getDate()).slice(-2);
                var mm = ('0' + (d.getMonth() + 1)).slice(-2);
                var yyyy = d.getFullYear();
                var hh = ('0' + d.getHours()).slice(-2);
                var ii = ('0' + d.getMinutes()).slice(-2);
                return dd + '/' + mm + '/' + yyyy + ' ' + hh + ':' + ii;
            }

            var _inFlight = {};
            function request(key, opts, allowDupe) {
                if (!allowDupe && _inFlight[key]) { try { _inFlight[key].abort(); } catch (e) { } }
                var ctrl = { aborted: false, abort: function () { this.aborted = true; } };
                _inFlight[key] = ctrl;
                return $.ajax(opts).always(function () { if (_inFlight[key] === ctrl) delete _inFlight[key]; });
            }

            function ajaxCrud(endpoint, postData, errEl, btnEl, onSuccess) {
                var restore = btnLoading(btnEl);
                $(errEl).hide().text('');
                request(endpoint, {
                    url: CFG.base + '/' + endpoint, method: 'POST', dataType: 'json',
                    data: $.extend({}, csrf(), postData),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function (res) { restore(); updateCsrf(res); if (res.status === 'success') { onSuccess(res); } else { $(errEl).text(res.message || 'Terjadi kesalahan').show(); } },
                    error: function (xhr) {
                        restore();
                        var m = 'Terjadi kesalahan server.';
                        try { var r = JSON.parse(xhr.responseText); if (r && r.message) m = r.message; } catch (e) { }
                        if (xhr.status === 401 || xhr.status === 403) { m = 'Sesi kedaluwarsa — halaman akan di-refresh…'; setTimeout(function () { location.reload(); }, 2000); }
                        $(errEl).text(m).show();
                    }
                }, false);
            }

            var _rows = CFG.rows.slice();
            var _cardMap = {};

            function rowHash(r) {
                return [r.id, r.anggotakomite, r.nikkomite, r.keahlian,
                r.jbtaudit, r.jbtpantauresiko, r.jbtremunerasi,
                r.jbtmanrisk, r.jbtlain, r.independen,
                r.accdekom, r.is_approved].join('|');
            }

            function jabLabel(code) {
                if (code === '01') return '<span style="color:var(--brand);font-weight:700;">Ketua</span>';
                if (code === '02') return '<span style="color:var(--gray-700);">Anggota</span>';
                return '<span style="color:var(--gray-400);">Tidak menjabat</span>';
            }

            function buildCard(r, idx) {
                var ok = (parseInt(r.accdekom) === 1) && (parseInt(r.is_approved) === 1);
                var isYa = (String(r.independen) === '1');
                var keahlianLines = '';
                if (r.keahlian) {
                    r.keahlian.split('\n').forEach(function (l) {
                        l = l.trim(); if (l) keahlianLines += '<li>' + esc(l) + '</li>';
                    });
                }
                if (!keahlianLines) keahlianLines = '<li class="text-muted">Belum ada data</li>';

                var actions = '';
                if (CFG.canEdit) {
                    actions = '<button class="btn btn-sm btn-warning btn-ubah dcard-btn" title="Ubah"'
                        + ' data-id="' + esc(r.id) + '" data-anggota="' + esc(r.anggotakomite) + '"'
                        + ' data-nik="' + esc(r.nikkomite) + '" data-keahlian="' + esc(r.keahlian) + '"'
                        + ' data-jbtaudit="' + esc(r.jbtaudit) + '" data-jbtpantauresiko="' + esc(r.jbtpantauresiko) + '"'
                        + ' data-jbtremunerasi="' + esc(r.jbtremunerasi) + '" data-jbtmanrisk="' + esc(r.jbtmanrisk) + '"'
                        + ' data-jbtlain="' + esc(r.jbtlain) + '" data-independen="' + esc(r.independen) + '">'
                        + '<i class="fas fa-pencil-alt"></i></button>'
                        + '<button class="btn btn-sm btn-danger btn-hapus dcard-btn" title="Hapus"'
                        + ' data-id="' + esc(r.id) + '" data-nama="' + esc(r.anggotakomite) + '" data-nik="' + esc(r.nikkomite) + '">'
                        + '<i class="fas fa-trash-alt"></i></button>';
                }

                return '<div class="dir-card" id="kcard-' + r.id + '" data-h="' + esc(rowHash(r)) + '">'
                    + '<div class="dcard-header">'
                    + '<div class="dcard-index-wrap"><span class="dcard-index">' + (idx + 1) + '</span>'
                    + '<div class="dcard-nama">' + esc(r.anggotakomite) + '</div></div>'
                    + '<div class="dcard-actions">' + actions + '</div></div>'
                    + '<div class="dcard-body">'
                    + '<span class="dcard-nik">' + esc(r.nikkomite || '-') + '</span>'
                    + '<div class="dcard-section-label"><i class="fas fa-star" style="margin-right:4px;"></i>Keahlian</div>'
                    + '<ul class="dcard-keahlian-list">' + keahlianLines + '</ul>'
                    + '<div class="dcard-section-label"><i class="fas fa-briefcase" style="margin-right:4px;"></i>Jabatan Komite</div>'
                    + '<div class="dcard-jabatan-grid">'
                    + '<div class="dcard-jabatan-item"><div class="dcard-jabatan-item-label">Audit</div><div class="dcard-jabatan-item-val">' + jabLabel(r.jbtaudit) + '</div></div>'
                    + '<div class="dcard-jabatan-item"><div class="dcard-jabatan-item-label">Pmntau Risiko</div><div class="dcard-jabatan-item-val">' + jabLabel(r.jbtpantauresiko) + '</div></div>'
                    + '<div class="dcard-jabatan-item"><div class="dcard-jabatan-item-label">Remunerasi</div><div class="dcard-jabatan-item-val">' + jabLabel(r.jbtremunerasi) + '</div></div>'
                    + '<div class="dcard-jabatan-item"><div class="dcard-jabatan-item-label">Manj. Risiko</div><div class="dcard-jabatan-item-val">' + jabLabel(r.jbtmanrisk) + '</div></div>'
                    + '<div class="dcard-jabatan-item"><div class="dcard-jabatan-item-label">Lainnya</div><div class="dcard-jabatan-item-val">' + jabLabel(r.jbtlain) + '</div></div>'
                    + '</div>'
                    + '<span class="dcard-independen-badge ' + (isYa ? 'dcard-independen-yes' : 'dcard-independen-no') + '">'
                    + '<i class="fas ' + (isYa ? 'fa-check' : 'fa-times') + '"></i> ' + (isYa ? 'Pihak Independen' : 'Bukan Pihak Independen')
                    + '</span>'
                    + '<div class="dcard-footer">'
                    + '<span class="acc-badge ' + (ok ? 'acc-badge-ok' : 'acc-badge-no') + '">'
                    + '<i class="fas ' + (ok ? 'fa-check-circle' : 'fa-clock') + '"></i> ' + (ok ? 'Disetujui' : 'Menunggu Persetujuan')
                    + '</span></div></div></div>';
            }

            function renderCards(rows) {
                var container = document.getElementById('cards-komite');

                // pisahkan null record dan real record
                var nullRecords = (rows || []).filter(function (r) { return parseInt(r.is_null_record) === 1; });
                var realRecords = (rows || []).filter(function (r) { return !parseInt(r.is_null_record); });

                // tampilkan tombol tambah hanya jika tidak ada null record
                if (CFG.canEdit) {
                    $('#btn-show-tambah').toggle(nullRecords.length === 0);
                }

                if (!rows || !rows.length) {
                    container.innerHTML = '';
                    _cardMap = {};
                    $('#empty-null-record').hide();
                    $('#empty-no-action').show();
                    $('#empty-state').show();
                    $('#table-wrapper').hide();
                    return;
                }

                if (nullRecords.length > 0 && realRecords.length === 0) {
                    container.innerHTML = '';
                    _cardMap = {};
                    $('#empty-no-action').hide();
                    $('#empty-null-record').show();
                    $('#empty-state').show();
                    $('#table-wrapper').hide();
                    return;
                }

                $('#empty-state').hide();
                $('#table-wrapper').show();

                var newIds = realRecords.map(function (r) { return String(r.id); });
                var oldIds = Object.keys(_cardMap);

                oldIds.forEach(function (id) {
                    if (newIds.indexOf(id) === -1) {
                        var el = document.getElementById('kcard-' + id);
                        if (el) el.remove();
                        delete _cardMap[id];
                    }
                });

                var frag = document.createDocumentFragment(), tmp = document.createElement('div');

                realRecords.forEach(function (r, idx) {
                    var id = String(r.id), h = rowHash(r);
                    if (_cardMap[id] === h) {
                        var badge = document.querySelector('#kcard-' + id + ' .dcard-index');
                        if (badge && badge.textContent !== String(idx + 1)) badge.textContent = String(idx + 1);
                        return;
                    }
                    tmp.innerHTML = buildCard(r, idx);
                    var newEl = tmp.firstElementChild;
                    var existing = document.getElementById('kcard-' + id);
                    if (existing) { existing.parentNode.replaceChild(newEl, existing); }
                    else { frag.appendChild(newEl); }
                    _cardMap[id] = h;
                });

                if (frag.childNodes.length) container.appendChild(frag);
                newIds.forEach(function (id) {
                    var el = document.getElementById('kcard-' + id);
                    if (el) container.appendChild(el);
                });
            }

            function applyServerData(rows) {
                _rows = rows || [];
                renderCards(_rows);
                syncApprovalUI(_rows);
            }            

            function setAppBadge(id, approved, ts) {
                var $b = $('#' + id);
                if (approved) {
                    $b.removeClass('badge-status-secondary').addClass('badge-status-success');
                    $b.find('i').removeClass('fa-clock').addClass('fa-check-circle');
                    $b.find('.approval-status-sub').text('Disetujui' + (ts ? ' · ' + ts : ''));
                } else {
                    $b.removeClass('badge-status-success').addClass('badge-status-secondary');
                    $b.find('i').removeClass('fa-check-circle').addClass('fa-clock');
                    $b.find('.approval-status-sub').text('Belum disetujui seluruhnya');
                }
            }

            function setAppBtn(id, approved) {
                var $b = $('#' + id);
                if (approved) { $b.removeClass('btn-primary').addClass('btn-success'); }
                else { $b.removeClass('btn-success').addClass('btn-primary'); }
            }

            function updateNotifBadge(n) {
                n = parseInt(n) || 0;
                var $btn = $('#btn-komentar-global'), $badge = $('#notif-global');
                if (n > 0) { $badge.text(n).show(); $btn.addClass('has-unread'); }
                else { $badge.hide(); $btn.removeClass('has-unread'); }
            }

            var _pollTimer = null, _pollDelay = CFG.pollBase, _lastUnread = 0, _pollAbort = null;

            function doSetNodata(btnEl) {
                if (!confirm('Konfirmasi: Tandai bahwa tidak ada data anggota komite untuk periode ini?')) return;
                var restore = btnLoading(btnEl);
                $.ajax({
                    url: CFG.base + '/Strukturkomite/setNodataAjax', method: 'POST', dataType: 'json',
                    data: csrf(),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function (res) {
                        restore(); updateCsrf(res);
                        if (res.status === 'success') { toast(res.message || 'Data disimpan'); applyServerData(res.data); }
                        else { toast(res.message || 'Gagal', 'error'); }
                    },
                    error: function () { restore(); toast('Terjadi kesalahan server', 'error'); }
                });
            }

            $('#btn-nodata').on('click', function () { doSetNodata($(this)); });

            $(document).on('click', '#btn-batal-nodata', function () {
                if (!confirm('Batalkan status "Tidak Ada Data"?')) return;
                var restore = btnLoading($(this));
                $.ajax({
                    url: CFG.base + '/Strukturkomite/batalNodataAjax', method: 'POST', dataType: 'json',
                    data: csrf(),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function (res) {
                        restore(); updateCsrf(res);
                        if (res.status === 'success') { toast(res.message || 'Status dibatalkan'); applyServerData(res.data); }
                        else { toast(res.message || 'Gagal', 'error'); }
                    },
                    error: function () { restore(); toast('Terjadi kesalahan server', 'error'); }
                });
            });

            function schedulePoll(delay) { clearTimeout(_pollTimer); _pollTimer = setTimeout(doPoll, delay); }

            function doPoll() {
                if (_pollAbort) { try { _pollAbort.abort(); } catch (e) { } }
                var aborter = { aborted: false, abort: function () { this.aborted = true; } };
                _pollAbort = aborter;
                $.ajax({
                    url: CFG.base + '/Strukturkomite/getUnreadCommentCountForFactor',
                    method: 'GET', dataType: 'json',
                    data: { id: 'Strukturkomite', kodebpr: CFG.kbpr, user_id: CFG.uid, periode_id: CFG.pid },
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function (res) {
                        if (aborter.aborted) return;
                        var n = parseInt(res.unread_count) || 0;
                        updateNotifBadge(n);
                        _pollDelay = n > _lastUnread ? Math.max(5000, CFG.pollBase / 2) : Math.min(60000, CFG.pollBase);
                        _lastUnread = n;
                        if (res.next_poll_ms) _pollDelay = Math.max(5000, parseInt(res.next_poll_ms));
                        schedulePoll(_pollDelay);
                    },
                    error: function () {
                        if (aborter.aborted) return;
                        _pollDelay = Math.min(120000, _pollDelay * 2);
                        schedulePoll(_pollDelay);
                    }
                });
            }

            function loadKomentarList(scroll) {
                var list = $('#komentar-list');
                list.html('<div class="komentar-loading"><i class="fas fa-spinner fa-spin"></i> Memuat...</div>');
                request('getKomentar', {
                    url: CFG.base + '/Strukturkomite/getKomentarByFaktorId',
                    method: 'GET', dataType: 'json',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function (res) {
                        var items = Array.isArray(res) ? res : (res.comments || []);
                        if (res.unread_count !== undefined) updateNotifBadge(res.unread_count);
                        if (!items.length) { list.html('<div class="komentar-empty"><i class="fas fa-comment-slash fa-2x mb-2 d-block"></i>Belum ada komentar.</div>'); return; }
                        var frag = document.createDocumentFragment(), tmp2 = document.createElement('div');
                        items.forEach(function (k) {
                            var own = parseInt(k.user_id) === CFG.uid;
                            tmp2.innerHTML = '<div class="km-item' + (own ? ' km-own' : '') + '"><div class="km-meta">'
                                + '<span class="km-author"><i class="fas fa-user-circle"></i> ' + esc(k.fullname || '?')
                                + (own ? ' <span class="km-you">Anda</span>' : '') + '</span>'
                                + '<span class="km-time"><i class="fas fa-clock"></i> ' + timeAgo(k.created_at) + '</span>'
                                + '</div><p class="km-text">' + esc(k.komentar) + '</p></div>';
                            frag.appendChild(tmp2.firstElementChild);
                        });
                        list.empty()[0].appendChild(frag);
                        if (scroll) { var w = document.getElementById('komentar-list-wrapper'); if (w) requestAnimationFrame(function () { w.scrollTop = w.scrollHeight; }); }
                    },
                    error: function () { list.html('<div class="komentar-empty text-danger"><i class="fas fa-exclamation-circle"></i> Gagal memuat komentar.</div>'); }
                });
            }

            function markAllRead() {
                $.ajax({
                    url: CFG.base + '/Strukturkomite/markUserCommentsAsRead', method: 'POST',
                    data: $.extend({}, csrf(), { id: 'Strukturkomite', kodebpr: CFG.kbpr, user_id: CFG.uid, periode_id: CFG.pid }),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function (res) { updateCsrf(res); updateNotifBadge(0); _lastUnread = 0; }
                });
            }

            function renderTindakLanjut(rows) {
                var $bw = $('#tindak-btn-wrap'), $cw = $('#tindak-content-wrap');
                if (CFG.canEdit) {
                    if (!rows || !rows.length) {
                        $bw.html('<button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#modalTambahTindak"><i class="fas fa-plus"></i> Tambah Tindak Lanjut</button>');
                    } else {
                        $bw.html('<button type="button" class="btn btn-sm btn-outline-warning" data-toggle="modal" data-target="#modalEditTindak"'
                            + ' data-id="' + esc(rows[0].id) + '" data-tindaklanjut="' + esc(rows[0].tindaklanjut || '') + '">'
                            + '<i class="fas fa-edit"></i> Ubah Tindak Lanjut</button>');
                    }
                }
                var html = '';
                if (!rows || !rows.length) {
                    html = '<div class="tindak-empty"><i class="fas fa-inbox fa-2x text-muted mb-2 d-block"></i><span class="text-muted">Belum ada data tindak lanjut.</span></div>';
                } else {
                    rows.forEach(function (row) {
                        html += '<div class="tindak-card"><div class="tindak-section">'
                            + '<div class="tindak-label"><i class="fas fa-check-circle text-success"></i> Tindak Lanjut Rekomendasi Struktur, Keanggotaan, Keahlian, dan Independensi Anggota Komite</div>'
                            + '<div class="tindak-content">';
                        if (row.tindaklanjut) {
                            html += esc(row.tindaklanjut).replace(/\n/g, '<br>');
                            if (CFG.canEdit) html += ' <a href="#" class="btn-icon-del btn-del-tindak" data-id="' + esc(row.id) + '" title="Hapus"><i class="fas fa-trash-alt"></i></a>';
                        } else { html += '<span class="text-muted fst-italic">Belum diisi</span>'; }
                        html += '</div></div></div>';
                    });
                }
                $cw.html(html);
                $('#tt-tindaklanjut').val('');
                $('#tindak-tambah-err').hide();
            }

            // ── TAMBAH / UBAH ──────────────────────────────────────────────────────
            $('#btn-show-tambah').on('click', function () {
                $('#t-id').val('');
                $('#t-anggota,#t-nik,#t-keahlian').val('');
                $('#t-jbtaudit,#t-jbtpantauresiko,#t-jbtremunerasi,#t-jbtmanrisk,#t-jbtlain,#t-independen').val('');
                $('#nik-counter').text('0 / 16 digit');
                $('#tambah-err').hide();
                $('#modalTambahTitle').html('<i class="fas fa-plus-circle"></i> Tambah Data Anggota Komite');
                $('#btn-simpan-tambah').html('<i class="fas fa-save"></i> Simpan Data');
                $('#modalTambah').modal('show');
                setTimeout(function () { $('#t-anggota').focus(); }, 450);
            });

            $(document).on('click', '.btn-ubah', function () {
                var d = $(this).data();
                $('#t-id').val(d.id);
                $('#t-anggota').val(d.anggota);
                $('#t-nik').val(d.nik);
                $('#t-keahlian').val(d.keahlian);
                $('#t-jbtaudit').val(d.jbtaudit);
                $('#t-jbtpantauresiko').val(d.jbtpantauresiko);
                $('#t-jbtremunerasi').val(d.jbtremunerasi);
                $('#t-jbtmanrisk').val(d.jbtmanrisk);
                $('#t-jbtlain').val(d.jbtlain);
                $('#t-independen').val(d.independen);
                $('#nik-counter').text((d.nik || '').length + ' / 16 digit');
                $('#tambah-err').hide();
                $('#modalTambahTitle').html('<i class="fas fa-edit"></i> Ubah Data Anggota Komite');
                $('#btn-simpan-tambah').html('<i class="fas fa-save"></i> Simpan Perubahan');
                $('#modalTambah').modal('show');
                setTimeout(function () { $('#t-anggota').focus(); }, 450);
            });

            var execSimpan = debounce(function () {
                var isEdit = !!$('#t-id').val();
                var anggota = $.trim($('#t-anggota').val()), nik = $.trim($('#t-nik').val());
                var keahlian = $.trim($('#t-keahlian').val());
                var jbtaudit = $('#t-jbtaudit').val(), jbtpantauresiko = $('#t-jbtpantauresiko').val();
                var jbtremunerasi = $('#t-jbtremunerasi').val(), jbtmanrisk = $('#t-jbtmanrisk').val();
                var jbtlain = $('#t-jbtlain').val(), independen = $('#t-independen').val();

                if (!anggota || !nik || !keahlian) { $('#tambah-err').text('Nama, NIK, dan Keahlian wajib diisi.').show(); return; }
                if (!/^\d{16}$/.test(nik)) { $('#tambah-err').text('NIK harus tepat 16 digit angka.').show(); return; }
                if (!jbtaudit || !jbtpantauresiko || !jbtremunerasi || !jbtmanrisk || !jbtlain || !independen) {
                    $('#tambah-err').text('Semua jabatan dan status independen wajib dipilih.').show(); return;
                }

                var pd = {
                    anggotakomite: anggota, nikkomite: nik, keahlian: keahlian,
                    jbtaudit: jbtaudit, jbtpantauresiko: jbtpantauresiko,
                    jbtremunerasi: jbtremunerasi, jbtmanrisk: jbtmanrisk,
                    jbtlain: jbtlain, independen: independen
                };
                var ep = 'Strukturkomite/tambahAjax';
                if (isEdit) { pd.id = $('#t-id').val(); ep = 'Strukturkomite/ubahAjax'; }

                ajaxCrud(ep, pd, '#tambah-err', $('#btn-simpan-tambah'), function (res) {
                    $('#modalTambah').modal('hide');
                    toast(res.message || (isEdit ? 'Data berhasil diubah' : 'Data berhasil ditambahkan'));
                    applyServerData(res.data);
                });
            }, 400);

            $('#btn-simpan-tambah').on('click', execSimpan);
            $('#modalTambah').on('keydown', function (e) { if (e.key === 'Enter' && !$(e.target).is('textarea,select')) execSimpan(); });

            // ── HAPUS ──────────────────────────────────────────────────────────────
            $(document).on('click', '.btn-hapus', function () {
                var d = $(this).data();
                $('#h-id').val(d.id);
                $('#h-nama').text(d.nama || '-');
                $('#h-nik').text(d.nik || '-');
                $('#modalHapus').modal('show');
            });

            var execHapus = debounce(function () {
                var id = $('#h-id').val();
                if (!id) { toast('ID tidak valid', 'error'); return; }
                var restore = btnLoading($('#btn-konfirmasi-hapus'));
                request('hapus', {
                    url: CFG.base + '/Strukturkomite/hapusAjax', method: 'POST', dataType: 'json',
                    data: $.extend({}, csrf(), { id: id }),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function (res) {
                        restore(); updateCsrf(res); $('#modalHapus').modal('hide');
                        if (res.status === 'success') { toast(res.message || 'Data berhasil dihapus'); applyServerData(res.data); }
                        else { toast(res.message || 'Gagal menghapus', 'error'); }
                    },
                    error: function (xhr) {
                        restore();
                        var m = 'Terjadi kesalahan server.';
                        try { var r = JSON.parse(xhr.responseText); if (r && r.message) m = r.message; } catch (e) { }
                        if (xhr.status === 403) { m = 'Sesi kedaluwarsa — halaman akan di-refresh…'; setTimeout(function () { location.reload(); }, 2000); }
                        toast(m, 'error');
                    }
                });
            }, 400);
            $('#btn-konfirmasi-hapus').on('click', execHapus);

            // ── TINDAK: tambah ─────────────────────────────────────────────────────
            var execTambahTindak = debounce(function () {
                var tindak = $.trim($('#tt-tindaklanjut').val());
                if (!tindak) { $('#tindak-tambah-err').text('Tindak Lanjut wajib diisi.').show(); return; }
                var restore = btnLoading($('#btn-simpan-tindak'));
                $('#tindak-tambah-err').hide();
                $.ajax({
                    url: CFG.base + '/Strukturkomite/tambahKeteranganAjax', method: 'POST', dataType: 'json',
                    data: $.extend({}, csrf(), { tindaklanjut: tindak }),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function (res) {
                        restore(); updateCsrf(res);
                        if (res.status === 'success') { $('#modalTambahTindak').modal('hide'); toast(res.message || 'Tindak lanjut berhasil disimpan'); renderTindakLanjut(res.penjelastindak || []); }
                        else { $('#tindak-tambah-err').text(res.message || 'Terjadi kesalahan').show(); }
                    },
                    error: function (xhr) {
                        restore();
                        var m = 'Terjadi kesalahan server.';
                        try { var r = JSON.parse(xhr.responseText); if (r && r.message) m = r.message; } catch (e) { }
                        if (xhr.status === 403) { m = 'Sesi kedaluwarsa — halaman akan di-refresh…'; setTimeout(function () { location.reload(); }, 2000); }
                        $('#tindak-tambah-err').text(m).show();
                    }
                });
            }, 400);
            $('#btn-simpan-tindak').on('click', execTambahTindak);

            // ── TINDAK: edit ───────────────────────────────────────────────────────
            $('#modalEditTindak').on('show.bs.modal', function (e) {
                var btn = $(e.relatedTarget);
                $('#et-id').val(btn.data('id'));
                $('#et-tindaklanjut').val(btn.data('tindaklanjut'));
                $('#tindak-edit-err').hide();
            });

            var execEditTindak = debounce(function () {
                var id = $('#et-id').val(), tindak = $.trim($('#et-tindaklanjut').val());
                if (!tindak) { $('#tindak-edit-err').text('Tindak Lanjut tidak boleh kosong.').show(); return; }
                var restore = btnLoading($('#btn-simpan-edit-tindak'));
                $('#tindak-edit-err').hide();
                $.ajax({
                    url: CFG.base + '/Strukturkomite/editKeteranganAjax', method: 'POST', dataType: 'json',
                    data: $.extend({}, csrf(), { id: id, tindaklanjut: tindak }),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function (res) {
                        restore(); updateCsrf(res);
                        if (res.status === 'success') { $('#modalEditTindak').modal('hide'); toast(res.message || 'Tindak lanjut berhasil diubah'); renderTindakLanjut(res.penjelastindak || []); }
                        else { $('#tindak-edit-err').text(res.message || 'Terjadi kesalahan').show(); }
                    },
                    error: function (xhr) {
                        restore();
                        var m = 'Terjadi kesalahan server.';
                        try { var r = JSON.parse(xhr.responseText); if (r && r.message) m = r.message; } catch (e) { }
                        if (xhr.status === 403) { m = 'Sesi kedaluwarsa — halaman akan di-refresh…'; setTimeout(function () { location.reload(); }, 2000); }
                        $('#tindak-edit-err').text(m).show();
                    }
                });
            }, 400);
            $('#btn-simpan-edit-tindak').on('click', execEditTindak);

            // ── TINDAK: hapus ──────────────────────────────────────────────────────
            $(document).on('click', '.btn-del-tindak', function (e) {
                e.preventDefault();
                var id = $(this).data('id'), $btn = $(this);
                if (!confirm('Hapus data Tindak Lanjut ini?')) return;
                $btn.html('<i class="fas fa-spinner fa-spin"></i>').css('pointer-events', 'none');
                $.ajax({
                    url: CFG.base + '/Strukturkomite/setNullKolomTindakAjax', method: 'POST', dataType: 'json',
                    data: $.extend({}, csrf(), { id: id }),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function (res) {
                        updateCsrf(res);
                        if (res.status === 'success') { toast(res.message || 'Data dihapus'); renderTindakLanjut([]); }
                        else { toast(res.message || 'Gagal menghapus', 'error'); $btn.html('<i class="fas fa-trash-alt"></i>').css('pointer-events', ''); }
                    },
                    error: function () { toast('Terjadi kesalahan server', 'error'); $btn.html('<i class="fas fa-trash-alt"></i>').css('pointer-events', ''); }
                });
            });

            // ── KOMENTAR ───────────────────────────────────────────────────────────
            var _sendingKomentar = false;
            var execKomentar = debounce(function () {
                if (_sendingKomentar) return;
                var teks = $.trim($('#k-teks').val());
                if (!teks) { toast('Komentar tidak boleh kosong.', 'error'); return; }
                if (teks.length < 3) { toast('Komentar minimal 3 karakter.', 'error'); return; }
                _sendingKomentar = true;
                var restore = btnLoading($('#btn-kirim-komentar'));
                $.ajax({
                    url: CFG.base + '/Strukturkomite/Tambahkomentar', method: 'POST', dataType: 'json',
                    data: $.extend({}, csrf(), {
                        TambahKomentar: 1, id: 'Strukturkomite', komentar: teks,
                        fullname: CFG.fname, kodebpr: CFG.kbpr,
                        date: new Date().toISOString().slice(0, 19).replace('T', ' ')
                    }),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function (res) {
                        _sendingKomentar = false; restore(); updateCsrf(res);
                        if (res && res.status === 'success') {
                            $('#k-teks').val(''); $('#k-char-count').text('0 / 1000').removeClass('danger');
                            toast('Komentar berhasil dikirim'); loadKomentarList(true);
                            _lastUnread = 0; _pollDelay = 5000;
                        } else { toast((res && res.message) || 'Gagal mengirim', 'error'); }
                    },
                    error: function () { _sendingKomentar = false; restore(); toast('Gagal mengirim komentar', 'error'); }
                });
            }, 400);

            $('#btn-komentar-global').on('click', function () { loadKomentarList(true); markAllRead(); $('#modalKomentar').modal('show'); });
            $('#btn-kirim-komentar').on('click', execKomentar);
            $('#k-teks')
                .on('keydown', function (e) { if (e.ctrlKey && e.key === 'Enter') { e.preventDefault(); execKomentar(); } })
                .on('input', function () { var l = this.value.length, $el = $('#k-char-count'); $el.text(l + ' / 1000'); l > 950 ? $el.addClass('danger') : $el.removeClass('danger'); });

            document.getElementById('t-nik').addEventListener('input', function () {
                this.value = this.value.replace(/\D/g, '');
                document.getElementById('nik-counter').textContent = this.value.length + ' / 16 digit';
            }, { passive: true });

            $('#btn-retry').on('click', function () {
                $('#error-state').hide(); $('#skeleton-container').show();
                setTimeout(function () { $('#skeleton-container').hide(); $('#table-wrapper').show(); renderCards(_rows); }, 300);
            });

            $(document).on('hidden.bs.modal', '.modal', function () { $(this).find('.alert-danger').hide().text(''); });

            document.addEventListener('visibilitychange', function () {
                if (document.hidden) { clearTimeout(_pollTimer); }
                else { _pollDelay = CFG.pollBase; schedulePoll(1000); }
            }, { passive: true });

            (function boot() {
                $('#skeleton-container').show();
                $('#table-wrapper,#empty-state,#error-state').hide();
                requestAnimationFrame(function () {
                    setTimeout(function () {
                        $('#skeleton-container').hide();

                        var nullRecords = _rows.filter(function (r) { return parseInt(r.is_null_record) === 1; });
                        var realRecords = _rows.filter(function (r) { return !parseInt(r.is_null_record); });

                        if (nullRecords.length > 0 && realRecords.length === 0) {
                            $('#empty-no-action').hide();
                            $('#empty-null-record').show();
                            $('#empty-state').show();
                            $('#table-wrapper').hide();
                            if (CFG.canEdit) $('#btn-show-tambah').hide();
                        } else if (!_rows.length) {
                            $('#empty-no-action').show();
                            $('#empty-null-record').hide();
                            $('#empty-state').show();
                            $('#table-wrapper').hide();
                        } else {
                            $('#empty-state').hide();
                            $('#table-wrapper').show();
                            renderCards(_rows);
                        }

                        syncApprovalUI(_rows);
                    }, 80);
                });
            }());

            schedulePoll(_pollDelay);
        });
    }());
</script>

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
        --gray-50: #f8fafc;
        --gray-100: #f1f5f9;
        --gray-200: #e2e8f0;
        --gray-400: #94a3b8;
        --gray-500: #64748b;
        --gray-700: #334155;
        --gray-800: #1e293b;
        --gray-900: #0f172a;
        --blue: #1f2aa8;
        --sh-sm: 0 1px 3px rgba(0, 0, 0, .08);
        --sh: 0 4px 6px -1px rgba(0, 0, 0, .1);
        --sh-lg: 0 10px 25px -3px rgba(0, 0, 0, .1);
        --tr: all .2s cubic-bezier(.4, 0, .2, 1);
        --grad: linear-gradient(135deg, #141863 0%, #1f2aa8 100%);
        --r-sm: 8px;
        --r: 12px;
        --r-lg: 16px
    }

    * {
        font-family: 'Plus Jakarta Sans', sans-serif
    }

    .page-header {
        text-align: center;
        padding: 28px 16px 20px;
        background: white;
        border-bottom: 1px solid var(--gray-100);
        position: relative;
        overflow: hidden
    }

    .page-header::before {
        content: '';
        position: absolute;
        top: -60px;
        right: -60px;
        width: 180px;
        height: 180px;
        border-radius: 50%;
        background: radial-gradient(circle, #eef2ff, transparent 70%)
    }

    .page-title {
        font-size: clamp(1.05rem, 3.5vw, 1.5rem);
        font-weight: 800;
        color: var(--gray-900);
        margin-bottom: 6px;
        letter-spacing: -.5px;
        position: relative;
        z-index: 1
    }

    .page-subtitle {
        font-size: .95rem;
        font-weight: 600;
        color: var(--gray-700);
        margin-bottom: 10px;
        position: relative;
        z-index: 1
    }

    .badge-namabpr {
        display: inline-block;
        background: var(--grad);
        color: white;
        font-size: .9rem;
        font-weight: 700;
        padding: 5px 18px;
        border-radius: 100px;
        position: relative;
        z-index: 1;
        box-shadow: 0 4px 12px rgba(79, 70, 229, .3)
    }

    .approval-badges-row {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: center
    }

    .approval-status-badge {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 14px;
        border-radius: var(--r);
        font-size: 12.5px;
        font-weight: 600;
        border: 1.5px solid transparent;
        transition: var(--tr)
    }

    .approval-status-badge i {
        font-size: 18px;
        flex-shrink: 0
    }

    .approval-status-label {
        font-weight: 700;
        font-size: 12px
    }

    .approval-status-sub {
        font-size: 11px;
        opacity: .8
    }

    .badge-status-secondary {
        background: var(--gray-100);
        color: var(--gray-500);
        border-color: var(--gray-200)
    }

    .badge-status-success {
        background: var(--success-bg);
        color: #065f46;
        border-color: #6ee7b7
    }

    .approval-actions-row {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        justify-content: center
    }

    .action-card {
        border-radius: var(--r) !important;
        border: 1.5px solid var(--gray-200) !important;
        transition: var(--tr);
        overflow: hidden;
        min-width: 200px
    }

    .action-card:hover {
        border-color: var(--brand-light) !important;
        box-shadow: 0 8px 24px rgba(79, 70, 229, .12) !important;
        transform: translateY(-2px)
    }

    .btn {
        transition: var(--tr);
        font-weight: 600
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: var(--sh)
    }

    .btn:active {
        transform: scale(.96) !important
    }

    .btn-sm {
        font-size: 12px
    }

    .btn-kembali {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: white;
        border: 1.5px solid var(--gray-200);
        border-radius: var(--r-sm);
        padding: 7px 14px;
        font-size: .875rem;
        font-weight: 600;
        color: var(--gray-500);
        text-decoration: none;
        transition: var(--tr);
        margin: 12px 0 0 8px
    }

    .btn-kembali:hover {
        background: var(--brand-bg);
        border-color: var(--brand-light);
        color: var(--brand);
        text-decoration: none
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
        cursor: pointer
    }

    .btn-primaryy:hover {
        background: var(--brand-bg);
        transform: rotate(90deg) scale(1.1)
    }

    .btn-icon-del {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        border-radius: 6px;
        background: var(--danger-bg);
        color: var(--danger);
        font-size: 11px;
        margin-left: 6px;
        vertical-align: middle;
        text-decoration: none;
        transition: var(--tr)
    }

    .btn-icon-del:hover {
        background: var(--danger);
        color: white
    }

    .dir-card-skel {
        background: white;
        border-radius: var(--r-lg);
        overflow: hidden;
        margin-bottom: 14px;
        box-shadow: var(--sh-sm)
    }

    .skel-card-header {
        height: 50px;
        background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
        background-size: 200% 100%;
        animation: skAnim 1.4s infinite
    }

    .skel-card-body {
        padding: 14px
    }

    .skel {
        border-radius: var(--r);
        background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
        background-size: 200% 100%;
        animation: skAnim 1.4s infinite
    }

    @keyframes skAnim {
        0% {
            background-position: 200% 0
        }

        100% {
            background-position: -200% 0
        }
    }

    .dir-cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 16px;
        padding: 4px 2px
    }

    .dir-card {
        background: white;
        border-radius: var(--r-lg);
        box-shadow: var(--sh-sm);
        border: 1.5px solid var(--gray-100);
        overflow: hidden;
        transition: var(--tr);
        animation: rowIn .3s ease both;
        display: flex;
        flex-direction: column
    }

    .dir-card:hover {
        box-shadow: var(--sh-lg);
        border-color: var(--brand-light);
        transform: translateY(-3px)
    }

    @keyframes rowIn {
        from {
            opacity: 0;
            transform: translateY(8px)
        }

        to {
            opacity: 1;
            transform: translateY(0)
        }
    }

    .dcard-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        background: var(--grad);
        gap: 8px
    }

    .dcard-index-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
        flex: 1
    }

    .dcard-index {
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
        flex-shrink: 0
    }

    .dcard-nama {
        font-size: 14px;
        font-weight: 800;
        color: rgba(255, 255, 255, .9)
    }

    .dcard-actions {
        display: flex;
        align-items: center;
        gap: 5px;
        flex-shrink: 0
    }

    .dcard-btn {
        width: 28px;
        height: 28px;
        padding: 0 !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--r-sm) !important;
        font-size: 12px !important
    }

    .dcard-body {
        padding: 14px 16px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        flex: 1
    }

    .dcard-nik {
        color: var(--gray-700);
        font-size: 13px;
        font-weight: 700;
        letter-spacing: .3px;
        background: var(--gray-100);
        padding: 3px 10px;
        border-radius: 6px;
        display: inline-block;
        margin-bottom: 2px
    }

    .dcard-section-label {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .7px;
        color: var(--gray-400);
        margin-top: 4px
    }

    .dcard-keahlian-list {
        margin: 0;
        padding-left: 1.2em;
        font-size: 12.5px;
        color: var(--gray-700);
        line-height: 1.7
    }

    .dcard-keahlian-list li {
        margin-bottom: 2px
    }

    .dcard-jabatan-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 4px;
        margin-top: 4px
    }

    .dcard-jabatan-item {
        background: var(--gray-50);
        border: 1px solid var(--gray-200);
        border-radius: 6px;
        padding: 5px 8px
    }

    .dcard-jabatan-item-label {
        font-size: 10px;
        font-weight: 700;
        color: var(--gray-400);
        text-transform: uppercase;
        letter-spacing: .4px
    }

    .dcard-jabatan-item-val {
        font-size: 11.5px;
        font-weight: 600;
        color: var(--gray-700);
        margin-top: 1px
    }

    .dcard-independen-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 100px;
        margin-top: 4px
    }

    .dcard-independen-yes {
        background: var(--success-bg);
        color: #065f46;
        border: 1.5px solid #a7f3d0
    }

    .dcard-independen-no {
        background: var(--danger-bg);
        color: #991b1b;
        border: 1.5px solid #fca5a5
    }

    .dcard-footer {
        display: flex;
        align-items: center;
        padding-top: 8px;
        border-top: 1.5px solid var(--gray-100)
    }

    .acc-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 100px;
        border: 1.5px solid transparent
    }

    .acc-badge-ok {
        background: var(--success-bg);
        color: #065f46;
        border-color: #a7f3d0
    }

    .acc-badge-no {
        background: var(--warning-bg);
        color: #92400e;
        border-color: #fcd34d
    }

    .tindak-card {
        background: var(--gray-50);
        border-radius: var(--r-lg);
        border: 1.5px solid var(--gray-200);
        padding: 16px;
        margin-bottom: 12px
    }

    .tindak-label {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .6px;
        color: var(--gray-500);
        margin-bottom: 8px
    }

    .tindak-content {
        font-size: 13.5px;
        color: var(--gray-700);
        line-height: 1.7;
        background: white;
        border: 1.5px solid var(--gray-200);
        border-radius: var(--r-sm);
        padding: 10px 14px
    }

    .tindak-empty {
        text-align: center;
        padding: 30px 16px;
        color: var(--gray-400)
    }

    .cardpilihfaktor {
        border: 1px solid var(--gray-200);
        border-radius: var(--r);
        max-width: 720px;
        margin: 0 auto
    }

    .cardpilihfaktor-header {
        text-align: center;
        background: var(--gray-50);
        padding: 6px;
        border-bottom: 1px solid var(--gray-200)
    }

    .cardpilihfaktor-header h6 {
        margin: 0;
        font-size: .9rem;
        font-weight: 700
    }

    .cardpilihfaktor-body {
        padding: 8px 10px
    }

    .nav-pages-wrap {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        justify-content: center
    }

    .nav-page-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 32px;
        height: 32px;
        padding: 0 6px;
        border-radius: 6px;
        border: 1.5px solid var(--gray-200);
        background: white;
        color: var(--gray-700);
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        transition: var(--tr)
    }

    .nav-page-btn:hover {
        background: var(--brand-bg);
        border-color: var(--brand-light);
        color: var(--brand);
        text-decoration: none
    }

    .nav-page-btn.active {
        background: var(--grad);
        color: white;
        border-color: var(--blue)
    }

    .notif-badge {
        position: absolute;
        top: -7px;
        right: -7px;
        background: var(--danger);
        color: #fff;
        border-radius: 50%;
        padding: 2px 5px;
        font-size: 10px;
        font-weight: 700;
        min-width: 17px;
        text-align: center;
        line-height: 1.3;
        box-shadow: 0 2px 6px rgba(239, 68, 68, .5);
        animation: pulse 2s infinite
    }

    @keyframes pulse {

        0%,
        100% {
            box-shadow: 0 0 0 0 rgba(239, 68, 68, .4)
        }

        50% {
            box-shadow: 0 0 0 5px rgba(239, 68, 68, 0)
        }
    }

    .empty-state-box {
        text-align: center;
        padding: 50px 20px;
        background: var(--gray-50);
        border-radius: var(--r-lg);
        border: 2px dashed var(--gray-200);
        margin: 10px 0
    }

    .btn-komentar-global-btn {
        position: relative;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: white;
        border: none;
        color: var(--blue);
        font-size: 17px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 10px rgba(0, 0, 0, .18);
        cursor: pointer;
        transition: all .25s cubic-bezier(.4, 0, .2, 1);
        outline: none
    }

    .btn-komentar-global-btn:hover {
        background: var(--brand-bg);
        color: var(--brand);
        transform: scale(1.13)
    }

    .btn-komentar-global-btn.has-unread {
        animation: btn-shake 1.8s ease infinite;
        background: var(--brand-bg);
        color: var(--brand)
    }

    @keyframes btn-shake {

        0%,
        100% {
            transform: rotate(0)scale(1)
        }

        10% {
            transform: rotate(-14deg)scale(1.08)
        }

        20% {
            transform: rotate(12deg)scale(1.08)
        }

        30% {
            transform: rotate(-10deg)scale(1.05)
        }

        40% {
            transform: rotate(8deg)scale(1.05)
        }

        50% {
            transform: rotate(0)scale(1)
        }
    }

    .btn-komentar-ripple {
        position: absolute;
        inset: -4px;
        border-radius: 50%;
        border: 2px solid var(--brand-light);
        opacity: 0;
        pointer-events: none
    }

    .btn-komentar-global-btn.has-unread .btn-komentar-ripple {
        animation: ripple-out 1.8s ease infinite
    }

    @keyframes ripple-out {
        0% {
            transform: scale(.9);
            opacity: .7
        }

        100% {
            transform: scale(1.6);
            opacity: 0
        }
    }

    .komentar-modal-content {
        border-radius: 18px !important;
        overflow: hidden;
        border: none;
        box-shadow: 0 20px 60px rgba(0, 0, 0, .18) !important
    }

    .komentar-modal-header {
        background: var(--grad);
        padding: 18px 22px !important;
        border: none !important;
        display: flex;
        align-items: center;
        justify-content: space-between
    }

    .komentar-modal-title-wrap {
        display: flex;
        align-items: center;
        gap: 12px
    }

    .komentar-modal-icon-wrap {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        background: rgba(255, 255, 255, .18);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        color: white;
        flex-shrink: 0
    }

    .komentar-modal-title {
        font-size: 14px;
        font-weight: 800;
        color: white
    }

    .komentar-close-btn {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .15);
        border: none;
        color: white;
        font-size: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all .2s;
        flex-shrink: 0
    }

    .komentar-close-btn:hover {
        background: rgba(255, 255, 255, .3);
        transform: rotate(90deg)
    }

    .komentar-modal-body {
        padding: 18px 20px !important;
        background: var(--gray-50)
    }

    .komentar-modal-footer {
        background: white;
        border-top: 1px solid var(--gray-100) !important;
        padding: 10px 20px !important
    }

    .komentar-list-label {
        font-size: 11px;
        font-weight: 700;
        color: var(--gray-500);
        text-transform: uppercase;
        letter-spacing: .8px;
        margin-bottom: 8px
    }

    .komentar-list-wrapper {
        background: white;
        border: 1.5px solid var(--gray-200);
        border-radius: 12px;
        max-height: 300px;
        overflow-y: auto;
        padding: 10px;
        margin-bottom: 14px;
        scroll-behavior: smooth
    }

    .komentar-list-wrapper::-webkit-scrollbar {
        width: 5px
    }

    .komentar-list-wrapper::-webkit-scrollbar-thumb {
        background: var(--gray-200);
        border-radius: 10px
    }

    .komentar-loading,
    .komentar-empty {
        text-align: center;
        color: var(--gray-400);
        padding: 24px 0;
        font-size: 13px
    }

    .km-item {
        background: var(--gray-50);
        border-left: 3px solid var(--brand-light);
        border-radius: 10px;
        padding: 9px 12px;
        margin-bottom: 7px;
        box-shadow: var(--sh-sm);
        animation: kmIn .25s ease both
    }

    .km-item.km-own {
        border-left-color: var(--success);
        background: #f0fdf4
    }

    @keyframes kmIn {
        from {
            opacity: 0;
            transform: translateY(6px)
        }

        to {
            opacity: 1;
            transform: translateY(0)
        }
    }

    .km-meta {
        display: flex;
        justify-content: space-between;
        margin-bottom: 4px;
        flex-wrap: wrap;
        gap: 3px
    }

    .km-author {
        font-weight: 700;
        font-size: 12px;
        color: var(--gray-800);
        display: flex;
        align-items: center;
        gap: 5px
    }

    .km-you {
        font-size: 9px;
        background: var(--success);
        color: white;
        padding: 1px 5px;
        border-radius: 100px;
        font-weight: 700
    }

    .km-time {
        font-size: 11px;
        color: var(--gray-400)
    }

    .km-text {
        font-size: 13px;
        white-space: pre-wrap;
        margin: 0;
        color: var(--gray-700);
        line-height: 1.5
    }

    .komentar-input-wrap {
        background: white;
        border: 1.5px solid var(--gray-200);
        border-radius: 12px;
        padding: 12px;
        transition: border-color .2s
    }

    .komentar-input-wrap:focus-within {
        border-color: var(--brand);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, .1)
    }

    .komentar-input-label {
        font-size: 11px;
        font-weight: 700;
        color: var(--gray-500);
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: .6px
    }

    .komentar-textarea {
        width: 100%;
        border: none;
        outline: none;
        resize: none;
        font-size: 13px;
        color: var(--gray-700);
        line-height: 1.6;
        background: transparent
    }

    .komentar-input-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 6px;
        padding-top: 6px;
        border-top: 1px solid var(--gray-100)
    }

    .komentar-hint {
        font-size: 11px;
        color: var(--gray-400)
    }

    .komentar-char {
        font-size: 11px;
        color: var(--gray-400);
        font-weight: 600;
        transition: color .2s
    }

    .komentar-char.danger {
        color: var(--danger)
    }

    .modal-header-dir {
        background: var(--grad);
        color: white
    }

    .modal-content {
        border-radius: var(--r-lg);
        overflow: hidden;
        border: none;
        box-shadow: var(--sh-lg)
    }

    .modal-body {
        padding: 18px 22px !important
    }

    .modal-footer {
        padding: 12px 22px !important
    }

    .modal-body .form-group {
        margin-bottom: 14px !important
    }

    .modal-body .form-control {
        width: 100% !important;
        box-sizing: border-box !important;
        border-radius: 8px !important;
        border: 1.5px solid var(--gray-200) !important;
        padding: 8px 12px !important;
        font-size: 13px !important;
        transition: var(--tr) !important
    }

    .modal-body .form-control:focus {
        border-color: var(--brand) !important;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, .15) !important;
        outline: none !important
    }

    .modal-body .row {
        margin-left: -6px !important;
        margin-right: -6px !important
    }

    .modal-body .row>[class*="col-"] {
        padding-left: 6px !important;
        padding-right: 6px !important
    }

    .modal-dialog {
        margin: 16px auto !important;
        max-width: 700px !important
    }

    .t-item {
        background: white;
        border-radius: var(--r);
        padding: 12px 16px;
        margin-bottom: 8px;
        box-shadow: var(--sh-lg);
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 13px;
        animation: tIn .3s ease;
        pointer-events: all;
        border-left: 4px solid
    }

    .t-item.t-success {
        border-color: var(--success);
        color: #065f46
    }

    .t-item.t-error {
        border-color: var(--danger);
        color: #991b1b
    }

    .t-item.t-info {
        border-color: #06b6d4;
        color: #164e63
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

    .blk-disabled {
        opacity: .4;
        pointer-events: none
    }

    .card-header {
        border-radius: var(--r-lg) var(--r-lg) 0 0 !important
    }

    @media(max-width:576px) {
        .dir-cards-grid {
            grid-template-columns: 1fr
        }

        .dcard-jabatan-grid {
            grid-template-columns: 1fr
        }

        .modal-dialog {
            margin: 8px !important;
            max-width: calc(100% - 16px) !important
        }

        .modal-body {
            padding: 14px !important
        }

        .approval-actions-row,
        .approval-badges-row {
            flex-direction: column;
            align-items: center
        }

        .action-card {
            width: 100%;
            max-width: 340px
        }

        .nav-pages-wrap {
            gap: 3px
        }

        .nav-page-btn {
            min-width: 28px;
            height: 28px;
            font-size: 11px
        }
    }
</style>