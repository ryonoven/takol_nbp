<?php
$v = static function (array $row, string $k): string {
    $x = (string) ($row[$k] ?? '');
    return $x === '' ? '' : esc($x);
};

$dis = $canEdit ? '' : 'disabled';

// Approval hanya Direktur Utama
$dirutOk = (int) ($row['is_approved'] ?? 0) === 1;
$cards = [
    ['dirut', 'Direktur Utama', $dirutOk, $row['approved_at'] ?? null, (int) ($row['approved_by'] ?? 0)],
];
$myCards = $canApproveDireksi ? $cards : [];
?>
<div id="toast-wrap" style="position:fixed;top:20px;right:20px;z-index:99999;min-width:290px;pointer-events:none;">
</div>
<div class="container-fluid">
    <a href="<?= base_url('Showtahunan') ?>" class="btn-kembali mb-3">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>

    <?php if (session()->getFlashdata('message')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <strong><?= esc(session()->getFlashdata('message')) ?></strong>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <strong><?= esc(session()->getFlashdata('error')) ?></strong>
        </div>
    <?php endif; ?>

    <?php if (!$periode): ?>
        <div class="alert alert-warning">Periode tahunan aktif tidak ditemukan. Pilih periode tahunan terlebih dahulu.</div>
    <?php else: ?>

        <div class="card card-body mb-3 shadow-sm">
            <div class="page-header">
                <?php if (!empty($row)): ?>
                    <div class="rb-badges">
                        <?php foreach ($cards as [$type, $label, $ok, $at, $by]): ?>
                            <div class="approval-status-badge <?= $ok ? 'badge-status-success' : 'badge-status-secondary' ?>">
                                <i class="fas <?= $ok ? 'fa-check-circle' : 'fa-clock' ?>"></i>
                                <div>
                                    <span class="approval-status-label"><?= $label ?></span><br>
                                    <span class="approval-status-sub">
                                        <?= $ok ? 'Disetujui' . ($at ? ' · ' . esc(date('d/m/Y H:i', strtotime($at))) : '') : 'Belum disetujui' ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <h1 class="page-title"><?= esc($judul) ?></h1>
                <div class="page-subtitle">Tahun <?= esc($periode['tahun']) ?></div>
                <span class="badge-namabpr"><?= esc($namabpr) ?></span>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center"
                style="background: var(--grad);">
                <h5 class="m-0 font-weight-bold text-white">
                    <i class="fas fa-file-signature"></i> Data Surat Pernyataan Direksi
                </h5>
                <?php if (!empty($row)): ?>
                    <?php if ($canViewPdf): ?>
                        <a href="<?= site_url('suratpernyataandireksi/pdf') ?>" target="_blank" rel="noopener"
                            class="btn btn-light btn-sm"
                            title="<?= $hasPdf ? 'Menampilkan scan surat bertanda tangan yang diunggah' : 'Surat dibuat otomatis dari isian' ?>">
                            <i class="fas fa-file-pdf"></i> Lihat PDF
                        </a>
                    <?php else: ?>
                        <span title="Tersedia setelah Direktur Utama menyetujui">
                            <button type="button" class="btn btn-light btn-sm" disabled style="pointer-events:none;">
                                <i class="fas fa-file-pdf"></i> Lihat PDF
                            </button>
                        </span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="card-body p-3">
                <?php if ($canEdit && $hasApproval): ?>
                    <div class="alert alert-warning">
                        <small><i class="fas fa-info-circle"></i> <strong>Perhatian:</strong> Mengubah data akan me-reset status
                            approval.</small>
                    </div>
                <?php endif; ?>

                <div class="alert alert-info py-2">
                    <small><i class="fas fa-info-circle"></i>
                        Isi data penandatangan untuk membuat surat secara otomatis (<strong>Lihat PDF</strong>), cetak, tanda
                        tangani dan bubuhkan meterai, lalu unggah hasil scan pada <strong>Surat Bertanda Tangan (PDF)</strong>.
                        Jika scan diunggah, file tersebut yang digunakan sebagai laporan.</small>
                </div>

                <form id="rbForm" autocomplete="off" novalidate>
                    <input type="hidden" id="rbCsrf" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">

                    <div class="row">
                        <?php
                        // Satu blok direksi. $d = data (array), $i = index; dipakai juga untuk <template> (index "__i__").
                        $renderDireksi = static function (array $d, $i) use ($direksiFields, $hasPdf, $dis, $canEdit) {
                            ob_start(); ?>
                            <div class="signer-box" data-direksi>
                                <div class="signer-title d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-user-tie"></i> Direksi <span data-no><?= is_int($i) ? $i + 1 : '' ?></span></span>
                                    <?php if ($canEdit): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-remove title="Hapus direksi ini">
                                            <i class="fas fa-trash"></i> Hapus
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <div class="row">
                                    <?php foreach ($direksiFields as $f => [$label, $maxLen]):
                                        $isAddr = str_starts_with($f, 'alamat'); ?>
                                        <div class="<?= $isAddr ? 'col-md-12' : 'col-md-4' ?>">
                                            <div class="form-group" data-f="d_<?= $f ?>.<?= $i ?>" data-field="<?= $f ?>">
                                                <label class="font-weight-bold"><?= esc($label) ?>
                                                    <?php if (!$hasPdf): ?><span class="text-danger">*</span><?php endif; ?>
                                                </label>
                                                <input type="<?= $f === 'telepon' ? 'tel' : 'text' ?>" class="form-control"
                                                    name="d_<?= $f ?>[]" maxlength="<?= (int) $maxLen ?>"
                                                    value="<?= esc((string) ($d[$f] ?? '')) ?>" <?= $dis ?>>
                                                <div class="rb-msg" data-msg="d_<?= $f ?>.<?= $i ?>"></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php return ob_get_clean();
                        };
                        ?>
                        <div class="col-md-12">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="font-weight-bold">Kami yang bertanda tangan di bawah ini:</div>
                                <?php if ($canEdit): ?>
                                    <button type="button" class="btn btn-sm btn-success" id="btnAddDireksi">
                                        <i class="fas fa-plus"></i> Tambah Direksi
                                    </button>
                                <?php endif; ?>
                            </div>
                            <div id="direksiList" data-min-direksi="<?= (int) $minDireksi ?>" data-max-direksi="<?= (int) $maxDireksi ?>">
                                <?php foreach (array_values($direksi) as $i => $d): ?>
                                    <?= $renderDireksi($d, $i) ?>
                                <?php endforeach; ?>
                            </div>
                            <div class="rb-msg mb-3" data-msg="direksi"></div>
                            <?php if ($canEdit): ?>
                                <template id="direksiTpl"><?= $renderDireksi(['alamatkantor' => $alamatBpr], '__i__') ?></template>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group" data-f="kota">
                                <label class="font-weight-bold" for="f_kota">Kota Penandatanganan
                                    <?php if (!$hasPdf): ?><span class="text-danger">*</span><?php endif; ?>
                                </label>
                                <input type="text" class="form-control" id="f_kota" name="kota" maxlength="<?= (int) $maxKota ?>"
                                    value="<?= $v($row, 'kota') ?>" placeholder="mis. Surabaya" <?= $dis ?>>
                                <div class="rb-msg" data-msg="kota"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group" data-f="tanggalsurat">
                                <label class="font-weight-bold" for="f_tanggalsurat">Tanggal Surat
                                    <?php if (!$hasPdf): ?><span class="text-danger">*</span><?php endif; ?>
                                </label>
                                <input type="date" class="form-control" id="f_tanggalsurat" name="tanggalsurat"
                                    value="<?= $v($row, 'tanggalsurat') ?>" <?= $dis ?>>
                                <div class="rb-msg" data-msg="tanggalsurat"></div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="pernyataan-preview">
                                <div class="font-weight-bold mb-1">Menyatakan bahwa: <small class="text-muted font-weight-normal">(isi tetap, tercetak di surat)</small></div>
                                <ol class="mb-0">
                                    <?php foreach ($pernyataan as $s): ?>
                                        <li><?= esc($s) ?></li>
                                    <?php endforeach; ?>
                                </ol>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group" data-f="suratpdf">
                                <label class="font-weight-bold" for="f_suratpdf">Surat Bertanda Tangan + Meterai (PDF)
                                    <small class="text-muted font-weight-normal">— opsional</small>
                                </label>

                                <?php if ($hasPdf): ?>
                                    <div class="d-flex flex-wrap align-items-center mb-2" style="gap:12px;">
                                        <div class="pdf-current">
                                            <i class="fas fa-file-pdf text-danger"></i>
                                            <a href="<?= site_url('suratpernyataandireksi/pdf') ?>" target="_blank" rel="noopener">Scan surat
                                                terunggah</a>
                                        </div>
                                        <?php if ($canEdit): ?>
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="f_hapus_pdf" name="hapus_pdf"
                                                    value="1">
                                                <label class="custom-control-label" for="f_hapus_pdf">Hapus scan (gunakan surat
                                                    otomatis dari isian)</label>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php elseif (!$canEdit): ?>
                                    <div class="text-muted small">Tidak ada PDF yang diunggah — PDF dibuat otomatis dari isian.</div>
                                <?php endif; ?>

                                <?php if ($canEdit): ?>
                                    <input type="file" class="form-control" id="f_suratpdf" name="suratpdf"
                                        accept="application/pdf,.pdf" data-maxkb="<?= (int) $pdfMaxKb ?>">
                                    <small class="text-muted">
                                        Format PDF, maksimal <?= esc($pdfMaxKb / 1024) ?> MB.
                                        <?= $hasPdf ? 'Unggah file baru untuk mengganti file yang ada.' : 'Kosongkan bila PDF ingin dibuat otomatis dari isian di atas.' ?>
                                    </small>
                                <?php endif; ?>

                                <div class="rb-msg" data-msg="suratpdf"></div>
                            </div>
                        </div>
                    </div>

                    <?php if ($canEdit): ?>
                        <button type="submit" class="btn btn-primary" id="rbSave"><i class="fas fa-save"></i> Simpan
                            Data</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <?php if ($myCards): ?>
            <div class="text-center mt-2 mb-2 d-flex justify-content-center flex-wrap" style="gap:12px;">
                <?php foreach ($myCards as [$type, $label, $ok, $at, $by]): ?>
                    <div class="card shadow-sm action-card d-inline-block" style="min-width:220px;">
                        <div class="card-body py-2 text-center">
                            <small class="d-block font-weight-bold text-dark mb-2">
                                <i class="fas fa-user-tie text-success"></i> <?= $label ?>
                            </small>
                            <div class="d-flex justify-content-center flex-wrap" style="gap:4px;">
                                <?php if (!$ok): ?>
                                    <button type="button" class="btn btn-primary btn-sm rb-act" data-type="<?= $type ?>"
                                        data-act="approve">
                                        <i class="fas fa-check-double"></i> Setujui
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-outline-danger btn-sm rb-act" data-type="<?= $type ?>"
                                        data-act="cancel">
                                        <i class="fas fa-ban"></i> Batal
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

    :root {
        --brand: #4f46e5;
        --brand-light: #818cf8;
        --brand-bg: #eef2ff;
        --success: #10b981;
        --success-bg: #d1fae5;
        --danger: #ef4444;
        --warning: #f59e0b;
        --info: #06b6d4;
        --gray-50: #f8fafc;
        --gray-100: #f1f5f9;
        --gray-200: #e2e8f0;
        --gray-400: #94a3b8;
        --gray-500: #64748b;
        --gray-900: #0f172a;
        --blue: #1f2aa8;
        --r-sm: 8px;
        --r: 12px;
        --r-lg: 16px;
        --sh-sm: 0 1px 3px rgba(0, 0, 0, .08), 0 1px 2px rgba(0, 0, 0, .06);
        --sh: 0 4px 6px -1px rgba(0, 0, 0, .1), 0 2px 4px -1px rgba(0, 0, 0, .06);
        --sh-lg: 0 10px 25px -3px rgba(0, 0, 0, .1), 0 4px 6px -2px rgba(0, 0, 0, .05);
        --tr: all .2s cubic-bezier(.4, 0, .2, 1);
        --grad: linear-gradient(135deg, #141863 0%, #1f2aa8 100%)
    }

    * {
        font-family: 'Plus Jakarta Sans', sans-serif
    }

    .page-header {
        text-align: center;
        padding: 32px 20px 24px;
        background: white;
        border-bottom: 1px solid var(--gray-100);
        position: relative;
        overflow: hidden
    }

    .page-title {
        font-size: clamp(1.3rem, 3.5vw, 1.75rem);
        font-weight: 800;
        color: var(--gray-900);
        margin-bottom: 6px;
        letter-spacing: -.5px;
        position: relative;
        z-index: 1
    }

    .page-subtitle {
        font-size: 1rem;
        font-weight: 600;
        color: var(--gray-900);
        margin-bottom: 12px;
        position: relative;
        z-index: 1
    }

    .badge-namabpr {
        display: inline-block;
        background: var(--grad);
        color: white;
        font-size: 1rem;
        font-weight: 700;
        padding: 6px 20px;
        border-radius: 100px;
        letter-spacing: .3px;
        position: relative;
        z-index: 1;
        box-shadow: 0 4px 12px rgba(79, 70, 229, .3)
    }

    .rb-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: flex-start;
        margin-bottom: 12px;
        position: relative;
        z-index: 1
    }

    @media(max-width:576px) {
        .rb-badges {
            justify-content: center
        }
    }

    .approval-status-badge {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 16px;
        border-radius: var(--r);
        font-size: 13px;
        font-weight: 600;
        border: 1.5px solid transparent;
        text-align: left
    }

    .approval-status-badge i {
        font-size: 18px;
        flex-shrink: 0
    }

    .approval-status-label {
        font-weight: 700;
        font-size: 12px
    }

    .approval-status-sub {
        font-size: 11px;
        opacity: .8
    }

    .badge-status-secondary {
        background: var(--gray-100);
        color: var(--gray-500);
        border-color: var(--gray-200)
    }

    .badge-status-success {
        background: var(--success-bg);
        color: #065f46;
        border-color: #6ee7b7
    }

    .action-card {
        border-radius: var(--r) !important;
        border: 1.5px solid var(--gray-200) !important;
        transition: var(--tr);
        overflow: hidden
    }

    .action-card:hover {
        border-color: var(--brand-light) !important;
        box-shadow: 0 8px 24px rgba(79, 70, 229, .12) !important
    }

    .card-header {
        border-radius: var(--r-lg) var(--r-lg) 0 0 !important
    }

    .btn {
        transition: var(--tr);
        font-weight: 600
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: var(--sh)
    }

    .btn:active {
        transform: scale(.96) !important
    }

    .btn-sm {
        font-size: 12px
    }

    .btn-kembali {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: white;
        border: 1.5px solid var(--gray-200);
        border-radius: var(--r-sm);
        padding: 8px 16px;
        font-size: .875rem;
        font-weight: 600;
        color: var(--gray-500);
        text-decoration: none;
        transition: var(--tr);
        margin: 14px 0 0 16px
    }

    .btn-kembali:hover {
        background: var(--brand-bg);
        border-color: var(--brand-light);
        color: var(--brand);
        text-decoration: none
    }

    .card-body .form-group {
        margin-bottom: 16px
    }

    .card-body .form-control {
        width: 100%;
        box-sizing: border-box;
        border-radius: 8px;
        border: 1.5px solid var(--gray-200);
        padding: 8px 12px;
        font-size: 13.5px;
        transition: var(--tr)
    }

    .card-body .input-group .form-control {
        width: 1%;
        flex: 1 1 auto;
        border-radius: 8px 0 0 8px
    }

    .card-body .input-group-text {
        border-radius: 0 8px 8px 0;
        border: 1.5px solid var(--gray-200);
        background: var(--gray-50);
        font-weight: 600;
        color: var(--gray-500)
    }

    .card-body .form-control:focus {
        border-color: var(--brand);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, .15);
        outline: none
    }

    .card-body .form-control:disabled {
        background: var(--gray-50);
        color: #334155
    }

    .form-group.err .form-control {
        border-color: var(--danger)
    }

    .signer-box {
        border: 1.5px solid var(--gray-200);
        border-radius: var(--r);
        padding: 14px 16px 2px;
        margin-bottom: 16px;
        background: var(--gray-50)
    }

    .signer-title {
        font-weight: 700;
        color: var(--gray-900);
        margin-bottom: 10px
    }

    .pernyataan-preview {
        border-left: 4px solid var(--brand-light);
        background: var(--brand-bg);
        border-radius: var(--r-sm);
        padding: 12px 16px;
        margin-bottom: 16px;
        font-size: 13px
    }

    .pdf-current {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 12px;
        border: 1.5px solid var(--gray-200);
        border-radius: var(--r-sm);
        background: var(--gray-50);
        font-size: 13px;
        font-weight: 600
    }

    .rb-msg {
        color: var(--danger);
        font-size: 12px;
        margin-top: 4px
    }

    .char-counter-wrap {
        display: flex;
        justify-content: flex-end;
        margin-top: 4px
    }

    .char-counter {
        font-size: 11px;
        font-weight: 600;
        color: var(--gray-400);
        transition: color .2s
    }

    .char-counter.warn {
        color: var(--warning)
    }

    .char-counter.danger {
        color: var(--danger)
    }

    .t-item {
        background: white;
        border-radius: var(--r);
        padding: 14px 18px;
        margin-bottom: 10px;
        box-shadow: var(--sh-lg);
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 13.5px;
        animation: tIn .3s ease;
        pointer-events: all;
        border-left: 4px solid
    }

    .t-item.t-success {
        border-color: var(--success);
        color: #065f46
    }

    .t-item.t-error {
        border-color: var(--danger);
        color: #991b1b
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
</style>

<?php if ($periode): ?>
    <script>
        (function () {
            var form = document.getElementById('rbForm');
            var csrf = document.getElementById('rbCsrf');
            var base = '<?= site_url('suratpernyataandireksi') ?>';
            var CAN_EDIT = <?= $canEdit ? 'true' : 'false' ?>;
            var HAS_APPROVAL_CARDS = <?= !empty($row) ? 'true' : 'false' ?>;

            function toast(msg, type) {
                type = type || 'success';
                var icons = { success: 'check-circle', error: 'exclamation-circle' };
                var el = document.createElement('div');
                el.className = 't-item t-' + type;
                el.innerHTML = '<i class="fas fa-' + icons[type] + ' fa-lg"></i><div></div>';
                el.lastChild.textContent = msg;
                document.getElementById('toast-wrap').appendChild(el);
                setTimeout(function () {
                    el.style.transition = 'opacity .4s'; el.style.opacity = '0';
                    setTimeout(function () { el.remove(); }, 400);
                }, 3500);
            }

            function post(url, fd) {
                fd.set(csrf.name, csrf.value);
                return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' })
                    .then(function (r) {
                        if (r.status === 403 && r.headers.get('content-type').indexOf('json') < 0) {
                            toast('Sesi keamanan kedaluwarsa. Halaman akan di-refresh...', 'error');
                            setTimeout(function () { location.reload(); }, 2000);
                            throw new Error('csrf');
                        }
                        return r.json().then(function (j) { j._status = r.status; return j; });
                    })
                    .then(function (j) {
                        if (j.csrf) { csrf.name = j.csrf.name; csrf.value = j.csrf.hash; }
                        if (j._status === 401) { setTimeout(function () { location.href = '<?= base_url('login') ?>'; }, 1500); }
                        return j;
                    });
            }

            // ---- counter karakter (satu listener delegasi untuk semua field)
            function count(el) {
                var max = +el.dataset.max, len = el.value.length;
                var c = form.querySelector('[data-cnt="' + el.name + '"]');
                if (!c) return;
                c.textContent = len + ' / ' + max;
                c.classList.remove('warn', 'danger');
                if (len >= max) c.classList.add('danger'); else if (len >= max * 0.85) c.classList.add('warn');
            }
            form.querySelectorAll('[data-max]').forEach(count);
            form.addEventListener('input', function (e) {
                var t = e.target;
                if (t.dataset && t.dataset.max) count(t);
            });

            function clearErr() {
                form.querySelectorAll('.err').forEach(function (n) { n.classList.remove('err'); });
                form.querySelectorAll('[data-msg]').forEach(function (n) { n.textContent = ''; });
            }

            // ---- daftar direksi: tambah / hapus, nomor & kunci error mengikuti urutan di layar
            var list = document.getElementById('direksiList');
            var tpl = document.getElementById('direksiTpl');
            var btnAdd = document.getElementById('btnAddDireksi');
            var MIN_D = +list.dataset.minDireksi, MAX_D = +list.dataset.maxDireksi;

            function renumber() {
                var blocks = list.querySelectorAll('[data-direksi]');
                blocks.forEach(function (b, i) {
                    b.querySelector('[data-no]').textContent = i + 1;
                    b.querySelectorAll('[data-field]').forEach(function (g) {
                        var key = 'd_' + g.dataset.field + '.' + i;   // sama dengan kunci error dari server
                        g.dataset.f = key;
                        g.querySelector('[data-msg]').dataset.msg = key;
                    });
                    var rm = b.querySelector('[data-remove]');
                    if (rm) rm.style.display = blocks.length > MIN_D ? '' : 'none';
                });
                if (btnAdd) btnAdd.disabled = blocks.length >= MAX_D;
            }

            if (btnAdd && tpl) {
                btnAdd.addEventListener('click', function () {
                    if (list.querySelectorAll('[data-direksi]').length >= MAX_D) return;
                    list.appendChild(tpl.content.firstElementChild.cloneNode(true));
                    renumber();
                    var last = list.lastElementChild;
                    last.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    last.querySelector('input').focus();
                });
            }
            list.addEventListener('click', function (e) {
                var rm = e.target.closest('[data-remove]');
                if (!rm) return;
                var block = rm.closest('[data-direksi]');
                var hasData = Array.prototype.some.call(block.querySelectorAll('input'), function (i) { return i.value.trim() !== ''; });
                if (hasData && !confirm('Hapus data direksi ini?')) return;
                block.remove();
                renumber();
            });
            renumber();

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                if (!CAN_EDIT) return;
                clearErr();

                // cek PDF di sisi klien (server tetap memvalidasi ulang)
                var pdf = document.getElementById('f_suratpdf');
                if (pdf && pdf.files.length) {
                    var f = pdf.files[0], maxKb = +pdf.dataset.maxkb;
                    var pdfErr = !/\.pdf$/i.test(f.name) ? 'File harus berformat PDF.'
                        : (f.size > maxKb * 1024 ? 'Ukuran file maksimal ' + (maxKb / 1024) + ' MB.' : '');
                    if (pdfErr) {
                        form.querySelector('[data-f="suratpdf"]').classList.add('err');
                        form.querySelector('[data-msg="suratpdf"]').textContent = pdfErr;
                        toast(pdfErr, 'error');
                        return;
                    }
                }

                var btn = document.getElementById('rbSave');
                var ori = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                btn.disabled = true;               // cegah double submit

                post(base + '/save', new FormData(form)).then(function (j) {
                    toast(j.message || 'Selesai.', j.ok ? 'success' : 'error');
                    if (j.errors) {
                        Object.keys(j.errors).forEach(function (k) {
                            var w = form.querySelector('[data-f="' + k + '"]');
                            var m = form.querySelector('[data-msg="' + k + '"]');
                            if (w) w.classList.add('err');
                            if (m) m.textContent = j.errors[k];
                        });
                    }
                    // muat ulang bila status approval berubah / kartu approval belum ada / PDF baru diunggah
                    var hapus = document.getElementById('f_hapus_pdf');
                    if (j.ok && (!HAS_APPROVAL_CARDS || /direset/.test(j.message) || (pdf && pdf.files.length) || (hapus && hapus.checked))) {
                        setTimeout(function () { location.reload(); }, 800);
                    }
                }).catch(function () {
                    toast('Terjadi kesalahan server.', 'error');
                }).then(function () { btn.innerHTML = ori; btn.disabled = false; });
            });

            // ---- approval Direktur
            document.querySelectorAll('.rb-act').forEach(function (b) {
                b.addEventListener('click', function () {
                    var cancel = b.dataset.act === 'cancel';
                    if (!confirm(cancel ? 'Batalkan approval ini?' : 'Setujui Surat Pernyataan Direksi ini?')) return;
                    b.disabled = true;
                    var fd = new FormData();
                    fd.set('act', b.dataset.act);
                    post(base + '/approve', fd).then(function (j) {
                        if (j.ok) { toast(j.message); setTimeout(function () { location.reload(); }, 600); return; }
                        toast(j.message || 'Gagal.', 'error');
                        b.disabled = false;
                    }).catch(function () { toast('Terjadi kesalahan server.', 'error'); b.disabled = false; });
                });
            });
        })();
    </script>
<?php endif; ?>
<?php if (!empty($periode)): ?>
    <?= view('templates/komentar', ['komentarPage' => 'Suratpernyataandireksi']) ?>
<?php endif; ?>
