<?php
// --- api/metrics.php ---

header("Content-Type: application/json");
session_start();
require_once '../db_connect.php'; // 1. டேட்டாபேஸ் இணைப்பைச் சரிபார்க்கவும்

// 2. பயனர் உள்நுழைந்துள்ளாரா எனச் சரிபார்க்கவும்
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['message' => 'User not logged in.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        // 3. தரவு சரியாக வருகிறதா எனச் சரிபார்க்கவும்
        if (!isset($data['height_cm'], $data['weight_kg'], $data['activity_level'])) {
            http_response_code(400); // Bad Request
            echo json_encode(['message' => 'Missing required metric data.']);
            exit();
        }
        
        $height = (float)$data['height_cm'];
        $weight = (float)$data['weight_kg'];
        $activity_level = $data['activity_level'];
        $bmi = 0;
        
        // 4. BMI கணக்கீடு (0 ஆல் வகுப்பதைத் தடுத்தல்)
        if ($height > 0) {
            $height_m = $height / 100;
            $bmi = round($weight / ($height_m * $height_m), 2);
        }

        $sql = "INSERT INTO user_metrics (user_id, height_cm, weight_kg, bmi, activity_level) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        // 5. SQL prepare statement தோல்வியடைந்ததா எனச் சரிபார்க்கவும்
        if ($stmt === false) {
             http_response_code(500);
             // உண்மையான MySQL பிழையைக் காட்டுகிறது
             echo json_encode(['message' => 'Database prepare statement failed: ' . $conn->error]);
             exit();
        }
        
        $stmt->bind_param("iddds", $user_id, $height, $weight, $bmi, $activity_level);
        
        if ($stmt->execute()) {
            echo json_encode(['message' => 'Metrics saved successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to save metrics: ' . $stmt->error]);
        }
        $stmt->close();
        break;

    case 'GET':
        // இது சார்ட்டுக்காக (chart) தரவை எடுக்கும்
        $sql = "SELECT weight_kg, recorded_at FROM user_metrics WHERE user_id = ? ORDER BY recorded_at ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $metrics = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode($metrics);
        $stmt->close();
        break;
}
$conn->close();
?>