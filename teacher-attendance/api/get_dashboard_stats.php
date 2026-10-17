<?php
// api/get_dashboard_stats.php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $today = date('Y-m-d');
    
    // Total Teachers
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM teachers");
    $total_teachers = $stmt->fetch()['total'];

    // Present Today
    $stmt = $pdo->prepare("SELECT COUNT(*) as present FROM attendance WHERE attendance_date = ? AND status = 'Present'");
    $stmt->execute([$today]);
    $present_today = $stmt->fetch()['present'];

    // Absent Today
    $absent_today = $total_teachers - $present_today;
    if ($absent_today < 0) $absent_today = 0;

    // Attendance Percentage
    $attendance_percent = 0;
    if ($total_teachers > 0) {
        $attendance_percent = round(($present_today / $total_teachers) * 100);
    }

    // Weekly Data (last 7 days)
    $weekly_data = [];
    $weekly_labels = [];
    
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $day_name = date('D', strtotime($date)); // Short day name (Mon, Tue)
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM attendance WHERE attendance_date = ? AND status = 'Present'");
        $stmt->execute([$date]);
        $count = $stmt->fetch()['count'];
        
        $weekly_labels[] = $day_name;
        $weekly_data[] = $count;
    }

    // Recent Attendance
    $stmt = $pdo->query("
        SELECT t.name, t.department, a.attendance_time, a.status 
        FROM attendance a
        JOIN teachers t ON a.teacher_id = t.id
        ORDER BY a.created_at DESC
        LIMIT 5
    ");
    $recent = $stmt->fetchAll();

    // Department Summary
    $stmt = $pdo->prepare("
        SELECT t.department, 
               COUNT(t.id) as total_dept_teachers, 
               (SELECT COUNT(a.id) FROM attendance a JOIN teachers t2 ON a.teacher_id = t2.id WHERE a.attendance_date = ? AND a.status = 'Present' AND t2.department = t.department) as present_dept_teachers
        FROM teachers t
        GROUP BY t.department
        ORDER BY present_dept_teachers DESC
    ");
    $stmt->execute([$today]);
    $dept_data_raw = $stmt->fetchAll();
    
    $departments = [];
    foreach ($dept_data_raw as $d) {
        $pct = 0;
        if ($d['total_dept_teachers'] > 0) {
            $pct = round(($d['present_dept_teachers'] / $d['total_dept_teachers']) * 100);
        }
        $departments[] = [
            'name' => $d['department'],
            'percent' => $pct
        ];
    }

    echo json_encode([
        'success' => true,
        'stats' => [
            'total_teachers' => $total_teachers,
            'present_today' => $present_today,
            'absent_today' => $absent_today,
            'attendance_percent' => $attendance_percent
        ],
        'weekly' => [
            'labels' => $weekly_labels,
            'data' => $weekly_data
        ],
        'recent' => $recent,
        'departments' => $departments
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
