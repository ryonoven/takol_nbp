<?php
/**
 * Halaman penilaian Profil Risiko (dipakai App\Libraries\ProfilRisikoController).
 * Variabel: $judul, $pr, $structure, $config, $rows, $unread, $missing, $required, $approved, $autoTotal, $prev,
 *           $bprData, $periodeDetail, $canEdit, $canApprove, $canAccRow, $canComment (+ data VIEW_EXTRA)
 */
$L = $pr['labels'];
$col = $pr['col'];
$colR = $pr['col_rasio'];
$route = $pr['route'];
$tid = $pr['total_id'];
$pid = $pr['prev_id'];
$nv = static function ($row) use ($col) {
    $v = (int) ($row[$col] ?? 0);
    return $v >= 1 && $v <= 5 ? $v : 0;
};
$fmtDt = static function ($v) {
    $t = $v ? strtotime((string) $v) : false;
    return ($t && $t > 0) ? date('d/m/Y H:i', $t) : '';
};
$pct = static fn($v) => number_format((float) $v, 2, ',', '.') . '%';
$pill = static function (int $v, string $empty = 'Belum dinilai') use ($L) {
    return $v ? '<span class="pr-pill pk-' . $v . '"><b>' . $v . '</b> ' . esc($L[$v]) . '</span>'
        : '<span class="pr-pill none">' . $empty . '</span>';
};
$total = $nv($rows[$tid] ?? null);
$prevV = $nv($rows[$pid] ?? null);
$manual = (int) ($rows[$tid]['is_manual'] ?? 0) === 1 && $total;
$sem = (int) ($periodeDetail['semester'] ?? 1);
$curLabel = 'Semester ' . $sem . ' Tahun ' . ($periodeDetail['tahun'] ?? '');
$nReq = count($required);
$nDone = $nReq - count($missing);
$accCount = 0;
foreach ($rows as $f => $r) {
    if ($f !== $tid && $f !== $pid && (string) ($r['accdir2'] ?? '') === '1' && $nv($r)) {
        $accCount++;
    }
}
$kesimpulan = trim((string) ($rows[$tid]['keterangan'] ?? ''));

// Data ringkas untuk JS (dialog penilaian)
$jsCfg = [];
foreach ($structure as $p) {
    foreach (array_merge([$p], $p['children'] ?? []) as $f) {
        $fid = (int) $f['id'];
        $c = $config[$fid] ?? [];
        $jsCfg[$fid] = ['t' => $f['title'], 'd' => $c['descriptions'] ?? [], 'th' => $c['threshold'] ?? '', 'cat' => ($c['catatan'] ?? '-') !== '-' ? $c['catatan'] : '',
            'v' => $nv($rows[$fid] ?? null), 'ket' => (string) ($rows[$fid]['keterangan'] ?? ''),
            'r' => $colR && isset($rows[$fid][$colR]) && $rows[$fid][$colR] !== null ? (float) $rows[$fid][$colR] : null];
    }
}

/** Tombol aksi satu faktor. */
$actions = static function (int $fid, string $kind, bool $hasRow) use ($rows, $nv, $unread, $canEdit, $canAccRow) {
    $v = $nv($rows[$fid] ?? null);
    $h = '';
    if ($canEdit && $kind === 'leaf') {
        $h .= '<button type="button" class="pu-btn pr-mini ' . ($v ? 'pu-btn-light' : 'pu-btn-primary') . '" data-nilai="' . $fid . '">'
            . '<i class="fas ' . ($v ? 'fa-pen' : 'fa-plus') . '"></i><span>' . ($v ? 'Ubah' : 'Nilai') . '</span></button>';
    } elseif ($canEdit && $kind === 'category' && $v) {
        $h .= '<button type="button" class="pu-btn pr-mini pu-btn-light" data-ket="' . $fid . '" title="Ubah keterangan pilar"><i class="fas fa-pen"></i><span>Keterangan</span></button>';
    }
    $n = (int) ($unread[$fid] ?? 0);
    $h .= '<button type="button" class="pu-btn pr-mini pu-btn-light pr-kom" data-kom="' . $fid . '" title="Komentar" aria-label="Komentar">'
        . '<i class="far fa-comment-dots"></i><span class="pr-badge" data-badge="' . $fid . '"' . ($n ? '' : ' hidden') . '>' . $n . '</span></button>';
    if ($kind === 'total') {
        // persetujuan per faktor tidak berlaku untuk baris total
    } elseif ($canAccRow && $v) {
        $on = (string) ($rows[$fid]['accdir2'] ?? '') === '1';
        $h .= '<button type="button" class="pu-btn pr-mini pr-acc ' . ($on ? 'on' : '') . '" data-acc="' . $fid . '" aria-pressed="' . ($on ? 'true' : 'false') . '"'
            . ' title="' . ($on ? 'Disetujui Direksi — klik untuk membatalkan' : 'Setujui faktor (Direksi)') . '"><i class="fas ' . ($on ? 'fa-check-circle' : 'fa-circle') . '"></i></button>';
    } elseif ($v && (string) ($rows[$fid]['accdir2'] ?? '') === '1') {
        $h .= '<span class="pr-accmark" title="Disetujui Direksi"><i class="fas fa-check-circle"></i></span>';
    }
    return '<div class="pr-act">' . $h . '</div>';
};
/** Keterangan (dipotong 3 baris). */
$ket = static function (?array $row, string $empty = '') {
    $t = trim((string) ($row['keterangan'] ?? ''));
    if ($t === '') {
        return $empty !== '' ? '<p class="pr-ket empty">' . $empty . '</p>' : '';
    }
    return '<p class="pr-ket" data-clamp>' . esc($t) . '</p>';
};
?>
<link rel="stylesheet" href="<?= base_url('asset/css/transparansi.css') ?>?v=<?= @filemtime(FCPATH . 'asset/css/transparansi.css') ?>">
<div id="pu-toast" class="pu-toast" aria-live="polite"></div>

