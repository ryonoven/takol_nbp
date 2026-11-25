<?php

$errors = session('errors') ?? [];
$editId = (int) (session('edit_id') ?? 0);   // edit gagal validasi -> modal edit dibuka kembali
$hasOld = (bool) old('action');
$oldFor = static fn(string $action, string $key, $default = '') => $hasOld && old('action') === $action ? old($key) : $default;
$rupiah = static fn($n) => (string) $n !== '' && (float) $n > 0 ? number_format((float) $n, 0, ',', '.') : '';
$fmtDate = static fn(?string $d, string $f) => ($d && ($ts = strtotime($d))) ? date($f, $ts) : '';

$fields = static function (string $p, array $v) use ($rupiah, $jenisKoreksi, $nomorsuratMax): void {
    $isK = ($v['kodejenis'] ?? 'R') === 'K'; ?>
    <div class="ps-group">
        <label class="ps-label" for="<?= $p ?>date"><i class="fas fa-calendar-day"></i>Tanggal Pelaporan</label>
        <input type="date" id="<?= $p ?>date" name="date" class="ps-input ps-plain js-req" value="<?= esc($v['date'] ?? '') ?>">
        <div class="ps-err">Tanggal pelaporan harus diisi.</div>
    </div>

    <div class="ps-group">
        <label class="ps-label"><i class="fas fa-file-alt"></i>Jenis Pelaporan</label>
        <div class="ps-jenis-group" role="radiogroup">
            <?php foreach (['R' => ['Rutin', 'fa-sync-alt'], 'K' => ['Koreksi', 'fa-edit']] as $val => [$lbl, $ico]): ?>
                <label class="ps-jenis ps-jenis-<?= strtolower($val) ?>">
                    <input type="radio" name="kodejenis" value="<?= $val ?>" class="js-jenis" <?= ($v['kodejenis'] ?? 'R') === $val ? 'checked' : '' ?>>
                    <i class="fas <?= $ico ?>"></i><?= $lbl ?>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="ps-koreksi js-koreksi" <?= $isK ? '' : 'hidden' ?>>
        <div class="ps-group">
            <label class="ps-label" for="<?= $p ?>nomorsurat"><i class="fas fa-envelope"></i>Nomor Surat</label>
            <input type="text" id="<?= $p ?>nomorsurat" name="nomorsurat" class="ps-input ps-plain js-kreq" maxlength="<?= (int) $nomorsuratMax ?>"
                placeholder="Contoh: 001/SURAT/VI/2025" autocomplete="off" value="<?= esc($v['nomorsurat'] ?? '') ?>" <?= $isK ? '' : 'disabled' ?>>
            <div class="ps-err">Nomor surat wajib diisi untuk pelaporan Koreksi.</div>
        </div>
        <div class="ps-group mb-0">
            <label class="ps-label" for="<?= $p ?>jeniskoreksi"><i class="fas fa-tag"></i>Jenis Koreksi</label>
            <select id="<?= $p ?>jeniskoreksi" name="jeniskoreksi" class="ps-select js-kreq" <?= $isK ? '' : 'disabled' ?>>
                <option value="">Pilih Jenis Koreksi</option>
                <?php foreach ($jenisKoreksi as $k => $lbl): ?>
                    <option value="<?= $k ?>" <?= (string) ($v['jeniskoreksi'] ?? '') === (string) $k ? 'selected' : '' ?>><?= esc($lbl) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="ps-err">Jenis koreksi harus dipilih.</div>
        </div>
    </div>

    <div class="ps-group">
        <label class="ps-label" for="<?= $p ?>modalinti"><i class="fas fa-coins"></i>Modal Inti</label>
        <div class="ps-input-wrap">
            <span class="ps-prefix">Rp</span>
            <input type="text" id="<?= $p ?>modalinti" name="modalinti" class="ps-input js-rupiah js-modal" placeholder="0"
                inputmode="numeric" autocomplete="off" value="<?= $rupiah($v['modalinti'] ?? '') ?>">
        </div>
        <div class="ps-inline mt-2 js-kat" hidden></div>
        <div class="ps-err">Modal inti harus diisi.</div>
    </div>

    <div class="ps-row">
        <div class="ps-group">
            <label class="ps-label" for="<?= $p ?>totalaset"><i class="fas fa-chart-bar"></i>Total Aset</label>
            <div class="ps-input-wrap">
                <span class="ps-prefix">Rp</span>
                <input type="text" id="<?= $p ?>totalaset" name="totalaset" class="ps-input js-rupiah" placeholder="0"
                    inputmode="numeric" autocomplete="off" value="<?= $rupiah($v['totalaset'] ?? '') ?>">
            </div>
            <div class="ps-err">Total aset harus diisi.</div>
        </div>
        <div class="ps-group">
            <label class="ps-label" for="<?= $p ?>kantorcabang"><i class="fas fa-building"></i>Kantor Cabang</label>
            <input type="number" id="<?= $p ?>kantorcabang" name="kantorcabang" class="ps-input ps-plain js-int" placeholder="0"
                min="0" step="1" inputmode="numeric" value="<?= esc((string) ($v['kantorcabang'] ?? '')) ?>">
            <div class="ps-err">Isi jumlah kantor cabang (0 bila tidak ada).</div>
        </div>
    </div>
<?php };
?>
<link rel="stylesheet" href="<?= base_url('asset/css/periode.css') ?>?v=<?= @filemtime(FCPATH . 'asset/css/periode.css') ?>">
<div class="container-fluid px-3 px-md-4 py-4">

    <div class="mb-4">
        <h1 class="ps-title"><i class="fas fa-shield-alt"></i>&nbsp;Periode Laporan Strategi Anti Fraud</h1>
        <p class="ps-sub">Kelola periode laporan strategi anti fraud</p>
    </div>

    <?php if ($msg = session()->getFlashdata('message')): ?>
        <div class="alert alert-success ps-alert js-autohide" role="alert"><i class="fas fa-check-circle"></i> <?= esc($msg) ?></div>
    <?php endif; ?>
    <?php if ($err = session()->getFlashdata('error')): ?>
        <div class="alert alert-danger ps-alert" role="alert"><i class="fas fa-exclamation-circle"></i> <?= esc($err) ?></div>
    <?php endif; ?>
    <?php if ($errors): ?>
        <div class="alert alert-danger ps-alert" role="alert">
            <i class="fas fa-exclamation-triangle"></i> Periksa kembali isian:
            <ul class="mb-0 mt-1"><?php foreach ($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <div class="row">
        <?php if ($canManagePeriode): ?>
            <!-- Buat periode -->
            <div class="col-12 col-lg-5 col-xl-4 mb-4">
                <div class="ps-card h-100">
                    <div class="ps-card-header"><h5 class="mb-0"><i class="fas fa-plus-circle"></i>&nbsp;Buat Periode Baru</h5></div>
                    <div class="ps-card-body">
                        <form action="<?= base_url('periodesaf/handlePeriode') ?>" method="post" class="js-form" novalidate>
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="create">

                            <div class="ps-row">
                                <div class="ps-group">
                                    <label class="ps-label" for="c_semester"><i class="fas fa-list-ol"></i>Semester</label>
                                    <select id="c_semester" name="semester" class="ps-select js-req">
                                        <option value="">Pilih</option>
                                        <?php foreach (['1', '2'] as $s): ?>
                                            <option value="<?= $s ?>" <?= (string) $oldFor('create', 'semester') === $s ? 'selected' : '' ?>>Semester <?= $s ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="ps-err">Pilih semester.</div>
                                </div>
                                <div class="ps-group">
                                    <label class="ps-label" for="c_tahun"><i class="fas fa-calendar"></i>Tahun</label>
                                    <select id="c_tahun" name="tahun" class="ps-select js-req">
                                        <option value="">Pilih</option>
                                        <?php for ($y = $tahunMin; $y <= $tahunMax; $y++): ?>
                                            <option value="<?= $y ?>" <?= (int) $oldFor('create', 'tahun', date('Y')) === $y ? 'selected' : '' ?>><?= $y ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <div class="ps-err">Pilih tahun.</div>
                                </div>
                            </div>

                            <div class="ps-group">
                                <label class="ps-label" for="c_jenispelaporan"><i class="fas fa-calendar-alt"></i>Periode Pelaporan</label>
                                <select id="c_jenispelaporan" name="jenispelaporan" class="ps-select js-req">
                                    <option value="">Pilih Periode Pelaporan</option>
                                    <?php foreach ($jenisLaporan as $k => [$lbl]): ?>
                                        <option value="<?= $k ?>" <?= $oldFor('create', 'jenispelaporan') === $k ? 'selected' : '' ?>><?= esc($k === 'SAFTI3' ? 'Laporan ' . $lbl : $lbl) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="ps-err">Periode pelaporan harus dipilih.</div>
                            </div>

                            <?php $fields('c_', [
                                'date' => $oldFor('create', 'date', date('Y-m-d')),
                                'kodejenis' => $oldFor('create', 'kodejenis', 'R'),
                                'nomorsurat' => $oldFor('create', 'nomorsurat'),
                                'jeniskoreksi' => $oldFor('create', 'jeniskoreksi'),
                                'modalinti' => $oldFor('create', 'modalinti'),
                                'totalaset' => $oldFor('create', 'totalaset'),
                                'kantorcabang' => $oldFor('create', 'kantorcabang'),
                            ]); ?>

                            <button type="submit" class="ps-btn ps-btn-create w-100 js-submit">
                                <i class="fas fa-plus-circle"></i><span class="js-submit-txt">Buat Periode Baru</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Daftar periode -->
        <div class="col-12 <?= $canManagePeriode ? 'col-lg-7 col-xl-8' : '' ?> mb-4">
            <div class="ps-card h-100">
                <div class="ps-card-header"><h5 class="mb-0"><i class="fas fa-list"></i>&nbsp;Daftar Periode Tersedia</h5></div>
                <div class="ps-card-body">
                    <?php if (!$periodes): ?>
                        <div class="ps-empty">
                            <div class="ps-empty-icon"><i class="fas fa-folder-open"></i></div>
                            <h6>Belum Ada Periode</h6>
                            <p class="mb-0">Silakan buat periode baru untuk memulai pelaporan</p>
                        </div>
                    <?php else: ?>
                        <div class="ps-list">
                            <?php foreach ($periodes as $p):
                                $isActive = $current_periode === (int) $p['id'];
                                $isK = ($p['kodejenis'] ?? 'R') === 'K';
                                $jk = (string) ($p['jeniskoreksi'] ?? '0'); ?>
                                <div class="ps-item-wrap">
                                    <form action="<?= base_url('periodesaf/handlePeriode') ?>" method="post" class="ps-item-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="select">
                                        <input type="hidden" name="periode_id" value="<?= (int) $p['id'] ?>">
                                        <button type="submit" class="ps-item<?= $isActive ? ' active' : '' ?>">
                                            <div class="ps-item-icon"><i class="fas fa-calendar-check"></i></div>
                                            <div class="ps-item-body">
                                                <h6 class="ps-item-title">Semester <?= esc($p['semester']) ?> — Tahun <?= esc($p['tahun']) ?></h6>
                                                <div class="ps-badges">
                                                    <span class="ps-badge <?= $isK ? 'ps-badge-k' : 'ps-badge-r' ?>"><i class="fas <?= $isK ? 'fa-edit' : 'fa-sync-alt' ?>"></i> <?= $isK ? 'Koreksi' : 'Rutin' ?></span>
                                                    <span class="ps-badge ps-badge-n"><i class="fas fa-calendar-alt"></i> <?= esc($jenisLaporan[$p['jenispelaporan']][0] ?? $p['jenispelaporan']) ?></span>
                                                    <?php if (isset($jenisKoreksi[$jk])): ?>
                                                        <span class="ps-badge ps-badge-n"><i class="fas fa-tag"></i> <?= esc($jenisKoreksi[$jk]) ?></span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($p['nomorsurat'])): ?>
                                                        <span class="ps-badge ps-badge-n"><i class="fas fa-envelope"></i> <?= esc($p['nomorsurat']) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="ps-details">
                                                    <span><i class="fas fa-coins"></i> Modal: <strong>Rp <?= $rupiah($p['modalinti']) ?: '0' ?></strong></span>
                                                    <span><i class="fas fa-chart-bar"></i> Aset: <strong>Rp <?= $rupiah($p['totalaset']) ?: '0' ?></strong></span>
                                                    <span><i class="fas fa-building"></i> Cabang: <strong><?= (int) $p['kantorcabang'] ?></strong></span>
                                                    <?php if ($d = $fmtDate($p['date'] ?? null, 'd M Y')): ?>
                                                        <span><i class="fas fa-calendar-day"></i> Tgl lapor: <strong><?= $d ?></strong></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php if ($isActive): ?>
                                                <div class="ps-active-badge"><i class="fas fa-check-circle"></i> Aktif</div>
                                            <?php else: ?>
                                                <i class="fas fa-chevron-right ps-arrow"></i>
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                    <?php if ($canManagePeriode): ?>
                                        <button type="button" class="ps-btn-edit js-edit" title="Edit periode" aria-label="Edit periode"
                                            data-id="<?= (int) $p['id'] ?>"
                                            data-label="Semester <?= esc($p['semester'], 'attr') ?> — Tahun <?= esc($p['tahun'], 'attr') ?>"
                                            data-f-date="<?= esc($fmtDate($p['date'] ?? null, 'Y-m-d'), 'attr') ?>"
                                            data-f-kodejenis="<?= $isK ? 'K' : 'R' ?>"
                                            data-f-nomorsurat="<?= esc($p['nomorsurat'] ?? '', 'attr') ?>"
                                            data-f-jeniskoreksi="<?= isset($jenisKoreksi[$jk]) ? $jk : '' ?>"
                                            data-f-modalinti="<?= (int) $p['modalinti'] ?>" data-f-totalaset="<?= (int) $p['totalaset'] ?>"
                                            data-f-kantorcabang="<?= (int) $p['kantorcabang'] ?>">
                                            <i class="fas fa-pencil-alt"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($canManagePeriode): ?>
    <!-- Modal edit -->
    <div class="ps-overlay" id="modalEdit" hidden data-reopen="<?= $editId ?: '' ?>">
        <div class="ps-modal" role="dialog" aria-modal="true" aria-labelledby="mEditTitle">
            <div class="ps-modal-header">
                <div><div class="ps-mh-title" id="mEditTitle"><i class="fas fa-pencil-alt"></i> Edit Periode</div><div class="ps-mh-sub" id="mEditLabel">—</div></div>
                <button type="button" class="ps-mh-close js-close" aria-label="Tutup"><i class="fas fa-times"></i></button>
            </div>
            <form action="<?= base_url('periodesaf/handlePeriode') ?>" method="post" class="js-form" id="editForm" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="periode_id" id="editId" value="<?= $editId ?: '' ?>">
                <div class="ps-modal-body">
                    <?php $fields('e_', [
                        'date' => $oldFor('edit', 'date'),
                        'kodejenis' => $oldFor('edit', 'kodejenis', 'R'),
                        'nomorsurat' => $oldFor('edit', 'nomorsurat'),
                        'jeniskoreksi' => $oldFor('edit', 'jeniskoreksi'),
                        'modalinti' => $oldFor('edit', 'modalinti'),
                        'totalaset' => $oldFor('edit', 'totalaset'),
                        'kantorcabang' => $oldFor('edit', 'kantorcabang'),
                    ]); ?>
                </div>
                <div class="ps-modal-footer">
                    <button type="button" class="ps-btn ps-btn-cancel js-close"><i class="fas fa-times"></i> Batal</button>
                    <button type="submit" class="ps-btn ps-btn-create js-submit"><i class="fas fa-save"></i><span class="js-submit-txt">Simpan Perubahan</span></button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>


<script src="<?= base_url('asset/js/periode.js') ?>?v=<?= @filemtime(FCPATH . 'asset/js/periode.js') ?>" defer></script>
