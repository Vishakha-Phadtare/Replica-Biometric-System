<?php
// attendance-kiosk.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Teacher Attendance Kiosk - AI Face Recognition</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom Styles -->
    <link href="css/style.css" rel="stylesheet">
    
    <style>
        body { 
            background: radial-gradient(circle at 50% 10%, #1e293b 0%, #0f172a 100%);
            min-height: 100vh;
            color: #f8fafc;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
        .kiosk-nav {
            width: 100%;
            max-width: 780px;
            margin-bottom: 0.75rem;
        }
        .kiosk-card {
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.4);
            overflow: hidden;
            background: #ffffff;
            color: #0f172a;
        }
        .kiosk-header {
            background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
            color: #fff;
            padding: 1.25rem 1rem;
            text-align: center;
        }
        @media (min-width: 576px) {
            .kiosk-header { padding: 1.75rem 1.5rem; }
        }
        .camera-wrapper {
            position: relative;
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            border-radius: 14px;
            overflow: hidden;
            background-color: #020617;
            aspect-ratio: 4/3;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        #video-kiosk {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        #overlay-kiosk {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10;
        }
        .status-banner {
            font-size: clamp(0.85rem, 2.5vw, 1.05rem);
            font-weight: 600;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 1.25rem;
            word-break: break-word;
        }
        .time-display {
            font-size: clamp(1.1rem, 3.5vw, 1.35rem);
            font-weight: 700;
            color: #475569;
            text-align: center;
            margin-top: 1.25rem;
            font-variant-numeric: tabular-nums;
        }
    </style>
</head>
<body class="d-flex flex-column align-items-center justify-content-center p-2 p-sm-3 p-md-4">

<!-- Kiosk Top Navigation Bar -->
<div class="kiosk-nav d-flex justify-content-between align-items-center px-1">
    <a href="dashboard.php" class="btn btn-sm btn-outline-light rounded-pill px-3 py-1 bg-dark bg-opacity-50 text-white text-decoration-none">
        <i class="bi bi-arrow-left me-1"></i> Dashboard
    </a>
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-50 rounded-pill px-2 py-1 small">
            <i class="bi bi-broadcast me-1"></i> Live
        </span>
        <button type="button" class="btn btn-sm btn-outline-light rounded-circle p-1" style="width: 32px; height: 32px;" onclick="toggleFullscreen()" title="Toggle Fullscreen">
            <i class="bi bi-fullscreen" id="fsIcon"></i>
        </button>
    </div>
</div>

<div class="kiosk-card w-100" style="max-width: 780px;">
    
    <!-- Header -->
    <div class="kiosk-header">
        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-2" style="width: 52px; height: 52px; background: rgba(255, 255, 255, 0.18);">
            <i class="bi bi-person-bounding-box fs-2 text-white"></i>
        </div>
        <h3 class="fw-bold mb-1 fs-4">AI FACE ATTENDANCE</h3>
        <p class="mb-0 text-white-50 small">Smart Biometric Recognition &bull; GCOER Campus</p>
    </div>

    <div class="card-body p-3 p-sm-4 p-md-5">
        
        <!-- Location Status -->
        <div id="kiosk-location-status" class="status-banner bg-light text-secondary border">
            <div class="spinner-border spinner-border-sm me-2 text-primary" role="status"></div>
            Checking college GPS location...
        </div>

        <!-- Camera Area -->
        <div class="camera-wrapper mb-3">
            <video id="video-kiosk" autoplay muted playsinline style="display: none;"></video>
            <canvas id="overlay-kiosk"></canvas>
            
            <div id="kiosk-placeholder" class="d-flex flex-column justify-content-center align-items-center h-100 w-100 text-white text-center p-3">
                <i class="bi bi-camera-video display-3 opacity-25 mb-2"></i>
                <h6 id="kiosk-placeholder-text" class="fw-medium text-white-50">Waiting for location verification...</h6>
                <div id="kiosk-loader" class="loader mt-3 d-none"></div>
            </div>
        </div>

        <!-- Face Recognition Status -->
        <div id="kiosk-face-status" class="status-banner bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 d-none">
            <i class="bi bi-person-scan me-2"></i> Looking for face...
        </div>

        <!-- Success/Error Overlay Container -->
        <div id="kiosk-result-container" class="d-none text-center">
            <!-- Content injected via JS -->
        </div>

        <!-- Live Clock -->
        <div class="time-display" id="kiosk-clock">
            --:-- --
        </div>
        
    </div>
</div>

<script>
function toggleFullscreen() {
    const icon = document.getElementById('fsIcon');
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(() => {});
        if (icon) icon.className = 'bi bi-fullscreen-exit';
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen().catch(() => {});
        }
        if (icon) icon.className = 'bi bi-fullscreen';
    }
}
</script>

<!-- Face API -->
<script src="js/face-api.min.js"></script>
<!-- Kiosk Logic -->
<script src="js/kiosk.js"></script>
</body>
</html>