<div class="container-fluid pu pr">
    <a href="<?= base_url('Showprofilresiko') ?>" class="pu-back"><i class="fas fa-arrow-left"></i> Laporan Profil Risiko</a>

    <?php foreach (['message' => 'success', 'error' => 'danger'] as $k => $cls): ?>
        <?php if ($msg = session()->getFlashdata($k)): ?>
            <div class="alert alert-<?= $cls ?> mt-3"><?= esc($msg) ?></div>
        <?php endif; ?>
    <?php endforeach; ?>

    <header class="pu-head">
        <div class="pu-head-main">
            <span class="pu-kicker">Laporan Profil Risiko · <?= esc($pr['risk']) ?></span>
            <h1 class="pu-title"><i class="fas <?= $pr['icon'] ?>"></i> <?= esc($pr['title']) ?></h1>
            <div class="pu-meta">
                <span class="pu-bpr"><?= esc($bprData['namabpr'] ?? 'Nama BPR') ?></span>
                <span class="pu-year"><i class="far fa-calendar"></i> <?= esc($curLabel) ?></span>
            </div>
        </div>
        <div class="pu-status">
            <div class="pu-chip <?= $approved ? 'ok' : '' ?>">
                <i class="fas <?= $approved ? 'fa-check-circle' : 'fa-clock' ?>"></i>
                <div>
                    <b>Direktur Utama</b>
                    <small><?= $approved ? 'Disetujui ' . $fmtDt($rows[$tid]['approved_at'] ?? '') : ($missing ? 'Penilaian belum lengkap' : 'Menunggu persetujuan') ?></small>
                </div>
            </div>
        </div>
    </header>

    <!-- Ringkasan -->
    <section class="pr-scores">
        <div class="pr-score main pk-<?= $total ?>">
            <span class="pr-lbl">Peringkat <?= esc($pr['title']) ?></span>
            <span class="pr-val"><?= $total ?: '—' ?> <small><?= $total ? esc($L[$total]) : 'Belum tersedia' ?></small></span>
            <span class="pr-sub">
                <?php if ($manual): ?>
                    <i class="fas fa-hand-paper"></i> Ditetapkan manual<?= $autoTotal ? ' · otomatis: ' . $autoTotal . ' (' . esc($L[$autoTotal]) . ')' : '' ?>
                <?php elseif ($total): ?>
                    <i class="fas fa-magic"></i> Otomatis · rata-rata <?= count($structure) ?> pilar
                <?php else: ?>
                    Dihitung otomatis setelah pilar dinilai
                <?php endif; ?>
            </span>
            <?php if ($canEdit): ?>
                <div class="pr-score-act">
                    <button type="button" class="pr-ghost" data-total="manual"><i class="fas fa-sliders-h"></i> Tetapkan manual</button>
                    <?php if ($manual): ?>
                        <button type="button" class="pr-ghost" data-total="auto"><i class="fas fa-undo"></i> Kembalikan otomatis</button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="pr-score pk-<?= $prevV ?>">
            <span class="pr-lbl">Peringkat periode sebelumnya</span>
            <span class="pr-val"><?= $prevV ?: '—' ?> <small><?= $prevV ? esc($L[$prevV]) : 'Belum diisi' ?></small></span>
            <span class="pr-sub">
                <?php if ($prevV && $total): ?>
                    <?= $total < $prevV ? '<i class="fas fa-arrow-down"></i> Membaik' : ($total > $prevV ? '<i class="fas fa-arrow-up"></i> Memburuk' : '<i class="fas fa-equals"></i> Tetap') ?> dibanding periode lalu
                <?php elseif (($prev['total'] ?? 0) && !$prevV): ?>
                    Tersedia dari <?= esc($prev['label']) ?>: <?= $prev['total'] ?> (<?= esc($L[$prev['total']]) ?>)
                <?php else: ?>
                    Posisi penilaian semester sebelumnya
                <?php endif; ?>
            </span>
            <?php if ($canEdit): ?>
                <div class="pr-score-act"><button type="button" class="pr-ghost" data-total="prev"><i class="fas fa-pen"></i> <?= $prevV ? 'Ubah' : 'Isi' ?></button></div>
            <?php endif; ?>
        </div>
        <div class="pr-score progress-card">
            <span class="pr-lbl">Kelengkapan penilaian</span>
            <span class="pr-val"><?= $nDone ?><small>/ <?= $nReq ?> parameter</small></span>
            <div class="pr-bar" role="progressbar" aria-valuemin="0" aria-valuemax="<?= $nReq ?>" aria-valuenow="<?= $nDone ?>"><i style="width:<?= $nReq ? round($nDone / $nReq * 100) : 0 ?>%"></i></div>
            <span class="pr-sub"><i class="fas fa-user-check"></i> <?= $accCount ?> faktor disetujui Direksi<?= $pr['optional'] ? ' · faktor lainnya opsional' : '' ?></span>
        </div>
    </section>

    <?php if ($canEdit && $prev['count'] && $missing): ?>
        <section class="pr-sync">
            <i class="fas fa-history"></i>
            <div><b>Data <?= esc($prev['label']) ?> tersedia</b>
                <small><?= $prev['count'] ?> faktor sudah dinilai pada periode sebelumnya. Salin sebagai titik awal, lalu sesuaikan.</small></div>
            <button type="button" class="pu-btn pu-btn-primary" data-sync><i class="fas fa-copy"></i> Salin data</button>
        </section>
    <?php endif; ?>

    <?php if (!empty($pr['extra'])): ?>
        <?= view($pr['extra'], get_defined_vars()) ?>
    <?php endif; ?>

    <!-- Kertas kerja -->
    <section class="pu-card">
        <div class="pu-card-head">
            <h2><i class="fas fa-clipboard-check"></i> Kertas Kerja <?= esc($pr['title']) ?></h2>
            <div class="pu-card-tools">
                <?php if ($canEdit && $prev['count'] && !$missing): ?>
                    <button type="button" class="pu-btn pu-btn-ghost pu-btn-icon" data-sync><i class="fas fa-copy"></i> Salin <?= esc($prev['label']) ?></button>
                <?php endif; ?>
                <span class="pr-legend">
                    <?php foreach ($L as $v => $t): ?><span class="pk-<?= $v ?>" title="<?= esc($t) ?>"><?= $v ?></span><?php endforeach; ?>
                    <small>Sangat rendah → sangat tinggi</small>
                </span>
            </div>
        </div>
        <div class="pu-card-body">
            <?php if (!$canEdit): ?>
                <div class="pu-note"><i class="fas fa-eye"></i> Mode baca — hanya Pengelola (PE) atau Admin yang dapat mengubah penilaian.</div>
            <?php elseif ($approved): ?>
                <div class="pu-note warn"><i class="fas fa-info-circle"></i> Penilaian sudah disetujui Direktur Utama. Perubahan apa pun akan membatalkan persetujuan.</div>
            <?php endif; ?>

            <?php foreach ($structure as $i => $p):
                $fid = (int) $p['id'];
                $row = $rows[$fid] ?? null;
                $v = $nv($row);
                $isCat = !empty($p['children']);
                $opt = in_array($fid, $pr['optional'], true);
                ?>
                <article class="pr-pilar <?= $v ? '' : 'todo' ?>" id="f<?= $fid ?>">
                    <header class="pr-pilar-head">
                        <span class="pr-no pk-<?= $v ?>"><?= $i + 1 ?></span>
                        <div class="pr-pilar-title">
                            <small><?= $isCat ? 'Pilar · rata-rata ' . count($p['children']) . ' parameter' : ($opt ? 'Pilar · opsional' : 'Pilar') ?></small>
                            <h3><?= esc($p['title']) ?></h3>
                        </div>
                        <div class="pr-pilar-side">
                            <?= $pill($v, $isCat ? 'Menunggu parameter' : 'Belum dinilai') ?>
                            <?= $actions($fid, $isCat ? 'category' : 'leaf', (bool) $row) ?>
                        </div>
                    </header>
                    <?= $ket($row) ?>

                    <?php if ($isCat): ?>
                        <ol class="pr-params">
                            <?php foreach ($p['children'] as $j => $c):
                                $cid = (int) $c['id'];
                                $cr = $rows[$cid] ?? null;
                                $cv = $nv($cr);
                                $th = $config[$cid]['threshold'] ?? '';
                                // Ambang "≥ x%" = makin tinggi makin baik; selain itu "≤ x%"
                                $thGe = str_starts_with($th, '≥');
                                $thV = (float) preg_replace('/[^\d.]/', '', str_replace(',', '.', $th));
                                $rv = $colR && isset($cr[$colR]) && $cr[$colR] !== null ? (float) $cr[$colR] : null;
                                ?>
                                <li class="pr-param <?= $cv ? '' : 'todo' ?>" id="f<?= $cid ?>">
                                    <div class="pr-param-main">
                                        <div class="pr-param-title"><span class="pr-sub-no"><?= ($i + 1) . '.' . ($j + 1) ?></span> <?= esc($c['title']) ?></div>
                                        <div class="pu-chips">
                                            <?php if ($rv !== null): ?>
                                                <span class="pu-chipmini <?= $th !== '' && ($thGe ? $rv < $thV : $rv > $thV) ? 'over' : '' ?>"><i class="fas fa-percentage"></i> Rasio <b><?= $pct($rv) ?></b></span>
                                            <?php elseif ($colR && $th !== ''): ?>
                                                <span class="pu-chipmini muted"><i class="fas fa-percentage"></i> Rasio belum dihitung</span>
                                            <?php endif; ?>
                                            <?php if ($th !== ''): ?><span class="pu-chipmini muted">Ambang peringkat 1: <?= $thGe ? '' : '≤ ' ?><?= esc($th) ?></span><?php endif; ?>
                                        </div>
                                        <?= $ket($cr) ?>
                                    </div>
                                    <div class="pr-pilar-side">
                                        <?= $pill($cv) ?>
                                        <?= $actions($cid, 'leaf', (bool) $cr) ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Kesimpulan -->
    <section class="pu-card" id="f<?= $tid ?>">
        <div class="pu-card-head">
            <h2><i class="fas fa-flag-checkered"></i> Kesimpulan <?= esc($pr['title']) ?></h2>
            <div class="pu-card-tools"><?= $actions($tid, 'total', isset($rows[$tid])) ?></div>
        </div>
        <div class="pu-card-body">
            <?php if ($canEdit): ?>
                <form id="prKes" class="pu-field" data-f="keterangan" novalidate>
                    <textarea name="keterangan" class="pu-input pu-area" rows="6" maxlength="<?= $pr['max'] ?>" data-max="<?= $pr['max'] ?>"
                        placeholder="Kesimpulan penilaian <?= esc(strtolower($pr['title'])) ?>…"><?= esc($kesimpulan) ?></textarea>
                    <div class="pu-foot"><span class="pu-err" data-msg="keterangan"></span><span class="pu-count" data-cnt></span></div>
                    <div class="pr-kes-bar">
                        <button type="submit" class="pu-btn pu-btn-primary"><i class="fas fa-save"></i> Simpan kesimpulan</button>
                        <span class="pu-dirty" id="prKesDirty"><i class="fas fa-circle"></i> <span>Belum ada perubahan</span></span>
                    </div>
                </form>
            <?php else: ?>
                <div class="pu-read <?= $kesimpulan === '' ? 'empty' : '' ?>"><?= $kesimpulan !== '' ? esc($kesimpulan) : 'Kesimpulan belum diisi.' ?></div>
                <div class="pb-3"></div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Persetujuan Direktur Utama -->
    <?php if ($canApprove): ?>
        <section class="pu-approve">
            <div class="pu-approve-card <?= $approved ? 'ok' : '' ?>">
                <div class="pu-approve-who"><i class="fas fa-user-shield"></i> Direktur Utama</div>
                <?php if ($approved): ?>
                    <button type="button" class="pu-btn pu-btn-danger" data-approve="cancel" data-confirm="Batalkan persetujuan <?= esc($pr['title']) ?>?"><i class="fas fa-ban"></i> Batalkan</button>
                    <small>Disetujui <?= $fmtDt($rows[$tid]['approved_at'] ?? '') ?></small>
                <?php elseif (!$missing && $total): ?>
                    <button type="button" class="pu-btn pu-btn-primary" data-approve="approve" data-confirm="Setujui seluruh penilaian <?= esc($pr['title']) ?>?"><i class="fas fa-check-double"></i> Setujui semua</button>
                    <small>Peringkat <?= $total ?> (<?= esc($L[$total]) ?>)</small>
                <?php else: ?>
                    <button type="button" class="pu-btn pu-btn-primary" disabled><i class="fas fa-lock"></i> Setujui semua</button>
                    <small><?= count($missing) ?> parameter wajib belum dinilai</small>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <nav class="pu-nav" aria-label="Navigasi <?= esc($pr['risk']) ?>">
        <?php foreach ($pr['nav'] as $u => $n): ?>
            <a href="<?= base_url($u) ?>" class="pr-nav <?= $u === $route ? 'active' : '' ?>" <?= $u === $route ? 'aria-current="page"' : '' ?>><?= esc($pr['risk'] . ' ' . $n) ?></a>
        <?php endforeach; ?>
        <a href="<?= base_url('Showprofilresiko') ?>" class="all">Ringkasan Profil Risiko</a>
    </nav>
