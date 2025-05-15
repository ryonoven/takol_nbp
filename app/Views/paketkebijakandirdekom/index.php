<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-4 text-gray-800"><?= $judul; ?></h1>
        <span>
            <?php
            $allApproved = true;
            foreach ($paketkebijakandirdekom as $item) {
                if ($item['is_approved'] != 1) {
                    $allApproved = false;
                    break;
                }
            }
            ?>
            <?php if ($allApproved): ?>
                <span class="badge badge-success" style="font-size: 14px;">
                    Disetujui oleh <strong><?= esc($fullname ?? '-') ?></strong><br>
                    <?= esc($paketkebijakandirdekom[0]['approved_at'] ?? '-') ?>
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
                        data-target="#modalTambahgaji"><i class="fa fa-plus"> Tambah Data</i></button>
                </div>
                <div class="col-md">
                    <!-- <button onclick="window.print()"
                        class="btn btn-outline-secondary shadow float-right mr-3 ml-2 mt-3">Print <i
                            class="fa fa-print"></i> </button>
                    <a href="/paketkebijakandirdekom/excel" class="btn btn-outline-success shadow float-right mt-3">Excel <i
                            class="fa fa-file-excel"></i></a> -->
                    <a href="/paketkebijakandirdekom/exporttxtpaketkebijakandirdekom"
                        class="btn btn-outline-secondary shadow float-right mr-3 ml-2 mt-3">Export TXT <i
                            class="fa fa-file-alt"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <table class="table table-primary">
            <th>1.1. Gaji Bagi Direksi dan Dewan Komisaris</th>
        </table>
        <table class="table table-bordered table-secondary">
            <tbody>
                <?php if (empty($paketkebijakandirdekom)) { ?>
                    <tr>
                        <th style="width: 25%;">Jumlah Direksi Penerima Gaji :</th>
                        <td colspan="2">0 Orang</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Jumlah Nominal Keseluruhan Gaji Direksi (Rp):</th>
                        <td colspan="2">Rp 0</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Jumlah Komisaris Penerima Gaji :</th>
                        <td colspan="2">0 Orang</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Jumlah Nominal Keseluruhan Gaji Komisaris (Rp):</th>
                        <td colspan="2">Rp 0</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($paketkebijakandirdekom as $row): ?>
                        <?php if (
                            !empty($row['penerimagajidir']) ||
                            !empty($row['nominalgajidir']) ||
                            !empty($row['penerimagajidekom']) ||
                            !empty($row['nominalgajidekom'])
                        ): ?>
                            <tr>
                                <th style="width: 25%;">Jumlah Direksi Penerima Gaji :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['penerimagajidir']) ? $row['penerimagajidir'] : '0'; ?> Orang
                                </td>
                            </tr>
                            <tr>
                                <th style="width: 25%;">Jumlah Nominal Keseluruhan Gaji Direksi (Rp) :</th>
                                <td style="width: 75%;">
                                    Rp
                                    <?= !empty($row['nominalgajidir']) ? number_format($row['nominalgajidir'], 0, ',', '.') : '0'; ?>
                                </td>
                            </tr>
                            <tr>
                                <th style="width: 25%;">Jumlah Komisaris Penerima Gaji :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['penerimagajidekom']) ? $row['penerimagajidekom'] : '0'; ?> Orang
                                </td>
                            </tr>
                            <tr>
                                <th style="width: 25%;">Jumlah Nominal Keseluruhan Gaji Komisaris (Rp) :</th>
                                <td style="width: 75%;">
                                    Rp
                                    <?= !empty($row['nominalgajidekom']) ? number_format($row['nominalgajidekom'], 0, ',', '.') : '0'; ?>
                                </td>
                            </tr>
                            <tr>
                                <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                    <td colspan="3">
                                        <button type="button" data-toggle="modal" class="btn" data-target="#modalUbahgaji" id="btn-edit"
                                            style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                            data-penerimagajidir="<?= $row['penerimagajidir']; ?>"
                                            data-nominalgajidir="<?= $row['nominalgajidir']; ?>"
                                            data-penerimagajidekom="<?= $row['penerimagajidekom']; ?>"
                                            data-nominalgajidekom="<?= $row['nominalgajidekom']; ?>"><i class="fa fa-edit"></i>&nbsp;
                                        </button>
                                        <button type="button" data-toggle="modal" data-target="#modalHapuspaketkebijakandirdekom"
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
    </div>
</div>

<div class="card">
    <div class="card-reader">
        <?php if ($userInGroupPE || $userInGroupAdmin): ?>
            <div class="row">
                <div class="col-md">
                    <button type="button" data-toggle="modal" class="btn btn-primary ml-3 mt-3"
                        data-target="#modalTambahtunjangan">
                        <i class="fa fa-plus"> Tambah Data</i>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <table class="table table-primary">
            <th>1.2. Tunjangan Bagi Direksi dan Dewan Komisaris</th>
        </table>
        <table class="table table-bordered table-secondary">
            <tbody>
                <?php if (empty($paketkebijakandirdekom)) { ?>
                    <tr>
                        <th style="width: 25%;">Jumlah Direksi Penerima Tunjangan :</th>
                        <td colspan="2">0 Orang</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Jumlah Nominal Keseluruhan Tunjangan Direksi:</th>
                        <td colspan="2">Rp 0</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Jumlah Komisaris Penerima Tunjangan :</th>
                        <td colspan="2">0 Orang</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Jumlah Nominal Keseluruhan Tunjangan Komisaris (Rp):</th>
                        <td colspan="2">Rp 0</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($paketkebijakandirdekom as $row): ?>
                        <?php if (
                            !empty($row['terimatunjangandir']) ||
                            !empty($row['nominaltunjangandir']) ||
                            !empty($row['terimatunjangandekom']) ||
                            !empty($row['nominaltunjangandekom'])
                        ): ?>
                            <tr>
                                <th style="width: 25%;">Jumlah Direksi Penerima Tunjangan :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['terimatunjangandir']) ? $row['terimatunjangandir'] : '0'; ?> Orang
                                </td>
                            </tr>
                            <tr>
                                <th style="width: 25%;">Jumlah Nominal Keseluruhan Tunjangan Direksi :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['nominaltunjangandir']) ? 'Rp ' . number_format($row['nominaltunjangandir'], 0, ',', '.') : 'Rp 0'; ?>
                                </td>
                            </tr>
                            <tr>
                                <th style="width: 25%;">Jumlah Komisaris Penerima Tunjangan :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['terimatunjangandekom']) ? $row['terimatunjangandekom'] : '0'; ?> Orang
                                </td>
                            </tr>
                            <tr>
                                <th style="width: 25%;">Jumlah Nominal Keseluruhan Tunjangan Komisaris (Rp) :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['nominaltunjangandekom']) ? 'Rp ' . number_format($row['nominaltunjangandekom'], 0, ',', '.') : 'Rp 0'; ?>
                                </td>
                            </tr>
                            <tr>
                                <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                    <td colspan="3">
                                        <button type="button" data-toggle="modal" class="btn" data-target="#modalUbahtunjangan"
                                            id="btn-edit" style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                            data-terimatunjangandir="<?= $row['terimatunjangandir']; ?>"
                                            data-nominaltunjangandir="<?= $row['nominaltunjangandir']; ?>"
                                            data-terimatunjangandekom="<?= $row['terimatunjangandekom']; ?>"
                                            data-nominaltunjangandekom="<?= $row['nominaltunjangandekom']; ?>"><i
                                                class="fa fa-edit"></i>&nbsp;
                                        </button>
                                        <button type="button" data-toggle="modal" data-target="#modalHapuspaketkebijakandirdekom"
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
    </div>
</div>

<div class="card">
    <div class="card-reader">
        <?php if ($userInGroupPE || $userInGroupAdmin): ?>
            <div class="row">
                <div class="col-md">
                    <button type="button" data-toggle="modal" class="btn btn-primary ml-3 mt-3"
                        data-target="#modalTambahtantiem">
                        <i class="fa fa-plus"> Tambah Data</i>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <table class="table table-primary">
            <th>1.3. Tantiem Bagi Direksi dan Dewan Komisaris</th>
        </table>
        <table class="table table-bordered table-secondary">
            <tbody>
                <?php if (empty($paketkebijakandirdekom)) { ?>
                    <tr>
                        <th style="width: 25%;">Jumlah Direksi Penerima Tantiem :</th>
                        <td colspan="2">0 Orang</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Jumlah Nominal Keseluruhan Tantiem Direksi (Rp):</th>
                        <td colspan="2">Rp 0</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Jumlah Komisaris Penerima Tantiem :</th>
                        <td colspan="2">0 Orang</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Jumlah Nominal Keseluruhan Tantiem Komisaris (Rp):</th>
                        <td colspan="2">Rp 0</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($paketkebijakandirdekom as $row): ?>
                        <?php if (
                            !empty($row['terimatantiemdir']) ||
                            !empty($row['nominaltantiemdir']) ||
                            !empty($row['terimatantiemdekom']) ||
                            !empty($row['nominaltantiemdekom'])
                        ): ?>
                            <tr>
                                <th style="width: 25%;">Jumlah Direksi Penerima Tantiem :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['terimatantiemdir']) ? $row['terimatantiemdir'] : '0'; ?> Orang
                                </td>
                            </tr>
                            <tr>
                                <th style="width: 25%;">Jumlah Nominal Keseluruhan Tantiem Direksi (Rp) :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['nominaltantiemdir']) ? 'Rp ' . number_format($row['nominaltantiemdir'], 0, ',', '.') : 'Rp 0'; ?>
                                </td>
                            </tr>
                            <tr>
                                <th style="width: 25%;">Jumlah Komisaris Penerima Tantiem :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['terimatantiemdekom']) ? $row['terimatantiemdekom'] : '0'; ?> Orang
                                </td>
                            </tr>
                            <tr>
                                <th style="width: 25%;">Jumlah Nominal Keseluruhan Tantiem Komisaris (Rp) :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['nominaltantiemdekom']) ? 'Rp ' . number_format($row['nominaltantiemdekom'], 0, ',', '.') : 'Rp 0'; ?>
                                </td>
                            </tr>
                            <tr>
                                <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                    <td colspan="3">
                                        <button type="button" data-toggle="modal" class="btn" data-target="#modalUbahtantiem"
                                            id="btn-edit" style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                            data-terimatantiemdir="<?= $row['terimatantiemdir']; ?>"
                                            data-nominaltantiemdir="<?= $row['nominaltantiemdir']; ?>"
                                            data-terimatantiemdekom="<?= $row['terimatantiemdekom']; ?>"
                                            data-nominaltantiemdekom="<?= $row['nominaltantiemdekom']; ?>"><i
                                                class="fa fa-edit"></i>&nbsp;
                                        </button>
                                        <button type="button" data-toggle="modal" data-target="#modalHapuspaketkebijakandirdekom"
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
    </div>
