<?php
// --- api/metrics.php ---

header("Content-Type: application/json");
session_start();
require_once '../db_connect.php'; // Assumes this file provides the $conn (mysqli) variable

// 1. Authentication Check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['message' => 'User not logged in.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    // --- HANDLE INSERT ---
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);

        // 2. Validation
        if (!isset($data['height_cm'], $data['weight_kg'], $data['activity_level'])) {
            http_response_code(400); // Bad Request
            echo json_encode(['message' => 'Missing required metric data.']);
            exit();
        }

        // 3. Data Processing
        $height = $data['height_cm'];
        $weight = $data['weight_kg'];
        $activity_level = $data['activity_level'];
        
        // Robust BMI calculation
        $height_m = $height / 100;
        $bmi = ($height_m > 0) ? round($weight / ($height_m * $height_m), 2) : 0;

        // 4. Secure Database Insertion
        $sql = "INSERT INTO user_metrics (user_id, height_cm, weight_kg, bmi, activity_level) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        // "iddds" = int, double, double, double, string
        $stmt->bind_param("iddds", $user_id, $height, $weight, $bmi, $activity_level);
        
        if ($stmt->execute()) {
            http_response_code(201); // Created
            echo json_encode(['message' => 'Metrics saved successfully.', 'bmi' => $bmi]);
        } else {
            http_response_code(500); // Internal Server Error
            echo json_encode(['message' => 'Failed to save metrics.']);
        }
        $stmt->close();
        break;

    // --- HANDLE FETCH ---
    case 'GET':
        // 5. Secure Database Fetch
        // Fetches weight history for a chart
        $sql = "SELECT weight_kg, recorded_at FROM user_metrics WHERE user_id = ? ORDER BY recorded_at ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $metrics = $result->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode($metrics);
        $stmt->close();
        break;

    // --- Handle other methods ---
    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(['message' => 'Method not allowed.']);
        break;
}

$conn->close();
?>