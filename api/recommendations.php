<?php
// --- api/smart_recommendations.php ---
// இது உங்கள் "Smart AI Recommendations" கார்டிற்கான தர்க்கம்

header("Content-Type: application/json");
session_start();
require_once '../db_connect.php'; // டேட்டாபேஸ் இணைப்பு

// 1. பயனர் உள்நுழைந்துள்ளாரா எனச் சரிபார்க்கவும்
if (!isset($_SESSION['user_id'])) { 
    exit(json_encode(['error' => 'Not logged in'])); 
}
$user_id = $_SESSION['user_id'];

/**
 * TDEE (Total Daily Energy Expenditure) ஐக் கணக்கிடும் ஒரு ஹெல்பர் செயல்பாடு.
 * இது உங்கள் api/predictions.php கோப்பிலும் உள்ளது.
 */
function get_user_tdee_and_goal($conn, $user_id) {
    // 2. பயனரின் சுயவிவரத்தைப் (profile) பெறுகிறது (வயது, பாலினம், இலக்கு)
    $stmt = $conn->prepare("SELECT age, gender, goal FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_data = $stmt->get_result()->fetch_assoc();
    if (!$user_data || !$user_data['age']) { 
        // இந்தத் தரவு இல்லையென்றால், பயனரிடம் அதை உள்ளிடுமாறு கேட்கிறது
        return ['error' => 'Please set your Age and Gender in the "Predictions & Goals" card.']; 
    }
    $stmt->close();

    // 3. பயனரின் சமீபத்திய அளவீடுகளைப் (metrics) பெறுகிறது (உயரம், எடை, ஆக்டிவிட்டி)
    $stmt = $conn->prepare("SELECT weight_kg, height_cm, activity_level FROM user_metrics WHERE user_id = ? ORDER BY recorded_at DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $metrics = $stmt->get_result()->fetch_assoc();
    if (!$metrics) { 
        return ['error' => 'Please log your metrics (Height/Weight) first.']; 
    }
    $stmt->close();

    // 4. BMR-ஐக் கணக்கிடுகிறது (Mifflin-St Jeor Formula)
    $bmr = 0;
    if ($user_data['gender'] == 'male') {
        $bmr = (10 * $metrics['weight_kg']) + (6.25 * $metrics['height_cm']) - (5 * $user_data['age']) + 5;
    } else {
        $bmr = (10 * $metrics['weight_kg']) + (6.25 * $metrics['height_cm']) - (5 * $user_data['age']) - 161;
    }
    
    // 5. TDEE-ஐக் கணக்கிடுகிறது (BMR * Activity Level)
    $activity_factors = ['sedentary' => 1.2, 'light' => 1.375, 'moderate' => 1.55, 'active' => 1.725, 'very_active' => 1.9];
    $tdee = round($bmr * ($activity_factors[$metrics['activity_level']] ?? 1.2));

    return ['tdee' => $tdee, 'goal' => $user_data['goal']];
}

// --- முக்கிய தர்க்கம் (Main Logic) ---

// 6. TDEE மற்றும் இலக்கைப் பெறுகிறது
$calc = get_user_tdee_and_goal($conn, $user_id);
if (isset($calc['error'])) { 
    echo json_encode($calc); 
    $conn->close();
    exit(); 
}
$tdee = $calc['tdee']; // பயனரின் தினசரி கலோரி இலக்கு (Maintenance)
$goal = $calc['goal']; // பயனரின் இலக்கு (e.g., 'lose_weight')

// 7. இன்று உட்கொண்ட கலோரிகளைக் கணக்கிடுகிறது
$today = date("Y-m-d");
$log_stmt = $conn->prepare("SELECT SUM(calories) as total_calories FROM daily_food_log WHERE user_id = ? AND log_date = ?");
$log_stmt->bind_param("is", $user_id, $today);
$log_stmt->execute();
$calories_today = $log_stmt->get_result()->fetch_assoc()['total_calories'] ?? 0;
$remaining_calories = $tdee - $calories_today; // மீதமுள்ள கலோரிகள்
$log_stmt->close();

// 8. மீதமுள்ள கலோரிகளுக்கு ஏற்ப உணவுப் பரிந்துரைகளைத் தேடுகிறது
$min_cal = ($remaining_calories / 2) - 150; // அடுத்த வேளை உணவிற்கான ஒரு தோராயமான கணக்கீடு
$max_cal = ($remaining_calories / 2) + 150;
$food_stmt = $conn->prepare("SELECT recipe_name, calories_per_serving FROM recipes WHERE calories_per_serving BETWEEN ? AND ? ORDER BY RAND() LIMIT 2");
$food_stmt->bind_param("ii", $min_cal, $max_cal);
$food_stmt->execute();
$food_suggestions = $food_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$food_stmt->close();

// 9. இலக்கின் அடிப்படையில் உடற்பயிற்சித் திட்டத்தைப் பரிந்துரைக்கிறது
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