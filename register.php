<?php
require_once __DIR__ . "/includes/functions.php";
if (current_user()) redirect("profile.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = clean($_POST["name"] ?? "");
    $email = clean_email($_POST["email"] ?? "");
    $password = clean($_POST["password"] ?? "");
    $confirm = clean($_POST["confirm"] ?? "");

    if (!$name || !$email || !$password) {
        flash("error", "All fields are required.");
        redirect("register.php");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash("error", "Invalid email.");
        redirect("register.php");
    }

    if ($password !== $confirm) {
        flash("error", "Passwords do not match.");
        redirect("register.php");
    }

    [$i, $exists] = find_user($email);
    if ($exists) {
        flash("error", "Email already registered.");
        redirect("register.php");
    }

    $users = users();
    $users[] = [
        "id" => make_id("USR"),
        "name" => $name,
        "email" => $email,
        "password" => $password,
        "phone" => "",
        "balance" => 0,
        "created_at" => date("d M Y")
    ];
    save_users($users);

    $_SESSION["user_email"] = $email;
    flash("success", "Account created successfully.");
    redirect("profile.php");
}

$page_title = "Register - 90N.GameShop";
$body_class = "public-page dark-shop";
require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/includes/nav.php";
$f = get_flash();
?>
<main class="shop-main auth-dark-wrap">
    <section class="auth-box dark-user-auth">
        <h1>Register</h1>
        <?php if ($f): ?><div class="alert <?= e($f["type"]) ?>"><?= e($f["message"]) ?></div><?php endif; ?>

        <form method="POST">
            <label>Name</label>
            <input type="text" name="name" placeholder="Enter your name" required>

            <label>Email</label>
            <input type="email" name="email" placeholder="Enter your email" required>

            <label>Password</label>
            <div class="password-field">
                <input id="regPass" type="password" name="password" placeholder="Create password" required>
                <button type="button" class="eye-btn" onclick="togglePassword('regPass', this)">👁</button>
            </div>

            <label>Confirm Password</label>
            <div class="password-field">
                <input id="regPass2" type="password" name="confirm" placeholder="Confirm password" required>
                <button type="button" class="eye-btn" onclick="togglePassword('regPass2', this)">👁</button>
            </div>

            <button class="black-btn" type="submit">Register</button>
        </form>

        <p class="auth-switch">Already have account? <a href="login.php">Login</a></p>
    </section>
</main>
<?php require_once __DIR__ . "/includes/footer.php"; ?>
