<?php
// api/index.php - Vercel Serverless Function Router
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = trim($uri, '/');

// Friendly route mappings
$routes = [
    '' => 'index.php',
    'admin' => 'dashboard.php',
    'admin/login' => 'index.php',
    'admin/dashboard' => 'dashboard.php',
    'teacher' => 'teacher_dashboard.php',
    'teacher/login' => 'teacher_login.php',
    'teacher/signup' => 'teacher_signup.php',
    'teacher/dashboard' => 'teacher_dashboard.php',
    'teacher/attendance' => 'teacher_attendance.php'
];

if (isset($routes[$file])) {
    chdir(__DIR__ . '/..');
    require __DIR__ . '/../' . $routes[$file];
    exit;
}

$target = __DIR__ . '/../' . $file;
if (!empty($file) && file_exists($target) && is_file($target)) {
    chdir(dirname($target));
    require $target;
    exit;
}

chdir(__DIR__ . '/..');
require __DIR__ . '/../index.php';
exit;
