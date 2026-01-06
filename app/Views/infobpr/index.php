<div class="container-fluid">
    <?php if (session()->getFlashdata('message')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">&times;</button>
            <strong><?= esc(session()->getFlashdata('message')); ?></strong>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('err')): ?>
        <div class="alert alert-danger" role="alert"><?= esc(session()->getFlashdata('err')); ?></div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-white">Informasi BPR</h6>
        </div>
        <div class="card-body">
            <form action="<?= base_url('infobpr/simpaninfo'); ?>" method="post" enctype="multipart/form-data">
                <?php if (!empty($infobpr)): ?>
                    <input type="hidden" name="id" value="<?= esc($infobpr['id']); ?>">
                <?php endif; ?>

                <div class="form-group mb-2 text-center">
                    <label for="logo" class="form-label">Logo BPR</label>
                    <input type="file" name="logo" class="dropify" data-height="150" data-show-remove="false"
                        data-default-file="<?= !empty($infobpr['logo']) ? base_url('/asset/img/' . $infobpr['logo']) : ''; ?>">
                    <small class="form-text text-muted">Pilih gambar untuk diunggah.</small>
                </div>

                <div class="mb-3 row">
                    <label for="kodebpr" class="col-sm-3 col-form-label">Kode BPR:</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="kodebpr" name="kodebpr" placeholder="Kode BPR"
                            style="height: 45px" value="<?= esc($infobpr['kodebpr'] ?? ''); ?>" readonly>
                    </div>
                </div>

                <div class="mb-3 row">
                    <label for="namabpr" class="col-sm-3 col-form-label">Nama BPR:</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="namabpr" name="namabpr" placeholder="Nama BPR"
                            style="height: 45px" value="<?= esc($infobpr['namabpr'] ?? ''); ?>">
                    </div>
                </div>

                <div class="mb-3 row">
                    <label for="alamat" class="col-sm-3 col-form-label">Alamat BPR</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="alamat" name="alamat" placeholder="Alamat BPR"
                            style="height: 45px" value="<?= esc($infobpr['alamat'] ?? ''); ?>">
                    </div>
                </div>

                <div class="mb-3 row">
                    <label for="nomor" class="col-sm-3 col-form-label">Nomor Telepon</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="nomor" name="nomor" placeholder="Nomor Telepon BPR"
                            style="height: 45px" value="<?= esc($infobpr['nomor'] ?? ''); ?>">
                    </div>
                </div>

                <div class="mb-3 row">
                    <label for="sandibpr" class="col-sm-3 col-form-label">Sandi BPR</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="sandibpr" name="sandibpr" placeholder="Sandi BPR"
                            style="height: 45px" value="<?= esc($infobpr['sandibpr'] ?? ''); ?>">
                    </div>
                </div>

                <div class="mb-3 row">
                    <label for="jenis" class="col-sm-3 col-form-label">Jenis Lembaga</label>
                    <div class="col-sm-9">
                        <select class="form-control" id="jenis" name="jenis" style="height: 45px;">
                            <option value="">-- Pilih Jenis Lembaga --</option>
                            <option value="BPR Konvensional" <?= (isset($infobpr['jenis']) && $infobpr['jenis'] == 'BPR Konvensional') ? 'selected' : ''; ?>>BPR Konvensional</option>
                            <option value="BPR Syariah" <?= (isset($infobpr['jenis']) && $infobpr['jenis'] == 'BPR Syariah') ? 'selected' : ''; ?>>BPR Syariah</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3 row" style="display:none;">
                    <label for="kodejenis" class="col-sm-3 col-form-label">Kode Jenis BPR</label>
                    <div class="col-sm-9">
                        <input type="hidden" class="form-control" id="kodejenis" name="kodejenis"
                            value="<?= esc($infobpr['kodejenis'] ?? ''); ?>">
                    </div>
                </div>

                <div class="mb-3 row">
                    <label for="kategori" class="col-sm-3 col-form-label">Kategori BPR</label>
                    <div class="col-sm-9">
                        <select class="form-control" id="kategori" name="kategori" style="height: 45px">
                            <option value="Bank Perekonomian Rakyat" <?= (isset($infobpr['kategori']) && $infobpr['kategori'] == 'Bank Perekonomian Rakyat') ? 'selected' : ''; ?>>
                                Bank Perekonomian Rakyat
                            </option>
                        </select>
                    </div>
                </div>

                <div class="mb-3 row">
                    <label for="email" class="col-sm-3 col-form-label">Email BPR</label>
                    <div class="col-sm-9">
                        <input type="email" class="form-control" id="email" name="email" placeholder="Email BPR"
                            style="height: 45px" value="<?= esc($infobpr['email'] ?? ''); ?>">
                    </div>
                </div>

                <div class="mb-5 row">
                    <label for="webbpr" class="col-sm-3 col-form-label">Website BPR</label>
                    <div class="col-sm-9">
                        <input type="url" class="form-control" id="webbpr" name="webbpr" placeholder="Website BPR"
                            style="height: 45px" value="<?= esc($infobpr['webbpr'] ?? ''); ?>">
                    </div>
                </div>

                <div class="d-flex justify-content-center">
                    <button class="btn btn-primary mr-2" type="submit" name="ubah">
                        <i class="fa-sm text-white-50"></i> Simpan Data
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

