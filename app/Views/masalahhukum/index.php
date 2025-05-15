<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-4 text-gray-800"><?= $judul; ?></h1>
        <span>
            <?php
            $allApproved = true;
            foreach ($masalahhukum as $item) {
                if ($item['is_approved'] != 1) {
                    $allApproved = false;
                    break;
                }
            }
            ?>
            <?php if ($allApproved): ?>
                <span class="badge badge-success" style="font-size: 14px;">
                    Disetujui oleh <strong><?= esc($fullname ?? '-') ?></strong><br>
                    <?= esc($masalahhukum[0]['approved_at'] ?? '-') ?>
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
                        data-target="#modalTambahmasalahhukum"><i class="fa fa-plus"> Tambah Data</i></button>
                </div>
                <div class="col-md">
                    <button onclick="window.print()"
                        class="btn btn-outline-secondary shadow float-right mr-3 ml-2 mt-3">Print <i
                            class="fa fa-print"></i> </button>
                    <a href="/masalahhukum/excel" class="btn btn-outline-success shadow float-right mt-3">Excel <i
                            class="fa fa-file-excel"></i></a>
                    <a href="/masalahhukum/exporttxtmasalahhukum"
                        class="btn btn-outline-secondary shadow float-right mr-3 ml-2 mt-3">Export TXT <i
                            class="fa fa-file-alt"></i></a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <table class="table table-primary">
            <th>1.1. Permasalahan Hukum yang Telah Selesai</th>
        </table>
        <table class="table table-bordered table-secondary">
            <tbody>
                <?php if (empty($masalahhukum)) { ?>
                    <tr>
                        <th style="width: 30%;">Permasalahan Hukum Perdata yang Telah Selesai (telah mempunyai kekuatan
                            hukum yang tetap) :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Permasalahan Hukum Pidana yang Telah Selesai (telah mempunyai kekuatan
                            hukum yang tetap) :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($masalahhukum as $row): ?>
                        <tr>
                            <th style="width: 25%;">Permasalahan Hukum Perdata yang Telah Selesai (telah mempunyai kekuatan
                                hukum yang tetap) :</th>
                            <td style="width: 75%;"><?= $row['hukumperdataselesai']; ?></td>
                        </tr>
                        <tr>
                            <th style="width: 25%;">Permasalahan Hukum Pidana yang Telah Selesai (telah mempunyai kekuatan
                                hukum yang tetap) :</th>
                            <td style="width: 75%;"><?= $row['hukumpidanaselesai']; ?></td>
                        </tr>
                        <tr>
                            <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                <td colspan="3">
                                    <button type="button" data-toggle="modal" class="btn" data-target="#modalUbah" id="btn-edit"
                                        style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                        data-hukumperdataselesai="<?= $row['hukumperdataselesai']; ?>"
                                        data-hukumpidanaselesai="<?= $row['hukumpidanaselesai']; ?>"><i
                                            class="fa fa-edit"></i>&nbsp;
                                    </button>
                                    <button type="button" data-toggle="modal" data-target="#modalHapusmasalahhukum" id="btn-hapus"
                                        class="btn" style="font-weight: 600;" data-id="<?= $row['id']; ?>"> <i
                                            class="fa fa-trash"></i>&nbsp;</button>
                                </td>
                            <?php endif; ?>
                        </tr>
                        <tr height="40">
                            <td colspan="3" style="border-color: white; background-color: white;"></td>
                        </tr>
                    <?php endforeach; ?>
                <?php } ?>
            </tbody>
        </table>
        <table class="table table-primary">
            <th>1.2. Permasalah Hukum yang Dalam Proses Penyelesaian</th>
        </table>
        <table class="table table-bordered table-secondary">
            <tbody>
                <?php if (empty($masalahhukum)) { ?>
                    <tr>
                        <th style="width: 30%;">Permasalahan Hukum Perdata yang Dalam Proses Penyelesaian :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Permasalahan Hukum Pidana yang Dalam Proses Penyelesaian :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($masalahhukum as $row): ?>
                        <tr>
                            <th style="width: 25%;">Permasalahan Hukum Perdata yang Dalam Proses Penyelesaian :</th>
                            <td style="width: 75%;"><?= $row['hukumperdataproses']; ?></td>
                        </tr>
                        <tr>
                            <th style="width: 25%;">Permasalahan Hukum Pidana yang Dalam Proses Penyelesaian :</th>
                            <td style="width: 75%;"><?= $row['hukumpidanaproses']; ?></td>
                        </tr>
                        <tr>
                            <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                <td colspan="3">
                                    <button type="button" data-toggle="modal" class="btn" data-target="#modalUbahproses"
                                        id="btn-edit" style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                        data-hukumperdataproses="<?= $row['hukumperdataproses']; ?>"
                                        data-hukumpidanaproses="<?= $row['hukumpidanaproses']; ?>"><i class="fa fa-edit"></i>&nbsp;
                                    </button>
                                    <button type="button" data-toggle="modal" data-target="#modalHapusmasalahhukum" id="btn-hapus"
                                        class="btn" style="font-weight: 600;" data-id="<?= $row['id']; ?>"> <i
                                            class="fa fa-trash"></i>&nbsp;</button>
                                </td>
                            <?php endif; ?>
                        </tr>
                        <tr height="40">
                            <td colspan="3" style="border-color: white; background-color: white;"></td>
                        </tr>
                    <?php endforeach; ?>
                <?php } ?>
            </tbody>
        </table>
        <table style="border-collapse: collapse;">
            <tbody>
                <?php if (empty($masalahhukum)) { ?>
                    <tr>
                        <th>Keterangan:</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($masalahhukum as $row): ?>
                        <?php if ($row['id'] == 1): ?>
                            <tr>
                                <th>Keterangan:</th>
                            </tr>
                            <tr>
                                <td>
                                    <ol style="list-style: lower-latin;">
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
                                        Direksi</i>&nbsp;</button>
                            <?php endif; ?>
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
<?php if (!empty($masalahhukum)) { ?>
    <div class="modal fade" id="modalUbah">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Permasalahan Hukum yang Telah Selesai </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('masalahhukum/ubah'); ?>" method="post">
                        <input type="hidden" name="id" id="id-masalahhukum">
                        <div class="mb-3">
                            <label for="hukumperdataselesai" class="form-label">Input Permasalahan Hukum Perdata yang Telah
                                Selesai (telah mempunyai kekuatan hukum yang tetap):</label>
                            <input class="form-control" type="text" name="hukumperdataselesai" id="hukumperdataselesai"
                                value="<?= $row['hukumperdataselesai'] ?>">
                        </div>
                        <div class="mb-3">
                            <label for="hukumpidanaselesai" class="form-label">Input Permasalahan Hukum Pidana yang Telah
                                Selesai (telah mempunyai kekuatan hukum yang tetap):</label>
                            <input class="form-control" type="text" name="hukumpidanaselesai" id="hukumpidanaselesai"
                                value="<?= $row['hukumpidanaselesai'] ?>">
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

<?php if (!empty($masalahhukum)) { ?>
    <div class="modal fade" id="modalUbahproses">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Permasalahan Hukum yang Dalam Proses Penyelesaian </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('masalahhukum/ubahproses'); ?>" method="post">
                        <input type="hidden" name="id" id="id-masalahhukum">
                        <div class="mb-3">
                            <label for="hukumperdataproses" class="form-label">Input Permasalahan Hukum Perdata yang Telah
                                Selesai (telah mempunyai kekuatan hukum yang tetap):</label>
                            <input class="form-control" type="text" name="hukumperdataproses" id="hukumperdataproses"
                                value="<?= $row['hukumperdataproses'] ?>">
                        </div>
                        <div class="mb-3">
                            <label for="hukumpidanaproses" class="form-label">Input Permasalahan Hukum Pidana yang Telah
                                Selesai (telah mempunyai kekuatan hukum yang tetap):</label>
                            <input class="form-control" type="text" name="hukumpidanaproses" id="hukumpidanaproses"
                                value="<?= $row['hukumpidanaproses'] ?>">
                        </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="ubahproses" class="btn btn-primary">Ubah Data</button>
                </div>
                </form>
            </div>
        </div>
    </div>
<?php } ?>

<?php if (!empty($masalahhukum)) { ?>
    <div class="modal fade" id="modalUbahketerangan">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Keterangan </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('masalahhukum/ubahketerangan'); ?>" method="post">
                        <input type="hidden" name="id" id="id-masalahhukum">
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

<!-- Add your modalTambah code here -->
<div class="modal fade" id="modalTambahmasalahhukum">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Permasalahan Hukum yang Dihadapi </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('masalahhukum/tambahmasalahhukum'); ?>" method="post">
                    <div class="form-group">
                        <label for="hukumperdataselesai">Input Permasalahan Hukum Perdata yang Telah Selesai (telah
                            mempunyai kekuatan hukum yang tetap):</label>
                        <input type="text" name="hukumperdataselesai" id="hukumperdataselesai" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="hukumpidanaselesai">Input Permasalahan Hukum Pidana yang Telah Selesai (telah
                            mempunyai kekuatan hukum yang tetap): </label>
                        <input type="text" name="hukumpidanaselesai" id="hukumpidanaselesai" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="hukumperdataproses">Input Permasalahan Hukum Perdata yang Dalam Proses Penyelesaian:
                        </label>
                        <input type="text" name="hukumperdataproses" id="hukumperdataproses" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="hukumpidanaproses">Input Permasalahan Hukum Pidana yang Dalam Proses Penyelesaian:
                        </label>
                        <input type="text" name="hukumpidanaproses" id="hukumpidanaproses" class="form-control">
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" name="tambahmasalahhukum" class="btn btn-primary">Tambah Data</button>
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
                <form action="<?= base_url('masalahhukum/tambahkomentar'); ?>" method="post">
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
                            <a href="<?= base_url('masalahhukum/approveSemua') ?>"
                                class="btn btn-success shadow mt-3 mx-2"
                                onclick="return confirm('Apakah Anda yakin hendak melakukan approval?');">
                                Approve
                            </a>
                            <a href="<?= base_url('masalahhukum/unapproveSemua') ?>"
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

<!-- Modal Hapus data bisnis -->
<div class="modal fade" id="modalHapusmasalahhukum">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                Apakah anda yakin ingin menghapus data no-<span id="idData"></span>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btnHapusmasalahhukum">Yakin</button>
            </div>
        </div>
    </div>
</div>