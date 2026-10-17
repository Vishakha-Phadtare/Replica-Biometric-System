<?php
// router.php for PHP built-in web server
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Clean trailing slash for routing map (except root)
$cleanUri = rtrim($uri, '/');

$routes = [
    '/admin/login' => 'index.php',
    '/admin/dashboard' => 'dashboard.php',
    '/admin' => 'dashboard.php',
    '/teacher/login' => 'teacher_login.php',
    '/teacher/signup' => 'teacher_signup.php',
    '/teacher/dashboard' => 'teacher_dashboard.php',
    '/teacher/attendance' => 'teacher_attendance.php',
    '/teacher' => 'teacher_dashboard.php'
];

if (isset($routes[$cleanUri])) {
    require __DIR__ . '/' . $routes[$cleanUri];
    return true;
}

// If static file or explicit php script exists, let server serve it
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// Default index
require __DIR__ . '/index.php';
return true;
?>
