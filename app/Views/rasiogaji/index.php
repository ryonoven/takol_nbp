<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-4 text-gray-800"><?= $judul; ?></h1>
        <span>
            <?php
            $allApproved = true;
            foreach ($rasiogaji as $item) {
                if ($item['is_approved'] != 1) {
                    $allApproved = false;
                    break;
                }
            }
            ?>
            <?php if ($allApproved): ?>
                <span class="badge badge-success" style="font-size: 14px;">
                    Disetujui oleh <strong><?= esc($fullname ?? '-') ?></strong><br>
                    <?= esc($rasiogaji[0]['approved_at'] ?? '-') ?>
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
                    <?php if (empty($rasiogaji)) { ?>
                        <button type="button" data-toggle="modal" class="btn btn-primary ml-3 mt-3"
                            data-target="#modalTambahrasio"><i class="fa fa-plus"></i> Tambah Data</button>
                    <?php } else { ?>
                        <button type="button" data-toggle="modal" class="btn btn-primary ml-3 mt-3"
                            data-target="#modalUbahrasio" id="btn-edit" style="font-weight: 600;"
                            data-id="<?= $rasiogaji[0]['id'] ?? ''; ?>"
                            data-pegawaitinggi="<?= $rasiogaji[0]['pegawaitinggi'] ?? ''; ?>"
                            data-pegawairendah="<?= $rasiogaji[0]['pegawairendah'] ?? ''; ?>"
                            data-dirtinggi="<?= $rasiogaji[0]['dirtinggi'] ?? ''; ?>"
                            data-dirrendah="<?= $rasiogaji[0]['dirrendah'] ?? ''; ?>"
                            data-dekomtinggi="<?= $rasiogaji[0]['dekomtinggi'] ?? ''; ?>"
                            data-dekomrendah="<?= $rasiogaji[0]['dekomrendah'] ?? ''; ?>"><i class="fa fa-plus"></i> Ubah Data
                        </button>
                        <?php foreach ($rasiogaji as $row): ?>
                            <button type="button" data-toggle="modal" data-target="#modalHapusrasio" id="btn-hapus"
                                class="btn btn-danger ml-3 mt-3" style="font-weight: 600;" data-id="<?= $row['id']; ?>"><i
                                    class="fa fa-trash"> Hapus Data</i>&nbsp;</button>
                        <?php endforeach; ?>
                    <?php } ?>
                </div>
                <div class="col-md">
                    <button onclick="window.print()"
                        class="btn btn-outline-secondary shadow float-right mr-3 ml-2 mt-3">Print <i
                            class="fa fa-print"></i> </button>
                    <a href="/rasiogaji/excel" class="btn btn-outline-success shadow float-right mt-3">Excel <i
                            class="fa fa-file-excel"></i></a>
                    <a href="/rasiogaji/exporttxtrasiogaji"
                        class="btn btn-outline-secondary shadow float-right mr-3 ml-2 mt-3">Export TXT <i
                            class="fa fa-file-alt"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <table class="table table-primary">
            <th>1. Rasio (a) Gaji Pegawai yang tertinggi dan (b) Gaji Pegawai yang terendah</th>
        </table>
        <table class="table table-bordered table-secondary">
            <tbody>
                <?php if (empty($rasiogaji)) { ?>
                    <tr>
                        <th style="width: 30%;">Gaji Pegawai Tertinggi :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Gaji Pegawai Terendah :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Rasio (a/b) :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($rasiogaji as $row): ?>
                        <?php
                        // Mengambil nilai gaji tertinggi dan gaji terendah
                        $gajiTertinggi = $row['pegawaitinggi'];
                        $gajiTerendah = $row['pegawairendah'];

                        // Melakukan pembagian untuk rasio a/b
                        if ($gajiTerendah != 0) {
                            $rasio = $gajiTertinggi / $gajiTerendah;
                        } else {
                            $rasio = 0; // Menghindari pembagian dengan nol
                        }

                        // Menyimpan hasil perhitungan di kolom pegawaitinggirendah
                        $row['pegawaitinggirendah'] = $rasio; // Update kolom rasio dengan nilai hasil pembagian
                        ?>

                        <tr>
                            <th style="width: 30%;">Gaji Pegawai Tertinggi :</th>
                            <td style="width: 70%;">
                                <?php
                                $gajiTertinggiFormatted = 'Rp ' . number_format($gajiTertinggi, 2, ',', '.');
                                echo $gajiTertinggiFormatted;
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th style="width: 30%;">Gaji Pegawai Terendah :</th>
                            <td style="width: 70%;">
                                <?php
                                $gajiTerendahFormatted = 'Rp ' . number_format($gajiTerendah, 2, ',', '.');
                                echo $gajiTerendahFormatted;
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th style="width: 30%;">Rasio (a/b) :</th>
                            <td style="width: 70%;">
                                <?php
                                // Menampilkan hasil rasio dengan format dua angka desimal
                                echo number_format($rasio, 2, ',', '.');
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <div class="card-body">
        <table class="table table-primary">
            <th>2. Rasio (a) Gaji Anggota Direksi yang tertinggi dan (b) Gaji Anggota Direksi yang terendah</th>
        </table>
        <table class="table table-bordered table-secondary">
            <tbody>
                <?php if (empty($rasiogaji)) { ?>
                    <tr>
                        <th style="width: 30%;">Gaji Direksi Tertinggi :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Gaji Direksi Terendah :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Rasio (a/b) :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($rasiogaji as $row): ?>
                        <?php
                        // Mengambil nilai gaji tertinggi dan gaji terendah untuk direksi
                        $gajiDireksiTertinggi = $row['dirtinggi'];
                        $gajiDireksiTerendah = $row['dirrendah'];

                        // Melakukan pembagian untuk rasio a/b
                        if ($gajiDireksiTerendah != 0) {
                            $rasioDireksi = $gajiDireksiTertinggi / $gajiDireksiTerendah;
                        } else {
                            $rasioDireksi = 0; // Menghindari pembagian dengan nol
                        }

                        // Menyimpan hasil perhitungan di kolom direksitinggirendah
                        $row['direksitinggirendah'] = $rasioDireksi; // Update kolom rasio dengan nilai hasil pembagian
                        ?>

                        <tr>
                            <th style="width: 30%;">Gaji Direksi Tertinggi :</th>
                            <td style="width: 70%;">
                                <?php
                                $gajiDireksiTertinggiFormatted = 'Rp ' . number_format($gajiDireksiTertinggi, 2, ',', '.');
                                echo $gajiDireksiTertinggiFormatted;
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th style="width: 30%;">Gaji Direksi Terendah :</th>
                            <td style="width: 70%;">
                                <?php
                                $gajiDireksiTerendahFormatted = 'Rp ' . number_format($gajiDireksiTerendah, 2, ',', '.');
                                echo $gajiDireksiTerendahFormatted;
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th style="width: 30%;">Rasio (a/b) :</th>
                            <td style="width: 70%;">
                                <?php
                                // Menampilkan hasil rasio dengan format dua angka desimal
                                echo number_format($rasioDireksi, 2, ',', '.');
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <div class="card-body">
        <table class="table table-primary">
            <th>3. Rasio (a) Gaji Anggota Dewan Komisaris yang tertinggi dan (b) Gaji Anggota Dewan Komisaris yang
                terendah</th>
        </table>
        <table class="table table-bordered table-secondary">
            <tbody>
                <?php if (empty($rasiogaji)) { ?>
                    <tr>
                        <th style="width: 30%;">Gaji Anggota Dewan Komisaris Tertinggi :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Gaji Anggota Dewan Komisaris Terendah :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Rasio (a/b) :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($rasiogaji as $row): ?>
                        <?php
                        // Mengambil nilai gaji tertinggi dan gaji terendah untuk dewan komisaris
                        $gajiDekomTertinggi = $row['dekomtinggi'];
                        $gajiDekomTerendah = $row['dekomrendah'];

                        // Melakukan pembagian untuk rasio a/b
                        if ($gajiDekomTerendah != 0) {
                            $rasioDekom = $gajiDekomTertinggi / $gajiDekomTerendah;
                        } else {
                            $rasioDekom = 0; // Menghindari pembagian dengan nol
                        }

                        // Menyimpan hasil perhitungan di kolom dekomtinggirendah
                        $row['dekomtinggirendah'] = $rasioDekom; // Update kolom rasio dengan nilai hasil pembagian
                        ?>

                        <tr>
                            <th style="width: 30%;">Gaji Anggota Dewan Komisaris Tertinggi :</th>
                            <td style="width: 70%;">
                                <?php
                                $gajiDekomTertinggiFormatted = 'Rp ' . number_format($gajiDekomTertinggi, 2, ',', '.');
                                echo $gajiDekomTertinggiFormatted;
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th style="width: 30%;">Gaji Anggota Dewan Komisaris Terendah :</th>
                            <td style="width: 70%;">
                                <?php
                                $gajiDekomTerendahFormatted = 'Rp ' . number_format($gajiDekomTerendah, 2, ',', '.');
                                echo $gajiDekomTerendahFormatted;
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th style="width: 30%;">Rasio (a/b) :</th>
                            <td style="width: 70%;">
                                <?php
                                // Menampilkan hasil rasio dengan format dua angka desimal
                                echo number_format($rasioDekom, 2, ',', '.');
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <div class="card-body">
        <table class="table table-primary">
            <th>4. Rasio (a) Gaji Anggota Direksi yang tertinggi dan (b) Gaji Anggota Dewan Komisaris yang tertinggi
            </th>
        </table>
        <table class="table table-bordered table-secondary">
            <tbody>
                <?php if (empty($rasiogaji)) { ?>
                    <tr>
                        <th style="width: 30%;">Gaji Anggota Direksi Tertinggi :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Gaji Anggota Dewan Komisaris Tertinggi :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Rasio (a/b) :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($rasiogaji as $row): ?>
                        <?php
                        // Mengambil nilai gaji tertinggi untuk Direksi dan Dewan Komisaris
                        $gajiDireksiTertinggi = $row['dirtinggi'];
                        $gajiDekomTertinggi = $row['dekomtinggi'];

                        // Melakukan pembagian untuk rasio a/b
                        if ($gajiDekomTertinggi != 0) {
                            $rasioDirDekom = $gajiDireksiTertinggi / $gajiDekomTertinggi;
                        } else {
                            $rasioDirDekom = 0; // Menghindari pembagian dengan nol
                        }

                        // Menyimpan hasil perhitungan di kolom dirdekomtinggirendah
                        $row['dirdekomtinggirendah'] = $rasioDirDekom; // Update kolom rasio dengan nilai hasil pembagian
                        ?>

                        <tr>
                            <th style="width: 30%;">Gaji Anggota Direksi Tertinggi :</th>
                            <td style="width: 70%;">
                                <?php
                                $gajiDireksiTertinggiFormatted = 'Rp ' . number_format($gajiDireksiTertinggi, 2, ',', '.');
                                echo $gajiDireksiTertinggiFormatted;
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th style="width: 30%;">Gaji Anggota Dewan Komisaris Tertinggi :</th>
                            <td style="width: 70%;">
                                <?php
                                $gajiDekomTertinggiFormatted = 'Rp ' . number_format($gajiDekomTertinggi, 2, ',', '.');
                                echo $gajiDekomTertinggiFormatted;
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th style="width: 30%;">Rasio (a/b) :</th>
                            <td style="width: 70%;">
                                <?php
                                // Menampilkan hasil rasio dengan format dua angka desimal
                                echo number_format($rasioDirDekom, 2, ',', '.');
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <div class="card-body">
        <table class="table table-primary">
            <th>5. Rasio (a) Gaji Anggota Direksi yang tertinggi dan (b) Gaji Pegawai yang tertinggi</th>
        </table>
        <table class="table table-bordered table-secondary">
            <tbody>
                <?php if (empty($rasiogaji)) { ?>
                    <tr>
                        <th style="width: 30%;">Gaji Anggota Direksi Tertinggi :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Gaji Pegawai Tertinggi :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Rasio (a/b) :</th>
                        <td colspan="2">Data tidak tersedia</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($rasiogaji as $row): ?>
                        <?php
                        // Mengambil nilai gaji tertinggi untuk Direksi dan Pegawai
                        $gajiDireksiTertinggi = $row['dirtinggi'];
                        $gajiPegawaiTertinggi = $row['pegawaitinggi'];

                        // Melakukan pembagian untuk rasio a/b
                        if ($gajiPegawaiTertinggi != 0) {
                            $rasioDirPegawai = $gajiDireksiTertinggi / $gajiPegawaiTertinggi;
                        } else {
                            $rasioDirPegawai = 0; // Menghindari pembagian dengan nol
                        }

                        // Menyimpan hasil perhitungan di kolom dirpegawaitinggirendah
                        $row['dirpegawaitinggirendah'] = $rasioDirPegawai; // Update kolom rasio dengan nilai hasil pembagian
                        ?>

                        <tr>
                            <th style="width: 30%;">Gaji Anggota Direksi Tertinggi :</th>
                            <td style="width: 70%;">
                                <?php
                                $gajiDireksiTertinggiFormatted = 'Rp ' . number_format($gajiDireksiTertinggi, 2, ',', '.');
                                echo $gajiDireksiTertinggiFormatted;
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th style="width: 30%;">Gaji Pegawai Tertinggi :</th>
                            <td style="width: 70%;">
                                <?php
                                $gajiPegawaiTertinggiFormatted = 'Rp ' . number_format($gajiPegawaiTertinggi, 2, ',', '.');
                                echo $gajiPegawaiTertinggiFormatted;
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th style="width: 30%;">Rasio (a/b) :</th>
                            <td style="width: 70%;">
                                <?php
                                // Menampilkan hasil rasio dengan format dua angka desimal
                                echo number_format($rasioDirPegawai, 2, ',', '.');
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
</div>
<!-- End Page Content -->

<!--edit data-->
<?php if (!empty($rasiogaji)) { ?>
    <div class="modal fade" id="modalUbahrasio">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah <?= $judul; ?> </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('rasiogaji/ubahrasio'); ?>" method="post">
                        <input type="hidden" name="id" id="id-rasiogaji">
                        <div class="mb-3">
                            <label for="pegawaitinggi" class="form-label">Ubah Gaji Pegawai Tertinggi:</label>
                            <input class="form-control" type="text" name="pegawaitinggi" id="pegawaitinggi"
                                value="<?= $row['pegawaitinggi'] ?>">
                        </div>
                        <div class="mb-3">
                            <label for="pegawairendah" class="form-label">Ubah Gaji Pegawai Terendah:</label>
                            <input class="form-control" type="text" name="pegawairendah" id="pegawairendah"
                                value="<?= $row['pegawairendah'] ?>">
                        </div>
                        <div class="mb-3">
                            <label for="dirtinggi" class="form-label">Ubah Gaji Direksi Tertinggi: </label>
                            <input class="form-control" type="text" name="dirtinggi" id="dirtinggi"
                                value="<?= $row['dirtinggi'] ?>">
                        </div>
                        <div class="mb-3">
                            <label for="dirrendah">Ubah Gaji DirekSi Terendah: </label>
                            <input class="form-control" type="text" name="dirrendah" id="dirrendah"
                                value="<?= $row['dirrendah'] ?>">
                        </div>
                        <div class="mb-3">
                            <label for="dekomtinggi" class="form-label">Ubah Gaji Anggota Dewan Komisaris Tertinggi:
                            </label>
                            <input class="form-control" type="text" name="dekomtinggi" id="dekomtinggi"
                                value="<?= $row['dekomtinggi'] ?>" placeholder="<?= $row['dekomtinggi'] ?>">
                        </div>
                        <div class="mb-3">
                            <label for="dekomrendah">Ubah Gaji Anggota Dewan Komisaris Terendah: </label>
                            <input class="form-control" type="text" name="dekomrendah" id="dekomrendah"
                                value="<?= $row['dekomrendah'] ?>" placeholder="<?= $row['dekomrendah'] ?>">
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="ubahrasio" class="btn btn-primary">Ubah Data</button>
                </div>
                </form>
            </div>
        </div>
    </div>
<?php } ?>

<div class="modal fade" id="modalTambahrasio">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah <?= $judul; ?> </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="<?= base_url('rasiogaji/tambahrasio'); ?>" method="post"
                onsubmit="bersihkanSemuaFormatRupiah()">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="pegawaitinggi">Input Nominal Gaji Pegawai Tertinggi:</label>
                        <input type="text" name="pegawaitinggi" id="pegawaitinggi" class="form-control"
                            oninput="formatRupiah(this)">
                    </div>
                    <div class="form-group">
                        <label for="pegawairendah">Input Nominal Gaji Pegawai Terendah:</label>
                        <input type="text" name="pegawairendah" id="pegawairendah" class="form-control"
                            oninput="formatRupiah(this)">
                    </div>
                    <div class="form-group">
                        <label for="dirtinggi">Input Nominal Gaji Direksi Tertinggi:</label>
                        <input type="text" name="dirtinggi" id="dirtinggi" class="form-control"
                            oninput="formatRupiah(this)">
                    </div>
                    <div class="form-group">
                        <label for="dirrendah">Input Nominal Gaji Direksi Terendah:</label>
                        <input type="text" name="dirrendah" id="dirrendah" class="form-control"
                            oninput="formatRupiah(this)">
                    </div>
                    <div class="form-group">
                        <label for="dekomtinggi">Input Nominal Gaji Dewan Komisaris Tertinggi:</label>
                        <input type="text" name="dekomtinggi" id="dekomtinggi" class="form-control"
                            oninput="formatRupiah(this)">
                    </div>
                    <div class="form-group">
                        <label for="dekomrendah">Input Nominal Gaji Dewan Komisaris Terendah:</label>
                        <input type="text" name="dekomrendah" id="dekomrendah" class="form-control"
                            oninput="formatRupiah(this)">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="tambahrasio" class="btn btn-primary">Tambah Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Fungsi untuk menampilkan format Rupiah
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

    // Fungsi untuk membersihkan format Rupiah dan hanya mengirimkan angka
    function bersihkanSemuaFormatRupiah() {
        let fields = ['pegawaitinggi', 'pegawairendah', 'dirtinggi', 'dirrendah', 'dekomtinggi', 'dekomrendah'];

        fields.forEach(function (field) {
            let inputField = document.getElementById(field);
            let angkaBersih = inputField.value.replace(/[^,\d]/g, '').replace(',', ''); // Menghapus format
            inputField.value = angkaBersih; // Mengganti value dengan angka yang bersih
        });
    }
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
                <form action="<?= base_url('rasiogaji/tambahkomentar'); ?>" method="post">
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
                            <a href="<?= base_url('rasiogaji/approveSemua') ?>"
                                class="btn btn-success shadow mt-3 mx-2"
                                onclick="return confirm('Apakah Anda yakin hendak melakukan approval?');">
                                Approve
                            </a>
                            <a href="<?= base_url('rasiogaji/unapproveSemua') ?>"
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

<div class="modal fade" id="modalHapusrasio">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                Apakah anda yakin ingin menghapus data></span>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btnHapusrasio">Yakin</button>
            </div>
        </div>
    </div>
</div>