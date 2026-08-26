<?php
$peringkat13 = null;
$keterangan13 = '';
$peringkat14 = null;
$keterangan14 = '';

if (isset($nilai13) && is_array($nilai13)) {
    $peringkat13 = $nilai13['penilaiankredit'] ?? null;
    $keterangan13 = $nilai13['keterangan'] ?? '';
}

if (isset($nilai14) && is_array($nilai14)) {
    $peringkat14 = $nilai14['penilaiankredit'] ?? null;
    $keterangan14 = $nilai14['keterangan'] ?? '';
}

$peringkatLabels = [
    '1' => 'Sangat Rendah',
    '2' => 'Rendah',
    '3' => 'Sedang',
    '4' => 'Tinggi',
    '5' => 'Sangat Tinggi'
];

function getBadgeClass($nilai)
{
    $map = ['1'=>'badge-info','2'=>'badge-success','3'=>'badge-warning','4'=>'badge-danger','5'=>'badge-dark'];
    return $map[$nilai] ?? 'badge-secondary';
}

$currentUserId   = session()->get('user_id');
$activePeriodeId = session()->get('active_periode');
$canEdit         = ($userInGroupAdmin ?? false) || ($userInGroupPE ?? false);
$canApproveDir   = ($userInGroupAdmin ?? false) || ($userInGroupDireksi2 ?? false) || ($userInGroupDireksi ?? false);
$kesimpulan13    = $nilai13 ?? null;
$isManual13      = isset($nilai13['is_manual']) && $nilai13['is_manual'] == 1;
$badge13Class    = getBadgeClass($peringkat13);
$badge14Class    = getBadgeClass($peringkat14);
$label13         = $peringkatLabels[$peringkat13] ?? 'N/A';
$label14         = $peringkatLabels[$peringkat14] ?? 'N/A';
$approvedAt      = $nilai13['approved_at'] ?? null;
$approvedAtStr   = $approvedAt ? date('d/m/Y H:i:s', strtotime($approvedAt)) : '-';
$semester        = esc($periodeDetail['semester'] ?? '');
$tahun           = esc($periodeDetail['tahun'] ?? '');
?>

<div class="alert alert-info my-2">
    <?php if (isset($bprData) && isset($periodeDetail)): ?>
        <strong><?= esc($bprData['namabpr'] ?? 'Nama BPR') ?></strong> - Periode Pelaporan Semester <?= $semester ?> Tahun <?= $tahun ?>
    <?php elseif (isset($periodeDetail)): ?>
        <strong>Periode:</strong> Semester <?= $semester ?> Tahun <?= $tahun ?>
    <?php else: ?>
        <strong>Periode belum ditentukan</strong>
    <?php endif; ?>
</div>

<div id="sync-bar" style="display:none;">
    <div id="sync-bar-text" class="sync-bar-text"></div>
    <button type="button" id="btn-sync-trigger" class="btn btn-sm btn-warning">
        <i class="fas fa-sync-alt"></i> Sinkronisasi
    </button>
</div>
 
<!-- tidak ada data periode sebelumnya -->
<div id="sync-nodata-bar" style="display:none;">
    <i class="fas fa-info-circle"></i>
    <span id="sync-nodata-text"></span>
</div>

<div id="ajaxAlert" class="alert alert-success alert-dismissible fade show d-none" role="alert">
    <span id="ajaxAlertMsg"></span>
    <button type="button" class="close" data-bs-dismiss="alert">&times;</button>
</div>

