<?php if (session()->getFlashdata('message')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">&times;</button>
        <strong><?= esc(session()->getFlashdata('message')); ?></strong>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('err')): ?>
    <div class="alert alert-danger" role="alert"><?= esc(session()->getFlashdata('err')); ?></div>
<?php endif; ?>

<a href="<?= base_url('Showsemesteran') ?>" class="btn-kembali mb-3 ml-4">
    ← Kembali ke halaman Laporan Semesteran
</a>

<div class="container-fluid px-2 px-md-3">
    <div class="card main-card">
        <div class="container-fluid">
            <div class="page-header">
                <h1 class="h3 mt-4 text-gray-800 text-center"><b><?= esc($judul); ?></b></h1>
                <div class="page-subtitle">
                    <?php
                    $semDisplay = esc($periode_semester);
                    $semNum = strtolower($semDisplay) === 'ganjil' ? '1' : (strtolower($semDisplay) === 'genap' ? '2' : $semDisplay);
                    ?>
                    Semester <?= $semNum ?> Tahun <?= esc($periode_tahun); ?>
                </div>
                <span class="badge-namabpr"><?= esc($namabpr); ?></span>
            </div>

            <!-- Transparan Cards -->
            <div class="row mx-0">
                <?php foreach ($transparan as $trans): ?>
                    <div class="col-12 col-sm-6 col-xl-4 mb-3 px-2">
                        <div class="card trans-card shadow-sm h-100">
                            <a href="<?= esc($trans['link']); ?>" class="card-link-wrapper">
                                <div class="card-body py-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="flex-grow-1 mr-2">
                                            <div class="h2 mb-1 font-weight-bold text-gray-800">
                                                <?= esc($trans['name']); ?>
                                            </div>
                                            <hr class="my-1">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                <?= esc($bprData['namabpr'] ?? 'Nama BPR'); ?>
                                            </div>
                                            <div class="text-xs text-muted mb-1">
                                                Semester <?= esc($periodeDetail['semester']) ?> Tahun
                                                <?= esc($periodeDetail['tahun']) ?>
                                            </div>
                                            <?php
                                            $message2 = isset($trans['is_approved'])
                                                ? ($trans['is_approved'] == 1 ? 'Telah disetujui oleh Direktur Utama' : 'Belum disetujui oleh Direktur Utama')
                                                : 'Data tidak diisi';
                                            $statusClass2 = isset($trans['is_approved'])
                                                ? ($trans['is_approved'] == 1 ? 'text-success' : 'text-danger')
                                                : 'text-secondary';
                                            ?>
                                            <p class="<?= $statusClass2; ?> mb-0 text-xs"><?= $message2; ?></p>
                                        </div>
                                        <i class="fas fa-chart-line fa-2x text-gray-300 flex-shrink-0"></i>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Risk Rating Badge -->
            <?php
            $ntotalrisk = $riskCalculations['ntotalrisk'] ?? null;
            $ratings = [
                1 => ['title' => 'Sangat Rendah', 'level' => '(1)', 'color' => '#5bc0de'],
                2 => ['title' => 'Rendah', 'level' => '(2)', 'color' => '#28a745'],
                3 => ['title' => 'Sedang', 'level' => '(3)', 'color' => '#ffc107'],
                4 => ['title' => 'Tinggi', 'level' => '(4)', 'color' => '#fd7e14'],
                5 => ['title' => 'Sangat Tinggi', 'level' => '(5)', 'color' => '#dc3545'],
            ];
            $selectedRating = $ratings[$ntotalrisk] ?? null;
            ?>
            <div class="d-flex justify-content-center mb-3">
                <?php if ($selectedRating): ?>
                    <div class="card shadow text-center border-0 risk-rating-card"
                        style="background-color: <?= esc($selectedRating['color']) ?>;">
                        <div class="card-header bg-white fw-bold" style="color:#000;">Peringkat Profil Risiko</div>
                        <div class="card-body">
                            <h4 class="card-title text-black mb-1"><?= esc($selectedRating['title']) ?></h4>
                            <p class="card-text mb-0 text-black small">Tingkat Risiko:
                                <?= esc($selectedRating['level']) ?>
                            </p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-dark text-center risk-rating-card">Data peringkat risiko belum tersedia.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Update Ntotalrisk Form (collapsed by default) -->
            <?php if (isset($showprofilresiko) && !empty($showprofilresiko) && !empty($showprofilresiko['ntotalrisk'])): ?>
                <div class="d-flex justify-content-center mb-4">
                    <div class="card shadow border-0 risk-update-card">
                        <div class="collapse" id="mainCardBody">
                            <div class="card-body">
                                <form method="post" action="<?= base_url('Showprofilresiko/updateNtotalrisk'); ?>"
                                    id="formUpdateNtotalrisk">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= esc($showprofilresiko['id']); ?>">
                                    <?php
                                    $currentNtotalrisk = $showprofilresiko['ntotalrisk'] ?? 'N/A';
                                    $currentDisplay = getNilaiTextAndColor($currentNtotalrisk);
                                    ?>
                                    <div class="alert alert-info py-2">
                                        <strong>Nilai Saat Ini:</strong>
                                        <span
                                            class="fw-bold <?= $currentDisplay['color']; ?>"><?= $currentDisplay['text']; ?></span>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label for="ntotalrisk" class="fw-bold">Nilai Total Risiko Baru <span
                                                class="text-danger">*</span></label>
                                        <select class="form-control" id="ntotalrisk" name="ntotalrisk" required>
                                            <option value="">-- Pilih Nilai --</option>
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <?php $labels = ['', 'Sangat Rendah', 'Rendah', 'Sedang', 'Tinggi', 'Sangat Tinggi']; ?>
                                                <option value="<?= $i ?>" <?= ($currentNtotalrisk == $i) ? 'selected' : ''; ?>><?= $i ?> - <?= $labels[$i] ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2 justify-content-between mt-3">
                                        <button type="submit" class="btn btn-primary btn-sm"><i
                                                class="fas fa-save mr-1"></i> Update Nilai</button>
                                        <button type="button" class="btn btn-warning btn-sm"
                                            onclick="resetNtotalrisk()"><i class="fas fa-undo mr-1"></i> Reset
                                            Otomatis</button>
                                    </div>
                                </form>
                                <form method="post" action="<?= base_url('Showprofilresiko/resetNtotalrisk'); ?>"
                                    id="formResetNtotalrisk" style="display:none;">
                                    <input type="hidden" name="id" value="<?= esc($showprofilresiko['id']); ?>">
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Factor Cards -->
            <div class="row mx-0">
                <?php
                function getNilaiTextAndColor($value)
                {
                    if ($value === '0' || $value === 'N/A' || $value === '' || $value === null)
                        return ['text' => 'N/A', 'color' => 'text-secondary'];
                    $map = [
                        '1' => ['Sangat Rendah (1)', 'text-primary'],
                        '2' => ['Rendah (2)', 'text-success'],
                        '3' => ['Sedang (3)', 'text-warning'],
                        '4' => ['Tinggi (4)', 'text-orange'],
                        '5' => ['Sangat Tinggi (5)', 'text-danger'],
                    ];
                    return isset($map[$value])
                        ? ['text' => $map[$value][0], 'color' => $map[$value][1]]
                        : ['text' => $value, 'color' => 'text-secondary'];
                }

                function getNilaiLabel($v)
                {
                    $m = ['1' => 'Sangat Rendah (1)', '2' => 'Rendah (2)', '3' => 'Sedang (3)', '4' => 'Tinggi (4)', '5' => 'Sangat Tinggi (5)'];
                    return $m[$v] ?? ($v === '0' || $v === null || $v === '' ? 'N/A' : $v);
                }

                $isKategoriA = isset($kategori) && strtoupper($kategori) === 'A';
                ?>
                <?php foreach ($factors as $factor): ?>
                    <?php
                    $riskType = $factor['risk_type'] ?? '';
                    if (!$isKategoriA && in_array($riskType, ['reputasi', 'stratejik']))
                        continue;

                    $nfVal = esc($factor['nfaktor'] ?? 'N/A');
                    $nfKpmr = esc($factor['nfaktor_kpmr'] ?? 'N/A');
                    $nResult = esc($factor['nkredit'] ?? $factor['noperasional'] ?? $factor['nkepatuhan'] ?? $factor['nlikuiditas'] ?? $factor['nreputasi'] ?? $factor['nstratejik'] ?? 'N/A');

                    $isApproved = isset($factor['is_approved']) && $factor['is_approved'] == 1;
                    ?>
                    <div class="col-12 col-md-6 mb-3 px-2">
                        <div class="card factor-card shadow-sm h-100 <?= $isApproved ? 'approved-card' : '' ?>">
                            <a href="<?= esc($factor['link']); ?>" class="card-link-wrapper">
                                <div class="card-body py-3">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div class="flex-grow-1 mr-2">
                                            <div class="h5 mb-1 font-weight-bold text-gray-800">
                                                <?= esc($factor['name']); ?>
                                            </div>
                                            <hr class="my-1">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                <?= esc($bprData['namabpr'] ?? 'Nama BPR'); ?>
                                            </div>
                                            <div class="text-xs text-muted mb-2">Semester
                                                <?= esc($periodeDetail['semester']) ?> Tahun
                                                <?= esc($periodeDetail['tahun']) ?>
                                            </div>
                                            <div class="risk-detail-row">
                                                <span class="risk-label">Risiko Inheren:</span>
                                                <span
                                                    class="font-weight-bold <?= ($nfVal === '0' || $nfVal === 'N/A') ? 'text-danger' : 'text-success' ?>">
                                                    <?= getNilaiLabel($nfVal) ?>
                                                </span>
                                            </div>
                                            <div class="risk-detail-row">
                                                <span class="risk-label">KPMR:</span>
                                                <span
                                                    class="font-weight-bold <?= ($nfKpmr === '0' || $nfKpmr === 'N/A') ? 'text-danger' : 'text-success' ?>">
                                                    <?= getNilaiLabel($nfKpmr) ?>
                                                </span>
                                            </div>
                                            <div class="risk-detail-row">
                                                <span class="risk-label">Tingkat Risiko:</span>
                                                <span
                                                    class="font-weight-bold <?= ($nResult === '0' || $nResult === 'N/A') ? 'text-danger' : 'text-success' ?>">
                                                    <?= getNilaiLabel($nResult) ?>
                                                </span>
                                            </div>
                                            <p
                                                class="<?= $isApproved ? 'text-success' : 'text-danger' ?> mb-0 text-xs mt-1">
                                                <?= $isApproved ? 'Telah disetujui oleh Direktur Utama' : 'Belum disetujui oleh Direktur Utama' ?>
                                            </p>
                                        </div>
                                        <i class="fas fa-chart-line fa-2x text-gray-300 flex-shrink-0 mt-1"></i>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Kesimpulan Card -->
            <?php if (isset($showprofilresiko) && !empty($showprofilresiko)): ?>
                <div class="row mx-0 mb-4">
                    <div class="col-12 px-2">
                        <div class="card shadow-sm">
                            <div class="card-header bg-primary text-white py-2">
                                <h5 class="mb-0"><i class="fas fa-edit mr-1"></i> Kesimpulan/Analisis Profil Risiko
                                </h5>
                            </div>
                            <div class="card-body">
                                <div id="alert-kesimpulan" class="d-none">
                                    <div class="alert alert-dismissible fade show mb-2" role="alert">
                                        <span id="alert-kesimpulan-message"></span>
                                        <button type="button" class="close py-1"
                                            data-dismiss="alert">&times;</button>
                                    </div>
                                </div>
                                <form id="formKesimpulan" autocomplete="off">
                                    <input type="hidden" name="id" value="<?= esc($showprofilresiko['id']); ?>">
                                    <div class="form-group mb-2">
                                        <label for="kesimpulan" class="font-weight-bold small">
                                            Kesimpulan dan Analisis <span class="text-danger">*</span>
                                            <i class="fas fa-spinner fa-spin text-primary ml-1 d-none"
                                                id="indicator-kesimpulan"></i>
                                            <span class="text-success small d-none" id="status-kesimpulan"><i
                                                    class="fas fa-check-circle"></i> Tersimpan</span>
                                        </label>
                                        <textarea class="form-control" id="kesimpulan" name="kesimpulan" rows="6"
                                            placeholder="Tuliskan kesimpulan analisis profil risiko..."><?= esc($showprofilresiko['kesimpulan'] ?? ''); ?></textarea>
                                    </div>
                                    <!-- <button type="submit" class="btn btn-success btn-sm"><i
                                            class="fas fa-save mr-1"></i> Simpan Kesimpulan</button> -->
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Lembar Persetujuan (dipindah ke bawah Kesimpulan) -->
            <div class="row mx-0">
                <div class="col-12 px-2 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white py-2">
                            <h5 class="mb-0"><i class="fas fa-signature mr-1"></i> Lembar Persetujuan</h5>
                        </div>
                        <div class="card-body">
                            <div id="alert-container" class="d-none">
                                <div class="alert alert-dismissible fade show" role="alert">
                                    <span id="alert-message"></span>
                                    <button type="button" class="close py-1" data-dismiss="alert">&times;</button>
                                </div>
                            </div>

                            <form id="formTtd" autocomplete="off">
                                <input type="hidden" id="record_id" name="id"
                                    value="<?= esc($showprofilresiko['id'] ?? ''); ?>">

                                <?php
                                $fields = [
                                    'dirut' => ['label' => 'Nama Direktur Utama', 'type' => 'text'],
                                    'dirkep' => ['label' => 'Nama Direktur yang membawahi fungsi kepatuhan', 'type' => 'text'],
                                    'pe' => ['label' => 'Nama Pejabat Eksekutif Manajemen Risiko', 'type' => 'text'],
                                    'tanggal' => ['label' => 'Tanggal Pelaporan', 'type' => 'text', 'placeholder' => 'dd/mm/yyyy'],
                                    'lokasi' => ['label' => 'Lokasi', 'type' => 'text'],
                                ];
                                foreach ($fields as $fid => $fdef): ?>
                                    <div class="form-group mb-3">
                                        <label for="<?= $fid ?>" class="small font-weight-bold">
                                            <?= $fdef['label'] ?> <span class="text-danger">*</span>
                                            <i class="fas fa-spinner fa-spin text-primary ml-1 d-none"
                                                id="indicator-<?= $fid ?>"></i>
                                        </label>
                                        <input type="<?= $fdef['type'] ?>" class="form-control auto-save" id="<?= $fid ?>"
                                            name="<?= $fid ?>" data-field="<?= $fid ?>" <?= isset($fdef['placeholder']) ? 'placeholder="' . $fdef['placeholder'] . '"' : '' ?>
                                            value="<?= esc($showprofilresiko[$fid === 'tanggal' ? 'tanggal_display' : $fid] ?? ''); ?>"
                                            required>
                                        <small class="text-success d-none" id="status-<?= $fid ?>"><i
                                                class="fas fa-check-circle"></i> Tersimpan</small>
                                    </div>
                                <?php endforeach; ?>

                                <div class="d-flex flex-wrap gap-2 mt-3">
                                    <button type="submit" class="btn btn-success btn-sm"><i
                                            class="fas fa-save mr-1"></i> Simpan Semua Data</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <?php
            // Validation checks
            $allRisksApproved = true;
            $unapprovedRisks = [];
            $allFactorsApproved = true;
            $unapprovedFactors = [];

            foreach ($transparan as $trans) {
                $rt = strtolower($trans['risk_type'] ?? '');
                if (in_array($rt, ['stratejik', 'reputasi'])) {
                    $hasData = ($rt === 'stratejik' && !empty($trans['nstratejik']) && $trans['nstratejik'] != '0')
                        || ($rt === 'reputasi' && !empty($trans['nreputasi']) && $trans['nreputasi'] != '0');
                    if (!$hasData)
                        continue;
                }
                if (!(isset($trans['is_approved']) && $trans['is_approved'] == 1)) {
                    $allRisksApproved = false;
                    $unapprovedRisks[] = $trans['name'] ?? 'Unknown';
                }
            }

            if (isset($factors) && is_array($factors)) {
                foreach ($factors as $factor) {
                    $rt = strtolower($factor['risk_type'] ?? '');
                    if (in_array($rt, ['stratejik', 'reputasi'])) {
                        $hasData = ($rt === 'stratejik' && !empty($factor['nstratejik']) && $factor['nstratejik'] != '0')
                            || ($rt === 'reputasi' && !empty($factor['nreputasi']) && $factor['nreputasi'] != '0');
                        if (!$hasData)
                            continue;
                    }
                    if (!(isset($factor['is_approved']) && $factor['is_approved'] == 1)) {
                        $allFactorsApproved = false;
                        $unapprovedFactors[] = $factor['name'] ?? 'Unknown';
                    }
                }
            }

            $kesimpulanFilled = isset($showprofilresiko['kesimpulan']) && !empty(trim($showprofilresiko['kesimpulan']));
            $pr = $showprofilresiko ?? [];
            $lembarFilled = !empty(trim($pr['dirut'] ?? '')) && !empty(trim($pr['dirkep'] ?? '')) &&
                !empty(trim($pr['pe'] ?? '')) && !empty(trim($pr['tanggal'] ?? '')) &&
                !empty(trim($pr['lokasi'] ?? ''));

            $disableBtn = !($allRisksApproved && $allFactorsApproved && $kesimpulanFilled && $lembarFilled);

            // Build alert message
            if ($allRisksApproved && $allFactorsApproved && $kesimpulanFilled && $lembarFilled) {
                $alertMsg = '<i class="fas fa-check-circle mr-1"></i> Seluruh data telah lengkap. Anda dapat melakukan generate laporan.';
                $alertCls = 'alert-success';
            } else {
                $issues = [];
                if (!$allRisksApproved && $unapprovedRisks)
                    $issues[] = 'Risiko belum disetujui: <strong>' . implode(', ', $unapprovedRisks) . '</strong>';
                if (!$allFactorsApproved && $unapprovedFactors)
                    $issues[] = 'Faktor belum disetujui: <strong>' . implode(', ', $unapprovedFactors) . '</strong>';
                if (!$kesimpulanFilled)
                    $issues[] = '<strong>Kesimpulan/analisis belum terisi</strong>';
                if (!$lembarFilled) {
                    $mf = [];
                    if (empty(trim($pr['dirut'] ?? '')))
                        $mf[] = 'Nama Direktur Utama';
                    if (empty(trim($pr['dirkep'] ?? '')))
                        $mf[] = 'Nama Direktur Kepatuhan';
                    if (empty(trim($pr['pe'] ?? '')))
                        $mf[] = 'Nama Pejabat Eksekutif MR';
                    if (empty(trim($pr['tanggal'] ?? '')))
                        $mf[] = 'Tanggal Pelaporan';
                    if (empty(trim($pr['lokasi'] ?? '')))
                        $mf[] = 'Lokasi';
                    if ($mf)
                        $issues[] = '<strong>Lembar persetujuan belum lengkap:</strong> ' . implode(', ', $mf);
                }
                $alertMsg = '<i class="fas fa-exclamation-triangle mr-1"></i> <strong>Laporan belum dapat di-generate.</strong><br>' . implode('<br>', $issues);
                $alertCls = 'alert-warning';
            }
            ?>

            <div class="col-12 mb-3 px-3">
                <div class="alert <?= $alertCls ?>" style="font-size:13px; line-height:1.6;">
                    <?= $alertMsg ?>
                </div>
            </div>

            <div class="col-12 text-center mb-2">
                <span class="text-secondary small">Pastikan seluruh data telah terisi dengan benar sebelum
                    generate laporan.</span>
            </div>

            <div class="col-12 d-flex flex-wrap justify-content-center gap-3 mt-2 mb-4">
                <button id="btnExportAllPDF" class="btn btn-generate <?= $disableBtn ? 'disabled' : '' ?>"
                    <?= $disableBtn ? 'disabled' : '' ?> type="button">
                    <i class="fas fa-download mr-1"></i> GENERATE PDF
                </button>
            </div>
        </div>
    </div>
