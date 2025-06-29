<?php
if (!defined('BASE_PATH')) exit('No direct script access allowed');

$user = getCurrentUser();
?>

<header id="page-topbar">
    <div class="layout-width">
        <div class="navbar-header">
            <div class="d-flex">
                <!-- LOGO -->
                <div class="navbar-brand-box horizontal-logo">
                    <a href="<?php echo url($user['role']); ?>" class="logo logo-dark">
                        <span class="logo-sm">
                            <i class='bx bx-recycle'></i>
                        </span>
                        <span class="logo-lg">
                            <i class='bx bx-recycle'></i>
                            <span>WMS</span>
                        </span>
                    </a>
                </div>

                <button type="button" class="btn btn-sm px-3 fs-16 header-item vertical-menu-btn topnav-hamburger" id="topnav-hamburger-icon">
                    <span class="hamburger-icon">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                </button>
            </div>

            <div class="d-flex align-items-center">
                <!-- User profile -->
                <div class="dropdown ms-sm-3 header-item topbar-user">
                    <button type="button" class="btn" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="d-flex align-items-center">
                            <span class="text-start ms-xl-2">
                                <span class="d-none d-xl-inline-block ms-1 fw-medium user-name-text">
                                    <?php echo htmlspecialchars($user['name']); ?>
                                </span>
                                <span class="d-none d-xl-block ms-1 fs-12 text-muted user-name-sub-text">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </span>
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <!-- item-->
                        <h6 class="dropdown-header">Welcome <?php echo htmlspecialchars($user['name']); ?>!</h6>
                        <a class="dropdown-item" href="<?php echo url('profile'); ?>">
                            <i class="bx bx-user fs-16 align-middle me-1"></i>
                            <span class="align-middle">Profile</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="<?php echo url('logout'); ?>">
                            <i class="bx bx-power-off fs-16 align-middle me-1"></i>
                            <span class="align-middle">Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header> 