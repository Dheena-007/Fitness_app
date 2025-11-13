<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start(); // Start session if not already started
}

// Check for language change request
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'ta'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

// Set language from session or default to English
$current_lang = $_SESSION['lang'] ?? 'en';

// Load the corresponding language file
require_once __DIR__ . '/../lang/' . $current_lang . '.php';
?>