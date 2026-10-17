<?php
// teacher_login.php
session_start();

if (isset($_SESSION['teacher_id']) && ($_SESSION['user_role'] ?? '') === 'teacher') {
    header("Location: teacher_dashboard.php");
    exit;
}
if (isset($_SESSION['admin_id']) && ($_SESSION['user_role'] ?? '') === 'admin') {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Teacher Portal Login - AI Face Biometric Attendance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <style>
        .portal-badge {
            background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
            color: #fff;
        }
        .portal-card {
            border-radius: 22px;
            box-shadow: 0 15px 35px rgba(15, 23, 42, 0.08);
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4 py-sm-5">
        <div class="row justify-content-center align-items-center min-vh-100 py-3">
            <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">
                <div class="card border-0 rounded-4 p-3 p-sm-4 p-md-4 bg-white portal-card">
                    <div class="text-center mb-4 pt-2">
                        <div class="d-inline-flex align-items-center justify-content-center portal-badge rounded-circle mb-3 shadow-sm" style="width: 58px; height: 58px;">
                            <i class="bi bi-mortarboard-fill fs-2"></i>
                        </div>
                        <h4 class="fw-bold mb-1 text-dark">Teacher Portal</h4>
                        <p class="text-muted small mb-0">Biometric Attendance & Self-Tracking</p>
                    </div>

                    <div id="loginAlert" class="alert alert-danger d-none py-2 small" role="alert"></div>

                    <form id="teacherLoginForm">
                        <input type="hidden" name="action" value="login">
                        
                        <div class="mb-3">
                            <label for="identifier" class="form-label text-muted small">Email or Teacher ID</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control border-start-0" id="identifier" name="identifier" required placeholder="e.g. teacher@college.edu or T001" autocomplete="username">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label text-muted small">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control border-start-0" id="password" name="password" required placeholder="Enter your password" autocomplete="current-password">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 rounded-3 fw-bold mb-3 shadow-xs" style="min-height: 44px;">
                            <i class="bi bi-box-arrow-in-right me-2"></i> TEACHER LOGIN
                        </button>
                    </form>
                    
                    <div class="text-center bg-light rounded-3 p-3 mb-3 border border-light">
                        <span class="text-muted small d-block mb-1">New faculty member without an account?</span>
                        <a href="teacher_signup.php" class="btn btn-sm btn-outline-primary fw-semibold rounded-pill px-3 py-1">
                            <i class="bi bi-person-plus me-1"></i> Register as Teacher
                        </a>
                    </div>

                    <hr class="text-muted opacity-25 my-3">
                    
                    <div class="d-flex justify-content-between align-items-center mt-2 px-1">
                        <a href="index.php" class="text-decoration-none small text-muted">
                            <i class="bi bi-shield-lock me-1"></i> Admin Portal
                        </a>
                        <a href="attendance-kiosk.php" class="text-decoration-none small text-muted">
                            <i class="bi bi-camera-video me-1"></i> Public Kiosk
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('teacherLoginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const alertBox = document.getElementById('loginAlert');
            const submitBtn = e.target.querySelector('button[type="submit"]');
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Verifying...';
            alertBox.classList.add('d-none');
            
            try {
                const response = await fetch('api/teacher_auth.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    window.location.href = result.redirect || 'teacher_dashboard.php';
                } else {
                    alertBox.textContent = result.message || 'Login failed.';
                    alertBox.classList.remove('d-none');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-box-arrow-in-right me-2"></i> TEACHER LOGIN';
                }
            } catch (error) {
                alertBox.textContent = 'Network or server error during login. Please try again.';
                alertBox.classList.remove('d-none');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-box-arrow-in-right me-2"></i> TEACHER LOGIN';
            }
        });
    </script>
</body>
</html>
