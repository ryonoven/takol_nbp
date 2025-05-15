<div class="container-fluid">

    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800"><?= $judul; ?></h1>

    <?= view('Myth\Auth\Views\_message_block') ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3" style="max-width: 540px;">
                <div class="row g-0">
                    <div class="col-md-4">
                        <img src="<?= base_url('/asset/img/' . user()->user_image); ?>" class="card-img" alt="<?= user()->username; ?>">
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
                                </li>
                            </ul>                        
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
</div>
<!-- /.container-fluid -->

</div>
<!-- End of Main Content -->