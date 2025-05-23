<div class="container-fluid">
    <?php if (session()->get('message')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <strong><?= session()->getFlashdata('message'); ?></strong>
        </div>
    <?php endif; ?>
    <!-- Faktor 1 -->
    <div class="card card-body">
        <div class="table-vertical">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <span class="label" style="flex: 3; margin-right: 10px; text-align: center;">
                    <h3>Faktor 1</h3>
                    <h4>Aspek Pemegang Saham</h4>
                </span>
                <span>
                    <?php
                    $allApproved = true;
                    foreach ($faktors as $item) {
                        if (!isset($item['is_approved']) || $item['is_approved'] != 1) {
                            $allApproved = false;
                            break;
                        }
                    }
                    ?>
                    <?php if ($allApproved): ?>
                        <span class="badge badge-success" style="font-size: 14px;">
                            Disetujui oleh <strong><?= esc($fullname ?? '-') ?></strong><br>
                            <?= esc($faktors[0]['approved_at'] ?? '-') ?>
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
                    <a href="<?= base_url('faktor/approveSemua') ?>" class="btn btn-success shadow mt-3 mr-2"
                        onclick="return confirm('Apakah Anda yakin ingin melakukan approval?');">
                        Approve
                    </a>
                    <a href="<?= base_url('faktor/unapproveSemua') ?>" class="btn btn-danger shadow mt-3 mr-2"
                        onclick="return confirm('Batalkan semua approval?');">
                        Batalkan Approval
                    </a>
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
                        <?php if (empty($faktors)) { ?>
                            <tr>
                                <td scope="row"></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($faktors as $row): ?>
                                <?php if ($row['sph'] == 'Struktur'): ?>
                                    <tr>
                                        <td scope="row"><?= $row['id']; ?></td>
                                        <td><?= $row['sub_category'] ?></td>
                                        <td><?= $row['nilai'] ?? '' ?></td>
                                        <td><?= $row['keterangan'] ?? '' ?></td>
                                        <td>
                                            <?php if (empty($row['nilai']) && empty($row['keterangan'])): ?>
                                                <button type="button" data-toggle="modal" data-target="#modaltambahNilai"
                                                    id="btn-tambah" class="btn btn-sm" style="font-weight: 600;"
                                                    data-id="<?= $row['id']; ?>" data-sub_category="<?= $row['sub_category']; ?>">
                                                    <i class="fas fa-plus"></i>&nbsp;
                                                </button>
                                            <?php else: ?>
                                                <button type="button" data-toggle="modal" data-target="#modalUbah" id="btn-edit"
                                                    class="btn btn-sm" style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                                    data-sph="<?= $row['sph']; ?>" data-sub_category="<?= $row['sub_category']; ?>"
                                                    data-nilai="<?= $row['nilai']; ?>" data-keterangan="<?= $row['keterangan']; ?>">
                                                    <i class="fa fa-edit"></i>&nbsp;
                                                </button>
                                                <button type="button" data-toggle="modal" data-target="#modalHapusnilai" id="btn-hapus"
                                                    class="btn" style="font-weight: 600;" data-id="<?= $row['id']; ?>"> <i
                                                        class="fas fa-trash-alt"></i>&nbsp;
                                                </button>
                                                <button type="button" data-toggle="modal" data-target="#modaltambahKomentar"
                                                    id="btn-komentar" class="btn btn-sm" style="font-weight: 600;"
                                                    data-id="<?= $row['id']; ?>"><i class="fas fa-comment"></i>&nbsp;
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
                        <?php if (empty($faktors)) { ?>
                            <tr>
                                <td scope="row"></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($faktors as $row): ?>
                                <?php if ($row['sph'] == 'Proses'): ?>
                                    <tr>
                                        <td scope="row"><?= $row['id']; ?></td>
                                        <td><?= $row['sub_category'] ?></td>
                                        <td><?= $row['nilai'] ?></td>
                                        <td><?= $row['keterangan'] ?></td>
                                        <td>
                                            <?php if (empty($row['nilai']) && empty($row['keterangan'])): ?>
                                                <button type="button" data-toggle="modal" data-target="#modaltambahNilai"
                                                    id="btn-tambah" class="btn btn-sm" style="font-weight: 600;"
                                                    data-id="<?= $row['id']; ?>" data-sub_category="<?= $row['sub_category']; ?>">
                                                    <i class="fas fa-plus"></i>&nbsp;
                                                </button>
                                            <?php else: ?>
                                                <button type="button" data-toggle="modal" data-target="#modalUbah" id="btn-edit"
                                                    class="btn btn-sm" style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                                    data-sph="<?= $row['sph']; ?>" data-sub_category="<?= $row['sub_category']; ?>"
                                                    data-nilai="<?= $row['nilai']; ?>" data-keterangan="<?= $row['keterangan']; ?>">
                                                    <i class="fa fa-edit"></i>&nbsp;
                                                </button>
                                                <button type="button" data-toggle="modal" data-target="#modalHapusnilai" id="btn-hapus"
                                                    class="btn" style="font-weight: 600;" data-id="<?= $row['id']; ?>"> <i
                                                        class="fas fa-trash-alt"></i>&nbsp;
                                                </button>
                                                <button type="button" data-toggle="modal" data-target="#modaltambahKomentar"
                                                    id="btn-komentar" class="btn btn-sm" style="font-weight: 600;"
                                                    data-id="<?= $row['id']; ?>"><i class="fas fa-comment"></i>&nbsp;
                                                </button>
                                            <?php endif; ?>
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
                        <?php if (empty($faktors)) { ?>
                            <tr>
                                <td scope="row"></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($faktors as $row): ?>
                                <?php if ($row['sph'] == 'Hasil'): ?>
                                    <tr>
                                        <td scope="row"><?= $row['id']; ?></td>
                                        <td><?= $row['sub_category'] ?></td>
                                        <td><?= $row['nilai'] ?></td>
                                        <td><?= $row['keterangan'] ?></td>
                                        <td>
                                            <?php if (empty($row['nilai']) && empty($row['keterangan'])): ?>
                                                <button type="button" data-toggle="modal" data-target="#modaltambahNilai"
                                                    id="btn-tambah" class="btn btn-sm" style="font-weight: 600;"
                                                    data-id="<?= $row['id']; ?>" data-sub_category="<?= $row['sub_category']; ?>">
                                                    <i class="fas fa-plus"></i>&nbsp;
                                                </button>
                                            <?php else: ?>
                                                <button type="button" data-toggle="modal" data-target="#modalUbah" id="btn-edit"
                                                    class="btn btn-sm" style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                                    data-sph="<?= $row['sph']; ?>" data-sub_category="<?= $row['sub_category']; ?>"
                                                    data-nilai="<?= $row['nilai']; ?>" data-keterangan="<?= $row['keterangan']; ?>">
                                                    <i class="fa fa-edit"></i>&nbsp;
                                                </button>
                                                <button type="button" data-toggle="modal" data-target="#modalHapusnilai" id="btn-hapus"
                                                    class="btn" style="font-weight: 600;" data-id="<?= $row['id']; ?>"> <i
                                                        class="fas fa-trash-alt"></i>&nbsp;
                                                </button>
                                                <button type="button" data-toggle="modal" data-target="#modaltambahKomentar"
                                                    id="btn-komentar" class="btn btn-sm" style="font-weight: 600;"
                                                    data-id="<?= $row['id']; ?>"><i class="fas fa-comment"></i>&nbsp;
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
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <span class="label" style="flex: 3; margin-right: 10px;">
                <strong>Kesimpulan Penilaian Faktor 1 (Aspek Pemegang Saham)</strong>
            </span>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <!-- Table header -->
                <thead>
                    <tr>
                        <th colspan="5" class="text-center">Nilai Faktor 1</th>
                    </tr>
                    <!-- Display the average value here -->
                    <tr>
                        <th colspan="5" class="text-center"><?= round($rataRata, 2) ?></th>
                    </tr>
                </thead>
                <!-- Table body -->
                <tbody>
                    <?php if (empty($faktors)) { ?>
                        <tr>
                            <td scope="row"></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    <?php } else { ?>
                        <?php foreach ($faktors as $row): ?>
                            <?php if ($row['sph'] == 'Kesimpulan'): ?>
                                <tr>
                                    <td scope="row"></td>
                                    <td><?= $row['sub_category'] ?></td>
                                    <td><?= $row['nilai'] ?></td>
                                    <td><?= $row['keterangan'] ?></td>
                                    <td>
                                        <?= round($rataRata, 2) ?>
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

<?php if (!empty($faktors)) { ?>
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
                    <!-- Form untuk ubah data -->
                    <form action="<?= base_url('faktor/ubah'); ?>" method="post">
                        <!-- Hidden field untuk faktor1id -->
                        <input type="text" name="faktor1id" id="id-faktor" value="">
                        <div class="mb-3">
                            <label for="sub_category" class="form-label">Sub Kategori: </label>
                            <textarea class="form-control" name="sub_category" id="sub_category" style="height: 100px"
                                readonly></textarea>
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
                            <button type="button" class="btn btn-outline-info btn-sm" id="lihatPanduanFaktorUmum">Lihat
                                Panduan</button>
                            <div id="panduanNilaiContainerFaktorUmum" style="display: none; margin-top: 15px;">
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
                                            <td>Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai
                                                ketentuan, proses pelaksanaan tata kelola dilakukan dengan sangat memadai
                                                dan ditunjukkan dengan hasil pelaksanaan tata kelola yang sangat baik.
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Nilai 2</td>
                                            <td>Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai
                                                ketentuan, proses pelaksanaan tata kelola dilakukan dengan memadai dan
                                                ditunjukkan dengan hasil pelaksanaan tata kelola yang baik.
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Nilai 3</td>
                                            <td>Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai
                                                ketentuan, proses pelaksanaan tata kelola dilakukan cukup memadai dan
                                                ditunjukkan dengan hasil pelaksanaan tata kelola yang cukup baik.
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Nilai 4</td>
                                            <td>Memenuhi kondisi belum sepenuhnya terpenuhinya struktur dan/atau
                                                infrastruktur sesuai ketentuan, proses pelaksanaan tata kelola dilakukan
                                                kurang memadai dan ditunjukkan dengan hasil pelaksanaan tata kelola yang
                                                kurang baik.
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Nilai 5</td>
                                            <td>Memenuhi kondisi tidak terpenuhinya struktur dan/atau infrastruktur sesuai
                                                ketentuan, proses pelaksanaan tata kelola dilakukan dengan tidak memadai,
                                                dan ditunjukkan dengan hasil pelaksanaan tata kelola yang tidak baik.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="keterangan" class="form-label">Keterangan: </label>
                            <textarea class="form-control" name="keterangan" id="keterangan" style="height: 120px"
                                required></textarea>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" name="ubah" class="btn btn-primary">Ubah Data</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php } ?>


<script>
    document.addEventListener('DOMContentLoaded', function () {
        const lihatPanduanButtonFaktorUmum = document.getElementById('lihatPanduanFaktorUmum');
        const panduanContainerFaktorUmum = document.getElementById('panduanNilaiContainerFaktorUmum');

        if (lihatPanduanButtonFaktorUmum && panduanContainerFaktorUmum) {
            lihatPanduanButtonFaktorUmum.addEventListener('click', function () {
                panduanContainerFaktorUmum.style.display = panduanContainerFaktorUmum.style.display === 'none' ? 'block' : 'none';
                lihatPanduanButtonFaktorUmum.textContent = panduanContainerFaktorUmum.style.display === 'none' ? 'Lihat Panduan' : 'Sembunyikan Panduan';
            });
        }
    });
</script>

<div class="modal fade" id="modaltambahNilai">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form action="<?= base_url('faktor/tambahNilai'); ?>" method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Komentar Direksi dan Dewan Komisaris</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php date_default_timezone_set('Asia/Jakarta'); ?>

                    <input type="hidden" name="faktor_id" id="id-faktor" readonly>
                    <div class="mb-3">
                        <label for="sub_category" class="form-label">Sub Kategori: </label>
                        <textarea class="form-control" name="sub_category" id="sub_category" style="height: 100px"
                            readonly></textarea>
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

                    <!-- Button to show/hide the guide -->
                    <button type="button" class="btn btn-outline-info btn-sm" id="lihatPanduanNilai">Lihat
                        Panduan</button>

                    <!-- Guide section -->
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
                                    <td>Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai
                                        ketentuan, proses pelaksanaan tata kelola dilakukan dengan sangat memadai
                                        dan ditunjukkan dengan hasil pelaksanaan tata kelola yang sangat baik.
                                    </td>
                                </tr>
                                <tr>
                                    <td>Nilai 2</td>
                                    <td>Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai
                                        ketentuan, proses pelaksanaan tata kelola dilakukan dengan memadai dan
                                        ditunjukkan dengan hasil pelaksanaan tata kelola yang baik.
                                    </td>
                                </tr>
                                <tr>
                                    <td>Nilai 3</td>
                                    <td>Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai
                                        ketentuan, proses pelaksanaan tata kelola dilakukan cukup memadai dan
                                        ditunjukkan dengan hasil pelaksanaan tata kelola yang cukup baik.
                                    </td>
                                </tr>
                                <tr>
                                    <td>Nilai 4</td>
                                    <td>Memenuhi kondisi belum sepenuhnya terpenuhinya struktur dan/atau
                                        infrastruktur sesuai ketentuan, proses pelaksanaan tata kelola dilakukan
                                        kurang memadai dan ditunjukkan dengan hasil pelaksanaan tata kelola yang
                                        kurang baik.
                                    </td>
                                </tr>
                                <tr>
                                    <td>Nilai 5</td>
                                    <td>Memenuhi kondisi tidak terpenuhinya struktur dan/atau infrastruktur sesuai
                                        ketentuan, proses pelaksanaan tata kelola dilakukan dengan tidak memadai,
                                        dan ditunjukkan dengan hasil pelaksanaan tata kelola yang tidak baik.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <input type="hidden" name="fullname" value="<?= htmlspecialchars($fullname) ?>">
                    <input type="hidden" name="date" value="<?= date('Y-m-d H:i:s') ?>">

                    <div class="form-group">
                        <label for="keterangan">Tambah Keterangan:</label>
                        <textarea class="form-control" name="keterangan" id="keterangan" style="height: 100px"
                            required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="submit" name="tambahNilai" class="btn btn-primary">Simpan Komentar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const lihatPanduanButton = document.getElementById('lihatPanduanNilai');
        const panduanContainer = document.getElementById('panduanNilaiContainer');

        if (lihatPanduanButton && panduanContainer) {
            lihatPanduanButton.addEventListener('click', function () {
                panduanContainer.style.display = panduanContainer.style.display === 'none' ? 'block' : 'none';
                lihatPanduanButton.textContent = panduanContainer.style.display === 'none' ? 'Lihat Panduan' : 'Sembunyikan Panduan';
            });
        }
    });
</script>


<script>
    $(document).ready(function () {
        // When the "Tambah Nilai" button is clicked, populate the id-faktor input field
        $('#modaltambahNilai').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget); // Button that triggered the modal
            var faktorId = button.data('id'); // Extract the faktor_id from data-id attribute

            var modal = $(this);
            modal.find('#id-faktor').val(faktorId); // Set the value of #id-faktor input
        });

        // Optional: if you need to clear the value of #id-faktor when closing the modal
        $('#modaltambahNilai').on('hidden.bs.modal', function () {
            $(this).find('#id-faktor').val('');
        });
    });
</script>

<!-- Modal untuk Tambah Komentar -->
<div class="modal fade" id="modaltambahKomentar">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="<?= base_url('faktor/tambahKomentar'); ?>" method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Komentar Direksi dan Dewan Komisaris</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php date_default_timezone_set('Asia/Jakarta'); ?>

                    <input type="hidden" name="faktor_id" id="id-faktor">

                    <div class="form-group">
                        <label for="komentarLama">Komentar Saat Ini:</label>
                        <ul id="komentarLamaList" style="list-style-type: none; padding-left: 0;">
                            <li>Memuat komentar...</li>
                        </ul>
                    </div>

                    <?php if ($userInGroupAdmin || $userInGroupDekom || $userInGroupDireksi): ?>
                        <input type="hidden" name="fullname" value="<?= htmlspecialchars($fullname) ?>">
                        <input type="hidden" name="date" value="<?= date('Y-m-d H:i:s') ?>">
                        <div class="form-group">
                            <label for="komentar">Tambahkan Komentar Baru:</label>
                            <textarea class="form-control" name="komentar" id="komentar" style="height: 100px"
                                required></textarea>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="submit" name="tambahKomentar" class="btn btn-primary">Simpan Komentar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalHapusnilai">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                Apakah anda yakin ingin menghapus data?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btnHapusnilai">Yakin</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {

        $('#formTambahKomentar').on('submit', function (e) {
            e.preventDefault();
            var formData = $(this).serialize();

            $.ajax({
                url: '<?= base_url('faktor/save_komentar'); ?>',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        alert(response.message);
                        $('#komentarText').val('');
                        var currentFaktorId = $('#inputFaktorId').val();
                        $(`button[data-id="${currentFaktorId}"]`).click();

                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function (xhr, status, error) {
                    console.error("AJAX Error:", status, error);
                    alert('Terjadi kesalahan saat menyimpan komentar.');
                }
            });
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const btnSetNulls = document.querySelectorAll('#btn-set-null');

        btnSetNulls.forEach(function (btn) {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-id');

                if (confirm("Apakah Anda yakin hendak menghapus data nilai dan keterangan ini?")) {
                    window.location.href = "/faktor/setNullKolom/" + id;
                }
            });
        });
    });
