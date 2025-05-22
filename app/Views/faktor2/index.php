<div class="container-fluid">
    <?php if (session()->get('message')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <strong><?= session()->getFlashdata('message'); ?></strong>
        </div>
    <?php endif; ?>

    <!-- Faktor 2 -->
    <div class="card card-body">
        <div class="table-vertical">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <span class="label" style="flex: 3; margin-right: 10px; text-align: center;">
                    <h3>Faktor 2</h3>
                    <h4>Pelaksanaan Tugas, Tanggung Jawab, dan Wewenang Direksi</h4>
                </span>
                <span>
                    <?php
                    $allApproved = true;
                    foreach ($faktor2 as $item) {
                        if ($item['is_approved'] != 1) {
                            $allApproved = false;
                            break;
                        }
                    }
                    ?>
                    <?php if ($allApproved): ?>
                        <span class="badge badge-success" style="font-size: 14px;">
                            Disetujui oleh <strong><?= esc($fullname ?? '-') ?></strong><br>
                            <?= esc($faktor2[0]['approved_at'] ?? '-') ?>
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
                    <a href="<?= base_url('faktor2/approveSemua') ?>" class="btn btn-success shadow mt-3 mr-2"
                        onclick="return confirm('Apakah Anda yakin ingin melakukan approval?');">
                        Approve
                    </a>
                    <a href="<?= base_url('faktor2/unapproveSemua') ?>" class="btn btn-danger shadow mt-3 mr-2"
                        onclick="return confirm('Batalkan semua approval?');">
                        Batalkan Approval
                    </a>
                </div>
            <?php endif; ?>
            <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                <div class="col-md">
                    <button onclick="window.print()"
                        class="btn btn-outline-secondary shadow float-right mr-3 ml-2 mt-3">Print <i
                            class="fa fa-print"></i> </button>
                    <a href="/faktor2/excel" class="btn btn-outline-success shadow float-right mt-3">Excel <i
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
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Sub Kategori</th>
                            <th>Nilai</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($faktor2)) { ?>
                            <tr>
                                <td scope="row"></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($faktor2 as $row): ?>
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
                        <?php if (empty($faktor2)) { ?>
                            <tr>
                                <td scope="row"></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($faktor2 as $row): ?>
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
                                            <?php if ($userInGroupDekom || $userInGroupDireksi || $userInGroupPE || $userInGroupAdmin): ?>
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
            <div style="display: flex; align-items;">
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
                        <?php if (empty($faktor2)) { ?>
                            <tr>
                                <td scope="row"></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($faktor2 as $row): ?>
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
                                                    data-komentar="<?= $row['komentar']; ?>"><i class="fa fa-edit"></i>&nbsp;
                                                </button>
                                                <button type="button" class="btn btn-sm" style="font-weight: 600;"
                                                    data-id="<?= $row['id']; ?>" id="btn-set-null"><i
                                                        class="fas fa-trash-alt"></i>&nbsp;
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($userInGroupDekom || $userInGroupDireksi || $userInGroupPE || $userInGroupAdmin): ?>
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
            <!-- <div class="card-body d-flex justify-content-center">
                <button type="button" class="btn btn-primary ml-3 mt-3" data-toggle="modal" data-target="#modalUbah">
                    <i class="fas fa-save"> Simpan Data </i>
                </button>
            </div> -->
            <!-- <div class="card-body d-flex justify-content-center">
                        <button type="button" class="btn btn-primary ml-2 mt-2" data-toggle="modal"
                            data-target="#modalUbah">
                            <i class="fas fa-edit"> Edit Data </i>
                        </button>
                    </div> -->
        </div>
    </div>
</div>
</div>

<?php if (!empty($faktor2)) { ?>
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
                    <form action="<?= base_url('faktor2/ubah'); ?>" method="post">
                        <input type="hidden" name="id" id="id-faktor2">
                        <div class="mb-3">
                            <label for="sub_category" class="form-label">Sub Kategori: </label>
                            <textarea class="form-control" type="text" name="sub_category" id="sub_category"
                                style="height: 100px" value="<?= $row['sub_category'] ?? '' ?>"
                                placeholder="<?= $row['sub_category'] ?? '' ?>" readonly></textarea>
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
                            <button type="button" class="btn btn-outline-info btn-sm" id="lihatPanduanFaktor2">Lihat
                                Panduan</button>
                            <div id="panduanNilaiContainerFaktor2" style="display: none; margin-top: 15px;">
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
                                                a. Direksi memenuhi seluruh persyaratan yang harus dipenuhi selama menjabat
                                                sesuai dengan ketentuan sehingga tugas dan tanggung jawab terlaksana dengan
                                                itikad baik, penuh tanggung jawab, kehati-hatian, dan independen, serta
                                                hasil kinerja Direksi dapat dipertanggungjawabkan sepenuhnya kepada pemegang
                                                saham melalui RUPS.<br>
                                                b. Direksi telah melakukan pemenuhan sumber daya manusia dan struktur
                                                organisasi, termasuk membentuk satuan kerja atau mengangkat Pejabat
                                                Eksekutif dengan kuantitas dan kualitas sesuai dengan ketentuan dengan
                                                mempertimbangkan kompleksitas kegiatan usaha dalam rangka mendukung
                                                pelaksanaan tugas dan fungsi Direksi sehingga penyelenggaraan kegiatan usaha
                                                pada seluruh jenjang organisasi telah sepenuhnya menerapkan prinsip tata
                                                kelola.<br>
                                                c. Direksi telah memiliki dan menginikan secara berkala pedoman dan tata
                                                tertib kerja anggota Direksi sehingga pelaksanaan tugas dan pengambilan
                                                keputusan rapat Direksi yang bersifat strategis terlaksana dengan
                                                memperhatikan pedoman dan tata tertib kerja.<br>
                                                d. Direksi memiliki kemauan dan kemampuan, serta upaya untuk membudayakan
                                                pembelajaran secara berkala dan berkelanjutan sehingga terdapat peningkatan
                                                pengetahuan, keahlian, dan kemampuan.<br>
                                                e. Direksi sesuai dengan tugas dan tanggung jawab melakukan tindak lanjut
                                                seluruh temuan audit atau pemeriksaan, dan rekomendasi dari satuan kerja
                                                atau pejabat yang bertanggung jawab terhadap pelaksanaan audit intern,
                                                auditor ekstern, dan hasil pengawasan Dewan Komisaris, Otoritas Jasa
                                                Keuangan, dan/atau otoritas lain sehingga tidak terdapat temuan serupa
                                                dan/atau temuan berulang.<br>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Nilai 2</td>
                                            <td>Apabila memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai
                                                ketentuan, proses penerapan tata kelola dilakukan dengan memadai, dan
                                                ditunjukkan dengan hasil penerapan tata kelola yang baik.<br>
                                                Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara
                                                lain:<br>
                                                a. Direksi memenuhi seluruh persyaratan yang harus dipenuhi selama menjabat
                                                sesuai dengan ketentuan sehingga tugas dan tanggung jawab terlaksana dengan
                                                baik namun terdapat kelemahan dalam tugas dan tanggung jawab yang tidak
                                                signifikan dan dapat diperbaiki dengan segera serta hasil kinerja Direksi
                                                dapat dipertanggungjawabkan kepada pemegang saham melalui RUPS.<br>
                                                b. Direksi telah melakukan pemenuhan sumber daya manusia dan struktur
                                                organisasi, termasuk membentuk satuan kerja atau mengangkat Pejabat
                                                Eksekutif dengan kuantitas dan kualitas sesuai dengan ketentuan dalam rangka
                                                mendukung pelaksanaan tugas dan fungsi Direksi sehingga penyelenggaraan
                                                kegiatan usaha pada seluruh jenjang organisasi telah menerapkan prinsip tata
                                                kelola dengan baik.<br>
                                                c. Direksi telah memiliki dan menginikan pedoman dan tata tertib kerja
                                                anggota Direksi sehingga pelaksanaan tugas dan pengambilan keputusan rapat
                                                Direksi yang bersifat strategis terlaksana dengan memperhatikan pedoman dan
                                                tata tertib kerja.<br>
                                                d. Direksi memiliki kemauan dan kemampuan, serta upaya untuk membudayakan
                                                pembelajaran secara berkala sehingga terdapat peningkatan pengetahuan,
                                                keahlian, dan kemampuan.<br>
                                                e. Direksi sesuai dengan tugas dan tanggung jawab telah melakukan tindak
                                                lanjut seluruh temuan audit atau pemeriksaan, dan rekomendasi dari satuan
                                                kerja atau pejabat yang bertanggung jawab terhadap pelaksanaan audit intern,
                                                auditor ekstern, dan hasil pengawasan Dewan Komisaris, Otoritas Jasa
                                                Keuangan, dan/atau otoritas lain namun terdapat temuan yang bersifat
                                                administratif.<br>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Nilai 3</td>
                                            <td>Apabila memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai
                                                ketentuan, proses penerapan tata kelola dilakukan dengan cukup memadai, dan
                                                ditunjukkan dengan hasil penerapan tata kelola yang cukup baik.<br>
                                                Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara
                                                lain:<br>
                                                a. Direksi memenuhi seluruh persyaratan yang harus dipenuhi selama menjabat
                                                sesuai dengan ketentuan sehingga tugas dan tanggung jawab terlaksana dengan
                                                cukup baik namun terdapat kelemahan dalam tugas dan tanggung jawab dan dapat
                                                diperbaiki serta hasil kinerja Direksi dapat dipertanggungjawabkan kepada
                                                pemegang saham melalui RUPS<br>
                                                b. Direksi telah melakukan pemenuhan sumber daya manusia dan struktur
                                                organisasi, termasuk membentuk satuan kerja atau mengangkat Pejabat
                                                Eksekutif dengan kuantitas dan kualitas sesuai dengan ketentuan dalam rangka
                                                mendukung pelaksanaan tugas dan fungsi Direksi sehingga penyelenggaraan
                                                kegiatan usaha pada seluruh jenjang organisasi telah menerapkan prinsip tata
                                                kelola dengan cukup baik.<br>
                                                c. Direksi telah memiliki pedoman dan tata tertib kerja anggota Direksi
                                                sehingga pelaksanaan tugas dan pengambilan keputusan rapat Direksi yang
                                                bersifat strategis terlaksana dengan memperhatikan pedoman dan tata tertib
                                                kerja.<br>
                                                d. Direksi memiliki kemauan dan kemampuan, serta upaya untuk membudayakan
                                                pembelajaran sehingga terdapat peningkatan pengetahuan, keahlian, dan
                                                kemampuan.<br>
                                                e. Direksi sesuai dengan tugas dan tanggung jawab telah melakukan tindak
                                                lanjut seluruh temuan audit atau pemeriksaan, dan rekomendasi dari satuan
                                                kerja atau pejabat yang bertanggung jawab terhadap pelaksanaan audit intern,
                                                auditor ekstern, dan hasil pengawasan Dewan Komisaris, Otoritas Jasa
                                                Keuangan, dan/atau otoritas lain namun terdapat temuan berulang yang
                                                bersifat administratif.
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
                                                a. Direksi memenuhi sebagian persyaratan yang harus dipenuhi selama menjabat
                                                sesuai dengan ketentuan sehingga pelaksanaan tugas dan tanggung jawab kurang
                                                berjalan dengan baik dan hasil kinerja Direksi tidak sepenuhnya dapat
                                                dipertanggungjawabkan kepada pemegang saham melalui RUPS.<br>
                                                b. Direksi tidak melakukan pemenuhan sumber daya manusia dan struktur
                                                organisasi, termasuk pembentukan satuan kerja atau pengangkatan Pejabat
                                                Eksekutif dengan kuantitas dan kualitas yang tidak sesuai dengan ketentuan
                                                sehingga kurang mendukung pelaksanaan tugas dan fungsi Direksi sehingga
                                                penyelenggaraan kegiatan usaha pada seluruh jenjang organisasi tidak
                                                sepenuhnya menerapkan prinsip tata kelola.<br>
                                                c. Direksi telah memiliki pedoman dan tata tertib kerja anggota Direksi
                                                namun ruang lingkup belum sesuai dengan ketentuan sehingga pelaksanaan tugas
                                                dan pengambilan keputusan rapat Direksi yang bersifat strategis tidak
                                                terlaksana dengan baik.<br>
                                                d. Direksi kurang memiliki kemauan dan kemampuan, serta upaya untuk
                                                membudayakan pembelajaran secara berkelanjutan sehingga tidak terdapat
                                                peningkatan pengetahuan, keahlian, dan kemampuan.<br>
                                                e. Direksi telah melakukan tindak lanjut terhadap sebagian temuan audit atau
                                                pemeriksaan, dan rekomendasi dari satuan kerja atau pejabat yang bertanggung
                                                jawab terhadap pelaksanaan audit intern, auditor ekstern, dan hasil
                                                pengawasan Dewan Komisaris, Otoritas Jasa Keuangan, dan/atau otoritas lain
                                                sehingga terdapat temuan dan/atau temuan berulang yang bersifat substantif.
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
                                                a. Direksi tidak memenuhi seluruh persyaratan yang harus dipenuhi selama
                                                menjabat sesuai dengan ketentuan sehingga pelaksanaan tugas dan tanggung
                                                jawab tidak berjalan dengan baik dan hasil kinerja Direksi tidak dapat
                                                dipertanggungjawabkan kepada pemegang saham melalui RUPS.<br>
                                                b. Direksi tidak melakukan pemenuhan sumber daya manusia dan struktur
                                                organisasi, termasuk tidak membentuk satuan kerja atau mengangkat Pejabat
                                                Eksekutif sesuai dengan ketentuan dalam rangka mendukung pelaksanaan tugas
                                                dan fungsi Direksi sehingga prinsip tata kelola tidak dapat diterapkan dalam
                                                penyelenggaraan kegiatan usaha pada seluruh jenjang organisasi.<br>
                                                c. Direksi tidak memiliki pedoman dan tata tertib kerja anggota Direksi
                                                sehingga pelaksanaan tugas dan pengambilan keputusan rapat Direksi yang
                                                bersifat strategis tidak dapat terlaksana dengan baik.<br>
                                                d. Direksi tidak memiliki pedoman dan tata tertib kerja anggota Direksi
                                                sehingga pelaksanaan tugas dan pengambilan keputusan rapat Direksi yang
                                                bersifat strategis tidak dapat terlaksana dengan baik. Direksi tidak
                                                memiliki kemauan dan kemampuan, serta upaya untuk membudayakan pembelajaran
                                                secara berkelanjutan sehingga tidak terdapat peningkatan pengetahuan,
                                                keahlian, dan kemampuan.<br>
                                                e. Direksi tidak melakukan tindak lanjut seluruh temuan audit atau
                                                pemeriksaan, dan rekomendasi dari satuan kerja atau pejabat yang bertanggung
                                                jawab terhadap pelaksanaan audit intern, auditor ekstern, dan hasil
                                                pengawasan Dewan Komisaris, Otoritas Jasa Keuangan, dan/atau otoritas lain
                                                sehingga terdapat temuan dan/atau temuan berulang yang bersifat substantif.
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
        const lihatPanduanButtonFaktor2 = document.getElementById('lihatPanduanFaktor2');
        const panduanContainerFaktor2 = document.getElementById('panduanNilaiContainerFaktor2');

        if (lihatPanduanButtonFaktor2 && panduanContainerFaktor2) {
            lihatPanduanButtonFaktor2.addEventListener('click', function () {
                panduanContainerFaktor2.style.display = panduanContainerFaktor2.style.display === 'none' ? 'block' : 'none';
                lihatPanduanButtonFaktor2.textContent = panduanContainerFaktor2.style.display === 'none' ? 'Lihat Panduan' : 'Sembunyikan Panduan';
            });
        }
    });
</script>

<!-- Add your modaltambahKomentar code here -->
<div class="modal fade" id="modaltambahKomentar">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Komentar Dewan Komisaris </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('faktor2/tambahKomentar'); ?>" method="post">
                    <input type="hidden" name="id" id="id-faktor2">
                    <div class="form-group" style="position: relative;">
                        <label for="komentar_disabled">Komentar:</label>
                        <textarea class="form-control" type="text" name="komentar_disabled" id="komentar_disabled"
                            style="height: 150px" value="<?= $row['komentar'] ?>" placeholder="<?= $row['komentar'] ?>"
                            readonly>
                        </textarea>
                    </div>
                    <div class="form-group">
                        <label for="komentar">Komentar:</label>
                        <textarea class="form-control" type="text" name="komentar" id="komentar" style="height: 150px"
                            value="<?= $row['komentar'] ?>" placeholder="<?= $row['komentar'] ?>"></textarea>
                    </div>
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
                    window.location.href = "/faktor2/setNullKolom/" + id;
                }
            });
        });
    });
</script>