<?php
// teacher_dashboard.php
session_start();

// Strict teacher access control
if (!isset($_SESSION['teacher_id']) || ($_SESSION['user_role'] ?? '') !== 'teacher') {
    if (isset($_SESSION['admin_id']) && ($_SESSION['user_role'] ?? '') === 'admin') {
        header("Location: dashboard.php");
        exit;
    }
    header("Location: teacher_login.php");
    exit;
}

require_once 'api/db.php';

$teacher_db_id = (int)$_SESSION['teacher_id'];
$today = date('Y-m-d');

// Fetch teacher info directly from DB
$stmt = $pdo->prepare("SELECT id, teacher_id, name, department, email, phone, (face_descriptor IS NOT NULL AND face_descriptor != '') as has_face FROM teachers WHERE id = ?");
$stmt->execute([$teacher_db_id]);
$teacher = $stmt->fetch();

if (!$teacher) {
    header("Location: teacher_logout.php");
    exit;
}

// Fetch today's attendance
$stmt = $pdo->prepare("SELECT id, attendance_time, status, location_verified FROM attendance WHERE teacher_id = ? AND attendance_date = ?");
$stmt->execute([$teacher_db_id, $today]);
$today_att = $stmt->fetch();

$today_status = $today_att ? $today_att['status'] : 'Not Marked';
$today_time = $today_att ? date('h:i A', strtotime($today_att['attendance_time'])) : null;

// Overall stats
$stmt = $pdo->prepare("SELECT COUNT(*) as total_present FROM attendance WHERE teacher_id = ? AND status = 'Present'");
$stmt->execute([$teacher_db_id]);
$total_present = (int)$stmt->fetch()['total_present'];

$stmt = $pdo->query("SELECT COUNT(DISTINCT attendance_date) as total_days FROM attendance");
$total_days = max(1, (int)$stmt->fetch()['total_days']);
if ($total_present > $total_days) $total_days = $total_present;

$total_absent = max(0, $total_days - $total_present);
$attendance_rate = $total_days > 0 ? round(($total_present / $total_days) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Teacher Dashboard - <?= htmlspecialchars($teacher['name']) ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <!-- Face-API.js -->
    <script src="js/face-api.min.js"></script>
    <style>
        .teacher-navbar {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        }
        .stat-card-teacher {
            border: 0;
            border-radius: 16px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
        }
        .stat-card-teacher:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }
        .camera-scanner-box {
            position: relative;
            width: 100%;
            max-width: 520px;
            margin: 0 auto;
            border-radius: 14px;
            overflow: hidden;
            background: #020617;
            aspect-ratio: 4/3;
        }
        #attendance-video, #enroll-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        #attendance-overlay, #enroll-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }
    </style>