<div class="container-fluid">
    <?php if (session()->get('message')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <button type="button" class="close" data-bs-dismiss="alert"><span aria-hidden="true">&times;</span></button>
            <strong><?= session()->getFlashdata('message'); ?></strong>
        </div>
    <?php endif; ?>

    <div class="card card-body">
        <div class="table-vertical">
            <div class="button-bar">
                <a href="Showprofilresiko" class="kpmr-btn icon-link icon-link-hover" id="btn-laporan">🡸 Laporan Profil Risiko</a>
                <a href="Likuiditaskpmr" class="kpmr-btn" id="btn-risiko">Risiko Likuiditas KPMR 🡺</a>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
                <div class="text-center flex-grow-1">
                    <h4 class="mb-0"><i class="fas fa-info-circle text-primary"></i> <strong>Tingkat Likuiditas Inheren</strong></h4>
                    <small class="text-muted"><i class="fas fa-file-alt"></i> Laporan Profil Risiko Semester <?= $semester ?> Tahun <?= $tahun ?></small>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
                <div class="text-center flex-grow-1">
                    <div class="mt-2">
                        <span class="badge badge-lg <?= $badge13Class ?>" style="font-size:1.1em;padding:0.5em 1em;">
                            <i class="fas fa-check-circle"></i>
                            Peringkat penilaian: <?= esc($peringkat13 ?? 'N/A') ?> [<?= $label13 ?>]
                        </span>
                        <?php if ($canEdit): ?>
                            <?php if (!$isManual13): ?>
                                <button type="button" class="btn btn-sm btn-outline-primary ml-2" data-bs-toggle="collapse" data-bs-target="#editPeringkat13"><i class="fa fa-caret-down"></i></button>
                            <?php else: ?>
                                <button type="button" class="btn btn-sm btn-warning ml-2" id="btnResetPeringkat13" title="Kembalikan ke perhitungan otomatis"><i class="fa fa-undo"></i></button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <?php if ($canEdit): ?>
                    <div id="editPeringkat13" class="collapse">
                        <div class="p-3 border-top">
                            <select id="peringkat13_manual" class="form-control w-50 mx-auto">
                                <option value="">-- Pilih Peringkat --</option>
                                <?php foreach ([1=>'Sangat Rendah',2=>'Rendah',3=>'Sedang',4=>'Tinggi',5=>'Sangat Tinggi'] as $v=>$l): ?>
                                <option value="<?= $v ?>" <?= $peringkat13 == $v ? 'selected' : '' ?>><?= $v ?> - <?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="text-center">
                                <button type="button" class="btn btn-warning btn-sm mt-2" id="btnSimpanPeringkat13"><i class="fas fa-edit"></i> Update</button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="mt-2">
                        <span class="badge badge-lg <?= $badge14Class ?>" style="font-size:1.1em;padding:0.5em 1em;">
                            <i class="fas fa-check-circle"></i>
                            Peringkat penilaian pada periode sebelumnya: <?= esc($peringkat14 ?? 'N/A') ?> [<?= $label14 ?>]
                        </span>
                        <?php if ($canEdit): ?>
                            <button type="button" class="btn btn-sm btn-outline-primary ml-2" data-bs-toggle="collapse" data-bs-target="#editNilai14"><i class="fa fa-caret-down"></i></button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div id="editNilai14" class="collapse">
                <div class="p-3 border-top">
                    <form action="<?= base_url('Likuiditasinheren/simpanNilai14') ?>" method="post">
                        <?= csrf_field() ?>
                        <select name="penilaiankredit" class="form-control w-50 mx-auto" required>
                            <?php if (!empty($nilai14)): ?>
                                <option value="" disabled selected>-- Nilai Saat Ini: <?= $nilai14['penilaiankredit'] ?> --</option>
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
                            <button type="submit" class="btn <?= !empty($nilai14) ? 'btn-warning' : 'btn-primary' ?> btn-sm mt-2">
                                <i class="fas <?= !empty($nilai14) ? 'fa-edit' : 'fa-save' ?>"></i> <?= !empty($nilai14) ? 'Update' : 'Simpan' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div style="text-align:right;">
                <span id="approval-badge-main" class="badge <?= ($allApproved ?? false) ? 'badge-success' : 'badge-secondary' ?>" style="font-size:14px;">
                    <?php if ($allApproved ?? false): ?>
                        Disetujui oleh <strong>Direktur Utama</strong><br><?= $approvedAtStr ?>
                    <?php else: ?>
                        Belum Disetujui Seluruhnya<br>Oleh Direktur Utama
                    <?php endif; ?>
                </span>
            </div>

            <div class="card-body">
                <div class="mb-2"><strong>A. Kertas Kerja Inheren Likuiditas</strong></div>
                <?php if ($canEdit): ?>
                    <button class="btn btn-primary mr-2 mb-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasWithBothOptions" aria-controls="offcanvasWithBothOptions">
                        Kalkulator Risiko Likuiditas Inheren
                    </button>
                <?php endif; ?>

                <div id="factors-skeleton" style="display:block;">
                    <?php for ($i = 1; $i <= 3; $i++): ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3"><div class="skeleton skeleton-text" style="width:60%;"></div></div>
                        <div class="card-body">
                            <div class="skeleton skeleton-text mb-2" style="width:80%;"></div>
                            <div class="skeleton skeleton-text" style="width:70%;"></div>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>

                <div id="factors-container" style="display:none;"></div>

                <?php if ($canEdit): ?>
                <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasWithBothOptions" aria-labelledby="offcanvasWithBothOptionsLabel">
                    <div class="offcanvas-header">
                        <h5 class="offcanvas-title" id="offcanvasWithBothOptionsLabel">Kalkulator Komposisi Portofolio Aset</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close">&times;</button>
                    </div>
                    <div class="offcanvas-body">
                        <form id="riskForm">
                            <?php
                            // Build input fields from config array to avoid repetition
                            $fields = [
                                'totalaset' => 'Total Aset',
                                'kas' => 'Kas',
                                'girobanklain' => 'Giro pada bank lain',
                                'tabunganbanklain' => 'Tabungan pada Bank Lain',
                                'kewajibansegera' => 'Kewajiban segera',
                                'tabungandpk' => 'Tabungan DPK',
                                'depositodpk' => 'Deposito DPK',
                                'tabunganabp' => 'Tabungan ABP',
                                'depositoabp' => 'Deposito ABP',
                                'pinjamanditerima' => 'Pinjaman yang diterima',
                                'kreditkyd' => 'Kredit yang diberikan',
                                'penabung25deposan' => '25 deposan dan penabung terbesar',
                                'totalpendanaan' => 'Total Pendanaan',
                                'dpkdiataslps' => 'DPK di atas LPS',
                                'pinjamananmungkinditarik'=> 'Pinjaman yang Mungkin Ditarik Sewaktu-Waktu',
                            ];
                            foreach ($fields as $name => $label):
                                $val = esc($kalkulatorData[$name] ?? '');
                            ?>
                            <div class="mb-3">
                                <label for="<?= $name ?>" class="form-label"><?= $label ?></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" step="0.01" class="form-control" id="<?= $name ?>" name="<?= $name ?>" value="<?= $val ?>" placeholder="Masukkan nilai" required>
                                </div>
                            </div>
                            <?php endforeach; ?>

                            <input type="hidden" id="asetlikuid_hidden">
                            <input type="hidden" id="kewajibanlancar_hidden">
                            <input type="hidden" id="totaldpk_hidden">
                            <input type="hidden" id="transaksibpr_hidden">
                            <input type="hidden" id="pendanaannoninti_hidden">
                            <input type="hidden" id="rasioasetlikuidtotalaset_submit">
                            <input type="hidden" id="rasioasetlikuidkewajiban_submit">
                            <input type="hidden" id="rasiokreditterhadapdpk_submit">
                            <input type="hidden" id="rasiokreditdiberikan_submit">
                            <input type="hidden" id="rasio25deposan_submit">
                            <input type="hidden" id="rasiononinti_submit">

                            <button type="button" class="btn btn-primary w-100" id="btnHitung"><i class="fas fa-calculator"></i> Hitung</button>
                        </form>

                        <div id="riskResult" class="risk-result mt-3"></div>

                        <div id="detailResult" style="display:none;" class="mt-4">
                            <h6>Hasil Perhitungan:</h6>
                            <table class="table table-sm">
                                <tr><td>Aset Likuid</td><td id="asetlikuid">-</td></tr>
                                <tr><td>Kewajiban lancar</td><td id="kewajibanlancar">-</td></tr>
                                <tr><td>Total DPK</td><td id="totaldpk_display">-</td></tr>
                                <tr><td>Transaksi antar BPR</td><td id="transaksibpr">-</td></tr>
                                <tr><td>Pendanaan Non Inti</td><td id="pendanaannoninti">-</td></tr>
                                <tr><td>Rasio aset likuid terhadap total aset</td><td id="rasioasetlikuidtotalaset">-</td></tr>
                                <tr><td>Rasio aset likuid terhadap kewajiban lancar</td><td id="rasioasetlikuidkewajiban">-</td></tr>
                                <tr><td>Rasio kredit yang diberikan terhadap total DPK (LDR)</td><td id="rasiokreditterhadapdpk">-</td></tr>
                                <tr><td>Rasio 25 deposan dan penabung terbesar terhadap total DPK</td><td id="rasio25deposan">-</td></tr>
                                <tr><td>Rasio pendanaan non inti terhadap total pendanaan</td><td id="rasiononinti">-</td></tr>
                            </table>
                            <button type="button" id="btnInsertRasio" class="btn btn-success w-100 mt-2" style="display:none;"><i class="fas fa-save"></i> Masukkan ke Kertas Kerja</button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="card mt-3">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-clipboard-list"></i> Kesimpulan Penilaian Risiko Likuiditas Inheren</h6>
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
                                    <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="collapse" data-bs-target="#editKesimpulan13"><i class="fas fa-edit"></i> Edit</button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($canEdit): ?>
                        <div class="collapse" id="editKesimpulan13">
                            <hr>
                            <form action="<?= base_url('Likuiditasinheren/simpanKesimpulan13') ?>" method="post">
                                <?= csrf_field() ?>
                                <div class="form-group">
                                    <label for="keterangan_edit"><strong>Edit Keterangan Kesimpulan:</strong></label>
                                    <textarea class="form-control" name="keterangan" id="keterangan_edit" rows="5" required><?= esc($kesimpulan13['keterangan']) ?></textarea>
                                </div>
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-secondary" data-bs-toggle="collapse" data-bs-target="#editKesimpulan13"><i class="fas fa-times"></i> Batal</button>
                                    <button type="submit" class="btn btn-primary ml-3"><i class="fas fa-save"></i> Update Kesimpulan</button>
                                </div>
                            </form>
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if ($canEdit): ?>
                            <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> <strong>Kesimpulan belum diisi.</strong> Silakan isi keterangan kesimpulan di bawah ini.</div>
                            <form action="<?= base_url('Likuiditasinheren/simpanKesimpulan13') ?>" method="post">
                                <?= csrf_field() ?>
                                <div class="form-group">
                                    <label for="keterangan_new"><strong>Keterangan Kesimpulan:</strong></label>
                                    <textarea class="form-control" name="keterangan" id="keterangan_new" rows="5" placeholder="Masukkan kesimpulan penilaian risiko kredit inheren..." required></textarea>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Simpan Kesimpulan</button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-info"><i class="fas fa-info-circle"></i> Kesimpulan belum diisi oleh Tim Penilaian Risiko.</div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (($userInGroupAdmin ?? false) || ($userInGroupDireksi ?? false)): ?>
            <div class="d-flex justify-content-center mt-3">
                <div class="col-md-3 mx-auto">
                    <div class="card shadow-sm approvaldir-card" style="height:120px;">
                        <div class="card-body approvaldir-card-body">
                            <div class="approval-badge-container">
                                <span class="badge approval-badge">Approval Direktur Utama</span>
                            </div>
                            <div class="approval-buttons-container">
                                <a href="<?= base_url('Likuiditasinheren/approveSemua') ?>" id="btn-approve-semua" class="btn approval-btn approval-btn-approve disabled-btn">Approve</a>
                                <a href="<?= base_url('Likuiditasinheren/unapproveSemua') ?>" id="btn-unapprove-semua" class="btn approval-btn approval-btn-reject disabled-btn">Tolak</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modals -->
<div class="modal fade" id="modalUbah">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ubah <?= $judul ?? 'Data'; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('Likuiditasinheren/ubah'); ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="faktor1id" id="edit-faktor-id">
                    <div class="mb-3">
                        <label for="edit-parameterpenilaian"><strong>Parameter penilaian risiko:</strong></label>
                        <textarea class="form-control" id="edit-parameterpenilaian" style="height:100px" readonly></textarea>
                    </div>
                    <label><strong>Pilih Tingkat Risiko:</strong></label>
                    <div class="alert alert-info mb-2" id="edit-catatan-info" style="display:none;font-size:0.9em;"><i class="fas fa-info-circle"></i> <span id="edit-catatan-text"></span></div>
                    <div class="list-group mb-3" id="editRatingList"></div>
                    <p>Nilai yang dipilih: <strong id="editSelectedValue" class="text-primary"></strong></p>
                    <input type="hidden" name="penilaiankredit" id="edit-penilaiankredit">
                    <input type="hidden" name="penjelasanpenilaian" id="edit-penjelasanpenilaian">
                    <div class="mb-3">
                        <label for="edit-keterangan"><strong>Keterangan:</strong></label>
                        <textarea class="form-control" name="keterangan" id="edit-keterangan" style="height:100px" placeholder="Masukkan keterangan..." required></textarea>
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

<div class="modal fade" id="modalUbahkesimpulan">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ubah Keterangan Kesimpulan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('Likuiditasinheren/ubahkesimpulan'); ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="faktor1id" id="edit-faktor-id-kesimpulan">
                    <div class="mb-3">
                        <label><strong>Sub Kategori:</strong></label>
                        <textarea class="form-control" id="edit-parameterpenilaian-kesimpulan" style="height:100px" readonly></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit-keterangan-kesimpulan"><strong>Keterangan:</strong></label>
                        <textarea class="form-control" name="keterangan" id="edit-keterangan-kesimpulan" style="height:100px" placeholder="Masukkan keterangan..." required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Ubah Keterangan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modaltambahNilai">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="<?= base_url('Likuiditasinheren/tambahNilai'); ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Nilai Faktor</h5>
                    <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="faktor_id" id="add-faktor-id">
                    <input type="hidden" name="fullname" value="<?= esc($fullname ?? '') ?>">
                    <div class="mb-3">
                        <label for="add-parameterpenilaian"><strong>Parameter penilaian risiko:</strong></label>
                        <textarea class="form-control" id="add-parameterpenilaian" style="height:100px" readonly></textarea>
                    </div>
                    <label><strong>Pilih Tingkat Risiko:</strong></label>
                    <div class="alert alert-info mb-2" id="add-catatan-info" style="display:none;font-size:0.9em;"><i class="fas fa-info-circle"></i> <span id="add-catatan-text"></span></div>
                    <div class="list-group mb-3" id="addRatingList"></div>
                    <p>Nilai yang dipilih: <strong id="addSelectedValue" class="text-primary">Belum ada</strong></p>
                    <input type="hidden" name="penilaiankredit" id="add-penilaiankredit" required>
                    <input type="hidden" name="penjelasanpenilaian" id="add-penjelasanpenilaian">
                    <div class="mb-3">
                        <label for="add-keterangan"><strong>Keterangan:</strong></label>
                        <textarea class="form-control" name="keterangan" id="add-keterangan" style="height:100px" placeholder="Masukkan keterangan..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" name="tambahNilai" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modaltambahKomentar">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= base_url('Likuiditasinheren/tambahKomentar'); ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Komentar Direksi dan Dewan Komisaris</h5>
                    <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="faktor_id" id="comment-faktor-id">
                    <input type="hidden" name="kodebpr" value="<?= esc($kodebpr ?? '') ?>">
                    <div class="form-group">
                        <label>Komentar Saat Ini:</label>
                        <ul id="komentarLamaList" style="list-style-type:none;padding-left:0;"><li>Memuat komentar...</li></ul>
                    </div>
                    <?php if (($userInGroupAdmin??false)||($userInGroupDekom??false)||($userInGroupDireksi??false)||($userInGroupPE??false)||($userInGroupDekom2??false)||($userInGroupDireksi2??false)): ?>
                    <input type="hidden" name="fullname" value="<?= esc($fullname ?? '') ?>">
                    <div class="form-group">
                        <label for="komentar">Tambahkan Komentar Baru:</label>
                        <textarea class="form-control" name="komentar" id="komentar" style="height:100px"></textarea>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" name="tambahKomentar" class="btn btn-primary">Simpan Komentar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalSyncConfirm">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-sync-alt mr-1"></i>
                    Sinkronisasi Data Periode Sebelumnya
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div id="sync-modal-info" class="alert alert-info mb-3" style="font-size:.9em;line-height:1.5;"></div>
 
                <div class="sync-checkbox-row">
                    <input type="checkbox" id="sync-force-overwrite" class="sync-checkbox">
                    <label for="sync-force-overwrite" class="sync-checkbox-label">
                        <strong>Timpa data yang sudah ada</strong>
                        <span class="sync-checkbox-hint">
                            Jika tidak dicentang, hanya faktor yang belum terisi yang akan disalin.
                        </span>
                    </label>
                </div>
 
                <div id="sync-progress" style="display:none;" class="sync-progress-area">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    <span>Sedang menyinkronisasi data...</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm"
                        data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-warning btn-sm" id="btn-sync-execute">
                    <i class="fas fa-sync-alt"></i> Mulai Sinkronisasi
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// ===== CONSTANTS (inline, no extra requests) =====
const GLOBAL_KODEBPR    = '<?= esc($kodebpr ?? '', 'js') ?>';
const GLOBAL_PERIODE_ID = '<?= esc($activePeriodeId ?? '', 'js') ?>';
const GLOBAL_USER_ID    = '<?= esc($currentUserId ?? '', 'js') ?>';
const BASE_URL          = '<?= base_url() ?>';
const PENILAIAN_CONFIG  = <?= json_encode($penilaianConfig ?? [], JSON_UNESCAPED_UNICODE) ?>;
const CSRF_TOKEN_NAME   = '<?= csrf_token() ?>';
const CSRF_HASH         = '<?= csrf_hash() ?>';
const USER_IN_GROUP_ADMIN = <?= json_encode($userInGroupAdmin ?? false) ?>;
const USER_IN_GROUP_PE    = <?= json_encode($userInGroupPE ?? false) ?>;
const CAN_EDIT = USER_IN_GROUP_ADMIN || USER_IN_GROUP_PE;

// ===== UTILITIES =====
const formatCurrency = (v) => new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',minimumFractionDigits:0,maximumFractionDigits:0}).format(v);

const getInputValue = (id) => {
    const el = document.getElementById(id);
    if (!el) return 0;
    return parseFloat(el.value) || 0;
};

const setHiddenValue = (id, v) => { const el = document.getElementById(id); if (el) el.value = v.toFixed(2); };
const updateDisplay  = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };

