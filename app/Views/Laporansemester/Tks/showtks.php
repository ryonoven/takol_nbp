<?php
function getKompositColor($nilai)
{
    if ($nilai >= 1.00 && $nilai <= 1.50)
        return '#5bc0de';
    if ($nilai >= 1.51 && $nilai <= 2.60)
        return '#28a745';
    if ($nilai >= 2.61 && $nilai <= 3.40)
        return '#ffc107';
    if ($nilai >= 3.41 && $nilai <= 4.20)
        return '#fd7e14';
    if ($nilai >= 4.21 && $nilai <= 5.00)
        return '#dc3545';
    return '#6c757d';
}

function getBadgeColor($nilai)
{
    return [1 => 'badge-primary', 2 => 'badge-success', 3 => 'badge-warning', 4 => 'badge-orange', 5 => 'badge-danger'][$nilai] ?? 'badge-secondary';
}
?>

<div class="alert beautiful-alert my-4">
    <i class="fas fa-info-circle alert-icon"></i>
    <?php if (isset($bprData) && isset($periodeDetail)): ?>
        <strong><?= esc($bprData['namabpr'] ?? 'Nama BPR') ?></strong> - Periode Pelaporan Semester
        <?= esc($periodeDetail['semester']) ?> Tahun <?= esc($periodeDetail['tahun']) ?>
    <?php elseif (isset($periodeDetail)): ?>
        <strong>Periode:</strong> Semester <?= esc($periodeDetail['semester']) ?> Tahun <?= esc($periodeDetail['tahun']) ?>
    <?php else: ?>
        <strong>Periode belum ditentukan</strong>
    <?php endif; ?>
</div>

