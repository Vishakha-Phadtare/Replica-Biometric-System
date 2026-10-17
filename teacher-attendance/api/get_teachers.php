<?php
// api/get_teachers.php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    if (isset($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT id, teacher_id, name, department, email, phone, created_at FROM teachers WHERE teacher_id = ?");
        $stmt->execute([$_GET['id']]);
        $teachers = $stmt->fetchAll();
    } else {
        $stmt = $pdo->query("SELECT id, teacher_id, name, department, email, phone, created_at FROM teachers ORDER BY created_at DESC");
        $teachers = $stmt->fetchAll();
    }
    
    echo json_encode(['success' => true, 'teachers' => $teachers]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
