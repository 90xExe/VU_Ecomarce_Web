<?php
require_once __DIR__ . "/includes/functions.php";
if (current_user()) redirect("profile.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = clean_email($_POST["email"] ?? "");
    $password = clean($_POST["password"] ?? "");

    [$i, $u] = find_user($email);
    if (!$u || ($u["password"] ?? "") !== $password) {
        flash("error", "Invalid email or password.");
        redirect("login.php");
    }

    $_SESSION["user_email"] = $email;
    flash("success", "Login successful.");
    redirect("profile.php");
}

$page_title = "Login - 90N.GameShop";
$body_class = "public-page dark-shop";
require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/includes/nav.php";
$f = get_flash();
?>
<main class="shop-main auth-dark-wrap">
    <section class="auth-box dark-user-auth">
        <h1>Login</h1>
        <?php if ($f): ?><div class="alert <?= e($f["type"]) ?>"><?= e($f["message"]) ?></div><?php endif; ?>

        <form method="POST">
            <label>Email</label>
            <input type="email" name="email" placeholder="Enter your email" required>

            <label>Password</label>
            <div class="password-field">
                <input id="userPass" type="password" name="password" placeholder="Enter your password" required>
                <button type="button" class="eye-btn" onclick="togglePassword('userPass', this)">👁</button>
            </div>

            <button class="black-btn" type="submit">Login</button>
        </form>

        <p class="auth-switch">Don't have account? <a href="register.php">Register</a></p>
    </section>
</main>
<?php require_once __DIR__ . "/includes/footer.php"; ?>
