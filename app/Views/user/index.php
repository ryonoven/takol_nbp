<div class="container-fluid">

    <!-- Page Heading -->
    <div class="page-header-profile">
        <h1 class="page-title"><?= $judul; ?></h1>
        <p class="page-subtitle">Kelola informasi profil Anda</p>
    </div>

    <?= view('Myth\Auth\Views\_message_block') ?>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-8 col-xl-6">
            <!-- Profile Card -->
            <div class="profile-card">
                <!-- Profile Header -->
                <div class="profile-header">
                    <div class="profile-image-wrapper">
                        <img src="<?= base_url('/asset/img/' . user()->user_image); ?>" class="profile-image"
                            alt="<?= user()->username; ?>">
                        <div class="profile-status"></div>
                    </div>
                </div>

                <!-- Profile Info -->
                <div class="profile-info">
                    <h2 class="profile-name"><?= user()->fullname; ?></h2>
                    <p class="profile-username">@<?= user()->username; ?></p>

                    <!-- Info Cards -->
                    <div class="info-section">
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Email</div>
                                <div class="info-value"><?= user()->email; ?></div>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-user-tag"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Username</div>
                                <div class="info-value"><?= user()->username; ?></div>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Role</div>
                                <div class="info-value">
                                    <?php if (in_groups('admin')): ?>
                                        <span class="badge badge-admin">Administrator</span>
                                    <?php else: ?>
                                        <span class="badge badge-user">User</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Button -->
                    <button class="btn-edit" type="button" data-bs-toggle="collapse" data-bs-target="#emailForm"
                        aria-expanded="false" aria-controls="emailForm" id="editBtn">
                        <i class="fas fa-edit me-2"></i>
                        <span class="btn-text">Edit Profile</span>&nbsp;
                        <i class="fas fa-chevron-down ms-auto toggle-icon"></i>
                    </button>

                    <!-- Collapsible Form Section -->
                    <div class="collapse" id="emailForm">
                        <div class="edit-form-wrapper">
                            <h5 class="form-title">
                                <i class="fas fa-user-edit me-2"></i>
                                &nbsp;Edit Informasi
                            </h5>
                            <form action="<?= base_url('user/updateEmail/' . user()->id) ?>" method="POST">
                                <?= csrf_field(); ?>
                                <div class="form-group-custom">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope me-2"></i>&nbsp;Email
                                    </label>
                                    <input type="email" id="email" name="email" class="form-control-custom"
                                        value="<?= user()->email; ?>" required placeholder="Masukkan email">
                                </div>

                                <div class="form-actions">
                                    <button type="button" class="btn-cancel" data-bs-toggle="collapse"
                                        data-bs-target="#emailForm">
                                        <i class="fas fa-times me-2"></i>&nbsp;Batal
                                    </button>
                                    <button type="submit" class="btn-save">
                                        <i class="fas fa-check me-2"></i>&nbsp;Simpan Perubahan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>            
        </div>
    </div>

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Toggle icon rotation
    document.getElementById('editBtn')?.addEventListener('click', function () {
        const icon = this.querySelector('.toggle-icon');
        icon.classList.toggle('rotated');
    });
</script>

