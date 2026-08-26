<div class="container-fluid px-3 px-md-4 py-4">

    <div class="mb-4">
        <h1 class="pt-title"><i class="fas fa-file-invoice me-2"></i>&nbsp;Periode Laporan Transparansi Tahunan</h1>
        <p class="pt-sub">Kelola periode laporan transparansi tahunan</p>
    </div>

    <?php if ($msg = session()->getFlashdata('message')): ?>
        <div class="alert alert-success alert-dismissible fade show pt-alert" role="alert">
            <i class="fas fa-check-circle me-2"></i><?= $msg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($err = session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show pt-alert" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?= $err ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($validation) && $validation->getErrors()): ?>
        <div class="alert alert-danger alert-dismissible fade show pt-alert" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <ul class="mb-0"><?php foreach ($validation->getErrors() as $e): ?>
                    <li><?= esc($e) ?></li><?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12 col-lg-5 col-xl-4 mb-4">
            <div class="pt-card shadow-sm h-100">
                <div class="pt-card-header">
                    <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i><span class="d-none d-sm-inline">&nbsp;Buat
                        Periode Baru</span><span class="d-sm-none">Buat Baru</span>
                    </h5>
                </div>
                <div class="pt-card-body">
                    <form action="<?= base_url('periodetahunan/handlePeriode') ?>" method="post" id="createForm"
                        novalidate>
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="kategori" id="kategori">

                        <div class="pt-group mb-3">
                            <label class="pt-label"><i class="fas fa-calendar mr-1"></i>Tahun</label>
                            <select id="tahun" name="tahun" class="pt-select" required>
                                <option value="">Pilih Tahun</option>
                                <?php for ($y = 2024; $y <= 2030; $y++): ?>
                                    <option value="<?= $y ?>" <?= (old('tahun', date('Y')) == $y) ? ' selected' : '' ?>>
                                        <?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                            <div class="pt-err">Tahun harus dipilih</div>
                        </div>

                        <div class="pt-group mb-3">
                            <label class="pt-label"><i class="fas fa-coins mr-1"></i>Modal Inti</label>
                            <div class="pt-input-wrap">
                                <span class="pt-prefix">Rp</span>
                                <input type="text" id="modalinti" name="modalinti" class="pt-input" placeholder="0"
                                    inputmode="numeric" autocomplete="off"
                                    value="<?= old('modalinti') ? number_format((int) old('modalinti'), 0, ',', '.') : '' ?>">
                            </div>
                            <div id="warn6B" class="pt-inline pt-inline-danger mt-1" hidden><i
                                    class="fas fa-exclamation-triangle me-1"></i>Modal inti di bawah 6 Miliar, silakan
                                cek kembali.</div>
                            <div id="infoKat" class="pt-inline mt-2" hidden><i class="fas fa-info-circle me-1"></i><span
                                    id="infoKatTxt"></span></div>
                            <div class="pt-err">Modal inti harus diisi</div>
                        </div>

                        <div class="pt-group mb-4">
                            <label class="pt-label"><i class="fas fa-chart-bar mr-1"></i>Total Aset</label>
                            <div class="pt-input-wrap">
                                <span class="pt-prefix">Rp</span>
                                <input type="text" id="totalaset" name="totalaset" class="pt-input" placeholder="0"
                                    inputmode="numeric" autocomplete="off"
                                    value="<?= old('totalaset') ? number_format((int) old('totalaset'), 0, ',', '.') : '' ?>">
                            </div>
                            <div class="pt-err">Total aset harus diisi</div>
                        </div>

                        <div class="pt-group mb-3">
                            <label class="pt-label"><i class="fas fa-file-alt mr-1"></i>Jenis Pelaporan</label>
                            <div class="pt-jenis-group">
                                <button type="button" class="pt-jenis active-r" id="btnR" data-val="R"><span
                                        class="pt-dot filled"></span>Rutin</button>
                                <button type="button" class="pt-jenis" id="btnK" data-val="K"><span
                                        class="pt-dot"></span>Koreksi</button>
                            </div>
                            <input type="hidden" id="jenispelaporan" name="jenispelaporan" value="R">
                        </div>

                        <button type="submit" class="pt-btn-create w-100" id="submitBtn">
                            <span class="pt-spinner" hidden><span class="spinner-border spinner-border-sm me-2"
                                    role="status" aria-hidden="true"></span>Memproses...</span>
                            <span class="pt-btn-txt"><i class="fas fa-plus-circle mr-1"></i><span
                                    class="d-none d-sm-inline">Buat Periode Baru</span><span class="d-sm-none">Buat
                                    Baru</span></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Daftar Periode -->
        <div class="col-12 col-lg-7 col-xl-8 mb-4">
            <div class="pt-card shadow-sm h-100">
                <div class="pt-card-header">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i><span class="d-none d-sm-inline">&nbsp;Daftar
                            Periode Tersedia</span><span class="d-sm-none">Daftar Periode</span></h5>
                </div>
                <div class="pt-card-body">
                    <?php if (empty($periodes)): ?>
                        <div class="pt-empty">
                            <div class="pt-empty-icon"><i class="fas fa-folder-open"></i></div>
                            <h6>Belum Ada Periode</h6>
                            <p>Silakan buat periode baru untuk memulai pelaporan</p>
                        </div>
                    <?php else: ?>
                        <div class="pt-list">
                            <?php foreach ($periodes as $p):
                                $isActive = ($current_periode == $p['id']); ?>
                                <div class="pt-item-wrap">
                                    <form action="<?= base_url('periodetahunan/handlePeriode') ?>" method="post"
                                        class="pt-periodeform" style="flex:1;min-width:0">
                                        <input type="hidden" name="action" value="select">
                                        <input type="hidden" name="periode_id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="pt-item<?= $isActive ? ' active' : '' ?>">
                                            <div class="pt-item-icon"><i class="fas fa-calendar-check"></i></div>
                                            <div class="pt-item-body">
                                                <h6 class="pt-item-title">Laporan Transparansi Tahun <?= esc($p['tahun']) ?>
                                                </h6>
                                                <div class="mb-1">
                                                    <?php if (($p['jenispelaporan'] ?? 'R') === 'K'): ?>
                                                        <span class="pt-badge pt-badge-k"><i class="fas fa-edit"></i> Koreksi</span>
                                                    <?php else: ?>
                                                        <span class="pt-badge pt-badge-r"><i class="fas fa-sync-alt"></i>
                                                            Rutin</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="pt-details">
                                                    <span><i class="fas fa-coins"></i><span class="d-none d-sm-inline"> Modal
                                                            Inti:</span> <strong>Rp
                                                            <?= !empty($p['modalinti']) ? number_format($p['modalinti'], 0, ',', '.') : '—' ?></strong></span>
                                                    <span><i class="fas fa-chart-bar"></i><span class="d-none d-sm-inline">
                                                            Total Aset:</span> <strong>Rp
                                                            <?= !empty($p['totalaset']) ? number_format($p['totalaset'], 0, ',', '.') : '—' ?></strong></span>
                                                </div>
                                                <small class="pt-date"><i class="fas fa-clock me-1"></i><span
                                                        class="d-none d-sm-inline"><?= date('d M Y, H:i', strtotime($p['created_at'])) ?></span><span
                                                        class="d-sm-none"><?= date('d/m/Y', strtotime($p['created_at'])) ?></span></small>
                                            </div>
                                            <?php if ($isActive): ?>
                                                <div class="pt-active-badge"><i class="fas fa-check-circle d-none d-sm-inline"></i>
                                                    Aktif</div>
                                            <?php else: ?>
                                                <i class="fas fa-chevron-right pt-arrow"></i>
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                    <button type="button" class="pt-btn-edit" title="Edit periode" data-id="<?= $p['id'] ?>"
                                        data-tahun="<?= $p['tahun'] ?>" data-modal="<?= esc($p['modalinti']) ?>"
                                        data-aset="<?= esc($p['totalaset']) ?>"
                                        data-jenis="<?= esc($p['jenispelaporan'] ?? 'R') ?>">
                                        <i class="fas fa-pencil-alt"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="pt-overlay" id="modalEdit" hidden>
    <div class="pt-modal">
        <div class="pt-modal-header"><i class="fas fa-pencil-alt mr-1"></i>Edit Periode Tahun&nbsp;<span
                id="mEditTahun">-</span></div>
        <form action="<?= base_url('periodetahunan/handlePeriode') ?>" method="post" id="editForm" novalidate>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="periode_id" id="editId">
            <input type="hidden" name="kategori" id="editKat">
            <div class="pt-modal-body">
                <div class="pt-group mb-3">
                    <label class="pt-label"><i class="fas fa-file-alt mr-1"></i>Jenis Pelaporan</label>
                    <div class="pt-jenis-group">
                        <button type="button" class="pt-jenis active-r" id="eBtnR" data-val="R"><span
                                class="pt-dot filled"></span>Rutin</button>
                        <button type="button" class="pt-jenis" id="eBtnK" data-val="K"><span
                                class="pt-dot"></span>Koreksi</button>
                    </div>
                    <input type="hidden" id="editJenis" name="jenispelaporan" value="R">
                </div>
                <div class="pt-group mb-3">
                    <label class="pt-label"><i class="fas fa-coins mr-1"></i>Modal Inti</label>
                    <div class="pt-input-wrap">
                        <span class="pt-prefix">Rp</span>
                        <input type="text" id="eModalinti" name="modalinti" class="pt-input" placeholder="0"
                            inputmode="numeric" autocomplete="off">
                    </div>
                    <div id="eWarn6B" class="pt-inline pt-inline-danger mt-2" hidden><i
                            class="fas fa-exclamation-triangle me-1"></i>Modal inti di bawah 6 Miliar.</div>
                    <div id="eInfoKat" class="pt-inline mt-2" hidden><i class="fas fa-info-circle me-1"></i><span
                            id="eInfoKatTxt"></span></div>
                    <div class="pt-err" id="eErrModal">Modal inti harus diisi</div>
                </div>
                <div class="pt-group mb-3">
                    <label class="pt-label"><i class="fas fa-chart-bar mr-1"></i>Total Aset</label>
                    <div class="pt-input-wrap">
                        <span class="pt-prefix">Rp</span>
                        <input type="text" id="eTotalaset" name="totalaset" class="pt-input" placeholder="0"
                            inputmode="numeric" autocomplete="off">
                    </div>
                    <div class="pt-err" id="eErrAset">Total aset harus diisi</div>
                </div>
            </div>
            <div class="pt-modal-footer">
                <button type="button" class="btn btn-outline-secondary pt-btn-cancel" id="btnCloseModal"><i
                        class="fas fa-times mr-1"></i>Batal</button>
                <button type="submit" class="pt-btn-save" id="editSubmit">
                    <span class="pt-spinner" hidden><span class="spinner-border spinner-border-sm mr-1" role="status"
                            aria-hidden="true"></span>Menyimpan...</span>
                    <span class="pt-btn-txt"><i class="fas fa-save mr-1"></i>Simpan Perubahan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    * {
        -webkit-tap-highlight-color: transparent
    }

    .pt-title {
        font-size: clamp(1.25rem, 4vw, 1.75rem);
        font-weight: 700;
        color: #1f2937;
        margin-bottom: .5rem;
        display: flex;
        align-items: center
    }

    .pt-sub {
        color: #6b7280;
        font-size: clamp(.875rem, 2vw, .95rem);
        margin: 0
    }

    .pt-alert {
        border: none;
        border-radius: 12px;
        padding: 1rem 1.25rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .08)
    }

    /* ── Card ── */
    .pt-card {
        border: none;
        border-radius: 16px;
        overflow: hidden;
        transition: box-shadow .3s, transform .3s
    }

    .pt-card:hover {
        box-shadow: 0 8px 24px rgba(0, 0, 0, .12) !important;
        transform: translateY(-2px)
    }

    .pt-card-header {
        padding: clamp(1rem, 3vw, 1.25rem) clamp(1.25rem, 4vw, 1.5rem);
        background: linear-gradient(135deg, #1f2aa8, #141863);
        color: #fff;
        font-size: clamp(.95rem, 2.5vw, 1.1rem);
        font-weight: 600;
        display: flex;
        align-items: center
    }

    .pt-card-header h5 {
        color: #fff
    }

    .pt-card-body {
        padding: clamp(1rem, 3vw, 1.5rem)
    }

    .pt-group {
        margin-bottom: 1rem
    }

    .pt-label {
        font-size: clamp(.85rem, 2vw, .9rem);
        font-weight: 600;
        color: #374151;
        margin-bottom: .5rem;
        display: flex;
        align-items: center
    }

    .pt-select,
    .pt-input {
        width: 100%;
        padding: clamp(.625rem, 2vw, .75rem) 1rem;
        font-size: clamp(.875rem, 2vw, .95rem);
        color: #1f2937;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        background: #fff;
        transition: border-color .2s, box-shadow .2s
    }

    .pt-select {
        background-image: url('data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 6"%3E%3Cpath d="M0 0l5 5 5-5z" fill="none" stroke="%236b7280" stroke-width="1.5"/%3E%3C/svg%3E');
        background-repeat: no-repeat;
        background-position: right 12px center;
        background-size: 10px 6px;
        padding-right: 2.5rem;
        appearance: none
    }

    .pt-select:focus,
    .pt-input:focus {
        outline: none;
        border-color: #1f2aa8;
        box-shadow: 0 0 0 3px rgba(31, 42, 168, .1)
    }

    .pt-select.is-invalid,
    .pt-input.is-invalid {
        border-color: #dc3545;
        box-shadow: 0 0 0 3px rgba(220, 53, 69, .1)
    }

    .pt-err {
        display: none;
        font-size: .8rem;
        color: #dc3545;
        margin-top: .25rem
    }

    .is-invalid~.pt-err,
    .is-invalid+.pt-err {
        display: block
    }

    .pt-input-wrap {
        position: relative;
        display: flex
    }

    .pt-prefix {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        font-size: .9rem;
        font-weight: 600;
        color: #6b7280;
        pointer-events: none;
        z-index: 1
    }

    .pt-input {
        padding-left: 3rem
    }

    .pt-inline {
        border-radius: 8px;
        padding: .5rem .75rem;
        font-size: .8rem;
        font-weight: 500;
        display: flex;
        align-items: flex-start;
        gap: .375rem
    }

    .pt-inline-danger {
        background: #fef2f2;
        color: #991b1b;
        border: 1px solid #fecaca
    }

    .pt-inline-warning {
        background: #fffbeb;
        color: #92400e;
        border: 1px solid #fde68a
    }

    .pt-inline-info {
        background: #eff6ff;
        color: #1e40af;
        border: 1px solid #bfdbfe
    }

    .pt-inline-success {
        background: #f0fdf4;
        color: #166534;
        border: 1px solid #bbf7d0
    }

    .pt-jenis-group {
        display: flex;
        gap: 8px
    }

    .pt-jenis {
        flex: 1;
        height: 48px;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-size: clamp(.875rem, 2vw, .95rem);
        font-weight: 600;
        color: #6b7280;
        cursor: pointer;
        transition: border-color .2s, background .2s, color .2s
    }

    .pt-jenis.active-r {
        border-color: #1f2aa8;
        background: #eff4ff;
        color: #1f2aa8
    }

    .pt-jenis.active-k {
        border-color: #d97706;
        background: #fffbeb;
        color: #92400e
    }

    .pt-dot {
        width: 16px;
        height: 16px;
        border-radius: 50%;
        border: 2px solid currentColor;
        flex-shrink: 0
    }

    .pt-dot.filled::after {
        content: '';
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: currentColor;
        display: block;
        margin: 2px
    }

    .pt-btn-create {
        padding: clamp(.75rem, 2vw, .875rem) 1.5rem;
        background: linear-gradient(135deg, #1fa85a, #146337);
        color: #fff;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        font-size: clamp(.9rem, 2vw, 1rem);
        transition: transform .2s, box-shadow .2s;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer
    }

    .pt-btn-create:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(16, 185, 129, .3)
    }

    .pt-btn-create:disabled {
        opacity: .7;
        cursor: not-allowed;
        transform: none
    }

    .pt-empty {
        text-align: center;
        padding: clamp(2rem, 5vw, 3rem) 1rem;
        color: #6b7280
    }

    .pt-empty-icon {
        font-size: clamp(3rem, 8vw, 4rem);
        color: #d1d5db;
        margin-bottom: 1rem
    }

    .pt-list {
        display: flex;
        flex-direction: column;
        gap: clamp(.625rem, 1.5vw, .75rem);
        max-height: 520px;
        overflow-y: auto;
        padding-right: .5rem
    }

    .pt-list::-webkit-scrollbar {
        width: 6px
    }

    .pt-list::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, #1f2aa8, #141863);
        border-radius: 10px
    }

    .pt-list::-webkit-scrollbar-track {
        background: #f3f4f6;
        border-radius: 10px
    }

    .pt-item-wrap {
        display: flex;
        align-items: stretch;
        gap: 8px
    }

    .pt-item {
        width: 100%;
        display: flex;
        align-items: center;
        padding: clamp(.875rem, 2vw, 1rem);
        background: #f9fafb;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        text-align: left;
        transition: border-color .2s, transform .2s, box-shadow .2s;
        cursor: pointer
    }

    .pt-item:hover {
        background: #fff;
        border-color: #1f2aa8;
        transform: translateX(4px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, .08)
    }

    .pt-item.active {
        background: linear-gradient(135deg, #1fa85a, #146337);
        color: #fff;
        border-color: transparent
    }

    .pt-item-icon {
        width: clamp(40px, 8vw, 45px);
        height: clamp(40px, 8vw, 45px);
        background: #fff;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: clamp(.75rem, 2vw, 1rem);
        flex-shrink: 0;
        font-size: clamp(1.1rem, 2.5vw, 1.25rem);
        color: #1f2aa8
    }

    .pt-item.active .pt-item-icon {
        background: rgba(255, 255, 255, .2);
        color: #fff
    }

    .pt-item-body {
        flex: 1;
        min-width: 0
    }

    .pt-item-title {
        font-size: clamp(.9rem, 2vw, 1rem);
        font-weight: 700;
        color: #1f2937;
        margin-bottom: .4rem;
        word-break: break-word
    }

    .pt-item.active .pt-item-title,
    .pt-item.active .pt-details,
    .pt-item.active .pt-date {
        color: #fff
    }

    .pt-item.active .pt-details span {
        color: rgba(255, 255, 255, .9)
    }

    .pt-item.active .pt-date {
        color: rgba(255, 255, 255, .8)
    }

    .pt-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: .3rem .65rem;
        border-radius: 6px;
        font-size: clamp(.72rem, 1.8vw, .78rem);
        font-weight: 600
    }

    .pt-badge-r {
        background: #dbeafe;
        color: #1e40af
    }

    .pt-badge-k {
        background: #fef3c7;
        color: #92400e
    }

    .pt-item.active .pt-badge-r,
    .pt-item.active .pt-badge-k {
        background: rgba(255, 255, 255, .2);
        color: #fff
    }

    .pt-details {
        display: flex;
        flex-wrap: wrap;
        gap: clamp(.5rem, 1.5vw, .75rem);
        margin-bottom: .4rem;
        font-size: clamp(.75rem, 1.8vw, .8rem);
        color: #6b7280
    }

    .pt-date {
        font-size: clamp(.7rem, 1.5vw, .75rem);
        color: #9ca3af;
        display: flex;
        align-items: center;
        gap: .375rem
    }

    .pt-active-badge {
        background: rgba(255, 255, 255, .2);
        padding: clamp(.375rem, 1.5vw, .5rem) clamp(.625rem, 2vw, .75rem);
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: .375rem;
        font-size: clamp(.8rem, 1.8vw, .85rem);
        font-weight: 600;
        color: #fff;
        flex-shrink: 0;
        white-space: nowrap
    }

    .pt-arrow {
        color: #9ca3af;
        font-size: clamp(.9rem, 2vw, 1rem);
        flex-shrink: 0;
        margin-left: .5rem;
        transition: transform .2s, color .2s
    }

    .pt-item:hover .pt-arrow {
        color: #1f2aa8;
        transform: translateX(3px)
    }

    .pt-btn-edit {
        width: 40px;
        min-width: 40px;
        background: #fff;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: #6b7280;
        font-size: .85rem;
        transition: border-color .2s, color .2s, background .2s;
        flex-shrink: 0
    }

    .pt-btn-edit:hover {
        border-color: #1f2aa8;
        color: #1f2aa8;
        background: #eff4ff
    }

    .pt-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .5);
        z-index: 1050;
        display: flex;
        align-items: center;
        justify-content: center
    }

    .pt-modal {
        background: #fff;
        border-radius: 16px;
        width: 480px;
        max-width: calc(100vw - 2rem);
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0, 0, 0, .2);
        max-height: calc(100vh - 4rem);
        display: flex;
        flex-direction: column;
        animation: ptSlide .22s ease
    }

    @keyframes ptSlide {
        from {
            transform: translateY(24px);
            opacity: 0
        }

        to {
            transform: translateY(0);
            opacity: 1
        }
    }

    .pt-modal-header {
        padding: 1.25rem 1.5rem;
        background: linear-gradient(135deg, #1f2aa8, #141863);
        color: #fff;
        font-size: 1rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        flex-shrink: 0
    }

    .pt-modal-body {
        padding: 1.5rem;
        overflow-y: auto;
        flex: 1
    }

    .pt-modal-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid #e5e7eb;
        display: flex;
        gap: 10px;
        flex-shrink: 0
    }

    .pt-btn-cancel {
        flex: 1;
        height: 46px;
        border-radius: 10px;
        font-weight: 600
    }

    .pt-btn-save {
        flex: 2;
        height: 46px;
        background: linear-gradient(135deg, #1fa85a, #146337);
        color: #fff;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        font-size: .95rem;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: transform .2s, box-shadow .2s
    }

    .pt-btn-save:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, .3)
    }

    .pt-btn-save:disabled {
        opacity: .7;
        cursor: not-allowed;
        transform: none
    }

    .pt-spinner {
        display: inline-flex;
        align-items: center
    }

    @media(max-width:991px) {
        .pt-details {
            flex-direction: column;
            gap: .375rem
        }

        .pt-list {
            max-height: 400px
        }
    }

    @media(max-width:767px) {
        .pt-item {
            flex-wrap: wrap
        }

        .pt-active-badge {
            margin-top: .5rem;
            width: 100%;
            justify-content: center
        }
    }
