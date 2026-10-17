<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>
<?php require_once 'api/db.php'; ?>

<?php
$today = date('Y-m-d');
$sql = "
    SELECT 
        t.teacher_id as teacher_id_str,
        t.name,
        t.department,
        a.attendance_date,
        a.attendance_time,
        a.status
    FROM attendance a
    JOIN teachers t ON a.teacher_id = t.id
    WHERE a.attendance_date >= ? AND a.attendance_date <= ?
    ORDER BY a.attendance_date DESC, a.attendance_time DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$today, $today]);
$records = $stmt->fetchAll();
?>

<main class="col-12 col-lg-10 ms-auto main-content px-3 px-sm-4 px-xl-5 py-3 py-md-4">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 pt-1 pb-3 mb-4 border-bottom border-secondary border-opacity-10">
        <div>
            <h1 class="h4 fw-bold text-dark mb-1">Attendance Records</h1>
            <p class="text-muted mb-0 small">Search, filter, and export detailed attendance logs.</p>
        </div>
        <div class="w-100 w-sm-auto">
            <button class="btn btn-outline-primary bg-white shadow-xs rounded-3 px-3 py-2 fw-medium w-100 w-sm-auto d-flex align-items-center justify-content-center" onclick="exportCSV()">
                <i class="bi bi-download me-2"></i> Export CSV
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-3 p-md-4">
            <form id="filterForm" class="row g-2 g-md-3 align-items-end">
                <div class="col-12 col-sm-6 col-lg-3">
                    <label class="form-label text-muted small mb-1">Search Name/ID</label>
                    <input type="text" class="form-control" id="search" placeholder="e.g. Rahul" oninput="loadAttendance()">
                </div>
                <div class="col-6 col-sm-3 col-lg-2">
                    <label class="form-label text-muted small mb-1">Date From</label>
                    <input type="date" class="form-control" id="dateFrom" value="<?= $today ?>" onchange="loadAttendance()">
                </div>
                <div class="col-6 col-sm-3 col-lg-2">
                    <label class="form-label text-muted small mb-1">Date To</label>
                    <input type="date" class="form-control" id="dateTo" value="<?= $today ?>" onchange="loadAttendance()">
                </div>
                <div class="col-12 col-sm-8 col-lg-3">
                    <label class="form-label text-muted small mb-1">Department</label>
                    <select class="form-select" id="deptFilter" onchange="loadAttendance()">
                        <option value="">All Departments</option>
                        <option value="Computer Engineering">Computer Engineering</option>
                        <option value="Information Technology">Information Technology</option>
                        <option value="AI & DS">AI & DS</option>
                        <option value="Electronics">Electronics</option>
                        <option value="Mechanical">Mechanical</option>
                        <option value="Civil">Civil</option>
                    </select>
                </div>
                <div class="col-12 col-sm-4 col-lg-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="d-md-none px-3 py-2 bg-light border-bottom text-muted" style="font-size: 0.75rem;">
            <i class="bi bi-arrows-expand me-1 text-primary"></i> Scroll horizontally to view complete records
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap">
                    <thead class="table-light text-muted">
                        <tr>
                            <th class="ps-3 ps-sm-4">Teacher ID</th>
                            <th>Teacher Name</th>
                            <th>Department</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th class="pe-3 pe-sm-4 text-end">Status</th>
                        </tr>
                    </thead>
                    <tbody id="attendance-list">
                        <?php if (count($records) > 0): ?>
                            <?php foreach ($records as $record): ?>
                                <?php
                                    $statusBadge = $record['status'] === 'Present' ? 'bg-success' : 'bg-danger';
                                    $dateParts = explode('-', $record['attendance_date']);
                                    $formattedDate = $dateParts[2] . '-' . $dateParts[1] . '-' . $dateParts[0];
                                    
                                    $timeArr = explode(':', $record['attendance_time']);
                                    $hours = (int)$timeArr[0];
                                    $minutes = $timeArr[1];
                                    $ampm = $hours >= 12 ? 'PM' : 'AM';
                                    $hours = $hours % 12;
                                    $hours = $hours ? $hours : 12;
                                    $formattedTime = $hours . ':' . $minutes . ' ' . $ampm;
                                ?>
                                <tr>
                                    <td class="ps-4 fw-semibold text-dark"><?= htmlspecialchars($record['teacher_id_str']) ?></td>
                                    <td><?= htmlspecialchars($record['name']) ?></td>
                                    <td><span class="text-muted small"><?= htmlspecialchars($record['department']) ?></span></td>
                                    <td><?= htmlspecialchars($formattedDate) ?></td>
                                    <td><?= htmlspecialchars($formattedTime) ?></td>
                                    <td class="pe-4 text-end">
                                        <span class="badge <?= $statusBadge ?> rounded-pill px-3"><?= htmlspecialchars($record['status']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">No attendance records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    function initAttendanceRecords() {
        // Attach form submit handler
        document.getElementById('filterForm').addEventListener('submit', (e) => {
            e.preventDefault();
            loadAttendance();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAttendanceRecords);
    } else {
        initAttendanceRecords();
    }

    async function loadAttendance() {
        const search = document.getElementById('search').value;
        const dateFrom = document.getElementById('dateFrom').value;
        const dateTo = document.getElementById('dateTo').value;
        const dept = document.getElementById('deptFilter').value;

        const query = new URLSearchParams({ search, dateFrom, dateTo, dept });

        try {
            const res = await fetch(`api/get_attendance.php?${query.toString()}`);
            const data = await res.json();

            const tbody = document.getElementById('attendance-list');
            tbody.innerHTML = '';

            if (data.success && data.records.length > 0) {
                data.records.forEach(record => {
                    const statusBadge = record.status === 'Present' ? 'bg-success' : 'bg-danger';
                    
                    // Formatting Date
                    const dateParts = record.attendance_date.split('-');
                    const formattedDate = `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`;
                    
                    // Formatting Time
                    const timeArr = record.attendance_time.split(':');
                    let hours = parseInt(timeArr[0]);
                    const minutes = timeArr[1];
                    const ampm = hours >= 12 ? 'PM' : 'AM';
                    hours = hours % 12;
                    hours = hours ? hours : 12; 
                    const formattedTime = hours + ':' + minutes + ' ' + ampm;

                    tbody.innerHTML += `
                        <tr>
                            <td class="ps-4 fw-semibold text-dark">${record.teacher_id_str}</td>
                            <td>${record.name}</td>
                            <td><span class="text-muted small">${record.department}</span></td>
                            <td>${formattedDate}</td>
                            <td>${formattedTime}</td>
                            <td class="pe-4 text-end">
                                <span class="badge ${statusBadge} rounded-pill px-3">${record.status}</span>
                            </td>
                        </tr>
                    `;
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No attendance records found.</td></tr>';
            }
        } catch (e) {
            console.error(e);
        }
    }

    function exportCSV() {
        const search = document.getElementById('search').value;
        const dateFrom = document.getElementById('dateFrom').value;
        const dateTo = document.getElementById('dateTo').value;
        const dept = document.getElementById('deptFilter').value;

        const query = new URLSearchParams({ search, dateFrom, dateTo, dept, export: 'csv' });
        window.location.href = `api/get_attendance.php?${query.toString()}`;
    }
    </script>
</main>

<?php require_once 'includes/footer.php'; ?>
