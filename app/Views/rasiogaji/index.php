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

<!-- Begin Page Content -->
<div class="container-fluid">
    <div id="ajax-response-message"></div>
    <div class="card">
        <div class="card-body">
            <?php
            // pastikan variabel ada dan tidak null
            $kodebpr = $kodebpr ?? null;
            $periodeId = $periodeId ?? null;
            ?>
            <div class="card-body custom-badge-container">
                <?php
                // Check if all accdekom records are approved
                $allAccdekomApproved = true;
                if (!empty($accdekomData)) {
                    foreach ($accdekomData as $item) {
                        if ($item['accdekom'] != 1) {
                            $allAccdekomApproved = false;
                            break;
                        }
                    }
                }

                // Check if all accdirut records are approved
                $allAccdirutApproved = true;
                if (!empty($accdirutData)) {
                    foreach ($accdirutData as $item) {
                        if ($item['is_approved'] != 1) {
                            $allAccdirutApproved = false;
                            break;
                        }
                    }
                }
                ?>

                <?php if (!empty($accdekomData)): ?>
                    <?php if ($allAccdekomApproved): ?>
                        <span class="badge badge-success" style="font-size: 14px;">
                            Disetujui oleh <strong>Komisaris Utama</strong><br>
                            Tanggal: <?php
                            $date = strtotime($accdekomData[count($accdekomData) - 1]['accdekom_at']);
                            setlocale(LC_TIME, 'id_ID.UTF-8', 'id_ID', 'ind');
                            echo strftime("%d %B %Y %H:%M:%S", $date);
                            ?>
                        </span>
                    <?php else: ?>
                        <span class="badge badge-secondary" style="font-size: 14px;">
                            Belum Disetujui Seluruhnya<br>Oleh Komisaris Utama
                        </span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if (!empty($accdirutData)): ?>
                    <?php
                    $item = $accdirutData[0]; // Ambil data pertama
                    if ($item['is_approved'] == 1): ?>
                        <span class="badge badge-success" style="font-size: 14px;">
                            Disetujui oleh <strong>Direktur Utama</strong><br>
                            Tanggal: <?php
                            setlocale(LC_TIME, 'id_ID.UTF-8', 'id_ID', 'ind');
                            $date = strtotime($item['approved_at']);
                            echo strftime("%d %B %Y %H:%M:%S", $date);
                            ?>
                        </span>
                    <?php else: ?>
                        <span class="badge badge-secondary" style="font-size: 14px;">
                            Belum Disetujui Seluruhnya<br>Oleh Direktur Utama
                        </span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <h1 class="h3 mb-4 text-gray-800 text-center"><?= $judul; ?><br>(E0600)</h1>
            <form id="paketKebijakanForm">
                <input type="hidden" name="user_id" id="user_id" value="<?= esc($userId ?? '-') ?>">
                <input type="hidden" name="fullname" id="fullname" value="<?= esc($fullname ?? '-') ?>">

                <!-- 1.1. Gaji Direksi dan Dewan Komisaris -->
                <table class="table table-info table-hover mb-4">
                    <thead class="thead-primary">
                        <tr>
                            <th>1. Rasio gaji pegawai yang tertinggi (a) dan gaji pegawai yang terendah (b) dalam 1
                                (satu) tahun</th>
                        </tr>
                    </thead>
                </table>

                <div class="row g-2">
                    <!-- Baris pertama: Field a (kiri) dan Field b (kanan) -->
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="pegawaitinggi">a. Gaji pegawai yang tertinggi (Rp):</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="pegawaitinggi" id="pegawaitinggi"
                                    class="form-control format-currency" style="height: 45px"
                                    value="<?= esc($rasiogaji[0]['pegawaitinggi'] ?? '') ?>" required>
                                <input type="hidden" name="pegawaitinggi" id="pegawaitinggi_raw"
                                    value="<?= esc($rasiogaji[0]['pegawaitinggi'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="pegawairendah">b. Gaji pegawai yang terendah (Rp):</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="pegawairendah" id="pegawairendah"
                                    class="form-control format-currency" style="height: 45px"
                                    value="<?= esc($rasiogaji[0]['pegawairendah'] ?? '') ?>" required>
                                <input type="hidden" name="pegawairendah" id="pegawairendah_raw"
                                    value="<?= esc($rasiogaji[0]['pegawairendah'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Baris kedua: Field c di tengah -->
                    <div class="col-md-6 offset-md-3 mt-3">
                        <div class="form-group mb-3 text-center">
                            <label for="pegawaitinggirendah">Rasio gaji pegawai tertinggi dan terendah:</label>
                            <div class="input-group">
                                <input type="text" name="pegawaitinggirendah" id="pegawaitinggirendah"
                                    class="form-control" style="height: 45px"
                                    value="<?= esc($rasiogaji[0]['pegawaitinggirendah'] ?? '') ?>" required readonly>
                                <input type="hidden" name="pegawaitinggirendah" id="pegawaitinggirendah_raw"
                                    value="<?= esc($rasiogaji[0]['pegawaitinggirendah'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 1.2. Tunjangan -->
                <table class="table table-info table-hover mb-4">
                    <thead class="thead-primary">
                        <tr>
                            <th>2. Rasio gaji anggota direksi yang tertinggi (a) dan gaji anggota direksi yang terendah
                                (b) dalam 1 (satu) tahun</th>
                        </tr>
                    </thead>
                </table>

                <div class="row g-2">
                    <!-- Baris pertama: Field a (kiri) dan Field b (kanan) -->
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="dirtinggi">a. Gaji direksi yang tertinggi (Rp):</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="dirtinggi" id="dirtinggi" class="form-control format-currency"
                                    style="height: 45px" value="<?= esc($rasiogaji[0]['dirtinggi'] ?? '') ?>" required>
                                <input type="hidden" name="dirtinggi" id="dirtinggi_raw"
                                    value="<?= esc($rasiogaji[0]['dirtinggi'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="dirrendah">b. Gaji direksi yang terendah (Rp):</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="dirrendah" id="dirrendah" class="form-control format-currency"
                                    style="height: 45px" value="<?= esc($rasiogaji[0]['dirrendah'] ?? '') ?>" required>
                                <input type="hidden" name="dirrendah" id="dirrendah_raw"
                                    value="<?= esc($rasiogaji[0]['dirrendah'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Baris kedua: Field c di tengah -->
                    <div class="col-md-6 offset-md-3 mt-3">
                        <div class="form-group mb-3 text-center">
                            <label for="direksitinggirendah">Rasio gaji direksi tertinggi dan terendah:</label>
                            <div class="input-group">
                                <input type="text" name="direksitinggirendah" id="direksitinggirendah"
                                    class="form-control" style="height: 45px"
                                    value="<?= esc($rasiogaji[0]['direksitinggirendah'] ?? '') ?>" required readonly>
                                <input type="hidden" name="direksitinggirendah" id="direksitinggirendah_raw"
                                    value="<?= esc($rasiogaji[0]['direksitinggirendah'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 1.3. Tunjangan -->
                <table class="table table-info table-hover mb-4">
                    <thead class="thead-primary">
                        <tr>
                            <th>3. Rasio gaji anggota dewan komisaris yang tertinggi (a) dan gaji anggota dewan
                                komisaris yang terendah (b) dalam 1 (satu) tahun</th>
                        </tr>
                    </thead>
                </table>

                <div class="row g-2">
                    <!-- Baris pertama: Field a (kiri) dan Field b (kanan) -->
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="dekomtinggi">a. Gaji dewan komisaris yang tertinggi (Rp):</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="dekomtinggi" id="dekomtinggi"
                                    class="form-control format-currency" style="height: 45px"
                                    value="<?= esc($rasiogaji[0]['dekomtinggi'] ?? '') ?>" required>
                                <input type="hidden" name="dekomtinggi" id="dekomtinggi_raw"
                                    value="<?= esc($rasiogaji[0]['dekomtinggi'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="dekomrendah">b. Gaji dewan komisaris yang terendah (Rp):</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="dekomrendah" id="dekomrendah"
                                    class="form-control format-currency" style="height: 45px"
                                    value="<?= esc($rasiogaji[0]['dekomrendah'] ?? '') ?>" required>
                                <input type="hidden" name="dekomrendah" id="dekomrendah_raw"
                                    value="<?= esc($rasiogaji[0]['dekomrendah'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Baris kedua: Field c di tengah -->
                    <div class="col-md-6 offset-md-3 mt-3">
                        <div class="form-group mb-3 text-center">
                            <label for="dekomtinggirendah">c. Rasio gaji dewan komisaris yang tinggi dan terendah
                                (Rp):</label>
                            <div class="input-group">
                                <input type="text" name="dekomtinggirendah" id="dekomtinggirendah" class="form-control"
                                    style="height: 45px" value="<?= esc($rasiogaji[0]['dekomtinggirendah'] ?? '') ?>"
                                    required readonly>
                                <input type="hidden" name="dekomtinggirendah" id="dekomtinggirendah_raw"
                                    value="<?= esc($rasiogaji[0]['dekomtinggirendah'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 1.4. Tunjangan -->
                <table class="table table-info table-hover mb-4">
                    <thead class="thead-primary">
                        <tr>
                            <th>4. Rasio gaji anggota direksi yang tertinggi (a) dan gaji anggota dewan Komisaris yang
                                tertinggi (b) dalam 1 (satu) tahun</th>
                        </tr>
                    </thead>
                </table>

                <div class="row g-2">
                    <!-- Baris pertama: Field a (kiri) dan Field b (kanan) -->
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="dirtinggi">a. Gaji direksi yang tertinggi (Rp):</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="dirtinggi" id="dirtinggi" class="form-control format-currency"
                                    style="height: 45px" value="<?= esc($rasiogaji[0]['dirtinggi'] ?? '') ?>" readonly
                                    required>
                                <input type="hidden" name="dirtinggi" id="dirtinggi_raw"
                                    value="<?= esc($rasiogaji[0]['dirtinggi'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="dekomtinggi">b. Gaji dewan komisaris yang tertinggi (Rp):</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="dekomtinggi" id="dekomtinggi"
                                    class="form-control format-currency" style="height: 45px"
                                    value="<?= esc($rasiogaji[0]['dekomtinggi'] ?? '') ?>" readonly required>
                                <input type="hidden" name="dekomtinggi" id="dekomtinggi_raw"
                                    value="<?= esc($rasiogaji[0]['dekomtinggi'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Baris kedua: Field c di tengah -->
                    <div class="col-md-6 offset-md-3 mt-3">
                        <div class="form-group mb-3 text-center">
                            <label for="dirdekomtinggirendah">c. Rasio gaji direksi dan dewan komisaris yang tertinggi
                                dan terendah (Rp):</label>
                            <div class="input-group">
                                <input type="text" name="dirdekomtinggirendah" id="dirdekomtinggirendah"
                                    class="form-control" style="height: 45px"
                                    value="<?= esc($rasiogaji[0]['dirdekomtinggirendah'] ?? '') ?>" required readonly>
                                <input type="hidden" name="dirdekomtinggirendah" id="dirdekomtinggirendah_raw"
                                    value="<?= esc($rasiogaji[0]['dirdekomtinggirendah'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 1.4. Tunjangan -->
                <table class="table table-info table-hover mb-4">
                    <thead class="thead-primary">
                        <tr>
                            <th>5. Rasio gaji direksi yang tertinggi (a) dan gaji pegawai yang tertinggi (b)
                                dalam 1 (satu) tahun</th>
                        </tr>
                    </thead>
                </table>

                <div class="row g-2">
                    <!-- Baris pertama: Field a (kiri) dan Field b (kanan) -->
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="dirtinggi">a. Gaji direksi yang tertinggi (Rp):</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="dirtinggi" id="dirtinggi" class="form-control format-currency"
                                    style="height: 45px" value="<?= esc($rasiogaji[0]['dirtinggi'] ?? '') ?>" readonly
                                    required>
                                <input type="hidden" name="dirtinggi" id="dirtinggi_raw"
                                    value="<?= esc($rasiogaji[0]['dirtinggi'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="pegawaitinggi">b. Gaji pegawai yang tertinggi (Rp):</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="pegawaitinggi" id="pegawaitinggi"
                                    class="form-control format-currency" style="height: 45px"
                                    value="<?= esc($rasiogaji[0]['pegawaitinggi'] ?? '') ?>" readonly required>
                                <input type="hidden" name="pegawaitinggi" id="pegawaitinggi_raw"
                                    value="<?= esc($rasiogaji[0]['pegawaitinggi'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Baris kedua: Field c di tengah -->
                    <div class="col-md-6 offset-md-3 mt-3">
                        <div class="form-group mb-3 text-center">
                            <label for="dirpegawaitinggirendah">c. Rasio gaji direksi dan pegawai yang tertinggi dan
                                terendah (Rp):</label>
                            <div class="input-group">
                                <input type="text" name="dirpegawaitinggirendah" id="dirpegawaitinggirendah"
                                    class="form-control" style="height: 45px"
                                    value="<?= esc($rasiogaji[0]['dirpegawaitinggirendah'] ?? '') ?>" required readonly>
                                <input type="hidden" name="dirpegawaitinggirendah" id="dirpegawaitinggirendah_raw"
                                    value="<?= esc($rasiogaji[0]['dirpegawaitinggirendah'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <table class="table table-bordered table-hover mb-5">
                    <?php
                    $tindaklanjut = '';
                    $id = '';

                    foreach ($penjelastindak as $item) {
                        if ($item['id']) {
                            $id = $item['id'] ?? '';
                            $tindaklanjut = $item['tindaklanjut'] ?? '';
                            break;
                        }
                    }
                    ?>

                    <div>
                        <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                            <div class="btn-group" role="group" aria-label="Button group">
                                <?php if (empty($penjelastindak)): ?>
                                    <button type="button" class="btn btn-primary3 btn-sm" data-toggle="modal"
                                        data-target="#modalTambahketerangan">
                                        <i class="fa fa-plus"></i> Penjelasan Lebih Lanjut
                                    </button>
                                <?php else: ?>
                                    <?php foreach ($penjelastindak as $row): ?>
                                        <button type="button" class="btn btn-primary2 btn-sm" data-toggle="modal"
                                            data-target="#modaleditketerangan" id="btn-edit" data-id="<?= esc($row['id']); ?>"
                                            data-tindaklanjut="<?= esc($row['tindaklanjut'] ?? ''); ?>">
                                            <i class="fa fa-edit"></i>Ubah Penjelasan Lebih Lanjut
                                        </button>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <tbody>
                        <?php if (empty($penjelastindak)) { ?>
                            <tr>
                                <th class="table-info" style="width: 30%; color: black;">Penjelasan Lebih Lanjut :
                                </th>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-center">
                                    <em>Tidak ada penjelasan lebih lanjut</em>
                                </td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($penjelastindak as $row): ?>
                                <tr>
                                    <th colspan="2" class="table-info" style="width: 30%; color: black;">Penjelasan Lebih Lanjut
                                        :
                                    </th>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <?= isset($row['tindaklanjut']) ? esc($row['tindaklanjut']) : 'Data tidak tersedia'; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <button type="button" class="btn btn-outline-danger btn-sm" id="btn-setnulltindak"
                                            data-id="<?= $row['id']; ?>"><i class="fa fa-trash"></i></button>
                                    </td>
                                </tr>
                                <tr height="40">
                                    <td colspan="3" style="background-color: white; border-color: white;"></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php } ?>
                    </tbody>
                </table>
                <div class="row" style="display: flex; justify-content: space-between;">
                    <?php if (($userInGroupAdmin || $userInGroupDekom) && !empty($accdekomData)): ?>
                        <div class="col-md-3">
                            <div class="card shadow-sm approval-card">
                                <div class="card-body approval-card-body">
                                    <div class="approval-badge-container"> <span class="badge approval-badge">Approval
                                            Komisaris
                                            Utama</span> </div>

                                    <div class="approval-buttons-container"> <a
                                            href="<?= base_url('Rasiogaji/approveSemuaKom') ?>"
                                            class="btn btn-success approval-btn approval-btn-approve"
                                            onclick="return confirm('Apakah Anda yakin hendak melakukan approval?');">
                                            Setuju
                                        </a>
                                        <a href="<?= base_url('Rasiogaji/unapproveSemuaKom') ?>"
                                            class="btn btn-danger approval-btn approval-btn-reject"
                                            onclick="return confirm('Apakah Anda yakin hendak melakukan pembatalan semua approval?');">
                                            Tolak
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (($userInGroupAdmin || $userInGroupDireksi) && !empty($accdirutData)): ?>
                        <div class="col-md-3">
                            <div class="card shadow-sm approvaldir-card">
                                <div
                                    class="card-body approvaldir-card-body <?php echo (isset($canApprove) && !$canApprove) ? 'disabled-btn' : ''; ?>">
                                    <div class="approval-badge-container">
                                        <span class="badge approval-badge">Approval
                                            Direktur Utama
                                        </span>
                                    </div>
                                    <div class="approval-buttons-container"> <a
                                            href="<?= base_url('Rasiogaji/approveSemuaDirut') ?>"
                                            class="btn btn-success approval-btn approval-btn-approve <?php echo (isset($canApprove) && !$canApprove) ? 'disabled-btn' : ''; ?>"
                                            onclick="return confirm('Apakah Anda yakin ingin melakukan approval?');">
                                            Setuju
                                        </a>
                                        <a href="<?= base_url('Rasiogaji/unapproveSemuaDirut') ?>"
                                            class="btn btn-danger approval-btn approval-btn-reject <?php echo (isset($canApprove) && !$canApprove) ? 'disabled-btn' : ''; ?>"
                                            onclick="return confirm('Batalkan semua approval?');">
                                            Tolak
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Button Submit -->
                <div class="d-flex justify-content-center gap-2 mb-5 mt-4">
                    <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                        <a href="<?= base_url('Rasiogaji/exporttxtrasiogaji'); ?>" class="btn btn-secondary btn-sm">
                            <i class="fa fa-file-alt"></i> Export .txt
                        </a>
                        <button type="submit" class="btn btn-primary">Simpan Data</button>
                    <?php endif; ?>
                    <?php if ($userInGroupDekom || $userInGroupDireksi || $userInGroupPE || $userInGroupAdmin): ?>
                        <td>
                            <?php
                            // $Id = session()->get('id');
                            $subkategori = 'Rasiogaji';
                            $currentUserId = session()->get('user_id');
                            $activePeriodeId = session()->get('active_periode');

                            // Hitung unread count dengan ID yang benar
                            $initialUnreadCount = $commentReadsModel->countUnreadCommentsForUserByFactor($subkategori, $kodebpr, $currentUserId, $activePeriodeId);

                            // echo "<!-- Debug: Id=$Id, unreadCount=$initialUnreadCount -->";
                            ?>
                            <div class="komentar-btn-wrapper">
                                <button type="button" data-toggle="modal" data-target="#modalTambahkomentar"
                                    id="btn-komentar-<?= $subkategori; ?>" class="btn btn-success btn-sm"
                                    style="font-weight: 610;" data-id="<?= $subkategori; ?>" data-kodebpr="<?= $kodebpr; ?>"
                                    data-user-id="<?= $currentUserId; ?>" data-periode-id="<?= $activePeriodeId; ?>">
                                    <i class="fas fa-comment"></i>
                                    <span id="notification-badge-<?= $subkategori; ?>"
                                        class="badge badge-danger notification-badge"
                                        style="display: <?= $initialUnreadCount > 0 ? 'inline-flex' : 'none'; ?>;">
                                        <?= $initialUnreadCount ?>
                                    </span>
                                </button>
                            </div>
                        </td>
                    <?php endif; ?>
                </div>

                <!-- Response Message -->
                <div id="ajax-response-message"></div>
            </form>

            <!-- Success or error message container -->
            <div id="ajax-response-message"></div>

            <div class="cardpilihfaktor">
                <div class="cardpilihfaktor-header">
                    <h6>Pilih Halaman</h6>
                </div>
                <div class="cardpilihfaktor-body">
                    <div class="d-flex justify-content-center">
                        <div class="btn-toolbar" role="toolbar" aria-label="Toolbar with button groups">
                            <div class="btn-group me-2" role="group" aria-label="First group">
                                <button type="button" class="btn btn-outline-primary btn-sm"
                                    onclick="window.location.href='<?= base_url('Paketkebijakandirdekom') ?>'">
                                    << </button>
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
                                        <button style="background-color: #000; color: #fff;" type="button"
                                            class="btn btn-outline-primary btn-sm"
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
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            onclick="window.location.href='<?= base_url('ShowTransparansi') ?>'">All</button>
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            onclick="window.location.href='<?= base_url('Rapat') ?>'">>></button>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-1">
                        <a href="<?= base_url('Rapat'); ?>" class="btn btn-link btn-sm">Kembali ke halaman
                            periode</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambahkomentar">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="<?= base_url('Rasiogaji/Tambahkomentar'); ?>" method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Komentar Direksi dan Dewan Komisaris</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php date_default_timezone_set('Asia/Jakarta'); ?>

                    <input type="hidden" name="id" id="id">
                    <input type="hidden" name="kodebpr" value="<?= $kodebpr ?>">
                    <!-- Tambahkan ini -->

                    <div class="form-group">
                        <label for="komentarLama">Komentar Saat Ini:</label>
                        <ul id="komentarLamaList" style="list-style-type: none; padding-left: 0;">
                            <li>Memuat komentar...</li>
                        </ul>
                    </div>

                    <?php if ($userInGroupAdmin || $userInGroupDekom || $userInGroupDireksi || $userInGroupPE || $userInGroupDekom2 || $userInGroupDireksi2): ?>
                        <input type="hidden" name="fullname" value="<?= htmlspecialchars($fullname) ?>">
                        <input type="hidden" name="date" value="<?= date('Y-m-d H:i:s') ?>">
                        <div class="form-group">
                            <label for="komentar">Tambahkan Komentar Baru:</label>
                            <textarea class="form-control" name="komentar" id="komentar" style="height: 100px"></textarea>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="TambahKomentar" class="btn btn-primary">Simpan
                        Komentar</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambahketerangan">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Penjelasan lebih lanjut (Opsional)</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('Rasiogaji/tambahketerangan'); ?>" method="post">
                    <input type="hidden" name="id" id="id-penjelastindak"> <!-- Hidden field to pass the ID -->

                    <div class="form-group">
                        <label for="tindaklanjut" class="form-label">Penjelasan lebih lanjut (Opsional):
                        </label>
                        <textarea class="form-control" name="tindaklanjut" id="tindaklanjut"
                            style="height: 150px"></textarea>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" name="tambahketerangan" class="btn btn-primary">Ubah Data</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
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


