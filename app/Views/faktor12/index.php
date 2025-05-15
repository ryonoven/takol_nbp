<!-- Begin Page Content -->
<div class="container-fluid">
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
    <!-- Faktor 12 -->
    <div class="card card-body">
        <div class="table-vertical">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <span class="label" style="flex: 3; margin-right: 10px; text-align: center;">
                    <h3>Faktor 12</h3>
                    <h4>Rencana Bisnis BPR</h4>
                </span>
                <span>
                    <?php
                    $allApproved = true;
                    foreach ($faktor12 as $item) {
                        if ($item['is_approved'] != 1) {
                            $allApproved = false;
                            break;
                        }
                    }
                    ?>
                    <?php if ($allApproved): ?>
                        <span class="badge badge-success" style="font-size: 14px;">
                            Disetujui oleh <strong><?= esc($fullname ?? '-') ?></strong><br>
                            <?= esc($faktor12[0]['approved_at'] ?? '-') ?>
                        </span>
                    <?php else: ?>
                        <span class="badge badge-secondary" style="font-size: 14px;">
                            Belum Disetujui Seluruhnya<br>Oleh Direksi
                        </span>
                    <?php endif; ?>
                </span>
            </div>
            <?php if ($userInGroupAdmin || $userInGroupDekom || $userInGroupDireksi): ?>
                <div class="col-md" style="display: flex; justify-content: flex-end; align-items: center;">
                    <a href="<?= base_url('faktor12/approveSemua') ?>" class="btn btn-success shadow mt-3 mr-2"
                        onclick="return confirm('Apakah Anda yakin ingin melakukan approval?');">
                        Approve
                    </a>
                    <a href="<?= base_url('faktor12/unapproveSemua') ?>" class="btn btn-danger shadow mt-3 mr-2"
                        onclick="return confirm('Batalkan semua approval?');">
                        Batalkan Approval
                    </a>
                </div>
            <?php endif; ?>
            <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                <div class="col-md">
                    <a href="/faktor12/excel" class="btn btn-outline-success shadow float-right mt-3">Excel <i
                            class="fa fa-file-excel"></i></a>
                </div>
            <?php endif; ?>
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <span class="label" style="flex: 3; margin-right: 10px;">
                    <strong>A. Struktur dan Infrastruktur Tata Kelola (S)</strong>
                </span>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <!-- Table header -->
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Sub Kategori</th>
                            <th>Nilai</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <!-- Table body -->
                    <tbody>
                        <?php if (empty($faktor12)) { ?>
                            <tr>
                                <td scope="row"></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($faktor12 as $row): ?>
                                <?php if ($row['sph'] == 'Struktur'): ?>
                                    <tr>
                                        <td scope="row"><?= $row['id']; ?></td>
                                        <td><?= $row['sub_category'] ?></td>
                                        <td><?= $row['nilai'] ?></td>
                                        <td><?= $row['keterangan'] ?></td>
                                        <td>
                                            <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                                <button type="button" data-toggle="modal" data-target="#modalUbah" id="btn-edit"
                                                    class="btn btn-sm" style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                                    data-sph="<?= $row['sph']; ?>" data-category="<?= $row['category']; ?>"
                                                    data-sub_category="<?= $row['sub_category']; ?>" data-nilai="<?= $row['nilai']; ?>"
                                                    data-keterangan="<?= $row['keterangan']; ?>"
                                                    data-komentar="<?= $row['komentar']; ?>"
                                                    data-created_at="<?= $row['created_at']; ?>"><i
                                                        class="fa fa-edit"></i>&nbsp;</button>
                                                <button type="button" class="btn btn-sm" style="font-weight: 600;"
                                                    data-id="<?= $row['id']; ?>" id="btn-set-null"><i
                                                        class="fas fa-trash-alt"></i>&nbsp;
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($userInGroupDekom || $userInGroupDireksi || $userInGroupPE || $userInGroupAdmin): ?>
                                                <button type="button" data-toggle="modal" data-target="#modaltambahKomentar"
                                                    id="btn-komentar" class="btn btn-sm" style="font-weight: 600;"
                                                    data-id="<?= $row['komentar']; ?>"> <i class="fas fa-comment"></i>&nbsp;
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <span class="label" style="flex: 3; margin-right: 10px;">
                    <strong>B. Proses Penerapan Tata Kelola (P)</strong>
                </span>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <!-- Table header -->
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Sub Kategori</th>
                            <th>Nilai</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <!-- Table body -->
                    <tbody>
                        <?php if (empty($faktor12)) { ?>
                            <tr>
                                <td scope="row"></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($faktor12 as $row): ?>
                                <?php if ($row['sph'] == 'Proses'): ?>
                                    <tr>
                                        <td scope="row"><?= $row['id']; ?></td>
                                        <td><?= $row['sub_category'] ?></td>
                                        <td><?= $row['nilai'] ?></td>
                                        <td><?= $row['keterangan'] ?></td>
                                        <td>
                                            <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                                <button type="button" data-toggle="modal" data-target="#modalUbah" id="btn-edit"
                                                    class="btn btn-sm" style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                                    data-sub_category="<?= $row['sub_category']; ?>" data-nilai="<?= $row['nilai']; ?>"
                                                    data-keterangan="<?= $row['keterangan']; ?>"><i
                                                        class="fa fa-edit"></i>&nbsp;</button>
                                                <button type="button" class="btn btn-sm" style="font-weight: 600;"
                                                    data-id="<?= $row['id']; ?>" id="btn-set-null"><i
                                                        class="fas fa-trash-alt"></i>&nbsp;
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($userInGroupDekom || $userInGroupDireksi || $userInGroupPE || $userInGroupAdmin): ?>
                                                <button type="button" data-toggle="modal" data-target="#modaltambahKomentar"
                                                    id="btn-komentar" class="btn btn-sm" style="font-weight: 600;"
                                                    data-id="<?= $row['komentar']; ?>"><i class="fas fa-comment"> </i>&nbsp;
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <span class="label" style="flex: 3; margin-right: 10px;">
                    <strong>C. Hasil Penerapan Tata Kelola (H)</strong>
                </span>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <!-- Table header -->
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Sub Kategori</th>
                            <th>Nilai</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <!-- Table body -->
                    <tbody>
                        <?php if (empty($faktor12)) { ?>
                            <tr>
                                <td scope="row"></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($faktor12 as $row): ?>
                                <?php if ($row['sph'] == 'Hasil'): ?>
                                    <tr>
                                        <td scope="row"><?= $row['id']; ?></td>
                                        <td><?= $row['sub_category'] ?></td>
                                        <td><?= $row['nilai'] ?></td>
                                        <td><?= $row['keterangan'] ?></td>
                                        <td>
                                            <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                                <button type="button" data-toggle="modal" data-target="#modalUbah" id="btn-edit"
                                                    class="btn btn-sm" style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                                    data-category="<?= $row['category']; ?>" data-nilai="<?= $row['nilai']; ?>"
                                                    data-keterangan="<?= $row['keterangan']; ?>"><i class="fa fa-edit"></i>&nbsp;
                                                </button>
                                                <button type="button" class="btn btn-sm" style="font-weight: 600;"
                                                    data-id="<?= $row['id']; ?>" id="btn-set-null"><i
                                                        class="fas fa-trash-alt"></i>&nbsp;
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($userInGroupDekom || $userInGroupDireksi || $userInGroupPE || $userInGroupAdmin): ?>
                                                <button type="button" data-toggle="modal" data-target="#modaltambahKomentar"
                                                    id="btn-komentar" class="btn btn-sm" style="font-weight: 600;"
                                                    data-id="<?= $row['komentar']; ?>"><i class="fas fa-comment"></i>&nbsp;
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>

<?php if (!empty($faktor12)) { ?>
    <div class="modal fade" id="modalUbah">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah <?= $judul; ?> </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('faktor12/ubah'); ?>" method="post">
                        <input type="hidden" name="id" id="id-faktor12">
                        <div class="mb-3">
                            <label for="sub_category" class="form-label">Sub Kategori: </label>
                            <textarea class="form-control" type="text" name="sub_category" id="sub_category"
                                style="height: 100px" value="<?= $row['sub_category'] ?? '' ?>"
                                placeholder="<?= $row['sub_category'] ?? '' ?>" disabled></textarea>
                        </div>
                        <div class="form-group">
                            <label for="nilai">Nilai: </label>
                            <select name="nilai" id="nilai" class="form-control">
                                <option>Pilih nilai faktor</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                            </select>
                        </div>

                        <div class="mt-3">
                            <button type="button" class="btn btn-outline-info btn-sm" id="lihatPanduanFaktor12">Lihat
                                Panduan</button>
                            <div id="panduanNilaiContainerFaktor12" style="display: none; margin-top: 15px;">
                                <p><strong>Panduan Pengisian Nilai:</strong></p>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Nilai Faktor</th>
                                            <th>Penjelasan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Nilai 1</td>
                                            <td>Apabila memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai
                                                ketentuan, proses penerapan tata kelola dilakukan dengan sangat memadai, dan
                                                ditunjukkan dengan hasil penerapan tata kelola yang sangat baik.<br>
                                                Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara
                                                lain:<br>
                                                a. Rencana bisnis BPR telah disusun secara realistis, komprehensif, dan
                                                terukur (achievable) oleh Direksi dan disetujui oleh Dewan Komisaris sesuai
                                                dengan visi dan misi BPR, serta menggambarkan rencana strategis jangka
                                                panjang dan rencana bisnis tahunan dan direalisasikan sesuai dengan
                                                perencanaan sehingga indikator kinerja keuangan dan nonkeuangan dalam
                                                rencana bisnis tercapai melebihi target yang ditetapkan, termasuk
                                                penyampaian laporan rencana bisnis secara lengkap, akurat, kini, utuh, dan
                                                tepat waktu.<br>
                                                b. Rencana bisnis BPR yang telah disusun didukung oleh pemegang saham yang
                                                ditunjukkan dengan pemenuhan seluruh komitmen dalam rangka memperkuat
                                                permodalan dan infrastruktur.
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Nilai 2</td>
                                            <td>Apabila memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai
                                                ketentuan, proses penerapan tata kelola dilakukan dengan memadai, dan
                                                ditunjukkan dengan hasil penerapan tata kelola yang baik.<br>
                                                Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara
                                                lain:<br>
                                                a. Rencana bisnis BPR telah disusun secara realistis, komprehensif, dan
                                                terukur (achievable) oleh Direksi dan disetujui oleh Dewan Komisaris sesuai
                                                dengan visi dan misi BPR, serta menggambarkan rencana strategis jangka
                                                panjang dan rencana bisnis tahunan dan direalisasikan sesuai dengan
                                                perencanaan sehingga indikator kinerja keuangan dan nonkeuangan dalam
                                                rencana bisnis tercapai sesuai target yang ditetapkan, termasuk penyampaian
                                                laporan rencana bisnis secara lengkap, akurat, kini, utuh, dan tepat
                                                waktu.<br>
                                                b. Rencana bisnis BPR yang telah disusun didukung oleh pemegang saham yang
                                                ditunjukkan dengan pemenuhan sebagian besar komitmen dalam rangka memperkuat
                                                permodalan dan infrastruktur.
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Nilai 3</td>
                                            <td>Apabila memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai
                                                ketentuan, proses penerapan tata kelola dilakukan dengan cukup memadai, dan
                                                ditunjukkan dengan hasil penerapan tata kelola yang cukup baik.<br>
                                                Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara
                                                lain:<br>
                                                "a. Rencana bisnis BPR telah disusun secara realistis, komprehensif, dan
                                                terukur (achievable) oleh Direksi dan disetujui oleh Dewan Komisaris sesuai
                                                dengan visi dan misi BPR, serta menggambarkan rencana strategis jangka
                                                panjang dan rencana bisnis tahunan dan sebagian besar direalisasikan sesuai
                                                dengan perencanaan sehingga indikator kinerja keuangan dan nonkeuangan dalam
                                                rencana bisnis tercapai sebagian sesuai target yang ditetapkan, termasuk
                                                penyampaian laporan rencana bisnis secara lengkap, akurat, kini, utuh,
                                                dan tepat waktu."<br>
                                                b. Rencana bisnis BPR yang telah disusun didukung oleh pemegang saham namun
                                                pemenuhan komitmen dalam rangka memperkuat permodalan dan infrastruktur
                                                hanya dilakukan sebagian.
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Nilai 4</td>
                                            <td>Apabila memenuhi kondisi belum sepenuhnya terpenuhi struktur dan/atau
                                                infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan
                                                dengan kurang memadai, dan ditunjukkan dengan hasil penerapan tata kelola
                                                yang kurang baik.<br>
                                                Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara
                                                lain:<br>
                                                a. Rencana bisnis BPR belum sepenuhnya disusun secara realistis,
                                                komprehensif, dan terukur (achievable) oleh Direksi dan disetujui oleh Dewan
                                                Komisaris, serta kurang menggambarkan rencana strategis jangka panjang dan
                                                rencana bisnis tahunan dan direalisasikan kurang sesuai dengan perencanaan
                                                sehingga indikator kinerja keuangan dan nonkeuangan dalam rencana bisnis
                                                tidak tercapai target yang ditetapkan, termasuk laporan rencana bisnis tidak
                                                sepenuhnya disampaikan secara lengkap, akurat, kini, utuh dan tepat
                                                waktu.<br>
                                                b. Rencana bisnis BPR yang telah disusun belum sepenuhnya didukung oleh
                                                pemegang saham yang ditunjukkan dengan pemenuhan sebagian kecil komitmen
                                                dalam rangka memperkuat permodalan dan infrastruktur.
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Nilai 5</td>
                                            <td>Apabila memenuhi kondisi tidak terpenuhi struktur dan/atau infrastruktur
                                                sesuai ketentuan, proses penerapan tata kelola dilakukan dengan tidak
                                                memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang tidak
                                                baik.<br>
                                                Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara
                                                lain:<br>
                                                a. Rencana bisnis BPR tidak disusun secara realistis, komprehensif, dan
                                                terukur (achievable) oleh Direksi dan disetujui oleh Dewan Komisaris, serta
                                                tidak menggambarkan rencana strategis jangka panjang dan rencana bisnis
                                                tahunan dan tidak direalisasikan sesuai dengan perencanaan sehingga
                                                indikator kinerja keuangan dan nonkeuangan dalam rencana bisnis tidak
                                                tercapai target yang ditetapkan, termasuk penyampaian laporan rencana bisnis
                                                secara tidak lengkap, tidak akurat, tidak kini, tidak utuh, dan melebihi
                                                batas waktu.<br>
                                                b. Rencana bisnis BPR yang telah disusun tidak didukung oleh pemegang saham
                                                yang ditunjukkan dengan tidak terdapat pemenuhan komitmen dalam rangka
                                                memperkuat permodalan dan infrastruktur.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="keterangan">Keterangan: </label>
                            <input class="form-control" type="text" name="keterangan" id="keterangan"
                                value="<?= $row['keterangan'] ?? '' ?>"></input>
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const lihatPanduanButtonFaktor12 = document.getElementById('lihatPanduanFaktor12');
        const panduanContainerFaktor12 = document.getElementById('panduanNilaiContainerFaktor12');

        if (lihatPanduanButtonFaktor12 && panduanContainerFaktor12) {
            lihatPanduanButtonFaktor12.addEventListener('click', function () {
                panduanContainerFaktor12.style.display = panduanContainerFaktor12.style.display === 'none' ? 'block' : 'none';
                lihatPanduanButtonFaktor12.textContent = panduanContainerFaktor12.style.display === 'none' ? 'Lihat Panduan' : 'Sembunyikan Panduan';
            });
        }
    });
</script>

<!-- Add your modaltambahKomentar code here -->
<div class="modal fade" id="modaltambahKomentar">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    Beri Komentar
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('faktor12/tambahKomentar'); ?>" method="post">
                    <input type="text" name="id" id="id-faktor">
                    <div class="form-group">
                        <label for="komentar">Komentar:</label>
                        <textarea class="form-control" type="text" name="komentar" id="komentar" style="height: 150px"
                            value="<?= $row['komentar'] ?>" placeholder="<?= $row['komentar'] ?>"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="komentar">Komentar:</label>
                        <textarea class="form-control" type="text" name="komentar" id="komentar" style="height: 150px"
                            value="<?= $row['komentar'] ?>" placeholder="<?= $row['komentar'] ?>" disabled></textarea>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" name="tambah" class="btn btn-primary">Simpan Data</button>
            </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const btnSetNulls = document.querySelectorAll('#btn-set-null');

        btnSetNulls.forEach(function (btn) {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-id');

                if (confirm("Apakah Anda yakin hendak menghapus data nilai dan keterangan ini?")) {
                    window.location.href = "/faktor12/setNullKolom/" + id;
                }
            });
        });
    });
</script>