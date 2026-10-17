<?php
// api/teacher_face.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

header('Content-Type: application/json');

// Ensure teacher is logged in
if (!isset($_SESSION['teacher_id']) || ($_SESSION['user_role'] ?? '') !== 'teacher') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login as teacher.']);
    exit;
}

$teacher_db_id = (int)$_SESSION['teacher_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->prepare("SELECT id, teacher_id, name, department, email, phone, (face_descriptor IS NOT NULL AND face_descriptor != '') as has_face, face_descriptor FROM teachers WHERE id = ?");
        $stmt->execute([$teacher_db_id]);
        $teacher = $stmt->fetch();

        if (!$teacher) {
            echo json_encode(['success' => false, 'message' => 'Teacher profile not found.']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'teacher_id' => $teacher['teacher_id'],
            'name' => $teacher['name'],
            'department' => $teacher['department'],
            'has_face' => (bool)$teacher['has_face'],
            'face_descriptor' => $teacher['face_descriptor']
        ]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $face_descriptor = $_POST['face_descriptor'] ?? '';

    if (empty($face_descriptor)) {
        echo json_encode(['success' => false, 'message' => 'Face descriptor data is missing.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE teachers SET face_descriptor = ? WHERE id = ?");
        $stmt->execute([$face_descriptor, $teacher_db_id]);

        echo json_encode([
            'success' => true,
            'message' => 'Face biometric data registered successfully!'
        ]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error updating face descriptor.']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}
