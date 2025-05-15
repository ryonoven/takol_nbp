<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-4 text-gray-800"><?= $judul; ?></h1>
        <span>
            <?php
            $allApproved = true;
            foreach ($shmusahadirdekom as $item) {
                if ($item['is_approved'] != 1) {
                    $allApproved = false;
                    break;
                }
            }
            ?>
            <?php if ($allApproved): ?>
                <span class="badge badge-success" style="font-size: 14px;">
                    Disetujui oleh <strong><?= esc($fullname ?? '-') ?></strong><br>
                    <?= esc($shmusahadirdekom[0]['approved_at'] ?? '-') ?>
                </span>
            <?php else: ?>
                <span class="badge badge-danger" style="font-size: 14px;">
                    Belum Disetujui Seluruhnya<br>Oleh Direksi
                </span>
            <?php endif; ?>
        </span>
    </div>
</div>

<!-- Display success message -->
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
        <?php if ($userInGroupDekom || $userInGroupDireksi || $userInGroupPE || $userInGroupAdmin): ?>
            <button type="button" data-toggle="modal" class="btn btn-outline-success shadow float-right mr-3 ml-2 mt-3"
                data-target="#modalTambahkomentar"><i class="far fa-check-circle"></i> Komentar dan
                Approval
            </button>
        <?php endif; ?>
        <?php if ($userInGroupPE || $userInGroupAdmin): ?>
            <div class="row">
                <div class="col-md">
                    <button type="button" data-toggle="modal" class="btn btn-primary ml-3 mt-3"
                        data-target="#modalTambahsahamdir"><i class="fa fa-plus"> Tambah Data</i></button>
                </div>
                <div class="col-md">
                    <button onclick="window.print()"
                        class="btn btn-outline-secondary shadow float-right mr-3 ml-2 mt-3">Print <i
                            class="fa fa-print"></i> </button>
                    <a href="/shmusahadirdekom/excel" class="btn btn-outline-success shadow float-right mt-3">Excel <i
                            class="fa fa-file-excel"></i></a>
                    <a href="/shmusahadirdekom/exporttxtshmusahadirdekom"
                        class="btn btn-outline-secondary shadow float-right mr-3 ml-2 mt-3">Export TXT <i
                            class="fa fa-file-alt"></i></a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <table class="table table-primary">
            <th>Kepemilikan Saham Anggota Direksi pada Kelompok Usaha BPR </th>
        </table>
        <table class="table table-bordered table-secondary">
            <tbody>
                <?php if (empty($shmusahadirdekom)) { ?>
                    <tr>
                        <th style="width: 30%;">Nama Direksi :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Nama Kelompok Usaha BPR :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Persentase Kepemilikan (%) :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Persentase Kepemilikan (%) Tahun Sebelumnya :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($shmusahadirdekom as $row): ?>
                        <?php if ($row['direksi'] != NULL): ?>
                            <tr>
                                <th style="width: 3%; background-color: #a3b6ee; color: transparent;" rowspan="5">
                                    <?= $row['id']; ?>
                                </th>
                                <th style="width: 30%;">Nama Direksi :</th>
                                <td style="width: 70%;"><?= $row['direksi']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 30%;">Nama Kelompok Usaha BPR :</th>
                                <td style="width: 70%;"><?= $row['usahadir']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 30%;">Persentase Kepemilikan (%) :</th>
                                <td style="width: 70%;"><?= $row['persenshmdir']; ?>%</td>
                            </tr>
                            <tr>
                                <th style="width: 30%;">Persentase Kepemilikan (%) Tahun Sebelumnya :</th>
                                <td style="width: 70%;"><?= $row['persenshmdirlalu']; ?>%</td>
                            </tr>
                            <tr>
                                <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                    <td colspan="3">
                                        <button type="button" data-toggle="modal" class="btn" data-target="#modalUbahdir" id="btn-edit"
                                            style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                            data-direksi="<?= $row['direksi']; ?>" data-usahadir="<?= $row['usahadir']; ?>"
                                            data-persenshmdir="<?= $row['persenshmdir']; ?>"
                                            data-persenshmdirlalu="<?= $row['persenshmdirlalu']; ?>"><i class="fa fa-edit"></i>&nbsp;
                                        </button>
                                        <button type="button" data-toggle="modal" data-target="#modalHapusshmusahadirdekom"
                                            id="btn-hapus" class="btn" style="font-weight: 600;" data-id="<?= $row['id']; ?>"> <i
                                                class="fa fa-trash"></i>&nbsp;</button>
                                    </td>
                                <?php endif; ?>
                            </tr>
                            <tr height="40">
                                <td colspan="3" style="border-color: white; background-color: white;"></td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-reader">
        <?php if ($userInGroupPE || $userInGroupAdmin): ?>
            <div class="row">
                <div class="col-md">
                    <button type="button" data-toggle="modal" class="btn btn-primary ml-3 mt-3"
                        data-target="#modalTambahsahamdekom"><i class="fa fa-plus"> Tambah Data</i></button>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <table class="table table-primary">
            <th>Kepemilikan Saham Anggota Dewan Komisaris pada Kelompok Usaha BPR </th>
        </table>
        <table class="table table-bordered table-secondary">
            <tbody>
                <?php if (empty($shmusahadirdekom)) { ?>
                    <tr>
                        <th style="width: 30%;">Nama Dewan Komisaris :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Nama Kelompok Usaha BPR :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Persentase Kepemilikan (%) :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Persentase Kepemilikan (%) Tahun Sebelumnya :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($shmusahadirdekom as $row): ?>
                        <?php if ($row['dekom'] != NULL): ?>
                            <tr>
                                <th style="width: 3%; background-color: #a3b6ee; color: transparent;" rowspan="5">
                                    <?= $row['id']; ?>
                                </th>
                                <th style="width: 30%;">Nama Dewan Komisaris :</th>
                                <td style="width: 70%;"><?= $row['dekom']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 30%;">Nama Kelompok Usaha BPR :</th>
                                <td style="width: 70%;"><?= $row['usahadekom']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 30%;">Persentase Kepemilikan (%) :</th>
                                <td style="width: 70%;"><?= $row['persenshmdekom']; ?>%</td>
                            </tr>
                            <tr>
                                <th style="width: 30%;">Persentase Kepemilikan (%) Tahun Sebelumnya :</th>
                                <td style="width: 70%;"><?= $row['persenshmdekomlalu']; ?>%</td>
                            </tr>
                            <tr>
                                <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                    <td colspan="3">
                                        <button type="button" data-toggle="modal" class="btn" data-target="#modalUbahdekom"
                                            id="btn-edit" style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                            data-dekom="<?= $row['dekom']; ?>" data-usahadekom="<?= $row['usahadekom']; ?>"
                                            data-persenshmdekom="<?= $row['persenshmdekom']; ?>"
                                            data-persenshmdekomlalu="<?= $row['persenshmdekomlalu']; ?>"><i
                                                class="fa fa-edit"></i>&nbsp;
                                        </button>
                                        <button type="button" data-toggle="modal" data-target="#modalHapusshmusahadirdekom"
                                            id="btn-hapus" class="btn" style="font-weight: 600;" data-id="<?= $row['id']; ?>"> <i
                                                class="fa fa-trash"></i>&nbsp;</button>
                                    </td>
                                <?php endif; ?>
                            </tr>
                            <tr height="40">
                                <td colspan="3" style="border-color: white; background-color: white;"></td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<div class="card">
    <div class="card-reader">
        <?php if ($userInGroupPE || $userInGroupAdmin): ?>
            <div class="row">
                <div class="col-md">
                    <button type="button" data-toggle="modal" class="btn btn-primary ml-3 mt-3"
                        data-target="#modalTambahsahampshm"><i class="fa fa-plus"> Tambah Data</i></button>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <table class="table table-primary">
            <th>Kepemilikan Saham Pemegang Saham BPR/BPRS </th>
        </table>
        <table class="table table-bordered table-secondary">
            <tbody>
                <?php if (empty($shmusahadirdekom)) { ?>
                    <tr>
                        <th style="width: 30%;">Nama Pemegang Saham BPR/BPRS :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">NIK :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Nama Kelompok Usaha BPR :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Persentase Kepemilikan (%) :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Persentase Kepemilikan (%) Tahun Sebelumnya :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($shmusahadirdekom as $row): ?>
                        <?php if ($row['pshm'] != NULL): ?>
                            <tr>
                                <th style="width: 3%; background-color: #a3b6ee; color: transparent;" rowspan="7">
                                    <?= $row['id']; ?>
                                </th>
                                <th style="width: 30%;">Nama Pemegang Saham BPR/BPRS :</th>
                                <td style="width: 70%;"><?= $row['pshm']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 30%;">NIK :</th>
                                <td style="width: 70%;"><?= $row['nikpshm']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 30%;">Nama Kelompok Usaha BPR :</th>
                                <td style="width: 70%;"><?= $row['usahapshm']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 30%;">Persentase Kepemilikan (%) :</th>
                                <td style="width: 70%;"><?= $row['persenpshm']; ?>%</td>
                            </tr>
                            <tr>
                                <th style="width: 30%;">Persentase Kepemilikan (%) Tahun Sebelumnya :</th>
                                <td style="width: 70%;"><?= $row['persenpshmlalu']; ?>%</td>
                            </tr>
                            <tr>
                            <tr>
                                <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                    <td colspan="3">
                                        <button type="button" data-toggle="modal" class="btn" data-target="#modalUbahpshm" id="btn-edit"
                                            style="font-weight: 600;" data-id="<?= $row['id']; ?>" data-pshm="<?= $row['pshm']; ?>"
                                            data-nikpshm="<?= $row['nikpshm']; ?>" data-usahapshm="<?= $row['usahapshm']; ?>"
                                            data-persenpshm="<?= $row['persenpshm']; ?>"
                                            data-persenpshmlalu="<?= $row['persenpshmlalu']; ?>"><i class="fa fa-edit"></i>&nbsp;
                                        </button>
                                        <button type="button" data-toggle="modal" data-target="#modalHapusshmusahadirdekom"
                                            id="btn-hapus" class="btn" style="font-weight: 600;" data-id="<?= $row['id']; ?>"> <i
                                                class="fa fa-trash"></i>&nbsp;</button>
                                    </td>
                                <?php endif; ?>
                            </tr>
                            </tr>
                            <tr height="40">
                                <td colspan="3" style="border-color: white; background-color: white;"></td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php } ?>
            </tbody>
        </table>
        <table style="border-collapse: collapse;">
            <tbody>
                <?php if (empty($shmusahadirdekom)) { ?>
                    <tr>
                        <th>Tindak Lanjut Rekomendasi Dewan Direksi:</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($shmusahadirdekom as $row): ?>
                        <?php if ($row['id'] == 1): ?>
                            <tr>
                                <th>Tindak Lanjut Rekomendasi Dewan Direksi:</th>
                            </tr>
                            <tr>
                                <td>
                                    <ol style="list-style: none;">
                                        <?php
                                        $keterangan = explode("\n", $row['keterangan']);
                                        foreach ($keterangan as $poin):
                                            ?>
                                            <li><?= htmlspecialchars(trim($poin)); ?></li>
                                        <?php endforeach; ?>
                                    </ol>
                                </td>
                            </tr>
                            <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                <button type="button" data-toggle="modal" class="btn btn-primary mt-3"
                                    data-target="#modalUbahketerangan" id="btn-edit" style="font-weight: 600;"
                                    data-id="<?= $row['id']; ?>" data-keterangan="<?= $row['keterangan']; ?>"><i class="fa fa-edit">
                                        Tambah Tindak Lanjut
                                        Direksi</i>&nbsp;
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<!-- End Page Content -->

<!--edit data-->
<?php if (!empty($shmusahadirdekom)) { ?>
    <div class="modal fade" id="modalUbahdir">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Kepemilikan Saham Anggota Direksi, Dewan Komisaris, dan Pemegang Saham pada
                        Kelompok Usaha BPR </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('shmusahadirdekom/ubahdir'); ?>" method="post">
                        <input type="text" name="id" id="id-shmusahadirdekom">
                        <div class="mb-3">
                            <label for="direksi" class="form-label">Input Nama Direksi:</label>
                            <input class="form-control" type="text" name="direksi" id="direksi"
                                value="<?= $row['direksi'] ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="usahadir" class="form-label">Nama Kelompok Usaha BPR: </label>
                            <input class="form-control" type="text" name="usahadir" id="usahadir"
                                placeholder="<?= $row['usahadir'] ?>"></input>
                        </div>
                        <div class="form-group">
                            <label for="persenshmdir" class="form-label">Persentase Kepemilikan (%): </label>
                            <input class="form-control" type="text" name="persenshmdir" id="persenshmdir"
                                placeholder="<?= $row['persenshmdir'] ?>"></input>
                        </div>
                        <div class="form-group">
                            <label for="persenshmdirlalu" class="form-label">Persentase Kepemilikan (%) Tahun Sebelumnya:
                            </label>
                            <input class="form-control" type="text" name="persenshmdirlalu" id="persenshmdirlalu"
                                placeholder="<?= $row['persenshmdirlalu'] ?>"></input>
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="ubahdir" class="btn btn-primary">Ubah Data</button>
                </div>
                </form>
            </div>
        </div>
    </div>
<?php } ?>

<?php if (!empty($shmusahadirdekom)) { ?>
    <div class="modal fade" id="modalUbahdekom">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Kepemilikan Saham Anggota Dewan Komisaris pada BPR</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('shmusahadirdekom/ubahdekom'); ?>" method="post">
                        <input type="text" name="id" id="id-shmusahadirdekom">
                        <div class="mb-3">
                            <label for="dekom" class="form-label">Input Nama Dewan Komisaris :</label>
                            <input class="form-control" type="text" name="dekom" id="dekom" value="<?= $row['dekom'] ?>"
                                readonly>
                        </div>
                        <div class="form-group">
                            <label for="usahadekom" class="form-label">Nama Kelompok Usaha BPR (%): </label>
                            <input class="form-control" type="text" name="usahadekom" id="usahadekom"
                                placeholder="<?= $row['usahadekom'] ?>"></input>
                        </div>
                        <div class="form-group">
                            <label for="persenshmdekom" class="form-label">Persentase Kepemilikan (%): </label>
                            <input class="form-control" type="text" name="persenshmdekom" id="persenshmdekom"
                                placeholder="<?= $row['persenshmdekom'] ?>"></input>
                        </div>
                        <div class="form-group">
                            <label for="persenshmdekomlalu" class="form-label">Persentase Kepemilikan (%) Tahun Sebelumnya:
                            </label>
                            <input class="form-control" type="text" name="persenshmdekomlalu" id="persenshmdekomlalu"
                                placeholder="<?= $row['persenshmdekomlalu'] ?>"></input>
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="ubahdekom" class="btn btn-primary">Ubah Data</button>
                </div>
                </form>
            </div>
        </div>
    </div>
<?php } ?>

<?php if (!empty($shmusahadirdekom)) { ?>
    <div class="modal fade" id="modalUbahpshm">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Kepemilikan Saham Pemegang Saham pada BPR </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('shmusahadirdekom/ubahpshm'); ?>" method="post">
                        <input type="hidden" name="id" id="id-shmusahadirdekom">
                        <div class="mb-3">
                            <label for="pshm" class="form-label">Input Nama Pemegang Saham :</label>
                            <input class="form-control" type="text" name="pshm" id="pshm" value="<?= $row['pshm'] ?>"
                                readonly>
                        </div>
                        <div class="form-group">
                            <label for="nikpshm" class="form-label">Nama NIK: </label>
                            <input class="form-control" type="text" name="nikpshm" id="nikpshm"
                                placeholder="<?= $row['nikpshm'] ?>"></input>
                        </div>
                        <div class="form-group">
                            <label for="usahapshm" class="form-label">Nama Kelompok Usaha BPR: </label>
                            <input class="form-control" type="text" name="usahapshm" id="usahapshm"
                                placeholder="<?= $row['usahapshm'] ?>"></input>
                        </div>
                        <div class="form-group">
                            <label for="persenpshm" class="form-label">Persentase Kepemilikan (%): </label>
                            <input class="form-control" type="text" name="persenpshm" id="persenpshm"
                                placeholder="<?= $row['persenpshm'] ?>"></input>
                        </div>
                        <div class="form-group">
                            <label for="persenpshmlalu" class="form-label">Persentase Kepemilikan (%) Tahun Sebelumnya:
                            </label>
                            <input class="form-control" type="text" name="persenpshmlalu" id="persenpshmlalu"
                                placeholder="<?= $row['persenpshmlalu'] ?>"></input>
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="ubahpshm" class="btn btn-primary">Ubah Data</button>
                </div>
                </form>
            </div>
        </div>
    </div>
<?php } ?>

<?php if (!empty($shmusahadirdekom)) { ?>
    <div class="modal fade" id="modalUbahketerangan">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Tambah Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('shmusahadirdekom/ubahketerangan'); ?>" method="post">
                        <input type="hidden" name="id" id="id-shmusahadirdekom">
                        <div class="form-group">
                            <label for="keterangan" class="form-label">Input Tindak Lanjut Direksi BPR: </label>
                            <textarea class="form-control" type="text" name="keterangan" id="keterangan"
                                style="height: 150px" placeholder="<?= $row['keterangan'] ?>"></textarea>
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="ubahketerangan" class="btn btn-primary">Ubah Data</button>
                </div>
                </form>
            </div>
        </div>
    </div>
<?php } ?>

<div class="modal fade" id="modalTambahsahamdir">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('shmusahadirdekom/tambahsahamdir'); ?>" method="post">
                    <div class="form-group">
                        <label for="direksi">Input Nama Direksi:</label>
                        <select name="direksi" id="direksi" class="form-control">
                            <?php if (isset($shmusahadirdekom) && is_array($shmusahadirdekom)): ?>
                                <?php foreach ($shmusahadirdekom as $row): ?>
                                    <option value="<?= $row['direksi']; ?>"><?= $row['direksi']; ?></option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="">Tidak ada data direksi</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="usahadir">Input Nama Kelompok Usaha BPR: </label>
                        <input type="text" name="usahadir" id="usahadir" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="persenshmdir">Input Persentase Kepemilikan (%): </label>
                        <input type="text" name="persenshmdir" id="persenshmdir" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="persenshmdirlalu">Persentase Kepemilikan (%) Tahun Sebelumnya: </label>
                        <input type="text" name="persenshmdirlalu" id="persenshmdirlalu" class="form-control">
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" name="tambahsahamdir" class="btn btn-primary">Tambah Data</button>
            </div>
            </form>
        </div>
    </div>
</div>

<!-- Add your modalTambah code here -->
<div class="modal fade" id="modalTambahsahamdekom">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Kepemilikan Saham Anggota Dewan Komisaris pada BPR </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('shmusahadirdekom/tambahsahamdekom'); ?>" method="post">
                    <div class="form-group">
                        <label for="dekom">Input Nama Dewan Komisaris:</label>
                        <select name="dekom" id="dekom" class="form-control">
                            <?php if (isset($tgjwbdekom) && is_array($tgjwbdekom)): ?>
                                <?php foreach ($tgjwbdekom as $row): ?>
                                    <option value="<?= $row['dekom']; ?>"><?= $row['dekom']; ?></option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="">Tidak ada data Dewan Komisaris</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="usahadekom">Input Nama Kelompok Usaha BPR: </label>
                        <input type="text" name="usahadekom" id="usahadekom" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="persenshmdekom">Input Persentase Kepemilikan (%): </label>
                        <input type="text" name="persenshmdekom" id="persenshmdekom" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="persenshmdekomlalu">Persentase Kepemilikan (%) Tahun Sebelumnya: </label>
                        <input type="text" name="persenshmdekomlalu" id="persenshmdekomlalu" class="form-control">
                    </div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="tambahsahamdekom" class="btn btn-primary">Tambah Data</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>

            </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambahsahampshm">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Kepemilikan Saham Pemegang Saham pada BPR </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('shmusahadirdekom/tambahsahampshm'); ?>" method="post">
                    <div class="form-group">
                        <label for="pshm">Input Nama Pemegang Saham:</label>
                        <input type="text" name="pshm" id="pshm" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="nikpshm">Input NIK:</label>
                        <input type="text" name="nikpshm" id="nikpshm" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="usahapshm">Input Nama Kelompok Usaha BPR: </label>
                        <input type="text" name="usahapshm" id="usahapshm" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="persenpshm">Input Persentase Kepemilikan (%): </label>
                        <input type="text" name="persenpshm" id="persenpshm" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="persenpshmlalu">Persentase Kepemilikan (%) Tahun Sebelumnya: </label>
                        <input type="text" name="persenpshmlalu" id="persenpshmlalu" class="form-control">
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="tambahsahampshm" class="btn btn-primary">Tambah Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambahkomentar">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Komentar dan Approval </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('shmusahadirdekom/tambahkomentar'); ?>" method="post">
                    <div class="form-group">
                        <label for="komentar">Komentar Direksi dan Dewan Komisaris:</label>
                        <textarea type="text" name="komentar" id="komentar" class="form-control" style="height: 150px;"
                            readonly></textarea>
                    </div>
                    <?php if ($userInGroupAdmin || $userInGroupDekom || $userInGroupDireksi): ?>
                        <div class="form-group">
                            <label for="komentar">Input Komentar Direksi dan Dewan Komisaris:</label>
                            <textarea type="komentar" name="komentar" id="komentar" class="form-control"
                                style="height: 150px;"></textarea>
                        </div>
                        <div class="col-md d-flex justify-content-center align-items-center" style="margin-top: 20px;">
                            <a href="<?= base_url('shmusahadirdekom/approveSemua') ?>"
                                class="btn btn-success shadow mt-3 mx-2"
                                onclick="return confirm('Apakah Anda yakin hendak melakukan approval?');">
                                Approve
                            </a>
                            <a href="<?= base_url('shmusahadirdekom/unapproveSemua') ?>"
                                class="btn btn-danger shadow mt-3 mx-2"
                                onclick="return confirm('Apakah Anda yakin hendak membatalkan semua approval?');">
                                Batalkan Approval
                            </a>
                        </div>
                    <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" name="tambahkomentar" class="btn btn-primary">Tambah Data</button>
            </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Hapus data  -->
<div class="modal fade" id="modalHapusshmusahadirdekom">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                Apakah anda yakin ingin menghapus data ini?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btnHapusshmusahadirdekom">Yakin</button>
            </div>
        </div>
    </div>
</div>