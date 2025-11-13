<?php
// --- api/predictions.php (சரிசெய்யப்பட்ட கோட்) ---

header("Content-Type: application/json");
session_start();
require_once '../db_connect.php'; // 1. டேட்டாபேஸ் இணைப்பு

// 2. பயனர் உள்நுழைந்துள்ளாரா எனச் சரிபார்க்கவும்
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['message' => 'User not logged in.']));
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? ''; // என்ன செயல்பாடு (action) எனப் பெறுகிறது

/**
 * TDEE (Total Daily Energy Expenditure) ஐக் கணக்கிடும் ஒரு ஹெல்பர் செயல்பாடு.
 * இது வயது, பாலினம், மெட்ரிக்குகள் உள்ளதா எனச் சரிபார்க்கும்.
 * @param mysqli $conn
 * @param int $user_id
 * @return array
 */
function calculate_tdee($conn, $user_id) {
    // 1. பயனரின் சுயவிவரத்தைப் (profile) பெறுகிறது (வயது, பாலினம், இலக்கு)
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

    // 2. பயனரின் சமீபத்திய அளவீடுகளைப் (metrics) பெறுகிறது
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

    // 3. BMR-ஐக் கணக்கிடுகிறது
    $bmr = 0;
    if ($user_data['gender'] == 'male') {
        $bmr = (10 * $metrics['weight_kg']) + (6.25 * $metrics['height_cm']) - (5 * $user_data['age']) + 5;
    } else {
        $bmr = (10 * $metrics['weight_kg']) + (6.25 * $metrics['height_cm']) - (5 * $user_data['age']) - 161;
    }
    
    // 4. TDEE-ஐக் கணக்கிடுகிறது
    $activity_factors = ['sedentary' => 1.2, 'light' => 1.375, 'moderate' => 1.55, 'active' => 1.725, 'very_active' => 1.9];
    $activity_level = $metrics['activity_level'] ?? 'sedentary'; // Default to sedentary
    $tdee = round($bmr * ($activity_factors[$activity_level] ?? 1.2));

    // எல்லா தரவையும் திருப்பி அனுப்புகிறது
    return ['tdee' => $tdee, 'goal' => $user_data['goal'], 'metrics' => $metrics];
}

// --- கோரிக்கையைக் கையாளுதல் (Routing the request) ---
switch ($action) {
    /**
     * செயல்பாடு: 7 நாட்களில் எடையைக் கணித்தல்
     * முறை: GET
     */
    case 'predict_weight':
        $stmt = $conn->prepare("SELECT weight_kg FROM user_metrics WHERE user_id = ? ORDER BY recorded_at DESC LIMIT 2");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $weights = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        if (count($weights) < 2) {
            echo json_encode(['predicted_weight' => null, 'message' => 'Not enough data.']);
            break;
        }
        
        $trend = $weights[0]['weight_kg'] - $weights[1]['weight_kg'];
        $predicted_weight = $weights[0]['weight_kg'] + $trend; 
        echo json_encode(['predicted_weight' => round($predicted_weight, 1)]);
        break;

    /**
     * செயல்பாடு: கலோரி இலக்கைக் கணக்கிடுதல் மற்றும் சுயவிவரத்தை (Profile) அப்டேட் செய்தல்
     * முறை: POST
     */
    case 'calculate_calories':
        $data = json_decode(file_get_contents('php://input'), true);
        
        // 1. சுயவிவரத்தை (Profile) அப்டேட் செய்கிறது (வயது, பாலினம், இலக்கு)
        $age = $data['age'] ?? null;
        $gender = $data['gender'] ?? null;
        $goal = $data['goal'] ?? 'maintain'; 

        if(empty($age) || empty($gender)) {
            http_response_code(400);
            echo json_encode(['message' => 'Age and Gender are required to save profile.']);
            break;
        }

        $stmt = $conn->prepare("UPDATE users SET age = ?, gender = ?, goal = ? WHERE id = ?");
        if ($stmt === false) { die(json_encode(['error' => 'SQL Error (UPDATE users): ' . $conn->error])); }
        $stmt->bind_param("issi", $age, $gender, $goal, $user_id);
        $stmt->execute();
        $stmt->close();

        // 2. "Save Goal" பட்டன் மட்டும் அழுத்தப்பட்டால், இங்கே நிறுத்திவிடவும்
        if (!isset($data['target_weight']) || empty($data['target_weight']) || !isset($data['target_date']) || empty($data['target_date'])) { 
            echo json_encode(['message' => 'Profile updated.']);
            break;
        }

        // 3. "Calculate" பட்டன் அழுத்தப்பட்டால், கணக்கீட்டைத் தொடரவும்
        $calc = calculate_tdee($conn, $user_id);
        
        if (isset($calc['error'])) { 
            http_response_code(400);
            echo json_encode(['message' => $calc['error']]);
            break; 
        }
        
        $tdee = $calc['tdee'];
        $current_weight = $calc['metrics']['weight_kg'];
        
        $weight_change_kg = $data['target_weight'] - $current_weight;
        $total_calorie_change = $weight_change_kg * 7700; // 1 கிலோ = 7700 கலோரிகள்
        
        $days_to_target = (new DateTime($data['target_date']))->diff(new DateTime())->days;
        
        if ($days_to_target <= 0) {
            http_response_code(400);
            echo json_encode(['message' => 'Target date must be in the future.']);
            break;
        }
        
        $daily_calorie_adjustment = $total_calorie_change / $days_to_target;
        $calorie_goal = round($tdee + $daily_calorie_adjustment);
        echo json_encode(['calorie_goal' => $calorie_goal]);
        break;

    /**
     * செயல்பாடு: தானியங்கி (Automatic) கலோரி இலக்கைப் பெறுதல்
     * முறை: GET
     */
    case 'get_auto_calorie_goal':
        $calc = calculate_tdee($conn, $user_id);
        if (isset($calc['error'])) { 
            http_response_code(400); 
            echo json_encode(['error' => $calc['error']]); // பிழைச் செய்தியை அனுப்புகிறது
            break; 
        }
        
        $tdee = $calc['tdee'];
        $goal = $calc['goal'];
        
        $auto_goal = $tdee;
        if ($goal == 'lose_weight') $auto_goal -= 500; 
        elseif ($goal == 'gain_muscle') $auto_goal += 300; 
        
        echo json_encode(['auto_calorie_goal' => $auto_goal]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['message' => 'Invalid action specified.']);
        break;
}

$conn->close();
?>