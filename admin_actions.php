<?php
session_start();
require_once 'db_connect.php';

// Security check
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die('Unauthorized access.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_recipe') {
        $stmt = $conn->prepare("INSERT INTO recipes (recipe_name, ingredients, instructions, calories_per_serving) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $_POST['recipe_name'], $_POST['ingredients'], $_POST['instructions'], $_POST['calories']);
        $stmt->execute();
    } elseif ($_POST['action'] === 'delete_recipe' && isset($_POST['recipe_id'])) {
        $stmt = $conn->prepare("DELETE FROM recipes WHERE id = ?");
        $stmt->bind_param("i", $_POST['recipe_id']);
        $stmt->execute();
    }
}

header('Location: admin.php');
exit;
?>