<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

<script>
    $(document).ready(function () {
        // Function untuk format currency
        function formatCurrency(value) {
            return new Intl.NumberFormat('id-ID').format(value);
        }

        // Function untuk parse currency
        function parseCurrency(value) {
            if (!value) return 0;
            return parseFloat(value.toString().replace(/\D/g, '')) || 0;
        }

        // UNIFIED CALCULATION FUNCTION - Menggabungkan semua perhitungan rasio
        function calculateAllRatios() {
            console.log('Calculating all ratios...');

            // 1. Rasio pegawai tinggi/rendah
            var pegawaiTinggi = parseCurrency($('#pegawaitinggi_raw').val() || $('#pegawaitinggi').val());
            var pegawaiRendah = parseCurrency($('#pegawairendah_raw').val() || $('#pegawairendah').val());

            if (pegawaiRendah > 0 && pegawaiTinggi > 0) {
                var ratioPegawai = pegawaiTinggi / pegawaiRendah;
                $('#pegawaitinggirendah').val(ratioPegawai.toFixed(2));
                $('#pegawaitinggirendah_raw').val(ratioPegawai.toFixed(2));
                console.log('Ratio pegawai:', ratioPegawai.toFixed(2));
            } else {
                $('#pegawaitinggirendah').val('');
                $('#pegawaitinggirendah_raw').val('');
            }

            // 2. Rasio direksi tinggi/rendah
            var dirTinggi = parseCurrency($('#dirtinggi_raw').val() || $('#dirtinggi').val());
            var dirRendah = parseCurrency($('#dirrendah_raw').val() || $('#dirrendah').val());

            if (dirRendah > 0 && dirTinggi > 0) {
                var ratioDir = dirTinggi / dirRendah;
                $('#direksitinggirendah').val(ratioDir.toFixed(2));
                $('#direksitinggirendah_raw').val(ratioDir.toFixed(2));
                console.log('Ratio direksi:', ratioDir.toFixed(2));
            } else {
                $('#direksitinggirendah').val('');
                $('#direksitinggirendah_raw').val('');
            }

            // 3. Rasio dewan komisaris tinggi/rendah
            var dekomTinggi = parseCurrency($('#dekomtinggi_raw').val() || $('#dekomtinggi').val());
            var dekomRendah = parseCurrency($('#dekomrendah_raw').val() || $('#dekomrendah').val());

            if (dekomRendah > 0 && dekomTinggi > 0) {
                var ratioDekom = dekomTinggi / dekomRendah;
                $('#dekomtinggirendah').val(ratioDekom.toFixed(2));
                $('#dekomtinggirendah_raw').val(ratioDekom.toFixed(2));
                console.log('Ratio dewan komisaris:', ratioDekom.toFixed(2));
            } else {
                $('#dekomtinggirendah').val('');
                $('#dekomtinggirendah_raw').val('');
            }

            // 4. Rasio direksi tinggi/dekom tinggi
            if (dekomTinggi > 0 && dirTinggi > 0) {
                var ratioDirDekom = dirTinggi / dekomTinggi;
                $('#dirdekomtinggirendah').val(ratioDirDekom.toFixed(2));
                $('#dirdekomtinggirendah_raw').val(ratioDirDekom.toFixed(2));
                console.log('Ratio dir/dekom:', ratioDirDekom.toFixed(2));
            } else {
                $('#dirdekomtinggirendah').val('');
                $('#dirdekomtinggirendah_raw').val('');
            }

            // 5. Rasio direksi tinggi/pegawai tinggi
            if (pegawaiTinggi > 0 && dirTinggi > 0) {
                var ratioDirPegawai = dirTinggi / pegawaiTinggi;
                $('#dirpegawaitinggirendah').val(ratioDirPegawai.toFixed(2));
                $('#dirpegawaitinggirendah_raw').val(ratioDirPegawai.toFixed(2));
                console.log('Ratio dir/pegawai:', ratioDirPegawai.toFixed(2));
            } else {
                $('#dirpegawaitinggirendah').val('');
                $('#dirpegawaitinggirendah_raw').val('');
            }
        }

        // Event handlers untuk format currency dan trigger calculation
        function setupCurrencyInput(selector) {
            $(selector).on('input blur', function () {
                var $this = $(this);
                var rawSelector = $this.attr('id') + '_raw';
                var value = $this.val().replace(/\D/g, '');

                if (value && !isNaN(value) && value !== '0') {
                    // Format untuk display
                    $this.val(formatCurrency(value));
                    // Store raw value
                    $('#' + rawSelector).val(value);
                } else {
                    $this.val('');
                    $('#' + rawSelector).val('');
                }

                // Trigger recalculation
                calculateAllRatios();
            });

            // Handle paste event
            $(selector).on('paste', function () {
                var $this = $(this);
                setTimeout(function () {
                    var value = $this.val().replace(/\D/g, '');
                    if (value && !isNaN(value)) {
                        $this.val(formatCurrency(value));
                        $('#' + $this.attr('id') + '_raw').val(value);
                        calculateAllRatios();
                    }
                }, 10);
            });
        }

        // Setup currency inputs
        setupCurrencyInput('#pegawaitinggi');
        setupCurrencyInput('#pegawairendah');
        setupCurrencyInput('#dirtinggi');
        setupCurrencyInput('#dirrendah');
        setupCurrencyInput('#dekomtinggi');
        setupCurrencyInput('#dekomrendah');

        // Sync function for field synchronization
        function syncDirtinggiValues() {
            const dirtinggiFields = document.querySelectorAll('#dirtinggi');
            const dirtinggiRawFields = document.querySelectorAll('#dirtinggi_raw');
            const dekomtinggiFields = document.querySelectorAll('#dekomtinggi');
            const dekomtinggiRawFields = document.querySelectorAll('#dekomtinggi_raw');
            const pegawaitinggiFields = document.querySelectorAll('#pegawaitinggi');
            const pegawaitinggiRawFields = document.querySelectorAll('#pegawaitinggi_raw');

            // Sinkronisasi dirtinggi (nomor 2 ke nomor 4 dan 5)
            if (dirtinggiFields.length >= 3) {
                const sourceDirField = dirtinggiFields[0]; // nomor 2
                const sourceDirRawField = dirtinggiRawFields[0];
                const targetDirField1 = dirtinggiFields[1]; // nomor 4
                const targetDirRawField1 = dirtinggiRawFields[1];
                const targetDirField2 = dirtinggiFields[2]; // nomor 5
                const targetDirRawField2 = dirtinggiRawFields[2];

                sourceDirField.addEventListener('input', function () {
                    targetDirField1.value = this.value;
                    targetDirField2.value = this.value;
                    if (sourceDirRawField && targetDirRawField1) {
                        targetDirRawField1.value = sourceDirRawField.value;
                    }
                    if (sourceDirRawField && targetDirRawField2) {
                        targetDirRawField2.value = sourceDirRawField.value;
                    }
                    // Trigger recalculation after sync
                    calculateAllRatios();
                });

                // Sinkronkan nilai awal jika ada
                if (sourceDirField.value) {
                    targetDirField1.value = sourceDirField.value;
                    targetDirField2.value = sourceDirField.value;
                    if (sourceDirRawField && targetDirRawField1) {
                        targetDirRawField1.value = sourceDirRawField.value;
                    }
                    if (sourceDirRawField && targetDirRawField2) {
                        targetDirRawField2.value = sourceDirRawField.value;
                    }
                }
            }

            // Sinkronisasi dekomtinggi (nomor 3 ke nomor 4)
            if (dekomtinggiFields.length >= 2) {
                const sourceDekomField = dekomtinggiFields[0]; // nomor 3
                const sourceDekomRawField = dekomtinggiRawFields[0];
                const targetDekomField = dekomtinggiFields[1]; // nomor 4
                const targetDekomRawField = dekomtinggiRawFields[1];

                sourceDekomField.addEventListener('input', function () {
                    targetDekomField.value = this.value;
                    if (sourceDekomRawField && targetDekomRawField) {
                        targetDekomRawField.value = sourceDekomRawField.value;
                    }
                    // Trigger recalculation after sync
                    calculateAllRatios();
                });

                // Sinkronkan nilai awal jika ada
                if (sourceDekomField.value) {
                    targetDekomField.value = sourceDekomField.value;
                    if (sourceDekomRawField && targetDekomRawField) {
                        targetDekomRawField.value = sourceDekomRawField.value;
                    }
                }
            }

            // Sinkronisasi pegawaitinggi (nomor 1 ke nomor 5)
            if (pegawaitinggiFields.length >= 2) {
                const sourcePegawaiField = pegawaitinggiFields[0]; // nomor 1
                const sourcePegawaiRawField = pegawaitinggiRawFields[0];
                const targetPegawaiField = pegawaitinggiFields[1]; // nomor 5
                const targetPegawaiRawField = pegawaitinggiRawFields[1];

                sourcePegawaiField.addEventListener('input', function () {
                    targetPegawaiField.value = this.value;
                    if (sourcePegawaiRawField && targetPegawaiRawField) {
                        targetPegawaiRawField.value = sourcePegawaiRawField.value;
                    }
                    // Trigger recalculation after sync
                    calculateAllRatios();
                });

                // Sinkronkan nilai awal jika ada
                if (sourcePegawaiField.value) {
                    targetPegawaiField.value = sourcePegawaiField.value;
                    if (sourcePegawaiRawField && targetPegawaiRawField) {
                        targetPegawaiRawField.value = sourcePegawaiRawField.value;
                    }
                }
            }
        }

        // Initialize synchronization
        syncDirtinggiValues();

        // Initial calculation when page loads
        calculateAllRatios();

        // Form submission handler
        $('#paketKebijakanForm').submit(function (e) {
            e.preventDefault();

            // Ensure all calculations are up to date
            calculateAllRatios();

            // Debug: Log all values before submission
            console.log('Form submission - Current values:');
            console.log('pegawaitinggirendah:', $('#pegawaitinggirendah').val());
            console.log('pegawaitinggirendah_raw:', $('#pegawaitinggirendah_raw').val());
            console.log('direksitinggirendah:', $('#direksitinggirendah').val());
            console.log('direksitinggirendah_raw:', $('#direksitinggirendah_raw').val());
            console.log('dekomtinggirendah:', $('#dekomtinggirendah').val());
            console.log('dekomtinggirendah_raw:', $('#dekomtinggirendah_raw').val());

            var formData = $(this).serialize();
            console.log('Serialized form data:', formData);

            $.ajax({
                url: '/Rasiogaji/tambahpenjelasAjax',
                type: 'POST',
                data: formData,
                dataType: 'json',
                beforeSend: function () {
                    $('button[type="submit"]').prop('disabled', true).text('Menyimpan...');
                },
                success: function (response) {
                    console.log('Response:', response);

                    if (response.status === 'success') {
                        $('#ajax-response-message').html(
                            '<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                            '<i class="fa fa-check-circle"></i> ' + response.message +
                            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                            '</div>'
                        );
                    } else {
                        $('#ajax-response-message').html(
                            '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                            '<i class="fa fa-exclamation-triangle"></i> ' + response.message +
                            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                            '</div>'
                        );
                    }

                    $('html, body').animate({
                        scrollTop: $("#ajax-response-message").offset().top - 100
                    }, 500);

                    setTimeout(function () {
                        $('#ajax-response-message .alert').fadeOut();
                    }, 5000);
                },
                error: function (xhr, status, error) {
                    console.log('Error:', xhr.responseText);

                    var errorMessage = 'Terjadi kesalahan saat menyimpan data.';

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.status === 404) {
                        errorMessage = 'URL endpoint tidak ditemukan. Periksa route di controller.';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Error server internal. Periksa log server.';
                    } else if (xhr.status === 403) {
                        errorMessage = 'Akses ditolak. Pastikan Anda memiliki permission yang tepat.';
                    }

                    $('#ajax-response-message').html(
                        '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                        '<i class="fa fa-exclamation-triangle"></i> ' + errorMessage +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                        '</div>'
                    );
                },
                complete: function () {
                    $('button[type="submit"]').prop('disabled', false).text('Simpan Data');
                }
            });
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        // Selector yang konsisten
        const commentButtons = document.querySelectorAll('[id^="btn-komentar-"]');

        console.log('Found comment buttons:', commentButtons.length); // Debug

        function updateBadge(Id, newCount) {
            const badge = document.getElementById('notification-badge-' + Id);
            // console.log('Updating badge for factor', Id, 'with count', newCount); // Debug
            if (badge) {
                if (newCount > 0) {
                    badge.textContent = newCount;
                    badge.style.display = 'inline-flex';
                } else {
                    badge.style.display = 'none';
                    badge.textContent = '0';
                }
            }
        }

        function fetchAndDisplayComments(Id, kodebpr, periodeId) {
            const modal = $('#modalTambahkomentar');
            modal.find('#komentarLamaList').html('<li>Memuat komentar...</li>');

            $.ajax({
                url: '<?= base_url('rasiogaji/getKomentarByFaktorId'); ?>/' + Id,
                method: 'GET',
                data: {
                    kodebpr: kodebpr,
                    periode_id: periodeId
                },
                dataType: 'json',
                success: function (response) {
                    let komentarListHtml = '';
                    if (response.length > 0) {
                        response.forEach(function (komentar) {
                            komentarListHtml += '<li>' + komentar.komentar +
                                ' - (' + komentar.fullname +
                                ' - ' + komentar.created_at + ')</li>';
                        });
                    } else {
                        komentarListHtml = '<li>Tidak ada komentar.</li>';
                    }
                    modal.find('#komentarLamaList').html(komentarListHtml);
                },
                error: function (xhr, status, error) {
                    console.error('Error fetching comments for modal:', error);
                    console.log('Response:', xhr.responseText);
                    modal.find('#komentarLamaList').html('<li>Gagal memuat komentar.</li>');
                }
            });
        }

        // Event listener untuk button komentar
        commentButtons.forEach(button => {
            button.addEventListener('click', function () {
                const Id = this.getAttribute('data-id');
                const kodebpr = this.getAttribute('data-kodebpr');
                const userId = this.getAttribute('data-user-id');
                const periodeId = this.getAttribute('data-periode-id');

                // console.log('Button clicked - Id:', Id, 'userId:', userId); // Debug

                $('#modalTambahkomentar').find('#id').val(Id);

                // Fetch dan display comments
                fetchAndDisplayComments(Id, kodebpr, periodeId);

                // Mark comments as read
                $.ajax({
                    url: '<?= base_url('Rasiogaji/markUserCommentsAsRead'); ?>',
                    method: 'POST',
                    data: {
                        id: Id,
                        kodebpr: kodebpr,
                        user_id: userId,
                        periode_id: periodeId,
                        '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
                    },
                    success: function (response) {
                        if (response.status === 'success') {
                            updateBadge(Id, 0);
                            console.log('Comments marked as read for factor', Id); // Debug
                        } else {
                            console.error('Failed to mark comments as read:', response.message);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('Error marking comments as read:', error);
                    }
                });
            });
        });

        // Form submit handler
        $('#formTambahKomentar').on('submit', function (e) {
            e.preventDefault();
            const form = $(this);
            const formData = form.serialize();
            const Id = form.find('#id').val(); // ID yang konsisten

            console.log('Submitting comment for factor:', Id); // Debug

            $.ajax({
                url: form.attr('action'),
                method: form.attr('method'),
                data: formData + '&<?= csrf_token() ?>=' + '<?= csrf_hash() ?>',
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        alert(response.message);
                        form.find('#komentar').val('');
                        fetchAndDisplayComments(Id, GLOBAL_KODEBPR, GLOBAL_ACTIVE_PERIODE_ID);

                        // Refresh badge untuk user lain
                        refreshBadgeForAllUsers(GLOBAL_CURRENT_USER_ID);
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function (xhr, status, error) {
                    console.error("AJAX Error:", status, error);
                    alert('Terjadi kesalahan saat menyimpan komentar.');
                }
            });
        });

        // Polling function
        function pollUnreadCounts() {
            console.log('Polling unread counts for', commentButtons.length, 'buttons');

            commentButtons.forEach(button => {
                const Id = button.getAttribute('data-id');
                const kodebpr = button.getAttribute('data-kodebpr');
                const userId = button.getAttribute('data-user-id');
                const periodeId = button.getAttribute('data-periode-id');

                console.log('Polling for ID:', Id, 'User:', userId);

                $.ajax({
                    url: '<?= base_url('Rasiogaji/getUnreadCommentCountForFactor'); ?>',
                    method: 'GET',
                    data: {
                        id: Id,
                        kodebpr: kodebpr,
                        user_id: userId,
                        periode_id: periodeId
                    },
                    success: function (response) {
                        console.log('Unread count response for ID', Id, ':', response);
                        if (response && typeof response.unread_count !== 'undefined') {
                            updateBadge(Id, response.unread_count);
                        } else {
                            console.warn('Invalid response format:', response);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('Error fetching unread count for factor ' + Id + ':', error);
                        console.error('Response text:', xhr.responseText);
                    }
                });
            });
        }

        // Fungsi untuk refresh badge setelah ada komentar baru
        function refreshBadgeForAllUsers(exceptUserId) {
            console.log('Refreshing badges for all users except:', exceptUserId);

            commentButtons.forEach(button => {
                const Id = button.getAttribute('data-id');
                const kodebpr = button.getAttribute('data-kodebpr');
                const periodeId = button.getAttribute('data-periode-id');

                $.ajax({
                    url: '<?= base_url('Rasiogaji/getUnreadCommentCountForAllUsers'); ?>',
                    method: 'GET',
                    data: {
                        id: Id,
                        kodebpr: kodebpr,
                        periode_id: periodeId,
                        except_user_id: exceptUserId
                    },
                    success: function (response) {
                        console.log('Badge refresh response:', response);
                    },
                    error: function (xhr, status, error) {
                        console.error('Error refreshing badges:', error);
                    }
                });
            });
        }

        // Call polling function initially
        pollUnreadCounts();

        // setInterval(pollUnreadCounts, 10000);
    });
</script>

<style>
    .cardpilihfaktor {
        width: auto;
        max-width: 700px;
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
        font-size: 1.1rem;
        font-weight: 600;
        transition: background-color 0.3s ease, border-color 0.3s ease, transform 0.3s ease;
    }

    .btn-secondary:hover {
        background-color: #ffffff;
        border-color: #343a40;
        transform: translateY(-2px);
        color: #343a40;
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
    }

    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border-color: #f5c6cb;
        border-radius: 10px;
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

    .disabled-card {
        opacity: 0.5;
        /* Membuat card terlihat samar */
        pointer-events: none;
        /* Menonaktifkan interaksi dengan elemen */
    }

    .disabled-btn {
        opacity: 0.5;
        /* Membuat tombol terlihat samar */
        pointer-events: none;
        /* Menonaktifkan klik pada tombol */
    }
</style>