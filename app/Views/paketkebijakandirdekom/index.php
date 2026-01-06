<div class="alert beautiful-alert my-4">
    <i class="fas fa-info-circle alert-icon"></i> <?php if (isset($bprData) && isset($periodeDetail)): ?>
        <strong><?= esc($bprData['namabpr'] ?? 'Nama BPR') ?></strong> - Periode Pelaporan Tahun
        <?= esc($periodeDetail['tahun']) ?>
    <?php elseif (isset($periodeDetail)): ?>
        <strong>Periode:</strong> Tahun <?= esc($periodeDetail['tahun']) ?>
    <?php else: ?>
        <strong>Periode belum ditentukan</strong>
    <?php endif; ?>
</div>
<?php if (session()->getFlashdata('message')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">&times;</button>
        <strong><?= esc(session()->getFlashdata('message')); ?></strong>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('err')): ?>
    <div class="alert alert-danger" role="alert"><?= esc(session()->getFlashdata('err')); ?></div>
<?php endif; ?>

<!-- Begin Page Content -->
<div class="container-fluid">
    <div id="ajax-response-message"></div>
    <div class="card">
        <div class="card-body">
            <?php
            // pastikan variabel ada dan tidak null
            $kodebpr = $kodebpr ?? null;
            $periodeId = $periodeId ?? null;
            ?>
            <div class="card-body custom-badge-container">
                <?php
                // Check if all accdekom records are approved
                $allAccdekomApproved = true;
                if (!empty($accdekomData)) {
                    foreach ($accdekomData as $item) {
                        if ($item['accdekom'] != 1) {
                            $allAccdekomApproved = false;
                            break;
                        }
                    }
                }

                // Check if all accdirut records are approved
                $allAccdirutApproved = true;
                if (!empty($accdirutData)) {
                    foreach ($accdirutData as $item) {
                        if ($item['is_approved'] != 1) {
                            $allAccdirutApproved = false;
                            break;
                        }
                    }
                }
                ?>

                <?php if (!empty($accdekomData)): ?>
                    <?php if ($allAccdekomApproved): ?>
                        <span class="badge badge-success" style="font-size: 14px;">
                            Disetujui oleh <strong>Komisaris Utama</strong><br>
                            Tanggal: <?php
                            $date = strtotime($accdekomData[count($accdekomData) - 1]['accdekom_at']);
                            setlocale(LC_TIME, 'id_ID.UTF-8', 'id_ID', 'ind');
                            echo strftime("%d %B %Y %H:%M:%S", $date);
                            ?>
                        </span>
                    <?php else: ?>
                        <span class="badge badge-secondary" style="font-size: 14px;">
                            Belum Disetujui Seluruhnya<br>Oleh Komisaris Utama
                        </span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if (!empty($accdirutData)): ?>
                    <?php
                    $item = $accdirutData[0]; // Ambil data pertama
                    if ($item['is_approved'] == 1): ?>
                        <span class="badge badge-success" style="font-size: 14px;">
                            Disetujui oleh <strong>Direktur Utama</strong><br>
                            Tanggal: <?php
                            setlocale(LC_TIME, 'id_ID.UTF-8', 'id_ID', 'ind');
                            $date = strtotime($item['approved_at']);
                            echo strftime("%d %B %Y %H:%M:%S", $date);
                            ?>
                        </span>
                    <?php else: ?>
                        <span class="badge badge-secondary" style="font-size: 14px;">
                            Belum Disetujui Seluruhnya<br>Oleh Direktur Utama
                        </span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <h1 class="h3 mb-4 text-gray-800 text-center"><?= $judul; ?><br>(E0500)</h1>
            <form id="paketKebijakanForm">
                <input type="hidden" name="user_id" id="user_id" value="<?= esc($userId ?? '-') ?>">
                <input type="hidden" name="fullname" id="fullname" value="<?= esc($fullname ?? '-') ?>">

                <!-- 1.1. Gaji Direksi dan Dewan Komisaris -->
                <table class="table table-info table-hover mb-4">
                    <thead class="thead-primary">
                        <tr>
                            <th>1.1. Gaji Direksi dan Dewan Komisaris selama 1 tahun</th>
                        </tr>
                    </thead>
                </table>
                <div class="row g-2">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="penerimagajidir">Direksi yang menerima gaji:</label>
                            <div class="input-group">
                                <input type="number" name="penerimagajidir" id="penerimagajidir" class="form-control"
                                    style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['penerimagajidir'] ?? '') ?>" required>
                                <span class="input-group-text">Orang</span>
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="penerimagajidekom">Dewan Komisaris yang menerima gaji:</label>
                            <div class="input-group">
                                <input type="number" name="penerimagajidekom" id="penerimagajidekom"
                                    class="form-control" style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['penerimagajidekom'] ?? '') ?>" required>
                                <span class="input-group-text">Orang</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="nominalgajidir">Nominal gaji yang diterima Direksi:</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="nominalgajidir" id="nominalgajidir"
                                    class="form-control format-currency" style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['nominalgajidir'] ?? '') ?>" required>
                                <input type="hidden" name="nominalgajidir" id="nominalgajidir_raw"
                                    value="<?= esc($paketkebijakandirdekom[0]['nominalgajidir'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="nominalgajidekom">Nominal gaji yang diterima Dewan Komisaris:</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="nominalgajidekom" id="nominalgajidekom"
                                    class="form-control format-currency" style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['nominalgajidekom'] ?? '') ?>" required>
                                <input type="hidden" name="nominalgajidekom" id="nominalgajidekom_raw"
                                    value="<?= esc($paketkebijakandirdekom[0]['nominalgajidekom'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                </div>

                <!-- 1.2. Tunjangan -->
                <table class="table table-info table-hover mt-5">
                    <thead class="thead-primary">
                        <tr>
                            <th>1.2. Tunjangan Direksi dan Dewan Komisaris selama 1 tahun</th>
                        </tr>
                    </thead>
                </table>
                <div class="row g-2 mt-4">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="terimatunjangandir">Direksi yang menerima tunjangan:</label>
                            <div class="input-group">
                                <input type="number" name="terimatunjangandir" id="terimatunjangandir"
                                    class="form-control" style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['terimatunjangandir'] ?? '') ?>" required>
                                <span class="input-group-text">Orang</span>
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="terimatunjangandekom">Dewan Komisaris yang menerima tunjangan:</label>
                            <div class="input-group">
                                <input type="number" name="terimatunjangandekom" id="terimatunjangandekom"
                                    class="form-control" style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['terimatunjangandekom'] ?? '') ?>"
                                    required>
                                <span class="input-group-text">Orang</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="nominaltunjangandir">Nominal tunjangan yang diterima Direksi:</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="nominaltunjangandir" id="nominaltunjangandir"
                                    class="form-control format-currency" style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['nominaltunjangandir'] ?? '') ?>"
                                    required>
                                <input type="hidden" name="nominaltunjangandir" id="nominaltunjangandir_raw"
                                    value="<?= esc($paketkebijakandirdekom[0]['nominaltunjangandir'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="nominaltunjangandekom">Nominal tunjangan yang diterima Dewan Komisaris:</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="nominaltunjangandekom" id="nominaltunjangandekom"
                                    class="form-control format-currency" style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['nominaltunjangandekom'] ?? '') ?>"
                                    required>
                                <input type="hidden" name="nominaltunjangandekom" id="nominaltunjangandekom_raw"
                                    value="<?= esc($paketkebijakandirdekom[0]['nominaltunjangandekom'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 1.3. Tantiem -->
                <table class="table table-info table-hover mt-5">
                    <thead class="thead-primary">
                        <tr>
                            <th>1.3. Tantiem Direksi dan Dewan Komisaris selama 1 tahun</th>
                        </tr>
                    </thead>
                </table>
                <div class="row g-2 mt-4">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="terimatantiemdir">Direksi yang menerima tantiem:</label>
                            <div class="input-group">
                                <input type="number" name="terimatantiemdir" id="terimatantiemdir" class="form-control"
                                    style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['terimatantiemdir'] ?? '') ?>" required>
                                <span class="input-group-text">Orang</span>
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="terimatantiemdekom">Dewan Komisaris yang menerima tantiem:</label>
                            <div class="input-group">
                                <input type="number" name="terimatantiemdekom" id="terimatantiemdekom"
                                    class="form-control" style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['terimatantiemdekom'] ?? '') ?>" required>
                                <span class="input-group-text">Orang</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="nominaltantiemdir">Nominal tantiem yang diterima Direksi:</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="nominaltantiemdir" id="nominaltantiemdir"
                                    class="form-control format-currency" style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['nominaltantiemdir'] ?? '') ?>" required>
                                <input type="hidden" name="nominaltantiemdir" id="nominaltantiemdir_raw"
                                    value="<?= esc($paketkebijakandirdekom[0]['nominaltantiemdir'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="nominaltantiemdekom">Nominal tantiem yang diterima Dewan Komisaris:</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="nominaltantiemdekom" id="nominaltantiemdekom"
                                    class="form-control format-currency" style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['nominaltantiemdekom'] ?? '') ?>"
                                    required>
                                <input type="hidden" name="nominaltantiemdekom" id="nominaltantiemdekom_raw"
                                    value="<?= esc($paketkebijakandirdekom[0]['nominaltantiemdekom'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 1.4. Kompensasi Saham -->
                <table class="table table-info table-hover mt-5">
                    <thead class="thead-primary">
                        <tr>
                            <th>1.4. Kompensasi berbasis saham Direksi dan Dewan Komisaris selama 1 tahun</th>
                        </tr>
                    </thead>
                </table>
                <div class="row g-2 mt-4">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="terimashmdir">Direksi yang menerima saham:</label>
                            <div class="input-group">
                                <input type="number" name="terimashmdir" id="terimashmdir" class="form-control"
                                    style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['terimashmdir'] ?? '') ?>" required>
                                <span class="input-group-text">Orang</span>
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="terimashmdekom">Dewan Komisaris yang menerima saham:</label>
                            <div class="input-group">
                                <input type="number" name="terimashmdekom" id="terimashmdekom" class="form-control"
                                    style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['terimashmdekom'] ?? '') ?>" required>
                                <span class="input-group-text">Orang</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="nominalshmdir">Nominal saham yang diterima Direksi:</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="nominalshmdir" id="nominalshmdir"
                                    class="form-control format-currency" style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['nominalshmdir'] ?? '') ?>" required>
                                <input type="hidden" name="nominalshmdir" id="nominalshmdir_raw"
                                    value="<?= esc($paketkebijakandirdekom[0]['nominalshmdir'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="nominalshmdekom">Nominal saham yang diterima Dewan Komisaris:</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="nominalshmdekom" id="nominalshmdekom"
                                    class="form-control format-currency" style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['nominalshmdekom'] ?? '') ?>" required>
                                <input type="hidden" name="nominalshmdekom" id="nominalshmdekom_raw"
                                    value="<?= esc($paketkebijakandirdekom[0]['nominalshmdekom'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 1.5. Remunerasi Lainnya -->
                <table class="table table-info table-hover mt-5">
                    <thead class="thead-primary">
                        <tr>
                            <th>1.5. Remunerasi lainnya Direksi dan Dewan Komisaris selama 1 tahun</th>
                        </tr>
                    </thead>
                </table>
                <div class="row g-2 mt-4">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="terimaremunlaindir">Direksi yang menerima remunerasi:</label>
                            <div class="input-group">
                                <input type="number" name="terimaremunlaindir" id="terimaremunlaindir"
                                    class="form-control" style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['terimaremunlaindir'] ?? '') ?>" required>
                                <span class="input-group-text">Orang</span>
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="terimaremunlaindekom">Dewan Komisaris yang menerima remunerasi:</label>
                            <div class="input-group">
                                <input type="number" name="terimaremunlaindekom" id="terimaremunlaindekom"
                                    class="form-control" style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['terimaremunlaindekom'] ?? '') ?>"
                                    required>
                                <span class="input-group-text">Orang</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="nominalremunlaindir">Nominal remunerasi yang diterima Direksi:</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="nominalremunlaindir" id="nominalremunlaindir"
                                    class="form-control format-currency" style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['nominalremunlaindir'] ?? '') ?>"
                                    required>
                                <input type="hidden" name="nominalremunlaindir" id="nominalremunlaindir_raw"
                                    value="<?= esc($paketkebijakandirdekom[0]['nominalremunlaindir'] ?? '') ?>">
                            </div>

                        </div>
                        <div class="form-group mb-3">
                            <label for="nominalremunlaindekom">Nominal remunerasi yang diterima Dewan Komisaris:</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="nominalremunlaindekom" id="nominalremunlaindekom"
                                    class="form-control format-currency" style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['nominalremunlaindekom'] ?? '') ?>"
                                    required>
                                <input type="hidden" name="nominalremunlaindekom" id="nominalremunlaindekom_raw"
                                    value="<?= esc($paketkebijakandirdekom[0]['nominalremunlaindekom'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Remunerasi -->
                <table class="table table-info table-hover mt-5">
                    <thead class="thead-primary">
                        <tr>
                            <th class="text-center">Total Remunerasi</th>
                        </tr>
                    </thead>
                </table>
                <div class="row g-2 mt-4">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="totalremundir">Direksi :</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="totalremundir" id="totalremundir"
                                    class="form-control format-currency" style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['totalremundir'] ?? '') ?>" required
                                    readonly>
                                <input type="hidden" name="totalremundir" id="totalremundir_raw"
                                    value="<?= esc($paketkebijakandirdekom[0]['totalremundir'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="totalremundekom">Dewan Komisaris:</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="totalremundekom" id="totalremundekom"
                                    class="form-control format-currency" style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['totalremundekom'] ?? '') ?>" required
                                    readonly>
                                <input type="hidden" name="totalremundekom" id="totalremundekom_raw"
                                    value="<?= esc($paketkebijakandirdekom[0]['totalremundekom'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2.1. Perumahan -->
                <table class="table table-info table-hover mt-5">
                    <thead class="thead-primary">
                        <tr>
                            <th>2.1. Perumahan Direksi dan Dewan Komisaris selama 1 tahun</th>
                        </tr>
                    </thead>
                </table>
                <div class="row g-2 mt-4">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="terimarumahdir">Direksi yang menerima tunjangan rumah:</label>
                            <div class="input-group">
                                <input type="number" name="terimarumahdir" id="terimarumahdir" class="form-control"
                                    style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['terimarumahdir'] ?? '') ?>" required>
                                <span class="input-group-text">Orang</span>
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="terimarumahdekom">Dewan Komisaris yang menerima tunjangan rumah:</label>
                            <div class="input-group">
                                <input type="number" name="terimarumahdekom" id="terimarumahdekom" class="form-control"
                                    style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['terimarumahdekom'] ?? '') ?>" required>
                                <span class="input-group-text">Orang</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="nominalrumahdir">Nominal tunjangan rumah yang diterima Direksi:</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="nominalrumahdir" id="nominalrumahdir"
                                    class="form-control format-currency" style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['nominalrumahdir'] ?? '') ?>" required>
                                <input type="hidden" name="nominalrumahdir" id="nominalrumahdir_raw"
                                    value="<?= esc($paketkebijakandirdekom[0]['nominalrumahdir'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="nominalrumahdekom">Nominal tunjangan rumah yang diterima Dewan
                                Komisaris:</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="nominalrumahdekom" id="nominalrumahdekom"
                                    class="form-control format-currency" style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['nominalrumahdekom'] ?? '') ?>" required>
                                <input type="hidden" name="nominalrumahdekom" id="nominalrumahdekom_raw"
                                    value="<?= esc($paketkebijakandirdekom[0]['nominalrumahdekom'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2.2. Transportasi -->
                <table class="table table-info table-hover mt-5">
                    <thead class="thead-primary">
                        <tr>
                            <th>2.2. Transportasi Direksi dan Dewan Komisaris selama 1 tahun</th>
                        </tr>
                    </thead>
                </table>
                <div class="row g-2 mt-4">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="terimatransportdir">Direksi yang menerima tunjangan transportasi:</label>
                            <div class="input-group">
                                <input type="number" name="terimatransportdir" id="terimatransportdir"
                                    class="form-control" style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['terimatransportdir'] ?? '') ?>" required>
                                <span class="input-group-text">Orang</span>
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="terimatransportdekom">Dewan Komisaris yang menerima tunjangan
                                transportasi:</label>
                            <div class="input-group">
                                <input type="number" name="terimatransportdekom" id="terimatransportdekom"
                                    class="form-control" style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['terimatransportdekom'] ?? '') ?>"
                                    required>
                                <span class="input-group-text">Orang</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="nominaltransportdir">Nominal tunjangan transportasi yang diterima
                                Direksi:</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="nominaltransportdir" id="nominaltransportdir"
                                    class="form-control format-currency" style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['nominaltransportdir'] ?? '') ?>"
                                    required>
                                <input type="hidden" name="nominaltransportdir" id="nominaltransportdir_raw"
                                    value="<?= esc($paketkebijakandirdekom[0]['nominaltransportdir'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="nominaltransportdekom">Nominal tunjangan transportasi yang diterima Dewan
                                Komisaris:</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="nominaltransportdekom" id="nominaltransportdekom"
                                    class="form-control format-currency" style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['nominaltransportdekom'] ?? '') ?>"
                                    required>
                                <input type="hidden" name="nominaltransportdekom" id="nominaltransportdekom_raw"
                                    value="<?= esc($paketkebijakandirdekom[0]['nominaltransportdekom'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2.3. Asuransi Kesehatan -->
                <table class="table table-info table-hover mt-5">
                    <thead class="thead-primary">
                        <tr>
                            <th>2.3. Asuransi Kesehatan Direksi dan Dewan Komisaris selama 1 tahun</th>
                        </tr>
                    </thead>
                </table>
                <div class="row g-2 mt-4">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="terimaasuransidir">Direksi yang menerima asuransi kesehatan:</label>
                            <div class="input-group">
                                <input type="number" name="terimaasuransidir" id="terimaasuransidir"
                                    class="form-control" style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['terimaasuransidir'] ?? '') ?>" required>
                                <span class="input-group-text">Orang</span>
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="terimaasuransidekom">Dewan Komisaris yang menerima asuransi kesehatan:</label>
                            <div class="input-group">
                                <input type="number" name="terimaasuransidekom" id="terimaasuransidekom"
                                    class="form-control" style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['terimaasuransidekom'] ?? '') ?>"
                                    required>
                                <span class="input-group-text">Orang</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="nominalasuransidir">Nominal asuransi kesehatan yang diterima Direksi:</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="nominalasuransidir" id="nominalasuransidir"
                                    class="form-control format-currency" style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['nominalasuransidir'] ?? '') ?>" required>
                                <input type="hidden" name="nominalasuransidir" id="nominalasuransidir_raw"
                                    value="<?= esc($paketkebijakandirdekom[0]['nominalasuransidir'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="nominalasuransidekom">Nominal asuransi kesehatan yang diterima Dewan
                                Komisaris:</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="nominalasuransidekom" id="nominalasuransidekom"
                                    class="form-control format-currency" style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['nominalasuransidekom'] ?? '') ?>"
                                    required>
                                <input type="hidden" name="nominalasuransidekom" id="nominalasuransidekom_raw"
                                    value="<?= esc($paketkebijakandirdekom[0]['nominalasuransidekom'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2.4. Fasilitas Lainnya -->
                <table class="table table-info table-hover mt-5">
                    <thead class="thead-primary">
                        <tr>
                            <th>2.4. Fasilitas Lainnya Direksi dan Dewan Komisaris selama 1 tahun</th>
                        </tr>
                    </thead>
                </table>
                <div class="row g-2 mt-4">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="terimafasilitasdir">Direksi yang menerima fasilitas lainya:</label>
                            <div class="input-group">
                                <input type="number" name="terimafasilitasdir" id="terimafasilitasdir"
                                    class="form-control" style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['terimafasilitasdir'] ?? '') ?>" required>
                                <span class="input-group-text">Orang</span>
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="terimafasilitasdekom">Dewan Komisaris yang menerima fasilitas lain:</label>
                            <div class="input-group">
                                <input type="number" name="terimafasilitasdekom" id="terimafasilitasdekom"
                                    class="form-control" style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['terimafasilitasdekom'] ?? '') ?>"
                                    required>
                                <span class="input-group-text">Orang</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="nominalfasilitasdir">Nominal fasilitas lain yang diterima Direksi:</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="nominalfasilitasdir" id="nominalfasilitasdir"
                                    class="form-control format-currency" style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['nominalfasilitasdir'] ?? '') ?>"
                                    required>
                                <input type="hidden" name="nominalfasilitasdir" id="nominalfasilitasdir_raw"
                                    value="<?= esc($paketkebijakandirdekom[0]['nominalfasilitasdir'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="nominalfasilitasdekom">Nominal fasilitas lain yang diterima Dewan
                                Komisaris:</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="nominalfasilitasdekom" id="nominalfasilitasdekom"
                                    class="form-control format-currency" style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['nominalfasilitasdekom'] ?? '') ?>"
                                    required>
                                <input type="hidden" name="nominalfasilitasdekom" id="nominalfasilitasdekom_raw"
                                    value="<?= esc($paketkebijakandirdekom[0]['nominalfasilitasdekom'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <table class="table table-info table-hover mt-5">
                    <thead class="thead-primary">
                        <tr>
                            <th class="text-center">Total Fasilitas Lain</th>
                        </tr>
                    </thead>
                </table>
                <div class="row g-2 mt-4">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="totalfasdir">Direksi :</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="totalfasdir" id="totalfasdir"
                                    class="form-control format-currency" style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['totalfasdir'] ?? '') ?>" required
                                    readonly>
                                <input type="hidden" name="totalfasdir" id="totalfasdir_raw"
                                    value="<?= esc($paketkebijakandirdekom[0]['totalfasdir'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="totalfasdekom">Dewan Komisaris:</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="totalfasdekom" id="totalfasdekom"
                                    class="form-control format-currency" style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['totalfasdekom'] ?? '') ?>" required
                                    readonly>
                                <input type="hidden" name="totalfasdekom" id="totalfasdekom_raw"
                                    value="<?= esc($paketkebijakandirdekom[0]['totalfasdekom'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <table class="table table-info table-hover mt-5">
                    <thead class="thead-primary">
                        <tr>
                            <th class="text-center">Total Remunerasi dan Fasilitas Lain yang diterima Direksi dan Dewan
                                Komisaris selama 1 Tahun</th>
                        </tr>
                    </thead>
                </table>
                <div class="row g-2 mt-4">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="totaldir" class="text-center">Direksi :</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="totaldir" id="totaldir" class="form-control format-currency"
                                    style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['totaldir'] ?? '') ?>" required readonly>
                                <input type="hidden" name="totaldir" id="totaldir_raw"
                                    value="<?= esc($paketkebijakandirdekom[0]['totaldir'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="totaldekom">Dewan Komisaris:</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="totaldekom" id="totaldekom"
                                    class="form-control format-currency" style="height: 45px"
                                    value="<?= esc($paketkebijakandirdekom[0]['totaldekom'] ?? '') ?>" required
                                    readonly>
                                <input type="hidden" name="totaldekom" id="totaldekom_raw"
                                    value="<?= esc($paketkebijakandirdekom[0]['totaldekom'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <table class="table table-bordered table-hover mb-5">
                    <?php
                    $tindaklanjut = '';
                    $id = '';

                    foreach ($penjelastindak as $item) {
                        if ($item['id']) {
                            $id = $item['id'] ?? '';
                            $tindaklanjut = $item['tindaklanjut'] ?? '';
                            break;
                        }
                    }
                    ?>

                    <div>
                        <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                            <div class="btn-group" role="group" aria-label="Button group">
                                <?php if (empty($penjelastindak)): ?>
                                    <button type="button" class="btn btn-primary3 btn-sm" data-toggle="modal"
                                        data-target="#modalTambahketerangan">
                                        <i class="fa fa-plus"></i> Penjelasan Lebih Lanjut
                                    </button>
                                <?php else: ?>
                                    <?php foreach ($penjelastindak as $row): ?>
                                        <button type="button" class="btn btn-primary2 btn-sm" data-toggle="modal"
                                            data-target="#modaleditketerangan" id="btn-edit" data-id="<?= esc($row['id']); ?>"
                                            data-tindaklanjut="<?= esc($row['tindaklanjut'] ?? ''); ?>">
                                            <i class="fa fa-edit"></i>Ubah Penjelasan Lebih Lanjut
                                        </button>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <tbody>
                        <?php if (empty($penjelastindak)) { ?>
                            <tr>
                                <th class="table-info" style="width: 30%; color: black;">Penjelasan Lebih Lanjut mengenai
                                    Paket/Kebijakan Remunerasi dan Fasilitas Lain bagi Direksi dan Dewan Komisaris
                                    (Opsional) :
                                </th>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-center">
                                    <em>Tidak ada penjelasan lebih lanjut</em>
                                </td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($penjelastindak as $row): ?>
                                <tr>
                                    <th colspan="2" class="table-info" style="width: 30%; color: black;">Penjelasan Lebih Lanjut
                                        mengenai
                                        Paket/Kebijakan Remunerasi dan Fasilitas Lain bagi Direksi dan Dewan Komisaris
                                        (Opsional) :</th>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <?= isset($row['tindaklanjut']) ? esc($row['tindaklanjut']) : 'Data tidak tersedia'; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <button type="button" class="btn btn-outline-danger btn-sm" id="btn-setnulltindak"
                                            data-id="<?= $row['id']; ?>"><i class="fa fa-trash"></i></button>
                                    </td>
                                </tr>
                                <tr height="40">
                                    <td colspan="3" style="background-color: white; border-color: white;"></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php } ?>
                    </tbody>
                </table>
                <div class="row" style="display: flex; justify-content: space-between;">
                    <?php if (($userInGroupAdmin || $userInGroupDekom) && !empty($accdekomData)): ?>
                        <div class="col-md-3">
                            <div class="card shadow-sm approval-card">
                                <div class="card-body approval-card-body">
                                    <div class="approval-badge-container"> <span class="badge approval-badge">Approval
                                            Komisaris
                                            Utama</span> </div>

                                    <div class="approval-buttons-container"> <a
                                            href="<?= base_url('Paketkebijakandirdekom/approveSemuaKom') ?>"
                                            class="btn btn-success approval-btn approval-btn-approve"
                                            onclick="return confirm('Apakah Anda yakin hendak melakukan approval?');">
                                            Setuju
                                        </a>
                                        <a href="<?= base_url('Paketkebijakandirdekom/unapproveSemuaKom') ?>"
                                            class="btn btn-danger approval-btn approval-btn-reject"
                                            onclick="return confirm('Apakah Anda yakin hendak melakukan pembatalan semua approval?');">
                                            Tolak
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (($userInGroupAdmin || $userInGroupDireksi) && !empty($accdirutData)): ?>
                        <div class="col-md-3">
                            <div class="card shadow-sm approvaldir-card">
                                <div
                                    class="card-body approvaldir-card-body <?php echo (isset($canApprove) && !$canApprove) ? 'disabled-btn' : ''; ?>">
                                    <div class="approval-badge-container"> <span class="badge approval-badge">Approval
                                            Direktur
                                            Utama</span> </div>

                                    <div class="approval-buttons-container"> <a
                                            href="<?= base_url('Paketkebijakandirdekom/approveSemuaDirut') ?>"
                                            class="btn btn-success approval-btn approval-btn-approve <?php echo (isset($canApprove) && !$canApprove) ? 'disabled-btn' : ''; ?>"
                                            onclick="return confirm('Apakah Anda yakin ingin melakukan approval?');">
                                            Setuju
                                        </a>
                                        <a href="<?= base_url('Paketkebijakandirdekom/unapproveSemuaDirut') ?>"
                                            class="btn btn-danger approval-btn approval-btn-reject <?php echo (isset($canApprove) && !$canApprove) ? 'disabled-btn' : ''; ?>"
                                            onclick="return confirm('Batalkan semua approval?');">
                                            Tolak
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Button Submit -->
                <div class="d-flex justify-content-center gap-2 mb-5 mt-4">
                    <?php if ($userInGroupPE || $userInGroupAdmin): ?>
                        <a href="<?= base_url('Paketkebijakandirdekom/exporttxtpaketkebijakandirdekom'); ?>"
                            class="btn btn-secondary btn-sm">
                            <i class="fa fa-file-alt"></i> Export .txt
                        </a>
                        <button type="submit" class="btn btn-primary">Simpan Data</button>
                    <?php endif; ?>
                    <?php if ($userInGroupDekom || $userInGroupDireksi || $userInGroupPE || $userInGroupAdmin): ?>
                        <td>
                            <?php
                            // $Id = session()->get('id');
                            $subkategori = 'Paketkebijakandirdekom';
                            $currentUserId = session()->get('user_id');
                            $activePeriodeId = session()->get('active_periode');

                            // Hitung unread count dengan ID yang benar
                            $initialUnreadCount = $commentReadsModel->countUnreadCommentsForUserByFactor($subkategori, $kodebpr, $currentUserId, $activePeriodeId);

                            // echo "<!-- Debug: Id=$Id, unreadCount=$initialUnreadCount -->";
                            ?>
                            <div class="komentar-btn-wrapper">
                                <button type="button" data-toggle="modal" data-target="#modalTambahkomentar"
                                    id="btn-komentar-<?= $subkategori; ?>" class="btn btn-success btn-sm"
                                    style="font-weight: 610;" data-id="<?= $subkategori; ?>" data-kodebpr="<?= $kodebpr; ?>"
                                    data-user-id="<?= $currentUserId; ?>" data-periode-id="<?= $activePeriodeId; ?>">
                                    <i class="fas fa-comment"></i>
                                    <span id="notification-badge-<?= $subkategori; ?>"
                                        class="badge badge-danger notification-badge"
                                        style="display: <?= $initialUnreadCount > 0 ? 'inline-flex' : 'none'; ?>;">
                                        <?= $initialUnreadCount ?>
                                    </span>
                                </button>
                            </div>
                        </td>
                    <?php endif; ?>
                </div>

                <!-- Response Message -->
                <div id="ajax-response-message"></div>
            </form>

            <!-- Success or error message container -->
            <div id="ajax-response-message"></div>

            <div class="cardpilihfaktor">
                <div class="cardpilihfaktor-header">
                    <h6>Pilih Halaman</h6>
                </div>
                <div class="cardpilihfaktor-body">
                    <div class="d-flex justify-content-center">
                        <div class="btn-toolbar" role="toolbar" aria-label="Toolbar with button groups">
                            <div class="btn-group me-2" role="group" aria-label="First group">
                                <button type="button" class="btn btn-outline-primary btn-sm"
                                    onclick="window.location.href='<?= base_url('Keluargadirdekompshm') ?>'">
                                    << </button>
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            onclick="window.location.href='<?= base_url('Penjelasanumum'); ?>'">1</button>
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            onclick="window.location.href='<?= base_url('Tgjwbdir'); ?>'">2</button>
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            onclick="window.location.href='<?= base_url('Tgjwbdekom'); ?>'">3</button>
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            onclick="window.location.href='<?= base_url('Tgjwbkomite'); ?>'">4</button>
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            onclick="window.location.href='<?= base_url('Strukturkomite'); ?>'">5</button>
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            onclick="window.location.href='<?= base_url('Sahamdirdekom'); ?>'">6</button>
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            onclick="window.location.href='<?= base_url('Shmusahadirdekom'); ?>'">7</button>
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            onclick="window.location.href='<?= base_url('Shmdirdekomlain'); ?>'">8</button>
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            onclick="window.location.href='<?= base_url('Keuangandirdekompshm'); ?>'">9</button>
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            onclick="window.location.href='<?= base_url('Keluargadirdekompshm'); ?>'">10</button>
                                        <button style="background-color: #000; color: #fff;" type="button"
                                            class="btn btn-outline-primary btn-sm"
                                            onclick="window.location.href='<?= base_url('Paketkebijakandirdekom'); ?>'">11</button>
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            onclick="window.location.href='<?= base_url('Rasiogaji'); ?>'">12</button>
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            onclick="window.location.href='<?= base_url('Rapat'); ?>'">13</button>
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            onclick="window.location.href='<?= base_url('Kehadirandekom'); ?>'">14</button>
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            onclick="window.location.href='<?= base_url('Fraudinternal'); ?>'">15</button>
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            onclick="window.location.href='<?= base_url('Masalahhukum'); ?>'">16</button>
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            onclick="window.location.href='<?= base_url('Transaksikepentingan'); ?>'">17</button>
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            onclick="window.location.href='<?= base_url('Danasosial'); ?>'">18</button>
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            onclick="window.location.href='<?= base_url('ShowTransparansi') ?>'">All</button>
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            onclick="window.location.href='<?= base_url('Rasiogaji') ?>'">>></button>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-1">
                        <a href="<?= base_url('Rasiogaji'); ?>" class="btn btn-link btn-sm">Kembali ke halaman
                            periode</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambahketerangan">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Penjelasan lebih lanjut (Opsional)</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('Paketkebijakandirdekom/tambahketerangan'); ?>" method="post">
                    <input type="hidden" name="id" id="id-penjelastindak"> <!-- Hidden field to pass the ID -->

                    <div class="form-group">
                        <label for="tindaklanjut" class="form-label">Penjelasan lebih lanjut (Opsional):
                        </label>
                        <textarea class="form-control" name="tindaklanjut" id="tindaklanjut"
                            style="height: 150px"></textarea>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" name="tambahketerangan" class="btn btn-primary">Ubah Data</button>
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modaleditketerangan">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ubah Penjelasan dan Tindak Lanjut Anggota Dekom</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('Paketkebijakandirdekom/editketerangan'); ?>" method="post">
                    <input type="hidden" name="id" id="id-penjelastindak"
                        value="<?= isset($row['id']) ? esc($row['id']) : ''; ?>">
                    <div class="form-group">
                        <label for="tindaklanjut" class="form-label">Penjelasan lebih lanjut (Opsional):
                        </label>
                        <textarea class="form-control" name="tindaklanjut" id="tindaklanjut"
                            style="height: 150px"><?= isset($row['tindaklanjut']) ? esc($row['tindaklanjut']) : ''; ?></textarea>
                    </div>
                    <div class="modal-footer">
                        <!-- Submit button should be inside the form to trigger submission -->
                        <button type="submit" name="editketerangan" class="btn btn-primary">Ubah Data</button>
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambahkomentar">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="<?= base_url('Paketkebijakandirdekom/Tambahkomentar'); ?>" method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Komentar Direksi dan Dewan Komisaris</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php date_default_timezone_set('Asia/Jakarta'); ?>

                    <input type="hidden" name="id" id="id">
                    <input type="hidden" name="kodebpr" value="<?= $kodebpr ?>">
                    <!-- Tambahkan ini -->

                    <div class="form-group">
                        <label for="komentarLama">Komentar Saat Ini:</label>
                        <ul id="komentarLamaList" style="list-style-type: none; padding-left: 0;">
                            <li>Memuat komentar...</li>
                        </ul>
                    </div>

                    <?php if ($userInGroupAdmin || $userInGroupDekom || $userInGroupDireksi || $userInGroupPE || $userInGroupDekom2 || $userInGroupDireksi2): ?>
                        <input type="hidden" name="fullname" value="<?= htmlspecialchars($fullname) ?>">
                        <input type="hidden" name="date" value="<?= date('Y-m-d H:i:s') ?>">
                        <div class="form-group">
                            <label for="komentar">Tambahkan Komentar Baru:</label>
                            <textarea class="form-control" name="komentar" id="komentar" style="height: 100px"></textarea>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="TambahKomentar" class="btn btn-primary">Simpan
                        Komentar</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if (session()->getFlashdata('message')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">&times;</button>
        <strong><?= esc(session()->getFlashdata('message')); ?></strong>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('err')): ?>
    <div class="alert alert-danger" role="alert"><?= esc(session()->getFlashdata('err')); ?></div>
