<div class="container-fluid px-3 px-md-4 py-4">
    <div class="mb-4">
        <h1 class="ps-title"><i class="fas fa-shield-alt me-2"></i>&nbsp;Periode Laporan Semesteran</h1>
        <p class="ps-sub">Kelola periode laporan semesteran</p>
    </div>

    <?php if ($msg = session()->getFlashdata('message')): ?>
        <div class="alert alert-success alert-dismissible fade show ps-alert" role="alert">
            <i class="fas fa-check-circle me-2"></i><?= $msg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($err = session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show ps-alert" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?= $err ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($validation) && $validation->getErrors()): ?>
        <div class="alert alert-danger alert-dismissible fade show ps-alert" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <ul class="mb-0"><?php foreach ($validation->getErrors() as $e): ?>
                    <li><?= esc($e) ?></li><?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <?php if ($canManagePeriode): ?>
        <div class="col-12 col-lg-5 col-xl-4 mb-4">
            <div class="ps-card shadow-sm h-100">
                <div class="ps-card-header">
                    <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i><span class="d-none d-sm-inline">&nbsp;Buat Periode Baru</span><span class="d-sm-none">Buat Baru</span></h5>
                </div>
                <div class="ps-card-body">
                    <form action="<?= base_url('periodesemesteran/handlePeriode') ?>" method="post" id="createForm" novalidate>
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="kategori" id="kategori">

                        <div class="ps-group mb-3">
                            <label class="ps-label"><i class="fas fa-list-ol me-1"></i>&nbsp;Semester</label>
                            <select id="semester" name="semester" class="ps-select" required>
                                <option value="">Pilih Semester</option>
                                <option value="1" <?= old('semester') == '1' ? 'selected' : '' ?>>Semester 1</option>
                                <option value="2" <?= old('semester') == '2' ? 'selected' : '' ?>>Semester 2</option>
                            </select>
                            <div class="ps-err">Semester harus dipilih</div>
                        </div>

                        <div class="ps-group mb-3">
                            <label class="ps-label"><i class="fas fa-calendar me-1"></i>&nbsp;Tahun</label>
                            <select id="tahun" name="tahun" class="ps-select" required>
                                <option value="">Pilih Tahun</option>
                                <?php for ($y = 2024; $y <= 2030; $y++): ?>
                                    <option value="<?= $y ?>" <?= (old('tahun', date('Y')) == $y) ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                            <div class="ps-err">Tahun harus dipilih</div>
                        </div>

                        <div class="ps-group mb-3">
                            <label class="ps-label"><i class="fas fa-file-alt me-1"></i>&nbsp;Jenis Pelaporan</label>
                            <div class="ps-jenis-group">
                                <button type="button" class="ps-jenis active-r" id="btnR" data-val="R"><span class="ps-dot filled"></span>Rutin</button>
                                <button type="button" class="ps-jenis" id="btnK" data-val="K"><span class="ps-dot"></span>Koreksi</button>
                            </div>
                            <input type="hidden" id="jenispelaporan" name="jenispelaporan" value="<?= old('jenispelaporan', 'R') ?>">
                        </div>

                        <div class="ps-group mb-3" id="fieldNomorSurat" style="display:none;">
                            <label class="ps-label"><i class="fas fa-envelope me-1"></i>&nbsp;Nomor Surat</label>
                            <input type="text" id="nomorsurat" name="nomorsurat" class="ps-input"
                                placeholder="Contoh: 001/SURAT/VI/2025" value="<?= old('nomorsurat') ?>">
                            <div class="ps-err">Nomor surat harus diisi</div>
                        </div>

                        <div class="ps-group mb-3" id="fieldJenisKoreksi" style="display:none;">
                            <label class="ps-label"><i class="fas fa-edit me-1"></i>&nbsp;Jenis Koreksi</label>
                            <select id="jeniskoreksiSelect" class="ps-select" disabled>
                                <option value="">Pilih Jenis Koreksi</option>
                                <option value="1" <?= old('jeniskoreksi') == '1' ? 'selected' : '' ?>>Koreksi Laporan Semesteran</option>
                                <option value="2" <?= old('jeniskoreksi') == '2' ? 'selected' : '' ?>>Pengkinian Data</option>
                            </select>
                            <input type="hidden" id="jeniskoreksiHidden" name="jeniskoreksi" value="<?= old('jeniskoreksi', '0') ?>">
                            <div class="ps-err">Jenis koreksi harus dipilih</div>
                        </div>

                        <div class="ps-group mb-3">
                            <label class="ps-label"><i class="fas fa-coins me-1"></i>&nbsp;Modal Inti</label>
                            <div class="ps-input-wrap">
                                <span class="ps-prefix">Rp</span>
                                <input type="text" id="modalinti" name="modalinti" class="ps-input" placeholder="0"
                                    inputmode="numeric" autocomplete="off"
                                    value="<?= old('modalinti') ? number_format((int)old('modalinti'), 0, ',', '.') : '' ?>">
                            </div>
                            <div id="warn6B" class="ps-inline ps-inline-danger mt-1" hidden><i class="fas fa-exclamation-triangle me-1"></i>Modal inti di bawah 6 Miliar.</div>
                            <div id="infoKat" class="ps-inline mt-2" hidden><i class="fas fa-info-circle me-1"></i><span id="infoKatTxt"></span></div>
                            <div class="ps-err">Modal inti harus diisi</div>
                        </div>

                        <div class="ps-group mb-3">
                            <label class="ps-label"><i class="fas fa-chart-bar me-1"></i>&nbsp;Total Aset</label>
                            <div class="ps-input-wrap">
                                <span class="ps-prefix">Rp</span>
                                <input type="text" id="totalaset" name="totalaset" class="ps-input" placeholder="0"
                                    inputmode="numeric" autocomplete="off"
                                    value="<?= old('totalaset') ? number_format((int)old('totalaset'), 0, ',', '.') : '' ?>">
                            </div>
                            <div class="ps-err">Total aset harus diisi</div>
                        </div>

                        <div class="ps-group mb-3">
                            <label class="ps-label"><i class="fas fa-building me-1"></i>&nbsp;Jumlah Kantor Cabang</label>
                            <input type="number" id="kantorcabang" name="kantorcabang" class="ps-input"
                                placeholder="0" min="0" value="<?= old('kantorcabang') ?>">
                            <div class="ps-err">Jumlah kantor cabang harus diisi</div>
                        </div>

                        <div class="ps-group mb-4">
                            <label class="ps-label"><i class="fas fa-credit-card me-1"></i>&nbsp;Penerbit Kartu ATM / Debit</label>
                            <div class="ps-jenis-group">
                                <button type="button" class="ps-jenis" id="btnAtmYa" data-val="1"><span class="ps-dot"></span>Ya</button>
                                <button type="button" class="ps-jenis" id="btnAtmTidak" data-val="2"><span class="ps-dot"></span>Tidak</button>
                            </div>
                            <input type="hidden" id="atmdebit" name="atmdebit" value="<?= old('atmdebit', '') ?>">
                            <div class="ps-err" id="atmErr">Pilihan harus dipilih</div>
                        </div>

                        <button type="submit" class="ps-btn-create w-100" id="submitBtn">
                            <span class="ps-spinner" hidden><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Memproses...</span>
                            <span class="ps-btn-txt"><i class="fas fa-plus-circle me-1"></i><span class="d-none d-sm-inline">&nbsp;Buat Periode Baru</span><span class="d-sm-none">Buat Baru</span></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- LIST -->
        <div class="col-12 <?= $canManagePeriode ? 'col-lg-7 col-xl-8' : '' ?> mb-4">
            <div class="ps-card shadow-sm h-100">
                <div class="ps-card-header">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i><span class="d-none d-sm-inline">&nbsp;Daftar Periode Tersedia</span><span class="d-sm-none">Daftar Periode</span></h5>
                </div>
                <div class="ps-card-body">
                    <?php if (empty($periodes)): ?>
                        <div class="ps-empty">
                            <div class="ps-empty-icon"><i class="fas fa-folder-open"></i></div>
                            <h6>Belum Ada Periode</h6>
                            <p>Silakan buat periode baru untuk memulai pelaporan</p>
                        </div>
                    <?php else: ?>
                        <div class="ps-list">
                            <?php foreach ($periodes as $p):
                                $isActive = ($current_periode == $p['id']); ?>
                                <div class="ps-item-wrap">
                                    <form action="<?= base_url('periodesemesteran/handlePeriode') ?>" method="post" class="ps-periodeform" style="flex:1;min-width:0">
                                        <input type="hidden" name="action" value="select">
                                        <input type="hidden" name="periode_id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="ps-item<?= $isActive ? ' active' : '' ?>">
                                            <div class="ps-item-icon"><i class="fas fa-calendar-check"></i></div>
                                            <div class="ps-item-body">
                                                <h6 class="ps-item-title">Semester <?= esc($p['semester']) ?> — Tahun <?= esc($p['tahun']) ?></h6>
                                                <div class="mb-1">
                                                    <?php if (($p['jenispelaporan'] ?? 'R') === 'K'): ?>
                                                        <span class="ps-badge ps-badge-k"><i class="fas fa-edit"></i> Koreksi</span>
                                                    <?php else: ?>
                                                        <span class="ps-badge ps-badge-r"><i class="fas fa-sync-alt"></i> Rutin</span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($p['jeniskoreksi']) && $p['jeniskoreksi'] > 0): ?>
                                                        <span class="ps-badge ps-badge-jk ms-1"><i class="fas fa-tag"></i> <?= $p['jeniskoreksi'] == 1 ? 'Koreksi Semesteran' : 'Pengkinian Data' ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (!empty($p['nomorsurat'])): ?>
                                                    <div class="ps-nomorsurat mb-1"><i class="fas fa-envelope me-1"></i><?= esc($p['nomorsurat']) ?></div>
                                                <?php endif; ?>
                                                <div class="ps-details">
                                                    <span><i class="fas fa-coins"></i><span class="d-none d-sm-inline"> Modal:</span> <strong>Rp <?= number_format($p['modalinti'], 0, ',', '.') ?></strong></span>
                                                    <span><i class="fas fa-chart-bar"></i><span class="d-none d-sm-inline"> Aset:</span> <strong>Rp <?= number_format($p['totalaset'], 0, ',', '.') ?></strong></span>
                                                    <span><i class="fas fa-building"></i><span class="d-none d-sm-inline"> Cabang:</span> <strong><?= esc($p['kantorcabang']) ?></strong></span>
                                                    <span><i class="fas fa-credit-card"></i><span class="d-none d-sm-inline"> ATM:</span> <strong><?= $p['atmdebit'] == 1 ? 'Ya' : 'Tidak' ?></strong></span>
                                                </div>
                                                <small class="ps-date"><i class="fas fa-clock me-1"></i><span class="d-none d-sm-inline"><?= date('d M Y, H:i', strtotime($p['created_at'])) ?></span><span class="d-sm-none"><?= date('d/m/Y', strtotime($p['created_at'])) ?></span></small>
                                            </div>
                                            <?php if ($isActive): ?>
                                                <div class="ps-active-badge"><i class="fas fa-check-circle d-none d-sm-inline"></i> Aktif</div>
                                            <?php else: ?>
                                                <i class="fas fa-chevron-right ps-arrow"></i>
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                    <?php if ($canManagePeriode): ?>
                                    <button type="button" class="ps-btn-edit" title="Edit periode"
                                        data-id="<?= $p['id'] ?>"
                                        data-semester="<?= $p['semester'] ?>"
                                        data-tahun="<?= $p['tahun'] ?>"
                                        data-modal="<?= esc($p['modalinti']) ?>"
                                        data-aset="<?= esc($p['totalaset']) ?>"
                                        data-cabang="<?= esc($p['kantorcabang']) ?>"
                                        data-atm="<?= esc($p['atmdebit']) ?>"
                                        data-jenis="<?= esc($p['jenispelaporan'] ?? 'R') ?>">
                                        <i class="fas fa-pencil-alt"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($canManagePeriode): ?>