<style>
    /* ========== GENERAL STYLES ========== */
    .container-fluid {
        background-color: #f5f7fa;
        padding: 20px;
        min-height: 100vh;
    }

    /* ========== PAGE HEADER ========== */
    .page-header-profile {
        text-align: center;
        margin-bottom: 30px;
        padding: 20px 0;
    }

    .page-title {
        color: #1f2937;
        font-weight: 700;
        font-size: 2rem;
        margin-bottom: 8px;
    }

    .page-subtitle {
        color: #6b7280;
        font-size: 1rem;
        margin: 0;
    }

    /* ========== PROFILE CARD ========== */
    .profile-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        margin-bottom: 20px;
        transition: all 0.3s ease;
    }

    .profile-card:hover {
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    }

    /* ========== PROFILE HEADER ========== */
    .profile-header {
        background: linear-gradient(135deg, #1f2aa8 0%, #141863 100%);
        padding: 40px 20px 80px;
        position: relative;
        text-align: center;
    }

    .profile-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        opacity: 0.3;
    }

    .profile-image-wrapper {
        position: relative;
        display: inline-block;
        margin-bottom: -60px;
        z-index: 10;
    }

    .profile-image {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        border: 5px solid white;
        object-fit: cover;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        transition: transform 0.3s ease;
    }

    .profile-image:hover {
        transform: scale(1.05);
    }

    .profile-status {
        position: absolute;
        bottom: 10px;
        right: 10px;
        width: 20px;
        height: 20px;
        background: #10b981;
        border: 3px solid white;
        border-radius: 50%;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }

    /* ========== PROFILE INFO ========== */
    .profile-info {
        padding: 80px 30px 30px;
        text-align: center;
    }

    .profile-name {
        font-size: 1.75rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 8px;
    }

    .profile-username {
        color: #6b7280;
        font-size: 1rem;
        margin-bottom: 30px;
    }

    /* ========== INFO SECTION ========== */
    .info-section {
        text-align: left;
        margin-bottom: 25px;
    }

    .info-item {
        display: flex;
        align-items: center;
        padding: 15px;
        background: #f9fafb;
        border-radius: 12px;
        margin-bottom: 12px;
        transition: all 0.3s ease;
    }

    .info-item:hover {
        background: #f3f4f6;
        transform: translateX(5px);
    }

    .info-icon {
        width: 45px;
        height: 45px;
        background: linear-gradient(135deg, #1f2aa8 0%, #141863 100%);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.1rem;
        flex-shrink: 0;
        margin-right: 15px;
    }

    .info-content {
        flex: 1;
    }

    .info-label {
        font-size: 0.75rem;
        color: #6b7280;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }

    .info-value {
        font-size: 0.95rem;
        color: #1f2937;
        font-weight: 600;
    }

    /* ========== BADGES ========== */
    .badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-block;
    }

    .badge-admin {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
    }

    .badge-user {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
    }

    /* ========== EDIT BUTTON ========== */
    .btn-edit {
        width: 100%;
        padding: 15px 20px;
        background: linear-gradient(135deg, #1f2aa8 0%, #141863 100%);
        color: white;
        border: none;
        border-radius: 12px;
        font-weight: 600;
        font-size: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-bottom: 15px;
    }

    .btn-edit:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(31, 42, 168, 0.3);
    }

    .toggle-icon {
        transition: transform 0.3s ease;
        font-size: 0.875rem;
    }

    .toggle-icon.rotated {
        transform: rotate(180deg);
    }

    /* ========== EDIT FORM ========== */
    .edit-form-wrapper {
        background: #f9fafb;
        padding: 25px;
        border-radius: 12px;
        margin-top: 15px;
        border: 2px solid #e5e7eb;
    }

    .form-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
    }

    .form-group-custom {
        margin-bottom: 20px;
    }

    .form-label {
        display: flex;
        align-items: center;
        font-size: 0.875rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }

    .form-control-custom {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        background: white;
    }

    .form-control-custom:focus {
        outline: none;
        border-color: #1f2aa8;
        box-shadow: 0 0 0 3px rgba(31, 42, 168, 0.1);
    }

    .form-actions {
        display: flex;
        gap: 12px;
        margin-top: 25px;
    }

    .btn-cancel {
        flex: 1;
        padding: 12px;
        background: #f3f4f6;
        color: #374151;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-cancel:hover {
        background: #e5e7eb;
    }

    .btn-save {
        flex: 1;
        padding: 12px;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(16, 185, 129, 0.3);
    }

    /* ========== ACTION CARDS ========== */
    .action-cards {
        display: grid;
        gap: 15px;
        margin-top: 20px;
    }

    .action-card {
        display: flex;
        align-items: center;
        padding: 20px;
        background: white;
        border-radius: 12px;
        text-decoration: none;
        transition: all 0.3s ease;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
    }

    .action-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
    }

    .action-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.25rem;
        margin-right: 15px;
        flex-shrink: 0;
    }

    .action-icon.bg-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    }

    .action-icon.bg-success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }

    .action-content {
        flex: 1;
    }

    .action-content h6 {
        font-size: 1rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 4px;
    }

    .action-content p {
        font-size: 0.875rem;
        color: #6b7280;
        margin: 0;
    }

    .action-card i:last-child {
        color: #9ca3af;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .action-card:hover i:last-child {
        color: #1f2aa8;
        transform: translateX(5px);
    }

    /* ========== RESPONSIVE ========== */
    @media (max-width: 768px) {
        .container-fluid {
            padding: 15px;
        }

        .page-title {
            font-size: 1.5rem;
        }

        .page-subtitle {
            font-size: 0.875rem;
        }

        .profile-header {
            padding: 30px 15px 70px;
        }

        .profile-image {
            width: 100px;
            height: 100px;
        }

        .profile-info {
            padding: 70px 20px 20px;
        }

        .profile-name {
            font-size: 1.5rem;
        }

        .profile-username {
            font-size: 0.875rem;
        }

        .info-item {
            padding: 12px;
        }

        .info-icon {
            width: 40px;
            height: 40px;
            font-size: 1rem;
            margin-right: 12px;
        }

        .info-label {
            font-size: 0.7rem;
        }

        .info-value {
            font-size: 0.875rem;
        }

        .btn-edit {
            padding: 12px 16px;
            font-size: 0.95rem;
        }

        .edit-form-wrapper {
            padding: 20px;
        }

        .form-title {
            font-size: 1rem;
        }

        .form-control-custom {
            padding: 10px 14px;
            font-size: 0.875rem;
        }

        .form-actions {
            flex-direction: column;
        }

        .btn-cancel,
        .btn-save {
            width: 100%;
        }

        .action-card {
            padding: 15px;
        }

        .action-icon {
            width: 45px;
            height: 45px;
            font-size: 1.1rem;
        }

        .action-content h6 {
            font-size: 0.95rem;
        }

        .action-content p {
            font-size: 0.8rem;
        }
    }

    @media (max-width: 576px) {
        .page-header-profile {
            margin-bottom: 20px;
            padding: 10px 0;
        }

        .page-title {
            font-size: 1.25rem;
        }

        .profile-header {
            padding: 25px 10px 60px;
        }

        .profile-image {
            width: 90px;
            height: 90px;
        }

        .profile-info {
            padding: 60px 15px 15px;
        }

        .profile-name {
            font-size: 1.25rem;
        }

        .info-item {
            padding: 10px;
        }

        .edit-form-wrapper {
            padding: 15px;
        }
    }
</style>