<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-4 text-gray-800"><?= $judul; ?></h1>
        <span>
            <?php
            $allApproved = true;
            foreach ($keuangandirdekompshm as $item) {
                if ($item['is_approved'] != 1) {
                    $allApproved = false;
                    break;
                }
            }
            ?>
            <?php if ($allApproved): ?>
                <span class="badge badge-success" style="font-size: 14px;">
                    Disetujui oleh <strong><?= esc($fullname ?? '-') ?></strong><br>
                    <?= esc($keuangandirdekompshm[0]['approved_at'] ?? '-') ?>
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
                        data-target="#modalTambahuangdir"><i class="fa fa-plus"> Tambah Data</i></button>
                </div>
                <div class="col-md">
                    <!-- <button onclick="window.print()"
                        class="btn btn-outline-secondary shadow float-right mr-3 ml-2 mt-3">Print <i
                            class="fa fa-print"></i> </button>
                    <a href="/keuangandirdekompshm/excel" class="btn btn-outline-success shadow float-right mt-3">Excel
                        <i class="fa fa-file-excel"></i></a> -->
                    <a href="/keuangandirdekompshm/exporttxtkeuangandirdekompshm"
                        class="btn btn-outline-secondary shadow float-right mr-3 ml-2 mt-3">Export TXT <i
                            class="fa fa-file-alt"></i></a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <table class="table table-primary">
            <th>Kepemilikan Saham Anggota Direksi pada Perusahaan Lain</th>
        </table>
        <table class="table table-bordered table-secondary">
            <tbody>
                <?php if (empty($keuangandirdekompshm)) { ?>
                    <tr>
                        <th style="width: 30%;">Nama Direksi :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Hubungan Keuangan Dengan Anggota Direksi Lain di BPR :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Hubungan Keuangan Dengan Anggota Dewan Komisaris Lain di BPR :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Hubungan Keuangan Dengan Pemegang Saham Lain di BPR :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($keuangandirdekompshm as $row): ?>
                        <?php if ($row['direksi'] != NULL): ?>
                            <tr>
                                <th style="width: 3%; background-color: #a3b6ee; color: transparent;" rowspan="5">
                                    <?= $row['id']; ?>
                                </th>
                                <th style="width: 30%;">Nama Direksi :</th>
                                <td style="width: 70%;"><?= $row['direksi']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 30%;">Hubungan Keuangan Dengan Anggota Direksi Lain di BPR :</th>
                                <td style="width: 70%;"><?= $row['hubdirdir']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 30%;">Hubungan Keuangan Dengan Anggota Dewan Komisaris Lain di BPR :</th>
                                <td style="width: 70%;"><?= $row['hubdirdekom']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 30%;">Hubungan Keuangan Dengan Pemegang Saham Lain di BPR :</th>
                                <td style="width: 70%;"><?= $row['hubdirpshm']; ?></td>
                            </tr>
                            <tr>
                                <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                    <td colspan="3">
                                        <button type="button" data-toggle="modal" class="btn" data-target="#modalUbahdir" id="btn-edit"
                                            style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                            data-direksi="<?= $row['direksi']; ?>" data-hubdirdir="<?= $row['hubdirdir']; ?>"
                                            data-hubdirdekom="<?= $row['hubdirdekom']; ?>"
                                            data-hubdirpshm="<?= $row['hubdirpshm']; ?>"><i class="fa fa-edit"></i>&nbsp;
                                        </button>
                                        <button type="button" data-toggle="modal" data-target="#modalHapuskeuangandirdekompshm"
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
                        data-target="#modalTambahuangdekom"><i class="fa fa-plus"> Tambah Data</i></button>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <table class="table table-primary">
            <th>Hubungan Keuangan Anggota Dewan Komisaris pada BPR</th>
        </table>
        <table class="table table-bordered table-secondary">
            <tbody>
                <?php if (empty($keuangandirdekompshm)) { ?>
                    <tr>
                        <th style="width: 30%;">Nama Dewan Komisaris :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Hubungan Keuangan Dengan Anggota Direksi Lain di BPR :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Hubungan Keuangan Dengan Anggota Dewan Komisaris Lain di BPR :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Hubungan Keuangan Dengan Pemegang Saham Lain di BPR :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($keuangandirdekompshm as $row): ?>
                        <?php if ($row['dekom'] != NULL): ?>
                            <tr>
                                <th style="width: 3%; background-color: #a3b6ee; color: transparent;" rowspan="5">
                                    <?= $row['id']; ?>
                                </th>
                                <th style="width: 30%;">Nama Dewan Komisaris :</th>
                                <td style="width: 70%;"><?= $row['dekom']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 30%;">Hubungan Keuangan Dengan Anggota Direksi Lain di BPR :</th>
                                <td style="width: 70%;"><?= $row['hubdekomdir']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 30%;">Hubungan Keuangan Dengan Anggota Dewan Komisaris Lain di BPR :</th>
                                <td style="width: 70%;"><?= $row['hubdekomdekom']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 30%;">Hubungan Keuangan Dengan Pemegang Saham Lain di BPR :</th>
                                <td style="width: 70%;"><?= $row['hubdekompshm']; ?></td>
                            </tr>
                            <tr>
                                <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                    <td colspan="3">
                                        <button type="button" data-toggle="modal" class="btn" data-target="#modalUbahdekom"
                                            id="btn-edit" style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                            data-dekom="<?= $row['dekom']; ?>" data-hubdekomdir="<?= $row['hubdekomdir']; ?>"
                                            data-hubdekomdekom="<?= $row['hubdekomdekom']; ?>"
                                            data-hubdekompshm="<?= $row['hubdekompshm']; ?>"><i class="fa fa-edit"></i>&nbsp;
                                        </button>
                                        <button type="button" data-toggle="modal" data-target="#modalHapuskeuangandirdekompshm"
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
                        data-target="#modalTambahuangpshm"><i class="fa fa-plus"> Tambah Data</i></button>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <table class="table table-primary">
            <th>Hubungan Keuangan Pemegang Saham pada BPR</th>
        </table>
        <table class="table table-bordered table-secondary">
            <tbody>
                <?php if (empty($keuangandirdekompshm)) { ?>
                    <tr>
                        <th style="width: 30%;">Nama Pemegang Saham :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Hubungan Keuangan Dengan Anggota Direksi Lain di BPR :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Hubungan Keuangan Dengan Anggota Dewan Komisaris Lain di BPR :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Hubungan Keuangan Dengan Pemegang Saham Lain di BPR :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($keuangandirdekompshm as $row): ?>
                        <?php if ($row['pshm'] != NULL): ?>
                            <tr>
                                <th style="width: 3%; background-color: #a3b6ee; color: transparent;" rowspan="5">
                                    <?= $row['id']; ?>
                                </th>
                                <th style="width: 30%;">Nama Pemegang Saham :</th>
                                <td style="width: 70%;"><?= $row['pshm']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 30%;">Hubungan Keuangan Dengan Anggota Direksi Lain di BPR :</th>
                                <td style="width: 70%;"><?= $row['hubpshmdir']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 30%;">Hubungan Keuangan Dengan Anggota Dewan Komisaris Lain di BPR :</th>
                                <td style="width: 70%;"><?= $row['hubpshmdekom']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 30%;">Hubungan Keuangan Dengan Pemegang Saham Lain di BPR :</th>
                                <td style="width: 70%;"><?= $row['hubpshmpshm']; ?></td>
                            </tr>
                            <tr>
                                <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                    <td colspan="3">
                                        <button type="button" data-toggle="modal" class="btn" data-target="#modalUbahpshm" id="btn-edit"
                                            style="font-weight: 600;" data-id="<?= $row['id']; ?>" data-pshm="<?= $row['pshm']; ?>"
                                            data-hubpshmdir="<?= $row['hubpshmdir']; ?>"
                                            data-hubpshmdekom="<?= $row['hubpshmdekom']; ?>"
                                            data-hubpshmpshm="<?= $row['hubpshmpshm']; ?>"><i class="fa fa-edit"></i>&nbsp;
                                        </button>
                                        <button type="button" data-toggle="modal" data-target="#modalHapuskeuangandirdekompshm"
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
                <?php if (empty($keuangandirdekompshm)) { ?>
                    <tr>
                        <th>Tindak Lanjut Rekomendasi Dewan Direksi:</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($keuangandirdekompshm as $row): ?>
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
<?php if (!empty($keuangandirdekompshm)) { ?>
    <div class="modal fade" id="modalUbahdir">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Hubungan Keuangan Anggota Direksi, Anggota Dewan Komisaris, dan
                        Pemegang Saham pada BPR</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('keuangandirdekompshm/ubahdir'); ?>" method="post">
                        <input type="hidden" name="id" id="id-keuangandirdekompshm">
                        <div class="mb-3">
                            <label for="direksi" class="form-label">Input Nama Direksi:</label>
                            <input class="form-control" type="text" name="direksi" id="direksi"
                                value="<?= $row['direksi'] ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="hubdirdir" class="form-label">Hubungan Keuangan Dengan Anggota Direksi Lain di
                                BPR: </label>
                            <input class="form-control" type="text" name="hubdirdir" id="hubdirdir"
                                placeholder="<?= $row['hubdirdir'] ?>"></input>
                        </div>
                        <div class="form-group">
                            <label for="hubdirdekom" class="form-label">Hubungan Keuangan Dengan Anggota Dewan Komisaris
                                Lain di BPR: </label>
                            <input class="form-control" type="text" name="hubdirdekom" id="hubdirdekom"
                                placeholder="<?= $row['hubdirdekom'] ?>"></input>
                        </div>
                        <div class="form-group">
                            <label for="hubdirpshm" class="form-label">Hubungan Keuangan Dengan Pemegang Saham Lain di
                                BPR: </label>
                            <input class="form-control" type="text" name="hubdirpshm" id="hubdirpshm"
                                placeholder="<?= $row['hubdirpshm'] ?>"></input>
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

<?php if (!empty($keuangandirdekompshm)) { ?>
    <div class="modal fade" id="modalUbahdekom">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Hubungan Keuangan Anggota Direksi, Anggota Dewan Komisaris, dan
                        Pemegang Saham pada BPR</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('keuangandirdekompshm/ubahdekom'); ?>" method="post">
                        <input type="hidden" name="id" id="id-keuangandirdekompshm">
                        <div class="mb-3">
                            <label for="dekom" class="form-label">Input Nama Dewan Komisaris :</label>
                            <input class="form-control" type="text" name="dekom" id="dekom" value="<?= $row['dekom'] ?>"
                                readonly>
                        </div>
                        <div class="form-group">
                            <label for="hubdekomdir" class="form-label">Keuangan Dengan Anggota Direksi Lain di BPR:
                            </label>
                            <input class="form-control" type="text" name="hubdekomdir" id="hubdekomdir"
                                placeholder="<?= $row['hubdekomdir'] ?>"></input>
                        </div>
                        <div class="form-group">
                            <label for="hubdekomdekom" class="form-label">Keuangan Dengan Anggota Dewan Komisaris Lain
                                di BPR: </label>
                            <input class="form-control" type="text" name="hubdekomdekom" id="hubdekomdekom"
                                placeholder="<?= $row['hubdekomdekom'] ?>"></input>
                        </div>
                        <div class="form-group">
                            <label for="hubdekompshm" class="form-label">Keuangan Dengan Pemegang Saham Lain di BPR:
                            </label>
                            <input class="form-control" type="text" name="hubdekompshm" id="hubdekompshm"
                                placeholder="<?= $row['hubdekompshm'] ?>"></input>
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

<?php if (!empty($keuangandirdekompshm)) { ?>
    <div class="modal fade" id="modalUbahpshm">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Hubungan Keuangan Anggota Direksi, Anggota Dewan Komisaris, dan
                        Pemegang Saham pada BPR</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('keuangandirdekompshm/ubahpshm'); ?>" method="post">
                        <input type="hidden" name="id" id="id-keuangandirdekompshm">
                        <div class="mb-3">
                            <label for="pshm" class="form-label">Input Nama Pemegang Saham :</label>
                            <input class="form-control" type="text" name="pshm" id="pshm" value="<?= $row['pshm'] ?>"
                                readonly>
                        </div>
                        <div class="form-group">
                            <label for="hubpshmdir" class="form-label">Keuangan Dengan Anggota Direksi Lain di BPR:
                            </label>
                            <input class="form-control" type="text" name="hubpshmdir" id="hubpshmdir"
                                placeholder="<?= $row['hubpshmdir'] ?>"></input>
                            <small>Note: Diisi dengan format Nama - Hubungan Keuangan / Tidak Ada</small>
                        </div>
                        <div class="form-group">
                            <label for="hubpshmdekom" class="form-label">Keuangan Dengan Anggota Dewan Komisaris Lain di
                                BPR: </label>
                            <input class="form-control" type="text" name="hubpshmdekom" id="hubpshmdekom"
                                placeholder="<?= $row['hubpshmdekom'] ?>"></input>
                            <small>Note: Diisi dengan format Nama - Hubungan Keuangan / Tidak Ada</small>
                        </div>
                        <div class="form-group">
                            <label for="hubpshmpshm" class="form-label">Keuangan Dengan Pemegang Saham Lain di
                                BPR:</label>
                            <input class="form-control" type="text" name="hubpshmpshm" id="hubpshmpshm"
                                placeholder="<?= $row['hubpshmpshm'] ?>"></input>
                            <small>Note: Diisi dengan format Nama - Hubungan Keuangan / Tidak Ada</small>
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

<?php if (!empty($keuangandirdekompshm)) { ?>
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
                    <form action="<?= base_url('keuangandirdekompshm/ubahketerangan'); ?>" method="post">
                        <input type="hidden" name="id" id="id-keuangandirdekompshm">
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

<div class="modal fade" id="modalTambahuangdir">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Hubungan Keuangan Anggota Direksi pada BPR </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('keuangandirdekompshm/tambahuangdir'); ?>" method="post">
                    <div class="form-group">
                        <label for="direksi">Input Nama Direksi:</label>
                        <label for="direksi">Input Nama Direksi:</label>
                        <select name="direksi" id="direksi" class="form-control">
                            <?php if (isset($tgjwbdir) && is_array($tgjwbdir)): ?>
                                <option value="">Pilih Direksi</option>
                                <?php foreach ($tgjwbdir as $row): ?>
                                    <?php if (!empty($row['direksi'])): ?>
                                        <option value="<?= $row['direksi']; ?>"><?= $row['direksi']; ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="">Tidak ada</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="hubdirdir">Input Keuangan Dengan Anggota Direksi Lain di BPR </label>
                        <input type="text" name="hubdirdir" id="hubdirdir" class="form-control">
                        <small>Note: Diisi dengan format Nama - Hubungan Keuangan / Tidak Ada</small>
                    </div>
                    <div class="form-group">
                        <label for="hubdirdekom">Input Keuangan Dengan Anggota Dewan Komisaris Lain di BPR: </label>
                        <input type="text" name="hubdirdekom" id="hubdirdekom" class="form-control">
                        <small>Note: Diisi dengan format Nama - Hubungan Keuangan / Tidak Ada</small>
                    </div>
                    <div class="form-group">
                        <label for="hubdirpshm">Input Keuangan Dengan Pemegang Saham Lain di BPR: </label>
                        <input type="text" name="hubdirpshm" id="hubdirpshm" class="form-control">
                        <small>Note: Diisi dengan format Nama - Hubungan Keuangan / Tidak Ada</small>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" name="tambahuangdir" class="btn btn-primary">Tambah Data</button>
            </div>
            </form>
        </div>
    </div>
</div>

<!-- Add your modalTambah code here -->
<div class="modal fade" id="modalTambahuangdekom">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Kepemilikan Saham Anggota Dewan Komisaris pada BPR </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('keuangandirdekompshm/tambahuangdekom'); ?>" method="post">
                    <div class="form-group">
                        <label for="dekom">Input Nama Dewan Komisaris:</label>
                        <label for="dekom">Input Nama Dewan Komisaris:</label>
                        <select name="dekom" id="dekom" class="form-control">
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
                        <label for="hubdekomdir">Input Keuangan Dengan Anggota Direksi Lain di BPR: </label>
                        <input type="text" name="hubdekomdir" id="hubdekomdir" class="form-control">
                        <small>Note: Diisi dengan format Nama - Hubungan Keuangan / Tidak Ada</small>
                    </div>
                    <div class="form-group">
                        <label for="hubdekomdekom">Input Keuangan Dengan Anggota Dewan Komisaris Lain di
                            BPR:</label>
                        <input type="text" name="hubdekomdekom" id="hubdekomdekom" class="form-control">
                        <small>Note: Diisi dengan format Nama - Hubungan Keuangan / Tidak Ada</small>
                    </div>
                    <div class="form-group">
                        <label for="hubdekompshm">Input Keuangan Dengan Pemegang Saham Lain di BPR: </label>
                        <input type="text" name="hubdekompshm" id="hubdekompshm" class="form-control">
                        <small>Note: Diisi dengan format Nama - Hubungan Keuangan / Tidak Ada</small>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" name="tambahuangdekom" class="btn btn-primary">Tambah Data</button>
            </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambahuangpshm">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Hubungan Keuangan Pemegang Saham pada BPR </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('keuangandirdekompshm/tambahuangpshm'); ?>" method="post">
                    <div class="form-group">
                        <label for="pshm">Input Nama Pemegang Saham:</label>
                        <select name="pshm" id="pshm" class="form-control">
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
                        <label for="hubpshmdir">Input Hubungan Keuangan Dengan Anggota Direksi Lain di BPR: </label>
                        <input type="text" name="hubpshmdir" id="hubpshmdir" class="form-control">
                        <small>Note: Diisi dengan format Nama - Hubungan Keuangan / Tidak Ada</small>
                    </div>
                    <div class="form-group">
                        <label for="hubpshmdekom">Input Hubungan Keuangan Dengan Anggota Dewan Komisaris Lain di
                            BPR:</label>
                        <input type="text" name="hubpshmdekom" id="hubpshmdekom" class="form-control">
                        <small>Note: Diisi dengan format Nama - Hubungan Keuangan / Tidak Ada</small>
                    </div>
                    <div class="form-group">
                        <label for="hubpshmpshm">Input Hubungan Keuangan Dengan Pemegang Saham Lain di BPR: </label>
                        <input type="text" name="hubpshmpshm" id="hubpshmpshm" class="form-control">
                        <small>Note: Diisi dengan format Nama - Hubungan Keuangan / Tidak Ada</small>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" name="tambahuangpshm" class="btn btn-primary">Tambah Data</button>
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
                <form action="<?= base_url('keuangandirdekompshm/tambahkomentar'); ?>" method="post">
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
                            <a href="<?= base_url('keuangandirdekompshm/approveSemua') ?>"
                                class="btn btn-success shadow mt-3 mx-2"
                                onclick="return confirm('Apakah Anda yakin hendak melakukan approval?');">
                                Approve
                            </a>
                            <a href="<?= base_url('keuangandirdekompshm/unapproveSemua') ?>"
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
<div class="modal fade" id="modalHapuskeuangandirdekompshm">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                Apakah anda yakin ingin menghapus data ini?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btnHapuskeuangandirdekompshm">Yakin</button>
            </div>
        </div>
    </div>
</div>