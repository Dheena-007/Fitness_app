<?php
// --- api/water_log.php ---

header("Content-Type: application/json");
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) { exit; }

$user_id = $_SESSION['user_id'];
$today = date("Y-m-d");
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $conn->prepare("SELECT glasses FROM daily_water_log WHERE user_id = ? AND log_date = ?");
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $log = $result->fetch_assoc();
    echo json_encode($log ? $log : ['glasses' => 0]);
} elseif ($method === 'POST') {
    // ஒரு நாளில் முதல் முறை கிளிக் செய்தால் புதிய பதிவை உருவாக்கும், இல்லையெனில் அளவை அதிகரிக்கும்
    $sql = "INSERT INTO daily_water_log (user_id, log_date, glasses) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE glasses = glasses + 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    echo json_encode(['status' => 'success']);
}
$conn->close();
?>