<div class="container my-4">
    <!-- Notifikasi -->
    <?php if (session()->getFlashdata('message')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('message') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($validation) && $validation->getErrors()): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                <?php foreach ($validation->getErrors() as $error): ?>
                    <li><?= esc($error) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Form Buat Periode Baru -->
        <div class="accordion" id="accordionExample">
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingOne">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                        Buat Periode Baru Laporan Profil Resiko Tata Kelola
                    </button>
                </h2>
                <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne"
                    data-bs-parent="#accordionExample">
                    <div class="accordion-body">
                        <form action="<?= base_url('periodeprofilresiko/handlePeriode') ?>" method="post" novalidate>
                            <input type="hidden" name="action" value="create">
                            <div class="mb-3">
                                <label for="tahun" class="form-label">Tahun</label>
                                <select id="tahun" name="tahun" class="form-select" required>
                                    <?php for ($year = 2024; $year <= 2030; $year++): ?>
                                        <option value="<?= $year ?>"><?= $year ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="semester" class="form-label">Semester</label>
                                <select id="semester" name="semester" class="form-select" required>
                                    <option value="" disabled selected>Pilih Semester ...</option>
                                    <option value="1">Semester 1</option>
                                    <option value="2">Semester 2</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="jenispelaporan" class="form-label">Jenis Pelaporan</label>
                                <select id="jenispelaporan" name="jenispelaporan" class="form-select" required>
                                    <option value="" disabled selected>Pilih Jenis Pelaporan ...</option>
                                    <option value="R">Laporan Rutin</option>
                                    <option value="K">Laporan Koreksi</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="modalinti">Input Modal Inti: </label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" name="modalinti" id="modalinti" class="form-control" required
                                        oninput="formatRupiah(this)">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="totalaset">Input Total Aset: </label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" name="totalaset" id="totalaset" class="form-control" required
                                        oninput="formatRupiah(this)">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="kantorcabang" class="form-label">Jumlah kantor cabang saat pelaporan:
                                </label>
                                <input type="number" name="kantorcabang" id="kantorcabang" class="form-control"
                                    required>
                            </div>

                            <div class="mb-3">
                                <label for="atmdebit" class="form-label">Kegiatan sebagai penerbit kartu ATM atau kartu
                                    debit:</label>
                                <select id="atmdebit" name="atmdebit" class="form-select" required>
                                    <option value="" disabled selected>Klik di sini untuk memilih</option>
                                    <option value="1">Ya</option>
                                    <option value="2">Tidak</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="kategori" class="form-label">Jumlah Risiko</label>

                                <div class="dropdown w-100">
                                    <button class="btn btn-outline-secondary dropdown-toggle w-100 text-start"
                                        type="button" id="dropdownKategori" data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                        Klik di sini untuk memilih
                                    </button>
                                    <ul class="dropdown-menu w-100" aria-labelledby="dropdownKategori">
                                        <li>
                                            <a class="dropdown-item" href="#" data-value="A">
                                                <strong>6 Risiko</strong><br>
                                                <small class="text-muted">Risiko Kredit, Risiko Operasional, Risiko
                                                    Kepatuhan,Risiko Likuiditas,Risiko Reputasi, Risiko Stratejik<br>
                                                    Note: Hanya Untuk BPR yang memiliki modal inti diatas 50 Miliyar
                                                    Rupiah

                                                </small>
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="#" data-value="B">
                                                <strong>4 Risiko</strong><br>
                                                <small class="text-muted">Risiko Kredit, Risiko Operasional, Risiko
                                                    Kepatuhan, Risiko Likuiditas</small>
                                            </a>
                                        </li>                                        
                                    </ul>
                                </div>

                                <input type="hidden" name="kategori" id="kategori">
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary custom-btn">
                                    <i class="fas fa-plus me-1"></i> Buat Laporan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daftar Periode yang Sudah Ada -->
        <div class="col-md-12">
            <div class="card shadow-lg">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0">Pilih Periode Pelaporan Profil Resiko Yang Sudah Ada</h4>
                </div>
                <div class="card-body">
                    <?php if (empty($periodes)): ?>
                        <div class="alert alert-warning mb-0">
                            Belum ada periode yang dibuat.
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($periodes as $periodeprofilresiko): ?>
                                <form action="<?= base_url('periodeprofilresiko/handlePeriode') ?>" method="post" class="mb-2">
                                    <input type="hidden" name="action" value="select">
                                    <input type="hidden" name="periode_id" value="<?= $periodeprofilresiko['id'] ?>">

                                    <button type="submit"
                                        class="list-group-item list-group-item-action <?= ($current_periode == $periodeprofilresiko['id']) ? 'active' : '' ?>">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong style="font-size: 20px;">Periode Pelaporan Semester
                                                    <?= esc($periodeprofilresiko['semester']) ?>
                                                    Tahun <?= esc($periodeprofilresiko['tahun']) ?>
                                                </strong><br>
                                                <small style="font-size: 13px;">Dibuat:
                                                    <?= date('d M Y', strtotime($periodeprofilresiko['created_at'])) ?>
                                                </small><br>
                                                <strong style="font-size: 15px;">Modal Inti:
                                                    <?= "Rp " . number_format(esc($periodeprofilresiko['modalinti']), 0, ',', '.') ?></strong>
                                                <br>
                                                <strong style="font-size: 15px;">Total Aset:
                                                    <?= "Rp " . number_format(esc($periodeprofilresiko['totalaset']), 0, ',', '.') ?></strong>
                                                <br>
                                                <strong style="font-size: 15px;">Jumlah Kantor Cabang saat pelaporan:
                                                    <?= esc($periodeprofilresiko['kantorcabang']) ?> Kantor Cabang</strong>
                                                <br>
                                                <strong style="font-size: 15px;">
                                                    Kegiatan sebagai penerbit kartu ATM atau kartu debit:
                                                    <?= $periodeprofilresiko['atmdebit'] == 1 ? 'Ya' : 'Tidak' ?>
                                                </strong>
                                            </div>
                                            <?php if ($current_periode == $periodeprofilresiko['id']): ?>
                                                <span style="font-size: 16px;" class="badge bg-light text-primary">
                                                    <i class="fas fa-check"></i> Aktif
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </button>
                                </form>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>


