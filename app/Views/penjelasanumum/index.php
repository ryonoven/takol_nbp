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

            <h1 class="h3 mb-4 text-gray-800 text-center"><?= $judul; ?><br>(E0100)</h1>

            <form id="tambahPenjelasForm">
                <input type="hidden" name="user_id" id="user_id" value="<?= esc($userId ?? '-') ?>">
                <input type="hidden" name="fullname" id="fullname" value="<?= esc($fullname ?? '-') ?>">

                <div class="form-group mb-3">
                    <label for="namabpr">Nama BPR:</label>
                    <input type="text" name="namabpr" id="namabpr" class="form-control" style="height: 45px"
                        value="<?= esc($penjelasanumum[0]['namabpr'] ?? '') ?>" required>
                </div>

                <div class="form-group mb-3">
                    <label for="alamat">Alamat BPR:</label>
                    <input type="text" name="alamat" id="alamat" class="form-control" style="height: 45px"
                        value="<?= esc($penjelasanumum[0]['alamat'] ?? '') ?>" required>
                </div>

                <div class="form-group mb-3">
                    <label for="nomor">Nomor Telepon BPR:</label>
                    <input type="text" name="nomor" id="nomor" class="form-control" style="height: 45px"
                        value="<?= esc($penjelasanumum[0]['nomor'] ?? '') ?>" required>
                </div>

                <div class="form-group mb-3">
                    <label for="penjelasan">Penjelasan Umum:</label>
                    <textarea name="penjelasan" id="penjelasan" class="form-control" style="height: 250px;"
                        required><?= esc($penjelasanumum[0]['penjelasan'] ?? '') ?></textarea>
                </div>

                <div class="form-group mb-3">
                    <label for="peringkatkomposit">Peringkat Komposit:</label>
                    <select name="peringkatkomposit" id="peringkatkomposit" class="form-control" style="height: 45px"
                        required>
                        <option value="" disabled <?= empty($penjelasanumum[0]['peringkatkomposit']) ? 'selected' : ''; ?>>-- Pilih Peringkat Komposit --</option>
                        <option value="1" <?= (isset($penjelasanumum[0]['peringkatkomposit']) && $penjelasanumum[0]['peringkatkomposit'] == '1') ? 'selected' : ''; ?>>1. Sangat Baik</option>
                        <option value="2" <?= (isset($penjelasanumum[0]['peringkatkomposit']) && $penjelasanumum[0]['peringkatkomposit'] == '2') ? 'selected' : ''; ?>>2. Baik</option>
                        <option value="3" <?= (isset($penjelasanumum[0]['peringkatkomposit']) && $penjelasanumum[0]['peringkatkomposit'] == '3') ? 'selected' : ''; ?>>3. Cukup</option>
                        <option value="4" <?= (isset($penjelasanumum[0]['peringkatkomposit']) && $penjelasanumum[0]['peringkatkomposit'] == '4') ? 'selected' : ''; ?>>4. Kurang Baik</option>
                        <option value="5" <?= (isset($penjelasanumum[0]['peringkatkomposit']) && $penjelasanumum[0]['peringkatkomposit'] == '5') ? 'selected' : ''; ?>>5. Buruk</option>
                    </select>
                </div>

                <div class="form-group mb-3">
                    <label for="penjelasankomposit">Penjelasan Peringkat Komposit:</label>
                    <textarea name="penjelasankomposit" id="penjelasankomposit" class="form-control"
                        style="height: 250px;"
                        required><?= esc($penjelasanumum[0]['penjelasankomposit'] ?? '') ?></textarea>
                </div>
                <div class="row" style="display: flex; justify-content: space-between;">
                    <?php if (($userInGroupAdmin || $userInGroupDekom) && !empty($accdekomData)): ?>
                        <div class="col-md-3">
                            <div class="card shadow-sm approval-card">
                                <div class="card-body approval-card-body">
                                    <div class="approval-badge-container"> <span class="badge approval-badge">Approval
                                            Komisaris
                                            Utama</span> </div>

                                    <div class="approval-buttons-container"> <a
                                            href="<?= base_url('Penjelasanumum/approveSemuaKom') ?>"
                                            class="btn btn-success approval-btn approval-btn-approve"
                                            onclick="return confirm('Apakah Anda yakin hendak melakukan approval?');">
                                            Setuju
                                        </a>
                                        <a href="<?= base_url('Penjelasanumum/unapproveSemuaKom') ?>"
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
                                    <div class="approval-badge-container"> <span class="badge approval-badge">Approval
                                            Direktur
                                            Utama</span> </div>

                                    <div class="approval-buttons-container"> <a
                                            href="<?= base_url('Penjelasanumum/approveSemuaDirut') ?>"
                                            class="btn btn-success approval-btn approval-btn-approve <?php echo (isset($canApprove) && !$canApprove) ? 'disabled-btn' : ''; ?>"
                                            onclick="return confirm('Apakah Anda yakin ingin melakukan approval?');">
                                            Setuju
                                        </a>
                                        <a href="<?= base_url('Penjelasanumum/unapproveSemuaDirut') ?>"
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

                <div class="d-flex justify-content-center gap-2 mb-5">
                    <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                        <button type="button" class="btn btn-secondary"
                            onclick="window.location.href='/penjelasanumum/exporttxtpenjelasanumum'"><i
                                class="fa fa-file-alt"></i> .TXT
                        </button>
                        <button type="submit" class="btn btn-primary">Simpan Data</button>
                    <?php endif; ?>
                    <?php if ($userInGroupDekom || $userInGroupDireksi || $userInGroupPE || $userInGroupAdmin): ?>
                        <td>
                            <?php
                            // Pastikan menggunakan ID yang konsisten
                            // $Id = session()->get('id'); // Sesuaikan dengan struktur data Anda
                            $subkategori = 'Penjelasanumum';
                            $currentUserId = session()->get('user_id');
                            $activePeriodeId = session()->get('active_periode');

                            // Hitung unread count dengan ID yang benar
                            $initialUnreadCount = $commentReadsModel->countUnreadCommentsForUserByFactor($subkategori, $kodebpr, $currentUserId, $activePeriodeId);

                            // echo "<!-- Debug: Id=$Id, unreadCount=$initialUnreadCount -->";
                            ?>
                            <div class="komentar-btn-wrapper">
                                <button type="button" data-toggle="modal" data-target="#modalTambahkomentar"
                                    id="btn-komentar-<?= $subkategori; ?>" class="btn btn-success shadow btn-sm"
                                    style="font-weight: 600;" data-id="<?= $subkategori; ?>" data-kodebpr="<?= $kodebpr; ?>"
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
                                    onclick="window.location.href='<?= base_url('Periodetransparansi') ?>'">
                                    << </button>
                                        <button style="background-color: #000; color: #fff;" type="button"
                                            class="btn btn-outline-primary btn-sm"
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
                                            onclick="window.location.href='<?= base_url('Tgjwbdir') ?>'">>></button>
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

