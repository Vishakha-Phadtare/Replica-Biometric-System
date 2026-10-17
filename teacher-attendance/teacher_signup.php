<?php
// teacher_signup.php
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
    <title>Teacher Registration / Signup - AI Face Biometric Attendance</title>
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
            <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
                <div class="card border-0 rounded-4 p-3 p-sm-4 p-md-4 bg-white portal-card">
                    <div class="text-center mb-4 pt-1">
                        <div class="d-inline-flex align-items-center justify-content-center portal-badge rounded-circle mb-3 shadow-sm" style="width: 58px; height: 58px;">
                            <i class="bi bi-person-plus-fill fs-2"></i>
                        </div>
                        <h4 class="fw-bold mb-1 text-dark">Teacher Registration</h4>
                        <p class="text-muted small mb-0">Create your faculty account for biometric attendance</p>
                    </div>

                    <div id="signupAlert" class="alert d-none py-2 small" role="alert"></div>

                    <form id="teacherSignupForm">
                        <input type="hidden" name="action" value="signup">

                        <div class="row g-2 mb-3">
                            <div class="col-12 col-md-7">
                                <label for="name" class="form-label text-muted small mb-1">Full Name <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control border-start-0" id="name" name="name" required placeholder="Prof. Jane Doe">
                                </div>
                            </div>
                            <div class="col-12 col-md-5">
                                <label for="teacher_id" class="form-label text-muted small mb-1">Teacher ID <small class="text-muted">(Optional)</small></label>
                                <input type="text" class="form-control text-uppercase" id="teacher_id" name="teacher_id" placeholder="e.g. T003">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="department" class="form-label text-muted small mb-1">Academic Department <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-building"></i></span>
                                <select class="form-select border-start-0" id="department" name="department" required>
                                    <option value="">Select Department</option>
                                    <option value="Computer Engineering">Computer Engineering</option>
                                    <option value="Information Technology">Information Technology</option>
                                    <option value="AI & DS">AI & DS</option>
                                    <option value="Electronics">Electronics</option>
                                    <option value="Mechanical">Mechanical</option>
                                    <option value="Civil">Civil</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-12 col-md-7">
                                <label for="email" class="form-label text-muted small mb-1">Institutional Email <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control border-start-0" id="email" name="email" required placeholder="name@college.edu">
                                </div>
                            </div>
                            <div class="col-12 col-md-5">
                                <label for="phone" class="form-label text-muted small mb-1">Phone Number</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-telephone"></i></span>
                                    <input type="tel" class="form-control border-start-0" id="phone" name="phone" placeholder="Contact number">
                                </div>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-12 col-md-6">
                                <label for="password" class="form-label text-muted small mb-1">Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control border-start-0" id="password" name="password" required minlength="6" placeholder="Min. 6 chars">
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="confirm_password" class="form-label text-muted small mb-1">Confirm Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-shield-check"></i></span>
                                    <input type="password" class="form-control border-start-0" id="confirm_password" name="confirm_password" required minlength="6" placeholder="Repeat password">
                                </div>
                            </div>
                        </div>

                        <div class="form-text small text-muted mb-3">
                            <i class="bi bi-info-circle me-1"></i> Biometric face enrollment can be completed directly in your dashboard after registration.
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 rounded-3 fw-bold mb-3 shadow-xs" style="min-height: 44px;">
                            <i class="bi bi-person-check me-2"></i> CREATE TEACHER ACCOUNT
                        </button>
                    </form>

                    <div class="text-center pt-2 border-top">
                        <span class="text-muted small">Already have a teacher account?</span>
                        <a href="teacher_login.php" class="fw-semibold text-primary text-decoration-none ms-1">
                            Login here
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('teacherSignupForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const alertBox = document.getElementById('signupAlert');
            const submitBtn = form.querySelector('button[type="submit"]');

            const pwd = form.password.value;
            const confirmPwd = form.confirm_password.value;

            if (pwd !== confirmPwd) {
                alertBox.textContent = 'Passwords do not match. Please verify.';
                alertBox.className = 'alert alert-danger py-2 small';
                alertBox.classList.remove('d-none');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Creating Account...';
            alertBox.classList.add('d-none');

            try {
                const formData = new FormData(form);
                const response = await fetch('api/teacher_auth.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    alertBox.textContent = result.message || 'Registration successful! Redirecting...';
                    alertBox.className = 'alert alert-success py-2 small';
                    alertBox.classList.remove('d-none');
                    setTimeout(() => {
                        window.location.href = result.redirect || 'teacher_dashboard.php';
                    }, 1200);
                } else {
                    alertBox.textContent = result.message || 'Registration failed.';
                    alertBox.className = 'alert alert-danger py-2 small';
                    alertBox.classList.remove('d-none');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-person-check me-2"></i> CREATE TEACHER ACCOUNT';
                }
            } catch (error) {
                alertBox.textContent = 'A connection error occurred. Please try again.';
                alertBox.className = 'alert alert-danger py-2 small';
                alertBox.classList.remove('d-none');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-person-check me-2"></i> CREATE TEACHER ACCOUNT';
            }
        });
    </script>
</body>
</html>