</div>

<div class="card">
    <div class="card-reader">
        <?php if ($userInGroupPE || $userInGroupAdmin): ?>
            <div class="row">
                <div class="col-md">
                    <button type="button" data-toggle="modal" class="btn btn-primary ml-3 mt-3"
                        data-target="#modalTambahsaham">
                        <i class="fa fa-plus"> Tambah Data</i>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <table class="table table-primary">
            <th>1.4. Kompensasi berbasis saham Bagi Direksi dan Dewan Komisaris</th>
        </table>
        <table class="table table-bordered table-secondary">
            <tbody>
                <?php if (empty($paketkebijakandirdekom)) { ?>
                    <tr>
                        <th style="width: 25%;">Jumlah Direksi Penerima Kompensasi berbasis saham:</th>
                        <td colspan="2">0 Orang</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Jumlah Nominal Keseluruhan Kompensasi berbasis saham Direksi (Rp):</th>
                        <td colspan="2">Rp 0</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Jumlah Komisaris Penerima Kompensasi berbasis saham:</th>
                        <td colspan="2">0 Orang</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Jumlah Nominal Keseluruhan Kompensasi berbasis saham Komisaris (Rp):
                        </th>
                        <td colspan="2">Rp 0</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($paketkebijakandirdekom as $row): ?>
                        <?php if (
                            !empty($row['terimashmdir']) ||
                            !empty($row['nominalshmdir']) ||
                            !empty($row['terimashmdekom']) ||
                            !empty($row['nominalshmdekom'])
                        ): ?>
                            <tr>
                                <th style="width: 25%;">Jumlah Direksi Penerima Kompensasi berbasis saham:</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['terimashmdir']) ? $row['terimashmdir'] : '0'; ?> Orang
                                </td>
                            </tr>
                            <tr>
                                <th style="width: 25%;">Jumlah Nominal Keseluruhan Kompensasi berbasis saham Direksi (Rp):</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['nominalshmdir']) ? 'Rp ' . number_format($row['nominalshmdir'], 0, ',', '.') : 'Rp 0'; ?>
                                </td>
                            </tr>
                            <tr>
                                <th style="width: 25%;">Jumlah Komisaris Penerima Kompensasi berbasis saham:</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['terimashmdekom']) ? $row['terimashmdekom'] : '0'; ?> Orang
                                </td>
                            </tr>
                            <tr>
                                <th style="width: 25%;">Jumlah Nominal Keseluruhan Kompensasi berbasis saham Komisaris (Rp):
                                </th>
                                <td style="width: 75%;">
                                    <?= !empty($row['nominalshmdekom']) ? 'Rp ' . number_format($row['nominalshmdekom'], 0, ',', '.') : 'Rp 0'; ?>
                                </td>
                            </tr>
                            <tr>
                                <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                    <td colspan="3">
                                        <button type="button" data-toggle="modal" class="btn" data-target="#modalUbahsaham"
                                            id="btn-edit" style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                            data-terimashmdir="<?= $row['terimashmdir']; ?>"
                                            data-nominalshmdir="<?= $row['nominalshmdir']; ?>"
                                            data-terimashmdekom="<?= $row['terimashmdekom']; ?>"
                                            data-nominalshmdekom="<?= $row['nominalshmdekom']; ?>"><i class="fa fa-edit"></i>&nbsp;
                                        </button>
                                        <button type="button" data-toggle="modal" data-target="#modalHapuspaketkebijakandirdekom"
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
    </div>
</div>

<div class="card">
    <div class="card-reader">
        <?php if ($userInGroupPE || $userInGroupAdmin): ?>
            <div class="row">
                <div class="col-md">
                    <button type="button" data-toggle="modal" class="btn btn-primary ml-3 mt-3"
                        data-target="#modalTambahremun">
                        <i class="fa fa-plus"> Tambah Data</i>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <table class="table table-primary">
            <th>1.5. Remunerasi lainnya Bagi Direksi dan Dewan Komisaris</th>
        </table>
        <table class="table table-bordered table-secondary">
            <tbody>
                <?php if (empty($paketkebijakandirdekom)) { ?>
                    <tr>
                        <th style="width: 25%;">Jumlah Direksi Penerima Remunerasi lainnya:</th>
                        <td colspan="2">0 Orang</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Jumlah Nominal Keseluruhan Remunerasi lainnya Direksi (Rp):</th>
                        <td colspan="2">Rp 0</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Jumlah Komisaris Penerima Remunerasi lainnya:</th>
                        <td colspan="2">0 Orang</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Jumlah Nominal Keseluruhan Remunerasi lainnya Komisaris (Rp):</th>
                        <td colspan="2">Rp 0</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($paketkebijakandirdekom as $row): ?>
                        <?php if (
                            !empty($row['terimaremunlaindir']) ||
                            !empty($row['nominalremunlaindir']) ||
                            !empty($row['terimaremunlaindekom']) ||
                            !empty($row['nominalremunlaindekom'])
                        ): ?>
                            <tr>
                                <th style="width: 25%;">Jumlah Direksi Penerima Remunerasi lainnya:</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['terimaremunlaindir']) ? $row['terimaremunlaindir'] : '0'; ?> Orang
                                </td>
                            </tr>
                            <tr>
                                <th style="width: 25%;">Jumlah Nominal Keseluruhan Remunerasi lainnya Direksi (Rp):</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['nominalremunlaindir']) ? 'Rp ' . number_format($row['nominalremunlaindir'], 0, ',', '.') : 'Rp 0'; ?>
                                </td>
                            </tr>
                            <tr>
                                <th style="width: 25%;">Jumlah Komisaris Penerima Remunerasi lainnya:</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['terimaremunlaindekom']) ? $row['terimaremunlaindekom'] : '0'; ?> Orang
                                </td>
                            </tr>
                            <tr>
                                <th style="width: 25%;">Jumlah Nominal Keseluruhan Remunerasi lainnya Komisaris (Rp):</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['nominalremunlaindekom']) ? 'Rp ' . number_format($row['nominalremunlaindekom'], 0, ',', '.') : 'Rp 0'; ?>
                                </td>
                            </tr>
                            <tr>
                                <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                    <td colspan="3">
                                        <button type="button" data-toggle="modal" class="btn" data-target="#modalUbahremun"
                                            id="btn-edit" style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                            data-terimaremunlaindir="<?= $row['terimaremunlaindir']; ?>"
                                            data-nominalremunlaindir="<?= $row['nominalremunlaindir']; ?>"
                                            data-terimaremunlaindekom="<?= $row['terimaremunlaindekom']; ?>"
                                            data-nominalremunlaindekom="<?= $row['nominalremunlaindekom']; ?>"><i
                                                class="fa fa-edit"></i>&nbsp;
                                        </button>
                                        <button type="button" data-toggle="modal" data-target="#modalHapuspaketkebijakandirdekom"
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
    </div>
</div>

<div class="card">
    <div class="card-reader">
        <?php if ($userInGroupPE || $userInGroupAdmin): ?>
            <div class="row">
                <div class="col-md">
                    <button type="button" data-toggle="modal" class="btn btn-primary ml-3 mt-3"
                        data-target="#modalTambahrumah">
                        <i class="fa fa-plus"> Tambah Data</i>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <table class="table table-primary">
            <th>2.1. Perumahan Bagi Direksi dan Dewan Komisaris</th>
        </table>
        <table class="table table-bordered table-secondary">
            <tbody>
                <?php if (empty($paketkebijakandirdekom)) { ?>
                    <tr>
                        <th style="width: 25%;">Jumlah Direksi Penerima Perumahan (Orang):</th>
                        <td colspan="2">0 Orang</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Jumlah Nominal Perumahan Direksi (Rp):</th>
                        <td colspan="2">Rp 0</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Jumlah Komisaris Penerima Perumahan (Orang):</th>
                        <td colspan="2">0 Orang</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Jumlah Nominal Perumahan Komisaris (Rp):</th>
                        <td colspan="2">Rp 0</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($paketkebijakandirdekom as $row): ?>
                        <?php if (
                            !empty($row['terimarumahdir']) ||
                            !empty($row['nominalrumahdir']) ||
                            !empty($row['terimarumahdekom']) ||
                            !empty($row['nominalrumahdekom'])
                        ): ?>
                            <tr>
                                <th style="width: 25%;">Jumlah Direksi Penerima Perumahan (Orang):</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['terimarumahdir']) ? $row['terimarumahdir'] : '0'; ?> Orang
                                </td>
                            </tr>
                            <tr>
                                <th style="width: 25%;">Jumlah Nominal Perumahan Direksi (Rp):</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['nominalrumahdir']) ? 'Rp ' . number_format($row['nominalrumahdir'], 0, ',', '.') : 'Rp 0'; ?>
                                </td>
                            </tr>
                            <tr>
                                <th style="width: 25%;">Jumlah Komisaris Penerima Perumahan (Orang):</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['terimarumahdekom']) ? $row['terimarumahdekom'] : '0'; ?> Orang
                                </td>
                            </tr>
                            <tr>
                                <th style="width: 25%;">Jumlah Nominal Perumahan Komisaris (Rp):</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['nominalrumahdekom']) ? 'Rp ' . number_format($row['nominalrumahdekom'], 0, ',', '.') : 'Rp 0'; ?>
                                </td>
                            </tr>
                            <tr>
                                <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                    <td colspan="3">
                                        <button type="button" data-toggle="modal" class="btn" data-target="#modalUbahrumah"
                                            id="btn-edit" style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                            data-terimarumahdir="<?= $row['terimarumahdir']; ?>"
                                            data-nominalrumahdir="<?= $row['nominalrumahdir']; ?>"
                                            data-terimarumahdekom="<?= $row['terimarumahdekom']; ?>"
                                            data-nominalrumahdekom="<?= $row['nominalrumahdekom']; ?>"><i class="fa fa-edit"></i>&nbsp;
                                        </button>
                                        <button type="button" data-toggle="modal" data-target="#modalHapuspaketkebijakandirdekom"
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
    </div>
</div>

