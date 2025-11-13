<?php
// --- Generates AI-based diet and workout plans ---

header("Content-Type: application/json");
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['message' => 'User not logged in.']);
    exit();
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT bmi FROM user_metrics WHERE user_id = ? ORDER BY recorded_at DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $latest_metric = $result->fetch_assoc();
    $bmi = $latest_metric['bmi'];
    $workout_plan = [];
    $diet_plan = [];

    if ($bmi < 18.5) { // Underweight
        $diet_plan = ['goal' => 'Gain Weight (Calorie Surplus)', 'breakfast' => 'Oatmeal with nuts', 'lunch' => 'Chicken breast, brown rice', 'dinner' => 'Salmon, quinoa'];
        $workout_plan = ['focus' => 'Strength Training', 'monday' => 'Full Body Strength', 'wednesday' => 'Full Body Strength', 'friday' => 'Full Body Strength'];
    } elseif ($bmi >= 18.5 && $bmi <= 24.9) { // Normal
        $diet_plan = ['goal' => 'Maintain Weight (Balanced Diet)', 'breakfast' => 'Greek yogurt with berries', 'lunch' => 'Salad with grilled turkey', 'dinner' => 'Lean steak with sweet potato'];
        $workout_plan = ['focus' => 'Overall Fitness', 'monday' => 'Cardio (30 mins)', 'tuesday' => 'Upper Body Strength', 'thursday' => 'Lower Body Strength'];
    } else { // Overweight
        $diet_plan = ['goal' => 'Lose Weight (Calorie Deficit)', 'breakfast' => 'Scrambled eggs with spinach', 'lunch' => 'Lentil soup', 'dinner' => 'Baked cod with asparagus'];
        $workout_plan = ['focus' => 'Fat Loss and Cardio', 'monday' => 'HIIT', 'tuesday' => 'Full Body Strength', 'wednesday' => 'Cardio (45 mins)'];
    }
    echo json_encode(['workout' => $workout_plan, 'diet' => $diet_plan]);
} else {
    echo json_encode(['message' => 'No metrics found. Please log your metrics first.']);
}
$stmt->close();
$conn->close();
?>