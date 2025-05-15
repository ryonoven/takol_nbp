<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-4 text-gray-800"><?= $judul; ?></h1>
        <span>
            <?php
            $allApproved = true;
            foreach ($penjelasanumum as $item) {
                if ($item['is_approved'] != 1) {
                    $allApproved = false;
                    break;
                }
            }
            ?>
            <?php if ($allApproved): ?>
                <span class="badge badge-success" style="font-size: 14px;">
                    Disetujui oleh <strong><?= esc($fullname ?? '-') ?></strong><br>
                    <?= esc($penjelasanumum[0]['approved_at'] ?? '-') ?>
                </span>
            <?php else: ?>
                <span class="badge badge-danger" style="font-size: 14px;">
                    Belum Disetujui Seluruhnya<br>Oleh Direksi
                </span>
            <?php endif; ?>
        </span>
    </div>
</div>

<?php if (session()->get('message')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
        <strong><?= session()->getFlashdata('message'); ?></strong>
    </div>
<?php endif; ?>

<!-- Display error message -->
<div class="row">
    <div class="col-md-6">
        <?php if (session()->get('err')): ?>
            <div class="alert alert-danger" role="alert"><?= session()->get('err'); ?></div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-reader">
        <!-- Button for adding comments and approval (visible to specific user groups) -->
        <?php if ($userInGroupDekom || $userInGroupDireksi || $userInGroupPE || $userInGroupAdmin): ?>
            <button type="button" data-toggle="modal" class="btn btn-outline-success shadow float-right mr-3 ml-2 mt-3"
                data-target="#modalTambahkomentar">
                <i class="far fa-check-circle"></i> Komentar dan Approval
            </button>
        <?php endif; ?>

        <!-- Button for adding or editing data (visible to specific user groups) -->
        <?php if ($userInGroupPE || $userInGroupAdmin): ?>
            <div class="row">
                <div class="col-md">
                    <!-- If no data exists, show the "Add Data" button -->
                    <?php if (empty($penjelasanumum)) { ?>
                        <button type="button" data-toggle="modal" class="btn btn-primary ml-3 mt-3"
                            data-target="#modalTambahpenjelas">
                            <i class="fa fa-plus"></i> Tambah Data
                        </button>
                    <?php } else { ?>
                        <!-- If data exists, show the "Edit Data" button -->
                        <button type="button" data-toggle="modal" class="btn btn-primary ml-3 mt-3" data-target="#modalUbah"
                            id="btn-edit" style="font-weight: 600;" data-id="<?= $penjelasanumum[0]['id'] ?? ''; ?>"
                            data-namabpr="<?= $penjelasanumum[0]['namabpr'] ?? ''; ?>"
                            data-alamat="<?= $penjelasanumum[0]['alamat'] ?? ''; ?>"
                            data-nomor="<?= $penjelasanumum[0]['nomor'] ?? ''; ?>"
                            data-penjelasan="<?= $penjelasanumum[0]['penjelasan'] ?? ''; ?>"
                            data-peringkatkomposit="<?= $penjelasanumum[0]['peringkatkomposit'] ?? ''; ?>"
                            data-penjelasankomposit="<?= $penjelasanumum[0]['penjelasankomposit'] ?? ''; ?>">
                            <i class="fa fa-edit"></i> Ubah Data
                        </button>

                        <!-- If data exists, show the "Delete Data" button for each row -->
                        <?php foreach ($penjelasanumum as $row): ?>
                            <button type="button" data-toggle="modal" data-target="#modalHapuspenjelas" id="btn-hapus"
                                class="btn btn-danger ml-3 mt-3" style="font-weight: 600;" data-id="<?= $row['id']; ?>">
                                <i class="fa fa-trash"></i> Hapus Data
                            </button>
                        <?php endforeach; ?>
                    <?php } ?>
                </div>

                <div class="col-md">
                    <!-- Optional print and export buttons (currently commented out) -->
                    <!-- <button onclick="window.print()" class="btn btn-outline-secondary shadow float-right mr-3 ml-2 mt-3">Print <i class="fa fa-print"></i> </button> -->
                    <a href="/penjelasanumum/exporttxtpenjelasanumum"
                        class="btn btn-outline-secondary shadow float-right mr-3 ml-2 mt-3">
                        Export TXT <i class="fa fa-file-alt"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="card-body">
        <table class="table table-primary">
            <th>Informasi Umum BPR</th>
        </table>
        <table class="table table-bordered table-secondary">
            <tbody>
                <?php if (empty($penjelasanumum)) { ?>
                    <tr>
                        <th style="width: 30%;">Nama BPR :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Alamat :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Nomor Telepon :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($penjelasanumum as $row): ?>
                        <tr>
                            <th style="width: 30%;">Nama BPR :</th>
                            <td style="width: 70%;"><?= $row['namabpr']; ?></td>
                        </tr>
                        <tr>
                            <th style="width: 30%;">Alamat :</th>
                            <td style="width: 70%;"><?= $row['alamat']; ?></td>
                        </tr>
                        <tr>
                            <th style="width: 30%;">Nomor Telepon :</th>
                            <td style="width: 70%;"><?= $row['nomor']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php } ?>
            </tbody>
        </table>
        <table style="border-collapse: collapse;">
            <tbody>
                <?php if (empty($penjelasanumum)) { ?>
                    <tr>
                        <th>Penjelasan Umum :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($penjelasanumum as $row): ?>
                        <tr>
                            <th>Penjelasan Umum :</th>
                        </tr>
                        <tr>
                            <td><?= $row['penjelasan']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php } ?>
            </tbody>
        </table>
        <br>
        <table class="table table-primary">
            <th>Ringkasan Hasil Penilaian Sendiri atas Penerapan Tata Kelola</th>
        </table>
        <table class="table table-bordered table-secondary">
            <tbody>
                <?php if (empty($penjelasanumum)) { ?>
                    <tr>
                        <th style="width: 30%;">Peringkat Komposit Hasil<br>Penilaian Sendiri (Self Assessment)<br>Tata
                            Kelola:</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($penjelasanumum as $row): ?>
                        <tr>
                            <th style="width: 30%;">Peringkat Komposit Hasil<br>Penilaian Sendiri (Self Assessment)<br>Tata
                                Kelola:</th>
                            <td style="width: 70%;"><?= $row['peringkatkomposit']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php } ?>
            </tbody>
        </table>
        <table style="border-collapse: collapse;">
            <tbody>
                <?php if (empty($penjelasanumum)) { ?>
                    <tr>
                        <th>Penjelasan Peringkat Komposit Hasil Penilaian Sendiri (Self Assessment) Tata Kelola:</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($penjelasanumum as $row): ?>
                        <tr>
                            <th>Penjelasan Peringkat Komposit Hasil Penilaian Sendiri (Self Assessment) Tata Kelola:</th>
                        </tr>
                        <tr>
                            <td><?= $row['penjelasankomposit']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
</div>
<!-- End Page Content -->

<!--edit data-->
<?php if (!empty($penjelasanumum)) { ?>
    <div class="modal fade" id="modalUbah">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah <?= $judul; ?> </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('penjelasanumum/ubah'); ?>" method="post">
                        <input type="hidden" name="id" id="id-penjelasanumum">
                        <div class="mb-3">
                            <label for="namabpr" class="form-label">Input Nama BPR:</label>
                            <input class="form-control" type="text" name="namabpr" id="namabpr"
                                value="<?= $row['namabpr'] ?>">
                        </div>
                        <div class="mb-3">
                            <label for="alamat" class="form-label">Input Alamat BPR: </label>
                            <input class="form-control" type="text" name="alamat" id="alamat" value="<?= $row['alamat'] ?>">
                        </div>
                        <div class="mb-3">
                            <label for="nomor">Input Nomor Telepon BPR: </label>
                            <input class="form-control" type="text" name="nomor" id="nomor" value="<?= $row['nomor'] ?>">
                        </div>
                        <div class="mb-3">
                            <label for="penjelasan" class="form-label">Input Penjelasan Umum: </label>
                            <textarea class="form-control" type="text" name="penjelasan" id="penjelasan"
                                style="height: 150px"
                                placeholder="<?= $row['penjelasan'] ?>"><?= $row['penjelasan'] ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="peringkatkomposit">Peringkat Komposit: </label>
                            <input class="form-control" type="text" name="peringkatkomposit" id="peringkatkomposit"
                                value="<?= $row['peringkatkomposit'] ?>">
                        </div>
                        <div class="mb-3">
                            <label for="penjelasankomposit">Penjelasan Peringkat Komposit: </label>
                            <textarea class="form-control" type="text" name="penjelasankomposit" id="penjelasankomposit"
                                placeholder="<?= $row['penjelasankomposit'] ?>"
                                style="height: 150px"><?= $row['penjelasankomposit'] ?></textarea>
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="ubah" class="btn btn-primary">Ubah Data</button>
                </div>
                </form>
            </div>
        </div>
    </div>
<?php } ?>

<div class="modal fade" id="modalTambahpenjelas">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah <?= $judul; ?> </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('penjelasanumum/tambahpenjelas'); ?>" method="post">
                    <div class="form-group">
                        <label for="namabpr">Input Nama BPR:</label>
                        <input type="text" name="namabpr" id="namabpr" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="alamat">Input Alamat BPR: </label>
                        <input type="text" name="alamat" id="alamat" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="nomor">Input Nomor Telepon BPR: </label>
                        <input type="text" name="nomor" id="nomor" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="penjelasan">Input Penjelasan Umum: </label>
                        <input type="text" name="penjelasan" id="penjelasan" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="peringkatkomposit">Input Peringkat Komposit: </label>
                        <input type="text" name="peringkatkomposit" id="peringkatkomposit" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="penjelasankomposit">Input Penjelasan Komposit: </label>
                        <input type="text" name="penjelasankomposit" id="penjelasankomposit" class="form-control">
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" name="tambahpenjelas" class="btn btn-primary">Tambah Data</button>
            </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambahkomentar">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Komentar dan Approval</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <?php date_default_timezone_set('Asia/Jakarta'); ?>
                <form action="<?= base_url('penjelasanumum/tambahkomentar'); ?>" method="post">
                    <div class="form-group">
                        <label for="komentar">Komentar Direksi dan Dewan Komisaris:</label>
                        <textarea type="text" name="komentar" id="komentar" class="form-control" style="height: 150px"
                            readonly></textarea>
                    </div>
                    <div class="form-group">
                        <label for="komentar">Input Komentar: </label>
                        <textarea type="text" name="komentar" id="komentar" class="form-control"
                            style="height: 150px"></textarea>
                    </div>
                    <?php if ($userInGroupAdmin || $userInGroupDekom || $userInGroupDireksi): ?>
                        <div class="col-md d-flex justify-content-center align-items-center" style="margin-top: 20px;">
                            <a href="<?= base_url('penjelasanumum/approveSemua') ?>"
                                class="btn btn-success shadow mt-3 mx-2"
                                onclick="return confirm('Apakah Anda yakin hendak melakukan approval?');">
                                Approve
                            </a>
                            <a href="<?= base_url('penjelasanumum/unapproveSemua') ?>"
                                class="btn btn-danger shadow mt-3 mx-2"
                                onclick="return confirm('Apakah Anda yakin hendak membatalkan semua approval?');">
                                Batalkan Approval
                            </a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" name="tambahkomentar" class="btn btn-primary">Tambah Data</button>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="modalHapuspenjelas">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                Apakah anda yakin ingin menghapus data?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btnHapuspenjelas">Yakin</button>
            </div>
        </div>
    </div>
</div>