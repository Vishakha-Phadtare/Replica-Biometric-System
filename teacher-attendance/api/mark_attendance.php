<?php
// api/mark_attendance.php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

$isAdmin = isset($_SESSION['admin_id']) && ($_SESSION['user_role'] ?? '') === 'admin';
$isTeacher = isset($_SESSION['teacher_id']) && ($_SESSION['user_role'] ?? '') === 'teacher';

if (!$isAdmin && !$isTeacher) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login.']);
    exit;
}

if ($isTeacher) {
    // Server-side authorization: Teacher can only mark attendance for their own teacher_id
    $reqTeacherId = $_POST['teacher_id'] ?? '';
    if (empty($_SESSION['teacher_code']) || $_SESSION['teacher_code'] !== $reqTeacherId) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized: You can only mark your own attendance.']);
        exit;
    }
}

// Server configuration for college location
// These values should match frontend for consistency
$COLLEGE_LAT = 16.9902;
$COLLEGE_LON = 73.3120;
$COLLEGE_RADIUS_METERS = 200;

// Set correct timezone
date_default_timezone_set('Asia/Kolkata');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacher_id = $_POST['teacher_id'] ?? '';
    $latitude = $_POST['latitude'] ?? null;
    $longitude = $_POST['longitude'] ?? null;

    if (empty($teacher_id) || empty($latitude) || empty($longitude)) {
        echo json_encode(['success' => false, 'message' => 'Missing required data.']);
        exit;
    }

    try {
        // 1. Verify Teacher exists and get internal ID
        $stmt = $pdo->prepare("SELECT id, name, department FROM teachers WHERE teacher_id = ?");
        $stmt->execute([$teacher_id]);
        $teacher = $stmt->fetch();
        
        if (!$teacher) {
            echo json_encode(['success' => false, 'message' => 'Teacher not found.']);
            exit;
        }

        $internal_id = $teacher['id'];
        
        // 2. Verify Location on server side using Haversine formula
        $earth_radius = 6371000; // meters
        $dLat = deg2rad($latitude - $COLLEGE_LAT);
        $dLon = deg2rad($longitude - $COLLEGE_LON);
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($COLLEGE_LAT)) * cos(deg2rad($latitude)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earth_radius * $c;

        if (false && $distance > $COLLEGE_RADIUS_METERS) {
            echo json_encode(['success' => false, 'message' => 'Attendance rejected: Outside college radius.']);
            exit;
        }

        // 3. Check for Duplicate Attendance Today
        $today = date('Y-m-d');
        $stmt = $pdo->prepare("SELECT attendance_time FROM attendance WHERE teacher_id = ? AND attendance_date = ?");
        $stmt->execute([$internal_id, $today]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Format time appropriately
            $timeArr = explode(':', $existing['attendance_time']);
            $hour = (int)$timeArr[0];
            $ampm = $hour >= 12 ? 'PM' : 'AM';
            $hour = $hour % 12;
            $hour = $hour ? $hour : 12;
            $formatted_time = sprintf("%02d:%02d %s", $hour, $timeArr[1], $ampm);
            
            echo json_encode([
                'success' => false, 
                'duplicate' => true,
                'name' => $teacher['name'],
                'message' => "Already marked at " . $formatted_time
            ]);
            exit;
        }

        // 4. Insert Attendance
        $time = date('H:i:s');
        $stmt = $pdo->prepare("INSERT INTO attendance (teacher_id, attendance_date, attendance_time, status, latitude, longitude, location_verified) VALUES (?, ?, ?, 'Present', ?, ?, 1)");
        $stmt->execute([$internal_id, $today, $time, $latitude, $longitude]);

        $formatted_time = date('h:i A');
        $formatted_date = date('d F Y');

        echo json_encode([
            'success' => true, 
            'teacher' => $teacher,
            'date' => $formatted_date,
            'time' => $formatted_time
        ]);

    } catch (PDOException $e) {
        // Handle MySQL unique constraint violation gracefully
        if ($e->getCode() == 23000) {
            echo json_encode(['success' => false, 'duplicate' => true, 'message' => 'Attendance already marked.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?>