<div class="card">
    <div class="card-reader">
        <?php if ($userInGroupPE || $userInGroupAdmin): ?>
            <div class="row">
                <div class="col-md">
                    <button type="button" data-toggle="modal" class="btn btn-primary ml-3 mt-3"
                        data-target="#modalTambahtransport">
                        <i class="fa fa-plus"> Tambah Data</i>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <table class="table table-primary">
            <th>2.2. Transportasi Bagi Direksi dan Dewan Komisaris</th>
        </table>
        <table class="table table-bordered table-secondary">
            <tbody>
                <?php if (empty($paketkebijakandirdekom)) { ?>
                    <tr>
                        <th style="width: 25%;">Jumlah Nominal Transportasi Direksi (Rp) :</th>
                        <td colspan="2">Rp 0</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Jumlah Nominal Keseluruhan Remunerasi lainnya Direksi (Rp):</th>
                        <td colspan="2">Rp 0</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Jumlah Komisaris Penerima Transportasi (Orang) :</th>
                        <td colspan="2">0 Orang</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Jumlah Nominal Transportasi Komisaris (Rp):</th>
                        <td colspan="2">Rp 0</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($paketkebijakandirdekom as $row): ?>
                        <?php if (
                            !empty($row['terimatransportdir']) ||
                            !empty($row['nominaltransportdir']) ||
                            !empty($row['terimatransportdekom']) ||
                            !empty($row['nominaltransportdekom'])
                        ): ?>
                            <tr>
                                <th style="width: 25%;">Jumlah Direksi Penerima Transportasi (Orang) :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['terimatransportdir']) ? $row['terimatransportdir'] : '0'; ?> Orang
                                </td>
                            </tr>
                            <tr>
                                <th style="width: 25%;">Jumlah Nominal Transportasi Direksi (Rp) :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['nominaltransportdir']) ? 'Rp ' . number_format($row['nominaltransportdir'], 0, ',', '.') : 'Rp 0'; ?>
                                </td>
                            </tr>
                            <tr>
                                <th style="width: 25%;">Jumlah Komisaris Penerima Transportasi (Orang) :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['terimatransportdekom']) ? $row['terimatransportdekom'] : '0'; ?> Orang
                                </td>
                            </tr>
                            <tr>
                                <th style="width: 25%;">Jumlah Nominal Transportasi Komisaris (Rp) :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['nominaltransportdekom']) ? 'Rp ' . number_format($row['nominaltransportdekom'], 0, ',', '.') : 'Rp 0'; ?>
                                </td>
                            </tr>
                            <tr>
                                <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                    <td colspan="3">
                                        <button type="button" data-toggle="modal" class="btn" data-target="#modalUbahtransport"
                                            id="btn-edit" style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                            data-terimatransportdir="<?= $row['terimatransportdir']; ?>"
                                            data-nominaltransportdir="<?= $row['nominaltransportdir']; ?>"
                                            data-terimatransportdekom="<?= $row['terimatransportdekom']; ?>"
                                            data-nominaltransportdekom="<?= $row['nominaltransportdekom']; ?>"><i
                                                class="fa fa-edit"></i>&nbsp;
                                        </button>
                                        <button type="button" data-toggle="modal" data-target="#modalHapuspaketkebijakandirdekom"
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
    </div>
</div>

<div class="card">
    <div class="card-reader">
        <?php if ($userInGroupPE || $userInGroupAdmin): ?>
            <div class="row">
                <div class="col-md">
                    <button type="button" data-toggle="modal" class="btn btn-primary ml-3 mt-3"
                        data-target="#modalTambahasuransi">
                        <i class="fa fa-plus"> Tambah Data</i>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <table class="table table-primary">
            <th>2.3. Asuransi Kesehatan Bagi Direksi dan Dewan Komisaris</th>
        </table>
        <table class="table table-bordered table-secondary">
            <tbody>
                <?php if (empty($paketkebijakandirdekom)) { ?>
                    <tr>
                        <th style="width: 25%;">Jumlah Direksi Penerima Asuransi Kesehatan (Orang):</th>
                        <td colspan="2">0 Orang</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Jumlah Nominal Asuransi Kesehatan Direksi (Rp):</th>
                        <td colspan="2">Rp 0</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Jumlah Komisaris Penerima Asuransi Kesehatan (Orang):</th>
                        <td colspan="2">0 Orang</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Jumlah Nominal Asuransi Kesehatan Komisaris (Rp):</th>
                        <td colspan="2">Rp 0</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($paketkebijakandirdekom as $row): ?>
                        <?php if (
                            !empty($row['terimaasuransidir']) ||
                            !empty($row['nominalasuransidir']) ||
                            !empty($row['terimaasuransidekom']) ||
                            !empty($row['nominalasuransidekom'])
                        ): ?>
                            <tr>
                                <th style="width: 25%;">Jumlah Direksi Penerima Asuransi Kesehatan (Orang) :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['terimaasuransidir']) ? $row['terimaasuransidir'] : '0'; ?> Orang
                                </td>
                            </tr>
                            <tr>
                                <th style="width: 25%;">Jumlah Nominal Asuransi Kesehatan Direksi (Rp) :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['nominalasuransidir']) ? 'Rp ' . number_format($row['nominalasuransidir'], 0, ',', '.') : 'Rp 0'; ?>
                                </td>
                            </tr>
                            <tr>
                                <th style="width: 25%;">Jumlah Komisaris Penerima Asuransi Kesehatan (Orang) :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['terimaasuransidekom']) ? $row['terimaasuransidekom'] : '0'; ?> Orang
                                </td>
                            </tr>
                            <tr>
                                <th style="width: 25%;">Jumlah Nominal Asuransi Kesehatan Komisaris (Rp) :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['nominalasuransidekom']) ? 'Rp ' . number_format($row['nominalasuransidekom'], 0, ',', '.') : 'Rp 0'; ?>
                                </td>
                            </tr>
                            <tr>
                                <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                    <td colspan="3">
                                        <button type="button" data-toggle="modal" class="btn" data-target="#modalUbahasuransi"
                                            id="btn-edit" style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                            data-terimaasuransidir="<?= $row['terimaasuransidir']; ?>"
                                            data-nominalasuransidir="<?= $row['nominalasuransidir']; ?>"
                                            data-terimaasuransidekom="<?= $row['terimaasuransidekom']; ?>"
                                            data-nominalasuransidekom="<?= $row['nominalasuransidekom']; ?>"><i
                                                class="fa fa-edit"></i>&nbsp;
                                        </button>
                                        <button type="button" data-toggle="modal" data-target="#modalHapuspaketkebijakandirdekom"
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
    </div>
</div>

<div class="card">
    <div class="card-reader">
        <?php if ($userInGroupPE || $userInGroupAdmin): ?>
            <div class="row">
                <div class="col-md">
                    <button type="button" data-toggle="modal" class="btn btn-primary ml-3 mt-3"
                        data-target="#modalTambahfasilitas">
                        <i class="fa fa-plus"> Tambah Data</i>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <table class="table table-primary">
            <th>2.4. Fasilitas Lain-Lainnya Bagi Direksi dan Dewan Komisaris</th>
        </table>
        <table class="table table-bordered table-secondary">
            <tbody>
                <?php if (empty($paketkebijakandirdekom)) { ?>
                    <tr>
                        <th style="width: 25%;">Jumlah Direksi Penerima Fasilitas Lain-Lainnya (Orang):</th>
                        <td colspan="2">0 Orang</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Jumlah Nominal Fasilitas Lain-Lainnya Direksi (Rp):</th>
                        <td colspan="2">Rp 0</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Jumlah Komisaris Penerima Fasilitas Lain-Lainnya (Orang):</th>
                        <td colspan="2">0 Orang</td>
                    </tr>
                    <tr>
                        <th style="width: 25%;">Jumlah Nominal Fasilitas Lain-Lainnya Komisaris (Rp):</th>
                        <td colspan="2">Rp 0</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($paketkebijakandirdekom as $row): ?>
                        <?php if (
                            !empty($row['terimafasilitasdir']) ||
                            !empty($row['nominalfasilitasdir']) ||
                            !empty($row['terimafasilitasdekom']) ||
                            !empty($row['nominalfasilitasdekom'])
                        ): ?>
                            <tr>
                                <th style="width: 25%;">Jumlah Direksi Penerima Fasilitas Lain-Lainnya (Orang) :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['terimafasilitasdir']) ? $row['terimafasilitasdir'] : '0'; ?> Orang
                                </td>
                            </tr>
                            <tr>
                                <th style="width: 25%;">Jumlah Nominal Fasilitas Lain-Lainnya Direksi (Rp) :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['nominalfasilitasdir']) ? 'Rp ' . number_format($row['nominalfasilitasdir'], 0, ',', '.') : 'Rp 0'; ?>
                                </td>
                            </tr>
                            <tr>
                                <th style="width: 25%;">Jumlah Komisaris Penerima Fasilitas Lain-Lainnya (Orang) :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['terimafasilitasdekom']) ? $row['terimafasilitasdekom'] : '0'; ?> Orang
                                </td>
                            </tr>
                            <tr>
                                <th style="width: 25%;">Jumlah Nominal Fasilitas Lain-Lainnya Komisaris (Rp) :</th>
                                <td style="width: 75%;">
                                    <?= !empty($row['nominalfasilitasdekom']) ? 'Rp ' . number_format($row['nominalfasilitasdekom'], 0, ',', '.') : 'Rp 0'; ?>
                                </td>
                            </tr>
                            <tr>
                                <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                    <td colspan="3">
                                        <button type="button" data-toggle="modal" class="btn" data-target="#modalUbahfasilitas"
                                            id="btn-edit" style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                            data-terimafasilitasdir="<?= $row['terimafasilitasdir']; ?>"
                                            data-nominalfasilitasdir="<?= $row['nominalfasilitasdir']; ?>"
                                            data-terimafasilitasdekom="<?= $row['terimafasilitasdekom']; ?>"
                                            data-nominalfasilitasdekom="<?= $row['nominalfasilitasdekom']; ?>"><i
                                                class="fa fa-edit"></i>&nbsp;
                                        </button>
                                        <button type="button" data-toggle="modal" data-target="#modalHapuspaketkebijakandirdekom"
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
    </div>
</div>

