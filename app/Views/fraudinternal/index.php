<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-4 text-gray-800"><?= $judul; ?></h1>
        <span>
            <?php
            $allApproved = true;
            foreach ($fraudinternal as $item) {
                if ($item['is_approved'] != 1) {
                    $allApproved = false;
                    break;
                }
            }
            ?>
            <?php if ($allApproved): ?>
                <span class="badge badge-success" style="font-size: 14px;">
                    Disetujui oleh <strong><?= esc($fullname ?? '-') ?></strong><br>
                    <?= esc($fraudinternal[0]['approved_at'] ?? '-') ?>
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
                        data-target="#modalTambahfrauddir"><i class="fa fa-plus"> Tambah Data</i></button>
                </div>
                <div class="col-md">
                    <!-- <button onclick="window.print()"
                    class="btn btn-outline-secondary shadow float-right mr-3 ml-2 mt-3">Print <i
                        class="fa fa-print"></i> </button>
                <a href="/fraudinternal/excel" class="btn btn-outline-success shadow float-right mt-3">Excel <i
                        class="fa fa-file-excel"></i></a> -->
                    <a href="/fraudinternal/exporttxtfraudinternal"
                        class="btn btn-outline-secondary shadow float-right mr-3 ml-2 mt-3">Export TXT <i
                            class="fa fa-file-alt"></i></a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <table class="table table-primary">
            <th>1.1. Jumlah Penyimpangan Internal oleh Anggota Direksi</th>
        </table>
        <table class="table table-bordered table-secondary">
            <tbody>
                <?php if (empty($fraudinternal)) { ?>
                    <tr>
                        <th style="width: 25%;">Total Fraud Pada Tahun Laporan :</th>
                        <td colspan="2">0 Kasus</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Total Fraud Pada Tahun Sebelumnya:</th>
                        <td colspan="2">0 Kasus</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Telah Diselesaikan Pada Tahun Laporan :</th>
                        <td colspan="2">0 Kasus</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Dalam Proses Penyelesaian Pada Tahun Laporan:</th>
                        <td colspan="2">0 Kasus</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Dalam Proses Penyelesaian Pada Tahun Sebelumnya:</th>
                        <td colspan="2">0 Kasus</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Belum Diupayakan Penyelesaiannya Pada Tahun Laporan:</th>
                        <td colspan="2">0 Kasus</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Belum Diupayakan Penyelesaiannya Pada Tahun Sebelumnya:</th>
                        <td colspan="2">0 Kasus</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Telah ditindaklanjuti Melalui Proses Hukum Pada Tahun Laporan:</th>
                        <td colspan="2">0 Kasus</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($fraudinternal as $row): ?>
                        <?php if (
                            !empty($row['fraudtahunlaporandir']) ||
                            !empty($row['fraudtahunsebelumdir']) ||
                            !empty($row['selesaitahunlaporandir']) ||
                            !empty($row['prosestahunlaporandir']) ||
                            !empty($row['prosestahunsebelumdir']) ||
                            !empty($row['belumtahunlaporandir']) ||
                            !empty($row['belumtahunsebelumdir']) ||
                            !empty($row['hukumtahunlaporandir'])
                        ): ?>
                            <tr>
                                <th style="width: 25%;">Total Fraud Pada Tahun Laporan :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['fraudtahunlaporandir']) ? $row['fraudtahunlaporandir'] : '0'; ?> Kasus
                                </td>
                            </tr>
                            <tr>
                                <th style="width: 25%;">Total Fraud Pada Tahun Sebelumnya :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['fraudtahunsebelumdir']) ? $row['fraudtahunsebelumdir'] : '0'; ?> Kasus
                                </td>
                            </tr>
                            <tr>
                                <th style="width: 25%;">Telah Diselesaikan Pada Tahun Laporan :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['selesaitahunlaporandir']) ? $row['selesaitahunlaporandir'] : '0'; ?>
                                    Kasus
                                </td>
                            </tr>
                            <tr>
                                <th style="width: 25%;">Dalam Proses Penyelesaian Pada Tahun Laporan :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['prosestahunlaporandir']) ? $row['prosestahunlaporandir'] : '0'; ?>
                                    Kasus
                                </td>
                            </tr>
                            <?php if (!empty($row['prosestahunsebelumdir'])): ?>
                                <tr>
                                    <th style="width: 25%;">Dalam Proses Penyelesaian Pada Tahun Sebelumnya :</th>
                                    <td style="width: 75%;"><?= $row['prosestahunsebelumdir']; ?> Kasus</td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <th style="width: 25%;">Belum Diupayakan Penyelesaiannya Pada Tahun Laporan :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['belumtahunlaporandir']) ? $row['belumtahunlaporandir'] : '0'; ?> Kasus
                                </td>
                            </tr>
                            <?php if (!empty($row['belumtahunsebelumdir'])): ?>
                                <tr>
                                    <th style="width: 25%;">Belum Diupayakan Penyelesaiannya Pada Tahun Sebelumnya :</th>
                                    <td style="width: 75%;"><?= $row['belumtahunsebelumdir']; ?> Kasus</td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <th style="width: 25%;">Telah ditindaklanjuti Melalui Proses Hukum Pada Tahun Laporan :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['hukumtahunlaporandir']) ? $row['hukumtahunlaporandir'] : '0'; ?> Kasus
                                </td>
                            </tr>
                            <tr>
                                <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                    <td colspan="3">
                                        <button type="button" data-toggle="modal" class="btn" data-target="#modalUbahfrauddir"
                                            id="btn-edit" style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                            data-fraudtahunlaporandir="<?= $row['fraudtahunlaporandir']; ?>"
                                            data-fraudtahunsebelumdir="<?= $row['fraudtahunsebelumdir']; ?>"
                                            data-selesaitahunlaporandir="<?= $row['selesaitahunlaporandir']; ?>"
                                            data-prosestahunlaporandir="<?= $row['prosestahunlaporandir']; ?>"
                                            data-prosestahunsebelumdir="<?= $row['prosestahunsebelumdir']; ?>"
                                            data-belumtahunlaporandir="<?= $row['belumtahunlaporandir']; ?>"
                                            data-belumtahunsebelumdir="<?= $row['belumtahunsebelumdir']; ?>"
                                            data-hukumtahunlaporandir="<?= $row['hukumtahunlaporandir']; ?>"><i
                                                class="fa fa-edit"></i>&nbsp;
                                        </button>
                                        <button type="button" data-toggle="modal" data-target="#modalHapusfraudinternal" id="btn-hapus"
                                            class="btn" style="font-weight: 600;" data-id="<?= $row['id']; ?>">
                                            <i class="fa fa-trash"></i>&nbsp;</button>
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
    </div>
