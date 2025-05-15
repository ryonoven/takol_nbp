<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= $judul; ?></h1>

    <?php if (session()->get('message')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <strong><?= session()->getFlashdata('message'); ?></strong>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-6">
            <?php if (session()->get('err')): ?>
                <div class="alert alert-danger" role="alert"><?= session()->get('err'); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Informasi BPR</h6>
        </div>
        <div class="card-body">
            <form action="<?= base_url('infobpr/simpaninfo'); ?>" method="post" enctype="multipart/form-data">
                <?php if (!empty($infobpr)): ?>
                    <input type="hidden" name="id" value="<?= $infobpr[0]['id']; ?>">
                <?php endif; ?>
                <div class="form-group mb-2 text-center">
                    <label for="logo" class="form-label">Logo BPR</label>
                    <input type="file" name="logo" class="dropify" data-height="150" data-show-remove="false"
                        data-default-file="<?= !empty($infobpr[0]['logo']) ? base_url('asset/img/' . $infobpr[0]['logo']) : '' ?>">
                    <small class="form-text text-muted">Pilih gambar untuk diunggah.</small>
                </div>
                <div class="mb-3 row justify-content-center">
                </div>
                <div class="mb-3 row">
                    <label for="namabpr" class="col-sm-3 col-form-label">Nama BPR:</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="namabpr" name="namabpr" placeholder="Nama BPR"
                            value="<?= !empty($infobpr[0]['namabpr']) ? $infobpr[0]['namabpr'] : '' ?>">
                    </div>
                </div>
                <div class="mb-3 row">
                    <label for="alamat" class="col-sm-3 col-form-label">Alamat BPR</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="alamat" name="alamat" placeholder="Alamat BPR"
                            value="<?= !empty($infobpr[0]['alamat']) ? $infobpr[0]['alamat'] : '' ?>">
                    </div>
                </div>
                <div class="mb-3 row">
                    <label for="nomor" class="col-sm-3 col-form-label">Nomor Telepon</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="nomor" name="nomor" placeholder="Nomor Telepon BPR"
                            value="<?= !empty($infobpr[0]['nomor']) ? $infobpr[0]['nomor'] : '' ?>">
                    </div>
                </div>
                <div class="mb-3 row">
                    <label for="sandibpr" class="col-sm-3 col-form-label">Sandi BPR</label>
                    <div class="col-sm-9">
                        <input class="form-control" id="sandibpr" name="sandibpr" placeholder="Sandi BPR"
                            value="<?= !empty($infobpr[0]['sandibpr']) ? $infobpr[0]['sandibpr'] : '' ?>">
                    </div>
                </div>
                <div class="mb-3 row">
                    <label for="jenis" class="col-sm-3 col-form-label">Jenis Lembaga</label>
                    <div class="col-sm-9">
                        <select class="form-control" id="jenis" name="jenis">
                            <option value="">-- Pilih Jenis Lembaga --</option>
                            <option value="BPR Konvensional" <?= (!empty($infobpr[0]['jenis']) && $infobpr[0]['jenis'] == 'BPR Konvensional') ? 'selected' : '' ?>>BPR Konvensional</option>
                            <option value="BPR Syariah" <?= (!empty($infobpr[0]['jenis']) && $infobpr[0]['jenis'] == 'BPR Syariah') ? 'selected' : '' ?>>BPR Syariah</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3 row" style="display:none;">
                    <label for="kodejenis" class="col-sm-3 col-form-label">Kode Jenis BPR</label>
                    <div class="col-sm-9">
                        <input type="hidden" class="form-control" id="kodejenis" name="kodejenis"
                            value="<?= !empty($infobpr[0]['kodejenis']) ? $infobpr[0]['kodejenis'] : '' ?>">
                    </div>
                </div>
                <div class="mb-3 row">
                    <label for="kategori" class="col-sm-3 col-form-label">Kategori BPR</label>
                    <div class="col-sm-9">
                        <select class="form-control" id="kategori" name="kategori">
                            <option value="Bank Perekonomian Rakyat" <?= (!empty($infobpr[0]['kategori']) && $infobpr[0]['kategori'] == 'Bank Perekonomian Rakyat') ? 'selected' : '' ?>>Bank
                                Perekonomian Rakyat</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3 row">
                    <label for="email" class="col-sm-3 col-form-label">Email BPR</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="email" name="email" placeholder="Email BPR"
                            value="<?= !empty($infobpr[0]['email']) ? $infobpr[0]['email'] : '' ?>">
                    </div>
                </div>
                <div class="mb-3 row">
                    <label for="webbpr" class="col-sm-3 col-form-label">Website BPR</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="webbpr" name="webbpr" placeholder="Website BPR"
                            value="<?= !empty($infobpr[0]['webbpr']) ? $infobpr[0]['webbpr'] : '' ?>">
                    </div>
                </div>
                <div class="d-flex justify-content-center">
                    <button class="btn btn-primary mr-2" type="submit" name="ubah">
                        <i class="fas fa-save fa-sm text-white-50"></i> Simpan Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const jenisLembagaSelect = document.getElementById('jenis');
    const kodeJenisInput = document.getElementById('kodejenis');

    jenisLembagaSelect.addEventListener('change', function () {
        if (this.value === 'BPR Konvensional') {
            kodeJenisInput.value = '010201';
        } else {
            kodeJenisInput.value = '';
        }
    });

    // Set initial value on page load
    if (jenisLembagaSelect.value === 'BPR Konvensional') {
        kodeJenisInput.value = '010201';
    }
</script>