<?php
// --- api/ai_insights.php ---

header("Content-Type: application/json");
session_start();
require_once '../db_connect.php'; // டேட்டாபேஸ் இணைப்பு

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['insight' => 'Please log in to see your insights.']));
}

$user_id = $_SESSION['user_id'];
$insight = ''; // ஊக்கமளிக்கும் செய்தி (insight) இங்கே சேமிக்கப்படும்

try {
    // --- AI தர்க்கம் 1: தொடர்ச்சியான கலோரி பதிவுகளைச் சரிபார்க்கவும் ---
    $stmt1 = $conn->prepare("SELECT COUNT(DISTINCT log_date) as streak FROM daily_food_log WHERE user_id = ? AND log_date >= CURDATE() - INTERVAL 3 DAY");
    if($stmt1 === false) { throw new Exception('SQL Error (Streak Check)'); }
    $stmt1->bind_param("i", $user_id);
    $stmt1->execute();
    $streak = $stmt1->get_result()->fetch_assoc()['streak'] ?? 0;
    $stmt1->close();
    
    if ($streak >= 3) {
        $insight = "You've logged your calories for 3 days in a row. Amazing consistency! Keep it up.";
    }

    // --- AI தர்க்கம் 2: சமீபத்திய எடை குறைப்பைச் சரிபார்க்கவும் ---
    if ($insight == '') {
        $stmt2 = $conn->prepare("SELECT weight_kg FROM user_metrics WHERE user_id = ? ORDER BY recorded_at DESC LIMIT 2");
        if($stmt2 === false) { throw new Exception('SQL Error (Weight Check)'); }
        $stmt2->bind_param("i", $user_id);
        $stmt2->execute();
        $weights = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt2->close();
        
        if (count($weights) == 2 && $weights[0]['weight_kg'] < $weights[1]['weight_kg']) {
            $loss = $weights[1]['weight_kg'] - $weights[0]['weight_kg'];
            $insight = "You've lost " . round($loss, 1) . " kg since your last weigh-in. Fantastic progress!";
        }
    }

    // --- AI தர்க்கம் 3: செயலற்ற நிலையைக் (inactivity) கண்டறிதல் ---
    if ($insight == '') {
        $stmt3 = $conn->prepare("SELECT MAX(recorded_at) as last_log FROM user_metrics WHERE user_id = ?");
        if($stmt3 === false) { throw new Exception('SQL Error (Activity Check)'); }
        $stmt3->bind_param("i", $user_id);
        $stmt3->execute();
        $last_log_result = $stmt3->get_result()->fetch_assoc();
        $stmt3->close();
        
        if ($last_log_result && $last_log_result['last_log']) {
            $days_since_log = (new DateTime())->diff(new DateTime($last_log_result['last_log']))->days;
            if ($days_since_log > 3) {
                $insight = "It's been a few days since your last weigh-in. Logging your metrics today is a great step to get back on track!";
            }
        }
    }

    // --- AI தர்க்கம் 4: இயல்புநிலை உதவிக்குறிப்பு (Default Tip) ---
    if ($insight == '') {
        $stmt4 = $conn->prepare("SELECT COUNT(id) as total FROM user_metrics WHERE user_id = ?");
        if($stmt4 === false) { throw new Exception('SQL Error (Total Logs Check)'); }
        $stmt4->bind_param("i", $user_id);
        $stmt4->execute();
        $total_logs = $stmt4->get_result()->fetch_assoc()['total'];
        $stmt4->close();
        
        if ($total_logs < 2) {
            $insight = "Welcome! A great first step is to log your metrics and your first meal of the day.";
        } else {
            $insight = "Staying hydrated is key to success. Have you logged your water intake today?";
        }
    }

    // --- இறுதி முடிவை அனுப்புதல் ---
    echo json_encode(['insight' => $insight]);

} catch (Exception $e) {
    // ஏதேனும் SQL பிழை ஏற்பட்டால், அதைப் பதிவுசெய்து, பிழைச் செய்தியை அனுப்புகிறது
    http_response_code(500);
    echo json_encode(['insight' => 'Could not load insight due to a server error.', 'error' => $e->getMessage()]);
}

$conn->close();
?>