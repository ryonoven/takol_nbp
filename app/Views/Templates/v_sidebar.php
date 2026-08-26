<!-- Sidebar Toggle Button (Mobile) -->
<button id="sidebarToggleBtn" class="sidebar-toggle-btn">
    <i class="fas fa-bars"></i>
</button>

<!-- Overlay untuk menutup sidebar saat klik di luar (mobile) -->
<div id="sidebarOverlay" class="sidebar-overlay"></div>

<ul class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center mt-2" href="<?= base_url('Home'); ?>">
        <img class="img-profile" src="/asset/img/logo/logo.png" alt="Logo">
    </a>

    <div class="sidebar-heading">
    </div>

    <!-- Dashboard -->
    <li class="nav-item">
        <a class="nav-link" href="<?= base_url('Home'); ?>">
            <i class="fas fa-house-user"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <?php if (in_groups('admin')): ?>
        <hr class="sidebar-divider">
        <div class="sidebar-heading">
            Users Management
        </div>

        <!-- Nav Item - Users List -->
        <li class="nav-item">
            <a class="nav-link" href="<?= base_url('admin'); ?>">
                <i class="fas fa-users"></i>
                <span>Users List</span>
            </a>
        </li>
    <?php endif; ?>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        Input Session
    </div>

    <!-- Informasi BPR -->
    <li class="nav-item">
        <a class="nav-link" href="<?= base_url('infobpr'); ?>">
            <i class="fas fa-laptop-house"></i>
            <span>Informasi BPR</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link" href="<?= base_url('Periodesemesteran'); ?>">
            <i class="fas fa-table"></i>
            <span>Laporan Semesteran</span>
        </a>
        <a class="nav-link" href="<?= base_url('Periodetahunan'); ?>">
            <i class="fas fa-table"></i>
            <span>Laporan Tahunan</span>
        </a>
    </li>

    <!-- Laporan Tahunan (2 Level Only) -->
    <!-- <li class="nav-item">
        <a class="nav-link has-submenu" href="javascript:void(0)">
            <i class="fas fa-fw fa-table"></i>
            <span>Laporan Tahunan</span>
            <i class="fas fa-chevron-down ml-auto arrow-icon"></i>
        </a>
        <ul class="submenu">
            <li>
                <a class="submenu-link" href="<?= base_url('periodetransparansi'); ?>">
                    <i class="fas fa-eye"></i>
                    <span>Laporan Transparansi</span>
                </a>
            </li>
            <li>
                <a class="submenu-link" href="<?= base_url('periodekeberlanjutan'); ?>">
                    <i class="fas fa-leaf"></i>
                    <span>Laporan Keberlanjutan</span>
                </a>
            </li>
        </ul>
    </li> -->
</ul>

