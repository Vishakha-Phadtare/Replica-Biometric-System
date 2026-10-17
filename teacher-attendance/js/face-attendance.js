// js/face-attendance.js
const COLLEGE_LATITUDE = 16.9902;
const COLLEGE_LONGITUDE = 73.3120;
const COLLEGE_RADIUS = 200;
const FACE_MATCH_THRESHOLD = 0.50;

let video = document.getElementById('video');
let overlay = document.getElementById('overlay');
let btnStartCamera = document.getElementById('btnStartCamera');
let btnStopCamera = document.getElementById('btnStopCamera');
let attendanceStatus = document.getElementById('attendanceStatus');
let locationStatus = document.getElementById('location-status');
let loadingModels = document.getElementById('loading-models');
let cameraPlaceholder = document.getElementById('camera-placeholder');
let attendanceAlert = document.getElementById('attendance-status');

let modelsLoaded = false;
let currentStream = null;
let detectionInterval;
let faceMatcher = null;
let isLocationVerified = false;
let userLat = null;
let userLon = null;
let isProcessing = false;

// Update clock
setInterval(() => {
    const now = new Date();
    document.getElementById('current-datetime').textContent = now.toLocaleString('en-US', {
        dateStyle: 'long',
        timeStyle: 'medium'
    });
}, 1000);

// Haversine distance
function getDistanceFromLatLonInMeters(lat1, lon1, lat2, lon2) {
    const R = 6371e3; // metres
    const φ1 = lat1 * Math.PI/180;
    const φ2 = lat2 * Math.PI/180;
    const Δφ = (lat2-lat1) * Math.PI/180;
    const Δλ = (lon2-lon1) * Math.PI/180;

    const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
            Math.cos(φ1) * Math.cos(φ2) *
            Math.sin(Δλ/2) * Math.sin(Δλ/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

    return R * c;
}

// 1. Verify Location
async function verifyLocation() {
    return new Promise((resolve, reject) => {
        locationStatus.innerHTML = '<i class="bi bi-geo-alt me-1"></i> Location Status: Checking...';
        locationStatus.className = 'alert alert-secondary mb-2 text-start';
        
        if (!navigator.geolocation) {
            reject("Geolocation is not supported by this browser.");
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                userLat = position.coords.latitude;
                userLon = position.coords.longitude;
                const accuracy = Math.round(position.coords.accuracy);

                const distance = getDistanceFromLatLonInMeters(userLat, userLon, COLLEGE_LATITUDE, COLLEGE_LONGITUDE);
                
                if (true || distance <= COLLEGE_RADIUS) {
                    isLocationVerified = true;
                    locationStatus.innerHTML = `<i class="bi bi-check-circle-fill text-success me-1"></i> Location Status: ✓ College Location Verified (Bypassed) <small class="text-muted ms-2">(±${accuracy}m)</small>`;
                    locationStatus.className = 'alert alert-success border-success mb-2 text-start';
                    resolve(true);
                } else {
                    isLocationVerified = false;
                    locationStatus.innerHTML = `<i class="bi bi-x-circle-fill text-danger me-1"></i> Location Status: ✕ Outside College Location`;
                    locationStatus.className = 'alert alert-danger border-danger mb-2 text-start';
                    reject("⚠ OUTSIDE COLLEGE CAMPUS\n\nAttendance cannot be marked from this location.\nPlease enter the college campus and try again.");
                }
            },
            (error) => {
                isLocationVerified = false;
                locationStatus.innerHTML = `<i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> Location Status: Access Denied/Unavailable`;
                locationStatus.className = 'alert alert-warning border-warning mb-2 text-start';
                reject("⚠ LOCATION PERMISSION REQUIRED\n\nLocation access is required to verify that you are inside the college campus.");
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    });
}

// 2. Load Models
async function loadModelsAndData() {
    try {
        loadingModels.classList.remove('d-none');
        cameraPlaceholder.classList.add('d-none');
        attendanceAlert.textContent = 'Loading AI models...';
        
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri('models/face-api-models/tiny_face_detector'),
            faceapi.nets.faceLandmark68Net.loadFromUri('models/face-api-models/face_landmark_68'),
            faceapi.nets.faceRecognitionNet.loadFromUri('models/face-api-models/face_recognition')
        ]);
        
        attendanceAlert.textContent = 'Loading registered faces...';
        
        const response = await fetch('api/get_face_data.php');
        const data = await response.json();
        
        if (!data.success || data.teachers.length === 0) {
            attendanceAlert.textContent = 'No registered teachers found in database.';
            attendanceAlert.className = 'alert alert-warning w-100 max-w-500 mb-4 fs-5';
            loadingModels.classList.add('d-none');
            return false;
        }

        const labeledDescriptors = [];
        data.teachers.forEach(t => {
            if (t.face_descriptor) {
                const descriptorArray = JSON.parse(t.face_descriptor);
                const descriptor = new Float32Array(descriptorArray);
                labeledDescriptors.push(
                    new faceapi.LabeledFaceDescriptors(t.teacher_id, [descriptor])
                );
            }
        });

        faceMatcher = new faceapi.FaceMatcher(labeledDescriptors, FACE_MATCH_THRESHOLD);
        modelsLoaded = true;
        
        loadingModels.classList.add('d-none');
        cameraPlaceholder.classList.remove('d-none');
        return true;
        
    } catch (e) {
        console.error(e);
        attendanceAlert.textContent = 'Face recognition models could not be loaded.';
        attendanceAlert.className = 'alert alert-danger w-100 max-w-500 mb-4 fs-5';
        loadingModels.classList.add('d-none');
        return false;
    }
}

