<div id="layoutSidenav_content">
    <main>
        <header class="page-header page-header-dark pb-10">
            <div class="container-xl px-4">
                <div class="page-header-content pt-4">
                    <div class="row align-items-center justify-content-between">
                        <div class="col-12">
                            <div class="text-center text-white">
                                <h1 class="display-4 fw-bold">Dashboard NBP Simpel</h1>
                                <p class="lead mb-0">Sistem Pelaporan Bank Perekonomian Rakyat</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="container-xl px-4 mt-n10">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card welcome-card shadow-lg">
                        <div class="card-body p-4 p-md-5">
                            <div class="row align-items-center">
                                <div class="col-lg-8">
                                    <div class="welcome-icon mb-3">
                                        <i class="fas fa-hand-holding-heart"></i>
                                    </div>
                                    <h2 class="text-primary fw-bold mb-3">Selamat Datang di NBP Simpel</h2>
                                    <p class="text-muted mb-4 lead">
                                        NBP Simpel dirancang untuk memudahkan Anda dalam pengisian laporan secara
                                        efisien dan akurat.
                                        Kami berharap aplikasi ini dapat memberikan pengalaman yang mudah dan cepat
                                        dalam menyelesaikan tugas Anda.
                                    </p>
                                    <div class="alert alert-info border-0 shadow-sm">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Penting:</strong> Silakan mengisi <a href="<?= base_url('infobpr') ?>"
                                            class="alert-link fw-bold text-decoration-underline">informasi BPR</a>
                                        terlebih dahulu sebelum memulai pelaporan.
                                    </div>
                                </div>
                                <div class="col-lg-4 d-none d-lg-block text-center">
                                    <div class="welcome-illustration">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-12">
                    <h3 class="fw-bold text-dark mb-0">
                        <i class="fas fa-folder-open me-2"></i>&nbsp;Jenis Pelaporan
                    </h3>
                    <p class="text-muted">Pilih jenis laporan yang hendak Anda isi</p>
                </div>
            </div>

            <div class="row">
                <div class="col-6 mb-4">
                    <div class="card report-card report-card-success h-100">
                        <div class="card-body">
                            <div class="report-icon-wrapper mb-3">
                                <div class="report-icon">
                                    <i class="fas fa-clipboard-check"></i>
                                </div>
                            </div>
                            <div class="report-category">Laporan Semester</div>
                            <h4 class="report-title">Laporan Semesteran</h4>
                            <p class="report-description">- Self Assessment Tata Kelola
                                <br>
                                - Pokok Pelaksanaan Tugas Anggota Direksi yang Membawahkan Fungsi Kepatuhan <br>
                                - Pelaksanaan dan Pokok Hasil Audit Intern<br>
                                - Self Assessment Tingkat Kesehatan Bank</p>
                        </div>
                        <div class="card-footer">
                            <a href="<?= base_url('Periodesemesteran') ?>" class="btn-report-action">
                                <span>Mulai Pelaporan</span>
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-6 mb-4">
                    <div class="card report-card report-card-danger h-100">
                        <div class="card-body">
                            <div class="report-icon-wrapper mb-3">
                                <div class="report-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                            </div>
                            <div class="report-category">Laporan Tahunan</div>
                            <h4 class="report-title">Laporan Tahunan</h4>
                            <p class="report-description">
                                - Laporan Transparansi<br>
                                - Laporan Keberlanjutan
                            </p>
                        </div>
                        <div class="card-footer">
                            <a href="<?= base_url('periodetahunan') ?>" class="btn-report-action">
                                <span>Mulai Pelaporan</span>
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>                
            </div>
        </div>
    </main>
</div>

