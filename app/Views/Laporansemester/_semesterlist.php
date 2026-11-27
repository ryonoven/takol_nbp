<?php
/**
 * Halaman daftar laporan semesteran (App\Libraries\SemesterListController).
 * Variabel: $judul, $cfg, $bprData, $periodeLabel, $prevLabel, $rows, $nullRow, $prevCount, $canEdit, $canApprove
 */
$F = $cfg['fields'];
$route = $cfg['route'];
$all = $nullRow ? array_merge($rows, [$nullRow]) : $rows;
$total = count($all);
$okN = count(array_filter($all, static fn($r) => (int) $r['accdir2'] === 1));
$allOk = $total > 0 && $okN === $total;
$fmtDate = static function ($v) {
    $t = $v ? strtotime((string) $v) : false;
    return ($t && $t > 0 && $v !== '0000-00-00') ? date('d/m/Y', $t) : '—';
};
$fmtDt = static function ($v) {
    $t = $v ? strtotime((string) $v) : false;
    return ($t && $t > 0) ? date('d/m/Y H:i', $t) : '';
};
$long = array_keys(array_filter($F, static fn($f) => $f['type'] === 'textarea'));
$short = array_keys(array_filter($F, static fn($f) => $f['type'] !== 'textarea'));
$card = $cfg['card'] ?? [];            // tampilan kartu orang (mis. Formasi SDM): title, tag, sub, chips, utama
$rowApproval = $cfg['row_approval'] ?? true; // false = persetujuan hanya untuk seluruh data
$optLabel = static fn(string $k, $v) => ($F[$k]['type'] ?? '') === 'select' ? ($F[$k]['options'][$v] ?? (string) $v) : (string) $v;
?>
<link rel="stylesheet" href="<?= base_url('asset/css/transparansi.css') ?>?v=<?= @filemtime(FCPATH . 'asset/css/transparansi.css') ?>">
<div id="pu-toast" class="pu-toast" aria-live="polite"></div>

