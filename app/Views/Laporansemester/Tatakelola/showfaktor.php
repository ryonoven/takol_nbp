<?php
/**
 * Ringkasan Self Assessment Tata Kelola (App\Controllers\ShowFaktor).
 * Variabel: $judul, $bprData, $periodeLabel, $vals, $nilai, $peringkat, $row, $files, $canEdit
 */
use App\Controllers\ShowFaktor as SF;

$namabpr = $bprData['namabpr'] ?? '';
$rated = count(array_filter($vals, static fn($f) => $f['v'] !== null && $f['v'] !== 0));
$dirutN = count(array_filter($vals, static fn($f) => $f['dirut']));
$komutN = count(array_filter($vals, static fn($f) => $f['komut']));
$fmtSize = static fn(int $b) => $b >= 1048576 ? number_format($b / 1048576, 1, ',', '.') . ' MB' : number_format(max(1, $b / 1024), 0, ',', '.') . ' KB';
$v = static fn(string $k) => (string) ($row[$k] ?? '');
$tgl = $v('tanggal');
if (!preg_match('/^\d{4}-\d{2}-\d{2}/', $tgl) || str_starts_with($tgl, '0000')) {
    $tgl = '';
}

// Kelengkapan (syarat Generate Laporan)
$missing = [];
$notApproved = array_keys(array_filter($vals, static fn($f) => !$f['dirut']));
if ($notApproved) {
    $missing[] = 'Persetujuan Direktur Utama: Faktor ' . implode(', ', $notApproved);
}
$emptyTexts = array_filter(array_keys(SF::TEXTS), static fn($k) => trim($v($k)) === '');
if ($emptyTexts) {
    $missing[] = 'Kesimpulan & analisis belum lengkap (' . count($emptyTexts) . ' isian kosong)';
}
foreach (SF::TTD as $k => [$lbl]) {
    if (($k === 'tanggal' ? $tgl : trim($v($k))) === '') {
        $missing[] = 'Lembar persetujuan: ' . $lbl . ' belum diisi';
    }
}
if (!$coverOk) {
    $missing[] = 'Cover laporan belum dipilih';
}
foreach ([1, 2] as $s) {
    if (!$files[$s]) {
        $missing[] = 'Dokumen pendukung belum diunggah: ' . SF::PDFS[$s][2];
    }
}
$ready = !$missing;
$ttdDone = $tgl !== '' && trim($v('dirut')) !== '' && trim($v('komut')) !== '' && trim($v('lokasi')) !== '';
$pk = $nilai >= 1 && $nilai <= 5 ? $nilai : 0;
?>
<link rel="stylesheet" href="<?= base_url('asset/css/transparansi.css') ?>?v=<?= @filemtime(FCPATH . 'asset/css/transparansi.css') ?>">
<div id="pu-toast" class="pu-toast" aria-live="polite"></div>

