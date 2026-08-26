<?php
$peringkatLabels = [
    '1' => 'Sangat Baik',
    '2' => 'Baik',
    '3' => 'Cukup',
    '4' => 'Kurang Baik',
    '5' => 'Buruk'
];

function getBadgeClass($nilai)
{
    switch ((string) $nilai) {
        case '1':
            return 'badge-success';
        case '2':
            return 'badge-info';
        case '3':
            return 'badge-warning';
        case '4':
            return 'badge-orange';
        case '5':
            return 'badge-danger';
        default:
            return 'badge-secondary';
    }
}

$isSynced = isset($syncStatus) && $syncStatus['is_synced'];
$canSync = isset($showfaktor) && !empty($showfaktor);

$nilaiperingkat = 0;
$peringkatLabel = 'Belum Ada Data';
$deskripsiValue = '';
$analisisValue = '';

if (isset($tkstatakelola) && !empty($tkstatakelola)) {
    $nilaiperingkat = $tkstatakelola['nilaiperingkat'] ?? 0;
    $peringkatLabel = $tkstatakelola['peringkat'] ?? ($peringkatLabels[$nilaiperingkat] ?? 'Tidak Valid');
    $deskripsiValue = $tkstatakelola['deskripsi'] ?? '';
    $analisisValue = $tkstatakelola['analisis'] ?? '';
}

$badgeClass = getBadgeClass($nilaiperingkat);

$jsConfig = json_encode([
    'baseUrl' => base_url(),
    'csrfName' => csrf_token(),
    'csrfHash' => csrf_hash(),
    'initialUnread' => (int) ($unreadCount ?? 0),
    'semester' => $syncStatus['semester'] ?? '',
    'tahun' => $syncStatus['tahun'] ?? '',
    'prevSemester' => $syncStatus['prev_semester'] ?? '',
    'prevTahun' => $syncStatus['prev_tahun'] ?? '',
    'nilaikomposit' => $syncStatus['nilaikomposit'] ?? null,
    'nilaikompositBefore' => $syncStatus['nilaikomposit_before'] ?? null,
    'currentAvailable' => $syncStatus['current_available'] ?? false,
    'beforeAvailable' => $syncStatus['before_available'] ?? false,
    'peringkatLabels' => $peringkatLabels,
], JSON_HEX_TAG | JSON_HEX_AMP);
?>

<div class="alert alert-info my-2">
    <?php if (isset($bprData) && isset($periodeDetail)): ?>
        <strong><?= esc($bprData['namabpr'] ?? 'Nama BPR') ?></strong> - Periode Pelaporan
        Semester <?= esc($periodeDetail['semester']) ?> Tahun <?= esc($periodeDetail['tahun']) ?>
    <?php elseif (isset($periodeDetail)): ?>
        <strong>Periode:</strong>
        Semester <?= esc($periodeDetail['semester']) ?> Tahun <?= esc($periodeDetail['tahun']) ?>
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

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">&times;</button>
        <strong><?= esc(session()->getFlashdata('error')); ?></strong>
    </div>
<?php endif; ?>