<?php if ($msg = session()->getFlashdata('message')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">&times;</button>
        <strong><?= esc($msg) ?></strong>
    </div>
<?php endif; ?>

<?php if ($err = session()->getFlashdata('err')): ?>
    <div class="alert alert-danger" role="alert"><?= esc($err) ?></div>
<?php endif; ?>

<a href="<?= base_url('Showsemesteran') ?>" class="btn-kembali mb-3">← Kembali ke Laporan Semesteran</a>

<div class="container-fluid">
    <div class="card">
        <div class="container-fluid">
            <h1 class="h3 mt-5 mb-4 text-gray-800 text-center"><b><?= $judul ?></b></h1>
            <?php if (isset($kompositData) && $kompositData['nkomposittks'] !== null): ?>
                <div class="row justify-content-center">
                    <div class="col-12">
                        <div class="d-flex justify-content-center gap-4 flex-wrap">
                            <div class="card shadow-lg text-center border-0 mb-3"
                                style="max-width:25rem;width:100%;background-color:<?= getKompositColor($kompositData['nkomposittks']) ?>">
                                <div class="card-header bg-white fw-bold">Nilai Komposit TKS (Saat Ini)</div>
                                <div class="card-body">
                                    <h2 class="card-title">Peringkat <?= $kompositData['peringkatkomposittks'] ?>
                                    </h2>
                                    <p class="card-text mb-0">Nilai:
                                        <?= number_format($kompositData['nkomposittks'], 2) ?>
                                    </p>
                                </div>
                            </div>

                            <?php if ($kompositData['nkomposittksbefore'] !== null): ?>
                                <div class="card shadow-lg text-center border-0 mb-3 ml-4"
                                    style="max-width:25rem;width:100%;background-color:<?= getKompositColor($kompositData['nkomposittksbefore']) ?>">
                                    <div class="card-header bg-white fw-bold">Nilai Komposit TKS (Sebelumnya)</div>
                                    <div class="card-body">
                                        <h2 class="card-title">Peringkat
                                            <?= $kompositData['peringkatkomposittksbefore'] ?>
                                        </h2>
                                        <p class="card-text mb-0">Nilai:
                                            <?= number_format($kompositData['nkomposittksbefore'], 2) ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="row">
                <?php if (!empty($faktorTKSData)): ?>
                    <?php foreach ($faktorTKSData as $faktor): ?>
                        <div class="col-md-6 mb-4">
                            <a href="<?= esc($faktor['url'] ?? '#') ?>" class="card-link-wrapper"
                                style="text-decoration:none;color:inherit;" tabindex="0">
                                <div class="card h-100 shadow-sm faktor-tks-card clickable-card">
                                    <div class="card-header bg-gradient-primary text-white">
                                        <h5 class="mb-0 d-flex justify-content-between align-items-center">
                                            <span><i class="fas fa-chart-bar mr-2"></i><?= esc($faktor['nama_faktor']) ?></span>
                                            <i class="fas fa-arrow-right card-arrow"></i>
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($faktor['nilai_peringkat_before'] !== null): ?>
                                            <div class="mb-3 p-3 bg-light rounded border">
                                                <div class="row align-items-center">
                                                    <div class="col-md-6">
                                                        <label class="text-muted mb-1">Nilai Peringkat (Saat ini):</label>
                                                        <p class="mb-0 font-weight-bold text-dark">
                                                            <?= esc($faktor['peringkat_label']) ?>
                                                        </p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="text-muted mb-1">Nilai Peringkat (Sebelumnya):</label>
                                                        <h4 class="mb-0">
                                                            <span
                                                                class="badge <?= getBadgeColor($faktor['nilai_peringkat_before']) ?>">
                                                                <?= esc($faktor['nilai_peringkat_before']) ?>
                                                            </span>
                                                        </h4>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <div class="info-box">
                                                    <label class="text-muted small">Nilai Faktor (Saat Ini):</label>
                                                    <p class="mb-0 font-weight-bold text-primary">
                                                        <?= $faktor['nilai_faktor'] !== null ? number_format($faktor['nilai_faktor'], 2) : 'N/A' ?>
                                                    </p>
                                                </div>
                                            </div>
                                            <?php if ($faktor['nilai_faktor_before'] !== null): ?>
                                                <div class="col-md-6">
                                                    <div class="info-box">
                                                        <label class="text-muted small">Nilai Faktor (Sebelumnya):</label>
                                                        <p class="mb-0 font-weight-bold">
                                                            <?= number_format($faktor['nilai_faktor_before'], 2) ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="mt-3 pt-3 border-top">
                                            <?php if ($faktor['is_approved'] == 1): ?>
                                                <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Telah
                                                    Disetujui Direktur Utama</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning"><i class="fas fa-clock mr-1"></i>Belum
                                                    Disetujui Direktur Utama</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="card-hover-overlay">
                                        <i class="fas fa-external-link-alt"></i>
                                        <span>Lihat laporan</span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle mr-2"></i>
                            Data faktor TKS belum tersedia. Silakan lengkapi penilaian pada masing-masing faktor.
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($showtks)): ?>
                    <div class="col-12 mt-4 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-edit"></i> Kesimpulan/Analisis Tingkat Kesehatan
                                    Bank (TKS)</h5>
                            </div>
                            <div class="card-body">
                                <div id="alert-kesimpulan" style="display:none;">
                                    <div class="alert alert-dismissible fade show" role="alert">
                                        <span id="alert-kesimpulan-message"></span>
                                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                                    </div>
                                </div>

                                <form id="formKesimpulan" autocomplete="off">
                                    <input type="hidden" name="id" value="<?= esc($showtks['id']) ?>">
                                    <div class="form-group">
                                        <label for="kesimpulan">
                                            Kesimpulan dan Analisis Tingkat Kesehatan Bank (TKS)
                                            <span class="text-danger">*</span>
                                            <span class="save-indicator" id="indicator-kesimpulan" style="display:none;">
                                                <i class="fas fa-spinner fa-spin text-primary"></i>
                                            </span>
                                        </label>
                                        <textarea class="form-control" id="kesimpulan" name="kesimpulan" rows="8"
                                            placeholder="Tuliskan kesimpulan analisis Tingkat Kesehatan Bank (TKS) secara keseluruhan..."><?= esc($showtks['kesimpulan'] ?? '') ?></textarea>
                                        <small class="form-text text-muted">
                                            <i class="fas fa-info-circle"></i>
                                            Isi dengan analisis komprehensif mengenai Tingkat Kesehatan Bank (TKS)
                                            BPR berdasarkan penilaian seluruh jenis risiko
                                        </small>
                                        <small class="text-success" id="status-kesimpulan" style="display:none;">
                                            <i class="fas fa-check-circle"></i> Tersimpan otomatis
                                        </small>
                                    </div>
                                    <!-- <div class="d-flex justify-content-between align-items-center mt-3">
                                        <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Simpan Kesimpulan</button>
                                    </div> -->
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Lembar Persetujuan (dipindah ke bawah Kesimpulan) -->
                <div class="col-12 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-signature mr-1"></i> Lembar Persetujuan</h5>
                        </div>
                        <div class="card-body">
                            <div id="alert-container" style="display:none;">
                                <div class="alert alert-dismissible fade show" role="alert">
                                    <span id="alert-message"></span>
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                </div>
                            </div>

                            <form id="formTtd" autocomplete="off">
                                <input type="hidden" id="record_id" name="id" value="<?= esc($showtks['id'] ?? '') ?>">

                                <?php
                                $ttdFields = [
                                    'dirut' => ['label' => 'Nama Direktur Utama', 'value' => $showtks['dirut'] ?? ''],
                                    'dirkep' => ['label' => 'Nama Direktur yang membawahi fungsi kepatuhan', 'value' => $showtks['dirkep'] ?? ''],
                                    'pe' => ['label' => 'Nama Pejabat Eksekutif Manajemen Risiko', 'value' => $showtks['pe'] ?? ''],
                                    'lokasi' => ['label' => 'Lokasi', 'value' => $showtks['lokasi'] ?? ''],
                                ];
                                foreach ($ttdFields as $fieldKey => $fieldMeta): ?>
                                    <div class="form-group">
                                        <label for="<?= $fieldKey ?>">
                                            <?= $fieldMeta['label'] ?> <span class="text-danger">*</span>
                                            <span class="save-indicator" id="indicator-<?= $fieldKey ?>"
                                                style="display:none;">
                                                <i class="fas fa-spinner fa-spin text-primary"></i>
                                            </span>
                                        </label>
                                        <input type="text" class="form-control auto-save" id="<?= $fieldKey ?>"
                                            name="<?= $fieldKey ?>" data-field="<?= $fieldKey ?>"
                                            value="<?= esc($fieldMeta['value']) ?>" required>
                                        <small class="text-success" id="status-<?= $fieldKey ?>" style="display:none;">
                                            <i class="fas fa-check-circle"></i> Tersimpan
                                        </small>
                                    </div>
                                <?php endforeach; ?>

                                <!-- kalender -->
                                <div class="form-group">
                                    <label for="tanggal">
                                        Tanggal Pelaporan <span class="text-danger">*</span>
                                        <span class="save-indicator" id="indicator-tanggal" style="display:none;">
                                            <i class="fas fa-spinner fa-spin text-primary"></i>
                                        </span>
                                    </label>
                                    <input type="text" class="form-control auto-save" id="tanggal" name="tanggal"
                                        data-field="tanggal" placeholder="dd/mm/yyyy"
                                        value="<?= esc($showtks['tanggal_display'] ?? '') ?>" required>
                                    <small class="text-success" id="status-tanggal" style="display:none;">
                                        <i class="fas fa-check-circle"></i> Tersimpan
                                    </small>
                                </div>

                                <div class="d-flex">
                                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Simpan
                                        Semua Data</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-12 text-center mt-2">
                    <span class="text-secondary fw-semibold">
                        Cek kembali seluruh data, pastikan informasi BPR dan data yang diperlukan telah terisi
                        dengan benar
                    </span>
                </div>

                <?php
                $allFactorsApproved = true;
                $unapprovedFactors = [];
                foreach ($faktorTKSData as $faktor) {
                    if (empty($faktor['is_approved']) || $faktor['is_approved'] != 1) {
                        $allFactorsApproved = false;
                        preg_match('/Faktor (\d+)/', $faktor['nama_faktor'], $m);
                        $unapprovedFactors[] = isset($m[1]) ? 'Faktor ' . $m[1] : $faktor['nama_faktor'];
                    }
                }

                $kesimpulanFilled = !empty($showtks['kesimpulan']);

                $missingTtd = [];
                if (empty($showtks['dirut']))
                    $missingTtd[] = 'Nama Direktur Utama';
                if (empty($showtks['dirkep']))
                    $missingTtd[] = 'Nama Direktur yang membawahi fungsi kepatuhan';
                if (empty($showtks['pe']))
                    $missingTtd[] = 'Nama Pejabat Eksekutif Manajemen Risiko';
                if (empty($showtks['tanggal']))
                    $missingTtd[] = 'Tanggal Pelaporan';
                if (empty($showtks['lokasi']))
                    $missingTtd[] = 'Lokasi';
                $ttdFieldsFilled = empty($missingTtd);

                $disableGenerateButton = !($allFactorsApproved && $kesimpulanFilled && $ttdFieldsFilled);

                // List warning
                $warningItems = [];
                foreach ($unapprovedFactors as $namaFaktor) {
                    $warningItems[] = 'Faktor belum disetujui: <strong>' . esc($namaFaktor) . '</strong>';
                }
                if (!$kesimpulanFilled) {
                    $warningItems[] = '<strong>Kesimpulan/Analisis Tingkat Kesehatan Bank (TKS) belum terisi</strong>';
                }
                foreach ($missingTtd as $namaField) {
                    $warningItems[] = 'Lembar Persetujuan belum lengkap: <strong>' . esc($namaField) . '</strong>';
                }
                ?>

                <div class="col-12 mt-1 mb-3">
                    <div class="row justify-content-center">
                        <div class="col-12 col-md-10 col-lg-8">
                            <?php if (!empty($warningItems)): ?>
                                <div class="alert-warning-box">
                                    <div class="alert-warning-title"><i>⚠</i> Data belum lengkap</div>
                                    <ul>
                                        <?php foreach ($warningItems as $item): ?>
                                            <li><?= $item ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php else: ?>
                                <div class="alert-success-box">
                                    <i>✓</i> Seluruh data telah lengkap dan disetujui. Laporan siap untuk di-generate.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-12 text-center mt-1 mb-3">
                    <?php if ($disableGenerateButton): ?>
                        <a href="#" class="btn btn-primary disabled" aria-disabled="true"
                            title="Pastikan seluruh faktor disetujui, kesimpulan terisi, dan Lembar Persetujuan lengkap">
                            Cetak PDF Laporan TKS
                        </a>
                    <?php else: ?>
                        <a href="<?= base_url('Showtks/LaporanPDFTKS') ?>" target="_blank" class="btn btn-primary">
                            Cetak PDF Laporan TKS
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" media="print"
    onload="this.media='all'">
