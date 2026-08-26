<?php
$peringkatLabels = [
    '1' => 'Sangat Rendah',
    '2' => 'Rendah',
    '3' => 'Sedang',
    '4' => 'Tinggi',
    '5' => 'Sangat Tinggi'
];

function getBadgeClass($nilai)
{
    switch ($nilai) {
        case '1':
            return 'badge-info';
        case '2':
            return 'badge-success';
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
$canSync = isset($syncStatus) && $syncStatus['can_sync'];

$rataRata = 0;
$nilaiperingkat = 0;
$nilaifaktor = 0;
$nilaiperingkatbefore = 0;
$nilaifaktorbefore = 0;
$peringkatLabel = 'Belum Ada Data';
$deskripsiValue = '';
$analisisValue = '';

if ($isSynced && isset($syncStatus['ntotalrisk'])) {
    $rataRata = $syncStatus['ntotalrisk'];
    $nilaiperingkat = $syncStatus['ntotalrisk'];
    $nilaifaktor = round($nilaiperingkat * 0.25, 2);

    if ($syncStatus['before_available']) {
        // Data periode sebelumnya tersedia otomatis dari Laporan Profil Risiko
        $nilaiperingkatbefore = $syncStatus['nbelumtotalrisk'] ?? 0;
        $nilaifaktorbefore = $nilaiperingkatbefore > 0 ? round($nilaiperingkatbefore * 0.25, 2) : 0;
    } else {
        // Fallback: pakai nilai yang sudah diisi manual oleh user (tersimpan di tksprofilrisiko)
        $nilaiperingkatbefore = $tksprofilrisiko['nilaiperingkatbefore'] ?? 0;
        $nilaifaktorbefore = $tksprofilrisiko['nilaifaktorbefore'] ?? 0;
    }

    $peringkatLabel = $peringkatLabels[$nilaiperingkat] ?? 'Tidak Valid';
    $deskripsiValue = $tksprofilrisiko['deskripsi'] ?? '';
    $analisisValue = $syncStatus['kesimpulan'] ?? '';
} elseif (isset($tksprofilrisiko) && !empty($tksprofilrisiko)) {
    $rataRata = $tksprofilrisiko['ratarata'] ?? 0;
    $nilaiperingkat = $tksprofilrisiko['nilaiperingkat'] ?? 0;
    $nilaifaktor = $tksprofilrisiko['nilaifaktor'] ?? 0;
    $nilaiperingkatbefore = $tksprofilrisiko['nilaiperingkatbefore'] ?? 0;
    $nilaifaktorbefore = $tksprofilrisiko['nilaifaktorbefore'] ?? 0;
    $peringkatLabel = $tksprofilrisiko['peringkat'] ?? 'Tidak Valid';
    $deskripsiValue = $tksprofilrisiko['deskripsi'] ?? '';
    $analisisValue = $tksprofilrisiko['analisis'] ?? '';
}

$badgeClass = getBadgeClass($nilaiperingkat);

// View: jsConfig
$jsConfig = json_encode([
    'baseUrl' => base_url(),
    'csrfName' => csrf_token(),
    'csrfHash' => csrf_hash(),
    'initialUnread' => (int) ($unreadCount ?? 0),
    'syncCurrentSemester' => $syncStatus['current_semester'] ?? null,
    'syncCurrentTahun' => $syncStatus['current_tahun'] ?? null,
    'syncBeforeSemester' => $syncStatus['before_semester'] ?? null,
    'syncBeforeTahun' => $syncStatus['before_tahun'] ?? null,
    'syncBeforeAvailable' => $syncStatus['before_available'] ?? false,
], JSON_HEX_TAG | JSON_HEX_AMP);
?>

<div class="alert alert-info my-2">
    <?php if (isset($bprData) && isset($periodeDetail)): ?>
        <strong><?= esc($bprData['namabpr'] ?? 'Nama BPR') ?></strong> - Periode Pelaporan
        Semester <?= esc($periodeDetail['semester']) ?> Tahun <?= esc($periodeDetail['tahun']) ?>
    <?php elseif (isset($periodeDetail)): ?>
        <strong>Periode:</strong> Semester <?= esc($periodeDetail['semester']) ?> Tahun <?= esc($periodeDetail['tahun']) ?>
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
                            <i class="fas fa-shield-alt me-2 text-primary"></i>
                            Faktor 1. Penilaian Profil Risiko
                        </h4>
                        <p class="text-muted mb-0 mt-2">
                            <small>Penilaian profil risiko berdasarkan data Laporan Profil Risiko periode yang
                                sama</small>
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
                            style="margin-left:15px;" id="btnKomentar" aria-label="Buka komentar">
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
                                    <i class="fas fa-link me-2"></i> Tersinkron dengan Laporan Profil Risiko
                                </h6>
                                <p class="mb-1">
                                    <strong>Periode:</strong> Semester <?= $periodeDetail['semester'] ?? '1' ?>
                                    Tahun <?= $periodeDetail['tahun'] ?? date('Y') ?>
                                </p>
                                <p class="mb-0">
                                    <i class="fas fa-clock text-success"></i>
                                    Disinkronkan pada:
                                    <strong><?= date('d M Y H:i', strtotime($syncStatus['sync_date'] ?? 'now')) ?></strong>
                                </p>

                                <?php if (!($syncStatus['before_available'] ?? false)): ?>
                                    <div class="alert alert-warning mt-3 mb-0 py-2 px-3">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Nilai peringkat periode sebelumnya belum terisi otomatis (data Laporan Profil Risiko
                                        Semester <?= $syncStatus['before_semester'] ?? '-' ?> Tahun
                                        <?= $syncStatus['before_tahun'] ?? '-' ?> belum tersedia).
                                        Silakan lengkapi secara manual pada form di bawah.
                                    </div>
                                <?php endif; ?>
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
                                <!-- View: block "Data Belum Disinkronkan" -->
                                <?php if ($canSync): ?>
                                    <h6 class="mb-2 fw-bold"><i class="fas fa-info-circle me-2"></i>Data Belum Disinkronkan</h6>
                                    <p class="mb-2">
                                        Data Laporan Profil Risiko tersedia untuk periode
                                        <strong>Semester <?= $syncStatus['current_semester'] ?> Tahun
                                            <?= $syncStatus['current_tahun'] ?></strong>.
                                        Klik tombol di bawah untuk menyinkronkan data secara otomatis atau isi manual.
                                    </p>
                                    <div class="sync-info-box mt-3">
                                        <p class="mb-1"><strong>Data yang akan diambil &amp; disimpan:</strong></p>
                                        <ul class="mb-0">
                                            <li>
                                                Nilai Peringkat (Semester <?= $syncStatus['current_semester'] ?> Tahun
                                                <?= $syncStatus['current_tahun'] ?>):
                                                <span class="badge <?= getBadgeClass($syncStatus['ntotalrisk']) ?>">
                                                    <?= $syncStatus['ntotalrisk'] ?> -
                                                    <?= $peringkatLabels[$syncStatus['ntotalrisk']] ?? 'N/A' ?>
                                                </span>
                                            </li>
                                            <li>Nilai Faktor: <strong><?= round($syncStatus['ntotalrisk'] * 0.25, 2) ?></strong>
                                                (<?= $syncStatus['ntotalrisk'] ?> × 25%)</li>

                                            <?php if ($syncStatus['before_available']): ?>
                                                <li>
                                                    Nilai Peringkat Periode Sebelumnya (Semester <?= $syncStatus['before_semester'] ?>
                                                    Tahun <?= $syncStatus['before_tahun'] ?>):
                                                    <span class="badge <?= getBadgeClass($syncStatus['nbelumtotalrisk']) ?>">
                                                        <?= $syncStatus['nbelumtotalrisk'] ?> -
                                                        <?= $peringkatLabels[$syncStatus['nbelumtotalrisk']] ?? 'N/A' ?>
                                                    </span>
                                                </li>
                                                <li>Nilai Faktor Periode Sebelumnya:
                                                    <strong><?= round($syncStatus['nbelumtotalrisk'] * 0.25, 2) ?></strong>
                                                    (<?= $syncStatus['nbelumtotalrisk'] ?> × 25%)
                                                </li>
                                            <?php else: ?>
                                                <li class="text-warning">
                                                    <i class="fas fa-info-circle"></i>
                                                    Data periode sebelumnya (Semester <?= $syncStatus['before_semester'] ?> Tahun
                                                    <?= $syncStatus['before_tahun'] ?>) <strong>belum tersedia</strong>.
                                                    Sinkronisasi <strong>tetap bisa dilakukan</strong> — nilai "Periode Sebelumnya"
                                                    cukup diisi secara manual setelah sinkron selesai.
                                                </li>
                                            <?php endif; ?>

                                            <li>Analisis / Kesimpulan (Semester <?= $syncStatus['current_semester'] ?> Tahun
                                                <?= $syncStatus['current_tahun'] ?>)
                                            </li>
                                        </ul>
                                    </div>
                                <?php else: ?>
                                    <h6 class="mb-2 fw-bold"><i class="fas fa-exclamation-circle me-2"></i> Data Laporan Profil
                                        Risiko Tidak Tersedia</h6>
                                    <p class="mb-0">
                                        Belum ada data Laporan Profil Risiko untuk periode Semester
                                        <?= $syncStatus['current_semester'] ?? '1' ?> Tahun
                                        <?= $syncStatus['current_tahun'] ?? date('Y') ?>.
                                        Silakan lengkapi keseluruhan Laporan Profil Risiko terlebih dahulu sebelum melakukan
                                        sinkronisasi.
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Ringkasan Nilai Section -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-gradient-primary">
                <h6 class="mb-0 text-black fw-bold">
                    <i class="fas fa-chart-bar me-2"></i> Ringkasan Penilaian Profil Risiko
                </h6>
            </div>
            <div class="card-body p-4">
                <?php if ($nilaiperingkat > 0): ?>
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="info-box shadow-sm border-0 h-100">
                                <div class="d-flex align-items-center h-100">
                                    <div class="info-icon me-3">
                                        <i class="fas fa-star fa-2x text-warning"></i>
                                    </div>
                                    <div class="flex-grow-1 ml-3">
                                        <div class="info-label text-muted mb-1">Nilai Peringkat</div>
                                        <div class="info-value"><?= $nilaiperingkat ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box shadow-sm border-0 h-100">
                                <div class="d-flex align-items-center h-100">
                                    <div class="info-icon me-3">
                                        <i class="fas fa-calculator fa-2x text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1 ml-3">
                                        <div class="info-label text-muted mb-1">Nilai Faktor (25%)</div>
                                        <div class="info-value"><?= $nilaifaktor ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
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

                        <?php if ($nilaiperingkatbefore > 0): ?>
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
                                                    <?= $peringkatLabels[$nilaiperingkatbefore] ?? '' ?>
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
                                            <div class="info-label text-muted mb-1">Nilai Faktor Periode Sebelumnya (25%)</div>
                                            <div style="font-size:1.5rem;font-weight:700;color:#2c3e50;">
                                                <?= $nilaifaktorbefore ?>
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
                    <button class="btn btn-sync btn-block" id="btnSyncProfilRisiko">
                        <i class="fas fa-sync-alt me-2"></i> Sinkron Dengan Laporan Profil Risiko
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <!-- SYNCED STATE -->
        <!-- SYNCED STATE -->
        <?php if ($isSynced): ?>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-gradient-warning">
                    <h6 class="mb-0 text-black fw-bold">
                        <i class="fas fa-edit me-2"></i>
                        Nilai Peringkat Periode Sebelumnya (Manual)
                    </h6>
                </div>
                <div class="card-body p-4">
                    <?php if ($syncStatus['before_available'] ?? false): ?>
                        <div class="alert alert-info mb-3">
                            <i class="fas fa-info-circle"></i>
                            Nilai berikut sudah terisi otomatis dari Laporan Profil Risiko
                            Semester <?= $syncStatus['before_semester'] ?? '-' ?> Tahun
                            <?= $syncStatus['before_tahun'] ?? '-' ?>.
                            Anda tetap bisa mengubahnya secara manual jika diperlukan.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mb-3">
                            <i class="fas fa-exclamation-triangle"></i>
                            Data Laporan Profil Risiko periode
                            Semester <?= $syncStatus['before_semester'] ?? '-' ?> Tahun
                            <?= $syncStatus['before_tahun'] ?? '-' ?>
                            belum tersedia untuk diambil otomatis. Silakan isi nilai peringkat periode sebelumnya secara manual
                            di bawah ini.
                        </div>
                    <?php endif; ?>

                    <form action="<?= base_url('Tksprofilrisiko/simpanPeringkatBeforeManual') ?>" method="post"
                        class="row g-3">
                        <?= csrf_field() ?>
                        <div class="col-md-6">
                            <label for="nilaiperingkatbefore_manual" class="fw-bold">
                                Nilai Peringkat Periode Sebelumnya <span class="text-danger">*</span>
                            </label>
                            <select class="form-control form-control-lg" name="nilaiperingkatbefore_manual"
                                id="nilaiperingkatbefore_manual" required>
                                <option value="" disabled <?= empty($nilaiperingkatbefore) ? 'selected' : '' ?>>
                                    -- Pilih Peringkat --
                                </option>
                                <?php foreach ($peringkatLabels as $nilai => $label): ?>
                                    <option value="<?= $nilai ?>" <?= ($nilaiperingkatbefore == $nilai) ? 'selected' : '' ?>>
                                        <?= $nilai ?> - <?= $label ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="nilaifaktorbefore_manual_display" class="fw-bold">
                                Nilai Faktor Periode Sebelumnya (25%)
                            </label>
                            <input type="text" class="form-control form-control-lg bg-light"
                                id="nilaifaktorbefore_manual_display" value="<?= $nilaifaktorbefore ?>" readonly>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Simpan Nilai Sebelumnya
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 fw-bold text-success">
                        <i class="fas fa-lock me-2"></i> Analisa / Penjelasan
                        <span class="badge badge-success float-end">Tersinkron</span>
                    </h6>
                    <small class="text-muted">Kesimpulan dari Laporan Profil Risiko</small>
                </div>
                <div class="card-body p-4">
                    <div class="synced-content-box">
                        <div class="synced-badge">
                            <i class="fas fa-check-circle text-success"></i>
                            <span>Data dari Laporan Profil Risiko</span>
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

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 fw-bold">
                        <i class="fas fa-info-circle text-primary"></i> Deskripsi Tambahan
                    </h6>
                    <small class="text-muted">Deskripsi dapat diisi secara manual</small>
                </div>
                <div class="card-body p-4">
                    <form action="<?= base_url('Tksprofilrisiko/simpanDeskripsi') ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="form-group mb-3">
                            <label for="deskripsi" class="fw-bold">Deskripsi Detail</label>
                            <textarea class="form-control" name="deskripsi" id="deskripsi" rows="6"
                                placeholder="Masukkan deskripsi lengkap mengenai peringkat profil risiko..."><?= esc($deskripsiValue) ?></textarea>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i>
                                Jelaskan secara detail karakteristik dan kondisi BPR berdasarkan peringkat profil risiko
                            </small>
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Simpan Deskripsi
                        </button>
                    </form>
                </div>
            </div>

        <?php else: ?>
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-gradient-warning">
                    <h6 class="mb-0 text-black fw-bold">
                        <i class="fas fa-edit me-2"></i> Input Peringkat Faktor (Manual)
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Info:</strong> Anda dalam mode input manual. Nilai faktor akan otomatis dihitung (25% dari
                        nilai peringkat).
                    </div>
                    <form action="<?= base_url('Tksprofilrisiko/simpanPeringkatManual') ?>" method="post"
                        id="formPeringkatManual">
                        <?= csrf_field() ?>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="nilaiperingkat" class="fw-bold">
                                        Nilai Peringkat <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-control form-control-lg" name="nilaiperingkat" id="nilaiperingkat"
                                        required>
                                        <option value="" disabled <?= empty($nilaiperingkat) ? 'selected' : '' ?>>
                                            -- Pilih Peringkat --
                                        </option>
                                        <?php foreach ($peringkatLabels as $nilai => $label): ?>
                                            <option value="<?= $nilai ?>" <?= ($nilaiperingkat == $nilai) ? 'selected' : '' ?>>
                                                <?= $nilai ?> - <?= $label ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="form-text text-muted">Pilih nilai antara 1 (Sangat Rendah) sampai 5
                                        (Sangat Tinggi)</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="nilaifaktor_display" class="fw-bold">
                                        Nilai Faktor (25% dari Nilai Peringkat)
                                    </label>
                                    <input type="text" class="form-control form-control-lg bg-light"
                                        id="nilaifaktor_display" value="<?= $nilaifaktor ?>" readonly>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-calculator"></i> Otomatis dihitung: Nilai Peringkat × 25%
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="nilaiperingkatbefore" class="fw-bold">
                                        Nilai Peringkat Periode Sebelumnya
                                    </label>
                                    <select class="form-control form-control-lg" name="nilaiperingkatbefore"
                                        id="nilaiperingkatbefore">
                                        <option value="">-- Pilih Peringkat (Opsional) --</option>
                                        <?php foreach ($peringkatLabels as $nilai => $label): ?>
                                            <option value="<?= $nilai ?>" <?= ($nilaiperingkatbefore == $nilai) ? 'selected' : '' ?>><?= $nilai ?> - <?= $label ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select><small class="form-text text-muted">Opsional: Nilai peringkat pada periode
                                        sebelumnya</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="nilaifaktorbefore_display" class="fw-bold">
                                        Nilai Faktor Periode Sebelumnya (25%)
                                    </label>
                                    <input type="text" class="form-control form-control-lg bg-light"
                                        id="nilaifaktorbefore_display" value="<?= $nilaifaktorbefore ?>" readonly>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-calculator"></i> Otomatis dihitung: Nilai Peringkat Before × 25%
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Simpan Peringkat
                            </button>
                            <?php if ($canSync): ?>
                                <small class="text-muted">
                                    <i class="fas fa-lightbulb"></i>
                                    Tip: Klik tombol "Sinkron" di atas untuk mengisi otomatis dari Laporan Profil Risiko
                                </small>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 fw-bold">
                        <i class="fas fa-info-circle text-primary"></i> Deskripsi
                    </h6>
                    <small class="text-muted">Deskripsi detail mengenai penilaian profil risiko</small>
                </div>
                <div class="card-body p-4">
                    <form action="<?= base_url('Tksprofilrisiko/simpanDeskripsi') ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="form-group mb-3">
                            <label for="deskripsi" class="fw-bold">Deskripsi Detail</label>
                            <textarea class="form-control" name="deskripsi" id="deskripsi" rows="6"
                                placeholder="Masukkan deskripsi lengkap mengenai peringkat profil risiko..."><?= esc($deskripsiValue) ?></textarea>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i>
                                Jelaskan secara detail karakteristik dan kondisi BPR berdasarkan peringkat profil risiko
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
                        <i class="fas fa-chart-line text-primary mr-2"></i>Analisa / Penjelasan
                    </h6>
                    <small class="text-muted">Analisis komprehensif profil risiko</small>
                </div>
                <div class="card-body p-4">
                    <form action="<?= base_url('Tksprofilrisiko/simpanAnalisa') ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="form-group mb-3">
                            <label for="analisis" class="fw-bold">Analisis Profil Risiko</label>
                            <textarea class="form-control" name="analisis" id="analisis" rows="8"
                                placeholder="Masukkan analisa komprehensif mengenai profil risiko BPR..."><?= esc($analisisValue) ?></textarea>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i>
                                Jelaskan analisa mendalam terhadap profil risiko secara keseluruhan
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
                                <a href="<?= base_url('Tksprofilrisiko/approve') ?>"
                                    class="btn approval-btn approval-btn-approve <?= !$canApprove || $isApproved ? 'disabled-btn' : '' ?>"
                                    onclick="return <?= $canApprove && !$isApproved ? "confirm('Apakah Anda yakin ingin menyetujui data ini?')" : 'false' ?>;">
                                    <i class="fas fa-check-circle"></i> Approve
                                </a>
                                <a href="<?= base_url('Tksprofilrisiko/unapprove') ?>"
                                    class="btn approval-btn approval-btn-reject <?= !$isApproved ? 'disabled-btn' : '' ?>"
                                    onclick="return <?= $isApproved ? "confirm('Batalkan persetujuan?')" : 'false' ?>;">
                                    <i class="fas fa-times-circle"></i> Tolak
                                </a>
                            </div>
                            <?php if (!$canApprove): ?>
                                <div class="text-center mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle"></i>
                                        Lengkapi data nilai peringkat terlebih dahulu
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
            <div class="modal-footer" id="modalFooter">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
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
                    background: bg, color: '#fff',
                    padding: '15px 20px', borderRadius: '8px',
                    boxShadow: '0 4px 12px rgba(0,0,0,.3)', zIndex: 9999,
                    display: 'flex', alignItems: 'center', gap: '10px',
                    animation: 'slideIn .3s ease'
                })
                .html('<i class="fas fa-' + icon + '" style="font-size:20px"></i><span>' + esc(message) + '</span>');

            $('body').append($t);
            setTimeout(() => $t.fadeOut(300, function () { $(this).remove(); }), 3000);
        }

        function updateBadge(count) {
            const $b = $('#notification-badge');
            $b.text(count);
            count > 0 ? $b.fadeIn(200) : $b.fadeOut(200);
        }

        function pollUnreadCount() {
            if (document.hidden || isPolling) return;

            isPolling = true;
            $.ajax({
                url: CFG.baseUrl + '/Tksprofilrisiko/getUnreadCount',
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
                    ? '<span class="badge badge-success ms-2"><i class="fas fa-star"></i> BARU</span>'
                    : '';

                return '<div class="komentar-item mb-3 p-3" ' +
                    'style="background:' + bg + ';border-radius:6px;border-left:3px solid ' + border + ';transition:all .3s ease">' +
                    '<div class="d-flex justify-content-between align-items-start mb-2">' +
                    '<strong style="color:' + border + '"><i class="fas fa-user-circle"></i> ' +
                    esc(k.fullname || 'Unknown') + newBadge + '</strong>' +
                    '<small class="text-muted"><i class="fas fa-clock"></i> ' + formatDateTime(k.created_at) + '</small>' +
                    '</div>' +
                    '<p class="mb-0" style="white-space:pre-wrap">' + esc(k.komentar) + '</p>' +
                    '</div>';
            }).join('');
        }

        function loadKomentarList(scrollToBottom) {
            if (commentLoading) return;
            commentLoading = true;

            $.ajax({
                url: CFG.baseUrl + '/Tksprofilrisiko/getKomentar',
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
                url: CFG.baseUrl + '/Tksprofilrisiko/markCommentsAsRead',
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
            const $btnConfirm = $('#btnConfirmAction');
            const $btn = $(opts.btn);
            const origHtml = $btn.html();

            // Set konten modal
            $title.html(opts.confirmTitle);
            $body.html(opts.confirmHtml);
            $btnConfirm
                .html(opts.confirmLabel)
                .css('background-color', opts.confirmColor || '#3b82f6')
                .css('border-color', opts.confirmColor || '#3b82f6');

            // Tampilkan modal
            $modal.modal('show');

            // Hapus event lama agar tidak dobel
            $btnConfirm.off('click').on('click', function () {
                $modal.modal('hide');
                $btn.html('<i class="fas fa-spinner fa-spin me-2"></i> Memproses...').prop('disabled', true);

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
                            // Tampilkan modal sukses
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
            const icon = type === 'success'
                ? '<i class="fas fa-check-circle fa-3x text-success mb-3"></i>'
                : '<i class="fas fa-times-circle fa-3x text-danger mb-3"></i>';

            const $modal = $('#modalConfirmAction');
            $('#modalConfirmTitle').html(title);
            $('#modalConfirmBody').html(
                '<div class="text-center py-2">' + icon +
                '<p class="mb-0">' + message + '</p></div>'
            );
            $('#modalFooter').hide();
            $modal.modal('show');
        }

        $(function () {
            $('#nilaiperingkat').on('change', function () {
                $('#nilaifaktor_display').val(
                    ((parseFloat(this.value) || 0) * 0.25).toFixed(2)
                );
            });

            $('#nilaiperingkatbefore').on('change', function () {
                const v = parseFloat(this.value) || 0;
                $('#nilaifaktorbefore_display').val(v > 0 ? (v * 0.25).toFixed(2) : '0.00');
            });

            $('#nilaiperingkatbefore_manual').on('change', function () {
                const v = parseFloat(this.value) || 0;
                $('#nilaifaktorbefore_manual_display').val(v > 0 ? (v * 0.25).toFixed(2) : '0.00');
            });

            $('#modalConfirmAction').on('hidden.bs.modal', function () {
                $('#modalFooter').show();
            });

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
                    url: CFG.baseUrl + '/Tksprofilrisiko/tambahKomentar',
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
            $('#btnSyncProfilRisiko').on('click', function (e) {
                e.preventDefault();

                const curLabel = 'Semester ' + CFG.syncCurrentSemester + ' Tahun ' + CFG.syncCurrentTahun;
                const beforeLabel = CFG.syncBeforeAvailable
                    ? 'Semester ' + CFG.syncBeforeSemester + ' Tahun ' + CFG.syncBeforeTahun
                    : null;

                let html = '<div style="text-align:left">' +
                    '<p>Apakah Anda yakin ingin melakukan sinkronisasi dengan Laporan Profil Risiko?</p><br>' +
                    '<small class="text-muted">Data yang akan diambil &amp; disimpan:</small>' +
                    '<ul style="text-align:left;margin-top:10px">' +
                    '<li>Nilai Peringkat &amp; Nilai Faktor (25%) &mdash; dari <strong>' + curLabel + '</strong></li>';

                if (beforeLabel) {
                    html += '<li>Nilai Peringkat &amp; Nilai Faktor Periode Sebelumnya &mdash; dari <strong>' + beforeLabel + '</strong></li>';
                } else {
                    html += '<li class="text-warning">Data periode sebelumnya belum tersedia &mdash; nilai "sebelumnya" tidak akan diisi</li>';
                }

                html += '<li>Analisis/Kesimpulan &mdash; dari <strong>' + curLabel + '</strong></li></ul>' +
                    '<p class="text-warning mt-2"><strong>Catatan:</strong> Status approval akan direset.</p></div>';

                performAjaxAction({
                    btn: this,
                    url: CFG.baseUrl + '/Tksprofilrisiko/syncWithProfilRisiko',
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
                    url: CFG.baseUrl + '/Tksprofilrisiko/unsyncWithProfilRisiko',
                    confirmTitle: 'Konfirmasi Lepas Sinkronisasi',
                    icon: 'warning',
                    confirmColor: '#f59e0b',
                    confirmLabel: '<i class="fas fa-unlink"></i> Ya, Lepas Sinkronisasi',
                    successMsg: 'Sinkronisasi berhasil dibatalkan',
                    confirmHtml:
                        '<div style="text-align:left">' +
                        '<p>Apakah Anda yakin ingin melepas sinkronisasi?</p><br>' +
                        '<small class="text-muted">Setelah dilepas, Anda dapat mengedit data secara manual.</small>' +
                        '<p class="text-warning mt-2"><strong>Catatan:</strong> Status approval akan direset.</p></div>'
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
        padding: 16px;
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

    .badge-info {
        background-color: #3b82f6;
        color: #fff;
    }

    .badge-success {
        background-color: #10b981;
        color: #fff;
    }

    .badge-warning {
        background-color: #f59e0b;
        color: #fff;
    }

    .badge-orange {
        background-color: #ea580c;
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
    }

    .form-control:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, .1);
    }

    .form-control-lg {
        height: 52px;
    }

    .bg-gradient-warning {
        background: linear-gradient(135deg, #fef3c7, #fde68a);
    }

    /* Synced content */
    .synced-content-box {
        background: linear-gradient(135deg, #f0fdf4, #dcfce7);
        border: 2px solid #10b981;
        border-radius: 12px;
        padding: 24px;
    }

    .synced-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #fff;
        padding: 8px 16px;
        border-radius: 20px;
        margin-bottom: 16px;
        font-weight: 600;
        font-size: .85rem;
        color: #10b981;
    }

    .content-display {
        line-height: 1.8;
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
        border-color: #1e7e34;
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
        border-color: #bd2130;
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

    /* Comments list */
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

    .swal2-container {
        z-index: 99999 !important;
    }

    .swal2-backdrop-show {
        z-index: 99998 !important;
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

        .info-box {
            padding: 1rem;
        }

        .approval-btn {
            font-size: .85rem;
            padding: 8px 12px;
        }
    }
</style>