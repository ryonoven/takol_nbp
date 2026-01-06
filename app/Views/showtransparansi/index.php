<div class="alert beautiful-alert my-4">
    <i class="fas fa-info-circle alert-icon"></i> <?php if (isset($bprData) && isset($periodeDetail)): ?>
        <strong><?= esc($bprData['namabpr'] ?? 'Nama BPR') ?></strong> - Periode Pelaporan Tahun
        <?= esc($periodeDetail['tahun']) ?>
    <?php elseif (isset($periodeDetail)): ?>
        <strong>Periode:</strong> Tahun <?= esc($periodeDetail['tahun']) ?>
    <?php else: ?>
        <strong>Periode belum ditentukan</strong>
    <?php endif; ?>
</div>

<?php if (session()->getFlashdata('message')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">&times;</button>
        <strong><?= esc(session()->getFlashdata('message')); ?></strong>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('err')): ?>
    <div class="alert alert-danger" role="alert"><?= esc(session()->getFlashdata('err')); ?></div>
<?php endif; ?>

<div class="container-fluid">
    <div class="card">
        <h1 class="h3 mt-5 mb-4 text-gray-800 text-center"><?= $judul; ?></h1>
        <div class="row">
            <?php foreach ($transparan as $trans): ?>
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card trans-card shadow h-100 py-3" style="width: 450px;">
                        <?= (isset($trans['accdekom']) && $trans['accdekom'] == 1 && isset($trans['is_approved']) && $trans['is_approved'] == 1) ? '' : '' ?>
                        <a href="<?= esc($trans['link']); ?>" style="text-decoration: none; color: inherit;">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= esc($trans['name']); ?>
                                        </div>
                                        <hr class="sidebar-divider my-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            <?= esc($bprData['namabpr'] ?? 'Nama BPR'); ?>
                                        </div>
                                        <div class="text-xs text-muted mb-3">
                                            Periode Tahun:
                                            <?php
                                            ?>     <?= esc($periodeDetail['tahun']) ?>
                                        </div>

                                        <!-- Status Approval -->
                                        <div class="text-gray-700">
                                            <?php
                                            // Check if accdekom is set and determine status
                                            if (isset($trans['accdekom'])) {
                                                if ($trans['accdekom'] == 1) {
                                                    $message = 'Telah disetujui oleh Komisaris Utama';
                                                    $statusClass = 'text-success';
                                                } else {
                                                    $message = 'Belum disetujui oleh Komisaris Utama';
                                                    $statusClass = 'text-danger';
                                                }
                                            } else {
                                                $message = 'Data tidak diisi';
                                                $statusClass = 'text-secondary';
                                            }
                                            ?>
                                            <p class="<?= $statusClass; ?>"><?= $message; ?></p>
                                        </div>

                                        <div class="text-gray-700">
                                            <?php
                                            // Check if is_approved is set and determine status
                                            if (isset($trans['is_approved'])) {
                                                if ($trans['is_approved'] == 1) {
                                                    $message2 = 'Telah disetujui oleh Direktur Utama';
                                                    $statusClass2 = 'text-success';
                                                } else {
                                                    $message2 = 'Belum disetujui oleh Direktur Utama';
                                                    $statusClass2 = 'text-danger';
                                                }
                                            } else {
                                                $message2 = 'Data tidak diisi';
                                                $statusClass2 = 'text-secondary';
                                            }
                                            ?>
                                            <p class="<?= $statusClass2; ?>"><?= $message2; ?></p>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Form for inputting data into the fields -->
            <!-- <div class="col-12 mb-4">
                <div class="card trans-card shadow h-100 py-3">
                    <div class="card-body">
                        <h5 class="font-weight-bold text-gray-800">Kesimpulan</h5>
                        <form method="post" action="<?= esc(base_url('ShowFaktor/update')); ?>">
                            <input type="hidden" name="id" value="<?= esc($showfaktor['id'] ?? ''); ?>">
                            <div class="form-group">
                                <label for="kesimpulan">Kesimpulan</label>
                                <textarea class="form-control" id="kesimpulan" name="kesimpulan" rows="7"
                                    required><?= esc($showfaktor['kesimpulan'] ?? ''); ?></textarea>
                                <label for="positifstruktur">Positif Struktur</label>
                                <textarea class="form-control" id="positifstruktur" name="positifstruktur" rows="7"
                                    required><?= esc($showfaktor['positifstruktur'] ?? ''); ?></textarea>
                                <label for="positifproses">Positif Proses</label>
                                <textarea class="form-control" id="positifproses" name="positifproses" rows="7"
                                    required><?= esc($showfaktor['positifproses'] ?? ''); ?></textarea>
                                <label for="positifhasil">Positif Hasil</label>
                                <textarea class="form-control" id="positifhasil" name="positifhasil" rows="7"
                                    required><?= esc($showfaktor['positifhasil'] ?? ''); ?></textarea>
                                <label for="negatifstruktur">Negatif Struktur</label>
                                <textarea class="form-control" id="negatifstruktur" name="negatifstruktur" rows="7"
                                    required><?= esc($showfaktor['negatifstruktur'] ?? ''); ?></textarea>
                                <label for="negatifproses">Negatif Proses</label>
                                <textarea class="form-control" id="negatifproses" name="negatifproses" rows="7"
                                    required><?= esc($showfaktor['negatifproses'] ?? ''); ?></textarea>
                                <label for="negatifhasil">Negatif Hasil</label>
                                <textarea class="form-control" id="negatifhasil" name="negatifhasil" rows="7"
                                    required><?= esc($showfaktor['negatifhasil'] ?? ''); ?></textarea>

                            </div> -->
            <!-- dst untuk field lain... -->
            <!-- <div class="d-flex justify-content-center">
                                <button type="submit" class="btn btn-success">Simpan Data</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 mb-4">
                <div class="card trans-card shadow h-100 py-3">
                    <div class="card-body">
                        <h5 class="font-weight-bold text-gray-800">Lembar Persetujuan</h5>
                        <form method="post" action="<?= esc(base_url('ShowFaktor/updatettd')); ?>">
                            <input type="hidden" name="id" value="<?= esc($showfaktor['id'] ?? ''); ?>">
                            <div class="form-group">
                                <label for="dirut">Nama Direktur Utama</label>
                                <input type="text" class="form-control" id="dirut" name="dirut"
                                    value="<?= esc($showfaktor['dirut'] ?? ''); ?>" required>

                                <label for="komut">Nama Komisaris Utama</label>
                                <input type="text" class="form-control" id="komut" name="komut"
                                    value="<?= esc($showfaktor['komut'] ?? ''); ?>" required>

                                <label for="tanggal">Tanggal Pelaporan</label>
                                <input type="text" class="form-control" id="tanggal" name="tanggal"
                                    placeholder="dd/mm/yyyy" value="<?= esc($showfaktor['tanggal'] ?? ''); ?>" required>

                                <label for="lokasi">Lokasi</label>
                                <input type="text" class="form-control" id="lokasi" name="lokasi"
                                    value="<?= esc($showfaktor['lokasi'] ?? ''); ?>" required>
                            </div> -->
            <!-- dst untuk field lain... -->
            <!-- <div class="d-flex justify-content-center">
                                <button type="submit" class="btn btn-success">Simpan Data</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div> -->

            <!-- <div class="col-12 mb-4">
                <div class="card shadow-lg border-0 rounded-lg">
                    <div class="card-body p-4 p-md-5">
                        <h4 class="font-weight-bold text-gray-900 mb-4 text-center">Upload Dokumen Pendukung</h4>
                        <p class="text-muted text-center mb-4">Silakan unggah file PDF pendukung laporan.</p>
                        <form method="post" action="<?= base_url('pdfself/uploadPdf'); ?>"
                            enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="pdf1">Upload Laporan Pokok Pelaksanaan tugas anggota Direksi yang
                                    membawahkan
                                    fungsi kepatuhan. (.PDF)</label>
                                <input type="file" class="form-control" id="pdf1" name="pdf1" accept="application/pdf">
                                <p>Maksimal ukuran file .PDF 2 Mb</p>
                            </div>
                            <?php if (!empty($showfaktor['pdf1_filename'])): ?>
                                <div class="mt-2">
                                    <p>PDF 1 Terunggah:
                                        <a href="<?= base_url('pdfself/download/' . esc($showfaktor['pdf1_filename'])); ?>"
                                            target="_blank">
                                            <?= esc($showfaktor['pdf1_filename']); ?>
                                        </a>
                                    </p>
                                </div>
                            <?php endif; ?>

                            <div class="form-group">
                                <label for="pdf2">Upload Laporan pelaksanaan dan pokok hasil audit intern (.PDF)</label>
                                <input type="file" class="form-control" id="pdf2" name="pdf2" accept="application/pdf">
                                <p>Maksimal ukuran file .PDF 2 Mb</p>
                            </div>
                            <?php if (!empty($showfaktor['pdf2_filename'])): ?>
                                <div class="mt-2">
                                    <p>PDF 2 Terunggah:
                                        <a href="<?= base_url('pdfself/download/' . esc($showfaktor['pdf2_filename'])); ?>"
                                            target="_blank">
                                            <?= esc($showfaktor['pdf2_filename']); ?>
                                        </a>
                                    </p>
                                </div>
                            <?php endif; ?>

                            <button type="submit" class="btn btn-success">Upload PDF</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-12 mb-4">
                <div class="card shadow-lg border-0 rounded-lg">
                    <div class="card-body p-4 p-md-5"> -->
            <!-- BATASSS -->
            <!-- <h4 class="font-weight-bold text-gray-900 mb-4 text-center">Cover Laporan</h4>
                        <p class="text-muted text-center mb-2">Silakan pilih cover untuk PDF pelaporan.</p>
                        <form method="post" action="<?= esc(base_url('ShowFaktor/updatecover')); ?>">
                            <input type="hidden" name="id" value="<?= esc($showfaktor['id'] ?? ''); ?>">
                            <input type="hidden" name="cover" id="cover"
                                value="<?= esc($showfaktor['cover'] ?? ''); ?>">
                            <div class="container text-center" id="ratingList" name="cover">
                                <div class="row row-cols-2 row-cols-lg-5 g-2 g-lg-3">
                                    <div class="col">
                                        <a href="#" value="Cover.png"
                                            class="list-group-item list-group-item-action py-1 px-2">
                                            <img src="/assets/img/Cover.png"
                                                class="list-group-item list-group-item-action py-1 px-2" alt="Cover"
                                                style="max-width: 100%; max-height: 250px;">
                                        </a>
                                    </div>
                                    <div class="col">
                                        <a href="#" value="Cover1.png"
                                            class="list-group-item list-group-item-action py-1 px-2">
                                            <img src="/assets/img/Cover1.png"
                                                class="list-group-item list-group-item-action py-1 px-2" alt="Cover 1"
                                                style="max-width: 100%; max-height: 250px;">
                                        </a>
                                    </div>
                                    <div class="col">
                                        <a href="#" value="Cover2.png"
                                            class="list-group-item list-group-item-action py-1 px-2">
                                            <img src="/assets/img/Cover2.png"
                                                class="list-group-item list-group-item-action py-1 px-2" alt="Cover 2"
                                                style="max-width: 100%; max-height: 250px;">
                                        </a>
                                    </div>
                                    <div class="col">
                                        <a href="#" value="Cover3.png"
                                            class="list-group-item list-group-item-action py-1 px-2">
                                            <img src="/assets/img/Cover3.png"
                                                class="list-group-item list-group-item-action py-1 px-2" alt="Cover 3"
                                                style="max-width: 100%; max-height: 250px;">
                                        </a>
                                    </div>
                                    <div class="col">
                                        <a href="#" value="Cover4.png"
                                            class="list-group-item list-group-item-action py-1 px-2">
                                            <img src="/assets/img/Cover4.png"
                                                class="list-group-item list-group-item-action py-1 px-2" alt="Cover 4"
                                                style="max-width: 100%; max-height: 250px;">
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-center mt-5">
                                <button type="submit" class="btn btn-success">Pilih Cover</button>
                            </div>
                        </form>
                    </div>
                    <p class="text-muted text-center mb-4"></p>
                </div>
            </div>
        </div> -->

            <!-- <script>
            document.addEventListener('DOMContentLoaded', function () {
                const ratingList = document.getElementById('ratingList');
                const listItems = ratingList.querySelectorAll('.list-group-item');
                const selectedValueDisplay = document.getElementById('selectedValue');
                // Changed this line to get the input by its new ID
                const hiddenRatingInput = document.getElementById('cover');

                listItems.forEach(item => {
                    item.addEventListener('click', function (event) {
                        event.preventDefault();

                        // Remove 'active' class from all items
                        listItems.forEach(li => li.classList.remove('active'));

                        // Add 'active' class to the clicked item
                        this.classList.add('active');

                        // Get the data-value of the clicked item
                        const selectedRating = this.getAttribute('value');

                        // Update the hidden input field's value
                        hiddenRatingInput.value = selectedRating;

                        // Update the display for the selected value
                        selectedValueDisplay.textContent = selectedRating;

                        console.log('Cover yang dipilih (untuk disimpan):', selectedRating);
                    });
                });
            });
        </script> -->

            <?php
            $allFactorsApproved = true;
            $unapprovedFactors = [];
            foreach ($transparan as $trans) {
                // Check if 'is_approved' is 0 and not empty
                if (isset($trans['is_approved']) && $trans['is_approved'] == 0) {
                    $allFactorsApproved = false;
                    // Capture the name of the unapproved factor
                    preg_match('/Faktor (\d+)/', $trans['name'], $matches);
                    $unapprovedFactors[] = isset($matches[1]) ? 'Faktor ' . $matches[1] : $trans['name'];
                }
            }

            // $conclusionFieldsFilled = !empty($showfaktor['kesimpulan']) &&
            //     !empty($showfaktor['positifstruktur']) &&
            //     !empty($showfaktor['positifproses']) &&
            //     !empty($showfaktor['positifhasil']) &&
            //     !empty($showfaktor['negatifstruktur']) &&
            //     !empty($showfaktor['negatifproses']) &&
            //     !empty($showfaktor['negatifhasil']);
            
            // Cek jika data 'dirut', 'komut', 'tanggal', dan 'lokasi' sudah terisi
            // $formFieldsFilled = !empty($showfaktor['dirut']) &&
            //     !empty($showfaktor['komut']) &&
            //     !empty($showfaktor['tanggal']) &&
            //     !empty($showfaktor['lokasi']);
            
            // $coverFieldsFilled = !empty($showfaktor['cover']);
            
            // $pdfMergeFieldsFilled = !empty($showfaktor['pdf1_filename']) && !empty($showfaktor['pdf2_filename']);
            
            // Tambahkan juga validasi untuk kesimpulan dan persetujuan faktor
            $disablePdfButton = !($allFactorsApproved);
            // $disablePdfButton = !($allFactorsApproved && $conclusionFieldsFilled && $formFieldsFilled && $coverFieldsFilled);
            
            // Prepare the alert message
            $alertMessage = '';
            $alertClass = '';

            if ($allFactorsApproved) {
                $alertMessage = 'Seluruh faktor telah disetujui.';
                $alertClass = 'alert-success';
            } elseif (!$allFactorsApproved) {
                $alertMessage = 'Persetujuan Direktur Utama pada page berikut belum lengkap: ' . implode(', ', $unapprovedFactors);
                $alertClass = 'alert-warning';
            }

            // Contoh
            // if ($allFactorsApproved && $conclusionFieldsFilled && $formFieldsFilled && $coverFieldsFilled && $pdfMergeFieldsFilled) {
            //     $alertMessage = 'Seluruh faktor telah disetujui, dan data kesimpulan serta informasi pendukung lainnya telah terisi.';
            //     $alertClass = 'alert-success';
            // } elseif (!$allFactorsApproved && !$conclusionFieldsFilled && !$formFieldsFilled && $coverFieldsFilled) {
            //     $alertMessage = 'PDF tidak dapat dibuat. Persetujuan Direktur Utama pada page berikut belum lengkap: ' . implode(', ', $unapprovedFactors) . '. Dan data kesimpulan serta informasi lainnya belum terisi lengkap.';
            //     $alertClass = 'alert-warning';
            // } elseif (!$allFactorsApproved && !$formFieldsFilled) {
            //     $alertMessage = 'PDF tidak dapat dibuat. Persetujuan Direktur Utama pada page berikut belum lengkap: ' . implode(', ', $unapprovedFactors) . '. Dan data informasi lainnya (Direktur Utama, Komisaris Utama, Tanggal, Lokasi) belum terisi lengkap.';
            //     $alertClass = 'alert-warning';
            // } elseif (!$allFactorsApproved) {
            //     $alertMessage = 'PDF tidak dapat dibuat. Persetujuan Direktur Utama pada page berikut belum lengkap: ' . implode(', ', $unapprovedFactors) . '.';
            //     $alertClass = 'alert-warning';
            // } elseif (!$conclusionFieldsFilled) {
            //     $alertMessage = 'PDF tidak dapat dibuat. Data kesimpulan (Kesimpulan, Positif Struktur, Positif Proses, Positif Hasil, Negatif Struktur, Negatif Proses, Negatif Hasil) belum terisi lengkap.';
            //     $alertClass = 'alert-warning';
            // } elseif (!$coverFieldsFilled) {
            //     $alertMessage = 'PDF tidak dapat dibuat. Cover laporan belum dipilih.';
            //     $alertClass = 'alert-warning';
            // } elseif (!$pdfMergeFieldsFilled) {
            //     $alertMessage = 'PDF tidak dapat dibuat. Dokumen pendukung belum di upload.';
            //     $alertClass = 'alert-warning';
            // }
            
            if ($alertMessage !== '') {
                echo '<div class="container-fluid my-4">'; // responsive full width container
                echo '<div class="row justify-content-center">';
                echo '<div class="col-12 col-md-10 col-lg-8">';
                echo '<div class="alert ' . $alertClass . ' alert-dismissible fade show w-100" role="alert">';
                echo $alertMessage;
                echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                echo '</div></div></div></div>';
            }
            ?>

            <div class="col-12 d-flex justify-content-center mt-2">
                <span class="text-secondary" style="font-weight: 600;">Cek kembali seluruh data, pastikan informasi BPR
                    dan
                    data yang diperlukan telah terisi dengan benar</span>
            </div>

            <!-- <div class="col-12 d-flex justify-content-center mt-3">
            <a href="/pdfself/generateFullReport"
                class="btn btn-outline-info shadow <?= $disablePdfButton ? 'disabled' : '' ?>" <?= $disablePdfButton ? 'aria-disabled="true"' : '' ?>>
                <i class="fa fa-file-archive"></i> GENERATE PDF
            </a>
        </div> -->

            <!-- </div>
        <div class="col-12 d-flex justify-content-center mt-5">
            <a href="/ShowTransparansi/exportAllToZip" class="btn btn-success shadow">
                <i class="fa fa-file-archive"></i> GENERATE PDF
            </a>
        </div> -->
            <div class="col-12 d-flex justify-content-center mt-5">
                <a href="/ShowTransparansi/exportAllToZip"
                    class="btn btn-success shadow <?= $disablePdfButton ? 'disabled' : '' ?>" <?= $disablePdfButton ? 'aria-disabled="true"' : '' ?>>
                    <i class="fa fa-file-archive"></i> GENERATE TXT
                </a>
            </div>
            <br>
            <div class="cardpilihfaktor">
                <div class="cardpilihfaktor-header">
                    <h6>Pilih Faktor</h6>
                </div>
                <div class="cardpilihfaktor-body">
                    <div class="d-flex justify-content-center">
                        <div class="btn-toolbar" role="toolbar" aria-label="Toolbar with button groups">
                            <div class="btn-group me-2" role="group" aria-label="First group">
                                <button type="button" class="btn btn-outline-primary btn-sm"
                                    onclick="window.location.href='<?= base_url('Penjelasanumum'); ?>'">1</button>
                                <button type="button" class="btn btn-outline-primary btn-sm"
                                    onclick="window.location.href='<?= base_url('Tgjwbdir'); ?>'">2</button>
                                <button type="button" class="btn btn-outline-primary btn-sm"
                                    onclick="window.location.href='<?= base_url('Tgjwbdekom'); ?>'">3</button>
                                <button type="button" class="btn btn-outline-primary btn-sm"
                                    onclick="window.location.href='<?= base_url('Tgjwbkomite'); ?>'">4</button>
                                <button type="button" class="btn btn-outline-primary btn-sm"
                                    onclick="window.location.href='<?= base_url('Strukturkomite'); ?>'">5</button>
                                <button type="button" class="btn btn-outline-primary btn-sm"
                                    onclick="window.location.href='<?= base_url('Sahamdirdekom'); ?>'">6</button>
                                <button type="button" class="btn btn-outline-primary btn-sm"
                                    onclick="window.location.href='<?= base_url('Shmusahadirdekom'); ?>'">7</button>
                                <button type="button" class="btn btn-outline-primary btn-sm"
                                    onclick="window.location.href='<?= base_url('Shmdirdekomlain'); ?>'">8</button>
                                <button type="button" class="btn btn-outline-primary btn-sm"
                                    onclick="window.location.href='<?= base_url('Keuangandirdekompshm'); ?>'">9</button>
                                <button type="button" class="btn btn-outline-primary btn-sm"
                                    onclick="window.location.href='<?= base_url('Keluargadirdekompshm'); ?>'">10</button>
                                <button type="button" class="btn btn-outline-primary btn-sm"
                                    onclick="window.location.href='<?= base_url('Paketkebijakandirdekom'); ?>'">11</button>
                                <button type="button" class="btn btn-outline-primary btn-sm"
                                    onclick="window.location.href='<?= base_url('Rasiogaji'); ?>'">12</button>
                                <button type="button" class="btn btn-outline-primary btn-sm"
                                    onclick="window.location.href='<?= base_url('Rapat'); ?>'">13</button>
                                <button type="button" class="btn btn-outline-primary btn-sm"
                                    onclick="window.location.href='<?= base_url('Kehadirandekom'); ?>'">14</button>
                                <button type="button" class="btn btn-outline-primary btn-sm"
                                    onclick="window.location.href='<?= base_url('Fraudinternal'); ?>'">15</button>
                                <button type="button" class="btn btn-outline-primary btn-sm"
                                    onclick="window.location.href='<?= base_url('Masalahhukum'); ?>'">16</button>
                                <button type="button" class="btn btn-outline-primary btn-sm"
                                    onclick="window.location.href='<?= base_url('Transaksikepentingan'); ?>'">17</button>
                                <button type="button" class="btn btn-outline-primary btn-sm"
                                    onclick="window.location.href='<?= base_url('Danasosial'); ?>'">18</button>
                                <button style="background-color: #000; color: #fff;" type="button"
                                    class="btn btn-outline-primary btn-sm"
                                    onclick="window.location.href='<?= base_url('ShowTransparansi') ?>'">All</button>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-1">
                        <a href="<?= base_url('periodetransparansi'); ?>" class="btn btn-link btn-sm">Kembali ke halaman
                            periode</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
        flatpickr("#tanggal", {
            dateFormat: "Y/m/d",
        });
    </script>


    <style>
        .cardpilihfaktor {
            width: auto;
            max-width: 650px;
            margin: 10px auto;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .cardpilihfaktor-header {
            text-align: center;
            background-color: #f8f9fa;
            padding: 2px;
            border-bottom: 1px solid #ddd;
            font-size: 1.0rem;
            font-weight: bold;
        }

        .cardpilihfaktor-body {
            padding: 5px;
        }

        .cardpilihfaktor .btn-toolbar {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
        }

        .cardpilihfaktor .btn-group .btn {
            margin: 1px;
        }

        body {
            background-color: #f8f9fa;
        }

        .card {
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-top: 30px;
            margin-bottom: 30px;
        }

        .card-body {
            padding: 40px;
        }

        .alert-info-custom {
            background-color: #e0f7fa;
            color: #007bb5;
            border-color: #b2ebf2;
            border-radius: 10px;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            font-size: 1.1rem;
            margin-bottom: 25px;
        }

        .alert-info-custom strong {
            color: #0056b3;
        }

        .alert-info-custom .fas {
            margin-right: 10px;
            font-size: 1.5rem;
        }

        .h3.mb-4.text-gray-800.text-center {
            color: #343a40;
            font-weight: 700;
            margin-bottom: 30px !important;
            position: relative;
            padding-bottom: 10px;
        }

        .h3.mb-4.text-gray-800.text-center::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: 0;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background-color: #007bff;
            border-radius: 5px;
        }

        .form-group label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }

        .form-control {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #ced4da;
            transition: all 0.3s ease-in-out;
        }

        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        textarea.form-control {
            resize: vertical;
        }

        .btn-primary {
            background-color: #141863;
            border-color: #141863;
            border-radius: 25px;
            padding: 12px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: background-color 0.3s ease, border-color 0.3s ease, transform 0.3s ease;
            margin-left: 15px;
        }

        .btn-primary:hover {
            background-color: #ffffff;
            border-color: #141863;
            transform: translateY(-2px);
            color: #141863;
        }

        .btn-primary2 {
            background-color: #141863;
            border-color: #141863;
            border-radius: 25px;
            padding: 12px 30px;
            font-size: 0.9rem;
            font-weight: 600;
            transition: background-color 0.3s ease, border-color 0.3s ease, transform 0.3s ease;
            margin-right: 10px;
            margin-left: 0px;
            margin-top: 30px;
            margin-bottom: 15px;
            color: #ffffff;
        }

        .btn-primary2:hover {
            background-color: #ffffff;
            border-color: #0056b3;
            transform: translateY(-2px);
            color: #0056b3;
        }

        .btn-primary3 {
            background-color: #141863;
            border-color: #141863;
            border-radius: 25px;
            padding: 12px 30px;
            font-size: 0.9rem;
            font-weight: 600;
            transition: background-color 0.3s ease, border-color 0.3s ease, transform 0.3s ease;
            margin-top: 40px;
            margin-right: 10px;
            margin-bottom: 15px;
            color: #ffffff;
        }

        .btn-primary3:hover {
            background-color: #ffffff;
            border-color: #141863;
            color: #141863;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: #343a40;
            border-color: #343a40;
            border-radius: 55px;
            padding: 12px 30px;
            font-size: 1.0rem;
            font-weight: 600;
            transition: background-color 0.3s ease, border-color 0.3s ease, transform 0.3s ease;
        }

        .btn-secondary:hover {
            background-color: #ffffff;
            border-color: #343a40;
            transform: translateY(-2px);
            color: #343a40;
        }

        .btn-danger {
            background-color: #721c24;
            border-color: #721c24;
            border-radius: 55px;
            padding: 12px 30px;
            font-size: 0.9rem;
            font-weight: 600;
            transition: background-color 0.3s ease, border-color 0.3s ease, transform 0.3s ease;
            margin-right: 5px;
        }

        .btn-danger:hover {
            background-color: #ffffff;
            border-color: #721c24;
            transform: translateY(-2px);
            color: #721c24;
        }

        .btn-success {
            background-color: #ffffff;
            /* Blue button */
            border-color: #28a745;
            border-radius: 25px;
            /* Pill-shaped button */
            padding: 12px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: background-color 0.3s ease, border-color 0.3s ease, transform 0.3s ease;
            margin-right: 15px;
            margin-left: 15px;
            color: #28a745;
        }

        .btn-success:hover {
            background-color: #28a745;
            border-color: #ffffff;
            transform: translateY(-2px);
            color: #ffffff;
            /* Slight lift on hover */
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
            border-radius: 10px;
            width: 1622px;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
            border-radius: 10px;
        }

        .close {
            color: #000;
            opacity: 0.5;
            font-weight: 700;
            text-shadow: none;
        }

        .close:hover {
            opacity: 0.75;
        }

        .beautiful-alert {
            background-color: #e0f7fa;
            /* A very light blue */
            color: #007bb5;
            /* A darker, soothing blue for text */
            border-color: #b2ebf2;
            /* A slightly darker border than background */
            border-radius: 10px;
            /* Rounded corners for a softer look */
            padding: 15px 25px;
            /* More padding for breathing room */
            display: flex;
            /* Use flexbox for alignment of icon and text */
            align-items: center;
            /* Vertically align items in the middle */
            font-size: 1.1rem;
            /* Slightly larger font for readability */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            /* Subtle shadow for depth */
        }

        .beautiful-alert strong {
            color: #0056b3;
            /* Even darker blue for the strong text */
        }

        .beautiful-alert .alert-icon {
            margin-right: 15px;
            /* Space between the icon and text */
            font-size: 1.8rem;
            /* Larger icon size */
            color: #007bff;
            /* Bootstrap primary blue for the icon */
        }

        .custom-badge-container {
            display: flex;
            /* Makes it a flex container */
            justify-content: space-between;
            /* Pushes the first item to the start (left) and the last item to the end (right) */
            align-items: flex-start;
            /* Aligns items to the top if they have different heights */
            /* Add any other styling for the container here, e.g., padding */
            padding: 15px;
            /* Example padding */
        }

        /* Card keseluruhan */
        .approval-card {
            width: 75%;
            left: 6px;
            margin-top: 1px;
            /*(Jika ingin mempertahankan margin-top) */
            height: 95px;
            /* Tinggi tetap */
            border-radius: 15px;
            /* Sudut lebih membulat untuk efek modern */
            overflow: hidden;
            /* Pastikan konten tidak meluber */
        }

        /* Bagian body dari card */
        .approval-card-body {
            display: flex;
            /* Menggunakan flexbox untuk tata letak vertikal */
            flex-direction: column;
            /* Mengatur item dalam kolom (vertikal) */
            justify-content: space-between;
            /* Menarik badge ke atas dan tombol ke bawah */
            padding: 15px;
            /* Sesuaikan padding di dalam card body */
            height: 100%;
            /* Pastikan body mengisi penuh tinggi card */
        }

        /* Card keseluruhan */
        .approvaldir-card {
            position: absolute;
            right: 16px;
            width: 75%;
            margin-top: 1px;
            /*(Jika ingin mempertahankan margin-top) */
            height: 95px;
            /* Tinggi tetap */
            border-radius: 15px;
            /* Sudut lebih membulat untuk efek modern */
            overflow: hidden;
            /* Pastikan konten tidak meluber */
        }

        /* Bagian body dari card */
        .approvaldir-card-body {
            display: flex;
            /* Menggunakan flexbox untuk tata letak vertikal */
            flex-direction: column;
            /* Mengatur item dalam kolom (vertikal) */
            justify-content: right;
            /* Menarik badge ke atas dan tombol ke bawah */
            padding: 15px;
            /* Sesuaikan padding di dalam card body */
            height: 100%;
            /* Pastikan body mengisi penuh tinggi card */
        }

        /* Container untuk badge */
        .approval-badge-container {
            text-align: center;
            /* Menengahkan badge horizontal */
            margin-bottom: 6px;
            /* Jarak antara badge dan tombol */
        }

        /* Gaya badge */
        .approval-badge {
            background-color: #343a40;
            /* Warna hitam gelap (sesuai gambar) */
            color: #ffffff;
            /* Teks putih */
            font-size: 0.8em;
            /* Ukuran font */
            font-weight: 500;
            /* Ketebalan font */
            padding: 5px 12px;
            /* Padding vertikal dan horizontal */
            border-radius: 5px;
            /* Sedikit membulat */
            display: inline-block;
            /* Penting untuk padding dan margin */
            /* Box shadow untuk efek seperti di gambar */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        /* Container untuk tombol-tombol */
        .approval-buttons-container {
            display: flex;
            /* Menggunakan flexbox untuk tata letak horizontal */
            justify-content: center;
            /* Menengahkan tombol-tombol secara keseluruhan */
            align-items: center;
            /* Mengatur vertikal di tengah (jika ada perbedaan tinggi) */
            gap: 9px;
            /* Jarak antar tombol (modern, Bootstrap 5+ setara dengan me-*) */
            /* Jika menggunakan Bootstrap 4 dan tidak ada 'gap': */
            /* .approval-btn:first-child { margin-right: 8px; } */
        }

        /* Gaya umum untuk semua tombol approval */
        .approval-btn {
            flex: 1;
            /* Membuat tombol mengisi ruang yang tersedia secara merata */
            max-width: 50%;
            /* Batasi lebar maksimal agar tidak terlalu besar */
            padding: 8px 15px;
            /* Padding tombol */
            font-size: 0.95em;
            /* Ukuran font tombol */
            font-weight: 500;
            /* Ketebalan font */
            border-radius: 8px;
            /* Sudut tombol yang lebih membulat */
            text-align: center;
            /* Pastikan teks tombol di tengah */
            text-decoration: none;
            /* Hapus garis bawah pada link */
            white-space: nowrap;
            /* Pastikan teks tombol tidak pecah baris */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            /* Bayangan lembut */
            transition: all 0.2s ease-in-out;
            /* Transisi untuk efek hover */
        }

        /* Gaya khusus tombol Setujui */
        .approval-btn-approve {
            background-color: #28a745;
            /* Warna hijau */
            border-color: #28a745;
            color: #fff;
        }

        .approval-btn-approve:hover {
            background-color: #218838;
            /* Hijau lebih gelap saat hover */
            border-color: #1e7e34;
            transform: translateY(-1px);
            /* Efek terangkat sedikit */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        /* Gaya khusus tombol Tolak */
        .approval-btn-reject {
            background-color: #dc3545;
            /* Warna merah */
            border-color: #dc3545;
            color: #fff;
        }

        .approval-btn-reject:hover {
            background-color: #c82333;
            /* Merah lebih gelap saat hover */
            border-color: #bd2130;
            transform: translateY(-1px);
            /* Efek terangkat sedikit */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
    </style>