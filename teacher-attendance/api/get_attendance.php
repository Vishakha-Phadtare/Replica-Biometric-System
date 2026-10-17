<?php
// api/get_attendance.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

if (!isset($_SESSION['admin_id'])) {
    if (isset($_GET['export'])) { die('Unauthorized'); }
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$search = $_GET['search'] ?? '';
$dateFrom = $_GET['dateFrom'] ?? '';
$dateTo = $_GET['dateTo'] ?? '';
$dept = $_GET['dept'] ?? '';
$isExport = isset($_GET['export']) && $_GET['export'] == 'csv';

$params = [];
$whereStr = "1=1";

if (!empty($search)) {
    $whereStr .= " AND (t.name LIKE ? OR t.teacher_id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if (!empty($dateFrom)) {
    $whereStr .= " AND a.attendance_date >= ?";
    $params[] = $dateFrom;
}
if (!empty($dateTo)) {
    $whereStr .= " AND a.attendance_date <= ?";
    $params[] = $dateTo;
}
if (!empty($dept)) {
    $whereStr .= " AND t.department = ?";
    $params[] = $dept;
}

try {
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
        WHERE $whereStr
        ORDER BY a.attendance_date DESC, a.attendance_time DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll();

    if ($isExport) {
        if (ob_get_level()) {
            ob_end_clean();
        }
        error_reporting(0);
        ini_set('display_errors', '0');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="Attendance_Export_' . date('Ymd_His') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        // Add UTF-8 BOM so Excel opens cleanly without encoding issues
        fputs($output, "\xEF\xBB\xBF");
        fputcsv($output, ['Teacher ID', 'Teacher Name', 'Department', 'Date', 'Time', 'Status'], ',', '"', "\\");
        
        foreach ($records as $row) {
            $timeArr = explode(':', $row['attendance_time']);
            $hour = (int)$timeArr[0];
            $ampm = $hour >= 12 ? 'PM' : 'AM';
            $hour = $hour % 12;
            $hour = $hour ? $hour : 12;
            $timeFormatted = sprintf("%02d:%02d %s", $hour, $timeArr[1], $ampm);
            $dateFormatted = '="' . date('d-m-Y', strtotime($row['attendance_date'])) . '"';

            fputcsv($output, [
                '="' . $row['teacher_id_str'] . '"',
                $row['name'],
                $row['department'],
                $dateFormatted,
                $timeFormatted,
                $row['status']
            ], ',', '"', "\\");
        }
        fclose($output);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'records' => $records]);
    }
} catch (PDOException $e) {
    if ($isExport) { die('Database error'); }
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