<div class="container-fluid py-4">
    <div class="main-content">
        <div class="d-flex justify-content-start mb-3">
            <a href="<?= base_url('Showtks') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>

        <!-- Header Section -->
        <div class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h4 class="mb-0 fw-bold text-primary">
                            <i class="fas fa-building me-2 text-primary"></i>
                            Faktor 2. Penilaian Tata Kelola (GCG)
                        </h4>
                        <p class="text-muted mb-0 mt-2">
                            <small>Penilaian tata kelola berdasarkan data Laporan Tata Kelola periode yang sama</small>
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <span id="approval-badge-main"
                            class="badge <?= $isApproved ? 'badge-success' : 'badge-secondary' ?>"
                            style="font-size:14px;padding:10px 15px;">
                            <?php if ($isApproved): ?>
                                <i class="fas fa-check-circle"></i>
                                Disetujui oleh <strong>Direktur Utama</strong><br>
                                <?= $approvedAt ? date('d/m/Y H:i:s', strtotime($approvedAt)) : '-' ?>
                            <?php else: ?>
                                <i class="fas fa-clock"></i>
                                Belum Disetujui<br>Oleh Direktur Utama
                            <?php endif; ?>
                        </span>
                        <button type="button" class="btn btn-primary position-relative komentar-button mb-3"
                            id="btnKomentar" style="margin-left:15px;" aria-label="Buka komentar">
                            <i class="fas fa-comment"></i>
                            <span id="notification-badge" class="badge badge-danger notification-badge"
                                style="display:<?= ($unreadCount ?? 0) > 0 ? 'inline-block' : 'none' ?>;">
                                <?= $unreadCount ?? 0 ?>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sync Status Section -->
        <div id="syncStatusContainer" class="mb-4">
            <?php if (isset($syncStatus)): ?>
                <?php if ($syncStatus['is_synced']): ?>
                    <div class="alert alert-sync-success">
                        <div class="row align-items-start">
                            <div class="col-auto">
                                <i class="fas fa-check-circle sync-icon"></i>
                            </div>
                            <div class="col">
                                <h6 class="mb-2 fw-bold">
                                    <i class="fas fa-link me-2"></i> Tersinkron dengan Laporan Tata Kelola
                                </h6>
                                <p class="mb-1">
                                    <strong>Periode:</strong> Semester <?= $syncStatus['semester'] ?? '1' ?>
                                    Tahun <?= $syncStatus['tahun'] ?? date('Y') ?>
                                </p>
                                <p class="mb-0">
                                    <i class="fas fa-clock text-success"></i>
                                    Disinkronkan pada:
                                    <strong><?= date('d M Y H:i', strtotime($syncStatus['sync_date'] ?? 'now')) ?></strong>
                                </p>
                            </div>
                            <div class="col-12 col-md-auto mt-3 mt-md-0">
                                <button class="btn btn-unsync" id="btnUnsync">
                                    <i class="fas fa-unlink me-2"></i> Batalkan Sinkronisasi
                                </button>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert <?= $canSync ? 'alert-sync-warning' : 'alert-sync-danger' ?>">
                        <div class="row align-items-start">
                            <div class="col-auto">
                                <i
                                    class="fas <?= $canSync ? 'fa-exclamation-triangle' : 'fa-times-circle' ?> sync-icon-warning"></i>
                            </div>
                            <div class="col">
                                <?php if ($canSync): ?>
                                    <h6 class="mb-2 fw-bold"><i class="fas fa-info-circle me-2"></i>Data Belum Disinkronkan</h6>
                                    <p class="mb-2">
                                        Data Laporan Tata Kelola tersedia. Klik tombol di bawah untuk menyinkronkan secara otomatis.
                                    </p>
                                    <div class="sync-info-box mt-3">
                                        <p class="mb-1"><strong>Data yang akan diambil &amp; disimpan:</strong></p>
                                        <ul class="mb-0">
                                            <li>
                                                Nilai Peringkat (Semester <?= $syncStatus['semester'] ?> Tahun
                                                <?= $syncStatus['tahun'] ?>):
                                                <?php if ($syncStatus['current_available']): ?>
                                                    <span class="badge <?= getBadgeClass($syncStatus['nilaikomposit']) ?>">
                                                        <?= $syncStatus['nilaikomposit'] ?> -
                                                        <?= $syncStatus['nilaikomposit'] !== null ? ($peringkatLabels[(string) $syncStatus['nilaikomposit']] ?? 'N/A') : 'N/A' ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Tidak Ada Data</span>
                                                <?php endif; ?>
                                            </li>
                                            <li>Nilai Faktor (30%):
                                                <strong><?= round(($syncStatus['nilaikomposit'] ?? 0) * 0.30, 2) ?></strong>
                                            </li>

                                            <?php if ($syncStatus['before_available']): ?>
                                                <li>
                                                    Nilai Peringkat Periode Sebelumnya (Semester <?= $syncStatus['prev_semester'] ?>
                                                    Tahun <?= $syncStatus['prev_tahun'] ?>):
                                                    <span class="badge <?= getBadgeClass($syncStatus['nilaikomposit_before']) ?>">
                                                        <?= $syncStatus['nilaikomposit_before'] ?> -
                                                        <?= $peringkatLabels[$syncStatus['nilaikomposit_before']] ?? 'N/A' ?>
                                                    </span>
                                                </li>
                                                <li>Nilai Faktor Periode Sebelumnya (30%):
                                                    <strong><?= round($syncStatus['nilaikomposit_before'] * 0.30, 2) ?></strong>
                                                </li>
                                            <?php else: ?>
                                                <li class="text-warning">
                                                    <i class="fas fa-info-circle"></i>
                                                    Data periode sebelumnya (Semester <?= $syncStatus['prev_semester'] ?> Tahun
                                                    <?= $syncStatus['prev_tahun'] ?>) <strong>belum tersedia</strong>.
                                                    Sinkronisasi <strong>tetap bisa dilakukan</strong> — nilai "Periode Sebelumnya"
                                                    cukup diisi secara manual pada form "Peringkat Periode Sebelumnya" di bawah.
                                                </li>
                                            <?php endif; ?>

                                            <li>Deskripsi &amp; Analisis &mdash; dari Kesimpulan Laporan Tata Kelola periode
                                                Semester <?= $syncStatus['semester'] ?> Tahun <?= $syncStatus['tahun'] ?></li>
                                        </ul>
                                    </div>
                                <?php else: ?>
                                    <h6 class="mb-2 fw-bold"><i class="fas fa-exclamation-circle me-2"></i>Data Laporan Tata Kelola
                                        Tidak Tersedia</h6>
                                    <p class="mb-0">
                                        Belum ada nilai komposit Laporan Tata Kelola untuk periode aktif Semester
                                        <?= $syncStatus['semester'] ?? '1' ?> Tahun <?= $syncStatus['tahun'] ?? date('Y') ?>.
                                        Silakan lengkapi Laporan Tata Kelola periode ini terlebih dahulu.
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Ringkasan Nilai Section -->
        <!-- View: Ringkasan Penilaian Section — tambahkan kolom Nilai Peringkat Periode Sebelumnya saat synced -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-gradient-primary">
                <h6 class="mb-0 text-black fw-bold">
                    <i class="fas fa-chart-bar me-2"></i>Ringkasan Penilaian Tata Kelola
                </h6>
            </div>
            <div class="card-body p-4">
                <?php if ($nilaiperingkat > 0): ?>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="info-box shadow-sm border-0 h-100">
                                <div class="d-flex align-items-center h-100">
                                    <div class="info-icon me-3">
                                        <i class="fas fa-trophy fa-2x text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1 ml-3">
                                        <div class="info-label text-muted mb-1">Nilai Peringkat Faktor</div>
                                        <div class="info-value"><?= $nilaiperingkat ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-box shadow-sm border-0 h-100">
                                <div class="d-flex align-items-center h-100">
                                    <div class="info-icon me-3">
                                        <i class="fas fa-award fa-2x"></i>
                                    </div>
                                    <div class="flex-grow-1 ml-3">
                                        <div class="info-label text-muted mb-2">Kategori Penilaian</div>
                                        <div class="info-value">
                                            <span class="badge <?= $badgeClass ?> badge-lg px-4 py-2"
                                                style="font-size:1rem;font-weight:600;">
                                                <?= $peringkatLabel ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php
                        $nilaiperingkatbefore = $tkstatakelola['nilaiperingkatbefore'] ?? 0;
                        $peringkatBeforeLabel = $tkstatakelola['peringkatbefore'] ?? ($peringkatLabels[$nilaiperingkatbefore] ?? '');
                        $nilaifaktorbefore = $tkstatakelola['nilaifaktorbefore'] ?? 0;
                        ?>
                        <?php if ($isSynced && $nilaiperingkatbefore > 0): ?>
                            <div class="col-md-6">
                                <div class="info-box shadow-sm border-0 h-100 bg-light">
                                    <div class="d-flex align-items-center h-100">
                                        <div class="info-icon me-3">
                                            <i class="fas fa-history fa-2x text-secondary"></i>
                                        </div>
                                        <div class="flex-grow-1 ml-3">
                                            <div class="info-label text-muted mb-1">Nilai Peringkat Periode Sebelumnya</div>
                                            <div style="font-size:1.5rem;font-weight:700;color:#2c3e50;">
                                                <?= $nilaiperingkatbefore ?>
                                                <span class="badge <?= getBadgeClass($nilaiperingkatbefore) ?> ml-2"
                                                    style="font-size:.75rem;">
                                                    <?= $peringkatBeforeLabel ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-box shadow-sm border-0 h-100 bg-light">
                                    <div class="d-flex align-items-center h-100">
                                        <div class="info-icon me-3">
                                            <i class="fas fa-chart-line fa-2x text-info"></i>
                                        </div>
                                        <div class="flex-grow-1 ml-3">
                                            <div class="info-label text-muted mb-1">Nilai Faktor Periode Sebelumnya (30%)</div>
                                            <div style="font-size:1.5rem;font-weight:700;color:#2c3e50;">
                                                <?= number_format($nilaifaktorbefore, 2) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <div class="empty-state">
                            <div class="empty-state-icon mb-3">
                                <i class="fas fa-inbox fa-4x" style="color:#e0e0e0;"></i>
                            </div>
                            <h5 class="text-muted mb-2" style="font-weight:600;">Belum Ada Data</h5>
                            <p class="text-muted mb-0" style="font-size:.9rem;">Data penilaian belum tersedia saat ini</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($canSync && !$isSynced): ?>
                <div class="card-footer bg-white border-top">
                    <?php
                    $nilaikompositCheck = $syncStatus['nilaikomposit'] ?? 0;
                    $isEmpty = empty($nilaikompositCheck) || $nilaikompositCheck == 0;
                    ?>
                    <?php if ($isEmpty): ?>
                        <button class="btn btn-sync btn-block" disabled style="opacity:0.5;cursor:not-allowed;">
                            <i class="fas fa-exclamation-circle me-2"></i> Tidak Ada Data — Sinkronisasi Tidak Tersedia
                        </button>
                        <small class="text-muted d-block text-center mt-2">
                            <i class="fas fa-info-circle"></i>
                            Nilai komposit Laporan Tata Kelola belum tersedia. Silakan lengkapi Laporan Tata Kelola terlebih
                            dahulu.
                        </small>
                    <?php else: ?>
                        <button class="btn btn-sync btn-block" id="btnSyncTatakelola">
                            <i class="fas fa-sync-alt me-2"></i> Sinkron Dengan Laporan Tata Kelola
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Peringkat Periode Sebelumnya -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-history"></i> Peringkat Periode Sebelumnya
                </h6>
                <?php if ($isSynced): ?>
                    <span class="badge badge-success">Manual — Tidak Terpengaruh Sinkron</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if ($isSynced): ?>
                    <?php if ($syncStatus['before_available'] ?? false): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Nilai berikut sudah terisi otomatis dari Laporan Tata Kelola
                            Semester <?= $syncStatus['prev_semester'] ?? '-' ?> Tahun <?= $syncStatus['prev_tahun'] ?? '-' ?>.
                            Anda tetap bisa mengubahnya secara manual jika diperlukan.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            Data Laporan Tata Kelola periode Semester <?= $syncStatus['prev_semester'] ?? '-' ?> Tahun
                            <?= $syncStatus['prev_tahun'] ?? '-' ?> belum tersedia untuk diambil otomatis.
                            Silakan isi nilai peringkat periode sebelumnya secara manual di bawah ini.
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <form action="<?= base_url('tkstatakelola/simpanPeringkatSebelumnya') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="nilaiperingkatbefore">
                                    Nilai Peringkat Periode Sebelumnya <span class="text-danger">*</span>
                                </label>
                                <select class="form-control" id="nilaiperingkatbefore" name="nilaiperingkatbefore"
                                    required>
                                    <option value="">-- Pilih Nilai Peringkat --</option>
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <option value="<?= $i ?>" <?= (isset($tkstatakelola['nilaiperingkatbefore']) && $tkstatakelola['nilaiperingkatbefore'] == $i) ? 'selected' : '' ?>>
                                            <?= $i ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                                <small class="form-text text-muted">
                                    1 = Sangat Baik, 2 = Baik, 3 = Cukup, 4 = Kurang Baik, 5 = Buruk
                                </small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="peringkatbefore">Peringkat</label>
                                <input type="text" class="form-control bg-light" id="peringkatbefore"
                                    name="peringkatbefore" value="<?= esc($tkstatakelola['peringkatbefore'] ?? '-') ?>"
                                    readonly>
                                <small class="form-text text-muted">Otomatis terisi berdasarkan nilai peringkat</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="nilaifaktorbefore">
                                    Nilai Faktor (30%)
                                    <i class="fas fa-info-circle text-info" data-toggle="tooltip"
                                        title="Hasil otomatis dari Nilai Peringkat x 30%"></i>
                                </label>
                                <input type="text" class="form-control bg-light font-weight-bold text-primary"
                                    id="nilaifaktorbefore" name="nilaifaktorbefore"
                                    value="<?= number_format($tkstatakelola['nilaifaktorbefore'] ?? 0, 2) ?>" readonly>
                                <small class="form-text text-muted">Otomatis: Nilai Peringkat × 30%</small>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle"></i>
                        <strong>Catatan:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Peringkat akan otomatis terisi sesuai dengan nilai yang dipilih</li>
                            <li>Nilai Faktor dihitung otomatis: <strong>Nilai Peringkat × 30%</strong></li>
                            <li>Contoh: Nilai 3 → Nilai Faktor = 3 × 0.30 = <strong>0.90</strong></li>
                        </ul>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Peringkat Periode Sebelumnya
                        </button>
                    </div>
                </form>

                <?php if (isset($tkstatakelola['nilaiperingkatbefore']) && !empty($tkstatakelola['nilaiperingkatbefore'])): ?>
                    <hr>
                    <div class="mt-4">
                        <h6 class="font-weight-bold text-secondary">Data Tersimpan:</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <tr>
                                    <th width="30%">Nilai Peringkat</th>
                                    <td><?= $tkstatakelola['nilaiperingkatbefore'] ?></td>
                                </tr>
                                <tr>
                                    <th>Peringkat</th>
                                    <td>
                                        <span class="badge <?= getBadgeClass($tkstatakelola['nilaiperingkatbefore']) ?>">
                                            <?= esc($tkstatakelola['peringkatbefore'] ?? '') ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Nilai Faktor (30%)</th>
                                    <td class="font-weight-bold text-primary">
                                        <?= number_format($tkstatakelola['nilaifaktorbefore'] ?? 0, 2) ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Terakhir Update</th>
                                    <td>
                                        <?= isset($tkstatakelola['updated_at'])
                                            ? date('d/m/Y H:i', strtotime($tkstatakelola['updated_at']))
                                            : '-' ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- SYNCED STATE -->
        <?php if ($isSynced): ?>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 fw-bold text-success">
                        <i class="fas fa-lock me-2"></i> Deskripsi Peringkat
                        <span class="badge badge-success float-end">Tersinkron</span>
                    </h6>
                    <small class="text-muted">Berdasarkan Matriks Peringkat Faktor Penilaian Tata Kelola</small>
                </div>
                <div class="card-body p-4">
                    <div class="synced-content-box">
                        <div class="synced-badge">
                            <i class="fas fa-check-circle text-success"></i>
                            <span>Data dari Laporan Tata Kelola</span>
                        </div>
                        <div class="content-display">
                            <?php if (!empty($deskripsiValue)): ?>
                                <p class="mb-0"><?= nl2br(esc($deskripsiValue)) ?></p>
                            <?php else: ?>
                                <p class="text-muted mb-0"><em>Tidak ada deskripsi</em></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 fw-bold text-success">
                        <i class="fas fa-lock me-2"></i> Analisa / Penjelasan Lebih Lanjut
                        <span class="badge badge-success float-end">Tersinkron</span>
                    </h6>
                    <small class="text-muted">Kesimpulan dari Laporan Tata Kelola</small>
                </div>
                <div class="card-body p-4">
                    <div class="synced-content-box">
                        <div class="synced-badge">
                            <i class="fas fa-check-circle text-success"></i>
                            <span>Data dari Laporan Tata Kelola</span>
                        </div>
                        <div class="content-display">
                            <?php if (!empty($analisisValue)): ?>
                                <p class="mb-0"><?= nl2br(esc($analisisValue)) ?></p>
                            <?php else: ?>
                                <p class="text-muted mb-0"><em>Belum ada analisa</em></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- MANUAL STATE -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-gradient-warning">
                    <h6 class="mb-0 text-black fw-bold">
                        <i class="fas fa-edit me-2"></i>Input Peringkat Faktor (Manual)
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Info:</strong> Anda dalam mode input manual. Data tidak akan otomatis berubah jika Laporan
                        Tata Kelola diupdate.
                    </div>
                    <form action="<?= base_url('Tkstatakelola/simpanPeringkatManual') ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="nilaiperingkat" class="fw-bold">
                                        Nilai Peringkat Faktor <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-control form-control-lg" name="nilaiperingkat" id="nilaiperingkat"
                                        required>
                                        <option value="" disabled <?= empty($nilaiperingkat) ? 'selected' : '' ?>>
                                            -- Pilih Peringkat --
                                        </option>
                                        <?php foreach ($peringkatLabels as $nilai => $label): ?>
                                            <option value="<?= $nilai ?>" data-label="<?= $label ?>"
                                                <?= ($nilaiperingkat == $nilai) ? 'selected' : '' ?>>
                                                <?= $nilai ?> - <?= $label ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle"></i>
                                        Pilih nilai antara 1 (Sangat Baik) sampai 5 (Buruk)
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="nilaifaktor_display">
                                        Nilai Faktor (30%)
                                        <i class="fas fa-info-circle text-info" data-toggle="tooltip"
                                            title="Hasil otomatis dari Nilai Peringkat x 30%"></i>
                                    </label>
                                    <input type="text"
                                        class="form-control form-control-lg bg-light font-weight-bold text-primary"
                                        id="nilaifaktor_display" name="nilaifaktor_display"
                                        value="<?= number_format($tkstatakelola['nilaifaktor'] ?? 0, 2) ?>" readonly>
                                    <small class="form-text text-muted">Otomatis: Nilai Peringkat × 30%</small>
                                </div>
                            </div>
                        </div>

                        <!-- Hidden deskripsi -->
                        <input type="hidden" name="deskripsi" id="deskripsi_hidden" value="<?= esc($deskripsiValue) ?>">

                        <div class="alert alert-info" role="alert">
                            <i class="fas fa-info-circle"></i>
                            <strong>Catatan:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Kategori penilaian akan otomatis ditampilkan sesuai nilai yang dipilih</li>
                                <li>Nilai Faktor dihitung otomatis: <strong>Nilai Peringkat × 30%</strong></li>
                                <li>Contoh: Nilai 3 → Nilai Faktor = 3 × 0.30 = <strong>0.90</strong></li>
                            </ul>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Simpan Peringkat
                            </button>
                            <?php if ($canSync): ?>
                                <small class="text-muted">
                                    <i class="fas fa-lightbulb"></i>
                                    Tip: Klik tombol "Sinkron" di atas untuk mengisi otomatis dari Laporan Tata Kelola
                                </small>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 fw-bold">
                        <i class="fas fa-info-circle text-primary"></i> Deskripsi Peringkat
                    </h6>
                    <small class="text-muted">Berdasarkan Matriks Peringkat Faktor Penilaian Tata Kelola</small>
                </div>
                <div class="card-body p-4">
                    <form action="<?= base_url('Tkstatakelola/simpanDeskripsi') ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="form-group mb-3">
                            <label for="deskripsi" class="fw-bold">Deskripsi Detail</label>
                            <textarea class="form-control" name="deskripsi" id="deskripsi" rows="6"
                                placeholder="Masukkan deskripsi lengkap mengenai peringkat tata kelola..."><?= esc($deskripsiValue) ?></textarea>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i>
                                Jelaskan secara detail karakteristik dan kondisi BPR berdasarkan peringkat tata kelola
                            </small>
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Simpan Deskripsi
                        </button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 fw-bold">
                        <i class="fas fa-chart-line text-primary"></i> Analisa / Penjelasan Lebih Lanjut
                    </h6>
                    <small class="text-muted">Analisis komprehensif tata kelola</small>
                </div>
                <div class="card-body p-4">
                    <form action="<?= base_url('Tkstatakelola/simpanAnalisa') ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="form-group mb-3">
                            <label for="analisis" class="fw-bold">Analisis Tata Kelola</label>
                            <textarea class="form-control" name="analisis" id="analisis" rows="8"
                                placeholder="Masukkan analisa komprehensif mengenai tata kelola BPR..."><?= esc($analisisValue) ?></textarea>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i>
                                Jelaskan analisa mendalam terhadap tata kelola secara keseluruhan
                            </small>
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Simpan Analisa
                        </button>
                    </form>
                </div>
            </div>

        <?php endif; ?>

        <!-- Approval Section -->
        <?php if (($userInGroupAdmin ?? false) || ($userInGroupDireksi ?? false)): ?>
            <div class="d-flex justify-content-center mt-4 mb-4">
                <div class="col-md-4 mx-auto">
                    <div class="card shadow-sm approvaldir-card">
                        <div class="card-body approvaldir-card-body">
                            <div class="approval-badge-container">
                                <span class="badge approval-badge">
                                    <i class="fas fa-user-tie"></i> Approval Direktur Utama
                                </span>
                            </div>
                            <div class="approval-buttons-container">
                                <a href="<?= base_url('Tkstatakelola/approve') ?>"
                                    class="btn approval-btn approval-btn-approve <?= !$canApprove || $isApproved ? 'disabled-btn' : '' ?>"
                                    onclick="return <?= $canApprove && !$isApproved ? "confirm('Apakah Anda yakin ingin menyetujui data ini?')" : 'false' ?>;">
                                    <i class="fas fa-check-circle"></i> Approve
                                </a>
                                <a href="<?= base_url('Tkstatakelola/unapprove') ?>"
                                    class="btn approval-btn approval-btn-reject <?= !$isApproved ? 'disabled-btn' : '' ?>"
                                    onclick="return <?= $isApproved ? "confirm('Batalkan persetujuan?')" : 'false' ?>;">
                                    <i class="fas fa-times-circle"></i> Tolak
                                </a>
                            </div>
                            <?php if (!$canApprove): ?>
                                <div class="text-center mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle"></i>
                                        Lengkapi data terlebih dahulu sebelum approval
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Komentar Modal -->
        <div class="modal fade" id="modalKomentar">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-comments"></i>
                            Komentar Direksi dan Dewan Komisaris
                        </h5>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="fw-bold">Riwayat Komentar:</label>
                            <div id="komentarList"
                                style="max-height:300px;overflow-y:auto;border:1px solid #dee2e6;border-radius:8px;padding:15px;background:#f8f9fa;">
                                <div class="text-center text-muted">
                                    <i class="fas fa-spinner fa-spin"></i> Memuat komentar...
                                </div>
                            </div>
                        </div>

                        <?php if (
                            ($userInGroupAdmin ?? false) ||
                            ($userInGroupDekom ?? false) ||
                            ($userInGroupDireksi ?? false) ||
                            ($userInGroupPE ?? false)
                        ): ?>
                            <form id="formKomentar">
                                <?= csrf_field() ?>
                                <input type="hidden" name="fullname" value="<?= esc($fullname ?? '') ?>">
                                <div class="mb-3">
                                    <label for="komentar" class="fw-bold">Tambahkan Komentar Baru:</label>
                                    <textarea class="form-control" name="komentar" id="komentar" rows="4"
                                        placeholder="Tulis komentar Anda di sini..."></textarea>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i> Kirim Komentar
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                Anda tidak memiliki akses untuk menambahkan komentar.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Confirm Action Modal -->
<div class="modal fade" id="modalConfirmAction" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalConfirmTitle"></h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modalConfirmBody"></div>
            <div class="modal-footer" id="modalConfirmFooter">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="button" class="btn btn-primary" id="btnConfirmAction"></button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"
    integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>

