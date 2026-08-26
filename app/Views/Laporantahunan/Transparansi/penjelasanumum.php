<div id="toast-wrap" style="position:fixed;top:20px;right:20px;z-index:99999;min-width:290px;pointer-events:none;">
</div>

<div class="container-fluid">
    <a href="<?= base_url('ShowTransparansi') ?>" class="btn-kembali mb-3">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>

    <?php if (session()->getFlashdata('message')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <strong><?= esc(session()->getFlashdata('message')) ?></strong>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('err')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <strong><?= esc(session()->getFlashdata('err')) ?></strong>
        </div>
    <?php endif; ?>

    <?php
    $allAccdekomApproved = !empty($accdekomData) && array_reduce($accdekomData, fn($c, $i) => $c && $i['accdekom'] == 1, true);
    $allAccdirutApproved = !empty($accdirutData) && array_reduce($accdirutData, fn($c, $i) => $c && $i['is_approved'] == 1, true);
    $lastDekomAt = $allAccdekomApproved && !empty($accdekomData)
        ? date('d/m/Y H:i', strtotime($accdekomData[count($accdekomData) - 1]['accdekom_at']))
        : '';
    $lastDirutAt = $allAccdirutApproved && !empty($accdirutData)
        ? date('d/m/Y H:i', strtotime($accdirutData[0]['approved_at']))
        : '';
    $canEdit = $canEdit ?? false;
    $row0 = $penjelasanumum[0] ?? [];
    ?>

    <div class="card card-body mb-3 shadow-sm">
        <div class="page-header">
            <div class="approval-badges-row mb-3">
                <div class="approval-status-badge <?= $allAccdekomApproved ? 'badge-status-success' : 'badge-status-secondary' ?>"
                    id="badge-dekom">
                    <i class="fas <?= $allAccdekomApproved ? 'fa-check-circle' : 'fa-clock' ?>"></i>
                    <div>
                        <span class="approval-status-label">Komisaris Utama</span><br>
                        <span class="approval-status-sub">
                            <?= $allAccdekomApproved ? 'Disetujui &middot; ' . $lastDekomAt : 'Belum disetujui seluruhnya' ?>
                        </span>
                    </div>
                </div>
                <div class="approval-status-badge <?= $allAccdirutApproved ? 'badge-status-success' : 'badge-status-secondary' ?>"
                    id="badge-dirut">
                    <i class="fas <?= $allAccdirutApproved ? 'fa-check-circle' : 'fa-clock' ?>"></i>
                    <div>
                        <span class="approval-status-label">Direktur Utama</span><br>
                        <span class="approval-status-sub">
                            <?= $allAccdirutApproved ? 'Disetujui &middot; ' . $lastDirutAt : 'Belum disetujui seluruhnya' ?>
                        </span>
                    </div>
                </div>
            </div>
            <h1 class="page-title"><?= esc($judul) ?></h1>
            <small style="font-size:.7em;opacity:.6;">(E0100)</small>
            <div class="page-subtitle">Tahun <?= esc($periodeDetail['tahun']) ?></div>
            <span class="badge-namabpr"><?= esc($bprData['namabpr'] ?? 'Nama BPR') ?></span>
        </div>
    </div>

    <?php if ($canEdit): ?>
        <div id="autosave-bar" class="autosave-bar as-idle mb-3 d-flex justify-content-between align-items-center flex-wrap"
            style="gap:8px;">
            <div class="d-flex align-items-center" style="gap:8px;">
                <i class="fas fa-circle as-dot-idle as-icon"></i>
                <span class="as-text">Siap menyimpan</span>
                <span class="as-hint">&middot; Setiap perubahan tersimpan otomatis</span>
            </div>
            <button type="button" id="btn-sync-prev" class="btn-sync-year"
                title="Salin data dari tahun sebelumnya ke form ini" disabled>
                <i class="fas fa-sync-alt btn-sync-icon"></i>
                <span class="btn-sync-label">Sinkron pada laporan tahun:</span>
                <span class="btn-sync-badge" id="sync-year-badge">—</span>
            </button>
        </div>
    <?php else: ?>
        <div class="autosave-bar as-idle mb-3">
            <i class="fas fa-eye as-icon"></i>
            <span class="as-text">Mode baca</span>
            <span class="as-hint">&middot; Anda tidak memiliki akses untuk mengubah data ini</span>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center" style="background:var(--grad);">
            <h5 class="m-0 font-weight-bold text-white">
                <i class="fas fa-edit"></i> Data Penjelasan Umum
            </h5>
            <?php if (($userInGroupAdmin || $userInGroupDekom || $userInGroupDireksi || $userInGroupPE || $userInGroupDekom2 || $userInGroupDireksi2) ?? false): ?>
                <div class="position-relative d-inline-block">
                    <button type="button" class="btn-komentar-global-btn" id="btn-komentar-global" title="Komentar">
                        <i class="fas fa-comment-dots"></i>
                        <span class="btn-komentar-ripple"></span>
                    </button>
                    <span id="notif-global" class="notif-badge" style="display:none;">0</span>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-body p-4">
            <form id="puForm" autocomplete="off">

                <div class="pu-field">
                    <label class="pu-label" for="namabpr">Nama BPR <span class="text-danger">*</span></label>
                    <input type="text" id="namabpr" name="namabpr" class="pu-input" placeholder="Nama BPR"
                        value="<?= esc($row0['namabpr'] ?? '') ?>" <?= $canEdit ? '' : 'readonly' ?>>
                </div>

                <div class="pu-field">
                    <label class="pu-label" for="alamat">Alamat BPR <span class="text-danger">*</span></label>
                    <input type="text" id="alamat" name="alamat" class="pu-input" placeholder="Alamat lengkap BPR"
                        value="<?= esc($row0['alamat'] ?? '') ?>" <?= $canEdit ? '' : 'readonly' ?>>
                </div>

                <div class="pu-field">
                    <label class="pu-label" for="nomor">Nomor Telepon <span class="text-danger">*</span></label>
                    <input type="text" id="nomor" name="nomor" class="pu-input" placeholder="(021) 000-0000"
                        value="<?= esc($row0['nomor'] ?? '') ?>" <?= $canEdit ? '' : 'readonly' ?>>
                </div>

                <div class="pu-field">
                    <label class="pu-label" for="penjelasan">Penjelasan Umum <span class="text-danger">*</span></label>
                    <textarea id="penjelasan" name="penjelasan" class="pu-textarea"
                        placeholder="Tuliskan penjelasan umum BPR..." style="min-height:250px;" <?= $canEdit ? '' : 'readonly' ?>><?= esc($row0['penjelasan'] ?? '') ?></textarea>
                    <div class="pu-char-hint" id="charPenjelasan">0 / 2000</div>
                </div>

                <div class="pu-field">
                    <label class="pu-label" for="peringkatkomposit">Peringkat Komposit <span
                            class="text-danger">*</span></label>
                    <?php if ($canEdit): ?>
                        <select id="peringkatkomposit" name="peringkatkomposit" class="pu-select">
                            <option value="" disabled <?= empty($row0['peringkatkomposit']) ? 'selected' : '' ?>>— Pilih
                                Peringkat —</option>
                            <?php foreach (['1' => '1 — Sangat Baik', '2' => '2 — Baik', '3' => '3 — Cukup', '4' => '4 — Kurang Baik', '5' => '5 — Buruk'] as $v => $lbl): ?>
                                <option value="<?= $v ?>" <?= (isset($row0['peringkatkomposit']) && $row0['peringkatkomposit'] == $v) ? 'selected' : '' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <?php
                        $pkLabels = ['1' => '1 — Sangat Baik', '2' => '2 — Baik', '3' => '3 — Cukup', '4' => '4 — Kurang Baik', '5' => '5 — Buruk'];
                        $pkVal = $row0['peringkatkomposit'] ?? '';
                        ?>
                        <input type="text" class="pu-input" value="<?= esc($pkLabels[$pkVal] ?? $pkVal) ?>" readonly>
                        <input type="hidden" id="peringkatkomposit" name="peringkatkomposit" value="<?= esc($pkVal) ?>">
                    <?php endif; ?>
                </div>

                <div class="pu-field">
                    <label class="pu-label" for="penjelasankomposit">Penjelasan Peringkat Komposit <span
                            class="text-danger">*</span></label>
                    <textarea id="penjelasankomposit" name="penjelasankomposit" class="pu-textarea"
                        placeholder="Tuliskan penjelasan peringkat komposit..." style="min-height:250px;" <?= $canEdit ? '' : 'readonly' ?>><?= esc($row0['penjelasankomposit'] ?? '') ?></textarea>
                    <div class="pu-char-hint" id="charKomposit">0 / 2000</div>
                </div>

                <div id="form-err" class="alert alert-danger mt-2" style="display:none;"></div>

            </form>
        </div>
    </div>

    <div class="approval-actions-row mt-3">
        <?php if (($userInGroupAdmin ?? false) || ($userInGroupDekom ?? false)): ?>
            <div class="action-card card shadow-sm">
                <div class="card-body py-2 text-center">
                    <small class="d-block font-weight-bold text-dark mb-2">
                        <i class="fas fa-user-tie text-success"></i> Komisaris Utama
                    </small>
                    <div class="d-flex justify-content-center flex-wrap" style="gap:4px;">
                        <button type="button" class="btn btn-primary btn-sm btn-approve-dekom">
                            <i class="fas fa-check-double"></i> Setujui
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm btn-unapprove-dekom">
                            <i class="fas fa-ban"></i> Batal
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <?php if (($userInGroupAdmin ?? false) || ($userInGroupDireksi ?? false)): ?>
            <div class="action-card card shadow-sm <?= (!($canApprove ?? false)) ? 'blk-disabled' : '' ?>">
                <div class="card-body py-2 text-center">
                    <small class="d-block font-weight-bold text-dark mb-2">
                        <i class="fas fa-user-shield text-primary"></i> Direktur Utama
                    </small>
                    <?php if (!($canApprove ?? false)): ?>
                        <small class="text-muted d-block mb-1" style="font-size:10px;">
                            <i class="fas fa-lock"></i> Komisaris harus setuju dulu
                        </small>
                    <?php endif; ?>
                    <div class="d-flex justify-content-center flex-wrap" style="gap:4px;">
                        <button type="button" class="btn btn-primary btn-sm btn-approve-dirut">
                            <i class="fas fa-check-double"></i> Setujui
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm btn-unapprove-dirut">
                            <i class="fas fa-ban"></i> Batal
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="cardpilihfaktor mb-4 mt-4">
        <div class="cardpilihfaktor-header">
            <h6>Pilih Halaman</h6>
        </div>
        <div class="cardpilihfaktor-body">
            <div class="nav-pages-wrap">
                <?php
                $pages = [
                    ['url' => 'Penjelasanumum', 'label' => '1'],
                    ['url' => 'Tgjwbdir', 'label' => '2'],
                    ['url' => 'Tgjwbdekom', 'label' => '3'],
                    ['url' => 'Tgjwbkomite', 'label' => '4'],
                    ['url' => 'Strukturkomite', 'label' => '5'],
                    ['url' => 'Sahamdirdekom', 'label' => '6'],
                    ['url' => 'Shmusahadirdekom', 'label' => '7'],
                    ['url' => 'Shmdirdekomlain', 'label' => '8'],
                    ['url' => 'Keuangandirdekompshm', 'label' => '9'],
                    ['url' => 'Keluargadirdekompshm', 'label' => '10'],
                    ['url' => 'Paketkebijakandirdekom', 'label' => '11'],
                    ['url' => 'Rasiogaji', 'label' => '12'],
                    ['url' => 'Rapat', 'label' => '13'],
                    ['url' => 'Kehadirandekom', 'label' => '14'],
                    ['url' => 'Fraudinternal', 'label' => '15'],
                    ['url' => 'Masalahhukum', 'label' => '16'],
                    ['url' => 'Transaksikepentingan', 'label' => '17'],
                    ['url' => 'Danasosial', 'label' => '18'],
                ];
                foreach ($pages as $p): ?>
                    <a href="<?= base_url($p['url']) ?>"
                        class="nav-page-btn <?= $p['url'] === 'Penjelasanumum' ? 'active' : '' ?>"><?= $p['label'] ?></a>
                <?php endforeach; ?>
                <a href="<?= base_url('ShowTransparansi') ?>" class="nav-page-btn">All</a>
            </div>
            <div style="text-align:center;padding:4px 0 8px;">
                <a href="<?= base_url('ShowTransparansi') ?>"
                    style="font-size:12px;color:var(--gray-400);font-weight:600;">Kembali ke halaman transparansi</a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalKomentar" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content komentar-modal-content">
            <div class="modal-header komentar-modal-header">
                <div class="komentar-modal-title-wrap">
                    <div class="komentar-modal-icon-wrap"><i class="fas fa-comments"></i></div>
                    <div>
                        <div class="komentar-modal-title">Komentar — Penjelasan Umum</div>
                    </div>
                </div>
                <button type="button" class="komentar-close-btn" data-dismiss="modal"><i
                        class="fas fa-times"></i></button>
            </div>
            <div class="modal-body komentar-modal-body">
                <div class="komentar-list-label"><i class="fas fa-history"></i> Riwayat Komentar</div>
                <div id="komentar-list-wrapper" class="komentar-list-wrapper">
                    <div id="komentar-list">
                        <div class="komentar-loading"><i class="fas fa-spinner fa-spin"></i> Memuat...</div>
                    </div>
                </div>
                <?php if (($userInGroupAdmin || $userInGroupDekom || $userInGroupDireksi || $userInGroupPE || $userInGroupDekom2 || $userInGroupDireksi2) ?? false): ?>
                    <div class="komentar-input-wrap">
                        <div class="komentar-input-label"><i class="fas fa-pen-alt"></i> Tulis Komentar</div>
                        <textarea id="k-teks" class="komentar-textarea" rows="3"
                            placeholder="Tuliskan komentar, catatan, atau arahan Anda..." maxlength="1000"></textarea>
                        <div class="komentar-input-footer">
                            <span class="komentar-hint"><i class="fas fa-keyboard"></i> Ctrl+Enter untuk kirim</span>
                            <span id="k-char-count" class="komentar-char">0 / 1000</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer komentar-modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal"><i class="fas fa-times"></i>
                    Tutup</button>
                <?php if (($userInGroupAdmin || $userInGroupDekom || $userInGroupDireksi || $userInGroupPE || $userInGroupDekom2 || $userInGroupDireksi2) ?? false): ?>
                    <button type="button" class="btn btn-primary btn-sm" id="btn-kirim-komentar">
                        <i class="fas fa-paper-plane"></i> Kirim
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$_canEdit = $canEdit ? 'true' : 'false';
$_base = rtrim(base_url(), '/');
$_csrf = csrf_token();
$_hash = csrf_hash();
$_userId = (int) ($userId ?? 0);
$_fullname = addslashes($fullname ?? 'Unknown');
$_periodeId = (int) ($periodeId ?? 0);
$_kodebpr = addslashes($kodebpr ?? '');
$_canKomentar = (($userInGroupAdmin || $userInGroupDekom || $userInGroupDireksi || $userInGroupPE || $userInGroupDekom2 || $userInGroupDireksi2) ?? false) ? 'true' : 'false';
?>
<script>
    (function wait() {
        if (typeof jQuery === 'undefined') return setTimeout(wait, 50);
        jQuery(document).ready(init);
    })();

    function init($) {
        var BASE = '<?= $_base ?>', CSRF = '<?= $_csrf ?>', HASH = '<?= $_hash ?>',
            UID = <?= $_userId ?>, FNAME = '<?= $_fullname ?>',
            PID = <?= $_periodeId ?>, KBPR = '<?= $_kodebpr ?>',
            CAN_EDIT = <?= $_canEdit ?>;

        function esc(s) { return s == null ? '' : String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }
        function debounce(fn, ms) { var t; return function () { var a = arguments, c = this; clearTimeout(t); t = setTimeout(function () { fn.apply(c, a); }, ms); }; }
        function throttle(fn, ms) { var busy = false; return function () { if (busy) return; busy = true; fn.apply(this, arguments); setTimeout(function () { busy = false; }, ms); }; }
        function toast(msg, type) {
            type = type || 'success';
            var icons = { success: 'check-circle', error: 'exclamation-circle', info: 'info-circle' };
            var el = $('<div class="t-item t-' + type + '"><i class="fas fa-' + icons[type] + ' fa-lg"></i><div>' + msg + '</div></div>');
            $('#toast-wrap').append(el);
            setTimeout(function () { el.fadeOut(400, function () { $(this).remove(); }); }, 3500);
        }
        function csrf() { var d = {}; d[CSRF] = HASH; return d; }
        function updateCsrf(res) {
            if (res && res.csrf_hash) HASH = res.csrf_hash;
            var m = document.cookie.match(/ci_csrf_token=([^;]+)/); if (m) HASH = decodeURIComponent(m[1]);
        }
        function parseErr(xhr) { try { var r = JSON.parse(xhr.responseText); if (r && r.message) return r.message; } catch (e) { } return null; }
        function doPost(url, data, cb, errCb) {
            $.ajax({
                url: BASE + '/' + url, method: 'POST', dataType: 'json',
                data: $.extend({}, csrf(), data),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) { updateCsrf(res); cb(res); },
                error: function (xhr) {
                    var m = parseErr(xhr) || 'Terjadi kesalahan server.';
                    if (xhr.status === 403) { m = 'Sesi keamanan kedaluwarsa. Halaman akan di-refresh...'; setTimeout(function () { location.reload(); }, 2000); }
                    errCb ? errCb(m) : toast(m, 'error');
                }
            });
        }
        function btnLoading(btn) {
            var ori = btn.html(); btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
            return function () { btn.html(ori).prop('disabled', false); };
        }
        function timeAgo(s) {
            if (!s) return '-';
            var d = new Date(s.replace(' ', 'T')), diff = Math.floor((Date.now() - d.getTime()) / 60000);
            if (isNaN(diff) || diff < 1) return 'Baru saja'; if (diff < 60) return diff + ' menit lalu'; if (diff < 1440) return Math.floor(diff / 60) + ' jam lalu';
            return ('0' + d.getDate()).slice(-2) + '/' + ('0' + (d.getMonth() + 1)).slice(-2) + '/' + d.getFullYear() + ' ' + ('0' + d.getHours()).slice(-2) + ':' + ('0' + d.getMinutes()).slice(-2);
        }
        function updateNotifBadge(count) {
            var btn = $('#btn-komentar-global'), badge = $('#notif-global');
            if (count > 0) { badge.text(count).show(); btn.addClass('has-unread'); }
            else { badge.hide(); btn.removeClass('has-unread'); }
        }

        var $bar = $('#autosave-bar');
        function setSaveStatus(state, text) {
            if (!$bar.length) return;
            $bar.attr('class', 'autosave-bar as-' + state);
            var icons = { idle: 'fa-circle as-dot-idle', saving: 'fa-spinner fa-spin', saved: 'fa-check-circle', error: 'fa-exclamation-triangle' };
            var texts = { idle: 'Siap menyimpan', saving: 'Menyimpan...', saved: 'Tersimpan otomatis', error: 'Gagal menyimpan' };
            $bar.find('.as-icon').attr('class', 'fas ' + (icons[state] || 'fa-circle') + ' as-icon');
            $bar.find('.as-text').text(text || texts[state] || '');
        }

        var _saveXhr = null, _debTimer = null;
        function triggerDebounce() {
            if (!CAN_EDIT) return;
            setSaveStatus('saving', 'Mengetik...');
            clearTimeout(_debTimer);
            _debTimer = setTimeout(doSave, 700);
        }
        function doSave() {
            setSaveStatus('saving', 'Menyimpan...');
            if (_saveXhr) _saveXhr.abort();
            var fields = ['namabpr', 'alamat', 'nomor', 'penjelasan', 'peringkatkomposit', 'penjelasankomposit'];
            var valid = true;
            fields.forEach(function (id) { var el = document.getElementById(id); if (el) el.classList.remove('is-invalid'); });
            fields.forEach(function (id) { var el = document.getElementById(id); if (el && !el.value.trim()) { el.classList.add('is-invalid'); valid = false; } });
            if (!valid) { setSaveStatus('error', 'Field wajib diisi'); toast('Harap lengkapi semua field yang wajib diisi.', 'error'); return; }
            var data = $.extend({}, csrf());
            fields.forEach(function (id) { var el = document.getElementById(id); if (el) data[id] = el.value; });
            _saveXhr = $.ajax({
                url: BASE + '/Penjelasanumum/tambahpenjelasAjax', method: 'POST', dataType: 'json', data: data,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) {
                    updateCsrf(res);
                    if (res.status === 'success') { setSaveStatus('saved', 'Tersimpan otomatis'); toast('Tersimpan otomatis', 'success'); setTimeout(function () { setSaveStatus('idle', 'Siap menyimpan'); }, 3500); }
                    else { setSaveStatus('error', 'Gagal menyimpan'); toast(res.message || 'Gagal menyimpan', 'error'); }
                },
                error: function (xhr) { if (xhr.statusText === 'abort') return; setSaveStatus('error', 'Error'); toast('Terjadi kesalahan koneksi', 'error'); }
            });
        }
        if (CAN_EDIT) { $('#puForm').on('input change', 'input,textarea,select', triggerDebounce); }

        function refreshApprovalStatus() {
            $.ajax({
                url: BASE + '/Penjelasanumum/getApprovalStatus', method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) {
                    if (!res || res.status !== 'success') return;
                    updateBadge('#badge-dekom', 'Komisaris Utama', res.dekom_approved, res.dekom_approved_at);
                    updateBadge('#badge-dirut', 'Direktur Utama', res.dirut_approved, res.dirut_approved_at);
                }
            });
        }
        function updateBadge(sel, label, approved, at) {
            var bd = $(sel);
            bd.removeClass('badge-status-secondary badge-status-success').addClass(approved ? 'badge-status-success' : 'badge-status-secondary');
            var icon = approved ? 'fa-check-circle' : 'fa-clock';
            var sub = approved ? 'Disetujui · ' + timeAgo(at) : 'Belum disetujui seluruhnya';
            bd.html('<i class="fas ' + icon + '"></i><div><span class="approval-status-label">' + label + '</span><br><span class="approval-status-sub">' + sub + '</span></div>');
        }

        function loadKomentarList(scrollToBottom) {
            var list = $('#komentar-list');
            list.html('<div class="komentar-loading"><i class="fas fa-spinner fa-spin"></i> Memuat...</div>');
            $.ajax({
                url: BASE + '/Penjelasanumum/getKomentar', method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) {
                    var items = Array.isArray(res) ? res : [];
                    if (!items.length) { list.html('<div class="komentar-empty"><i class="fas fa-comment-slash fa-2x mb-2 d-block"></i>Belum ada komentar.</div>'); return; }
                    var html = '';
                    $.each(items, function (i, k) {
                        var own = parseInt(k.user_id) === UID;
                        html += '<div class="km-item' + (own ? ' km-own' : '') + '"><div class="km-meta">'
                            + '<span class="km-author"><i class="fas fa-user-circle"></i> ' + esc(k.fullname || '?') + (own ? ' <span class="km-you">Anda</span>' : '') + ' </span>'
                            + '<span class="km-time"><i class="fas fa-clock"></i> ' + timeAgo(k.created_at) + '</span>'
                            + '</div><p class="km-text">' + esc(k.komentar) + '</p></div>';
                    });
                    list.html(html);
                    if (scrollToBottom) { var w = document.getElementById('komentar-list-wrapper'); if (w) setTimeout(function () { w.scrollTop = w.scrollHeight; }, 100); }
                },
                error: function () { list.html('<div class="komentar-empty text-danger"><i class="fas fa-exclamation-circle"></i> Gagal memuat komentar.</div>'); }
            });
        }
        function pollUnreadCount() {
            $.ajax({
                url: BASE + '/Penjelasanumum/getUnreadCounts', method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) { if (res && res.status === 'success') updateNotifBadge(parseInt(res.count) || 0); }
            });
        }
        function markAllRead() {
            doPost('Penjelasanumum/markKomentarAsRead', {}, function (res) {
                if (res && res.status === 'success') updateNotifBadge(0);
            });
        }

        function bulkApproval(url, msg) {
            if (!confirm(msg)) return;
            doPost(url, {}, function (res) {
                toast(res.message, res.status === 'success' ? 'success' : 'error');
                refreshApprovalStatus();
            });
        }

        (function initSync() {
            if (!CAN_EDIT) return;

            var $btn = $('#btn-sync-prev'), $badge = $('#sync-year-badge');
            if (!$btn.length) return;

            $.ajax({
                url: BASE + '/Penjelasanumum/checkPrevYear',
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) {
                    if (res && res.hasPrev) {
                        $badge.text(res.prevTahun);
                        $btn.prop('disabled', false);
                    } else {
                        var yr = res && res.prevTahun ? res.prevTahun : 'N/A';
                        $badge.text(yr);
                        $btn.prop('disabled', true)
                            .attr('title', 'Tidak ada data tahun ' + yr);
                    }
                },
                error: function () { $btn.prop('disabled', true); }
            });

            $btn.on('click', throttle(function () {
                if (!confirm('Salin semua data dari tahun sebelumnya ke form tahun ini?\n\nData yang sudah ada di form ini akan ditimpa.'))
                    return;

                var restore = btnLoading($btn);
                $btn.find('.btn-sync-label').text('Menyinkronkan...');

                doPost('Penjelasanumum/syncFromPrevYear', {}, function (res) {
                    restore();
                    $btn.find('.btn-sync-label').text('Sinkron Tahun Lalu');

                    if (res.status === 'success' && res.data) {
                        var d = res.data;
                        var fieldMap = {
                            namabpr: d.namabpr, alamat: d.alamat,
                            nomor: d.nomor, penjelasan: d.penjelasan,
                            peringkatkomposit: d.peringkatkomposit,
                            penjelasankomposit: d.penjelasankomposit
                        };
                        $.each(fieldMap, function (id, val) {
                            var el = document.getElementById(id);
                            if (!el) return;
                            if (el.tagName === 'SELECT') {
                                $(el).val(val);
                            } else {
                                el.value = val || '';
                            }
                            $(el).trigger('input');
                        });

                        toast(res.message, 'success');
                        setSaveStatus('saved', 'Data tersinkron');
                        setTimeout(function () { setSaveStatus('idle', 'Siap menyimpan'); }, 3500);

                    } else if (res.status === 'no_prev') {
                        $btn.prop('disabled', true);
                        toast(res.message || 'Tidak ada data tahun sebelumnya.', 'info');

                    } else {
                        toast(res.message || 'Sinkronisasi gagal.', 'error');
                    }
                }, function (m) {
                    restore();
                    $btn.find('.btn-sync-label').text('Sinkron Tahun Lalu');
                    toast(m, 'error');
                });
            }, 3000));
        }());

        var execKomentar = debounce(function () {
            var teks = $.trim($('#k-teks').val());
            if (!teks) { toast('Komentar tidak boleh kosong.', 'error'); return; }
            if (teks.length < 3) { toast('Komentar minimal 3 karakter.', 'error'); return; }
            var restore = btnLoading($('#btn-kirim-komentar'));
            doPost('Penjelasanumum/tambahKomentar', { komentar: teks, fullname: FNAME }, function (res) {
                restore();
                if (res.status === 'success') {
                    $('#k-teks').val(''); $('#k-char-count').text('0 / 1000').removeClass('danger');
                    toast(res.message || 'Komentar terkirim'); loadKomentarList(true);
                } else { toast(res.message || 'Gagal mengirim komentar', 'error'); }
            }, function (m) { restore(); toast(m, 'error'); });
        }, 400);

        // ── EVENT BINDINGS ────────────────────────────────────────────────────
        $('#btn-komentar-global').on('click', function () { loadKomentarList(true); markAllRead(); $('#modalKomentar').modal('show'); });
        $('#btn-kirim-komentar').on('click', execKomentar);
        $('#k-teks')
            .on('keydown', function (e) { if (e.ctrlKey && e.key === 'Enter') { e.preventDefault(); execKomentar(); } })
            .on('input', function () { var l = $(this).val().length; var el = $('#k-char-count'); el.text(l + ' / 1000'); l > 950 ? el.addClass('danger') : el.removeClass('danger'); });

        $.each({
            '.btn-approve-dekom': ['Penjelasanumum/approveSemuaKom', 'Setujui semua data Penjelasan Umum sebagai Komisaris Utama?'],
            '.btn-unapprove-dekom': ['Penjelasanumum/unapproveSemuaKom', 'Batalkan semua persetujuan Komisaris Utama?'],
            '.btn-approve-dirut': ['Penjelasanumum/approveSemuaDirut', 'Setujui semua data Penjelasan Umum sebagai Direktur Utama?'],
            '.btn-unapprove-dirut': ['Penjelasanumum/unapproveSemuaDirut', 'Batalkan semua persetujuan Direktur Utama?']
        }, function (sel, cfg) {
            $(sel).on('click', throttle(function () { bulkApproval(cfg[0], cfg[1]); }, 2000));
        });

        $(document).on('hidden.bs.modal', '.modal', function () {
            $(this).find('.alert-danger').hide().text('');
        });

        function initCharCounter(id, hintId, max) {
            var ta = $('#' + id), hint = $('#' + hintId);
            if (!ta.length || !hint.length) return;
            function update() { var l = ta.val().length; hint.text(l + (max ? ' / ' + max : ' karakter')); hint.toggleClass('warn', max && l > max * .9).toggleClass('over', max && l >= max); }
            ta.on('input', debounce(update, 80)); update();
        }
        initCharCounter('penjelasan', 'charPenjelasan', 2000);
        initCharCounter('penjelasankomposit', 'charKomposit', 2000);
        pollUnreadCount();
        setInterval(function () { pollUnreadCount(); }, 10000);
    }