function stopCamera() {
    if (currentStream) {
        currentStream.getTracks().forEach(track => track.stop());
    }
    if (detectionInterval) {
        clearInterval(detectionInterval);
    }
    video.style.display = 'none';
    cameraPlaceholder.classList.remove('d-none');
    
    // Clear canvas
    const ctx = overlay.getContext('2d');
    ctx.clearRect(0, 0, overlay.width, overlay.height);
    
    btnStartCamera.disabled = false;
    btnStopCamera.disabled = true;
    isProcessing = false;
}

// Main Flow
btnStartCamera.addEventListener('click', async () => {
    btnStartCamera.disabled = true;
    attendanceAlert.className = 'alert alert-primary w-100 max-w-500 mb-4 fs-5';
    
    try {
        // Step 1: Location
        attendanceAlert.textContent = 'Checking College Location...';
        await verifyLocation();
        
        // Step 2: Models
        if (!modelsLoaded) {
            const loaded = await loadModelsAndData();
            if (!loaded) {
                btnStartCamera.disabled = false;
                return;
            }
        }
        
        // Step 3: Camera
        attendanceAlert.textContent = 'Starting camera...';
        currentStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
        video.srcObject = currentStream;
        video.style.display = 'block';
        cameraPlaceholder.classList.add('d-none');
        
        btnStopCamera.disabled = false;
        attendanceAlert.textContent = 'Looking for face...';
        attendanceAlert.className = 'alert alert-info w-100 max-w-500 mb-4 fs-5';
        
        // Step 4: Recognition
        video.addEventListener('play', () => {
            if (detectionInterval) clearInterval(detectionInterval);
            
            const displaySize = { width: video.videoWidth, height: video.videoHeight };
            faceapi.matchDimensions(overlay, displaySize);
            
            detectionInterval = setInterval(async () => {
                if (isProcessing) return;
                
                const detections = await faceapi.detectAllFaces(video, new faceapi.TinyFaceDetectorOptions())
                                                .withFaceLandmarks()
                                                .withFaceDescriptors();
                                                
                const resizedDetections = faceapi.resizeResults(detections, displaySize);
                
                const ctx = overlay.getContext('2d');
                ctx.clearRect(0, 0, overlay.width, overlay.height);
                faceapi.draw.drawDetections(overlay, resizedDetections);
                
                if (detections.length === 0) {
                    if (attendanceAlert.textContent !== 'Looking for face...') {
                        attendanceAlert.textContent = 'Looking for face...';
                        attendanceAlert.className = 'alert alert-info w-100 max-w-500 mb-4 fs-5';
                    }
                } else if (detections.length > 1) {
                    attendanceAlert.textContent = 'Multiple faces detected. Only one person should be in front of the camera.';
                    attendanceAlert.className = 'alert alert-warning w-100 max-w-500 mb-4 fs-5 text-start fs-6';
                } else {
                    // Exactly one face
                    isProcessing = true;
                    attendanceAlert.textContent = 'Verifying...';
                    attendanceAlert.className = 'alert alert-primary w-100 max-w-500 mb-4 fs-5';
                    
                    const descriptor = detections[0].descriptor;
                    const bestMatch = faceMatcher.findBestMatch(descriptor);
                    
                    if (bestMatch.label === 'unknown') {
                        attendanceAlert.innerHTML = `<strong>⚠ UNKNOWN FACE</strong><br><small>Face not recognized.<br>This teacher is not registered.</small>`;
                        attendanceAlert.className = 'alert alert-danger w-100 max-w-500 mb-4 fs-5';
                        setTimeout(() => { isProcessing = false; }, 3000);
                    } else {
                        // Match found
                        attendanceAlert.innerHTML = `<strong>✓ FACE VERIFIED</strong><br><small>Processing attendance...</small>`;
                        attendanceAlert.className = 'alert alert-success w-100 max-w-500 mb-4 fs-5';
                        
                        // Stop camera before API call to freeze frame
                        stopCamera();
                        
                        // Call API
                        submitAttendance(bestMatch.label);
                    }
                }
            }, 500); // 500ms interval for better performance
        });

    } catch (errorMsg) {
        attendanceAlert.innerText = errorMsg;
        attendanceAlert.className = 'alert alert-danger w-100 max-w-500 mb-4 fs-6 text-start';
        btnStartCamera.disabled = false;
    }
});

