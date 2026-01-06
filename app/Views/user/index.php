<div class="container-fluid">

    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800"><?= $judul; ?></h1>

    <?= view('Myth\Auth\Views\_message_block') ?>

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card mb-3" style="max-width: 600px;">
                <div class="row g-0">
                    <div class="col-md-4">
                        <img src="<?= base_url('/asset/img/' . user()->user_image); ?>" class="card-img"
                            alt="<?= user()->username; ?>">
                    </div>
                    <div class="col-md-8">
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <h4><?= user()->fullname; ?></h4>
                                </li>
                                <li class="list-group-item">
                                    <?= user()->username; ?>
                                </li>
                                <li class="list-group-item"><?= user()->email; ?></li>
                                <!-- Email Section with Edit Form -->

                                <!-- Collapsible Form Section -->
                                <div class="collapse" id="emailForm">
                                    <form action="<?= base_url('user/updateEmail/' . user()->id) ?>" method="POST">
                                        <?= csrf_field(); ?>
                                        <div class="form-group">
                                            <label for="email">Email</label>
                                            <input type="email" id="email" name="email" class="form-control"
                                                value="<?= user()->email; ?>" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary mt-2">Update Email</button>
                                    </form>
                                </div>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <button class="btn btn-dark mt-2 d-flex justify-content-center align-items-center mx-auto"
                type="button" data-bs-toggle="collapse" data-bs-target="#emailForm" aria-expanded="false"
                aria-controls="emailForm">
                Edit
            </button>
        </div>
    </div>

</div>
<!-- /.container-fluid -->

<!-- Bootstrap JS (required for collapse) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>



<style>
    /* Container Styling */
    .container-fluid {
        background-color: #f8f9fc;
        padding: 20px;
        border-radius: 8px;
        align-items: center;
    }

    .card {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 45vh;
        /* Membuat container mengisi seluruh tinggi viewport */
        padding: 0;
        margin: 0;
    }

    /* Page Heading */
    .h3 {
        color: #4e73df;
        font-weight: bold;
        text-align: center;
        margin-bottom: 30px;
    }

    /* Image inside Card */
    .card-img {
        border-top-left-radius: 12px;
        border-bottom-left-radius: 12px;
        object-fit: fill;
        height: 100%;
    }

    /* Card Body */
    .card-body {
        background-color: #ffffff;
        padding: 15px;
        border-top-right-radius: 12px;
        border-bottom-right-radius: 12px;
    }

    /* List Group */
    .list-group-item {
        border: none;
        padding: 10px;
        font-size: 16px;
        color: #495057;
    }

    /* User Info Styling */
    .list-group-item h4 {
        font-size: 20px;
        font-weight: 600;
        color: #333;
        margin-bottom: 10px;
    }

    .list-group-item:hover {
        background-color: #f1f1f1;
    }

    /* Ensure responsiveness */
    @media (max-width: 768px) {
        .card {
            max-width: 100%;
            margin: 0 auto;
        }

        .card-body {
            padding: 10px;
        }
    }
</style>