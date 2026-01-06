<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->

    <h1 class="h1 mb-4 text-black-800"></h1>

    <?= view('Myth\Auth\Views\_message_block') ?>

</div>
<!-- /.container-fluid -->
<div id="layoutSidenav_content">
    <main>
        <header class="page-header page-header-dark bg-gradient-primary-to-secondary pb-10">
            <div class="container-xl px-4">
                <div class="page-header-content pt-4">
                    <div class="row align-items-center justify-content-between">
                        <div class="col-12 col-xl-auto mt-4">
                        </div>
                    </div>
                </div>
            </div>
        </header>
        <!-- Main page content-->
        <div class="container-xl px-4 mt-n5">
            <div class="row">
                <div class="col-xxl-4 col-xl-12 mb-4">
                    <div class="card h-100">
                        <div class="card-body h-100 p-5">
                            <h1 class="text-primary text-center">Selamat Datang di NBP Simpel</h1>
                            <p class="text-gray-700 text-center">NBP Simpel dirancang untuk memudahkan Anda dalam
                                pengisian laporan secara efisien dan akurat. Kami berharap aplikasi ini dapat memberikan
                                pengalaman yang mudah dan cepat dalam menyelesaikan tugas Anda.
                                Jika Anda membutuhkan bantuan, tim kami siap membantu. Anda diharapkan untuk mengisi
                                informasi BPR terlebih dahulu sebelum mengisi pelaporan. Selamat bekerja dan semoga
                                laporan Anda terselesaikan dengan baik!</p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Example Colored Cards for Dashboard Demo-->
            <div class="row">
                <div class="col-lg-6 col-xl-4 mb-4">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="me-3">
                                    <div class="text-white-75 small">Laporan</div>
                                    <div class="text-lg fw-bold">Tata Kelola (GCG)</div>
                                </div>
                                <i class="feather-xl text-white-50" data-feather="calendar"></i>
                            </div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between small">
                            <a class="text-white stretched-link" href="<?= base_url('periode') ?>">Mulai Pelaporan</a>
                            <div class="text-white"><i class="fas fa-angle-right"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-xl-4 mb-4">
                    <div class="card bg-warning text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="me-3">
                                    <div class="text-white-75 small">Laporan</div>
                                    <div class="text-lg fw-bold">Transparansi Tahunan</div>
                                </div>
                                <i class="feather-xl text-white-50" data-feather="dollar-sign"></i>
                            </div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between small">
                            <a class="text-white stretched-link" href="<?= base_url('periodetransparansi') ?>">Mulai
                                Pelaporan</a>
                            <div class="text-white"><i class="fas fa-angle-right"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-xl-4 mb-4">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="me-3">
                                    <div class="text-white-75 small">Laporan</div>
                                    <div class="text-lg fw-bold">Manajemen Risiko</div>
                                </div>
                                <i class="feather-xl text-white-50" data-feather="check-square"></i>
                            </div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between small">
                            <a class="text-white stretched-link" href="<?= base_url('periodeprofilresiko') ?>">Mulai
                                Pelaporan</a>
                            <div class="text-white"><i class="fas fa-angle-right"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-xl-4 mb-4">
                    <div class="card bg-info text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="me-3">
                                    <div class="text-white-75 small">Laporan</div>
                                    <div class="text-lg fw-bold">Tingkat Kesehatan Bank (TKS)</div>
                                </div>
                                <i class="feather-xl text-white-50" data-feather="check-square"></i>
                            </div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between small">
                            <a class="text-white stretched-link" href="<?= base_url('periodetks') ?>">Mulai
                                Pelaporan</a>
                            <div class="text-white"><i class="fas fa-angle-right"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"
    integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj"
    crossorigin="anonymous"></script>
<!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct"
    crossorigin="anonymous"></script> -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js" crossorigin="anonymous"></script>
<script src="assets/demo/chart-area-demo.js"></script>
<script src="assets/demo/chart-bar-demo.js"></script>
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest" crossorigin="anonymous"></script>
<script src="js/datatables/datatables-simple-demo.js"></script>
<script src="https://cdn.jsdelivr.net/npm/litepicker/dist/bundle.js" crossorigin="anonymous"></script>
<script src="js/litepicker.js"></script>

</main>
</div>