<div class="container-fluid pu sf">
    <a href="<?= base_url('Showsemesteran') ?>" class="pu-back"><i class="fas fa-arrow-left"></i> Laporan Semesteran</a>

    <?php foreach (['message' => 'success', 'error' => 'danger', 'err' => 'danger'] as $k => $cls): ?>
        <?php if ($msg = session()->getFlashdata($k)): ?>
            <div class="alert alert-<?= $cls ?> mt-3"><?= esc($msg) ?></div>
        <?php endif; ?>
    <?php endforeach; ?>

    <header class="pu-head">
        <div class="pu-head-main">
            <span class="pu-kicker">Laporan Semesteran · Tata Kelola (GCG)</span>
            <h1 class="pu-title"><?= esc($judul) ?></h1>
            <div class="pu-meta">
                <span class="pu-bpr"><?= esc($namabpr ?: 'Nama BPR belum diisi') ?></span>
                <span class="pu-year"><i class="far fa-calendar"></i> <?= $periodeLabel ?></span>
            </div>
        </div>
        <div class="sf-komp r<?= $pk ?>">
            <span>Peringkat Komposit</span>
            <b><?= $pk ?: '–' ?></b>
            <em><?= esc($pk ? $peringkat : 'Belum dapat dihitung') ?></em>
        </div>
    </header>

    <div class="sf-stats">
        <div><span>Faktor dinilai</span><b><?= $rated ?><small>/12</small></b><i class="sf-bar"><i style="width:<?= round($rated / 12 * 100) ?>%"></i></i></div>
        <div><span>Disetujui Direktur Utama</span><b><?= $dirutN ?><small>/12</small></b><i class="sf-bar"><i style="width:<?= round($dirutN / 12 * 100) ?>%"></i></i></div>
        <div><span>Disetujui Komisaris Utama</span><b><?= $komutN ?><small>/12</small></b><i class="sf-bar"><i style="width:<?= round($komutN / 12 * 100) ?>%"></i></i></div>
    </div>

    <!-- 12 faktor -->
    <section class="pu-card">
        <div class="pu-card-head">
            <h2><i class="fas fa-th-large"></i> Ringkasan Penilaian 12 Faktor</h2>
            <span class="sf-legend"><i class="r1">1</i><i class="r2">2</i><i class="r3">3</i><i class="r4">4</i><i class="r5">5</i> Sangat baik → buruk</span>
        </div>
        <div class="pu-card-body">
            <div class="sf-grid">
                <?php foreach (SF::FACTORS as $no => [$name, $page, $fid]):
                    $f = $vals[$no];
                    $nv = $f['v'] !== null && $f['v'] >= 1 && $f['v'] <= 5 ? $f['v'] : 0;
                    $cls = $f['dirut'] && $f['komut'] ? 'ok' : ($nv ? 'wait' : 'empty'); ?>
                    <a class="sf-f <?= $cls ?>" href="<?= base_url($page) . '?modal_nilai=' . $fid ?>">
                        <span class="sf-no"><?= $no ?></span>
                        <span class="sf-fb">
                            <b><?= esc($name) ?></b>
                            <span class="sf-ap">
                                <i class="fas <?= $f['dirut'] ? 'fa-check-circle on' : 'fa-circle' ?>" title="Direktur Utama: <?= $f['dirut'] ? 'disetujui' : 'belum' ?>"></i> Dirut
                                <i class="fas <?= $f['komut'] ? 'fa-check-circle on' : 'fa-circle' ?>" title="Komisaris Utama: <?= $f['komut'] ? 'disetujui' : 'belum' ?>"></i> Komut
                            </span>
                        </span>
                        <span class="sf-n r<?= $nv ?>" title="<?= $nv ? esc(SF::PERINGKAT[$nv]) : 'Belum dinilai' ?>"><?= $nv ?: '–' ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
            <p class="sf-foot">Nilai komposit = rata-rata nilai faktor (faktor 4 dikecualikan bila tidak dinilai), dibulatkan. Klik faktor untuk membuka kertas kerjanya.</p>
        </div>
    </section>

    <!-- Kesimpulan -->
    <section class="pu-card">
        <div class="pu-card-head"><h2><i class="fas fa-clipboard-check"></i> Kesimpulan &amp; Analisis</h2></div>
        <form id="sfText" class="pu-card-body" novalidate>
            <?php $ta = static function (string $k, int $rows) use ($v, $canEdit) {
                [$lbl, $max] = SF::TEXTS[$k]; ?>
                <div class="pu-field" data-f="<?= $k ?>">
                    <label for="t_<?= $k ?>"><?= esc($lbl) ?></label>
                    <textarea id="t_<?= $k ?>" name="<?= $k ?>" class="pu-input" rows="<?= $rows ?>" maxlength="<?= $max ?>" <?= $canEdit ? '' : 'disabled' ?>><?= esc($v($k)) ?></textarea>
                    <div class="pu-foot"><span class="pu-err" data-msg="<?= $k ?>"></span><span class="pu-count"><span data-c="<?= $k ?>">0</span>/<?= number_format($max, 0, ',', '.') ?></span></div>
                </div>
            <?php }; ?>
            <?php $ta('kesimpulan', 5); ?>
            <div class="sf-pn">
                <fieldset class="pos"><legend><i class="fas fa-plus-circle"></i> Faktor Positif</legend>
                    <?php foreach (['positifstruktur', 'positifproses', 'positifhasil'] as $k) { $ta($k, 4); } ?>
                </fieldset>
                <fieldset class="neg"><legend><i class="fas fa-minus-circle"></i> Faktor Negatif</legend>
                    <?php foreach (['negatifstruktur', 'negatifproses', 'negatifhasil'] as $k) { $ta($k, 4); } ?>
                </fieldset>
            </div>
        </form>
        <?php if ($canEdit): ?>
            <div class="pu-savebar">
                <span class="sf-dirty" id="sfDirty"><i class="fas fa-circle"></i> Belum ada perubahan</span>
                <button type="button" class="pu-btn pu-btn-primary" id="sfTextSave"><i class="fas fa-save"></i> Simpan kesimpulan</button>
            </div>
        <?php endif; ?>
    </section>

    <!-- Lembar persetujuan -->
    <section class="pu-card">
        <div class="pu-card-head"><h2><i class="fas fa-file-signature"></i> Lembar Persetujuan</h2></div>
        <form id="sfTtd" class="pu-card-body row" autocomplete="off" novalidate>
            <?php foreach (SF::TTD as $k => [$lbl, $type, $max]): ?>
                <div class="col-md-6 pu-field" data-f="<?= $k ?>">
                    <label for="ttd_<?= $k ?>"><?= esc($lbl) ?> <span class="text-danger">*</span></label>
                    <input id="ttd_<?= $k ?>" name="<?= $k ?>" class="pu-input" type="<?= $type ?>" value="<?= esc($k === 'tanggal' ? substr($tgl, 0, 10) : $v($k)) ?>"
                        <?= $type === 'text' ? 'maxlength="' . $max . '"' : '' ?> <?= $canEdit ? '' : 'disabled' ?>>
                    <div class="pu-foot"><span class="pu-err" data-msg="<?= $k ?>"></span></div>
                </div>
            <?php endforeach; ?>
        </form>
        <div class="pu-savebar">
            <?php if ($canEdit): ?>
                <button type="button" class="pu-btn pu-btn-primary" id="sfTtdSave"><i class="fas fa-save"></i> Simpan</button>
            <?php endif; ?>
            <?php if ($ttdDone): ?>
                <a class="pu-btn pu-btn-light" href="<?= site_url('ShowFaktor/cetakPengesahan') ?>" target="_blank" rel="noopener"><i class="fas fa-print"></i> Cetak Lembar Pengesahan</a>
            <?php else: ?>
                <button type="button" class="pu-btn pu-btn-light" disabled title="Lengkapi lembar persetujuan terlebih dahulu"><i class="fas fa-lock"></i> Cetak Lembar Pengesahan</button>
            <?php endif; ?>
        </div>
    </section>

    <!-- Dokumen pendukung -->
    <section class="pu-card">
        <div class="pu-card-head"><h2><i class="fas fa-paperclip"></i> Dokumen Pendukung</h2></div>
        <div class="pu-card-body sf-docs">
            <p class="sf-foot mt-0">PDF, maksimal <?= SF::MAX_PDF_KB / 1024 ?> MB per dokumen. Dokumen 1 & 2 digabung ke laporan PDF; dokumen 3 masuk ke ZIP Laporan Semesteran.</p>
            <?php foreach (SF::PDFS as $slot => [$col, , $label]): $f = $files[$slot]; ?>
                <div class="sf-doc <?= $f ? 'has' : '' ?>" data-slot="<?= $slot ?>">
                    <i class="fas <?= $f ? 'fa-file-pdf' : 'fa-file-upload' ?> sf-doc-ic"></i>
                    <div class="sf-doc-b">
                        <b><?= $slot ?>. <?= esc($label) ?><?= $slot < 3 ? ' <span class="text-danger">*</span>' : '' ?></b>
                        <small><?= $f ? 'Terunggah · ' . $fmtSize($f['size']) . ' · ' . date('d/m/Y H:i', $f['time']) : 'Belum diunggah' ?></small>
                        <span class="sf-up" hidden><span class="sf-bar"><i></i></span><small>0%</small></span>
                    </div>
                    <div class="sf-doc-act">
                        <?php if ($f): ?>
                            <a class="pu-btn pu-btn-light" href="<?= site_url('ShowFaktor/downloadPdf/' . $slot) ?>" target="_blank" rel="noopener"><i class="fas fa-eye"></i> Lihat</a>
                        <?php endif; ?>
                        <?php if ($canEdit): ?>
                            <label class="pu-btn pu-btn-ghost mb-0"><i class="fas fa-upload"></i> <?= $f ? 'Ganti' : 'Unggah' ?>
                                <input type="file" accept="application/pdf,.pdf" hidden data-up="<?= $slot ?>"></label>
                            <?php if ($f): ?>
                                <button type="button" class="pu-btn pu-btn-danger" data-del="<?= $slot ?>" aria-label="Hapus dokumen <?= $slot ?>"><i class="fas fa-trash"></i></button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Cover -->
    <?php
    $curCover = $v('cover');
    $curCustom = null;
    foreach ($customCovers as $cc) {
        if ($cc['value'] === $curCover) {
            $curCustom = $cc;
        }
    }
    ?>
    <section class="pu-card" id="sfCoverCard">
        <div class="pu-card-head">
            <h2><i class="far fa-image"></i> Cover Laporan</h2>
            <a class="pu-btn pu-btn-light" id="cvPreview" href="<?= site_url('ShowFaktor/coverPreview') ?>" target="_blank" rel="noopener"><i class="fas fa-eye"></i> Pratinjau cover</a>
        </div>
        <div class="pu-card-body">
            <p class="sf-foot mt-0 mb-3">Logo, judul, periode, nama BPR, dan kontak otomatis ditempatkan sesuai desain. Pratinjau memakai data BPR & periode ini.</p>

            <h3 class="sf-h3">Template</h3>
            <div class="sf-covers" role="radiogroup" aria-label="Pilih template cover">
                <?php foreach ($covers as $val => $name): ?>
                    <label class="sf-cover">
                        <input type="radio" name="cover" value="<?= esc($val) ?>" <?= $curCover === $val ? 'checked' : '' ?> <?= $canEdit ? '' : 'disabled' ?>>
                        <img src="<?= \App\Libraries\CoverDesign::thumbUrl($val) ?>" alt="" loading="lazy" width="120" height="170">
                        <span><?= esc($name) ?><?= str_starts_with($val, 'tpl:') ? ' <em class="sf-new">baru</em>' : '' ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <h3 class="sf-h3">Desain Sendiri</h3>
            <div class="sf-covers">
                <?php foreach ($customCovers as $cc): ?>
                    <label class="sf-cover">
                        <input type="radio" name="cover" value="<?= esc($cc['value']) ?>" <?= $curCover === $cc['value'] ? 'checked' : '' ?> <?= $canEdit ? '' : 'disabled' ?>
                            data-custom='<?= esc(json_encode(array_intersect_key($cc, \App\Libraries\CoverDesign::CUSTOM_DEFAULT)), 'attr') ?>'>
                        <img src="<?= site_url('ShowFaktor/coverImage/' . $cc['file']) ?>" alt="" loading="lazy" width="120" height="170">
                        <span title="<?= esc($cc['name']) ?>"><?= esc($cc['name']) ?></span>
                    </label>
                <?php endforeach; ?>
                <?php if ($canEdit): ?>
                    <label class="sf-cover sf-cover-up" id="cvZone">
                        <input type="file" id="cvFile" accept="image/jpeg,image/png" hidden>
                        <span class="sf-cover-add"><i class="fas fa-cloud-upload-alt"></i><b>Unggah desain</b><small>JPG/PNG · A4 tegak · maks. <?= \App\Libraries\CoverDesign::MAX_UPLOAD_KB / 1024 ?> MB</small></span>
                        <span class="sf-up" hidden><span class="sf-bar"><i></i></span><small>0%</small></span>
                    </label>
                <?php elseif (!$customCovers): ?>
                    <p class="sf-foot mt-0">Belum ada desain sendiri.</p>
                <?php endif; ?>
            </div>

            <?php if ($canEdit): ?>
                <div class="sf-cset" id="cvSet" <?= $curCustom ? '' : 'hidden' ?>>
                    <div class="sf-cset-head"><b><i class="fas fa-sliders-h"></i> Pengaturan desain sendiri</b>
                        <small>Ukuran gambar otomatis disesuaikan ke A4. Warna teks “Otomatis” dipilih dari terang-gelapnya gambar di area teks.</small></div>
                    <div class="sf-cset-grid">
                        <?php foreach (\App\Libraries\CoverDesign::CUSTOM_OPTS as $k => [$lbl, $opts]): ?>
                            <div class="sf-seg" data-k="<?= $k ?>">
                                <span><?= esc($lbl) ?></span>
                                <div role="radiogroup" aria-label="<?= esc($lbl) ?>">
                                    <?php foreach ($opts as $ov => $ol): ?>
                                        <label><input type="radio" name="cs_<?= $k ?>" value="<?= esc($ov) ?>" <?= (string) ($curCustom[$k] ?? \App\Libraries\CoverDesign::CUSTOM_DEFAULT[$k]) === (string) $ov ? 'checked' : '' ?>><span><?= esc($ol) ?></span></label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="sf-cset-act">
                        <button type="button" class="pu-btn pu-btn-danger" id="cvDel"><i class="fas fa-trash"></i> Hapus desain</button>
                        <button type="button" class="pu-btn pu-btn-light" id="cvTry"><i class="fas fa-eye"></i> Pratinjau pengaturan</button>
                        <button type="button" class="pu-btn pu-btn-primary" id="cvSave"><i class="fas fa-save"></i> Simpan pengaturan</button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Kelengkapan & generate -->
    <section class="pu-card sf-check <?= $ready ? 'ok' : '' ?>">
        <div class="pu-card-body">
            <?php if ($ready): ?>
                <p class="mb-0"><i class="fas fa-check-circle"></i> Seluruh faktor disetujui dan data pendukung lengkap. Laporan siap dibuat.</p>
            <?php else: ?>
                <p class="mb-2"><i class="fas fa-exclamation-triangle"></i> <b>Laporan belum dapat dibuat</b> — lengkapi:</p>
                <ul class="mb-0"><?php foreach ($missing as $m): ?><li><?= esc($m) ?></li><?php endforeach; ?></ul>
            <?php endif; ?>
        </div>
        <div class="pu-savebar">
            <?php if ($ready): ?>
                <a class="pu-btn pu-btn-primary" href="<?= site_url('pdfself/generateFullReport') ?>" target="_blank" rel="noopener"><i class="fas fa-file-pdf"></i> Generate Laporan</a>
            <?php else: ?>
                <button type="button" class="pu-btn pu-btn-primary" disabled><i class="fas fa-lock"></i> Generate Laporan</button>
            <?php endif; ?>
        </div>
    </section>