<?php endif; ?>


<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const currencyInputs = document.querySelectorAll('.format-currency');

        currencyInputs.forEach(input => {
            input.addEventListener('input', function (e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value) {
                    let formattedValue = new Intl.NumberFormat('id-ID').format(value);
                    e.target.value = formattedValue;

                    updateHiddenInput(e.target.id, value);
                } else {
                    e.target.value = '';
                    updateHiddenInput(e.target.id, '');
                }
                updateDebugDisplay();
            });

            let initialValue = input.value.replace(/\D/g, '');
            if (initialValue) {
                input.value = new Intl.NumberFormat('id-ID').format(initialValue);
                updateHiddenInput(input.id, initialValue);
            }
        });

        function updateHiddenInput(displayInputId, rawValue) {
            const hiddenInput = document.getElementById(displayInputId + '_raw');
            if (hiddenInput) {
                hiddenInput.value = rawValue;
            }
        }
    });

    $(document).ready(function () {
        function calculateTotals() {
            var totalRemunDir = 0;
            totalRemunDir += parseFloat($('#nominalgajidir').val().replace(/\D/g, '') || 0);
            totalRemunDir += parseFloat($('#nominaltunjangandir').val().replace(/\D/g, '') || 0);
            totalRemunDir += parseFloat($('#nominaltantiemdir').val().replace(/\D/g, '') || 0);
            totalRemunDir += parseFloat($('#nominalshmdir').val().replace(/\D/g, '') || 0);
            totalRemunDir += parseFloat($('#nominalremunlaindir').val().replace(/\D/g, '') || 0);
            $('#totalremundir').val(new Intl.NumberFormat('id-ID').format(totalRemunDir));
            $('#totalremundir_raw').val(totalRemunDir.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ''));

            var totalFasDir = 0;
            totalFasDir += parseFloat($('#nominalrumahdir').val().replace(/\D/g, '') || 0);
            totalFasDir += parseFloat($('#nominaltransportdir').val().replace(/\D/g, '') || 0);
            totalFasDir += parseFloat($('#nominalasuransidir').val().replace(/\D/g, '') || 0);
            totalFasDir += parseFloat($('#nominalfasilitasdir').val().replace(/\D/g, '') || 0);
            $('#totalfasdir').val(new Intl.NumberFormat('id-ID').format(totalFasDir));
            $('#totalfasdir_raw').val(totalFasDir.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ''));

            var totalDir = totalRemunDir + totalFasDir;
            $('#totaldir').val(new Intl.NumberFormat('id-ID').format(totalDir));
            $('#totaldir_raw').val(totalDir.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ''));

            var totalRemunDekom = 0;
            totalRemunDekom += parseFloat($('#nominalgajidekom').val().replace(/\D/g, '') || 0);
            totalRemunDekom += parseFloat($('#nominaltunjangandekom').val().replace(/\D/g, '') || 0);
            totalRemunDekom += parseFloat($('#nominaltantiemdekom').val().replace(/\D/g, '') || 0);
            totalRemunDekom += parseFloat($('#nominalshmdekom').val().replace(/\D/g, '') || 0);
            totalRemunDekom += parseFloat($('#nominalremunlaindekom').val().replace(/\D/g, '') || 0);
            $('#totalremundekom').val(new Intl.NumberFormat('id-ID').format(totalRemunDekom));
            $('#totalremundekom_raw').val(totalRemunDekom.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ''));

            var totalFasDekom = 0;
            totalFasDekom += parseFloat($('#nominalrumahdekom').val().replace(/\D/g, '') || 0);
            totalFasDekom += parseFloat($('#nominaltransportdekom').val().replace(/\D/g, '') || 0);
            totalFasDekom += parseFloat($('#nominalasuransidekom').val().replace(/\D/g, '') || 0);
            totalFasDekom += parseFloat($('#nominalfasilitasdekom').val().replace(/\D/g, '') || 0);
            $('#totalfasdekom').val(new Intl.NumberFormat('id-ID').format(totalFasDekom));
            $('#totalfasdekom_raw').val(totalFasDekom.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ''));

            var totalDekom = totalRemunDekom + totalFasDekom;
            $('#totaldekom').val(new Intl.NumberFormat('id-ID').format(totalDekom));
            $('#totaldekom_raw').val(totalDekom.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ''));
        }

        $('input[id*="nominal"]').on('input', function () {
            var value = $(this).val().replace(/\D/g, '');
            $(this).val(new Intl.NumberFormat('id-ID').format(value));

            calculateTotals();
        });

        calculateTotals();

        $('#paketKebijakanForm').submit(function (e) {
            e.preventDefault();

            calculateTotals();

            var formData = $(this).serialize();

            console.log('Data yang akan dikirim:', formData);

            // Remove commas before sending to database (replace the commas from the format)
            formData = formData.replace(/(\d{1,3})(?=(\d{3})+(?!\d))/g, '$1'); // Remove commas from formatted numbers

            // Now formData is clean and ready to be sent to the server (no commas, just raw digits)
            $.ajax({
                url: '/Paketkebijakandirdekom/tambahpenjelasAjax',
                type: 'POST',
                data: formData,
                dataType: 'json',
                beforeSend: function () {
                    $('button[type="submit"]').prop('disabled', true).text('Menyimpan...');
                },
                success: function (response) {
                    console.log('Response:', response);

                    if (response.status === 'success') {
                        $('#ajax-response-message').html(
                            '<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                            '<i class="fa fa-check-circle"></i> ' + response.message +
                            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                            '</div>'
                        );
                    } else {
                        $('#ajax-response-message').html(
                            '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                            '<i class="fa fa-exclamation-triangle"></i> ' + response.message +
                            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                            '</div>'
                        );
                    }

                    $('html, body').animate({
                        scrollTop: $("#ajax-response-message").offset().top - 100
                    }, 500);

                    setTimeout(function () {
                        $('#ajax-response-message .alert').fadeOut();
                    }, 5000);
                },
                error: function (xhr, status, error) {
                    console.log('Error:', xhr.responseText);

                    var errorMessage = 'Terjadi kesalahan saat menyimpan data.';

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.status === 404) {
                        errorMessage = 'URL endpoint tidak ditemukan. Periksa route di controller.';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Error server internal. Periksa log server.';
                    } else if (xhr.status === 403) {
                        errorMessage = 'Akses ditolak. Pastikan Anda memiliki permission yang tepat.';
                    }

                    $('#ajax-response-message').html(
                        '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                        '<i class="fa fa-exclamation-triangle"></i> ' + errorMessage +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                        '</div>'
                    );
                },
                complete: function () {
                    $('button[type="submit"]').prop('disabled', false).text('Simpan Data');
                }
            });
        });
    });
