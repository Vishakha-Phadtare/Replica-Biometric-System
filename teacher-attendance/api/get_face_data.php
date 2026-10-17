<?php
// api/get_face_data.php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id']) && !isset($_SESSION['teacher_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $stmt = $pdo->query("SELECT teacher_id, name, department, face_descriptor FROM teachers");
    $teachers = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'teachers' => $teachers]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