<div class="modal fade" id="modalTambahgaji">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Data Gaji Bagi Direksi dan Dewan Komisaris</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('paketkebijakandirdekom/tambahgaji'); ?>" method="post">
                    <div class="form-group">
                        <label for="penerimagajidir">Input Jumlah Direksi Penerima Gaji:</label>
                        <input type="text" name="penerimagajidir" id="penerimagajidir" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="nominalgajidir">Input Jumlah Nominal Keseluruhan Gaji Direksi (Rp): </label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input type="text" id="formatted_nominalgajidir" class="form-control rupiah">
                            <input type="hidden" name="nominalgajidir" id="nominalgajidir_hidden">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="penerimagajidekom">Input Jumlah Komisaris Penerima Gaji: </label>
                        <input type="text" name="penerimagajidekom" id="penerimagajidekom" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="nominalgajidekom">Input Jumlah Nominal Keseluruhan Gaji Komisaris (Rp): </label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input type="text" id="formatted_nominalgajidekom" class="form-control rupiah">
                            <input type="hidden" name="nominalgajidekom" id="nominalgajidekom_hidden">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="tambahgaji" class="btn btn-primary">Tambah Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const rupiahInputs = document.querySelectorAll('.rupiah');

    rupiahInputs.forEach(formattedInput => {
        const hiddenInputId = formattedInput.id.replace('formatted_', '') + '_hidden';
        const hiddenInput = document.getElementById(hiddenInputId);

        formattedInput.addEventListener('keyup', function (e) {
            const cleanedValue = this.value.replace(/[^,\d]/g, '');
            this.value = formatRupiah(cleanedValue);
            hiddenInput.value = cleanedValue;
        });

        formattedInput.addEventListener('focusout', function () {
            const cleanedValue = this.value.replace(/[^,\d]/g, '');
            this.value = formatRupiah(cleanedValue);
            hiddenInput.value = cleanedValue;
        });
    });

    function formatRupiah(angka) {
        var number_string = angka.replace(/[^,\d]/g, '').toString(),
            split = number_string.split(','),
            sisa = split[0].length % 3,
            rupiah = split[0].substr(0, sisa),
            ribuan = split[0].substr(sisa).match(/\d{3}/gi);

        if (ribuan) {
            separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }
        return rupiah;
    }
</script>

<div class="modal fade" id="modalTambahtunjangan">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Data Tunjangan Bagi Direksi dan Dewan Komisaris</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('paketkebijakandirdekom/tambahtunjangan'); ?>" method="post">
                    <div class="form-group">
                        <label for="terimatunjangandir">Input Jumlah Direksi Penerima Tunjangan:</label>
                        <input type="text" name="terimatunjangandir" id="terimatunjangandir" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="nominaltunjangandir">Input Jumlah Nominal Keseluruhan Tunjangan Direksi (Rp):
                        </label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input type="text" id="formatted_nominaltunjangandir"
                                class="form-control rupiah_tambah_tampilan">
                            <input type="hidden" name="nominaltunjangandir" id="nominaltunjangandir">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="terimatunjangandekom">Input Jumlah Komisaris Penerima Tunjangan : </label>
                        <input type="text" name="terimatunjangandekom" id="terimatunjangandekom" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="nominaltunjangandekom">Input Jumlah Nominal Keseluruhan Tunjangan Komisaris
                            (Rp): </label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input type="text" id="formatted_nominaltunjangandekom"
                                class="form-control rupiah_tambah_tampilan">
                            <input type="hidden" name="nominaltunjangandekom" id="nominaltunjangandekom">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="tambahtunjangan" class="btn btn-primary">Tambah Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const rupiahTunjanganInputsTambahTampilan = document.querySelectorAll('#modalTambahtunjangan .input-group .rupiah_tambah_tampilan');

    rupiahTunjanganInputsTambahTampilan.forEach(formattedInput => {
        const hiddenInputId = formattedInput.id.replace('formatted_', '');
        const hiddenInput = document.getElementById(hiddenInputId);

        formattedInput.addEventListener('keyup', function (e) {
            const cleanedValue = this.value.replace(/[^,\d]/g, '');
            this.value = formatRupiahTunjanganTambahTampilan(cleanedValue);
            if (hiddenInput) {
                hiddenInput.value = cleanedValue;
            }
        });

        formattedInput.addEventListener('focusout', function () {
            const cleanedValue = this.value.replace(/[^,\d]/g, '');
            this.value = formatRupiahTunjanganTambahTampilan(cleanedValue);
            if (hiddenInput) {
                hiddenInput.value = cleanedValue;
            }
        });
    });

    function formatRupiahTunjanganTambahTampilan(angka) {
        var number_string = angka.replace(/[^,\d]/g, '').toString(),
            split = number_string.split(','),
            sisa = split[0].length % 3,
            rupiah = split[0].substr(0, sisa),
            ribuan = split[0].substr(sisa).match(/\d{3}/gi);

        if (ribuan) {
            separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }
        return rupiah;
    }
</script>

<div class="modal fade" id="modalTambahtantiem">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Data Tantiem Bagi Direksi dan Dewan Komisaris</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('paketkebijakandirdekom/tambahtantiem'); ?>" method="post">
                    <div class="form-group">
                        <label for="terimatantiemdir">Input Jumlah Direksi Penerima Tantiem:</label>
                        <input type="text" name="terimatantiemdir" id="terimatantiemdir" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="nominaltantiemdir">Input Jumlah Nominal Keseluruhan Tantiem Direksi (Rp):
                        </label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input type="text" id="formatted_nominaltantiemdir"
                                class="form-control rupiah_tambah_tampilan_tantiem">
                            <input type="hidden" name="nominaltantiemdir" id="nominaltantiemdir">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="terimatantiemdekom">Input Jumlah Komisaris Penerima Tantiem: </label>
                        <input type="text" name="terimatantiemdekom" id="terimatantiemdekom" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="nominaltantiemdekom">Input Jumlah Nominal Keseluruhan Tantiem Komisaris (Rp):
                        </label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input type="text" id="formatted_nominaltantiemdekom"
                                class="form-control rupiah_tambah_tampilan_tantiem">
                            <input type="hidden" name="nominaltantiemdekom" id="nominaltantiemdekom">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="tambahtantiem" class="btn btn-primary">Tambah Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const rupiahTantiemInputsTambahTampilan = document.querySelectorAll('#modalTambahtantiem .input-group .rupiah_tambah_tampilan_tantiem');

    rupiahTantiemInputsTambahTampilan.forEach(formattedInput => {
        const hiddenInputId = formattedInput.id.replace('formatted_', '');
        const hiddenInput = document.getElementById(hiddenInputId);

        formattedInput.addEventListener('keyup', function (e) {
            const cleanedValue = this.value.replace(/[^,\d]/g, '');
            this.value = formatRupiahTantiemTambahTampilan(cleanedValue);
            if (hiddenInput) {
                hiddenInput.value = cleanedValue;
            }
        });

        formattedInput.addEventListener('focusout', function () {
            const cleanedValue = this.value.replace(/[^,\d]/g, '');
            this.value = formatRupiahTantiemTambahTampilan(cleanedValue);
            if (hiddenInput) {
                hiddenInput.value = cleanedValue;
            }
        });
    });

    function formatRupiahTantiemTambahTampilan(angka) {
        var number_string = angka.replace(/[^,\d]/g, '').toString(),
            split = number_string.split(','),
            sisa = split[0].length % 3,
            rupiah = split[0].substr(0, sisa),
            ribuan = split[0].substr(sisa).match(/\d{3}/gi);

        if (ribuan) {
            separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }
        return rupiah;
    }
</script>

<div class="modal fade" id="modalTambahsaham">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Data Kompensasi berbasis saham Bagi Direksi dan Dewan Komisaris</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('paketkebijakandirdekom/tambahsaham'); ?>" method="post">
                    <div class="form-group">
                        <label for="terimashmdir">Input Jumlah Direksi Penerima Kompensasi berbasis saham:</label>
                        <input type="text" name="terimashmdir" id="terimashmdir" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="nominalshmdir">Input Jumlah Nominal Keseluruhan Kompensasi berbasis saham
                            Direksi (Rp): </label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input type="text" id="formatted_nominalshmdir"
                                class="form-control rupiah_tambah_tampilan_saham">
                            <input type="hidden" name="nominalshmdir" id="nominalshmdir">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="terimashmdekom">Input Jumlah Komisaris Penerima Kompensasi berbasis saham :
                        </label>
                        <input type="text" name="terimashmdekom" id="terimashmdekom" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="nominalshmdekom">Input Jumlah Nominal Keseluruhan Kompensasi berbasis saham
                            Komisaris (Rp): </label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input type="text" id="formatted_nominalshmdekom"
                                class="form-control rupiah_tambah_tampilan_saham">
                            <input type="hidden" name="nominalshmdekom" id="nominalshmdekom">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="tambahsaham" class="btn btn-primary">Tambah Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const rupiahSahamInputsTambahTampilan = document.querySelectorAll('#modalTambahsaham .input-group .rupiah_tambah_tampilan_saham');

    rupiahSahamInputsTambahTampilan.forEach(formattedInput => {
        const hiddenInputId = formattedInput.id.replace('formatted_', '');
        const hiddenInput = document.getElementById(hiddenInputId);

        formattedInput.addEventListener('keyup', function (e) {
            const cleanedValue = this.value.replace(/[^,\d]/g, '');
            this.value = formatRupiahSahamTambahTampilan(cleanedValue);
            if (hiddenInput) {
                hiddenInput.value = cleanedValue;
            }
        });

        formattedInput.addEventListener('focusout', function () {
            const cleanedValue = this.value.replace(/[^,\d]/g, '');
            this.value = formatRupiahSahamTambahTampilan(cleanedValue);
            if (hiddenInput) {
                hiddenInput.value = cleanedValue;
            }
        });
    });

    function formatRupiahSahamTambahTampilan(angka) {
        var number_string = angka.replace(/[^,\d]/g, '').toString(),
            split = number_string.split(','),
            sisa = split[0].length % 3,
            rupiah = split[0].substr(0, sisa),
            ribuan = split[0].substr(sisa).match(/\d{3}/gi);

        if (ribuan) {
            separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }
        return rupiah;
    }
</script>

<div class="modal fade" id="modalTambahremun">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Data Remunerasi lainnya Bagi Direksi dan Dewan Komisaris</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('paketkebijakandirdekom/tambahremun'); ?>" method="post">
                    <div class="form-group">
                        <label for="terimaremunlaindir">Input Jumlah Direksi Penerima Remunerasi lainnya:</label>
                        <input type="text" name="terimaremunlaindir" id="terimaremunlaindir" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="nominalremunlaindir">Input Jumlah Nominal Keseluruhan Remunerasi lainnya Direksi
                            (Rp): </label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input type="text" id="formatted_nominalremunlaindir"
                                class="form-control rupiah_tambah_tampilan_remun">
                            <input type="hidden" name="nominalremunlaindir" id="nominalremunlaindir">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="terimaremunlaindekom">Input Jumlah Komisaris Penerima Remunerasi lainnya :
                        </label>
                        <input type="text" name="terimaremunlaindekom" id="terimaremunlaindekom" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="nominalremunlaindekom">Input Jumlah Nominal Keseluruhan Remunerasi lainnya
                            Komisaris (Rp): </label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input type="text" id="formatted_nominalremunlaindekom"
                                class="form-control rupiah_tambah_tampilan_remun">
                            <input type="hidden" name="nominalremunlaindekom" id="nominalremunlaindekom">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="tambahremun" class="btn btn-primary">Tambah Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const rupiahRemunInputsTambahTampilan = document.querySelectorAll(
        '#modalTambahremun .input-group .rupiah_tambah_tampilan_remun'
    );

    rupiahRemunInputsTambahTampilan.forEach(formattedInput => {
        const hiddenInputId = formattedInput.id.replace('formatted_', '');
        const hiddenInput = document.getElementById(hiddenInputId);

        formattedInput.addEventListener('keyup', function (e) {
            const cleanedValue = this.value.replace(/[^,\d]/g, '');
            this.value = formatRupiahRemunTambahTampilan(cleanedValue);
            if (hiddenInput) {
                hiddenInput.value = cleanedValue;
            }
        });

        formattedInput.addEventListener('focusout', function () {
            const cleanedValue = this.value.replace(/[^,\d]/g, '');
            this.value = formatRupiahRemunTambahTampilan(cleanedValue);
            if (hiddenInput) {
                hiddenInput.value = cleanedValue;
            }
        });
    });

    function formatRupiahRemunTambahTampilan(angka) {
        var number_string = angka.replace(/[^,\d]/g, '').toString(),
            split = number_string.split(','),
            sisa = split[0].length % 3,
            rupiah = split[0].substr(0, sisa),
            ribuan = split[0].substr(sisa).match(/\d{3}/gi);

        if (ribuan) {
            separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }
        return rupiah;
    }
