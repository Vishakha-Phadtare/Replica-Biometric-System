<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    if (isset($_SESSION['teacher_id']) && ($_SESSION['user_role'] ?? '') === 'teacher') {
        header("Location: teacher_dashboard.php");
        exit;
    }
    header("Location: index.php");
    exit;
}
$current_page = basename($_SERVER['PHP_SELF']);

// Page title map
$page_titles = [
    'dashboard.php' => 'Dashboard - GCOER Attendance',
    'teachers.php' => 'Teachers Directory - GCOER Attendance',
    'attendance_records.php' => 'Daily Attendance - GCOER Attendance',
    'reports.php' => 'Analytics & Reports - GCOER Attendance',
    'fingerprint_simulation.php' => 'Fingerprint Simulation - GCOER Attendance'
];
$page_title = $page_titles[$current_page] ?? 'GCOER Attendance Portal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Face-API.js -->
    <script src="js/face-api.min.js"></script>
</head>
<body>
    <!-- Top App Bar for Mobile & Tablet (< 992px) -->
    <header class="app-header navbar sticky-top d-lg-none px-3 py-2">
        <div class="d-flex align-items-center justify-content-between w-100">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-link text-white p-1 text-decoration-none border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas" aria-label="Toggle navigation">
                    <i class="bi bi-list fs-2 lh-1"></i>
                </button>
                <a class="navbar-brand text-white fw-bold d-flex align-items-center gap-2 mb-0 py-0 fs-6" href="dashboard.php">
                    <i class="bi bi-shield-check fs-5"></i>
                    <span>GCOER Attendance</span>
                </a>
            </div>
            
            <div class="d-flex align-items-center gap-2">
                <a href="attendance-kiosk.php" target="_blank" class="btn btn-sm btn-light text-primary fw-semibold rounded-pill px-2 py-1 d-flex align-items-center gap-1 shadow-sm" title="Launch Kiosk">
                    <i class="bi bi-camera-video-fill"></i>
                    <span class="d-none d-sm-inline small">Kiosk</span>
                </a>
                <a href="logout.php" class="btn btn-sm btn-outline-light rounded-pill px-2 py-1" title="Sign Out">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </div>
        </div>
    </header>

    <div class="container-fluid p-0">
        <div class="row g-0 app-wrapper">