<div class="container-fluid pu sl">
    <a href="<?= base_url($cfg['back'][0]) ?>" class="pu-back"><i class="fas fa-arrow-left"></i> <?= esc($cfg['back'][1]) ?></a>

    <header class="pu-head">
        <div class="pu-head-main">
            <span class="pu-kicker"><?= esc($cfg['kicker']) ?></span>
            <h1 class="pu-title"><?= esc($judul) ?></h1>
            <div class="pu-meta">
                <span class="pu-bpr"><?= esc($bprData['namabpr'] ?? 'Nama BPR') ?></span>
                <span class="pu-year"><i class="far fa-calendar"></i> <?= $periodeLabel ?></span>
            </div>
        </div>
        <div class="pu-status">
            <div class="pu-chip <?= $allOk ? 'ok' : '' ?>">
                <i class="fas <?= $allOk ? 'fa-check-circle' : 'fa-clock' ?>"></i>
                <div><b>Direktur Utama</b>
                    <small><?= !$total ? 'Data belum diisi' : ($allOk ? 'Seluruh data disetujui' : "{$okN} dari {$total} disetujui") ?></small></div>
            </div>
        </div>
    </header>

    <!-- Toolbar -->
    <section class="sl-bar">
        <div class="sl-bar-l">
            <?php if ($canEdit && !$nullRow): ?>
                <button type="button" class="pu-btn pu-btn-primary" id="slAdd"><i class="fas fa-plus"></i> Tambah data</button>
                <button type="button" class="pu-btn pu-btn-light sl-act" data-url="<?= site_url($route . '/sync') ?>" <?= $prevCount ? '' : 'disabled' ?>
                    data-confirm="Salin <?= $prevCount ?> baris data <?= $prevLabel ?> ke periode ini?"
                    title="<?= $prevCount ? "Salin {$prevCount} baris dari {$prevLabel}" : "Tidak ada data baru di {$prevLabel}" ?>">
                    <i class="fas fa-history"></i> Salin <?= $prevLabel ?><?= $prevCount ? " ({$prevCount})" : '' ?></button>
                <?php if ($cfg['import']): ?>
                    <button type="button" class="pu-btn pu-btn-light" id="slImp"><i class="fas fa-file-excel"></i> Impor Excel</button>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <div class="sl-bar-r">
            <?php if ($cfg['txt'] && $rows): ?>
                <a class="pu-btn pu-btn-light" href="<?= site_url($route . '/' . $cfg['txt']) ?>"><i class="fas fa-file-alt"></i> Export TXT</a>
            <?php endif; ?>
            <?php if ($canEdit && !$rows && !empty($cfg['nodata'])): ?>
                <button type="button" class="pu-btn pu-btn-ghost sl-act" data-url="<?= site_url($route . '/nodata') ?>" data-act="<?= $nullRow ? 'cancel' : 'set' ?>"
                    data-confirm="<?= $nullRow ? 'Batalkan status &quot;Tidak ada data&quot;?' : 'Tandai periode ini &quot;Tidak ada data&quot;?' ?>">
                    <i class="fas <?= $nullRow ? 'fa-undo' : 'fa-ban' ?>"></i> <?= $nullRow ? 'Batalkan "Tidak ada data"' : 'Tidak ada data' ?></button>
            <?php endif; ?>
        </div>
    </section>

    <?php if (!$canEdit): ?>
        <div class="pu-note"><i class="fas fa-eye"></i> Mode baca — hanya Pengelola (PE), Auditor, atau Admin yang dapat mengubah data.</div>
    <?php endif; ?>

    <!-- Persetujuan seluruh data -->
    <?php if ($canApprove && $total && $rowApproval): ?>
        <section class="sl-approve <?= $allOk ? 'ok' : '' ?>">
            <div><i class="fas fa-user-shield"></i> <b>Persetujuan Direktur Utama</b>
                <small><?= $allOk ? 'Seluruh data telah disetujui.' : ($total - $okN) . ' data menunggu persetujuan.' ?></small></div>
            <div class="sl-approve-btn">
                <?php if (!$allOk): ?>
                    <button type="button" class="pu-btn pu-btn-primary sl-act" data-url="<?= site_url($route . '/approve') ?>" data-act="approve"
                        data-confirm="Setujui seluruh data (<?= $total - $okN ?> baris)?"><i class="fas fa-check-double"></i> Setujui semua</button>
                <?php endif; ?>
                <?php if ($okN): ?>
                    <button type="button" class="pu-btn pu-btn-danger sl-act" data-url="<?= site_url($route . '/approve') ?>" data-act="cancel"
                        data-confirm="Batalkan seluruh persetujuan?"><i class="fas fa-ban"></i> Batalkan semua</button>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Daftar -->
    <?php if ($nullRow): ?>
        <div class="pu-empty sl-null"><i class="fas fa-ban"></i><b>Periode ini ditandai "Tidak ada data"</b>
            <small><?= (int) $nullRow['accdir2'] === 1 ? 'Disetujui Direktur Utama ' . $fmtDt($nullRow['accdir2_at']) : 'Menunggu persetujuan Direktur Utama' ?></small></div>
    <?php elseif (!$rows): ?>
        <div class="pu-empty"><i class="far fa-folder-open"></i><b>Belum ada data</b>
            <small><?= $canEdit ? 'Klik "Tambah data", salin dari periode sebelumnya, atau impor dari Excel.' : 'Data belum diisi.' ?></small></div>
    <?php elseif ($card): ?>
        <!-- Kartu orang (gaya halaman Transparansi) -->
        <div class="sl-count"><b><?= count($rows) ?></b> data</div>
        <section class="pu-list">
            <?php foreach ($rows as $ci => $r): $ok = (int) $r['accdir2'] === 1;
                $payload = ['id' => (int) $r['id']];
                foreach ($F as $k => $f) {
                    $payload[$k] = (string) ($r[$k] ?? '');
                }
                // Judul kartu: isi field 'title', atau 'heading' bernomor (mis. "Pengawasan #1") bila tidak ada field judul
                $name = !empty($card['title']) ? trim((string) ($r[$card['title']] ?? '')) : ($card['heading'] ?? 'Data') . ' #' . ($ci + 1);
                $initials = strtoupper(implode('', array_map(static fn($w) => mb_substr($w, 0, 1), array_slice(preg_split('/\s+/', $name ?: '?'), 0, 2))));
                $tagV = !empty($card['tag']) ? ($r[$card['tag']] ?? '') : null;
                ?>
                <article class="pu-item sl-card <?= $ok ? 'ok' : '' ?>" data-row="<?= esc(json_encode($payload), 'attr') ?>">
                    <div class="pu-item-top">
                        <div class="pu-avatar" aria-hidden="true"><?= !empty($card['icon']) ? '<i class="fas ' . esc($card['icon'], 'attr') . '"></i>' : esc($initials) ?></div>
                        <div class="sl-card-main">
                            <div class="pu-item-name"><?= esc($name ?: '—') ?></div>
                            <?php if ($tagV !== null): ?>
                                <span class="pu-tag <?= in_array((string) $tagV, $card['utama'] ?? [], true) ? 'utama' : '' ?>"><?= esc($optLabel($card['tag'], $tagV) ?: $F[$card['tag']][0] . ' belum diisi') ?></span>
                            <?php endif; ?>
                            <?php foreach ($card['sub'] ?? [] as $k): $v = (string) ($r[$k] ?? ''); ?>
                                <div class="pu-item-nik"><?= esc($F[$k]['short'] ?? $F[$k][0]) ?>: <?= $F[$k]['type'] === 'date' ? $fmtDate($v) : esc($v !== '' ? $optLabel($k, $v) . ($F[$k]['unit'] ?? '') : '—') ?></div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (!$ok && $okN): ?><span class="sl-state" title="Data diubah setelah disetujui">Menunggu</span><?php endif; ?>
                    </div>
                    <?php if (!empty($card['chips'])): ?>
                        <div class="pu-chips">
                            <?php foreach ($card['chips'] as $k): $v = (string) ($r[$k] ?? '');
                                if (!empty($F[$k]['show_if']) && !in_array((string) ($r[$F[$k]['show_if'][0]] ?? ''), array_map('strval', $F[$k]['show_if'][1]), true)) {
                                    continue; // tidak berlaku untuk baris ini
                                } ?>
                                <span class="pu-chipmini <?= $v === '' ? 'muted' : '' ?>"><b><?= esc($F[$k]['short'] ?? $F[$k][0]) ?></b>
                                    <?= $v === '' ? '—' : esc($optLabel($k, $v) . ($F[$k]['unit'] ?? '')) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php foreach ($card['body'] ?? [] as $k): $v = trim((string) ($r[$k] ?? '')); ?>
                        <?php if (count($card['body']) > 1): ?><div class="pu-item-label"><?= esc($F[$k]['short'] ?? $F[$k][0]) ?></div><?php endif; ?>
                        <p class="pu-item-text"><?= $v !== '' ? esc($v) : '<em>—</em>' ?></p>
                    <?php endforeach; ?>
                    <?php if ($canEdit || ($canApprove && $rowApproval)): ?>
                        <div class="pu-item-actions">
                            <?php if ($canEdit): ?>
                                <button type="button" class="pu-btn pu-btn-light pu-btn-icon sl-edit"><i class="fas fa-pen"></i> Ubah</button>
                                <button type="button" class="pu-btn pu-btn-danger pu-btn-icon sl-act" data-url="<?= site_url($route . '/delete') ?>" data-id="<?= (int) $r['id'] ?>"
                                    data-confirm="Hapus data <?= esc($name, 'attr') ?>?"><i class="fas fa-trash"></i> Hapus</button>
                            <?php endif; ?>
                            <?php if ($canApprove && $rowApproval): ?>
                                <button type="button" class="pu-btn <?= $ok ? 'pu-btn-ghost' : 'pu-btn-primary' ?> pu-btn-icon sl-act" data-url="<?= site_url($route . '/approve') ?>"
                                    data-id="<?= (int) $r['id'] ?>" data-act="<?= $ok ? 'cancel' : 'approve' ?>"><i class="fas <?= $ok ? 'fa-undo' : 'fa-check' ?>"></i> <?= $ok ? 'Batal' : 'Setujui' ?></button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </section>
    <?php else: ?>
        <section class="sl-list">
            <?php foreach ($rows as $i => $r): $ok = (int) $r['accdir2'] === 1;
                $payload = ['id' => (int) $r['id']];
                foreach ($F as $k => $f) {
                    $payload[$k] = (string) ($r[$k] ?? '');
                } ?>
                <article class="sl-item <?= $ok ? 'ok' : '' ?>" data-row="<?= esc(json_encode($payload), 'attr') ?>">
                    <div class="sl-item-top">
                        <span class="sl-no"><?= $i + 1 ?></span>
                        <div class="sl-chips">
                            <?php foreach ($short as $k): $f = $F[$k]; $v = $r[$k] ?? ''; ?>
                                <span class="sl-chip"><small><?= esc($f['short'] ?? $f[0]) ?></small>
                                    <?= $f['type'] === 'date' ? $fmtDate($v) : esc($f['type'] === 'select' ? ($f['options'][$v] ?? ($v ?: '—')) : ($v ?: '—')) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <span class="sl-state"><i class="fas <?= $ok ? 'fa-check-circle' : 'fa-clock' ?>"></i> <?= $ok ? 'Disetujui' : 'Menunggu' ?></span>
                    </div>
                    <?php foreach ($long as $k): $v = trim((string) ($r[$k] ?? '')); ?>
                        <div class="sl-text">
                            <?php if (count($long) > 1): ?><small><?= esc($F[$k][0]) ?></small><?php endif; ?>
                            <p class="<?= mb_strlen($v) > 320 || substr_count($v, "\n") > 3 ? 'clamp' : '' ?>"><?= $v !== '' ? esc($v) : '<em>—</em>' ?></p>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($canEdit || $canApprove): ?>
                        <div class="sl-item-act">
                            <?php if ($canEdit): ?>
                                <button type="button" class="pu-btn pu-btn-light sl-edit"><i class="fas fa-pen"></i> Ubah</button>
                                <button type="button" class="pu-btn pu-btn-ghost sl-act" data-url="<?= site_url($route . '/delete') ?>" data-id="<?= (int) $r['id'] ?>"
                                    data-confirm="Hapus data nomor <?= $i + 1 ?>?"><i class="fas fa-trash"></i> Hapus</button>
                            <?php endif; ?>
                            <?php if ($canApprove): ?>
                                <button type="button" class="pu-btn <?= $ok ? 'pu-btn-ghost' : 'pu-btn-primary' ?> sl-act" data-url="<?= site_url($route . '/approve') ?>"
                                    data-id="<?= (int) $r['id'] ?>" data-act="<?= $ok ? 'cancel' : 'approve' ?>">
                                    <i class="fas <?= $ok ? 'fa-undo' : 'fa-check' ?>"></i> <?= $ok ? 'Batalkan persetujuan' : 'Setujui' ?></button>
                            <?php endif; ?>
                            <?php if ($ok && ($d = $fmtDt($r['accdir2_at']))): ?><small class="sl-when">Disetujui <?= $d ?></small><?php endif; ?>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <?php if (!$rowApproval && $total && ($canApprove || $okN)): ?>
        <!-- Persetujuan seluruh data (satu kali untuk semua baris) -->
        <section class="pu-approve">
            <div class="pu-approve-card <?= $allOk ? 'ok' : '' ?>">
                <div class="pu-approve-who"><i class="fas fa-user-shield"></i> Direktur Utama</div>
                <?php if ($canApprove && !$allOk): ?>
                    <button type="button" class="pu-btn pu-btn-primary sl-act" data-url="<?= site_url($route . '/approve') ?>" data-act="approve"
                        data-confirm="Setujui seluruh <?= $total ?> data <?= esc($judul, 'attr') ?>?"><i class="fas fa-check-double"></i> Setujui semua</button>
                    <small><?= $okN ? ($total - $okN) . ' data diubah/ditambah setelah disetujui' : $total . ' data menunggu persetujuan' ?></small>
                <?php elseif ($canApprove): ?>
                    <button type="button" class="pu-btn pu-btn-danger sl-act" data-url="<?= site_url($route . '/approve') ?>" data-act="cancel"
                        data-confirm="Batalkan persetujuan seluruh data?"><i class="fas fa-ban"></i> Batalkan</button>
                    <small>Seluruh data disetujui</small>
                <?php else: ?>
                    <small><?= $allOk ? 'Seluruh data disetujui' : "{$okN} dari {$total} data disetujui" ?></small>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($cfg['nav']): ?>
        <nav class="pu-nav" aria-label="Bagian laporan">
            <?php foreach ($cfg['nav'] as $c => $n): ?>
                <a href="<?= base_url($c) ?>" class="tpr-nav <?= $c === $cfg['page'] ? 'active' : '' ?> <?= $c === $cfg['back'][0] ? 'all' : '' ?>"
                    <?= $c === $cfg['page'] ? 'aria-current="page"' : '' ?>><?= esc($n) ?></a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>
