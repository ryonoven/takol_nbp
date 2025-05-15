<div class="container-fluid">

    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800"><?= $judul; ?></h1>

    <div class="row">
        <div class="col-lg-8">
            <table class="table">
                <thead>
                    <tr>
                    <th scope="col">No</th>
                    <th scope="col">Username</th>
                    <th scope="col">Email</th>
                    <th scope="col">Role</th>
                    <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1;?>
                    <?php foreach ($users as $user) : ?>
                    <tr>
                    <th scope="row"><?= $i++; ?></th>
                    <td><?= $user->username; ?></td>
                    <td><?= $user->email; ?></td>
                    <td><?= $user->name; ?></td>
                    <td>
                        <a href="<?= base_url('admin/' . $user->userid); ?>" class="btn btn-info">Detail</a>
                    </td>
                    </tr>
                    <?php endforeach; ?>
                    
                </tbody>
                </table>
        </div>

    </div>

    <?= view('Myth\Auth\Views\_message_block') ?>


</div>
<!-- /.container-fluid -->

</div>
<!-- End of Main Content -->