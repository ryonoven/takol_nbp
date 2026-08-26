<?php
$peringkat13 = isset($nilai13) && is_array($nilai13) ? ($nilai13['penilaiankredit'] ?? null) : null;
$keterangan13 = isset($nilai13) && is_array($nilai13) ? ($nilai13['keterangan'] ?? '') : '';
$peringkat14 = isset($nilai14) && is_array($nilai14) ? ($nilai14['penilaiankredit'] ?? null) : null;
$peringkatLabels = ['1' => 'Sangat Rendah', '2' => 'Rendah', '3' => 'Sedang', '4' => 'Tinggi', '5' => 'Sangat Tinggi'];

function getBadgeClassKpmr($nilai)
{
    $map = ['1' => 'badge-info', '2' => 'badge-success', '3' => 'badge-warning', '4' => 'badge-danger', '5' => 'badge-dark'];
    return $map[$nilai] ?? 'badge-secondary';
}

$activePeriodeId = session()->get('active_periode');
$isManual13 = isset($nilai13['is_manual']) && $nilai13['is_manual'] == 1;
$kesimpulan13 = $nilai13 ?? null;
$canEdit = ($userInGroupAdmin ?? false) || ($userInGroupPE ?? false);
$canApprDir = ($userInGroupAdmin ?? false) || ($userInGroupDireksi ?? false) || ($userInGroupDireksi2 ?? false);
$nilaiLookup = $nilaiLookup ?? [];
$unreadCounts = $unreadCounts ?? [];

$factorStructure = [
    [
        'faktor_id' => 16,
        'title' => 'Pilar Pengawasan Direksi dan Dewan Komisaris',
        'type' => 'category',
        'children' => [
            ['faktor_id' => 17, 'title' => 'Apakah Dewan Komisaris telah melakukan persetujuan terhadap kebijakan manajemen risiko kredit yang disusun oleh Direksi dan melakukan evaluasi secara berkala?'],
            ['faktor_id' => 18, 'title' => 'Apakah Dewan Komisaris telah melakukan evaluasi terhadap pertanggungjawaban Direksi atas pelaksanaan kebijakan manajemen risiko kredit secara berkala dan memastikan tindak lanjut hasil evaluasi dimaksud?'],
            ['faktor_id' => 19, 'title' => 'Apakah Direksi telah menyusun kebijakan manajemen risiko kredit, melaksanakan secara konsisten, dan melakukan pengkinian secara berkala?'],
            ['faktor_id' => 20, 'title' => 'Apakah Direksi telah memiliki kemampuan untuk mengambil tindakan yang diperlukan dalam rangka mitigasi risiko kredit, dan melakukan komunikasi kebijakan manajemen risiko kredit terhadap seluruh jenjang organisasi BPR?'],
            ['faktor_id' => 21, 'title' => 'Apakah BPR telah memiliki kecukupan organisasi yang menangani fungsi kredit dan fungsi manajemen risiko kredit?'],
            ['faktor_id' => 22, 'title' => 'Apakah Direksi telah menerapkan kebijakan pengelolaan SDM dalam rangka penerapan Manajemen Risiko kredit?'],
        ],
    ],
    [
        'faktor_id' => 23,
        'title' => 'Pilar Kecukupan Kebijakan, Prosedur, dan Limit',
        'type' => 'category',
        'children' => [
            ['faktor_id' => 24, 'title' => 'Apakah BPR telah memiliki kebijakan manajemen risiko kredit yang memadai dan disusun dengan mempertimbangkan visi, misi, skala usaha dan kompleksitas bisnis, serta kecukupan SDM?'],
            ['faktor_id' => 25, 'title' => 'Apakah BPR: ● Memiliki prosedur manajemen risiko kredit dan penetapan limit risiko kredit yang ditetapkan oleh Direksi; ● Melaksanakan prosedur manajemen risiko kredit dan penetapan limit risiko kredit secara konsisten untuk seluruh aktivitas; dan ● Melakukan evaluasi dan pengkinian terhadap prosedur manajemen risiko kredit dan penetapan limit risiko kredit secara berkala?'],
            ['faktor_id' => 26, 'title' => 'Apakah BPR telah memiliki kebijakan dan prosedur penerbitan produk dan/atau pelaksanaan aktivitas baru yang mencakup identifikasi dan mitigasi risiko kredit sesuai dengan ketentuan?'],
        ],
    ],
    [
        'faktor_id' => 27,
        'title' => 'Kecukupan Proses dan Sistem Manajemen Informasi',
        'type' => 'category',
        'children' => [
            ['faktor_id' => 28, 'title' => 'Apakah BPR telah melaksanakan proses manajemen risiko kredit yang melekat pada kegiatan usaha BPR?'],
            ['faktor_id' => 29, 'title' => 'Apakah BPR telah memiliki sistem informasi manajemen risiko yang mendukung Direksi dalam pengambilan keputusan terkait risiko kredit serta telah dilaporkan kepada Direksi secara berkala?'],
        ],
    ],
    [
        'faktor_id' => 30,
        'title' => 'Sistem Pengendalian Internal yang Menyeluruh',
        'type' => 'category',
        'children' => [
            ['faktor_id' => 31, 'title' => 'Apakah SKAI atau PEAI telah melaksanakan audit secara berkala terhadap penerapan manajemen risiko kredit, menyampaikan laporan hasil audit intern, dan memastikan tindaklanjut atas temuan pemeriksaan?'],
            ['faktor_id' => 32, 'title' => 'Apakah sistem pengendalian intern terhadap risiko kredit telah dilaksanakan oleh seluruh jenjang organisasi BPR?'],
        ],
    ],
];
?>

<!-- Toast container -->
<div id="toast-wrapper" aria-live="polite"></div>

<!-- Flash info -->
<div class="alert alert-info my-2">
    <?php if (isset($bprData) && isset($periodeDetail)): ?>
        <strong><?= esc($bprData['namabpr'] ?? 'Nama BPR') ?></strong>
        — Periode Pelaporan Semester <?= esc($periodeDetail['semester']) ?> Tahun <?= esc($periodeDetail['tahun']) ?>
    <?php elseif (isset($periodeDetail)): ?>
        <strong>Periode:</strong> Semester <?= esc($periodeDetail['semester']) ?> Tahun <?= esc($periodeDetail['tahun']) ?>
    <?php else: ?>
        <strong>Periode belum ditentukan</strong>
    <?php endif; ?>
</div>
<div id="sync-bar" style="display:none;background:#fff;border:0.5px solid #B5D4F4;border-left:3px solid #185FA5;border-radius:10px;padding:12px 16px;margin-bottom:10px;display:none;align-items:center;justify-content:space-between;gap:12px;">
    <div style="display:flex;align-items:center;gap:10px;flex:1;min-width:0;">
        <div style="width:32px;height:32px;background:#E6F1FB;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fas fa-history" style="font-size:14px;color:#185FA5;"></i>
        </div>
        <div style="min-width:0;">
            <div id="sync-bar-title" style="font-size:13px;font-weight:600;color:#0C447C;"></div>
            <div id="sync-bar-text" style="font-size:11px;color:#378ADD;margin-top:1px;"></div>
        </div>
    </div>
    <button type="button" id="btn-sync-trigger"
            style="flex-shrink:0;padding:7px 14px;border-radius:8px;font-size:12px;font-weight:600;border:none;background:linear-gradient(135deg,#141863 0%,#1f2aa8 100%);color:#fff;cursor:pointer;display:flex;align-items:center;gap:6px;white-space:nowrap;">
        <i class="fas fa-sync-alt" style="font-size:11px;"></i> Sinkronisasi
    </button>
</div>

<!-- <div id="sync-nodata-bar" style="display:none;"
     class="alert alert-info d-flex justify-content-between align-items-center py-2 px-3 mb-2">
    <div style="font-size:.9em;">
        <i class="fas fa-info-circle"></i>
        <span id="sync-nodata-text"></span>
    </div>
</div> -->

