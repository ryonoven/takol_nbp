<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-4 text-gray-800"><?= $judul; ?></h1>
        <span>
            <?php
            $allApproved = true;
            foreach ($sahamdirdekom as $item) {
                if ($item['is_approved'] != 1) {
                    $allApproved = false;
                    break;
                }
            }
            ?>
            <?php if ($allApproved): ?>
                <span class="badge badge-success" style="font-size: 14px;">
                    Disetujui oleh <strong><?= esc($fullname ?? '-') ?></strong><br>
                    <?= esc($sahamdirdekom[0]['approved_at'] ?? '-') ?>
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
                    <!-- <button onclick="window.print()"
                    class="btn btn-outline-secondary shadow float-right mr-3 ml-2 mt-3">Print <i
                        class="fa fa-print"></i> </button>
                <a href="/sahamdirdekom/excel" class="btn btn-outline-success shadow float-right mt-3">Excel <i
                        class="fa fa-file-excel"></i></a> -->
                    <button onclick="window.print()"
                        class="btn btn-outline-secondary shadow float-right mr-3 ml-2 mt-3">Export TXT <i
                            class="fa fa-print"></i>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <table class="table table-primary">
            <th>Kepemilikan Saham Anggota Direksi pada BPR</th>
        </table>
        <table class="table table-bordered table-secondary">
            <tbody>
                <?php if (empty($sahamdirdekom)) { ?>
                    <tr>
                        <th style="width: 30%;">Nama Direksi :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Persentase Kepemilikan (%) :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($sahamdirdekom as $row): ?>
                        <?php if ($row['direksi'] != NULL): ?>
                            <tr>
                                <th style="width: 3%; background-color: #a3b6ee; color: transparent;" rowspan="3">
                                    <?= $row['id']; ?>
                                </th>
                                <th style="width: 30%;">Nama Direksi :</th>
                                <td style="width: 70%;"><?= $row['direksi']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 30%;">Persentase Kepemilikan (%) :</th>
                                <td style="width: 70%;"><?= $row['persensahamdir']; ?>%</td>
                            </tr>
                            <tr>
                                <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                    <td colspan="3">
                                        <button type="button" data-toggle="modal" class="btn" data-target="#modalUbah" id="btn-edit"
                                            style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                            data-direksi="<?= $row['direksi']; ?>"
                                            data-persensahamdir="<?= $row['persensahamdir']; ?>"><i class="fa fa-edit"></i>&nbsp;
                                        </button>
                                        <button type="button" data-toggle="modal" data-target="#modalHapus" id="btn-hapus" class="btn"
                                            style="font-weight: 600;" data-id="<?= $row['id']; ?>"> <i
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
        <div class="row">
            <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                <div class="col-md">
                    <button type="button" data-toggle="modal" class="btn btn-primary ml-3 mt-3"
                        data-target="#modalTambahsahamdekom"><i class="fa fa-plus"> Tambah Data</i>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <table class="table table-primary">
            <th>Kepemilikan Saham Anggota Dewan Komisaris pada BPR</th>
        </table>
        <table class="table table-bordered table-secondary">
            <tbody>
                <?php if (empty($sahamdirdekom)) { ?>
                    <tr>
                        <th style="width: 30%;">Nama Dewan Komisaris :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Persentase Kepemilikan (%) :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($sahamdirdekom as $row): ?>
                        <?php if ($row['dekom'] != NULL): ?>
                            <tr>
                                <th style="width: 3%; background-color: #a3b6ee; color: transparent;" rowspan="3">
                                    <?= $row['id']; ?>
                                </th>
                                <th style="width: 30%;">Nama Dewan Komisaris :</th>
                                <td style="width: 70%;"><?= $row['dekom']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 30%;">Persentase Kepemilikan (%) :</th>
                                <td style="width: 70%;"><?= $row['persensahamdekom']; ?>%</td>
                            </tr>
                            <tr>
                                <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                    <td colspan="3">
                                        <button type="button" data-toggle="modal" class="btn" data-target="#modalUbahdekom"
                                            id="btn-edit" style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                            data-dekom="<?= $row['dekom']; ?>"
                                            data-persensahamdekom="<?= $row['persensahamdekom']; ?>"><i class="fa fa-edit"></i>&nbsp;
                                        </button>
                                        <button type="button" data-toggle="modal" data-target="#modalHapussahamdirdekom" id="btn-hapus"
                                            class="btn" style="font-weight: 600;" data-id="<?= $row['id']; ?>"> <i
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

</div>
<!-- End Page Content -->

<!--edit data-->
<?php if (!empty($sahamdirdekom)) { ?>
    <div class="modal fade" id="modalUbah">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Tambah Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('sahamdirdekom/ubah'); ?>" method="post">
                        <input type="hidden" name="id" id="id-sahamdirdekom">
                        <div class="mb-3">
                            <label for="direksi" class="form-label">Input Nama Direksi:</label>
                            <input class="form-control" type="text" name="direksi" id="direksi"
                                value="<?= $row['direksi'] ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="persensahamdir" class="form-label">Persentase Kepemilikan (%): </label>
                            <input class="form-control" type="text" name="persensahamdir" id="persensahamdir"
                                placeholder="<?= $row['persensahamdir'] ?>"></input>
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

<?php if (!empty($sahamdirdekom)) { ?>
    <div class="modal fade" id="modalUbahdekom">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Kepemilikan Saham Anggota Dewan Komisaris pada BPR </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('sahamdirdekom/ubahdekom'); ?>" method="post">
                        <input type="hidden" name="id" id="id-sahamdirdekom">
                        <div class="mb-3">
                            <label for="dekom" class="form-label">Input Nama Dewan Komisaris :</label>
                            <input class="form-control" type="text" name="dekom" id="dekom" value="<?= $row['dekom'] ?>"
                                readonly>
                        </div>
                        <div class="form-group">
                            <label for="persensahamdekom" class="form-label">Persentase Kepemilikan (%): </label>
                            <input class="form-control" type="text" name="persensahamdekom" id="persensahamdekom"
                                value="<?= $row['persensahamdekom'] ?>"></input>
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

<!-- Add your modalTambah code here -->
<div class="modal fade" id="modalTambahsahamdir">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Kepemilikan Saham Direksi pada BPR </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('sahamdirdekom/tambahsahamdir'); ?>" method="post">
                    <div class="form-group">
                        <label for="direksi">Input Nama Direksi:</label>
                        <select name="direksi" id="direksi" class="form-control">
                            <option value="">-- Pilih Direksi --</option>
                            <?php foreach ($tgjwbdir as $row): ?>
                                <option value="<?= $row['direksi']; ?>"><?= $row['direksi']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="persensahamdir">Input Persentase Kepemilikan (%): </label>
                        <input type="text" name="persensahamdir" id="persensahamdir" class="form-control">
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
                <h5 class="modal-title">Tambah Kepemilikan Saham Anggota Direksi pada BPR </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('sahamdirdekom/tambahsahamdekom'); ?>" method="post">
                    <div class="form-group">
                        <label for="dekom">Input Nama Dewan Komisaris:</label>
                        <select name="dekom" id="dekom" class="form-control">
                            <option value="">-- Pilih Dewan Komisaris --</option>
                            <?php foreach ($tgjwbdekom as $row): ?>
                                <option value="<?= $row['dekom']; ?>"><?= $row['dekom']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="persensahamdekom">Input Persentase Kepemilikan (%): </label>
                        <input type="text" name="persensahamdekom" id="persensahamdekom" class="form-control">
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" name="tambahsahamdekom" class="btn btn-primary">Tambah Data</button>
            </div>
            </form>
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
                <form action="<?= base_url('sahamdirdekom/tambahkomentar'); ?>" method="post">
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
                            <a href="<?= base_url('sahamdirdekom/approveSemua') ?>" class="btn btn-success shadow mt-3 mx-2"
                                onclick="return confirm('Apakah Anda yakin hendak melakukan approval?');">
                                Approve
                            </a>
                            <a href="<?= base_url('sahamdirdekom/unapproveSemua') ?>"
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
<div class="modal fade" id="modalHapussahamdirdekom">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                Apakah anda yakin ingin menghapus data ini?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btnHapussahamdirdekom">Yakin</button>
            </div>
        </div>
    </div>
</div>