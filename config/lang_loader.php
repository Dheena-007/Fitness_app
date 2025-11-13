<?php
session_start();

// Check if the user is requesting a language change
if (isset($_GET['lang']) && ($_GET['lang'] == 'en' || $_GET['lang'] == 'ta')) {
    $_SESSION['lang'] = $_GET['lang'];
}

// Set the language from the session, or default to English
$current_lang = $_SESSION['lang'] ?? 'en';

// Load the corresponding language file
require_once __DIR__ . '/../lang/' . $current_lang . '.php';
?>