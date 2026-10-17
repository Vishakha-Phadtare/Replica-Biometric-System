<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>
<?php require_once 'api/db.php'; ?>

<main class="col-12 col-lg-10 ms-auto main-content px-3 px-sm-4 px-xl-5 py-3 py-md-4">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 pt-1 pb-3 mb-4 border-bottom border-secondary border-opacity-10">
        <div>
            <h1 class="h4 fw-bold text-dark mb-1">Reports & Analytics</h1>
            <p class="text-muted mb-0 small">High-level attendance metrics and performance over time.</p>
        </div>
    </div>

    <?php
    // Basic PHP reporting logic without AJAX for simplicity
    $period = $_GET['period'] ?? 'today';
    
    $where = "1=1";
    if ($period == 'today') {
        $where = "attendance_date = CURDATE()";
    } elseif ($period == 'week') {
        $where = "YEARWEEK(attendance_date, 1) = YEARWEEK(CURDATE(), 1)";
    } elseif ($period == 'month') {
        $where = "MONTH(attendance_date) = MONTH(CURDATE()) AND YEAR(attendance_date) = YEAR(CURDATE())";
    }

    // Get Total Teachers
    $stmt = $pdo->query("SELECT COUNT(*) as t FROM teachers");
    $total = $stmt->fetch()['t'];

    // Get Present
    $stmt = $pdo->query("SELECT COUNT(DISTINCT teacher_id, attendance_date) as p FROM attendance WHERE $where AND status = 'Present'");
    $present = $stmt->fetch()['p'];

    // For period > 1 day, "absent" requires complex logic. 
    // We simplify: if it's "today", absent is total - present.
    // Otherwise, we'll just show average daily attendance.
    if ($period == 'today') {
        $absent = $total - $present;
        $attendance_percent = $total > 0 ? round(($present / $total) * 100) : 0;
        $absent_label = "Absent Today";
        $percent_label = "Attendance % (Today)";
    } else {
        // Find distinct days in period
        $stmt = $pdo->query("SELECT COUNT(DISTINCT attendance_date) as days FROM attendance WHERE $where");
        $days = $stmt->fetch()['days'];
        
        $avg_present_per_day = $days > 0 ? round($present / $days) : 0;
        $absent = $total - $avg_present_per_day;
        $attendance_percent = $total > 0 ? round(($avg_present_per_day / $total) * 100) : 0;
        
        $absent_label = "Avg Absent/Day";
        $percent_label = "Avg Attendance %";
    }
    
    if ($absent < 0) $absent = 0;
    ?>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-2 p-sm-3">
            <ul class="nav nav-pills d-flex flex-wrap gap-2 mb-0">
                <li class="nav-item flex-fill flex-sm-grow-0">
                    <a class="nav-link text-center rounded-3 py-2 px-3 fw-medium <?= $period == 'today' ? 'active' : 'bg-light text-dark' ?>" href="?period=today">Today</a>
                </li>
                <li class="nav-item flex-fill flex-sm-grow-0">
                    <a class="nav-link text-center rounded-3 py-2 px-3 fw-medium <?= $period == 'week' ? 'active' : 'bg-light text-dark' ?>" href="?period=week">This Week</a>
                </li>
                <li class="nav-item flex-fill flex-sm-grow-0">
                    <a class="nav-link text-center rounded-3 py-2 px-3 fw-medium <?= $period == 'month' ? 'active' : 'bg-light text-dark' ?>" href="?period=month">This Month</a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Stats -->
    <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-4">
        <div class="col">
            <div class="card border-0 bg-white shadow-sm rounded-4 h-100 p-2">
                <div class="card-body text-center p-3">
                    <h6 class="text-muted text-uppercase fw-semibold mb-2" style="font-size: 0.75rem;">Total Teachers</h6>
                    <h2 class="h1 fw-bold text-dark mb-0"><?= $total ?></h2>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card border-0 bg-white shadow-sm rounded-4 h-100 p-2 border-start border-success border-4">
                <div class="card-body text-center p-3">
                    <h6 class="text-muted text-uppercase fw-semibold mb-2" style="font-size: 0.75rem;">Present (<?= ucfirst($period) ?>)</h6>
                    <h2 class="h1 fw-bold text-success mb-0"><?= $present ?></h2>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card border-0 bg-white shadow-sm rounded-4 h-100 p-2 border-start border-danger border-4">
                <div class="card-body text-center p-3">
                    <h6 class="text-muted text-uppercase fw-semibold mb-2" style="font-size: 0.75rem;"><?= $absent_label ?></h6>
                    <h2 class="h1 fw-bold text-danger mb-0"><?= $absent ?></h2>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card border-0 bg-white shadow-sm rounded-4 h-100 p-2 border-start border-info border-4">
                <div class="card-body text-center p-3">
                    <h6 class="text-muted text-uppercase fw-semibold mb-2" style="font-size: 0.75rem;"><?= $percent_label ?></h6>
                    <h2 class="h1 fw-bold text-info mb-0"><?= $attendance_percent ?>%</h2>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>
