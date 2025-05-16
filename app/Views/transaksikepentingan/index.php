<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-4 text-gray-800"><?= $judul; ?></h1>
        <span>
            <?php
            $allApproved = true;
            foreach ($transaksikepentingan as $item) {
                if ($item['is_approved'] != 1) {
                    $allApproved = false;
                    break;
                }
            }
            ?>
            <?php if ($allApproved): ?>
                <span class="badge badge-success" style="font-size: 14px;">
                    Disetujui oleh <strong><?= esc($fullname ?? '-') ?></strong><br>
                    <?= esc($transaksikepentingan[0]['approved_at'] ?? '-') ?>
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
                        data-target="#modalTambahtransaksikepentingan"><i class="fa fa-plus"> Tambah Data</i></button>
                </div>
                <div class="col-md">
                    <button onclick="window.print()"
                        class="btn btn-outline-secondary shadow float-right mr-3 ml-2 mt-3">Print <i
                            class="fa fa-print"></i> </button>
                    <a href="/transaksikepentingan/excel" class="btn btn-outline-success shadow float-right mt-3">Excel
                        <i class="fa fa-file-excel"></i></a>
                    <a href="/transaksikepentingan/exporttxttransaksikepentingan"
                        class="btn btn-outline-secondary shadow float-right mr-3 ml-2 mt-3">Export TXT <i
                            class="fa fa-file-alt"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <table class="table table-primary">
            <th>Transaksi yang Mengandung Benturan Kepentingan</th>
        </table>
        <table class="table table-bordered table-secondary">
            <tbody>
                <?php if (empty($transaksikepentingan)) { ?>
                    <tr>
                        <th style="width: 30%;">Nama Pihak yang Memiliki Benturan Kepentingan :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Jabatan Pihak yang Memiliki Benturan Kepentingan :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">NIK Pihak yang Memiliki Benturan Kepentingan :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Nama Pengambil Keputusan :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Jabatan Pengambil Keputusan :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">NIK Pengambil Keputusan :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Jenis Transaksi :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Nilai Transaksi (Jutaan Rupiah) :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Keterangan :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($transaksikepentingan as $row): ?>
                        <tr>
                            <th style="width: 3%; background-color: #a3b6ee;" rowspan="10"><?= $row['id']; ?></th>
                            <th style="width: 30%;">Nama Pihak yang Memiliki Benturan Kepentingan :</th>
                            <td style="width: 70%;"><?= $row['namapihakbenturan']; ?></td>
                        </tr>
                        <tr>
                            <th style="width: 30%;">Jabatan Pihak yang Memiliki Benturan Kepentingan :</th>
                            <td style="width: 70%;"><?= $row['jbtbenturan']; ?></td>
                        </tr>
                        <tr>
                            <th style="width: 30%;">NIK Pihak yang Memiliki Benturan Kepentingan :</th>
                            <td style="width: 70%;"><?= $row['nikbenturan']; ?></td>
                        </tr>
                        <tr>
                            <th style="width: 30%;">Nama Pengambil Keputusan :</th>
                            <td style="width: 70%;"><?= $row['pengambilkeputusan']; ?></td>
                        </tr>
                        <tr>
                            <th style="width: 30%;">Jabatan Pengambil Keputusan :</th>
                            <td style="width: 70%;"><?= $row['jbtpengambilkeputusan']; ?></td>
                        </tr>
                        <tr>
                            <th style="width: 30%;">NIK Pengambil Keputusan :</th>
                            <td style="width: 70%;"><?= $row['nikpengambilkeputusan']; ?></td>
                        </tr>
                        <tr>
                            <th style="width: 30%;">Jenis Transaksi :</th>
                            <td style="width: 70%;"><?= $row['jenistransaksi']; ?></td>
                        </tr>
                        <tr>
                            <th style="width: 30%;">Nilai Transaksi :</th>
                            <td style="width: 70%;">
                                <?php
                                $nilaiTransaksi = $row['nilaitransaksi'];
                                // Format angka Rupiah dengan 2 desimal, pemisah ribuan titik, dan pemisah desimal koma
                                $nilaiTransaksiFormatted = 'Rp ' . number_format($nilaiTransaksi, 2, ',', '.');
                                echo $nilaiTransaksiFormatted;
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th style="width: 30%;">Keterangan :</th>
                            <td style="width: 70%;"><?= $row['keterangan']; ?></td>
                        </tr>
                        <tr>
                            <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                <td colspan="3">
                                    <button type="button" data-toggle="modal" class="btn" data-target="#modalUbah" id="btn-edit"
                                        style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                        data-namapihakbenturan="<?= $row['namapihakbenturan']; ?>"
                                        data-jbtbenturan="<?= $row['jbtbenturan']; ?>"
                                        data-nikbenturan="<?= $row['nikbenturan']; ?>"
                                        data-pengambilkeputusan="<?= $row['pengambilkeputusan']; ?>"
                                        data-jbtpengambilkeputusan="<?= $row['jbtpengambilkeputusan']; ?>"
                                        data-nikpengambilkeputusan="<?= $row['nikpengambilkeputusan']; ?>"
                                        data-jenistransaksi="<?= $row['jenistransaksi']; ?>"
                                        data-nilaitransaksi="<?= $row['nilaitransaksi']; ?>"
                                        data-keterangan="<?= $row['keterangan']; ?>"><i class="fa fa-edit"></i>&nbsp;
                                    </button>
                                    <button type="button" data-toggle="modal" data-target="#modalHapustransaksikepentingan"
                                        id="btn-hapus" class="btn" style="font-weight: 600;" data-id="<?= $row['id']; ?>"> <i
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
    </div>
</div>

</div>

<?php if (!empty($transaksikepentingan)) { ?>
    <div class="modal fade" id="modalUbah">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Transaksi yang Mengandung Benturan Kepentingan </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('transaksikepentingan/ubah'); ?>" method="post">
                        <input type="text" name="id" id="id-transaksikepentingan">
                        <div class="mb-3">
                            <label for="namapihakbenturan">Ubah Nama Pihak yang Memiliki Benturan Kepentingan:</label>
                            <input class="form-control" type="text" name="namapihakbenturan" id="namapihakbenturan"
                                value="<?= $row['namapihakbenturan'] ?>" placeholder="<?= $row['namapihakbenturan'] ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="jbtbenturan" class="form-label">Ubah Jabatan Pihak yang Memiliki Benturan
                                Kepentingan: </label>
                            <textarea class="form-control" type="text" name="jbtbenturan" id="jbtbenturan"
                                placeholder="<?= $row['jbtbenturan'] ?>" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="nikbenturan" class="form-label">Ubah NIK Pihak yang Memiliki Benturan Kepentingan:
                            </label>
                            <textarea class="form-control" type="text" name="nikbenturan" id="nikbenturan"
                                placeholder="<?= $row['nikbenturan'] ?>" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="pengambilkeputusan" class="form-label">Ubah Nama Pengambil Keputusan: </label>
                            <textarea class="form-control" type="text" name="pengambilkeputusan" id="pengambilkeputusan"
                                style="height: 150px" placeholder="<?= $row['pengambilkeputusan'] ?>" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="jbtpengambilkeputusan" class="form-label">Ubah Jabatan Pengambil Keputusan: </label>
                            <textarea class="form-control" type="text" name="jbtpengambilkeputusan"
                                id="jbtpengambilkeputusan" placeholder="<?= $row['jbtpengambilkeputusan'] ?>" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="nikpengambilkeputusan" class="form-label">Ubah NIK Pengambil Keputusan: </label>
                            <textarea class="form-control" type="text" name="nikpengambilkeputusan"
                                id="nikpengambilkeputusan" placeholder="<?= $row['nikpengambilkeputusan'] ?>" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="jenistransaksi" class="form-label">Ubah Jenis Transaksi: </label>
                            <textarea class="form-control" type="text" name="jenistransaksi" id="jenistransaksi"
                                placeholder="<?= $row['jenistransaksi'] ?>" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="nilaitransaksi" class="form-label">Ubah Nilai Transaksi: </label>
                            <textarea class="form-control" type="text" name="nilaitransaksi" id="nilaitransaksi"
                                placeholder="<?= $row['nilaitransaksi'] ?>" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="keterangan" class="form-label">Ubah Keterangan: </label>
                            <textarea class="form-control" type="text" name="keterangan" id="keterangan"
                                placeholder="<?= $row['keterangan'] ?>" required></textarea>
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

<div class="modal fade" id="modalTambahtransaksikepentingan">
    <div class="modal-dialog" role="document"> <!-- ← di sini kurang 'div' -->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Transaksi yang Mengandung Benturan Kepentingan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('transaksikepentingan/tambahtransaksikepentingan'); ?>" method="post">
                    <div class="mb-3">
                        <label for="namapihakbenturan">Input Nama Pihak yang Memiliki Benturan Kepentingan:</label>
                        <input class="form-control" type="text" name="namapihakbenturan" id="namapihakbenturan" required>
                    </div>
                    <div class="form-group">
                        <label for="jbtbenturan" class="form-label">Input Jabatan Pihak yang Memiliki Benturan
                            Kepentingan:</label>
                        <input class="form-control" type="text" name="jbtbenturan" id="jbtbenturan" required>
                    </div>
                    <div class="form-group">
                        <label for="nikbenturan" class="form-label">Input NIK Pihak yang Memiliki Benturan
                            Kepentingan:</label>
                        <input class="form-control" type="text" name="nikbenturan" id="nikbenturan" required>
                    </div>
                    <div class="form-group">
                        <label for="pengambilkeputusan" class="form-label">Input Nama Pengambil Keputusan:</label>
                        <input class="form-control" type="text" name="pengambilkeputusan" id="pengambilkeputusan" required>
                    </div>
                    <div class="form-group">
                        <label for="jbtpengambilkeputusan" class="form-label">Input Jabatan Pengambil Keputusan:</label>
                        <input class="form-control" type="text" name="jbtpengambilkeputusan" id="jbtpengambilkeputusan" required>
                    </div>
                    <div class="form-group">
                        <label for="nikpengambilkeputusan" class="form-label">Input NIK Pengambil Keputusan:</label>
                        <input class="form-control" type="text" name="nikpengambilkeputusan" id="nikpengambilkeputusan" required>
                    </div>
                    <div class="form-group">
                        <label for="jenistransaksi" class="form-label">Input Jenis Transaksi:</label>
                        <input class="form-control" type="text" name="jenistransaksi" id="jenistransaksi" required>
                    </div>
                    <div class="form-group">
                        <label for="nilaitransaksi" class="form-label">Input Nilai Transaksi:</label>
                        <input class="form-control" type="text" name="nilaitransaksi" id="nilaitransaksi"
                            oninput="formatRupiah(this)" required>
                    </div>
                    <div class="form-group">
                        <label for="keterangan" class="form-label">Input Keterangan:</label>
                        <input class="form-control" type="text" name="keterangan" id="keterangan" required>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" name="tambahtransaksikepentingan" class="btn btn-primary">Tambah Data</button>
            </div>
            </form>
        </div>
    </div>
</div>

<!-- Tambahkan script ini -->
<script>
    function formatRupiah(input) {
        let value = input.value;
        value = value.replace(/[^,\d]/g, '').toString(); // hanya angka
        let split = value.split(',');
        let sisa = split[0].length % 3;
        let rupiah = split[0].substr(0, sisa);
        let ribuan = split[0].substr(sisa).match(/\d{3}/gi);

        if (ribuan) {
            let separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }

        rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
        input.value = rupiah;
    }
</script>


<!-- Modal Hapus data bisnis -->
<div class="modal fade" id="modalHapustransaksikepentingan">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                Apakah anda yakin ingin menghapus data no-<span id="idData"></span>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btnHapustransaksikepentingan">Yakin</button>
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
                <form action="<?= base_url('transaksikepentingan/tambahkomentar'); ?>" method="post">
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
                            <a href="<?= base_url('transaksikepentingan/approveSemua') ?>"
                                class="btn btn-success shadow mt-3 mx-2"
                                onclick="return confirm('Apakah Anda yakin hendak melakukan approval?');">
                                Approve
                            </a>
                            <a href="<?= base_url('transaksikepentingan/unapproveSemua') ?>"
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