</div>

<div class="card">
    <div class="card-reader">
        <?php if ($userInGroupPE || $userInGroupAdmin): ?>
            <div class="row">
                <div class="col-md">
                    <button type="button" data-toggle="modal" class="btn btn-primary ml-3 mt-3"
                        data-target="#modalTambahfrauddekom"><i class="fa fa-plus"> Tambah Data</i></button>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <table class="table table-primary">
            <th>1.2. Jumlah Penyimpangan Internal oleh Dewan Komisaris</th>
        </table>
        <table class="table table-bordered table-secondary">
            <tbody>
                <?php if (empty($fraudinternal)) { ?>
                    <tr>
                        <th style="width: 25%;">Total Fraud Pada Tahun Laporan :</th>
                        <td colspan="2">0 Kasus</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Total Fraud Pada Tahun Sebelumnya:</th>
                        <td colspan="2">0 Kasus</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Telah Diselesaikan Pada Tahun Laporan :</th>
                        <td colspan="2">0 Kasus</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Dalam Proses Penyelesaian Pada Tahun Laporan:</th>
                        <td colspan="2">0 Kasus</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Dalam Proses Penyelesaian Pada Tahun Sebelumnya:</th>
                        <td colspan="2">0 Kasus</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Belum Diupayakan Penyelesaiannya Pada Tahun Laporan:</th>
                        <td colspan="2">0 Kasus</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Belum Diupayakan Penyelesaiannya Pada Tahun Sebelumnya:</th>
                        <td colspan="2">0 Kasus</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Telah ditindaklanjuti Melalui Proses Hukum Pada Tahun Laporan:</th>
                        <td colspan="2">0 Kasus</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($fraudinternal as $row): ?>
                        <?php if (
                            !empty($row['fraudtahunlaporandekom']) ||
                            !empty($row['fraudtahunsebelumdekom']) ||
                            !empty($row['selesaitahunlaporandekom']) ||
                            !empty($row['prosestahunlaporandekom']) ||
                            !empty($row['prosestahunsebelumdekom']) ||
                            !empty($row['belumtahunlaporandekom']) ||
                            !empty($row['belumtahunsebelumdekom']) ||
                            !empty($row['hukumtahunlaporandekom'])
                        ): ?>
                            <tr>
                                <th style="width: 25%;">Total Fraud Pada Tahun Laporan :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['fraudtahunlaporandekom']) ? $row['fraudtahunlaporandekom'] : '0'; ?> Kasus
                                </td>
                            </tr>
                            <tr>
                                <th style="width: 25%;">Total Fraud Pada Tahun Sebelumnya :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['fraudtahunsebelumdekom']) ? $row['fraudtahunsebelumdekom'] : '0'; ?> Kasus
                                </td>
                            </tr>
                            <tr>
                                <th style="width: 25%;">Telah Diselesaikan Pada Tahun Laporan :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['selesaitahunlaporandekom']) ? $row['selesaitahunlaporandekom'] : '0'; ?>
                                    Kasus
                                </td>
                            </tr>
                            <tr>
                                <th style="width: 25%;">Dalam Proses Penyelesaian Pada Tahun Laporan :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['prosestahunlaporandekom']) ? $row['prosestahunlaporandekom'] : '0'; ?>
                                    Kasus
                                </td>
                            </tr>
                            <?php if (!empty($row['prosestahunsebelumdekom'])): ?>
                                <tr>
                                    <th style="width: 25%;">Dalam Proses Penyelesaian Pada Tahun Sebelumnya :</th>
                                    <td style="width: 75%;"><?= $row['prosestahunsebelumdekom']; ?> Kasus</td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <th style="width: 25%;">Belum Diupayakan Penyelesaiannya Pada Tahun Laporan :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['belumtahunlaporandekom']) ? $row['belumtahunlaporandekom'] : '0'; ?> Kasus
                                </td>
                            </tr>
                            <?php if (!empty($row['belumtahunsebelumdekom'])): ?>
                                <tr>
                                    <th style="width: 25%;">Belum Diupayakan Penyelesaiannya Pada Tahun Sebelumnya :</th>
                                    <td style="width: 75%;"><?= $row['belumtahunsebelumdekom']; ?> Kasus</td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <th style="width: 25%;">Telah ditindaklanjuti Melalui Proses Hukum Pada Tahun Laporan :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['hukumtahunlaporandekom']) ? $row['hukumtahunlaporandekom'] : '0'; ?> Kasus
                                </td>
                            </tr>
                            <tr>
                                <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                    <td colspan="3">
                                        <button type="button" data-toggle="modal" class="btn" data-target="#modalUbahfrauddekom"
                                            id="btn-edit" style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                            data-fraudtahunlaporandekom="<?= $row['fraudtahunlaporandekom']; ?>"
                                            data-fraudtahunsebelumdekom="<?= $row['fraudtahunsebelumdekom']; ?>"
                                            data-selesaitahunlaporandekom="<?= $row['selesaitahunlaporandekom']; ?>"
                                            data-prosestahunlaporandekom="<?= $row['prosestahunlaporandekom']; ?>"
                                            data-prosestahunsebelumdekom="<?= $row['prosestahunsebelumdekom']; ?>"
                                            data-belumtahunlaporandekom="<?= $row['belumtahunlaporandekom']; ?>"
                                            data-belumtahunsebelumdekom="<?= $row['belumtahunsebelumdekom']; ?>"
                                            data-hukumtahunlaporandekom="<?= $row['hukumtahunlaporandekom']; ?>"><i
                                                class="fa fa-edit"></i>&nbsp;
                                        </button>
                                        <button type="button" data-toggle="modal" data-target="#modalHapusfraudinternal" id="btn-hapus"
                                            class="btn" style="font-weight: 600;" data-id="<?= $row['id']; ?>">
                                            <i class="fa fa-trash"></i>&nbsp;</button>
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
    </div>