</script>

<style>
    /* @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap'); */
    :root {
        --brand: #4f46e5;
        --brand-light: #818cf8;
        --brand-dark: #3730a3;
        --brand-bg: #eef2ff;
        --success: #10b981;
        --success-bg: #d1fae5;
        --danger: #ef4444;
        --danger-bg: #fee2e2;
        --warning: #f59e0b;
        --warning-bg: #fef3c7;
        --gray-50: #f8fafc;
        --gray-100: #f1f5f9;
        --gray-200: #e2e8f0;
        --gray-400: #94a3b8;
        --gray-500: #64748b;
        --gray-700: #334155;
        --gray-800: #1e293b;
        --gray-900: #0f172a;
        --blue: #1f2aa8;
        --r-sm: 8px;
        --r: 12px;
        --r-lg: 16px;
        --sh-sm: 0 1px 3px rgba(0, 0, 0, .08);
        --sh: 0 4px 6px -1px rgba(0, 0, 0, .1), 0 2px 4px -1px rgba(0, 0, 0, .06);
        --sh-lg: 0 10px 25px -3px rgba(0, 0, 0, .1), 0 4px 6px -2px rgba(0, 0, 0, .05);
        --tr: all .2s cubic-bezier(.4, 0, .2, 1);
        --grad: linear-gradient(135deg, #141863 0%, #1f2aa8 100%);
    }

    * {
        font-family: 'Plus Jakarta Sans', sans-serif;
    }

    .page-header {
        text-align: center;
        padding: 28px 16px 20px;
        background: white;
        border-bottom: 1px solid var(--gray-100);
        position: relative;
        overflow: hidden;
    }

    .page-header::before {
        content: '';
        position: absolute;
        top: -60px;
        right: -60px;
        width: 180px;
        height: 180px;
        border-radius: 50%;
        background: radial-gradient(circle, #eef2ff, transparent 70%);
    }

    .page-title {
        font-size: clamp(1.05rem, 3.5vw, 1.5rem);
        font-weight: 800;
        color: var(--gray-900);
        margin-bottom: 6px;
        letter-spacing: -.5px;
        position: relative;
        z-index: 1;
    }

    .page-subtitle {
        font-size: .95rem;
        font-weight: 600;
        color: var(--gray-700);
        margin-bottom: 10px;
        position: relative;
        z-index: 1;
    }

    .badge-namabpr {
        display: inline-block;
        background: var(--grad);
        color: white;
        font-size: .9rem;
        font-weight: 700;
        padding: 5px 18px;
        border-radius: 100px;
        position: relative;
        z-index: 1;
        box-shadow: 0 4px 12px rgba(79, 70, 229, .3);
    }

    .approval-badges-row {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: center;
    }

    .approval-status-badge {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 14px;
        border-radius: var(--r);
        font-size: 12.5px;
        font-weight: 600;
        border: 1.5px solid transparent;
        transition: var(--tr);
    }

    .approval-status-badge i {
        font-size: 18px;
        flex-shrink: 0;
    }

    .approval-status-label {
        font-weight: 700;
        font-size: 12px;
    }

    .approval-status-sub {
        font-size: 11px;
        opacity: .8;
    }

    .badge-status-secondary {
        background: var(--gray-100);
        color: var(--gray-500);
        border-color: var(--gray-200);
    }

    .badge-status-success {
        background: var(--success-bg);
        color: #065f46;
        border-color: #6ee7b7;
    }

    .approval-actions-row {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        justify-content: center;
    }

    .action-card {
        border-radius: var(--r) !important;
        border: 1.5px solid var(--gray-200) !important;
        transition: var(--tr);
        overflow: hidden;
        min-width: 200px;
    }

    .action-card:hover {
        border-color: var(--brand-light) !important;
        box-shadow: 0 8px 24px rgba(79, 70, 229, .12) !important;
        transform: translateY(-2px);
    }

    .card-header {
        border-radius: var(--r-lg) var(--r-lg) 0 0 !important;
    }

    .btn {
        transition: var(--tr);
        font-weight: 600;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: var(--sh);
    }

    .btn:active {
        transform: scale(.96) !important;
    }

    .btn-sm {
        font-size: 12px;
    }

    .btn-kembali {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: white;
        border: 1.5px solid var(--gray-200);
        border-radius: var(--r-sm);
        padding: 7px 14px;
        font-size: .875rem;
        font-weight: 600;
        color: var(--gray-500);
        text-decoration: none;
        transition: var(--tr);
        margin: 12px 0 0 8px;
    }

    .btn-kembali:hover {
        background: var(--brand-bg);
        border-color: var(--brand-light);
        color: var(--brand);
        text-decoration: none;
    }

    /* AUTOSAVE BAR */
    .autosave-bar {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 14px;
        border-radius: var(--r-sm);
        font-size: 12.5px;
        font-weight: 600;
        border: 1.5px solid transparent;
        transition: all .3s ease;
    }

    .autosave-bar .as-icon {
        font-size: 13px;
        flex-shrink: 0;
        transition: all .3s ease;
    }

    .autosave-bar .as-hint {
        font-weight: 400;
        opacity: .65;
        font-size: 11.5px;
    }

    .as-idle {
        background: var(--gray-50);
        color: var(--gray-400);
        border-color: var(--gray-200);
    }

    .as-dot-idle {
        font-size: 7px !important;
    }

    .as-saving {
        background: #eff6ff;
        color: #1d4ed8;
        border-color: #bfdbfe;
    }

    .as-saved {
        background: var(--success-bg);
        color: #065f46;
        border-color: #6ee7b7;
    }

    .as-error {
        background: var(--danger-bg);
        color: #991b1b;
        border-color: #fca5a5;
    }

    /* FORM FIELDS */
    .pu-field {
        margin-bottom: 16px;
    }

    .pu-field:last-child {
        margin-bottom: 0;
    }

    .pu-label {
        display: block;
        font-size: .75rem;
        font-weight: 700;
        color: var(--gray-500);
        text-transform: uppercase;
        letter-spacing: .6px;
        margin-bottom: 6px;
    }

    .pu-input,
    .pu-textarea,
    .pu-select {
        width: 100%;
        padding: 10px 12px;
        font-size: .9rem;
        color: var(--gray-800);
        border: 1.5px solid var(--gray-200);
        border-radius: var(--r);
        background: var(--gray-50);
        transition: var(--tr);
        font-family: 'Plus Jakarta Sans', sans-serif;
        line-height: 1.5;
        -webkit-appearance: none;
    }

    .pu-input:focus,
    .pu-textarea:focus,
    .pu-select:focus {
        outline: none;
        border-color: var(--brand);
        background: white;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, .1);
    }

    .pu-input.is-invalid,
    .pu-textarea.is-invalid,
    .pu-select.is-invalid {
        border-color: var(--danger);
        box-shadow: 0 0 0 3px rgba(239, 68, 68, .1);
    }

    .pu-input:read-only,
    .pu-textarea:read-only {
        color: var(--gray-500);
        background: var(--gray-50);
        cursor: default;
    }

    .pu-input:read-only:focus,
    .pu-textarea:read-only:focus {
        border-color: var(--gray-200) !important;
        box-shadow: none !important;
    }

    .pu-textarea {
        resize: vertical;
        min-height: 100px;
    }

    .pu-select {
        background-image: url('data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 6"%3E%3Cpath d="M0 0l5 5 5-5z" fill="%2394a3b8"/%3E%3C/svg%3E');
        background-repeat: no-repeat;
        background-position: right 12px center;
        background-size: 9px;
        padding-right: 32px;
    }

    .pu-char-hint {
        display: flex;
        justify-content: flex-end;
        margin-top: 4px;
        font-size: .7rem;
        font-weight: 600;
        color: var(--gray-400);
        transition: color .2s;
    }

    .pu-char-hint.warn {
        color: var(--warning);
    }

    .pu-char-hint.over {
        color: var(--danger);
    }

    /* PAGE NAV */
    .cardpilihfaktor {
        border: 1px solid var(--gray-200);
        border-radius: var(--r);
        max-width: 720px;
        margin: 0 auto;
    }

    .cardpilihfaktor-header {
        text-align: center;
        background: var(--gray-50);
        padding: 6px;
        border-bottom: 1px solid var(--gray-200);
    }

    .cardpilihfaktor-header h6 {
        margin: 0;
        font-size: .9rem;
        font-weight: 700;
    }

    .cardpilihfaktor-body {
        padding: 8px 10px;
    }

    .nav-pages-wrap {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        justify-content: center;
    }

    .nav-page-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 32px;
        height: 32px;
        padding: 0 6px;
        border-radius: 6px;
        border: 1.5px solid var(--gray-200);
        background: white;
        color: var(--gray-700);
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        transition: var(--tr);
    }

    .nav-page-btn:hover {
        background: var(--brand-bg);
        border-color: var(--brand-light);
        color: var(--brand);
        text-decoration: none;
    }

    .nav-page-btn.active {
        background: var(--grad);
        color: white;
        border-color: var(--blue);
    }

    /* NOTIF / KOMENTAR */
    .notif-badge {
        position: absolute;
        top: -7px;
        right: -7px;
        background: var(--danger);
        color: #fff;
        border-radius: 50%;
        padding: 2px 5px;
        font-size: 10px;
        font-weight: 700;
        min-width: 17px;
        text-align: center;
        line-height: 1.3;
        box-shadow: 0 2px 6px rgba(239, 68, 68, .5);
        animation: pulse 2s infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            box-shadow: 0 0 0 0 rgba(239, 68, 68, .4)
        }

        50% {
            box-shadow: 0 0 0 5px rgba(239, 68, 68, 0)
        }
    }

    .btn-komentar-global-btn {
        position: relative;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: white;
        border: none;
        color: var(--blue);
        font-size: 17px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 10px rgba(0, 0, 0, .18);
        cursor: pointer;
        transition: all .25s cubic-bezier(.4, 0, .2, 1);
        outline: none;
    }

    .btn-komentar-global-btn:hover {
        background: var(--brand-bg);
        color: var(--brand);
        transform: scale(1.13);
    }

    .btn-komentar-global-btn.has-unread {
        animation: btn-shake 1.8s ease infinite;
        background: var(--brand-bg);
        color: var(--brand);
    }

    @keyframes btn-shake {

        0%,
        100% {
            transform: rotate(0)scale(1)
        }

        10% {
            transform: rotate(-14deg)scale(1.08)
        }

        20% {
            transform: rotate(12deg)scale(1.08)
        }

        30% {
            transform: rotate(-10deg)scale(1.05)
        }

        40% {
            transform: rotate(8deg)scale(1.05)
        }

        50% {
            transform: rotate(0)scale(1)
        }
    }

    .btn-komentar-ripple {
        position: absolute;
        inset: -4px;
        border-radius: 50%;
        border: 2px solid var(--brand-light);
        opacity: 0;
        pointer-events: none;
    }

    .btn-komentar-global-btn.has-unread .btn-komentar-ripple {
        animation: ripple-out 1.8s ease infinite;
    }

    @keyframes ripple-out {
        0% {
            transform: scale(.9);
            opacity: .7
        }

        100% {
            transform: scale(1.6);
            opacity: 0
        }
    }

    .komentar-modal-content {
        border-radius: 18px !important;
        overflow: hidden;
        border: none;
        box-shadow: 0 20px 60px rgba(0, 0, 0, .18) !important;
    }

    .komentar-modal-header {
        background: var(--grad);
        padding: 18px 22px !important;
        border: none !important;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .komentar-modal-title-wrap {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .komentar-modal-icon-wrap {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        background: rgba(255, 255, 255, .18);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        color: white;
        flex-shrink: 0;
    }

    .komentar-modal-title {
        font-size: 14px;
        font-weight: 800;
        color: white;
    }

    .komentar-close-btn {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .15);
        border: none;
        color: white;
        font-size: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all .2s;
        flex-shrink: 0;
    }

    .komentar-close-btn:hover {
        background: rgba(255, 255, 255, .3);
        transform: rotate(90deg);
    }

    .komentar-modal-body {
        padding: 18px 20px !important;
        background: var(--gray-50);
    }

    .komentar-modal-footer {
        background: white;
        border-top: 1px solid var(--gray-100) !important;
        padding: 10px 20px !important;
    }

    .komentar-list-label {
        font-size: 11px;
        font-weight: 700;
        color: var(--gray-500);
        text-transform: uppercase;
        letter-spacing: .8px;
        margin-bottom: 8px;
    }

    .komentar-list-wrapper {
        background: white;
        border: 1.5px solid var(--gray-200);
        border-radius: 12px;
        max-height: 300px;
        overflow-y: auto;
        padding: 10px;
        margin-bottom: 14px;
        scroll-behavior: smooth;
    }

    .komentar-list-wrapper::-webkit-scrollbar {
        width: 5px;
    }

    .komentar-list-wrapper::-webkit-scrollbar-thumb {
        background: var(--gray-200);
        border-radius: 10px;
    }

    .komentar-loading,
    .komentar-empty {
        text-align: center;
        color: var(--gray-400);
        padding: 24px 0;
        font-size: 13px;
    }

    .km-item {
        background: var(--gray-50);
        border-left: 3px solid var(--brand-light);
        border-radius: 10px;
        padding: 9px 12px;
        margin-bottom: 7px;
        box-shadow: var(--sh-sm);
        animation: kmIn .25s ease both;
    }

    .km-item:last-child {
        margin-bottom: 0;
    }

    .km-item.km-own {
        border-left-color: var(--success);
        background: #f0fdf4;
    }

    @keyframes kmIn {
        from {
            opacity: 0;
            transform: translateY(6px)
        }

        to {
            opacity: 1;
            transform: translateY(0)
        }
    }

    .km-meta {
        display: flex;
        justify-content: space-between;
        margin-bottom: 4px;
        flex-wrap: wrap;
        gap: 3px;
    }

    .km-author {
        font-weight: 700;
        font-size: 12px;
        color: var(--gray-800);
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .km-you {
        font-size: 9px;
        background: var(--success);
        color: white;
        padding: 1px 5px;
        border-radius: 100px;
        font-weight: 700;
    }

    .km-time {
        font-size: 11px;
        color: var(--gray-400);
    }

    .km-text {
        font-size: 13px;
        white-space: pre-wrap;
        margin: 0;
        color: var(--gray-700);
        line-height: 1.5;
    }

    .komentar-input-wrap {
        background: white;
        border: 1.5px solid var(--gray-200);
        border-radius: 12px;
        padding: 12px;
        transition: border-color .2s;
    }

    .komentar-input-wrap:focus-within {
        border-color: var(--brand);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, .1);
    }

    .komentar-input-label {
        font-size: 11px;
        font-weight: 700;
        color: var(--gray-500);
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: .6px;
    }

    .komentar-textarea {
        width: 100%;
        border: none;
        outline: none;
        resize: none;
        font-size: 13px;
        color: var(--gray-700);
        line-height: 1.6;
        background: transparent;
        font-family: 'Plus Jakarta Sans', sans-serif;
    }

    .komentar-input-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 6px;
        padding-top: 6px;
        border-top: 1px solid var(--gray-100);
    }

    .komentar-hint {
        font-size: 11px;
        color: var(--gray-400);
    }

    .komentar-char {
        font-size: 11px;
        color: var(--gray-400);
        font-weight: 600;
        transition: color .2s;
    }

    .komentar-char.danger {
        color: var(--danger);
    }

    .modal-content {
        border-radius: var(--r-lg);
        overflow: hidden;
        border: none;
        box-shadow: var(--sh-lg);
    }

    .modal-body {
        padding: 18px 22px !important;
    }

    .modal-footer {
        padding: 12px 22px !important;
    }

    .modal-dialog {
        margin: 16px auto !important;
        max-width: 680px !important;
    }

    .t-item {
        background: white;
        border-radius: var(--r);
        padding: 12px 16px;
        margin-bottom: 8px;
        box-shadow: var(--sh-lg);
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 13px;
        animation: tIn .3s ease;
        pointer-events: all;
        border-left: 4px solid;
    }

    .t-item.t-success {
        border-color: var(--success);
        color: #065f46;
    }

    .t-item.t-error {
        border-color: var(--danger);
        color: #991b1b;
    }

    .t-item.t-info {
        border-color: #06b6d4;
        color: #164e63;
    }

    @keyframes tIn {
        from {
            transform: translateX(110%);
            opacity: 0
        }

        to {
            transform: translateX(0);
            opacity: 1
        }
    }

    .blk-disabled {
        opacity: .4;
        pointer-events: none;
    }

    .btn-sync-year {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 6px 13px;
        border-radius: var(--r-sm);
        background: white;
        border: 1.5px solid var(--gray-200);
        color: var(--gray-700);
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: var(--tr);
        white-space: nowrap;
        font-family: 'Plus Jakarta Sans', sans-serif;
    }

    .btn-sync-year:not(:disabled):hover {
        background: #eff6ff;
        border-color: #93c5fd;
        color: #1d4ed8;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(29, 78, 216, .15);
    }

    .btn-sync-year:not(:disabled):hover .btn-sync-icon {
        animation: spin-once .5s ease;
    }

    .btn-sync-year:disabled {
        opacity: .45;
        cursor: not-allowed;
    }

    .btn-sync-year:active:not(:disabled) {
        transform: scale(.96);
    }

    .btn-sync-icon {
        font-size: 12px;
        flex-shrink: 0;
    }

    .btn-sync-label {
        flex-shrink: 0;
        align-items: end;
    }

    .btn-sync-badge {
        background: var(--gray-100);
        color: var(--gray-500);
        border-radius: 100px;
        padding: 1px 7px;
        font-size: 10.5px;
        font-weight: 800;
        letter-spacing: .3px;
    }

    .btn-sync-year:not(:disabled) .btn-sync-badge {
        background: #dbeafe;
        color: #1d4ed8;
    }

    @keyframes spin-once {
        from {
            transform: rotate(0deg)
        }

        to {
            transform: rotate(360deg)
        }
    }

    @media(max-width:576px) {
        .modal-dialog {
            margin: 8px !important;
            max-width: calc(100% - 16px) !important;
        }

        .modal-body {
            padding: 14px !important;
        }

        .approval-actions-row,
        .approval-badges-row {
            flex-direction: column;
            align-items: center;
        }

        .action-card {
            width: 100%;
            max-width: 340px;
        }

        .nav-pages-wrap {
            gap: 3px;
        }

        .nav-page-btn {
            min-width: 28px;
            height: 28px;
            font-size: 11px;
        }
    }
</style>