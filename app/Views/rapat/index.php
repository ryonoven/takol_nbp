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

<!-- Begin Page Content -->
<div class="container-fluid">
    <div class="row">
        <div class="col-md-6">
            <?php if (session()->get('err')): ?>
                <div class="alert alert-danger" role="alert"><?= session()->get('err'); ?></div>
            <?php endif; ?>
        </div>
    </div>
    <div class="card shadow">
        <div class="card-headertitle">
            <?php
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

            <h1 class="h3 mb-4 text-gray-800 text-center"><?= $judul; ?><br>(E0701)</h1>
        </div>

        <div class="card-body">
            <div class="d-flex justify-content-between">
                <div>
                    <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                        <div class="btn-group" role="group" aria-label="Button group">
                            <button type="button" class="btn btn-primarys btn-sm" data-toggle="modal"
                                data-target="#modalTambahrapat"><i class="fa fa-plus"></i> Tambah Data </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <table class="table table-info table-hover">
                <thead class="thead-primary">
                    <tr>
                        <th>Pelaksanaan Rapat dalam 1 (satu) Tahun</th>
                    </tr>
                </thead>
            </table>
            <table class="table table-bordered table-hover">
                <tbody>
                    <?php if (empty($rapat)) { ?>
                        <tr>
                            <td colspan="3" class="text-center">
                                <em>Tidak ada data Pelaksanaan Rapat dalam 1 (satu) Tahun</em>
                            </td>
                        </tr>
                    <?php } else { ?>
                        <?php
                        $i = 1;
                        foreach ($rapat as $row):
                            if (
                                !empty($row['tanggalrapat']) && !is_null($row['tanggalrapat']) &&
                                !empty($row['jumlahpeserta']) && !is_null($row['jumlahpeserta']) &&
                                !empty($row['topikrapat']) && !is_null($row['topikrapat'])
                            ) {

                                $formattedDate = date('d-m-y', strtotime($row['tanggalrapat']));
                                ?>
                                <tr>
                                    <th class="table-info" style="width: 3%;" rowspan="4"><?= $i++; ?></th>
                                    <th style="width: 25%; color: black;">Tanggal Pelaksanaan Rapat :</th>
                                    <td><?= htmlspecialchars($formattedDate); ?></td>
                                </tr>
                                <tr>
                                    <th style="width: 25%; color: black;">Jumlah Peserta Rapat :</th>
                                    <td><?= htmlspecialchars($row['jumlahpeserta']); ?> Orang</td>
                                </tr>
                                <tr>
                                    <th>Topik Rapat :</th>
                                    <td colspan="2">
                                        <ul style="list-style-type: square; padding-left: 1.5em;">
                                            <?php
                                            $rapat = explode("\n", $row['topikrapat']);
                                            foreach ($rapat as $poin) {
                                                echo '<li>' . htmlspecialchars(trim($poin)) . '</li>';
                                            }
                                            ?>
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                                        <td colspan="3">
                                            <button type="button" class="btn btn-outline-warning btn-sm" data-toggle="modal"
                                                data-target="#modalUbahrapat" id="btn-edit" data-id="<?= $row['id']; ?>"
                                                data-tanggalrapat="<?= htmlspecialchars($row['tanggalrapat']); ?>"
                                                data-jumlahpeserta="<?= htmlspecialchars($row['jumlahpeserta']); ?>"
                                                data-topikrapat="<?= htmlspecialchars($row['topikrapat']); ?>">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-sm" data-toggle="modal"
                                                data-target="#modalHapus" id="btn-hapus" data-id="<?= $row['id']; ?>">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                                <tr>
                                    <td colspan="3"
                                        style="height: 20px; background-color: #ffffff; border-left: hidden; border-right: hidden;">
                                    </td>
                                </tr>
                                <?php
                            }
                        endforeach;

                        $hasValidData = false;
                        foreach ($rapat as $row) {
                            if (
                                !empty($row['jumlahpeserta']) && !is_null($row['jumlahpeserta']) &&
                                !empty($row['tanggalrapat']) && !is_null($row['tanggalrapat']) &&
                                !empty($row['topikrapat']) && !is_null($row['topikrapat'])
                            ) {
                                $hasValidData = true;
                                break;
                            }
                        }

                        if (!$hasValidData) {
                            ?>
                            <!-- <tr>
                                <td colspan="3" class="text-center">
                                    <em>Tidak ada data yang valid</em>
                                </td>
                            </tr> -->
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>

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
                            <th class="table-info" style="width: 30%; color: black;">Penjelasan Lebih Lanjut Pelaksanaan
                                Rapat dalam 1 (satu) Tahun (Opsional) :
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
                                    Pelaksanaan Rapat dalam 1 (satu) Tahun (Opsional) :</th>
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
                                <div class="approval-badge-container"> <span class="badge approval-badge">Approval Komisaris
                                        Utama</span> </div>

                                <div class="approval-buttons-container"> <a href="<?= base_url('Rapat/approveSemuaKom') ?>"
                                        class="btn btn-success approval-btn approval-btn-approve"
                                        onclick="return confirm('Apakah Anda yakin hendak melakukan approval?');">
                                        Setuju
                                    </a>
                                    <a href="<?= base_url('Rapat/unapproveSemuaKom') ?>"
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
                                <div class="approval-badge-container"> <span class="badge approval-badge">Approval Direktur
                                        Utama</span> </div>

                                <div class="approval-buttons-container"> <a
                                        href="<?= base_url('Rapat/approveSemuaDirut') ?>"
                                        class="btn btn-success approval-btn approval-btn-approve <?php echo (isset($canApprove) && !$canApprove) ? 'disabled-btn' : ''; ?>"
                                        onclick="return confirm('Apakah Anda yakin ingin melakukan approval?');">
                                        Setuju
                                    </a>
                                    <a href="<?= base_url('Rapat/unapproveSemuaDirut') ?>"
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
            <div class="d-flex justify-content-center mb-5">
                <div class="d-flex justify-content-center gap-2 mb-5">
                    <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                        <a href="<?= base_url('Rapat/exporttxtrapat'); ?>" class="btn btn-secondary btn-sm">
                            <i class="fa fa-file-alt"></i> Export .txt
                        </a>
                    <?php endif; ?>
                    <?php if ($userInGroupDekom || $userInGroupDireksi || $userInGroupPE || $userInGroupAdmin): ?>
                        <td>
                            <?php
                            // $Id = session()->get('id');
                            $subkategori = 'Rapat';
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
            </div>
            <div class="cardpilihfaktor">
                <div class="cardpilihfaktor-header">
                    <h6>Pilih Halaman</h6>
                </div>
                <div class="cardpilihfaktor-body">
                    <div class="d-flex justify-content-center">
                        <div class="btn-toolbar" role="toolbar" aria-label="Toolbar with button groups">
                            <div class="btn-group me-2" role="group" aria-label="First group">
                                <button type="button" class="btn btn-outline-primary btn-sm"
                                    onclick="window.location.href='<?= base_url('Rasiogaji') ?>'">
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
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            onclick="window.location.href='<?= base_url('Rasiogaji'); ?>'">12</button>
                                        <button style="background-color: #000; color: #fff;" type="button"
                                            class="btn btn-outline-primary btn-sm"
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
                                            onclick="window.location.href='<?= base_url('Kehadirandekom') ?>'">>></button>
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
</div>

<?php if (!empty($rapat)): ?>
    <div class="modal fade" id="modalUbahrapat">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Pelaksanaan Rapat dalam 1 (satu) Tahun </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('rapat/ubahrapat'); ?>" method="post">
                        <input type="hidden" name="id" id="id-rapat"
                            value="<?= isset($row['id']) ? esc($row['id']) : ''; ?>">

                        <div class="mb-3">
                            <label for="tanggalrapat" class="form-label">Tanggal Pelaksanaan Rapat:</label>
                            <input class="form-control" type="date" name="tanggalrapat" id="tanggalrapat"
                                value="<?= isset($row['tanggalrapat']) ? esc($row['tanggalrapat']) : ''; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="jumlahpeserta" class="form-label">Jumlah Peserta Rapat:</label>
                            <input class="form-control" type="text" name="jumlahpeserta" id="jumlahpeserta"
                                value="<?= isset($row['jumlahpeserta']) ? esc($row['jumlahpeserta']) : ''; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="topikrapat" class="form-label">Topik Rapat:</label>
                            <textarea class="form-control" type="text" name="topikrapat" id="topikrapat"
                                value="<?= isset($row['topikrapat']) ? esc($row['topikrapat']) : ''; ?>"
                                style="height: 150px" required></textarea>
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="ubahrapat" class="btn btn-primary">Ubah Data</button>
                </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

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
                <form action="<?= base_url('rapat/tambahketerangan'); ?>" method="post">
                    <input type="hidden" name="id" id="id-penjelastindak"> <!-- Hidden field to pass the ID -->

                    <div class="form-group">
                        <label for="tindaklanjut" class="form-label">Penjelasan lebih lanjut (Opsional):
                        </label>
                        <textarea class="form-control" name="tindaklanjut" id="tindaklanjut"
                            style="height: 150px"></textarea>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" name="tambahketerangan" class="btn btn-primary">Ubah Data</button>
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modaleditketerangan">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ubah Penjelasan dan Tindak Lanjut mengenai Pelaksanaan Rapat dalam 1 (satu)
                    Tahun</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('rapat/editketerangan'); ?>" method="post">
                    <input type="hidden" name="id" id="id-penjelastindak"
                        value="<?= isset($row['id']) ? esc($row['id']) : ''; ?>">
                    <div class="form-group">
                        <label for="tindaklanjut" class="form-label">Penjelasan lebih lanjut (Opsional):
                        </label>
                        <textarea class="form-control" name="tindaklanjut" id="tindaklanjut"
                            style="height: 150px"><?= isset($row['tindaklanjut']) ? esc($row['tindaklanjut']) : ''; ?></textarea>
                    </div>
                    <div class="modal-footer">
                        <!-- Submit button should be inside the form to trigger submission -->
                        <button type="submit" name="editketerangan" class="btn btn-primary">Ubah Data</button>
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambahrapat">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Pelaksanaan Rapat dalam 1 (satu) Tahun</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('Rapat/tambahrapat'); ?>" method="post">
                    <div class="form-group">
                        <label for="tanggalrapat">Tanggal Pelaksanaan Rapat:</label>
                        <input type="date" name="tanggalrapat" id="tanggalrapat" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="jumlahpeserta" class="form-label">Jumlah Peserta Rapat:</label>
                        <input class="form-control" type="text" name="jumlahpeserta" id="jumlahpeserta" required>
                    </div>
                    <div class="mb-3">
                        <label for="topikrapat" class="form-label">Topik Rapat:</label>
                        <textarea class="form-control" type="text" name="topikrapat" id="topikrapat"
                            style="height: 150px" required></textarea>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                <button type="submit" name="tambahrapat" class="btn btn-primary">Tambah Data</button>
            </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambahkomentar">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="<?= base_url('Rapat/Tambahkomentar'); ?>" method="post">
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
                    <button type="submit" name="TambahKomentar" class="btn btn-primary">Simpan Komentar</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Hapus data bisnis -->
<div class="modal fade" id="modalHapusrapat">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                Apakah anda yakin ingin menghapus data?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="btnHapusrapat">Yakin</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalsetnulltindak">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                Apakah anda yakin ingin menghapus data?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btnsetnulltindak">Yakin</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const btnSetNulls = document.querySelectorAll('#btn-setnulltindak');

        btnSetNulls.forEach(function (btn) {
            btn.addEventListener('click', function () {
                // Retrieve the ID from the data-id attribute
                const id = this.getAttribute('data-id');

                // Show the confirmation modal
                if (confirm("Apakah Anda yakin hendak menghapus data nilai dan keterangan ini?")) {
                    // Redirect to the delete route with the ID as a parameter
                    window.location.href = "/rapat/setNullKolomTindak/" + id;
                }
            });
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Adjust the selector to match the correct button ID or use class for better targeting
        const btnSetNulls = document.querySelectorAll('#btnsetnulllanjut');  // Corrected ID

        btnSetNulls.forEach(function (btn) {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-id');

                // Confirmation before redirection
                if (confirm("Apakah Anda yakin hendak menghapus data nilai dan keterangan ini?")) {
                    window.location.href = "/Rapat/setNullKolomPenjelaslanjut/" + id;
                }
            });
        });
    });
