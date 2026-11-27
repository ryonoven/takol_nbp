<?php
/**
 * Halaman faktor Self Assessment Tata Kelola (App\Libraries\GcgFaktorController).
 * Variabel: $judul, $cfg, $bprData, $periodeLabel, $prevLabel, $params, $rows, $avg, $penjelasan, $st, $can
 */
use App\Libraries\GcgFaktorController as G;

$route = $cfg['route'];
$sum = $rows[$cfg['summary']] ?? [];
$namabpr = $bprData['namabpr'] ?? '';
$nv = static function ($r) { $v = (int) ($r['nilai'] ?? 0); return $v >= 1 && $v <= 5 ? $v : 0; };
$fmt = static function ($v) { $t = $v ? strtotime((string) $v) : false; return $t && $t > 0 ? date('d/m/Y H:i', $t) : ''; };
$groups = [];
foreach ($params as $id => $p) {
    $groups[$p['sph']][$id] = $p;
}
$nav = ['faktor' => 1, 'faktor2' => 2, 'faktor3' => 3, 'faktor4' => 4, 'faktor5' => 5, 'faktor6' => 6, 'faktor7' => 7, 'faktor8' => 8, 'faktor9' => 9, 'faktor10' => 10, 'faktor11' => 11, 'faktor12' => 12];
$locked = $st['komut'] || $st['dirut'];
$fmtAt = static fn($at): string => $at && ($t = strtotime((string) $at)) > 0 ? ' · ' . date('d/m/Y H:i', $t) : '';
// [jenis, label, disetujui?, waktu, oleh (id), nama, boleh aksi?]
$approvals = [
    ['komut', 'Komisaris Utama', $st['komut'], $st['komut_at'], $st['komut_by'], $st['komut_name'], $can['komut']],
    ['dirut', 'Direktur Utama', $st['dirut'], $st['dirut_at'], $st['dirut_by'], $st['dirut_name'], $can['dirut']],
];
$jsParams = [];
foreach ($params as $id => $p) {
    $r = $rows[$id] ?? [];
    $jsParams[$id] = ['n' => $p['number'], 't' => $p['sub_category'], 'v' => $nv($r), 'k' => (string) ($r['keterangan'] ?? '')];
}
?>
<link rel="stylesheet" href="<?= base_url('asset/css/transparansi.css') ?>?v=<?= @filemtime(FCPATH . 'asset/css/transparansi.css') ?>">
<div id="pu-toast" class="pu-toast" aria-live="polite"></div>

