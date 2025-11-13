<?php
$page_title = 'Login';
include 'templates/header.php';
require_once 'db_connect.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT id, username, password FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['flash_message'] = "Welcome back, " . htmlspecialchars($user['username']) . "!";
            header("Location: dashboard.php");
            exit();
        }
    }
    $error = "Invalid email or password.";
}
?>

<div class="container">
    <div class="card form-card">
        <h2 style="text-align:center;">User Login</h2>
        <?php if(!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="post" action="login.php">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>
        <p style="text-align:center; margin-top:1rem;">Don't have an account? <a href="register.php">Register here</a>.</p>
    </div>
</div>

<?php include 'templates/footer.php'; ?>