<script>
    function formatRupiah(element) {
        let value = element.value;

        // Hapus semua karakter selain angka
        value = value.replace(/[^0-9]/g, "");

        // Format angka dengan titik sebagai pemisah ribuan
        if (value.length > 3) {
            value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        // Update input dengan format rupiah
        element.value = value;
    }

    // Mengirim data tanpa titik pemisah saat submit
    document.querySelector('form').onsubmit = function () {
        let modalinti = document.getElementById('modalinti');
        let totalaset = document.getElementById('totalaset');

        // Menghapus titik sebelum mengirimkan data
        modalinti.value = modalinti.value.replace(/\./g, '');
        totalaset.value = totalaset.value.replace(/\./g, '');

        // Set input value menjadi angka tanpa titik
        return true;
    };
</script>

<script>
    document.querySelectorAll('.dropdown-item').forEach(item => {
        item.addEventListener('click', e => {
            e.preventDefault();
            const value = item.dataset.value;
            const label = item.querySelector('strong').textContent;
            document.getElementById('kategori').value = value;
            document.getElementById('dropdownKategori').innerHTML = label;
        });
    });
</script>

<style>
    /* Styling untuk Accordion */
    .accordion-item {
        border: 1px solid #ddd;
        border-radius: 8px;
        margin-bottom: 15px;
        margin-left: 12px;
        overflow: hidden;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .accordion-header {
        background-color: #f9f9f9;
        margin-bottom: 0;
    }

    .accordion-button {
        width: 100%;
        text-align: left;
        padding: 15px 20px;
        font-size: 17px;
        font-weight: bold;
        color: #fff;
        background-color: #007bff;
        border: none;
        cursor: pointer;
        transition: background-color 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .accordion-button:hover {
        background-color: #f0f0f0;
        color: #007bff;
        border: #007bff;
    }

    .accordion-button:focus {
        outline: none;
        box-shadow: 0 0 5px rgba(76, 175, 80, 0.4);
    }

    .accordion-button::after {
        flex-shrink: 0;
        width: 1.25rem;
        height: 1.25rem;
        margin-left: auto;
        background-repeat: no-repeat;
        background-size: 1.25rem;
        transition: transform 0.2s ease-in-out;
    }

    .accordion-button:not(.collapsed)::after {

        transform: rotate(-180deg);
    }

    .accordion-collapse {
        border-top: 1px solid #ddd;
    }

    .accordion-body {
        padding: 20px;
        background-color: #fff;
    }

    .form-select {
        width: 100%;
        padding: 12px 20px;
        font-size: 17px;
        color: #555;
        border-radius: 8px;
        border: 1px solid #ccc;
        background-color: #f9f9f9;
        transition: all 0.3s ease;
    }

    .form-select:focus {
        outline: none;
        border-color: #4CAF50;
        box-shadow: 0 0 5px rgba(76, 175, 80, 0.4);
    }

    .form-control {
        width: 100%;
        padding: 12px 20px;
        font-size: 17px;
        color: #555;
        border-radius: 8px;
        border: 1px solid #ccc;
        background-color: #f9f9f9;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        outline: none;
        border-color: #4CAF50;
        box-shadow: 0 0 5px rgba(76, 175, 80, 0.4);
    }

    .form-control:focus {
        outline: none;
        border-color: #4CAF50;
        box-shadow: 0 0 5px rgba(76, 175, 80, 0.4);
    }

    .form-select option {
        padding: 10px;
    }

    /* Optional: Add a custom arrow to the select */
    .form-select::-ms-expand {
        display: none;
    }

    .form-select {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        background-image: url('data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 6"%3E%3Cpath d="M0 0l5 5 5-5z" fill="none" stroke="%23999" stroke-width="1.5" /%3E%3C/svg%3E');
        background-repeat: no-repeat;
        background-position: right 10px center;
        background-size: 10px 6px;
    }

    .list-group {
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .list-group-item {
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 12px;
        font-size: 16px;
        transition: all 0.3s ease;
        background-color: #fff;
        color: #333;
    }

    .list-group-item:hover {
        background-color: #f8f9fa;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .list-group-item.active {
        background-color: #4CAF50;
        color: #fff;
        border-color: #4CAF50;
    }

    .list-group-item .d-flex {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .badge {
        font-size: 14px;
        padding: 5px 10px;
        border-radius: 12px;
    }

    .badge.bg-light {
        background-color: #f8f9fa !important;
    }

    .badge.text-primary {
        color: #007bff;
    }

    .custom-btn {
        padding: 12px 25px;
        font-size: 16px;
        border-radius: 50px;
        /* Membuat tombol dengan sudut membulat */
        background-color: #007bff;
        /* Warna latar belakang */
        color: #fff;
        /* Warna teks */
        border: none;
        /* Menghapus border default */
        box-shadow: 0 4px 6px rgba(0, 123, 255, 0.3);
        /* Memberikan bayangan pada tombol */
        transition: all 0.3s ease;
        /* Efek transisi untuk hover */
    }

    .custom-btn:hover {
        background-color: #0056b3;
        /* Mengubah warna latar belakang saat hover */
        transform: translateY(-3px);
        /* Memberikan efek sedikit terangkat saat hover */
        box-shadow: 0 6px 10px rgba(0, 123, 255, 0.4);
        /* Memberikan bayangan lebih besar saat hover */
    }

    .custom-btn:focus {
        outline: none;
        /* Menghapus outline saat tombol difokuskan */
        box-shadow: 0 0 8px rgba(0, 123, 255, 0.6);
        /* Memberikan bayangan biru saat fokus */
    }

    .custom-btn i {
        font-size: 18px;
        /* Ukuran ikon yang lebih besar */
    }

    .dropdown-menu .dropdown-item {
        background-color: #fff;
        border: 1px solid #ddd;
        margin-bottom: 8px;
        transition: all 0.3s ease;
    }

    .dropdown-menu .dropdown-item:hover {
        background-color: #f8f9fa;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .dropdown-menu .dropdown-item strong {
        font-size: 17px;
    }

    .dropdown-menu .dropdown-item small {
        font-size: 15px;
        color: #555;
        white-space: normal;
        line-height: 1.5;
    }


    .form-select[aria-expanded="true"] {
        border-color: #007bff;
        box-shadow: 0 0 5px rgba(0, 123, 255, 0.4);
    }
</style>