</script>

<div class="modal fade" id="modalTambahrumah">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Data Perumahan Bagi Direksi dan Dewan Komisaris</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('paketkebijakandirdekom/tambahrumah'); ?>" method="post">
                    <div class="form-group">
                        <label for="terimarumahdir">Input Jumlah Direksi Penerima Perumahan (Orang):</label>
                        <input type="text" name="terimarumahdir" id="terimarumahdir" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="nominalrumahdir">Input Jumlah Nominal Perumahan Direksi (Rp): </label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input type="text" id="formatted_nominalrumahdir"
                                class="form-control rupiah_tambah_tampilan_rumah">
                            <input type="hidden" name="nominalrumahdir" id="nominalrumahdir">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="terimarumahdekom">Input Jumlah Komisaris Penerima Perumahan (Orang) : </label>
                        <input type="text" name="terimarumahdekom" id="terimarumahdekom" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="nominalrumahdekom">Input Jumlah Nominal Perumahan Komisaris (Rp): </label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input type="text" id="formatted_nominalrumahdekom"
                                class="form-control rupiah_tambah_tampilan_rumah">
                            <input type="hidden" name="nominalrumahdekom" id="nominalrumahdekom">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="tambahrumah" class="btn btn-primary">Tambah Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const rupiahRumahInputsTambahTampilan = document.querySelectorAll('#modalTambahrumah .input-group .rupiah_tambah_tampilan_rumah');

    rupiahRumahInputsTambahTampilan.forEach(formattedInput => {
        const hiddenInputId = formattedInput.id.replace('formatted_', '');
        const hiddenInput = document.getElementById(hiddenInputId);

        formattedInput.addEventListener('keyup', function (e) {
            const cleanedValue = this.value.replace(/[^,\d]/g, '');
            this.value = formatRupiahRumahTambahTampilan(cleanedValue);
            if (hiddenInput) {
                hiddenInput.value = cleanedValue;
            }
        });

        formattedInput.addEventListener('focusout', function () {
            const cleanedValue = this.value.replace(/[^,\d]/g, '');
            this.value = formatRupiahRumahTambahTampilan(cleanedValue);
            if (hiddenInput) {
                hiddenInput.value = cleanedValue;
            }
        });
    });

    function formatRupiahRumahTambahTampilan(angka) {
        var number_string = angka.replace(/[^,\d]/g, '').toString(),
            split = number_string.split(','),
            sisa = split[0].length % 3,
            rupiah = split[0].substr(0, sisa),
            ribuan = split[0].substr(sisa).match(/\d{3}/gi);

        if (ribuan) {
            separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }
        return rupiah;
    }
</script>

<div class="modal fade" id="modalTambahtransport">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Data Transportasi Bagi Direksi dan Dewan Komisaris</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('paketkebijakandirdekom/tambahtransport'); ?>" method="post">
                    <div class="form-group">
                        <label for="terimatransportdir">Input Jumlah Direksi Penerima Transportasi (Orang):</label>
                        <input type="text" name="terimatransportdir" id="terimatransportdir" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="nominaltransportdir">Input Jumlah Nominal Transportasi Direksi (Rp): </label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input type="text" id="formatted_nominaltransportdir"
                                class="form-control rupiah_tambah_tampilan_transport">
                            <input type="hidden" name="nominaltransportdir" id="nominaltransportdir">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="terimatransportdekom">Input Jumlah Komisaris Penerima Transportasi (Orang) :
                        </label>
                        <input type="text" name="terimatransportdekom" id="terimatransportdekom" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="nominaltransportdekom">Input Jumlah Nominal Transportasi Komisaris (Rp):
                        </label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input type="text" id="formatted_nominaltransportdekom"
                                class="form-control rupiah_tambah_tampilan_transport">
                            <input type="hidden" name="nominaltransportdekom" id="nominaltransportdekom">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="tambahtransport" class="btn btn-primary">Tambah Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const rupiahTransportInputsTambahTampilan = document.querySelectorAll(
        '#modalTambahtransport .input-group .rupiah_tambah_tampilan_transport'
    );

    rupiahTransportInputsTambahTampilan.forEach(formattedInput => {
        const hiddenInputId = formattedInput.id.replace('formatted_', '');
        const hiddenInput = document.getElementById(hiddenInputId);

        formattedInput.addEventListener('keyup', function (e) {
            const cleanedValue = this.value.replace(/[^,\d]/g, '');
            this.value = formatRupiahTransportTambahTampilan(cleanedValue);
            if (hiddenInput) {
                hiddenInput.value = cleanedValue;
            }
        });

        formattedInput.addEventListener('focusout', function () {
            const cleanedValue = this.value.replace(/[^,\d]/g, '');
            this.value = formatRupiahTransportTambahTampilan(cleanedValue);
            if (hiddenInput) {
                hiddenInput.value = cleanedValue;
            }
        });
    });

    function formatRupiahTransportTambahTampilan(angka) {
        var number_string = angka.replace(/[^,\d]/g, '').toString(),
            split = number_string.split(','),
            sisa = split[0].length % 3,
            rupiah = split[0].substr(0, sisa),
            ribuan = split[0].substr(sisa).match(/\d{3}/gi);

        if (ribuan) {
            separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }
        return rupiah;
    }
</script>

<div class="modal fade" id="modalTambahasuransi">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Data Asuransi Kesehatan Bagi Direksi dan Dewan Komisaris</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('paketkebijakandirdekom/tambahasuransi'); ?>" method="post">
                    <div class="form-group">
                        <label for="terimaasuransidir">Input Jumlah Direksi Penerima Asuransi Kesehatan
                            (Orang):</label>
                        <input type="text" name="terimaasuransidir" id="terimaasuransidir" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="nominalasuransidir">Input Jumlah Nominal Asuransi Kesehatan Direksi (Rp):
                        </label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input type="text" id="formatted_nominalasuransidir"
                                class="form-control rupiah_tambah_tampilan_asuransi">
                            <input type="hidden" name="nominalasuransidir" id="nominalasuransidir">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="terimaasuransidekom">Input Jumlah Komisaris Penerima Asuransi Kesehatan (Orang)
                            : </label>
                        <input type="text" name="terimaasuransidekom" id="terimaasuransidekom" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="nominalasuransidekom">Input Jumlah Nominal Asuransi Kesehatan Komisaris (Rp):
                        </label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input type="text" id="formatted_nominalasuransidekom"
                                class="form-control rupiah_tambah_tampilan_asuransi">
                            <input type="hidden" name="nominalasuransidekom" id="nominalasuransidekom">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="tambahasuransi" class="btn btn-primary">Tambah Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const rupiahAsuransiInputsTambahTampilan = document.querySelectorAll('#modalTambahasuransi .input-group .rupiah_tambah_tampilan_asuransi');

    rupiahAsuransiInputsTambahTampilan.forEach(formattedInput => {
        const hiddenInputId = formattedInput.id.replace('formatted_', '');
        const hiddenInput = document.getElementById(hiddenInputId);

        formattedInput.addEventListener('keyup', function (e) {
            const cleanedValue = this.value.replace(/[^,\d]/g, '');
            this.value = formatRupiahAsuransiTambahTampilan(cleanedValue);
            if (hiddenInput) {
                hiddenInput.value = cleanedValue;
            }
        });

        formattedInput.addEventListener('focusout', function () {
            const cleanedValue = this.value.replace(/[^,\d]/g, '');
            this.value = formatRupiahAsuransiTambahTampilan(cleanedValue);
            if (hiddenInput) {
                hiddenInput.value = cleanedValue;
            }
        });
    });

    function formatRupiahAsuransiTambahTampilan(angka) {
        var number_string = angka.replace(/[^,\d]/g, '').toString(),
            split = number_string.split(','),
            sisa = split[0].length % 3,
            rupiah = split[0].substr(0, sisa),
            ribuan = split[0].substr(sisa).match(/\d{3}/gi);

        if (ribuan) {
            separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }
        return rupiah;
    }
</script>

<div class="modal fade" id="modalTambahfasilitas">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Data Fasilitas Bagi Direksi dan Dewan Komisaris</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('paketkebijakandirdekom/tambahfasilitas'); ?>" method="post">
                    <div class="form-group">
                        <label for="terimafasilitasdir">Input Jumlah Direksi Penerima Fasilitas Lain-Lainnya
                            (Orang):</label>
                        <input type="text" name="terimafasilitasdir" id="terimafasilitasdir" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="nominalfasilitasdir">Input Jumlah Nominal Fasilitas Lain-Lainnya Direksi (Rp):
                        </label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input type="text" id="formatted_nominalfasilitasdir"
                                class="form-control rupiah_tambah_tampilan_fasilitas">
                            <input type="hidden" name="nominalfasilitasdir" id="nominalfasilitasdir">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="terimafasilitasdekom">Input Jumlah Komisaris Penerima Fasilitas Lain-Lainnya
                            (Orang) : </label>
                        <input type="text" name="terimafasilitasdekom" id="terimafasilitasdekom" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="nominalfasilitasdekom">Input Jumlah Nominal Fasilitas Lain-Lainnya Komisaris
                            (Rp): </label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input type="text" id="formatted_nominalfasilitasdekom"
                                class="form-control rupiah_tambah_tampilan_fasilitas">
                            <input type="hidden" name="nominalfasilitasdekom" id="nominalfasilitasdekom">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="tambahfasilitas" class="btn btn-primary">Tambah Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const rupiahFasilitasInputsTambahTampilan = document.querySelectorAll(
        '#modalTambahfasilitas .input-group .rupiah_tambah_tampilan_fasilitas'
    );

    rupiahFasilitasInputsTambahTampilan.forEach(formattedInput => {
        const hiddenInputId = formattedInput.id.replace('formatted_', '');
        const hiddenInput = document.getElementById(hiddenInputId);

        formattedInput.addEventListener('keyup', function (e) {
            const cleanedValue = this.value.replace(/[^,\d]/g, '');
            this.value = formatRupiahFasilitasTambahTampilan(cleanedValue);
            if (hiddenInput) {
                hiddenInput.value = cleanedValue;
            }
        });

        formattedInput.addEventListener('focusout', function () {
            const cleanedValue = this.value.replace(/[^,\d]/g, '');
            this.value = formatRupiahFasilitasTambahTampilan(cleanedValue);
            if (hiddenInput) {
                hiddenInput.value = cleanedValue;
            }
        });
    });

    function formatRupiahFasilitasTambahTampilan(angka) {
        var number_string = angka.replace(/[^,\d]/g, '').toString(),
            split = number_string.split(','),
            sisa = split[0].length % 3,
            rupiah = split[0].substr(0, sisa),
            ribuan = split[0].substr(sisa).match(/\d{3}/gi);

        if (ribuan) {
            separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }
        return rupiah;
    }
