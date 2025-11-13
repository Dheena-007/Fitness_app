<?php
$page_title = 'Admin Panel';
include 'templates/header.php';
require_once 'db_connect.php';

// Security check
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: admin_login.php');
    exit;
}

// Fetch all recipes
$recipes = $conn->query("SELECT * FROM recipes ORDER BY recipe_name ASC");
?>
<div class="container">
    <h2>Admin Panel: Manage Recipes</h2>

    <div class="card">
        <h3>Add New Recipe</h3>
        <form action="admin_actions.php" method="POST">
            <input type="hidden" name="action" value="add_recipe">
            <div class="form-group"><input type="text" name="recipe_name" placeholder="Recipe Name" required></div>
            <div class="form-group"><textarea name="ingredients" placeholder="Ingredients" required></textarea></div>
            <div class="form-group"><textarea name="instructions" placeholder="Instructions" required></textarea></div>
            <div class="form-group"><input type="number" name="calories" placeholder="Calories per serving" required></div>
            <button type="submit" class="btn btn-success">Add Recipe</button>
        </form>
    </div>

    <div class="card">
        <h3>Existing Recipes</h3>
        <ul style="list-style: none; padding: 0;">
            <?php while ($recipe = $recipes->fetch_assoc()): ?>
                <li style="display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #eee;">
                    <span><?php echo htmlspecialchars($recipe['recipe_name']); ?></span>
                    <form action="admin_actions.php" method="POST" style="margin: 0;">
                        <input type="hidden" name="action" value="delete_recipe">
                        <input type="hidden" name="recipe_id" value="<?php echo $recipe['id']; ?>">
                        <button type="submit" class="btn btn-danger" style="padding: 5px 10px;">Delete</button>
                    </form>
                </li>
            <?php endwhile; ?>
        </ul>
    </div>
</div>
<?php include 'templates/footer.php'; ?>