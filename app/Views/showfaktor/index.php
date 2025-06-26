<div class="alert alert-info my-2">
    <strong><?= esc($namabpr); ?></strong>
    Periode Pelaporan: Semester
    <?php
    $semesterDisplay = esc($periode_semester);
    if (strtolower($semesterDisplay) === 'ganjil') {
        echo '1';
    } elseif (strtolower($semesterDisplay) === 'genap') {
        echo '2';
    } else {
        echo $semesterDisplay;
    }
    ?>
    Tahun <?= esc($periode_tahun); ?>
</div>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= $judul; ?></h1>

    <div class="row">
        <?php foreach ($factors as $factor): ?>
            <div class="col-xl-4 col-md-6 mb-4">
                <div
                    class="card factor-card shadow h-100 py-2 
                    <?= (isset($factor['accdekom']) && $factor['accdekom'] == 1 && isset($factor['is_approved']) && $factor['is_approved'] == 1) ? 'approved-card' : '' ?>">
                    <a href="<?= esc($factor['link']); ?>" style="text-decoration: none; color: inherit;">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= esc($factor['name']); ?></div>
                                    <hr class="sidebar-divider my-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        <?= esc($namabpr); ?>
                                    </div>
                                    <div class="text-xs text-muted mb-3">
                                        Periode: Semester
                                        <?php
                                        $semesterDisplay = esc($periode_semester);
                                        if (strtolower($semesterDisplay) === 'ganjil') {
                                            echo '1';
                                        } elseif (strtolower($semesterDisplay) === 'genap') {
                                            echo '2';
                                        } else {
                                            echo $semesterDisplay;
                                        }
                                        ?>
                                        Tahun <?= esc($periode_tahun); ?>
                                    </div>
                                    <!-- Dynamically Display the Correct Factor Value -->
                                    <div class="text-gray-700">
                                        <strong>Nilai Faktor:</strong>
                                        <?php
                                        // Extract the factor number from the 'name' field
                                        preg_match('/Faktor (\d+)/', $factor['name'], $matches);
                                        $factorNumber = isset($matches[1]) ? $matches[1] : 1; // Default to 1 if not found
                                    
                                        // Special handling for Faktor 1, using 'nfaktor'
                                        if ($factorNumber == 1) {
                                            $factorKey = 'nfaktor'; // Directly use 'nfaktor' for Faktor 1
                                        } else {
                                            // Use 'nfaktor' and append the number dynamically for other factors
                                            $factorKey = 'nfaktor' . $factorNumber;
                                        }

                                        // Get the value for the dynamically generated key
                                        $nfaktorValue = esc($factor[$factorKey] ?? 'N/A');

                                        // Set the class based on whether it's N/A or a valid number
                                        $nfaktorClass = 'text-success';
                                        if ($nfaktorValue === '0' || $nfaktorValue === 'N/A') {
                                            $nfaktorValue = 'N/A';
                                            $nfaktorClass = 'text-danger';
                                        }
                                        ?>
                                        <span class="font-weight-bold <?= $nfaktorClass; ?>"><?= $nfaktorValue; ?></span>
                                    </div>

                                    <!-- Status Approval -->
                                    <div class="text-gray-700">
                                        <?php
                                        // Default message in red
                                        $message = 'Belum disetujui oleh Komisaris Utama';
                                        $statusClass = 'text-danger'; // Default color red
                                    
                                        // Check if accdekom is approved
                                        if (isset($factor['accdekom']) && $factor['accdekom'] == 1) {
                                            // If accdekom is approved, update the message
                                            $message = 'Telah disetujui oleh Komisaris Utama';
                                            $statusClass = 'text-success'; // Green color for approval
                                        }
                                        ?>
                                        <p class="<?= $statusClass; ?>"><?= $message; ?></p>
                                    </div>
                                    <div class="text-gray-700">
                                        <?php
                                        // Default message in red
                                        $message2 = 'Belum disetujui oleh Direktur Utama';
                                        $statusClass = 'text-danger'; // Default color red
                                    
                                        // Check if accdekom is approved
                                        if (isset($factor['is_approved']) && $factor['is_approved'] == 1) {
                                            // If accdekom is approved, update the message
                                            $message2 = 'Telah disetujui oleh Direktur Utama';
                                            $statusClass = 'text-success'; // Green color for approval
                                        }
                                        ?>
                                        <p class="<?= $statusClass; ?>"><?= $message2; ?></p>
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
        <div class="col-12 mb-4">
            <div class="card factor-card shadow h-100 py-3">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Nilai Komposit</div>
                            <hr class="sidebar-divider my-2">
                            <div class="text-M font-weight-bold text-primary text-uppercase mb-1">
                                <?= esc($namabpr); ?>
                            </div>
                            <div class="text-xs text-muted mb-3">
                                Periode: Semester
                                <?php
                                $semesterDisplay = esc($periode_semester);
                                if (strtolower($semesterDisplay) === 'ganjil') {
                                    echo '1';
                                } elseif (strtolower($semesterDisplay) === 'genap') {
                                    echo '2';
                                } else {
                                    echo $semesterDisplay;
                                }
                                ?>
                                Tahun <?= esc($periode_tahun); ?>
                            </div>
                            <div class="text-gray-700" style="font-size: 36px; text-align: right;">
                                <strong>Nilai Komposit:</strong> <span
                                    class="font-weight-bold <?= esc($colorClass); ?>"><?= esc($nilaikomposit); ?></span>
                            </div>
                            <div class="text-gray-700 text-right mt-2">
                                <strong>Peringkat Komposit:</strong> <span
                                    class="font-weight-bold <?= esc($colorClass); ?>"><?= esc($peringkatkomposit); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form for inputting data into the fields -->
        <div class="col-12 mb-4">
            <div class="card factor-card shadow h-100 py-3">
                <div class="card-body">
                    <h5 class="font-weight-bold text-gray-800">Kesimpulan</h5>
                    <form method="post" action="<?= esc(base_url('showfaktor/update')); ?>">
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

                        </div>
                        <!-- dst untuk field lain... -->
                        <div class="d-flex justify-content-center">
                            <button type="submit" class="btn btn-success">Simpan Data</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 mb-4">
            <div class="card factor-card shadow h-100 py-3">
                <div class="card-body">
                    <h5 class="font-weight-bold text-gray-800">Lembar Persetujuan</h5>
                    <form method="post" action="<?= esc(base_url('showfaktor/updatettd')); ?>">
                        <input type="hidden" name="id" value="<?= esc($showfaktor['id'] ?? ''); ?>">
                        <div class="form-group">
                            <label for="dirut">Nama Direktur Utama</label>
                            <input type="text" class="form-control" id="dirut" name="dirut"
                                value="<?= esc($showfaktor['dirut'] ?? ''); ?>" required>

                            <label for="komut">Nama Komisaris Utama</label>
                            <input type="text" class="form-control" id="komut" name="komut"
                                value="<?= esc($showfaktor['komut'] ?? ''); ?>" required>

                            <label for="tanggal">Tanggal Pelaporan</label>
                            <input type="text" class="form-control" id="tanggal" name="tanggal" placeholder="dd/mm/yyyy"
                                value="<?= esc($showfaktor['tanggal'] ?? ''); ?>" required>

                            <label for="lokasi">Lokasi</label>
                            <input type="text" class="form-control" id="lokasi" name="lokasi"
                                value="<?= esc($showfaktor['lokasi'] ?? ''); ?>" required>
                        </div>
                        <!-- dst untuk field lain... -->
                        <div class="d-flex justify-content-center">
                            <button type="submit" class="btn btn-success">Simpan Data</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 mb-4">
            <div class="card shadow-lg border-0 rounded-lg">
                <div class="card-body p-4 p-md-5">
                    <h4 class="font-weight-bold text-gray-900 mb-4 text-center">Upload Dokumen Pendukung</h4>
                    <p class="text-muted text-center mb-4">Silakan unggah file PDF pendukung laporan.</p>
                    <form method="post" action="<?= base_url('pdfself/uploadPdf'); ?>" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="pdf1">Upload PDF 1 (Opsional)</label>
                            <input type="file" class="form-control" id="pdf1" name="pdf1" accept="application/pdf">
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
                            <label for="pdf2">Upload PDF 2 (Opsional)</label>
                            <input type="file" class="form-control" id="pdf2" name="pdf2" accept="application/pdf">
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
        <!-- <script>
            // Script untuk menampilkan nama file yang dipilih pada custom file input
            document.querySelectorAll('.custom-file-input').forEach(input => {
                input.addEventListener('change', function (e) {
                    var fileName = e.target.files[0].name;
                    var nextSibling = e.target.nextElementSibling;
                    nextSibling.innerText = fileName;
                });
            });
        </script> -->

        <?php
        $allFactorsApproved = true;
        $unapprovedFactors = [];
        foreach ($factors as $factor) {
            if (!(isset($factor['is_approved']) && $factor['is_approved'] == 1)) {
                $allFactorsApproved = false;
                // Capture the name of the unapproved factor
                preg_match('/Faktor (\d+)/', $factor['name'], $matches);
                $unapprovedFactors[] = isset($matches[1]) ? 'Faktor ' . $matches[1] : $factor['name'];
            }
        }

        $conclusionFieldsFilled = !empty($showfaktor['kesimpulan']) &&
            !empty($showfaktor['positifstruktur']) &&
            !empty($showfaktor['positifproses']) &&
            !empty($showfaktor['positifhasil']) &&
            !empty($showfaktor['negatifstruktur']) &&
            !empty($showfaktor['negatifproses']) &&
            !empty($showfaktor['negatifhasil']);

        // Cek jika data 'dirut', 'komut', 'tanggal', dan 'lokasi' sudah terisi
        $formFieldsFilled = !empty($showfaktor['dirut']) &&
            !empty($showfaktor['komut']) &&
            !empty($showfaktor['tanggal']) &&
            !empty($showfaktor['lokasi']);

        // Tambahkan juga validasi untuk kesimpulan dan persetujuan faktor
        $disablePdfButton = !($allFactorsApproved && $conclusionFieldsFilled && $formFieldsFilled);

        // Prepare the alert message
        $alertMessage = '';
        $alertClass = '';

        if ($allFactorsApproved && $conclusionFieldsFilled && $formFieldsFilled) {
            $alertMessage = 'Seluruh faktor telah disetujui, dan data kesimpulan serta informasi lainnya telah terisi.';
            $alertClass = 'alert-success';
        } elseif (!$allFactorsApproved && !$conclusionFieldsFilled && !$formFieldsFilled) {
            $alertMessage = 'PDF tidak dapat dibuat. Persetujuan Direktur Utama untuk faktor-faktor berikut belum lengkap: ' . implode(', ', $unapprovedFactors) . '. Dan data kesimpulan serta informasi lainnya belum terisi lengkap.';
            $alertClass = 'alert-warning';
        } elseif (!$allFactorsApproved && !$formFieldsFilled) {
            $alertMessage = 'PDF tidak dapat dibuat. Persetujuan Direktur Utama untuk faktor-faktor berikut belum lengkap: ' . implode(', ', $unapprovedFactors) . '. Dan data informasi lainnya (Direktur Utama, Komisaris Utama, Tanggal, Lokasi) belum terisi lengkap.';
            $alertClass = 'alert-warning';
        } elseif (!$allFactorsApproved) {
            $alertMessage = 'PDF tidak dapat dibuat. Persetujuan Direktur Utama untuk faktor-faktor berikut belum lengkap: ' . implode(', ', $unapprovedFactors) . '.';
            $alertClass = 'alert-warning';
        } elseif (!$conclusionFieldsFilled) {
            $alertMessage = 'PDF tidak dapat dibuat. Data kesimpulan (Kesimpulan, Positif Struktur, Positif Proses, Positif Hasil, Negatif Struktur, Negatif Proses, Negatif Hasil) belum terisi lengkap.';
            $alertClass = 'alert-warning';
        } elseif (!$formFieldsFilled) {
            $alertMessage = 'PDF tidak dapat dibuat. Data informasi (Direktur Utama, Komisaris Utama, Tanggal, Lokasi) belum terisi lengkap.';
            $alertClass = 'alert-warning';
        }

        if ($alertMessage !== '') {
            echo '<div class="col-12 mb-3"><div class="alert ' . $alertClass . '" role="alert">' . $alertMessage . '</div></div>';
        }
        ?>


        <div class="col-12 d-flex justify-content-center mt-3">
            <a href="/pdfself/generateFullReport"
                class="btn btn-outline-info shadow <?= $disablePdfButton ? 'disabled' : '' ?>" <?= $disablePdfButton ? 'aria-disabled="true"' : '' ?>>
                <i class="fa fa-file-archive"></i> GENERATE PDF
            </a>
        </div>
    </div>
    <br>
    <div class="d-flex justify-content-center">
        <div class="btn-toolbar" role="toolbar" aria-label="Toolbar with button groups">
            <div class="btn-group me-2" role="group" aria-label="First group">
                <button type="button" class="btn btn-outline-primary btn-sm"
                    onclick="window.location.href='<?= base_url('periode'); ?>'">Periode</button>
                <button type="button" class="btn btn-outline-primary btn-sm"
                    onclick="window.location.href='<?= base_url('faktor'); ?>'">1</button>
                <button type="button" class="btn btn-outline-primary btn-sm"
                    onclick="window.location.href='<?= base_url('faktor2'); ?>'">2</button>
                <button type="button" class="btn btn-outline-primary btn-sm"
                    onclick="window.location.href='<?= base_url('faktor3'); ?>'">3</button>
                <button type="button" class="btn btn-outline-primary btn-sm"
                    onclick="window.location.href='<?= base_url('faktor4'); ?>'">4</button>
                <button type="button" class="btn btn-outline-primary btn-sm"
                    onclick="window.location.href='<?= base_url('faktor5'); ?>'">5</button>
                <button type="button" class="btn btn-outline-primary btn-sm"
                    onclick="window.location.href='<?= base_url('faktor6'); ?>'">6</button>
                <button type="button" class="btn btn-outline-primary btn-sm"
                    onclick="window.location.href='<?= base_url('faktor7'); ?>'">7</button>
                <button type="button" class="btn btn-outline-primary btn-sm"
                    onclick="window.location.href='<?= base_url('faktor8'); ?>'">8</button>
                <button type="button" class="btn btn-outline-primary btn-sm"
                    onclick="window.location.href='<?= base_url('faktor9'); ?>'">9</button>
                <button type="button" class="btn btn-outline-primary btn-sm"
                    onclick="window.location.href='<?= base_url('faktor10'); ?>'">10</button>
                <button type="button" class="btn btn-outline-primary btn-sm"
                    onclick="window.location.href='<?= base_url('faktor11'); ?>'">11</button>
                <button type="button" class="btn btn-outline-primary btn-sm"
                    onclick="window.location.href='<?= base_url('faktor12'); ?>'">12</button>
                <button type="button" class="btn btn-outline-primary btn-sm"
                    onclick="window.location.href='<?= base_url('showFaktor'); ?>'">All</button>
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
    .btn-outline-info {
        background-color: #28a745;
        /* Warna latar belakang hijau muda */
        color: white;
        /* Warna teks putih */
        padding: 10px 30px;
        /* Jarak dalam tombol */
        font-size: 16px;
        /* Ukuran font */
        border-radius: 50px;
        /* Membuat tombol oval */
        transition: all 0.5s ease;
        /* Efek transisi saat hover */
    }

    /* Efek saat hover pada tombol */
    .btn-outline-info:hover {
        background-color: #218838;
        /* Ubah warna latar belakang saat hover */
        border-color: #218838;
        /* Ubah border menjadi hijau gelap */
        color: white;
        /* Pastikan warna teks tetap putih saat hover */
    }

    /* Menambahkan bayangan pada tombol untuk efek kedalaman */
    .btn-outline-info.shadow {
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        /* Menambah bayangan halus */
    }

    .btn-outline-info i {
        margin-right: 10px;
        /* Memberikan jarak antara ikon dan teks */
    }

    /* Default card appearance */
    .factor-card {
        border-left: 5px solid #343a40;
        /* Default border color */
        border-radius: 8px;
        background-color: #f8f9fa;
        transition: transform 0.3s ease, box-shadow 0.3s ease, border-left 0.3s ease;
    }

    /* When accdekom = 1 and is_approved = 1, add a green left border */
    .approved-card {
        border-left: 5px solid #28a745 !important;
        /* Green border */
    }

    /* Hover effect for the card */
    .factor-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .factor-card:hover .approved-card {
        border-left: 5px solid #218838 !important;
        /* Darker green on hover for approved card */
    }

    /* Card body */
    .card-body {
        padding: 15px;
        background-color: white;
    }

    /* Text colors for the statuses */
    .text-success {
        color: #28a745 !important;
    }

    .text-danger {
        color: #dc3545;
    }

    /* Add more customizations if needed */
    .text-muted {
        color: #6c757d;
    }

    /* Style for disabled button */
    .btn.disabled {
        pointer-events: none;
        /* Disables click events */
        opacity: 0.65;
        /* Reduces opacity to indicate it's disabled */
        cursor: not-allowed;
        /* Changes cursor to a "not allowed" symbol */
    }
</style>

<style>
    /* Button select page */
    .btn-outline-primary {
        border-width: 0px;
        background-color: transparent;
        color: #007bff;
        /* Primary color */
        transition: all 0.3s ease;
    }

    .btn-outline-primary:hover {
        background-color: #007bff;
        color: white;
        border-color: #007bff;
    }

    .btn-sm {
        font-size: 16px;
        padding: 6px 10px;
    }
</style>