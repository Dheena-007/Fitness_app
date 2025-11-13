<?php
// --- api/weekly_summary.php ---

header("Content-Type: application/json");
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) { exit(json_encode(['message' => 'User not logged in.'])); }

$user_id = $_SESSION['user_id'];
$date_7_days_ago = date('Y-m-d', strtotime('-7 days'));
$today = date('Y-m-d');

// 1. சராசரி தினசரி கலோரிகள்
$avg_cal_stmt = $conn->prepare(
    "SELECT AVG(daily_total) as avg_calories FROM 
    (SELECT SUM(calories) as daily_total FROM daily_food_log WHERE user_id = ? AND log_date BETWEEN ? AND ? GROUP BY log_date) as daily_sums"
);
$avg_cal_stmt->bind_param("iss", $user_id, $date_7_days_ago, $today);
$avg_cal_stmt->execute();
$avg_calories = $avg_cal_stmt->get_result()->fetch_assoc()['avg_calories'] ?? 0;

// 2. சராசரி தினசரி தண்ணீர் உட்கொள்ளல்
$avg_water_stmt = $conn->prepare("SELECT AVG(glasses) as avg_glasses FROM daily_water_log WHERE user_id = ? AND log_date BETWEEN ? AND ?");
$avg_water_stmt->bind_param("iss", $user_id, $date_7_days_ago, $today);
$avg_water_stmt->execute();
$avg_glasses = $avg_water_stmt->get_result()->fetch_assoc()['avg_glasses'] ?? 0;

// 3. எடை மாற்றம்
// (எளிய தீர்வு: இந்த வாரத்தின் முதல் மற்றும் கடைசி பதிவை ஒப்பிடுகிறது)
$weight_stmt = $conn->prepare("SELECT weight_kg FROM user_metrics WHERE user_id = ? AND recorded_at BETWEEN ? AND ? ORDER BY recorded_at ASC");
$weight_stmt->bind_param("iss", $user_id, $date_7_days_ago, $today);
$weight_stmt->execute();
$weights = $weight_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$weight_change = 0;
if (count($weights) >= 2) {
    $weight_change = end($weights)['weight_kg'] - $weights[0]['weight_kg'];
}

// 4. ஒர்க்அவுட் செய்த நாட்கள் (மெட்ரிக்குகளைப் பதிவு செய்த நாட்கள்)
$consistency_stmt = $conn->prepare("SELECT COUNT(DISTINCT DATE(recorded_at)) as workout_days FROM user_metrics WHERE user_id = ? AND recorded_at BETWEEN ? AND ?");
$consistency_stmt->bind_param("iss", $user_id, $date_7_days_ago, $today);
$consistency_stmt->execute();
$workout_days = $consistency_stmt->get_result()->fetch_assoc()['workout_days'] ?? 0;

echo json_encode([
    'avg_calories' => round($avg_calories),
    'avg_glasses' => round($avg_glasses, 1),
    'weight_change' => round($weight_change, 1),
    'workout_days' => $workout_days
]);
$conn->close();
?>