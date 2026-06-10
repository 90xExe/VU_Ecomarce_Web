<?php
require_once __DIR__ . "/../includes/functions.php";
if (current_admin()) redirect("index.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = clean($_POST["username"] ?? "");
    $password = clean($_POST["password"] ?? "");

    [, $admin] = find_admin($username);

    if (!$admin || ($admin["password"] ?? "") !== $password) {
        flash("error", "Invalid admin username or password.");
        redirect("login.php");
    }

    $_SESSION["admin_username"] = $username;
    flash("success", "Admin login successful.");
    redirect("index.php");
}

$f = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - 90N.GameShop</title>
    <link rel="stylesheet" href="../assets/css/admin.css?v=9001">
</head>
<body class="admin-login-body">
    <main class="admin-login-wrap">
        <section class="admin-login-card">
            <div class="admin-logo">90N</div>
            <p>ADMIN CONTROL</p>
            <h1>90N.GameShop</h1>

            <?php if ($f): ?><div class="admin-alert <?= e($f["type"]) ?>"><?= e($f["message"]) ?></div><?php endif; ?>

            <form method="POST">
                <label>Username</label>
                <input type="text" name="username" value="admin" required>

                <label>Password</label>
                <input type="password" name="password" placeholder="Admin password" required>

                <button type="submit">Login Admin</button>
            </form>

            <small>Default: admin / admin123</small>
            <a href="../index.php">← Back to Website</a>
        </section>
    </main>
</body>
</html>
