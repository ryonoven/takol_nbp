<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-4 text-gray-800"><?= $judul; ?></h1>
        <span>
            <?php
            $allApproved = true;
            foreach ($shmdirdekomlain as $item) {
                if ($item['is_approved'] != 1) {
                    $allApproved = false;
                    break;
                }
            }
            ?>
            <?php if ($allApproved): ?>
                <span class="badge badge-success" style="font-size: 14px;">
                    Disetujui oleh <strong><?= esc($fullname ?? '-') ?></strong><br>
                    <?= esc($shmdirdekomlain[0]['approved_at'] ?? '-') ?>
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
                <a href="/shmdirdekomlain/excel" class="btn btn-outline-success shadow float-right mt-3">Excel <i
                        class="fa fa-file-excel"></i></a> -->
                    <a href="/shmdirdekomlain/exporttxtshmdirdekomlain"
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
                <?php if (empty($shmdirdekomlain)) { ?>
                    <tr>
                        <th style="width: 30%;">Nama Direksi :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Jenis Bank/Perusahaan Lain :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Nama Bank/Perusahaan Lain :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Persentase Kepemilikan (%) :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($shmdirdekomlain as $row): ?>
                        <?php
                        $rowspandirValue = ($row['jenisdir'] == 'Bank') ? 6 : 5;
                        if ($row['direksi'] != NULL): ?>
                            <tr>
                                <th style="width: 3%; background-color: #a3b6ee; color: transparent;"
                                    rowspan="<?= $rowspandirValue; ?>">
                                    <?= $row['id']; ?>
                                </th>
                                <th style="width: 30%;">Nama Direksi :</th>
                                <td style="width: 70%;"><?= $row['direksi']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 30%;">Jenis Bank/Perusahaan Lain :</th>
                                <td style="width: 70%;"><?= $row['jenisdir']; ?></td>
                            </tr>
                            <?php if ($row['jenisdir'] == 'Bank'): ?>
                                <tr>
                                    <th style="width: 30%;">Kode Bank :</th>
                                    <td style="width: 70%;"><?= $row['kodedir']; ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <th style="width: 30%;">Nama Bank/Perusahaan Lain :</th>
                                <td style="width: 70%;"><?= $row['perusahaandir']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 30%;">Persentase Kepemilikan (%) :</th>
                                <td style="width: 70%;"><?= $row['persenshmdirlain']; ?>%</td>
                            </tr>
                            <tr>
                                <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                    <td colspan="3">
                                        <button type="button" data-toggle="modal" class="btn" data-target="#modalUbahdir" id="btn-edit"
                                            style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                            data-direksi="<?= $row['direksi']; ?>" data-jenisdir="<?= $row['jenisdir']; ?>" <?php if ($row['jenisdir'] == 'Bank'): ?> data-kodedir="<?= $row['kodedir']; ?>" <?php endif; ?>
                                            data-perusahaandir="<?= $row['perusahaandir']; ?>"
                                            data-persenshmdirlain="<?= $row['persenshmdirlain']; ?>"><i class="fa fa-edit"></i>&nbsp;
                                        </button>
                                        <button type="button" data-toggle="modal" data-target="#modalHapusshmdirdekomlain"
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
                        data-target="#modalTambahsahamdekom"><i class="fa fa-plus"> Tambah Data</i></button>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <table class="table table-primary">
            <th>Kepemilikan Saham Anggota Dewan Komisaris pada Perusahaan Lain</th>
        </table>
        <table class="table table-bordered table-secondary">
            <tbody>
                <?php if (empty($shmdirdekomlain)) { ?>
                    <tr>
                        <th style="width: 30%;">Nama Dewan Komisaris :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Jenis Bank/Perusahaan Lain :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Nama Bank/Perusahaan Lain :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Persentase Kepemilikan (%) :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($shmdirdekomlain as $row): ?>
                        <?php
                        $rowspanValue = ($row['jenisdekom'] == 'Bank') ? 6 : 5;
                        if ($row['dekom'] != NULL): ?>
                            <tr>
                                <th style="width: 3%; background-color: #a3b6ee; color: transparent;"
                                    rowspan="<?= $rowspanValue; ?>">
                                    <?= $row['id']; ?>
                                </th>
                                <th style="width: 30%;">Nama Dewan Komisaris :</th>
                                <td style="width: 70%;"><?= $row['dekom']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 30%;">Jenis Bank/Perusahaan Lain :</th>
                                <td style="width: 70%;"><?= $row['jenisdekom']; ?></td>
                            </tr>
                            <?php if ($row['jenisdekom'] == 'Bank'): ?>
                                <tr>
                                    <th style="width: 30%;">Kode Bank :</th>
                                    <td style="width: 70%;"><?= $row['kodedekom']; ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <th style="width: 30%;">Nama Bank/Perusahaan Lain :</th>
                                <td style="width: 70%;"><?= $row['perusahaandekom']; ?></td>
                            </tr>
                            <tr>
                                <th style="width: 30%;">Persentase Kepemilikan (%) :</th>
                                <td style="width: 70%;"><?= $row['persenshmdekomlain']; ?>%</td>
                            </tr>
                            <tr>
                                <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                    <td colspan="3">
                                        <button type="button" data-toggle="modal" class="btn" data-target="#modalUbahdekom"
                                            id="btn-edit" style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                            data-dekom="<?= $row['dekom']; ?>" data-perusahaandekom="<?= $row['perusahaandekom']; ?>"
                                            data-persenshmdekomlain="<?= $row['persenshmdekomlain']; ?>"><i
                                                class="fa fa-edit"></i>&nbsp;
                                        </button>
                                        <button type="button" data-toggle="modal" data-target="#modalHapusshmdirdekomlain"
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
                <?php if (empty($shmdirdekomlain)) { ?>
                    <tr>
                        <th>Tindak Lanjut Rekomendasi Dewan Direksi:</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($shmdirdekomlain as $row): ?>
                        <?php if ($row['id'] == 1): ?>
                            <tr>
                                <th>Tindak Lanjut Rekomendasi Dewan Direksi:</th>
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
<?php if (!empty($shmdirdekomlain)) { ?>
    <div class="modal fade" id="modalUbahdir">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Kepemilikan Saham Anggota Direksi pada Perusahaan Lain</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('shmdirdekomlain/ubahdir'); ?>" method="post">
                        <input type="hidden" name="id" id="id-shmdirdekomlain">
                        <div class="mb-3">
                            <label for="direksi" class="form-label">Input Nama Direksi:</label>
                            <input class="form-control" type="text" name="direksi" id="direksi" readonly>
                        </div>
                        <div class="form-group">
                            <label for="jenisdir" class="form-label">Pilih Jenis Bank/Perusahaan Lain</label>
                            <select class="form-control" name="jenisdir" id="jenisdir">
                                <option value="">-- Pilih --</option>
                                <option value="Bank">Bank</option>
                                <option value="Perusahaan Lain">Perusahaan Lain</option>
                            </select>
                        </div>
                        <div class="form-group" id="kodeBankDirUbahGroup" style="display: none;">
                            <label for="kodedir" class="form-label">Input Kode Bank</label>
                            <input type="text" name="kodedir" id="kodedir" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="perusahaandir" class="form-label">Nama Bank/Perusahaan Lain: </label>
                            <input class="form-control" type="text" name="perusahaandir" id="perusahaandir">
                        </div>
                        <div class="form-group">
                            <label for="persenshmdirlain" class="form-label">Persentase Kepemilikan (%): </label>
                            <input class="form-control" type="text" name="persenshmdirlain" id="persenshmdirlain">
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
    <script>
        const jenisDirSelect = document.getElementById('jenisdir');
        const kodeBankDirUbahGroup = document.getElementById('kodeBankDirUbahGroup');

        jenisDirSelect.addEventListener('change', function () {
            if (this.value === 'Bank') {
                kodeBankDirUbahGroup.style.display = 'block';
            } else {
                kodeBankDirUbahGroup.style.display = 'none';
            }
        });

        // Pastikan saat halaman dimuat, status kode bank sesuai dengan pilihan awal (jika ada)
        document.addEventListener('DOMContentLoaded', function () {
            if (jenisDirSelect.value === 'Bank') {
                kodeBankDirUbahGroup.style.display = 'block';
            } else {
                kodeBankDirUbahGroup.style.display = 'none';
            }
        });
    </script>
<?php } ?>

<?php if (!empty($shmdirdekomlain)) { ?>
    <div class="modal fade" id="modalUbahdekom">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Kepemilikan Saham Anggota Dewan Komisaris pada Perusahaan Lain</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('shmdirdekomlain/ubahdekom'); ?>" method="post">
                        <input type="text" name="id" id="id-shmdirdekomlain">
                        <div class="mb-3">
                            <label for="dekom" class="form-label">Input Nama Dewan Komisaris :</label>
                            <input class="form-control" type="text" name="dekom" id="dekom" value="<?= $row['dekom'] ?>"
                                readonly>
                        </div>
                        <div class="form-group">
                            <label for="perusahaandekom" class="form-label">Nama Bank/Perusahaan Lain: </label>
                            <input class="form-control" type="text" name="perusahaandekom" id="perusahaandekom"
                                placeholder="<?= $row['perusahaandekom'] ?>"></input>
                        </div>
                        <div class="form-group">
                            <label for="persenshmdekomlain" class="form-label">Persentase Kepemilikan (%): </label>
                            <input class="form-control" type="text" name="persenshmdekomlain" id="persenshmdekomlain"
                                placeholder="<?= $row['persenshmdekomlain'] ?>"></input>
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

<?php if (!empty($shmdirdekomlain)) { ?>
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
                    <form action="<?= base_url('shmdirdekomlain/ubahketerangan'); ?>" method="post">
                        <input type="hidden" name="id" id="id-shmdirdekomlain">
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

<div class="modal fade" id="modalTambahsahamdir">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('shmdirdekomlain/tambahsahamdir'); ?>" method="post">
                    <div class="form-group">
                        <label for="direksi">Input Nama Direksi:</label>
                        <select name="direksi" id="direksi" class="form-control">
                            <?php if (isset($tgjwbdir) && is_array($tgjwbdir)): ?>
                                <?php foreach ($tgjwbdir as $row): ?>
                                    <option value="<?= $row['direksi']; ?>"><?= $row['direksi']; ?></option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="">Tidak ada data direksi</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="jenisdir">Pilih Jenis Bank/Perusahaan Lain</label>
                        <select class="form-control" name="jenisdir" id="jenisdir">
                            <option value="">-- Pilih Jenis Bank/Perusahaan Lain --</option>
                            <option value="Bank">Bank</option>
                            <option value="Perusahaan Lain">Perusahaan Lain</option>
                        </select>
                    </div>
                    <div class="form-group" id="kodeBankGroup" style="display: none;">
                        <label for="kodedir">Input Kode Bank</label>
                        <input type="text" name="kodedir" id="kodedir" class="form-control">
                        <small>Note: Input angkanya saja</small>
                    </div>
                    <div class="form-group">
                        <label for="perusahaandir">Input Nama Bank/Perusahaan Lain </label>
                        <input type="text" name="perusahaandir" id="perusahaandir" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="persenshmdir">Input Persentase Kepemilikan (%): </label>
                        <input type="text" name="persenshmdirlain" id="persenshmdirlain" class="form-control">
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

<script>
    const jenisDirSelect = document.getElementById('jenisdir');
    const kodeBankGroup = document.getElementById('kodeBankGroup');

    jenisDirSelect.addEventListener('change', function () {
        if (this.value === 'Bank') {
            kodeBankGroup.style.display = 'block';
        } else {
            kodeBankGroup.style.display = 'none';
        }
    });
</script>

<!-- Add your modalTambah code here -->
<div class="modal fade" id="modalTambahsahamdekom">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Kepemilikan Saham Anggota Dewan Komisaris pada BPR </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('shmdirdekomlain/tambahsahamdekom'); ?>" method="post">
                    <div class="form-group">
                        <label for="dekom">Input Nama Dewan Komisaris:</label>
                        <select name="dekom" id="dekom" class="form-control">
                            <?php if (isset($tgjwbdekom) && is_array($tgjwbdekom)): ?>
                                <?php foreach ($tgjwbdekom as $row): ?>
                                    <option value="<?= $row['dekom']; ?>"><?= $row['dekom']; ?></option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="">Tidak ada data Dewan Komisaris</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="jenisdekom">Pilih Jenis Bank/Perusahaan Lain</label>
                        <select class="form-control" name="jenisdekom" id="jenisdekom">
                            <option value="">-- Pilih Bank/Perusahaan Lain --</option>
                            <option value="Bank">Bank</option>
                            <option value="Perusahaan Lain">Perusahaan Lain</option>
                        </select>
                    </div>
                    <div class="form-group" id="kodeBankDekomGroup" style="display: none;">
                        <label for="kodedekom">Input Kode Bank</label>
                        <input type="text" name="kodedekom" id="kodedekom" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="perusahaandekom">Input Nama Bank/Perusahaan Lain: </label>
                        <input type="text" name="perusahaandekom" id="perusahaandekom" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="persenshmdekomlain">Input Persentase Kepemilikan (%): </label>
                        <input type="text" name="persenshmdekomlain" id="persenshmdekomlain" class="form-control">
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

<script>
    const jenisDekomSelect = document.getElementById('jenisdekom');
    const kodeBankDekomGroup = document.getElementById('kodeBankDekomGroup');

    jenisDekomSelect.addEventListener('change', function () {
        if (this.value === 'Bank') {
            kodeBankDekomGroup.style.display = 'block';
        } else {
            kodeBankDekomGroup.style.display = 'none';
        }
    });
</script>

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
                <form action="<?= base_url('shmdirdekomlain/tambahkomentar'); ?>" method="post">
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
                            <a href="<?= base_url('shmdirdekomlain/approveSemua') ?>"
                                class="btn btn-success shadow mt-3 mx-2"
                                onclick="return confirm('Apakah Anda yakin hendak melakukan approval?');">
                                Approve
                            </a>
                            <a href="<?= base_url('shmdirdekomlain/unapproveSemua') ?>"
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
<div class="modal fade" id="modalHapusshmdirdekomlain">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                Apakah anda yakin ingin menghapus data ini?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btnHapusshmdirdekomlain">Yakin</button>
            </div>
        </div>
    </div>
</div>