<div class="container-fluid pu gf">
    <a href="<?= base_url('ShowFaktor') ?>" class="pu-back"><i class="fas fa-arrow-left"></i> Ringkasan Self Assessment</a>

    <nav class="gf-nav" aria-label="Faktor">
        <?php foreach ($nav as $u => $n): ?>
            <a href="<?= base_url($u) ?>" class="<?= $n === $cfg['no'] ? 'on' : '' ?>" <?= $n === $cfg['no'] ? 'aria-current="page"' : '' ?>><?= $n ?></a>
        <?php endforeach; ?>
    </nav>

    <header class="pu-head">
        <div class="pu-head-main">
            <span class="pu-kicker">Self Assessment Tata Kelola · Faktor <?= $cfg['no'] ?></span>
            <h1 class="pu-title"><?= esc($cfg['title']) ?></h1>
            <div class="pu-meta">
                <span class="pu-bpr"><?= esc($namabpr ?: 'Nama BPR belum diisi') ?></span>
                <span class="pu-year"><i class="far fa-calendar"></i> <?= $periodeLabel ?></span>
            </div>
        </div>
        <div class="pu-status">
            <div class="pu-chip gf-chip-score r<?= $avg ?>">
                <b class="gf-chip-n"><?= $avg ?: '–' ?></b>
                <div><b>Nilai Faktor</b><small><?= $avg ? esc(G::SKALA[$avg]) : ($st['na'] ? 'Tidak diterapkan' : 'Belum dinilai') ?></small></div>
            </div>
            <?php foreach ($approvals as [$type, $label, $ok, $at, , $nama]): ?>
                <div class="pu-chip <?= $ok ? 'ok' : '' ?>">
                    <i class="fas <?= $ok ? 'fa-check-circle' : 'fa-clock' ?>"></i>
                    <div>
                        <b><?= $label ?></b>
                        <small><?= $ok ? 'Disetujui' . ($nama ? ' oleh ' . esc($nama) : '') . $fmtAt($at) : ($st['complete'] ? 'Menunggu persetujuan' : 'Penilaian belum lengkap') ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </header>

    <?php if ($can['edit'] && $locked): ?>
        <div class="pu-note warn"><i class="fas fa-info-circle"></i> Faktor ini sudah disetujui. Mengubah nilai atau kesimpulan akan membatalkan persetujuan Komisaris Utama & Direktur Utama.</div>
    <?php endif; ?>

    <!-- Parameter -->
    <section class="pu-card">
        <div class="pu-card-head">
            <h2><i class="fas fa-list-ol"></i> Penilaian Parameter
                <span class="gf-prog <?= $st['complete'] ? 'ok' : '' ?>"><?= $st['na'] ? 'Tidak diterapkan' : $st['rated'] . '/' . $st['total'] . ' dinilai · kesimpulan ' . ($st['kesimpulan'] ? 'lengkap' : 'belum lengkap') ?></span></h2>
            <div class="pu-card-tools">
                <span class="gf-legend"><i class="r1">1</i><i class="r2">2</i><i class="r3">3</i><i class="r4">4</i><i class="r5">5</i> Sangat baik → buruk</span>
                <?php if ($can['edit']): ?>
                    <button type="button" class="pu-btn pu-btn-ghost pu-btn-icon" id="gfSync"><i class="fas fa-sync-alt"></i> Salin <?= esc($prevLabel) ?></button>
                <?php endif; ?>
            </div>
        </div>
        <div class="pu-card-body">
            <?php if ($cfg['optional']): ?>
                <div class="pu-note mt-0"><i class="fas fa-info-circle"></i> Faktor ini <b>opsional</b>. Bila BPR tidak memiliki komite, biarkan seluruh parameter kosong — Komisaris Utama & Direktur Utama tetap dapat menyetujui dan faktor ini tidak dihitung dalam nilai komposit.
                    <?php if ($can['edit'] && $st['rated'] > 0): ?><button type="button" class="pu-btn pu-btn-light gf-na" id="gfNa"><i class="fas fa-eraser"></i> Tandai tidak diterapkan</button><?php endif; ?></div>
            <?php endif; ?>
            <?php $no = 0; foreach ($groups as $sph => $items): ?>
                <h3 class="gf-sph"><?= esc(G::SPH[$sph] ?? $sph) ?></h3>
                <ol class="gf-list">
                    <?php foreach ($items as $id => $p): $r = $rows[$id] ?? []; $v = $nv($r); ?>
                        <li class="gf-p <?= $v ? '' : 'todo' ?>" id="p<?= $id ?>">
                            <span class="gf-code"><?= esc($p['number']) ?></span>
                            <div class="gf-pb">
                                <p class="gf-pt"><?= esc($p['sub_category']) ?></p>
                                <?php if ($v): ?>
                                    <p class="gf-ket"><?= nl2br(esc((string) $r['keterangan'])) ?></p>
                                <?php else: ?>
                                    <p class="gf-ket muted">Belum dinilai.</p>
                                <?php endif; ?>
                                <?php if ($v): ?>
                                    <div class="gf-paraf">
                                        <?php foreach (['dekom2' => ['accdekom2', 'Dewan Komisaris'], 'dir2' => ['accdir2', 'Direksi']] as $kind => [$col, $lbl]):
                                            $on = (int) ($r[$col] ?? 0) === 1; ?>
                                            <?php if ($can[$kind]): ?>
                                                <button type="button" class="gf-chip <?= $on ? 'on' : '' ?>" data-paraf="<?= $kind ?>" data-f="<?= $id ?>" aria-pressed="<?= $on ? 'true' : 'false' ?>"><i class="fas <?= $on ? 'fa-check-circle' : 'fa-circle' ?>"></i> Paraf <?= $lbl ?></button>
                                            <?php elseif ($on): ?>
                                                <span class="gf-chip on"><i class="fas fa-check-circle"></i> Paraf <?= $lbl ?></span>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="gf-act">
                                <span class="gf-n r<?= $v ?>" title="<?= $v ? esc(G::SKALA[$v]) : 'Belum dinilai' ?>"><?= $v ?: '–' ?></span>
                                <?php if ($can['edit']): ?>
                                    <button type="button" class="pu-btn <?= $v ? 'pu-btn-light' : 'pu-btn-primary' ?>" data-nilai="<?= $id ?>"><i class="fas <?= $v ? 'fa-pen' : 'fa-plus' ?>"></i> <?= $v ? 'Ubah' : 'Nilai' ?></button>
                                <?php endif; ?>
                                <button type="button" class="pu-btn pu-btn-light gf-kom" data-kom="<?= $id ?>" aria-label="Komentar parameter <?= esc($p['number']) ?>"><i class="far fa-comment"></i><span class="gf-badge" data-unread="<?= $id ?>" hidden></span></button>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Kesimpulan -->
    <section class="pu-card">
        <div class="pu-card-head">
            <h2><i class="fas fa-clipboard-check"></i> Kesimpulan Faktor <?= $cfg['no'] ?></h2>
            <button type="button" class="pu-btn pu-btn-light gf-kom" data-kom="<?= $cfg['summary'] ?>" aria-label="Komentar kesimpulan"><i class="far fa-comment"></i><span class="gf-badge" data-unread="<?= $cfg['summary'] ?>" hidden></span></button>
        </div>
        <div class="pu-card-body">
            <?php if ($avg): ?>
                <details class="gf-pj">
                    <summary><span class="gf-n r<?= $avg ?>"><?= $avg ?></span> <b>Penjelasan nilai faktor (<?= esc(G::SKALA[$avg]) ?>)</b> <i class="fas fa-chevron-down"></i></summary>
                    <div><?= nl2br(esc($penjelasan)) ?></div>
                </details>
            <?php endif; ?>
            <form id="gfKes" novalidate>
                <div class="gf-kes">
                    <?php foreach (['pos' => ['Faktor Positif', 'fa-plus-circle'], 'neg' => ['Faktor Negatif', 'fa-minus-circle']] as $side => [$lbl, $ic]): ?>
                        <fieldset class="<?= $side ?>"><legend><i class="fas <?= $ic ?>"></i> <?= $lbl ?></legend>
                            <?php foreach (G::KESIMPULAN as $k => [$klbl, $ks]): if ($ks !== $side) { continue; } ?>
                                <div class="pu-field" data-f="<?= $k ?>">
                                    <label for="k_<?= $k ?>"><?= $klbl ?></label>
                                    <textarea id="k_<?= $k ?>" name="<?= $k ?>" class="pu-input" rows="3" maxlength="<?= $cfg['max_ket'] ?>" <?= $can['edit'] ? '' : 'disabled' ?>><?= esc((string) ($sum[$k] ?? '')) ?></textarea>
                                    <div class="pu-foot"><span class="pu-err" data-msg="<?= $k ?>"></span><span class="pu-count"><span data-c="<?= $k ?>">0</span>/<?= number_format($cfg['max_ket'], 0, ',', '.') ?></span></div>
                                </div>
                            <?php endforeach; ?>
                        </fieldset>
                    <?php endforeach; ?>
                </div>
            </form>
        </div>
        <?php if ($can['edit']): ?>
            <div class="pu-savebar">
                <span class="gf-dirty" id="gfDirty"><i class="fas fa-circle"></i> Belum ada perubahan</span>
                <button type="button" class="pu-btn pu-btn-primary" id="gfKesSave"><i class="fas fa-save"></i> Simpan kesimpulan</button>
            </div>
        <?php endif; ?>
    </section>

    <!-- Persetujuan (bagian bawah halaman) -->
    <?php if ($can['komut'] || $can['dirut']): ?>
        <section class="pu-approve" id="persetujuan">
            <?php foreach ($approvals as [$type, $label, $ok, , $by, , $allowed]): ?>
                <?php if (!$allowed) continue; ?>
                <?php
                $waitKomut = $type === 'dirut' && !$st['komut'] && !$ok;
                $mine = $can['admin'] || $by === $userId;
                ?>
                <div class="pu-approve-card <?= $ok ? 'ok' : '' ?>">
                    <div class="pu-approve-who"><i class="fas <?= $type === 'komut' ? 'fa-user-tie' : 'fa-user-shield' ?>"></i> <?= $label ?></div>
                    <?php if (!$ok && !$st['complete']): ?>
                        <button type="button" class="pu-btn pu-btn-primary" disabled><i class="fas fa-lock"></i> Setujui</button>
                        <small>Lengkapi penilaian (<?= $st['rated'] ?>/<?= $st['total'] ?>) & kesimpulan<?= $cfg['optional'] ? ', atau kosongkan seluruh parameter bila tidak diterapkan' : '' ?></small>
                    <?php elseif ($waitKomut): ?>
                        <button type="button" class="pu-btn pu-btn-primary" disabled><i class="fas fa-lock"></i> Setujui</button>
                        <small>Menunggu persetujuan Komisaris Utama</small>
                    <?php elseif (!$ok): ?>
                        <button type="button" class="pu-btn pu-btn-primary" data-appr="<?= $type ?>" data-op="approve"><i class="fas fa-check-double"></i> Setujui</button>
                    <?php elseif ($mine): ?>
                        <button type="button" class="pu-btn pu-btn-danger" data-appr="<?= $type ?>" data-op="cancel"><i class="fas fa-ban"></i> Batalkan</button>
                        <?php if ($type === 'komut' && $st['dirut']): ?><small>Membatalkan juga persetujuan Direktur Utama</small><?php endif; ?>
                    <?php else: ?>
                        <small><i class="fas fa-check"></i> Sudah disetujui</small>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <nav class="gf-pager">
        <?php $keys = array_keys($nav); $ix = $cfg['no'] - 1; ?>
        <?php if ($ix > 0): ?><a class="pu-btn pu-btn-light" href="<?= base_url($keys[$ix - 1]) ?>"><i class="fas fa-arrow-left"></i> Faktor <?= $ix ?></a><?php endif; ?>
        <a class="pu-btn pu-btn-light" href="<?= base_url('ShowFaktor') ?>"><i class="fas fa-th-large"></i> Ringkasan</a>
        <?php if ($ix < 11): ?><a class="pu-btn pu-btn-light" href="<?= base_url($keys[$ix + 1]) ?>">Faktor <?= $ix + 2 ?> <i class="fas fa-arrow-right"></i></a><?php endif; ?>
    </nav>
