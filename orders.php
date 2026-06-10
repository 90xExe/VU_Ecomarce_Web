<?php
require_once __DIR__ . "/includes/functions.php";

/* Page helper fallback */
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

        $type = function_exists("e") ? e($flash["type"] ?? "info") : htmlspecialchars((string)($flash["type"] ?? "info"));
        $message = function_exists("e") ? e($flash["message"] ?? "") : htmlspecialchars((string)($flash["message"] ?? ""));

        echo '<div class="flash flash-' . $type . '">' . $message . '</div>';
    }
}

require_login();

$user = current_user();
$userOrders = array_reverse(user_orders($user["email"]));
$userDeposits = array_reverse(user_deposits($user["email"]));

$page_title = "Orders - 90N.GameShop";
$body_class = "dark-site";

require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/includes/nav.php";
?>

<style>
/* Header/nav er sathe content clash fix */
.fixed-top-space {
    max-width: 1320px;
    margin: 0 auto;
    padding: 115px 24px 70px !important;
}

/* Orders page er card spacing */
.orders-page .profile-hero-strip,
.orders-page .order-history-card {
    margin-bottom: 26px;
}

.orders-page .profile-hero-strip {
    padding: 34px 34px;
}

.orders-page .order-history-card {
    padding: 34px;
}

.orders-page .section-title {
    margin-bottom: 22px;
}

.orders-page .order-list-view {
    display: grid;
    gap: 18px;
}

.orders-page .history-item {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    padding: 24px 26px;
    border: 1px solid rgba(140, 180, 220, .18);
    border-radius: 16px;
    background: rgba(255, 255, 255, .035);
}

.orders-page .history-col p {
    margin: 0 0 12px;
    line-height: 1.5;
}

.orders-page .history-col p:last-child {
    margin-bottom: 0;
}

.orders-page .empty-history {
    padding: 24px 26px;
    border: 1px solid rgba(140, 180, 220, .18);
    border-radius: 16px;
    color: #c8d5e8;
    font-weight: 700;
    background: rgba(255, 255, 255, .035);
}

.orders-page .pink-price {
    color: #ff4fb8;
    font-weight: 800;
}

.orders-page .status-text {
    font-weight: 800;
}

.orders-page .status-text.success {
    color: #7cff4c;
}

.orders-page .status-text.warning {
    color: #ffd84d;
}

.orders-page .status-text.danger {
    color: #ff5b6e;
}

.orders-page .status-text.info {
    color: #4db8ff;
}

.orders-page .profile-top-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .fixed-top-space {
        padding: 100px 14px 50px !important;
    }

    .orders-page .profile-hero-strip,
    .orders-page .order-history-card {
        padding: 24px 18px;
    }

    .orders-page .history-item {
        grid-template-columns: 1fr;
        padding: 20px;
    }
}
</style>

<main class="orders-page fixed-top-space">
    <?php show_flash(); ?>

    <section class="glass-panel profile-hero-strip">
        <div>
            <p>ACCOUNT HISTORY</p>
            <h1>Orders & Add Money</h1>
            <span>Topup orders and wallet add-money requests ek jaygay.</span>
        </div>

        <div class="profile-top-actions">
            <a href="profile.php" class="outline-green-btn">Profile</a>
            <a href="index.php#free-fire" class="green-btn">Buy Topup</a>
        </div>
    </section>

    <section class="glass-panel order-history-card">
        <div class="section-title">
            <div>
                <p>TOPUP</p>
                <h2>My Orders</h2>
            </div>
        </div>

        <div class="order-list-view">
            <?php if (!$userOrders): ?>
                <div class="empty-history">No topup orders yet.</div>
            <?php endif; ?>

            <?php foreach ($userOrders as $order): ?>
                <article class="history-item">
                    <div class="history-col">
                        <p><b>Serial NO:</b> <?= e($order["id"] ?? "-") ?></p>
                        <p><b>Date:</b> <?= e($order["created_at"] ?? "-") ?></p>
                        <p><b>Package:</b> <?= e($order["package"] ?? "-") ?></p>
                        <p><b>Quantity:</b> <?= e($order["quantity"] ?? "1") ?></p>
                    </div>

                    <div class="history-col">
                        <p><b>Player ID:</b> <?= e($order["player_uid"] ?? "-") ?></p>
                        <p><b>Product:</b> <?= e($order["product"] ?? "-") ?></p>
                        <p><b>Price:</b> <span class="pink-price"><?= money($order["price"] ?? 0) ?></span></p>
                        <p>
                            <b>Status:</b>
                            <span class="status-text <?= status_class($order["status"] ?? "pending") ?>">
                                <?= e($order["status"] ?? "pending") ?>
                            </span>
                        </p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="glass-panel order-history-card">
        <div class="section-title">
            <div>
                <p>WALLET</p>
                <h2>Add Money Requests</h2>
            </div>

            <a href="profile.php" class="outline-green-btn small">Add Money</a>
        </div>

        <div class="order-list-view">
            <?php if (!$userDeposits): ?>
                <div class="empty-history">No add money requests yet.</div>
            <?php endif; ?>

            <?php foreach ($userDeposits as $deposit): ?>
                <article class="history-item">
                    <div class="history-col">
                        <p><b>Request ID:</b> <?= e($deposit["id"] ?? "-") ?></p>
                        <p><b>Date:</b> <?= e($deposit["created_at"] ?? "-") ?></p>
                        <p><b>Method:</b> <?= e($deposit["method"] ?? "bKash Send Money") ?></p>
                        <p><b>Merchant:</b> <?= e($deposit["merchant_number"] ?? "-") ?></p>
                    </div>

                    <div class="history-col">
                        <p><b>Transaction ID:</b> <?= e($deposit["trxid"] ?? "-") ?></p>
                        <p><b>Amount:</b> <span class="pink-price"><?= money($deposit["amount"] ?? 0) ?></span></p>
                        <p>
                            <b>Status:</b>
                            <span class="status-text <?= status_class($deposit["status"] ?? "pending") ?>">
                                <?= e($deposit["status"] ?? "pending") ?>
                            </span>
                        </p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<?php require_once __DIR__ . "/includes/footer.php"; ?>