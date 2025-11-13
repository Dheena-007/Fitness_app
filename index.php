<?php
// Load language and set page title
require_once __DIR__ . '/config/lang_loader.php';
$page_title = $lang['index_hero_title'];
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
                <div class="card-header"><i class="fas fa-cogs icon"></i><h3>Personalized Plans</h3></div>
                <p>This web-based system provides users with custom workout and diet plans based on their individual body data.</p>
            </div>
            <div class="card">
                <div class="card-header"><i class="fas fa-video icon"></i><h3>Real-Time Form Check</h3></div>
                <p>The system uses AI with a webcam to check exercise form in real time, acting like a virtual trainer.</p>
            </div>
            <div class="card">
                <div class="card-header"><i class="fas fa-chart-line icon"></i><h3>Dynamic Progress Tracking</h3></div>
                <p>Plans are automatically adjusted as the user progresses, helping them stay motivated and achieve health goals effectively.</p>
            </div>
        </div>
    </section>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>