<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar"
    style="position: sticky; top: 0; left: 0; overflow-y: hidden; overflow-x: hidden; height: 100vh; width: 280px !important;">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?= base_url('Home'); ?>">
        <img class="img-profile" src="/asset/img/logo/logo.png">
    </a>

    <div class="sidebar-heading">
    </div>

    <li class="nav-item">
        <a class="nav-link collapsed" href="<?= base_url('Home'); ?>">
            <i class="fas fa-house-user"></i>
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

    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#pagesCollapseTahun"
            aria-expanded="false" aria-controls="pagesCollapseTahun">
            <i class="fas fa-fw fa-table"></i>
            <span>Laporan Tahunan</span>
        </a>
        <div class="collapse" id="pagesCollapseTahun" data-parent="#accordionSidebar">
            <nav class="sidenav-menu-nested nav">
                <a class="nav-link" href="<?= base_url('periodetransparansi'); ?>" style="font-size: 14px;">Laporan
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
                <a class="nav-link" href="<?= base_url('periodeprofilresiko'); ?>" style="font-size: 14px;">Profil
                    Risiko BPR</a>
                <a class="nav-link" href="<?= base_url('periode'); ?>" style="font-size: 14px;">Self Assessment Tata
                    Kelola</a>
            </nav>
        </div>
    </li>
</ul>

<style>
    .sidebar {
        background: linear-gradient(145deg, #fff, #fff);
        border-radius: 0;
        box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.15);
        height: 100vh;
        position: sticky;
        top: 0;
        left: 0;
        z-index: 99;
        padding: 15px 20px;
        width: 280px;
        font-weight: bold;
    }

    /* Sidebar pada perangkat mobile */
    @media (max-width: 991px) {
        .sidebar {
            width: 250px;
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
        color: #2a3b5c;
        /* Warna putih abu-abu untuk teks */
        text-transform: uppercase;
        margin-bottom: 10px;
    }

    /* Nav Item - Link */
    .nav-item {
        margin: 10px 0;
    }

    .nav-link {
        color: #2a3b5c;
        display: flex;
        align-items: center;
        padding: 12px 20px;
        border-radius: 0;
        font-size: 16px;
        text-decoration: none;
        transition: background-color 0.3s ease, transform 0.3s ease;
    }

    /* Hover effect untuk nav-link */
    .nav-link:hover {
        background-color: #141863;
        /* Warna biru saat hover */
        color: white;
        /* Mengubah warna teks menjadi putih saat hover */
        transform: translateX(10px);
        /* Memberikan efek bergerak sedikit */
    }

    .nav-link2:hover {
        background-color: #fff;
        /* Warna biru saat hover */
        color: grey;
        /* Mengubah warna teks menjadi putih saat hover */
        transform: translateX(5px);
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