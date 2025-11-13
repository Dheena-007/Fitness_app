<?php
// --- api/smart_recommendations.php ---

header("Content-Type: application/json");
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) { exit(json_encode(['error' => 'Not logged in'])); }

$user_id = $_SESSION['user_id'];

// 1. TDEE மற்றும் இலக்கைப் பெறவும் (predictions.php இலிருந்து தர்க்கத்தைப் பயன்படுத்துதல்)
include 'predictions_helper.php'; // (predictions.php இல் உள்ள calculate_tdee செயல்பாட்டை ஒரு தனி கோப்பிற்கு நகர்த்தவும்)
$calc = calculate_tdee($conn, $user_id);
if (isset($calc['error'])) { exit(json_encode($calc)); }
$tdee = $calc['tdee'];
$goal = $calc['goal'];

// 2. இன்றைய கலோரி நுகர்வைக் கணக்கிடவும்
$today = date("Y-m-d");
$log_stmt = $conn->prepare("SELECT SUM(calories) as total_calories FROM daily_food_log WHERE user_id = ? AND log_date = ?");
$log_stmt->bind_param("is", $user_id, $today);
$log_stmt->execute();
$calories_today = $log_stmt->get_result()->fetch_assoc()['total_calories'] ?? 0;
$remaining_calories = $tdee - $calories_today;

// 3. உணவுப் பரிந்துரைகளைப் பெறவும்
$min_cal = ($remaining_calories / 2) - 150;
$max_cal = ($remaining_calories / 2) + 150;
$food_stmt = $conn->prepare("SELECT recipe_name, calories_per_serving FROM recipes WHERE calories_per_serving BETWEEN ? AND ? ORDER BY RAND() LIMIT 2");
$food_stmt->bind_param("ii", $min_cal, $max_cal);
$food_stmt->execute();
$food_suggestions = $food_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 4. உடற்பயிற்சித் திட்டத்தை உருவாக்கவும்
$exercise_plans = [
    'lose_weight' => ['focus' => 'Fat Loss', 'plan' => 'Focus on 3 days of HIIT and 2 days of full-body strength training.'],
    'gain_muscle' => ['focus' => 'Muscle Gain', 'plan' => 'Focus on 4 days of strength training (e.g., Push/Pull/Legs).'],
    'maintain' => ['focus' => 'Maintenance', 'plan' => 'Mix 2 days of strength training with 2 days of moderate cardio.']
];
$exercise_plan = $exercise_plans[$goal] ?? $exercise_plans['maintain'];

echo json_encode([
    'calorie_analysis' => ['goal' => $tdee, 'consumed' => $calories_today, 'remaining' => $remaining_calories],
    'food_suggestions' => $food_suggestions,
    'exercise_plan' => $exercise_plan
]);
$conn->close();
?>