</div>

<!-- Dialog: penilaian / peringkat -->
<dialog class="pu-dialog pr-dlg" id="prDlg" aria-labelledby="prDlgT">
    <form id="prForm" method="dialog" novalidate>
        <div class="pu-dialog-head"><h3 id="prDlgT">Penilaian</h3><button type="button" class="pu-dialog-x" data-close aria-label="Tutup">&times;</button></div>
        <div class="pu-dialog-body">
            <p class="pr-dlg-param" id="prDlgP"></p>
            <div class="pr-dlg-info" id="prDlgI" hidden></div>
            <div class="pu-field" data-f="nilai">
                <label>Tingkat risiko <span class="text-danger">*</span></label>
                <div class="pr-opts" id="prOpts" role="radiogroup"></div>
                <div class="pu-foot"><span class="pu-err" data-msg="nilai"></span></div>
            </div>
            <div class="pu-field" data-f="keterangan" id="prKetWrap">
                <label for="prKet">Keterangan <span class="text-danger">*</span></label>
                <textarea id="prKet" name="keterangan" class="pu-input pu-area" rows="5" maxlength="<?= $pr['max'] ?>" data-max="<?= $pr['max'] ?>"
                    placeholder="Dasar penilaian, data pendukung, kondisi BPR…"></textarea>
                <div class="pu-foot"><span class="pu-err" data-msg="keterangan"></span><span class="pu-count" data-cnt></span></div>
            </div>
        </div>
        <div class="pu-dialog-foot">
            <button type="button" class="pu-btn pu-btn-light" data-close>Batal</button>
            <button type="submit" class="pu-btn pu-btn-primary" id="prSave"><i class="fas fa-save"></i> Simpan</button>
        </div>
    </form>
