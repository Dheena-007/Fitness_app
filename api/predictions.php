<?php
// --- api/predictions.php ---

header("Content-Type: application/json");
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['message' => 'User not logged in.']));
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

// BMR மற்றும் TDEE கணக்கிடும் பொதுவான செயல்பாடுகள்
function calculate_tdee($conn, $user_id) {
    $stmt = $conn->prepare("SELECT age, gender, goal FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_data = $stmt->get_result()->fetch_assoc();
    if (!$user_data || !$user_data['age']) return ['error' => 'Please set Age and Gender first.'];

    $stmt = $conn->prepare("SELECT weight_kg, height_cm, activity_level FROM user_metrics WHERE user_id = ? ORDER BY recorded_at DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $metrics = $stmt->get_result()->fetch_assoc();
    if (!$metrics) return ['error' => 'Please log your metrics first.'];

    $bmr = 0;
    if ($user_data['gender'] == 'male') {
        $bmr = (10 * $metrics['weight_kg']) + (6.25 * $metrics['height_cm']) - (5 * $user_data['age']) + 5;
    } else {
        $bmr = (10 * $metrics['weight_kg']) + (6.25 * $metrics['height_cm']) - (5 * $user_data['age']) - 161;
    }
    
    $activity_factors = ['sedentary' => 1.2, 'light' => 1.375, 'moderate' => 1.55, 'active' => 1.725, 'very_active' => 1.9];
    $tdee = round($bmr * ($activity_factors[$metrics['activity_level']] ?? 1.2));

    return ['tdee' => $tdee, 'goal' => $user_data['goal']];
}

switch ($action) {
    case 'predict_weight':
        // ... (PHP இல் Linear Regression கணக்கீடு செய்வது சிக்கலானது, ஒரு எளிய போக்கைக் காட்டலாம்)
        $stmt = $conn->prepare("SELECT weight_kg FROM user_metrics WHERE user_id = ? ORDER BY recorded_at DESC LIMIT 2");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $weights = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        if (count($weights) < 2) {
            echo json_encode(['predicted_weight' => null, 'message' => 'Not enough data.']);
            break;
        }
        $trend = $weights[0]['weight_kg'] - $weights[1]['weight_kg'];
        $predicted_weight = $weights[0]['weight_kg'] + $trend; // மிகவும் எளிமையான கணிப்பு
        echo json_encode(['predicted_weight' => round($predicted_weight, 1)]);
        break;

    case 'calculate_calories':
        $data = json_decode(file_get_contents('php://input'), true);
        
        // வயது, பாலினம், இலக்கு ஆகியவற்றை 'users' டேபிளில் சேமித்தல்
        $stmt = $conn->prepare("UPDATE users SET age = ?, gender = ?, goal = ? WHERE id = ?");
        $goal = $data['goal'] ?? 'maintain';
        $stmt->bind_param("issi", $data['age'], $data['gender'], $goal, $user_id);
        $stmt->execute();

        if (!isset($data['target_weight'])) { // வெறும் சுயவிவரத்தை (profile) மட்டும் அப்டேட் செய்தால்
            echo json_encode(['message' => 'Profile updated.']);
            break;
        }

        $calc = calculate_tdee($conn, $user_id);
        if (isset($calc['error'])) {
            echo json_encode($calc);
            break;
        }
        $tdee = $calc['tdee'];
        
        $metrics_stmt = $conn->prepare("SELECT weight_kg FROM user_metrics WHERE user_id = ? ORDER BY recorded_at DESC LIMIT 1");
        $metrics_stmt->bind_param("i", $user_id);
        $metrics_stmt->execute();
        $current_weight = $metrics_stmt->get_result()->fetch_assoc()['weight_kg'];
        
        $weight_change_kg = $data['target_weight'] - $current_weight;
        $total_calorie_change = $weight_change_kg * 7700;
        $days_to_target = (new DateTime($data['target_date']))->diff(new DateTime())->days;
        
        if ($days_to_target <= 0) {
            echo json_encode(['error' => 'Target date must be in the future.']);
            break;
        }
        
        $daily_calorie_adjustment = $total_calorie_change / $days_to_target;
        $calorie_goal = round($tdee + $daily_calorie_adjustment);
        echo json_encode(['calorie_goal' => $calorie_goal]);
        break;

    case 'get_auto_calorie_goal':
        $calc = calculate_tdee($conn, $user_id);
        if (isset($calc['error'])) {
            echo json_encode($calc);
            break;
        }
        $tdee = $calc['tdee'];
        $goal = $calc['goal'];
        
        $auto_goal = $tdee;
        if ($goal == 'lose_weight') $auto_goal -= 500;
        elseif ($goal == 'gain_muscle') $auto_goal += 300;
        
        echo json_encode(['auto_calorie_goal' => $auto_goal]);
        break;
}
$conn->close();
?>
<?php
// ... (inside api/predictions.php) ...

// This helper function (or similar logic) should be available in the file
function calculate_tdee($conn, $user_id) {
    $stmt = $conn->prepare("SELECT age, gender, goal FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_data = $stmt->get_result()->fetch_assoc();
    if (!$user_data || !$user_data['age']) return ['error' => 'Please set Age and Gender first.'];

    $stmt = $conn->prepare("SELECT weight_kg, height_cm, activity_level FROM user_metrics WHERE user_id = ? ORDER BY recorded_at DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $metrics = $stmt->get_result()->fetch_assoc();
    if (!$metrics) return ['error' => 'Please log your metrics first.'];

    $bmr = 0;
    if ($user_data['gender'] == 'male') {
        $bmr = (10 * $metrics['weight_kg']) + (6.25 * $metrics['height_cm']) - (5 * $user_data['age']) + 5;
    } else {
        $bmr = (10 * $metrics['weight_kg']) + (6.25 * $metrics['height_cm']) - (5 * $user_data['age']) - 161;
    }
    
    $activity_factors = ['sedentary' => 1.2, 'light' => 1.375, 'moderate' => 1.55, 'active' => 1.725, 'very_active' => 1.9];
    $tdee = round($bmr * ($activity_factors[$metrics['activity_level']] ?? 1.2));

    return ['tdee' => $tdee, 'goal' => $user_data['goal']];
}

// ... (inside the 'switch ($action)' block) ...

case 'get_auto_calorie_goal':
    $calc = calculate_tdee($conn, $user_id);
    if (isset($calc['error'])) {
        echo json_encode($calc);
        break;
    }
    $tdee = $calc['tdee'];
    $goal = $calc['goal'];
    
    $auto_goal = $tdee;
    if ($goal == 'lose_weight') $auto_goal -= 500;
    elseif ($goal == 'gain_muscle') $auto_goal += 300;
    
    echo json_encode(['auto_calorie_goal' => $auto_goal]);
    break;

// ... (rest of the file) ...
?>