<script src="https://cdn.jsdelivr.net/npm/flatpickr" defer></script>

<script>
    (function () {
        'use strict';
        var SAVE_DELAY = 3000;
        var KESIMPULAN_SAVE_DELAY = 5000;

        var BASE_URL_AUTO_SAVE = '<?= base_url("Showtks/ajaxAutoSaveField") ?>';
        var BASE_URL_SAVE_KESIMPULAN = '<?= base_url("Showtks/ajaxSaveKesimpulan") ?>';
        var BASE_URL_UPDATE_TTD = '<?= base_url("Showtks/ajaxUpdateTtd") ?>';

        function showAlertIn(containerId, msgSpanId, type, message) {
            var container = document.getElementById(containerId);
            var msgSpan = document.getElementById(msgSpanId);
            if (!container || !msgSpan) return;

            var alertDiv = container.querySelector('.alert');
            if (!alertDiv) return;

            var cls = type === 'success' ? 'alert-success' : type === 'warning' ? 'alert-warning' : 'alert-danger';
            var icon = type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'exclamation-circle';

            alertDiv.className = 'alert alert-dismissible fade show ' + cls;
            msgSpan.innerHTML = '<i class="fas fa-' + icon + '"></i> ' + message;
            container.style.display = 'block';

            clearTimeout(container._hideTimer);
            container._hideTimer = setTimeout(function () { container.style.display = 'none'; }, 5000);
        }

        window.showAlert = function (type, msg) {
            showAlertIn('alert-container', 'alert-message', type, msg);
        };
        window.showAlertKesimpulan = function (type, msg) {
            showAlertIn('alert-kesimpulan', 'alert-kesimpulan-message', type, msg);
        };

        function showIndicator(field) {
            var el = document.getElementById('indicator-' + field);
            var st = document.getElementById('status-' + field);
            if (el) el.style.display = 'inline-block';
            if (st) st.style.display = 'none';
        }
        function hideIndicator(field, success) {
            var el = document.getElementById('indicator-' + field);
            var st = document.getElementById('status-' + field);
            if (el) el.style.display = 'none';
            if (success && st) {
                st.style.display = 'inline-block';
                setTimeout(function () { st.style.display = 'none'; }, 3000);
            }
        }

        function ajaxPost(url, data, onSuccess, onError) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', url, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.timeout = 10000;

            xhr.onload = function () {
                try {
                    var json = JSON.parse(xhr.responseText);
                    onSuccess(json);
                } catch (e) {
                    if (onError) onError(e);
                }
            };
            xhr.onerror = function () { if (onError) onError(new Error('Network error')); };
            xhr.ontimeout = function () { if (onError) onError(new Error('Timeout')); };

            var body = Object.keys(data).map(function (k) {
                return encodeURIComponent(k) + '=' + encodeURIComponent(data[k]);
            }).join('&');

            xhr.send(body);
        }

        function autoSaveField(id, field, value) {
            if (!id) { window.showAlert('danger', 'ID record tidak ditemukan'); return; }
            showIndicator(field);

            ajaxPost(BASE_URL_AUTO_SAVE, { id: id, field: field, value: value },
                function (res) { hideIndicator(field, res.success); if (!res.success) window.showAlert('danger', res.message || 'Gagal menyimpan data'); },
                function () { hideIndicator(field, false); window.showAlert('danger', 'Terjadi kesalahan saat menyimpan data'); }
            );
        }

        function autoSaveKesimpulan(id, value) {
            if (!id) { window.showAlertKesimpulan('danger', 'ID record tidak ditemukan'); return; }
            showIndicator('kesimpulan');

            ajaxPost(BASE_URL_SAVE_KESIMPULAN, { id: id, kesimpulan: value },
                function (res) { hideIndicator('kesimpulan', res.success); if (!res.success) window.showAlertKesimpulan('danger', res.message || 'Gagal menyimpan kesimpulan'); },
                function () { hideIndicator('kesimpulan', false); window.showAlertKesimpulan('danger', 'Terjadi kesalahan saat menyimpan kesimpulan'); }
            );
        }

        function init() {

            var recordIdEl = document.getElementById('record_id');
            var autoSaveEls = document.querySelectorAll('.auto-save');
            var kesimpulanEl = document.getElementById('kesimpulan');
            var formTtd = document.getElementById('formTtd');
            var formKes = document.getElementById('formKesimpulan');

            var saveTimer = null;
            var kesTimer = null;

            // tanggal kalender
            var tanggalEl = document.getElementById('tanggal');
            function tryInitFlatpickr() {
                if (typeof flatpickr === 'undefined') { setTimeout(tryInitFlatpickr, 100); return; }
                flatpickr(tanggalEl, {
                    dateFormat: 'd/m/Y',
                    allowInput: true,
                    onChange: function (_, dateStr) {
                        var id = recordIdEl && recordIdEl.value;
                        if (!id) return;
                        clearTimeout(saveTimer);
                        showIndicator('tanggal');
                        saveTimer = setTimeout(function () { autoSaveField(id, 'tanggal', dateStr); }, SAVE_DELAY);
                    }
                });
            }
            if (tanggalEl) tryInitFlatpickr();

            if (formTtd) {
                formTtd.addEventListener('input', function (e) {
                    var el = e.target;
                    if (!el.classList.contains('auto-save') || el.id === 'tanggal') return;
                    var field = el.dataset.field;
                    var id = recordIdEl && recordIdEl.value;
                    if (!field || !id) return;

                    clearTimeout(saveTimer);
                    showIndicator(field);
                    saveTimer = setTimeout(function () { autoSaveField(id, field, el.value); }, SAVE_DELAY);
                });

                formTtd.addEventListener('input', function (e) {
                    if (e.target.classList.contains('is-invalid')) e.target.classList.remove('is-invalid');
                });

                formTtd.addEventListener('submit', function (e) {
                    e.preventDefault();

                    var id = recordIdEl ? recordIdEl.value : '';
                    var emptyFs = [];
                    var valid = true;

                    autoSaveEls.forEach(function (f) {
                        if (f.required && !f.value.trim()) {
                            valid = false;
                            f.classList.add('is-invalid');
                            var lbl = f.previousElementSibling;
                            emptyFs.push(lbl ? lbl.textContent.replace('*', '').trim() : f.name);
                        } else {
                            f.classList.remove('is-invalid');
                        }
                    });

                    if (!valid) { window.showAlert('danger', 'Mohon lengkapi field berikut: ' + emptyFs.join(', ')); return; }

                    var btn = formTtd.querySelector('button[type="submit"]');
                    var orig = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
                    btn.disabled = true;

                    var payload = { id: id };
                    autoSaveEls.forEach(function (f) { payload[f.name] = f.value; });

                    ajaxPost(BASE_URL_UPDATE_TTD, payload,
                        function (res) {
                            btn.innerHTML = orig; btn.disabled = false;
                            if (res.success) {
                                window.showAlert('success', res.message || 'Semua data berhasil disimpan');
                                document.querySelectorAll('[id^="status-"]').forEach(function (el) {
                                    el.style.display = 'inline-block';
                                    setTimeout(function () { el.style.display = 'none'; }, 5000);
                                });
                            } else {
                                window.showAlert('danger', res.message || 'Gagal menyimpan data');
                            }
                        },
                        function () {
                            btn.innerHTML = orig; btn.disabled = false;
                            window.showAlert('danger', 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.');
                        }
                    );
                });
            }

            if (kesimpulanEl) {
                kesimpulanEl.addEventListener('input', function () {
                    this.classList.remove('is-invalid');
                    var id = (document.querySelector('#formKesimpulan input[name="id"]') || {}).value;
                    if (!id) return;
                    clearTimeout(kesTimer);
                    showIndicator('kesimpulan');
                    var val = this.value;
                    kesTimer = setTimeout(function () { autoSaveKesimpulan(id, val); }, KESIMPULAN_SAVE_DELAY);
                });
            }

            if (formKes) {
                formKes.addEventListener('submit', function (e) {
                    e.preventDefault();
                    var id = (formKes.querySelector('input[name="id"]') || {}).value;
                    var val = kesimpulanEl ? kesimpulanEl.value : '';

                    if (!val.trim()) {
                        window.showAlertKesimpulan('warning', 'Mohon isi kesimpulan terlebih dahulu');
                        if (kesimpulanEl) kesimpulanEl.classList.add('is-invalid');
                        return;
                    }
                    if (kesimpulanEl) kesimpulanEl.classList.remove('is-invalid');

                    var btn = formKes.querySelector('button[type="submit"]');
                    var orig = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
                    btn.disabled = true;

                    ajaxPost(BASE_URL_SAVE_KESIMPULAN, { id: id, kesimpulan: val },
                        function (res) {
                            btn.innerHTML = orig; btn.disabled = false;
                            if (res.success) {
                                window.showAlertKesimpulan('success', res.message || 'Kesimpulan berhasil disimpan');
                                hideIndicator('kesimpulan', true);
                            } else {
                                window.showAlertKesimpulan('danger', res.message || 'Gagal menyimpan kesimpulan');
                            }
                        },
                        function () {
                            btn.innerHTML = orig; btn.disabled = false;
                            window.showAlertKesimpulan('danger', 'Terjadi kesalahan saat menyimpan kesimpulan. Silakan coba lagi.');
                        }
                    );
                });
            }

            var cards = document.querySelectorAll('.faktor-tks-card');
            cards.forEach(function (card, i) {
                card.style.setProperty('--card-delay', (i * 0.1) + 's');

                card.addEventListener('click', function (ev) {
                    var ripple = document.createElement('div');
                    var rect = card.getBoundingClientRect();
                    ripple.style.cssText = [
                        'position:absolute;border-radius:50%;background:rgba(255,255,255,.6)',
                        'width:100px;height:100px;margin-left:-50px;margin-top:-50px',
                        'animation:ripple .6s;pointer-events:none',
                        'left:' + (ev.clientX - rect.left) + 'px',
                        'top:' + (ev.clientY - rect.top) + 'px'
                    ].join(';');
                    card.appendChild(ripple);
                    setTimeout(function () { ripple.remove(); }, 600);
                });
            });

            document.querySelectorAll('[id^="alert-"] .close, #alert-container .close').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var container = this.closest('[id^="alert-"]') || document.getElementById('alert-container');
                    if (container) container.style.display = 'none';
                });
            });

            var btnExport = document.getElementById('x');
            if (btnExport) {
                btnExport.addEventListener('click', function () {
                    var btn = this;
                    var orig = btn.innerHTML;
                    btn.disabled = true;
                    btn.classList.add('processing');
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';

                    var win = window.open('<?= base_url("Showtks/exportAllPDFToZipView") ?>',
                        'ExportPDFWindow', 'width=800,height=600,scrollbars=yes,resizable=yes');

                    if (!win || win.closed) {
                        alert('Pop-up blocker terdeteksi!\n\nSilakan izinkan pop-up untuk website ini dan coba lagi.');
                    }
                    setTimeout(function () {
                        btn.disabled = false; btn.classList.remove('processing'); btn.innerHTML = orig;
                    }, 3000);
                });
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    })();
</script>

