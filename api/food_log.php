<?php
// --- api/metrics.php ---

header("Content-Type: application/json");
session_start();
require_once '../db_connect.php';

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
        if (!isset($data['height_cm'], $data['weight_kg'], $data['activity_level'])) {
            http_response_code(400);
            echo json_encode(['message' => 'Missing required metric data.']);
            exit();
        }
        $height = $data['height_cm'];
        $weight = $data['weight_kg'];
        $activity_level = $data['activity_level'];
        $height_m = $height / 100;
        $bmi = ($height_m > 0) ? round($weight / ($height_m * $height_m), 2) : 0;

        $sql = "INSERT INTO user_metrics (user_id, height_cm, weight_kg, bmi, activity_level) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iddds", $user_id, $height, $weight, $bmi, $activity_level);
        
        if ($stmt->execute()) {
            echo json_encode(['message' => 'Metrics saved successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to save metrics.']);
        }
        $stmt->close();
        break;

    case 'GET':
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