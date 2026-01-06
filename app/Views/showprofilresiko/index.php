<div class="alert beautiful-alert my-4">
    <i class="fas fa-info-circle alert-icon"></i> <?php if (isset($bprData) && isset($periodeDetail)): ?>
        <strong><?= esc($bprData['namabpr'] ?? 'Nama BPR') ?></strong> - Periode Pelaporan Semester
        <?= esc($periodeDetail['semester']) ?> Tahun
        <?= esc($periodeDetail['tahun']) ?>
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

<?php if (session()->getFlashdata('err')): ?>
    <div class="alert alert-danger" role="alert"><?= esc(session()->getFlashdata('err')); ?></div>
<?php endif; ?>

<div class="container-fluid">
    <div class="card">
        <nav>
            <div class="nav nav-tabs w-100 d-flex" id="nav-tab" role="tablist">
                <a class="nav-item nav-link flex-fill text-center active" id="nav-home-tab" data-toggle="tab"
                    href="#nav-home" role="tab" aria-controls="nav-home" aria-selected="true">Laporan Profil Risiko</a>
                <a class="nav-item nav-link flex-fill text-center" id="nav-profile-tab" data-toggle="tab"
                    href="#nav-profile" role="tab" aria-controls="nav-profile" aria-selected="false">Generate file
                    laporan</a>
            </div>
        </nav>
        <div class="tab-content" id="nav-tabContent">
            <div class="tab-pane fade show active" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab">
                <div class="container-fluid">
                    <p><a href="Periodeprofilresiko" class="link-primary custom-link ml-5 mt-5">Kembali ke halaman
                            pilih periode</a>
                    </p>
                    <h1 class="h3 mt-5 mb-4 text-gray-800 text-center"><b><?= $judul; ?></b></h1>

                    <div class="row">
                        <?php foreach ($transparan as $trans): ?>
                            <div class="col-xl-4 col-md-6 mb-4">
                                <div class="card trans-card shadow h-100 py-3">
                                    <a href="<?= esc($trans['link']); ?>" style="text-decoration: none; color: inherit;">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                        <?= esc($trans['name']); ?>
                                                    </div>
                                                    <hr class="sidebar-divider my-2">
                                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                        <?= esc($bprData['namabpr'] ?? 'Nama BPR'); ?>
                                                    </div>
                                                    <div class="text-xs text-muted mb-1">
                                                        Semester: <?= esc($periodeDetail['semester']) ?>
                                                    </div>
                                                    <div class="text-xs text-muted mb-3">
                                                        Periode: Semester <?= esc($periodeDetail['semester']) ?> Tahun
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
                                                    <p class="<?= $statusClass2; ?>"><?= $message2; ?></p>
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

                        <?php
                        $ntotalrisk = $riskCalculations['ntotalrisk'] ?? null;

                        $ratings = [
                            1 => ['title' => 'Sangat Rendah', 'level' => '(1)', 'color' => '#5bc0de'],
                            2 => ['title' => 'Rendah', 'level' => '(2)', 'color' => '#28a745'],
                            3 => ['title' => 'Sedang', 'level' => '(3)', 'color' => '#ffc107'],
                            4 => ['title' => 'Tinggi', 'level' => '(4)', 'color' => '#fd7e14'],
                            5 => ['title' => 'Sangat Tinggi', 'level' => '(5)', 'color' => '#dc3545']
                        ];

                        $selectedRating = $ratings[$ntotalrisk] ?? null;
                        ?>

                        <?php if ($selectedRating): ?>
                            <div class="container-fluid d-flex justify-content-center align-items-center min-vh-20">
                                <div class="card shadow-lg text-center border-0 mb-4 mt-3"
                                    style="max-width: 25rem; width: 100%; background-color: <?= esc($selectedRating['color']) ?>;">
                                    <div class="card-header bg-white fw-bold" style="color: #000;">
                                        Peringkat Profil Risiko
                                    </div>
                                    <div class="card-body" style="color: #000;">
                                        <h2 class="card-title text-black"><?= esc($selectedRating['title']) ?></h2>
                                        <p class="card-text mb-0 text-black">Tingkat Risiko:
                                            <?= esc($selectedRating['level']) ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="container-fluid d-flex justify-content-center align-items-center min-vh-20">
                                <div class="alert alert-dark mt-3 text-center" style="max-width: 25rem; width: 100%;">
                                    Data peringkat risiko belum tersedia.
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($showprofilresiko) && !empty($showprofilresiko) && !empty($showprofilresiko['ntotalrisk'])): ?>
                            <div class="container-fluid d-flex justify-content-center align-items-center mb-4">
                                <div class="card shadow-lg border-0" style="max-width: 450px; width: 100%;">
                                    <div class="card-header bg-primary text-white" style="cursor: pointer;"
                                        data-bs-toggle="collapse" data-bs-target="#mainCardBody" aria-expanded="true">
                                        <h5 class="mb-0 d-flex justify-content-between align-items-center">
                                            <span>
                                                <i class="fas fa-edit"></i> Update Manual Nilai Total Risiko
                                            </span>
                                            <i class="fas fa-chevron-down" id="mainCardIcon"></i>
                                        </h5>
                                    </div>

                                    <div class="collapse" id="mainCardBody">
                                        <div class="card-body">
                                            <form method="post"
                                                action="<?= base_url('Showprofilresiko/updateNtotalrisk'); ?>"
                                                id="formUpdateNtotalrisk"> <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= esc($showprofilresiko['id']); ?>">

                                                <!-- Nilai Saat Ini -->
                                                <div class="alert alert-info">
                                                    <i class="fas fa-info-circle"></i>
                                                    <strong>Nilai Saat Ini:</strong>
                                                    <?php
                                                    $currentNtotalrisk = $showprofilresiko['ntotalrisk'] ?? 'N/A';
                                                    $currentDisplay = getNilaiTextAndColor($currentNtotalrisk);
                                                    ?>
                                                    <span class="fw-bold <?= $currentDisplay['color']; ?>">
                                                        <?= $currentDisplay['text']; ?>
                                                    </span>
                                                </div>

                                                <!-- Pilihan Nilai Baru -->
                                                <div class="form-group mb-3">
                                                    <label for="ntotalrisk" class="fw-bold">
                                                        Nilai Total Risiko Baru <span class="text-danger">*</span>
                                                    </label>
                                                    <select class="form-control form-control-lg" id="ntotalrisk"
                                                        name="ntotalrisk" required>
                                                        <option value="">-- Pilih Nilai --</option>
                                                        <option value="1" <?= ($currentNtotalrisk == 1) ? 'selected' : ''; ?>>1
                                                            - Sangat Rendah
                                                        </option>
                                                        <option value="2" <?= ($currentNtotalrisk == 2) ? 'selected' : ''; ?>>2
                                                            - Rendah
                                                        </option>
                                                        <option value="3" <?= ($currentNtotalrisk == 3) ? 'selected' : ''; ?>>3
                                                            - Sedang
                                                        </option>
                                                        <option value="4" <?= ($currentNtotalrisk == 4) ? 'selected' : ''; ?>>4
                                                            - Tinggi
                                                        </option>
                                                        <option value="5" <?= ($currentNtotalrisk == 5) ? 'selected' : ''; ?>>5
                                                            - Sangat Tinggi
                                                        </option>
                                                    </select>
                                                    <small class="form-text text-muted">
                                                        Pilih nilai antara 1 (Sangat Rendah) sampai 5 (Sangat Tinggi)
                                                    </small>
                                                </div>

                                                <!-- Penjelasan Semua Nilai -->
                                                <div class="mt-4">
                                                    <div class="card border-0 bg-light">
                                                        <div class="card-body p-3">
                                                            <div data-bs-toggle="collapse" data-bs-target="#riskContent"
                                                                aria-expanded="false" class="main-card collapsed">
                                                                <h6 class="fw-bold mb-0">
                                                                    <i class="fas fa-info-circle text-primary"></i>
                                                                    Penjelasan Tingkat Risiko:
                                                                    <i
                                                                        class="fas fa-chevron-down collapse-icon float-end"></i>
                                                                </h6>
                                                            </div>

                                                            <div class="collapse mt-3" id="riskContent">
                                                                <ul class="list-group list-group-flush">
                                                                    <li class="list-group-item">
                                                                        <strong>1 - Sangat Rendah:</strong>
                                                                        <span class="risk-level-badge risk-1">Sangat
                                                                            Rendah</span>
                                                                        <br><br>
                                                                        Profil Risiko BPR yang termasuk dalam peringkat
                                                                        ini pada umumnya
                                                                        memiliki karakteristik antara lain sebagai
                                                                        berikut:
                                                                        <br><br>
                                                                        <strong>a.</strong> Dengan mempertimbangkan
                                                                        aktivitas bisnis
                                                                        yang
                                                                        dilakukan BPR, kemungkinan kerugian yang
                                                                        dihadapi BPR dari
                                                                        Risiko
                                                                        inheren tergolong sangat rendah selama periode
                                                                        waktu tertentu
                                                                        pada
                                                                        masa yang akan datang.
                                                                        <br><br>
                                                                        <strong>b.</strong> KPMR sangat memadai. Dalam
                                                                        hal terdapat
                                                                        kelemahan minor, kelemahan tersebut dapat
                                                                        diabaikan.
                                                                    </li>

                                                                    <li class="list-group-item">
                                                                        <strong>2 - Rendah:</strong>
                                                                        <span class="risk-level-badge risk-2">Rendah</span>
                                                                        <br><br>
                                                                        Profil Risiko BPR yang termasuk dalam peringkat
                                                                        ini pada umumnya
                                                                        memiliki karakteristik antara lain sebagai
                                                                        berikut:
                                                                        <br><br>
                                                                        <strong>a.</strong> Dengan mempertimbangkan
                                                                        aktivitas bisnis
                                                                        yang
                                                                        dilakukan BPR, kemungkinan kerugian yang
                                                                        dihadapi BPR dari
                                                                        Risiko
                                                                        inheren tergolong rendah selama periode waktu
                                                                        tertentu pada masa
                                                                        yang akan datang.
                                                                        <br><br>
                                                                        <strong>b.</strong> KPMR memadai. Dalam hal
                                                                        terdapat kelemahan
                                                                        minor, kelemahan tersebut perlu mendapatkan
                                                                        perhatian manajemen.
                                                                    </li>

                                                                    <li class="list-group-item">
                                                                        <strong>3 - Sedang:</strong>
                                                                        <span class="risk-level-badge risk-3">Sedang</span>
                                                                        <br><br>
                                                                        Profil Risiko BPR yang termasuk dalam peringkat
                                                                        ini pada umumnya
                                                                        memiliki karakteristik antara lain sebagai
                                                                        berikut:
                                                                        <br><br>
                                                                        <strong>a.</strong> Dengan mempertimbangkan
                                                                        aktivitas bisnis
                                                                        yang
                                                                        dilakukan BPR, kemungkinan kerugian yang
                                                                        dihadapi BPR dari
                                                                        Risiko
                                                                        inheren tergolong sedang selama periode waktu
                                                                        tertentu pada masa
                                                                        yang akan datang.
                                                                        <br><br>
                                                                        <strong>b.</strong> KPMR cukup memadai. Meskipun
                                                                        persyaratan
                                                                        minimum
                                                                        terpenuhi, terdapat beberapa kelemahan yang
                                                                        membutuhkan
                                                                        perhatian
                                                                        manajemen dan perbaikan.
                                                                    </li>

                                                                    <li class="list-group-item">
                                                                        <strong>4 - Tinggi:</strong>
                                                                        <span class="risk-level-badge risk-4">Tinggi</span>
                                                                        <br><br>
                                                                        Profil Risiko BPR yang termasuk dalam peringkat
                                                                        ini pada umumnya
                                                                        memiliki karakteristik antara lain sebagai
                                                                        berikut:
                                                                        <br><br>
                                                                        <strong>a.</strong> Dengan mempertimbangkan
                                                                        aktivitas bisnis
                                                                        yang
                                                                        dilakukan BPR, kemungkinan kerugian yang
                                                                        dihadapi BPR dari
                                                                        Risiko
                                                                        inheren tergolong tinggi selama periode waktu
                                                                        tertentu pada masa
                                                                        yang akan datang.
                                                                        <br><br>
                                                                        <strong>b.</strong> KPMR kurang memadai.
                                                                        Terdapat kelemahan
                                                                        signifikan pada berbagai aspek Manajemen Risiko
                                                                        yang membutuhkan
                                                                        tindakan korektif segera.
                                                                    </li>

                                                                    <li class="list-group-item">
                                                                        <strong>5 - Sangat Tinggi:</strong>
                                                                        <span class="risk-level-badge risk-5">Sangat
                                                                            Tinggi</span>
                                                                        <br><br>
                                                                        Profil Risiko BPR yang termasuk dalam peringkat
                                                                        ini pada umumnya
                                                                        memiliki karakteristik antara lain sebagai
                                                                        berikut:
                                                                        <br><br>
                                                                        <strong>a.</strong> Dengan mempertimbangkan
                                                                        aktivitas bisnis
                                                                        yang
                                                                        dilakukan BPR, kemungkinan kerugian yang
                                                                        dihadapi BPR dari
                                                                        Risiko
                                                                        inheren tergolong sangat tinggi selama periode
                                                                        waktu tertentu
                                                                        pada
                                                                        masa yang akan datang.
                                                                        <br><br>
                                                                        <strong>b.</strong> KPMR tidak memadai. Terdapat
                                                                        kelemahan
                                                                        signifikan pada berbagai aspek Manajemen Risiko
                                                                        yang tindakan
                                                                        penyelesaiannya di luar kemampuan manajemen.
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Tombol Aksi -->
                                                <div class="form-group mt-4 mb-0">
                                                    <div class="d-flex justify-content-between">
                                                        <button type="submit" class="btn btn-primary">
                                                            <i class="fas fa-save"></i> Update Nilai
                                                        </button>
                                                        <button type="button" class="btn btn-warning"
                                                            onclick="resetNtotalrisk()">
                                                            <i class="fas fa-undo"></i> Reset ke Perhitungan Otomatis
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>

                                            <!-- Form Reset (hidden) -->
                                            <form method="post"
                                                action="<?= base_url('Showprofilresiko/resetNtotalrisk'); ?>"
                                                id="formResetNtotalrisk" style="display: none;">
                                                <input type="hidden" name="id" value="<?= esc($showprofilresiko['id']); ?>">
                                            </form>

                                            <div class="alert alert-warning mt-3 mb-0">
                                                <small>
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                    <strong>Perhatian:</strong> Perubahan manual akan menggantikan
                                                    perhitungan otomatis.
                                                    Klik "Reset" untuk kembali menggunakan perhitungan otomatis.
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Risk Type Cards -->
                        <div class="container-fluid d-flex justify-content-center align-items-center min-vh-100">
                            <div class="row g-2" style="width: 85%;">
                                <?php
                                function getNilaiTextAndColor($value)
                                {
                                    $result = ['text' => 'N/A', 'color' => 'text-secondary'];

                                    if ($value === '0' || $value === 'N/A' || $value === '' || $value === null) {
                                        return $result;
                                    }

                                    switch ($value) {
                                        case '1':
                                            $result['text'] = 'Sangat Rendah (1)';
                                            $result['color'] = 'text-primary';
                                            break;
                                        case '2':
                                            $result['text'] = 'Rendah (2)';
                                            $result['color'] = 'text-success';
                                            break;
                                        case '3':
                                            $result['text'] = 'Sedang (3)';
                                            $result['color'] = 'text-warning';
                                            break;
                                        case '4':
                                            $result['text'] = 'Tinggi (4)';
                                            $result['color'] = 'text-orange';
                                            break;
                                        case '5':
                                            $result['text'] = 'Sangat Tinggi (5)';
                                            $result['color'] = 'text-danger';
                                            break;
                                        default:
                                            $result['text'] = $value;
                                            $result['color'] = 'text-secondary';
                                    }

                                    return $result;
                                }

                                // Tentukan apakah kategori adalah A
                                $isKategoriA = isset($kategori) && strtoupper($kategori) === 'A';
                                ?>

                                <?php foreach ($factors as $factor): ?>
                                    <?php
                                    // Skip Risiko Reputasi dan Stratejik jika kategori bukan A
                                    $riskType = $factor['risk_type'] ?? '';
                                    if (!$isKategoriA && in_array($riskType, ['reputasi', 'stratejik'])) {
                                        continue; // Skip card ini
                                    }
                                    ?>

                                    <div class="col-12 col-md-6 mb-4">
                                        <div
                                            class="card factor-card shadow h-100 py-2 <?= (isset($factor['accdekom']) && $factor['accdekom'] == 1 && isset($factor['is_approved']) && $factor['is_approved'] == 1) ? 'approved-card' : '' ?>">
                                            <a href="<?= esc($factor['link']); ?>"
                                                style="text-decoration: none; color: inherit;">
                                                <div class="card-body">
                                                    <div class="row no-gutters align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                                <?= esc($factor['name']); ?>
                                                            </div>
                                                            <hr class="sidebar-divider my-2">
                                                            <div
                                                                class="text-sm font-weight-bold text-primary text-uppercase mb-1">
                                                                <?= esc($bprData['namabpr'] ?? 'Nama BPR'); ?>
                                                            </div>
                                                            <div class="text-sm text-muted mb-3">
                                                                Periode: Semester <?= esc($periodeDetail['semester']) ?>
                                                                Tahun
                                                                <?= esc($periodeDetail['tahun']) ?>
                                                            </div>

                                                            <!-- Nilai Inheren -->
                                                            <div class="text-gray-700">
                                                                <strong>Penilaian Risiko Inheren:</strong>
                                                                <?php
                                                                $nfaktorValue = esc($factor['nfaktor'] ?? 'N/A');
                                                                $nfaktorClass = 'text-success';
                                                                $nfaktorText = $nfaktorValue;

                                                                if ($nfaktorValue === '0' || $nfaktorValue === 'N/A') {
                                                                    $nfaktorValue = 'N/A';
                                                                    $nfaktorClass = 'text-danger';
                                                                } else {
                                                                    switch ($nfaktorValue) {
                                                                        case '1':
                                                                            $nfaktorText = 'Sangat Rendah (1)';
                                                                            break;
                                                                        case '2':
                                                                            $nfaktorText = 'Rendah (2)';
                                                                            break;
                                                                        case '3':
                                                                            $nfaktorText = 'Sedang (3)';
                                                                            break;
                                                                        case '4':
                                                                            $nfaktorText = 'Tinggi (4)';
                                                                            break;
                                                                        case '5':
                                                                            $nfaktorText = 'Sangat Tinggi (5)';
                                                                            break;
                                                                        default:
                                                                            $nfaktorText = $nfaktorValue;
                                                                    }
                                                                }
                                                                ?>
                                                                <span
                                                                    class="font-weight-bold <?= $nfaktorClass; ?>"><?= $nfaktorText; ?></span>
                                                            </div>

                                                            <!-- Nilai KPMR -->
                                                            <div class="text-gray-700">
                                                                <strong>Penilaian Risiko KPMR:</strong>
                                                                <?php
                                                                $nfaktorKpmrValue = esc($factor['nfaktor_kpmr'] ?? 'N/A');
                                                                $nfaktorKpmrClass = 'text-success';
                                                                $nfaktorKpmrText = $nfaktorKpmrValue;

                                                                if ($nfaktorKpmrValue === '0' || $nfaktorKpmrValue === 'N/A') {
                                                                    $nfaktorKpmrValue = 'N/A';
                                                                    $nfaktorKpmrClass = 'text-danger';
                                                                } else {
                                                                    switch ($nfaktorKpmrValue) {
                                                                        case '1':
                                                                            $nfaktorKpmrText = 'Sangat Rendah (1)';
                                                                            break;
                                                                        case '2':
                                                                            $nfaktorKpmrText = 'Rendah (2)';
                                                                            break;
                                                                        case '3':
                                                                            $nfaktorKpmrText = 'Sedang (3)';
                                                                            break;
                                                                        case '4':
                                                                            $nfaktorKpmrText = 'Tinggi (4)';
                                                                            break;
                                                                        case '5':
                                                                            $nfaktorKpmrText = 'Sangat Tinggi (5)';
                                                                            break;
                                                                        default:
                                                                            $nfaktorKpmrText = $nfaktorKpmrValue;
                                                                    }
                                                                }
                                                                ?>
                                                                <span
                                                                    class="font-weight-bold <?= $nfaktorKpmrClass; ?>"><?= $nfaktorKpmrText; ?></span>
                                                            </div>

                                                            <div class="text-gray-700">
                                                                <strong>Tingkat Risiko:</strong>
                                                                <?php
                                                                $nresultkeyValue = esc($factor['nkredit']
                                                                    ?? $factor['noperasional']
                                                                    ?? $factor['nkepatuhan']
                                                                    ?? $factor['nlikuiditas']
                                                                    ?? $factor['nreputasi']
                                                                    ?? $factor['nstratejik']
                                                                    ?? 'N/A');

                                                                $nresultkeyClass = 'text-success';
                                                                $nresultkeyText = $nresultkeyValue;

                                                                if ($nresultkeyValue === '0' || $nresultkeyValue === 'N/A') {
                                                                    $nresultkeyValue = 'N/A';
                                                                    $nresultkeyClass = 'text-danger';
                                                                } else {
                                                                    switch ($nresultkeyValue) {
                                                                        case '1':
                                                                            $nresultkeyText = 'Sangat Rendah (1)';
                                                                            break;
                                                                        case '2':
                                                                            $nresultkeyText = 'Rendah (2)';
                                                                            break;
                                                                        case '3':
                                                                            $nresultkeyText = 'Sedang (3)';
                                                                            break;
                                                                        case '4':
                                                                            $nresultkeyText = 'Tinggi (4)';
                                                                            break;
                                                                        case '5':
                                                                            $nresultkeyText = 'Sangat Tinggi (5)';
                                                                            break;
                                                                        default:
                                                                            $nresultkeyText = $nresultkeyValue;
                                                                    }
                                                                }
                                                                ?>
                                                                <span
                                                                    class="font-weight-bold <?= $nresultkeyClass; ?>"><?= $nresultkeyText; ?></span>
                                                            </div>

                                                            <!-- Status Approval Direktur -->
                                                            <div class="text-gray-700">
                                                                <?php
                                                                $message2 = 'Belum disetujui oleh Direktur Utama';
                                                                $statusClass2 = 'text-danger';

                                                                if (isset($factor['is_approved']) && $factor['is_approved'] == 1) {
                                                                    $message2 = 'Telah disetujui oleh Direktur Utama';
                                                                    $statusClass2 = 'text-success';
                                                                }
                                                                ?>
                                                                <p class="<?= $statusClass2; ?> mb-1"><?= $message2; ?>
                                                                </p>
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

                                <?php if (isset($showprofilresiko) && !empty($showprofilresiko)): ?>
                                    <div class="col-12 mt-4 mb-4">
                                        <div class="card shadow-sm">
                                            <div class="card-header bg-primary text-white">
                                                <h5 class="mb-0">
                                                    <i class="fas fa-edit"></i> Kesimpulan/Analisis Profil Risiko
                                                </h5>
                                            </div>
                                            <div class="card-body">
                                                <!-- Alert untuk notifikasi -->
                                                <div id="alert-kesimpulan" style="display: none;">
                                                    <div class="alert alert-dismissible fade show" role="alert">
                                                        <span id="alert-kesimpulan-message"></span>
                                                        <button type="button" class="close" data-dismiss="alert"
                                                            aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                </div>

                                                <form id="formKesimpulan" autocomplete="off">
                                                    <input type="hidden" name="id"
                                                        value="<?= esc($showprofilresiko['id']); ?>">

                                                    <div class="form-group">
                                                        <label for="kesimpulan">
                                                            Kesimpulan dan Analisis Profil Risiko
                                                            <span class="text-danger">*</span>
                                                            <span class="save-indicator" id="indicator-kesimpulan"
                                                                style="display: none;">
                                                                <i class="fas fa-spinner fa-spin text-primary"></i>
                                                            </span>
                                                        </label>
                                                        <textarea class="form-control auto-save-kesimpulan" id="kesimpulan"
                                                            name="kesimpulan" rows="8"
                                                            placeholder="Tuliskan kesimpulan analisis profil risiko secara keseluruhan..."
                                                            data-field="kesimpulan"><?= esc($showprofilresiko['kesimpulan'] ?? ''); ?></textarea>
                                                        <small class="form-text text-muted">
                                                            <i class="fas fa-info-circle"></i>
                                                            Isi dengan analisis komprehensif mengenai profil risiko BPR
                                                            berdasarkan penilaian seluruh jenis risiko
                                                        </small>
                                                        <small class="text-success" id="status-kesimpulan"
                                                            style="display: none;">
                                                            <i class="fas fa-check-circle"></i> Tersimpan otomatis
                                                        </small>
                                                    </div>

                                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                                        <button type="submit" class="btn btn-success">
                                                            <i class="fas fa-save"></i> Simpan Kesimpulan
                                                        </button>
                                                        <span class="text-muted">
                                                            <i class="fas fa-clock"></i> Auto-save aktif
                                                        </span>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12 mb-4 p-4 p-md-5">
                            <div class="card-body">
                                <h4 class="font-weight-bold text-gray-900 text-center">Lembar Persetujuan</h4>

                                <!-- Alert untuk notifikasi -->
                                <div id="alert-container" style="display: none;">
                                    <div class="alert alert-dismissible fade show" role="alert">
                                        <span id="alert-message"></span>
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                </div>

                                <form id="formTtd" autocomplete="off">
                                    <input type="hidden" id="record_id" name="id"
                                        value="<?= esc($showprofilresiko['id'] ?? ''); ?>">

                                    <div class="form-group">
                                        <label for="dirut">
                                            Nama Direktur Utama <span class="text-danger">*</span>
                                            <span class="save-indicator" id="indicator-dirut" style="display: none;">
                                                <i class="fas fa-spinner fa-spin text-primary"></i>
                                            </span>
                                        </label>
                                        <input type="text" class="form-control auto-save" id="dirut" name="dirut"
                                            data-field="dirut" value="<?= esc($showprofilresiko['dirut'] ?? ''); ?>"
                                            required>
                                        <small class="text-success" id="status-dirut" style="display: none;">
                                            <i class="fas fa-check-circle"></i> Tersimpan
                                        </small>
                                    </div>

                                    <div class="form-group">
                                        <label for="dirkep">
                                            Nama Direktur yang membawahi fungsi kepatuhan <span
                                                class="text-danger">*</span>
                                            <span class="save-indicator" id="indicator-dirkep" style="display: none;">
                                                <i class="fas fa-spinner fa-spin text-primary"></i>
                                            </span>
                                        </label>
                                        <input type="text" class="form-control auto-save" id="dirkep" name="dirkep"
                                            data-field="dirkep" value="<?= esc($showprofilresiko['dirkep'] ?? ''); ?>"
                                            required>
                                        <small class="text-success" id="status-dirkep" style="display: none;">
                                            <i class="fas fa-check-circle"></i> Tersimpan
                                        </small>
                                    </div>

                                    <div class="form-group">
                                        <label for="pe">
                                            Nama Pejabat Eksekutif Manajemen Risiko <span class="text-danger">*</span>
                                            <span class="save-indicator" id="indicator-pe" style="display: none;">
                                                <i class="fas fa-spinner fa-spin text-primary"></i>
                                            </span>
                                        </label>
                                        <input type="text" class="form-control auto-save" id="pe" name="pe"
                                            data-field="pe" value="<?= esc($showprofilresiko['pe'] ?? ''); ?>" required>
                                        <small class="text-success" id="status-pe" style="display: none;">
                                            <i class="fas fa-check-circle"></i> Tersimpan
                                        </small>
                                    </div>

                                    <div class="form-group">
                                        <label for="tanggal">
                                            Tanggal Pelaporan <span class="text-danger">*</span>
                                            <span class="save-indicator" id="indicator-tanggal" style="display: none;">
                                                <i class="fas fa-spinner fa-spin text-primary"></i>
                                            </span>
                                        </label>
                                        <input type="text" class="form-control auto-save" id="tanggal" name="tanggal"
                                            data-field="tanggal" placeholder="dd/mm/yyyy"
                                            value="<?= esc($showprofilresiko['tanggal_display'] ?? ''); ?>" required>
                                        <small class="text-success" id="status-tanggal" style="display: none;">
                                            <i class="fas fa-check-circle"></i> Tersimpan
                                        </small>
                                    </div>

                                    <div class="form-group">
                                        <label for="lokasi">
                                            Lokasi <span class="text-danger">*</span>
                                            <span class="save-indicator" id="indicator-lokasi" style="display: none;">
                                                <i class="fas fa-spinner fa-spin text-primary"></i>
                                            </span>
                                        </label>
                                        <input type="text" class="form-control auto-save" id="lokasi" name="lokasi"
                                            data-field="lokasi" value="<?= esc($showprofilresiko['lokasi'] ?? ''); ?>"
                                            required>
                                        <small class="text-success" id="status-lokasi" style="display: none;">
                                            <i class="fas fa-check-circle"></i> Tersimpan otomatis
                                        </small>
                                    </div>

                                    <p>Download lembar persetujuan:
                                        <a href="<?= base_url(); ?>" target="_blank"></a>
                                    </p>

                                    <div class="d-flex">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-save"></i> Simpan Semua Data
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Upload dokumen section tetap sama -->
                        <!-- <div class="col-12 mb-4">
                            <div class="card-body p-4 p-md-5">
                                <h4 class="font-weight-bold text-gray-900 mb-4 text-center">Upload Dokumen Pendukung
                                </h4>
                                <p class="text-muted text-center mb-4">Silakan unggah file PDF pendukung laporan.</p>
                                <form method="post" action="<?= base_url('pdfself/uploadPdf'); ?>"
                                    enctype="multipart/form-data">
                                    <div class="form-group">
                                        <label for="pdf1">Upload Laporan Pokok Pelaksanaan tugas anggota Direksi yang
                                            membawahkan fungsi kepatuhan. (.PDF)</label>
                                        <input type="file" class="form-control" id="pdf1" name="pdf1"
                                            accept="application/pdf">
                                        <p>Maksimal ukuran file .PDF 2 Mb</p>
                                    </div>
                                    <?php if (!empty($showprofilresiko['pdf1_filename'])): ?>
                                        <div class="mt-2">
                                            <p>PDF 1 Terunggah:
                                                <a href="<?= base_url('pdfself/download/' . esc($showprofilresiko['pdf1_filename'])); ?>"
                                                    target="_blank">
                                                    <?= esc($showprofilresiko['pdf1_filename']); ?>
                                                </a>
                                            </p>
                                        </div>
                                    <?php endif; ?>

                                    <div class="form-group">
                                        <label for="pdf2">Upload Laporan pelaksanaan dan pokok hasil audit intern
                                            (.PDF)</label>
                                        <input type="file" class="form-control" id="pdf2" name="pdf2"
                                            accept="application/pdf">
                                        <p>Maksimal ukuran file .PDF 2 Mb</p>
                                    </div>
                                    <?php if (!empty($showprofilresiko['pdf2_filename'])): ?>
                                        <div class="mt-2">
                                            <p>PDF 2 Terunggah:
                                                <a href="<?= base_url('pdfself/download/' . esc($showprofilresiko['pdf2_filename'])); ?>"
                                                    target="_blank">
                                                    <?= esc($showprofilresiko['pdf2_filename']); ?>
                                                </a>
                                            </p>
                                        </div>
                                    <?php endif; ?>

                                    <button type="submit" class="btn btn-success">Upload PDF</button>
                                </form>
                            </div>
                        </div> -->
                    </div>
                    <div class="col-12 text-center mt-2">
                        <span class="text-secondary fw-semibold">
                            Cek kembali seluruh data, pastikan informasi BPR dan data yang diperlukan telah
                            terisi dengan benar
                        </span>
                    </div>

                    <?php
                    $allFactorsApproved = true;
                    $unapprovedFactors = [];
                    $totalriskData = getNilaiTextAndColor($factor['ntotalrisk'] ?? 'N/A');
                    foreach ($transparan as $trans) {
                        if (isset($trans['is_approved']) && $trans['is_approved'] == 0) {
                            $allFactorsApproved = false;
                            preg_match('/Faktor (\d+)/', $trans['name'], $matches);
                            $unapprovedFactors[] = isset($matches[1]) ? 'Faktor ' . $matches[1] : $trans['name'];
                        }
                    }

                    $disablePdfButton = !$allFactorsApproved;
                    $alertMessage = $allFactorsApproved
                        ? 'Seluruh risiko telah disetujui.'
                        : 'Persetujuan Direktur Utama pada page berikut belum lengkap: ' . implode(', ', $unapprovedFactors);
                    $alertClass = $allFactorsApproved ? 'alert-success' : 'alert-warning';

                    if ($alertMessage): ?>
                        <div class="col-12 text-center mt-2">
                            <div class="row justify-content-center">
                                <div class="col-12 col-md-10 col-lg-8">
                                    <div class="alert <?= $alertClass ?> alert-dismissible fade show w-100" role="alert">
                                        <?= $alertMessage ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="col-12 d-flex justify-content-center mt-2 mb-4">
                        <a href="/Showprofilresiko/exportAllToZip"
                            class="btn btn-success shadow <?= $disablePdfButton ? 'disabled' : '' ?>"
                            <?= $disablePdfButton ? 'aria-disabled="true"' : '' ?>>
                            <i class="fa fa-file-archive"></i> GENERATE TXT
                        </a>
                        <button id="btnExportAllPDF"
                            class="btn btn-success shadow <?= $disablePdfButton ? 'disabled' : '' ?>"
                            <?= $disablePdfButton ? 'aria-disabled="true' : '' ?>>
                            <i class="fas fa-download"></i> GENERATE PDF
                        </button>
                    </div>
                    <!-- <div class="col-12 d-flex justify-content-center mb-5">
                        <a href="/ShowProfilresiko/exportAllToZip"
                            class="btn btn-success shadow <?= $disablePdfButton ? 'disabled' : '' ?>"
                            <?= $disablePdfButton ? 'aria-disabled="true"' : '' ?>>
                            <i class="fas fa-folder"></i> GENERATE FILE APOLO
                        </a>
                    </div> -->
                </div>
                <!-- <a href="<?= base_url('Risikokredit/viewPDFGabunganPreview?type=kredit') ?>" class="btn btn-danger"
                    target="_blank">
                    <i class="fas fa-file-pdf"></i> Export Kredit
                </a>

                <a href="<?= base_url('Risikokredit/viewPDFGabunganPreview?type=operasional') ?>"
                    class="btn btn-warning" target="_blank">
                    <i class="fas fa-file-pdf"></i> Export Operasional
                </a>

                <a href="<?= base_url('Risikokredit/viewPDFGabunganPreview?type=kepatuhan') ?>" class="btn btn-info"
                    target="_blank">
                    <i class="fas fa-file-pdf"></i> Export Kepatuhan
                </a>

                <a href="<?= base_url('Risikokredit/viewPDFGabunganPreview?type=likuiditas') ?>" class="btn btn-dark"
                    target="_blank">
                    <i class="fas fa-file-pdf"></i> Export Likuiditas
                </a>

                <a href="<?= base_url('Risikokredit/viewPDFGabunganPreview') ?>" class="btn btn-primary"
                    target="_blank">
                    <i class="fas fa-file-pdf"></i> Export Semua Risiko
                </a>

                <a href="/Showprofilresiko/viewLembarPernyataan"
                    class="btn btn-info shadow mx-2 <?= $disablePdfButton ? 'disabled' : '' ?>" <?= $disablePdfButton ? 'aria-disabled="true"' : '' ?> target="_blank">
                    <i class="fas fa-file-signature"></i> LEMBAR PERNYATAAN
                </a>

                <a href="/Showprofilresiko/viewLaporanProfilRisiko"
                    class="btn btn-info shadow mx-2 <?= $disablePdfButton ? 'disabled' : '' ?>" <?= $disablePdfButton ? 'aria-disabled="true"' : '' ?> target="_blank">
                    <i class="fas fa-file-signature"></i> LAPORAN PROFIL RISIKO
                </a>                 -->
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const btnExport = document.getElementById('btnExportAllPDF');

        if (btnExport) {
            btnExport.addEventListener('click', function () {
                const btn = this;
                const originalHTML = btn.innerHTML;

                // Disable button dan tampilkan loading
                btn.disabled = true;
                btn.classList.add('processing');
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';

                // Buka window baru untuk export
                const exportWindow = window.open(
                    '<?= base_url('Showprofilresiko/exportAllPDFToZipView') ?>',
                    'ExportPDFWindow',
                    'width=800,height=600,scrollbars=yes,resizable=yes'
                );

                // Cek apakah popup berhasil dibuka
                if (!exportWindow || exportWindow.closed || typeof exportWindow.closed === 'undefined') {
                    alert('Pop-up blocker terdeteksi!\n\nSilakan izinkan pop-up untuk website ini dan coba lagi.');
                }

                // Reset button setelah 3 detik
                setTimeout(() => {
                    btn.disabled = false;
                    btn.classList.remove('processing');
                    btn.innerHTML = originalHTML;
                }, 3000);
            });
        }
    });

    // Alternative: Jika ingin download langsung tanpa popup
    function downloadAllPDFDirect() {
        // Bisa digunakan jika Anda ingin implementasi download langsung
        // tanpa membuka window baru
        fetch('<?= base_url('Showprofilresiko/exportAllPDFToZipDirect') ?>')
            .then(response => response.blob())
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'Laporan_Profil_Risiko.zip';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat mengunduh file');
            });
    }
    flatpickr("#tanggal", {
        dateFormat: "d/m/Y",  // Format tampilan: dd/mm/yyyy
        allowInput: true,
        locale: "id",  // Optional: gunakan locale Indonesia
        onChange: function (selectedDates, dateStr, instance) {
            const $field = $('#tanggal');
            const fieldName = $field.data('field');
            const recordId = $('#record_id').val();

            if (recordId && fieldName) {
                $('#indicator-' + fieldName).show();
                $('#status-' + fieldName).hide();

                clearTimeout(window.saveTimeout);
                window.saveTimeout = setTimeout(function () {
                    autoSaveField(recordId, fieldName, dateStr);
                }, 1000);
            }
        }
    });