<div class="modal fade" id="modalTambahkomentar">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="<?= base_url('Penjelasanumum/Tambahkomentar'); ?>" method="post">
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

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

<script>
    const GLOBAL_SUBKATEGORI = '<?= $subkategori ?? '' ?>';
    const GLOBAL_KODEBPR = '<?= $kodebpr ?? '' ?>';
    const GLOBAL_ACTIVE_PERIODE_ID = '<?= $activePeriodeId ?? '' ?>';
    const GLOBAL_CURRENT_USER_ID = '<?= session()->get('user_id') ?? '' ?>';

    $(document).ready(function () {
        $('#tambahPenjelasForm').submit(function (e) {
            e.preventDefault();
            var form = $(this);
            var formData = form.serialize();

            $.ajax({
                url: '<?= base_url('penjelasanumum/tambahpenjelasAjax') ?>',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        // Update semua field form dengan nilai yang baru dikirim
                        form.find('#namabpr').val(form.find('#namabpr').val());
                        form.find('#alamat').val(form.find('#alamat').val());
                        form.find('#nomor').val(form.find('#nomor').val());
                        form.find('#penjelasan').val(form.find('#penjelasan').val());
                        form.find('#peringkatkomposit').val(form.find('#peringkatkomposit').val());
                        form.find('#penjelasankomposit').val(form.find('#penjelasankomposit').val());

                        // Tampilkan pesan sukses
                        $('#ajax-response-message').html('<div class="alert alert-success">' + response.message + '</div>');

                        // Sembunyikan pesan setelah 3 detik
                        setTimeout(function () {
                            $('#ajax-response-message').fadeOut();
                        }, 3000);
                    } else {
                        $('#ajax-response-message').html('<div class="alert alert-danger">' + response.message + '</div>');
                    }
                },
                error: function (xhr) {
                    var errorMessage = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'Error while processing the request.';
                    $('#ajax-response-message').html('<div class="alert alert-danger">' + errorMessage + '</div>');
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
                url: '<?= base_url('Penjelasanumum/getKomentarByFaktorId'); ?>/' + Id,
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
                    url: '<?= base_url('Penjelasanumum/markUserCommentsAsRead'); ?>',
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
                    url: '<?= base_url('Penjelasanumum/getUnreadCommentCountForFactor'); ?>',
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
                    url: '<?= base_url('Penjelasanumum/getUnreadCommentCountForAllUsers'); ?>',
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
        /* Light gray background */
    }

    .card {
        border-radius: 15px;
        /* Rounded corners for the card */
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        /* Subtle shadow for depth */
        margin-top: 30px;
        margin-bottom: 30px;
    }

    .card-body {
        padding: 40px;
        /* More padding inside the card */
    }

    .alert-info-custom {
        background-color: #e0f7fa;
        /* Light blue background for the info alert */
        color: #007bb5;
        /* Darker blue text */
        border-color: #b2ebf2;
        /* Border color */
        border-radius: 10px;
        padding: 15px 20px;
        display: flex;
        align-items: center;
        font-size: 1.1rem;
        margin-bottom: 25px;
    }

    .alert-info-custom strong {
        color: #0056b3;
        /* Even darker blue for strong text */
    }

    .alert-info-custom .fas {
        margin-right: 10px;
        font-size: 1.5rem;
    }

    .h3.mb-4.text-gray-800.text-center {
        color: #343a40;
        /* Darker text for heading */
        font-weight: 700;
        /* Bolder heading */
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
        /* Blue underline */
        border-radius: 5px;
    }

    .form-group label {
        font-weight: 600;
        /* Bolder labels */
        color: #495057;
        /* Slightly darker label text */
        margin-bottom: 8px;
    }

    .form-control {
        border-radius: 8px;
        /* Rounded input fields */
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
        /* Allow vertical resizing */
    }

    .btn-primary {
        background-color: #141863;
        /* Blue button */
        border-color: #141863;
        border-radius: 25px;
        /* Pill-shaped button */
        padding: 12px 30px;
        font-size: 1.1rem;
        font-weight: 600;
        transition: background-color 0.3s ease, border-color 0.3s ease, transform 0.3s ease;
        /* margin-right: 15px; */
        margin-left: 15px;
    }

    .btn-primary:hover {
        background-color: #ffffff;
        border-color: #141863;
        transform: translateY(-2px);
        color: #141863;
        /* Slight lift on hover */
    }

    .btn-secondary {
        background-color: #343a40;
        border-color: #343a40;
        border-radius: 55px;
        padding: 12px 30px;
        font-size: 1.0rem;
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

    /* No specific positioning CSS needed for custom-badge-left and custom-badge-right
   because justify-content: space-between on the parent handles it. */
</style>