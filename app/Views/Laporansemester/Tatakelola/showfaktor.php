<?php if (session()->getFlashdata('message')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">&times;</button>
        <strong><?= esc(session()->getFlashdata('message')); ?></strong>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('err')): ?>
    <div class="alert alert-danger" role="alert"><?= esc(session()->getFlashdata('err')); ?></div>
<?php endif; ?>

<a href="<?= base_url('Showsemesteran') ?>" class="btn btn-outline-secondary ml-4 mt-2">
    <i class="fas fa-arrow-left"></i> Kembali ke halaman periode
</a>

<div class="container-fluid">
    <div class="page-header">
        <h1 class="page-title"><?= esc($judul); ?></h1>
        <div class="page-subtitle">
            <?php
            $semDisplay = esc($periode_semester);
            $semNum = strtolower($semDisplay) === 'ganjil' ? '1' : (strtolower($semDisplay) === 'genap' ? '2' : $semDisplay);
            ?>
            Semester <?= $semNum ?> Tahun <?= esc($periode_tahun); ?>
        </div>
        <span class="badge-namabpr"><?= esc($namabpr); ?></span>
    </div>

    <!-- NILAI KOMPOSIT CARD -->
    <div class="komposit-body">
        <div class="peringkat-badge">
            <div class="peringkat-label">Peringkat Komposit</div>
            <div class="peringkat-value <?= esc($colorClass); ?>">
                <?= esc($peringkatkomposit); ?>
            </div>
            <div class="peringkat-score">Nilai Komposit: (
                <?= esc($nilaikomposit); ?>)
            </div>
        </div>
    </div>

    <div class="section-title">Ringkasan Penilaian Self Assessment Tata Kelola</div>

    <!-- GRID LAYOUT FOR FACTORS -->
    <div class="factors-grid-container loading" id="factorsContainer">
        <div class="skeleton-grid">
            <?php for ($i = 0; $i < 6; $i++): ?>
                <div class="skeleton-factor-card">
                    <div class="skeleton-header skeleton"></div>
                    <div class="skeleton-body">
                        <div class="skeleton skeleton-text title"></div>
                        <div class="skeleton skeleton-text"></div>
                        <div class="skeleton skeleton-badge"></div>
                        <div class="skeleton skeleton-text small"></div>
                        <div class="skeleton skeleton-text small"></div>
                    </div>
                </div>
            <?php endfor; ?>
        </div>

        <!-- Actual Factor Cards -->
        <div class="factors-grid">
            <?php foreach ($factors as $index => $factor): ?>
                <div
                    class="factor-grid-card <?= (isset($factor['accdekom']) && $factor['accdekom'] == 1 && isset($factor['is_approved']) && $factor['is_approved'] == 1) ? 'approved-card' : '' ?>">
                    <a href="<?= esc($factor['link']); ?>" style="text-decoration: none; color: inherit;">
                        <div class="factor-card-header">
                            <?php
                            preg_match('/Faktor (\d+)/', $factor['name'], $matches);
                            $factorNumber = isset($matches[1]) ? $matches[1] : ($index + 1);
                            ?>
                            <div class="factor-number">Faktor <?= $factorNumber; ?></div>
                        </div>

                        <div class="factor-card-body">
                            <div class="factor-title"><?= esc($factor['name']); ?></div>

                            <div class="factor-info">
                                <div class="bank-name"><?= esc($namabpr); ?></div>
                                <div class="periode-text">
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
                            </div>

                            <div class="factor-nilai-badge">
                                <?php
                                if ($factorNumber == 1) {
                                    $factorKey = 'nfaktor';
                                } else {
                                    $factorKey = 'nfaktor' . $factorNumber;
                                }

                                $nfaktorValue = esc($factor[$factorKey] ?? 'N/A');
                                $nfaktorClass = 'nilai-success';
                                if ($nfaktorValue === '0' || $nfaktorValue === 'N/A') {
                                    $nfaktorValue = 'N/A';
                                    $nfaktorClass = 'nilai-danger';
                                }
                                ?>
                                <div class="nilai-label">Nilai Faktor:</div>
                                <div class="nilai-value <?= $nfaktorClass; ?>"><?= $nfaktorValue; ?></div>
                            </div>

                            <div class="approval-badges">
                                <?php
                                $komusApproved = isset($factor['accdekom']) && $factor['accdekom'] == 1;
                                $dirutApproved = isset($factor['is_approved']) && $factor['is_approved'] == 1;
                                ?>
                                <div class="approval-badge <?= $komusApproved ? 'approved' : 'pending' ?>">
                                    <?= $komusApproved ? '✓' : '○' ?>
                                    <?= $komusApproved ? 'Disetujui' : 'Belum disetujui' ?> Komisaris Utama
                                </div>
                                <div class="approval-badge <?= $dirutApproved ? 'approved' : 'pending' ?>">
                                    <?= $dirutApproved ? '✓' : '○' ?>
                                    <?= $dirutApproved ? 'Disetujui' : 'Belum disetujui' ?> Direktur Utama
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card kesimpulan-card shadow mb-4">
        <div class="card-body">
            <form method="post" action="<?= base_url('ShowFaktor/update') ?>" id="form-kesimpulan">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= esc($showfaktor['id'] ?? ''); ?>">

                <div class="form-group">
                    <label for="kesimpulan">Kesimpulan</label>
                    <textarea class="form-control" id="kesimpulan" name="kesimpulan" rows="7"
                        maxlength="2000"><?= esc($showfaktor['kesimpulan'] ?? ''); ?></textarea>
                    <small class="form-text text-muted text-right d-block">
                        <span class="char-counter" data-target="kesimpulan">0</span>/2000 karakter
                    </small>

                    <label for="positifstruktur">Positif Struktur</label>
                    <textarea class="form-control" id="positifstruktur" name="positifstruktur" rows="7"
                        maxlength="650"><?= esc($showfaktor['positifstruktur'] ?? ''); ?></textarea>
                    <small class="form-text text-muted text-right d-block">
                        <span class="char-counter" data-target="positifstruktur">0</span>/650 karakter
                    </small>

                    <label for="positifproses">Positif Proses</label>
                    <textarea class="form-control" id="positifproses" name="positifproses" rows="7"
                        maxlength="650"><?= esc($showfaktor['positifproses'] ?? ''); ?></textarea>
                    <small class="form-text text-muted text-right d-block">
                        <span class="char-counter" data-target="positifproses">0</span>/650 karakter
                    </small>

                    <label for="positifhasil">Positif Hasil</label>
                    <textarea class="form-control" id="positifhasil" name="positifhasil" rows="7"
                        maxlength="650"><?= esc($showfaktor['positifhasil'] ?? ''); ?></textarea>
                    <small class="form-text text-muted text-right d-block">
                        <span class="char-counter" data-target="positifhasil">0</span>/650 karakter
                    </small>

                    <label for="negatifstruktur">Negatif Struktur</label>
                    <textarea class="form-control" id="negatifstruktur" name="negatifstruktur" rows="7"
                        maxlength="650"><?= esc($showfaktor['negatifstruktur'] ?? ''); ?></textarea>
                    <small class="form-text text-muted text-right d-block">
                        <span class="char-counter" data-target="negatifstruktur">0</span>/650 karakter
                    </small>

                    <label for="negatifproses">Negatif Proses</label>
                    <textarea class="form-control" id="negatifproses" name="negatifproses" rows="7"
                        maxlength="650"><?= esc($showfaktor['negatifproses'] ?? ''); ?></textarea>
                    <small class="form-text text-muted text-right d-block">
                        <span class="char-counter" data-target="negatifproses">0</span>/650 karakter
                    </small>

                    <label for="negatifhasil">Negatif Hasil</label>
                    <textarea class="form-control" id="negatifhasil" name="negatifhasil" rows="7"
                        maxlength="650"><?= esc($showfaktor['negatifhasil'] ?? ''); ?></textarea>
                    <small class="form-text text-muted text-right d-block">
                        <span class="char-counter" data-target="negatifhasil">0</span>/650 karakter
                    </small>
                </div>
                <div class="d-flex justify-content-center mb-3">
                    <button type="submit" class="btn btn-success">Simpan Kesimpulan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- LEMBAR PERSETUJUAN CARD -->
    <div class="card kesimpulan-card shadow mb-4">
        <div class="card-body">
            <h5 class="font-weight-bold text-gray-800">Lembar Persetujuan</h5>
            <form method="post" action="<?= esc(base_url('ShowFaktor/updatettd')); ?>">
                <input type="hidden" name="id" value="<?= esc($showfaktor['id'] ?? ''); ?>">
                <div class="form-group">
                    <label for="dirut">Nama Direktur Utama</label>
                    <input type="text" class="form-control auto-save" id="dirut" name="dirut"
                        value="<?= esc($showfaktor['dirut'] ?? ''); ?>" data-field="dirut">

                    <label for="komut">Nama Komisaris Utama</label>
                    <input type="text" class="form-control auto-save" id="komut" name="komut"
                        value="<?= esc($showfaktor['komut'] ?? ''); ?>" data-field="komut">

                    <label for="tanggal">Tanggal Pelaporan</label>
                    <input type="text" class="form-control auto-save" id="tanggal" name="tanggal"
                        placeholder="dd/mm/yyyy" value="<?= esc($showfaktor['tanggal'] ?? ''); ?>" data-field="tanggal">

                    <label for="lokasi">Lokasi</label>
                    <input type="text" class="form-control auto-save" id="lokasi" name="lokasi"
                        value="<?= esc($showfaktor['lokasi'] ?? ''); ?>" data-field="lokasi">
                </div>
                <div class="d-flex justify-content-center">
                    <button type="submit" class="btn btn-success">Simpan Data</button>
                </div>
            </form>

            <div class="d-flex justify-content-center">
                <a href="<?= base_url('ShowFaktor/cetakPengesahan') ?>" target="_blank" class="btn btn-outline-info">
                    <i class="fa fa-file-pdf"></i> Cetak Lembar Pengesahan
                </a>
            </div>
        </div>
    </div>

    <!-- UPLOAD DOKUMEN CARD -->
    <div class="card upload-card shadow mb-4">
        <div class="card-body">
            <h4 class="font-weight-bold text-gray-900 text-center">Upload Dokumen Pendukung</h4>
            <p class="text-muted text-center mb-4">Silakan unggah file PDF pendukung laporan.</p>
            <form method="post" action="<?= base_url('pdfself/uploadPdf'); ?>" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="pdf1">Upload Laporan Pokok Pelaksanaan tugas anggota Direksi yang membawahkan fungsi
                        kepatuhan (.PDF)</label>
                    <input type="file" class="form-control" id="pdf1" name="pdf1" accept="application/pdf">
                    <small class="form-text text-muted">Maksimal ukuran file .PDF 2 Mb</small>
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
                    <small class="form-text text-muted">Maksimal ukuran file .PDF 2 Mb</small>
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

                <div class="form-group">
                    <label for="pdf3">Upload Lembar Persetujuan yang telah ditandatangani (.PDF)</label>
                    <input type="file" class="form-control" id="pdf3" name="pdf3" accept="application/pdf">
                    <small class="form-text text-muted">Maksimal ukuran file .PDF 2 Mb</small>
                </div>
                <?php if (!empty($showfaktor['pdf3_filename'])): ?>
                    <div class="mt-2">
                        <p>Lembar Persetujuan Terunggah:
                            <a href="<?= base_url('pdfself/download/' . esc($showfaktor['pdf3_filename'])); ?>"
                                target="_blank">
                                <?= esc($showfaktor['pdf3_filename']); ?>
                            </a>
                        </p>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-center">
                    <button type="submit" class="btn btn-success">Upload PDF</button>
                </div>
            </form>
        </div>
    </div>

    <!-- COVER SELECTION CARD -->
    <div class="card cover-card shadow mb-4">
        <div class="card-body">
            <h4 class="font-weight-bold text-gray-900 text-center">Cover Laporan</h4>
            <p class="text-muted text-center mb-2">Silakan pilih cover untuk PDF pelaporan.</p>
            <form method="post" action="<?= esc(base_url('ShowFaktor/updatecover')); ?>">
                <input type="hidden" name="id" value="<?= esc($showfaktor['id'] ?? ''); ?>">
                <input type="hidden" name="cover" id="cover" value="<?= esc($showfaktor['cover'] ?? ''); ?>">
                <div class="cover-grid">
                    <div class="cover-option" data-value="Cover.png">
                        <img src="/assets/img/Cover.png" alt="Cover" loading="lazy">
                    </div>
                    <div class="cover-option" data-value="Cover1.png">
                        <img src="/assets/img/Cover1.png" alt="Cover 1" loading="lazy">
                    </div>
                    <div class="cover-option" data-value="Cover2.png">
                        <img src="/assets/img/Cover2.png" alt="Cover 2" loading="lazy">
                    </div>
                    <div class="cover-option" data-value="Cover3.png">
                        <img src="/assets/img/Cover3.png" alt="Cover 3" loading="lazy">
                    </div>
                    <div class="cover-option" data-value="Cover4.png">
                        <img src="/assets/img/Cover4.png" alt="Cover 4" loading="lazy">
                    </div>
                </div>
                <div class="d-flex justify-content-center mt-3">
                    <button type="submit" class="btn btn-success">Pilih Cover</button>
                </div>
            </form>
        </div>
    </div>

    <?php
    $allFactorsApproved = true;
    $unapprovedFactors = [];
    foreach ($factors as $factor) {
        if (!(isset($factor['is_approved']) && $factor['is_approved'] == 1)) {
            $allFactorsApproved = false;
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

    $formFieldsFilled = !empty($showfaktor['dirut']) &&
        !empty($showfaktor['komut']) &&
        !empty($showfaktor['tanggal']) &&
        !empty($showfaktor['lokasi']);

    $coverFieldsFilled = !empty($showfaktor['cover']);
    $pdfMergeFieldsFilled = !empty($showfaktor['pdf1_filename']) && !empty($showfaktor['pdf2_filename']);
    $disablePdfButton = !($allFactorsApproved && $conclusionFieldsFilled && $formFieldsFilled && $coverFieldsFilled);

    $alertMessage = '';
    $alertClass = '';

    if ($allFactorsApproved && $conclusionFieldsFilled && $formFieldsFilled && $coverFieldsFilled && $pdfMergeFieldsFilled) {
        $alertMessage = 'Seluruh faktor telah disetujui, dan data kesimpulan serta informasi pendukung lainnya telah terisi.';
        $alertClass = 'alert-success';
    } elseif (!$allFactorsApproved && !$conclusionFieldsFilled && !$formFieldsFilled) {
        $alertMessage = 'PDF tidak dapat dibuat. Persetujuan Direktur Utama untuk faktor-faktor berikut belum lengkap: ' . implode(', ', $unapprovedFactors) . '. Dan data kesimpulan serta informasi lainnya belum terisi lengkap.';
        $alertClass = 'alert-warning';
    } elseif (!$allFactorsApproved) {
        $alertMessage = 'PDF tidak dapat dibuat. Persetujuan Direktur Utama untuk faktor-faktor berikut belum lengkap: ' . implode(', ', $unapprovedFactors) . '.';
        $alertClass = 'alert-warning';
    } elseif (!$conclusionFieldsFilled) {
        $alertMessage = 'PDF tidak dapat dibuat. Data kesimpulan belum terisi lengkap.';
        $alertClass = 'alert-warning';
    } elseif (!$formFieldsFilled) {
        $alertMessage = 'PDF tidak dapat dibuat. Data informasi (Direktur Utama, Komisaris Utama, Tanggal, Lokasi) belum terisi lengkap.';
        $alertClass = 'alert-warning';
    } elseif (!$coverFieldsFilled) {
        $alertMessage = 'PDF tidak dapat dibuat. Cover laporan belum dipilih.';
        $alertClass = 'alert-warning';
    } elseif (!$pdfMergeFieldsFilled) {
        $alertMessage = 'PDF tidak dapat dibuat. Dokumen pendukung belum di upload.';
        $alertClass = 'alert-warning';
    }

    if ($alertMessage !== '') {
        echo '<div class="alert ' . $alertClass . ' text-center" role="alert">' . $alertMessage . '</div>';
    }
    ?>

    <div class="generate-section">
        <span class="text-secondary">Cek kembali seluruh data, pastikan informasi BPR dan data yang diperlukan telah
            terisi dengan benar</span>
        <a href="/pdfself/generateFullReport"
            class="btn btn-outline-info shadow <?= $disablePdfButton ? 'disabled' : '' ?>" <?= $disablePdfButton ? 'aria-disabled="true"' : '' ?>>
            <i class="fa fa-file-archive"></i> GENERATE LAPORAN
        </a>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
    const AUTOSAVE_MAX = 2000;

    function updateCharCounter(textarea) {
        const counter = document.querySelector(`.char-counter[data-target="${textarea.id}"]`);
        if (!counter) return;

        const max = textarea.maxLength; // baca dari atribut maxlength masing-masing textarea
        const len = textarea.value.length;
        counter.textContent = len;

        if (len >= max) {
            counter.style.color = '#dc3545';
            counter.parentElement.classList.add('font-weight-bold');
        } else if (len >= max * 0.9) {
            counter.style.color = '#fd7e14';
            counter.parentElement.classList.remove('font-weight-bold');
        } else {
            counter.style.color = '';
            counter.parentElement.classList.remove('font-weight-bold');
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const savedScroll = sessionStorage.getItem('scrollPosKesimpulan');

        setTimeout(() => {
            const container = document.getElementById('factorsContainer');
            if (container) {
                container.classList.remove('loading');
            }

            if (savedScroll !== null) {
                window.scrollTo(0, parseInt(savedScroll, 10));
                sessionStorage.removeItem('scrollPosKesimpulan');
            }
        }, 1000);

        flatpickr("#tanggal", {
            dateFormat: "Y/m/d",
        });

        lazyLoadImages();
    });

    const formKesimpulan = document.getElementById('form-kesimpulan');
    if (formKesimpulan) {
        formKesimpulan.addEventListener('submit', function () {
            sessionStorage.setItem('scrollPosKesimpulan', window.scrollY);
        });
    }

    document.addEventListener('input', function (e) {
        if (e.target.matches('#form-kesimpulan textarea[maxlength]')) {
            updateCharCounter(e.target);
        }
    });

    document.querySelectorAll('#form-kesimpulan textarea[maxlength]').forEach(function (ta) {
        updateCharCounter(ta);
    });

    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(() => {
            const container = document.getElementById('factorsContainer');
            if (container) {
                container.classList.remove('loading');
            }
        }, 1000);

        flatpickr("#tanggal", {
            dateFormat: "Y/m/d",
        });

        lazyLoadImages();
    });

    function lazyLoadImages() {
        const images = document.querySelectorAll('img[loading="lazy"]');

        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.classList.add('loaded');
                        observer.unobserve(img);
                    }
                });
            });

            images.forEach(img => imageObserver.observe(img));
        } else {
            images.forEach(img => img.classList.add('loaded'));
        }
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    const autoSaveInputs = document.querySelectorAll('.auto-save');

    const saveData = debounce(function (field, value) {
        console.log(`Auto-saving ${field}: ${value}`);

        const element = document.querySelector(`[data-field="${field}"]`);
        if (element) {
            element.style.borderColor = '#4A90E2';
            setTimeout(() => {
                element.style.borderColor = '';
            }, 1000);
        }
    }, 1000);

    autoSaveInputs.forEach(input => {
        input.addEventListener('input', function () {
            const field = this.dataset.field;
            const value = this.value;
            saveData(field, value);
        });
    });

    const coverOptions = document.querySelectorAll('.cover-option');
    const coverInput = document.getElementById('cover');

    coverOptions.forEach(option => {
        option.addEventListener('click', function () {
            coverOptions.forEach(opt => opt.classList.remove('active'));
            this.classList.add('active');
            coverInput.value = this.dataset.value;
        });
    });

    if (coverInput && coverInput.value) {
        const activeOption = document.querySelector(`.cover-option[data-value="${coverInput.value}"]`);
        if (activeOption) {
            activeOption.classList.add('active');
        }
    }
