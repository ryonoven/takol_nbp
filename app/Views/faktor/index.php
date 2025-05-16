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
                    <h4>Pelaksanaan Aspek Pemegang Saham</h4>
                </span>
                <span>
                    <?php
                    $allApproved = true;
                    foreach ($faktor as $item) {
                        if ($item['is_approved'] != 1) {
                            $allApproved = false;
                            break;
                        }
                    }
                    ?>
                    <?php if ($allApproved): ?>
                        <span class="badge badge-success" style="font-size: 14px;">
                            Disetujui oleh <strong><?= esc($fullname ?? '-') ?></strong><br>
                            <?= esc($faktor[0]['approved_at'] ?? '-') ?>
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
                        <?php if (empty($faktor)) { ?>
                            <tr>
                                <td scope="row"></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($faktor as $row): ?>
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
                                                    data-komentar="<?= $row['komentar']; ?>" data-date="<?= $row['date']; ?>"><i
                                                        class="fa fa-edit"></i>&nbsp;</button>
                                                <button type="button" class="btn btn-sm" style="font-weight: 600;"
                                                    data-id="<?= $row['id']; ?>" id="btn-set-null"><i
                                                        class="fas fa-trash-alt"></i>&nbsp;
                                                </button>
                                            <?php endif; ?>
                                            <button type="button" data-toggle="modal" data-target="#modaltambahKomentar"
                                                id="btn-komentar" class="btn btn-sm" style="font-weight: 600;"
                                                data-id="<?= $row['id']; ?>"><i class="fas fa-comment"></i>&nbsp;</button>
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
                        <?php if (empty($faktor)) { ?>
                            <tr>
                                <td scope="row"></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($faktor as $row): ?>
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
                                                    data-keterangan="<?= $row['keterangan']; ?>"><i class="fa fa-edit"></i>&nbsp;
                                                </button>
                                                <button type="button" class="btn btn-sm" style="font-weight: 600;"
                                                    data-id="<?= $row['id']; ?>" id="btn-set-null"><i
                                                        class="fas fa-trash-alt"></i>&nbsp;
                                                </button>
                                            <?php endif; ?>
                                            <button type="button" data-toggle="modal" data-target="#modaltambahKomentar"
                                                id="btn-komentar" class="btn btn-sm" style="font-weight: 600;"
                                                data-id="<?= $row['id']; ?>" data-id="<?= $row['komentar']; ?>"><i
                                                    class="fas fa-comment"> </i>&nbsp;
                                            </button>
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
                        <?php if (empty($faktor)) { ?>
                            <tr>
                                <td scope="row"></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($faktor as $row): ?>
                                <?php if ($row['sph'] == 'Hasil'): ?>
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
                                                    data-komentar="<?= $row['komentar']; ?>" data-date="<?= $row['date']; ?>"><i
                                                        class="fa fa-edit"></i>&nbsp;
                                                </button>
                                                <button type="button" class="btn btn-sm" style="font-weight: 600;"
                                                    data-id="<?= $row['id']; ?>" id="btn-set-null"><i
                                                        class="fas fa-trash-alt"></i>&nbsp;
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($userInGroupAdmin || $userInGroupDekom || $userInGroupDireksi): ?>
                                                <button type="button" data-toggle="modal" data-target="#modaltambahKomentar"
                                                    id="btn-komentar" class="btn btn-sm" style="font-weight: 600;"
                                                    data-id="<?= $row['id']; ?>" data-id="<?= $row['komentar']; ?>"> <i
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

<?php if (!empty($faktor)) { ?>
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
                    <form action="<?= base_url('faktor/ubah'); ?>" method="post">
                        <input type="hidden" name="id" id="id-faktor">
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
                                            <td>Apabila memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai
                                                ketentuan, proses penerapan tata kelola dilakukan dengan sangat memadai, dan
                                                ditunjukkan dengan hasil penerapan tata kelola yang sangat baik.<br>
                                                Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara
                                                lain:<br>
                                                a. Struktur pemegang saham memenuhi seluruh ketentuan dan pelaksanaan tata
                                                kelola sangat memadai sehingga tidak terdapat benturan kepentingan,
                                                intervensi, mengambil keuntungan pribadi atau kepentingan golongan tertentu,
                                                dan/atau keputusan pengangkatan, penggantian, atau pemberhentian anggota
                                                Direksi dan/atau Dewan Komisaris sesuai dengan ketentuan peraturan
                                                perundang-undangan.<br>
                                                b. Seluruh pengambilan kebijakan aksi korporasi melalui RUPS sejalan dengan
                                                anggaran dasar, ketentuan peraturan perundang-undangan, dan rencana
                                                strategis sehingga perencanaan pengembangan BPR terealisasikan sepenuhnya
                                                yang tercermin pada pemenuhan ketentuan permodalan, kinerja keuangan,
                                                dan/atau perkembangan kegiatan usaha BPR.<br>
                                                c. Kebijakan penggunaan laba dan pembagian dividen telah dievaluasi secara
                                                berkala sehingga seluruh pelaksanaan penggunaan laba dan pembagian dividen
                                                telah sesuai dengan kebijakan yang ditetapkan.
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Nilai 2</td>
                                            <td>Apabila memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai
                                                ketentuan, proses penerapan tata kelola dilakukan dengan memadai, dan
                                                ditunjukkan dengan hasil penerapan tata kelola yang baik.<br>
                                                Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara
                                                lain:<br>
                                                a. Struktur pemegang saham memenuhi seluruh ketentuan dan pelaksanaan tata
                                                kelola memadai sehingga benturan kepentingan dapat diselesaikan, intervensi
                                                yang timbul tidak signifikan, tidak mengambil keuntungan pribadi atau
                                                kepentingan golongan tertentu, dan/atau keputusan pengangkatan, penggantian,
                                                atau pemberhentian anggota Direksi dan/atau Dewan Komisaris sesuai dengan
                                                ketentuan peraturan perundang-undangan.<br>
                                                b. Sebagian besar pengambilan kebijakan aksi korporasi melalui RUPS sejalan
                                                dengan anggaran dasar, ketentuan peraturan perundang-undangan, dan rencana
                                                strategis sehingga perencanaan pengembangan BPR sebagian besar
                                                terealisasikan yang tercermin pada pemenuhan ketentuan permodalan, kinerja
                                                keuangan, dan/atau perkembangan kegiatan usaha BPR.<br>
                                                c. Kebijakan penggunaan laba dan pembagian dividen telah dievaluasi sehingga
                                                sebagian besar pelaksanaan penggunaan laba dan pembagian dividen telah
                                                sesuai dengan kebijakan yang ditetapkan.
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Nilai 3</td>
                                            <td>Apabila memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai
                                                ketentuan, proses penerapan tata kelola dilakukan dengan cukup memadai, dan
                                                ditunjukkan dengan hasil penerapan tata kelola yang cukup baik.<br>
                                                Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara
                                                lain:<br>
                                                a. Struktur pemegang saham memenuhi seluruh ketentuan dan pelaksanaan tata
                                                kelola cukup memadai sehingga benturan kepentingan dapat diselesaikan,
                                                intervensi yang timbul tidak signifikan, tidak mengambil keuntungan pribadi
                                                atau kepentingan golongan tertentu, dan/atau keputusan pengangkatan,
                                                penggantian, atau pemberhentian anggota Direksi dan/atau Dewan Komisaris
                                                sesuai dengan ketentuan peraturan perundang-undangan.<br>
                                                b. Sebagian pengambilan kebijakan aksi korporasi melalui RUPS sejalan dengan
                                                anggaran dasar, ketentuan peraturan perundang-undangan, dan rencana
                                                strategis sehingga perencanaan pengembangan BPR belum sepenuhnya
                                                terealisasikan yang tercermin pada pemenuhan ketentuan permodalan, kinerja
                                                keuangan, dan/atau perkembangan kegiatan usaha BPR.<br>
                                                c. Kebijakan penggunaan laba dan pembagian dividen telah dievaluasi sehingga
                                                sebagian pelaksanaan penggunaan laba dan pembagian dividen telah sesuai
                                                dengan kebijakan yang ditetapkan.
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
                                                a. Struktur pemegang saham memenuhi sebagian ketentuan dan pelaksanaan tata
                                                kelola kurang memadai sehingga benturan kepentingan kurang dapat
                                                diselesaikan, intervensi yang timbul cukup signifikan, mengambil keuntungan
                                                pribadi atau kepentingan golongan tertentu, dan/atau keputusan pengangkatan,
                                                penggantian, atau pemberhentian anggota Direksi dan/atau Dewan Komisaris
                                                kurang sesuai dengan ketentuan peraturan perundang-undangan.<br>
                                                b. Sebagian kecil pengambilan kebijakan aksi korporasi melalui RUPS sejalan
                                                dengan anggaran dasar, ketentuan peraturan perundang-undangan, dan rencana
                                                strategis sehingga perencanaan pengembangan BPR sebagian kecil
                                                terealisasikan yang tercermin pada pemenuhan ketentuan permodalan, kinerja
                                                keuangan, dan/atau perkembangan kegiatan usaha BPR.<br>
                                                c. Sebagian kebijakan penggunaan laba dan pembagian dividen telah dievaluasi
                                                sehingga sebagian kecil pelaksanaan penggunaan laba dan pembagian dividen
                                                telah sesuai dengan kebijakan yang ditetapkan.
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
                                                a. Struktur pemegang saham tidak memenuhi ketentuan dan pelaksanaan tata
                                                kelola tidak memadai sehingga benturan kepentingan tidak dapat diselesaikan,
                                                intervensi yang timbul signifikan, mengambil keuntungan pribadi atau
                                                kepentingan golongan tertentu, dan/atau keputusan pengangkatan, penggantian,
                                                atau pemberhentian anggota Direksi dan/atau Dewan Komisaris tidak sesuai
                                                dengan ketentuan peraturan perundang-undangan.<br>
                                                b. Pengambilan kebijakan aksi korporasi tidak melalui RUPS dan tidak sejalan
                                                dengan anggaran dasar, ketentuan peraturan perundang-undangan, dan rencana
                                                strategis sehingga perencanaan pengembangan BPR tidak terealisasikan yang
                                                tercermin pada pemenuhan ketentuan permodalan, kinerja keuangan, dan/atau
                                                perkembangan kegiatan usaha BPR.<br>
                                                c. Kebijakan penggunaan laba dan pembagian dividen tidak dievaluasi sehingga
                                                pelaksanaan penggunaan laba dan pembagian dividen tidak sesuai dengan
                                                kebijakan yang ditetapkan.
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


<!-- Add your modaltambahKomentar code here -->
<!-- Modal untuk Tambah Komentar -->
<div class="modal fade" id="modaltambahKomentar">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="<?= base_url('faktor/tambahKomentar'); ?>" method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Beri Komentar</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <?php date_default_timezone_set('Asia/Jakarta'); ?>

                    <!-- Input faktor_id -->
                    <input type="hidden" name="faktor_id" id="id-faktor">

                    <div class="form-group">
                        <label for="komentarLama">Komentar Saat Ini:</label>
                        <textarea class="input-group mb-3" id="komentarLama" style="height: 150px" readonly>
                            <?php
                            // Loop untuk menampilkan seluruh komentar
                            if (!empty($komentarList)) {
                                foreach ($komentarList as $komentar) {
                                    echo $komentar['komentar'] . " - " . " (". $fullname . " - " . $komentar['created_at'] . ") " ."\n";
                                }
                            } else {
                                echo "Tidak ada komentar.";
                            }
                            ?>
                        </textarea>
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
<script>
    // Script untuk menangani pengaturan id-faktor di modal tambah komentar
    document.addEventListener('DOMContentLoaded', function () {
        // Menangani event saat modal tambah komentar dibuka
        $('#modaltambahKomentar').on('show.bs.modal', function (e) {
            var faktorId = $(e.relatedTarget).data('id'); // Ambil faktor_id dari tombol yang membuka modal
            // Set faktor_id ke modal tambah komentar
            $('#id-faktor').val(faktorId);
        });

        // Menangani event saat modal ubah dibuka
        $('#modalUbah').on('show.bs.modal', function (e) {
            var faktorId = $(e.relatedTarget).data('id'); // Ambil faktor_id dari tombol yang membuka modal
            // Set faktor_id ke modal ubah
            $('#id-faktor').val(faktorId);
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