<!-- EDIT MODAL -->
<div class="ps-overlay" id="modalEdit" hidden>
    <div class="ps-modal">
        <div class="ps-modal-header">
            <div class="ps-mh-icon"><i class="fas fa-pencil-alt"></i></div>
            <div class="ps-mh-text">
                <div class="ps-mh-title">Edit Periode</div>
                <div class="ps-mh-sub" id="mEditLabel">—</div>
            </div>
        </div>
        <form action="<?= base_url('periodesemesteran/handlePeriode') ?>" method="post" id="editForm" novalidate>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="periode_id" id="editId">
            <input type="hidden" name="kategori" id="editKat">
            <div class="ps-modal-body">

                <div class="ps-section-hd"><i class="fas fa-wallet me-2"></i>&nbsp;Data Finansial</div>

                <div class="ps-group mb-3">
                    <label class="ps-label"><i class="fas fa-coins me-1"></i>&nbsp;Modal Inti</label>
                    <div class="ps-input-wrap">
                        <span class="ps-prefix">Rp</span>
                        <input type="text" id="eModalinti" name="modalinti" class="ps-input" placeholder="0" inputmode="numeric" autocomplete="off">
                    </div>
                    <div id="eWarn6B" class="ps-inline ps-inline-danger mt-2" hidden><i class="fas fa-exclamation-triangle me-1"></i>Modal inti di bawah 6 Miliar.</div>
                    <div id="eInfoKat" class="ps-inline mt-2" hidden><i class="fas fa-info-circle me-1"></i><span id="eInfoKatTxt"></span></div>
                    <div class="ps-err">Modal inti harus diisi</div>
                </div>

                <div class="ps-group mb-1">
                    <label class="ps-label"><i class="fas fa-chart-bar me-1"></i>&nbsp;Total Aset</label>
                    <div class="ps-input-wrap">
                        <span class="ps-prefix">Rp</span>
                        <input type="text" id="eTotalaset" name="totalaset" class="ps-input" placeholder="0" inputmode="numeric" autocomplete="off">
                    </div>
                    <div class="ps-err">Total aset harus diisi</div>
                </div>

                <div class="ps-section-hd mt-4"><i class="fas fa-building me-2"></i>&nbsp;Data Operasional</div>

                <div class="ps-two-col">
                    <div class="ps-group mb-0">
                        <label class="ps-label"><i class="fas fa-code-branch me-1"></i>&nbsp;Kantor Cabang</label>
                        <input type="number" id="eKantorcabang" name="kantorcabang" class="ps-input" placeholder="0" min="0">
                        <div class="ps-err">Harus diisi</div>
                    </div>
                    <div class="ps-group mb-0">
                        <label class="ps-label"><i class="fas fa-credit-card me-1"></i>&nbsp;Penerbit ATM / Debit</label>
                        <div class="ps-jenis-group">
                            <button type="button" class="ps-jenis" id="eBtnAtmYa"><span class="ps-dot"></span>Ya</button>
                            <button type="button" class="ps-jenis" id="eBtnAtmTidak"><span class="ps-dot"></span>Tidak</button>
                        </div>
                        <input type="hidden" id="eAtmdebit" name="atmdebit" value="">
                        <div class="ps-err" id="eAtmErr">Pilihan harus dipilih</div>
                    </div>
                </div>

            </div>
            <div class="ps-modal-footer">
                <button type="button" class="ps-btn-cancel" id="btnCloseModal">
                    <i class="fas fa-times me-1"></i>&nbsp;Batal
                </button>
                <button type="submit" class="ps-btn-save" id="editSubmit">
                    <span class="ps-spinner" hidden><span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Menyimpan...</span>
                    <span class="ps-btn-txt"><i class="fas fa-save me-1"></i> &nbsp;Simpan Perubahan</span>
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<style>
* { -webkit-tap-highlight-color: transparent }

