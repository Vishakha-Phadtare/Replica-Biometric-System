<?php
// teacher_attendance.php
session_start();

// Strict teacher access control
if (!isset($_SESSION['teacher_id']) || ($_SESSION['user_role'] ?? '') !== 'teacher') {
    if (isset($_SESSION['admin_id']) && ($_SESSION['user_role'] ?? '') === 'admin') {
        header("Location: dashboard.php");
        exit;
    }
    header("Location: teacher_login.php");
    exit;
}

require_once 'api/db.php';

$teacher_db_id = (int)$_SESSION['teacher_id'];

// Handle CSV Export strictly for this teacher
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $dateFrom = $_GET['dateFrom'] ?? '';
    $dateTo = $_GET['dateTo'] ?? '';

    $params = [$teacher_db_id];
    $whereSql = "WHERE teacher_id = ?";
    if (!empty($dateFrom)) {
        $whereSql .= " AND attendance_date >= ?";
        $params[] = $dateFrom;
    }
    if (!empty($dateTo)) {
        $whereSql .= " AND attendance_date <= ?";
        $params[] = $dateTo;
    }

    $stmt = $pdo->prepare("SELECT attendance_date, attendance_time, status, location_verified FROM attendance $whereSql ORDER BY attendance_date DESC, attendance_time DESC");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=my_attendance_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Time', 'Status', 'Biometric Mode', 'Location Verified']);

    foreach ($rows as $r) {
        fputcsv($output, [
            $r['attendance_date'],
            date('h:i A', strtotime($r['attendance_time'])),
            $r['status'],
            'AI Face Biometric',
            $r['location_verified'] ? 'Yes' : 'No'
        ]);
    }
    fclose($output);
    exit;
}

// Fetch teacher profile
$stmt = $pdo->prepare("SELECT id, teacher_id, name, department, email FROM teachers WHERE id = ?");
$stmt->execute([$teacher_db_id]);
$teacher = $stmt->fetch();

