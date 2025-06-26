<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar"
    style="position: sticky; top: 0; left: 0; overflow-y: auto; overflow-x: hidden; height: 100vh; width: 280px !important;">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?= base_url('Home'); ?>">
        <img class="img-profile" src="/asset/img/logo/logo.png">
    </a>

    <div class="sidebar-heading">
    </div>

    <li class="nav-item">
        <a class="nav-link collapsed" href="<?= base_url('Home'); ?>">
            <i class="fas fa-users"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <?php if (in_groups('admin')): ?>
        <!-- Divider -->
        <hr class="sidebar-divider">

        <!-- Heading -->
        <div class="sidebar-heading">
            Users Management
        </div>

        <!-- Nav Item - Users List -->
        <li class="nav-item">
            <a class="nav-link collapsed" href="<?= base_url('admin'); ?>">
                <i class="fas fa-users"></i>
                <span>Users List</span>
            </a>
        </li>
    <?php endif; ?>
    <!-- Nav Item - Users List -->

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        Input Session
    </div>

    <li class="nav-item">
        <a class="nav-link collapsed" href="<?= base_url('infobpr'); ?>">
            <i class="fas fa-laptop-house"></i></i>
            <span>Informasi BPR</span>
        </a>
    </li>

    <!-- <li class="nav-item">
        <a class="nav-link collapsed" href="<?= base_url('periode'); ?>">
            <i class="fas fa-laptop-house"></i></i>
            <span>Periode Pelaporan</span>
        </a>
    </li> -->

    <!-- <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#pagesCollapseTransparansi"
            aria-expanded="false" aria-controls="pagesCollapseTransparansi">
            <i class="fas fa-fw fa-table"></i>
            <span>Transparansi Tahunan</span>
        </a>
        <div class="collapse" id="pagesCollapseTransparansi" data-parent="#accordionSidebar">
            <nav class="sidenav-menu-nested nav">
                <a class="nav-link" href="<?= base_url('penjelasanumum'); ?>" style="font-size: 14px;">1. Penjelasan
                    Umum</a>
                <a class="nav-link" href="<?= base_url('tgjwbdir'); ?>" style="font-size: 14px;">2. Pelaksanaan Tugas
                    dan
                    Tanggung Jawab Anggota Direksi</a>
                <a class="nav-link" href="<?= base_url('tgjwbdekom'); ?>" style="font-size: 14px;">3. Pelaksanaan Tugas
                    dan
                    Tanggung Jawab Anggota Dewan Komisaris</a>
                <a class="nav-link" href="<?= base_url('tgjwbkomite'); ?>" style="font-size: 14px;">4. Tugas, Tanggung
                    Jawab, Program Kerja, dan Realisasi Program Kerja Komite</a>
                <a class="nav-link" href="<?= base_url('strukturkomite'); ?>" style="font-size: 14px;">5. Struktur,
                    Keanggotaan,
                    Keahlian, dan Independensi Anggota Komite</a>
                <a class="nav-link" href="<?= base_url('sahamdirdekom'); ?>" style="font-size: 14px;">6. Kepemilikan
                    Saham Anggota
                    Direksi dan Dewan Komisaris pada BPR</a>
                <a class="nav-link" href="<?= base_url('shmusahadirdekom'); ?>" style="font-size: 14px;">7. Kepemilikan
                    Saham Anggota
                    Direksi, Dewan Komisaris, dan Pemegang Saham pada Kelompok Usaha BPR</a>
                <a class="nav-link" href="<?= base_url('shmdirdekomlain'); ?>" style="font-size: 14px;">8. Kepemilikan
                    Saham Anggota
                    Direksi dan Dewan Komisaris pada Perusahaan Lain</a>
                <a class="nav-link" href="<?= base_url('keuangandirdekompshm'); ?>" style="font-size: 14px;">9. Hubungan
                    Keuangan Anggota
                    Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR</a>
                <a class="nav-link" href="<?= base_url('keluargadirdekompshm'); ?>" style="font-size: 14px;">10.
                    Hubungan Keluarga Anggota
                    Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR</a>
                <a class="nav-link" href="<?= base_url('paketkebijakandirdekom'); ?>" style="font-size: 14px;">11.
                    Paket/Kebijakan Remunerasi dan
                    Fasilitas Lain bagi Direksi dan Dewan Komisaris</a>
                <a class="nav-link" href="<?= base_url('rasiogaji'); ?>" style="font-size: 14px;">12. Rasio Gaji
                    Tertinggi dan Gaji
                    Terendah</a>
                <a class="nav-link" href="<?= base_url('rapat'); ?>" style="font-size: 14px;">13. Pelaksanaan Rapat
                    dalam 1
                    (satu) tahun</a>
                <a class="nav-link" href="<?= base_url('kehadirandekom'); ?>" style="font-size: 14px;">14. Kehadiran
                    Anggota Dewan
                    Komisaris</a>
                <a class="nav-link" href="<?= base_url('fraudinternal'); ?>" style="font-size: 14px;">15. Jumlah
                    Penyimpangan Intern
                    (Internal Fraud)</a>
                <a class="nav-link" href="<?= base_url('masalahhukum'); ?>" style="font-size: 14px;">16. Permasalahan
                    Hukum yang
                    Dihadapi</a>
                <a class="nav-link" href="<?= base_url('transaksikepentingan'); ?>" style="font-size: 14px;">17.
                    Transaksi yang Mengandung
                    Benturan Kepentingan</a>
                <a class="nav-link" href="<?= base_url('danasosial'); ?>" style="font-size: 14px;">18. Pemberian Dana
                    untuk Kegiatan
                    Sosial dan Kegiatan Politik</a>
            </nav>
        </div>
    </li> -->

    <!-- <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#pagesCollapseFaktor"
            aria-expanded="false" aria-controls="pagesCollapseFaktor">
            <i class="fas fa-fw fa-table"></i>
            <span>Self Assessment</span>
        </a>
        <div class="collapse" id="pagesCollapseFaktor" data-parent="#accordionSidebar">
            <nav class="sidenav-menu-nested nav">
                <a class="nav-link" href="<?= base_url('Faktor'); ?>" style="font-size: 14px;">Faktor 1</a>
                <a class="nav-link" href="<?= base_url('faktor2'); ?>" style="font-size: 14px;">Faktor 2</a>
                <a class="nav-link" href="<?= base_url('faktor3'); ?>" style="font-size: 14px;">Faktor 3</a>
                <a class="nav-link" href="<?= base_url('faktor4'); ?>" style="font-size: 14px;">Faktor 4</a>
                <a class="nav-link" href="<?= base_url('faktor5'); ?>" style="font-size: 14px;">Faktor 5</a>
                <a class="nav-link" href="<?= base_url('faktor6'); ?>" style="font-size: 14px;">Faktor 6</a>
                <a class="nav-link" href="<?= base_url('faktor7'); ?>" style="font-size: 14px;">Faktor 7</a>
                <a class="nav-link" href="<?= base_url('faktor8'); ?>" style="font-size: 14px;">Faktor 8</a>
                <a class="nav-link" href="<?= base_url('faktor9'); ?>" style="font-size: 14px;">Faktor 9</a>
                <a class="nav-link" href="<?= base_url('faktor10'); ?>" style="font-size: 14px;">Faktor 10</a>
                <a class="nav-link" href="<?= base_url('faktor11'); ?>" style="font-size: 14px;">Faktor 11</a>
                <a class="nav-link" href="<?= base_url('faktor12'); ?>" style="font-size: 14px;">Faktor 12</a>
                <a class="nav-link" href="<?= base_url('showFaktor'); ?>" style="font-size: 14px;">Ringkasan</a>
            </nav>
        </div>
    </li> -->

    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#pagesCollapseTahun"
            aria-expanded="false" aria-controls="pagesCollapseTahun">
            <i class="fas fa-fw fa-table"></i>
            <span>Laporan Tahunan</span>
        </a>
        <div class="collapse" id="pagesCollapseTahun" data-parent="#accordionSidebar">
            <nav class="sidenav-menu-nested nav">
                <a class="nav-link" href="<?= base_url('penjelasanumum'); ?>" style="font-size: 14px;">Laporan
                    Transparansi Tata Kelola</a>
                <a class="nav-link" href="<?= base_url(''); ?>" style="font-size: 14px;">Laporan
                    Keberlanjutan</a>
            </nav>
        </div>
    </li>

    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#pagesCollapseRisk"
            aria-expanded="false" aria-controls="pagesCollapseRisk">
            <i class="fas fa-exclamation-triangle"></i>
            <span>Laporan Profil Risiko</span>
        </a>
        <div class="collapse" id="pagesCollapseRisk" data-parent="#accordionSidebar">
            <nav class="sidenav-menu-nested nav">
                <a class="nav-link" href="<?= base_url(''); ?>" style="font-size: 14px;">Profil Risiko BPR</a>
                <a class="nav-link" href="<?= base_url('periode'); ?>" style="font-size: 14px;">Self Assessment Tata Kelola</a>
            </nav>
        </div>
    </li>

    <!-- Sidebar Toggler (Sidebar) -->
    <!-- <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div> -->


    <!-- Nav Item - Pages Form Bisnis Menu -->
    <!-- <li class="nav-item">
        <a class="nav-link collapsed" href="">
            <i class="fas fa-fw fa-table"></i>
            <span>Form Bisnis</span>
        </a>
    </li> -->