</div>

<div id="toast-container" aria-live="polite" aria-atomic="true"></div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
    (function () {
        'use strict';

        function debounce(fn, ms) {
            let t;
            return function (...args) { clearTimeout(t); t = setTimeout(() => fn.apply(this, args), ms); };
        }

        function showToast(type, message) {
            const colors = { success: '#28a745', danger: '#dc3545', warning: '#ffc107', info: '#17a2b8' };
            const icons = { success: 'check-circle', danger: 'exclamation-circle', warning: 'exclamation-triangle', info: 'info-circle' };
            const toast = document.createElement('div');
            toast.className = 'app-toast';
            toast.style.cssText = `border-left:4px solid ${colors[type] || colors.info};`;
            toast.innerHTML = `<i class="fas fa-${icons[type] || 'info-circle'} mr-2"></i>${message}
            <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>`;
            document.getElementById('toast-container').appendChild(toast);
            requestAnimationFrame(() => toast.classList.add('show'));
            setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 300); }, 4500);
        }

        function setIndicator(field, show) {
            const el = document.getElementById('indicator-' + field);
            if (el) el.classList.toggle('d-none', !show);
        }
        function setStatus(field, show) {
            const el = document.getElementById('status-' + field);
            if (!el) return;
            if (show) {
                el.classList.remove('d-none');
                setTimeout(() => el.classList.add('d-none'), 3000);
            } else {
                el.classList.add('d-none');
            }
        }

        const AJAX_HEADERS = {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        };

        function autoSaveField(id, field, value) {
            if (!id) { showToast('danger', 'ID record tidak ditemukan'); return; }
            fetch('<?= base_url("Showprofilresiko/ajaxAutoSaveField"); ?>', {
                method: 'POST',
                headers: AJAX_HEADERS,
                body: new URLSearchParams({ id, field, value })
            })
                .then(r => r.json())
                .then(data => {
                    setIndicator(field, false);
                    if (data.success) setStatus(field, true);
                    else showToast('danger', data.message || 'Gagal menyimpan data');
                })
                .catch(() => { setIndicator(field, false); showToast('danger', 'Kesalahan koneksi saat menyimpan'); });
        }

        const debouncedSave = debounce(autoSaveField, 1500);

        document.querySelectorAll('.auto-save').forEach(function (el) {
            el.addEventListener('input', function () {
                setIndicator(this.dataset.field, true);
                setStatus(this.dataset.field, false);
                debouncedSave(document.getElementById('record_id')?.value, this.dataset.field, this.value);
            });
            el.addEventListener('blur', function () {
                autoSaveField(document.getElementById('record_id')?.value, this.dataset.field, this.value);
            });
        });

        function saveKesimpulan(id, value) {
            if (!id) return;
            fetch('<?= base_url("Showprofilresiko/ajaxSaveKesimpulan"); ?>', {
                method: 'POST',
                headers: AJAX_HEADERS,
                body: new URLSearchParams({ id, kesimpulan: value })
            })
                .then(r => r.json())
                .then(data => {
                    setIndicator('kesimpulan', false);
                    if (data.success) { setStatus('kesimpulan', true); }
                    else showToast('danger', data.message || 'Gagal menyimpan kesimpulan');
                })
                .catch(() => { setIndicator('kesimpulan', false); showToast('danger', 'Kesalahan koneksi'); });
        }

        const debouncedKesimpulan = debounce(function (id, val) { saveKesimpulan(id, val); }, 1500);

        const kesimpulanEl = document.getElementById('kesimpulan');
        if (kesimpulanEl) {
            kesimpulanEl.addEventListener('input', function () {
                const id = document.querySelector('#formKesimpulan input[name="id"]')?.value;
                setIndicator('kesimpulan', true);
                setStatus('kesimpulan', false);
                debouncedKesimpulan(id, this.value);
            });
        }

        const formTtd = document.getElementById('formTtd');
        if (formTtd) {
            formTtd.addEventListener('submit', function (e) {
                e.preventDefault();
                const id = document.getElementById('record_id')?.value;
                const body = new URLSearchParams({
                    id,
                    dirut: document.getElementById('dirut')?.value || '',
                    dirkep: document.getElementById('dirkep')?.value || '',
                    pe: document.getElementById('pe')?.value || '',
                    tanggal: document.getElementById('tanggal')?.value || '',
                    lokasi: document.getElementById('lokasi')?.value || '',
                });
                const btn = this.querySelector('button[type="submit"]');
                const orig = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...';

                fetch('<?= base_url("Showprofilresiko/ajaxUpdateTtd"); ?>', {
                    method: 'POST',
                    headers: AJAX_HEADERS,
                    body
                })
                    .then(r => r.json())
                    .then(data => {
                        btn.disabled = false; btn.innerHTML = orig;
                        showToast(data.success ? 'success' : 'danger', data.message || (data.success ? 'Data berhasil disimpan' : 'Gagal menyimpan'));
                    })
                    .catch(() => { btn.disabled = false; btn.innerHTML = orig; showToast('danger', 'Kesalahan koneksi'); });
            });
        }

        const formKesimpulan = document.getElementById('formKesimpulan');
        if (formKesimpulan) {
            formKesimpulan.addEventListener('submit', function (e) {
                e.preventDefault();
                const id = this.querySelector('input[name="id"]')?.value;
                const val = document.getElementById('kesimpulan')?.value?.trim();
                if (!val) { showToast('warning', 'Mohon isi kesimpulan terlebih dahulu'); return; }
                const btn = this.querySelector('button[type="submit"]');
                const orig = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...';
                saveKesimpulan(id, val);
                setTimeout(() => { btn.disabled = false; btn.innerHTML = orig; }, 1500);
            });
        }

        window.resetNtotalrisk = function () {
            if (confirm('Reset nilai total risiko ke perhitungan otomatis?')) {
                document.getElementById('formResetNtotalrisk')?.submit();
            }
        };

        const tanggalEl = document.getElementById('tanggal');
        if (tanggalEl) {
            flatpickr(tanggalEl, {
                dateFormat: 'd/m/Y',
                allowInput: true,
                onChange: function (_, dateStr) {
                    const id = document.getElementById('record_id')?.value;
                    if (id) debouncedSave(id, 'tanggal', dateStr);
                }
            });
        }

        const btnPDF = document.getElementById('btnExportAllPDF');
        if (btnPDF && !btnPDF.disabled) {
            btnPDF.addEventListener('click', function () {
                const btn = this;
                const orig = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Memproses...';
                const w = window.open('<?= base_url('Showprofilresiko/exportAllPDFToZipView') ?>', 'ExportPDFWindow', 'width=800,height=600,scrollbars=yes,resizable=yes');
                if (!w || w.closed) showToast('warning', 'Pop-up diblokir. Izinkan pop-up dan coba lagi.');
                setTimeout(() => { btn.disabled = false; btn.innerHTML = orig; }, 3000);
            });
        }

        document.querySelectorAll('.btn.disabled, a.disabled').forEach(function (el) {
            el.addEventListener('click', function (e) {
                e.preventDefault(); e.stopImmediatePropagation();
                showToast('warning', 'Lengkapi semua persyaratan terlebih dahulu!');
            });
        });

        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                formTtd?.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
            }
        });

    })();
