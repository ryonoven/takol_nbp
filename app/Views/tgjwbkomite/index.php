<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-4 text-gray-800"><?= $judul; ?></h1>
        <span>
            <?php
            $allApproved = true;
            foreach ($tgjwbkomite as $item) {
                if ($item['is_approved'] != 1) {
                    $allApproved = false;
                    break;
                }
            }
            ?>
            <?php if ($allApproved): ?>
                <span class="badge badge-success" style="font-size: 14px;">
                    Disetujui oleh <strong><?= esc($fullname ?? '-') ?></strong><br>
                    <?= esc($tgjwbkomite[0]['approved_at'] ?? '-') ?>
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
                        data-target="#modalTambahtgjwbkomite"><i class="fa fa-plus"> Tambah Data</i></button>
                </div>
                <div class="col-md">
                    <!-- <button onclick="window.print()"
                    class="btn btn-outline-secondary shadow float-right mr-3 ml-2 mt-3">Print <i
                        class="fa fa-print"></i> </button>
                <a href="/tgjwbkomite/excel" class="btn btn-outline-success shadow float-right mt-3">Excel <i
                        class="fa fa-file-excel"></i></a> -->
                    <a href="/tgjwbkomite/exporttxttgjwbkomite"
                        class="btn btn-outline-secondary shadow float-right mr-3 ml-2 mt-3">Export TXT <i
                            class="fa fa-file-alt"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <table class="table table-primary">
            <th>Tugas, Tanggung Jawab, Program Kerja, dan Realisasi Program Kerja Komite</th>
        </table>
        <table class="table table-bordered table-secondary">
            <tbody>
                <?php if (empty($tgjwbkomite)) { ?>
                    <tr>
                        <th style="width: 30%;">Komite :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Penjelasan Tugas dan Tanggung Jawab :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr
                        style="background-color: white; border-bottom-style: hidden; border-left-style: hidden; border-right-style: hidden;">
                        <th colspan="2">Program Kerja Komite :</th>
                    </tr>
                    <tr
                        style="background-color: white; border-bottom-style: hidden; border-left-style: hidden; border-right-style: hidden;">
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr
                        style="background-color: white; border-bottom-style: hidden; border-left-style: hidden; border-right-style: hidden;">
                        <th colspan="2">Hasil Program Kerja Komite :</th>
                    </tr>
                    <tr
                        style="background-color: white; border-bottom-style: hidden; border-left-style: hidden; border-right-style: hidden;">
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($tgjwbkomite as $row): ?>
                        <tr>
                            <th style="width: 3%; background-color: #a3b6ee;" rowspan="8"><?= $row['id']; ?></th>
                            <th style="width: 25%;">Komite :</th>
                            <td style="width: 75%;">
                                <?php
                                $namaKomite = [
                                    '01. Komite Audit' => '01. Komite Audit',
                                    '02. Komite Pemantau Risiko' => '02. Komite Pemantau Risiko',
                                    '03. Komite Remunerasi dan Nominasi' => '03. Komite Remunerasi dan Nominasi',
                                    '04. Komite Manajemen Risiko' => '04. Komite Manajemen Risiko',
                                    '05. Komite Lainnya**' => '05. Komite Lainnya**'
                                ];
                                echo isset($namaKomite[$row['komite']]) ? $namaKomite[$row['komite']] : $row['komite'];
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th style="width: 25%;">Penjelasan Tugas dan Tanggung Jawab :</th>
                            <td style="width: 75%;"><?= $row['tugastgjwbkomite']; ?></td>
                        </tr>
                        <tr>
                            <th style="width: 25%;">Jumlah Rapat :</th>
                            <td style="width: 75%;"><?= $row['jumlahrapat']; ?></td>
                        </tr>
                        <tr
                            style="background-color: white; border-bottom-style: hidden; border-left-style: hidden; border-right-style: hidden;">
                            <th colspan="2">Program Kerja Komite :</th>
                        </tr>
                        <tr
                            style="background-color: white; border-bottom-style: hidden; border-left-style: hidden; border-right-style: hidden;">
                            <td colspan="2">
                                <ol style="list-style: lower-latin;">
                                    <?php
                                    $prokerkomite = explode("\n", $row['prokerkomite']);
                                    foreach ($prokerkomite as $poin) {
                                        echo '<li>' . htmlspecialchars(trim($poin)) . '</li>';
                                    }
                                    ?>
                                </ol>
                            </td>
                        </tr>
                        <tr
                            style="background-color: white; border-bottom-style: hidden; border-left-style: hidden; border-right-style: hidden;">
                            <th colspan="2">Realisasi :</th>
                        </tr>
                        <tr
                            style="background-color: white; border-bottom-style: hidden; border-left-style: hidden; border-right-style: hidden;">
                            <td colspan="2">
                                <ol style="list-style: lower-latin;">
                                    <?php
                                    $hasilprokerkomite = explode("\n", $row['hasilprokerkomite']);
                                    foreach ($hasilprokerkomite as $poin) {
                                        echo '<li>' . htmlspecialchars(trim($poin)) . '</li>';
                                    }
                                    ?>
                                </ol>
                            </td>
                        </tr>
                        <tr>
                            <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                style="background-color: white; border-bottom-style: hidden; border-left-style: hidden;
                                border-right-style: hidden;">
                                <td colspan="3">
                                    <button type="button" data-toggle="modal" class="btn" data-target="#modalUbah" id="btn-edit"
                                        style="font-weight: 600;" data-id="<?= $row['id']; ?>" data-komite="<?= $row['komite']; ?>"
                                        data-tugastgjwbkomite="<?= $row['tugastgjwbkomite']; ?>"
                                        data-jumlahrapat="<?= $row['jumlahrapat']; ?>"
                                        data-prokerkomite="<?= $row['prokerkomite']; ?>"
                                        data-hasilprokerkomite="<?= $row['hasilprokerkomite']; ?>"><i class="fa fa-edit"></i>&nbsp;
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
                <?php if (empty($tgjwbkomite)) { ?>
                    <tr>
                        <th>Tindak Lanjut Rekomendasi Dewan Dewan Komisaris:</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($tgjwbkomite as $row): ?>
                        <?php if ($row['id'] == 1): ?>
                            <tr>
                                <th>Tindak Lanjut Rekomendasi Dewan Dewan Komisaris:</th>
                            </tr>
                            <tr>
                                <td>
                                    <ol style="list-style: lower-latin;">
                                        <?php
                                        $tindakkomite = explode("\n", $row['tindakkomite']);
                                        foreach ($tindakkomite as $poin):
                                            ?>
                                            <li><?= htmlspecialchars(trim($poin)); ?></li>
                                        <?php endforeach; ?>
                                    </ol>
                                </td>
                            </tr>
                            <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                <button type="button" data-toggle="modal" class="btn btn-primary mt-3"
                                    data-target="#modalUbahketerangan" id="btn-edit" style="font-weight: 600;"
                                    data-id="<?= $row['id']; ?>" data-tindakkomite="<?= $row['tindakkomite']; ?>"><i class="fa fa-edit">
                                        Tambah Tindak Lanjut
                                        Dewan Komisaris</i>&nbsp;
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <!-- End Page Content -->

    <!--edit data-->
    <?php if (!empty($tgjwbkomite)) { ?>
        <div class="modal fade" id="modalUbah">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Ubah Tambah Pelaksanaan Tugas dan Tanggung Jawab Anggota Dewan Komisaris
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="<?= base_url('tgjwbkomite/ubah'); ?>" method="post">
                            <input type="hidden" name="id" id="id-tgjwbkomite">
                            <div class="mb-3">
                                <label for="komite" class="form-label">Pilih Komite:</label>
                                <select name="komite" id="komite" class="form-control">
                                    <option value="">-- Pilih Komite --</option>
                                    <option value="01. Komite Audit" <?= ($row['komite'] == '01. Komite Audit') ? 'selected' : ''; ?>>01. Komite Audit</option>
                                    <option value="02. Komite Pemantau Risiko" <?= ($row['komite'] == '02. Komite Pemantau Risiko') ? 'selected' : ''; ?>>02. Komite Pemantau
                                        Risiko</option>
                                    <option value="03. Komite Remunerasi dan Nominasi" <?= ($row['komite'] == '03. Komite Remunerasi dan Nominasi') ? 'selected' : ''; ?>>03. Komite Remunerasi dan
                                        Nominasi</option>
                                    <option value="04. Komite Manajemen Risiko" <?= ($row['komite'] == '04. Komite Manajemen Risiko') ? 'selected' : ''; ?>>04. Komite Manajemen
                                        Risiko</option>
                                    <option value="05. Komite Lainnya**" <?= ($row['komite'] == '05. Komite Lainnya**') ? 'selected' : ''; ?>>05. 05. Komite Lainnya**
                                    </option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="tugastgjwbkomite" class="form-label">Input Penjelasan Tugas dan Tanggung Jawab:
                                </label>
                                <textarea class="form-control" type="text" name="tugastgjwbkomite" id="tugastgjwbkomite"
                                    style="height: 100px" placeholder="<?= $row['tugastgjwbkomite'] ?>"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="jumlahrapat" class="form-label">Input Jumlah Rapat:</label>
                                <input class="form-control" type="text" name="jumlahrapat" id="jumlahrapat"
                                    placeholder="<?= $row['jumlahrapat'] ?>">
                                <small> Note: Isi menggunakan angka saja</small>
                            </div>
                            <div class="form-group">
                                <label for="prokerkomite" class="form-label">Program Kerja Komite: </label>
                                <textarea class="form-control" type="text" name="prokerkomite" id="prokerkomite"
                                    style="height: 100px" placeholder="<?= $row['prokerkomite'] ?>"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="hasilprokerkomite" class="form-label">Realisasi: </label>
                                <textarea class="form-control" type="text" name="hasilprokerkomite" id="hasilprokerkomite"
                                    style="height: 100px" placeholder="<?= $row['hasilprokerkomite'] ?>"></textarea>
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

    <?php if (!empty($tgjwbkomite)) { ?>
        <div class="modal fade" id="modalUbahketerangan">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Ubah Tambah Pelaksanaan Tugas dan Tanggung Jawab Anggota Dewan Komisaris
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="<?= base_url('tgjwbkomite/ubahketerangan'); ?>" method="post">
                            <input type="hidden" name="id" id="id-tgjwbkomite">
                            <div class="form-group">
                                <label for="tindakkomite" class="form-label">Input Tindak Lanjut Rekomendasi Program Kerja
                                    dan
                                    Realisasi Program Kerja Komite:</label>
                                <textarea class="form-control" type="text" name="tindakkomite" id="tindakkomite"
                                    style="height: 150px" placeholder="<?= $row['tindakkomite'] ?>"></textarea>
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
    <div class="modal fade" id="modalTambahtgjwbkomite">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Pelaksanaan Tugas, Tanggung Jawab, Program Kerja, dan Realisasi
                        Program
                        Kerja Komite </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('tgjwbkomite/tambahtgjwbkomite'); ?>" method="post">
                        <div class="form-group">
                            <label for="komite">Pilih Nama Komite:</label>
                            <select name="komite" id="komite" class="form-control">
                                <option value="">-- Pilih Komite --</option>
                                <option value="01. Komite Audit">01. Komite Audit</option>
                                <option value="02. Komite Pemantau Risiko">02. Komite Pemantau Risiko</option>
                                <option value="03. Komite Remunerasi dan Nominasi">03. Komite Remunerasi dan Nominasi
                                </option>
                                <option value="04. Komite Manajemen Risiko">04. Komite Manajemen Risiko</option>
                                <option value="05. Komite Lainnya**">05. Komite Lainnya**</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="tugastgjwbkomite">Input Penjelasan Tugas dan tanggung jawab: </label>
                            <textarea class="form-control" type="text" name="tugastgjwbkomite" id="tugastgjwbkomite"
                                style="height: 100px;"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="jumlahrapat">Input Jumlah Rapat: </label>
                            <input type="text" name="jumlahrapat" id="jumlahrapat" class="form-control"></input>
                            <small> Note: Isi menggunakan angka saja</small>
                        </div>
                        <div class="form-group">
                            <label for="prokerkomite">Input Program Kerja Komite: </label>
                            <textarea class="form-control" type="text" name="prokerkomite" id="prokerkomite"
                                style="height: 100px;"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="hasilprokerkomite">Realisasi: </label>
                            <textarea class="form-control" type="text" name="hasilprokerkomite" id="hasilprokerkomite"
                                style="height: 100px;"></textarea>
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="tambahtgjwbkomite" class="btn btn-primary">Tambah Data</button>
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
                    <form action="<?= base_url('tgjwbkomite/tambahkomentar'); ?>" method="post">
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
                                <a href="<?= base_url('tgjwbkomite/approveSemua') ?>"
                                    class="btn btn-success shadow mt-3 mx-2"
                                    onclick="return confirm('Apakah Anda yakin hendak melakukan approval?');">
                                    Approve
                                </a>
                                <a href="<?= base_url('tgjwbkomite/unapproveSemua') ?>"
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

    <!-- Modal Hapus data -->
    <div class="modal fade" id="modalHapuskomite">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    Apakah anda yakin ingin menghapus data no-<span id="idData"></span>?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="btnHapuskomite">Yakin</button>
                </div>
            </div>
        </div>
    </div>