</script>

<div class="modal fade" id="modalTambahketerangan">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Keterangan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('paketkebijakandirdekom/tambahketerangan'); ?>" method="post">
                    <div class="form-group">
                        <label for="keterangan">Input Keterangan:</label>
                        <textarea style="height: 150px" type="text" name="keterangan" id="keterangan"
                            class="form-control"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" name="tambahketerangan" class="btn btn-primary">Tambah Data</button>
            </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalUbahgaji" tabindex="-1" role="dialog" aria-labelledby="modalUbahgajiLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalUbahgajiLabel">Ubah Gaji Bagi Direksi dan Dewan Komisaris </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('paketkebijakandirdekom/ubahgaji'); ?>" method="post" id="gajiForm">
                    <input type="hidden" name="id" id="id-paketkebijakandirdekom">
                    <div class="mb-3">
                        <label for="penerimagajidir">Ubah Jumlah Direksi Penerima Gaji:</label>
                        <input class="form-control" type="number" name="penerimagajidir" id="penerimagajidir" min="0"
                            value="<?= isset($row['penerimagajidir']) ? htmlspecialchars($row['penerimagajidir']) : '' ?>">
                    </div>
                    <div class="mb-3">
                        <label for="nominalgajidir">Ubah Jumlah Nominal Keseluruhan Gaji Direksi (Rp):</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input class="form-control rupiah-input" type="text" name="nominalgajidir"
                                id="nominalgajidir"
                                value="<?= isset($row['nominalgajidir']) ? number_format($row['nominalgajidir'], 0, ',', '.') : '' ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="penerimagajidekom">Ubah Jumlah Komisaris Penerima Gaji :</label>
                        <input class="form-control" type="number" name="penerimagajidekom" id="penerimagajidekom"
                            min="0"
                            value="<?= isset($row['penerimagajidekom']) ? htmlspecialchars($row['penerimagajidekom']) : '' ?>">
                    </div>
                    <div class="mb-3">
                        <label for="nominalgajidekom">Ubah Jumlah Nominal Keseluruhan Gaji Komisaris (Rp):</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input class="form-control rupiah-input" type="text" name="nominalgajidekom"
                                id="nominalgajidekom"
                                value="<?= isset($row['nominalgajidekom']) ? number_format($row['nominalgajidekom'], 0, ',', '.') : '' ?>">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="ubahgaji" class="btn btn-primary">Ubah Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Format Rupiah function
        const formatRupiah = (input) => {
            let value = input.value.replace(/\D/g, '');
            if (value.length > 0) {
                value = parseInt(value, 10).toLocaleString('id-ID');
            }
            input.value = value;
        };

        // Initialize Rupiah formatting
        document.querySelectorAll('.rupiah-input').forEach(input => {
            input.addEventListener('input', function () {
                formatRupiah(this);
            });

            input.addEventListener('focus', function () {
                formatRupiah(this);
            });
        });

        // Clean up values before form submission
        document.getElementById('gajiForm').addEventListener('submit', function (e) {
            document.querySelectorAll('.rupiah-input').forEach(input => {
                input.value = input.value.replace(/\D/g, '');
            });
        });

        // Prevent negative numbers
        document.getElementById('penerimagajidir').addEventListener('change', function () {
            if (this.value < 0) this.value = 0;
        });

        document.getElementById('penerimagajidekom').addEventListener('change', function () {
            if (this.value < 0) this.value = 0;
        });

        // Modal show event handler
        $('#modalUbahgaji').on('show.bs.modal', function (event) {
            const button = $(event.relatedTarget);
            const id = button.data('id');
            const penerimagajidir = button.data('penerimagajidir');
            const nominalgajidir = button.data('nominalgajidir');
            const penerimagajidekom = button.data('penerimagajidekom');
            const nominalgajidekom = button.data('nominalgajidekom');

            const modal = $(this);
            modal.find('#id-paketkebijakandirdekom').val(id);
            modal.find('#penerimagajidir').val(penerimagajidir);
            modal.find('#penerimagajidekom').val(penerimagajidekom);

            // Format nominal values
            const formatValue = (value) => {
                return value ? parseInt(value).toLocaleString('id-ID') : '';
            };

            modal.find('#nominalgajidir').val(formatValue(nominalgajidir));
            modal.find('#nominalgajidekom').val(formatValue(nominalgajidekom));
        });
    });
</script>


<?php if (!empty($paketkebijakandirdekom)) { ?>
    <div class="modal fade" id="modalUbahtunjangan">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Tunjangan Bagi Direksi dan Dewan Komisaris</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('paketkebijakandirdekom/ubahtunjangan'); ?>" method="post"
                        id="tunjanganForm">
                        <input type="hidden" name="id" id="id-paketkebijakandirdekom">
                        <div class="mb-3">
                            <label for="terimatunjangandir">Ubah Jumlah Direksi Penerima Tunjangan:</label>
                            <input class="form-control" type="number" name="terimatunjangandir" id="terimatunjangandir"
                                min="0"
                                value="<?= isset($row['terimatunjangandir']) ? htmlspecialchars($row['terimatunjangandir']) : '' ?>">
                        </div>
                        <div class="mb-3">
                            <label for="nominaltunjangandir">Ubah Jumlah Nominal Keseluruhan Tunjangan Direksi (Rp):</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Rp</span>
                                </div>
                                <input class="form-control rupiah-input" type="text" name="nominaltunjangandir"
                                    id="nominaltunjangandir"
                                    value="<?= isset($row['nominaltunjangandir']) ? number_format($row['nominaltunjangandir'], 0, ',', '.') : '' ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="terimatunjangandekom">Ubah Jumlah Komisaris Penerima Tunjangan:</label>
                            <input class="form-control" type="number" name="terimatunjangandekom" id="terimatunjangandekom"
                                min="0"
                                value="<?= isset($row['terimatunjangandekom']) ? htmlspecialchars($row['terimatunjangandekom']) : '' ?>">
                        </div>
                        <div class="mb-3">
                            <label for="nominaltunjangandekom">Ubah Jumlah Nominal Keseluruhan Tunjangan Komisaris
                                (Rp):</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Rp</span>
                                </div>
                                <input class="form-control rupiah-input" type="text" name="nominaltunjangandekom"
                                    id="nominaltunjangandekom"
                                    value="<?= isset($row['nominaltunjangandekom']) ? number_format($row['nominaltunjangandekom'], 0, ',', '.') : '' ?>">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" name="ubahtunjangan" class="btn btn-primary">Ubah Data</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Format Rupiah function
            const formatRupiah = (input) => {
                let value = input.value.replace(/\D/g, '');
                if (value.length > 0) {
                    value = parseInt(value, 10).toLocaleString('id-ID');
                }
                input.value = value;
            };

            // Initialize Rupiah formatting
            document.querySelectorAll('.rupiah-input').forEach(input => {
                input.addEventListener('input', function () {
                    formatRupiah(this);
                });

                input.addEventListener('focus', function () {
                    formatRupiah(this);
                });
            });

            // Clean up values before form submission
            document.getElementById('tunjanganForm').addEventListener('submit', function (e) {
                document.querySelectorAll('.rupiah-input').forEach(input => {
                    input.value = input.value.replace(/\D/g, '');
                });
            });

            // Prevent negative numbers
            document.getElementById('terimatunjangandir').addEventListener('change', function () {
                if (this.value < 0) this.value = 0;
            });

            document.getElementById('terimatunjangandekom').addEventListener('change', function () {
                if (this.value < 0) this.value = 0;
            });
        });
    </script>
<?php } ?>

<?php if (!empty($paketkebijakandirdekom)) { ?>
    <div class="modal fade" id="modalUbahtantiem">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Tantiem Bagi Direksi dan Dewan Komisaris</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('paketkebijakandirdekom/ubahtantiem'); ?>" method="post" id="tantiemForm">
                        <input type="hidden" name="id" id="id-paketkebijakandirdekom">
                        <div class="mb-3">
                            <label for="terimatantiemdir">Ubah Jumlah Direksi Penerima Tantiem:</label>
                            <input class="form-control" type="number" name="terimatantiemdir" id="terimatantiemdir" min="0"
                                value="<?= isset($row['terimatantiemdir']) ? htmlspecialchars($row['terimatantiemdir']) : '' ?>">
                        </div>
                        <div class="mb-3">
                            <label for="nominaltantiemdir">Ubah Jumlah Nominal Keseluruhan Tantiem Direksi (Rp):</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Rp</span>
                                </div>
                                <input class="form-control rupiah-input" type="text" name="nominaltantiemdir"
                                    id="nominaltantiemdir"
                                    value="<?= isset($row['nominaltantiemdir']) ? number_format($row['nominaltantiemdir'], 0, ',', '.') : '' ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="terimatantiemdekom">Ubah Jumlah Komisaris Penerima Tantiem:</label>
                            <input class="form-control" type="number" name="terimatantiemdekom" id="terimatantiemdekom"
                                min="0"
                                value="<?= isset($row['terimatantiemdekom']) ? htmlspecialchars($row['terimatantiemdekom']) : '' ?>">
                        </div>
                        <div class="mb-3">
                            <label for="nominaltantiemdekom">Ubah Jumlah Nominal Keseluruhan Tantiem Komisaris (Rp):</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Rp</span>
                                </div>
                                <input class="form-control rupiah-input" type="text" name="nominaltantiemdekom"
                                    id="nominaltantiemdekom"
                                    value="<?= isset($row['nominaltantiemdekom']) ? number_format($row['nominaltantiemdekom'], 0, ',', '.') : '' ?>">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" name="ubahtantiem" class="btn btn-primary">Ubah Data</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Format Rupiah function
            const formatRupiah = (input) => {
                let value = input.value.replace(/\D/g, '');
                if (value.length > 0) {
                    value = parseInt(value, 10).toLocaleString('id-ID');
                }
                input.value = value;
            };

            // Initialize Rupiah formatting
            document.querySelectorAll('.rupiah-input').forEach(input => {
                input.addEventListener('input', function () {
                    formatRupiah(this);
                });

                input.addEventListener('focus', function () {
                    formatRupiah(this);
                });
            });

            // Clean up values before form submission
            document.getElementById('tantiemForm').addEventListener('submit', function (e) {
                document.querySelectorAll('.rupiah-input').forEach(input => {
                    input.value = input.value.replace(/\D/g, '');
                });
            });

            // Prevent negative numbers
            document.getElementById('terimatantiemdir').addEventListener('change', function () {
                if (this.value < 0) this.value = 0;
            });

            document.getElementById('terimatantiemdekom').addEventListener('change', function () {
                if (this.value < 0) this.value = 0;
            });
        });
    </script>
