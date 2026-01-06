<div class="container my-4">
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
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Buat Periode Baru untuk Laporan Transparansi Tahunan</h4>
                </div>
                <div class="card-body">
                    <form action="<?= base_url('periodetransparansi/handlePeriode') ?>" method="post" novalidate>
                        <input type="hidden" name="action" value="create">

                        <div class="mb-3">
                            <label for="tahun" class="form-label">Tahun</label>
                            <select id="tahun" name="tahun" class="form-select" required>
                                <?php for ($year = 2024; $year <= 2030; $year++): ?>
                                    <option value="<?= $year ?>"><?= $year ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary custom-btn">
                                <i class="me-1"></i> Buat Laporan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Daftar Periode yang Sudah Ada -->
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0">Pilih Periode Pelaporan Transparansi Tahunan Yang Sudah Ada</h4>
                </div>
                <div class="card-body">
                    <?php if (empty($periodes)): ?>
                        <div class="alert alert-warning mb-0">
                            Belum ada periode yang dibuat.
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($periodes as $periode): ?>
                                <form action="<?= base_url('periodetransparansi/handlePeriode') ?>" method="post" class="mb-2">
                                    <input type="hidden" name="action" value="select">
                                    <input type="hidden" name="periode_id" value="<?= $periode['id'] ?>">

                                    <button type="submit"
                                        class="list-group-item list-group-item-action <?= ($current_periode == $periode['id']) ? 'active' : '' ?>">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>Periode Pelaporan Tahun <?= esc($periode['tahun']) ?></strong><br>
                                                <small>Dibuat: <?= date('d M Y', strtotime($periode['created_at'])) ?></small>
                                            </div>
                                            <?php if ($current_periode == $periode['id']): ?>
                                                <span class="badge bg-light text-primary">
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

<style>
    .form-select {
        width: 100%;
        padding: 12px 20px;
        font-size: 16px;
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
        color: #141863;
    }

    .custom-btn {
        padding: 12px 25px;
        font-size: 16px;
        border-radius: 50px;
        /* Membuat tombol dengan sudut membulat */
        background-color: #141863;
        /* Warna latar belakang */
        color: #fff;
        /* Warna teks */
        border: none;
        /* Menghapus border default */
        transition: all 0.3s ease;
        /* Efek transisi untuk hover */
    }

    .custom-btn:hover {
        background-color: #ffffff;
        /* Mengubah warna latar belakang saat hover */
        transform: translateY(-3px);
        /* Memberikan efek sedikit terangkat saat hover */
        box-shadow: 0 6px 10px rgba(0, 123, 255, 0.4);
        /* Memberikan bayangan lebih besar saat hover */
        color: #141863;
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
</style>