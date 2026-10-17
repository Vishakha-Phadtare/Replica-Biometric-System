<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>
<?php require_once 'api/db.php'; ?>

<?php
$today = date('Y-m-d');

// Total Teachers
$stmt = $pdo->query("SELECT COUNT(*) as total FROM teachers");
$total_teachers = $stmt->fetch()['total'];

// Present Today
$stmt = $pdo->prepare("SELECT COUNT(*) as present FROM attendance WHERE attendance_date = ? AND status = 'Present'");
$stmt->execute([$today]);
$present_today = $stmt->fetch()['present'];

// Absent Today
$absent_today = max(0, $total_teachers - $present_today);

// Attendance Percentage
$attendance_percent = $total_teachers > 0 ? round(($present_today / $total_teachers) * 100) : 0;

// Weekly Data (last 7 days)
$weekly_data = [];
$weekly_labels = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $day_name = date('D', strtotime($date));
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM attendance WHERE attendance_date = ? AND status = 'Present'");
    $stmt->execute([$date]);
    $weekly_labels[] = $day_name;
    $weekly_data[] = $stmt->fetch()['count'];
}

// Recent Attendance
$stmt = $pdo->query("SELECT t.name, t.department, a.attendance_time, a.status FROM attendance a JOIN teachers t ON a.teacher_id = t.id ORDER BY a.created_at DESC LIMIT 5");
$recent = $stmt->fetchAll();

// Department Summary
$stmt = $pdo->prepare("SELECT t.department, COUNT(t.id) as total_dept_teachers, (SELECT COUNT(a.id) FROM attendance a JOIN teachers t2 ON a.teacher_id = t2.id WHERE a.attendance_date = ? AND a.status = 'Present' AND t2.department = t.department) as present_dept_teachers FROM teachers t GROUP BY t.department ORDER BY present_dept_teachers DESC");
$stmt->execute([$today]);
$departments = $stmt->fetchAll();
?>