<script>
    (function () {
        'use strict';
        const CFG = <?= $jsConfig ?>;

        let lastReadTime = null;
        let pollTimer = null;
        let isPolling = false;
        let commentLoading = false;
        let modalOpen = false;

        const POLL_INTERVAL = 30_000;
        const esc = (s) =>
            String(s).replace(/[&<>"']/g, c =>
                ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c]));

        function formatDateTime(dateStr) {
            const date = new Date(dateStr);
            const now = new Date();
            const diffMs = now - date;
            const diffMin = Math.floor(diffMs / 60_000);
            const diffH = Math.floor(diffMs / 3_600_000);
            const diffD = Math.floor(diffMs / 86_400_000);

            if (diffMin < 1) return 'Baru saja';
            if (diffMin < 60) return diffMin + ' menit yang lalu';
            if (diffH < 24) return diffH + ' jam yang lalu';
            if (diffD < 7) return diffD + ' hari yang lalu';

            const d = String(date.getDate()).padStart(2, '0');
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const y = date.getFullYear();
            const hh = String(date.getHours()).padStart(2, '0');
            const mm = String(date.getMinutes()).padStart(2, '0');
            return d + '/' + m + '/' + y + ' ' + hh + ':' + mm;
        }

        function showToast(type, message) {
            const bg = type === 'success' ? '#28a745' : '#dc3545';
            const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
            const $t = $('<div>')
                .css({
                    position: 'fixed', top: '20px', right: '20px',
                    background: bg, color: '#fff', padding: '15px 20px',
                    borderRadius: '8px', boxShadow: '0 4px 12px rgba(0,0,0,.3)',
                    zIndex: 9999, display: 'flex', alignItems: 'center', gap: '10px'
                })
                .html('<i class="fas fa-' + icon + '" style="font-size:20px"></i><span>' + esc(message) + '</span>');
            $('body').append($t);
            setTimeout(() => $t.fadeOut(300, function () { $(this).remove(); }), 3000);
        }

        /* Unread-count badge */
        function updateBadge(count) {
            const $b = $('#notification-badge');
            $b.text(count);
            count > 0 ? $b.fadeIn(200) : $b.fadeOut(200);
        }

        function pollUnreadCount() {
            if (document.hidden || isPolling) return;
            isPolling = true;
            $.ajax({
                url: CFG.baseUrl + '/Tkstatakelola/getUnreadCount',
                method: 'GET',
                dataType: 'json',
                timeout: 8000,
                success: function (r) { if (r.status === 'success') updateBadge(r.count); },
                complete: function () { isPolling = false; }
            });
        }

        function startPolling() {
            if (pollTimer) return;
            pollUnreadCount();
            pollTimer = setInterval(pollUnreadCount, POLL_INTERVAL);
        }

        function stopPolling() {
            clearInterval(pollTimer);
            pollTimer = null;
        }

        document.addEventListener('visibilitychange', function () {
            document.hidden ? stopPolling() : startPolling();
        });

        /* Comments */
        function isNewComment(createdAt) {
            if (!lastReadTime) return true;
            return new Date(createdAt).getTime() > new Date(lastReadTime).getTime();
        }

        function buildCommentHtml(list) {
            if (!list || list.length === 0) {
                return '<div class="text-center text-muted py-4">' +
                    '<i class="fas fa-inbox fa-3x mb-3" style="opacity:.3"></i>' +
                    '<p>Belum ada komentar</p></div>';
            }
            return list.map(function (k) {
                const isNew = isNewComment(k.created_at);
                const border = isNew ? '#28a745' : '#007bff';
                const bg = isNew ? '#d4edda' : '#fff';
                const newBadge = isNew
                    ? '<span class="badge badge-success ms-2"><i class="fas fa-star"></i> BARU</span>' : '';
                return '<div class="komentar-item mb-3 p-3" ' +
                    'style="background:' + bg + ';border-radius:6px;border-left:3px solid ' + border + '">' +
                    '<div class="d-flex justify-content-between align-items-start mb-2">' +
                    '<strong style="color:' + border + '"><i class="fas fa-user-circle"></i> ' +
                    esc(k.fullname || 'Unknown') + newBadge + '</strong>' +
                    '<small class="text-muted"><i class="fas fa-clock"></i> ' + formatDateTime(k.created_at) + '</small>' +
                    '</div>' +
                    '<p class="mb-0" style="white-space:pre-wrap">' + esc(k.komentar) + '</p></div>';
            }).join('');
        }

        function loadKomentarList(scrollToBottom) {
            if (commentLoading) return;
            commentLoading = true;
            $.ajax({
                url: CFG.baseUrl + '/Tkstatakelola/getKomentar',
                method: 'GET',
                dataType: 'json',
                timeout: 10000,
                success: function (list) {
                    const $c = $('#komentarList');
                    const html = buildCommentHtml(list);
                    $c.html(html);
                    const hasNew = Array.isArray(list) && list.some(k => isNewComment(k.created_at));
                    if (scrollToBottom || hasNew) {
                        requestAnimationFrame(function () {
                            $c.animate({ scrollTop: $c[0].scrollHeight }, 400);
                        });
                    }
                },
                error: function () {
                    $('#komentarList').html(
                        '<div class="alert alert-danger">' +
                        '<i class="fas fa-exclamation-triangle"></i> Gagal memuat komentar</div>'
                    );
                },
                complete: function () { commentLoading = false; }
            });
        }

        function markAsRead() {
            const data = {};
            data[CFG.csrfName] = CFG.csrfHash;
            $.ajax({
                url: CFG.baseUrl + '/Tkstatakelola/markCommentsAsRead',
                method: 'POST',
                data: data,
                dataType: 'json',
                timeout: 8000,
                success: function (r) {
                    if (r.status === 'success') {
                        updateBadge(0);
                        lastReadTime = new Date().toISOString();
                    }
                }
            });
        }

        function performAjaxAction(opts) {
            const $modal = $('#modalConfirmAction');
            const $title = $('#modalConfirmTitle');
            const $body = $('#modalConfirmBody');
            const $footer = $('#modalConfirmFooter');
            const $btnConfirm = $('#btnConfirmAction');
            const $btn = $(opts.btn);
            const origHtml = $btn.html();

            // Set konten modal
            $title.html(opts.confirmTitle);
            $body.html(opts.confirmHtml);
            $btnConfirm
                .html(opts.confirmLabel)
                .css({
                    'background-color': opts.confirmColor || '#3b82f6',
                    'border-color': opts.confirmColor || '#3b82f6'
                });

            // Reset footer & tampilkan
            $footer.show();

            // Tampilkan modal
            $modal.modal('show');

            // Hapus event lama agar tidak dobel
            $btnConfirm.off('click').on('click', function () {
                $modal.modal('hide');
                $btn.html('<i class="fas fa-spinner fa-spin"></i> Memproses...').prop('disabled', true);

                const payload = {};
                payload[CFG.csrfName] = CFG.csrfHash;

                $.ajax({
                    url: opts.url,
                    method: 'POST',
                    data: payload,
                    dataType: 'json',
                    timeout: 30_000,
                    beforeSend: function (xhr) {
                        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    },
                    success: function (r) {
                        if (r && r.success) {
                            showResultModal('success', 'Berhasil!', r.message || opts.successMsg);
                            setTimeout(function () { location.reload(); }, 1500);
                        } else {
                            showResultModal('error', 'Gagal', r.message || 'Terjadi kesalahan');
                            $btn.html(origHtml).prop('disabled', false);
                        }
                    },
                    error: function (xhr) {
                        const msgs = {
                            0: 'Tidak dapat terhubung ke server',
                            403: 'Akses ditolak (403)',
                            404: 'Halaman tidak ditemukan (404)',
                            500: 'Server error (500)'
                        };
                        showResultModal('error', 'Error', msgs[xhr.status] || ('Error ' + xhr.status));
                        $btn.html(origHtml).prop('disabled', false);
                    }
                });
            });
        }

        function showResultModal(type, title, message) {
            const $modal = $('#modalConfirmAction');
            const isOk = type === 'success';
            const icon = isOk
                ? '<i class="fas fa-check-circle fa-3x text-success mb-3"></i>'
                : '<i class="fas fa-times-circle fa-3x text-danger mb-3"></i>';

            $('#modalConfirmTitle').html(
                '<span class="' + (isOk ? 'text-success' : 'text-danger') + '">' + title + '</span>'
            );
            $('#modalConfirmBody').html(
                '<div class="text-center py-2">' + icon + '<p class="mb-0 mt-2">' + message + '</p></div>'
            );
            $('#modalConfirmFooter').hide();
            $modal.modal('show');
        }

        $(function () {

            /* Tooltips */
            $('[data-toggle="tooltip"]').tooltip();

            /* nilaiperingkat select */
            $('#nilaiperingkat').on('change', function () {
                const val = this.value;
                const label = $(this).find('option:selected').data('label') || '';
                $('#deskripsi_hidden').val(label);
                $('#nilaifaktor_display').val(val ? (parseFloat(val) * 0.30).toFixed(2) : '0.00');
            });

            /* nilaiperingkatbefore select */
            function updatePeringkatBefore(val) {
                if (val && CFG.peringkatLabels[val]) {
                    $('#peringkatbefore').val(CFG.peringkatLabels[val]);
                    $('#nilaifaktorbefore').val((parseFloat(val) * 0.30).toFixed(2));
                } else {
                    $('#peringkatbefore').val('-');
                    $('#nilaifaktorbefore').val('0.00');
                }
            }

            $('#modalConfirmAction').on('hidden.bs.modal', function () {
                $('#modalConfirmFooter').show();
                $('#modalConfirmBody').html('');
                $('#modalConfirmTitle').html('');
            });

            $('#nilaiperingkatbefore').on('change', function () {
                updatePeringkatBefore(this.value);
            });

            const initBefore = $('#nilaiperingkatbefore').val();
            if (initBefore) updatePeringkatBefore(initBefore);

            /* Komentar button */
            $('#btnKomentar').on('click', function () {
                if (!modalOpen) {
                    loadKomentarList(true);
                    markAsRead();
                }
                $('#modalKomentar').modal('show');
            });

            $('#modalKomentar')
                .on('shown.bs.modal', function () { modalOpen = true; })
                .on('hidden.bs.modal', function () { modalOpen = false; });

            /* Comment form submit */
            $('#formKomentar').on('submit', function (e) {
                e.preventDefault();
                const $form = $(this);
                const $btn = $form.find('button[type="submit"]');
                const origHtml = $btn.html();
                const txt = $form.find('[name="komentar"]').val().trim();

                if (!txt) { alert('Komentar tidak boleh kosong'); return; }

                $btn.html('<i class="fas fa-spinner fa-spin"></i> Mengirim...').prop('disabled', true);

                $.ajax({
                    url: CFG.baseUrl + '/Tkstatakelola/tambahKomentar',
                    method: 'POST',
                    data: $form.serialize(),
                    dataType: 'json',
                    timeout: 15_000,
                    success: function (r) {
                        if (r.status === 'success') {
                            $form.find('[name="komentar"]').val('');
                            loadKomentarList(true);
                            showToast('success', 'Komentar berhasil dikirim');
                        } else {
                            showToast('error', r.message || 'Gagal menambahkan komentar');
                        }
                    },
                    error: function () { showToast('error', 'Gagal menambahkan komentar'); },
                    complete: function () { $btn.html(origHtml).prop('disabled', false); }
                });
            });

            /* Sync button */
            $('#btnSyncTatakelola').on('click', function (e) {
                e.preventDefault();
                if ($(this).is(':disabled') || $(this).prop('disabled')) return;

                const curLabel = 'Semester ' + CFG.semester + ' Tahun ' + CFG.tahun;
                const beforeLabel = CFG.beforeAvailable
                    ? 'Semester ' + CFG.prevSemester + ' Tahun ' + CFG.prevTahun
                    : null;

                let html = '<div style="text-align:left">' +
                    '<p>Apakah Anda yakin ingin melakukan sinkronisasi dengan Laporan Tata Kelola?</p><br>' +
                    '<small class="text-muted">Data yang akan diambil &amp; disimpan:</small>' +
                    '<ul style="text-align:left;margin-top:10px">' +
                    '<li>Nilai Peringkat &amp; Nilai Faktor (30%) &mdash; dari <strong>' + curLabel + '</strong></li>';

                if (beforeLabel) {
                    html += '<li>Nilai Peringkat &amp; Nilai Faktor Periode Sebelumnya &mdash; dari <strong>' + beforeLabel + '</strong></li>';
                } else {
                    html += '<li class="text-warning">Data periode sebelumnya belum tersedia &mdash; nilai "sebelumnya" tidak akan diisi</li>';
                }

                html += '<li>Deskripsi &amp; Analisis &mdash; dari Kesimpulan Laporan Tata Kelola <strong>' + curLabel + '</strong></li></ul>' +
                    '<p class="text-warning mt-2"><strong>Catatan:</strong> Status approval akan direset.</p></div>';

                performAjaxAction({
                    btn: this,
                    url: CFG.baseUrl + '/Tkstatakelola/syncWithTatakelola',
                    confirmTitle: 'Konfirmasi Sinkronisasi',
                    confirmColor: '#3b82f6',
                    confirmLabel: '<i class="fas fa-sync-alt"></i> Ya, Sinkronkan',
                    successMsg: 'Data berhasil disinkronkan',
                    confirmHtml: html
                });
            });

            /* Unsync button */
            $('#btnUnsync').on('click', function (e) {
                e.preventDefault();
                performAjaxAction({
                    btn: this,
                    url: CFG.baseUrl + '/Tkstatakelola/unsyncWithTatakelola',
                    confirmTitle: 'Konfirmasi Lepas Sinkronisasi',
                    icon: 'warning',
                    confirmColor: '#f59e0b',
                    confirmLabel: '<i class="fas fa-unlink"></i> Ya, Lepas Sinkronisasi',
                    successMsg: 'Sinkronisasi berhasil dibatalkan',
                    confirmHtml:
                        '<div style="text-align:left">' +
                        '<p>Apakah Anda yakin ingin melepas sinkronisasi?</p><br>' +
                        '<small class="text-muted">Setelah dilepas, Anda dapat mengedit data secara manual dan ' +
                        'data tidak akan otomatis berubah jika Laporan Tata Kelola diupdate.</small></div>'
                });
            });
            startPolling();
        });
    })();