<?php } ?>

<?php if (!empty($paketkebijakandirdekom)) { ?>
    <div class="modal fade" id="modalUbahsaham">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Kompensasi Berbasis Saham Bagi Direksi dan Dewan Komisaris</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('paketkebijakandirdekom/ubahsaham'); ?>" method="post" id="sahamForm">
                        <input type="hidden" name="id" id="id-paketkebijakandirdekom">
                        <div class="mb-3">
                            <label for="terimashmdir">Ubah Jumlah Direksi Penerima Kompensasi Berbasis Saham:</label>
                            <input class="form-control" type="number" name="terimashmdir" id="terimashmdir" min="0"
                                value="<?= isset($row['terimashmdir']) ? htmlspecialchars($row['terimashmdir']) : '' ?>">
                        </div>
                        <div class="mb-3">
                            <label for="nominalshmdir">Ubah Jumlah Nominal Keseluruhan Kompensasi Berbasis Saham Direksi
                                (Rp):</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Rp</span>
                                </div>
                                <input class="form-control rupiah-input" type="text" name="nominalshmdir" id="nominalshmdir"
                                    value="<?= isset($row['nominalshmdir']) ? number_format($row['nominalshmdir'], 0, ',', '.') : '' ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="terimashmdekom">Ubah Jumlah Komisaris Penerima Kompensasi Berbasis Saham:</label>
                            <input class="form-control" type="number" name="terimashmdekom" id="terimashmdekom" min="0"
                                value="<?= isset($row['terimashmdekom']) ? htmlspecialchars($row['terimashmdekom']) : '' ?>">
                        </div>
                        <div class="mb-3">
                            <label for="nominalshmdekom">Ubah Jumlah Nominal Keseluruhan Kompensasi Berbasis Saham Komisaris
                                (Rp):</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Rp</span>
                                </div>
                                <input class="form-control rupiah-input" type="text" name="nominalshmdekom"
                                    id="nominalshmdekom"
                                    value="<?= isset($row['nominalshmdekom']) ? number_format($row['nominalshmdekom'], 0, ',', '.') : '' ?>">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" name="ubahsaham" class="btn btn-primary">Ubah Data</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Format Rupiah function
            const formatRupiah = (input) => {
                let value = input.value.replace(/\D/g, '');
                if (value.length > 0) {
                    value = parseInt(value, 10).toLocaleString('id-ID');
                }
                input.value = value;
            };

            // Initialize Rupiah formatting
            document.querySelectorAll('.rupiah-input').forEach(input => {
                input.addEventListener('input', function () {
                    formatRupiah(this);
                });

                input.addEventListener('focus', function () {
                    formatRupiah(this);
                });
            });

            // Clean up values before form submission
            document.getElementById('sahamForm').addEventListener('submit', function (e) {
                document.querySelectorAll('.rupiah-input').forEach(input => {
                    input.value = input.value.replace(/\D/g, '');
                });
            });

            // Prevent negative numbers
            document.getElementById('terimashmdir').addEventListener('change', function () {
                if (this.value < 0) this.value = 0;
            });

            document.getElementById('terimashmdekom').addEventListener('change', function () {
                if (this.value < 0) this.value = 0;
            });
        });
    </script>
<?php } ?>

<?php if (!empty($paketkebijakandirdekom)) { ?>
    <div class="modal fade" id="modalUbahremun">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Remunerasi Lainnya Bagi Direksi dan Dewan Komisaris</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('paketkebijakandirdekom/ubahremun'); ?>" method="post" id="remunForm">
                        <input type="hidden" name="id" id="id-paketkebijakandirdekom">
                        <div class="mb-3">
                            <label for="terimaremunlaindir">Ubah Jumlah Direksi Penerima Remunerasi Lainnya:</label>
                            <input class="form-control" type="number" name="terimaremunlaindir" id="terimaremunlaindir"
                                min="0"
                                value="<?= isset($row['terimaremunlaindir']) ? htmlspecialchars($row['terimaremunlaindir']) : '' ?>">
                        </div>
                        <div class="mb-3">
                            <label for="nominalremunlaindir">Ubah Jumlah Nominal Keseluruhan Remunerasi Lainnya Direksi
                                (Rp):</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Rp</span>
                                </div>
                                <input class="form-control rupiah-input" type="text" name="nominalremunlaindir"
                                    id="nominalremunlaindir"
                                    value="<?= isset($row['nominalremunlaindir']) ? number_format($row['nominalremunlaindir'], 0, ',', '.') : '' ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="terimaremunlaindekom">Ubah Jumlah Komisaris Penerima Remunerasi Lainnya:</label>
                            <input class="form-control" type="number" name="terimaremunlaindekom" id="terimaremunlaindekom"
                                min="0"
                                value="<?= isset($row['terimaremunlaindekom']) ? htmlspecialchars($row['terimaremunlaindekom']) : '' ?>">
                        </div>
                        <div class="mb-3">
                            <label for="nominalremunlaindekom">Ubah Jumlah Nominal Keseluruhan Remunerasi Lainnya Komisaris
                                (Rp):</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Rp</span>
                                </div>
                                <input class="form-control rupiah-input" type="text" name="nominalremunlaindekom"
                                    id="nominalremunlaindekom"
                                    value="<?= isset($row['nominalremunlaindekom']) ? number_format($row['nominalremunlaindekom'], 0, ',', '.') : '' ?>">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" name="ubahremun" class="btn btn-primary">Ubah Data</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Format Rupiah function
            const formatRupiah = (input) => {
                let value = input.value.replace(/\D/g, '');
                if (value.length > 0) {
                    value = parseInt(value, 10).toLocaleString('id-ID');
                }
                input.value = value;
            };

            // Initialize Rupiah formatting
            document.querySelectorAll('.rupiah-input').forEach(input => {
                input.addEventListener('input', function () {
                    formatRupiah(this);
                });

                input.addEventListener('focus', function () {
                    formatRupiah(this);
                });
            });

            // Clean up values before form submission
            document.getElementById('remunForm').addEventListener('submit', function (e) {
                document.querySelectorAll('.rupiah-input').forEach(input => {
                    input.value = input.value.replace(/\D/g, '');
                });
            });

            // Prevent negative numbers
            document.getElementById('terimaremunlaindir').addEventListener('change', function () {
                if (this.value < 0) this.value = 0;
            });

            document.getElementById('terimaremunlaindekom').addEventListener('change', function () {
                if (this.value < 0) this.value = 0;
            });
        });
    </script>
<?php } ?>

<?php if (!empty($paketkebijakandirdekom)) { ?>
    <div class="modal fade" id="modalUbahrumah">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Perumahan Bagi Direksi dan Dewan Komisaris</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('paketkebijakandirdekom/ubahrumah'); ?>" method="post" id="rumahForm">
                        <input type="hidden" name="id" id="id-paketkebijakandirdekom">
                        <div class="mb-3">
                            <label for="terimarumahdir">Ubah Jumlah Direksi Penerima Perumahan (Orang):</label>
                            <input class="form-control" type="number" name="terimarumahdir" id="terimarumahdir" min="0"
                                value="<?= isset($row['terimarumahdir']) ? htmlspecialchars($row['terimarumahdir']) : '' ?>">
                        </div>
                        <div class="mb-3">
                            <label for="nominalrumahdir">Ubah Jumlah Nominal Perumahan Direksi (Rp):</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Rp</span>
                                </div>
                                <input class="form-control rupiah-input" type="text" name="nominalrumahdir"
                                    id="nominalrumahdir"
                                    value="<?= isset($row['nominalrumahdir']) ? number_format($row['nominalrumahdir'], 0, ',', '.') : '' ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="terimarumahdekom">Ubah Jumlah Komisaris Penerima Perumahan (Orang):</label>
                            <input class="form-control" type="number" name="terimarumahdekom" id="terimarumahdekom" min="0"
                                value="<?= isset($row['terimarumahdekom']) ? htmlspecialchars($row['terimarumahdekom']) : '' ?>">
                        </div>
                        <div class="mb-3">
                            <label for="nominalrumahdekom">Ubah Jumlah Nominal Perumahan Komisaris (Rp):</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Rp</span>
                                </div>
                                <input class="form-control rupiah-input" type="text" name="nominalrumahdekom"
                                    id="nominalrumahdekom"
                                    value="<?= isset($row['nominalrumahdekom']) ? number_format($row['nominalrumahdekom'], 0, ',', '.') : '' ?>">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" name="ubahrumah" class="btn btn-primary">Ubah Data</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Format Rupiah function
            const formatRupiah = (input) => {
                let value = input.value.replace(/\D/g, '');
                if (value.length > 0) {
                    value = parseInt(value, 10).toLocaleString('id-ID');
                }
                input.value = value;
            };

            // Initialize Rupiah formatting
            document.querySelectorAll('.rupiah-input').forEach(input => {
                input.addEventListener('input', function () {
                    formatRupiah(this);
                });

                input.addEventListener('focus', function () {
                    formatRupiah(this);
                });
            });

            // Clean up values before form submission
            document.getElementById('rumahForm').addEventListener('submit', function (e) {
                document.querySelectorAll('.rupiah-input').forEach(input => {
                    input.value = input.value.replace(/\D/g, '');
                });
            });

            // Prevent negative numbers
            document.getElementById('terimarumahdir').addEventListener('change', function () {
                if (this.value < 0) this.value = 0;
            });

            document.getElementById('terimarumahdekom').addEventListener('change', function () {
                if (this.value < 0) this.value = 0;
            });
        });
    </script>
<?php } ?>

