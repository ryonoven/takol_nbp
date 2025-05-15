<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-4 text-gray-800"><?= $judul; ?></h1>
        <span>
            <?php
            $allApproved = true;
            foreach ($tgjwbdir as $item) {
                if ($item['is_approved'] != 1) {
                    $allApproved = false;
                    break;
                }
            }
            ?>
            <?php if ($allApproved): ?>
                <span class="badge badge-success" style="font-size: 14px;">
                    Disetujui oleh <strong><?= esc($fullname ?? '-') ?></strong><br>
                    <?= esc($tgjwbdir[0]['approved_at'] ?? '-') ?>
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
                        data-target="#modalTambah"><i class="fa fa-plus"> Tambah Data</i></button>
                </div>
                <div class="col-md">
                    <!-- <button onclick="window.print()"
                    class="btn btn-outline-secondary shadow float-right mr-3 ml-2 mt-3">Print <i
                        class="fa fa-print"></i> </button>
                <a href="/tgjwbdir/excel" class="btn btn-outline-success shadow float-right mt-3">Excel <i
                        class="fa fa-file-excel"></i></a> -->
                    <a href="/tgjwbdir/exporttxttgjwbdir"
                        class="btn btn-outline-secondary shadow float-right mr-3 ml-2 mt-3">Export TXT <i
                            class="fa fa-file-alt"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <table class="table table-primary">
            <th>Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi</th>
        </table>
        <table class="table table-bordered table-secondary">
            <tbody>
                <?php if (empty($tgjwbdir)) { ?>
                    <tr>
                        <th style="width: 30%;">Nama Direksi :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">NIK :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Tugas tanggung jawab :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($tgjwbdir as $row): ?>
                        <tr>
                            <th style="width: 3%; background-color: #a3b6ee;" rowspan="5"><?= $row['id']; ?></th>
                            <th style="width: 25%;">Nama Direksi :</th>
                            <td style="width: 75%;"><?= $row['direksi']; ?></td>
                        </tr>
                        <tr>
                            <th style="width: 25%;">NIK :</th>
                            <td style="width: 75%;"><?= $row['nik']; ?></td>
                        </tr>
                        <tr>
                            <th colspan="2">Penjelasan Tugas dan tanggung jawab :</th>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <ol style="list-style: none;">
                                    <?php
                                    $tugastgjwbdir = explode("\n", $row['tugastgjwbdir']);
                                    foreach ($tugastgjwbdir as $poin) {
                                        echo '<li>' . htmlspecialchars(trim($poin)) . '</li>';
                                    }
                                    ?>
                                </ol>
                            </td>
                        </tr>
                        <tr>
                            <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                <td colspan="3">
                                    <button type="button" data-toggle="modal" class="btn" data-target="#modalUbah" id="btn-edit"
                                        style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                        data-direksi="<?= $row['direksi']; ?>" data-nik="<?= $row['nik']; ?>"
                                        data-tugastgjwbdir="<?= $row['tugastgjwbdir']; ?>"><i class="fa fa-edit"></i>&nbsp;
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
                    <?php endforeach; ?>
                <?php } ?>
            </tbody>
        </table>
        <table style="border-collapse: collapse;">
            <tbody>
                <?php if (empty($tgjwbdir)) { ?>
                    <tr>
                        <th>Tindak Lanjut Rekomendasi Dewan Direksi:</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($tgjwbdir as $row): ?>
                        <?php if ($row['id'] == 1): ?>
                            <tr>
                                <th>Tindak Lanjut Rekomendasi Dewan Direksi:</th>
                            </tr>
                            <tr>
                                <td>
                                    <ol style="list-style: none;">
                                        <?php
                                        $tindakdir = explode("\n", $row['tindakdir']);
                                        foreach ($tindakdir as $poin):
                                            ?>
                                            <li><?= htmlspecialchars(trim($poin)); ?></li>
                                        <?php endforeach; ?>
                                    </ol>
                                </td>
                            </tr>
                            <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                <button type="button" data-toggle="modal" class="btn btn-primary mt-3"
                                    data-target="#modalUbahketerangan" id="btn-edit" style="font-weight: 600;"
                                    data-id="<?= $row['id']; ?>" data-tindakdir="<?= $row['tindakdir']; ?>"><i class="fa fa-edit">
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

</div>
<!-- End Page Content -->

<!--edit data-->
<?php if (!empty($tgjwbdir)) { ?>
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
                    <form action="<?= base_url('tgjwbdir/ubah'); ?>" method="post">
                        <input type="hidden" name="id" id="id-tgjwbdir">
                        <div class="mb-3">
                            <label for="direksi" class="form-label">Input Nama Direksi:</label>
                            <input class="form-control" type="text" name="direksi" id="direksi"
                                value="<?= $row['direksi'] ?>">
                        </div>
                        <div class="mb-3">
                            <label for="direksi" class="form-label">Input NIK:</label>
                            <input class="form-control" type="text" name="nik" id="nik" value="<?= $row['nik'] ?>">
                        </div>
                        <div class="form-group">
                            <label for="tugastgjwbdir" class="form-label">Input Tugas dan Tanggung Jawab BPR: </label>
                            <textarea class="form-control" type="text" name="tugastgjwbdir" id="tugastgjwbdir"
                                style="height: 150px" placeholder="<?= $row['tugastgjwbdir'] ?>"></textarea>
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

<?php if (!empty($tgjwbdir)) { ?>
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
                    <form action="<?= base_url('tgjwbdir/ubahketerangan'); ?>" method="post">
                        <input type="hidden" name="id" id="id-tgjwbdir">
                        <div class="form-group">
                            <label for="tindakdir" class="form-label">Input Tindak Lanjut Direksi BPR: </label>
                            <textarea class="form-control" type="text" name="tindakdir" id="tindakdir" style="height: 150px"
                                placeholder="<?= $row['tindakdir'] ?>"></textarea>
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
<div class="modal fade" id="modalTambah">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('tgjwbdir/tambahtgjwbdir'); ?>" method="post">
                    <div class="form-group">
                        <label for="direksi">Input Nama Direksi:</label>
                        <input type="text" name="direksi" id="direksi" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="nik">Input NIK:</label>
                        <input type="text" name="nik" id="nik" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="tugastgjwbdir">Input Penjelasan Tugas dan tanggung jawab: </label>
                        <input type="text" name="tugastgjwbdir" id="tugastgjwbdir" class="form-control">
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" name="tambahtgjwbdir" class="btn btn-primary">Tambah Data</button>
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
                <form action="<?= base_url('tgjwbdir/tambahkomentar'); ?>" method="post">
                    <div class="form-group">
                        <label for="komentar">Komentar Direksi dan Dewan Komisaris:</label>
                        <textarea type="text" name="komentar" id="komentar" class="form-control"
                            style="height: 150px;"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="komentar">Input Komentar Direksi dan Dewan Komisaris:</label>
                        <textarea type="komentar" name="komentar" id="komentar" class="form-control"
                            style="height: 150px;"></textarea>
                    </div>
                    <?php if ($userInGroupAdmin || $userInGroupDekom || $userInGroupDireksi): ?>
                        <div class="col-md d-flex justify-content-center align-items-center" style="margin-top: 20px;">
                            <a href="<?= base_url('tgjwbdir/approveSemua') ?>" class="btn btn-success shadow mt-3 mx-2"
                                onclick="return confirm('Apakah Anda yakin hendak melakukan approval?');">
                                Approve
                            </a>
                            <a href="<?= base_url('tgjwbdir/unapproveSemua') ?>" class="btn btn-danger shadow mt-3 mx-2"
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
<div class="modal fade" id="modalHapusdir">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                Apakah anda yakin ingin menghapus data no-<span id="idData"></span>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btnHapusdir">Yakin</button>
            </div>
        </div>
    </div>
</div>