<div class="container-fluid">

    <?php if (session()->get('message')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <strong><?= session()->getFlashdata('message') ?></strong>
        </div>
    <?php endif; ?>

    <div class="card card-body">
        <div class="table-vertical">

            <!-- Nav -->
            <div class="button-bar">
                <a href="Risikokredit" class="kpmr-btn icon-link icon-link-hover">🡸 Laporan Risiko Kredit Inheren</a>
            </div>

            <!-- Title -->
            <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
                <div class="text-center flex-grow-1">
                    <h4 class="mb-0">
                        <i class="fas fa-info-circle text-primary"></i>
                        <strong>Tingkat Risiko Kredit KPMR</strong>
                    </h4>
                    <small class="text-muted">
                        <i class="fas fa-file-alt"></i>
                        Laporan Profil Risiko Semester <?= esc($periodeDetail['semester'] ?? '') ?> Tahun
                        <?= esc($periodeDetail['tahun'] ?? '') ?>
                    </small>
                </div>
            </div>

            <!-- Peringkat 13 -->
            <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
                <div class="text-center flex-grow-1">
                    <div class="mt-2">
                        <span class="badge badge-lg <?= getBadgeClassKpmr($peringkat13) ?>"
                            style="font-size:1.1em;padding:.5em 1em;" id="badge-peringkat13">
                            <i class="fas fa-check-circle"></i>
                            Peringkat penilaian: <?= esc($peringkat13 ?? 'N/A') ?>
                            [<?= $peringkatLabels[$peringkat13] ?? 'N/A' ?>]
                        </span>
                        <?php if ($canEdit): ?>
                            <?php if (!$isManual13): ?>
                                <button type="button" class="btn btn-sm btn-outline-primary ml-2" data-bs-toggle="collapse"
                                    data-bs-target="#editPeringkat13">
                                    <i class="fa fa-caret-down"></i>
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-sm btn-warning ml-2" id="btnResetPeringkat13"
                                    title="Kembalikan ke perhitungan otomatis">
                                    <i class="fa fa-undo"></i>
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Edit peringkat 13 collapse -->
            <?php if ($canEdit): ?>
                <div id="editPeringkat13" class="collapse">
                    <div class="p-3 border-top">
                        <select id="peringkat13_manual" class="form-control w-50 mx-auto">
                            <option value="">-- Pilih Peringkat --</option>
                            <?php foreach ($peringkatLabels as $v => $l): ?>
                                <option value="<?= $v ?>" <?= $peringkat13 == $v ? 'selected' : '' ?>>
                                    <?= $v ?> - <?= $l ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="text-center">
                            <button type="button" class="btn btn-warning btn-sm mt-2" id="btnSimpanPeringkat13">
                                <i class="fas fa-edit"></i> Update
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Peringkat 14 -->
            <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
                <div class="text-center flex-grow-1">
                    <div class="mt-2">
                        <span class="badge badge-lg <?= getBadgeClassKpmr($peringkat14) ?>"
                            style="font-size:1.1em;padding:.5em 1em;">
                            <i class="fas fa-check-circle"></i>
                            Peringkat penilaian pada periode sebelumnya: <?= esc($peringkat14 ?? 'N/A') ?>
                            [<?= $peringkatLabels[$peringkat14] ?? 'N/A' ?>]
                        </span>
                        <?php if ($canEdit): ?>
                            <button type="button" class="btn btn-sm btn-outline-primary ml-2" data-bs-toggle="collapse"
                                data-bs-target="#editNilai14">
                                <i class="fa fa-caret-down"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Edit nilai 14 -->
            <div id="editNilai14" class="collapse">
                <div class="p-3 border-top">
                    <form action="<?= base_url('Risikokreditkpmr/simpanNilai14') ?>" method="post">
                        <?= csrf_field() ?>
                        <select name="penilaiankredit" class="form-control w-50 mx-auto" required>
                            <?php if (!empty($nilai14)): ?>
                                <option value="" disabled selected>-- Nilai Saat Ini:
                                    <?= esc($nilai14['penilaiankredit']) ?> --
                                </option>
                            <?php else: ?>
                                <option value="">-- Pilih Tingkat Risiko --</option>
                            <?php endif; ?>
                            <option value="1">Sangat Rendah (1)</option>
                            <option value="2">Rendah (2)</option>
                            <option value="3">Cukup (3)</option>
                            <option value="4">Tinggi (4)</option>
                            <option value="5">Sangat Tinggi (5)</option>
                        </select>
                        <div class="text-center">
                            <button type="submit"
                                class="btn <?= !empty($nilai14) ? 'btn-warning' : 'btn-primary' ?> btn-sm mt-2">
                                <i class="fas <?= !empty($nilai14) ? 'fa-edit' : 'fa-save' ?>"></i>
                                <?= !empty($nilai14) ? 'Update' : 'Simpan' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Approval badge -->
            <div style="text-align:right;">
                <span id="approval-badge-main"
                    class="badge <?= ($allApproved ?? false) ? 'badge-success' : 'badge-secondary' ?>"
                    style="font-size:14px;">
                    <?php if ($allApproved ?? false): ?>
                        Disetujui oleh <strong>Direktur Utama</strong><br>
                        <?= !empty($nilai13['approved_at']) ? date('d/m/Y H:i:s', strtotime($nilai13['approved_at'])) : '-' ?>
                    <?php else: ?>
                        Belum Disetujui Seluruhnya<br>Oleh Direktur Utama
                    <?php endif; ?>
                </span>
            </div>

            <!-- KERTAS KERJA KPMR -->
            <div class="card-body">
                <div class="mb-2"><strong>A. Kertas Kerja Kredit KPMR</strong></div>

                <?php foreach ($factorStructure as $idx => $factor):
                    $fid = $factor['faktor_id'];
                    $nilai = $nilaiLookup[$fid] ?? null;
                    $hasData = !empty($nilai['penilaiankredit']);
                    $unread = (int) ($unreadCounts[$fid] ?? 0);
                    $accOk = !empty($nilai['accdir2']) && $nilai['accdir2'] == 1;
                    ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap"
                            style="gap:6px;">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <?= $idx + 1 ?>. <?= esc($factor['title']) ?>
                            </h6>
                            <div class="parent-btns">

                                <?php if ($hasData): ?>
                                    <span class="badge badge-info">
                                        <i class="fas fa-check"></i> Peringkat: <?= esc($nilai['penilaiankredit']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Belum dinilai</span>
                                <?php endif; ?>

                                <?php if ($canEdit): ?>
                                    <button type="button" class="btn btn-sm btn-warning ml-2 btn-edit-keterangan"
                                        data-id="<?= $fid ?>" data-title="<?= esc($factor['title']) ?>"
                                        data-keterangan="<?= esc($nilai['keterangan'] ?? '') ?>">
                                        <i class="fa fa-edit"></i>
                                    </button>
                                <?php endif; ?>

                                <button type="button" class="btn btn-sm btn-primary ml-2 position-relative komentar-button"
                                    data-faktor-id="<?= $fid ?>">
                                    <i class="fas fa-comment"></i>
                                    <span class="badge badge-danger notification-badge" id="notification-badge-<?= $fid ?>"
                                        style="<?= $unread > 0 ? '' : 'display:none;' ?>">
                                        <?= $unread > 0 ? $unread : '' ?>
                                    </span>
                                </button>

                                <?php if ($canApprDir && $hasData): ?>
                                    <form action="<?= base_url('Risikokreditkpmr/' . ($accOk ? 'unapprovedir2' : 'accdir2')) ?>"
                                        method="POST" style="display:inline-block;" class="ml-2 form-approval">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="faktor1id" value="<?= $fid ?>">
                                        <input type="hidden" name="kodebpr" value="<?= esc($kodebpr ?? '') ?>">
                                        <input type="hidden" name="periode_id" value="<?= esc($activePeriodeId ?? '') ?>">
                                        <button type="submit" class="btn btn-sm <?= $accOk ? 'btn-success' : 'btn-dark' ?>">
                                            <i class="fas fa-<?= $accOk ? 'check' : 'times' ?>-circle"></i>
                                        </button>
                                    </form>
                                <?php elseif ($canApprDir): ?>
                                    <span class="text-muted ml-2">-</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Children -->
                        <?php if (!empty($factor['children'])): ?>
                            <div class="card-body">
                                <?php foreach ($factor['children'] as $cidx => $child):
                                    $cfid = $child['faktor_id'];
                                    $cnilai = $nilaiLookup[$cfid] ?? null;
                                    $cHasData = !empty($cnilai['penilaiankredit']);
                                    $cUnread = (int) ($unreadCounts[$cfid] ?? 0);
                                    $cAccOk = !empty($cnilai['accdir2']) && $cnilai['accdir2'] == 1;
                                    ?>
                                    <div class="border-left border-success pl-4 mb-3">
                                        <div class="child-row">
                                            <div class="child-title">
                                                <h6 class="font-weight-bold">
                                                    <?= $cidx + 1 ?>. <?= nl2br(esc($child['title'])) ?>
                                                </h6>
                                            </div>
                                            <div class="child-btns">

                                                <?php if ($cHasData): ?>
                                                    <span class="badge badge-success">
                                                        <i class="fas fa-check"></i> Peringkat: <?= esc($cnilai['penilaiankredit']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Belum dinilai</span>
                                                <?php endif; ?>

                                                <?php if ($canEdit): ?>
                                                    <?php if ($cHasData): ?>
                                                        <button class="btn btn-sm btn-warning ml-2 btn-edit" data-id="<?= $cfid ?>"
                                                            data-title="<?= esc($child['title']) ?>"
                                                            data-penilaiankredit="<?= esc($cnilai['penilaiankredit']) ?>"
                                                            data-keterangan="<?= esc($cnilai['keterangan'] ?? '') ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-success ml-2 btn-add" data-id="<?= $cfid ?>"
                                                            data-title="<?= esc($child['title']) ?>">
                                                            <i class="fas fa-plus"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                <?php endif; ?>

                                                <button type="button"
                                                    class="btn btn-sm btn-primary ml-2 position-relative komentar-button"
                                                    data-faktor-id="<?= $cfid ?>">
                                                    <i class="fas fa-comment"></i>
                                                    <span class="badge badge-danger notification-badge"
                                                        id="notification-badge-<?= $cfid ?>"
                                                        style="<?= $cUnread > 0 ? '' : 'display:none;' ?>">
                                                        <?= $cUnread > 0 ? $cUnread : '' ?>
                                                    </span>
                                                </button>

                                                <?php if ($canApprDir && $cHasData): ?>
                                                    <form
                                                        action="<?= base_url('Risikokreditkpmr/' . ($cAccOk ? 'unapprovedir2' : 'accdir2')) ?>"
                                                        method="POST" style="display:inline-block;" class="ml-2 form-approval">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="faktor1id" value="<?= $cfid ?>">
                                                        <input type="hidden" name="kodebpr" value="<?= esc($kodebpr ?? '') ?>">
                                                        <input type="hidden" name="periode_id"
                                                            value="<?= esc($activePeriodeId ?? '') ?>">
                                                        <button type="submit"
                                                            class="btn btn-sm <?= $cAccOk ? 'btn-success' : 'btn-dark' ?>">
                                                            <i class="fas fa-<?= $cAccOk ? 'check' : 'times' ?>-circle"></i>
                                                        </button>
                                                    </form>
                                                <?php elseif ($canApprDir): ?>
                                                    <span class="text-muted ml-2">-</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Kesimpulan -->
            <div class="card mt-3">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-clipboard-list"></i> Kesimpulan Penilaian Risiko Kredit KPMR</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($kesimpulan13) && !empty($kesimpulan13['keterangan'])): ?>
                        <div class="alert border">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="alert-heading"><i class="fas fa-check-circle"></i> Kesimpulan Tersimpan</h6>
                                    <p class="mb-0"><?= nl2br(esc($kesimpulan13['keterangan'])) ?></p>
                                </div>
                                <?php if ($canEdit): ?>
                                    <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="collapse"
                                        data-bs-target="#editKesimpulan13">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($canEdit): ?>
                            <div class="collapse" id="editKesimpulan13">
                                <hr>
                                <form action="<?= base_url('Risikokreditkpmr/simpanKesimpulan13') ?>" method="post">
                                    <?= csrf_field() ?>
                                    <div class="form-group">
                                        <label><strong>Edit Keterangan Kesimpulan:</strong></label>
                                        <textarea class="form-control" name="keterangan" rows="5"
                                            required><?= esc($kesimpulan13['keterangan']) ?></textarea>
                                    </div>
                                    <div class="d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-secondary" data-bs-toggle="collapse"
                                            data-bs-target="#editKesimpulan13">
                                            <i class="fas fa-times"></i> Batal
                                        </button>
                                        <button type="submit" class="btn btn-primary ml-3">
                                            <i class="fas fa-save"></i> Update Kesimpulan
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if ($canEdit): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Kesimpulan belum diisi.</strong> Silakan isi keterangan kesimpulan di bawah ini.
                            </div>
                            <form action="<?= base_url('Risikokreditkpmr/simpanKesimpulan13') ?>" method="post">
                                <?= csrf_field() ?>
                                <div class="form-group">
                                    <label><strong>Keterangan Kesimpulan:</strong></label>
                                    <textarea class="form-control" name="keterangan" rows="5"
                                        placeholder="Masukkan kesimpulan penilaian risiko kredit KPMR..." required></textarea>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save"></i> Simpan Kesimpulan
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Kesimpulan belum diisi oleh Tim Penilaian Risiko.
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Approval Direktur Utama -->
            <?php if (($userInGroupAdmin ?? false) || ($userInGroupDireksi ?? false)): ?>
                <div class="d-flex justify-content-center mt-3">
                    <div class="col-md-3 mx-auto">
                        <div class="card shadow-sm approvaldir-card" style="height:120px;">
                            <div class="card-body approvaldir-card-body">
                                <div class="approval-badge-container">
                                    <span class="badge approval-badge">Approval Direktur Utama</span>
                                </div>
                                <div class="approval-buttons-container">
                                    <a href="<?= base_url('Risikokreditkpmr/approveSemua') ?>"
                                        class="btn approval-btn approval-btn-approve <?= !($canApprove ?? false) ? 'disabled-btn' : '' ?>"
                                        id="btnApproveSemua">Approve</a>
                                    <a href="<?= base_url('Risikokreditkpmr/unapproveSemua') ?>"
                                        class="btn approval-btn approval-btn-reject <?= !($allApproved ?? false) ? 'disabled-btn' : '' ?>"
                                        id="btnUnapproveSemua">Tolak</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>


<!-- Ubah Nilai -->
<div class="modal fade" id="modalUbah">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ubah Data Penilaian</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="formUbah" data-action="<?= base_url('Risikokreditkpmr/ubah') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="faktor1id" id="edit-faktor-id">
                    <div class="mb-3">
                        <label><strong>Parameter penilaian risiko:</strong></label>
                        <textarea class="form-control" id="edit-parameterpenilaian" style="height:100px;"
                            readonly></textarea>
                    </div>
                    <label><strong>Pilih Tingkat Risiko:</strong></label>
                    <div class="alert alert-info mb-2 d-none" id="edit-catatan-info" style="font-size:.9em;">
                        <i class="fas fa-info-circle"></i> <span id="edit-catatan-text"></span>
                    </div>
                    <div class="list-group mb-3" id="editRatingList"></div>
                    <p>Nilai yang dipilih: <strong id="editSelectedValue" class="text-primary"></strong></p>
                    <input type="hidden" name="penilaiankredit" id="edit-penilaiankredit">
                    <input type="hidden" name="penjelasanpenilaian" id="edit-penjelasanpenilaian">
                    <div class="mb-3">
                        <label><strong>Keterangan:</strong></label>
                        <textarea class="form-control" name="keterangan" id="edit-keterangan" style="height:100px;"
                            placeholder="Masukkan keterangan..." required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Ubah Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Ubah Keterangan Kesimpulan -->
<div class="modal fade" id="modalUbahkesimpulan">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ubah Keterangan Kesimpulan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="formUbahKesimpulan" data-action="<?= base_url('Risikokreditkpmr/ubahkesimpulan') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="faktor1id" id="edit-faktor-id-kesimpulan">
                    <div class="mb-3">
                        <label><strong>Sub Kategori:</strong></label>
                        <textarea class="form-control" id="edit-parameterpenilaian-kesimpulan" style="height:100px;"
                            readonly></textarea>
                    </div>
                    <div class="mb-3">
                        <label><strong>Keterangan:</strong></label>
                        <textarea class="form-control" name="keterangan" id="edit-keterangan-kesimpulan"
                            style="height:100px;" placeholder="Masukkan keterangan..." required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Ubah
                            Keterangan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Tambah Nilai -->
<div class="modal fade" id="modaltambahNilai">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Nilai Faktor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="formTambahNilai" data-action="<?= base_url('Risikokreditkpmr/tambahNilai') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="faktor_id" id="add-faktor-id">
                    <input type="hidden" name="fullname" value="<?= esc($fullname ?? '') ?>">
                    <div class="mb-3">
                        <label><strong>Parameter penilaian risiko:</strong></label>
                        <textarea class="form-control" id="add-parameterpenilaian" style="height:100px;"
                            readonly></textarea>
                    </div>
                    <label><strong>Pilih Tingkat Risiko:</strong></label>
                    <div class="alert alert-info mb-2 d-none" id="add-catatan-info" style="font-size:.9em;">
                        <i class="fas fa-info-circle"></i> <span id="add-catatan-text"></span>
                    </div>
                    <div class="list-group mb-3" id="addRatingList"></div>
                    <p>Nilai yang dipilih: <strong id="addSelectedValue" class="text-primary">Belum ada</strong></p>
                    <input type="hidden" name="penilaiankredit" id="add-penilaiankredit">
                    <input type="hidden" name="penjelasanpenilaian" id="add-penjelasanpenilaian">
                    <div class="mb-3">
                        <label><strong>Keterangan:</strong></label>
                        <textarea class="form-control" name="keterangan" id="add-keterangan" style="height:100px;"
                            placeholder="Masukkan keterangan..." required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" name="tambahNilai" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Komentar -->
<div class="modal fade" id="modaltambahKomentar">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Komentar Direksi dan Dewan Komisaris</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="formKomentar" data-action="<?= base_url('Risikokreditkpmr/tambahKomentar') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="faktor_id" id="comment-faktor-id">
                    <input type="hidden" name="kodebpr" value="<?= esc($kodebpr ?? '') ?>">
                    <div class="form-group">
                        <label>Komentar Saat Ini:</label>
                        <ul id="komentarLamaList" style="list-style-type:none;padding-left:0;">
                            <li>Memuat komentar...</li>
                        </ul>
                    </div>
                    <?php if (
                        ($userInGroupAdmin ?? false) || ($userInGroupDekom ?? false) ||
                        ($userInGroupDireksi ?? false) || ($userInGroupPE ?? false) ||
                        ($userInGroupDekom2 ?? false) || ($userInGroupDireksi2 ?? false)
                    ): ?>
                        <input type="hidden" name="fullname" value="<?= esc($fullname ?? '') ?>">
                        <div class="form-group">
                            <label for="komentar">Tambahkan Komentar Baru:</label>
                            <textarea class="form-control" name="komentar" id="komentar" style="height:100px;"></textarea>
                        </div>
                    <?php endif; ?>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" name="tambahKomentar" class="btn btn-primary">Simpan Komentar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalSyncConfirm">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
        <div class="modal-content" style="border:none;border-radius:16px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.2);">

            <!-- Header -->
            <div style="background:linear-gradient(135deg,#141863 0%,#1f2aa8 100%);padding:20px 24px;display:flex;align-items:center;justify-content:space-between;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:38px;height:38px;background:rgba(255,255,255,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-sync-alt" style="font-size:16px;color:#fff;"></i>
                    </div>
                    <div>
                        <div style="color:#fff;font-size:15px;font-weight:600;line-height:1.3;">Sinkronisasi Periode</div>
                        <div style="color:rgba(255,255,255,0.6);font-size:12px;">Salin data dari periode sebelumnya</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                        style="width:28px;height:28px;background:rgba(255,255,255,0.15);border:none;border-radius:7px;cursor:pointer;color:#fff;font-size:18px;line-height:1;display:flex;align-items:center;justify-content:center;padding:0;opacity:1;">
                    &times;
                </button>
            </div>

            <!-- Body -->
            <div style="padding:20px 24px;background:#fff;">

                <!-- Source card -->
                <div style="background:#EAF3DE;border:0.5px solid #97C459;border-radius:10px;padding:14px 16px;margin-bottom:16px;display:flex;align-items:flex-start;gap:12px;">
                    <div style="width:34px;height:34px;background:#639922;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">
                        <i class="fas fa-calendar-alt" style="font-size:15px;color:#fff;"></i>
                    </div>
                    <div id="sync-modal-info" style="flex:1;min-width:0;"></div>
                </div>

                <!-- Grid yang akan disalin -->
                <div style="margin-bottom:16px;">
                    <div style="font-size:11px;font-weight:600;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">Yang akan disalin</div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                        <div style="background:#f8f9fa;border-radius:8px;padding:9px 12px;display:flex;align-items:center;gap:8px;">
                            <i class="fas fa-star-half-alt" style="font-size:13px;color:#6c757d;"></i>
                            <span style="font-size:12px;color:#495057;">Penilaian kredit</span>
                        </div>
                        <div style="background:#f8f9fa;border-radius:8px;padding:9px 12px;display:flex;align-items:center;gap:8px;">
                            <i class="fas fa-align-left" style="font-size:13px;color:#6c757d;"></i>
                            <span style="font-size:12px;color:#495057;">Keterangan</span>
                        </div>
                        <div style="background:#f8f9fa;border-radius:8px;padding:9px 12px;display:flex;align-items:center;gap:8px;">
                            <i class="fas fa-file-alt" style="font-size:13px;color:#6c757d;"></i>
                            <span style="font-size:12px;color:#495057;">Penjelasan penilaian</span>
                        </div>
                        <div style="background:#f8f9fa;border-radius:8px;padding:9px 12px;display:flex;align-items:center;gap:8px;">
                            <i class="fas fa-calculator" style="font-size:13px;color:#6c757d;"></i>
                            <span style="font-size:12px;color:#495057;">Rata-rata ulang</span>
                        </div>
                    </div>
                </div>

                <!-- Warning (tampil dinamis jika ada data existing) -->
                <div id="sync-overwrite-warning"
                     style="display:none;background:#FAEEDA;border:0.5px solid #EF9F27;border-radius:10px;padding:12px 14px;margin-bottom:14px;align-items:flex-start;gap:10px;">
                    <i class="fas fa-exclamation-triangle" style="font-size:14px;color:#854F0B;flex-shrink:0;margin-top:1px;"></i>
                    <div style="font-size:12px;color:#633806;line-height:1.5;">
                        Periode aktif sudah memiliki sebagian data. Aktifkan <em>"Timpa data"</em> untuk mengganti semua nilai yang sudah ada.
                    </div>
                </div>

                <!-- Toggle timpa data -->
                <label id="sync-toggle-label"
                       style="display:flex;align-items:center;gap:12px;cursor:pointer;padding:11px 14px;background:#f8f9fa;border-radius:10px;border:0.5px solid #dee2e6;margin-bottom:0;user-select:none;">
                    <div style="position:relative;width:40px;height:22px;flex-shrink:0;">
                        <input type="checkbox" id="sync-force-overwrite"
                               style="position:absolute;opacity:0;width:0;height:0;">
                        <div id="sync-toggle-track"
                             style="width:40px;height:22px;background:#ced4da;border-radius:11px;transition:background .2s ease;"></div>
                        <div id="sync-toggle-thumb"
                             style="position:absolute;top:3px;left:3px;width:16px;height:16px;background:#fff;border-radius:50%;transition:left .2s ease;pointer-events:none;box-shadow:0 1px 3px rgba(0,0,0,.2);"></div>
                    </div>
                    <div>
                        <div style="font-size:13px;font-weight:600;color:#343a40;">Timpa data yang sudah ada</div>
                        <div style="font-size:11px;color:#6c757d;margin-top:2px;">Semua form diganti dengan data periode sebelumnya</div>
                    </div>
                </label>

                <!-- Progress -->
                <div id="sync-progress"
                     style="display:none;margin-top:14px;background:#e9f3ff;border-radius:10px;padding:12px 14px;align-items:center;gap:10px;">
                    <i class="fas fa-spinner fa-spin" style="font-size:16px;color:#1f2aa8;"></i>
                    <span style="font-size:13px;color:#1f2aa8;font-weight:500;">Sedang menyinkronisasi data...</span>
                </div>

            </div>

            <!-- Footer -->
            <div style="padding:14px 24px;border-top:1px solid #f0f0f0;background:#fff;display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                        style="padding:9px 18px;border-radius:8px;font-size:13px;font-weight:500;background:#fff;color:#495057;border:1px solid #ced4da;">
                    Batal
                </button>
                <button type="button" id="btn-sync-execute"
                        style="padding:9px 22px;border-radius:8px;font-size:13px;font-weight:600;border:none;background:linear-gradient(135deg,#141863 0%,#1f2aa8 100%);color:#fff;cursor:pointer;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-sync-alt"></i> Mulai Sinkronisasi
                </button>
            </div>

        </div>
    </div>
</div>

<!-- JAVASCRIPT -->
<script>
    (function () {
        'use strict';

        /* CONSTANTS */
        const GLOBAL_KODEBPR = '<?= esc($kodebpr ?? '') ?>';
        const GLOBAL_PERIODE_ID = '<?= esc($activePeriodeId ?? '') ?>';
        const BASE_URL = '<?= base_url() ?>';
        const PENILAIAN_CONFIG = <?= json_encode($penilaianConfig ?? []) ?>;
        const CSRF_TOKEN_NAME = '<?= csrf_token() ?>';
        let CSRF_HASH = '<?= csrf_hash() ?>';

        /* UTILITIES */
        function debounce(fn, ms) {
            let t; return function (...a) { clearTimeout(t); t = setTimeout(() => fn.apply(this, a), ms); };
        }
        const $id = id => document.getElementById(id);
        const escH = s => String(s ?? '').replace(/[&<>"']/g,
            m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m]));

        function refreshCsrf() {
            const m = document.querySelector('meta[name="csrf-token"]') ||
                document.querySelector('meta[name="X-CSRF-TOKEN"]');
            if (m) CSRF_HASH = m.getAttribute('content');
        }

        /* INFLIGHT ABORT */
        const _ctrl = {};
        function abortPrev(key) {
            if (_ctrl[key]) _ctrl[key].abort();
            _ctrl[key] = new AbortController();
            return _ctrl[key].signal;
        }

        /* FETCH HELPER */
        function postJSON(url, data, abortKey) {
            data[CSRF_TOKEN_NAME] = CSRF_HASH;
            const fullUrl = url.startsWith('http') ? url : BASE_URL + url.replace(/^\//, '');
            const opts = {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: new URLSearchParams(data),
            };
            if (abortKey) opts.signal = abortPrev(abortKey);
            return fetch(fullUrl, opts).then(r => {
                refreshCsrf();
                if (!r.ok) return r.text().then(t => { throw new Error('HTTP ' + r.status + ': ' + t.substring(0, 200)); });
                return r.json();
            });
        }

        /* TOAST */
        function showToast(title, message, type) {
            const colors = { success: '#28a745', error: '#dc3545', warning: '#e0a800', info: '#17a2b8' };
            const icons = { success: 'check-circle', error: 'exclamation-circle', warning: 'exclamation-triangle', info: 'info-circle' };
            const wrap = $id('toast-wrapper');
            const t = document.createElement('div');
            t.className = 'toast-notification';
            t.style.background = colors[type] || colors.info;
            t.innerHTML = `<i class="fas fa-${icons[type] || 'info-circle'}"></i><div><strong>${escH(title)}</strong><br><span>${escH(message)}</span></div>`;
            wrap.appendChild(t);
            requestAnimationFrame(() => t.classList.add('show'));
            setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 300); }, 3500);
        }

        /* MODAL */
        const _modalInst = {};
        function modalShow(id) {
            const el = $id(id); if (!el) return;
            if (window.bootstrap?.Modal) {
                if (!_modalInst[id]) {
                    _modalInst[id] = new bootstrap.Modal(el, { backdrop: true, keyboard: true });
                    el.addEventListener('hidden.bs.modal', () => delete _modalInst[id], { once: true });
                }
                _modalInst[id].show();
            } else if (window.$) window.$('#' + id).modal('show');
        }
        function modalHide(id) {
            const el = $id(id); if (!el) return;
            if (_modalInst[id]) _modalInst[id].hide();
            else if (window.bootstrap?.Modal) bootstrap.Modal.getInstance(el)?.hide();
            else if (window.$) window.$('#' + id).modal('hide');
        }
        document.querySelectorAll('[data-bs-dismiss="modal"]').forEach(btn =>
            btn.addEventListener('click', function () { const m = this.closest('.modal'); if (m) modalHide(m.id); })
        );

        /* COLLAPSE */
        document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(btn =>
            btn.addEventListener('click', function () {
                const t = document.querySelector(this.getAttribute('data-bs-target'));
                if (t) t.classList.toggle('show');
            })
        );

        /* RATING OPTIONS */
        function generateRatingOptions(faktorId, containerId) {
            const cfg = PENILAIAN_CONFIG[faktorId] || PENILAIAN_CONFIG['default'] || {};
            const container = $id(containerId); if (!container) return cfg;
            let html = '';
            for (let i = 1; i <= 5; i++) {
                const desc = (cfg.descriptions && cfg.descriptions[i]) || 'Tidak ada deskripsi';
                html += `<a href="#" class="list-group-item list-group-item-action py-2 px-3"
                data-value="${i}" data-description="${escH(desc)}">
                <h6 class="mb-1"><b>Nilai ${i}</b></h6>
                <p class="mb-0 small">${desc}</p></a>`;
            }
            container.innerHTML = html; return cfg;
        }
        function updateCatatanInfo(cfg, prefix) {
            let html = '';
            if (cfg.threshold) html += `<strong>Threshold:</strong> ${cfg.threshold}<br>`;
            if (cfg.catatan) html += `<strong>Catatan:</strong> ${cfg.catatan}`;
            const el = $id(prefix + '-catatan-info'), tx = $id(prefix + '-catatan-text');
            if (!el || !tx) return;
            if (html) { tx.innerHTML = html; el.classList.remove('d-none'); } else el.classList.add('d-none');
        }

        /* DELEGATED CLICK HANDLER */
        document.addEventListener('click', function (e) {
            const btnEdit = e.target.closest('.btn-edit');
            if (btnEdit) {
                $id('edit-faktor-id').value = btnEdit.dataset.id;
                $id('edit-parameterpenilaian').value = btnEdit.dataset.title;
                $id('edit-penilaiankredit').value = btnEdit.dataset.penilaiankredit;
                $id('edit-keterangan').value = btnEdit.dataset.keterangan || '';
                const cfg = generateRatingOptions(btnEdit.dataset.id, 'editRatingList');
                updateCatatanInfo(cfg, 'edit');
                document.querySelectorAll('#editRatingList .list-group-item').forEach(li => {
                    if (li.dataset.value == btnEdit.dataset.penilaiankredit) {
                        li.classList.add('active');
                        $id('editSelectedValue').textContent = 'Nilai ' + btnEdit.dataset.penilaiankredit;
                    }
                });
                modalShow('modalUbah'); return;
            }

            // btn-add
            const btnAdd = e.target.closest('.btn-add');
            if (btnAdd) {
                $id('add-faktor-id').value = btnAdd.dataset.id;
                $id('add-parameterpenilaian').value = btnAdd.dataset.title;
                $id('add-penilaiankredit').value = '';
                $id('add-keterangan').value = '';
                $id('addSelectedValue').textContent = 'Belum ada';
                const cfg = generateRatingOptions(btnAdd.dataset.id, 'addRatingList');
                updateCatatanInfo(cfg, 'add');
                modalShow('modaltambahNilai'); return;
            }

            // btn-edit-keterangan
            const btnEK = e.target.closest('.btn-edit-keterangan');
            if (btnEK) {
                $id('edit-faktor-id-kesimpulan').value = btnEK.dataset.id;
                $id('edit-parameterpenilaian-kesimpulan').value = btnEK.dataset.title;
                $id('edit-keterangan-kesimpulan').value = btnEK.dataset.keterangan || '';
                modalShow('modalUbahkesimpulan'); return;
            }

            // komentar
            const btnKom = e.target.closest('.komentar-button');
            if (btnKom) {
                const fid = btnKom.dataset.faktorId;
                $id('comment-faktor-id').value = fid;
                loadKomentar(fid); markRead(fid);
                modalShow('modaltambahKomentar'); return;
            }

            // Rating item selection
            const item = e.target.closest('#addRatingList .list-group-item, #editRatingList .list-group-item');
            if (item) {
                e.preventDefault();
                const list = item.closest('.list-group');
                list.querySelectorAll('.list-group-item').forEach(li => li.classList.remove('active'));
                item.classList.add('active');
                const isAdd = list.id === 'addRatingList', pfx = isAdd ? 'add' : 'edit';
                $id(pfx + '-penilaiankredit').value = item.dataset.value;
                $id(pfx + '-penjelasanpenilaian').value = item.dataset.description;
                $id(pfx + 'SelectedValue').textContent = 'Nilai ' + item.dataset.value;
            }
        });

        /* APPROVAL FORM */
        document.addEventListener('submit', function (e) {
            const form = e.target.closest('.form-approval'); if (!form) return;
            e.preventDefault();
            const btn = form.querySelector('button'), orig = btn.innerHTML;
            btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            const data = new URLSearchParams(new FormData(form));
            data.set(CSRF_TOKEN_NAME, CSRF_HASH);
            fetch(form.action, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' }, body: data })
                .then(() => {
                    const isApp = form.action.includes('accdir2') && !form.action.includes('unapprovedir2');
                    btn.className = `btn btn-sm ${isApp ? 'btn-success' : 'btn-dark'}`;
                    btn.innerHTML = `<i class="fas fa-${isApp ? 'check' : 'times'}-circle"></i>`;
                    form.action = form.action.replace(/(unapprovedir2|accdir2)/, isApp ? 'unapprovedir2' : 'accdir2');
                    btn.disabled = false;
                    showToast('Berhasil', 'Status approval diubah', 'success');
                })
                .catch(() => { btn.disabled = false; btn.innerHTML = orig; showToast('Error', 'Gagal', 'error'); });
        });

        /* AJAX FORM HANDLER */
        function makeAjaxFormHandler(formId, onSuccess, opts) {
            opts = opts || {};
            const form = $id(formId); if (!form) return;
            form.addEventListener('submit', function (e) { e.preventDefault(); }, true);
            form.addEventListener('submit', debounce(function (e) {
                e.preventDefault(); e.stopPropagation();
                const btn = this.querySelector('button[type="submit"]'); if (!btn) return;
                const orig = btn.innerHTML; btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
                const data = new URLSearchParams(new FormData(this));
                data.set(CSRF_TOKEN_NAME, CSRF_HASH);
                const url = this.dataset.action || (this.action !== window.location.href ? this.action : null);
                if (!url) { btn.disabled = false; btn.innerHTML = orig; showToast('Error', 'URL tidak ditemukan', 'error'); return; }
                fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' }, body: data })
                    .then(r => { refreshCsrf(); return r.json(); })
                    .then(r => {
                        btn.disabled = false; btn.innerHTML = orig;
                        if (r.success || r.status === 'success') {
                            showToast('Berhasil', opts.successMsg || 'Data berhasil disimpan', 'success');
                            if (opts.hideModal) modalHide(opts.hideModal);
                            if (onSuccess) onSuccess(r);
                            if (opts.reload) setTimeout(() => location.reload(), 800);
                        } else showToast('Error', r.message || 'Gagal menyimpan', 'error');
                    })
                    .catch(err => { btn.disabled = false; btn.innerHTML = orig; showToast('Error', 'Koneksi gagal: ' + err.message, 'error'); });
            }, 400));
        }

        makeAjaxFormHandler('formUbah',
            r => { if (r.peringkat13) updateBadgePeringkat13(r.peringkat13); },
            { hideModal: 'modalUbah', successMsg: 'Data berhasil diubah', reload: true }
        );
        makeAjaxFormHandler('formUbahKesimpulan', null,
            { hideModal: 'modalUbahkesimpulan', successMsg: 'Keterangan berhasil diubah', reload: true }
        );
        makeAjaxFormHandler('formTambahNilai',
            r => { if (r.peringkat13) updateBadgePeringkat13(r.peringkat13); $id('addSelectedValue').textContent = 'Belum ada'; },
            { hideModal: 'modaltambahNilai', successMsg: 'Nilai berhasil ditambahkan', reload: true }
        );
        makeAjaxFormHandler('formKomentar',
            () => {
                const fid = $id('comment-faktor-id')?.value;
                loadKomentar(fid); pollUnreadCounts();
                const ta = $id('komentar'); if (ta) ta.value = '';
            },
            { successMsg: 'Komentar berhasil ditambahkan' }
        );

        /* KOMENTAR */
        function loadKomentar(fid) {
            const list = $id('komentarLamaList');
            list.innerHTML = '<li>Memuat komentar...</li>';
            postJSON('Risikokreditkpmr/getKomentarByFaktorId/' + fid, { kodebpr: GLOBAL_KODEBPR, periode_id: GLOBAL_PERIODE_ID })
                .then(data => {
                    const rows = Array.isArray(data) ? data : (data.data || []);
                    list.innerHTML = rows.length
                        ? rows.map(k => `<li style="margin-bottom:6px;padding:6px;background:#f8f9fa;border-radius:4px;">
                        ${escH(k.komentar)}<br>
                        <small class="text-muted"><em>${escH(k.fullname)} &bull; ${k.created_at}</em></small>
                    </li>`).join('')
                        : '<li>Tidak ada komentar.</li>';
                })
                .catch(() => { list.innerHTML = '<li>Gagal memuat komentar.</li>'; });
        }

        function markRead(fid) {
            postJSON('Risikokreditkpmr/markUserCommentsAsRead', { faktor_id: fid, kodebpr: GLOBAL_KODEBPR, periode_id: GLOBAL_PERIODE_ID })
                .then(r => {
                    if (r.status === 'success') {
                        const b = $id('notification-badge-' + fid);
                        if (b) { b.style.display = 'none'; b.textContent = ''; }
                    }
                }).catch(() => { });
        }

        /* BADGE UPDATE */
        const _pLabels = { 1: 'Sangat Rendah', 2: 'Rendah', 3: 'Sedang', 4: 'Tinggi', 5: 'Sangat Tinggi' };
        const _bClass = { 1: 'badge-info', 2: 'badge-success', 3: 'badge-warning', 4: 'badge-danger', 5: 'badge-dark' };

        function updateBadgePeringkat13(p) {
            const badge = $id('badge-peringkat13'); if (!badge) return;
            badge.className = `badge badge-lg ${_bClass[p] || 'badge-secondary'}`;
            badge.style.cssText = 'font-size:1.1em;padding:.5em 1em;';
            badge.innerHTML = `<i class="fas fa-check-circle"></i> Peringkat penilaian: ${p} [${_pLabels[p] || 'N/A'}]`;
        }

        function updateMainApprovalBadge(approved, dt) {
            const badge = $id('approval-badge-main'); if (!badge) return;
            badge.className = 'badge ' + (approved ? 'badge-success' : 'badge-secondary');
            badge.style.fontSize = '14px';
            badge.innerHTML = approved
                ? `Disetujui oleh <strong>Direktur Utama</strong><br>${dt || '-'}`
                : 'Belum Disetujui Seluruhnya<br>Oleh Direktur Utama';
        }

        /* PERINGKAT 13 BUTTONS */
        const btnS13 = $id('btnSimpanPeringkat13');
        if (btnS13) btnS13.addEventListener('click', debounce(function () {
            const p = $id('peringkat13_manual')?.value;
            if (!p) { showToast('Error', 'Pilih peringkat terlebih dahulu', 'error'); return; }
            const orig = this.innerHTML; this.disabled = true; this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            postJSON('Risikokreditkpmr/ubahPeringkat13Manual', { penilaiankredit: p })
                .then(r => {
                    if (r.status === 'success') { showToast('Berhasil', 'Peringkat berhasil diubah', 'success'); setTimeout(() => location.reload(), 800); }
                    else { showToast('Error', r.message, 'error'); this.disabled = false; this.innerHTML = orig; }
                })
                .catch(() => { this.disabled = false; this.innerHTML = orig; showToast('Error', 'Gagal', 'error'); });
        }, 400));

        const btnR13 = $id('btnResetPeringkat13');
        if (btnR13) btnR13.addEventListener('click', debounce(function () {
            if (!confirm('Kembalikan ke perhitungan otomatis?')) return;
            const orig = this.innerHTML; this.disabled = true; this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            postJSON('Risikokreditkpmr/resetPeringkat13Auto', {})
                .then(r => {
                    if (r.status === 'success') { showToast('Berhasil', 'Peringkat direset ke otomatis', 'success'); setTimeout(() => location.reload(), 800); }
                    else { showToast('Error', r.message, 'error'); this.disabled = false; this.innerHTML = orig; }
                })
                .catch(() => { this.disabled = false; this.innerHTML = orig; showToast('Error', 'Gagal', 'error'); });
        }, 400));

        /* APPROVE LINKS */
        function wireApproveLink(id, confirmMsg, successMsg, approved) {
            const lnk = $id(id); if (!lnk) return;
            lnk.addEventListener('click', function (e) {
                e.preventDefault();
                if (!confirm(confirmMsg)) return;
                let path; try { path = new URL(this.href).pathname; } catch (_) { path = this.href.replace(/^https?:\/\/[^/]+/, ''); }
                const orig = this.innerHTML; this.classList.add('disabled-btn');
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                postJSON(path, {})
                    .then(() => {
                        showToast('Berhasil', successMsg, 'success');
                        if (approved) updateMainApprovalBadge(true, new Date().toLocaleString('id-ID'));
                        else updateMainApprovalBadge(false);
                        this.classList.remove('disabled-btn'); this.innerHTML = orig;
                        setTimeout(() => location.reload(), 800);
                    })
                    .catch(() => { this.classList.remove('disabled-btn'); this.innerHTML = orig; showToast('Error', 'Gagal', 'error'); });
            });
        }
        wireApproveLink('btnApproveSemua', 'Apakah Anda yakin ingin melakukan approval?', 'Semua data berhasil disetujui', true);
        wireApproveLink('btnUnapproveSemua', 'Batalkan semua approval?', 'Semua approval berhasil dibatalkan', false);

        /* NOTIFICATION BADGE POLLING */
        function pollUnreadCounts() {
            postJSON('Risikokreditkpmr/getAllUnreadCounts',
                { kodebpr: GLOBAL_KODEBPR, periode_id: GLOBAL_PERIODE_ID }, 'unreadPoll')
                .then(r => {
                    if (r.status !== 'success') return;
                    document.querySelectorAll('.notification-badge').forEach(b => { b.style.display = 'none'; b.textContent = ''; });
                    Object.entries(r.counts || {}).forEach(([fid, cnt]) => {
                        const b = $id('notification-badge-' + fid);
                        if (b && cnt > 0) { b.textContent = cnt; b.style.display = 'inline-flex'; }
                    });
                }).catch(() => { });
        }

        (function initSyncModule() {
            const syncBar        = $id('sync-bar');
            const syncBarTitle   = $id('sync-bar-title');
            const syncBarText    = $id('sync-bar-text');
            const btnSyncTrigger = $id('btn-sync-trigger');
            const syncNodataBar  = $id('sync-nodata-bar');
            const syncNodataText = $id('sync-nodata-text');
            const syncModalInfo  = $id('sync-modal-info');
            const syncProgress   = $id('sync-progress');
            const syncForceChk   = $id('sync-force-overwrite');
            const syncToggleTrack= $id('sync-toggle-track');
            const syncToggleThumb= $id('sync-toggle-thumb');
            const syncToggleLabel= $id('sync-toggle-label');
            const syncWarnEl     = $id('sync-overwrite-warning');
            const btnSyncExecute = $id('btn-sync-execute');

            if (!syncBar || !btnSyncTrigger || !btnSyncExecute) return;

            let _prevInfo       = null;
            let _currentHasData = false;
            let _checkDone      = false;

            /* ── Toggle switch ── */
            function setToggle(checked) {
                if (!syncForceChk) return;
                syncForceChk.checked = checked;
                if (syncToggleTrack) syncToggleTrack.style.background = checked ? '#1f2aa8' : '#ced4da';
                if (syncToggleThumb) syncToggleThumb.style.left = checked ? '21px' : '3px';
            }
            if (syncToggleLabel) {
                syncToggleLabel.addEventListener('click', function(e) {
                    if (e.target === syncForceChk) return;
                    e.preventDefault();
                    setToggle(!syncForceChk.checked);
                });
            }

            /* ── STEP 1: check (1x per load, ringan — COUNT saja) ── */
            function checkPrevPeriode() {
                if (_checkDone) return;
                _checkDone = true;

                postJSON('Risikokreditkpmr/checkPrevPeriode', {})
                    .then(r => {
                        if (r.status === 'available') {
                            _prevInfo       = r.prev_info;
                            _currentHasData = !!r.current_has_data;
                            const pi = r.prev_info;
                            if (syncBarTitle) syncBarTitle.textContent =
                                'Data Semester ' + pi.semester + ' Tahun ' + pi.tahun + ' tersedia';
                            if (syncBarText) syncBarText.textContent =
                                pi.faktor_count + ' form penilaian KPMR siap disinkronisasi' +
                                (_currentHasData ? ' · Periode aktif sudah punya sebagian data' : '');
                            syncBar.style.display = 'flex';

                        } else if (r.status === 'no_data' || r.status === 'no_prev') {
                            if (syncNodataText) syncNodataText.textContent =
                                r.message || 'Tidak ada data periode sebelumnya.';
                            if (syncNodataBar) syncNodataBar.style.display = 'flex';
                        }
                    })
                    .catch(() => {});
            }

            /* ── STEP 2: buka modal ── */
            btnSyncTrigger.addEventListener('click', function() {
                if (!_prevInfo) return;
                const pi = _prevInfo;

                if (syncModalInfo) {
                    syncModalInfo.innerHTML =
                        '<div style="font-size:12px;font-weight:600;color:#27500A;margin-bottom:3px;">Sumber data</div>' +
                        '<div style="font-size:15px;font-weight:600;color:#173404;margin-bottom:2px;">' +
                            'Semester ' + pi.semester + ' Tahun ' + pi.tahun +
                        '</div>' +
                        '<div style="font-size:12px;color:#3B6D11;">' +
                            pi.faktor_count + ' form penilaian KPMR tersedia' +
                        '</div>';
                }

                if (syncWarnEl) {
                    syncWarnEl.style.display = _currentHasData ? 'flex' : 'none';
                }

                setToggle(false);
                if (syncProgress) syncProgress.style.display = 'none';
                btnSyncExecute.disabled = false;
                btnSyncExecute.style.background = 'linear-gradient(135deg,#141863 0%,#1f2aa8 100%)';
                btnSyncExecute.innerHTML = '<i class="fas fa-sync-alt"></i> Mulai Sinkronisasi';

                modalShow('modalSyncConfirm');
            });

            /* ── STEP 3: eksekusi sync ── */
            btnSyncExecute.addEventListener('click', function() {
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
                if (syncProgress) syncProgress.style.display = 'flex';

                const forceOverwrite = syncForceChk && syncForceChk.checked ? 1 : 0;

                postJSON('Risikokreditkpmr/syncFromPrevPeriode', {force_overwrite: forceOverwrite})
                    .then(r => {
                        if (syncProgress) syncProgress.style.display = 'none';
                        modalHide('modalSyncConfirm');

                        if (r.status === 'success') {
                            showToast('Sinkronisasi Berhasil', r.message, 'success');
                            syncBar.style.display = 'none';
                            setTimeout(() => location.reload(), 1200);

                        } else if (r.status === 'no_change') {
                            showToast('Tidak Ada Perubahan', r.message, 'info');
                            if (!forceOverwrite) {
                                setToggle(true);
                                if (syncWarnEl) syncWarnEl.style.display = 'flex';
                                modalShow('modalSyncConfirm');
                            }
                            this.disabled = false;
                            this.innerHTML = '<i class="fas fa-sync-alt"></i> Mulai Sinkronisasi';

                        } else {
                            showToast('Gagal', r.message || 'Terjadi kesalahan saat sinkronisasi', 'error');
                            this.disabled = false;
                            this.innerHTML = '<i class="fas fa-sync-alt"></i> Mulai Sinkronisasi';
                        }
                    })
                    .catch(err => {
                        if (syncProgress) syncProgress.style.display = 'none';
                        this.disabled = false;
                        this.innerHTML = '<i class="fas fa-sync-alt"></i> Mulai Sinkronisasi';
                        showToast('Error', 'Koneksi gagal: ' + err.message, 'error');
                    });
            });

            setTimeout(checkPrevPeriode, 800);
        })();

        let _pollTimer = null;
        function startPolling(ms) { if (_pollTimer) clearInterval(_pollTimer); _pollTimer = setInterval(pollUnreadCounts, ms); }

        document.addEventListener('visibilitychange', () => {
            document.hidden ? startPolling(120000) : (pollUnreadCounts(), startPolling(60000));
        });
        pollUnreadCounts();
        startPolling(60000);

    })();
</script>


<!-- CSS -->
<style>
    :root {
        --primary-color: linear-gradient(135deg, #141863 0%, #1f2aa8 100%);
        --success-color: #28a745;
        --danger-color: #dc3545;
        --dark-color: #343a40;
        --secondary-color: #6c757d;
        --light-bg: #f8f9fa;
        --white: #ffffff;
        --border-color: #dee2e6;
        --box-shadow: 0 2px 4px rgba(0, 0, 0, .1);
        --box-shadow-hover: 0 4px 8px rgba(0, 0, 0, .2);
        --border-radius: 8px;
        --transition: all .2s ease-in-out;
    }

    body {
        font-family: Arial, sans-serif;
        background: var(--light-bg);
        margin: 0;
        padding: 0;
    }

    .container-fluid {
        margin-top: 30px;
    }

    .alert {
        font-size: 14px;
        padding: 15px;
        border-radius: var(--border-radius);
        margin-bottom: 20px;
    }

    .alert-success {
        background: var(--success-color);
        color: #fff;
    }

    .card {
        margin-bottom: 20px;
        border-radius: 10px;
        box-shadow: var(--box-shadow);
    }

    .card-body {
        padding: 20px;
    }

    .card-header {
        background: #f1f3f5;
        border-bottom: 2px solid #ddd;
        font-weight: bold;
    }

    /* Badges */
    .badge {
        font-size: 14px;
        padding: 5px 10px;
        border-radius: 20px;
    }

    .badge-success {
        background: var(--success-color);
        color: #fff;
    }

    .badge-secondary {
        background: var(--secondary-color);
        color: #fff;
    }

    .badge-primary {
        background: #12131C;
        color: #fff;
    }

    .badge-info {
        background: #17a2b8;
        color: #fff;
    }

    .badge-warning {
        background: #ffc107;
        color: #212529;
    }

    .badge-danger {
        background: var(--danger-color);
        color: #fff;
    }

    .badge-dark {
        background: var(--dark-color);
        color: #fff;
    }

    /* Notification badge */
    .notification-badge {
        position: absolute;
        top: -6px;
        right: -6px;
        min-width: 18px;
        height: 18px;
        padding: 0 4px;
        font-size: 10px;
        font-weight: 700;
        border-radius: 9px;
        background: var(--danger-color);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-sizing: border-box;
        line-height: 1;
        pointer-events: none;
        z-index: 1;
    }

    .komentar-button {
        position: relative;
    }

    /* Approval */
    .approvaldir-card {
        border-radius: 20px;
        background: var(--white);
        overflow: hidden;
        box-shadow: var(--box-shadow);
    }

    .approvaldir-card-body {
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 15px;
        height: 100%;
    }

    .approval-badge-container {
        text-align: center;
        margin-bottom: 6px;
    }

    .approval-badge {
        background: var(--dark-color);
        color: #fff;
        font-size: .8em;
        font-weight: 500;
        padding: 5px 12px;
        border-radius: 5px;
        display: inline-block;
        margin-bottom: 7px;
    }

    .approval-buttons-container {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 9px;
    }

    .approval-btn {
        flex: 1;
        max-width: 50%;
        padding: 8px 13px;
        font-size: .95em;
        font-weight: 500;
        border-radius: 28px;
        text-align: center;
        text-decoration: none;
        white-space: nowrap;
        box-shadow: var(--box-shadow);
        transition: var(--transition);
    }

    .approval-btn-approve {
        background: var(--success-color);
        border-color: var(--success-color);
        color: #fff;
    }

    .approval-btn-approve:hover {
        background: #218838;
        transform: translateY(-1px);
    }

    .approval-btn-reject {
        background: var(--danger-color);
        border-color: var(--danger-color);
        color: #fff;
    }

    .approval-btn-reject:hover {
        background: #c82333;
        transform: translateY(-1px);
    }

    /* Buttons */
    .btn {
        font-size: 14px;
        padding: 8px 20px;
        border-radius: 5px;
        cursor: pointer;
        transition: var(--transition);
        font-weight: 500;
        border: 1px solid transparent;
    }

    .btn:hover:not(:disabled):not(.disabled-btn) {
        transform: scale(1.05);
        box-shadow: var(--box-shadow-hover);
    }

    .btn-sm {
        padding: 5px 10px;
        font-size: 12px;
    }

    .btn-primary {
        background: var(--primary-color);
        color: #fff;
        border-color: var(--primary-color);
    }

    .btn-primary:hover {
        background: #0b5ed7;
    }

    .btn-outline-primary {
        border: 1px solid #1f2aa8;
        background: transparent;
        color: #1f2aa8;
    }

    .btn-outline-primary:hover {
        background: #1f2aa8;
        color: #fff;
    }

    .disabled-btn {
        opacity: .5;
        pointer-events: none;
    }

    .btn-close {
        background: none;
        border: none;
        padding: .5rem;
        cursor: pointer;
        font-size: 1.5rem;
        color: #000;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
    }

    /* Nav */
    .button-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        margin: 15px 0;
        flex-wrap: wrap;
        gap: 8px;
    }

    .kpmr-btn {
        background: var(--primary-color);
        color: #fff;
        font-weight: 600;
        padding: 8px 20px;
        border-radius: 6px;
        text-decoration: none;
        transition: var(--transition);
    }

    .kpmr-btn:hover {
        background: #0d4a73;
        transform: translateY(-2px);
        color: #fff;
    }

    /* Modal */
    .modal-header {
        background: var(--primary-color);
        color: #fff;
        font-size: 18px;
        padding: 15px;
    }

    .modal-footer .btn {
        padding: 10px 25px;
        font-size: 16px;
        border-radius: var(--border-radius);
    }

    /* Rating list */
    .list-group-item.active {
        background: #007bff !important;
        border-color: #007bff !important;
        color: #fff !important;
    }

    .list-group-item.active h6,
    .list-group-item.active p,
    .list-group-item.active b {
        color: #fff !important;
    }

    .list-group-item:hover:not(.active) {
        background: #f8f9fa;
        cursor: pointer;
    }

    .list-group-item h6 {
        font-size: 1rem;
        margin-bottom: .25rem;
    }

    .list-group-item p {
        font-size: .875rem;
        line-height: 1.4;
    }

    /* Collapse */
    .collapse {
        display: none;
    }

    .collapse.show {
        display: block;
    }

    /* Toast */
    #toast-wrapper {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 8px;
        max-width: min(360px, calc(100vw - 40px));
        pointer-events: none;
    }

    .toast-notification {
        border-radius: 8px;
        color: #fff;
        display: flex;
        align-items: center;
        gap: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, .3);
        transform: translateX(400px);
        opacity: 0;
        transition: transform .3s ease, opacity .3s ease;
        padding: 14px 18px;
        min-width: 250px;
        pointer-events: all;
    }

    .toast-notification.show {
        transform: translateX(0);
        opacity: 1;
    }

    .toast-notification i {
        font-size: 22px;
    }

    .toast-notification div {
        flex: 1;
    }

    /* Responsive */
    @media(max-width:768px) {
        .container-fluid {
            margin-top: 10px;
            padding-left: 10px;
            padding-right: 10px;
        }

        .card-body {
            padding: 12px;
        }

        .button-bar {
            flex-direction: column;
            align-items: stretch;
        }

        .kpmr-btn {
            text-align: center;
        }

        .approval-btn {
            font-size: .85em;
            padding: 6px 10px;
        }

        .badge.badge-lg {
            font-size: .85em !important;
            padding: .35em .6em !important;
        }

        #toast-wrapper {
            top: 8px;
            right: 8px;
            left: 8px;
            max-width: none;
        }

        .toast-notification {
            min-width: auto;
        }

        .d-flex.justify-content-between {
            flex-direction: column;
            gap: 8px;
        }

        .modal-dialog {
            margin: 8px;
        }
    }

    @media(max-width:576px) {
        h4 {
            font-size: 1.2rem;
        }

        .approval-buttons-container {
            flex-direction: column;
            gap: 5px;
        }

        .approval-btn {
            max-width: 100%;
        }

        .btn-sm {
            font-size: 11px;
            padding: 4px 8px;
        }

        .card-header {
            flex-direction: column;
            align-items: flex-start !important;
        }

        .card-header>div {
            margin-top: 6px;
            flex-wrap: wrap;
        }

        .border-left.pl-4 {
            padding-left: 8px !important;
        }

        .d-flex.align-items-start {
            flex-direction: column;
        }

        .d-flex.align-items-start .ml-3 {
            margin-left: 0 !important;
            margin-top: 8px;
        }
    }

    .child-row {
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }

    .child-title {
        flex: 1 1 0;
        min-width: 0;
    }

    .child-title h6 {
        margin-bottom: 0;
        word-break: break-word;
    }

    .child-btns {
        display: flex;
        align-items: center;
        gap: 4px;
        flex-shrink: 0;
        flex-wrap: nowrap;
    }

    #sync-bar,
    #sync-nodata-bar {
        border-radius: var(--border-radius);
        font-size: .9em;
        margin-bottom: 12px;
        padding: 10px 14px;
    }
    #sync-bar {
        background: #fff3cd;
        border: 1px solid #ffc107;
        color: #856404;
    }
    #sync-nodata-bar {
        background: #d1ecf1;
        border: 1px solid #bee5eb;
        color: #0c5460;
    }
    #btn-sync-trigger {
        flex-shrink: 0;
        font-size: .85em;
        padding: 4px 12px;
        border-radius: 5px;
        white-space: nowrap;
    }
    /* Sync modal — progress area */
    #sync-progress {
        background: #f8f9fa;
        border-radius: 6px;
        padding: 10px;
        font-size: .88em;
    }

    @media (max-width: 576px) {
        #sync-bar { flex-direction: column; align-items: flex-start; }
        #btn-sync-trigger { width: 100%; justify-content: center; }
    }

    .custom-control {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        padding: 4px 0;
    }

    .custom-control-input {
        margin-top: 3px;
        cursor: pointer;
        width: 16px;
        height: 16px;
        flex-shrink: 0;
    }

    .custom-control-label {
        cursor: pointer;
        line-height: 1.4;
    }

    @media (max-width: 768px) {
        #sync-bar {
            flex-direction: column;
            align-items: flex-start;
        }

        #btn-sync-trigger {
            width: 100%;
        }
    }

    /* ── Parent card-header button group ── */
    .parent-btns {
        display: flex;
        align-items: center;
        gap: 4px;
        flex-shrink: 0;
        flex-wrap: nowrap;
    }

    /* Remove stray ml-2 inside button groups */
    .child-btns .ml-2,
    .parent-btns .ml-2 {
        margin-left: 0 !important;
    }

    .child-btns .ml-1,
    .parent-btns .ml-1 {
        margin-left: 0 !important;
    }

    /* form-approval inside button group — no extra margin */
    .child-btns .form-approval,
    .parent-btns .form-approval {
        display: inline-flex !important;
        margin-left: 0 !important;
    }

    @media(max-width:576px) {
        .child-row {
            flex-direction: column;
            gap: 8px;
        }

        .child-btns {
            flex-wrap: wrap;
        }

        .parent-btns {
            flex-wrap: wrap;
        }
    }
</style>