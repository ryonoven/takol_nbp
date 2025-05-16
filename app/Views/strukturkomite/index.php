<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-4 text-gray-800"><?= $judul; ?></h1>
        <span>
            <?php
            $allApproved = true;
            foreach ($strukturkomite as $item) {
                if ($item['is_approved'] != 1) {
                    $allApproved = false;
                    break;
                }
            }
            ?>
            <?php if ($allApproved): ?>
                <span class="badge badge-success" style="font-size: 14px;">
                    Disetujui oleh <strong><?= esc($fullname ?? '-') ?></strong><br>
                    <?= esc($strukturkomite[0]['approved_at'] ?? '-') ?>
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
                        data-target="#modalTambahstrukturkomite"><i class="fa fa-plus"> Tambah Data</i></button>
                </div>
                <div class="col-md">
                    <!-- <button onclick="window.print()"
                    class="btn btn-outline-secondary shadow float-right mr-3 ml-2 mt-3">Print <i
                        class="fa fa-print"></i> </button>
                <a href="/strukturkomite/excel" class="btn btn-outline-success shadow float-right mt-3">Excel <i
                        class="fa fa-file-excel"></i></a> -->
                    <a href="/strukturkomite/exporttxtstrukturkomite"
                        class="btn btn-outline-secondary shadow float-right mr-3 ml-2 mt-3">Export TXT <i
                            class="fa fa-file-alt"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <table class="table table-primary">
            <th>Daftar Struktur, Keanggotaan, Keahlian, dan Independensi Anggota Komite</th>
        </table>
        <table class="table table-bordered table-secondary">
            <tbody>
                <?php if (empty($strukturkomite)) { ?>
                    <tr>
                        <th style="width: 30%;">Nama Anggota Komite :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">NIK :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Keahlian :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Jabatan Dalam Komite Audit :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Jabatan Dalam Komite Pemantau Risiko :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Jabatan Dalam Komite Remunerasi dan Nominasi :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Jabatan Dalam Komite Lainnya :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Apakah Merupakan Pihak Independen? :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($strukturkomite as $row): ?>
                        <tr>
                            <th style="width: 3%; background-color: #a3b6ee;" rowspan="10"><?= $row['id']; ?></th>
                            <th style="width: 25%;">Nama Anggota Komite :</th>
                            <td style="width: 75%;"><?= $row['anggotakomite']; ?></td>
                        </tr>
                        <tr>
                            <th style="width: 25%;">NIK :</th>
                            <td style="width: 75%;"><?= $row['nikkomite']; ?></td>
                        </tr>
                        <tr>
                            <th style="width: 25%;">Keahlian :</th>
                            <td style="width: 75%;"><?= $row['keahlian']; ?></td>
                        </tr>
                        <tr>
                            <th style="width: 25%;">Jabatan Dalam Komite Audit :</th>
                            <td style="width: 75%;">
                                <?php
                                $jbtAudit = [
                                    '00' => 'Tidak Menjabat',
                                    '01' => 'Ketua',
                                    '02' => 'Anggota'
                                ];
                                echo isset($jbtAudit[$row['jbtaudit']]) ? $jbtAudit[$row['jbtaudit']] : $row['jbtaudit'];
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th style="width: 25%;">Jabatan Dalam Komite Pemantau Risiko :</th>
                            <td style="width: 75%;">
                                <?php
                                $jbtPantau = [
                                    '00' => 'Tidak Menjabat',
                                    '01' => 'Ketua',
                                    '02' => 'Anggota'
                                ];
                                echo isset($jbtPantau[$row['jbtpantauresiko']]) ? $jbtPantau[$row['jbtpantauresiko']] : $row['jbtpantauresiko'];
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th style="width: 25%;">Jabatan Dalam Komite Remunerasi dan Nominasi :</th>
                            <td style="width: 75%;">
                                <?php
                                $jbtRemun = [
                                    '00' => 'Tidak Menjabat',
                                    '01' => 'Ketua',
                                    '02' => 'Anggota'
                                ];
                                echo isset($jbtRemun[$row['jbtremunerasi']]) ? $jbtRemun[$row['jbtremunerasi']] : $row['jbtremunerasi'];
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th style="width: 25%;">Jabatan Dalam Komite Manajemen Risiko :</th>
                            <td style="width: 75%;">
                                <?php
                                $jbtManrisk = [
                                    '00' => 'Tidak Menjabat',
                                    '01' => 'Ketua',
                                    '02' => 'Anggota'
                                ];
                                echo isset($jbtManrisk[$row['jbtmanrisk']]) ? $jbtManrisk[$row['jbtmanrisk']] : $row['jbtmanrisk'];
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th style="width: 25%;">Jabatan Dalam Komite Lainnya :</th>
                            <td style="width: 75%;">
                                <?php
                                $jbtLain = [
                                    '00' => 'Tidak Menjabat',
                                    '01' => 'Ketua',
                                    '02' => 'Anggota'
                                ];
                                echo isset($jbtLain[$row['jbtlain']]) ? $jbtLain[$row['jbtlain']] : $row['jbtlain'];
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th style="width: 25%;">Apakah Merupakan Pihak Independen :</th>
                            <td style="width: 75%;">
                                <?php
                                $independen = [
                                    '1' => 'Ya',
                                    '2' => 'Tidak'
                                ];
                                echo isset($independen[$row['independen']]) ? $independen[$row['independen']] : $row['independen'];
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                <td colspan="3">
                                    <button type="button" data-toggle="modal" class="btn" data-target="#modalUbah" id="btn-edit"
                                        style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                        data-anggotakomite="<?= $row['anggotakomite']; ?>"
                                        data-nikkomite="<?= $row['nikkomite']; ?>" data-keahlian="<?= $row['keahlian']; ?>"
                                        data-jbtaudit="<?= $row['jbtaudit']; ?>"
                                        data-jbtpantauresiko="<?= $row['jbtpantauresiko']; ?>"
                                        data-jbtremunerasi="<?= $row['jbtremunerasi']; ?>"
                                        data-jbtmanrisk="<?= $row['jbtmanrisk']; ?>" data-jbtlain="<?= $row['jbtlain']; ?>"
                                        data-independen="<?= $row['independen']; ?>"><i class="fa fa-edit"></i>&nbsp;
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
                <?php if (empty($strukturkomite)) { ?>
                    <tr>
                        <th>Tindak Lanjut Rekomendasi Struktur, Keanggotaan, Keahlian, dan Independensi Anggota Komite:
                        </th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($strukturkomite as $row): ?>
                        <?php if ($row['id'] == 1): ?>
                            <tr>
                                <th>Tindak Lanjut Rekomendasi Struktur, Keanggotaan, Keahlian, dan Independensi Anggota Komite:
                                </th>
                            </tr>
                            <tr>
                                <td>
                                    <ol style="list-style: lower-latin;">
                                        <?php
                                        $tindakstrukturkomite = explode("\n", $row['tindakstrukturkomite']);
                                        foreach ($tindakstrukturkomite as $poin):
                                            ?>
                                            <li><?= htmlspecialchars(trim($poin)); ?></li>
                                        <?php endforeach; ?>
                                    </ol>
                                </td>
                            </tr>
                            <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                <button type="button" data-toggle="modal" class="btn btn-primary mt-3"
                                    data-target="#modalUbahketerangan" id="btn-edit" style="font-weight: 600;"
                                    data-id="<?= $row['id']; ?>" data-tindakstrukturkomite="<?= $row['tindakstrukturkomite']; ?>"><i
                                        class="fa fa-edit"> Tindak Lanjut Rekomendasi Struktur, Keanggotaan, Keahlian, dan
                                        Independensi Anggota Komite:</i>&nbsp;
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
<?php if (!empty($strukturkomite)) { ?>
    <div class="modal fade" id="modalUbah">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Daftar Struktur, Keanggotaan, Keahlian, dan Independensi Anggota Komite</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('strukturkomite/ubah'); ?>" method="post">
                        <input type="hidden" name="id" id="id-strukturkomite">
                        <div class="mb-3">
                            <label for="anggotakomite" class="form-label">Input Nama Anggota Komisaris:</label>
                            <input class="form-control" type="text" name="anggotakomite" id="anggotakomite"
                                value="<?= $row['anggotakomite'] ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="nikkomite" class="form-label">Input NIK:</label>
                            <input class="form-control" type="text" name="nikkomite" id="nikkomite"
                                value="<?= $row['nikkomite'] ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="keahlian" class="form-label">Input Keahlian:</label>
                            <input class="form-control" type="text" name="keahlian" id="keahlian"
                                value="<?= $row['keahlian'] ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="jbtaudit" class="form-label">Pilih Jabatan Dalam Komite Audit:</label>
                            <select name="jbtaudit" id="jbtaudit" class="form-control" required>
                                <option value="">-- Pilih Komite --</option>
                                <option value="00" <?= ($row['jbtaudit'] == '00') ? 'selected' : ''; ?>>Tidak Menjabat</option>
                                <option value="01" <?= ($row['jbtaudit'] == '01') ? 'selected' : ''; ?>>Ketua</option>
                                <option value="02" <?= ($row['jbtaudit'] == '02') ? 'selected' : ''; ?>>Anggota</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="jbtpantauresiko" class="form-label">Pilih Jabatan Dalam Komite Pemantau
                                Risiko:</label>
                            <select name="jbtpantauresiko" id="jbtpantauresiko" class="form-control" required>
                                <option value="">-- Pilih Komite --</option>
                                <option value="00" <?= ($row['jbtpantauresiko'] == '00') ? 'selected' : ''; ?>>Tidak Menjabat
                                </option>
                                <option value="01" <?= ($row['jbtpantauresiko'] == '01') ? 'selected' : ''; ?>>Ketua</option>
                                <option value="02" <?= ($row['jbtpantauresiko'] == '02') ? 'selected' : ''; ?>>Anggota</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="jbtremunerasi" class="form-label">Pilih Jabatan Dalam Komite Remunerasi dan
                                Nominasi:</label>
                            <select name="jbtremunerasi" id="jbtremunerasi" class="form-control" required>
                                <option value="">-- Pilih Komite --</option>
                                <option value="00" <?= ($row['jbtremunerasi'] == '00') ? 'selected' : ''; ?>>Tidak Menjabat
                                </option>
                                <option value="01" <?= ($row['jbtremunerasi'] == '01') ? 'selected' : ''; ?>>Ketua</option>
                                <option value="02" <?= ($row['jbtremunerasi'] == '02') ? 'selected' : ''; ?>>Anggota</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="jbtmanrisk" class="form-label">Pilih Jabatan Dalam Komite Manajemen Risiko:</label>
                            <select name="jbtmanrisk" id="jbtmanrisk" class="form-control" required>
                                <option value="">-- Pilih Komite --</option>
                                <option value="00" <?= ($row['jbtmanrisk'] == '00') ? 'selected' : ''; ?>>Tidak Menjabat
                                </option>
                                <option value="01" <?= ($row['jbtmanrisk'] == '01') ? 'selected' : ''; ?>>Ketua</option>
                                <option value="02" <?= ($row['jbtmanrisk'] == '02') ? 'selected' : ''; ?>>Anggota</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="jbtlain" class="form-label">Pilih Jabatan Dalam Komite Lainnya:</label>
                            <select name="jbtlain" id="jbtlain" class="form-control" required>
                                <option value="">-- Pilih Komite --</option>
                                <option value="00" <?= ($row['jbtlain'] == '00') ? 'selected' : ''; ?>>Tidak Menjabat</option>
                                <option value="01" <?= ($row['jbtlain'] == '01') ? 'selected' : ''; ?>>Ketua</option>
                                <option value="02" <?= ($row['jbtlain'] == '02') ? 'selected' : ''; ?>>Anggota</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="independen" class="form-label">Pilih Merupakan Pihak Independen:</label>
                            <select name="independen" id="independen" class="form-control" required>
                                <option value="">-- Pilih Komite --</option>
                                <option value="1" <?= ($row['jbtlain'] == '1') ? 'selected' : ''; ?>>Ya</option>
                                <option value="2" <?= ($row['jbtlain'] == '2') ? 'selected' : ''; ?>>Tidak</option>
                            </select>
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

<?php if (!empty($strukturkomite)) { ?>
    <div class="modal fade" id="modalUbahketerangan">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Tindak Lanjut Rekomendasi Struktur, Keanggotaan, Keahlian, dan Independensi
                        Anggota Komite</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('strukturkomite/ubahketerangan'); ?>" method="post">
                        <input type="hidden" name="id" id="id-strukturkomite">
                        <div class="form-group">
                            <label for="tindakstrukturkomite" class="form-label">Input Tindak Lanjut Dewan Komisaris BPR:
                            </label>
                            <textarea class="form-control" type="text" name="tindakstrukturkomite" id="tindakstrukturkomite"
                                style="height: 150px" placeholder="<?= $row['tindakstrukturkomite'] ?>"></textarea>
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
<div class="modal fade" id="modalTambahstrukturkomite">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Pelaksanaan Tugas dan Tanggung Jawab Anggota Dewan Komisaris </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('strukturkomite/tambahstrukturkomite'); ?>" method="post">
                    <div class="mb-3">
                        <label for="anggotakomite" class="form-label">Input Nama Anggota Komite:</label>
                        <input class="form-control" type="text" name="anggotakomite" id="anggotakomite" required>
                    </div>
                    <div class="mb-3">
                        <label for="nikkomite" class="form-label">Input NIK:</label>
                        <input class="form-control" type="text" name="nikkomite" id="nikkomite" required>
                    </div>
                    <div class="mb-3">
                        <label for="keahlian" class="form-label">Input Keahlian:</label>
                        <input class="form-control" type="text" name="keahlian" id="keahlian" required>
                    </div>
                    <div class="form-group">
                        <label for="jbtaudit">Pilih Jabatan Dalam Komite Audit:</label>
                        <select name="jbtaudit" id="jbtaudit" class="form-control" required>
                            <option value="">-- Pilih Jabatan --</option>
                            <option value="00">Tidak Menjabat</option>
                            <option value="01">Ketua</option>
                            <option value="02">Anggota</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="jbtpantauresiko">Pilih Jabatan Dalam Komite Pemantau Risiko:</label>
                        <select name="jbtpantauresiko" id="jbtpantauresiko" class="form-control" required>
                            <option value="">-- Pilih Jabatan --</option>
                            <option value="00">Tidak Menjabat</option>
                            <option value="01">Ketua</option>
                            <option value="02">Anggota</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="jbtremunerasi">Pilih Jabatan Dalam Komite Komite Remunerasi dan Nominasi:</label>
                        <select name="jbtremunerasi" id="jbtremunerasi" class="form-control" required>
                            <option value="">-- Pilih Jabatan --</option>
                            <option value="00">Tidak Menjabat</option>
                            <option value="01">Ketua</option>
                            <option value="02">Anggota</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="jbtmanrisk">Pilih Jabatan Dalam Komite Komite Manajemen Risiko:</label>
                        <select name="jbtmanrisk" id="jbtmanrisk" class="form-control" required>
                            <option value="">-- Pilih Jabatan --</option>
                            <option value="00">Tidak Menjabat</option>
                            <option value="01">Ketua</option>
                            <option value="02">Anggota</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="jbtlain">Pilih Jabatan Dalam Komite Komite Lainnya:</label>
                        <select name="jbtlain" id="jbtlain" class="form-control" required>
                            <option value="">-- Pilih Jabatan --</option>
                            <option value="00">Tidak Menjabat</option>
                            <option value="01">Ketua</option>
                            <option value="02">Anggota</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="independen">Apakah anggota independen?:</label>
                        <select name="independen" id="independen" class="form-control" required>
                            <option value="">-- Apakah anggota independen? --</option>
                            <option value="1">Ya</option>
                            <option value="2">Tidak</option>
                        </select>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" name="tambahstrukturkomite" class="btn btn-primary">Tambah Data</button>
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
                <form action="<?= base_url('strukturkomite/tambahkomentar'); ?>" method="post">
                    <div class="form-group">
                        <label for="komentar">Komentar Direksi dan Dewan Komisaris:</label>
                        <textarea type="text" name="komentar" id="komentar" class="form-control"
                            style="height: 150px;" readonly></textarea>
                    </div>
                    <?php if ($userInGroupAdmin || $userInGroupDekom || $userInGroupDireksi): ?>
                        <div class="form-group">
                            <label for="komentar">Input Komentar Direksi dan Dewan Komisaris:</label>
                            <textarea type="komentar" name="komentar" id="komentar" class="form-control"
                                style="height: 150px;"></textarea>
                        </div>
                        <div class="col-md d-flex justify-content-center align-items-center" style="margin-top: 20px;">
                            <a href="<?= base_url('strukturkomite/approveSemua') ?>"
                                class="btn btn-success shadow mt-3 mx-2"
                                onclick="return confirm('Apakah Anda yakin hendak melakukan approval?');">
                                Approve
                            </a>
                            <a href="<?= base_url('strukturkomite/unapproveSemua') ?>"
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
<div class="modal fade" id="modalHapusstrukturkomite">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                Apakah anda yakin ingin menghapus data no-<span id="idData"></span>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btnHapusstrukturkomite">Yakin</button>
            </div>
        </div>
    </div>
</div>