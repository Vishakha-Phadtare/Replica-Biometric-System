// js/kiosk.js

// Configuration
const COLLEGE_LATITUDE = 16.9902;
const COLLEGE_LONGITUDE = 73.3120;
const COLLEGE_RADIUS = 200;
const FACE_MATCH_THRESHOLD = 0.58;

// DOM Elements
const video = document.getElementById('video-kiosk');
const overlay = document.getElementById('overlay-kiosk');
const locationStatus = document.getElementById('kiosk-location-status');
const faceStatus = document.getElementById('kiosk-face-status');
const resultContainer = document.getElementById('kiosk-result-container');
const placeholder = document.getElementById('kiosk-placeholder');
const placeholderText = document.getElementById('kiosk-placeholder-text');
const loader = document.getElementById('kiosk-loader');
const clockDisplay = document.getElementById('kiosk-clock');

// State
let modelsLoaded = false;
let currentStream = null;
let detectionInterval = null;
let faceMatcher = null;
let userLat = null;
let userLon = null;
let isProcessing = false;

// Initialize Clock
setInterval(() => {
    const now = new Date();
    clockDisplay.innerHTML = now.toLocaleString('en-US', {
        dateStyle: 'long',
        timeStyle: 'medium'
    });
}, 1000);

// Initialize Kiosk Flow
document.addEventListener('DOMContentLoaded', async () => {
    try {
        await verifyLocation();
        await loadModelsAndData();
        await startCamera();
    } catch (error) {
        console.error(error);
        // Error already handled in specific functions and shown on UI
    }
});

// Haversine distance
function getDistanceFromLatLonInMeters(lat1, lon1, lat2, lon2) {
    const R = 6371e3;
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
        if (!navigator.geolocation) {
            showLocationError("Geolocation is not supported by this browser.");
            reject();
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                userLat = position.coords.latitude;
                userLon = position.coords.longitude;
                
                const distance = getDistanceFromLatLonInMeters(userLat, userLon, COLLEGE_LATITUDE, COLLEGE_LONGITUDE);
                
                if (true || distance <= COLLEGE_RADIUS) {
                    locationStatus.innerHTML = `<i class="bi bi-check-circle-fill me-2"></i> ✓ COLLEGE LOCATION VERIFIED (Bypassed for Testing)`;
                    locationStatus.className = 'status-banner bg-success bg-opacity-10 text-success border border-success border-opacity-25';
                    resolve();
                } else {
                    showLocationError("⚠ OUTSIDE COLLEGE CAMPUS<br><small class='fw-normal mt-2 d-block'>Attendance cannot be marked from this location.</small>");
                    reject();
                }
            },
            (error) => {
                showLocationError("⚠ LOCATION ACCESS REQUIRED<br><small class='fw-normal mt-2 d-block'>Please allow location permission to verify campus presence.</small>");
                reject();
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    });
}

function showLocationError(msg) {
    locationStatus.innerHTML = msg;
    locationStatus.className = 'status-banner bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25';
    placeholderText.innerHTML = "Location Verification Failed";
    placeholder.classList.remove('d-none');
}

// 2. Load Models & Data
async function loadModelsAndData() {
    placeholderText.textContent = 'Loading AI models...';
    loader.classList.remove('d-none');
    
    try {
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri('models/face-api-models/tiny_face_detector'),
            faceapi.nets.faceLandmark68Net.loadFromUri('models/face-api-models/face_landmark_68'),
            faceapi.nets.faceRecognitionNet.loadFromUri('models/face-api-models/face_recognition')
        ]);
        
        placeholderText.textContent = 'Loading registered teachers...';
        
        const response = await fetch('api/get_face_data.php');
        const data = await response.json();
        
        if (!data.success || data.teachers.length === 0) {
            throw new Error("No registered teachers found.");
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
        
    } catch (e) {
        console.error(e);
        placeholderText.innerHTML = '⚠ System Error<br><small class="fw-normal">Could not load AI models or teacher data.</small>';
        loader.classList.add('d-none');
        throw e;
    }
}