</dialog>

<!-- Dialog: komentar -->
<dialog class="pu-dialog" id="prKomDlg" aria-labelledby="prKomT">
    <div class="pu-dialog-head"><h3 id="prKomT"><i class="far fa-comments"></i> Komentar</h3><button type="button" class="pu-dialog-x" data-close aria-label="Tutup">&times;</button></div>
    <div class="pu-dialog-body">
        <p class="pr-dlg-param" id="prKomP"></p>
        <ul class="pr-kom-list" id="prKomList" aria-live="polite"></ul>
        <?php if ($canComment): ?>
            <form id="prKomForm" class="pu-field" novalidate>
                <label for="prKomTxt">Tambah komentar</label>
                <textarea id="prKomTxt" name="komentar" class="pu-input" rows="3" maxlength="<?= $pr['max_komentar'] ?>" placeholder="Tulis komentar Direksi / Dewan Komisaris…"></textarea>
                <div class="pr-kom-send"><small>Ctrl+Enter untuk kirim</small><button type="submit" class="pu-btn pu-btn-primary pu-btn-icon"><i class="fas fa-paper-plane"></i> Kirim</button></div>
            </form>
        <?php endif; ?>
    </div>
</dialog>

<?php if ($canEdit && $prev['count']): ?>
    <!-- Dialog: salin periode sebelumnya -->
    <dialog class="pu-dialog" id="prSyncDlg" aria-labelledby="prSyncT" style="width:480px">
        <div class="pu-dialog-head"><h3 id="prSyncT"><i class="fas fa-copy"></i> Salin dari <?= esc($prev['label']) ?></h3><button type="button" class="pu-dialog-x" data-close aria-label="Tutup">&times;</button></div>
        <div class="pu-dialog-body">
            <p class="pr-dlg-param">Peringkat, penjelasan, keterangan<?= $colR ? ', rasio' : '' ?><?= !empty($pr['extra']) ? ' dan data kalkulator' : '' ?> dari
                <b><?= $prev['count'] ?> faktor</b> akan disalin<?= $prev['total'] ? ', serta peringkat periode sebelumnya = <b>' . $prev['total'] . ' (' . esc($L[$prev['total']]) . ')</b>' : '' ?>.
                Pilar & peringkat total dihitung ulang otomatis.</p>
            <label class="pr-check"><input type="checkbox" id="prSyncForce"> <span><b>Timpa</b> faktor yang sudah dinilai pada periode ini<?= $missing ? '' : ' (semua faktor sudah dinilai)' ?></span></label>
            <?php if ($approved): ?><div class="pu-note warn mt-2 mb-0">Persetujuan Direktur Utama akan dibatalkan.</div><?php endif; ?>
        </div>
        <div class="pu-dialog-foot">
            <button type="button" class="pu-btn pu-btn-light" data-close>Batal</button>
            <button type="button" class="pu-btn pu-btn-primary" id="prSyncGo"><i class="fas fa-copy"></i> Salin data</button>
        </div>
    </dialog>
<?php endif; ?>

<input type="hidden" id="puCsrf" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">

