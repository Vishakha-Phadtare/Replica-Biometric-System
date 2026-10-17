(function() {
// js/face-register.js
let video = document.getElementById('video');
let overlay = document.getElementById('overlay');
let btnStartCamera = document.getElementById('btnStartCamera');
let btnCapture = document.getElementById('btnCapture');
let btnRegister = document.getElementById('btnRegister');
let faceStatus = document.getElementById('faceStatus');
let formAlert = document.getElementById('formAlert');
let faceDescriptorInput = document.getElementById('face_descriptor');
let loadingModels = document.getElementById('loading-models');
let cameraPlaceholder = document.getElementById('camera-placeholder');

let modelsLoaded = false;
let currentStream = null;
let detectionInterval;

// Load models
async function loadModels() {
    try {
        loadingModels.classList.remove('d-none');
        cameraPlaceholder.classList.add('d-none');
        
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri('models/face-api-models/tiny_face_detector'),
            faceapi.nets.faceLandmark68Net.loadFromUri('models/face-api-models/face_landmark_68'),
            faceapi.nets.faceRecognitionNet.loadFromUri('models/face-api-models/face_recognition')
        ]);
        
        modelsLoaded = true;
        loadingModels.classList.add('d-none');
        cameraPlaceholder.classList.remove('d-none');
        faceStatus.textContent = 'Models loaded. Ready to start camera.';
    } catch (e) {
        console.error(e);
        faceStatus.textContent = 'Face recognition models could not be loaded. Please check the models folder.';
        faceStatus.classList.replace('alert-secondary', 'alert-danger');
        loadingModels.classList.add('d-none');
    }
}

