<?php
$approved = $approval['approved'];
?>
<div id="toast-wrap" style="position:fixed;top:20px;right:20px;z-index:99999;min-width:290px;pointer-events:none;">
</div>
<div class="container-fluid">
    <a href="<?= base_url('Showtahunan') ?>" class="btn-kembali mb-3">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>

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
                <?php if ($rows): ?>
                    <div class="rb-badges">
                        <div class="approval-status-badge <?= $approved ? 'badge-status-success' : 'badge-status-secondary' ?>">
                            <i class="fas <?= $approved ? 'fa-check-circle' : 'fa-clock' ?>"></i>
                            <div>
                                <span class="approval-status-label">Direktur Utama</span><br>
                                <span class="approval-status-sub">
                                    <?= $approved ? 'Disetujui' . ($approval['at'] ? ' · ' . esc(date('d/m/Y H:i', strtotime($approval['at']))) : '') : 'Belum disetujui' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <h1 class="page-title"><?= esc($judul) ?></h1>
                <div class="page-subtitle">Tahun <?= esc($periode['tahun']) ?></div>
                <span class="badge-namabpr"><?= esc($namabpr) ?></span>
            </div>
        </div>

        <?php if ($canEdit): ?>
            <!-- ===== Form tambah / ubah ===== -->
            <div class="card shadow mb-4">
                <div class="card-header py-3" style="background: var(--grad);">
                    <h5 class="m-0 font-weight-bold text-white" id="formTitle">
                        <i class="fas fa-plus-circle"></i> Tambah Kegiatan Pengembangan
                    </h5>
                </div>
                <div class="card-body p-3">
                    <?php if ($approved): ?>
                        <div class="alert alert-warning">
                            <small><i class="fas fa-info-circle"></i> <strong>Perhatian:</strong> Menambah, mengubah, atau menghapus
                                data akan me-reset status approval.</small>
                        </div>
                    <?php endif; ?>

                    <form id="rbForm" autocomplete="off" novalidate>
                        <input type="hidden" id="rbCsrf" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
                        <input type="hidden" name="id" id="f_id" value="">

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group" data-f="kegiatanpengembangan">
                                    <label class="font-weight-bold" for="f_kegiatanpengembangan">Kegiatan Pengembangan <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="f_kegiatanpengembangan" name="kegiatanpengembangan" rows="2"
                                        maxlength="<?= $max['kegiatanpengembangan'] ?>" data-max="<?= $max['kegiatanpengembangan'] ?>"></textarea>
                                    <div class="char-counter-wrap"><span class="char-counter" data-cnt="kegiatanpengembangan">0 / <?= $max['kegiatanpengembangan'] ?></span></div>
                                    <div class="rb-msg" data-msg="kegiatanpengembangan"></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group" data-f="tanggalpelaksana">
                                    <label class="font-weight-bold" for="f_tanggalpelaksana">Tanggal Pelaksanaan <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="f_tanggalpelaksana" name="tanggalpelaksana">
                                    <div class="rb-msg" data-msg="tanggalpelaksana"></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group" data-f="pihakpelaksana">
                                    <label class="font-weight-bold" for="f_pihakpelaksana">Pihak Pelaksana <span class="text-danger">*</span></label>
                                    <select class="form-control" id="f_pihakpelaksana" name="pihakpelaksana">
                                        <option value="">-- Pilih --</option>
                                        <?php foreach ($pihakPelaksana as $k => $label): ?>
                                            <option value="<?= esc($k) ?>"><?= esc($k . ' - ' . $label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="rb-msg" data-msg="pihakpelaksana"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group" data-f="kategoripeserta">
                                    <label class="font-weight-bold" for="f_kategoripeserta">Kategori Peserta <span class="text-danger">*</span></label>
                                    <select class="form-control" id="f_kategoripeserta" name="kategoripeserta">
                                        <option value="">-- Pilih --</option>
                                        <?php foreach ($kategoriPeserta as $k => $label): ?>
                                            <option value="<?= esc($k) ?>"><?= esc($k . ' - ' . $label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="rb-msg" data-msg="kategoripeserta"></div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group" data-f="jumlahpeserta">
                                    <label class="font-weight-bold" for="f_jumlahpeserta">Jumlah Peserta <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="f_jumlahpeserta" name="jumlahpeserta"
                                        min="0" max="<?= (int) $maxPeserta ?>" step="1" inputmode="numeric">
                                    <div class="rb-msg" data-msg="jumlahpeserta"></div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group" data-f="uraiankegiatan">
                                    <label class="font-weight-bold" for="f_uraiankegiatan">Uraian Kegiatan <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="f_uraiankegiatan" name="uraiankegiatan" rows="4"
                                        maxlength="<?= $max['uraiankegiatan'] ?>" data-max="<?= $max['uraiankegiatan'] ?>"></textarea>
                                    <div class="char-counter-wrap"><span class="char-counter" data-cnt="uraiankegiatan">0 / <?= $max['uraiankegiatan'] ?></span></div>
                                    <div class="rb-msg" data-msg="uraiankegiatan"></div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" id="rbSave"><i class="fas fa-save"></i> Simpan</button>
                        <button type="button" class="btn btn-outline-secondary" id="rbCancel" style="display:none;">
                            <i class="fas fa-times"></i> Batal Ubah
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- ===== Daftar ===== -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center" style="background: var(--grad);">
                <h5 class="m-0 font-weight-bold text-white">
                    <i class="fas fa-seedling"></i> Daftar Kegiatan Pengembangan (<?= count($rows) ?>)
                </h5>
                <?php if ($rows): ?>
                    <div class="d-flex flex-wrap" style="gap:6px;">
                        <a href="<?= site_url('kegiatanpengembangan/exporttxt') ?>" class="btn btn-light btn-sm">
                            <i class="fas fa-file-alt"></i> Export TXT
                        </a>
                        <a href="<?= site_url('kegiatanpengembangan/pdf') ?>" target="_blank" rel="noopener" class="btn btn-light btn-sm">
                            <i class="fas fa-file-pdf"></i> Lihat PDF
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <?php if (!$rows): ?>
                    <div class="text-center text-muted py-4">Belum ada data.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0 bu-table">
                            <thead>
                                <tr>
                                    <th style="width:40px;">No</th>
                                    <th>Kegiatan Pengembangan</th>
                                    <th>Tanggal</th>
                                    <th>Pihak Pelaksana</th>
                                    <th>Kategori Peserta</th>
                                    <th class="text-right">Jumlah Peserta</th>
                                    <th>Uraian Kegiatan</th>
                                    <?php if ($canEdit): ?><th style="width:90px;">Aksi</th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $i => $r): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td class="pre"><?= esc($r['kegiatanpengembangan']) ?></td>
                                        <td class="text-nowrap"><?= esc(date('d/m/Y', strtotime($r['tanggalpelaksana']))) ?></td>
                                        <td><?= esc($pihakPelaksana[$r['pihakpelaksana']] ?? $r['pihakpelaksana']) ?></td>
                                        <td><?= esc($kategoriPeserta[$r['kategoripeserta']] ?? $r['kategoripeserta']) ?></td>
                                        <td class="text-right"><?= number_format((int) $r['jumlahpeserta'], 0, ',', '.') ?></td>
                                        <td class="pre"><?= esc($r['uraiankegiatan']) ?></td>
                                        <?php if ($canEdit): ?>
                                            <td class="text-nowrap">
                                                <button type="button" class="btn btn-sm btn-outline-primary bu-edit" title="Ubah"
                                                    data-row="<?= esc(json_encode(array_intersect_key($r, array_flip(['id', 'kegiatanpengembangan', 'tanggalpelaksana', 'pihakpelaksana', 'kategoripeserta', 'jumlahpeserta', 'uraiankegiatan']))), 'attr') ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger bu-del" title="Hapus"
                                                    data-id="<?= (int) $r['id'] ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($rows): ?>
            <!-- ===== Keterangan (footer F01) ===== -->
            <div class="card shadow mb-4">
                <div class="card-header py-3" style="background: var(--grad);">
                    <h5 class="m-0 font-weight-bold text-white"><i class="fas fa-sticky-note"></i> Keterangan</h5>
                </div>
                <div class="card-body p-3">
                    <form id="ketForm" autocomplete="off" novalidate>
                        <div class="form-group" data-f="keterangan">
                            <label class="font-weight-bold" for="f_keterangan">Keterangan <small class="text-muted font-weight-normal">— opsional, berlaku untuk seluruh daftar</small></label>
                            <textarea class="form-control" id="f_keterangan" name="keterangan" rows="3"
                                maxlength="<?= (int) $maxKeterangan ?>" data-max="<?= (int) $maxKeterangan ?>"
                                <?= $canEdit ? '' : 'disabled' ?>><?= esc($keterangan) ?></textarea>
                            <div class="char-counter-wrap"><span class="char-counter" data-cnt="keterangan">0 / <?= (int) $maxKeterangan ?></span></div>
                            <div class="rb-msg" data-msg="keterangan"></div>
                        </div>
                        <?php if ($canEdit): ?>
                            <button type="submit" class="btn btn-primary" id="ketSave"><i class="fas fa-save"></i> Simpan Keterangan</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($canApprove): ?>
            <div class="text-center mt-2 mb-2">
                <div class="card shadow-sm action-card d-inline-block" style="min-width:220px;">
                    <div class="card-body py-2 text-center">
                        <small class="d-block font-weight-bold text-dark mb-2">
                            <i class="fas fa-user-tie text-success"></i> Direktur Utama
                        </small>
                        <?php if (!$approved): ?>
                            <button type="button" class="btn btn-primary btn-sm rb-act" data-act="approve">
                                <i class="fas fa-check-double"></i> Setujui
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-outline-danger btn-sm rb-act" data-act="cancel">
                                <i class="fas fa-ban"></i> Batal
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!$canEdit): ?>
            <input type="hidden" id="rbCsrf" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
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
        --gray-50: #f8fafc;
        --gray-100: #f1f5f9;
        --gray-200: #e2e8f0;
        --gray-400: #94a3b8;
        --gray-500: #64748b;
        --gray-900: #0f172a;
        --r-sm: 8px;
        --r: 12px;
        --r-lg: 16px;
        --sh: 0 4px 6px -1px rgba(0, 0, 0, .1), 0 2px 4px -1px rgba(0, 0, 0, .06);
        --sh-lg: 0 10px 25px -3px rgba(0, 0, 0, .1), 0 4px 6px -2px rgba(0, 0, 0, .05);
        --tr: all .2s cubic-bezier(.4, 0, .2, 1);
        --grad: linear-gradient(135deg, #141863 0%, #1f2aa8 100%)
    }

    * { font-family: 'Plus Jakarta Sans', sans-serif }

    .page-header { text-align: center; padding: 32px 20px 24px; background: white; border-bottom: 1px solid var(--gray-100); position: relative }
    .page-title { font-size: clamp(1.3rem, 3.5vw, 1.75rem); font-weight: 800; color: var(--gray-900); margin-bottom: 6px; letter-spacing: -.5px }
    .page-subtitle { font-size: 1rem; font-weight: 600; color: var(--gray-900); margin-bottom: 12px }
    .badge-namabpr { display: inline-block; background: var(--grad); color: white; font-size: 1rem; font-weight: 700; padding: 6px 20px; border-radius: 100px; box-shadow: 0 4px 12px rgba(79, 70, 229, .3) }

    .rb-badges { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px }
    @media(max-width:576px) { .rb-badges { justify-content: center } }
    .approval-status-badge { display: flex; align-items: center; gap: 10px; padding: 10px 16px; border-radius: var(--r); font-size: 13px; font-weight: 600; border: 1.5px solid transparent; text-align: left }
    .approval-status-badge i { font-size: 18px }
    .approval-status-label { font-weight: 700; font-size: 12px }
    .approval-status-sub { font-size: 11px; opacity: .8 }
    .badge-status-secondary { background: var(--gray-100); color: var(--gray-500); border-color: var(--gray-200) }
    .badge-status-success { background: var(--success-bg); color: #065f46; border-color: #6ee7b7 }

    .action-card { border-radius: var(--r) !important; border: 1.5px solid var(--gray-200) !important; transition: var(--tr) }
    .action-card:hover { border-color: var(--brand-light) !important; box-shadow: 0 8px 24px rgba(79, 70, 229, .12) !important }
    .card-header { border-radius: var(--r-lg) var(--r-lg) 0 0 !important }

    .btn { transition: var(--tr); font-weight: 600 }
    .btn:hover { transform: translateY(-2px); box-shadow: var(--sh) }
    .btn-sm { font-size: 12px }
    .btn-kembali { display: inline-flex; align-items: center; gap: 6px; background: white; border: 1.5px solid var(--gray-200); border-radius: var(--r-sm); padding: 8px 16px; font-size: .875rem; font-weight: 600; color: var(--gray-500); text-decoration: none; transition: var(--tr); margin: 14px 0 0 16px }
    .btn-kembali:hover { background: var(--brand-bg); border-color: var(--brand-light); color: var(--brand); text-decoration: none }

    .card-body .form-group { margin-bottom: 16px }
    .card-body .form-control { border-radius: 8px; border: 1.5px solid var(--gray-200); font-size: 13.5px; transition: var(--tr) }
    .card-body .form-control:focus { border-color: var(--brand); box-shadow: 0 0 0 3px rgba(79, 70, 229, .15) }
    .form-group.err .form-control { border-color: var(--danger) }
    .rb-msg { color: var(--danger); font-size: 12px; margin-top: 4px }
    .char-counter-wrap { display: flex; justify-content: flex-end; margin-top: 4px }
    .char-counter { font-size: 11px; font-weight: 600; color: var(--gray-400) }
    .char-counter.warn { color: var(--warning) }
    .char-counter.danger { color: var(--danger) }

    .bu-table th { background: var(--gray-50); font-size: 12px; text-transform: uppercase; color: var(--gray-500); padding: 10px 12px; border-top: 0 }
    .bu-table td { font-size: 13px; padding: 10px 12px; vertical-align: top }
    .bu-table td.pre { white-space: pre-line; max-width: 320px }
    .bu-table tr.editing td { background: var(--brand-bg) }

    .t-item { background: white; border-radius: var(--r); padding: 14px 18px; margin-bottom: 10px; box-shadow: var(--sh-lg); display: flex; align-items: center; gap: 12px; font-size: 13.5px; animation: tIn .3s ease; pointer-events: all; border-left: 4px solid }
    .t-item.t-success { border-color: var(--success); color: #065f46 }
    .t-item.t-error { border-color: var(--danger); color: #991b1b }
    @keyframes tIn { from { transform: translateX(110%); opacity: 0 } to { transform: translateX(0); opacity: 1 } }
</style>

<?php if ($periode): ?>
    <script>
        (function () {
            var csrf = document.getElementById('rbCsrf');
            var base = '<?= site_url('kegiatanpengembangan') ?>';
            var form = document.getElementById('rbForm');

            function toast(msg, type) {
                type = type || 'success';
                var el = document.createElement('div');
                el.className = 't-item t-' + type;
                el.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + ' fa-lg"></i><div></div>';
                el.lastChild.textContent = msg;
                document.getElementById('toast-wrap').appendChild(el);
                setTimeout(function () { el.style.transition = 'opacity .4s'; el.style.opacity = '0'; setTimeout(function () { el.remove(); }, 400); }, 3500);
            }

            function post(url, fd) {
                fd.set(csrf.name, csrf.value);
                return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' })
                    .then(function (r) {
                        if (r.status === 403 && (r.headers.get('content-type') || '').indexOf('json') < 0) {
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

            function reloadSoon() { setTimeout(function () { location.reload(); }, 700); }

            // ---- approval
            document.querySelectorAll('.rb-act').forEach(function (b) {
                b.addEventListener('click', function () {
                    var cancel = b.dataset.act === 'cancel';
                    if (!confirm(cancel ? 'Batalkan approval ini?' : 'Setujui data Kegiatan Pengembangan ini?')) return;
                    b.disabled = true;
                    var fd = new FormData();
                    fd.set('act', b.dataset.act);
                    post(base + '/approve', fd).then(function (j) {
                        toast(j.message || 'Selesai.', j.ok ? 'success' : 'error');
                        if (j.ok) reloadSoon(); else b.disabled = false;
                    }).catch(function () { toast('Terjadi kesalahan server.', 'error'); b.disabled = false; });
                });
            });

            // ---- counter karakter (semua textarea ber-data-max di halaman)
            function count(el) {
                var max = +el.dataset.max, len = el.value.length;
                var c = document.querySelector('[data-cnt="' + el.name + '"]');
                if (!c) return;
                c.textContent = len + ' / ' + max;
                c.classList.remove('warn', 'danger');
                if (len >= max) c.classList.add('danger'); else if (len >= max * 0.85) c.classList.add('warn');
            }
            document.querySelectorAll('[data-max]').forEach(count);
            document.addEventListener('input', function (e) { if (e.target.dataset && e.target.dataset.max) count(e.target); });

            function clearErr(f) {
                f.querySelectorAll('.err').forEach(function (n) { n.classList.remove('err'); });
                f.querySelectorAll('[data-msg]').forEach(function (n) { n.textContent = ''; });
            }

            // Kirim form: tombol spinner, tampilkan error per field, reload bila sukses.
            function submitForm(f, url, btn) {
                var ori = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                btn.disabled = true;
                clearErr(f);
                post(url, new FormData(f)).then(function (j) {
                    toast(j.message || 'Selesai.', j.ok ? 'success' : 'error');
                    Object.keys(j.errors || {}).forEach(function (k) {
                        var w = f.querySelector('[data-f="' + k + '"]');
                        var m = f.querySelector('[data-msg="' + k + '"]');
                        if (w) w.classList.add('err');
                        if (m) m.textContent = j.errors[k];
                    });
                    if (j.ok) reloadSoon();
                }).catch(function () {
                    toast('Terjadi kesalahan server.', 'error');
                }).then(function () { btn.innerHTML = ori; btn.disabled = false; });
            }

            // ---- keterangan (footer)
            var ketForm = document.getElementById('ketForm');
            var ketBtn = document.getElementById('ketSave');
            if (ketForm && ketBtn) {
                ketForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    submitForm(ketForm, base + '/savefooter', ketBtn);
                });
            }

            if (!form) return;   // tanpa hak edit: hanya approval

            var idEl = document.getElementById('f_id');
            var title = document.getElementById('formTitle');
            var btnCancel = document.getElementById('rbCancel');

            function resetForm() {
                form.reset();
                idEl.value = '';
                form.querySelectorAll('[data-max]').forEach(count);
                clearErr(form);
                title.innerHTML = '<i class="fas fa-plus-circle"></i> Tambah Kegiatan Pengembangan';
                btnCancel.style.display = 'none';
                document.querySelectorAll('tr.editing').forEach(function (tr) { tr.classList.remove('editing'); });
            }
            btnCancel.addEventListener('click', resetForm);

            // ---- ubah: isi form dari baris
            document.querySelectorAll('.bu-edit').forEach(function (b) {
                b.addEventListener('click', function () {
                    var r = JSON.parse(b.dataset.row);
                    resetForm();
                    idEl.value = r.id;
                    ['kegiatanpengembangan', 'tanggalpelaksana', 'pihakpelaksana', 'kategoripeserta', 'jumlahpeserta', 'uraiankegiatan']
                        .forEach(function (k) { form.elements[k].value = r[k] == null ? '' : r[k]; });
                    form.querySelectorAll('[data-max]').forEach(count);
                    title.innerHTML = '<i class="fas fa-edit"></i> Ubah Kegiatan Pengembangan';
                    btnCancel.style.display = '';
                    b.closest('tr').classList.add('editing');
                    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            });

            // ---- hapus
            document.querySelectorAll('.bu-del').forEach(function (b) {
                b.addEventListener('click', function () {
                    if (!confirm('Hapus data ini?')) return;
                    b.disabled = true;
                    var fd = new FormData();
                    fd.set('id', b.dataset.id);
                    post(base + '/delete', fd).then(function (j) {
                        toast(j.message || 'Selesai.', j.ok ? 'success' : 'error');
                        if (j.ok) reloadSoon(); else b.disabled = false;
                    }).catch(function () { toast('Terjadi kesalahan server.', 'error'); b.disabled = false; });
                });
            });

            // ---- simpan kegiatan
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                submitForm(form, base + '/save', document.getElementById('rbSave'));
            });
        })();
    </script>
<?php endif; ?>

<?php if (!empty($periode)): ?>
    <?= view('templates/komentar', ['komentarPage' => 'Kegiatanpengembangan']) ?>
<?php endif; ?>