</div>

<!-- Dialog nilai -->
<?php if ($can['edit']): ?>
    <dialog class="pu-dialog gf-dlg" id="gfDlg" aria-labelledby="gfDlgT">
        <form id="gfForm" novalidate>
            <div class="pu-dialog-head"><h3 id="gfDlgT">Nilai parameter</h3><button type="button" class="pu-dialog-x" data-close aria-label="Tutup">&times;</button></div>
            <div class="pu-dialog-body">
                <p class="gf-dlg-p"><b id="gfDlgN"></b> <span id="gfDlgP"></span></p>
                <input type="hidden" name="faktor" id="gfFid">
                <div class="pu-field" data-f="nilai">
                    <label>Pilih Nilai <span class="text-danger">*</span></label>
                    <div class="gf-opts" role="radiogroup" aria-label="Pilih nilai">
                        <?php foreach (G::SKALA as $n => $lbl): ?>
                            <label class="gf-opt r<?= $n ?>"><input type="radio" name="nilai" value="<?= $n ?>"><b><?= $n ?></b>
                                <span><strong>Nilai <?= $n ?></strong><?= esc(G::SKALA_DESC) ?> <em><?= $lbl ?></em></span></label>
                        <?php endforeach; ?>
                    </div>
                    <div class="pu-foot"><span class="pu-err" data-msg="nilai"></span></div>
                </div>
                <div class="pu-field" data-f="keterangan">
                    <label for="gfKet">Keterangan <span class="text-danger">*</span></label>
                    <textarea id="gfKet" name="keterangan" class="pu-input" rows="6" maxlength="<?= $cfg['max_ket'] ?>" placeholder="Uraikan dasar penilaian…"></textarea>
                    <div class="pu-foot"><span class="pu-err" data-msg="keterangan"></span><span class="pu-count"><span id="gfKetC">0</span>/<?= number_format($cfg['max_ket'], 0, ',', '.') ?></span></div>
                </div>
            </div>
            <div class="pu-dialog-foot">
                <button type="button" class="pu-btn pu-btn-light" data-close>Batal</button>
                <button type="submit" class="pu-btn pu-btn-primary"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </form>
    </dialog>
