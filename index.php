<?php
// --- index.php (Final Upgraded Version) ---

// This code helps find errors during development. You can remove it for a live server.
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Use __DIR__ for a reliable path to the language loader.
require_once __DIR__ . '/config/lang_loader.php';

// Set the page title from the language file.
$page_title = $lang['index_hero_title'];

// Use __DIR__ for a reliable path to the header.
include __DIR__ . '/templates/header.php';
?>

<div class="container">
    <section class="hero-section">
        <h1><?php echo $lang['index_hero_title']; ?></h1>
        <p><?php echo $lang['index_hero_subtitle']; ?></p>
        <a href="register.php" class="btn btn-light" style="color: var(--primary-color); font-weight: bold;"><?php echo $lang['index_cta_button']; ?></a>
    </section>

    <section style="margin-top: 3rem;">
        <h2 class="section-title"><?php echo $lang['index_features_title']; ?></h2>
        <div class="features-grid">

        <div class="card">
    <div class="card-header">
        <i class="fas fa-cogs icon"></i>
        <h3>Personalized Plans</h3>
    </div>
    <p>This web-based system provides users with custom workout and diet plans based on their individual body data. The platform generates personalized workout routines and diet plans using details such as height, weight, and BMI.</p>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-video icon"></i>
        <h3>Real-Time Form Check</h3>
    </div>
    <p>The system uses AI with a webcam to check exercise form in real time. This AI-powered module analyzes exercise posture and provides feedback that acts like a virtual trainer for correct exercise form.</p>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-chart-line icon"></i>
        <h3>Dynamic Progress Tracking</h3>
    </div>
    <p>Plans are automatically adjusted and updated as the user progresses. The platform helps users track progress and stay motivated to achieve their health goals effectively.</p>
</div>
            
        </div>
    </section>
</div>

<?php
// Use __DIR__ for a reliable path to the footer.
include __DIR__ . '/templates/footer.php';
?>