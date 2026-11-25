<?php
/**
 * Dokumen Pendukung laporan tahunan.
 * Variabel: $periode, $tahun, $namabpr, $row, $kota, $tanggalsurat, $anggota, $prefilled, $hasAnggota, $hasSurat,
 *           $slots, $complete, $canEdit, $pdfMaxKb, $mergedName
 */
use App\Models\M_dokumenpendukung;

$dis = $canEdit ? '' : 'disabled';
$base = rtrim(site_url('dokumenpendukung'), '/');
$fmtSize = static fn(int $b) => $b >= 1048576 ? number_format($b / 1048576, 1, ',', '.') . ' MB' : number_format($b / 1024, 0, ',', '.') . ' KB';

/** Satu baris anggota (dipakai juga untuk <template>). */
$anggotaRow = static function (string $jenis, array $a, $i) use ($dis): void { ?>
    <div class="dp-member" data-row>
        <span class="dp-member-no" data-no><?= is_int($i) ? $i + 1 : '' ?></span>
        <div class="dp-field">
            <input type="text" name="<?= $jenis ?>_nama[]" class="dp-input" maxlength="<?= M_dokumenpendukung::MAX_NAMA ?>"
                placeholder="Nama lengkap" value="<?= esc($a['nama'] ?? '') ?>" data-err-key="<?= $jenis ?>_nama" <?= $dis ?>>
            <div class="dp-err"></div>
        </div>
        <div class="dp-field">
            <input type="text" name="<?= $jenis ?>_jabatan[]" class="dp-input" maxlength="<?= M_dokumenpendukung::MAX_NAMA ?>"
                placeholder="Jabatan" value="<?= esc($a['jabatan'] ?? '') ?>" data-err-key="<?= $jenis ?>_jabatan" <?= $dis ?>>
            <div class="dp-err"></div>
        </div>
        <?php if ($dis === ''): ?>
            <button type="button" class="dp-icon-btn dp-del" data-del title="Hapus baris" aria-label="Hapus baris"><i class="fas fa-trash-alt"></i></button>
        <?php endif; ?>
    </div>
<?php };

/** Kotak unggah satu slot. */
$uploadBox = static function (string $slot, array $st) use ($canEdit, $base, $fmtSize, $pdfMaxKb): void { ?>
    <div class="dp-upload" data-slot="<?= $slot ?>">
        <div class="dp-file <?= $st['uploaded'] ? '' : 'is-empty' ?>" data-file>
            <i class="fas <?= $st['uploaded'] ? 'fa-file-pdf' : 'fa-cloud-upload-alt' ?>"></i>
            <div class="dp-file-info">
                <strong data-file-title><?= $st['uploaded'] ? 'PDF sudah diunggah' : 'Belum ada file' ?></strong>
                <small data-file-meta><?= $st['uploaded'] ? $fmtSize((int) $st['size']) . ' · ' . esc($st['at']) : 'PDF, maks. ' . ($pdfMaxKb / 1024) . ' MB' ?></small>
            </div>
            <a href="<?= $base ?>/file/<?= $slot ?>" target="_blank" rel="noopener" class="dp-btn dp-btn-light" data-view <?= $st['uploaded'] ? '' : 'hidden' ?>>
                <i class="fas fa-eye"></i> Lihat
            </a>
            <?php if ($canEdit): ?>
                <button type="button" class="dp-btn dp-btn-danger-o" data-remove <?= $st['uploaded'] ? '' : 'hidden' ?>><i class="fas fa-trash-alt"></i></button>
            <?php endif; ?>
        </div>
        <?php if ($canEdit): ?>
            <label class="dp-drop" data-drop>
                <input type="file" accept="application/pdf,.pdf" data-input hidden>
                <i class="fas fa-file-upload"></i>
                <span data-drop-text><?= $st['uploaded'] ? 'Ganti file: klik atau seret PDF ke sini' : 'Klik atau seret PDF bertanda tangan ke sini' ?></span>
            </label>
            <div class="dp-progress" data-progress hidden><div class="dp-progress-bar" data-bar></div></div>
        <?php endif; ?>
    </div>
<?php };

$done1 = $hasAnggota;
$sections = [
    ['n' => 1, 'done' => $done1],
    ['n' => 2, 'done' => $slots['suratdireksi']['uploaded']],
    ['n' => 3, 'done' => $slots['lembar']['uploaded']],
    ['n' => 4, 'done' => $slots['pengendalian']['uploaded']],
];
$doneCount = count(array_filter($sections, static fn($s) => $s['done']));
?>
<div id="toast-wrap" class="dp-toasts" aria-live="polite"></div>