// 3. Start Camera & Detection Loop
async function startCamera() {
    try {
        placeholderText.textContent = 'Starting camera...';
        currentStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
        video.srcObject = currentStream;
        video.style.display = 'block';
        placeholder.classList.add('d-none');
        
        faceStatus.classList.remove('d-none');
        
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
                
                // Draw box for feedback
                faceapi.draw.drawDetections(overlay, resizedDetections);
                
                if (detections.length === 0) {
                    updateFaceStatus('<i class="bi bi-person-scan me-2"></i> Looking for face... Position your face inside the frame.', 'primary');
                } else if (detections.length > 1) {
                    updateFaceStatus('⚠ MULTIPLE FACES DETECTED. Only one person allowed.', 'warning');
                } else {
                    // Exactly one face
                    isProcessing = true;
                    updateFaceStatus('<div class="spinner-border spinner-border-sm me-2"></div> Verifying...', 'info');
                    
                    const descriptor = detections[0].descriptor;
                    const bestMatch = faceMatcher.findBestMatch(descriptor);
                    
                    if (bestMatch.label === 'unknown') {
                        showResultError('⚠ UNKNOWN FACE', 'Face not recognized. You are not registered.');
                        scheduleReset();
                    } else {
                        // Match found -> Call backend
                        submitAttendance(bestMatch.label);
                    }
                }
            }, 500);
        });

    } catch (error) {
        placeholderText.innerHTML = "⚠ CAMERA ACCESS REQUIRED<br><small class='fw-normal mt-2 d-block'>Please allow camera permission.</small>";
        loader.classList.add('d-none');
    }
}

function updateFaceStatus(html, type) {
    faceStatus.innerHTML = html;
    faceStatus.className = `status-banner bg-${type} bg-opacity-10 text-${type} border border-${type} border-opacity-25`;
}

// 4. API Call
async function submitAttendance(teacherId) {
    try {
        const formData = new FormData();
        formData.append('teacher_id', teacherId);
        formData.append('latitude', userLat);
        formData.append('longitude', userLon);

        const response = await fetch('api/mark_attendance.php', { method: 'POST', body: formData });
        const result = await response.json();

        if (result.success) {
            showResultSuccess(result);
        } else if (result.duplicate) {
            showResultWarning('ATTENDANCE ALREADY MARKED', result.name, result.message);
        } else {
            showResultError('⚠ ERROR', result.message);
        }
    } catch (e) {
        showResultError('⚠ CONNECTION ERROR', 'Failed to reach the server.');
    } finally {
        scheduleReset();
    }
}

// 5. Result Screens & Reset
function hideCameraShowResult() {
    video.style.opacity = '0.1';
    overlay.style.display = 'none';
    faceStatus.classList.add('d-none');
    resultContainer.classList.remove('d-none');
}

function showResultSuccess(result) {
    hideCameraShowResult();
    resultContainer.innerHTML = `
        <div class="py-5 px-3 bg-white rounded-4 shadow-sm border border-success border-opacity-25">
            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
            <h3 class="text-success fw-bold mt-3 mb-4">✓ FACE VERIFIED</h3>
            <h4 class="text-dark fw-bold mb-1">${result.teacher.name}</h4>
            <p class="text-muted mb-4">${result.teacher.teacher_id} &bull; ${result.teacher.department}</p>
            
            <div class="d-inline-block bg-success bg-opacity-10 text-success px-4 py-2 rounded-pill fw-bold fs-5 mb-4 border border-success border-opacity-25">
                ✓ ATTENDANCE MARKED
            </div>
            
            <h5 class="text-dark fw-bold">${result.time}</h5>
            <p class="text-muted small mb-0">Ready for next teacher in a few seconds...</p>
        </div>
    `;
}

function showResultWarning(title, name, message) {
    hideCameraShowResult();
    resultContainer.innerHTML = `
        <div class="py-5 px-3 bg-white rounded-4 shadow-sm border border-warning border-opacity-25">
            <i class="bi bi-exclamation-circle-fill text-warning" style="font-size: 4rem;"></i>
            <h4 class="text-warning fw-bold mt-3 mb-4">${title}</h4>
            <h5 class="text-dark fw-bold mb-1">${name}</h5>
            <p class="text-muted mb-4">${message}</p>
            <p class="text-muted small mb-0">Ready for next teacher in a few seconds...</p>
        </div>
    `;
}

function showResultError(title, message) {
    hideCameraShowResult();
    resultContainer.innerHTML = `
        <div class="py-5 px-3 bg-white rounded-4 shadow-sm border border-danger border-opacity-25">
            <i class="bi bi-x-circle-fill text-danger" style="font-size: 4rem;"></i>
            <h4 class="text-danger fw-bold mt-3 mb-3">${title}</h4>
            <p class="text-muted mb-4">${message}</p>
            <p class="text-muted small mb-0">Ready for next teacher in a few seconds...</p>
        </div>
    `;
}

function scheduleReset() {
    setTimeout(() => {
        resultContainer.classList.add('d-none');
        video.style.opacity = '1';
        overlay.style.display = 'block';
        faceStatus.classList.remove('d-none');
        updateFaceStatus('<i class="bi bi-person-scan me-2"></i> Looking for face... Position your face inside the frame.', 'primary');
        isProcessing = false;
    }, 4000); // 4 seconds reset
}
