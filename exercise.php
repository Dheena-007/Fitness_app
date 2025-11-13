<?php
// --- exercise.php (Final PHP Version) ---

// 1. Load language, session, and database connection
require_once __DIR__ . '/config/lang_loader.php';
require_once 'db_connect.php'; 

// 2. Set Page Title
$page_title = $lang['trainer_page_title'];

// 3. Include the Header
include 'templates/header.php';

// 4. Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<div class="container">
    <div style="text-align: center; margin-bottom: 2rem;">
        <h1><?php echo $lang['trainer_page_title']; ?></h1>
        <p><?php echo $lang['trainer_subtitle']; ?></p>
        
        <div class="form-group" style="max-width: 300px; margin: 1rem auto;">
            <label for="exerciseSelect" style="font-weight: bold;"><?php echo $lang['choose_exercise']; ?></label>
            <select id="exerciseSelect" class="form-control">
                <option value="squat"><?php echo $lang['squats']; ?></option>
                <option value="bicep_curl"><?php echo $lang['bicep_curls']; ?></option>
                <option value="overhead_press"><?php echo $lang['overhead_press']; ?></option>
            </select>
        </div>

        <div id="buttonContainer">
            <button id="startBtn" class="btn btn-success"><?php echo $lang['start_camera']; ?></button>
            <button id="stopBtn" class="btn btn-danger" style="display: none;"><?php echo $lang['stop_camera']; ?></button>
        </div>
    </div>

    <div class="exercise-container">
        <div class="video-wrapper">
            <video id="webcam" autoplay playsinline></video>
            <canvas id="outputCanvas"></canvas>
        </div>
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-label"><i class="fas fa-hashtag icon"></i><?php echo $lang['rep_count']; ?></div>
                <div id="counter" class="stat-value">0</div>
            </div>
            <div class="stat-card">
                <div class="stat-label"><i class="fas fa-comment-dots icon"></i><?php echo $lang['feedback']; ?></div>
                <div id="feedback" class="stat-value" style="font-size: 1.8rem; height: 60px;"><?php echo $lang['js_ready']; ?></div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils/camera_utils.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/@mediapipe/drawing_utils/drawing_utils.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/@mediapipe/pose/pose.js" crossorigin="anonymous"></script>

<script>
    const lang_js = {
        ready: "<?php echo $lang['js_ready']; ?>",
        loading: "<?php echo $lang['js_loading']; ?>",
        go_lower: "<?php echo $lang['js_go_lower']; ?>",
        great_depth: "<?php echo $lang['js_great_depth']; ?>",
        lift_higher: "<?php echo $lang['js_lift_higher']; ?>",
        good_curl: "<?php echo $lang['js_good_curl']; ?>",
        press_higher: "<?php echo $lang['js_press_higher']; ?>",
        good_press: "<?php echo $lang['js_good_press']; ?>"
    };
</script>

<script type="module" src="js/exercise.js"></script>

<?php include 'templates/footer.php'; ?>