</script>

<style>
    :root {
        --blue: #3b82f6;
        --blue-d: #1d4ed8;
        --green: #10b981;
        --red: #ef4444;
        --amber: #f59e0b;
        --gray-50: #f8fafc;
        --gray-100: #f1f5f9;
        --gray-200: #e2e8f0;
        --gray-400: #94a3b8;
        --gray-600: #475569;
        --gray-800: #1e293b;
        --radius: 10px;
        --primary-gradient: linear-gradient(135deg, #141863 0%, #1f2aa8 100%);
        --transition: 0.2s ease;
    }

    body {
        -webkit-font-smoothing: antialiased;
    }

    .main-card {
        border-radius: var(--radius);
        overflow: hidden;
    }

    .btn-kembali {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: white;
        border: 1.5px solid var(--gray-200);
        border-radius: 8px;
        padding: 7px 14px;
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--gray-600);
        text-decoration: none;
        transition: all var(--transition);
        margin: 14px 0 0 12px;
    }

    .btn-kembali:hover {
        background: var(--gray-50);
        border-color: var(--gray-400);
        color: var(--gray-800);
        text-decoration: none;
    }

    .page-header {
        text-align: center;
        padding: 20px 16px 16px;
        background: white;
    }

    .page-subtitle {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--blue);
        margin-bottom: 8px;
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

    .card {
        border-radius: var(--radius);
        border: 1px solid var(--gray-200);
    }

    .card:hover {
        transform: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, .1) !important;
    }

    .card-link-wrapper {
        display: block;
        text-decoration: none;
        color: inherit;
    }

    .card-link-wrapper:hover {
        color: inherit;
    }

    .trans-card {
        border-left: 4px solid #6c757d;
        transition: box-shadow var(--transition);
    }

    .factor-card {
        border-left: 4px solid #6c757d;
        transition: box-shadow var(--transition);
    }

    .approved-card {
        border-left-color: #28a745 !important;
    }

    .risk-rating-card {
        width: 100%;
        max-width: 300px;
    }

    .risk-update-card {
        width: 100%;
        max-width: 440px;
    }

    .risk-detail-row {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        font-size: 0.9rem;
        margin-bottom: 3px;
        font-weight: 700;
    }

    .risk-label {
        color: var(--gray-600);
    }

    .text-xs {
        font-size: 0.85rem;
    }

    .btn-generate {
        background: #28a745;
        color: white;
        padding: 10px 30px;
        font-size: 1.1rem;
        font-weight: 600;
        border-radius: 50px;
        border: 2px solid #28a745;
        transition: all var(--transition);
    }

    .btn-generate:hover:not(.disabled) {
        background: #218838;
        border-color: #218838;
        color: white;
        transform: translateY(-1px);
    }

    .btn-generate.disabled {
        background: #6c757d;
        border-color: #6c757d;
        pointer-events: none;
        opacity: 0.6;
    }

    #toast-container {
        position: fixed;
        top: 16px;
        right: 16px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 8px;
        pointer-events: none;
        max-width: min(380px, calc(100vw - 32px));
    }

    .app-toast {
        background: white;
        border-radius: 8px;
        padding: 12px 38px 12px 14px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, .15);
        font-size: 0.875rem;
        pointer-events: all;
        position: relative;
        opacity: 0;
        transform: translateX(20px);
        transition: opacity .25s ease, transform .25s ease;
    }

    .app-toast.show {
        opacity: 1;
        transform: translateX(0);
    }

    .toast-close {
        position: absolute;
        top: 8px;
        right: 10px;
        background: none;
        border: none;
        font-size: 1rem;
        cursor: pointer;
        color: #666;
        line-height: 1;
    }

    .fa-spinner {
        font-size: 0.8rem;
    }

    [id^="status-"] {
        font-size: 0.75rem;
    }

    .alert {
        border-radius: 8px;
    }

    .gap-2 {
        gap: 0.5rem;
    }

    .gap-3 {
        gap: 0.75rem;
    }

    .form-control:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 .2rem rgba(0, 123, 255, .2);
    }

    .form-group label {
        font-weight: 600;
        font-size: 1.0rem;
        margin-bottom: 4px;
    }

    @media (max-width: 575.98px) {
        .page-header {
            padding: 16px 12px 12px;
        }

        .page-header h1 {
            font-size: 1.1rem !important;
        }

        .page-subtitle {
            font-size: 1rem;
        }

        .badge-namabpr {
            font-size: 0.8rem;
            padding: 3px 12px;
        }

        .card-body {
            padding: 10px 12px;
        }

        .btn-kembali {
            font-size: 0.8rem;
            padding: 6px 10px;
        }

        .risk-rating-card {
            max-width: 100%;
            margin: 0 8px;
        }

        .risk-update-card {
            max-width: 100%;
            margin: 0 8px;
        }

        #toast-container {
            top: 8px;
            right: 8px;
            left: 8px;
            max-width: none;
        }

        .btn-generate {
            width: 100%;
            max-width: 260px;
            text-align: center;
            font-size: 1rem;
        }

        .d-flex.flex-wrap.gap-3 {
            gap: 0.5rem !important;
        }
    }

    @media (max-width: 767.98px) {
        .container-fluid {
            padding-left: 8px;
            padding-right: 8px;
        }

        .row.mx-0 {
            margin: 0 !important;
        }

        .px-2 {
            padding-left: 6px !important;
            padding-right: 6px !important;
        }
    }

    @media print {

        #toast-container,
        .btn-kembali,
        button,
        .save-indicator {
            display: none !important;
        }
    }
</style>