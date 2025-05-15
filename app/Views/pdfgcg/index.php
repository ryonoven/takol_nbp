<div class="container mt-4">
    <h1><?= esc($judul) ?></h1>

    <p>Silakan klik tombol di bawah ini untuk mengunduh laporan PDF:</p>

    <a href="<?= base_url('pdf/pdfPenjelasanUmum') ?>" class="btn btn-primary" target="_blank">
        Unduh Laporan Penjelasan Umum
    </a>

    <hr>

    <!-- Tampilkan jumlah data dari masing-masing model (opsional) -->
    <h5>Ringkasan Data:</h5>
    <ul>
        <li>Penjelasan Umum: <?= count($penjelasanumum) ?> entri</li>
        <li>Tanggung Jawab Direksi: <?= count($tgjwbdir) ?> entri</li>
        <li>Tanggung Jawab Dewan Komisaris: <?= count($tgjwbdekom) ?> entri</li>
        <li>Tanggung Jawab Komite: <?= count($tgjwbkomite) ?> entri</li>
        <li>Struktur Komite: <?= count($strukturkomite) ?> entri</li>
        <li>Saham Direksi dan Komisaris: <?= count($sahamdirdekom) ?> entri</li>
        <li>SHM Usaha: <?= count($shmusahadirdekom) ?> entri</li>
        <li>SHM Lain: <?= count($shmdirdekomlain) ?> entri</li>
        <li>Keuangan Direksi/Komisaris: <?= count($keuangandirdekompshm) ?> entri</li>
        <li>Keluarga Pemilik Saham: <?= count($keluargadirdekompshm) ?> entri</li>
        <li>Paket Kebijakan: <?= count($paketkebijakandirdekom) ?> entri</li>
        <li>Rasio Gaji: <?= count($rasiogaji) ?> entri</li>
        <li>Rapat: <?= count($rapat) ?> entri</li>
        <li>Kehadiran Dewan Komisaris: <?= count($kehadirandekom) ?> entri</li>
        <li>Fraud Internal: <?= count($fraudinternal) ?> entri</li>
        <li>Masalah Hukum: <?= count($masalahhukum) ?> entri</li>
        <li>Transaksi Kepentingan: <?= count($transaksikepentingan) ?> entri</li>
        <li>Informasi BPR: <?= count($infobpr) ?> entri</li>
        <li>Dana Sosial: <?= count($danasosial) ?> entri</li>
    </ul>
</div>
