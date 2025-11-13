<?php
// --- api/smart_recommendations.php ---

header("Content-Type: application/json");
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) { exit(json_encode(['error' => 'Not logged in'])); }

$user_id = $_SESSION['user_id'];

// 1. Get TDEE and Goal (This logic is duplicated from predictions.php for simplicity)
$stmt = $conn->prepare("SELECT age, gender, goal FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
if (!$user_data || !$user_data['age']) { exit(json_encode(['error' => 'Please set Age and Gender first.'])); }

$stmt = $conn->prepare("SELECT weight_kg, height_cm, activity_level FROM user_metrics WHERE user_id = ? ORDER BY recorded_at DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$metrics = $stmt->get_result()->fetch_assoc();
if (!$metrics) { exit(json_encode(['error' => 'Please log your metrics first.'])); }

$bmr = 0;
if ($user_data['gender'] == 'male') {
    $bmr = (10 * $metrics['weight_kg']) + (6.25 * $metrics['height_cm']) - (5 * $user_data['age']) + 5;
} else {
    $bmr = (10 * $metrics['weight_kg']) + (6.25 * $metrics['height_cm']) - (5 * $user_data['age']) - 161;
}
$activity_factors = ['sedentary' => 1.2, 'light' => 1.375, 'moderate' => 1.55, 'active' => 1.725, 'very_active' => 1.9];
$tdee = round($bmr * ($activity_factors[$metrics['activity_level']] ?? 1.2));
$goal = $user_data['goal'];
// --------------------

// 2. Get Today's Calorie Intake
$today = date("Y-m-d");
$log_stmt = $conn->prepare("SELECT SUM(calories) as total_calories FROM daily_food_log WHERE user_id = ? AND log_date = ?");
$log_stmt->bind_param("is", $user_id, $today);
$log_stmt->execute();
$calories_today = $log_stmt->get_result()->fetch_assoc()['total_calories'] ?? 0;
$remaining_calories = $tdee - $calories_today;

// 3. Get Food Suggestions
$min_cal = ($remaining_calories / 2) - 150; // Suggest meal for half of remaining
$max_cal = ($remaining_calories / 2) + 150;
$food_stmt = $conn->prepare("SELECT recipe_name, calories_per_serving FROM recipes WHERE calories_per_serving BETWEEN ? AND ? ORDER BY RAND() LIMIT 2");
$food_stmt->bind_param("ii", $min_cal, $max_cal);
$food_stmt->execute();
$food_suggestions = $food_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 4. Get Exercise Plan
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