btnStopCamera.addEventListener('click', stopCamera);

async function submitAttendance(teacherId) {
    try {
        const formData = new FormData();
        formData.append('teacher_id', teacherId);
        formData.append('latitude', userLat);
        formData.append('longitude', userLon);

        const response = await fetch('api/mark_attendance.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.success) {
            attendanceAlert.innerHTML = `
                <div class="text-success mb-2"><i class="bi bi-check-circle-fill display-4"></i></div>
                <strong>✓ ATTENDANCE MARKED</strong>
                <hr class="my-2">
                <div class="fs-6 text-start">
                    <strong>Name:</strong> ${result.teacher.name}<br>
                    <strong>ID:</strong> ${result.teacher.teacher_id}<br>
                    <strong>Dept:</strong> ${result.teacher.department}<br>
                    <strong>Time:</strong> ${result.time}<br>
                    <strong>Date:</strong> ${result.date}
                </div>
            `;
            attendanceAlert.className = 'alert alert-success w-100 max-w-500 mb-4';
        } else if (result.duplicate) {
            attendanceAlert.innerHTML = `
                <div class="text-warning mb-2"><i class="bi bi-exclamation-circle-fill display-4"></i></div>
                <strong>ATTENDANCE ALREADY MARKED</strong>
                <hr class="my-2">
                <div class="fs-6 text-start">
                    <strong>Name:</strong> ${result.name}<br>
                    ${result.message}
                </div>
            `;
            attendanceAlert.className = 'alert alert-warning w-100 max-w-500 mb-4';
        } else {
            attendanceAlert.innerHTML = `<strong>Error:</strong><br>${result.message}`;
            attendanceAlert.className = 'alert alert-danger w-100 max-w-500 mb-4 fs-6';
        }
    } catch (e) {
        attendanceAlert.innerHTML = `<strong>API Error</strong><br>Failed to connect to server.`;
        attendanceAlert.className = 'alert alert-danger w-100 max-w-500 mb-4 fs-6';
    } finally {
        btnStartCamera.disabled = false;
    }
}