<div class="dp" id="dp" data-base="<?= esc($base, 'attr') ?>" data-csrf-name="<?= csrf_token() ?>" data-csrf-hash="<?= csrf_hash() ?>"
    data-max-kb="<?= (int) $pdfMaxKb ?>" data-max-anggota="<?= M_dokumenpendukung::MAX_ANGGOTA ?>">

    <a href="<?= base_url('Showtahunan') ?>" class="dp-back"><i class="fas fa-arrow-left"></i> Kembali</a>

    <?php if ($m = session()->getFlashdata('message')): ?>
        <div class="dp-flash ok"><?= esc($m) ?></div>
    <?php endif; ?>
    <?php if ($m = session()->getFlashdata('error')): ?>
        <div class="dp-flash err"><?= esc($m) ?></div>
    <?php endif; ?>

    <header class="dp-head">
        <h1>Dokumen Pendukung</h1>
        <div class="dp-sub">Laporan Tahunan · Periode Tahun <?= esc($tahun) ?></div>
        <span class="dp-bpr"><?= esc($namabpr ?: 'Nama BPR') ?></span>
        <div class="dp-steps" aria-label="Kemajuan">
            <?php foreach ($sections as $s): ?>
                <a href="#bagian-<?= $s['n'] ?>" class="dp-step<?= $s['done'] ? ' done' : '' ?>" data-step="<?= $s['n'] ?>">
                    <span><?= $s['done'] ? '<i class="fas fa-check"></i>' : $s['n'] ?></span>
                </a>
            <?php endforeach; ?>
            <div class="dp-steps-lbl"><strong data-done-count><?= $doneCount ?></strong>/4 bagian selesai</div>
        </div>
    </header>

    <!-- ===================== 1. Direksi & Dewan Komisaris ===================== -->
    <section class="dp-card" id="bagian-1">
        <div class="dp-card-head">
            <span class="dp-num">1</span>
            <div>
                <h2>Daftar Direksi dan Dewan Komisaris</h2>
                <p>Penandatangan Lembar Pernyataan (Bagian 3). Simpan untuk memperbarui template.</p>
            </div>
            <span class="dp-badge <?= $done1 ? 'ok' : 'todo' ?>" data-badge="1"><?= $done1 ? 'Tersimpan' : 'Belum disimpan' ?></span>
        </div>
        <div class="dp-card-body">
            <?php if ($prefilled): ?>
                <div class="dp-note"><i class="fas fa-info-circle"></i> Daftar Direksi diisi otomatis dari Surat Pernyataan Direksi. Periksa, lengkapi Dewan Komisaris, lalu simpan.</div>
            <?php endif; ?>

            <form id="formAnggota" novalidate>
                <div class="dp-grid2">
                    <div class="dp-field">
                        <label class="dp-label" for="kota">Tempat Penandatanganan</label>
                        <input type="text" id="kota" name="kota" class="dp-input" maxlength="<?= M_dokumenpendukung::MAX_KOTA ?>" placeholder="Contoh: Surabaya" value="<?= esc($kota) ?>" data-err-key="kota" <?= $dis ?>>
                        <div class="dp-err"></div>
                    </div>
                    <div class="dp-field">
                        <label class="dp-label" for="tanggalsurat">Tanggal Penandatanganan</label>
                        <input type="date" id="tanggalsurat" name="tanggalsurat" class="dp-input" value="<?= esc((string) $tanggalsurat) ?>" data-err-key="tanggalsurat" <?= $dis ?>>
                        <div class="dp-err"></div>
                    </div>
                </div>

                <div class="dp-grid2 dp-lists">
                    <?php foreach (M_dokumenpendukung::JENIS as $jenis => [$judul, $contoh]): ?>
                        <div class="dp-list" data-jenis="<?= $jenis ?>">
                            <div class="dp-list-head">
                                <h3><i class="fas <?= $jenis === 'direksi' ? 'fa-user-tie' : 'fa-users' ?>"></i> <?= esc($judul) ?></h3>
                                <span class="dp-count" data-count><?= count($anggota[$jenis]) ?></span>
                            </div>
                            <div class="dp-err dp-err-block" data-err-for="<?= $jenis ?>"></div>
                            <div class="dp-members" data-members>
                                <?php foreach ($anggota[$jenis] as $i => $a) { $anggotaRow($jenis, $a, $i); } ?>
                            </div>
                            <template data-tpl><?php $anggotaRow($jenis, ['nama' => '', 'jabatan' => ''], '__i__'); ?></template>
                            <?php if ($canEdit): ?>
                                <button type="button" class="dp-add" data-add><i class="fas fa-plus"></i> Tambah <?= esc($judul) ?></button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($canEdit): ?>
                    <div class="dp-actions">
                        <button type="submit" class="dp-btn dp-btn-primary" id="btnSaveAnggota"><i class="fas fa-save"></i> <span>Simpan</span></button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </section>

    <!-- ===================== 2. Surat Pernyataan Direksi (TTD) ===================== -->
    <section class="dp-card" id="bagian-2">
        <div class="dp-card-head">
            <span class="dp-num">2</span>
            <div>
                <h2>Surat Pernyataan Direksi Terkait Kebenaran Laporan Keuangan Tahunan Yang Telah Ditandatangani</h2>
                <p>Periode Tahun <?= esc($tahun) ?></p>
            </div>
            <span class="dp-badge <?= $slots['suratdireksi']['uploaded'] ? 'ok' : 'todo' ?>" data-badge="2"><?= $slots['suratdireksi']['uploaded'] ? 'Sudah diunggah' : 'Belum diunggah' ?></span>
        </div>
        <div class="dp-card-body">
            <ol class="dp-howto">
                <li>Lihat / unduh draf Surat Pernyataan Direksi, cetak, lalu tandatangani (dengan meterai bila diperlukan).</li>
                <li>Pindai (scan) surat yang telah ditandatangani dan unggah PDF-nya di bawah.</li>
            </ol>
            <?php if ($hasSurat): ?>
                <div class="dp-preview-bar">
                    <a href="<?= site_url('suratpernyataandireksi/pdf') ?>" target="_blank" rel="noopener" class="dp-btn dp-btn-light"><i class="fas fa-external-link-alt"></i> Buka draf</a>
                    <button type="button" class="dp-btn dp-btn-light" data-preview="<?= site_url('suratpernyataandireksi/pdf') ?>"><i class="fas fa-eye"></i> Tampilkan pratinjau</button>
                    <a href="<?= site_url('suratpernyataandireksi') ?>" class="dp-link">Ubah isi surat</a>
                </div>
                <div class="dp-frame" hidden></div>
            <?php else: ?>
                <div class="dp-note warn"><i class="fas fa-exclamation-triangle"></i> Surat Pernyataan Direksi belum diisi.
                    <a href="<?= site_url('suratpernyataandireksi') ?>">Isi Surat Pernyataan Direksi</a> untuk membuat drafnya, atau langsung unggah surat yang sudah ditandatangani.</div>
            <?php endif; ?>
            <?php $uploadBox('suratdireksi', $slots['suratdireksi']); ?>
        </div>
    </section>

    <!-- ===================== 3. Lembar Pernyataan (TTD) ===================== -->
    <section class="dp-card" id="bagian-3">
        <div class="dp-card-head">
            <span class="dp-num">3</span>
            <div>
                <h2>Lembar Pernyataan Anggota Direksi dan Anggota Dewan Komisaris tentang Tanggung Jawab atas Laporan Tahunan Yang Telah Ditandatangani</h2>
                <p>Periode Tahun <?= esc($tahun) ?></p>
            </div>
            <span class="dp-badge <?= $slots['lembar']['uploaded'] ? 'ok' : 'todo' ?>" data-badge="3"><?= $slots['lembar']['uploaded'] ? 'Sudah diunggah' : 'Belum diunggah' ?></span>
        </div>
        <div class="dp-card-body">
            <ol class="dp-howto">
                <li>Template dibuat otomatis dari Bagian 1 (kop surat BPR, daftar Direksi & Dewan Komisaris).</li>
                <li>Cetak, tandatangani oleh seluruh anggota, pindai, lalu unggah PDF-nya di bawah.</li>
            </ol>
            <div class="dp-preview-bar" data-need-anggota <?= $hasAnggota ? '' : 'hidden' ?>>
                <a href="<?= $base ?>/lembar" target="_blank" rel="noopener" class="dp-btn dp-btn-light"><i class="fas fa-download"></i> Buka / unduh template</a>
                <button type="button" class="dp-btn dp-btn-light" data-preview="<?= $base ?>/lembar"><i class="fas fa-eye"></i> Tampilkan pratinjau</button>
            </div>
            <div class="dp-frame" hidden></div>
            <div class="dp-note warn" data-no-anggota <?= $hasAnggota ? 'hidden' : '' ?>><i class="fas fa-exclamation-triangle"></i> Simpan daftar Direksi & Dewan Komisaris di Bagian 1 untuk membuat template.</div>
            <?php $uploadBox('lembar', $slots['lembar']); ?>
        </div>
    </section>

    <!-- ===================== 4. Laporan Pengendalian Internal ===================== -->
    <section class="dp-card" id="bagian-4">
        <div class="dp-card-head">
            <span class="dp-num">4</span>
            <div>
                <h2>Unggah Laporan Pengendalian Internal Dalam Proses Pelaporan Keuangan Bank</h2>
                <p>(Dengan Lampiran Hasil Pengujian Atas Pos-pos Laporan Keuangan dan Self Assessment Pengendalian Internal Pelaporan Keuangan Bank) · Periode Tahun <?= esc($tahun) ?></p>
            </div>
            <span class="dp-badge <?= $slots['pengendalian']['uploaded'] ? 'ok' : 'todo' ?>" data-badge="4"><?= $slots['pengendalian']['uploaded'] ? 'Sudah diunggah' : 'Belum diunggah' ?></span>
        </div>
        <div class="dp-card-body">
            <?php $uploadBox('pengendalian', $slots['pengendalian']); ?>
        </div>
    </section>

    <!-- ===================== Hasil gabungan ===================== -->
    <section class="dp-merged <?= $complete ? 'ready' : '' ?>" id="merged">
        <i class="fas <?= $complete ? 'fa-file-pdf' : 'fa-layer-group' ?>" data-merged-icon></i>
        <div class="dp-merged-info">
            <strong>PDF Gabungan Dokumen Pendukung</strong>
            <small>Bagian 2 + 3 + 4 digabung menjadi satu file untuk ZIP laporan tahunan:</small>
            <code><?= esc($mergedName) ?></code>
            <small data-merged-status><?= $complete ? 'Siap — termasuk otomatis dalam ZIP laporan tahunan.' : 'Tersedia setelah ketiga dokumen diunggah.' ?></small>
        </div>
        <a href="<?= $base ?>/gabungan" target="_blank" rel="noopener" class="dp-btn dp-btn-primary" data-merged-link <?= $complete ? '' : 'hidden' ?>>
            <i class="fas fa-eye"></i> Lihat PDF gabungan
        </a>
    </section>
