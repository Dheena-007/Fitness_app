<?php
// --- api/search_recipes.php ---

header("Content-Type: application/json");
session_start();
require_once '../db_connect.php'; // 1. டேட்டாபேஸ் இணைப்பு

// 2. பயனர் உள்நுழைந்துள்ளாரா எனச் சரிபார்க்கவும்
if (!isset($_SESSION['user_id'])) { 
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized'])); 
}

// 3. தேடல் உள்ளீடுகளைப் (inputs) பெறுகிறது
$query = $_GET['query'] ?? '';
$max_calories = $_GET['calories'] ?? 0;

// 4. SQL query-ஐ மாறும் வகையில் (dynamically) உருவாக்குகிறது
$sql = "SELECT recipe_name, calories_per_serving, instructions FROM recipes WHERE 1=1";
$params = []; // bind_param க்கான அளவுருக்கள்
$types = ""; // bind_param க்கான வகைகள் (types)

// பயனர் ஒரு தேடல் வார்த்தையை (keyword) உள்ளிட்டிருந்தால்
if (!empty($query)) {
    $sql .= " AND recipe_name LIKE ?";
    $params[] = "%" . $query . "%";
    $types .= "s"; // 's' for string
}

// பயனர் ஒரு அதிகபட்ச கலோரியை (max calories) உள்ளிட்டிருந்தால்
if ($max_calories > 0 && is_numeric($max_calories)) {
    $sql .= " AND calories_per_serving <= ?";
    $params[] = $max_calories;
    $types .= "i"; // 'i' for integer
}

$sql .= " LIMIT 10"; // 10 முடிவுகளை மட்டும் காட்டவும்
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    http_response_code(500);
    die(json_encode(['error' => 'SQL prepare failed: ' . $conn->error]));
}

// 5. **முக்கிய சரிபார்ப்பு:** $params காலியாக இல்லை என்றால் மட்டுமே bind_param-ஐ அழைக்கிறது
if (!empty($params)) {
    $stmt->bind_param($types, ...$params); // '...' (spread operator) என்பது முக்கியம்
}

$stmt->execute();
$result = $stmt->get_result();
$recipes = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($recipes); // முடிவுகளை JSON ஆக அனுப்புகிறது

$stmt->close();
$conn->close();
?>