</div>

<input type="hidden" id="puCsrf" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">

<?php if ($canEdit): ?>
    <!-- Dialog tambah / ubah -->
    <dialog class="pu-dialog" id="slDlg" aria-labelledby="slDlgTitle">
        <form id="slForm" method="dialog" novalidate>
            <div class="pu-dialog-head"><b id="slDlgTitle">Tambah data</b><button type="button" class="pu-dialog-x" data-close aria-label="Tutup">&times;</button></div>
            <div class="pu-dialog-body row">
                <input type="hidden" name="id" value="">
                <?php foreach ($F as $k => $f): ?>
                    <div class="<?= $f['col'] ?? 'col-12' ?> pu-field" data-f="<?= $k ?>"
                        <?= !empty($f['show_if']) ? 'data-showif="' . esc($f['show_if'][0], 'attr') . '" data-showvals="' . esc(implode(',', $f['show_if'][1]), 'attr') . '"' : '' ?>>
                        <label for="sl_<?= $k ?>"><?= esc($f[0]) ?><?= ($f['required'] ?? true) ? ' <span class="text-danger">*</span>' : '' ?></label>
                        <?php if ($f['type'] === 'textarea'): ?>
                            <textarea id="sl_<?= $k ?>" name="<?= $k ?>" class="pu-input pu-area" rows="<?= $f['rows'] ?? 5 ?>" maxlength="<?= $f['max'] ?>" data-max="<?= $f['max'] ?>"></textarea>
                        <?php elseif ($f['type'] === 'select'): ?>
                            <select id="sl_<?= $k ?>" name="<?= $k ?>" class="pu-input"><option value="">— Pilih —</option>
                                <?php foreach ($f['options'] as $v => $t): ?><option value="<?= esc($v) ?>"><?= esc($t) ?></option><?php endforeach; ?></select>
                        <?php else: ?>
                            <input id="sl_<?= $k ?>" name="<?= $k ?>" class="pu-input" type="<?= $f['type'] === 'date' ? 'date' : ($f['type'] === 'number' ? 'number' : 'text') ?>"
                                <?= $f['type'] === 'number' ? 'inputmode="numeric" step="1"' . (isset($f['min']) ? ' min="' . $f['min'] . '"' : '') . (isset($f['maxv']) ? ' max="' . $f['maxv'] . '"' : '') : '' ?>
                                <?= !empty($f['max']) ? 'maxlength="' . $f['max'] . '" data-max="' . $f['max'] . '"' : '' ?>>
                        <?php endif; ?>
                        <div class="pu-foot"><span class="pu-err" data-msg="<?= $k ?>"></span><?php if (!empty($f['max'])): ?><span class="pu-count" data-cnt="<?= $k ?>"></span><?php endif; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="pu-dialog-foot">
                <button type="button" class="pu-btn pu-btn-light" data-close>Batal</button>
                <button type="submit" class="pu-btn pu-btn-primary" id="slSave"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </form>
    </dialog>

    <?php if ($cfg['import']): ?>
        <!-- Dialog impor Excel -->
        <dialog class="pu-dialog sl-imp" id="slImpDlg" aria-labelledby="slImpTitle">
            <div class="pu-dialog-head"><b id="slImpTitle"><i class="fas fa-file-excel"></i> Impor dari Excel</b><button type="button" class="pu-dialog-x" data-close aria-label="Tutup">&times;</button></div>
            <div class="pu-dialog-body">
                <p class="sl-imp-help">Kolom: <?= implode(' · ', array_map(static fn($c, $k) => chr(65 + $c) . ' = ' . esc($F[$k][0]), array_keys($cfg['import_cols']), $cfg['import_cols'])) ?>.
                    Baris pertama adalah judul kolom. Maks. <?= (int) ($cfg['import_max'] ?? 200) ?> baris, 3 MB.
                    <a href="<?= base_url('assets/template/' . rawurlencode($cfg['import'])) ?>"><i class="fas fa-download"></i> Unduh template</a></p>
                <input type="file" id="slImpFile" class="pu-input" accept=".xlsx,.xls">
                <div id="slImpRes" class="sl-imp-res" hidden></div>
            </div>
            <div class="pu-dialog-foot">
                <button type="button" class="pu-btn pu-btn-light" data-close>Batal</button>
                <button type="button" class="pu-btn pu-btn-primary" id="slImpGo" disabled><i class="fas fa-file-import"></i> Impor</button>
            </div>
        </dialog>
    <?php endif; ?>
