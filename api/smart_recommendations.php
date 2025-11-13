<?php
// --- api/smart_recommendations.php (சரிசெய்யப்பட்ட கோட்) ---

header("Content-Type: application/json");
session_start();
require_once '../db_connect.php'; // டேட்டாபேஸ் இணைப்பு

// 1. பயனர் உள்நுழைந்துள்ளாரா எனச் சரிபார்க்கவும்
if (!isset($_SESSION['user_id'])) { 
    http_response_code(401);
    exit(json_encode(['error' => 'User not logged in.'])); 
}
$user_id = $_SESSION['user_id'];

/**
 * TDEE (Total Daily Energy Expenditure) ஐக் கணக்கிடும் ஒரு ஹெல்பர் செயல்பாடு.
 * இது வயது, பாலினம், மெட்ரிக்குகள் உள்ளதா எனச் சரிபார்க்கும்.
 */
function get_user_tdee_and_goal($conn, $user_id) {
    // 2. பயனரின் சுயவிவரத்தைப் (profile) பெறுகிறது (வயது, பாலினம், இலக்கு)
    $stmt = $conn->prepare("SELECT age, gender, goal FROM users WHERE id = ?");
    if ($stmt === false) { return ['error' => 'SQL Error (users table): ' . $conn->error]; }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // **முக்கிய பிழை சரிபார்ப்பு: பயனர் வயது/பாலினத்தை அமைக்கவில்லை என்றால்**
    if (!$user_data || !$user_data['age'] || !$user_data['gender']) { 
        return ['error' => 'Please set your Age and Gender in the "Predictions & Goals" card.']; 
    }

    // 3. பயனரின் சமீபத்திய அளவீடுகளைப் (metrics) பெறுகிறது
    $stmt = $conn->prepare("SELECT weight_kg, height_cm, activity_level FROM user_metrics WHERE user_id = ? ORDER BY recorded_at DESC LIMIT 1");
    if ($stmt === false) { return ['error' => 'SQL Error (user_metrics table): ' . $conn->error]; }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $metrics = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // **பிழை சரிபார்ப்பு: பயனர் மெட்ரிக்குகளை அமைக்கவில்லை என்றால்**
    if (!$metrics) { 
        return ['error' => 'Please log your metrics (Height/Weight) first.']; 
    }

    // 4. BMR-ஐக் கணக்கிடுகிறது
    $bmr = 0;
    if ($user_data['gender'] == 'male') {
        $bmr = (10 * $metrics['weight_kg']) + (6.25 * $metrics['height_cm']) - (5 * $user_data['age']) + 5;
    } else {
        $bmr = (10 * $metrics['weight_kg']) + (6.25 * $metrics['height_cm']) - (5 * $user_data['age']) - 161;
    }
    
    // 5. TDEE-ஐக் கணக்கிடுகிறது
    $activity_factors = ['sedentary' => 1.2, 'light' => 1.375, 'moderate' => 1.55, 'active' => 1.725, 'very_active' => 1.9];
    $activity_level = $metrics['activity_level'] ?? 'sedentary'; // Default to sedentary
    $tdee = round($bmr * ($activity_factors[$activity_level] ?? 1.2));

    return ['tdee' => $tdee, 'goal' => $user_data['goal']];
}

// --- முக்கிய தர்க்கம் (Main Logic) ---

// 6. TDEE மற்றும் இலக்கைப் பெறுகிறது
$calc = get_user_tdee_and_goal($conn, $user_id);

// **முக்கிய பிழை சரிபார்ப்பு: TDEE கணக்கீடு தோல்வியுற்றால்**
if (isset($calc['error'])) { 
    http_response_code(400); // Bad Request
    echo json_encode($calc); // பிழைச் செய்தியை டாஷ்போர்டிற்கு அனுப்புகிறது
    $conn->close();
    exit(); 
}
$tdee = $calc['tdee']; // பயனரின் தினசரி கலோரி இலக்கு
$goal = $calc['goal']; // பயனரின் இலக்கு

// 7. இன்று உட்கொண்ட கலோரிகளைக் கணக்கிடுகிறது
$today = date("Y-m-d");
$log_stmt = $conn->prepare("SELECT SUM(calories) as total_calories FROM daily_food_log WHERE user_id = ? AND log_date = ?");
$log_stmt->bind_param("is", $user_id, $today);
$log_stmt->execute();
$calories_today = $log_stmt->get_result()->fetch_assoc()['total_calories'] ?? 0;
$remaining_calories = $tdee - $calories_today;
$log_stmt->close();

// 8. உணவுப் பரிந்துரைகளைத் தேடுகிறது
$min_cal = ($remaining_calories / 2) - 150;
$max_cal = ($remaining_calories / 2) + 150;
$food_stmt = $conn->prepare("SELECT recipe_name, calories_per_serving FROM recipes WHERE calories_per_serving BETWEEN ? AND ? ORDER BY RAND() LIMIT 2");
$food_stmt->bind_param("ii", $min_cal, $max_cal);
$food_stmt->execute();
$food_suggestions = $food_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$food_stmt->close();

// 9. உடற்பயிற்சித் திட்டத்தைப் பரிந்துரைக்கிறது
$exercise_plans = [
    'lose_weight' => ['focus' => 'Fat Loss', 'plan' => 'Focus on 3 days of HIIT and 2 days of full-body strength training.'],
    'gain_muscle' => ['focus' => 'Muscle Gain', 'plan' => 'Focus on 4 days of strength training (e.g., Push/Pull/Legs).'],
    'maintain' => ['focus' => 'Maintenance', 'plan' => 'Mix 2 days of strength training with 2 days of moderate cardio.']
];
$exercise_plan = $exercise_plans[$goal] ?? $exercise_plans['maintain'];

// 10. இறுதி முடிவுகளை JSON ஆக டாஷ்போர்டிற்கு அனுப்புகிறது
echo json_encode([
    'calorie_analysis' => ['goal' => $tdee, 'consumed' => $calories_today, 'remaining' => $remaining_calories],
    'food_suggestions' => $food_suggestions,
    'exercise_plan' => $exercise_plan
]);

$conn->close();
?>