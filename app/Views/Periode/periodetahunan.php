<?php
/**
 * Periode Laporan Transparansi Tahunan.
 * Variabel: $periodes, $current_periode, $tahunMin, $tahunMax, $nomorsuratMax
 */
$errors = session('errors') ?? [];
$editId = (int) (session('edit_id') ?? 0);       // edit gagal validasi -> buka kembali modal edit
$hasOld = (bool) old('action');
$oldFor = static fn(string $action, string $key, $default = '') => $hasOld && old('action') === $action ? old($key) : $default;
$rupiah = static fn($n) => (string) $n !== '' && (float) $n > 0 ? number_format((float) $n, 0, ',', '.') : '';

/**
 * Field bersama form Buat & Edit (jenis pelaporan, nomor surat, modal inti, total aset).
 * $p = prefix id elemen, $v = nilai awal.
 */
$fields = static function (string $p, array $v) use ($rupiah, $nomorsuratMax): void {
    $isK = ($v['jenispelaporan'] ?? 'R') === 'K'; ?>
    <div class="pt-group">
        <label class="pt-label"><i class="fas fa-file-alt mr-1"></i>Jenis Pelaporan</label>
        <div class="pt-jenis-group" role="radiogroup">
            <?php foreach (['R' => ['Rutin', 'fa-sync-alt'], 'K' => ['Koreksi', 'fa-edit']] as $val => [$lbl, $ico]): ?>
                <label class="pt-jenis pt-jenis-<?= strtolower($val) ?>">
                    <input type="radio" name="jenispelaporan" value="<?= $val ?>" class="js-jenis" <?= ($v['jenispelaporan'] ?? 'R') === $val ? 'checked' : '' ?>>
                    <i class="fas <?= $ico ?>"></i><?= $lbl ?>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="pt-group js-nomorsurat-wrap" <?= $isK ? '' : 'hidden' ?>>
        <label class="pt-label" for="<?= $p ?>nomorsurat"><i class="fas fa-envelope-open-text mr-1"></i>Nomor Surat Koreksi</label>
        <input type="text" id="<?= $p ?>nomorsurat" name="nomorsurat" class="pt-input pt-input-plain js-nomorsurat"
            maxlength="<?= (int) $nomorsuratMax ?>" autocomplete="off" placeholder="Contoh: 001/BPR/DIR/I/2025"
            value="<?= esc($v['nomorsurat'] ?? '') ?>" <?= $isK ? 'required' : 'disabled' ?>>
        <div class="pt-hint">Wajib diisi untuk pelaporan koreksi, maks. <?= (int) $nomorsuratMax ?> karakter.</div>
        <div class="pt-err">Nomor surat wajib diisi untuk pelaporan Koreksi.</div>
    </div>

    <div class="pt-group">
        <label class="pt-label" for="<?= $p ?>modalinti"><i class="fas fa-coins mr-1"></i>Modal Inti</label>
        <div class="pt-input-wrap">
            <span class="pt-prefix">Rp</span>
            <input type="text" id="<?= $p ?>modalinti" name="modalinti" class="pt-input js-rupiah js-modal" placeholder="0"
                inputmode="numeric" autocomplete="off" value="<?= $rupiah($v['modalinti'] ?? '') ?>">
        </div>
        <div class="pt-inline mt-2 js-kat-info" hidden></div>
        <div class="pt-err">Modal inti harus diisi.</div>
    </div>

    <div class="pt-group">
        <label class="pt-label" for="<?= $p ?>totalaset"><i class="fas fa-chart-bar mr-1"></i>Total Aset</label>
        <div class="pt-input-wrap">
            <span class="pt-prefix">Rp</span>
            <input type="text" id="<?= $p ?>totalaset" name="totalaset" class="pt-input js-rupiah" placeholder="0"
                inputmode="numeric" autocomplete="off" value="<?= $rupiah($v['totalaset'] ?? '') ?>">
        </div>
        <div class="pt-err">Total aset harus diisi.</div>
    </div>
<?php };
?>
<div class="container-fluid px-3 px-md-4 py-4">

    <div class="mb-4">
        <h1 class="pt-title"><i class="fas fa-file-invoice me-2"></i>&nbsp;Periode Laporan Transparansi Tahunan</h1>
        <p class="pt-sub">Kelola periode laporan transparansi tahunan</p>
    </div>

    <?php if ($msg = session()->getFlashdata('message')): ?>
        <div class="alert alert-success pt-alert" role="alert"><i class="fas fa-check-circle me-2"></i> <?= esc($msg) ?></div>
    <?php endif; ?>
    <?php if ($err = session()->getFlashdata('error')): ?>
        <div class="alert alert-danger pt-alert" role="alert"><i class="fas fa-exclamation-circle me-2"></i> <?= esc($err) ?></div>
    <?php endif; ?>
    <?php if ($errors): ?>
        <div class="alert alert-danger pt-alert" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i> Periksa kembali isian:
            <ul class="mb-0 mt-1"><?php foreach ($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Buat periode -->
        <div class="col-12 col-lg-5 col-xl-4 mb-4">
            <div class="pt-card shadow-sm h-100">
                <div class="pt-card-header">
                    <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>&nbsp;Buat Periode Baru</h5>
                </div>
                <div class="pt-card-body">
                    <form action="<?= base_url('periodetahunan/handlePeriode') ?>" method="post" class="js-periode-form" novalidate>
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="create">

                        <div class="pt-group">
                            <label class="pt-label" for="c_tahun"><i class="fas fa-calendar mr-1"></i>Tahun</label>
                            <select id="c_tahun" name="tahun" class="pt-select js-required">
                                <option value="">Pilih Tahun</option>
                                <?php for ($y = $tahunMin; $y <= $tahunMax; $y++): ?>
                                    <option value="<?= $y ?>" <?= (int) $oldFor('create', 'tahun', date('Y')) === $y ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                            <div class="pt-err">Tahun harus dipilih.</div>
                        </div>

                        <?php $fields('c_', [
                            'jenispelaporan' => $oldFor('create', 'jenispelaporan', 'R'),
                            'nomorsurat' => $oldFor('create', 'nomorsurat'),
                            'modalinti' => $oldFor('create', 'modalinti'),
                            'totalaset' => $oldFor('create', 'totalaset'),
                        ]); ?>

                        <button type="submit" class="pt-btn-create w-100 js-submit">
                            <i class="fas fa-plus-circle mr-1"></i><span class="js-submit-txt">Buat Periode Baru</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Daftar periode -->
        <div class="col-12 col-lg-7 col-xl-8 mb-4">
            <div class="pt-card shadow-sm h-100">
                <div class="pt-card-header">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>&nbsp;Daftar Periode Tersedia</h5>
                </div>
                <div class="pt-card-body">
                    <?php if (!$periodes): ?>
                        <div class="pt-empty">
                            <div class="pt-empty-icon"><i class="fas fa-folder-open"></i></div>
                            <h6>Belum Ada Periode</h6>
                            <p>Silakan buat periode baru untuk memulai pelaporan</p>
                        </div>
                    <?php else: ?>
                        <div class="pt-list">
                            <?php foreach ($periodes as $p):
                                $isActive = $current_periode === (int) $p['id'];
                                $isK = ($p['jenispelaporan'] ?? '') === 'K'; ?>
                                <div class="pt-item-wrap">
                                    <form action="<?= base_url('periodetahunan/handlePeriode') ?>" method="post" class="pt-item-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="select">
                                        <input type="hidden" name="periode_id" value="<?= (int) $p['id'] ?>">
                                        <button type="submit" class="pt-item<?= $isActive ? ' active' : '' ?>">
                                            <div class="pt-item-icon"><i class="fas fa-calendar-check"></i></div>
                                            <div class="pt-item-body">
                                                <h6 class="pt-item-title">Laporan Transparansi Tahun <?= esc($p['tahun']) ?></h6>
                                                <div class="mb-1">
                                                    <?php if ($isK): ?>
                                                        <span class="pt-badge pt-badge-k"><i class="fas fa-edit"></i> Koreksi</span>
                                                        <?php if (!empty($p['nomorsurat'])): ?>
                                                            <span class="pt-badge pt-badge-surat"><i class="fas fa-envelope"></i> <?= esc($p['nomorsurat']) ?></span>
                                                        <?php else: ?>
                                                            <span class="pt-badge pt-badge-warn"><i class="fas fa-exclamation-triangle"></i> Nomor surat belum diisi</span>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="pt-badge pt-badge-r"><i class="fas fa-sync-alt"></i> Rutin</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="pt-details">
                                                    <span><i class="fas fa-coins"></i> Modal Inti: <strong><?= $rupiah($p['modalinti']) !== '' ? 'Rp ' . $rupiah($p['modalinti']) : '—' ?></strong></span>
                                                    <span><i class="fas fa-chart-bar"></i> Total Aset: <strong><?= $rupiah($p['totalaset']) !== '' ? 'Rp ' . $rupiah($p['totalaset']) : '—' ?></strong></span>
                                                </div>
                                                <small class="pt-date"><i class="fas fa-clock"></i> <?= esc(date('d M Y, H:i', strtotime($p['created_at']))) ?></small>
                                            </div>
                                            <?php if ($isActive): ?>
                                                <div class="pt-active-badge"><i class="fas fa-check-circle"></i> Aktif</div>
                                            <?php else: ?>
                                                <i class="fas fa-chevron-right pt-arrow"></i>
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                    <button type="button" class="pt-btn-edit js-edit" title="Edit periode"
                                        data-id="<?= (int) $p['id'] ?>" data-tahun="<?= esc($p['tahun']) ?>"
                                        data-modal="<?= esc((string) (int) $p['modalinti']) ?>" data-aset="<?= esc((string) (int) $p['totalaset']) ?>"
                                        data-jenis="<?= $isK ? 'K' : 'R' ?>" data-nomorsurat="<?= esc($p['nomorsurat'] ?? '') ?>">
                                        <i class="fas fa-pencil-alt"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal edit -->
<div class="pt-overlay" id="modalEdit" hidden>
    <div class="pt-modal" role="dialog" aria-modal="true" aria-labelledby="mEditTitle">
        <div class="pt-modal-header" id="mEditTitle"><i class="fas fa-pencil-alt mr-1"></i>Edit Periode Tahun&nbsp;<span id="mEditTahun">-</span></div>
        <form action="<?= base_url('periodetahunan/handlePeriode') ?>" method="post" class="js-periode-form" id="editForm" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="periode_id" id="editId" value="<?= $editId ?: '' ?>">
            <div class="pt-modal-body">
                <?php $fields('e_', [
                    'jenispelaporan' => $oldFor('edit', 'jenispelaporan', 'R'),
                    'nomorsurat' => $oldFor('edit', 'nomorsurat'),
                    'modalinti' => $oldFor('edit', 'modalinti'),
                    'totalaset' => $oldFor('edit', 'totalaset'),
                ]); ?>
            </div>
            <div class="pt-modal-footer">
                <button type="button" class="btn btn-outline-secondary pt-btn-cancel" id="btnCloseModal"><i class="fas fa-times mr-1"></i>Batal</button>
                <button type="submit" class="pt-btn-save js-submit">
                    <i class="fas fa-save mr-1"></i><span class="js-submit-txt">Simpan Perubahan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    .pt-title { font-size: clamp(1.25rem, 4vw, 1.75rem); font-weight: 700; color: #1f2937; margin-bottom: .5rem; display: flex; align-items: center; }
    .pt-sub { color: #6b7280; font-size: clamp(.875rem, 2vw, .95rem); margin: 0; }
    .pt-alert { border: none; border-radius: 12px; padding: 1rem 1.25rem; box-shadow: 0 2px 8px rgba(0, 0, 0, .08); transition: opacity .4s; }

    .pt-card { border: none; border-radius: 16px; overflow: hidden; background: #fff; }
    .pt-card-header { padding: 1.1rem 1.5rem; background: linear-gradient(135deg, #1f2aa8, #141863); color: #fff; }
    .pt-card-header h5 { color: #fff; font-size: clamp(.95rem, 2.5vw, 1.1rem); font-weight: 600; }
    .pt-card-body { padding: clamp(1rem, 3vw, 1.5rem); }

    .pt-group { margin-bottom: 1rem; }
    .pt-label { font-size: .9rem; font-weight: 600; color: #374151; margin-bottom: .5rem; display: flex; align-items: center; }
    .pt-hint { font-size: .75rem; color: #6b7280; margin-top: .25rem; }
    .pt-select, .pt-input {
        width: 100%; padding: .7rem 1rem; font-size: .95rem; color: #1f2937; background: #fff;
        border: 2px solid #e5e7eb; border-radius: 10px; transition: border-color .2s, box-shadow .2s;
    }
    .pt-select {
        appearance: none; padding-right: 2.5rem; background-repeat: no-repeat; background-position: right 12px center; background-size: 10px 6px;
        background-image: url('data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 6"%3E%3Cpath d="M0 0l5 5 5-5z" fill="none" stroke="%236b7280" stroke-width="1.5"/%3E%3C/svg%3E');
    }
    .pt-select:focus, .pt-input:focus { outline: none; border-color: #1f2aa8; box-shadow: 0 0 0 3px rgba(31, 42, 168, .1); }
    .pt-input { padding-left: 3rem; }
    .pt-input.pt-input-plain { padding-left: 1rem; }
    .is-invalid { border-color: #dc3545 !important; box-shadow: 0 0 0 3px rgba(220, 53, 69, .1) !important; }
    .pt-err { display: none; font-size: .8rem; color: #dc3545; margin-top: .25rem; }
    .pt-group.has-error .pt-err { display: block; }
    .pt-group.has-error .pt-hint { display: none; }
    .pt-input-wrap { position: relative; display: flex; }
    .pt-prefix { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); font-size: .9rem; font-weight: 600; color: #6b7280; pointer-events: none; }

    .pt-inline { border-radius: 8px; padding: .5rem .75rem; font-size: .8rem; font-weight: 500; }
    .pt-inline.warn { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
    .pt-inline.info { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
    .pt-inline.ok { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }

    /* Jenis pelaporan: radio bergaya tombol */
    .pt-jenis-group { display: flex; gap: 8px; }
    .pt-jenis {
        flex: 1; height: 48px; margin: 0; border: 2px solid #e5e7eb; border-radius: 10px; background: #fff;
        display: flex; align-items: center; justify-content: center; gap: 8px;
        font-size: .95rem; font-weight: 600; color: #6b7280; cursor: pointer; transition: border-color .2s, background .2s, color .2s;
    }
    .pt-jenis input { position: absolute; opacity: 0; pointer-events: none; }
    .pt-jenis:has(input:focus-visible) { box-shadow: 0 0 0 3px rgba(31, 42, 168, .2); }
    .pt-jenis-r:has(input:checked) { border-color: #1f2aa8; background: #eff4ff; color: #1f2aa8; }
    .pt-jenis-k:has(input:checked) { border-color: #d97706; background: #fffbeb; color: #92400e; }
    .js-nomorsurat-wrap { padding: .75rem; margin-top: -.25rem; border-radius: 10px; background: #fffbeb; border: 1px dashed #fcd34d; animation: ptFade .2s ease; }
    @keyframes ptFade { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: none; } }

    .pt-btn-create, .pt-btn-save {
        display: flex; align-items: center; justify-content: center; gap: 4px; border: none; border-radius: 10px;
        background: linear-gradient(135deg, #1fa85a, #146337); color: #fff; font-weight: 600; cursor: pointer; transition: transform .2s, box-shadow .2s;
    }
    .pt-btn-create { padding: .85rem 1.5rem; font-size: 1rem; }
    .pt-btn-save { flex: 2; height: 46px; font-size: .95rem; }
    .pt-btn-create:hover, .pt-btn-save:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(16, 185, 129, .3); }
    .pt-btn-create:disabled, .pt-btn-save:disabled { opacity: .7; cursor: not-allowed; transform: none; }

    .pt-empty { text-align: center; padding: 3rem 1rem; color: #6b7280; }
    .pt-empty-icon { font-size: 3.5rem; color: #d1d5db; margin-bottom: 1rem; }

    .pt-list { display: flex; flex-direction: column; gap: .75rem; max-height: 520px; overflow-y: auto; padding-right: .5rem; }
    .pt-item-wrap { display: flex; align-items: stretch; gap: 8px; }
    .pt-item-form { flex: 1; min-width: 0; margin: 0; }
    .pt-item {
        width: 100%; display: flex; align-items: center; padding: 1rem; text-align: left; cursor: pointer;
        background: #f9fafb; border: 2px solid #e5e7eb; border-radius: 12px; transition: border-color .2s, transform .2s, box-shadow .2s;
    }
    .pt-item:hover { background: #fff; border-color: #1f2aa8; transform: translateX(4px); box-shadow: 0 4px 12px rgba(0, 0, 0, .08); }
    .pt-item.active { background: linear-gradient(135deg, #1fa85a, #146337); color: #fff; border-color: transparent; }
    .pt-item-icon {
        width: 44px; height: 44px; margin-right: 1rem; flex-shrink: 0; border-radius: 10px; background: #fff;
        display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: #1f2aa8;
    }
    .pt-item.active .pt-item-icon { background: rgba(255, 255, 255, .2); color: #fff; }
    .pt-item-body { flex: 1; min-width: 0; }
    .pt-item-title { font-size: 1rem; font-weight: 700; color: #1f2937; margin-bottom: .4rem; word-break: break-word; }
    .pt-details { display: flex; flex-wrap: wrap; gap: .75rem; margin-bottom: .4rem; font-size: .8rem; color: #6b7280; }
    .pt-date { font-size: .75rem; color: #9ca3af; }
    .pt-item.active .pt-item-title, .pt-item.active .pt-details, .pt-item.active .pt-date { color: #fff; }

    .pt-badge { display: inline-flex; align-items: center; gap: 4px; padding: .3rem .65rem; border-radius: 6px; font-size: .78rem; font-weight: 600; margin: 0 4px 4px 0; }
    .pt-badge-r { background: #dbeafe; color: #1e40af; }
    .pt-badge-k { background: #fef3c7; color: #92400e; }
    .pt-badge-surat { background: #f3f4f6; color: #374151; word-break: break-all; }
    .pt-badge-warn { background: #fee2e2; color: #991b1b; }
    .pt-item.active .pt-badge { background: rgba(255, 255, 255, .2); color: #fff; }

    .pt-active-badge { background: rgba(255, 255, 255, .2); padding: .5rem .75rem; border-radius: 8px; font-size: .85rem; font-weight: 600; color: #fff; flex-shrink: 0; white-space: nowrap; }
    .pt-arrow { color: #9ca3af; flex-shrink: 0; margin-left: .5rem; transition: transform .2s, color .2s; }
    .pt-item:hover .pt-arrow { color: #1f2aa8; transform: translateX(3px); }
    .pt-btn-edit {
        width: 40px; min-width: 40px; display: flex; align-items: center; justify-content: center; cursor: pointer;
        background: #fff; border: 2px solid #e5e7eb; border-radius: 10px; color: #6b7280; font-size: .85rem; transition: border-color .2s, color .2s, background .2s;
    }
    .pt-btn-edit:hover { border-color: #1f2aa8; color: #1f2aa8; background: #eff4ff; }

    .pt-overlay { position: fixed; inset: 0; z-index: 1050; display: flex; align-items: center; justify-content: center; background: rgba(0, 0, 0, .5); }
    .pt-overlay[hidden] { display: none; }
    .pt-modal {
        width: 480px; max-width: calc(100vw - 2rem); max-height: calc(100vh - 4rem); display: flex; flex-direction: column; overflow: hidden;
        background: #fff; border-radius: 16px; box-shadow: 0 20px 60px rgba(0, 0, 0, .2); animation: ptFade .22s ease;
    }
    .pt-modal-header { padding: 1.25rem 1.5rem; background: linear-gradient(135deg, #1f2aa8, #141863); color: #fff; font-weight: 600; display: flex; align-items: center; }
    .pt-modal-body { padding: 1.5rem; overflow-y: auto; flex: 1; }
    .pt-modal-footer { padding: 1rem 1.5rem; border-top: 1px solid #e5e7eb; display: flex; gap: 10px; }
    .pt-btn-cancel { flex: 1; height: 46px; border-radius: 10px; font-weight: 600; }

    @media (max-width: 991px) { .pt-details { flex-direction: column; gap: .375rem; } .pt-list { max-height: 400px; } }
    @media (max-width: 767px) { .pt-item { flex-wrap: wrap; } .pt-active-badge { margin-top: .5rem; width: 100%; text-align: center; } }
    @media (prefers-reduced-motion: reduce) { * { animation: none !important; transition: none !important; } }
</style>

<script>
    (function () {
        'use strict';

        const MODAL_MIN = 6e9;
        const digits = s => String(s || '').replace(/\D/g, '');
        const toRupiah = s => digits(s) ? parseInt(digits(s), 10).toLocaleString('id-ID') : '';
        const rawNum = el => parseInt(digits(el.value), 10) || 0;
        const groupOf = el => el.closest('.pt-group');

        function setError(el, on) {
            el.classList.toggle('is-invalid', on);
            groupOf(el)?.classList.toggle('has-error', on);
        }

        /** Peringatan bila modal inti di bawah Rp 6 Miliar. */
        function updateKategori(form) {
            const n = rawNum(form.querySelector('.js-modal'));
            const box = form.querySelector('.js-kat-info');
            box.hidden = !(n > 0 && n < MODAL_MIN);
            if (!box.hidden) {
                box.className = 'pt-inline mt-2 js-kat-info warn';
                box.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Modal inti di bawah <strong>Rp 6 Miliar</strong>, silakan cek kembali.';
            }
        }

        /** Tampilkan field Nomor Surat hanya untuk jenis Koreksi. */
        function updateJenis(form, focus) {
            const isK = form.querySelector('.js-jenis:checked')?.value === 'K';
            const wrap = form.querySelector('.js-nomorsurat-wrap');
            const input = form.querySelector('.js-nomorsurat');
            wrap.hidden = !isK;
            input.disabled = !isK;      // disabled -> tidak ikut terkirim saat Rutin
            input.required = isK;
            if (!isK) setError(input, false);
            if (isK && focus) input.focus();
        }

        function validate(form) {
            let first = null;
            const check = (el, bad) => { setError(el, bad); if (bad && !first) first = el; };

            const tahun = form.querySelector('select[name="tahun"]');
            if (tahun) check(tahun, !tahun.value);
            form.querySelectorAll('.js-rupiah').forEach(el => check(el, rawNum(el) <= 0));
            const ns = form.querySelector('.js-nomorsurat');
            if (!ns.disabled) check(ns, ns.value.trim() === '');

            first?.focus();
            return !first;
        }

        document.querySelectorAll('.js-periode-form').forEach(form => {
            form.querySelectorAll('.js-rupiah').forEach(el => {
                el.addEventListener('input', () => {
                    el.value = toRupiah(el.value);
                    if (rawNum(el) > 0) setError(el, false);
                    if (el.classList.contains('js-modal')) updateKategori(form);
                });
            });
            form.querySelectorAll('.js-jenis').forEach(r => r.addEventListener('change', () => updateJenis(form, true)));
            form.querySelector('.js-nomorsurat').addEventListener('input', e => { if (e.target.value.trim()) setError(e.target, false); });
            form.querySelector('select[name="tahun"]')?.addEventListener('change', e => setError(e.target, !e.target.value));

            let locked = false;
            form.addEventListener('submit', e => {
                if (locked || !validate(form)) { e.preventDefault(); return; }
                locked = true;
                form.querySelectorAll('.js-rupiah').forEach(el => { el.value = digits(el.value); }); // kirim angka murni
                const btn = form.querySelector('.js-submit');
                btn.disabled = true;
                btn.querySelector('.js-submit-txt').textContent = 'Memproses…';
            });

            updateJenis(form, false);
            updateKategori(form);
        });

        // ---------------------------------------------------------------- modal edit
        const overlay = document.getElementById('modalEdit');
        const editForm = document.getElementById('editForm');
        let lastTrigger = null;

        function openEdit(d) {
            document.getElementById('editId').value = d.id;
            document.getElementById('mEditTahun').textContent = d.tahun;
            editForm.querySelector('[name="modalinti"]').value = toRupiah(d.modal);
            editForm.querySelector('[name="totalaset"]').value = toRupiah(d.aset);
            editForm.querySelector('.js-nomorsurat').value = d.nomorsurat || '';
            editForm.querySelector('.js-jenis[value="' + (d.jenis === 'K' ? 'K' : 'R') + '"]').checked = true;
            editForm.querySelectorAll('.is-invalid').forEach(el => setError(el, false));
            updateJenis(editForm, false);
            updateKategori(editForm);
            overlay.hidden = false;
            document.body.style.overflow = 'hidden';
            editForm.querySelector('.js-jenis:checked').focus();
        }
        function closeEdit() {
            overlay.hidden = true;
            document.body.style.overflow = '';
            lastTrigger?.focus();
        }

        document.querySelectorAll('.js-edit').forEach(btn => btn.addEventListener('click', () => {
            lastTrigger = btn;
            openEdit(btn.dataset);
        }));
        document.getElementById('btnCloseModal').addEventListener('click', closeEdit);
        overlay.addEventListener('click', e => { if (e.target === overlay) closeEdit(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape' && !overlay.hidden) closeEdit(); });

        // Edit gagal validasi di server -> buka kembali modal dengan isian sebelumnya
        <?php if ($editId): ?>
            (function () {
                const btn = document.querySelector('.js-edit[data-id="<?= $editId ?>"]');
                if (!btn) return;
                lastTrigger = btn;
                document.getElementById('editId').value = btn.dataset.id;
                document.getElementById('mEditTahun').textContent = btn.dataset.tahun;
                updateJenis(editForm, false);
                updateKategori(editForm);
                overlay.hidden = false;
                document.body.style.overflow = 'hidden';
            })();
        <?php endif; ?>

        // Pilih periode: tanda sedang diproses
        document.querySelectorAll('.pt-item-form').forEach(f => f.addEventListener('submit', () => {
            f.querySelector('button').style.opacity = '.6';
        }));

        // Notifikasi sukses hilang otomatis
        setTimeout(() => document.querySelectorAll('.alert-success.pt-alert').forEach(el => {
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 400);
        }), 5000);
    })();
</script>
