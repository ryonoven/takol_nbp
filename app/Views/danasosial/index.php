<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-4 text-gray-800"><?= $judul; ?></h1>
        <span>
            <?php
            $allApproved = true;
            foreach ($danasosial as $item) {
                if ($item['is_approved'] != 1) {
                    $allApproved = false;
                    break;
                }
            }
            ?>
            <?php if ($allApproved): ?>
                <span class="badge badge-success" style="font-size: 14px;">
                    Disetujui oleh <strong><?= esc($fullname ?? '-') ?></strong><br>
                    <?= esc($danasosial[0]['approved_at'] ?? '-') ?>
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
                        data-target="#modalTambahdanasosial"><i class="fa fa-plus"> Tambah Data</i></button>
                </div>
                <div class="col-md">
                    <!-- <button onclick="window.print()"
                    class="btn btn-outline-secondary shadow float-right mr-3 ml-2 mt-3">Print <i
                        class="fa fa-print"></i> </button>
                <a href="/danasosial/excel" class="btn btn-outline-success shadow float-right mt-3">Excel <i
                        class="fa fa-file-excel"></i></a> -->
                    <a href="/danasosial/exporttxtdanasosial"
                        class="btn btn-outline-secondary shadow float-right mr-3 ml-2 mt-3">Export TXT <i
                            class="fa fa-file-alt"></i>
                    </a>
                    <a href="/danasosial/exportAllToZip"
                        class="btn btn-outline-info shadow float-right mr-3 ml-2 mt-3">Export All to ZIP <i
                            class="fa fa-file-archive"></i>
                    </a>
                    <a href="/pdfgcg/generateFullReport" class="btn btn-outline-info shadow float-right mr-3 ml-2 mt-3">TES
                        PDF <i class="fa fa-file-archive"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <table class="table table-primary">
            <th>Pemberian Dana untuk Kegiatan Sosial dan Kegiatan Politik</th>
        </table>
        <table class="table table-bordered table-secondary">
            <tbody>
                <?php if (empty($danasosial)) { ?>
                    <tr>
                        <th style="width: 30%;">Tanggal Pelaksanaan :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Jenis Kegiatan (Sosial/Politik) :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Penerima Dana :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Penjelasan Kegiatan :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Jumlah (Rp) :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($danasosial as $row): ?>
                        <tr>
                            <th style="width: 3%; background-color: #a3b6ee;" rowspan="6"><?= $row['id']; ?></th>
                            <th style="width: 30%;">Tanggal Pelaksanaan :</th>
                            <td style="width: 70%;"><?= $row['tanggalpelaksanaan']; ?></td>
                        </tr>
                        <tr>
                            <th style="width: 30%;">Jenis Kegiatan (Sosial/Politik) :</th>
                            <td style="width: 70%;">
                                <?php
                                if ($row['jeniskegiatan'] == '01') {
                                    echo '01. Kegiatan Sosial';
                                } elseif ($row['jeniskegiatan'] == '02') {
                                    echo '02. Kegiatan Politik';
                                } else {
                                    echo $row['jeniskegiatan']; // Tampilkan kode aslinya jika tidak 01 atau 02
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th style="width: 30%;">Penerima Dana :</th>
                            <td style="width: 70%;"><?= $row['penerimadana']; ?></td>
                        </tr>
                        <tr>
                            <th style="width: 30%;">Penjelasan Kegiatan :</th>
                            <td style="width: 70%;"><?= $row['penjelasankegiatan']; ?></td>
                        </tr>
                        <tr>
                            <th style="width: 30%;">Jumlah Dana :</th>
                            <td style="width: 70%;">Rp <?= number_format($row['jumlah'], 0, ',', '.'); ?></td>
                        </tr>
                        <tr>
                            <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                <td colspan="3">
                                    <button type="button" data-toggle="modal" class="btn" data-target="#modalUbah" id="btn-edit"
                                        style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                        data-tanggalpelaksanaan="<?= $row['tanggalpelaksanaan']; ?>"
                                        data-jeniskegiatan="<?= $row['jeniskegiatan']; ?>"
                                        data-penerimadana="<?= $row['penerimadana']; ?>"
                                        data-penjelasankegiatan="<?= $row['penjelasankegiatan']; ?>"
                                        data-jumlah="<?= $row['jumlah']; ?>"><i class="fa fa-edit"></i>&nbsp;
                                    </button>
                                    <button type="button" data-toggle="modal" data-target="#modalHapusdanasosial" id="btn-hapus"
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
                <?php if (empty($danasosial)) { ?>
                    <tr>
                        <th>Keterangan:</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($danasosial as $row): ?>
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
                                    data-id="<?= $row['id']; ?>" data-keteranganr="<?= $row['keterangan']; ?>"><i class="fa fa-edit">
                                        Tambah Keterangan</i>&nbsp;</button>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<!--edit data-->
<?php if (!empty($danasosial)) { ?>
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
                    <form action="<?= base_url('danasosial/ubah'); ?>" method="post">
                        <input type="hidden" name="id" id="id-danasosial">
                        <div class="mb-3">
                            <label for="tanggalpelaksanaan">Ubah Tanggal Pelaksanaan:</label>
                            <input class="form-control" type="text" name="tanggalpelaksanaan" id="tanggalpelaksanaan"
                                value="<?= $row['tanggalpelaksanaan'] ?>" placeholder="<?= $row['tanggalpelaksanaan'] ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="jeniskegiatan" class="form-label">Ubah Jenis Kegiatan: </label>
                            <select class="form-control" name="jeniskegiatan" id="jeniskegiatan" required>
                                <option value="">-- Pilih Jenis Kegiatan --</option>
                                <option value="01" <?= ($row['jeniskegiatan'] == '01') ? 'selected' : '' ?>>01. Kegiatan Sosial
                                </option>
                                <option value="02" <?= ($row['jeniskegiatan'] == '02') ? 'selected' : '' ?>>02. Kegiatan
                                    Politik</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="penerimadana" class="form-label">Ubah Penerima Dana: </label>
                            <input class="form-control" type="text" name="penerimadana" id="penerimadana"
                                placeholder="<?= $row['penerimadana'] ?>" required></input>
                        </div>
                        <div class="form-group">
                            <label for="penjelasankegiatan" class="form-label">Ubah Penjelasan Kegiatan: </label>
                            <textarea class="form-control" type="text" name="penjelasankegiatan" id="penjelasankegiatan"
                                style="height: 150px" placeholder="<?= $row['penjelasankegiatan'] ?>" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="jumlah" class="form-label">Ubah Jumlah Dana: </label>
                            <input class="form-control" type="text" name="jumlah" id="jumlah"
                                placeholder="<?= $row['jumlah'] ?>" required></input>
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

<?php if (!empty($danasosial)) { ?>
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
                    <form action="<?= base_url('danasosial/ubahketerangan'); ?>" method="post">
                        <input type="hidden" name="id" id="id-danasosial">
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

<div class="modal fade" id="modalTambahdanasosial">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Pemberian Dana untuk Kegiatan Sosial dan Kegiatan Politik </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('danasosial/tambahdanasosial'); ?>" method="post">
                    <div class="form-group">
                        <label for="tanggalpelaksanaan">Input Tanggal Pelaksanaan:</label>
                        <input type="text" name="tanggalpelaksanaan" id="tanggalpelaksanaan" class="form-control" required>
                        <small>Note: Template inputan yyyymmdd</small>
                    </div>
                    <div class="form-group">
                        <label for="jeniskegiatan">Input Jenis Kegiatan: </label>
                        <select name="jeniskegiatan" id="jeniskegiatan" class="form-control" required>
                            <option value="">-- Pilih Kegiatan --</option>
                            <option value="01">01. Kegiatan Sosial</option>
                            <option value="02">02. Kegiatan Politik</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="penerimadana">Input Penerima Dana: </label>
                        <input type="text" name="penerimadana" id="penerimadana" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="penjelasankegiatan">Input Penjelasan Kegiatan: </label>
                        <input type="text" name="penjelasankegiatan" id="penjelasankegiatan" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="jumlah">Input Jumlah Dana: </label>
                        <input type="text" name="jumlah" id="jumlah" class="form-control" oninput="formatRupiah(this)" required>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" name="tambahdanasosial" class="btn btn-primary">Tambah Data</button>
            </div>
            </form>
        </div>
    </div>
</div>

<script>
    function formatRupiah(input) {
        let angka = input.value.replace(/[^,\d]/g, '').toString();
        let split = angka.split(',');
        let sisa = split[0].length % 3;
        let rupiah = split[0].substr(0, sisa);
        let ribuan = split[0].substr(sisa).match(/\d{3}/gi);

        if (ribuan) {
            let separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }

        rupiah = split[1] !== undefined ? rupiah + ',' + split[1] : rupiah;
        input.value = rupiah ? 'Rp ' + rupiah : '';
    }
</script>

<!-- Modal Hapus data bisnis -->
<div class="modal fade" id="modalHapusdanasosial">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                Apakah anda yakin ingin menghapus data no-<span id="idData"></span>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btnHapusdanasosial">Yakin</button>
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
                <form action="<?= base_url('danasosial/tambahkomentar'); ?>" method="post">
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
                            <a href="<?= base_url('danasosial/approveSemua') ?>" class="btn btn-success shadow mt-3 mx-2"
                                onclick="return confirm('Apakah Anda yakin hendak melakukan approval?');">
                                Approve
                            </a>
                            <a href="<?= base_url('danasosial/unapproveSemua') ?>" class="btn btn-danger shadow mt-3 mx-2"
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