<style>
    :root {
        --gray-200: #e2e8f0;
        --amber: #f59e0b;
        --green: #10b981;
        --radius: 12px;
    }

    .alert-warning-box {
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-left: 4px solid var(--amber);
        padding: 14px 18px;
        border-radius: var(--radius);
        margin-bottom: 18px;
        font-size: .85rem;
        color: #78350f;
    }

    .alert-warning-title {
        font-weight: 700;
        color: #92400e;
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 6px;
    }

    .alert-warning-box ul {
        margin: 0;
        padding-left: 20px;
    }

    .alert-warning-box li {
        margin-bottom: 4px;
        line-height: 1.5;
    }

    .alert-success-box {
        background: #d1fae5;
        border: 1px solid #a7f3d0;
        border-left: 4px solid var(--green);
        padding: 12px 18px;
        border-radius: var(--radius);
        margin-bottom: 18px;
        font-size: .85rem;
        color: #065f46;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Factor card */
    .faktor-tks-card {
        border: none;
        transition: transform .3s ease, box-shadow .3s ease;
        position: relative;
        overflow: hidden;
        animation: cardLoad .5s ease-out both;
        animation-delay: var(--card-delay, 0s);
    }

    .clickable-card {
        cursor: pointer;
    }

    .card-link-wrapper {
        display: block;
        height: 100%;
        text-decoration: none;
        color: inherit;
    }

    .faktor-tks-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 28px rgba(0, 0, 0, .2) !important;
    }

    .faktor-tks-card:active {
        transform: translateY(-6px) scale(.98);
    }

    .faktor-tks-card .card-arrow {
        transition: transform .3s ease;
        opacity: .7;
    }

    .faktor-tks-card:hover .card-arrow {
        transform: translateX(5px);
        opacity: 1;
    }

    /* Hover overlay */
    .card-hover-overlay {
        position: absolute;
        inset: 0;
        background: rgba(78, 115, 223, .95);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        opacity: 0;
        transition: opacity .3s ease;
        pointer-events: none;
        z-index: 10;
    }

    .card-hover-overlay i {
        font-size: 3rem;
        color: #fff;
        margin-bottom: 1rem;
    }

    .card-hover-overlay span {
        color: #fff;
        font-size: 1.2rem;
        font-weight: 600;
    }

    .faktor-tks-card:hover .card-hover-overlay {
        opacity: 1;
    }

    /* Info box */
    .info-box {
        padding: .75rem;
        background: #f8f9fc;
        border-radius: .35rem;
    }

    /* Badge */
    .badge-orange {
        background-color: #fd7e14;
        color: #fff;
    }

    /* Alerts */
    .beautiful-alert {
        background-color: #e0f7fa;
        color: #007bb5;
        border-color: #b2ebf2;
        border-radius: 10px;
        padding: 15px 25px;
        display: flex;
        align-items: center;
        font-size: 1.1rem;
        box-shadow: 0 4px 8px rgba(0, 0, 0, .1);
    }

    .beautiful-alert strong {
        color: #0056b3;
    }

    .beautiful-alert .alert-icon {
        margin-right: 15px;
        font-size: 1.8rem;
        color: #007bff;
    }

    /* Fixed alert toast */
    #alert-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        max-width: 500px;
        animation: slideInRight .3s ease-out;
    }

    #alert-container .alert {
        box-shadow: 0 4px 12px rgba(0, 0, 0, .15);
        border-left: 4px solid;
        max-height: 200px;
        overflow-y: auto;
    }

    #alert-container .alert-success {
        border-left-color: #28a745;
    }

    #alert-container .alert-danger {
        border-left-color: #dc3545;
    }

    #alert-container .alert-warning {
        border-left-color: #ffc107;
    }

    /* Save indicator */
    .save-indicator {
        margin-left: 10px;
        font-size: 14px;
        display: inline-block;
        vertical-align: middle;
    }

    /* Buttons */
    .btn-success {
        background-color: #fff;
        border-color: #28a745;
        border-radius: 25px;
        padding: 12px 30px;
        font-size: 1.1rem;
        font-weight: 600;
        color: #fff;
        margin: 0 15px;
        transition: all .3s ease;
    }

    .btn-success:hover {
        background-color: #28a745;
        border-color: #fff;
        transform: translateY(-2px);
        color: #fff;
    }

    .btn-success.disabled,
    .btn-success:disabled {
        background-color: #6c757d !important;
        border-color: #6c757d !important;
        cursor: not-allowed !important;
        opacity: .65;
        pointer-events: none;
    }

    /* Back button */
    .btn-kembali {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #fff;
        border: 1.5px solid var(--gray-200);
        border-radius: 8px;
        padding: 8px 16px;
        font-size: .9rem;
        font-weight: 600;
        color: var(--gray-600);
        text-decoration: none;
        transition: all .2s;
        margin: 14px 0 0 16px;
    }

    .btn-kembali:hover {
        background: var(--gray-50);
        border-color: var(--gray-400);
        color: var(--gray-800);
    }

    /* Form states */
    .auto-save.is-invalid {
        border-color: #dc3545;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right calc(.375em + .1875rem) center;
        background-size: calc(.75em + .375rem);
    }

    /* Card generic */
    .card {
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, .15);
        transition: all .3s ease-in-out;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, .25);
    }

    .card-title {
        font-size: clamp(1.2rem, 2.5vw, 1.8rem);
    }

    .card-text {
        font-size: clamp(.9rem, 2vw, 1.1rem);
    }

    /* Textarea */
    #formKesimpulan textarea {
        resize: vertical;
        min-height: 150px;
    }

    /* Alert list */
    .alert ul {
        padding-left: 0;
        margin-bottom: 0;
    }

    .alert ul li {
        text-align: left;
        margin-bottom: 8px;
        line-height: 1.6;
        list-style-position: inside;
    }

    .alert hr {
        border-top: 1px solid rgba(0, 0, 0, .1);
        margin: 1rem 0;
    }

    /* Keyboard focus */
    .card-link-wrapper:focus {
        outline: 2px solid #4e73df;
        outline-offset: 2px;
        border-radius: .35rem;
    }

    .card-link-wrapper:focus .faktor-tks-card {
        box-shadow: 0 0 0 3px rgba(78, 115, 223, .3);
    }

    /* Animations */
    @keyframes cardLoad {
        from {
            opacity: 0;
            transform: translateY(30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes ripple {
        from {
            opacity: 1;
            transform: scale(0);
        }

        to {
            opacity: 0;
            transform: scale(4);
        }
    }

    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideInDown {
        from {
            transform: translateY(-20px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .alert {
        animation: slideInDown .4s ease-out;
    }

    /* Responsive */
    @media (max-width: 768px) {
        #alert-container {
            left: 10px;
            right: 10px;
            min-width: auto;
            top: 10px;
        }

        .faktor-tks-card:hover {
            transform: translateY(-5px);
        }

        .card-hover-overlay i {
            font-size: 2rem;
        }

        .card-hover-overlay span {
            font-size: 1rem;
        }
    }

    /* Print */
    @media print {

        #alert-container,
        .save-indicator,
        [id^="status-"],
        button {
            display: none !important;
        }
    }
</style>