</div>

<style>
    .sf-komp { display: flex; flex-direction: column; align-items: center; min-width: 150px; padding: 12px 18px; border-radius: 16px; background: rgba(255,255,255,.12); color: #fff; text-align: center; }
    .sf-komp span { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; opacity: .85; }
    .sf-komp b { font-size: 2.4rem; line-height: 1.1; }
    .sf-komp em { font-style: normal; font-weight: 700; font-size: .9rem; }
    .sf-komp.r1 { background: #059669; } .sf-komp.r2 { background: #0284c7; } .sf-komp.r3 { background: #d97706; } .sf-komp.r4 { background: #dc2626; } .sf-komp.r5 { background: #7f1d1d; }
    .sf-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 16px; }
    .sf-stats > div { background: #fff; border: 1.5px solid var(--bd); border-radius: 14px; padding: 12px 14px; display: flex; flex-direction: column; gap: 4px; }
    .sf-stats span { font-size: .74rem; font-weight: 700; color: var(--mut); text-transform: uppercase; letter-spacing: .03em; }
    .sf-stats b { font-size: 1.5rem; color: var(--txt); line-height: 1.1; }
    .sf-stats b small { font-size: .9rem; color: var(--mut); font-weight: 600; }
    .sf-bar { display: block; height: 6px; border-radius: 999px; background: #e2e8f0; overflow: hidden; }
    .sf-bar > i { display: block; height: 100%; background: linear-gradient(90deg, #34d399, #10b981); }
    .sf-legend { display: inline-flex; align-items: center; gap: 3px; font-size: .7rem; color: var(--mut); }
    .sf-legend i { font-style: normal; display: inline-flex; width: 18px; height: 18px; align-items: center; justify-content: center; border-radius: 5px; color: #fff; font-weight: 800; font-size: .68rem; }
    .r1 { --rc: #059669; } .r2 { --rc: #0284c7; } .r3 { --rc: #d97706; } .r4 { --rc: #dc2626; } .r5 { --rc: #7f1d1d; } .r0 { --rc: #cbd5e1; }
    .sf-legend i { background: var(--rc); }
    .sf-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 10px; }
    .sf-f { display: flex; align-items: center; gap: 12px; padding: 12px 14px; background: #fff; border: 1.5px solid var(--bd); border-left-width: 5px; border-radius: 14px; color: inherit; text-decoration: none !important; transition: box-shadow .15s, transform .15s; }
    .sf-f:hover, .sf-f:focus { box-shadow: 0 8px 22px rgba(31,42,168,.1); transform: translateY(-2px); }
    .sf-f.ok { border-left-color: #10b981; } .sf-f.wait { border-left-color: #f59e0b; } .sf-f.empty { border-left-color: #cbd5e1; }
    .sf-no { flex: none; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; background: #eef2ff; color: var(--c2); font-weight: 800; }
    .sf-fb { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 5px; }
    .sf-fb b { font-size: .86rem; line-height: 1.3; color: var(--txt); }
    .sf-ap { font-size: .7rem; color: var(--mut); font-weight: 600; }
    .sf-ap i { color: #cbd5e1; margin-left: 4px; } .sf-ap i:first-child { margin-left: 0; } .sf-ap i.on { color: #10b981; }
    .sf-n { flex: none; width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center; border-radius: 12px; background: var(--rc); color: #fff; font-weight: 800; font-size: 1.15rem; }
    .sf-foot { font-size: .76rem; color: var(--mut); margin: 12px 0 0; }
    .sf-pn { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .sf-pn fieldset { border: 1.5px solid var(--bd); border-radius: 14px; padding: 4px 14px 2px; min-width: 0; }
    .sf-pn legend { width: auto; font-size: .85rem; font-weight: 800; padding: 0 6px; margin: 0; }
    .sf-pn .pos legend { color: #047857; } .sf-pn .neg legend { color: #b91c1c; }
    .sf-dirty { margin-right: auto; font-size: .78rem; color: var(--mut); } .sf-dirty i { font-size: .5rem; vertical-align: middle; }
    .sf-dirty.on { color: #b45309; font-weight: 700; }
    .sf-doc { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; padding: 12px 14px; border: 1.5px dashed #c7d2fe; border-radius: 12px; background: #f8faff; margin-top: 10px; }
    .sf-doc.has { border-style: solid; border-color: #bbf7d0; background: #f0fdf4; }
    .sf-doc-ic { font-size: 1.6rem; color: #6366f1; } .sf-doc.has .sf-doc-ic { color: #dc2626; }
    .sf-doc-b { flex: 1; min-width: 200px; display: flex; flex-direction: column; gap: 2px; }
    .sf-doc-b b { font-size: .86rem; } .sf-doc-b small { font-size: .74rem; color: var(--mut); }
    .sf-doc-act { display: flex; gap: 6px; flex-wrap: wrap; }
    .sf-doc.busy .sf-doc-act { pointer-events: none; opacity: .5; }
    .sf-up { display: flex; align-items: center; gap: 8px; } .sf-up[hidden] { display: none; } .sf-up .sf-bar { flex: 1; }
    .sf-covers { display: grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 12px; }
    .sf-cover { margin: 0; cursor: pointer; text-align: center; font-size: .78rem; font-weight: 700; color: var(--mut); }
    .sf-cover input { position: absolute; opacity: 0; }
    .sf-cover img { display: block; width: 100%; height: auto; aspect-ratio: 12 / 17; object-fit: cover; border-radius: 10px; border: 3px solid transparent; box-shadow: 0 2px 8px rgba(15,23,42,.1); transition: border-color .15s, transform .15s; margin-bottom: 4px; background: #f1f5f9; }
    .sf-cover:hover img { transform: translateY(-2px); }
    .sf-cover input:checked + img { border-color: var(--c2); } .sf-cover input:checked ~ span { color: var(--c2); }
    .sf-cover input:focus-visible + img { outline: 3px solid #a5b4fc; }
    .sf-h3 { font-size: .82rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; color: var(--mut); margin: 4px 0 10px; }
    .sf-covers + .sf-h3 { margin-top: 22px; }
    .sf-cover span { display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .sf-new { font-style: normal; font-size: .62rem; font-weight: 800; color: #fff; background: #10b981; border-radius: 6px; padding: 1px 5px; vertical-align: 1px; }
    .sf-cover-up { display: flex; flex-direction: column; }
    .sf-cover-add { flex: 1; aspect-ratio: 12 / 17; display: flex !important; flex-direction: column; align-items: center; justify-content: center; gap: 4px; white-space: normal !important; border: 2px dashed #c7d2fe; border-radius: 10px; background: #f8faff; color: #312e81; padding: 8px; text-align: center; transition: background .15s, border-color .15s; }
    .sf-cover-add i { font-size: 1.6rem; color: #6366f1; } .sf-cover-add small { font-weight: 600; font-size: .66rem; color: var(--mut); }
    .sf-cover-up:hover .sf-cover-add, .sf-cover-up.over .sf-cover-add { background: #eef2ff; border-color: #818cf8; }
    .sf-cover-up.busy { pointer-events: none; opacity: .7; } .sf-cover-up .sf-up { margin-top: 6px; }
    .sf-cset { margin-top: 16px; border: 1.5px solid #c7d2fe; background: #f8faff; border-radius: 14px; padding: 14px; }
    .sf-cset[hidden] { display: none; }
    .sf-cset-head { display: flex; flex-direction: column; gap: 2px; margin-bottom: 12px; } .sf-cset-head small { color: var(--mut); font-size: .76rem; }
    .sf-cset-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 12px; }
    .sf-seg > span { display: block; font-size: .74rem; font-weight: 700; color: var(--mut); margin-bottom: 5px; }
    .sf-seg [role=radiogroup] { display: flex; flex-wrap: wrap; gap: 4px; }
    .sf-seg label { margin: 0; cursor: pointer; }
    .sf-seg input { position: absolute; opacity: 0; }
    .sf-seg label span { display: inline-block; padding: 5px 10px; border: 1.5px solid var(--bd); border-radius: 999px; background: #fff; font-size: .76rem; font-weight: 600; color: #334155; }
    .sf-seg input:checked + span { background: var(--c2); border-color: var(--c2); color: #fff; }
    .sf-seg input:focus-visible + span { outline: 3px solid #a5b4fc; }
    .sf-cset-act { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 8px; margin-top: 14px; } .sf-cset-act #cvDel { margin-right: auto; }
    .sf-check { border-color: #fcd34d; background: #fffbeb; color: #92400e; font-size: .86rem; }
    .sf-check.ok { border-color: #6ee7b7; background: #ecfdf5; color: #065f46; }
    .sf-check ul { padding-left: 20px; } .sf-check .pu-savebar { background: transparent; justify-content: center; }
    @media (max-width: 768px) { .sf-pn { grid-template-columns: 1fr; } .sf-stats { grid-template-columns: 1fr; gap: 8px; } .sf-stats > div { flex-direction: row; flex-wrap: wrap; align-items: baseline; justify-content: space-between; } .sf-stats .sf-bar { flex-basis: 100%; } .sf-komp { flex-direction: row; gap: 10px; min-width: 0; width: 100%; justify-content: center; } .sf-komp b { font-size: 1.8rem; } }
    @media (prefers-reduced-motion: reduce) { .sf-f, .sf-cover img { transition: none; } .sf-f:hover, .sf-cover:hover img { transform: none; } }
</style>

<input type="hidden" id="puCsrf" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
<script>
    (function () {
        'use strict';
        // Penghitung karakter (juga untuk pembaca/non-editor)
        document.querySelectorAll('#sfText textarea').forEach(function (ta) {
            var c = document.querySelector('[data-c="' + ta.name + '"]'), up = function () { c.textContent = ta.value.length.toLocaleString('id-ID'); c.parentNode.classList.toggle('warn', ta.value.length >= ta.maxLength * .9); };
            ta.addEventListener('input', up); up();
        });
        <?php if (!$canEdit): ?>return;<?php endif; ?>

        var csrf = document.getElementById('puCsrf'), busy = false, MAX = <?= SF::MAX_PDF_KB ?> * 1024;
        function toast(msg, err) { var d = document.createElement('div'); if (err) d.className = 'e'; d.textContent = msg; document.getElementById('pu-toast').appendChild(d); setTimeout(function () { d.remove(); }, 3800); }
        function done(j) { if (j.csrf) { csrf.name = j.csrf.name; csrf.value = j.csrf.hash; } toast(j.message || 'Selesai.', !j.ok); if (j.ok && j.reload) setTimeout(function () { location.reload(); }, 700); return j.ok; }
        function post(url, fd) {
            fd.set(csrf.name, csrf.value);
            return fetch('<?= site_url('ShowFaktor') ?>/' + url, { method: 'POST', body: fd, credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); });
        }
        function saveForm(formId, btnId, url, after) {
            var f = document.getElementById(formId), btn = document.getElementById(btnId);
            btn.addEventListener('click', function () {
                if (busy) return; busy = true;
                var ori = btn.innerHTML; btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan…';
                f.querySelectorAll('.pu-field').forEach(function (w) { w.classList.remove('err'); var m = w.querySelector('[data-msg]'); if (m) m.textContent = ''; });
                post(url, new FormData(f)).then(function (j) {
                    Object.keys(j.errors || {}).forEach(function (k) { var w = f.querySelector('[data-f="' + k + '"]'); if (w) { w.classList.add('err'); w.querySelector('[data-msg]').textContent = j.errors[k]; } });
                    if (done(j) && after) after();
                    if (!j.ok || !j.reload) { busy = false; btn.disabled = false; btn.innerHTML = ori; }
                }).catch(function () { toast('Koneksi bermasalah.', true); busy = false; btn.disabled = false; btn.innerHTML = ori; });
            });
        }

        // Kesimpulan: penanda perubahan + peringatan sebelum meninggalkan halaman
        var tf = document.getElementById('sfText'), dirty = document.getElementById('sfDirty'), snap = new URLSearchParams(new FormData(tf)).toString();
        function isDirty() { return new URLSearchParams(new FormData(tf)).toString() !== snap; }
        tf.addEventListener('input', function () { var d = isDirty(); dirty.classList.toggle('on', d); dirty.lastChild.textContent = d ? ' Ada perubahan belum disimpan' : ' Belum ada perubahan'; });
        window.addEventListener('beforeunload', function (e) { if (isDirty()) { e.preventDefault(); e.returnValue = ''; } });
        saveForm('sfText', 'sfTextSave', 'saveText', function () { snap = new URLSearchParams(new FormData(tf)).toString(); dirty.classList.remove('on'); dirty.lastChild.textContent = ' Tersimpan'; });
        saveForm('sfTtd', 'sfTtdSave', 'saveTtd');

        // Cover: template disimpan langsung; desain sendiri menampilkan pengaturan
        var cvSet = document.getElementById('cvSet'), cvPrev = document.getElementById('cvPreview'), PREV = '<?= site_url('ShowFaktor/coverPreview') ?>';
        function cvCurrent() { var r = document.querySelector('.sf-cover input[name="cover"]:checked'); return r ? r.value : ''; }
        function cvSettings() { var s = {}; cvSet.querySelectorAll('.sf-seg').forEach(function (g) { var c = g.querySelector('input:checked'); if (c) s[g.dataset.k] = c.value; }); return s; }
        function cvUrl(withSettings) {
            var p = new URLSearchParams(); p.set('cover', cvCurrent());
            if (withSettings) { var s = cvSettings(); Object.keys(s).forEach(function (k) { p.set('settings[' + k + ']', s[k]); }); }
            return PREV + '?' + p.toString();
        }
        function cvShow(input) {
            var data = input && input.dataset.custom ? JSON.parse(input.dataset.custom) : null;
            cvSet.hidden = !data;
            if (data) Object.keys(data).forEach(function (k) { var o = cvSet.querySelector('input[name="cs_' + k + '"][value="' + data[k] + '"]'); if (o) o.checked = true; });
            cvPrev.href = cvUrl(false);
        }
        document.querySelectorAll('.sf-cover input[name="cover"]').forEach(function (r) {
            r.addEventListener('change', function () {
                cvShow(r);
                var fd = new FormData(); fd.set('cover', r.value);
                post('saveCover', fd).then(done).catch(function () { toast('Koneksi bermasalah.', true); });
            });
        });
        cvShow(document.querySelector('.sf-cover input[name="cover"]:checked'));
        cvSet.addEventListener('change', function () { cvPrev.href = cvUrl(true); });
        document.getElementById('cvTry').addEventListener('click', function () { window.open(cvUrl(true), '_blank', 'noopener'); });
        document.getElementById('cvSave').addEventListener('click', function () {
            var b = this, fd = new FormData(), s = cvSettings(), cur = document.querySelector('.sf-cover input[name="cover"]:checked');
            fd.set('cover', cvCurrent()); Object.keys(s).forEach(function (k) { fd.set('settings[' + k + ']', s[k]); });
            b.disabled = true;
            post('saveCover', fd).then(function (j) {
                if (done(j) && cur) { var d = JSON.parse(cur.dataset.custom || '{}'); Object.assign(d, s); cur.dataset.custom = JSON.stringify(d); }
                b.disabled = false;
            }).catch(function () { toast('Koneksi bermasalah.', true); b.disabled = false; });
        });
        document.getElementById('cvDel').addEventListener('click', function () {
            if (busy || !confirm('Hapus desain ini? File akan dihapus permanen.')) return; busy = true;
            var b = this, fd = new FormData(); fd.set('cover', cvCurrent()); b.disabled = true;
            post('deleteCover', fd).then(function (j) { if (!done(j)) { busy = false; b.disabled = false; } }).catch(function () { toast('Koneksi bermasalah.', true); busy = false; b.disabled = false; });
        });
        var cvFile = document.getElementById('cvFile'), cvZone = document.getElementById('cvZone');
        function cvUpload(file) {
            if (!file || busy) return;
            if (!/\.(jpe?g|png)$/i.test(file.name)) { toast('File harus berupa gambar JPG atau PNG.', true); return; }
            if (file.size > <?= \App\Libraries\CoverDesign::MAX_UPLOAD_KB ?> * 1024) { toast('Ukuran file maksimal <?= \App\Libraries\CoverDesign::MAX_UPLOAD_KB / 1024 ?> MB.', true); return; }
            busy = true; cvZone.classList.add('busy');
            var up = cvZone.querySelector('.sf-up'), bar = up.querySelector('i'), pct = up.querySelector('small'); up.hidden = false;
            var fd = new FormData(); fd.set(csrf.name, csrf.value); fd.set('img', file);
            var x = new XMLHttpRequest(); x.open('POST', '<?= site_url('ShowFaktor/uploadCover') ?>');
            x.setRequestHeader('Accept', 'application/json'); x.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            x.upload.onprogress = function (e) { if (e.lengthComputable) { var p = Math.round(e.loaded / e.total * 100); bar.style.width = p + '%'; pct.textContent = p < 100 ? p + '%' : 'Memproses…'; } };
            var fail = function () { busy = false; cvZone.classList.remove('busy'); up.hidden = true; cvFile.value = ''; };
            x.onload = function () { var j; try { j = JSON.parse(x.responseText); } catch (e) { j = { ok: false, message: 'Respons server tidak valid (' + x.status + ').' }; } if (!done(j)) fail(); };
            x.onerror = function () { toast('Koneksi bermasalah, desain belum terunggah.', true); fail(); };
            x.send(fd);
        }
        cvFile.addEventListener('change', function () { cvUpload(cvFile.files[0]); });
        ['dragenter', 'dragover'].forEach(function (ev) { cvZone.addEventListener(ev, function (e) { e.preventDefault(); cvZone.classList.add('over'); }); });
        ['dragleave', 'drop'].forEach(function (ev) { cvZone.addEventListener(ev, function (e) { e.preventDefault(); cvZone.classList.remove('over'); }); });
        cvZone.addEventListener('drop', function (e) { if (e.dataTransfer && e.dataTransfer.files.length) cvUpload(e.dataTransfer.files[0]); });

        // Dokumen: unggah dengan progres, hapus
        document.querySelectorAll('[data-up]').forEach(function (inp) {
            inp.addEventListener('change', function () {
                var file = inp.files[0], box = inp.closest('.sf-doc'); if (!file || busy) return;
                if (!/\.pdf$/i.test(file.name)) { toast('File harus berupa PDF.', true); inp.value = ''; return; }
                if (file.size > MAX) { toast('Ukuran file maksimal <?= SF::MAX_PDF_KB / 1024 ?> MB.', true); inp.value = ''; return; }
                if (box.classList.contains('has') && !confirm('Ganti dokumen yang sudah ada dengan "' + file.name + '"?')) { inp.value = ''; return; }
                busy = true; box.classList.add('busy');
                var up = box.querySelector('.sf-up'), bar = up.querySelector('i'), pct = up.querySelector('small'); up.hidden = false;
                var fd = new FormData(); fd.set(csrf.name, csrf.value); fd.set('slot', inp.dataset.up); fd.set('pdf', file);
                var x = new XMLHttpRequest(); x.open('POST', '<?= site_url('ShowFaktor/uploadPdf') ?>');
                x.setRequestHeader('Accept', 'application/json'); x.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                x.upload.onprogress = function (e) { if (e.lengthComputable) { var p = Math.round(e.loaded / e.total * 100); bar.style.width = p + '%'; pct.textContent = p + '%'; } };
                var fail = function () { busy = false; box.classList.remove('busy'); up.hidden = true; inp.value = ''; };
                x.onload = function () { var j; try { j = JSON.parse(x.responseText); } catch (e) { j = { ok: false, message: 'Respons server tidak valid (' + x.status + ').' }; } if (!done(j)) fail(); };
                x.onerror = function () { toast('Koneksi bermasalah, file belum terunggah.', true); fail(); };
                x.send(fd);
            });
        });
        document.querySelectorAll('[data-del]').forEach(function (b) {
            b.addEventListener('click', function () {
                if (busy || !confirm('Hapus dokumen ini? File akan dihapus permanen.')) return; busy = true; b.disabled = true;
                var fd = new FormData(); fd.set('slot', b.dataset.del);
                post('deletePdf', fd).then(function (j) { if (!done(j)) { busy = false; b.disabled = false; } }).catch(function () { toast('Koneksi bermasalah.', true); busy = false; b.disabled = false; });
            });
        });
    })();
</script>
