<?php
// includes/sidebar.php
?>
<aside class="offcanvas-lg offcanvas-start sidebar col-lg-2 col-xl-2 border-end bg-white" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel">
    <!-- Header for Mobile Offcanvas Drawer -->
    <div class="offcanvas-header d-lg-none border-bottom px-3 py-3">
        <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 36px; height: 36px;">
                <i class="bi bi-shield-check fs-5"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-0 text-dark" id="sidebarOffcanvasLabel">GCOER Attendance</h6>
                <small class="text-muted" style="font-size: 0.72rem;">Teacher Portal</small>
            </div>
        </div>
        <button type="button" class="btn-close text-reset shadow-none" data-bs-dismiss="offcanvas" data-bs-target="#sidebarOffcanvas" aria-label="Close"></button>
    </div>
    
    <!-- Sidebar Content Body -->
    <div class="offcanvas-body p-0 d-flex flex-column h-100">
        <!-- Brand Logo Area (Visible on Laptop / Desktop >= 992px) -->
        <div class="d-none d-lg-block text-center py-4 px-3 border-bottom border-light">
            <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-circle mb-2 shadow-sm" style="width: 48px; height: 48px;">
                <i class="bi bi-shield-check fs-4"></i>
            </div>
            <h6 class="fw-bold mb-0 text-dark">GCOER Attendance</h6>
            <small class="text-muted" style="font-size: 0.75rem;">Teacher Portal</small>
        </div>

        <!-- Navigation Links -->
        <div class="sidebar-nav-container flex-grow-1 overflow-y-auto py-3">
            <ul class="nav flex-column px-2 mb-0">
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                        <i class="bi bi-grid-fill me-3"></i> <span>Dashboard</span>
                    </a>
                </li>
                
                <li class="nav-item mt-3 mb-1 px-3">
                    <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.68rem; letter-spacing: 0.6px;">Management</span>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'teachers.php' ? 'active' : '' ?>" href="teachers.php">
                        <i class="bi bi-people-fill me-3"></i> <span>Teachers Directory</span>
                    </a>
                </li>
                
                <li class="nav-item mt-3 mb-1 px-3">
                    <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.68rem; letter-spacing: 0.6px;">Records & Analytics</span>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'attendance_records.php' ? 'active' : '' ?>" href="attendance_records.php">
                        <i class="bi bi-journal-check me-3"></i> <span>Daily Attendance</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'reports.php' ? 'active' : '' ?>" href="reports.php">
                        <i class="bi bi-bar-chart-line-fill me-3"></i> <span>Analytics & Reports</span>
                    </a>
                </li>
                
                <li class="nav-item mt-3 mb-1 px-3">
                    <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.68rem; letter-spacing: 0.6px;">Biometric Modes</span>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-primary bg-primary bg-opacity-10 rounded-3 mt-1" href="attendance-kiosk.php" target="_blank">
                        <i class="bi bi-camera-video-fill me-3 text-primary"></i> <span class="fw-semibold">Launch Kiosk</span>
                    </a>
                </li>
                <li class="nav-item mt-1">
                    <a class="nav-link <?= $current_page == 'fingerprint_simulation.php' ? 'active' : '' ?>" href="fingerprint_simulation.php">
                        <i class="bi bi-fingerprint me-3"></i> <span>Fingerprint Demo</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Sidebar Footer / User Profile & Logout -->
        <div class="sidebar-footer p-3 border-top mt-auto bg-light bg-opacity-50">
            <div class="d-flex align-items-center mb-3">
                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center me-3 border border-primary border-opacity-25" style="width: 38px; height: 38px;">
                    <i class="bi bi-person-badge fs-5"></i>
                </div>
                <div class="overflow-hidden">
                    <div class="fw-semibold text-dark text-truncate" style="font-size: 0.85rem;"><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Administrator') ?></div>
                    <div class="text-muted text-truncate" style="font-size: 0.72rem;">admin@college.edu</div>
                </div>
            </div>
            <a href="logout.php" class="btn btn-outline-danger btn-sm w-100 d-flex align-items-center justify-content-center py-2 fw-medium">
                <i class="bi bi-box-arrow-left me-2"></i> Sign Out
            </a>
        </div>
    </div>
</aside>
