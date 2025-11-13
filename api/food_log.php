<?php
// --- api/food_log.php ---

header("Content-Type: application/json");
session_start();
require_once '../db_connect.php';

// பயனர் உள்நுழைந்துள்ளாரா எனச் சரிபார்க்கவும்
if (!isset($_SESSION['user_id'])) { 
    http_response_code(401);
    echo json_encode(['error' => 'User not logged in']);
    exit; 
}

$user_id = $_SESSION['user_id'];
$today = date("Y-m-d"); // இன்றைய தேதியைப் பெறுகிறது
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // **முக்கிய பகுதி: இன்றைய உணவுப் பதிவுகளை மட்டும் தேர்ந்தெடுக்கிறது**
    $stmt = $conn->prepare("SELECT food_name, calories FROM daily_food_log WHERE user_id = ? AND log_date = ?");
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $log = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($log); // JSON ஆக அனுப்புகிறது

} elseif ($method === 'POST') {
    // இது புதிய உணவைச் சேமிக்கும் தர்க்கம்
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $conn->prepare("INSERT INTO daily_food_log (user_id, food_name, calories, log_date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isis", $user_id, $data['food_name'], $data['calories'], $today);
    $stmt->execute();
    echo json_encode(['status' => 'success']);
}
$conn->close();
?>