<?php
// --- api/search_recipes.php ---

header("Content-Type: application/json");
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) { exit(json_encode(['error' => 'Unauthorized'])); }

$query = $_GET['query'] ?? '';
$max_calories = $_GET['calories'] ?? 0;

$sql = "SELECT recipe_name, calories_per_serving, instructions FROM recipes WHERE 1=1";
$params = [];
$types = "";

if (!empty($query)) {
    $sql .= " AND recipe_name LIKE ?";
    $params[] = "%" . $query . "%";
    $types .= "s";
}

if ($max_calories > 0) {
    $sql .= " AND calories_per_serving <= ?";
    $params[] = $max_calories;
    $types .= "i";
}

$sql .= " LIMIT 10";
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$recipes = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($recipes);
$conn->close();
?>