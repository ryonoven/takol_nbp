<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-4 text-gray-800"><?= $judul; ?></h1>
        <span>
            <?php
            $allApproved = true;
            foreach ($kehadirandekom as $item) {
                if ($item['is_approved'] != 1) {
                    $allApproved = false;
                    break;
                }
            }
            ?>
            <?php if ($allApproved): ?>
                <span class="badge badge-success" style="font-size: 14px;">
                    Disetujui oleh <strong><?= esc($fullname ?? '-') ?></strong><br>
                    <?= esc($kehadirandekom[0]['approved_at'] ?? '-') ?>
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
                        data-target="#modalTambahdekom"><i class="fa fa-plus"> Tambah Data</i></button>
                </div>
                <div class="col-md">
                    <!-- <button onclick="window.print()"
                    class="btn btn-outline-secondary shadow float-right mr-3 ml-2 mt-3">Print <i
                        class="fa fa-print"></i> </button>
                <a href="/kehadirandekom/excel" class="btn btn-outline-success shadow float-right mt-3">Excel <i
                        class="fa fa-file-excel"></i></a> -->
                    <a href="/kehadirandekom/exporttxtkehadirandekom"
                        class="btn btn-outline-secondary shadow float-right mr-3 ml-2 mt-3">Export TXT <i
                            class="fa fa-file-alt"></i></a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <table class="table table-primary">
            <th>Kehadiran Anggota Dewan Komisaris </th>
        </table>
        <table class="table table-bordered table-secondary">
            <tbody>
                <?php if (empty($kehadirandekom)) { ?>
                    <tr>
                        <th style="width: 30%;">Nama Anggota Dewan Komisaris :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Frekuensi Kehadiran (Fisik) :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Frekuensi Kehadiran (Telekonferensi) :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Tingkat Kehadiran (dalam %) :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($kehadirandekom as $row): ?>
                        <?php if ($row['dekom'] != NULL): ?>
                            <tr>
                                <th style="width: 3%; background-color: #a3b6ee; color: transparent;" rowspan="5">
                                    <?= $row['id']; ?>
                                </th>
                                <th style="width: 30%;">Nama Anggota Dewan Komisaris :</th>
                                <td style="width: 70%;"><?= $row['dekom']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 30%;">Frekuensi Kehadiran (Fisik) :</th>
                                <td style="width: 70%;"><?= $row['hadirfisik']; ?> Kali</td>
                            </tr>
                            <tr>
                                <th style="width: 30%;">Frekuensi Kehadiran (Telekonferensi) :</th>
                                <td style="width: 70%;"><?= $row['hadironline']; ?> Kali</td>
                            </tr>
                            <tr>
                                <th style="width: 30%;">Tingkat Kehadiran (dalam %) :</th>
                                <td style="width: 70%;"><?= $row['persen']; ?> %</td>
                            </tr>
                            <tr>
                                <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                    <td colspan="3">
                                        <button type="button" data-toggle="modal" class="btn" data-target="#modalUbahdekom"
                                            id="btn-edit" style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                            data-dekom="<?= $row['dekom']; ?>" data-hadirfisik="<?= $row['hadirfisik']; ?>"
                                            data-hadironline="<?= $row['hadironline']; ?>" data-persen="<?= $row['persen']; ?>"><i
                                                class="fa fa-edit"></i>&nbsp;
                                        </button>
                                        <button type="button" data-toggle="modal" data-target="#modalHapuskehadirandekom" id="btn-hapus"
                                            class="btn" style="font-weight: 600;" data-id="<?= $row['id']; ?>"> <i
                                                class="fa fa-trash"></i>&nbsp;</button>
                                    </td>
                                </tr>
                                <tr height="40">
                                    <td colspan="3" style="border-color: white; background-color: white;"></td>
                                </tr>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php } ?>
            </tbody>
        </table>
        <table style="border-collapse: collapse;">
            <tbody>
                <?php if (empty($kehadirandekom)) { ?>
                    <tr>
                        <th>Keterangan:</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($kehadirandekom as $row): ?>
                        <?php if ($row['id'] == 1): ?>
                            <tr>
                                <th>Keterangan:</th>
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
                                    data-target="#modalUbahketerangan" id="btn-edit-keterangan" style="font-weight: 600;"
                                    data-id="<?= $row['id']; ?>" data-keterangan="<?= htmlspecialchars($row['keterangan']); ?>"><i
                                        class="fa fa-edit">Keterangan</i>&nbsp;
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
</div>

<!--edit data-->
<?php if (!empty($kehadirandekom)) { ?>
    <div class="modal fade" id="modalUbahdekom">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Kehadiran Anggota Dewan Komisaris dalam Pelaksanaan Rapat dalam 1 Tahun
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="<?= base_url('kehadirandekom/ubahdekom'); ?>" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="id-kehadirandekom">
                        <div class="mb-3">
                            <label for="dekom" class="form-label">Input Nama Anggota Dewan Komisaris :</label>
                            <input class="form-control" type="text" name="dekom" id="dekom" value="<?= $row['dekom'] ?>"
                                readonly>
                        </div>
                        <div class="form-group">
                            <label for="hadirfisik" class="form-label">Frekuensi Kehadiran (Fisik): </label>
                            <input class="form-control" type="text" name="hadirfisik" id="hadirfisik"
                                value="<?= $row['hadirfisik'] ?>" required></input>
                        </div>
                        <div class="form-group">
                            <label for="hadironline" class="form-label">Frekuensi Kehadiran (Telekonferensi): </label>
                            <input class="form-control" type="text" name="hadironline" id="hadironline"
                                value="<?= $row['hadironline'] ?>" required></input>
                        </div>
                        <div class="form-group">
                            <label for="persen" class="form-label">Tingkat Kehadiran (dalam %): </label>
                            <input class="form-control" type="text" name="persen" id="persen"
                                value="<?= $row['persen'] ?>" required></input>
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

<?php if (!empty($kehadirandekom)) { ?>
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
                    <form action="<?= base_url('kehadirandekom/ubahketerangan'); ?>" method="post">
                        <input type="text" name="id" id="id-kehadirandekom">
                        <div class="form-group">
                            <label for="keterangan" class="form-label">Keterangan: </label>
                            <textarea class="form-control" type="text" name="keterangan" id="keterangan"
                                style="height: 150px" placeholder="<?= $row['keterangan'] ?>"></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" name="ubahketerangan" class="btn btn-primary">Ubah Data</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php } ?>

<!-- Add your modalTambah code here -->
<div class="modal fade" id="modalTambahdekom">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Kehadiran Anggota Dewan Komisaris dalam Pelaksanaan Rapat dalam 1 Tahun
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('kehadirandekom/tambahdekom'); ?>" method="post">
                    <div class="form-group">
                        <label for="dekom">Input Nama Dewan Komisaris:</label>
                        <select name="dekom" id="dekom" class="form-control" required>
                            <?php foreach ($tgjwbdekom as $row): ?>
                                <option value="<?= $row['dekom']; ?>"><?= $row['dekom']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="hadirfisik">Frekuensi Kehadiran (Fisik): </label>
                        <input type="text" name="hadirfisik" id="hadirfisik" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="hadironline">Frekuensi Kehadiran (Telekonferensi): </label>
                        <input type="text" name="hadironline" id="hadironline" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="persen">Tingkat Kehadiran (dalam %): </label>
                        <input type="text" name="persen" id="persen" class="form-control" required>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" name="tambahdekom" class="btn btn-primary">Tambah Data</button>
            </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Hapus data  -->
<div class="modal fade" id="modalHapuskehadirandekom">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                Apakah anda yakin ingin menghapus data ini?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btnHapuskehadirandekom">Yakin</button>
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
                <form action="<?= base_url('kehadirandekom/tambahkomentar'); ?>" method="post">
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
                            <a href="<?= base_url('kehadirandekom/approveSemua') ?>"
                                class="btn btn-success shadow mt-3 mx-2"
                                onclick="return confirm('Apakah Anda yakin hendak melakukan approval?');">
                                Approve
                            </a>
                            <a href="<?= base_url('kehadirandekom/unapproveSemua') ?>"
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