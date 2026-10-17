<?php
// api/get_teacher_records.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

header('Content-Type: application/json');

// Ensure only authenticated teachers can access
if (!isset($_SESSION['teacher_id']) || ($_SESSION['user_role'] ?? '') !== 'teacher') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$teacher_db_id = (int)$_SESSION['teacher_id'];
$dateFrom = $_GET['dateFrom'] ?? '';
$dateTo = $_GET['dateTo'] ?? '';
$today = date('Y-m-d');

try {
    // 1. Fetch teacher profile
    $stmt = $pdo->prepare("SELECT id, teacher_id, name, department, email, phone, (face_descriptor IS NOT NULL AND face_descriptor != '') as has_face, created_at FROM teachers WHERE id = ?");
    $stmt->execute([$teacher_db_id]);
    $teacher = $stmt->fetch();

    if (!$teacher) {
        echo json_encode(['success' => false, 'message' => 'Teacher account not found.']);
        exit;
    }

    // 2. Today's attendance status
    $stmt = $pdo->prepare("SELECT id, attendance_time, status, location_verified, created_at FROM attendance WHERE teacher_id = ? AND attendance_date = ?");
    $stmt->execute([$teacher_db_id, $today]);
    $today_record = $stmt->fetch();

    $today_status = 'Not Marked';
    $today_time = null;
    $today_verified = false;

    if ($today_record) {
        $today_status = $today_record['status'];
        // Format time (e.g. 09:30 AM)
        $today_time = date('h:i A', strtotime($today_record['attendance_time']));
        $today_verified = (bool)$today_record['location_verified'];
    }

    // 3. Overall attendance metrics
    // Total present days for this teacher
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_present FROM attendance WHERE teacher_id = ? AND status = 'Present'");
    $stmt->execute([$teacher_db_id]);
    $total_present = (int)$stmt->fetch()['total_present'];

    // Total active academic attendance days recorded in system
    $stmt = $pdo->query("SELECT COUNT(DISTINCT attendance_date) as total_working_days FROM attendance");
    $total_system_days = (int)$stmt->fetch()['total_working_days'];
    if ($total_system_days < 1) {
        $total_system_days = max(1, $total_present);
    }
    if ($total_present > $total_system_days) {
        $total_system_days = $total_present;
    }

    $total_absent = max(0, $total_system_days - $total_present);
    $attendance_rate = $total_system_days > 0 ? round(($total_present / $total_system_days) * 100) : 0;

    // 4. Filterable attendance history (strictly for this teacher)
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

    $historyStmt = $pdo->prepare("
        SELECT id, attendance_date, attendance_time, status, location_verified, created_at 
        FROM attendance 
        $whereSql 
        ORDER BY attendance_date DESC, attendance_time DESC
    ");
    $historyStmt->execute($params);
    $records = $historyStmt->fetchAll();

    // Format records for response
    $formatted_records = [];
    foreach ($records as $r) {
        $formatted_records[] = [
            'id' => (int)$r['id'],
            'date' => $r['attendance_date'],
            'date_formatted' => date('D, d M Y', strtotime($r['attendance_date'])),
            'time' => $r['attendance_time'],
            'time_formatted' => date('h:i A', strtotime($r['attendance_time'])),
            'status' => $r['status'],
            'location_verified' => (bool)$r['location_verified']
        ];
    }

    echo json_encode([
        'success' => true,
        'teacher' => [
            'id' => $teacher['id'],
            'teacher_id' => $teacher['teacher_id'],
            'name' => $teacher['name'],
            'department' => $teacher['department'],
            'email' => $teacher['email'],
            'phone' => $teacher['phone'],
            'has_face' => (bool)$teacher['has_face'],
            'member_since' => date('d M Y', strtotime($teacher['created_at']))
        ],
        'today' => [
            'date' => date('d M Y'),
            'status' => $today_status,
            'time' => $today_time,
            'location_verified' => $today_verified
        ],
        'summary' => [
            'total_present' => $total_present,
            'total_absent' => $total_absent,
            'total_working_days' => $total_system_days,
            'attendance_rate' => $attendance_rate
        ],
        'records' => $formatted_records
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
