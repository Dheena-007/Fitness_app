<?php
// --- api/weekly_summary.php ---

header("Content-Type: application/json");
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) { exit(json_encode(['message' => 'User not logged in.'])); }

$user_id = $_SESSION['user_id'];
$date_7_days_ago = date('Y-m-d', strtotime('-7 days'));
$today = date('Y-m-d');

// 1. Avg Daily Calories
$avg_cal_stmt = $conn->prepare(
    "SELECT AVG(daily_total) as avg_calories FROM 
    (SELECT SUM(calories) as daily_total FROM daily_food_log WHERE user_id = ? AND log_date BETWEEN ? AND ? GROUP BY log_date) as daily_sums"
);
$avg_cal_stmt->bind_param("iss", $user_id, $date_7_days_ago, $today);
$avg_cal_stmt->execute();
$avg_calories = $avg_cal_stmt->get_result()->fetch_assoc()['avg_calories'] ?? 0;

// 2. Avg Daily Water
$avg_water_stmt = $conn->prepare("SELECT AVG(glasses) as avg_glasses FROM daily_water_log WHERE user_id = ? AND log_date BETWEEN ? AND ?");
$avg_water_stmt->bind_param("iss", $user_id, $date_7_days_ago, $today);
$avg_water_stmt->execute();
$avg_glasses = $avg_water_stmt->get_result()->fetch_assoc()['avg_glasses'] ?? 0;

// 3. Weight Change
$weight_start_stmt = $conn->prepare("SELECT weight_kg FROM user_metrics WHERE user_id = ? AND recorded_at <= ? ORDER BY recorded_at DESC LIMIT 1");
$weight_start_stmt->bind_param("is", $user_id, $date_7_days_ago);
$weight_start_stmt->execute();
$start_weight = $weight_start_stmt->get_result()->fetch_assoc()['weight_kg'] ?? null;

$weight_end_stmt = $conn->prepare("SELECT weight_kg FROM user_metrics WHERE user_id = ? ORDER BY recorded_at DESC LIMIT 1");
$weight_end_stmt->bind_param("i", $user_id);
$weight_end_stmt->execute();
$end_weight = $weight_end_stmt->get_result()->fetch_assoc()['weight_kg'] ?? null;

$weight_change = ($start_weight && $end_weight) ? $end_weight - $start_weight : 0;

// 4. Workout Days
$consistency_stmt = $conn->prepare("SELECT COUNT(DISTINCT DATE(recorded_at)) as workout_days FROM user_metrics WHERE user_id = ? AND recorded_at >= ?");
$consistency_stmt->bind_param("is", $user_id, $date_7_days_ago);
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