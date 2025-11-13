<?php
// --- dashboard.php (Final PHP Version) ---

// 1. Load language, session, and database connection FIRST.
require_once __DIR__ . '/config/lang_loader.php';
require_once 'db_connect.php'; // This must be loaded before any database queries.

// 2. Set Page Title (uses $lang variable from loader)
$page_title = $lang['nav_dashboard'];

// 3. Include the Header (must be after lang_loader)
include 'templates/header.php';

// 4. Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 5. Flash Message & Username Logic
$flash_message = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_message']);
$username = htmlspecialchars($_SESSION['username'] ?? 'User');

// 6. Fetch User Profile Data (Age, Gender, Goal)
$user_profile = [];
// This query is now safe because db_connect.php was loaded first
$stmt = $conn->prepare("SELECT age, gender, goal FROM users WHERE id = ?"); 

if ($stmt === false) {
    // This will catch the error if the query fails (e.g., columns still don't exist)
    error_log("Failed to prepare statement: " . $conn->error);
} else {
    $stmt->bind_param("i", $_SESSION['user_id']);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user_profile = $result->fetch_assoc();
        }
    }
    $stmt->close();
}
$conn->close(); // Close connection as it's no longer needed on this page
?>

<div class="container">
    <h2 style="margin-bottom: 1rem;"><?php echo $lang['dashboard_welcome']; ?>, <?php echo $username; ?>!</h2>
    
    <?php if(!empty($flash_message)): ?>
        <div class="alert alert-success"><?php echo $flash_message; ?></div>
    <?php endif; ?>

    <div class="dashboard-grid">
        
        <div class="card">
            <div class="card-header"><i class="fas fa-calendar-week icon"></i><h3>Your Weekly Summary</h3></div>
            <div id="weeklySummaryContent"><p>Loading...</p></div>
        </div>

        <div class="card">
    <div class="card-header"><i class="fas fa-clipboard-list icon"></i><h3><?php echo $lang['log_metrics_title']; ?></h3></div>
    <form id="metricsForm">
        <div class="form-group"><label for="height">Height (cm):</label><input type="number" id="height" required></div>
        <div class="form-group"><label for="weight">Weight (kg):</label><input type="number" id="weight" step="0.1" required></div>
        <div class="form-group"><label for="activity">Activity Level:</label>
            <select id="activity" required>
                <option value="sedentary">Sedentary</option><option value="light">Lightly active</option><option value="moderate">Moderately active</option><option value="active">Active</option><option value="very_active">Very Active</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Save Metrics</button>
    </form>
    <p id="formMessage" style="margin-top: 1rem;"></p>

    <hr>
    <details class="health-instructions">
        <summary>
            <?php echo $lang['health_title']; ?>
        </summary>
        <div class="instruction-content">
            <p><strong><?php echo $lang['health_subtitle']; ?></strong></p>
            <ul>
                <li><i class="fas fa-thermometer-half"></i> <?php echo $lang['health_item_1']; ?></li>
                <li><i class="fas fa-dizzy"></i> <?php echo $lang['health_item_2']; ?></li>
                <li><i class="fas fa-user-injured"></i> <?php echo $lang['health_item_3']; ?></li>
                <li><i class="fas fa-bed"></i> <?php echo $lang['health_item_4']; ?></li>
            </ul>
            <p style="margin-bottom: 0;"><em><?php echo $lang['health_consult']; ?></em></p>
        </div>
    </details>
    </div>
        <div class="card">
            <div class="card-header"><i class="fas fa-chart-line icon"></i><h3><?php echo $lang['weight_progress_title']; ?></h3></div>
            <canvas id="progressChart"></canvas>
        </div>

        <div class="card">
            <div class="card-header"><i class="fas fa-utensils icon"></i><h3><?php echo $lang['calorie_tracker_title']; ?></h3></div>
            <form id="foodLogForm">
                <div class="form-group"><input type="text" id="foodName" placeholder="e.g., Apple" required></div>
                <div class="form-group"><input type="number" id="foodCalories" placeholder="Calories" required></div>
                <button type="submit" class="btn btn-primary">Log Food</button>
            </form>
            <hr>
            <div class="progress-container"><div id="calorieProgressBar" class="progress-bar-fill"></div></div>
            <div id="calorieProgressText" class="progress-text">0 / 0 kcal</div>
            <ul id="foodList" style="list-style: none; padding: 0; margin-top: 1rem;"></ul>
        </div>

        <div class="card">
             <div class="card-header"><i class="fas fa-tint icon"></i><h3><?php echo $lang['water_intake_title']; ?></h3></div>
            <p style="text-align:center; font-size: 1.5rem;"><span id="waterCount">0</span> / 8 glasses</p>
            <button id="addWaterBtn" class="btn btn-primary btn-block">Add a Glass</button>
        </div>

        <div class="card">
            <div class="card-header"><i class="fas fa-brain icon"></i><h3><?php echo $lang['recommendations_title']; ?></h3></div>
            <div id="smartRecommendations">Loading...</div>
        </div>

        <div class="card">
            <div class="card-header"><i class="fas fa-magic icon"></i><h3>AI Predictions & Goals</h3></div>
            
            <div><h5>Weight Prediction</h5><p>Predicted weight in 7 days:</p><p id="predictedWeight" style="font-size: 2rem; font-weight: bold; color: var(--primary-color);">...</p></div>
            <hr>
            
            <form id="calorieGoalForm"><h5>Calorie Goal Calculator</h5>
                <div class="form-group"><label>Your Age:</label><input type="number" id="userAge" value="<?php echo htmlspecialchars($user_profile['age'] ?? ''); ?>" required></div>
                <div class="form-group"><label>Gender:</label><select id="userGender">
                    <option value="male" <?php echo (($user_profile['gender'] ?? '') == 'male') ? 'selected' : ''; ?>>Male</option>
                    <option value="female" <?php echo (($user_profile['gender'] ?? '') == 'female') ? 'selected' : ''; ?>>Female</option>
                </select></div>
                <div class="form-group"><label>Target Weight (kg):</label><input type="number" step="0.1" id="targetWeight" required></div>
                <div class="form-group"><label>Target Date:</label><input type="date" id="targetDate" required></div>
                <button type="submit" class="btn btn-primary">Calculate</button>
            </form><div id="calorieGoalResult"></div>
            <hr>

            <form id="goalForm"><h5>Set Your Primary Goal</h5>
                 <div class="form-group"><select id="userGoal">
                    <option value="maintain" <?php echo (($user_profile['goal'] ?? '') == 'maintain') ? 'selected' : ''; ?>>Maintain Weight</option>
                    <option value="lose_weight" <?php echo (($user_profile['goal'] ?? '') == 'lose_weight') ? 'selected' : ''; ?>>Lose Weight</option>
                    <option value="gain_muscle" <?php echo (($user_profile['goal'] ?? '') == 'gain_muscle') ? 'selected' : ''; ?>>Gain Muscle</option>
                 </select></div>
                 <button type="submit" class="btn btn-primary">Save Goal</button>
            </form><p id="goalMessage"></p>
        </div>

         <div class="card">
            <div class="card-header"><i class="fas fa-bullseye icon"></i><h3>Automatic Daily Calorie Goal</h3></div>
            <div id="autoCalorieGoal" class="stat-value" style="text-align: center;">...</div><p style="text-align: center;">kcal/day</p>
        </div>

        <div class="card">
            <div class="card-header"><i class="fas fa-comment-dots icon"></i><h3>AI Motivational Insight</h3></div>
            <p id="aiInsightText" style="font-style: italic;">Loading...</p>
        </div>

        <div class="card">
            <div class="card-header"><i class="fas fa-robot icon"></i><h3><?php echo $lang['trainer_title']; ?></h3></div>
            <p>Try our AI-powered exercise trainer.</p>
            <a href="exercise.php" class="btn btn-success">Start Training</a>
        </div>

        <div class="card">
            <div class="card-header"><i class="fas fa-search icon"></i><h3>Recipe Finder</h3></div>
            <form id="recipeSearchForm">
                <div class="form-group"><label for="recipeSearchQuery">Search by Keyword:</label><input type="text" id="recipeSearchQuery" placeholder="e.g., Chicken"></div>
                <div class="form-group"><label for="recipeMaxCalories">Max. Calories:</label><input type="number" id="recipeMaxCalories" placeholder="e.g., 500"></div>
                <button type="submit" class="btn btn-primary">Search Recipes</button>
            </form>
            <hr>
            <div id="recipeSearchResults"><p>Search results will appear here.</p></div>
        </div>

    </div> </div> <script>
    const lang_js = {
        error_saving: "<?php echo $lang['js_error_saving']; ?>",
        metrics_saved: "<?php echo $lang['js_metrics_saved']; ?>"
        // Add other JS strings as needed
    };
    // Pass the user profile data fetched by PHP to the JavaScript
    const userProfile = <?php echo json_encode($user_profile); ?>;
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="js/dashboard.js"></script>

<?php include 'templates/footer.php'; ?>