<style>
    .pr .pu-title i { opacity: .75; font-size: .85em; margin-right: 4px; }
    .pr-scores { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px; margin-bottom: 14px; }
    .pr-score { position: relative; display: flex; flex-direction: column; gap: 3px; padding: 16px 18px; border-radius: 16px; color: #fff; background: linear-gradient(135deg, #64748b, #94a3b8); box-shadow: 0 6px 18px rgba(15,23,42,.08); }
    .pk-1.pr-score { background: linear-gradient(135deg, #0369a1, #0ea5e9); } .pk-2.pr-score { background: linear-gradient(135deg, #047857, #10b981); }
    .pk-3.pr-score { background: linear-gradient(135deg, #b45309, #f59e0b); } .pk-4.pr-score { background: linear-gradient(135deg, #c2410c, #f97316); }
    .pk-5.pr-score { background: linear-gradient(135deg, #991b1b, #ef4444); }
    .pr-score.progress-card { background: #fff; color: var(--txt); border: 1px solid var(--bd); box-shadow: 0 2px 8px rgba(15,23,42,.05); }
    .pr-lbl { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; opacity: .9; }
    .progress-card .pr-lbl, .progress-card .pr-sub { color: var(--mut); opacity: 1; }
    .pr-val { font-size: 2rem; font-weight: 800; line-height: 1.15; font-variant-numeric: tabular-nums; }
    .pr-val small { font-size: .95rem; font-weight: 700; margin-left: 4px; }
    .pr-sub { font-size: .8rem; opacity: .95; }
    .pr-score-act { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
    .pr-ghost { border: 1px solid rgba(255,255,255,.45); background: rgba(255,255,255,.14); color: #fff; border-radius: 8px; padding: 4px 10px; font-size: .76rem; font-weight: 600; cursor: pointer; }
    .pr-ghost:hover { background: rgba(255,255,255,.26); }
    .pr-bar { height: 8px; border-radius: 99px; background: #e2e8f0; overflow: hidden; margin: 6px 0 4px; }
    .pr-bar i { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, var(--c2), #10b981); transition: width .4s; }

    .pr-sync { display: flex; flex-wrap: wrap; align-items: center; gap: 12px 14px; padding: 14px 16px; border-radius: 14px; border: 1.5px solid #c7d2fe; background: #eef2ff; color: #312e81; margin-bottom: 16px; font-size: .88rem; }
    .pr-sync > i { font-size: 1.3rem; } .pr-sync > div { flex: 1 1 260px; display: flex; flex-direction: column; } .pr-sync small { font-size: .8rem; }

    .pr-legend { display: inline-flex; align-items: center; gap: 3px; font-size: .72rem; color: var(--mut); }
    .pr-legend span { width: 20px; height: 20px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; color: #fff; font-weight: 800; }
    .pr-legend small { margin-left: 6px; }
    .pr-legend .pk-1, .pr-pill.pk-1, .pr-no.pk-1 { background: #0284c7; } .pr-legend .pk-2, .pr-pill.pk-2, .pr-no.pk-2 { background: #059669; }
    .pr-legend .pk-3, .pr-pill.pk-3, .pr-no.pk-3 { background: #d97706; } .pr-legend .pk-4, .pr-pill.pk-4, .pr-no.pk-4 { background: #ea580c; }
    .pr-legend .pk-5, .pr-pill.pk-5, .pr-no.pk-5 { background: #dc2626; }

    .pr-pilar { border: 1.5px solid var(--bd); border-radius: 14px; padding: 14px 16px; margin-bottom: 14px; background: #fff; }
    .pr-pilar.todo { border-style: dashed; }
    .pr-pilar-head { display: flex; flex-wrap: wrap; gap: 10px 12px; align-items: flex-start; }
    .pr-no { flex: 0 0 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 800; color: #fff; background: #cbd5e1; }
    .pr-pilar-title { flex: 1 1 240px; min-width: 0; }
    .pr-pilar-title small { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--mut); }
    .pr-pilar-title h3 { font-size: .98rem; font-weight: 700; color: var(--txt); margin: 1px 0 0; line-height: 1.35; }
    .pr-pilar-side { display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: 8px; margin-left: auto; }
    .pr-pill { display: inline-flex; align-items: center; gap: 5px; padding: 4px 11px; border-radius: 999px; color: #fff; font-size: .76rem; font-weight: 600; white-space: nowrap; }
    .pr-pill b { font-size: .9rem; }
    .pr-pill.none { background: #f1f5f9; color: #94a3b8; }
    .pr-act { display: flex; gap: 5px; }
    .pr-mini { padding: 5px 10px; font-size: .76rem; border-radius: 8px; position: relative; }
    .pr-kom { min-width: 34px; }
    .pr-badge { position: absolute; top: -7px; right: -7px; min-width: 18px; height: 18px; padding: 0 5px; border-radius: 9px; background: var(--err); color: #fff; font-size: .66rem; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; }
    .pr-badge[hidden] { display: none; }
    .pr-acc { background: #fff; border-color: var(--bd); color: #94a3b8; min-width: 34px; }
    .pr-acc.on { background: var(--okbg); border-color: #6ee7b7; color: var(--ok); }
    .pr-accmark { color: var(--ok); font-size: 1rem; padding: 0 4px; }
    .pr-ket { margin: 8px 0 0 46px; font-size: .85rem; color: #334155; white-space: pre-wrap; word-break: break-word; line-height: 1.55; }
    .pr-ket[data-clamp] { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
    .pr-ket.open { display: block; }
    .pr-ket.empty { color: #94a3b8; font-style: italic; }
    .pr-more { margin-left: 46px; }
    .pr-params { list-style: none; margin: 12px 0 0 17px; padding: 0 0 0 17px; border-left: 2px solid #e2e8f0; }
    .pr-param { display: flex; flex-wrap: wrap; gap: 8px 12px; padding: 10px 0; border-bottom: 1px dashed #e2e8f0; }
    .pr-param:last-child { border-bottom: none; padding-bottom: 2px; }
    .pr-param-main { flex: 1 1 280px; min-width: 0; display: flex; flex-direction: column; gap: 5px; }
    .pr-param-title { font-size: .9rem; font-weight: 600; color: var(--txt); line-height: 1.4; white-space: pre-line; }
    .pr-sub-no { color: var(--mut); font-weight: 700; font-variant-numeric: tabular-nums; margin-right: 2px; }
    .pr-param .pr-ket, .pr-param .pr-more { margin-left: 0; margin-top: 0; }
    .pu-chipmini.over { background: #fff7ed; color: #9a3412; }
    .pr-kes-bar { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; padding: 8px 0 12px; }

    .pr-dlg { width: 720px; }
    .pr-dlg-param { font-size: .9rem; font-weight: 600; color: var(--txt); margin-bottom: 10px; white-space: pre-line; }
    .pr-dlg-info { font-size: .8rem; background: #f8fafc; border: 1px solid var(--bd); border-radius: 10px; padding: 9px 12px; margin-bottom: 12px; color: #334155; }
    .pr-dlg-info .hint { display: block; margin-top: 4px; font-weight: 700; color: var(--c2); }
    .pr-opts { display: flex; flex-direction: column; gap: 6px; }
    .pr-opt { display: flex; gap: 10px; align-items: flex-start; border: 1.5px solid var(--bd); border-radius: 12px; padding: 9px 12px; cursor: pointer; transition: border-color .12s, background .12s; margin: 0; }
    .pr-opt:hover { border-color: #a5b4fc; }
    .pr-opt input { position: absolute; opacity: 0; pointer-events: none; }
    .pr-opt .pr-pill { flex: 0 0 132px; justify-content: center; margin-top: 1px; }
    .pr-opts.compact .pr-opt .pr-pill { flex-basis: auto; }
    .pr-opt-d { font-size: .82rem; color: #334155; line-height: 1.5; font-weight: 400; }
    .pr-opt:has(input:checked) { border-color: var(--c2); background: #eef2ff; box-shadow: 0 0 0 3px rgba(31,42,168,.1); }
    .pr-opt:has(input:focus-visible) { outline: 2px solid var(--c2); outline-offset: 2px; }
    .pr-opt.sel { border-color: var(--c2); background: #eef2ff; }
    .pr-opts.compact { flex-direction: row; flex-wrap: wrap; }
    .pr-opts.compact .pr-opt { flex: 1 1 120px; justify-content: center; }
    .pr-kom-list { list-style: none; padding: 0; margin: 0 0 12px; display: flex; flex-direction: column; gap: 8px; }
    .pr-kom-list li { background: #f8fafc; border: 1px solid var(--bd); border-radius: 12px; padding: 9px 12px; font-size: .86rem; white-space: pre-wrap; word-break: break-word; }
    .pr-kom-list li.mine { background: #eef2ff; border-color: #c7d2fe; }
    .pr-kom-list li small { display: block; margin-top: 4px; font-size: .72rem; color: var(--mut); white-space: normal; }
    .pr-kom-list li.empty { text-align: center; color: var(--mut); background: none; border-style: dashed; }
    .pr-kom-send { display: flex; justify-content: space-between; align-items: center; margin-top: 6px; } .pr-kom-send small { color: #94a3b8; font-size: .72rem; }
    .pr-check { display: flex; gap: 8px; align-items: flex-start; font-size: .86rem; cursor: pointer; margin: 4px 0 0; }
    .pr-check input { margin-top: 3px; }
    .pu-nav .pr-nav { padding: 0 14px; }

    @media (max-width: 576px) {
        .pr-pilar { padding: 12px; }
        .pr-pilar-side { width: 100%; justify-content: space-between; }
        .pr-ket, .pr-more { margin-left: 0; }
        .pr-params { margin-left: 4px; padding-left: 12px; }
        .pr-legend small { display: none; }
        .pr-mini span { display: none; }
        .pr-mini { padding: 6px 10px; }
    }
</style>

<script>
    (function () {
        'use strict';
        if (!window.fetch || !window.HTMLDialogElement) return;
        var BASE = <?= json_encode(rtrim(site_url($route), '/') . '/') ?>;
        var CFG = <?= json_encode($jsCfg, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
        var LBL = <?= json_encode($L) ?>;
        var TOTAL = { v: <?= $total ?>, prev: <?= $prevV ?>, title: <?= json_encode($pr['title']) ?> };
        var csrf = document.getElementById('puCsrf');
        var busy = false, leaving = false;
        var $ = function (id) { return document.getElementById(id); };

        function toast(msg, err) {
            var d = document.createElement('div'); if (err) d.className = 'e'; d.textContent = msg;
            $('pu-toast').appendChild(d); setTimeout(function () { d.remove(); }, 3800);
        }
        function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (m) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]; }); }
        /** Deskripsi config: escape lalu izinkan <br> saja. */
        function desc(s) { return esc(s).replace(/&lt;br\s*\/?&gt;/gi, '<br>'); }
        function setCsrf(j) { if (j && j.csrf) { csrf.name = j.csrf.name; csrf.value = j.csrf.hash; } }
        function post(act, fd, btn, form) {
            if (busy) return Promise.resolve(null); busy = true;
            var ori = btn ? btn.innerHTML : ''; if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses…'; }
            fd.set(csrf.name, csrf.value);
            return fetch(BASE + act, { method: 'POST', body: fd, credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json().catch(function () { return { ok: false, message: 'Respons server tidak valid (' + r.status + ').' }; }); })
                .then(function (j) {
                    setCsrf(j);
                    if (j.message) toast(j.message, !j.ok);
                    if (form) Object.keys(j.errors || {}).forEach(function (k) {
                        var w = form.querySelector('[data-f="' + k + '"]'); if (!w) return;
                        w.classList.add('err'); var m = w.querySelector('[data-msg]'); if (m) m.textContent = j.errors[k];
                    });
                    if (j.ok && j.reload) { leaving = true; setTimeout(function () { location.reload(); }, 650); }
                    return j;
                })
                .catch(function () { toast('Koneksi bermasalah, coba lagi.', true); return null; })
                .then(function (j) { busy = false; if (btn && !leaving) { btn.disabled = false; btn.innerHTML = ori; } return j; });
        }
        window.PR = { post: post, toast: toast, esc: esc };

        // Tutup dialog
        document.querySelectorAll('dialog [data-close]').forEach(function (x) { x.addEventListener('click', function () { x.closest('dialog').close(); }); });
        document.querySelectorAll('dialog').forEach(function (d) {
            d.addEventListener('click', function (e) { if (e.target === d) d.close(); });
        });

        // Counter textarea
        function count(el) {
            var c = el.parentNode.querySelector('[data-cnt]'); if (!c) return;
            var max = +el.dataset.max, len = el.value.length;
            c.textContent = len.toLocaleString('id-ID') + ' / ' + max.toLocaleString('id-ID');
            c.className = 'pu-count' + (len >= max ? ' max' : len >= max * 0.9 ? ' warn' : '');
        }
        function clearErr(e) { var w = e.target.closest('.pu-field'); if (w) { w.classList.remove('err'); var m = w.querySelector('[data-msg]'); if (m) m.textContent = ''; } }
        document.addEventListener('input', function (e) { if (e.target.dataset && e.target.dataset.max) count(e.target); clearErr(e); });

        // Keterangan panjang: tombol "Selengkapnya" hanya bila terpotong
        document.querySelectorAll('[data-clamp]').forEach(function (p) {
            if (p.scrollHeight - p.clientHeight < 4) return;
            var b = document.createElement('button'); b.type = 'button'; b.className = 'pu-more pr-more'; b.textContent = 'Selengkapnya';
            b.addEventListener('click', function () { var o = p.classList.toggle('open'); b.textContent = o ? 'Ringkas' : 'Selengkapnya'; });
            p.after(b);
        });

        // ── Dialog penilaian (faktor / keterangan pilar / peringkat total / periode sebelumnya) ──
        var dlg = $('prDlg'), form = $('prForm'), opts = $('prOpts'), ketTa = $('prKet'), mode = null, curFid = 0;
        function optsHtml(d, sel, compact) {
            var h = '';
            for (var i = 1; i <= 5; i++) {
                h += '<label class="pr-opt"><input type="radio" name="nilai" value="' + i + '"' + (sel === i ? ' checked' : '') + '>'
                    + '<span class="pr-pill pk-' + i + '"><b>' + i + '</b> ' + esc(LBL[i]) + '</span>'
                    + (compact ? '' : '<span class="pr-opt-d">' + desc((d && d[i]) || '—') + '</span>') + '</label>';
            }
            opts.className = 'pr-opts' + (compact ? ' compact' : ''); opts.innerHTML = h;
        }
        function openDlg(m, fid) {
            mode = m; curFid = fid || 0;
            form.querySelectorAll('.pu-field').forEach(function (w) { w.classList.remove('err'); var x = w.querySelector('[data-msg]'); if (x) x.textContent = ''; });
            var info = $('prDlgI'); info.hidden = true; info.innerHTML = '';
            var c = CFG[fid] || {};
            $('prKetWrap').hidden = !(m === 'nilai' || m === 'ket');
            form.querySelector('[data-f="nilai"]').hidden = m === 'ket';
            if (m === 'nilai') {
                $('prDlgT').textContent = c.v ? 'Ubah penilaian' : 'Nilai parameter';
                $('prDlgP').textContent = c.t;
                var parts = [];
                if (c.r !== null && c.r !== undefined) {
                    var ge = /^\s*≥/.test(c.th), thv = String(c.th).replace(/[^\d.,]/g, '').replace(',', '.');
                    var th = parseFloat(thv);
                    var rs = c.r.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '%';
                    var ok = ge ? c.r >= th : c.r <= th;
                    parts.push('<b>Rasio dari kalkulator: ' + rs + '</b>' + (isNaN(th) ? '' : '<span class="hint">' + (ok
                        ? (ge ? '≥' : '≤') + ' ambang ' + esc(thv) + '% → umumnya peringkat 1'
                        : (ge ? '<' : '>') + ' ambang ' + esc(thv) + '% → peringkat 2–5 sesuai kondisi') + '</span>'));
                } else if (c.th) parts.push('Ambang batas peringkat 1: ' + (/^\s*≥/.test(c.th) ? '' : '≤ ') + esc(c.th));
                if (c.cat) parts.push('<i class="fas fa-info-circle"></i> ' + esc(c.cat));
                if (parts.length) { info.innerHTML = parts.join('<br>'); info.hidden = false; }
                optsHtml(c.d, c.v, false);
                ketTa.value = c.ket || '';
            } else if (m === 'ket') {
                $('prDlgT').textContent = 'Keterangan pilar'; $('prDlgP').textContent = c.t;
                info.innerHTML = 'Peringkat pilar dihitung otomatis dari rata-rata parameter: <b>' + c.v + ' (' + esc(LBL[c.v] || '') + ')</b>.'; info.hidden = false;
                ketTa.value = c.ket || '';
            } else if (m === 'manual') {
                $('prDlgT').textContent = 'Tetapkan peringkat manual'; $('prDlgP').textContent = 'Peringkat ' + TOTAL.title;
                info.innerHTML = 'Peringkat manual tidak berubah saat faktor dinilai ulang, sampai dikembalikan ke perhitungan otomatis.'; info.hidden = false;
                optsHtml(null, TOTAL.v, true);
            } else {
                $('prDlgT').textContent = 'Peringkat periode sebelumnya'; $('prDlgP').textContent = 'Peringkat ' + TOTAL.title + ' posisi semester sebelumnya';
                optsHtml(null, TOTAL.prev, true);
            }
            count(ketTa);
            dlg.showModal();
            var first = form.querySelector('input[name="nilai"]:checked') || form.querySelector('input[name="nilai"]');
            if (m === 'ket') ketTa.focus(); else if (first) first.focus();
        }
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var fd = new FormData(), sel = form.querySelector('input[name="nilai"]:checked');
            if (mode !== 'ket') {
                if (!sel) { var w = form.querySelector('[data-f="nilai"]'); w.classList.add('err'); w.querySelector('[data-msg]').textContent = 'Pilih tingkat risiko.'; return; }
                fd.set('nilai', sel.value);
            }
            if (mode === 'nilai' || mode === 'ket') { fd.set('faktor', curFid); fd.set('keterangan', ketTa.value); }
            var act = { nilai: 'save', ket: 'saveKet', manual: 'setTotal', prev: 'savePrev' }[mode];
            if (mode === 'manual') fd.set('act', 'manual');
            post(act, fd, $('prSave'), form).then(function (j) { if (j && j.ok) leaving = true; });
        });
        document.addEventListener('keydown', function (e) {
            if (dlg.open && (e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) { e.preventDefault(); form.requestSubmit(); }
        });

        // ── Komentar ──
        var komDlg = $('prKomDlg'), komFid = 0;
        function loadKom() {
            var ul = $('prKomList'); ul.innerHTML = '<li class="empty"><i class="fas fa-spinner fa-spin"></i> Memuat…</li>';
            fetch(BASE + 'komentar?faktor=' + komFid, { credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (j) {
                    setCsrf(j);
                    if (!j.ok) { ul.innerHTML = '<li class="empty">' + esc(j.message || 'Gagal memuat.') + '</li>'; return; }
                    ul.innerHTML = j.list.length ? j.list.map(function (k) {
                        return '<li class="' + (k.mine ? 'mine' : '') + '">' + esc(k.text) + '<small><i class="far fa-user"></i> ' + esc(k.by) + ' · ' + esc(k.at) + '</small></li>';
                    }).join('') : '<li class="empty">Belum ada komentar.</li>';
                    ul.lastElementChild && ul.lastElementChild.scrollIntoView({ block: 'nearest' });
                    var b = document.querySelector('[data-badge="' + komFid + '"]'); if (b) { b.hidden = true; b.textContent = '0'; }
                })
                .catch(function () { ul.innerHTML = '<li class="empty">Koneksi bermasalah.</li>'; });
        }
        var komForm = $('prKomForm');
        if (komForm) {
            komForm.addEventListener('submit', function (e) {
                e.preventDefault();
                var ta = $('prKomTxt'); if (!ta.value.trim()) { ta.focus(); return; }
                var fd = new FormData(); fd.set('faktor', komFid); fd.set('komentar', ta.value);
                post('addKomentar', fd, komForm.querySelector('button')).then(function (j) { if (j && j.ok) { ta.value = ''; loadKom(); } });
            });
            $('prKomTxt').addEventListener('keydown', function (e) { if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') { e.preventDefault(); komForm.requestSubmit(); } });
        }

        // ── Klik tombol ──
        document.addEventListener('click', function (e) {
            var b = e.target.closest('[data-nilai],[data-ket],[data-kom],[data-acc],[data-total],[data-approve],[data-sync]');
            if (!b || b.disabled) return;
            if (b.dataset.nilai) return openDlg('nilai', +b.dataset.nilai);
            if (b.dataset.ket) return openDlg('ket', +b.dataset.ket);
            if (b.dataset.kom) {
                komFid = +b.dataset.kom; var c = CFG[komFid];
                $('prKomP').textContent = c ? c.t : 'Kesimpulan ' + TOTAL.title;
                komDlg.showModal(); loadKom(); return;
            }
            if (b.dataset.acc) {
                var fd = new FormData(); fd.set('faktor', b.dataset.acc);
                var ori = b.innerHTML;
                return post('accRow', fd, b).then(function (j) {
                    if (!j || !j.ok) return; b.innerHTML = '<i class="fas ' + (j.acc ? 'fa-check-circle' : 'fa-circle') + '"></i>';
                    b.classList.toggle('on', j.acc); b.setAttribute('aria-pressed', j.acc ? 'true' : 'false');
                    b.title = j.acc ? 'Disetujui Direksi — klik untuk membatalkan' : 'Setujui faktor (Direksi)';
                });
            }
            if (b.dataset.total) {
                if (b.dataset.total === 'auto') {
                    if (!confirm('Kembalikan peringkat ke perhitungan otomatis (rata-rata pilar)?')) return;
                    var f2 = new FormData(); f2.set('act', 'auto'); return post('setTotal', f2, b);
                }
                return openDlg(b.dataset.total === 'prev' ? 'prev' : 'manual');
            }
            if (b.dataset.approve) {
                if (!confirm(b.dataset.confirm)) return;
                var f3 = new FormData(); f3.set('act', b.dataset.approve); return post('approve', f3, b);
            }
            if (b.hasAttribute('data-sync')) { var sd = $('prSyncDlg'); if (sd) sd.showModal(); }
        });
        var syncGo = $('prSyncGo');
        if (syncGo) syncGo.addEventListener('click', function () {
            var fd = new FormData(); fd.set('force', $('prSyncForce').checked ? '1' : '0');
            post('syncPrev', fd, syncGo);
        });

        // ── Kesimpulan ──
        var kes = $('prKes');
        if (kes) {
            var kTa = kes.querySelector('textarea'), kSnap = kTa.value, kDirty = $('prKesDirty');
            count(kTa);
            var setD = function () { var d = kTa.value !== kSnap; kDirty.classList.toggle('on', d); kDirty.lastElementChild.textContent = d ? 'Ada perubahan belum disimpan' : 'Belum ada perubahan'; };
            kTa.addEventListener('input', setD);
            kes.addEventListener('submit', function (e) {
                e.preventDefault(); var fd = new FormData(); fd.set('keterangan', kTa.value);
                post('saveKesimpulan', fd, kes.querySelector('button[type="submit"]'), kes).then(function (j) { if (j && j.ok) { kSnap = kTa.value; setD(); } });
            });
            document.addEventListener('keydown', function (e) {
                if (!dlg.open && document.activeElement === kTa && (e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) { e.preventDefault(); kes.requestSubmit(); }
            });
            window.addEventListener('beforeunload', function (e) { if (!leaving && kTa.value !== kSnap) { e.preventDefault(); e.returnValue = ''; } });
        }

        // ── Polling komentar belum dibaca (ringan, hanya saat tab terlihat) ──
        var pollT = null;
        function poll() {
            if (document.hidden) return;
            fetch(BASE + 'unread', { credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (j) {
                    if (!j || !j.ok) return;
                    document.querySelectorAll('[data-badge]').forEach(function (b) {
                        var n = +(j.counts[b.dataset.badge] || 0);
                        if (komDlg.open && +b.dataset.badge === komFid) n = 0;
                        b.textContent = n; b.hidden = !n;
                    });
                }).catch(function () {});
        }
        function schedule() { clearInterval(pollT); pollT = setInterval(poll, 90000); }
        document.addEventListener('visibilitychange', function () { if (!document.hidden) { poll(); schedule(); } });
        schedule();
    })();
</script>