</head>
<body class="bg-light">

    <!-- Top Navigation Bar for Teacher -->
    <nav class="navbar navbar-expand-lg teacher-navbar sticky-top navbar-dark py-2 py-md-3">
        <div class="container-fluid px-3 px-sm-4 px-xl-5">
            <a class="navbar-brand d-flex align-items-center gap-2 text-white fw-bold" href="teacher_dashboard.php">
                <div class="rounded-circle bg-primary bg-opacity-25 text-primary-subtle d-flex align-items-center justify-content-center p-2" style="width: 40px; height: 40px;">
                    <i class="bi bi-mortarboard-fill fs-5 text-white"></i>
                </div>
                <div>
                    <span class="d-block lh-1 fs-6">GCOER Faculty Portal</span>
                    <small class="text-white-50" style="font-size: 0.72rem;">AI Face Biometric Attendance</small>
                </div>
            </a>

            <div class="d-flex align-items-center gap-2">
                <!-- User Profile Pill -->
                <div class="d-none d-md-flex align-items-center gap-2 bg-white bg-opacity-10 rounded-pill px-3 py-1 text-white border border-white border-opacity-10 me-2">
                    <i class="bi bi-person-circle fs-5 text-info"></i>
                    <div class="text-start lh-sm">
                        <span class="fw-semibold small d-block"><?= htmlspecialchars($teacher['name']) ?></span>
                        <span class="text-white-50" style="font-size: 0.70rem;"><?= htmlspecialchars($teacher['teacher_id']) ?> &bull; <?= htmlspecialchars($teacher['department']) ?></span>
                    </div>
                </div>

                <a href="teacher_attendance.php" class="btn btn-sm btn-outline-light rounded-pill px-3 py-1 d-flex align-items-center gap-1">
                    <i class="bi bi-calendar-check"></i>
                    <span class="d-none d-sm-inline">History</span>
                </a>

                <a href="teacher_logout.php" class="btn btn-sm btn-danger rounded-pill px-3 py-1 d-flex align-items-center gap-1" title="Sign Out">
                    <i class="bi bi-box-arrow-right"></i>
                    <span class="d-none d-sm-inline">Logout</span>
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-3 px-sm-4 px-xl-5 py-4">

        <!-- Welcome Banner & Alert -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 pb-3 mb-3 border-bottom">
            <div>
                <h1 class="h4 fw-bold text-dark mb-1">Welcome back, <?= htmlspecialchars($teacher['name']) ?>!</h1>
                <p class="text-muted small mb-0">
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill me-1"><?= htmlspecialchars($teacher['teacher_id']) ?></span>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill me-2"><?= htmlspecialchars($teacher['department']) ?></span>
                    <span id="live-clock" class="text-muted"><i class="bi bi-clock me-1"></i> <?= date('l, d F Y') ?></span>
                </p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <?php if (!$teacher['has_face']): ?>
                    <button type="button" class="btn btn-warning rounded-pill px-3 py-2 fw-semibold shadow-xs" data-bs-toggle="modal" data-bs-target="#enrollModal">
                        <i class="bi bi-camera-fill me-1"></i> Enroll Face Biometrics
                    </button>
                <?php else: ?>
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-2" data-bs-toggle="modal" data-bs-target="#enrollModal" title="Update biometric descriptor">
                        <i class="bi bi-person-bounding-box me-1"></i> Update Face Data
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$teacher['has_face']): ?>
            <div class="alert alert-warning border border-warning border-opacity-25 rounded-4 p-3 mb-4 shadow-xs d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill fs-4 text-warning"></i>
                    <div>
                        <strong class="d-block">Biometric Face Registration Pending</strong>
                        <span class="small text-muted">You must enroll your face before you can mark biometric attendance.</span>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-warning rounded-pill px-3 py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#enrollModal">
                    <i class="bi bi-camera me-1"></i> Enroll Face Now
                </button>
            </div>
        <?php endif; ?>

        <!-- Today Attendance Action Card -->
        <div class="row g-3 g-xl-4 mb-4">
            <div class="col-12 col-lg-8">
                <div class="card stat-card-teacher h-100 overflow-hidden border-0 shadow-sm">
                    <div class="card-body p-4 d-flex flex-column justify-content-between">
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
                            <div>
                                <span class="text-uppercase fw-bold text-muted" style="font-size: 0.72rem; letter-spacing: 0.8px;">TODAY'S ATTENDANCE STATUS</span>
                                <h3 class="fw-bold mt-1 mb-0" id="today-status-text">
                                    <?php if ($today_status === 'Present'): ?>
                                        <span class="text-success"><i class="bi bi-check-circle-fill me-2"></i>Present</span>
                                    <?php else: ?>
                                        <span class="text-warning"><i class="bi bi-clock-history me-2"></i>Not Marked</span>
                                    <?php endif; ?>
                                </h3>
                                <p class="text-muted small mb-0 mt-1" id="today-details-text">
                                    <?php if ($today_status === 'Present'): ?>
                                        Recorded at <strong><?= htmlspecialchars($today_time) ?></strong> via AI Face Biometric Verification
                                    <?php else: ?>
                                        You have not marked attendance for today yet.
                                    <?php endif; ?>
                                </p>
                            </div>

                            <div class="text-end">
                                <span class="badge <?= $today_status === 'Present' ? 'bg-success' : 'bg-warning text-dark' ?> bg-opacity-10 <?= $today_status === 'Present' ? 'text-success border-success' : 'border-warning' ?> border px-3 py-2 rounded-pill fs-6 fw-semibold">
                                    <i class="bi bi-calendar-event me-1"></i> <?= date('d M Y') ?>
                                </span>
                            </div>
                        </div>

                        <!-- Mark Attendance Button Box -->
                        <div class="bg-light rounded-4 p-3 p-md-4 border border-light">
                            <?php if ($today_status === 'Present'): ?>
                                <div class="d-flex flex-column flex-sm-row align-items-center justify-content-between gap-3 text-center text-sm-start">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center shadow-xs" style="width: 48px; height: 48px;">
                                            <i class="bi bi-check2-all fs-4"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold text-dark mb-0">Attendance Completed for Today</h6>
                                            <small class="text-muted">Check-in time: <?= htmlspecialchars($today_time) ?></small>
                                        </div>
                                    </div>
                                    <button class="btn btn-outline-success rounded-pill px-4 py-2 fw-semibold" disabled>
                                        <i class="bi bi-check-circle me-1"></i> Marked (<?= htmlspecialchars($today_time) ?>)
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="d-flex flex-column flex-sm-row align-items-center justify-content-between gap-3 text-center text-sm-start">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-1">Ready to Mark Attendance?</h6>
                                        <p class="text-muted small mb-0">Position your face in front of the camera for AI verification.</p>
                                    </div>
                                    <button type="button" class="btn btn-primary btn-lg rounded-pill px-4 py-2 fw-bold shadow-sm d-flex align-items-center gap-2" id="btnOpenAttendanceModal" data-bs-toggle="modal" data-bs-target="#attendanceModal" <?= !$teacher['has_face'] ? 'disabled' : '' ?>>
                                        <i class="bi bi-camera-video-fill"></i>
                                        <span>Mark Attendance</span>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Summary Card -->
            <div class="col-12 col-lg-4">
                <div class="card stat-card-teacher h-100 border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom border-light pt-3 pb-2">
                        <h6 class="card-title fw-bold text-dark mb-0"><i class="bi bi-person-badge-fill me-2 text-primary"></i>Faculty Profile</h6>
                    </div>
                    <div class="card-body p-3 p-sm-4">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-bold fs-4 border border-primary border-opacity-25" style="width: 52px; height: 52px;">
                                <?= strtoupper(substr($teacher['name'], 0, 1)) ?>
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark mb-0"><?= htmlspecialchars($teacher['name']) ?></h6>
                                <small class="text-muted"><?= htmlspecialchars($teacher['email'] ?: 'No email registered') ?></small>
                            </div>
                        </div>

                        <ul class="list-group list-group-flush small">
                            <li class="list-group-item px-0 py-2 d-flex justify-content-between text-muted">
                                <span>Teacher ID</span>
                                <strong class="text-dark"><?= htmlspecialchars($teacher['teacher_id']) ?></strong>
                            </li>
                            <li class="list-group-item px-0 py-2 d-flex justify-content-between text-muted">
                                <span>Department</span>
                                <strong class="text-dark"><?= htmlspecialchars($teacher['department']) ?></strong>
                            </li>
                            <li class="list-group-item px-0 py-2 d-flex justify-content-between text-muted">
                                <span>Phone</span>
                                <strong class="text-dark"><?= htmlspecialchars($teacher['phone'] ?: 'N/A') ?></strong>
                            </li>
                            <li class="list-group-item px-0 py-2 d-flex justify-content-between text-muted">
                                <span>Biometric Face Status</span>
                                <?php if ($teacher['has_face']): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill"><i class="bi bi-check-circle me-1"></i>Enrolled</span>
                                <?php else: ?>
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill"><i class="bi bi-exclamation-circle me-1"></i>Pending</span>
                                <?php endif; ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4 Metrics Cards -->
        <div class="row g-3 g-xl-4 mb-4">
            <div class="col-6 col-md-3">
                <div class="card stat-card-teacher p-3 p-sm-4 bg-white">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Days Present</span>
                        <div class="rounded-circle bg-success bg-opacity-10 text-success p-2">
                            <i class="bi bi-person-check fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-dark mb-0" id="metric-present"><?= $total_present ?></h3>
                    <small class="text-muted">Total check-ins</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card stat-card-teacher p-3 p-sm-4 bg-white">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Days Absent</span>
                        <div class="rounded-circle bg-danger bg-opacity-10 text-danger p-2">
                            <i class="bi bi-person-x fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-dark mb-0" id="metric-absent"><?= $total_absent ?></h3>
                    <small class="text-muted">Estimated days</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card stat-card-teacher p-3 p-sm-4 bg-white">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Attendance Rate</span>
                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-2">
                            <i class="bi bi-pie-chart fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-dark mb-0" id="metric-rate"><?= $attendance_rate ?>%</h3>
                    <div class="progress mt-1" style="height: 5px;">
                        <div class="progress-bar bg-primary" id="metric-bar" role="progressbar" style="width: <?= $attendance_rate ?>%"></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card stat-card-teacher p-3 p-sm-4 bg-white">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Working Days</span>
                        <div class="rounded-circle bg-info bg-opacity-10 text-info p-2">
                            <i class="bi bi-calendar-range fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-dark mb-0" id="metric-days"><?= $total_days ?></h3>
                    <small class="text-muted">Total marked sessions</small>
                </div>
            </div>
        </div>

        <!-- Attendance Tracking Section (History Table) -->
        <div class="card stat-card-teacher border-0 shadow-sm overflow-hidden mb-4">
            <div class="card-header bg-white border-bottom border-light pt-3 pb-3 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3">
                <div>
                    <h5 class="fw-bold text-dark mb-1"><i class="bi bi-journal-text me-2 text-primary"></i>My Attendance Records</h5>
                    <p class="text-muted small mb-0">View date-wise verification history strictly for your account.</p>
                </div>
                
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <input type="date" id="filterFrom" class="form-control form-control-sm" style="max-width: 150px;" title="From date">
                    <input type="date" id="filterTo" class="form-control form-control-sm" style="max-width: 150px;" title="To date">
                    <button id="btnFilter" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    <button id="btnResetFilter" class="btn btn-sm btn-outline-secondary rounded-pill px-2">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-nowrap">
                        <thead class="table-light text-muted small">
                            <tr>
                                <th class="ps-4">Date</th>
                                <th>Check-in Time</th>
                                <th>Status</th>
                                <th>Biometric Verification</th>
                                <th>Location Verified</th>
                            </tr>
                        </thead>
                        <tbody id="attendance-table-body">
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <div class="spinner-border spinner-border-sm text-primary me-2"></div> Loading records...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white border-top border-light py-2 px-4 d-flex justify-content-between align-items-center small text-muted">
                <span id="record-count-text">Showing 0 records</span>
                <a href="teacher_attendance.php" class="text-primary text-decoration-none fw-semibold">
                    Detailed View <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>

    </div>

    <!-- Attendance Verification Modal with Camera -->
    <div class="modal fade" id="attendanceModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="attendanceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header bg-primary text-white border-0 py-3">
                    <h5 class="modal-title fw-bold d-flex align-items-center gap-2" id="attendanceModalLabel">
                        <i class="bi bi-person-bounding-box"></i> Biometric Face Verification
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" id="btnCloseModal"></button>
                </div>
                <div class="modal-body p-3 p-md-4 text-center">
                    
                    <div id="modal-status-banner" class="alert alert-info py-2 small mb-3">
                        <div class="spinner-border spinner-border-sm me-2"></div> Initializing facial recognition models...
                    </div>

                    <div class="camera-scanner-box mb-3">
                        <video id="attendance-video" autoplay muted playsinline></video>
                        <canvas id="attendance-overlay"></canvas>
                        
                        <div id="camera-loading" class="position-absolute top-50 start-50 translate-middle text-white text-center">
                            <div class="spinner-border text-primary mb-2"></div>
                            <p class="small mb-0">Starting camera & AI models...</p>
                        </div>
                    </div>

                    <!-- Result feedback box -->
                    <div id="verify-result-box" class="d-none p-3 rounded-4 mb-2"></div>

                    <p class="text-muted small mb-0">
                        <i class="bi bi-shield-lock me-1 text-primary"></i> AI compares your live webcam facial descriptors with your registered biometric data.
                    </p>
                </div>
                <div class="modal-footer border-0 bg-light py-2 d-flex justify-content-between">
                    <span class="text-muted small" id="geo-status"><i class="bi bi-geo-alt me-1"></i> Checking location...</span>
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Face Biometric Enrollment Modal -->
    <div class="modal fade" id="enrollModal" data-bs-backdrop="static" tabindex="-1" aria-labelledby="enrollModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header bg-dark text-white border-0 py-3">
                    <h5 class="modal-title fw-bold" id="enrollModalLabel">
                        <i class="bi bi-camera-fill me-2"></i> Enroll Face Biometrics
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" id="btnCloseEnrollModal"></button>
                </div>
                <div class="modal-body p-3 text-center">
                    <div id="enroll-status" class="alert alert-secondary py-2 small mb-3">
                        Camera ready. Position your face in the box and click Capture.
                    </div>

                    <div class="camera-scanner-box mb-3" style="aspect-ratio: 4/3; max-height: 280px;">
                        <video id="enroll-video" autoplay muted playsinline></video>
                        <canvas id="enroll-overlay"></canvas>
                        <div id="enroll-camera-loading" class="position-absolute top-50 start-50 translate-middle text-white text-center">
                            <div class="spinner-border text-warning mb-2"></div>
                            <p class="small mb-0">Starting camera...</p>
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" id="btnStartEnrollCamera" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                            <i class="bi bi-camera-video me-1"></i> Start Camera
                        </button>
                        <button type="button" id="btnCaptureEnrollFace" class="btn btn-success btn-sm rounded-pill px-4 fw-semibold" disabled>
                            <i class="bi bi-camera-fill me-1"></i> Capture & Save Face
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Teacher Dashboard Interactive Script -->
    <script>
        const CURRENT_TEACHER_CODE = "<?= htmlspecialchars($teacher['teacher_id']) ?>";
        const CURRENT_TEACHER_NAME = "<?= htmlspecialchars($teacher['name']) ?>";
        const HAS_REGISTERED_FACE = <?= $teacher['has_face'] ? 'true' : 'false' ?>;

        let modelsLoaded = false;
        let registeredDescriptor = null;
        let attendanceStream = null;
        let attendanceInterval = null;
        let isAttendanceVerifying = false;
        let userLatitude = 16.9902;
        let userLongitude = 73.3120;

        // Load models helper
        async function loadAiModels() {
            if (modelsLoaded) return;
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri('models/face-api-models/tiny_face_detector'),
                faceapi.nets.faceLandmark68Net.loadFromUri('models/face-api-models/face_landmark_68'),
                faceapi.nets.faceRecognitionNet.loadFromUri('models/face-api-models/face_recognition')
            ]);
            modelsLoaded = true;
        }

        // Fetch teacher face descriptor
        async function fetchMyFaceDescriptor() {
            try {
                const res = await fetch('api/teacher_face.php');
                const data = await res.json();
                if (data.success && data.face_descriptor) {
                    const parsed = JSON.parse(data.face_descriptor);
                    registeredDescriptor = new Float32Array(parsed);
                    return true;
                }
            } catch (e) {
                console.error('Failed to load registered face descriptor', e);
            }
            return false;
        }

        // Geolocation
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    userLatitude = pos.coords.latitude;
                    userLongitude = pos.coords.longitude;
                    const geoEl = document.getElementById('geo-status');
                    if (geoEl) geoEl.innerHTML = '<i class="bi bi-geo-alt-fill text-success me-1"></i> Campus location acquired';
                },
                (err) => {
                    const geoEl = document.getElementById('geo-status');
                    if (geoEl) geoEl.innerHTML = '<i class="bi bi-geo-alt text-muted me-1"></i> Default location used';
                },
                { timeout: 8000 }
            );
        }

        // Load Attendance Table
        async function loadAttendanceRecords(dateFrom = '', dateTo = '') {
            const tbody = document.getElementById('attendance-table-body');
            const countText = document.getElementById('record-count-text');

            try {
                let url = 'api/get_teacher_records.php';
                const params = new URLSearchParams();
                if (dateFrom) params.append('dateFrom', dateFrom);
                if (dateTo) params.append('dateTo', dateTo);
                if (params.toString()) url += '?' + params.toString();

                const response = await fetch(url);
                const data = await response.json();

                if (data.success) {
                    // Update summary metrics
                    document.getElementById('metric-present').textContent = data.summary.total_present;
                    document.getElementById('metric-absent').textContent = data.summary.total_absent;
                    document.getElementById('metric-days').textContent = data.summary.total_working_days;
                    document.getElementById('metric-rate').textContent = data.summary.attendance_rate + '%';
                    document.getElementById('metric-bar').style.width = data.summary.attendance_rate + '%';

                    if (data.records.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No attendance records found for this period.</td></tr>';
                        countText.textContent = 'Showing 0 records';
                        return;
                    }

                    countText.textContent = `Showing ${data.records.length} record(s)`;
                    tbody.innerHTML = data.records.map(r => `
                        <tr>
                            <td class="ps-4 fw-semibold text-dark">
                                <i class="bi bi-calendar-check me-2 text-primary"></i>${r.date_formatted}
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-clock me-1 text-muted"></i>${r.time_formatted}</span>
                            </td>
                            <td>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-1">
                                    <i class="bi bi-check-circle me-1"></i>${r.status}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2 py-1 small">
                                    <i class="bi bi-shield-check me-1"></i>Face Verified
                                </span>
                            </td>
                            <td>
                                ${r.location_verified ? 
                                    '<span class="text-success small fw-medium"><i class="bi bi-geo-alt-fill me-1"></i>Verified</span>' : 
                                    '<span class="text-muted small"><i class="bi bi-geo-alt me-1"></i>Logged</span>'}
                            </td>
                        </tr>
                    `).join('');
                } else {
                    tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-danger">${data.message || 'Error loading records.'}</td></tr>`;
                }
            } catch (err) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-danger">Network error loading attendance history.</td></tr>';
            }
        }

        // Filter button listeners
        document.getElementById('btnFilter').addEventListener('click', () => {
            const df = document.getElementById('filterFrom').value;
            const dt = document.getElementById('filterTo').value;
            loadAttendanceRecords(df, dt);
        });

        document.getElementById('btnResetFilter').addEventListener('click', () => {
            document.getElementById('filterFrom').value = '';
            document.getElementById('filterTo').value = '';
            loadAttendanceRecords();
        });

        // ------------------ Biometric Attendance Verification Loop ------------------
        const attModal = document.getElementById('attendanceModal');
        const attVideo = document.getElementById('attendance-video');
        const attOverlay = document.getElementById('attendance-overlay');
        const modalStatus = document.getElementById('modal-status-banner');
        const cameraLoading = document.getElementById('camera-loading');
        const verifyResultBox = document.getElementById('verify-result-box');

        attModal.addEventListener('shown.bs.modal', async () => {
            verifyResultBox.classList.add('d-none');
            isAttendanceVerifying = false;

            try {
                modalStatus.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div> Loading face models & registered profile...';
                modalStatus.className = 'alert alert-info py-2 small mb-3';

                await loadAiModels();
                const hasDesc = await fetchMyFaceDescriptor();

                if (!hasDesc || !registeredDescriptor) {
                    modalStatus.innerHTML = '⚠ No registered biometric face found. Please enroll your face first.';
                    modalStatus.className = 'alert alert-warning py-2 small mb-3';
                    cameraLoading.classList.add('d-none');
                    return;
                }

                modalStatus.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div> Opening camera feed...';

                attendanceStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                attVideo.srcObject = attendanceStream;
                cameraLoading.classList.add('d-none');

                attVideo.onloadedmetadata = () => {
                    attVideo.play();
                    startFaceRecognitionLoop();
                };

            } catch (err) {
                console.error(err);
                modalStatus.innerHTML = '⚠ Camera access denied or hardware error. Please allow camera permissions.';
                modalStatus.className = 'alert alert-danger py-2 small mb-3';
                cameraLoading.classList.add('d-none');
            }
        });

        function stopAttendanceCamera() {
            if (attendanceInterval) {
                clearInterval(attendanceInterval);
                attendanceInterval = null;
            }
            if (attendanceStream) {
                attendanceStream.getTracks().forEach(track => track.stop());
                attendanceStream = null;
            }
            attVideo.srcObject = null;
        }

        attModal.addEventListener('hidden.bs.modal', () => {
            stopAttendanceCamera();
        });

        function startFaceRecognitionLoop() {
            const displaySize = { width: attVideo.videoWidth, height: attVideo.videoHeight };
            faceapi.matchDimensions(attOverlay, displaySize);

            modalStatus.innerHTML = '<i class="bi bi-person-bounding-box me-2"></i> Position your face inside the frame...';
            modalStatus.className = 'alert alert-primary py-2 small mb-3';

            attendanceInterval = setInterval(async () => {
                if (isAttendanceVerifying) return;

                const detections = await faceapi.detectAllFaces(attVideo, new faceapi.TinyFaceDetectorOptions())
                                                .withFaceLandmarks()
                                                .withFaceDescriptors();

                const resized = faceapi.resizeResults(detections, displaySize);
                const ctx = attOverlay.getContext('2d');
                ctx.clearRect(0, 0, attOverlay.width, attOverlay.height);
                faceapi.draw.drawDetections(attOverlay, resized);

                if (detections.length === 0) {
                    modalStatus.innerHTML = '<i class="bi bi-person-scan me-2"></i> Looking for face... Position face in camera.';
                    modalStatus.className = 'alert alert-info py-2 small mb-3';
                } else if (detections.length > 1) {
                    modalStatus.innerHTML = '⚠ Multiple faces detected. Only one faculty member allowed.';
                    modalStatus.className = 'alert alert-warning py-2 small mb-3';
                } else {
                    // Single face detected -> Compute Euclidean distance to registered biometric descriptor
                    isAttendanceVerifying = true;
                    modalStatus.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div> Verifying face biometric signature...';
                    modalStatus.className = 'alert alert-info py-2 small mb-3';

                    const liveDescriptor = detections[0].descriptor;
                    const distance = faceapi.euclideanDistance(liveDescriptor, registeredDescriptor);

                    // Standard Euclidean distance threshold for 128d face descriptors
                    // 0.58 comfortably accommodates real-world variations (glasses, lighting, angle) while preventing false matches
                    const VERIFY_THRESHOLD = 0.58;

                    if (distance <= VERIFY_THRESHOLD) {
                        modalStatus.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i> <strong>✓ BIOMETRIC FACE VERIFIED!</strong> Submitting attendance...';
                        modalStatus.className = 'alert alert-success py-2 small mb-3';

                        await submitTeacherAttendance();
                    } else {
                        modalStatus.innerHTML = '⚠ <strong>Face Verification Pending</strong>. Align face directly with camera (Diff: ' + distance.toFixed(2) + ' / Req: ≤ ' + VERIFY_THRESHOLD + ').';
                        modalStatus.className = 'alert alert-warning py-2 small mb-3';
                        setTimeout(() => { isAttendanceVerifying = false; }, 1000);
                    }
                }
            }, 600);
        }

        async function submitTeacherAttendance() {
            try {
                const formData = new FormData();
                formData.append('teacher_id', CURRENT_TEACHER_CODE);
                formData.append('latitude', userLatitude);
                formData.append('longitude', userLongitude);

                const response = await fetch('api/mark_attendance.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                stopAttendanceCamera();

                verifyResultBox.classList.remove('d-none');
                if (result.success) {
                    verifyResultBox.className = 'p-3 rounded-4 mb-2 bg-success bg-opacity-10 text-success border border-success border-opacity-25';
                    verifyResultBox.innerHTML = `
                        <i class="bi bi-check-circle-fill fs-1 text-success d-block mb-2"></i>
                        <h5 class="fw-bold mb-1">Attendance Successfully Marked!</h5>
                        <p class="mb-2 small">Date: <strong>${result.date}</strong> &bull; Time: <strong>${result.time}</strong></p>
                        <span class="badge bg-success px-3 py-2 rounded-pill"><i class="bi bi-shield-check me-1"></i> Biometric Present</span>
                    `;

                    // Update UI immediately
                    document.getElementById('today-status-text').innerHTML = '<span class="text-success"><i class="bi bi-check-circle-fill me-2"></i>Present</span>';
                    document.getElementById('today-details-text').innerHTML = `Recorded at <strong>${result.time}</strong> via AI Face Biometric Verification`;

                    loadAttendanceRecords();
                } else if (result.duplicate) {
                    verifyResultBox.className = 'p-3 rounded-4 mb-2 bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25';
                    verifyResultBox.innerHTML = `
                        <i class="bi bi-info-circle-fill fs-1 text-warning d-block mb-2"></i>
                        <h5 class="fw-bold mb-1">Attendance Already Marked</h5>
                        <p class="mb-0 small">${result.message}</p>
                    `;
                } else {
                    verifyResultBox.className = 'p-3 rounded-4 mb-2 bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25';
                    verifyResultBox.innerHTML = `
                        <i class="bi bi-x-circle-fill fs-1 text-danger d-block mb-2"></i>
                        <h5 class="fw-bold mb-1">Attendance Failed</h5>
                        <p class="mb-0 small">${result.message || 'Could not record attendance.'}</p>
                    `;
                }
            } catch (err) {
                verifyResultBox.className = 'p-3 rounded-4 mb-2 bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25';
                verifyResultBox.innerHTML = '<h5 class="fw-bold mb-1">Network Error</h5><p class="mb-0 small">Failed to reach the server.</p>';
            }
        }

        // ------------------ Face Enrollment Modal ------------------
        const enrollModal = document.getElementById('enrollModal');
        const enrollVideo = document.getElementById('enroll-video');
        const enrollOverlay = document.getElementById('enroll-overlay');
        const enrollStatus = document.getElementById('enroll-status');
        const btnStartEnrollCamera = document.getElementById('btnStartEnrollCamera');
        const btnCaptureEnrollFace = document.getElementById('btnCaptureEnrollFace');
        const enrollLoading = document.getElementById('enroll-camera-loading');
        let enrollStream = null;
        let enrollInterval = null;

        btnStartEnrollCamera.addEventListener('click', async () => {
            try {
                enrollLoading.classList.remove('d-none');
                await loadAiModels();

                enrollStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                enrollVideo.srcObject = enrollStream;
                enrollLoading.classList.add('d-none');

                enrollVideo.onloadedmetadata = () => {
                    enrollVideo.play();
                    btnStartEnrollCamera.disabled = true;
                    btnCaptureEnrollFace.disabled = false;
                    enrollStatus.innerHTML = '<i class="bi bi-camera me-1"></i> Look at the camera and click <strong>Capture & Save Face</strong>';
                    enrollStatus.className = 'alert alert-primary py-2 small mb-3';
                };
            } catch (err) {
                enrollLoading.classList.add('d-none');
                enrollStatus.innerHTML = '⚠ Camera access denied. Please grant webcam permissions.';
                enrollStatus.className = 'alert alert-danger py-2 small mb-3';
            }
        });

        btnCaptureEnrollFace.addEventListener('click', async () => {
            btnCaptureEnrollFace.disabled = true;
            btnCaptureEnrollFace.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Processing...';
            enrollStatus.innerHTML = '<div class="spinner-border spinner-border-sm me-1"></div> Detecting face & extracting biometric descriptors...';

            try {
                const detection = await faceapi.detectSingleFace(enrollVideo, new faceapi.TinyFaceDetectorOptions())
                                               .withFaceLandmarks()
                                               .withFaceDescriptor();

                if (!detection) {
                    enrollStatus.innerHTML = '⚠ No face detected. Please face the camera directly in good lighting.';
                    enrollStatus.className = 'alert alert-warning py-2 small mb-3';
                    btnCaptureEnrollFace.disabled = false;
                    btnCaptureEnrollFace.innerHTML = '<i class="bi bi-camera-fill me-1"></i> Capture & Save Face';
                    return;
                }

                // Convert Float32Array to standard array for JSON serialization
                const descriptorArray = Array.from(detection.descriptor);
                const descriptorJson = JSON.stringify(descriptorArray);

                const formData = new FormData();
                formData.append('face_descriptor', descriptorJson);

                const response = await fetch('api/teacher_face.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    enrollStatus.innerHTML = '<strong>✓ Biometric Face Registered Successfully!</strong> Reloading...';
                    enrollStatus.className = 'alert alert-success py-2 small mb-3';
                    setTimeout(() => {
                        window.location.reload();
                    }, 1200);
                } else {
                    enrollStatus.innerHTML = '⚠ ' + (result.message || 'Failed to save face descriptor.');
                    enrollStatus.className = 'alert alert-danger py-2 small mb-3';
                    btnCaptureEnrollFace.disabled = false;
                    btnCaptureEnrollFace.innerHTML = '<i class="bi bi-camera-fill me-1"></i> Capture & Save Face';
                }
            } catch (err) {
                enrollStatus.innerHTML = '⚠ Error processing face capture.';
                enrollStatus.className = 'alert alert-danger py-2 small mb-3';
                btnCaptureEnrollFace.disabled = false;
                btnCaptureEnrollFace.innerHTML = '<i class="bi bi-camera-fill me-1"></i> Capture & Save Face';
            }
        });

        enrollModal.addEventListener('hidden.bs.modal', () => {
            if (enrollStream) {
                enrollStream.getTracks().forEach(t => t.stop());
                enrollStream = null;
            }
            enrollVideo.srcObject = null;
            btnStartEnrollCamera.disabled = false;
            btnCaptureEnrollFace.disabled = true;
        });

        // Initialize records on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadAttendanceRecords();
        });
    </script>
</body>
</html>
