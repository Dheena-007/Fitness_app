<?php
// Load language and session
require_once __DIR__ . '/../config/lang_loader.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang ?? 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : ($lang['nav_brand'] ?? 'AI Fitness'); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

<nav class="navbar-container">
    <div class="navbar">
        <a href="index.php" class="brand"><?php echo $lang['nav_brand']; ?></a>
        <div class="nav-links">
            <a href="?lang=en">English</a> | <a href="?lang=ta">தமிழ்</a>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php"><?php echo $lang['nav_dashboard']; ?></a>
                <a href="logout.php"><?php echo $lang['nav_logout']; ?></a>
            <?php else: ?>
                <a href="login.php" class="btn btn-secondary"><?php echo $lang['nav_login']; ?></a>
                <a href="register.php" class="btn btn-primary"><?php echo $lang['nav_register']; ?></a>
            <?php endif; ?>
        </div>
    </div>
</nav>