</script>

<script>
    function resetNtotalrisk() {
        if (confirm('Apakah Anda yakin ingin mereset nilai total risiko ke perhitungan otomatis?')) {
            document.getElementById('formResetNtotalrisk').submit();
        }
    }

    document.getElementById('formUpdateNtotalrisk').addEventListener('submit', function (e) {
        const ntotalrisk = document.getElementById('ntotalrisk').value;

        if (!ntotalrisk || ntotalrisk === '') {
            e.preventDefault();
            alert('Silakan pilih nilai total risiko terlebih dahulu');
            return false;
        }

        if (confirm('Apakah Anda yakin ingin mengubah nilai total risiko menjadi ' + ntotalrisk + '?')) {
            return true;
        } else {
            e.preventDefault();
            return false;
        }
    });

    document.getElementById('ntotalrisk').addEventListener('change', function () {
        const value = this.value;
        const colors = {
            '1': 'text-primary',
            '2': 'text-success',
            '3': 'text-warning',
            '4': 'text-orange',
            '5': 'text-danger'
        };

        this.className = 'form-control form-control-lg ' + (colors[value] || '');
    });

    $(document).ready(function () {

        const activeTab = sessionStorage.getItem('activeTab');
        if (activeTab) {
            $('#nav-tab a[href="' + activeTab + '"]').tab('show');
        }

        $('#nav-tab a').on('click', function (e) {
            const tabId = $(this).attr('href');
            sessionStorage.setItem('activeTab', tabId);
        });

        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            const tabId = $(e.target).attr('href');
            sessionStorage.setItem('activeTab', tabId);
        });

        flatpickr("#tanggal", {
            dateFormat: "d/m/Y",
            allowInput: true,
            onChange: function (selectedDates, dateStr, instance) {
                const $field = $('#tanggal');
                const fieldName = $field.data('field');
                const recordId = $('#record_id').val();

                if (recordId && fieldName) {
                    $('#indicator-' + fieldName).show();
                    $('#status-' + fieldName).hide();

                    clearTimeout(window.saveTimeout);
                    window.saveTimeout = setTimeout(function () {
                        autoSaveField(recordId, fieldName, dateStr);
                    }, 1000);
                }
            }
        });

        const SAVE_DELAY = 1000;
        $('.auto-save').on('input change blur', function () {
            const $field = $(this);
            const fieldName = $field.data('field');
            const fieldValue = $field.val();
            const recordId = $('#record_id').val();

            if (!fieldValue && !$field.prop('required')) {
                return;
            }

            clearTimeout(window.saveTimeout);

            $('#indicator-' + fieldName).show();
            $('#status-' + fieldName).hide();

            window.saveTimeout = setTimeout(function () {
                autoSaveField(recordId, fieldName, fieldValue);
            }, SAVE_DELAY);
        });

        function autoSaveField(id, field, value) {
            if (!id) {
                showAlert('danger', 'ID record tidak ditemukan');
                $('#indicator-' + field).hide();
                return;
            }

            $.ajax({
                url: '<?= base_url("Showprofilresiko/ajaxAutoSaveField"); ?>',
                type: 'POST',
                dataType: 'json',
                data: {
                    id: id,
                    field: field,
                    value: value
                },
                success: function (response) {
                    $('#indicator-' + field).hide();

                    if (response.success) {
                        $('#status-' + field).fadeIn();
                        setTimeout(function () {
                            $('#status-' + field).fadeOut();
                        }, 3000);
                    } else {
                        showAlert('danger', response.message || 'Gagal menyimpan data');
                    }
                },
                error: function (xhr, status, error) {
                    $('#indicator-' + field).hide();
                    console.error('AJAX Error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    showAlert('danger', 'Terjadi kesalahan saat menyimpan data');
                }
            });
        }

        window.autoSaveField = autoSaveField;

        $('#formTtd').on('submit', function (e) {
            e.preventDefault();

            const formData = {
                id: $('#record_id').val(),
                dirut: $('#dirut').val(),
                dirkep: $('#dirkep').val(),
                pe: $('#pe').val(),
                tanggal: $('#tanggal').val(),
                lokasi: $('#lokasi').val()
            };

            let isValid = true;
            let emptyFields = [];

            $('.auto-save').each(function () {
                const $field = $(this);
                const fieldLabel = $field.prev('label').text().replace('*', '').trim();

                if ($field.prop('required') && !$field.val()) {
                    isValid = false;
                    $field.addClass('is-invalid');
                    emptyFields.push(fieldLabel);
                } else {
                    $field.removeClass('is-invalid');
                }
            });

            if (!isValid) {
                showAlert('danger', 'Mohon lengkapi field berikut: ' + emptyFields.join(', '));
                return;
            }

            const $submitBtn = $(this).find('button[type="submit"]');
            const originalText = $submitBtn.html();
            $submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...').prop('disabled', true);

            $.ajax({
                url: '<?= base_url("Showprofilresiko/ajaxUpdateTtd"); ?>',
                type: 'POST',
                dataType: 'json',
                data: formData,
                success: function (response) {
                    $submitBtn.html(originalText).prop('disabled', false);

                    if (response.success) {
                        showAlert('success', response.message || 'Semua data berhasil disimpan');

                        $('.save-indicator').hide();
                        $('[id^="status-"]').fadeIn();

                        setTimeout(function () {
                            $('[id^="status-"]').fadeOut();
                        }, 5000);
                    } else {
                        showAlert('danger', response.message || 'Gagal menyimpan data');
                    }
                },
                error: function (xhr, status, error) {
                    $submitBtn.html(originalText).prop('disabled', false);

                    console.error('Submit Error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });

                    showAlert('danger', 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.');
                }
            });
        });

        function showAlert(type, message) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const iconClass = type === 'success' ? 'check-circle' : 'exclamation-circle';
            const $alert = $('#alert-container .alert');

            $alert.removeClass('alert-success alert-danger alert-warning alert-info')
                .addClass(alertClass);

            $('#alert-message').html(
                '<i class="fas fa-' + iconClass + '"></i> ' + message
            );

            $('#alert-container').fadeIn();

            setTimeout(function () {
                $('#alert-container').fadeOut();
            }, 5000);
        }

        window.showAlert = showAlert;

        $('#alert-container .close').on('click', function () {
            $('#alert-container').fadeOut();
        });

        $('.auto-save').on('input', function () {
            $(this).removeClass('is-invalid');
        });

        $('[data-bs-toggle="collapse"]').on('click', function () {
            const $icon = $(this).find('.collapse-icon');
            $icon.toggleClass('fa-chevron-down fa-chevron-up');
        });

        $('#mainCardBody').on('show.bs.collapse', function () {
            $('#mainCardIcon').removeClass('fa-chevron-down').addClass('fa-chevron-up');
        }).on('hide.bs.collapse', function () {
            $('#mainCardIcon').removeClass('fa-chevron-up').addClass('fa-chevron-down');
        });

        $('#riskContent').on('show.bs.collapse', function () {
            $(this).prev().find('.collapse-icon').removeClass('fa-chevron-down').addClass('fa-chevron-up');
        }).on('hide.bs.collapse', function () {
            $(this).prev().find('.collapse-icon').removeClass('fa-chevron-up').addClass('fa-chevron-down');
        });

    });

    function resetNtotalrisk() {
        if (confirm('Apakah Anda yakin ingin mereset nilai total risiko ke perhitungan otomatis?')) {
            const form = document.getElementById('formResetNtotalrisk');

            if (form) {
                if (window.showAlert) {
                    window.showAlert('info', 'Mereset nilai total risiko...');
                }

                form.submit();
            } else {
                alert('Form reset tidak ditemukan');
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const formUpdateNtotalrisk = document.getElementById('formUpdateNtotalrisk');
        if (formUpdateNtotalrisk) {
            formUpdateNtotalrisk.addEventListener('submit', function (e) {
                const ntotalrisk = document.getElementById('ntotalrisk').value;

                if (!ntotalrisk || ntotalrisk === '') {
                    e.preventDefault();
                    alert('Silakan pilih nilai total risiko terlebih dahulu');
                    return false;
                }

                if (confirm('Apakah Anda yakin ingin mengubah nilai total risiko menjadi ' + ntotalrisk + '?')) {
                    return true;
                } else {
                    e.preventDefault();
                    return false;
                }
            });
        }

        const ntotalriskSelect = document.getElementById('ntotalrisk');
        if (ntotalriskSelect) {
            ntotalriskSelect.addEventListener('change', function () {
                const value = this.value;
                const colors = {
                    '1': 'text-primary',
                    '2': 'text-success',
                    '3': 'text-warning',
                    '4': 'text-orange',
                    '5': 'text-danger'
                };

                this.className = 'form-control form-control-lg';

                if (colors[value]) {
                    this.className += ' ' + colors[value];
                }
            });

            const initialValue = ntotalriskSelect.value;
            if (initialValue) {
                const event = new Event('change');
                ntotalriskSelect.dispatchEvent(event);
            }
        }

        const forms = document.querySelectorAll('form');
        forms.forEach(function (form) {
            form.addEventListener('submit', function () {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn && !submitBtn.disabled) {
                    submitBtn.disabled = true;

                    // Re-enable after 3 seconds as failsafe
                    setTimeout(function () {
                        submitBtn.disabled = false;
                    }, 3000);
                }
            });
        });

        const alertContainer = document.getElementById('alert-container');
        if (alertContainer) {
            // Observer to scroll to alert when shown
            const observer = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    if (mutation.target.style.display !== 'none') {
                        window.scrollTo({
                            top: 0,
                            behavior: 'smooth'
                        });
                    }
                });
            });

            observer.observe(alertContainer, {
                attributes: true,
                attributeFilter: ['style']
            });
        }

        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();

                const formTtd = document.getElementById('formTtd');
                if (formTtd) {
                    formTtd.dispatchEvent(new Event('submit', {
                        bubbles: true,
                        cancelable: true
                    }));
                }
            }
        });

        const firstEmptyField = document.querySelector('.auto-save[required]:not([value]), .auto-save[required][value=""]');
        if (firstEmptyField) {
            firstEmptyField.focus();
        }

        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                const savedTab = sessionStorage.getItem('activeTab');
                if (savedTab) {
                    const tabTrigger = document.querySelector('#nav-tab a[href="' + savedTab + '"]');
                    if (tabTrigger) {
                        const tab = new bootstrap.Tab(tabTrigger);
                        tab.show();
                    }
                }
            }
        });

    });

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

    function formatDate(date) {
        const d = new Date(date);
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        return `${day}/${month}/${year}`;
    }

    function isValidDate(dateString) {
        const regex = /^(\d{2})\/(\d{2})\/(\d{4})$/;
        if (!regex.test(dateString)) return false;

        const [, day, month, year] = dateString.match(regex);
        const date = new Date(year, month - 1, day);

        return date.getFullYear() == year &&
            date.getMonth() == month - 1 &&
            date.getDate() == day;
    }

    function debugLog(message, data) {
        if (typeof console !== 'undefined' && console.log) {
            console.log('[ShowProfilResiko Debug]', message, data || '');
        }
    }

    function isInViewport(element) {
        const rect = element.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }

    window.addEventListener('error', function (e) {
        console.error('Global error:', e.error);

        if (e.error && e.error.message && e.error.message.includes('AJAX')) {
            if (window.showAlert) {
                window.showAlert('danger', 'Terjadi kesalahan koneksi. Silakan periksa koneksi internet Anda.');
            }
        }
    });

    const KESIMPULAN_SAVE_DELAY = 2000; // 2 detik delay untuk textarea

    $('.auto-save-kesimpulan').on('input change blur', function () {
        const $field = $(this);
        const fieldName = $field.data('field');
        const fieldValue = $field.val();
        const recordId = $('input[name="id"]').first().val();

        clearTimeout(window.kesimpulanSaveTimeout);

        $('#indicator-' + fieldName).show();
        $('#status-' + fieldName).hide();

        window.kesimpulanSaveTimeout = setTimeout(function () {
            autoSaveKesimpulan(recordId, fieldValue);
        }, KESIMPULAN_SAVE_DELAY);
    });

    function autoSaveKesimpulan(id, value) {
        if (!id) {
            showAlertKesimpulan('danger', 'ID record tidak ditemukan');
            $('#indicator-kesimpulan').hide();
            return;
        }

        $.ajax({
            url: '<?= base_url("Showprofilresiko/ajaxSaveKesimpulan"); ?>',
            type: 'POST',
            dataType: 'json',
            data: {
                id: id,
                kesimpulan: value
            },
            success: function (response) {
                $('#indicator-kesimpulan').hide();

                if (response.success) {
                    $('#status-kesimpulan').fadeIn();
                    setTimeout(function () {
                        $('#status-kesimpulan').fadeOut();
                    }, 3000);
                } else {
                    showAlertKesimpulan('danger', response.message || 'Gagal menyimpan kesimpulan');
                }
            },
            error: function (xhr, status, error) {
                $('#indicator-kesimpulan').hide();
                console.error('AJAX Error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                showAlertKesimpulan('danger', 'Terjadi kesalahan saat menyimpan kesimpulan');
            }
        });
    }

    // Form submit untuk kesimpulan
    $('#formKesimpulan').on('submit', function (e) {
        e.preventDefault();

        const formData = {
            id: $('input[name="id"]').val(),
            kesimpulan: $('#kesimpulan').val()
        };

        if (!formData.kesimpulan || formData.kesimpulan.trim() === '') {
            showAlertKesimpulan('warning', 'Mohon isi kesimpulan terlebih dahulu');
            $('#kesimpulan').addClass('is-invalid');
            return;
        }

        $('#kesimpulan').removeClass('is-invalid');

        const $submitBtn = $(this).find('button[type="submit"]');
        const originalText = $submitBtn.html();
        $submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...').prop('disabled', true);

        $.ajax({
            url: '<?= base_url("Showprofilresiko/ajaxSaveKesimpulan"); ?>',
            type: 'POST',
            dataType: 'json',
            data: formData,
            success: function (response) {
                $submitBtn.html(originalText).prop('disabled', false);

                if (response.success) {
                    showAlertKesimpulan('success', response.message || 'Kesimpulan berhasil disimpan');
                    $('#status-kesimpulan').fadeIn();
                    setTimeout(function () {
                        $('#status-kesimpulan').fadeOut();
                    }, 5000);
                } else {
                    showAlertKesimpulan('danger', response.message || 'Gagal menyimpan kesimpulan');
                }
            },
            error: function (xhr, status, error) {
                $submitBtn.html(originalText).prop('disabled', false);
                console.error('Submit Error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                showAlertKesimpulan('danger', 'Terjadi kesalahan saat menyimpan kesimpulan. Silakan coba lagi.');
            }
        });
    });

    function showAlertKesimpulan(type, message) {
        const alertClass = type === 'success' ? 'alert-success' :
            type === 'warning' ? 'alert-warning' : 'alert-danger';
        const iconClass = type === 'success' ? 'check-circle' :
            type === 'warning' ? 'exclamation-triangle' : 'exclamation-circle';
        const $alert = $('#alert-kesimpulan .alert');

        $alert.removeClass('alert-success alert-danger alert-warning alert-info')
            .addClass(alertClass);

        $('#alert-kesimpulan-message').html(
            '<i class="fas fa-' + iconClass + '"></i> ' + message
        );

        $('#alert-kesimpulan').fadeIn();

        setTimeout(function () {
            $('#alert-kesimpulan').fadeOut();
        }, 5000);
    }

    // Close alert kesimpulan
    $('#alert-kesimpulan .close').on('click', function () {
        $('#alert-kesimpulan').fadeOut();
    });

    // Remove invalid class on input
    $('#kesimpulan').on('input', function () {
        $(this).removeClass('is-invalid');
    });

    window.addEventListener('unhandledrejection', function (e) {
        console.error('Unhandled promise rejection:', e.reason);
    });
</script>

<style>
    /* Factor Card Styling */
    .factor-card {
        border-left: 5px solid #343a40;
        border-radius: 8px;
        background-color: #f8f9fa;
        transition: transform 0.3s ease, box-shadow 0.3s ease, border-left 0.3s ease;
    }

    .approved-card {
        border-left: 5px solid #28a745 !important;
    }

    .factor-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .factor-card:hover .approved-card {
        border-left: 5px solid #218838 !important;
    }

    .text-success {
        color: #28a745 !important;
    }

    .text-danger {
        color: #dc3545 !important;
    }

    .text-muted {
        color: #6c757d !important;
    }

    .custom-link {
        font-size: 18px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        color: #0d6efd;
        font-weight: 500;
    }

    .custom-link::before {
        content: "←";
        transition: transform 0.3s ease;
    }

    .custom-link:hover::before {
        transform: translateX(-6px);
    }

    .custom-link:hover {
        text-decoration: underline;
        color: #0a58ca;
    }

    .custom-element0,
    .custom-element1,
    .custom-element2,
    .custom-element3,
    .custom-element4,
    .custom-element5 {
        text-align: center;
        width: 100%;
        height: auto;
        color: black;
        font-size: 0.9rem;
    }

    .custom-element6,
    .custom-element7,
    .custom-element8,
    .custom-element9 {
        max-width: 100%;
        margin: 0 auto 20px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease-in-out;
    }

    .custom-element6:hover,
    .custom-element7:hover,
    .custom-element8:hover,
    .custom-element9:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    }

    .btn-success {
        background-color: #ffffff;
        border-color: #28a745;
        border-radius: 25px;
        padding: 12px 30px;
        font-size: 1.1rem;
        font-weight: 600;
        color: #28a745;
        margin: 0 15px;
    }

    .btn-success:hover {
        background-color: #28a745;
        border-color: #ffffff;
        transform: translateY(-2px);
        color: #ffffff;
    }

    .beautiful-alert {
        background-color: #e0f7fa;
        color: #007bb5;
        border-color: #b2ebf2;
        border-radius: 10px;
        padding: 15px 25px;
        display: flex;
        align-items: center;
        font-size: 1.1rem;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .beautiful-alert strong {
        color: #0056b3;
    }

    .beautiful-alert .alert-icon {
        margin-right: 15px;
        font-size: 1.8rem;
        color: #007bff;
    }

    .rating-center-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        /* penuh 1 layar */
        background: #f8f9fa;
        /* opsional agar kontras */
    }


    .card {
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        transition: all 0.3s ease-in-out;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.25);
    }

    .card-title {
        font-size: clamp(1.2rem, 2.5vw, 1.8rem);
    }

    .card-text {
        font-size: clamp(0.9rem, 2vw, 1.1rem);
    }

    .save-indicator {
        margin-left: 10px;
        font-size: 14px;
        display: inline-block;
        vertical-align: middle;
    }

    .save-indicator i {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    /* Form Validation */
    .auto-save.is-invalid {
        border-color: #dc3545;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right calc(0.375em + 0.1875rem) center;
        background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        padding-right: calc(1.5em + 0.75rem);
    }

    .auto-save.is-invalid:focus {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
    }

    /* Success Status */
    [id^="status-"] {
        font-size: 12px;
        margin-top: 5px;
        display: block;
    }

    [id^="status-"] i {
        margin-right: 5px;
    }

    /* Alert Container */
    #alert-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        max-width: 500px;
        animation: slideInRight 0.3s ease-out;
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

    #alert-container .alert {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        border-left: 4px solid;
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

    #alert-container .alert-info {
        border-left-color: #17a2b8;
    }

    /* Responsive Alert */
    @media (max-width: 768px) {
        #alert-container {
            left: 10px;
            right: 10px;
            min-width: auto;
            top: 10px;
        }
    }

    /* Tab Navigation */
    .nav-tabs .nav-link {
        transition: all 0.3s ease;
    }

    .nav-tabs .nav-link.active {
        background-color: #fff;
        border-color: #dee2e6 #dee2e6 #fff;
        font-weight: 600;
    }

    .nav-tabs .nav-link:hover {
        border-color: #e9ecef #e9ecef #dee2e6;
    }

    /* Form Control States */
    .form-control:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .form-control:disabled {
        background-color: #e9ecef;
        cursor: not-allowed;
        opacity: 0.6;
    }

    /* Button Loading State */
    button[disabled] {
        cursor: not-allowed;
        opacity: 0.65;
    }

    button i.fa-spinner {
        margin-right: 5px;
    }

    /* Collapse Icon Animation */
    .collapse-icon {
        transition: transform 0.3s ease;
    }

    .collapsed .collapse-icon {
        transform: rotate(0deg);
    }

    .collapse-icon.fa-chevron-up {
        transform: rotate(180deg);
    }

    /* Label with Required Indicator */
    label .text-danger {
        font-weight: bold;
    }

    /* Smooth Transitions */
    .card,
    .alert,
    .btn,
    .form-control {
        transition: all 0.3s ease;
    }

    /* Hover Effects */
    .btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
    }

    /* Loading Overlay (Optional) */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 10000;
    }

    .loading-overlay.active {
        display: flex;
    }

    .loading-spinner {
        border: 5px solid #f3f3f3;
        border-top: 5px solid #3498db;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        animation: spin 1s linear infinite;
    }

    /* Print Styles */
    @media print {

        #alert-container,
        .save-indicator,
        [id^="status-"],
        button {
            display: none !important;
        }
    }

    /* Focus Visible for Accessibility */
    .form-control:focus-visible {
        outline: 2px solid #0d6efd;
        outline-offset: 2px;
    }

    /* Custom Scrollbar for Alert */
    #alert-container .alert {
        max-height: 200px;
        overflow-y: auto;
    }

    #alert-container .alert::-webkit-scrollbar {
        width: 6px;
    }

    #alert-container .alert::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    #alert-container .alert::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 3px;
    }

    #alert-container .alert::-webkit-scrollbar-thumb:hover {
        background: #555;
    }

    #formKesimpulan textarea {
        resize: vertical;
        min-height: 150px;
    }

    #formKesimpulan .form-control:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    #formKesimpulan .is-invalid {
        border-color: #dc3545;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right calc(0.375em + 0.1875rem) top calc(0.375em + 0.1875rem);
        background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
    }

    #alert-kesimpulan {
        margin-bottom: 15px;
    }

    #alert-kesimpulan .alert {
        animation: slideInDown 0.3s ease-out;
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
</style>