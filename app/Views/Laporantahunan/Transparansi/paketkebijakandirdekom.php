<div id="toast-wrap" style="position:fixed;top:20px;right:20px;z-index:99999;min-width:290px;pointer-events:none;">
</div>

<div class="container-fluid">
  <a href="<?= base_url('ShowTransparansi') ?>" class="btn-kembali mb-3">
    <i class="fas fa-arrow-left"></i> Kembali
  </a>

  <?php if (session()->getFlashdata('message')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <button type="button" class="close" data-dismiss="alert">&times;</button>
      <strong><?= esc(session()->getFlashdata('message')) ?></strong>
    </div>
  <?php endif; ?>
  <?php if (session()->getFlashdata('err')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <button type="button" class="close" data-dismiss="alert">&times;</button>
      <strong><?= esc(session()->getFlashdata('err')) ?></strong>
    </div>
  <?php endif; ?>

  <?php
  $row0 = $paketRow ?? [];
  $allAccdekomApproved = !empty($accdekomData) && array_reduce($accdekomData, fn($c, $i) => $c && $i['accdekom'] == 1, true);
  $allAccdirutApproved = !empty($accdirutData) && array_reduce($accdirutData, fn($c, $i) => $c && $i['is_approved'] == 1, true);
  $lastDekomAt = $allAccdekomApproved && !empty($accdekomData)
    ? date('d/m/Y H:i', strtotime($accdekomData[count($accdekomData) - 1]['accdekom_at']))
    : '';
  $lastDirutAt = $allAccdirutApproved && !empty($accdirutData)
    ? date('d/m/Y H:i', strtotime($accdirutData[0]['approved_at']))
    : '';
  // canEdit is passed from controller — only pe/admin
  $canEdit = $canEdit ?? false;
  ?>

  <div class="card card-body mb-3 shadow-sm">
    <div class="page-header">
      <div class="approval-badges-row mb-3">
        <div
          class="approval-status-badge <?= $allAccdekomApproved ? 'badge-status-success' : 'badge-status-secondary' ?>"
          id="badge-dekom">
          <i class="fas <?= $allAccdekomApproved ? 'fa-check-circle' : 'fa-clock' ?>"></i>
          <div>
            <span class="approval-status-label">Komisaris Utama</span><br>
            <span class="approval-status-sub">
              <?= $allAccdekomApproved ? 'Disetujui &middot; ' . $lastDekomAt : 'Belum disetujui seluruhnya' ?>
            </span>
          </div>
        </div>
        <div
          class="approval-status-badge <?= $allAccdirutApproved ? 'badge-status-success' : 'badge-status-secondary' ?>"
          id="badge-dirut">
          <i class="fas <?= $allAccdirutApproved ? 'fa-check-circle' : 'fa-clock' ?>"></i>
          <div>
            <span class="approval-status-label">Direktur Utama</span><br>
            <span class="approval-status-sub">
              <?= $allAccdirutApproved ? 'Disetujui &middot; ' . $lastDirutAt : 'Belum disetujui seluruhnya' ?>
            </span>
          </div>
        </div>
      </div>
      <h1 class="page-title"><?= esc($judul) ?></h1>
      <div class="page-subtitle">Tahun <?= esc($periodeDetail['tahun']) ?></div>
      <span class="badge-namabpr"><?= esc($bprData['namabpr'] ?? 'Nama BPR') ?></span>
    </div>
    <div id="pkd-save-status" class="pkd-save-status save-idle" style="margin-left:auto;">
      <i class="fas fa-cloud" style="font-size:10px;"></i>
      <?= $canEdit ? 'Autosave aktif' : 'Read Only' ?>
    </div>
  </div>

  <!-- Skeleton -->
  <div id="pkd-skeleton">
    <?php for ($s = 0; $s < 3; $s++): ?>
      <div class="skel-card">
        <div class="skel skel-header"></div>
        <div class="skel-body">
          <?php for ($f = 0; $f < 4; $f++): ?>
            <div class="skel skel-field"></div><?php endfor; ?>
        </div>
      </div>
    <?php endfor; ?>
  </div>

  <form id="pkdForm" style="display:none;" autocomplete="off">

    <?php
    /* Helper: render a two-column section */
    function pkdSection(string $num, string $title, array $rows, array $row0, bool $canEdit): void
    { ?>
      <div class="pkd-card">
        <div class="pkd-section-header">
          <span class="pkd-section-num"><?= $num ?></span>
          <span class="pkd-section-title"><?= esc($title) ?></span>
        </div>
        <div class="pkd-card-body">
          <div class="pkd-grid">
            <?php foreach ($rows as $r):
              $lid = $r[1];
              $rid = $r[3];
              $type = $r[4] ?? 'number';
              $lval = esc($row0[$lid] ?? '');
              $rval = esc($row0[$rid] ?? '');
              $ro = $canEdit ? '' : ' readonly';
              $roCls = $canEdit ? '' : ' pkd-readonly-field';
              ?>
              <div class="pkd-field<?= $roCls ?>">
                <label class="pkd-label" for="<?= $lid ?>"><?= esc($r[0]) ?></label>
                <?php if ($type === 'number'): ?>
                  <div class="pkd-input-group<?= $canEdit ? '' : ' readonly-group' ?>">
                    <input type="number" id="<?= $lid ?>" name="<?= $lid ?>" class="pkd-input pkd-num" value="<?= $lval ?>"
                      min="0" placeholder="0" <?= $ro ?>>
                    <span class="pkd-addon pkd-addon-right">Orang</span>
                  </div>
                <?php else: ?>
                  <div class="pkd-input-group<?= $canEdit ? '' : ' readonly-group' ?>">
                    <span class="pkd-addon">Rp</span>
                    <input type="text" id="<?= $lid ?>" name="<?= $lid ?>" class="pkd-input pkd-currency" value="<?= $lval ?>"
                      placeholder="0" <?= $ro ?>>
                  </div>
                <?php endif; ?>
              </div>
              <div class="pkd-field<?= $roCls ?>">
                <label class="pkd-label" for="<?= $rid ?>"><?= esc($r[2]) ?></label>
                <?php if ($type === 'number'): ?>
                  <div class="pkd-input-group<?= $canEdit ? '' : ' readonly-group' ?>">
                    <input type="number" id="<?= $rid ?>" name="<?= $rid ?>" class="pkd-input pkd-num" value="<?= $rval ?>"
                      min="0" placeholder="0" <?= $ro ?>>
                    <span class="pkd-addon pkd-addon-right">Orang</span>
                  </div>
                <?php else: ?>
                  <div class="pkd-input-group<?= $canEdit ? '' : ' readonly-group' ?>">
                    <span class="pkd-addon">Rp</span>
                    <input type="text" id="<?= $rid ?>" name="<?= $rid ?>" class="pkd-input pkd-currency" value="<?= $rval ?>"
                      placeholder="0" <?= $ro ?>>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php }

    pkdSection('1.1', 'Gaji Direksi dan Dewan Komisaris selama 1 tahun', [
      ['Direksi yang menerima gaji', 'penerimagajidir', 'Dewan Komisaris yang menerima gaji', 'penerimagajidekom', 'number'],
      ['Nominal gaji – Direksi', 'nominalgajidir', 'Nominal gaji – Dewan Komisaris', 'nominalgajidekom', 'currency'],
    ], $row0, $canEdit);

    pkdSection('1.2', 'Tunjangan Direksi dan Dewan Komisaris selama 1 tahun', [
      ['Direksi yang menerima tunjangan', 'terimatunjangandir', 'Dewan Komisaris yang menerima tunjangan', 'terimatunjangandekom', 'number'],
      ['Nominal tunjangan – Direksi', 'nominaltunjangandir', 'Nominal tunjangan – Dewan Komisaris', 'nominaltunjangandekom', 'currency'],
    ], $row0, $canEdit);

    pkdSection('1.3', 'Tantiem Direksi dan Dewan Komisaris selama 1 tahun', [
      ['Direksi yang menerima tantiem', 'terimatantiemdir', 'Dewan Komisaris yang menerima tantiem', 'terimatantiemdekom', 'number'],
      ['Nominal tantiem – Direksi', 'nominaltantiemdir', 'Nominal tantiem – Dewan Komisaris', 'nominaltantiemdekom', 'currency'],
    ], $row0, $canEdit);

    pkdSection('1.4', 'Kompensasi berbasis saham Direksi dan Dewan Komisaris selama 1 tahun', [
      ['Direksi yang menerima saham', 'terimashmdir', 'Dewan Komisaris yang menerima saham', 'terimashmdekom', 'number'],
      ['Nominal saham – Direksi', 'nominalshmdir', 'Nominal saham – Dewan Komisaris', 'nominalshmdekom', 'currency'],
    ], $row0, $canEdit);

    pkdSection('1.5', 'Remunerasi lainnya Direksi dan Dewan Komisaris selama 1 tahun', [
      ['Direksi yang menerima remunerasi', 'terimaremunlaindir', 'Dewan Komisaris yang menerima remunerasi', 'terimaremunlaindekom', 'number'],
      ['Nominal remunerasi – Direksi', 'nominalremunlaindir', 'Nominal remunerasi – Dewan Komisaris', 'nominalremunlaindekom', 'currency'],
    ], $row0, $canEdit);
    ?>

    <!-- Total Remunerasi -->
    <div class="pkd-total-banner">
      <div class="pkd-total-title"><i class="fas fa-calculator" style="margin-right:6px;"></i>Total Remunerasi</div>
      <div class="pkd-total-grid">
        <div class="pkd-total-item">
          <div class="pkd-total-entity">Direksi</div>
          <div class="pkd-total-val"><span>Rp</span>
            <input type="text" id="totalremundir" class="pkd-input pkd-currency" readonly
              style="flex:1;padding:0;font-size:18px;font-weight:800;color:var(--blue);"
              value="<?= esc($row0['totalremundir'] ?? '0') ?>">
          </div>
        </div>
        <div class="pkd-total-item">
          <div class="pkd-total-entity">Dewan Komisaris</div>
          <div class="pkd-total-val"><span>Rp</span>
            <input type="text" id="totalremundekom" class="pkd-input pkd-currency" readonly
              style="flex:1;padding:0;font-size:18px;font-weight:800;color:var(--blue);"
              value="<?= esc($row0['totalremundekom'] ?? '0') ?>">
          </div>
        </div>
      </div>
    </div>

    <?php
    pkdSection('2.1', 'Perumahan Direksi dan Dewan Komisaris selama 1 tahun', [
      ['Direksi yang menerima tunjangan rumah', 'terimarumahdir', 'Dewan Komisaris yang menerima tunjangan rumah', 'terimarumahdekom', 'number'],
      ['Nominal rumah – Direksi', 'nominalrumahdir', 'Nominal rumah – Dewan Komisaris', 'nominalrumahdekom', 'currency'],
    ], $row0, $canEdit);

    pkdSection('2.2', 'Transportasi Direksi dan Dewan Komisaris selama 1 tahun', [
      ['Direksi yang menerima tunjangan transportasi', 'terimatransportdir', 'Dewan Komisaris yang menerima tunjangan transportasi', 'terimatransportdekom', 'number'],
      ['Nominal transportasi – Direksi', 'nominaltransportdir', 'Nominal transportasi – Dewan Komisaris', 'nominaltransportdekom', 'currency'],
    ], $row0, $canEdit);

    pkdSection('2.3', 'Asuransi Kesehatan Direksi dan Dewan Komisaris selama 1 tahun', [
      ['Direksi yang menerima asuransi kesehatan', 'terimaasuransidir', 'Dewan Komisaris yang menerima asuransi kesehatan', 'terimaasuransidekom', 'number'],
      ['Nominal asuransi – Direksi', 'nominalasuransidir', 'Nominal asuransi – Dewan Komisaris', 'nominalasuransidekom', 'currency'],
    ], $row0, $canEdit);

    pkdSection('2.4', 'Fasilitas Lainnya Direksi dan Dewan Komisaris selama 1 tahun', [
      ['Direksi yang menerima fasilitas lain', 'terimafasilitasdir', 'Dewan Komisaris yang menerima fasilitas lain', 'terimafasilitasdekom', 'number'],
      ['Nominal fasilitas lain – Direksi', 'nominalfasilitasdir', 'Nominal fasilitas lain – Dewan Komisaris', 'nominalfasilitasdekom', 'currency'],
    ], $row0, $canEdit);
    ?>

    <!-- Total Fasilitas Lain -->
    <div class="pkd-total-banner">
      <div class="pkd-total-title"><i class="fas fa-calculator" style="margin-right:6px;"></i>Total Fasilitas Lain</div>
      <div class="pkd-total-grid">
        <div class="pkd-total-item">
          <div class="pkd-total-entity">Direksi</div>
          <div class="pkd-total-val"><span>Rp</span>
            <input type="text" id="totalfasdir" class="pkd-input pkd-currency" readonly
              style="flex:1;padding:0;font-size:18px;font-weight:800;color:var(--blue);"
              value="<?= esc($row0['totalfasdir'] ?? '0') ?>">
          </div>
        </div>
        <div class="pkd-total-item">
          <div class="pkd-total-entity">Dewan Komisaris</div>
          <div class="pkd-total-val"><span>Rp</span>
            <input type="text" id="totalfasdekom" class="pkd-input pkd-currency" readonly
              style="flex:1;padding:0;font-size:18px;font-weight:800;color:var(--blue);"
              value="<?= esc($row0['totalfasdekom'] ?? '0') ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- Grand Total -->
    <div class="pkd-total-banner"
      style="background:linear-gradient(135deg,#dbeafe 0%,#ede9fe 100%);border-color:#bfdbfe;">
      <div class="pkd-total-title" style="color:var(--navy);">
        <i class="fas fa-layer-group" style="margin-right:6px;"></i>Total Remunerasi &amp; Fasilitas Lain selama 1 Tahun
      </div>
      <div class="pkd-total-grid">
        <div class="pkd-total-item" style="border:2px solid #bfdbfe;">
          <div class="pkd-total-entity">Direksi</div>
          <div class="pkd-total-val"><span>Rp</span>
            <input type="text" id="totaldir" class="pkd-input pkd-currency" readonly
              style="flex:1;padding:0;font-size:20px;font-weight:800;color:var(--navy);"
              value="<?= esc($row0['totaldir'] ?? '0') ?>">
          </div>
        </div>
        <div class="pkd-total-item" style="border:2px solid #bfdbfe;">
          <div class="pkd-total-entity">Dewan Komisaris</div>
          <div class="pkd-total-val"><span>Rp</span>
            <input type="text" id="totaldekom" class="pkd-input pkd-currency" readonly
              style="flex:1;padding:0;font-size:20px;font-weight:800;color:var(--navy);"
              value="<?= esc($row0['totaldekom'] ?? '0') ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- Penjelasan Lebih Lanjut -->
    <div class="card shadow mb-4">
      <div class="card-header py-3" style="background:var(--grad);">
        <h5 class="m-0 font-weight-bold text-white"><i class="fas fa-tasks"></i> Penjelasan Lebih Lanjut</h5>
      </div>
      <div class="card-body p-3" id="tindak-body">
        <?php if ($canEdit): ?>
          <div class="mb-3" id="tindak-btn-wrap">
            <?php if (empty($penjelastindak)): ?>
              <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal"
                data-target="#modalTambahTindak">
                <i class="fas fa-plus"></i> Tambah Penjelasan
              </button>
            <?php else: ?>
              <?php foreach ($penjelastindak as $row): ?>
                <button type="button" class="btn btn-sm btn-outline-warning" data-toggle="modal"
                  data-target="#modalEditTindak" data-id="<?= esc($row['id']) ?>"
                  data-tindaklanjut="<?= esc($row['tindaklanjut'] ?? '') ?>">
                  <i class="fas fa-edit"></i> Ubah Penjelasan
                </button>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        <?php endif; ?>
        <div id="tindak-content-wrap">
          <?php if (empty($penjelastindak)): ?>
            <div class="tindak-empty"><i class="fas fa-inbox fa-2x text-muted mb-2 d-block"></i><span
                class="text-muted">Belum ada penjelasan lebih lanjut.</span></div>
          <?php else: ?>
            <?php foreach ($penjelastindak as $row): ?>
              <div class="tindak-card">
                <div class="tindak-section">
                  <div class="tindak-label"><i class="fas fa-check-circle text-success"></i> Penjelasan Lebih Lanjut</div>
                  <div class="tindak-content">
                    <?php if (!empty($row['tindaklanjut'])): ?>
                      <?= nl2br(esc($row['tindaklanjut'])) ?>
                      <?php if ($canEdit): ?>
                        <a href="#" class="btn-icon-del btn-del-tindak" data-id="<?= esc($row['id']) ?>" title="Hapus">
                          <i class="fas fa-trash-alt"></i>
                        </a>
                      <?php endif; ?>
                    <?php else: ?>
                      <span class="text-muted fst-italic">Belum diisi</span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Approval actions -->
    <div class="approval-actions-row mt-3">
      <?php if (($userInGroupAdmin ?? false) || ($userInGroupDekom ?? false)): ?>
        <div class="action-card card shadow-sm" id="action-dekom">
          <div class="card-body py-2 text-center">
            <small class="d-block font-weight-bold text-dark mb-2"><i class="fas fa-user-tie text-success"></i> Komisaris
              Utama</small>
            <div class="d-flex justify-content-center flex-wrap" style="gap:4px;">
              <a href="<?= base_url('Paketkebijakandirdekom/approveSemuaKom') ?>"
                onclick="return confirm('Setujui semua data sebagai Komisaris Utama?')"
                class="btn <?= $allAccdekomApproved ? 'btn-success' : 'btn-primary' ?> btn-sm" id="btn-approve-dekom">
                <i class="fas fa-check-double"></i> Setujui
              </a>
              <a href="<?= base_url('Paketkebijakandirdekom/unapproveSemuaKom') ?>"
                onclick="return confirm('Batalkan semua approval Komisaris Utama?')"
                class="btn btn-outline-danger btn-sm">
                <i class="fas fa-ban"></i> Batal
              </a>
            </div>
          </div>
        </div>
      <?php endif; ?>
      <?php if (($userInGroupAdmin ?? false) || ($userInGroupDireksi ?? false)): ?>
        <div class="action-card card shadow-sm <?= (!($canApprove ?? false)) ? 'blk-disabled' : '' ?>" id="action-dirut">
          <div class="card-body py-2 text-center">
            <small class="d-block font-weight-bold text-dark mb-2"><i class="fas fa-user-shield text-primary"></i>
              Direktur Utama</small>
            <?php if (!($canApprove ?? false)): ?>
              <small class="text-muted d-block mb-1" style="font-size:10px;"><i class="fas fa-lock"></i> Komisaris harus
                setuju dulu</small>
            <?php endif; ?>
            <div class="d-flex justify-content-center flex-wrap" style="gap:4px;">
              <a href="<?= base_url('Paketkebijakandirdekom/approveSemuaDirut') ?>"
                onclick="return confirm('Setujui semua data sebagai Direktur Utama?')"
                class="btn <?= $allAccdirutApproved ? 'btn-success' : 'btn-primary' ?> btn-sm" id="btn-approve-dirut">
                <i class="fas fa-check-double"></i> Setujui
              </a>
              <a href="<?= base_url('Paketkebijakandirdekom/unapproveSemuaDirut') ?>"
                onclick="return confirm('Batalkan semua approval Direktur Utama?')" class="btn btn-outline-danger btn-sm">
                <i class="fas fa-ban"></i> Batal
              </a>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Bottom bar: komentar -->
    <div class="pkd-bottom-bar">
      <?php if (($userInGroupAdmin || $userInGroupDekom || $userInGroupDireksi || $userInGroupPE || $userInGroupDekom2 || $userInGroupDireksi2) ?? false): ?>
        <div class="position-relative d-inline-block">
          <button type="button" class="btn-komentar-global-btn" id="btn-komentar-global" title="Komentar">
            <i class="fas fa-comment-dots"></i>
            <span class="btn-komentar-ripple"></span>
          </button>
          <span id="notif-global" class="notif-badge" style="display:none;">0</span>
        </div>
      <?php endif; ?>
    </div>

  </form><!-- /pkdForm -->

  <!-- Page nav -->
  <div class="cardpilihfaktor mb-4 mt-4">
    <div class="cardpilihfaktor-header">
      <h6>Pilih Halaman</h6>
    </div>
    <div class="cardpilihfaktor-body">
      <div class="nav-pages-wrap">
        <?php
        $pages = [
          ['url' => 'Penjelasanumum', 'label' => '1'],
          ['url' => 'Tgjwbdir', 'label' => '2'],
          ['url' => 'Tgjwbdekom', 'label' => '3'],
          ['url' => 'Tgjwbkomite', 'label' => '4'],
          ['url' => 'Strukturkomite', 'label' => '5'],
          ['url' => 'Sahamdirdekom', 'label' => '6'],
          ['url' => 'Shmusahadirdekom', 'label' => '7'],
          ['url' => 'Shmdirdekomlain', 'label' => '8'],
          ['url' => 'Keuangandirdekompshm', 'label' => '9'],
          ['url' => 'Keluargadirdekompshm', 'label' => '10'],
          ['url' => 'Paketkebijakandirdekom', 'label' => '11'],
          ['url' => 'Rasiogaji', 'label' => '12'],
          ['url' => 'Rapat', 'label' => '13'],
          ['url' => 'Kehadirandekom', 'label' => '14'],
          ['url' => 'Fraudinternal', 'label' => '15'],
          ['url' => 'Masalahhukum', 'label' => '16'],
          ['url' => 'Transaksikepentingan', 'label' => '17'],
          ['url' => 'Danasosial', 'label' => '18'],
        ];
        foreach ($pages as $p): ?>
          <a href="<?= base_url($p['url']) ?>"
            class="nav-page-btn <?= $p['url'] === 'Paketkebijakandirdekom' ? 'active' : '' ?>"><?= $p['label'] ?></a>
        <?php endforeach; ?>
      </div>
      <div style="text-align:center;padding:4px 0 8px;">
        <a href="<?= base_url('ShowTransparansi') ?>"
          style="font-size:12px;color:var(--gray-400);font-weight:600;">Kembali ke halaman transparansi</a>
      </div>
    </div>
  </div>
</div>

<!-- ═══ MODAL TAMBAH PENJELASAN ═══ -->
<div class="modal fade" id="modalTambahTindak" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header modal-header-dir">
        <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Tambah Penjelasan</h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label class="font-weight-bold">Penjelasan Lebih Lanjut <span class="text-danger">*</span></label>
          <textarea class="form-control" id="tt-tindaklanjut" rows="6"
            placeholder="Tuliskan penjelasan lebih lanjut..."></textarea>
        </div>
        <div id="tindak-tambah-err" class="alert alert-danger mt-2" style="display:none;"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Batal</button>
        <button type="button" class="btn btn-primary" id="btn-simpan-tindak"><i class="fas fa-save"></i> Simpan</button>
      </div>
    </div>
  </div>
</div>

<!-- ═══ MODAL EDIT PENJELASAN ═══ -->
<div class="modal fade" id="modalEditTindak" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header modal-header-dir">
        <h5 class="modal-title"><i class="fas fa-edit"></i> Ubah Penjelasan</h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="et-id">
        <div class="form-group">
          <label class="font-weight-bold">Penjelasan Lebih Lanjut</label>
          <textarea class="form-control" id="et-tindaklanjut" rows="6"></textarea>
        </div>
        <div id="tindak-edit-err" class="alert alert-danger mt-2" style="display:none;"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Batal</button>
        <button type="button" class="btn btn-primary" id="btn-simpan-edit-tindak"><i class="fas fa-save"></i> Simpan
          Perubahan</button>
      </div>
    </div>
  </div>
</div>

<!-- ═══ MODAL KOMENTAR ═══ -->
<div class="modal fade" id="modalKomentar" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
    <div class="modal-content komentar-modal-content">
      <div class="modal-header komentar-modal-header">
        <div class="komentar-modal-title-wrap">
          <div class="komentar-modal-icon-wrap"><i class="fas fa-comments"></i></div>
          <div>
            <div class="komentar-modal-title">Komentar — Paket Kebijakan Direksi &amp; Dewan Komisaris</div>
          </div>
        </div>
        <button type="button" class="komentar-close-btn" data-dismiss="modal"><i class="fas fa-times"></i></button>
      </div>
      <div class="modal-body komentar-modal-body">
        <div class="komentar-list-label"><i class="fas fa-history"></i> Riwayat Komentar</div>
        <div id="komentar-list-wrapper" class="komentar-list-wrapper">
          <div id="komentar-list">
            <div class="komentar-loading"><i class="fas fa-spinner fa-spin"></i> Memuat...</div>
          </div>
        </div>
        <?php if (($userInGroupAdmin || $userInGroupDekom || $userInGroupDireksi || $userInGroupPE || $userInGroupDekom2 || $userInGroupDireksi2) ?? false): ?>
          <div class="komentar-input-wrap">
            <div class="komentar-input-label"><i class="fas fa-pen-alt"></i> Tulis Komentar</div>
            <textarea id="k-teks" class="komentar-textarea" rows="3" placeholder="Tuliskan komentar..."
              maxlength="1000"></textarea>
            <div class="komentar-input-footer">
              <span class="komentar-hint"><i class="fas fa-keyboard"></i> Ctrl+Enter untuk kirim</span>
              <span id="k-char-count" class="komentar-char">0 / 1000</span>
            </div>
          </div>
        <?php endif; ?>
      </div>
      <div class="modal-footer komentar-modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal"><i class="fas fa-times"></i>
          Tutup</button>
        <?php if (($userInGroupAdmin || $userInGroupDekom || $userInGroupDireksi || $userInGroupPE || $userInGroupDekom2 || $userInGroupDireksi2) ?? false): ?>
          <button type="button" class="btn btn-primary btn-sm" id="btn-kirim-komentar"><i class="fas fa-paper-plane"></i>
            Kirim</button>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php
$_canEdit = $canEdit ? 'true' : 'false';
$_canComment = (($userInGroupAdmin || $userInGroupDekom || $userInGroupDireksi || $userInGroupPE || $userInGroupDekom2 || $userInGroupDireksi2) ?? false) ? 'true' : 'false';
?>
<script>
  (function () {
    'use strict';

    var CFG = {
      base: '<?= rtrim(base_url(), '/') ?>',
      csrfName: '<?= csrf_token() ?>',
      csrfHash: '<?= csrf_hash() ?>',
      uid: <?= (int) ($userId ?? 0) ?>,
      fname: <?= json_encode($fullname ?? 'Unknown', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
      kbpr: <?= json_encode($kodebpr ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
      pid: <?= (int) ($periodeId ?? 0) ?>,
      canEdit: <?= $_canEdit ?>,
      canComment: <?= $_canComment ?>,
      pollBase: <?= (int) ($pollIntervalMs ?? 10000) ?>
    };

    function ready(fn) {
      if (typeof jQuery !== 'undefined') jQuery(fn);
      else setTimeout(function () { ready(fn); }, 40);
    }

    ready(function ($) {

      /* ── UTILS ── */
      function esc(s) { return s == null ? '' : String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }
      function debounce(fn, ms) { var t; return function () { var a = arguments, c = this; clearTimeout(t); t = setTimeout(function () { fn.apply(c, a); }, ms); }; }
      function toast(msg, type) {
        type = type || 'success';
        var ic = { success: 'check-circle', error: 'exclamation-circle', info: 'info-circle' };
        var el = $('<div class="t-item t-' + type + '"><i class="fas fa-' + ic[type] + ' fa-lg"></i><div>' + esc(msg) + '</div></div>');
        $('#toast-wrap').append(el);
        setTimeout(function () { el.fadeOut(400, function () { $(this).remove(); }); }, 3500);
      }
      function readCsrf() { var m = document.cookie.match(/ci_csrf_token=([^;]+)/); if (m) CFG.csrfHash = decodeURIComponent(m[1]); }
      function csrf() { readCsrf(); var d = {}; d[CFG.csrfName] = CFG.csrfHash; return d; }
      function updateCsrf(res) { readCsrf(); if (res && res.csrf_hash) CFG.csrfHash = res.csrf_hash; }
      function btnLoading(btn) { var ori = btn.html(); btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true); return function () { btn.html(ori).prop('disabled', false); }; }
      function timeAgo(s) {
        if (!s) return '-';
        var d = new Date(s.replace(' ', 'T')), diff = Math.floor((Date.now() - d.getTime()) / 60000);
        if (isNaN(diff) || diff < 1) return 'Baru saja';
        if (diff < 60) return diff + ' menit lalu';
        if (diff < 1440) return Math.floor(diff / 60) + ' jam lalu';
        return ('0' + d.getDate()).slice(-2) + '/' + ('0' + (d.getMonth() + 1)).slice(-2) + '/' + d.getFullYear();
      }
      var fmt = function (v) { return v ? new Intl.NumberFormat('id-ID').format(v) : ''; };
      var raw = function (v) { return parseInt((v + '').replace(/\D/g, ''), 10) || 0; };

      /* ── CURRENCY FORMAT ── */
      document.querySelectorAll('.pkd-currency:not([readonly])').forEach(function (inp) {
        if (inp.value) inp.value = fmt(raw(inp.value));
        inp.addEventListener('input', function () {
          var digits = (this.value.replace(/\D/g, '')).slice(0, 15);
          this.value = digits ? fmt(parseInt(digits, 10)) : '';
          recalcTotals();
          if (CFG.canEdit) triggerDebounce();
        });
      });
      document.querySelectorAll('.pkd-currency[readonly]').forEach(function (inp) {
        if (inp.value) inp.value = fmt(raw(inp.value));
      });

      function getVal(id) { return raw(document.getElementById(id)?.value || '0'); }
      function setRO(id, val) { var el = document.getElementById(id); if (el) el.value = val ? fmt(val) : '0'; }

      function recalcTotals() {
        var rd = getVal('nominalgajidir') + getVal('nominaltunjangandir') + getVal('nominaltantiemdir') + getVal('nominalshmdir') + getVal('nominalremunlaindir');
        var rk = getVal('nominalgajidekom') + getVal('nominaltunjangandekom') + getVal('nominaltantiemdekom') + getVal('nominalshmdekom') + getVal('nominalremunlaindekom');
        setRO('totalremundir', rd); setRO('totalremundekom', rk);
        var fd = getVal('nominalrumahdir') + getVal('nominaltransportdir') + getVal('nominalasuransidir') + getVal('nominalfasilitasdir');
        var fk = getVal('nominalrumahdekom') + getVal('nominaltransportdekom') + getVal('nominalasuransidekom') + getVal('nominalfasilitasdekom');
        setRO('totalfasdir', fd); setRO('totalfasdekom', fk);
        setRO('totaldir', rd + fd); setRO('totaldekom', rk + fk);
      }

      /* ── AUTOSAVE ── */
      var $saveStatus = $('#pkd-save-status');
      function setSaveStatus(state, text) {
        $saveStatus.attr('class', 'pkd-save-status save-' + state);
        var icons = { idle: '<i class="fas fa-circle" style="font-size:8px;"></i>', typing: '<i class="fas fa-pencil-alt" style="font-size:10px;"></i>', saving: '<div class="pkd-spinner"></div>', saved: '<i class="fas fa-check" style="font-size:11px;"></i>', error: '<i class="fas fa-times" style="font-size:11px;"></i>' };
        $saveStatus.html((icons[state] || '') + ' ' + text);
      }

      function collectFormData() {
        var numFields = ['penerimagajidir', 'penerimagajidekom', 'terimatunjangandir', 'terimatunjangandekom', 'terimatantiemdir', 'terimatantiemdekom', 'terimashmdir', 'terimashmdekom', 'terimaremunlaindir', 'terimaremunlaindekom', 'terimarumahdir', 'terimarumahdekom', 'terimatransportdir', 'terimatransportdekom', 'terimaasuransidir', 'terimaasuransidekom', 'terimafasilitasdir', 'terimafasilitasdekom'];
        var curFields = ['nominalgajidir', 'nominalgajidekom', 'nominaltunjangandir', 'nominaltunjangandekom', 'nominaltantiemdir', 'nominaltantiemdekom', 'nominalshmdir', 'nominalshmdekom', 'nominalremunlaindir', 'nominalremunlaindekom', 'nominalrumahdir', 'nominalrumahdekom', 'nominaltransportdir', 'nominaltransportdekom', 'nominalasuransidir', 'nominalasuransidekom', 'nominalfasilitasdir', 'nominalfasilitasdekom', 'totalremundir', 'totalremundekom', 'totalfasdir', 'totalfasdekom', 'totaldir', 'totaldekom'];
        var data = {};
        numFields.forEach(function (f) { data[f] = parseInt(document.getElementById(f)?.value || '0', 10) || 0; });
        curFields.forEach(function (f) { data[f] = raw(document.getElementById(f)?.value || '0'); });
        return data;
      }

      var _saveXhr = null, _debTimer = null;
      function triggerDebounce() {
        setSaveStatus('typing', 'Mengetik...');
        clearTimeout(_debTimer);
        _debTimer = setTimeout(doSave, 600);
      }
      function doSave() {
        setSaveStatus('saving', 'Menyimpan...');
        if (_saveXhr) _saveXhr.abort();
        var payload = $.extend({}, csrf(), collectFormData());
        _saveXhr = $.ajax({
          url: CFG.base + '/Paketkebijakandirdekom/tambahpenjelasAjax',
          method: 'POST', dataType: 'json', data: payload,
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          success: function (res) {
            updateCsrf(res);
            if (res.status === 'success') {
              setSaveStatus('saved', 'Tersimpan');
              toast('Data berhasil tersimpan', 'success', 2500);
              setTimeout(function () { setSaveStatus('idle', 'Autosave aktif'); }, 3500);
            } else {
              setSaveStatus('error', 'Gagal simpan');
              toast('Gagal menyimpan: ' + (res.message || 'Unknown'), 'error');
            }
          },
          error: function (xhr) {
            if (xhr.statusText === 'abort') return;
            setSaveStatus('error', 'Error');
            toast('Terjadi kesalahan koneksi', 'error');
          }
        });
      }

      /* Attach input listeners only if canEdit */
      if (CFG.canEdit) {
        $('#pkdForm .pkd-input:not([readonly])').on('input', function () {
          if (!$(this).hasClass('pkd-currency')) triggerDebounce();
        });
      }

      /* ── POLL ── */
      var _pollTimer = null, _pollDelay = CFG.pollBase, _lastUnread = 0, _pollAbort = null;
      function schedulePoll(delay) { clearTimeout(_pollTimer); _pollTimer = setTimeout(doPoll, delay); }
      function doPoll() {
        if (_pollAbort) { try { _pollAbort.abort(); } catch (e) { } }
        var aborter = { aborted: false, abort: function () { this.aborted = true; } };
        _pollAbort = aborter;
        $.ajax({
          url: CFG.base + '/Paketkebijakandirdekom/getUnreadCommentCountForFactor',
          method: 'GET', dataType: 'json',
          data: { id: 'Paketkebijakandirdekom', kodebpr: CFG.kbpr, user_id: CFG.uid, periode_id: CFG.pid },
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          success: function (res) {
            if (aborter.aborted) return;
            var n = parseInt(res.unread_count) || 0;
            updateNotifBadge(n);
            _pollDelay = n > _lastUnread ? Math.max(5000, CFG.pollBase / 2) : Math.min(60000, CFG.pollBase);
            _lastUnread = n;
            if (res.next_poll_ms) _pollDelay = Math.max(5000, parseInt(res.next_poll_ms));
            schedulePoll(_pollDelay);
          },
          error: function () {
            if (aborter.aborted) return;
            _pollDelay = Math.min(120000, _pollDelay * 2);
            schedulePoll(_pollDelay);
          }
        });
      }
      function updateNotifBadge(n) {
        n = parseInt(n) || 0;
        var $btn = $('#btn-komentar-global'), $badge = $('#notif-global');
        if (n > 0) { $badge.text(n).show(); $btn.addClass('has-unread'); }
        else { $badge.hide(); $btn.removeClass('has-unread'); }
      }

      /* ── KOMENTAR ── */
      function loadKomentarList(scroll) {
        var list = $('#komentar-list');
        list.html('<div class="komentar-loading"><i class="fas fa-spinner fa-spin"></i> Memuat...</div>');
        $.ajax({
          url: CFG.base + '/Paketkebijakandirdekom/getKomentarByFaktorId',
          method: 'GET', dataType: 'json',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          success: function (res) {
            var items = Array.isArray(res) ? res : (res.comments || []);
            if (res.unread_count !== undefined) updateNotifBadge(res.unread_count);
            if (!items.length) { list.html('<div class="komentar-empty"><i class="fas fa-comment-slash fa-2x mb-2 d-block"></i>Belum ada komentar.</div>'); return; }
            var frag = document.createDocumentFragment(), tmp = document.createElement('div');
            items.forEach(function (k) {
              var own = parseInt(k.user_id) === CFG.uid;
              tmp.innerHTML = '<div class="km-item' + (own ? ' km-own' : '') + '"><div class="km-meta">'
                + '<span class="km-author"><i class="fas fa-user-circle"></i> ' + esc(k.fullname || '?') + (own ? ' <span class="km-you">Anda</span>' : '') + '</span>'
                + '<span class="km-time"><i class="fas fa-clock"></i> ' + timeAgo(k.created_at) + '</span>'
                + '</div><p class="km-text">' + esc(k.komentar) + '</p></div>';
              frag.appendChild(tmp.firstElementChild);
            });
            list.empty()[0].appendChild(frag);
            if (scroll) { var w = document.getElementById('komentar-list-wrapper'); if (w) requestAnimationFrame(function () { w.scrollTop = w.scrollHeight; }); }
          },
          error: function () { list.html('<div class="komentar-empty text-danger"><i class="fas fa-exclamation-circle"></i> Gagal memuat komentar.</div>'); }
        });
      }
      function markAllRead() {
        $.ajax({
          url: CFG.base + '/Paketkebijakandirdekom/markUserCommentsAsRead', method: 'POST',
          data: $.extend({}, csrf(), { id: 'Paketkebijakandirdekom', kodebpr: CFG.kbpr, user_id: CFG.uid, periode_id: CFG.pid }),
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          success: function (res) { updateCsrf(res); updateNotifBadge(0); _lastUnread = 0; }
        });
      }

      var _sendingKomentar = false;
      var execKomentar = debounce(function () {
        if (_sendingKomentar) return;
        var teks = $.trim($('#k-teks').val());
        if (!teks) { toast('Komentar tidak boleh kosong.', 'error'); return; }
        if (teks.length < 3) { toast('Komentar minimal 3 karakter.', 'error'); return; }
        _sendingKomentar = true;
        var restore = btnLoading($('#btn-kirim-komentar'));
        $.ajax({
          url: CFG.base + '/Paketkebijakandirdekom/Tambahkomentar', method: 'POST', dataType: 'json',
          data: $.extend({}, csrf(), { TambahKomentar: 1, id: 'Paketkebijakandirdekom', komentar: teks, fullname: CFG.fname, kodebpr: CFG.kbpr, date: new Date().toISOString().slice(0, 19).replace('T', ' ') }),
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          success: function (res) {
            _sendingKomentar = false; restore(); updateCsrf(res);
            if (res && res.status === 'success') { $('#k-teks').val(''); $('#k-char-count').text('0 / 1000').removeClass('danger'); toast('Komentar berhasil dikirim'); loadKomentarList(true); _lastUnread = 0; _pollDelay = 5000; }
            else { toast((res && res.message) || 'Gagal mengirim', 'error'); }
          },
          error: function () { _sendingKomentar = false; restore(); toast('Gagal mengirim komentar', 'error'); }
        });
      }, 400);

      $('#btn-komentar-global').on('click', function () { loadKomentarList(true); markAllRead(); $('#modalKomentar').modal('show'); });
      $('#btn-kirim-komentar').on('click', execKomentar);
      $('#k-teks')
        .on('keydown', function (e) { if (e.ctrlKey && e.key === 'Enter') { e.preventDefault(); execKomentar(); } })
        .on('input', function () { var l = this.value.length, $el = $('#k-char-count'); $el.text(l + ' / 1000'); l > 950 ? $el.addClass('danger') : $el.removeClass('danger'); });

      /* ── PENJELASAN ── */
      function renderTindakLanjut(rows) {
        var $bw = $('#tindak-btn-wrap'), $cw = $('#tindak-content-wrap');
        if (CFG.canEdit) {
          if (!rows || !rows.length) {
            $bw.html('<button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#modalTambahTindak"><i class="fas fa-plus"></i> Tambah Penjelasan</button>');
          } else {
            $bw.html('<button type="button" class="btn btn-sm btn-outline-warning" data-toggle="modal" data-target="#modalEditTindak"'
              + ' data-id="' + esc(rows[0].id) + '" data-tindaklanjut="' + esc(rows[0].tindaklanjut || '') + '">'
              + '<i class="fas fa-edit"></i> Ubah Penjelasan</button>');
          }
        }
        var html = '';
        if (!rows || !rows.length) {
          html = '<div class="tindak-empty"><i class="fas fa-inbox fa-2x text-muted mb-2 d-block"></i><span class="text-muted">Belum ada penjelasan lebih lanjut.</span></div>';
        } else {
          rows.forEach(function (row) {
            html += '<div class="tindak-card"><div class="tindak-section"><div class="tindak-label"><i class="fas fa-check-circle text-success"></i> Penjelasan Lebih Lanjut</div>'
              + '<div class="tindak-content">';
            if (row.tindaklanjut) {
              html += esc(row.tindaklanjut).replace(/\n/g, '<br>');
              if (CFG.canEdit) html += ' <a href="#" class="btn-icon-del btn-del-tindak" data-id="' + esc(row.id) + '" title="Hapus"><i class="fas fa-trash-alt"></i></a>';
            } else { html += '<span class="text-muted fst-italic">Belum diisi</span>'; }
            html += '</div></div></div>';
          });
        }
        $cw.html(html);
        $('#tt-tindaklanjut').val('');
        $('#tindak-tambah-err').hide();
      }

      var execTambahTindak = debounce(function () {
        var tindak = $.trim($('#tt-tindaklanjut').val());
        if (!tindak) { $('#tindak-tambah-err').text('Penjelasan wajib diisi.').show(); return; }
        var restore = btnLoading($('#btn-simpan-tindak'));
        $('#tindak-tambah-err').hide();
        $.ajax({
          url: CFG.base + '/Paketkebijakandirdekom/tambahketerangan', method: 'POST', dataType: 'json',
          data: $.extend({}, csrf(), { tindaklanjut: tindak }),
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          success: function (res) {
            restore(); updateCsrf(res);
            if (res.status === 'success') { $('#modalTambahTindak').modal('hide'); toast(res.message || 'Penjelasan berhasil disimpan'); renderTindakLanjut(res.penjelastindak || []); }
            else { $('#tindak-tambah-err').text(res.message || 'Terjadi kesalahan').show(); }
          },
          error: function (xhr) {
            restore();
            var m = 'Terjadi kesalahan server.';
            try { var r = JSON.parse(xhr.responseText); if (r && r.message) m = r.message; } catch (e) { }
            if (xhr.status === 403) { m = 'Sesi kedaluwarsa — halaman akan di-refresh…'; setTimeout(function () { location.reload(); }, 2000); }
            $('#tindak-tambah-err').text(m).show();
          }
        });
      }, 400);
      $('#btn-simpan-tindak').on('click', execTambahTindak);

      $('#modalEditTindak').on('show.bs.modal', function (e) {
        var btn = $(e.relatedTarget);
        $('#et-id').val(btn.data('id'));
        $('#et-tindaklanjut').val(btn.data('tindaklanjut'));
        $('#tindak-edit-err').hide();
      });

      var execEditTindak = debounce(function () {
        var id = $('#et-id').val(), tindak = $.trim($('#et-tindaklanjut').val());
        if (!tindak) { $('#tindak-edit-err').text('Penjelasan tidak boleh kosong.').show(); return; }
        var restore = btnLoading($('#btn-simpan-edit-tindak'));
        $('#tindak-edit-err').hide();
        $.ajax({
          url: CFG.base + '/Paketkebijakandirdekom/editketerangan', method: 'POST', dataType: 'json',
          data: $.extend({}, csrf(), { id: id, tindaklanjut: tindak }),
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          success: function (res) {
            restore(); updateCsrf(res);
            if (res.status === 'success') { $('#modalEditTindak').modal('hide'); toast(res.message || 'Penjelasan berhasil diubah'); renderTindakLanjut(res.penjelastindak || []); }
            else { $('#tindak-edit-err').text(res.message || 'Terjadi kesalahan').show(); }
          },
          error: function (xhr) {
            restore();
            var m = 'Terjadi kesalahan server.';
            try { var r = JSON.parse(xhr.responseText); if (r && r.message) m = r.message; } catch (e) { }
            if (xhr.status === 403) { m = 'Sesi kedaluwarsa — halaman akan di-refresh…'; setTimeout(function () { location.reload(); }, 2000); }
            $('#tindak-edit-err').text(m).show();
          }
        });
      }, 400);
      $('#btn-simpan-edit-tindak').on('click', execEditTindak);

      $(document).on('click', '.btn-del-tindak', function (e) {
        e.preventDefault();
        var id = $(this).data('id'), $btn = $(this);
        if (!confirm('Hapus penjelasan ini?')) return;
        $btn.html('<i class="fas fa-spinner fa-spin"></i>').css('pointer-events', 'none');
        $.ajax({
          url: CFG.base + '/Paketkebijakandirdekom/setNullKolomTindakAjax', method: 'POST', dataType: 'json',
          data: $.extend({}, csrf(), { id: id }),
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          success: function (res) {
            updateCsrf(res);
            if (res.status === 'success') { toast(res.message || 'Data dihapus'); renderTindakLanjut([]); }
            else { toast(res.message || 'Gagal menghapus', 'error'); $btn.html('<i class="fas fa-trash-alt"></i>').css('pointer-events', ''); }
          },
          error: function () { toast('Terjadi kesalahan server', 'error'); $btn.html('<i class="fas fa-trash-alt"></i>').css('pointer-events', ''); }
        });
      });

      $(document).on('hidden.bs.modal', '.modal', function () { $(this).find('.alert-danger').hide().text(''); });

      document.addEventListener('visibilitychange', function () {
        if (document.hidden) { clearTimeout(_pollTimer); }
        else { _pollDelay = CFG.pollBase; schedulePoll(1000); }
      }, { passive: true });

      /* ── BOOT ── */
      setTimeout(function () {
        recalcTotals();
        document.getElementById('pkd-skeleton').style.display = 'none';
        document.getElementById('pkdForm').style.display = '';
        schedulePoll(_pollDelay);
        if (!CFG.canEdit) setSaveStatus('idle', 'Read Only');
      }, 180);

    }); // ready
  }());
</script>

<style>
  :root {
    --brand: #4f46e5;
    --brand-light: #818cf8;
    --brand-dark: #3730a3;
    --brand-bg: #eef2ff;
    --navy: #141863;
    --blue: #1f2aa8;
    --grad: linear-gradient(135deg, #141863 0%, #1f2aa8 100%);
    --success: #10b981;
    --success-bg: #d1fae5;
    --danger: #ef4444;
    --danger-bg: #fee2e2;
    --warning: #f59e0b;
    --warning-bg: #fef3c7;
    --gray-50: #f8fafc;
    --gray-100: #f1f5f9;
    --gray-200: #e2e8f0;
    --gray-300: #cbd5e1;
    --gray-400: #94a3b8;
    --gray-500: #64748b;
    --gray-600: #475569;
    --gray-700: #334155;
    --gray-800: #1e293b;
    --gray-900: #0f172a;
    --sh-sm: 0 1px 3px rgba(0, 0, 0, .08);
    --sh: 0 4px 6px -1px rgba(0, 0, 0, .1);
    --sh-lg: 0 10px 25px -3px rgba(0, 0, 0, .1);
    --tr: all .2s cubic-bezier(.4, 0, .2, 1);
    --r-sm: 8px;
    --r: 12px;
    --r-lg: 16px
  }

  * {
    font-family: 'Plus Jakarta Sans', sans-serif
  }

  .page-header {
    text-align: center;
    padding: 28px 16px 20px;
    background: white;
    border-bottom: 1px solid var(--gray-100);
    position: relative;
    overflow: hidden
  }

  .page-header::before {
    content: '';
    position: absolute;
    top: -60px;
    right: -60px;
    width: 180px;
    height: 180px;
    border-radius: 50%;
    background: radial-gradient(circle, #eef2ff, transparent 70%)
  }

  .page-title {
    font-size: clamp(1.05rem, 3.5vw, 1.5rem);
    font-weight: 800;
    color: var(--gray-900);
    margin-bottom: 6px;
    letter-spacing: -.5px;
    position: relative;
    z-index: 1
  }

  .page-subtitle {
    font-size: .9rem;
    font-weight: 600;
    color: var(--gray-500);
    margin-bottom: 10px;
    position: relative;
    z-index: 1
  }

  .approval-badges-row {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: center;
    align-items: center
  }

  .approval-status-badge {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 14px;
    border-radius: var(--r);
    font-size: 12.5px;
    font-weight: 600;
    border: 1.5px solid transparent;
    transition: var(--tr)
  }

  .approval-status-badge i {
    font-size: 18px;
    flex-shrink: 0
  }

  .approval-status-label {
    font-weight: 700;
    font-size: 12px
  }

  .approval-status-sub {
    font-size: 11px;
    opacity: .8
  }

  .badge-status-secondary {
    background: var(--gray-100);
    color: var(--gray-500);
    border-color: var(--gray-200)
  }

  .badge-status-success {
    background: var(--success-bg);
    color: #065f46;
    border-color: #6ee7b7
  }

  .approval-actions-row {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    justify-content: center
  }

  .action-card {
    border-radius: var(--r) !important;
    border: 1.5px solid var(--gray-200) !important;
    transition: var(--tr);
    overflow: hidden;
    min-width: 200px
  }

  .action-card:hover {
    border-color: var(--brand-light) !important;
    box-shadow: 0 8px 24px rgba(79, 70, 229, .12) !important;
    transform: translateY(-2px)
  }

  .btn {
    transition: var(--tr);
    font-weight: 600
  }

  .btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--sh)
  }

  .btn:active {
    transform: scale(.96) !important
  }

  .btn-sm {
    font-size: 12px
  }

  .badge-namabpr {
    display: inline-block;
    background: var(--grad);
    color: white;
    font-size: .9rem;
    font-weight: 700;
    padding: 5px 18px;
    border-radius: 100px;
    letter-spacing: .3px;
    position: relative;
    z-index: 1;
    box-shadow: 0 4px 12px rgba(79, 70, 229, .3);
  }

  .btn-kembali {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: white;
    border: 1.5px solid var(--gray-200);
    border-radius: var(--r-sm);
    padding: 7px 14px;
    font-size: .875rem;
    font-weight: 600;
    color: var(--gray-500);
    text-decoration: none;
    transition: var(--tr);
    margin: 12px 0 0 8px
  }

  .btn-kembali:hover {
    background: var(--brand-bg);
    border-color: var(--brand-light);
    color: var(--brand);
    text-decoration: none
  }

  .btn-icon-del {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 6px;
    background: var(--danger-bg);
    color: var(--danger);
    font-size: 11px;
    margin-left: 6px;
    vertical-align: middle;
    text-decoration: none;
    transition: var(--tr)
  }

  .btn-icon-del:hover {
    background: var(--danger);
    color: white
  }

  /* PKD CARD */
  .pkd-card {
    background: white;
    border-radius: var(--r-lg);
    border: 1.5px solid var(--gray-200);
    box-shadow: var(--sh-sm);
    overflow: hidden;
    margin-bottom: 20px;
    transition: box-shadow .25s
  }

  .pkd-card:hover {
    box-shadow: var(--sh)
  }

  .pkd-section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    background: var(--grad);
    padding: 11px 18px;
    border-radius: var(--r-lg) var(--r-lg) 0 0
  }

  .pkd-section-num {
    background: rgba(255, 255, 255, .2);
    color: white;
    font-size: 11px;
    font-weight: 800;
    width: 26px;
    height: 26px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0
  }

  .pkd-section-title {
    font-size: 13.5px;
    font-weight: 700;
    color: rgba(255, 255, 255, .95)
  }

  .pkd-card-body {
    padding: 20px
  }

  .pkd-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px
  }

  @media(max-width:640px) {
    .pkd-grid {
      grid-template-columns: 1fr
    }
  }

  .pkd-field {
    display: flex;
    flex-direction: column;
    gap: 6px
  }

  .pkd-readonly-field {
    opacity: .8
  }

  .pkd-label {
    font-size: 12px;
    font-weight: 700;
    color: var(--gray-600);
    text-transform: uppercase;
    letter-spacing: .4px
  }

  .pkd-input-group {
    display: flex;
    align-items: stretch;
    border: 1.5px solid var(--gray-200);
    border-radius: var(--r-sm);
    overflow: hidden;
    transition: border-color .2s, box-shadow .2s;
    background: white
  }

  .pkd-input-group:focus-within {
    border-color: var(--brand);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, .12)
  }

  .pkd-input-group.readonly-group {
    background: var(--gray-50);
    border-color: var(--gray-200) !important
  }

  .pkd-addon {
    display: flex;
    align-items: center;
    padding: 0 10px;
    background: var(--gray-100);
    border-right: 1.5px solid var(--gray-200);
    font-size: 12px;
    font-weight: 700;
    color: var(--gray-500);
    white-space: nowrap;
    flex-shrink: 0
  }

  .pkd-addon-right {
    border-right: none;
    border-left: 1.5px solid var(--gray-200)
  }

  .pkd-input {
    flex: 1;
    border: none;
    outline: none;
    padding: 10px 12px;
    font-size: 13.5px;
    font-weight: 600;
    color: var(--gray-800);
    background: transparent;
    min-width: 0
  }

  .pkd-input::placeholder {
    color: var(--gray-300);
    font-weight: 400
  }

  .pkd-input:read-only {
    color: var(--gray-500);
    cursor: default
  }

  /* TOTAL BANNER */
  .pkd-total-banner {
    background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
    border: 1.5px solid #c7d2fe;
    border-radius: var(--r-lg);
    padding: 16px 20px;
    margin-bottom: 20px
  }

  .pkd-total-title {
    text-align: center;
    font-size: 12px;
    font-weight: 800;
    color: var(--navy);
    text-transform: uppercase;
    letter-spacing: .6px;
    margin-bottom: 14px
  }

  .pkd-total-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px
  }

  @media(max-width:480px) {
    .pkd-total-grid {
      grid-template-columns: 1fr
    }
  }

  .pkd-total-item {
    background: white;
    border-radius: var(--r);
    padding: 12px 14px;
    box-shadow: var(--sh-sm);
    border: 1.5px solid var(--gray-100)
  }

  .pkd-total-entity {
    font-size: 11px;
    font-weight: 700;
    color: var(--gray-400);
    margin-bottom: 4px;
    text-transform: uppercase;
    letter-spacing: .5px
  }

  .pkd-total-val {
    font-size: 18px;
    font-weight: 800;
    color: var(--blue);
    display: flex;
    align-items: center;
    gap: 6px
  }

  .pkd-total-val span {
    font-size: 12px;
    font-weight: 600;
    color: var(--gray-400)
  }

  /* SAVE STATUS */
  .pkd-save-status {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    font-size: 12px;
    font-weight: 600;
    padding: 5px 12px;
    border-radius: 100px;
    transition: var(--tr)
  }

  .save-idle {
    background: var(--gray-100);
    color: var(--gray-400)
  }

  .save-typing {
    background: var(--warning-bg);
    color: #92400e
  }

  .save-saving {
    background: #dbeafe;
    color: #1e40af
  }

  .save-saved {
    background: var(--success-bg);
    color: #065f46
  }

  .save-error {
    background: var(--danger-bg);
    color: #991b1b
  }

  @keyframes spin {
    to {
      transform: rotate(360deg)
    }
  }

  .pkd-spinner {
    width: 13px;
    height: 13px;
    border: 2px solid currentColor;
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin .6s linear infinite;
    flex-shrink: 0;
    display: inline-block
  }

  /* SKELETON */
  @keyframes skShimmer {
    0% {
      background-position: 200% 0
    }

    100% {
      background-position: -200% 0
    }
  }

  .skel {
    background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
    background-size: 200% 100%;
    animation: skShimmer 1.4s infinite;
    border-radius: var(--r-sm)
  }

  .skel-card {
    background: white;
    border-radius: var(--r-lg);
    border: 1.5px solid var(--gray-200);
    overflow: hidden;
    margin-bottom: 20px
  }

  .skel-header {
    height: 46px
  }

  .skel-body {
    padding: 20px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px
  }

  .skel-field {
    height: 42px
  }

  /* TINDAK */
  .tindak-card {
    background: var(--gray-50);
    border-radius: var(--r-lg);
    border: 1.5px solid var(--gray-200);
    padding: 16px;
    margin-bottom: 12px
  }

  .tindak-label {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .6px;
    color: var(--gray-500);
    margin-bottom: 8px
  }

  .tindak-content {
    font-size: 13.5px;
    color: var(--gray-700);
    line-height: 1.7;
    background: white;
    border: 1.5px solid var(--gray-200);
    border-radius: var(--r-sm);
    padding: 10px 14px
  }

  .tindak-empty {
    text-align: center;
    padding: 30px 16px;
    color: var(--gray-400)
  }

  /* PAGE NAV */
  .cardpilihfaktor {
    border: 1px solid var(--gray-200);
    border-radius: var(--r);
    max-width: 720px;
    margin: 0 auto
  }

  .cardpilihfaktor-header {
    text-align: center;
    background: var(--gray-50);
    padding: 6px;
    border-bottom: 1px solid var(--gray-200)
  }

  .cardpilihfaktor-header h6 {
    margin: 0;
    font-size: .9rem;
    font-weight: 700
  }

  .cardpilihfaktor-body {
    padding: 8px 10px
  }

  .nav-pages-wrap {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    justify-content: center
  }

  .nav-page-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    height: 32px;
    padding: 0 6px;
    border-radius: 6px;
    border: 1.5px solid var(--gray-200);
    background: white;
    color: var(--gray-700);
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    transition: var(--tr)
  }

  .nav-page-btn:hover {
    background: var(--brand-bg);
    border-color: var(--brand-light);
    color: var(--brand);
    text-decoration: none
  }

  .nav-page-btn.active {
    background: var(--grad);
    color: white;
    border-color: var(--blue)
  }

  /* BOTTOM BAR */
  .pkd-bottom-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    flex-wrap: wrap;
    padding: 20px 0 8px
  }

  /* KOMENTAR */
  .notif-badge {
    position: absolute;
    top: -7px;
    right: -7px;
    background: var(--danger);
    color: #fff;
    border-radius: 50%;
    padding: 2px 5px;
    font-size: 10px;
    font-weight: 700;
    min-width: 17px;
    text-align: center;
    line-height: 1.3;
    box-shadow: 0 2px 6px rgba(239, 68, 68, .5);
    animation: pulse 2s infinite
  }

  @keyframes pulse {

    0%,
    100% {
      box-shadow: 0 0 0 0 rgba(239, 68, 68, .4)
    }

    50% {
      box-shadow: 0 0 0 5px rgba(239, 68, 68, 0)
    }
  }

  .btn-komentar-global-btn {
    position: relative;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: white;
    border: none;
    color: var(--blue);
    font-size: 17px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 10px rgba(0, 0, 0, .18);
    cursor: pointer;
    transition: all .25s cubic-bezier(.4, 0, .2, 1);
    outline: none
  }

  .btn-komentar-global-btn:hover {
    background: var(--brand-bg);
    color: var(--brand);
    transform: scale(1.13)
  }

  .btn-komentar-global-btn.has-unread {
    animation: btn-shake 1.8s ease infinite;
    background: var(--brand-bg);
    color: var(--brand)
  }

  @keyframes btn-shake {

    0%,
    100% {
      transform: rotate(0)scale(1)
    }

    10% {
      transform: rotate(-14deg)scale(1.08)
    }

    20% {
      transform: rotate(12deg)scale(1.08)
    }

    30% {
      transform: rotate(-10deg)scale(1.05)
    }

    40% {
      transform: rotate(8deg)scale(1.05)
    }

    50% {
      transform: rotate(0)scale(1)
    }
  }

  .btn-komentar-ripple {
    position: absolute;
    inset: -4px;
    border-radius: 50%;
    border: 2px solid var(--brand-light);
    opacity: 0;
    pointer-events: none
  }

  .btn-komentar-global-btn.has-unread .btn-komentar-ripple {
    animation: ripple-out 1.8s ease infinite
  }

  @keyframes ripple-out {
    0% {
      transform: scale(.9);
      opacity: .7
    }

    100% {
      transform: scale(1.6);
      opacity: 0
    }
  }

  .komentar-modal-content {
    border-radius: 18px !important;
    overflow: hidden;
    border: none;
    box-shadow: 0 20px 60px rgba(0, 0, 0, .18) !important
  }

  .komentar-modal-header {
    background: var(--grad);
    padding: 18px 22px !important;
    border: none !important;
    display: flex;
    align-items: center;
    justify-content: space-between
  }

  .komentar-modal-title-wrap {
    display: flex;
    align-items: center;
    gap: 12px
  }

  .komentar-modal-icon-wrap {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: rgba(255, 255, 255, .18);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: white;
    flex-shrink: 0
  }

  .komentar-modal-title {
    font-size: 14px;
    font-weight: 800;
    color: white
  }

  .komentar-close-btn {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .15);
    border: none;
    color: white;
    font-size: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all .2s;
    flex-shrink: 0
  }

  .komentar-close-btn:hover {
    background: rgba(255, 255, 255, .3);
    transform: rotate(90deg)
  }

  .komentar-modal-body {
    padding: 18px 20px !important;
    background: var(--gray-50)
  }

  .komentar-modal-footer {
    background: white;
    border-top: 1px solid var(--gray-100) !important;
    padding: 10px 20px !important
  }

  .komentar-list-label {
    font-size: 11px;
    font-weight: 700;
    color: var(--gray-500);
    text-transform: uppercase;
    letter-spacing: .8px;
    margin-bottom: 8px
  }

  .komentar-list-wrapper {
    background: white;
    border: 1.5px solid var(--gray-200);
    border-radius: 12px;
    max-height: 300px;
    overflow-y: auto;
    padding: 10px;
    margin-bottom: 14px;
    scroll-behavior: smooth
  }

  .komentar-list-wrapper::-webkit-scrollbar {
    width: 5px
  }

  .komentar-list-wrapper::-webkit-scrollbar-thumb {
    background: var(--gray-200);
    border-radius: 10px
  }

  .komentar-loading,
  .komentar-empty {
    text-align: center;
    color: var(--gray-400);
    padding: 24px 0;
    font-size: 13px
  }

  .km-item {
    background: var(--gray-50);
    border-left: 3px solid var(--brand-light);
    border-radius: 10px;
    padding: 9px 12px;
    margin-bottom: 7px;
    box-shadow: var(--sh-sm);
    animation: kmIn .25s ease both
  }

  .km-item.km-own {
    border-left-color: var(--success);
    background: #f0fdf4
  }

  @keyframes kmIn {
    from {
      opacity: 0;
      transform: translateY(6px)
    }

    to {
      opacity: 1;
      transform: translateY(0)
    }
  }

  .km-meta {
    display: flex;
    justify-content: space-between;
    margin-bottom: 4px;
    flex-wrap: wrap;
    gap: 3px
  }

  .km-author {
    font-weight: 700;
    font-size: 12px;
    color: var(--gray-800);
    display: flex;
    align-items: center;
    gap: 5px
  }

  .km-you {
    font-size: 9px;
    background: var(--success);
    color: white;
    padding: 1px 5px;
    border-radius: 100px;
    font-weight: 700
  }

  .km-time {
    font-size: 11px;
    color: var(--gray-400)
  }

  .km-text {
    font-size: 13px;
    white-space: pre-wrap;
    margin: 0;
    color: var(--gray-700);
    line-height: 1.5
  }

  .komentar-input-wrap {
    background: white;
    border: 1.5px solid var(--gray-200);
    border-radius: 12px;
    padding: 12px;
    transition: border-color .2s
  }

  .komentar-input-wrap:focus-within {
    border-color: var(--brand);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, .1)
  }

  .komentar-input-label {
    font-size: 11px;
    font-weight: 700;
    color: var(--gray-500);
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: .6px
  }

  .komentar-textarea {
    width: 100%;
    border: none;
    outline: none;
    resize: none;
    font-size: 13px;
    color: var(--gray-700);
    line-height: 1.6;
    background: transparent
  }

  .komentar-input-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 6px;
    padding-top: 6px;
    border-top: 1px solid var(--gray-100)
  }

  .komentar-hint {
    font-size: 11px;
    color: var(--gray-400)
  }

  .komentar-char {
    font-size: 11px;
    color: var(--gray-400);
    font-weight: 600;
    transition: color .2s
  }

  .komentar-char.danger {
    color: var(--danger)
  }

  .modal-header-dir {
    background: var(--grad);
    color: white
  }

  .modal-content {
    border-radius: var(--r-lg);
    overflow: hidden;
    border: none;
    box-shadow: var(--sh-lg)
  }

  .modal-body {
    padding: 18px 22px !important
  }

  .modal-footer {
    padding: 12px 22px !important
  }

  .modal-body .form-group {
    margin-bottom: 14px !important
  }

  .modal-body .form-control {
    width: 100% !important;
    box-sizing: border-box !important;
    border-radius: 8px !important;
    border: 1.5px solid var(--gray-200) !important;
    padding: 8px 12px !important;
    font-size: 13px !important;
    transition: var(--tr) !important
  }

  .modal-body .form-control:focus {
    border-color: var(--brand) !important;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, .15) !important;
    outline: none !important
  }

  .modal-dialog {
    margin: 16px auto !important;
    max-width: 700px !important
  }

  .t-item {
    background: white;
    border-radius: var(--r);
    padding: 12px 16px;
    margin-bottom: 8px;
    box-shadow: var(--sh-lg);
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    animation: tIn .3s ease;
    pointer-events: all;
    border-left: 4px solid
  }

  .t-item.t-success {
    border-color: var(--success);
    color: #065f46
  }

  .t-item.t-error {
    border-color: var(--danger);
    color: #991b1b
  }

  .t-item.t-info {
    border-color: #06b6d4;
    color: #164e63
  }

  @keyframes tIn {
    from {
      transform: translateX(110%);
      opacity: 0
    }

    to {
      transform: translateX(0);
      opacity: 1
    }
  }

  .blk-disabled {
    opacity: .4;
    pointer-events: none
  }

  .card-header {
    border-radius: var(--r-lg) var(--r-lg) 0 0 !important
  }

  @media(max-width:576px) {
    .modal-dialog {
      margin: 8px !important;
      max-width: calc(100% - 16px) !important
    }

    .modal-body {
      padding: 14px !important
    }

    .approval-actions-row,
    .approval-badges-row {
      flex-direction: column;
      align-items: center
    }

    .action-card {
      width: 100%;
      max-width: 340px
    }

    .nav-pages-wrap {
      gap: 3px
    }

    .nav-page-btn {
      min-width: 28px;
      height: 28px;
      font-size: 11px
    }

    .skel-body {
      grid-template-columns: 1fr
    }
  }
</style>