</script>

<style>
    :root {
        --blue: #3b82f6;
        --blue-d: #1d4ed8;
        --primary-gradient: linear-gradient(135deg, #141863 0%, #1f2aa8 100%);
        --success-gradient: linear-gradient(135deg, #146337 0%, #1fa85a 100%);
        --danger-gradient: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
        --warning-gradient: linear-gradient(135deg, #FFA726 0%, #FB8C00 100%);
        --bg-gradient: linear-gradient(135deg, #E3F2FD 0%, #BBDEFB 100%);

        --border-radius-sm: 8px;
        --border-radius-md: 12px;
        --border-radius-lg: 20px;
        --border-radius-xl: 25px;

        --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.08);
        --shadow-md: 0 4px 15px rgba(0, 0, 0, 0.12);
        --shadow-lg: 0 8px 25px rgba(0, 0, 0, 0.15);

        --transition-fast: 0.2s ease;
        --transition-normal: 0.3s ease;
        --transition-slow: 0.4s ease;
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    .container-fluid {
        min-height: 100vh;
        padding: 1.5rem 1rem;
    }

    /* Alert Info */
    .alert-info {
        background: var(--primary-gradient);
        border: none;
        border-radius: var(--border-radius-md);
        color: white;
        padding: 1rem 1.25rem;
        box-shadow: var(--shadow-md);
        margin-bottom: 1.5rem;
        font-size: 0.95rem;
    }

    .alert-info strong {
        display: block;
        margin-bottom: 0.25rem;
    }

    /* Page Title */
    .h3.mb-4 {
        color: #2c3e50;
        font-weight: 700;
        text-align: center;
        margin-bottom: 1.5rem !important;
        font-size: clamp(1.25rem, 4vw, 1.75rem);
    }

    .page-header {
        text-align: center;
        padding: 28px 20px 22px;
        background: white;
        border-bottom: 1px solid var(--gray-200);
    }

    .page-title {
        font-size: clamp(1.25rem, 3.5vw, 1.65rem);
        font-weight: 800;
        color: var(--gray-800);
        margin-bottom: 5px;
    }

    .page-subtitle {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--blue);
        margin-bottom: 10px;
    }

    .badge-namabpr {
        display: inline-block;
        background: var(--primary-gradient);
        color: white;
        font-size: 0.92rem;
        font-weight: 600;
        padding: 5px 18px;
        border-radius: 20px;
    }

    /* NILAI KOMPOSIT COMPACT */
    .nilai-komposit-compact {
        background: white;
        border-radius: var(--border-radius-lg);
        overflow: hidden;
        box-shadow: var(--shadow-md);
        margin-bottom: 2rem;
    }

    .komposit-header {
        background: var(--primary-gradient);
        padding: 0.75rem 1.5rem;
        text-align: center;
    }

    .komposit-header h5 {
        color: white;
        font-weight: 700;
        font-size: 1.1rem;
        margin: 0;
    }

    .komposit-body {
        padding: 1.5rem;
        text-align: center;
    }

    .peringkat-badge {
        display: inline-block;
        background: linear-gradient(135deg, #141863 0%, #1f2aa8 100%);
        border-radius: var(--border-radius-lg);
        padding: 1.5rem 3rem;
        color: white;
    }

    .peringkat-label {
        font-size: 0.9rem;
        font-weight: 500;
        margin-bottom: 0.5rem;
        opacity: 0.95;
    }

    .peringkat-value {
        font-size: 2.5rem;
        font-weight: 800;
        margin-bottom: 0.25rem;
        line-height: 1;
    }

    .peringkat-score {
        font-size: 0.95rem;
        font-weight: 500;
        opacity: 0.9;
    }

    /* Color classes for peringkat */
    .peringkat-value.text-success {
        color: white !important;
    }

    .peringkat-value.text-danger {
        color: white !important;
    }

    /* Section Title */
    .section-title {
        font-size: 1.2rem;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 3px solid #141863;
    }

    /* SKELETON LOADING */
    @keyframes skeleton-loading {
        0% {
            background-position: -200% 0;
        }

        100% {
            background-position: 200% 0;
        }
    }

    .skeleton {
        background: linear-gradient(90deg,
                #f0f0f0 0%,
                #e0e0e0 20%,
                #f0f0f0 40%,
                #f0f0f0 100%);
        background-size: 200% 100%;
        animation: skeleton-loading 1.5s ease-in-out infinite;
        border-radius: var(--border-radius-md);
    }

    .skeleton-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1.5rem;
    }

    .skeleton-factor-card {
        background: white;
        border-radius: var(--border-radius-lg);
        overflow: hidden;
        box-shadow: var(--shadow-md);
    }

    .skeleton-header {
        height: 50px;
        background: linear-gradient(90deg, #141863 0%, #1f2aa8 100%);
    }

    .skeleton-body {
        padding: 1.25rem;
    }

    .skeleton-text {
        height: 14px;
        margin-bottom: 10px;
        border-radius: 4px;
    }

    .skeleton-text.title {
        height: 20px;
        width: 80%;
        margin-bottom: 15px;
    }

    .skeleton-text.small {
        height: 12px;
        width: 60%;
    }

    .skeleton-badge {
        height: 60px;
        margin: 15px 0;
        border-radius: var(--border-radius-md);
    }

    /* Hide/show skeleton and real content */
    .factors-grid-container.loading .factors-grid {
        display: none;
    }

    .factors-grid-container.loading .skeleton-grid {
        display: grid;
    }

    .factors-grid-container:not(.loading) .skeleton-grid {
        display: none;
    }

    /* FACTORS GRID LAYOUT */
    .factors-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .factor-grid-card {
        background: white;
        border-radius: var(--border-radius-lg);
        overflow: hidden;
        box-shadow: var(--shadow-md);
        transition: transform var(--transition-normal), box-shadow var(--transition-normal);
        display: flex;
        flex-direction: column;
        position: relative;
    }

    .factor-grid-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--primary-gradient);
        transition: height var(--transition-fast);
    }

    .factor-grid-card.approved-card::before {
        background: var(--success-gradient);
    }

    .factor-grid-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
    }

    .factor-grid-card:hover::before {
        height: 6px;
    }

    .factor-card-header {
        background: var(--primary-gradient);
        color: white;
        padding: 0.75rem 1.25rem;
        text-align: center;
    }

    .factor-grid-card.approved-card .factor-card-header {
        background: var(--success-gradient);
    }

    .factor-number {
        font-weight: 700;
        font-size: 1rem;
        margin: 0;
    }

    .factor-card-body {
        padding: 1.25rem;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    .factor-title {
        font-size: 1rem;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 1rem;
        line-height: 1.4;
        min-height: 2.8em;
    }

    .factor-info {
        margin-bottom: 1rem;
    }

    .bank-name {
        color: #141863;
        font-weight: 600;
        font-size: 0.85rem;
        margin-bottom: 0.25rem;
    }

    .periode-text {
        color: #6c757d;
        font-size: 0.75rem;
    }

    .factor-nilai-badge {
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        border: 2px solid #e9ecef;
        border-radius: var(--border-radius-md);
        padding: 1rem;
        text-align: center;
        margin: 1rem 0;
    }

    .nilai-label {
        font-size: 0.8rem;
        color: #495057;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .nilai-value {
        font-size: 2rem;
        font-weight: 800;
        line-height: 1;
    }

    .nilai-value.nilai-success {
        color: #38ef7d;
    }

    .nilai-value.nilai-danger {
        color: #ff6b6b;
    }

    .approval-badges {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        margin-top: auto;
    }

    .approval-badge {
        font-size: 0.75rem;
        padding: 0.5rem 0.75rem;
        border-radius: var(--border-radius-sm);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 600;
    }

    .approval-badge.approved {
        background: rgba(56, 239, 125, 0.15);
        color: #11998e;
    }

    .approval-badge.pending {
        background: rgba(255, 107, 107, 0.15);
        color: #e74c3c;
    }

    /* FORMS AND CARDS */
    .kesimpulan-card,
    .upload-card,
    .cover-card {
        border: none;
        border-radius: var(--border-radius-lg);
        background: white;
        box-shadow: var(--shadow-md);
        margin-bottom: 1.5rem;
    }

    .kesimpulan-card .card-body,
    .upload-card .card-body,
    .cover-card .card-body {
        padding: 1.5rem;
    }

    .kesimpulan-card h5,
    .upload-card h4,
    .cover-card h4 {
        color: #2c3e50;
        font-weight: 700;
        font-size: 1.3rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .kesimpulan-card h5::before {
        content: '';
        width: 4px;
        height: 25px;
        background: var(--primary-gradient);
        border-radius: 4px;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-group label {
        color: #495057;
        font-weight: 600;
        margin-bottom: 0.5rem;
        margin-top: 1rem;
        display: block;
        font-size: 0.95rem;
    }

    .form-group label:first-of-type {
        margin-top: 0;
    }

    .form-control {
        border: 2px solid #e9ecef;
        border-radius: var(--border-radius-md);
        padding: 0.75rem 1rem;
        font-size: 0.95rem;
        transition: border-color var(--transition-fast);
        width: 100%;
    }

    .form-control:focus {
        border-color: #4A90E2;
        outline: none;
        box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
    }

    textarea.form-control {
        min-height: 100px;
        resize: vertical;
    }

    .btn-success {
        background: var(--success-gradient);
        border: none;
        border-radius: var(--border-radius-xl);
        padding: 0.75rem 2rem;
        font-size: 1rem;
        font-weight: 600;
        color: white;
        box-shadow: var(--shadow-sm);
        transition: all var(--transition-normal);
        cursor: pointer;
        margin: 1rem 0.5rem 0.5rem;
    }

    .btn-success:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .btn-success:active {
        transform: translateY(0);
    }

    input[type="file"].form-control {
        border: 2px dashed #4A90E2;
        background: #E3F2FD;
        cursor: pointer;
        padding: 1rem;
        height: calc(2.9em + .85rem + 1px);
    }

    input[type="file"].form-control:hover {
        border-color: #0066CC;
        background: #BBDEFB;
    }

    /* Cover Grid */
    .cover-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 1rem;
        margin: 1.5rem 0;
    }

    .cover-option {
        position: relative;
        border-radius: var(--border-radius-md);
        overflow: hidden;
        transition: all var(--transition-normal);
        cursor: pointer;
        border: 3px solid transparent;
    }

    .cover-option:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-md);
        border-color: #4A90E2;
    }

    .cover-option.active {
        border-color: #38ef7d;
        box-shadow: var(--shadow-lg);
    }

    .cover-option img {
        width: 100%;
        height: auto;
        display: block;
    }

    /* Alerts */
    .alert {
        border: none;
        border-radius: var(--border-radius-md);
        padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow-sm);
    }

    .alert-success {
        background: var(--success-gradient);
        color: white;
    }

    .alert-warning {
        background: linear-gradient(135deg, #FFC107 0%, #FF9800 100%);
        color: white;
        font-weight: 500;
    }

    .alert-danger {
        background: var(--danger-gradient);
        color: white;
    }

    /* Generate Section */
    .generate-section {
        text-align: center;
        margin: 2rem 0 1.5rem;
    }

    .generate-section .text-secondary {
        display: block;
        margin-bottom: 1rem;
        color: #6c757d;
        font-size: 0.95rem;
    }

    .btn-outline-info {
        background: var(--primary-gradient);
        border: none;
        color: white;
        padding: 1rem 2.5rem;
        font-size: 1.1rem;
        font-weight: 700;
        border-radius: var(--border-radius-xl);
        box-shadow: var(--shadow-md);
        transition: all var(--transition-normal);
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-outline-info:hover:not(.disabled) {
        transform: translateY(-3px);
        box-shadow: var(--shadow-lg);
    }

    .btn-outline-info.disabled {
        background: #95a5a6;
        cursor: not-allowed;
        opacity: 0.6;
        box-shadow: none;
    }

    .btn-link {
        color: #4A90E2;
        font-weight: 500;
        text-decoration: none;
        transition: color var(--transition-fast);
        font-size: 0.9rem;
    }

    .btn-link:hover {
        color: #0066CC;
        text-decoration: underline;
    }

    /* Utility Classes */
    .text-center {
        text-align: center;
    }

    .d-flex {
        display: flex;
    }

    .justify-content-center {
        justify-content: center;
    }

    .mt-2 {
        margin-top: 0.5rem;
    }

    .mt-3 {
        margin-top: 1rem;
    }

    .mb-4 {
        margin-bottom: 1.5rem;
    }

    /* Lazy load images */
    img[loading="lazy"] {
        opacity: 0;
        transition: opacity 0.3s;
    }

    img[loading="lazy"].loaded {
        opacity: 1;
    }

    btn-outline-secondary {
        border: 2px solid #6b7280;
        color: #6b7280;
    }

    .btn-outline-secondary:hover {
        background-color: #6b7280;
        color: white;
    }

    /* Tablet (768px and below) */
    @media (max-width: 768px) {
        .container-fluid {
            padding: 1rem 0.75rem;
        }

        .factors-grid {
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
        }

        .peringkat-badge {
            padding: 1.25rem 2rem;
        }

        .peringkat-value {
            font-size: 2rem;
        }

        .section-title {
            font-size: 1.1rem;
        }
    }

    /* Mobile (576px and below) */
    @media (max-width: 576px) {
        .container-fluid {
            padding: 0.75rem 0.5rem;
        }

        .factors-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .peringkat-badge {
            padding: 1rem 1.5rem;
            width: 100%;
        }

        .peringkat-value {
            font-size: 1.75rem;
        }

        .factor-title {
            font-size: 0.95rem;
        }

        .nilai-value {
            font-size: 1.75rem;
        }

        .cover-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .btn-success {
            width: 100%;
            margin: 1rem 0;
        }

        .btn-outline-info {
            padding: 0.875rem 2rem;
            font-size: 1rem;
        }
    }

    /* Extra small devices (400px and below) */
    @media (max-width: 400px) {
        .h3.mb-4 {
            font-size: 1.1rem;
        }

        .section-title {
            font-size: 1rem;
        }

        .factor-card-body {
            padding: 1rem;
        }

        .approval-badge {
            font-size: 0.7rem;
            padding: 0.4rem 0.6rem;
        }
    }

    /* Reduced motion */
    @media (prefers-reduced-motion: reduce) {

        *,
        *::before,
        *::after {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
        }
    }

    /* GPU acceleration */
    .factor-grid-card,
    .btn-success,
    .btn-outline-info {
        will-change: transform;
        backface-visibility: hidden;
        transform: translateZ(0);
    }
</style>