<?php endif; ?>

<?= view('templates/komentar', ['komentarPage' => $cfg['page']]) ?>

<style>
    .sl-bar { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 8px; margin-bottom: 14px; }
    .sl-bar-l, .sl-bar-r { display: flex; flex-wrap: wrap; gap: 8px; }
    .sl-approve { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; padding: 12px 16px; margin-bottom: 14px;
        border: 1.5px solid #c7d2fe; background: #eef2ff; color: #312e81; border-radius: 14px; font-size: .86rem; }
    .sl-approve.ok { border-color: #6ee7b7; background: var(--okbg, #ecfdf5); color: #065f46; }
    .sl-approve small { display: block; font-size: .78rem; opacity: .9; }
    .sl-approve-btn { display: flex; flex-wrap: wrap; gap: 8px; }
    .sl-list { display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px; }
    .sl-item { background: #fff; border: 1.5px solid var(--bd, #e2e8f0); border-left: 5px solid #f59e0b; border-radius: 14px; padding: 14px 16px; }
    .sl-item.ok { border-left-color: #10b981; }
    .sl-item-top { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 10px; margin-bottom: 10px; }
    .sl-no { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 9px; background: #eef2ff; color: var(--c2, #1f2aa8); font-weight: 800; font-size: .85rem; }
    .sl-chips { display: flex; flex-wrap: wrap; gap: 6px; flex: 1; }
    .sl-chip { display: inline-flex; align-items: baseline; gap: 6px; padding: 4px 10px; border-radius: 999px; background: #f1f5f9; font-size: .8rem; font-weight: 700; color: #334155; font-variant-numeric: tabular-nums; }
    .sl-chip small { font-size: .68rem; font-weight: 600; color: var(--mut, #64748b); }
    .sl-state { font-size: .72rem; font-weight: 700; padding: 3px 10px; border-radius: 999px; background: #fef3c7; color: #92400e; }
    .sl-item.ok .sl-state { background: #d1fae5; color: #065f46; }
    .sl-text small { display: block; font-size: .7rem; font-weight: 700; color: var(--mut, #64748b); text-transform: uppercase; letter-spacing: .03em; }
    .sl-text p { margin: 0 0 8px; font-size: .88rem; color: #334155; white-space: pre-wrap; word-break: break-word; }
    .sl-text p.clamp { display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical; overflow: hidden; cursor: pointer; }
    .sl-text p.clamp::after { content: ''; }
    .sl-text p.open { display: block; }
    .sl-item-act { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; padding-top: 10px; border-top: 1px dashed var(--bd, #e2e8f0); }
    .sl-when { margin-left: auto; font-size: .72rem; color: var(--mut, #64748b); }
    .sl-null { border-color: #cbd5e1; }
    .pu-field[hidden] { display: none !important; }
    .sl-count { font-size: .82rem; color: var(--mut, #64748b); margin: 0 2px 8px; }
    .sl-card { border-top: 3px solid #e2e8f0; }
    .sl-card.ok { border-top-color: #10b981; }
    .sl-card-main { flex: 1; min-width: 0; }
    .sl-card .pu-item-top .sl-state { margin-left: auto; }
    .sl .pu-empty { background: #fff; border: 1.5px dashed var(--bd, #e2e8f0); border-radius: 14px; margin-bottom: 16px; }
    .sl .pu-empty b, .sl .pu-empty small { display: block; }
    .pu-dialog-body.row { margin: 0; }
    .sl-imp { max-width: 760px; }
    .sl-imp-help { font-size: .82rem; color: var(--mut, #64748b); }
    .sl-imp-res { margin-top: 12px; max-height: 45vh; overflow: auto; border: 1px solid var(--bd, #e2e8f0); border-radius: 10px; }
    .sl-imp-res table { width: 100%; font-size: .78rem; border-collapse: collapse; }
    .sl-imp-res th, .sl-imp-res td { padding: 6px 8px; border-bottom: 1px solid #f1f5f9; vertical-align: top; text-align: left; }
    .sl-imp-res th { position: sticky; top: 0; background: #f8fafc; }
    .sl-imp-res tr.bad td { background: #fef2f2; }
    .sl-imp-res .e { color: #b91c1c; font-size: .72rem; }
    .pu-nav .tpr-nav { width: auto; padding: 0 12px; }
</style>

<script>
    (function () {
        'use strict';
        if (!window.fetch) return;
        var csrf = document.getElementById('puCsrf'), busy = false;
        function toast(msg, err) {
            var d = document.createElement('div'); if (err) d.className = 'e'; d.textContent = msg;
            document.getElementById('pu-toast').appendChild(d); setTimeout(function () { d.remove(); }, 3800);
        }
        function post(url, fd, btn) {
            if (busy) return Promise.resolve(null); busy = true;
            var ori = btn ? btn.innerHTML : ''; if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses…'; }
            fd.set(csrf.name, csrf.value);
            return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json().catch(function () { return { ok: false, message: 'Respons server tidak valid (' + r.status + ').' }; }); })
                .then(function (j) {
                    if (j.csrf) { csrf.name = j.csrf.name; csrf.value = j.csrf.hash; }
                    if (j.message) toast(j.message, !j.ok);
                    if (j.ok && j.reload) setTimeout(function () { location.reload(); }, 600);
                    else if (btn) { btn.disabled = false; btn.innerHTML = ori; }
                    return j;
                })
                .catch(function () { toast('Koneksi bermasalah, coba lagi.', true); if (btn) { btn.disabled = false; btn.innerHTML = ori; } return null; })
                .then(function (j) { busy = false; return j; });
        }

        // Teks panjang: klik untuk buka/tutup
        document.querySelectorAll('.sl-text p.clamp').forEach(function (p) {
            p.title = 'Klik untuk menampilkan seluruh teks';
            p.addEventListener('click', function () { p.classList.toggle('open'); });
        });
        // Kartu: tombol "Selengkapnya" hanya untuk teks yang benar-benar terpotong
        document.querySelectorAll('.sl-card .pu-item-text').forEach(function (p) {
            if (p.scrollHeight <= p.clientHeight + 2) return;
            var b = document.createElement('button');
            b.type = 'button'; b.className = 'pu-more'; b.textContent = 'Selengkapnya';
            b.addEventListener('click', function () { b.textContent = p.classList.toggle('open') ? 'Ringkas' : 'Selengkapnya'; });
            p.after(b);
        });

        // Aksi sederhana (hapus, setujui, sinkron, tidak ada data)
        document.querySelectorAll('.sl-act').forEach(function (b) {
            b.addEventListener('click', function () {
                if (b.dataset.confirm && !confirm(b.dataset.confirm)) return;
                var fd = new FormData();
                if (b.dataset.id) fd.set('id', b.dataset.id);
                if (b.dataset.act) fd.set('act', b.dataset.act);
                post(b.dataset.url, fd, b);
            });
        });

        // Dialog tutup
        document.querySelectorAll('dialog [data-close]').forEach(function (x) { x.addEventListener('click', function () { x.closest('dialog').close(); }); });

        // ---- Tambah / ubah
        var dlg = document.getElementById('slDlg');
        if (dlg) {
            var form = document.getElementById('slForm'), saveBtn = document.getElementById('slSave');
            function count(el) {
                var c = form.querySelector('[data-cnt="' + el.name + '"]'); if (!c) return;
                var max = +el.dataset.max, len = el.value.length;
                c.textContent = len.toLocaleString('id-ID') + ' / ' + max.toLocaleString('id-ID');
                c.className = 'pu-count' + (len >= max ? ' max' : len >= max * 0.9 ? ' warn' : '');
            }
            function clearErr() { form.querySelectorAll('.pu-field').forEach(function (w) { w.classList.remove('err'); w.querySelector('[data-msg]').textContent = ''; }); }
            function open(data) {
                form.reset(); clearErr();
                form.elements.id.value = data ? data.id : '';
                if (data) Object.keys(data).forEach(function (k) { if (form.elements[k] && k !== 'id') form.elements[k].value = data[k]; });
                document.getElementById('slDlgTitle').textContent = data ? 'Ubah data' : 'Tambah data';
                form.querySelectorAll('[data-max]').forEach(count);
                showIf();
                dlg.showModal();
                var first = form.querySelector('.pu-input'); if (first) first.focus();
            }
            var add = document.getElementById('slAdd'); if (add) add.addEventListener('click', function () { open(null); });
            document.querySelectorAll('.sl-edit').forEach(function (b) {
                b.addEventListener('click', function () { open(JSON.parse(b.closest('[data-row]').dataset.row)); });
            });
            // Field bersyarat (show_if): tampil hanya bila field acuan bernilai tertentu
            function showIf() {
                form.querySelectorAll('[data-showif]').forEach(function (w) {
                    var src = form.elements[w.dataset.showif], on = src && w.dataset.showvals.split(',').indexOf(src.value) >= 0;
                    w.hidden = !on;
                });
            }
            form.addEventListener('change', showIf);
            form.addEventListener('input', function (e) {
                if (e.target.dataset.max) count(e.target);
                var w = e.target.closest('.pu-field'); if (w) { w.classList.remove('err'); w.querySelector('[data-msg]').textContent = ''; }
            });
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                post('<?= site_url($route . '/save') ?>', new FormData(form), saveBtn).then(function (j) {
                    if (!j) return;
                    Object.keys(j.errors || {}).forEach(function (k) {
                        var w = form.querySelector('[data-f="' + k + '"]'); if (!w) return;
                        w.classList.add('err'); w.querySelector('[data-msg]').textContent = j.errors[k];
                    });
                    if (j.ok && j.reload) dlg.close();
                });
            });
        }

        // ---- Impor Excel
        var imp = document.getElementById('slImpDlg');
        if (imp) {
            var file = document.getElementById('slImpFile'), res = document.getElementById('slImpRes'), go = document.getElementById('slImpGo'), valid = [];
            var COLS = <?= json_encode(array_map(static fn($k) => [$k, $F[$k][0], $F[$k]['type']], $cfg['import_cols'])) ?>;
            function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
            document.getElementById('slImp').addEventListener('click', function () { file.value = ''; res.hidden = true; go.disabled = true; valid = []; imp.showModal(); });
            file.addEventListener('change', function () {
                if (!file.files[0]) return;
                var fd = new FormData(); fd.set('file', file.files[0]);
                res.hidden = false; res.innerHTML = '<p class="p-3 mb-0"><i class="fas fa-spinner fa-spin"></i> Membaca file…</p>'; go.disabled = true;
                post('<?= site_url($route . '/importPreview') ?>', fd).then(function (j) {
                    if (!j || !j.ok) { res.innerHTML = '<p class="p-3 mb-0 text-danger">' + esc(j ? j.message : 'Gagal membaca file.') + '</p>'; return; }
                    valid = j.rows.filter(function (r) { return !r.errors.length; }).map(function (r) { return r.data; });
                    var h = '<table><thead><tr><th>Baris</th>' + COLS.map(function (c) { return '<th>' + esc(c[1]) + '</th>'; }).join('') + '<th></th></tr></thead><tbody>';
                    j.rows.forEach(function (r) {
                        h += '<tr class="' + (r.errors.length ? 'bad' : '') + '"><td>' + r.line + '</td>' + COLS.map(function (c) {
                            var v = r.data[c[0]] || ''; if (c[2] === 'date' && v) v = v.split('-').reverse().join('/');
                            return '<td>' + esc(v.length > 140 ? v.slice(0, 140) + '…' : v) + '</td>';
                        }).join('') + '<td>' + (r.errors.length ? '<span class="e">' + r.errors.map(esc).join('<br>') + '</span>' : '<i class="fas fa-check text-success"></i>') + '</td></tr>';
                    });
                    res.innerHTML = h + '</tbody></table>';
                    go.disabled = !valid.length;
                    go.innerHTML = '<i class="fas fa-file-import"></i> Impor ' + valid.length + ' baris';
                });
            });
            go.addEventListener('click', function () {
                if (!valid.length) return;
                var fd = new FormData(); fd.set('rows', JSON.stringify(valid));
                post('<?= site_url($route . '/importConfirm') ?>', fd, go).then(function (j) { if (j && j.ok) imp.close(); });
            });
        }
    })();
</script>