const escapeHtml = (() => {
    const map = {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'};
    return (t) => String(t).replace(/[&<>"']/g, m => map[m]);
})();

const formatDateTime = (ds) => {
    if (!ds) return '-';
    const d = new Date(ds);
    return [String(d.getDate()).padStart(2,'0'),String(d.getMonth()+1).padStart(2,'0'),d.getFullYear()].join('/')
        + ' ' + [String(d.getHours()).padStart(2,'0'),String(d.getMinutes()).padStart(2,'0'),String(d.getSeconds()).padStart(2,'0')].join(':');
};

// ===== TOAST (single instance, reused) =====
let _toastEl = null;
function showToast(title, message, type) {
    if (!_toastEl) {
        _toastEl = document.createElement('div');
        _toastEl.className = 'toast-notification';
        document.body.appendChild(_toastEl);
    }
    _toastEl.className = 'toast-notification ' + (type === 'success' ? 'bg-success' : 'bg-danger');
    _toastEl.innerHTML = `<i class="fas fa-${type==='success'?'check':'exclamation'}-circle"></i><div><strong>${title}</strong><br><span>${message}</span></div>`;
    _toastEl.classList.add('show');
    clearTimeout(_toastEl._timer);
    _toastEl._timer = setTimeout(() => _toastEl.classList.remove('show'), 3000);
}

// ===== RATING OPTIONS =====
function generateRatingOptions(faktorId, containerSelector) {
    const config = PENILAIAN_CONFIG[faktorId] || PENILAIAN_CONFIG['default'] || {};
    const container = document.querySelector(containerSelector);
    if (!container) return config;
    const frag = document.createDocumentFragment();
    for (let i = 1; i <= 5; i++) {
        const desc = (config.descriptions && config.descriptions[i]) || 'Tidak ada deskripsi';
        const a = document.createElement('a');
        a.href = '#';
        a.className = 'list-group-item list-group-item-action py-2 px-3';
        a.dataset.value = i;
        a.dataset.description = desc;
        a.innerHTML = `<h6 class="mb-1"><b>Nilai ${i}</b></h6><p class="mb-0 small">${desc}</p>`;
        frag.appendChild(a);
    }
    container.innerHTML = '';
    container.appendChild(frag);
    return config;
}

function refreshApprovalStatus() {
    fetch(BASE_URL + '/Likuiditasinheren/getApprovalStatus', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(r => {
        if (r.status !== 'success') return;

        const btnApprove   = document.getElementById('btn-approve-semua');
        const btnUnapprove = document.getElementById('btn-unapprove-semua');

        if (btnApprove) {
            btnApprove.classList.toggle('disabled-btn', !r.can_approve);
        }
        if (btnUnapprove) {
            btnUnapprove.classList.toggle('disabled-btn', !r.all_approved);
        }
    })
    .catch(() => {});
}

function updateCatatanInfo(config, prefix) {
    const text = document.getElementById(`${prefix}-catatan-text`);
    const info = document.getElementById(`${prefix}-catatan-info`);
    if (!text || !info) return;
    let html = '';
    if (config.threshold) html += `<strong>Threshold:</strong> ${config.threshold}<br>`;
    if (config.catatan)   html += `<strong>Catatan:</strong> ${config.catatan}`;
    text.innerHTML = html;
    info.style.display = html ? '' : 'none';
}

function handleRatingSelection(value, description, prefix) {
    const pk = document.getElementById(`${prefix}-penilaiankredit`);
    const sv = document.getElementById(`${prefix}SelectedValue`);
    const pp = document.getElementById(`${prefix}-penjelasanpenilaian`);
    if (pk) pk.value = value;
    if (sv) sv.textContent = 'Nilai ' + value;
    if (pp) pp.value = description;
}

// ===== CALCULATOR =====
function calculateRiskKalkulator() {
    const g = getInputValue;
    const totalaset=g('totalaset'),kas=g('kas'),girobanklain=g('girobanklain'),tabunganbanklain=g('tabunganbanklain');
    const kewajibansegera=g('kewajibansegera'),tabungandpk=g('tabungandpk'),depositodpk=g('depositodpk');
    const tabunganabp=g('tabunganabp'),depositoabp=g('depositoabp'),pinjamanditerima=g('pinjamanditerima');
    const kreditkyd=g('kreditkyd'),penabung25deposan=g('penabung25deposan'),totalpendanaan=g('totalpendanaan');
    const dpkdiataslps=g('dpkdiataslps'),pinjamananmungkinditarik=g('pinjamananmungkinditarik');

    const asetlikuid        = kas+girobanklain+tabunganbanklain;
    const kewajibanlancar   = kewajibansegera+tabungandpk+depositodpk;
    // const kewajibanlancar   = kewajibansegera+tabungandpk+depositodpk+tabunganabp+depositoabp+pinjamanditerima;
    const totaldpk          = tabungandpk+depositodpk;
    const transaksibpr      = tabunganabp+depositoabp;
    const pendanaannoninti  = dpkdiataslps+transaksibpr+pinjamananmungkinditarik;

    const r1 = totalaset       > 0 ? (asetlikuid/totalaset)*100       : 0;
    const r2 = kewajibanlancar > 0 ? (asetlikuid/kewajibanlancar)*100 : 0;
    const r3 = totaldpk        > 0 ? (kreditkyd/totaldpk)*100         : 0;
    const r4 = totaldpk        > 0 ? (penabung25deposan/totaldpk)*100 : 0;
    const r5 = totalpendanaan  > 0 ? (pendanaannoninti/totalpendanaan)*100 : 0;

    setHiddenValue('asetlikuid_hidden', asetlikuid);
    setHiddenValue('kewajibanlancar_hidden', kewajibanlancar);
    setHiddenValue('totaldpk_hidden', totaldpk);
    setHiddenValue('transaksibpr_hidden', transaksibpr);
    setHiddenValue('pendanaannoninti_hidden', pendanaannoninti);
    setHiddenValue('rasioasetlikuidtotalaset_submit', r1);
    setHiddenValue('rasioasetlikuidkewajiban_submit', r2);
    setHiddenValue('rasiokreditterhadapdpk_submit', r3);
    setHiddenValue('rasio25deposan_submit', r4);
    setHiddenValue('rasiononinti_submit', r5);

    updateDisplay('asetlikuid',            formatCurrency(asetlikuid));
    updateDisplay('kewajibanlancar',       formatCurrency(kewajibanlancar));
    updateDisplay('totaldpk_display',      formatCurrency(totaldpk));
    updateDisplay('transaksibpr',          formatCurrency(transaksibpr));
    updateDisplay('pendanaannoninti',      formatCurrency(pendanaannoninti));
    updateDisplay('rasioasetlikuidtotalaset', r1.toFixed(2)+'%');
    updateDisplay('rasioasetlikuidkewajiban', r2.toFixed(2)+'%');
    updateDisplay('rasiokreditterhadapdpk',   r3.toFixed(2)+'%');
    updateDisplay('rasio25deposan',            r4.toFixed(2)+'%');
    updateDisplay('rasiononinti',              r5.toFixed(2)+'%');

    const btn = document.getElementById('btnInsertRasio');
    if (btn) btn.style.display = (kas>0||girobanklain>0||tabunganbanklain>0) ? 'block' : 'none';
    const dr = document.getElementById('detailResult');
    if (dr) dr.style.display = 'block';
    const rr = document.getElementById('riskResult');
    if (rr) { rr.innerHTML = '<div style="font-size:18px;margin-bottom:10px;color:#28a745;">✓ Perhitungan Selesai</div><div style="font-size:14px;">Silakan lihat detail analisis di bawah</div>'; rr.style.display='block'; }
}

// ===== INSERT RASIO (debounced) =====
let _insertBusy = false;
function insertRasioToKertasKerja() {
    if (_insertBusy) return;
    const get = (id) => { const el=document.getElementById(id); return el?el.value:''; };
    const rasios = {
        rasioasetlikuidtotalaset: get('rasioasetlikuidtotalaset_submit'),
        rasioasetlikuidkewajiban: get('rasioasetlikuidkewajiban_submit'),
        rasiokreditterhadapdpk:   get('rasiokreditterhadapdpk_submit'),
        rasio25deposan:           get('rasio25deposan_submit'),
        rasiononinti:             get('rasiononinti_submit'),
    };
    if (!Object.values(rasios).some(v=>v)) { showToast('Error','Silakan hitung terlebih dahulu.','error'); return; }

    const fields = ['totalaset','kas','girobanklain','tabunganbanklain','kewajibansegera','tabungandpk','depositodpk',
                    'tabunganabp','depositoabp','pinjamanditerima','kreditkyd','penabung25deposan','totalpendanaan',
                    'dpkdiataslps','pinjamananmungkinditarik'];
    const postData = {...rasios, [CSRF_TOKEN_NAME]: CSRF_HASH};
    fields.forEach(f => { const el=document.getElementById(f); postData[f] = el?el.value:0; });

    const btn = document.getElementById('btnInsertRasio');
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...'; btn.disabled = true;
    _insertBusy = true;

    fetch(BASE_URL+'/Likuiditasinheren/insertRasioToKertasKerja', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
        body: new URLSearchParams(postData)
    })
    .then(r=>r.json())
    .then(result => {
        btn.innerHTML=orig; btn.disabled=false; _insertBusy=false;
        if (result.success) { showToast('Berhasil',result.message,'success'); setTimeout(()=>location.reload(),1500); }
        else showToast('Error',result.message||'Gagal memasukkan rasio','error');
    })
    .catch(()=>{ btn.innerHTML=orig; btn.disabled=false; _insertBusy=false; showToast('Error','Terjadi kesalahan. Silakan coba lagi.','error'); });
}

// ===== RENDER FACTORS =====
function renderFactors(factors) {
    const container = document.getElementById('factors-container');
    const parts = [];
    factors.forEach((factor, index) => {
        const nilai = factor.nilai;
        const hasData = nilai && nilai.penilaiankredit;
        parts.push(`
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">${index+1}. ${escapeHtml(factor.title)}</h6>
                <div class="d-flex align-items-center flex-nowrap" style="gap:4px;">
                    ${hasData ? `<span class="badge badge-info" style="white-space:nowrap;"><i class="fas fa-check"></i> Peringkat penilaian: ${nilai.penilaiankredit}</span>` : '<span class="badge badge-secondary" style="white-space:nowrap;">Belum dinilai</span>'}
                    ${factor.type==='single' ? renderSingleButtons(factor.faktor_id, nilai) : ''}
                    ${factor.type==='category' ? renderCategoryButtons(factor.faktor_id, nilai) : ''}
                </div>
            </div>
            <div class="card-body">${factor.type==='category' ? renderCategoryChildren(factor) : ''}</div>
        </div>`);
    });
    container.innerHTML = parts.join('');
    attachEventHandlers();
    setTimeout(pollUnreadCounts, 300);
}

function renderSingleButtons(faktorId, nilai) {
    const hasData = nilai && nilai.penilaiankredit;
    let html = '';
    if (CAN_EDIT) {
        html += hasData
            ? `<button class="btn btn-sm btn-warning btn-edit" data-id="${faktorId}" data-title="Parameter Lainnya" data-penilaiankredit="${nilai.penilaiankredit}" data-keterangan="${escapeHtml(nilai.keterangan||'')}"><i class="fas fa-edit"></i></button>`
            : `<button type="button" class="btn btn-sm btn-success btn-add" data-id="${faktorId}" data-title="Parameter Lainnya"><i class="fas fa-plus"></i></button>`;
    }
    html += `<button type="button" class="btn btn-sm btn-primary position-relative komentar-button" data-faktor-id="${faktorId}"><i class="fas fa-comment"></i><span id="notification-badge-${faktorId}" class="badge badge-danger notification-badge" style="display:none;">0</span></button>`;
    html += renderApprovalButton(faktorId, nilai);
    return html;
}

function renderCategoryButtons(faktorId, nilai) {
    const keterangan = nilai?.keterangan || '';
    let html = '';
    if (CAN_EDIT) {
        html += `<button type="button" class="btn btn-sm btn-warning btn-edit-keterangan" data-id="${faktorId}" data-title="Kategori" data-keterangan="${escapeHtml(keterangan)}"><i class="fa fa-edit"></i></button>`;
    }
    html += `<button type="button" class="btn btn-sm btn-primary position-relative komentar-button" data-faktor-id="${faktorId}"><i class="fas fa-comment"></i><span id="notification-badge-${faktorId}" class="badge badge-danger notification-badge" style="display:none;">0</span></button>`;
    html += renderApprovalButton(faktorId, nilai);
    return html;
}

function renderCategoryChildren(factor) {
    if (!factor.children) return '';
    return factor.children.map((child, idx) => {
        const nilai = child.nilai;
        const hasData = nilai && nilai.penilaiankredit;
        const displayTitle = escapeHtml(child.title).replace(/\\n|\n|&lt;br&gt;/gi,'<br>');
        return `<div class="border-left border-success pl-4 mb-3">
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <h6 class="font-weight-bold">${idx+1}. ${displayTitle}</h6>
                    ${nilai?.rasiokredit ? `<p class="mb-2"><strong>Nilai rasio: ${nilai.rasiokredit}</strong></p>` : ''}
                </div>
                <div class="ml-3 d-flex align-items-center flex-nowrap" style="gap:4px;">
                    ${hasData ? `<span class="badge badge-success" style="white-space:nowrap;"><i class="fas fa-check"></i> Peringkat: ${nilai.penilaiankredit}</span>` : '<span class="badge badge-secondary" style="white-space:nowrap;">Belum dinilai</span>'}
                    ${CAN_EDIT ? (hasData
                        ? `<button class="btn btn-sm btn-warning btn-edit" data-id="${child.faktor_id}" data-title="${escapeHtml(child.title)}" data-penilaiankredit="${nilai.penilaiankredit}" data-keterangan="${escapeHtml(nilai.keterangan||'')}"><i class="fas fa-edit"></i></button>`
                        : `<button type="button" class="btn btn-sm btn-success btn-add" data-id="${child.faktor_id}" data-title="${escapeHtml(child.title)}"><i class="fas fa-plus"></i></button>`) : ''}
                    <button type="button" class="btn btn-sm btn-primary position-relative komentar-button" data-faktor-id="${child.faktor_id}"><i class="fas fa-comment"></i><span id="notification-badge-${child.faktor_id}" class="badge badge-danger notification-badge" style="display:none;">0</span></button>
                    ${renderApprovalButton(child.faktor_id, nilai)}
                </div>
            </div>
        </div>`;
    }).join('');
}

function renderApprovalButton(faktorId, nilai) {
    <?php if (($userInGroupAdmin??false)||($userInGroupDireksi??false)||($userInGroupDireksi2??false)): ?>
    if (!nilai || !nilai.penilaiankredit) return '<span class="text-muted ml-2">-</span>';
    const isApproved = nilai.accdir2 == 1;
    return `<form action="${BASE_URL}/Likuiditasinheren/${isApproved?'unapprovedir2':'accdir2'}" method="POST" style="display:inline-flex;margin:0;" class="form-approval">
        <input type="hidden" name="${CSRF_TOKEN_NAME}" value="${CSRF_HASH}">
        <input type="hidden" name="faktor1id" value="${faktorId}">
        <input type="hidden" name="kodebpr" value="${GLOBAL_KODEBPR}">
        <input type="hidden" name="periode_id" value="${GLOBAL_PERIODE_ID}">
        <button type="submit" class="btn btn-sm ${isApproved?'btn-success':'btn-dark'}"><i class="fas fa-${isApproved?'check':'times'}-circle"></i></button>
    </form>`;
    <?php else: ?>
    return '';
    <?php endif; ?>
}

// ===== BADGE UPDATES =====
function updateBadge(faktorId, count) {
    const badge = document.getElementById(`notification-badge-${faktorId}`);
    if (!badge) return;
    badge.textContent = count;
    badge.style.display = count > 0 ? '' : 'none';
}

// ===== UNREAD COUNTS (batched) =====
let _pollTimer = null;
function pollUnreadCounts() {
    fetch(`${BASE_URL}/Likuiditasinheren/getAllUnreadCounts`, {headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json())
    .then(response => {
        if (response.status === 'success') {
            document.querySelectorAll('.notification-badge').forEach(b=>b.style.display='none');
            Object.keys(response.counts||{}).forEach(id => updateBadge(id, response.counts[id]));
        }
    }).catch(()=>{});
}

function updateBadgePeringkat13(peringkat) {
    const labels = {1:'Sangat Rendah',2:'Rendah',3:'Sedang',4:'Tinggi',5:'Sangat Tinggi'};
    const classes = {1:'badge-info',2:'badge-success',3:'badge-warning',4:'badge-danger',5:'badge-dark'};
    const badge = document.querySelector('.d-flex.justify-content-between .text-center .mt-2 span.badge:first-child');
    if (badge) {
        badge.className = `badge badge-lg ${classes[peringkat]||'badge-secondary'}`;
        badge.innerHTML = `<i class="fas fa-check-circle"></i> Peringkat penilaian: ${peringkat} [${labels[peringkat]||'N/A'}]`;
    }
}

function updateMainApprovalBadge(allApproved, approvedAt = null) {
    const badge = document.getElementById('approval-badge-main');
    if (!badge) return;
    badge.className = `badge ${allApproved ? 'badge-success' : 'badge-secondary'}`;
    badge.style.fontSize = '14px';
    if (allApproved) {
        badge.innerHTML = `Disetujui oleh <strong>Direktur Utama</strong><br>${approvedAt ? formatDateTime(approvedAt) : '-'}`;
    } else {
        badge.innerHTML = 'Belum Disetujui Seluruhnya<br>Oleh Direktur Utama';
    }
}

function refreshFactorsData() {
    fetch(BASE_URL+'/Likuiditasinheren/getFactorsData', {headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json())
    .then(response => {
        const sk = document.getElementById('factors-skeleton');
        const ct = document.getElementById('factors-container');
        if (response.status==='success') {
            renderFactors(response.data);
            if (sk) sk.style.display = 'none';
            if (ct) ct.style.display = 'block';
        }
    })
    .catch(()=>{
        const sk = document.getElementById('factors-skeleton');
        if (sk) sk.innerHTML = '<div class="alert alert-danger">Gagal memuat data. <a href="#" onclick="location.reload()">Refresh</a></div>';
    });
}

// ===== AJAX HELPER =====
function ajaxPost(url, data) {
    return fetch(BASE_URL + url, {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
        body: new URLSearchParams({...data, [CSRF_TOKEN_NAME]: CSRF_HASH})
    }).then(r => r.json());
}

// ===== EVENT HANDLERS (delegated) =====
function attachEventHandlers() {
    // Edit buttons
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id, title = this.dataset.title;
            const penilaian = this.dataset.penilaiankredit, ket = this.dataset.keterangan;
            document.getElementById('edit-faktor-id').value = id;
            document.getElementById('edit-parameterpenilaian').value = title;
            document.getElementById('edit-penilaiankredit').value = penilaian;
            document.getElementById('edit-keterangan').value = ket;
            const config = generateRatingOptions(id, '#editRatingList');
            updateCatatanInfo(config, 'edit');
            document.querySelectorAll('#editRatingList .list-group-item').forEach(item => {
                if (item.dataset.value == penilaian) { item.classList.add('active'); document.getElementById('editSelectedValue').textContent = 'Nilai '+penilaian; }
            });
            $('#modalUbah').modal('show');
        });
    });

    // Edit keterangan buttons
    document.querySelectorAll('.btn-edit-keterangan').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit-faktor-id-kesimpulan').value = this.dataset.id;
            document.getElementById('edit-parameterpenilaian-kesimpulan').value = this.dataset.title;
            document.getElementById('edit-keterangan-kesimpulan').value = this.dataset.keterangan;
            $('#modalUbahkesimpulan').modal('show');
        });
    });

    // Add buttons
    document.querySelectorAll('.btn-add').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('add-faktor-id').value = this.dataset.id;
            document.getElementById('add-parameterpenilaian').value = this.dataset.title;
            ['add-penilaiankredit','add-penjelasanpenilaian','add-keterangan'].forEach(id => { const el=document.getElementById(id); if(el) el.value=''; });
            document.getElementById('addSelectedValue').textContent = 'Belum ada';
            const config = generateRatingOptions(this.dataset.id, '#addRatingList');
            updateCatatanInfo(config, 'add');
            $('#modaltambahNilai').modal('show');
        });
    });

    // Approval forms (AJAX)
    document.querySelectorAll('.form-approval').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('button');
            const orig = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; btn.disabled = true;
            fetch(this.action, {method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'},body:new FormData(this)})
            .then(() => {
                const isApproved = this.action.includes('accdir2');
                btn.className = `btn btn-sm ${isApproved?'btn-success':'btn-dark'}`;
                btn.innerHTML = `<i class="fas fa-${isApproved?'check':'times'}-circle"></i>`;
                this.action = this.action.replace(/(accdir2|unapprovedir2)/, isApproved?'unapprovedir2':'accdir2');
                btn.disabled = false;
                showToast('Berhasil','Status approval diubah','success');
            })
            .catch(() => { btn.innerHTML=orig; btn.disabled=false; showToast('Error','Gagal mengubah status','error'); });
        });
    });
}