<main class="col-12 col-lg-10 ms-auto main-content px-3 px-sm-4 px-xl-5 py-3 py-md-4">
    <!-- Header -->
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 pt-1 pb-3 mb-4 border-bottom border-secondary border-opacity-10">
        <div>
            <h1 class="h4 fw-bold text-dark mb-1" style="letter-spacing: -0.02em;">Dashboard</h1>
            <p class="text-muted mb-0" style="font-size: 0.85rem;">Welcome back, <?= htmlspecialchars($_SESSION['admin_username']) ?>. Here's today's attendance overview.</p>
        </div>
        <div class="text-sm-end d-flex flex-row flex-sm-column gap-2 align-items-center align-items-sm-end">
            <span class="badge bg-white text-dark border shadow-xs px-3 py-1" id="dash-date"><i class="bi bi-calendar3 me-1 text-primary"></i></span>
            <small class="text-muted fw-medium" id="dash-time" style="font-size: 0.75rem;"></small>
        </div>
    </div>

    <!-- Top Row: Statistics Cards -->
    <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-4">
        <div class="col">
            <div class="card metric-card total-teachers h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h6 class="text-muted fw-medium mb-1" style="font-size: 0.85rem;">Total Teachers</h6>
                            <h3 class="fw-bold text-dark mb-0" id="stat-total-teachers"><?= $total_teachers ?></h3>
                        </div>
                        <div class="metric-icon-box icon-box-primary">
                            <i class="bi bi-people-fill"></i>
                        </div>
                    </div>
                    <small class="text-muted" style="font-size: 0.75rem;">Registered in the system</small>
                </div>
            </div>
        </div>
        
        <div class="col">
            <div class="card metric-card present-today h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h6 class="text-muted fw-medium mb-1" style="font-size: 0.85rem;">Present Today</h6>
                            <h3 class="fw-bold text-dark mb-0" id="stat-present-today"><?= $present_today ?></h3>
                        </div>
                        <div class="metric-icon-box icon-box-success">
                            <i class="bi bi-person-check-fill"></i>
                        </div>
                    </div>
                    <small class="text-success fw-medium" style="font-size: 0.75rem;">Checked in successfully</small>
                </div>
            </div>
        </div>
        
        <div class="col">
            <div class="card metric-card absent-today h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h6 class="text-muted fw-medium mb-1" style="font-size: 0.85rem;">Absent Today</h6>
                            <h3 class="fw-bold text-dark mb-0" id="stat-absent-today"><?= $absent_today ?></h3>
                        </div>
                        <div class="metric-icon-box icon-box-danger">
                            <i class="bi bi-person-x-fill"></i>
                        </div>
                    </div>
                    <small class="text-danger fw-medium" style="font-size: 0.75rem;">Not yet marked</small>
                </div>
            </div>
        </div>
        
        <div class="col">
            <div class="card metric-card attendance-rate h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h6 class="text-muted fw-medium mb-1" style="font-size: 0.85rem;">Attendance Rate</h6>
                            <h3 class="fw-bold text-dark mb-0" id="stat-attendance-percent"><?= $attendance_percent ?>%</h3>
                        </div>
                        <div class="metric-icon-box icon-box-info">
                            <i class="bi bi-pie-chart-fill"></i>
                        </div>
                    </div>
                    <small class="text-info fw-medium" style="font-size: 0.75rem;">Overall for today</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Middle Row: Charts -->
    <div class="row g-3 mb-4">
        <!-- Weekly Chart -->
        <div class="col-xl-8">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3 pb-0 px-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Weekly Attendance</h6>
                        <small class="text-muted" style="font-size: 0.75rem;">Teacher attendance for the current week</small>
                    </div>
                </div>
                <div class="card-body position-relative px-3 px-sm-4 pb-3">
                    <div class="chart-container-fluid position-relative w-100" style="height: 250px;">
                        <canvas id="weeklyChart"></canvas>
                        <div id="chart-empty-state" class="position-absolute top-50 start-50 translate-middle text-center d-none">
                            <i class="bi bi-bar-chart text-muted opacity-25 display-4 mb-2 d-block"></i>
                            <span class="text-muted small">No attendance data available yet.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Summary Progress -->
        <div class="col-xl-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3 pb-0 px-4">
                    <h6 class="fw-bold text-dark mb-0">Today's Attendance</h6>
                </div>
                <div class="card-body d-flex flex-column justify-content-center px-4">
                    
                    <div class="d-flex justify-content-between mb-2 align-items-center">
                        <span class="fw-medium text-muted small">Present</span>
                        <span class="fw-bold text-success fs-5" id="summary-present"><?= $present_today ?></span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-4 align-items-center">
                        <span class="fw-medium text-muted small">Absent</span>
                        <span class="fw-bold text-danger fs-5" id="summary-absent"><?= $absent_today ?></span>
                    </div>

                    <div class="mb-2 d-flex justify-content-between align-items-end">
                        <span class="text-muted fw-semibold small text-uppercase" style="letter-spacing: 0.5px; font-size: 0.7rem;">Attendance Rate</span>
                        <h4 class="fw-bold text-dark mb-0" id="summary-rate"><?= $attendance_percent ?>%</h4>
                    </div>
                    <div class="progress" style="height: 4px; border-radius: 2px; background-color: #f1f5f9;">
                        <div class="progress-bar bg-primary" id="summary-progress" role="progressbar" style="width: <?= $attendance_percent ?>%; border-radius: 2px;"></div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Row: Recent, Dept, Actions -->
    <div class="row g-3 mb-4">
        
        <!-- Recent Attendance -->
        <div class="col-xl-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3 pb-2 px-4 d-flex justify-content-between align-items-center border-bottom">
                    <h6 class="fw-bold text-dark mb-0">Recent Attendance</h6>
                    <a href="attendance_records.php" class="btn btn-sm btn-outline-primary px-3 py-1">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 <?= empty($recent) ? 'd-none' : '' ?>">
                            <thead>
                                <tr>
                                    <th class="ps-4">Teacher</th>
                                    <th>Time</th>
                                    <th class="pe-4 text-end">Status</th>
                                </tr>
                            </thead>
                            <tbody id="recent-attendance-list">
                                <?php foreach ($recent as $row): ?>
                                    <?php
                                    $timeArr = explode(':', $row['attendance_time']);
                                    $hour = (int)$timeArr[0];
                                    $ampm = $hour >= 12 ? 'PM' : 'AM';
                                    $hour = $hour % 12;
                                    $hour = $hour ? $hour : 12;
                                    $timeStr = sprintf("%02d:%s %s", $hour, $timeArr[1], $ampm);
                                    ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-medium text-dark"><?= htmlspecialchars($row['name']) ?></div>
                                            <div class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($row['department']) ?></div>
                                        </td>
                                        <td class="text-muted"><?= htmlspecialchars($timeStr) ?></td>
                                        <td class="pe-4 text-end">
                                            <span class="badge badge-success">
                                                <?= htmlspecialchars($row['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div id="recent-empty-state" class="text-center py-4 <?= !empty($recent) ? 'd-none' : '' ?>">
                        <i class="bi bi-journal-x text-muted opacity-25 display-4 mb-2 d-block"></i>
                        <span class="text-muted small">No recent attendance.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Department Attendance -->
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3 pb-2 px-4 border-bottom">
                    <h6 class="fw-bold text-dark mb-0">Department Attendance</h6>
                </div>
                <div class="card-body px-4 py-3" id="department-list">
                    <?php foreach ($departments as $dept): ?>
                        <?php $pct = $dept['total_dept_teachers'] > 0 ? round(($dept['present_dept_teachers'] / $dept['total_dept_teachers']) * 100) : 0; ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-medium text-dark text-truncate pe-2" style="font-size: 0.85rem;"><?= htmlspecialchars($dept['department']) ?></span>
                                <span class="fw-semibold text-primary" style="font-size: 0.8rem;"><?= $pct ?>%</span>
                            </div>
                            <div class="progress" style="height: 4px; border-radius: 2px; background-color: #f1f5f9;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $pct ?>%; border-radius: 2px;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div id="dept-empty-state" class="text-center py-4 <?= !empty($departments) ? 'd-none' : '' ?>">
                    <i class="bi bi-building text-muted opacity-25 fs-1 mb-2 d-block"></i>
                    <span class="text-muted small">No departments configured.</span>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 border-0 shadow-sm bg-white">
                <div class="card-header bg-white border-0 pt-3 pb-2 px-4 border-bottom">
                    <h6 class="fw-bold text-dark mb-0">Quick Actions</h6>
                </div>
                <div class="card-body p-4">
                    
                    <a href="register_teacher.php" class="quick-action-btn">
                        <i class="bi bi-person-plus"></i> Register Teacher
                    </a>
                    
                    <a href="attendance-kiosk.php" target="_blank" class="quick-action-btn">
                        <i class="bi bi-camera"></i> Monitor Kiosk
                    </a>
                    
                    <a href="attendance_records.php" class="quick-action-btn">
                        <i class="bi bi-list-check"></i> View Attendance
                    </a>
                    
                    <a href="reports.php" class="quick-action-btn mb-0">
                        <i class="bi bi-bar-chart"></i> Generate Report
                    </a>
                </div>
            </div>
        </div>
        
    </div>

    <script src="js/dashboard.js"></script>
    <script>
    (function() {
        // Initialize chart with PHP data
        const chartData = <?= json_encode(['labels' => $weekly_labels, 'data' => $weekly_data]) ?>;
        const hasData = chartData.data.some(val => val > 0);
        const ctx = document.getElementById('weeklyChart');
        const emptyState = document.getElementById('chart-empty-state');
        
        if (!hasData) {
            ctx.style.display = 'none';
            emptyState.classList.remove('d-none');
        } else {
            ctx.style.display = 'block';
            emptyState.classList.add('d-none');
        }

        if (window.weeklyChartInstance) {
            window.weeklyChartInstance.destroy();
        }

        window.weeklyChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'Present Teachers',
                    data: chartData.data,
                    backgroundColor: '#2563eb',
                    hoverBackgroundColor: '#1d4ed8',
                    borderWidth: 0,
                    borderRadius: 6,
                    borderSkipped: 'bottom',
                    barPercentage: 0.5,
                    categoryPercentage: 0.7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#111827',
                        padding: 12,
                        titleFont: { family: 'Inter', size: 13, weight: '500' },
                        bodyFont: { family: 'Inter', size: 13 },
                        cornerRadius: 8,
                        displayColors: false
                    }
                },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        ticks: { stepSize: 1, color: '#9ca3af', font: { family: 'Inter', size: 12 } },
                        border: { display: false, dash: [4, 4] },
                        grid: { color: '#f3f4f6', drawTicks: false, tickLength: 0 }
                    },
                    x: { 
                        ticks: { color: '#6b7280', font: { family: 'Inter', size: 12 } },
                        border: { display: false },
                        grid: { display: false } 
                    }
                },
                layout: { padding: { top: 20 } }
            }
        });
    })();
    </script>
</main>

<?php require_once 'includes/footer.php'; ?>
