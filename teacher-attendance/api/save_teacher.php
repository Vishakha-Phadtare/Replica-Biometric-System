<?php
// api/save_teacher.php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacher_id = $_POST['teacher_id'] ?? '';
    $name = $_POST['name'] ?? '';
    $department = $_POST['department'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $face_descriptor = $_POST['face_descriptor'] ?? '';

    // If it's an update for re-registration
    $is_update = isset($_POST['is_update']) && $_POST['is_update'] == '1';

    if (empty($teacher_id) || empty($name) || empty($department) || empty($face_descriptor)) {
        echo json_encode(['success' => false, 'message' => 'Required fields are missing.']);
        exit;
    }

    try {
        if ($is_update) {
            $stmt = $pdo->prepare("UPDATE teachers SET face_descriptor = ? WHERE teacher_id = ?");
            $stmt->execute([$face_descriptor, $teacher_id]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Face registered successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Teacher not found or descriptor unchanged.']);
            }
        } else {
            // Check for duplicate teacher ID
            $stmt = $pdo->prepare("SELECT id FROM teachers WHERE teacher_id = ?");
            $stmt->execute([$teacher_id]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Teacher ID already exists.']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO teachers (teacher_id, name, department, email, phone, face_descriptor) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$teacher_id, $name, $department, $email, $phone, $face_descriptor]);

            echo json_encode(['success' => true, 'message' => 'Teacher registered successfully']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?>
