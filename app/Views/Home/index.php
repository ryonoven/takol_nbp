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
                            <h1 class="text-primary text-center">Selamat Datang di NBP Bantu Lapor</h1>
                            <p class="text-gray-700 mb-0">Browse our fully designed UI toolkit! Browse our prebuilt app
                                pages, components, and utilites, and be sure to look at our full documentation!</p>
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
                                    <div class="text-white-75 small">Laporan Tata Kelola</div>
                                    <div class="text-lg fw-bold">Self Assessment</div>
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
                                    <div class="text-white-75 small">Laporan Tata Kelola</div>
                                    <div class="text-lg fw-bold">Transparansi Tahunan</div>
                                </div>
                                <i class="feather-xl text-white-50" data-feather="dollar-sign"></i>
                            </div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between small">
                            <a class="text-white stretched-link" href="#!">View Report</a>
                            <div class="text-white"><i class="fas fa-angle-right"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-xl-4 mb-4">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="me-3">
                                    <div class="text-white-75 small">Laporan Tata Kelola</div>
                                    <div class="text-lg fw-bold">Risk Assessment</div>
                                </div>
                                <i class="feather-xl text-white-50" data-feather="check-square"></i>
                            </div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between small">
                            <a class="text-white stretched-link" href="#!">View Tasks</a>
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
    .container-fluid {
        background-color: #f8f9fc;
    }

    .page-header {
        background: linear-gradient(135deg, #6c757d, #343a40);
        color: white;
        border-radius: 10px;
        padding: 20px 0;
    }

    h1 {
        font-size: 2.5rem;
        font-weight: bold;
        color: #007bff;
    }

    .card {
        border-radius: 15px;
        transition: all 0.3s ease-in-out;
    }

    .card:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .card-body {
        padding: 30px;
        text-align: center;
    }

    .card-footer {
        background-color: rgba(0, 0, 0, 0.05);
        border-radius: 0 0 15px 15px;
    }

    .text-lg {
        font-size: 1.25rem;
        font-weight: bold;
    }

    .text-white-75 {
        opacity: 0.75;
    }

    .feather-xl {
        font-size: 36px;
    }

    .text-primary {
        color: #007bff !important;
    }

    .text-success {
        color: #28a745 !important;
    }

    .text-warning {
        color: #ffc107 !important;
    }

    .text-danger {
        color: #dc3545 !important;
    }
</style>

<!-- Add external JS (Optional) -->
<script src="https://cdn.jsdelivr.net/npm/feather-icons@4.28.0/dist/feather.min.js"
    integrity="sha384-Fkz6OhOcxa4Lw9TYbxgXH7z8xj1J2qj/fjKN2cbP6q6ndpoTxTtL0c93lQ7l1wnv"
    crossorigin="anonymous"></script>
<script>
    feather.replace();
</script>