// Start Camera
if(btnStartCamera) {
    btnStartCamera.addEventListener('click', async () => {
        if (!modelsLoaded) {
            alert("Models are still loading or failed to load.");
            return;
        }
        
        try {
            currentStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
            video.srcObject = currentStream;
            video.style.display = 'block';
            cameraPlaceholder.classList.add('d-none');
            
            btnStartCamera.disabled = true;
            btnCapture.disabled = false;
            faceStatus.textContent = 'Camera active. Position your face in the center.';
            faceStatus.classList.replace('alert-danger', 'alert-primary');
            faceStatus.classList.replace('alert-secondary', 'alert-primary');
            
            // Setup canvas
            video.addEventListener('play', () => {
                const displaySize = { width: video.videoWidth, height: video.videoHeight };
                faceapi.matchDimensions(overlay, displaySize);
                
                detectionInterval = setInterval(async () => {
                    const detections = await faceapi.detectAllFaces(video, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks();
                    const resizedDetections = faceapi.resizeResults(detections, displaySize);
                    
                    const ctx = overlay.getContext('2d');
                    ctx.clearRect(0, 0, overlay.width, overlay.height);
                    faceapi.draw.drawDetections(overlay, resizedDetections);
                }, 100);
            });
            
        } catch (e) {
            console.error(e);
            faceStatus.textContent = 'Unable to access camera. Please allow camera permission and try again.';
            faceStatus.classList.replace('alert-secondary', 'alert-danger');
            faceStatus.classList.replace('alert-primary', 'alert-danger');
        }
    });
}

// Capture Face
if(btnCapture) {
    btnCapture.addEventListener('click', async () => {
        faceStatus.textContent = 'Detecting...';
        
        const detections = await faceapi.detectAllFaces(video, new faceapi.TinyFaceDetectorOptions())
                                        .withFaceLandmarks()
                                        .withFaceDescriptors();
        
        if (detections.length === 0) {
            faceStatus.textContent = 'No face detected. Please position your face inside the camera.';
            faceStatus.classList.replace('alert-primary', 'alert-danger');
        } else if (detections.length > 1) {
            faceStatus.textContent = 'Multiple faces detected. Only one person should be in front of the camera.';
            faceStatus.classList.replace('alert-primary', 'alert-danger');
        } else {
            const descriptor = Array.from(detections[0].descriptor);
            faceDescriptorInput.value = JSON.stringify(descriptor);
            
            faceStatus.textContent = 'Face detected ✓ Face captured ✓';
            faceStatus.classList.replace('alert-primary', 'alert-success');
            faceStatus.classList.replace('alert-danger', 'alert-success');
            
            // Stop camera to save resources
            if (currentStream) {
                currentStream.getTracks().forEach(track => track.stop());
                clearInterval(detectionInterval);
            }
            
            btnCapture.disabled = true;
            btnStartCamera.disabled = false;
            btnStartCamera.textContent = 'Retake';
            btnRegister.disabled = false;
        }
    });
}

// Form Submission
const registerForm = document.getElementById('registerForm');
if(registerForm) {
    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        if (!faceDescriptorInput.value) {
            formAlert.textContent = 'Please capture a face first.';
            formAlert.classList.remove('d-none', 'alert-success');
            formAlert.classList.add('alert-danger');
            return;
        }

        const formData = new FormData(e.target);
        
        // Check if this is a re-registration (handled via URL parameter or hidden input)
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('update')) {
            formData.append('is_update', '1');
        }

        btnRegister.disabled = true;
        btnRegister.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';

        try {
            const response = await fetch('api/save_teacher.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            formAlert.classList.remove('d-none');
            if (result.success) {
                formAlert.textContent = result.message;
                formAlert.classList.remove('alert-danger');
                formAlert.classList.add('alert-success');
                
                if (!urlParams.has('update')) {
                    e.target.reset();
                    faceDescriptorInput.value = '';
                    btnRegister.disabled = true;
                    faceStatus.textContent = 'Face registered successfully ✓';
                    faceStatus.classList.replace('alert-success', 'alert-secondary');
                } else {
                    setTimeout(() => window.location.href = 'teachers.php', 1500);
                }
            } else {
                formAlert.textContent = result.message;
                formAlert.classList.remove('alert-success');
                formAlert.classList.add('alert-danger');
            }
        } catch (error) {
            formAlert.textContent = 'An error occurred. Please try again.';
            formAlert.classList.remove('d-none', 'alert-success');
            formAlert.classList.add('alert-danger');
        } finally {
            btnRegister.disabled = false;
            btnRegister.innerHTML = '<i class="bi bi-person-plus me-2"></i> Register Teacher';
        }
    });
}

function initFaceRegister() {
    loadModels();
    
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('update')) {
        const id = urlParams.get('update');
        fetch(`api/get_teachers.php?id=${id}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.teachers.length > 0) {
                    const teacher = data.teachers[0];
                    document.getElementById('teacher_id').value = teacher.teacher_id;
                    document.getElementById('teacher_id').readOnly = true;
                    document.getElementById('name').value = teacher.name;
                    document.getElementById('name').readOnly = true;
                    document.getElementById('department').value = teacher.department;
                    document.getElementById('department').disabled = true;
                    
                    const deptHidden = document.createElement('input');
                    deptHidden.type = 'hidden';
                    deptHidden.name = 'department';
                    deptHidden.value = teacher.department;
                    document.getElementById('registerForm').appendChild(deptHidden);

                    const nameHidden = document.createElement('input');
                    nameHidden.type = 'hidden';
                    nameHidden.name = 'name';
                    nameHidden.value = teacher.name;
                    document.getElementById('registerForm').appendChild(nameHidden);

                    if (teacher.email) {
                        document.getElementById('email').value = teacher.email;
                        document.getElementById('email').readOnly = true;
                    }
                    if (teacher.phone) {
                        document.getElementById('phone').value = teacher.phone;
                        document.getElementById('phone').readOnly = true;
                    }
                    
                    document.querySelector('.h2').textContent = 'Re-register Face';
                    btnRegister.innerHTML = '<i class="bi bi-camera me-2"></i> Update Face';
                }
            })
            .catch(e => console.error(e));
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFaceRegister);
} else {
    initFaceRegister();
}
})();
