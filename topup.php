<?php
require_once __DIR__ . "/includes/functions.php";

$slug = clean($_GET["item"] ?? $_GET["slug"] ?? "");
[$productIndex, $product] = find_product($slug);

if (!$product || empty($product["active"])) {
    flash("error", "Product not found.");
    redirect("index.php");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user = current_user();

    if (!$user) {
        flash("error", "Please login to purchase.");
        redirect("login.php");
    }

    $packageIndex = (int)($_POST["package"] ?? -1);
    $uid = clean($_POST["player_uid"] ?? "");
    $packages = $product["packages"] ?? [];

    if (!isset($packages[$packageIndex])) {
        flash("error", "Please select a recharge package.");
        redirect("topup.php?item=" . urlencode($slug));
    }

    if ($uid === "") {
        flash("error", "Please enter your UID.");
        redirect("topup.php?item=" . urlencode($slug));
    }

    $pkg = $packages[$packageIndex];
    $price = (float)($pkg["price"] ?? 0);
    $couponCode = strtoupper(clean($_POST["coupon_code"] ?? ""));
    $discount = 0;
    $coupon = null;

    if ($couponCode !== "") {
        [$discount, $coupon] = coupon_discount($price, $couponCode);
        if (!$coupon) {
            flash("error", "Invalid or inactive coupon code.");
            redirect("topup.php?item=" . urlencode($slug));
        }
    }

    $finalPrice = max(0, $price - $discount);

    [$userIndex, $freshUser] = find_user($user["email"]);

    if ($userIndex === null || !$freshUser) {
        flash("error", "User not found. Please login again.");
        redirect("logout.php");
    }

    if (($freshUser["status"] ?? "active") === "banned") {
        flash("error", "Your account is banned. Please contact support.");
        redirect("profile.php");
    }

    if (($freshUser["status"] ?? "active") === "inactive") {
        flash("error", "Your account is inactive. Please contact support / regular hon.");
        redirect("profile.php");
    }

    $allUsers = users();
    $currentBalance = (float)($freshUser["balance"] ?? 0);

    if ($currentBalance < $finalPrice) {
        flash("error", "Not enough wallet balance. Add money from profile first.");
        redirect("profile.php");
    }

    $allUsers[$userIndex]["balance"] = $currentBalance - $finalPrice;
    save_users($allUsers);

    $orders = orders();
    $orders[] = [
        "id" => make_id("ORD"),
        "user_email" => $user["email"],
        "user_name" => $user["name"],
        "product" => $product["name"],
        "product_slug" => $product["slug"],
        "package" => $pkg["name"],
        "price" => $finalPrice,
        "original_price" => $price,
        "discount" => $discount,
        "coupon_code" => $coupon["code"] ?? "",
        "player_uid" => $uid,
        "payment" => "Wallet Balance",
        "status" => "pending",
        "created_at" => date("d M Y, h:i A")
    ];

    save_orders($orders);

    flash("success", "Order placed successfully. Wallet balance has been deducted.");
    redirect("profile.php");
}

$how_to_order_url = trim((string)($product["how_to_order_url"] ?? ""));
$page_title = $product["name"] . " - 90N.GameShop";
$body_class = "public-page dark-shop";

require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/includes/nav.php";

$flash_msg = get_flash();
$user = current_user();
?>

<main class="shop-main">
    <?php if ($flash_msg): ?>
        <div class="alert <?= e($flash_msg["type"]) ?>">
            <?= e($flash_msg["message"]) ?>
        </div>
    <?php endif; ?>

    <section class="dashboard-hero product-hero">
        <div class="hero-product">
            <img src="<?= e($product["image"]) ?>" alt="<?= e($product["name"]) ?>">

            <div>
                <p>TOPUP SERVICE</p>
                <h1><?= e($product["name"]) ?></h1>
                <span><?= e($product["type"] ?? "Game / Top up") ?></span>
            </div>
        </div>

        <?php if ($how_to_order_url !== ""): ?><a class="how-order-btn" style="display:inline-flex;align-items:center;justify-content:center;min-height:46px;padding:0 22px;border-radius:12px;border:0;background:linear-gradient(135deg,#92ff55,#56d942);color:#06110c;font-weight:900;text-decoration:none;box-shadow:0 14px 32px rgba(87,225,65,.24);cursor:pointer;" href="<?= e($how_to_order_url) ?>" target="_blank" rel="noopener">কিভাবে অর্ডার করবেন?</a><?php else: ?><button type="button" class="how-order-btn" style="display:inline-flex;align-items:center;justify-content:center;min-height:46px;padding:0 22px;border-radius:12px;border:0;background:linear-gradient(135deg,#92ff55,#56d942);color:#06110c;font-weight:900;text-decoration:none;box-shadow:0 14px 32px rgba(87,225,65,.24);cursor:pointer;">কিভাবে অর্ডার করবেন?</button><?php endif; ?>
    </section>

    <form method="POST" class="topup-dark-layout">
        <section class="glass-panel recharge-dark">
            <div class="section-title">
                <div>
                    <p>STEP 01</p>
                    <h2>Select Recharge</h2>
                </div>

                <span>Win 0 Coins 🪙</span>
            </div>

            <div class="dark-package-grid">
                <?php foreach (($product["packages"] ?? []) as $i => $pkg): ?>
                    <label class="dark-package">
                        <input type="radio" name="package" value="<?= e($i) ?>" required>