.ps-title { font-size: clamp(1.25rem,4vw,1.75rem); font-weight:700; color:#1f2937; margin-bottom:.5rem; display:flex; align-items:center }
.ps-sub { color:#6b7280; font-size:clamp(.875rem,2vw,.95rem); margin:0 }
.ps-alert { border:none; border-radius:12px; padding:1rem 1.25rem; box-shadow:0 2px 8px rgba(0,0,0,.08) }

/* Card */
.ps-card { border:none; border-radius:16px; overflow:hidden; transition:box-shadow .3s,transform .3s }
.ps-card:hover { box-shadow:0 8px 24px rgba(0,0,0,.12)!important; transform:translateY(-2px) }
.ps-card-header { padding:clamp(1rem,3vw,1.25rem) clamp(1.25rem,4vw,1.5rem); background:linear-gradient(135deg,#1f2aa8,#141863); color:#fff; font-size:clamp(.95rem,2.5vw,1.1rem); font-weight:600; display:flex; align-items:center }
.ps-card-header h5 { color:#fff }
.ps-card-body { padding:clamp(1rem,3vw,1.5rem) }

/* Form */
.ps-group { margin-bottom:1rem }
.ps-label { font-size:clamp(.85rem,2vw,.9rem); font-weight:600; color:#374151; margin-bottom:.5rem; display:flex; align-items:center }
.ps-select, .ps-input { width:100%; padding:clamp(.625rem,2vw,.75rem) 1rem; font-size:clamp(.875rem,2vw,.95rem); color:#1f2937; border:2px solid #e5e7eb; border-radius:10px; background:#fff; transition:border-color .2s,box-shadow .2s }
.ps-select { background-image:url('data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 6"%3E%3Cpath d="M0 0l5 5 5-5z" fill="none" stroke="%236b7280" stroke-width="1.5"/%3E%3C/svg%3E'); background-repeat:no-repeat; background-position:right 12px center; background-size:10px 6px; padding-right:2.5rem; appearance:none }
.ps-select:focus, .ps-input:focus { outline:none; border-color:#1f2aa8; box-shadow:0 0 0 3px rgba(31,42,168,.1) }
.ps-select.is-invalid, .ps-input.is-invalid { border-color:#dc3545; box-shadow:0 0 0 3px rgba(220,53,69,.1) }
.ps-err { display:none; font-size:.8rem; color:#dc3545; margin-top:.25rem }
.is-invalid ~ .ps-err, .is-invalid + .ps-err { display:block }
.ps-input-wrap { position:relative; display:flex }
.ps-prefix { position:absolute; left:1rem; top:50%; transform:translateY(-50%); font-size:.9rem; font-weight:600; color:#6b7280; pointer-events:none; z-index:1 }
.ps-input { padding-left:3rem }
input[type=number].ps-input { padding-left:1rem }
.ps-inline { border-radius:8px; padding:.5rem .75rem; font-size:.8rem; font-weight:500; display:flex; align-items:flex-start; gap:.375rem }
.ps-inline-danger { background:#fef2f2; color:#991b1b; border:1px solid #fecaca }
.ps-inline-warning { background:#fffbeb; color:#92400e; border:1px solid #fde68a }
.ps-inline-info { background:#eff6ff; color:#1e40af; border:1px solid #bfdbfe }
.ps-inline-success { background:#f0fdf4; color:#166534; border:1px solid #bbf7d0 }

/* Jenis toggle */
.ps-jenis-group { display:flex; gap:8px }
.ps-jenis { flex:1; height:48px; border:2px solid #e5e7eb; border-radius:10px; background:#fff; display:flex; align-items:center; justify-content:center; gap:8px; font-size:clamp(.875rem,2vw,.95rem); font-weight:600; color:#6b7280; cursor:pointer; transition:border-color .2s,background .2s,color .2s }
.ps-jenis.active-r { border-color:#1f2aa8; background:#eff4ff; color:#1f2aa8 }
.ps-jenis.active-k { border-color:#d97706; background:#fffbeb; color:#92400e }
.ps-jenis.active-y { border-color:#1f2aa8; background:#eff4ff; color:#1f2aa8 }
.ps-jenis.active-n { border-color:#6b7280; background:#f3f4f6; color:#374151 }
.ps-dot { width:16px; height:16px; border-radius:50%; border:2px solid currentColor; flex-shrink:0 }
.ps-dot.filled::after { content:''; width:8px; height:8px; border-radius:50%; background:currentColor; display:block; margin:2px }

/* Create button */
.ps-btn-create { padding:clamp(.75rem,2vw,.875rem) 1.5rem; background:linear-gradient(135deg,#1fa85a,#146337); color:#fff; border:none; border-radius:10px; font-weight:600; font-size:clamp(.9rem,2vw,1rem); transition:transform .2s,box-shadow .2s; display:flex; align-items:center; justify-content:center; cursor:pointer }
.ps-btn-create:hover { transform:translateY(-2px); box-shadow:0 6px 16px rgba(16,185,129,.3) }
.ps-btn-create:disabled { opacity:.7; cursor:not-allowed; transform:none }

/* Empty */
.ps-empty { text-align:center; padding:clamp(2rem,5vw,3rem) 1rem; color:#6b7280 }
.ps-empty-icon { font-size:clamp(3rem,8vw,4rem); color:#d1d5db; margin-bottom:1rem }

/* List */
.ps-list { display:flex; flex-direction:column; gap:clamp(.625rem,1.5vw,.75rem); max-height:560px; overflow-y:auto; padding-right:.5rem }
.ps-list::-webkit-scrollbar { width:6px }
.ps-list::-webkit-scrollbar-thumb { background:linear-gradient(135deg,#1f2aa8,#141863); border-radius:10px }
.ps-list::-webkit-scrollbar-track { background:#f3f4f6; border-radius:10px }

.ps-item-wrap { display:flex; align-items:stretch; gap:8px }
.ps-item { width:100%; display:flex; align-items:center; padding:clamp(.875rem,2vw,1rem); background:#f9fafb; border:2px solid #e5e7eb; border-radius:12px; text-align:left; transition:border-color .2s,transform .2s,box-shadow .2s; cursor:pointer }
.ps-item:hover { background:#fff; border-color:#1f2aa8; transform:translateX(4px); box-shadow:0 4px 12px rgba(0,0,0,.08) }
.ps-item.active { background:linear-gradient(135deg,#1fa85a,#146337); color:#fff; border-color:transparent }

.ps-item-icon { width:clamp(40px,8vw,45px); height:clamp(40px,8vw,45px); background:#fff; border-radius:10px; display:flex; align-items:center; justify-content:center; margin-right:clamp(.75rem,2vw,1rem); flex-shrink:0; font-size:clamp(1.1rem,2.5vw,1.25rem); color:#1f2aa8 }
.ps-item.active .ps-item-icon { background:rgba(255,255,255,.2); color:#fff }
.ps-item-body { flex:1; min-width:0 }
.ps-item-title { font-size:clamp(.9rem,2vw,1rem); font-weight:700; color:#1f2937; margin-bottom:.4rem; word-break:break-word }
.ps-item.active .ps-item-title, .ps-item.active .ps-details, .ps-item.active .ps-date, .ps-item.active .ps-nomorsurat { color:#fff }
.ps-item.active .ps-details span { color:rgba(255,255,255,.9) }
.ps-item.active .ps-date { color:rgba(255,255,255,.8) }

.ps-badge { display:inline-flex; align-items:center; gap:4px; padding:.3rem .65rem; border-radius:6px; font-size:clamp(.72rem,1.8vw,.78rem); font-weight:600 }
.ps-badge-r { background:#dbeafe; color:#1e40af }
.ps-badge-k { background:#fef3c7; color:#92400e }
.ps-badge-jk { background:#f3e8ff; color:#6b21a8 }
.ps-item.active .ps-badge-r, .ps-item.active .ps-badge-k, .ps-item.active .ps-badge-jk { background:rgba(255,255,255,.2); color:#fff }

.ps-nomorsurat { font-size:clamp(.72rem,1.8vw,.78rem); color:#6b7280; display:flex; align-items:center; gap:.375rem; margin-bottom:.35rem }

.ps-details { display:flex; flex-wrap:wrap; gap:clamp(.5rem,1.5vw,.75rem); margin-bottom:.4rem; font-size:clamp(.75rem,1.8vw,.8rem); color:#6b7280 }
.ps-date { font-size:clamp(.7rem,1.5vw,.75rem); color:#9ca3af; display:flex; align-items:center; gap:.375rem }
.ps-active-badge { background:rgba(255,255,255,.2); padding:clamp(.375rem,1.5vw,.5rem) clamp(.625rem,2vw,.75rem); border-radius:8px; display:flex; align-items:center; gap:.375rem; font-size:clamp(.8rem,1.8vw,.85rem); font-weight:600; color:#fff; flex-shrink:0; white-space:nowrap }
.ps-arrow { color:#9ca3af; font-size:clamp(.9rem,2vw,1rem); flex-shrink:0; margin-left:.5rem; transition:transform .2s,color .2s }
.ps-item:hover .ps-arrow { color:#1f2aa8; transform:translateX(3px) }

.ps-btn-edit { width:40px; min-width:40px; background:#fff; border:2px solid #e5e7eb; border-radius:10px; display:flex; align-items:center; justify-content:center; cursor:pointer; color:#6b7280; font-size:.85rem; transition:border-color .2s,color .2s,background .2s; flex-shrink:0 }
.ps-btn-edit:hover { border-color:#1f2aa8; color:#1f2aa8; background:#eff4ff }

/* Modal */
.ps-overlay { position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:1050; display:flex; align-items:center; justify-content:center }
.ps-modal { background:#fff; border-radius:16px; width:500px; max-width:calc(100vw - 2rem); overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,.2); max-height:calc(100vh - 4rem); display:flex; flex-direction:column; animation:psSlide .22s ease }
@keyframes psSlide { from { transform:translateY(24px); opacity:0 } to { transform:translateY(0); opacity:1 } }
.ps-modal-header { padding:1.1rem 1.5rem; background:linear-gradient(135deg,#1f2aa8,#141863); color:#fff; display:flex; align-items:center; gap:.9rem; flex-shrink:0 }
.ps-mh-icon { width:40px; height:40px; background:rgba(255,255,255,.15); border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0 }
.ps-mh-title { font-size:1rem; font-weight:700; line-height:1.2 }
.ps-mh-sub { font-size:.8rem; color:rgba(255,255,255,.75); margin-top:.1rem }
.ps-modal-body { padding:1.5rem; overflow-y:auto; flex:1 }
.ps-section-hd { font-size:.75rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:#6b7280; padding-bottom:.6rem; border-bottom:2px solid #f3f4f6; margin-bottom:1rem; display:flex; align-items:center }
.ps-two-col { display:grid; grid-template-columns:1fr 1fr; gap:1rem }
.ps-modal-footer { padding:1rem 1.5rem; border-top:1px solid #e5e7eb; display:flex; gap:10px; flex-shrink:0 }
.ps-btn-cancel { flex:1; height:46px; border-radius:10px; font-weight:600; background:#fff; border:2px solid #e5e7eb; color:#374151; cursor:pointer; font-size:.9rem; transition:border-color .2s,background .2s }
.ps-btn-cancel:hover { border-color:#9ca3af; background:#f9fafb }
.ps-btn-save { flex:2; height:46px; background:linear-gradient(135deg,#1fa85a,#146337); color:#fff; border:none; border-radius:10px; font-weight:600; font-size:.95rem; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:transform .2s,box-shadow .2s }
.ps-btn-save:hover { transform:translateY(-1px); box-shadow:0 4px 12px rgba(16,185,129,.3) }
.ps-btn-save:disabled { opacity:.7; cursor:not-allowed; transform:none }
.ps-spinner { display:inline-flex; align-items:center }
@media(max-width:575px) { .ps-two-col { grid-template-columns:1fr } }

@media(max-width:767px) {
    .ps-item { flex-wrap:wrap }
    .ps-active-badge { margin-top:.5rem; width:100%; justify-content:center }
    .ps-details { flex-direction:column; gap:.375rem }
    .ps-list { max-height:400px }
}
@media(max-width:575px) {
    .ps-modal-body { padding:1rem }
    .ps-modal-footer { padding:.75rem 1rem }
}
</style>

<script>
(function () {
    'use strict';

    const $ = id => document.getElementById(id);
    const fmt = n => n ? parseInt(n, 10).toLocaleString('id-ID') : '';
    const raw = el => parseInt((el.value || '').replace(/\./g, '').replace(/,/g, ''), 10) || 0;

    /* Currency */
    function onCurrency(el, cb) {
        el.addEventListener('input', function () {
            const v = this.value.replace(/\D/g, '');
            this.value = v ? parseInt(v, 10).toLocaleString('id-ID') : '';
            cb && cb(raw(this));
        });
        el.addEventListener('focus', function () { if (this.value === '0') this.value = ''; });
    }

    /* Kategori */
    function setKat(num, cfg) {
        cfg.warn.hidden = true; cfg.info.hidden = true;
        if (num <= 0) { cfg.kat.value = ''; return; }
        if (num < 6e9) {
            cfg.kat.value = 'B'; cfg.warn.hidden = false;
            cfg.info.className = 'ps-inline ps-inline-warning mt-2';
            cfg.infoTxt.innerHTML = 'Kategori B &ndash; 4 Risiko';
            cfg.info.hidden = false;
        } else if (num < 5e10) {
            cfg.kat.value = 'B';
            cfg.info.className = 'ps-inline ps-inline-info mt-2';
            cfg.infoTxt.innerHTML = 'Kategori B &ndash; 4 Risiko (modal inti di bawah 50 Miliar)';
            cfg.info.hidden = false;
        } else {
            cfg.kat.value = 'A';
            cfg.info.className = 'ps-inline ps-inline-success mt-2';
            cfg.infoTxt.innerHTML = 'Kategori A &ndash; 6 Risiko (modal inti di atas 50 Miliar)';
            cfg.info.hidden = false;
        }
    }

    /* Jenis toggle factory */
    function mkJenis(hiddenId, rId, kId) {
        const hidden = $(hiddenId), btnR = $(rId), btnK = $(kId);
        function set(v) {
            hidden.value = v;
            btnR.className = 'ps-jenis' + (v === 'R' ? ' active-r' : '');
            btnR.querySelector('.ps-dot').className = 'ps-dot' + (v === 'R' ? ' filled' : '');
            btnK.className = 'ps-jenis' + (v === 'K' ? ' active-k' : '');
            btnK.querySelector('.ps-dot').className = 'ps-dot' + (v === 'K' ? ' filled' : '');
        }
        btnR.addEventListener('click', () => set('R'));
        btnK.addEventListener('click', () => set('K'));
        return set;
    }

    /* ATM toggle factory */
    function mkAtm(hiddenId, yaId, tidakId) {
        const hidden = $(hiddenId), btnYa = $(yaId), btnTidak = $(tidakId);
        function set(v) {
            hidden.value = v;
            btnYa.className = 'ps-jenis' + (v == '1' ? ' active-y' : '');
            btnYa.querySelector('.ps-dot').className = 'ps-dot' + (v == '1' ? ' filled' : '');
            btnTidak.className = 'ps-jenis' + (v == '2' ? ' active-n' : '');
            btnTidak.querySelector('.ps-dot').className = 'ps-dot' + (v == '2' ? ' filled' : '');
        }
        btnYa.addEventListener('click', () => set('1'));
        btnTidak.addEventListener('click', () => set('2'));
        return set;
    }

    /* ── CREATE FORM ── (hanya ada di DOM untuk PE/Admin) */
    const createForm = $('createForm');
    if (createForm) {
        const createKat = { warn: $('warn6B'), info: $('infoKat'), infoTxt: $('infoKatTxt'), kat: $('kategori') };
        const mInput = $('modalinti'), aInput = $('totalaset');

        if (mInput) {
            onCurrency(mInput, n => setKat(n, createKat));
            if (mInput.value) setKat(raw(mInput), createKat);
        }
        if (aInput) onCurrency(aInput);

        const jenisHiddenCreate = $('jenispelaporan');
        const setCreateJenis = mkJenis('jenispelaporan', 'btnR', 'btnK');
        setCreateJenis(jenisHiddenCreate.value || 'R');

        (function () {
            const nomorField = $('fieldNomorSurat'), jenisField = $('fieldJenisKoreksi');
            const nomorInput = $('nomorsurat'), jenisSelect = $('jeniskoreksiSelect'), jenisHid = $('jeniskoreksiHidden');
            function toggle() {
                const isK = jenisHiddenCreate.value === 'K';
                nomorField.style.display = isK ? 'block' : 'none';
                jenisField.style.display = isK ? 'block' : 'none';
                jenisSelect.disabled = !isK;
                jenisSelect.name = isK ? 'jeniskoreksi' : '';
                jenisHid.name = isK ? '' : 'jeniskoreksi';
                nomorInput.required = isK;
                jenisSelect.required = isK;
                if (!isK) { nomorInput.value = ''; jenisSelect.value = ''; jenisHid.value = '0'; nomorInput.classList.remove('is-invalid'); jenisSelect.classList.remove('is-invalid'); }
            }
            $('btnR').addEventListener('click', toggle);
            $('btnK').addEventListener('click', toggle);
            jenisSelect.addEventListener('change', function () { if (!this.disabled) jenisHid.value = this.value; });
            toggle();
        })();

        const setAtmCreate = mkAtm('atmdebit', 'btnAtmYa', 'btnAtmTidak');
        const initAtmVal = $('atmdebit').value;
        if (initAtmVal) setAtmCreate(initAtmVal);

        let createLock = false;
        createForm.addEventListener('submit', function (e) {
            const semester = $('semester'), tahun = $('tahun'), modalinti = $('modalinti'), totalaset = $('totalaset'), kantorcabang = $('kantorcabang'), atmdebit = $('atmdebit'), nomorsurat = $('nomorsurat'), jeniskoreksiSelect = $('jeniskoreksiSelect');
            let ok = true;
            [semester, tahun, modalinti, totalaset, kantorcabang, nomorsurat, jeniskoreksiSelect].forEach(f => f && f.classList.remove('is-invalid'));
            $('atmErr') && $('atmErr').style.removeProperty('display');
            if (!semester.value) { semester.classList.add('is-invalid'); ok = false; }
            if (!tahun.value) { tahun.classList.add('is-invalid'); ok = false; }
            if (raw(modalinti) <= 0) { modalinti.classList.add('is-invalid'); ok = false; }
            if (raw(totalaset) <= 0) { totalaset.classList.add('is-invalid'); ok = false; }
            if (!kantorcabang.value && kantorcabang.value !== '0') { kantorcabang.classList.add('is-invalid'); ok = false; }
            if (!atmdebit.value) { $('atmErr').style.display = 'block'; ok = false; }
            if ($('jenispelaporan').value === 'K') {
                if (!nomorsurat.value.trim()) { nomorsurat.classList.add('is-invalid'); ok = false; }
                if (!jeniskoreksiSelect.value) { jeniskoreksiSelect.classList.add('is-invalid'); ok = false; }
            }
            if (!ok) { e.preventDefault(); document.querySelector('.is-invalid')?.scrollIntoView({ behavior: 'smooth', block: 'center' }); return; }
            if (createLock) { e.preventDefault(); return; }
            createLock = true;
            modalinti.value = raw(modalinti);
            totalaset.value = raw(totalaset);
            const btn = $('submitBtn');
            btn.querySelector('.ps-spinner').hidden = false;
            btn.querySelector('.ps-btn-txt').hidden = true;
            btn.disabled = true;
        });
    }

    /* ── EDIT MODAL ── (hanya ada di DOM untuk PE/Admin) */
    const overlay = $('modalEdit');
    if (overlay) {
        const editKat = { warn: $('eWarn6B'), info: $('eInfoKat'), infoTxt: $('eInfoKatTxt'), kat: $('editKat') };
        const eMInput = $('eModalinti'), eAInput = $('eTotalaset');
        const setAtmEdit = mkAtm('eAtmdebit', 'eBtnAtmYa', 'eBtnAtmTidak');

        if (eMInput) onCurrency(eMInput, n => setKat(n, editKat));
        if (eAInput) onCurrency(eAInput);

        document.querySelectorAll('.ps-btn-edit').forEach(btn => {
            btn.addEventListener('click', function () {
                const { id, semester, tahun, modal, aset, cabang, atm } = this.dataset;
                $('editId').value = id;
                $('mEditLabel').textContent = 'Semester ' + semester + ' — Tahun ' + tahun;
                const rm = (modal || '').replace(/\D/g, '');
                const ra = (aset || '').replace(/\D/g, '');
                eMInput.value = rm ? parseInt(rm, 10).toLocaleString('id-ID') : '';
                eAInput.value = ra ? parseInt(ra, 10).toLocaleString('id-ID') : '';
                $('eKantorcabang').value = cabang || '';
                setAtmEdit(atm || '');
                setKat(raw(eMInput), editKat);
                [eMInput, eAInput, $('eKantorcabang')].forEach(f => f && f.classList.remove('is-invalid'));
                $('eAtmErr') && ($('eAtmErr').style.cssText = '');
                overlay.hidden = false;
                document.body.style.overflow = 'hidden';
            });
        });

        function closeModal() { overlay.hidden = true; document.body.style.overflow = ''; }
        $('btnCloseModal').addEventListener('click', closeModal);
        overlay.addEventListener('click', e => { if (e.target === overlay) closeModal(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape' && !overlay.hidden) closeModal(); });

        let editLock = false;
        $('editForm').addEventListener('submit', function (e) {
            const modal = $('eModalinti'), aset = $('eTotalaset'), cabang = $('eKantorcabang'), atm = $('eAtmdebit');
            let ok = true;
            [modal, aset, cabang].forEach(f => f.classList.remove('is-invalid'));
            $('eAtmErr') && ($('eAtmErr').style.cssText = '');
            if (raw(modal) <= 0) { modal.classList.add('is-invalid'); ok = false; }
            if (raw(aset) <= 0) { aset.classList.add('is-invalid'); ok = false; }
            if (cabang.value === '' || cabang.value < 0) { cabang.classList.add('is-invalid'); ok = false; }
            if (!atm.value) { $('eAtmErr').style.display = 'block'; ok = false; }
            if (!ok) return e.preventDefault();
            if (editLock) return e.preventDefault();
            editLock = true;
            modal.value = raw(modal);
            aset.value = raw(aset);
            const btn = $('editSubmit');
            btn.querySelector('.ps-spinner').hidden = false;
            btn.querySelector('.ps-btn-txt').hidden = true;
            btn.disabled = true;
        });
    }

    /* Period select lock — selalu ada untuk semua role */
    document.querySelectorAll('.ps-periodeform').forEach(f => {
        f.addEventListener('submit', function () { this.querySelector('button').style.opacity = '.6'; });
    });

    /* Auto-dismiss alerts */
    setTimeout(() => {
        document.querySelectorAll('.ps-alert').forEach(el => { try { new bootstrap.Alert(el).close(); } catch (_) {} });
    }, 5000);

})();
</script>