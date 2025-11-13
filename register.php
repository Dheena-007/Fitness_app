<?php
$page_title = 'Register';
include 'templates/header.php';
require_once 'db_connect.php';

$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $username, $email, $password);

    if ($stmt->execute()) {
        $message = "Registration successful! You can now login.";
        $message_type = 'success';
    } else {
        if ($conn->errno == 1062) {
            $message = "Error: This email or username is already taken.";
        } else {
            $message = "Error: " . $stmt->error;
        }
        $message_type = 'danger';
    }
    $stmt->close();
    $conn->close();
}
?>

<div class="container">
    <div class="card form-card">
        <h2 style="text-align:center;">Create a New Account</h2>
        <?php if(!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
        <?php endif; ?>
        <form method="post" action="register.php">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Register</button>
        </form>
        <p style="text-align:center; margin-top:1rem;">Already have an account? <a href="login.php">Login here</a>.</p>
    </div>
</div>

<?php include 'templates/footer.php'; ?>