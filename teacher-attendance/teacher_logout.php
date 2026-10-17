<?php
// teacher_logout.php
session_start();
unset(
    $_SESSION['teacher_id'],
    $_SESSION['teacher_code'],
    $_SESSION['teacher_name'],
    $_SESSION['teacher_email'],
    $_SESSION['teacher_department']
);
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'teacher') {
    unset($_SESSION['user_role']);
}
header("Location: teacher_login.php");
exit;
?>