</div>

<div class="card">
    <div class="card-reader">
        <?php if ($userInGroupPE || $userInGroupAdmin): ?>
            <div class="row">
                <div class="col-md">
                    <button type="button" data-toggle="modal" class="btn btn-primary ml-3 mt-3"
                        data-target="#modalTambahfraudkartap"><i class="fa fa-plus"> Tambah Data</i></button>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <table class="table table-primary">
            <th>1.3. Jumlah Penyimpangan Internal oleh Pegawai Tidak Tetap</th>
        </table>
        <table class="table table-bordered table-secondary">
            <tbody>
                <?php if (empty($fraudinternal)) { ?>
                    <tr>
                        <th style="width: 25%;">Total Fraud Pada Tahun Laporan :</th>
                        <td colspan="2">0 Kasus</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Total Fraud Pada Tahun Sebelumnya:</th>
                        <td colspan="2">0 Kasus</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Telah Diselesaikan Pada Tahun Laporan :</th>
                        <td colspan="2">0 Kasus</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Dalam Proses Penyelesaian Pada Tahun Laporan:</th>
                        <td colspan="2">0 Kasus</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Dalam Proses Penyelesaian Pada Tahun Sebelumnya:</th>
                        <td colspan="2">0 Kasus</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Belum Diupayakan Penyelesaiannya Pada Tahun Laporan:</th>
                        <td colspan="2">0 Kasus</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Belum Diupayakan Penyelesaiannya Pada Tahun Sebelumnya:</th>
                        <td colspan="2">0 Kasus</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Telah ditindaklanjuti Melalui Proses Hukum Pada Tahun Laporan:</th>
                        <td colspan="2">0 Kasus</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($fraudinternal as $row): ?>
                        <?php if (
                            !empty($row['fraudtahunlaporankartap']) ||
                            !empty($row['fraudtahunsebelumkartap']) ||
                            !empty($row['selesaitahunlaporankartap']) ||
                            !empty($row['prosestahunlaporankartap']) ||
                            !empty($row['prosestahunsebelumkartap']) ||
                            !empty($row['belumtahunlaporankartap']) ||
                            !empty($row['belumtahunsebelumkartap']) ||
                            !empty($row['hukumtahunlaporankartap'])
                        ): ?>
                            <tr>
                                <th style="width: 25%;">Total Fraud Pada Tahun Laporan :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['fraudtahunlaporankartap']) ? $row['fraudtahunlaporankartap'] : '0'; ?>
                                    Kasus
                                </td>
                            </tr>
                            <tr>
                                <th style="width: 25%;">Total Fraud Pada Tahun Sebelumnya :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['fraudtahunsebelumkartap']) ? $row['fraudtahunsebelumkartap'] : '0'; ?>
                                    Kasus
                                </td>
                            </tr>
                            <tr>
                                <th style="width: 25%;">Telah Diselesaikan Pada Tahun Laporan :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['selesaitahunlaporankartap']) ? $row['selesaitahunlaporankartap'] : '0'; ?>
                                    Kasus
                                </td>
                            </tr>
                            <tr>
                                <th style="width: 25%;">Dalam Proses Penyelesaian Pada Tahun Laporan :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['prosestahunlaporankartap']) ? $row['prosestahunlaporankartap'] : '0'; ?>
                                    Kasus
                                </td>
                            </tr>
                            <?php if (!empty($row['prosestahunsebelumkartap'])): ?>
                                <tr>
                                    <th style="width: 25%;">Dalam Proses Penyelesaian Pada Tahun Sebelumnya :</th>
                                    <td style="width: 75%;"><?= $row['prosestahunsebelumkartap']; ?> Kasus</td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <th style="width: 25%;">Belum Diupayakan Penyelesaiannya Pada Tahun Laporan :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['belumtahunlaporankartap']) ? $row['belumtahunlaporankartap'] : '0'; ?>
                                    Kasus
                                </td>
                            </tr>
                            <?php if (!empty($row['belumtahunsebelumkartap'])): ?>
                                <tr>
                                    <th style="width: 25%;">Belum Diupayakan Penyelesaiannya Pada Tahun Sebelumnya :</th>
                                    <td style="width: 75%;"><?= $row['belumtahunsebelumkartap']; ?> Kasus</td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <th style="width: 25%;">Telah ditindaklanjuti Melalui Proses Hukum Pada Tahun Laporan :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['hukumtahunlaporankartap']) ? $row['hukumtahunlaporankartap'] : '0'; ?>
                                    Kasus
                                </td>
                            </tr>
                            <tr>
                                <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                    <td colspan="3">
                                        <button type="button" data-toggle="modal" class="btn" data-target="#modalUbahfraudkartap"
                                            id="btn-edit" style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                            data-fraudtahunlaporankartap="<?= $row['fraudtahunlaporankartap']; ?>"
                                            data-fraudtahunsebelumkartap="<?= $row['fraudtahunsebelumkartap']; ?>"
                                            data-selesaitahunlaporankartap="<?= $row['selesaitahunlaporankartap']; ?>"
                                            data-prosestahunlaporankartap="<?= $row['prosestahunlaporankartap']; ?>"
                                            data-prosestahunsebelumkartap="<?= $row['prosestahunsebelumkartap']; ?>"
                                            data-belumtahunlaporankartap="<?= $row['belumtahunlaporankartap']; ?>"
                                            data-belumtahunsebelumkartap="<?= $row['belumtahunsebelumkartap']; ?>"
                                            data-hukumtahunlaporankartap="<?= $row['hukumtahunlaporankartap']; ?>"><i
                                                class="fa fa-edit"></i>&nbsp;
                                        </button>
                                        <button type="button" data-toggle="modal" data-target="#modalHapusfraudinternal" id="btn-hapus"
                                            class="btn" style="font-weight: 600;" data-id="<?= $row['id']; ?>">
                                            <i class="fa fa-trash"></i>&nbsp;</button>
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

    <div class="card">
        <div class="card-reader">
            <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                <div class="row">
                    <div class="col-md">
                        <button type="button" data-toggle="modal" class="btn btn-primary ml-3 mt-3"
                            data-target="#modalTambahfraudkontrak"><i class="fa fa-plus"> Tambah Data</i></button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <table class="table table-primary">
                <th>1.4. Jumlah Penyimpangan Internal oleh Pegawai Tidak Tetap</th>
            </table>
            <table class="table table-bordered table-secondary">
                <tbody>
                    <?php if (empty($fraudinternal)) { ?>
                        <tr>
                            <th style="width: 25%;">Total Fraud Pada Tahun Laporan :</th>
                            <td colspan="2">0 Kasus</td>
                        </tr>
                        <tr>
                            <th style="width: 25%;">Total Fraud Pada Tahun Sebelumnya:</th>
                            <td colspan="2">0 Kasus</td>
                        </tr>
                        <tr>
                            <th style="width: 25%;">Telah Diselesaikan Pada Tahun Laporan :</th>
                            <td colspan="2">0 Kasus</td>
                        </tr>
                        <tr>
                            <th style="width: 25%;">Dalam Proses Penyelesaian Pada Tahun Laporan:</th>
                            <td colspan="2">0 Kasus</td>
                        </tr>
                        <tr>
                            <th style="width: 25%;">Dalam Proses Penyelesaian Pada Tahun Sebelumnya:</th>
                            <td colspan="2">0 Kasus</td>
                        </tr>
                        <tr>
                            <th style="width: 25%;">Belum Diupayakan Penyelesaiannya Pada Tahun Laporan:</th>
                            <td colspan="2">0 Kasus</td>
                        </tr>
                        <tr>
                            <th style="width: 25%;">Belum Diupayakan Penyelesaiannya Pada Tahun Sebelumnya:</th>
                            <td colspan="2">0 Kasus</td>
                        </tr>
                        <tr>
                            <th style="width: 25%;">Telah ditindaklanjuti Melalui Proses Hukum Pada Tahun Laporan:</th>
                            <td colspan="2">0 Kasus</td>
                        </tr>
                    <?php } else { ?>
                        <?php foreach ($fraudinternal as $row): ?>
                            <?php if (
                                !empty($row['fraudtahunlaporankontrak']) ||
                                !empty($row['fraudtahunsebelumkontrak']) ||
                                !empty($row['selesaitahunlaporankontrak']) ||
                                !empty($row['prosestahunlaporankontrak']) ||
                                !empty($row['prosestahunsebelumkontrak']) ||
                                !empty($row['belumtahunlaporankontrak']) ||
                                !empty($row['belumtahunsebelumkontrak']) ||
                                !empty($row['hukumtahunlaporankontrak'])
                            ): ?>
                                <tr>
                                    <th style="width: 25%;">Total Fraud Pada Tahun Laporan :</th>
                                    <td style="width: 75%;">
                                        <?= !empty($row['fraudtahunlaporankontrak']) ? $row['fraudtahunlaporankontrak'] : '0'; ?>
                                        Kasus
                                    </td>
                                </tr>
                                <tr>
                                    <th style="width: 25%;">Total Fraud Pada Tahun Sebelumnya :</th>
                                    <td style="width: 75%;">
                                        <?= !empty($row['fraudtahunsebelumkontrak']) ? $row['fraudtahunsebelumkontrak'] : '0'; ?>
                                        Kasus
                                    </td>
                                </tr>
                                <tr>
                                    <th style="width: 25%;">Telah Diselesaikan Pada Tahun Laporan :</th>
                                    <td style="width: 75%;">
                                        <?= !empty($row['selesaitahunlaporankontrak']) ? $row['selesaitahunlaporankontrak'] : '0'; ?>
                                        Kasus
                                    </td>
                                </tr>
                                <tr>
                                    <th style="width: 25%;">Dalam Proses Penyelesaian Pada Tahun Laporan :</th>
                                    <td style="width: 75%;">
                                        <?= !empty($row['prosestahunlaporankontrak']) ? $row['prosestahunlaporankontrak'] : '0'; ?>
                                        Kasus
                                    </td>
                                </tr>
                                <?php if (!empty($row['prosestahunsebelumkontrak'])): ?>
                                    <tr>
                                        <th style="width: 25%;">Dalam Proses Penyelesaian Pada Tahun Sebelumnya :</th>
                                        <td style="width: 75%;"><?= $row['prosestahunsebelumkontrak']; ?> Kasus</td>
                                    </tr>
                                <?php endif; ?>
                                <tr>
                                    <th style="width: 25%;">Belum Diupayakan Penyelesaiannya Pada Tahun Laporan :</th>
                                    <td style="width: 75%;">
                                        <?= !empty($row['belumtahunlaporankontrak']) ? $row['belumtahunlaporankontrak'] : '0'; ?>
                                        Kasus
                                    </td>
                                </tr>
                                <?php if (!empty($row['belumtahunsebelumkontrak'])): ?>
                                    <tr>
                                        <th style="width: 25%;">Belum Diupayakan Penyelesaiannya Pada Tahun Sebelumnya :</th>
                                        <td style="width: 75%;"><?= $row['belumtahunsebelumkontrak']; ?> Kasus</td>
                                    </tr>
                                <?php endif; ?>
                                <tr>
                                    <th style="width: 25%;">Telah ditindaklanjuti Melalui Proses Hukum Pada Tahun Laporan :</th>
                                    <td style="width: 75%;">
                                        <?= !empty($row['hukumtahunlaporankontrak']) ? $row['hukumtahunlaporankontrak'] : '0'; ?>
                                        Kasus
                                    </td>
                                </tr>
                                <tr>
                                    <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                        <td colspan="3">
                                            <button type="button" data-toggle="modal" class="btn" data-target="#modalUbahfraudkontrak"
                                                id="btn-edit" style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                                data-fraudtahunlaporankontrak="<?= $row['fraudtahunlaporankontrak']; ?>"
                                                data-fraudtahunsebelumkontrak="<?= $row['fraudtahunsebelumkontrak']; ?>"
                                                data-selesaitahunlaporankontrak="<?= $row['selesaitahunlaporankontrak']; ?>"
                                                data-prosestahunlaporankontrak="<?= $row['prosestahunlaporankontrak']; ?>"
                                                data-prosestahunsebelumkontrak="<?= $row['prosestahunsebelumkontrak']; ?>"
                                                data-belumtahunlaporankontrak="<?= $row['belumtahunlaporankontrak']; ?>"
                                                data-belumtahunsebelumkontrak="<?= $row['belumtahunsebelumkontrak']; ?>"
                                                data-hukumtahunlaporankontrak="<?= $row['hukumtahunlaporankontrak']; ?>"><i
                                                    class="fa fa-edit"></i>&nbsp;
                                            </button>
                                            <button type="button" data-toggle="modal" data-target="#modalHapusfraudinternal"
                                                id="btn-hapus" class="btn" style="font-weight: 600;" data-id="<?= $row['id']; ?>">
                                                <i class="fa fa-trash"></i>&nbsp;</button>
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
            <!-- <div class="card-reader">
                    <div class="row">
                        <div class="col-md">
                            <button type="button" data-toggle="modal" class="btn btn-primary ml-3 mt-3"
                                data-target="#modalUbahketerangan"><i class="fa fa-plus"> Tambah Keterangan</i></button>
                        </div>
                    </div>
                </div>
                <table style="border-collapse: collapse;">
                    <tbody>
                        <?php if (empty($fraudinternal)) { ?>
                            <tr>
                                <th>Keterangan:</th>
                                <td colspan="2">Data tidak tersedia</td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($fraudinternal as $row): ?>
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
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php } ?>
                    </tbody>
                </table> -->
        </div>
    </div>

</div>
<!-- End Page Content -->

<div class="modal fade" id="modalTambahfrauddir">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Jumlah Penyimpangan Internal oleh Anggota Direksi</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('fraudinternal/tambahfrauddir'); ?>" method="post">
                    <div class="form-group">
                        <label for="fraudtahunlaporandir">Input Total Fraud Pada Tahun Laporan:</label>
                        <input type="text" name="fraudtahunlaporandir" id="fraudtahunlaporandir" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="fraudtahunsebelumdir">Input Total Fraud Pada Tahun Sebelumnya: </label>
                        <input type="text" name="fraudtahunsebelumdir" id="fraudtahunsebelumdir" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="selesaitahunlaporandir">Input Telah Diselesaikan Pada Tahun Laporan : </label>
                        <input type="text" name="selesaitahunlaporandir" id="selesaitahunlaporandir"
                            class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="prosestahunlaporandir">Input Proses Penyelesaian Pada Tahun Laporan: </label>
                        <input type="text" name="prosestahunlaporandir" id="prosestahunlaporandir" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="prosestahunsebelumdir">Input Dalam Proses Penyelesaian Pada Tahun Sebelumnya:
                        </label>
                        <input type="text" name="prosestahunsebelumdir" id="prosestahunsebelumdir" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="belumtahunlaporandir">Input Belum Diupayakan Penyelesaiannya Pada Tahun Laporan:
                        </label>
                        <input type="text" name="belumtahunlaporandir" id="belumtahunlaporandir" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="belumtahunsebelumdir">Input Belum Diupayakan Penyelesaiannya Pada Tahun
                            Sebelumnya:
                        </label>
                        <input type="text" name="belumtahunsebelumdir" id="belumtahunsebelumdir" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="hukumtahunlaporandir">Input Telah Ditindaklanjuti Melalui Proses Hukum Pada
                            Tahun
                            Laporan: </label>
                        <input type="text" name="hukumtahunlaporandir" id="hukumtahunlaporandir" class="form-control" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="tambahfrauddir" class="btn btn-primary">Tambah Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!--edit data-->
<?php if (!empty($fraudinternal)) { ?>
    <div class="modal fade" id="modalUbahfrauddir">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Jumlah Penyimpangan Internal oleh Anggota Direksi </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('fraudinternal/ubahfrauddir'); ?>" method="post">
                        <input type="hidden" name="id" id="id-fraudinternal">
                        <div class="mb-3">
                            <label for="fraudtahunlaporandir">Ubah Total Fraud Pada Tahun Laporan:</label>
                            <input class="form-control" type="text" name="fraudtahunlaporandir" id="fraudtahunlaporandir"
                                placeholder="<?= $row['fraudtahunlaporandir'] ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="fraudtahunsebelumdir">Ubah Total Fraud Pada Tahun Sebelumnya:</label>
                            <input class="form-control" type="text" name="fraudtahunsebelumdir" id="fraudtahunsebelumdir"
                                placeholder="<?= $row['fraudtahunsebelumdir'] ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="selesaitahunlaporandir">Ubah Telah Diselesaikan Pada Tahun Laporan :</label>
                            <input class="form-control" type="text" name="selesaitahunlaporandir"
                                id="selesaitahunlaporandir" placeholder="<?= $row['selesaitahunlaporandir'] ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="prosestahunlaporandir">Ubah Dalam Proses Penyelesaian Pada Tahun
                                Laporan:</label>
                            <input class="form-control" type="text" name="prosestahunlaporandir" id="prosestahunlaporandir"
                                placeholder="<?= $row['prosestahunlaporandir'] ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="prosestahunsebelumdir">Ubah Dalam Proses Penyelesaian Pada Tahun
                                Sebelumnya:</label>
                            <input class="form-control" type="text" name="prosestahunsebelumdir" id="prosestahunsebelumdir"
                                placeholder="<?= $row['prosestahunsebelumdir'] ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="belumtahunlaporandir">Ubah Belum Diupayakan Penyelesaiannya Pada Tahun
                                Laporan:</label>
                            <input class="form-control" type="text" name="belumtahunlaporandir" id="belumtahunlaporandir"
                                placeholder="<?= $row['belumtahunlaporandir'] ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="belumtahunsebelumdir">Ubah Belum Diupayakan Penyelesaiannya Pada Tahun
                                Sebelumnya:</label>
                            <input class="form-control" type="text" name="belumtahunsebelumdir" id="belumtahunsebelumdir"
                                placeholder="<?= $row['belumtahunsebelumdir'] ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="hukumtahunlaporandir">Ubah Telah ditindaklanjuti Melalui Proses Hukum Pada Tahun
                                Laporan:</label>
                            <input class="form-control" type="text" name="hukumtahunlaporandir" id="hukumtahunlaporandir"
                                placeholder="<?= $row['hukumtahunlaporandir'] ?>" required>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" name="ubahfrauddir" class="btn btn-primary">Ubah Data</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php } ?>

<div class="modal fade" id="modalTambahfrauddekom">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Jumlah Penyimpangan Internal oleh Anggota Direksi (Dekompensasi)</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('fraudinternal/tambahfrauddekom'); ?>" method="post">
                    <div class="form-group">
                        <label for="fraudtahunlaporandekom">Input Total Fraud Pada Tahun Laporan:</label>
                        <input type="text" name="fraudtahunlaporandekom" id="fraudtahunlaporandekom"
                            class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="fraudtahunsebelumdekom">Input Total Fraud Pada Tahun Sebelumnya: </label>
                        <input type="text" name="fraudtahunsebelumdekom" id="fraudtahunsebelumdekom"
                            class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="selesaitahunlaporandekom">Input Telah Diselesaikan Pada Tahun Laporan : </label>
                        <input type="text" name="selesaitahunlaporandekom" id="selesaitahunlaporandekom"
                            class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="prosestahunlaporandekom">Input Proses Penyelesaian Pada Tahun Laporan: </label>
                        <input type="text" name="prosestahunlaporandekom" id="prosestahunlaporandekom"
                            class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="prosestahunsebelumdekom">Input Dalam Proses Penyelesaian Pada Tahun Sebelumnya:
                        </label>
                        <input type="text" name="prosestahunsebelumdekom" id="prosestahunsebelumdekom"
                            class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="belumtahunlaporandekom">Input Belum Diupayakan Penyelesaiannya Pada Tahun
                            Laporan:
                        </label>
                        <input type="text" name="belumtahunlaporandekom" id="belumtahunlaporandekom"
                            class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="belumtahunsebelumdekom">Input Belum Diupayakan Penyelesaiannya Pada Tahun
                            Sebelumnya:
                        </label>
                        <input type="text" name="belumtahunsebelumdekom" id="belumtahunsebelumdekom"
                            class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="hukumtahunlaporandekom">Input Telah Ditindaklanjuti Melalui Proses Hukum Pada
                            Tahun
                            Laporan: </label>
                        <input type="text" name="hukumtahunlaporandekom" id="hukumtahunlaporandekom"
                            class="form-control" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="tambahfrauddekom" class="btn btn-primary">Tambah Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($fraudinternal)) { ?>
    <div class="modal fade" id="modalUbahfrauddekom">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Jumlah Penyimpangan Internal oleh Anggota Direksi (Dekompensasi)</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('fraudinternal/ubahfrauddekom'); ?>" method="post">
                        <input type="hidden" name="id" id="id-fraudinternal">
                        <div class="mb-3">
                            <label for="fraudtahunlaporandekom">Ubah Total Fraud Pada Tahun Laporan:</label>
                            <input class="form-control" type="text" name="fraudtahunlaporandekom"
                                id="fraudtahunlaporandekom"
                                placeholder="<?= isset($row['fraudtahunlaporandekom']) ? $row['fraudtahunlaporandekom'] : '' ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="fraudtahunsebelumdekom">Ubah Total Fraud Pada Tahun Sebelumnya:</label>
                            <input class="form-control" type="text" name="fraudtahunsebelumdekom"
                                id="fraudtahunsebelumdekom"
                                placeholder="<?= isset($row['fraudtahunsebelumdekom']) ? $row['fraudtahunsebelumdekom'] : '' ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="selesaitahunlaporandekom">Ubah Telah Diselesaikan Pada Tahun Laporan :</label>
                            <input class="form-control" type="text" name="selesaitahunlaporandekom"
                                id="selesaitahunlaporandekom"
                                placeholder="<?= isset($row['selesaitahunlaporandekom']) ? $row['selesaitahunlaporandekom'] : '' ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="prosestahunlaporandekom">Ubah Dalam Proses Penyelesaian Pada Tahun
                                Laporan:</label>
                            <input class="form-control" type="text" name="prosestahunlaporandekom"
                                id="prosestahunlaporandekom"
                                placeholder="<?= isset($row['prosestahunlaporandekom']) ? $row['prosestahunlaporandekom'] : '' ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="prosestahunsebelumdekom">Ubah Dalam Proses Penyelesaian Pada Tahun
                                Sebelumnya:</label>
                            <input class="form-control" type="text" name="prosestahunsebelumdekom"
                                id="prosestahunsebelumdekom"
                                placeholder="<?= isset($row['prosestahunsebelumdekom']) ? $row['prosestahunsebelumdekom'] : '' ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="belumtahunlaporandekom">Ubah Belum Diupayakan Penyelesaiannya Pada Tahun
                                Laporan:</label>
                            <input class="form-control" type="text" name="belumtahunlaporandekom"
                                id="belumtahunlaporandekom"
                                placeholder="<?= isset($row['belumtahunlaporandekom']) ? $row['belumtahunlaporandekom'] : '' ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="belumtahunsebelumdekom">Ubah Belum Diupayakan Penyelesaiannya Pada Tahun
                                Sebelumnya:</label>
                            <input class="form-control" type="text" name="belumtahunsebelumdekom"
                                id="belumtahunsebelumdekom"
                                placeholder="<?= isset($row['belumtahunsebelumdekom']) ? $row['belumtahunsebelumdekom'] : '' ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="hukumtahunlaporandekom">Ubah Telah ditindaklanjuti Melalui Proses Hukum Pada
                                Tahun
                                Laporan:</label>
                            <input class="form-control" type="text" name="hukumtahunlaporandekom"
                                id="hukumtahunlaporandekom"
                                placeholder="<?= isset($row['hukumtahunlaporandekom']) ? $row['hukumtahunlaporandekom'] : '' ?>" required>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" name="ubahfrauddekom" class="btn btn-primary">Ubah Data</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php } ?>

<div class="modal fade" id="modalTambahfraudkartap">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Jumlah Penyimpangan Internal oleh Anggota Kartap</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('fraudinternal/tambahfraudkartap'); ?>" method="post">
                    <div class="form-group">
                        <label for="fraudtahunlaporankartap">Input Total Fraud Pada Tahun Laporan:</label>
                        <input type="text" name="fraudtahunlaporankartap" id="fraudtahunlaporankartap"
                            class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="fraudtahunsebelumkartap">Input Total Fraud Pada Tahun Sebelumnya: </label>
                        <input type="text" name="fraudtahunsebelumkartap" id="fraudtahunsebelumkartap"
                            class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="selesaitahunlaporankartap">Input Telah Diselesaikan Pada Tahun Laporan :
                        </label>
                        <input type="text" name="selesaitahunlaporankartap" id="selesaitahunlaporankartap"
                            class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="prosestahunlaporankartap">Input Proses Penyelesaian Pada Tahun Laporan: </label>
                        <input type="text" name="prosestahunlaporankartap" id="prosestahunlaporankartap"
                            class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="prosestahunsebelumkartap">Input Dalam Proses Penyelesaian Pada Tahun Sebelumnya:
                        </label>
                        <input type="text" name="prosestahunsebelumkartap" id="prosestahunsebelumkartap"
                            class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="belumtahunlaporankartap">Input Belum Diupayakan Penyelesaiannya Pada Tahun
                            Laporan:
                        </label>
                        <input type="text" name="belumtahunlaporankartap" id="belumtahunlaporankartap"
                            class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="belumtahunsebelumkartap">Input Belum Diupayakan Penyelesaiannya Pada Tahun
                            Laporan:
                        </label>
                        <input type="text" name="belumtahunsebelumkartap" id="belumtahunsebelumkartap"
                            class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="hukumtahunlaporankartap">Input Telah Ditindaklanjuti Melalui Proses Hukum Pada
                            Tahun
                            Laporan: </label>
                        <input type="text" name="hukumtahunlaporankartap" id="hukumtahunlaporankartap"
                            class="form-control" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="tambahfraudkartap" class="btn btn-primary">Tambah Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($fraudinternal)) { ?>
    <div class="modal fade" id="modalUbahfraudkartap">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Jumlah Penyimpangan Internal oleh Anggota Kartap </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('fraudinternal/ubahfraudkartap'); ?>" method="post">
                        <input type="hidden" name="id" id="id-fraudinternal">
                        <div class="mb-3">
                            <label for="fraudtahunlaporankartap">Ubah Total Fraud Pada Tahun Laporan:</label>
                            <input class="form-control" type="text" name="fraudtahunlaporankartap"
                                id="fraudtahunlaporankartap" placeholder="<?= $row['fraudtahunlaporankartap'] ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="fraudtahunsebelumkartap">Ubah Total Fraud Pada Tahun Sebelumnya:</label>
                            <input class="form-control" type="text" name="fraudtahunsebelumkartap"
                                id="fraudtahunsebelumkartap" placeholder="<?= $row['fraudtahunsebelumkartap'] ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="selesaitahunlaporankartap">Ubah Telah Diselesaikan Pada Tahun Laporan :</label>
                            <input class="form-control" type="text" name="selesaitahunlaporankartap"
                                id="selesaitahunlaporankartap" placeholder="<?= $row['selesaitahunlaporankartap'] ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="prosestahunlaporankartap">Ubah Dalam Proses Penyelesaian Pada Tahun
                                Laporan:</label>
                            <input class="form-control" type="text" name="prosestahunlaporankartap"
                                id="prosestahunlaporankartap" placeholder="<?= $row['prosestahunlaporankartap'] ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="prosestahunsebelumkartap">Ubah Dalam Proses Penyelesaian Pada Tahun
                                Sebelumnya:</label>
                            <input class="form-control" type="text" name="prosestahunsebelumkartap"
                                id="prosestahunsebelumkartap" placeholder="<?= $row['prosestahunsebelumkartap'] ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="belumtahunlaporankartap">Ubah Belum Diupayakan Penyelesaiannya Pada Tahun
                                Laporan:</label>
                            <input class="form-control" type="text" name="belumtahunlaporankartap"
                                id="belumtahunlaporankartap" placeholder="<?= $row['belumtahunlaporankartap'] ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="belumtahunsebelumkartap">Ubah Belum Diupayakan Penyelesaiannya Pada Tahun
                                Sebelumnya:</label>
                            <input class="form-control" type="text" name="belumtahunsebelumkartap"
                                id="belumtahunsebelumkartap" placeholder="<?= $row['belumtahunsebelumkartap'] ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="hukumtahunlaporankartap">Ubah Telah ditindaklanjuti Melalui Proses Hukum Pada
                                Tahun
                                Laporan:</label>
                            <input class="form-control" type="text" name="hukumtahunlaporankartap"
                                id="hukumtahunlaporankartap" placeholder="<?= $row['hukumtahunlaporankartap'] ?>" required>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" name="ubahfraudkartap" class="btn btn-primary">Ubah Data</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php } ?>

<div class="modal fade" id="modalTambahfraudkontrak">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Jumlah Penyimpangan Internal oleh Anggota Kontrak</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('fraudinternal/tambahfraudkontrak'); ?>" method="post">
                    <div class="form-group">
                        <label for="fraudtahunlaporankontrak">Input Total Fraud Pada Tahun Laporan:</label>
                        <input type="text" name="fraudtahunlaporankontrak" id="fraudtahunlaporankontrak"
                            class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="fraudtahunsebelumkontrak">Input Total Fraud Pada Tahun Sebelumnya: </label>
                        <input type="text" name="fraudtahunsebelumkontrak" id="fraudtahunsebelumkontrak"
                            class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="selesaitahunlaporankontrak">Input Telah Diselesaikan Pada Tahun Laporan :
                        </label>
                        <input type="text" name="selesaitahunlaporankontrak" id="selesaitahunlaporankontrak"
                            class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="prosestahunlaporankontrak">Input Proses Penyelesaian Pada Tahun Laporan:
                        </label>
                        <input type="text" name="prosestahunlaporankontrak" id="prosestahunlaporankontrak"
                            class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="prosestahunsebelumkontrak">Input Dalam Proses Penyelesaian Pada Tahun
                            Sebelumnya:
                        </label>
                        <input type="text" name="prosestahunsebelumkontrak" id="prosestahunsebelumkontrak"
                            class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="belumtahunlaporankontrak">Input Belum Diupayakan Penyelesaiannya Pada Tahun
                            Laporan:
                        </label>
                        <input type="text" name="belumtahunlaporankontrak" id="belumtahunlaporankontrak"
                            class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="belumtahunsebelumkontrak">Input Belum Diupayakan Penyelesaiannya Pada Tahun
                            Laporan:
                        </label>
                        <input type="text" name="belumtahunsebelumkontrak" id="belumtahunsebelumkontrak"
                            class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="hukumtahunlaporankontrak">Input Telah Ditindaklanjuti Melalui Proses Hukum Pada
                            Tahun
                            Laporan: </label>
                        <input type="text" name="hukumtahunlaporankontrak" id="hukumtahunlaporankontrak"
                            class="form-control" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="tambahfraudkontrak" class="btn btn-primary">Tambah Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($fraudinternal)) { ?>
    <div class="modal fade" id="modalUbahfraudkontrak">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Jumlah Penyimpangan Internal oleh Anggota Kontrak </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('fraudinternal/ubahfraudkontrak'); ?>" method="post">
                        <input type="hidden" name="id" id="id-fraudinternal">
                        <div class="mb-3">
                            <label for="fraudtahunlaporankontrak">Ubah Total Fraud Pada Tahun Laporan:</label>
                            <input class="form-control" type="text" name="fraudtahunlaporankontrak"
                                id="fraudtahunlaporankontrak" placeholder="" required>
                        </div>
                        <div class="mb-3">
                            <label for="fraudtahunsebelumkontrak">Ubah Total Fraud Pada Tahun Sebelumnya:</label>
                            <input class="form-control" type="text" name="fraudtahunsebelumkontrak"
                                id="fraudtahunsebelumkontrak" placeholder="" required>
                        </div>
                        <div class="mb-3">
                            <label for="selesaitahunlaporankontrak">Ubah Telah Diselesaikan Pada Tahun Laporan :</label>
                            <input class="form-control" type="text" name="selesaitahunlaporankontrak"
                                id="selesaitahunlaporankontrak" placeholder="" required>
                        </div>
                        <div class="mb-3">
                            <label for="prosestahunlaporankontrak">Ubah Dalam Proses Penyelesaian Pada Tahun
                                Laporan:</label>
                            <input class="form-control" type="text" name="prosestahunlaporankontrak"
                                id="prosestahunlaporankontrak" placeholder="" required>
                        </div>
                        <div class="mb-3">
                            <label for="prosestahunsebelumkontrak">Ubah Dalam Proses Penyelesaian Pada Tahun
                                Sebelumnya:</label>
                            <input class="form-control" type="text" name="prosestahunsebelumkontrak"
                                id="prosestahunsebelumkontrak" placeholder="" required>
                        </div>
                        <div class="mb-3">
                            <label for="belumtahunlaporankontrak">Ubah Belum Diupayakan Penyelesaiannya Pada Tahun
                                Laporan:</label>
                            <input class="form-control" type="text" name="belumtahunlaporankontrak"
                                id="belumtahunlaporankontrak" placeholder="" required>
                        </div>
                        <div class="mb-3">
                            <label for="belumtahunsebelumkontrak">Ubah Belum Diupayakan Penyelesaiannya Pada Tahun
                                Sebelumnya:</label>
                            <input class="form-control" type="text" name="belumtahunsebelumkontrak"
                                id="belumtahunsebelumkontrak" placeholder="" required>
                        </div>
                        <div class="mb-3">
                            <label for="hukumtahunlaporankontrak">Ubah Telah ditindaklanjuti Melalui Proses Hukum Pada
                                Tahun
                                Laporan:</label>
                            <input class="form-control" type="text" name="hukumtahunlaporankontrak"
                                id="hukumtahunlaporankontrak" placeholder="" required>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" name="ubahfraudkontrak" class="btn btn-primary">Ubah Data</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php } ?>

<div class="modal fade" id="modalTambahfraudket">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Keterangan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('fraudinternal/tambahfraudket'); ?>" method="post">
                    <div class="form-group">
                        <label for="keterangan">Input Keterangan:</label>
                        <textarea style="height: 150px" type="text" name="keterangan" id="keterangan"
                            class="form-control"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" name="tambahfraudket" class="btn btn-primary">Tambah Data</button>
            </div>
            </form>
        </div>
    </div>
</div>

<?php if (!empty($fraudinternal)) { ?>
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
                    <form action="<?= base_url('fraudinternal/ubahketerangan'); ?>" method="post">
                        <input type="hidden" name="id" id="id-fraudinternal">
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

<div class="modal fade" id="modalHapusfraudinternal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                Apakah anda yakin ingin menghapus data no-<span id="idData"></span>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btnHapusfraudinternal">Yakin</button>
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
                <form action="<?= base_url('fraudinternal/tambahkomentar'); ?>" method="post">
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
                            <a href="<?= base_url('fraudinternal/approveSemua') ?>"
                                class="btn btn-success shadow mt-3 mx-2"
                                onclick="return confirm('Apakah Anda yakin hendak melakukan approval?');">
                                Approve
                            </a>
                            <a href="<?= base_url('fraudinternal/unapproveSemua') ?>"
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