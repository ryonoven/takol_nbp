<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-4 text-gray-800"><?= $judul; ?></h1>
        <span>
            <?php
            $allApproved = true;
            foreach ($keluargadirdekompshm as $item) {
                if ($item['is_approved'] != 1) {
                    $allApproved = false;
                    break;
                }
            }
            ?>
            <?php if ($allApproved): ?>
                <span class="badge badge-success" style="font-size: 14px;">
                    Disetujui oleh <strong><?= esc($fullname ?? '-') ?></strong><br>
                    <?= esc($keluargadirdekompshm[0]['approved_at'] ?? '-') ?>
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
                        data-target="#modalTambahkeldir"><i class="fa fa-plus"> Tambah Data</i></button>
                </div>
                <div class="col-md">
                    <!-- <button onclick="window.print()"
                        class="btn btn-outline-secondary shadow float-right mr-3 ml-2 mt-3">Print <i
                            class="fa fa-print"></i> </button>
                    <a href="/keluargadirdekompshm/excel" class="btn btn-outline-success shadow float-right mt-3">Excel
                        <i class="fa fa-file-excel"></i></a> -->
                    <a href="/keluargadirdekompshm/exporttxtkeluargadirdekompshm"
                        class="btn btn-outline-secondary shadow float-right mr-3 ml-2 mt-3">Export TXT <i
                            class="fa fa-file-alt"></i></a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <table class="table table-primary">
            <th>Hubungan Keluarga Anggota Direksi pada BPR</th>
        </table>
        <table class="table table-bordered table-secondary">
            <tbody>
                <?php if (empty($keluargadirdekompshm)) { ?>
                    <tr>
                        <th style="width: 30%;">Nama Direksi :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Hubungan Keluarga Dengan Anggota Direksi Lain di BPR :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Hubungan Keluarga Dengan Anggota Dewan Komisaris Lain di BPR :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Hubungan Keluarga Dengan Pemegang Saham Lain di BPR :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($keluargadirdekompshm as $row): ?>
                        <?php if ($row['direksi'] != NULL): ?>
                            <tr>
                                <th style="width: 3%; background-color: #a3b6ee; color: transparent;" rowspan="5">
                                    <?= $row['id']; ?>
                                </th>
                                <th style="width: 30%;">Nama Direksi :</th>
                                <td style="width: 70%;"><?= $row['direksi']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 30%;">Hubungan Keluarga Dengan Anggota Direksi Lain di BPR :</th>
                                <td style="width: 70%;"><?= $row['hubkeldirdir']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 30%;">Hubungan Keluarga Dengan Anggota Dewan Komisaris Lain di BPR :</th>
                                <td style="width: 70%;"><?= $row['hubkeldirdekom']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 30%;">Hubungan Keluarga Dengan Pemegang Saham Lain di BPR :</th>
                                <td style="width: 70%;"><?= $row['hubkeldirpshm']; ?></td>
                            </tr>
                            <tr>
                                <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                    <td colspan="3">
                                        <button type="button" data-toggle="modal" class="btn" data-target="#modalUbahdir" id="btn-edit"
                                            style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                            data-direksi="<?= $row['direksi']; ?>" data-hubkeldirdir="<?= $row['hubkeldirdir']; ?>"
                                            data-hubkeldirdekom="<?= $row['hubkeldirdekom']; ?>"
                                            data-hubkeldirpshm="<?= $row['hubkeldirpshm']; ?>"><i class="fa fa-edit"></i>&nbsp;
                                        </button>
                                        <button type="button" data-toggle="modal" data-target="#modalHapuskeluargadirdekompshm"
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
                        data-target="#modalTambahkeldekom"><i class="fa fa-plus"> Tambah Data</i></button>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <table class="table table-primary">
            <th>Hubungan Keluarga Anggota Dewan Komisaris pada BPR</th>
        </table>
        <table class="table table-bordered table-secondary">
            <tbody>
                <?php if (empty($keluargadirdekompshm)) { ?>
                    <tr>
                        <th style="width: 30%;">Nama Dewan Komisaris :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Hubungan Keluarga Dengan Anggota Direksi Lain di BPR :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Hubungan Keluarga Dengan Anggota Dewan Komisaris Lain di BPR :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Hubungan Keluarga Dengan Pemegang Saham Lain di BPR :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($keluargadirdekompshm as $row): ?>
                        <?php if ($row['dekom'] != NULL): ?>
                            <tr>
                                <th style="width: 3%; background-color: #a3b6ee; color: transparent;" rowspan="5">
                                    <?= $row['id']; ?>
                                </th>
                                <th style="width: 30%;">Nama Dewan Komisaris :</th>
                                <td style="width: 70%;"><?= $row['dekom']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 30%;">Hubungan Keluarga Dengan Anggota Direksi Lain di BPR :</th>
                                <td style="width: 70%;"><?= $row['hubkeldekomdir']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 30%;">Hubungan Keluarga Dengan Anggota Dewan Komisaris Lain di BPR :</th>
                                <td style="width: 70%;"><?= $row['hubkeldekomdekom']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 30%;">Hubungan Keluarga Dengan Pemegang Saham Lain di BPR :</th>
                                <td style="width: 70%;"><?= $row['hubkeldekompshm']; ?></td>
                            </tr>
                            <tr>
                                <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                    <td colspan="3">
                                        <button type="button" data-toggle="modal" class="btn" data-target="#modalUbahdekom"
                                            id="btn-edit" style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                            data-dekom="<?= $row['dekom']; ?>" data-hubkeldekomdir="<?= $row['hubkeldekomdir']; ?>"
                                            data-hubkeldekomdekom="<?= $row['hubkeldekomdekom']; ?>"
                                            data-hubkeldekompshm="<?= $row['hubkeldekompshm']; ?>"><i class="fa fa-edit"></i>&nbsp;
                                        </button>
                                        <button type="button" data-toggle="modal" data-target="#modalHapuskeluargadirdekompshm"
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
                        data-target="#modalTambahkelpshm"><i class="fa fa-plus"> Tambah Data</i>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <table class="table table-primary">
            <th>Hubungan Keluarga Pemegang Saham pada BPR</th>
        </table>
        <table class="table table-bordered table-secondary">
            <tbody>
                <?php if (empty($keluargadirdekompshm)) { ?>
                    <tr>
                        <th style="width: 30%;">Nama Pemegang Saham :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Hubungan Keluarga Dengan Anggota Direksi Lain di BPR :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Hubungan Keluarga Dengan Anggota Dewan Komisaris Lain di BPR :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Hubungan Keluarga Dengan Pemegang Saham Lain di BPR :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($keluargadirdekompshm as $row): ?>
                        <?php if ($row['pshm'] != NULL): ?>
                            <tr>
                                <th style="width: 3%; background-color: #a3b6ee; color: transparent;" rowspan="5">
                                    <?= $row['id']; ?>
                                </th>
                                <th style="width: 30%;">Nama Pemegang Saham :</th>
                                <td style="width: 70%;"><?= $row['pshm']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 30%;">Hubungan Keluarga Dengan Anggota Direksi Lain di BPR :</th>
                                <td style="width: 70%;"><?= $row['hubkelpshmdir']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 30%;">Hubungan Keluarga Dengan Anggota Dewan Komisaris Lain di BPR :</th>
                                <td style="width: 70%;"><?= $row['hubkelpshmdekom']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 30%;">Hubungan Keluarga Dengan Pemegang Saham Lain di BPR :</th>
                                <td style="width: 70%;"><?= $row['hubkelpshmpshm']; ?></td>
                            </tr>
                            <tr>
                                <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                    <td colspan="3">
                                        <button type="button" data-toggle="modal" class="btn" data-target="#modalUbahpshm" id="btn-edit"
                                            style="font-weight: 600;" data-id="<?= $row['id']; ?>" data-pshm="<?= $row['pshm']; ?>"
                                            data-hubkelpshmdir="<?= $row['hubkelpshmdir']; ?>"
                                            data-hubkelpshmdekom="<?= $row['hubkelpshmdekom']; ?>"
                                            data-hubkelpshmpshm="<?= $row['hubkelpshmpshm']; ?>"><i class="fa fa-edit"></i>&nbsp;
                                        </button>
                                        <button type="button" data-toggle="modal" data-target="#modalHapuskeluargadirdekompshm"
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
        <table style="border-collapse: collapse;">
            <tbody>
                <?php if (empty($keluargadirdekompshm)) { ?>
                    <tr>
                        <th>Tindak Lanjut Rekomendasi Dewan Direksi:</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($keluargadirdekompshm as $row): ?>
                        <?php if ($row['id'] == 1): ?>
                            <tr>
                                <th>Penjelasan Lebih Lanjut:</th>
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
                                        Penjelasan Lebih Lanjut</i>&nbsp;
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
</di>
<!-- End Page Content -->

<!--edit data-->
<?php if (!empty($keluargadirdekompshm)) { ?>
    <div class="modal fade" id="modalUbahdir">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Hubungan Keluarga Anggota Direksi, Anggota Dewan Komisaris, dan
                        Pemegang Saham pada BPR</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('keluargadirdekompshm/ubahdir'); ?>" method="post">
                        <input type="text" name="id" id="id-keluargadirdekompshm">
                        <div class="mb-3">
                            <label for="direksi" class="form-label">Input Nama Direksi:</label>
                            <input class="form-control" type="text" name="direksi" id="direksi"
                                value="<?= $row['direksi'] ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="hubkeldirdir" class="form-label">Hubungan Keluarga Dengan Anggota Direksi Lain
                                di
                                BPR: </label>
                            <input class="form-control" type="text" name="hubkeldirdir" id="hubkeldirdir"
                                placeholder="<?= $row['hubkeldirdir'] ?>" required></input>
                            <small>Note: Diisi dengan format Nama - Hubungan Keluarga / Tidak Ada</small>
                        </div>
                        <div class="form-group">
                            <label for="hubkeldirdekom" class="form-label">Hubungan Keluarga Dengan Anggota Dewan
                                Komisaris
                                Lain di BPR: </label>
                            <input class="form-control" type="text" name="hubkeldirdekom" id="hubkeldirdekom"
                                placeholder="<?= $row['hubkeldirdekom'] ?>" required></input>
                            <small>Note: Diisi dengan format Nama - Hubungan Keluarga / Tidak Ada</small>
                        </div>
                        <div class="form-group">
                            <label for="hubkeldirpshm" class="form-label">Hubungan Keluarga Dengan Pemegang Saham Lain
                                di
                                BPR: </label>
                            <input class="form-control" type="text" name="hubkeldirpshm" id="hubkeldirpshm"
                                placeholder="<?= $row['hubkeldirpshm'] ?>" required></input>
                            <small>Note: Diisi dengan format Nama - Hubungan Keluarga / Tidak Ada</small>
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="ubahdir" class="btn btn-primary">Ubah Data</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
                </form>
            </div>
        </div>
    </div>
<?php } ?>

<?php if (!empty($keluargadirdekompshm)) { ?>
    <div class="modal fade" id="modalUbahdekom">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Hubungan Keluarga Anggota Direksi, Anggota Dewan Komisaris, dan
                        Pemegang Saham pada BPR</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('keluargadirdekompshm/ubahdekom'); ?>" method="post">
                        <input type="text" name="id" id="id-keluargadirdekompshm">
                        <div class="mb-3">
                            <label for="dekom" class="form-label">Input Nama Dewan Komisaris :</label>
                            <input class="form-control" type="text" name="dekom" id="dekom" value="<?= $row['dekom'] ?>"
                                readonly>
                        </div>
                        <div class="form-group">
                            <label for="hubkeldekomdir" class="form-label">Ubah Hubungan Keluarga Dengan Anggota Direksi
                                Lain di BPR:
                            </label>
                            <input class="form-control" type="text" name="hubkeldekomdir" id="hubkeldekomdir"
                                placeholder="<?= $row['hubkeldekomdir'] ?>" required></input>
                            <small>Note: Diisi dengan format Nama - Hubungan Keluarga / Tidak Ada</small>
                        </div>
                        <div class="form-group">
                            <label for="hubkeldekomdekom" class="form-label">Ubah Hubungan Keluarga Dengan Anggota Dewan
                                Komisaris Lain
                                di BPR: </label>
                            <input class="form-control" type="text" name="hubkeldekomdekom" id="hubkeldekomdekom"
                                placeholder="<?= $row['hubkeldekomdekom'] ?>" required></input>
                            <small>Note: Diisi dengan format Nama - Hubungan Keluarga / Tidak Ada</small>
                        </div>
                        <div class="form-group">
                            <label for="hubkeldekompshm" class="form-label">Ubah Hubungan Keluarga Dengan Pemegang Saham
                                Lain di BPR:
                            </label>
                            <input class="form-control" type="text" name="hubkeldekompshm" id="hubkeldekompshm"
                                placeholder="<?= $row['hubkeldekompshm'] ?>" required></input>
                            <small>Note: Diisi dengan format Nama - Hubungan Keluarga / Tidak Ada</small>
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="ubahdekom" class="btn btn-primary">Ubah Data</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
                </form>
            </div>
        </div>
    </div>
<?php } ?>

<?php if (!empty($keluargadirdekompshm)) { ?>
    <div class="modal fade" id="modalUbahpshm">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Hubungan Keluarga Anggota Direksi, Anggota Dewan Komisaris, dan
                        Pemegang Saham pada BPR</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('keluargadirdekompshm/ubahpshm'); ?>" method="post">
                        <input type="text" name="id" id="id-keluargadirdekompshm">
                        <div class="mb-3">
                            <label for="pshm" class="form-label">Input Nama Pemegang Saham :</label>
                            <input class="form-control" type="text" name="pshm" id="pshm" value="<?= $row['pshm'] ?>"
                                readonly>
                        </div>
                        <div class="form-group">
                            <label for="hubkelpshmdir" class="form-label">Ubah Hubungan Keluarga Dengan Anggota Direksi
                                Lain di BPR:
                            </label>
                            <input class="form-control" type="text" name="hubkelpshmdir" id="hubkelpshmdir"
                                placeholder="<?= $row['hubkelpshmdir'] ?>" required></input>
                            <small>Note: Diisi dengan format Nama - Hubungan Keluarga / Tidak Ada</small>
                        </div>
                        <div class="form-group">
                            <label for="hubkelpshmdekom" class="form-label">Ubah Hubungan Keluarga Dengan Anggota Dewan
                                Komisaris Lain di
                                BPR: </label>
                            <input class="form-control" type="text" name="hubkelpshmdekom" id="hubkelpshmdekom"
                                placeholder="<?= $row['hubkelpshmdekom'] ?>" required></input>
                            <small>Note: Diisi dengan format Nama - Hubungan Keluarga / Tidak Ada</small>
                        </div>
                        <div class="form-group">
                            <label for="hubkelpshmpshm" class="form-label">Ubah Hubungan Keluarga Dengan Pemegang Saham
                                Lain di BPR:
                            </label>
                            <input class="form-control" type="text" name="hubkelpshmpshm" id="hubkelpshmpshm"
                                placeholder="<?= $row['hubkelpshmpshm'] ?>" required></input>
                            <small>Note: Diisi dengan format Nama - Hubungan Keluarga / Tidak Ada</small>
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="ubahpshm" class="btn btn-primary">Ubah Data</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
                </form>
            </div>
        </div>
    </div>
<?php } ?>

<?php if (!empty($keluargadirdekompshm)) { ?>
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
                    <form action="<?= base_url('keluargadirdekompshm/ubahketerangan'); ?>" method="post">
                        <input type="hidden" name="id" id="id-keluargadirdekompshm">
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

<div class="modal fade" id="modalTambahkeldir">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Hubungan Keluarga Anggota Direksi pada BPR </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('keluargadirdekompshm/tambahkeldir'); ?>" method="post">
                    <div class="form-group">                        
                        <label for="direksi">Input Nama Direksi:</label>
                        <select name="direksi" id="direksi" class="form-control" required>
                            <?php if (isset($tgjwbdir) && is_array($tgjwbdir)): ?>
                                <option value="">Pilih Direksi</option>
                                <?php foreach ($tgjwbdir as $row): ?>
                                    <?php if (!empty($row['direksi'])): ?>
                                        <option value="<?= $row['direksi']; ?>"><?= $row['direksi']; ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="">Tidak ada data direksi</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="hubkeldirdir">Input Keluarga Dengan Anggota Direksi Lain di BPR </label>
                        <input type="text" name="hubkeldirdir" id="hubkeldirdir" class="form-control" required>
                        <small>Note: Diisi dengan format Nama - Hubungan Keluarga / Tidak Ada</small>
                    </div>
                    <div class="form-group">
                        <label for="hubkeldirdekom">Input Keluarga Dengan Anggota Dewan Komisaris Lain di BPR:
                        </label>
                        <input type="text" name="hubkeldirdekom" id="hubkeldirdekom" class="form-control" required>
                        <small>Note: Diisi dengan format Nama - Hubungan Keluarga / Tidak Ada</small>
                    </div>
                    <div class="form-group">
                        <label for="hubkeldirpshm">Input Keluarga Dengan Pemegang Saham Lain di BPR: </label>
                        <input type="text" name="hubkeldirpshm" id="hubkeldirpshm" class="form-control" required>
                        <small>Note: Diisi dengan format Nama - Hubungan Keluarga / Tidak Ada</small>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" name="tambahkeldir" class="btn btn-primary">Tambah Data</button>
            </div>
            </form>
        </div>
    </div>
</div>

<!-- Add your modalTambah code here -->
<div class="modal fade" id="modalTambahkeldekom">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Kepemilikan Saham Anggota Dewan Komisaris pada BPR </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('keluargadirdekompshm/tambahkeldekom'); ?>" method="post">
                    <div class="form-group">
                        <label for="dekom">Input Nama Dewan Komisaris:</label>
                        <select name="dekom" id="dekom" class="form-control" required>
                            <?php if (isset($tgjwbdekom) && is_array($tgjwbdekom)): ?>
                                <option value="">Pilih Dewan Komisaris</option>
                                <?php foreach ($tgjwbdekom as $row): ?>
                                    <?php if (!empty($row['dekom'])): ?>
                                        <option value="<?= $row['dekom']; ?>"><?= $row['dekom']; ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="">Tidak ada data Dewan Komisaris</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="hubkeldekomdir">Input Keluarga Dengan Anggota Direksi Lain di BPR: </label>
                        <input type="text" name="hubkeldekomdir" id="hubkeldekomdir" class="form-control" required>
                        <small>Note: Diisi dengan format Nama - Hubungan Keluarga / Tidak Ada</small>
                    </div>
                    <div class="form-group">
                        <label for="hubkeldekomdekom">Input Keluarga Dengan Anggota Dewan Komisaris Lain di BPR:
                        </label>
                        <input type="text" name="hubkeldekomdekom" id="hubkeldekomdekom" class="form-control" required>
                        <small>Note: Diisi dengan format Nama - Hubungan Keluarga / Tidak Ada</small>
                    </div>
                    <div class="form-group">
                        <label for="hubkeldekompshm">Input Keluarga Dengan Pemegang Saham Lain di BPR: </label>
                        <input type="text" name="hubkeldekompshm" id="hubkeldekompshm" class="form-control" required>
                        <small>Note: Diisi dengan format Nama - Hubungan Keluarga / Tidak Ada</small>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" name="tambahkeldekom" class="btn btn-primary">Tambah Data</button>
            </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambahkelpshm">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Hubungan Keluarga Pemegang Saham pada BPR </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('keluargadirdekompshm/tambahkelpshm'); ?>" method="post">
                    <div class="form-group">
                        <label for="pshm">Input Nama Pemegang Saham:</label>
                        <select name="pshm" id="pshm" class="form-control" required>
                            <?php if (isset($shmusahadirdekom) && is_array($shmusahadirdekom)): ?>
                                <option value="">Pilih Pemegang Saham</option>
                                <?php foreach ($shmusahadirdekom as $row): ?>
                                    <?php if (!empty($row['pshm'])): ?>
                                        <option value="<?= $row['pshm']; ?>"><?= $row['pshm']; ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="">Tidak ada data Pemegang Saham</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="hubkelpshmdir">Input Keluarga Dengan Anggota Direksi Lain di BPR: </label>
                        <input type="text" name="hubkelpshmdir" id="hubkelpshmdir" class="form-control" required>
                        <small>Note: Diisi dengan format Nama - Hubungan Keluarga / Tidak Ada</small>
                    </div>
                    <div class="form-group">
                        <label for="hubkelpshmdekom">Input Keluarga Dengan Anggota Dewan Komisaris Lain di BPR:
                        </label>
                        <input type="text" name="hubkelpshmdekom" id="hubkelpshmdekom" class="form-control" required>
                        <small>Note: Diisi dengan format Nama - Hubungan Keluarga / Tidak Ada</small>
                    </div>
                    <div class="form-group">
                        <label for="hubkelpshmpshm">Input Keluarga Dengan Pemegang Saham Lain di BPR: </label>
                        <input type="text" name="hubkelpshmpshm" id="hubkelpshmpshm" class="form-control" required>
                        <small>Note: Diisi dengan format Nama - Hubungan Keluarga / Tidak Ada</small>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" name="tambahkelpshm" class="btn btn-primary">Tambah Data</button>
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
                <form action="<?= base_url('keluargadirdekompshm/tambahkomentar'); ?>" method="post">
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
                            <a href="<?= base_url('keluargadirdekompshm/approveSemua') ?>"
                                class="btn btn-success shadow mt-3 mx-2"
                                onclick="return confirm('Apakah Anda yakin hendak melakukan approval?');">
                                Approve
                            </a>
                            <a href="<?= base_url('keluargadirdekompshm/unapproveSemua') ?>"
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
<div class="modal fade" id="modalHapuskeluargadirdekompshm">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                Apakah anda yakin ingin menghapus data ini?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btnHapuskeluargadirdekompshm">Yakin</button>
            </div>
        </div>
    </div>
</div>