<style>
    /* Global Styles */
    body {
        font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        background-color: #F8F9FA;
        /* Sangat terang, hampir putih */
        color: #212529;
        /* Dark grey untuk teks utama */
    }

    /* Container Fluid - Main Content Background */
    .container-fluid {
        background-color: #F8F9FA;
        /* Konsisten dengan body background */
        padding-top: 20px;
        /* Sedikit padding atas */
        padding-bottom: 20px;
        /* Sedikit padding bawah */
    }

    /* Page Header (Top Section with Gradient) */
    .page-header {
        /* Menggunakan gradien yang lebih halus atau warna solid yang profesional */
        background: linear-gradient(135deg, #141863, #0056B3);
        /* background-color: #007BFF; */
        /* Atau bisa juga solid blue */
        color: white;
        padding-top: 40px;
        /* Menambah padding agar lebih lapang */
        padding-bottom: 80px;
        /* Sedikit rounded corner */
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        /* Sedikit bayangan untuk kedalaman */
    }

    /* Page Heading inside container-fluid, not in header */
    h1.h1 {
        color: #212529;
        /* Warna teks heading umum */
        font-size: 2.25rem;
        margin-bottom: 1.5rem;
    }

    /* "Selamat Datang di NBP Simpel" Text */
    h1.text-primary {
        color: #007BFF !important;
        /* Biru utama untuk judul selamat datang */
        font-size: 2.5rem;
        font-weight: bold;
        margin-bottom: 1rem;
    }

    /* Description paragraph */
    p.text-gray-700 {
        color: #495057 !important;
        /* Abu-abu sedang untuk teks deskripsi */
        line-height: 1.6;
    }

    /* Card General Styling */
    .card {
        border-radius: 12px;
        /* Radius sudut kartu yang lebih seragam */
        transition: all 0.3s ease-in-out;
        border: none;
        /* Menghilangkan border default */
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        /* Bayangan yang lebih lembut */
    }

    .card:hover {
        transform: translateY(-5px);
        /* Sedikit naik saat dihover */
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        /* Bayangan yang lebih jelas saat dihover */
    }

    .card-body {
        padding: 25px;
        /* Menyesuaikan padding di dalam kartu */
        text-align: left;
        /* Teks di dalam kartu lebih baik rata kiri */
    }

    /* Card Footer */
    .card-footer {
        background-color: rgba(0, 0, 0, 0.03);
        /* Latar belakang footer kartu yang sangat tipis */
        border-top: 1px solid rgba(0, 0, 0, 0.05);
        /* Sedikit garis pemisah */
        border-bottom-left-radius: 12px;
        border-bottom-right-radius: 12px;
        padding: 12px 25px;
        /* Padding footer */
    }

    /* Specific Card Colors */
    .card.bg-success {
        background-color: #28A745 !important;
        /* Hijau untuk "Self Assessment" */
    }

    .card.bg-warning {
        background-color: #FFC107 !important;
        /* Kuning untuk "Transparansi Tahunan" */
    }

    .card.bg-primary {
        /* Mengganti warna "Risk Assessment" menjadi abu-abu gelap atau merah peringatan */
        background-color: #6C757D !important;
        /* Abu-abu netral untuk "Risk Assessment" */
        /* Atau jika ingin lebih tegas sebagai 'risiko', bisa pakai: */
        /* background-color: #DC3545 !important; */
        /* Merah untuk peringatan */
    }

    /* Text inside cards */
    .card-body .text-white-75 {
        color: rgba(255, 255, 255, 0.85) !important;
        /* Warna teks keterangan (misal "Laporan") */
        font-size: 0.9rem;
        margin-bottom: 5px;
    }

    .card-body .text-lg.fw-bold {
        font-size: 1.35rem;
        /* Ukuran teks judul kartu */
        font-weight: 600;
        /* Sedikit lebih tebal */
        color: white;
        /* Pastikan teks judul kartu putih */
    }

    /* Icons inside cards */
    .feather-xl {
        font-size: 40px;
        /* Ukuran ikon sedikit lebih besar */
        opacity: 0.7;
        /* Sedikit transparansi untuk ikon */
    }

    /* Links in card footer */
    .card-footer a.text-white {
        color: white !important;
        text-decoration: none;
        font-weight: 500;
    }

    .card-footer a.text-white:hover {
        text-decoration: underline;
    }

    /* Arrow icon in card footer */
    .card-footer .text-white i {
        color: white !important;
        font-size: 0.9rem;
    }

    /* Margin top for main content relative to header */
    .mt-n5 {
        margin-top: -6rem !important;
        /* Sesuaikan ini jika perlu agar card masuk ke header */
    }

    /* Ensure text in content is visible */
    .text-black-800 {
        color: #212529 !important;
        /* Pastikan judul halaman umum terlihat */
    }
</style>

<!-- Add external JS (Optional) -->
<script src="https://cdn.jsdelivr.net/npm/feather-icons@4.28.0/dist/feather.min.js"
    integrity="sha384-Fkz6OhOcxa4Lw9TYbxgXH7z8xj1J2qj/fjKN2cbP6q6ndpoTxTtL0c93lQ7l1wnv"
    crossorigin="anonymous"></script>
<script>
    feather.replace();
</script>