</ul>

<style>
    /* Sidebar - Dark Mode */
    .sidebar {
        background: linear-gradient(145deg, #0d1b2a, #2a3b5c);
        /* Gradien biru gelap untuk dark mode */
        /* Hapus border-radius untuk menghindari ujung yang melengkung */
        border-radius: 0;
        box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.15);
        /* Bayangan lebih halus */
        height: 100vh;
        position: sticky;
        top: 0;
        left: 0;
        z-index: 99;
        padding: 15px 20px;
        width: 280px;
        /* Lebar Sidebar */
    }

    /* Sidebar pada perangkat mobile */
    @media (max-width: 991px) {
        .sidebar {
            width: 250px;
            /* Lebar sidebar lebih kecil pada perangkat mobile */
        }
    }

    /* Sidebar Brand (Logo) */
    .sidebar-brand {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-bottom: 30px;
        padding: 10px;
    }

    .sidebar-brand img {
        max-width: 80%;
        /* Hapus border-radius untuk logo jika ingin sudut logo juga kotak */
        border-radius: 0;
    }

    /* Heading di Sidebar */
    .sidebar-heading {
        font-size: 18px;
        font-weight: bold;
        color: #d1d1d1;
        /* Warna putih abu-abu untuk teks */
        text-transform: uppercase;
        margin-bottom: 10px;
    }

    /* Nav Item - Link */
    .nav-item {
        margin: 10px 0;
    }

    .nav-link {
        color: #d1d1d1;
        /* Warna abu-abu muda untuk teks */
        display: flex;
        align-items: center;
        padding: 12px 20px;
        /* Menghilangkan border-radius agar sudut tetap tajam */
        border-radius: 0;
        font-size: 16px;
        text-decoration: none;
        transition: background-color 0.3s ease, transform 0.3s ease;
    }

    /* Hover effect untuk nav-link */
    .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.1);
        /* Efek hover dengan latar belakang sedikit terang */
        transform: translateX(10px);
        /* Memberikan efek bergerak sedikit */
    }

    /* Menandai Item Aktif */
    .nav-link.active {
        background-color: #004085;
        /* Warna latar belakang untuk item aktif */
        color: #fff;
    }

    /* Sidebar Toggler */
    #sidebarToggle {
        background-color: #fff;
        border: none;
        width: 40px;
        height: 40px;
        /* Hapus border-radius jika tidak ingin tombol melengkung */
        border-radius: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        cursor: pointer;
    }

    #sidebarToggle:hover {
        background-color: #003366;
        transform: scale(1.1);
    }

    /* Mengatur scrollbar untuk sidebar */
    .sidebar::-webkit-scrollbar {
        width: 6px;
        /* Lebar scrollbar lebih tipis */
    }

    .sidebar::-webkit-scrollbar-thumb {
        background-color: rgba(255, 255, 255, 0.4);
        /* Warna scrollbar lebih terang untuk gelap */
        border-radius: 10px;
        /* Sudut membulat untuk scrollbar */
        border: 2px solid #2a3b5c;
        /* Memberikan batas tipis di sekitar scrollbar */
    }

    .sidebar::-webkit-scrollbar-thumb:hover {
        background-color: rgba(255, 255, 255, 0.6);
        /* Efek hover untuk scrollbar */
    }

    .sidebar::-webkit-scrollbar-track {
        background-color: transparent;
        /* Menghilangkan latar belakang pada track scrollbar */
        border-radius: 10px;
        /* Sudut track scrollbar juga lebih halus */
    }

    /* Sidebar pada perangkat mobile */
    @media (max-width: 991px) {
        .sidebar {
            width: 250px;
            /* Lebar sidebar lebih kecil pada perangkat mobile */
        }
    }
</style>