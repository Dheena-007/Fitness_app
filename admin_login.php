<?php
$page_title = 'Admin Login';
include 'templates/header.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // For simplicity, we are using hardcoded admin credentials
    $admin_user = 'admin';
    $admin_pass = 'password123';

    if ($_POST['username'] === $admin_user && $_POST['password'] === $admin_pass) {
        $_SESSION['is_admin'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $error = 'Invalid credentials.';
    }
}
?>
<div class="container">
    <div class="card form-card">
        <h2 style="text-align:center;">Admin Login</h2>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>
    </div>
</div>
<?php include 'templates/footer.php'; ?>