let _syncPrevInfo       = null;
let _syncCurrentHasData = false;
let _syncChecked        = false;
 
function initSyncModule() {
    const syncBar        = document.getElementById('sync-bar');
    const syncBarText    = document.getElementById('sync-bar-text');
    const btnTrigger     = document.getElementById('btn-sync-trigger');
    const syncNodataBar  = document.getElementById('sync-nodata-bar');
    const syncNodataText = document.getElementById('sync-nodata-text');
    const syncModalInfo  = document.getElementById('sync-modal-info');
    const syncProgress   = document.getElementById('sync-progress');
    const forceChk       = document.getElementById('sync-force-overwrite');
    const btnExecute     = document.getElementById('btn-sync-execute');
 
    /* Jika HTML sync belum dipasang → skip tanpa error */
    if (!syncBar || !btnTrigger || !btnExecute) return;
 
    /* ── STEP 1: check ringan — COUNT query, 1x per load ── */
    function doCheck() {
        if (_syncChecked) return;
        _syncChecked = true;
 
        ajaxPost('/Likuiditasinheren/checkPrevPeriode', {})
            .then(r => {
                if (r.status === 'available') {
                    _syncPrevInfo       = r.prev_info;
                    _syncCurrentHasData = !!r.current_has_data;
                    const pi = r.prev_info;
                    syncBarText.innerHTML =
                        '<i class="fas fa-info-circle mr-1"></i>' +
                        'Data <strong>Semester ' + pi.semester + ' Tahun ' + pi.tahun + '</strong> ' +
                        'tersedia &mdash; ' + pi.faktor_count + ' faktor' +
                        (pi.has_kalkulator ? ' + kalkulator' : '') + '.' +
                        (_syncCurrentHasData
                            ? ' <em class="text-muted">Periode aktif sudah ada sebagian data.</em>'
                            : ' Periode aktif belum ada data.');
                    syncBar.style.display = '';
 
                } else if (r.status === 'no_data' || r.status === 'no_prev') {
                    if (syncNodataText) syncNodataText.textContent = r.message || 'Tidak ada data periode sebelumnya.';
                    if (syncNodataBar) syncNodataBar.style.display = '';
                }
                /* status error → silent */
            })
            .catch(() => { /* network error → silent */ });
    }
 
    /* ── STEP 2: buka modal konfirmasi (jQuery) ── */
    btnTrigger.addEventListener('click', function () {
        if (!_syncPrevInfo) return;
        const pi = _syncPrevInfo;
 
        let info =
            '<i class="fas fa-calendar-alt mr-1"></i>' +
            'Menyalin dari <strong>Semester ' + pi.semester + ' Tahun ' + pi.tahun + '</strong><br>' +
            '<span class="text-muted" style="font-size:.9em;">' +
            pi.faktor_count + ' faktor penilaian' +
            (pi.has_kalkulator ? ' + data kalkulator likuiditas' : '') +
            ' akan disalin.</span>';
 
        if (_syncCurrentHasData) {
            info +=
                '<div class="alert alert-warning mt-2 mb-0 py-1 px-2" style="font-size:.83em;">' +
                '<i class="fas fa-exclamation-triangle mr-1"></i>' +
                'Periode aktif sudah memiliki sebagian data. ' +
                'Centang <em>"Timpa data"</em> untuk mengganti semua.' +
                '</div>';
        }
 
        if (syncModalInfo) syncModalInfo.innerHTML = info;
        if (forceChk)      forceChk.checked = false;
        if (syncProgress)  syncProgress.style.display = 'none';
 
        btnExecute.disabled = false;
        btnExecute.innerHTML = '<i class="fas fa-sync-alt"></i> Mulai Sinkronisasi';
        $('#modalSyncConfirm').modal('show');
    });
 
    /* ── STEP 3: eksekusi sync ── */
    btnExecute.addEventListener('click', function () {
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
        if (syncProgress) syncProgress.style.display = 'block';
 
        const force = forceChk && forceChk.checked ? 1 : 0;
 
        ajaxPost('/Likuiditasinheren/syncFromPrevPeriode', { force_overwrite: force })
            .then(r => {
                if (syncProgress) syncProgress.style.display = 'none';
                $('#modalSyncConfirm').modal('hide');
 
                if (r.status === 'success') {
                    showToast('Sinkronisasi Berhasil', r.message, 'success');
                    syncBar.style.display = 'none';
                    refreshFactorsData();
                    if (r.peringkat13) updateBadgePeringkat13(r.peringkat13);
 
                } else if (r.status === 'no_change') {
                    showToast('Tidak Ada Perubahan', r.message, 'info');
                    if (!force) {
                        if (forceChk) forceChk.checked = true;
                        $('#modalSyncConfirm').modal('show');
                    }
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-sync-alt"></i> Mulai Sinkronisasi';
 
                } else {
                    showToast('Gagal', r.message || 'Terjadi kesalahan', 'error');
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

    setTimeout(doCheck, 900);
}

document.addEventListener('DOMContentLoaded', function() {
    refreshFactorsData();
    refreshApprovalStatus();
    initSyncModule();

    pollUnreadCounts();
    setInterval(pollUnreadCounts, 10000);

    document.addEventListener('click', function(e) {
        const item = e.target.closest('#addRatingList .list-group-item, #editRatingList .list-group-item');
        if (!item) return;
        e.preventDefault();
        const listId  = item.closest('[id]').id;
        const prefix  = listId.startsWith('add') ? 'add' : 'edit';
        item.closest('.list-group').querySelectorAll('.list-group-item').forEach(i=>i.classList.remove('active'));
        item.classList.add('active');
        handleRatingSelection(item.dataset.value, item.dataset.description, prefix);
    });

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('[data-bs-toggle="collapse"]');
        if (!btn) return;
        const targetSel = btn.getAttribute('data-bs-target');
        if (!targetSel) return;
        const targetEl = document.querySelector(targetSel);
        if (!targetEl) return;
        e.preventDefault();
        targetEl.classList.toggle('show');
    });

    // Komentar button — delegated
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.komentar-button');
        if (!btn) return;
        const faktorId = btn.dataset.faktorId;
        document.getElementById('comment-faktor-id').value = faktorId;
        const list = document.getElementById('komentarLamaList');
        list.innerHTML = '<li>Memuat komentar...</li>';
        fetch(`${BASE_URL}/Likuiditasinheren/getKomentarByFaktorId/${faktorId}?kodebpr=${GLOBAL_KODEBPR}&periode_id=${GLOBAL_PERIODE_ID}`, {headers:{'X-Requested-With':'XMLHttpRequest'}})
        .then(r=>r.json())
        .then(data => {
            list.innerHTML = data.length > 0
                ? data.map(k=>`<li>${escapeHtml(k.komentar)} - (${escapeHtml(k.fullname)} - ${k.created_at})</li>`).join('')
                : '<li>Tidak ada komentar.</li>';
        });
        ajaxPost('/Likuiditasinheren/markUserCommentsAsRead', {faktor_id:faktorId, kodebpr:GLOBAL_KODEBPR, periode_id:GLOBAL_PERIODE_ID})
        .then(r => { if(r.status==='success') updateBadge(faktorId, 0); }).catch(()=>{});
        $('#modaltambahKomentar').modal('show');
    });

    // Form: ubah
    document.querySelector('form[action*="Likuiditasinheren/ubah"]')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const faktorId  = this.querySelector('[name="faktor1id"]').value;
        const penilaian = this.querySelector('[name="penilaiankredit"]').value;
        const keterangan = this.querySelector('[name="keterangan"]').value;
        if (!faktorId) return showToast('Error','Faktor ID tidak ditemukan','error');
        if (!penilaian && faktorId != 115) return showToast('Error','Pilih tingkat risiko terlebih dahulu','error');
        if (!keterangan.trim()) return showToast('Error','Keterangan harus diisi','error');
        const btn = this.querySelector('button[type="submit"]');
        const orig = btn.innerHTML; btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Menyimpan...'; btn.disabled=true;
        fetch(this.action, {method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'},body:new FormData(this)})
        .then(r=>r.json())
        .then(response => {
            $('#modalUbah').modal('hide'); showToast('Berhasil','Data berhasil diubah','success');
            if (response.peringkat13) updateBadgePeringkat13(response.peringkat13);
            updateMainApprovalBadge(false); refreshFactorsData();
            btn.innerHTML=orig; btn.disabled=false; this.reset();
        })
        .catch(() => { btn.innerHTML=orig; btn.disabled=false; showToast('Error','Gagal menyimpan data','error'); });
    });

    // Form: ubahkesimpulan
    document.querySelector('form[action*="Likuiditasinheren/ubahkesimpulan"]')?.addEventListener('submit', function(e) {
        e.preventDefault();
        if (!this.querySelector('[name="faktor1id"]').value) return showToast('Error','Faktor ID tidak ditemukan','error');
        if (!this.querySelector('[name="keterangan"]').value.trim()) return showToast('Error','Keterangan harus diisi','error');
        const btn = this.querySelector('button[type="submit"]');
        const orig = btn.innerHTML; btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Menyimpan...'; btn.disabled=true;
        fetch(this.action, {method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'},body:new FormData(this)})
        .then(r=>r.json())
        .then(() => { $('#modalUbahkesimpulan').modal('hide'); showToast('Berhasil','Keterangan berhasil diubah','success'); updateMainApprovalBadge(false); refreshFactorsData(); btn.innerHTML=orig; btn.disabled=false; this.reset(); })
        .catch(() => { btn.innerHTML=orig; btn.disabled=false; showToast('Error','Gagal menyimpan keterangan','error'); });
    });

    // Form: tambahNilai
    document.querySelector('form[action*="Likuiditasinheren/tambahNilai"]')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const penilaian = this.querySelector('[name="penilaiankredit"]').value;
        const keterangan = this.querySelector('[name="keterangan"]').value;
        if (!this.querySelector('[name="faktor_id"]').value) return showToast('Error','Faktor ID tidak ditemukan','error');
        if (!penilaian) return showToast('Error','Pilih tingkat risiko terlebih dahulu','error');
        if (!keterangan.trim()) return showToast('Error','Keterangan harus diisi','error');
        const btn = this.querySelector('button[type="submit"]');
        const orig = btn.innerHTML; btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Menyimpan...'; btn.disabled=true;
        fetch(this.action, {method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'},body:new FormData(this)})
        .then(r=>r.json())
        .then(response => {
            $('#modaltambahNilai').modal('hide'); showToast('Berhasil','Nilai berhasil ditambahkan','success');
            if (response.peringkat13) updateBadgePeringkat13(response.peringkat13);
            updateMainApprovalBadge(false); refreshFactorsData();
            btn.innerHTML=orig; btn.disabled=false; this.reset();
            document.getElementById('addSelectedValue').textContent = 'Belum ada';
        })
        .catch(() => { btn.innerHTML=orig; btn.disabled=false; showToast('Error','Gagal menambahkan nilai','error'); });
    });

    // Form: tambahKomentar
    document.querySelector('form[action*="Likuiditasinheren/tambahKomentar"]')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const komentar = this.querySelector('[name="komentar"]').value;
        if (!komentar.trim()) return showToast('Error','Komentar tidak boleh kosong','error');
        const btn = this.querySelector('button[type="submit"]');
        const orig = btn.innerHTML; btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Menyimpan...'; btn.disabled=true;
        const faktorId = this.querySelector('[name="faktor_id"]').value;
        fetch(this.action, {method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'},body:new FormData(this)})
        .then(() => {
            this.querySelector('[name="komentar"]').value='';
            fetch(`${BASE_URL}/Likuiditasinheren/getKomentarByFaktorId/${faktorId}`, {headers:{'X-Requested-With':'XMLHttpRequest'}})
            .then(r=>r.json()).then(data => {
                const list = document.getElementById('komentarLamaList');
                list.innerHTML = data.length > 0 ? data.map(k=>`<li>${escapeHtml(k.komentar)} - (${escapeHtml(k.fullname)} - ${k.created_at})</li>`).join('') : '<li>Tidak ada komentar.</li>';
            });
            showToast('Berhasil','Komentar berhasil ditambahkan','success');
            btn.innerHTML=orig; btn.disabled=false;
            pollUnreadCounts();
        })
        .catch(() => { btn.innerHTML=orig; btn.disabled=false; showToast('Error','Gagal menambahkan komentar','error'); });
    });

    // Approve/Unapprove semua
    document.querySelector('a[href*="approveSemua"]')?.addEventListener('click', function(e) {
        e.preventDefault();
        if (!confirm('Apakah Anda yakin ingin melakukan approval?')) return;
        this.classList.add('disabled');
        fetch(this.href, {headers:{'X-Requested-With':'XMLHttpRequest'}})
        .then(() => { showToast('Berhasil','Semua data berhasil disetujui','success'); updateMainApprovalBadge(true, new Date().toISOString()); refreshFactorsData(); this.classList.remove('disabled'); })
        .catch(() => { showToast('Error','Gagal melakukan approval','error'); this.classList.remove('disabled'); });
    });

    document.querySelector('a[href*="unapproveSemua"]')?.addEventListener('click', function(e) {
        e.preventDefault();
        if (!confirm('Batalkan semua approval?')) return;
        this.classList.add('disabled');
        fetch(this.href, {headers:{'X-Requested-With':'XMLHttpRequest'}})
        .then(() => { showToast('Berhasil','Semua approval berhasil dibatalkan','success'); updateMainApprovalBadge(false); refreshFactorsData(); this.classList.remove('disabled'); })
        .catch(() => { showToast('Error','Gagal membatalkan approval','error'); this.classList.remove('disabled'); });
    });

    // Peringkat13 manual
    document.getElementById('btnSimpanPeringkat13')?.addEventListener('click', function() {
        const peringkat = document.getElementById('peringkat13_manual').value;
        if (!peringkat) return showToast('Error','Pilih peringkat terlebih dahulu','error');
        const orig = this.innerHTML; this.innerHTML='<i class="fas fa-spinner fa-spin"></i> Menyimpan...'; this.disabled=true;
        ajaxPost('/Likuiditasinheren/ubahPeringkat13Manual', {penilaiankredit: peringkat})
        .then(r => {
            if (r.status==='success') { showToast('Berhasil','Peringkat berhasil diubah','success'); setTimeout(()=>location.reload(),1000); }
            else { showToast('Error', r.message, 'error'); this.innerHTML=orig; this.disabled=false; }
        }).catch(() => { this.innerHTML=orig; this.disabled=false; showToast('Error','Gagal mengubah peringkat','error'); });
    });

    document.getElementById('btnResetPeringkat13')?.addEventListener('click', function() {
        if (!confirm('Kembalikan ke perhitungan otomatis?')) return;
        const orig = this.innerHTML; this.innerHTML='<i class="fas fa-spinner fa-spin"></i>'; this.disabled=true;
        ajaxPost('/Likuiditasinheren/resetPeringkat13Auto', {})
        .then(r => {
            if (r.status==='success') { showToast('Berhasil','Peringkat dikembalikan ke perhitungan otomatis','success'); setTimeout(()=>location.reload(),1000); }
            else { showToast('Error', r.message, 'error'); this.innerHTML=orig; this.disabled=false; }
        }).catch(() => { this.innerHTML=orig; this.disabled=false; showToast('Error','Gagal reset peringkat','error'); });
    });

    document.getElementById('btn-approve-semua')?.addEventListener('click', function(e) {
        e.preventDefault();
        if (this.classList.contains('disabled-btn')) return;
        if (!confirm('Apakah Anda yakin ingin melakukan approval?')) return;
        this.classList.add('disabled-btn');
        fetch(this.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(() => {
            showToast('Berhasil', 'Semua data berhasil disetujui', 'success');
            updateMainApprovalBadge(true, new Date().toISOString());
            refreshFactorsData();
            refreshApprovalStatus();
        })
        .catch(() => {
            showToast('Error', 'Gagal melakukan approval', 'error');
            this.classList.remove('disabled-btn');
        });
    });

    document.getElementById('btn-unapprove-semua')?.addEventListener('click', function(e) {
        e.preventDefault();
        if (this.classList.contains('disabled-btn')) return;
        if (!confirm('Batalkan semua approval?')) return;
        this.classList.add('disabled-btn');
        fetch(this.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(() => {
            showToast('Berhasil', 'Semua approval berhasil dibatalkan', 'success');
            updateMainApprovalBadge(false);
            refreshFactorsData();
            refreshApprovalStatus();
        })
        .catch(() => {
            showToast('Error', 'Gagal membatalkan approval', 'error');
            this.classList.remove('disabled-btn');
        });
    });

    // Calculator button
    document.getElementById('btnHitung')?.addEventListener('click', calculateRiskKalkulator);
    document.getElementById('btnInsertRasio')?.addEventListener('click', insertRasioToKertasKerja);

    // Offcanvas
    document.querySelectorAll('[data-bs-toggle="offcanvas"]').forEach(button => {
        button.addEventListener('click', function() {
            const target = document.querySelector(this.getAttribute('data-bs-target'));
            if (target) showOffcanvas(target);
        });
    });
    document.querySelectorAll('[data-bs-dismiss="offcanvas"]').forEach(button => {
        button.addEventListener('click', function() {
            const oc = this.closest('.offcanvas');
            if (oc) hideOffcanvas(oc);
        });
    });

    // Modal reset
    ['modaltambahNilai','modalUbah'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('hidden.bs.modal', function() {
            const prefix = id==='modaltambahNilai'?'add':'edit';
            ['penilaiankredit','penjelasanpenilaian','keterangan'].forEach(f => { const el=document.getElementById(`${prefix}-${f}`); if(el) el.value=''; });
            const sv = document.getElementById(`${prefix}SelectedValue`); if(sv) sv.textContent='Belum ada';
            document.querySelectorAll(`#${prefix}RatingList .list-group-item`).forEach(i=>i.classList.remove('active'));
            const ci = document.getElementById(`${prefix}-catatan-info`); if(ci) ci.style.display='none';
        });
    });
});

// ===== OFFCANVAS HELPERS =====
function showOffcanvas(oc) {
    let bd = document.querySelector('.offcanvas-backdrop');
    if (!bd) { bd=document.createElement('div'); bd.className='offcanvas-backdrop'; document.body.appendChild(bd); }
    oc.classList.add('show'); bd.classList.add('show'); document.body.classList.add('offcanvas-open');
}
function hideOffcanvas(oc) {
    const bd = document.querySelector('.offcanvas-backdrop');
    if (oc) oc.classList.remove('show');
    if (bd) { bd.classList.remove('show'); setTimeout(()=>{ if(bd.parentNode) bd.parentNode.removeChild(bd); },300); }
    document.body.classList.remove('offcanvas-open'); document.body.style.overflow='';
}
</script>

<style>
/* ===== SKELETON LOADER ===== */
.skeleton{background:linear-gradient(90deg,#e0e0e0 25%,#f0f0f0 50%,#e0e0e0 75%);background-size:200% 100%;animation:skeleton-loading 1.2s infinite;border-radius:4px}
.skeleton-text{height:16px;margin-bottom:8px}
@keyframes skeleton-loading{0%{background-position:200% 0}100%{background-position:-200% 0}}

:root{--primary-color:#10598A;--success-color:#28a745;--danger-color:#dc3545;--dark-color:#343a40;--secondary-color:#6c757d;--light-bg:#f8f9fa;--white:#fff;--border-color:#dee2e6;--box-shadow:0 2px 4px rgba(0,0,0,.1);--primary-gradient: linear-gradient(135deg, #141863 0%, #1f2aa8 100%);--box-shadow-hover:0 4px 8px rgba(0,0,0,.2);--border-radius:8px;--transition:all .2s ease-in-out}
body{font-family:Arial,sans-serif;background-color:var(--light-bg);margin:0;padding:0}
.container-fluid{margin-top:30px}
h3,h4{font-weight:700;color:var(--dark-color)}
.alert{font-size:14px;padding:15px;border-radius:var(--border-radius);margin-bottom:20px}
.alert-success{background-color:var(--success-color);color:var(--white)}
.table{width:100%;margin-bottom:1rem;background-color:var(--white);border-radius:var(--border-radius);box-shadow:var(--box-shadow)}
.table th,.table td{padding:12px 15px;text-align:center;border-bottom:1px solid var(--border-color)}
.table th{background-color:#f1f3f5;color:#495057}
.table-striped tbody tr:nth-of-type(odd){background-color:var(--light-bg)}
.table-responsive{overflow-x:auto}
.kolom-parameter{width:600px;text-align:left}.kolom-keterangan{width:600px;max-width:600px;white-space:normal;word-wrap:break-word}
.kolom-aksi,.kolom-rasio,.kolom-penilaian{width:150px;text-align:center;white-space:nowrap}.kolom-aksi{width:100px}
.badge{font-size:14px;padding:5px 10px;border-radius:20px}
.badge-success{background-color:var(--success-color);color:var(--white)}
.badge-secondary{background-color:var(--secondary-color);color:var(--white)}
.badge-primary{background-color:#12131C;color:var(--white)}
.notification-badge{position:absolute;top:-5px;right:-5px;background-color:var(--danger-color);color:var(--white);min-width:20px;height:20px;padding:5px;font-size:10px;line-height:1;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;box-sizing:border-box}
.card{margin-bottom:20px;border-radius:10px;box-shadow:var(--box-shadow)}.card-body{padding:20px}.card-header{background-color:#f1f3f5;border-bottom:2px solid #ddd;font-weight:700}
.approvaldir-card{border-radius:20px;background:var(--white);overflow:hidden;box-shadow:var(--box-shadow)}
.approvaldir-card-body{display:flex;flex-direction:column;justify-content:center;padding:15px;height:100%}
.approval-badge-container{text-align:center;margin-bottom:6px}
.approval-badge{background-color:var(--dark-color);color:var(--white);font-size:.8em;font-weight:500;padding:5px 12px;border-radius:5px;display:inline-block;box-shadow:var(--box-shadow);margin-bottom:7px}
.btn{font-size:14px;padding:8px 20px;border-radius:5px;cursor:pointer;transition:var(--transition)}
.btn:hover{transform:scale(1.05);box-shadow:var(--box-shadow-hover)}.btn-sm{padding:5px 10px;font-size:12px}
.btn-outline-primary{border:1px solid var(--primary-color);background-color:transparent;color:var(--primary-color)}
.btn-outline-primary:hover{background-color:var(--primary-color);color:var(--white);border-color:var(--primary-color)}
.approval-buttons-container{display:flex;justify-content:center;align-items:center;gap:9px}
.approval-btn{flex:1;max-width:50%;padding:8px 13px;font-size:.95em;font-weight:500;border-radius:28px;text-align:center;text-decoration:none;white-space:nowrap;box-shadow:var(--box-shadow);transition:var(--transition)}
.approval-btn-approve{background-color:var(--success-color);border-color:var(--success-color);color:var(--white)}
.approval-btn-approve:hover{background-color:#218838;border-color:#1e7e34;transform:translateY(-1px);box-shadow:var(--box-shadow-hover)}
.approval-btn-reject{background-color:var(--danger-color);border-color:var(--danger-color);color:var(--white)}
.approval-btn-reject:hover{background-color:#c82333;border-color:#bd2130;transform:translateY(-1px);box-shadow:var(--box-shadow-hover)}
input[type="text"],input[type="number"],textarea{width:100%;padding:10px;font-size:14px;border:1px solid #ddd;border-radius:5px;margin-top:5px;margin-bottom:15px;box-sizing:border-box}
.modal-header{background-color:var(--primary-color);color:var(--white);font-size:18px;padding:15px}
.modal-footer .btn{padding:10px 25px;font-size:16px;border-radius:var(--border-radius)}
.disabled-btn,.disabled-card{opacity:.5;pointer-events:none}
.button-bar{display:flex;justify-content:space-between;align-items:center;width:100%;margin:15px 0}
.kpmr-btn{background-color:#10598A;color:#fff;font-weight:600;padding:8px 20px;border-radius:6px;text-decoration:none;transition:all .3s ease}
.kpmr-btn:hover{background-color:#10598A;transform:translateY(-2px);color:#fff}
.btn-primary{background-color:#10598A;color:#fff;border-color:#10598A}
.btn-primary:hover{background-color:#0b5ed7;border-color:#0a58ca}
.btn-close{content:"x";background:none;border:none;padding:.5rem;cursor:pointer;font-size:1.5rem;color:#000;width:2rem;height:2rem;display:flex;align-items:center;justify-content:center}
.offcanvas{position:fixed;bottom:0;top:0;z-index:1045;display:flex;flex-direction:column;max-width:100%;visibility:hidden;background-color:#fff;transition:visibility .3s ease-in-out,transform .3s ease-in-out}
.offcanvas-end{right:0;width:500px;transform:translateX(100%)}
.offcanvas.show{visibility:visible;transform:translateX(0)}
.offcanvas-backdrop{position:fixed;top:0;left:0;z-index:1040;width:100%;height:100%;background-color:rgba(0,0,0,.5);opacity:0;visibility:hidden;transition:opacity .3s ease-in-out,visibility .3s ease-in-out}
.offcanvas-backdrop.show{opacity:1;visibility:visible}
.offcanvas-header{display:flex;align-items:center;justify-content:space-between;padding:1rem 1.5rem;border-bottom:1px solid #e9ecef}
.offcanvas-title{margin:0;font-size:1.25rem;font-weight:600;color:#212529}
.offcanvas-body{flex:1;padding:1.5rem;overflow-y:auto;color:#6c757d}
body.offcanvas-open{overflow:hidden}
.risk-result{padding:15px;border-radius:8px;margin-top:20px;text-align:center;font-weight:700;display:none}
.list-group-item.active{background-color:#007bff!important;border-color:#007bff!important;color:#fff!important}
.list-group-item.active h6,.list-group-item.active p,.list-group-item.active b{color:#fff!important}
.list-group-item:hover{background-color:#f8f9fa;cursor:pointer}
.list-group-item.active:hover{background-color:#0056b3!important}
.list-group-item h6{font-size:1rem;margin-bottom:.25rem}.list-group-item p{font-size:.875rem;line-height:1.4}
.toast-notification{position:fixed;top:20px;right:20px;z-index:9999;padding:15px 20px;border-radius:8px;color:#fff;display:flex;align-items:center;gap:12px;box-shadow:0 4px 12px rgba(0,0,0,.3);transform:translateX(400px);opacity:0;transition:transform .3s ease,opacity .3s ease;min-width:300px}
.toast-notification.show{transform:translateX(0);opacity:1}
.toast-notification i{font-size:24px}
#sync-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    background: #fff3cd;
    border: 1px solid #ffc107;
    border-radius: var(--border-radius);
    color: #856404;
    font-size: .9em;
    padding: 10px 14px;
    margin-bottom: 12px;
}
.sync-bar-text { flex: 1; line-height: 1.4; }
 
#sync-nodata-bar {
    background: #d1ecf1;
    border: 1px solid #bee5eb;
    border-radius: var(--border-radius);
    color: #0c5460;
    font-size: .9em;
    padding: 10px 14px;
    margin-bottom: 12px;
}

.kpmr-btn {
    background: var(--primary-gradient);
    color: #fff;
    font-weight: 600;
    padding: 8px 20px;
    border-radius: 6px;
    text-decoration: none;
    display: inline-block;
    transition: var(--transition);
}

#btn-sync-trigger { flex-shrink: 0; font-size: .85em; padding: 4px 12px; white-space: nowrap; }
 
/* Sync modal: checkbox row */
.sync-checkbox-row { display: flex; align-items: flex-start; gap: 8px; padding: 4px 0; }
.sync-checkbox { width: 16px; height: 16px; margin-top: 3px; flex-shrink: 0; cursor: pointer; }
.sync-checkbox-label { cursor: pointer; line-height: 1.4; }
.sync-checkbox-hint { display: block; font-size: .83em; color: var(--secondary-color, #6c757d); line-height: 1.3; margin-top: 2px; }
 
/* Sync modal: progress */
.sync-progress-area {
    display: flex; align-items: center; gap: 8px;
    background: #f8f9fa; border-radius: 6px;
    padding: 10px 14px; margin-top: 12px;
    font-size: .88em; color: var(--secondary-color, #6c757d);
}
 
/* Responsive */
@media (max-width: 768px) {
    #sync-bar { flex-direction: column; align-items: flex-start; }
    #btn-sync-trigger { width: 100%; }
}
@media (max-width: 576px) {
    #sync-bar { font-size: .83em; }
}
@media(max-width:768px){.offcanvas-end{width:100%}.kolom-parameter,.kolom-keterangan{width:auto;max-width:100%}.approval-btn{font-size:.85em;padding:6px 10px}.table th,.table td{padding:8px 10px;font-size:12px}}
@media(max-width:576px){.container-fluid{margin-top:15px}h3{font-size:1.5rem}h4{font-size:1.2rem}.approval-buttons-container{flex-direction:column;gap:5px}.approval-btn{max-width:100%}}
</style>