</style>

<script>
    (function () {
        'use strict';

        const $ = id => document.getElementById(id);
        const fmt = n => n ? parseInt(n, 10).toLocaleString('id-ID') : '';
        const raw = el => parseInt(el.value.replace(/\./g, ''), 10) || 0;

        function onCurrency(el, cb) {
            el.addEventListener('input', function () {
                const v = this.value.replace(/\D/g, '');
                this.value = v ? parseInt(v, 10).toLocaleString('id-ID') : '';
                cb && cb(raw(this));
            });
            el.addEventListener('focus', function () { if (this.value === '0') this.value = ''; });
        }

        function setKat(num, { warn, info, infoTxt, kat }) {
            warn.hidden = true; info.hidden = true;
            if (num <= 0) { kat.value = ''; return; }
            if (num < 6e9) {
                kat.value = 'B'; warn.hidden = false;
                info.className = 'pt-inline pt-inline-warning mt-2';
                infoTxt.innerHTML = 'Modal inti masih dibawah <strong>6 Milyar Rupiah!</strong>';
                info.hidden = false;
            } else if (num < 5e10) {
                kat.value = 'B';
                info.className = 'pt-inline pt-inline-info mt-2';
                infoTxt.innerHTML = 'Modal inti sudah diatas <strong>6 Miliar</strong> tetapi masih dibawah <strong>50 Miliar Rupiah</strong>';
                info.hidden = false;
            } else {
                kat.value = 'A';
                info.className = 'pt-inline pt-inline-success mt-2';
                infoTxt.innerHTML = 'Modal inti sudah berada diatas <strong>50 Miliar Rupiah</strong>';
                info.hidden = false;
            }
        }

        function mkJenis(hiddenId, rId, kId) {
            const hidden = $('' + hiddenId), btnR = $(rId), btnK = $(kId);
            function set(v) {
                hidden.value = v;
                btnR.className = 'pt-jenis' + (v === 'R' ? ' active-r' : '');
                btnR.querySelector('.pt-dot').className = 'pt-dot' + (v === 'R' ? ' filled' : '');
                btnK.className = 'pt-jenis' + (v === 'K' ? ' active-k' : '');
                btnK.querySelector('.pt-dot').className = 'pt-dot' + (v === 'K' ? ' filled' : '');
            }
            btnR.addEventListener('click', () => set('R'));
            btnK.addEventListener('click', () => set('K'));
            return set;
        }

        const createKat = { warn: $('warn6B'), info: $('infoKat'), infoTxt: $('infoKatTxt'), kat: $('kategori') };
        const mInput = $('modalinti'), aInput = $('totalaset');
        if (mInput) {
            onCurrency(mInput, n => setKat(n, createKat));
            if (mInput.value) setKat(raw(mInput), createKat);
        }
        if (aInput) onCurrency(aInput);
        mkJenis('jenispelaporan', 'btnR', 'btnK');

        let createLock = false;
        $('createForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const tahun = $('tahun'), modal = $('modalinti'), aset = $('totalaset');
            let ok = true;
            [tahun, modal, aset].forEach(f => f.classList.remove('is-invalid'));
            if (!tahun.value) { tahun.classList.add('is-invalid'); ok = false; }
            if (raw(modal) <= 0) { modal.classList.add('is-invalid'); ok = false; }
            if (raw(aset) <= 0) { aset.classList.add('is-invalid'); ok = false; }
            if (!ok) { this.querySelector('.is-invalid')?.scrollIntoView({ behavior: 'smooth', block: 'center' }); return; }
            if (createLock) return; createLock = true;
            modal.value = raw(modal); aset.value = raw(aset);
            $('submitBtn').querySelector('.pt-spinner').hidden = false;
            $('submitBtn').querySelector('.pt-btn-txt').hidden = true;
            this.submit();
        });

        const overlay = $('modalEdit');
        const editKat = { warn: $('eWarn6B'), info: $('eInfoKat'), infoTxt: $('eInfoKatTxt'), kat: $('editKat') };
        const eMInput = $('eModalinti'), eAInput = $('eTotalaset');
        const setEditJenis = mkJenis('editJenis', 'eBtnR', 'eBtnK');

        if (eMInput) onCurrency(eMInput, n => setKat(n, editKat));
        if (eAInput) onCurrency(eAInput);

        document.querySelectorAll('.pt-btn-edit').forEach(btn => {
            btn.addEventListener('click', function () {
                const { id, tahun, modal, aset, jenis } = this.dataset;
                $('editId').value = id;
                $('mEditTahun').textContent = tahun;
                const rm = String(modal).replace(/\D/g, '');
                const ra = String(aset).replace(/\D/g, '');
                eMInput.value = rm ? parseInt(rm, 10).toLocaleString('id-ID') : '';
                eAInput.value = ra ? parseInt(ra, 10).toLocaleString('id-ID') : '';
                setEditJenis(jenis || 'R');
                setKat(raw(eMInput), editKat);
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
            e.preventDefault();
            const modal = $('eModalinti'), aset = $('eTotalaset');
            let ok = true;
            [modal, aset].forEach(f => f.classList.remove('is-invalid'));
            if (raw(modal) <= 0) { modal.classList.add('is-invalid'); ok = false; }
            if (raw(aset) <= 0) { aset.classList.add('is-invalid'); ok = false; }
            if (!ok) return;
            if (editLock) return; editLock = true;
            modal.value = raw(modal); aset.value = raw(aset);
            const btn = $('editSubmit');
            btn.querySelector('.pt-spinner').hidden = false;
            btn.querySelector('.pt-btn-txt').hidden = true;
            this.submit();
        });

        document.querySelectorAll('.pt-periodeform').forEach(f => {
            f.addEventListener('submit', function () {
                this.querySelector('button').style.opacity = '.6';
            });
        });

        setTimeout(() => {
            document.querySelectorAll('.pt-alert').forEach(el => {
                try { new bootstrap.Alert(el).close(); } catch (_) { }
            });
        }, 5000);

    })();
</script>