<style>
    /* Sidebar Toggle Button (Mobile) */
    .sidebar-toggle-btn {
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 1001;
        background: linear-gradient(135deg, #5b47ed 0%, #3730a3 100%);
        border: none;
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: none;
        justify-content: center;
        align-items: center;
        box-shadow: 0 4px 15px rgba(91, 71, 237, 0.4);
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .sidebar-toggle-btn i {
        color: white;
        font-size: 20px;
    }

    .sidebar-toggle-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 20px rgba(91, 71, 237, 0.6);
    }

    /* Sidebar Close Button */
    .sidebar-close-btn {
        position: absolute;
        top: 20px;
        right: 20px;
        background: transparent;
        border: none;
        width: 35px;
        height: 35px;
        border-radius: 8px;
        display: none;
        justify-content: center;
        align-items: center;
        cursor: pointer;
        transition: all 0.3s ease;
        z-index: 10;
    }

    .sidebar-close-btn i {
        color: #6b7280;
        font-size: 20px;
    }

    .sidebar-close-btn:hover {
        background: rgba(107, 114, 128, 0.1);
    }

    /* Sidebar Overlay */
    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 998;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .sidebar-overlay.active {
        display: block;
        opacity: 1;
    }

    /* Sidebar Styling */
    .sidebar {
        background: #ffffff;
        border-right: 1px solid #e5e7eb;
        box-shadow: 4px 0 15px rgba(0, 0, 0, 0.05);
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        z-index: 999;
        padding: 0;
        width: 280px;
        font-weight: 500;
        overflow-y: auto;
        overflow-x: hidden;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Sidebar Brand (Logo) */
    .sidebar-brand {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 24px 20px;
        position: relative;
        border-bottom: 0px solid #e5e7eb;
        background: #ffffff;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .sidebar-brand img {
        max-width: 60%;
        height: auto;
        transition: transform 0.3s ease;
    }

    .sidebar-brand:hover img {
        transform: scale(1.05);
    }

    /* Heading di Sidebar */
    .sidebar-heading {
        font-size: 11px;
        font-weight: 700;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: 1.2px;
        margin: 20px 0 12px 20px;
        padding: 0;
    }

    /* Sidebar Divider */
    .sidebar-divider {
        border-top: 1px solid #e5e7eb;
        margin: 16px 20px;
    }

    /* Nav Item */
    .nav-item {
        margin: 0;
        padding: 0;
    }

    /* Nav Link - Level 1 */
    .nav-link {
        color: #374151;
        display: flex;
        align-items: center;
        padding: 12px 16px;
        border-radius: 8px;
        font-size: 15px;
        text-decoration: none;
        transition: all 0.3s ease;
        position: relative;
        margin-bottom: 4px;
        cursor: pointer;
    }

    .nav-link i:first-child {
        margin-right: 12px;
        font-size: 16px;
        width: 20px;
        text-align: center;
        transition: all 0.3s ease;
    }

    .nav-link span {
        flex: 1;
        font-weight: 500;
    }

    /* Arrow Icon */
    .arrow-icon {
        font-size: 10px;
        transition: transform 0.3s ease;
        margin-left: auto;
    }

    /* Hover Effect - Level 1 */
    .nav-link:hover {
        background: linear-gradient(135deg, #1f2aa8 0%, #141863 100%);
        color: white;
    }

    .nav-link:hover i {
        color: white;
    }

    /* Active State */
    .nav-link.active {
        background: linear-gradient(135deg, #1f2aa8 0%, #141863 100%);
        color: #fff;
    }

    /* Submenu Container */
    .submenu {
        list-style: none;
        padding: 0;
        margin: 0 0 8px 0;
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        background: #f9fafb;
        border-radius: 8px;
    }

    .submenu.open {
        max-height: 500px;
        padding: 8px 0;
    }

    /* Submenu Item */
    .submenu li {
        margin: 0;
        padding: 0 8px;
    }

    /* Submenu Link */
    .submenu-link {
        color: #4b5563;
        display: flex;
        align-items: center;
        padding: 10px 16px;
        border-radius: 6px;
        font-size: 14px;
        text-decoration: none;
        transition: all 0.3s ease;
        cursor: pointer;
        margin-bottom: 4px;
    }

    .submenu-link i {
        font-size: 14px;
        margin-right: 10px;
        width: 16px;
        text-align: center;
        color: #6b7280;
        transition: all 0.3s ease;
    }

    .submenu-link span {
        flex: 1;
        font-weight: 500;
    }

    .submenu-link:hover {
        background: #e0e7ff;
        color: #5b47ed;
        padding-left: 20px;
    }

    .submenu-link:hover i {
        color: #5b47ed;
    }

    /* Active State for Submenu Items */
    .submenu-link.active {
        background: #dbeafe;
        color: #3730a3;
        font-weight: 600;
    }

    .submenu-link.active i {
        color: #3730a3;
    }

    /* Rotate Arrow when Open */
    .nav-link.open .arrow-icon {
        transform: rotate(180deg);
    }

    /* Scrollbar Styling */
    .sidebar::-webkit-scrollbar {
        width: 5px;
    }

    .sidebar::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, #5b47ed 0%, #3730a3 100%);
        border-radius: 10px;
    }

    .sidebar::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(135deg, #3730a3 0%, #5b47ed 100%);
    }

    .sidebar::-webkit-scrollbar-track {
        background: transparent;
    }

    /* Desktop View (Default: Sidebar Visible) */
    @media (min-width: 992px) {
        .sidebar {
            left: 0;
        }

        #wrapper {
            padding-left: 222px;
        }
    }

    /* Tablet & Mobile View */
    @media (max-width: 991px) {
        .sidebar-toggle-btn {
            display: flex;
        }

        .sidebar-close-btn {
            display: flex;
        }

        .sidebar {
            left: -280px;
        }

        .sidebar.active {
            left: 0;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15);
        }

        #wrapper {
            padding-left: 0;
        }
    }

    /* Mobile Small */
    @media (max-width: 576px) {
        .sidebar {
            width: 260px;
            left: -260px;
        }

        .sidebar.active {
            left: 0;
        }

        .sidebar-brand img {
            max-width: 70%;
        }
    }
</style>