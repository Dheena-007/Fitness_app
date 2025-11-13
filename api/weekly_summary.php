<?php
// --- api/weekly_summary.php ---

header("Content-Type: application/json");
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['message' => 'User not logged in.']));
}

$user_id = $_SESSION['user_id'];

// கடந்த 7 நாட்களுக்கான தேதிகளைக் கணக்கிடுதல் (இன்றைய நாள் உட்பட)
$today_date = date('Y-m-d');
$date_6_days_ago_date = date('Y-m-d', strtotime('-6 days')); // 6 நாட்களுக்கு முன்பு

$today_datetime_end = date('Y-m-d 23:59:59');
$date_7_days_ago_start_dt = date('Y-m-d 00:00:00', strtotime('-6 days')); // 7 நாட்கள் (இன்று உட்பட)

// 1. சராசரி தினசரி கலோரிகள்
$avg_cal_stmt = $conn->prepare(
    "SELECT AVG(daily_total) as avg_calories FROM 
    (SELECT SUM(calories) as daily_total FROM daily_food_log WHERE user_id = ? AND log_date BETWEEN ? AND ? GROUP BY log_date) as daily_sums"
);
if ($avg_cal_stmt === false) { die(json_encode(['error' => 'SQL Error (Avg Cal): ' . $conn->error])); }
$avg_cal_stmt->bind_param("iss", $user_id, $date_6_days_ago_date, $today_date);
$avg_cal_stmt->execute();
$avg_calories = $avg_cal_stmt->get_result()->fetch_assoc()['avg_calories'] ?? 0;
$avg_cal_stmt->close();

// 2. சராசரி தினசரி தண்ணீர் உட்கொள்ளல்
$avg_water_stmt = $conn->prepare("SELECT AVG(glasses) as avg_glasses FROM daily_water_log WHERE user_id = ? AND log_date BETWEEN ? AND ?");
if ($avg_water_stmt === false) { die(json_encode(['error' => 'SQL Error (Avg Water): ' . $conn->error])); }
$avg_water_stmt->bind_param("iss", $user_id, $date_6_days_ago_date, $today_date);
$avg_water_stmt->execute();
$avg_glasses = $avg_water_stmt->get_result()->fetch_assoc()['avg_glasses'] ?? 0;
$avg_water_stmt->close();

// 3. எடை மாற்றம் (கடந்த 7 நாட்களில்)
// 7 நாட்களுக்கு முன்பு இருந்த கடைசி எடை
$start_date_query = date('Y-m-d 23:59:59', strtotime('-7 days')); // 7 நாட்களுக்கு முந்தைய நாள்
$weight_start_stmt = $conn->prepare("SELECT weight_kg FROM user_metrics WHERE user_id = ? AND recorded_at <= ? ORDER BY recorded_at DESC LIMIT 1");
if ($weight_start_stmt === false) { die(json_encode(['error' => 'SQL Error (Weight Start): ' . $conn->error])); }
$weight_start_stmt->bind_param("is", $user_id, $start_date_query);
$weight_start_stmt->execute();

// --- பிழை இங்கே சரிசெய்யப்பட்டது ---
$start_weight_res = $weight_start_stmt->get_result(); // $weight_start_Tmt என்பதற்கு பதிலாக $weight_start_stmt
// ------------------------------

$start_weight = ($start_weight_res->num_rows > 0) ? $start_weight_res->fetch_assoc()['weight_kg'] : null;
$weight_start_stmt->close();


// மிக சமீபத்திய எடை
$weight_end_stmt = $conn->prepare("SELECT weight_kg FROM user_metrics WHERE user_id = ? ORDER BY recorded_at DESC LIMIT 1");
if ($weight_end_stmt === false) { die(json_encode(['error' => 'SQL Error (Weight End): ' . $conn->error])); }
$weight_end_stmt->bind_param("i", $user_id);
$weight_end_stmt->execute();
$end_weight_res = $weight_end_stmt->get_result();
$end_weight = ($end_weight_res->num_rows > 0) ? $end_weight_res->fetch_assoc()['weight_kg'] : null;
$weight_end_stmt->close();

$weight_change = 0;
if ($start_weight !== null && $end_weight !== null) {
     $weight_change = $end_weight - $start_weight;
}

// 4. ஒர்க்அவுட் செய்த நாட்கள் (மெட்ரிக்குகளைப் பதிவு செய்த நாட்கள்)
$consistency_stmt = $conn->prepare("SELECT COUNT(DISTINCT DATE(recorded_at)) as workout_days FROM user_metrics WHERE user_id = ? AND recorded_at BETWEEN ? AND ?");
if ($consistency_stmt === false) { die(json_encode(['error' => 'SQL Error (Workout Days): ' . $conn->error])); }
$consistency_stmt->bind_param("iss", $user_id, $date_7_days_ago_start_dt, $today_datetime_end);
$consistency_stmt->execute();
$workout_days = $consistency_stmt->get_result()->fetch_assoc()['workout_days'] ?? 0;
$consistency_stmt->close();

// முடிவுகளை JSON ஆக அனுப்புதல்
echo json_encode([
    'avg_calories' => round($avg_calories),
    'avg_glasses' => round($avg_glasses, 1),
    'weight_change' => round($weight_change, 1),
    'workout_days' => $workout_days
]);

$conn->close();
?>