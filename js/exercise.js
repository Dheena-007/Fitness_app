// js/exercise.js (Final Version)

document.addEventListener('DOMContentLoaded', function() {
    const videoElement = document.getElementById('webcam');
    const canvasElement = document.getElementById('outputCanvas');
    const feedbackElement = document.getElementById('feedback');
    const counterElement = document.getElementById('counter');
    const startBtn = document.getElementById('startBtn');
    const stopBtn = document.getElementById('stopBtn');
    const exerciseSelect = document.getElementById('exerciseSelect');

    const canvasCtx = canvasElement.getContext('2d');
    let camera = null;
    let stage = null;
    let counter = 0;
    let currentExercise = 'squat';

    function stopCamera() {
        if (camera) {
            camera.stop();
            if (videoElement.srcObject) {
                videoElement.srcObject.getTracks().forEach(track => track.stop());
            }
        }
        startBtn.style.display = 'inline-block';
        stopBtn.style.display = 'none';
        exerciseSelect.disabled = false;
        feedbackElement.textContent = lang_js.ready;
        counterElement.textContent = "0";
        counter = 0;
        stage = null;
        canvasCtx.clearRect(0, 0, canvasElement.width, canvasElement.height);
    }

    startBtn.addEventListener('click', () => {
        currentExercise = exerciseSelect.value;
        exerciseSelect.disabled = true;
        feedbackElement.textContent = lang_js.loading;
        startBtn.disabled = true;

        camera = new Camera(videoElement, {
            onFrame: async () => {
                if (videoElement.readyState >= 3) await pose.send({ image: videoElement });
            },
            width: 640,
            height: 480
        });
        camera.start();
        
        feedbackElement.textContent = lang_js.ready;
        startBtn.style.display = 'none';
        stopBtn.style.display = 'inline-block';
        startBtn.disabled = false;
    });

    stopBtn.addEventListener('click', stopCamera);

    function calculateAngle(a, b, c) {
        const radians = Math.atan2(c.y - b.y, c.x - b.x) - Math.atan2(a.y - b.y, a.x - b.x);
        let angle = Math.abs(radians * 180.0 / Math.PI);
        if (angle > 180.0) angle = 360 - angle;
        return angle;
    }

    function analyzeSquat(landmarks) {
        const hip = landmarks[23], knee = landmarks[25], ankle = landmarks[27];
        if (!hip || !knee || !ankle) return; 
        const kneeAngle = calculateAngle(hip, knee, ankle);
        if (kneeAngle < 100) stage = "down";
        if (kneeAngle > 160 && stage === 'down') {
            stage = "up";
            counter++;
        }
        feedbackElement.textContent = (stage === 'down' && hip.y > knee.y) ? lang_js.go_lower : lang_js.great_depth;
    }

    function analyzeBicepCurl(landmarks) {
        const shoulder = landmarks[11], elbow = landmarks[13], wrist = landmarks[15];
        if (!shoulder || !elbow || !wrist) return;
        const elbowAngle = calculateAngle(shoulder, elbow, wrist);
        if (elbowAngle < 40) stage = "up";
        if (elbowAngle > 160 && stage === 'up') {
            stage = "down";
            counter++;
        }
        feedbackElement.textContent = (stage === 'up' && elbowAngle > 50) ? lang_js.lift_higher : lang_js.good_curl;
    }

    function analyzeOverheadPress(landmarks) {
        const shoulder = landmarks[11], elbow = landmarks[13], wrist = landmarks[15];
        if (!shoulder || !elbow || !wrist) return;
        const elbowAngle = calculateAngle(shoulder, elbow, wrist);
        if (elbowAngle > 160 && shoulder.y > elbow.y) stage = "up";
        if (elbowAngle < 90 && stage === 'up') {
            stage = "down";
            counter++;
        }
        feedbackElement.textContent = (stage === 'up' && elbowAngle < 150) ? lang_js.press_higher : lang_js.good_press;
    }

    function onResults(results) {
        canvasElement.width = videoElement.videoWidth;
        canvasElement.height = videoElement.videoHeight;
        canvasCtx.clearRect(0, 0, canvasElement.width, canvasElement.height);

        if (results.poseLandmarks) {
            drawConnectors(canvasCtx, results.poseLandmarks, POSE_CONNECTIONS, { color: '#2ecc71', lineWidth: 4 });
            drawLandmarks(canvasCtx, results.poseLandmarks, { color: '#e74c3c', lineWidth: 2 });
            try {
                switch (currentExercise) {
                    case 'squat': analyzeSquat(results.poseLandmarks); break;
                    case 'bicep_curl': analyzeBicepCurl(results.poseLandmarks); break;
                    case 'overhead_press': analyzeOverheadPress(results.poseLandmarks); break;
                }
                counterElement.textContent = counter;
            } catch (error) { console.error("Error during pose analysis:", error); }
        }
    }
    
    const pose = new Pose({ locateFile: (file) => `https://cdn.jsdelivr.net/npm/@mediapipe/pose/${file}` });
    pose.setOptions({ modelComplexity: 1, smoothLandmarks: true, minDetectionConfidence: 0.5, minTrackingConfidence: 0.5 });
    pose.onResults(onResults);
});