if (!$teacher) {
    header("Location: teacher_logout.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>My Attendance History - <?= htmlspecialchars($teacher['name']) ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <style>
        .teacher-navbar {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        }
        .stat-card-teacher {
            border: 0;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
        }
    </style>
</head>
<body class="bg-light">

    <!-- Top Navigation Bar for Teacher -->
    <nav class="navbar navbar-expand-lg teacher-navbar sticky-top navbar-dark py-2 py-md-3">
        <div class="container-fluid px-3 px-sm-4 px-xl-5">
            <a class="navbar-brand d-flex align-items-center gap-2 text-white fw-bold" href="teacher_dashboard.php">
                <div class="rounded-circle bg-primary bg-opacity-25 text-primary-subtle d-flex align-items-center justify-content-center p-2" style="width: 40px; height: 40px;">
                    <i class="bi bi-mortarboard-fill fs-5 text-white"></i>
                </div>
                <div>
                    <span class="d-block lh-1 fs-6">GCOER Faculty Portal</span>
                    <small class="text-white-50" style="font-size: 0.72rem;">Attendance Records</small>
                </div>
            </a>

            <div class="d-flex align-items-center gap-2">
                <a href="teacher_dashboard.php" class="btn btn-sm btn-outline-light rounded-pill px-3 py-1 d-flex align-items-center gap-1">
                    <i class="bi bi-grid-fill"></i>
                    <span class="d-none d-sm-inline">Dashboard</span>
                </a>
                <a href="teacher_logout.php" class="btn btn-sm btn-danger rounded-pill px-3 py-1 d-flex align-items-center gap-1" title="Sign Out">
                    <i class="bi bi-box-arrow-right"></i>
                    <span class="d-none d-sm-inline">Logout</span>
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-3 px-sm-4 px-xl-5 py-4">

        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 pb-3 mb-4 border-bottom">
            <div>
                <h1 class="h4 fw-bold text-dark mb-1">My Attendance History</h1>
                <p class="text-muted small mb-0">Complete historical check-in audit log for <strong><?= htmlspecialchars($teacher['name']) ?></strong> (<?= htmlspecialchars($teacher['teacher_id']) ?>).</p>
            </div>
            <div class="d-flex gap-2">
                <a href="teacher_dashboard.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-2 fw-medium">
                    <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                </a>
                <button id="btnExportCsv" class="btn btn-primary btn-sm rounded-pill px-3 py-2 fw-medium shadow-xs">
                    <i class="bi bi-download me-1"></i> Export My CSV
                </button>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card stat-card-teacher bg-white p-3 p-md-4 mb-4">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-sm-6 col-md-4">
                    <label class="form-label text-muted small mb-1">From Date</label>
                    <input type="date" id="filterFrom" class="form-control">
                </div>
                <div class="col-12 col-sm-6 col-md-4">
                    <label class="form-label text-muted small mb-1">To Date</label>
                    <input type="date" id="filterTo" class="form-control">
                </div>
                <div class="col-12 col-md-4 d-flex gap-2">
                    <button id="btnApplyFilter" class="btn btn-primary w-100 rounded-3 py-2 fw-semibold">
                        <i class="bi bi-funnel me-1"></i> Apply Filter
                    </button>
                    <button id="btnResetFilter" class="btn btn-outline-secondary rounded-3 px-3 py-2" title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Records Table Card -->
        <div class="card stat-card-teacher bg-white overflow-hidden shadow-sm">
            <div class="card-header bg-white border-bottom border-light pt-3 pb-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-table me-2 text-primary"></i>Check-in Log Entries</h6>
                <span class="badge bg-light text-muted border" id="badge-count">Loading...</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-nowrap">
                        <thead class="table-light text-muted small">
                            <tr>
                                <th class="ps-4">Date</th>
                                <th>Day</th>
                                <th>Check-in Time</th>
                                <th>Status</th>
                                <th>Biometric Verification</th>
                                <th>Location</th>
                            </tr>
                        </thead>
                        <tbody id="history-table-body">
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <div class="spinner-border spinner-border-sm text-primary me-2"></div> Loading attendance records...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        async function fetchHistory(from = '', to = '') {
            const tbody = document.getElementById('history-table-body');
            const badgeCount = document.getElementById('badge-count');

            try {
                let url = 'api/get_teacher_records.php';
                const p = new URLSearchParams();
                if (from) p.append('dateFrom', from);
                if (to) p.append('dateTo', to);
                if (p.toString()) url += '?' + p.toString();

                const res = await fetch(url);
                const data = await res.json();

                if (data.success) {
                    badgeCount.textContent = `${data.records.length} records`;

                    if (data.records.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5 text-muted">No attendance records found.</td></tr>';
                        return;
                    }

                    tbody.innerHTML = data.records.map(r => {
                        const d = new Date(r.date);
                        const day = d.toLocaleDateString('en-US', { weekday: 'long' });
                        return `
                            <tr>
                                <td class="ps-4 fw-semibold text-dark">
                                    <i class="bi bi-calendar-event me-2 text-primary"></i>${r.date_formatted}
                                </td>
                                <td class="text-muted">${day}</td>
                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-clock me-1 text-muted"></i>${r.time_formatted}</span>
                                </td>
                                <td>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-1">
                                        <i class="bi bi-check-circle me-1"></i>${r.status}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2 py-1 small">
                                        <i class="bi bi-shield-check me-1"></i>Face Biometrics Verified
                                    </span>
                                </td>
                                <td>
                                    ${r.location_verified ? 
                                        '<span class="text-success small fw-medium"><i class="bi bi-geo-alt-fill me-1"></i>College Campus</span>' : 
                                        '<span class="text-muted small"><i class="bi bi-geo-alt me-1"></i>Logged</span>'}
                                </td>
                            </tr>
                        `;
                    }).join('');
                } else {
                    tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">${data.message || 'Error loading records.'}</td></tr>`;
                }
            } catch (e) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-danger">Network error loading attendance history.</td></tr>';
            }
        }

        document.getElementById('btnApplyFilter').addEventListener('click', () => {
            fetchHistory(document.getElementById('filterFrom').value, document.getElementById('filterTo').value);
        });

        document.getElementById('btnResetFilter').addEventListener('click', () => {
            document.getElementById('filterFrom').value = '';
            document.getElementById('filterTo').value = '';
            fetchHistory();
        });

        document.getElementById('btnExportCsv').addEventListener('click', () => {
            const f = document.getElementById('filterFrom').value;
            const t = document.getElementById('filterTo').value;
            let url = 'teacher_attendance.php?export=csv';
            if (f) url += '&dateFrom=' + encodeURIComponent(f);
            if (t) url += '&dateTo=' + encodeURIComponent(t);
            window.location.href = url;
        });

        document.addEventListener('DOMContentLoaded', () => {
            fetchHistory();
        });
    </script>
</body>
</html>
