<div class="container-fluid px-3 px-md-4 py-4">
    <!-- Page Header -->
    <div class="page-header-periode mb-4">
        <div class="row align-items-center">
            <div class="col-12">
                <h1 class="page-title-periode">
                    <i class="fas fa-calendar-alt me-2"></i>&nbsp;
                    Periode Pelaporan
                </h1>
                <p class="page-subtitle-periode">Kelola periode laporan Self Assessment Tata Kelola</p>
            </div>
        </div>
    </div>

    <!-- Notifikasi -->
    <?php if (session()->getFlashdata('message')): ?>
        <div class="alert alert-success alert-dismissible fade show alert-modern" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?= session()->getFlashdata('message') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show alert-modern" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($validation) && $validation->getErrors()): ?>
        <div class="alert alert-danger alert-dismissible fade show alert-modern" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <ul class="mb-0">
                <?php foreach ($validation->getErrors() as $error): ?>
                    <li><?= esc($error) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Skeleton Loading (Hidden by default) -->
    <div id="skeletonLoader" class="row" style="display: none;">
        <div class="col-lg-5 col-xl-4 mb-4">
            <div class="card card-periode skeleton-card">
                <div class="skeleton skeleton-header"></div>
                <div class="card-body-periode">
                    <div class="skeleton skeleton-label mb-2"></div>
                    <div class="skeleton skeleton-input mb-3"></div>
                    <div class="skeleton skeleton-label mb-2"></div>
                    <div class="skeleton skeleton-input mb-3"></div>
                    <div class="skeleton skeleton-label mb-2"></div>
                    <div class="skeleton skeleton-input mb-3"></div>
                    <div class="skeleton skeleton-label mb-2"></div>
                    <div class="skeleton skeleton-input mb-3"></div>
                    <div class="skeleton skeleton-label mb-2"></div>
                    <div class="skeleton skeleton-input mb-3"></div>
                    <div class="skeleton skeleton-button"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-7 col-xl-8 mb-4">
            <div class="card card-periode skeleton-card">
                <div class="skeleton skeleton-header"></div>
                <div class="card-body-periode">
                    <div class="skeleton-item mb-3"></div>
                    <div class="skeleton-item mb-3"></div>
                    <div class="skeleton-item"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div id="mainContent" class="row">
        <!-- Form Buat Periode Baru -->
        <div class="col-12 col-lg-5 col-xl-4 mb-4">
            <div class="card card-periode shadow-sm h-100">
                <div class="card-header-periode bg-gradient-primary">
                    <h5 class="card-title-periode mb-0">
                        <i class="fas fa-plus-circle me-2"></i>
                        <span class="d-none d-sm-inline">&nbsp;Buat Periode Baru</span>
                        <span class="d-inline d-sm-none">Buat Baru</span>
                    </h5>
                </div>
                <div class="card-body-periode">
                    <form action="<?= base_url('periode/handlePeriode') ?>" method="post" id="createForm" novalidate>
                        <input type="hidden" name="action" value="create">

                        <div class="form-group-periode mb-3">
                            <label for="semester" class="form-label-periode">
                                <i class="fas fa-list-ol me-2"></i>&nbsp;Semester
                            </label>
                            <select id="semester" name="semester" class="form-select-periode" required>
                                <option value="">Pilih Semester</option>
                                <option value="1">Semester 1</option>
                                <option value="2">Semester 2</option>
                            </select>
                            <div class="invalid-feedback">Semester harus dipilih</div>
                        </div>

                        <div class="form-group-periode mb-3">
                            <label for="jenispelaporan" class="form-label-periode">
                                <i class="fas fa-file-alt me-2"></i>&nbsp;Jenis Pelaporan
                            </label>
                            <select id="jenispelaporan" name="jenispelaporan" class="form-select-periode" required>
                                <option value="">Pilih Jenis Pelaporan</option>
                                <option value="R">Laporan Rutin</option>
                                <option value="K">Laporan Koreksi</option>
                            </select>
                            <div class="invalid-feedback">Jenis pelaporan harus dipilih</div>
                        </div>

                        <div class="form-group-periode mb-3">
                            <label for="tahun" class="form-label-periode">
                                <i class="fas fa-calendar me-2"></i>&nbsp;Tahun
                            </label>
                            <select id="tahun" name="tahun" class="form-select-periode" required>
                                <option value="">Pilih Tahun</option>
                                <?php for ($year = 2024; $year <= 2030; $year++): ?>
                                    <option value="<?= $year ?>" <?= (date('Y') == $year) ? 'selected' : '' ?>><?= $year ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                            <div class="invalid-feedback">Tahun harus dipilih</div>
                        </div>

                        <div class="form-group-periode mb-3">
                            <label for="modalinti" class="form-label-periode">
                                <i class="fas fa-coins me-2"></i>&nbsp;Modal Inti
                            </label>
                            <div class="input-group-custom">
                                <span class="input-prefix">Rp</span>
                                <input type="text" id="modalinti" name="modalinti"
                                    class="form-control-periode with-prefix" placeholder="0" required>
                            </div>
                            <small class="form-text-helper">
                                <i class="fas fa-info-circle me-1"></i>
                                Kategori A: ≥ 50 Miliar | Kategori B: < 50 Miliar </small>
                                    <div class="invalid-feedback">Modal inti harus diisi</div>
                        </div>

                        <div class="form-group-periode mb-4">
                            <label for="totalaset" class="form-label-periode">
                                <i class="fas fa-chart-bar me-2"></i>&nbsp;Total Aset
                            </label>
                            <div class="input-group-custom">
                                <span class="input-prefix">Rp</span>
                                <input type="text" id="totalaset" name="totalaset"
                                    class="form-control-periode with-prefix" placeholder="0" required>
                            </div>
                            <div class="invalid-feedback">Total aset harus diisi</div>
                        </div>

                        <button type="submit" class="btn btn-create-periode w-100">
                            <span class="btn-spinner" style="display: none;">
                                <span class="spinner-border spinner-border-sm me-2" role="status"
                                    aria-hidden="true"></span>
                                Memproses...
                            </span>
                            <span class="btn-text">
                                <i class="fas fa-plus-circle me-2"></i>
                                <span class="d-none d-sm-inline">Buat Periode Baru</span>
                                <span class="d-inline d-sm-none">Buat Baru</span>
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Daftar Periode yang Sudah Ada -->
        <div class="col-12 col-lg-7 col-xl-8 mb-4">
            <div class="card card-periode shadow-sm h-100">
                <div class="card-header-periode bg-gradient-primary">
                    <h5 class="card-title-periode mb-0">
                        <i class="fas fa-list me-2"></i>
                        <span class="d-none d-sm-inline">&nbsp;Daftar Periode Tersedia</span>
                        <span class="d-inline d-sm-none">Daftar Periode</span>
                    </h5>
                </div>
                <div class="card-body-periode">
                    <?php if (empty($periodes)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-folder-open"></i>
                            </div>
                            <h6 class="empty-title">Belum Ada Periode</h6>
                            <p class="empty-text">Silakan buat periode baru untuk memulai pelaporan</p>
                        </div>
                    <?php else: ?>
                        <div class="periode-list">
                            <?php foreach ($periodes as $index => $periode): ?>
                                <form action="<?= base_url('periode/handlePeriode') ?>" method="post" class="periode-form">
                                    <input type="hidden" name="action" value="select">
                                    <input type="hidden" name="periode_id" value="<?= $periode['id'] ?>">

                                    <button type="submit"
                                        class="periode-item <?= ($current_periode == $periode['id']) ? 'active' : '' ?>">
                                        <div class="periode-icon">
                                            <i class="fas fa-calendar-check"></i>
                                        </div>
                                        <div class="periode-content">
                                            <h6 class="periode-title">
                                                <span class="d-none d-md-inline">Semester <?= esc($periode['semester']) ?> -
                                                    Tahun <?= esc($periode['tahun']) ?></span>
                                                <span class="d-inline d-md-none">Semester <?= esc($periode['semester']) ?> -
                                                    Tahun
                                                    <?= esc($periode['tahun']) ?></span>
                                            </h6>
                                            <div class="periode-type mb-2">
                                                <span
                                                    class="badge-jenis <?= $periode['jenispelaporan'] == 'R' ? 'badge-rutin' : 'badge-koreksi' ?>">
                                                    <i
                                                        class="fas <?= $periode['jenispelaporan'] == 'R' ? 'fa-file-alt' : 'fa-file-edit' ?> me-1"></i>
                                                    <?= $periode['jenispelaporan'] == 'R' ? '&nbsp;Laporan Rutin' : '&nbsp;Laporan Koreksi' ?>
                                                </span>
                                            </div>
                                            <div class="periode-details">
                                                <span class="detail-item">
                                                    <i class="fas fa-coins"></i>
                                                    <span class="d-none d-sm-inline">Modal:</span>
                                                    <span class="detail-value">Rp
                                                        <?= number_format($periode['modalinti'], 0, ',', '.') ?></span>
                                                </span>
                                                <span class="detail-item">
                                                    <i class="fas fa-chart-bar"></i>
                                                    <span class="d-none d-sm-inline">Aset:</span>
                                                    <span class="detail-value">Rp
                                                        <?= number_format($periode['totalaset'], 0, ',', '.') ?></span>
                                                </span>
                                            </div>
                                            <small class="periode-date">
                                                <i class="fas fa-clock me-1"></i>
                                                <span
                                                    class="d-none d-sm-inline"><?= date('d M Y, H:i', strtotime($periode['created_at'])) ?></span>
                                                <span
                                                    class="d-inline d-sm-none"><?= date('d/m/Y', strtotime($periode['created_at'])) ?></span>
                                            </small>
                                        </div>
                                        <?php if ($current_periode == $periode['id']): ?>
                                            <div class="periode-badge">
                                                <i class="fas fa-check-circle d-none d-sm-inline"></i>
                                                <span>Aktif</span>
                                            </div>
                                        <?php else: ?>
                                            <div class="periode-arrow">
                                                <i class="fas fa-chevron-right"></i>
                                            </div>
                                        <?php endif; ?>
                                    </button>
                                </form>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* ========== SKELETON LOADING ========== */
    .skeleton-card {
        overflow: hidden;
    }

    .skeleton {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: skeleton-loading 1.5s infinite;
        border-radius: 8px;
    }

    @keyframes skeleton-loading {
        0% {
            background-position: 200% 0;
        }

        100% {
            background-position: -200% 0;
        }
    }

    .skeleton-header {
        height: 60px;
        margin-bottom: 1.5rem;
    }

    .skeleton-label {
        height: 20px;
        width: 30%;
    }

    .skeleton-input {
        height: 45px;
        width: 100%;
    }

    .skeleton-button {
        height: 48px;
        width: 100%;
    }

    .skeleton-item {
        height: 100px;
        width: 100%;
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: skeleton-loading 1.5s infinite;
        border-radius: 12px;
    }

    /* ========== PAGE HEADER ========== */
    .page-header-periode {
        margin-bottom: 1.5rem;
    }

    .page-title-periode {
        font-size: clamp(1.25rem, 4vw, 1.75rem);
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
    }

    .page-subtitle-periode {
        color: #6b7280;
        font-size: clamp(0.875rem, 2vw, 0.95rem);
        margin: 0;
    }

    /* ========== ALERTS ========== */
    .alert-modern {
        border: none;
        border-radius: 12px;
        padding: 1rem 1.25rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        animation: slideInDown 0.3s ease;
    }

    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .alert-modern i {
        font-size: 1.1rem;
    }

    /* ========== CARD STYLING ========== */
    .card-periode {
        border: none;
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.3s ease;
        will-change: transform, box-shadow;
    }

    .card-periode:hover {
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12) !important;
        transform: translateY(-2px);
    }

    .card-header-periode {
        padding: clamp(1rem, 3vw, 1.25rem) clamp(1.25rem, 4vw, 1.5rem);
        border-bottom: none;
    }

    .bg-gradient-primary {
        background: linear-gradient(135deg, #1f2aa8 0%, #141863 100%);
        color: white;
    }

    .bg-gradient-info {
        background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
        color: white;
    }

    .card-title-periode {
        font-size: clamp(0.95rem, 2.5vw, 1.1rem);
        font-weight: 600;
        display: flex;
        align-items: center;
    }

    .card-body-periode {
        padding: clamp(1rem, 3vw, 1.5rem);
    }

    /* ========== FORM STYLING ========== */
    .form-group-periode {
        margin-bottom: 1rem;
    }

    .form-label-periode {
        font-size: clamp(0.85rem, 2vw, 0.9rem);
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
    }

    .input-group-custom {
        position: relative;
        display: flex;
        width: 100%;
    }

    .input-prefix {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        font-size: 0.9rem;
        font-weight: 600;
        color: #6b7280;
        z-index: 1;
        pointer-events: none;
    }

    .form-control-periode.with-prefix {
        padding-left: 3rem;
    }

    .form-select-periode,
    .form-control-periode {
        width: 100%;
        padding: clamp(0.625rem, 2vw, 0.75rem) 1rem;
        font-size: clamp(0.875rem, 2vw, 0.95rem);
        color: #1f2937;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        background-color: #fff;
        transition: all 0.3s ease;
    }

    .form-select-periode:focus,
    .form-control-periode:focus {
        outline: none;
        border-color: #1f2aa8;
        box-shadow: 0 0 0 3px rgba(31, 42, 168, 0.1);
    }

    .form-select-periode.is-invalid,
    .form-control-periode.is-invalid {
        border-color: #dc3545;
    }

    .invalid-feedback {
        display: none;
        font-size: 0.8rem;
        color: #dc3545;
        margin-top: 0.25rem;
    }

    .is-invalid~.invalid-feedback {
        display: block;
    }

    .form-text-helper {
        display: block;
        margin-top: 0.25rem;
        font-size: clamp(0.75rem, 1.5vw, 0.8rem);
        color: #6b7280;
    }

    /* ========== BADGE JENIS PELAPORAN ========== */
    .periode-type {
        margin-bottom: 0.5rem;
    }

    .badge-jenis {
        display: inline-flex;
        align-items: center;
        padding: 0.375rem 0.75rem;
        border-radius: 6px;
        font-size: clamp(0.75rem, 1.8vw, 0.8rem);
        font-weight: 600;
    }

    .badge-rutin {
        background-color: #dbeafe;
        color: #1e40af;
    }

    .badge-koreksi {
        background-color: #fef3c7;
        color: #92400e;
    }

    .periode-item.active .badge-rutin {
        background-color: rgba(219, 234, 254, 0.3);
        color: white;
    }

    .periode-item.active .badge-koreksi {
        background-color: rgba(254, 243, 199, 0.3);
        color: white;
    }

    /* ========== BUTTON ========== */
    .btn-create-periode {
        padding: clamp(0.75rem, 2vw, 0.875rem) 1.5rem;
        background: linear-gradient(135deg, #1fa85a 0%, #146337 100%);
        color: white;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        font-size: clamp(0.9rem, 2vw, 1rem);
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }

    .btn-create-periode::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }

    .btn-create-periode:hover::before {
        width: 300px;
        height: 300px;
    }

    .btn-create-periode:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(16, 185, 129, 0.3);
    }

    .btn-create-periode:active {
        transform: translateY(0);
    }

    .btn-spinner,
    .btn-text {
        position: relative;
        z-index: 1;
    }

    /* ========== EMPTY STATE ========== */
    .empty-state {
        text-align: center;
        padding: clamp(2rem, 5vw, 3rem) 1rem;
    }

    .empty-icon {
        font-size: clamp(3rem, 8vw, 4rem);
        color: #d1d5db;
        margin-bottom: 1rem;
    }

    .empty-title {
        font-size: clamp(1rem, 2.5vw, 1.1rem);
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
    }

    .empty-text {
        color: #6b7280;
        font-size: clamp(0.85rem, 2vw, 0.9rem);
        margin: 0;
    }

    /* ========== PERIODE LIST ========== */
    .periode-list {
        display: flex;
        flex-direction: column;
        gap: clamp(0.625rem, 1.5vw, 0.75rem);
        max-height: 500px;
        overflow-y: auto;
        padding-right: 0.5rem;
    }

    .periode-list::-webkit-scrollbar {
        width: 6px;
    }

    .periode-list::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, #1f2aa8 0%, #141863 100%);
        border-radius: 10px;
    }

    .periode-list::-webkit-scrollbar-track {
        background: #f3f4f6;
        border-radius: 10px;
    }

    .periode-form {
        margin: 0;
    }

    .periode-item {
        width: 100%;
        display: flex;
        align-items: center;
        padding: clamp(0.875rem, 2vw, 1rem);
        background: #f9fafb;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        text-align: left;
        transition: all 0.3s ease;
        cursor: pointer;
        will-change: transform;
    }

    .periode-item:hover {
        background: white;
        border-color: #1f2aa8;
        transform: translateX(5px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    .periode-item.active {
        background: linear-gradient(135deg, #1fa85a 0%, #146337 100%);
        color: white;
    }

    .periode-icon {
        width: clamp(40px, 8vw, 45px);
        height: clamp(40px, 8vw, 45px);
        background: white;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: clamp(0.75rem, 2vw, 1rem);
        flex-shrink: 0;
        font-size: clamp(1.1rem, 2.5vw, 1.25rem);
        color: #1f2aa8;
    }

    .periode-item.active .periode-icon {
        background: rgba(255, 255, 255, 0.2);
        color: white;
    }

    .periode-content {
        flex: 1;
        min-width: 0;
    }

    .periode-title {
        font-size: clamp(0.9rem, 2vw, 1rem);
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 0.5rem;
        word-break: break-word;
    }

    .periode-item.active .periode-title {
        color: white;
    }

    .periode-details {
        display: flex;
        flex-wrap: wrap;
        gap: clamp(0.5rem, 1.5vw, 0.75rem);
        margin-bottom: 0.5rem;
    }

    .detail-item {
        font-size: clamp(0.75rem, 1.8vw, 0.8rem);
        color: #6b7280;
        display: flex;
        align-items: center;
        gap: 0.375rem;
    }

    .detail-value {
        font-weight: 600;
    }

    .periode-item.active .detail-item {
        color: rgba(255, 255, 255, 0.9);
    }

    .periode-date {
        font-size: clamp(0.7rem, 1.5vw, 0.75rem);
        color: #9ca3af;
        display: flex;
        align-items: center;
        gap: 0.375rem;
    }

    .periode-item.active .periode-date {
        color: rgba(255, 255, 255, 0.8);
    }

    .periode-badge {
        background: rgba(255, 255, 255, 0.2);
        padding: clamp(0.375rem, 1.5vw, 0.5rem) clamp(0.625rem, 2vw, 0.75rem);
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 0.375rem;
        font-size: clamp(0.8rem, 1.8vw, 0.85rem);
        font-weight: 600;
        color: white;
        flex-shrink: 0;
        white-space: nowrap;
    }

    .periode-arrow {
        color: #9ca3af;
        font-size: clamp(0.9rem, 2vw, 1rem);
        flex-shrink: 0;
        margin-left: 0.5rem;
        transition: all 0.3s ease;
    }

    .periode-item:hover .periode-arrow {
        color: #1f2aa8;
        transform: translateX(3px);
    }

    /* ========== RESPONSIVE ========== */
    @media (max-width: 1199px) {
        .periode-list {
            max-height: 450px;
        }
    }

    @media (max-width: 991px) {
        .periode-details {
            flex-direction: column;
            gap: 0.375rem;
        }

        .periode-list {
            max-height: 400px;
        }
    }

    @media (max-width: 767px) {
        .container-fluid {
            padding-left: 0.75rem !important;
            padding-right: 0.75rem !important;
        }

        .periode-item {
            flex-wrap: wrap;
        }

        .periode-badge {
            margin-top: 0.5rem;
            width: 100%;
            justify-content: center;
        }

        .periode-arrow {
            position: absolute;
            right: 1rem;
            top: 1rem;
        }
    }

    @media (max-width: 575px) {
        .page-header-periode {
            margin-bottom: 1rem;
        }

        .card-periode {
            border-radius: 12px;
        }

        .periode-icon {
            margin-right: 0.75rem;
        }

        .detail-item {
            width: 100%;
        }
    }

    /* ========== PERFORMANCE OPTIMIZATIONS ========== */
    * {
        -webkit-tap-highlight-color: transparent;
    }

    .periode-item,
    .card-periode,
    .btn-create-periode {
        -webkit-backface-visibility: hidden;
        backface-visibility: hidden;
        -webkit-transform: translateZ(0);
        transform: translateZ(0);
    }
</style>

<script>
    // Show skeleton loading
    function showSkeleton() {
        document.getElementById('skeletonLoader').style.display = 'flex';
        document.getElementById('mainContent').style.display = 'none';
    }

    // Hide skeleton loading
    function hideSkeleton() {
        document.getElementById('skeletonLoader').style.display = 'none';
        document.getElementById('mainContent').style.display = 'flex';
    }

    // Simulate loading (remove this in production)
    document.addEventListener('DOMContentLoaded', function () {
        // Hide skeleton after page load
        hideSkeleton();
    });

    // Format currency input
    function formatCurrency(input) {
        let value = input.value.replace(/\D/g, '');
        if (value) {
            value = parseInt(value).toLocaleString('id-ID');
        }
        input.value = value;
    }

    // Real-time currency formatting
    const modalintiInput = document.getElementById('modalinti');
    const totalasetInput = document.getElementById('totalaset');

    if (modalintiInput) {
        modalintiInput.addEventListener('input', function (e) {
            formatCurrency(e.target);
        });

        modalintiInput.addEventListener('focus', function (e) {
            if (e.target.value === '0') {
                e.target.value = '';
            }
        });
    }

    if (totalasetInput) {
        totalasetInput.addEventListener('input', function (e) {
            formatCurrency(e.target);
        });

        totalasetInput.addEventListener('focus', function (e) {
            if (e.target.value === '0') {
                e.target.value = '';
            }
        });
    }

    // Form validation
    const createForm = document.getElementById('createForm');
    if (createForm) {
        createForm.addEventListener('submit', function (e) {
            const tahun = document.getElementById('tahun');
            const semester = document.getElementById('semester');
            const jenispelaporan = document.getElementById('jenispelaporan');
            const modalinti = document.getElementById('modalinti');
            const totalaset = document.getElementById('totalaset');

            let isValid = true;

            // Reset validation
            [tahun, semester, jenispelaporan, modalinti, totalaset].forEach(field => {
                field.classList.remove('is-invalid');
            });

            // Validate
            if (!tahun.value) {
                tahun.classList.add('is-invalid');
                isValid = false;
            }

            if (!semester.value) {
                semester.classList.add('is-invalid');
                isValid = false;
            }

            if (!jenispelaporan.value) {
                jenispelaporan.classList.add('is-invalid');
                isValid = false;
            }

            if (!modalinti.value || modalinti.value === '0') {
                modalinti.classList.add('is-invalid');
                isValid = false;
            }

            if (!totalaset.value || totalaset.value === '0') {
                totalaset.classList.add('is-invalid');
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
                return false;
            }

            // Show loading spinner
            const btnSpinner = this.querySelector('.btn-spinner');
            const btnText = this.querySelector('.btn-text');
            if (btnSpinner && btnText) {
                btnSpinner.style.display = 'inline-flex';
                btnText.style.display = 'none';
            }

            // Show skeleton
            showSkeleton();
        });
    }

    // Auto-dismiss alerts
    setTimeout(function () {
        const alerts = document.querySelectorAll('.alert-modern');
        alerts.forEach(function (alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Smooth scroll for long lists
    const periodeList = document.querySelector('.periode-list');
    if (periodeList && 'scrollBehavior' in document.documentElement.style) {
        periodeList.style.scrollBehavior = 'smooth';
    }

    // Prevent double submission
    let isSubmitting = false;
    document.querySelectorAll('.periode-form').forEach(form => {
        form.addEventListener('submit', function (e) {
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }
            isSubmitting = true;
            showSkeleton();
        });
    });
</script>