<b><?= e($pkg["name"] ?? "") ?></b>
                        <strong><?= money($pkg["price"] ?? 0) ?></strong>
                    </label>
                <?php endforeach; ?>
            </div>
        </section>

        <aside class="side-stack">
            <section class="glass-panel compact-panel">
                <div class="section-title mini">
                    <div>
                        <p>STEP 02</p>
                        <h2>Account Info</h2>
                    </div>
                </div>

                <label class="field-label">Free Fire UID</label>

                <input
                    class="dark-input"
                    id="player_uid"
                    type="text"
                    name="player_uid"
                    value="<?= e($user["game_uid"] ?? "") ?>"
                    placeholder="এখানে আইডি কোড দিন"
                    required
                >

                <button type="button" class="outline-btn uid-check-btn" id="checkPlayerBtn">Check player name</button>
                <div class="uid-result" id="uidResult"></div>
            </section>

            <section class="glass-panel compact-panel wallet-panel">
                <div class="section-title mini">
                    <div>
                        <p>STEP 03</p>
                        <h2>Wallet Payment</h2>
                    </div>
                </div>

                <?php if (!$user): ?>
                    <p class="soft-text">Product কিনতে হলে আগে login করতে হবে।</p>
                    <a class="green-full-btn" href="login.php">Login</a>
                <?php else: ?>
                    <div class="wallet-balance-box">
                        <small>Your Wallet Balance</small>
                        <strong><?= money($user["balance"] ?? 0) ?></strong>
                        <p>Selected package price wallet theke auto cut hobe.</p>
                    </div>

                    <label class="field-label">Coupon Code</label>
                    <input class="dark-input" type="text" name="coupon_code" placeholder="Coupon thakle ekhane din">
                    <small class="soft-text">Coupon apply korle order submit-er time-e price discount hobe.</small>

                    <button class="green-full-btn" type="submit">Buy With Wallet</button>
                <?php endif; ?>
            </section>
        </aside>
    </form>

    <section class="glass-panel rules-dark">
        <div class="section-title">
            <div>
                <p>RULES</p>
                <h2>Rules & Conditions</h2>
            </div>
        </div>

        <p><?= nl2br(e($product["rules"] ?? "90N.GameShop")) ?></p>
    </section>
</main>


<script>
document.querySelectorAll(".dark-package input[type='radio']").forEach(input => {
    input.addEventListener("change", () => {
        document.querySelectorAll(".dark-package").forEach(card => card.classList.remove("selected"));
        input.closest(".dark-package").classList.add("selected");
    });
});

const checkBtn = document.getElementById("checkPlayerBtn");
const uidInput = document.getElementById("player_uid");
const uidResult = document.getElementById("uidResult");

if (checkBtn && uidInput && uidResult) {
    checkBtn.addEventListener("click", async () => {
        const uid = uidInput.value.trim();

        if (!uid) {
            uidResult.className = "uid-result error";
            uidResult.textContent = "আগে Free Fire UID দিন";
            checkBtn.textContent = "Check player name";
            return;
        }

        checkBtn.disabled = true;
        checkBtn.textContent = "Checking...";
        uidResult.className = "uid-result loading";
        uidResult.textContent = "Player name check হচ্ছে...";

        try {
            const response = await fetch("check_uid.php?uid=" + encodeURIComponent(uid), {
                headers: { "Accept": "application/json" }
            });

            const data = await response.json();

            if (data.success && data.name) {
                checkBtn.textContent = data.name;
                uidResult.className = "uid-result success";
                uidResult.textContent = "Player found: " + data.name;
            } else {
                checkBtn.textContent = "Check player name";
                uidResult.className = "uid-result error";
                uidResult.textContent = data.message || "Player name পাওয়া যায়নি";
            }
        } catch (error) {
            checkBtn.textContent = "Check player name";
            uidResult.className = "uid-result error";
            uidResult.textContent = "API problem. আবার চেষ্টা করুন।";
        }

        checkBtn.disabled = false;
    });
}
</script>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
<script>
document.querySelectorAll('.dark-package input[type="radio"]').forEach(function(input){
    input.addEventListener('change', function(){
        document.querySelectorAll('.dark-package').forEach(function(card){ card.classList.remove('selected'); });
        if (input.checked) input.closest('.dark-package').classList.add('selected');
    });
});
</script>

<script id="PACKAGE_TEXT_ONLY_SELECTED_JS">
document.querySelectorAll('.dark-package input[type="radio"]').forEach(function(input){
    function refreshPackageCards(){
        document.querySelectorAll('.dark-package').forEach(function(card){
            const radio = card.querySelector('input[type="radio"]');
            card.classList.toggle('selected', !!radio && radio.checked);
        });
    }
    input.addEventListener('change', refreshPackageCards);
    refreshPackageCards();
});
</script>
