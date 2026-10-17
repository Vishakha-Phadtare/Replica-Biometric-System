<?php
// logout.php
session_start();
unset($_SESSION['admin_id'], $_SESSION['admin_username']);
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    unset($_SESSION['user_role']);
}
header("Location: index.php");
exit;
?>