</script>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    $(document).ready(function () {
        $('#modaltambahKomentar').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var faktorId = button.data('id');

            var modal = $(this);
            modal.find('#id-faktor').val(faktorId);
            modal.find('#komentarLamaList').html('<li>Memuat komentar...</li>');
            $.ajax({
                url: '<?= base_url('faktor/getKomentarByFaktorId'); ?>/' + faktorId,
                method: 'GET',
                dataType: 'json',
                success: function (response) {
                    var komentarListHtml = '';
                    if (response.length > 0) {
                        response.forEach(function (komentar) {
                            komentarListHtml += '<li>' + htmlspecialchars(komentar.komentar) + ' - (' + htmlspecialchars(komentar.fullname) + ' - ' + htmlspecialchars(komentar.created_at) + ')</li>';
                        });
                    } else {
                        komentarListHtml = '<li>Tidak ada komentar.</li>';
                    }
                    modal.find('#komentarLamaList').html(komentarListHtml);
                },
                error: function (xhr, status, error) {
                    console.error('Error loading comments:', status, error);
                    modal.find('#komentarLamaList').html('<li>Gagal memuat komentar.</li>');
                }
            });

            var urlParams = new URLSearchParams(window.location.search);

            urlParams.delete('modaltambahKomentar');
            urlParams.delete('modal_komentar');

            urlParams.set('modal_komentar', faktorId);

            var newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');

            history.pushState({ modalId: faktorId }, '', newUrl);
        });

        $('#modaltambahKomentar').on('hide.bs.modal', function (event) {
            var urlParams = new URLSearchParams(window.location.search);

            if (urlParams.has('modal_komentar')) {
                urlParams.delete('modal_komentar');
                var newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
                history.pushState({}, '', newUrl);
            }
            $(this).find('#komentarLamaList').html('');
        });

        window.addEventListener('popstate', function (event) {
            const urlParams = new URLSearchParams(window.location.search);
            const modalKomentarId = urlParams.get('modal_komentar');

            if (modalKomentarId) {
                $('#modaltambahKomentar').modal('show');
                $('#modaltambahKomentar').find('#id-faktor').val(modalKomentarId);
                $.ajax({
                    url: '<?= base_url('faktor/getKomentarByFaktorId'); ?>/' + modalKomentarId,
                    method: 'GET',
                    dataType: 'json',
                    success: function (response) {
                        var komentarListHtml = '';
                        if (response.length > 0) {
                            response.forEach(function (komentar) {
                                komentarListHtml += '<li>' + htmlspecialchars(komentar.komentar) + ' - (' + htmlspecialchars(komentar.fullname) + ' - ' + htmlspecialchars(komentar.created_at) + ')</li>';
                            });
                        } else {
                            komentarListHtml = '<li>Tidak ada komentar.</li>';
                        }
                        $('#modaltambahKomentar').find('#komentarLamaList').html(komentarListHtml);
                    },
                    error: function () {
                        console.log('Error loading comments via popstate.');
                        $('#modaltambahKomentar').find('#komentarLamaList').html('<li>Gagal memuat komentar.</li>');
                    }
                });
            } else {
                $('#modaltambahKomentar').modal('hide');
            }
        });
        const initialUrlParams = new URLSearchParams(window.location.search);
        const initialModalKomentarId = initialUrlParams.get('modal_komentar');
        if (initialModalKomentarId) {
            $('#modaltambahKomentar').modal('show');
            $('#modaltambahKomentar').find('#id-faktor').val(initialModalKomentarId);
            $.ajax({
                url: '<?= base_url('faktor/getKomentarByFaktorId'); ?>/' + initialModalKomentarId,
                method: 'GET',
                dataType: 'json',
                success: function (response) {
                    var komentarListHtml = '';
                    if (response.length > 0) {
                        response.forEach(function (komentar) {
                            komentarListHtml += '<li>' + htmlspecialchars(komentar.komentar) + ' - (' + htmlspecialchars(komentar.fullname) + ' - ' + htmlspecialchars(komentar.created_at) + ')</li>';
                        });
                    } else {
                        komentarListHtml = '<li>Tidak ada komentar.</li>';
                    }
                    $('#modaltambahKomentar').find('#komentarLamaList').html(komentarListHtml);
                },
                error: function () {
                    console.log('Error loading comments on page load.');
                    $('#modaltambahKomentar').find('#komentarLamaList').html('<li>Gagal memuat komentar.</li>');
                }
            });
        }

        function htmlspecialchars(str) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return str.replace(/[&<>"']/g, function (m) { return map[m]; });
        }
    });
</script>