<div class="container-fluid">
    <?php if (session()->get('message')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <strong><?= session()->getFlashdata('message'); ?></strong>
        </div>
    <?php endif; ?>
    <!-- Faktor 3 -->
    <div class="card card-body">
        <div class="table-vertical">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <span class="label" style="flex: 3; margin-right: 10px; text-align: center;">
                    <h3>Faktor 3</h3>
                    <h4>Pelaksanaan Tugas, Tanggung Jawab, dan Wewenang Komisaris</h4>
                </span>
                <span>
                    <?php
                    $allApproved = true;
                    foreach ($faktor3 as $item) {
                        if ($item['is_approved'] != 1) {
                            $allApproved = false;
                            break;
                        }
                    }
                    ?>
                    <?php if ($allApproved): ?>
                        <span class="badge badge-success" style="font-size: 14px;">
                            Disetujui oleh <strong><?= esc($fullname ?? '-') ?></strong><br>
                            <?= esc($faktor3[0]['approved_at'] ?? '-') ?>
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
                    <a href="<?= base_url('faktor3/approveSemua') ?>" class="btn btn-success shadow mt-3 mr-2"
                        onclick="return confirm('Apakah Anda yakin ingin melakukan approval?');">
                        Approve
                    </a>
                    <a href="<?= base_url('faktor3/unapproveSemua') ?>" class="btn btn-danger shadow mt-3 mr-2"
                        onclick="return confirm('Batalkan semua approval?');">
                        Batalkan Approval
                    </a>
                </div>
            <?php endif; ?>
            <!-- <div class="col-md">
                <a href="/faktor3/excel" class="btn btn-outline-success shadow float-right mt-3">Excel <i
                        class="fa fa-file-excel"></i></a>
            </div> -->
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
                        <?php if (empty($faktor3)) { ?>
                            <tr>
                                <td scope="row"></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($faktor3 as $row): ?>
                                <?php if ($row['sph'] == 'Struktur'): ?>
                                    <tr>
                                        <td scope="row"><?= $row['id']; ?></td>
                                        <td><?= $row['sub_category'] ?></td>
                                        <td><?= $row['nilai'] ?></td>
                                        <td><?= $row['keterangan'] ?></td>
                                        <td>
                                            <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                                <!-- Button to trigger confirmation modal -->
                                                <button type="button" data-toggle="modal" data-target="#modalUbah" id="btn-edit"
                                                    class="btn btn-sm" style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                                    data-sph="<?= $row['sph']; ?>" data-category="<?= $row['category']; ?>"
                                                    data-sub_category="<?= $row['sub_category']; ?>" data-nilai="<?= $row['nilai']; ?>"
                                                    data-keterangan="<?= $row['keterangan']; ?>"
                                                    data-komentar="<?= $row['komentar']; ?>"
                                                    data-created_at="<?= $row['created_at']; ?>"><i class="fa fa-edit"></i>&nbsp;
                                                </button>
                                                <button type="button" class="btn btn-sm" style="font-weight: 600;"
                                                    data-id="<?= $row['id']; ?>" id="btn-set-null"><i
                                                        class="fas fa-trash-alt"></i>&nbsp;
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($userInGroupAdmin || $userInGroupDekom || $userInGroupDireksi): ?>
                                                <button type="button" data-toggle="modal" data-target="#modaltambahKomentar"
                                                    id="btn-komentar" class="btn btn-sm" style="font-weight: 600;"
                                                    data-id="<?= $row['id']; ?>" data-komentar="<?= $row['komentar']; ?>"> <i
                                                        class="fas fa-comment"></i>&nbsp;
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
                        <?php if (empty($faktor3)) { ?>
                            <tr>
                                <td scope="row"></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($faktor3 as $row): ?>
                                <?php if ($row['sph'] == 'Proses'): ?>
                                    <tr>
                                        <td scope="row"><?= $row['id']; ?></td>
                                        <td><?= $row['sub_category'] ?></td>
                                        <td><?= $row['nilai'] ?></td>
                                        <td><?= $row['keterangan'] ?></td>
                                        <td>
                                            <!-- Button to trigger confirmation modal -->
                                            <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                                <button type="button" data-toggle="modal" data-target="#modalUbah" id="btn-edit"
                                                    class="btn btn-sm" style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                                    data-sub_category="<?= $row['sub_category']; ?>" data-nilai="<?= $row['nilai']; ?>"
                                                    data-keterangan="<?= $row['keterangan']; ?>"><i class="fa fa-edit"></i>&nbsp;
                                                </button>
                                                <button type="button" class="btn btn-sm" style="font-weight: 600;"
                                                    data-id="<?= $row['id']; ?>" id="btn-set-null"><i
                                                        class="fas fa-trash-alt"></i>&nbsp;
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($userInGroupAdmin || $userInGroupDekom || $userInGroupDireksi): ?>
                                                <button type="button" data-toggle="modal" data-target="#modaltambahKomentar"
                                                    id="btn-komentar" class="btn btn-sm" style="font-weight: 600;"
                                                    data-id="<?= $row['id']; ?>" data-komentar="<?= $row['komentar']; ?>"> <i
                                                        class="fas fa-comment"></i>&nbsp;
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
                        <?php if (empty($faktor3)) { ?>
                            <tr>
                                <td scope="row"></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($faktor3 as $row): ?>
                                <?php if ($row['sph'] == 'Hasil'): ?>
                                    <tr>
                                        <td scope="row"><?= $row['id']; ?></td>
                                        <td><?= $row['sub_category'] ?></td>
                                        <td><?= $row['nilai'] ?></td>
                                        <td><?= $row['keterangan'] ?></td>
                                        <td>
                                            <!-- Button to trigger confirmation modal -->
                                            <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                                <button type="button" data-toggle="modal" data-target="#modalUbah" id="btn-edit"
                                                    class="btn btn-sm" style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                                    data-sph="<?= $row['sph']; ?>" data-category="<?= $row['category']; ?>"
                                                    data-sub_category="<?= $row['sub_category']; ?>" data-nilai="<?= $row['nilai']; ?>"
                                                    data-keterangan="<?= $row['keterangan']; ?>"
                                                    data-komentar="<?= $row['komentar']; ?>"
                                                    data-created_at="<?= $row['created_at']; ?>"><i class="fa fa-edit"></i>&nbsp;
                                                </button>
                                                <button type="button" class="btn btn-sm" style="font-weight: 600;"
                                                    data-id="<?= $row['id']; ?>" id="btn-set-null"><i
                                                        class="fas fa-trash-alt"></i>&nbsp;
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($userInGroupAdmin || $userInGroupDekom || $userInGroupDireksi): ?>
                                                <button type="button" data-toggle="modal" data-target="#modaltambahKomentar"
                                                    id="btn-komentar" class="btn btn-sm" style="font-weight: 600;"
                                                    data-id="<?= $row['id']; ?>" data-komentar="<?= $row['komentar']; ?>"> <i
                                                        class="fas fa-comment"></i>&nbsp;
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

<?php if (!empty($faktor3)) { ?>
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
                    <form action="<?= base_url('faktor3/ubah'); ?>" method="post">
                        <input type="hidden" name="id" id="id-faktor3">
                        <div class="mb-3">
                            <label for="sub_category" class="form-label">Sub Kategori: </label>
                            <textarea class="form-control" type="text" name="sub_category" id="sub_category"
                                style="height: 100px" value="<?= $row['sub_category'] ?? '' ?>"
                                placeholder="<?= $row['sub_category'] ?? '' ?>" disabled></textarea>
                        </div>
                        <div class="form-group">
                            <label for="nilai">Nilai: </label>
                            <select name="nilai" id="nilai" class="form-control" required>
                                <option>Pilih nilai faktor</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                            </select>
                        </div>

                        <div class="mt-3">
                            <button type="button" class="btn btn-outline-info btn-sm" id="lihatPanduan">Lihat
                                Panduan Pengisian Nilai</button>
                            <div id="panduanNilaiContainer" style="display: none; margin-top: 15px;">
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
                                                a. Dewan Komisaris memenuhi seluruh persyaratan yang harus dipenuhi selama
                                                menjabat sesuai dengan ketentuan sehingga pelaksanaan tugas dan tanggung
                                                jawab, termasuk pengambilan keputusan berjalan dengan sangat baik serta
                                                hasil kinerja Dewan Komisaris dapat dipertanggungjawabkan sepenuhnya kepada
                                                pemegang saham melalui RUPS.<br>
                                                b. Dewan Komisaris telah memiliki dan menginikan secara berkala pedoman dan
                                                tata tertib kerja anggota Dewan Komisaris sehingga pelaksanaan tugas dan
                                                pengambilan keputusan rapat Dewan Komisaris terlaksana dengan memperhatikan
                                                pedoman dan tata tertib kerja.<br>
                                                c. Dewan Komisaris memiliki kemauan dan kemampuan, serta upaya untuk
                                                membudayakan pembelajaran secara berkala dan berkelanjutan, sehingga
                                                terdapat peningkatan pengetahuan, keahlian, dan kemampuan.<br>
                                                d. Dewan Komisaris telah memiliki dan menginikan secara berkala kebijakan
                                                remunerasi dan nominasi sehingga pelaksanaan tugas terlaksana dengan
                                                memperhatikan kebijakan remunerasi dan nominasi.


                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Nilai 2</td>
                                            <td>Apabila memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai
                                                ketentuan, proses penerapan tata kelola dilakukan dengan memadai, dan
                                                ditunjukkan dengan hasil penerapan tata kelola yang baik.<br>
                                                Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara
                                                lain:<br>
                                                a. Dewan Komisaris memenuhi seluruh persyaratan yang harus dipenuhi selama
                                                menjabat sesuai dengan ketentuan sehingga pelaksanaan tugas dan tanggung
                                                jawab, termasuk pengambilan keputusan berjalan dengan baik serta hasil
                                                kinerja Dewan Komisaris dapat dipertanggungjawabkan kepada pemegang saham
                                                melalui RUPS.<br>
                                                b. Dewan Komisaris telah memiliki dan menginikan pedoman dan tata tertib
                                                kerja anggota Dewan Komisaris sehingga pelaksanaan tugas dan pengambilan
                                                keputusan rapat Dewan Komisaris terlaksana dengan memperhatikan pedoman dan
                                                tata tertib kerja.<br>
                                                c. Dewan Komisaris memiliki kemauan dan kemampuan, serta upaya untuk
                                                membudayakan pembelajaran secara berkala, sehingga terdapat peningkatan
                                                pengetahuan, keahlian, dan kemampuan.<br>
                                                d. Dewan Komisaris telah memiliki dan menginikan kebijakan remunerasi dan
                                                nominasi sehingga pelaksanaan tugas terlaksana dengan memperhatikan
                                                kebijakan remunerasi dan nominasi.

                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Nilai 3</td>
                                            <td>Apabila memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai
                                                ketentuan, proses penerapan tata kelola dilakukan dengan cukup memadai, dan
                                                ditunjukkan dengan hasil penerapan tata kelola yang cukup baik.<br>
                                                Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara
                                                lain:<br>
                                                a. Dewan Komisaris memenuhi seluruh persyaratan yang harus dipenuhi selama
                                                menjabat sesuai dengan ketentuan sehingga pelaksanaan tugas dan tanggung
                                                jawab, termasuk pengambilan keputusan berjalan dengan cukup baik serta hasil
                                                kinerja Dewan Komisaris dapat dipertanggungjawabkan kepada pemegang saham
                                                melalui RUPS.<br>
                                                b. Dewan Komisaris telah memiliki pedoman dan tata tertib kerja anggota
                                                Dewan Komisaris sehingga pelaksanaan tugas dan pengambilan keputusan rapat
                                                Dewan Komisaris terlaksana dengan memperhatikan pedoman dan tata tertib
                                                kerja.<br>
                                                c. Dewan Komisaris memiliki kemauan dan kemampuan, serta upaya untuk
                                                membudayakan pembelajaran, sehingga terdapat peningkatan pengetahuan,
                                                keahlian, dan kemampuan.<br>
                                                d. Dewan Komisaris telah memiliki kebijakan remunerasi dan nominasi sehingga
                                                pelaksanaan tugas terlaksana dengan memperhatikan kebijakan remunerasi dan
                                                nominasi.

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
                                                a. Dewan Komisaris memenuhi sebagian persyaratan yang harus dipenuhi selama
                                                menjabat sesuai dengan ketentuan sehingga pelaksanaan tugas dan tanggung
                                                jawab, termasuk pengambilan keputusan berjalan dengan kurang baik serta
                                                hasil kinerja Dewan Komisaris tidak sepenuhnya dapat dipertanggungjawabkan
                                                kepada pemegang saham melalui RUPS.<br>
                                                b. Dewan Komisaris telah memiliki pedoman dan tata tertib kerja anggota
                                                Dewan Komisaris namun ruang lingkup belum sesuai dengan ketentuan.
                                                c. Dewan Komisaris kurang memiliki kemauan dan kemampuan, serta upaya untuk
                                                membudayakan pembelajaran secara berkala, sehingga tidak terdapat
                                                peningkatan pengetahuan, keahlian, dan kemampuan.
                                                d. Dewan Komisaris telah memiliki kebijakan remunerasi dan nominasi namun
                                                ruang lingkup belum sesuai dengan ketentuan sehingga pelaksanaan tugas tidak
                                                terlaksana dengan baik.
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
                                                a. Dewan Komisaris tidak memenuhi seluruh persyaratan yang harus dipenuhi
                                                selama menjabat sesuai dengan ketentuan sehingga pelaksanaan tugas dan
                                                tanggung jawab, termasuk pengambilan keputusan tidak berjalan dengan baik
                                                dan hasil kinerja Dewan Komisaris tidak dapat dipertanggungjawabkan kepada
                                                pemegang saham melalui RUPS.<br>
                                                b. Dewan Komisaris tidak memenuhi seluruh persyaratan yang harus dipenuhi
                                                selama menjabat sesuai dengan ketentuan sehingga pelaksanaan tugas dan
                                                tanggung jawab, termasuk pengambilan keputusan tidak berjalan dengan baik
                                                dan hasil kinerja Dewan Komisaris tidak dapat dipertanggungjawabkan kepada
                                                pemegang saham melalui RUPS. Dewan Komisaris tidak memiliki pedoman dan tata
                                                tertib kerja anggota Dewan Komisaris sehingga pelaksanaan tugas dan
                                                pengambilan keputusan rapat Dewan Komisaris tidak dapat terlaksana dengan
                                                baik.<br>
                                                c. Dewan Komisaris tidak memiliki kemauan dan kemampuan, serta upaya untuk
                                                membudayakan pembelajaran secara berkala, sehingga tidak terdapat
                                                peningkatan pengetahuan, keahlian, dan kemampuan.<br>
                                                d. Dewan Komisaris tidak memiliki kebijakan remunerasi dan nominasi sehingga
                                                pelaksanaan tugas tidak dapat terlaksana dengan baik.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="keterangan" class="form-label">Keterangan: </label>
                            <textarea class="form-control" type="text" name="keterangan" id="keterangan"
                                style="height: 120px" value="<?= $row['keterangan'] ?? '' ?>"
                                placeholder="<?= $row['keterangan'] ?? '' ?>" required></textarea>
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
        const lihatPanduanButton = document.getElementById('lihatPanduan');
        const panduanContainer = document.getElementById('panduanNilaiContainer');

        if (lihatPanduanButton && panduanContainer) {
            lihatPanduanButton.addEventListener('click', function () {
                panduanContainer.style.display = panduanContainer.style.display === 'none' ? 'block' : 'none';
                lihatPanduanButton.textContent = panduanContainer.style.display === 'none' ? 'Lihat Panduan' : 'Sembunyikan Panduan';
            });
        }
    });
</script>


<!-- Add your modaltambahKomentar code here -->
<div class="modal fade" id="modaltambahKomentar">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Komentar Dewan Komisaris</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('faktor3/tambahKomentar'); ?>" method="post">
                    <input type="hidden" name="id" id="id-faktor3">
                    <div class="form-group">
                        <label for="komentar">Input Komentar:</label>
                        <textarea class="form-control" type="text" name="komentar" id="komentar" style="height: 150px"
                            value="<?= $row['komentar'] ?>" placeholder="<?= $row['komentar'] ?>"></textarea>
                    </div>
                    <?php if ($userInGroupAdmin || $userInGroupDekom || $userInGroupDireksi): ?>
                        <div class="form-group">
                            <label for="komentar">Komentar:</label>
                            <textarea class="form-control" type="text" name="komentar" id="komentar" style="height: 150px"
                                value="<?= $row['komentar'] ?>" placeholder="<?= $row['komentar'] ?>" disabled></textarea>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" name="tambahKomentar" class="btn btn-primary">Simpan Data</button>
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
                    window.location.href = "/faktor3/setNullKolom/" + id;
                }
            });
        });
    });
</script>