<style>
    body {
        background-color: #f0f2f5;
        /* Light grey background for the whole page */
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .container-fluid {
        padding-top: 30px;
        padding-bottom: 30px;
    }

    .h3.mb-4.text-gray-800 {
        color: #34495e;
        /* Darker, professional blue-grey for main heading */
        font-weight: 700;
        margin-bottom: 35px !important;
        text-align: center;
        position: relative;
        padding-bottom: 10px;
    }

    .h3.mb-4.text-gray-800::after {
        content: '';
        position: absolute;
        left: 50%;
        bottom: 0;
        transform: translateX(-50%);
        width: 100px;
        height: 4px;
        background-color: #007bff;
        /* Primary blue underline */
        border-radius: 2px;
    }

    .alert {
        border-radius: 8px;
        font-size: 1rem;
        display: flex;
        align-items: center;
        margin-bottom: 25px;
    }

    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border-color: #c3e6cb;
    }

    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border-color: #f5c6cb;
    }

    .alert .close {
        color: inherit;
        /* Inherit color from alert for better contrast */
        opacity: 0.6;
        font-size: 1.5rem;
    }

    .alert .close:hover {
        opacity: 1;
    }

    .alert i {
        margin-right: 10px;
        font-size: 1.2rem;
    }

    .card.shadow.mb-4 {
        border-radius: 12px;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15) !important;
        /* Stronger, more prominent shadow */
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .card.shadow.mb-4:hover {
        transform: translateY(-5px);
        /* Slight lift on hover */
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.2) !important;
    }

    .card-header.py-3 {
        background-color: #141863;
        /* Primary blue header */
        border-bottom: 1px solid rgba(0, 0, 0, 0.125);
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
        padding: 18px 25px;
    }

    .card-header .h6 {
        color: #ffffff;
        /* White text for header */
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
    }

    .card-header .h6 i {
        margin-right: 10px;
        font-size: 1.2rem;
    }

    .card-body {
        padding: 30px;
    }

    .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 8px;
    }

    .form-control,
    .form-control-file {
        border-radius: 6px;
        border: 1px solid #ced4da;
        padding: 10px 15px;
        transition: all 0.3s ease;
    }

    .form-control:focus,
    .form-control-file:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    /* Styling for Dropify */
    .dropify-wrapper {
        border: 2px dashed #6c757d;
        /* Blue dashed border */
        border-radius: 10px;
        background-color: #f8f9fa;
        /* Light background for drop area */
        padding: 20px;
        height: 180px;
        /* Adjust height as needed */
        transition: all 0.3s ease;
    }

    .dropify-wrapper:hover {
        border-color: #6c757d;
        /* Darker blue on hover */
        background-color: #e2f2ff;
        /* Lighter blue background on hover */
    }

    .dropify-message p {
        font-size: 1.1rem;
        color: #6c757d;
    }

    .dropify-preview .dropify-render img {
        max-height: 100%;
        max-width: 100%;
        object-fit: contain;
        /* Ensures the image fits without cropping */
        border-radius: 8px;
    }

    .dropify-clear,
    .dropify-infos {
        background-color: #dc3545;
        /* Red for clear button */
        color: white;
        border-radius: 5px;
    }

    .dropify-infos .dropify-infos-inner p.dropify-filename {
        color: #495057;
        /* Darker text for filename */
    }


    .col-form-label {
        font-weight: 600;
        color: #343a40;
    }

    .btn-primary {
        background-color: #141863;
        border-color: #141863;
        border-radius: 25px;
        /* Pill-shaped button */
        padding: 12px 30px;
        font-size: 1.1rem;
        font-weight: 600;
        transition: background-color 0.3s ease, border-color 0.3s ease, transform 0.3s ease;
    }

    .btn-primary:hover {
        background-color: #ffffff;
        border-color: #0056b3;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
        color: #141863;
    }

    small.form-text.text-muted {
        font-size: 0.85rem;
        margin-top: 5px;
    }
</style>