</script>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script>
    const GLOBAL_SUBKATEGORI = '<?= $subkategori ?? '' ?>';
    const GLOBAL_KODEBPR = '<?= $kodebpr ?? '' ?>';
    const GLOBAL_ACTIVE_PERIODE_ID = '<?= $activePeriodeId ?? '' ?>';
    const GLOBAL_CURRENT_USER_ID = '<?= session()->get('user_id') ?? '' ?>';

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
                url: '<?= base_url('Rapat/getKomentarByFaktorId'); ?>/' + Id,
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

                // Set hidden input id di modal (pastikan ID konsisten)
                $('#modalTambahkomentar').find('#id').val(Id);

                // Fetch dan display comments
                fetchAndDisplayComments(Id, kodebpr, periodeId);

                // Mark comments as read
                $.ajax({
                    url: '<?= base_url('Rapat/markUserCommentsAsRead'); ?>',
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
                    url: '<?= base_url('Rapat/getUnreadCommentCountForFactor'); ?>',
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
                    url: '<?= base_url('Rapat/getUnreadCommentCountForAllUsers'); ?>',
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

        // Uncomment untuk polling otomatis setiap 10 detik
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
        padding: 25px;
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

    .btn-success {
        background-color: #ffffff;
        border-color: #28a745;
        border-radius: 25px;
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
    }

    .btn-primarys {
        background-color: #141863;
        border-color: #141863;
        color: #ffffff;
        border-radius: 25px;
        padding: 12px 30px;
        font-size: 0.9rem;
        font-weight: 600;
        transition: background-color 0.3s ease, border-color 0.3s ease, transform 0.3s ease;
        margin-right: 10px;
        margin-left: 0px;
        margin-bottom: 15px;
    }

    .btn-primarys:hover {
        background-color: #ffffff;
        border-color: #141863;
        color: #141863;
        transform: translateY(-2px);
        /* Slight lift on hover */
    }

    .btn-primary {
        background-color: #141863;
        border-color: #141863;
        border-radius: 25px;
        padding: 12px 30px;
        font-size: 0.9rem;
        font-weight: 600;
        transition: background-color 0.3s ease, border-color 0.3s ease, transform 0.3s ease;
        margin-right: 10px;
        margin-left: 0px;
        /* margin-bottom: 15px; */
    }

    .btn-primary:hover {
        background-color: #ffffff;
        border-color: #141863;
        color: #141863;
        transform: translateY(-2px);
        /* Slight lift on hover */
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
        margin-right: 5px;
    }

    .btn-secondary:hover {
        background-color: #ffffff;
        border-color: #343a40;
        color: #343a40;
        transform: translateY(-2px);
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

    .custom-badge-container {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: 15px;
    }

    .approval-card {
        width: 75%;
        left: 6px;
        margin-top: 1px;
        height: 95px;
        border-radius: 15px;
        overflow: hidden;
    }

    .approval-card-body {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 15px;
        height: 100%;
    }

    .approvaldir-card {
        position: absolute;
        right: 16px;
        width: 75%;
        margin-top: 1px;
        height: 95px;
        border-radius: 15px;
        overflow: hidden;
    }

    .approvaldir-card-body {
        display: flex;
        flex-direction: column;
        justify-content: right;
        padding: 15px;
        height: 100%;
    }

    .approval-badge-container {
        text-align: center;
        margin-bottom: 6px;
    }

    .approval-badge {
        background-color: #343a40;
        color: #ffffff;
        font-size: 0.8em;
        font-weight: 500;
        padding: 5px 12px;
        border-radius: 5px;
        display: inline-block;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .approval-buttons-container {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 9px;
    }

    .approval-btn {
        flex: 1;
        max-width: 50%;
        padding: 8px 15px;
        font-size: 0.95em;
        font-weight: 500;
        border-radius: 8px;
        text-align: center;
        text-decoration: none;
        white-space: nowrap;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: all 0.2s ease-in-out;
    }

    .approval-btn-approve {
        background-color: #28a745;
        border-color: #28a745;
        color: #fff;
    }

    .approval-btn-approve:hover {
        background-color: #218838;
        border-color: #1e7e34;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .approval-btn-reject {
        background-color: #dc3545;
        border-color: #dc3545;
        color: #fff;
    }

    .approval-btn-reject:hover {
        background-color: #c82333;
        border-color: #bd2130;
        transform: translateY(-1px);
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