</div>

<style>
    .dp { max-width: 1100px; margin: 0 auto; padding: 0 16px 48px; color: #1e293b; --brand: #1f2aa8; --brand-d: #141863; }
    .dp-back { display: inline-flex; align-items: center; gap: 6px; margin-bottom: 14px; padding: 8px 14px; border: 1.5px solid #e2e8f0; border-radius: 8px; background: #fff; color: #475569; font-weight: 600; font-size: .88rem; text-decoration: none; }
    .dp-back:hover { border-color: #94a3b8; color: #1e293b; text-decoration: none; }
    .dp-flash { margin-bottom: 12px; padding: 12px 16px; border-radius: 12px; font-weight: 600; font-size: .88rem; }
    .dp-flash.ok { background: #d1fae5; color: #065f46; }
    .dp-flash.err { background: #fee2e2; color: #991b1b; }

    .dp-head { text-align: center; padding: 22px 16px 18px; margin-bottom: 16px; background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(15, 23, 42, .06); }
    .dp-head h1 { font-size: clamp(1.25rem, 3.5vw, 1.6rem); font-weight: 800; margin: 0 0 4px; }
    .dp-sub { color: #3b82f6; font-weight: 700; margin-bottom: 10px; }
    .dp-bpr { display: inline-block; padding: 5px 16px; border-radius: 999px; background: linear-gradient(135deg, var(--brand), var(--brand-d)); color: #fff; font-weight: 600; font-size: .9rem; }
    .dp-steps { display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 16px; flex-wrap: wrap; }
    .dp-step { width: 34px; height: 34px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: #f1f5f9; color: #64748b; font-weight: 800; text-decoration: none; border: 2px solid #e2e8f0; }
    .dp-step.done { background: #10b981; border-color: #10b981; color: #fff; }
    .dp-step:hover { text-decoration: none; border-color: var(--brand); }
    .dp-steps-lbl { font-size: .82rem; color: #64748b; margin-left: 6px; }

    .dp-card { background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(15, 23, 42, .06); margin-bottom: 16px; overflow: hidden; scroll-margin-top: 80px; }
    .dp-card-head { display: flex; align-items: flex-start; gap: 14px; padding: 16px 20px; background: linear-gradient(135deg, var(--brand), var(--brand-d)); color: #fff; }
    .dp-card-head h2 { font-size: 1.02rem; font-weight: 700; margin: 0 0 2px; line-height: 1.35; color: #fff; }
    .dp-card-head p { margin: 0; font-size: .8rem; opacity: .85; }
    .dp-card-head > div { flex: 1; min-width: 0; }
    .dp-num { width: 32px; height: 32px; flex-shrink: 0; border-radius: 50%; background: rgba(255, 255, 255, .2); display: inline-flex; align-items: center; justify-content: center; font-weight: 800; }
    .dp-badge { flex-shrink: 0; font-size: .75rem; font-weight: 700; padding: 5px 10px; border-radius: 999px; white-space: nowrap; }
    .dp-badge.ok { background: #d1fae5; color: #047857; }
    .dp-badge.todo { background: rgba(255, 255, 255, .18); color: #fff; }
    .dp-card-body { padding: 18px 20px; }

    .dp-grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 18px; }
    .dp-lists { margin-top: 18px; }
    .dp-field { min-width: 0; }
    .dp-label { display: block; font-size: .85rem; font-weight: 600; color: #374151; margin-bottom: 6px; }
    .dp-input { width: 100%; padding: 9px 12px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: .92rem; transition: border-color .2s, box-shadow .2s; }
    .dp-input:focus { outline: none; border-color: var(--brand); box-shadow: 0 0 0 3px rgba(31, 42, 168, .1); }
    .dp-input:disabled { background: #f8fafc; }
    .dp-input.is-invalid { border-color: #dc3545; }
    .dp-err { font-size: .76rem; color: #dc3545; margin-top: 3px; }
    .dp-err:empty { display: none; }
    .dp-err-block { margin: 0 0 8px; }

    .dp-list { border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 12px; background: #f8fafc; }
    .dp-list-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
    .dp-list-head h3 { font-size: .95rem; font-weight: 700; margin: 0; color: #1e293b; }
    .dp-list-head h3 i { color: var(--brand); margin-right: 4px; }
    .dp-count { font-size: .75rem; font-weight: 700; background: #e0e7ff; color: #3730a3; border-radius: 999px; padding: 2px 9px; }
    .dp-member { display: grid; grid-template-columns: 26px 1fr 1fr 34px; gap: 8px; align-items: start; margin-bottom: 8px; }
    .dp-member-no { height: 40px; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #64748b; font-size: .85rem; }
    .dp-icon-btn { width: 34px; height: 40px; border: none; border-radius: 8px; background: transparent; color: #94a3b8; }
    .dp-icon-btn:hover { background: #fee2e2; color: #dc2626; }
    .dp-add { width: 100%; padding: 8px; border: 1.5px dashed #a5b4fc; border-radius: 10px; background: #fff; color: var(--brand); font-weight: 600; font-size: .86rem; }
    .dp-add:hover { background: #eef2ff; }
    .dp-add:disabled { opacity: .5; cursor: not-allowed; }
    .dp-actions { display: flex; justify-content: flex-end; margin-top: 16px; }

    .dp-btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border-radius: 10px; border: 1.5px solid transparent; font-weight: 600; font-size: .86rem; text-decoration: none; cursor: pointer; white-space: nowrap; }
    .dp-btn:hover { text-decoration: none; }
    .dp-btn-primary { background: linear-gradient(135deg, #1fa85a, #146337); color: #fff; }
    .dp-btn-primary:hover { color: #fff; box-shadow: 0 4px 14px rgba(16, 185, 129, .3); }
    .dp-btn-primary:disabled { opacity: .6; }
    .dp-btn-light { background: #fff; border-color: #e2e8f0; color: #334155; }
    .dp-btn-light:hover { border-color: var(--brand); color: var(--brand); }
    .dp-btn-danger-o { background: #fff; border-color: #fecaca; color: #dc2626; padding: 9px 12px; }
    .dp-btn-danger-o:hover { background: #fef2f2; }
    .dp-link { font-size: .85rem; font-weight: 600; }

    .dp-note { padding: 10px 14px; border-radius: 10px; background: #eff6ff; color: #1e40af; font-size: .85rem; margin-bottom: 14px; }
    .dp-note.warn { background: #fffbeb; color: #92400e; }
    .dp-note[hidden] { display: none; }
    .dp-howto { margin: 0 0 14px; padding-left: 20px; font-size: .86rem; color: #475569; }
    .dp-howto li { margin-bottom: 3px; }
    .dp-preview-bar { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 12px; }
    .dp-preview-bar[hidden] { display: none; }
    .dp-frame { border: 1.5px solid #e2e8f0; border-radius: 12px; overflow: hidden; margin-bottom: 14px; background: #f1f5f9; }
    .dp-frame iframe { display: block; width: 100%; height: 70vh; min-height: 420px; border: 0; }

    .dp-upload { border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 12px; }
    .dp-file { display: flex; align-items: center; gap: 12px; }
    .dp-file > i { font-size: 1.8rem; color: #dc2626; width: 32px; text-align: center; }
    .dp-file.is-empty > i { color: #94a3b8; }
    .dp-file-info { flex: 1; min-width: 0; display: flex; flex-direction: column; }
    .dp-file-info strong { font-size: .9rem; }
    .dp-file-info small { color: #64748b; }
    .dp-drop { display: flex; align-items: center; justify-content: center; gap: 10px; margin: 12px 0 0; padding: 16px; border: 2px dashed #cbd5e1; border-radius: 10px; color: #475569; font-size: .88rem; cursor: pointer; text-align: center; transition: border-color .2s, background .2s; }
    .dp-drop:hover, .dp-drop.over { border-color: var(--brand); background: #eef2ff; color: var(--brand); }
    .dp-drop i { font-size: 1.3rem; }
    .dp-progress { height: 8px; border-radius: 999px; background: #e2e8f0; overflow: hidden; margin-top: 10px; }
    .dp-progress[hidden] { display: none; }
    .dp-progress-bar { height: 100%; width: 0; background: linear-gradient(90deg, var(--brand), #3b82f6); transition: width .15s; }

    .dp-merged { display: flex; align-items: center; gap: 16px; padding: 18px 20px; border-radius: 16px; background: #f8fafc; border: 2px dashed #cbd5e1; }
    .dp-merged > i { font-size: 2.2rem; color: #94a3b8; }
    .dp-merged.ready { background: #ecfdf5; border: 2px solid #6ee7b7; }
    .dp-merged.ready > i { color: #dc2626; }
    .dp-merged-info { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 2px; }
    .dp-merged-info code { font-size: .82rem; color: #334155; word-break: break-all; background: none; padding: 0; }
    .dp-merged-info small { color: #64748b; }

    .dp-toasts { position: fixed; top: 20px; right: 20px; z-index: 99999; display: flex; flex-direction: column; gap: 8px; max-width: min(360px, calc(100vw - 40px)); pointer-events: none; }
    .dp-toast { padding: 12px 16px; border-radius: 10px; background: #fff; box-shadow: 0 8px 24px rgba(15, 23, 42, .15); border-left: 4px solid #10b981; font-size: .88rem; font-weight: 600; color: #1e293b; animation: dpIn .2s ease; }
    .dp-toast.err { border-left-color: #ef4444; }
    @keyframes dpIn { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: none; } }

    @media (max-width: 768px) {
        .dp-grid2 { grid-template-columns: 1fr; }
        .dp-card-head { flex-wrap: wrap; }
        .dp-card-head > div { flex-basis: calc(100% - 46px); }
        .dp-badge { margin-left: 46px; }
        .dp-merged { flex-direction: column; align-items: flex-start; }
    }
    @media (max-width: 480px) {
        .dp-member { grid-template-columns: 1fr 34px; }
        .dp-member-no { display: none; }
        .dp-member .dp-field:nth-of-type(2) { grid-column: 1; }
        .dp-member .dp-del { grid-row: 1; grid-column: 2; }
        .dp-file { flex-wrap: wrap; }
        .dp-frame iframe { height: 60vh; min-height: 320px; }
    }
    @media (prefers-reduced-motion: reduce) { .dp-toast { animation: none; } }
</style>

<script>
    (function () {
        'use strict';
        var root = document.getElementById('dp');
        if (!root || !window.fetch) return;
        var BASE = root.dataset.base, MAX_KB = parseInt(root.dataset.maxKb, 10), MAX_A = parseInt(root.dataset.maxAnggota, 10);
        var csrf = { name: root.dataset.csrfName, hash: root.dataset.csrfHash };

        function toast(msg, err) {
            var w = document.getElementById('toast-wrap'), t = document.createElement('div');
            t.className = 'dp-toast' + (err ? ' err' : '');
            t.textContent = msg;
            w.appendChild(t);
            setTimeout(function () { t.remove(); }, 4000);
        }
        function withCsrf(fd) { fd.append(csrf.name, csrf.hash); return fd; }
        function takeCsrf(j) { if (j && j.csrf) csrf = j.csrf; return j; }
        function post(path, fd) {
            return fetch(BASE + '/' + path, { method: 'POST', body: withCsrf(fd), credentials: 'same-origin', headers: { Accept: 'application/json' } })
                .then(function (r) { return r.json().catch(function () { return { ok: false, message: 'Respons server tidak valid.' }; }); })
                .then(takeCsrf);
        }

        // ---------------------------------------------------------------- kemajuan
        function setDone(n, done, label) {
            var b = root.querySelector('[data-badge="' + n + '"]'), s = root.querySelector('[data-step="' + n + '"]');
            if (b) { b.className = 'dp-badge ' + (done ? 'ok' : 'todo'); b.textContent = label; }
            if (s) { s.classList.toggle('done', done); s.firstElementChild.innerHTML = done ? '<i class="fas fa-check"></i>' : n; }
            root.querySelector('[data-done-count]').textContent = root.querySelectorAll('.dp-step.done').length;
        }
        function setMerged(complete) {
            var box = document.getElementById('merged');
            box.classList.toggle('ready', complete);
            box.querySelector('[data-merged-icon]').className = 'fas ' + (complete ? 'fa-file-pdf' : 'fa-layer-group');
            box.querySelector('[data-merged-link]').hidden = !complete;
            box.querySelector('[data-merged-status]').textContent = complete
                ? 'Siap — termasuk otomatis dalam ZIP laporan tahunan.' : 'Tersedia setelah ketiga dokumen diunggah.';
        }

        // ---------------------------------------------------------------- pratinjau (dimuat saat diminta)
        root.querySelectorAll('[data-preview]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var frame = btn.closest('.dp-card-body').querySelector('.dp-frame');
                if (!frame.hidden) { frame.hidden = true; frame.innerHTML = ''; btn.innerHTML = '<i class="fas fa-eye"></i> Tampilkan pratinjau'; return; }
                frame.innerHTML = '<iframe title="Pratinjau PDF" src="' + btn.dataset.preview + '?t=' + Date.now() + '"></iframe>';
                frame.hidden = false;
                btn.innerHTML = '<i class="fas fa-eye-slash"></i> Sembunyikan pratinjau';
            });
        });

        // ---------------------------------------------------------------- bagian 1: daftar anggota
        var form = document.getElementById('formAnggota');
        root.querySelectorAll('.dp-list').forEach(function (list) {
            var wrap = list.querySelector('[data-members]'), tpl = list.querySelector('[data-tpl]'), add = list.querySelector('[data-add]');
            function renumber() {
                var rows = wrap.querySelectorAll('[data-row]');
                rows.forEach(function (r, i) { r.querySelector('[data-no]').textContent = i + 1; });
                list.querySelector('[data-count]').textContent = rows.length;
                if (add) add.disabled = rows.length >= MAX_A;
                wrap.querySelectorAll('[data-del]').forEach(function (b) { b.disabled = rows.length <= 1; });
            }
            if (add) add.addEventListener('click', function () {
                wrap.insertAdjacentHTML('beforeend', tpl.innerHTML);
                renumber();
                wrap.lastElementChild.querySelector('input').focus();
            });
            wrap.addEventListener('click', function (e) {
                var del = e.target.closest('[data-del]');
                if (del && wrap.querySelectorAll('[data-row]').length > 1) { del.closest('[data-row]').remove(); renumber(); }
            });
            renumber();
        });

        function clearErrors() {
            form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
            form.querySelectorAll('.dp-err').forEach(function (el) { el.textContent = ''; });
        }
        function showErrors(errors) {
            var first = null;
            Object.keys(errors || {}).forEach(function (key) {
                var msg = errors[key], m = /^(.+)\.(\d+)$/.exec(key), el = null;
                if (m) {
                    el = form.querySelectorAll('[data-err-key="' + m[1] + '"]')[parseInt(m[2], 10)];
                } else {
                    el = form.querySelector('[data-err-key="' + key + '"]');
                    var block = form.querySelector('[data-err-for="' + key + '"]');
                    if (!el && block) { block.textContent = msg; return; }
                }
                if (el) {
                    el.classList.add('is-invalid');
                    el.parentElement.querySelector('.dp-err').textContent = msg;
                    if (!first) first = el;
                }
            });
            if (first) first.focus();
        }
        if (form) form.addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = document.getElementById('btnSaveAnggota');
            if (!btn || btn.disabled) return;
            clearErrors();
            btn.disabled = true;
            btn.querySelector('span').textContent = 'Menyimpan…';
            post('save', new FormData(form)).then(function (j) {
                if (j.ok) {
                    toast(j.message);
                    setDone(1, true, 'Tersimpan');
                    root.querySelector('[data-need-anggota]').hidden = false;
                    root.querySelector('[data-no-anggota]').hidden = true;
                    var f3 = document.querySelector('#bagian-3 .dp-frame');   // muat ulang pratinjau template bila terbuka
                    if (f3 && !f3.hidden) f3.querySelector('iframe').src = BASE + '/lembar?t=' + Date.now();
                } else {
                    toast(j.message || 'Data gagal disimpan.', true);
                    showErrors(j.errors);
                }
            }).catch(function () { toast('Koneksi bermasalah. Coba lagi.', true); })
                .then(function () { btn.disabled = false; btn.querySelector('span').textContent = 'Simpan'; });
        });

        // ---------------------------------------------------------------- unggah PDF (dengan progres)
        var SLOT_STEP = { suratdireksi: 2, lembar: 3, pengendalian: 4 };
        function fmtSize(b) { return b >= 1048576 ? (b / 1048576).toFixed(1).replace('.', ',') + ' MB' : Math.round(b / 1024) + ' KB'; }

        function applySlots(slots, complete) {
            Object.keys(slots || {}).forEach(function (slot) {
                var st = slots[slot], box = root.querySelector('.dp-upload[data-slot="' + slot + '"]');
                if (!box) return;
                var file = box.querySelector('[data-file]');
                file.classList.toggle('is-empty', !st.uploaded);
                file.querySelector('i').className = 'fas ' + (st.uploaded ? 'fa-file-pdf' : 'fa-cloud-upload-alt');
                file.querySelector('[data-file-title]').textContent = st.uploaded ? 'PDF sudah diunggah' : 'Belum ada file';
                file.querySelector('[data-file-meta]').textContent = st.uploaded ? fmtSize(st.size) + ' · ' + st.at : 'PDF, maks. ' + (MAX_KB / 1024) + ' MB';
                file.querySelector('[data-view]').hidden = !st.uploaded;
                var rm = file.querySelector('[data-remove]'); if (rm) rm.hidden = !st.uploaded;
                var dt = box.querySelector('[data-drop-text]');
                if (dt) dt.textContent = st.uploaded ? 'Ganti file: klik atau seret PDF ke sini' : 'Klik atau seret PDF bertanda tangan ke sini';
                setDone(SLOT_STEP[slot], st.uploaded, st.uploaded ? 'Sudah diunggah' : 'Belum diunggah');
            });
            setMerged(!!complete);
        }

        function upload(box, file) {
            var slot = box.dataset.slot;
            if (!file) return;
            if (!/\.pdf$/i.test(file.name)) { toast('File harus berformat PDF.', true); return; }
            if (file.size > MAX_KB * 1024) { toast('Ukuran file maksimal ' + (MAX_KB / 1024) + ' MB.', true); return; }
            var prog = box.querySelector('[data-progress]'), bar = box.querySelector('[data-bar]'), drop = box.querySelector('[data-drop]');
            var fd = withCsrf(new FormData());
            fd.append('slot', slot);
            fd.append('pdf', file);

            var xhr = new XMLHttpRequest();
            xhr.open('POST', BASE + '/upload');
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.upload.onprogress = function (e) { if (e.lengthComputable) bar.style.width = Math.round(e.loaded / e.total * 90) + '%'; };
            xhr.onload = function () {
                var j; try { j = takeCsrf(JSON.parse(xhr.responseText)); } catch (er) { j = { ok: false, message: 'Respons server tidak valid.' }; }
                bar.style.width = '100%';
                setTimeout(function () { prog.hidden = true; bar.style.width = '0'; }, 400);
                drop.style.pointerEvents = '';
                if (j.ok) { toast(j.message); applySlots(j.slots, j.complete); }
                else toast(j.message || 'Upload gagal.', true);
            };
            xhr.onerror = function () { prog.hidden = true; drop.style.pointerEvents = ''; toast('Koneksi bermasalah. Upload gagal.', true); };
            prog.hidden = false;
            bar.style.width = '5%';
            drop.style.pointerEvents = 'none';
            xhr.send(fd);
        }

        root.querySelectorAll('.dp-upload').forEach(function (box) {
            var input = box.querySelector('[data-input]'), drop = box.querySelector('[data-drop]'), rm = box.querySelector('[data-remove]');
            if (input) input.addEventListener('change', function () { upload(box, input.files[0]); input.value = ''; });
            if (drop) {
                ['dragenter', 'dragover'].forEach(function (ev) { drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('over'); }); });
                ['dragleave', 'drop'].forEach(function (ev) { drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('over'); }); });
                drop.addEventListener('drop', function (e) { upload(box, e.dataTransfer.files[0]); });
            }
            if (rm) rm.addEventListener('click', function () {
                if (!confirm('Hapus PDF yang sudah diunggah pada bagian ini?')) return;
                var fd = new FormData(); fd.append('slot', box.dataset.slot);
                post('hapus', fd).then(function (j) { j.ok ? (toast(j.message), applySlots(j.slots, j.complete)) : toast(j.message || 'Gagal menghapus.', true); });
            });
        });
    })();
</script>

<?= view('templates/komentar', ['komentarPage' => 'Dokumenpendukung']) ?>