<?php endif; ?>

<!-- Dialog komentar -->
<dialog class="pu-dialog gf-dlg" id="gfKomDlg" aria-labelledby="gfKomT">
    <div class="pu-dialog-head"><h3 id="gfKomT"><i class="far fa-comments"></i> Komentar</h3><button type="button" class="pu-dialog-x" data-close aria-label="Tutup">&times;</button></div>
    <div class="pu-dialog-body">
        <p class="gf-dlg-p" id="gfKomP"></p>
        <div class="gf-thread" id="gfThread" aria-live="polite"></div>
        <?php if ($can['comment']): ?>
            <form id="gfKomForm" class="gf-komf" novalidate>
                <textarea name="komentar" class="pu-input" rows="2" maxlength="<?= $cfg['max_kom'] ?>" placeholder="Tulis komentar…" required></textarea>
                <button type="submit" class="pu-btn pu-btn-primary" aria-label="Kirim"><i class="fas fa-paper-plane"></i></button>
            </form>
        <?php endif; ?>
    </div>
</dialog>

<style>
    .gf-nav { display: flex; flex-wrap: wrap; gap: 6px; margin: 12px 0 4px; }
    .gf-nav a { min-width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; border: 1.5px solid var(--bd); background: #fff; color: var(--mut); font-weight: 800; font-size: .82rem; text-decoration: none !important; }
    .gf-nav a:hover { border-color: #a5b4fc; color: var(--c2); }
    .gf-nav a.on { background: var(--c2); border-color: var(--c2); color: #fff; }
    .r1 { --rc: #059669; } .r2 { --rc: #0284c7; } .r3 { --rc: #d97706; } .r4 { --rc: #dc2626; } .r5 { --rc: #7f1d1d; } .r0 { --rc: #cbd5e1; }
    .gf-chip-score .gf-chip-n { flex: none; width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; background: var(--rc); color: #fff; font-size: 1.15rem; }
    .gf-chip-score.r0 .gf-chip-n { background: rgba(255,255,255,.2); }
    .gf-prog { display: inline-block; margin-left: 8px; padding: 2px 10px; border-radius: 999px; background: #fef3c7; color: #92400e; font-size: .72rem; font-weight: 700; vertical-align: middle; }
    .gf-prog.ok { background: #d1fae5; color: #065f46; }
    .gf-na { margin-top: 8px; display: inline-flex; }
    .gf-legend { display: inline-flex; align-items: center; gap: 3px; font-size: .7rem; color: var(--mut); }
    .gf-legend i { font-style: normal; display: inline-flex; width: 18px; height: 18px; align-items: center; justify-content: center; border-radius: 5px; color: #fff; font-weight: 800; font-size: .68rem; background: var(--rc); }
    .gf-sph { font-size: .8rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; color: var(--c2); margin: 6px 0 8px; }
    .gf-list { list-style: none; padding: 0; margin: 0 0 18px; display: flex; flex-direction: column; gap: 8px; }
    .gf-p { display: flex; gap: 12px; align-items: flex-start; padding: 12px 14px; border: 1.5px solid var(--bd); border-radius: 14px; background: #fff; }
    .gf-p.todo { border-style: dashed; background: #fcfcfd; }
    .gf-code { flex: none; font-size: .7rem; font-weight: 800; color: var(--c2); background: #eef2ff; border-radius: 8px; padding: 4px 7px; margin-top: 2px; }
    .gf-pb { flex: 1; min-width: 0; }
    .gf-pt { margin: 0 0 4px; font-size: .88rem; font-weight: 700; color: var(--txt); line-height: 1.4; }
    .gf-ket { margin: 0; font-size: .82rem; color: #475569; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
    .gf-ket.open { -webkit-line-clamp: unset; } .gf-ket.muted { color: #94a3b8; font-style: italic; }
    .gf-paraf { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
    .gf-chip { display: inline-flex; align-items: center; gap: 5px; border: 1.5px solid var(--bd); background: #fff; border-radius: 999px; padding: 3px 10px; font-size: .72rem; font-weight: 700; color: #64748b; }
    button.gf-chip { cursor: pointer; } button.gf-chip:hover { border-color: #a5b4fc; }
    .gf-chip i { color: #cbd5e1; } .gf-chip.on { border-color: #6ee7b7; background: #ecfdf5; color: #065f46; } .gf-chip.on i { color: #10b981; }
    .gf-act { flex: none; display: flex; align-items: center; gap: 6px; }
    .gf-n { flex: none; width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; border-radius: 11px; background: var(--rc); color: #fff; font-weight: 800; font-size: 1.1rem; }
    .gf-kom { position: relative; }
    .gf-badge { position: absolute; top: -6px; right: -6px; min-width: 18px; height: 18px; padding: 0 5px; border-radius: 999px; background: #ef4444; color: #fff; font-size: .66rem; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; }
    .gf-badge[hidden] { display: none; }
    .gf-pj { border: 1.5px solid var(--bd); border-radius: 14px; padding: 10px 14px; margin-bottom: 14px; background: #f8fafc; }
    .gf-pj summary { display: flex; align-items: center; gap: 10px; cursor: pointer; list-style: none; font-size: .86rem; } .gf-pj summary::-webkit-details-marker { display: none; }
    .gf-pj summary .gf-n { width: 30px; height: 30px; font-size: .95rem; } .gf-pj summary .fa-chevron-down { margin-left: auto; color: var(--mut); transition: transform .15s; }
    .gf-pj[open] summary .fa-chevron-down { transform: rotate(180deg); }
    .gf-pj > div { font-size: .82rem; color: #334155; line-height: 1.6; margin-top: 10px; text-align: justify; }
    .gf-kes { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .gf-kes fieldset { border: 1.5px solid var(--bd); border-radius: 14px; padding: 4px 14px 2px; min-width: 0; }
    .gf-kes legend { width: auto; font-size: .85rem; font-weight: 800; padding: 0 6px; margin: 0; }
    .gf-kes .pos legend { color: #047857; } .gf-kes .neg legend { color: #b91c1c; }
    .gf-dirty { margin-right: auto; font-size: .78rem; color: var(--mut); } .gf-dirty i { font-size: .5rem; vertical-align: middle; } .gf-dirty.on { color: #b45309; font-weight: 700; }
    .gf-pager { display: flex; justify-content: center; flex-wrap: wrap; gap: 8px; margin: 6px 0 24px; }
    .gf-dlg { width: 640px; }
    .gf-dlg-p { font-size: .84rem; color: #334155; background: #f8fafc; border-radius: 10px; padding: 10px 12px; margin-bottom: 12px; }
    .gf-opts { display: flex; flex-direction: column; border: 1.5px solid var(--bd); border-radius: 12px; overflow: hidden; }
    .gf-opt { margin: 0; cursor: pointer; display: flex; align-items: flex-start; gap: 12px; padding: 10px 14px; background: #fff; border-bottom: 1px solid var(--bd); transition: background .12s; }
    .gf-opt:last-child { border-bottom: none; }
    .gf-opt:hover { background: #f8fafc; }
    .gf-opt input { position: absolute; opacity: 0; }
    .gf-opt b { flex: none; width: 30px; height: 30px; margin-top: 2px; display: inline-flex; align-items: center; justify-content: center; border-radius: 9px; background: var(--rc); color: #fff; }
    .gf-opt span { font-size: .8rem; font-weight: 400; color: #64748b; line-height: 1.45; }
    .gf-opt span strong { display: block; font-size: .92rem; font-weight: 700; color: #475569; margin-bottom: 1px; }
    .gf-opt span em { font-style: normal; font-weight: 700; color: #334155; }
    .gf-opt:has(input:checked) { background: linear-gradient(90deg, #1f2aa8, #141863); }
    .gf-opt:has(input:checked) span, .gf-opt:has(input:checked) span strong, .gf-opt:has(input:checked) span em { color: #fff; }
    .gf-opt:has(input:checked) b { box-shadow: 0 0 0 2px #fff; }
    .gf-opt:has(input:focus-visible) { outline: 3px solid #a5b4fc; outline-offset: -3px; }
    .gf-thread { display: flex; flex-direction: column; gap: 8px; max-height: 46vh; overflow: auto; margin-bottom: 10px; }
    .gf-msg { max-width: 85%; padding: 8px 12px; border-radius: 12px; background: #f1f5f9; font-size: .84rem; }
    .gf-msg.me { align-self: flex-end; background: #eef2ff; }
    .gf-msg small { display: block; font-size: .7rem; color: var(--mut); margin-bottom: 2px; font-weight: 700; }
    .gf-msg p { margin: 0; white-space: pre-wrap; word-break: break-word; }
    .gf-empty { text-align: center; color: var(--mut); font-size: .84rem; padding: 16px 0; }
    .gf-komf { display: flex; gap: 8px; align-items: flex-end; } .gf-komf textarea { flex: 1; }
    @media (max-width: 768px) {
            .gf-p { flex-wrap: wrap; } .gf-act { width: 100%; justify-content: flex-end; }
            .gf-opt { padding: 9px 11px; gap: 10px; } .gf-opt span { font-size: .76rem; }
    }
</style>

<input type="hidden" id="puCsrf" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
<script>
    (function () {
        'use strict';
        var URL = '<?= site_url($route) ?>/', P = <?= json_encode($jsParams, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>, SUM = <?= (int) $cfg['summary'] ?>;
        var csrf = document.getElementById('puCsrf'), busy = false;
        var $ = function (id) { return document.getElementById(id); };
        function toast(m, e) { var d = document.createElement('div'); if (e) d.className = 'e'; d.textContent = m; $('pu-toast').appendChild(d); setTimeout(function () { d.remove(); }, 3800); }
        function done(j) { if (j.csrf) { csrf.name = j.csrf.name; csrf.value = j.csrf.hash; } if (j.message) toast(j.message, !j.ok); if (j.ok && j.reload) setTimeout(function () { location.reload(); }, 650); return j.ok; }
        function req(url, fd) {
            var o = { credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } };
            if (fd) { fd.set(csrf.name, csrf.value); o.method = 'POST'; o.body = fd; }
            return fetch(URL + url, o).then(function (r) { return r.json(); });
        }
        function fail() { toast('Koneksi bermasalah.', true); busy = false; }
        function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
        document.querySelectorAll('dialog [data-close]').forEach(function (x) { x.addEventListener('click', function () { x.closest('dialog').close(); }); });
        document.querySelectorAll('dialog').forEach(function (d) { d.addEventListener('click', function (e) { if (e.target === d) d.close(); }); });
        document.querySelectorAll('.gf-ket:not(.muted)').forEach(function (k) { k.addEventListener('click', function () { k.classList.toggle('open'); }); });
        function counter(ta, out) { var u = function () { out.textContent = ta.value.length.toLocaleString('id-ID'); }; ta.addEventListener('input', u); u(); }
        document.querySelectorAll('#gfKes textarea').forEach(function (ta) { counter(ta, document.querySelector('[data-c="' + ta.name + '"]')); });

        // Dialog nilai
        var dlg = $('gfDlg'), form = $('gfForm');
        if (dlg) {
            counter($('gfKet'), $('gfKetC'));
            document.querySelectorAll('[data-nilai]').forEach(function (b) {
                b.addEventListener('click', function () {
                    var id = b.dataset.nilai, p = P[id];
                    form.reset(); form.querySelectorAll('.pu-field').forEach(function (w) { w.classList.remove('err'); w.querySelector('[data-msg]').textContent = ''; });
                    $('gfFid').value = id; $('gfDlgN').textContent = p.n; $('gfDlgP').textContent = p.t; $('gfDlgT').textContent = p.v ? 'Ubah penilaian' : 'Nilai parameter';
                    if (p.v) form.querySelector('input[name="nilai"][value="' + p.v + '"]').checked = true;
                    $('gfKet').value = p.k; $('gfKet').dispatchEvent(new Event('input'));
                    dlg.showModal();
                });
            });
            form.addEventListener('submit', function (e) {
                e.preventDefault(); if (busy) return; busy = true;
                var btn = form.querySelector('[type=submit]'), ori = btn.innerHTML; btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan…';
                req('save', new FormData(form)).then(function (j) {
                    Object.keys(j.errors || {}).forEach(function (k) { var w = form.querySelector('[data-f="' + k + '"]'); if (w) { w.classList.add('err'); w.querySelector('[data-msg]').textContent = j.errors[k]; } });
                    if (done(j) && j.reload) return; if (j.ok) dlg.close();
                    busy = false; btn.disabled = false; btn.innerHTML = ori;
                }).catch(function () { fail(); btn.disabled = false; btn.innerHTML = ori; });
            });
        }

        // Kesimpulan
        var kf = $('gfKes'), kb = $('gfKesSave');
        if (kb) {
            var snap = new URLSearchParams(new FormData(kf)).toString(), dirty = $('gfDirty');
            var isDirty = function () { return new URLSearchParams(new FormData(kf)).toString() !== snap; };
            kf.addEventListener('input', function () { var d = isDirty(); dirty.classList.toggle('on', d); dirty.lastChild.textContent = d ? ' Ada perubahan belum disimpan' : ' Belum ada perubahan'; });
            window.addEventListener('beforeunload', function (e) { if (isDirty()) { e.preventDefault(); e.returnValue = ''; } });
            kb.addEventListener('click', function () {
                if (busy) return;
                <?php if ($locked): ?>if (!confirm('Faktor sudah disetujui. Menyimpan perubahan akan membatalkan persetujuan. Lanjutkan?')) return;<?php endif; ?>
                busy = true; var ori = kb.innerHTML; kb.disabled = true; kb.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan…';
                req('saveKesimpulan', new FormData(kf)).then(function (j) {
                    if (done(j)) { snap = new URLSearchParams(new FormData(kf)).toString(); if (j.reload) return; dirty.classList.remove('on'); dirty.lastChild.textContent = ' Tersimpan'; }
                    busy = false; kb.disabled = false; kb.innerHTML = ori;
                }).catch(function () { fail(); kb.disabled = false; kb.innerHTML = ori; });
            });
        }

        // Persetujuan
        document.querySelectorAll('[data-appr]').forEach(function (b) {
            b.addEventListener('click', function () {
                var who = b.dataset.appr === 'komut' ? 'Komisaris Utama' : 'Direktur Utama', ap = b.dataset.op === 'approve';
                if (busy || !confirm(ap ? 'Setujui seluruh penilaian Faktor <?= $cfg['no'] ?> sebagai ' + who + '?' : 'Batalkan persetujuan ' + who + (b.dataset.appr === 'komut' ? ' (persetujuan Direktur Utama ikut dibatalkan)' : '') + '?')) return;
                busy = true; b.disabled = true;
                var fd = new FormData(); fd.set('who', b.dataset.appr); fd.set('op', b.dataset.op);
                req('approve', fd).then(function (j) { if (!done(j)) { busy = false; b.disabled = false; } }).catch(function () { fail(); b.disabled = false; });
            });
        });

        // Paraf
        document.querySelectorAll('button[data-paraf]').forEach(function (b) {
            b.addEventListener('click', function () {
                if (b.disabled) return; b.disabled = true;
                var fd = new FormData(); fd.set('kind', b.dataset.paraf); fd.set('faktor', b.dataset.f);
                req('paraf', fd).then(function (j) {
                    if (done(j)) { b.classList.toggle('on', j.on); b.setAttribute('aria-pressed', j.on); b.querySelector('i').className = 'fas ' + (j.on ? 'fa-check-circle' : 'fa-circle'); }
                    b.disabled = false;
                }).catch(function () { toast('Koneksi bermasalah.', true); b.disabled = false; });
            });
        });

        // Faktor opsional: tandai tidak diterapkan
        var na = $('gfNa');
        if (na) na.addEventListener('click', function () {
            if (busy || !confirm('Tandai Faktor <?= $cfg['no'] ?> tidak diterapkan? Seluruh nilai parameter periode ini dihapus dan persetujuan direset.')) return;
            busy = true; na.disabled = true;
            req('notApplicable', new FormData()).then(function (j) { if (!done(j)) { busy = false; na.disabled = false; } }).catch(function () { fail(); na.disabled = false; });
        });

        // Sinkron periode sebelumnya
        var sync = $('gfSync');
        if (sync) sync.addEventListener('click', function () {
            if (busy || !confirm('Salin nilai, keterangan, dan kesimpulan dari <?= esc($prevLabel, 'js') ?>? Data periode ini akan ditimpa dan persetujuan direset.')) return;
            busy = true; sync.disabled = true;
            req('syncPrev', new FormData()).then(function (j) { if (!done(j)) { busy = false; sync.disabled = false; } }).catch(function () { fail(); sync.disabled = false; });
        });

        // Komentar
        var kd = $('gfKomDlg'), th = $('gfThread'), kfm = $('gfKomForm'), cur = 0;
        function loadKom() {
            th.innerHTML = '<div class="gf-empty"><i class="fas fa-spinner fa-spin"></i></div>';
            req('komentar?faktor=' + cur).then(function (j) {
                if (j.csrf) { csrf.name = j.csrf.name; csrf.value = j.csrf.hash; }
                var it = j.items || [];
                th.innerHTML = it.length ? it.map(function (c) { return '<div class="gf-msg' + (c.mine ? ' me' : '') + '"><small>' + esc(c.name) + ' · ' + esc(String(c.at).slice(0, 16)) + '</small><p>' + esc(c.text) + '</p></div>'; }).join('') : '<div class="gf-empty">Belum ada komentar.</div>';
                th.scrollTop = th.scrollHeight;
                var bd = document.querySelectorAll('[data-unread="' + cur + '"]'); bd.forEach(function (x) { x.hidden = true; });
            }).catch(function () { th.innerHTML = '<div class="gf-empty">Gagal memuat komentar.</div>'; });
        }
        document.querySelectorAll('[data-kom]').forEach(function (b) {
            b.addEventListener('click', function () {
                cur = +b.dataset.kom; var p = P[cur];
                $('gfKomP').textContent = p ? p.n + ' — ' + p.t : 'Kesimpulan Faktor <?= $cfg['no'] ?>';
                kd.showModal(); loadKom();
            });
        });
        if (kfm) kfm.addEventListener('submit', function (e) {
            e.preventDefault(); var ta = kfm.komentar; if (busy || ta.value.trim().length < 3) { toast('Komentar minimal 3 karakter.', true); return; }
            busy = true; var fd = new FormData(); fd.set('faktor', cur); fd.set('komentar', ta.value);
            req('addKomentar', fd).then(function (j) { if (done(j)) { ta.value = ''; loadKom(); } busy = false; }).catch(fail);
        });
        function unread() {
            if (document.hidden) return;
            req('unread').then(function (j) {
                if (j.csrf) { csrf.name = j.csrf.name; csrf.value = j.csrf.hash; }
                document.querySelectorAll('[data-unread]').forEach(function (x) { var n = (j.counts || {})[x.dataset.unread] || 0; x.hidden = !n; x.textContent = n > 9 ? '9+' : n; });
            }).catch(function () {});
        }
        unread(); setInterval(unread, 90000);
        document.addEventListener('visibilitychange', function () { if (!document.hidden) unread(); });
    })();
</script>
