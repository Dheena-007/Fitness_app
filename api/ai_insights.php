<?php
// --- api/ai_insights.php ---

header("Content-Type: application/json");
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) { exit(json_encode(['insight' => 'Please log in.'])); }

$user_id = $_SESSION['user_id'];
$insight = '';

// 1. Check for calorie logging streak
$stmt = $conn->prepare("SELECT COUNT(DISTINCT log_date) as streak FROM daily_food_log WHERE user_id = ? AND log_date >= CURDATE() - INTERVAL 3 DAY");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$streak = $stmt->get_result()->fetch_assoc()['streak'] ?? 0;
if ($streak >= 3) {
    $insight = "You've logged your calories for 3 days in a row. Amazing consistency! Keep it up.";
}

// 2. Check for recent weight loss
if ($insight == '') {
    $stmt = $conn->prepare("SELECT weight_kg FROM user_metrics WHERE user_id = ? ORDER BY recorded_at DESC LIMIT 2");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $weights = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    if (count($weights) == 2 && $weights[0]['weight_kg'] < $weights[1]['weight_kg']) {
        $loss = $weights[1]['weight_kg'] - $weights[0]['weight_kg'];
        $insight = "You've lost " . round($loss, 1) . " kg since your last weigh-in. Fantastic progress!";
    }
}

// 3. Generic tip if no other insight is found
if ($insight == '') {
    $insight = "Staying hydrated is key to success. Have you logged your water intake today?";
}

echo json_encode(['insight' => $insight]);
$conn->close();
?>