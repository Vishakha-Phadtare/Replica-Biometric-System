<?php
session_start();
if (isset($_SESSION['admin_id']) && ($_SESSION['user_role'] ?? '') === 'admin') {
    header("Location: dashboard.php");
    exit;
}
if (isset($_SESSION['teacher_id']) && ($_SESSION['user_role'] ?? '') === 'teacher') {
    header("Location: teacher_dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>AI Face Biometric Attendance - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
</head>
<body class="bg-light">
    <div class="container py-4 py-sm-5">
        <div class="row justify-content-center align-items-center min-vh-100 py-3">
            <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">
                <div class="card shadow-sm border-0 rounded-4 p-3 p-sm-4 p-md-4 bg-white">
                    <div class="text-center mb-4 pt-2">
                        <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle mb-3 shadow-xs" style="width: 58px; height: 58px;">
                            <i class="bi bi-person-bounding-box fs-2"></i>
                        </div>
                        <h4 class="fw-bold mb-1 text-dark">AI Face Biometric</h4>
                        <p class="text-muted small mb-0">Smart Face Recognition Attendance Portal</p>
                    </div>

                    <div id="loginAlert" class="alert alert-danger d-none py-2 small" role="alert"></div>

                    <form id="loginForm">
                        <div class="mb-3">
                            <label for="username" class="form-label text-muted small">Username</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control border-start-0" id="username" name="username" required placeholder="Enter admin username" autocomplete="username">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label text-muted small">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control border-start-0" id="password" name="password" required placeholder="Enter password" autocomplete="current-password">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 rounded-3 fw-bold mb-3 shadow-xs" style="min-height: 44px;">
                            <i class="bi bi-box-arrow-in-right me-2"></i> LOGIN
                        </button>
                    </form>
                    
                    <hr class="text-muted opacity-25 my-3">
                    
                    <div class="text-center mt-2 pb-1">
                        <p class="text-muted small mb-2">Are you a teacher?</p>
                        <div class="d-flex flex-column flex-sm-row justify-content-center gap-2">
                            <a href="teacher_login.php" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-2 fw-medium">
                                <i class="bi bi-person-badge me-1"></i> Teacher Portal Login
                            </a>
                            <a href="attendance-kiosk.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-2 fw-medium">
                                <i class="bi bi-camera-video me-1"></i> Open Kiosk
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const alertBox = document.getElementById('loginAlert');
            const submitBtn = e.target.querySelector('button[type="submit"]');
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Authenticating...';
            
            try {
                const response = await fetch('api/login.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    window.location.href = 'dashboard.php';
                } else {
                    alertBox.textContent = result.message;
                    alertBox.classList.remove('d-none');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-box-arrow-in-right me-2"></i> LOGIN';
                }
            } catch (error) {
                alertBox.textContent = 'An error occurred during login. Please try again.';
                alertBox.classList.remove('d-none');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-box-arrow-in-right me-2"></i> LOGIN';
            }
        });
    </script>
</body>
</html>