</script>

<style>
    /* Layout */
    body {
        background-color: #f8f9fa;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        font-size: 15px;
    }

    .main-content {
        max-width: 1400px;
        margin: 0 auto;
    }

    /* Cards */
    .card {
        border-radius: 12px;
        border: none;
        transition: transform .3s ease, box-shadow .3s ease;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, .1) !important;
    }

    .shadow-sm {
        box-shadow: 0 2px 8px rgba(0, 0, 0, .08) !important;
    }

    .card-header {
        background: #fff !important;
        border-bottom: 1px solid #e5e7eb !important;
    }

    /* Buttons */
    .btn {
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 500;
        font-size: 15px;
        transition: all .3s ease;
    }

    .btn-sync {
        background: linear-gradient(135deg, #6366f1, #3b82f6);
        color: #fff;
        border: none;
    }

    .btn-sync:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(99, 102, 241, .4);
        color: #fff;
    }

    .btn-unsync {
        background-color: #f59e0b;
        color: #fff;
        border: none;
    }

    .btn-unsync:hover {
        background-color: #ea580c;
        color: #fff;
    }

    .btn-primary {
        background: linear-gradient(135deg, #3b82f6, #6366f1);
        border: none;
    }

    .btn-success {
        background: linear-gradient(135deg, #10b981, #059669);
        border: none;
    }

    .btn-secondary {
        background-color: #e5e7eb;
        color: #374151;
        border: none;
    }

    .btn-outline-secondary {
        border: 2px solid #6b7280;
        color: #6b7280;
    }

    .btn-outline-secondary:hover {
        background-color: #6b7280;
        color: #fff;
    }

    /* Alerts */
    .alert {
        border-radius: 12px;
        padding: 24px;
        border: none;
    }

    .alert-sync-success {
        background: linear-gradient(135deg, #ecfdf5, #d1fae5);
        border: 2px solid #10b981;
    }

    .alert-sync-warning {
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        border: 2px solid #f59e0b;
    }

    .alert-sync-danger {
        background: linear-gradient(135deg, #fee2e2, #fecaca);
        border: 2px solid #ef4444;
    }

    .alert-info {
        background: linear-gradient(135deg, #dbeafe, #bfdbfe);
        color: #1e40af;
    }

    /* Sync icons */
    .sync-icon {
        font-size: 36px;
        color: #10b981;
    }

    .sync-icon-warning {
        font-size: 36px;
        color: #f59e0b;
    }

    .sync-info-box {
        background: rgba(255, 255, 255, .5);
        border-radius: 8px;
        padding: 15px;
        border-left: 4px solid #f59e0b;
    }

    /* Info boxes */
    .info-box {
        background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
        border-radius: 12px;
        padding: 1.5rem;
        transition: all .3s ease;
        border: 1px solid #e9ecef;
    }

    .info-box:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, .1) !important;
    }

    .info-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .info-label {
        font-size: .85rem;
        font-weight: 500;
    }

    .info-value {
        font-size: 2rem;
        font-weight: 700;
        color: #2c3e50;
    }

    /* Badges */
    .badge-lg {
        border-radius: 8px;
        letter-spacing: .5px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .1);
    }

    .badge-success {
        background-color: #10b981;
        color: #fff;
    }

    .badge-info {
        background-color: #3b82f6;
        color: #fff;
    }

    .badge-warning {
        background-color: #f59e0b;
        color: #fff;
    }

    .badge-orange {
        background-color: #f97316;
        color: #fff;
    }

    .badge-danger {
        background-color: #ef4444;
        color: #fff;
    }

    .badge-secondary {
        background-color: #6b7280;
        color: #fff;
    }

    /* Notification badge */
    .notification-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        font-size: 11px;
        padding: 3px 7px;
        border-radius: 50%;
        background-color: #dc3545;
        color: #fff;
        font-weight: 700;
        min-width: 20px;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0, 0, 0, .2);
    }

    .komentar-button {
        position: relative;
    }

    /* Forms */
    .form-control {
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        padding: 14px;
        font-size: 15px;
        height: 52px;
    }

    .form-control:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, .1);
    }

    .form-control-lg {
        height: 52px;
    }

    .bg-gradient-warning {
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    }

    /* Synced content */
    .synced-content-box {
        background: #f9fafb;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        padding: 20px;
    }

    .synced-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #ecfdf5;
        color: #059669;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 15px;
    }

    .content-display {
        color: #374151;
        line-height: 1.8;
        font-size: 15px;
    }

    /* Approval card */
    .approvaldir-card {
        border-radius: 12px;
        border: 2px solid #e5e7eb;
        min-height: 140px;
    }

    .approvaldir-card-body {
        padding: 1.5rem;
    }

    .approval-badge-container {
        text-align: center;
        margin-bottom: 1rem;
    }

    .approval-badge {
        background-color: #343a40;
        color: #fff;
        font-size: .9rem;
        font-weight: 600;
        padding: 8px 16px;
        border-radius: 6px;
        display: inline-block;
        box-shadow: 0 2px 4px rgba(0, 0, 0, .1);
    }

    .approval-buttons-container {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 12px;
    }

    .approval-btn {
        flex: 1;
        max-width: 48%;
        padding: 10px 16px;
        font-size: .95rem;
        font-weight: 500;
        border-radius: 8px;
        text-align: center;
        text-decoration: none;
        white-space: nowrap;
        box-shadow: 0 2px 4px rgba(0, 0, 0, .1);
        transition: all .3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    .approval-btn-approve {
        background-color: #28a745;
        border-color: #28a745;
        color: #fff;
    }

    .approval-btn-approve:hover:not(.disabled-btn) {
        background-color: #218838;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, .2);
        color: #fff;
    }

    .approval-btn-reject {
        background-color: #dc3545;
        border-color: #dc3545;
        color: #fff;
    }

    .approval-btn-reject:hover:not(.disabled-btn) {
        background-color: #c82333;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, .2);
        color: #fff;
    }

    .disabled-btn {
        opacity: .5;
        pointer-events: none;
        cursor: not-allowed;
    }

    #approval-badge-main {
        transition: all .3s ease;
        animation: fadeIn .5s ease;
    }

    .komentar-item {
        transition: transform .2s ease;
    }

    .komentar-item:hover {
        transform: translateX(5px);
    }

    #komentarList::-webkit-scrollbar {
        width: 8px;
    }

    #komentarList::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    #komentarList::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 4px;
    }

    #komentarList::-webkit-scrollbar-thumb:hover {
        background: #555;
    }

    .empty-state {
        animation: fadeIn .5s ease-in-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px)
        }

        to {
            opacity: 1;
            transform: translateY(0)
        }
    }

    /* Mobile view */
    @media(max-width:768px) {
        .btn {
            padding: 10px 18px;
            font-size: 14px;
        }

        .sync-icon,
        .sync-icon-warning {
            font-size: 28px;
        }

        .approval-btn {
            font-size: .85rem;
            padding: 8px 12px;
        }

        .approvaldir-card {
            min-height: auto;
        }
    }
</style>