</script>


<script>
    const GLOBAL_SUBKATEGORI = '<?= $subkategori ?? '' ?>';
    const GLOBAL_KODEBPR = '<?= $kodebpr ?? '' ?>';
    const GLOBAL_ACTIVE_PERIODE_ID = '<?= $activePeriodeId ?? '' ?>';
    const GLOBAL_CURRENT_USER_ID = '<?= session()->get('user_id') ?? '' ?>';

    $(document).ready(function () {
        $('#tambahPenjelasForm').submit(function (e) {
            e.preventDefault();
            var form = $(this);
            var formData = form.serialize();

            $.ajax({
                url: '<?= base_url('Paketkebijakandirdekom/tambahpenjelasAjax') ?>',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        form.find('#penerimagajidir').val(form.find('#penerimagajidir').val());
                        form.find('#nominalgajidir').val(form.find('#nominalgajidir').val());
                        form.find('#penerimagajidekom').val(form.find('#penerimagajidekom').val());
                        form.find('#nominalgajidekom').val(form.find('#nominalgajidekom').val());
                        form.find('#terimatunjangandir').val(form.find('#terimatunjangandir').val());
                        form.find('#nominaltunjangandir').val(form.find('#nominaltunjangandir').val());
                        form.find('#terimatunjangandekom').val(form.find('#terimatunjangandekom').val());
                        form.find('#nominaltunjangandekom').val(form.find('#nominaltunjangandekom').val());
                        form.find('#terimatantiemdir').val(form.find('#terimatantiemdir').val());
                        form.find('#nominaltantiemdir').val(form.find('#nominaltantiemdir').val());
                        form.find('#terimatantiemdekom').val(form.find('#terimatantiemdekom').val());
                        form.find('#nominaltantiemdekom').val(form.find('#nominaltantiemdekom').val());
                        form.find('#terimashmdir').val(form.find('#terimashmdir').val());
                        form.find('#nominalshmdir').val(form.find('#nominalshmdir').val());
                        form.find('#terimashmdekom').val(form.find('#terimashmdekom').val());
                        form.find('#nominalshmdekom').val(form.find('#nominalshmdekom').val());
                        form.find('#terimaremunlaindir').val(form.find('#terimaremunlaindir').val());
                        form.find('#nominalremunlaindir').val(form.find('#nominalremunlaindir').val());
                        form.find('#terimaremunlaindekom').val(form.find('#terimaremunlaindekom').val());
                        form.find('#nominalremunlaindekom').val(form.find('#nominalremunlaindekom').val());
                        form.find('#terimarumahdir').val(form.find('#terimarumahdir').val());
                        form.find('#nominalrumahdir').val(form.find('#nominalrumahdir').val());
                        form.find('#terimarumahdekom').val(form.find('#terimarumahdekom').val());
                        form.find('#nominalrumahdekom').val(form.find('#nominalrumahdekom').val());
                        form.find('#terimatransportdir').val(form.find('#terimatransportdir').val());
                        form.find('#nominaltransportdir').val(form.find('#nominaltransportdir').val());
                        form.find('#terimatransportdekom').val(form.find('#terimatransportdekom').val());
                        form.find('#nominaltransportdekom').val(form.find('#nominaltransportdekom').val());
                        form.find('#terimaasuransidir').val(form.find('#terimaasuransidir').val());
                        form.find('#nominalasuransidir').val(form.find('#nominalasuransidir').val());
                        form.find('#terimaasuransidekom').val(form.find('#terimaasuransidekom').val());
                        form.find('#nominalasuransidekom').val(form.find('#nominalasuransidekom').val());
                        form.find('#terimafasilitasdir').val(form.find('#terimafasilitasdir').val());
                        form.find('#nominalfasilitasdir').val(form.find('#nominalfasilitasdir').val());
                        form.find('#terimafasilitasdekom').val(form.find('#terimafasilitasdekom').val());
                        form.find('#nominalfasilitasdekom').val(form.find('#nominalfasilitasdekom').val());
                        form.find('#totalremundir').val(form.find('#totalremundir').val());
                        form.find('#totalremundekom').val(form.find('#totalremundekom').val());
                        form.find('#totalfasdir').val(form.find('#totalfasdir').val());
                        form.find('#totalfasdekom').val(form.find('#totalfasdekom').val());
                        form.find('#totaldir').val(form.find('#totaldir').val());
                        form.find('#totaldekom').val(form.find('#totaldekom').val());

                        $('#ajax-response-message').html('<div class="alert alert-success">' + response.message + '</div>');

                        // Sembunyikan pesan setelah 3 detik
                        setTimeout(function () {
                            $('#ajax-response-message').fadeOut();
                        }, 3000);
                    } else {
                        $('#ajax-response-message').html('<div class="alert alert-danger">' + response.message + '</div>');
                    }
                },
                error: function (xhr) {
                    var errorMessage = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'Error while processing the request.';
                    $('#ajax-response-message').html('<div class="alert alert-danger">' + errorMessage + '</div>');
                }
            });
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        // Selector yang konsisten
        const commentButtons = document.querySelectorAll('[id^="btn-komentar-"]');

        console.log('Found comment buttons:', commentButtons.length); // Debug

        function updateBadge(Id, newCount) {
            const badge = document.getElementById('notification-badge-' + Id);
            // console.log('Updating badge for factor', Id, 'with count', newCount); // Debug
            if (badge) {
                if (newCount > 0) {
                    badge.textContent = newCount;
                    badge.style.display = 'inline-flex';
                } else {
                    badge.style.display = 'none';
                    badge.textContent = '0';
                }
            }
        }

        function fetchAndDisplayComments(Id, kodebpr, periodeId) {
            const modal = $('#modalTambahkomentar');
            modal.find('#komentarLamaList').html('<li>Memuat komentar...</li>');

            $.ajax({
                url: '<?= base_url('paketkebijakandirdekom/getKomentarByFaktorId'); ?>/' + Id,
                method: 'GET',
                data: {
                    kodebpr: kodebpr,
                    periode_id: periodeId
                },
                dataType: 'json',
                success: function (response) {
                    let komentarListHtml = '';
                    if (response.length > 0) {
                        response.forEach(function (komentar) {
                            komentarListHtml += '<li>' + komentar.komentar +
                                ' - (' + komentar.fullname +
                                ' - ' + komentar.created_at + ')</li>';
                        });
                    } else {
                        komentarListHtml = '<li>Tidak ada komentar.</li>';
                    }
                    modal.find('#komentarLamaList').html(komentarListHtml);
                },
                error: function (xhr, status, error) {
                    console.error('Error fetching comments for modal:', error);
                    console.log('Response:', xhr.responseText);
                    modal.find('#komentarLamaList').html('<li>Gagal memuat komentar.</li>');
                }
            });
        }

        // Event listener untuk button komentar
        commentButtons.forEach(button => {
            button.addEventListener('click', function () {
                const Id = this.getAttribute('data-id');
                const kodebpr = this.getAttribute('data-kodebpr');
                const userId = this.getAttribute('data-user-id');
                const periodeId = this.getAttribute('data-periode-id');

                // console.log('Button clicked - Id:', Id, 'userId:', userId); // Debug

                $('#modalTambahkomentar').find('#id').val(Id);

                // Fetch dan display comments
                fetchAndDisplayComments(Id, kodebpr, periodeId);

                // Mark comments as read
                $.ajax({
                    url: '<?= base_url('Paketkebijakandirdekom/markUserCommentsAsRead'); ?>',
                    method: 'POST',
                    data: {
                        id: Id,
                        kodebpr: kodebpr,
                        user_id: userId,
                        periode_id: periodeId,
                        '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
                    },
                    success: function (response) {
                        if (response.status === 'success') {
                            updateBadge(Id, 0);
                            console.log('Comments marked as read for factor', Id); // Debug
                        } else {
                            console.error('Failed to mark comments as read:', response.message);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('Error marking comments as read:', error);
                    }
                });
            });
        });

        // Form submit handler
        $('#formTambahKomentar').on('submit', function (e) {
            e.preventDefault();
            const form = $(this);
            const formData = form.serialize();
            const Id = form.find('#id').val(); // ID yang konsisten

            console.log('Submitting comment for factor:', Id); // Debug

            $.ajax({
                url: form.attr('action'),
                method: form.attr('method'),
                data: formData + '&<?= csrf_token() ?>=' + '<?= csrf_hash() ?>',
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        alert(response.message);
                        form.find('#komentar').val('');
                        fetchAndDisplayComments(Id, GLOBAL_KODEBPR, GLOBAL_ACTIVE_PERIODE_ID);

                        // Refresh badge untuk user lain
                        refreshBadgeForAllUsers(GLOBAL_CURRENT_USER_ID);
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function (xhr, status, error) {
                    console.error("AJAX Error:", status, error);
                    alert('Terjadi kesalahan saat menyimpan komentar.');
                }
            });
        });

        // Polling function
        function pollUnreadCounts() {
            console.log('Polling unread counts for', commentButtons.length, 'buttons');

            commentButtons.forEach(button => {
                const Id = button.getAttribute('data-id');
                const kodebpr = button.getAttribute('data-kodebpr');
                const userId = button.getAttribute('data-user-id');
                const periodeId = button.getAttribute('data-periode-id');

                console.log('Polling for ID:', Id, 'User:', userId);

                $.ajax({
                    url: '<?= base_url('Paketkebijakandirdekom/getUnreadCommentCountForFactor'); ?>',
                    method: 'GET',
                    data: {
                        id: Id,
                        kodebpr: kodebpr,
                        user_id: userId,
                        periode_id: periodeId
                    },
                    success: function (response) {
                        console.log('Unread count response for ID', Id, ':', response);
                        if (response && typeof response.unread_count !== 'undefined') {
                            updateBadge(Id, response.unread_count);
                        } else {
                            console.warn('Invalid response format:', response);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('Error fetching unread count for factor ' + Id + ':', error);
                        console.error('Response text:', xhr.responseText);
                    }
                });
            });
        }

        // Fungsi untuk refresh badge setelah ada komentar baru
        function refreshBadgeForAllUsers(exceptUserId) {
            console.log('Refreshing badges for all users except:', exceptUserId);

            commentButtons.forEach(button => {
                const Id = button.getAttribute('data-id');
                const kodebpr = button.getAttribute('data-kodebpr');
                const periodeId = button.getAttribute('data-periode-id');

                $.ajax({
                    url: '<?= base_url('Paketkebijakandirdekom/getUnreadCommentCountForAllUsers'); ?>',
                    method: 'GET',
                    data: {
                        id: Id,
                        kodebpr: kodebpr,
                        periode_id: periodeId,
                        except_user_id: exceptUserId
                    },
                    success: function (response) {
                        console.log('Badge refresh response:', response);
                    },
                    error: function (xhr, status, error) {
                        console.error('Error refreshing badges:', error);
                    }
                });
            });
        }

        // Call polling function initially
        pollUnreadCounts();

        // setInterval(pollUnreadCounts, 10000);
    });
</script>

<style>
    .cardpilihfaktor {
        width: auto;
        max-width: 700px;
        margin: 10px auto;
        border: 1px solid #ddd;
        border-radius: 8px;
        margin-bottom: 30px;
    }

    .cardpilihfaktor-header {
        text-align: center;
        background-color: #f8f9fa;
        padding: 2px;
        border-bottom: 1px solid #ddd;
        font-size: 1.0rem;
        font-weight: bold;
    }

    .cardpilihfaktor-body {
        padding: 5px;
    }

    .cardpilihfaktor .btn-toolbar {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
    }

    .cardpilihfaktor .btn-group .btn {
        margin: 1px;
    }

    body {
        background-color: #f8f9fa;
    }

    .card {
        border-radius: 15px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        margin-top: 30px;
        margin-bottom: 30px;
    }

    .card-body {
        padding: 40px;
    }

    .alert-info-custom {
        background-color: #e0f7fa;
        color: #007bb5;
        border-color: #b2ebf2;
        border-radius: 10px;
        padding: 15px 20px;
        display: flex;
        align-items: center;
        font-size: 1.1rem;
        margin-bottom: 25px;
    }

    .alert-info-custom strong {
        color: #0056b3;
    }

    .alert-info-custom .fas {
        margin-right: 10px;
        font-size: 1.5rem;
    }

    .h3.mb-4.text-gray-800.text-center {
        color: #343a40;
        font-weight: 700;
        margin-bottom: 30px !important;
        position: relative;
        padding-bottom: 10px;
    }

    .h3.mb-4.text-gray-800.text-center::after {
        content: '';
        position: absolute;
        left: 50%;
        bottom: 0;
        transform: translateX(-50%);
        width: 80px;
        height: 3px;
        background-color: #007bff;
        border-radius: 5px;
    }

    .form-group label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 8px;
    }

    .form-control {
        border-radius: 8px;
        padding: 10px 15px;
        border: 1px solid #ced4da;
        transition: all 0.3s ease-in-out;
    }

    .form-control:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    textarea.form-control {
        resize: vertical;
    }

    .btn-primary {
        background-color: #141863;
        border-color: #141863;
        border-radius: 25px;
        padding: 12px 30px;
        font-size: 1.1rem;
        font-weight: 600;
        transition: background-color 0.3s ease, border-color 0.3s ease, transform 0.3s ease;
        margin-left: 15px;
    }

    .btn-primary:hover {
        background-color: #ffffff;
        border-color: #141863;
        transform: translateY(-2px);
        color: #141863;
    }

    .btn-primary2 {
        background-color: #141863;
        border-color: #141863;
        border-radius: 25px;
        padding: 12px 30px;
        font-size: 0.9rem;
        font-weight: 600;
        transition: background-color 0.3s ease, border-color 0.3s ease, transform 0.3s ease;
        margin-right: 10px;
        margin-left: 0px;
        margin-top: 30px;
        margin-bottom: 15px;
        color: #ffffff;
    }

    .btn-primary2:hover {
        background-color: #ffffff;
        border-color: #0056b3;
        transform: translateY(-2px);
        color: #0056b3;
    }

    .btn-primary3 {
        background-color: #141863;
        border-color: #141863;
        border-radius: 25px;
        padding: 12px 30px;
        font-size: 0.9rem;
        font-weight: 600;
        transition: background-color 0.3s ease, border-color 0.3s ease, transform 0.3s ease;
        margin-top: 40px;
        margin-right: 10px;
        margin-bottom: 15px;
        color: #ffffff;
    }

    .btn-primary3:hover {
        background-color: #ffffff;
        border-color: #141863;
        color: #141863;
        transform: translateY(-2px);
    }

    .btn-secondary {
        background-color: #343a40;
        border-color: #343a40;
        border-radius: 55px;
        padding: 12px 30px;
        font-size: 1.0rem;
        font-weight: 600;
        transition: background-color 0.3s ease, border-color 0.3s ease, transform 0.3s ease;
    }

    .btn-secondary:hover {
        background-color: #ffffff;
        border-color: #343a40;
        transform: translateY(-2px);
        color: #343a40;
    }

    .btn-danger {
        background-color: #721c24;
        border-color: #721c24;
        border-radius: 55px;
        padding: 12px 30px;
        font-size: 0.9rem;
        font-weight: 600;
        transition: background-color 0.3s ease, border-color 0.3s ease, transform 0.3s ease;
        margin-right: 5px;
    }

    .btn-danger:hover {
        background-color: #ffffff;
        border-color: #721c24;
        transform: translateY(-2px);
        color: #721c24;
    }

    .btn-success {
        background-color: #ffffff;
        /* Blue button */
        border-color: #28a745;
        border-radius: 25px;
        /* Pill-shaped button */
        padding: 12px 30px;
        font-size: 1.1rem;
        font-weight: 600;
        transition: background-color 0.3s ease, border-color 0.3s ease, transform 0.3s ease;
        margin-right: 15px;
        margin-left: 15px;
        color: #28a745;
    }

    .btn-success:hover {
        background-color: #28a745;
        border-color: #ffffff;
        transform: translateY(-2px);
        color: #ffffff;
        /* Slight lift on hover */
    }

    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border-color: #c3e6cb;
        border-radius: 10px;
    }

    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border-color: #f5c6cb;
        border-radius: 10px;
    }

    .close {
        color: #000;
        opacity: 0.5;
        font-weight: 700;
        text-shadow: none;
    }

    .close:hover {
        opacity: 0.75;
    }

    .beautiful-alert {
        background-color: #e0f7fa;
        /* A very light blue */
        color: #007bb5;
        /* A darker, soothing blue for text */
        border-color: #b2ebf2;
        /* A slightly darker border than background */
        border-radius: 10px;
        /* Rounded corners for a softer look */
        padding: 15px 25px;
        /* More padding for breathing room */
        display: flex;
        /* Use flexbox for alignment of icon and text */
        align-items: center;
        /* Vertically align items in the middle */
        font-size: 1.1rem;
        /* Slightly larger font for readability */
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        /* Subtle shadow for depth */
    }

    .beautiful-alert strong {
        color: #0056b3;
        /* Even darker blue for the strong text */
    }

    .beautiful-alert .alert-icon {
        margin-right: 15px;
        /* Space between the icon and text */
        font-size: 1.8rem;
        /* Larger icon size */
        color: #007bff;
        /* Bootstrap primary blue for the icon */
    }

    .custom-badge-container {
        display: flex;
        /* Makes it a flex container */
        justify-content: space-between;
        /* Pushes the first item to the start (left) and the last item to the end (right) */
        align-items: flex-start;
        /* Aligns items to the top if they have different heights */
        /* Add any other styling for the container here, e.g., padding */
        padding: 15px;
        /* Example padding */
    }

    /* Card keseluruhan */
    .approval-card {
        width: 75%;
        left: 6px;
        margin-top: 1px;
        /*(Jika ingin mempertahankan margin-top) */
        height: 95px;
        /* Tinggi tetap */
        border-radius: 15px;
        /* Sudut lebih membulat untuk efek modern */
        overflow: hidden;
        /* Pastikan konten tidak meluber */
    }

    /* Bagian body dari card */
    .approval-card-body {
        display: flex;
        /* Menggunakan flexbox untuk tata letak vertikal */
        flex-direction: column;
        /* Mengatur item dalam kolom (vertikal) */
        justify-content: space-between;
        /* Menarik badge ke atas dan tombol ke bawah */
        padding: 15px;
        /* Sesuaikan padding di dalam card body */
        height: 100%;
        /* Pastikan body mengisi penuh tinggi card */
    }

    /* Card keseluruhan */
    .approvaldir-card {
        position: absolute;
        right: 16px;
        width: 75%;
        margin-top: 1px;
        /*(Jika ingin mempertahankan margin-top) */
        height: 95px;
        /* Tinggi tetap */
        border-radius: 15px;
        /* Sudut lebih membulat untuk efek modern */
        overflow: hidden;
        /* Pastikan konten tidak meluber */
    }

    /* Bagian body dari card */
    .approvaldir-card-body {
        display: flex;
        /* Menggunakan flexbox untuk tata letak vertikal */
        flex-direction: column;
        /* Mengatur item dalam kolom (vertikal) */
        justify-content: right;
        /* Menarik badge ke atas dan tombol ke bawah */
        padding: 15px;
        /* Sesuaikan padding di dalam card body */
        height: 100%;
        /* Pastikan body mengisi penuh tinggi card */
    }

    /* Container untuk badge */
    .approval-badge-container {
        text-align: center;
        /* Menengahkan badge horizontal */
        margin-bottom: 6px;
        /* Jarak antara badge dan tombol */
    }

    /* Gaya badge */
    .approval-badge {
        background-color: #343a40;
        /* Warna hitam gelap (sesuai gambar) */
        color: #ffffff;
        /* Teks putih */
        font-size: 0.8em;
        /* Ukuran font */
        font-weight: 500;
        /* Ketebalan font */
        padding: 5px 12px;
        /* Padding vertikal dan horizontal */
        border-radius: 5px;
        /* Sedikit membulat */
        display: inline-block;
        /* Penting untuk padding dan margin */
        /* Box shadow untuk efek seperti di gambar */
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    /* Container untuk tombol-tombol */
    .approval-buttons-container {
        display: flex;
        /* Menggunakan flexbox untuk tata letak horizontal */
        justify-content: center;
        /* Menengahkan tombol-tombol secara keseluruhan */
        align-items: center;
        /* Mengatur vertikal di tengah (jika ada perbedaan tinggi) */
        gap: 9px;
        /* Jarak antar tombol (modern, Bootstrap 5+ setara dengan me-*) */
        /* Jika menggunakan Bootstrap 4 dan tidak ada 'gap': */
        /* .approval-btn:first-child { margin-right: 8px; } */
    }

    /* Gaya umum untuk semua tombol approval */
    .approval-btn {
        flex: 1;
        /* Membuat tombol mengisi ruang yang tersedia secara merata */
        max-width: 50%;
        /* Batasi lebar maksimal agar tidak terlalu besar */
        padding: 8px 15px;
        /* Padding tombol */
        font-size: 0.95em;
        /* Ukuran font tombol */
        font-weight: 500;
        /* Ketebalan font */
        border-radius: 8px;
        /* Sudut tombol yang lebih membulat */
        text-align: center;
        /* Pastikan teks tombol di tengah */
        text-decoration: none;
        /* Hapus garis bawah pada link */
        white-space: nowrap;
        /* Pastikan teks tombol tidak pecah baris */
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        /* Bayangan lembut */
        transition: all 0.2s ease-in-out;
        /* Transisi untuk efek hover */
    }

    /* Gaya khusus tombol Setujui */
    .approval-btn-approve {
        background-color: #28a745;
        /* Warna hijau */
        border-color: #28a745;
        color: #fff;
    }

    .approval-btn-approve:hover {
        background-color: #218838;
        /* Hijau lebih gelap saat hover */
        border-color: #1e7e34;
        transform: translateY(-1px);
        /* Efek terangkat sedikit */
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    /* Gaya khusus tombol Tolak */
    .approval-btn-reject {
        background-color: #dc3545;
        /* Warna merah */
        border-color: #dc3545;
        color: #fff;
    }

    .approval-btn-reject:hover {
        background-color: #c82333;
        /* Merah lebih gelap saat hover */
        border-color: #bd2130;
        transform: translateY(-1px);
        /* Efek terangkat sedikit */
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .disabled-card {
        opacity: 0.5;
        /* Membuat card terlihat samar */
        pointer-events: none;
        /* Menonaktifkan interaksi dengan elemen */
    }

    .disabled-btn {
        opacity: 0.5;
        /* Membuat tombol terlihat samar */
        pointer-events: none;
        /* Menonaktifkan klik pada tombol */
    }
</style>