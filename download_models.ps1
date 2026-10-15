$repo = "https://raw.githubusercontent.com/justadudewhohacks/face-api.js/master/weights/"
$models = @(
    "tiny_face_detector_model-weights_manifest.json",
    "tiny_face_detector_model-shard1",
    "face_landmark_68_model-weights_manifest.json",
    "face_landmark_68_model-shard1",
    "face_recognition_model-weights_manifest.json",
    "face_recognition_model-shard1",
    "face_recognition_model-shard2"
)

# Download face-api.min.js
Invoke-WebRequest -Uri "https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js" -OutFile "teacher-attendance/js/face-api.min.js"

foreach ($file in $models) {
    $url = $repo + $file
    if ($file -match "^tiny_face_detector") {
        $dest = "teacher-attendance/models/face-api-models/tiny_face_detector/" + $file
    } elseif ($file -match "^face_landmark_68") {
        $dest = "teacher-attendance/models/face-api-models/face_landmark_68/" + $file
    } elseif ($file -match "^face_recognition") {
        $dest = "teacher-attendance/models/face-api-models/face_recognition/" + $file
    }
    
    Write-Host "Downloading $file to $dest"
    try {
        Invoke-WebRequest -Uri $url -OutFile $dest
    } catch {
        Write-Host "Error downloading $file"
    }
}
