<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Detail User</h1>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3" style="max-width: 540px;">
                <div class="row g-0">
                    <div class="col-md-4">
                        <img src="<?= base_url(); ?>/asset/img/<?= $user->user_image; ?>" class="card-img"
                            alt="<?= $user->username; ?>">
                    </div>
                    <div class="col-md-8">
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <!-- Set form action here -->
                                <form method="post" action="<?= site_url('admin/updateKodebpr/' . $user->userid); ?>">
                                    <li class="list-group-item">
                                        <h4><?= $user->username; ?></h4>
                                    </li>
                                    <?php if ($user->fullname): ?>
                                        <li class="list-group-item"><?= $user->fullname; ?></li>
                                    <?php endif; ?>
                                    <li class="list-group-item"><?= $user->email; ?></li>
                                    <li class="list-group-item">BPR NBP <?= $user->kodebpr; ?></li>
                                    <li class="list-group-item">
                                        <label for="kodebpr">Select Kode BPR:</label>
                                        <select name="kodebpr" id="kodebpr" class="form-control">
                                            <option value="">Select Kode BPR</option>
                                            <?php foreach ($kodebpr as $kode): ?>
                                                <option value="<?= $kode->kodebpr; ?>" <?= ($user->kodebpr == $kode->kodebpr) ? 'selected' : ''; ?>>
                                                    <?= $kode->kodebpr; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </li>
                                    <li class="list-group-item">
                                        <button type="submit" class="btn btn-primary">Update</button>
                                    </li>
                                    <li class="list-group-item">
                                        <a href="<?= base_url('admin') ?>">&laquo; Back to user list</a>
                                    </li>
                                </form> <!-- Close the form here -->
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?= view('Myth\Auth\Views\_message_block') ?>
</div>
