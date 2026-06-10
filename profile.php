<?php
require_once __DIR__ . "/includes/functions.php";

/* Page helper fallback: eta thakle old functions.php thakleo fatal error hobe na. */
if (!function_exists("orders")) {
    function orders(): array { return read_json("orders.json", []); }
}
if (!function_exists("deposits")) {
    function deposits(): array { return read_json("deposits.json", []); }
}
if (!function_exists("user_orders")) {
    function user_orders(string $email): array {
        return array_values(array_filter(orders(), function ($order) use ($email) {
            return ($order["user_email"] ?? "") === $email;
        }));
    }
}
if (!function_exists("user_deposits")) {
    function user_deposits(string $email): array {
        return array_values(array_filter(deposits(), function ($deposit) use ($email) {
            return ($deposit["user_email"] ?? "") === $email;
        }));
    }
}
if (!function_exists("money")) {
    function money($amount): string {
        $amount = (float)$amount;
        if (floor($amount) == $amount) return number_format($amount, 0) . " tk";
        return number_format($amount, 2) . " tk";
    }
}
if (!function_exists("status_class")) {
    function status_class(string $status): string {
        $status = strtolower(trim($status));
        if (in_array($status, ["completed", "approved", "active"], true)) return "success";
        if (in_array($status, ["cancelled", "rejected", "banned", "inactive"], true)) return "danger";
        if ($status === "processing") return "info";
        return "warning";
    }
}
if (!function_exists("show_flash")) {
    function show_flash(): void {
        $flash = function_exists("get_flash") ? get_flash() : ($_SESSION["flash"] ?? null);
        if (isset($_SESSION["flash"])) unset($_SESSION["flash"]);

        if (!$flash) return;

        $type = function_exists("e")
            ? e($flash["type"] ?? "info")
            : htmlspecialchars((string)($flash["type"] ?? "info"));

        $message = function_exists("e")
            ? e($flash["message"] ?? "")
            : htmlspecialchars((string)($flash["message"] ?? ""));

        echo '<div class="flash flash-' . $type . '">' . $message . '</div>';
    }
}

require_login();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    [$idx, $u] = find_user($_SESSION["user_email"]);
    $all = users();

    if ($idx !== null && isset($all[$idx])) {
        $all[$idx]["name"] = clean($_POST["name"] ?? ($u["name"] ?? ""));
        $all[$idx]["phone"] = clean($_POST["phone"] ?? "");
        $all[$idx]["address"] = clean($_POST["address"] ?? "");
        $all[$idx]["game_uid"] = clean($_POST["game_uid"] ?? "");
        $all[$idx]["profile_image"] = clean($_POST["profile_image"] ?? "");
        save_users($all);
    }

    flash("success", "Profile updated.");
    redirect("profile.php");
}

$user = current_user();
$userOrders = user_orders($user["email"]);
$userDeposits = user_deposits($user["email"]);

$pendingDeposits = array_values(array_filter($userDeposits, function ($d) {
    return strtolower($d["status"] ?? "") === "pending";
}));

$totalSpent = array_sum(array_map(function ($o) {
    return (float)($o["price"] ?? 0);
}, array_filter($userOrders, function ($o) {
    return strtolower($o["status"] ?? "") === "completed";
})));

$page_title = "Profile - 90N.GameShop";
$body_class = "dark-site";
require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/includes/nav.php";
?>

<style>
/* ===== Profile page spacing/header clash fix ===== */
.profile-page {
    width: min(1700px, calc(100% - 48px));
    margin: 0 auto;
    padding-top: 125px !important;
    padding-bottom: 60px;
}

/* header/nav er niche content jate dhuke na jay */
.site-header,
.top-nav,
.navbar {
    z-index: 9999;
}

/* hero strip ektu niche and clean */
.profile-hero-strip {
    margin-top: 0 !important;
    margin-bottom: 24px;
    padding: 32px 36px !important;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
}

.profile-hero-strip p {
    color: #86ff4f;
    letter-spacing: 1px;
    margin: 0 0 8px;
    font-weight: 800;
}

.profile-hero-strip h1 {
    margin: 0;
    font-size: 42px;
    line-height: 1.1;
}

.profile-hero-strip span {
    display: block;
    margin-top: 8px;
    color: #b8c7dc;
}

