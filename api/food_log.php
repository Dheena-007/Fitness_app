<?php
// --- api/food_log.php ---

header("Content-Type: application/json");
session_start();
require_once '../db_connect.php'; // 1. டேட்டாபேஸ் இணைப்பு உள்ளதா எனச் சரிபார்க்கவும்

if (!isset($_SESSION['user_id'])) { 
    http_response_code(401);
    echo json_encode(['error' => 'User not logged in']);
    exit; 
}

$user_id = $_SESSION['user_id'];
$today = date("Y-m-d");
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // இன்று பதிவுசெய்த உணவுகளைத் தேர்ந்தெடுக்கிறது
    $stmt = $conn->prepare("SELECT food_name, calories FROM daily_food_log WHERE user_id = ? AND log_date = ?");
    if($stmt === false) { die(json_encode(['error' => 'SQL Error (GET): ' . $conn->error])); } 
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $log = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($log); 

} elseif ($method === 'POST') {
    // புதிய உணவைச் சேமிக்கிறது
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['food_name'], $data['calories'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing food name or calories.']);
        exit;
    }
    
    $stmt = $conn->prepare("INSERT INTO daily_food_log (user_id, food_name, calories, log_date) VALUES (?, ?, ?, ?)");
    if($stmt === false) { die(json_encode(['error' => 'SQL Error (POST): ' . $conn->error])); } 
    $stmt->bind_param("isis", $user_id, $data['food_name'], $data['calories'], $today);
    $stmt->execute();
    echo json_encode(['status' => 'success']);
}
$conn->close();
?>