<!-- Content Wrapper -->
<div id="content-wrapper" class="d-flex flex-column">

    <!-- Main Content -->
    <div id="content">

        <!-- Topbar -->
        <nav class="navbar navbar-expand navbar-light bg-white topbar mb-2 sticky-top shadow">
            <nav class="navbar bg-body-tertiary">
            </nav>

            <!-- Sidebar Toggle (Topbar) -->
            <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                <i class="fa fa-bars"></i>
            </button>

            <!-- Topbar Navbar -->
            <ul class="navbar-nav ml-auto">

                <!-- Nav Item - User Information -->
                <li class="nav-item dropdown no-arrow">
                    <a class="nav-link2 dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown"
                        aria-haspopup="true" aria-expanded="false">
                        <div class="topbar-divider d-none d-sm-block"></div>
                        <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?= user()->fullname; ?></span>
                        <img class="img-profile rounded-circle"
                            src="<?= base_url(); ?>/asset/img/<?= user()->user_image; ?>">
                    </a>
                    <!-- Dropdown - User Information -->
                    <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                        aria-labelledby="userDropdown">
                        <a class="dropdown-item" href="<?= base_url('user') ?>">
                            <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                            Profile
                        </a>
                        <!-- <a class="dropdown-item" href="#">
                            <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                            Settings
                        </a>
                        <a class="dropdown-item" href="#">
                            <i class="fas fa-list fa-sm fa-fw mr-2 text-gray-400"></i>
                            Activity Log
                        </a> -->
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                            <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                            Logout
                        </a>
                    </div>
                </li>

            </ul>

        </nav>
        <!-- End of Topbar -->

        <style>
            /* Custom Styles for Topbar */
            .topbar {
                background-color: #fff;
                /* White background for a clean look */
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
                /* Softer shadow for elegance */
                padding: 0.75rem 1.5rem;
                /* Slightly more vertical padding */
                height: 11vh;
                /* Allow height to adjust based on content */
                display: flex;
                /* Ensure flexbox layout for alignment */
                align-items: center;
                /* Vertically align items */
                justify-content: space-between;
                /* Space out items */
            }

            /* Specific styling for the inner navbar if needed, though topbar itself usually suffices */
            .topbar .navbar {
                padding: 0;
                /* Remove default padding from inner navbar if it causes issues */
            }

            /* Sidebar Toggle (Topbar) button */
            #sidebarToggleTop {
                background-color: transparent;
                /* Transparent background */
                border: none;
                /* No border */
                color: #4a5c6c;
                /* Dark icon color for contrast on white */
                font-size: 1.5rem;
                /* Larger icon */
                transition: color 0.3s ease, transform 0.3s ease;
            }

            #sidebarToggleTop:hover {
                color: #fff;
                /* Primary blue on hover */
                transform: scale(1.1);
                /* Slight zoom effect */
            }

            /* User Information Section */
            .navbar-nav .nav-item.dropdown.no-arrow {
                display: flex;
                /* Use flexbox for alignment */
                align-items: center;
                /* Vertically align items */
            }

            .navbar-nav .nav-item.dropdown.no-arrow .nav-link {
                display: flex;
                align-items: center;
                padding: 0.5rem 1rem;
                /* Adjust padding around user info */
            }

            /* User Fullname */
            .topbar .text-gray-600.small {
                color: #4a5c6c !important;
                /* Ensure dark text for the username */
                font-size: 0.95rem;
                /* Slightly larger font for readability */
                font-weight: 500;
                /* A bit bolder */
                margin-right: 0.5rem;
                /* Space between name and image */
            }

            /* User Profile Image */
            .topbar .img-profile {
                width: 40px;
                /* Standard size for profile image */
                height: 40px;
                /* Standard size for profile image */
                object-fit: cover;
                /* Ensures image fills the space without distortion */
                border: 2px solid #e0e0e0;
                /* Subtle light grey border */
            }

            /* Dropdown Menu (User Information) */
            .dropdown-menu.shadow.animated--grow-in {
                border-radius: 0.5rem;
                /* Slightly rounded corners for the dropdown */
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
                /* More prominent shadow for depth */
                margin-top: 0.75rem;
                /* Space below the topbar */
            }

            .dropdown-menu .dropdown-item {
                color: #4a5c6c;
                /* Dark text for dropdown items */
                padding: 0.75rem 1.25rem;
                /* More padding for clickable area */
                transition: background-color 0.2s ease, color 0.2s ease;
            }

            .dropdown-menu .dropdown-item:hover {
                background-color: #f8f9fa;
                /* Light background on hover */
                color: #007bff;
                /* Primary blue text on hover */
            }

            .dropdown-menu .dropdown-item .fas {
                color: #8898aa;
                /* Softer color for icons in dropdown */
            }

            .dropdown-menu .dropdown-item:hover .fas {
                color: #007bff;
                /* Primary blue for icons on hover */
            }

            .dropdown-divider {
                border-top: 1px solid rgba(0, 0, 0, 0.08);
                /* Lighter divider */
            }

            /* Hide Topbar Divider (often unnecessary if elements are well-spaced) */
            .topbar-divider.d-none.d-sm-block {
                display: none !important;
                /* Hide the vertical divider between user elements */
            }

            /* Ensure proper spacing for the main content area below topbar */
            #content {
                margin-top: 0;
                /* Reset default margin if any, as topbar handles spacing */
            }
        </style>