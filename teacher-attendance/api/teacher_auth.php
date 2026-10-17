<?php
// api/teacher_auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'signup') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $teacher_id = strtoupper(trim($_POST['teacher_id'] ?? ''));
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validation
        if (empty($name) || empty($email) || empty($department) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
            exit;
        }

        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long.']);
            exit;
        }

        if ($password !== $confirm_password) {
            echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
            exit;
        }

        try {
            // Check for duplicate email
            $stmt = $pdo->prepare("SELECT id FROM teachers WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'An account with this email address already exists. Please login.']);
                exit;
            }

            // Determine teacher_id if not provided
            if (empty($teacher_id)) {
                // Auto generate T00X based on highest ID
                $stmt = $pdo->query("SELECT MAX(id) as max_id FROM teachers");
                $maxRow = $stmt->fetch();
                $nextNum = ($maxRow['max_id'] ? $maxRow['max_id'] : 0) + 1;
                $teacher_id = 'T' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
                
                // Ensure generated code is unique
                $chk = $pdo->prepare("SELECT id FROM teachers WHERE teacher_id = ?");
                $chk->execute([$teacher_id]);
                while ($chk->fetch()) {
                    $nextNum++;
                    $teacher_id = 'T' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
                    $chk->execute([$teacher_id]);
                }
            } else {
                // Check if user provided teacher_id already exists
                $stmt = $pdo->prepare("SELECT id FROM teachers WHERE teacher_id = ? LIMIT 1");
                $stmt->execute([$teacher_id]);
                if ($stmt->fetch()) {
                    echo json_encode(['success' => false, 'message' => "Teacher ID '$teacher_id' is already assigned to another teacher."]);
                    exit;
                }
            }

            // Hash password securely
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Insert new teacher account
            $stmt = $pdo->prepare("INSERT INTO teachers (teacher_id, name, department, email, phone, password_hash, face_descriptor) VALUES (?, ?, ?, ?, ?, ?, NULL)");
            $stmt->execute([$teacher_id, $name, $department, $email, $phone, $password_hash]);
            $new_id = $pdo->lastInsertId();

            // Set teacher session
            unset($_SESSION['admin_id'], $_SESSION['admin_username']);
            $_SESSION['teacher_id'] = (int)$new_id;
            $_SESSION['teacher_code'] = $teacher_id;
            $_SESSION['teacher_name'] = $name;
            $_SESSION['teacher_email'] = $email;
            $_SESSION['teacher_department'] = $department;
            $_SESSION['user_role'] = 'teacher';

            echo json_encode([
                'success' => true,
                'message' => 'Registration successful! Redirecting to Teacher Dashboard...',
                'redirect' => 'teacher_dashboard.php'
            ]);
            exit;

        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error during registration: ' . $e->getMessage()]);
            exit;
        }
    } elseif ($action === 'login') {
        $identifier = trim($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($identifier) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Please enter your email or teacher ID and password.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT id, teacher_id, name, department, email, phone, password_hash, face_descriptor FROM teachers WHERE email = ? OR teacher_id = ? LIMIT 1");
            $stmt->execute([$identifier, $identifier]);
            $teacher = $stmt->fetch();

            if ($teacher && !empty($teacher['password_hash']) && password_verify($password, $teacher['password_hash'])) {
                // Clear any admin session
                unset($_SESSION['admin_id'], $_SESSION['admin_username']);

                $_SESSION['teacher_id'] = (int)$teacher['id'];
                $_SESSION['teacher_code'] = $teacher['teacher_id'];
                $_SESSION['teacher_name'] = $teacher['name'];
                $_SESSION['teacher_email'] = $teacher['email'];
                $_SESSION['teacher_department'] = $teacher['department'];
                $_SESSION['user_role'] = 'teacher';

                echo json_encode([
                    'success' => true,
                    'message' => 'Login successful! Redirecting...',
                    'redirect' => 'teacher_dashboard.php'
                ]);
                exit;
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid email/teacher ID or password.']);
                exit;
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error during authentication.']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}