<style>
    body {
        font-family: 'Inter', 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        background-color: #f5f7fa;
        color: #2d3748;
    }

    .page-header {
        background: linear-gradient(135deg, #1f2aa8 0%, #141863 100%);
        padding-top: 3rem;
        padding-bottom: 6rem;
        box-shadow: 0 4px 20px rgba(91, 71, 237, 0.15);
        position: relative;
        overflow: hidden;
    }

    .page-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        opacity: 0.5;
    }

    .page-header .display-4 {
        font-size: 2.7rem;
        font-weight: 700;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .page-header .lead {
        font-size: 1.1rem;
        opacity: 0.95;
    }

    .welcome-card {
        border: none;
        border-radius: 16px;
        background: white;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .welcome-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1) !important;
    }

    .welcome-icon {
        font-size: 3rem;
        color: #1f2aa8;
        animation: float 3s ease-in-out infinite;
    }

    .welcome-icon i {
        filter: drop-shadow(0 4px 8px rgba(91, 71, 237, 0.3));
    }

    @keyframes float {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-10px);
        }
    }

    .welcome-illustration {
        font-size: 8rem;
        color: #e0e7ff;
        animation: pulse 2s ease-in-out infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.05);
        }
    }

    .stats-card {
        border: none;
        border-radius: 12px;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .stats-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0) 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .stats-card:hover::before {
        opacity: 1;
    }

    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    }

    .stats-card-success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }

    .stats-card-warning {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
    }

    .stats-card-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
    }

    .stats-card-info {
        background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
        color: white;
    }

    .stats-label {
        font-size: 0.875rem;
        opacity: 0.9;
        margin-bottom: 0.5rem;
        font-weight: 500;
    }

    .stats-value {
        font-size: 2.5rem;
        font-weight: 700;
        line-height: 1;
        margin-bottom: 0.25rem;
    }

    .stats-subtitle {
        font-size: 0.75rem;
        opacity: 0.8;
    }

    .stats-icon {
        font-size: 3rem;
        opacity: 0.3;
    }

    .report-card {
        border: none;
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.3s ease;
        background: white;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    }

    .report-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
    }

    .report-card-success {
        border-top: 4px solid #10b981;
    }

    .report-card-danger {
        border-top: 4px solid #ef4444;
    }

    .report-card-primary {
        border-top: 4px solid #3b82f6;
    }

    .report-card-warning {
        border-top: 4px solid #f59e0b;
    }

    .report-icon-wrapper {
        display: inline-block;
    }

    .report-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        transition: all 0.3s ease;
    }

    .report-card-success .report-icon {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
    }

    .report-card-danger .report-icon {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
    }

    .report-card-primary .report-icon {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
    }

    .report-card-warning .report-icon {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
    }

    .report-card:hover .report-icon {
        transform: scale(1.1) rotate(5deg);
    }

    .report-category {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        color: #6b7280;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
    }

    .report-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 0.75rem;
        line-height: 1.3;
    }

    .report-description {
        font-size: 0.875rem;
        color: #6b7280;
        line-height: 1.5;
        margin-bottom: 1rem;
    }

    .report-stats .badge {
        font-size: 0.75rem;
        padding: 0.5rem 0.75rem;
        font-weight: 600;
    }

    .report-card .card-footer {
        background: #f9fafb;
        border: none;
        padding: 1rem 1.5rem;
    }

    .btn-report-action {
        display: flex;
        align-items: center;
        justify-content: space-between;
        text-decoration: none;
        color: #374151;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-report-action:hover {
        color: #1f2aa8;
        padding-left: 5px;
    }

    .btn-report-action i {
        transition: all 0.3s ease;
    }

    .btn-report-action:hover i {
        transform: translateX(5px);
    }

    .activity-timeline {
        position: relative;
    }

    .activity-item {
        display: flex;
        align-items: flex-start;
        padding: 1rem 0;
        border-bottom: 1px solid #f3f4f6;
        position: relative;
    }

    .activity-item:last-child {
        border-bottom: none;
    }

    .activity-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        margin-right: 1rem;
        flex-shrink: 0;
    }

    .activity-content h6 {
        font-size: 0.95rem;
        font-weight: 600;
        color: #1f2937;
    }

    .quick-links {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .quick-link-item {
        display: flex;
        align-items: center;
        padding: 1rem;
        background: #f9fafb;
        border-radius: 10px;
        text-decoration: none;
        transition: all 0.3s ease;
        border: 1px solid transparent;
    }

    .quick-link-item:hover {
        background: white;
        border-color: #1f2aa8;
        transform: translateX(5px);
    }

    .quick-link-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        background: linear-gradient(135deg, #1f2aa8 0%, #3730a3 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
        flex-shrink: 0;
    }

    .quick-link-text {
        flex: 1;
    }

    .quick-link-text h6 {
        font-size: 0.95rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 0.25rem;
    }

    .quick-link-item i:last-child {
        color: #9ca3af;
        font-size: 0.875rem;
        transition: all 0.3s ease;
    }

    .quick-link-item:hover i:last-child {
        color: #1f2aa8;
        transform: translateX(3px);
    }

    @media (max-width: 991px) {
        .page-header {
            padding-top: 2rem;
            padding-bottom: 4rem;
        }

        .page-header .display-4 {
            font-size: 1.75rem;
        }

        .page-header .lead {
            font-size: 0.95rem;
        }

        .welcome-card .card-body {
            padding: 2rem !important;
        }

        .welcome-icon {
            font-size: 2rem;
        }

        .stats-value {
            font-size: 2rem;
        }

        .stats-icon {
            font-size: 2rem;
        }

        .report-icon {
            width: 50px;
            height: 50px;
            font-size: 1.5rem;
        }

        .report-title {
            font-size: 1.1rem;
        }
    }

    @media (max-width: 576px) {
        .mt-n10 {
            margin-top: -4rem !important;
        }

        .welcome-card .card-body {
            padding: 1.5rem !important;
        }

        .stats-card .card-body {
            padding: 1rem !important;
        }

        .stats-value {
            font-size: 1.75rem;
        }

        .report-card .card-body {
            padding: 1.25rem !important;
        }

        .activity-icon {
            width: 35px;
            height: 35px;
        }

        .quick-link-icon {
            width: 35px;
            height: 35px;
        }
    }

    .mt-n10 {
        margin-top: -5rem !important;
    }

    .shadow-sm {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06) !important;
    }

    .shadow-lg {
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1) !important;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>
<script>
    $(document).ready(function () {
        $('.card').css('opacity', '0').each(function (i) {
            $(this).delay(i * 100).animate({ opacity: 1 }, 600);
        });
    });
</script>