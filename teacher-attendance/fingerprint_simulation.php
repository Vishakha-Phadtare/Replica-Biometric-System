<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<main class="col-12 col-lg-10 ms-auto main-content px-3 px-sm-4 px-xl-5 py-3 py-md-4">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 pt-1 pb-3 mb-4 border-bottom border-secondary border-opacity-10">
        <div>
            <h1 class="h4 fw-bold text-dark mb-1">Fingerprint Biometric &mdash; Simulation</h1>
            <p class="text-muted mb-0 small">Hardware integration sandbox demonstration.</p>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 text-center p-3 p-sm-4 p-md-5">
                <div class="card-body p-2 p-sm-3">
                    <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle mb-3" style="width: 72px; height: 72px;">
                        <i class="bi bi-fingerprint fs-1"></i>
                    </div>
                    
                    <h4 class="fw-bold mb-3 text-dark">Fingerprint Biometric Simulation</h4>
                    
                    <div class="alert alert-info border-0 rounded-4 p-3 p-sm-4 text-start shadow-xs">
                        <strong class="d-flex align-items-center"><i class="bi bi-info-circle-fill me-2 fs-5"></i> Notice:</strong>
                        <p class="mt-2 mb-2 text-muted">
                            This module is a demonstration of how fingerprint attendance integrates with external optical/capacitive biometric hardware scanners.
                        </p>
                        <hr class="opacity-25 my-2">
                        <p class="mb-0 text-muted small">
                            <strong>No external fingerprint hardware is detected.</strong><br>
                            Standard laptop webcams and phone touchscreens cannot authenticate physical fingerprints. The primary operational biometric engine in this system is <strong>AI Face Recognition</strong>.
                        </p>
                    </div>

                    <button class="btn btn-outline-secondary mt-3 rounded-pill px-4 py-2" disabled>
                        <i class="bi bi-fingerprint me-2"></i> Scan Fingerprint (Simulated)
                    </button>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>