<?php if (!empty($paketkebijakandirdekom)) { ?>
    <div class="modal fade" id="modalUbahtransport">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Transportasi Bagi Direksi dan Dewan Komisaris</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('paketkebijakandirdekom/ubahtransport'); ?>" method="post"
                        id="transportForm">
                        <input type="hidden" name="id" id="id-paketkebijakandirdekom">
                        <div class="mb-3">
                            <label for="terimatransportdir">Ubah Jumlah Direksi Penerima Transportasi (Orang):</label>
                            <input class="form-control" type="number" name="terimatransportdir" id="terimatransportdir"
                                min="0"
                                value="<?= isset($row['terimatransportdir']) ? htmlspecialchars($row['terimatransportdir']) : '' ?>">
                        </div>
                        <div class="mb-3">
                            <label for="nominaltransportdir">Ubah Jumlah Nominal Transportasi Direksi (Rp):</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Rp</span>
                                </div>
                                <input class="form-control rupiah-input" type="text" name="nominaltransportdir"
                                    id="nominaltransportdir"
                                    value="<?= isset($row['nominaltransportdir']) ? number_format($row['nominaltransportdir'], 0, ',', '.') : '' ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="terimatransportdekom">Ubah Jumlah Komisaris Penerima Transportasi (Orang):</label>
                            <input class="form-control" type="number" name="terimatransportdekom" id="terimatransportdekom"
                                min="0"
                                value="<?= isset($row['terimatransportdekom']) ? htmlspecialchars($row['terimatransportdekom']) : '' ?>">
                        </div>
                        <div class="mb-3">
                            <label for="nominaltransportdekom">Ubah Jumlah Nominal Transportasi Komisaris (Rp):</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Rp</span>
                                </div>
                                <input class="form-control rupiah-input" type="text" name="nominaltransportdekom"
                                    id="nominaltransportdekom"
                                    value="<?= isset($row['nominaltransportdekom']) ? number_format($row['nominaltransportdekom'], 0, ',', '.') : '' ?>">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" name="ubahtransport" class="btn btn-primary">Ubah Data</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Format Rupiah function
            const formatRupiah = (input) => {
                let value = input.value.replace(/\D/g, '');
                if (value.length > 0) {
                    value = parseInt(value, 10).toLocaleString('id-ID');
                }
                input.value = value;
            };

            // Initialize Rupiah formatting
            document.querySelectorAll('.rupiah-input').forEach(input => {
                input.addEventListener('input', function () {
                    formatRupiah(this);
                });

                input.addEventListener('focus', function () {
                    formatRupiah(this);
                });
            });

            // Clean up values before form submission
            document.getElementById('transportForm').addEventListener('submit', function (e) {
                document.querySelectorAll('.rupiah-input').forEach(input => {
                    input.value = input.value.replace(/\D/g, '');
                });
            });

            // Prevent negative numbers
            document.getElementById('terimatransportdir').addEventListener('change', function () {
                if (this.value < 0) this.value = 0;
            });

            document.getElementById('terimatransportdekom').addEventListener('change', function () {
                if (this.value < 0) this.value = 0;
            });
        });
    </script>
<?php } ?>

<?php if (!empty($paketkebijakandirdekom)) { ?>
    <div class="modal fade" id="modalUbahasuransi">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Asuransi Kesehatan Bagi Direksi dan Dewan Komisaris</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('paketkebijakandirdekom/ubahasuransi'); ?>" method="post" id="asuransiForm">
                        <input type="hidden" name="id" id="id-paketkebijakandirdekom">
                        <div class="mb-3">
                            <label for="terimaasuransidir">Ubah Jumlah Direksi Penerima Asuransi Kesehatan (Orang):</label>
                            <input class="form-control" type="number" name="terimaasuransidir" id="terimaasuransidir"
                                min="0"
                                value="<?= isset($row['terimaasuransidir']) ? htmlspecialchars($row['terimaasuransidir']) : '' ?>">
                        </div>
                        <div class="mb-3">
                            <label for="nominalasuransidir">Ubah Jumlah Nominal Asuransi Kesehatan Direksi (Rp):</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Rp</span>
                                </div>
                                <input class="form-control rupiah-input" type="text" name="nominalasuransidir"
                                    id="nominalasuransidir"
                                    value="<?= isset($row['nominalasuransidir']) ? number_format($row['nominalasuransidir'], 0, ',', '.') : '' ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="terimaasuransidekom">Ubah Jumlah Komisaris Penerima Asuransi Kesehatan
                                (Orang):</label>
                            <input class="form-control" type="number" name="terimaasuransidekom" id="terimaasuransidekom"
                                min="0"
                                value="<?= isset($row['terimaasuransidekom']) ? htmlspecialchars($row['terimaasuransidekom']) : '' ?>">
                        </div>
                        <div class="mb-3">
                            <label for="nominalasuransidekom">Ubah Jumlah Nominal Asuransi Kesehatan Komisaris (Rp):</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Rp</span>
                                </div>
                                <input class="form-control rupiah-input" type="text" name="nominalasuransidekom"
                                    id="nominalasuransidekom"
                                    value="<?= isset($row['nominalasuransidekom']) ? number_format($row['nominalasuransidekom'], 0, ',', '.') : '' ?>">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" name="ubahasuransi" class="btn btn-primary">Ubah Data</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Format Rupiah function
            const formatRupiah = (input) => {
                let value = input.value.replace(/\D/g, '');
                if (value.length > 0) {
                    value = parseInt(value, 10).toLocaleString('id-ID');
                }
                input.value = value;
            };

            // Initialize Rupiah formatting
            document.querySelectorAll('.rupiah-input').forEach(input => {
                input.addEventListener('input', function () {
                    formatRupiah(this);
                });

                input.addEventListener('focus', function () {
                    formatRupiah(this);
                });
            });

            // Clean up values before form submission
            document.getElementById('asuransiForm').addEventListener('submit', function (e) {
                document.querySelectorAll('.rupiah-input').forEach(input => {
                    input.value = input.value.replace(/\D/g, '');
                });
            });

            // Prevent negative numbers
            document.getElementById('terimaasuransidir').addEventListener('change', function () {
                if (this.value < 0) this.value = 0;
            });

            document.getElementById('terimaasuransidekom').addEventListener('change', function () {
                if (this.value < 0) this.value = 0;
            });
        });
    </script>
<?php } ?>

<?php if (!empty($paketkebijakandirdekom)) { ?>
    <div class="modal fade" id="modalUbahfasilitas">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Fasilitas Lain-Lain Bagi Direksi dan Dewan Komisaris</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('paketkebijakandirdekom/ubahfasilitas'); ?>" method="post"
                        id="fasilitasForm">
                        <input type="hidden" name="id" id="id-paketkebijakandirdekom">
                        <div class="mb-3">
                            <label for="terimafasilitasdir">Ubah Jumlah Direksi Penerima Fasilitas Lain-Lain
                                (Orang):</label>
                            <input class="form-control" type="number" name="terimafasilitasdir" id="terimafasilitasdir"
                                min="0"
                                value="<?= isset($row['terimafasilitasdir']) ? htmlspecialchars($row['terimafasilitasdir']) : '' ?>">
                        </div>
                        <div class="mb-3">
                            <label for="nominalfasilitasdir">Ubah Jumlah Nominal Fasilitas Lain-Lain Direksi (Rp):</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Rp</span>
                                </div>
                                <input class="form-control rupiah-input" type="text" name="nominalfasilitasdir"
                                    id="nominalfasilitasdir"
                                    value="<?= isset($row['nominalfasilitasdir']) ? number_format($row['nominalfasilitasdir'], 0, ',', '.') : '' ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="terimafasilitasdekom">Ubah Jumlah Komisaris Penerima Fasilitas Lain-Lain
                                (Orang):</label>
                            <input class="form-control" type="number" name="terimafasilitasdekom" id="terimafasilitasdekom"
                                min="0"
                                value="<?= isset($row['terimafasilitasdekom']) ? htmlspecialchars($row['terimafasilitasdekom']) : '' ?>">
                        </div>
                        <div class="mb-3">
                            <label for="nominalfasilitasdekom">Ubah Jumlah Nominal Fasilitas Lain-Lain Komisaris
                                (Rp):</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Rp</span>
                                </div>
                                <input class="form-control rupiah-input" type="text" name="nominalfasilitasdekom"
                                    id="nominalfasilitasdekom"
                                    value="<?= isset($row['nominalfasilitasdekom']) ? number_format($row['nominalfasilitasdekom'], 0, ',', '.') : '' ?>">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" name="ubahfasilitas" class="btn btn-primary">Ubah Data</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Format Rupiah function
            const formatRupiah = (input) => {
                let value = input.value.replace(/\D/g, '');
                if (value.length > 0) {
                    value = parseInt(value, 10).toLocaleString('id-ID');
                }
                input.value = value;
            };

            // Initialize Rupiah formatting
            document.querySelectorAll('.rupiah-input').forEach(input => {
                input.addEventListener('input', function () {
                    formatRupiah(this);
                });

                input.addEventListener('focus', function () {
                    formatRupiah(this);
                });
            });

            // Clean up values before form submission
            document.getElementById('fasilitasForm').addEventListener('submit', function (e) {
                document.querySelectorAll('.rupiah-input').forEach(input => {
                    input.value = input.value.replace(/\D/g, '');
                });
            });

            // Prevent negative numbers
            document.getElementById('terimafasilitasdir').addEventListener('change', function () {
                if (this.value < 0) this.value = 0;
            });

            document.getElementById('terimafasilitasdekom').addEventListener('change', function () {
                if (this.value < 0) this.value = 0;
            });
        });
    </script>
<?php } ?>


<?php if (!empty($paketkebijakandirdekom)) { ?>
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
                    <form action="<?= base_url('paketkebijakandirdekom/ubahketerangan'); ?>" method="post">
                        <input type="hidden" name="id" id="id-paketkebijakandirdekom">
                        <div class="form-group">
                            <label for="keterangan" class="form-label">Keterangan: </label>
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
                <form action="<?= base_url('paketkebijakandirdekom/tambahkomentar'); ?>" method="post">
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
                            <a href="<?= base_url('paketkebijakandirdekom/approveSemua') ?>"
                                class="btn btn-success shadow mt-3 mx-2"
                                onclick="return confirm('Apakah Anda yakin hendak melakukan approval?');">
                                Approve
                            </a>
                            <a href="<?= base_url('paketkebijakandirdekom/unapproveSemua') ?>"
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
<div class="modal fade" id="modalHapuspaketkebijakandirdekom">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                Apakah anda yakin ingin menghapus data no-<span id="idData"></span>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btnHapuspaketkebijakandirdekom">Yakin</button>
            </div>
        </div>
    </div>
</div>