.profile-top-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.green-btn,
.wallet-add-btn,
.profile-form button {
    background: linear-gradient(135deg, #8cff48, #55d83f) !important;
    color: #06120d !important;
    border: 0 !important;
    border-radius: 14px !important;
    padding: 14px 26px !important;
    font-weight: 900 !important;
    text-decoration: none !important;
    cursor: pointer;
    box-shadow: 0 14px 30px rgba(117, 255, 70, 0.18);
}

.outline-green-btn {
    border: 1px solid rgba(132, 255, 79, .65);
    color: #eaf7ff;
    background: rgba(132, 255, 79, .06);
    border-radius: 14px;
    padding: 14px 26px;
    font-weight: 800;
    text-decoration: none;
}

.profile-layout {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 24px;
    align-items: start;
}

.profile-card {
    padding: 28px 24px !important;
}

.profile-info {
    padding: 32px !important;
}

.profile-form {
    display: grid;
    gap: 12px;
}

.profile-form label {
    font-weight: 800;
    color: #e8f2ff;
}

.profile-form input {
    width: 100%;
    padding: 16px 18px;
    border-radius: 12px;
    border: 1px solid rgba(183, 211, 255, .18);
    background: rgba(255, 255, 255, .08);
    color: #fff;
    outline: none;
}

.profile-form input:focus {
    border-color: rgba(132, 255, 79, .7);
}

.profile-form button {
    width: fit-content;
    margin-top: 8px;
}

.soft-text {
    color: #afbdd0;
    margin-bottom: 4px;
}

@media (max-width: 900px) {
    .profile-page {
        width: min(100% - 24px, 100%);
        padding-top: 115px !important;
    }

    .profile-layout {
        grid-template-columns: 1fr;
    }

    .profile-hero-strip {
        flex-direction: column;
        align-items: flex-start;
    }

    .profile-hero-strip h1 {
        font-size: 34px;
    }
}
</style>

<main class="profile-page">
    <?php show_flash(); ?>

    <section class="glass-panel profile-hero-strip">
        <div>
            <p>USER ACCOUNT</p>
            <h1>My Profile</h1>
            <span>Wallet details, personal information and profile photo.</span>
        </div>

        <div class="profile-top-actions">
            <a href="index.php#free-fire" class="green-btn">Buy Topup</a>
            <a href="orders.php" class="outline-green-btn">Orders</a>
        </div>
    </section>

    <section class="profile-layout">
        <aside class="glass-panel profile-card">
            <?php if (!empty($user["profile_image"])): ?>
                <img src="<?= e($user["profile_image"]) ?>" alt="Profile Picture" class="profile-photo">
            <?php else: ?>
                <div class="avatar"><?= strtoupper(substr($user["name"] ?? "U", 0, 1)) ?></div>
            <?php endif; ?>

            <h2><?= e($user["name"] ?? "User") ?></h2>
            <p><?= e($user["email"] ?? "") ?></p>
            <h3>Wallet Balance: <?= money($user["balance"] ?? 0) ?></h3>

            <form action="add_money.php" method="GET" class="add-wallet-form">
                <input type="number" name="amount" min="1" step="1" placeholder="Add amount" required>
                <button class="wallet-add-btn" type="submit">Add Money</button>
            </form>

            <div class="mini-stats">
                <div>
                    <b><?= count($userOrders) ?></b>
                    <span>Total Orders</span>
                </div>
                <div>
                    <b><?= count($pendingDeposits) ?></b>
                    <span>Pending Add Money</span>
                </div>
                <div>
                    <b><?= money($totalSpent) ?></b>
                    <span>Total Spent</span>
                </div>
            </div>

            <a class="logout-btn" href="logout.php">Logout</a>
        </aside>

        <section class="glass-panel profile-info">
            <div class="section-title">
                <div>
                    <p>PROFILE INFO</p>
                    <h2>Personal Details</h2>
                </div>
            </div>

            <form method="POST" class="profile-form">
                <label>Name</label>
                <input type="text" name="name" value="<?= e($user["name"] ?? "") ?>" required>

                <label>Phone</label>
                <input type="text" name="phone" value="<?= e($user["phone"] ?? "") ?>">

                <label>Address</label>
                <input type="text" name="address" value="<?= e($user["address"] ?? "") ?>" placeholder="Your address">

                <label>Free Fire UID</label>
                <input type="text" name="game_uid" value="<?= e($user["game_uid"] ?? "") ?>" placeholder="Your game UID">

                <label>Profile Picture Link</label>
                <input type="url" name="profile_image" value="<?= e($user["profile_image"] ?? "") ?>" placeholder="https://example.com/photo.png">

                <small class="soft-text">Image direct link dile profile picture show korbe.</small>

                <button type="submit">Save Profile</button>
            </form>
        </section>
    </section>
</main>

<?php require_once __DIR__ . "/includes/footer.php"; ?>