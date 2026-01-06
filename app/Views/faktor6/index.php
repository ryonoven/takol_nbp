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
<div class="container-fluid">
    <?php if (session()->get('message')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <strong><?= session()->getFlashdata('message'); ?></strong>
        </div>
    <?php endif; ?>
    <!-- Faktor 4 -->
    <div class="card card-body">
        <div class="table-vertical">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <span class="label" style="flex: 3; margin-right: 10px; text-align: center;">
                    <h3>Faktor 6</h3>
                    <h4>Penerapan Fungsi Kepatuhan</h4>
                </span>
                <?php
                // pastikan variabel ada dan tidak null
                $kodebpr = $kodebpr ?? null;
                $periodeId = $periodeId ?? null;
                ?>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <!-- Approval Komisaris Utama (di kiri) -->
                <span style="flex: 1; text-align: left;">
                    <?php
                    $allApproved = true;
                    $requiredFaktor6Ids = range(1, 10);

                    foreach ($requiredFaktor6Ids as $faktor6Id) {
                        $item = array_filter(
                            $faktors6,
                            fn($f) =>
                            $f['id'] == $faktor6Id
                            && $f['kodebpr'] == $kodebpr
                            && $f['periode_id'] == $periodeId
                        );

                        if (empty($item)) {
                            $allApproved = false;
                            break;
                        }

                        $item = array_values($item)[0];

                        if (!isset($item['accdekom']) || $item['accdekom'] != 1) {
                            $allApproved = false;
                            break;
                        }
                    }
                    ?>

                    <?php if ($allApproved): ?>
                        <span class="badge badge-success" style="font-size: 14px;">
                            Disetujui oleh <strong>Komisaris Utama</strong><br>
                            <?= esc($item['accdekom_at'] ?? '-') ?>
                        </span>
                    <?php else: ?>
                        <span class="badge badge-secondary" style="font-size: 14px;">
                            Belum Disetujui Seluruhnya<br>Oleh Komisaris Utama
                        </span>
                    <?php endif; ?>
                </span>

                <!-- Approval Direktur Utama (di kanan) -->
                <span style="flex: 1; text-align: right;">
                    <?php
                    $allApproved = true;
                    foreach ($requiredFaktor6Ids as $faktor6Id) {
                        $item = array_filter(
                            $faktors6,
                            fn($f) =>
                            $f['id'] == $faktor6Id
                            && $f['kodebpr'] == $kodebpr
                            && $f['periode_id'] == $periodeId
                        );

                        if (empty($item)) {
                            $allApproved = false;
                            break;
                        }

                        $item = array_values($item)[0];

                        if (!isset($item['is_approved']) || $item['is_approved'] != 1) {
                            $allApproved = false;
                            break;
                        }
                    }
                    ?>

                    <?php if ($allApproved): ?>
                        <span class="badge badge-success" style="font-size: 14px;">
                            Disetujui oleh <strong>Direktur Utama</strong><br>
                            <?= esc($item['approved_at'] ?? '-') ?>
                        </span>
                    <?php else: ?>
                        <span class="badge badge-secondary" style="font-size: 14px;">
                            Belum Disetujui Seluruhnya<br>Oleh Direktur Utama
                        </span>
                    <?php endif; ?>
                </span>
            </div>

            <!-- Approval Direktur Utama -->
            <div class="row" style="display: flex; justify-content: space-between;">
                <!-- Card untuk Approval Komisaris Utama -->
                <?php if ($userInGroupAdmin || $userInGroupDekom): ?>
                    <div class="col-md-3">
                        <div class="card shadow-sm" style="width: 100%; margin-top: 10px; height: 120px;">
                            <div class="card-body">
                                <!-- Label Approval Direktur Utama -->
                                <div class="col-md" style="text-align: center;">
                                    <span class="badge badge-primary">Approval Komisaris Utama</span>
                                </div>

                                <!-- Tombol Approve dan Batalkan Approval -->
                                <div class="col-md" style="display: flex; justify-content: center; align-items: center;">
                                    <a href="<?= base_url('faktor6/approveSemuaKom') ?>"
                                        class="btn btn-success shadow mt-3 mr-2"
                                        onclick="return confirm('Apakah Anda yakin ingin melakukan approval?');">
                                        Approve
                                    </a>
                                    <a href="<?= base_url('faktor6/unapprovekom') ?>"
                                        class="btn btn-danger shadow mt-3 mr-2"
                                        onclick="return confirm('Batalkan semua approval?');">
                                        Tolak Approval
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Card untuk Approval Direktur Utama -->
                <?php if ($userInGroupAdmin || $userInGroupDireksi): ?>
                    <div class="col-md-3" style="margin-left: auto;">
                        <div class="card shadow-sm <?php echo (isset($canApprove) && !$canApprove) ? 'disabled-card' : ''; ?>"
                            style="width: 100%; margin-top: 10px; height: 120px;">
                            <div class="card-body">
                                <!-- Label Approval Direktur Utama -->
                                <div class="col-md" style="text-align: center;">
                                    <span class="badge badge-primary">Approval Direktur Utama</span>
                                </div>

                                <!-- Tombol Approve dan Batalkan Approval -->
                                <div class="col-md" style="display: flex; justify-content: center; align-items: center;">
                                    <a href="<?= base_url('faktor6/approveSemua') ?>"
                                        class="btn btn-success shadow mt-3 mr-2 <?php echo (isset($canApprove) && !$canApprove) ? 'disabled-btn' : ''; ?>"
                                        onclick="return confirm('Apakah Anda yakin ingin melakukan approval?');">
                                        Approve
                                    </a>
                                    <a href="<?= base_url('faktor6/unapproveSemua') ?>"
                                        class="btn btn-danger shadow mt-3 mr-2 <?php echo (isset($canApprove) && !$canApprove) ? 'disabled-btn' : ''; ?>"
                                        onclick="return confirm('Batalkan semua approval?');">
                                        Tolak Approval
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

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
                        <?php if (empty($faktors6)) { ?>
                            <tr>
                                <td scope="row"></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($faktors6 as $row): ?>
                                <?php if ($row['sph'] == 'Struktur'): ?>
                                    <tr>
                                        <td scope="row"><?= $row['id']; ?></td>
                                        <td><?= $row['sub_category'] ?></td>

                                        <?php
                                        // Cari data spesifik user berdasarkan kodebpr dan periode
                                        $userSpecificData = array_filter($faktors6, function ($item) use ($row, $kodebpr, $periodeId) {
                                            return $item['id'] == $row['id'] &&
                                                $item['kodebpr'] == $kodebpr &&
                                                $item['periode_id'] == $periodeId;
                                        });

                                        // Jika ada data spesifik user, gunakan datanya
                                        if (!empty($userSpecificData)) {
                                            $userData = reset($userSpecificData);
                                            $nilai = $userData['nilai'] ?? '';
                                            $keterangan = $userData['keterangan'] ?? '';
                                            // $accdekomValue = $userData['accdekom'] ?? '';
                                        } else {
                                            $nilai = '';
                                            $keterangan = '';
                                        }
                                        ?>

                                        <td><?= $nilai ?></td>
                                        <td><?= $keterangan ?></td>
                                        <td>
                                            <?php if ($userInGroupAdmin || $userInGroupPE): ?>
                                                <?php if (empty($nilai) && empty($keterangan)): ?>
                                                    <button type="button" data-toggle="modal" data-target="#modaltambahNilai"
                                                        id="btn-tambah" class="btn btn-sm" style="font-weight: 600;"
                                                        data-id="<?= $row['id']; ?>" data-sub_category="<?= $row['sub_category']; ?>">
                                                        <i class="fas fa-plus"></i>&nbsp;
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" data-toggle="modal" data-target="#modalUbah" id="btn-edit"
                                                        class="btn btn-sm" style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                                        data-sph="<?= $row['sph']; ?>" data-sub_category="<?= $row['sub_category']; ?>"
                                                        data-nilai="<?= $nilai; ?>" data-keterangan="<?= $keterangan; ?>">
                                                        <i class="fa fa-edit"></i>&nbsp;
                                                    </button>
                                                    <button type="button" data-toggle="modal" data-target="#modalHapusnilai6" id="btn-hapus"
                                                        class="btn" style="font-weight: 600;" data-id="<?= $row['id']; ?>">
                                                        <i class="fas fa-trash-alt"></i>&nbsp;
                                                    </button>
                                                <?php endif; ?>
                                                <!-- Add checkbox for approval -->
                                            <?php endif; ?>
                                        </td>
                                        <!-- Button untuk Approval -->
                                        <td>
                                            <?php
                                            $currentUserId = session()->get('user_id');
                                            $activePeriodeId = session()->get('active_periode');

                                            // Get the initial count of unread comments for this specific factor and user
                                            // Make sure $commentReadsModel is passed from the controller
                                            $initialUnreadCount = $commentReads6Model->countUnreadCommentsForUserByFactor(
                                                $row['id'],
                                                $kodebpr, // The BPR code for the current user
                                                $currentUserId,
                                                $activePeriodeId
                                            );
                                            ?>
                                            <div class="komentar-btn-wrapper">
                                                <button type="button" data-toggle="modal" data-target="#modaltambahKomentar"
                                                    id="btn-komentar-<?= $row['id']; ?>"
                                                    class="btn btn-sm position-relative komentar-button" style="font-weight: 600;"
                                                    data-faktor-id="<?= $row['id']; ?>" data-kodebpr="<?= $kodebpr; ?>"
                                                    data-user-id="<?= $currentUserId; ?>"
                                                    data-periode-id="<?= $activePeriodeId; ?>">
                                                    <i class="fas fa-comment"></i>
                                                    <span id="notification-badge-<?= $row['id']; ?>"
                                                        class="badge badge-danger notification-badge"
                                                        style="display: <?= $initialUnreadCount > 0 ? 'inline-flex' : 'none'; ?>;">
                                                        <?= $initialUnreadCount ?>
                                                    </span>
                                                </button>
                                            </div>
                                        </td>
                                        <!-- View (HTML/PHP) -->
                                        <?php if ($userInGroupAdmin || $userInGroupDekom): ?>
                                            <?php
                                            // Mendapatkan nilai user_id dan active_periode dari session
                                            $currentUserId = session()->get('user_id');
                                            $activePeriodeId = session()->get('active_periode');
                                            if ($row['kodebpr'] == $kodebpr && $row['periode_id'] == $activePeriodeId) {
                                                $accdekomValue = $row['accdekom'] ?? null;

                                                // Jika accdekom == 1 dan filter sesuai dengan kodebpr dan periode_id
                                                if ($accdekomValue == 1) {
                                                    // Jika accdekom == 1, tampilkan tombol unapprove
                                                    ?>
                                                    <td>
                                                        <form action="/faktor6/unapprovedekom/<?= $row['id']; ?>" method="POST"
                                                            style="display: inline-block;">
                                                            <input type="hidden" name="kodebpr" value="<?= $kodebpr ?>" />
                                                            <input type="hidden" name="periode_id" value="<?= $periodeId ?>" />
                                                            <input type="hidden" name="faktor6id" value="<?= $row['id']; ?>" />
                                                            <button type="submit" class="btn btn-sm btn-success" style="font-weight: 600;">
                                                                <i class="fas fa-check-circle"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                    <?php
                                                } else {
                                                    // Jika accdekom != 1, tampilkan tombol approve
                                                    ?>
                                                    <td>
                                                        <form action="/faktor6/accdekom/<?= $row['id']; ?>" method="POST"
                                                            style="display: inline-block;">
                                                            <input type="hidden" name="kodebpr" value="<?= $kodebpr ?>" />
                                                            <input type="hidden" name="periode_id" value="<?= $periodeId ?>" />
                                                            <input type="hidden" name="faktor6id" value="<?= $row['id']; ?>" />
                                                            <button type="submit" class="btn btn-sm btn-dark" style="font-weight: 600;">
                                                                <i class="fas fa-times-circle"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        <?php endif; ?>
                                        <?php if ($userInGroupAdmin || $userInGroupDekom2): ?>
                                            <?php
                                            // Mendapatkan nilai user_id dan active_periode dari session
                                            $currentUserId = session()->get('user_id');
                                            $activePeriodeId = session()->get('active_periode');
                                            if ($row['kodebpr'] == $kodebpr && $row['periode_id'] == $activePeriodeId) {
                                                $accdekom2Value = $row['accdekom2'] ?? null;

                                                // Jika accdekom == 1 dan filter sesuai dengan kodebpr dan periode_id
                                                if ($accdekom2Value == 1) {
                                                    // Jika accdekom == 1, tampilkan tombol unapprove
                                                    ?>
                                                    <td>
                                                        <form action="/faktor6/unapprovedekom2/<?= $row['id']; ?>" method="POST"
                                                            style="display: inline-block;">
                                                            <input type="hidden" name="kodebpr" value="<?= $kodebpr ?>" />
                                                            <input type="hidden" name="periode_id" value="<?= $periodeId ?>" />
                                                            <input type="hidden" name="faktor6id" value="<?= $row['id']; ?>" />
                                                            <button type="submit" class="btn btn-sm btn-success" style="font-weight: 600;">
                                                                <i class="fas fa-check-circle"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                    <?php
                                                } else {
                                                    // Jika accdekom != 1, tampilkan tombol approve
                                                    ?>
                                                    <td>
                                                        <form action="/faktor6/accdekom2/<?= $row['id']; ?>" method="POST"
                                                            style="display: inline-block;">
                                                            <input type="hidden" name="kodebpr" value="<?= $kodebpr ?>" />
                                                            <input type="hidden" name="periode_id" value="<?= $periodeId ?>" />
                                                            <input type="hidden" name="faktor6id" value="<?= $row['id']; ?>" />
                                                            <button type="submit" class="btn btn-sm btn-dark" style="font-weight: 600;">
                                                                <i class="fas fa-times-circle"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        <?php endif; ?>
                                        <?php if ($userInGroupAdmin || $userInGroupDireksi2 || $userInGroupDireksi): ?>
                                            <?php
                                            // Mendapatkan nilai user_id dan active_periode dari session
                                            $currentUserId = session()->get('user_id');
                                            $activePeriodeId = session()->get('active_periode');
                                            if ($row['kodebpr'] == $kodebpr && $row['periode_id'] == $activePeriodeId) {
                                                $accdir2Value = $row['accdir2'] ?? null;

                                                // Jika accdekom == 1 dan filter sesuai dengan kodebpr dan periode_id
                                                if ($accdir2Value == 1) {
                                                    // Jika accdekom == 1, tampilkan tombol unapprove
                                                    ?>
                                                    <td>
                                                        <form action="/faktor6/unapprovedir2/<?= $row['id']; ?>" method="POST"
                                                            style="display: inline-block;">
                                                            <input type="hidden" name="kodebpr" value="<?= $kodebpr ?>" />
                                                            <input type="hidden" name="periode_id" value="<?= $periodeId ?>" />
                                                            <input type="hidden" name="faktor6id" value="<?= $row['id']; ?>" />
                                                            <button type="submit" class="btn btn-sm btn-success" style="font-weight: 600;">
                                                                <i class="fas fa-check-circle"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                    <?php
                                                } else {
                                                    // Jika accdekom != 1, tampilkan tombol approve
                                                    ?>
                                                    <td>
                                                        <form action="/faktor6/accdir2/<?= $row['id']; ?>" method="POST"
                                                            style="display: inline-block;">
                                                            <input type="hidden" name="kodebpr" value="<?= $kodebpr ?>" />
                                                            <input type="hidden" name="periode_id" value="<?= $periodeId ?>" />
                                                            <input type="hidden" name="faktor6id" value="<?= $row['id']; ?>" />
                                                            <button type="submit" class="btn btn-sm btn-dark" style="font-weight: 600;">
                                                                <i class="fas fa-times-circle"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        <?php endif; ?>
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
                            <td></td>
                            <td></td>
                        </tr>
                    </thead>
                    <!-- Table body -->
                    <tbody>
                        <?php if (empty($faktors6)) { ?>
                            <tr>
                                <td scope="row"></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($faktors6 as $row): ?>
                                <?php if ($row['sph'] == 'Proses'): ?>
                                    <tr>
                                        <td scope="row"><?= $row['id']; ?></td>
                                        <td><?= $row['sub_category'] ?></td>

                                        <?php
                                        // Cari data spesifik user berdasarkan kodebpr dan periode
                                        $userSpecificData = array_filter($faktors6, function ($item) use ($row, $kodebpr, $periodeId) {
                                            return $item['id'] == $row['id'] &&
                                                $item['kodebpr'] == $kodebpr &&
                                                $item['periode_id'] == $periodeId;
                                        });

                                        // Jika ada data spesifik user, gunakan datanya
                                        if (!empty($userSpecificData)) {
                                            $userData = reset($userSpecificData);
                                            $nilai = $userData['nilai'] ?? '';
                                            $keterangan = $userData['keterangan'] ?? '';
                                        } else {
                                            $nilai = '';
                                            $keterangan = '';
                                        }
                                        ?>

                                        <td><?= $nilai ?></td>
                                        <td><?= $keterangan ?></td>
                                        <td>
                                            <?php if ($userInGroupAdmin || $userInGroupPE): ?>
                                                <?php if (empty($nilai) && empty($keterangan)): ?>
                                                    <button type="button" data-toggle="modal" data-target="#modaltambahNilai"
                                                        id="btn-tambah" class="btn btn-sm" style="font-weight: 600;"
                                                        data-id="<?= $row['id']; ?>" data-sub_category="<?= $row['sub_category']; ?>">
                                                        <i class="fas fa-plus"></i>&nbsp;
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" data-toggle="modal" data-target="#modalUbah" id="btn-edit"
                                                        class="btn btn-sm" style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                                        data-sph="<?= $row['sph']; ?>" data-sub_category="<?= $row['sub_category']; ?>"
                                                        data-nilai="<?= $nilai; ?>" data-keterangan="<?= $keterangan; ?>">
                                                        <i class="fa fa-edit"></i>&nbsp;
                                                    </button>
                                                    <button type="button" data-toggle="modal" data-target="#modalHapusnilai6" id="btn-hapus"
                                                        class="btn" style="font-weight: 600;" data-id="<?= $row['id']; ?>">
                                                        <i class="fas fa-trash-alt"></i>&nbsp;
                                                    </button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $currentUserId = session()->get('user_id');
                                            $activePeriodeId = session()->get('active_periode');

                                            // Get the initial count of unread comments for this specific factor and user
                                            // Make sure $commentReadsModel is passed from the controller
                                            $initialUnreadCount = $commentReads6Model->countUnreadCommentsForUserByFactor(
                                                $row['id'],
                                                $kodebpr, // The BPR code for the current user
                                                $currentUserId,
                                                $activePeriodeId
                                            );
                                            ?>
                                            <div class="komentar-btn-wrapper">
                                                <button type="button" data-toggle="modal" data-target="#modaltambahKomentar"
                                                    id="btn-komentar-<?= $row['id']; ?>"
                                                    class="btn btn-sm position-relative komentar-button" style="font-weight: 600;"
                                                    data-faktor-id="<?= $row['id']; ?>" data-kodebpr="<?= $kodebpr; ?>"
                                                    data-user-id="<?= $currentUserId; ?>"
                                                    data-periode-id="<?= $activePeriodeId; ?>">
                                                    <i class="fas fa-comment"></i>
                                                    <span id="notification-badge-<?= $row['id']; ?>"
                                                        class="badge badge-danger notification-badge"
                                                        style="display: <?= $initialUnreadCount > 0 ? 'inline-flex' : 'none'; ?>;">
                                                        <?= $initialUnreadCount ?>
                                                    </span>
                                                </button>
                                            </div>
                                        </td>
                                        <?php if ($userInGroupAdmin || $userInGroupDekom): ?>
                                            <?php
                                            // Mendapatkan nilai user_id dan active_periode dari session
                                            $currentUserId = session()->get('user_id');
                                            $activePeriodeId = session()->get('active_periode');
                                            if ($row['kodebpr'] == $kodebpr && $row['periode_id'] == $activePeriodeId) {
                                                $accdekomValue = $row['accdekom'] ?? null;

                                                // Jika accdekom == 1 dan filter sesuai dengan kodebpr dan periode_id
                                                if ($accdekomValue == 1) {
                                                    // Jika accdekom == 1, tampilkan tombol unapprove
                                                    ?>
                                                    <td>
                                                        <form action="/faktor6/unapprovedekom/<?= $row['id']; ?>" method="POST"
                                                            style="display: inline-block;">
                                                            <input type="hidden" name="kodebpr" value="<?= $kodebpr ?>" />
                                                            <input type="hidden" name="periode_id" value="<?= $periodeId ?>" />
                                                            <input type="hidden" name="faktor6id" value="<?= $row['id']; ?>" />
                                                            <button type="submit" class="btn btn-sm btn-success" style="font-weight: 600;">
                                                                <i class="fas fa-check-circle"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                    <?php
                                                } else {
                                                    // Jika accdekom != 1, tampilkan tombol approve
                                                    ?>
                                                    <td>
                                                        <form action="/faktor6/accdekom/<?= $row['id']; ?>" method="POST"
                                                            style="display: inline-block;">
                                                            <input type="hidden" name="kodebpr" value="<?= $kodebpr ?>" />
                                                            <input type="hidden" name="periode_id" value="<?= $periodeId ?>" />
                                                            <input type="hidden" name="faktor6id" value="<?= $row['id']; ?>" />
                                                            <button type="submit" class="btn btn-sm btn-dark" style="font-weight: 600;">
                                                                <i class="fas fa-times-circle"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        <?php endif; ?>
                                        <?php if ($userInGroupAdmin || $userInGroupDekom2): ?>
                                            <?php
                                            // Mendapatkan nilai user_id dan active_periode dari session
                                            $currentUserId = session()->get('user_id');
                                            $activePeriodeId = session()->get('active_periode');
                                            if ($row['kodebpr'] == $kodebpr && $row['periode_id'] == $activePeriodeId) {
                                                $accdekom2Value = $row['accdekom2'] ?? null;

                                                // Jika accdekom == 1 dan filter sesuai dengan kodebpr dan periode_id
                                                if ($accdekom2Value == 1) {
                                                    // Jika accdekom == 1, tampilkan tombol unapprove
                                                    ?>
                                                    <td>
                                                        <form action="/faktor6/unapprovedekom2/<?= $row['id']; ?>" method="POST"
                                                            style="display: inline-block;">
                                                            <input type="hidden" name="kodebpr" value="<?= $kodebpr ?>" />
                                                            <input type="hidden" name="periode_id" value="<?= $periodeId ?>" />
                                                            <input type="hidden" name="faktor6id" value="<?= $row['id']; ?>" />
                                                            <button type="submit" class="btn btn-sm btn-success" style="font-weight: 600;">
                                                                <i class="fas fa-check-circle"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                    <?php
                                                } else {
                                                    // Jika accdekom != 1, tampilkan tombol approve
                                                    ?>
                                                    <td>
                                                        <form action="/faktor6/accdekom2/<?= $row['id']; ?>" method="POST"
                                                            style="display: inline-block;">
                                                            <input type="hidden" name="kodebpr" value="<?= $kodebpr ?>" />
                                                            <input type="hidden" name="periode_id" value="<?= $periodeId ?>" />
                                                            <input type="hidden" name="faktor6id" value="<?= $row['id']; ?>" />
                                                            <button type="submit" class="btn btn-sm btn-dark" style="font-weight: 600;">
                                                                <i class="fas fa-times-circle"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        <?php endif; ?>
                                        <?php if ($userInGroupAdmin || $userInGroupDireksi2 || $userInGroupDireksi): ?>
                                            <?php
                                            // Mendapatkan nilai user_id dan active_periode dari session
                                            $currentUserId = session()->get('user_id');
                                            $activePeriodeId = session()->get('active_periode');
                                            if ($row['kodebpr'] == $kodebpr && $row['periode_id'] == $activePeriodeId) {
                                                $accdir2Value = $row['accdir2'] ?? null;

                                                // Jika accdekom == 1 dan filter sesuai dengan kodebpr dan periode_id
                                                if ($accdir2Value == 1) {
                                                    // Jika accdekom == 1, tampilkan tombol unapprove
                                                    ?>
                                                    <td>
                                                        <form action="/faktor6/unapprovedir2/<?= $row['id']; ?>" method="POST"
                                                            style="display: inline-block;">
                                                            <input type="hidden" name="kodebpr" value="<?= $kodebpr ?>" />
                                                            <input type="hidden" name="periode_id" value="<?= $periodeId ?>" />
                                                            <input type="hidden" name="faktor6id" value="<?= $row['id']; ?>" />
                                                            <button type="submit" class="btn btn-sm btn-success" style="font-weight: 600;">
                                                                <i class="fas fa-check-circle"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                    <?php
                                                } else {
                                                    // Jika accdekom != 1, tampilkan tombol approve
                                                    ?>
                                                    <td>
                                                        <form action="/faktor6/accdir2/<?= $row['id']; ?>" method="POST"
                                                            style="display: inline-block;">
                                                            <input type="hidden" name="kodebpr" value="<?= $kodebpr ?>" />
                                                            <input type="hidden" name="periode_id" value="<?= $periodeId ?>" />
                                                            <input type="hidden" name="faktor6id" value="<?= $row['id']; ?>" />
                                                            <button type="submit" class="btn btn-sm btn-dark" style="font-weight: 600;">
                                                                <i class="fas fa-times-circle"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        <?php endif; ?>
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
                        <?php if (empty($faktors6)) { ?>
                            <tr>
                                <td scope="row"></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($faktors6 as $row): ?>
                                <?php if ($row['sph'] == 'Hasil'): ?>
                                    <tr>
                                        <td scope="row"><?= $row['id']; ?></td>
                                        <td><?= $row['sub_category'] ?></td>

                                        <?php
                                        // Cari data spesifik user berdasarkan kodebpr dan periode
                                        $userSpecificData = array_filter($faktors6, function ($item) use ($row, $kodebpr, $periodeId) {
                                            return $item['id'] == $row['id'] &&
                                                $item['kodebpr'] == $kodebpr &&
                                                $item['periode_id'] == $periodeId;
                                        });

                                        // Jika ada data spesifik user, gunakan datanya
                                        if (!empty($userSpecificData)) {
                                            $userData = reset($userSpecificData);
                                            $nilai = $userData['nilai'] ?? '';
                                            $keterangan = $userData['keterangan'] ?? '';
                                        } else {
                                            $nilai = '';
                                            $keterangan = '';
                                        }
                                        ?>

                                        <td><?= $nilai ?></td>
                                        <td><?= $keterangan ?></td>
                                        <td>
                                            <?php if ($userInGroupAdmin || $userInGroupPE): ?>
                                                <?php if (empty($nilai) && empty($keterangan)): ?>
                                                    <button type="button" data-toggle="modal" data-target="#modaltambahNilai"
                                                        id="btn-tambah" class="btn btn-sm" style="font-weight: 600;"
                                                        data-id="<?= $row['id']; ?>" data-sub_category="<?= $row['sub_category']; ?>">
                                                        <i class="fas fa-plus"></i>&nbsp;
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" data-toggle="modal" data-target="#modalUbah" id="btn-edit"
                                                        class="btn btn-sm" style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                                        data-sph="<?= $row['sph']; ?>" data-sub_category="<?= $row['sub_category']; ?>"
                                                        data-nilai="<?= $nilai; ?>" data-keterangan="<?= $keterangan; ?>">
                                                        <i class="fa fa-edit"></i>&nbsp;
                                                    </button>
                                                    <button type="button" data-toggle="modal" data-target="#modalHapusnilai6" id="btn-hapus"
                                                        class="btn" style="font-weight: 600;" data-id="<?= $row['id']; ?>">
                                                        <i class="fas fa-trash-alt"></i>&nbsp;
                                                    </button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $currentUserId = session()->get('user_id');
                                            $activePeriodeId = session()->get('active_periode');

                                            // Get the initial count of unread comments for this specific factor and user
                                            // Make sure $commentReadsModel is passed from the controller
                                            $initialUnreadCount = $commentReads6Model->countUnreadCommentsForUserByFactor(
                                                $row['id'],
                                                $kodebpr, // The BPR code for the current user
                                                $currentUserId,
                                                $activePeriodeId
                                            );
                                            ?>
                                            <div class="komentar-btn-wrapper">
                                                <button type="button" data-toggle="modal" data-target="#modaltambahKomentar"
                                                    id="btn-komentar-<?= $row['id']; ?>"
                                                    class="btn btn-sm position-relative komentar-button" style="font-weight: 600;"
                                                    data-faktor-id="<?= $row['id']; ?>" data-kodebpr="<?= $kodebpr; ?>"
                                                    data-user-id="<?= $currentUserId; ?>"
                                                    data-periode-id="<?= $activePeriodeId; ?>">
                                                    <i class="fas fa-comment"></i>
                                                    <span id="notification-badge-<?= $row['id']; ?>"
                                                        class="badge badge-danger notification-badge"
                                                        style="display: <?= $initialUnreadCount > 0 ? 'inline-flex' : 'none'; ?>;">
                                                        <?= $initialUnreadCount ?>
                                                    </span>
                                                </button>
                                            </div>
                                        </td>
                                        <?php if ($userInGroupAdmin || $userInGroupDekom): ?>
                                            <?php
                                            // Mendapatkan nilai user_id dan active_periode dari session
                                            $currentUserId = session()->get('user_id');
                                            $activePeriodeId = session()->get('active_periode');
                                            if ($row['kodebpr'] == $kodebpr && $row['periode_id'] == $activePeriodeId) {
                                                $accdekomValue = $row['accdekom'] ?? null;

                                                // Jika accdekom == 1 dan filter sesuai dengan kodebpr dan periode_id
                                                if ($accdekomValue == 1) {
                                                    // Jika accdekom == 1, tampilkan tombol unapprove
                                                    ?>
                                                    <td>
                                                        <form action="/faktor6/unapprovedekom/<?= $row['id']; ?>" method="POST"
                                                            style="display: inline-block;">
                                                            <input type="hidden" name="kodebpr" value="<?= $kodebpr ?>" />
                                                            <input type="hidden" name="periode_id" value="<?= $periodeId ?>" />
                                                            <input type="hidden" name="faktor6id" value="<?= $row['id']; ?>" />
                                                            <button type="submit" class="btn btn-sm btn-success" style="font-weight: 600;">
                                                                <i class="fas fa-check-circle"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                    <?php
                                                } else {
                                                    // Jika accdekom != 1, tampilkan tombol approve
                                                    ?>
                                                    <td>
                                                        <form action="/faktor6/accdekom/<?= $row['id']; ?>" method="POST"
                                                            style="display: inline-block;">
                                                            <input type="hidden" name="kodebpr" value="<?= $kodebpr ?>" />
                                                            <input type="hidden" name="periode_id" value="<?= $periodeId ?>" />
                                                            <input type="hidden" name="faktor6id" value="<?= $row['id']; ?>" />
                                                            <button type="submit" class="btn btn-sm btn-dark" style="font-weight: 600;">
                                                                <i class="fas fa-times-circle"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        <?php endif; ?>
                                        <?php if ($userInGroupAdmin || $userInGroupDekom2): ?>
                                            <?php
                                            // Mendapatkan nilai user_id dan active_periode dari session
                                            $currentUserId = session()->get('user_id');
                                            $activePeriodeId = session()->get('active_periode');
                                            if ($row['kodebpr'] == $kodebpr && $row['periode_id'] == $activePeriodeId) {
                                                $accdekom2Value = $row['accdekom2'] ?? null;

                                                // Jika accdekom == 1 dan filter sesuai dengan kodebpr dan periode_id
                                                if ($accdekom2Value == 1) {
                                                    // Jika accdekom == 1, tampilkan tombol unapprove
                                                    ?>
                                                    <td>
                                                        <form action="/faktor6/unapprovedekom2/<?= $row['id']; ?>" method="POST"
                                                            style="display: inline-block;">
                                                            <input type="hidden" name="kodebpr" value="<?= $kodebpr ?>" />
                                                            <input type="hidden" name="periode_id" value="<?= $periodeId ?>" />
                                                            <input type="hidden" name="faktor6id" value="<?= $row['id']; ?>" />
                                                            <button type="submit" class="btn btn-sm btn-success" style="font-weight: 600;">
                                                                <i class="fas fa-check-circle"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                    <?php
                                                } else {
                                                    // Jika accdekom != 1, tampilkan tombol approve
                                                    ?>
                                                    <td>
                                                        <form action="/faktor6/accdekom2/<?= $row['id']; ?>" method="POST"
                                                            style="display: inline-block;">
                                                            <input type="hidden" name="kodebpr" value="<?= $kodebpr ?>" />
                                                            <input type="hidden" name="periode_id" value="<?= $periodeId ?>" />
                                                            <input type="hidden" name="faktor6id" value="<?= $row['id']; ?>" />
                                                            <button type="submit" class="btn btn-sm btn-dark" style="font-weight: 600;">
                                                                <i class="fas fa-times-circle"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        <?php endif; ?>
                                        <?php if ($userInGroupAdmin || $userInGroupDireksi2 || $userInGroupDireksi): ?>
                                            <?php
                                            // Mendapatkan nilai user_id dan active_periode dari session
                                            $currentUserId = session()->get('user_id');
                                            $activePeriodeId = session()->get('active_periode');
                                            if ($row['kodebpr'] == $kodebpr && $row['periode_id'] == $activePeriodeId) {
                                                $accdir2Value = $row['accdir2'] ?? null;

                                                // Jika accdekom == 1 dan filter sesuai dengan kodebpr dan periode_id
                                                if ($accdir2Value == 1) {
                                                    // Jika accdekom == 1, tampilkan tombol unapprove
                                                    ?>
                                                    <td>
                                                        <form action="/faktor6/unapprovedir2/<?= $row['id']; ?>" method="POST"
                                                            style="display: inline-block;">
                                                            <input type="hidden" name="kodebpr" value="<?= $kodebpr ?>" />
                                                            <input type="hidden" name="periode_id" value="<?= $periodeId ?>" />
                                                            <input type="hidden" name="faktor6id" value="<?= $row['id']; ?>" />
                                                            <button type="submit" class="btn btn-sm btn-success" style="font-weight: 600;">
                                                                <i class="fas fa-check-circle"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                    <?php
                                                } else {
                                                    // Jika accdekom != 1, tampilkan tombol approve
                                                    ?>
                                                    <td>
                                                        <form action="/faktor6/accdir2/<?= $row['id']; ?>" method="POST"
                                                            style="display: inline-block;">
                                                            <input type="hidden" name="kodebpr" value="<?= $kodebpr ?>" />
                                                            <input type="hidden" name="periode_id" value="<?= $periodeId ?>" />
                                                            <input type="hidden" name="faktor6id" value="<?= $row['id']; ?>" />
                                                            <button type="submit" class="btn btn-sm btn-dark" style="font-weight: 600;">
                                                                <i class="fas fa-times-circle"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        <?php endif; ?>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <span class="label" style="flex: 3; margin-right: 10px;">
                <strong>Kesimpulan Penilaian Faktor 6 (Penerapan Fungsi Kepatuhan)</strong>
            </span>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <!-- Table header -->
                <thead>
                    <tr>
                        <th colspan="5" class="text-center">Nilai Faktor 6</th>
                    </tr>
                    <?php if ($rataRata !== null): ?>
                        <tr>
                            <th colspan="5" class="text-center"><strong><?= $rataRata ?></strong></th>
                        </tr>
                        <tr>
                            <th colspan="5" class="text-center">Penjelasan Nilai Faktor</th>
                        </tr>
                        <?php if ($rataRata !== null): ?>
                            <tr>
                                <td colspan="5" class="text-center">
                                    <?php
                                    // Explanation based on rataRata
                                    switch ($rataRata) {
                                        case 1:
                                            echo "<p style='text-align: justify; padding-left: 0px;'>";
                                            echo "<li>Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan sangat memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang sangat baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:</li>";

                                            echo "<ol style='text-align: left; padding-left: 40px;' type='a'>";
                                            echo "<li>Anggota Direksi yang membawahkan fungsi kepatuhan memenuhi seluruh persyaratan yang harus dipenuhi selama menjabat sesuai dengan ketentuan sehingga pelaksanaan tugas dan tanggung jawab berjalan dengan sangat baik serta hasil kinerja anggota Direksi yang membawahkan fungsi kepatuhan dapat dipertanggungjawabkan sepenuhnya kepada direktur utama atau Dewan Komisaris (bagi Direksi yang membawahkan fungsi kepatuhan adalah direktur utama) dan tidak terdapat pelanggaran yang signifikan atau berhasil menurunkan tingkat pelanggaran signifikan.</li>";
                                            echo "<li>Anggota Direksi yang membawahkan fungsi kepatuhan telah membentuk satuan kerja atau mengangkat Pejabat Eksekutif dengan memperhatikan kompleksitas kegiatan usaha dalam rangka mendukung pelaksanaan tugas dan fungsi anggota Direksi yang membawahkan fungsi kepatuhan sehingga prinsip tata kelola diterapkan secara efektif sesuai dengan ketentuan peraturan perundang-undangan maupun pedoman internal dan tata tertib kerja.</li>";
                                            echo "<li>Satuan kerja kepatuhan dan Pejabat Eksekutif yang membawahkan fungsi kepatuhan telah memiliki  dan/atau menginikan secara berkala pedoman dan tata tertib kerja sehingga pelaksanaan tugas terlaksana dengan memperhatikan pedoman dan tata tertib kerja.</li>";
                                            echo "</ol>";
                                            break;
                                        case 2:
                                            echo "<p style='text-align: justify; padding-left: 0px;'>";
                                            echo "<li>Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:</li>";

                                            echo "<ol style='text-align: left; padding-left: 40px;' type='a'>";
                                            echo "<li>Anggota Direksi yang membawahkan fungsi kepatuhan memenuhi seluruh persyaratan yang harus dipenuhi selama menjabat sesuai dengan ketentuan sehingga pelaksanaan tugas dan tanggung jawab berjalan dengan baik serta hasil kinerja anggota Direksi yang membawahkan fungsi kepatuhan dapat dipertanggungjawabkan kepada direktur utama atau Dewan Komisaris (bagi Direksi yang membawahkan fungsi kepatuhan adalah direktur utama) dan berhasil menurunkan tingkat pelanggaran.</li>";
                                            echo "<li>Anggota Direksi yang membawahkan fungsi kepatuhan telah membentuk satuan kerja atau mengangkat Pejabat Eksekutif dalam rangka mendukung pelaksanaan tugas dan fungsi anggota Direksi yang membawahkan fungsi kepatuhan sehingga prinsip tata kelola diterapkan sesuai dengan ketentuan peraturan perundang-undangan maupun pedoman internal dan tata tertib kerja.</li>";
                                            echo "<li>Satuan kerja kepatuhan dan Pejabat Eksekutif yang membawahkan fungsi kepatuhan telah memiliki dan/atau menginikan pedoman dan tata tertib kerja sehingga pelaksanaan tugas terlaksana dengan memperhatikan pedoman dan tata tertib kerja.</li>";
                                            echo "</ol>";
                                            break;
                                        case 3:
                                            echo "<p style='text-align: justify; padding-left: 0px;'>";
                                            echo "<li>Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan cukup memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang cukup baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:</li>";

                                            echo "<ol style='text-align: left; padding-left: 40px;' type='a'>";
                                            echo "<li>Anggota Direksi yang membawahkan fungsi kepatuhan memenuhi seluruh persyaratan yang harus dipenuhi selama menjabat sesuai dengan ketentuan sehingga pelaksanaan tugas dan tanggung jawab berjalan dengan cukup baik serta hasil kinerja anggota Direksi yang membawahkan fungsi kepatuhan dapat dipertanggungjawabkan kepada direktur utama atau Dewan Komisaris (bagi Direksi yang membawahkan fungsi kepatuhan adalah direktur utama) dan cukup berhasil menurunkan tingkat pelanggaran.</li>";
                                            echo "<li>Anggota Direksi yang membawahkan fungsi kepatuhan telah membentuk satuan kerja atau mengangkat Pejabat Eksekutif namun belum dapat mendukung sepenuhnya pelaksanaan tugas dan fungsi Direksi yang membawahkan fungsi kepatuhan sehingga penerapan prinsip tata kelola belum sepenuhnya sesuai dengan ketentuan peraturan perundang-undangan maupun pedoman internal dan tata tertib kerja.</li>";
                                            echo "<li>Satuan kerja kepatuhan dan Pejabat Eksekutif yang membawahkan fungsi kepatuhan telah memiliki pedoman dan tata tertib kerja sehingga pelaksanaan tugas terlaksana dengan memperhatikan pedoman dan tata tertib kerja.</li>";
                                            echo "</ol>";
                                            break;
                                        case 4:
                                            echo "<p style='text-align: justify; padding-left: 0px;'>";
                                            echo "<li>Belum sepenuhnya terpenuhi struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan kurang memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang kurang baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:</li>";

                                            echo "<ol style='text-align: left; padding-left: 40px;' type='a'>";
                                            echo "<li>Anggota Direksi yang membawahkan fungsi kepatuhan memenuhi sebagian persyaratan yang harus dipenuhi selama menjabat sesuai dengan ketentuan sehingga pelaksanaan tugas dan tanggung jawab berjalan dengan kurang baik serta hasil kinerja anggota Direksi yang membawahkan fungsi kepatuhan tidak dapat dipertanggungjawabkan sepenuhnya kepada direktur utama atau Dewan Komisaris (bagi Direksi yang membawahkan fungsi kepatuhan adalah direktur utama) dan kurang berhasil menurunkan tingkat pelanggaran.</li>";
                                            echo "<li>Anggota Direksi yang membawahkan fungsi kepatuhan telah membentuk satuan kerja atau mengangkat Pejabat Eksekutif namun tidak sesuai dengan ketentuan sehingga kurang mendukung pelaksanaan tugas dan fungsi anggota Direksi yang membawahkan fungsi kepatuhan dan penerapan prinsip tata kelola belum sesuai dengan ketentuan peraturan perundang-undangan maupun pedoman internal dan tata tertib kerja.</li>";
                                            echo "<li>Satuan kerja kepatuhan dan Pejabat Eksekutif yang membawahkan fungsi kepatuhan telah memiliki pedoman dan tata tertib kerja namun ruang lingkup belum sesuai dengan ketentuan sehingga pelaksanaan tugas tidak terlaksana dengan baik.</li>";
                                            echo "</ol>";
                                            break;
                                        case 5:
                                            echo "<p style='text-align: justify; padding-left: 0px;'>";
                                            echo "<li>Tidak terpenuhi struktur dan/atau infrastruktur sesuai ketentuan, proses penerapan tata kelola dilakukan dengan tidak memadai, dan ditunjukkan dengan hasil penerapan tata kelola yang tidak baik. Contoh/ilustrasi kondisi yang dapat menjadi indikator tersebut antara lain:</li>";

                                            echo "<ol style='text-align: left; padding-left: 40px;' type='a'>";
                                            echo "<li>Anggota Direksi yang membawahkan fungsi kepatuhan tidak memenuhi seluruh persyaratan yang harus dipenuhi selama menjabat sesuai dengan ketentuan sehingga pelaksanaan tugas dan tanggung jawab tidak berjalan dengan baik serta hasil kinerja anggota Direksi yang membawahkan fungsi kepatuhan tidak dapat dipertanggungjawabkan kepada direktur utama atau Dewan Komisaris (bagi Direksi yang membawahkan fungsi kepatuhan adalah direktur utama) dan tidak berhasil menurunkan tingkat pelanggaran.</li>";
                                            echo "<li>Anggota Direksi yang membawahkan fungsi kepatuhan tidak membentuk satuan kerja atau mengangkat Pejabat Eksekutif sesuai dengan ketentuan dalam rangka mendukung pelaksanaan tugas dan fungsi anggota Direksi yang membawahkan fungsi kepatuhan sehingga prinsip tata kelola tidak dapat diterapkan sesuai dengan ketentuan peraturan perundang-undangan maupun pedoman internal dan tata tertib kerja.</li>";
                                            echo "<li>Satuan kerja kepatuhan dan Pejabat Eksekutif yang membawahkan fungsi kepatuhan tidak memiliki pedoman dan tata tertib kerja sehingga pelaksanaan tugas tidak dapat terlaksana dengan baik.</li>";
                                            echo "</ol>";
                                            break;
                                        default:
                                            echo "Belum ada nilai faktor6.";
                                            break;
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endif; ?>
                </thead>
                <!-- Table body -->
            </table>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <!-- Table header -->
                <thead>
                    <tr>
                        <th colspan="5" class="text-center">Kesimpulan
                            <?php if ($userInGroupPE || $userInGroupAdmin):
                                // Find the specific conclusion data for this BPR and period
                                $kesimpulanData = array_filter($faktors6, function ($item) use ($kodebpr, $periodeId) {
                                    return $item['kodebpr'] == $kodebpr && $item['periode_id'] == $periodeId;
                                });

                                if (!empty($kesimpulanData)) {
                                    $kesimpulan = reset($kesimpulanData);
                                    ?>
                                    <button type="button" data-toggle="modal" class="btn" data-target="#modalUbahkesimpulan"
                                        id="btn-edit" style="font-weight: 600;" data-id="<?= $row['id']; ?>"
                                        data-positifstruktur="<?= $row['positifstruktur']; ?>"
                                        data-negatifstruktur="<?= $row['negatifstruktur']; ?>"
                                        data-positifproses="<?= $row['positifproses']; ?>"
                                        data-negatifproses="<?= $row['negatifproses']; ?>"
                                        data-positifhasil="<?= $row['positifhasil']; ?>"
                                        data-negatifhasil="<?= $row['negatifhasil']; ?>"><i class="fa fa-plus"></i>&nbsp;
                                    </button>
                                <?php } ?>
                            <?php endif; ?>
                        </th>
                    </tr>
                </thead>
            </table>
            <table class="table">
                <tbody>
                    <?php
                    // Filter faktor6 data for current BPR and period
                    $filteredFaktors = array_filter($faktors6, function ($item) use ($kodebpr, $periodeId) {
                        return $item['kodebpr'] == $kodebpr && $item['periode_id'] == $periodeId;
                    });

                    // Jika data untuk kodebpr dan periode tidak ditemukan
                    if (empty($filteredFaktors)) { ?>
                        <tr>
                            <td colspan="2"><?= esc($bprData['namabpr'] ?? 'BPR') ?> mengamati tidak ada data untuk
                                faktor6
                                ini.</td>
                        </tr>
                    <?php } else {
                        // Tampilkan setiap item dengan filter yang benar
                        foreach ($filteredFaktors as $kesimpulan) {
                            if (!empty($kesimpulan['positifstruktur'])) { ?>
                                <tr>
                                    <th style="text-align: left; width: 25%;">Faktor Positif (Struktur):</th>
                                </tr>
                                <tr>
                                    <td style="width: 75%;"><?= esc($kesimpulan['positifstruktur']) ?></td>
                                </tr>
                            <?php }
                            if (!empty($kesimpulan['negatifstruktur'])) { ?>
                                <tr>
                                    <th style="text-align: left; width: 25%;">Faktor Negatif (Struktur):</th>
                                </tr>
                                <tr>
                                    <td style="width: 75%;"><?= esc($kesimpulan['negatifstruktur']) ?></td>
                                </tr>
                            <?php }
                            if (!empty($kesimpulan['positifproses'])) { ?>
                                <tr>
                                    <th style="text-align: left; width: 25%;">Faktor Positif (Proses):</th>
                                </tr>
                                <tr>
                                    <td style="width: 75%;"><?= esc($kesimpulan['positifproses']) ?></td>
                                </tr>
                            <?php }
                            if (!empty($kesimpulan['negatifproses'])) { ?>
                                <tr>
                                    <th style="text-align: left; width: 25%;">Faktor Negatif (Proses):</th>
                                </tr>
                                <tr>
                                    <td style="width: 75%;"><?= esc($kesimpulan['negatifproses']) ?></td>
                                </tr>
                            <?php }
                            if (!empty($kesimpulan['positifhasil'])) { ?>
                                <tr>
                                    <th style="text-align: left; width: 25%;">Faktor Positif (Hasil):</th>
                                </tr>
                                <tr>
                                    <td style="width: 75%;"><?= esc($kesimpulan['positifhasil']) ?></td>
                                </tr>
                            <?php }
                            if (!empty($kesimpulan['negatifhasil'])) { ?>
                                <tr>
                                    <th style="text-align: left; width: 25%;">Faktor Negatif (Hasil):</th>
                                </tr>
                                <tr>
                                    <td style="width: 75%;"><?= esc($kesimpulan['negatifhasil']) ?></td>
                                </tr>
                            <?php }
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
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
            <div class="text-center mt-1">
                <a href="<?= base_url('periode'); ?>" class="btn btn-link btn-sm">Kembali ke halaman periode</a>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($faktors6)) { ?>
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
                    <!-- Form untuk ubah data -->
                    <form action="<?= base_url('faktor6/ubah'); ?>" method="post">
                        <!-- Hidden field untuk faktor6id -->
                        <input type="hidden" name="faktor6id" id="id-faktor6" value="">
                        <div class="mb-3">
                            <label for="sub_category" class="form-label">Sub Kategori: </label>
                            <textarea class="form-control" name="sub_category" id="sub_category" style="height: 100px"
                                readonly></textarea>
                        </div>
                        <label for="nilai" class="form-label">Pilih Nilai Faktor:</label>
                        <input type="hidden" name="nilai" id="nilai" value="">

                        <div class="list-group" id="ratingList" name="nilai">
                            <a href="#" class="list-group-item list-group-item-action py-1 px-2" value="1">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-0 fs-6"><b>Nilai 1</b></h6>
                                </div>
                                <p class="mb-0 small">Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur
                                    sesuai ketentuan, proses pelaksanaan tata kelola dilakukan dengan memadai dan
                                    ditunjukkan dengan hasil pelaksanaan tata kelola yang <b>Sangat Baik</b></p>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action py-1 px-2" value="2">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-0 fs-6"><b>Nilai 2</b></h6>
                                </div>
                                <p class="mb-0 small">Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur
                                    sesuai ketentuan, proses pelaksanaan tata kelola dilakukan dengan memadai dan
                                    ditunjukkan dengan hasil pelaksanaan tata kelola yang <b>Baik</b></p>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action py-1 px-2" value="3">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-0 fs-6"><b>Nilai 3</b></h6>
                                </div>
                                <p class="mb-0 small">Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur
                                    sesuai ketentuan, proses pelaksanaan tata kelola dilakukan dengan memadai dan
                                    ditunjukkan dengan hasil pelaksanaan tata kelola yang <b>Cukup Baik</b></p>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action py-1 px-2" value="4">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-0 fs-6"><b>Nilai 4</b></h6>
                                </div>
                                <p class="mb-0 small">Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur
                                    sesuai ketentuan, proses pelaksanaan tata kelola dilakukan dengan memadai dan
                                    ditunjukkan dengan hasil pelaksanaan tata kelola yang <b>Kurang Baik</b></p>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action py-1 px-2" value="5">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-0 fs-6"><b>Nilai 5</b></h6>
                                </div>
                                <p class="mb-0 small">Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur
                                    sesuai ketentuan, proses pelaksanaan tata kelola dilakukan dengan memadai dan
                                    ditunjukkan dengan hasil pelaksanaan tata kelola yang <b>Buruk</b></p>
                            </a>
                        </div>

                        <p class="mt-3">Nilai yang dipilih: <strong id="selectedValue">Belum ada</strong></p>
                        <div class="mb-3">
                            <label for="keterangan" class="form-label">Keterangan: </label>
                            <textarea class="form-control" name="keterangan" id="keterangan" style="height: 100px"
                                required></textarea>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" name="ubah" class="btn btn-primary">Ubah Data</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php } ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ratingList = document.getElementById('ratingList');
        const listItems = ratingList.querySelectorAll('.list-group-item');
        const selectedValueDisplay = document.getElementById('selectedValue');
        // Changed this line to get the input by its new ID
        const hiddenRatingInput = document.getElementById('nilai');

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

                console.log('Nilai yang dipilih (untuk disimpan):', selectedRating);
            });
        });
    });
</script>

<?php if (!empty($faktors6)) { ?>
    <div class="modal fade" id="modalUbahkesimpulan">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Kesimpulan Penilaian Faktor 6 </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('faktor6/ubahkesimpulan'); ?>" method="post">
                        <input type="hidden" name="faktor6id" id="id-faktor6" value="">
                        <div class="mb-3">
                            <label for="positifstruktur">Faktor Positif (Struktur):</label>
                            <textarea class="form-control" type="text" name="positifstruktur" id="positifstruktur"
                                placeholder="<?= $row['positifstruktur'] ?>" style="height: 100px;" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="negatifstruktur">Faktor Negatif (Struktur):</label>
                            <textarea class="form-control" type="text" name="negatifstruktur" id="negatifstruktur"
                                placeholder="<?= $row['negatifstruktur'] ?>" style="height: 100px;" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="positifproses">Faktor Positif (Proses):</label>
                            <textarea class="form-control" type="text" name="positifproses" id="positifproses"
                                placeholder="<?= $row['positifproses'] ?>" style="height: 100px;" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="negatifproses">Faktor Negatif (Proses):</label>
                            <textarea class="form-control" type="text" name="negatifproses" id="negatifproses"
                                placeholder="<?= $row['negatifproses'] ?>" style="height: 100px;" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="positifhasil">Faktor Positif (Hasil):</label>
                            <textarea class="form-control" type="text" name="positifhasil" id="positifhasil"
                                placeholder="<?= $row['positifhasil'] ?>" style="height: 100px;" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="negatifhasil">Faktor Negatif (Hasil):</label>
                            <textarea class="form-control" type="text" name="negatifhasil" id="negatifhasil"
                                placeholder="<?= $row['negatifhasil'] ?>" style="height: 100px;" required></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" name="ubahkesimpulan" class="btn btn-primary">Ubah Data</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php } ?>

<div class="modal fade" id="modaltambahNilai">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form action="<?= base_url('faktor6/tambahNilai'); ?>" method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Faktor 6</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php date_default_timezone_set('Asia/Jakarta'); ?>

                    <input type="hidden" name="faktor6_id" id="id-faktor6" readonly>
                    <div class="mb-3">
                        <label for="sub_category" class="form-label">Sub Kategori: </label>
                        <textarea class="form-control" name="sub_category" id="sub_category" style="height: 100px"
                            readonly></textarea>
                    </div>
                    <label for="nilai" class="form-label">Pilih Nilai Faktor:</label>
                    <input type="hidden" name="nilai" id="nilai" value="">

                    <div class="list-group" id="ratingLists">
                        <a href="#" class="list-group-item list-group-item-action py-1 px-2" data-value="1">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-0 fs-6"><b>Nilai 1</b></h6>
                            </div>
                            <p class="mb-0 small">Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur
                                sesuai ketentuan, proses pelaksanaan tata kelola dilakukan dengan memadai dan
                                ditunjukkan dengan hasil pelaksanaan tata kelola yang <b>Sangat Baik</b></p>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action py-1 px-2" data-value="2">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-0 fs-6"><b>Nilai 2</b></h6>
                            </div>
                            <p class="mb-0 small">Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur
                                sesuai ketentuan, proses pelaksanaan tata kelola dilakukan dengan memadai dan
                                ditunjukkan dengan hasil pelaksanaan tata kelola yang <b>Baik</b></p>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action py-1 px-2" data-value="3">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-0 fs-6"><b>Nilai 3</b></h6>
                            </div>
                            <p class="mb-0 small">Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur
                                sesuai ketentuan, proses pelaksanaan tata kelola dilakukan dengan memadai dan
                                ditunjukkan dengan hasil pelaksanaan tata kelola yang <b>Cukup Baik</b></p>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action py-1 px-2" data-value="4">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-0 fs-6"><b>Nilai 4</b></h6>
                            </div>
                            <p class="mb-0 small">Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur
                                sesuai ketentuan, proses pelaksanaan tata kelola dilakukan dengan memadai dan
                                ditunjukkan dengan hasil pelaksanaan tata kelola yang <b>Kurang Baik</b></p>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action py-1 px-2" data-value="5">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-0 fs-6"><b>Nilai 5</b></h6>
                            </div>
                            <p class="mb-0 small">Memenuhi kondisi terpenuhinya struktur dan/atau infrastruktur
                                sesuai ketentuan, proses pelaksanaan tata kelola dilakukan dengan memadai dan
                                ditunjukkan dengan hasil pelaksanaan tata kelola yang <b>Buruk</b></p>
                        </a>
                    </div>

                    <p class="mt-3">Nilai yang dipilih: <strong id="selectedValues">Belum ada</strong></p>
                    <input type="hidden" name="fullname" value="<?= htmlspecialchars($fullname) ?>">
                    <input type="hidden" name="date" value="<?= date('Y-m-d H:i:s') ?>">

                    <div class="form-group">
                        <label for="keterangan">Tambah Keterangan:</label>
                        <textarea class="form-control" name="keterangan" id="keterangan" style="height: 100px"
                            required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="submit" name="tambahNilai" class="btn btn-primary">Simpan Komentar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        $('#modaltambahNilai').on('shown.bs.modal', function () {
            const modal = $(this);
            const ratingLists = modal.find('#ratingLists')[0]; // [0] to get the native DOM element from jQuery object

            if (!ratingLists) {
                console.warn("Element with ID 'ratingLists' not found within #modaltambahNilai. Script might not function correctly.");
                return;
            }

            const listItems = ratingLists.querySelectorAll('.list-group-item');
            const selectedValuesDisplay = modal.find('#selectedValues')[0];
            const nilaiInput = modal.find('#nilai')[0];

            listItems.forEach(li => li.classList.remove('active'));
            nilaiInput.value = '';
            selectedValuesDisplay.textContent = 'Belum ada';

            listItems.forEach(item => {
                item.removeEventListener('click', handleRatingSelection);
                item.addEventListener('click', handleRatingSelection);
            });

            function handleRatingSelection(event) {
                event.preventDefault();
                listItems.forEach(li => li.classList.remove('active'));
                this.classList.add('active');
                const selectedRatings = this.getAttribute('data-value');
                nilaiInput.value = selectedRatings;

                selectedValuesDisplay.textContent = selectedRatings;

                console.log('Nilai yang dipilih (untuk disimpan):', selectedRatings);
            }
        });
    });
</script>

<!-- Modal untuk Tambah Komentar -->
<div class="modal fade" id="modaltambahKomentar">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="<?= base_url('faktor6/tambahKomentar'); ?>" method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Komentar Direksi dan Dewan Komisaris</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php date_default_timezone_set('Asia/Jakarta'); ?>

                    <input type="hidden" name="faktor6_id" id="id-faktor6">
                    <input type="hidden" name="kodebpr" value="<?= $kodebpr ?>"> <!-- Tambahkan ini -->

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
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="submit" name="tambahKomentar" class="btn btn-primary">Simpan Komentar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalHapusnilai6">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                Apakah anda yakin ingin menghapus data?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btnHapusnilai6">Yakin</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {

        $('#formTambahKomentar').on('submit', function (e) {
            e.preventDefault();
            var formData = $(this).serialize();

            $.ajax({
                url: '<?= base_url('faktor6/save_komentar'); ?>',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        alert(response.message);
                        $('#komentarText').val('');
                        var currentFaktorId = $('#inputFaktorId').val();
                        $(`button[data-id="${currentFaktorId}"]`).click();

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
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const btnSetNulls = document.querySelectorAll('#btn-set-null');

        btnSetNulls.forEach(function (btn) {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-id');

                if (confirm("Apakah Anda yakin hendak menghapus data nilai dan keterangan ini?")) {
                    window.location.href = "/faktor6/setNullKolom/" + id;
                }
            });
        });
    });
</script>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<!-- <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script> -->
<script>
    // Define global variables from PHP for consistent use in JS
    const GLOBAL_KODEBPR = '<?= $kodebpr ?? '' ?>'; // Use null coalescing to prevent errors if not set
    const GLOBAL_ACTIVE_PERIODE_ID = '<?= $activePeriodeId ?? '' ?>';
    const GLOBAL_CURRENT_USER_ID = '<?= session()->get('user_id') ?? '' ?>';

    document.addEventListener('DOMContentLoaded', function () {
        const commentButtons = document.querySelectorAll('.komentar-button');

        // Function to update a single badge's count and visibility
        function updateBadge(faktor6Id, newCount) {
            const badge = document.getElementById('notification-badge-' + faktor6Id);
            if (badge) {
                if (newCount > 0) {
                    badge.textContent = newCount;
                    badge.style.display = 'inline-flex';
                } else {
                    badge.style.display = 'none';
                    badge.textContent = '0'; // Reset count
                }
            }
        }

        // Function to fetch and display comments in the modal
        function fetchAndDisplayComments(faktor6Id, kodebpr, periodeId) {
            const modal = $('#modaltambahKomentar');
            modal.find('#komentarLamaList').html('<li>Memuat komentar...</li>'); // Show loading message

            $.ajax({
                url: '<?= base_url('faktor6/getKomentarByFaktorId'); ?>/' + faktor6Id,
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
                    modal.find('#komentarLamaList').html('<li>Gagal memuat komentar.</li>');
                }
            });
        }


        // --- Event Listener for Comment Button Clicks (to open modal and mark as read) ---
        commentButtons.forEach(button => {
            button.addEventListener('click', function () {
                const faktor6Id = this.getAttribute('data-faktor-id');
                const kodebpr = this.getAttribute('data-kodebpr');
                const userId = this.getAttribute('data-user-id');
                const periodeId = this.getAttribute('data-periode-id');

                // Set the hidden input in the modal
                $('#modaltambahKomentar').find('#id-faktor6').val(faktor6Id);

                // Fetch and display comments when the modal opens
                fetchAndDisplayComments(faktor6Id, kodebpr, periodeId);

                // AJAX call to mark comments as read for the current user
                $.ajax({
                    url: '<?= base_url('faktor6/markUserCommentsAsRead'); ?>',
                    method: 'POST',
                    data: {
                        faktor6_id: faktor6Id,
                        kodebpr: kodebpr,
                        user_id: userId,
                        periode_id: periodeId,
                        '<?= csrf_token() ?>': '<?= csrf_hash() ?>', // CSRF token
                    },
                    success: function (response) {
                        if (response.status === 'success') {
                            updateBadge(faktor6Id, 0); // Set badge to 0 after comments are marked read
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

        // --- Comment Submission Logic (for #formTambahKomentar) ---
        // This handles submitting a NEW comment via AJAX
        $('#formTambahKomentar').on('submit', function (e) {
            e.preventDefault(); // Prevent default form submission
            const form = $(this);
            const formData = form.serialize();
            const faktor6Id = form.find('#id-faktor6').val(); // Get faktor6Id from the form's hidden input

            $.ajax({
                url: form.attr('action'), // Use the form's action attribute
                method: form.attr('method'), // Use the form's method attribute
                data: formData + '&<?= csrf_token() ?>=' + '<?= csrf_hash() ?>', // Append CSRF token
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        alert(response.message);
                        form.find('#komentar').val(''); // Clear the textarea
                        // Re-fetch and display comments to show the newly added one
                        fetchAndDisplayComments(faktor6Id, GLOBAL_KODEBPR, GLOBAL_ACTIVE_PERIODE_ID);
                        // No need to close modal here if user might add more comments
                        // If you want to close: $('#modaltambahKomentar').modal('hide');
                        // No need to update badge to 0 here, as the new comment is *from* this user
                        // and the polling function will handle other users seeing it.
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

        // --- AJAX Polling for Unread Comment Counts ---
        function pollUnreadCounts() {
            commentButtons.forEach(button => {
                const faktor6Id = button.getAttribute('data-faktor-id');
                const kodebpr = button.getAttribute('data-kodebpr');
                const userId = button.getAttribute('data-user-id');
                const periodeId = button.getAttribute('data-periode-id');

                $.ajax({
                    url: '<?= base_url('faktor6/getUnreadCommentCountForFactor'); ?>',
                    method: 'GET',
                    data: {
                        faktor6_id: faktor6Id,
                        kodebpr: kodebpr,
                        user_id: userId,
                        periode_id: periodeId
                    },
                    success: function (response) {
                        if (response && typeof response.unread_count !== 'undefined') {
                            updateBadge(faktor6Id, response.unread_count);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('Error fetching unread count for factor ' + faktor6Id + ':', error);
                    }
                });
            });
        }

        // Call polling function initially
        pollUnreadCounts();
        // Set up polling interval (e.g., every 60 seconds)
        // setInterval(pollUnreadCounts, 10000);
    });


    // --- Other existing jQuery code for approve checkbox (remains unchanged) ---
    $(document).ready(function () {
        $('.approve-checkbox').change(function () {
            var id = $(this).data('id');
            var isChecked = $(this).prop('checked');
            approveItem(id, isChecked);
        });
    });

    function approveItem(id, isChecked) {
        $.ajax({
            url: '<?= base_url('faktor6/approve') ?>',
            method: 'POST',
            data: {
                id: id,
                approve: isChecked ? 1 : 0,
                '<?= csrf_token() ?>': '<?= csrf_hash() ?>', // CSRF token for security
            },
            success: function (response) {
                if (response.success) {
                    alert(response.message);
                } else {
                    alert(response.message);
                }
            },
            error: function () {
                alert('Terjadi kesalahan jaringan.');
            }
        });
    }

    // --- Existing Set Nulls Logic (remains unchanged) ---
    document.addEventListener('DOMContentLoaded', function () {
        const btnSetNulls = document.querySelectorAll('#btn-set-null');

        btnSetNulls.forEach(function (btn) {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-id');

                if (confirm("Apakah Anda yakin hendak menghapus data nilai dan keterangan ini?")) {
                    window.location.href = "/faktor6/setNullKolom/" + id;
                }
            });
        });
    });

    // --- Existing Approve Dekom/Dir Logic (remains unchanged, added feedback) ---
    $(document).ready(function () {
        $('.btn-accdekom').on('click', function () {
            var faktor6Id = $(this).data('faktorid');
            var kodebpr = $(this).data('kodebpr');
            var periodeId = $(this).data('periodeid');

            $.ajax({
                url: '<?= base_url('faktor6/accdekom'); ?>',
                method: 'POST',
                data: {
                    faktor6id: faktor6Id,
                    kodebpr: kodebpr,
                    periode_id: periodeId,
                    '<?= csrf_token() ?>': '<?= csrf_hash() ?>',
                },
                success: function (response) {
                    if (response.status === 'success') {
                        alert('Approval berhasil!');
                    } else {
                        alert('Approval gagal: ' + response.message);
                    }
                },
                error: function () {
                    alert('Terjadi kesalahan jaringan saat approval.');
                }
            });
        });

        $('.btn-accdekom2').on('click', function () {
            var faktor6Id = $(this).data('faktorid');
            var kodebpr = $(this).data('kodebpr');
            var periodeId = $(this).data('periodeid');

            $.ajax({
                url: '<?= base_url('faktor6/accdekom2'); ?>',
                method: 'POST',
                data: {
                    faktor6id: faktor6Id,
                    kodebpr: kodebpr,
                    periode_id: periodeId,
                    '<?= csrf_token() ?>': '<?= csrf_hash() ?>',
                },
                success: function (response) {
                    if (response.status === 'success') {
                        alert('Approval berhasil!');
                    } else {
                        alert('Approval gagal: ' + response.message);
                    }
                },
                error: function () {
                    alert('Terjadi kesalahan jaringan saat approval.');
                }
            });
        });

        $('.btn-accdir2').on('click', function () {
            var faktor6Id = $(this).data('faktorid');
            var kodebpr = $(this).data('kodebpr');
            var periodeId = $(this).data('periodeid');

            $.ajax({
                url: '<?= base_url('faktor6/accdir2'); ?>',
                method: 'POST',
                data: {
                    faktor6id: faktor6Id,
                    kodebpr: kodebpr,
                    periode_id: periodeId,
                    '<?= csrf_token() ?>': '<?= csrf_hash() ?>',
                },
                success: function (response) {
                    if (response.status === 'success') {
                        alert('Approval berhasil!');
                    } else {
                        alert('Approval gagal: ' + response.message);
                    }
                },
                error: function () {
                    alert('Terjadi kesalahan jaringan saat approval.');
                }
            });
        });
    });
</script>

<script>
    $(document).ready(function () {
        // Event listener untuk tombol Setujui
        $('.btn-accdekom').on('click', function () {
            // Ambil data yang dibutuhkan dari atribut data
            var faktor6Id = $(this).data('faktorid');
            var kodebpr = $(this).data('kodebpr');
            var periodeId = $(this).data('periodeid');

            // Kirim permintaan AJAX ke server
            $.ajax({
                url: '<?= base_url('faktor6/accdekom'); ?>', // Gunakan base_url() agar lebih robust
                method: 'POST',
                data: {
                    faktor6id: faktor6Id,
                    kodebpr: kodebpr,
                    periode_id: periodeId,
                    '<?= csrf_token() ?>': '<?= csrf_hash() ?>', // CSRF token untuk keamanan
                },
            });
        });
    });
</script>

<script>
    $(document).ready(function () {
        // Event listener untuk tombol Setujui
        $('.btn-accdekom2').on('click', function () {
            // Ambil data yang dibutuhkan dari atribut data
            var faktor6Id = $(this).data('faktorid');
            var kodebpr = $(this).data('kodebpr');
            var periodeId = $(this).data('periodeid');

            // Kirim permintaan AJAX ke server
            $.ajax({
                url: '<?= base_url('faktor6/accdekom2'); ?>', // Gunakan base_url() agar lebih robust
                method: 'POST',
                data: {
                    faktor6id: faktor6Id,
                    kodebpr: kodebpr,
                    periode_id: periodeId,
                    '<?= csrf_token() ?>': '<?= csrf_hash() ?>', // CSRF token untuk keamanan
                },
            });
        });
    });
</script>

<script>
    $(document).ready(function () {
        // Event listener untuk tombol Setujui
        $('.btn-accdir2').on('click', function () {
            // Ambil data yang dibutuhkan dari atribut data
            var faktor6Id = $(this).data('faktorid');
            var kodebpr = $(this).data('kodebpr');
            var periodeId = $(this).data('periodeid');

            // Kirim permintaan AJAX ke server
            $.ajax({
                url: '<?= base_url('faktor6/accdir2'); ?>', // Gunakan base_url() agar lebih robust
                method: 'POST',
                data: {
                    faktor6id: faktor6Id,
                    kodebpr: kodebpr,
                    periode_id: periodeId,
                    '<?= csrf_token() ?>': '<?= csrf_hash() ?>', // CSRF token untuk keamanan
                },
            });
        });
    });
</script>

<style>
    /* General Styling */
    body {
        font-family: 'Arial', sans-serif;
        background-color: #f8f9fa;
        margin: 0;
        padding: 0;
    }

    /* Container for whole content */
    .container-fluid {
        margin-top: 30px;
    }

    /* Heading Styling */
    h3,
    h4 {
        font-weight: bold;
        color: #343a40;
    }

    /* Alert Styling */
    .alert {
        font-size: 14px;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    /* Custom Success Alert */
    .alert-success {
        background-color: #28a745;
        color: #fff;
    }

    /* Table Styling */
    .table {
        width: 100%;
        margin-bottom: 1rem;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .table th,
    .table td {
        padding: 12px 15px;
        text-align: center;
        border-bottom: 1px solid #dee2e6;
    }

    .table th {
        background-color: #f1f3f5;
        color: #495057;
    }

    .table-striped tbody tr:nth-of-type(odd) {
        background-color: #f8f9fa;
    }

    /* Badge Styling */
    .badge {
        font-size: 14px;
        padding: 5px 10px;
        border-radius: 20px;
    }

    .badge-success {
        background-color: #28a745;
        color: white;
    }

    .badge-secondary {
        background-color: #6c757d;
        color: white;
    }

    .badge-primary {
        background-color: #007bff;
        color: white;
    }

    /* Card Styling */
    .card-body {
        padding: 20px;
    }

    .card {
        margin-bottom: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .card-header {
        background-color: #f1f3f5;
        border-bottom: 2px solid #ddd;
        font-weight: bold;
    }

    .cardpilihfaktor {
        width: auto;
        max-width: 440px;
        margin: 10px auto;
        border: 1px solid #ddd;
        border-radius: 8px;
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

    /* Buttons Styling */
    .btn {
        font-size: 14px;
        padding: 8px 20px;
        border-radius: 5px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-outline-primary {
        border: 1px solid #007bff;
        background-color: transparent;
        color: #007bff;
    }

    .btn-outline-primary:hover {
        background-color: #007bff;
        color: white;
    }

    .btn-sm {
        padding: 5px 10px;
        font-size: 12px;
    }

    /* Modal Button Styling */
    .modal-footer .btn {
        padding: 10px 25px;
        font-size: 16px;
        border-radius: 8px;
    }

    /* Input fields in table rows */
    input[type="text"],
    input[type="number"],
    textarea {
        width: 100%;
        padding: 10px;
        font-size: 14px;
        border: 1px solid #ddd;
        border-radius: 5px;
        margin-top: 5px;
        margin-bottom: 15px;
        box-sizing: border-box;
    }

    /* Add Hover Effects to Buttons */
    .btn:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
    }

    /* Adjust Modal Header */
    .modal-header {
        background-color: #007bff;
        color: #fff;
        font-size: 18px;
        padding: 15px;
    }

    /* Add Scroll to Large Tables */
    .table-responsive {
        overflow-x: auto;
    }

    /* Your existing CSS for the badge and button should be here */
    .komentar-btn-wrapper {
        position: relative;
        display: inline-block;
    }

    .notification-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background-color: red;
        color: white;
        padding: 5px;
        border-radius: 50%;
        font-size: 10px;
        line-height: 1;
        min-width: 20px;
        height: 20px;
        display: flex;
        justify-content: center;
        align-items: center;
        box-sizing: border-box;
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