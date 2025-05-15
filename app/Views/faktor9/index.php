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

    <!-- Faktor 9 -->
    <div class="card card-body">
        <div class="table-vertical">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <span class="label" style="flex: 3; margin-right: 10px; text-align: center;">
                    <h3>Faktor 9</h3>
                    <h4>Penerapan Manajemen Risiko dan Strategi Anti Fraud</h4>
                </span>
                <span>
                    <?php
                    $allApproved = true;
                    foreach ($faktor9 as $item) {
                        if ($item['is_approved'] != 1) {
                            $allApproved = false;
                            break;
                        }
                    }
                    ?>
                    <?php if ($allApproved): ?>
                        <span class="badge badge-success" style="font-size: 14px;">
                            Disetujui oleh <strong><?= esc($fullname ?? '-') ?></strong><br>
                            <?= esc($faktor9[0]['approved_at'] ?? '-') ?>
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
                    <a href="<?= base_url('faktor9/approveSemua') ?>" class="btn btn-success shadow mt-3 mr-2"
                        onclick="return confirm('Apakah Anda yakin ingin melakukan approval?');">
                        Approve
                    </a>
                    <a href="<?= base_url('faktor9/unapproveSemua') ?>" class="btn btn-danger shadow mt-3 mr-2"
                        onclick="return confirm('Batalkan semua approval?');">
                        Batalkan Approval
                    </a>
                </div>
            <?php endif; ?>
            <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                <div class="col-md">
                    <a href="/faktor9/excel" class="btn btn-outline-success shadow float-right mt-3">Excel <i
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
                        <?php if (empty($faktor9)) { ?>
                            <tr>
                                <td scope="row"></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($faktor9 as $row): ?>
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
                                                    data-created_at="<?= $row['created_at']; ?>"><i class="fa fa-edit"></i>&nbsp;
                                                </button>
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
                        <?php if (empty($faktor9)) { ?>
                            <tr>
                                <td scope="row"></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($faktor9 as $row): ?>
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
                        <?php if (empty($faktor9)) { ?>
                            <tr>
                                <td scope="row"></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($faktor9 as $row): ?>
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
        </div>
    </div>
</div>
</div>

<?php if (!empty($faktor9)) { ?>
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
                    <form action="<?= base_url('faktor9/ubah'); ?>" method="post">
                        <input type="hidden" name="id" id="id-faktor9">
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
                            <button type="button" class="btn btn-outline-info btn-sm" id="lihatPanduanFaktor9">Lihat
                                Panduan</button>
                            <div id="panduanNilaiContainerFaktor9" style="display: none; margin-top: 15px;">
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
                                                a. BPR memenuhi seluruh persyaratan terkait dengan komite, satuan kerja,
                                                dan/atau Pejabat Eksekutif yang bertanggung jawab terhadap penerapan fungsi
                                                manajemen risiko, termasuk fungsi anti fraud program anti pencucian uang dan
                                                pencegahan pendanaan terorisme sebagaimana diatur dalam Peraturan Otoritas
                                                Jasa Keuangan dan ketentuan peraturan perundang-undangan serta penerapan
                                                fungsi manajemen risiko dilakukan dengan baik sehingga:<br>
                                                1. peringkat risiko sangat rendah;<br>
                                                2. tidak terdapat fraud; dan/atau<br>
                                                3. peringkat program anti pencucian uang dan pencegahan pendanaan terorisme
                                                sangat rendah.<br>
                                                b. BPR telah memiliki dan menginikan secara berkala pedoman manajemen
                                                risiko, prosedur manajemen risiko, penetapan limit risiko, serta kebijakan
                                                prosedur secara tertulis mengenai pengelolaan risiko yang melekat pada
                                                produk dan aktivitas baru dengan ruang lingkup sangat memadai, dan penerapan
                                                manajemen risiko memperhatikan pedoman dan kebijakan tersebut.<br>
                                                c. Seluruh pelaksanaan tugas dan fungsi Direksi dan Dewan Komisaris terhadap
                                                penerapan manajemen risiko dilakukan sesuai dengan ketentuan peraturan
                                                perundang-undangan maupun pedoman, termasuk mengembangkan budaya manajemen
                                                risiko pada seluruh jenjang organisasi dan peningkatan kompetensi sumber
                                                daya manusia.
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Nilai 2</td>
                                            <td>Apabila memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai
                                                ketentuan, proses penerapan tata kelola dilakukan dengan memadai, dan
                                                ditunjukkan dengan hasil penerapan tata kelola yang baik.<br>
                                                Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara
                                                lain:<br>
                                                a. BPR memenuhi seluruh persyaratan terkait dengan komite, satuan kerja,
                                                dan/atau Pejabat Eksekutif yang bertanggung jawab terhadap penerapan fungsi
                                                manajemen risiko, termasuk fungsi anti fraud program anti pencucian uang dan
                                                pencegahan pendanaan terorisme sebagaimana diatur dalam Peraturan Otoritas
                                                Jasa Keuangan dan ketentuan peraturan perundang-undangan serta penerapan
                                                fungsi manajemen risiko dilakukan dengan baik sehingga:<br>
                                                1. peringkat risiko rendah;<br>
                                                2. tidak terdapat fraud; dan/atau<br>
                                                3. peringkat program anti pencucian uang dan pencegahan pendanaan terorisme
                                                rendah.<br>
                                                b. BPR telah memiliki dan menginikan pedoman manajemen risiko, prosedur
                                                manajemen risiko, penetapan limit risiko, serta kebijakan prosedur secara
                                                tertulis mengenai pengelolaan risiko yang melekat pada produk dan aktivitas
                                                baru dengan ruang lingkup memadai, dan penerapan manajemen risiko
                                                memperhatikan pedoman dan kebijakan tersebut<br>
                                                c. Sebagian besar pelaksanaan tugas dan fungsi Direksi dan Dewan Komisaris
                                                terhadap penerapan manajemen risiko dilakukan sesuai dengan ketentuan
                                                peraturan perundang-undangan maupun pedoman, termasuk mengembangkan budaya
                                                manajemen risiko pada sebagian besar jenjang organisasi dan peningkatan
                                                kompetensi sumber daya manusia.
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Nilai 3</td>
                                            <td>Apabila memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai
                                                ketentuan, proses penerapan tata kelola dilakukan dengan cukup memadai, dan
                                                ditunjukkan dengan hasil penerapan tata kelola yang cukup baik.<br>
                                                Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara
                                                lain:<br>
                                                a. BPR memenuhi seluruh persyaratan terkait dengan komite, satuan kerja,
                                                dan/atau Pejabat Eksekutif yang bertanggung jawab terhadap penerapan fungsi
                                                manajemen risiko, termasuk fungsi anti fraud program anti pencucian uang dan
                                                pencegahan pendanaan terorisme sebagaimana diatur dalam Peraturan Otoritas
                                                Jasa Keuangan dan ketentuan peraturan perundang-undangan serta penerapan
                                                fungsi manajemen risiko dilakukan dengan cukup baik sehingga:<br>
                                                1. peringkat risiko sedang;<br>
                                                2. tidak terdapat fraud; dan/atau<br>
                                                3. peringkat program anti pencucian uang dan pencegahan pendanaan terorisme
                                                rendah.<br>
                                                b. BPR telah memiliki pedoman manajemen risiko, prosedur manajemen risiko,
                                                penetapan limit risiko, serta kebijakan prosedur secara tertulis mengenai
                                                pengelolaan risiko yang melekat pada produk dan aktivitas baru dengan ruang
                                                lingkup cukup memadai, dan penerapan manajemen risiko memperhatikan pedoman
                                                dan kebijakan tersebut.<br>
                                                c. Sebagian pelaksanaan tugas dan fungsi Direksi dan Dewan Komisaris
                                                terhadap penerapan manajemen risiko dilakukan sesuai dengan ketentuan
                                                peraturan perundang-undangan maupun pedoman, termasuk mengembangkan budaya
                                                manajemen risiko pada sebagian jenjang organisasi dan peningkatan kompetensi
                                                sumber daya manusia.
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
                                                a. BPR memenuhi sebagian persyaratan terkait dengan komite, satuan kerja,
                                                dan/atau Pejabat Eksekutif yang bertanggung jawab terhadap penerapan fungsi
                                                manajemen risiko, termasuk fungsi anti fraud program anti pencucian uang dan
                                                pencegahan pendanaan terorisme sebagaimana diatur dalam Peraturan Otoritas
                                                Jasa Keuangan dan ketentuan peraturan perundang-undangan serta penerapan
                                                fungsi manajemen risiko dilakukan dengan kurang baik sehingga:<br>
                                                1. peringkat risiko tinggi;<br>
                                                2. terdapat fraud; dan/atau<br>
                                                3. peringkat program anti pencucian uang dan pencegahan pendanaan terorisme
                                                tinggi.<br>
                                                b. BPR telah memiliki pedoman manajemen risiko, prosedur manajemen risiko,
                                                penetapan limit risiko, serta kebijakan prosedur secara tertulis mengenai
                                                pengelolaan risiko yang melekat pada produk dan aktivitas baru dengan ruang
                                                lingkup kurangmemadai, dan penerapan manajemen risiko kurang memperhatikan
                                                pedoman dan kebijakan tersebut.<br>
                                                c. Sebagian kecil pelaksanaan tugas dan fungsi Direksi dan Dewan Komisaris
                                                terhadap penerapan manajemen risiko dilakukan sesuai dengan ketentuan
                                                peraturan perundang-undangan maupun pedoman, termasuk mengembangkan budaya
                                                manajemen risiko pada sebagian kecil jenjang organisasi dan peningkatan
                                                kompetensi sumber daya manusia.
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
                                                a. BPR tidak memenuhi persyaratan terkait dengan komite, satuan kerja,
                                                dan/atau Pejabat Eksekutif yang bertanggung jawab terhadap penerapan fungsi
                                                manajemen risiko, termasuk fungsi anti fraud program anti pencucian uang dan
                                                pencegahan pendanaan terorisme sebagaimana diatur dalam Peraturan Otoritas
                                                Jasa Keuangan dan ketentuan peraturan perundang-undangan serta penerapan
                                                fungsi manajemen risiko dilakukan dengan tidak baik sehingga:<br>
                                                1. peringkat risiko sangat tinggi;<br>
                                                2. terdapat fraud; dan/atau<br>
                                                3. peringkat program anti pencucian uang dan pencegahan pendanaan terorisme
                                                sangat tinggi.<br>
                                                b. BPR tidak memiliki pedoman manajemen risiko, prosedur manajemen risiko,
                                                penetapan limit risiko, serta kebijakan prosedur secara tertulis mengenai
                                                pengelolaan risiko yang melekat pada produk dan aktivitas baru sehingga
                                                penerapan manajemen risiko tidak memperhatikan pedoman dan kebijakan.<br>
                                                c. Seluruh pelaksanaan tugas dan fungsi Direksi dan Dewan Komisaris terhadap
                                                penerapan manajemen risiko tidak dilakukan sesuai dengan ketentuan peraturan
                                                perundang-undangan maupun pedoman, termasuk tidak mengembangkan budaya
                                                manajemen risiko pada seluruh jenjang organisasi dan peningkatan kompetensi
                                                sumber daya manusia.<br>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="keterangan">Keterangan: </label>
                            <input type="text" name="keterangan" id="keterangan" class="form-control"
                                value="<?= $row['keterangan'] ?? '' ?>">
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
        const lihatPanduanButtonFaktor9 = document.getElementById('lihatPanduanFaktor9');
        const panduanContainerFaktor9 = document.getElementById('panduanNilaiContainerFaktor9');

        if (lihatPanduanButtonFaktor9 && panduanContainerFaktor9) {
            lihatPanduanButtonFaktor9.addEventListener('click', function () {
                panduanContainerFaktor9.style.display = panduanContainerFaktor9.style.display === 'none' ? 'block' : 'none';
                lihatPanduanButtonFaktor9.textContent = panduanContainerFaktor9.style.display === 'none' ? 'Lihat Panduan' : 'Sembunyikan Panduan';
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
                <form action="<?= base_url('faktor9/tambahKomentar'); ?>" method="post">
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
                    window.location.href = "/faktor9/setNullKolom/" + id;
                }
            });
        });
    });
</script>